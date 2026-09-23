<?php
declare(strict_types=1);

/**
 * Schemaprobe — laeuft `schema.sql` und der Migrationskatalog auf der
 * Datenbank, gegen die sie laufen sollen? (Backlog Nr. 238)
 *
 * WOGEGEN. Am 20.09.2026 scheiterte die Einrichtung auf dem neuen
 * Staging-Webspace mit `SQLSTATE[42000] … 1064 … near 'manual TINYINT(1) NOT
 * NULL DEFAULT 0`. MySQL fuehrt MANUAL von 8.4.0 bis 8.4.10 als reserviertes
 * Wort. Dahinter lag ein zweiter Fehler, den erst das Beheben des ersten
 * sichtbar machte: `DEFAULT UTC_TIMESTAMP()` ohne Klammern, von MySQL auf
 * JEDER Fassung abgewiesen.
 *
 * BEIDE HATTEN DIESELBE URSACHE, und sie ist nicht SQL: Entwickelt und
 * geprueft wird gegen MariaDB, ausgeliefert wird gegen MySQL. MariaDB nimmt
 * beide Schreibweisen an und haelt MANUAL nicht fuer reserviert — die
 * Anwendung liess sich seit Web 20.16.5 auf MySQL UEBERHAUPT NICHT
 * einrichten, und gemerkt hat es niemand, weil niemand es probiert hat.
 *
 * Diese Probe probiert es. Sie beantwortet vier Fragen mit Zahlen:
 *
 *   FALL 1  Laesst sich aus `schema.sql` eine leere Anlage bauen, und bleibt
 *           danach jede Migration stehen (Vorabliste)?
 *   FALL 2  Kommt eine BESTEHENDE Datenbank mit Bestand ueber die
 *           Migrationen — ohne einen einzigen Wert zu verlieren?
 *   FALL 3  Was tut eine Datenbank, deren Migrationsregister fehlt?
 *   FALL 4  Und eine, auf der die Umbenennung schon gelaufen ist?
 *
 * Fall 2 ist der wichtigste: Er legt Bestand an, migriert und vergleicht
 * Zeile fuer Zeile. Eine Migration, die Werte verliert, faellt hier auf.
 *
 * SIE BRAUCHT EINE LEERE DATENBANK UND LEGT SIE MEHRFACH NEU AN. Gegen eine
 * Anlage mit Inhalt darf sie nicht laufen; sie loescht das Schema am Anfang
 * jedes Falls. Deshalb der Pflichtschalter `--datenbank` mit einem Namen,
 * den niemand versehentlich trifft.
 *
 * Aufruf:
 *   php tools/schemaprobe/probe.php --datenbank nadoku_probe \
 *       [--host 127.0.0.1] [--port 3306] [--benutzer probe] \
 *       [--passwort probe] [--klient mariadb]
 *   php tools/schemaprobe/probe.php --selbstprobe
 *
 * `--klient` ist das Kommandozeilenprogramm, mit dem `schema.sql` eingespielt
 * wird (`mariadb` oder `mysql`) — die Datei enthaelt mehrere Anweisungen, und
 * PDO fuehrt sie nicht in einem Rutsch aus.
 *
 * Rueckgabewert: 0 = alle Erwartungen erfuellt, 1 = mindestens eine nicht,
 * 2 = die Probe selbst kam nicht zum Laufen (Verbindung, fehlende Datei).
 */

$WURZEL = dirname(__DIR__, 2) . '/server';

/* ---- Schalter ------------------------------------------------------------ */

/* DIE SCHALTER STEHEN MIT IHREN ZWEI STRICHEN DA, und das ist kein Zufall:
 * `tools/kettenaufrufe/pruefen.py` liest die Schnittstelle eines
 * PHP-Werkzeugs aus seinen `'--…'`-Zeichenketten. Stuende hier `'datenbank'`
 * und das Praefix erst beim Vergleich, saehe die Kettenpruefung KEINEN
 * einzigen Schalter und meldete jeden Aufruf als unbekannt. Beim ersten
 * Versuch tat sie genau das (sechs Befunde, 20.09.2026). */
$opt = [
    '--datenbank' => null,
    '--host'      => '127.0.0.1',
    '--port'      => '3306',
    '--benutzer'  => 'root',
    '--passwort'  => '',
    '--klient'    => 'mariadb',
];
$selbstprobe = false;

for ($i = 1; $i < $argc; $i++) {
    $a = $argv[$i];
    if ($a === '--selbstprobe') { $selbstprobe = true; continue; }
    if ($a === '--hilfe' || $a === '-h') {
        fwrite(STDOUT, "Aufruf: php tools/schemaprobe/probe.php --datenbank NAME"
             . " [--host H] [--port P] [--benutzer B] [--passwort P] [--klient mariadb|mysql]\n"
             . "        php tools/schemaprobe/probe.php --selbstprobe\n");
        exit(0);
    }
    if (!array_key_exists($a, $opt)) {
        fwrite(STDERR, "Unbekannter Schalter: $a\n"
             . "Bekannt: " . implode(' ', array_keys($opt)) . " --selbstprobe --hilfe\n");
        exit(2);
    }
    $wert = $argv[++$i] ?? null;
    if ($wert === null) { fwrite(STDERR, "Zu $a fehlt der Wert.\n"); exit(2); }
    $opt[$a] = $wert;
}

/* ---- Zaehlwerk ----------------------------------------------------------- */

$fehler = 0;
$pruefungen = 0;

function sag(string $s = ''): void { echo $s . "\n"; }

function pruefe(string $was, bool $ok, string $detail = ''): void
{
    global $fehler, $pruefungen;
    $pruefungen++;
    if (!$ok) { $fehler++; }
    sag(sprintf('  [%s] %s%s', $ok ? 'ok  ' : 'FEHL', $was,
                $detail !== '' ? '  — ' . $detail : ''));
}

/* ---- Selbstprobe ---------------------------------------------------------
 *
 * Ein gruener Lauf einer Pruefung, die immer gruen meldet, sieht genauso aus
 * wie einer, der nichts gefunden hat (dieselbe Begruendung wie bei
 * `tools/quelltext/ (migrationsregister)`). Die Selbstprobe braucht keine Datenbank: Sie
 * prueft, dass `pruefe()` einen Fehlschlag auch als solchen zaehlt, und dass
 * `schema.sql` und `migration_lib.php` ueberhaupt dort liegen, wo die Probe
 * sie sucht. */
if ($selbstprobe) {
    sag('Schemaprobe — Selbstprobe (ohne Datenbank)');
    sag();
    $vorher = $fehler;
    pruefe('ein Fehlschlag wird als Fehlschlag gezaehlt', false, 'absichtlich rot');
    $zaehlteMit = ($fehler === $vorher + 1);
    $fehler = $vorher; $pruefungen--;   // den absichtlichen Fehlschlag zuruecknehmen
    pruefe('… und das Zaehlwerk hat ihn bemerkt', $zaehlteMit);
    pruefe('schema.sql liegt, wo die Probe sie sucht', is_file($WURZEL . '/schema.sql'),
           $WURZEL . '/schema.sql');
    pruefe('migration_lib.php ebenso', is_file($WURZEL . '/migration_lib.php'));
    pruefe('der Klient ist aufrufbar',
           (bool)shell_exec('command -v ' . escapeshellarg($opt['--klient'])),
           $opt['--klient']);
    sag();
    sag(sprintf('%d Pruefungen, %d Fehlschlaege.', $pruefungen, $fehler));
    exit($fehler > 0 ? 1 : 0);
}

if ($opt['--datenbank'] === null || $opt['--datenbank'] === '') {
    fwrite(STDERR, "--datenbank fehlt. Die Probe LOESCHT das genannte Schema "
                 . "mehrfach; deshalb muss der Name ausdruecklich dastehen.\n");
    exit(2);
}

$DB = $opt['--datenbank'];

/* ---- Verbindung ---------------------------------------------------------- */

try {
    $roh = new PDO(
        "mysql:host={$opt['--host']};port={$opt['--port']};charset=utf8mb4",
        $opt['--benutzer'], $opt['--passwort'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Keine Verbindung: " . $e->getMessage() . "\n");
    exit(2);
}

$fassung = (string)$roh->query('SELECT VERSION()')->fetchColumn();
sag("Schemaprobe gegen $fassung  (Datenbank: $DB, Klient: {$opt['--klient']})");
sag();

/* ---- Handwerkszeug ------------------------------------------------------- */

function frisch(): PDO
{
    global $roh, $DB, $opt;
    $roh->exec("DROP DATABASE IF EXISTS `$DB`");
    $roh->exec("CREATE DATABASE `$DB` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    return new PDO(
        "mysql:host={$opt['--host']};port={$opt['--port']};dbname=$DB;charset=utf8mb4",
        $opt['--benutzer'], $opt['--passwort'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

/** `schema.sql` einspielen. Ueber den Klienten, weil es viele Anweisungen sind. */
function schema_einspielen(): void
{
    global $opt, $DB, $WURZEL;
    $cmd = sprintf(
        '%s -h%s -P%s -u%s %s %s < %s 2>&1',
        escapeshellcmd($opt['--klient']),
        escapeshellarg($opt['--host']), escapeshellarg($opt['--port']),
        escapeshellarg($opt['--benutzer']),
        $opt['--passwort'] !== '' ? '-p' . escapeshellarg($opt['--passwort']) : '',
        escapeshellarg($DB), escapeshellarg($WURZEL . '/schema.sql')
    );
    exec($cmd, $aus, $rc);
    if ($rc !== 0) {
        throw new RuntimeException("schema.sql liess sich nicht einspielen:\n"
                                 . implode("\n", $aus));
    }
}

/**
 * `schema.sql` einspielen — und beim Scheitern als ERWARTUNG melden, nicht
 * als Ausnahme.
 *
 * DER UNTERSCHIED IST NICHT KOSMETISCH. Genau dieser Fehler ist der Anlass
 * der Probe: Auf dem Altstand vom 20.09.2026 antwortet MySQL 8.4.0 hier mit
 * `1064 … near 'manual TINYINT(1) …'`. Eine ungefangene Ausnahme meldet das
 * mit Rueckgabe 255 und einem Stapelabzug — in der Kette rot, aber unlesbar,
 * und nicht die dokumentierte 1. Gemessen beim Gegenlauf: genau so.
 *
 * Laesst sich das Schema nicht anlegen, hat keiner der folgenden Faelle noch
 * eine Aussage. Die Probe bricht deshalb hier ab, mit Zahl und Meldung.
 */
function schema_oder_raus(string $fall): void
{
    global $pruefungen, $fehler;
    try {
        schema_einspielen();
    } catch (Throwable $e) {
        /* DIE ERSTE ZEILE MIT EINEM FEHLERCODE, nicht einfach die zweite:
         * Der Klient schreibt vor die Meldung Warnungen und Trennstriche,
         * und beim ersten Versuch stand als "Detail" ein `--------------`. */
        $zeilen = preg_split('/\R/', $e->getMessage()) ?: [];
        $meldung = '';
        foreach ($zeilen as $z) {
            if (preg_match('/\bERROR\s+\d+/i', $z)) { $meldung = trim($z); break; }
        }
        if ($meldung === '') { $meldung = trim($zeilen[0] ?? $e->getMessage()); }
        pruefe("schema.sql laesst sich einspielen ($fall)", false, $meldung);
        sag();
        sag(sprintf('%d Pruefungen, %d Fehlschlaege.  ABGEBROCHEN: Ohne Schema '
                  . 'hat kein weiterer Fall eine Aussage.', $pruefungen, $fehler));
        exit(1);
    }
}

/** Die Spaltennamen von `missions`, klein geschrieben. */
function missions_spalten(PDO $pdo): array
{
    $sp = $pdo->query("SELECT column_name FROM information_schema.columns
                        WHERE table_schema = DATABASE() AND table_name = 'missions'")
              ->fetchAll(PDO::FETCH_COLUMN);
    return array_map('strtolower', $sp);
}

/* MySQL liefert die Spaltennamen von `information_schema` in GROSS-, MariaDB
 * in Kleinbuchstaben. Ohne diese Normalisierung meldet dieselbe, richtige
 * Datenbank auf der einen Fassung einen Fehlschlag und auf der anderen nicht
 * — genau die Sorte Unterschied, wegen der es diese Probe gibt. */
function spaltendefinition(PDO $pdo, string $tabelle, string $spalte): array
{
    $st = $pdo->prepare("SELECT column_type, is_nullable, column_default
                           FROM information_schema.columns
                          WHERE table_schema = DATABASE()
                            AND table_name = ? AND column_name = ?");
    $st->execute([$tabelle, $spalte]);
    return array_change_key_case($st->fetch(PDO::FETCH_ASSOC) ?: [], CASE_LOWER);
}

/* ---- Wegwerf-config.php --------------------------------------------------
 *
 * `migration_lib.php` laedt `db.php`, und das laedt `config.php`. Statt den
 * Katalog nachzubauen, legt die Probe eine hin und raeumt sie wieder weg —
 * auch wenn sie unterwegs abbricht. Eine vorhandene config.php wird zur
 * Seite gelegt und zurueckgeholt; ein Entwicklungsrechner soll seine
 * Einstellungen behalten. */
$cfgPfad  = $WURZEL . '/config.php';
$beiseite = $cfgPfad . '.schemaprobe';
$hatteCfg = is_file($cfgPfad);
if ($hatteCfg) { copy($cfgPfad, $beiseite); }

file_put_contents($cfgPfad, "<?php\n// Wegwerfdatei der Schemaprobe. Wird am Ende entfernt.\nreturn "
    . var_export([
        'db'  => ['dsn'  => "mysql:host={$opt['--host']};port={$opt['--port']};dbname=$DB;charset=utf8mb4",
                  'user' => $opt['--benutzer'], 'pass' => $opt['--passwort']],
        'app' => ['base_url' => 'https://127.0.0.1', 'timezone' => 'Europe/Berlin',
                  'logo_path' => 'assets/images/gen-em_logo_helicopter.svg',
                  'max_body_bytes' => 524288],
        'smtp' => ['host' => '127.0.0.1', 'port' => 2525, 'user' => 'x', 'pass' => 'x',
                   'from' => 'x@example.invalid', 'secure' => 'none'],
        'server_key' => str_repeat('ab', 32),
        'kdf_anteil' => str_repeat('cd', 32),
    ], true) . ";\n");

register_shutdown_function(function () use ($cfgPfad, $beiseite, $hatteCfg) {
    if ($hatteCfg) { rename($beiseite, $cfgPfad); } else { @unlink($cfgPfad); }
});

require_once $WURZEL . '/migration_lib.php';

$katalog = migrationen_katalog();

/* ---- Fall 1: frische Installation ---------------------------------------- */

sag('FALL 1 — frische Installation ueber schema.sql');
$pdo = frisch();
schema_oder_raus('Fall 1');

$tabellen = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables
                               WHERE table_schema = DATABASE()")->fetchColumn();
pruefe('schema.sql laeuft durch', $tabellen > 0, "$tabellen Tabellen");

$sp = missions_spalten($pdo);
pruefe('missions traegt uhr_gesperrt', in_array('uhr_gesperrt', $sp, true));
pruefe('und NICHT manual',             !in_array('manual', $sp, true),
       'MANUAL ist in MySQL 8.4.0-8.4.10 reserviert');

$vorab = (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
pruefe('die Vorabliste traegt jede Kennung', $vorab === count($katalog),
       "$vorab von " . count($katalog));

migrationen_lauf($pdo, true);
pruefe('nach dem Migrationslauf weiterhin kein manual',
       !in_array('manual', missions_spalten($pdo), true));
sag();

/* ---- Fall 2: bestehende Datenbank mit Bestand ----------------------------- */

sag('FALL 2 — bestehende Datenbank mit Bestand (der Produktivfall)');
$pdo = frisch();
schema_oder_raus('Fall 2');

/* DIE KULISSE SETZT DEN NEUEN NAMEN VORAUS -- und sagt es, statt daran zu
 * zerbrechen. Faehrt jemand die Probe gegen einen Stand VOR Web 20.25.0
 * (etwa als Gegenprobe aus einem Arbeitsbaum), traegt `missions` noch
 * `manual`; die Zeile darunter antwortete dann mit `1054` und einem
 * Stapelabzug. Gemessen am 20.09.2026 beim Gegenlauf gegen MariaDB 10.6. */
if (!in_array('uhr_gesperrt', missions_spalten($pdo), true)) {
    pruefe('das Schema kennt uhr_gesperrt (Voraussetzung der Kulisse)', false,
           'dieser Stand fuehrt die Spalte noch als `manual` -- Fall 2 bis 4 '
         . 'haben hier keine Aussage');
    sag();
    sag(sprintf('%d Pruefungen, %d Fehlschlaege.  ABGEBROCHEN.', $pruefungen, $fehler));
    exit(1);
}

/* Auf den Stand VOR der Umbenennung zurueckdrehen: Spalte heisst wieder
 * `manual`, die Kennung fehlt im Register. Das ist der Zustand jeder
 * Installation, die vor Web 20.25.0 eingerichtet wurde. */
$pdo->exec('ALTER TABLE missions CHANGE uhr_gesperrt `manual` TINYINT(1) NOT NULL DEFAULT 0');
$pdo->exec("DELETE FROM schema_migrations WHERE id = '2026_09_20_uhr_gesperrt'");

$pdo->exec("INSERT INTO users (id, email, password_hash, role)
            VALUES (1, 'probe@example.invalid', 'x', 'admin')");
for ($i = 1; $i <= 7; $i++) {
    $pdo->prepare("INSERT INTO missions (id, user_id, client_ref, started_at, `manual`, origin, final)
                   VALUES (?, 1, ?, '2026-09-01 08:00:00', ?, 'watch', 1)")
        ->execute([$i, 'probe-' . $i, $i <= 4 ? 1 : 0]);
}
$vorher = $pdo->query('SELECT id, `manual` FROM missions ORDER BY id')
              ->fetchAll(PDO::FETCH_KEY_PAIR);
pruefe('Ausgangsbestand steht', count($vorher) === 7 && array_sum($vorher) === 4,
       '7 Einsaetze, davon ' . array_sum($vorher) . ' gesperrt');
pruefe('die Umbenennung steht aus', migrationen_ausstehend($pdo));

migrationen_lauf($pdo, true);

$sp = missions_spalten($pdo);
pruefe('die Spalte heisst jetzt uhr_gesperrt', in_array('uhr_gesperrt', $sp, true));
pruefe('manual ist weg',                       !in_array('manual', $sp, true));

$nachher = $pdo->query('SELECT id, uhr_gesperrt FROM missions ORDER BY id')
               ->fetchAll(PDO::FETCH_KEY_PAIR);
pruefe('KEIN Datenverlust — Wert fuer Wert gleich', $vorher === $nachher,
       json_encode($nachher));

$def = spaltendefinition($pdo, 'missions', 'uhr_gesperrt');
pruefe('die Definition ist unveraendert',
       ($def['column_type'] ?? '') === 'tinyint(1)'
       && ($def['is_nullable'] ?? '') === 'NO'
       && (string)($def['column_default'] ?? '') === '0',
       ($def['column_type'] ?? '?') . ' / null=' . ($def['is_nullable'] ?? '?')
       . ' / default=' . ($def['column_default'] ?? '?'));

/* Der zweite Lauf darf nichts tun und nichts werfen. Ohne die zweite
 * Bedingung der Skip-Pruefung braeche hier `1054 Unknown column` den GANZEN
 * Lauf — 1054 steht nicht in der Schluckliste von `migrationen_lauf()`. */
$zweiterLauf = true;
try { migrationen_lauf($pdo, true); } catch (Throwable $e) { $zweiterLauf = false; }
pruefe('der zweite Lauf ist folgenlos', $zweiterLauf,
       'sonst braeche 1054 die ganze Kette');
sag();

/* ---- Fall 3: Datenbank ohne Registereintrag ------------------------------- */

sag('FALL 3 — Datenbank ohne Registereintrag (Teilwiederherstellung, fremder Dump)');
$pdo = frisch();
schema_oder_raus('Fall 3');
$pdo->exec('ALTER TABLE missions CHANGE uhr_gesperrt `manual` TINYINT(1) NOT NULL DEFAULT 0');
$pdo->exec('DELETE FROM schema_migrations');

$alt = null; $neu = null;
foreach ($katalog as $m) {
    if ($m['id'] === '2026_07_18_manuelle_einsaetze') { $alt = $m; }
    if ($m['id'] === '2026_09_20_uhr_gesperrt')       { $neu = $m; }
}
pruefe('beide Migrationen stehen im Katalog', $alt !== null && $neu !== null);

pruefe('2026_07_18 wird uebersprungen — sie findet manual', ($alt['skip'])($pdo) === true,
       'ohne die Oder-Bedingung legte sie uhr_gesperrt LEER daneben an');
pruefe('die Umbenennung steht an — manual da, uhr_gesperrt nicht',
       ($neu['skip'])($pdo) === false);
$sp = missions_spalten($pdo);
pruefe('genau eine der beiden Spalten ist da',
       in_array('manual', $sp, true) && !in_array('uhr_gesperrt', $sp, true));
sag();

/* ---- Fall 4: bereits umbenannt, Register leer ----------------------------- */

sag('FALL 4 — bereits umbenannte Datenbank, Register leer');
$pdo = frisch();
schema_oder_raus('Fall 4');
$pdo->exec('DELETE FROM schema_migrations');

pruefe('2026_07_18 wird uebersprungen — sie findet uhr_gesperrt',
       ($alt['skip'])($pdo) === true,
       'ohne die Oder-Bedingung legte sie manual LEER daneben an, und 1060 wird geschluckt');
pruefe('die Umbenennung wird uebersprungen — uhr_gesperrt ist da',
       ($neu['skip'])($pdo) === true);

$pdo->exec('ALTER TABLE missions DROP COLUMN uhr_gesperrt');
pruefe('die Umbenennung wird uebersprungen — BEIDE Spalten fehlen',
       ($neu['skip'])($pdo) === true,
       'ohne die zweite Bedingung braeche 1054 den ganzen Lauf');
sag();

/* ---- Schluss -------------------------------------------------------------- */

$roh->exec("DROP DATABASE IF EXISTS `$DB`");

sag(sprintf('%d Pruefungen, %d Fehlschlaege.  (%s)', $pruefungen, $fehler, $fassung));
exit($fehler > 0 ? 1 : 0);
