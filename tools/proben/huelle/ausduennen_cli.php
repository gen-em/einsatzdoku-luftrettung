<?php
/* Ausdünnen von der Kommandozeile — die PHP-Referenz für Werkzeuge, die nicht
 * in PHP geschrieben sind.
 *
 * Anlass: Nr. 500 — das Einspielwerkzeug des Referenzbestands bringt Spuren
 * über den regulären Wartungslauf (`api/wartungslauf.php`) auf Stufe 3 und
 * muss dafür so ausdünnen wie der Browser (`EdWartungslauf.ausduennen()`).
 * Die Regel steht in `spur_lib.php` (`spur_ausduennen()`, dazu die Prüfung
 * `spur_ausduennung_pruefen()`); eine dritte Fassung in Python gibt es mit
 * Absicht nicht (Konzept RD, RD-03).
 *
 * Eingabe (stdin):  {"punkte": [[seq, lat, lon, ele|null, ts], …], "zeiten": [ts, …]}
 * Ausgabe (stdout): {"behalten": [Index, …]} — oder Rückgabe 1 mit der
 *                   Abweichung, die die Prüfung meldet.
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../server/spur_lib.php';

$ein = json_decode((string)stream_get_contents(STDIN), true);
if (!is_array($ein) || !is_array($ein['punkte'] ?? null)) {
    fwrite(STDERR, "ausduennen_cli: erwartet {\"punkte\": [[seq, lat, lon, ele, ts], …], \"zeiten\": […]}\n");
    exit(2);
}
$punkte = array_map(static fn($p) => [(int)$p[0], (float)$p[1], (float)$p[2],
                                      $p[3] === null ? null : (float)$p[3], (int)$p[4]], $ein['punkte']);
$zeiten = array_map('intval', (array)($ein['zeiten'] ?? []));
$behalten = spur_ausduennen($punkte, $zeiten);
$fehler = spur_ausduennung_pruefen($punkte, $behalten, $zeiten);
if ($fehler !== null) {
    fwrite(STDERR, "ausduennen_cli: Prüfung der Ausdünnung: $fehler\n");
    exit(1);
}
echo json_encode(['behalten' => $behalten]);
