<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../backup_lib.php';

/**
 * POST api/backup_eintraege_restore.php — ein Fenster von Eintraegen.
 *
 * WOFUER (S2/AP5b). Der Kopf einer Fassung-4-Datei ist eingespielt; die
 * Einsaetze und Ruhesegmente kommen danach in Fenstern. Zwei Gruende, und
 * beide sind gemessen:
 *
 *   POST       Der Kern eines 5000er-Bestands waere ein Rumpf von 9,4 MB.
 *              Lokal geht das (bis 40 MB nachgemessen), aber davor sitzt ein
 *              Webserver, den niemand kennt: nginx deckelt in der Vorgabe bei
 *              1 MB. Ein Fenster von 250 Eintraegen ist 0,44 MB.
 *   SPEICHER   Denselben Kern am Stueck zu bauen kostet den Server 39,5 MB
 *              von 64 (Z3) — und das waechst mit dem Bestand: bei 187
 *              Eintraegen sind es 4,0 MB, bei 10 797 die genannten 39,5, also
 *              rund 3,3 kB je Eintrag. In Fenstern bleibt es bei 10,0 MB.
 *              (Bis Web 11.0.0 waren es 92 MB; das hat AP5 mit den Fenstern
 *              der Kindtabellen erledigt, nicht dieses Paket.)
 *
 * Rumpf: {"eintraege": {"missions": [...], "rest_segments": [...]},
 *         "day_map": {"<Datei-Kennung>": <Kennung hier>, …}}
 * Antwort: {"ok": true, "stats": {...}, "spur_karte": {...}}
 *
 * DIE ZUORDNUNG DER DIENSTTAGE WIRD GEPRUEFT, NICHT GEGLAUBT. Sie kommt vom
 * Browser, und ein Browser kann alles schicken. `edbak_restore()` prueft sie
 * gegen `user_id`; was nicht diesem Konto gehoert, faellt heraus, und die
 * betroffenen Eintraege werden uebersprungen und gezaehlt. Ohne diese Pruefung
 * liesse sich ein Einsatz an den Diensttag eines FREMDEN Kontos haengen.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_out(['error' => 'method'], 405); }
if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
    json_out(['error' => 'csrf'], 403);
}

$roh = file_get_contents('php://input');
if ($roh === '' || $roh === false) {
    json_out(['error' => 'leer', 'hinweis' =>
        'Es kamen keine Daten an — evtl. begrenzt der Server die Upload-Größe '
      . '(post_max_size, client_max_body_size).'], 400);
}
$b = json_decode($roh, true);
if (!is_array($b) || !isset($b['eintraege']) || !is_array($b['eintraege'])) {
    json_out(['error' => 'payload'], 400);
}

$eintraege = $b['eintraege'];
/* Die Fassung steht im Kopf der Datei und wird hier mitgeschickt: Der
 * Eintragsweg gibt es nur fuer Nutzlast 8, und das soll dastehen statt sich
 * daraus zu ergeben, dass niemand anders ihn ruft. */
$eintraege['version'] = 9;

try {
    $stats = edbak_restore($userId, $eintraege,
                           is_array($b['day_map'] ?? null) ? $b['day_map'] : []);
    $karte = $stats['spur_karte'] ?? null;
    unset($stats['spur_karte'], $stats['day_map']);
    json_out(['ok' => true, 'stats' => $stats,
              'spur_karte' => $karte ?: new stdClass()]);
} catch (Throwable $ex) {
    json_fehler($ex, 'restore');
}
