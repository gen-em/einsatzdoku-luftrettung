<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../validate_lib.php';   // liefert $userId
require_once __DIR__ . '/../mission_fields_lib.php';
require_once __DIR__ . '/../diensttag_lib.php';

/**
 * GET  api/day.php            -> { days: [{id, day, …}, …], latest: <id> }
 * GET  api/day.php?d=<id>     -> Tagesdaten: Diensttag-Meta, Besatzung,
 *                                Einsaetze (inkl. Track, Phasenzeiten),
 *                                Ruhe-Segmente
 *                                Je Einsatz zusaetzlich die Spalten der
 *                                Tagestabelle unter ihrem Spaltennamen —
 *                                welche das sind, sagt 'day_col' in
 *                                mission_fields.php (siehe mf_tagesspalten()).
 * POST api/day.php            -> Diensttag-Felder speichern
 *                                JSON-Body {day_id, vehicle_id, base_id,
 *                                crew: {<rolle>: name, …}, notes},
 *                                Header X-CSRF muss zum Session-Token passen
 *
 * DER SCHLUESSEL IST DIE KENNUNG, NICHT DAS DATUM (Web 6.0.0). Bis dahin nahm
 * dieser Endpunkt `?day=YYYY-MM-DD` und legte bei Bedarf eine Zeile an — das
 * Datum war der Schluessel, ein Upsert also die richtige Form. Seit E9 kann es
 * mehrere Diensttage je Kalendertag geben; ein Datum bestimmt keinen Tag mehr.
 * Der POST ist deshalb ein reines UPDATE auf eine vorhandene Zeile: Angelegt
 * wird ein Diensttag in diensttag_neu.php oder von der Uhr, nicht als
 * Nebenwirkung des Speicherns.
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
        $dayId = isset($b['day_id']) ? (int)$b['day_id'] : 0;
        if (!is_array($b) || $dayId <= 0) {
            json_out(['error' => 'payload'], 400);
        }

        /* DIENSTTAG IM PAPIERKORB: ABLEHNEN UND MELDEN (D1).
         *
         * Die Aktualisierung unten hat keine Bedingung auf deleted_at. Sie
         * traf deshalb auch einen Tag, der im Papierkorb liegt, ueberschrieb
         * seine Angaben und liess ihn geloescht — die Eingabe verschwand
         * spurlos, und die Antwort lautete "ok". Dieselbe Schnittstelle
         * bedient das Formular; wer dort Besatzung oder Rettungsmittel eintraegt
         * und speichert, bekam eine Bestaetigung fuer nichts.
         *
         * Warum ABLEHNEN und nicht STILL WIEDERHERSTELLEN (D1): Das Loeschen
         * war eine bewusste Handlung. Sie durch eine Nebenwirkung
         * rueckgaengig zu machen, ist eine Ueberraschung — und zwar eine, die
         * niemand sieht. Der Papierkorb hat eine eigene
         * Wiederherstellungsfunktion; wer den Tag zurueckholen will, soll sie
         * benutzen. */
        $tag = dt_laden($userId, $dayId, true);
        if ($tag === null) {
            json_out(['error' => 'not_found',
                      'meldung' => 'Dieser Diensttag ist nicht vorhanden. '
                                 . 'Es wurde nichts gespeichert.'], 404);
        }
        if ($tag['deleted_at'] !== null) {
            json_out(['error' => 'day_deleted',
                      'meldung' => 'Dieser Diensttag liegt im Papierkorb. Es wurde nichts '
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
         * nicht): $trim gilt fuer ALLE Felder des Diensttags, auch die Notiz.
         * Eine Notiz "0" — etwa als Zaehlung — verschwand.
         *
         * Der Vergleich auf die leere Zeichenkette ist die Schreibweise, die
         * an acht anderen Stellen des Projekts ohnehin steht. */
        $trim = function (string $k, int $max) use ($b): ?string {
            $v = mb_substr(trim((string)($b[$k] ?? '')), 0, $max);
            return $v !== '' ? $v : null;
        };

        $pdo = db();
        $pdo->beginTransaction();
        try {
            /* Standort und Rettungsmittel schreiben und alles Abgeleitete
             * einfrieren (E8) — Art, Bezeichnungen, Standortkoordinaten,
             * Rollensatz und Faehigkeiten. Die Pruefung, ob die Kennungen der
             * NutzerIn ueberhaupt zur Verfuegung stehen, steckt in
             * dt_zuordnen(); sie muss zu der Liste passen, aus der index.php
             * die Auswahlfelder baut, sonst wird ein zentraler Eintrag beim
             * Speichern stillschweigend auf NULL zurueckgesetzt. */
            dt_zuordnen($pdo, $userId, $dayId,
                        isset($b['vehicle_id']) ? (int)$b['vehicle_id'] : null,
                        isset($b['base_id'])    ? (int)$b['base_id']    : null);

            // Besatzungsnamen. Nur Rollen, die der Diensttag anbietet — die
            // Zeilenmenge in `day_crew` ist der eingefrorene Rollensatz (E8),
            // und eine Rolle, die er nicht enthaelt, wird auch nicht angelegt.
            $crew = (isset($b['crew']) && is_array($b['crew'])) ? $b['crew'] : [];
            dt_crew_speichern($pdo, $dayId, $crew);

            $pdo->prepare('UPDATE days SET notes = ? WHERE id = ? AND user_id = ?')
                ->execute([$trim('notes', 2000), $dayId, $userId]);
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $ex;
        }
        json_out(['ok' => true]);
    }

    $dayId = (int)($_GET['d'] ?? 0);

    if ($dayId <= 0) {
        /* Liste der Diensttage. Sie traegt jetzt Kennungen und nicht mehr
         * Datumszeichenketten: Zwei Dienste am selben Kalendertag waeren sonst
         * ein Eintrag. Was die Anzeige braucht, um sie auseinanderzuhalten —
         * Uhrzeit, Art, Rettungsmittel —, kommt mit. */
        $tage = dt_liste($userId, 120);
        $liste = array_map(static function (array $t): array {
            $sym = dt_art_symbol($t['kind'] === null ? null : (string)$t['kind']);
            return [
                'id'           => (int)$t['id'],
                'day'          => (string)$t['day'],
                'start_hhmm'   => $t['started_at'] !== null ? fmt_local((string)$t['started_at']) : null,
                'kind'         => $t['kind'] === null ? null : (string)$t['kind'],
                'art_symbol'   => $sym['symbol'],
                'art_text'     => $sym['text'],
                'vehicle_name' => $t['vehicle_name'] !== null ? (string)$t['vehicle_name'] : null,
                'base_name'    => $t['base_name'] !== null ? (string)$t['base_name'] : null,
                'mehrfach'     => (bool)$t['mehrfach'],
            ];
        }, $tage);
        json_out(['days' => $liste, 'latest' => $liste[0]['id'] ?? null]);
    }

    /* AUCH BEIM LESEN MELDEN.
     *
     * Ein Diensttag im Papierkorb wird von dt_laden() ausgelassen und liefert
     * dann schlicht null — nicht unterscheidbar von "es gibt ihn nicht". Wer
     * seine Angaben vermisst, sucht den Fehler bei sich. Der Zustand gehoert
     * genannt, damit die Oberflaeche ihn zeigen kann. */
    $tag = dt_laden($userId, $dayId, true);
    if ($tag === null) { json_out(['error' => 'not_found'], 404); }
    $dayDeletedAt = $tag['deleted_at'];

    /* Metadaten des Diensttags. ANGEZEIGT WERDEN DIE SNAPSHOT-SPALTEN (E8),
     * nicht die Stammdaten: `vehicle_name` und `base_name` stehen in `days`
     * und aendern sich nicht mehr, wenn das Rettungsmittel umbenannt oder
     * geloescht wird (A4). Die Kennungen kommen daneben mit, weil das Formular
     * seine Auswahlfelder darauf stellt — angezeigt werden sie nie. */
    $sym = dt_art_symbol($tag['kind'] === null ? null : (string)$tag['kind']);
    $meta = [
        'vehicle_id'   => $tag['vehicle_id'] !== null ? (int)$tag['vehicle_id'] : null,
        'base_id'      => $tag['base_id']    !== null ? (int)$tag['base_id']    : null,
        'vehicle_name' => $tag['vehicle_name'] !== null ? (string)$tag['vehicle_name'] : null,
        'base_name'    => $tag['base_name']    !== null ? (string)$tag['base_name']    : null,
        'kind'         => $tag['kind'] === null ? null : (string)$tag['kind'],
        'art_symbol'   => $sym['symbol'],
        'art_text'     => $sym['text'],
        'notes'        => $tag['notes'] !== null ? (string)$tag['notes'] : null,
        'started_at'   => $tag['started_at'] !== null ? fmt_local((string)$tag['started_at']) : null,
        'ended_at'     => $tag['ended_at']   !== null ? fmt_local((string)$tag['ended_at'])   : null,
        /* Standortkoordinate — ebenfalls eingefroren (E8) und deshalb aus
         * `days`, nicht aus den Stammdaten. Sie ist die Quelle des Abfahrtorts
         * „Standort"; eine spaetere Korrektur am Standort aendert an bereits
         * erfassten Diensttagen nichts (A13p). */
        'base_lat'     => $tag['base_lat'] !== null ? (float)$tag['base_lat'] : null,
        'base_lon'     => $tag['base_lon'] !== null ? (float)$tag['base_lon'] : null,
    ];

    /* Besatzung des Tages: die Zeilenmenge aus `day_crew`, mit Beschriftung aus
     * dem Rollenkatalog. Sie ist zugleich die Auskunft darueber, WELCHE Rollen
     * dieser Diensttag anbietet — ein neutraler Tag liefert eine leere Liste
     * (E26), und das Formular zeigt dann keine Besatzungsfelder (A7a). */
    $crew = [];
    foreach (dt_crew($dayId) as $code => $name) {
        $crew[] = ['role'  => $code,
                   'label' => crew_role_label($code),
                   'name'  => $name !== null ? (string)$name : null];
    }
    $meta['crew'] = $crew;
    $meta['capabilities'] = dt_faehigkeiten($dayId);

    /* Besatzungs-Vorbelegungen als Vorschlagsliste je Rolle — DES STANDORTS,
     * der am Diensttag hinterlegt ist (E15). Sie kommen mit der Tagesantwort,
     * weil die Rollen selbst erst aus ihr hervorgehen: Welche Felder die
     * Uebersicht zeigt, entscheidet `day_crew`, und ohne die Vorschlaege in
     * derselben Antwort brauchte die Seite je Rollenwechsel eine zweite Abfrage.
     *
     * Ohne Standort keine Vorschlaege. Das ist die Folge von E15 und kein
     * Mangel: Eine standortuebergreifende Ebene gibt es nicht, also gibt es auch
     * keine Liste, aus der sich hier etwas anbieten liesse. Freitext bleibt
     * uneingeschraenkt moeglich. */
    $presets = [];
    if ($tag['base_id'] !== null && $crew) {
        $pq = db()->prepare('SELECT DISTINCT role_code, name FROM crew_presets
                              WHERE base_id = ? AND (user_id = ? OR user_id IS NULL)
                              ORDER BY name');
        $pq->execute([(int)$tag['base_id'], $userId]);
        foreach ($pq->fetchAll() as $z) {
            $presets[(string)$z['role_code']][] = (string)$z['name'];
        }
    }
    $meta['presets'] = (object)$presets;

    /* SPURPUNKTE GEBUENDELT HOLEN, NICHT JE EINSATZ EINZELN (M3-15).
     *
     * Hier stand eine vorbereitete Abfrage, die in zwei Schleifen je Einsatz
     * und je Ruhesegment erneut ausgefuehrt wurde. Ein Diensttag mit acht
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
     * mf_tagesspalten(). (Seit Web 5.10.0 sind es wieder genau die drei
     * genannten Spalten: „abw. Crew" ist aus dem Katalog abbestellt.)
     *
     * Die Spaltennamen sind gegen [a-z][a-z0-9_]* geprueft (siehe
     * mission_fields_lib.php) und stammen aus einer Projektdatei, nicht aus
     * einer Anfrage — die Einsetzung in das SQL ist deshalb unbedenklich.
     * Ein Platzhalter ist fuer Spaltennamen ohnehin nicht moeglich. */
    $tagesSpalten = mf_tagesspalten();
    $spaltenSql = '';
    foreach ($tagesSpalten as $dc) { $spaltenSql .= ', ' . $dc['col']; }

    /* `start_src`, `dest_lat` und `dest_lon` stehen FEST im SELECT und nicht im
     * Katalog: Sie sind keine Spalten der Tagestabelle, sondern die Zutaten der
     * Luftlinie auf der Tageskarte (E34/E35). Die Aufloesung der beiden
     * Vorgaenger-Regeln geschieht im Browser — er hat die Einsaetze des Tages
     * ohnehin gemeinsam vorliegen und entschluesselt sie dort, wo der Server es
     * gar nicht koennte (Konzept 4.6.1). */
    $st = db()->prepare('SELECT id, started_at, ended_at, distance_m, final,
                           pat_blob, start_src, dest_lat, dest_lon' . $spaltenSql . ',
                           (SELECT MAX(occurred_at) FROM mission_phases p
                            WHERE p.mission_id = missions.id AND p.phase = 9) AS p9_at
                         FROM missions WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL
                         ORDER BY started_at');
    $st->execute([$userId, $dayId]);
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
            'start_src'  => $m['start_src'] !== null ? (string)$m['start_src'] : null,
            'dest_lat'   => $m['dest_lat'] !== null ? (float)$m['dest_lat'] : null,
            'dest_lon'   => $m['dest_lon'] !== null ? (float)$m['dest_lon'] : null,
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

    $st = db()->prepare('SELECT id FROM rest_segments WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL ORDER BY started_at');
    $st->execute([$userId, $dayId]);
    $restZeilen = $st->fetchAll();
    $spurRuhe = $spurLaden('rest',
        array_map(static fn($r) => (int)$r['id'], $restZeilen));
    $rest = [];
    foreach ($restZeilen as $r) {
        $track = $spurRuhe[(int)$r['id']] ?? [];
        if ($track) $rest[] = $track;
    }

    $antwort = ['day_id' => $dayId, 'day' => (string)$tag['day'], 'meta' => $meta,
                'missions' => $missions, 'rest_segments' => $rest];
    // Zustand nennen statt ihn als "nichts eingetragen" erscheinen zu lassen.
    if ($dayDeletedAt !== null) {
        $antwort['day_deleted_at'] = $dayDeletedAt;
    }
    json_out($antwort);
} catch (Throwable $ex) {
    // Statt eines leeren HTTP 500 eine lesbare Fehlermeldung — das Frontend
    // (index.php) zeigt error+meldung bereits an.
    json_fehler($ex, 'day');
}
