<?php
declare(strict_types=1);

/**
 * Papierkorb (Soft-Delete) fuer Einsaetze und Diensttage.
 *
 * Geloeschtes wird zunaechst nur markiert (`deleted_at`) und bleibt
 * TRASH_DAYS Tage wiederherstellbar; der Aufraeumjob in db.php entfernt es
 * danach endgueltig. Erst beim endgueltigen Entfernen wandert die Referenz in
 * die Sperrliste `deleted_refs`, damit die Uhr den Einsatz nicht erneut
 * hochlaedt — solange etwas im Papierkorb liegt, quittiert der Server
 * Uploads zwar, verwirft sie aber (siehe ingest.php).
 *
 * Beim Loeschen eines ganzen Diensttags werden dessen Einsaetze und
 * Ruhesegmente mit `deleted_with_day = 1` markiert. Sie erscheinen dadurch
 * nicht einzeln im Papierkorb, sondern haengen am Diensttag und kehren mit ihm
 * gemeinsam zurueck.
 *
 * SCHLUESSEL IST SEIT WEB 6.0.0 DIE KENNUNG `days.id`, nicht mehr das Datum.
 * Alle Funktionen dieser Datei nehmen deshalb `int $dayId`. Inhaltlich aendert
 * sich nichts: `deleted_with_day` und die Sperrliste arbeiten unveraendert.
 * Entfallen ist allein das Anlegen einer Traegerzeile beim Loeschen — den
 * Diensttag, den es loescht, gibt es jetzt zwangslaeufig, weil die Einsaetze
 * mit einem Fremdschluessel auf ihn verweisen.
 */

const TRASH_DAYS = 90;

/* ---- Umfang ermitteln (fuer die Sicherheitsabfragen) ------------------- */

function trash_scope_mission(int $userId, int $id): ?array {
    $st = db()->prepare('SELECT * FROM missions WHERE id = ? AND user_id = ?');
    $st->execute([$id, $userId]);
    $m = $st->fetch();
    if (!$m) { return null; }

    $one = function (string $sql, array $p): int {
        $s = db()->prepare($sql); $s->execute($p); return (int)$s->fetchColumn();
    };
    return [
        'mission'  => $m,
        'phasen'   => $one('SELECT COUNT(*) FROM mission_phases WHERE mission_id = ?', [$id]),
        'reas'     => $one('SELECT COUNT(*) FROM resus_sessions WHERE mission_id = ?', [$id]),
        'punkte'   => $one("SELECT COUNT(*) FROM track_points
                            WHERE owner_type = 'mission' AND owner_id = ?", [$id]),
    ];
}

function trash_scope_day(int $userId, int $dayId): array {
    $one = function (string $sql, array $p): int {
        $s = db()->prepare($sql); $s->execute($p); return (int)$s->fetchColumn();
    };
    $missions = db()->prepare('SELECT id FROM missions
                               WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL');
    $missions->execute([$userId, $dayId]);
    $mids = $missions->fetchAll(PDO::FETCH_COLUMN);

    $segs = db()->prepare('SELECT id FROM rest_segments
                           WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL');
    $segs->execute([$userId, $dayId]);
    $sids = $segs->fetchAll(PDO::FETCH_COLUMN);

    $punkte = 0; $phasen = 0; $reas = 0;
    foreach ($mids as $mid) {
        $punkte += $one("SELECT COUNT(*) FROM track_points
                         WHERE owner_type = 'mission' AND owner_id = ?", [(int)$mid]);
        $phasen += $one('SELECT COUNT(*) FROM mission_phases WHERE mission_id = ?', [(int)$mid]);
        $reas   += $one('SELECT COUNT(*) FROM resus_sessions WHERE mission_id = ?', [(int)$mid]);
    }
    foreach ($sids as $sid) {
        $punkte += $one("SELECT COUNT(*) FROM track_points
                         WHERE owner_type = 'rest' AND owner_id = ?", [(int)$sid]);
    }
    $meta = db()->prepare('SELECT * FROM days WHERE user_id = ? AND id = ? AND deleted_at IS NULL');
    $meta->execute([$userId, $dayId]);

    return [
        'day_id'    => $dayId,
        'einsaetze' => count($mids),
        'segmente'  => count($sids),
        'punkte'    => $punkte,
        'phasen'    => $phasen,
        'reas'      => $reas,
        'meta'      => $meta->fetch() ?: null,
    ];
}

/* ---- In den Papierkorb legen ------------------------------------------ */

function trash_delete_mission(int $userId, int $id): void {
    $st = db()->prepare('UPDATE missions SET deleted_at = UTC_TIMESTAMP(), deleted_with_day = 0
                         WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
    $st->execute([$id, $userId]);
}

function trash_delete_day(int $userId, int $dayId): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        /* Keine Traegerzeile mehr sicherstellen: Der Diensttag IST die Zeile,
         * ohne die es die Einsaetze nicht gaebe (Fremdschluessel `day_id`).
         * Bis Web 5.10.0 hing die Zuordnung am Datum und ein Tag konnte
         * Einsaetze haben, ohne selbst zu existieren — dafuer war das
         * INSERT IGNORE da. */
        $pdo->prepare('UPDATE days SET deleted_at = UTC_TIMESTAMP()
                       WHERE user_id = ? AND id = ? AND deleted_at IS NULL')
            ->execute([$userId, $dayId]);
        $pdo->prepare('UPDATE missions SET deleted_at = UTC_TIMESTAMP(), deleted_with_day = 1
                       WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL')
            ->execute([$userId, $dayId]);
        $pdo->prepare('UPDATE rest_segments SET deleted_at = UTC_TIMESTAMP(), deleted_with_day = 1
                       WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL')
            ->execute([$userId, $dayId]);
        $pdo->commit();
    } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
}

/* ---- Wiederherstellen -------------------------------------------------- */

function trash_restore_mission(int $userId, int $id): void {
    db()->prepare('UPDATE missions SET deleted_at = NULL
                   WHERE id = ? AND user_id = ? AND deleted_with_day = 0')
        ->execute([$id, $userId]);
}

function trash_restore_day(int $userId, int $dayId): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE days SET deleted_at = NULL WHERE user_id = ? AND id = ?')
            ->execute([$userId, $dayId]);
        $pdo->prepare('UPDATE missions SET deleted_at = NULL, deleted_with_day = 0
                       WHERE user_id = ? AND day_id = ? AND deleted_with_day = 1')
            ->execute([$userId, $dayId]);
        $pdo->prepare('UPDATE rest_segments SET deleted_at = NULL, deleted_with_day = 0
                       WHERE user_id = ? AND day_id = ? AND deleted_with_day = 1')
            ->execute([$userId, $dayId]);
        $pdo->commit();
    } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
}

/* ---- Endgueltig entfernen ---------------------------------------------- */

/**
 * Sperrliste fuellen, damit die Uhr den Datensatz nicht neu anlegt.
 *
 * $ownerType unterscheidet 'mission' und 'rest'. Die Unterscheidung ist noetig,
 * weil beide Arten ihre Kennungen unabhaengig vergeben — ein gemeinsamer
 * Eintrag koennte sonst die falsche Art sperren.
 *
 * FRUEHER GALT DIE LISTE NUR FUER EINSAETZE, und zwar an BEIDEN Enden: Sie
 * wurde nur fuer Einsaetze befuellt und nur im Einsatz-Zweig abgefragt. Ein
 * endgueltig geloeschtes Ruhe-Segment wurde deshalb von der naechsten
 * Nachlieferung wieder angelegt — und beim erneuten Loeschen wieder. Wer eine
 * Uhr im Einsatz hat, kam aus dieser Schleife nicht heraus.
 *
 * Von Hand angelegte Datensaetze ('man-') werden bewusst NICHT gesperrt: Dort
 * gibt es keine Uhr, die etwas nachliefern koennte, und die Kennung koennte
 * spaeter erneut vergeben werden.
 */
function trash_block_ref(PDO $pdo, array $m, string $ownerType = 'mission'): void {
    if ($m['device_id'] !== null && strpos((string)$m['client_ref'], 'man-') !== 0) {
        $pdo->prepare('INSERT IGNORE INTO deleted_refs (device_id, owner_type, client_ref)
                       VALUES (?,?,?)')
            ->execute([(int)$m['device_id'], $ownerType, $m['client_ref']]);
    }
}

function trash_purge_mission(int $userId, int $id): void {
    $pdo = db();
    $st = $pdo->prepare('SELECT id, device_id, client_ref FROM missions
                         WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL');
    $st->execute([$id, $userId]);
    $m = $st->fetch();
    if (!$m) { return; }

    $pdo->beginTransaction();
    try {
        trash_block_ref($pdo, $m);
        $pdo->prepare("DELETE FROM track_points WHERE owner_type = 'mission' AND owner_id = ?")
            ->execute([$id]);
        $pdo->prepare('DELETE FROM missions WHERE id = ?')->execute([$id]);  // Rest kaskadiert
        $pdo->commit();
    } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
}

function trash_purge_day(int $userId, int $dayId): void {
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $ms = $pdo->prepare('SELECT id, device_id, client_ref FROM missions
                             WHERE user_id = ? AND day_id = ? AND deleted_at IS NOT NULL');
        $ms->execute([$userId, $dayId]);
        foreach ($ms->fetchAll() as $m) {
            trash_block_ref($pdo, $m);
            $pdo->prepare("DELETE FROM track_points WHERE owner_type = 'mission' AND owner_id = ?")
                ->execute([(int)$m['id']]);
            $pdo->prepare('DELETE FROM missions WHERE id = ?')->execute([(int)$m['id']]);
        }
        $ss = $pdo->prepare('SELECT id, device_id, client_ref FROM rest_segments
                             WHERE user_id = ? AND day_id = ? AND deleted_at IS NOT NULL');
        $ss->execute([$userId, $dayId]);
        foreach ($ss->fetchAll() as $seg) {
            // Auch Ruhe-Segmente sperren — sonst legt die naechste
            // Nachlieferung derselben Uhr sie wieder an.
            trash_block_ref($pdo, $seg, 'rest');
            $pdo->prepare("DELETE FROM track_points WHERE owner_type = 'rest' AND owner_id = ?")
                ->execute([(int)$seg['id']]);
            $pdo->prepare('DELETE FROM rest_segments WHERE id = ?')->execute([(int)$seg['id']]);
        }
        $pdo->prepare('DELETE FROM days WHERE user_id = ? AND id = ? AND deleted_at IS NOT NULL')
            ->execute([$userId, $dayId]);
        $pdo->commit();
    } catch (Throwable $ex) { $pdo->rollBack(); throw $ex; }
}

/* ---- Aufraeumjob: abgelaufene Papierkorb-Eintraege --------------------- */

function trash_purge_expired(PDO $pdo): void {
    /* Die Frist als Parameter, nicht im SQL-Text (M5-06).
     *
     * TRASH_DAYS ist eine Konstante dieser Datei — eingesetzt wurde also
     * nichts Fremdes. Trotzdem: Es waren die einzigen Stellen im Projekt,
     * an denen ein Wert im Anweisungstext stand, und "das ist eine
     * Konstante" muss man erst nachschlagen. INTERVAL nimmt keinen
     * Platzhalter, deshalb rechnet die Grenze hier in PHP. */
    $grenze = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->modify('-' . TRASH_DAYS . ' days')->format('Y-m-d H:i:s');

    // Tage zuerst (nimmt die daran haengenden Einsaetze/Segmente mit)
    $st = $pdo->prepare('SELECT id, user_id FROM days
                         WHERE deleted_at IS NOT NULL AND deleted_at < ?');
    $st->execute([$grenze]);
    foreach ($st->fetchAll() as $d) {
        trash_purge_day((int)$d['user_id'], (int)$d['id']);
    }
    // Einzeln geloeschte Einsaetze
    $st = $pdo->prepare('SELECT id, user_id FROM missions
                         WHERE deleted_at IS NOT NULL AND deleted_with_day = 0
                           AND deleted_at < ?');
    $st->execute([$grenze]);
    foreach ($st->fetchAll() as $m) {
        trash_purge_mission((int)$m['user_id'], (int)$m['id']);
    }
}

/* ---- Papierkorb-Inhalt fuer die Anzeige -------------------------------- */

function trash_list_days(int $userId): array {
    $st = db()->prepare(
        'SELECT d.id, d.day, d.started_at, d.kind, d.vehicle_name, d.base_name, d.deleted_at,
                (SELECT COUNT(*) FROM missions m
                  WHERE m.day_id = d.id
                    AND m.deleted_with_day = 1 AND m.deleted_at IS NOT NULL) AS einsaetze
           FROM days d
          WHERE d.user_id = ? AND d.deleted_at IS NOT NULL
          ORDER BY d.deleted_at DESC');
    $st->execute([$userId]);
    return $st->fetchAll();
}

/* Der Join auf `days` ist seit Web 6.0.0 der vorgesehene Weg (Konzept 4.11).
 * Die alte Regel "days und missions duerfen nie gejoint werden" entstand allein
 * aus den gleichnamigen crew_*-Spalten; mit deren Wegfall ist sie aufgehoben.
 * Hier wird sie gebraucht, weil der Papierkorb das Datum ANZEIGT, der Einsatz
 * es aber nicht mehr traegt. */
function trash_list_missions(int $userId): array {
    $st = db()->prepare(
        'SELECT m.id, m.day_id, d.day, m.started_at, m.deleted_at
           FROM missions m
           LEFT JOIN days d ON d.id = m.day_id
          WHERE m.user_id = ? AND m.deleted_at IS NOT NULL AND m.deleted_with_day = 0
          ORDER BY m.deleted_at DESC');
    $st->execute([$userId]);
    return $st->fetchAll();
}
