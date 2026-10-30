<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../mission_fields_lib.php';
require_once __DIR__ . '/../einsatz_lib.php';
require_once __DIR__ . '/../diensttag_lib.php';
require_once __DIR__ . '/../spur_lib.php';   // Spuren: Zeilen UND Blob

// Nur lesen — derselbe Grund wie bei den uebrigen lesenden
// Endpunkten: Was nichts aendert, beantwortet auch kein POST.
api_methode('GET');

try {
    $id = (int)($_GET['id'] ?? 0);

    $m = einsatz_laden($id, $userId);   // Datentrennung steckt in der Funktion
    if (!$m) json_out(['error' => 'not_found'], 404);

    /* Zusatzfelder generisch aus der zentralen Definition (mission_fields.php).
     *
     * JEDER EINTRAG NENNT SEINE SPALTE ('col'). Die Einsatzansicht
     * ordnet die Liste selbst — Name, Geburtsdatum, Einsatzort,
     * Diagnose, Notizen und der Transportblock stehen dort in einer fachlich
     * gewaehlten Reihenfolge, die nicht die des Katalogs ist. Ohne die Spalte
     * muesste sie die Felder ueber ihre BESCHRIFTUNG zurueckerkennen; eine
     * Umbenennung im Katalog haette die Sortierung dann still zerlegt. */
    $FIELDS = require __DIR__ . '/../mission_fields.php';
    $fields = [];
    $collect = function (string $col, array $f) use (&$collect, &$fields, $m) {
        $type = $f['type'] ?? 'text';
        /* Felder mit 'store' liegen nicht in `missions` (mission_fields.php).
         * Erreichbar sind sie hier ohnehin nicht — die Besatzung bekommt unten
         * ihren eigenen Block —, aber die Bedingung steht da, damit ein
         * kuenftiges Feld mit eigener Ablage nicht als leerer Wert erscheint. */
        if (!mf_ist_spalte($f) && $type !== 'resources') {
            /* DIE KINDER GEHEN TROTZDEM DURCH. Seit das
             * Transportziel im Blob liegt, ist es das erste verschluesselte
             * Feld MIT Kindern — und der Haken „Schockraum" darunter ist
             * Klartext. Ein blosses `return` haette ihn aus der Ansicht
             * genommen. */
            foreach (($f['children'] ?? []) as $cc => $cf) { $collect($cc, $cf); }
            return;
        }
        $v = $m[$col] ?? null;
        if ($type === 'resources') {
            // Eigene Tabelle: jedes Rettungsmittel ist eine eigene Zeile
            $q = db()->prepare('SELECT name FROM mission_resources WHERE mission_id = ? ORDER BY id');
            $q->execute([(int)$m['id']]);
            $namen = $q->fetchAll(PDO::FETCH_COLUMN);
            if ($namen) {
                $fields[] = ['col' => $col, 'label' => $f['label'],
                             'value' => implode(', ', $namen)];
            }
            return;
        }
        if ($type === 'checkbox') {
            if ((int)$v === 1) {
                $fields[] = ['col' => $col, 'label' => $f['label'], 'value' => 'Ja'];
                foreach (($f['children'] ?? []) as $cc => $cf) { $collect($cc, $cf); }
            }
            return;
        }
        if ($v !== null && $v !== '') {
            /* Bei einem Auswahlfeld die BESCHRIFTUNG zeigen, nicht den
             * gespeicherten Wert: In der Einsatzansicht stuende sonst „ground"
             * statt „Boden" (mf_optionen). Ein Wert ohne Eintrag in
             * der Liste bleibt sichtbar, wie er ist — er kann aus aelteren
             * Daten oder aus geaenderten Stammdaten stammen. */
            $anzeige = (string)$v;
            if ($type === 'select' && isset($f['options'])) {
                $anzeige = mf_optionen($f['options'])[$anzeige] ?? $anzeige;
            }
            $fields[] = ['col' => $col, 'label' => $f['label'], 'value' => $anzeige];
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
     * Die Regel laeuft ueber zwei TABELLEN: Die Namen stehen in `day_crew`
     * und `mission_crew` und koennen sich nicht ueberschreiben.
     *
     * WELCHE ROLLEN VORKOMMEN, sagt der DIENSTTAG (`day_crew`) — nicht der
     * Katalog. Ein bodengebundener Dienst liefert Fahrer und Praktikant, ein
     * luftgebundener die Flugrollen, ein neutraler keine. Rollen, die nur
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

    /* DIE SPUR LIEST DER BROWSER. Die Punkte kommen nur verschlossen an;
     * `assets/ortsdaten.js` oeffnet die Stuecke und rechnet den Index des
     * zeitlich naechsten Punkts je Phase (`track_idx`) dort, wo die Punkte
     * sind. */
    $ph = db()->prepare('SELECT phase, occurred_at FROM mission_phases
                         WHERE mission_id = ? ORDER BY occurred_at');
    $ph->execute([$id]);
    $phases = array_map(fn($p) => [
        'phase' => (int)$p['phase'],
        'label' => PHASE_LABELS[(int)$p['phase']] ?? ('Phase ' . $p['phase']),
        'time'  => fmt_local($p['occurred_at']),
        /* Die Zeit auch als Epoche: Der Browser ordnet damit die
         * Phasen der Einsatzhuelle zu (Zeiten dort relativ zum Beginn). */
        'ts'    => $p['occurred_at'] !== null
                   ? (new DateTime((string)$p['occurred_at'], new DateTimeZone('UTC')))->getTimestamp() : null,
    ], $ph->fetchAll());

    /* DIE REANIMATION STEHT IN DER EINSATZHUELLE. Der Browser baut die
     * Ereignislisten aus der Huelle. Die
     * Beschriftungen und die Zeitzone liefert die Antwort mit. */

    /* Der Diensttag des Einsatzes — fuer die Anzeige mit seinen EINGEFRORENEN
     * Angaben. `day` bleibt als Datum in der Antwort, weil die
     * Einsatzansicht es im Kopf zeigt; dazu kommen Kennung, Art und
     * Bezeichnungen, damit die Seite den Dienst benennen kann, ohne die
     * Stammdaten zu befragen. */
    $tag = $dayId > 0 ? dt_laden($userId, $dayId) : null;
    /* Zeichen aus dem Typ, wo einer steht (eine Funktion, alle
     * Stellen). Ohne den zweiten Wert truege die Einsatzansicht den
     * Hubschrauber, waehrend die Leiste daneben den Berg zeigt. */
    $sym = dt_art_symbol($tag !== null && $tag['kind'] !== null ? (string)$tag['kind'] : null,
                         $tag !== null && $tag['vehicle_typ'] !== null
                             ? (string)$tag['vehicle_typ'] : null);

    /* ---- Abfahrtort aufloesen ----------------------------------------------
     *
     * Gespeichert ist nur die REGEL. Woher die Koordinate kommt, haengt an ihr —
     * und damit auch, ob sie ueberhaupt hierher gehoert:
     *
     *   base       days.base_lat/base_lon, eingefroren beim Anlegen  Klartext
     *   prev_dest  Transportziel des vorherigen Einsatzes            verschlüsselt
     *   prev_site  Einsatzort des vorherigen Einsatzes               verschlüsselt
     *   manual     pat_blob.start DIESES Einsatzes                   verschlüsselt
     *
     * Den Klartextfall loest der Server auf, die drei anderen der Browser:
     * 'manual' steht ohnehin im eigenen Blob, fuer 'prev_site' und 'prev_dest'
     * geht der Blob des Vorgaengers mit — anders ist er nicht lesbar, der
     * Server kann es nicht.
     *
     * GELIEFERT WIRD NUR, WAS DIE GEWAEHLTE REGEL BRAUCHT. Den Blob eines
     * anderen Einsatzes mitzuschicken, wo niemand ihn auswertet, waere eine
     * Datenweitergabe ohne Zweck — auch innerhalb desselben Kontos.
     *
     * VORHERIGER EINSATZ ist der zeitlich unmittelbar vorangehende DESSELBEN
     * Diensttags; Papierkorbeintraege zaehlen nicht mit. Gibt es keinen,
     * bleibt die Quelle leer und es entsteht keine Linie — es wird NICHT auf
     * eine andere ausgewichen. */
    $startSrc = $m['start_src'] !== null ? (string)$m['start_src'] : null;
    $startBase = null; $startPrevBlob = null;

    if ($startSrc === 'base' && $tag !== null
        && $tag['base_lat'] !== null && $tag['base_lon'] !== null) {
        $startBase = ['lat' => (float)$tag['base_lat'], 'lon' => (float)$tag['base_lon']];
    }
    /* AUCH BEI 'prev_dest' NUR DER BLOB. Das Transportziel liegt im
     * `pat_blob`; der Server kann es nicht lesen und reicht den Blob des
     * Vorgaengers durch wie bei 'prev_site'. Die Einsatzansicht liest daraus
     * `transport_dest`. */
    if (($startSrc === 'prev_dest' || $startSrc === 'prev_site') && $dayId > 0) {
        $vq = db()->prepare('SELECT pat_blob FROM missions
                              WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL
                                AND started_at < ?
                              ORDER BY started_at DESC, id DESC LIMIT 1');
        $vq->execute([$userId, $dayId, $m['started_at']]);
        $vor = $vq->fetch();
        if ($vor && !empty($vor['pat_blob'])) {
            $startPrevBlob = (string)$vor['pat_blob'];
        }
    }

    json_out([
        'id' => (int)$m['id'],
        'day_id' => $dayId,
        'day' => $tag !== null ? (string)$tag['day'] : null,
        /* Das ECHTE Einsatzdatum in Ortszeit. Bezugstag der Altersberechnung
         * und der Anzeige — nicht das Datum des Diensttags: Ein Einsatz um
         * 01:30 eines Dienstes vom Vortag hat sein eigenes Datum. */
        'mission_day' => fmt_local($m['started_at'], 'Y-m-d'),
        'day_kind'         => $tag !== null && $tag['kind'] !== null ? (string)$tag['kind'] : null,
        'day_art_symbol'   => $sym['symbol'],
        'day_art_text'     => $sym['text'],
        'day_vehicle_name' => $tag !== null && $tag['vehicle_name'] !== null
                              ? (string)$tag['vehicle_name'] : null,
        'day_base_name'    => $tag !== null && $tag['base_name'] !== null
                              ? (string)$tag['base_name'] : null,
        /* Standort-Koordinate des Tages (eingefroren) — Klartext wie ihr
         * Name; die Einsatzansicht zeichnet daraus das Haus-Schild. */
        'base_lat' => $tag !== null && $tag['base_lat'] !== null ? (float)$tag['base_lat'] : null,
        'base_lon' => $tag !== null && $tag['base_lon'] !== null ? (float)$tag['base_lon'] : null,
        'start_hhmm' => fmt_local($m['started_at']),
        'end_hhmm'   => fmt_local($m['ended_at']),
        'distance_m' => $m['distance_m'] !== null ? (int)$m['distance_m'] : null,
        /* `ascent_m` steht hier NICHT. Die Einsatzansicht zeigt die Steigung
         * nicht an: Sie ist das Profil der geflogenen Strecke, keine Aussage
         * ueber den Einsatz. Erhalten bleibt sie ueberall sonst — in der
         * Spalte, im Export (`hoehenmeter_m`), im Import und im Backup. */
        /* `site_ele_m` steht hier NICHT. Die Hoehe des
         * Einsatzorts liegt im `pat_blob` (`ortshoehe`); die Einsatzansicht
         * liest sie nach dem Entsperren von dort. */
        'origin'     => (string)($m['origin'] ?? 'watch'),
        'edited'     => (int)($m['edited'] ?? 0) === 1,
        'day_no'     => $dayNo,
        /* `hat_ende` und nicht „gibt es Phase 9". Die Einsatzansicht fragt
         * damit, ob sie eine Endzeit anzeigen darf -- und Phase 9 waere dafuer
         * nur ein Stellvertreter: Ein geschnittener oder importierter Einsatz
         * hat keine Phase 9, aber sehr wohl ein Ende. Gefragt wird das, was
         * gemeint ist. */
        'hat_ende'   => $m['ended_at'] !== null,
        /* `dest_lat`, `dest_lon`, `dest_name` und `start_prev_dest` stehen hier
         * NICHT. Das Transportziel liegt im `pat_blob`
         * (`transport_dest` = {addr, lat, lon}); sein Pin erscheint erst nach
         * dem Entsperren. */
        'start_src'        => $startSrc,
        'start_base'       => $startBase,
        'start_prev_blob'  => $startPrevBlob,
        'fields'     => $fields,
        'crew_effektiv' => (object)$crewEff,
        'pat_blob'   => !empty($m['pat_blob']) ? (string)$m['pat_blob'] : null,
        'pat_wrap'   => $patWrapPw,
        'phases' => $phases,
        'reanimationsarten' => RESUS_LABELS,
        'zeitzone'     => (string)konfig('app.timezone', 'Europe/Berlin'),
        /* ---- DIE ORTSDATEN ALS GERAETEHUELLEN -------------------------------
         *
         * Spur, Phasenkoordinaten und Reanimation kommen nur verschlossen an;
         * der Server liefert sie hier aus, wie sie liegen, und oeffnet nichts.
         * Dazu, was der Browser zum Oeffnen und Einordnen braucht:
         * `client_ref` (Bindung), `started_ts` (die Zeiten der Einsatzhuelle
         * zaehlen von dort) und die Sperrzeiten aus Schnitten (gesperrte
         * Punkte blendet der Browser aus).
         *
         * `track`, `phases[].lat/lon/track_idx` und `resus` setzt der Browser
         * (`assets/ortsdaten.js`). */
        'client_ref'   => (string)$m['client_ref'],
        'started_ts'   => (new DateTime((string)$m['started_at'], new DateTimeZone('UTC')))->getTimestamp(),
        'spur_stuecke' => spur2_lesen(db(), 'mission', $id),
        'spur_sperren' => array_map(fn($x) => ['von' => $x['von_ts'], 'bis' => $x['bis_ts']],
                                    schnitte_lesen(db(), 'mission', $id)),
        'ort_huelle'   => ($m['ort_huelle'] ?? null) !== null ? base64_encode((string)$m['ort_huelle']) : null,
    ]);
} catch (Throwable $ex) {
    // Statt eines leeren HTTP 500 eine lesbare Fehlermeldung.
    json_fehler($ex, 'mission');
}
