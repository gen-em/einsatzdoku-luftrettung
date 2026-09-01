<?php
declare(strict_types=1);

/**
 * GPX 1.1 aus Spuren — die EINZIGE Stelle, die GPX schreibt (S2/AP4).
 *
 * WOFUER (Konzept S2, E-S2-09, Backlog Nr. 3). Eine Spur soll sich einzeln
 * herunterladen lassen, je Einsatz und je Ruhesegment, in einem Format, das
 * jedes Kartenprogramm liest — und mehrere ausgewaehlte als EINE Datei. Bis Web 10.2.0 gab es GPX nur als Beiwerk im
 * grossen Export, im Browser zusammengesetzt (`assets/export.js`).
 *
 * WARUM SERVERSEITIG — und warum das die erste Datei dieses Projekts ist, die
 * der Server ausliefert.
 *
 * Bis hierher entsteht JEDE Datei, die auf der Platte einer Nutzerin landet,
 * im Browser aus einem Blob. Das hat einen Grund und keinen Zufall: Ihr
 * Inhalt ist Ende-zu-Ende verschluesselt, der Server KANN ihn nicht
 * zusammensetzen. Fuer eine Spur gilt das nicht — Spurpunkte liegen im
 * Klartext (Backlog Nr. 43), und die Stufe, die E-S2-09 sichtbar verlangt,
 * kennt ohnehin nur der Server (`spur_stand()`).
 *
 * Der Browser haette beides gar nicht: `api/mission.php` liefert die Spur als
 * blosse Paare [lat, lon] — ohne Hoehe, ohne Zeit, ohne Stufe. Ein
 * browsergebautes GPX braeuchte also einen neuen, breiteren Abrufweg, nur um
 * anschliessend zusammenzusetzen, was auf dem Server schon beieinander liegt.
 *
 * Und ein Sicherheitsargument gibt den Ausschlag: Der DATEINAME landet im
 * Downloadordner, in einem Backup, vielleicht in einer Mail. Serverseitig
 * gebaut KANN er keine geschuetzte Angabe tragen — der Server kann Diagnose,
 * Alter und Einsatzort nicht lesen. Browserseitig gebaut koennte er es, und
 * das waere ein neuer Weg, auf dem Klartext das Haus verlaesst.
 *
 * WAS DIESE DATEI NICHT TUT: Rechte pruefen. `spur_lib.php` prueft kein
 * Eigentum, und diese Datei tut es auch nicht — sie bekommt Punkte und
 * schreibt XML. Wer sie ruft, hat vorher gegen `user_id` gefiltert. Der
 * Endpunkt `gpx.php` tut das; ein neuer Verbraucher, der es vergisst,
 * liefert fremde Spuren aus.
 */

require_once __DIR__ . '/spur_lib.php';

/** Formatfassung, die wir schreiben. */
const GPX_FASSUNG = '1.1';
const GPX_NS      = 'http://www.topografix.com/GPX/1/1';
const GPX_CREATOR = 'Einsatzdoku';

/**
 * Wie viele Spuren hoechstens in EINE Datei duerfen (S2/AP4).
 *
 * Nicht wegen der Rechte — es sind die eigenen Spuren —, sondern wegen des
 * Speichers: Die Datei entsteht vollstaendig im Arbeitsspeicher, weil ihre
 * Laenge in die Kopfzeile gehoert. Bei der groessten Spur des
 * Referenzbestands (1063 Punkte von 9581 Spuren, Mittel 196) sind hundert
 * Spuren rund 11 MB — im Budget von 64 MB (Z3) und weit ueber dem, was ein
 * Diensttag traegt.
 */
const GPX_AUSWAHL_MAX = 100;

/**
 * Ein Wert fuer XML-Inhalt.
 *
 * `htmlspecialchars` mit ENT_XML1: `&apos;` ist in XML gueltig, in HTML 4
 * nicht — und die Vorgabe von PHP richtet sich nach HTML.
 */
function gpx_e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/**
 * Die Kennzeichnung einer Stufe im Klartext (E-S2-09).
 *
 * Sie steht an drei Stellen, und das ist Absicht: in der Datei (zweimal, s.
 * `gpx_bauen()`), auf der Seite vor dem Herunterladen und im Dateinamen. Nur
 * der Dateiname ueberlebt das Verschieben der Datei in einen anderen Ordner.
 */
function gpx_stufe_text(int $stufe): string
{
    return $stufe === SPUR_STUFE_DUENN
        ? 'ausgedünnt'
        : 'Originalspur';
}

/** Dasselbe als Wortmarke fuer den Dateinamen — ohne Umlaut, ohne Leerzeichen. */
function gpx_stufe_marke(int $stufe): string
{
    return $stufe === SPUR_STUFE_DUENN ? 'ausgeduennt' : 'original';
}

/**
 * Die Beschreibung EINER Spur — Stufe und Punktzahl im Klartext.
 */
function gpx_beschreibung(int $anzahl, int $stufe, ?int $nOriginal = null): string
{
    $t = gpx_stufe_text($stufe);
    if ($stufe === SPUR_STUFE_DUENN && $nOriginal !== null && $nOriginal > $anzahl) {
        /* DIE ZAHL GEHOERT DAZU. „ausgeduennt" allein sagt nicht, wie viel
         * fehlt; wer die Datei in zwei Jahren wiederfindet, soll es ihr
         * ansehen koennen, ohne die Anwendung zu befragen. */
        return $t . sprintf(' — %d von ursprünglich %d Punkten '
            . '(Douglas-Peucker, %s m waagerecht / %s m senkrecht)',
            $anzahl, $nOriginal,
            rtrim(rtrim(number_format(SPUR_TOL_WAAGERECHT_M, 1, ',', ''), '0'), ','),
            rtrim(rtrim(number_format(SPUR_TOL_SENKRECHT_M, 1, ',', ''), '0'), ','));
    }
    return $t . sprintf(' — %d Punkte', $anzahl);
}

/**
 * Ein `<trk>` samt Punkten.
 *
 * DIE REIHENFOLGE DER KINDELEMENTE IST NICHT FREI (s. `gpx_bauen_viele()`):
 * `<desc>` steht zwischen `<name>` und `<trkseg>`.
 *
 * @param list<array{0:int,1:float,2:float,3:float|null,4:int}> $punkte
 */
function gpx_trk(array $punkte, string $name, string $beschreibung): string
{
    $x  = '<trk><name>' . gpx_e($name) . '</name>'
        . '<desc>' . gpx_e($beschreibung) . '</desc>'
        . '<trkseg>';
    foreach ($punkte as $p) {
        /* KEINE Maskierung noetig und KEINE Umformatierung erlaubt: Die Werte
         * kommen aus `spur_dekodieren()` und sind Zahlen, keine Zeichenketten.
         * Sie hier durch number_format zu schicken waere ein Fehler — die
         * deutsche Schreibweise mit Komma macht das Dokument unbrauchbar. */
        $x .= '<trkpt lat="' . $p[1] . '" lon="' . $p[2] . '">';
        if ($p[3] !== null) { $x .= '<ele>' . $p[3] . '</ele>'; }
        $x .= '<time>' . gmdate('Y-m-d\TH:i:s\Z', (int)$p[4]) . '</time>';
        $x .= '</trkpt>';
    }
    return $x . '</trkseg></trk>' . "\n";
}

/**
 * Ein GPX-1.1-Dokument aus EINER ODER MEHREREN Spuren.
 *
 * DIE REIHENFOLGE DER KINDELEMENTE IST NICHT FREI. GPX 1.1 beschreibt sie als
 * `xsd:sequence`, nicht als `xsd:choice` — wer `<desc>` hinten anhaengt,
 * schreibt eine Datei, die gegen das Schema durchfaellt, und genau das misst
 * die Abnahme dieses Pakets. Die beiden Stellen, auf die es ankommt:
 *
 *   metadataType   name, desc, author, copyright, link, TIME, keywords, …
 *                  -> `<desc>` steht VOR `<time>`
 *   trkType        name, cmt, desc, src, link, number, type, ext, TRKSEG
 *                  -> `<desc>` steht zwischen `<name>` und `<trkseg>`
 *
 * MEHRERE SPUREN WERDEN NICHT ZUSAMMENGEKLEBT, sondern bleiben mehrere
 * `<trk>` in einer Datei. GPX 1.1 erlaubt das ausdruecklich (`<gpx>` hat
 * `trk` als `maxOccurs="unbounded"`), und es ist der einzige richtige Weg:
 * Wer zwei Spuren in EIN `<trkseg>` schreibt, laesst jedes Kartenprogramm
 * eine gerade Linie vom Ende der einen zum Anfang der naechsten ziehen — quer
 * ueber das Land, einen Weg, den niemand gefahren ist. Auch mehrere `<trkseg>`
 * in einem `<trk>` waeren falsch: Sie meinen Abschnitte EINER Aufzeichnung
 * mit einer Luecke dazwischen, nicht zwei verschiedene Fahrten.
 *
 * `iterable` UND NICHT `array` — der Grund ist der Speicher. Eine dekodierte
 * Spur kostet rund 4 MB (S2/AP3, gemessen); ein Diensttag kann zwei Dutzend
 * tragen. Wer sie alle als Feld uebergibt, haelt sie alle gleichzeitig und
 * sprengt das Budget von 64 MB (Z3). Ein Generator liefert sie einzeln, und
 * nach jedem `<trk>` ist die vorige wieder frei. Deshalb entstehen die Bloecke
 * ZUERST und der Kopf danach: Die Gesamtzahl kennt man erst am Ende, das
 * `<metadata>` steht aber vorn.
 *
 * @param iterable<array{punkte:list<array>,name:string,stufe:int,n_original?:int|null}> $spuren
 */
function gpx_bauen_viele(iterable $spuren, string $name,
                         ?int $erzeugtAm = null): string
{
    $bloecke = '';
    $anzahl  = 0;
    $punkte  = 0;
    $stufen  = [];
    $einzeln = '';

    foreach ($spuren as $s) {
        $n = count($s['punkte']);
        $b = gpx_beschreibung($n, (int)$s['stufe'], $s['n_original'] ?? null);
        $bloecke .= gpx_trk($s['punkte'], (string)$s['name'], $b);
        $anzahl++;
        $punkte += $n;
        $stufen[(int)$s['stufe']] = true;
        $einzeln = $b;
    }

    /* BEI EINER SPUR IST DIE BESCHREIBUNG DES DOKUMENTS DIE DER SPUR. Bei
     * mehreren waere sie eine Halbwahrheit — „ausgeduennt — 412 Punkte" ueber
     * einer Datei mit sechs Spuren. Dann sagt der Kopf, was die Datei als
     * Ganzes ist; welche Stufe die einzelne Spur hat, steht an ihr. */
    if ($anzahl === 1) {
        $beschreibung = $einzeln;
    } else {
        $stufenText = count($stufen) > 1
            ? 'teils ausgedünnt — jede Spur nennt ihre Stufe'
            : (isset($stufen[SPUR_STUFE_DUENN]) ? 'alle ausgedünnt' : 'alle im Original');
        $beschreibung = sprintf('%d Spuren — %d Punkte insgesamt · %s',
                                $anzahl, $punkte, $stufenText);
    }

    $erzeugt = gmdate('Y-m-d\TH:i:s\Z', $erzeugtAm ?? time());

    $x  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $x .= '<gpx version="' . GPX_FASSUNG . '" creator="' . GPX_CREATOR . '"'
        . ' xmlns="' . GPX_NS . '">' . "\n";
    $x .= '<metadata><name>' . gpx_e($name) . '</name>'
        . '<desc>' . gpx_e($beschreibung) . '</desc>'
        . '<time>' . $erzeugt . '</time></metadata>' . "\n";
    return $x . $bloecke . "</gpx>\n";
}

/**
 * Eine einzelne Spur als GPX-1.1-Dokument.
 *
 * @param list<array{0:int,1:float,2:float,3:float|null,4:int}> $punkte
 *        wie `spur_lesen()` sie liefert
 * @param int $stufe SPUR_STUFE_ROH oder SPUR_STUFE_DUENN
 * @param int|null $nOriginal Punktzahl vor der Ausduennung; nur gesetzt, wenn
 *        sie sich von der gespeicherten unterscheidet
 */
function gpx_bauen(array $punkte, string $name, int $stufe,
                   ?int $nOriginal = null, ?int $erzeugtAm = null): string
{
    return gpx_bauen_viele([[
        'punkte' => $punkte, 'name' => $name,
        'stufe'  => $stufe,  'n_original' => $nOriginal,
    ]], $name, $erzeugtAm);
}

/**
 * Der Dateiname einer Spur.
 *
 * ER TRAEGT ART, LAUFENDE NUMMER, DATUM, UHRZEIT UND DIE STUFE. Diagnose,
 * Alter, Einsatzort und Einsatznummer liegen im `pat_blob` und sind fuer den
 * Server nicht lesbar (CLAUDE.md 4) — der Name kann sie also nicht
 * versehentlich tragen. Das ist einer der Gruende, warum diese Datei
 * serverseitig entsteht.
 *
 * „NUR KLARTEXT" HEISST NICHT „NICHTS PERSONENBEZOGENES". Datum und Uhrzeit
 * eines Einsatzes sind fuer sich genommen harmlos, in Verbindung mit einer
 * Ortsangabe aber ein Hinweis — und die Datei traegt beides. Der Dateiname
 * ist deshalb nicht der Ort, an dem etwas geschuetzt wird; er ist nur der
 * Ort, an dem VERSEHENTLICH nichts Geschuetztes landen kann. Was die Datei
 * als Ganzes bedeutet, sagt der Hinweis in der Oberflaeche.
 *
 * ALLES AUSSER [A-Za-z0-9._-] FAELLT WEG. Ein Dateiname geht durch eine
 * HTTP-Kopfzeile, ein Dateisystem und moeglicherweise durch ein Archiv; ein
 * Anfuehrungszeichen oder ein Zeilenumbruch darin waere eine Einladung zur
 * Kopfzeilen-Einschleusung.
 */
function gpx_dateiname(string $art, int $id, ?string $wann, int $stufe): string
{
    $zeit = '';
    if ($wann !== null && $wann !== '') {
        try {
            $d = new DateTime($wann, new DateTimeZone('UTC'));
            $zeit = '_' . $d->format('Y-m-d_Hi');
        } catch (Throwable $ex) { $zeit = ''; }
    }
    $name = sprintf('%s_%06d%s_%s.gpx',
        $art === 'rest' ? 'ruhezeit' : 'einsatz',
        $id, $zeit, gpx_stufe_marke($stufe));
    return preg_replace('/[^A-Za-z0-9._-]/', '', $name) ?? 'spur.gpx';
}

/**
 * Der Dateiname einer Auswahl mehrerer Spuren eines Diensttages.
 *
 * ER TRAEGT DATUM, ANZAHL UND STUFE — nach denselben Regeln wie
 * `gpx_dateiname()`: kein geschuetztes Feld (der Server kann keines lesen),
 * und alles ausser [A-Za-z0-9._-] faellt weg.
 *
 * Die Anzahl steht drin, weil eine Auswahl kein Ganzes ist: „diensttag_
 * 2026-08-31.gpx" liest sich wie der ganze Tag, auch wenn drei von zwoelf
 * Spuren drinstehen. Wer zweimal eine andere Auswahl herunterlaedt, bekommt
 * sonst zweimal denselben Namen und im Downloadordner ein „(1)".
 *
 * @param list<int> $stufen die vorkommenden Stufen
 */
function gpx_dateiname_tag(string $datum, int $anzahl, array $stufen): string
{
    $marke = count(array_unique($stufen)) > 1
        ? 'gemischt'
        : gpx_stufe_marke((int)($stufen[0] ?? SPUR_STUFE_ROH));
    $name = sprintf('diensttag_%s_%d-spuren_%s.gpx',
                    $datum !== '' ? $datum : 'ohne-datum', $anzahl, $marke);
    return preg_replace('/[^A-Za-z0-9._-]/', '', $name) ?? 'spuren.gpx';
}
