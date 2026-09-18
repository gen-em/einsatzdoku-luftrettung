<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth_guard.php';        // liefert $userId
require_once __DIR__ . '/../einstieg_lib.php';

/**
 * api/rueckfrage.php — die Antwort auf die Konto-Rueckfrage (P5b/AP9,
 * E-P5b-09, -19).
 *
 * ===========================================================================
 * ZWEI ANTWORTEN, UND DIE DRITTE GIBT ES NICHT
 * ===========================================================================
 *
 *   `beantwortet`  Die Frage ist fuer diese Runde erledigt — naechste Runde,
 *                  Zaehler zurueck. Gilt fuer BEIDE Wege: „Ja, liegt sicher"
 *                  und „Nein" mit anschliessender Erneuerung. Wer gerade
 *                  einen neuen Schluessel notiert hat, hat die Frage
 *                  gruendlicher beantwortet als jeder Ja-Klick.
 *   `spaeter`      Sieben Tage, hoechstens dreimal je Runde.
 *   `blatt_spaeter` Die BETREIBER-Rueckfrage (Schluesselblatt) wird heute
 *                  nicht beantwortet. Auch das schreibt nichts in die
 *                  Datenbank — warum nicht sieben Tage, steht im Kopf von
 *                  `blatt_dialog.php`.
 *   `weggeklickt`  Esc oder Klick daneben, in Abschnitt 1. Schreibt NICHTS
 *                  in die Datenbank — nur ein Merkmal in der Sitzung, damit
 *                  der Dialog nicht bei jedem Aufruf der Startseite wieder
 *                  aufgeht. Die Frist bleibt faellig; bei der naechsten
 *                  Anmeldung steht die Frage wieder da.
 *
 * ES GIBT KEIN „NEIN" ALS ANTWORT AN DIESEN ENDPUNKT. „Nein" ist im Dialog
 * kein Ergebnis, sondern ein Schritt: Es fuehrt zur Passworteingabe und von
 * dort zur Erneuerung. Erst wenn die durch ist, meldet der Browser
 * `beantwortet`. Wer zwischendurch abbricht, hat nichts beantwortet — und
 * bekommt die Frage bei der naechsten Anmeldung wieder. Genau so soll es
 * sein: Ein „Nein", das die Frist verlaengert, ohne dass ein Schluessel
 * entstanden ist, waere ein Weg, sie stillzulegen.
 *
 * ===========================================================================
 * DIESER ENDPUNKT VERSCHIEBT EINE FRIST — MEHR NICHT
 * ===========================================================================
 *
 * Er schreibt `rueckfrage_naechste`, `rueckfrage_runde` und
 * `rueckfrage_verschoben`. Kein Schluessel, keine Huelle, kein Passwort. Die
 * Erneuerung ist `api/schluessel_erneuern.php`, und die verlangt das Passwort
 * aus gutem Grund (der Kopf dort sagt, aus welchem).
 *
 * DESHALB REICHT HIER DIE SITZUNG. Das Schlimmste, was jemand mit einer
 * uebernommenen Sitzung hier anrichten kann, ist eine um sechs Monate
 * verschobene Frage — aergerlich, aber kein Verlust. Ein Passwort zu
 * verlangen, um einen Dialog wegzuklicken, waere die Art von Sicherheit, die
 * niemand ernst nimmt.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'methode']);
    exit;
}
csrf_check();

$antwort = (string)($_POST['antwort'] ?? '');

/* DAS SITZUNGSMERKMAL FUER JEDE ANTWORT — auch fuer `weggeklickt`. Es haelt
 * den Dialog bis zur naechsten Anmeldung zurueck. `index.php` liest es und
 * setzt es NICHT (der Kopf dort sagt, warum). */
if (in_array($antwort, ['beantwortet', 'spaeter', 'weggeklickt'], true)) {
    $_SESSION['rueckfrage_gezeigt'] = true;
}

if ($antwort === 'weggeklickt') {
    echo json_encode(['ok' => true]);
    exit;
}

/* EIGENES MERKMAL, EIGENE FRAGE. `blatt_spaeter` darf die Konto-Rueckfrage
 * NICHT mit wegraeumen: Sie ist eine andere Frage an dieselbe Person, und
 * `einstieg_faellig()` gibt sie erst heraus, wenn die Schluesselblatt-Frage
 * durch ist. Ein gemeinsames Merkmal haette beide auf einen Klick
 * stillgelegt. */
if ($antwort === 'blatt_spaeter') {
    $_SESSION['blatt_gezeigt'] = true;
    echo json_encode(['ok' => true]);
    exit;
}

if ($antwort === 'beantwortet') {
    rueckfrage_beantwortet($userId);
    echo json_encode(['ok' => true]);
    exit;
}

if ($antwort === 'spaeter') {
    /* `false` heisst: Das Verschieben ist aufgebraucht (dreimal je Runde).
     * Der Dialog schliesst sich trotzdem — er soll nicht zur Falle werden —,
     * aber er steht bei der naechsten Anmeldung wieder da. Der Browser sagt
     * das an, damit niemand sich wundert. */
    echo json_encode(['ok' => true, 'geschoben' => rueckfrage_spaeter($userId)]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'antwort']);
