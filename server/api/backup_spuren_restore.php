<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../backup_lib.php';      // edbak_spuren_schreiben()

/**
 * POST api/backup_spuren_restore.php — die Spuren eines Backups zurueckspielen.
 *
 * WOFUER (Konzept S2, 3.2.4). Der Kern ist eingespielt; der Server hat dabei
 * gesagt, unter welcher Kennung jede `spur_ref` angelegt wurde. Jetzt kommen
 * die Spuren, teilweise — hoechstens 2 MB je Anfrage.
 *
 * Rumpf: {"spuren": [{"owner_type": "mission", "owner_id": 42, "stufe": 2,
 *                     "stuecke": [{seq_from, punkte, ts_von, ts_bis, huelle,
 *                                  n_original?}, …]}, …]}
 *
 * SEIT WEB 22.5.0 NUR VERSCHLOSSEN (S11-06b). Bis dahin kamen hier SPUR1-
 * Blobs an, also Punkte im Klartext. Der Browser nimmt die Punktlisten der
 * Nutzlast 13, verschliesst sie mit dem oeffentlichen Schluessel des Kontos
 * und schickt Stuecke (bis Web 22.7.0 las er dazu SPUR1 aus 8 bis 12);
 * eine lange Spur verteilt er auf mehrere Anfragen. Ein Blob wird mit 400
 * abgewiesen, und zwar die ganze Anfrage, bevor etwas liegt.
 * Antwort: {"geschrieben": 12, "uebersprungen": 3, "abgelehnt": [{…}]}
 *
 * DREI DINGE, DIE HIER PASSIEREN MUESSEN — und die man einzeln vergessen kann:
 *
 * 1. EIGENTUM PRUEFEN. Die Kennungen stammen aus der Antwort, die dieser
 *    Server gerade geschickt hat; trotzdem wird jede gegen `user_id`
 *    geprueft. Wer sich darauf verlaesst, dass der Browser nur zurueckgibt,
 *    was er bekommen hat, hat eine Schnittstelle gebaut, die fremde Spuren
 *    ueberschreibt — es genuegte eine Anfrage von Hand.
 *
 * 2. JEDES STUECK PRUEFEN, BEVOR ES LIEGT (`pruef_spurstueck()`) — dieselbe
 *    Schicht wie im Eingang der Geraete. CLAUDE.md 4 sagt „alle
 *    Schreibwege, ohne Ausnahme".
 *
 * 3. VORHANDENE UEBERSPRINGEN. Eine abgebrochene Wiederherstellung soll sich
 *    fortsetzen lassen: Eine Spur, die schon im Klartext liegt, wird nicht
 *    ergaenzt, und ein Stueck, das schon liegt, nicht doppelt geschrieben.
 *
 * DIE HOEHE DES EINSATZORTS RECHNET HIER NIEMAND MEHR (F-S11-45). Bis
 * Web 22.4.0 stand sie hier, aus der eben geschriebenen Klartextspur; seit
 * S11-06b traegt sie der `pat_blob`, den der Browser aus der Datei fuellt.
 */

/** Hoechstens so viele Spuren je Anfrage — die Groesse deckelt der Browser. */
const BACKUP_SPUREN_RESTORE_MAX = 500;

try {
    api_methode();
    csrf_check();   // Feld ODER Kopfzeile X-CSRF (Nr. 67)

    $b = api_rumpf();
    if (!isset($b['spuren']) || !is_array($b['spuren'])) {
        json_out(['error' => 'payload'], 400);
    }
    $liste = $b['spuren'];
    if (count($liste) > BACKUP_SPUREN_RESTORE_MAX) {
        json_out(['error' => 'zu_viele',
                  'meldung' => 'Höchstens ' . BACKUP_SPUREN_RESTORE_MAX
                             . ' Aufzeichnungen je Anfrage.'], 400);
    }

    /* DIE ARBEIT STEHT IN backup_lib.php (S2/AP6).
     *
     * Sie stand bis Web 11.2.0 hier — und das Admin-Backup braucht seit
     * dem Umbau auf das mehrteilige Rohpaket genau dasselbe. Ein zweiter Weg
     * waere ein zweiter Ort, an dem die Eigentumspruefung, die Blobpruefung
     * und das Ueberspringen vorhandener Spuren zu vergessen sind. Dieser
     * Endpunkt ist seitdem die Schale: Rumpf pruefen, Grenze halten,
     * antworten. */
    foreach ($liste as $s) {
        if (is_array($s) && isset($s['blob'])) {
            json_out(['error' => 'klartext',
                      'meldung' => 'Das Backup enthielt GPS-Daten im Klartext. Seit Web 22.5.0 '
                                 . 'verschlüsselt sie der Browser vor dem Senden; bitte die Seite '
                                 . 'neu laden. Es wurde nichts geändert.'], 400);
        }
    }
    $e = edbak_spuren_schreiben(db(), $userId, $liste);
    $geschrieben   = $e['geschrieben'];
    $uebersprungen = $e['uebersprungen'];
    $abgelehnt     = $e['abgelehnt'];

    json_out(['ok' => true, 'geschrieben' => $geschrieben,
              'uebersprungen' => $uebersprungen, 'abgelehnt' => $abgelehnt]);
} catch (Throwable $ex) {
    json_fehler($ex, 'restore');
}
