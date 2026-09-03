<?php
declare(strict_types=1);

/**
 * Ingestprobe — nimmt die Uhr-Schnittstelle nach der Ausduennung noch das
 * Richtige an? (S2/AP3, E-S2-08)
 *
 * WOFUER. AP3 aendert `ingest.php` an der gefaehrlichsten Stelle, die es
 * gibt: Punkte, die die Uhr schickt, werden unter bestimmten Umstaenden
 * VERWORFEN — und dann so quittiert, dass die Uhr sie loescht. Ein Fehler
 * dabei ist stiller, endgueltiger Datenverlust, und er faellt niemandem auf,
 * weil die Antwort „ok" lautet.
 *
 * Drei Faelle sind zu unterscheiden, und die Grenze zwischen ihnen ist die
 * STUFE der Spur, nicht ihre Punktzahl:
 *
 *   Stufe 1/2, seq >= n_original   annehmen  (Nachzuegler, E-S2-08)
 *   Stufe 3,   seq >= n_original   verwerfen und quittieren
 *   jede Stufe, seq <  n_original  still uebergehen (Wiederholung)
 *
 * Der zweite Fall darf den ersten nicht verschlucken: Wer statt der Stufe
 * pruefte, ob ueberhaupt ein Blob dasteht, wirft bei Stufe 2 genau die Punkte
 * weg, die der naechste Verdichtungslauf einarbeiten soll.
 *
 * UEBER ECHTES HTTP, nicht ueber Funktionsaufrufe. Was hier geprueft wird,
 * ist ein ENDPUNKT: Kopfzeilen, Authentifizierung, JSON-Antwort. Ein
 * Funktionsaufruf umginge die Haelfte davon.
 *
 * SIE LEGT IHR EIGENES KONTO AN und raeumt es am Ende wieder ab. Bestehende
 * Daten fasst sie nicht an.
 *
 * Aufruf:
 *   php tools/ingestprobe/probe.php [basisadresse]
 *   (Vorgabe: http://127.0.0.1:8080)
 *
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 2) . '/server';
require_once $wurzel . '/config.php';
require_once $wurzel . '/db.php';
require_once $wurzel . '/spur_lib.php';
require_once $wurzel . '/jobs_lib.php';

$basis = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$pdo   = db();

$erwartungen = 0; $offen = 0;
function pruefe(bool $ok, string $was, string $wert = ''): void {
    global $erwartungen, $offen;
    $erwartungen++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-58s %s\n", $ok ? 'ok ' : 'FEHL', $was, $wert);
}

/* ---- Konto und Geraet ---------------------------------------------------- */

$email = 'ingestprobe@gen-em.org';

/* DAS KONTO UND DAS GERAET ENTSTEHEN PER SQL, nicht ueber die Oberflaeche.
 * Das ist eine bewusste Abkuerzung und keine Nachlaessigkeit: Geprueft wird
 * `ingest.php`, nicht die Geraeteverwaltung. Fuer den Weg ueber die
 * Oberflaeche gibt es tools/referenzdatensatz/einspielen/. Das Konto traegt
 * kein Schluesselmaterial und kann keine geschuetzten Angaben halten — es
 * braucht auch keine, denn die Uhr sendet keine. */
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$email]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Ingestprobe', 'user', '', '', 320000)")->execute([$email]);
$uid = (int)$pdo->lastInsertId();

$geraetKennung = 'dev-ingestprobe';
$geraetKey     = bin2hex(random_bytes(24));
$pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label, active)
               VALUES (?,?,?,?,1)')
    ->execute([$uid, $geraetKennung, geraet_schluessel_hash($geraetKey),
               'Ingestprobe']);

/** Eine Anfrage an ingest.php — echtes HTTP, wie die Uhr sie stellt. */
function senden(array $koerper): array {
    global $basis, $geraetKennung, $geraetKey;
    $ch = curl_init("$basis/ingest.php");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json',
                               'X-Device-Id: ' . $geraetKennung,
                               'X-Api-Key: ' . $geraetKey],
        CURLOPT_POSTFIELDS => json_encode($koerper),
        CURLOPT_TIMEOUT => 60,
    ]);
    $roh = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $d = json_decode((string)$roh, true);
    return ['code' => $code, 'daten' => is_array($d) ? $d : ['roh' => $roh]];
}

/** Ein Paket mit n Punkten ab seq_from. */
function paket(string $ref, int $seqFrom, int $n, bool $final = false,
               int $tsBasis = 1750000000): array {
    $punkte = [];
    for ($i = 0; $i < $n; $i++) {
        $s = $seqFrom + $i;
        $punkte[] = [47.0 + $s * 0.0002, 11.0 + $s * 0.0002, 700.0 + $s * 0.5,
                     $tsBasis + $s * 10];
    }
    return ['kind' => 'mission', 'client_ref' => $ref, 'day' => '2026-03-01',
            'started_at' => '2026-03-01T06:00:00Z',
            'ended_at' => $final ? '2026-03-01T08:00:00Z' : null,
            'final' => $final,
            'track' => ['seq_from' => $seqFrom, 'points' => $punkte]];
}

function zeilen(PDO $pdo, int $id): int {
    $q = $pdo->prepare("SELECT COUNT(*) FROM track_points WHERE owner_type='mission' AND owner_id=?");
    $q->execute([$id]); return (int)$q->fetchColumn();
}

echo "Ingestprobe gegen $basis\n";
echo "  Konto $email (uid $uid), Geraet $geraetKennung\n";

/* DIE JOBS ANHALTEN. Sie wuerden waehrend der Probe verdichten und
 * ausduennen — die Probe stellt die Stufen selbst her und will genau wissen,
 * welche gerade gilt. */
jobs_pause(900);

try {

/* ---- Teil 1 — Der gewoehnliche Weg ist unberuehrt ------------------------ */

echo "\n  Teil 1 — Stufe 1: Punkte kommen an wie bisher\n";
$a = senden(paket('probe-1', 0, 100));
$mid = (int)($a['daten']['id'] ?? 0);
pruefe($a['code'] === 200 && ($a['daten']['ok'] ?? false) === true,
       'Erstes Teilstueck wird angenommen',
       "HTTP {$a['code']}, stored " . ($a['daten']['stored_points'] ?? '?'));
pruefe(($a['daten']['stored_points'] ?? -1) === 100 && ($a['daten']['next_seq'] ?? -1) === 100,
       'stored_points und next_seq stimmen',
       "stored " . ($a['daten']['stored_points'] ?? '?')
       . ", next_seq " . ($a['daten']['next_seq'] ?? '?'));
pruefe(!isset($a['daten']['dropped_points']),
       'Kein dropped_points, wo nichts verworfen wurde');

$b = senden(paket('probe-1', 100, 100, true));
pruefe(($b['daten']['stored_points'] ?? -1) === 100 && ($b['daten']['next_seq'] ?? -1) === 200,
       'Zweites Teilstueck haengt an', 'next_seq ' . ($b['daten']['next_seq'] ?? '?'));

// Wiederholung desselben Teilstuecks
$c = senden(paket('probe-1', 100, 100, true));
pruefe(($c['daten']['stored_points'] ?? -1) === 0 && ($c['daten']['next_seq'] ?? -1) === 200,
       'Eine Wiederholung speichert nichts und quittiert dasselbe',
       'stored ' . ($c['daten']['stored_points'] ?? '?')
       . ', next_seq ' . ($c['daten']['next_seq'] ?? '?'));
pruefe(zeilen($pdo, $mid) === 200, 'Es stehen genau 200 Zeilen da', (string)zeilen($pdo, $mid));

/* ---- Teil 2 — Die Ankunftszeit ------------------------------------------ */

echo "\n  Teil 2 — Die Ankunftszeit wird gefuehrt (Grundlage von E-S2-06)\n";
$q = $pdo->prepare('SELECT letzter_punkt_am FROM missions WHERE id = ?');
$q->execute([$mid]);
$lpa = $q->fetchColumn();
pruefe($lpa !== null && $lpa !== false,
       'letzter_punkt_am ist gesetzt', (string)($lpa ?: 'NULL'));

// Eine reine Wiederholung darf sie NICHT fortschreiben.
$pdo->prepare("UPDATE missions SET letzter_punkt_am = '2020-01-01 00:00:00' WHERE id = ?")
    ->execute([$mid]);
senden(paket('probe-1', 100, 100, true));
$q->execute([$mid]);
pruefe((string)$q->fetchColumn() === '2020-01-01 00:00:00',
       'Eine reine Wiederholung schreibt sie NICHT fort',
       'sonst hielte eine Uhr im Kreis ihre Einsaetze ewig aus der Verdichtung');

/* ---- Teil 3 — Nachzuegler an Stufe 2 duerfen NICHT verworfen werden ------ */

echo "\n  Teil 3 — Stufe 2: Nachzuegler werden angenommen (E-S2-08)\n";
$punkte = spur_lesen($pdo, 'mission', $mid);
$blob = spur_kodieren($punkte, SPUR_STUFE_ROH, count($punkte));
$pdo->beginTransaction();
spur_blob_schreiben($pdo, 'mission', $mid, $blob, SPUR_STUFE_ROH, 200, 200);
spur_loeschen_nur_zeilen($pdo, 'mission', $mid, 200);
$pdo->commit();
pruefe(zeilen($pdo, $mid) === 0, 'Spur ist verdichtet (Stufe 2, 0 Zeilen)');

$d = senden(paket('probe-1', 200, 10, true));
pruefe(($d['daten']['stored_points'] ?? -1) === 10,
       'Nachzuegler an einer Stufe-2-Spur werden GESPEICHERT',
       'stored ' . ($d['daten']['stored_points'] ?? '?')
       . ' — wer hier verwirft, verliert sie unwiederbringlich');
pruefe(!isset($d['daten']['dropped_points']),
       'und werden nicht als verworfen gemeldet');
pruefe(($d['daten']['next_seq'] ?? -1) === 210,
       'next_seq zaehlt weiter', (string)($d['daten']['next_seq'] ?? '?'));

// Wiederholung unterhalb n_original: still uebergehen, keine neue Zeile.
$vorher = zeilen($pdo, $mid);
$e = senden(paket('probe-1', 0, 50));
pruefe(zeilen($pdo, $mid) === $vorher && ($e['daten']['stored_points'] ?? -1) === 0,
       'Wiederholung unterhalb n_original legt KEINE unsichtbare Zeile an',
       "$vorher Zeilen vorher und nachher");

/* ---- Teil 4 — Stufe 3: verwerfen und quittieren -------------------------- */

echo "\n  Teil 4 — Stufe 3: verwerfen, aber quittieren (E-S2-08)\n";
// Sauber machen: Nachzuegler einarbeiten, dann ausduennen.
$punkte = spur_lesen($pdo, 'mission', $mid);
$n = count($punkte);
$pdo->beginTransaction();
spur_blob_schreiben($pdo, 'mission', $mid, spur_kodieren($punkte, SPUR_STUFE_ROH, $n),
                    SPUR_STUFE_ROH, $n, $n);
spur_loeschen_nur_zeilen($pdo, 'mission', $mid, $n);
$pdo->commit();
$behalten = spur_ausduennen($punkte, spur_schutzzeiten($pdo, 'mission', $mid));
$duenn = []; foreach ($behalten as $i) { $duenn[] = $punkte[$i]; }
spur_blob_schreiben($pdo, 'mission', $mid,
                    spur_kodieren($duenn, SPUR_STUFE_DUENN, $n),
                    SPUR_STUFE_DUENN, $n, count($duenn));
$stand = spur_stand($pdo, 'mission', $mid);
pruefe($stand['stufe'] === SPUR_STUFE_DUENN && $stand['n_original'] === $n,
       'Spur ist ausgeduennt, n_original steht',
       "stufe {$stand['stufe']}, n_original {$stand['n_original']}, "
       . "gespeichert {$stand['n_gespeichert']}");

$f = senden(paket('probe-1', $n, 10, true));
pruefe(($f['daten']['dropped_points'] ?? -1) === 10
       && ($f['daten']['stored_points'] ?? -1) === 0,
       'Punkte hinter einer ausgeduennten Spur werden verworfen',
       'dropped ' . ($f['daten']['dropped_points'] ?? '?')
       . ', stored ' . ($f['daten']['stored_points'] ?? '?'));
pruefe(($f['daten']['next_seq'] ?? -1) === $n + 10,
       'und trotzdem QUITTIERT — sonst sendet die Uhr ewig weiter',
       'next_seq ' . ($f['daten']['next_seq'] ?? '?') . " (erwartet " . ($n + 10) . ')');
pruefe(zeilen($pdo, $mid) === 0,
       'Es entsteht keine einzige Zeile', (string)zeilen($pdo, $mid));
pruefe(!isset($f['daten']['rejected']),
       'Das ist KEIN Datenfehler — rejected bleibt leer',
       'sonst saehe jeder Upload einer alten Spur wie ein Fehler aus');

// Gemischtes Paket an der Grenze.
$g = senden(paket('probe-1', $n - 4, 10, true));
pruefe(($g['daten']['dropped_points'] ?? -1) === 6
       && ($g['daten']['stored_points'] ?? -1) === 0,
       'Gemischtes Paket: nur der Teil OBERHALB n_original zaehlt als verworfen',
       'dropped ' . ($g['daten']['dropped_points'] ?? '?') . ' (erwartet 6)');

/* ---- Teil 5 — Die Untergrenze von next_seq (E-S2-25) --------------------- */

echo "\n  Teil 5 — next_seq quittiert auch, was die Wertepruefung verwirft\n";
$h = ['kind' => 'mission', 'client_ref' => 'probe-2', 'day' => '2026-03-02',
      'started_at' => '2026-03-02T06:00:00Z', 'ended_at' => '2026-03-02T07:00:00Z',
      'final' => true,
      'track' => ['seq_from' => 0, 'points' => [
          [47.0, 11.0, 700.0, 1750000000],
          [47.1, 11.1, 701.0, 1750000010],
          [91.0, 11.2, 702.0, 1750000020],     // unbrauchbare Breite: LETZTER Punkt
      ]]];
$r = senden($h);
$mid2 = (int)($r['daten']['id'] ?? 0);
pruefe(($r['daten']['next_seq'] ?? -1) === 3,
       'Ein am Ende gescheiterter Punkt blockiert die Marke nicht mehr',
       'next_seq ' . ($r['daten']['next_seq'] ?? '?') . ' (erwartet 3; vorher 2 — '
       . 'die Uhr raeumt erst bei next_seq >= pointCount auf und sandte endlos)');
pruefe(isset($r['daten']['rejected']) && ($r['daten']['stored_points'] ?? -1) === 2,
       'Der verworfene Wert wird trotzdem benannt',
       'stored ' . ($r['daten']['stored_points'] ?? '?')
       . ', rejected ' . json_encode($r['daten']['rejected'] ?? null));

/* ---- Teil 6 — Die Ortshoehe ueberlebt eine berichtigte Phasenzeit -------- */

echo "\n  Teil 6 — Ausgeduennt: eine berichtigte Phasenzeit loescht die Ortshoehe nicht\n";
require_once $wurzel . '/site_elevation_lib.php';

// Eine Phase auf einen Zeitpunkt legen, an dem die Spur einen Punkt hat.
$punkte = spur_lesen($pdo, 'mission', $mid);
$mitte  = $punkte[(int)(count($punkte) / 2)];
$pdo->prepare('DELETE FROM mission_phases WHERE mission_id = ?')->execute([$mid]);
$pdo->prepare('INSERT INTO mission_phases (mission_id, phase, occurred_at)
               VALUES (?, 5, FROM_UNIXTIME(?))')->execute([$mid, $mitte[4]]);
compute_site_elevation($pdo, $mid);
$q = $pdo->prepare('SELECT site_ele_m FROM missions WHERE id = ?');
$q->execute([$mid]);
$hoeheVor = $q->fetchColumn();
pruefe($hoeheVor !== null && $hoeheVor !== false,
       'Die Ortshoehe steht (Phase auf einem behaltenen Punkt)',
       'site_ele_m = ' . var_export($hoeheVor, true));

// Phase weit weg schieben — weiter als SITE_ELE_TOLERANCE_S von jedem Punkt.
$pdo->prepare('UPDATE mission_phases SET occurred_at = FROM_UNIXTIME(?)
                WHERE mission_id = ? AND phase = 5')
    ->execute([(int)$mitte[4] + 100000, $mid]);
compute_site_elevation($pdo, $mid);
$q->execute([$mid]);
$hoeheNach = $q->fetchColumn();
pruefe((string)$hoeheNach === (string)$hoeheVor,
       'Nach dem Verschieben bleibt sie stehen statt still zu verschwinden',
       'vorher ' . var_export($hoeheVor, true) . ', nachher ' . var_export($hoeheNach, true));

/* GEGENPROBE: Auf einer Spur der Stufe 2 muss NULL weiterhin geschrieben
 * werden — dort traegt die Spur alle Punkte, ein leeres Ergebnis ist die
 * Wahrheit und keine Folge der Ausduennung. */
$vollBlob = spur_kodieren($punkte, SPUR_STUFE_ROH, count($punkte));
spur_blob_schreiben($pdo, 'mission', $mid, $vollBlob, SPUR_STUFE_ROH,
                    count($punkte), count($punkte));
compute_site_elevation($pdo, $mid);
$q->execute([$mid]);
pruefe($q->fetchColumn() === null,
       'GEGENPROBE Stufe 2: dort wird NULL sehr wohl geschrieben',
       'sonst bliebe ein falscher Wert stehen, den niemand mehr los wird');

} finally {
    jobs_pause(0);
    /* Aufraeumen: das Konto und alles daran. Die Kaskade nimmt missions mit;
     * Spuren haengen an keinem Fremdschluessel und muessen ausdruecklich weg
     * (F-S2-B) — genau der Fund, den AP1 behoben hat. */
    $ids = $pdo->prepare('SELECT id FROM missions WHERE user_id = ?');
    $ids->execute([$uid]);
    $mIds = array_map('intval', $ids->fetchAll(PDO::FETCH_COLUMN));
    if ($mIds) { spur_loeschen($pdo, 'mission', $mIds); }
    $ids = $pdo->prepare('SELECT id FROM rest_segments WHERE user_id = ?');
    $ids->execute([$uid]);
    $rIds = array_map('intval', $ids->fetchAll(PDO::FETCH_COLUMN));
    if ($rIds) { spur_loeschen($pdo, 'rest', $rIds); }
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
    echo "\n  Konto und Spuren der Probe wieder entfernt.\n";
}

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $erwartungen, $offen);
exit($offen === 0 ? 0 : 1);
