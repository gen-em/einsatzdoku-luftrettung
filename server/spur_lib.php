<?php
declare(strict_types=1);

/**
 * Spurspeicherung — die EINZIGE Stelle, die SPUR1 liest und schreibt.
 *
 * WOFUER (Konzept S2, E-S2-03/04, Ausarbeitung 3.1).
 *
 * Spurpunkte sind 93 % des Bestands (B-S2-01). Als Zeilen in `track_points`
 * kosten sie gemessen 62,4 Byte je Punkt; als Blob 3,58 — ein
 * Siebzehntel. Deshalb wandern sie in drei Stufen:
 *
 *   Stufe 1  Zeilen in `track_points` — Eingangspuffer der Uhr. Fuer den
 *            idempotenten, in Teilstuecken ankommenden Upload ist eine
 *            Zeilentabelle richtig (B-S2-08).
 *   Stufe 2  verlustfreier Blob, sobald das Paket abgeschlossen ist.
 *   Stufe 3  ausgeduennter Blob, sechs Monate nach Einsatzende.
 *
 * Diese Datei baut die Stufen NICHT um — das tun die Jobs aus AP3. Sie stellt
 * bereit, was dafuer und fuer alle Leser gebraucht wird: Kodieren, Dekodieren,
 * Lesen ueber beide Stufen hinweg, Ausduennen und die Rundlaufpruefung.
 *
 * WARUM ALLES AN EINER STELLE. Sechs Stellen im Projekt lesen heute
 * `track_points` per SQL, jede mit einer eigenen Projektion. Bliebe das so,
 * muesste jede von ihnen die Stufen kennen — und die erste, die es vergisst,
 * zeigt eine leere Spur, ohne dass es auffaellt. `CLAUDE.md` traegt dafuer
 * seit S2 eine Pflegepflicht: SPUR1 nur ueber `spur_lib.php`.
 *
 * WAS „VERLUSTFREI" HEISST (F-S2-01, entschieden 31.08.2026).
 *
 * Keine Festkomma-Kodierung ist bitgleich gegen einen beliebigen `DOUBLE`.
 * „Verlustfrei" heisst deshalb: verlustfrei innerhalb einer FESTGESCHRIEBENEN
 * Aufloesung. Sie steht im Blob-Kopf und lautet
 *
 *      Breite/Laenge  x 1 000 000   (10^-6 Grad, rund 0,11 m)
 *      Hoehe          x 10          (Zehntelmeter)
 *      Zeit           Sekunden      (wie die Spalte)
 *
 * Die Hoehe in GANZEN Metern abzulegen — so stand es im Konzeptwortlaut —
 * waere hier nicht nur ungenauer, sondern haette den Mechanismus stillgelegt:
 * 74,4 % der Punkte des Referenzbestands tragen eine Nachkommastelle
 * (699,7 · 702,7 · …), die Rundlaufpruefung unten haette also bei drei von
 * vier Spuren angeschlagen und der Verdichtungsjob nie eine Zeile geloescht.
 * Der Preis der Zehntelmeter sind 7 % Blobgroesse (3,32 -> 3,58 B/Punkt) bei
 * einem Zielwert von 3 MB je 1000 Einsaetzen — kein Abwaegungsfall.
 *
 * Die Aufloesungskennung steht im Kopf, weil sie eine ZUSAGE ist und kein
 * Rechenweg: Wer sie aendert, aendert die Bedeutung jedes bereits
 * geschriebenen Blobs. Ein Leser, der eine unbekannte Kennung findet,
 * verweigert die Arbeit, statt Zahlen mit dem falschen Faktor zu deuten.
 */

require_once __DIR__ . '/validate_lib.php';

/* ---- Formatkonstanten ---------------------------------------------------- */

const SPUR_MAGIE       = 'SP';
const SPUR_FASSUNG     = 1;
const SPUR_STUFE_ROH   = 2;      // verlustfrei
const SPUR_STUFE_DUENN = 3;      // ausgeduennt

/** Aufloesungskennung 1: Grad x10^6, Hoehe x10. Siehe Kopfkommentar. */
const SPUR_AUFL_1        = 1;
const SPUR_AUFL_1_GRAD   = 1000000;
const SPUR_AUFL_1_HOEHE  = 10;

/** Kopflaenge: 2 Magie + 1 Fassung + 1 Stufe + 1 Aufloesung + 4 + 4 */
const SPUR_KOPF_LEN = 13;

/**
 * Toleranzen der Ausduennung (E-S2-05). Waagerecht und senkrecht GETRENNT:
 * Eine rein zweidimensionale Ausduennung ebnet das Hoehenprofil eines Fluges
 * ein — der Punkt auf halber Steigung liegt in der Draufsicht genau auf der
 * Verbindungslinie und faellt weg.
 */
const SPUR_TOL_WAAGERECHT_M = 2.0;
const SPUR_TOL_SENKRECHT_M  = 3.0;

/* ---- Kodieren ------------------------------------------------------------ */

/**
 * Punkte zu einem SPUR1-Blob machen.
 *
 * @param list<array{0:int,1:float,2:float,3:float|null,4:int}> $punkte
 *        je Punkt [seq, lat, lon, ele|null, ts], nach seq geordnet und
 *        LUECKENLOS ab 0 (E-S2-06). `seq` wird nicht gespeichert — die
 *        Position im Blob IST die Nummer.
 * @param int $nOriginal Punktzahl VOR jeder Ausduennung. Sie ist die
 *        Grundlage von `next_seq` und muss die Ausduennung ueberleben,
 *        sonst schickt die Uhr laengst verarbeitete Punkte erneut.
 */
function spur_kodieren(array $punkte, int $stufe, ?int $nOriginal = null): string
{
    $n = count($punkte);
    $nOriginal ??= $n;

    /* SPALTENWEISE, nicht punktweise (E-S2-04).
     *
     * Der Unterschied ist der ganze Gewinn: Nebeneinander stehen dann Werte
     * DERSELBEN Groessenordnung — lauter kleine Breitendifferenzen, dann
     * lauter kleine Laengendifferenzen. zlib findet darin Muster; in der
     * Reihenfolge lat,lon,ele,ts,lat,lon,… findet es keine.
     *
     * STUFE 9 UND NICHT 6. Gemessen am Referenzbestand (181 Spuren, 55 861
     * Punkte): 3,58 gegen 3,66 Byte je Punkt, 0,25 gegen 0,21 Sekunden fuer
     * den GANZEN Bestand. Zwei Prozent Platz fuer ein Fuenftel Rechenzeit ist
     * hier richtig herum: Verdichtet wird einmal in einem Hintergrundjob,
     * gelesen wird oft. */
    $lat = $lon = $ts = [];
    $hoehen = [];
    $bitfeld = str_repeat("\0", intdiv($n + 7, 8));

    foreach ($punkte as $i => $p) {
        $lat[] = (int)round(((float)$p[1]) * SPUR_AUFL_1_GRAD);
        $lon[] = (int)round(((float)$p[2]) * SPUR_AUFL_1_GRAD);
        $ts[]  = (int)$p[4];
        if ($p[3] !== null && $p[3] !== '') {
            $hoehen[] = (int)round(((float)$p[3]) * SPUR_AUFL_1_HOEHE);
            $bitfeld[intdiv($i, 8)] = chr(ord($bitfeld[intdiv($i, 8)]) | (1 << ($i % 8)));
        }
    }

    $nutz = spur_spalte_packen($lat)
          . spur_spalte_packen($lon)
          . $bitfeld
          . spur_spalte_packen($hoehen)
          . spur_spalte_packen($ts);

    $kopf = SPUR_MAGIE
          . chr(SPUR_FASSUNG)
          . chr($stufe)
          . chr(SPUR_AUFL_1)
          . pack('V', $nOriginal)
          . pack('V', $n);

    /* gzcompress = zlib-Strom (RFC 1950), das Gegenstueck zu gzuncompress.
     * NICHT gzencode (gzip-Rahmen) und nicht gzdeflate (roh) — das Rezept in
     * docs/Backup-Format.md nennt zlib, und Python (`zlib.decompress`) wie
     * JavaScript (`DecompressionStream('deflate')`) erwarten dasselbe. */
    return $kopf . gzcompress($nutz, 9);
}

/**
 * Eine Wertereihe als Differenzen zum Vorgaenger, int32 LE.
 *
 * Startwert ist 0, nicht der erste Wert: Damit ist die Reihe fuer sich
 * lesbar, ohne dass irgendwo ein Anker mitgefuehrt werden muesste.
 */
function spur_spalte_packen(array $werte): string
{
    $aus = '';
    $vor = 0;
    foreach ($werte as $w) {
        $d = $w - $vor;
        if ($d < -2147483648 || $d > 2147483647) {
            /* Kann mit gueltigen Koordinaten nicht vorkommen: Der groesste
             * denkbare Sprung ist 360 Grad, also 360 Mio. bei x10^6 — ein
             * Sechstel des Wertebereichs. Wer hier landet, hat keine Spur
             * mehr, sondern Unfug; das gehoert gesagt und nicht gekappt. */
            throw new RuntimeException(
                'Spurdifferenz ausserhalb von int32: ' . $d);
        }
        $aus .= pack('l', $d);
        $vor = $w;
    }
    return $aus;
}

/* ---- Dekodieren ---------------------------------------------------------- */

/**
 * Kopf eines Blobs lesen, ohne die Nutzlast auszupacken.
 *
 * Das ist der billige Weg fuer alles, was nur die Punktzahl braucht — der
 * Export etwa fragt heute mit einem eigenen COUNT nach (B-S2-02); mit dem
 * Blob steht die Zahl im Kopf und kostet nichts.
 *
 * @return array{fassung:int,stufe:int,aufloesung:int,n_original:int,n:int}
 */
function spur_kopf(string $blob): array
{
    if (strlen($blob) < SPUR_KOPF_LEN || substr($blob, 0, 2) !== SPUR_MAGIE) {
        throw new RuntimeException('Kein SPUR-Blob (Signatur fehlt).');
    }
    $fassung = ord($blob[2]);
    if ($fassung !== SPUR_FASSUNG) {
        throw new RuntimeException(
            "Spur-Blob in Formatfassung $fassung — diese Anwendung kennt nur "
            . SPUR_FASSUNG . '. Bitte die Anwendung aktualisieren.');
    }
    $aufl = ord($blob[4]);
    if ($aufl !== SPUR_AUFL_1) {
        /* NICHT einfach weiterrechnen. Eine unbekannte Aufloesungskennung
         * heisst, dass die Zahlen im Blob eine andere Bedeutung haben als die
         * Faktoren dieser Fassung. Wer sie trotzdem deutet, verschiebt jede
         * Koordinate — und zwar lautlos. */
        throw new RuntimeException(
            "Spur-Blob mit unbekannter Aufloesung $aufl. Bitte die Anwendung "
            . 'aktualisieren; die Daten sind in Ordnung.');
    }
    $z = unpack('Vorig/Vn', substr($blob, 5, 8));
    return ['fassung' => $fassung, 'stufe' => ord($blob[3]),
            'aufloesung' => $aufl,
            'n_original' => (int)$z['orig'], 'n' => (int)$z['n']];
}

/**
 * Blob zu Punkten. Gegenstueck zu spur_kodieren().
 *
 * @return list<array{0:int,1:float,2:float,3:float|null,4:int}>
 */
function spur_dekodieren(string $blob): array
{
    $kopf = spur_kopf($blob);
    $n = $kopf['n'];
    if ($n === 0) { return []; }

    $nutz = @gzuncompress(substr($blob, SPUR_KOPF_LEN));
    if ($nutz === false) {
        throw new RuntimeException('Spur-Blob laesst sich nicht entpacken.');
    }

    $bitLen = intdiv($n + 7, 8);
    $erwartetMin = 4 * $n + 4 * $n + $bitLen + 4 * $n;   // ohne Hoehenreihe
    if (strlen($nutz) < $erwartetMin) {
        throw new RuntimeException('Spur-Blob ist zu kurz für ' . $n . ' Punkte.');
    }

    $pos = 0;
    $lat = spur_spalte_lesen($nutz, $pos, $n);
    $lon = spur_spalte_lesen($nutz, $pos, $n);
    $bitfeld = substr($nutz, $pos, $bitLen);
    $pos += $bitLen;

    // Wie viele Hoehen stehen drin? Das sagt das Bitfeld, nicht die Laenge.
    $mitHoehe = 0;
    for ($i = 0; $i < $n; $i++) {
        if (ord($bitfeld[intdiv($i, 8)]) & (1 << ($i % 8))) { $mitHoehe++; }
    }
    $hoehen = spur_spalte_lesen($nutz, $pos, $mitHoehe);
    $ts = spur_spalte_lesen($nutz, $pos, $n);

    /* AUSDRUECKLICH (float) — und das ist kein Zierrat.
     *
     * PHPs Division liefert bei zwei Ganzzahlen mit glattem Ergebnis einen
     * INT zurueck: 7800 / 10 ist int(780), nicht float(780.0). Die
     * Rundlaufpruefung vergleicht mit `!==`, und int(780) !== float(780.0).
     * Ohne diese Umwandlung schlug sie bei 175 von 181 Referenzspuren an,
     * mit der Meldung „erwartet 780, gelesen 780" — dieselbe Zahl, ein
     * anderer Typ. Der Verdichtungsjob haette daraufhin keine einzige Zeile
     * geloescht, und die Ursache stand nirgends. */
    $punkte = [];
    $h = 0;
    for ($i = 0; $i < $n; $i++) {
        $hatHoehe = (bool)(ord($bitfeld[intdiv($i, 8)]) & (1 << ($i % 8)));
        $punkte[] = [
            $i,
            (float)($lat[$i] / SPUR_AUFL_1_GRAD),
            (float)($lon[$i] / SPUR_AUFL_1_GRAD),
            $hatHoehe ? (float)($hoehen[$h++] / SPUR_AUFL_1_HOEHE) : null,
            $ts[$i],
        ];
    }
    return $punkte;
}

/** Eine Differenzreihe zurueckrechnen. `$pos` wandert mit. */
function spur_spalte_lesen(string $nutz, int &$pos, int $anzahl): array
{
    if ($anzahl === 0) { return []; }
    $roh = unpack('l' . $anzahl, substr($nutz, $pos, 4 * $anzahl));
    if ($roh === false) {
        throw new RuntimeException('Spur-Blob: Wertereihe unlesbar.');
    }
    $pos += 4 * $anzahl;
    $aus = [];
    $lauf = 0;
    foreach ($roh as $d) { $lauf += $d; $aus[] = $lauf; }
    return $aus;
}

/* ---- Rundlaufpruefung ---------------------------------------------------- */

/**
 * Kommt aus dem Blob genau das zurueck, was hineinging? (E-S2-07)
 *
 * VERGLICHEN WIRD GEGEN DEN QUANTISIERTEN SOLLWERT, nicht gegen die rohe
 * `DOUBLE`-Spalte (F-S2-01). Die Pruefung belegt damit, dass Kodieren und
 * Dekodieren zueinander passen, dass kein Punkt verlorengeht, seine Stelle
 * wechselt oder seine Reihenfolge verliert — nicht eine Genauigkeit, die das
 * Format nie zugesagt hat.
 *
 * Das ist keine Formsache: Der Verdichtungsjob loescht die Zeilen erst, wenn
 * diese Funktion `null` zurueckgibt. Sie ist die letzte Instanz vor einem
 * unwiderruflichen DELETE.
 *
 * @return string|null null = in Ordnung, sonst die erste Abweichung im Klartext
 */
function spur_rundlauf_pruefen(array $punkte, string $blob): ?string
{
    try {
        $zurueck = spur_dekodieren($blob);
    } catch (Throwable $ex) {
        return 'Blob nicht lesbar: ' . $ex->getMessage();
    }
    if (count($zurueck) !== count($punkte)) {
        return 'Punktzahl weicht ab: ' . count($punkte) . ' hinein, '
             . count($zurueck) . ' zurueck';
    }
    foreach ($punkte as $i => $p) {
        $soll = spur_quantisieren($p);
        $ist  = $zurueck[$i];
        if ($ist[1] !== $soll[1] || $ist[2] !== $soll[2] || $ist[4] !== $soll[4]) {
            return "Punkt $i weicht ab: erwartet ({$soll[1]}, {$soll[2]}, {$soll[4]}), "
                 . "gelesen ({$ist[1]}, {$ist[2]}, {$ist[4]})";
        }
        if ($soll[3] === null ? $ist[3] !== null : $ist[3] !== $soll[3]) {
            return "Punkt $i: Hoehe weicht ab (erwartet "
                 . ($soll[3] === null ? 'keine' : (string)$soll[3])
                 . ', gelesen ' . ($ist[3] === null ? 'keine' : (string)$ist[3]) . ')';
        }
    }
    return null;
}

/**
 * Einen Punkt auf die Aufloesung des Formats bringen.
 *
 * Das ist der SOLLWERT der Rundlaufpruefung und zugleich das, was ein Leser
 * nach der Verdichtung zurueckbekommt. Wer wissen will, ob eine Anzeige sich
 * durch die Verdichtung aendert, vergleicht gegen diese Funktion.
 */
function spur_quantisieren(array $p): array
{
    // (float) wie in spur_dekodieren() — beide Seiten des Vergleichs muessen
    // denselben Typ haben, sonst prueft `!==` den Typ statt des Wertes.
    return [
        (int)$p[0],
        (float)(round((float)$p[1] * SPUR_AUFL_1_GRAD) / SPUR_AUFL_1_GRAD),
        (float)(round((float)$p[2] * SPUR_AUFL_1_GRAD) / SPUR_AUFL_1_GRAD),
        ($p[3] === null || $p[3] === '')
            ? null
            : (float)(round((float)$p[3] * SPUR_AUFL_1_HOEHE) / SPUR_AUFL_1_HOEHE),
        (int)$p[4],
    ];
}

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

/** Frist bis zur Ausduennung (E-S2-03): sechs Monate nach Einsatzende. */
const SPUR_AUSDUENN_FRIST_MONATE = 6;

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
 * DIE WAHL BEI GLEICHSTAND IST NICHT GESCHMACK. `site_elevation_lib.php` und
 * `api/mission.php` suchen den zeitnaechsten Punkt mit `<` und behalten damit
 * den FRUEHEREN. Waehlte die Behalteliste den spaeteren, fiele der
 * eigentliche Gewinner weg — und der naechste Aufruf von
 * `compute_site_elevation()` (er kommt bei jedem Speichern im Formular)
 * schriebe eine andere oder gar keine Hoehe, ohne Meldung. Am Referenzbestand
 * faellt das nicht auf: 576 Phasenzeitpunkte, 0 Gleichstaende.
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
 * `spur_rundlauf_pruefen()` allein ist hier WERTLOS: Die Behalteliste stammt
 * aus `spur_dekodieren()` des Stufe-2-Blobs, ihre Werte liegen also schon auf
 * der Formataufloesung; `spur_quantisieren()` ist darauf ein Nulloperator,
 * und der Vergleich geht IMMER auf. Er waere gruen, auch wenn die Ausduennung
 * die halbe Spur an der falschen Stelle wegwirft — und er ist die letzte
 * Instanz vor dem Ersetzen eines Blobs, nach dem das Original weg ist.
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

/* ---- Lesen ueber beide Stufen hinweg ------------------------------------- */

/**
 * Spuren mehrerer Eigentuemer lesen — Blob UND Nachzuegler, in EINER Runde.
 *
 * GEBUENDELT, weil der teuerste Leser die Tagesansicht ist: Sie holt die
 * Spuren aller Einsaetze und Ruhesegmente eines Diensttags. Einzeln gefragt
 * waeren das zwei Abfragen je Spur; hier sind es zwei insgesamt. Die
 * Blockgroesse uebernimmt sql_in_bloecken() aus db.php, damit die
 * IN-Liste nicht ins Uferlose waechst.
 *
 * WAS „NACHZUEGLER" SIND (E-S2-08). Zwischen Verdichtung und Ausduennung darf
 * die Uhr weiterhin Punkte nachreichen; sie landen als Zeilen in
 * `track_points`, hinter dem Blob. Diese Funktion setzt beides zusammen, und
 * zwar in dieser Reihenfolge — der Blob traegt die Punkte 0…n_original-1, die
 * Zeilen alles danach. Wer nur eines von beidem liest, zeigt eine Spur, der
 * das Ende fehlt, ohne dass es auffaellt.
 *
 * @param list<int> $ids
 * @return array<int, list<array{0:int,1:float,2:float,3:float|null,4:int}>>
 *         je Eigentuemerkennung die Punktliste; fehlt eine Kennung, hat sie
 *         keine Spur.
 */
function spur_lesen_viele(PDO $pdo, string $ownerType, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return []; }
    require_once __DIR__ . '/db.php';

    // 1. Blobs
    $aus = [];
    $nOriginal = [];
    foreach (sql_in_bloecken($pdo,
        'SELECT owner_id, n_original, blob_daten FROM track_blobs
          WHERE owner_type = ? AND owner_id IN ({IDS})', $ids, [$ownerType]) as $r) {
        $id = (int)$r['owner_id'];
        $aus[$id] = spur_dekodieren($r['blob_daten']);
        $nOriginal[$id] = (int)$r['n_original'];
    }

    // 2. Zeilen — alle, wenn kein Blob da ist; sonst nur die Nachzuegler.
    foreach (sql_in_bloecken($pdo,
        'SELECT owner_id, seq, lat, lon, ele, ts FROM track_points
          WHERE owner_type = ? AND owner_id IN ({IDS})
          ORDER BY owner_id, seq', $ids, [$ownerType]) as $r) {
        $id  = (int)$r['owner_id'];
        $seq = (int)$r['seq'];
        /* Eine Zeile, die der Blob schon traegt, ist ein Rest aus einem
         * abgebrochenen Verdichtungslauf — sie wird uebergangen, nicht
         * angehaengt. Sonst stuende derselbe Punkt zweimal in der Spur. */
        if (isset($nOriginal[$id]) && $seq < $nOriginal[$id]) { continue; }
        $aus[$id][] = [$seq, (float)$r['lat'], (float)$r['lon'],
                       $r['ele'] === null ? null : (float)$r['ele'],
                       (int)$r['ts']];
    }
    return $aus;
}

/** Eine einzelne Spur. Duenne Huelle um spur_lesen_viele(). */
function spur_lesen(PDO $pdo, string $ownerType, int $ownerId): array
{
    return spur_lesen_viele($pdo, $ownerType, [$ownerId])[$ownerId] ?? [];
}

/**
 * Punktzahl je Eigentuemer, OHNE die Punkte zu lesen.
 *
 * Der Export braucht sie, um zu entscheiden, ob er eine GPX-Datei anlegt
 * (`track_points > 0`); heute kostet ihn das ein eigenes COUNT ueber die
 * Zeilentabelle. Mit dem Blob steht die Zahl im Kopf — und als Spalte
 * daneben, damit auch das ohne Entpacken geht.
 *
 * @return array<int,int>
 */
function spur_zahlen(PDO $pdo, string $ownerType, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return []; }
    require_once __DIR__ . '/db.php';

    $aus = [];
    $nOriginal = [];
    foreach (sql_in_bloecken($pdo,
        'SELECT owner_id, n_original, n_gespeichert FROM track_blobs
          WHERE owner_type = ? AND owner_id IN ({IDS})', $ids, [$ownerType]) as $r) {
        $id = (int)$r['owner_id'];
        $aus[$id] = (int)$r['n_gespeichert'];
        $nOriginal[$id] = (int)$r['n_original'];
    }

    /* Nachzuegler zaehlen. Die Bedingung `seq >= n_original` steht im SQL und
     * nicht in PHP — sonst muesste je Spur mit Blob eine eigene Abfrage
     * folgen, und genau das ist das N+1, das sql_in_bloecken() abschaffen
     * sollte. Ohne Blob ist n_original 0, dann zaehlt sie alle Zeilen. */
    $marken = [];
    foreach ($ids as $id) { $marken[$id] = $nOriginal[$id] ?? 0; }
    // Ein CASE je Kennung waere unleserlich; stattdessen zwei Gruppen:
    // Spuren ohne Blob (alle Zeilen) und Spuren mit Blob (Zeilen ab Marke).
    $ohneBlob = array_values(array_filter($ids, fn($i) => !isset($nOriginal[$i])));
    if ($ohneBlob) {
        foreach (sql_in_bloecken($pdo,
            'SELECT owner_id, COUNT(*) n FROM track_points
              WHERE owner_type = ? AND owner_id IN ({IDS})
              GROUP BY owner_id', $ohneBlob, [$ownerType]) as $r) {
            $aus[(int)$r['owner_id']] = (int)$r['n'];
        }
    }
    $mitBlob = array_values(array_filter($ids, fn($i) => isset($nOriginal[$i])));
    if ($mitBlob) {
        foreach (sql_in_bloecken($pdo,
            'SELECT tp.owner_id, COUNT(*) n
               FROM track_points tp
               JOIN track_blobs tb ON tb.owner_type = tp.owner_type
                                  AND tb.owner_id  = tp.owner_id
              WHERE tp.owner_type = ? AND tp.owner_id IN ({IDS})
                AND tp.seq >= tb.n_original
              GROUP BY tp.owner_id', $mitBlob, [$ownerType]) as $r) {
            $aus[(int)$r['owner_id']] = ($aus[(int)$r['owner_id']] ?? 0) + (int)$r['n'];
        }
    }
    return $aus;
}

/**
 * Die Fortsetzungsmarke fuer die Uhr (E-S2-08, JSON-Vertrag).
 *
 * Bis Web 9.14.0 war das schlicht `MAX(seq)+1` ueber die Zeilen. Sobald die
 * Punkte im Blob liegen, gibt es diese Zeilen nicht mehr — die Marke faellt
 * auf 0 zurueck, und die Uhr sendet den ganzen Dienst noch einmal.
 *
 * Deshalb: `n_original` aus dem Blob (die Punktzahl VOR jeder Ausduennung)
 * und darueber hinaus die hoechste Zeilennummer. Fuer die Uhr ist das
 * ununterscheidbar vom bisherigen Verhalten.
 */
function spur_naechste_seq(PDO $pdo, string $ownerType, int $ownerId): int
{
    $q = $pdo->prepare('SELECT n_original FROM track_blobs
                         WHERE owner_type = ? AND owner_id = ?');
    $q->execute([$ownerType, $ownerId]);
    $ausBlob = (int)($q->fetchColumn() ?: 0);

    $q = $pdo->prepare('SELECT COALESCE(MAX(seq)+1, 0) FROM track_points
                         WHERE owner_type = ? AND owner_id = ?');
    $q->execute([$ownerType, $ownerId]);
    $ausZeilen = (int)$q->fetchColumn();

    return max($ausBlob, $ausZeilen);
}

/**
 * Der Zustand einer Spur in einem Blick — Stufe, Zahlen, Fortsetzungsmarke.
 *
 * WOFUER. `ingest.php` muss seit AP3 wissen, ob eine Spur AUSGEDUENNT ist
 * (E-S2-08: dann werden Punkte verworfen und quittiert, statt angenommen).
 * Es darf `track_blobs` aber nicht selbst abfragen — SPUR1 nur ueber diese
 * Datei (CLAUDE.md 4). Und es soll dafuer keine zusaetzliche Abfrage
 * bezahlen: Diese Funktion ERSETZT den bisherigen Aufruf von
 * `spur_naechste_seq()`, sie kommt nicht dazu. Gemessen an dieser
 * Installation (3,3 Mio. Zeilen, 181 Blobs): 0,13 ms, genau wie vorher.
 *
 * `stufe` ist 1, wenn es keine Blobzeile gibt — damit bildet der
 * Rueckgabewert das Stufenmodell aus E-S2-03 vollstaendig ab, und der
 * Aufrufer muss „kein Blob" nicht von „Stufe 1" unterscheiden.
 *
 * @return array{stufe:int,n_original:int,n_gespeichert:int,naechste_seq:int}
 */
function spur_stand(PDO $pdo, string $ownerType, int $ownerId): array
{
    $q = $pdo->prepare('SELECT stufe, n_original, n_gespeichert FROM track_blobs
                         WHERE owner_type = ? AND owner_id = ?');
    $q->execute([$ownerType, $ownerId]);
    $r = $q->fetch(PDO::FETCH_ASSOC);

    $q = $pdo->prepare('SELECT COALESCE(MAX(seq)+1, 0) FROM track_points
                         WHERE owner_type = ? AND owner_id = ?');
    $q->execute([$ownerType, $ownerId]);
    $ausZeilen = (int)$q->fetchColumn();

    if (!$r) {
        return ['stufe' => 1, 'n_original' => 0, 'n_gespeichert' => 0,
                'naechste_seq' => $ausZeilen];
    }
    $nOriginal = (int)$r['n_original'];
    return ['stufe' => (int)$r['stufe'], 'n_original' => $nOriginal,
            'n_gespeichert' => (int)$r['n_gespeichert'],
            'naechste_seq' => max($nOriginal, $ausZeilen)];
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
    return ($stand['stufe'] ?? 0) === SPUR_STUFE_DUENN;
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

/**
 * Der Umriss einer Menge von Spuren — alles, was der Verdichtungsjob zum
 * ENTSCHEIDEN braucht, ohne einen einzigen Punkt zu lesen.
 *
 * WARUM DAS EINE EIGENE FUNKTION IST. Der Job muss je Kandidat wissen:
 * Wie viele Zeilen? Lueckenlos? Wann kam der letzte Punkt? Gibt es schon
 * einen Blob, und auf welcher Stufe? Diese vier Fragen einzeln zu stellen
 * waere ein N+1 ueber den ganzen Block; hier sind es zwei Abfragen fuer alle.
 *
 * Und sie stehen HIER und nicht im Job, weil Lueckenlosigkeit eine
 * FORMATAUSSAGE ist: `seq` wird nicht gespeichert, die Position im Blob IST
 * die Nummer (siehe `spur_kodieren()`). Wer eine Spur mit Luecke verdichtet,
 * verschiebt stillschweigend jeden Punkt dahinter.
 *
 * @param list<int> $ids
 * @return array<int,array{zeilen:int,min_seq:?int,max_seq:?int,max_ts:?int,
 *                         stufe:int,n_original:int,lueckenlos:bool,gesamt:int}>
 */
function spur_umriss(PDO $pdo, string $ownerType, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return []; }
    require_once __DIR__ . '/db.php';

    $aus = [];
    foreach ($ids as $id) {
        $aus[$id] = ['zeilen' => 0, 'min_seq' => null, 'max_seq' => null,
                     'max_ts' => null, 'stufe' => 1, 'n_original' => 0,
                     'lueckenlos' => true, 'gesamt' => 0];
    }
    foreach (sql_in_bloecken($pdo,
        'SELECT owner_id, COUNT(*) n, MIN(seq) mn, MAX(seq) mx, MAX(ts) mts
           FROM track_points WHERE owner_type = ? AND owner_id IN ({IDS})
          GROUP BY owner_id', $ids, [$ownerType]) as $r) {
        $id = (int)$r['owner_id'];
        $aus[$id]['zeilen']  = (int)$r['n'];
        $aus[$id]['min_seq'] = (int)$r['mn'];
        $aus[$id]['max_seq'] = (int)$r['mx'];
        $aus[$id]['max_ts']  = (int)$r['mts'];
    }
    foreach (sql_in_bloecken($pdo,
        'SELECT owner_id, stufe, n_original FROM track_blobs
          WHERE owner_type = ? AND owner_id IN ({IDS})', $ids, [$ownerType]) as $r) {
        $id = (int)$r['owner_id'];
        $aus[$id]['stufe']      = (int)$r['stufe'];
        $aus[$id]['n_original'] = (int)$r['n_original'];
    }

    foreach ($aus as $id => &$u) {
        $nO = $u['n_original'];
        if ($u['zeilen'] === 0) {
            $u['gesamt'] = $nO;
            continue;
        }
        /* Die Vereinigung aus Blobpunkten (0 .. n_original-1) und Zeilen ist
         * genau dann lueckenlos, wenn die Zeilen bei 0 (ohne Blob) bzw.
         * spaetestens bei n_original (mit Blob) anfangen und selbst keine
         * Luecke haben.
         *
         * BEI STUFE 3 IST DIE FRAGE NICHT SO ZU BEANTWORTEN: Ein
         * ausgeduennter Blob gibt seine Punkte mit der POSITION zurueck, nicht
         * mit der Originalnummer; die Vereinigung saehe immer nach Luecke aus.
         * Der Job darf eine solche Spur deshalb gar nicht erst als Kandidaten
         * nehmen — siehe dort. */
        $u['gesamt'] = max($nO, $u['max_seq'] + 1);
        $u['lueckenlos'] = ($u['min_seq'] <= max(0, $nO))
                        && ($u['max_seq'] - $u['min_seq'] + 1 === $u['zeilen'])
                        && ($nO === 0 ? $u['min_seq'] === 0 : true);
    }
    unset($u);
    return $aus;
}

/* ---- Schreiben und Loeschen ---------------------------------------------- */

/** Einen Blob ablegen oder ersetzen. */
function spur_blob_schreiben(PDO $pdo, string $ownerType, int $ownerId,
                             string $blob, int $stufe, int $nOriginal, int $n): void
{
    $pdo->prepare('INSERT INTO track_blobs
                     (owner_type, owner_id, stufe, n_original, n_gespeichert,
                      blob_daten, erstellt_am, geaendert_am)
                   VALUES (?,?,?,?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP())
                   ON DUPLICATE KEY UPDATE
                     stufe = VALUES(stufe), n_original = VALUES(n_original),
                     n_gespeichert = VALUES(n_gespeichert),
                     blob_daten = VALUES(blob_daten),
                     geaendert_am = UTC_TIMESTAMP()')
        ->execute([$ownerType, $ownerId, $stufe, $nOriginal, $n, $blob]);
}

/**
 * Eine Spur restlos entfernen — Zeilen UND Blob.
 *
 * DIE EINZIGE STELLE, an der eine Spur geloescht wird (E-S2-18). Jeder
 * Loeschweg der Anwendung ruft sie; keiner loescht selbst.
 *
 * WARUM DAS EINE EIGENE FUNKTION IST: Weder `track_points` noch
 * `track_blobs` haengen an einem Fremdschluessel — sie sind polymorph, und
 * MySQL kennt keine bedingten Fremdschluessel. Ein Loeschweg, der das
 * vergisst, laesst Positionsdaten liegen; genau das ist bei der
 * Kontoloeschung jahrelang passiert, und der Kommentar dort behauptete das
 * Gegenteil (F-S2-B). Mit einer benannten Funktion faellt das Vergessen
 * wenigstens beim Lesen auf.
 *
 * @param list<int> $ids
 * @return array{zeilen:int,blobs:int}
 */
function spur_loeschen(PDO $pdo, string $ownerType, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return ['zeilen' => 0, 'blobs' => 0]; }
    require_once __DIR__ . '/db.php';

    /* HIER NICHT sql_in_bloecken(): Sie ist zum LESEN gebaut und gibt Zeilen
     * zurueck, keine Zahl betroffener Saetze. Die Blockung wird deshalb von
     * Hand gemacht — mit derselben Blockgroesse, damit beide Wege dieselbe
     * Grenze zur Parameterzahl von MySQL halten. */
    $zeilen = $blobs = 0;
    foreach (array_chunk($ids, 1000) as $block) {
        $platz = implode(',', array_fill(0, count($block), '?'));
        $q = $pdo->prepare("DELETE FROM track_points
                             WHERE owner_type = ? AND owner_id IN ($platz)");
        $q->execute(array_merge([$ownerType], $block));
        $zeilen += $q->rowCount();

        $q = $pdo->prepare("DELETE FROM track_blobs
                             WHERE owner_type = ? AND owner_id IN ($platz)");
        $q->execute(array_merge([$ownerType], $block));
        $blobs += $q->rowCount();
    }
    return ['zeilen' => $zeilen, 'blobs' => $blobs];
}

/**
 * NUR die Zeilen UNTERHALB einer Nummer entfernen, den Blob behalten.
 *
 * Das ist der letzte Schritt der Verdichtung (AP3): Blob geschrieben,
 * Rundlauf bestanden, jetzt duerfen die Zeilen gehen. Bewusst eine EIGENE
 * Funktion neben spur_loeschen() — die beiden sehen sich aehnlich und meinen
 * Gegensaetzliches. Wer sie verwechselt, loescht entweder eine Spur, die
 * bleiben sollte, oder laesst Zeilen stehen, die der Blob schon traegt.
 *
 * WARUM DIE OBERGRENZE PFLICHT IST — und warum sie in AP3 dazugekommen ist.
 *
 * Bis Web 10.1.0 loeschte diese Funktion ALLE Zeilen eines Eigentuemers, ohne
 * Grenze. Der Verdichtungsjob liest die Punkte, kodiert, prueft und loescht;
 * `ingest.php` laeuft dabei in einer EIGENEN Transaktion mit eigenem Commit.
 * Ein DELETE ist in MySQL ein *current read* — er sieht auch, was nach dem
 * Schnappschuss des Jobs committet wurde. Traf also ein Upload genau
 * dazwischen ein, verschwanden Punkte, die in keinem Blob stehen. Still,
 * endgueltig, und der Uhr mit „ok" quittiert.
 *
 * `$unterhalbSeq` ist deshalb VERPFLICHTEND und nicht wahlweise: Eine
 * wahlweise Obergrenze ist eine, die vergessen wird, und das Vergessen ist
 * hier stiller Datenverlust. Und es ist EIN Parameter statt einer zweiten
 * Funktion, weil zwei aehnlich heissende Loeschfunktionen genau das Problem
 * sind, vor dem der Absatz darueber warnt.
 *
 * Der Aufrufer uebergibt die Punktzahl, die er tatsaechlich in den Blob
 * geschrieben hat. Was danach eintraf, traegt eine hoehere Nummer und bleibt
 * als Nachzuegler stehen — der naechste Lauf arbeitet es ein.
 *
 * @return int Zahl der entfernten Zeilen
 */
function spur_loeschen_nur_zeilen(PDO $pdo, string $ownerType, int $ownerId,
                                  int $unterhalbSeq): int
{
    $q = $pdo->prepare('DELETE FROM track_points
                         WHERE owner_type = ? AND owner_id = ? AND seq < ?');
    $q->execute([$ownerType, $ownerId, $unterhalbSeq]);
    return $q->rowCount();
}

/* ---- Umdatieren ---------------------------------------------------------- */

/**
 * Die fruehesten Zeitstempel einer Menge von Spuren — Zeilen UND Blob.
 *
 * Gebraucht von der Umdatierung eines Diensttags: `track_points.ts` ist
 * VORZEICHENLOS, eine Rueckdatierung unter den 1.1.1970 waere ein
 * Datenbankfehler mitten in der Transaktion. Die Pruefung davor muss deshalb
 * beide Stufen sehen.
 */
function spur_min_ts(PDO $pdo, string $ownerType, array $ids): ?int
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return null; }
    require_once __DIR__ . '/db.php';

    $min = null;
    foreach (sql_in_bloecken($pdo,
        'SELECT MIN(ts) AS m FROM track_points
          WHERE owner_type = ? AND owner_id IN ({IDS})', $ids, [$ownerType]) as $r) {
        if ($r['m'] !== null) { $min = $min === null ? (int)$r['m'] : min($min, (int)$r['m']); }
    }
    /* Fuer die Blobs gibt es keinen SQL-Weg an den Zeitstempel: Er steckt im
     * gepackten Strom. Bei einer Umdatierung geht es um die Spuren EINES
     * Diensttags — eine Handvoll —, deshalb ist das Auspacken hier
     * vertretbar. Fuer einen Massenweg waere es das nicht. */
    foreach (spur_lesen_viele($pdo, $ownerType, $ids) as $punkte) {
        foreach ($punkte as $p) {
            $min = $min === null ? (int)$p[4] : min($min, (int)$p[4]);
        }
    }
    return $min;
}

/**
 * Alle Zeitstempel einer Spur um `$delta` Sekunden verschieben — Zeilen UND Blob.
 *
 * WARUM DER BLOB NEU GESCHRIEBEN WIRD. Das Umdatieren eines Diensttags war
 * bis S2 ein einziges `UPDATE track_points SET ts = ts + ?`. An einem Blob
 * geht das vorbei: Die Zeilen wanderten, die Blobpunkte blieben stehen, und
 * die Spur haette danach zwei Zeitrechnungen — sichtbar erst als
 * durcheinandergeratene Phasenzuordnung in der Einsatzansicht.
 *
 * Der Blob wird deshalb gelesen, verschoben und zurueckgeschrieben. Stufe und
 * `n_original` bleiben, was sie waren: Umdatieren ist keine Verdichtung.
 */
function spur_zeit_verschieben(PDO $pdo, string $ownerType, array $ids, int $delta): void
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids || $delta === 0) { return; }
    require_once __DIR__ . '/db.php';

    foreach (array_chunk($ids, 1000) as $block) {
        $platz = implode(',', array_fill(0, count($block), '?'));
        $pdo->prepare("UPDATE track_points SET ts = ts + ?
                        WHERE owner_type = ? AND owner_id IN ($platz)")
            ->execute(array_merge([$delta, $ownerType], $block));
    }

    foreach (sql_in_bloecken($pdo,
        'SELECT owner_id, stufe, n_original, blob_daten FROM track_blobs
          WHERE owner_type = ? AND owner_id IN ({IDS})', $ids, [$ownerType]) as $r) {
        $punkte = spur_dekodieren($r['blob_daten']);
        foreach ($punkte as $i => $p) { $punkte[$i][4] = $p[4] + $delta; }
        $blob = spur_kodieren($punkte, (int)$r['stufe'], (int)$r['n_original']);
        spur_blob_schreiben($pdo, $ownerType, (int)$r['owner_id'], $blob,
                            (int)$r['stufe'], (int)$r['n_original'], count($punkte));
    }
}
