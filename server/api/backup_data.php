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
 * FUER CONTAINERFASSUNG 4 (S2/AP5) LIEFERT ER IN STUECKEN:
 *
 *   ?teil=kopf                       Stammdaten, Diensttage, `eintraege_gesamt`
 *   ?teil=eintraege&ab=0&anzahl=500  ein Fenster von Einsaetzen und
 *                                    Ruhesegmenten, ohne Punktlisten, dafuer
 *                                    mit einer `spur_ref` je Spur
 *
 * Die Punkte holt der Browser danach ueber `api/backup_spuren.php`.
 *
 * WARUM IN STUECKEN — zwei Gruende, und der erste wiegt schwerer:
 *
 * 1. DER RUECKWEG. Der Kern eines 5000er-Bestands ist 10,5 MB; er ginge als
 *    EIN POST zurueck, gegen ein Limit, das niemand kennt — nginx nimmt in
 *    der Vorgabe 1 MB. Ein Fenster von 250 Eintraegen ist 0,44 MB.
 * 2. DER SPEICHER. Am Stueck kostet der Bau 39,5 MB von 64 (Z3) und waechst
 *    mit dem Bestand; in Fenstern sind es 10,0 MB, fast unabhaengig davon.
 *
 * GEMESSEN am 31.08.2026 (`memory_get_peak_usage(true)`, PHP-CLI):
 *
 *   Kern am Stueck   Demo (187 Eintraege)      0,18 MB Text,  4,0 MB Spitze
 *                    Messstand (10 797)       10,47 MB Text, 39,5 MB Spitze
 *   in Fenstern      Demo                      0,17 MB groesstes Fenster,  4,0 MB
 *                    Messstand                 0,44 MB groesstes Fenster, 10,0 MB
 *
 * Am Stueck waechst die Spitze mit dem Bestand (3,3 kB je Eintrag, aus den
 * zwei Messpunkten fortgeschrieben) — 64 MB waeren bei rund 18 000 Eintraegen
 * erreicht. In Fenstern waechst sie kaum: Was bleibt, ist die Kennungsliste.
 *
 * WARUM EIN GET-PARAMETER UND KEIN POST. Der Endpunkt aendert nichts, und was
 * nichts aendert, beantwortet auch kein POST (M3-11) — der Schutz ist die
 * Sitzung. Ein Wechsel auf POST haette nur den CSRF-Kopf gebracht, den ein
 * lesender Weg nicht braucht, und den heutigen Vertrag gebrochen.
 *
 * OHNE PARAMETER liefert er weiterhin die vollstaendige Nutzlast mit
 * Punktlisten — so holen die Adminpakete und die Demo-Fixture ihren Bestand.
 */

/** Hoechstens so viele Eintraege je Anfrage. */
const BACKUP_EINTRAEGE_MAX = 1000;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { json_out(['error' => 'method'], 405); }

try {
    $teil = (string)($_GET['teil'] ?? '');
    if ($teil === 'kopf') {
        $out = edbak_build($userId, true, ['kopf' => true]);
    } elseif ($teil === 'eintraege') {
        $ab = max(0, (int)($_GET['ab'] ?? 0));
        $anzahl = (int)($_GET['anzahl'] ?? EDBAK_FENSTER);
        if ($anzahl < 1 || $anzahl > BACKUP_EINTRAEGE_MAX) {
            json_out(['error' => 'anzahl',
                      'meldung' => 'Zwischen 1 und ' . BACKUP_EINTRAEGE_MAX
                                 . ' Einträgen je Anfrage.'], 400);
        }
        $out = edbak_build($userId, true, ['ab' => $ab, 'anzahl' => $anzahl]);
    } elseif ($teil !== '') {
        json_out(['error' => 'teil',
                  'meldung' => 'Unbekannter Teil — erwartet „kopf" oder „eintraege".'], 400);
    } else {
        $out = edbak_build($userId);
    }
    header('Content-Type: application/json; charset=utf-8');
    // Dieser Weg gibt das Paket direkt aus, nicht ueber json_out() — der
    // Kopf gegen das Zwischenspeichern muss deshalb hier stehen (M3-11).
    header('Cache-Control: no-store');
    echo $out;
} catch (Throwable $ex) {
    json_fehler($ex, 'backup');
}
