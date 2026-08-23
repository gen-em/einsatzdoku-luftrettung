<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Gemeinsame Pruefschicht fuer Einsatzdaten (Baustein B1, samt B2).
 *
 * WARUM ES DIESE DATEI GIBT
 * Dieselben Tabellen werden ueber VIER unabhaengige Wege beschrieben:
 *
 *   Formular   einsatz_form.php        eigene Eingabe          Sitzung ja
 *   Uhr        ingest.php              Geraet mit Schluessel   Sitzung nein
 *   Import     api/import_commit.php   Datei ueber den Browser Sitzung ja
 *   Sicherung  backup_lib.php          Datei beliebiger Herkunft
 *
 * Die Pruefungen liefen bisher an vier Stellen mit vier verschiedenen
 * Massstaeben — und zwar UMGEKEHRT zur Vertrauenswuerdigkeit der Quelle: Der
 * Import prueft neun Eigenschaften, das Wiedereinspielen einer Sicherung keine
 * einzige. Diese Datei ist die eine Stelle, auf die alle vier Wege umgestellt
 * werden.
 *
 * HERKUNFT DER REGELN
 * Die Pruefungen sind nicht neu erfunden, sondern aus api/import_commit.php
 * hierher gehoben — dort waren sie bereits geschrieben und erprobt. Neu sind
 * ausschliesslich: die Kalendertagspruefung (B2), die einheitlichen Grenzen
 * des Patientenblocks und die Ursachenmeldung.
 *
 * LEITPRINZIP "KEIN STILLES SCHEITERN"
 * Jede Funktion unterscheidet zwei Faelle, die frueher beide als "null"
 * endeten:
 *   - Der Wert war NICHT VORHANDEN  -> null, keine Meldung
 *   - Der Wert war UNGUELTIG        -> null UND ein Eintrag in der Pruefliste
 * Nur so kann die aufrufende Stelle zwischen "nichts zu tun" und "etwas ging
 * schief" unterscheiden. Wer keine Pruefliste uebergibt, bekommt das alte
 * Verhalten (stilles null) — das ist waehrend der schrittweisen Umstellung
 * beabsichtigt.
 *
 * Diese Datei aendert von sich aus nichts. Sie wird von den vier Schreibwegen
 * schrittweise in Dienst genommen.
 */

/* ---------------------------------------------------------------------------
 * Grenzen und Mengen — eine Quelle fuer alle vier Schreibwege
 * ------------------------------------------------------------------------ */

/**
 * Untergrenze des verschluesselten Patientenblocks.
 *
 * Herleitung, nicht geraten: AES-256-GCM legt vor den Chiffretext einen
 * Zufallswert von 12 Byte und haengt einen Pruefwert von 16 Byte an. Auch bei
 * voellig leerem Klartext sind das 28 Byte, in base64 also 40 Zeichen. Kuerzer
 * KANN ein gueltiger Block nicht sein.
 *
 * Im Umlauf waren bisher drei verschiedene Untergrenzen: 16 (Formular),
 * 20 (Import) und gar keine (Sicherung) — alle drei unterhalb des
 * ueberhaupt Moeglichen oder nicht vorhanden.
 */
const PAT_BLOB_MIN = 40;

/**
 * Obergrenze des verschluesselten Patientenblocks.
 *
 * 60000 Zeichen = 60000 Byte (base64 ist reines ASCII). Die Spalte ist ein
 * TEXT und fasst 65535 Byte; es bleiben also 5535 Byte Luft. Das entspricht
 * rund 44972 Byte Klartext.
 *
 * Die Grenze bleibt bewusst erhalten. Ohne sie entscheidet die Datenbank, und
 * ihre Entscheidung ist entweder stilles Abschneiden — ein abgeschnittener
 * Chiffretext ist DAUERHAFT unlesbar — oder ein Abbruch.
 */
const PAT_BLOB_MAX = 60000;

/**
 * Formatkennung vor einem Chiffretext (M2-10).
 *
 * WARUM ES SIE GIBT
 * Ein Chiffretext bestand bisher aus Zufallswert und Nutzdaten, ohne jede
 * Angabe darueber, mit welchem Verfahren er entstanden ist. Wird das Verfahren
 * je gewechselt — und irgendwann wird es das —, gibt es kein Merkmal, an dem
 * sich alt von neu unterscheiden liesse. Der Sicherungscontainer macht es seit
 * jeher richtig vor: Er traegt eine Fassungsnummer im Kopf.
 *
 * Der Doppelpunkt gehoert NICHT zum base64-Zeichenvorrat. Die Kennung ist
 * deshalb eindeutig zu erkennen, ohne etwas zu entschluesseln — anders als ein
 * Kennungsbyte INNERHALB der Daten, das man von einem Zufallswert nur durch
 * Ausprobieren unterscheiden koennte.
 *
 * BEIM LESEN GROSSZUEGIG: Ein Chiffretext OHNE Kennung ist die erste Fassung.
 * Es gibt keine Umstellung des Bestands — der Server kann sie nicht
 * entschluesseln und die Kennung deshalb nicht nachtragen. Beide Formen stehen
 * dauerhaft nebeneinander; ein Datensatz bekommt die Kennung, wenn er das
 * naechste Mal gespeichert wird.
 */
const CHIFFRE_PRAEFIX = 'edk1:';
const CHIFFRE_PRAEFIX_RE = '(?:edk1:)?';

/** Zeichenvorrat des Chiffretexts (base64 mit Fuellzeichen), mit oder ohne
 *  Formatkennung. Die Laengengrenzen gelten fuer den base64-Teil. */
const PAT_BLOB_RE = '#^' . CHIFFRE_PRAEFIX_RE
                  . '[A-Za-z0-9+/=]{' . PAT_BLOB_MIN . ',' . PAT_BLOB_MAX . '}$#';

/**
 * Zeichenvorrat einer Schluesselhuelle (pat_wrap_pw, pat_wrap_rc).
 *
 * Stand bis Web 5.0.1 dreimal im Projekt: als Konstante in pw_handling.php und
 * als wortgleiche Zeichenkette in einstellungen.php und api/kdf_upgrade.php.
 * Mit der Formatkennung waeren daraus drei Stellen geworden, die man einzeln
 * haette nachziehen muessen — und eine vergessene haette eine gueltige Huelle
 * abgewiesen, mit dem Ergebnis, dass ein Passwortwechsel scheitert.
 */
const WRAP_RE = '#^' . CHIFFRE_PRAEFIX_RE . '[A-Za-z0-9+/=]{20,4000}$#';

/**
 * Mengenbegrenzungen je Einsatz.
 *
 * Zur Phasenzahl: Mehrfache Eintraege derselben Phasennummer sind
 * AUSDRUECKLICH ERLAUBT (JSON-Vertrag) — eine erneut gesetzte Phase ist eine
 * Korrektur und damit eine Information, die erhalten bleibt. Die Grenze ist
 * deshalb bewusst hoch angesetzt: Sie schuetzt vor einer entgleisten Nutzlast
 * und darf nicht als Ueberlaufschutz fuer die Entdoppelung herhalten, die es
 * nicht mehr geben soll.
 */
const LIMIT_PHASEN      = 500;
const LIMIT_REA_SESSION = 20;
const LIMIT_REA_EREIGN  = 200;
const LIMIT_RESSOURCEN  = 40;
const LIMIT_TRACKPUNKTE = 2000;

/** Wertebereich der Phasennummern. Phase 1 ist "Frei" (kein Zeitstempel),
 *  Phase 10 wurde mit 2026_07_19_phase10_entfernen abgeschafft. */
const PHASE_MIN = 2;
const PHASE_MAX = 9;

/* ---------------------------------------------------------------------------
 * Pruefliste — sammelt Ursachen, damit L1 erfuellbar ist
 * ------------------------------------------------------------------------ */

/**
 * Sammelt die Gruende, aus denen Werte verworfen wurden.
 *
 * Der Sinn ist nicht die schoene Fehlermeldung, sondern die Unterscheidbarkeit:
 * Wer 40 uebersprungene Einsaetze sieht, kann heute nicht erkennen, ob das gut
 * ist (alles schon da) oder schlecht (alles kaputt). Mit der Aufschluesselung
 * nach Ursache kann er es.
 */
final class Pruefliste
{
    /** @var array<int, array{feld: string, grund: string}> */
    private array $eintraege = [];

    /* ENTFALLEN MIT A4 (T-02 bis T-04): anzahl(), eintraege() und setBezug().
     *
     * Die vier Nutzer der Klasse — ingest.php, api/import_commit.php,
     * backup_lib.php und einsatz_form.php — benutzen ausschliesslich melde(),
     * sauber(), nachUrsache() und text(); die drei anderen Methoden hatte in
     * der gesamten Historie nie jemand aufgerufen.
     *
     * Mit setBezug() faellt das MERKMAL 'bezug' ganz weg, und zwar nicht als
     * Beifang, sondern weil es transitiv tot war: Ohne Aufrufer blieb $bezug
     * immer der Leerstring, jeder Eintrag trug also ein leeres Feld, und
     * gelesen hat es ohnehin niemand — nachUrsache() wertet 'feld' und
     * 'grund' aus, text() ebenso. */

    public function melde(string $feld, string $grund): void
    {
        $this->eintraege[] = ['feld' => $feld, 'grund' => $grund];
    }

    public function sauber(): bool { return $this->eintraege === []; }

    /**
     * Zaehlung nach Ursache: ['Datum unmoeglicher Kalendertag' => 12, ...].
     * Fuer Antworten, die eine Zahl nennen muessen, aber keine Romane.
     * @return array<string, int>
     */
    public function nachUrsache(): array
    {
        $out = [];
        foreach ($this->eintraege as $e) {
            $k = $e['feld'] . ': ' . $e['grund'];
            $out[$k] = ($out[$k] ?? 0) + 1;
        }
        arsort($out);
        return $out;
    }

    /** Kurzfassung fuer eine Meldung an die NutzerIn. */
    public function text(int $maxZeilen = 5): string
    {
        if ($this->sauber()) { return ''; }
        $teile = [];
        $i = 0;
        foreach ($this->nachUrsache() as $ursache => $n) {
            if ($i++ >= $maxZeilen) { $teile[] = '…'; break; }
            $teile[] = $n > 1 ? "$ursache ({$n}×)" : $ursache;
        }
        return implode('; ', $teile);
    }
}

/* ---------------------------------------------------------------------------
 * Einzelpruefungen
 * ------------------------------------------------------------------------ */

/**
 * Zeichenkette auf Spaltenlaenge zuschneiden.
 *
 * Zuschneiden statt ablehnen ist hier richtig: Ein zu langer Freitext ist
 * keine Falscheingabe, sondern eine zu lange Eingabe. Gemeldet wird trotzdem —
 * sonst verschwindet der abgeschnittene Teil unbemerkt.
 */
function pruef_text($wert, int $max, string $feld = 'Text', ?Pruefliste $p = null): ?string
{
    if ($wert === null) { return null; }
    if (is_array($wert) || is_object($wert)) {
        $p?->melde($feld, 'keine Zeichenkette');
        return null;
    }
    $s = trim((string)$wert);
    if ($s === '') { return null; }                    // nicht vorhanden
    if (mb_strlen($s) > $max) {
        $p?->melde($feld, "laenger als {$max} Zeichen — gekuerzt");
        $s = mb_substr($s, 0, $max);
    }
    return $s;
}

/**
 * Ganzzahl im erlaubten Bereich.
 *
 * Bei Unsinn kommt NULL zurueck, nicht 0. Eine 0 wuerde eine Messung
 * vortaeuschen, die es nie gab — bei Flugstrecke oder Steigung ist das ein
 * Unterschied, der in jeder Jahresstatistik landet.
 */
function pruef_zahl($wert, int $min, int $max, string $feld = 'Zahl', ?Pruefliste $p = null): ?int
{
    if ($wert === null || $wert === '') { return null; }
    if (!is_numeric($wert)) {
        $p?->melde($feld, 'keine Zahl');
        return null;
    }
    $n = (int)$wert;
    if ($n < $min || $n > $max) {
        $p?->melde($feld, "ausserhalb von {$min}…{$max}");
        return null;
    }
    return $n;
}

/** Ja/Nein-Marker. Kennt keinen Fehlerfall — alles, was nicht leer ist, gilt. */
function pruef_flag($wert): int
{
    return !empty($wert) ? 1 : 0;
}

/**
 * Kalendertag "JJJJ-MM-TT" (Baustein B2).
 *
 * HINTERGRUND, und der ist der eigentliche Grund fuer diesen Baustein:
 * Die Datumsumwandlung liefert bei einem unmoeglichen Tag KEIN Fehlerergebnis,
 * sondern rechnet weiter — aus dem 30. Februar wird der 2. Maerz. Der Fehler
 * wird ausschliesslich ueber die Warnungsabfrage der Datumsklasse sichtbar,
 * und die wurde nirgends abgefragt. Ein Tippfehler in der Importdatei wurde so
 * zu einem stillen Datumssprung.
 *
 * Dieselbe Luecke besteht bei der einfachen Zeitstempelumwandlung: Sie faengt
 * einen unmoeglichen MONAT ab, einen unmoeglichen TAG aber nicht.
 *
 * Behandlung wie bei einer unmoeglichen Uhrzeit, die bereits richtig geloest
 * ist (siehe local_to_utc in db.php): Nullwert statt Weiterrechnen.
 */
function pruef_kalendertag($wert, string $feld = 'Datum', ?Pruefliste $p = null): ?string
{
    if ($wert === null || $wert === '') { return null; }
    $s = trim((string)$wert);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $s)) {
        $p?->melde($feld, 'kein Datum im Format JJJJ-MM-TT');
        return null;
    }
    // '!' setzt alle nicht genannten Bestandteile auf null — sonst uebernimmt
    // die Umwandlung die aktuelle Uhrzeit und die Warnungspruefung wird
    // von einer Sekunde Laufzeitunterschied abhaengig.
    $dt = DateTime::createFromFormat('!Y-m-d', $s, new DateTimeZone('UTC'));
    if ($dt === false || !datum_ohne_warnung()) {
        $p?->melde($feld, 'unmoeglicher Kalendertag');
        return null;
    }
    return $dt->format('Y-m-d');
}

/**
 * Hat die letzte Datumsumwandlung sauber gearbeitet?
 *
 * Ab PHP 8.2 liefert getLastErrors() false, wenn es nichts zu berichten gibt;
 * davor ein Feld mit zwei Zaehlern. Beide Faelle werden hier abgedeckt, damit
 * die Pruefung nicht von der PHP-Fassung des Hosters abhaengt.
 */
function datum_ohne_warnung(): bool
{
    $err = DateTime::getLastErrors();
    if ($err === false || $err === null) { return true; }
    return (int)($err['warning_count'] ?? 0) === 0 && (int)($err['error_count'] ?? 0) === 0;
}

/**
 * Zeitstempel der Uhr: "2026-03-14T09:50:00Z" -> "2026-03-14 09:50:00" (UTC).
 * Die Sekunden sind optional, der Zonenkennbuchstabe nicht — eine Zeit ohne
 * Zone waere nicht eindeutig.
 */
function pruef_utc($wert, string $feld = 'Zeitpunkt', ?Pruefliste $p = null): ?string
{
    if ($wert === null || $wert === '') { return null; }
    if (!is_string($wert)) {
        $p?->melde($feld, 'kein Zeitstempel');
        return null;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?Z$/', $wert)) {
        $p?->melde($feld, 'kein UTC-Zeitstempel (JJJJ-MM-TTThh:mm[:ss]Z)');
        return null;
    }
    // Der Kalendertag wird vorab eigens geprueft: Die Umwandlung unten
    // wuerde den 30. Februar sonst klaglos verschieben (siehe B2).
    if (pruef_kalendertag(substr($wert, 0, 10), $feld, $p) === null) { return null; }
    try {
        $dt = new DateTime($wert, new DateTimeZone('UTC'));
    } catch (Throwable $ex) {
        $p?->melde($feld, 'kein gueltiger Zeitpunkt');
        return null;
    }
    return $dt->format('Y-m-d H:i:s');
}

/**
 * Zeitstempel in UTC — entweder im Uhr-Format oder in Datenbankschreibweise.
 *
 * Der Uhr-Weg liefert "2026-03-14T09:50:00Z", eine Sicherungsdatei dagegen
 * "2026-03-14 09:50:00", weil sie die Werte so uebernimmt, wie sie in der
 * Datenbank stehen. Beide meinen dasselbe. Diese Funktion nimmt beides an —
 * damit nicht wieder zwei Massstaebe entstehen, nur weil zwei Formate im
 * Umlauf sind.
 */
function pruef_utc_oder_sql($wert, string $feld = 'Zeitpunkt', ?Pruefliste $p = null): ?string
{
    if ($wert === null || $wert === '') { return null; }
    if (!is_string($wert)) {
        $p?->melde($feld, 'kein Zeitstempel');
        return null;
    }
    $s = trim($wert);
    // Uhr-Format: an das Z erkennbar
    if (str_ends_with($s, 'Z')) { return pruef_utc($s, $feld, $p); }

    if (!preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{2}):(\d{2})(:(\d{2}))?$/', $s, $t)) {
        $p?->melde($feld, 'kein Zeitstempel (JJJJ-MM-TT hh:mm[:ss])');
        return null;
    }
    if (pruef_kalendertag($t[1], $feld, $p) === null) { return null; }
    if ((int)$t[2] > 23 || (int)$t[3] > 59 || (int)($t[5] ?? 0) > 59) {
        $p?->melde($feld, 'Uhrzeit ausserhalb 00:00:00…23:59:59');
        return null;
    }
    return $t[1] . ' ' . $t[2] . ':' . $t[3] . ':' . (($t[5] ?? '') !== '' ? $t[5] : '00');
}

/**
 * Ortszeit (App-Zeitzone) -> UTC, mit Kalendertagspruefung.
 *
 * Gegenstueck zu local_to_utc() in db.php, um B2 erweitert. Die dortige
 * Fassung prueft Muster und Wertebereich der UHRZEIT bereits richtig, den
 * Kalendertag aber nicht.
 *
 * $addDays deckt Zeiten nach Mitternacht ab, die noch zum Diensttag gehoeren.
 */
function pruef_ortszeit_zu_utc($tag, $hhmm, int $addDays = 0,
                               string $feld = 'Uhrzeit', ?Pruefliste $p = null): ?string
{
    $tagOk = pruef_kalendertag($tag, $feld . ' (Datum)', $p);
    if ($tagOk === null) { return null; }
    if ($hhmm === null || $hhmm === '') { return null; }
    $s = trim((string)$hhmm);
    if (!preg_match('/^(\d{2}):(\d{2})$/', $s, $t)) {
        $p?->melde($feld, 'keine Uhrzeit im Format hh:mm');
        return null;
    }
    if ((int)$t[1] > 23 || (int)$t[2] > 59) {
        $p?->melde($feld, 'Uhrzeit ausserhalb 00:00…23:59');
        return null;
    }
    $utc = local_to_utc($tagOk, $s, $addDays);
    if ($utc === null) { $p?->melde($feld, 'nicht in UTC umrechenbar'); }
    return $utc;
}

/**
 * Geografische Koordinate.
 *
 * Ungeprueft gingen die Werte bisher vom Uhr-Weg aus in die Phasen, in die
 * Spur UND in die Hoehenberechnung des Einsatzorts. Ein Ausreisser dort
 * verschiebt nicht nur einen Kartenpunkt, sondern auch eine berechnete Zahl.
 */
function pruef_koordinate($wert, float $grenze, string $feld = 'Koordinate', ?Pruefliste $p = null): ?float
{
    if ($wert === null || $wert === '') { return null; }
    if (!is_numeric($wert)) {
        $p?->melde($feld, 'keine Zahl');
        return null;
    }
    $f = (float)$wert;
    if (!is_finite($f) || $f < -$grenze || $f > $grenze) {
        // Ganzzahlig ausgeben, ohne Nachkommastellen abzuschneiden. Die
        // fruehere Fassung strich nachlaufende Nullen und machte aus der
        // Grenze 90 die Meldung "ausserhalb von ±9" — eine Fehlermeldung,
        // die selbst falsch ist, kostet mehr Zeit als gar keine.
        $p?->melde($feld, 'ausserhalb von ±' . (string)(int)$grenze);
        return null;
    }
    return $f;
}

function pruef_breite($wert, string $feld = 'Breitengrad', ?Pruefliste $p = null): ?float
{
    return pruef_koordinate($wert, 90.0, $feld, $p);
}

function pruef_laenge($wert, string $feld = 'Laengengrad', ?Pruefliste $p = null): ?float
{
    return pruef_koordinate($wert, 180.0, $feld, $p);
}

/**
 * Ein Koordinaten-PAAR aus einem Formular (Web 6.1.0, E37/E39).
 *
 * Optionale Koordinaten gibt es seit Web 6.0.0 an Standorten und Zielkliniken,
 * seit 6.1.0 zusaetzlich am Einsatz (Zielklinik) — an fuenf Stellen, und an
 * dreien stand dieselbe kleine Umrechnung ausgeschrieben. Sie liegt jetzt hier.
 *
 * DREI REGELN, die alle drei Fassungen gemeinsam hatten:
 *
 * 1. NUR ZUSAMMEN. Eine Breite ohne Laenge ist kein Ort; ein halbes Paar wird
 *    ganz verworfen statt zu einem stillen 0/0 zu werden — das laege im Golf
 *    von Guinea, mitten in der Auswertung.
 * 2. AUSSERHALB DES BEREICHS IST LEER, nicht abgeschnitten. Eine gekappte
 *    Koordinate zeigte auf einen anderen Ort, und zwar ohne Hinweis.
 * 3. KOMMA IST ZULAESSIG. Wer eine Koordinate aus einer deutschsprachigen
 *    Anwendung kopiert, bringt es mit.
 *
 * Rueckgabe als Zeichenkette mit sechs Nachkommastellen — dem Format der
 * Spalten DECIMAL(9,6). Ohne gueltiges Paar zweimal null.
 *
 * @return array{0:?string,1:?string}
 */
function pruef_ortspaar($lat, $lon): array
{
    $zahl = static function ($w): ?string {
        if ($w === null) { return null; }
        $s = str_replace(',', '.', trim((string)$w));
        return $s === '' ? null : $s;
    };
    $la = pruef_breite($zahl($lat));
    $lo = pruef_laenge($zahl($lon));
    if ($la === null || $lo === null) { return [null, null]; }
    return [number_format($la, 6, '.', ''), number_format($lo, 6, '.', '')];
}

/** Phasennummer 2 bis 9. */
function pruef_phase($wert, string $feld = 'Phase', ?Pruefliste $p = null): ?int
{
    if ($wert === null || $wert === '') { return null; }
    if (!is_numeric($wert)) {
        $p?->melde($feld, 'keine Phasennummer');
        return null;
    }
    $n = (int)$wert;
    if ($n < PHASE_MIN || $n > PHASE_MAX) {
        $p?->melde($feld, 'ausserhalb von ' . PHASE_MIN . '…' . PHASE_MAX);
        return null;
    }
    return $n;
}

/**
 * Reanimationsart gegen die bekannte Liste.
 *
 * Ein freier Text waere im Formular spaeter nicht darstellbar — die Anzeige
 * kennt nur die Schluessel aus RESUS_LABELS.
 */
function pruef_reanimationsart($wert, string $feld = 'Reanimationsart', ?Pruefliste $p = null): ?string
{
    if ($wert === null || $wert === '') { return null; }
    $s = mb_substr(trim((string)$wert), 0, 24);
    if ($s === '') { return null; }
    if (!array_key_exists($s, RESUS_LABELS)) {
        $p?->melde($feld, 'unbekannte Art');
        return null;
    }
    return $s;
}

/**
 * Verschluesselter Patientenblock.
 *
 * Der INHALT geht den Server nichts an — er kann ihn nach Bauart nicht lesen.
 * Geprueft werden ausschliesslich Zeichenvorrat und Laenge. Was hier nicht
 * nach base64 aussieht, waere entweder ein Fehler oder Klartext; beides wird
 * nicht gespeichert.
 *
 * WICHTIG: Eine Verletzung erzeugt eine MELDUNG. Frueher wurde die Spalte
 * einfach nicht in die Aktualisierung aufgenommen — kein Fehler, keine
 * Meldung, der bisherige Block blieb stehen. Wer eine Diagnose korrigiert und
 * "gespeichert" liest, hat dann die alte Diagnose in der Datenbank.
 */
function pruef_pat_blob($wert, string $feld = 'Patientenblock', ?Pruefliste $p = null): ?string
{
    if ($wert === null) { return null; }
    if (!is_string($wert)) {
        $p?->melde($feld, 'kein Chiffretext');
        return null;
    }
    $s = trim($wert);
    if ($s === '') { return null; }                    // bewusst ohne Angaben
    if (!preg_match(PAT_BLOB_RE, $s)) {
        $laenge = strlen($s);
        $grund = $laenge < PAT_BLOB_MIN
            ? 'kuerzer als ' . PAT_BLOB_MIN . ' Zeichen (kein gueltiger Chiffretext moeglich)'
            : ($laenge > PAT_BLOB_MAX
                ? 'laenger als ' . PAT_BLOB_MAX . ' Zeichen'
                : 'kein base64');
        $p?->melde($feld, $grund);
        return null;
    }
    return $s;
}

/**
 * Mengenbegrenzung einer Liste.
 *
 * Schneidet ab und meldet. Ein stilles Abschneiden waere hier besonders
 * unangenehm: Es traefe immer das ENDE einer Zeitreihe, also die spaeten
 * Phasen eines langen Einsatzes.
 */
function pruef_menge($liste, int $max, string $feld = 'Liste', ?Pruefliste $p = null): array
{
    if (!is_array($liste)) {
        if ($liste !== null) { $p?->melde($feld, 'keine Liste'); }
        return [];
    }
    if (count($liste) > $max) {
        $p?->melde($feld, 'mehr als ' . $max . ' Eintraege — Rest verworfen');
        return array_slice($liste, 0, $max);
    }
    return $liste;
}

/**
 * Ist die Nutzlast eine LISTE und kein Objekt?
 *
 * Der Unterschied ist im JSON eindeutig, in PHP nach dem Einlesen aber nicht
 * mehr: Beides wird zu einem Feld. Wo der Vertrag eine Liste zusichert, muss
 * das geprueft werden, sonst wandert ein Objekt mit den Schluesseln "0", "1"
 * unbemerkt durch die Verarbeitung.
 */
function ist_liste($wert): bool
{
    return is_array($wert) && ($wert === [] || array_is_list($wert));
}
