<?php
/* Die Gerätehilfe der Proben — senden wie ein Gerät.
 *
 * Anlass: Nr. 43 — seit S11 nimmt `ingest.php` Spur, Phasenkoordinaten und
 * Reanimation nur noch verschlossen an. Die Proben, die den Eingang wie ein
 * Gerät bedienen, schrieben ihre Pakete im alten Format; diese Datei
 * verwandelt ein solches Paket in das neue, an EINER Stelle, statt dass jede
 * Probe ihr eigenes Verschließen baut.
 *
 *   gh_probe_paar()          ein Schlüsselpaar für die Probe (privat, SEC1)
 *   gh_probe_koerper()       altes Paket -> neues: `track.points` wird ein
 *                            verschlossenes Stück, Koordinaten der Phasen und
 *                            die Reanimation wandern in `ort_huelle`
 *   gh2_probe_oeffnen()      eine Hülle des Probegeräts öffnen — NUR HIER,
 *                            im Prüfwerkzeug. Der Server hat diese Funktion
 *                            mit Absicht nicht.
 *   gh_probe_einsatz()       die Einsatzhülle eines Einsatzes öffnen und als
 *                            Feld zurückgeben
 *   gh_probe_geraet()        das Schlüsselpaar DES Probegeräts (eines je Lauf)
 *   gh_probe_herkunft()      die Geräte eines Kontos so stellen, als hätte der
 *                            Browser sie nach dem Vergleichswert eingetragen
 *                            (`devices.spk`, `herkunft = 1`)
 *
 * SEIT WEB 26.0.0 IN FASSUNG 2 (06e, KA-20): Der Eingang nimmt Fassung 1
 * nicht mehr an (Stichtag). `gh_probe_koerper()` verschließt deshalb wie ein
 * gekoppeltes Gerät — als Absender das Probegerät, mit dem Zähler `z` in der
 * Einsatzhülle. Die Rechnung steht in `referenz.py` (`verschliessen2()`);
 * der Server hat sie mit Absicht nicht. Bis Web 25.x schrieb diese Datei
 * Fassung 1. Seit KA-26 öffnet sie Fassung 1 auch nicht mehr
 * (`gh_probe_oeffnen()` ist entfallen); gebaut wird sie nur noch als
 * Gegenlage (`gh_verschliessen()` in `geraetehuelle_lib.php`).
 *
 * DIE INNENFORM DER EINSATZHÜLLE steht im Kopf von `referenz.py` und hier
 * als Code: {"fassung":2, "z", "start", "schluss", "phasen":[{"phase","t",
 * "lat","lon"}], "rea":[{"t","ereignisse":[{"art","t"}]}]} — alle Zeiten in
 * Sekunden seit `start`, damit ein Datumswechsel des Diensttags die Hülle
 * nicht ungültig macht.
 */
declare(strict_types=1);

require_once __DIR__ . '/geraetehuelle_lib.php';

/** Das Probegerät: EIN festes Schlüsselpaar, wie der Geräteschlüssel. */
function gh_probe_geraet(): array
{
    static $g = null;
    if ($g === null) {
        /* FEST, aus einem Namen abgeleitet (wie `schluessel_aus_name()` der
         * Referenz): `geraet_cli.php` läuft je Aufruf als eigener Prozess,
         * und der Eingang prüft die SID gegen `devices.spk`. */
        $k = gh_privat_aus_d(hash('sha256', 'nadoku-probegeraet-1', true));
        $sec1 = gh_sec1_von($k);
        $g = ['privat' => $k, 'sec1' => $sec1,
              'sid' => substr(hash('sha256', 'nadoku-absender-1' . $sec1, true), 0, 8)];
    }
    return $g;
}

/** Die Geräte eines Kontos (oder eines) bekommen den Schlüssel des Probegeräts
 *  und gelten als vom Browser bestätigt — nur in der örtlichen Anlage. */
function gh_probe_herkunft(PDO $pdo, int $userId, ?string $deviceId = null): void
{
    $sql = 'UPDATE devices SET spk = ?, herkunft = 1 WHERE user_id = ?' . ($deviceId !== null ? ' AND device_id = ?' : '');
    $pdo->prepare($sql)->execute(array_merge([base64_encode(gh_probe_geraet()['sec1']), $userId],
                                             $deviceId !== null ? [$deviceId] : []));
}

/** Eine Hülle der Fassung 2 vom Probegerät (Rechnung: `referenz.py`, `verschliessen2()`). */
function gh2_probe_verschliessen(string $klar, string $kontoSec1, string $art, string $ref, string $platz): string
{
    $g = gh_probe_geraet();
    $fl = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    $epk = gh_sec1_von($fl);
    $konto = gh_oeffentlich_laden($kontoSec1);
    $zE = openssl_pkey_derive($konto, $fl, 32);
    $zS = openssl_pkey_derive($konto, $g['privat'], 32);
    $okm = hash_hkdf('sha256', $zE . $zS, 64, 'nadoku-huelle-2' . $kontoSec1 . $g['sec1'], $epk);
    $iv = random_bytes(16);
    $ct = openssl_encrypt($klar, 'aes-256-cbc', substr($okm, 0, 32), OPENSSL_RAW_DATA, $iv);
    $kopf = "\x02" . $g['sid'] . $epk . $iv;
    return $kopf . $ct . hash_hmac('sha256', $kopf . $ct . gh_bindung($art, $ref, $platz), substr($okm, 32), true);
}

/** Eine Hülle der Fassung 2 öffnen (Konto) — NUR im Prüfwerkzeug. Liefert
 *  das Innere (bei einem Spurstück mit Kopf), wirft mit dem Grund. */
function gh2_probe_oeffnen(string $h, $kontoPriv, string $art, string $ref, string $platz = ''): string
{
    if (strlen($h) < 90 + 16 + 32 || (strlen($h) - 90 - 32) % 16) { throw new RuntimeException('laenge'); }
    if (ord($h[0]) !== 2) { throw new RuntimeException('fassung'); }
    $g = gh_probe_geraet();
    if (substr($h, 1, 8) !== $g['sid']) { throw new RuntimeException('absender'); }
    $epk = substr($h, 9, 65);
    $iv = substr($h, 74, 16);
    $kontoSec1 = gh_sec1_von($kontoPriv);
    $zE = openssl_pkey_derive(gh_oeffentlich_laden($epk), $kontoPriv, 32);
    $zS = openssl_pkey_derive(gh_oeffentlich_laden($g['sec1']), $kontoPriv, 32);
    $okm = hash_hkdf('sha256', $zE . $zS, 64, 'nadoku-huelle-2' . $kontoSec1 . $g['sec1'], $epk);
    $soll = hash_hmac('sha256', substr($h, 0, -32) . gh_bindung($art, $ref, $platz), substr($okm, 32), true);
    if (!hash_equals($soll, substr($h, -32))) { throw new RuntimeException('pruefwert'); }
    $klar = openssl_decrypt(substr($h, 90, -32), 'aes-256-cbc', substr($okm, 0, 32), OPENSSL_RAW_DATA, $iv);
    if ($klar === false) { throw new RuntimeException('polsterung'); }
    return $klar;
}

/** Die Einsatzhülle in Fassung 2 vom Probegerät (bis KA-26 daneben
 *  `gh_einsatz_verschliessen()` in Fassung 1). */
function gh2_probe_einsatz_verschliessen(string $sec1, string $ref, int $beginn, array $phasen, array $reas,
                                         bool $schluss = true): string
{
    $innen = ['fassung' => 2, 'z' => gh2_probe_zaehler(), 'start' => $beginn, 'schluss' => $schluss,
              'phasen' => [], 'rea' => []];
    foreach ($phasen as $p) {
        $innen['phasen'][] = ['phase' => (int)$p['phase'], 't' => (int)$p['ts'] - $beginn,
                              'lat' => $p['lat'] === null ? null : (float)$p['lat'],
                              'lon' => $p['lon'] === null ? null : (float)$p['lon']];
    }
    foreach ($reas as $r) {
        $ev = [];
        foreach ($r['ereignisse'] as $e) { $ev[] = ['art' => (string)$e['art'], 't' => (int)$e['ts'] - $beginn]; }
        $innen['rea'][] = ['t' => (int)$r['ts'] - $beginn, 'ereignisse' => $ev];
    }
    return gh2_probe_verschliessen(json_encode($innen, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                                   $sec1, 'einsatz', $ref, '');
}

/** Punkte zu Stücken in Fassung 2 vom Probegerät (Gruppen wie
 *  `EdOrtsdaten.stueckeBauen()`); SCHLUSS am letzten Stück, wenn `$schluss`.
 *  @return list<array{seq_from:int, punkte:int, ts_von:int, ts_bis:int, huelle:string}> */
function gh2_probe_stuecke_bauen(string $sec1, string $art, string $ref, array $punkte, bool $schluss = false): array
{
    $aus = gh_stuecke_gruppieren($punkte);
    $n = count($aus);
    foreach ($aus as $i => &$st) {
        $innen = pack('JCCn', $st['ts_von'], ($schluss && $i === $n - 1) ? 1 : 0, 0, 0)
               . gh_spur_innen(array_map(static fn($p) => [$p[1], $p[2], $p[3], (int)$p[4]], $st['gruppe']),
                               $st['ts_von']);
        unset($st['gruppe']);
        $st['huelle'] = gh2_probe_verschliessen($innen, $sec1, $art, $ref, $st['seq_from'] . ':' . $st['punkte']);
    }
    unset($st);
    return $aus;
}

/** Der Zähler `z` der Einsatzhülle: eine Unix-Sekunde, streng steigend (E-KA-69). */
function gh2_probe_zaehler(): int
{
    static $z = 0;
    $z = max($z + 1, time());
    return $z;
}

/** @return array{privat:mixed, sec1:string} */
function gh_probe_paar(): array
{
    $k = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    return ['privat' => $k, 'sec1' => gh_sec1_von($k)];
}

function gh_probe_iso_zu_s(string $iso): int
{
    return (int)strtotime($iso);
}

/**
 * Ein Paket im alten Format (Klartext) so umbauen, wie ein Gerät sendet.
 * Ohne Punkte fällt `track` weg; ohne Koordinaten und Reanimation entsteht
 * trotzdem eine Einsatzhülle, sobald Phasen dabei sind — das tut auch das
 * Gerät, damit die Hülle zu den Phasen passt.
 */
function gh_probe_koerper(array $k, string $sec1): array
{
    $ref = (string)($k['client_ref'] ?? '');
    $art = ($k['kind'] ?? '') === 'rest_segment' ? 'ruhe' : 'spur';
    if (isset($k['track']) && is_array($k['track']) && array_key_exists('points', $k['track'])) {
        $pts = $k['track']['points'];
        $seq = (int)($k['track']['seq_from'] ?? 0);
        if (!$pts) {
            unset($k['track']);
        } else {
            $ts = array_map(fn($p) => (int)$p[3], $pts);
            $von = min($ts);
            $n = count($pts);
            $k['track'] = ['seq_from' => $seq, 'punkte' => $n, 'ts_von' => $von, 'ts_bis' => max($ts),
                           'huelle' => base64_encode(gh2_probe_verschliessen(
                               pack('JCCn', $von, !empty($k['final']) ? 1 : 0, 0, 0) . gh_spur_innen($pts, $von),
                               $sec1, $art, $ref, $seq . ':' . $n))];
        }
    }
    if (($k['kind'] ?? '') === 'mission') {
        $beginn = gh_probe_iso_zu_s((string)($k['started_at'] ?? 'now'));
        $phasen = [];
        $hatPhasen = isset($k['phases']) && is_array($k['phases']);
        foreach ($hatPhasen ? $k['phases'] : [] as $i => $p) {
            $phasen[] = ['phase' => (int)($p['phase'] ?? 0),
                         't' => gh_probe_iso_zu_s((string)($p['at'] ?? '')) - $beginn,
                         'lat' => $p['lat'] ?? null, 'lon' => $p['lon'] ?? null];
            unset($k['phases'][$i]['lat'], $k['phases'][$i]['lon']);
        }
        $sitzungen = $k['resus_sessions'] ?? (!empty($k['resus']) ? [$k['resus']] : []);
        $rea = [];
        foreach ($sitzungen as $s) {
            $ev = [];
            foreach ($s['events'] ?? [] as $e) {
                $ev[] = ['art' => (string)($e['type'] ?? ''), 't' => gh_probe_iso_zu_s((string)($e['at'] ?? '')) - $beginn];
            }
            $rea[] = ['t' => gh_probe_iso_zu_s((string)($s['started_at'] ?? '')) - $beginn, 'ereignisse' => $ev];
        }
        unset($k['resus_sessions'], $k['resus']);
        if ($hatPhasen || $rea) {
            $innen = json_encode(['fassung' => 2, 'z' => gh2_probe_zaehler(), 'start' => $beginn,
                                  'schluss' => !empty($k['final']), 'phasen' => $phasen, 'rea' => $rea],
                                 JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $k['ort_huelle'] = base64_encode(gh2_probe_verschliessen($innen, $sec1, 'einsatz', $ref, ''));
        }
    }
    return $k;
}

/** Die Einsatzhülle eines Einsatzes, geöffnet — oder null, wenn keine dasteht.
 *  Nur Fassung 2 vom Probegerät; alles andere wirft mit dem Grund. */
function gh_probe_einsatz(PDO $pdo, int $missionId, $privat): ?array
{
    $q = $pdo->prepare('SELECT client_ref, ort_huelle FROM missions WHERE id = ?');
    $q->execute([$missionId]);
    $r = $q->fetch(PDO::FETCH_ASSOC);
    if (!$r || $r['ort_huelle'] === null) { return null; }
    return json_decode(gh2_probe_oeffnen((string)$r['ort_huelle'], $privat, 'einsatz', (string)$r['client_ref']), true);
}
