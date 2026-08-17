<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';

/**
 * Einsaetze eines Jahres oder Monats — Grundlage der Zeitraum-Uebersicht.
 * Bewusst OHNE Trackpunkte: Bei einem ganzen Jahr waeren das schnell
 * hunderttausende Koordinaten. Die Kartenansicht (Einsatzort-Pins) kommt
 * stattdessen aus den Koordinaten im `pat_blob`, die der Browser fuer die
 * Tabellenspalten ohnehin entschluesselt. Verschluesselte Angaben gehen wie
 * ueberall als `pat_blob` an den Browser, der sie selbst entschluesselt.
 */

/* Nur lesen (M3-11). Die Uebersicht war fuer jede Methode offen — ein POST
 * mit einem Formular von fremder Seite bekam dieselbe Antwort wie ein GET.
 * Gelesen wird dabei nichts Fremdes (die Abfrage haengt an $userId), aber
 * ein lesender Endpunkt, der POST beantwortet, ist eine Einladung, die
 * niemand aussprechen wollte. */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { json_out(['error' => 'method'], 405); }

header('Content-Type: application/json; charset=utf-8');

$jahr  = (string)($_GET['y'] ?? '');
$monat = (string)($_GET['m'] ?? '');
if (!preg_match('/^\d{4}$/', $jahr)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiges Jahr']);
    exit;
}
/* Der Wertebereich zaehlt, nicht nur die Ziffernzahl (M3-06).
 *
 * Vorher genuegten zwei Ziffern. "00" und "13" kamen damit durch, und dann
 * ging es schief: strtotime('2026-00-01') scheitert und liefert false,
 * date('Y-m-t', false) rechnet mit dem Zeitpunkt 0 weiter. Herausgekommen ist
 * kein Fehler, sondern ein FALSCHER ZEITRAUM — bei m=00 der Dezember des
 * Vorjahres. Eine Uebersicht, die stillschweigend einen anderen Monat zeigt
 * als den angefragten, ist schlimmer als eine, die sich weigert. */
if ($monat !== '' && !preg_match('/^(0[1-9]|1[0-2])$/', $monat)) {
    http_response_code(400);
    echo json_encode(['error' => 'Ungültiger Monat']);
    exit;
}

if ($monat !== '') {
    $von  = sprintf('%s-%s-01', $jahr, $monat);
    $bis  = date('Y-m-t', strtotime($von));
} else {
    $von = $jahr . '-01-01';
    $bis = $jahr . '-12-31';
}

try {
    /* DIE STATISTIK RECHNET NACH DIENSTTAG (E14). Der Zeitraum filtert deshalb
     * `days.day`, nicht `missions.started_at`: Ein Einsatz um 01:30 eines
     * Dienstes, der am Vortag begonnen hat, zaehlt zum Vortag — und faellt am
     * Monatsersten damit noch in den Vormonat. Die Einsatzsuche macht es
     * ausdruecklich anders (api/suchindex.php); der Unterschied ist gewollt und
     * im Handbuch erklaert.
     *
     * Der Join auf `days` ist seit Web 6.0.0 der vorgesehene Weg (Konzept
     * 4.11). Bis dahin trug jeder Einsatz sein Tagesdatum selbst. */
    $st = db()->prepare('SELECT m.id, m.day_id, d.day, m.started_at, m.distance_m,
                           m.winch, m.bergwacht, m.secondary, m.winch_cycles,
                           m.site_ele_m, m.pat_blob,
                           (SELECT MAX(occurred_at) FROM mission_phases p
                            WHERE p.mission_id = m.id AND p.phase = 9) AS p9_at
                         FROM missions m
                         JOIN days d ON d.id = m.day_id
                         WHERE m.user_id = ? AND d.day BETWEEN ? AND ?
                           AND m.deleted_at IS NULL AND d.deleted_at IS NULL
                         ORDER BY m.started_at');
    $st->execute([$userId, $von, $bis]);

    $missions = [];
    foreach ($st->fetchAll() as $m) {
        $dur = null;
        if ($m['p9_at'] !== null) {
            $dur = (new DateTime($m['p9_at']))->getTimestamp()
                 - (new DateTime($m['started_at']))->getTimestamp();
        }
        $missions[] = [
            'id'         => (int)$m['id'],
            'day_id'     => (int)$m['day_id'],
            'day'        => (string)$m['day'],
            'start_hhmm' => fmt_local($m['started_at']),
            'duration_s' => $dur,
            'distance_m' => $m['distance_m'] !== null ? (int)$m['distance_m'] : null,
            'winch'      => (int)$m['winch'] === 1,
            'bergwacht'  => (int)$m['bergwacht'] === 1,
            'secondary'  => (int)$m['secondary'] === 1,
            'winch_cycles' => $m['winch_cycles'] !== null ? (int)$m['winch_cycles'] : null,
            'site_ele_m'   => $m['site_ele_m']   !== null ? (int)$m['site_ele_m']   : null,
            'pat_blob'   => !empty($m['pat_blob']) ? (string)$m['pat_blob'] : null,
        ];
    }

    // Kennzahl 'tage': alle im Zeitraum ANGELEGTEN Diensttage, auch ohne Einsatz —
    // bewusste Semantikaenderung (vorher: COUNT(DISTINCT day) aus missions, zaehlte
    // also nur Tage mit dokumentiertem Einsatz). Divisor der Durchschnittswerte
    // in der Statistiktabelle der Zeitraum-Uebersicht.
    //
    // Gezaehlt werden ZEILEN, nicht Kalendertage: Zwei Dienste an einem Tag sind
    // seit E9 zwei Diensttage, und ein Durchschnitt „Einsaetze je Diensttag"
    // waere sonst um den Faktor der Doppeltage zu hoch.
    $tage = db()->prepare('SELECT COUNT(*) FROM days
                           WHERE user_id = ? AND day BETWEEN ? AND ? AND deleted_at IS NULL');
    $tage->execute([$userId, $von, $bis]);

    echo json_encode([
        'jahr'     => $jahr,
        'monat'    => $monat !== '' ? $monat : null,
        'von'      => $von,
        'bis'      => $bis,
        'tage'     => (int)$tage->fetchColumn(),
        'missions' => $missions,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $ex) {
    // Statt eines leeren HTTP 500 (z. B. fehlende Spalte nach vergessener
    // Migration) eine lesbare Fehlermeldung — das Frontend zeigt sie an.
    json_fehler($ex, 'range');
}
