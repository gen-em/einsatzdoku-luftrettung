<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../mission_fields_lib.php';

/**
 * Suchindex — der gesamte aktive Einsatzbestand der angemeldeten Person.
 *
 * Wird von suche.php EINMAL je Sitzung geholt; danach filtert der Browser
 * vollstaendig lokal, ohne weitere Serveranfragen. Grundlage der Entscheidung:
 * erwartet werden 50–80 Einsaetze pro Jahr, also selbst nach zwei Jahrzehnten
 * unter etwa 1 600 Datensaetzen.
 *
 * Bewusst NICHT enthalten: Trackpunkte und Phasenlisten. Beides waere um
 * Groessenordnungen groesser als alles andere zusammen und wird zum Filtern
 * nicht gebraucht.
 *
 * Verschluesselte Angaben (Einsatznummer, Name, Geburtsdatum, Alter, Diagnose,
 * Einsatzort) gehen wie ueberall als `pat_blob` unveraendert als Chiffretext
 * an den Browser. Der Server sieht sie nicht und filtert nicht danach —
 * deshalb nimmt dieser Endpunkt auch keinerlei Suchparameter entgegen.
 *
 * DAS SUCHDATUM IST DAS ECHTE EINSATZDATUM (E14, Web 6.0.0). Es wird aus
 * `started_at` in Ortszeit abgeleitet, nicht aus dem Datum des Diensttags. Ein
 * Einsatz um 01:30 eines Dienstes, der am Vortag begann, ist damit unter SEINEM
 * Datum zu finden — waehrend die Statistik ihn dem Diensttag zurechnet. Der
 * Unterschied ist beabsichtigt; ohne ihn suchte man einen Nachteinsatz unter
 * dem falschen Tag.
 *
 * Abfragen: fuenf Stueck, unabhaengig von der Zahl der Einsaetze. Kein N+1 —
 * Einsaetze, Diensttage, Tagesbesatzung, abweichende Besatzung, weitere
 * Rettungsmittel. (Bis Web 5.10.0 waren es sechs; die Abfrage ueber die
 * Stammdatentabellen ist mit den Snapshot-Spalten des Diensttags entfallen.)
 */

// Nur lesen (M3-11) — derselbe Grund wie bei den uebrigen lesenden
// Endpunkten: Was nichts aendert, beantwortet auch kein POST.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { json_out(['error' => 'method'], 405); }

try {
    // ---- Einsaetze -------------------------------------------------------
    // Datentrennung nach user_id in JEDER Abfrage dieser Datei.
    /* `edited` steht hier NICHT mehr (Backlog Nr. 15). Es war totes Nutzdatum:
     * suche.php ist der einzige Abnehmer dieses Endpunkts und hat den Wert
     * nirgends ausgewertet — die Spalte reiste bei jedem Aufruf durch die
     * Antwort, ohne je gelesen zu werden. Der Bearbeitungsstand steht
     * unveraendert in der Einsatzansicht (api/mission.php). */
    $st = db()->prepare(
        'SELECT m.id, m.day_id, m.started_at, m.distance_m,
                m.transport_mode, m.na_escort, m.transport_dest, m.schockraum,
                m.false_alarm,
                m.winch, m.winch_cycles, m.winch_cycles_pat, m.winch_airload,
                m.bergwacht, m.bw_unit, m.bw_info,
                m.secondary, m.other_ema, m.notes, m.crew_override,
                m.pat_blob,
                (SELECT MAX(occurred_at) FROM mission_phases p
                  WHERE p.mission_id = m.id AND p.phase = 9) AS p9_at
           FROM missions m
          WHERE m.user_id = ? AND m.deleted_at IS NULL
          ORDER BY m.started_at'
    );
    $st->execute([$userId]);
    $rows = $st->fetchAll();

    /* ---- Diensttage (Standort, Rettungsmittel, Art) ----------------------
     *
     * Verknuepfung ueber `day_id`. Bis Web 5.10.0 lief sie ueber den
     * natuerlichen Schluessel (user_id, day) und musste eine eigene Abfrage
     * bleiben, weil `missions` und `days` beide crew_*-Spalten trugen. Beides
     * ist entfallen — die Regel "days und missions nie joinen" ist ausdruecklich
     * aufgehoben (Konzept 4.11). Als eigene Abfrage bleibt es trotzdem: Sie
     * laedt jeden Diensttag EINMAL statt einmal je Einsatz.
     *
     * ANGEZEIGT WERDEN DIE SNAPSHOT-SPALTEN (E8): `vehicle_name` und
     * `base_name` stehen im Diensttag. Damit sind auch Dienste auffindbar,
     * deren Rettungsmittel oder Standort inzwischen umbenannt oder geloescht
     * wurde — und die Migration hat den Altfreitext `days.aircraft`/`days.base`
     * genau dorthin gerettet (Berichtigung B6). Der Rueckfall auf die
     * Altspalten, den diese Datei bis Web 5.10.0 brauchte, ist dadurch
     * ersatzlos entfallen.
     */
    $dq = db()->prepare(
        'SELECT id, day, kind, vehicle_name, base_name
           FROM days WHERE user_id = ? AND deleted_at IS NULL'
    );
    $dq->execute([$userId]);
    $tage = [];
    foreach ($dq->fetchAll() as $d) { $tage[(int)$d['id']] = $d; }

    // ---- Besatzung: Diensttag und abweichende je Einsatz ------------------
    $cq = db()->prepare(
        'SELECT c.day_id, c.role_code, c.name
           FROM day_crew c JOIN days d ON d.id = c.day_id
          WHERE d.user_id = ? AND d.deleted_at IS NULL'
    );
    $cq->execute([$userId]);
    $tagesCrew = [];
    foreach ($cq->fetchAll() as $z) {
        $tagesCrew[(int)$z['day_id']][(string)$z['role_code']] = $z['name'];
    }

    $mcq = db()->prepare(
        'SELECT c.mission_id, c.role_code, c.name
           FROM mission_crew c JOIN missions m ON m.id = c.mission_id
          WHERE m.user_id = ? AND m.deleted_at IS NULL'
    );
    $mcq->execute([$userId]);
    $einsatzCrew = [];
    foreach ($mcq->fetchAll() as $z) {
        $einsatzCrew[(int)$z['mission_id']][(string)$z['role_code']] = $z['name'];
    }

    // ---- Weitere Rettungsmittel je Einsatz -------------------------------
    $rq = db()->prepare(
        'SELECT r.mission_id, r.name
           FROM mission_resources r
           JOIN missions m ON m.id = r.mission_id
          WHERE m.user_id = ? AND m.deleted_at IS NULL
          ORDER BY r.id'
    );
    $rq->execute([$userId]);
    $mittel = [];
    foreach ($rq->fetchAll() as $r) { $mittel[(int)$r['mission_id']][] = (string)$r['name']; }

    // ---- Zusammenbauen ---------------------------------------------------
    $missions = [];
    foreach ($rows as $m) {
        $id    = (int)$m['id'];
        $dayId = $m['day_id'] !== null ? (int)$m['day_id'] : 0;
        $d     = $tage[$dayId] ?? null;

        $dur = null;
        if ($m['p9_at'] !== null) {
            $dur = (new DateTime($m['p9_at']))->getTimestamp()
                 - (new DateTime($m['started_at']))->getTimestamp();
        }

        // Ortszeit einmal formatieren und daraus die Minuten seit Mitternacht
        // ableiten — so koennen Anzeige und Uhrzeitfilter nicht auseinander-
        // laufen (beide Werte stammen aus derselben Umrechnung).
        $hhmm = fmt_local($m['started_at']);
        $startMin = null;
        if (preg_match('/^(\d{2}):(\d{2})$/', $hhmm, $t)) {
            $startMin = (int)$t[1] * 60 + (int)$t[2];
        }

        // Effektive Besatzung (COALESCE-Regel wie in api/mission.php): der
        // Einsatzwert gilt nur, wenn der Haken gesetzt UND das Rollenfeld
        // belegt ist; sonst zaehlt die Besatzung des Diensttags.
        $ovOn = (int)($m['crew_override'] ?? 0) === 1;
        $tc   = $tagesCrew[$dayId] ?? [];
        $mc   = $einsatzCrew[$id]  ?? [];
        $crew = [];
        foreach (array_keys(CREW_ROLES) as $r) {
            $mVal = trim((string)($mc[$r] ?? ''));
            $dVal = trim((string)($tc[$r] ?? ''));
            $eff  = ($ovOn && $mVal !== '') ? $mVal : $dVal;
            $crew[$r] = $eff !== '' ? $eff : null;
        }

        $missions[] = [
            'id'          => $id,
            'day_id'      => $dayId,
            // Das ECHTE Einsatzdatum in Ortszeit (E14), nicht das des Diensttags.
            'day'         => fmt_local($m['started_at'], 'Y-m-d'),
            // Das Datum des Dienstes daneben: Die Suche zeigt beides, damit ein
            // Nachteinsatz nicht wie ein falsch zugeordneter aussieht.
            'dienst_day'  => $d !== null ? (string)$d['day'] : null,
            'start_hhmm'  => $hhmm,
            'start_min'   => $startMin,
            'duration_s'  => $dur,
            'distance_m'  => $m['distance_m'] !== null ? (int)$m['distance_m'] : null,
            /* Die Klartextfelder der Etappe 2 (Web 6.1.0), soweit die Suche
             * sie auswertet. `dest_lat`/`dest_lon` und `start_src` bleiben
             * bewusst DRAUSSEN: Nach einer Koordinate oder nach der Herkunft
             * eines Abfahrtorts wird nicht gefiltert, und dieser Index fuehrt
             * grundsaetzlich nur, was die Suche auch benutzt (siehe Kopf). */
            'transport_mode' => $m['transport_mode'] !== null ? (string)$m['transport_mode'] : null,
            'na_escort'   => (int)$m['na_escort'] === 1,
            'transport_dest' => $m['transport_dest'] !== null ? (string)$m['transport_dest'] : null,
            'schockraum'  => (int)$m['schockraum'] === 1,
            'false_alarm' => (int)$m['false_alarm'] === 1,
            'winch'       => (int)$m['winch'] === 1,
            'winch_cycles'     => $m['winch_cycles']     !== null ? (int)$m['winch_cycles']     : null,
            'winch_cycles_pat' => $m['winch_cycles_pat'] !== null ? (int)$m['winch_cycles_pat'] : null,
            'winch_airload'    => (int)$m['winch_airload'] === 1,
            'bergwacht'   => (int)$m['bergwacht'] === 1,
            'bw_unit'     => $m['bw_unit'] !== null ? (string)$m['bw_unit'] : null,
            'bw_info'     => $m['bw_info'] !== null ? (string)$m['bw_info'] : null,
            'secondary'   => (int)$m['secondary'] === 1,
            'other_ema'   => $m['other_ema'] !== null ? (string)$m['other_ema'] : null,
            'notes'       => $m['notes'] !== null ? (string)$m['notes'] : null,
            // Standort, Rettungsmittel und Art aus den Snapshot-Spalten des
            // Diensttags (E8) — nie aus den Stammdaten.
            'base'        => $d !== null && $d['base_name'] !== null ? (string)$d['base_name'] : null,
            'vehicle'     => $d !== null && $d['vehicle_name'] !== null ? (string)$d['vehicle_name'] : null,
            'kind'        => $d !== null && $d['kind'] !== null ? (string)$d['kind'] : null,
            'crew'        => (object)$crew,
            'resources'   => $mittel[$id] ?? [],
            'pat_blob'    => !empty($m['pat_blob']) ? (string)$m['pat_blob'] : null,
        ];
    }

    json_out(['missions' => $missions]);
} catch (Throwable $ex) {
    // Statt eines leeren HTTP 500 (z. B. fehlende Spalte nach vergessener
    // Migration) eine lesbare Meldung — das Frontend zeigt sie an.
    json_fehler($ex, 'suchindex');
}
