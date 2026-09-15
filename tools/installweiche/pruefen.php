<?php
declare(strict_types=1);

/**
 * Die Weiche in `install.php` — traegt sie noch? (P5a/AP2, PP-1)
 *
 * WOGEGEN. `install.php` beginnt seit Web 20.5.0 mit einer Versionspruefung:
 * Auf PHP unter 8.2 zeigt sie eine Seite, die sagt, warum. Das nuetzt nur,
 * solange die Datei auf jener alten Fassung ueberhaupt noch UEBERSETZT werden
 * kann — PHP uebersetzt eine Datei vollstaendig, bevor es die erste Zeile
 * ausfuehrt. Eine einzige `match`-Anweisung irgendwo weiter unten, und die
 * Besucherin auf PHP 8.0 bekommt statt der Erklaerung einen Parse Error.
 *
 * Der Fehler waere nicht zu bemerken: Auf dem Entwicklungsrechner laeuft PHP
 * 8.4, und dort uebersetzt alles. Auffallen wuerde er auf der Installation
 * einer Fremden, die genau in diesem Moment keine Auskunft bekommt.
 *
 * WIE GEMESSEN WIRD: mit dem Tokenizer, nicht mit `grep`. Ein `grep` nach
 * „match(" trifft `preg_match(` und jeden Kommentar, der das Wort nennt —
 * beides sind keine Befunde, und eine Pruefung mit falschem Alarm wird
 * abgeschaltet. `token_get_all()` sieht dagegen genau das, was der Uebersetzer
 * sieht.
 *
 * WAS SIE NICHT SIEHT. Konstrukte, die keinen eigenen Token haben, sondern nur
 * eine andere Anordnung bekannter Tokens sind: benannte Argumente
 * (`foo(name: 1)`), Konstruktor-Eigenschaftenbefoerderung, ein nachgestelltes
 * Komma in einer Parameterliste, `new` in Initialisierern. Sie stehen unten in
 * der Liste der GRENZEN. Wer eines davon einbaut, faellt hier nicht auf — und
 * genau deshalb sagt der Kopf von `install.php` es zusaetzlich im Klartext.
 *
 * Aufruf:
 *   php tools/installweiche/pruefen.php
 *   php tools/installweiche/pruefen.php --selbstprobe
 *
 * Rueckgabewert: 0 = die Weiche traegt · 1 = Befund · 2 = Datei fehlt.
 */

/**
 * Token, die es vor PHP 8.0 beziehungsweise 8.1 nicht gab — je mit der
 * Fassung, ab der sie uebersetzt werden.
 *
 * Die Namen stehen als Zeichenketten da und nicht als Konstanten: `T_ENUM`
 * und `T_READONLY` gibt es erst ab PHP 8.1 beziehungsweise 8.2, und eine
 * Pruefung, die auf einer aelteren Fassung an einer fehlenden Konstante
 * scheitert, hat ihren Zweck verfehlt.
 */
const WEICHE_VERBOTEN = [
    'T_MATCH'                       => '8.0 (match-Ausdruck)',
    'T_NULLSAFE_OBJECT_OPERATOR'    => '8.0 (?->)',
    'T_ATTRIBUTE'                   => '8.0 (#[Attribut])',
    'T_ENUM'                        => '8.1 (enum)',
    'T_READONLY'                    => '8.2 (readonly)',
    'T_NAME_FULLY_QUALIFIED'        => null,   // nur PHP 8, aber harmlos: siehe unten
];

/**
 * Bezeichner, die als Rueckgabetyp erst ab PHP 8.0/8.1 erlaubt sind.
 *
 * Sie sind fuer den Tokenizer gewoehnliche `T_STRING` — geprueft wird deshalb
 * die Stelle: ein `:` davor und ein `{` oder `;` dahinter.
 */
const WEICHE_RUECKGABETYPEN = ['never' => '8.1', 'mixed' => '8.0'];

$wurzel = dirname(__DIR__, 2);
/* BEIDE DATEIEN, NICHT NUR EINE. Die Weiche laedt `php_mindest.php`, bevor sie
 * vergleicht — eine PHP-8-Zeile DORT waere genauso toedlich wie eine hier, und
 * sie faellt noch weniger auf, weil die Datei drei Zeilen lang ist. */
$dateien = [$wurzel . '/server/install.php', $wurzel . '/server/php_mindest.php'];
$selbst  = in_array('--selbstprobe', array_slice($argv, 1), true);

/**
 * Eine Datei pruefen.
 *
 * @return list<array{zeile:int, was:string, ab:string}>
 */
function weiche_pruefen(string $quelle): array
{
    $befunde = [];
    $tokens  = @token_get_all($quelle);
    if (!is_array($tokens)) { return $befunde; }

    /* Nur die BEDEUTUNGSTRAGENDEN Tokens: Kommentare und Leerraum fallen
     * heraus, sonst traefe jeder erklaerende Satz, der `match` nennt. */
    $folge = [];
    foreach ($tokens as $t) {
        if (is_array($t)) {
            if (in_array($t[0], [T_COMMENT, T_DOC_COMMENT, T_WHITESPACE,
                                 T_INLINE_HTML], true)) { continue; }
            $folge[] = ['name' => token_name($t[0]), 'text' => $t[1], 'zeile' => $t[2]];
        } else {
            $folge[] = ['name' => 'ZEICHEN', 'text' => $t,
                        'zeile' => $folge ? (int)end($folge)['zeile'] : 0];
        }
    }

    foreach ($folge as $i => $t) {
        $ab = WEICHE_VERBOTEN[$t['name']] ?? false;
        if ($ab !== false && $ab !== null) {
            $befunde[] = ['zeile' => (int)$t['zeile'], 'was' => $t['text'], 'ab' => (string)$ab];
            continue;
        }
        /* Rueckgabetyp: `) : never {` beziehungsweise `) : mixed {` */
        if ($t['name'] === 'T_STRING'
            && isset(WEICHE_RUECKGABETYPEN[strtolower($t['text'])])
            && ($folge[$i - 1]['text'] ?? '') === ':'
            && in_array(($folge[$i + 1]['text'] ?? ''), ['{', ';'], true)) {
            $befunde[] = ['zeile' => (int)$t['zeile'],
                          'was'   => ': ' . strtolower($t['text']),
                          'ab'    => WEICHE_RUECKGABETYPEN[strtolower($t['text'])]
                                   . ' (Rueckgabetyp)'];
        }
    }
    return $befunde;
}

/* ---- Selbstprobe --------------------------------------------------------- */

if ($selbst) {
    $faelle = [
        ['<?php $x = match (1) { default => 2 };',       true,  'match'],
        ['<?php $x = $a?->b;',                            true,  'Nullsafe'],
        ['<?php function f(): never { exit; }',           true,  'Rueckgabetyp never'],
        ['<?php enum E { case A; }',                      true,  'enum'],
        ['<?php /* match ( ?-> : never */ $x = 1;',       false, 'dieselben Woerter im Kommentar'],
        ['<?php $x = preg_match("/a/", "a");',            false, 'preg_match'],
        ['<?php $m = ["match" => 1]; $y = $m["match"];',  false, 'match als Zeichenkette'],
        ['<?php function f(): string { return "a"; }',    false, 'gewoehnlicher Rueckgabetyp'],
    ];
    $ok = 0; $offen = 0;
    echo "Selbstprobe — trifft die Pruefung das Richtige und nur das?\n\n";
    foreach ($faelle as [$quelle, $erwartet, $name]) {
        $traf = weiche_pruefen($quelle) !== [];
        $gut  = $traf === $erwartet;
        if ($gut) { $ok++; } else { $offen++; }
        printf("  [%s] %-34s erwartet %s, gemessen %s\n", $gut ? 'ok ' : 'FEHL', $name,
               $erwartet ? 'Befund ' : 'sauber ', $traf ? 'Befund' : 'sauber');
    }
    printf("\n  erfuellt: %d · offen: %d\n", $ok, $offen);
    exit($offen === 0 ? 0 : 1);
}

/* ---- Der Lauf ------------------------------------------------------------ */

$zeilenGesamt = 0;
$befunde      = [];
echo "Die Weiche in server/install.php\n\n";
foreach ($dateien as $datei) {
    if (!is_file($datei)) {
        fwrite(STDERR, "Datei fehlt: $datei\n");
        exit(2);
    }
    $quelle = (string)file_get_contents($datei);
    $zeilen = substr_count($quelle, "\n") + 1;
    $zeilenGesamt += $zeilen;
    $kurz = basename($datei);
    foreach (weiche_pruefen($quelle) as $b) {
        $befunde[] = $b + ['datei' => $kurz];
    }
    printf("  %-34s %d Zeilen\n", $kurz . ':', $zeilen);
}
printf("  %-34s %d\n", 'Token-Arten in der Sperrliste:',
       count(array_filter(WEICHE_VERBOTEN, static fn($v) => $v !== null))
       + count(WEICHE_RUECKGABETYPEN));
printf("  %-34s %d\n", 'Befunde:', count($befunde));
echo "\n";

foreach ($befunde as $b) {
    echo '  ' . $b['datei'] . ', Zeile ' . $b['zeile'] . ': „' . $b['was']
       . '" — gibt es erst ab PHP ' . $b['ab'] . "\n";
}
if ($befunde) {
    echo "\n  Damit uebersetzt install.php auf einer alten Fassung nicht mehr, und\n"
       . "  die Versionsmeldung wird zum Parse Error. Bitte anders schreiben.\n";
}

echo "\nGRENZEN dieser Pruefung — was sie NICHT sieht:\n"
   . "  · benannte Argumente         foo(name: 1)\n"
   . "  · Eigenschaftenbefoerderung  function __construct(private int \$a)\n"
   . "  · nachgestelltes Komma in einer Parameterliste\n"
   . "  · `new` in Initialisierern\n"
   . "Alle vier sind gewoehnliche Tokens in ungewohnter Anordnung. Der Kopf von\n"
   . "install.php sagt die Regel deshalb zusaetzlich im Klartext.\n";

exit($befunde ? 1 : 0);
