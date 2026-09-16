<?php
declare(strict_types=1);

/**
 * DER NAME DIESER INSTALLATION — an einer Stelle (P5a/AP5, E-P5a-35).
 *
 * WAS BIS HIERHER GALT. Der Name stand **38-mal von Hand** im Quelltext, und
 * zwar in DREI Schreibweisen fuer dieselbe Sache:
 *
 *   „Gen-EM NAdoku"                        Tab-Titel, Kopfleiste, Anmeldeseite,
 *                                          Wartungsseite, Schluesselblatt,
 *                                          Installer, GPX-Datei, `from_name`
 *   „Gen-EM Einsatzdokumentation Notarzt"  alle acht Mailtexte
 *   „Einsatzdokumentation Notarzt"         die Testmail — ohne „Gen-EM"
 *
 * Die dritte ist kein Sonderfall, sondern der Beweis: Niemandem faellt eine
 * abweichende Schreibweise auf, solange man acht Dateien nebeneinanderlegen
 * muesste, um sie zu sehen. Dieselbe Klasse Fehler wie bei den Zusatzfeldern,
 * und dieselbe Antwort — ein Katalog statt Sonderfaelle (`CLAUDE.md` 4).
 *
 * DER ZWEITE GRUND WIEGT SCHWERER: „Gen-EM" IST EINE MARKE, KEINE FUNKTION.
 * Diese Anwendung ist dafuer gebaut, dass sie jemand anders aufsetzt — Logo,
 * Impressum und Datenschutztext sind laengst je Installation einstellbar
 * (E-P3-19/20, R32). Der Name war es nicht: Eine fremde Betreiberin
 * verschickte Post, die mit „Gen-EM" unterschrieben ist, und zeigte im
 * Browsertab einen Namen, der ihr nicht gehoert. Bei einer Dokumentation fuer
 * Notaerztinnen ist das keine Kleinigkeit.
 *
 * ZWEI WERTE, WEIL ES ZWEI GEBRAUCHSLAGEN GIBT:
 *
 *   `instanz_kurz()`  Wo wenig Platz ist und der Name oft steht: Browsertab,
 *                     Kopfleiste, Anmeldeseite. Vorgabe „Gen-EM NAdoku".
 *   `instanz_name()`  Wo der Name einmal steht und tragen muss: Mailbetreff,
 *                     Grussformel, Schluesselblatt. Vorgabe „Gen-EM
 *                     Einsatzdokumentation Notarzt".
 *
 * DIE VORGABEN SIND DIE HEUTIGEN ZEICHENKETTEN. Wer nichts einstellt, sieht
 * nach dem Update genau das, was vorher dastand — mit einer Ausnahme, und die
 * ist gewollt: Die Testmail heisst kuenftig ebenfalls nach `instanz_name()`
 * und bekommt damit das fehlende „Gen-EM" zurueck.
 *
 * ---------------------------------------------------------------------------
 * DREI SEITEN DUERFEN HIER NICHT ANKLOPFEN — und das ist keine Feinheit
 * ---------------------------------------------------------------------------
 *
 * `install.php` laeuft, BEVOR es eine Datenbank gibt. Die Wartungsseite
 * (`wartung_lib.php`) ist ausdruecklich ohne Datenbank gebaut — sie muss
 * antworten, waehrend die Datenbank umgebaut wird. Das HTTPS-Tor
 * (`kopfzeilen_lib.php`) antwortet, bevor irgendetwas geladen ist.
 *
 * Alle drei benutzen deshalb `INSTANZ_KURZ_VORGABE` unmittelbar und rufen
 * diese Funktionen NICHT. Eine Wartungsseite, die fuer ihren Titel eine
 * Datenbankabfrage braucht, ist keine Wartungsseite mehr.
 *
 * ---------------------------------------------------------------------------
 * WAS HIER AUSDRUECKLICH NICHT HERKOMMT
 * ---------------------------------------------------------------------------
 *
 * 1. DIE FUSSZEILE „© Gen-EM · Open Source" (`ui.php`). Das ist die
 *    URHEBERSCHAFT der Software, nicht der Name des Betriebs. Wer diese
 *    Anwendung aufsetzt, darf seinen Dienst benennen — nicht den, der sie
 *    geschrieben hat.
 * 2. `creator` IN JEDER GPX-DATEI (`gpx_creator()` in `gpx_lib.php`,
 *    `assets/export.js` fuer den grossen Export). Das Feld benennt die
 *    SOFTWARE, nicht die Installation — GPX 1.1 beschreibt es als „the name
 *    and URL of the software that created your GPX document". Wer diese
 *    Anwendung aufsetzt, hat sie nicht geschrieben.
 *
 *    ENTSCHIEDEN UND AUSGESCHRIEBEN, nicht stillschweigend stehengelassen
 *    (P5a/AP5, Weg B). Die Begruendung in voller Laenge steht an zwei
 *    Stellen: im Kopf von `gpx_lib.php` (fuer den, der den Code liest) und
 *    in `docs/Export-Format.md` 3.5 (fuer den, der das Format liest). Wer
 *    diese Ausnahme in Frage stellt, faengt dort an.
 *
 *    Nachgemessen am 16.09.2026, weil die fruehere Begruendung an dieser
 *    Stelle eine offene Behauptung war: Der Referenz-Export enthaelt **204**
 *    GPX-Dateien, alle mit `creator`, und `normalisieren.py` blendete das
 *    Attribut NICHT aus — es wurde 204-mal byteweise verglichen. Seither ist
 *    die FASSUNG dort maskiert und der NAME weiterhin verglichen.
 */

/* DIESE DATEI LAEDT NICHTS. Das ist Absicht und derselbe Kniff wie in
 * `smtp.php`: Die drei Seiten ohne Datenbank (`install.php`,
 * `wartung_lib.php`, das HTTPS-Tor in `kopfzeilen_lib.php`) sollen die
 * VORGABEN benutzen duerfen, ohne dabei `db.php` mitzuziehen — eine
 * Wartungsseite, die fuer ihren Titel eine Verbindung aufbaut, ist keine
 * Wartungsseite mehr. Die Funktionen unten pruefen deshalb selbst, ob es
 * `app_state_lesen()` ueberhaupt gibt. */

/** Kurzname, wenn nichts eingestellt ist. AUCH der Rueckfall der drei Seiten
 *  ohne Datenbank — deshalb eine Konstante und keine Funktion. */
const INSTANZ_KURZ_VORGABE = 'Gen-EM NAdoku';

/** Langname, wenn nichts eingestellt ist. */
const INSTANZ_NAME_VORGABE = 'Gen-EM Einsatzdokumentation Notarzt';

/** Beide Namen zusammen hoechstens so lang — `app_state.v` fasst 190. */
const INSTANZ_MAX = 80;

/**
 * Der Langname dieser Installation.
 *
 * Steht in `app_state` unter `instanz_name`; fehlt er oder ist er leer, gilt
 * die Vorgabe. `app_state_lesen()` faengt eine fehlende Tabelle selbst ab und
 * liefert dann `null` — waehrend eines Migrationslaufs steht hier also die
 * Vorgabe und nicht eine Ausnahme.
 */
function instanz_name(): string
{
    static $wert = null;
    if ($wert === null) {
        $v = function_exists('app_state_lesen') ? app_state_lesen('instanz_name') : null;
        $wert = ($v === null || trim($v) === '') ? INSTANZ_NAME_VORGABE : trim($v);
    }
    return $wert;
}

/** Der Kurzname dieser Installation. Siehe `instanz_name()`. */
function instanz_kurz(): string
{
    static $wert = null;
    if ($wert === null) {
        $v = function_exists('app_state_lesen') ? app_state_lesen('instanz_kurz') : null;
        $wert = ($v === null || trim($v) === '') ? INSTANZ_KURZ_VORGABE : trim($v);
    }
    return $wert;
}

/**
 * Beide Namen setzen. Leer heisst „zurueck auf die Vorgabe".
 *
 * PRUEFT, WAS EIN NAME SEIN DARF, und zwar eng: Steuerzeichen, Zeilenumbrueche
 * und die Zeichen, die in einer Mail-Kopfzeile etwas bedeuten, sind
 * ausgeschlossen. Der Langname geht in einen BETREFF — ein Zeilenumbruch
 * darin waere eine eingeschleuste Kopfzeile (Header-Injection), und der
 * Betreff ist die eine Stelle, an der ein selbst eingetippter Wert die
 * Anwendung verlaesst, ohne dass ein Mensch ihn noch einmal ansieht.
 *
 * @return array{0:bool,1:string} Erfolg und eine Meldung im Klartext
 */
function instanz_namen_setzen(string $name, string $kurz): array
{
    foreach ([['Name', $name], ['Kurzname', $kurz]] as [$was, $v]) {
        $v = trim($v);
        if ($v === '') { continue; }                       // leer = Vorgabe
        if (mb_strlen($v) > INSTANZ_MAX) {
            return [false, 'Der ' . $was . ' darf hoechstens ' . INSTANZ_MAX
                         . ' Zeichen lang sein.'];
        }
        if (preg_match('/[\x00-\x1F\x7F]/', $v)) {
            return [false, 'Der ' . $was . ' darf keine Steuerzeichen oder '
                         . 'Zeilenumbrueche enthalten — er steht in Mailbetreffs.'];
        }
    }
    if (!function_exists('app_state_setzen')) {
        return [false, 'Ohne Datenbank laesst sich der Name nicht speichern.'];
    }
    $a = app_state_setzen('instanz_name', trim($name));
    $b = app_state_setzen('instanz_kurz', trim($kurz));
    if (!$a || !$b) {
        return [false, 'Der Name liess sich nicht speichern. Die Einzelheiten '
                     . 'stehen im Fehlerprotokoll des Webspace.'];
    }
    return [true, 'Name der Installation gespeichert.'];
}
