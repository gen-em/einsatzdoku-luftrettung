<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../validate_lib.php';   // liefert $userId
require_once __DIR__ . '/../mission_fields_lib.php';

/**
 * GET  api/day.php            -> { days: ["2026-07-16", ...], latest: "..." }
 * GET  api/day.php?day=Y-m-d  -> Tagesdaten: Flugtag-Meta, Einsaetze (inkl.
 *                                Track, Phasenzeiten), Ruhe-Segmente
 *                                Je Einsatz zusaetzlich die Spalten der
 *                                Tagestabelle unter ihrem Spaltennamen —
 *                                welche das sind, sagt 'day_col' in
 *                                mission_fields.php (siehe mf_tagesspalten()).
 * POST api/day.php            -> Flugtag-Felder speichern (Upsert)
 *                                JSON-Body {day, aircraft, base, crew, notes},
 *                                Header X-CSRF muss zum Session-Token passen
 */

// Dieser Endpunkt kennt zwei Methoden — jede andere ist ein Irrtum und wird
// benannt, statt stillschweigend als GET behandelt zu werden (M3-11).
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    json_out(['error' => 'method'], 405);
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
            json_out(['error' => 'csrf'], 403);
        }
        $b = json_decode(file_get_contents('php://input'), true);
        $day = pruef_kalendertag($b['day'] ?? null, 'day');
        if (!is_array($b) || $day === null) {
            json_out(['error' => 'payload'], 400);
        }

        /* FLUGTAG IM PAPIERKORB: ABLEHNEN UND MELDEN (D1).
         *
         * Die Aktualisierung unten hat keine Bedingung auf deleted_at. Sie
         * traf deshalb auch einen Tag, der im Papierkorb liegt, ueberschrieb
         * seine Angaben und liess ihn geloescht — die Eingabe verschwand
         * spurlos, und die Antwort lautete "ok". Dieselbe Schnittstelle
         * bedient das Formular; wer dort Besatzung oder Maschine eintraegt und
         * speichert, bekam eine Bestaetigung fuer nichts.
         *
         * Warum ABLEHNEN und nicht STILL WIEDERHERSTELLEN (D1): Das Loeschen
         * war eine bewusste Handlung. Sie durch eine Nebenwirkung
         * rueckgaengig zu machen, ist eine Ueberraschung — und zwar eine, die
         * niemand sieht. Der Papierkorb hat eine eigene
         * Wiederherstellungsfunktion; wer den Tag zurueckholen will, soll sie
         * benutzen. */
        $imPapierkorb = db()->prepare(
            'SELECT deleted_at FROM days WHERE user_id = ? AND day = ? AND deleted_at IS NOT NULL');
        $imPapierkorb->execute([$userId, $day]);
        if ($imPapierkorb->fetchColumn() !== false) {
            json_out(['error' => 'day_deleted',
                      'meldung' => 'Dieser Flugtag liegt im Papierkorb. Es wurde nichts '
                                 . 'gespeichert. Bitte den Tag zuerst wiederherstellen '
                                 . '(Einstellungen → Papierkorb).'], 409);
        }
        /* Leer heisst leer — "0" heisst "0" (M3-13).
         *
         * Hier stand der Kurzschlussoperator ?:, der auf WAHRHEITSWERT
         * prueft. Die Zeichenkette "0" ist in PHP unwahr, ebenso wie "".
         * Ein Feld, in dem genau eine Null steht, wurde damit zu NULL —
         * beim Speichern verschwunden, ohne Meldung.
         *
         * Betroffen war nicht nur ein Besatzungsname (den es geben mag oder
         * nicht): $trim gilt fuer ALLE Felder des Flugtags, auch die Notiz.
         * Eine Notiz "0" — etwa als Zaehlung — verschwand.
         *
         * Der Vergleich auf die leere Zeichenkette ist die Schreibweise, die
         * an acht anderen Stellen des Projekts ohnehin steht. */
        $trim = function (string $k, int $max) use ($b): ?string {
            $v = mb_substr(trim((string)($b[$k] ?? '')), 0, $max);
            return $v !== '' ? $v : null;
        };

        // Dropdown-IDs nur uebernehmen, wenn sie der NutzerIn gehoeren ODER
        // zentral sind (user_id IS NULL). Muss zu der Liste passen, aus der
        // index.php das Dropdown baut — sonst wird eine zentrale Maschine oder
        // Basis beim Speichern stillschweigend auf NULL zurueckgesetzt.
        $checkId = function (?int $id, string $table) use ($userId): ?int {
            if ($id === null || $id <= 0) { return null; }
            $q = db()->prepare("SELECT id FROM `$table`
                                WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
            $q->execute([$id, $userId]);
            return $q->fetchColumn() !== false ? $id : null;
        };
        $acId   = $checkId(isset($b['aircraft_id']) ? (int)$b['aircraft_id'] : null, 'aircraft');
        $baseId = $checkId(isset($b['base_id']) ? (int)$b['base_id'] : null, 'bases');

        db()->prepare('INSERT INTO days (user_id, day, aircraft_id, base_id,
                         crew_p1, crew_p2, crew_hems, crew_fr, crew_other, notes)
                       VALUES (?,?,?,?,?,?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE aircraft_id = VALUES(aircraft_id),
                         base_id = VALUES(base_id), crew_p1 = VALUES(crew_p1),
                         crew_p2 = VALUES(crew_p2), crew_hems = VALUES(crew_hems),
                         crew_fr = VALUES(crew_fr), crew_other = VALUES(crew_other),
                         notes = VALUES(notes)')
            ->execute([$userId, $day, $acId, $baseId,
                       $trim('crew_p1', 120), $trim('crew_p2', 120), $trim('crew_hems', 120),
                       $trim('crew_fr', 120), $trim('crew_other', 120), $trim('notes', 2000)]);
        json_out(['ok' => true]);
    }

    $day = (string)($_GET['day'] ?? '');

    if ($day === '') {
        $st = db()->prepare('SELECT DISTINCT day FROM (
                               SELECT day FROM missions      WHERE user_id = ? AND deleted_at IS NULL
                               UNION SELECT day FROM rest_segments WHERE user_id = ? AND deleted_at IS NULL
                             ) t ORDER BY day DESC LIMIT 120');
        $st->execute([$userId, $userId]);
        $days = array_column($st->fetchAll(), 'day');
        json_out(['days' => $days, 'latest' => $days[0] ?? null]);
    }

    if (pruef_kalendertag($day, 'day') === null) json_out(['error' => 'payload'], 400);

    /* AUCH BEIM LESEN MELDEN.
     *
     * Die Abfrage unten filtert auf deleted_at IS NULL und liefert dann
     * schlicht null — nicht unterscheidbar von "fuer diesen Tag wurde noch
     * nichts eingetragen". Wer seine Angaben vermisst, sucht den Fehler bei
     * sich. Der Zustand gehoert genannt, damit die Oberflaeche ihn zeigen
     * kann. */
    $geloescht = db()->prepare(
        'SELECT deleted_at FROM days WHERE user_id = ? AND day = ? AND deleted_at IS NOT NULL');
    $geloescht->execute([$userId, $day]);
    $dayDeletedAt = $geloescht->fetchColumn();

    // Flugtag-Metadaten (null, wenn noch keine gespeichert)
    $mt = db()->prepare('SELECT d.aircraft_id, d.base_id, d.crew_p1, d.crew_p2, d.crew_hems,
                                d.crew_fr, d.crew_other, d.notes,
                                d.aircraft, d.base, d.crew,
                                a.registration AS aircraft_name, b.name AS base_name
                         FROM days d
                         LEFT JOIN aircraft a ON a.id = d.aircraft_id
                         LEFT JOIN bases b ON b.id = d.base_id
                         WHERE d.user_id = ? AND d.day = ? AND d.deleted_at IS NULL');
    $mt->execute([$userId, $day]);
    $meta = $mt->fetch() ?: null;

    /* SPURPUNKTE GEBUENDELT HOLEN, NICHT JE EINSATZ EINZELN (M3-15).
     *
     * Hier stand eine vorbereitete Abfrage, die in zwei Schleifen je Einsatz
     * und je Ruhesegment erneut ausgefuehrt wurde. Ein Flugtag mit acht
     * Einsaetzen und den zugehoerigen Ruhezeiten kam so auf ueber ein Dutzend
     * Abfragen fuer eine Ansicht, die beim Blaettern durch die Tage bei JEDEM
     * Tageswechsel neu laedt.
     *
     * api/export_data.php loest dieselbe Aufgabe seit jeher gebuendelt und
     * hat den Weg im Kommentar vermerkt. Er stand nur dort — deshalb liegt
     * die Funktion jetzt in db.php.
     *
     * Die Reihenfolge innerhalb eines Besitzers kommt weiterhin aus dem SQL
     * (ORDER BY owner_id, seq); die Zuordnung im Speicher aendert sie nicht.
     */
    $spurLaden = function (string $ownerType, array $ids): array {
        $nach = [];
        foreach ($ids as $id) { $nach[$id] = []; }
        if (!$ids) { return $nach; }
        foreach (sql_in_bloecken(db(),
                'SELECT owner_id, lat, lon FROM track_points
                 WHERE owner_type = ? AND owner_id IN ({IDS}) ORDER BY owner_id, seq',
                $ids, [$ownerType]) as $p) {
            $nach[(int)$p['owner_id']][] = [(float)$p['lat'], (float)$p['lon']];
        }
        return $nach;
    };

    /* SPALTEN DER TAGESTABELLE AUS DEM FELDKATALOG (Backlog Nr. 10).
     *
     * Hier standen `winch, bergwacht, secondary` fest im SELECT und weiter
     * unten noch einmal im Aufbau der Antwort. mission_fields.php kennt den
     * Schluessel 'day_col' — er war damit reine Dokumentation, und die dort
     * definierte Spalte „abw. Crew" erschien nie. Beides kommt jetzt aus
     * mf_tagesspalten().
     *
     * Die Spaltennamen sind gegen [a-z][a-z0-9_]* geprueft (siehe
     * mission_fields_lib.php) und stammen aus einer Projektdatei, nicht aus
     * einer Anfrage — die Einsetzung in das SQL ist deshalb unbedenklich.
     * Ein Platzhalter ist fuer Spaltennamen ohnehin nicht moeglich. */
    $tagesSpalten = mf_tagesspalten();
    $spaltenSql = '';
    foreach ($tagesSpalten as $dc) { $spaltenSql .= ', ' . $dc['col']; }

    $st = db()->prepare('SELECT id, started_at, ended_at, distance_m, final,
                           pat_blob' . $spaltenSql . ',
                           (SELECT MAX(occurred_at) FROM mission_phases p
                            WHERE p.mission_id = missions.id AND p.phase = 9) AS p9_at
                         FROM missions WHERE user_id = ? AND day = ? AND deleted_at IS NULL
                         ORDER BY started_at');
    $st->execute([$userId, $day]);
    $missionZeilen = $st->fetchAll();
    $spurEinsatz = $spurLaden('mission',
        array_map(static fn($m) => (int)$m['id'], $missionZeilen));

    $missions = [];
    foreach ($missionZeilen as $m) {
        // Dauer = Alarmierung bis Phase 9; ohne Phase 9 bewusst null
        // (Anzeige "kein Ende" — auch bei abgeschlossenen Einsaetzen ohne 9er)
        $dur = null;
        if ($m['p9_at'] !== null) {
            $dur = (new DateTime($m['p9_at']))->getTimestamp() - (new DateTime($m['started_at']))->getTimestamp();
        }
        $zeile = [
            'id'         => (int)$m['id'],
            'start_hhmm' => fmt_local($m['started_at']),
            'duration_s' => $dur,
            'distance_m' => $m['distance_m'] !== null ? (int)$m['distance_m'] : null,
            'final'      => (bool)$m['final'],
            'has_p9'     => $m['p9_at'] !== null,
            'pat_blob'   => !empty($m['pat_blob']) ? (string)$m['pat_blob'] : null,
            'track'      => $spurEinsatz[(int)$m['id']] ?? [],
        ];
        /* Die Spalten aus 'day_col' stehen unter ihrem eigenen Namen in der
         * Antwort — bisher hiessen sie dort schon 'winch', 'bergwacht' und
         * 'secondary', der Vertrag bleibt fuer sie also unveraendert.
         * Ein Feldname, der einen der festen Schluessel oben doppelt belegen
         * wuerde, faellt sofort auf: Die Tagestabelle zeigte dann Unsinn.
         * Deshalb hier keine stille Umbenennung. */
        foreach ($tagesSpalten as $dc) {
            $w = $m[$dc['col']] ?? null;
            $zeile[$dc['col']] = $dc['art'] === 'check'
                ? ((int)$w === 1)
                : ($w !== null && $w !== '' ? (string)$w : null);
        }
        $missions[] = $zeile;
    }

    $st = db()->prepare('SELECT id FROM rest_segments WHERE user_id = ? AND day = ? AND deleted_at IS NULL ORDER BY started_at');
    $st->execute([$userId, $day]);
    $restZeilen = $st->fetchAll();
    $spurRuhe = $spurLaden('rest',
        array_map(static fn($r) => (int)$r['id'], $restZeilen));
    $rest = [];
    foreach ($restZeilen as $r) {
        $track = $spurRuhe[(int)$r['id']] ?? [];
        if ($track) $rest[] = $track;
    }

    $antwort = ['day' => $day, 'meta' => $meta,
                'missions' => $missions, 'rest_segments' => $rest];
    // Zustand nennen statt ihn als "nichts eingetragen" erscheinen zu lassen.
    if ($dayDeletedAt !== false && $dayDeletedAt !== null) {
        $antwort['day_deleted_at'] = $dayDeletedAt;
    }
    json_out($antwort);
} catch (Throwable $ex) {
    // Statt eines leeren HTTP 500 eine lesbare Fehlermeldung — das Frontend
    // (index.php) zeigt error+meldung bereits an.
    json_fehler($ex, 'day');
}
