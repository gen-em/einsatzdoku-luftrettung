<?php
declare(strict_types=1);

/**
 * Jobprobe — tut der Job-Rahmen, was er zusagt? (S2/AP2)
 *
 * WOFUER. Der Rahmen aus `jobs_lib.php` hat drei Zusagen, und alle drei sind
 * unsichtbar, solange nichts schiefgeht:
 *
 *   1. **Drei Ausloeser, eine Arbeit.** Ob `cli`, `token` oder `anfrage` —
 *      derselbe Rueckstand muss verschwinden. Ein Weg, der still nichts tut,
 *      faellt sonst erst auf, wenn die Platte voll ist.
 *   2. **Die gemeldete Zahl stimmt.** „erledigt 7" muss heissen, dass sieben
 *      Dinge weg sind, nicht sechs und ein Blob. Eine Zahl, die niemand
 *      nachrechnet, ist Dekoration.
 *   3. **Der Huckepack-Weg ist ein Rueckfall.** Er darf nicht bei jeder
 *      Anfrage laufen und nicht laenger als sein Budget dauern. Genau das war
 *      der erste Anlauf falsch (bis zu 18 s je Anfrage).
 *
 * Dazu die Sperre: Zwei gleichzeitige Laeufe muessen sich ausschliessen, und
 * eine verwaiste Sperre muss verfallen.
 *
 * ES AENDERT ETWAS — anders als die Spurprobe. Der Waisenjob loescht; das ist
 * sein Zweck, und ein Job, der in einer zurueckgerollten Transaktion laeuft,
 * beweist nichts ueber sein Zusammenspiel mit der Sperre (die auf COMMIT
 * angewiesen ist). Deshalb legt die Probe ihre eigenen Waisen an — Punkte auf
 * Eigentuemerkennungen, die es garantiert nicht gibt — und raeumt am Ende
 * hinter sich auf. Bestehende Daten fasst sie nicht an.
 *
 * Aufruf:
 *   php tools/jobprobe/probe.php
 *
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 2) . '/server';
require_once $wurzel . '/config.php';
require_once $wurzel . '/db.php';
require_once $wurzel . '/jobs_lib.php';
require_once $wurzel . '/spur_lib.php';

$pdo = db();

$erwartungen = 0;
$offen = 0;
function pruefe(bool $ok, string $was, string $wert = ''): void {
    global $erwartungen, $offen;
    $erwartungen++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-62s %s\n", $ok ? 'ok ' : 'FEHL', $was, $wert);
}

/* Eigentuemerkennungen, die es sicher nicht gibt: oberhalb des groessten
 * vergebenen Werts, mit Abstand. Alles darunter koennte einem echten Einsatz
 * gehoeren, und den wuerde die Probe dann loeschen. */
$maxM = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM missions')->fetchColumn();
$maxR = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM rest_segments')->fetchColumn();
$basisM = $maxM + 100000;
$basisR = $maxR + 100000;

/** Punkte auf eine Kennung legen, die keinem Einsatz gehoert. */
function waise_zeilen(PDO $pdo, string $typ, int $id, int $n): void {
    $st = $pdo->prepare('INSERT INTO track_points
                         (owner_type, owner_id, seq, lat, lon, ele, ts)
                         VALUES (?,?,?,?,?,?,?)');
    for ($i = 0; $i < $n; $i++) {
        $st->execute([$typ, $id, $i, 47.5 + $i / 1e5, 11.5 + $i / 1e5, 700.0,
                      1750000000 + $i]);
    }
}

function waise_blob(PDO $pdo, string $typ, int $id, int $n): void {
    $punkte = [];
    for ($i = 0; $i < $n; $i++) {
        $punkte[] = [$i, 47.5 + $i / 1e5, 11.5 + $i / 1e5, 700.0, 1750000000 + $i];
    }
    spur_blob_schreiben($pdo, $typ, $id,
        spur_kodieren($punkte, SPUR_STUFE_ROH), SPUR_STUFE_ROH, $n, $n);
}

/** Alles wegraeumen, was diese Probe angelegt hat. */
function aufraeumen(PDO $pdo, int $basisM, int $basisR): void {
    $pdo->prepare('DELETE FROM track_points WHERE (owner_type = ? AND owner_id >= ?)
                                               OR (owner_type = ? AND owner_id >= ?)')
        ->execute(['mission', $basisM, 'rest', $basisR]);
    $pdo->prepare('DELETE FROM track_blobs WHERE (owner_type = ? AND owner_id >= ?)
                                              OR (owner_type = ? AND owner_id >= ?)')
        ->execute(['mission', $basisM, 'rest', $basisR]);
}

/** Zaehlt, was von den gepflanzten Waisen noch dasteht. */
function rest_waisen(PDO $pdo, int $basisM, int $basisR): array {
    $z = $pdo->prepare('SELECT COUNT(*) FROM track_points
                         WHERE (owner_type = ? AND owner_id >= ?)
                            OR (owner_type = ? AND owner_id >= ?)');
    $z->execute(['mission', $basisM, 'rest', $basisR]);
    $b = $pdo->prepare('SELECT COUNT(*) FROM track_blobs
                         WHERE (owner_type = ? AND owner_id >= ?)
                            OR (owner_type = ? AND owner_id >= ?)');
    $b->execute(['mission', $basisM, 'rest', $basisR]);
    return ['zeilen' => (int)$z->fetchColumn(), 'blobs' => (int)$b->fetchColumn()];
}

/** Faelligkeit zuruecksetzen — nicht den Fortschritt. */
function faellig_machen(PDO $pdo): void {
    $pdo->exec('UPDATE jobs SET letzter_lauf = NULL, laeuft_seit = NULL');
}

/* Die Marke des Waisenjobs zurueckdrehen, damit er die frisch gepflanzten
 * Kennungen ueberhaupt sieht: Sie liegen oberhalb aller echten, die Marke
 * steht aber moeglicherweise schon dahinter. */
function marke_zuruecksetzen(PDO $pdo): void {
    $pdo->prepare('UPDATE jobs SET zustand = ? WHERE job = ?')
        ->execute([json_encode(['mission' => 0, 'rest' => 0]), 'waisen']);
}

echo "Jobprobe — Rahmen aus jobs_lib.php\n";
echo "  Waisen werden auf Kennungen ab mission/$basisM und rest/$basisR gelegt.\n\n";

/* WAS HIER UNVERAENDERT BLEIBEN MUSS, ist nicht die Zeilenzahl der Tabelle.
 * Der Waisenjob raeumt waehrend der Probe auch ECHTE Waisen ab, die schon
 * vorher dastanden — das ist sein Zweck, kein Schaden. Der erste Anlauf dieser
 * Probe verglich trotzdem `COUNT(*)` und schlug deshalb an (3 313 253 ->
 * 3 313 246, sieben echte Waisen). Verglichen wird also, was einen
 * Eigentuemer HAT: Daran darf der Job nicht ruehren. */
function punkte_mit_eigentuemer(PDO $pdo): int {
    $q = $pdo->query("SELECT
        (SELECT COUNT(*) FROM track_points tp JOIN missions m ON m.id = tp.owner_id
          WHERE tp.owner_type = 'mission')
      + (SELECT COUNT(*) FROM track_points tp JOIN rest_segments r ON r.id = tp.owner_id
          WHERE tp.owner_type = 'rest')");
    return (int)$q->fetchColumn();
}

$gesamtVorher = (int)$pdo->query('SELECT COUNT(*) FROM track_points')->fetchColumn();
$echteVorher  = punkte_mit_eigentuemer($pdo);

aufraeumen($pdo, $basisM, $basisR);

try {

/* ---- Teil 1 — Der Katalog steht ------------------------------------------ */

echo "  Teil 1 — Katalog und Tabelle\n";
$katalog = jobs_katalog();
pruefe(isset($katalog['aufraeumen'], $katalog['waisen']),
       'Beide Jobs stehen im Katalog', implode(', ', array_keys($katalog)));
pruefe(!empty($katalog['aufraeumen']['taeglich'])
       && empty($katalog['waisen']['taeglich']),
       'aufraeumen ist taeglich, waisen nicht',
       'taeglich: aufraeumen=ja, waisen=nein');
$hatTabelle = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables
                                WHERE table_schema = DATABASE() AND table_name = 'jobs'")
                        ->fetchColumn();
pruefe($hatTabelle === 1, 'Die Tabelle jobs gibt es (Migration gelaufen)');

/* ---- Teil 2 — Jeder der drei Ausloeser traegt denselben Rueckstand ab ----- */

echo "\n  Teil 2 — Drei Ausloeser, dieselbe Arbeit (E-S2-17)\n";
foreach (['cli', 'token', 'anfrage'] as $ausloeser) {
    aufraeumen($pdo, $basisM, $basisR);
    waise_zeilen($pdo, 'mission', $basisM + 1, 5);
    waise_zeilen($pdo, 'rest',    $basisR + 1, 5);
    waise_blob($pdo, 'mission', $basisM + 2, 4);
    $vorher = rest_waisen($pdo, $basisM, $basisR);

    faellig_machen($pdo);
    marke_zuruecksetzen($pdo);
    $bericht = jobs_lauf($ausloeser, ['waisen']);
    $nachher = rest_waisen($pdo, $basisM, $basisR);

    $weg = $nachher['zeilen'] === 0 && $nachher['blobs'] === 0;
    pruefe($weg, "Ausloeser '$ausloeser' raeumt den Rueckstand ab",
           sprintf('%d Zeilen + %d Blobs -> %d + %d',
                   $vorher['zeilen'], $vorher['blobs'],
                   $nachher['zeilen'], $nachher['blobs']));
    pruefe(empty($bericht['waisen']['fehler']),
           "Ausloeser '$ausloeser' meldet keinen Fehler",
           (string)($bericht['waisen']['fehler'] ?? '—'));
}

/* ---- Teil 3 — Die gemeldete Zahl stimmt ---------------------------------- */

echo "\n  Teil 3 — Die gemeldete Zahl ist nachgerechnet\n";
aufraeumen($pdo, $basisM, $basisR);
waise_zeilen($pdo, 'mission', $basisM + 3, 6);   // 6 Zeilen
waise_blob($pdo, 'rest', $basisR + 3, 12);       // 1 Blob (12 Punkte darin)
faellig_machen($pdo);
marke_zuruecksetzen($pdo);
$bericht = jobs_lauf('cli', ['waisen']);
$erledigt = (int)($bericht['waisen']['erledigt'] ?? -1);
pruefe($erledigt === 7,
       'Sechs Zeilen und ein Blob werden als 7 gemeldet',
       "erledigt=$erledigt (erwartet 7 — Zeilen zaehlen einzeln, ein Blob als eins)");
$nachher = rest_waisen($pdo, $basisM, $basisR);
pruefe($nachher['zeilen'] === 0 && $nachher['blobs'] === 0,
       'und danach steht nichts davon mehr da',
       "{$nachher['zeilen']} Zeilen, {$nachher['blobs']} Blobs");

/* ---- Teil 4 — Der Rueckstand luegt nicht --------------------------------- */

echo "\n  Teil 4 — Der gemeldete Rueckstand nach vollstaendigem Durchlauf\n";
$z = $pdo->prepare('SELECT rueckstand, zustand FROM jobs WHERE job = ?');
$z->execute(['waisen']);
$zeile = $z->fetch(PDO::FETCH_ASSOC);
$rueck = $zeile['rueckstand'] === null ? null : (int)$zeile['rueckstand'];
pruefe($rueck === 0,
       'Nach einem vollstaendigen Durchlauf ist der Rueckstand 0',
       'rueckstand=' . var_export($rueck, true)
       . ' (der erste Anlauf meldete hier die ganze Tabelle)');
$zustand = json_decode((string)$zeile['zustand'], true) ?: [];
pruefe(!empty($zustand['durch']['mission']) && !empty($zustand['durch']['rest']),
       'Der Zustand haelt fest, DASS der Durchlauf zu Ende kam',
       'durch=' . json_encode($zustand['durch'] ?? null));

/* ---- Teil 5 — Die Sperre ------------------------------------------------- */

echo "\n  Teil 5 — Sperre gegen zwei gleichzeitige Laeufe\n";
$pdo->prepare('UPDATE jobs SET laeuft_seit = UTC_TIMESTAMP(), letzter_lauf = NULL
                WHERE job = ?')->execute(['waisen']);
$bericht = jobs_lauf('cli', ['waisen']);
pruefe(isset($bericht['waisen']['uebersprungen']),
       'Ein laufender Job wird nicht ein zweites Mal gestartet',
       (string)($bericht['waisen']['uebersprungen'] ?? 'NICHT uebersprungen'));

// Verwaiste Sperre: aelter als JOB_SPERRE_VERFALL_S.
$pdo->prepare('UPDATE jobs
                  SET laeuft_seit = DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? SECOND),
                      letzter_lauf = NULL
                WHERE job = ?')
    ->execute([JOB_SPERRE_VERFALL_S + 60, 'waisen']);
$bericht = jobs_lauf('cli', ['waisen']);
pruefe(!isset($bericht['waisen']['uebersprungen']),
       'Eine verwaiste Sperre verfaellt und blockiert nicht ewig',
       'Verfall nach ' . JOB_SPERRE_VERFALL_S . ' s');

/* ---- Teil 6 — Der Huckepack-Weg ist ein Rueckfall ------------------------ */

echo "\n  Teil 6 — Huckepack traegt wenig und selten (JOB_ANFRAGE_PAUSE_S)\n";
aufraeumen($pdo, $basisM, $basisR);
waise_zeilen($pdo, 'mission', $basisM + 4, 3);
faellig_machen($pdo);
marke_zuruecksetzen($pdo);

$t0 = microtime(true);
jobs_lauf('anfrage', ['waisen']);
$ersteDauer = microtime(true) - $t0;
pruefe($ersteDauer <= JOB_BUDGET_ANFRAGE + 1.0,
       'Ein Haeppchen am Huckepack-Weg bleibt im Budget',
       sprintf('%.3f s (Budget %.0f s)', $ersteDauer, JOB_BUDGET_ANFRAGE));

// Direkt danach noch einmal — der Mindestabstand muss greifen.
$t0 = microtime(true);
$bericht = jobs_lauf('anfrage', ['waisen']);
$zweiteDauer = microtime(true) - $t0;
pruefe(isset($bericht['waisen']['uebersprungen']),
       'Die naechste Anfrage laeuft NICHT gleich wieder',
       (string)($bericht['waisen']['uebersprungen'] ?? 'lief erneut — Fehler'));
pruefe($zweiteDauer < 0.5,
       'und kostet dann fast nichts',
       sprintf('%.3f s', $zweiteDauer));

// Derselbe Zustand ueber cli — dort gilt der Abstand NICHT.
$bericht = jobs_lauf('cli', ['waisen']);
pruefe(!isset($bericht['waisen']['uebersprungen']),
       'Fuer cli gilt der Mindestabstand nicht',
       'der Zeitplan bestimmt dort die Haeufigkeit');

/* ---- Teil 7 — Der taegliche Job laeuft taeglich --------------------------- */

echo "\n  Teil 7 — aufraeumen laeuft hoechstens einmal je Kalendertag\n";
faellig_machen($pdo);
$b1 = jobs_lauf('cli', ['aufraeumen']);
pruefe(!isset($b1['aufraeumen']['uebersprungen'])
       && empty($b1['aufraeumen']['fehler']),
       'Erster Lauf des Tages geht durch',
       'erledigt ' . (string)($b1['aufraeumen']['erledigt'] ?? '?') . ' Schritte'
       . (empty($b1['aufraeumen']['fehler']) ? '' : ' · FEHLER: ' . $b1['aufraeumen']['fehler']));
$b2 = jobs_lauf('cli', ['aufraeumen']);
pruefe(($b2['aufraeumen']['uebersprungen'] ?? '') === 'heute schon gelaufen',
       'Der zweite Lauf desselben Tages wird uebersprungen',
       (string)($b2['aufraeumen']['uebersprungen'] ?? 'lief erneut — Fehler'));

/* ---- Teil 8 — Das Token -------------------------------------------------- */

echo "\n  Teil 8 — Token fuer den Abruf ueber die Adresse\n";
$t1 = jobs_token();
$t2 = jobs_token();
pruefe($t1 === $t2 && strlen($t1) === 64,
       'Das Token ist bestaendig und 32 Byte lang', strlen($t1) . ' Hexzeichen');
$t3 = jobs_token(true);
pruefe($t3 !== $t1 && jobs_token() === $t3,
       'Ein neues Token ersetzt das alte',
       'das alte ist damit ungueltig');

/* ---- Teil 9 — Nichts Fremdes angefasst ----------------------------------- */

echo "\n  Teil 9 — Was einen Eigentuemer hat, ist unveraendert\n";
aufraeumen($pdo, $basisM, $basisR);
$gesamtNachher = (int)$pdo->query('SELECT COUNT(*) FROM track_points')->fetchColumn();
$echteNachher  = punkte_mit_eigentuemer($pdo);
pruefe($echteNachher === $echteVorher,
       'Kein Punkt mit Eigentuemer ist verschwunden',
       number_format($echteVorher, 0, ',', ' ') . ' -> '
       . number_format($echteNachher, 0, ',', ' '));
printf("  [--] %-62s %s\n", 'Nebenbei abgeraeumte ECHTE Waisen (kein Fehler)',
       ($gesamtVorher - $gesamtNachher) . ' Zeilen');

} finally {
    aufraeumen($pdo, $basisM, $basisR);
}

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $erwartungen, $offen);
exit($offen === 0 ? 0 : 1);
