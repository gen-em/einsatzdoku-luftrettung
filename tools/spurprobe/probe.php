<?php
declare(strict_types=1);

/**
 * Spurprobe — kommt aus dem Blob genau das zurueck, was hineinging? (S2/AP1)
 *
 * WOFUER. Die Verdichtung loescht Zeilen. Was danach fehlt, ist weg — es gibt
 * keine zweite Quelle. Die Rundlaufpruefung in `spur_lib.php` ist deshalb die
 * letzte Instanz vor einem unwiderruflichen DELETE, und dieses Werkzeug faehrt
 * sie ueber den GANZEN Referenzbestand, nicht ueber ein Beispiel.
 *
 * Und es beantwortet die zweite Frage von AP1: Liefern die Leser vor und nach
 * der Verdichtung DASSELBE? Tagesansicht, Einsatzansicht, Export und Sicherung
 * ziehen ihre Punkte seither ueber `spur_lib.php`; wenn dort etwas anders
 * herauskommt, faellt es hier auf und nicht erst in der Karte.
 *
 * ES AENDERT NICHTS. Der Verdichtungsteil laeuft in einer Transaktion, die am
 * Ende zurueckgerollt wird. Das Werkzeug laesst sich damit auch gegen einen
 * Bestand fahren, den man behalten will — etwa das Referenzkonto.
 *
 * Aufruf:
 *   php tools/spurprobe/probe.php [konto]
 *
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 2) . '/server';
require_once $wurzel . '/config.php';
require_once $wurzel . '/db.php';
require_once $wurzel . '/spur_lib.php';
require_once $wurzel . '/backup_lib.php';

$konto = $argv[1] ?? 'demo@gen-em.org';

$pdo = db();
$q = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$q->execute([$konto]);
$uid = (int)$q->fetchColumn();
if (!$uid) {
    fwrite(STDERR, "Konto $konto gibt es auf dieser Installation nicht.\n");
    exit(2);
}

$erwartungen = 0;
$offen = 0;
function pruefe(bool $ok, string $was, string $wert = ''): void {
    global $erwartungen, $offen;
    $erwartungen++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-62s %s\n", $ok ? 'ok ' : 'FEHL', $was, $wert);
}

echo "Spurprobe gegen $konto\n\n";

/* ---- Welche Spuren gibt es? ---------------------------------------------- */

$mIds = $pdo->prepare('SELECT id FROM missions WHERE user_id = ?');
$mIds->execute([$uid]);
$mIds = array_map('intval', $mIds->fetchAll(PDO::FETCH_COLUMN));
$rIds = $pdo->prepare('SELECT id FROM rest_segments WHERE user_id = ?');
$rIds->execute([$uid]);
$rIds = array_map('intval', $rIds->fetchAll(PDO::FETCH_COLUMN));

$vorher = [
    'mission' => spur_lesen_viele($pdo, 'mission', $mIds),
    'rest'    => spur_lesen_viele($pdo, 'rest', $rIds),
];
$punkteGesamt = 0;
foreach ($vorher as $liste) {
    foreach ($liste as $p) { $punkteGesamt += count($p); }
}
$spurenGesamt = count($vorher['mission']) + count($vorher['rest']);
echo "  $spurenGesamt Spuren, $punkteGesamt Punkte\n\n";
pruefe($punkteGesamt > 0, 'Der Bestand hat ueberhaupt Spuren',
       "$punkteGesamt Punkte");

/* ---- Teil 1 — Rundlauf ueber jede einzelne Spur --------------------------- */

echo "\n  Teil 1 — Rundlauf Punkte -> Blob -> Punkte\n";
$abweichungen = [];
$bytes = 0;
foreach ($vorher as $typ => $liste) {
    foreach ($liste as $id => $punkte) {
        if (!$punkte) { continue; }
        $blob = spur_kodieren($punkte, SPUR_STUFE_ROH);
        $bytes += strlen($blob);
        $m = spur_rundlauf_pruefen($punkte, $blob);
        if ($m !== null) { $abweichungen[] = "$typ/$id: $m"; }
    }
}
pruefe($abweichungen === [], 'Jede Spur kommt unveraendert zurueck',
       count($abweichungen) . ' Abweichungen'
       . ($abweichungen ? ' — erste: ' . $abweichungen[0] : ''));
pruefe($punkteGesamt > 0 && $bytes / max(1, $punkteGesamt) < 10.0,
       'Der Blob kostet weniger als 10 Byte je Punkt',
       sprintf('%.2f B/Punkt (Zeilen: 62,4)', $bytes / max(1, $punkteGesamt)));

/* ---- Teil 2 — Kopf lesen, ohne auszupacken -------------------------------- */

echo "\n  Teil 2 — Der Kopf sagt die Wahrheit, ohne den Strom zu oeffnen\n";
$kopfFehler = [];
foreach ($vorher as $typ => $liste) {
    foreach ($liste as $id => $punkte) {
        if (!$punkte) { continue; }
        $blob = spur_kodieren($punkte, SPUR_STUFE_ROH);
        $k = spur_kopf($blob);
        if ($k['n'] !== count($punkte) || $k['n_original'] !== count($punkte)
            || $k['stufe'] !== SPUR_STUFE_ROH || $k['aufloesung'] !== SPUR_AUFL_1) {
            $kopfFehler[] = "$typ/$id";
        }
    }
}
pruefe($kopfFehler === [], 'Punktzahl, Stufe und Aufloesung stehen im Kopf',
       count($kopfFehler) . ' abweichend');

/* Ein Blob mit fremder Aufloesungskennung muss ABGELEHNT werden, nicht
 * falsch gedeutet — sonst verschoebe eine kuenftige Formataenderung jede
 * Koordinate, und zwar lautlos. */
$erste = null;
foreach ($vorher['mission'] as $p) { if ($p) { $erste = $p; break; } }
if ($erste) {
    $blob = spur_kodieren($erste, SPUR_STUFE_ROH);
    $verbogen = substr_replace($blob, chr(99), 4, 1);
    $abgelehnt = false;
    try { spur_dekodieren($verbogen); } catch (Throwable $ex) { $abgelehnt = true; }
    pruefe($abgelehnt, 'Ein Blob mit unbekannter Aufloesung wird abgelehnt');

    $verbogen = substr_replace($blob, chr(99), 2, 1);
    $abgelehnt = false;
    try { spur_dekodieren($verbogen); } catch (Throwable $ex) { $abgelehnt = true; }
    pruefe($abgelehnt, 'Ein Blob mit unbekannter Formatfassung wird abgelehnt');

    $abgelehnt = false;
    try { spur_dekodieren('Unfug'); } catch (Throwable $ex) { $abgelehnt = true; }
    pruefe($abgelehnt, 'Etwas, das kein Blob ist, wird abgelehnt');
}

/* ---- Teil 3 — Verdichten und die Leser vergleichen ------------------------ */

echo "\n  Teil 3 — Vor und nach der Verdichtung dasselbe\n";

/* Der Vergleichsmassstab ist der QUANTISIERTE Bestand, nicht der rohe: Das
 * Format sagt 10^-6 Grad und 0,1 m zu (F-S2-01), und genau das muss
 * herauskommen. Wer hier gegen die rohe DOUBLE-Spalte vergliche, pruefte eine
 * Genauigkeit, die nie versprochen war. */
$sollNach = [];
foreach ($vorher as $typ => $liste) {
    foreach ($liste as $id => $punkte) {
        $sollNach[$typ][$id] = array_map('spur_quantisieren', $punkte);
    }
}

$backupVorher = edbak_build($uid);

$pdo->beginTransaction();
try {
    $verdichtet = 0;
    foreach ($vorher as $typ => $liste) {
        foreach ($liste as $id => $punkte) {
            if (!$punkte) { continue; }
            $blob = spur_kodieren($punkte, SPUR_STUFE_ROH);
            spur_blob_schreiben($pdo, $typ, $id, $blob, SPUR_STUFE_ROH,
                                count($punkte), count($punkte));
            spur_loeschen_nur_zeilen($pdo, $typ, [$id]);
            $verdichtet++;
        }
    }
    pruefe($verdichtet === $spurenGesamt, 'Alle Spuren verdichtet',
           "$verdichtet von $spurenGesamt");

    /* NUR DIE ZEILEN DIESES KONTOS. Die erste Fassung zaehlte
     * `SELECT COUNT(*) FROM track_points` — also die Tabelle aller Konten —
     * und schlug an, weil das Messstandkonto daneben 3,2 Mio. Zeilen haelt.
     * Verdichtet wurde aber nur ein Konto. Wieder eine Zahl, die etwas
     * anderes misst, als sie behauptet. */
    $rest = 0;
    foreach ([['mission', $mIds], ['rest', $rIds]] as [$typ, $ids]) {
        if (!$ids) { continue; }
        $platz = implode(',', array_fill(0, count($ids), '?'));
        $z = $pdo->prepare("SELECT COUNT(*) FROM track_points
                             WHERE owner_type = ? AND owner_id IN ($platz)");
        $z->execute(array_merge([$typ], $ids));
        $rest += (int)$z->fetchColumn();
    }
    pruefe($rest === 0,
           'Nach der Verdichtung stehen keine Zeilen dieses Kontos mehr da',
           "verbleibende Zeilen dieses Kontos: $rest");

    $nachher = [
        'mission' => spur_lesen_viele($pdo, 'mission', $mIds),
        'rest'    => spur_lesen_viele($pdo, 'rest', $rIds),
    ];
    $unterschiede = [];
    foreach ($sollNach as $typ => $liste) {
        foreach ($liste as $id => $soll) {
            $ist = $nachher[$typ][$id] ?? [];
            if ($ist != $soll) { $unterschiede[] = "$typ/$id"; }
        }
    }
    pruefe($unterschiede === [],
           'spur_lesen_viele() liefert nach der Verdichtung dasselbe',
           count($unterschiede) . ' Spuren abweichend'
           . ($unterschiede ? ' — erste: ' . $unterschiede[0] : ''));

    /* DIE SICHERUNG IST DIE HAERTESTE PROBE: Sie enthaelt jeden Punkt jeder
     * Spur mit Nummer, Koordinate, Hoehe und Zeit. Sind beide Pakete gleich,
     * hat die Verdichtung an keinem einzigen Wert etwas geaendert. */
    $backupNachher = edbak_build($uid);
    $a = json_decode($backupVorher, true);
    $b = json_decode($backupNachher, true);
    // created_at unterscheidet sich zwangslaeufig — es ist der Zeitpunkt.
    unset($a['created_at'], $b['created_at']);
    // Die Punkte des Vorher-Pakets auf die Formataufloesung bringen.
    foreach (['missions', 'rest_segments'] as $zweig) {
        foreach ($a[$zweig] as $i => $z) {
            if (!empty($z['track'])) {
                $a[$zweig][$i]['track'] = array_map('spur_quantisieren', $z['track']);
            }
        }
        foreach ($b[$zweig] as $i => $z) {
            if (!empty($z['track'])) {
                $b[$zweig][$i]['track'] = array_map('spur_quantisieren', $z['track']);
            }
        }
    }
    pruefe($a == $b, 'edbak_build() liefert vor und nach der Verdichtung dasselbe',
           'Paket ' . round(strlen($backupNachher) / 1048576, 2) . ' MB');

    /* Die Fortsetzungsmarke der Uhr darf durch die Verdichtung NICHT
     * zurueckfallen — sonst schickt sie den ganzen Dienst noch einmal. */
    $markenFehler = [];
    foreach ($vorher['mission'] as $id => $punkte) {
        if (!$punkte) { continue; }
        if (spur_naechste_seq($pdo, 'mission', $id) !== count($punkte)) {
            $markenFehler[] = "mission/$id";
        }
    }
    pruefe($markenFehler === [],
           'next_seq bleibt nach der Verdichtung gleich (E-S2-08)',
           count($markenFehler) . ' abweichend');

    /* Und die Loeschwege muessen BEIDES abraeumen. */
    $probeId = $mIds[0] ?? null;
    if ($probeId !== null) {
        spur_loeschen($pdo, 'mission', [$probeId]);
        $z = $pdo->prepare("SELECT COUNT(*) FROM track_points WHERE owner_type='mission' AND owner_id=?");
        $z->execute([$probeId]);
        $b1 = $pdo->prepare("SELECT COUNT(*) FROM track_blobs WHERE owner_type='mission' AND owner_id=?");
        $b1->execute([$probeId]);
        pruefe((int)$z->fetchColumn() === 0 && (int)$b1->fetchColumn() === 0,
               'spur_loeschen() laesst weder Zeile noch Blob zurueck',
               "Einsatz $probeId");
    }
} finally {
    $pdo->rollBack();
}

/* Nach dem Rollback muss der Bestand sein, was er war. */
$nachRollback = spur_lesen_viele($pdo, 'mission', $mIds);
$gleich = true;
foreach ($vorher['mission'] as $id => $punkte) {
    if (($nachRollback[$id] ?? []) != $punkte) { $gleich = false; break; }
}
pruefe($gleich, 'Nach dem Rollback ist der Bestand unveraendert');

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $erwartungen, $offen);
exit($offen === 0 ? 0 : 1);
