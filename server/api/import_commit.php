<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';   // liefert $userId

/**
 * Import: Abgleich mit dem Bestand.
 *
 * POST api/import_commit.php   JSON-Body, Header X-CSRF wie bei api/day.php
 *
 *   { action: 'check',
 *     days: ["2026-05-21", ...] }             // Flugtage der Datei
 *
 * Antwort:
 *   { days: { "2026-05-21": { crew: {p1,p2,hems,fr,other},
 *                             aircraft_id, base_id,
 *                             missions: [{id, hhmm, pat_blob}] } } }
 *
 *   { action: 'commit',
 *     days:     [{day, crew_p1..crew_other, aircraft_id, base_id,
 *                 mode:'insert'|'keep'|'update'}],
 *     missions: [{day, started_local:'HH:MM', transport_dest, winch,
 *                 resources:[], crew_override, crew_p1..crew_other,
 *                 pat_blob, dup:'insert'|'overwrite'|'skip', overwrite_id,
 *
 *                 // ab Web 2.10.0, alle optional (Rueckimport der eigenen
 *                 // Exportformate; die Jahreslisten-Profile senden sie nicht)
 *                 ended_utc, site_desc, site_ele_m, distance_m, ascent_m,
 *                 schockraum, secondary, winch_cycles, winch_cycles_pat,
 *                 winch_airload, bergwacht, bw_unit, bw_info, other_ema, notes,
 *                 phases: [{phase:2..9, at:'...Z'|null, local:'HH:MM'|null,
 *                           lat, lon}],
 *                 rea:    [{started_at:'...Z', events:[{type, at:'...Z'}]}] }] }
 *
 * ZEITEN IN DER NUTZLAST: 'started_local' ist Ortszeit (HH:MM) und bleibt es —
 * der Browser vergleicht damit unmittelbar die Zeiten aus der Datei. Die
 * Phasen- und Reanimationszeiten kommen dagegen als UTC-Zeitstempel, weil die
 * Quelldatei dort einen vollstaendigen Zeitpunkt samt Zonenversatz liefert;
 * eine zweite Umrechnung hier waere eine zusaetzliche Fehlerquelle. Wo eine
 * Phase nur als Ortszeit vorliegt (Standard-Excel kennt nur HH:MM), traegt sie
 * 'local' statt 'at' und wird hier wie 'started_local' umgerechnet.
 *
 * Antwort:
 *   { ok, days_inserted, days_updated, missions_inserted,
 *     missions_overwritten, missions_skipped, first_day }
 *
 * WARUM SO WENIG: Die Anfrage enthaelt ausschliesslich Datum und Uhrzeit.
 * Name, Geburtsdatum, Diagnose, Einsatzort und seit Web 2.9.0 auch die
 * Einsatznummer bleiben im Browser — der Server kann und soll nicht wissen,
 * um welche Personen es geht. Fuer die Duplikaterkennung ueber die Nummer
 * liefert 'check' deshalb je vorhandenem Einsatz den pat_blob mit; der
 * Browser entschluesselt ihn lokal und vergleicht dort (siehe
 * assets/import_ui.js, bestandEinsatznummernIndex). Erkannt werden
 * Nummerndubletten dadurch nur noch innerhalb der Flugtage, die in der
 * Importdatei vorkommen — das ist der Preis der Verschluesselung
 * (docs/Technik.md).
 *
 * Die Uhrzeiten gehen als ORTSZEIT (HH:MM) zurueck, nicht als UTC-Zeitstempel.
 * Der Browser vergleicht sie unmittelbar mit den Zeiten aus der Datei, die
 * ebenfalls Ortszeit sind; eine Umrechnung auf beiden Seiten waere eine
 * zusaetzliche Fehlerquelle.
 *
 * Geloeschte Eintraege (Papierkorb) gelten bewusst als nicht vorhanden: Ein
 * Import soll nicht an etwas scheitern, das die NutzerIn weggeworfen hat.
 */

/**
 * Uebernahme in einer einzigen Transaktion: entweder alles oder nichts.
 * Ein halb eingespielter Jahresbestand waere von Hand kaum zu bereinigen.
 */
function import_commit(array $b, int $userId): never
{
    $tage      = is_array($b['days'] ?? null) ? $b['days'] : [];
    $einsaetze = is_array($b['missions'] ?? null) ? $b['missions'] : [];
    if (count($tage) > 600 || count($einsaetze) > 3000) {
        json_out(['error' => 'zu_gross',
                  'meldung' => 'Zu viele Zeilen auf einmal. Bitte in Jahresabschnitten importieren.'], 413);
    }

    $txt = function ($v, int $max): ?string {
        $s = mb_substr(trim((string)$v), 0, $max);
        return $s === '' ? null : $s;
    };

    /** Ganzzahl im erlaubten Bereich, sonst NULL (statt einer 0, die eine
     *  echte Messung vortaeuschen wuerde). */
    $zahl = function ($v, int $min, int $max): ?int {
        if ($v === null || $v === '' || !is_numeric($v)) { return null; }
        $n = (int)$v;
        return ($n < $min || $n > $max) ? null : $n;
    };

    $flag = static fn ($v): int => !empty($v) ? 1 : 0;

    /** "2026-03-14T09:50:00Z" -> "2026-03-14 09:50:00" (UTC, wie gespeichert). */
    $utc = function ($v): ?string {
        if (!is_string($v) || $v === '') { return null; }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?Z$/', $v)) { return null; }
        try {
            return (new DateTime($v, new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    };

    $pdo = db();
    $pdo->beginTransaction();
    try {
        /* ---- Stammdaten-IDs pruefen (nur eigene oder zentrale) ------------ */
        $checkId = function (?int $id, string $table) use ($userId, $pdo): ?int {
            if ($id === null || $id <= 0) { return null; }
            $q = $pdo->prepare("SELECT id FROM `$table`
                                WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
            $q->execute([$id, $userId]);
            return $q->fetchColumn() !== false ? $id : null;
        };

        /* ---- Flugtage ----------------------------------------------------- */
        $tageNeu = 0; $tageGeaendert = 0;

        // 'insert': Tag anlegen. Existiert er wider Erwarten doch (zwischen
        // Pruefung und Uebernahme angelegt, oder im Papierkorb), werden nur
        // LEERE Felder gefuellt und der Tag aus dem Papierkorb geholt —
        // vorhandene Angaben werden nie ueberschrieben.
        $insTag = $pdo->prepare(
            'INSERT INTO days (user_id, day, aircraft_id, base_id,
                               crew_p1, crew_p2, crew_hems, crew_fr, crew_other)
             VALUES (?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
               deleted_at  = NULL,
               aircraft_id = COALESCE(aircraft_id, VALUES(aircraft_id)),
               base_id     = COALESCE(base_id,     VALUES(base_id)),
               crew_p1     = COALESCE(crew_p1,     VALUES(crew_p1)),
               crew_p2     = COALESCE(crew_p2,     VALUES(crew_p2)),
               crew_hems   = COALESCE(crew_hems,   VALUES(crew_hems)),
               crew_fr     = COALESCE(crew_fr,     VALUES(crew_fr)),
               crew_other  = COALESCE(crew_other,  VALUES(crew_other))');

        // 'update': ausdruecklicher Wunsch, die Besatzung aus der Datei zu
        // uebernehmen — hier wird ueberschrieben, aber nur wo die Datei etwas
        // liefert (COALESCE andersherum).
        $updTag = $pdo->prepare(
            'UPDATE days SET deleted_at = NULL,
               crew_p1    = COALESCE(?, crew_p1),
               crew_p2    = COALESCE(?, crew_p2),
               crew_hems  = COALESCE(?, crew_hems),
               crew_fr    = COALESCE(?, crew_fr),
               crew_other = COALESCE(?, crew_other)
             WHERE user_id = ? AND day = ?');

        $bekannteTage = [];
        foreach ($tage as $t) {
            $tag = (string)($t['day'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tag)) { continue; }
            $bekannteTage[$tag] = true;
            $modus = (string)($t['mode'] ?? 'keep');
            $crew = [
                $txt($t['crew_p1'] ?? null, 120), $txt($t['crew_p2'] ?? null, 120),
                $txt($t['crew_hems'] ?? null, 120), $txt($t['crew_fr'] ?? null, 120),
                $txt($t['crew_other'] ?? null, 120),
            ];
            if ($modus === 'insert') {
                $insTag->execute(array_merge(
                    [$userId, $tag,
                     $checkId(isset($t['aircraft_id']) ? (int)$t['aircraft_id'] : null, 'aircraft'),
                     $checkId(isset($t['base_id']) ? (int)$t['base_id'] : null, 'bases')],
                    $crew));
                $tageNeu++;
            } elseif ($modus === 'update') {
                $updTag->execute(array_merge($crew, [$userId, $tag]));
                $tageGeaendert++;
            }
            // 'keep' = bewusst nichts tun
        }

        /* ---- Virtuelles Geraet "Manuelle Einträge" ------------------------- */
        // Importierte Einsaetze zaehlen wie von Hand angelegte: Sie haengen am
        // selben virtuellen Geraet, damit die Uhr sie nie ueberschreibt und sie
        // in der Geraeteliste nicht auftauchen (Filter 'manual-%').
        $devKey = 'manual-' . $userId;
        $q = $pdo->prepare('SELECT id FROM devices WHERE device_id = ?');
        $q->execute([$devKey]);
        $devId = $q->fetchColumn();
        if ($devId === false) {
            $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label, active)
                           VALUES (?,?,?,?,0)')
                ->execute([$userId, $devKey,
                           password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
                           'Manuelle Einträge']);
            $devId = (int)$pdo->lastInsertId();
        }
        $devId = (int)$devId;

        /* ---- Einsaetze ---------------------------------------------------- */
        // Die zusaetzlichen Felder ab Web 2.10.0 haengen hinten an. Profile,
        // die sie nicht liefern, schreiben dort NULL beziehungsweise 0 — das
        // entspricht dem Zustand vor dieser Version.
        $insE = $pdo->prepare(
            'INSERT INTO missions (user_id, device_id, client_ref, day, started_at, ended_at,
                                   final, manual, transport_dest, winch,
                                   crew_override, crew_p1, crew_p2, crew_hems, crew_fr,
                                   crew_other, pat_blob,
                                   site_desc, site_ele_m, distance_m, ascent_m,
                                   schockraum, secondary, winch_cycles, winch_cycles_pat,
                                   winch_airload, bergwacht, bw_unit, bw_info,
                                   other_ema, notes)
             VALUES (?,?,?,?,?,?,1,1,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $updE = $pdo->prepare(
            'UPDATE missions SET day = ?, started_at = ?, ended_at = ?,
                                 transport_dest = ?, winch = ?, crew_override = ?,
                                 crew_p1 = ?, crew_p2 = ?, crew_hems = ?, crew_fr = ?,
                                 crew_other = ?, pat_blob = ?,
                                 site_desc = ?, site_ele_m = ?, distance_m = ?, ascent_m = ?,
                                 schockraum = ?, secondary = ?, winch_cycles = ?,
                                 winch_cycles_pat = ?, winch_airload = ?, bergwacht = ?,
                                 bw_unit = ?, bw_info = ?, other_ema = ?, notes = ?,
                                 manual = 1
             WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
        $insPhase = $pdo->prepare(
            'INSERT INTO mission_phases (mission_id, phase, occurred_at, lat, lon)
             VALUES (?,?,?,?,?)');
        $hatPhase2 = $pdo->prepare(
            'SELECT id FROM mission_phases WHERE mission_id = ? AND phase = 2 LIMIT 1');
        $delPhasen = $pdo->prepare('DELETE FROM mission_phases WHERE mission_id = ?');
        $delRes = $pdo->prepare('DELETE FROM mission_resources WHERE mission_id = ?');
        $insRes = $pdo->prepare('INSERT INTO mission_resources (mission_id, name) VALUES (?, ?)');
        // resus_events haengt per FOREIGN KEY ... ON DELETE CASCADE an
        // resus_sessions — das Loeschen der Sitzungen raeumt die Ereignisse mit.
        $delRea = $pdo->prepare('DELETE FROM resus_sessions WHERE mission_id = ?');
        $insReaS = $pdo->prepare(
            'INSERT INTO resus_sessions (mission_id, started_at) VALUES (?, ?)');
        $insReaE = $pdo->prepare(
            'INSERT INTO resus_events (session_id, type, occurred_at) VALUES (?,?,?)');

        $neu = 0; $ersetzt = 0; $uebersprungen = 0; $ersterTag = null;

        foreach ($einsaetze as $m) {
            $tag = (string)($m['day'] ?? '');
            $hhmm = (string)($m['started_local'] ?? '');
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tag)
                || !preg_match('/^\d{2}:\d{2}$/', $hhmm)) {
                $uebersprungen++; continue;
            }
            if (($m['dup'] ?? 'insert') === 'skip') { $uebersprungen++; continue; }

            $startedAt = local_to_utc($tag, $hhmm);
            if ($startedAt === null) { $uebersprungen++; continue; }

            // Chiffretext nur formal pruefen — der Inhalt geht den Server
            // nichts an. Was hier nicht nach Base64 aussieht, waere entweder
            // ein Fehler oder Klartext; beides wird nicht gespeichert.
            $blob = $m['pat_blob'] ?? null;
            if ($blob !== null) {
                $blob = (string)$blob;
                if (!preg_match('#^[A-Za-z0-9+/=]{20,60000}$#', $blob)) { $blob = null; }
            }

            // Endzeit: Liefert die Datei eine, wird sie uebernommen; sonst
            // bleibt es beim bisherigen Verhalten (Ende = Beginn), damit sich
            // fuer die Jahreslisten-Profile nichts aendert.
            $endedAt = $utc($m['ended_utc'] ?? null) ?? $startedAt;

            $werte = [
                $txt($m['transport_dest'] ?? null, 190),
                $flag($m['winch'] ?? null),
                $flag($m['crew_override'] ?? null),
                $txt($m['crew_p1'] ?? null, 120), $txt($m['crew_p2'] ?? null, 120),
                $txt($m['crew_hems'] ?? null, 120), $txt($m['crew_fr'] ?? null, 120),
                $txt($m['crew_other'] ?? null, 120),
                $blob,
                $txt($m['site_desc'] ?? null, 190),
                $zahl($m['site_ele_m'] ?? null, -500, 9000),
                $zahl($m['distance_m'] ?? null, 0, 100000000),
                $zahl($m['ascent_m'] ?? null, 0, 1000000),
                $flag($m['schockraum'] ?? null),
                $flag($m['secondary'] ?? null),
                $zahl($m['winch_cycles'] ?? null, 0, 127),
                $zahl($m['winch_cycles_pat'] ?? null, 0, 127),
                $flag($m['winch_airload'] ?? null),
                $flag($m['bergwacht'] ?? null),
                $txt($m['bw_unit'] ?? null, 120),
                $txt($m['bw_info'] ?? null, 190),
                $txt($m['other_ema'] ?? null, 190),
                $txt($m['notes'] ?? null, 2000),
            ];

            $id = null;
            if (($m['dup'] ?? '') === 'overwrite' && !empty($m['overwrite_id'])) {
                $id = (int)$m['overwrite_id'];
                $updE->execute(array_merge([$tag, $startedAt, $endedAt], $werte, [$id, $userId]));
                if ($updE->rowCount() === 0) {
                    // Gehoert jemand anderem oder ist inzwischen geloescht.
                    $uebersprungen++; continue;
                }
                $ersetzt++;
            } else {
                $insE->execute(array_merge(
                    [$userId, $devId, 'imp-' . bin2hex(random_bytes(12)),
                     $tag, $startedAt, $endedAt],
                    $werte));
                $id = (int)$pdo->lastInsertId();
                $neu++;
            }

            /* ---- Phasen ---------------------------------------------------
             * Liefert die Datei Phasen (Rueckimport der eigenen Exporte), wird
             * der komplette Satz ersetzt — ein Mischen aus alt und neu waere
             * nicht nachvollziehbar. Liefert sie keine (Jahreslisten), bleibt
             * es beim bisherigen Verhalten: Phase 2 anlegen, falls sie fehlt.
             *
             * Ohne wenigstens eine Phasenzeile laesst sich der Einsatz spaeter
             * nicht im Formular oeffnen — es rekonstruiert Beginn und Ende aus
             * den Phasen. Phase 2 = Alarmierung.
             */
            $phasen = is_array($m['phases'] ?? null) ? $m['phases'] : [];
            $gesetzt = [];
            if ($phasen) {
                $delPhasen->execute([$id]);
                foreach ($phasen as $p) {
                    $nr = (int)($p['phase'] ?? 0);
                    // Phase 10 wurde mit 2026_07_19_phase10_entfernen abgeschafft.
                    if ($nr < 2 || $nr > 9 || isset($gesetzt[$nr])) { continue; }
                    $wann = $utc($p['at'] ?? null);
                    if ($wann === null && !empty($p['local'])
                        && preg_match('/^\d{2}:\d{2}$/', (string)$p['local'])) {
                        $wann = local_to_utc($tag, (string)$p['local']);
                    }
                    if ($wann === null) { continue; }
                    $lat = isset($p['lat']) && is_numeric($p['lat']) ? (float)$p['lat'] : null;
                    $lon = isset($p['lon']) && is_numeric($p['lon']) ? (float)$p['lon'] : null;
                    if ($lat !== null && ($lat < -90 || $lat > 90)) { $lat = null; }
                    if ($lon !== null && ($lon < -180 || $lon > 180)) { $lon = null; }
                    $insPhase->execute([$id, $nr, $wann, $lat, $lon]);
                    $gesetzt[$nr] = true;
                }
            }
            if (!isset($gesetzt[2])) {
                $hatPhase2->execute([$id]);
                if ($hatPhase2->fetchColumn() === false) {
                    $insPhase->execute([$id, 2, $startedAt, null, null]);
                }
            }

            /* ---- Reanimation ----------------------------------------------
             * Ebenfalls ersetzend, und nur wenn die Datei etwas liefert. Ein
             * Einsatz ohne 'rea' in der Nutzlast behaelt seine vorhandene
             * Dokumentation — ein Import mit einem Format, das Reanimationen
             * gar nicht kennt, darf sie nicht loeschen.
             */
            if (is_array($m['rea'] ?? null)) {
                $delRea->execute([$id]);
                $anzS = 0;
                foreach ($m['rea'] as $s) {
                    if (++$anzS > 20) { break; }
                    $beginn = $utc($s['started_at'] ?? null);
                    if ($beginn === null) { continue; }
                    $insReaS->execute([$id, $beginn]);
                    $sid = (int)$pdo->lastInsertId();
                    $anzE = 0;
                    foreach ((is_array($s['events'] ?? null) ? $s['events'] : []) as $e) {
                        if (++$anzE > 200) { break; }
                        $typ = mb_substr(trim((string)($e['type'] ?? '')), 0, 24);
                        // Nur bekannte Schluessel — ein freier Text hier waere
                        // im Formular spaeter nicht darstellbar.
                        if ($typ === '' || !array_key_exists($typ, RESUS_LABELS)) { continue; }
                        $wann = $utc($e['at'] ?? null);
                        if ($wann === null) { continue; }
                        $insReaE->execute([$sid, $typ, $wann]);
                    }
                }
            }

            // Weitere Rettungsmittel als eigene Zeilen (einzeln entfernbar),
            // doppelte und leere verworfen — gleiche Regel wie im Formular.
            $rm = is_array($m['resources'] ?? null) ? $m['resources'] : [];
            $sauber = [];
            foreach ($rm as $name) {
                $name = mb_substr(trim((string)$name), 0, 120);
                if ($name !== '' && !in_array($name, $sauber, true)) { $sauber[] = $name; }
                if (count($sauber) >= 40) { break; }
            }
            $delRes->execute([$id]);
            foreach ($sauber as $name) { $insRes->execute([$id, $name]); }

            if ($ersterTag === null || $tag < $ersterTag) { $ersterTag = $tag; }
        }

        $pdo->commit();
        json_out([
            'ok' => true,
            'days_inserted'         => $tageNeu,
            'days_updated'          => $tageGeaendert,
            'missions_inserted'     => $neu,
            'missions_overwritten'  => $ersetzt,
            'missions_skipped'      => $uebersprungen,
            'first_day'             => $ersterTag,
        ]);
    } catch (Throwable $ex) {
        $pdo->rollBack();
        json_out(['error' => 'commit', 'meldung' => $ex->getMessage()], 500);
    }
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
    if ($action === 'commit') {
        import_commit($b, $userId);
    }
    if ($action !== 'check') {
        json_out(['error' => 'action'], 400);
    }

    /* ---- Eingaben saeubern ------------------------------------------------ */
    $tage = [];
    foreach ((array)($b['days'] ?? []) as $d) {
        $d = (string)$d;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) { $tage[$d] = true; }
        if (count($tage) >= 500) { break; }
    }
    $tage = array_keys($tage);

    $antwortTage = [];

    /* ---- Flugtage samt vorhandener Einsaetze ------------------------------ */
    if ($tage) {
        $platz = implode(',', array_fill(0, count($tage), '?'));

        // Datentrennung! Zusaetzlich nur nicht geloeschte Tage.
        $st = db()->prepare("SELECT day, aircraft_id, base_id,
                                    crew_p1, crew_p2, crew_hems, crew_fr, crew_other
                             FROM days
                             WHERE user_id = ? AND deleted_at IS NULL
                               AND day IN ($platz)");
        $st->execute(array_merge([$userId], $tage));
        foreach ($st->fetchAll() as $t) {
            $antwortTage[(string)$t['day']] = [
                'crew' => [
                    'p1'    => $t['crew_p1'],
                    'p2'    => $t['crew_p2'],
                    'hems'  => $t['crew_hems'],
                    'fr'    => $t['crew_fr'],
                    'other' => $t['crew_other'],
                ],
                'aircraft_id' => $t['aircraft_id'] !== null ? (int)$t['aircraft_id'] : null,
                'base_id'     => $t['base_id'] !== null ? (int)$t['base_id'] : null,
                'missions'    => [],
            ];
        }

        // Einsaetze dieser Tage — auch wenn der Flugtag selbst fehlt (moeglich,
        // wenn ein Einsatz ohne angelegten Tag existiert). pat_blob geht als
        // Chiffretext mit, damit der Browser die Einsatznummer fuer den
        // Dublettenabgleich lokal entschluesseln kann (siehe Kopfkommentar).
        $st = db()->prepare("SELECT id, day, started_at, pat_blob
                             FROM missions
                             WHERE user_id = ? AND deleted_at IS NULL
                               AND day IN ($platz)
                             ORDER BY started_at");
        $st->execute(array_merge([$userId], $tage));
        foreach ($st->fetchAll() as $m) {
            $tag = (string)$m['day'];
            if (!isset($antwortTage[$tag])) {
                $antwortTage[$tag] = ['crew' => null, 'aircraft_id' => null,
                                      'base_id' => null, 'missions' => []];
            }
            $antwortTage[$tag]['missions'][] = [
                'id'       => (int)$m['id'],
                'hhmm'     => fmt_local($m['started_at']),
                'pat_blob' => !empty($m['pat_blob']) ? (string)$m['pat_blob'] : null,
            ];
        }
    }

    json_out(['days' => $antwortTage]);
} catch (Throwable $ex) {
    // Lesbare Meldung statt leerem HTTP 500 — die Seite zeigt sie an.
    json_out(['error' => 'check', 'meldung' => $ex->getMessage()], 500);
}
