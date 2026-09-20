<?php
declare(strict_types=1);

/**
 * DAS JOBREGISTER — steht in `docs/Technik.md` noch, was im Code steht?
 * (Backlog Nr. 208, Schritt 16)
 *
 * WOGEGEN. Der Jobkatalog in `server/jobs_lib.php` ist die Quelle; das
 * Register in `docs/Technik.md` 4.97a ist eine von Hand gefuehrte
 * Aufzaehlung daneben. Sie ist dreimal hinterhergehinkt — P5a/AP5, P5a/AP8
 * und zuletzt am 20.09.2026, gemessen: 11 Jobs im Code gegen 9 im Register
 * (`mail` und `konto_verfall` fehlten), 16 Raeumschritte gegen „dreizehn".
 * Zweimal sind die Zahlen von Hand berichtigt worden, zweimal wuchs der
 * Abstand wieder. Nr. 208 verlangt deshalb ausdruecklich ein PRUEFMITTEL —
 * „sonst wandert das Problem nur eine Ebene weiter".
 *
 * DIE SICHTBARE BESCHREIBUNG WIRD SEIT WEB 20.26.0 ERZEUGT
 * (`jobs_katalog()` aus `array_keys(job_aufraeumen_schritte())`) und kann
 * nicht mehr altern. Die Registerzeile in der Dokumentation bleibt Prosa,
 * weil sie mehr sagt als eine Liste — sie wird ab jetzt nachgezaehlt.
 *
 * MIT DEM TOKENIZER, NICHT MIT `grep` — und nicht durch Ausfuehren.
 * `jobs_lib.php` laedt in Zeile 37 `db.php` und damit `config.php`; in Stufe
 * 1 der Kette gibt es keine Installation. Eine Pruefung, die dort nicht
 * laufen kann, laeuft nirgends. `token_get_all()` sieht dagegen genau das,
 * was der Uebersetzer sieht — und ein `grep` ueber `'name' =>` traefe jeden
 * Kommentar und jede gleichnamige Zeichenkette.
 *
 * WAS SIE NICHT SIEHT. Ob ein Job auch TUT, was danebensteht — das ist eine
 * Frage an `tools/jobprobe/`. Und einen Job, der nicht als Zeichenketten-
 * schluessel im Katalogliteral steht, sondern zur Laufzeit hineingerechnet
 * wird; es gibt heute keinen, und der Kopf von `jobs_katalog()` sagt, dass
 * es keinen geben soll.
 *
 * Aufruf:
 *   php tools/jobregister/pruefen.php
 *   php tools/jobregister/pruefen.php --selbstprobe
 *
 * Rueckgabewert: 0 = Register und Code stimmen ueberein · 1 = Befund ·
 *                2 = eine der beiden Dateien fehlt oder ist unlesbar.
 */

/** Zahlwoerter, wie die Registerzeile sie schreibt. */
const JR_ZAHLWORT = [
    'ein' => 1, 'zwei' => 2, 'drei' => 3, 'vier' => 4, 'fünf' => 5,
    'sechs' => 6, 'sieben' => 7, 'acht' => 8, 'neun' => 9, 'zehn' => 10,
    'elf' => 11, 'zwölf' => 12, 'dreizehn' => 13, 'vierzehn' => 14,
    'fünfzehn' => 15, 'sechzehn' => 16, 'siebzehn' => 17, 'achtzehn' => 18,
    'neunzehn' => 19, 'zwanzig' => 20, 'einundzwanzig' => 21,
    'zweiundzwanzig' => 22, 'dreiundzwanzig' => 23, 'vierundzwanzig' => 24,
    'fünfundzwanzig' => 25,
];

/**
 * Die Zeichenketten-Schluessel EINES Array-Literals in einer Funktion.
 *
 * DIE KLAMMERTIEFE IST DER GANZE TRICK. Gesucht sind die Schluessel der
 * OBERSTEN Ebene jenes Literals — nicht die von `'titel' => …` eine Ebene
 * tiefer. Gezaehlt wird deshalb ab der oeffnenden Klammer des Literals, und
 * genommen wird nur, was auf Tiefe 1 steht und ein `=>` hinter sich hat.
 *
 * @param string $quelle   PHP-Quelltext
 * @param string $funktion Name der Funktion, in der gesucht wird
 * @param string $start    Zeichenfolge, hinter der das Literal beginnt
 *                         (`$katalog = [` bzw. `return [`)
 * @return list<string> Schluessel in Quelltextreihenfolge
 */
function jr_schluessel(string $quelle, string $funktion, string $start): array
{
    $t = token_get_all($quelle);

    /* Erst den Rumpf der Funktion finden. */
    $i = 0; $n = count($t); $gefunden = false;
    for (; $i < $n; $i++) {
        if (!is_array($t[$i]) || $t[$i][0] !== T_FUNCTION) { continue; }
        for ($j = $i + 1; $j < $n; $j++) {
            if (is_array($t[$j]) && $t[$j][0] === T_WHITESPACE) { continue; }
            if (is_array($t[$j]) && $t[$j][0] === T_STRING && $t[$j][1] === $funktion) {
                $gefunden = true;
            }
            break;
        }
        if ($gefunden) { break; }
    }
    if (!$gefunden) { return []; }

    /* Dann das Literal. `$katalog = [` und `return [` sind beide an ihrem
     * letzten Token erkennbar: einer oeffnenden eckigen Klammer, der ein
     * `=` bzw. ein `return` vorausgeht. */
    $vorher = str_contains($start, 'return') ? T_RETURN : null;
    $auf = -1;
    for ($k = $i; $k < $n; $k++) {
        if ($t[$k] !== '[') { continue; }
        for ($j = $k - 1; $j >= 0; $j--) {
            if (is_array($t[$j]) && $t[$j][0] === T_WHITESPACE) { continue; }
            $passt = $vorher === null
                ? ($t[$j] === '=')
                : (is_array($t[$j]) && $t[$j][0] === $vorher);
            if ($passt) { $auf = $k; }
            break;
        }
        if ($auf >= 0) { break; }
    }
    if ($auf < 0) { return []; }

    $raus = []; $tiefe = 0;
    for ($k = $auf; $k < $n; $k++) {
        $tok = $t[$k];
        if ($tok === '[') { $tiefe++; continue; }
        if ($tok === ']') { $tiefe--; if ($tiefe === 0) { break; } continue; }
        if ($tiefe !== 1) { continue; }
        if (!is_array($tok) || $tok[0] !== T_CONSTANT_ENCAPSED_STRING) { continue; }
        /* Nur, wenn ein `=>` folgt — sonst ist es ein Wert, kein Schluessel. */
        for ($j = $k + 1; $j < $n; $j++) {
            if (is_array($t[$j]) && $t[$j][0] === T_WHITESPACE) { continue; }
            if (is_array($t[$j]) && $t[$j][0] === T_DOUBLE_ARROW) {
                $raus[] = substr($tok[1], 1, -1);
            }
            break;
        }
    }
    return $raus;
}

/** Die Jobnamen aus der Registertabelle in `docs/Technik.md` 4.97a. */
function jr_register(string $md): array
{
    $pos = strpos($md, '#### Der Katalog');
    if ($pos === false) { return ['jobs' => [], 'zeile' => '']; }
    $rest  = substr($md, $pos);
    $jobs  = []; $zeile = '';
    foreach (explode("\n", $rest) as $z) {
        if ($z !== '' && $z[0] !== '|') {
            if ($jobs !== []) { break; }     // Tabelle vorbei
            continue;
        }
        if (!preg_match('/^\|\s*`([a-z_]+)`\s*\|/u', $z, $m)) { continue; }
        $jobs[] = $m[1];
        if ($m[1] === 'aufraeumen') { $zeile = $z; }
    }
    return ['jobs' => $jobs, 'zeile' => $zeile];
}

/** Das Zahlwort aus der `aufraeumen`-Zeile, als Zahl. `null` = keines. */
function jr_zahl(string $zeile): ?int
{
    if (!preg_match('/\*\*([A-Za-zÄÖÜäöüß]+) Schritte\*\*/u', $zeile, $m)) { return null; }
    return JR_ZAHLWORT[mb_strtolower($m[1])] ?? null;
}

/* ---- Selbstprobe --------------------------------------------------------- */

function jr_selbstprobe(): int
{
    $faelle = [
        ["<?php function f() { \$katalog = ['a' => [1], 'b' => ['x' => 2]]; }",
         'f', '$katalog = [', ['a', 'b'], 'zwei Schluessel, verschachtelt'],
        ["<?php function g() { return ['Eins' => fn() => 1, 'Zwei' => fn() => 2]; }",
         'g', 'return [', ['Eins', 'Zwei'], 'return-Literal mit Pfeilfunktionen'],
        ["<?php function h() { return ['nur', 'werte']; }",
         'h', 'return [', [], 'Werte ohne Pfeil sind keine Schluessel'],
        ["<?php function i() { return ['a' => ['b' => 1, 'c' => 2]]; }",
         'i', 'return [', ['a'], 'tiefere Ebene wird nicht mitgezaehlt'],
        ["<?php /* function x() { return ['tot' => 1]; } */ function j() { return ['echt' => 1]; }",
         'j', 'return [', ['echt'], 'Kommentar zaehlt nicht'],
        ["<?php function k() { return ['x' => 1]; }",
         'nichtda', 'return [', [], 'unbekannte Funktion'],
    ];
    $ok = 0;
    foreach ($faelle as [$q, $f, $s, $soll, $was]) {
        $ist = jr_schluessel($q, $f, $s);
        $gut = $ist === $soll;
        $ok += $gut ? 1 : 0;
        printf("  %s  %-45s %s\n", $gut ? 'ok  ' : 'FEHL', $was,
            $gut ? '' : '(ist: ' . implode(',', $ist) . ')');
    }
    $zahlen = [
        ['| `aufraeumen` | ja | **siebzehn Schritte** — a, b |', 17, 'Zahlwort siebzehn'],
        ['| `aufraeumen` | ja | **dreizehn Schritte** — a |',    13, 'Zahlwort dreizehn'],
        ['| `aufraeumen` | ja | ganz ohne Zahl |',            null, 'kein Zahlwort'],
    ];
    foreach ($zahlen as [$z, $soll, $was]) {
        $ist = jr_zahl($z);
        $gut = $ist === $soll;
        $ok += $gut ? 1 : 0;
        printf("  %s  %-45s %s\n", $gut ? 'ok  ' : 'FEHL', $was,
            $gut ? '' : '(ist: ' . var_export($ist, true) . ')');
    }
    $gesamt = count($faelle) + count($zahlen);
    printf("\nSelbstprobe: %d von %d\n", $ok, $gesamt);
    return $ok === $gesamt ? 0 : 1;
}

/* ---- Lauf ---------------------------------------------------------------- */

$wurzel = dirname(__DIR__, 2);

if (in_array('--selbstprobe', $argv, true)) {
    echo "Jobregister — Selbstprobe des Lesers\n";
    echo "====================================\n";
    exit(jr_selbstprobe());
}

$phpDatei = $wurzel . '/server/jobs_lib.php';
$mdDatei  = $wurzel . '/docs/Technik.md';
foreach ([$phpDatei, $mdDatei] as $d) {
    if (!is_readable($d)) {
        fwrite(STDERR, "Datei fehlt oder ist unlesbar: $d\n");
        exit(2);
    }
}

$quelle   = (string)file_get_contents($phpDatei);
$jobs     = jr_schluessel($quelle, 'jobs_katalog', '$katalog = [');
$schritte = jr_schluessel($quelle, 'job_aufraeumen_schritte', 'return [');

$md       = (string)file_get_contents($mdDatei);
$reg      = jr_register($md);
$regJobs  = $reg['jobs'];
$regZahl  = jr_zahl($reg['zeile']);

$befunde = [];
if ($jobs === []) {
    $befunde[] = 'Im Katalog von `jobs_katalog()` wurde kein einziger Job gefunden — '
               . 'hat sich der Aufbau der Funktion geändert?';
}
if ($schritte === []) {
    $befunde[] = 'In `job_aufraeumen_schritte()` wurde kein einziger Schritt gefunden — '
               . 'hat sich der Aufbau der Funktion geändert?';
}
foreach (array_diff($jobs, $regJobs) as $fehlt) {
    $befunde[] = "Der Job `$fehlt` steht im Katalog, aber nicht im Register "
               . '(docs/Technik.md 4.97a, „Der Katalog").';
}
foreach (array_diff($regJobs, $jobs) as $zuviel) {
    $befunde[] = "Der Job `$zuviel` steht im Register, aber nicht im Katalog.";
}
if ($regZahl === null) {
    $befunde[] = 'Die Zeile `aufraeumen` im Register nennt keine Zahl der Form '
               . '**<Zahlwort> Schritte** — sie ist damit nicht nachzählbar.';
} elseif ($regZahl !== count($schritte)) {
    $befunde[] = sprintf('Das Register nennt %d Schritte, `job_aufraeumen_schritte()` '
        . 'hat %d.', $regZahl, count($schritte));
}
foreach ($schritte as $s) {
    if (!str_contains($reg['zeile'], $s)) {
        $befunde[] = "Der Aufräumschritt „$s\" wird in der Registerzeile nicht genannt.";
    }
}

echo "Jobregister — steht in docs/Technik.md noch, was im Code steht?\n";
echo "===============================================================\n";
printf("  Jobs im Katalog (jobs_lib.php):   %3d\n", count($jobs));
printf("  Jobs im Register (Technik.md):    %3d\n", count($regJobs));
printf("  Raeumschritte im Code:            %3d\n", count($schritte));
printf("  Raeumschritte laut Register:      %s\n",
    $regZahl === null ? ' — ' : sprintf('%3d', $regZahl));
echo "\n";
foreach ($befunde as $b) { echo "  BEFUND: $b\n"; }
printf("\nBefunde: %d\n", count($befunde));

echo "\nGRENZEN dieser Pruefung — was sie NICHT sieht:\n";
echo "  · ob ein Job tut, was danebensteht (dafuer: tools/jobprobe/)\n";
echo "  · einen Job, der nicht als Zeichenkettenschluessel im Katalogliteral\n";
echo "    steht, sondern zur Laufzeit hineingerechnet wird\n";
echo "  · die Prosa der Registerzeile jenseits der Namen und der Zahl\n";

exit($befunde === [] ? 0 : 1);
