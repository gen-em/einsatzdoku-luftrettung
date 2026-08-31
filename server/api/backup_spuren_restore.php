<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../spur_lib.php';
require_once __DIR__ . '/../site_elevation_lib.php';

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

    $pdo = db();

    /* Eigentum in EINER Abfrage je Art, nicht je Spur (M5-12). */
    $wunsch = ['mission' => [], 'rest' => []];
    foreach ($liste as $s) {
        $art = (string)($s['owner_type'] ?? '');
        $id  = (int)($s['owner_id'] ?? 0);
        if (isset($wunsch[$art]) && $id > 0) { $wunsch[$art][$id] = true; }
    }
    $eigen = ['mission' => [], 'rest' => []];
    foreach ($wunsch as $art => $ids) {
        if (!$ids) { continue; }
        $tabelle = $art === 'mission' ? 'missions' : 'rest_segments';
        foreach (sql_in_bloecken($pdo,
            "SELECT id FROM `$tabelle` WHERE user_id = ? AND id IN ({IDS})",
            array_keys($ids), [$userId]) as $r) {
            $eigen[$art][(int)$r['id']] = true;
        }
    }

    /* Welche Spuren liegen schon? Eine Abfrage je Art — die Wiederaufnahme
     * soll nicht je Spur nachfragen. */
    $vorhanden = ['mission' => [], 'rest' => []];
    foreach ($eigen as $art => $ids) {
        if (!$ids) { continue; }
        foreach (spur_blob_lesen_viele($pdo, $art, array_keys($ids)) as $id => $_x) {
            $vorhanden[$art][$id] = true;
        }
    }

    $geschrieben = 0; $uebersprungen = 0; $abgelehnt = []; $hoeheOffen = [];

    foreach ($liste as $s) {
        $art = (string)($s['owner_type'] ?? '');
        $id  = (int)($s['owner_id'] ?? 0);
        if (!isset($eigen[$art][$id])) {
            /* Fremd, geloescht oder erfunden — dieselbe Antwort, wie ueberall
             * in dieser Anwendung: Ein eigener Code verriete, welcher Fall es
             * ist. Gezaehlt wird er trotzdem, sonst faellt ein Fehler im
             * Browser nicht auf. */
            $abgelehnt[] = ['owner_type' => $art, 'owner_id' => $id,
                            'grund' => 'nicht vorhanden'];
            continue;
        }
        if (isset($vorhanden[$art][$id])) { $uebersprungen++; continue; }

        $blob = base64_decode((string)($s['blob'] ?? ''), true);
        if ($blob === false || $blob === '') {
            $abgelehnt[] = ['owner_type' => $art, 'owner_id' => $id,
                            'grund' => 'kein lesbarer Blob'];
            continue;
        }
        $fehler = spur_blob_pruefen($blob, isset($s['n']) ? (int)$s['n'] : null);
        if ($fehler !== null) {
            $abgelehnt[] = ['owner_type' => $art, 'owner_id' => $id, 'grund' => $fehler];
            continue;
        }
        $kopf = spur_kopf($blob);
        spur_blob_schreiben($pdo, $art, $id, $blob,
                            $kopf['stufe'], $kopf['n_original'], $kopf['n']);
        $vorhanden[$art][$id] = true;    // gegen Dubletten IN DERSELBEN Anfrage
        $geschrieben++;
        if ($art === 'mission') { $hoeheOffen[] = $id; }
    }

    /* DIE HOEHE NACH DEM SCHREIBEN, nicht mittendrin: Sie liest die Spur, die
     * gerade erst entstanden ist. Ein Fehlschlag ist kein Grund, die Anfrage
     * scheitern zu lassen — die Angabe ist abgeleitet, die Spur liegt. */
    $hoeheFehler = 0;
    foreach (array_unique($hoeheOffen) as $mid) {
        try { compute_site_elevation($pdo, $mid); }
        catch (Throwable $ex) { $hoeheFehler++; }
    }

    $antwort = ['ok' => true, 'geschrieben' => $geschrieben,
                'uebersprungen' => $uebersprungen, 'abgelehnt' => $abgelehnt];
    if ($hoeheFehler > 0) { $antwort['hoehe_fehler'] = $hoeheFehler; }
    json_out($antwort);
} catch (Throwable $ex) {
    json_fehler($ex, 'restore');
}
