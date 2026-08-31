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

require_once __DIR__ . '/spur_lib.php';

function compute_site_elevation(PDO $pdo, int $missionId): void {
    $ph = $pdo->prepare('SELECT occurred_at FROM mission_phases
                         WHERE mission_id = ? AND phase = ? ORDER BY occurred_at LIMIT 1');
    $ref = null;
    foreach ([5, 6] as $phase) {
        $ph->execute([$missionId, $phase]);
        $at = $ph->fetchColumn();
        if ($at !== false) { $ref = (string)$at; break; }
    }

    /* DIE SUCHE LAEUFT SEIT S2 UEBER spur_lib.php, nicht mehr per SQL.
     *
     * Zwei Gruende, und der zweite ist der wichtigere:
     *
     * 1. Sobald die Punkte im Blob liegen, findet die alte Abfrage nichts
     *    mehr. Sie liefe nicht auf einen Fehler, sondern auf `null` — der
     *    Einsatz haette dann keine Ortshoehe mehr, und niemand erfuehre,
     *    warum.
     * 2. Die alte Abfrage sortierte nach `ABS(ts - ?)`, einem BERECHNETEN
     *    Ausdruck. Kein Index traegt den; MySQL sortierte also jedes Mal alle
     *    Punkte des Einsatzes. Hier ist es eine Schleife ueber eine Liste,
     *    die ohnehin schon im Speicher liegt.
     *
     * Die Regel selbst ist unveraendert: der zeitlich naechstgelegene Punkt,
     * hoechstens SITE_ELE_TOLERANCE_S entfernt, und er muss selbst eine Hoehe
     * haben. Kein Ausweichen auf Nachbarn ohne Hoehe. */
    $ele = null;
    if ($ref !== null) {
        $refTs = (new DateTime($ref, new DateTimeZone('UTC')))->getTimestamp();
        $besterAbstand = PHP_INT_MAX;
        $besteHoehe = null;
        foreach (spur_lesen($pdo, 'mission', $missionId) as $p) {
            $d = abs($p[4] - $refTs);
            if ($d < $besterAbstand) {
                $besterAbstand = $d;
                $besteHoehe = $p[3];
            }
        }
        if ($besteHoehe !== null && $besterAbstand <= SITE_ELE_TOLERANCE_S) {
            $ele = (int)round($besteHoehe);
        }
    }

    $pdo->prepare('UPDATE missions SET site_ele_m = ? WHERE id = ?')
        ->execute([$ele, $missionId]);
}
