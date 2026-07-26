<?php
declare(strict_types=1);
/**
 * Einsatzort-Hoehe (missions.site_ele_m) — eine einzige Implementierung,
 * verwendet von:
 *   - ingest.php        (nach jedem Uhr-Upload eines Einsatzes)
 *   - einsatz_form.php  (nach manuellem Speichern, Phasen werden neu gesetzt)
 *   - backup_lib.php    (nach dem Wiedereinspielen eines Backups)
 *   - update.php        (einmaliger Backfill bei der Migration)
 *
 * Referenzzeitpunkt: Phase 5 "Ankunft PatientIn" (= PatientInnenkontakt),
 * Fallback Phase 6 "Transportbeginn". Fehlen beide, bleibt site_ele_m NULL
 * (keine Hoehenanzeige fuer diesen Einsatz).
 *
 * Wert = Hoehe (ele) des zeitlich naechstgelegenen Trackpunkts, sofern er
 * hoechstens SITE_ELE_TOLERANCE_S Sekunden vom Referenzzeitpunkt entfernt
 * liegt und selbst eine Hoehe hat. Kein Ausweichen auf Nachbarpunkte ohne
 * Hoehe in v1 (siehe Konzept, Abschnitt 5.1/7).
 */

const SITE_ELE_TOLERANCE_S = 300;

function compute_site_elevation(PDO $pdo, int $missionId): void {
    $ph = $pdo->prepare('SELECT occurred_at FROM mission_phases
                         WHERE mission_id = ? AND phase = ? ORDER BY occurred_at LIMIT 1');
    $ref = null;
    foreach ([5, 6] as $phase) {
        $ph->execute([$missionId, $phase]);
        $at = $ph->fetchColumn();
        if ($at !== false) { $ref = (string)$at; break; }
    }

    $ele = null;
    if ($ref !== null) {
        $refTs = (new DateTime($ref, new DateTimeZone('UTC')))->getTimestamp();
        $tp = $pdo->prepare("SELECT ele, ABS(ts - ?) AS diff FROM track_points
                             WHERE owner_type = 'mission' AND owner_id = ?
                             ORDER BY diff ASC LIMIT 1");
        $tp->execute([$refTs, $missionId]);
        $row = $tp->fetch();
        if ($row && $row['ele'] !== null && (int)$row['diff'] <= SITE_ELE_TOLERANCE_S) {
            $ele = (int)round((float)$row['ele']);
        }
    }

    $pdo->prepare('UPDATE missions SET site_ele_m = ? WHERE id = ?')
        ->execute([$ele, $missionId]);
}
