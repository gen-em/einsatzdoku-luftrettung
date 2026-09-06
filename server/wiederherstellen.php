<?php
declare(strict_types=1);
/**
 * INSTALLATION WIEDERHERSTELLEN — der Rückweg zum Komplett-Backup
 * (E-S2-20, S2/AP8).
 *
 * WOFÜR ES DIESE SEITE GIBT. Der Webspace ist weg. Ein neuer steht, die
 * Anwendungsdateien sind hochgeladen, die `config.php` aus dem
 * Wiederanlaufpaket liegt daneben, die Datenbank ist leer. `install.php`
 * verweigert sich (es gibt eine `config.php`), und `update.php` verlangt eine
 * Anmeldung, die es ohne Konten nicht geben kann. Genau in diese Lücke
 * gehört diese Seite.
 *
 * DREI SCHRANKEN, UND JEDE HAT IHREN GRUND
 *
 *   1. DIE DATENBANK MUSS LEER SEIN. Steht auch nur ein Konto darin, ist
 *      diese Installation in Betrieb, und dann hat hier niemand etwas zu
 *      suchen. Das ist die wichtigste der drei: Sie macht die Seite auf einer
 *      laufenden Anlage wirkungslos, unabhängig von allem anderen.
 *
 *   2. DER NACHWEIS. Dieselbe Bauart wie in `install.php` (M1-11): Die Seite
 *      legt eine Datei mit zufälligem Namen im Anwendungsverzeichnis ab; wer
 *      deren Kennung eintragen kann, hat Zugriff auf das Verzeichnis. Ohne
 *      ihn wäre die Seite in dem Zeitfenster zwischen „Datenbank leer" und
 *      „erstes Konto angelegt" für jede Person im Netz offen — und wer dort
 *      einen eigenen Dump einspielte, wäre danach der Administrator dieser
 *      Installation.
 *
 *   3. DIE DATEI KOMMT AUS `sicherungen/eingang/`, NICHT AUS EINEM FORMULAR.
 *      Es gibt hier bewusst kein Hochladen. Wer die Datei dorthin legen kann,
 *      hat ohnehin Dateizugriff — und ein Upload-Formular auf einer Seite
 *      ohne Anmeldung wäre genau das, was Schranke 2 verhindern soll, nur
 *      bequemer.
 *
 * WAS DIESE SEITE NICHT TUT: MIGRATIONEN. Das Konzept sieht den
 * Migrationslauf im Anschluss vor; er läuft hier trotzdem nicht mit, und
 * zwar aus dem Grund, aus dem `update.php` seit M6-01 zweistufig ist —
 * Migrationen können Spalten löschen, und deshalb steht zwischen Anzeigen und
 * Ausführen ein Knopf und eine angemeldete Administratorin. Eine Seite ohne
 * Anmeldung, die sie nebenbei mitlaufen liesse, nähme genau diese Absicherung
 * heraus. Stattdessen sagt die Seite am Ende, ob der Dump aus einer anderen
 * Fassung stammt, und schickt zur Wartung. Im Runbook steht derselbe Schritt.
 *
 * DER ABLAUF IN ZWEI GÄNGEN, beide wiederaufnehmbar:
 *   A  Auspacken   `.edk` entsiegeln und entpacken -> `eingang/.arbeit/dump.sql`
 *   B  Einspielen  Zeile für Zeile, mit Byteversatz als Fortsetzungsmarke
 */

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    require_once __DIR__ . '/ui.php';
    http_response_code(409);
    /* DIESELBE OEFFENTLICHE HUELLE WIE DER EINRICHTER (O10, Tabelle 5.4):
     * Kopf ohne Menue, Lesespalte, Fuss ohne Rechtslinks. Ohne `config.php`
     * gaebe es die Rechtstextseiten ohnehin nicht. */
    ui_seite_start(['titel' => 'Installation wiederherstellen']);
    ui_kopf(['menue' => false]);
    echo '<div class="rahmen rahmen-lesespalte">' . "\n  <main class=\"inhalt\">\n";
    ui_karte_start(['titel' => 'Diese Installation ist noch nicht eingerichtet']);
    echo '<p class="feld-hinweis">Es gibt keine <code>config.php</code>. Ohne sie ist '
       . 'weder ein Datenbankzugang noch der Serverschlüssel bekannt. Entweder die '
       . '<code>config.php</code> aus dem Wiederanlaufpaket hierher legen — oder die '
       . 'Anwendung über <a href="install.php">install.php</a> neu einrichten.</p>';
    ui_karte_ende();
    echo "  </main>\n</div>\n";
    ui_fuss_seite(['rechtslinks' => false]);
    ui_seite_ende();
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/komplett_lib.php';

/* Eigene Sitzung — `auth_guard.php` gibt es hier nicht, es gibt ja keine
 * Konten. Dieselben Einstellungen wie im Einrichter (M1-19). */
session_set_cookie_params([
    'httponly' => true, 'secure' => !empty($_SERVER['HTTPS']),
    'samesite' => 'Lax', 'path' => '/',
]);
ini_set('session.use_strict_mode', '1');
session_start();
if (empty($_SESSION['wh_csrf'])) { $_SESSION['wh_csrf'] = bin2hex(random_bytes(32)); }

/** Zeitbudget eines Durchgangs in Sekunden — wie bei „Alle sichern". */
const WH_BUDGET = 20.0;

/** Woher die Dateien kommen. */
const WH_EINGANG = 'eingang';
/** Wo der ausgepackte Klartext und die Fortsetzungsmarke liegen. */
const WH_ARBEIT  = '.arbeit';

function wh_eingang(): string { return edbak_wurzel() . '/' . WH_EINGANG; }
function wh_arbeit(): string  { return wh_eingang() . '/' . WH_ARBEIT; }
function wh_dump(): string    { return wh_arbeit() . '/dump.sql'; }
function wh_standdatei(): string { return wh_arbeit() . '/stand.json'; }

function wh_stand_lesen(): array
{
    $j = @file_get_contents(wh_standdatei());
    if ($j === false) { return []; }
    $a = json_decode($j, true);
    return is_array($a) ? $a : [];
}

function wh_stand_schreiben(array $a): void
{
    if (!is_dir(wh_arbeit())) { @mkdir(wh_arbeit(), 0770, true); }
    @file_put_contents(wh_standdatei(), json_encode($a), LOCK_EX);
}

function wh_arbeit_weg(): void
{
    foreach ([wh_dump(), wh_standdatei()] as $f) { @unlink($f); }
    @rmdir(wh_arbeit());
}

/** Die Dateien, die sich einspielen lassen. */
function wh_quellen(): array
{
    $pfad = wh_eingang();
    if (!is_dir($pfad)) { return []; }
    $raus = [];
    foreach (scandir($pfad) ?: [] as $n) {
        if ($n === '.' || $n === '..' || $n === WH_ARBEIT) { continue; }
        if (!preg_match('/\.(edk|sql|sql\.gz|gz)$/i', $n)) { continue; }
        if (str_contains($n, '/') || str_contains($n, '\\')) { continue; }
        $voll = $pfad . '/' . $n;
        if (!is_file($voll)) { continue; }
        $art = 'roh';
        $kopf = null;
        if (str_ends_with(strtolower($n), '.edk')) {
            $k = komp_kopf_lesen($voll);
            if ($k === null) { continue; }
            $art = 'edk';
            $kopf = $k['kopf'];
        } elseif (preg_match('/\.gz$/i', $n)) {
            $art = 'gz';
        }
        $raus[] = ['datei' => $n, 'art' => $art, 'groesse' => (int)filesize($voll),
                   'kopf' => $kopf];
    }
    usort($raus, fn(array $a, array $b): int => strcmp($b['datei'], $a['datei']));
    return $raus;
}

/* ---- Schranke 1: die Datenbank muss leer sein ---------------------------- */
$dbFehler = null;
$leer     = false;
$konten   = 0;
try {
    $pdo = db();
    $hat = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables
                              WHERE table_schema = DATABASE() AND table_name = 'users'")
                    ->fetchColumn();
    $konten = $hat > 0 ? (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() : 0;
    $leer = $konten === 0;
} catch (Throwable $ex) {
    $dbFehler = $ex->getMessage();
}

/* ---- Schranke 2: der Nachweis ------------------------------------------- *
 *
 * Wortgleich zur Bauart in `install.php`, nur mit eigenem Präfix: Die Kennung
 * steht im DATEINAMEN, nicht nur im Inhalt — bei Einfachhosting liegt dieses
 * Verzeichnis im Web-Wurzelverzeichnis, und eine Datei mit festem Namen wäre
 * über die Adresszeile abrufbar. Ein Name aus 128 Bit Zufall lässt sich nur
 * nennen, wer das Verzeichnis SIEHT. Die Kennung hängt an der Datei und nicht
 * an der Sitzung: Sonst läge nach jedem Aufruf — auch dem eines Neugierigen —
 * eine weitere Datei da, und niemand wüsste, welche die seine ist.
 */
$nachweisMuster = 'wiederher-nachweis-';
$nachweisOk = true;
$nachweis = '';

/* ER ENTSTEHT NUR, WENN DIESE SEITE UEBERHAUPT ETWAS TUN KANN.
 *
 * Die erste Fassung legte ihn bei JEDEM Aufruf an — auch auf einer laufenden
 * Installation, auf der die Seite nichts als „ist in Betrieb" sagen kann.
 * Damit haette jeder, der die Adresse kennt, den Server durch einen Aufruf
 * dazu gebracht, eine Datei ins Wurzelverzeichnis zu schreiben. Sie taete
 * dort nichts Boeses, aber sie hat dort nichts verloren, und eine
 * Schreiboperation auf einen unangemeldeten GET hin ist die falsche Antwort
 * auf jede Frage.
 *
 * Ein bereits VORHANDENER wird trotzdem gelesen — sonst liesse sich ein
 * laufendes Einspielen nicht zu Ende bringen, sobald die Datenbank sich
 * fuellt. */
$darfNachweis = ($dbFehler === null)
    && ($leer || in_array((string)(wh_stand_lesen()['phase'] ?? ''),
                          ['einspielen', 'fertig'], true));

foreach (glob(__DIR__ . '/' . $nachweisMuster . '*.txt') ?: [] as $datei) {
    if (!preg_match('/' . preg_quote($nachweisMuster, '/') . '([0-9a-f]{32})\.txt$/',
                    $datei, $tr)) { continue; }
    if ($nachweis === '') { $nachweis = $tr[1]; }
    elseif ($tr[1] !== $nachweis) { @unlink($datei); }
}
if ($nachweis === '') { $nachweis = bin2hex(random_bytes(16)); }
$nachweisDatei = __DIR__ . '/' . $nachweisMuster . $nachweis . '.txt';
if (!$darfNachweis) {
    $nachweisOk = file_exists($nachweisDatei);
} elseif (!is_writable(__DIR__)) {
    $nachweisOk = false;
} elseif (!file_exists($nachweisDatei)) {
    $inhalt = $nachweis . "\n\n"
            . "Diese Datei gehoert zur Wiederherstellung von Gen-EM NAdoku.\n"
            . "Die Zeichenfolge oben ist im Formular einzutragen. Sie beweist,\n"
            . "dass die wiederherstellende Person Zugriff auf dieses Verzeichnis\n"
            . "hat. Nach getaner Arbeit wird die Datei geloescht; sie kann auch\n"
            . "jederzeit von Hand geloescht werden.\n";
    if (@file_put_contents($nachweisDatei, $inhalt, LOCK_EX) === false) { $nachweisOk = false; }
    else { @chmod($nachweisDatei, 0640); }
}

$notice = null; $error = null;
$stand = wh_stand_lesen();

/* ---- „Leer" gilt fuers ANFANGEN, nicht fuers WEITERMACHEN ----------------- *
 *
 * DAS IST DIE BEHEBUNG EINES FEHLERS, DEN ERST DER LAUF GEZEIGT HAT. Die
 * erste Fassung prüfte vor JEDEM Durchgang, ob die Datenbank leer ist. Sie
 * ist es aber nur vor dem ersten: Ab dem zweiten steht dort, was der erste
 * eingespielt hat. Gemessen an der 122-MB-Datei mit einem Budget von vier
 * Sekunden brach die Wiederherstellung deshalb bei 91 % ab — die Seite
 * meldete „Diese Installation ist in Betrieb" und liess die halb gefüllte
 * Datenbank stehen. Bei einem grosszügigen Budget wäre es nie aufgefallen,
 * weil dann ein einziger Durchgang reicht.
 *
 * Die Schranke bleibt trotzdem, was sie ist — sie schützt eine LAUFENDE
 * Anlage. Der Unterschied hängt am Arbeitsstand: Wer einen hat, hat ihn auf
 * einer leeren Datenbank UND mit dem Nachweis begonnen. Ihn zu fälschen
 * setzt Dateizugriff voraus — und genau den weist der Nachweis ohnehin nach.
 */
$imLauf = in_array((string)($stand['phase'] ?? ''), ['einspielen', 'fertig'], true);
$darfArbeiten = ($leer || $imLauf) && $dbFehler === null;

/* ---- Die Handgriffe ------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $darfArbeiten) {
    if (!hash_equals((string)($_SESSION['wh_csrf'] ?? ''), (string)($_POST['csrf'] ?? ''))) {
        $error = 'Ungültiges Formular-Token. Bitte die Seite neu laden.';
    } else {
        $eingabe = strtolower(trim((string)($_POST['nachweis'] ?? '')));
        $eingabe = preg_replace('/^' . preg_quote($nachweisMuster, '/') . '/', '', $eingabe);
        $eingabe = (string)preg_replace('/\.txt$/', '', (string)$eingabe);
        if (!hash_equals($nachweis, $eingabe)) {
            $error = 'Der Nachweis stimmt nicht. Bitte die Zeichenfolge aus dem '
                   . 'Dateinamen im Anwendungsverzeichnis eintragen (siehe unten).';
        } else {
            $aktion = (string)($_POST['action'] ?? '');
            $anfang = microtime(true);
            $zeitLinks = static fn(): float => WH_BUDGET - (microtime(true) - $anfang);

            if ($aktion === 'auspacken' && !$leer) {
                /* ANFANGEN geht nur auf einer leeren Datenbank — auch dann
                 * nicht, wenn ein alter Arbeitsstand herumliegt. */
                $error = 'In der Datenbank stehen bereits Konten. Ein neuer '
                       . 'Durchgang beginnt nur auf einer leeren Datenbank.';
            } elseif ($aktion === 'auspacken') {
                $datei = (string)($_POST['datei'] ?? '');
                $pw    = (string)($_POST['passphrase'] ?? '');
                try {
                    $stand = wh_auspacken($datei, $pw, $stand);
                    wh_stand_schreiben($stand);
                    $notice = 'Ausgepackt: ' . edbak_groesse_text((int)$stand['sql_bytes'])
                            . ' SQL aus „' . $datei . '". '
                            . ($stand['endmarke'] ? 'Die Endmarke ist da — die Datei ist vollständig. '
                                                  : '')
                            . 'Jetzt einspielen.';
                } catch (Throwable $ex) {
                    $error = 'Das Auspacken ist gescheitert: ' . $ex->getMessage();
                }
            } elseif ($aktion === 'einspielen' && ($stand['phase'] ?? '') === 'einspielen') {
                try {
                    $stand = wh_einspielen($stand, $zeitLinks);
                    wh_stand_schreiben($stand);
                    if (($stand['phase'] ?? '') === 'fertig') {
                        $notice = 'Eingespielt: ' . number_format((int)$stand['statements'], 0, ',', '.')
                                . ' Anweisungen. Die Installation steht wieder.';
                    } else {
                        $notice = 'Durchgang zu Ende: '
                                . number_format((int)$stand['statements'], 0, ',', '.')
                                . ' Anweisungen, '
                                . round(100 * (int)$stand['versatz'] / max(1, (int)$stand['sql_bytes']))
                                . ' % der Datei. Weiter mit „Einspielen".';
                    }
                } catch (Throwable $ex) {
                    wh_stand_schreiben($stand);
                    $error = 'Das Einspielen ist an Anweisung '
                           . number_format((int)($stand['statements'] ?? 0) + 1, 0, ',', '.')
                           . ' gescheitert: ' . $ex->getMessage()
                           . ' Es wurde NICHTS zurückgenommen — die Datenbank steht auf halbem Weg. '
                           . 'Vor einem neuen Versuch die Datenbank leeren.';
                }
            } elseif ($aktion === 'abbrechen') {
                wh_arbeit_weg();
                $stand = [];
                $notice = 'Der Arbeitsstand wurde verworfen. Die Datei in „eingang" '
                        . 'bleibt liegen.';
            } elseif ($aktion === 'aufraeumen') {
                wh_arbeit_weg();
                @unlink($nachweisDatei);
                $stand = [];
                $notice = 'Aufgeräumt: ausgepackter Klartext und Nachweisdatei sind weg.';
                $nachweisOk = false;
            }
        }
    }
}

/**
 * Gang A — auspacken. Entsiegelt und entpackt nach `eingang/.arbeit/dump.sql`.
 *
 * ZWEI SCHRITTE UND EINE ZWISCHENDATEI, und das ist die Behebung eines
 * Fehlers, den erst der Lauf gezeigt hat:
 *
 *   A1  `.edk` -> `.arbeit/dump.sql.gz`   entsiegeln, Block für Block
 *   A2  `.arbeit/dump.sql.gz` -> `.sql`   entpacken, Stück für Stück
 *
 * Die erste Fassung sparte sich die Zwischendatei und schob jeden
 * entsiegelten Block durch `inflate_add()`. Das ging schief, und zwar
 * lehrreich: Ein erzeugter Dump besteht aus MEHREREN aneinandergehängten
 * gzip-Gliedern — eines je Häppchen des Erzeugens (siehe `komplett_lib.php`).
 * `inflate_add()` kennt genau ein Glied; am Ende des ersten meldet es
 * „data error", und was dastand, war die erste Portion und sah aus wie eine
 * Datei. Bei einem Dump, der in EINEM Zug entstanden ist, wäre der Fehler
 * nicht aufgefallen — die Probe fährt deshalb ausdrücklich den Dump aus
 * vierzehn Häppchen.
 *
 * `gzopen()`/`gzread()` lesen über Gliedgrenzen hinweg, wie `gunzip` und
 * `zcat` auch. Sie brauchen dafür aber eine DATEI und keinen Datenstrom —
 * daher die Zwischendatei. Sie wird sofort nach dem Entpacken gelöscht.
 *
 * WIEDERAUFNEHMBAR, ABER NICHT UMSONST. Bricht der Gang ab, beginnt er wieder
 * von vorn. Gemessen an einer Datenbank von 187 MB kostet ein ganzer
 * Durchgang unter einer Sekunde; es braucht also gar keinen zweiten.
 */
function wh_auspacken(string $datei, string $passwort, array $stand): array
{
    $quellen = wh_quellen();
    $gefunden = null;
    foreach ($quellen as $q) { if ($q['datei'] === $datei) { $gefunden = $q; break; } }
    if ($gefunden === null) { throw new RuntimeException('Diese Datei gibt es nicht in „eingang".'); }

    if (!is_dir(wh_arbeit()) && !@mkdir(wh_arbeit(), 0770, true) && !is_dir(wh_arbeit())) {
        throw new RuntimeException('Der Arbeitsordner liess sich nicht anlegen: ' . wh_arbeit());
    }
    $quelle = wh_eingang() . '/' . $datei;
    $ziel   = wh_dump();
    $zwischen = wh_arbeit() . '/dump.sql.gz';
    @set_time_limit(0);

    try {
        if ($gefunden['art'] === 'edk') {
            /* A1 — entsiegeln. */
            $schluessel = komp_schluessel_fuer((array)$gefunden['kopf'],
                                               $passwort === '' ? null : $passwort);
            $zh = fopen($zwischen, 'wb');
            if ($zh === false) { throw new RuntimeException('Die Zwischendatei liess sich nicht schreiben.'); }
            try {
                komp_oeffnen($quelle, $schluessel,
                    static function (string $gz) use ($zh): void { fwrite($zh, $gz); });
            } finally {
                fclose($zh);
            }
            wh_entpacken($zwischen, $ziel);
            @unlink($zwischen);
        } elseif ($gefunden['art'] === 'gz') {
            wh_entpacken($quelle, $ziel);
        } else {
            if (!@copy($quelle, $ziel)) {
                throw new RuntimeException('Der Dump liess sich nicht in den Arbeitsordner kopieren.');
            }
        }
    } catch (Throwable $ex) {
        @unlink($zwischen);
        throw $ex;
    }

    clearstatcache(true, $ziel);
    $bytes = (int)@filesize($ziel);
    if ($bytes <= 0) { throw new RuntimeException('Der ausgepackte Dump ist leer.'); }

    /* IST DIE DATEI VOLLSTÄNDIG? Ein Dump AUS DIESER ANWENDUNG trägt eine
     * Endmarke; fehlt sie, ist er mitten im Erzeugen abgebrochen. Ein fremder
     * Dump (`mysqldump`) hat keine — bei dem wird deshalb auch keine
     * verlangt, und die Seite sagt das offen.
     *
     * ZWEI SCHREIBWEISEN, UND DAS IST KEIN VERSEHEN (S7). Die Kopfzeile ist
     * zugleich Text für Menschen und Erkennungsmarke für diese Stelle. Mit
     * der Begriffsumstellung heisst sie „Komplett-Backup der Installation";
     * jeder Dump, der VOR S7 erzeugt wurde und heute auf einem Server oder
     * einem Backup-Ziel liegt, trägt aber noch „Komplett-Backup der
     * Installation". Würde hier nur die neue Schreibweise gesucht, gälte ein
     * solcher Dump als FREMD — und dann verlangt diese Stelle keine Endmarke
     * mehr und nähme einen abgebrochenen Stand klaglos an. Das ist der
     * gefährlichste Fehlschluss, den eine Umbenennung anrichten kann: Sie
     * schaltet eine Prüfung ab, ohne dass etwas rot wird. Die alte
     * Schreibweise darf am v1.0-Schnitt weg (R60), nicht vorher. */
    $anfang = (string)file_get_contents($ziel, false, null, 0, 200);
    $eigen  = str_contains($anfang, 'Komplett-Backup der Installation')
           || str_contains($anfang, 'Komplett-Backup der Installation');
    $endmarke = str_contains(wh_schwanz($ziel, 4096), KOMP_ENDMARKE);
    if ($eigen && !$endmarke) {
        @unlink($ziel);
        throw new RuntimeException('Dieses Backup ist unvollständig — die Endmarke fehlt. '
            . 'Es ist beim Erzeugen abgebrochen und wird nicht eingespielt.');
    }

    return ['phase' => 'einspielen', 'quelle' => $datei, 'sql_bytes' => $bytes,
            'versatz' => 0, 'statements' => 0, 'endmarke' => $endmarke, 'eigen' => $eigen,
            'kopf' => $gefunden['kopf'], 'begonnen' => gmdate('Y-m-d\TH:i:s\Z')];
}

/**
 * Eine gzip-Datei entpacken — über Gliedgrenzen hinweg.
 *
 * `gzread()` und nicht `gzdecode()`: Letzteres liest nur das ERSTE Glied und
 * bräuchte ausserdem die ganze Datei im Speicher.
 */
function wh_entpacken(string $quelle, string $ziel): void
{
    $gz = gzopen($quelle, 'rb');
    if ($gz === false) { throw new RuntimeException('Die gzip-Datei liess sich nicht öffnen.'); }
    $fh = fopen($ziel, 'wb');
    if ($fh === false) { gzclose($gz); throw new RuntimeException('Der Klartext liess sich nicht schreiben.'); }
    try {
        while (!gzeof($gz)) {
            $stueck = gzread($gz, 262144);
            if ($stueck === false) { throw new RuntimeException('Das Entpacken ist gescheitert.'); }
            if ($stueck === '') { break; }
            fwrite($fh, $stueck);
        }
    } finally {
        gzclose($gz);
        fclose($fh);
    }
}

/** Die letzten $n Byte einer Datei. */
function wh_schwanz(string $pfad, int $n): string
{
    $gr = (int)filesize($pfad);
    $fh = fopen($pfad, 'rb');
    if ($fh === false) { return ''; }
    fseek($fh, max(0, $gr - $n));
    $s = (string)fread($fh, $n);
    fclose($fh);
    return $s;
}

/**
 * Gang B — einspielen, Zeile für Zeile, mit Byteversatz als Fortsetzungsmarke.
 *
 * DER VERSATZ STEHT IM KLARTEXT-DUMP UND NICHT IM GEPACKTEN. Genau dafür gibt
 * es Gang A: In einer gepackten Datei kostet ein Sprung an Position N das
 * Entpacken der ersten N Byte — bei jedem Durchgang aufs Neue. Im Klartext
 * ist es ein `fseek`.
 *
 * DIE `SET`-ZEILEN WERDEN JE DURCHGANG NEU GESETZT. Sie gelten je Verbindung,
 * und jeder Durchgang ist eine neue Anfrage mit einer neuen. Stünden sie nur
 * in der Datei, liefe ab dem zweiten Durchgang wieder mit eingeschalteter
 * Fremdschlüsselprüfung — und die Reihenfolge der Tabellen müsste plötzlich
 * stimmen, auf die es dann ankäme.
 */
function wh_einspielen(array $stand, callable $zeitLinks): array
{
    $pfad = wh_dump();
    if (!is_file($pfad)) { throw new RuntimeException('Der ausgepackte Dump ist verschwunden.'); }
    $pdo = db();
    $pdo->exec('SET NAMES utf8mb4');
    $pdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('SET UNIQUE_CHECKS = 0');

    $fh = fopen($pfad, 'rb');
    if ($fh === false) { throw new RuntimeException('Der Dump liess sich nicht öffnen.'); }
    fseek($fh, (int)($stand['versatz'] ?? 0));
    try {
        while (!feof($fh)) {
            if ($zeitLinks() < 3.0) { break; }
            $zeile = fgets($fh);
            if ($zeile === false) { break; }
            $stand['versatz'] = ftell($fh);
            $sql = trim($zeile);
            if ($sql === '' || str_starts_with($sql, '--')) { continue; }
            $pdo->exec(rtrim($sql, ';'));
            $stand['statements'] = (int)($stand['statements'] ?? 0) + 1;
        }
        $fertig = feof($fh);
    } finally {
        fclose($fh);
    }

    if ($fertig) {
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $pdo->exec('SET UNIQUE_CHECKS = 1');
        $stand['phase'] = 'fertig';
        $stand['beendet'] = gmdate('Y-m-d\TH:i:s\Z');
        /* DER KLARTEXT WIRD SOFORT GELÖSCHT. Er ist eine vollständige,
         * unverschlüsselte Abschrift jeder Tabelle und hat auf der Platte
         * nichts verloren, sobald er drin ist. */
        @unlink(wh_dump());
    }
    return $stand;
}

$quellen  = $leer && $dbFehler === null ? wh_quellen() : [];
$halbdrin = !$leer && $imLauf;
$phase    = (string)($stand['phase'] ?? '');
$dumpWeb  = (string)(($stand['kopf'] ?? [])['web'] ?? '');

require_once __DIR__ . '/ui.php';
ui_seite_start(['titel' => 'Installation wiederherstellen']);
ui_kopf(['menue' => false]);
?>
<div class="rahmen rahmen-lesespalte">
  <main class="inhalt">

  <?php ui_titelzeile([
      'titel' => 'Installation wiederherstellen',
      'unter' => 'Ein Komplett-Backup in eine <strong>leere</strong> Datenbank '
               . 'einspielen. Diese Seite arbeitet nur, solange es noch kein Konto gibt.',
  ]); ?>

  <?php ui_meldung($notice, $error, 'info', '  '); ?>

  <?php if ($dbFehler !== null): ?>
    <?php ui_karte_start(['titel' => 'Die Datenbank antwortet nicht']); ?>
      <p class="feld-hinweis">Die Verbindung nach <code>config.php</code> kam nicht
      zustande: <code><?= ui_e($dbFehler) ?></code></p>
      <p class="feld-hinweis">Zu prüfen: Stimmen Rechnername, Datenbankname, Nutzer und
      Passwort in <code>config.php</code>? Existiert die Datenbank überhaupt? Sie muss
      angelegt sein — leer, aber vorhanden.</p>
    <?php ui_karte_ende(); ?>

  <?php elseif (!$darfArbeiten): ?>
    <?php ui_karte_start(['titel' => 'Diese Installation ist in Betrieb']); ?>
      <p class="feld-hinweis">In der Datenbank stehen <strong><?= (int)$konten ?></strong>
      Konten. Eine Wiederherstellung würde sie überschreiben, und deshalb passiert
      hier nichts mehr.</p>
      <p class="feld-hinweis">Wer einen einzelnen Stand zurückholen will, tut das
      angemeldet unter <a href="admin_sicherungen.php">Konto-Backups</a>. Wer wirklich
      die ganze Installation ersetzen will, leert die Datenbank vorher mit dem
      Werkzeug des Hosters — eine bewusste Handlung an der richtigen Stelle.</p>
    <?php ui_karte_ende(); ?>

  <?php else: ?>
    <?php if ($phase === 'fertig'): ?>
      <?php ui_karte_start(['titel' => 'Fertig']); ?>
        <?php
        ui_zeile(['text' => 'Eingespielt',
                  'klein' => 'aus „' . (string)($stand['quelle'] ?? '') . '"',
                  'plaketten' => ui_plakette(
                      number_format((int)($stand['statements'] ?? 0), 0, ',', '.')
                      . ' Anweisungen', ['ton' => 'blau'])]);
        if ($dumpWeb !== '' && $dumpWeb !== WEB_VERSION) {
            ui_zeile(['text' => 'Der Dump stammt aus einer anderen Fassung',
                      'klein' => 'Backup: Web ' . $dumpWeb . ' · hier läuft Web '
                               . WEB_VERSION . '. Der Migrationslauf ist deshalb '
                               . 'nicht optional.',
                      'plaketten' => ui_plakette('Updates ausführen', ['ton' => 'orange'])]);
        }
        ?>
        <p class="feld-hinweis"><strong>Jetzt in dieser Reihenfolge:</strong></p>
        <p class="feld-hinweis">1. <a href="index.php">Anmelden</a> — mit dem
        verwaltenden Konto aus dem Backup; die Passwörter sind dieselben wie
        vorher.<br>
        2. <a href="betrieb_updates.php">Betrieb → Updates</a> aufrufen und den
        Migrationslauf ausführen.
        Er läuft hier bewusst nicht mit: Migrationen können Spalten löschen, und
        dazwischen gehört eine angemeldete Person und ein Knopf.<br>
        3. Danach unten aufräumen — der ausgepackte Klartext und die Nachweisdatei
        haben auf dem Server nichts mehr verloren.</p>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>

    <?php ui_karte_start(['titel' => 'Nachweis']); ?>
      <?php if (!$nachweisOk): ?>
        <p class="feld-hinweis">Im Anwendungsverzeichnis lässt sich nichts schreiben —
        die Nachweisdatei konnte nicht angelegt werden. Ohne sie geht es hier nicht
        weiter. Bitte die Schreibrechte auf <code><?= ui_e(basename(__DIR__)) ?></code>
        prüfen.</p>
      <?php else: ?>
        <p class="feld-hinweis">Im Anwendungsverzeichnis liegt eine Datei mit dem Namen
        <code><?= ui_e($nachweisMuster) ?>&hellip;.txt</code>. Die Zeichenfolge aus
        ihrem Namen gehört in das Feld unten — sie beweist, dass Sie Zugriff auf
        dieses Verzeichnis haben. Ohne diesen Nachweis geschieht auf dieser Seite
        nichts.</p>
      <?php endif; ?>
    <?php ui_karte_ende(); ?>

    <?php if ($phase === 'einspielen'): ?>
      <?php ui_karte_start(['titel' => 'Einspielen']); ?>
        <?php if ($halbdrin): ?>
          <?= ui_meldung_markup('info', 'Die Datenbank ist nicht mehr leer — sie '
              . 'füllt sich gerade. Solange dieser Arbeitsstand besteht, geht es '
              . 'hier weiter; ein NEUER Durchgang würde eine wieder geleerte '
              . 'Datenbank verlangen.', '        ') ?>
        <?php endif; ?>
        <?php
        $anteil = (int)round(100 * (int)$stand['versatz'] / max(1, (int)$stand['sql_bytes']));
        ui_zeile(['text' => 'Quelle', 'klein' => (string)$stand['quelle'],
                  'plaketten' => ui_plakette(edbak_groesse_text((int)$stand['sql_bytes'])
                                             . ' SQL', ['ton' => 'neutral'])]);
        ui_zeile(['text' => 'Fortschritt',
                  'klein' => number_format((int)$stand['statements'], 0, ',', '.')
                           . ' Anweisungen ausgeführt',
                  'plaketten' => ui_plakette($anteil . ' %',
                                             ['ton' => $anteil >= 100 ? 'blau' : 'orange'])]);
        if (!($stand['endmarke'] ?? false)) {
            ui_zeile(['text' => 'Keine Endmarke',
                      'klein' => 'Die Datei stammt nicht aus dieser Anwendung (etwa aus '
                               . '`mysqldump`). Ob sie vollständig ist, lässt sich hier '
                               . 'nicht feststellen.',
                      'plaketten' => ui_plakette('fremder Dump', ['ton' => 'orange'])]);
        }
        ?>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= ui_e((string)$_SESSION['wh_csrf']) ?>">
          <input type="hidden" name="action" value="einspielen">
          <?php ui_feld(['name' => 'nachweis', 'label' => 'Nachweis', 'pflicht' => true,
                         'wert' => '', 'platzhalter' => str_repeat('0', 32)]); ?>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Einspielen', 'symbol' => 'haken', 'art' => 'primaer']) ?>
          </div>
        </form>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= ui_e((string)$_SESSION['wh_csrf']) ?>">
          <input type="hidden" name="action" value="abbrechen">
          <?php ui_feld(['name' => 'nachweis', 'label' => 'Nachweis (zum Verwerfen)',
                         'wert' => '']); ?>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Arbeitsstand verwerfen', 'symbol' => 'korb',
                          'art' => 'gefahr']) ?>
          </div>
        </form>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>

    <?php if ($phase !== 'einspielen'): ?>
      <?php ui_karte_start(['titel' => 'Dateien in „eingang"', 'zahl' => count($quellen)]); ?>
        <?php if ($quellen === []): ?>
          <p class="feld-hinweis">Der Ordner
          <code>sicherungen/<?= WH_EINGANG ?>/</code> ist leer oder fehlt. Das
          Backup gehört dort hinein — per FTP, SFTP oder Dateimanager des
          Hosters. Erkannt werden <code>.edk</code> (versiegelte
          Komplett-Backup), <code>.sql.gz</code> und <code>.sql</code>.</p>
          <p class="feld-hinweis">Es gibt hier bewusst kein Hochladen über den
          Browser: Wer die Datei ablegen kann, hat Dateizugriff — und ein
          Upload-Formular auf einer Seite ohne Anmeldung wäre genau die Lücke,
          die der Nachweis schliessen soll.</p>
        <?php else: ?>
          <?php foreach ($quellen as $nr => $q): $k = $q['kopf'] ?? []; ?>
            <?php
            ui_zeile([
                'text' => $q['datei'],
                'klein' => $q['art'] === 'edk'
                    ? ('versiegelt · ' . (isset($k['zeilen'])
                        ? number_format((int)$k['zeilen'], 0, ',', '.') . ' Zeilen aus '
                          . (int)($k['tabellen'] ?? 0) . ' Tabellen · Web '
                          . (string)($k['web'] ?? '?')
                        : '') . ' · '
                        . (($k['kdf'] ?? null) === null
                           ? 'mit dem Serverschlüssel aus config.php'
                           : 'mit einer Passphrase'))
                    : ($q['art'] === 'gz' ? 'gepackter SQL-Dump' : 'SQL-Dump im Klartext'),
                'plaketten' => ui_plakette(edbak_groesse_text((int)$q['groesse']),
                                           ['ton' => 'neutral']),
            ]);
            ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= ui_e((string)$_SESSION['wh_csrf']) ?>">
              <input type="hidden" name="action" value="auspacken">
              <input type="hidden" name="datei" value="<?= ui_e($q['datei']) ?>">
              <div class="fld-reihe">
                <?php ui_feld(['name' => 'nachweis', 'label' => 'Nachweis', 'pflicht' => true,
                               'wert' => '', 'platzhalter' => str_repeat('0', 32)]); ?>
                <?php if ($q['art'] === 'edk' && (($k['kdf'] ?? null) !== null)): ?>
                  <?php ui_feld(['name' => 'passphrase', 'label' => 'Passphrase',
                                 'art' => 'password', 'pflicht' => true, 'wert' => '',
                                 'klein' => 'Diese Datei ist unter einer Passphrase '
                                          . 'versiegelt, nicht unter dem Serverschlüssel.']); ?>
                <?php endif; ?>
              </div>
              <div class="listen-form-fuss">
                <?= ui_knopf(['text' => 'Auspacken und prüfen', 'symbol' => 'schloss-offen',
                              'art' => $nr === 0 ? 'primaer' : 'neutral']) ?>
              </div>
            </form>
          <?php endforeach; ?>
        <?php endif; ?>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>

    <?php if ($phase === 'fertig' || $phase === 'einspielen'): ?>
      <?php ui_karte_start(['titel' => 'Aufräumen']); ?>
        <p class="feld-hinweis">Entfernt den ausgepackten Klartext-Dump aus
        <code>sicherungen/<?= WH_EINGANG ?>/<?= WH_ARBEIT ?>/</code> und die
        Nachweisdatei. Die Backup-Datei selbst bleibt liegen.</p>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= ui_e((string)$_SESSION['wh_csrf']) ?>">
          <input type="hidden" name="action" value="aufraeumen">
          <?php ui_feld(['name' => 'nachweis', 'label' => 'Nachweis', 'pflicht' => true,
                         'wert' => '']); ?>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Aufräumen', 'symbol' => 'korb', 'art' => 'neutral']) ?>
          </div>
        </form>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>

    <?php ui_karte_start(['titel' => 'Was hier gilt', 'vorschau' => 'Wiederanlauf']); ?>
      <p class="feld-hinweis"><strong>Die Reihenfolge des Wiederanlaufs:</strong>
      Datenbank anlegen (leer) · Anwendungsdateien hochladen ·
      <code>config.php</code> aus dem Wiederanlaufpaket daneben legen ·
      Backup-Datei nach <code>sicherungen/<?= WH_EINGANG ?>/</code> ·
      diese Seite · anmelden · <a href="betrieb_updates.php">Updates</a>. Ausführlich steht
      es im Runbook, <code>docs/Technik.md</code>, Abschnitt 7.</p>

      <p class="feld-hinweis"><strong>Der Serverschlüssel entscheidet.</strong> Eine
      <code>.edk</code>-Datei mit dem Vermerk „mit dem Serverschlüssel" lässt sich
      nur mit <em>der</em> <code>config.php</code> öffnen, die beim Erzeugen galt.
      Ist sie verloren, hilft das Backup nicht — deshalb gehört sie ins
      Wiederanlaufpaket, getrennt vom Server aufbewahrt.</p>

      <p class="feld-hinweis"><strong>Es wird nichts zurückgenommen.</strong> Scheitert
      das Einspielen auf halbem Weg, steht die Datenbank halb da. Ein neuer Versuch
      braucht dann eine wieder geleerte Datenbank; ein Dump über einen halben
      Bestand zu legen ergäbe eine Mischung, die niemand mehr auseinanderbekommt.</p>
    <?php ui_karte_ende(true); ?>
  <?php endif; ?>

  </main>
</div>
<?php /* OHNE die Verweise auf Impressum und Datenschutz, aus demselben Grund
       wie im Einrichter: Die beiden Seiten lesen ihre Texte aus der Tabelle
       `rechtstexte`, und die gibt es auf einer leeren Datenbank noch nicht —
       der Verweis führte ins Leere. */ ?>
<?php ui_fuss_seite(['rechtslinks' => false]); ?>
<?php ui_seite_ende(); ?>
