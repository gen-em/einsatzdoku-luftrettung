<?php
declare(strict_types=1);

/**
 * Spurspeicherung — die EINZIGE Stelle, die GPS-Aufzeichnungen liest,
 * schreibt, zaehlt und loescht (CLAUDE.md 4).
 *
 * SEIT WEB 23 NUR NOCH SPUR2 (S11-12, E-S11-19). Eine Aufzeichnung liegt als
 * Folge verschlossener Stuecke in `track_stuecke`: Geraetehuellen, die nur
 * der Browser des Kontos oeffnet. Der Server sieht je Stueck nur, wo es steht
 * (`seq_from`, `punkte`, `n_original`), welchen Zeitraum es abdeckt
 * (`ts_von`, `ts_bis`) und auf welcher Stufe es liegt (1 vom Geraet,
 * 2 zusammengefuegt und gepackt, 3 ausgeduennt). Damit rechnet er die
 * Fortsetzungsmarke, die Sperren und die Groesse; die Punkte sieht er nicht.
 *
 * BIS WEB 22.7.0 lag daneben der Altbestand im Klartext: Zeilen in
 * `track_points` und Blobs im Format SPUR1 in `track_blobs` (Web 10.0.0 bis
 * 22). Das Einmalwerkzeug hat ihn in Stuecke umgezogen; die Migration
 * `2026_10_19_klartext_weg` hat beide Tabellen geworfen, und mit ihnen sind
 * Kodieren, Dekodieren, Verdichten und das Lesen ueber zwei Speicher hinweg
 * gegangen. Alte Sicherungen mit SPUR1 gibt es nicht mehr (E-S11-85).
 *
 * WAS BLEIBT, OBWOHL ES KEINE PUNKTE MEHR SIEHT: die Ausduennung nach
 * Douglas-Peucker. Sie laeuft im Browser (`assets/wartungslauf.js`); diese
 * PHP-Fassung ist die REFERENZ, gegen die die Wartungslaufprobe die
 * Browserfassung Punkt fuer Punkt vergleicht, und die Spurprobe misst sie.
 */

require_once __DIR__ . '/validate_lib.php';

/* ---- Toleranzen ---------------------------------------------------------- */


/**
 * Toleranzen der Ausduennung (E-S2-05). Waagerecht und senkrecht GETRENNT:
 * Eine rein zweidimensionale Ausduennung ebnet das Hoehenprofil eines Fluges
 * ein — der Punkt auf halber Steigung liegt in der Draufsicht genau auf der
 * Verbindungslinie und faellt weg.
 */
const SPUR_TOL_WAAGERECHT_M = 2.0;
const SPUR_TOL_SENKRECHT_M  = 3.0;

/* ---- Ausduennen (Stufe 2 -> 3) ------------------------------------------- */

/**
 * Deckel der Abschnittslaenge im Douglas-Peucker.
 *
 * WARUM ES IHN GIBT. Douglas-Peucker ist im schlechtesten Fall QUADRATISCH,
 * und der schlechteste Fall ist nicht konstruiert: Die Uhr nimmt einen Punkt
 * auf, sobald 15 m ODER 10 s vergangen sind (`watch/source/Track.mc`). Ein
 * laengerer Schwebeflug mit GPS-Rauschen ueber 2 m ergibt genau den Zickzack,
 * in dem kein Punkt verworfen werden darf. Gemessen in PHP fuer eine einzige
 * solche Spur:
 *
 *      2 000 Punkte    0,198 s
 *      5 000 Punkte    1,219 s
 *     10 000 Punkte    4,340 s
 *     20 000 Punkte   18,658 s
 *     50 000 Punkte  114,500 s      <- LIMIT_TRACKPUNKTE_SPUR
 *
 * 114 Sekunden fuer eine Spur, bei Haeppchenbudgets von 3 / 20 / 300 s und
 * einer Z3-Grenze von 30 s je Serveranfrage. Auf dem Token-Weg liefe das in
 * `max_execution_time` — und ein Zeitablauf ist KEIN `Throwable`: Der
 * `catch` in `jobs_lib.php` faengt ihn nicht, die Sperre bleibt stehen, und
 * der Job ist eine Stunde tot. Dann stirbt er wieder.
 *
 * Mit einer zusaetzlichen Abschnittsgrenze alle 1000 Punkte sinkt derselbe
 * Fall auf 2,40 s. Das ist zulaessig, weil zusaetzliche Abschnittsgrenzen nur
 * zusaetzliche behaltene Punkte erzeugen koennen — die Zusage wird nie
 * schwaecher, hoechstens die Ersparnis kleiner. Und am Normalfall kostet es
 * nichts: Eine glatte Spur mit 50 000 Punkten braucht MIT Deckel 0,031 s und
 * behaelt 786 Punkte, OHNE Deckel 0,161 s und 816 — der Deckel ist dort
 * schneller und behaelt weniger. Am Referenzbestand greift er gar nicht, der
 * laengste Abschnitt hat 804 Punkte.
 */
const SPUR_DP_ABSCHNITT_MAX = 1000;

/**
 * Punktvergleiche je Sekunde, gemessen (10,9 Mio.; hier bewusst niedriger
 * angesetzt). Grundlage von `spur_ausduenn_dauer_s()`.
 */
const SPUR_DP_VERGLEICHE_JE_S = 10000000;


/**
 * Der oertliche Meterrahmen einer Spur.
 *
 * Grad in Meter, EINMAL je Spur statt je Punktpaar. Haversine fuer jeden der
 * bis zu 50 000 Punkte waere genauer und um Groessenordnungen teurer; gemessen
 * weicht diese Naeherung ueber 52 232 Referenz-Punktabstaende im Mittel um
 * 0,116 % ab, im schlimmsten Einzelfall um 1,16 %. Bei 2 m Toleranz sind das
 * hoechstens 2,3 cm — weit unter der Aufloesung des Formats (0,11 m).
 *
 * @return array{0:float,1:float} [Meter je Grad Breite, Meter je Grad Laenge]
 */
function spur_ortsrahmen(array $punkte): array
{
    $lat0 = $punkte ? (float)$punkte[0][1] : 0.0;
    return [111132.95, 111319.49 * cos(deg2rad($lat0))];
}

/**
 * Die Bezugshoehen einer Spur — je Index eine, auch wo keine gemessen wurde.
 *
 * WARUM DAS NOETIG IST. Die Hoehe darf fehlen (Bitfeld im Format). Die
 * naheliegende Regel „fehlt einem Sehnenende die Hoehe, entfaellt der
 * Hoehentest fuer diesen Abschnitt" ist eine FALLE: Ein einzelner hoehenloser
 * Punkt an einer waagerechten Ecke wird zum Teilungspunkt — und damit zum
 * Sehnenende BEIDER Teilstuecke. Danach ist der Hoehentest dort tot.
 *
 * Im Prueffall (waagerechte Kante bei Index 200 ohne Hoehe, eine Hoehenspitze
 * von 150 m bei Index 300) behaelt diese Regel drei Punkte und verliert
 * 150,0 m Hoehe; mit Ankerreihe sind es 16 Punkte und 2,5 m. Und das
 * Perfide: Eine Pruefung, die Abschnitte mit hoehenlosem Ende ueberspringt,
 * weil sie dort „nicht bewerten kann", meldet dafuer 0,0 m Verlust. Der
 * Fehler versteckt sich in genau der Luecke, die ihn erzeugt.
 *
 * Deshalb hier: Luecken werden ueber die ZEIT zwischen den naechsten
 * gemessenen Nachbarn linear gefuellt, die Raender konstant fortgesetzt.
 * Traegt die Spur ueberhaupt keine Hoehe, bleibt die Reihe leer und der
 * Hoehentest entfaellt vollstaendig — sonst bliebe eine Spur ohne Barometer
 * zu 100 % stehen.
 *
 * Die dritte denkbare Regel — jeden WECHSEL der Hoehenverfuegbarkeit zur
 * Abschnittsgrenze machen — ist zwar richtig, aber bei sporadischen Luecken
 * ruinoes: bei „jeder siebte Punkt ohne Hoehe" erzwingt sie 859 von 3000
 * Punkten (28,6 %) statt 2,8 %.
 *
 * @return list<float> leer, wenn die Spur gar keine Hoehe traegt
 */
function spur_hoehenanker(array $punkte): array
{
    $n = count($punkte);
    $anker = [];
    $bekannt = [];               // Indizes mit gemessener Hoehe
    for ($i = 0; $i < $n; $i++) {
        if ($punkte[$i][3] !== null) { $bekannt[] = $i; }
    }
    if (!$bekannt) { return []; }

    $j = 0;                      // Zeiger in $bekannt
    for ($i = 0; $i < $n; $i++) {
        if ($punkte[$i][3] !== null) { $anker[$i] = (float)$punkte[$i][3]; continue; }
        while ($j < count($bekannt) - 1 && $bekannt[$j] < $i) { $j++; }
        $rechts = $bekannt[$j];
        $links  = null;
        for ($k = $j; $k >= 0; $k--) { if ($bekannt[$k] < $i) { $links = $bekannt[$k]; break; } }
        if ($links === null)          { $anker[$i] = (float)$punkte[$rechts][3]; continue; }
        if ($rechts <= $i)            { $anker[$i] = (float)$punkte[$links][3];  continue; }
        $tl = (int)$punkte[$links][4];  $tr = (int)$punkte[$rechts][4];
        $t  = ($tr > $tl) ? ((int)$punkte[$i][4] - $tl) / ($tr - $tl) : 0.0;
        $anker[$i] = (float)$punkte[$links][3]
                   + $t * ((float)$punkte[$rechts][3] - (float)$punkte[$links][3]);
    }
    return $anker;
}

/**
 * Wie weit liegt Punkt $k von der Sehne $i-$j — waagerecht und senkrecht.
 *
 * VERBUNDEN WERDEN DIE BEIDEN TOLERANZEN ALS MAXIMUM DER NORMIERTEN WERTE:
 *
 *      s = max( waagerecht / 2 m , senkrecht / 3 m ),   behalten wenn s > 1
 *
 * Das ist genau „beide Toleranzen eingehalten" (s <= 1 gilt dann und nur
 * dann, wenn waagerecht <= 2 m UND senkrecht <= 3 m) — und liefert zugleich
 * die EINE Zahl, die Douglas-Peucker fuer die Wahl des Teilungspunkts
 * braucht.
 *
 * DIE NAHELIEGENDE ALTERNATIVE IST FALSCH: zwei getrennte Laeufe (einer
 * waagerecht, einer senkrecht) und die Behaltelisten vereinigen. Die
 * Vereinigung erzeugt einen DRITTEN Streckenzug, fuer den keiner der beiden
 * Laeufe etwas zugesagt hat. Am Referenzbestand gemessen: 8,59 m waagerechte
 * und 31,97 m senkrechte Abweichung verworfener Punkte, bei zugesagten 2 und
 * 3. Sie behaelt dabei sogar MEHR Punkte (39,44 % gegen 38,32 %), sieht also
 * nach der sicheren Wahl aus und ist die unsicherste.
 *
 * @return array{0:float,1:float} [waagerecht in m, senkrecht in m]
 */
function spur_dp_abstand(array $punkte, array $anker, int $i, int $j, int $k,
                         float $ky, float $kx): array
{
    $bx = ((float)$punkte[$j][2] - (float)$punkte[$i][2]) * $kx;
    $by = ((float)$punkte[$j][1] - (float)$punkte[$i][1]) * $ky;
    $px = ((float)$punkte[$k][2] - (float)$punkte[$i][2]) * $kx;
    $py = ((float)$punkte[$k][1] - (float)$punkte[$i][1]) * $ky;

    /* Fusspunkt auf [0,1] GEKLEMMT — gemessen wird gegen die Strecke, nicht
     * gegen die unendliche Gerade. Ohne das Klemmen liesse eine Kehre den
     * Abstand kleiner erscheinen, als er ist. */
    $l2 = $bx * $bx + $by * $by;
    $t  = $l2 > 0.0 ? max(0.0, min(1.0, ($px * $bx + $py * $by) / $l2)) : 0.0;
    $dx = $px - $t * $bx;
    $dy = $py - $t * $by;
    $waag = sqrt($dx * $dx + $dy * $dy);

    $senk = 0.0;
    if ($anker && $punkte[$k][3] !== null) {
        $sehne = $anker[$i] + $t * ($anker[$j] - $anker[$i]);
        $senk  = abs((float)$punkte[$k][3] - $sehne);
    }
    return [$waag, $senk];
}

/**
 * Die Pflichtpunkte einer Spur (E-S2-05): erster, letzter, und je
 * Schutzzeitpunkt der zeitnaechste Punkt.
 *
 * DIE WAHL BEI GLEICHSTAND IST NICHT GESCHMACK. Die Hoehenregel
 * (`EdOrtsdaten.ortshoehe()`, bis Web 22.7.0 `compute_site_elevation()` in
 * `site_elevation_lib.php`) sucht den zeitnaechsten Punkt mit `<` und behaelt
 * damit den FRUEHEREN. Waehlte die Behalteliste den spaeteren, fiele der
 * eigentliche Gewinner weg — und das naechste Speichern im Formular schriebe
 * eine andere oder gar keine Hoehe, ohne Meldung. Am Referenzbestand faellt
 * das nicht auf: 576 Phasenzeitpunkte, 0 Gleichstaende.
 *
 * @param list<int> $zeiten Epochensekunden; fuer Ruhesegmente leer
 * @return list<int> aufsteigende Indizes in $punkte
 */
function spur_schutzpunkte(array $punkte, array $zeiten): array
{
    $n = count($punkte);
    if ($n === 0) { return []; }
    $pflicht = [0 => true, $n - 1 => true];

    foreach ($zeiten as $ts) {
        $ts = (int)$ts;
        $best = 0; $bestD = PHP_INT_MAX;
        for ($i = 0; $i < $n; $i++) {
            $d = abs((int)$punkte[$i][4] - $ts);
            if ($d < $bestD) { $bestD = $d; $best = $i; }   // `<`, nicht `<=`
        }
        $pflicht[$best] = true;
    }
    $aus = array_keys($pflicht);
    sort($aus);
    return $aus;
}

/**
 * Die Behalteliste einer Spur — Douglas-Peucker dreidimensional (E-S2-05).
 *
 * ABSCHNITTSWEISE, und das ist keine Feinheit. Die naheliegende Reihenfolge —
 * global ausduennen, dann die Pflichtpunkte hinterher einfuegen — bricht die
 * Zusage: Douglas-Peucker sichert Naehe zu DEM Streckenzug zu, den es selbst
 * gebaut hat; ein nachtraeglich eingefuegter Punkt knickt den Weg zu sich
 * hin, und ein Punkt, der 1,9 m auf der anderen Seite der Sehne lag und
 * deshalb wegfiel, liegt danach fast das Doppelte entfernt. Gemessen: 41 von
 * 171 Referenzspuren bekommen ueberhaupt einen Punkt nachtraeglich
 * eingefuegt, 9 davon verletzen danach die Zusage — schlimmstenfalls mit
 * 9,71 m waagerecht und 4,16 m senkrecht. Abschnittsweise: 0 Verletzungen,
 * und es kostet 56 Punkte auf 52 484 (38,32 % statt 38,22 %).
 *
 * ITERATIV, nicht rekursiv, und dabei immer die GROESSERE Haelfte auf den
 * Stapel. Begruendung: siehe SPUR_DP_ABSCHNITT_MAX oben — ein rekursiver Lauf
 * ueber 50 000 Punkte kostet 38 MB VM-Stapel (797 Byte je Rahmen, gemessen),
 * und der Abbruch waere ein nicht fangbarer Fatal. Mit „groessere Haelfte auf
 * den Stapel" ist jedes fortgesetzte Teilstueck hoechstens halb so lang wie
 * das vorige; der Stapel hat nie mehr als ceil(log2 n) Eintraege — 16 bei
 * 50 000 Punkten statt 50 000.
 *
 * @param list<int> $zeiten Schutzzeitpunkte (Epochensekunden)
 * @return list<int> aufsteigende Indizes der behaltenen Punkte
 */
function spur_ausduennen(array $punkte, array $zeiten = [],
                         float $tolW = SPUR_TOL_WAAGERECHT_M,
                         float $tolS = SPUR_TOL_SENKRECHT_M,
                         int $abschnittMax = SPUR_DP_ABSCHNITT_MAX): array
{
    $n = count($punkte);
    if ($n <= 2) { return range(0, max(0, $n - 1)); }

    [$ky, $kx] = spur_ortsrahmen($punkte);
    $anker = spur_hoehenanker($punkte);

    $behalten = [];
    foreach (spur_schutzpunkte($punkte, $zeiten) as $i) { $behalten[$i] = true; }

    /* Der Deckel als zusaetzliche Grenze. Er steht VOR dem Lauf, damit die
     * Abschnitte, die der Lauf sieht, schon gedeckelt sind. */
    if ($abschnittMax > 0) {
        $grenzen = array_keys($behalten);
        sort($grenzen);
        for ($g = 0; $g < count($grenzen) - 1; $g++) {
            $von = $grenzen[$g]; $bis = $grenzen[$g + 1];
            for ($m = $von + $abschnittMax; $m < $bis; $m += $abschnittMax) {
                $behalten[$m] = true;
            }
        }
    }

    $grenzen = array_keys($behalten);
    sort($grenzen);

    for ($g = 0; $g < count($grenzen) - 1; $g++) {
        $stapel = [[$grenzen[$g], $grenzen[$g + 1]]];
        while ($stapel) {
            [$i, $j] = array_pop($stapel);
            if ($j <= $i + 1) { continue; }
            $maxS = 0.0; $maxK = -1;
            for ($k = $i + 1; $k < $j; $k++) {
                [$w, $s] = spur_dp_abstand($punkte, $anker, $i, $j, $k, $ky, $kx);
                $rel = max($w / $tolW, $s / $tolS);
                if ($rel > $maxS) { $maxS = $rel; $maxK = $k; }
            }
            if ($maxS <= 1.0 || $maxK < 0) { continue; }
            $behalten[$maxK] = true;
            // GROESSERE Haelfte auf den Stapel, mit der kleineren weiter.
            $links = [$i, $maxK]; $rechts = [$maxK, $j];
            if (($maxK - $i) >= ($j - $maxK)) { $stapel[] = $links;  $stapel[] = $rechts; }
            else                              { $stapel[] = $rechts; $stapel[] = $links;  }
        }
    }

    $aus = array_keys($behalten);
    sort($aus);
    return $aus;
}

/**
 * Obere Schranke der Rechenzeit — damit ein Haeppchen VORHER weiss, ob es
 * eine Spur noch schafft.
 *
 * Mit Deckel ist die Arbeit durch n * Deckel / 2 Punktvergleiche begrenzt.
 * Vorhersage fuer 50 000 Punkte bei Deckel 1000: 2,29 s; gemessen 2,40 s —
 * 5 % daneben, und in die sichere Richtung waere zu wenig. Deshalb ist
 * SPUR_DP_VERGLEICHE_JE_S bewusst unter dem gemessenen Wert angesetzt.
 */
function spur_ausduenn_dauer_s(int $n, int $abschnittMax = SPUR_DP_ABSCHNITT_MAX): float
{
    if ($n < 3) { return 0.0; }
    $deckel = $abschnittMax > 0 ? min($abschnittMax, $n) : $n;
    return ($n * $deckel / 2.0) / SPUR_DP_VERGLEICHE_JE_S;
}

/**
 * Die Rundlaufpruefung der Stufe 3 (E-S2-07) — und sie ist eine ANDERE.
 *
 * Ein blosser Rundlauf (auspacken, vergleichen) ist hier WERTLOS: Die
 * Behalteliste stammt aus der Spur selbst, der Vergleich geht IMMER auf. Er
 * waere gruen, auch wenn die Ausduennung die halbe Spur an der falschen
 * Stelle wegwirft — und sie ist die letzte Instanz vor dem Ersetzen der
 * Stuecke, nach dem das Original weg ist. (Bis Web 22 stand hier der
 * Vergleich mit der SPUR1-Rundlaufpruefung.)
 *
 * Die Zusage der Stufe 3 lautet deshalb, in fuenf Teilen:
 *
 *   1. NICHTS ERFUNDEN. Jeder behaltene Punkt ist wertgleich mit einem Punkt
 *      der Eingabe an genau diesem Index. Die Ausduennung verschiebt und
 *      mittelt nicht.
 *   2. REIHENFOLGE UND ZEIT BLEIBEN. Indizes streng aufsteigend, Zeitstempel
 *      nicht fallend.
 *   3. DIE RAENDER BLEIBEN. Index 0 und n-1 sind enthalten — die Ringe
 *      „Start/Ende der Aufzeichnung" in der Einsatzansicht haengen daran.
 *   4. DIE ZEITANKER BLEIBEN. Zu jedem Schutzzeitpunkt ist der Index
 *      enthalten, den die Verbraucher waehlen wuerden.
 *   5. DIE GENAUIGKEIT IST EINGEHALTEN. Fuer JEDEN verworfenen Punkt gilt
 *      gegen den ENDGUELTIGEN Streckenzug — unabhaengig nachgemessen, nicht
 *      aus der Buchfuehrung der Rekursion uebernommen — waagerecht <= 2 m und
 *      senkrecht <= 3 m.
 *
 * Punkt 5 ist der Kern. Er kostet O(n) mit einem mitwandernden Segmentzeiger
 * und haette jede der 9 Zusageverletzungen des „global plus einfuegen"-Wegs
 * gefangen.
 *
 * @return string|null null = in Ordnung, sonst die erste Abweichung
 */
function spur_ausduennung_pruefen(array $punkte, array $behalten, array $zeiten,
                                  float $tolW = SPUR_TOL_WAAGERECHT_M,
                                  float $tolS = SPUR_TOL_SENKRECHT_M): ?string
{
    $n = count($punkte);
    $m = count($behalten);
    if ($m === 0)  { return 'Behalteliste ist leer'; }
    if ($m > $n)   { return "Behalteliste ist laenger als die Spur ($m > $n)"; }

    // (1)+(2) Teilmenge, aufsteigend, Zeit nicht fallend
    $vorher = -1;
    foreach ($behalten as $pos => $i) {
        if (!is_int($i) || $i < 0 || $i >= $n) { return "Index $i liegt ausserhalb der Spur"; }
        if ($i <= $vorher) { return "Indizes nicht streng aufsteigend bei Position $pos"; }
        if ($vorher >= 0 && (int)$punkte[$i][4] < (int)$punkte[$vorher][4]) {
            return "Zeit faellt zwischen Index $vorher und $i";
        }
        $vorher = $i;
    }

    // (3) Raender
    if ($behalten[0] !== 0)          { return 'Der erste Punkt fehlt'; }
    if ($behalten[$m - 1] !== $n - 1) { return 'Der letzte Punkt fehlt'; }

    // (4) Zeitanker
    $dabei = array_flip($behalten);
    foreach (spur_schutzpunkte($punkte, $zeiten) as $i) {
        if (!isset($dabei[$i])) { return "Schutzpunkt $i fehlt in der Behalteliste"; }
    }

    // (5) Genauigkeit gegen den ENDGUELTIGEN Streckenzug
    [$ky, $kx] = spur_ortsrahmen($punkte);
    $anker = spur_hoehenanker($punkte);
    $seg = 0;
    for ($k = 0; $k < $n; $k++) {
        if (isset($dabei[$k])) { continue; }
        while ($seg < $m - 2 && $behalten[$seg + 1] < $k) { $seg++; }
        $i = $behalten[$seg]; $j = $behalten[$seg + 1];
        [$w, $s] = spur_dp_abstand($punkte, $anker, $i, $j, $k, $ky, $kx);
        if ($w > $tolW + 1e-6) {
            return sprintf('Punkt %d liegt %.3f m waagerecht von der Strecke '
                         . '%d-%d (zugesagt %.1f)', $k, $w, $i, $j, $tolW);
        }
        if ($s > $tolS + 1e-6) {
            return sprintf('Punkt %d liegt %.3f m senkrecht von der Strecke '
                         . '%d-%d (zugesagt %.1f)', $k, $s, $i, $j, $tolS);
        }
    }
    return null;
}

/* ---- Zaehlen und Stand ----------------------------------------------------- */

/**
 * Punktzahl je Eigentuemer, OHNE die Punkte zu lesen — aus der Klartextzahl
 * `punkte` der Stuecke.
 *
 * Ueberlappen zwei Stuecke (ein Geraet, das seine Marke verloren hat), zaehlt
 * die Ueberlappung doppelt; der Browser entdoppelt beim Oeffnen. Fuer die
 * Fragen, die hier gestellt werden — gibt es eine Spur, wie gross ist sie
 * ungefaehr —, ist das ohne Belang.
 *
 * @return array<int,int>
 */
function spur_zahlen(PDO $pdo, string $ownerType, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return []; }
    require_once __DIR__ . '/db.php';

    $aus = [];
    foreach (sql_in_bloecken($pdo,
        'SELECT owner_id, SUM(punkte) n FROM track_stuecke
          WHERE owner_type = ? AND owner_id IN ({IDS})
          GROUP BY owner_id', $ids, [$ownerType]) as $r) {
        $aus[(int)$r['owner_id']] = (int)$r['n'];
    }
    return $aus;
}

/**
 * Die Fortsetzungsmarke fuer das Geraet (E-S2-08, JSON-Vertrag): die
 * naechste Sequenznummer, die es senden soll. Sie faellt nie zurueck — auch
 * nicht, wenn ein Stueck zusammengefuegt oder ausgeduennt wurde, denn dann
 * traegt es `n_original`.
 */
function spur_naechste_seq(PDO $pdo, string $ownerType, int $ownerId): int
{
    return spur2_stand($pdo, $ownerType, $ownerId)['naechste_seq'];
}

/**
 * Der Zustand einer Spur in einem Blick — Stufe, Zahlen, Fortsetzungsmarke.
 *
 * `ingest.php` muss wissen, ob eine Spur AUSGEDUENNT ist (E-S2-08: dann
 * werden Punkte verworfen und quittiert, statt angenommen). `stufe` ist 1,
 * wenn es noch kein Stueck gibt — damit muss der Aufrufer „keine Spur" nicht
 * von „Stufe 1" unterscheiden.
 *
 * @return array{stufe:int,n_original:int,naechste_seq:int}
 */
function spur_stand(PDO $pdo, string $ownerType, int $ownerId): array
{
    $s2 = spur2_stand($pdo, $ownerType, $ownerId);
    return ['stufe' => max(1, $s2['stufe']), 'n_original' => $s2['n_original'],
            'naechste_seq' => $s2['naechste_seq']];
}

/**
 * Ist die Spur ausgeduennt?
 *
 * DAS KRITERIUM IST DIE STUFE, NICHT `n_gespeichert < n_original`. Eine kurze
 * Spur kann die Ausduennung ohne einen einzigen Verlust ueberstehen — jeder
 * Punkt liegt innerhalb 2 m/3 m —, dann sind die beiden Zahlen gleich, obwohl
 * die Spur Stufe 3 ist. Wer ueber die Zahlen entscheidet, haelt eine solche
 * Spur fuer nicht ausgeduennt und legt ihre Nachzuegler als Zeilen hinter
 * einen Stufe-3-Blob. Die Stufe ist eine Aussage, die Zahlen sind eine Folge.
 */
function spur_ist_ausgeduennt(array $stand): bool
{
    return ($stand['stufe'] ?? 0) === SPUR2_STUFE_DUENN;
}

/**
 * Die Schutzzeitpunkte einer Spur (E-S2-05) aus der Datenbank.
 *
 * Fuer Ruhesegmente immer leer: Sie haben keine Phasen (`rest_segments` traegt
 * sie nicht), dort bleiben nur erster und letzter Punkt.
 *
 * @return list<int> Epochensekunden
 */
function spur_schutzzeiten(PDO $pdo, string $ownerType, int $ownerId): array
{
    if ($ownerType !== 'mission') { return []; }
    $q = $pdo->prepare('SELECT UNIX_TIMESTAMP(occurred_at) FROM mission_phases
                         WHERE mission_id = ? ORDER BY occurred_at');
    $q->execute([$ownerId]);
    return array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
}

/* ---- Groesse und Loeschen -------------------------------------------------- */

/**
 * WIE VIELE BYTE DIE GPS-DATEN DIESER AUFZEICHNUNGEN BELEGEN (P5b/AP6,
 * E-P5b-18): die Huellen, wie sie liegen. `LENGTH()` ist genau. Bis Web 22
 * stand hier eine Schaetzung fuer die Zeilen in `track_points` daneben.
 *
 * @return int Byte
 */
function spur_bytes(PDO $pdo, string $ownerType, array $ids): int
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return 0; }
    require_once __DIR__ . '/db.php';

    $summe = 0;
    foreach (sql_in_bloecken($pdo,
        'SELECT COALESCE(SUM(LENGTH(huelle)), 0) AS b FROM track_stuecke
          WHERE owner_type = ? AND owner_id IN ({IDS})', $ids, [$ownerType]) as $r) {
        $summe += (int)$r['b'];
    }
    return $summe;
}

/**
 * Eine Spur restlos entfernen.
 *
 * DIE EINZIGE STELLE, an der eine Spur geloescht wird (E-S2-18). Jeder
 * Loeschweg der Anwendung ruft sie; keiner loescht selbst. `track_stuecke`
 * haengt an keinem Fremdschluessel — die Tabelle ist polymorph, und MySQL
 * kennt keine bedingten Fremdschluessel. Ein Loeschweg, der das vergisst,
 * laesst Positionsdaten liegen; genau das ist bei der Kontoloeschung einmal
 * jahrelang passiert (F-S2-B).
 *
 * @param list<int> $ids
 * @return array{stuecke:int} die Zahl der geloeschten Stuecke
 */
function spur_loeschen(PDO $pdo, string $ownerType, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return ['stuecke' => 0]; }
    require_once __DIR__ . '/db.php';

    /* HIER NICHT sql_in_bloecken(): Sie ist zum LESEN gebaut und gibt Zeilen
     * zurueck, keine Zahl betroffener Saetze. Die Blockung wird deshalb von
     * Hand gemacht — mit derselben Blockgroesse. */
    $stuecke = 0;
    foreach (array_chunk($ids, 1000) as $block) {
        $platz = implode(',', array_fill(0, count($block), '?'));
        $q = $pdo->prepare("DELETE FROM track_stuecke
                             WHERE owner_type = ? AND owner_id IN ($platz)");
        $q->execute(array_merge([$ownerType], $block));
        $stuecke += $q->rowCount();
    }
    return ['stuecke' => $stuecke];
}

/* ---- Umdatieren ---------------------------------------------------------- */

/**
 * Der frueheste Zeitstempel einer Menge von Spuren — aus `ts_von`, dem
 * Klartextbeginn jedes Stuecks.
 *
 * Gebraucht von der Umdatierung eines Diensttags: `ts_von` ist
 * VORZEICHENLOS, eine Rueckdatierung unter den 1.1.1970 waere ein
 * Datenbankfehler mitten in der Transaktion.
 */
function spur_min_ts(PDO $pdo, string $ownerType, array $ids): ?int
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return null; }
    require_once __DIR__ . '/db.php';

    $min = null;
    foreach (sql_in_bloecken($pdo,
        'SELECT MIN(ts_von) AS m FROM track_stuecke
          WHERE owner_type = ? AND owner_id IN ({IDS})', $ids, [$ownerType]) as $r) {
        if ($r['m'] !== null) { $min = $min === null ? (int)$r['m'] : min($min, (int)$r['m']); }
    }
    return $min;
}

/**
 * Alle Zeitstempel einer Spur um `$delta` Sekunden verschieben.
 *
 * Die Punkte eines Stuecks stehen RELATIV zu `ts_von` (E-S11-18); es genuegt
 * also, `ts_von` und `ts_bis` zu verschieben. Die Huelle bleibt, wie sie ist,
 * und geht weiter auf, weil ihre Bindung die Zeiten nicht einschliesst.
 */
function spur_zeit_verschieben(PDO $pdo, string $ownerType, array $ids, int $delta): void
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids || $delta === 0) { return; }
    require_once __DIR__ . '/db.php';

    foreach (array_chunk($ids, 1000) as $block) {
        $platz = implode(',', array_fill(0, count($block), '?'));
        $pdo->prepare("UPDATE track_stuecke SET ts_von = ts_von + ?, ts_bis = ts_bis + ?
                        WHERE owner_type = ? AND owner_id IN ($platz)")
            ->execute(array_merge([$delta, $delta, $ownerType], $block));
    }
}

/* ---------------------------------------------------------------------------
 * DIE SPERRVERMERKE DES SCHNITTS (S4/A2, E-S4-53; seit S11-05c E-S11-37, -47)
 *
 * Aus einem Ruhesegment wird ein Einsatz geschnitten: Der Browser oeffnet das
 * Segment, nimmt die Punkte im Zeitraum und verschliesst sie als Spur des
 * neuen Einsatzes. Das Segment bleibt, wie es ist; sein Zeitraum wird hier
 * gesperrt, und beim Oeffnen fallen die Punkte darin weg. Endgueltig fallen
 * sie, wenn der Wartungslauf das Segment neu packt.
 *
 * WARUM EIN ZEITRAUM UND NICHT DIE FORTSETZUNGSMARKE: Punkte, die beim
 * Schnitt noch im Puffer des Geraets liegen (Funkloch), kommen mit Nummern
 * OBERHALB jeder Marke an. Was sie kenntlich macht, ist ihre Zeit — `ts_von`
 * und `ts_bis` jedes Stuecks sind Klartext. `ingest.php` fragt die Sperren
 * einmal je Upload ab (`schnitte_lesen()`).
 *
 * Ein Vermerk ohne Ziel (`mission_id` NULL) ist ein GELOESCHTES Stueck
 * (S11-05c, Nr. 161): Er haelt eine erneute Lieferung desselben Zeitraums
 * auf.
 *
 * WIE track_stuecke GEHOERT AUCH track_cuts HINTER DIESE DATEI (CLAUDE.md 4):
 * Wer die Tabelle unmittelbar liest, bekommt frueher oder spaeter eine halbe
 * Auskunft — etwa, indem er den Vermerk zum Ziel loescht, aber nicht den zur
 * Quelle.
 *
 * Bis Web 22.1.0 verschob der Server die Klartextpunkte selbst
 * (`spur_teilen()`); mit dem Klartext ist die Funktion gegangen (S11-12).
 * ------------------------------------------------------------------------ */

/**
 * Einen Schnitt vermerken. Gibt die Nummer des Vermerks zurueck.
 *
 * OHNE EIGENE TRANSAKTION, mit Absicht: Der Aufrufer schneidet, legt den
 * Einsatz an und vermerkt — das gehoert in EINE Transaktion, und die kann nur
 * er aufmachen; hier waere sie verschachtelt, und PDO kennt das nicht.
 *
 * DER ZEITPUNKT IST SEIT WEB 14.2.0 UEBERGEBBAR (R64, E-R64-12), und das ist
 * eine Zusage und keine Bequemlichkeit: Ein Vermerk ist ein EREIGNIS der
 * Vergangenheit — er sagt, wann geschnitten wurde. Kommt er aus einer
 * Sicherung zurueck, gehoert dort der urspruengliche Zeitpunkt hin und nicht
 * der des Einspielens. Das ist der Unterschied zu `deleted_at`, das die Frist
 * DIESER Installation traegt und deshalb neu entsteht.
 *
 * Der Vorgabewert `null` haelt alle bestehenden Aufrufer unveraendert: Wer
 * jetzt schneidet, bekommt `UTC_TIMESTAMP()` wie bisher. Der Wert wird NICHT
 * geprueft — das ist Sache der Pruefschicht beim Aufrufer, weil nur der weiss,
 * ob er aus einer Datei kommt (dann `pruef_utc_oder_sql()`) oder aus dem
 * eigenen Programm (dann nicht).
 *
 * `$missionId` NULL (seit S11-05c): ein GELOESCHTER Zeitraum (Nr. 161) — die
 * Sperre gegen eine erneute Lieferung ohne Einsatz dahinter.
 *
 * @param string|null $erstelltAm UTC `Y-m-d H:i:s`, oder null fuer „jetzt"
 */
function schnitt_vermerken(PDO $pdo, int $userId, string $quelleTyp, int $quelleId,
                           ?int $missionId, int $vonTs, int $bisTs, int $nPunkte,
                           ?string $erstelltAm = null): int
{
    $wann = $erstelltAm === null ? 'UTC_TIMESTAMP()' : '?';
    $werte = [$userId, $quelleTyp, $quelleId, $missionId, $vonTs, $bisTs, $nPunkte];
    if ($erstelltAm !== null) { $werte[] = $erstelltAm; }
    $pdo->prepare("INSERT INTO track_cuts
                     (user_id, owner_type, owner_id, mission_id,
                      von_ts, bis_ts, n_punkte, erstellt_am)
                   VALUES (?,?,?,?,?,?,?,$wann)")
        ->execute($werte);
    return (int)$pdo->lastInsertId();
}

/**
 * Die Sperrvermerke zu VIELEN Einsaetzen auf einmal — fuer die Sicherung
 * (R64 / Backlog Nr. 63, Web 14.2.0).
 *
 * WARUM ES DIESE FUNKTION GEBEN MUSS. `schnitte_zum_einsatz()` arbeitet auf
 * genau einem Ziel. Die Sicherung baut aber in Fenstern zu
 * `EDBAK_FENSTER` = 500 Einsaetzen (backup_lib.php) — je Fenster waeren das
 * 500 Einzelabfragen. Genau dieses N+1 hat M5-12 im Backup abgeschafft; es
 * hier wieder einzufuehren, kostete bei 5000 Einsaetzen 5000 Abfragen je
 * Sicherung. Der Ausweg „dann eben direktes SQL in backup_lib.php" ist
 * versperrt (CLAUDE.md 4) und waere auch der falsche: Genau die Aufloesung
 * unten ist das, was ein zweiter Verbraucher falsch machen wuerde.
 *
 * SIE LOEST DIE QUELLE GLEICH MIT AUF, und deshalb liest sie ausnahmsweise
 * `missions` und `rest_segments`. Eine Datenbankkennung gilt nur in DIESER
 * Datenbank; die Datei muss die Quelle ueber ihre `client_ref` benennen —
 * dieselbe Ueberlegung wie bei `day_refs` und bei `spur_ref`. Wer die
 * Aufloesung dem Aufrufer ueberliesse, haette die Regel „track_cuts nur ueber
 * spur_lib" zwar formal gehalten und den eigentlichen Fehler trotzdem
 * ermoeglicht: einen Vermerk mit einer Kennung, die im Zielkonto etwas
 * anderes bezeichnet.
 *
 * EIN VERMERK OHNE AUFLOESBARE QUELLE WIRD NICHT GELIEFERT. Es kann ihn nicht
 * geben — `schnitte_loeschen_quelle()` raeumt beim Loeschen einer Quelle auf.
 * Taucht doch einer auf, ist er eine Karteileiche; ihn in die Datei zu
 * schreiben hiesse, sie beim Einspielen zwangslaeufig verwerfen zu lassen.
 * Er wird gezaehlt zurueckgemeldet, damit das nicht stumm geschieht.
 *
 * DIE SORTIERUNG IST FEST (`mission_id, id`). `tools/proben/spur/` vergleicht
 * zwei vollstaendige Sicherungen desselben Kontos auf Gleichheit; eine
 * Reihenfolge, die von der Speicherlage abhaengt, machte diese Probe rot,
 * ohne dass jemand die Ursache im Backup vermutete.
 *
 * @param list<int> $missionIds Ziele (geschnittene Einsaetze)
 * @param int       $waisen     ZURUECK: Zahl der Vermerke ohne aufloesbare Quelle
 * @return array<int, list<array{quelle_art:string,quelle_ref:string,von_ts:int,
 *                               bis_ts:int,n_punkte:int,erstellt_am:string}>>
 *         nach Einsatzkennung; Einsaetze ohne Vermerk fehlen
 */
function schnitte_zu_einsaetzen(PDO $pdo, int $userId, array $missionIds,
                                int &$waisen = 0): array
{
    $missionIds = array_values(array_unique(array_map('intval', $missionIds)));
    if (!$missionIds) { return []; }
    require_once __DIR__ . '/db.php';

    /* `user_id` STEHT IN DER BEDINGUNG, obwohl die Kennungen schon aus dem
     * Konto stammen. Es ist derselbe Grundsatz wie in `api/schneiden.php`:
     * Die Kontobindung gehoert in die Abfrage und nicht in eine Pruefung
     * danach — eine Sicherung, die einen fremden Vermerk mitnaehme, gaebe
     * einen Zeitraum preis, in dem sich jemand anderes aufgehalten hat. */
    $roh = [];
    foreach (sql_in_bloecken($pdo,
        'SELECT mission_id, owner_type, owner_id, von_ts, bis_ts, n_punkte, erstellt_am
           FROM track_cuts
          WHERE user_id = ? AND mission_id IN ({IDS})
          ORDER BY mission_id, id', $missionIds, [$userId]) as $r) {
        $roh[] = $r;
    }
    if (!$roh) { return []; }

    /* Die Quellen in EINER Abfrage je Art aufloesen — zwei Abfragen, nicht
     * eine je Vermerk. Dasselbe Muster wie bei den Reanimationsereignissen
     * im Backup: erst die Kennungen einsammeln, dann geschlossen fragen. */
    $refs = ['mission' => [], 'rest' => []];
    foreach ($roh as $r) {
        $art = (string)$r['owner_type'];
        if (isset($refs[$art])) { $refs[$art][(int)$r['owner_id']] = null; }
    }
    foreach (['mission' => 'missions', 'rest' => 'rest_segments'] as $art => $tab) {
        if (!$refs[$art]) { continue; }
        foreach (sql_in_bloecken($pdo,
            "SELECT id, client_ref FROM `$tab` WHERE user_id = ? AND id IN ({IDS})",
            array_keys($refs[$art]), [$userId]) as $q) {
            $refs[$art][(int)$q['id']] = (string)$q['client_ref'];
        }
    }

    $aus = [];
    foreach ($roh as $r) {
        $art = (string)$r['owner_type'];
        $ref = $refs[$art][(int)$r['owner_id']] ?? null;
        if ($ref === null || $ref === '') { $waisen++; continue; }
        $aus[(int)$r['mission_id']][] = [
            'quelle_art'  => $art,
            'quelle_ref'  => $ref,
            'von_ts'      => (int)$r['von_ts'],
            'bis_ts'      => (int)$r['bis_ts'],
            'n_punkte'    => (int)$r['n_punkte'],
            'erstellt_am' => (string)$r['erstellt_am'],
        ];
    }
    return $aus;
}

/**
 * Die LOESCHVERMERKE vieler Quellen auf einmal — fuer die Sicherung (S11-06b,
 * F-S11-52). Ein Loeschvermerk ist ein Sperrvermerk ohne Ziel
 * (`mission_id` NULL, seit S11-05c): Ein Zeitraum wurde aus einer Spur
 * geloescht, und eine erneute Lieferung derselben Punkte soll ihn nicht
 * wieder auffuellen. `schnitte_zu_einsaetzen()` geht ueber das Ziel und sieht
 * ihn deshalb nicht; bis Web 22.4.0 fehlte er in jeder Sicherung.
 *
 * Er reist an seiner QUELLE mit, nicht an einem Einsatz — es gibt keinen.
 * Dieselbe feste Sortierung wie oben (`owner_id, id`).
 *
 * @param list<int> $ids Quellen dieser Art
 * @return array<int, list<array{von_ts:int,bis_ts:int,n_punkte:int,erstellt_am:string}>>
 */
function loeschvermerke_zu_quellen(PDO $pdo, int $userId, string $ownerType, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return []; }
    require_once __DIR__ . '/db.php';
    $aus = [];
    foreach (sql_in_bloecken($pdo,
        'SELECT owner_id, von_ts, bis_ts, n_punkte, erstellt_am FROM track_cuts
          WHERE user_id = ? AND owner_type = ? AND mission_id IS NULL AND owner_id IN ({IDS})
          ORDER BY owner_id, id', $ids, [$userId, $ownerType]) as $r) {
        $aus[(int)$r['owner_id']][] = ['von_ts' => (int)$r['von_ts'], 'bis_ts' => (int)$r['bis_ts'],
                                       'n_punkte' => (int)$r['n_punkte'],
                                       'erstellt_am' => (string)$r['erstellt_am']];
    }
    return $aus;
}

/**
 * Die Zeitfenster der Sperrvermerke einer Quelle mitverschieben
 * (Web 14.2.0, R64).
 *
 * WARUM DAS BIS HIERHER GEFEHLT HAT UND WARUM ES JETZT AUFFAELLT. Die
 * Umdatierung eines Diensttags (`tageszuordnung_lib.php`) verschiebt den Tag,
 * die Einsaetze, die Segmente, die Phasen, die Reanimationsereignisse — und
 * ueber `spur_zeit_verschieben()` auch jeden Spurpunkt. Die Sperrvermerke
 * blieben stehen. Solange sie nur in dieser Datenbank lagen, war das ein
 * Schoenheitsfehler mit Folgen im Verborgenen: Der Vermerk sperrte einen
 * Zeitraum, in dem die Spur nicht mehr liegt, nachgelieferte Punkte kamen
 * wieder durch, und die Fahrt lag in Einsatz UND Segment.
 *
 * Das ist wortgleich der Schaden, gegen den Backlog Nr. 63 antritt — und ab
 * Web 14.2.0 reist das falsche Fenster zusaetzlich in jede Sicherung.
 * Deshalb wird es hier behoben und nicht vertagt.
 *
 * VERSCHOBEN WIRD NACH QUELLE, NICHT NACH ZIEL. `von_ts` und `bis_ts`
 * beschreiben einen Ausschnitt der QUELLSPUR; wer zusaetzlich nach
 * `mission_id` verschoebe, verschoebe jeden Vermerk zweimal, dessen Quelle
 * und Ziel am selben Tag haengen — und das ist der Regelfall.
 *
 * @param list<int> $ids Kennungen der Quellen (Einsaetze oder Segmente)
 * @return int Zahl der verschobenen Vermerke
 */
function schnitte_zeit_verschieben(PDO $pdo, string $ownerType, array $ids,
                                   int $delta): int
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids || $delta === 0) { return 0; }
    $n = 0;
    foreach (array_chunk($ids, 1000) as $block) {
        $platz = implode(',', array_fill(0, count($block), '?'));
        $q = $pdo->prepare("UPDATE track_cuts
                               SET von_ts = von_ts + ?, bis_ts = bis_ts + ?
                             WHERE owner_type = ? AND owner_id IN ($platz)");
        $q->execute(array_merge([$delta, $delta, $ownerType], $block));
        $n += $q->rowCount();
    }
    return $n;
}

/**
 * Die gesperrten Zeitraeume einer Spur — EINE Abfrage, nicht eine je Punkt.
 *
 * Genau darum geht es an der einzigen heissen Stelle, an der sie gebraucht
 * werden (`ingest.php`, Konzept 14, offener Punkt 2): Der Aufrufer holt die
 * Liste einmal vor der Punktschleife und prueft darin. Eine Spur hat
 * ueblicherweise null Vermerke, oft einen, selten mehr als eine Handvoll —
 * die lineare Suche in `schnitt_gesperrt()` ist dann billiger als jeder
 * Index.
 *
 * `mission_id` KOMMT MIT, obwohl `ingest.php` es nicht braucht. Die
 * Tagesansicht braucht es: Sie zeigt an einem Segment, was daraus
 * geschnitten wurde, und der Weg zurueck fuehrt ueber den Einsatz. Eine
 * zweite Funktion fuer dieselbe Zeile mit einer Spalte mehr waere die
 * teurere Loesung — eine Spalte kostet an dieser Stelle nichts.
 *
 * @return list<array{mission_id:int,von_ts:int,bis_ts:int}> aufsteigend nach von_ts
 */
function schnitte_lesen(PDO $pdo, string $ownerType, int $ownerId): array
{
    $q = $pdo->prepare('SELECT mission_id, von_ts, bis_ts FROM track_cuts
                         WHERE owner_type = ? AND owner_id = ?
                         ORDER BY von_ts');
    $q->execute([$ownerType, $ownerId]);
    $aus = [];
    foreach ($q->fetchAll() as $r) {
        /* `mission_id` NULL heisst: geloescht, nicht geschnitten (S11-05c,
         * Nr. 161). Die Sperre gilt genauso; nur gibt es keinen Einsatz, zu
         * dem sie fuehrt, und keine Ruecknahme. */
        $aus[] = ['mission_id' => $r['mission_id'] !== null ? (int)$r['mission_id'] : null,
                  'von_ts' => (int)$r['von_ts'], 'bis_ts' => (int)$r['bis_ts']];
    }
    return $aus;
}

/**
 * Liegt dieser Zeitpunkt in einem gesperrten Bereich?
 *
 * BEIDE GRENZEN GEHOEREN DAZU (`ts >= von && ts <= bis`), wie beim Schnitt
 * im Browser; staende es hier anders, faende der Randpunkt einer Sekunde
 * zurueck, den der Schnitt gerade weggenommen hat.
 *
 * @param list<array{von_ts:int,bis_ts:int}> $schnitte aus schnitte_lesen()
 */
function schnitt_gesperrt(array $schnitte, int $ts): bool
{
    foreach ($schnitte as $s) {
        if ($ts >= $s['von_ts'] && $ts <= $s['bis_ts']) { return true; }
    }
    return false;
}

/**
 * Die Vermerke zu einem geschnittenen Einsatz — fuer das Rueckgaengig.
 *
 * `erstellt_am` braucht das Rueckgaengig seit Web 23 (S11-12): Ein Schnitt
 * von vor Web 22.2.0 hat die Punkte aus dem Segment VERSCHOBEN, einer danach
 * nicht — siehe `api/schneiden.php`.
 *
 * @return list<array{id:int,owner_type:string,owner_id:int,von_ts:int,bis_ts:int,n_punkte:int,erstellt_am:string}>
 */
function schnitte_zum_einsatz(PDO $pdo, int $missionId): array
{
    $q = $pdo->prepare('SELECT id, owner_type, owner_id, von_ts, bis_ts, n_punkte, erstellt_am
                          FROM track_cuts WHERE mission_id = ? ORDER BY id');
    $q->execute([$missionId]);
    $aus = [];
    foreach ($q->fetchAll() as $r) {
        $aus[] = ['id' => (int)$r['id'], 'owner_type' => (string)$r['owner_type'],
                  'owner_id' => (int)$r['owner_id'], 'von_ts' => (int)$r['von_ts'],
                  'bis_ts' => (int)$r['bis_ts'], 'n_punkte' => (int)$r['n_punkte'],
                  'erstellt_am' => (string)$r['erstellt_am']];
    }
    return $aus;
}

/**
 * Vermerke loeschen — nach Nummer, nach Ziel oder nach Konto.
 *
 * DREI WEGE, WEIL ES DREI ANLAESSE GIBT und keiner davon die anderen kennt:
 * das Rueckgaengig (nach Ziel), die Loeschung eines Einsatzes oder Segments
 * (nach Quelle) und die Kontoloeschung (nach Konto). Ohne den letzten bliebe
 * hier liegen, was bei den Spurpunkten jahrelang liegengeblieben ist (F-S2-B) —
 * und ein Zeitraum, in dem sich jemand aufgehalten hat, ist ein Ortsdatum.
 *
 * @return int Zahl der geloeschten Vermerke
 */
function schnitte_loeschen(PDO $pdo, string $wonach, array $werte): int
{
    if (!$werte) { return 0; }
    switch ($wonach) {
        case 'id':      $wo = 'id IN (%s)';         break;
        case 'ziel':    $wo = 'mission_id IN (%s)'; break;
        case 'konto':   $wo = 'user_id IN (%s)';    break;
        default:
            throw new InvalidArgumentException("schnitte_loeschen: unbekannt '$wonach'");
    }
    $werte = array_values(array_map('intval', $werte));
    $platz = implode(',', array_fill(0, count($werte), '?'));
    $q = $pdo->prepare('DELETE FROM track_cuts WHERE ' . sprintf($wo, $platz));
    $q->execute($werte);
    return $q->rowCount();
}

/**
 * Vermerke einer Quelle loeschen (Einsatz oder Ruhesegment faellt weg).
 *
 * EIGENE FUNKTION UND KEIN VIERTER FALL OBEN, weil hier zwei Werte
 * zusammengehoeren: `owner_type` UND `owner_id`. In die IN-Liste passt das
 * nicht, ohne dass man Typen mischt — und ein Vermerk, der zum Ruhesegment 7
 * gehoert, darf nicht mit dem Einsatz 7 verschwinden.
 *
 * @param list<int> $ids
 */
function schnitte_loeschen_quelle(PDO $pdo, string $ownerType, array $ids): int
{
    if (!$ids) { return 0; }
    $ids   = array_values(array_unique(array_map('intval', $ids)));
    $platz = implode(',', array_fill(0, count($ids), '?'));
    $q = $pdo->prepare("DELETE FROM track_cuts
                         WHERE owner_type = ? AND owner_id IN ($platz)");
    $q->execute(array_merge([$ownerType], $ids));
    return $q->rowCount();
}

/* ===========================================================================
 * SPUR2 — SPURSTUECKE ALS GERAETEHUELLE (S11-04a, Konzept S11 3.3, E-S11-23)
 * ===========================================================================
 *
 * Seit S11 bringt das Geraet keine Koordinaten mehr, sondern verschlossene
 * Stuecke: hoechstens 500 Punkte, verschlossen mit dem oeffentlichen
 * Schluessel des Kontos (Format: `tools/proben/huelle/referenz.py`). Der
 * Server sieht von einem Stueck nur, wo es steht (`seq_from`, `punkte`) und
 * welchen Zeitraum es abdeckt (`ts_von`, `ts_bis`). Damit rechnet er die
 * Fortsetzungsmarke und die Sperren; die Punkte selbst sieht nur der Browser.
 *
 * DIE REGELN DES EINGANGS, JE STUECK (bis Web 22 galten sie je Punkt):
 *
 *   Wiederholung    dasselbe `seq_from` noch einmal: still uebergehen (der
 *                   Primaerschluessel faengt es ab).
 *   unter der Marke das ganze Stueck unter `n_original` einer zusammengefuegten
 *                   oder ausgeduennten Spur: uebergehen — die Punkte stehen
 *                   schon im zusammengefuegten Stueck. Ragt es darueber, wird
 *                   es abgelegt; die Ueberlappung loest der Browser ueber die
 *                   Sequenznummer auf (Punkt i hat `seq_from` + i).
 *   ausgeduennt     Stufe 3: den Teil oberhalb der Marke verwerfen und zaehlen
 *                   (E-S2-08).
 *   geschnitten     liegt das GANZE Stueck in einem gesperrten Zeitraum,
 *                   verwerfen und zaehlen. Liegt es nur TEILWEISE darin, wird
 *                   es gespeichert, und der Browser blendet die gesperrten
 *                   Punkte beim Oeffnen aus — die Sperrzeiten sind Klartext.
 *                   Endgueltig fallen sie beim Zusammenfuegen (S11-07,
 *                   E-S11-37).
 */

/** Stufe eines Stuecks, wie es vom Geraet kommt. */
const SPUR2_STUFE_GERAET = 1;
/** Stufe eines zusammengefuegten und gepackten Stuecks (S11-07, E-S11-71):
 *  Innenform „B fest", die Punkte ohne eigene Nummer; das Stueck deckt
 *  `seq_from` bis `seq_from + n_original - 1`. */
const SPUR2_STUFE_GEPACKT = 2;
/** Frist der Ausduennung in Tagen nach dem Ende (E-S11-25). */
const SPUR2_AUSDUENN_FRIST_TAGE = 31;
/** Hoechstens so viele Spuren je Anfrage des Wartungslaufs (E-S11-68). */
const SPUR2_WARTUNG_MAX = 20;
/** Stufe eines ausgeduennten Stuecks. Bis S11-07 entsteht sie nur beim
 *  Einspielen einer Sicherung, die eine ausgeduennte Spur traegt (S11-06b):
 *  `n_original` reicht dann ueber `punkte` hinaus, und zwar am LETZTEN Stueck
 *  bis zur Originalzahl der Spur — so lesen es `spur2_stand()` (naechste
 *  Nummer) und `spur2_umriss()` (Summe) beide richtig. */
const SPUR2_STUFE_DUENN = 3;

/**
 * Stand der Stuecke einer Spur: hoechste Stufe, `n_original` der
 * zusammengefuegten Stuecke, naechste Sequenznummer.
 *
 * @return array{stufe:int,n_original:int,naechste_seq:int,stuecke:int}
 */
function spur2_stand(PDO $pdo, string $ownerType, int $ownerId): array
{
    $q = $pdo->prepare('SELECT COUNT(*) AS n, COALESCE(MAX(stufe), 0) AS stufe,
                               COALESCE(MAX(seq_from + punkte), 0) AS weiter,
                               COALESCE(MAX(CASE WHEN stufe > 1 THEN seq_from + n_original END), 0) AS n_orig
                          FROM track_stuecke WHERE owner_type = ? AND owner_id = ?');
    $q->execute([$ownerType, $ownerId]);
    $r = $q->fetch(PDO::FETCH_ASSOC) ?: [];
    return ['stufe' => (int)($r['stufe'] ?? 0), 'n_original' => (int)($r['n_orig'] ?? 0),
            'naechste_seq' => max((int)($r['weiter'] ?? 0), (int)($r['n_orig'] ?? 0)),
            'stuecke' => (int)($r['n'] ?? 0)];
}

/**
 * Liegt der ganze Zeitraum [von, bis] in EINEM gesperrten Bereich?
 * Beide Grenzen gehoeren dazu, wie bei `schnitt_gesperrt()`.
 *
 * @param list<array{von_ts:int,bis_ts:int}> $schnitte aus schnitte_lesen()
 */
function schnitt_bereich_gesperrt(array $schnitte, int $von, int $bis): bool
{
    foreach ($schnitte as $s) {
        if ($von >= $s['von_ts'] && $bis <= $s['bis_ts']) { return true; }
    }
    return false;
}

/**
 * Ein Stueck vom Geraet ablegen (Stufe 1). Gibt 1 zurueck, wenn es neu
 * abgelegt wurde, 0 bei einer Wiederholung desselben `seq_from`.
 *
 * `$stufe` und `$nOriginal` nur beim Einspielen einer Sicherung (S11-06b):
 * Eine ausgeduennte Spur kommt als Stufe 3 zurueck, damit sie nach dem
 * Einspielen nicht als vollstaendig gilt.
 *
 * Die Huelle kommt GEPRUEFT an (`pruef_huelle()`); diese Funktion prueft nur
 * noch, was sie selbst wissen muss.
 */
function spur2_stueck_schreiben(PDO $pdo, string $ownerType, int $ownerId, int $seqFrom,
                                int $punkte, int $tsVon, int $tsBis, string $huelle,
                                int $stufe = SPUR2_STUFE_GERAET, ?int $nOriginal = null): int
{
    $nOriginal ??= $punkte;
    if ($punkte < 1 || $tsBis < $tsVon || $seqFrom < 0 || $nOriginal < $punkte
        || !in_array($stufe, [SPUR2_STUFE_GERAET, SPUR2_STUFE_GEPACKT, SPUR2_STUFE_DUENN], true)) {
        throw new InvalidArgumentException('Spurstueck mit unmoeglichen Zahlen');
    }
    $ins = $pdo->prepare('INSERT INTO track_stuecke
        (owner_type, owner_id, seq_from, punkte, n_original, ts_von, ts_bis, stufe, huelle,
         erstellt_am, geaendert_am)
        VALUES (?,?,?,?,?,?,?,?,?, UTC_TIMESTAMP(), UTC_TIMESTAMP())');
    try {
        $ins->bindValue(1, $ownerType);
        $ins->bindValue(2, $ownerId, PDO::PARAM_INT);
        $ins->bindValue(3, $seqFrom, PDO::PARAM_INT);
        $ins->bindValue(4, $punkte, PDO::PARAM_INT);
        $ins->bindValue(5, $nOriginal, PDO::PARAM_INT);
        $ins->bindValue(6, $tsVon, PDO::PARAM_INT);
        $ins->bindValue(7, $tsBis, PDO::PARAM_INT);
        $ins->bindValue(8, $stufe, PDO::PARAM_INT);
        $ins->bindValue(9, $huelle, PDO::PARAM_LOB);
        $ins->execute();
        return $ins->rowCount() > 0 ? 1 : 0;
    } catch (PDOException $ex) {
        require_once __DIR__ . '/email_lib.php';      // ist_dublettenfehler()
        if (!ist_dublettenfehler($ex)) { throw $ex; }
        return 0;
    }
}
/**
 * DIE STUECKE EINES EIGENTUEMERS ERSETZEN — nur bei unveraendertem Stand
 * (S11-05c; derselbe Tausch dient S11-07 dem Wartungslauf).
 *
 * Der Browser hat die Stuecke gelesen, geoeffnet, veraendert und neu
 * verschlossen; dazwischen kann ein Geraet weitere gesendet haben. `$stand`
 * ist die Liste `[seq_from, punkte]`, die er gelesen hat; weicht der Bestand
 * davon ab, wird NICHTS getauscht und `false` zurueckgegeben — die Seite laedt
 * neu und faengt von vorn an, statt ein eben angekommenes Stueck zu verlieren.
 *
 * AUCH STUFE 2 UND 3 (seit S11-07, E-S11-73/-74). Zwei Riegel halten, was
 * ein zusammengefuegtes oder ausgeduenntes Stueck traegt:
 *
 *   stufe   Die hoechste Stufe faellt nicht. Eine ausgeduennte Spur kommt
 *           nicht als vollstaendige zurueck, eine gepackte nicht als
 *           Geraetestueck — sonst stuende eine Marke ohne Grundlage da.
 *   marke   Die Fortsetzungsmarke (`seq_from + n_original` der gepackten,
 *           `seq_from + punkte` der uebrigen Stuecke) faellt nicht. Faellt
 *           sie, schickte das Geraet laengst verarbeitete Punkte erneut.
 *
 * Ein neues Stueck traegt `stufe` und `n_original`, wenn es keines der
 * Stufe 1 ist; ohne Angabe gilt Stufe 1 mit `n_original = punkte`.
 *
 * Die Transaktion gehoert dem Aufrufer (`db_transaktion()`); die Zeilen
 * werden mit `FOR UPDATE` gesperrt.
 *
 * @param list<array{0:int,1:int}> $stand
 * @param list<array{seq_from:int,punkte:int,ts_von:int,ts_bis:int,huelle:string,stufe?:int,n_original?:int}> $neu
 * @return array{ok:bool,grund?:string,weg?:int,neu?:int}
 */
function spur2_tauschen(PDO $pdo, string $ownerType, int $ownerId, array $stand, array $neu): array
{
    $q = $pdo->prepare('SELECT seq_from, punkte, n_original, stufe FROM track_stuecke
                         WHERE owner_type = ? AND owner_id = ? ORDER BY seq_from FOR UPDATE');
    $q->execute([$ownerType, $ownerId]);
    $ist = [];
    $stufeAlt = 0;
    $markeAlt = 0;
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $ist[] = [(int)$r['seq_from'], (int)$r['punkte']];
        $stufeAlt = max($stufeAlt, (int)$r['stufe']);
        $markeAlt = max($markeAlt, spur2_marke((int)$r['seq_from'], (int)$r['punkte'],
                                               (int)$r['n_original'], (int)$r['stufe']));
    }
    $soll = array_map(static fn($x) => [(int)$x[0], (int)$x[1]], $stand);
    usort($soll, static fn($a, $b) => $a[0] <=> $b[0]);
    if ($ist !== $soll) { return ['ok' => false, 'grund' => 'stand']; }

    $stufeNeu = 0;
    $markeNeu = 0;
    foreach ($neu as $st) {
        $sn = (int)($st['stufe'] ?? SPUR2_STUFE_GERAET);
        $stufeNeu = max($stufeNeu, $sn);
        $markeNeu = max($markeNeu, spur2_marke($st['seq_from'], $st['punkte'],
                                               (int)($st['n_original'] ?? $st['punkte']), $sn));
    }
    if ($stufeNeu < $stufeAlt) { return ['ok' => false, 'grund' => 'stufe']; }
    if ($markeNeu < $markeAlt) { return ['ok' => false, 'grund' => 'marke']; }

    $del = $pdo->prepare('DELETE FROM track_stuecke WHERE owner_type = ? AND owner_id = ?');
    $del->execute([$ownerType, $ownerId]);
    $weg = $del->rowCount();
    $n = 0;
    foreach ($neu as $st) {
        $n += spur2_stueck_schreiben($pdo, $ownerType, $ownerId, $st['seq_from'], $st['punkte'],
                                     $st['ts_von'], $st['ts_bis'], $st['huelle'],
                                     (int)($st['stufe'] ?? SPUR2_STUFE_GERAET),
                                     isset($st['n_original']) ? (int)$st['n_original'] : null);
    }
    return ['ok' => true, 'weg' => $weg, 'neu' => $n];
}

/** Bis wohin ein Stueck die Sequenz deckt (E-S11-71): ein gepacktes oder
 *  ausgeduenntes bis `n_original`, ein Geraetestueck bis `punkte`. */
function spur2_marke(int $seqFrom, int $punkte, int $nOriginal, int $stufe): int
{
    return $seqFrom + ($stufe > SPUR2_STUFE_GERAET ? $nOriginal : $punkte);
}

/**
 * DIE FAELLIGEN SPUREN DES WARTUNGSLAUFS (S11-07, E-S11-05, -25, -68 bis -73).
 *
 * Der Server sieht keine Koordinate, aber genug, um zu entscheiden, WAS
 * faellig ist — Stufen, Zeiten, Vermerke stehen im Klartext:
 *
 *   zusammenfuegen  abgeschlossen (`final`), und es liegt ein Stueck der
 *                   Stufe 1 — keine Karenz mehr (E-S11-05, -69)
 *   neu packen      ein Sperrvermerk ist juenger als das juengste Stueck: Die
 *                   herausgeschnittenen Punkte liegen noch darin und fallen
 *                   jetzt endgueltig (E-S11-37, -47)
 *   ausduennen      31 Tage nach dem Ende (E-S11-25); faellt es mit dem
 *                   Zusammenfuegen zusammen, geht beides in einem Tausch
 *
 * NICHT im Papierkorb (E-S11-70).
 *
 * DER BROWSER BEKOMMT ALLES, WAS ER BRAUCHT, IN EINER ANTWORT: die Stuecke
 * mit Huellen, die Sperrzeiten, bei Einsaetzen die Phasenzeiten (Schutzpunkte
 * der Ausduennung, E-S2-05 — die Reanimation war nie einer, Technik 4.97)
 * und den `pat_blob` mit seiner Pruefsumme (Ortshoehe, E-S11-72).
 *
 * @return list<array<string,mixed>>
 */
function spur2_faellig(PDO $pdo, int $userId, int $max = SPUR2_WARTUNG_MAX, ?int $jetzt = null,
                       array $auslassen = []): array
{
    $jetzt ??= time();
    $kandidaten = array_slice(spur2_faellig_kandidaten($pdo, $userId, $jetzt, $auslassen, $max), 0, $max);
    return spur2_faellig_fuellen($pdo, $userId, $kandidaten);
}

/**
 * Wie viele Spuren faellig sind, und aus wie vielen Diensttagen — fuer die
 * Karte „GPS-Daten verdichten" (Reiter Backup, Mockup M-S11-02). Dieselbe
 * Abfrage wie die Liste, ohne Huellen und ohne Obergrenze.
 *
 * @return array{aufzeichnungen:int, diensttage:int}
 */
function spur2_faellig_zahl(PDO $pdo, int $userId, ?int $jetzt = null, array $auslassen = []): array
{
    $k = spur2_faellig_kandidaten($pdo, $userId, $jetzt ?? time(), $auslassen, null);
    $tage = array_unique(array_filter(array_map(static fn($x) => $x['day_id'], $k),
                                      static fn($d) => $d !== null));
    return ['aufzeichnungen' => count($k), 'diensttage' => count($tage)];
}

/**
 * Die faelligen Spuren, nur Kennung, Zeiten und Zielstufe — sortiert nach
 * dem Ende. `$max` null: alle.
 *
 * @return list<array{art:string,id:int,ref:string,day_id:?int,started_at:?string,ended_at:?string,bezug:?string,ziel_stufe:int}>
 */
function spur2_faellig_kandidaten(PDO $pdo, int $userId, int $jetzt, array $auslassen, ?int $max): array
{
    /* WAS DER BROWSER IN DIESER SITZUNG NICHT OEFFNEN KONNTE, bleibt draussen.
     * Sonst stuende eine Spur mit kaputter Huelle bei jedem Lauf vorn und
     * belegte einen der Plaetze, bis alle zwanzig kaputt sind und nichts mehr
     * vorangeht. Gemeldet ist sie schon (Reiter System). */
    $ohne = ['mission' => [], 'rest' => []];
    foreach (array_slice($auslassen, 0, 200) as $a) {
        if (is_array($a) && isset($ohne[$a[0] ?? ''])) { $ohne[$a[0]][] = (int)($a[1] ?? 0); }
    }
    $grenze = gmdate('Y-m-d H:i:s', $jetzt - SPUR2_AUSDUENN_FRIST_TAGE * 86400);
    $kandidaten = [];
    foreach ([['mission', 'missions'], ['rest', 'rest_segments']] as [$typ, $tabelle]) {
        /* Die Eigentuemertabelle als Variable: dieselbe Abfrage fuer beide
         * Arten, wie in den Jobs, die dieser Lauf abloest. */
        $ohneSql = $ohne[$typ]
            ? 'AND o.id NOT IN (' . implode(',', array_map('intval', $ohne[$typ])) . ')' : '';
        $q = $pdo->prepare("SELECT o.id, o.client_ref, o.day_id, o.started_at, o.ended_at,
                                   COALESCE(o.ended_at, o.started_at) AS bezug,
                                   MIN(s.stufe) AS stufe_min, MAX(s.stufe) AS stufe_max,
                                   MAX(s.geaendert_am) AS geaendert,
                                   (SELECT MAX(c.erstellt_am) FROM track_cuts c
                                     WHERE c.owner_type = s.owner_type AND c.owner_id = s.owner_id
                                       AND c.mission_id IS NOT NULL) AS geschnitten
                              FROM track_stuecke s JOIN `$tabelle` o ON o.id = s.owner_id
                             WHERE s.owner_type = ? AND o.user_id = ? AND o.final = 1
                               AND o.deleted_at IS NULL {$ohneSql}
                             GROUP BY o.id
                            HAVING stufe_min = 1
                                OR (stufe_max < 3 AND bezug <= ?)
                                OR (geschnitten IS NOT NULL AND geschnitten > geaendert)
                             ORDER BY bezug, o.id" . ($max !== null ? ' LIMIT ' . (int)$max : ''));
        $q->execute([$typ, $userId, $grenze]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $faellig = $r['bezug'] !== null && $r['bezug'] <= $grenze;
            $kandidaten[] = [
                'art' => $typ, 'id' => (int)$r['id'], 'ref' => (string)$r['client_ref'],
                'day_id' => $r['day_id'] !== null ? (int)$r['day_id'] : null,
                'started_at' => $r['started_at'], 'ended_at' => $r['ended_at'], 'bezug' => $r['bezug'],
                'ziel_stufe' => ($faellig || (int)$r['stufe_max'] === SPUR2_STUFE_DUENN)
                                ? SPUR2_STUFE_DUENN : SPUR2_STUFE_GEPACKT,
            ];
        }
    }
    usort($kandidaten, static fn($a, $b) => [$a['bezug'], $a['art'], $a['id']]
                                           <=> [$b['bezug'], $b['art'], $b['id']]);
    return $kandidaten;
}

/**
 * Die Kandidaten fuer den Browser fuellen: Stuecke, Sperren, Phasen,
 * `pat_blob` mit Pruefsumme (siehe `spur2_faellig()`).
 *
 * @return list<array<string,mixed>>
 */
function spur2_faellig_fuellen(PDO $pdo, int $userId, array $kandidaten): array
{

    $ids = ['mission' => [], 'rest' => []];
    foreach ($kandidaten as $k) { $ids[$k['art']][] = $k['id']; }
    $stuecke = ['mission' => spur2_lesen_viele($pdo, 'mission', $ids['mission']),
                'rest'    => spur2_lesen_viele($pdo, 'rest', $ids['rest'])];
    $einsatz = [];
    $phasen = [];
    if ($ids['mission']) {
        require_once __DIR__ . '/db.php';
        foreach (sql_in_bloecken($pdo, 'SELECT id, pat_blob FROM missions
                                         WHERE user_id = ? AND id IN ({IDS})',
                                 $ids['mission'], [$userId]) as $r) {
            $einsatz[(int)$r['id']] = $r;
        }
        foreach (sql_in_bloecken($pdo, 'SELECT mission_id, phase, occurred_at FROM mission_phases
                                         WHERE mission_id IN ({IDS}) ORDER BY occurred_at, id',
                                 $ids['mission']) as $r) {
            $phasen[(int)$r['mission_id']][] = ['phase' => (int)$r['phase'],
                                                'occurred_at' => $r['occurred_at']];
        }
    }
    foreach ($kandidaten as &$k) {
        unset($k['bezug'], $k['day_id']);
        $k['stuecke'] = $stuecke[$k['art']][$k['id']] ?? [];
        $k['sperren'] = array_map(static fn($c) => ['von' => $c['von_ts'], 'bis' => $c['bis_ts']],
                                  schnitte_lesen($pdo, $k['art'], $k['id']));
        if ($k['art'] === 'mission') {
            $e = $einsatz[$k['id']] ?? [];
            $k['phases'] = $phasen[$k['id']] ?? [];
            $k['pat_blob'] = $e['pat_blob'] ?? null;
            $k['pat_pruef'] = spur2_pat_pruefsumme($e['pat_blob'] ?? null);
        }
    }
    unset($k);
    return $kandidaten;
}

/** Pruefsumme des `pat_blob`, an der der Tausch erkennt, ob ihn inzwischen
 *  jemand geaendert hat (E-S11-72). Auch fuer einen leeren Blob eindeutig. */
function spur2_pat_pruefsumme(?string $blob): string
{
    return hash('sha256', $blob === null ? "\0leer" : 'b:' . $blob);
}

/**
 * EIN TAUSCH DES WARTUNGSLAUFS (S11-07, E-S11-73). Prueft, was der Server
 * pruefen kann, und tauscht in einer Transaktion: Stuecke und — wenn der
 * Browser eine Ortshoehe nachgetragen hat — den `pat_blob`.
 *
 * @param array{seq_from:int,punkte:int,n_original:int,ts_von:int,ts_bis:int,stufe:int,huelle:string} $stueck
 * @return array{ok:bool,grund?:string,weg?:int,neu?:int}
 */
function spur2_wartung_tauschen(PDO $pdo, int $userId, string $art, int $id, array $stand,
                                array $stueck, ?array $pat = null, ?int $jetzt = null): array
{
    $jetzt ??= time();
    $tabelle = $art === 'mission' ? 'missions' : ($art === 'rest' ? 'rest_segments' : null);
    if ($tabelle === null) { return ['ok' => false, 'grund' => 'art']; }
    return db_transaktion($pdo, function (PDO $pdo) use ($userId, $art, $id, $tabelle, $stand,
                                                        $stueck, $pat, $jetzt): array {
        $q = $pdo->prepare("SELECT id, final, deleted_at, COALESCE(ended_at, started_at) AS bezug"
                         . ($art === 'mission' ? ', pat_blob' : '')
                         . " FROM `$tabelle` WHERE id = ? AND user_id = ? FOR UPDATE");
        $q->execute([$id, $userId]);
        $o = $q->fetch(PDO::FETCH_ASSOC);
        if (!$o) { return ['ok' => false, 'grund' => 'fehlt']; }
        if ((int)$o['final'] !== 1 || $o['deleted_at'] !== null) {
            return ['ok' => false, 'grund' => 'offen'];
        }

        /* DIE FORM DES NEUEN STUECKS gegen den gelesenen Bestand. */
        $q = $pdo->prepare('SELECT MIN(seq_from) s, MIN(ts_von) v, MAX(ts_bis) b
                              FROM track_stuecke WHERE owner_type = ? AND owner_id = ?');
        $q->execute([$art, $id]);
        $alt = $q->fetch(PDO::FETCH_ASSOC);
        if ($alt['s'] === null) { return ['ok' => false, 'grund' => 'stand']; }
        if ($stueck['seq_from'] !== (int)$alt['s']
            || $stueck['ts_von'] < (int)$alt['v'] || $stueck['ts_bis'] > (int)$alt['b']) {
            return ['ok' => false, 'grund' => 'form'];
        }
        /* STUFE 3 NUR NACH DER FRIST — unwiderruflich, und den Zeitpunkt
         * kennt der Server, auch wenn er den Inhalt nicht sieht. */
        if ($stueck['stufe'] === SPUR2_STUFE_DUENN
            && ($o['bezug'] === null
                || strtotime($o['bezug'] . ' UTC') > $jetzt - SPUR2_AUSDUENN_FRIST_TAGE * 86400)) {
            return ['ok' => false, 'grund' => 'frist'];
        }

        $t = spur2_tauschen($pdo, $art, $id, $stand, [$stueck]);
        if (!$t['ok']) { return $t; }

        if ($pat !== null) {
            if ($art !== 'mission'
                || !hash_equals(spur2_pat_pruefsumme($o['pat_blob'] ?? null), (string)$pat['pruef'])) {
                /* Der Blob hat sich geaendert (Formular, anderes Fenster):
                 * Die Spur ist gepackt, die Ortshoehe kommt beim naechsten
                 * Speichern im Formular — nichts ueberschreiben. */
                $t['pat'] = 'geaendert';
            } else {
                $pdo->prepare('UPDATE missions SET pat_blob = ? WHERE id = ? AND user_id = ?')
                    ->execute([$pat['neu'], $id, $userId]);
                $t['pat'] = 'geschrieben';
            }
        }
        return $t;
    });
}

/**
 * Der Umriss der Stuecke je Spur (S11-06a): hoechste Stufe, Summe der
 * Originalzahlen, Summe der gespeicherten Punkte — ohne eine Huelle zu lesen.
 * Fuer die Angaben im Kern einer Sicherung (`edbak_build()`).
 *
 * @param list<int> $ids
 * @return array<int, array{stufe:int, n_original:int, n:int}>
 */
function spur2_umriss(PDO $pdo, string $ownerType, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $aus = [];
    if (!$ids) { return $aus; }
    require_once __DIR__ . '/db.php';
    foreach (sql_in_bloecken($pdo,
        'SELECT owner_id, MAX(stufe) s, SUM(n_original) o, SUM(punkte) n FROM track_stuecke
          WHERE owner_type = ? AND owner_id IN ({IDS}) GROUP BY owner_id', $ids, [$ownerType]) as $r) {
        $aus[(int)$r['owner_id']] = ['stufe' => (int)$r['s'], 'n_original' => (int)$r['o'],
                                     'n' => (int)$r['n']];
    }
    return $aus;
}

/**
 * Die Stuecke mehrerer Spuren, wie sie liegen — fuer den Browser, der sie
 * oeffnet (S11-04b). Je Eigentuemer eine Liste, aufsteigend nach `seq_from`;
 * die Huelle als Base64, damit sie unveraendert in eine JSON-Antwort geht.
 *
 * DER SERVER OEFFNET NICHTS. Was hier herausgeht, ist Chiffretext mit den
 * Klartextzahlen, die der Browser fuer die Bindung braucht (`seq_from`,
 * `punkte`) und fuer die Zeiten (`ts_von`: die Punktzeiten in der Huelle
 * stehen relativ dazu, E-S11-18).
 *
 * @param list<int> $ids
 * @return array<int, list<array{seq_from:int,punkte:int,n_original:int,ts_von:int,ts_bis:int,stufe:int,huelle:string}>>
 */
function spur2_lesen_viele(PDO $pdo, string $ownerType, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $aus = [];
    if (!$ids) { return $aus; }
    require_once __DIR__ . '/db.php';
    foreach (sql_in_bloecken($pdo,
        'SELECT owner_id, seq_from, punkte, n_original, ts_von, ts_bis, stufe, huelle
           FROM track_stuecke WHERE owner_type = ? AND owner_id IN ({IDS})
          ORDER BY owner_id, seq_from', $ids, [$ownerType]) as $r) {
        $aus[(int)$r['owner_id']][] = [
            'seq_from' => (int)$r['seq_from'], 'punkte' => (int)$r['punkte'],
            'n_original' => (int)$r['n_original'],
            'ts_von' => (int)$r['ts_von'], 'ts_bis' => (int)$r['ts_bis'],
            'stufe' => (int)$r['stufe'], 'huelle' => base64_encode((string)$r['huelle'])];
    }
    return $aus;
}

/** Die Stuecke EINER Spur — siehe spur2_lesen_viele(). */
function spur2_lesen(PDO $pdo, string $ownerType, int $ownerId): array
{
    return spur2_lesen_viele($pdo, $ownerType, [$ownerId])[$ownerId] ?? [];
}
