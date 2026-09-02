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
            spur_loeschen_nur_zeilen($pdo, $typ, $id, count($punkte));
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

/* ---- Teil 4 — Die Ausduennung (Stufe 2 -> 3, AP3) ------------------------- */

echo "\n  Teil 4 — Ausduennung: haelt sie ihre Zusage? (E-S2-05)\n";

/* GERECHNET WIRD AUF DEM BESTAND, GESCHRIEBEN WIRD NICHTS. Die Ausduennung
 * ersetzt einen Blob unwiderruflich; diese Probe rechnet nur und vergleicht.
 * Der Job selbst wird von der Jobprobe gefahren. */
$dpPunkte = 0; $dpBehalten = 0; $dpVerletzt = 0; $dpErste = null;
$dpBytesV = 0; $dpBytesN = 0; $dpMaxDauer = 0.0; $dpSpuren = 0; $dpSchon = 0;
$phasenVor = 0; $phasenNach = 0; $phasenGesamt = 0;

/* SCHON AUSGEDUENNTE SPUREN BLEIBEN AUSSEN VOR — und das ist keine Feinheit.
 * Eine Stufe-3-Spur noch einmal auszuduennen ist nicht die Handlung, um die
 * es hier geht: Ihre Punkte sind bereits die noetigen, ein zweiter Lauf
 * behaelt fast alle, und der gemessene Anteil steigt, ohne dass sich etwas
 * verbessert haette. Genau das ist beim ersten Lauf passiert — 25 Spuren des
 * Referenzkontos waren vom Job schon ausgeduennt, und der Anteil sprang von
 * 37,7 auf 43,0 %. Die Zahl war richtig gerechnet und beschrieb etwas
 * anderes, als ihre Beschriftung sagte.
 *
 * Wie viele uebersprungen wurden, steht unten in der Ausgabe. Wer die volle
 * Zahl braucht, setzt das Referenzkonto vorher neu auf
 * (tools/referenzdatensatz/einspielen/) und haelt die Jobs dabei an
 * (php jobs.php --pause 1800). */
$stufen = [];
foreach (['mission' => $mIds, 'rest' => $rIds] as $t => $ids) {
    if (!$ids) { continue; }
    $platz = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("SELECT owner_id, stufe FROM track_blobs
                         WHERE owner_type = ? AND owner_id IN ($platz)");
    $q->execute(array_merge([$t], $ids));
    foreach ($q->fetchAll(PDO::FETCH_KEY_PAIR) as $oid => $st) { $stufen["$t:$oid"] = (int)$st; }
}

foreach ($vorher as $typ => $liste) {
    foreach ($liste as $id => $punkte) {
        if (count($punkte) < 3) { continue; }
        if (($stufen["$typ:$id"] ?? 0) === SPUR_STUFE_DUENN) { $dpSchon++; continue; }
        $dpSpuren++;
        $zeiten = spur_schutzzeiten($pdo, $typ, $id);

        $t0 = microtime(true);
        $behalten = spur_ausduennen($punkte, $zeiten);
        $dpMaxDauer = max($dpMaxDauer, microtime(true) - $t0);

        $m = spur_ausduennung_pruefen($punkte, $behalten, $zeiten);
        if ($m !== null) { $dpVerletzt++; if ($dpErste === null) { $dpErste = "$typ/$id: $m"; } }

        $duenn = [];
        foreach ($behalten as $i) { $duenn[] = $punkte[$i]; }
        $dpPunkte   += count($punkte);
        $dpBehalten += count($duenn);
        $dpBytesV += strlen(spur_kodieren($punkte, SPUR_STUFE_ROH));
        $dpBytesN += strlen(spur_kodieren($duenn, SPUR_STUFE_DUENN, count($punkte)));

        /* Bleibt die Hoehenermittlung des Einsatzorts moeglich? Sie sucht
         * +/-SITE_ELE_TOLERANCE_S um den Phasenzeitpunkt; ohne den Schutz der
         * Phasenpunkte ginge sie leer aus (B-S2-08). */
        foreach ($zeiten as $ts) {
            $phasenGesamt++;
            $nahV = PHP_INT_MAX; foreach ($punkte as $p) { $nahV = min($nahV, abs((int)$p[4] - $ts)); }
            $nahN = PHP_INT_MAX; foreach ($duenn  as $p) { $nahN = min($nahN, abs((int)$p[4] - $ts)); }
            if ($nahV <= 300) { $phasenVor++; }
            if ($nahN <= 300) { $phasenNach++; }
        }
    }
}

pruefe($dpVerletzt === 0,
       'Kein verworfener Punkt liegt weiter weg als zugesagt',
       $dpVerletzt === 0
           ? sprintf('%d Spuren, %d Punkte, 0 Verletzungen von 2,0 m / 3,0 m%s',
                     $dpSpuren, $dpPunkte,
                     $dpSchon ? " (+$dpSchon schon ausgeduennt, uebersprungen)" : '')
           : "$dpVerletzt Spuren — erste: $dpErste");

$anteil = 100 * $dpBehalten / max(1, $dpPunkte);
pruefe($anteil > 25.0 && $anteil < 60.0,
       'Der behaltene Anteil liegt im erwarteten Band (E-S2-05: rund 37 %)',
       sprintf('%.2f %% (%d von %d Punkten)', $anteil, $dpBehalten, $dpPunkte));

pruefe($phasenNach === $phasenVor,
       'Die Ausduennung nimmt der Hoehenermittlung keinen Phasenpunkt',
       sprintf('%d von %d Phasen mit Punkt im +/-300-s-Fenster, vorher %d',
               $phasenNach, $phasenGesamt, $phasenVor));

/* WAS DIE AUSDUENNUNG WIRKLICH SPART, IN BYTE — nicht in Punkten. Die
 * naheliegende Rechnung „62 % weniger Punkte, also 62 % weniger Platz" ist
 * falsch: Die Ausduennung entfernt genau die VORHERSAGBAREN Punkte, die
 * verbleibenden Differenzen sind groesser und lassen sich schlechter packen. */
pruefe($dpBytesN < $dpBytesV,
       'Der Blob wird kleiner — aber viel weniger, als die Punktzahl vermuten laesst',
       sprintf('%.2f -> %.2f B je Originalpunkt (%.1f %% Ersparnis bei %.1f %% weggeworfenen Punkten)',
               $dpBytesV / max(1, $dpPunkte), $dpBytesN / max(1, $dpPunkte),
               100 * (1 - $dpBytesN / max(1, $dpBytesV)), 100 - $anteil));

pruefe($dpMaxDauer < 1.0,
       'Keine Spur des Bestands braucht laenger als eine Sekunde',
       sprintf('langsamste %.4f s; Schaetzung von spur_ausduenn_dauer_s() fuer '
             . '50 000 Punkte: %.2f s', $dpMaxDauer, spur_ausduenn_dauer_s(50000)));

/* ---- Die Prueffaelle, die der Referenzbestand NICHT liefert -------------- */

echo "\n  Teil 5 — Kuenstliche Prueffaelle (der Bestand liefert sie nicht)\n";

/** Eine gerade Strecke mit einer Ausbuchtung an einer Stelle. */
function probe_spur(int $n, callable $abweichung): array {
    $p = [];
    for ($i = 0; $i < $n; $i++) {
        [$dLat, $dEle] = $abweichung($i);
        $p[] = [$i, 47.0 + $i * 0.0001 + $dLat, 11.0, 700.0 + $dEle, 1750000000 + $i * 10];
    }
    return $p;
}

// (a) Gleichstand: zwei Punkte gleich weit vom Schutzzeitpunkt.
$g = probe_spur(11, fn($i) => [0.0, 0.0]);
$mitte = (int)$g[5][4];
$schutz = spur_schutzpunkte($g, [$mitte + 5]);   // genau zwischen 5 und 6
pruefe(in_array(5, $schutz, true) && !in_array(6, $schutz, true),
       'Bei Gleichstand gewinnt der FRUEHERE Punkt (wie site_elevation_lib.php)',
       'gewaehlt: ' . implode(',', $schutz) . ' (erwartet 0,5,10)');

// (b) Eine Spur ganz ohne Hoehe darf nicht zu 100 % stehenbleiben.
$ohneHoehe = probe_spur(500, fn($i) => [($i % 50 === 0) ? 0.00003 : 0.0, 0.0]);
foreach ($ohneHoehe as &$pp) { $pp[3] = null; } unset($pp);
$kOhne = spur_ausduennen($ohneHoehe, []);
pruefe(count($kOhne) < count($ohneHoehe) * 0.5,
       'Eine Spur ohne jede Hoehe wird trotzdem ausgeduennt',
       sprintf('%d von %d Punkten (%.1f %%)', count($kOhne), count($ohneHoehe),
               100 * count($kOhne) / count($ohneHoehe)));
pruefe(spur_ausduennung_pruefen($ohneHoehe, $kOhne, []) === null,
       'und haelt dabei die waagerechte Zusage ein');

// (c) Ein einzelner hoehenloser Punkt an einer waagerechten Ecke darf den
//     Hoehentest nicht stilllegen (die Ankerreihe, spur_hoehenanker()).
$falle = probe_spur(401, function ($i) {
    $dLat = ($i === 200) ? 0.0005 : 0.0;          // waagerechte Ecke
    $dEle = ($i === 300) ? 150.0 : 0.0;           // Hoehenspitze, waagerecht unsichtbar
    return [$dLat, $dEle];
});
$falle[200][3] = null;                            // genau dieser Punkt ohne Hoehe
$kFalle = spur_ausduennen($falle, []);
$hatSpitze = in_array(300, $kFalle, true);
pruefe($hatSpitze,
       'Eine Hoehenspitze hinter einem hoehenlosen Eckpunkt bleibt erhalten',
       sprintf('%d Punkte behalten, Index 300 %s', count($kFalle),
               $hatSpitze ? 'dabei' : 'FEHLT — 150 m still verloren'));

// (d) Der Abschnittsdeckel greift und aendert die Zusage nicht.
$lang = probe_spur(3000, fn($i) => [($i % 2 ? 0.000004 : -0.000004), 0.0]);
$mitDeckel  = spur_ausduennen($lang, [], SPUR_TOL_WAAGERECHT_M, SPUR_TOL_SENKRECHT_M, 500);
$ohneDeckel = spur_ausduennen($lang, [], SPUR_TOL_WAAGERECHT_M, SPUR_TOL_SENKRECHT_M, 0);
pruefe(spur_ausduennung_pruefen($lang, $mitDeckel, []) === null
       && count($mitDeckel) >= count($ohneDeckel),
       'Der Abschnittsdeckel haelt die Zusage und behaelt hoechstens mehr',
       sprintf('mit Deckel %d, ohne %d Punkte', count($mitDeckel), count($ohneDeckel)));

// (e) n_original ueberlebt die Ausduennung (E-S2-08).
$eineSpur = null; $eineId = null; $einTyp = null;
foreach ($vorher as $typ => $liste) {
    foreach ($liste as $id => $punkte) {
        if (count($punkte) > 50) { $eineSpur = $punkte; $eineId = $id; $einTyp = $typ; break 2; }
    }
}
if ($eineSpur !== null) {
    $k = spur_ausduennen($eineSpur, spur_schutzzeiten($pdo, $einTyp, $eineId));
    $d = []; foreach ($k as $i) { $d[] = $eineSpur[$i]; }
    $kopf = spur_kopf(spur_kodieren($d, SPUR_STUFE_DUENN, count($eineSpur)));
    pruefe($kopf['n_original'] === count($eineSpur)
           && $kopf['n'] === count($d)
           && $kopf['stufe'] === SPUR_STUFE_DUENN,
           'Der Stufe-3-Kopf traegt n_original der VOLLEN Spur (E-S2-08)',
           "n_original={$kopf['n_original']} (Spur " . count($eineSpur) . "), "
           . "n={$kopf['n']}, stufe={$kopf['stufe']}");
}

/* ---- Teil 6 — Schnitt und Nachlieferung (S4/A2, E-S4-53) ------------------
 *
 * DER PRUEFFALL, DEN DAS KONZEPT VERLANGT (Abschnitt 14, offener Punkt 4):
 * Schnitt, dann Nachlieferung — die geschnittenen Punkte kommen nicht wieder,
 * die spaeteren schon.
 *
 * ER LAEUFT AUF EINER EIGENS ANGELEGTEN KULISSE und nicht auf dem Bestand.
 * Der Grund ist der Fall selbst: Er braucht ein Ruhesegment mit bekannter
 * Zeitachse, einen Einsatz als Ziel und einen Punktvorrat, der beim Schnitt
 * absichtlich nur zur HAELFTE geliefert ist — die andere Haelfte liegt im
 * Puffer des Geraets, und genau um sie geht es. So etwas liefert kein
 * gewachsener Bestand. Alles laeuft in einer Transaktion, die am Ende
 * zurueckgerollt wird.
 */
echo "\n  Teil 6 — Schnitt und Nachlieferung (E-S4-53)\n";

$hatSchnitte = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables
                                  WHERE table_schema = DATABASE()
                                    AND table_name = 'track_cuts'")->fetchColumn() > 0;
pruefe($hatSchnitte, 'Tabelle track_cuts vorhanden',
       $hatSchnitte ? '' : 'update.php ausfuehren (Migration 2026_09_02_schnitte)');

if ($hatSchnitte) {
    $pdo->beginTransaction();
    try {
        $t0 = 1756700000;          // fest, damit die Zahlen reproduzierbar sind
        $pdo->prepare('INSERT INTO days (user_id, day) VALUES (?, ?)')
            ->execute([$uid, '2026-09-02']);
        $dayId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO rest_segments (user_id, client_ref, day_id, started_at)
                       VALUES (?, ?, ?, FROM_UNIXTIME(?))')
            ->execute([$uid, 'probe-rest', $dayId, $t0]);
        $segId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO missions (user_id, client_ref, day_id, started_at, origin, manual)
                       VALUES (?, ?, ?, FROM_UNIXTIME(?), ?, 1)')
            ->execute([$uid, 'probe-mission', $dayId, $t0 + 300, 'manual']);
        $misId = (int)$pdo->lastInsertId();

        /* 600 Punkte im Sekundentakt waeren der volle Dienst; beim Schnitt
         * sind erst 350 geliefert (seq 0..349). Geschnitten wird
         * [t0+300, t0+399] — davon liegen 50 Punkte da (seq 300..349) und 50
         * noch im Geraet. */
        $ins = $pdo->prepare('INSERT INTO track_points (owner_type, owner_id, seq, lat, lon, ele, ts)
                              VALUES (?,?,?,?,?,?,?)');
        for ($i = 0; $i < 350; $i++) {
            $ins->execute(['rest', $segId, $i, 48.0 + $i * 0.0001, 11.0 + $i * 0.0001,
                           500.0, $t0 + $i]);
        }
        pruefe(count(spur_lesen($pdo, 'rest', $segId)) === 350,
               'Kulisse steht: Ruhesegment mit gelieferten Punkten', '350 Punkte, seq 0..349');

        $markeVorher = spur_naechste_seq($pdo, 'rest', $segId);

        /* ---- Der Schnitt ------------------------------------------------- */
        $erg = spur_teilen($pdo, 'rest', $segId, 'mission', $misId, $t0 + 300, $t0 + 399);
        schnitt_vermerken($pdo, $uid, 'rest', $segId, $misId, $t0 + 300, $t0 + 399,
                          $erg['genommen']);

        pruefe($erg['genommen'] === 50 && $erg['geblieben'] === 300,
               'Der Schnitt nimmt genau den Zeitbereich',
               "genommen {$erg['genommen']}, geblieben {$erg['geblieben']} (erwartet 50/300)");
        pruefe(count(spur_lesen($pdo, 'mission', $misId)) === 50,
               'Die Punkte stehen danach im Einsatz', '50 Punkte');
        pruefe(count(spur_lesen($pdo, 'rest', $segId)) === 300,
               'und nicht mehr im Segment — sie wandern, sie werden nicht kopiert',
               '300 Punkte');

        $markeNachher = spur_naechste_seq($pdo, 'rest', $segId);
        pruefe($markeNachher >= $markeVorher,
               'Die Fortsetzungsmarke faellt durch den Schnitt nicht zurueck',
               "vorher $markeVorher, nachher $markeNachher");

        $schnitte = schnitte_lesen($pdo, 'rest', $segId);
        pruefe(count($schnitte) === 1
               && $schnitte[0]['von_ts'] === $t0 + 300
               && $schnitte[0]['bis_ts'] === $t0 + 399,
               'Der Sperrvermerk steht und nennt den Zeitraum',
               count($schnitte) . ' Vermerk(e), '
               . ($schnitte ? ($schnitte[0]['bis_ts'] - $schnitte[0]['von_ts'] + 1) . ' s' : '—'));

        /* ---- Die Nachlieferung ------------------------------------------- *
         *
         * Das Geraet setzt bei seiner Marke fort und sendet die restlichen
         * 250 Punkte (ts t0+350 .. t0+599). 50 davon liegen im geschnittenen
         * Bereich, 200 dahinter. Nachgebaut ist der Weg aus ingest.php:
         * Sequenz aus `seq_from`, dann `n_original`, dann die Sperrpruefung. */
        $stand    = spur_stand($pdo, 'rest', $segId);
        $seqFrom  = $markeNachher;
        $genommen = 0; $gesperrt = 0; $wiederholt = 0;
        for ($i = 350; $i < 600; $i++) {
            $seq = $seqFrom + ($i - 350);
            $ts  = $t0 + $i;
            if ($seq < $stand['n_original']) { $wiederholt++; continue; }
            if (schnitt_gesperrt($schnitte, $ts)) { $gesperrt++; continue; }
            $ins->execute(['rest', $segId, $seq, 48.0 + $i * 0.0001, 11.0 + $i * 0.0001,
                           500.0, $ts]);
            $genommen++;
        }
        pruefe($gesperrt === 50,
               'Nachgelieferte Punkte des geschnittenen Bereichs werden verworfen',
               "$gesperrt von 250 gesperrt (erwartet 50)");
        pruefe($genommen === 200,
               'Alles ausserhalb des Bereichs kommt normal an',
               "$genommen von 250 angenommen (erwartet 200)");
        pruefe(count(spur_lesen($pdo, 'rest', $segId)) === 500,
               'Das Segment traegt danach Ruhe vor UND nach dem Einsatz',
               '500 Punkte (300 vorher + 200 nachher)');
        pruefe(count(spur_lesen($pdo, 'mission', $misId)) === 50,
               'Der Einsatz hat dabei keinen Punkt dazubekommen', '50 Punkte');

        /* Und der zweite Boden getrennt nachgewiesen: Eine Uhr, die ihre
         * Marke verloren hat, sendet ab 0 neu. Diese Punkte faengt
         * `n_original` ab — nicht der Vermerk. */
        $nOrig = (int)spur_stand($pdo, 'rest', $segId)['n_original'];
        $abgefangen = 0;
        for ($i = 0; $i < 350; $i++) { if ($i < $nOrig) { $abgefangen++; } }
        pruefe($abgefangen === 350,
               'Eine Uhr, die ab 0 neu sendet, laeuft vollstaendig in n_original',
               "$abgefangen von 350 abgefangen (n_original = $nOrig)");

        /* ---- Rueckgaengig (E-S4-17) --------------------------------------- *
         *
         * Die Punkte gehen zurueck, der Vermerk faellt. Das ist die Stelle,
         * an der sich zeigt, ob `spur_teilen()` das Ziel ERGAENZT: Das
         * Segment traegt inzwischen 500 Punkte, und ein Blob-Schreiben, das
         * ersetzt statt zu mischen, liesse davon 50 uebrig. */
        $vorherSeg = count(spur_lesen($pdo, 'rest', $segId));
        $zur = spur_teilen($pdo, 'mission', $misId, 'rest', $segId, $t0, $t0 + 599);
        schnitte_loeschen($pdo, 'ziel', [$misId]);

        pruefe($zur['genommen'] === 50,
               'Rueckgaengig holt genau die geschnittenen Punkte zurueck',
               "{$zur['genommen']} Punkte (erwartet 50)");
        pruefe(count(spur_lesen($pdo, 'rest', $segId)) === 550,
               'Das Segment ist wieder vollstaendig — die 500 bleiben stehen',
               'vorher ' . $vorherSeg . ', nachher '
               . count(spur_lesen($pdo, 'rest', $segId)) . ' (erwartet 550)');
        pruefe(count(spur_lesen($pdo, 'mission', $misId)) === 0,
               'Der Einsatz traegt danach keine Punkte mehr', '0 Punkte');
        pruefe(schnitte_lesen($pdo, 'rest', $segId) === [],
               'Der Sperrvermerk ist mit aufgehoben — das Loch bleibt fuellbar',
               '0 Vermerke');

        /* Die Zeitfolge muss dabei aufsteigend geblieben sein: Die
         * zurueckgegebenen Punkte liegen ZWISCHEN den vorhandenen, nicht
         * hinten dran. Ein Blob mit ungeordneten Zeiten faellt sonst erst in
         * der Karte auf. */
        $zurueck = spur_lesen($pdo, 'rest', $segId);
        $unsortiert = 0;
        for ($i = 1; $i < count($zurueck); $i++) {
            if ((int)$zurueck[$i][4] < (int)$zurueck[$i - 1][4]) { $unsortiert++; }
        }
        pruefe($unsortiert === 0,
               'Die vereinigte Spur ist zeitlich aufsteigend',
               "$unsortiert Rueckspruenge in " . count($zurueck) . " Punkten");

        /* ---- Der Randfall: alles wandert ---------------------------------- */
        $erg2 = spur_teilen($pdo, 'rest', $segId, 'mission', $misId, $t0, $t0 + 599);
        pruefe($erg2['geblieben'] === 0 && count(spur_lesen($pdo, 'rest', $segId)) === 0,
               'Ein Schnitt darf die ganze Spur nehmen', '0 Punkte im Segment');
        pruefe(spur_naechste_seq($pdo, 'rest', $segId) >= $markeNachher,
               'und die Fortsetzungsmarke bleibt trotzdem stehen (leerer Blob)',
               'naechste_seq = ' . spur_naechste_seq($pdo, 'rest', $segId));

        $pdo->rollBack();
        pruefe(!$pdo->inTransaction(), 'Der Prueffall ist rueckstandslos zurueckgerollt', '');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }
}

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $erwartungen, $offen);
exit($offen === 0 ? 0 : 1);
