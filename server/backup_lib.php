<?php
declare(strict_types=1);
/**
 * Backup: Serialisierung und Wiederherstellung aller Daten einer NutzerIn.
 * Format-Doku: docs/Backup-Format.md
 *
 * Seit Format 2 versiegelt und oeffnet der BROWSER die Datei (crypto.js):
 * Er entschluesselt die geschuetzten Angaben vor dem Export und verschluesselt
 * sie beim Import mit dem Schluessel des Zielkontos neu — dadurch laesst sich
 * ein Backup in jedes Konto einspielen. Der Container ist in
 * docs/Backup-Format.md beschrieben; diese Datei serialisiert nur noch die
 * Daten (edbak_build) und spielt sie zurueck (edbak_restore).
 */

function edbak_build(int $userId): string {
    $pdo = db();
    $q = function (string $sql, array $p) use ($pdo): array {
        $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(PDO::FETCH_ASSOC);
    };

    $u = $q('SELECT email, name, pat_key_check FROM users WHERE id = ?', [$userId])[0];

    /* Umwandeln nur, wenn dabei auch eine Zahl herauskommt (M2-12).
     *
     * (float)$p['ele'] liefert fuer jede Eingabe klaglos ein Ergebnis: aus
     * "" wird 0.0, aus "Unfug" ebenfalls. Die Hoehe 0 ist aber ein GUELTIGER
     * Wert (Meereshoehe) — in der Sicherung waere danach nicht mehr zu
     * unterscheiden, ob dort eine gemessene Null stand oder ein Rest, den die
     * Umwandlung erzeugt hat.
     *
     * Aus der Datenbank kommen an dieser Stelle nur Zahlen oder NULL; die
     * Spalten sind DOUBLE. Der Befund zielt auf den Fall, in dem das einmal
     * nicht mehr stimmt — eine Sicherung ist das letzte, was stillschweigend
     * Ersatzwerte erfinden darf. */
    $zahl = fn($v, bool $ganz = false) =>
        (is_numeric($v) ? ($ganz ? (int)$v : (float)$v) : null);

    /* SPUREN GEBUENDELT HOLEN (M5-12).
     *
     * Hier stand eine Funktion, die JE EINSATZ und JE RUHESEGMENT eine eigene
     * Abfrage absetzte — zusammen mit Phasen, Rettungsmitteln und Reanimation
     * waren das bei 1600 Einsaetzen ueber 6000 Abfragen fuer EINE Sicherung.
     * Eine Obergrenze gab es nicht: Die Zahl waechst mit dem Bestand, und die
     * Sicherung ist genau die Handlung, die jemand ausfuehrt, wenn er ohnehin
     * schon beunruhigt ist.
     *
     * Jetzt: eine Abfrage je Tabelle, in Bloecken (sql_in_bloecken, db.php).
     * Die Reihenfolge kommt weiterhin aus dem SQL — sie ist Teil des
     * Dateiformats, nicht Zufall.
     */
    $spuren = function (string $type, array $ids) use ($pdo, $zahl): array {
        $nach = [];
        foreach ($ids as $id) { $nach[$id] = []; }
        if (!$ids) { return $nach; }
        foreach (sql_in_bloecken($pdo,
                'SELECT owner_id, seq, lat, lon, ele, ts FROM track_points
                 WHERE owner_type = ? AND owner_id IN ({IDS}) ORDER BY owner_id, seq',
                $ids, [$type]) as $p) {
            $nach[(int)$p['owner_id']][] = [
                $zahl($p['seq'], true), $zahl($p['lat']), $zahl($p['lon']),
                $zahl($p['ele']), $zahl($p['ts'], true)];
        }
        return $nach;
    };

    $missions = [];
    /* SPALTEN AUFZAEHLEN, NICHT ALLE NEHMEN (M5-07).
     *
     * Vorher stand hier SELECT * und darunter ein unset() fuer die drei
     * internen Spalten. Das FORMAT der Sicherung war damit nicht definiert,
     * sondern ERGAB SICH: Was in der Tabelle stand, landete in der Datei.
     *
     * Zwei Folgen, beide unangenehm:
     *  - Jede neue Spalte war automatisch in jeder Sicherung — auch eine,
     *    die dort nichts zu suchen hat. Wer eine interne Spalte ergaenzt,
     *    denkt nicht daran, dass er damit das Ausgabeformat aendert.
     *  - Das Wiedereinspielen und der Export prueften gegen eine Liste, die
     *    hier nirgends stand. Ob beide dasselbe meinten, liess sich nur
     *    durch Ausprobieren feststellen.
     *
     * Kommt kuenftig eine Spalte hinzu, die mitgesichert werden soll, ist sie
     * hier einzutragen — und genau das ist der Punkt: Es ist eine
     * Entscheidung, keine Nebenwirkung. */
    $missionSpalten = 'client_ref, day, started_at, ended_at, distance_m, ascent_m,
                       site_ele_m, final, manual, origin, edited, transport_dest,
                       winch, winch_cycles, winch_cycles_pat, winch_airload,
                       bergwacht, secondary, schockraum, bw_unit, bw_info,
                       other_ema, crew_override,
                       crew_p1, crew_p2, crew_hems, crew_fr, crew_other,
                       pat_blob, notes, created_at, deleted_at, deleted_with_day';
    /* NICHT in der Liste, und zwar mit Absicht:
     *
     *   id, user_id, device_id   Interne Verweise. Sie gelten nur in DIESER
     *                            Datenbank; eine Sicherung soll sich auch in
     *                            eine andere einspielen lassen.
     *
     *   other_resources          TOTE ALTSPALTE. Seit der Migration
     *                            2026_07 liegen die weiteren Rettungsmittel
     *                            als einzelne Zeilen in mission_resources
     *                            (dort einzeln entfernbar) und werden unten
     *                            als 'resources' mitgesichert. Die Spalte
     *                            wurde damals nur nicht geloescht. Mit
     *                            SELECT * ging sie trotzdem in jede
     *                            Sicherung — ein Feld, das seit Monaten
     *                            niemand mehr fuellt und das beim
     *                            Einspielen verworfen wird.
     *
     * WAS BEIM EINSPIELEN NICHT ANKOMMT (vorgefunden, hier nicht geaendert):
     * Der Einspielweg schreibt die Spalten aus mission_fields.php plus
     * pat_blob. site_ele_m steht dort nicht — die Einsatzort-Hoehe wird beim
     * Uhr-Upload gerechnet, nicht eingegeben. Sie ist in der Sicherung
     * enthalten (die Datei soll den Bestand vollstaendig abbilden), kommt
     * beim Einspielen aber nicht zurueck. Das ist eine Asymmetrie, die dieses
     * Paket nur SICHTBAR macht; sie zu beheben hiesse, den Einspielweg zu
     * aendern, und das ist ein eigener Vorgang. */
    $missionZeilen = $q("SELECT id, $missionSpalten FROM missions
                         WHERE user_id = ? AND deleted_at IS NULL ORDER BY started_at", [$userId]);
    $missionIds = array_map(static fn($m) => (int)$m['id'], $missionZeilen);

    // Phasen, Rettungsmittel, Reanimation: je eine Abfrage statt je Einsatz
    // (M5-12). Die Zuordnung geschieht im Speicher.
    $phasenNach = $mittelNach = $sitzungenNach = [];
    if ($missionIds) {
        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, phase, occurred_at, lat, lon FROM mission_phases
                 WHERE mission_id IN ({IDS}) ORDER BY mission_id, occurred_at',
                $missionIds) as $p) {
            $phasenNach[(int)$p['mission_id']][] = [
                'phase' => $zahl($p['phase'], true), 'occurred_at' => $p['occurred_at'],
                'lat' => $zahl($p['lat']), 'lon' => $zahl($p['lon'])];
        }
        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, name FROM mission_resources
                 WHERE mission_id IN ({IDS}) ORDER BY mission_id, id',
                $missionIds) as $r) {
            $mittelNach[(int)$r['mission_id']][] = (string)$r['name'];
        }
        $sitzungen = sql_in_bloecken($pdo,
            'SELECT id, mission_id, started_at FROM resus_sessions
             WHERE mission_id IN ({IDS}) ORDER BY mission_id, started_at',
            $missionIds);
        $ereignisseNach = [];
        $sitzungsIds = array_map(static fn($s) => (int)$s['id'], $sitzungen);
        if ($sitzungsIds) {
            foreach (sql_in_bloecken($pdo,
                    'SELECT session_id, type, occurred_at FROM resus_events
                     WHERE session_id IN ({IDS}) ORDER BY session_id, occurred_at',
                    $sitzungsIds) as $e) {
                $ereignisseNach[(int)$e['session_id']][] = [
                    'type' => $e['type'], 'occurred_at' => $e['occurred_at']];
            }
        }
        foreach ($sitzungen as $s) {
            $sitzungenNach[(int)$s['mission_id']][] = [
                'started_at' => $s['started_at'],
                'events'     => $ereignisseNach[(int)$s['id']] ?? [],
            ];
        }
    }
    $spurNachEinsatz = $spuren('mission', $missionIds);

    foreach ($missionZeilen as $m) {
        $mid = (int)$m['id'];
        unset($m['id']);        // nur fuer die Zuordnung gebraucht
        $m['phases']    = $phasenNach[$mid]    ?? [];
        $m['resources'] = $mittelNach[$mid]    ?? [];
        $m['resus']     = $sitzungenNach[$mid] ?? [];
        $m['track']     = $spurNachEinsatz[$mid] ?? [];
        $missions[] = $m;
    }

    $rests = [];
    $restZeilen = $q('SELECT id, client_ref, day, started_at, ended_at, final,
                             deleted_at, deleted_with_day
                      FROM rest_segments
                      WHERE user_id = ? AND deleted_at IS NULL ORDER BY started_at', [$userId]);
    $spurNachRuhe = $spuren('rest', array_map(static fn($r) => (int)$r['id'], $restZeilen));
    foreach ($restZeilen as $r) {
        $rid = (int)$r['id'];
        unset($r['id']);
        $r['track'] = $spurNachRuhe[$rid] ?? [];
        $rests[] = $r;
    }

    // Flugtage: Verweise fuer Portabilitaet in Namen aufloesen
    $days = [];
    foreach ($q('SELECT d.day, d.crew_p1, d.crew_p2, d.crew_hems, d.crew_fr,
                        d.crew_other, d.aircraft, d.base, d.crew, d.notes, d.deleted_at,
                        a.registration AS aircraft_reg, b.name AS base_name
                 FROM days d
                 LEFT JOIN aircraft a ON a.id = d.aircraft_id
                 LEFT JOIN bases b ON b.id = d.base_id
                 WHERE d.user_id = ? AND d.deleted_at IS NULL ORDER BY d.day', [$userId]) as $d) {
        $days[] = $d;
    }

    // Standard-Markierungen liegen seit user_defaults nicht mehr an der Zeile
    // selbst, werden im Exportformat aber weiterhin als is_default-Flag je
    // Zeile abgebildet (Abwaertskompatibilitaet, s. docs/Backup-Format.md)
    $defBaseId = (int)($q('SELECT item_id FROM user_defaults WHERE user_id = ? AND kind = "base"', [$userId])[0]['item_id'] ?? 0);
    $defAcId   = (int)($q('SELECT item_id FROM user_defaults WHERE user_id = ? AND kind = "aircraft"', [$userId])[0]['item_id'] ?? 0);

    $bases = $q('SELECT id, name FROM bases WHERE user_id = ? ORDER BY name', [$userId]);
    foreach ($bases as &$b) { $b['is_default'] = (int)$b['id'] === $defBaseId ? 1 : 0; unset($b['id']); }
    unset($b);
    $aircraft = $q('SELECT id, registration, p1, p2, hems, fr, other
                    FROM aircraft WHERE user_id = ? ORDER BY registration', [$userId]);
    foreach ($aircraft as &$a) { $a['is_default'] = (int)$a['id'] === $defAcId ? 1 : 0; unset($a['id']); }
    unset($a);

    $data = [
        'format' => 'einsatzdoku-backup',
        'version' => 5,
        'created_at' => gmdate('c'),
        'app' => 'einsatzdoku-luftrettung',
        'user' => ['email' => $u['email'], 'name' => $u['name']],
        /* Pruefsumme des Inhaltsschluessels dieses Kontos.
         *
         * Sie steht hier, damit sich beim Wiedereinspielen entscheiden laesst,
         * ob ein in der Datei MITGEFUEHRTER Chiffretext (Einsaetze, die beim
         * Sichern nicht entschluesselt werden konnten) in diesem Konto
         * ueberhaupt lesbar waere. Ohne diese Angabe bliebe nur Raten.
         *
         * Sie verraet nichts: Der Inhaltsschluessel ist 256 Bit Zufall, aus
         * seinem Hashwert nicht zurueckrechenbar. Bei Konten aus der Zeit vor
         * Web 4.0.0 kann sie fehlen (null) — dann ist die Zuordnung eben
         * unbekannt, und das Einspielen sagt das auch. */
        'pat_key_check' => $u['pat_key_check'] ?? null,
        'stammdaten' => [
            'bases'           => $bases,
            'aircraft'        => $aircraft,
            'crew_presets'    => $q('SELECT role, name FROM crew_presets WHERE user_id = ? ORDER BY role, name', [$userId]),
            'bw_units'        => $q('SELECT name FROM bw_units WHERE user_id = ? ORDER BY name', [$userId]),
            'resources'       => $q('SELECT name FROM resources WHERE user_id = ? ORDER BY name', [$userId]),
            'transport_dests' => $q('SELECT name FROM transport_dests WHERE user_id = ? ORDER BY name', [$userId]),
        ],
        'days' => $days,
        'missions' => $missions,
        'rest_segments' => $rests,
    ];
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

/* ======================= Import ======================= */

/**
 * Leitet Herkunft (origin) und Bearbeitungsstatus (edited) fuer einen
 * wiederherzustellenden Einsatz ab. Enthaelt der Datensatz gueltige Werte,
 * werden diese uebernommen (Formatversion 4). Fehlen sie (Version <= 3),
 * gilt dieselbe Ableitungsregel wie in der Migration
 * 2026_07_30_herkunft_bearbeitungsstatus (update.php): client_ref-Praefix
 * 'man-' -> manual, 'imp-' -> import, sonst watch; edited = 1 nur, wenn
 * manual = 1 und kein solches Praefix vorliegt. Absichtlich an einer
 * einzigen Stelle formuliert, damit Migration und Restore die Regel nicht
 * zweimal unterschiedlich hinschreiben.
 */
function edbak_origin_edited(array $m): array {
    $ref = (string)($m['client_ref'] ?? '');
    $erlaubt = ['watch', 'manual', 'import'];

    if (isset($m['origin']) && in_array($m['origin'], $erlaubt, true)) {
        $origin = $m['origin'];
    } elseif (str_starts_with($ref, 'man-')) {
        $origin = 'manual';
    } elseif (str_starts_with($ref, 'imp-')) {
        $origin = 'import';
    } else {
        $origin = 'watch';
    }

    if (isset($m['edited'])) {
        $edited = (int)$m['edited'];
    } else {
        $edited = ((int)($m['manual'] ?? 0) === 1
                   && !str_starts_with($ref, 'man-')
                   && !str_starts_with($ref, 'imp-')) ? 1 : 0;
    }

    return ['origin' => $origin, 'edited' => $edited];
}

/** @return array Zusammenfassung (Zaehler) */
function edbak_restore(int $userId, array $data): array {
    $pdo = db();
    $stats = ['missions' => 0, 'missions_skipped' => 0, 'rests' => 0, 'rests_skipped' => 0,
              'days' => 0, 'stammdaten' => 0, 'stammdaten_skipped' => 0];

    /* URSACHEN GETRENNT ZAEHLEN (M5-14).
     * "40 uebersprungen" ist nicht deutbar: Es kann heissen "alles war schon
     * da" (gut) oder "alles war kaputt" (schlecht). Genau diese Unterscheidung
     * braucht, wer eine Wiederherstellung beurteilen muss. */
    $grund = ['bereits_vorhanden' => 0, 'datum_oder_zeit' => 0, 'aufbau' => 0,
              'tag_im_papierkorb' => 0, 'tag_unbrauchbar' => 0];

    // Flugtage im Papierkorb einmal vorab feststellen (D1).
    $tageImPapierkorb = [];
    $qT = $pdo->prepare('SELECT day FROM days WHERE user_id = ? AND deleted_at IS NOT NULL');
    $qT->execute([$userId]);
    foreach ($qT->fetchAll(PDO::FETCH_COLUMN) as $dTrash) {
        $tageImPapierkorb[(string)$dTrash] = true;
    }
    $pruef = new Pruefliste();
    $hoeheOffen = [];   // Einsatz-IDs fuer die Hoehenberechnung nach dem Commit (M5-05)

    $pdo->beginTransaction();
    try {
        /* Stammdaten (INSERT IGNORE ueber die Unique-Schluessel; zentral
         * vorhandene Eintraege werden uebersprungen und gezaehlt, s. 6.3/8) */
        $sd = $data['stammdaten'] ?? [];
        /* Vorbereitete Anweisung, nicht eingesetzter Wert (M5-06).
         *
         * $userId ist hier ein int aus der Sitzung — hineingeschmuggelt
         * werden kann nichts. Der Befund zielt auch nicht darauf: Diese vier
         * Stellen waren die EINZIGEN im Projekt, an denen ein Wert im
         * SQL-Text stand. Eine Regel mit vier Ausnahmen ist keine Regel mehr;
         * beim naechsten Umbau ist nicht mehr zu sehen, welche Ausnahme
         * geprueft wurde und welche nur so aussieht. */
        $hd = $pdo->prepare("SELECT COUNT(*) FROM user_defaults WHERE user_id = ? AND kind = 'base'");
        $hd->execute([$userId]);
        $hasDefBase = (bool)$hd->fetchColumn();
        $newDefBaseName = null;
        foreach (($sd['bases'] ?? []) as $b) {
            $name = (string)$b['name'];
            if (stammdaten_dup_global('bases', 'name', $name)) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO bases (user_id, name) VALUES (?,?)');
            $st->execute([$userId, $name]);
            $stats['stammdaten'] += $st->rowCount();
            if (!$hasDefBase && (int)($b['is_default'] ?? 0)) { $newDefBaseName = $name; }
        }
        $ha = $pdo->prepare("SELECT COUNT(*) FROM user_defaults WHERE user_id = ? AND kind = 'aircraft'");
        $ha->execute([$userId]);
        $hasDefAc = (bool)$ha->fetchColumn();
        $newDefAcReg = null;
        foreach (($sd['aircraft'] ?? []) as $a) {
            $reg = (string)$a['registration'];
            if (stammdaten_dup_global('aircraft', 'registration', $reg)) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO aircraft
                (user_id, registration, p1, p2, hems, fr, other) VALUES (?,?,?,?,?,?,?)');
            $st->execute([$userId, $reg,
                (int)($a['p1'] ?? 0), (int)($a['p2'] ?? 0), (int)($a['hems'] ?? 0),
                (int)($a['fr'] ?? 0), (int)($a['other'] ?? 0)]);
            $stats['stammdaten'] += $st->rowCount();
            if (!$hasDefAc && (int)($a['is_default'] ?? 0)) { $newDefAcReg = $reg; }
        }
        // is_default-Flags aus dem Backup in user_defaults schreiben (nur wenn
        // noch kein Default des Typs existiert — bestehende Semantik $hasDefBase/$hasDefAc)
        if ($newDefBaseName !== null) {
            $x = $pdo->prepare('SELECT id FROM bases WHERE user_id = ? AND name = ?');
            $x->execute([$userId, $newDefBaseName]);
            if ($bid = $x->fetchColumn()) {
                $pdo->prepare('INSERT INTO user_defaults (user_id, kind, item_id) VALUES (?,"base",?)
                               ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)')->execute([$userId, $bid]);
            }
        }
        if ($newDefAcReg !== null) {
            $x = $pdo->prepare('SELECT id FROM aircraft WHERE user_id = ? AND registration = ?');
            $x->execute([$userId, $newDefAcReg]);
            if ($aid = $x->fetchColumn()) {
                $pdo->prepare('INSERT INTO user_defaults (user_id, kind, item_id) VALUES (?,"aircraft",?)
                               ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)')->execute([$userId, $aid]);
            }
        }
        foreach (($sd['crew_presets'] ?? []) as $c) {
            if (!in_array($c['role'] ?? '', ['p1','p2','hems','fr','other'], true)) { continue; }
            if (stammdaten_dup_global('crew_presets', 'name', (string)$c['name'], 'role', $c['role'])) {
                $stats['stammdaten_skipped']++; continue;
            }
            $st = $pdo->prepare('INSERT IGNORE INTO crew_presets (user_id, role, name) VALUES (?,?,?)');
            $st->execute([$userId, $c['role'], (string)$c['name']]);
            $stats['stammdaten'] += $st->rowCount();
        }
        foreach (($sd['bw_units'] ?? []) as $w) {
            if (stammdaten_dup_global('bw_units', 'name', (string)$w['name'])) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO bw_units (user_id, name) VALUES (?,?)');
            $st->execute([$userId, (string)$w['name']]);
            $stats['stammdaten'] += $st->rowCount();
        }
        foreach (($sd['resources'] ?? []) as $r) {
            if (stammdaten_dup_global('resources', 'name', (string)$r['name'])) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO resources (user_id, name) VALUES (?,?)');
            $st->execute([$userId, (string)$r['name']]);
            $stats['stammdaten'] += $st->rowCount();
        }
        foreach (($sd['transport_dests'] ?? []) as $t) {
            if (stammdaten_dup_global('transport_dests', 'name', (string)$t['name'])) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO transport_dests (user_id, name) VALUES (?,?)');
            $st->execute([$userId, (string)$t['name']]);
            $stats['stammdaten'] += $st->rowCount();
        }

        /* Flugtage (bestehende Tage bleiben unangetastet) */
        foreach (($data['days'] ?? []) as $d) {
            $tagWert = pruef_kalendertag($d['day'] ?? null, 'days.day', $pruef);
            if ($tagWert === null) { $grund['tag_unbrauchbar']++; continue; }

            $acId = null; $baseId = null;
            if (!empty($d['aircraft_reg'])) {
                $x = $pdo->prepare('SELECT id FROM aircraft WHERE user_id = ? AND registration = ?');
                $x->execute([$userId, $d['aircraft_reg']]);
                $acId = $x->fetchColumn() ?: null;
            }
            if (!empty($d['base_name'])) {
                $x = $pdo->prepare('SELECT id FROM bases WHERE user_id = ? AND name = ?');
                $x->execute([$userId, $d['base_name']]);
                $baseId = $x->fetchColumn() ?: null;
            }
            /* FLUGTAG IM PAPIERKORB: ABLEHNEN UND ZAEHLEN (D1).
             *
             * Ohne diese Pruefung tat INSERT IGNORE hier zwar nichts — der
             * eindeutige Schluessel greift —, aber eben STILL: Der Tag wurde
             * weder eingespielt noch gezaehlt noch erwaehnt. Wer eine
             * Sicherung zurueckspielt und seine Flugtage vermisst, hat
             * keinen Anhaltspunkt. Jetzt wird der Fall benannt. */
            if (isset($tageImPapierkorb[$tagWert])) {
                $grund['tag_im_papierkorb']++;
                continue;
            }
            $st = $pdo->prepare('INSERT IGNORE INTO days
                (user_id, day, aircraft_id, base_id, crew_p1, crew_p2, crew_hems, crew_fr,
                 crew_other, aircraft, base, crew, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$userId, $tagWert, $acId, $baseId,
                $d['crew_p1'] ?? null, $d['crew_p2'] ?? null, $d['crew_hems'] ?? null,
                $d['crew_fr'] ?? null, $d['crew_other'] ?? null,
                $d['aircraft'] ?? null, $d['base'] ?? null, $d['crew'] ?? null,
                $d['notes'] ?? null]);
            $stats['days'] += $st->rowCount();
        }

        /* Einsaetze: Dublette = gleiche client_ref bei dieser NutzerIn */
        $exists = $pdo->prepare('SELECT id FROM missions WHERE user_id = ? AND client_ref = ?');
        $insPoint = $pdo->prepare('INSERT INTO track_points
            (owner_type, owner_id, seq, lat, lon, ele, ts) VALUES (?,?,?,?,?,?,?)');
        $FIELDS = require __DIR__ . '/mission_fields.php';
        require_once __DIR__ . '/site_elevation_lib.php';
        $extraCols = [];
        $collectCols = function (array $fs) use (&$collectCols, &$extraCols) {
            foreach ($fs as $col => $f) {
                // 'resources' hat keine eigene Spalte in missions
                if (($f['type'] ?? '') === 'resources') { continue; }
                $extraCols[] = $col;
                if (!empty($f['children'])) { $collectCols($f['children']); }
            }
        };
        $collectCols($FIELDS);
        $extraCols = array_merge($extraCols, ['pat_blob']);   // Alt-Backups: loc_* wird ignoriert

        foreach (($data['missions'] ?? []) as $m) {
            if (!is_array($m)) { $stats['missions_skipped']++; $grund['aufbau']++; continue; }
            $exists->execute([$userId, (string)($m['client_ref'] ?? '')]);
            if ($exists->fetchColumn()) {
                $stats['missions_skipped']++; $grund['bereits_vorhanden']++; continue;
            }

            /* PRUEFUNG DIESES WEGES — sie fehlte bisher VOLLSTAENDIG.
             *
             * Das ist die auffaelligste Stelle des ganzen Reviews: Von den
             * neun Pruefungen, die der Import durchfuehrt, fand hier keine
             * einzige statt — obwohl die Datei aus BELIEBIGER Herkunft stammen
             * kann, waehrend der Uhr-Weg immerhin einen Schluessel verlangt.
             * Die Pruefsorgfalt verlief genau umgekehrt zur
             * Vertrauenswuerdigkeit der Quelle.
             *
             * Vier Folgen hatte das:
             *  - Phase 10 kehrte ueber diesen Weg in die Datenbank zurueck.
             *    Nach der Migration war das der einzige verbliebene Weg dorthin.
             *  - Reanimationsarten waren ungeprueft.
             *  - Der Patientenblock hatte keine Laengengrenze; die Datenbank
             *    haette ihn abgeschnitten — ein abgeschnittener Chiffretext ist
             *    DAUERHAFT unlesbar.
             *  - Ein einziger ungueltiger Wert brach die GESAMTE
             *    Wiederherstellung ab, statt die eine Zeile zu ueberspringen.
             *    Bei einer Wiederherstellung ist das die falsche Richtung:
             *    Wer sie startet, hat meist keinen zweiten Versuch.
             */
            $tag       = pruef_kalendertag($m['day'] ?? null, 'day', $pruef);
            $startedAt = pruef_utc_oder_sql($m['started_at'] ?? null, 'started_at', $pruef);
            if ($tag === null || $startedAt === null) {
                $stats['missions_skipped']++; $grund['datum_oder_zeit']++; continue;
            }
            $endedAt = pruef_utc_oder_sql($m['ended_at'] ?? null, 'ended_at', $pruef);

            $oe = edbak_origin_edited($m);

            $cols = ['user_id', 'client_ref', 'day', 'started_at', 'ended_at',
                     'manual', 'origin', 'edited', 'final', 'distance_m', 'ascent_m'];
            $vals = [$userId,
                     pruef_text($m['client_ref'] ?? null, 64, 'client_ref', $pruef)
                        ?? ('bak-' . bin2hex(random_bytes(6))),
                     $tag, $startedAt, $endedAt,
                     (int)($m['manual'] ?? 0), $oe['origin'], $oe['edited'], (int)($m['final'] ?? 1),
                     pruef_zahl($m['distance_m'] ?? null, 0, 100000000, 'distance_m', $pruef),
                     pruef_zahl($m['ascent_m'] ?? null, 0, 100000, 'ascent_m', $pruef)];
            foreach ($extraCols as $c) {
                if (!array_key_exists($c, $m)) { continue; }
                // Der Patientenblock bekommt dieselbe Grenze wie ueberall
                // sonst; alles andere wird auf seine Spaltenlaenge gestutzt.
                $wert = $c === 'pat_blob'
                    ? pruef_pat_blob($m[$c], 'pat_blob', $pruef)
                    : $m[$c];
                $cols[] = $c; $vals[] = $wert;
            }
            $sql = 'INSERT INTO missions (' . implode(',', $cols) . ') VALUES ('
                 . implode(',', array_fill(0, count($cols), '?')) . ')';
            $pdo->prepare($sql)->execute($vals);
            $mid = (int)$pdo->lastInsertId();

            $insPh = $pdo->prepare('INSERT INTO mission_phases
                (mission_id, phase, occurred_at, lat, lon) VALUES (?,?,?,?,?)');
            foreach (($m['resources'] ?? []) as $rname) {
                $rname = mb_substr(trim((string)$rname), 0, 120);
                if ($rname !== '') {
                    $pdo->prepare('INSERT INTO mission_resources (mission_id, name) VALUES (?,?)')
                        ->execute([$mid, $rname]);
                }
            }
            // Phasen: Nummer 2 bis 9. Mehrfache Eintraege derselben Nummer
            // bleiben erhalten — sie sind Korrekturen (JSON-Vertrag 3).
            foreach (pruef_menge($m['phases'] ?? [], LIMIT_PHASEN, 'phases', $pruef) as $p) {
                if (!is_array($p)) { continue; }
                $nr   = pruef_phase($p['phase'] ?? null, 'phases.phase', $pruef);
                $wann = pruef_utc_oder_sql($p['occurred_at'] ?? null, 'phases.occurred_at', $pruef);
                if ($nr === null || $wann === null) { continue; }
                $insPh->execute([$mid, $nr, $wann,
                                 pruef_breite($p['lat'] ?? null, 'phases.lat', $pruef),
                                 pruef_laenge($p['lon'] ?? null, 'phases.lon', $pruef)]);
            }
            foreach (pruef_menge($m['resus'] ?? [], LIMIT_REA_SESSION, 'resus', $pruef) as $r) {
                if (!is_array($r)) { continue; }
                $rStart = pruef_utc_oder_sql($r['started_at'] ?? null, 'resus.started_at', $pruef);
                if ($rStart === null) { continue; }
                $pdo->prepare('INSERT INTO resus_sessions (mission_id, started_at) VALUES (?,?)')
                    ->execute([$mid, $rStart]);
                $sid = (int)$pdo->lastInsertId();
                $insEv = $pdo->prepare('INSERT INTO resus_events
                    (session_id, type, occurred_at) VALUES (?,?,?)');
                foreach (pruef_menge($r['events'] ?? [], LIMIT_REA_EREIGN, 'resus.events', $pruef) as $e2) {
                    if (!is_array($e2)) { continue; }
                    $typ  = pruef_reanimationsart($e2['type'] ?? null, 'resus.events.type', $pruef);
                    $wann = pruef_utc_oder_sql($e2['occurred_at'] ?? null, 'resus.events.at', $pruef);
                    if ($typ === null || $wann === null) { continue; }
                    $insEv->execute([$sid, $typ, $wann]);
                }
            }
            foreach (pruef_menge($m['track'] ?? [], LIMIT_TRACKPUNKTE, 'track', $pruef) as $p) {
                if (!is_array($p) || count($p) < 5) { continue; }
                $la = pruef_breite($p[1], 'track.lat', $pruef);
                $lo = pruef_laenge($p[2], 'track.lon', $pruef);
                if ($la === null || $lo === null) { continue; }
                $insPoint->execute(['mission', $mid, (int)$p[0], $la, $lo, $p[3], (int)$p[4]]);
            }

            /* Einsatzort-Hoehe: NACH dem Abschluss, nicht hier (M5-05).
             *
             * Der Aufruf stand an dieser Stelle — INNERHALB der Transaktion,
             * ohne eigenen Fehlerblock, je Einsatz in der Schleife. Ein
             * Fehler darin riss die GESAMTE Wiederherstellung mit sich, und
             * zwar wegen eines Komfortwerts: Die Hoehe ist eine Anzeige, kein
             * Datum, das jemand eingegeben hat.
             *
             * Auf dem Uhr-Weg steht derselbe Aufruf laengst nach dem Commit
             * und in einem eigenen Fehlerblock, mit genau dieser Begruendung
             * (ingest.php). Erschwerend kam hinzu, dass die Eingangsdaten
             * hier am wenigsten geprueft sind — die Datei kann aus beliebiger
             * Herkunft stammen.
             *
             * Die IDs werden gesammelt und unten abgearbeitet.
             */
            $hoeheOffen[] = $mid;

            $stats['missions']++;
        }

        /* Ruhesegmente */
        $rexists = $pdo->prepare('SELECT id FROM rest_segments WHERE user_id = ? AND client_ref = ?');
        foreach (($data['rest_segments'] ?? []) as $r) {
            $rexists->execute([$userId, (string)($r['client_ref'] ?? '')]);
            if ($rexists->fetchColumn()) { $stats['rests_skipped']++; continue; }
            $pdo->prepare('INSERT INTO rest_segments
                (user_id, client_ref, day, started_at, ended_at, final)
                VALUES (?,?,?,?,?,?)')
                ->execute([$userId, $r['client_ref'] ?? ('imp-' . bin2hex(random_bytes(6))),
                           $r['day'], $r['started_at'], $r['ended_at'] ?? null,
                           (int)($r['final'] ?? 1)]);
            $rid = (int)$pdo->lastInsertId();
            foreach (($r['track'] ?? []) as $p) {
                $insPoint->execute(['rest', $rid, (int)$p[0], (float)$p[1],
                                    (float)$p[2], $p[3], (int)$p[4]]);
            }
            $stats['rests']++;
        }

        // Hinweis: Bis Formatversion 1 enthielt das Backup einen Block
        // `pat_module` mit den Schluessel-Huellen des Ursprungskontos, der hier
        // uebernommen wurde. Seit Version 2 liegen die geschuetzten Angaben im
        // (selbst verschluesselten) Container als Klartext und werden vom
        // Browser mit dem Schluessel des ZIELKONTOS verschluesselt — fremde
        // Huellen werden nie mehr geschrieben.

        // Aufschluesselung mitgeben: Der Aufrufer zeigt sie an.
        $stats['skipped_reasons'] = array_filter($grund);
        $stats['rejected'] = $pruef->nachUrsache();

        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }

    /* Einsatzort-Hoehe: nach dem Abschluss, je Einsatz eingefasst (M5-05).
     *
     * Ab hier sind die Daten sicher gespeichert. Ein Fehler kostet nur die
     * Hoehenanzeige des betroffenen Einsatzes; sie laesst sich spaeter
     * nachrechnen (update.php).
     *
     * ANDERS ALS AUF DEM UHR-WEG WIRD ER GEZAEHLT UND GEMELDET. Dort ist das
     * Schweigen richtig — die Uhr kann mit der Auskunft nichts anfangen. Eine
     * Wiederherstellung wertet dagegen ein Mensch aus, der wissen will, was
     * angekommen ist und was nicht.
     */
    $hoeheFehler = 0;
    foreach ($hoeheOffen as $mid) {
        try {
            compute_site_elevation($pdo, $mid);
        } catch (Throwable $ex) {
            $hoeheFehler++;
        }
    }
    if ($hoeheFehler > 0) { $stats['hoehe_fehler'] = $hoeheFehler; }

    return $stats;
}
