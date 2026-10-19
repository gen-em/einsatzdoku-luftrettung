<?php
/* Die Gerätehilfe von der Kommandozeile — für Werkzeuge, die nicht in PHP
 * geschrieben sind (S11-04a).
 *
 * Anlass: Nr. 43 — das Einspielwerkzeug des Referenzbestands
 * (`tools/referenzdatensatz/einspielen/einspielen.py`) schickt seine Pakete
 * an `ingest.php`, und der nimmt seit Web 22 nur noch verschlossene
 * Ortsdaten an. Statt das Umbauen in Python ein zweites Mal zu schreiben,
 * ruft es diese Datei: EINE Umsetzung, `geraet.php`.
 *
 * Eingabe (stdin):  {"sec1": "<Base64, 65 Byte>", "pakete": [ … ]}
 * Ausgabe (stdout): [ … ] — dieselben Pakete in der Form ab Web 22
 */
declare(strict_types=1);

require_once __DIR__ . '/geraet.php';

$ein = json_decode((string)stream_get_contents(STDIN), true);
$sec1 = base64_decode((string)($ein['sec1'] ?? ''), true);
if (!is_array($ein) || $sec1 === false || strlen($sec1) !== 65) {
    fwrite(STDERR, "geraet_cli: erwartet {\"sec1\": Base64 von 65 Byte, \"pakete\": [...]}\n");
    exit(2);
}
$aus = [];
foreach ((array)($ein['pakete'] ?? []) as $p) {
    $aus[] = gh_probe_koerper($p, $sec1);
}
echo json_encode($aus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
