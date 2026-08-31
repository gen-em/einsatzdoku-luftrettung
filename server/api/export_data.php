<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';   // liefert $userId
require_once __DIR__ . '/../spur_lib.php';   // Spuren: Zeilen UND Blob (S2)

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
 *   { action:'track', owner_type:'mission'|'rest', ids:[42,43,...],
 *     patient: bool }
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
 * DAS FLAG HEISST 'patient', MEINT ABER MEHR (Web 5.8.0, Block A9).
 *
 * Der Schluessel im Request bleibt unveraendert, damit der Vertrag zwischen
 * assets/export.js und dieser Datei stabil bleibt. Was er ein- und
 * ausschliesst, ist seit A9 groesser: nicht mehr nur der pat_blob, sondern
 * personenbezogene Angaben insgesamt. Ohne das Flag liefert dieser Endpunkt
 *
 *   - keine Besatzungsnamen (day_crew, mission_crew),
 *   - kein bw_info ("Namen / Infos" der Bergwacht) und kein other_ema,
 *   - keine Notizen (missions.notes, days.notes),
 *   - keine Koordinaten der Phasen (lat/lon; die Zeitpunkte bleiben, sie
 *     tragen Alarm- und Endzeit) und kein site_ele_m,
 *   - keinen pat_blob,
 *   - und ueberhaupt keine Trackpunkte (action 'track' wird abgewiesen).
 *
 * WARUM DIE PHASENKOORDINATEN: Phase 4 ist "Ankunft Einsatzort", Phase 5
 * "Ankunft PatientIn". Diese Punkte SIND der Einsatzort. Bis Web 5.7.0 nannte
 * ein Export "ohne Patientendaten" ihn trotzdem, nur in einer anderen Spalte
 * als pat_ort_lat/lon. Dasselbe gilt fuer die GPX-Spuren, die dort enden.
 *
 * DRAUSSEN BLEIBEN bewusst transport_dest (eine Einrichtung, keine Person),
 * bw_unit (dito), weitere_rettungsmittel (Organisationskennungen) und der
 * Reanimationsverlauf. Vom Auftraggeber je einzeln entschieden; die
 * Begruendungen stehen in docs/Export-Format.md.
 *
 * Die Aufzaehlung ist nicht der einzige Ort, an dem die Schranke wirkt —
 * assets/export.js blendet dieselben Felder noch einmal aus. Das ist Absicht:
 * Wer hier eine Spalte ergaenzt und dort nicht, faellt auf; wer sich auf eine
 * der beiden Seiten allein verlaesst, nicht.
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

    /* Kurzname aus dem Request, erweiterte Bedeutung — siehe Kopf der Datei.
     * Die lokale Variable heisst deshalb NICHT $patient: Sie steuert seit A9
     * ein Dutzend Spalten, von denen nur eine dem Patienten gehoert. */
    $pers = !empty($b['patient']);

    /* Zeitraumfilter. Er greift ueber den DIENSTTAG (`days.day`), nicht ueber
     * das Einsatzdatum: Ein Export soll ganze Dienste liefern, nicht Dienste
     * halbieren, deren Nachteinsaetze in den Folgemonat fielen. Dieselbe Regel
     * wie in der Statistik (E14). Der Join ist seit Web 6.0.0 der vorgesehene
     * Weg (Konzept 4.11).
     *
     * $whereTag gilt fuer `days` (Alias d), $whereEins fuer die daran haengenden
     * Tabellen. Bis Web 5.10.0 war beides dieselbe Bedingung, weil jede Tabelle
     * ihr Datum selbst trug. */
    $whereTag  = ' AND d.deleted_at IS NULL';
    $whereEins = ' AND x.deleted_at IS NULL AND d.deleted_at IS NULL';
    $params = [$userId];
    if ($from !== null && $to !== null) {
        $whereTag  .= ' AND d.day BETWEEN ? AND ?';
        $whereEins .= ' AND d.day BETWEEN ? AND ?';
        $params[] = $from; $params[] = $to;
    }

    /* ---- Obergrenze pruefen (I: max. 5000 Einsaetze) ---------------------- */
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM missions x
                          JOIN days d ON d.id = x.day_id
                          WHERE x.user_id = ?$whereEins");
    $cnt->execute($params);
    if ((int)$cnt->fetchColumn() > 5000) {
        json_out(['error' => 'zu_gross',
                  'meldung' => 'Mehr als 5000 Einsätze im gewählten Zeitraum. '
                             . 'Bitte einen kleineren Zeitraum wählen.'], 413);
    }

    /* ---- Diensttage --------------------------------------------------------
     * BEZEICHNUNGEN KOMMEN AUS DEN SNAPSHOT-SPALTEN (E8), nicht aus den
     * Stammdaten. Der frueher noetige Join auf `aircraft` und `bases` ist damit
     * entfallen — und mit ihm die Luecke, dass ein geloeschtes Rettungsmittel
     * einen Export ohne Bezeichnung hinterliess. Ein Export mit IDs waere
     * ausserhalb dieser Anwendung ohnehin wertlos (SPEC_Export.md, 2.1).
     *
     * Die personenbezogenen Spalten werden ohne Flag durch NULL ersetzt, statt
     * sie aus der Spaltenliste zu nehmen: Der ANTWORTAUFBAU bleibt dadurch in
     * beiden Faellen gleich, und der Browser muss nicht zwei Formen kennen.
     * Aus der Datenbank kommen die Werte trotzdem nicht (SELECT NULL). */
    $st = $pdo->prepare(
        "SELECT d.id, d.day, d.started_at, d.ended_at, d.kind,
                d.vehicle_name, d.base_name" . ($pers ? ', d.notes' : ', NULL AS notes') . "
         FROM days d
         WHERE d.user_id = ?$whereTag
         ORDER BY d.day, d.started_at, d.id");
    $st->execute($params);
    $dayRows = $st->fetchAll();
    $dayIds  = array_map(static fn($r) => (int)$r['id'], $dayRows);

    /* Besatzung der Diensttage: eine Zeile je Rolle (E7). Gebuendelt in EINER
     * Abfrage, nicht je Tag — ein Jahresexport hat dreihundert Tage. Ohne das
     * Flag bleibt sie leer; sie ist personenbezogen. */
    $crewByDay = [];
    if ($pers && $dayIds) {
        foreach (sql_in_bloecken($pdo,
                'SELECT day_id, role_code, name FROM day_crew
                 WHERE day_id IN ({IDS})', $dayIds) as $r) {
            $crewByDay[(int)$r['day_id']][(string)$r['role_code']] = $r['name'];
        }
    }
    $capsByDay = [];
    if ($dayIds) {
        foreach (sql_in_bloecken($pdo,
                'SELECT day_id, capability FROM day_capabilities
                 WHERE day_id IN ({IDS})', $dayIds) as $r) {
            $capsByDay[(int)$r['day_id']][] = (string)$r['capability'];
        }
    }

    $days = array_map(static function ($r) use ($crewByDay, $capsByDay): array {
        $id = (int)$r['id'];
        return [
            'id'           => $id,
            'day'          => (string)$r['day'],
            'started_at'   => export_iso_utc($r['started_at']),
            'ended_at'     => export_iso_utc($r['ended_at']),
            'kind'         => $r['kind'] !== null ? (string)$r['kind'] : null,
            'vehicle'      => $r['vehicle_name'] !== null ? (string)$r['vehicle_name'] : null,
            'base'         => $r['base_name'] !== null ? (string)$r['base_name'] : null,
            'crew'         => (object)($crewByDay[$id] ?? []),
            'capabilities' => $capsByDay[$id] ?? [],
            'notes'        => $r['notes'],
        ];
    }, $dayRows);

    /* ---- Einsaetze ---------------------------------------------------------
     * Die personenbezogenen Spalten stehen nur in der Spaltenliste, wenn das
     * Flag gesetzt ist — sie werden sonst gar nicht erst aus der Datenbank
     * geholt, nicht nur beim Zusammenbau weggelassen. Bis Web 5.7.0 galt das
     * allein fuer pat_blob (A9).
     *
     * crew_override bleibt in beiden Faellen erhalten: Der Haken sagt nur,
     * DASS die Besatzung an diesem Einsatz von der des Diensttags abwich, nicht
     * wer geflogen ist. Ohne ihn liesse sich nicht mehr erkennen, dass die
     * leeren Namensspalten leer gemacht wurden und nicht leer waren. */
    $einsPersCols = $pers
        ? 'x.site_ele_m, x.bw_info, x.other_ema, x.notes, x.pat_blob'
        : 'NULL AS site_ele_m, NULL AS bw_info, NULL AS other_ema,
           NULL AS notes, NULL AS pat_blob';
    $st = $pdo->prepare(
        "SELECT x.id, x.day_id, d.day, x.started_at, x.ended_at,
                x.distance_m, x.ascent_m,
                x.final, x.manual, x.origin, x.edited, x.transport_dest, x.winch,
                x.transport_mode, x.na_escort, x.false_alarm,
                x.start_src, x.dest_lat, x.dest_lon,
                x.winch_cycles, x.winch_cycles_pat, x.winch_airload, x.bergwacht,
                x.bw_unit, x.secondary, x.schockraum,
                x.crew_override,
                $einsPersCols
         FROM missions x
         JOIN days d ON d.id = x.day_id
         WHERE x.user_id = ?$whereEins
         ORDER BY x.started_at");
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
        /* Zeitpunkt ja, Ort nein: Die Phasenzeiten tragen Alarmzeit, Endzeit
         * und Dauer und muessen deshalb in jedem Export stehen (Kriterium 73).
         * Die Koordinaten sind der Einsatzort (Phase 4/5) und fallen unter die
         * Schranke. */
        $phasenOrt = $pers ? 'lat, lon' : 'NULL AS lat, NULL AS lon';
        foreach (sql_in_bloecken($pdo,
                "SELECT mission_id, phase, occurred_at, $phasenOrt
                 FROM mission_phases WHERE mission_id IN ({IDS}) ORDER BY mission_id, occurred_at",
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

        /* Punktzahl je Einsatz ueber spur_lib.php (S2/AP1). Sie steuert den
         * GPX-Export: export.js fragt nur Einsaetze mit `track_points > 0`
         * ueberhaupt ab. Stuende hier nach der Verdichtung eine 0, fiele der
         * Einsatz still aus dem Export heraus — deshalb zaehlt jetzt eine
         * Stelle, die Zeilen UND Blob kennt. */
        $trackCountByMission = spur_zahlen($pdo, 'mission', $ids);

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

    /* Abweichende Besatzung je Einsatz: Zeilen aus `mission_crew` (E7),
     * gebuendelt wie alles andere hier. Personenbezogen, also nur mit Flag. */
    $crewByMission = [];
    if ($pers && $ids) {
        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, role_code, name FROM mission_crew
                 WHERE mission_id IN ({IDS})', $ids) as $r) {
            $crewByMission[(int)$r['mission_id']][(string)$r['role_code']] = $r['name'];
        }
    }

    $missions = [];
    foreach ($missionRows as $r) {
        $id = (int)$r['id'];
        $source = EXPORT_ORIGIN_LABEL[(string)$r['origin']] ?? 'uhr';

        $missions[] = [
            'id'               => $id,
            'day_id'           => (int)$r['day_id'],
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
            /* Die Felder der Etappe 2 (Web 6.1.0). Sie liegen im KLARTEXT und
             * brauchen deshalb keine Personenbezugs-Markierung: Transportart
             * und Fehleinsatz sind Aussagen ueber den Einsatz, die
             * Zielklinik-Koordinate steht wie ihr Name unverschluesselt (E40),
             * und `start_src` ist eine REGEL, kein Ort (Konzept 4.6.1). Der
             * manuelle Abfahrtort selbst liegt im pat_blob und geht mit ihm. */
            'transport_mode'   => $r['transport_mode'],
            'na_escort'        => (int)$r['na_escort'],
            'false_alarm'      => (int)$r['false_alarm'],
            'start_src'        => $r['start_src'],
            'dest_lat'         => $r['dest_lat'] !== null ? (float)$r['dest_lat'] : null,
            'dest_lon'         => $r['dest_lon'] !== null ? (float)$r['dest_lon'] : null,
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
            // Nur die Rollen, die tatsaechlich abweichen — `mission_crew` fuehrt
            // keine Leerzeilen (anders als `day_crew`, wo die Zeilenmenge den
            // Rollensatz bildet).
            'crew'             => (object)($crewByMission[$id] ?? []),
            'pat_blob'         => $pers && !empty($r['pat_blob']) ? (string)$r['pat_blob'] : null,
            'notes'            => $r['notes'],
            'track_points'     => $trackCountByMission[$id] ?? 0,
            'phases'           => $phasesByMission[$id] ?? [],
            'resources'        => $resourcesByMission[$id] ?? [],
            'resus'            => $resusSessionsByMission[$id] ?? [],
        ];
    }

    /* ---- Ruhesegmente -------------------------------------------------- */
    $st = $pdo->prepare(
        "SELECT x.id, x.day_id, d.day, x.started_at, x.ended_at, x.final
         FROM rest_segments x
         JOIN days d ON d.id = x.day_id
         WHERE x.user_id = ?$whereEins
         ORDER BY x.started_at");
    $st->execute($params);
    $restRows = $st->fetchAll();
    $restIds = array_map(static fn($r) => (int)$r['id'], $restRows);

    // Punktzahl je Ruhesegment ueber spur_lib.php (S2/AP1), wie oben.
    $trackCountByRest = $restIds ? spur_zahlen($pdo, 'rest', $restIds) : [];

    $rests = array_map(static fn($r) => [
        'id'           => (int)$r['id'],
        'day_id'       => (int)$r['day_id'],
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

    /* Trackpunkte sind personenbezogene Angaben (A9).
     *
     * Eine Flugspur endet am Einsatzort — sie nennt ihn genauer als jede
     * Koordinatenspalte und traegt zusaetzlich den Zeitverlauf dorthin. Ein
     * Export ohne personenbezogene Angaben darf sie deshalb nicht enthalten,
     * und die Schranke steht HIER und nicht nur im Browser: Sonst genuegte
     * eine Anfrage von Hand, um sie zu umgehen.
     *
     * assets/export.js bietet die GPX-Wahl in diesem Fall gar nicht erst an;
     * diese Pruefung ist die zweite Schranke, nicht die erste. */
    if (empty($b['patient'])) {
        json_out(['error'   => 'personenbezogen',
                  'meldung' => 'GPX-Spuren enden am Einsatzort und sind deshalb an '
                             . 'die personenbezogenen Angaben gebunden.'], 403);
    }

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
        /* UEBER spur_lib.php (S2/AP1) — Zeilen und Blob zusammen. Ausgabeform
         * unveraendert: [lat, lon, ele|null, ts] je Punkt, das ist die Vorlage
         * fuer <trkpt> in export.js. */
        foreach (spur_lesen_viele($pdo, $ownerType, $validIds) as $id => $punkte) {
            foreach ($punkte as $pt) {
                $result[(string)$id][] = [$pt[1], $pt[2], $pt[3], $pt[4]];
            }
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
