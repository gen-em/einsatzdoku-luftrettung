<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Gemeinsame Pruefschicht fuer Einsatzdaten.
 *
 * WARUM ES DIESE DATEI GIBT
 * Dieselben Tabellen werden ueber VIER unabhaengige Wege beschrieben:
 *
 *   Formular   einsatz_form.php        eigene Eingabe          Sitzung ja
 *   Uhr        ingest.php              Geraet mit Schluessel   Sitzung nein
 *   Import     api/import_commit.php   Datei ueber den Browser Sitzung ja
 *   Backup  backup_lib.php          Datei beliebiger Herkunft
 *
 * Vier Stellen mit vier Massstaeben laufen auseinander — und zwar gern
 * UMGEKEHRT zur Vertrauenswuerdigkeit der Quelle: Eine Datei beliebiger
 * Herkunft verdient die strengste Pruefung, nicht die mildeste. Diese Datei
 * ist die eine Stelle fuer alle vier Wege.
 *
 * HERKUNFT DER REGELN
 * Die Pruefungen stammen aus dem Import, wo sie geschrieben und erprobt
 * waren; dazu kommen die Kalendertagspruefung, die einheitlichen Grenzen
 * des Patientenblocks und die Ursachenmeldung.
 *
 * LEITPRINZIP "KEIN STILLES SCHEITERN"
 * Jede Funktion unterscheidet zwei Faelle, die sonst beide als "null"
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
 * 20 (Import) und gar keine (Backup) — alle drei unterhalb des
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
 * Formatkennung vor einem Chiffretext.
 *
 * WARUM ES SIE GIBT
 * Ein Chiffretext bestand bisher aus Zufallswert und Nutzdaten, ohne jede
 * Angabe darueber, mit welchem Verfahren er entstanden ist. Wird das Verfahren
 * je gewechselt — und irgendwann wird es das —, gibt es kein Merkmal, an dem
 * sich alt von neu unterscheiden liesse. Der Backup-Container macht es seit
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
 * Kennung einer Schluesselhuelle MIT Server-Anteil.
 *
 * Eine solche Huelle lautet `edka1:<kennung>:<base64>`, wobei <kennung> die
 * acht Hexzeichen des Anteils sind, mit dem sie gebaut wurde. Der Chiffretext
 * dahinter ist derselbe AES-256-GCM-Aufbau wie bisher; was dazukommt, ist die
 * Auskunft, WELCHER Schluessel sie oeffnet.
 *
 * WARUM NICHT SCHLICHT `edk2:`. Zwei Gruende, und beide sind praktisch.
 * Erstens weist `EdCrypto.decrypt()` jede Kennung ausser `edk1:` als
 * „neuere Programmfassung" ab — die Fassung der HUELLE ist aber etwas anderes
 * als die Fassung des Verfahrens, und die beiden zu vermengen hiesse, jedem
 * Huellenwechsel eine Fehlermeldung mitzugeben. Zweitens braucht der Browser
 * bei einer Rotation die Auskunft, mit WELCHEM von zwei Anteilen er oeffnet,
 * und die Statusseite muss zaehlen koennen, wer noch auf dem alten steht —
 * beides steht im Praefix und ist lesbar, ohne dass jemand etwas oeffnet.
 *
 * `pat_wrap_rc` bekommt KEIN solches Praefix: Der Wiederherstellungsschluessel
 * haengt nicht am Anteil, und das ist der Rueckweg, wenn der Anteil verloren
 * geht.
 */
const HUELLE_PRAEFIX = 'edka1:';

/**
 * Zeichenvorrat einer Schluesselhuelle (pat_wrap_pw, pat_wrap_rc).
 *
 * EINE Stelle: Drei wortgleiche Fassungen (pw_handling.php, einstellungen.php,
 * api/kdf_upgrade.php) muesste man einzeln nachziehen — und eine vergessene
 * wiese eine gueltige Huelle ab, mit dem Ergebnis, dass ein Passwortwechsel
 * scheitert.
 *
 * ZWEI KENNUNGEN SIND ERLAUBT, und zwar nur hier — `PAT_BLOB_RE`
 * bleibt unberuehrt. Das ist kein Versehen: Der Anteil steckt im
 * DATENSCHLUESSEL, und der oeffnet ausschliesslich die Huelle. Der
 * Inhaltsschluessel darin und damit jeder `pat_blob` sind unveraendert —
 * kein Datensatz wird angefasst, wenn ein Konto umstellt.
 */
const WRAP_PRAEFIX_RE = '(?:edk1:|edka1:[0-9a-f]{8}:)?';

/**
 * ZWEI AUSDRUECKE, WEIL ES ZWEI HUELLEN MIT VERSCHIEDENEN ZUSAGEN SIND.
 * 
 *
 * Pruefte EINE Regel beide Huellen, gaelte die Kennung `edka1:`
 * stillschweigend auch fuer `pat_wrap_rc` — und damit koennte eine
 * Wiederherstellungs-Huelle am Server-Anteil haengen, ohne dass irgendetwas
 * es aufhielte.
 *
 * DAS WAERE DER VERLUST DES RUECKWEGS, also genau dessen, was die Zusage
 * verspricht: `pat_wrap_rc` oeffnet OHNE Anteil, und deshalb ist der Verlust
 * des Anteils kein Datenverlust. Eine `edka1:`-Huelle dort waere
 * ein Konto, das nach dem Verlust des Anteils nicht mehr zu retten ist — und
 * man saehe es dem Feld nicht an, bis es zu spaet ist.
 *
 * Deshalb gilt: `WRAP_PW_RE` nimmt beide Kennungen, `WRAP_RC_RE` nur die
 * alte. `WRAP_RE` bleibt als Name bestehen und ist `WRAP_PW_RE` — die
 * Aufrufer, die beide Huellen pruefen, stehen in pw_handling.php und meinen
 * dort die Passwort-Huelle.
 */
const WRAP_PW_RE = '#^' . WRAP_PRAEFIX_RE . '[A-Za-z0-9+/=]{20,4000}$#';
const WRAP_RC_RE = '#^' . CHIFFRE_PRAEFIX_RE . '[A-Za-z0-9+/=]{20,4000}$#';
const WRAP_RE    = WRAP_PW_RE;

/**
 * Das Schlüsselpaar des Rückwegs.
 *
 * `RW_OEFFENTLICH_RE`: der öffentliche Teil als SPKI in Base64 — für P-256
 * genau 124 Zeichen; der Spielraum 100 bis 200 lässt einen Kodierer mit
 * anderer Auffüllung durch, keinen Aufsatz. Ob es wirklich ein P-256-Schlüssel
 * ist, prüft `rw_oeffentlich_pruefen()`; die Regel hier hält nur die Form.
 *
 * `RW_PRIVAT_RE` IST `WRAP_RC_RE`, mit Absicht: Der private Teil liegt unter
 * dem INHALTSSCHLÜSSEL (`edk1:`) und muss mit dem Wiederherstellungsschlüssel
 * allein aufgehen — wie `pat_wrap_rc`. Eine `edka1:`-Fassung hinge am
 * Server-Anteil, und der Rückweg ginge mit dem Anteil verloren.
 */
const RW_OEFFENTLICH_RE = '#^[A-Za-z0-9+/]{100,200}={0,2}$#';
const RW_PRIVAT_RE      = WRAP_RC_RE;

/**
 * Das Schlüsselpaar des Kontos.
 *
 * Dieselben Formen wie beim Rückweg, aus demselben Grund: Der öffentliche
 * Teil ist SPKI in Base64 (ECDH auf P-256 hat dieselbe SPKI wie ECDSA), der
 * private liegt unter dem INHALTSSCHLÜSSEL (`edk1:`) und muss mit dem
 * Wiederherstellungsschlüssel allein aufgehen. Eine `edka1:`-Fassung hinge
 * am Server-Anteil — ginge der verloren, wären mit dem Paar alle Spuren,
 * Phasenkoordinaten und Reanimationen des Kontos verloren (`CLAUDE.md` 4).
 * Zwei Namen statt eines geteilten: Wer eines der Paare ändert, soll das
 * andere nicht mitändern.
 */
const KP_OEFFENTLICH_RE = '#^[A-Za-z0-9+/]{100,200}={0,2}$#';
const KP_PRIVAT_RE      = WRAP_RC_RE;

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
/* Die Sperrvermerke eines Einsatzes in einer Sicherung (Nutzlast 9).
 *
 * SIE STEHT HIER UND NICHT IN backup_lib.php, obwohl heute nur die Sicherung
 * sie braucht. Die Mengengrenzen sind Teil der gemeinsamen Pruefschicht
 * (CLAUDE.md 4); eine Grenze daneben waere die erste, die man an zwei Orten
 * suchen muesste.
 *
 * 200 IST REICHLICH UND SOLL ES SEIN. Ein Vermerk entsteht je Schnitt, und
 * ein Schnitt ist eine Handbewegung in der Tagesansicht — zweistellig je
 * Dienst waere schon viel. Wie ueberall hier schuetzt die Zahl vor einer
 * entgleisten Nutzlast, nicht vor Betrieb. */
const LIMIT_SCHNITTE    = 200;
/* ZWEI GRENZEN, WEIL ES ZWEI FRAGEN SIND. Die beiden Stellen meinen
 * Verschiedenes:
 *
 *   ingest.php        die Punkte EINER ANFRAGE. Die Uhr sendet in Stuecken zu
 *                     UPLOAD_CHUNK_POINTS = 500 (Const.mc); gemessen an den
 *                     526 Anfragen des Referenzlaufs sind es hoechstens 500.
 *                     2000 sind also vierfache Reserve — die Grenze greift
 *                     nie und schuetzt genau davor, wofuer sie da ist: einer
 *                     entgleisten Nutzlast.
 *
 *   backup_lib.php    die Punkte EINER GANZEN SPUR. Dort waere dieselbe
 *                     Grenze ein Datenverlust: Was die Uhr ueber viele
 *                     Anfragen aufbauen darf, wuerde beim Zurueckspielen
 *                     bei 2000 gekappt. Erreichbar ist das ohne weiteres — THIN_MAX_GAP_S
 *                     garantiert einen Punkt alle 10 s, THIN_MIN_GAP_S erlaubt
 *                     einen je Sekunde; 2000 Punkte sind damit zwischen 33
 *                     Minuten und 5,5 Stunden Aufzeichnung. Aufgefallen ist
 *                     es nie, weil die laengste Referenzspur 1133 Punkte hat.
 *
 * Deshalb jetzt getrennt. Und die Spurgrenze KAPPT NICHT MEHR, sondern lehnt
 * die ganze Spur ab (pruef_menge_streng): Eine halbe Spur sieht aus wie eine
 * ganze, eine abgelehnte sieht man.
 *
 * 50 000 Punkte sind 13,9 Stunden bei einem Punkt je Sekunde — laenger als
 * jeder Dienst. Als Blob sind das rund 178 KB; MEDIUMBLOB traegt 16 MB. Die
 * Zahl schuetzt also vor Unfug, nicht vor Betrieb.
 */
const LIMIT_TRACKPUNKTE_ANFRAGE = 2000;
const LIMIT_TRACKPUNKTE_SPUR    = 50000;

/* DIE GERAETEHUELLE IN DER PRUEFSCHICHT. Was der Server
 * nicht lesen kann, kann er nicht pruefen: Form und Groesse ja, Inhalt nein.
 * Den Inhalt pruefen das Geraet beim Erzeugen und der Browser beim Oeffnen.
 * Das Format steht in `tools/proben/huelle/referenz.py`:
 *
 *   Kennbyte 1 · EPK 65 · IV 16 · Chiffretext 16·n (n >= 1) · TAG 32
 *
 * also mindestens 130 Byte, und die Laenge ohne Kopf und TAG ein Vielfaches
 * von 16. Die Obergrenzen folgen aus den Mengengrenzen darueber, nicht aus
 * dem Gefuehl: Ein Spurstueck traegt hoechstens LIMIT_TRACKPUNKTE_ANFRAGE
 * Punkte zu 14 Byte, dazu Polsterung und Kopf. Die Einsatzhuelle
 * traegt JSON mit hoechstens LIMIT_PHASEN Phasen und LIMIT_REA_SESSION
 * Sitzungen zu LIMIT_REA_EREIGN Ereignissen; 256 KB sind das Vierfache des
 * groessten denkbaren Inhalts. */
const GH_MIN_BYTE         = 130;
const GH_STUECK_MAX_BYTE  = LIMIT_TRACKPUNKTE_ANFRAGE * 14 + 16 + 130;
/** Ein gepacktes Stueck (Stufe 2/3): hoechstens die ganze Spur,
 *  16 Byte je Punkt ungepackt, dazu was `deflate` im schlimmsten Fall anhaengt. */
const GH_GEPACKT_MAX_BYTE = LIMIT_TRACKPUNKTE_SPUR * 16 + 1024 + 130;
const GH_EINSATZ_MAX_BYTE = 262144;

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

    /* KEIN anzahl(), eintraege() oder setBezug(), und kein MERKMAL 'bezug'.
     *
     * Die vier Nutzer der Klasse — ingest.php, api/import_commit.php,
     * backup_lib.php und einsatz_form.php — benutzen ausschliesslich melde(),
     * sauber(), nachUrsache() und text(). Ein Merkmal 'bezug' laese niemand —
     * nachUrsache() wertet 'feld' und 'grund' aus, text() ebenso. */

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
 * Kalendertag "JJJJ-MM-TT".
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
    // wuerde den 30. Februar sonst klaglos verschieben.
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
 * Der Uhr-Weg liefert "2026-03-14T09:50:00Z", eine Backup-Datei dagegen
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
 * Passt ein Zeitpunkt zum Kalendertag, unter dem er gemeldet wird?
 *
 * Die Uhr schickt zu jedem Datensatz einen `day` (Ortsdatum) UND Zeitpunkte
 * (UTC). Beide kommen aus derselben Quelle, und ohne Pruefung
 * sieht niemand nach, ob sie zueinander passen: Ein Paket mit
 * `day` 2026-08-09 und `started_at` 2001-01-01 wuerde angenommen, der Einsatz
 * mit 96 Jahren Dauer gespeichert und der Zeitraum des Diensttags darauf
 * gezogen. Dagegen hilft keine Fensterregel, sondern nur die Frage,
 * ob die Angaben desselben Pakets einander widersprechen.
 *
 * DAS FENSTER IST BEWUSST SEHR WEIT: von Mitternacht des Vortags bis zum Ende
 * des 31. Tages danach. Das ist keine Feinpruefung, und sie soll auch keine
 * sein — sie weist das Unmoegliche ab und laesst alles durch, was ein Geraet
 * im Betrieb je meldet. Vier Dinge zwingen zu dieser Weite:
 *
 *  - Der Zeitzonenversatz: `day` ist ein ORTSDATUM (Uhr: Util.localDay();
 *    Handy: Zeit.tag() ueber ZoneId.systemDefault()), die Zeitpunkte sind UTC.
 *    Zwischen UTC-12 und UTC+14 liegt ein voller Tag Versatz in beide
 *    Richtungen.
 *  - Ein Dienst ueber Mitternacht und ein 24-Stunden-Dienst.
 *  - Vor allem aber: In der Handy-App traegt JEDES Paket eines Dienstes den
 *    Tag des DIENSTBEGINNS, nicht seinen eigenen (Dienstklammer.kt: das
 *    Ruhesegment bekommt `dienst.tag`). Ein Ruhesegment, das am dritten Tag
 *    eines Dienstes beginnt, meldet also den Tag 1 als `day`.
 *  - Und ein Dienst hat KEINE Hoechstdauer, weder in der App noch auf dem
 *    Server. Wer das Beenden vergisst, hat einen Dienst, der Tage laeuft --
 *    ein Datenfehler, aber ein echter, und seine Pakete duerfen nicht auch
 *    noch abgewiesen werden.
 *
 * 31 Tage decken das ab und wehren ab, worum es geht: Jahre und Jahrzehnte.
 * Den feinen Schutz leistet nicht diese Pruefung, sondern das Ersetzfenster --
 * der Zeitraum eines Diensttags, an dem seit 72 Stunden niemand mehr etwas
 * angelegt hat, wird ohnehin nicht mehr fortgeschrieben.
 *
 * VERWORFENE ALTERNATIVE: gegen die GEGENWART pruefen statt gegen `day`
 * (etwa "nicht aelter als ein Jahr, nicht mehr als einen Tag in der
 * Zukunft"). Das braeuchte keine Grenze fuer lange Dienste und faengt 2001
 * wie 2097. Es bricht aber die NACHLIEFERUNG: Eine Uhr, die ein Jahr im
 * Schrank lag, meldet einen Dienst, dessen Zeiten stimmig zu seinem `day`
 * sind -- und sein Diensttag bekaeme keinen Zeitraum. Genau dieser Fehler,
 * die Gegenwart als Massstab fuer Vergangenes, ist in dieser Runde schon
 * zweimal gemacht worden. `day` und die Zeiten stammen aus demselben Paket;
 * ob sie ZUEINANDER passen, ist die Frage, die ohne Wissen ueber die
 * Aussenwelt zu beantworten ist.
 *
 * Ein leerer Zeitpunkt ist in Ordnung — `ended_at` fehlt, solange der Einsatz
 * laeuft.
 */
function pruef_zeit_zum_tag(?string $zeit, ?string $tag, string $feld = 'Zeitpunkt',
                            ?Pruefliste $p = null): bool
{
    if ($zeit === null || $zeit === '' || $tag === null || $tag === '') { return true; }
    $t = strtotime($zeit . ' UTC');
    $tagBeginn = strtotime($tag . ' 00:00:00 UTC');
    if ($t === false || $tagBeginn === false) {
        $p?->melde($feld, 'kein vergleichbarer Zeitpunkt');
        return false;
    }
    $von = $tagBeginn - 86400;           // Mitternacht des Vortags
    $bis = $tagBeginn + 32 * 86400;      // Ende des 31. Tages danach
    if ($t < $von || $t >= $bis) {
        $p?->melde($feld, 'passt nicht zum Tag ' . $tag);
        return false;
    }
    return true;
}

/**
 * Liegt das Ende vor dem Beginn?
 *
 * Ohne diese Frage wuerde ein Paket mit vertauschten Zeiten angenommen, und
 * dt_zeitraum_fortschreiben() zoege daraufhin den Beginn des Diensttags auf das
 * ENDE nach vorn und sein Ende auf den BEGINN nach hinten -- der Tag wuerde in
 * beide Richtungen aufgezogen, mit Werten, die es so nie gab.
 *
 * Gleichstand ist erlaubt: Ein Einsatz von null Sekunden ist selten, aber
 * nicht unmoeglich, und ihn abzuweisen hiesse, ueber eine Sekunde zu streiten.
 */
function pruef_ende_nach_beginn(?string $beginn, ?string $ende, string $feld = 'ended_at',
                                ?Pruefliste $p = null): bool
{
    if ($beginn === null || $beginn === '' || $ende === null || $ende === '') { return true; }
    $b = strtotime($beginn . ' UTC');
    $e = strtotime($ende . ' UTC');
    if ($b === false || $e === false) { return true; }   // Format hat schon geprueft
    if ($e < $b) {
        $p?->melde($feld, 'liegt vor dem Beginn');
        return false;
    }
    return true;
}

/**
 * Ortszeit (App-Zeitzone) -> UTC, mit Kalendertagspruefung.
 *
 * Gegenstueck zu local_to_utc() in db.php, um die Kalendertagspruefung erweitert. Die dortige
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
 * Ein Koordinaten-PAAR aus einem Formular.
 *
 * Optionale Koordinaten gibt es an Standorten und Zielkliniken,
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
 * Eine Geraetehuelle aus der Nutzlast (Base64) — als Binaerwert, oder null.
 *
 * Geprueft wird die FORM: Base64 ohne fremde Zeichen, Laenge zwischen
 * GH_MIN_BYTE und `$maxByte`, Chiffretext ein Vielfaches von 16, Kennbyte der
 * Fassung 1, ein unkomprimierter Punkt als fluechtiger Schluessel. Ob die
 * Huelle aufgeht, kann nur der Browser sagen.
 */
function pruef_huelle($wert, int $maxByte, string $feld = 'Huelle', ?Pruefliste $p = null): ?string
{
    if ($wert === null) { return null; }
    if (!is_string($wert) || !preg_match('#^[A-Za-z0-9+/]+={0,2}$#', $wert)) {
        $p?->melde($feld, 'kein Base64');
        return null;
    }
    $roh = base64_decode($wert, true);
    if ($roh === false) { $p?->melde($feld, 'kein Base64'); return null; }
    $n = strlen($roh);
    if ($n < GH_MIN_BYTE) { $p?->melde($feld, 'kuerzer als ' . GH_MIN_BYTE . ' Byte'); return null; }
    if ($n > $maxByte) { $p?->melde($feld, 'laenger als ' . $maxByte . ' Byte'); return null; }
    if (($n - 82 - 32) % 16 !== 0) { $p?->melde($feld, 'Chiffretext kein Vielfaches von 16'); return null; }
    if ($roh[0] !== "\x01") { $p?->melde($feld, 'unbekannte Fassung ' . ord($roh[0])); return null; }
    if ($roh[1] !== "\x04") { $p?->melde($feld, 'kein unkomprimierter Schluessel'); return null; }
    return $roh;
}

/**
 * Ein Spurstueck (SPUR2): `seq_from`, `punkte`, `ts_von`, `ts_bis` im
 * Klartext und die Huelle. Gibt es geprueft zurueck (Huelle binaer) oder
 * null. EINE Stelle fuer den Eingang der Geraete (`ingest.php`) und die
 * Schreibwege des Browsers (`api/schneiden.php`) — dieselben
 * Grenzen fuer beide, wie es die gemeinsame Pruefschicht verlangt.
 *
 * @return array{seq_from:int,punkte:int,ts_von:int,ts_bis:int,huelle:string}|null
 */
function pruef_spurstueck($t, string $feld = 'track', ?Pruefliste $p = null): ?array
{
    if (!is_array($t)) { $p?->melde($feld, 'kein Stueck'); return null; }
    $seqFrom = (int)($t['seq_from'] ?? -1);
    $n       = (int)($t['punkte'] ?? 0);
    $tsVon   = (int)($t['ts_von'] ?? -1);
    $tsBis   = (int)($t['ts_bis'] ?? -1);
    $huelle  = pruef_huelle($t['huelle'] ?? null, GH_STUECK_MAX_BYTE, $feld . '.huelle', $p);
    if ($seqFrom < 0 || $n < 1 || $n > LIMIT_TRACKPUNKTE_ANFRAGE
        || $tsVon < 0 || $tsBis < $tsVon || $huelle === null) {
        /* Keine eigene Meldung fuer die Zahlen: Der Aufrufer nennt dann
         * „Stueck unvollstaendig" (so im Eingang). */
        return null;
    }
    return ['seq_from' => $seqFrom, 'punkte' => $n, 'ts_von' => $tsVon,
            'ts_bis' => $tsBis, 'huelle' => $huelle];
}

/**
 * Ein gepacktes Spurstueck des Wartungslaufs (Stufe 2 oder 3): wie
 * `pruef_spurstueck()`, dazu Stufe und Marke, und die Grenzen
 * der ganzen Spur statt der einer Anfrage.
 *
 * @return array{seq_from:int,punkte:int,n_original:int,ts_von:int,ts_bis:int,stufe:int,huelle:string}|null
 */
function pruef_spurstueck_gepackt($t, string $feld = 'stueck', ?Pruefliste $p = null): ?array
{
    if (!is_array($t)) { $p?->melde($feld, 'kein Stueck'); return null; }
    $seqFrom = (int)($t['seq_from'] ?? -1);
    $n       = (int)($t['punkte'] ?? 0);
    $nOrig   = (int)($t['n_original'] ?? 0);
    $stufe   = (int)($t['stufe'] ?? 0);
    $tsVon   = (int)($t['ts_von'] ?? -1);
    $tsBis   = (int)($t['ts_bis'] ?? -1);
    $huelle  = pruef_huelle($t['huelle'] ?? null, GH_GEPACKT_MAX_BYTE, $feld . '.huelle', $p);
    if ($seqFrom < 0 || $n < 1 || $n > LIMIT_TRACKPUNKTE_SPUR || $nOrig < $n
        || $seqFrom + $nOrig > 4294967295 || !in_array($stufe, [2, 3], true)
        || $tsVon < 0 || $tsBis < $tsVon || $huelle === null) {
        $p?->melde($feld, 'Stueck unvollstaendig');
        return null;
    }
    return ['seq_from' => $seqFrom, 'punkte' => $n, 'n_original' => $nOrig, 'ts_von' => $tsVon,
            'ts_bis' => $tsBis, 'stufe' => $stufe, 'huelle' => $huelle];
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
/**
 * Wie pruef_menge(), aber ABLEHNEND statt kappend.
 *
 * Fuer Listen, bei denen ein abgeschnittener Rest schlimmer ist als gar
 * keine Uebernahme — allen voran die Spur. Gibt `null` zurueck,
 * wenn die Liste zu lang ist; die Meldung steht dann in der Pruefliste und
 * der Aufrufer ueberspringt den ganzen Datensatz.
 */
function pruef_menge_streng($liste, int $max, string $feld = 'Liste',
                            ?Pruefliste $p = null): ?array
{
    if (!is_array($liste)) {
        if ($liste !== null) { $p?->melde($feld, 'keine Liste'); }
        return [];
    }
    if (count($liste) > $max) {
        $p?->melde($feld, 'mehr als ' . $max . ' Eintraege — ganz abgelehnt');
        return null;
    }
    return $liste;
}

function ist_liste($wert): bool
{
    return is_array($wert) && ($wert === [] || array_is_list($wert));
}

/* ---------------------------------------------------------------------------
 * Stammdaten: Rettungsmittel
 * ------------------------------------------------------------------------ */

/**
 * Groesste Laenge des Kurznamens. Steht hier und nicht nur im Schema,
 * weil das Formular sie als `maxlength` braucht und die Sicherung sie beim
 * Einlesen kappt — dieselbe Zahl, eine Quelle.
 */
const RM_KURZ_MAX = 16;

/** Groesste Laenge der Bezeichnung, wie `vehicles.name` sie traegt. */
const RM_NAME_MAX = 64;

/**
 * Ein Rettungsmittel pruefen — die eine Stelle fuer alle Schreibwege.
 *
 * WARUM DIESE FUNKTION IN DIESER DATEI STEHT. Rettungsmittel werden ueber
 * zwei Wege geschrieben:
 *
 *   einstellungen.php    veh_save        Konto        mit Meldung
 *   backup_lib.php       Einspielen      Datei        still uebergangen
 *
 * Mit dem Typ kaemen an jeder Stelle vier weitere Regeln dazu, und
 * eine Kopie waere die, die als erste veraltet. Der Kopf dieser
 * Datei nennt Einsatzdaten als ihren Gegenstand; die Rettungsmittel sind der
 * erste STAMMDATENSATZ, der hier einzieht — aus demselben Grund, aus dem die
 * Einsatzdaten hier stehen: vier Wege, ein Massstab.
 *
 * WAS SIE NICHT PRUEFT: ob der Standort existiert und wem er gehoert. Das
 * haengt am Aufrufer und steht dort; hier kommt eine
 * bereits aufgeloeste ID an oder null. Ebenso wenig prueft sie die Dublette —
 * die braucht die Datenbank.
 *
 * ZWEI ARTEN VON RUECKMELDUNG, weil es zwei Arten von Aufrufern gibt. Die
 * Formulare zeigen einer Person einen Satz; sie lesen `fehler`, das je Feld
 * eine ausformulierte Meldung traegt (die Dialoge zeigen sie). Die
 * Sicherung zaehlt und meldet nicht; sie uebergibt eine `Pruefliste` und
 * liest hinterher `nachUrsache()`. Beide bekommen dieselben Regeln.
 *
 * @param  array $roh  name, kurz, typ, kind, base_id, roles[], caps[]
 * @return array{daten: ?array, fehler: array<string,string>}
 */
/**
 * TYP UND BETRIEBSART — die Regeln, die sich zwei Schreibwege teilen.
 *
 * Sie stehen an EINER Stelle, seit es zwei Verwendungen gibt: den
 * STAMMDATENSATZ (`pruef_rettungsmittel()`) und das Rettungsmittel NUR FUER
 * EINEN TAG (`pruef_tagesrettungsmittel()`). Beide fragen
 * dasselbe — welcher Typ, und welche Betriebsart laesst er zu —, und eine
 * zweite Fassung davon liefe beim naechsten Typ auseinander. Genau dieser
 * Fehler steht weiter unten als Begruendung der vier Stammdatenlisten.
 *
 * @return array{typ:?string, kind:?string, regeln:?array, fehler:array<string,string>}
 */
function pruef_typ_betriebsart(array $roh, ?Pruefliste $p = null): array
{
    $fehler = [];

    /* TYP — Vorgabe 'standard'. Ein UNBEKANNTER Wert wird nicht stillschweigend
     * zu 'standard': Das machte aus einem Bergwacht-Rettungsmittel einer
     * neueren Fassung ein Standard-Rettungsmittel, ohne dass es jemand merkt.
     * Er wird abgelehnt und gemeldet. */
    $typRoh = trim((string)($roh['typ'] ?? ''));
    if ($typRoh === '') {
        $typ = 'standard';
    } elseif (array_key_exists($typRoh, VEHICLE_TYPEN)) {
        $typ = $typRoh;
    } else {
        $typ = null;
        $p?->melde('Typ', 'unbekannter Wert');
        $fehler['typ'] = 'Dieser Typ ist nicht bekannt. Bitte einen der vier '
                       . 'Typen wählen: Standard, Bergwacht, Veranstaltung, Sonstiges.';
    }
    $regeln = $typ !== null ? VEHICLE_TYPEN[$typ] : null;

    /* BETRIEBSART — Pflicht, und bei einem Typ mit fester
     * Betriebsart nicht wahlfrei. „Fest" heisst hier: Der Wert wird gesetzt,
     * nicht abgelehnt. Das Formular zeigt das Feld ausgegraut, also kann ein
     * abweichender Wert nur aus einer gebauten Anfrage oder einer aelteren
     * Sicherung kommen — beides soll den Datensatz nicht kosten, aber auch
     * nicht unbemerkt bleiben. */
    $kindRoh = (string)($roh['kind'] ?? '');
    $kind = in_array($kindRoh, ['air', 'ground'], true) ? $kindRoh : null;
    if ($regeln !== null && $regeln['betriebsart'] !== null) {
        if ($kind !== null && $kind !== $regeln['betriebsart']) {
            $p?->melde('Betriebsart', 'bei Typ „' . $regeln['label'] . '" festgelegt — überschrieben');
        }
        $kind = $regeln['betriebsart'];
    } elseif ($kind === null) {
        $fehler['kind'] = 'Bitte die Art wählen: luftgebunden oder bodengebunden. '
                        . 'Sie entscheidet über Besatzungsrollen und die im '
                        . 'Einsatzformular sichtbaren Felder.';
    }

    return ['typ' => $typ, 'kind' => $kind, 'regeln' => $regeln, 'fehler' => $fehler];
}

function pruef_rettungsmittel(array $roh, ?Pruefliste $p = null): array
{
    $fehler = [];

    /* BEZEICHNUNG — Pflicht. Zuschneiden statt ablehnen (wie pruef_text): Ein
     * zu langer Name ist eine zu lange Eingabe, keine falsche. */
    $name = pruef_text($roh['name'] ?? null, RM_NAME_MAX, 'Bezeichnung', $p);
    if ($name === null) {
        $fehler['name'] = 'Bitte eine Bezeichnung für das Rettungsmittel eintragen.';
    }

    /* KURZNAME — freiwillig, bis 16 Zeichen. NULL heisst „keiner",
     * nicht „leer": Die Anzeige entscheidet daran, ob sie ihn statt der
     * Bezeichnung zeigt. */
    $kurz = pruef_text($roh['kurz'] ?? null, RM_KURZ_MAX, 'Kurzname', $p);

    ['typ' => $typ, 'kind' => $kind, 'regeln' => $regeln, 'fehler' => $tkFehler]
        = pruef_typ_betriebsart($roh, $p);
    $fehler += $tkFehler;

    /* STANDORT — Pflicht nur noch bei 'standard'. Die Vorschlagslisten
     * haengen am Standort; ein Standard-Rettungsmittel ohne ihn hinterliesse
     * eine leere Tagesuebersicht. Die drei anderen Typen haben oft keinen:
     * Eine Bergwacht-Bereitschaft hat ein Einsatzgebiet, ein Sanitaetsdienst
     * einen Ort, der jedes Mal woanders liegt. */
    $baseRoh = $roh['base_id'] ?? null;
    $baseId  = ($baseRoh === null || $baseRoh === '' || (int)$baseRoh <= 0) ? null : (int)$baseRoh;
    if ($regeln !== null && $regeln['standort'] && $baseId === null) {
        $p?->melde('Standort', 'fehlt bei Typ „' . $regeln['label'] . '"');
        $fehler['base_id'] = 'Bitte einen Standort wählen. Ein Rettungsmittel des Typs '
                           . '„Standard" gehört zu genau einem Standort.';
    }

    /* ROLLEN-VORLAGEN — nur bei 'standard', und dort gefiltert auf die
     * Betriebsart: Ein bodengebundenes Rettungsmittel kann keinen
     * Flugretter fuehren. Die Filterung geschieht hier und nicht nur im
     * Formular — ein Haken, den die Oberflaeche nicht anbietet, darf auch ueber
     * eine gesendete Anfrage nicht hereinkommen.
     *
     * Bei den drei anderen Typen bleibt `vehicle_roles` leer. Das ist
     * keine Einschraenkung der Dokumentation, sondern der VORLAGE: Der
     * Diensttag bekommt dann keinen Rollensatz angeboten (F19). */
    $rollen = [];
    if ($regeln !== null && $regeln['rollen'] && $kind !== null) {
        $erlaubt = array_keys(crew_roles_fuer_art($kind));
        foreach ((array)($roh['roles'] ?? []) as $rc) {
            if (in_array((string)$rc, $erlaubt, true)) { $rollen[] = (string)$rc; }
        }
    } elseif ($roh['roles'] ?? []) {
        $p?->melde('Rollen', 'bei diesem Typ nicht vorgesehen — verworfen');
    }

    /* FAEHIGKEITEN — was der TYP in dieser BETRIEBSART zulaesst
     * (`veh_caps_erlaubt()` in db.php). Fuer Standard, Veranstaltung und
     * Sonstiges heisst das weiter „nur luftgebunden"; der Typ Bergwacht
     * darf sie in beiden Betriebsarten fuehren, weil ein Bergwachtnotarzt
     * hinfaehrt und geflogen wird.
     *
     * DIE REGEL STEHT NICHT HIER, sondern in `VEHICLE_TYPEN[...]['faehigkeiten']`
     * — aus demselben Grund wie die Standortpflicht darueber: Das Skript des
     * Dialogs liest dieselbe Tabelle, und eine abgetippte zweite Fassung liefe
     * beim naechsten Typ auseinander. */
    $caps = [];
    if (veh_caps_erlaubt($typ, $kind)) {
        foreach ((array)($roh['caps'] ?? []) as $c) {
            if (array_key_exists((string)$c, VEHICLE_CAPABILITIES)) { $caps[] = (string)$c; }
        }
    } elseif ($roh['caps'] ?? []) {
        $p?->melde('Fähigkeiten', 'bei diesem Typ nur luftgebunden — verworfen');
    }

    if ($fehler !== [] || $name === null || $typ === null || $kind === null) {
        return ['daten' => null, 'fehler' => $fehler];
    }

    return [
        'daten' => [
            'name'    => $name,
            'kurz'    => $kurz,
            'typ'     => $typ,
            'kind'    => $kind,
            'base_id' => $baseId,
            'roles'   => array_values(array_unique($rollen)),
            'caps'    => array_values(array_unique($caps)),
        ],
        'fehler' => [],
    ];
}


/* ---------------------------------------------------------------------------
 * DIE VIER EINFACHEN STAMMDATENLISTEN
 *
 * Besatzungs-Vorbelegungen, Zielkliniken, weitere Rettungsmittel und
 * Bergwacht-Bereitschaften folgen demselben Muster: ein Name, ein Standort,
 * bei der Besatzung eine Rolle dazu. Je Liste und Seite ausgeschrieben,
 * laufen die Fassungen auseinander:
 *
 *   - Eine meldet bei LEEREM NAMEN gar nichts. Kein `notice`,
 *     kein `error`, also auch keine Umleitung: Der POST versickert
 *     wortlos, und die Seite kommt als POST-Ergebnis zurueck, als waere nichts
 *     gewesen; die andere meldet „Bitte einen Namen eintragen." Mit einem
 *     Dialog faellt das auf, weil er zugeht.
 *   - Die Zielklinik heisst hier „Name" mit 190 Zeichen, dort „Bezeichnung"
 *     mit 120 im Formular und 190 im Handler.
 *
 * `CLAUDE.md` 4 sagt: Einsatzdaten laufen ueber die gemeinsame Pruefschicht,
 * alle Schreibwege, ohne Ausnahme. Fuer die Stammdaten gilt dasselbe.
 *
 * DIE OBERGRENZEN STEHEN HIER UND NICHT IM FORMULAR. `maxlength` im Markup
 * ist eine Bequemlichkeit fuer die Tippende, keine Regel — wer die Seite
 * umgeht, umgeht auch das Attribut. Die Zahlen sind die des Schemas.
 * ------------------------------------------------------------------------ */
/**
 * EIN RETTUNGSMITTEL NUR FUER DIESEN TAG.
 *
 * WOFUER. Ein Dienst auf einem Fahrzeug, das dieses Konto sonst nie fuehrt —
 * die Aushilfe, das Fremdfahrzeug, der einmalige Sanitaetsdienst. Sonst
 * bliebe nur, einen Stammdatensatz anzulegen, ihn einmal zu
 * benutzen und danach in der Auswahl stehen zu lassen. Was hier entsteht,
 * steht AUSSCHLIESSLICH in der Momentaufnahme des Diensttags: kein
 * Stammdatensatz, keine Zeile in `vehicles`.
 *
 * WAS SIE MIT `pruef_rettungsmittel()` TEILT: Typ und Betriebsart, ueber
 * `pruef_typ_betriebsart()` — dieselbe Regel, eine Fassung.
 *
 * WORIN SIE SICH UNTERSCHEIDET, und warum:
 *
 *   - KEIN KURZNAME. Er ist eine Eigenschaft des Bestands und dient
 *     der Wiedererkennung in Listen. Was einen Tag lang existiert, wird nicht
 *     wiedererkannt.
 *   - DIE ROLLEN DER BETRIEBSART, KEINE FAEHIGKEITEN. Der Tag bekommt alle
 *     Rollen der gewaehlten Art und keine Fluglisten; `day_capabilities` bleibt
 *     leer. Angehakt ist hier nichts, also gibt es nichts auszuwaehlen:
 *     Die Art ist die einzige Angabe, aus der sich ein Satz ergibt.
 *   - DER STANDORT IST FREIWILLIG — auch beim Typ „Standard". Am
 *     Stammdatensatz ist er dort Pflicht, weil die Vorschlagslisten an ihm
 *     haengen und ein Standard-Rettungsmittel ohne Standort eine leere
 *     Tagesuebersicht hinterliesse. Hier bleiben die Besatzungsfelder ohne
 *     Standort aus der Liste Freitext ohne Vorschlaege — ein Preis, den ein
 *     Dienst von einem Tag tragen kann, und keine Regel wert.
 *   - DER STANDORT DARF FREITEXT SEIN. `base_id` nennt einen Standort aus der
 *     Liste, `base_name` einen Ort, den es als Stammdatensatz nicht gibt. Wo
 *     eine Kennung steht, holt der Aufrufer Namen UND Koordinate aus den
 *     Stammdaten; Freitext bleibt Freitext ohne Koordinate.
 *
 * @return array{daten:?array{name:string,typ:string,kind:string,base_id:?int,base_name:?string},
 *               fehler:array<string,string>}
 */
function pruef_tagesrettungsmittel(array $roh, ?Pruefliste $p = null): array
{
    $fehler = [];

    $name = pruef_text($roh['name'] ?? null, RM_NAME_MAX, 'Bezeichnung', $p);
    if ($name === null) {
        $fehler['name'] = 'Bitte eine Bezeichnung für das Rettungsmittel eintragen.';
    }

    ['typ' => $typ, 'kind' => $kind, 'fehler' => $tkFehler]
        = pruef_typ_betriebsart($roh, $p);
    $fehler += $tkFehler;

    /* STANDORT: KENNUNG ODER FREITEXT. Steht eine Kennung da, gewinnt sie —
     * der Aufrufer holt Namen und Koordinate aus den Stammdaten.
     *
     * DER TEXT KOMMT TROTZDEM MIT, und zwar als Rueckfall. Er ist die
     * Beschriftung desselben Feldes, also ohnehin derselbe Name. Ohne ihn
     * verloere der Tag seinen Standort STILL, wenn die Kennung sich als
     * fremd erweist: `dt_base_erlaubt()` macht daraus NULL, und dann stuende
     * weder Kennung noch Name da. Was jemand tippen darf, darf auch als
     * Rueckfall stehen bleiben. */
    $baseRoh = $roh['base_id'] ?? null;
    $baseId  = ($baseRoh === null || $baseRoh === '' || (int)$baseRoh <= 0) ? null : (int)$baseRoh;
    $baseName = pruef_text($roh['base_name'] ?? null, SD_NAME_MAX, 'Standort', $p);

    if ($fehler !== [] || $name === null || $typ === null || $kind === null) {
        return ['daten' => null, 'fehler' => $fehler];
    }

    return [
        'daten' => [
            'name'      => $name,
            'typ'       => $typ,
            'kind'      => $kind,
            'base_id'   => $baseId,
            'base_name' => $baseName,
        ],
        'fehler' => [],
    ];
}

const SD_NAME_MAX = 120;   // crew_presets, resources, bw_units
const SD_ZIEL_MAX = 190;   // transport_dests — laengere Klinikbezeichnungen

/** Welche Tabelle wie heisst, wie lang sie darf und wie ihr Feld beschriftet ist. */
const SD_LISTEN = [
    'crew_presets'    => ['max' => SD_NAME_MAX, 'label' => 'Name',        'rolle' => true],
    'transport_dests' => ['max' => SD_ZIEL_MAX, 'label' => 'Bezeichnung', 'rolle' => false],
    'resources'       => ['max' => SD_NAME_MAX, 'label' => 'Bezeichnung', 'rolle' => false],
    'bw_units'        => ['max' => SD_NAME_MAX, 'label' => 'Bezeichnung', 'rolle' => false],
];

/**
 * Einen Eintrag einer der vier einfachen Listen pruefen.
 *
 * $roh: name, base_id (int|null), rolle (nur crew_presets), id (0 = neu)
 * $tabelle: einer der vier Schluessel aus SD_LISTEN
 *
 * Rueckgabe wie `pruef_rettungsmittel()`: ['daten' => …|null, 'fehler' => []].
 * `fehler` ist je FELD eine Meldung — der Dialog zeigt sie an seinem Feld,
 * und wer nur eine Zeile hat, nimmt die erste.
 */
function pruef_stammdaten(string $tabelle, array $roh, ?Pruefliste $p = null): array
{
    if (!array_key_exists($tabelle, SD_LISTEN)) {
        throw new InvalidArgumentException('Unbekannte Stammdatenliste: ' . $tabelle);
    }
    $regeln = SD_LISTEN[$tabelle];
    $fehler = [];

    $name = pruef_text($roh['name'] ?? null, $regeln['max'], $regeln['label'], $p);
    if ($name === null) {
        $fehler['name'] = 'Bitte eine ' . ($regeln['label'] === 'Name' ? 'Person' : 'Bezeichnung')
                        . ' eintragen.';
    }

    /* DIE ROLLE IST PFLICHT UND MUSS IM KATALOG STEHEN. Ein unbekannter Wert
       wird nicht stillschweigend zur ersten Rolle: Das machte aus einer
       Pilotin eine Notaerztin, ohne dass es jemand merkt. */
    $rolle = null;
    if ($regeln['rolle']) {
        $rolle = trim((string)($roh['rolle'] ?? ''));
        if (!array_key_exists($rolle, CREW_ROLES)) {
            $p?->melde('Rolle', 'unbekannter Wert');
            $fehler['rolle'] = 'Bitte eine Rolle wählen.';
            $rolle = null;
        }
    }

    /* DER STANDORT IST HIER IMMER PFLICHT — anders als beim Rettungsmittel,
       das ohne auskommen darf. Diese vier Listen sind
       Vorschlagslisten FUER einen Standort; ohne ihn haetten sie keinen Ort,
       an dem sie erscheinen. Das Schema sagt dasselbe: `base_id NOT NULL`. */
    $baseId = isset($roh['base_id']) && (int)$roh['base_id'] > 0
        ? (int)$roh['base_id'] : null;
    if ($baseId === null) {
        $p?->melde('Standort', 'fehlt');
        $fehler['base_id'] = 'Bitte einen Standort wählen.';
    }

    if ($fehler !== []) { return ['daten' => null, 'fehler' => $fehler]; }

    return [
        'daten'  => ['name' => $name, 'base_id' => $baseId, 'rolle' => $rolle],
        'fehler' => [],
    ];
}
