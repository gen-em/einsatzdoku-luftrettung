<?php
declare(strict_types=1);

/**
 * Schemalauf fuer die GPX-Probe (`probe.mjs`, S11-05d).
 *
 * Anlass: Nr. 43 — seit S11-05d baut der Browser GPX; das amtliche Schema
 * prueft aber nur libxml, also PHP. Bis Web 22.2.0 stand dieser Lauf in
 * `probe.php` selbst.
 *
 * Aufruf:  php schema.php gpx11.xsd datei1.gpx [datei2.gpx …]
 * Ausgabe: JSON {datei: [Fehlermeldungen]} — leere Liste = gueltig.
 */
$xsd = $argv[1] ?? '';
$aus = [];
foreach (array_slice($argv, 2) as $datei) {
    $vorher = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $f = [];
    if (!$doc->loadXML((string)file_get_contents($datei))) { $f[] = 'nicht wohlgeformt'; }
    elseif (!$doc->schemaValidate($xsd)) {
        foreach (libxml_get_errors() as $e) { $f[] = trim($e->message); }
        if (!$f) { $f[] = 'ungueltig ohne Meldung'; }
    }
    libxml_clear_errors();
    libxml_use_internal_errors($vorher);
    $aus[basename($datei)] = $f;
}
echo json_encode($aus, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
