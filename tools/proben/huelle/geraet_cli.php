<?php
/* Die Gerätehilfe von der Kommandozeile — für Werkzeuge, die nicht in PHP
 * geschrieben sind.
 *
 * Anlass: Nr. 43 — das Einspielwerkzeug des Referenzbestands
 * (`tools/referenzdatensatz/einspielen/einspielen.py`) schickt seine Pakete
 * an `ingest.php`, und der nimmt nur verschlossene
 * Ortsdaten an. Statt das Umbauen in Python ein zweites Mal zu schreiben,
 * ruft es diese Datei: EINE Umsetzung, `geraet.php`.
 *
 * Eingabe (stdin):  {"sec1": "<Base64, 65 Byte>", "geraet_d": "<64 Hex>"?, "pakete": [ … ]}
 *                   `geraet_d`: der private Schlüssel des sendenden Geräts;
 *                   ohne ihn sendet das Probegerät (`gh_probe_geraet()`).
 * Ausgabe (stdout): [ … ] — dieselben Pakete in der verschlossenen Form
 */
declare(strict_types=1);

require_once __DIR__ . '/geraet.php';

$ein = json_decode((string)stream_get_contents(STDIN), true);
$sec1 = base64_decode((string)($ein['sec1'] ?? ''), true);
if (!is_array($ein) || $sec1 === false || strlen($sec1) !== 65) {
    fwrite(STDERR, "geraet_cli: erwartet {\"sec1\": Base64 von 65 Byte, \"pakete\": [...]}\n");
    exit(2);
}
if (isset($ein['geraet_d'])) {
    $d = hex2bin((string)$ein['geraet_d']);
    if ($d === false || strlen($d) !== 32) {
        fwrite(STDERR, "geraet_cli: geraet_d muss 64 Hexzeichen haben\n");
        exit(2);
    }
    gh_probe_geraet($d);
}
$aus = [];
foreach ((array)($ein['pakete'] ?? []) as $p) {
    $aus[] = gh_probe_koerper($p, $sec1);
}
echo json_encode($aus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
