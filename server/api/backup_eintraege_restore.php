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

api_methode();
csrf_check();   // Feld ODER Kopfzeile X-CSRF (Nr. 67)

$b = api_rumpf();
/* DER ABSCHLUSS (Backlog Nr. 386, R17-23): Nach dem letzten Fenster schickt
 * der Browser die Sperrvermerke zurueck, deren Quelle beim Einspielen ihres
 * Fensters noch nicht da war — in Stuecken zu hoechstens
 * EDBAK_SCHNITTE_NACHREICHEN_MAX. `edbak_schnitte_nachreichen()` prueft sie
 * neu und sucht Ziel und Quelle im Konto.
 * Rumpf: {"schnitte_offen": [...]} · Antwort: {"ok": true, "stats": {...}} */
if (array_key_exists('schnitte_offen', $b)) {
    if (!is_array($b['schnitte_offen'])) { json_out(['error' => 'payload'], 400); }
    try {
        json_out(['ok' => true,
                  'stats' => edbak_schnitte_nachreichen($userId, $b['schnitte_offen'])]);
    } catch (Throwable $ex) {
        json_fehler($ex, 'restore');
    }
}
if (!isset($b['eintraege']) || !is_array($b['eintraege'])) {
    json_out(['error' => 'payload'], 400);
}

$eintraege = $b['eintraege'];
/* Die Fassung steht im Kopf der Datei und wird hier mitgeschickt: Den
 * Eintragsweg gibt es erst ab Nutzlast 8, und das soll dastehen statt sich
 * daraus zu ergeben, dass niemand anders ihn ruft. Die Zahl folgt der
 * geschriebenen Nutzlast (`EDBAK_NUTZLAST`, seit P5c/AP8; bis dahin stand
 * hier 10, F-P5c-128) — unbedenklich, weil alle Fassungen ab 8 denselben Weg
 * nehmen und die spaeteren Felder optional sind. */
$eintraege['version'] = EDBAK_NUTZLAST;

try {
    /* `offenZurueck`: Ein Vermerk, dessen Quelle in einem spaeteren Fenster
     * steht, kommt als `schnitte_offen` zurueck statt verworfen zu werden
     * (Nr. 386); der Browser reicht ihn nach dem letzten Fenster nach. */
    $stats = edbak_restore($userId, $eintraege,
                           is_array($b['day_map'] ?? null) ? $b['day_map'] : [], 'browser',
                           null, true);
    $karte = $stats['spur_karte'] ?? null;
    $offen = $stats['schnitte_offen'] ?? [];
    unset($stats['spur_karte'], $stats['day_map'], $stats['schnitte_offen']);
    json_out(['ok' => true, 'stats' => $stats,
              'spur_karte' => $karte ?: new stdClass(),
              'schnitte_offen' => $offen]);
} catch (DomainException $ex) {
    /* Klartext-Ortsdaten (S11-06b): Der Browser verschliesst seit Web 22.5.0
     * Orte, Reanimation und Spur vor dem Senden; eine Nutzlast mit Klartext
     * kommt aus einer veralteten Seite oder von Hand. Nichts ist geschrieben
     * — `edbak_restore()` prueft vor der Transaktion. */
    json_out(['error' => 'klartext',
              'meldung' => 'Das Backup enthielt Ortsdaten im Klartext ('
                         . preg_replace('/^klartext: /', '', $ex->getMessage())
                         . '). Seit Web 22.5.0 verschlüsselt sie der Browser vor dem Senden; '
                         . 'bitte die Seite neu laden. Es wurde nichts geändert.'], 400);
} catch (Throwable $ex) {
    json_fehler($ex, 'restore');
}
