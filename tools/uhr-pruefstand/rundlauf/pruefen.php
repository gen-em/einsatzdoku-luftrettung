<?php
/* Rundlauf der Uhr — die Gegenseite.
 *
 * Anlass: Nr. 43 — die Garmin-Uhr verschliesst seit Uhr 4.0.0 Spur,
 * Phasenkoordinaten und Reanimation; ob ihre Huellen dort aufgehen, wo sie
 * aufgehen muessen, zeigt kein Uebersetzen und kein Selbsttest, sondern nur
 * eine Huelle, die den Weg Uhr -> ingest.php -> Datenbank gegangen ist.
 *
 * WAS ES TUT. Es oeffnet, was die Prueffassung der App (Rundlauf.mc) an das
 * Demo-Konto gesendet hat, mit dessen privatem Schluessel — den kennt der
 * Server der Pruefanlage, weil er das Demo-Passwort kennt (demo_lib.php) —,
 * und haelt jeden Wert gegen dieselbe Rechnung, die Rundlauf.mc anlegt:
 * 600 Punkte in zwei Stuecken (500 + 100), drei Phasen, eine Reanimation,
 * ein Ruhesegment mit 3 Punkten. Jede Abweichung ist ein Fehlschlag.
 *
 * Aufruf (rundlauf.sh tut es): php pruefen.php <einsatz-ref> <ruhe-ref> [2 <spk>]
 *
 * FASSUNG 2 (seit Uhr 5.0.0, KG-03b, Nr. 577): mit `2` und dem
 * Geraeteschluessel (Base64, aus der Kopplung) oeffnet fassung2.py jede
 * Huelle mit der Referenz; dazu muss jedes Stueck den Kopf tragen, das
 * letzte den Merker SCHLUSS, und die Einsatzhuelle Fassung 2 mit Zaehler.
 * Ausgabe: je Pruefung eine Zeile, am Ende "N Pruefungen, M Fehlschlaege".
 */
declare(strict_types=1);

$wurzel = dirname(__DIR__, 3);
chdir($wurzel . '/server');
require 'db.php';
require_once 'demo_lib.php';
require_once $wurzel . '/tools/proben/huelle/geraet.php';

[$_, $einsatzRef, $ruheRef, $fassungArg, $spkB64] = $argv + [null, '', '', '1', ''];
$fassung = (int)$fassungArg === 2 ? 2 : 1;
$n = 0; $fehl = 0;
function pruefe(bool $ok, string $was): void
{
    global $n, $fehl;
    $n++;
    if (!$ok) { $fehl++; }
    printf("  %s  %s\n", $ok ? 'ok  ' : 'FEHL', $was);
}

const T0 = 1792224000;
/** Dieselbe Rechnung wie Rundlauf.punkt() — Hoehe als die Uhr sie innen schreibt. */
function soll_punkt(int $i): array
{
    $ele = null;
    if ($i === 1) { $ele = 4500.5; }
    elseif ($i === 2) { $ele = -12.5; }
    elseif ($i % 50 !== 0) { $ele = (float)(1800 + $i); }
    return [-33.4489 + $i * 0.0001, -70.6693 + $i * 0.0002, $ele, T0 + $i * 5];
}

/** Ein Spurstueck binaer -> Punkte [lat, lon, ele|null, ts] wie die Uhr sie meint. */
function innen_lesen(string $roh, int $tsVon): array
{
    $aus = [];
    for ($o = 0; $o + 14 <= strlen($roh); $o += 14) {
        $u = unpack('Nla/Nlo/nh/Nt', substr($roh, $o, 14));
        $la = $u['la'] >= 0x80000000 ? $u['la'] - 0x100000000 : $u['la'];
        $lo = $u['lo'] >= 0x80000000 ? $u['lo'] - 0x100000000 : $u['lo'];
        $h = $u['h'] >= 0x8000 ? $u['h'] - 0x10000 : $u['h'];
        $aus[] = [$la, $lo, $h === -32768 ? null : $h, $tsVon + $u['t']];
    }
    return $aus;
}

function weg_von_null(float $x): int { return (int)round($x); }   // PHP rundet von der Null weg

$pdo = db();
$demo = demo_id();
if ($demo === null) { fwrite(STDERR, "Kein Demo-Konto.\n"); exit(2); }
$konto = $pdo->query('SELECT * FROM users WHERE id = ' . (int)$demo)->fetch(PDO::FETCH_ASSOC);
$ck = demo_inhaltsschluessel($konto);
$pkcs8 = demo_edk1_oeffnen($ck, (string)$konto['kp_privat']);
if ($pkcs8 === null) { fwrite(STDERR, "Privater Schluessel des Demo-Kontos geht nicht auf.\n"); exit(2); }
$privat = openssl_pkey_get_private("-----BEGIN PRIVATE KEY-----\n" . chunk_split($pkcs8, 64, "\n")
                                   . "-----END PRIVATE KEY-----\n");

/** Eine Huelle oeffnen: Fassung 1 hier (geraet.php), Fassung 2 ueber die
 *  Referenz (fassung2.py). */
function oeffne(string $h, string $art, string $ref, string $platz = ''): string
{
    global $fassung, $privat, $pkcs8, $spkB64;
    if ($fassung !== 2) { return gh_probe_oeffnen($h, $privat, $art, $ref, $platz); }
    $p = proc_open(['python3', '-I', __DIR__ . '/fassung2.py', 'oeffnen'],
                   [0 => ['pipe', 'r'], 1 => ['pipe', 'w']], $rohr);
    fwrite($rohr[0], json_encode(['priv' => $pkcs8, 'spk' => $spkB64, 'huelle' => base64_encode($h),
                                  'art' => $art, 'ref' => $ref, 'platz' => $platz]));
    fclose($rohr[0]);
    $aus = json_decode((string)stream_get_contents($rohr[1]), true);
    fclose($rohr[1]);
    proc_close($p);
    if (!is_array($aus) || !isset($aus['klar'])) { throw new RuntimeException((string)($aus['fehler'] ?? 'helfer')); }
    return (string)base64_decode($aus['klar']);
}

/** Ein Spurstueck innen: Fassung 2 traegt vor den Punkten 12 Byte Kopf
 *  (ts_von absolut, Merker). Gibt [punkte, merker|null] zurueck. */
function spur_lesen(string $roh, int $tsVon): array
{
    global $fassung;
    if ($fassung !== 2) { return [innen_lesen($roh, $tsVon), null]; }
    $kopfTs = unpack('J', substr($roh, 0, 8))[1];
    $merker = ord($roh[8] ?? "\xff");
    if ($kopfTs !== $tsVon) { throw new RuntimeException("kopf-ts $kopfTs statt $tsVon"); }
    return [innen_lesen(substr($roh, 12), $tsVon), $merker];
}

// ---- Der Einsatz -----------------------------------------------------------
$q = $pdo->prepare('SELECT id, client_ref, ort_huelle, started_at FROM missions WHERE user_id = ? AND client_ref = ?');
$q->execute([$demo, $einsatzRef]);
$m = $q->fetch(PDO::FETCH_ASSOC);
pruefe($m !== false, "Einsatz $einsatzRef angekommen");
if ($m !== false) {
    $ph = $pdo->prepare('SELECT phase, occurred_at FROM mission_phases WHERE mission_id = ? ORDER BY occurred_at, id');
    $ph->execute([$m['id']]);
    $phasen = $ph->fetchAll(PDO::FETCH_ASSOC);
    pruefe(count($phasen) === 3, 'drei Phasen im Klartext (Nummer und Zeit): ' . count($phasen));
    /* DIE SPALTEN GIBT ES NICHT MEHR, und die
     * Pruefung fragt deshalb die Tabelle, nicht die Zeilen. Einmal stand
     * hier `SELECT … lat, lon FROM mission_phases` — geschrieben zwei Tage,
     * bevor die Spalten entfielen. Der Rundlauf laeuft nur,
     * wenn `watch/` oder `tools/uhr-pruefstand/` beruehrt ist; so brach er
     * eine Woche lang mit „Unknown column 'lat'" ab, ohne dass ihn jemand
     * fuhr. Keine Spalte ist die schaerfere Aussage als „keine
     * Zeile mit Wert": Ein Klartext-Ort kann gar nicht mehr ankommen. */
    pruefe(!db_hat_spalte($pdo, 'mission_phases', 'lat') && !db_hat_spalte($pdo, 'mission_phases', 'lon'),
           'keine Koordinatenspalte in mission_phases (seit S11-12) — kein Ort im Klartext');

    try {
        $innen = json_decode(oeffne((string)$m['ort_huelle'], 'einsatz', $einsatzRef), true);
        pruefe(is_array($innen) && ($innen['fassung'] ?? 0) === $fassung, "Einsatzhuelle geht auf, Fassung $fassung");
        if ($fassung === 2) {
            /* Zaehler: Unix-Sekunde des Verschliessens (E-KA-64), also
             * nicht kleiner als der Beginn dieses Laufs. */
            pruefe(is_int($innen['z'] ?? null) && $innen['z'] >= (int)getenv('RUNDLAUF_LAUF')
                   && ($innen['schluss'] ?? null) === true,
                   'Einsatzhuelle Fassung 2: Zaehler ' . json_encode($innen['z'] ?? null) . ' ab Laufbeginn, schluss');
        }
        $sollPh = [[2, 0, -33.4489, -70.6693], [3, 300, null, null], [4, 1200, -33.4389, -70.6493]];
        $gut = count($innen['phasen'] ?? []) === 3;
        foreach ($sollPh as $k => [$nr, $t, $la, $lo]) {
            $p = $innen['phasen'][$k] ?? [];
            $gut = $gut && ($p['phase'] ?? null) === $nr && ($p['t'] ?? null) === $t
                && (($la === null) ? $p['lat'] === null : abs($p['lat'] - $la) < 1e-7)
                && (($lo === null) ? $p['lon'] === null : abs($p['lon'] - $lo) < 1e-7);
        }
        pruefe($gut, 'Phasen in der Huelle: Nummer, Zeit seit Beginn, Koordinate (eine ohne Ortung)');
        $rea = $innen['rea'] ?? [];
        pruefe(count($rea) === 1 && $rea[0]['t'] === 1260
               && ($rea[0]['ereignisse'] ?? []) === [['art' => 'adrenalin', 't' => 1350],
                                                     ['art' => 'defibrillation', 't' => 1380]],
               'Reanimation in der Huelle: Beginn 1260 s, zwei Ereignisse');
    } catch (Throwable $e) {
        pruefe(false, 'Einsatzhuelle geht auf: ' . $e->getMessage());
    }

    $st = $pdo->prepare("SELECT seq_from, punkte, n_original, ts_von, ts_bis, stufe, huelle FROM track_stuecke
                         WHERE owner_type = 'mission' AND owner_id = ? ORDER BY seq_from");
    $st->execute([$m['id']]);
    $stuecke = $st->fetchAll(PDO::FETCH_ASSOC);
    pruefe(array_map(fn($s) => [(int)$s['seq_from'], (int)$s['punkte'], (int)$s['stufe']], $stuecke)
           === [[0, 500, 1], [500, 100, 1]], 'zwei Stuecke der Stufe 1: 0:500 und 500:100');
    $alle = [];
    $merker = [];
    foreach ($stuecke as $s) {
        try {
            $roh = oeffne((string)$s['huelle'], 'spur', $einsatzRef, $s['seq_from'] . ':' . $s['punkte']);
            [$pkt, $mk] = spur_lesen($roh, (int)$s['ts_von']);
            $alle = array_merge($alle, $pkt);
            $merker[] = $mk;
        } catch (Throwable $e) {
            pruefe(false, "Stueck {$s['seq_from']} geht auf: " . $e->getMessage());
        }
    }
    $abw = 0;
    for ($i = 0; $i < 600; $i++) {
        [$la, $lo, $ele, $ts] = soll_punkt($i);
        $soll = [weg_von_null($la * 1e7), weg_von_null($lo * 1e7),
                 $ele === null ? null : weg_von_null($ele), $ts];
        if (($alle[$i] ?? null) !== $soll) { $abw++; if ($abw <= 3) { echo '    Punkt ' . $i . ': ' . json_encode($alle[$i] ?? null) . ' statt ' . json_encode($soll) . "\n"; } }
    }
    pruefe(count($alle) === 600 && $abw === 0, "600 Punkte Wert fuer Wert gleich (Abweichungen: $abw)");
    /* Nicht `?? 0` fuer Punkt 0: `??` haelt null fuer „fehlt" und liefert die 0 —
     * genau der Wert, den die Pruefung ausschliessen soll (am 17.10.2026 so
     * einmal falsch rot). */
    pruefe(($alle[1][2] ?? null) === 4501 && ($alle[2][2] ?? null) === -13
           && isset($alle[0]) && $alle[0][2] === null,
           'Hoehe: 4500,5 m -> 4501 (ueber 3276 m, F-S11-64), -12,5 -> -13, keine -> -32768');
    pruefe(isset($stuecke[0]) && (int)$stuecke[0]['ts_von'] === T0 && (int)$stuecke[1]['ts_bis'] === T0 + 599 * 5,
           'Zeitraum im Klartext: ts_von und ts_bis der Stuecke');
    if ($fassung === 2) {
        pruefe($merker === [0, 1], 'Merker: SCHLUSS nur am letzten Stueck ' . json_encode($merker));
    }
}

// ---- Das Ruhesegment -------------------------------------------------------
$q = $pdo->prepare('SELECT id FROM rest_segments WHERE user_id = ? AND client_ref = ?');
$q->execute([$demo, $ruheRef]);
$r = $q->fetchColumn();
pruefe($r !== false, "Ruhesegment $ruheRef angekommen");
if ($r !== false) {
    $st = $pdo->prepare("SELECT seq_from, punkte, ts_von, huelle FROM track_stuecke
                         WHERE owner_type = 'rest' AND owner_id = ? ORDER BY seq_from");
    $st->execute([$r]);
    $s = $st->fetch(PDO::FETCH_ASSOC);
    try {
        [$p, $mk] = spur_lesen(oeffne((string)$s['huelle'], 'ruhe', $ruheRef, '0:3'), (int)$s['ts_von']);
        pruefe($p === [[471000000, 112000000, 600, T0 - 600], [471001000, 112001000, null, T0 - 300],
                       [471002000, 112002000, 601, T0 - 10]],
               'Ruhestueck (Art "ruhe") geht auf, 3 Punkte gleich');
        if ($fassung === 2) { pruefe($mk === 1, 'Ruhestueck: Merker SCHLUSS ' . json_encode($mk)); }
    } catch (Throwable $e) {
        pruefe(false, 'Ruhestueck geht auf: ' . $e->getMessage());
    }
}

printf("%d Pruefungen, %d Fehlschlaege\n", $n, $fehl);
exit($fehl === 0 ? 0 : 1);
