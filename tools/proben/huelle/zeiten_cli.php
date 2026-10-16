<?php
/* Ortszeit → Zeitpunkt von der Kommandozeile — für Werkzeuge, die nicht in
 * PHP geschrieben sind (S11-06e).
 *
 * Anlass: Nr. 43 — das Einspielwerkzeug des Referenzbestands
 * (`tools/referenzdatensatz/einspielen/einspielen.py`) verschließt seit
 * S11-06e Phasenorte und Reanimation selbst und schickt dem Formular die
 * Phasenzeiten mit, die der Server gegenhält (`ort_phasen`, S11-05b). Die
 * Zeiten müssen dafür genau so entstehen wie im Formular: über
 * `local_to_utc()` und die Zeitzone dieser Anlage. Statt die Regel in Python
 * ein zweites Mal zu schreiben, ruft es diese Datei (F-S11-43).
 *
 * Eingabe (stdin):  [["JJJJ-MM-TT", "hh:mm", Tagesversatz], …]
 * Ausgabe (stdout): [Sekunden seit 1970 (UTC) oder null, …]
 */
declare(strict_types=1);

require_once __DIR__ . '/../../../server/db.php';

$ein = json_decode((string)stream_get_contents(STDIN), true);
if (!is_array($ein)) {
    fwrite(STDERR, "zeiten_cli: erwartet [[\"JJJJ-MM-TT\", \"hh:mm\", Versatz], …]\n");
    exit(2);
}
$aus = [];
foreach ($ein as $z) {
    $utc = is_array($z) ? local_to_utc((string)($z[0] ?? ''), (string)($z[1] ?? ''), (int)($z[2] ?? 0))
                        : null;
    $aus[] = $utc === null ? null : (int)strtotime($utc . ' UTC');
}
echo json_encode($aus);
