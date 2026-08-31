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

    /* AUF EINER AUSGEDUENNTEN SPUR WIRD KEIN VORHANDENER WERT GELOESCHT
     * (S2/AP3).
     *
     * Diese Funktion laeuft bei JEDEM Speichern im Einsatzformular, und sie
     * schreibt bedingungslos — auch NULL. Auf einer Spur der Stufe 3 ist das
     * eine Falle: Die behaltenen Punkte wurden fuer die DAMALIGEN
     * Phasenzeiten geschuetzt (E-S2-05). Wer einen zwei Jahre alten Einsatz
     * oeffnet und Phase 5 um zehn Minuten verschiebt, findet im
     * 300-Sekunden-Fenster moeglicherweise keinen behaltenen Punkt mehr — und
     * verloere die Ortshoehe still, obwohl er nur eine Zeit berichtigt hat.
     *
     * Deshalb: Ein NEUER Wert wird immer geschrieben (auch auf Stufe 3 — eine
     * nachgetragene Phase soll ihre Hoehe bekommen). Ein vorhandener Wert
     * wird auf Stufe 3 aber NICHT durch NULL ersetzt. Auf Stufe 1 und 2 bleibt
     * es beim bisherigen Verhalten: Dort traegt die Spur alle Punkte, ein
     * leeres Ergebnis ist also die Wahrheit.
     *
     * Die Grenze davon steht im Handbuch: Nach der Ausduennung sagt eine
     * geaenderte Phasenzeit ueber die Ortshoehe nichts mehr. */
    if ($ele === null) {
        $stand = spur_stand($pdo, 'mission', $missionId);
        if (spur_ist_ausgeduennt($stand)) {
            $q = $pdo->prepare('SELECT site_ele_m FROM missions WHERE id = ?');
            $q->execute([$missionId]);
            $alt = $q->fetchColumn();
            if ($alt !== null && $alt !== false) { return; }
        }
    }

    $pdo->prepare('UPDATE missions SET site_ele_m = ? WHERE id = ?')
        ->execute([$ele, $missionId]);
}
