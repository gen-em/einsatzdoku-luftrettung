<?php
/**
 * Spaltenregister — `schema.sql` gegen `mf_missions_register()` (Schritt 15/AP6).
 *
 * Zwei Fragen, zwei Zahlen:
 *
 *   1. Fuehrt das Register JEDE Spalte von `missions` aus `schema.sql`?
 *      Eine neue Spalte, die niemand eintraegt, taucht in keiner der sechs
 *      erzeugten Listen auf — sie fehlte dann still in Export und Backup.
 *   2. Kennt das Register eine Spalte, die es nicht gibt? Dann liefe ein
 *      `SELECT` auf einen Namen, den die Datenbank nicht hat.
 *
 * Dazu die Selbstprobe von `mf_spalten()`: Die Positionen jedes Zwecks
 * muessen eine lueckenlose Folge ab 0 sein.
 *
 * Rueckgabe 0 = in Ordnung, 1 = Befund, 2 = nicht gelaufen.
 *
 *   php tools/spaltenregister/pruefen.php
 */
declare(strict_types=1);

$wurzel = dirname(__DIR__, 2) . '/server';
if (!is_dir($wurzel)) { fwrite(STDERR, "server/ nicht gefunden\n"); exit(2); }
require_once $wurzel . '/mission_fields_lib.php';

$schema = @file_get_contents($wurzel . '/schema.sql');
if ($schema === false) { fwrite(STDERR, "schema.sql nicht lesbar\n"); exit(2); }

if (!preg_match('/CREATE TABLE missions \((.*?)\n\) ENGINE/s', $schema, $m)) {
    fwrite(STDERR, "CREATE TABLE missions nicht gefunden\n"); exit(2);
}
$ausSchema = [];
foreach (explode("\n", $m[1]) as $z) {
    $z = trim($z);
    if ($z === '' || str_starts_with($z, '--')) { continue; }
    if (preg_match('/^(PRIMARY|FOREIGN|INDEX|UNIQUE|KEY|CONSTRAINT)/i', $z)) { continue; }
    if (preg_match('/^`?([a-z_]+)`?\s+/i', $z, $t)) { $ausSchema[] = $t[1]; }
}
$imRegister = array_keys(mf_missions_register());

$fehlend = array_values(array_diff($ausSchema, $imRegister));
$zuviel  = array_values(array_diff($imRegister, $ausSchema));

echo "Spaltenregister — schema.sql gegen mf_missions_register()\n";
echo str_repeat('=', 74) . "\n";
printf("  Spalten in schema.sql:            %3d\n", count($ausSchema));
printf("  Spalten im Register:              %3d\n", count($imRegister));
printf("  im Schema, nicht im Register:     %3d%s\n", count($fehlend),
       $fehlend ? '  -> ' . implode(', ', $fehlend) : '');
printf("  im Register, nicht im Schema:     %3d%s\n", count($zuviel),
       $zuviel ? '  -> ' . implode(', ', $zuviel) : '');

$zwecke = [];
foreach (mf_missions_register() as $sp => $z) { foreach (array_keys($z) as $zw) { $zwecke[$zw] = true; } }
$zwecke = array_keys($zwecke);
sort($zwecke);
echo "\n  Zwecke und ihre Listen:\n";
$fehler = count($fehlend) + count($zuviel);
foreach ($zwecke as $zw) {
    try {
        $l = mf_spalten($zw);
        printf("    %-16s %2d Spalten\n", $zw, count($l));
    } catch (Throwable $ex) {
        printf("    %-16s BEFUND: %s\n", $zw, $ex->getMessage());
        $fehler++;
    }
}

/* Jede Spalte muss einen Zweck ODER einen Grund haben. */
$gruende = mf_missions_gruende();
$ohne = [];
foreach (mf_missions_register() as $sp => $z) {
    if ($z === [] && !isset($gruende[$sp])) { $ohne[] = $sp; }
}
printf("\n  Spalten ohne Zweck und ohne Grund: %3d%s\n", count($ohne),
       $ohne ? '  -> ' . implode(', ', $ohne) : '');
$fehler += count($ohne);

echo "\n" . str_repeat('=', 74) . "\n";
echo $fehler === 0
    ? "Das Register fuehrt jede Spalte genau einmal.\n"
    : "BEFUNDE: $fehler\n";
exit($fehler === 0 ? 0 : 1);
