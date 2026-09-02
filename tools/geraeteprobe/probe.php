<?php
declare(strict_types=1);
/**
 * Geräteprobe — hält `geraet_block_lesen()` gegen das, was wirklich ankommt.
 *
 *     php tools/geraeteprobe/probe.php
 *
 * Rückgabewert: 0 = alle Erwartungen erfüllt · 1 = mindestens eine nicht.
 *
 * ## Wozu
 *
 * Der Block `geraet` einer Kopplung (JSON-Vertrag 1a, R42) ist der einzige
 * Teil von S6 mit echter Logik, und er hat drei Eigenschaften, die zusammen
 * unangenehm sind:
 *
 * 1. **Er ist freiwillig.** Fehlt er, ist er halb oder ist er Unsinn, muss die
 *    Kopplung trotzdem gelingen. Am anderen Ende steht jemand, der koppeln
 *    will — ein 500er wegen einer Statistikangabe wäre absurd.
 * 2. **Er kommt in zwei Formen.** Die Garmin-Uhr sendet eine Teilenummer, das
 *    Handy Hersteller und Modell (E-S4-28). Beide fallen auf dieselben drei
 *    Spalten.
 * 3. **Er ist eine Selbstauskunft.** Was ankommt, ist nicht geprüft — es kommt
 *    von einem Gerät, das sich beim Server erst noch vorstellt.
 *
 * Im Browser lässt sich das nicht prüfen: Es gibt keine Oberfläche dafür, und
 * eine echte Kopplung braucht eine Uhr in der Hand. Diese Probe ersetzt sie
 * nicht — sie prüft, was ohne Gerät prüfbar ist, und benennt am Ende, was
 * offen bleibt.
 *
 * ## Was diese Probe NICHT prüft
 *
 * - Ob `pair.php` das Ergebnis wirklich in die Spalten schreibt. Das braucht
 *   eine Datenbank; der Weg dafür steht in `docs/konzepte/` beim jeweiligen
 *   Prüfdokument bzw. im Runbook (`docs/Technik.md`, Abschnitt 7).
 * - Ob die Modelltabelle richtig ist. Sie ist erzeugt
 *   (`tools/geraetemodelle/`); diese Probe setzt eine EIGENE, kleine Tabelle,
 *   damit sie nicht vom ausgelieferten Bestand abhängt.
 * - Ob eine echte Uhr sendet, was der Vertrag sagt. Dafür gibt es nur die Uhr.
 */

/* Die Modelltabelle der Probe. Drei echte Teilenummern aus
 * `docs/Geraete-Eingabe.md` — sie stehen dort mit gemessenen Displaymaßen und
 * sind damit belegt, nicht erfunden. Der vierte Eintrag ist ein ERFUNDENER
 * Radcomputer: Er prüft den einen Fall, in dem die Tabelle die Selbstauskunft
 * schlägt (die Uhr-App sendet `art` fest als "uhr" und kann Uhr und Edge nicht
 * unterscheiden). Ein echter Edge stünde hier genauso; die Teilenummer eines
 * solchen ist nur nirgends im Repositorium belegt, und eine erfundene als
 * belegt auszugeben wäre schlechter, als sie zu benennen. */
const GERAETE_MODELLE = [
    '006-B4261-00' => ['Venu 3S',            'uhr'],
    '006-B3290-00' => ['fēnix 6 Pro',        'uhr'],
    '006-B3113-00' => ['Forerunner 945',     'uhr'],
    '006-XXXXX-99' => ['Edge (erfunden)',    'sonstiges'],
];

require_once __DIR__ . '/../../server/geraete_lib.php';

$geprueft = 0;
$fehler   = [];

function pruefe(string $titel, mixed $block, array $erwartet): void
{
    global $geprueft, $fehler;
    $geprueft++;
    $ist = geraet_block_lesen($block);
    foreach (['art', 'modell', 'teil'] as $k) {
        if (!array_key_exists($k, $ist)) {
            $fehler[] = "$titel: Schlüssel '$k' fehlt in der Rückgabe";
            return;
        }
    }
    if ($ist !== $erwartet) {
        $fehler[] = sprintf("%s\n      erwartet: %s\n      bekommen: %s",
                            $titel, kurz($erwartet), kurz($ist));
    }
}

function kurz(array $a): string
{
    $t = [];
    foreach ($a as $k => $v) { $t[] = $k . '=' . ($v === null ? 'null' : "'$v'"); }
    return implode(', ', $t);
}

/* ---- 1. Die Uhr-Form (JSON-Vertrag 1a) ---------------------------------- */

pruefe('Uhr, aufgelöst',
    ['art' => 'uhr', 'teil' => '006-B4261-00', 'br' => 390, 'ho' => 390,
     'touch' => true, 'fw' => 1140, 'ciq' => '5.2.0', 'app' => '1.9.0'],
    ['art' => 'uhr', 'modell' => 'Venu 3S', 'teil' => '006-B4261-00']);

pruefe('Uhr, Teilenummer unbekannt — Rohangabe bleibt, Modell leer',
    ['art' => 'uhr', 'teil' => '006-B9999-00'],
    ['art' => 'uhr', 'modell' => null, 'teil' => '006-B9999-00']);

pruefe('Uhr, Kleinschreibung der Teilenummer wird aufgelöst',
    ['art' => 'uhr', 'teil' => '006-b4261-00'],
    ['art' => 'uhr', 'modell' => 'Venu 3S', 'teil' => '006-b4261-00']);

pruefe('Radcomputer nennt sich "uhr" — die Tabelle schlägt die Selbstauskunft',
    ['art' => 'uhr', 'teil' => '006-XXXXX-99'],
    ['art' => 'sonstiges', 'modell' => 'Edge (erfunden)', 'teil' => '006-XXXXX-99']);

pruefe('Uhr-Fassung vor 1.9.0: kein Block',
    null,
    ['art' => null, 'modell' => null, 'teil' => null]);

/* ---- 2. Die Handy-Form (E-S4-28) ---------------------------------------- */

pruefe('Handy nach E-S4-28',
    ['art' => 'handy', 'teil' => null, 'hersteller' => 'Google', 'modell' => 'Pixel 8',
     'br' => 1080, 'ho' => 2400, 'touch' => true, 'fw' => '14', 'sdk' => 34,
     'app' => '0.7.7'],
    ['art' => 'handy', 'modell' => 'Google Pixel 8', 'teil' => 'Google Pixel 8']);

pruefe('Handy: Hersteller steckt schon im Modellnamen — nicht doppeln',
    ['art' => 'handy', 'teil' => null, 'hersteller' => 'Xiaomi', 'modell' => 'Xiaomi 14'],
    ['art' => 'handy', 'modell' => 'Xiaomi 14', 'teil' => 'Xiaomi 14']);

pruefe('Handy: Hersteller anders geschrieben als im Modell — trotzdem erkannt',
    ['art' => 'handy', 'teil' => null, 'hersteller' => 'samsung', 'modell' => 'SAMSUNG SM-S911B'],
    ['art' => 'handy', 'modell' => 'SAMSUNG SM-S911B', 'teil' => 'SAMSUNG SM-S911B']);

pruefe('Handy: nur Hersteller lesbar',
    ['art' => 'handy', 'teil' => null, 'hersteller' => 'Google', 'modell' => null],
    ['art' => 'handy', 'modell' => 'Google', 'teil' => 'Google']);

pruefe('Handy: gar nichts lesbar — Art bleibt, Rest leer',
    ['art' => 'handy', 'teil' => null, 'hersteller' => null, 'modell' => null],
    ['art' => 'handy', 'modell' => null, 'teil' => null]);

/* ---- 3. Was NICHT ankommen darf ---------------------------------------- */

pruefe('Erfundene Geräteart wird verworfen, nicht gespeichert',
    ['art' => 'kühlschrank', 'teil' => '006-B4261-00'],
    ['art' => 'uhr', 'modell' => 'Venu 3S', 'teil' => '006-B4261-00']);

pruefe('Erfundene Geräteart, unbekannter Teil — Art bleibt leer',
    ['art' => 'kühlschrank', 'teil' => '006-B9999-00'],
    ['art' => null, 'modell' => null, 'teil' => '006-B9999-00']);

pruefe('Grossschreibung der Art wird angeglichen',
    ['art' => 'HANDY', 'hersteller' => 'Google', 'modell' => 'Pixel 8'],
    ['art' => 'handy', 'modell' => 'Google Pixel 8', 'teil' => 'Google Pixel 8']);

pruefe('Block ist eine Zeichenkette statt eines Objekts',
    'ich bin ein Gerät',
    ['art' => null, 'modell' => null, 'teil' => null]);

pruefe('Block ist eine Zahl',
    42,
    ['art' => null, 'modell' => null, 'teil' => null]);

pruefe('Verschachtelte Werte statt Zeichenketten',
    ['art' => ['uhr'], 'teil' => ['006-B4261-00']],
    ['art' => null, 'modell' => null, 'teil' => null]);

pruefe('true statt einer Zeichenkette',
    ['art' => true, 'teil' => true],
    ['art' => null, 'modell' => null, 'teil' => null]);

pruefe('Leere Zeichenketten sind wie nicht gesendet',
    ['art' => '', 'teil' => '', 'hersteller' => '  ', 'modell' => ''],
    ['art' => null, 'modell' => null, 'teil' => null]);

pruefe('Zeilenumbruch im Modellnamen zerreisst keine Listenzeile',
    ['art' => 'handy', 'hersteller' => "Goo\ngle", 'modell' => "Pi\txel\r\n8"],
    ['art' => 'handy', 'modell' => 'Goo gle Pi xel 8', 'teil' => 'Goo gle Pi xel 8']);

pruefe('Ein zu langer Modellname wird auf die Spaltenbreite gekürzt',
    ['art' => 'handy', 'hersteller' => null, 'modell' => str_repeat('A', 200)],
    ['art' => 'handy', 'modell' => str_repeat('A', 64), 'teil' => str_repeat('A', 64)]);

pruefe('Umlaute überleben die Kürzung (mb_substr, nicht substr)',
    ['art' => 'handy', 'hersteller' => null, 'modell' => str_repeat('ä', 100)],
    ['art' => 'handy', 'modell' => str_repeat('ä', 64), 'teil' => str_repeat('ä', 64)]);

pruefe('Beide Formen zugleich: die Teilenummer gewinnt, kein Modell aus dem Handy-Zweig',
    ['art' => 'uhr', 'teil' => '006-B9999-00', 'hersteller' => 'Google', 'modell' => 'Pixel 8'],
    ['art' => 'uhr', 'modell' => null, 'teil' => '006-B9999-00']);

pruefe('Zahl als Teilenummer wird zur Zeichenkette',
    ['art' => 'uhr', 'teil' => 123456],
    ['art' => 'uhr', 'modell' => null, 'teil' => '123456']);

/* ---- 4. Die Beschriftungen der Geräteliste ------------------------------ */

$beschriftungen = [
    ['uhr',       'Venu 3S',       '006-B4261-00', 'Uhr · Venu 3S'],
    ['handy',     'Google Pixel 8', 'Google Pixel 8', 'Handy · Google Pixel 8'],
    ['uhr',       null,            '006-B9999-00', 'Uhr · 006-B9999-00'],
    ['uhr',       null,            null,           'Uhr · Modell unbekannt'],
    ['sonstiges', null,            null,           'Sonstiges · Modell unbekannt'],
    [null,        null,            '006-B9999-00', '006-B9999-00'],
    [null,        null,            null,           'Gerät unbekannt'],
];
foreach ($beschriftungen as [$art, $modell, $teil, $erwartet]) {
    $geprueft++;
    $ist = geraet_bezeichnung($art, $modell, $teil);
    if ($ist !== $erwartet) {
        $fehler[] = sprintf("Beschriftung (%s/%s/%s)\n      erwartet: '%s'\n      bekommen: '%s'",
            $art ?? 'null', $modell ?? 'null', $teil ?? 'null', $erwartet, $ist);
    }
}

/* ---- 5. Der Vorgabename beim Koppeln ------------------------------------ */

$namen = [
    ['uhr',       'Uhr'],
    ['handy',     'Handy'],
    ['sonstiges', 'Gerät'],
    // Kein Block heisst: Uhr-Fassung vor 1.9.0 — etwas anderes konnte damals
    // nicht koppeln. Deshalb "Uhr" und nicht "Gerät".
    [null,        'Uhr'],
];
foreach ($namen as [$art, $erwartet]) {
    $geprueft++;
    $ist = geraet_vorgabename($art);
    if ($ist !== $erwartet) {
        $fehler[] = sprintf("Vorgabename (%s): erwartet '%s', bekommen '%s'",
                            $art ?? 'null', $erwartet, $ist);
    }
}

/* ---- 6. Die gekürzte Gerätekennung -------------------------------------- */

$kennungen = [
    // Die heutige Form: "dev-" + 32 Hexziffern = 36 Zeichen.
    ['dev-3edc01ce807de66ee8da916a0a15905d', 'dev-3edc…5d'],
    // Altbestand vor Web 4.5.1: 4 Zufallsbytes = 12 Zeichen, bleibt ganz.
    ['dev-ca7774d5',                          'dev-ca7774d5'],
    // Genau an der Grenze — 12 Zeichen bleiben, 13 werden gekürzt.
    ['dev-12345678',                          'dev-12345678'],
    ['dev-123456789',                         'dev-1234…89'],
    // Das virtuelle Gerät ist kurz und taucht in keiner Liste auf; die
    // Funktion soll es trotzdem nicht entstellen.
    ['manual-2',                              'manual-2'],
];
foreach ($kennungen as [$roh, $erwartet]) {
    $geprueft++;
    $ist = geraet_kennung_kurz($roh);
    if ($ist !== $erwartet) {
        $fehler[] = sprintf("Kennung '%s': erwartet '%s', bekommen '%s'",
                            $roh, $erwartet, $ist);
    }
}

/* ---- Ergebnis ----------------------------------------------------------- */

echo "Geräteprobe — Block `geraet` der Kopplung (JSON-Vertrag 1a, R42)\n\n";
echo "  $geprueft Erwartungen geprüft\n";
if ($fehler === []) {
    echo "  0 Abweichungen\n\n";
    echo "NICHT geprüft (braucht Datenbank bzw. echtes Gerät):\n";
    echo "  · dass pair.php das Ergebnis in die drei Spalten schreibt\n";
    echo "  · dass die erzeugte Modelltabelle richtig ist\n";
    echo "  · dass eine echte Uhr sendet, was der Vertrag sagt\n";
    exit(0);
}
echo '  ' . count($fehler) . " Abweichungen:\n\n";
foreach ($fehler as $f) { echo "  · $f\n"; }
exit(1);
