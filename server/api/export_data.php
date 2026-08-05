<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';   // liefert $userId

/**
 * Export: Rohdaten fuer alle drei Export-Profile (nur lesend).
 *
 * POST api/export_data.php   JSON-Body, Header X-CSRF wie bei api/import_commit.php
 *
 *   { action:'meta', from:'YYYY-MM-DD'|null, to:'YYYY-MM-DD'|null,
 *     patient: bool }
 *
 * Antwort:
 *   { days: [...], missions: [...], rests: [...] }
 *
 *   { action:'track', owner_type:'mission'|'rest', ids:[42,43,...] }
 *
 * Antwort:
 *   { "42": [ [lat, lon, ele|null, ts], ... ] }
 *
 * WARUM EIN EIGENER ENDPUNKT: api/range.php bedient zeitraum.php und ist
 * bewusst schlank gehalten (keine Trackpunkte, kein Reanimationsdetail).
 * Eine Erweiterung dort wuerde diese Seite mitveraendern (SPEC_Export.md,
 * Abschnitt 2).
 *
 * ANNAHME ZU 'patient': Die Anfrage-Skizze in SPEC_Export.md, Abschnitt 2.1
 * nennt nur from/to, aber Abschnitt "Festlegungen" verlangt ausdruecklich,
 * dass pat_blob bei fehlendem Haken schon HIER nicht mitgesendet wird ("nicht
 * erst im Browser weggelassen"). Das Flag 'patient' im Request ist daher die
 * naheliegende Ergaenzung dieser Luecke und wird hier so umgesetzt — bitte
 * bei der Abnahme von E1 gegenpruefen.
 *
 * mission_no: Seit der Migration 2026_07_29_einsatznummer_verschluesselt hat
 * missions KEINE mission_no-Spalte mehr (SPEC_Export.md, Abschnitt 10). Das
 * Feld "mission_no":null im Beispiel unter 2.1 ist daher ein Rest aus einer
 * frueheren Fassung und wird hier NICHT als eigenes Feld ausgegeben — die
 * Einsatznummer steckt ausschliesslich verschluesselt im pat_blob.
 *
 * I1 (Patientendaten verlassen den Browser nur verschluesselt): pat_blob wird
 * unveraendert als Chiffretext durchgereicht, nie entschluesselt.
 * I3 (Papierkorb bleibt draussen) und I4 (Datentrennung): jede Abfrage filtert
 * deleted_at IS NULL und user_id = ?.
 */

/** Chunkweises IN(...) fuer grosse ID-Listen (Sicherheitsabstand zu
 *  MySQL/MariaDB-Parametergrenzen und zu grossen Einzel-Statements). */
function export_fetch_chunked(PDO $pdo, string $sqlTemplate, array $ids, array $leadParams = []): array
{
    $out = [];
    foreach (array_chunk($ids, 1000) as $chunk) {
        $platz = implode(',', array_fill(0, count($chunk), '?'));
        $st = $pdo->prepare(sprintf($sqlTemplate, $platz));
        $st->execute(array_merge($leadParams, $chunk));
        foreach ($st->fetchAll() as $row) { $out[] = $row; }
    }
    return $out;
}

/** UTC-DATETIME (aus DB, ohne Zonenangabe) -> ISO 8601 mit 'Z'. */
function export_iso_utc(?string $utc): ?string
{
    if ($utc === null || $utc === '') { return null; }
    $dt = new DateTime($utc, new DateTimeZone('UTC'));
    return $dt->format('Y-m-d\TH:i:s\Z');
}

function export_meta(array $b, int $userId): never
{
    $pdo = db();

    $from = $b['from'] ?? null;
    $to   = $b['to'] ?? null;
    $from = ($from === null || $from === '') ? null : (string)$from;
    $to   = ($to   === null || $to   === '') ? null : (string)$to;
    if ($from !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        json_out(['error' => 'zeitraum', 'meldung' => 'Ungültiges Von-Datum.'], 400);
    }
    if ($to !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        json_out(['error' => 'zeitraum', 'meldung' => 'Ungültiges Bis-Datum.'], 400);
    }
    $patient = !empty($b['patient']);

    $whereTag = ' AND deleted_at IS NULL';
    $params = [$userId];
    if ($from !== null && $to !== null) {
        $whereTag .= ' AND day BETWEEN ? AND ?';
        $params[] = $from; $params[] = $to;
    }

    /* ---- Obergrenze pruefen (I: max. 5000 Einsaetze) ---------------------- */
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM missions WHERE user_id = ?$whereTag");
    $cnt->execute($params);
    if ((int)$cnt->fetchColumn() > 5000) {
        json_out(['error' => 'zu_gross',
                  'meldung' => 'Mehr als 5000 Einsätze im gewählten Zeitraum. '
                             . 'Bitte einen kleineren Zeitraum wählen.'], 413);
    }

    /* ---- Flugtage ----------------------------------------------------------
     * Aufloesung von aircraft_id/base_id zu Klartextnamen — ein Export mit
     * IDs waere ausserhalb dieser Anwendung wertlos (SPEC_Export.md, 2.1). */
    $st = $pdo->prepare(
        "SELECT d.day, a.registration AS aircraft, b.name AS base,
                d.crew_p1, d.crew_p2, d.crew_hems, d.crew_fr, d.crew_other, d.notes
         FROM days d
         LEFT JOIN aircraft a ON a.id = d.aircraft_id
         LEFT JOIN bases b    ON b.id = d.base_id
         WHERE d.user_id = ?$whereTag
         ORDER BY d.day");
    $st->execute($params);
    $days = array_map(static fn($r) => [
        'day'         => (string)$r['day'],
        'aircraft'    => $r['aircraft'] !== null ? (string)$r['aircraft'] : null,
        'base'        => $r['base'] !== null ? (string)$r['base'] : null,
        'crew_p1'     => $r['crew_p1'],
        'crew_p2'     => $r['crew_p2'],
        'crew_hems'   => $r['crew_hems'],
        'crew_fr'     => $r['crew_fr'],
        'crew_other'  => $r['crew_other'],
        'notes'       => $r['notes'],
    ], $st->fetchAll());

    /* ---- Einsaetze ---------------------------------------------------------
     * pat_blob nur in der Spaltenliste, wenn 'patient' gesetzt ist — das Feld
     * wird sonst gar nicht erst aus der Datenbank geholt, nicht nur beim
     * Zusammenbau weggelassen. */
    $patCol = $patient ? ', pat_blob' : '';
    $st = $pdo->prepare(
        "SELECT id, day, started_at, ended_at, distance_m, ascent_m, site_ele_m,
                final, manual, client_ref, transport_dest, winch,
                winch_cycles, winch_cycles_pat, winch_airload, bergwacht,
                bw_unit, bw_info, secondary, schockraum, other_ema,
                crew_override, crew_p1, crew_p2, crew_hems, crew_fr, crew_other,
                notes$patCol
         FROM missions
         WHERE user_id = ?$whereTag
         ORDER BY started_at");
    $st->execute($params);
    $missionRows = $st->fetchAll();

    $ids = array_map(static fn($r) => (int)$r['id'], $missionRows);

    /* ---- Phasen, Rettungsmittel, Reanimation, Trackpunkt-Anzahl: gebuendelt
     * statt je Einsatz einzeln (bis zu 5000 Einsaetze). -------------------- */
    $phasesByMission = [];
    $resourcesByMission = [];
    $trackCountByMission = [];
    $resusSessionsByMission = [];

    if ($ids) {
        foreach (export_fetch_chunked($pdo,
                'SELECT mission_id, phase, occurred_at, lat, lon
                 FROM mission_phases WHERE mission_id IN (%s) ORDER BY mission_id, occurred_at',
                $ids) as $r) {
            $phasesByMission[(int)$r['mission_id']][] = [
                'phase' => (int)$r['phase'],
                'at'    => export_iso_utc($r['occurred_at']),
                'lat'   => $r['lat'] !== null ? (float)$r['lat'] : null,
                'lon'   => $r['lon'] !== null ? (float)$r['lon'] : null,
            ];
        }

        foreach (export_fetch_chunked($pdo,
                'SELECT mission_id, name FROM mission_resources
                 WHERE mission_id IN (%s) ORDER BY mission_id, id',
                $ids) as $r) {
            $resourcesByMission[(int)$r['mission_id']][] = (string)$r['name'];
        }

        foreach (export_fetch_chunked($pdo,
                "SELECT owner_id, COUNT(*) AS c FROM track_points
                 WHERE owner_type = 'mission' AND owner_id IN (%s) GROUP BY owner_id",
                $ids) as $r) {
            $trackCountByMission[(int)$r['owner_id']] = (int)$r['c'];
        }

        $sessionRows = export_fetch_chunked($pdo,
            'SELECT id, mission_id, started_at FROM resus_sessions
             WHERE mission_id IN (%s) ORDER BY mission_id, started_at',
            $ids);
        $sessionIds = array_map(static fn($r) => (int)$r['id'], $sessionRows);
        $eventsBySession = [];
        if ($sessionIds) {
            foreach (export_fetch_chunked($pdo,
                    'SELECT session_id, type, occurred_at FROM resus_events
                     WHERE session_id IN (%s) ORDER BY session_id, occurred_at',
                    $sessionIds) as $r) {
                $eventsBySession[(int)$r['session_id']][] = [
                    'type' => (string)$r['type'],
                    'at'   => export_iso_utc($r['occurred_at']),
                ];
            }
        }
        foreach ($sessionRows as $r) {
            $sid = (int)$r['id'];
            $resusSessionsByMission[(int)$r['mission_id']][] = [
                'started_at' => export_iso_utc($r['started_at']),
                'events'     => $eventsBySession[$sid] ?? [],
            ];
        }
    }

    $missions = [];
    foreach ($missionRows as $r) {
        $id = (int)$r['id'];
        $manual = (int)$r['manual'] === 1;
        if ($manual && strncmp((string)$r['client_ref'], 'imp-', 4) === 0) {
            $source = 'import';
        } elseif ($manual) {
            $source = 'manuell';
        } else {
            $source = 'uhr';
        }

        $missions[] = [
            'id'               => $id,
            'day'              => (string)$r['day'],
            'started_at'       => export_iso_utc($r['started_at']),
            'ended_at'         => export_iso_utc($r['ended_at']),
            'distance_m'       => $r['distance_m'] !== null ? (int)$r['distance_m'] : null,
            'ascent_m'         => $r['ascent_m'] !== null ? (int)$r['ascent_m'] : null,
            'site_ele_m'       => $r['site_ele_m'] !== null ? (int)$r['site_ele_m'] : null,
            'final'            => (int)$r['final'],
            'manual'           => (int)$r['manual'],
            'source'           => $source,
            'transport_dest'   => $r['transport_dest'],
            'winch'            => (int)$r['winch'],
            'winch_cycles'     => $r['winch_cycles'] !== null ? (int)$r['winch_cycles'] : null,
            'winch_cycles_pat' => $r['winch_cycles_pat'] !== null ? (int)$r['winch_cycles_pat'] : null,
            'winch_airload'    => (int)$r['winch_airload'],
            'bergwacht'        => (int)$r['bergwacht'],
            'bw_unit'          => $r['bw_unit'],
            'bw_info'          => $r['bw_info'],
            'secondary'        => (int)$r['secondary'],
            'schockraum'       => (int)$r['schockraum'],
            'other_ema'        => $r['other_ema'],
            'crew_override'    => (int)$r['crew_override'],
            'crew_p1'          => $r['crew_p1'],
            'crew_p2'          => $r['crew_p2'],
            'crew_hems'        => $r['crew_hems'],
            'crew_fr'          => $r['crew_fr'],
            'crew_other'       => $r['crew_other'],
            'pat_blob'         => $patient && !empty($r['pat_blob']) ? (string)$r['pat_blob'] : null,
            'notes'            => $r['notes'],
            'track_points'     => $trackCountByMission[$id] ?? 0,
            'phases'           => $phasesByMission[$id] ?? [],
            'resources'        => $resourcesByMission[$id] ?? [],
            'resus'            => $resusSessionsByMission[$id] ?? [],
        ];
    }

    /* ---- Ruhesegmente -------------------------------------------------- */
    $st = $pdo->prepare(
        "SELECT id, day, started_at, ended_at, final
         FROM rest_segments
         WHERE user_id = ?$whereTag
         ORDER BY started_at");
    $st->execute($params);
    $restRows = $st->fetchAll();
    $restIds = array_map(static fn($r) => (int)$r['id'], $restRows);

    $trackCountByRest = [];
    if ($restIds) {
        foreach (export_fetch_chunked($pdo,
                "SELECT owner_id, COUNT(*) AS c FROM track_points
                 WHERE owner_type = 'rest' AND owner_id IN (%s) GROUP BY owner_id",
                $restIds) as $r) {
            $trackCountByRest[(int)$r['owner_id']] = (int)$r['c'];
        }
    }

    $rests = array_map(static fn($r) => [
        'id'           => (int)$r['id'],
        'day'          => (string)$r['day'],
        'started_at'   => export_iso_utc($r['started_at']),
        'ended_at'     => export_iso_utc($r['ended_at']),
        'final'        => (int)$r['final'],
        'track_points' => $trackCountByRest[(int)$r['id']] ?? 0,
    ], $restRows);

    json_out(['days' => $days, 'missions' => $missions, 'rests' => $rests]);
}

function export_track(array $b, int $userId): never
{
    $pdo = db();

    $ownerType = (string)($b['owner_type'] ?? '');
    if (!in_array($ownerType, ['mission', 'rest'], true)) {
        json_out(['error' => 'owner_type'], 400);
    }

    $ids = [];
    foreach ((array)($b['ids'] ?? []) as $v) {
        $n = (int)$v;
        if ($n > 0) { $ids[$n] = true; }
    }
    $ids = array_keys($ids);
    if (count($ids) > 25) {
        json_out(['error' => 'zu_viele_ids',
                  'meldung' => 'Höchstens 25 IDs je Anfrage.'], 400);
    }
    if (!$ids) {
        header('Content-Type: application/json');
        echo '{}';
        exit;
    }

    // Datentrennung + Papierkorb: nur IDs zulassen, die dem Konto gehoeren
    // und nicht geloescht sind (I3, I4).
    $table = $ownerType === 'mission' ? 'missions' : 'rest_segments';
    $owned = export_fetch_chunked($pdo,
        "SELECT id FROM `$table` WHERE user_id = ? AND deleted_at IS NULL AND id IN (%s)",
        $ids, [$userId]);
    $validIds = array_map(static fn($r) => (int)$r['id'], $owned);

    $result = [];
    foreach ($validIds as $id) { $result[(string)$id] = []; }

    if ($validIds) {
        $rows = export_fetch_chunked($pdo,
            "SELECT owner_id, lat, lon, ele, ts FROM track_points
             WHERE owner_type = ? AND owner_id IN (%s) ORDER BY owner_id, seq",
            $validIds, [$ownerType]);
        foreach ($rows as $r) {
            $result[(string)(int)$r['owner_id']][] = [
                (float)$r['lat'],
                (float)$r['lon'],
                $r['ele'] !== null ? (float)$r['ele'] : null,
                (int)$r['ts'],
            ];
        }
    }

    header('Content-Type: application/json');
    echo $result ? json_encode($result) : '{}';
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['error' => 'method'], 405);
    }
    if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
        json_out(['error' => 'csrf'], 403);
    }

    $b = json_decode(file_get_contents('php://input'), true);
    if (!is_array($b)) { json_out(['error' => 'payload'], 400); }

    $action = (string)($b['action'] ?? '');
    if ($action === 'meta') { export_meta($b, $userId); }
    if ($action === 'track') { export_track($b, $userId); }
    json_out(['error' => 'action'], 400);
} catch (Throwable $ex) {
    // Lesbare Meldung statt leerem HTTP 500 — Muster wie in api/import_commit.php.
    json_out(['error' => 'export', 'meldung' => $ex->getMessage()], 500);
}
