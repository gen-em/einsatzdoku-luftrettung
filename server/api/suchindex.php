<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';

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
 * Abfragen: sechs Stueck, unabhaengig von der Zahl der Einsaetze. Kein N+1.
 */

try {
    // ---- Einsaetze -------------------------------------------------------
    // Datentrennung nach user_id in JEDER Abfrage dieser Datei.
    $st = db()->prepare(
        'SELECT m.id, m.day, m.started_at, m.distance_m, m.site_ele_m,
                m.origin, m.edited,
                m.transport_dest, m.site_desc, m.schockraum,
                m.winch, m.winch_cycles, m.winch_cycles_pat, m.winch_airload,
                m.bergwacht, m.bw_unit, m.bw_info,
                m.secondary, m.other_ema, m.notes,
                m.crew_override, m.crew_p1, m.crew_p2, m.crew_hems, m.crew_fr, m.crew_other,
                m.pat_blob,
                (SELECT MAX(occurred_at) FROM mission_phases p
                  WHERE p.mission_id = m.id AND p.phase = 9) AS p9_at
           FROM missions m
          WHERE m.user_id = ? AND m.deleted_at IS NULL
          ORDER BY m.started_at'
    );
    $st->execute([$userId]);
    $rows = $st->fetchAll();

    // ---- Flugtage (Standort, Maschine, Tagescrew) ------------------------
    // Verknuepfung ueber den natuerlichen Schluessel (user_id, day). Bewusst
    // als eigene Abfrage und NICHT per JOIN: missions und days tragen beide
    // Spalten crew_p1…crew_other, ein JOIN wuerde sie ueberschreiben. Dieselbe
    // Falle ist in api/mission.php dokumentiert.
    $dq = db()->prepare(
        'SELECT day, aircraft_id, base_id, aircraft, base,
                crew_p1, crew_p2, crew_hems, crew_fr, crew_other
           FROM days WHERE user_id = ? AND deleted_at IS NULL'
    );
    $dq->execute([$userId]);
    $tage = [];
    foreach ($dq->fetchAll() as $d) { $tage[(string)$d['day']] = $d; }

    // ---- Stammdaten: persoenliche UND zentrale Eintraege ------------------
    $bq = db()->prepare('SELECT id, name FROM bases WHERE user_id = ? OR user_id IS NULL');
    $bq->execute([$userId]);
    $basen = [];
    foreach ($bq->fetchAll() as $b) { $basen[(int)$b['id']] = (string)$b['name']; }

    $aq = db()->prepare('SELECT id, registration FROM aircraft WHERE user_id = ? OR user_id IS NULL');
    $aq->execute([$userId]);
    $maschinen = [];
    foreach ($aq->fetchAll() as $a) { $maschinen[(int)$a['id']] = (string)$a['registration']; }

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

    // ---- Reanimationen: Anzahl Sitzungen und vorkommende Ereignisarten ----
    $sq = db()->prepare(
        'SELECT s.mission_id, COUNT(*) AS n
           FROM resus_sessions s
           JOIN missions m ON m.id = s.mission_id
          WHERE m.user_id = ? AND m.deleted_at IS NULL
          GROUP BY s.mission_id'
    );
    $sq->execute([$userId]);
    $reaAnzahl = [];
    foreach ($sq->fetchAll() as $s) { $reaAnzahl[(int)$s['mission_id']] = (int)$s['n']; }

    $eq = db()->prepare(
        'SELECT DISTINCT s.mission_id, e.type
           FROM resus_events e
           JOIN resus_sessions s ON s.id = e.session_id
           JOIN missions m ON m.id = s.mission_id
          WHERE m.user_id = ? AND m.deleted_at IS NULL'
    );
    $eq->execute([$userId]);
    $reaTypen = [];
    foreach ($eq->fetchAll() as $e2) { $reaTypen[(int)$e2['mission_id']][] = (string)$e2['type']; }

    // ---- Zusammenbauen ---------------------------------------------------
    $ROLLEN = ['p1', 'p2', 'hems', 'fr', 'other'];

    /** Stammdatenname zur ID, sonst der Alt-Freitext, sonst null. */
    $textOderId = function (?array $d, string $idSpalte, string $altSpalte, array $namen): ?string {
        if ($d === null) { return null; }
        if ($d[$idSpalte] !== null && isset($namen[(int)$d[$idSpalte]])) {
            return $namen[(int)$d[$idSpalte]];
        }
        $alt = trim((string)($d[$altSpalte] ?? ''));
        return $alt !== '' ? $alt : null;
    };
    $missions = [];
    foreach ($rows as $m) {
        $id  = (int)$m['id'];
        $tag = (string)$m['day'];
        $d   = $tage[$tag] ?? null;

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
        // belegt ist; sonst zaehlt die Tagescrew.
        $ovOn = (int)($m['crew_override'] ?? 0) === 1;
        $crew = [];
        foreach ($ROLLEN as $r) {
            $spalte = 'crew_' . $r;
            $mVal = trim((string)($m[$spalte] ?? ''));
            $dVal = trim((string)($d[$spalte] ?? ''));
            $eff  = ($ovOn && $mVal !== '') ? $mVal : $dVal;
            $crew[$r] = $eff !== '' ? $eff : null;
        }

        $typen = $reaTypen[$id] ?? [];
        // 'beginn' ist kein Ereignis in resus_events, sondern der Startzeit-
        // punkt der Sitzung. Damit die Auswahlliste des Filters lueckenlos zu
        // RESUS_LABELS passt, wird er hier ergaenzt.
        if (($reaAnzahl[$id] ?? 0) > 0) { array_unshift($typen, 'beginn'); }

        $missions[] = [
            'id'          => $id,
            'day'         => $tag,
            'start_hhmm'  => $hhmm,
            'start_min'   => $startMin,
            'duration_s'  => $dur,
            'distance_m'  => $m['distance_m'] !== null ? (int)$m['distance_m'] : null,
            'site_ele_m'  => $m['site_ele_m'] !== null ? (int)$m['site_ele_m'] : null,
            'origin'      => (string)($m['origin'] ?? 'watch'),
            'edited'      => (int)($m['edited'] ?? 0) === 1,
            'transport_dest' => $m['transport_dest'] !== null ? (string)$m['transport_dest'] : null,
            'site_desc'   => $m['site_desc'] !== null ? (string)$m['site_desc'] : null,
            'schockraum'  => (int)$m['schockraum'] === 1,
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
            // Standort und Maschine kommen aus den Stammdaten. Faellt die
            // Verknuepfung aus (alte Flugtage vor der Umstellung auf
            // aircraft_id/base_id), greifen die Alt-Freitextspalten — sonst
            // waeren historische Einsaetze nach diesen beiden Kriterien
            // schlicht nicht auffindbar.
            'base'        => $textOderId($d, 'base_id', 'base', $basen),
            'aircraft'    => $textOderId($d, 'aircraft_id', 'aircraft', $maschinen),
            'crew'        => (object)$crew,
            'resources'   => $mittel[$id] ?? [],
            'resus_count' => $reaAnzahl[$id] ?? 0,
            'resus_types' => array_values(array_unique($typen)),
            'pat_blob'    => !empty($m['pat_blob']) ? (string)$m['pat_blob'] : null,
        ];
    }

    json_out(['missions' => $missions]);
} catch (Throwable $ex) {
    // Statt eines leeren HTTP 500 (z. B. fehlende Spalte nach vergessener
    // Migration) eine lesbare Meldung — das Frontend zeigt sie an.
    json_out(['error' => 'suchindex', 'meldung' => $ex->getMessage()], 500);
}
