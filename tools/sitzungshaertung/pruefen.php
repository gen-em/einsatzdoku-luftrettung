<?php
declare(strict_types=1);

/**
 * SITZUNGSHAERTUNG — steht vor jedem `session_start()` die Härtung?
 * ===========================================================================
 *
 * Aufruf:  php tools/sitzungshaertung/pruefen.php [--selbstprobe]
 * Rückgabe: 0 = keine Befunde · 1 = Befunde · 2 = die Probe kam nicht los
 *
 * WOGEGEN. Bis Web 20.9.1 stand `ini_set('session.use_strict_mode', '1')` an
 * genau zwei Stellen — `install.php` und `wiederherstellen.php` —, also
 * ausgerechnet den beiden Wegen, die KEINE Anmeldesitzung tragen. Auf den
 * fünf Wegen, die eine tragen (`auth_guard.php`, `login.php`,
 * `session_lib.php`, `pw_handling.php`, `rechtstext_seite.php`), fehlte sie.
 * Ohne sie übernimmt PHP eine Sitzungskennung, die der Browser mitbringt,
 * auch wenn es sie nie vergeben hat — Session-Fixation, und der Schutz hing
 * an der `php.ini` des Hosters.
 *
 * WARUM EIN WERKZEUG UND NICHT NUR EIN COMMIT. Die Zeile ist unscheinbar und
 * steht neben dem Aufruf, den sie schützt. Ein neuer Weg, der `session_start()`
 * aufruft und sie vergisst, sieht genauso aus wie einer, der sie hat — und
 * es passiert nichts, was auffiele. Genau dafür ist Stufe 1 da.
 *
 * MIT DEM TOKENIZER, NICHT MIT `grep`. Ein `grep` über `session_start` findet
 * jede Erwähnung in jedem Kommentar — die erste Fassung dieser Prüfung
 * meldete deshalb zwei Befunde, und beide waren Kommentarzeilen, die das
 * Werkzeug selbst beschrieben. Der Tokenizer sieht nur echte Aufrufe.
 *
 * WAS SIE NICHT MESSEN KANN:
 *
 * - **Ob die Einstellung wirkt.** `ini_set()` kann scheitern (`session.*`
 *   lässt sich nach `session_start()` nicht mehr setzen, und manche Hoster
 *   sperren einzelne Direktiven). Das misst nur eine laufende Installation:
 *   eine vorgegebene Sitzungskennung anbieten und nachsehen, ob eine andere
 *   zurückkommt. Prüfpunkt im Prüfdokument.
 * - **Die Reihenfolge innerhalb der zwölf Zeilen.** Steht die Zeile in
 *   einem `if`, das nie zutrifft, zählt sie hier trotzdem.
 * - **`session_start()` in einer Bibliothek Dritter.** `vendor/` ist
 *   ausgenommen; `phpseclib3/Crypt/Random.php` startet eine eigene Sitzung
 *   und wird nicht von uns gepflegt.
 */

const ABSTAND = 12;   // so viele Zeilen davor darf die Härtung stehen

/** Echte `session_start()`-Aufrufe einer Datei — Zeilennummern. */
function sp_aufrufe(string $quelle): array
{
    $tok = token_get_all($quelle);
    $treffer = [];
    foreach ($tok as $i => $t) {
        if (!is_array($t) || $t[0] !== T_STRING) { continue; }
        if (strtolower($t[1]) !== 'session_start') { continue; }
        /* Ein Aufruf, keine Definition: davor darf kein `function` stehen,
         * danach muss eine offene Klammer kommen. */
        $vor = $i - 1;
        while ($vor >= 0 && is_array($tok[$vor]) && in_array($tok[$vor][0], [T_WHITESPACE], true)) { $vor--; }
        if ($vor >= 0 && is_array($tok[$vor]) && $tok[$vor][0] === T_FUNCTION) { continue; }
        $nach = $i + 1;
        while (isset($tok[$nach]) && is_array($tok[$nach]) && $tok[$nach][0] === T_WHITESPACE) { $nach++; }
        if (!isset($tok[$nach]) || $tok[$nach] !== '(') { continue; }
        $treffer[] = $t[2];
    }
    return $treffer;
}

/** Steht die Härtung in den ABSTAND Zeilen davor? */
function sp_gehaertet(array $zeilen, int $nr): bool
{
    $von = max(0, $nr - 1 - ABSTAND);
    $stueck = implode("\n", array_slice($zeilen, $von, $nr - 1 - $von));
    return str_contains($stueck, 'use_strict_mode');
}

/* ---- Selbstprobe --------------------------------------------------------- */

if (in_array('--selbstprobe', $argv, true)) {
    $faelle = [
        ['<?php session_start();', 1, 'nackter Aufruf'],
        ["<?php ini_set('session.use_strict_mode','1');\nsession_start();", 0, 'gehaertet'],
        ["<?php\n/* session_start() in einem Kommentar */\n", 0, 'nur im Kommentar'],
        ['<?php $s = "session_start();";', 0, 'nur in einer Zeichenkette'],
        ['<?php function session_start() {}', 0, 'eigene Definition'],
        ['<?php @session_start();', 1, 'mit @ davor'],
        ["<?php ini_set('session.use_strict_mode','1');\n" . str_repeat("// x\n", 15)
         . "session_start();", 1, 'Haertung zu weit weg (15 Zeilen)'],
        ["<?php \$x = session_started();", 0, 'aehnlicher Name'],
    ];
    $ok = 0;
    foreach ($faelle as [$code, $erwartet, $was]) {
        $zeilen = explode("\n", $code);
        $offen = 0;
        foreach (sp_aufrufe($code) as $nr) {
            if (!sp_gehaertet($zeilen, $nr)) { $offen++; }
        }
        $passt = $offen === $erwartet;
        printf("  %s  %-34s erwartet %d, gemessen %d\n",
               $passt ? 'ok  ' : 'FEHL', $was, $erwartet, $offen);
        if ($passt) { $ok++; }
    }
    printf("\nSelbstprobe: %d von %d\n", $ok, count($faelle));
    exit($ok === count($faelle) ? 0 : 1);
}

/* ---- Der Lauf ------------------------------------------------------------ */

$wurzel = dirname(__DIR__, 2) . '/server';
if (!is_dir($wurzel)) {
    fwrite(STDERR, "server/ nicht gefunden.\n");
    exit(2);
}

$dateien = 0; $aufrufe = 0; $befunde = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($wurzel));
$liste = [];
foreach ($it as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') { continue; }
    $pfad = $f->getPathname();
    if (str_contains($pfad, '/vendor/')) { continue; }
    $liste[] = $pfad;
}
sort($liste);

foreach ($liste as $pfad) {
    $quelle = (string)file_get_contents($pfad);
    $dateien++;
    if (!str_contains($quelle, 'session_start')) { continue; }
    $zeilen = explode("\n", $quelle);
    foreach (sp_aufrufe($quelle) as $nr) {
        $aufrufe++;
        if (!sp_gehaertet($zeilen, $nr)) {
            $befunde[] = substr($pfad, strlen(dirname($wurzel)) + 1) . ':' . $nr
                       . '  ' . trim($zeilen[$nr - 1]);
        }
    }
}

echo "Sitzungshaertung — `session.use_strict_mode` vor jedem `session_start()`\n";
echo str_repeat('=', 72) . "\n";
printf("  PHP-Dateien (ohne vendor/):   %5d\n", $dateien);
printf("  Echte session_start()-Aufrufe:%5d\n", $aufrufe);
printf("  Davon ohne Haertung:          %5d\n", count($befunde));
foreach ($befunde as $b) { echo "    - " . $b . "\n"; }
echo "\nBefunde: " . count($befunde) . "\n";
exit($befunde === [] ? 0 : 1);
