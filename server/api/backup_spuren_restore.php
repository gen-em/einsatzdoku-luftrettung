<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../backup_lib.php';      // edbak_spuren_schreiben()

/**
 * POST api/backup_spuren_restore.php — die Spuren einer Sicherung zurueckspielen.
 *
 * WOFUER (Konzept S2, 3.2.4). Der Kern ist eingespielt; der Server hat dabei
 * gesagt, unter welcher Kennung jede `spur_ref` angelegt wurde. Jetzt kommen
 * die Blobs, teilweise — hoechstens 2 MB je Anfrage.
 *
 * Rumpf: {"spuren": [{"owner_type": "mission", "owner_id": 42,
 *                     "blob": "<Base64>", "n": 443}, …]}
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
 * 2. DEN BLOB PRUEFEN, BEVOR ER LIEGT (`spur_blob_pruefen()`). Fuer
 *    Punktlisten gibt es diese Schicht seit je; fuer Blobs waere sie sonst
 *    die einzige Stelle der Anwendung, an der ungeprueft Binaerinhalt in die
 *    Datenbank ginge. CLAUDE.md 4 sagt „alle Schreibwege, ohne Ausnahme".
 *
 * 3. VORHANDENE UEBERSPRINGEN. `spur_blob_schreiben()` ist ein Upsert und
 *    UEBERSCHREIBT. Das ist an seiner Stelle richtig (der Verdichtungsjob
 *    ersetzt einen Blob durch den ausgeduennten), hier aber falsch: Eine
 *    abgebrochene Wiederherstellung soll sich fortsetzen lassen, ohne dass
 *    der zweite Lauf ueberschreibt, was der erste schon gebracht hat.
 *
 * UND DIE HOEHE DES EINSATZORTS GEHOERT HIERHER, nicht in den Kernlauf.
 *
 * `edbak_restore()` bestimmt sie am Ende aus der Spur (`compute_site_elevation`).
 * Bei Nutzlast 7 lagen die Punkte da bereits — sie standen in derselben
 * Anfrage. Bei Fassung 4 kommen sie ERST HIER an; der Kernlauf haette eine
 * Spur ohne Punkte vor sich und traege nichts ein.
 *
 * Gefunden hat das der Kreislauf: 79 Einsaetze kamen ohne `site_ele_m`
 * zurueck, obwohl die Quelle sie hatte. Kein Datenverlust — die Angabe ist
 * abgeleitet und liesse sich nachrechnen —, aber ein stiller Unterschied
 * zwischen Sicherung und Wiederherstellung, und genau die sucht ein
 * Kreislauf.
 */

/** Hoechstens so viele Spuren je Anfrage — die Groesse deckelt der Browser. */
const BACKUP_SPUREN_RESTORE_MAX = 500;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_out(['error' => 'method'], 405); }
    if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
        json_out(['error' => 'csrf'], 403);
    }

    $roh = file_get_contents('php://input');
    if ($roh === '' || $roh === false) {
        json_out(['error' => 'leer', 'hinweis' =>
            'Es kamen keine Daten an — evtl. begrenzt der Server die Upload-Größe '
          . '(post_max_size).'], 400);
    }
    $b = json_decode($roh, true);
    if (!is_array($b) || !isset($b['spuren']) || !is_array($b['spuren'])) {
        json_out(['error' => 'payload'], 400);
    }
    $liste = $b['spuren'];
    if (count($liste) > BACKUP_SPUREN_RESTORE_MAX) {
        json_out(['error' => 'zu_viele',
                  'meldung' => 'Höchstens ' . BACKUP_SPUREN_RESTORE_MAX
                             . ' Spuren je Anfrage.'], 400);
    }

    /* DIE ARBEIT STEHT IN backup_lib.php (S2/AP6).
     *
     * Sie stand bis Web 11.2.0 hier — und die Admin-Sicherung braucht seit
     * dem Umbau auf das mehrteilige Rohpaket genau dasselbe. Ein zweiter Weg
     * waere ein zweiter Ort, an dem die Eigentumspruefung, die Blobpruefung
     * und das Ueberspringen vorhandener Spuren zu vergessen sind. Dieser
     * Endpunkt ist seitdem die Schale: Rumpf pruefen, Grenze halten,
     * antworten. */
    $e = edbak_spuren_schreiben(db(), $userId, $liste);
    $geschrieben   = $e['geschrieben'];
    $uebersprungen = $e['uebersprungen'];
    $abgelehnt     = $e['abgelehnt'];
    $hoeheFehler   = $e['hoehe_fehler'];

    $antwort = ['ok' => true, 'geschrieben' => $geschrieben,
                'uebersprungen' => $uebersprungen, 'abgelehnt' => $abgelehnt];
    if ($hoeheFehler > 0) { $antwort['hoehe_fehler'] = $hoeheFehler; }
    json_out($antwort);
} catch (Throwable $ex) {
    json_fehler($ex, 'restore');
}
