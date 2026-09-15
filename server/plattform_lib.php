<?php
declare(strict_types=1);

/**
 * DAS PLATTFORMPROFIL — eine Prueffunktion fuer zwei Leser (P5a/AP2, E-P5a-19).
 *
 * WOZU. Bis Web 20.4.0 prueft `install.php` genau vier Erweiterungen (`zip`,
 * `zlib`, `openssl`, `mbstring`) und sonst nichts: keine PHP-Version, kein
 * `pdo_mysql`, keine Weblimits, keine Datenbankfassung, keine
 * Verbindungsgrenze. Eine Installation auf PHP 8.0 faellt erst beim ersten
 * Formular mit einem Fatal Error auf; ein `memory_limit` von 32 MB erst beim
 * ersten grossen Export; eine `max_user_connections` von 2 erst dann, wenn
 * zwei Uhren gleichzeitig senden. Alle drei sind Eigenschaften der
 * PLATTFORM, und alle drei sind vorher messbar.
 *
 * ZWEI STUFEN, MEHR NICHT (E-PP-01):
 *
 *   muss       Fehlt es, laeuft die Anwendung nicht — und sagt es.
 *              `install.php` prueft es VOR der Einrichtung, die Statusseite
 *              im Betrieb und zeigt ROT, wenn es nachtraeglich wegfaellt.
 *   empfohlen  Wird genutzt, wenn es da ist. Fehlt es, laeuft die Anwendung
 *              vollstaendig — langsamer, mit Verzoegerung oder mit einem
 *              Handgriff mehr. Auf der Statusseite ein HINWEIS, keine
 *              Ampelfarbe.
 *
 * DIE REGEL IST DAUERHAFT, DIE ZAHL IST EIN STAND (E-PP-03). Fuer Versionen
 * gilt: eine Fassung, die vom Hersteller noch mit Sicherheitskorrekturen
 * versorgt wird. Die Zahlen unten sind der Stand vom 15.09.2026 und stehen an
 * EINER Stelle — hier, als Konstanten. Wer sie in einen Meldungstext
 * schreibt, hat zwei Wahrheiten angelegt.
 *
 * WAS SIE NICHT IST. Keine Beschreibung des heutigen Tarifs und keine
 * Anleitung fuer einen bestimmten Hoster. Ein Hosterwechsel aendert
 * `config.php`, keine Codezeile (E-PP-04).
 *
 * EHRLICHKEIT UEBER DAS NICHT MESSBARE. `ok` ist dreiwertig: `true`
 * erfuellt, `false` nicht erfuellt, **`null` nicht feststellbar**. Der freie
 * Plattenplatz ist der Fall, auf den es ankommt — `disk_free_space()` liefert
 * auf geteiltem Webspace den Datentraeger des HOSTS und nicht das Kontingent
 * dieses Kontos. Eine Zahl im Terabyte-Bereich waere schlimmer als keine: Man
 * glaubte, es sei Platz (PP-5, dieselbe Ueberlegung wie in
 * `speicher_lib.php`).
 *
 * WER DIESE DATEI LAEDT, HAT NOCH NICHTS GEAENDERT. Sie misst und gibt
 * zurueck; sie schreibt nichts und schaltet nichts.
 */

require_once __DIR__ . '/email_lib.php';

/* ---- Die Zahlen, an einer Stelle (E-PP-03) ------------------------------- */

/* Untergrenze PHP: `PLATTFORM_PHP_MIN`, definiert in `php_mindest.php`.
 *
 * SIE STEHT NICHT HIER, obwohl sie hierher gehoerte. Die Weiche am Kopf von
 * `install.php` braucht dieselbe Zahl und darf DIESE Datei nicht laden — sie
 * enthaelt `match` und waere auf der Fassung, die abgewiesen werden soll,
 * schon beim Uebersetzen ein Parse Error. Die Zahl liegt deshalb in einer
 * eigenen, absichtlich altertuemlich geschriebenen Datei; beide laden sie
 * (E-PP-03: eine Stelle, nicht drei Meldungstexte). */
require_once __DIR__ . '/php_mindest.php';

/** Empfohlene PHP-Fassung: aktive Pflege, nicht nur Sicherheitskorrekturen. */
const PLATTFORM_PHP_EMPFOHLEN = '8.3';

/**
 * Erweiterungen, ohne die es spaeter klemmt — je mit dem Grund.
 *
 * `json`, `ctype`, `session` und `hash` stehen NICHT hier: Sie sind seit
 * PHP 8 fest eingebaut und lassen sich nicht abschalten. Eine Pruefung, die
 * nie anschlagen kann, ist eine Zeile Beruhigung.
 */
const PLATTFORM_ERWEITERUNGEN = [
    'pdo_mysql' => 'Die Datenbankanbindung (bis Web 20.4.0 stillschweigend vorausgesetzt)',
    'openssl'   => 'Zufall und Prüfsummen',
    'mbstring'  => 'Texte in UTF-8',
    'zip'       => 'Backups sind ZIP-Dateien (Klasse ZipArchive)',
    'zlib'      => 'GPS-Daten werden komprimiert gespeichert',
];

/**
 * Weblimits nach Z3 — je Einstellung der Mindestwert in Byte beziehungsweise
 * Sekunden, und wofuer er gebraucht wird.
 *
 * DER NAME DER EINSTELLUNG STEHT MIT DA, und das ist der ganze Zweck: „zu
 * klein" hilft niemandem weiter, `post_max_size` schon — danach laesst sich
 * suchen, und viele Hoster haben dafuer ein Feld im Kundenmenue.
 */
const PLATTFORM_WEBLIMITS = [
    'memory_limit'        => [67108864, 'byte', 'PHP-Speicherspitze je Anfrage (Z3: 64 MB)'],
    'max_execution_time'  => [30,       'sek',  'Laufzeit je Anfrage (Z3: 30 s)'],
    'post_max_size'       => [2097152,  'byte', 'Größe eines Uploads (Z3: 2 MB)'],
    'upload_max_filesize' => [2097152,  'byte', 'Größe einer hochgeladenen Datei (Z3: 2 MB)'],
];

/** Untergrenze der Datenbank: MySQL / MariaDB. */
const PLATTFORM_MYSQL_MIN   = '8.0';
const PLATTFORM_MARIADB_MIN = '10.6';

/** Empfohlen: eine LTS-Fassung in Herstellerpflege (Stand 15.09.2026). */
const PLATTFORM_MYSQL_LTS   = '8.4';
const PLATTFORM_MARIADB_LTS = '10.11';

/** Verbindungsgrenze: Muss und Empfohlen. */
const PLATTFORM_VERBINDUNGEN_MIN       = 10;
const PLATTFORM_VERBINDUNGEN_EMPFOHLEN = 50;

/* ---- Hilfen -------------------------------------------------------------- */

/**
 * Ein `php.ini`-Groessenwert als Byte.
 *
 * `-1` und `0` heissen „unbegrenzt" und werden als PHP_INT_MAX zurueckgegeben
 * — sonst meldete ein `memory_limit = -1` (durchaus verbreitet) eine
 * Unterschreitung.
 */
function plattform_byte(string $wert): int
{
    $wert = trim($wert);
    if ($wert === '' ) { return 0; }
    if ($wert === '-1' || $wert === '0') { return PHP_INT_MAX; }
    $einheit = strtolower(substr($wert, -1));
    $zahl    = (float)$wert;
    return (int)match ($einheit) {
        'g'     => $zahl * 1024 * 1024 * 1024,
        'm'     => $zahl * 1024 * 1024,
        'k'     => $zahl * 1024,
        default => $zahl,
    };
}

/** Byte lesbar — dieselbe Schreibweise wie `edbak_groesse_text()`. */
function plattform_groesse(int $b): string
{
    if ($b >= PHP_INT_MAX) { return 'unbegrenzt'; }
    foreach ([['GB', 1073741824], ['MB', 1048576], ['KB', 1024]] as [$e, $t]) {
        if ($b >= $t) { return rtrim(rtrim(number_format($b / $t, 1, ',', '.'), '0'), ',') . ' ' . $e; }
    }
    return $b . ' B';
}

/**
 * IST EIN VERZEICHNIS WIRKLICH BESCHREIBBAR? Mit Probedatei, nicht mit
 * `is_writable()`.
 *
 * `is_writable()` beantwortet die Frage anhand der Rechtebits — und liegt
 * falsch, sobald ACLs, `open_basedir`, ein schreibgeschuetztes Dateisystem
 * oder SELinux im Spiel sind. Auf geteiltem Webspace ist genau das der
 * Regelfall. Die Probedatei fragt das Dateisystem selbst und raeumt hinter
 * sich auf.
 *
 * DER NAME IST ZUFAELLIG. Eine feste Probedatei waere ueber die Adresszeile
 * abrufbar, wenn das Verzeichnis im Web-Wurzelverzeichnis liegt — dieselbe
 * Ueberlegung wie beim Nachweis in `install.php`.
 *
 * @return array{ok: bool, grund: ?string}
 */
function plattform_schreibprobe(string $pfad): array
{
    if (!is_dir($pfad)) {
        return ['ok' => false, 'grund' => 'Das Verzeichnis gibt es nicht: ' . $pfad];
    }
    $datei = rtrim($pfad, '/') . '/.schreibprobe-' . bin2hex(random_bytes(6)) . '.tmp';
    $hin   = @file_put_contents($datei, "Probe\n", LOCK_EX);
    if ($hin === false) {
        return ['ok' => false, 'grund' => 'Es ließ sich nichts anlegen in ' . $pfad
                                        . ' — Schreibrechte prüfen'];
    }
    $zurueck = @file_get_contents($datei);
    @unlink($datei);
    if ($zurueck !== "Probe\n") {
        return ['ok' => false, 'grund' => 'Geschrieben, aber nicht zurückgelesen in '
                                        . $pfad . ' — das Dateisystem meldet etwas anderes, '
                                        . 'als es tut'];
    }
    clearstatcache(true, $datei);
    return ['ok' => true, 'grund' => null];
}

/**
 * Fassung und Art der Datenbank.
 *
 * MariaDB gibt sich in `VERSION()` selbst zu erkennen („10.11.14-MariaDB-…").
 * Die beiden Zaehlungen haben nichts miteinander zu tun; sie deshalb
 * getrennt zu vergleichen ist kein Feinschliff, sondern der Unterschied
 * zwischen richtig und falsch.
 *
 * @return array{art: string, version: string, roh: string}
 */
function plattform_db_fassung(PDO $pdo): array
{
    try {
        $roh = (string)$pdo->query('SELECT VERSION()')->fetchColumn();
    } catch (Throwable) {
        return ['art' => 'unbekannt', 'version' => '', 'roh' => ''];
    }
    $art = stripos($roh, 'mariadb') !== false ? 'MariaDB' : 'MySQL';
    preg_match('/^(\d+\.\d+\.\d+)/', $roh, $m);
    return ['art' => $art, 'version' => $m[1] ?? '', 'roh' => $roh];
}

/**
 * Eine Servervariable der Datenbank. `null`, wenn sie nicht zu haben ist.
 *
 * OHNE PLATZHALTER, UND DAS IST KEINE NACHLAESSIGKEIT. `SHOW VARIABLES LIKE ?`
 * scheitert auf MariaDB mit einem Syntaxfehler (1064): Die Anweisung ist
 * keine, die sich vorbereiten laesst, und `PDO::ATTR_EMULATE_PREPARES` steht
 * in dieser Anwendung ausdruecklich auf `false`. Gemessen am 15.09.2026 gegen
 * MariaDB 10.11.14 — die erste Fassung dieser Funktion meldete deshalb still
 * „Verbindungsgrenze unbekannt".
 *
 * Der Name wird stattdessen GEPRUEFT statt gebunden: nur Kleinbuchstaben und
 * Unterstriche, und er kommt ohnehin aus einer festen Liste im Code. Ein
 * `SELECT @@GLOBAL.<name>` waere die Alternative gewesen und wirft bei einem
 * unbekannten Namen eine Ausnahme, wo `SHOW VARIABLES` einfach nichts
 * liefert — bei einer Auskunftsfunktion ist das der schlechtere Handel.
 */
function plattform_db_variable(PDO $pdo, string $name): ?string
{
    if (!preg_match('/^[a-z_]+$/', $name)) { return null; }
    try {
        $z = $pdo->query("SHOW VARIABLES LIKE '" . $name . "'")->fetch();
        return $z === false ? null : (string)($z['Value'] ?? $z[1] ?? '');
    } catch (Throwable) {
        return null;
    }
}

/** Ein Befund. `ok`: true erfuellt · false nicht · null nicht feststellbar. */
function plattform_befund(string $schluessel, string $name, string $stufe,
                          string $gemessen, string $soll, ?bool $ok,
                          string $klein = '', ?string $einstellung = null): array
{
    return ['schluessel' => $schluessel, 'name' => $name, 'stufe' => $stufe,
            'gemessen' => $gemessen, 'soll' => $soll, 'ok' => $ok,
            'klein' => $klein, 'einstellung' => $einstellung];
}

/* ---- Die Pruefung -------------------------------------------------------- */

/**
 * Das ganze Profil, gemessen.
 *
 * @param ?PDO  $pdo     Verbindung; ohne sie entfallen die vier DB-Befunde
 *                       (`install.php` ruft vor dem Verbindungsaufbau).
 * @param bool  $mitNetz SMTP wirklich anwaehlen? Nur `install.php` tut das —
 *                       die Statusseite darf bei jedem Aufruf keinen
 *                       Mailserver anrufen, und ein haengender haelt sonst
 *                       einen PHP-Arbeitsprozess.
 * @return list<array> Befunde in Anzeigereihenfolge
 */
function plattform_pruefen(?PDO $pdo = null, bool $mitNetz = false): array
{
    $b = [];

    /* ---- 1 PHP-Version ---------------------------------------------------- */
    $phpOk = version_compare(PHP_VERSION, PLATTFORM_PHP_MIN, '>=');
    $b[] = plattform_befund('php', 'PHP-Version', 'muss',
        PHP_VERSION, '≥ ' . PLATTFORM_PHP_MIN, $phpOk,
        $phpOk ? 'In Herstellerpflege.'
               : 'Diese Fassung bekommt keine Sicherheitskorrekturen mehr. Der Code '
               . 'braucht sie nicht — die Untergrenze steht hier, damit niemand '
               . 'unbemerkt auf einem ungepflegten Stand läuft.');

    $b[] = plattform_befund('php_empf', 'PHP in aktiver Pflege', 'empfohlen',
        PHP_VERSION, '≥ ' . PLATTFORM_PHP_EMPFOHLEN,
        version_compare(PHP_VERSION, PLATTFORM_PHP_EMPFOHLEN, '>='),
        'Aktive Pflege statt nur Sicherheitskorrekturen.');

    /* ---- 2 Erweiterungen -------------------------------------------------- */
    $fehlen = [];
    foreach (PLATTFORM_ERWEITERUNGEN as $e => $wofuer) {
        if (!extension_loaded($e)) { $fehlen[] = $e . ' (' . $wofuer . ')'; }
    }
    $b[] = plattform_befund('erweiterungen', 'PHP-Erweiterungen', 'muss',
        $fehlen === [] ? 'alle ' . count(PLATTFORM_ERWEITERUNGEN) . ' vorhanden'
                       : count($fehlen) . ' fehlen',
        implode(', ', array_keys(PLATTFORM_ERWEITERUNGEN)),
        $fehlen === [],
        $fehlen === [] ? '' : 'Fehlt: ' . implode(' · ', $fehlen)
                            . '. Beim Hoster freischalten lassen.');

    /* ---- 3 Weblimits ------------------------------------------------------ */
    foreach (PLATTFORM_WEBLIMITS as $name => [$min, $art, $wofuer]) {
        $roh = (string)ini_get($name);
        if ($art === 'byte') {
            $ist      = plattform_byte($roh);
            $istText  = $roh === '' ? 'nicht gesetzt' : $roh . ' (' . plattform_groesse($ist) . ')';
            $sollText = '≥ ' . plattform_groesse($min);
        } else {
            /* `max_execution_time = 0` heisst unbegrenzt — auf der
             * Kommandozeile die Vorgabe, im Web selten und in Ordnung. */
            $ist      = ((int)$roh === 0) ? PHP_INT_MAX : (int)$roh;
            $istText  = ((int)$roh === 0) ? 'unbegrenzt' : $roh . ' s';
            $sollText = '≥ ' . $min . ' s';
        }
        $b[] = plattform_befund('ini_' . $name, $name, 'muss',
            $istText, $sollText, $ist >= $min, $wofuer, $name);
    }

    /* ---- 4 OPcache (empfohlen) -------------------------------------------- */
    $opAn = function_exists('opcache_get_status');
    if ($opAn) {
        $st   = @opcache_get_status(false);
        $opAn = is_array($st) && !empty($st['opcache_enabled']);
    }
    $b[] = plattform_befund('opcache', 'OPcache', 'empfohlen',
        $opAn ? 'aktiv' : 'aus', 'aktiv', $opAn,
        $opAn ? 'Die Anwendung ist darauf vorbereitet: Nach jedem Schreiben in '
              . '`config.php` läuft `opcache_invalidate()`.'
              : 'Ohne OPcache läuft die Anwendung vollständig — jede Anfrage '
              . 'übersetzt dann alles neu.');

    /* ---- 5 Schreibrechte, je mit Probedatei -------------------------------- */
    $orte = [
        'wurzel'  => ['Anwendungswurzel', __DIR__,
                      '`wartung.lock` und bei der Einrichtung `config.php` und `install.lock`'],
        'ablage'  => ['Ablage der Sicherungen', __DIR__ . '/sicherungen',
                      'Konto-Backups und Komplett-Stände'],
        'tempdir' => ['Temporäres Verzeichnis', sys_get_temp_dir(),
                      'Die Backups bauen ihre ZIP-Dateien dort'],
    ];
    foreach ($orte as $k => [$titel, $pfad, $wofuer]) {
        /* Die Ablage entsteht erst beim ersten Backup. Gibt es sie noch
         * nicht, wird die WURZEL geprueft — dort wuerde sie angelegt. Ein
         * „Verzeichnis gibt es nicht" waere hier eine Falschmeldung. */
        $gepruefterPfad = ($k === 'ablage' && !is_dir($pfad)) ? __DIR__ : $pfad;
        $p = plattform_schreibprobe($gepruefterPfad);
        $b[] = plattform_befund('schreib_' . $k, $titel, 'muss',
            $p['ok'] ? 'beschreibbar' : 'nicht beschreibbar', 'beschreibbar', $p['ok'],
            $p['ok'] ? $wofuer . ' · ' . $gepruefterPfad
                     : (string)$p['grund']);
    }

    /* `config.php` beschreibbar ist EMPFOHLEN, nicht Muss (PP-5): Die
     * Backup-Ziele, der Serverschluessel und der Server-Anteil bieten dann
     * einen Knopf; sonst die eine Zeile zum Eintragen von Hand. */
    $cfg   = __DIR__ . '/config.php';
    $cfgDa = file_exists($cfg);
    $b[] = plattform_befund('config_schreibbar', 'config.php beschreibbar', 'empfohlen',
        !$cfgDa ? 'noch nicht angelegt' : (is_writable($cfg) ? 'beschreibbar' : 'nur lesbar'),
        'beschreibbar', !$cfgDa ? null : is_writable($cfg),
        'Ist sie es, genügen die Knöpfe für Backup-Ziele, Serverschlüssel und '
      . 'Server-Anteil; sonst steht dort die Zeile zum Eintragen von Hand.');

    /* ---- 6 Freier Platz — und die Ehrlichkeit darueber ---------------------- */
    $frei = @disk_free_space(__DIR__);
    if ($frei === false) {
        $b[] = plattform_befund('platz', 'Freier Platz', 'muss',
            'unbekannt', '≥ 2× groesstes Komplett-Backup', null,
            'Der Hoster meldet keinen freien Platz. Das Kontingent bitte von Hand '
          . 'prüfen und unter Betrieb → Servereinstellungen eintragen — hier steht '
          . 'kein geratener Wert.');
    } else {
        $groesstes = 0;
        if (function_exists('komp_staende')) {
            foreach (komp_staende() as $s) { $groesstes = max($groesstes, (int)$s['groesse']); }
        }
        $ok = $groesstes === 0 ? null : ((int)$frei >= $groesstes);
        $b[] = plattform_befund('platz', 'Freier Platz', 'muss',
            plattform_groesse((int)$frei),
            $groesstes > 0 ? '≥ 2× ' . plattform_groesse($groesstes) : 'nicht bestimmbar',
            $ok,
            $groesstes === 0
                ? 'Es gibt noch kein Komplett-Backup, gegen das sich rechnen ließe.'
                : 'Unter dem Einfachen des größten Komplett-Backups schlägt das '
                . 'nächste fehl. ACHTUNG: Auf geteiltem Webspace meldet PHP den '
                . 'Datenträger des HOSTS, nicht das Kontingent dieses Kontos — die '
                . 'Zahl ist deshalb eine Untergrenze für schlechte Nachrichten und '
                . 'keine Entwarnung.');
    }

    /* ---- 7 HTTPS ----------------------------------------------------------- */
    $cli   = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    $https = !empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off';
    if (!$https && (string)($_SERVER['REQUEST_SCHEME'] ?? '') === 'https') { $https = true; }
    $lokal = in_array((string)($_SERVER['SERVER_NAME'] ?? ''), ['localhost', '127.0.0.1', '::1'], true);
    $b[] = plattform_befund('https', 'HTTPS', 'muss',
        $cli ? 'nicht messbar (Kommandozeile)' : ($https ? 'ja' : ($lokal ? 'nein (localhost)' : 'nein')),
        'ja', $cli ? null : ($https || $lokal),
        'Die Sitzungscookies tragen `secure`. Über HTTP sendet der Browser sie '
      . 'nicht, und die Anmeldung scheitert stumm. Ausnahme: localhost — Browser '
      . 'behandeln es als sicheren Kontext, und so läuft der Prüfstand.');

    /* ---- 8 Datenbank -------------------------------------------------------- */
    if ($pdo !== null) {
        $f   = plattform_db_fassung($pdo);
        $min = $f['art'] === 'MariaDB' ? PLATTFORM_MARIADB_MIN : PLATTFORM_MYSQL_MIN;
        $lts = $f['art'] === 'MariaDB' ? PLATTFORM_MARIADB_LTS : PLATTFORM_MYSQL_LTS;
        $dbOk = $f['version'] !== '' ? version_compare($f['version'], $min, '>=') : null;
        $b[] = plattform_befund('db_version', 'Datenbank', 'muss',
            $f['art'] . ' ' . ($f['version'] ?: '?'),
            'MySQL ≥ ' . PLATTFORM_MYSQL_MIN . ' oder MariaDB ≥ ' . PLATTFORM_MARIADB_MIN,
            $dbOk,
            'Das Schema ist InnoDB mit utf8mb4; der Code benutzt weder '
          . 'Fensterfunktionen noch CTEs. Die Untergrenze bestimmt deshalb die '
          . 'Herstellerpflege, nicht ein Merkmal.');

        $b[] = plattform_befund('db_lts', 'Datenbank in Herstellerpflege', 'empfohlen',
            $f['art'] . ' ' . ($f['version'] ?: '?'), $f['art'] . ' ≥ ' . $lts,
            $f['version'] !== '' ? version_compare($f['version'], $lts, '>=') : null,
            'Stand 15.09.2026: MySQL 8.4, MariaDB 10.11 oder 11.4.');

        /* Verbindungsgrenze. `max_user_connections = 0` heisst „es gilt
         * `max_connections`" — dann ist jene die Antwort. */
        $muc = (int)(plattform_db_variable($pdo, 'max_user_connections') ?? 0);
        $mc  = (int)(plattform_db_variable($pdo, 'max_connections') ?? 0);
        $grenze = $muc > 0 ? $muc : $mc;
        $b[] = plattform_befund('db_verbindungen', 'Verbindungsgrenze', 'muss',
            $grenze > 0 ? (string)$grenze . ($muc > 0 ? '' : ' (aus max_connections)')
                        : 'unbekannt',
            '≥ ' . PLATTFORM_VERBINDUNGEN_MIN,
            $grenze > 0 ? $grenze >= PLATTFORM_VERBINDUNGEN_MIN : null,
            'Die Anwendung hält genau EINE Verbindung je Anfrage, keine '
          . 'persistenten; der Huckepack-Job läuft auf der Verbindung der '
          . 'Anfrage, die ihn trägt.', 'max_user_connections');

        $b[] = plattform_befund('db_verbindungen_empf', 'Verbindungsgrenze bequem',
            'empfohlen',
            $grenze > 0 ? (string)$grenze : 'unbekannt',
            '≥ ' . PLATTFORM_VERBINDUNGEN_EMPFOHLEN,
            $grenze > 0 ? $grenze >= PLATTFORM_VERBINDUNGEN_EMPFOHLEN : null,
            'Darunter fängt der 503-Weg die Spitzen ab — verloren geht nichts, '
          . 'aber die Geräte liefern später.');
    }

    /* ---- 9 Kontingent der Datenbank (E-P5a-11) ------------------------------ */
    if ($pdo !== null && function_exists('speicher_db_kontingent_bytes')) {
        $konting = speicher_db_kontingent_bytes();
        $ist     = (int)(function_exists('edbak_marke_lesen')
                         ? (edbak_marke_lesen(SPEICHER_K_DB) ?? 0) : 0);
        $proz    = $konting > 0 && $ist > 0 ? (int)floor($ist * 100 / $konting) : 0;
        $schw    = function_exists('edbak_schwellen') ? edbak_schwellen() : [70, 90];
        $b[] = plattform_befund('db_kontingent', 'Kontingent der Datenbank', 'muss',
            $ist > 0 ? plattform_groesse($ist) . ' von ' . plattform_groesse($konting)
                     . ' · ' . $proz . ' %'
                     : 'noch nicht gemessen',
            '< ' . (int)max($schw) . ' % von ' . plattform_groesse($konting),
            $ist === 0 ? null : $proz < (int)max($schw),
            'Kein Hoster macht das DB-Kontingent abfragbar — es ist deshalb eine '
          . 'ANGABE (Vorgabe 10 GB, Z2) und wird mit denselben Schwellen gewarnt '
          . 'wie der Webspace: ' . implode(', ', $schw) . ' %. Die Größe selbst '
          . 'kommt aus der täglichen Messung im Aufräumjob.',
            'db_gb');
    }

    /* ---- 10 SMTP ------------------------------------------------------------ */
    /* VOR DER EINRICHTUNG GIBT ES KEINE ANTWORT, UND DAS IST KEINE
     * ABWEISUNG. `smtp_eingerichtet()` liest `config.php`; im Einrichter gibt
     * es die noch nicht. Ein Muss-Befund „nicht erfuellt" haette dort die
     * Einrichtung blockiert — wegen einer Angabe, die das Formular darunter
     * gerade erst erfragt. Deshalb `null`: nicht feststellbar. */
    if (!function_exists('smtp_eingerichtet') || !file_exists(__DIR__ . '/config.php')) {
        $b[] = plattform_befund('smtp', 'SMTP', 'muss',
            'noch nicht eingerichtet', 'eingerichtet', null,
            'Der Zugang wird im Formular unten eingetragen (oder später in der '
          . 'config.php). Ohne SMTP gibt es keine Einladungslinks, keine Setz-Links '
          . 'und keine Erinnerung an überfällige Backups.');
        return $b;
    }
    $smtpDa = smtp_eingerichtet();
    if ($mitNetz && $smtpDa && function_exists('smtp_probe')) {
        $p = smtp_probe(5);
        $b[] = plattform_befund('smtp', 'SMTP', 'muss',
            $p['ok'] ? 'erreichbar' : 'nicht erreichbar', 'erreichbar', $p['ok'],
            $p['ok'] ? 'Verbindung und TLS-Handshake stehen — versendet wurde nichts.'
                     : (string)$p['grund']);
    } else {
        $b[] = plattform_befund('smtp', 'SMTP', 'muss',
            $smtpDa ? 'eingerichtet' : 'nicht eingerichtet', 'eingerichtet', $smtpDa,
            $smtpDa
                ? 'Ob der Server auch antwortet, sagt „Testmail an mich" auf dieser '
                . 'Seite — hier wird nichts angewählt, damit ein hängender '
                . 'Mailserver nicht jeden Seitenaufruf hält.'
                : 'Ohne SMTP gibt es keine Einladungslinks, keine Setz-Links und '
                . 'keine Erinnerung an überfällige Backups. Der Zugang gehört in '
                . 'die config.php.');
    }

    /* ---- 11 Vertrauenswuerdige Proxys — reine Auskunft (E-PP-08) ----------- */
    $proxys = [];
    if (isset($GLOBALS['CFG']) && is_array($GLOBALS['CFG'])) {
        $roh = $GLOBALS['CFG']['netz']['vertrauenswuerdige_proxys'] ?? [];
        if (is_array($roh)) { $proxys = $roh; }
    }
    $b[] = plattform_befund('proxys', 'Vertrauenswürdige Proxys', 'empfohlen',
        $proxys === [] ? 'keine eingetragen' : count($proxys) . ' eingetragen',
        'nur nötig hinter einem Reverse Proxy', true,
        $proxys === []
            ? 'Leer heißt: Der Ratenschutz rechnet mit REMOTE_ADDR, wie bisher. '
            . 'Steht die Anwendung hinter einem Proxy, zählen sonst ALLE '
            . 'Nutzerinnen als eine — und werden gemeinsam ausgesperrt.'
            : 'Nur von diesen Adressen wird X-Forwarded-For ausgewertet.',
        'netz.vertrauenswuerdige_proxys');

    return $b;
}

/**
 * Die Befunde, die etwas zu melden haben.
 *
 * @return array{muss_offen: int, empfohlen_offen: int, unbekannt: int}
 */
function plattform_zaehlen(array $befunde): array
{
    $z = ['muss_offen' => 0, 'empfohlen_offen' => 0, 'unbekannt' => 0];
    foreach ($befunde as $f) {
        if ($f['ok'] === null)             { $z['unbekannt']++; continue; }
        if ($f['ok'] === true)             { continue; }
        if ($f['stufe'] === 'muss')        { $z['muss_offen']++; }
        else                               { $z['empfohlen_offen']++; }
    }
    return $z;
}
