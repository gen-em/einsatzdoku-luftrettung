<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../mission_fields_lib.php';
require_once __DIR__ . '/../diensttag_lib.php';

// Nur lesen (M3-11) — derselbe Grund wie bei den uebrigen lesenden
// Endpunkten: Was nichts aendert, beantwortet auch kein POST.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { json_out(['error' => 'method'], 405); }

try {
    $id = (int)($_GET['id'] ?? 0);

    $st = db()->prepare('SELECT * FROM missions WHERE id = ? AND user_id = ? AND deleted_at IS NULL');   // Datentrennung!
    $st->execute([$id, $userId]);
    $m = $st->fetch();
    if (!$m) json_out(['error' => 'not_found'], 404);

    // Zusatzfelder generisch aus der zentralen Definition (mission_fields.php)
    $FIELDS = require __DIR__ . '/../mission_fields.php';
    $fields = [];
    $collect = function (string $col, array $f) use (&$collect, &$fields, $m) {
        $type = $f['type'] ?? 'text';
        /* Felder mit 'store' liegen nicht in `missions` (mission_fields.php).
         * Erreichbar sind sie hier ohnehin nicht — die Besatzung bekommt unten
         * ihren eigenen Block —, aber die Bedingung steht da, damit ein
         * kuenftiges Feld mit eigener Ablage nicht als leerer Wert erscheint. */
        if (!mf_ist_spalte($f) && $type !== 'resources') { return; }
        $v = $m[$col] ?? null;
        if ($type === 'resources') {
            // Eigene Tabelle: jedes Rettungsmittel ist eine eigene Zeile
            $q = db()->prepare('SELECT name FROM mission_resources WHERE mission_id = ? ORDER BY id');
            $q->execute([(int)$m['id']]);
            $namen = $q->fetchAll(PDO::FETCH_COLUMN);
            if ($namen) {
                $fields[] = ['label' => $f['label'], 'value' => implode(', ', $namen)];
            }
            return;
        }
        if ($type === 'checkbox') {
            if ((int)$v === 1) {
                $fields[] = ['label' => $f['label'], 'value' => 'Ja'];
                foreach (($f['children'] ?? []) as $cc => $cf) { $collect($cc, $cf); }
            }
            return;
        }
        if ($v !== null && $v !== '') {
            /* Bei einem Auswahlfeld die BESCHRIFTUNG zeigen, nicht den
             * gespeicherten Wert: In der Einsatzansicht stuende sonst „ground"
             * statt „Boden" (mf_optionen, Web 6.1.0). Ein Wert ohne Eintrag in
             * der Liste bleibt sichtbar, wie er ist — er kann aus aelteren
             * Daten oder aus geaenderten Stammdaten stammen. */
            $anzeige = (string)$v;
            if ($type === 'select' && isset($f['options'])) {
                $anzeige = mf_optionen($f['options'])[$anzeige] ?? $anzeige;
            }
            $fields[] = ['label' => $f['label'], 'value' => $anzeige];
        }
        // Unterfelder von Nicht-Checkbox-Eltern (z. B. Schockraum unter
        // Transportziel) werden unabhaengig vom Elternwert verarbeitet — anders
        // als bei Checkbox-Eltern, wo sie an den Haken gebunden sind.
        foreach (($f['children'] ?? []) as $cc => $cf) { $collect($cc, $cf); }
    };
    foreach ($FIELDS as $col => $f) {
        // Die Besatzung bekommt unten einen eigenen Block ('crew_effektiv'),
        // der Tages- und Einsatzwerte bereits zusammenfuehrt. Ohne diese
        // Ausnahme stuende sie doppelt auf der Seite ("Abweichende Besatzung:
        // Ja" + Unterfelder). Ruecknahme = diese Zeile entfernen.
        if ($col === 'crew_override') { continue; }
        $collect($col, $f);
    }

    /* ---- Effektive Besatzung (COALESCE-Regel) -----------------------------
     *
     * Einsatzwert nur bei gesetztem Haken, sonst die Besatzung des Diensttags.
     * Die Regel ist unveraendert; sie laeuft seit Web 6.0.0 ueber zwei TABELLEN
     * statt ueber zwei Spaltensaetze (E7). Der frueher noetige Umweg — die
     * days-Zeile separat laden, weil 'SELECT *' und `days` dieselben
     * Spaltennamen crew_* trugen — ist damit entfallen: Die Namen stehen jetzt
     * in `day_crew` und `mission_crew` und koennen sich nicht ueberschreiben.
     *
     * WELCHE ROLLEN VORKOMMEN, sagt der DIENSTTAG (`day_crew`, E8) — nicht der
     * Katalog. Ein bodengebundener Dienst liefert Fahrer und Praktikant, ein
     * luftgebundener die Flugrollen, ein neutraler keine (E26). Rollen, die nur
     * am Einsatz belegt sind, kommen dazu: Sie stehen in der Datenbank, also
     * gehoeren sie angezeigt.
     */
    $dayId = $m['day_id'] !== null ? (int)$m['day_id'] : 0;
    $tagesCrew = $dayId > 0 ? dt_crew($dayId) : [];

    $mq = db()->prepare('SELECT role_code, name FROM mission_crew WHERE mission_id = ?');
    $mq->execute([$id]);
    $einsatzCrew = [];
    foreach ($mq->fetchAll() as $z) { $einsatzCrew[(string)$z['role_code']] = $z['name']; }

    $crewEff = [];
    $ovOn = (int)($m['crew_override'] ?? 0) === 1;
    // Reihenfolge: Katalog zuerst, danach was sonst noch belegt ist.
    $rollen = array_keys($tagesCrew + $einsatzCrew);
    $rollen = array_merge(
        array_values(array_filter(array_keys(CREW_ROLES),
            static fn(string $c): bool => in_array($c, $rollen, true))),
        array_values(array_filter($rollen,
            static fn(string $c): bool => !array_key_exists($c, CREW_ROLES)))
    );
    foreach ($rollen as $role) {
        $mVal  = trim((string)($einsatzCrew[$role] ?? ''));
        $dVal  = trim((string)($tagesCrew[$role] ?? ''));
        $nutzt = $ovOn && $mVal !== '';                  // Abweichung greift
        $eff   = $nutzt ? $mVal : $dVal;
        if ($eff === '') { continue; }                   // nur belegte Rollen
        $crewEff[$role] = [
            'label' => crew_role_label($role),
            'name'  => $eff,
            'abw'   => $nutzt && $mVal !== $dVal,
        ];
    }

    // Tagesnummer nach Alarmierungszeit (frueheste = 1)
    $no = db()->prepare('SELECT COUNT(*) + 1 FROM missions
                         WHERE user_id = ? AND day_id = ? AND started_at < ? AND deleted_at IS NULL');
    $no->execute([$userId, $dayId, $m['started_at']]);
    $dayNo = (int)$no->fetchColumn();

    // Phase 9 vorhanden? (Basis fuer Ende/Dauer)
    $p9 = db()->prepare('SELECT MAX(occurred_at) FROM mission_phases
                         WHERE mission_id = ? AND phase = 9');
    $p9->execute([$id]);
    $p9at = $p9->fetchColumn() ?: null;

    $pt = db()->prepare('SELECT lat, lon FROM track_points
                         WHERE owner_type = \'mission\' AND owner_id = ? ORDER BY seq');
    $pt->execute([$id]);
    $track = array_map(fn($p) => [(float)$p['lat'], (float)$p['lon']], $pt->fetchAll());

    $ph = db()->prepare('SELECT phase, occurred_at, lat, lon FROM mission_phases
                         WHERE mission_id = ? ORDER BY occurred_at');
    $ph->execute([$id]);
    $phases = array_map(fn($p) => [
        'phase' => (int)$p['phase'],
        'label' => PHASE_LABELS[(int)$p['phase']] ?? ('Phase ' . $p['phase']),
        'time'  => fmt_local($p['occurred_at']),
        'lat'   => $p['lat'] !== null ? (float)$p['lat'] : null,
        'lon'   => $p['lon'] !== null ? (float)$p['lon'] : null,
    ], $ph->fetchAll());

    $resus = null;
    $rs = db()->prepare('SELECT id, started_at FROM resus_sessions
                         WHERE mission_id = ? ORDER BY started_at');
    $rs->execute([$id]);
    $sessions = $rs->fetchAll();
    if ($sessions) {
        $ev = db()->prepare('SELECT type, occurred_at FROM resus_events
                             WHERE session_id = ? ORDER BY occurred_at');
        $resus = [];
        foreach ($sessions as $sess) {
            $ev->execute([(int)$sess['id']]);
            $events = [['label' => RESUS_LABELS['beginn'], 'time' => fmt_local($sess['started_at'])]];
            foreach ($ev->fetchAll() as $e2) {
                $events[] = ['label' => RESUS_LABELS[$e2['type']] ?? $e2['type'],
                             'time'  => fmt_local($e2['occurred_at'])];
            }
            $resus[] = $events;   // eine Tabelle je Reanimation
        }
    }

    /* Der Diensttag des Einsatzes — fuer die Anzeige mit seinen EINGEFRORENEN
     * Angaben (E8). `day` bleibt als Datum in der Antwort, weil die
     * Einsatzansicht es im Kopf zeigt; dazu kommen Kennung, Art und
     * Bezeichnungen, damit die Seite den Dienst benennen kann, ohne die
     * Stammdaten zu befragen. */
    $tag = $dayId > 0 ? dt_laden($userId, $dayId) : null;
    $sym = dt_art_symbol($tag !== null && $tag['kind'] !== null ? (string)$tag['kind'] : null);

    /* ---- Abfahrtort aufloesen (E34, Konzept 4.6.1) ------------------------
     *
     * Gespeichert ist nur die REGEL. Woher die Koordinate kommt, haengt an ihr —
     * und damit auch, ob sie ueberhaupt hierher gehoert:
     *
     *   base       days.base_lat/base_lon, eingefroren beim Anlegen  Klartext
     *   prev_dest  dest_lat/dest_lon des vorherigen Einsatzes        Klartext
     *   prev_site  Einsatzort des vorherigen Einsatzes               verschlüsselt
     *   manual     pat_blob.start DIESES Einsatzes                   verschlüsselt
     *
     * Die beiden Klartextfaelle loest der Server auf, die beiden anderen der
     * Browser: 'manual' steht ohnehin im eigenen Blob, fuer 'prev_site' geht der
     * Blob des Vorgaengers mit — anders ist er nicht lesbar, der Server kann es
     * nicht.
     *
     * GELIEFERT WIRD NUR, WAS DIE GEWAEHLTE REGEL BRAUCHT. Den Blob eines
     * anderen Einsatzes mitzuschicken, wo niemand ihn auswertet, waere eine
     * Datenweitergabe ohne Zweck — auch innerhalb desselben Kontos.
     *
     * VORHERIGER EINSATZ ist der zeitlich unmittelbar vorangehende DESSELBEN
     * Diensttags; Papierkorbeintraege zaehlen nicht mit (A13q). Gibt es keinen,
     * bleibt die Quelle leer und es entsteht keine Linie — es wird NICHT auf
     * eine andere ausgewichen (A13i). */
    $startSrc = $m['start_src'] !== null ? (string)$m['start_src'] : null;
    $startBase = null; $startPrevDest = null; $startPrevBlob = null;

    if ($startSrc === 'base' && $tag !== null
        && $tag['base_lat'] !== null && $tag['base_lon'] !== null) {
        $startBase = ['lat' => (float)$tag['base_lat'], 'lon' => (float)$tag['base_lon']];
    }
    if (($startSrc === 'prev_dest' || $startSrc === 'prev_site') && $dayId > 0) {
        $vq = db()->prepare('SELECT dest_lat, dest_lon, pat_blob FROM missions
                              WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL
                                AND started_at < ?
                              ORDER BY started_at DESC, id DESC LIMIT 1');
        $vq->execute([$userId, $dayId, $m['started_at']]);
        $vor = $vq->fetch();
        if ($vor) {
            if ($startSrc === 'prev_dest'
                && $vor['dest_lat'] !== null && $vor['dest_lon'] !== null) {
                $startPrevDest = ['lat' => (float)$vor['dest_lat'],
                                  'lon' => (float)$vor['dest_lon']];
            }
            if ($startSrc === 'prev_site' && !empty($vor['pat_blob'])) {
                $startPrevBlob = (string)$vor['pat_blob'];
            }
        }
    }

    json_out([
        'id' => (int)$m['id'],
        'day_id' => $dayId,
        'day' => $tag !== null ? (string)$tag['day'] : null,
        /* Das ECHTE Einsatzdatum in Ortszeit. Bezugstag der Altersberechnung
         * und der Anzeige — nicht das Datum des Diensttags: Ein Einsatz um
         * 01:30 eines Dienstes vom Vortag hat sein eigenes Datum (E14). */
        'mission_day' => fmt_local($m['started_at'], 'Y-m-d'),
        'day_kind'         => $tag !== null && $tag['kind'] !== null ? (string)$tag['kind'] : null,
        'day_art_zeichen'  => $sym['zeichen'],
        'day_art_text'     => $sym['text'],
        'day_vehicle_name' => $tag !== null && $tag['vehicle_name'] !== null
                              ? (string)$tag['vehicle_name'] : null,
        'day_base_name'    => $tag !== null && $tag['base_name'] !== null
                              ? (string)$tag['base_name'] : null,
        'start_hhmm' => fmt_local($m['started_at']),
        'end_hhmm'   => fmt_local($m['ended_at']),
        'distance_m' => $m['distance_m'] !== null ? (int)$m['distance_m'] : null,
        'ascent_m'   => $m['ascent_m']   !== null ? (int)$m['ascent_m']   : null,
        'site_ele_m' => $m['site_ele_m'] !== null ? (int)$m['site_ele_m'] : null,
        'origin'     => (string)($m['origin'] ?? 'watch'),
        'edited'     => (int)($m['edited'] ?? 0) === 1,
        'day_no'     => $dayNo,
        'has_p9'     => $p9at !== null,
        /* Zielklinik-Koordinate: KLARTEXT wie ihr Name (E40). Ihr Pin ist damit
         * ohne Freischalten sichtbar — anders als Einsatzort und Linie, deren
         * mittlerer Stuetzpunkt verschluesselt ist (A13o). */
        'dest_lat'   => $m['dest_lat'] !== null ? (float)$m['dest_lat'] : null,
        'dest_lon'   => $m['dest_lon'] !== null ? (float)$m['dest_lon'] : null,
        /* Der Name daneben, damit der Pin sich benennen kann. Er steht zwar
         * auch in 'fields' — dort aber als Beschriftung/Wert-Paar fuer die
         * Anzeigeliste, und ihn von dort ueber seine Beschriftung
         * zurueckzusuchen waere eine Kopplung an einen Anzeigetext. */
        'dest_name'  => ($m['transport_dest'] ?? null) !== null && $m['transport_dest'] !== ''
                        ? (string)$m['transport_dest'] : null,
        'start_src'        => $startSrc,
        'start_base'       => $startBase,
        'start_prev_dest'  => $startPrevDest,
        'start_prev_blob'  => $startPrevBlob,
        'fields'     => $fields,
        'crew_effektiv' => (object)$crewEff,
        'pat_blob'   => !empty($m['pat_blob']) ? (string)$m['pat_blob'] : null,
        'pat_wrap'   => $patWrapPw,
        'track' => $track, 'phases' => $phases, 'resus' => $resus,
    ]);
} catch (Throwable $ex) {
    // Statt eines leeren HTTP 500 eine lesbare Fehlermeldung.
    json_fehler($ex, 'mission');
}
