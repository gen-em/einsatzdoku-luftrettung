<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../backup_lib.php';

/**
 * GET api/backup_data.php -> das Datenpaket der angemeldeten NutzerIn als
 * JSON. Die verschluesselten Angaben stecken darin unveraendert als
 * Chiffretext (`pat_blob`) — der Browser entschluesselt sie und ersetzt sie
 * durch Klartext, bevor er die Datei mit dem Backup-Passwort versiegelt. Der
 * Server sieht dabei nie Klartext.
 *
 * `?ohne_spuren=1` LIEFERT DEN KERN DER FASSUNG 4 (S2/AP5, Konzept 3.2.3):
 * dieselbe Nutzlast ohne Punktlisten, dafuer mit einer `spur_ref` je Spur.
 * Die Punkte holt der Browser danach ueber `api/backup_spuren.php`.
 *
 * WARUM EIN GET-PARAMETER UND KEIN POST. Der Endpunkt aendert nichts, und was
 * nichts aendert, beantwortet auch kein POST (M3-11) — der Schutz ist die
 * Sitzung. Ein Wechsel auf POST haette nur den CSRF-Kopf gebracht, den ein
 * lesender Weg nicht braucht, und den heutigen Vertrag gebrochen.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { json_out(['error' => 'method'], 405); }

try {
    $out = edbak_build($userId, !empty($_GET['ohne_spuren']));
    header('Content-Type: application/json; charset=utf-8');
    // Dieser Weg gibt das Paket direkt aus, nicht ueber json_out() — der
    // Kopf gegen das Zwischenspeichern muss deshalb hier stehen (M3-11).
    header('Cache-Control: no-store');
    echo $out;
} catch (Throwable $ex) {
    json_fehler($ex, 'backup');
}
