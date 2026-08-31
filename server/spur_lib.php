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
 * NUR die Zeilen entfernen, den Blob behalten.
 *
 * Das ist der letzte Schritt der Verdichtung (AP3): Blob geschrieben,
 * Rundlauf bestanden, jetzt duerfen die Zeilen gehen. Bewusst eine EIGENE
 * Funktion neben spur_loeschen() — die beiden sehen sich aehnlich und meinen
 * Gegensaetzliches. Wer sie verwechselt, loescht entweder eine Spur, die
 * bleiben sollte, oder laesst Zeilen stehen, die der Blob schon traegt.
 *
 * @return int Zahl der entfernten Zeilen
 */
function spur_loeschen_nur_zeilen(PDO $pdo, string $ownerType, array $ids): int
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    if (!$ids) { return 0; }
    $weg = 0;
    foreach (array_chunk($ids, 1000) as $block) {
        $platz = implode(',', array_fill(0, count($block), '?'));
        $q = $pdo->prepare("DELETE FROM track_points
                             WHERE owner_type = ? AND owner_id IN ($platz)");
        $q->execute(array_merge([$ownerType], $block));
        $weg += $q->rowCount();
    }
    return $weg;
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
