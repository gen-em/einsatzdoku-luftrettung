<?php
declare(strict_types=1);

/**
 * Ankerprüfung — trifft jeder Verweis `hilfe.php#…` eine Überschrift des
 * Handbuchs? (P5c/AP9, E-P5c-06)
 *
 * Anlass: Nr. 188 — kein Prüfmittel misst Verweise zwischen Dokumenten
 *
 * WOFUER. Seit AP9 verweist jede Karte unter Verwaltung und Betrieb auf die
 * Stelle im Handbuch, an der das Erklärende steht. Die Sprungmarke bildet
 * `doku_marke()` aus dem Überschriftentext, und zwei gleiche Marken bekommen
 * `-2`. Wer eine Überschrift umformuliert, bricht damit still jeden Verweis
 * darauf: Der Browser springt nirgendwohin, und niemand merkt es. Die
 * Linkprobe sieht das nicht — sie prüft Parameter, keine Anker.
 *
 * WIE GEMESSEN WIRD: mit dem ECHTEN Weg. `doku_seite('handbuch')` rendert das
 * Handbuch genau so wie `hilfe.php`, und die Marken werden aus dem HTML
 * gelesen — nicht in einer zweiten Umsetzung nachgerechnet, die bei der
 * nächsten Änderung an `doku_marke()` still auseinanderläuft. Die Verweise
 * kommen aus `server/`: PHP über den Tokenizer (nur Zeichenketten und
 * HTML-Text, keine Kommentare — dort stehen Beispiele wie `#abschnitt`),
 * JavaScript mit entfernten Kommentaren.
 *
 * WAS SIE NICHT SIEHT. Einen Anker, der zur Laufzeit zusammengesetzt wird
 * (`'hilfe.php#' . $marke`) — gezählt als DYNAMISCH und nicht gemeldet;
 * heute gibt es keinen. Und ob der Verweis auf die RICHTIGE Stelle zeigt:
 * Sie prüft, dass es das Ziel gibt, nicht, dass es passt.
 *
 * Aufruf:
 *   php tools/quelltext/anker.php
 *   php tools/quelltext/anker.php --selbstprobe
 *
 * Rückgabewert: 0 = jeder Verweis trifft · 1 = Befund · 2 = Handbuch fehlt.
 */

$wurzel = dirname(__DIR__, 2);
require_once $wurzel . '/server/doku_lib.php';

/** Die Marken, die das Handbuch wirklich trägt — aus dem gerenderten HTML. */
function anker_marken(string $html): array
{
    preg_match_all('~<h[1-6] id="([^"]+)"~', $html, $m);
    return array_fill_keys($m[1], true);
}

/**
 * Die Verweise einer Datei: [[zeile, marke], …]; `null` als Marke heißt
 * dynamisch zusammengesetzt.
 */
function anker_verweise(string $pfad, string $text): array
{
    $aus = [];
    $finde = static function (string $s, int $zeile) use (&$aus): void {
        if (preg_match_all('~hilfe\.php#([A-Za-z0-9_-]*)~', $s, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as [$marke, $pos]) {
                $z = $zeile + substr_count(substr($s, 0, $pos), "\n");
                $aus[] = [$z, $marke === '' ? null : $marke];
            }
        }
    };
    if (str_ends_with($pfad, '.php')) {
        foreach (token_get_all($text) as $t) {
            if (!is_array($t)) { continue; }
            if (in_array($t[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE,
                                 T_INLINE_HTML], true)) {
                $finde($t[1], $t[2]);
            }
        }
    } else {
        $ohne = preg_replace_callback('~/\*.*?\*/|//[^\n]*~s',
            static fn(array $m): string => str_repeat("\n", substr_count($m[0], "\n")),
            $text) ?? $text;
        $finde($ohne, 1);
    }
    return $aus;
}

/** Alle Befunde über eine Liste von Dateien gegen eine Markenmenge. */
function anker_pruefen(array $dateien, array $marken): array
{
    $befunde = []; $zahl = 0; $dynamisch = 0;
    foreach ($dateien as $pfad => $text) {
        foreach (anker_verweise($pfad, $text) as [$zeile, $marke]) {
            if ($marke === null) { $dynamisch++; continue; }
            $zahl++;
            if (!isset($marken[$marke])) { $befunde[] = "$pfad:$zeile  #$marke"; }
        }
    }
    return ['zahl' => $zahl, 'dynamisch' => $dynamisch, 'befunde' => $befunde];
}

if (in_array('--selbstprobe', $argv, true)) {
    /* EIN EINGEBAUTER FEHLER, UND ZWEI FALLEN. Die Probe ist erst dann etwas
     * wert, wenn sie einen falschen Anker ROT meldet — und einen Anker im
     * Kommentar sowie einen doppelten Titel richtig behandelt. */
    $html = '<h2 id="12-4-hintergrundjobs">x</h2><h4 id="speicher">a</h4><h4 id="speicher-2">b</h4>';
    $marken = anker_marken($html);
    $faelle = [
        ['ein treffender Anker ist grün',
         ['a.php' => '<a href="hilfe.php#12-4-hintergrundjobs">x</a>'], 0],
        ['ein falscher Anker ist rot',
         ['a.php' => '<a href="hilfe.php#12-4-hintergrundjob">x</a>'], 1],
        ['der zweite gleiche Titel trägt -2',
         ['a.php' => '<?php $x = "hilfe.php#speicher-2";'], 0],
        ['ein Anker im PHP-Kommentar zählt nicht',
         ['a.php' => "<?php /* hilfe.php#gibt-es-nicht */ \$x = 1;"], 0],
        ['ein Anker im JS-Kommentar zählt nicht',
         ['a.js' => "// hilfe.php#gibt-es-nicht\nvar x = 1;"], 0],
        ['ein Anker in einer JS-Zeichenkette zählt',
         ['a.js' => "var u = 'hilfe.php#gibt-es-nicht';"], 1],
    ];
    $ok = 0;
    foreach ($faelle as [$was, $dateien, $soll]) {
        $ist = count(anker_pruefen($dateien, $marken)['befunde']);
        $gut = $ist === $soll;
        $ok += $gut ? 1 : 0;
        printf("  [%s] %s (%d Befund%s)\n", $gut ? 'ok ' : 'FEHL', $was, $ist, $ist === 1 ? '' : 'e');
    }
    printf("Selbstprobe: %d von %d\n", $ok, count($faelle));
    exit($ok === count($faelle) ? 0 : 1);
}

$seite = doku_seite('handbuch');
if ($seite === null) { fwrite(STDERR, "Handbuch nicht gefunden.\n"); exit(2); }
$marken = anker_marken($seite['html']);

$dateien = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($wurzel . '/server',
          FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    $p = $f->getPathname();
    if (!preg_match('~\.(php|js)$~', $p)) { continue; }
    if (str_contains($p, '/vendor/') || str_contains($p, '/assets/fonts/')) { continue; }
    $dateien[substr($p, strlen($wurzel) + 1)] = (string)file_get_contents($p);
}
ksort($dateien);

$e = anker_pruefen($dateien, $marken);
foreach ($e['befunde'] as $b) { echo "  FEHLT  $b\n"; }
printf("%d Verweise auf %d Marken des Handbuchs, %d ohne Ziel, %d dynamisch\n",
       $e['zahl'], count($marken), count($e['befunde']), $e['dynamisch']);
exit($e['befunde'] === [] ? 0 : 1);
