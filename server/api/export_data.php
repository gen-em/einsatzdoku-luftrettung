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

/**
 * Herkunft: missions.origin -> Wert der CSV-Spalte 'herkunft'.
 *
 * Der Wertevorrat der Exportspalte bleibt bewusst deutsch, obwohl die
 * Datenbank seit der Migration 2026_07_30_herkunft_bearbeitungsstatus
 * 'watch'/'manual'/'import' fuehrt: Bereits ausgelieferte Exportdateien tragen
 * die deutschen Werte, und jede darauf aufbauende Auswertung bliebe sonst
 * stehen.
 *
 * Bis Web 3.3.2 wurde der Wert stattdessen bei jedem Export aus 'manual' und
 * dem Praefix von 'client_ref' neu berechnet. Diese Regel stammt aus der Zeit
 * vor der Spalte 'origin' und lieferte fuer genau einen Fall etwas Falsches:
 * Ein von der Uhr aufgezeichneter und danach im Formular bearbeiteter Einsatz
 * bekommt 'manual = 1' (einsatz_form.php) und erschien deshalb als 'manuell',
 * obwohl 'origin' korrekt auf 'watch' stand. NICHT wieder einfuehren —
 * 'manual' bedeutet ausschliesslich "die Uhr ueberschreibt Metadaten, Phasen
 * und Reanimation nicht mehr" (schema.sql:50).
 *
 * Die gleichlautende Ableitungsregel in backup_lib.php bleibt bestehen: Dort
 * ist sie noetig, weil Backups der Formatversion 3 und aelter die Spalten
 * 'origin' und 'edited' nicht kennen.
 */
const EXPORT_ORIGIN_LABEL = [
    'watch'  => 'uhr',
    'manual' => 'manuell',
    'import' => 'import',
];

/* Die chunkweise IN(...)-Abfrage stand bis Web 4.5.3 HIER und war die einzige
 * Umsetzung im Projekt. Tagesansicht und Sicherung fragten stattdessen je
 * Datensatz einzeln — den Weg, den dieser Kommentar seit jeher beschreibt,
 * ist ihm niemand gefolgt, weil er nur hier zu finden war. Seit Web 4.6.0
 * steht er als sql_in_bloecken() in db.php und wird von allen drei Stellen
 * benutzt (M3-15, M5-12). */

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
    /* Ein halber Zeitraum ist keine Angabe (M3-07).
     *
     * Vorher griff der Filter nur, wenn BEIDE Grenzen gesetzt waren. Fehlte
     * eine, fiel die Bedingung stillschweigend weg — und statt der
     * angeforderten Auswahl kam DER GESAMTE BESTAND. Kein Fehler, keine
     * Meldung, nur eine viel groessere Datei als erwartet.
     *
     * Das ist die unangenehmste Sorte Fehler an einer Ausleitung: Wer "ab
     * 01.01.2026" eingibt und eine Datei mit allem seit 2019 bekommt, merkt es
     * unter Umstaenden erst, wenn die Datei bereits weitergegeben ist. Bei
     * Patientendaten ist das keine Kleinigkeit.
     *
     * Beide Grenzen leer heisst weiterhin "alles" — das ist eine bewusste
     * Angabe. Nur GENAU EINE Grenze wird abgelehnt. */
    if (($from === null) !== ($to === null)) {
        json_out(['error'   => 'zeitraum',
                  'meldung' => 'Für einen Zeitraum werden beide Grenzen gebraucht. '
                             . 'Bitte Von- und Bis-Datum angeben — oder beide leer '
                             . 'lassen, um den gesamten Bestand auszuleiten.'], 400);
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
                final, manual, origin, edited, transport_dest, winch,
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
        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, phase, occurred_at, lat, lon
                 FROM mission_phases WHERE mission_id IN ({IDS}) ORDER BY mission_id, occurred_at',
                $ids) as $r) {
            $phasesByMission[(int)$r['mission_id']][] = [
                'phase' => (int)$r['phase'],
                'at'    => export_iso_utc($r['occurred_at']),
                'lat'   => $r['lat'] !== null ? (float)$r['lat'] : null,
                'lon'   => $r['lon'] !== null ? (float)$r['lon'] : null,
            ];
        }

        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, name FROM mission_resources
                 WHERE mission_id IN ({IDS}) ORDER BY mission_id, id',
                $ids) as $r) {
            $resourcesByMission[(int)$r['mission_id']][] = (string)$r['name'];
        }

        foreach (sql_in_bloecken($pdo,
                "SELECT owner_id, COUNT(*) AS c FROM track_points
                 WHERE owner_type = 'mission' AND owner_id IN ({IDS}) GROUP BY owner_id",
                $ids) as $r) {
            $trackCountByMission[(int)$r['owner_id']] = (int)$r['c'];
        }

        $sessionRows = sql_in_bloecken($pdo,
            'SELECT id, mission_id, started_at FROM resus_sessions
             WHERE mission_id IN ({IDS}) ORDER BY mission_id, started_at',
            $ids);
        $sessionIds = array_map(static fn($r) => (int)$r['id'], $sessionRows);
        $eventsBySession = [];
        if ($sessionIds) {
            foreach (sql_in_bloecken($pdo,
                    'SELECT session_id, type, occurred_at FROM resus_events
                     WHERE session_id IN ({IDS}) ORDER BY session_id, occurred_at',
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
        $source = EXPORT_ORIGIN_LABEL[(string)$r['origin']] ?? 'uhr';

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
            'edited'           => (int)$r['edited'],
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
        foreach (sql_in_bloecken($pdo,
                "SELECT owner_id, COUNT(*) AS c FROM track_points
                 WHERE owner_type = 'rest' AND owner_id IN ({IDS}) GROUP BY owner_id",
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
    $owned = sql_in_bloecken($pdo,
        "SELECT id FROM `$table` WHERE user_id = ? AND deleted_at IS NULL AND id IN ({IDS})",
        $ids, [$userId]);
    $validIds = array_map(static fn($r) => (int)$r['id'], $owned);

    $result = [];
    foreach ($validIds as $id) { $result[(string)$id] = []; }

    if ($validIds) {
        $rows = sql_in_bloecken($pdo,
            "SELECT owner_id, lat, lon, ele, ts FROM track_points
             WHERE owner_type = ? AND owner_id IN ({IDS}) ORDER BY owner_id, seq",
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
    json_fehler($ex, 'export');
}
