<?php
declare(strict_types=1);

/**
 * Die Behandler des Fehlerprotokolls — stehen sie da, und nur dort? (P5c/AP3)
 *
 * Anlass: Backlog Nr. 248, E-P5c-58. Seit Web 20.40.0 richtet `db.php` drei
 * Behandler ein (`systemmeldung_lib.php`): fuer Ausnahmen, fuer Warnungen und
 * fuer den Abbruch am Ende der Anfrage. Ohne sie steht nichts im Reiter
 * System, und die NutzerIn sieht eine weisse Seite statt einer Kennung.
 *
 * WARUM NICHT DAS REGISTER. `tools/zaehlung/` kennt Decken: Eine Zeile ueber
 * der Decke ist rot, eine darunter gruen. Ein ENTFERNTER Behandler waere dort
 * „0 von hoechstens 1" — gruen. Diese Pruefung verlangt GENAU die drei Zeilen.
 *
 * WAS SIE VERLANGT
 *   1. In `server/db.php` genau ein `set_exception_handler('system_ausnahme_behandeln')`,
 *      ein `set_error_handler('system_fehler_behandeln')` und ein
 *      `register_shutdown_function('system_abbruch_pruefen')`.
 *   2. Sonst unter `server/` (ohne `vendor/`) KEIN `set_exception_handler(` —
 *      ein zweiter ersetzte den ersten still, fuer jede Anfrage danach.
 *   3. Ein `set_error_handler(` ausserhalb von `db.php` nur mit ebenso vielen
 *      `restore_error_handler(` in derselben Datei: ein kurzer, lokaler
 *      Behandler (`sicherungsziel_lib.php` faengt so die Warnung eines
 *      Verbindungsaufbaus) ist erlaubt, ein dauernder nicht.
 *
 * WIE: mit dem Tokenizer. Ein `grep` traefe jeden Kommentar, der die Namen
 * nennt — und dieser Kopf nennt sie alle.
 *
 * Aufruf:
 *   php tools/quelltext/behandler.php
 *   php tools/quelltext/behandler.php --selbstprobe
 *
 * Rueckgabewert: 0 = in Ordnung · 1 = Befund · 2 = Datei fehlt.
 */

const BEHANDLER_SOLL = [
    'set_exception_handler'      => 'system_ausnahme_behandeln',
    'set_error_handler'          => 'system_fehler_behandeln',
    'register_shutdown_function' => 'system_abbruch_pruefen',
];

/**
 * Die Aufrufe der drei Funktionen in einer Quelle, je mit ihrem ersten
 * Argument, wenn es eine Zeichenkette ist.
 *
 * @return list<array{name:string, arg:?string, zeile:int}>
 */
function behandler_aufrufe(string $quelle): array
{
    $t = @token_get_all($quelle);
    if (!is_array($t)) { return []; }
    $folge = [];
    foreach ($t as $x) {
        if (is_array($x) && in_array($x[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) { continue; }
        $folge[] = $x;
    }
    $namen = array_merge(array_keys(BEHANDLER_SOLL), ['restore_error_handler']);
    $aus = [];
    foreach ($folge as $i => $x) {
        if (!is_array($x) || $x[0] !== T_STRING || !in_array(strtolower($x[1]), $namen, true)) { continue; }
        if (($folge[$i + 1] ?? null) !== '(') { continue; }
        /* Eine Methode gleichen Namens (`->set_error_handler(`) ist kein Aufruf
         * der PHP-Funktion. */
        $vor = $folge[$i - 1] ?? null;
        if (is_array($vor) && in_array($vor[0], [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true)) { continue; }
        $arg = $folge[$i + 2] ?? null;
        $aus[] = ['name'  => strtolower($x[1]),
                  'arg'   => is_array($arg) && $arg[0] === T_CONSTANT_ENCAPSED_STRING ? trim($arg[1], '\'"') : null,
                  'zeile' => (int)$x[2]];
    }
    return $aus;
}

/**
 * Die Befunde fuer eine Menge von Dateien.
 *
 * @param array<string,string> $dateien Pfad (relativ zu `server/`) => Quelle
 * @return list<string>
 */
function behandler_pruefen(array $dateien): array
{
    $befunde = [];
    if (!isset($dateien['db.php'])) { return ['db.php fehlt']; }
    $inDb = behandler_aufrufe($dateien['db.php']);
    foreach (BEHANDLER_SOLL as $fn => $ziel) {
        $treffer = array_values(array_filter($inDb, static fn($a) => $a['name'] === $fn));
        $richtig = array_filter($treffer, static fn($a) => $a['arg'] === $ziel);
        if (count($treffer) !== 1 || count($richtig) !== 1) {
            $befunde[] = sprintf("db.php: %d× %s(), verlangt genau 1× %s('%s')",
                                 count($treffer), $fn, $fn, $ziel);
        }
    }
    foreach ($dateien as $pfad => $quelle) {
        if ($pfad === 'db.php') { continue; }
        $a = behandler_aufrufe($quelle);
        $zahl = static fn(string $n): int => count(array_filter($a, static fn($x) => $x['name'] === $n));
        foreach ($a as $x) {
            if ($x['name'] === 'set_exception_handler') {
                $befunde[] = "$pfad:{$x['zeile']}: set_exception_handler() ausserhalb von db.php "
                           . '— ersetzt den Behandler des Fehlerprotokolls';
            }
        }
        if ($zahl('set_error_handler') > $zahl('restore_error_handler')) {
            $befunde[] = "$pfad: " . $zahl('set_error_handler') . '× set_error_handler(), aber nur '
                       . $zahl('restore_error_handler') . '× restore_error_handler() — ein dauernder '
                       . 'Behandler ersetzt den des Fehlerprotokolls';
        }
    }
    return $befunde;
}

/* ---- Selbstprobe --------------------------------------------------------- */

if (in_array('--selbstprobe', array_slice($argv, 1), true)) {
    $db = "<?php\nset_exception_handler('system_ausnahme_behandeln');\n"
        . "set_error_handler('system_fehler_behandeln');\n"
        . "register_shutdown_function('system_abbruch_pruefen');\n";
    $faelle = [
        ['alles da',                          ['db.php' => $db],                                          false],
        ['Ausnahme-Behandler entfernt',       ['db.php' => str_replace("set_exception_handler('system_ausnahme_behandeln');", '', $db)], true],
        ['Behandler nur im Kommentar',        ['db.php' => str_replace("set_error_handler(", "// set_error_handler(", $db)], true],
        ['Behandler zweimal',                 ['db.php' => $db . "set_error_handler('system_fehler_behandeln');\n"], true],
        ['falscher Name',                     ['db.php' => str_replace('system_abbruch_pruefen', 'etwas_anderes', $db)], true],
        ['zweiter Ausnahme-Behandler anderswo', ['db.php' => $db, 'x.php' => "<?php set_exception_handler(fn() => 1);"], true],
        ['lokaler Behandler mit restore',     ['db.php' => $db, 'y.php' => "<?php set_error_handler(fn() => true); f(); restore_error_handler();"], false],
        ['lokaler Behandler ohne restore',    ['db.php' => $db, 'y.php' => "<?php set_error_handler(fn() => true); f();"], true],
        ['Methode gleichen Namens',           ['db.php' => $db, 'z.php' => "<?php \$o->set_exception_handler(1);"], false],
    ];
    $ok = 0; $offen = 0;
    echo "Selbstprobe — trifft die Pruefung das Richtige und nur das?\n\n";
    foreach ($faelle as [$name, $dateien, $erwartet]) {
        $traf = behandler_pruefen($dateien) !== [];
        $gut = $traf === $erwartet;
        $gut ? $ok++ : $offen++;
        printf("  [%s] %-38s erwartet %s, gemessen %s\n", $gut ? 'ok ' : 'FEHL', $name,
               $erwartet ? 'Befund ' : 'sauber ', $traf ? 'Befund' : 'sauber');
    }
    printf("\n  erfuellt: %d · offen: %d\n", $ok, $offen);
    exit($offen === 0 ? 0 : 1);
}

/* ---- Der Lauf ------------------------------------------------------------ */

$srv = dirname(__DIR__, 2) . '/server';
if (!is_file($srv . '/db.php')) { fwrite(STDERR, "server/db.php fehlt\n"); exit(2); }
$dateien = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srv, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $rel = substr($f->getPathname(), strlen($srv) + 1);
    if ($f->getExtension() !== 'php' || str_starts_with($rel, 'vendor/')) { continue; }
    $dateien[$rel] = (string)file_get_contents($f->getPathname());
}
ksort($dateien);
$befunde = behandler_pruefen($dateien);
echo "Die Behandler des Fehlerprotokolls\n\n";
printf("  %-34s %d\n", 'PHP-Dateien unter server/:', count($dateien));
foreach (behandler_aufrufe($dateien['db.php']) as $a) {
    printf("  %-34s %s('%s')\n", 'db.php:' . $a['zeile'], $a['name'], (string)$a['arg']);
}
printf("  %-34s %d\n", 'Befunde:', count($befunde));
foreach ($befunde as $b) { echo "  ! $b\n"; }
exit($befunde ? 1 : 0);
