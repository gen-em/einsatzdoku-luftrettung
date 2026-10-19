<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/spur_lib.php';
require_once __DIR__ . '/einsatz_lib.php';

/**
 * ortsumzug_lib.php — das Einmalwerkzeug für den Altbestand (S11-10; Konzept
 * S11 3.8 Schritt 5, E-S11-19).
 *
 * WAS ES TUT. Bis Web 22.0.0 lagen Spur, Koordinate jeder Phase,
 * Reanimation, Transportziel und Ortshöhe im Klartext. Seit S11 liest die
 * Anwendung sie nur noch verschlossen (Gerätehülle, `pat_blob`), und ein
 * Einsatz des Altbestands zeigt bis zum Umzug keine Karte (E-S11-36, -42).
 * Verschließen kann nur der Browser des Kontos — der Server kennt weder den
 * Inhaltsschlüssel noch den privaten Teil des Kontopaars. Dieses Werkzeug
 * ist die Serverhälfte: Es nennt, was im Klartext liegt, und tauscht es gegen
 * das Verschlossene, in EINER Transaktion je Datensatz und nur, wenn sich
 * seit dem Lesen nichts geändert hat.
 *
 * WAS ZUM ALTBESTAND ZÄHLT (die Liste aus Konzept 1, „Was verschlüsselt wird"):
 *   - `mission_phases.lat/lon`, die nicht NULL sind
 *   - jede Zeile in `resus_sessions` (die Ereignisse hängen daran)
 *   - `missions.transport_dest`, `dest_lat`, `dest_lon`, `site_ele_m`
 *   - Spurpunkte in `track_points` und `track_blobs` (SPUR1), für Einsätze
 *     und Ruhesegmente
 * Auch Einsätze im Papierkorb: Wer einen wiederherstellt, bekäme sonst den
 * Klartext zurück (wie `api/pat_anheben.php`).
 *
 * WAS ES NICHT ANFASST: `uhr_gesperrt` und `edited` (ein Umzug ist keine
 * Bearbeitung, dieselbe Regel wie beim Anheben der Notizen), und eine Spur,
 * deren Klartext und Stücke sich in den Punktnummern überschneiden — zwei
 * Stände derselben Punkte führt es nicht zusammen; sie bleibt liegen und
 * wird gezählt. Trägt ein Einsatz schon eine Einsatzhülle und daneben
 * Klartext-Orte, führt der Browser zusammen wie das Formular (E-S11-45 (4)):
 * Was die Hülle hat, gewinnt.
 */

/** Höchstens so viele Datensätze je Liste — der Browser holt in Runden. */
const OU_LISTE_MAX = 10;
/** Höchstens so viele Spurpunkte je Liste: ein Altbestand mit langen Spuren
 *  soll keine Antwort von vielen Megabyte bauen. Eine einzelne Spur geht
 *  immer mit, auch wenn sie allein darüber liegt. */
const OU_PUNKTE_MAX = 100000;

/** Die Bedingung „Einsatz hat Altbestand", als SQL für `missions m`. */
function ou_einsatz_bedingung(): string
{
    return "(EXISTS (SELECT 1 FROM mission_phases p WHERE p.mission_id = m.id
                      AND (p.lat IS NOT NULL OR p.lon IS NOT NULL))
          OR EXISTS (SELECT 1 FROM resus_sessions r WHERE r.mission_id = m.id)
          OR m.transport_dest IS NOT NULL OR m.dest_lat IS NOT NULL
          OR m.dest_lon IS NOT NULL OR m.site_ele_m IS NOT NULL
          OR EXISTS (SELECT 1 FROM track_points t WHERE t.owner_type = 'mission' AND t.owner_id = m.id)
          OR EXISTS (SELECT 1 FROM track_blobs b WHERE b.owner_type = 'mission' AND b.owner_id = m.id))";
}

function ou_ruhe_bedingung(): string
{
    return "(EXISTS (SELECT 1 FROM track_points t WHERE t.owner_type = 'rest' AND t.owner_id = r.id)
          OR EXISTS (SELECT 1 FROM track_blobs b WHERE b.owner_type = 'rest' AND b.owner_id = r.id))";
}

/**
 * Was dieses Konto noch im Klartext hat.
 *
 * @param list<array{0:string,1:int}> $auslassen was der Browser in dieser
 *        Sitzung nicht verschließen konnte (Konflikt, Blob unlesbar)
 * @return list<array{0:string,1:int}>
 */
function ou_kandidaten(PDO $pdo, int $userId, ?int $max = OU_LISTE_MAX, array $auslassen = []): array
{
    $aus = [];
    foreach ($auslassen as $a) {
        if (is_array($a) && in_array($a[0] ?? '', ['mission', 'rest'], true)) {
            $aus[$a[0] . ':' . (int)($a[1] ?? 0)] = true;
        }
    }
    $liste = [];
    $q = $pdo->prepare('SELECT m.id FROM missions m WHERE m.user_id = ? AND ' . ou_einsatz_bedingung()
                       . ' ORDER BY m.id');
    $q->execute([$userId]);
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $id) {
        if (!isset($aus['mission:' . $id])) { $liste[] = ['mission', (int)$id]; }
    }
    $q = $pdo->prepare('SELECT r.id FROM rest_segments r WHERE r.user_id = ? AND ' . ou_ruhe_bedingung()
                       . ' ORDER BY r.id');
    $q->execute([$userId]);
    foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $id) {
        if (!isset($aus['rest:' . $id])) { $liste[] = ['rest', (int)$id]; }
    }
    return $max === null ? $liste : array_slice($liste, 0, $max);
}

/** Wie viele Datensätze dieses Konto noch im Klartext hat. */
function ou_zahl(PDO $pdo, int $userId, array $auslassen = []): array
{
    $alle = ou_kandidaten($pdo, $userId, null, $auslassen);
    $n = ['einsaetze' => 0, 'ruhe' => 0];
    foreach ($alle as [$art]) { $n[$art === 'mission' ? 'einsaetze' : 'ruhe']++; }
    return $n;
}

/**
 * Der Altbestand der ganzen Anlage, gezählt JE SPALTE UND TABELLE — die Zahl
 * vor und nach dem Lauf (Abnahme S11-10) und später die Sperre der
 * Aufräummigration (E-S11-19). Keine Stichprobe: jede Zeile zählt.
 *
 * @return array<string,int>
 */
function ou_zaehlung(PDO $pdo): array
{
    $z = static fn(string $sql): int => (int)$pdo->query($sql)->fetchColumn();
    return [
        'mission_phases.lat/lon'   => $z('SELECT COUNT(*) FROM mission_phases WHERE lat IS NOT NULL OR lon IS NOT NULL'),
        'resus_sessions'           => $z('SELECT COUNT(*) FROM resus_sessions'),
        'resus_events'             => $z('SELECT COUNT(*) FROM resus_events'),
        'missions.transport_dest'  => $z('SELECT COUNT(*) FROM missions WHERE transport_dest IS NOT NULL'),
        'missions.dest_lat/lon'    => $z('SELECT COUNT(*) FROM missions WHERE dest_lat IS NOT NULL OR dest_lon IS NOT NULL'),
        'missions.site_ele_m'      => $z('SELECT COUNT(*) FROM missions WHERE site_ele_m IS NOT NULL'),
        'track_points'             => $z('SELECT COUNT(*) FROM track_points'),
        'track_blobs'              => $z('SELECT COUNT(*) FROM track_blobs'),
    ];
}

/**
 * Wie viele Konten noch Altbestand haben (für die Statuszeile). Das
 * Demo-Konto zählt mit: Auch sein Klartext sperrt die Aufräummigration.
 */
function ou_konten(PDO $pdo): int
{
    return (int)$pdo->query('SELECT COUNT(*) FROM users u WHERE EXISTS (SELECT 1 FROM missions m
                              WHERE m.user_id = u.id AND ' . ou_einsatz_bedingung() . ')
                              OR EXISTS (SELECT 1 FROM rest_segments r WHERE r.user_id = u.id AND '
                              . ou_ruhe_bedingung() . ')')->fetchColumn();
}

/** Ein Fingerabdruck des Klartextbestands einer Spur — der Wächter beim Tausch. */
function ou_spur_stand(PDO $pdo, string $art, int $id): string
{
    $u = spur_umriss($pdo, $art, [$id])[$id] ?? [];
    $q = $pdo->prepare('SELECT seq_from, punkte FROM track_stuecke
                         WHERE owner_type = ? AND owner_id = ? ORDER BY seq_from');
    $q->execute([$art, $id]);
    return hash('sha256', json_encode([$u, $q->fetchAll(PDO::FETCH_NUM)]));
}

/** Die kleinste Punktnummer der vorhandenen Stücke, oder null. */
function ou_stuecke_ab(PDO $pdo, string $art, int $id): ?int
{
    $q = $pdo->prepare('SELECT MIN(seq_from) FROM track_stuecke WHERE owner_type = ? AND owner_id = ?');
    $q->execute([$art, $id]);
    $v = $q->fetchColumn();
    return $v === null || $v === false ? null : (int)$v;
}

/**
 * Die Datensätze zum Verschließen, in der Form, die der Browser beim
 * Einspielen einer Sicherung schon kennt (`rwEinsatz`, `rwStuecke`):
 * Phasen mit `occurred_at` und `lat`/`lon`, Reanimation mit `started_at` und
 * `events`, Spurpunkte als `[seq, lat, lon, ele, ts]`.
 *
 * @param list<array{0:string,1:int}> $kandidaten
 */
function ou_fuellen(PDO $pdo, int $userId, array $kandidaten): array
{
    $aus = [];
    $punkteSumme = 0;
    foreach ($kandidaten as [$art, $id]) {
        if ($aus && $punkteSumme >= OU_PUNKTE_MAX) { break; }
        $tab = $art === 'mission' ? 'missions' : 'rest_segments';
        $spalten = $art === 'mission'
            ? 'id, client_ref, started_at, ort_huelle, pat_blob, transport_dest, dest_lat, dest_lon, site_ele_m'
            : 'id, client_ref, started_at';
        $q = $pdo->prepare("SELECT $spalten FROM $tab WHERE id = ? AND user_id = ?");
        $q->execute([$id, $userId]);
        $r = $q->fetch(PDO::FETCH_ASSOC);
        if (!$r) { continue; }

        $punkte = spur_lesen($pdo, $art, $id);
        $punkteSumme += count($punkte);
        $b = $pdo->prepare('SELECT stufe, n_original FROM track_blobs WHERE owner_type = ? AND owner_id = ?');
        $b->execute([$art, $id]);
        $blob = $b->fetch(PDO::FETCH_ASSOC) ?: null;
        $abStueck = ou_stuecke_ab($pdo, $art, $id);
        $maxSeq = $punkte ? max(array_map(static fn($p) => (int)$p[0], $punkte)) : -1;
        /* Eine ausgedünnte Spur deckt als gepacktes Stück alle Nummern bis
         * zum Original — auch die, deren Punkte es nicht mehr gibt. */
        if ($blob && (int)$blob['stufe'] === 3) { $maxSeq = max($maxSeq, (int)$blob['n_original'] - 1); }

        $e = [
            'art'        => $art,
            'id'         => $id,
            'client_ref' => (string)$r['client_ref'],
            'started_at' => (string)$r['started_at'],
            'spur'       => [
                'punkte'     => $punkte,
                'stufe'      => $blob ? (int)$blob['stufe'] : 1,
                'n_original' => $blob ? (int)$blob['n_original'] : 0,
                /* Überschneiden sich Klartext und Stücke, bleibt die Spur
                 * liegen: Zwei Stände derselben Punkte führt dieses Werkzeug
                 * nicht zusammen. */
                'konflikt'   => $abStueck !== null && $maxSeq >= $abStueck,
            ],
            'spur_stand' => ou_spur_stand($pdo, $art, $id),
        ];
        if ($art === 'mission') {
            $ph = $pdo->prepare('SELECT phase, occurred_at, lat, lon FROM mission_phases
                                  WHERE mission_id = ? ORDER BY occurred_at, id');
            $ph->execute([$id]);
            $phasen = array_map(static fn($p) => [
                'phase' => (int)$p['phase'], 'occurred_at' => (string)$p['occurred_at'],
                'lat' => $p['lat'] === null ? null : (float)$p['lat'],
                'lon' => $p['lon'] === null ? null : (float)$p['lon']], $ph->fetchAll(PDO::FETCH_ASSOC));
            $rs = $pdo->prepare('SELECT id, started_at FROM resus_sessions WHERE mission_id = ?
                                  ORDER BY started_at, id');
            $rs->execute([$id]);
            $ev = $pdo->prepare('SELECT type, occurred_at FROM resus_events WHERE session_id = ?
                                  ORDER BY occurred_at, id');
            $reas = [];
            foreach ($rs->fetchAll(PDO::FETCH_ASSOC) as $s) {
                $ev->execute([(int)$s['id']]);
                $reas[] = ['started_at' => (string)$s['started_at'],
                           'events' => array_map(static fn($x) => [
                               'type' => (string)$x['type'], 'occurred_at' => (string)$x['occurred_at']],
                               $ev->fetchAll(PDO::FETCH_ASSOC))];
            }
            $e += [
                'phases'         => $phasen,
                'resus'          => $reas,
                /* Trägt der Einsatz schon eine Hülle UND Klartext daneben,
                 * führt der Browser zusammen (wie das Formular, E-S11-45 (4))
                 * und schickt die alte mit — der Wächter beim Tausch. */
                'ort_huelle'     => $r['ort_huelle'] !== null ? base64_encode((string)$r['ort_huelle']) : null,
                'pat_blob'       => $r['pat_blob'] !== null ? (string)$r['pat_blob'] : null,
                'transport_dest' => $r['transport_dest'] !== null ? (string)$r['transport_dest'] : null,
                'dest_lat'       => $r['dest_lat'] !== null ? (float)$r['dest_lat'] : null,
                'dest_lon'       => $r['dest_lon'] !== null ? (float)$r['dest_lon'] : null,
                'site_ele_m'     => $r['site_ele_m'] !== null ? (int)$r['site_ele_m'] : null,
            ];
        }
        $aus[] = $e;
    }
    return $aus;
}

/**
 * Einen Datensatz tauschen: das Verschlossene hinein, den Klartext hinaus —
 * in EINER Transaktion, nur bei unverändertem Stand.
 *
 * `ort` und `pat` tragen je den neuen Wert und den, den der Browser gelesen
 * hat (`alt`, null für „keiner"); weicht der Bestand davon ab, geschieht
 * nichts. Dazu der Fingerabdruck der Spur (`spur_stand`).
 *
 * @param array{art:string,id:int,spur_stand:string,stuecke:list<array>,
 *              ort?:array{neu:string,alt:?string},pat?:array{neu:string,alt:?string}} $p
 * @return array{ok:bool,grund?:string,stuecke?:int}
 */
function ou_tauschen(PDO $pdo, int $userId, array $p): array
{
    $art = $p['art'];
    $id  = $p['id'];
    return db_transaktion($pdo, function (PDO $pdo) use ($userId, $art, $id, $p): array {
        $tab = $art === 'mission' ? 'missions' : 'rest_segments';
        $spalten = $art === 'mission' ? 'id, ort_huelle, pat_blob' : 'id';
        $q = $pdo->prepare("SELECT $spalten FROM $tab WHERE id = ? AND user_id = ? FOR UPDATE");
        $q->execute([$id, $userId]);
        $r = $q->fetch(PDO::FETCH_ASSOC);
        if (!$r) { return ['ok' => false, 'grund' => 'fehlt']; }
        if (!hash_equals(ou_spur_stand($pdo, $art, $id), $p['spur_stand'])) {
            return ['ok' => false, 'grund' => 'stand'];
        }

        /* ERST ALLE WÄCHTER, DANN SCHREIBEN: Ein Rückgabewert `ok => false`
         * schließt die Transaktion regulär ab — was davor geschrieben wäre,
         * bliebe. */
        if ($art === 'mission') {
            if (isset($p['pat']) && ($r['pat_blob'] ?? null) !== $p['pat']['alt']) {
                return ['ok' => false, 'grund' => 'blob'];
            }
            if (isset($p['ort']) && ($r['ort_huelle'] ?? null) !== $p['ort']['alt']) {
                return ['ok' => false, 'grund' => 'huelle'];
            }
            if (isset($p['pat'])) {
                $pdo->prepare('UPDATE missions SET pat_blob = ?, transport_dest = NULL, dest_lat = NULL,
                                       dest_lon = NULL, site_ele_m = NULL
                                WHERE id = ?')->execute([$p['pat']['neu'], $id]);
            }
            if (isset($p['ort'])) {
                $pdo->prepare('UPDATE missions SET ort_huelle = ? WHERE id = ?')
                    ->execute([$p['ort']['neu'], $id]);
                $pdo->prepare('UPDATE mission_phases SET lat = NULL, lon = NULL WHERE mission_id = ?')
                    ->execute([$id]);
                einsatz_reas_ersetzen($pdo, $id, []);
            }
        }

        $n = 0;
        if ($p['stuecke']) {
            foreach ($p['stuecke'] as $s) {
                $n += spur2_stueck_schreiben($pdo, $art, $id, $s['seq_from'], $s['punkte'],
                    $s['ts_von'], $s['ts_bis'], $s['huelle'], $s['stufe'] ?? SPUR2_STUFE_GERAET,
                    $s['n_original'] ?? null);
            }
            if ($n !== count($p['stuecke'])) {
                throw new RuntimeException('Stück schon vorhanden — der Stand hätte das ausschließen sollen');
            }
            spur_loeschen($pdo, $art, [$id], ['stuecke' => false]);
        }
        return ['ok' => true, 'stuecke' => $n];
    });
}
