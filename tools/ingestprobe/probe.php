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
 * SEIT WEB 13.0.1 KOMMT EINE ZWEITE FRAGE DAZU (Teil 7): Was macht so ein
 * spaetes Paket mit den METADATEN? Es traegt kein Ende, keine Strecke, keinen
 * Anstieg — und ueberschrieb sie damit bis 13.0.0 mit NULL, waehrend `final`
 * auf 1 blieb. Teil 3 hat diesen Fall seit S2 gesendet, ohne hinzusehen.
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

/* ZWEI GERAETE, UND BEIDE MIT KENNUNG (R64, Teil 8).
 *
 * Das erste gibt sich als UHR aus, das zweite als HANDY. Beide brauchen Art
 * und Modell, denn seit Web 14.0.0 wandert diese Angabe als Momentaufnahme an
 * jeden Einsatz und jedes Segment — und die Probe ist die einzige Stelle, an
 * der sich das ueber echtes HTTP nachweisen laesst.
 *
 * WARUM ZWEI: Die Herkunft entsteht aus dem `client_ref`-PRAEFIX, und die
 * Geraeteart ist nur der Rueckfall fuer ein unbekanntes Praefix (E-R64-01).
 * Mit einem Geraet liesse sich der Rueckfall nicht von der Regel
 * unterscheiden — beim Uhr-Geraet ist 'watch' beides. Das Handy-Geraet
 * trennt die Faelle: Ein Praefix, das die Regel nicht kennt, muss dort
 * 'android' ergeben und nicht 'watch'. */
$geraetKennung = 'dev-ingestprobe';
$geraetKey     = bin2hex(random_bytes(24));
$pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label, active,
                                    geraet_art, geraet_modell)
               VALUES (?,?,?,?,1,?,?)')
    ->execute([$uid, $geraetKennung, geraet_schluessel_hash($geraetKey),
               'Ingestprobe', 'uhr', 'fēnix 7 / fēnix 7 Solar / fēnix 7 Sapphire Solar']);

$handyKennung = 'dev-ingestprobe-handy';
$handyKey     = bin2hex(random_bytes(24));
$pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label, active,
                                    geraet_art, geraet_modell)
               VALUES (?,?,?,?,1,?,?)')
    ->execute([$uid, $handyKennung, geraet_schluessel_hash($handyKey),
               'Ingestprobe Handy', 'handy', 'Google Pixel 8']);

/**
 * Eine Anfrage an ingest.php — echtes HTTP, wie die Uhr sie stellt.
 *
 * Ohne die beiden letzten Angaben spricht sie mit dem Uhr-Geraet; Teil 8
 * reicht die Zugangsdaten des Handys herein.
 */
function senden(array $koerper, ?string $dev = null, ?string $key = null): array {
    global $basis, $geraetKennung, $geraetKey;
    $dev = $dev ?? $geraetKennung;
    $key = $key ?? $geraetKey;
    $ch = curl_init("$basis/ingest.php");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json',
                               'X-Device-Id: ' . $dev,
                               'X-Api-Key: ' . $key],
        CURLOPT_POSTFIELDS => json_encode($koerper),
        CURLOPT_TIMEOUT => 60,
    ]);
    $roh = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $d = json_decode((string)$roh, true);
    return ['code' => $code, 'daten' => is_array($d) ? $d : ['roh' => $roh]];
}

/* ---- DIE ZEITSTEMPEL LIEGEN IM ERSETZFENSTER (Backlog Nr. 134) ------------
 *
 * Bis Web 15.5.2 standen hier feste Maerz-Daten. Seit dem Ersetzfenster ist
 * das kein Detail mehr: Ein zweites Paket an einen Datensatz, dessen
 * `started_at` aelter als INGEST_ERSETZFENSTER_H ist, wird nicht mehr
 * uebernommen -- und die halbe Probe pruefte genau solche zweiten Pakete.
 * Zehn Erwartungen kippten, keine davon zu Recht: Gemessen wurde ein Fall, den
 * es im Betrieb nicht gibt, denn eine Uhr laedt hoch, waehrend der Dienst
 * laeuft.
 *
 * Die Zeitpunkte haengen deshalb an `time()`. Vier Bloecke, zwei Kalendertage
 * -- die Bloecke unterscheiden sich ueber `client_ref`, nicht ueber den Tag.
 * Das Fenster selbst prueft Teil "Ersetzfenster" weiter unten mit einem
 * eigenen, absichtlich alten Datensatz. */
$zeitpunkt = static function (int $tageZurueck, string $uhrzeit): string {
    return gmdate('Y-m-d', time() - $tageZurueck * 86400) . 'T' . $uhrzeit . ':00Z';
};
$tagVon = static fn(string $ts): string => substr($ts, 0, 10);

/* Vier Zeitachsen, alle innerhalb des Fensters (hoechstens 66 h alt). */
$tsA = $zeitpunkt(2, '06:00');   $tsAe = $zeitpunkt(2, '08:00');
$tsB = $zeitpunkt(2, '14:00');   $tsBe = $zeitpunkt(2, '15:00');
$tsC = $zeitpunkt(1, '06:00');   $tsCe = $zeitpunkt(1, '08:00');
$tsCk = $zeitpunkt(1, '09:30');
$tsD = $zeitpunkt(1, '12:00');   $tsDe = $zeitpunkt(1, '13:00');
$tsE = $zeitpunkt(1, '16:00');   $tsEe = $zeitpunkt(1, '17:00');

/** Ein Paket mit n Punkten ab seq_from. */
function paket(string $ref, int $seqFrom, int $n, bool $final = false,
               int $tsBasis = 1750000000): array {
    global $tsA, $tsAe, $tagVon;
    $punkte = [];
    for ($i = 0; $i < $n; $i++) {
        $s = $seqFrom + $i;
        $punkte[] = [47.0 + $s * 0.0002, 11.0 + $s * 0.0002, 700.0 + $s * 0.5,
                     $tsBasis + $s * 10];
    }
    return ['kind' => 'mission', 'client_ref' => $ref, 'day' => $tagVon($tsA),
            'started_at' => $tsA,
            'ended_at' => $final ? $tsAe : null,
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
$h = ['kind' => 'mission', 'client_ref' => 'probe-2', 'day' => $tagVon($tsB),
      'started_at' => $tsB, 'ended_at' => $tsBe,
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

/* ---- Teil 7 — Ein spaetes Paket darf nichts loeschen (Web 13.0.1) -------- */

echo "\n  Teil 7 — Was einmal dastand, bleibt stehen (B5.3)\n";

/* WARUM DIESER TEIL SPAETER KAM ALS DIE PROBE. Teil 3 schickt seit S2 eine
 * "Wiederholung unterhalb n_original" — also genau so ein spaetes,
 * nicht-finales Paket — und hat nur nie nachgesehen, was es an den
 * Metadaten anrichtet. Es richtete etwas an: `ended_at`, `distance_m` und
 * `ascent_m` gingen auf NULL, waehrend `final` wegen GREATEST auf 1 blieb.
 * Uebrig blieb ein abgeschlossener Einsatz ohne Ende. Seit 13.0.1 steht dort
 * COALESCE; dieser Teil haelt es fest. */

function meta(PDO $pdo, string $tabelle, string $ref): array {
    $spalten = $tabelle === 'missions' ? 'ended_at, distance_m, ascent_m, final' : 'ended_at, final';
    $q = $pdo->prepare("SELECT $spalten FROM `$tabelle` WHERE client_ref = ?");
    $q->execute([$ref]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: [];
}

$voll = ['kind' => 'mission', 'client_ref' => 'probe-spaet', 'day' => $tagVon($tsC),
         'started_at' => $tsC, 'ended_at' => $tsCe,
         'final' => true, 'distance_m' => 12345, 'ascent_m' => 678,
         'track' => ['seq_from' => 50, 'points' => []]];
$laufend = ['kind' => 'mission', 'client_ref' => 'probe-spaet', 'day' => $tagVon($tsC),
          'started_at' => $tsC, 'ended_at' => null, 'final' => false,
          'track' => ['seq_from' => 0, 'points' => []]];
senden($laufend);
senden($voll);
$mMeta = meta($pdo, 'missions', 'probe-spaet');
$alsDb = static fn(string $ts): string => str_replace(['T', 'Z'], [' ', ''], $ts);
pruefe(($mMeta['ended_at'] ?? null) === $alsDb($tsCe) && (int)($mMeta['distance_m'] ?? 0) === 12345
       && (int)($mMeta['ascent_m'] ?? 0) === 678 && (int)($mMeta['final'] ?? 0) === 1,
       'Nach dem finalen Paket stehen Ende, Strecke, Anstieg', json_encode($mMeta));

senden($laufend);   // das spaete, nicht-finale Paket — es traegt drei NULL-Werte
$mMeta = meta($pdo, 'missions', 'probe-spaet');
pruefe(($mMeta['ended_at'] ?? null) === $alsDb($tsCe),
       'Ein spaeteres nicht-finales Paket loescht das ENDE nicht',
       'ended_at ' . var_export($mMeta['ended_at'] ?? null, true)
       . ' — sonst bliebe ein abgeschlossener Einsatz ohne Ende zurueck');
pruefe((int)($mMeta['distance_m'] ?? 0) === 12345 && (int)($mMeta['ascent_m'] ?? 0) === 678,
       '... und auch STRECKE und ANSTIEG nicht',
       'distance_m ' . var_export($mMeta['distance_m'] ?? null, true)
       . ', ascent_m ' . var_export($mMeta['ascent_m'] ?? null, true));
pruefe((int)($mMeta['final'] ?? 0) === 1, '... und final bleibt 1 (GREATEST, unveraendert)');

// GEGENPROBE: Eine BERICHTIGUNG muss weiterhin durchgehen — COALESCE haelt
// nur NULL zurueck, nicht einen anderen Wert.
$korrektur = $voll;
$korrektur['ended_at']   = $tsCk;
$korrektur['distance_m'] = 999;
senden($korrektur);
$mMeta = meta($pdo, 'missions', 'probe-spaet');
pruefe(($mMeta['ended_at'] ?? null) === $alsDb($tsCk) && (int)($mMeta['distance_m'] ?? 0) === 999,
       'GEGENPROBE: eine Berichtigung mit anderen Werten gilt weiterhin',
       json_encode($mMeta) . ' — sonst waere aus dem Schutz eine Sperre geworden');

$rVoll = ['kind' => 'rest_segment', 'client_ref' => 'probe-spaet-r', 'day' => $tagVon($tsD),
          'started_at' => $tsD, 'ended_at' => $tsDe,
          'final' => true, 'track' => ['seq_from' => 0, 'points' => []]];
$rOffen = $rVoll;
$rOffen['ended_at'] = null;
$rOffen['final'] = false;
senden($rVoll);
senden($rOffen);
$rMeta = meta($pdo, 'rest_segments', 'probe-spaet-r');
pruefe(($rMeta['ended_at'] ?? null) === $alsDb($tsDe) && (int)($rMeta['final'] ?? 0) === 1,
       'Dasselbe am Ruhe-Segment: Ende bleibt, final bleibt', json_encode($rMeta));

/* ---- Teil 8 — Herkunft und Momentaufnahme (R64, Web 14.0.0) -------------- */

echo "\n  Teil 8 — Woher der Einsatz kommt und mit welchem Geraet (R64)\n";

/* WARUM DIESER TEIL UEBER HTTP LAEUFT UND NICHT ALS FUNKTIONSPRUEFUNG.
 * `herkunft_ableiten()` liesse sich mit sechs Zeilen aufrufen; was hier
 * geprueft wird, ist etwas anderes: dass die Angabe den ganzen Weg geht — von
 * der `devices`-Zeile ueber die Abfrage in `ingest.php` in den INSERT und in
 * die richtige Spalte. Genau dieser Weg war es, der bis Web 13.3.0 fehlte,
 * und zwar ohne dass irgendeine Antwort anders ausgesehen haette. */

/** Die drei neuen Spalten eines Einsatzes oder Segments. */
function herkunft(PDO $pdo, string $tabelle, string $ref): array {
    $spalten = $tabelle === 'missions'
        ? 'origin, geraet_art, geraet_modell' : 'geraet_art, geraet_modell';
    $q = $pdo->prepare("SELECT $spalten FROM `$tabelle` WHERE client_ref = ?");
    $q->execute([$ref]);
    return $q->fetch(PDO::FETCH_ASSOC) ?: [];
}

/** Ein leeres Segment-Paket (die Punkte spielen hier keine Rolle). */
function segment(string $ref): array {
    global $tsE, $tsEe, $tagVon;
    return ['kind' => 'rest_segment', 'client_ref' => $ref, 'day' => $tagVon($tsE),
            'started_at' => $tsE, 'ended_at' => $tsEe,
            'final' => true, 'track' => ['seq_from' => 0, 'points' => []]];
}

$fenix  = 'fēnix 7 / fēnix 7 Solar / fēnix 7 Sapphire Solar';
$pixel  = 'Google Pixel 8';

// 1. Die Garmin-Uhr: Praefix `m-`, Geraeteart `uhr` — beide sagen dasselbe.
senden(paket('m-r64-1', 0, 3, true));
$h = herkunft($pdo, 'missions', 'm-r64-1');
pruefe(($h['origin'] ?? '') === 'watch'
       && ($h['geraet_art'] ?? '') === 'uhr' && ($h['geraet_modell'] ?? '') === $fenix,
       'Uhr-Einsatz (m-): origin=watch, Art und Modell der Uhr', json_encode($h));

// 2. Das Android-Handy: Praefix `am-`, GEGEN die Geraeteart des Uhr-Geraets
//    waere es 'watch' — genau der Fehler, den R64 behebt.
senden(paket('am-r64-1', 0, 3, true), $handyKennung, $handyKey);
$h = herkunft($pdo, 'missions', 'am-r64-1');
pruefe(($h['origin'] ?? '') === 'android'
       && ($h['geraet_art'] ?? '') === 'handy' && ($h['geraet_modell'] ?? '') === $pixel,
       'Handy-Einsatz (am-): origin=android, Art und Modell des Handys', json_encode($h));

// 3. An der Wear-OS-Uhr begonnen, vom HANDY gesendet (E-S4-11). Dasselbe
//    Geraet wie oben — nur das Praefix unterscheidet die beiden Faelle.
senden(paket('wm-r64-1', 0, 3, true), $handyKennung, $handyKey);
$h = herkunft($pdo, 'missions', 'wm-r64-1');
pruefe(($h['origin'] ?? '') === 'wear'
       && ($h['geraet_art'] ?? '') === 'handy' && ($h['geraet_modell'] ?? '') === $pixel,
       'Wear-Einsatz (wm-): origin=wear, Geraet bleibt das HANDY', json_encode($h));

// 4. Der Rueckfall: unbekanntes Praefix — jetzt entscheidet die Geraeteart.
senden(paket('probe-r64-unbekannt', 0, 3, true), $handyKennung, $handyKey);
$h = herkunft($pdo, 'missions', 'probe-r64-unbekannt');
pruefe(($h['origin'] ?? '') === 'android',
       'Unbekanntes Praefix am Handy: origin=android (Rueckfall auf die Geraeteart)',
       json_encode($h));

senden(paket('probe-r64-unbekannt2', 0, 3, true));
$h = herkunft($pdo, 'missions', 'probe-r64-unbekannt2');
pruefe(($h['origin'] ?? '') === 'watch',
       'GEGENPROBE: dasselbe an der Uhr ergibt origin=watch', json_encode($h));

// 5. Die Segmente tragen die Momentaufnahme, aber keine Herkunft (E-R64-04).
senden(segment('r-r64-1'));
$h = herkunft($pdo, 'rest_segments', 'r-r64-1');
pruefe(($h['geraet_art'] ?? '') === 'uhr' && ($h['geraet_modell'] ?? '') === $fenix,
       'Ruhe-Segment der Uhr traegt Art und Modell', json_encode($h));

senden(segment('ar-r64-1'), $handyKennung, $handyKey);
$h = herkunft($pdo, 'rest_segments', 'ar-r64-1');
pruefe(($h['geraet_art'] ?? '') === 'handy' && ($h['geraet_modell'] ?? '') === $pixel,
       'Ruhe-Segment des Handys traegt Art und Modell', json_encode($h));

/* 6. DIE ZUSICHERUNG, AN DER DER GANZE PUNKT HAENGT: Die Momentaufnahme wird
 *    NIE nachgezogen (E-R64-05). Hier wird das Geraet zwischen zwei Paketen
 *    umgeschrieben — so, wie eine spaetere Nachaufloesung es taete
 *    (tools/geraetemodelle/nachaufloesen.php). Der Einsatz muss weiter das
 *    tragen, was beim Anlegen galt; ein neuer Einsatz das Neue. Ginge die
 *    Momentaufnahme mit, waere sie keine — dann koennte man auch gleich ueber
 *    `device_id` lesen, und genau das trug nicht (R47). */
$pdo->prepare('UPDATE devices SET geraet_modell = ? WHERE device_id = ?')
    ->execute(['Nachtraeglich anders aufgeloest', $geraetKennung]);
senden(paket('m-r64-1', 3, 3, true));
$h = herkunft($pdo, 'missions', 'm-r64-1');
pruefe(($h['geraet_modell'] ?? '') === $fenix,
       'Ein zweites Paket zieht die Momentaufnahme NICHT nach', json_encode($h));

senden(paket('m-r64-2', 0, 3, true));
$h = herkunft($pdo, 'missions', 'm-r64-2');
pruefe(($h['geraet_modell'] ?? '') === 'Nachtraeglich anders aufgeloest',
       'GEGENPROBE: ein NEUER Einsatz bekommt den neuen Wert', json_encode($h));


/* ---- Teil 9 — Das Ersetzfenster (Backlog Nr. 134, K-14, F-SP-8) ----------
 *
 * WOGEGEN. Der Geraeteschluessel liegt auf der Uhr im Klartext. Wer sie
 * findet, kann Einsaetze hochladen und die Phasen BESTEHENDER Einsaetze
 * ersetzen, bis das Geraet im Web getrennt ist. Dagegen steht das Fenster:
 * INGEST_ERSETZFENSTER_H Stunden ab dem GESPEICHERTEN `started_at`.
 *
 * ZWEI PAKETE AN DEMSELBEN DATENSATZ, einmal innerhalb und einmal ausserhalb.
 * Das Alter entsteht dabei nicht durch Warten, sondern indem `started_at`
 * nach dem ersten Paket per SQL zurueckdatiert wird -- der Wert, den der
 * Server liest, ist derselbe, den ein drei Wochen alter Einsatz haette.
 * (Ausnahmsweise per SQL: Ein Uhr-Paket kann `started_at` bei einem
 * bestehenden Datensatz gar nicht mehr veraendern -- das ist der Punkt.) */

echo "\n  Teil 9 — Das Ersetzfenster: 72 h ab Einsatzbeginn (Nr. 134)\n";

$fRef = 'probe-fenster';
$fPaket = static function (array $phasen, int $seqFrom, int $punkte) use ($tsC, $tsCe, $tagVon, $fRef): array {
    $pts = [];
    for ($i = 0; $i < $punkte; $i++) {
        $pts[] = [47.5 + ($seqFrom + $i) * 0.0001, 11.5, 700.0, 1750000000 + ($seqFrom + $i) * 10];
    }
    return ['kind' => 'mission', 'client_ref' => $fRef, 'day' => $tagVon($tsC),
            'started_at' => $tsC, 'ended_at' => $tsCe, 'final' => true,
            'phases' => $phasen,
            'track' => ['seq_from' => $seqFrom, 'points' => $pts]];
};
/* Phasen sind NUMMERN von PHASE_MIN bis PHASE_MAX (2..9), keine Namen. */
$phasenA = [['phase' => 2, 'at' => $tsC, 'lat' => 47.5, 'lon' => 11.5],
            ['phase' => 3, 'at' => $tsCe, 'lat' => 47.6, 'lon' => 11.6]];
$phasenB = [['phase' => 2, 'at' => $tsC, 'lat' => 40.0, 'lon' => 5.0],
            ['phase' => 3, 'at' => $tsCe, 'lat' => 40.1, 'lon' => 5.1]];

$zaehlPhasen = static function (PDO $pdo, string $ref): array {
    $q = $pdo->prepare('SELECT p.lat FROM mission_phases p
                        JOIN missions m ON m.id = p.mission_id
                        WHERE m.client_ref = ? AND p.phase = 2 LIMIT 1');
    $q->execute([$ref]);
    $lat = $q->fetchColumn();
    $z = $pdo->prepare('SELECT COUNT(*) FROM mission_phases p
                        JOIN missions m ON m.id = p.mission_id WHERE m.client_ref = ?');
    $z->execute([$ref]);
    return ['n' => (int)$z->fetchColumn(), 'lat' => $lat === false ? null : (float)$lat];
};

// INNERHALB: der Datensatz entsteht und wird danach veraendert.
$f1 = senden($fPaket($phasenA, 0, 5));
pruefe(($f1['daten']['ok'] ?? false) === true, 'Innerhalb: der Einsatz entsteht',
       'HTTP ' . $f1['code'] . ', stored ' . ($f1['daten']['stored_points'] ?? '?'));
$fId = (int)($f1['daten']['id'] ?? 0);

$f2 = senden($fPaket($phasenB, 5, 5));
$nach = $zaehlPhasen($pdo, $fRef);
pruefe(($f2['daten']['stored_points'] ?? -1) === 5
       && !isset($f2['daten']['kept_phases']) && !isset($f2['daten']['kept_points'])
       && $nach['lat'] !== null && abs($nach['lat'] - 40.0) < 0.001,
       'Innerhalb: ein zweites Paket ersetzt die Phasen und haengt Punkte an',
       'stored ' . ($f2['daten']['stored_points'] ?? '?') . ', Phasen-lat '
       . ($nach['lat'] ?? 'null') . ' (erwartet 40.0), keine kept_*-Felder');

// AUSSERHALB: `started_at` UND `created_at` zurueckdatieren, dann dasselbe
// noch einmal. Beide, weil der Anker seit der Nachbesserung vom 07.09.2026 das
// Spaetere aus beiden ist -- ein Einsatz, den der Server vor 73 h zum ersten
// Mal gesehen hat, traegt genau diese beiden Werte.
$alt = gmdate('Y-m-d H:i:s', time() - (INGEST_ERSETZFENSTER_H + 1) * 3600);
$pdo->prepare('UPDATE missions SET started_at = ?, created_at = ? WHERE id = ?')->execute([$alt, $alt, $fId]);
$phasenC = [['phase' => 2, 'at' => $tsC, 'lat' => 10.0, 'lon' => 5.0],
            ['phase' => 3, 'at' => $tsCe, 'lat' => 10.1, 'lon' => 5.1]];
$vorher = zeilen($pdo, $fId);
$f3 = senden($fPaket($phasenC, 10, 5));
$nach3 = $zaehlPhasen($pdo, $fRef);
$nachher = zeilen($pdo, $fId);

pruefe(($f3['code'] === 200) && (($f3['daten']['ok'] ?? false) === true),
       'Ausserhalb: die Antwort ist ok und kein Fehler',
       'HTTP ' . $f3['code'] . ' — ein Fehler liesse die Uhr endlos wiederholen');
pruefe(($f3['daten']['kept_phases'] ?? -1) === 2,
       'Ausserhalb: die vorhandenen Phasen bleiben und werden GENANNT',
       'kept_phases ' . ($f3['daten']['kept_phases'] ?? 'fehlt') . ' (erwartet 2)');
pruefe($nach3['lat'] !== null && abs($nach3['lat'] - 40.0) < 0.001,
       'Ausserhalb: die Phasen sind unveraendert die alten',
       'Phasen-lat ' . ($nach3['lat'] ?? 'null') . ' (erwartet 40.0, gesendet 10.0)');
pruefe(($f3['daten']['kept_points'] ?? -1) === 5 && $nachher === $vorher,
       'Ausserhalb: es wird kein Punkt angehaengt, und auch das wird GENANNT',
       'kept_points ' . ($f3['daten']['kept_points'] ?? 'fehlt') . ', Zeilen '
       . $vorher . ' vorher / ' . $nachher . ' nachher');
pruefe(($f3['daten']['next_seq'] ?? 0) >= 15,
       'Ausserhalb: next_seq wandert trotzdem weiter',
       'next_seq ' . ($f3['daten']['next_seq'] ?? '?') . ' — sonst sendet die Uhr ewig');

// NEUANLAGE bleibt immer moeglich: ein anderer client_ref, derselbe Tag.
$neuRef = 'probe-fenster-neu';
$fNeu = ['kind' => 'mission', 'client_ref' => $neuRef, 'day' => $tagVon($tsC),
         'started_at' => $tsC, 'ended_at' => $tsCe, 'final' => true,
         'phases' => $phasenA,
         'track' => ['seq_from' => 0, 'points' => [[47.9, 11.9, 700.0, 1750000000]]]];
$f4 = senden($fNeu);
pruefe(($f4['daten']['stored_points'] ?? -1) === 1 && (int)($f4['daten']['id'] ?? 0) > 0,
       'Ein NEUER Einsatz wird immer angenommen — er ueberschreibt nichts',
       'stored ' . ($f4['daten']['stored_points'] ?? '?') . ', id ' . ($f4['daten']['id'] ?? '?'));

/* ---- Die Nachbesserung vom 07.09.2026 (Gegenpruefung des Web-Teils) ------
 *
 * Sechs Funde am Fenster, jeder mit einer Erwartung, die VOR der Behebung
 * rot war: Der Zeitraum des Diensttags wurde auch ausserhalb umgeschrieben
 * (1); die falsch gestellte Geraeteuhr schloss das Fenster im Augenblick des
 * Anlegens (2); ein Abschlusspaket ausserhalb wurde still uebergangen (3);
 * ein Diensttag im Papierkorb bekam einen leeren Nachfolger (4); ein
 * `started_at` in der Zukunft schloss das Fenster nie (5); und das
 * Ruhesegment nannte nur die Punkte, nie das Ende (6).
 *
 * Dazu einer aus der ZWEITEN Gegenpruefung, auf die Nachbesserung selbst (7):
 * Der Anker war das Spaetere aus `started_at` und `created_at`, Zukunft
 * ausgenommen -- aber "Zukunft" wurde je Paket gegen jetzt gerechnet. Ein
 * `started_at`, das beim Anlegen vorn lag, wurde zum Anker, sobald die Zeit
 * es eingeholt hatte, und das laengst geschlossene Fenster ging noch einmal
 * auf. Der Anker ist jetzt `created_at` allein. */

// (1) Der Zeitraum des Diensttags bleibt, wenn das Fenster zu ist.
$liesTag = static function (PDO $pdo, int $missionId): string {
    $q = $pdo->prepare('SELECT d.started_at, d.ended_at FROM days d
                        JOIN missions m ON m.day_id = d.id WHERE m.id = ?');
    $q->execute([$missionId]);
    return json_encode($q->fetch(PDO::FETCH_ASSOC));
};
$tagVorher = $liesTag($pdo, $fId);
$fZeit = $fPaket($phasenC, 15, 1);
$fZeit['started_at'] = '2001-01-01T00:00:00Z';
$fZeit['ended_at']   = '2097-12-31T23:00:00Z';
$f5 = senden($fZeit);
$tagNachher = $liesTag($pdo, $fId);
pruefe(($f5['daten']['ok'] ?? false) === true && $tagVorher === $tagNachher,
       'Ausserhalb: der ZEITRAUM DES DIENSTTAGS bleibt (Gegenpruefung 1)',
       'Tag vorher ' . $tagVorher . ', nachher ' . $tagNachher);

// (3) Ein Abschlusspaket ausserhalb: uebergangen UND GENANNT (kept_meta).
$oRef = 'probe-fenster-offen';
$o1 = senden(['kind' => 'mission', 'client_ref' => $oRef, 'day' => $tagVon($tsC),
              'started_at' => $tsC, 'ended_at' => null, 'final' => false,
              'phases' => $phasenA,
              'track' => ['seq_from' => 0, 'points' => [[47.5, 11.5, 700.0, 1750000000]]]]);
$oId = (int)($o1['daten']['id'] ?? 0);
$pdo->prepare('UPDATE missions SET started_at = ?, created_at = ? WHERE id = ?')->execute([$alt, $alt, $oId]);
$o2 = senden(['kind' => 'mission', 'client_ref' => $oRef, 'day' => $tagVon($tsC),
              'started_at' => $tsC, 'ended_at' => $tsCe, 'final' => true,
              'distance_m' => 42000, 'ascent_m' => 310,
              'track' => ['seq_from' => 1, 'points' => []]]);
$oq = $pdo->prepare('SELECT ended_at, final, distance_m FROM missions WHERE id = ?');
$oq->execute([$oId]);
$oZeile = $oq->fetch(PDO::FETCH_ASSOC) ?: [];
pruefe(($o2['daten']['ok'] ?? false) === true && ($o2['daten']['kept_meta'] ?? 0) === 1
       && array_key_exists('ended_at', $oZeile) && $oZeile['ended_at'] === null && (int)($oZeile['final'] ?? 1) === 0,
       'Ausserhalb: ein ABSCHLUSSPAKET wird uebergangen und als kept_meta GENANNT (Gegenpruefung 3)',
       'kept_meta ' . ($o2['daten']['kept_meta'] ?? 'fehlt') . ', ended_at '
       . ($oZeile['ended_at'] ?? 'null') . ', final ' . ($oZeile['final'] ?? '?'));

// (2) Die falsch gestellte Uhr: `started_at` von vor Jahren, der Einsatz laeuft.
$uRef = 'probe-fenster-uhr';
$uPhasen = [['phase' => 2, 'at' => '2020-01-01T08:00:00Z', 'lat' => 47.5, 'lon' => 11.5]];
$u1 = senden(['kind' => 'mission', 'client_ref' => $uRef, 'day' => '2020-01-01',
              'started_at' => '2020-01-01T08:00:00Z', 'ended_at' => null, 'final' => false,
              'phases' => $uPhasen,
              'track' => ['seq_from' => 0, 'points' => [[47.5, 11.5, 700.0, 1577865600]]]]);
$u2 = senden(['kind' => 'mission', 'client_ref' => $uRef, 'day' => '2020-01-01',
              'started_at' => '2020-01-01T08:00:00Z', 'ended_at' => null, 'final' => false,
              'phases' => array_merge($uPhasen, [['phase' => 3, 'at' => '2020-01-01T08:10:00Z', 'lat' => 47.6, 'lon' => 11.6]]),
              'track' => ['seq_from' => 1, 'points' => [[47.5001, 11.5, 700.0, 1577865610],
                                                        [47.5002, 11.5, 700.0, 1577865620]]]]);
pruefe(($u1['daten']['stored_points'] ?? -1) === 1 && ($u2['daten']['stored_points'] ?? -1) === 2
       && !isset($u2['daten']['kept_points']) && !isset($u2['daten']['kept_phases']),
       'Falsch gestellte Uhr (started_at 2020): der LAUFENDE Einsatz nimmt weiter an (Gegenpruefung 2)',
       'stored ' . ($u2['daten']['stored_points'] ?? '?') . ', kept_points '
       . ($u2['daten']['kept_points'] ?? 'keins') . ' — der Anker ist created_at, nicht die Geraeteuhr');

// (5) `started_at` in der Zukunft: das Fenster schliesst trotzdem, 72 h nach dem Anlegen.
$zRef = 'probe-fenster-zukunft';
$zukunft = gmdate('Y-m-d\TH:i:s\Z', time() + 10 * 365 * 86400);
$z1 = senden(['kind' => 'mission', 'client_ref' => $zRef, 'day' => substr($zukunft, 0, 10),
              'started_at' => $zukunft, 'ended_at' => null, 'final' => false,
              'track' => ['seq_from' => 0, 'points' => [[47.5, 11.5, 700.0, 1750000000]]]]);
$zId = (int)($z1['daten']['id'] ?? 0);
$pdo->prepare('UPDATE missions SET created_at = ? WHERE id = ?')->execute([$alt, $zId]);
$z2 = senden(['kind' => 'mission', 'client_ref' => $zRef, 'day' => substr($zukunft, 0, 10),
              'started_at' => $zukunft, 'ended_at' => null, 'final' => false,
              'track' => ['seq_from' => 1, 'points' => [[47.5001, 11.5, 700.0, 1750000010]]]]);
pruefe($zId > 0 && ($z2['daten']['kept_points'] ?? -1) === 1,
       'started_at in der Zukunft: das Fenster schliesst 72 h nach dem Anlegen, nicht nie (Gegenpruefung 5)',
       'kept_points ' . ($z2['daten']['kept_points'] ?? 'fehlt') . ' (erwartet 1)');

// (7) Vorgehende Uhr: `started_at` lag beim Anlegen vorn, ist jetzt eingeholt --
//     der Anker bleibt `created_at`, das Fenster geht nicht noch einmal auf.
$vRef = 'probe-fenster-vorgehend';
$vStart = gmdate('Y-m-d\TH:i:s\Z', time() - 3600);        // vor einer Stunde: schon eingeholt
$v1 = senden(['kind' => 'mission', 'client_ref' => $vRef, 'day' => substr($vStart, 0, 10),
              'started_at' => $vStart, 'ended_at' => null, 'final' => false,
              'track' => ['seq_from' => 0, 'points' => [[47.5, 11.5, 700.0, 1750000000]]]]);
$vId = (int)($v1['daten']['id'] ?? 0);
// Das Anlegen lag 100 h zurueck -- damals war `started_at` 99 h Zukunft.
$pdo->prepare('UPDATE missions SET created_at = ? WHERE id = ?')
    ->execute([gmdate('Y-m-d H:i:s', time() - 100 * 3600), $vId]);
$v2 = senden(['kind' => 'mission', 'client_ref' => $vRef, 'day' => substr($vStart, 0, 10),
              'started_at' => $vStart, 'ended_at' => null, 'final' => false,
              'track' => ['seq_from' => 1, 'points' => [[47.5001, 11.5, 700.0, 1750000010]]]]);
pruefe($vId > 0 && ($v2['daten']['kept_points'] ?? -1) === 1,
       'Vorgehende Uhr: ein eingeholtes started_at oeffnet das Fenster nicht noch einmal (zweite Gegenpruefung)',
       'kept_points ' . ($v2['daten']['kept_points'] ?? 'fehlt') . ' (erwartet 1) — der Anker ist created_at allein');

// (4) Diensttag im Papierkorb: ausserhalb entsteht KEIN leerer neuer Tag.
$dq = $pdo->prepare('SELECT day_id FROM missions WHERE id = ?'); $dq->execute([$fId]);
$fTag = (int)$dq->fetchColumn();
$pdo->prepare('UPDATE days SET deleted_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$fTag]);
$zaehlTage = static function (PDO $pdo, int $uid): int {
    $q = $pdo->prepare('SELECT COUNT(*) FROM days WHERE user_id = ?'); $q->execute([$uid]);
    return (int)$q->fetchColumn();
};
$tageVor = $zaehlTage($pdo, $uid);
$fTagPaket = $fPaket($phasenC, 16, 1);
$fTagPaket['day'] = '2026-07-29';
$f6 = senden($fTagPaket);
$tageNach = $zaehlTage($pdo, $uid);
$pdo->prepare('UPDATE days SET deleted_at = NULL WHERE id = ?')->execute([$fTag]);
pruefe(($f6['daten']['ok'] ?? false) === true && $tageVor === $tageNach,
       'Ausserhalb, Tag im Papierkorb: es entsteht kein leerer Diensttag (Gegenpruefung 4)',
       'Diensttage vorher ' . $tageVor . ', nachher ' . $tageNach);

// (6) Ruhesegment ausserhalb: Punkte UND Ende uebergangen, beides genannt.
$sRef = 'probe-fenster-segment';
$s1 = senden(['kind' => 'rest_segment', 'client_ref' => $sRef, 'day' => $tagVon($tsD),
              'started_at' => $tsD, 'ended_at' => null, 'final' => false,
              'track' => ['seq_from' => 0, 'points' => [[47.5, 11.5, 700.0, 1750000000]]]]);
$sId = (int)($s1['daten']['id'] ?? 0);
$pdo->prepare('UPDATE rest_segments SET started_at = ?, created_at = ? WHERE id = ?')->execute([$alt, $alt, $sId]);
$s2 = senden(['kind' => 'rest_segment', 'client_ref' => $sRef, 'day' => $tagVon($tsD),
              'started_at' => $tsD, 'ended_at' => $tsDe, 'final' => true,
              'track' => ['seq_from' => 1, 'points' => [[47.5001, 11.5, 700.0, 1750000010]]]]);
$sq = $pdo->prepare('SELECT ended_at, final FROM rest_segments WHERE id = ?'); $sq->execute([$sId]);
$sZeile = $sq->fetch(PDO::FETCH_ASSOC) ?: [];
pruefe($sId > 0 && ($s2['daten']['kept_points'] ?? -1) === 1 && ($s2['daten']['kept_meta'] ?? 0) === 1
       && array_key_exists('ended_at', $sZeile) && $sZeile['ended_at'] === null,
       'Ruhesegment ausserhalb: Punkte UND Ende uebergangen, beides GENANNT (Gegenpruefung 6)',
       'kept_points ' . ($s2['daten']['kept_points'] ?? 'fehlt') . ', kept_meta '
       . ($s2['daten']['kept_meta'] ?? 'fehlt') . ', ended_at ' . ($sZeile['ended_at'] ?? 'null'));

printf("  Ergebnis des Fensters: innerhalb angenommen, ausserhalb abgewiesen und genannt (%d h ab dem Anlegen)\n",
       INGEST_ERSETZFENSTER_H);

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
