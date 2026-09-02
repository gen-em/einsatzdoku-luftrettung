<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/apk_lib.php';

/**
 * Die APK-Datei ausliefern (S4/A1, E-S4-16).
 *
 * GET apk.php?d=nadoku-1.0.0.apk
 *
 * NUR ANGEMELDET. Die Datei ist keine Verschlusssache — sie ist die App, die
 * jede NutzerIn ohnehin bekommt —, aber sie ist auch nichts, was ein
 * beliebiger Abruf aus dem Netz herunterladen soll: Wer sie hat, kennt die
 * Signatur, mit der sich eine spaetere Fassung ausgeben liesse, und die
 * Bandbreite eines geteilten Webspaces ist endlich. `auth_guard.php` bringt
 * die Schranke mit.
 *
 * NEBEN DEN SEITEN UND NICHT UNTER `api/`, aus demselben Grund wie `gpx.php`:
 * `ist_api_aufruf()` entscheidet am Pfad. Ein `<a href>`, den jemand
 * anklickt, bekaeme dort nach einer Mittagspause `{"error":"session_ende"}`
 * im Browserfenster statt der Anmeldeseite.
 *
 * GET UND OHNE CSRF (M3-11): Was nichts aendert, beantwortet auch kein POST.
 *
 * DER NAME WIRD NICHT GEPRUEFT, SONDERN GESUCHT. `apk_liste()` liest, was im
 * Ordner liegt; der Parameter waehlt daraus aus. Ein Pfad, den der Aufrufer
 * zusammensetzt, kommt damit nie an `fopen()` — auch keiner mit `..`, keiner
 * mit einem Nullbyte und keiner mit einem Zeilenumbruch fuer die
 * Content-Disposition-Kopfzeile. Der Unterschied zu „gefaehrliche Zeichen
 * entfernen" ist, dass hier nichts vergessen werden kann.
 */

$gesucht = (string)($_GET['d'] ?? '');
$treffer = null;
foreach (apk_liste() as $e) {
    if ($e['datei'] === $gesucht) { $treffer = $e; break; }
}

if ($treffer === null) {
    http_response_code(404);
    require_once __DIR__ . '/ui.php';
    ui_geruest_start(['aktiv' => 'einstellungen']);
    ui_titelzeile(['titel' => 'Datei nicht gefunden',
                   'zurueck' => ['text' => 'Geräte', 'href' => 'einstellungen.php?t=geraete']]);
    echo ui_meldung_markup('warn',
        'Diese Datei liegt nicht (mehr) auf dem Server. Auf dem Geräte-Reiter '
        . 'steht, was da ist.');
    ui_geruest_ende();
    ui_seite_ende();
    exit;
}

$pfad = apk_ordner() . '/' . $treffer['datei'];

/* `application/vnd.android.package-archive` ist der Typ, den Android
 * erwartet. `X-Content-Type-Options: nosniff` steht daneben, damit kein
 * Browser auf die Idee kommt, die Datei fuer etwas anderes zu halten und sie
 * darzustellen statt zu speichern. */
header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="' . $treffer['datei'] . '"');
header('Content-Length: ' . $treffer['groesse']);
header('X-Content-Type-Options: nosniff');
/* `no-store` waere hier falsch: Ein APK ist unveraenderlich, sobald es liegt
 * — es traegt seine Fassung im Namen. Ein Zwischenspeicher spart bei 7 MB
 * spuerbar, und ein Austausch bekommt einen neuen Namen. */
header('Cache-Control: private, max-age=86400');

/* Bei mehreren MB nicht ueber `file_get_contents()`: Das haelt die ganze
 * Datei im Speicher, und das Budget ist 64 MB (Z3). */
if (ob_get_level() > 0) { ob_end_clean(); }
readfile($pfad);
