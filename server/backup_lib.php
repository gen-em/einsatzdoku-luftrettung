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

    $u = $q('SELECT email, name FROM users WHERE id = ?', [$userId])[0];

    $tracks = function (string $type, int $id) use ($q): array {
        return array_map(
            fn($p) => [(int)$p['seq'], (float)$p['lat'], (float)$p['lon'],
                       $p['ele'] !== null ? (float)$p['ele'] : null, (int)$p['ts']],
            $q('SELECT seq, lat, lon, ele, ts FROM track_points
                WHERE owner_type = ? AND owner_id = ? ORDER BY seq', [$type, $id]));
    };

    $missions = [];
    foreach ($q('SELECT * FROM missions WHERE user_id = ? AND deleted_at IS NULL ORDER BY started_at', [$userId]) as $m) {
        $mid = (int)$m['id'];
        foreach (['id', 'user_id', 'device_id'] as $drop) { unset($m[$drop]); }
        $m['phases'] = array_map(
            fn($p) => ['phase' => (int)$p['phase'], 'occurred_at' => $p['occurred_at'],
                       'lat' => $p['lat'] !== null ? (float)$p['lat'] : null,
                       'lon' => $p['lon'] !== null ? (float)$p['lon'] : null],
            $q('SELECT phase, occurred_at, lat, lon FROM mission_phases
                WHERE mission_id = ? ORDER BY occurred_at', [$mid]));
        $m['resources'] = $q('SELECT name FROM mission_resources
                              WHERE mission_id = ? ORDER BY id', [$mid]);
        $m['resources'] = array_column($m['resources'], 'name');
        $m['resus'] = [];
        foreach ($q('SELECT id, started_at FROM resus_sessions
                     WHERE mission_id = ? ORDER BY started_at', [$mid]) as $s) {
            $m['resus'][] = [
                'started_at' => $s['started_at'],
                'events' => $q('SELECT type, occurred_at FROM resus_events
                                WHERE session_id = ? ORDER BY occurred_at', [(int)$s['id']]),
            ];
        }
        $m['track'] = $tracks('mission', $mid);
        $missions[] = $m;
    }

    $rests = [];
    foreach ($q('SELECT * FROM rest_segments WHERE user_id = ? AND deleted_at IS NULL ORDER BY started_at', [$userId]) as $r) {
        $rid = (int)$r['id'];
        foreach (['id', 'user_id', 'device_id'] as $drop) { unset($r[$drop]); }
        $r['track'] = $tracks('rest', $rid);
        $rests[] = $r;
    }

    // Flugtage: Verweise fuer Portabilitaet in Namen aufloesen
    $days = [];
    foreach ($q('SELECT d.*, a.registration AS aircraft_reg, b.name AS base_name
                 FROM days d
                 LEFT JOIN aircraft a ON a.id = d.aircraft_id
                 LEFT JOIN bases b ON b.id = d.base_id
                 WHERE d.user_id = ? AND d.deleted_at IS NULL ORDER BY d.day', [$userId]) as $d) {
        foreach (['id', 'user_id', 'aircraft_id', 'base_id'] as $drop) { unset($d[$drop]); }
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
        'version' => 3,
        'created_at' => gmdate('c'),
        'app' => 'einsatzdoku-luftrettung',
        'user' => ['email' => $u['email'], 'name' => $u['name']],
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

/** @return array Zusammenfassung (Zaehler) */
function edbak_restore(int $userId, array $data): array {
    $pdo = db();
    $stats = ['missions' => 0, 'missions_skipped' => 0, 'rests' => 0, 'rests_skipped' => 0,
              'days' => 0, 'stammdaten' => 0, 'stammdaten_skipped' => 0];

    $pdo->beginTransaction();
    try {
        /* Stammdaten (INSERT IGNORE ueber die Unique-Schluessel; zentral
         * vorhandene Eintraege werden uebersprungen und gezaehlt, s. 6.3/8) */
        $sd = $data['stammdaten'] ?? [];
        $hasDefBase = (bool)$pdo->query("SELECT COUNT(*) FROM user_defaults WHERE user_id = $userId AND kind = 'base'")->fetchColumn();
        $newDefBaseName = null;
        foreach (($sd['bases'] ?? []) as $b) {
            $name = (string)$b['name'];
            if (stammdaten_dup_global('bases', 'name', $name)) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO bases (user_id, name) VALUES (?,?)');
            $st->execute([$userId, $name]);
            $stats['stammdaten'] += $st->rowCount();
            if (!$hasDefBase && (int)($b['is_default'] ?? 0)) { $newDefBaseName = $name; }
        }
        $hasDefAc = (bool)$pdo->query("SELECT COUNT(*) FROM user_defaults WHERE user_id = $userId AND kind = 'aircraft'")->fetchColumn();
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
            $st = $pdo->prepare('INSERT IGNORE INTO days
                (user_id, day, aircraft_id, base_id, crew_p1, crew_p2, crew_hems, crew_fr,
                 crew_other, aircraft, base, crew, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$userId, $d['day'], $acId, $baseId,
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
            $exists->execute([$userId, (string)($m['client_ref'] ?? '')]);
            if ($exists->fetchColumn()) { $stats['missions_skipped']++; continue; }

            $cols = ['user_id', 'client_ref', 'day', 'started_at', 'ended_at',
                     'manual', 'final', 'distance_m', 'ascent_m'];
            $vals = [$userId, $m['client_ref'] ?? ('imp-' . bin2hex(random_bytes(6))),
                     $m['day'], $m['started_at'], $m['ended_at'] ?? null,
                     (int)($m['manual'] ?? 0), (int)($m['final'] ?? 1),
                     $m['distance_m'] ?? null, $m['ascent_m'] ?? null];
            foreach ($extraCols as $c) {
                if (array_key_exists($c, $m)) { $cols[] = $c; $vals[] = $m[$c]; }
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
            foreach (($m['phases'] ?? []) as $p) {
                $insPh->execute([$mid, (int)$p['phase'], $p['occurred_at'],
                                 $p['lat'] ?? null, $p['lon'] ?? null]);
            }
            foreach (($m['resus'] ?? []) as $r) {
                $pdo->prepare('INSERT INTO resus_sessions (mission_id, started_at) VALUES (?,?)')
                    ->execute([$mid, $r['started_at']]);
                $sid = (int)$pdo->lastInsertId();
                $insEv = $pdo->prepare('INSERT INTO resus_events
                    (session_id, type, occurred_at) VALUES (?,?,?)');
                foreach (($r['events'] ?? []) as $e2) {
                    $insEv->execute([$sid, (string)$e2['type'], $e2['occurred_at']]);
                }
            }
            foreach (($m['track'] ?? []) as $p) {
                $insPoint->execute(['mission', $mid, (int)$p[0], (float)$p[1],
                                    (float)$p[2], $p[3], (int)$p[4]]);
            }

            // Einsatzort-Hoehe aus den soeben eingespielten Phasen/Track neu
            // berechnen, statt einen exportierten Wert zu uebernehmen — eine
            // einzige Implementierung, siehe site_elevation_lib.php.
            compute_site_elevation($pdo, $mid);

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

        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    return $stats;
}
