<?php
declare(strict_types=1);

/**
 * Spurprobe — haelt die Ausduennung ihre Zusage, und gehen die Wege von
 * `spur_lib.php` richtig mit den Stuecken um? (S2/AP1, seit S11-12 SPUR2)
 *
 * Anlass: Nr. 304 — der Rundlauf hielt int gegen float; 175 von 181 Spuren sahen veraendert aus
 *
 * WAS SIE SEIT WEB 23 MISST (S11-12). Bis Web 22.7.0 fuhr sie SPUR1: Rundlauf
 * Punkte -> Blob -> Punkte, Kopf, Verdichtung, die Leser vor und nach der
 * Verdichtung, den Schnitt mit `spur_teilen()`. Mit dem Klartext sind Format
 * und Funktionen gegangen. Geblieben sind zwei Fragen:
 *
 *   Teil 1  Die Ausduennung nach Douglas-Peucker (`spur_ausduennen()`) — die
 *           PHP-Fassung ist die Referenz, gegen die die Wartungslaufprobe den
 *           Browser vergleicht. Gerechnet ueber die Punkte der Demo-Fixture
 *           (sie liegen im Repositorium im Klartext; der Server sieht sie nie).
 *   Teil 2  Kuenstliche Prueffaelle, die kein Bestand liefert.
 *   Teil 3  Die SPUR2-Wege an einem Einsatz des Kontos: Zahl, Marke, Stand,
 *           Groesse, Umdatieren, Loeschen — in einer Transaktion, die am Ende
 *           zurueckgerollt wird. Und dass es die Klartexttabellen nicht mehr
 *           gibt.
 *
 * ES AENDERT NICHTS. Teil 3 laeuft in einer Transaktion, die zurueckrollt.
 *
 * Aufruf:
 *   php tools/proben/spur/probe.php [konto]
 *
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 3) . '/server';
require_once $wurzel . '/config.php';
require_once $wurzel . '/db.php';
require_once $wurzel . '/spur_lib.php';
require_once $wurzel . '/demo_lib.php';

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

echo "Spurprobe gegen $konto\n";

/* ---- Teil 1 — Ausduennung ueber die Punkte der Demo-Fixture --------------- */

echo "\n  Teil 1 — Ausduennung: haelt sie ihre Zusage? (E-S2-05)\n";

$fx = demo_fixture_vorhanden() ? demo_fixture_laden()['daten'] : ['missions' => [], 'rest_segments' => []];
$spuren = [];
foreach ($fx['missions'] ?? [] as $m) {
    if (!empty($m['track'])) {
        $zeiten = [];
        foreach ($m['phases'] ?? [] as $p) {
            $zeiten[] = (int)strtotime((string)$p['occurred_at'] . ' UTC');
        }
        sort($zeiten);
        $spuren[] = [$m['track'], $zeiten];
    }
}
foreach ($fx['rest_segments'] ?? [] as $r) {
    if (!empty($r['track'])) { $spuren[] = [$r['track'], []]; }
}
$alle = array_sum(array_map(static fn($s) => count($s[0]), $spuren));
pruefe($alle > 0, 'Die Demo-Fixture traegt Punkte', count($spuren) . " Spuren, $alle Punkte");

$dpPunkte = 0; $dpBehalten = 0; $dpVerletzt = 0; $dpErste = null;
$dpMaxDauer = 0.0; $dpSpuren = 0;
$phasenVor = 0; $phasenNach = 0; $phasenGesamt = 0;
foreach ($spuren as $k => [$punkte, $zeiten]) {
    $punkte = array_map(static fn($p) => [(int)$p[0], (float)$p[1], (float)$p[2],
                                          $p[3] === null ? null : (float)$p[3], (int)$p[4]], $punkte);
    if (count($punkte) < 3) { continue; }
    $dpSpuren++;
    $t0 = microtime(true);
    $behalten = spur_ausduennen($punkte, $zeiten);
    $dpMaxDauer = max($dpMaxDauer, microtime(true) - $t0);
    $m = spur_ausduennung_pruefen($punkte, $behalten, $zeiten);
    if ($m !== null) { $dpVerletzt++; if ($dpErste === null) { $dpErste = "Spur $k: $m"; } }
    $duenn = [];
    foreach ($behalten as $i) { $duenn[] = $punkte[$i]; }
    $dpPunkte   += count($punkte);
    $dpBehalten += count($duenn);
    /* Bleibt die Hoehenermittlung des Einsatzorts moeglich? Sie sucht
     * +/-300 s um den Phasenzeitpunkt; ohne den Schutz der Phasenpunkte ginge
     * sie leer aus (B-S2-08). */
    foreach ($zeiten as $ts) {
        $phasenGesamt++;
        $nahV = PHP_INT_MAX; foreach ($punkte as $p) { $nahV = min($nahV, abs($p[4] - $ts)); }
        $nahN = PHP_INT_MAX; foreach ($duenn  as $p) { $nahN = min($nahN, abs($p[4] - $ts)); }
        if ($nahV <= 300) { $phasenVor++; }
        if ($nahN <= 300) { $phasenNach++; }
    }
}

pruefe($dpSpuren > 0 && $dpVerletzt === 0,
       'Kein verworfener Punkt liegt weiter weg als zugesagt',
       $dpVerletzt === 0
           ? sprintf('%d Spuren, %d Punkte, 0 Verletzungen von 2,0 m / 3,0 m', $dpSpuren, $dpPunkte)
           : "$dpVerletzt Spuren — erste: $dpErste");
$anteil = 100 * $dpBehalten / max(1, $dpPunkte);
pruefe($anteil > 25.0 && $anteil < 60.0,
       'Der behaltene Anteil liegt im erwarteten Band (E-S2-05: rund 37 %)',
       sprintf('%.2f %% (%d von %d Punkten)', $anteil, $dpBehalten, $dpPunkte));
pruefe($phasenNach === $phasenVor,
       'Die Ausduennung nimmt der Hoehenermittlung keinen Phasenpunkt',
       sprintf('%d von %d Phasen mit Punkt im +/-300-s-Fenster, vorher %d',
               $phasenNach, $phasenGesamt, $phasenVor));
pruefe($dpMaxDauer < 1.0,
       'Keine Spur der Fixture braucht laenger als eine Sekunde',
       sprintf('langsamste %.4f s; Schaetzung von spur_ausduenn_dauer_s() fuer '
             . '50 000 Punkte: %.2f s', $dpMaxDauer, spur_ausduenn_dauer_s(50000)));

/* ---- Teil 2 — Kuenstliche Prueffaelle ------------------------------------ */

echo "\n  Teil 2 — Kuenstliche Prueffaelle (die Fixture liefert sie nicht)\n";

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
       'Bei Gleichstand gewinnt der FRUEHERE Punkt (wie EdOrtsdaten.ortshoehe())',
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

/* ---- Teil 3 — Die SPUR2-Wege von spur_lib.php ----------------------------- */

echo "\n  Teil 3 — Die Wege von spur_lib.php an den Stuecken (S11-12)\n";

pruefe(!db_hat_tabelle($pdo, 'track_points') && !db_hat_tabelle($pdo, 'track_blobs'),
       'Die Klartexttabellen gibt es nicht mehr (Migration 2026_10_19)',
       'track_points, track_blobs');

/* DIE PROBE LEGT IHRE STUECKE SELBST AN, in einer Transaktion, die am Ende
 * zurueckrollt. Bis zum vierten Pruefstand von S11-12 suchte sie im
 * Demo-Konto einen Einsatz mit mehreren Stuecken der Stufe 1 — die gibt es
 * nach einem frischen Demo-Reset, nicht aber, sobald der Wartungslauf
 * (ausgeloest von jeder Probe, die die Startseite oeffnet) die Spuren
 * gepackt hat. Die Probe hing so am Zustand der Anlage, nicht am Code.
 * Gemessen wird an einem Eigentuemer ohne Zeile in `missions`: Die Stuecke
 * haengen an keinem Fremdschluessel, und die Funktionen fragen nur sie. */
$pdo->beginTransaction();
try {
    $mid = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) + 1000000 FROM missions')->fetchColumn();
    $h1 = random_bytes(146); $h2 = random_bytes(178);
    spur2_stueck_schreiben($pdo, 'mission', $mid, 0, 10, 1783000000, 1783000540, $h1);
    spur2_stueck_schreiben($pdo, 'mission', $mid, 10, 12, 1783000600, 1783001260, $h2);
    $soll = ['n' => 2, 'p' => 22, 'b' => strlen($h1) + strlen($h2), 'v' => 1783000000, 'w' => 22];
    pruefe(spur_stand($pdo, 'mission', $mid)['stufe'] === 1,
           'Zwei Stuecke der Stufe 1 an einem Probeneigentuemer angelegt', "Einsatz $mid");
    pruefe((spur_zahlen($pdo, 'mission', [$mid])[$mid] ?? -1) === $soll['p'],
           'spur_zahlen() = Summe der Punkte der Stuecke', (string)$soll['p']);
    pruefe(spur_naechste_seq($pdo, 'mission', $mid) === $soll['w'],
           'spur_naechste_seq() = hoechstes seq_from + punkte', (string)$soll['w']);
    $st = spur_stand($pdo, 'mission', $mid);
    pruefe($st['stufe'] === 1 && !spur_ist_ausgeduennt($st) && $st['naechste_seq'] === $soll['w'],
           'spur_stand(): Stufe 1, nicht ausgeduennt, dieselbe Marke', json_encode($st));
    pruefe(spur_bytes($pdo, 'mission', [$mid]) === $soll['b'],
           'spur_bytes() = Laenge der Huellen', (string)$soll['b']);
    pruefe(spur_min_ts($pdo, 'mission', [$mid]) === $soll['v'],
           'spur_min_ts() = fruehestes ts_von', (string)$soll['v']);
    spur_zeit_verschieben($pdo, 'mission', [$mid], 3600);
    pruefe(spur_min_ts($pdo, 'mission', [$mid]) === $soll['v'] + 3600,
           'spur_zeit_verschieben(+3600) verschiebt ts_von', '');
    $weg = spur_loeschen($pdo, 'mission', [$mid]);
    pruefe($weg['stuecke'] === $soll['n']
           && (spur_zahlen($pdo, 'mission', [$mid])[$mid] ?? 0) === 0
           && spur_stand($pdo, 'mission', $mid)['stufe'] === 1,
           'spur_loeschen() nimmt jedes Stueck', $weg['stuecke'] . ' Stuecke');
} finally {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
}
pruefe((int)$pdo->query("SELECT COUNT(*) FROM track_stuecke WHERE owner_type = 'mission'
                         AND owner_id = $mid")->fetchColumn() === 0,
       'Nach dem Zurueckrollen liegt nichts', '');

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $erwartungen, $offen);
exit($offen === 0 ? 0 : 1);
