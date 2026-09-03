<?php
declare(strict_types=1);

/**
 * KOMPLETTPROBE — der volle Zyklus des Komplett-Backups (S2/AP8).
 *
 *   php tools/komplettprobe/probe.php --pruefdb=edoku_probe [--nutzer=root] \
 *       [--passwort=…] [--ziel=/tmp/versandprobe]
 *
 * Sie fährt, was die Abnahme von AP8 verlangt: Komplett-Backup erzeugen ->
 * versiegeln -> öffnen -> in eine LEERE Datenbank einspielen -> Tabelle für
 * Tabelle vergleichen -> auf ein Backup-Ziel schieben.
 *
 * SIE ARBEITET IN EINER KOPIE, NICHT IN DER INSTALLATION. `edbak_wurzel()`
 * zeigt fest auf `server/sicherungen`; eine Probe, die dort einen Stand
 * ablegt, verdrängt unter Umständen einen echten. Deshalb entsteht unter
 * `/tmp` eine Kopie des Serververzeichnisses mit eigener `config.php` und
 * eigenem Serverschlüssel. Gelesen wird aus der ECHTEN Datenbank — der Dump
 * liest nur, und ein Dump gegen einen Spielbestand prüfte nichts.
 *
 * WAS SIE NICHT PRÜFT
 *   - Die Oberfläche. `admin_komplettsicherung.php` und
 *     `wiederherstellen.php` sind im Browser zu prüfen; hier laufen die
 *     Bibliotheken darunter.
 *   - Eine volle Platte. Geprüft ist die Speichergrenze als Rechnung, nicht
 *     als erlebter Zustand.
 *   - Einen Abbruch mitten in der Anfrage. Nachgestellt ist er (der Zustand
 *     wird zurückgedreht), erlebt nicht.
 *   - Den Migrationslauf nach der Wiederherstellung. Er gehört einer
 *     angemeldeten Administration und läuft nicht in einer Probe.
 */

$args = [];
foreach (array_slice($argv, 1) as $a) {
    if (preg_match('/^--([a-z]+)(?:=(.*))?$/', $a, $t)) { $args[$t[1]] = $t[2] ?? '1'; }
}
$pruefdb  = (string)($args['pruefdb'] ?? '');
$zielWurzel = (string)($args['ziel'] ?? '');

$echt = __DIR__ . '/../../server';
if (!is_file($echt . '/config.php')) {
    fwrite(STDERR, "Ohne server/config.php geht es nicht — die Probe liest aus der echten Datenbank.\n");
    exit(2);
}
$CFG_ECHT = (array)(require $echt . '/config.php');

/* ---- Die Kopie ----------------------------------------------------------- */
$tmp = sys_get_temp_dir() . '/komplettprobe-' . bin2hex(random_bytes(4));
$srv = $tmp . '/server';
mkdir($srv, 0770, true);
foreach (glob($echt . '/*.php') ?: [] as $f) { copy($f, $srv . '/' . basename($f)); }
copy($echt . '/schema.sql', $srv . '/schema.sql');
if (is_dir($echt . '/vendor')) {
    $kopiere = static function (string $von, string $nach) use (&$kopiere): void {
        @mkdir($nach, 0770, true);
        foreach (scandir($von) ?: [] as $n) {
            if ($n === '.' || $n === '..') { continue; }
            is_dir($von . '/' . $n) ? $kopiere($von . '/' . $n, $nach . '/' . $n)
                                    : copy($von . '/' . $n, $nach . '/' . $n);
        }
    };
    $kopiere($echt . '/vendor', $srv . '/vendor');
}
$CFG = $CFG_ECHT;
$CFG['server_key'] = bin2hex(random_bytes(32));
file_put_contents($srv . '/config.php', "<?php\nreturn " . var_export($CFG, true) . ";\n");

register_shutdown_function(static function () use ($tmp): void {
    $weg = static function (string $p) use (&$weg): void {
        if (!is_dir($p)) { @unlink($p); return; }
        foreach (scandir($p) ?: [] as $n) {
            if ($n === '.' || $n === '..') { continue; }
            $weg($p . '/' . $n);
        }
        @rmdir($p);
    };
    $weg($tmp);
});

require_once $srv . '/komplett_lib.php';

/* ---- Zählwerk ------------------------------------------------------------ */
$n = 0; $offen = 0;
function kopf(string $t): void { echo "\n$t\n"; }
function pruef(string $was, bool $ok, string $dazu = ''): void {
    global $n, $offen;
    $n++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-62s %s\n", $ok ? 'ok ' : 'OFFEN', $was, $dazu);
}
function offenlassen(string $was, string $warum): void {
    printf("  [ -- ] %-62s %s\n", $was, $warum);
}

echo "Komplettprobe — Arbeitskopie unter " . $tmp . "\n";
echo "Datenbank (gelesen): " . ($CFG['db']['dsn'] ?? '?') . "\n";

/* =========================================================================
 * Teil 1 — Tabellen, Reihenfolge, Cursor
 * ====================================================================== */
kopf('Teil 1 — Tabellen, Reihenfolge und Cursor');
$pdo = db();
$tabellen = komp_tabellen($pdo);
pruef('Es werden Tabellen gefunden', count($tabellen) > 0, count($tabellen) . ' Tabellen');

$ohnePk = array_keys(array_filter($tabellen, fn(array $t): bool => $t['pk'] === []));
pruef('Jede Tabelle hat einen Primärschlüssel', $ohnePk === [],
      $ohnePk === [] ? 'sonst liefe der Cursor über den Versatz' : implode(', ', $ohnePk));

$r = komp_reihenfolge($pdo, $tabellen);
pruef('Die Reihenfolge enthält jede Tabelle genau einmal',
      count($r['reihenfolge']) === count($tabellen)
      && count(array_unique($r['reihenfolge'])) === count($tabellen),
      count($r['reihenfolge']) . ' Einträge');
pruef('Es gibt keinen Fremdschlüssel-Ring', $r['ring'] === [],
      $r['ring'] === [] ? 'topologisch sortierbar' : implode(', ', $r['ring']));

/* Steht jede Tabelle hinter denen, auf die sie verweist? */
$platz = array_flip($r['reihenfolge']);
$verletzt = [];
$q = $pdo->query('SELECT DISTINCT table_name, referenced_table_name
                    FROM information_schema.key_column_usage
                   WHERE table_schema = DATABASE() AND referenced_table_name IS NOT NULL');
foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $f) {
    $von = (string)$f['table_name']; $auf = (string)$f['referenced_table_name'];
    if ($von === $auf || !isset($platz[$von], $platz[$auf])) { continue; }
    if ($platz[$auf] > $platz[$von]) { $verletzt[] = $von . ' vor ' . $auf; }
}
pruef('Verwiesene Tabellen stehen VOR den verweisenden', $verletzt === [],
      $verletzt === [] ? 'einspielbare Reihenfolge' : implode(', ', $verletzt));

$mitEnum = array_keys(array_filter($tabellen, fn(array $t): bool => $t['fest'] !== []));
pruef('Führende ENUM-Spalten werden festgenagelt', true,
      $mitEnum === [] ? 'keine Tabelle hat eine' : implode(', ', $mitEnum));

/* =========================================================================
 * Teil 2 — SQL-Literale
 * ====================================================================== */
kopf('Teil 2 — SQL-Literale');
pruef('Ein Zeilenumbruch wird zu \\n', komp_quote("a\nb") === "'a\\nb'", komp_quote("a\nb"));
pruef('Ein Wagenrücklauf wird zu \\r', komp_quote("a\rb") === "'a\\rb'", komp_quote("a\rb"));
pruef('Ein Anführungszeichen wird geschützt', komp_quote("O'Neill") === "'O\\'Neill'", komp_quote("O'Neill"));
pruef('Ein Rückstrich wird verdoppelt', komp_quote('a\\b') === "'a\\\\b'", komp_quote('a\\b'));
pruef('Ein Nullbyte wird zu \\0', komp_quote("a\0b") === "'a\\0b'");
pruef('Kein Literal enthält je einen echten Umbruch',
      !str_contains(komp_quote("a\nb\r\nc"), "\n"));
pruef('NULL bleibt NULL', komp_wert(null, false, false) === 'NULL');
pruef('Eine Zahl steht ohne Anführungszeichen', komp_wert('42', false, true) === '42');
pruef('Eine Kommazahl ebenso', komp_wert('-1.5e3', false, true) === '-1.5e3');
pruef('Etwas Nichtnumerisches in einer Zahlenspalte wird trotzdem geschützt',
      komp_wert('12x', false, true) === "'12x'", komp_wert('12x', false, true));
pruef('Binärdaten werden hexadezimal', komp_wert("\x00\xff", true, false) === '0x00ff',
      komp_wert("\x00\xff", true, false));
pruef('Leere Binärdaten werden zur leeren Zeichenkette',
      komp_wert('', true, false) === "''", 'nicht 0x — das wäre ungültig');

/* =========================================================================
 * Teil 3 — Der Dump in Häppchen
 * ====================================================================== */
kopf('Teil 3 — Der Dump entsteht in Häppchen');
$z = ['stand' => 'dump', 'bau' => KOMP_BAU_PRAEFIX . bin2hex(random_bytes(4)),
      'name' => komp_dateiname(), 'begonnen' => gmdate('Y-m-d\TH:i:s\Z'), 'roh_bytes' => 0];
[$bOk, $bMeldung] = komp_bereit();
pruef('Die Ablage lässt sich anlegen', $bOk, (string)$bMeldung);

$bauOrdner = (string)$z['bau'];
$budget = 0.6;    // klein: erzwingt viele Häppchen
$runden = 0; $spitze = 0; $anfangZeit = microtime(true);
while (true) {
    $runden++;
    $start = microtime(true);
    $e = komp_schub($pdo, $z, static fn(): float => $budget + 5.0 - (microtime(true) - $start), 5.0);
    $spitze = max($spitze, memory_get_peak_usage(true));
    if ($e['fertig'] && ($z['stand'] ?? '') === 'fertig') { break; }
    if ($runden > 500) { break; }
}
$dauer = microtime(true) - $anfangZeit;
pruef('Der Lauf braucht mehr als ein Häppchen', $runden > 1, $runden . ' Häppchen');
pruef('Der Lauf wird fertig', ($z['stand'] ?? '') === 'fertig', (string)($z['stand'] ?? '-'));
pruef('Die Speicherspitze bleibt unter dem Z3-Budget von 64 MB', $spitze <= 64 * 1048576,
      sprintf('%.1f MB in %.2f s', $spitze / 1048576, $dauer));
$datei = (string)($z['name'] ?? '');
$pfad  = komp_wurzel() . '/' . $datei;
pruef('Die versiegelte Datei liegt da', is_file($pfad),
      $datei . ' — ' . number_format((int)@filesize($pfad) / 1048576, 1, ',', '.') . ' MB');
pruef('Der Bauordner ist weg', !is_dir(komp_wurzel() . '/' . $bauOrdner), $bauOrdner);

/* =========================================================================
 * Teil 4 — Das Siegel EDKOMP1
 * ====================================================================== */
kopf('Teil 4 — Das Siegel EDKOMP1');
$k = komp_kopf_lesen($pfad);
pruef('Der Kopf ist lesbar', $k !== null);
/* `array_key_exists` und nicht `??`: Der Nullwehrt-Operator behandelt einen
 * vorhandenen `null` wie einen fehlenden Schlüssel — die erste Fassung dieser
 * Erwartung war deshalb NIE erfüllbar. */
pruef('Der Kopf nennt den Serverschlüssel (kdf: null)',
      array_key_exists('kdf', (array)$k['kopf']) && $k['kopf']['kdf'] === null);
pruef('Der Kopf nennt Zeilen und Tabellen',
      (int)($k['kopf']['zeilen'] ?? 0) === (int)$z['zeilen']
      && (int)($k['kopf']['tabellen'] ?? 0) === (int)$z['tabellen'],
      number_format((int)($k['kopf']['zeilen'] ?? 0), 0, ',', '.') . ' Zeilen aus '
      . (int)($k['kopf']['tabellen'] ?? 0) . ' Tabellen');
pruef('Der Kopf nennt den Migrationsstand', (string)($k['kopf']['migration'] ?? '') !== '',
      (string)($k['kopf']['migration'] ?? ''));

$sql = $tmp . '/dump.sql.gz';
$fh = fopen($sql, 'wb');
$bloecke = komp_oeffnen($pfad, (string)serverschluessel(),
                        static function (string $s) use ($fh): void { fwrite($fh, $s); });
fclose($fh);
pruef('Die Datei lässt sich mit dem Serverschlüssel öffnen', $bloecke > 0, $bloecke . ' Blöcke');

$fehler = static function (callable $fn): string {
    try { $fn(); return ''; } catch (Throwable $e) { return $e->getMessage(); }
};
pruef('Ein fremder Schlüssel öffnet sie nicht',
      $fehler(fn() => komp_oeffnen($pfad, random_bytes(32), fn() => null)) !== '');
$kurz = $tmp . '/kurz.edk';
copy($pfad, $kurz);
$g = fopen($kurz, 'r+b'); ftruncate($g, (int)filesize($kurz) - 100); fclose($g);
pruef('Eine hinten abgeschnittene Datei wird abgewiesen',
      $fehler(fn() => komp_oeffnen($kurz, (string)serverschluessel(), fn() => null)) !== '');

/* AN EINER BLOCKGRENZE abgeschnitten — formal heil, inhaltlich unvollständig.
 * Genau dafür steht die Endemarkierung in den Zusatzdaten. */
$grenzen = [$k['ab']]; $pos = $k['ab'];
$fh = fopen($pfad, 'rb'); fseek($fh, $k['ab']);
while (!feof($fh)) {
    $l = fread($fh, 4); if ($l === false || strlen($l) < 4) { break; }
    $ln = (int)unpack('N', $l)[1];
    fseek($fh, 12 + 16 + $ln, SEEK_CUR);
    $pos += 4 + 12 + 16 + $ln; $grenzen[] = $pos;
}
fclose($fh);
$grenz = $tmp . '/grenze.edk';
copy($pfad, $grenz);
$g = fopen($grenz, 'r+b'); ftruncate($g, $grenzen[max(0, count($grenzen) - 3)]); fclose($g);
pruef('An einer Blockgrenze abgeschnitten wird ebenfalls abgewiesen',
      $fehler(fn() => komp_oeffnen($grenz, (string)serverschluessel(), fn() => null)) !== '',
      'die Endemarkierung in den Zusatzdaten greift');

$roh = (string)file_get_contents($pfad);
$verdreht = $tmp . '/kopfweg.edk';
file_put_contents($verdreht, (string)preg_replace('/"web":"[^"]*"/', '"web":"0.0.0"', $roh, 1));
pruef('Ein veränderter Dateikopf macht die Datei unlesbar',
      $fehler(fn() => komp_oeffnen($verdreht, (string)serverschluessel(), fn() => null)) !== '',
      'der Kopf hängt über die Zusatzdaten an jedem Block');

/* =========================================================================
 * Teil 5 — Die Passphrase-Fassung
 * ====================================================================== */
kopf('Teil 5 — Die Passphrase-Fassung (Direktdownload)');
$pw = 'komplettprobe-2026';
$mitPw = $tmp . '/mitpw.edk';
$fh = fopen($mitPw, 'wb');
komp_ausgeben_passphrase($datei, $pw, static function (string $s) use ($fh): void { fwrite($fh, $s); });
fclose($fh);
$kp = komp_kopf_lesen($mitPw);
pruef('Der Kopf nennt PBKDF2', ($kp['kopf']['kdf']['art'] ?? '') === 'pbkdf2',
      'Runden ' . number_format((int)($kp['kopf']['kdf']['iter'] ?? 0), 0, ',', '.'));
pruef('Die Rundenzahl ist dieselbe wie im Browser',
      (int)($kp['kopf']['kdf']['iter'] ?? 0) === komp_kdf_runden(),
      'KDF_ITER_ZIEL');
$ausPw = $tmp . '/auspw.sql.gz';
$fh = fopen($ausPw, 'wb');
komp_oeffnen($mitPw, komp_schluessel_fuer((array)$kp['kopf'], $pw),
             static function (string $s) use ($fh): void { fwrite($fh, $s); });
fclose($fh);
pruef('Mit der Passphrase kommt derselbe Inhalt heraus',
      hash_file('sha256', $ausPw) === hash_file('sha256', $sql));
pruef('Eine falsche Passphrase öffnet sie nicht',
      $fehler(fn() => komp_oeffnen($mitPw, komp_schluessel_fuer((array)$kp['kopf'], 'falsch1234'), fn() => null)) !== '');
pruef('Der Serverschlüssel öffnet sie ebenfalls nicht',
      $fehler(fn() => komp_oeffnen($mitPw, (string)serverschluessel(), fn() => null)) !== '');
pruef('Eine zu kurze Passphrase wird abgewiesen',
      $fehler(fn() => komp_ausgeben_passphrase($datei, 'kurz', fn() => null)) !== '');

/* =========================================================================
 * Teil 6 — Die Form des Dumps (E-S2-20)
 * ====================================================================== */
kopf('Teil 6 — Die Form des Dumps');
$klar = $tmp . '/dump.sql';
$gz = gzopen($sql, 'rb'); $fh = fopen($klar, 'wb');
while (!gzeof($gz)) { $s = gzread($gz, 262144); if ($s === false || $s === '') { break; } fwrite($fh, $s); }
gzclose($gz); fclose($fh);
pruef('Der Klartext ist da', filesize($klar) > 0,
      number_format(filesize($klar) / 1048576, 1, ',', '.') . ' MB');

$zeilen = 0; $laengste = 0; $ueber = 0; $endmarke = false; $kopfzeilen = 0;
$fh = fopen($klar, 'rb');
while (($zeile = fgets($fh)) !== false) {
    $zeilen++;
    $l = strlen(rtrim($zeile, "\n"));
    $laengste = max($laengste, $l);
    if ($l > KOMP_STAPEL_BYTES + 4096) { $ueber++; }
    if (str_starts_with($zeile, KOMP_ENDMARKE)) { $endmarke = true; }
    if ($zeilen <= 20 && str_starts_with($zeile, '--')) { $kopfzeilen++; }
}
fclose($fh);
pruef('Die Datei trägt die Endmarke', $endmarke, KOMP_ENDMARKE);
pruef('Der Kopf nennt Version, Migrationsstand und Zeitpunkt', $kopfzeilen >= 6,
      $kopfzeilen . ' Kommentarzeilen im Kopf');
pruef('Keine Zeile sprengt den 1-MB-Stapel', $ueber === 0,
      'längste Zeile ' . number_format($laengste, 0, ',', '.') . ' Byte');
pruef('Der Dump hat Zeilen', $zeilen > 0, number_format($zeilen, 0, ',', '.') . ' Zeilen');

$inhalt = (string)file_get_contents($klar);
foreach (['SET FOREIGN_KEY_CHECKS = 0;', 'SET NAMES utf8mb4;',
          "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';"] as $muss) {
    pruef('Die Kopfzeile „' . $muss . '" steht drin', str_contains($inhalt, $muss));
}
$fehlt = [];
foreach (array_keys($tabellen) as $t) {
    if (!str_contains($inhalt, 'CREATE TABLE `' . $t . '`')) { $fehlt[] = $t; }
}
pruef('Jede Tabelle hat ein CREATE TABLE', $fehlt === [],
      $fehlt === [] ? count($tabellen) . ' Tabellen' : implode(', ', $fehlt));
unset($inhalt);

/* =========================================================================
 * Teil 7 — Einspielen in eine leere Datenbank und vergleichen
 * ====================================================================== */
kopf('Teil 7 — Einspielen in eine leere Datenbank');
$pruefPdo = null;
if ($pruefdb === '') {
    offenlassen('Rückspielung', 'ohne --pruefdb=<name> wird nichts eingespielt');
} else {
    /* Ohne `--nutzer` gilt der Zugang aus `config.php`. Er darf `CREATE
     * DATABASE` in aller Regel NICHT — die Probe sagt das dann und schweigt
     * nicht. */
    $nutzer = isset($args['nutzer'])
        ? (string)$args['nutzer'] : (string)($CFG['db']['user'] ?? 'root');
    $passwort = isset($args['passwort'])
        ? (string)$args['passwort']
        : (isset($args['nutzer']) ? '' : (string)($CFG['db']['pass'] ?? ''));
    $wirt = 'localhost';
    if (preg_match('/host=([^;]+)/', (string)($CFG['db']['dsn'] ?? ''), $t)) { $wirt = $t[1]; }
    try {
        $adm = new PDO("mysql:host={$wirt};charset=utf8mb4", $nutzer, $passwort,
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $q = '`' . str_replace('`', '``', $pruefdb) . '`';
        $adm->exec("DROP DATABASE IF EXISTS $q");
        $adm->exec("CREATE DATABASE $q CHARACTER SET utf8mb4");
        $pruefPdo = new PDO("mysql:host={$wirt};dbname={$pruefdb};charset=utf8mb4",
                            $nutzer, $passwort, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    } catch (Throwable $ex) {
        offenlassen('Rückspielung', 'Prüfdatenbank nicht erreichbar: ' . $ex->getMessage());
    }
}

if ($pruefPdo !== null) {
    $pruefPdo->exec('SET NAMES utf8mb4');
    $pruefPdo->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $pruefPdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pruefPdo->exec('SET UNIQUE_CHECKS = 0');
    $fh = fopen($klar, 'rb');
    $anweisungen = 0; $wo = null; $t0 = microtime(true);
    try {
        while (($zeile = fgets($fh)) !== false) {
            $s = trim($zeile);
            if ($s === '' || str_starts_with($s, '--')) { continue; }
            $pruefPdo->exec(rtrim($s, ';'));
            $anweisungen++;
        }
    } catch (Throwable $ex) {
        $wo = 'Anweisung ' . ($anweisungen + 1) . ': ' . $ex->getMessage();
    }
    fclose($fh);
    pruef('Der Dump lässt sich Zeile für Zeile einspielen', $wo === null,
          $wo ?? sprintf('%s Anweisungen in %.2f s',
                         number_format($anweisungen, 0, ',', '.'), microtime(true) - $t0));

    /* Tabelle für Tabelle: Schema und Inhalt. */
    $a = array_column($pdo->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")
                          ->fetchAll(PDO::FETCH_NUM), 0);
    $b = array_column($pruefPdo->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")
                               ->fetchAll(PDO::FETCH_NUM), 0);
    sort($a); sort($b);
    pruef('Die Prüfdatenbank hat dieselben Tabellen', $a === $b,
          count($a) . ' gegen ' . count($b));

    $schemaGleich = 0; $summeGleich = 0; $zeilenGleich = 0; $abweichung = [];
    $gesamtZeilen = 0;
    foreach (array_intersect($a, $b) as $t) {
        $q = '`' . str_replace('`', '``', $t) . '`';
        $nA = (int)$pdo->query("SELECT COUNT(*) FROM $q")->fetchColumn();
        $nB = (int)$pruefPdo->query("SELECT COUNT(*) FROM $q")->fetchColumn();
        $gesamtZeilen += $nA;
        if ($nA === $nB) { $zeilenGleich++; }
        $cA = (string)($pdo->query("SHOW CREATE TABLE $q")->fetch(PDO::FETCH_NUM)[1] ?? '');
        $cB = (string)($pruefPdo->query("SHOW CREATE TABLE $q")->fetch(PDO::FETCH_NUM)[1] ?? '');
        if ($cA === $cB) { $schemaGleich++; }
        $sA = (string)($pdo->query("CHECKSUM TABLE $q EXTENDED")->fetch(PDO::FETCH_NUM)[1] ?? 'a');
        $sB = (string)($pruefPdo->query("CHECKSUM TABLE $q EXTENDED")->fetch(PDO::FETCH_NUM)[1] ?? 'b');
        if ($sA === $sB) { $summeGleich++; } else { $abweichung[] = $t; }
    }
    $alle = count(array_intersect($a, $b));
    pruef('Jede Tabelle hat dieselbe Zeilenzahl', $zeilenGleich === $alle,
          $zeilenGleich . ' von ' . $alle . ', zusammen '
          . number_format($gesamtZeilen, 0, ',', '.') . ' Zeilen');
    pruef('Jedes Schema ist zeichengleich (SHOW CREATE TABLE)', $schemaGleich === $alle,
          $schemaGleich . ' von ' . $alle
          . ' — auch die einzeilig geschriebenen CREATE TABLE');

    /* DIE EINE ERWARTETE ABWEICHUNG: `jobs`. Das Backup schreibt ihren
     * eigenen Fortschritt in genau diese Tabelle, während sie läuft. Der Dump
     * hält deshalb den Stand „läuft" fest, während hier längst „fertig"
     * steht. Das ist der Schnappschuss, der nicht scharf ist — an der
     * harmlosesten möglichen Stelle. Jede WEITERE Abweichung ist ein Befund. */
    $unerwartet = array_values(array_diff($abweichung, ['jobs']));
    pruef('Der Inhalt stimmt überein (CHECKSUM TABLE EXTENDED)', $unerwartet === [],
          $summeGleich . ' von ' . $alle . ' gleich; abweichend: '
          . ($abweichung === [] ? 'keine' : implode(', ', $abweichung))
          . ($abweichung === ['jobs'] ? ' (erwartet: das Backup schreibt ihren eigenen Stand mit)' : ''));

    /* Eine unabhängige Gegenprobe an den Spurdaten: Sie sind der Grund für
     * die Hexschreibweise der Binärspalten. */
    $hA = (string)$pdo->query("SELECT COALESCE(MD5(GROUP_CONCAT(SHA2(blob_daten,256) ORDER BY owner_type,owner_id)),'-')
                                 FROM track_blobs")->fetchColumn();
    $hB = (string)$pruefPdo->query("SELECT COALESCE(MD5(GROUP_CONCAT(SHA2(blob_daten,256) ORDER BY owner_type,owner_id)),'-')
                                      FROM track_blobs")->fetchColumn();
    pruef('Die Spur-Blobs kommen Byte für Byte an', $hA === $hB && $hA !== '-',
          'Sammelprüfsumme ' . substr($hA, 0, 16) . '…');

    $lA = (string)$pdo->query("SELECT COALESCE(MD5(GROUP_CONCAT(CONCAT(lat,',',lon,',',COALESCE(ele,'-')) ORDER BY owner_type,owner_id,seq)),'-')
                                 FROM track_points")->fetchColumn();
    $lB = (string)$pruefPdo->query("SELECT COALESCE(MD5(GROUP_CONCAT(CONCAT(lat,',',lon,',',COALESCE(ele,'-')) ORDER BY owner_type,owner_id,seq)),'-')
                                      FROM track_points")->fetchColumn();
    pruef('Auch die Fliesskommazahlen der Spurpunkte kommen unverändert an',
          $lA === $lB, 'Sammelprüfsumme ' . substr($lA, 0, 16) . '…');
}

/* =========================================================================
 * Teil 8 — Wiederanlauf: was passiert, wenn ein Häppchen abbricht
 * ====================================================================== */
kopf('Teil 8 — Wiederanlauf nach einem abgebrochenen Häppchen');
$z2 = ['stand' => 'dump', 'bau' => KOMP_BAU_PRAEFIX . bin2hex(random_bytes(4)),
       'name' => komp_dateiname(), 'begonnen' => gmdate('Y-m-d\TH:i:s\Z'), 'roh_bytes' => 0];
$start = microtime(true);
komp_schub($pdo, $z2, static fn(): float => 5.6 - (microtime(true) - $start), 5.0);
$bauRoh = komp_wurzel() . '/' . $z2['bau'] . '/' . KOMP_ROHNAME;
$nachEins = (int)@filesize($bauRoh);
pruef('Nach dem ersten Häppchen steht etwas da', $nachEins > 0,
      number_format($nachEins, 0, ',', '.') . ' Byte');

/* Ein Absturz: Der Zustand wird zurückgedreht, die Datei bleibt, wie sie ist. */
$zurueck = $z2;
$zurueck['roh_bytes'] = (int)($z2['roh_bytes'] ?? 0);
$mehr = str_repeat("-- Muell aus einem abgebrochenen Haeppchen\n", 50);
$gzh = gzopen($bauRoh, 'ab6'); gzwrite($gzh, $mehr); gzclose($gzh);
clearstatcache(true, $bauRoh);
$mitMuell = (int)filesize($bauRoh);
pruef('Ein abgebrochenes Häppchen hinterlässt mehr Bytes, als der Zustand kennt',
      $mitMuell > (int)$zurueck['roh_bytes'],
      $mitMuell . ' gegen ' . (int)$zurueck['roh_bytes']);
$start = microtime(true);
komp_schub($pdo, $zurueck, static fn(): float => 5.3 - (microtime(true) - $start), 5.0);
clearstatcache(true, $bauRoh);
$inhalt2 = '';
$gzh = gzopen($bauRoh, 'rb');
while (!gzeof($gzh)) { $s = gzread($gzh, 262144); if ($s === false || $s === '') { break; } $inhalt2 .= $s; }
gzclose($gzh);
pruef('Der nächste Lauf schneidet den Rest weg', !str_contains($inhalt2, 'Muell aus einem'),
      'die gemerkte Länge ist die Wahrheit');
unset($inhalt2);

/* Und: Ist der Bauordner weg, wird von vorn begonnen statt in die Leere angehängt. */
$verlorenR = $zurueck;
@unlink($bauRoh);
$start = microtime(true);
komp_schub($pdo, $verlorenR, static fn(): float => 5.3 - (microtime(true) - $start), 5.0);
$gzh = gzopen($bauRoh, 'rb');
$anfang = (string)gzread($gzh, 4096);
gzclose($gzh);
pruef('Ist der Baustand verschwunden, beginnt der Lauf von vorn',
      /* BEIDE SCHREIBWEISEN, wie in wiederherstellen.php (S7, F-S7-04):
         Ein Dump, der vor der Begriffsumstellung entstanden ist, traegt
         noch "Komplett-Backup der Installation". Wer hier nur die neue
         Schreibweise sucht, laesst die Probe an einem alten Baustand
         scheitern, ohne dass etwas kaputt waere. */
      str_contains($anfang, 'Komplett-Backup der Installation')
      || str_contains($anfang, 'Komplett-Backup der Installation'),
      'der Kopf steht wieder am Anfang');
pruef('...und das wird im Zustand vermerkt', isset($verlorenR['neu_begonnen']),
      (string)($verlorenR['neu_begonnen'] ?? '-'));
komp_bau_weg((string)$z2['bau']);

/* =========================================================================
 * Teil 9 — Aufbewahrung, Speichergrenze, Rückstand
 * ====================================================================== */
kopf('Teil 9 — Aufbewahrung, Speichergrenze, Rückstand');
$zahlen = edbak_ablage_zahlen(true);
pruef('Die Komplett-Backups werden eigens gezählt', (int)$zahlen['komplett'] >= 1,
      $zahlen['komplett'] . ' Stände, '
      . number_format((int)$zahlen['komplett_bytes'] / 1048576, 1, ',', '.') . ' MB');
pruef('...und zählen nicht als „auffälliger Rest"',
      (int)$zahlen['sonstige_bytes'] < (int)$zahlen['komplett_bytes'],
      'sonstige ' . number_format((int)$zahlen['sonstige_bytes'], 0, ',', '.') . ' Byte');

pruef('Ohne Plan ist nichts fällig', komp_plan() === 'aus' ? !komp_faellig() : true,
      'Plan: ' . komp_plan());
pruef('Ein unbekannter Plan wird abgewiesen', !komp_plan_setzen('manchmal'));
pruef('Eine unsinnige Aufbewahrung wird abgewiesen',
      !komp_aufbewahrung_setzen(0) && !komp_aufbewahrung_setzen(999));

/* Verdrängung: mit Aufbewahrung 1 muss der zweite Stand den ersten nehmen. */
$vorher = count(komp_staende());
$zweit = komp_wurzel() . '/' . gmdate('Y-m-d\TH-i-s\Z', time() - 86400) . '_00000001.edk';
copy($pfad, $zweit);
pruef('Zwei Stände liegen da', count(komp_staende()) === $vorher + 1,
      count(komp_staende()) . ' Stände');
$altAufb = komp_aufbewahrung();
edbak_marke_setzen('komplett_aufbewahrung', '1');
$weg = komp_verdraengen();
edbak_marke_setzen('komplett_aufbewahrung', (string)$altAufb);
pruef('Die Verdrängung nimmt den ältesten und nennt ihn beim Namen',
      count($weg) >= 1 && in_array(basename($zweit), $weg, true),
      implode(', ', $weg));
pruef('Der jüngste Stand bleibt', is_file($pfad));

/* =========================================================================
 * Teil 10 — Der Versand auf ein Backup-Ziel
 * ====================================================================== */
kopf('Teil 10 — Der Versand auf ein Backup-Ziel');
if ($zielWurzel === '' || !is_dir($zielWurzel)) {
    offenlassen('Versand', 'ohne --ziel=<wurzel der gegenstellen> wird nichts gesendet');
} else {
    require_once $srv . '/sicherungsziel_lib.php';
    if (!sz_tabelle_da()) {
        offenlassen('Versand', 'die Tabelle backup_targets fehlt (Migration nicht gelaufen)');
    } else {
        /* Ein Wegwerf-Ziel auf die örtliche Gegenstelle. Es wird am Ende
         * wieder entfernt — eine Probe, die Einträge hinterlässt, ist keine. */
        $name = 'komplettprobe-' . bin2hex(random_bytes(3));
        [$ok, $was] = sz_speichern(null, [
            'name' => $name, 'protokoll' => 'ftp', 'host' => '127.0.0.1',
            'port' => '2121', 'nutzer' => 'probe', 'pfad' => '/',
            'passiv' => '1', 'aktiv' => '1',
        ], 'geheim-probe-2026', null);
        pruef('Ein Wegwerf-Ziel lässt sich anlegen', $ok,
              $ok ? $name : implode(' ', (array)$was));
        if ($ok) {
            $ziel = null;
            foreach (sz_alle() as $zz) { if ($zz['name'] === $name) { $ziel = $zz; break; } }
            $start = microtime(true);
            $e = sz_versand_schub(static fn(): float => 60.0 - (microtime(true) - $start), 5.0);
            pruef('Der Schub läuft ohne Fehler', $e['fehler'] === [],
                  implode(' | ', $e['fehler']));
            pruef('Es geht etwas hinaus', $e['gesendet'] > 0,
                  $e['gesendet'] . ' Dateien, '
                  . number_format($e['bytes'] / 1048576, 1, ',', '.') . ' MB');
            $dort = $zielWurzel . '/ftp/' . KOMP_ORDNER . '/' . $datei;
            pruef('Das Komplett-Backup liegt am Ziel unter „' . KOMP_ORDNER . '/"',
                  is_file($dort), $dort);
            pruef('...und ist Byte für Byte dieselbe',
                  is_file($dort) && hash_file('sha256', $dort) === hash_file('sha256', $pfad));
            /* Ein zweiter Schub darf sie NICHT noch einmal schicken. */
            $start = microtime(true);
            $e2 = sz_versand_schub(static fn(): float => 60.0 - (microtime(true) - $start), 5.0);
            pruef('Ein zweiter Schub schickt dieselbe Datei nicht noch einmal',
                  $e2['gesendet'] === 0, $e2['gesendet'] . ' Dateien');
            /* Halbe Datei am Ziel: Name gleich, Grösse anders -> muss neu. */
            file_put_contents($dort, substr((string)file_get_contents($dort), 0, 1000));
            $start = microtime(true);
            $e3 = sz_versand_schub(static fn(): float => 60.0 - (microtime(true) - $start), 5.0);
            pruef('Eine halbe Datei am Ziel wird erkannt und ersetzt',
                  $e3['gesendet'] >= 1 && hash_file('sha256', $dort) === hash_file('sha256', $pfad),
                  'Name gleich, Grösse anders');
            if ($ziel !== null) { sz_loeschen((int)$ziel['id']); }
            pruef('Das Wegwerf-Ziel ist wieder weg',
                  !in_array($name, array_column(sz_alle(), 'name'), true));
        }
    }
}

/* ---- Schluss ------------------------------------------------------------- */
echo "\n-> $n Erwartungen, $offen nicht erfuellt\n";
exit($offen === 0 ? 0 : 1);
