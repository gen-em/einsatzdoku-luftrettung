<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../mission_fields_lib.php';   // mf_spalten()   // liefert $userId
require_once __DIR__ . '/../spur_lib.php';   // Spuren: Zeilen UND Blob

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
 *   { "42": { ref: 'm-…', stuecke: [{seq_from, punkte, …, huelle}],
 *             sperren: [{von, bis}] } }
 *
 * WARUM EIN EIGENER ENDPUNKT: api/range.php bedient zeitraum.php und ist
 * bewusst schlank gehalten (keine Trackpunkte, kein Reanimationsdetail).
 * Eine Erweiterung dort wuerde diese Seite mitveraendern.
 *
 * WARUM 'patient' IM REQUEST: Ohne den Haken wird pat_blob schon HIER nicht
 * mitgesendet, nicht erst im Browser weggelassen. Deshalb traegt die Anfrage
 * neben from/to das Flag 'patient'.
 *
 * DAS FLAG HEISST 'patient', MEINT ABER MEHR.
 *
 * Der Schluessel im Request bleibt unveraendert, damit der Vertrag zwischen
 * assets/export.js und dieser Datei stabil bleibt. Was er ein- und
 * ausschliesst, ist groesser: nicht nur der pat_blob, sondern
 * personenbezogene Angaben insgesamt. Ohne das Flag liefert dieser Endpunkt
 *
 *   - keine Besatzungsnamen (day_crew, mission_crew),
 *   - kein bw_info ("Namen / Infos" der Bergwacht) und kein other_ema,
 *   - keine Tagesnotizen (days.notes) — die Notizen des EINSATZES stehen
 *     im `pat_blob` und reisen mit ihm: Ohne Flag kommt kein Blob mit,
 *     und mit Flag kommt er als Chiffretext, den nur der Browser oeffnet,
 *   - keine Koordinaten der Phasen (lat/lon; die Zeitpunkte bleiben, sie
 *     tragen Alarm- und Endzeit) und kein site_ele_m,
 *   - keinen pat_blob,
 *   - und ueberhaupt keine Trackpunkte (action 'track' wird abgewiesen).
 *
 * WARUM DIE PHASENKOORDINATEN: Phase 4 ist "Ankunft Einsatzort", Phase 5
 * "Ankunft PatientIn". Diese Punkte SIND der Einsatzort. Ein Export "ohne
 * Patientendaten" nennte ihn sonst trotzdem, nur in einer anderen Spalte
 * als pat_ort_lat/lon. Dasselbe gilt fuer die GPX-Spuren, die dort enden.
 *
 * DRAUSSEN BLEIBEN bewusst bw_unit (eine Einrichtung, keine Person) und
 * weitere_rettungsmittel (Organisationskennungen). Transportziel und
 * Reanimationsverlauf gehoeren NICHT dazu: Das Ziel liegt im pat_blob, die
 * Reanimation in der Einsatzhuelle, und beide gehen nur mit Haken mit. Je
 * einzeln entschieden; die Begruendungen stehen in docs/Export-Format.md.
 *
 * Die Aufzaehlung ist nicht der einzige Ort, an dem die Schranke wirkt —
 * assets/export.js blendet dieselben Felder noch einmal aus. Das ist Absicht:
 * Wer hier eine Spalte ergaenzt und dort nicht, faellt auf; wer sich auf eine
 * der beiden Seiten allein verlaesst, nicht.
 *
 * mission_no: missions hat KEINE mission_no-Spalte. Die Einsatznummer steckt
 * ausschliesslich verschluesselt im pat_blob und wird hier NICHT als eigenes
 * Feld ausgegeben.
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
 * Der Wert wird NICHT aus der Uhr-Sperre und dem Praefix von 'client_ref'
 * berechnet. Diese Regel liefert fuer genau einen Fall etwas Falsches: Ein
 * von der Uhr aufgezeichneter und danach im Formular bearbeiteter Einsatz
 * bekommt 'uhr_gesperrt = 1' (einsatz_form.php) und erschiene deshalb als
 * 'manuell', obwohl 'origin' korrekt auf 'watch' steht. Die Spalte bedeutet
 * ausschliesslich "die Uhr ueberschreibt Metadaten, Phasen und Reanimation
 * nicht mehr"; in der EXPORTDATEI heisst das Feld 'manual'.
 *
 * DIE ABLEITUNGSREGEL STEHT AN EINER STELLE:
 * `herkunft_ableiten()` in geraete_lib.php. Beim Einspielen wird sie
 * gebraucht, weil Backups der Formatversion 3 und aelter die Spalten 'origin'
 * und 'edited' nicht kennen; HIER wird sie nicht gebraucht und nicht
 * angewendet — die Spalte steht in der Datenbank.
 */
const EXPORT_ORIGIN_LABEL = [
    /* Die drei alten Werte bleiben WORTGLEICH — daran haengt die Zusage oben.
     * Die drei neuen sind so kurz und deutsch wie sie:
     * `handy` fuer die Android-App, `wear` fuer den Einsatz, der an der
     * Wear-OS-Uhr begonnen und vom Handy gesendet wurde, `schnitt` fuer den,
     * der aus einem Ruhesegment herausgeschnitten wurde.
     *
     * WARUM `wear` UND NICHT `uhr`: `uhr` ist seit dem ersten Export die
     * Garmin-App. Eine zweite Uhr unter demselben Wort machte jede
     * Auswertung, die auf `uhr` filtert, rueckwirkend mehrdeutig — und zwar
     * ohne dass sich das der Datei ansehen liesse. */
    'watch'   => 'uhr',
    'android' => 'handy',
    'wear'    => 'wear',
    'manual'  => 'manuell',
    'import'  => 'import',
    'schnitt' => 'schnitt',
];

/* Die chunkweise IN(...)-Abfrage steht als sql_in_bloecken() in db.php und
 * wird von Tagesansicht, Backup und Export benutzt. */

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
    /* Ein halber Zeitraum ist keine Angabe.
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
     * Die lokale Variable heisst deshalb NICHT $patient: Sie steuert
     * ein Dutzend Spalten, von denen nur eine dem Patienten gehoert. */
    $pers = !empty($b['patient']);

    /* Zeitraumfilter. Er greift ueber den DIENSTTAG (`days.day`), nicht ueber
     * das Einsatzdatum: Ein Export soll ganze Dienste liefern, nicht Dienste
     * halbieren, deren Nachteinsaetze in den Folgemonat fielen. Dieselbe Regel
     * wie in der Statistik. Der Join ist der vorgesehene
     * Weg.
     *
     * $whereTag gilt fuer `days` (Alias d), $whereEins fuer die daran haengenden
     * Tabellen. */
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
     * BEZEICHNUNGEN KOMMEN AUS DEN SNAPSHOT-SPALTEN, nicht aus den
     * Stammdaten. Der frueher noetige Join auf `aircraft` und `bases` ist damit
     * entfallen — und mit ihm die Luecke, dass ein geloeschtes Rettungsmittel
     * einen Export ohne Bezeichnung hinterliess. Ein Export mit IDs waere
     * ausserhalb dieser Anwendung ohnehin wertlos.
     *
     * Die personenbezogenen Spalten werden ohne Flag durch NULL ersetzt, statt
     * sie aus der Spaltenliste zu nehmen: Der ANTWORTAUFBAU bleibt dadurch in
     * beiden Faellen gleich, und der Browser muss nicht zwei Formen kennen.
     * Aus der Datenbank kommen die Werte trotzdem nicht (SELECT NULL). */
    $st = $pdo->prepare(
        "SELECT d.id, d.day, d.started_at, d.ended_at, d.kind,
                d.vehicle_name, d.vehicle_typ, d.vehicle_kurz, d.base_name" . ($pers ? ', d.notes' : ', NULL AS notes') . "
         FROM days d
         WHERE d.user_id = ?$whereTag
         ORDER BY d.day, d.started_at, d.id");
    $st->execute($params);
    $dayRows = $st->fetchAll();
    $dayIds  = array_map(static fn($r) => (int)$r['id'], $dayRows);

    /* Besatzung der Diensttage: eine Zeile je Rolle. Gebuendelt in EINER
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
            /* DER EXPORT ZEIGT DIE VOLLE BEZEICHNUNG — der Kurzname
               steht daneben als eigene Spalte, nicht an ihrer Stelle: Er ist
               eine Abkuerzung fuer den Hausgebrauch, und wer die Datei
               auswertet, kennt sie nicht. */
            'vehicle'      => $r['vehicle_name'] !== null ? (string)$r['vehicle_name'] : null,
            'vehicle_kurz' => $r['vehicle_kurz'] !== null ? (string)$r['vehicle_kurz'] : null,
            'vehicle_typ'  => $r['vehicle_typ']  !== null ? (string)$r['vehicle_typ']  : null,
            'base'         => $r['base_name'] !== null ? (string)$r['base_name'] : null,
            'crew'         => (object)($crewByDay[$id] ?? []),
            'capabilities' => $capsByDay[$id] ?? [],
            'notes'        => $r['notes'],
        ];
    }, $dayRows);

    /* ---- Einsaetze ---------------------------------------------------------
     * Die personenbezogenen Spalten stehen nur in der Spaltenliste, wenn das
     * Flag gesetzt ist — sie werden sonst gar nicht erst aus der Datenbank
     * geholt, nicht nur beim Zusammenbau weggelassen.
     *
     * crew_override bleibt in beiden Faellen erhalten: Der Haken sagt nur,
     * DASS die Besatzung an diesem Einsatz von der des Diensttags abwich, nicht
     * wer geflogen ist. Ohne ihn liesse sich nicht mehr erkennen, dass die
     * leeren Namensspalten leer gemacht wurden und nicht leer waren. */
    /* `notes` steht hier NICHT mehr: Die Notiz liegt im `pat_blob`
     * und faellt mit ihm — die Schranke wirkt fuer sie also weiter, aber ueber
     * den Blob statt ueber eine eigene Spalte. */
    /* SPALTEN AUS DEM REGISTER, Zweck `export`.
     * Die vier Felder unter der Schranke kommen als `NULL AS spalte` zurueck,
     * wenn das Flag fehlt — die Form haengt an der SPALTE und nicht an ihrer
     * Stelle im SELECT. Wer ein Feld unter die Schranke nimmt, traegt es in
     * `$schranke` ein; die Liste selbst bleibt die des Registers. */
    /* AUCH KENNUNG UND HUELLE: `client_ref` und `ort_huelle`
     * braucht der Browser, um die Einsatzhuelle zu oeffnen. Transportziel und
     * Ortshoehe liegen im `pat_blob` und gehen mit ihm. */
    $schranke = ['bw_info', 'other_ema', 'pat_blob', 'client_ref', 'ort_huelle'];
    $einsCols = [];
    /* Zwei Listen desselben Zwecks, Stelle fuer Stelle: die blossen Namen zum
     * Vergleichen, die SQL-Form (Namen in Backticks) zum Einsetzen. */
    $sqlCols = mf_spalten('export', 'x.', true, true);
    foreach (mf_spalten('export') as $i => $sp) {
        $einsCols[] = (!$pers && in_array($sp, $schranke, true))
            ? 'NULL AS ' . mf_bezeichner($sp) : $sqlCols[$i];
    }
    /* `d.day` kommt aus `days` und steht deshalb in keinem Register von
     * `missions`. Sein Platz ist hinter `day_id` — dort stand er immer, und
     * die Reihenfolge einer SELECT-Liste ist das, was ein Leser vergleicht. */
    array_splice($einsCols, 2, 0, 'd.day');
    $st = $pdo->prepare(
        /* DER ALIAS `manual` STEHT IN BACKTICKS —
         * aus mf_spalten(), nicht von Hand. Auf MySQL 8.4.0 bis
         * 8.4.10 ist `manual` auch als ALIAS ein reserviertes Wort; ohne die
         * Backticks antwortete der Export dort mit 1064. Die Behebung steht an
         * der einen Stelle, die aus dem Register SQL macht. */
        'SELECT ' . implode(', ', $einsCols) . "
         FROM missions x
         JOIN days d ON d.id = x.day_id
         WHERE x.user_id = ?$whereEins
         ORDER BY x.started_at");
    $st->execute($params);
    $missionRows = $st->fetchAll();

    $ids = array_map(static fn($r) => (int)$r['id'], $missionRows);

    /* ---- Phasen, Rettungsmittel, Trackpunkt-Anzahl: gebuendelt statt je
     * Einsatz einzeln (bis zu 5000 Einsaetze). ------------------------------ */
    $phasesByMission = [];
    $resourcesByMission = [];
    $trackCountByMission = [];

    if ($ids) {
        /* Zeitpunkt ja, Ort nein: Die Phasenzeiten tragen Alarmzeit, Endzeit
         * und Dauer und muessen deshalb in jedem Export stehen (Kriterium 73).
         * Die Orte liegen in der Einsatzhuelle; `assets/export.js` oeffnet sie
         * nur mit Haken und fuellt `lat`/`lon`. Die Schluessel
         * stehen deshalb hier mit `null`. */
        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, phase, occurred_at
                 FROM mission_phases WHERE mission_id IN ({IDS}) ORDER BY mission_id, occurred_at',
                $ids) as $r) {
            $phasesByMission[(int)$r['mission_id']][] = [
                'phase' => (int)$r['phase'],
                'at'    => export_iso_utc($r['occurred_at']),
                'lat'   => null,
                'lon'   => null,
            ];
        }

        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, name FROM mission_resources
                 WHERE mission_id IN ({IDS}) ORDER BY mission_id, id',
                $ids) as $r) {
            $resourcesByMission[(int)$r['mission_id']][] = (string)$r['name'];
        }

        /* Punktzahl je Einsatz ueber spur_lib.php. Sie steuert den
         * GPX-Export: export.js fragt nur Einsaetze mit `track_points > 0`
         * ueberhaupt ab. Stuende hier nach der Verdichtung eine 0, fiele der
         * Einsatz still aus dem Export heraus — deshalb zaehlt jetzt eine
         * Stelle, die Zeilen UND Blob kennt. */
        $trackCountByMission = spur_zahlen($pdo, 'mission', $ids);
    }

    /* Abweichende Besatzung je Einsatz: Zeilen aus `mission_crew`,
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
        /* RUECKFALL IST DER ROHWERT UND NICHT MEHR 'uhr'.
         * Seit `origin` ein VARCHAR ist, kann dort ein Wert
         * stehen, den diese Tabelle noch nicht kennt — ein kuenftiger Client.
         * Ihn als "uhr" auszuliefern waere die schlechteste aller Antworten:
         * falsch, und nicht als falsch zu erkennen. Der Rohwert ist im
         * Zweifel unschoen, aber wahr — und er ist der Hinweis darauf, dass
         * hier eine Beschriftung fehlt. */
        $source = EXPORT_ORIGIN_LABEL[(string)$r['origin']] ?? (string)$r['origin'];

        $missions[] = [
            'id'               => $id,
            'day_id'           => (int)$r['day_id'],
            'day'              => (string)$r['day'],
            'started_at'       => export_iso_utc($r['started_at']),
            'ended_at'         => export_iso_utc($r['ended_at']),
            'distance_m'       => $r['distance_m'] !== null ? (int)$r['distance_m'] : null,
            'ascent_m'         => $r['ascent_m'] !== null ? (int)$r['ascent_m'] : null,
            /* Ortshoehe und Transportziel (unten) aus dem `pat_blob`: Der
             * Browser fuellt sie mit Haken. Die Schluessel bleiben
             * im Vertrag. */
            'site_ele_m'       => null,
            'final'            => (int)$r['final'],
            'manual'           => (int)$r['manual'],
            'source'           => $source,
            'edited'           => (int)$r['edited'],
            /* Die Momentaufnahme des Geraets. Sie geht als
             * ROHWERT hinaus, nicht uebersetzt: `geraet_art` traegt dieselben
             * drei Werte wie die Geraeteseite (uhr | handy | sonstiges), und
             * `geraet_modell` ist ein Name, kein Schluessel. NULL wird zu
             * einer leeren Zelle — "unbekannt" hineinzuschreiben machte aus
             * dem Fehlen eine Aussage. */
            'geraet_art'       => $r['geraet_art'],
            'geraet_modell'    => $r['geraet_modell'],
            'transport_dest'   => null,
            /* Die Einsatzhuelle: Phasenorte und Reanimation. Nur mit Haken; `assets/export.js`
             * oeffnet sie und fuellt daraus `phases[].lat/lon` und `resus`.
             * `started_ts` ist der Bezug ihrer Zeiten. */
            'client_ref'       => $r['client_ref'],
            'ort_huelle'       => $r['ort_huelle'] !== null ? base64_encode((string)$r['ort_huelle']) : null,
            'started_ts'       => $r['started_at'] !== null ? (int)strtotime($r['started_at'] . ' UTC') : null,
            /* Transportart, Begleitung und Fehleinsatz liegen im KLARTEXT und
             * brauchen keine Personenbezugs-Markierung: Sie sind Aussagen
             * ueber den Einsatz. `start_src` ist eine REGEL, kein Ort. Die
             * Zielklinik samt Koordinate und der manuelle Abfahrtort liegen
             * im pat_blob und gehen nur mit ihm; `dest_lat`/`dest_lon` sind
             * hier deshalb leer und werden im Browser aus dem Block gefuellt. */
            'transport_mode'   => $r['transport_mode'],
            'na_escort'        => (int)$r['na_escort'],
            'false_alarm'      => (int)$r['false_alarm'],
            'start_src'        => $r['start_src'],
            'dest_lat'         => null,
            'dest_lon'         => null,
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
            /* 'notes' entfaellt — der Text reist im 'pat_blob' eine
               Zeile darueber mit. */
            'track_points'     => $trackCountByMission[$id] ?? 0,
            'phases'           => $phasesByMission[$id] ?? [],
            'resources'        => $resourcesByMission[$id] ?? [],
            /* Die Reanimation liegt in der Einsatzhuelle. */
            'resus'            => [],
        ];
    }

    /* ---- Ruhesegmente -------------------------------------------------- */
    $st = $pdo->prepare(
        "SELECT x.id, x.day_id, d.day, x.started_at, x.ended_at, x.final,
                x.geraet_art, x.geraet_modell
         FROM rest_segments x
         JOIN days d ON d.id = x.day_id
         WHERE x.user_id = ?$whereEins
         ORDER BY x.started_at");
    $st->execute($params);
    $restRows = $st->fetchAll();
    $restIds = array_map(static fn($r) => (int)$r['id'], $restRows);

    // Punktzahl je Ruhesegment ueber spur_lib.php, wie oben.
    $trackCountByRest = $restIds ? spur_zahlen($pdo, 'rest', $restIds) : [];

    $rests = array_map(static fn($r) => [
        'id'           => (int)$r['id'],
        'day_id'       => (int)$r['day_id'],
        'day'          => (string)$r['day'],
        'started_at'   => export_iso_utc($r['started_at']),
        'ended_at'     => export_iso_utc($r['ended_at']),
        'final'        => (int)$r['final'],
        // Momentaufnahme wie beim Einsatz; eine Herkunft hat das Segment
        // nicht, sein Praefix sagt sie.
        'geraet_art'   => $r['geraet_art'],
        'geraet_modell' => $r['geraet_modell'],
        'track_points' => $trackCountByRest[(int)$r['id']] ?? 0,
    ], $restRows);

    json_out(['days' => $days, 'missions' => $missions, 'rests' => $rests]);
}

function export_track(array $b, int $userId): never
{
    $pdo = db();

    /* Trackpunkte sind personenbezogene Angaben.
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
                  'meldung' => 'Die GPS-Daten im GPX enden am Einsatzort und sind deshalb an '
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
    if (!$ids) { json_roh_out('{}'); }

    // Datentrennung + Papierkorb: nur IDs zulassen, die dem Konto gehoeren
    // und nicht geloescht sind.
    $table = $ownerType === 'mission' ? 'missions' : 'rest_segments';
    $owned = sql_in_bloecken($pdo,
        "SELECT id, client_ref FROM `$table` WHERE user_id = ? AND deleted_at IS NULL AND id IN ({IDS})",
        $ids, [$userId]);
    $validIds = array_map(static fn($r) => (int)$r['id'], $owned);

    /* DIE VERSCHLOSSENEN STUECKE JE SPUR, mit `ref` (die Kennung, an die sie
     * gebunden sind) und `sperren` (geschnittene oder geloeschte Zeitraeume)
     * — `assets/export.js` oeffnet sie mit `EdOrtsdaten.spur()`. */
    $result = [];
    foreach ($owned as $r) {
        $result[(string)$r['id']] = ['ref' => (string)$r['client_ref'],
                                     'stuecke' => [], 'sperren' => []];
    }

    if ($validIds) {
        foreach (spur2_lesen_viele($pdo, $ownerType, $validIds) as $id => $stuecke) {
            $result[(string)$id]['stuecke'] = $stuecke;
            $result[(string)$id]['sperren'] = array_map(
                static fn($x) => ['von' => $x['von_ts'], 'bis' => $x['bis_ts']],
                schnitte_lesen($pdo, $ownerType, (int)$id));
        }
    }

    /* UEBER `json_roh_out()`, nicht `json_out()`: Der
     * Text steht fertig da, und er kann gross werden. Und `header()` + `echo`
     * von Hand vergaessen `Cache-Control: no-store`. DIESE ANTWORT ENTHAELT
     * GPS-SPURPUNKTE; ein Zwischenspeicher darf sie
     * nicht behalten. */
    json_roh_out($result ? (string)json_encode($result) : '{}');
}

try {
    api_methode();
    csrf_check();   // Feld ODER Kopfzeile X-CSRF

    $b = api_rumpf();

    $action = (string)($b['action'] ?? '');
    if ($action === 'meta') { export_meta($b, $userId); }
    if ($action === 'track') { export_track($b, $userId); }
    json_out(['error' => 'action'], 400);
} catch (Throwable $ex) {
    // Lesbare Meldung statt leerem HTTP 500 — Muster wie in api/import_commit.php.
    json_fehler($ex, 'export');
}
