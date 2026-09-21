<?php
declare(strict_types=1);

/**
 * SITZUNGSHAERTUNG — steht die Haertung vor dem EINEN `session_start()`?
 * ===========================================================================
 *
 * Aufruf:  php tools/sitzungshaertung/pruefen.php [--selbstprobe]
 * Rückgabe: 0 = keine Befunde · 1 = Befunde · 2 = die Probe kam nicht los
 *
 * WOGEGEN, ERSTER TEIL. Bis Web 20.9.1 stand `ini_set('session.use_strict_mode',
 * '1')` an genau zwei Stellen — `install.php` und `wiederherstellen.php` —,
 * also ausgerechnet den beiden Wegen, die KEINE Anmeldesitzung tragen. Auf den
 * fuenf Wegen, die eine tragen, fehlte sie. Ohne sie uebernimmt PHP eine
 * Sitzungskennung, die der Browser mitbringt, auch wenn es sie nie vergeben
 * hat — Session-Fixation, und der Schutz hing an der `php.ini` des Hosters.
 *
 * WOGEGEN, ZWEITER TEIL (Schritt 15 AP2, E-ZE-13). Seit Web 20.27.0 gibt es
 * in `server/` genau EINEN `session_start()`, und er steht in
 * `sitzung_lib.php` in `sitzung_starten()`. Vorher waren es neun in vier
 * Fassungen, und der Unterschied zwischen ihnen stand nirgends. Diese Probe
 * prueft deshalb nicht mehr nur „ist jeder Aufruf gehaertet", sondern
 * schaerfer:
 *
 *   (a) es gibt GENAU EINEN Aufruf,
 *   (b) er steht in `sitzung_lib.php`,
 *   (c) die Haertung steht in den ABSTAND Zeilen davor,
 *   (d) jeder weitere Aufruf irgendwo ist ein Befund — auch ein gehaerteter.
 *
 * (d) ist der eigentliche Zugewinn. Ein neuer Sitzungsstart, der die Haertung
 * mitbringt, waere unter der alten Probe grün gewesen und haette trotzdem die
 * Cookie-Parameter der vier Arten neu erfinden muessen. Genau so sind die
 * neun entstanden.
 *
 * MIT DEM TOKENIZER, NICHT MIT `grep`. Ein `grep` ueber `session_start`
 * findet jede Erwaehnung in jedem Kommentar — die erste Fassung dieser
 * Pruefung meldete deshalb zwei Befunde, und beide waren Kommentarzeilen, die
 * das Werkzeug selbst beschrieben.
 *
 * WAS SIE NICHT MESSEN KANN:
 *
 * - **Ob die Einstellung wirkt.** `ini_set()` kann scheitern (`session.*`
 *   laesst sich nach `session_start()` nicht mehr setzen, und manche Hoster
 *   sperren einzelne Direktiven). Das misst nur eine laufende Installation:
 *   eine vorgegebene Sitzungskennung anbieten und nachsehen, ob eine andere
 *   zurueckkommt. Pruefpunkt im Pruefdokument.
 * - **Ob `sitzung_starten()` richtig gerufen wird.** Dass es nur einen
 *   Aufruf gibt, heisst nicht, dass jede Seite die richtige ART waehlt. Das
 *   ist Lesearbeit; die Tabelle der vier Arten steht in `sitzung_lib.php`.
 * - **Die Reihenfolge innerhalb der zwoelf Zeilen.** Steht die Zeile in
 *   einem `if`, das nie zutrifft, zaehlt sie hier trotzdem.
 * - **`session_start()` in einer Bibliothek Dritter.** `vendor/` ist
 *   ausgenommen; `phpseclib3/Crypt/Random.php` startet eine eigene Sitzung
 *   und wird nicht von uns gepflegt.
 */

const ABSTAND = 12;   // so viele Zeilen davor darf die Haertung stehen
const HEIMAT  = 'server/sitzung_lib.php';   // wo der eine Aufruf stehen darf

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

/** Steht die Haertung in den ABSTAND Zeilen davor? */
function sp_gehaertet(array $zeilen, int $nr): bool
{
    $von = max(0, $nr - 1 - ABSTAND);
    $stueck = implode("\n", array_slice($zeilen, $von, $nr - 1 - $von));
    return str_contains($stueck, 'use_strict_mode');
}

/* ---- Selbstprobe --------------------------------------------------------- */

if (in_array('--selbstprobe', $argv, true)) {
    /* Je Fall: Quelle, Pfad, erwartete Befundzahl, Beschreibung. Der Pfad
     * entscheidet mit, seit (b) und (d) geprueft werden. */
    $faelle = [
        ["<?php ini_set('session.use_strict_mode','1');\nsession_start();",
         HEIMAT, 0, 'gehaertet, und zwar in sitzung_lib.php'],
        ['<?php session_start();',
         HEIMAT, 1, 'in sitzung_lib.php, aber ungehaertet'],
        ["<?php ini_set('session.use_strict_mode','1');\nsession_start();",
         'server/login.php', 1, 'gehaertet, aber am falschen Ort (d)'],
        ['<?php session_start();',
         'server/login.php', 1, 'ungehaertet und am falschen Ort'],
        ["<?php\n/* session_start() in einem Kommentar */\n",
         'server/login.php', 0, 'nur im Kommentar'],
        ['<?php $s = "session_start();";',
         'server/login.php', 0, 'nur in einer Zeichenkette'],
        ['<?php function session_start() {}',
         'server/login.php', 0, 'eigene Definition'],
        ["<?php ini_set('session.use_strict_mode','1');\n@session_start();",
         HEIMAT, 0, 'mit @ davor, gehaertet'],
        ["<?php ini_set('session.use_strict_mode','1');\n" . str_repeat("// x\n", 15) . "session_start();",
         HEIMAT, 1, 'Haertung zu weit weg (15 Zeilen)'],
        ["<?php \$x = session_started();",
         HEIMAT, 0, 'aehnlicher Name'],
        ["<?php ini_set('session.use_strict_mode','1');\nsession_start();\nsession_start();",
         HEIMAT, 1, 'zwei Aufrufe in der Heimat — der zweite ist ein Befund'],
    ];
    $ok = 0;
    foreach ($faelle as [$code, $pfad, $erwartet, $was]) {
        $zeilen = explode("\n", $code);
        $gesehen = 0; $offen = 0;
        foreach (sp_aufrufe($code) as $nr) {
            $gesehen++;
            if ($gesehen > 1 || $pfad !== HEIMAT || !sp_gehaertet($zeilen, $nr)) { $offen++; }
        }
        $passt = $offen === $erwartet;
        printf("  %s  %-48s erwartet %d, gemessen %d\n",
               $passt ? 'ok  ' : 'FEHL', $was, $erwartet, $offen);
        if ($passt) { $ok++; }
    }
    /* Der Fall, den die Faelle oben nicht abdecken: GAR KEIN Aufruf. Dann
     * gibt es nichts zu haerten — und genau das ist ein Befund, weil die
     * Anwendung ohne Sitzung nicht laeuft. */
    $keiner = sp_aufrufe('<?php echo 1;') === [];
    printf("  %s  %-48s erwartet %s, gemessen %s\n",
           $keiner ? 'ok  ' : 'FEHL', 'kein Aufruf wird als solcher erkannt', 'ja', $keiner ? 'ja' : 'nein');
    if ($keiner) { $ok++; }

    $n = count($faelle) + 1;
    printf("\nSelbstprobe: %d von %d\n", $ok, $n);
    exit($ok === $n ? 0 : 1);
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
    /* `config.php` gehoert nicht zum Repositorium und liegt nur dort, wo eine
     * Anlage eingerichtet ist. Waere sie dabei, haengt die gemeldete
     * Dateizahl davon ab — und zwei Laeufe waeren nicht vergleichbar.
     * Dieselbe Ausnahme wie in `tools/wortliste/` und `tools/zaehlung/`. */
    if ($pfad === $wurzel . '/config.php') { continue; }
    $liste[] = $pfad;
}
sort($liste);

foreach ($liste as $pfad) {
    $quelle = (string)file_get_contents($pfad);
    $dateien++;
    if (!str_contains($quelle, 'session_start')) { continue; }
    $zeilen = explode("\n", $quelle);
    $rel = substr($pfad, strlen(dirname($wurzel)) + 1);
    foreach (sp_aufrufe($quelle) as $nr) {
        $aufrufe++;
        $stelle = $rel . ':' . $nr . '  ' . trim($zeilen[$nr - 1]);
        if ($aufrufe > 1) {
            $befunde[] = $stelle . '   [zweiter Aufruf — es darf nur einen geben]';
        } elseif ($rel !== HEIMAT) {
            $befunde[] = $stelle . '   [falscher Ort — der Aufruf gehoert in ' . HEIMAT . ']';
        } elseif (!sp_gehaertet($zeilen, $nr)) {
            $befunde[] = $stelle . '   [ohne Haertung in den ' . ABSTAND . ' Zeilen davor]';
        }
    }
}

if ($aufrufe === 0) {
    $befunde[] = 'KEIN `session_start()` in server/ — die Anwendung kann keine '
               . 'Sitzung starten. Erwartet wird genau einer, in ' . HEIMAT . '.';
}

echo "Sitzungshaertung — ein `session_start()`, gehaertet, in " . HEIMAT . "\n";
echo str_repeat('=', 72) . "\n";
printf("  PHP-Dateien (ohne vendor/):   %5d\n", $dateien);
printf("  Echte session_start()-Aufrufe:%5d   (erwartet: 1)\n", $aufrufe);
printf("  Befunde:                      %5d\n", count($befunde));
foreach ($befunde as $b) { echo "    - " . $b . "\n"; }
echo "\nBefunde: " . count($befunde) . "\n";
exit($befunde === [] ? 0 : 1);
