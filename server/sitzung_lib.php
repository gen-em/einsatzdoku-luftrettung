<?php
declare(strict_types=1);

/**
 * DIE SITZUNGSABLAGE — die Anwendung legt ihre Sitzungsdateien selbst ab
 * (Schritt 16, E-SA-01 bis E-SA-07, Backlog Nr. 241).
 *
 * WOGEGEN. Bis Web 20.25.0 lag keine Zeile Code an `session.save_path`. Wo
 * PHP die Sitzungsdateien hinlegt, entschied damit allein der Hoster. Auf der
 * Staging-Anlage bei lima-city ist das ein GETEILTES Verzeichnis: `0773`,
 * Eigentuemer root, `gc_probability = 0`. Es ist gutgegangen, weil der Hoster
 * das Auflisten ueber das Web sperrt — die Anwendung haette es nicht gemerkt.
 * Fuer den Produktivserver ist derselbe Wert nie erhoben worden, fuer jede
 * Selbsthosterin ist er offen.
 *
 * WAS AUF DEM SPIEL STEHT, NUECHTERN. Eine Sitzungsdatei traegt KEIN
 * Schluesselmaterial; die Zusage der Ende-zu-Ende-Verschluesselung ist nicht
 * beruehrt. Sie traegt aber die Sitzung selbst, und ihr DATEINAME IST DIE
 * SITZUNGSKENNUNG. Wer sie liest, ist angemeldet und sieht die Klartextliste
 * aus `CLAUDE.md` 4; bei `role = admin` die Verwaltung.
 *
 * ZWEI AUFRUFSTELLEN, NICHT NEUN (E-SA-02). Es gibt neun `session_start()`
 * in neun Dateien — `auth_guard.php`, `login.php`, `pw_handling.php`,
 * `session_lib.php`, `doku_seite.php`, `notfallblatt.php`,
 * `rechtstext_seite.php`, `install.php`, `wiederherstellen.php`. ACHT davon
 * laden `db.php` vor ihrem Sitzungsstart; die neunte ist `install.php`, wo es
 * noch keine `config.php` gibt. Der Aufruf steht deshalb in `db.php` (frueh,
 * neben `wartung_tor()`, ohne Datenbank) und in `install.php` — und ein
 * kuenftiger zehnter Sitzungsstart ist damit von selbst gedeckt.
 *
 * Der erste Entwurf des Konzepts kannte nur DREI der neun und gab nur ihnen
 * den Aufruf. Darunter fehlte `login.php`: Die Anmeldung haette die Sitzung
 * beim Hoster abgelegt und `auth_guard.php` sie in `.sitzungen/` gesucht.
 * NIEMAND HAETTE SICH ANMELDEN KOENNEN. Das ist der Grund fuer die zentrale
 * Stelle, und er ist teuer bezahlt.
 *
 * DIESE DATEI LAEDT NICHTS (E-SA-02). Weder `db.php` noch `config.php` —
 * `db.php` ruft sie waehrend des eigenen Ladens, und `install.php` hat zu
 * diesem Zeitpunkt keine Konfiguration. `session_lib.php` (Abmelden, Logo,
 * CSRF) kam als Ort deshalb nicht in Frage: Sie laedt `db.php` in Zeile 3.
 *
 * DER CACHE IST EINE DATEI UND KEINE TABELLE. `app_state` waere der
 * naheliegende Ort und der falsche: Vor dem Sitzungsstart ist die Datenbank
 * nicht verbunden (`db()` verbindet erst beim ersten Aufruf), in
 * `install.php` gibt es sie gar nicht, und faellt sie aus, straeubte sich
 * jede Anfrage schon vor der Fehlerseite. Stattdessen die Markerdatei
 * `.sitzungen/.geprueft`: Ist ihr `mtime` juenger als eine Stunde, genuegt
 * ein `is_dir()`; sonst Probedatei, und bei Erfolg `touch`.
 *
 * `gc_probability = 0` IST KEIN FEINSCHLIFF, SONDERN NOTWENDIG (E-SA-06).
 * Der Hoster stellt `gc_maxlifetime` ein — auf lima-city 1440 s, also 24
 * Minuten. Die Anwendung hat ihre EIGENE Frist von 30 Minuten
 * (`SESSION_TIMEOUT_S`). Liefe PHPs Zufallsraeumung mit, loeschte sie
 * Sitzungsdateien sechs Minuten VOR Ablauf der Frist, die die Anwendung
 * zusagt — und die Betroffene saehe eine Abmeldung ohne Grund. Geraeumt wird
 * deshalb vom Aufraeumjob (4.97a, Teil „Sitzungsdateien"), nicht vom Zufall.
 *
 * DER RUECKFALL IST KEINE VERSCHLECHTERUNG (E-SA-03). Scheitert das Anlegen
 * oder die Probe, gilt der Hosterpfad wie bisher — die Anwendung laeuft
 * weiter. Kein Anhalten, und kein Ausweichen nach `sys_get_temp_dir()`: Dort
 * ist dasselbe Problem, nur woanders. NEU ist allein, dass man den Rueckfall
 * SIEHT (Statusseite, Karte „Plattform"). Genau das ist die Verbesserung.
 */

/**
 * Wie lange eine Sitzung ohne Zutun gilt — 30 Minuten Inaktivitaet.
 *
 * SIE STAND BIS WEB 20.25.0 IN `auth_guard.php`, und dort war sie am
 * falschen Ort: Der Aufraeumjob laeuft ueber `jobs.php`, das `db.php` laedt,
 * aber nie `auth_guard.php`. Ein Raeumteil, der die Frist braucht, waere auf
 * der Kommandozeile an `Undefined constant` gestorben und am Huckepack-Weg
 * durchgelaufen — ein Fehler auf einem von drei Wegen, also der Sorte, die
 * erst im Betrieb auffaellt. Dasselbe Argument hat `PAIR_TTL_MIN` nach
 * `db.php` gebracht und nicht nach `kopplung_lib.php`.
 *
 * VERSCHOBEN UND NICHT VERDOPPELT: Eine zweite `const` desselben Namens ist
 * in PHP 8 kein Fatal, sondern eine Warnung — bei JEDER angemeldeten
 * Anfrage. Still genug, um stehen zu bleiben, laut genug, um das
 * Fehlerprotokoll zuzumuellen.
 */
const SESSION_TIMEOUT_S = 1800;

/**
 * Karenz, die der Raeumteil auf die Frist legt (E-SA-06).
 *
 * Geraeumt wird nach `SESSION_TIMEOUT_S` PLUS dieser Stunde. Die Karenz ist
 * kein Sicherheitsabstand gegen Uhrenversatz, sondern gegen die eigene
 * Taktung: Der Aufraeumjob laeuft hoechstens einmal je Kalendertag. Wer
 * knapper raeumte, loeschte eine Sitzung, die eine Anfrage weiter noch
 * gebraucht wird.
 */
const SITZUNG_KARENZ_S = 3600;

/** Wie lange die Markerdatei eine gelungene Probe verbuergt (E-SA-02). */
const SITZUNG_MARKER_S = 3600;

/** Der Pfad der eigenen Ablage — eine Stelle, drei Leser. */
function sitzung_ablage_pfad(): string
{
    return __DIR__ . '/.sitzungen';
}

/**
 * Laeuft das hier auf der Kommandozeile?
 *
 * DIESELBE DOPPELBEDINGUNG WIE `wartung_cli()` und wie der HTTPS-Befund in
 * `plattform_lib.php`. `phpdbg` gehoert dazu: Zwei Tore nebeneinander in
 * `db.php` mit unterschiedlicher Vorstellung davon, was „Kommandozeile"
 * heisst, sind ein Widerspruch, der beim ersten `phpdbg`-Lauf auffaellt.
 */
function sitzung_cli(): bool
{
    return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
}

/**
 * Was der letzte Lauf von `sitzung_ablage()` ergeben hat — fuer die
 * Statusseite (E-SA-03) und fuer `plattform_pruefen()`.
 *
 * Ohne Argumente eine Auskunft, mit Argumenten die Eintragung. Dasselbe
 * Muster wie `db()`: ein `static` in der Funktion statt einer globalen
 * Variablen, die jeder anfassen kann.
 *
 * DIE LAGE IST BENANNT, NICHT ERRECHNET (Berichtigung 20.09.2026). Bis Web
 * 20.26.0 trug der Stand nur `eigen` und `grund`, und die Statusseite hat die
 * URSACHE daraus geraten: Sie druckte „konnte kein eigenes Verzeichnis
 * einrichten", sobald der gemessene Pfad nicht der eigene war. Auf der
 * Staging-Anlage war das nachweislich falsch — das Verzeichnis war angelegt
 * und beschreibbar, nur hat der Hoster den gesetzten Pfad nicht uebernommen.
 * Fuer diesen Fall gab es keinen Zustand, also gab es auch keinen wahren
 * Satz. Jetzt sagt `lage`, was war, und der Text haengt daran.
 *
 * `gelaufen` ist ersatzlos entfallen: Es wurde im ganzen Repositorium von
 * niemandem gelesen — und es war ausgerechnet der Wert, der zwei Ursachen
 * voneinander getrennt haette.
 *
 * @return array{eigen: bool, lage: string, grund: ?string}
 */
function sitzung_ablage_stand(?string $lage = null, bool $eigen = false,
                              ?string $grund = null): array
{
    static $stand = ['eigen' => false, 'lage' => 'nicht_gelaufen', 'grund' => null];
    if ($lage !== null) {
        $stand = ['eigen' => $eigen, 'lage' => $lage, 'grund' => $grund];
    }
    return $stand;
}

/**
 * Was auf der Statusseite steht — je Lage EIN Satz, und zwar ein wahrer.
 *
 * Steht hier und nicht in `plattform_lib.php`, weil nur diese Datei weiss,
 * was tatsaechlich geschehen ist. Wer einen Zustand ergaenzt, ergaenzt den
 * Satz hier mit; eine unbekannte Lage bekommt bewusst keinen erfundenen.
 */
function sitzung_ablage_satz(array $stand): string
{
    $saetze = [
        'eigen' => 'Die Anwendung legt ihre Sitzungen selbst ab.',
        'nicht_gelaufen' => 'RUECKFALL: Die Einrichtung der Ablage ist nicht gelaufen. '
            . 'Das sollte nicht vorkommen - `db.php` ruft sie bei jeder Anfrage.',
        'kommandozeile' => 'Auf der Kommandozeile richtet die Anwendung die Ablage '
            . 'absichtlich nicht ein.',
        'sitzung_lief' => 'RUECKFALL: Beim Laden von `db.php` lief bereits eine Sitzung, '
            . 'deshalb liess sich der Ort nicht mehr setzen. Das deutet auf '
            . '`session.auto_start` oder ein `auto_prepend_file` der Anlage - dann '
            . 'greifen auch `secure`, `SameSite` und `use_strict_mode` des '
            . 'Sitzungscookies nicht.',
        'nicht_anlegbar' => 'RUECKFALL: Das eigene Verzeichnis liess sich nicht anlegen.',
        'nicht_beschreibbar' => 'RUECKFALL: Das eigene Verzeichnis ist nicht beschreibbar.',
        'nicht_uebernommen' => 'RUECKFALL: Das eigene Verzeichnis ist angelegt und '
            . 'beschreibbar - die Anlage uebernimmt den gesetzten Pfad aber nicht. '
            . 'Das heisst in aller Regel, dass der Hoster `session.save_path` '
            . 'festgeschrieben hat; die Anwendung kann daran nichts aendern.',
    ];
    $satz = $saetze[$stand['lage']] ?? 'RUECKFALL: Grund nicht festgehalten.';
    return $stand['grund'] !== null ? $satz . ' ' . $stand['grund'] : $satz;
}

/**
 * Die eigene Ablage einrichten und `session.save_path` darauf richten.
 *
 * Gerufen an genau zwei Stellen: `db.php` (neben `wartung_tor()`) und
 * `install.php` (vor dessen `session_start()`). Mehrfachaufrufe sind
 * gutartig — der `static` laesst nur den ersten arbeiten.
 *
 * DREI TORE, UND JEDES HAT SEINEN GRUND:
 *
 *   Kommandozeile   Ein Cron-Lauf laeuft unter Umstaenden als anderer Nutzer
 *                   als der Webserver. Legte ER das Verzeichnis an, gehoerte
 *                   es dem Falschen, und die Anwendung koennte nicht mehr
 *                   hineinschreiben — ein Schaden, den es ohne diesen Code
 *                   nicht gaebe.
 *   Sitzung laeuft  `session_save_path()` scheitert bei aktiver Sitzung mit
 *                   einer Warnung und aendert nichts. Lieber gar nicht
 *                   versuchen als eine Warnung je Anfrage.
 *   schon gelaufen  `db.php` wird in einer Anfrage genau einmal geladen,
 *                   `install.php` ruft zusaetzlich — der Riegel kostet
 *                   nichts und macht die Reihenfolge gleichgueltig.
 */
function sitzung_ablage(): void
{
    static $gelaufen = false;
    if ($gelaufen) { return; }
    $gelaufen = true;

    /* JEDER AUSGANG VERMERKT SEINE LAGE. Bis Web 20.26.0 kehrten diese
     * beiden stumm zurueck; die Statusseite stand dann auf dem Vorgabewert
     * des `static` und nannte keinen Grund — nachgewiesen auf der
     * Staging-Anlage. Drei von sechs Ausgaengen waren sichtbar, drei nicht. */
    if (sitzung_cli()) {
        sitzung_ablage_stand('kommandozeile', false, null);
        return;
    }
    if (session_status() !== PHP_SESSION_NONE) {
        sitzung_ablage_stand('sitzung_lief', false, null);
        return;
    }

    $pfad   = sitzung_ablage_pfad();
    $marker = $pfad . '/.geprueft';

    /* DER SCHNELLE WEG. Ist der Marker juenger als eine Stunde, ist die
     * Probe gelaufen und gelungen; dann genuegt die Frage, ob das
     * Verzeichnis noch da ist. Das ist der Regelfall und kostet zwei
     * Dateisystemfragen. */
    $frisch = @filemtime($marker);
    if ($frisch !== false && $frisch > time() - SITZUNG_MARKER_S && is_dir($pfad)) {
        sitzung_ablage_setzen($pfad);
        return;
    }

    /* DAS WETTRENNEN IST HIER DER REGELFALL und nicht die Ausnahme: Jede
     * Anfrage kommt hier vorbei, und die erste nach einem Deploy trifft auf
     * ein fehlendes Verzeichnis. Deshalb die dreiteilige Bedingung aus dem
     * Bestand (`adminbackup_lib.php`, `komplett_lib.php`): Der zweite
     * `is_dir()` faengt die Anfrage ab, die zwischen Frage und `mkdir` von
     * einer anderen ueberholt wurde.
     *
     * `0700` UND ZUSAETZLICH `chmod` (E-SA-02). Der Modus im `mkdir` ist ein
     * Wunsch, kein Ergebnis: Die `umask` des Prozesses maskiert ihn. Ohne
     * die zweite Zeile waere „0700" eine Absicht und keine Aussage — und
     * genau diese Aussage ist auf nginx die EINZIGE Sicherung (E-SA-05).
     * `0700` ist im Bestand beispiellos; die sieben anderen `mkdir` legen
     * mit `0770` an. Hier ist die Gruppe zu viel: Auf geteiltem Webspace
     * steht in ihr, wer gerade zufaellig danebenwohnt. */
    if (!is_dir($pfad) && !@mkdir($pfad, 0700, true) && !is_dir($pfad)) {
        sitzung_ablage_stand('nicht_anlegbar', false, 'Betroffen ist `' . $pfad . '`.');
        return;
    }
    @chmod($pfad, 0700);
    /* DEN STATUSPUFFER LEEREN, und zwar mit dem Pfad als Argument: Das
     * raeumt auch den realpath-Cache, der anders als der stat-Cache
     * PROZESSWEIT gilt und eine Vorgabe-Lebensdauer von 120 s hat. Ohne
     * diese Zeile kann die Schreibprobe unmittelbar nach dem `mkdir` noch
     * die Auskunft von vorher bekommen — gemessen am 20.09.2026 im
     * Pruefstand: `sitzung_ablage()` meldete „Es liess sich nichts anlegen",
     * waehrend `plattform_schreibprobe()` zwei Zeilen spaeter auf demselben
     * Pfad gelang. Ein Rueckfall, den es nicht gab. */
    clearstatcache(true, $pfad);

    /* DIE PROBEDATEI STEHT IN `plattform_lib.php`, und sie wird NUR HIER
     * nachgeladen — also hoechstens einmal je Stunde, nicht bei jeder
     * Anfrage. Eine zweite Schreibprobe im Repositorium waere die Art
     * Verdopplung, die auseinanderlaeuft.
     *
     * ACHTUNG BEIM WEITERBAUEN: `plattform_lib.php` laedt heute nur
     * `email_lib.php` und `php_mindest.php`, und keine der beiden laedt
     * `db.php`. Das MUSS so bleiben. `db.php` ruft diese Funktion waehrend
     * des eigenen Ladens; zoege `plattform_lib.php` `db.php` nach, liefe
     * `sitzung_ablage()` in einer halb geladenen `db.php`, und `require_once`
     * verdeckte den Zyklus, statt ihn zu melden. Die Stelle, an der das
     * kippen kann, ist `email_lib.php` Zeile 128 — siehe den Kommentar dort. */
    require_once __DIR__ . '/plattform_lib.php';
    $p = plattform_schreibprobe($pfad);
    if (!$p['ok']) {
        sitzung_ablage_stand('nicht_beschreibbar', false, (string)$p['grund']);
        return;
    }

    /* DER MARKER TRAEGT KEINEN INHALT, nur seinen Zeitstempel. `touch`
     * legt ihn an, wenn er fehlt. Er beginnt mit einem Punkt und faellt
     * damit unter dieselbe `.htaccess`-Regel wie das Verzeichnis; der
     * Raeumteil laesst ihn stehen, weil er nur `sess_*` anfasst. */
    @touch($marker);
    @chmod($marker, 0600);
    sitzung_ablage_setzen($pfad);
}

/**
 * Den Pfad setzen und die Zufallsraeumung abstellen.
 *
 * Eigene Funktion, weil beide Wege durch `sitzung_ablage()` hier enden —
 * der schnelle ueber den Marker und der langsame ueber die Probe.
 */
function sitzung_ablage_setzen(string $pfad): void
{
    session_save_path($pfad);
    ini_set('session.gc_probability', '0');

    /* ZURUECKLESEN UND NICHT DEM RUECKGABEWERT GLAUBEN (Berichtigung
     * 20.09.2026). Bis Web 20.26.0 stand hier `sitzung_ablage_merken(true,
     * null)` — eine Behauptung ohne Messung, und damit genau das, was
     * `docs/Technik.md` 5b.1 verbietet: „Wer nichts gemessen hat, darf nichts
     * behaupten."
     *
     * DER RUECKGABEWERT TAUGT DAFUER NICHT. Gemessen am 20.09.2026 unter PHP
     * 8.4.19:
     *
     *   Sitzung schon aktiv       `false`, dazu eine Warnung
     *   Kopfzeilen schon gesendet `false`, dazu eine Warnung
     *   `open_basedir` sperrt     DEN ALTEN PFAD ALS ZEICHENKETTE — also
     *                             dasselbe wie bei Erfolg —, dazu eine Warnung
     *
     * Ein `=== false` haette den dritten Fall durchgelassen. Belastbar ist
     * allein der Vergleich des WIRKSAMEN Pfads mit dem gewuenschten, und den
     * macht die Zeile darunter. */
    $wirk  = sitzung_wirksamer_pfad();
    $rZiel = realpath($pfad);
    $rWirk = realpath($wirk);
    $griff = ($rZiel !== false && $rWirk !== false)
        ? ($rZiel === $rWirk) : ($pfad === $wirk);

    if ($griff) {
        sitzung_ablage_stand('eigen', true, null);
        return;
    }
    /* NUR DER GESETZTE PFAD. Der wirksame steht ohnehin am Ende derselben
     * Zeile („Wirksam: <Pfad>"); ihn hier zu wiederholen hat ihn auf der
     * Statusseite zweimal gedruckt. */
    sitzung_ablage_stand('nicht_uebernommen', false,
        'Gesetzt wurde `' . $pfad . '`.');
}

/**
 * Abgelaufene Sitzungsdateien der EIGENEN Ablage loeschen (E-SA-06).
 *
 * NUR `sess_*`, und das ist der wichtigste Satz dieser Funktion. Die
 * Markerdatei `.geprueft` bleibt stehen; sie ist kein Bestand, sondern der
 * Cache. Und geraeumt wird ausschliesslich in `sitzung_ablage_pfad()` —
 * NIEMALS im Hosterpfad. Dort liegen im Rueckfall die Sitzungen fremder
 * Konten, und ein Aufraeumjob, der die loescht, ist kein Aufraeumjob.
 *
 * @param  int $aelterAls Sekunden ohne Zugriff, ab denen geloescht wird
 * @return int Zahl geloeschter Dateien
 */
function sitzung_aufraeumen(int $aelterAls): int
{
    $pfad = sitzung_ablage_pfad();
    if (!is_dir($pfad)) { return 0; }
    $namen = @scandir($pfad);
    if ($namen === false) { return 0; }

    $grenze = time() - max(0, $aelterAls);
    $weg    = 0;
    foreach ($namen as $name) {
        if (strncmp($name, 'sess_', 5) !== 0) { continue; }
        $datei = $pfad . '/' . $name;
        if (!is_file($datei)) { continue; }
        $m = @filemtime($datei);
        if ($m === false || $m >= $grenze) { continue; }
        if (@unlink($datei)) { $weg++; }
    }
    clearstatcache();
    return $weg;
}

/**
 * Wie viele Sitzungsdateien liegen in einem Verzeichnis?
 *
 * Gezaehlt werden nur `sess_*` — dieselbe Abgrenzung wie beim Raeumen, damit
 * die Zahl auf der Statusseite und die Zahl im Job dasselbe meinen.
 * `null` heisst „nicht feststellbar" und nicht „null Dateien": Auf einem
 * fremden Verzeichnis darf `scandir()` scheitern, und genau dann ist die
 * Auskunft „0" die falscheste von allen.
 */
function sitzung_dateien_zahlen(string $pfad): ?int
{
    if (!is_dir($pfad)) { return null; }
    $namen = @scandir($pfad);
    if ($namen === false) { return null; }
    $n = 0;
    foreach ($namen as $name) {
        if (strncmp($name, 'sess_', 5) === 0) { $n++; }
    }
    return $n;
}

/**
 * Der wirksame Ablageort, so wie PHP ihn gleich benutzen wird.
 *
 * `session_save_path()` liefert einen Leerstring, wenn nichts gesetzt ist —
 * dann nimmt PHP `sys_get_temp_dir()`. Die Auskunft muss den TATSAECHLICHEN
 * Ort nennen, sonst prueft E-SA-07 ein Verzeichnis, das niemand benutzt.
 *
 * Die Form `N;/pfad` (Streutiefe) kommt bei keinem der beiden Hoster vor,
 * wird hier aber trotzdem abgeschnitten: Ein Pfad mit Semikolon davor ist
 * kein Pfad, und `fileperms()` auf ihn faende nichts.
 */
function sitzung_wirksamer_pfad(): string
{
    $roh = (string)session_save_path();
    if ($roh === '') { return sys_get_temp_dir(); }
    $teile = explode(';', $roh);
    return (string)end($teile);
}
