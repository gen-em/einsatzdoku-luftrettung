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

/* Pruefschicht ausdruecklich laden.
 *
 * WARUM DAS HIER STEHEN MUSS
 * edbak_restore() benutzt Pruefliste und die pruef_*-Funktionen. Sie standen
 * nur zufaellig zur Verfuegung, wenn die aufrufende Seite validate_lib.php
 * schon geladen hatte — api/backup_restore.php tat das NICHT. Das
 * Wiedereinspielen brach deshalb mit "Class Pruefliste not found" ab, und zwar
 * seit die Pruefschicht eingefuehrt wurde.
 *
 * Gefunden auf der Testinstallation, nicht durch die automatische Pruefung:
 * Deren Skript hatte validate_lib.php selbst geladen und den fehlenden
 * require damit verdeckt. Wer eine Bibliothek prueft, muss sie so laden, wie
 * die Anwendung sie laedt — sonst prueft er seinen eigenen Aufbau mit.
 */
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/spur_lib.php';   // Spuren: Zeilen UND Blob (S2)
require_once __DIR__ . '/mission_fields_lib.php';   // mf_ist_spalte(), mf_ort_spalten()

/**
 * Inneres Backup-JSON aufbauen.
 *
 * DER PAPIERKORB IST TEIL JEDER SICHERUNG (E-S1-01). Bis Web 7.3.1 filterten
 * drei Abfragen hier auf `deleted_at IS NULL`, und ein Parameter
 * `$mitPapierkorb` schaltete den Filter fuer die Demo-Fixture ab. Beides ist
 * entfallen.
 *
 * WARUM DIE ENTSCHEIDUNG UMGEDREHT WURDE. Die alte Begruendung lautete: „Wer
 * eine Sicherung erstellt, sichert seinen Bestand, nicht seinen Abfall." Das
 * klingt einleuchtend und ist trotzdem falsch, denn der Papierkorb ist kein
 * Abfall — er ist ein WIEDERHERSTELLBARER Zustand mit einer laufenden Frist
 * (TRASH_DAYS = 90). Wer am Tag nach einem versehentlichen Loeschen sichert
 * und die Sicherung spaeter zurueckspielt, verliert genau das, was er
 * zurueckholen wollte, und zwar endgueltig und ohne Hinweis. Eine Sicherung
 * ist ein ABBILD; ein Abbild, das einen Teil des Bestands weglaesst, ist
 * keines.
 *
 * KEINE WAHLMOEGLICHKEIT AUF DER SICHERUNGSSEITE (E-S1-02). Ein Haken
 * „Papierkorb mitsichern" verschoebe die Entscheidung auf den Zeitpunkt, an
 * dem am wenigsten ueberlegt wird. Stattdessen nennen die Umfangsangaben
 * (Admin-Tabelle, Freigabe-Hinweis, `umfang.papierkorb` der Admin-Sicherung),
 * wie viel davon im Papierkorb liegt.
 *
 * Die Spalten `deleted_at` und `deleted_with_day` standen schon vorher in der
 * Datei — bisher stets `null` beziehungsweise `0`. Seit Nutzlast 7 tragen sie
 * einen Zustand, und `edbak_restore()` wertet ihn aus: Was hier geloescht ist,
 * kommt als Papierkorbeintrag zurueck. Der ZEITPUNKT wandert dabei nicht mit
 * — der Eintrag entsteht in der Zielinstallation neu und bekommt volle
 * 90 Tage (E-S1-03, docs/Backup-Format.md 2 und 3).
 *
 * `$ohneSpuren` IST DER KERN DER FASSUNG 4 (S2/AP5, Konzept 3.2.1). Statt der
 * Punktlisten traegt jedes spurtragende Objekt dann eine fortlaufende
 * `spur_ref` und die Angaben `stufe`, `n_original` und `n`; die Punkte kommen
 * ueber `api/backup_spuren.php` als SPUR1-Blobs in eigene Teile.
 *
 * WARUM DIE ZAHLEN IN DEN KERN GEHOEREN und nicht nur in die Spurteile: Sie
 * sind die einzige Stelle, an der beim Vergleich zu sehen ist, dass eine Spur
 * AUSGEDUENNT ist statt verlorengegangen. Und sie kosten nichts — `spur_zahlen()`
 * und `spur_umriss()` holen sie, ohne einen Punkt zu lesen.
 *
 * WARUM DIE NUMMER FORTLAUFEND IST und nicht die Datenbankkennung: Die
 * Kennung gilt nur in der Datenbank, aus der die Sicherung stammt — dieselbe
 * Ueberlegung wie beim Diensttag (E9) und beim Standortnamen (E15). `spur_ref`
 * ist eine Nummer DIESES Vorgangs und sonst nichts.
 */
function edbak_build(int $userId, bool $ohneSpuren = false): string {
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
    /* SPUREN UEBER spur_lib.php (S2/AP1) — Zeilen und Blob zusammen.
     *
     * Das Dateiformat bleibt, was es war: je Punkt [seq, lat, lon, ele, ts]
     * (Backup-Format.md). Die Nummer kommt jetzt aus der Position im Blob
     * beziehungsweise aus der Zeile; sie ist damit weiterhin luecken- und
     * dublettenfrei, was der Rueckweg voraussetzt. */
    $spuren = function (string $type, array $ids) use ($pdo, $zahl): array {
        $nach = [];
        foreach ($ids as $id) { $nach[$id] = []; }
        if (!$ids) { return $nach; }
        foreach (spur_lesen_viele($pdo, $type, $ids) as $id => $punkte) {
            foreach ($punkte as $p) {
                $nach[$id][] = [$zahl($p[0], true), $zahl($p[1]), $zahl($p[2]),
                                $zahl($p[3]), $zahl($p[4], true)];
            }
        }
        return $nach;
    };

    /* FASSUNG 4: statt der Punkte nur ihr Umriss. Vier Abfragen je Art, kein
     * gelesener Punkt — und die laufende Nummer, unter der der Spurteil den
     * Blob wiederfindet.
     *
     * DIE ZAHLEN BESCHREIBEN, WAS IN DER DATEI STEHT, nicht was in der
     * Datenbank liegt. Der Unterschied ist eine Stufe: Eine Spur, die noch als
     * Zeilen liegt (Stufe 1), wird beim Sichern verlustfrei kodiert und steht
     * in der Datei als Stufe 2 (Konzept 3.2.3). Wuerde der Kern hier „Stufe 1"
     * sagen und das Spurteil einen Stufe-2-Blob tragen, stuenden zwei
     * Wahrheiten in derselben Datei — und die falsche waere die, die der
     * Rueckweg zuerst liest.
     *
     *   Bestand Stufe 1 oder 2  ->  Datei Stufe 2, n_original = n
     *   Bestand Stufe 3         ->  Datei Stufe 3, n_original aus dem Blobkopf
     *
     * `n` KOMMT AUS `spur_zahlen()` UND NICHT AUS `$umriss['gesamt']`. Die
     * beiden sind nicht dasselbe: `gesamt` ist die hoechste Punktnummer plus
     * eins — bei einer ausgeduennten Spur also die Zahl VOR der Ausduennung
     * (443 statt der 148, die tatsaechlich gespeichert sind). Der erste Entwurf
     * hat das verwechselt, und die Sicherung haette fuer jede ausgeduennte Spur
     * eine Punktzahl genannt, die es in ihr nicht gibt. */
    $spurRefZaehler = 0;
    $spurVerzeichnis = [];
    $umrisse = function (string $type, array $ids) use ($pdo, &$spurRefZaehler): array {
        $nach = [];
        if (!$ids) { return $nach; }
        $u = spur_umriss($pdo, $type, $ids);
        $z = spur_zahlen($pdo, $type, $ids);
        foreach ($ids as $id) {
            $x = $u[$id] ?? null;
            $n = (int)($z[$id] ?? 0);
            if ($x === null || $n === 0) { continue; }
            $duenn = $x['stufe'] === SPUR_STUFE_DUENN;
            $nach[$id] = ['spur_ref' => ++$spurRefZaehler,
                          'stufe' => $duenn ? SPUR_STUFE_DUENN : SPUR_STUFE_ROH,
                          'n_original' => $duenn ? $x['n_original'] : $n,
                          'n' => $n];
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
    $missionSpalten = 'client_ref, day_id, started_at, ended_at, distance_m, ascent_m,
                       site_ele_m, final, manual, origin, edited, transport_dest,
                       transport_mode, na_escort, false_alarm, start_src,
                       dest_lat, dest_lon,
                       winch, winch_cycles, winch_cycles_pat, winch_airload,
                       bergwacht, secondary, schockraum, bw_unit, bw_info,
                       other_ema, crew_override,
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
                         WHERE user_id = ? ORDER BY started_at", [$userId]);
    $missionIds = array_map(static fn($m) => (int)$m['id'], $missionZeilen);

    /* Abweichende Besatzung je Einsatz (`mission_crew`, E7). Bis Web 5.10.0
     * waren es fuenf Spalten in `missions`; sie stehen deshalb im Format jetzt
     * als Objekt role_code => name. */
    $einsatzCrewNach = [];
    if ($missionIds) {
        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, role_code, name FROM mission_crew
                 WHERE mission_id IN ({IDS}) ORDER BY mission_id, role_code',
                $missionIds) as $c) {
            $einsatzCrewNach[(int)$c['mission_id']][(string)$c['role_code']] = $c['name'];
        }
    }

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
    $spurNachEinsatz = $ohneSpuren ? $umrisse('mission', $missionIds)
                                   : $spuren('mission', $missionIds);

    foreach ($missionZeilen as $m) {
        $mid = (int)$m['id'];
        unset($m['id']);        // nur fuer die Zuordnung gebraucht
        $m['phases']    = $phasenNach[$mid]    ?? [];
        $m['resources'] = $mittelNach[$mid]    ?? [];
        $m['resus']     = $sitzungenNach[$mid] ?? [];
        $m['crew']      = $einsatzCrewNach[$mid] ?? [];
        if ($ohneSpuren) {
            /* KEIN `track` UND KEIN LEERES `track`. Ein leeres Feld saehe aus
             * wie „hat keine Spur"; die Fassung sagt, dass die Punkte woanders
             * stehen. Wer keine Spur hat, bekommt gar keine `spur_ref`. */
            if (isset($spurNachEinsatz[$mid])) {
                $m += $spurNachEinsatz[$mid];
                $spurVerzeichnis[] = ['spur_ref' => $m['spur_ref'], 'art' => 'mission',
                                      'id' => $mid, 'n' => $m['n']];
            }
        } else {
            $m['track'] = $spurNachEinsatz[$mid] ?? [];
        }
        $missions[] = $m;
    }

    $rests = [];
    $restZeilen = $q('SELECT id, client_ref, day_id, started_at, ended_at, final,
                             deleted_at, deleted_with_day
                      FROM rest_segments
                      WHERE user_id = ? ORDER BY started_at', [$userId]);
    $restIds = array_map(static fn($r) => (int)$r['id'], $restZeilen);
    $spurNachRuhe = $ohneSpuren ? $umrisse('rest', $restIds) : $spuren('rest', $restIds);
    foreach ($restZeilen as $r) {
        $rid = (int)$r['id'];
        unset($r['id']);
        if ($ohneSpuren) {
            if (isset($spurNachRuhe[$rid])) {
                $r += $spurNachRuhe[$rid];
                $spurVerzeichnis[] = ['spur_ref' => $r['spur_ref'], 'art' => 'rest',
                                      'id' => $rid, 'n' => $r['n']];
            }
        } else {
            $r['track'] = $spurNachRuhe[$rid] ?? [];
        }
        $rests[] = $r;
    }

    /* ---- Diensttage -------------------------------------------------------
     *
     * DIE KENNUNG MUSS MIT (E9). Bis Web 5.10.0 war das Datum der Schluessel
     * eines Flugtags und genuegte als Verweis; seit mehrere Diensttage auf einem
     * Kalendertag liegen koennen, benennt es keine Zeile mehr. Einsaetze und
     * Ruhe-Segmente verweisen mit `day_id` hierher, und beim Einspielen wird die
     * Kennung auf die neu vergebene umgeschrieben.
     *
     * ANGEZEIGT UND GESICHERT WERDEN DIE SNAPSHOT-SPALTEN (E8): `vehicle_name`
     * und `base_name` stehen im Diensttag selbst. Der frueher noetige Join auf
     * `aircraft` und `bases` ist damit entfallen — und mit ihm die Luecke, dass
     * ein geloeschtes Rettungsmittel eine Sicherung ohne Bezeichnung hinterliess.
     * Die Verweise auf die Stammdaten werden zusaetzlich als NAMEN mitgefuehrt
     * (`vehicle_ref`, `base_ref`), damit das Einspielen sie wieder verknuepfen
     * kann; sie zeigen auf denselben Namen, koennen aber leer sein, wenn der
     * Stammdatensatz inzwischen fehlt.
     *
     * Rollensatz und Faehigkeiten sind Teil des Snapshots und muessen mit —
     * ohne sie waere nach dem Einspielen nicht mehr erkennbar, welche Rollen ein
     * Dienst anbot.
     *
     * `day_refs` MUSS ins Backup (Konzept 4.8): Sonst legt ein spaeter
     * eintreffender Upload derselben Uhr den Diensttag nach einer
     * Wiederherstellung erneut an (A9, A8). */
    $days = [];
    $dayZeilen = $q('SELECT d.id, d.day, d.started_at, d.ended_at, d.kind,
                            d.base_name, d.base_lat, d.base_lon, d.vehicle_name,
                            d.notes, d.deleted_at,
                            v.name AS vehicle_ref, b.name AS base_ref
                     FROM days d
                     LEFT JOIN vehicles v ON v.id = d.vehicle_id
                     LEFT JOIN bases b ON b.id = d.base_id
                     WHERE d.user_id = ?
                     ORDER BY d.day, d.started_at, d.id', [$userId]);
    $dayIds = array_map(static fn($d) => (int)$d['id'], $dayZeilen);

    $tagesCrewNach = $tagesCapsNach = $dayRefsNach = [];
    if ($dayIds) {
        foreach (sql_in_bloecken($pdo,
                'SELECT day_id, role_code, name FROM day_crew
                 WHERE day_id IN ({IDS}) ORDER BY day_id, role_code', $dayIds) as $c) {
            $tagesCrewNach[(int)$c['day_id']][(string)$c['role_code']] = $c['name'];
        }
        foreach (sql_in_bloecken($pdo,
                'SELECT day_id, capability FROM day_capabilities
                 WHERE day_id IN ({IDS}) ORDER BY day_id, capability', $dayIds) as $c) {
            $tagesCapsNach[(int)$c['day_id']][] = (string)$c['capability'];
        }
        /* Die Uhr-Kennungen samt oeffentlicher Geraetekennung. Die interne
         * device_id gilt nur in dieser Datenbank; der Name des Geraets ist der
         * portable Verweis — dieselbe Regel wie bei den Stammdaten. */
        foreach (sql_in_bloecken($pdo,
                'SELECT r.day_id, r.day_ref, dev.device_id
                 FROM day_refs r LEFT JOIN devices dev ON dev.id = r.device_id
                 WHERE r.day_id IN ({IDS}) ORDER BY r.day_id, r.id', $dayIds) as $r) {
            $dayRefsNach[(int)$r['day_id']][] = [
                'day_ref'   => (string)$r['day_ref'],
                'device_id' => $r['device_id'] !== null ? (string)$r['device_id'] : null,
            ];
        }
    }
    foreach ($dayZeilen as $d) {
        $did = (int)$d['id'];
        $d['crew']         = $tagesCrewNach[$did] ?? [];
        $d['capabilities'] = $tagesCapsNach[$did] ?? [];
        $d['refs']         = $dayRefsNach[$did]   ?? [];
        $days[] = $d;
    }

    // Standard-Markierungen liegen seit user_defaults nicht mehr an der Zeile
    // selbst, werden im Exportformat aber weiterhin als is_default-Flag je
    // Zeile abgebildet (Abwaertskompatibilitaet, s. docs/Backup-Format.md)
    $defBaseId = (int)($q('SELECT item_id FROM user_defaults WHERE user_id = ? AND kind = "base"', [$userId])[0]['item_id'] ?? 0);
    $defVehId  = (int)($q('SELECT item_id FROM user_defaults WHERE user_id = ? AND kind = "vehicle"', [$userId])[0]['item_id'] ?? 0);

    /* Standorte: mit Koordinaten (E37). Der Name bleibt der portable
     * Schluessel, an dem die uebrigen Stammdaten haengen. */
    $bases = $q('SELECT id, name, lat, lon FROM bases WHERE user_id = ? ORDER BY name', [$userId]);
    $baseNameById = [];
    foreach ($bases as &$b) {
        $baseNameById[(int)$b['id']] = (string)$b['name'];
        $b['is_default'] = (int)$b['id'] === $defBaseId ? 1 : 0;
        unset($b['id']);
    }
    unset($b);

    /* Rettungsmittel (bis Web 5.10.0: `aircraft`). Art, Rollen und
     * Faehigkeiten gehoeren dazu; der Standort als NAME, weil Kennungen nur in
     * dieser Datenbank gelten (E15). */
    $vehZeilen = $q('SELECT id, name, kind, base_id FROM vehicles
                     WHERE user_id = ? ORDER BY name', [$userId]);
    $vehIds = array_map(static fn($v) => (int)$v['id'], $vehZeilen);
    $vehRollen = $vehCaps = [];
    if ($vehIds) {
        foreach (sql_in_bloecken($pdo,
                'SELECT vehicle_id, role_code FROM vehicle_roles
                 WHERE vehicle_id IN ({IDS}) ORDER BY vehicle_id, role_code', $vehIds) as $r) {
            $vehRollen[(int)$r['vehicle_id']][] = (string)$r['role_code'];
        }
        foreach (sql_in_bloecken($pdo,
                'SELECT vehicle_id, capability FROM vehicle_capabilities
                 WHERE vehicle_id IN ({IDS}) ORDER BY vehicle_id, capability', $vehIds) as $c) {
            $vehCaps[(int)$c['vehicle_id']][] = (string)$c['capability'];
        }
    }
    $vehicles = [];
    foreach ($vehZeilen as $v) {
        $vid = (int)$v['id'];
        $vehicles[] = [
            'name'         => (string)$v['name'],
            'kind'         => (string)$v['kind'],
            'base_ref'     => $v['base_id'] !== null ? ($baseNameById[(int)$v['base_id']] ?? null) : null,
            'roles'        => $vehRollen[$vid] ?? [],
            'capabilities' => $vehCaps[$vid] ?? [],
            'is_default'   => $vid === $defVehId ? 1 : 0,
        ];
    }

    /* Auswahl zentraler Standorte (E16). Als NAME, nicht als Kennung: Ein
     * zentraler Standort heisst in der Zieldatenbank gleich, hat dort aber eine
     * andere Kennung. */
    $userBases = $q('SELECT b.name FROM user_bases ub
                     JOIN bases b ON b.id = ub.base_id
                     WHERE ub.user_id = ? AND b.user_id IS NULL
                     ORDER BY b.name', [$userId]);

    /* Die uebrigen Stammdaten tragen jetzt ihren Standort (E15) — ebenfalls als
     * Name. Ohne ihn liesse sich nach dem Einspielen nicht entscheiden, zu
     * welchem Standort eine Zielklinik gehoert, und die Auswahllisten blieben
     * leer. */
    $mitBase = static function (array $zeilen, array $namen): array {
        foreach ($zeilen as &$z) {
            $z['base_ref'] = isset($z['base_id']) && $z['base_id'] !== null
                ? ($namen[(int)$z['base_id']] ?? null) : null;
            unset($z['base_id']);
        }
        unset($z);
        return $zeilen;
    };

    $data = [
        'format' => 'einsatzdoku-backup',
        /* Nutzlastversion 7 (E-S1-07): Die Datei fuehrt jetzt den Papierkorb.
         * Der Container bleibt 3 und die Signatur `EDBAK2` unveraendert — nur
         * der INHALT hat sich geaendert.
         *
         * DER SPRUNG IST KENNZEICHNUNG, KEINE SPERRE, und das gehoert deutlich
         * gesagt: api/backup_restore.php nimmt weiterhin alles ab Version 6 an,
         * und ein bereits AUSGELIEFERTER Stand (Web 7.3.1 und aelter) tut das
         * auch. Der wertet `deleted_at` aber nicht aus und braechte den
         * Papierkorb einer Version-7-Datei als AKTIVEN Bestand zurueck. Das
         * laesst sich nachtraeglich nicht verhindern — eine Sperre haette in
         * jenen Staenden stehen muessen. Es steht deshalb als Warnung in
         * docs/Backup-Format.md 4.
         *
         * Umgekehrt bleiben Version-6-Dateien vollstaendig einspielbar: Sie
         * enthalten keinen Papierkorb, `deleted_at` fehlt oder ist null, und
         * der Rueckweg legt sie als aktiven Bestand an — genau wie bisher. */
        /* NUTZLAST 8 (S2/AP5): der Kern der Fassung 4 — ohne Punktlisten,
         * dafuer mit `spur_ref`, `stufe`, `n_original` und `n` je Spur.
         *
         * DIE ZAHL SAGT, WIE DIE SPUREN DRINSTEHEN, und der Rueckweg
         * entscheidet daran, welchen der beiden Wege er nimmt: 6 und 7 tragen
         * Punktlisten, 8 traegt Verweise. Das ist der Unterschied, an dem es
         * haengt — nicht die Anwesenheit eines `track`-Feldes, denn eine Spur
         * ohne Punkte saehe genauso aus.
         *
         * Nutzlast 7 wird weiterhin GELESEN (E-S2-12) und nicht mehr
         * geschrieben. Mit NaDoku 1.0 faellt sie weg (Backlog Nr. 46). */
        'version' => $ohneSpuren ? 8 : 7,
        'created_at' => gmdate('c'),
        'app' => 'einsatzdoku-notarzt',
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
            'vehicles'        => $vehicles,
            'user_bases'      => array_map(static fn($r) => (string)$r['name'], $userBases),
            'crew_presets'    => $mitBase($q('SELECT role_code, name, base_id FROM crew_presets
                                              WHERE user_id = ? ORDER BY role_code, name', [$userId]), $baseNameById),
            'bw_units'        => $mitBase($q('SELECT name, base_id FROM bw_units
                                              WHERE user_id = ? ORDER BY name', [$userId]), $baseNameById),
            'resources'       => $mitBase($q('SELECT name, base_id FROM resources
                                              WHERE user_id = ? ORDER BY name', [$userId]), $baseNameById),
            'transport_dests' => $mitBase($q('SELECT name, lat, lon, base_id FROM transport_dests
                                              WHERE user_id = ? ORDER BY name', [$userId]), $baseNameById),
        ],
        'days' => $days,
        'missions' => $missions,
        'rest_segments' => $rests,
    ];

    /* DAS VERZEICHNIS DER SPUREN — ein ARBEITSFELD, das nicht in die Datei
     * gehoert (S2/AP5).
     *
     * Der Browser braucht die Datenbankkennung, um die Blobs zu holen
     * (`api/backup_spuren.php`); die Datei darf sie nicht tragen, denn sie
     * gilt nur in DIESER Datenbank — genau deshalb loescht `edbak_build()`
     * `id` aus jedem Objekt (E9, E15).
     *
     * Beides zusammen geht nur so: Die Zuordnung steht getrennt und traegt den
     * Unterstrich, den dieses Projekt fuer Arbeitsfelder benutzt (`_pat`,
     * `_patState`). Der Sicherungslauf loescht sie, bevor er versiegelt — und
     * die Containerprobe sieht nach, ob er es getan hat. */
    if ($ohneSpuren) { $data['_spur_index'] = $spurVerzeichnis; }
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
              'days' => 0, 'stammdaten' => 0, 'stammdaten_skipped' => 0,
              /* Wie viel davon in den PAPIERKORB gegangen ist (E-S1-08). Die
               * Zahlen stecken in den drei Zaehlern darueber MIT drin — sie
               * sagen nicht "zusaetzlich", sondern "davon". */
              'papierkorb' => ['einsaetze' => 0, 'diensttage' => 0, 'ruhezeiten' => 0]];

    /* URSACHEN GETRENNT ZAEHLEN (M5-14).
     * "40 uebersprungen" ist nicht deutbar: Es kann heissen "alles war schon
     * da" (gut) oder "alles war kaputt" (schlecht). Genau diese Unterscheidung
     * braucht, wer eine Wiederherstellung beurteilen muss. */
    $grund = ['bereits_vorhanden' => 0, 'datum_oder_zeit' => 0, 'aufbau' => 0,
              'tag_im_papierkorb' => 0, 'tag_unbrauchbar' => 0,
              /* NEU (E-S1-08): Einsaetze und Ruhesegmente, deren Diensttag aus
               * der Datei uebersprungen wurde. Sie liefen bisher unter
               * `datum_oder_zeit` — irrefuehrend, denn an ihrem Datum ist
               * nichts auszusetzen; es fehlt ihnen der Tag. Wer die Meldung
               * liest, suchte den Fehler an der falschen Stelle. */
              'tag_uebersprungen' => 0,
              /* NEU (Backlog Nr. 34): Die Einsaetze eines Datei-Diensttags
               * liegen im Ziel an MEHREREN verschiedenen Tagen. Schritt 1 der
               * Wiedererkennung liefert dann kein Ergebnis, der Fingerabdruck
               * entscheidet — und dass geraten werden musste, gehoert in die
               * Rueckmeldung statt in die Stille. Zaehlt Diensttage, nicht
               * Einsaetze. */
              'tag_mehrdeutig' => 0];

    /* EIN Loeschzeitpunkt fuer den ganzen Lauf (E-S1-03).
     *
     * Aus der Datei wird der ZUSTAND uebernommen (geloescht ja/nein), nicht
     * der Zeitpunkt: Der Eintrag entsteht in dieser Installation neu und
     * bekommt volle TRASH_DAYS Tage — dieselbe Linie wie bei `herkunft`.
     *
     * Der Gegenentwurf waere, den Zeitpunkt aus der Datei zu uebernehmen.
     * Dann koennte eine aeltere Sicherung Eintraege mitbringen, deren Frist
     * laengst abgelaufen ist, und der naechste Aufraeumjob loeschte sie
     * endgueltig — eine Wiederherstellung, die einspielt und Stunden spaeter
     * selbst wieder entfernt.
     *
     * In PHP gerechnet und nicht als UTC_TIMESTAMP() ins SQL geschrieben,
     * damit ALLE Eintraege eines Laufs denselben Zeitpunkt tragen (die
     * Verbindung steht auf UTC, siehe db.php). Das ist auch die ehrlichere
     * Angabe: Sie sind in EINEM Vorgang entstanden. */
    $loeschZeit = gmdate('Y-m-d H:i:s');

    // Diensttage im Papierkorb DES ZIELKONTOS einmal vorab feststellen (D1).
    $tageImPapierkorb = [];
    $qT = $pdo->prepare('SELECT day FROM days WHERE user_id = ? AND deleted_at IS NOT NULL');
    $qT->execute([$userId]);
    foreach ($qT->fetchAll(PDO::FETCH_COLUMN) as $dTrash) {
        $tageImPapierkorb[(string)$dTrash] = true;
    }
    $pruef = new Pruefliste();
    $hoeheOffen = [];   // Einsatz-IDs fuer die Hoehenberechnung nach dem Commit (M5-05)

    /* DIE SPURKARTE (S2/AP5, Konzept 3.2.4).
     *
     * Nutzlast 8 traegt keine Punktlisten, sondern je Spur eine `spur_ref`.
     * Der Browser schickt die Blobs danach in eigenen Anfragen — und braucht
     * dafuer die Kennung, unter der der Datensatz HIER angelegt wurde. Die
     * kennt nur dieser Lauf.
     *
     * Sie steht bewusst nicht in `$stats`: `$stats` ist die Rueckmeldung an
     * die Nutzerin und wird angezeigt; das hier ist eine Arbeitsangabe. */
    $spurKarte = [];
    $nutzlast = (int)($data['version'] ?? 0);
    $mitVerweisen = $nutzlast >= 8;

    /* VERSCHACHTELUNGSFAEHIG (Web 7.3.0).
     *
     * Diese Funktion hat ihre Transaktion bisher bedingungslos geoeffnet. Das
     * ging gut, solange sie genau einen Aufrufer hatte — api/backup_restore.php,
     * das nichts weiter tut. Der Demo-Reset (demo_lib.php) muss aber MEHR in
     * dieselbe Klammer nehmen: Kontomaterial, Geraete und Bestand. Zerfiele das
     * in mehrere Transaktionen, koennte ein Fehler in der Mitte ein Konto mit
     * halbem Bestand hinterlassen — und ausgerechnet der Reset laeuft
     * unbeaufsichtigt, alle 30 Minuten.
     *
     * PDO kennt keine echten verschachtelten Transaktionen; ein zweites
     * beginTransaction() wirft. Deshalb wird geprueft, ob schon eine laeuft,
     * und Commit wie Rollback bleiben dem ueberlassen, der sie geoeffnet hat.
     * Ein Fehler kommt als Ausnahme weiterhin heraus — der aeussere Aufrufer
     * setzt zurueck. */
    $eigeneTransaktion = !$pdo->inTransaction();
    if ($eigeneTransaktion) { $pdo->beginTransaction(); }
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
            // Koordinaten kommen mit (E37); sie duerfen leer bleiben.
            $st = $pdo->prepare('INSERT IGNORE INTO bases (user_id, name, lat, lon) VALUES (?,?,?,?)');
            $st->execute([$userId, $name, $b['lat'] ?? null, $b['lon'] ?? null]);
            $stats['stammdaten'] += $st->rowCount();
            if (!$hasDefBase && (int)($b['is_default'] ?? 0)) { $newDefBaseName = $name; }
        }
        /* Standortkennung zum Namen. Der Name ist der portable Schluessel (E15):
         * Die Kennung aus der Sicherungsdatei gilt nur in der Datenbank, aus der
         * sie stammt. Gesucht wird unter den EIGENEN und den zentralen
         * Standorten — ein zentraler heisst in beiden Installationen gleich. */
        $baseIdByName = function (?string $name) use ($pdo, $userId): ?int {
            if ($name === null || $name === '') { return null; }
            $x = $pdo->prepare('SELECT id FROM bases
                                WHERE name = ? AND (user_id = ? OR user_id IS NULL)
                                ORDER BY user_id IS NULL LIMIT 1');
            $x->execute([$name, $userId]);
            $id = $x->fetchColumn();
            return $id === false ? null : (int)$id;
        };

        /* Zentrale Standorte wieder auswaehlen (E16). Ohne das verschwaenden sie
         * nach dem Einspielen aus den Auswahllisten, obwohl die Diensttage sie
         * weiter benennen. */
        foreach (($sd['user_bases'] ?? []) as $bn) {
            $bid = $baseIdByName((string)$bn);
            if ($bid === null) { continue; }
            $pdo->prepare('INSERT IGNORE INTO user_bases (user_id, base_id) VALUES (?,?)')
                ->execute([$userId, $bid]);
        }

        /* Rettungsmittel samt Art, Rollen und Faehigkeiten (E3, E29).
         *
         * OHNE STANDORT WIRD NICHT ANGELEGT: `vehicles.base_id` traegt nach der
         * Nachbearbeitung NOT NULL (A12), und ein Rettungsmittel ohne Standort
         * waere nach E15 kein gueltiger Zustand. Der Fall wird gezaehlt, nicht
         * stillschweigend uebergangen. */
        $hv = $pdo->prepare("SELECT COUNT(*) FROM user_defaults WHERE user_id = ? AND kind = 'vehicle'");
        $hv->execute([$userId]);
        $hasDefVeh = (bool)$hv->fetchColumn();
        $newDefVehName = null;
        foreach (($sd['vehicles'] ?? []) as $v) {
            $name = (string)($v['name'] ?? '');
            if ($name === '') { $stats['stammdaten_skipped']++; continue; }
            $kind = ($v['kind'] ?? '') === 'ground' ? 'ground' : 'air';
            $bid  = $baseIdByName(isset($v['base_ref']) ? (string)$v['base_ref'] : null);
            if ($bid === null) { $stats['stammdaten_skipped']++; continue; }
            if (stammdaten_dup_global('vehicles', 'name', $name)) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO vehicles (user_id, base_id, name, kind)
                                 VALUES (?,?,?,?)');
            $st->execute([$userId, $bid, $name, $kind]);
            $stats['stammdaten'] += $st->rowCount();

            $x = $pdo->prepare('SELECT id FROM vehicles WHERE user_id = ? AND name = ?');
            $x->execute([$userId, $name]);
            $vid = $x->fetchColumn();
            if ($vid !== false) {
                $insR = $pdo->prepare('INSERT IGNORE INTO vehicle_roles (vehicle_id, role_code)
                                       VALUES (?,?)');
                foreach ((array)($v['roles'] ?? []) as $rc) {
                    if (array_key_exists((string)$rc, CREW_ROLES)) { $insR->execute([(int)$vid, (string)$rc]); }
                }
                /* Faehigkeiten kommen ausschliesslich an luftgebundenen
                 * Rettungsmitteln vor (E29, schema.sql). Bei einem
                 * bodengebundenen werden sie verworfen statt gespeichert — sonst
                 * traege der Bestand einen Zustand, den die Oberflaeche nicht
                 * herstellen kann. */
                if ($kind === 'air') {
                    $insC = $pdo->prepare('INSERT IGNORE INTO vehicle_capabilities
                                           (vehicle_id, capability) VALUES (?,?)');
                    foreach ((array)($v['capabilities'] ?? []) as $cap) {
                        if (array_key_exists((string)$cap, VEHICLE_CAPABILITIES)) {
                            $insC->execute([(int)$vid, (string)$cap]);
                        }
                    }
                }
            }
            if (!$hasDefVeh && (int)($v['is_default'] ?? 0)) { $newDefVehName = $name; }
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
        if ($newDefVehName !== null) {
            $x = $pdo->prepare('SELECT id FROM vehicles WHERE user_id = ? AND name = ?');
            $x->execute([$userId, $newDefVehName]);
            if ($vid = $x->fetchColumn()) {
                $pdo->prepare('INSERT INTO user_defaults (user_id, kind, item_id) VALUES (?,"vehicle",?)
                               ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)')->execute([$userId, $vid]);
            }
        }
        /* Die uebrigen Stammdaten haengen jetzt an einem Standort (E15). Ohne
         * ihn wird nicht angelegt — die Spalte traegt nach der Nachbearbeitung
         * NOT NULL, und ein Eintrag ohne Standort erschiene in keiner
         * Auswahlliste. Der Fall wird gezaehlt. */
        foreach (($sd['crew_presets'] ?? []) as $c) {
            $rc = (string)($c['role_code'] ?? $c['role'] ?? '');
            if (!array_key_exists($rc, CREW_ROLES)) { continue; }
            $bid = $baseIdByName(isset($c['base_ref']) ? (string)$c['base_ref'] : null);
            if ($bid === null) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO crew_presets
                                 (user_id, base_id, role_code, name) VALUES (?,?,?,?)');
            $st->execute([$userId, $bid, $rc, (string)$c['name']]);
            $stats['stammdaten'] += $st->rowCount();
        }
        foreach (($sd['bw_units'] ?? []) as $w) {
            $bid = $baseIdByName(isset($w['base_ref']) ? (string)$w['base_ref'] : null);
            if ($bid === null) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO bw_units (user_id, base_id, name) VALUES (?,?,?)');
            $st->execute([$userId, $bid, (string)$w['name']]);
            $stats['stammdaten'] += $st->rowCount();
        }
        foreach (($sd['resources'] ?? []) as $r) {
            $bid = $baseIdByName(isset($r['base_ref']) ? (string)$r['base_ref'] : null);
            if ($bid === null) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO resources (user_id, base_id, name) VALUES (?,?,?)');
            $st->execute([$userId, $bid, (string)$r['name']]);
            $stats['stammdaten'] += $st->rowCount();
        }
        foreach (($sd['transport_dests'] ?? []) as $t) {
            $bid = $baseIdByName(isset($t['base_ref']) ? (string)$t['base_ref'] : null);
            if ($bid === null) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO transport_dests
                                 (user_id, base_id, name, lat, lon) VALUES (?,?,?,?,?)');
            $st->execute([$userId, $bid, (string)$t['name'],
                          $t['lat'] ?? null, $t['lon'] ?? null]);
            $stats['stammdaten'] += $st->rowCount();
        }

        /* ---- Diensttage -------------------------------------------------------
         *
         * DIE KENNUNG WIRD NEU VERGEBEN, und die Zuordnung von alter auf neue
         * Kennung ist das Ergebnis dieses Abschnitts: Einsaetze und
         * Ruhe-Segmente der Datei verweisen mit der ALTEN `day_id`, in der
         * Datenbank gilt die neue.
         *
         * KEIN `INSERT IGNORE` MEHR, und die Wiedererkennung braucht mehr als
         * das Datum. Bis Web 5.10.0 verhinderte der Tagesschluessel
         * `uq_user_day`, dass ein bereits vorhandener Flugtag ein zweites Mal
         * entstand; das Einspielen konnte deshalb blind einfuegen. Seit E9 gibt
         * es diesen Schluessel nicht — ein blindes INSERT legte bei jedem
         * Einspielen derselben Datei neue Diensttage an.
         *
         * ERKANNT WIRD IN ZWEI SCHRITTEN, und der erste ist der belastbare:
         *
         *   1. UEBER DIE EINSAETZE. `client_ref` ist geraeteweit eindeutig und
         *      wandert unveraendert durch jede Sicherung. Existiert einer der
         *      Einsaetze dieses Diensttags im Ziel schon, IST sein `day_id` der
         *      gesuchte Tag — daran gibt es nichts zu raten.
         *   2. UEBER EINEN FINGERABDRUCK aus Datum, Dienstbeginn, Dienstende,
         *      Art und den eingefrorenen Bezeichnungen. Er greift fuer
         *      Diensttage OHNE Einsatz, die Schritt 1 nicht sehen kann. Zwei
         *      Tage, die in all dem uebereinstimmen, sind nicht
         *      unterscheidbar — sie zu vereinen ist dann kein Verlust.
         *
         * Datum und Dienstbeginn allein genuegen NICHT: Zwei Dienste eines
         * Kalendertags koennen denselben Beginn tragen, sobald ein Einsatz
         * zwischen ihnen verschoben wurde (dt_zeitraum_fortschreiben zieht den
         * Beginn nach vorne). Genau dieser Fall verschmolz beim Pruefen zwei
         * Diensttage zu einem.
         *
         * ANGABEN WERDEN NICHT UEBERSCHRIEBEN. Ein vorhandener Diensttag bleibt
         * unangetastet — auch das ist bestehendes Verhalten (INSERT IGNORE tat
         * genau das). Nur die Zuordnung wird gemerkt, damit fehlende Einsaetze
         * an ihm landen. */
        $dayIdMap = [];   // alte Kennung aus der Datei -> Kennung in dieser DB

        /* LIEGT DER ZIELTAG IM PAPIERKORB? (E-S1-04)
         *
         * Die Antwort entscheidet ueber `deleted_with_day` der Einsaetze und
         * Ruhesegmente, die an ihm landen — und sie haengt am ZIEL, nicht an
         * der Datei: Ein in der Datei mitgeloeschter Einsatz, dessen Zieltag
         * hier aktiv ist, wird EINZELN geloescht. Andernfalls entstuende ein
         * Eintrag mit `deleted_with_day = 1` an einem aktiven Tag, und der
         * waere unsichtbar (trash_list_missions() zeigt nur
         * `deleted_with_day = 0`) und unwiederbringlich (trash_restore_day()
         * holt nur zurueck, was am geloeschten Tag haengt). */
        $zieltagGeloescht = [];   // Kennung in dieser DB -> bool
        $tagZustand = $pdo->prepare('SELECT deleted_at FROM days WHERE id = ? AND user_id = ?');

        // Schritt 1 braucht die Einsatzkennungen JE QUELL-DIENSTTAG, also vor
        // der Schleife: Die Einsaetze selbst werden erst danach verarbeitet.
        $refsJeQuelltag = [];
        foreach (($data['missions'] ?? []) as $mm) {
            if (!is_array($mm)) { continue; }
            $q = isset($mm['day_id']) ? (int)$mm['day_id'] : 0;
            $ref = (string)($mm['client_ref'] ?? '');
            if ($q > 0 && $ref !== '') { $refsJeQuelltag[$q][] = $ref; }
        }
        /* SCHRITT 1 BELEGT, ER RAET NICHT MEHR (Backlog Nr. 34).
         *
         * Hier stand `… AND client_ref = ? AND day_id IS NOT NULL LIMIT 1`,
         * abgefragt fuer eine Kennung nach der anderen, und der ERSTE Treffer
         * bestimmte den Zieltag fuer ALLE Einsaetze und Ruhesegmente des
         * Datei-Tags. Drei Dinge waren daran falsch:
         *
         *  - Hat jemand im Ziel EINEN dieser Einsaetze auf einen anderen Tag
         *    verschoben, wanderte der ganze Datei-Tag mit — auch wenn er im
         *    Ziel unveraendert daneben lag.
         *  - Fuehrte der Treffer auf einen Tag im PAPIERKORB, wurden seither
         *    (E-S1-19) alle aktiven Eintraege des Datei-Tags abgelehnt. Richtig
         *    gezaehlt, aber angekommen ist nichts.
         *  - `LIMIT 1` ohne `ORDER BY`, und `client_ref` ist nur je
         *    `device_id` eindeutig: Bei zwei Uhren kann derselbe Wert zweimal
         *    vorkommen, und welcher gewinnt, sagte niemand.
         *
         * Jetzt werden ALLE Kennungen des Datei-Tags nachgeschlagen, und zwar
         * nur auf AKTIVE Zieltage:
         *
         *   genau ein Zieltag  -> benutzen (das bisherige Verhalten, belegt)
         *   mehrere Zieltage   -> Schritt 1 gilt als ergebnislos, der
         *                         Fingerabdruck entscheidet; der Widerspruch
         *                         wird als `tag_mehrdeutig` gezaehlt
         *   keiner              -> Fingerabdruck wie bisher
         *
         * Die richtige Antwort auf „raten" ist nicht, anders zu raten, sondern
         * zu merken, dass man es nicht weiss. Der Fingerabdruck bleibt Schritt
         * 2 und nicht Schritt 1: Er ist der SPROEDERE Anker — er bricht,
         * sobald jemand am Zieltag Beginn, Ende, Art, Rettungsmittel oder
         * Station berichtigt hat, und das ist der haeufige Fall. `client_ref`
         * ist stabil. */
        $findeUeberEinsatz = $pdo->prepare('SELECT DISTINCT m.day_id
                                              FROM missions m
                                              JOIN days d ON d.id = m.day_id
                                             WHERE m.user_id = ? AND m.client_ref = ?
                                               AND d.deleted_at IS NULL');
        $findeTag = $pdo->prepare(
            'SELECT id FROM days
              WHERE user_id = ? AND day = ?
                AND ((started_at IS NULL AND ? IS NULL) OR started_at = ?)
                AND ((ended_at IS NULL AND ? IS NULL) OR ended_at = ?)
                AND ((kind IS NULL AND ? IS NULL) OR kind = ?)
                AND ((vehicle_name IS NULL AND ? IS NULL) OR vehicle_name = ?)
                AND ((base_name IS NULL AND ? IS NULL) OR base_name = ?)
              ORDER BY id LIMIT 1');
        foreach (($data['days'] ?? []) as $d) {
            $tagWert = pruef_kalendertag($d['day'] ?? null, 'days.day', $pruef);
            if ($tagWert === null) { $grund['tag_unbrauchbar']++; continue; }
            $altId = isset($d['id']) ? (int)$d['id'] : 0;

            /* IST DIESER TAG IN DER DATEI GELOESCHT? (E-S1-03/05)
             *
             * Seit Nutzlast 7 traegt die Datei den Papierkorb. Der ZUSTAND
             * wird uebernommen, der Zeitpunkt nicht — siehe $loeschZeit oben. */
            $dateiGeloescht = ($d['deleted_at'] ?? null) !== null;

            /* D1 — UND DER FALL HAT SEIT S1 ZWEI HAELFTEN (E-S1-05).
             *
             * (1) EIN AKTIVER DATEI-TAG, dessen DATUM im Zielkonto einen Tag
             *     im Papierkorb trifft, wird uebersprungen und gezaehlt. Das
             *     ist unveraendertes Verhalten: Das Loeschen war eine bewusste
             *     Handlung, und ein Einspielen soll sie nicht nebenbei
             *     rueckgaengig machen ("Ablehnen statt Zurueckholen").
             *
             *     Ohne diese Pruefung tat INSERT IGNORE hier zwar nichts — der
             *     eindeutige Schluessel griff —, aber eben STILL: Der Tag wurde
             *     weder eingespielt noch gezaehlt noch erwaehnt. Wer eine
             *     Sicherung zurueckspielt und seine Diensttage vermisst, hatte
             *     keinen Anhaltspunkt.
             *
             * (2) EIN IN DER DATEI GELOESCHTER TAG durchlaeuft die normale
             *     Wiedererkennung. Er will gar nicht aktiv werden — ihn am
             *     Ziel-Papierkorb zu messen ergaebe keinen Sinn. Wird er nicht
             *     gefunden, entsteht er ALS PAPIERKORBEINTRAG samt seinen
             *     mitgeloeschten Einsaetzen und Ruhesegmenten.
             *
             * Zwei geloeschte Tage desselben Datums (einer in der Datei, einer
             * im Ziel, verschiedener Fingerabdruck) duerfen damit nebeneinander
             * bestehen. Das ist richtig so: Der Papierkorb kennt keine
             * Eindeutigkeit je Datum, und seit E9 gibt es sie auch bei den
             * aktiven Tagen nicht mehr. */
            if (!$dateiGeloescht && isset($tageImPapierkorb[$tagWert])) {
                $grund['tag_im_papierkorb']++;
                continue;
            }

            $startedAt = $d['started_at'] ?? null;
            $endedAt   = $d['ended_at'] ?? null;
            $kindWert  = in_array($d['kind'] ?? null, ['air', 'ground'], true) ? $d['kind'] : null;
            $vName     = $d['vehicle_name'] ?? null;
            $bName     = $d['base_name'] ?? null;

            // Schritt 1: ueber einen Einsatz, der im Ziel schon liegt.
            $vorhanden = false;
            $kandidaten = [];
            foreach (($refsJeQuelltag[$altId] ?? []) as $ref) {
                $findeUeberEinsatz->execute([$userId, $ref]);
                foreach ($findeUeberEinsatz->fetchAll(PDO::FETCH_COLUMN) as $w) {
                    $kandidaten[(int)$w] = true;
                }
            }
            if (count($kandidaten) === 1) {
                $vorhanden = (int)array_key_first($kandidaten);
            } elseif (count($kandidaten) > 1) {
                /* WIDERSPRUCH — und der wird gemeldet, nicht aufgeloest. Die
                 * Einsaetze dieses Datei-Tags liegen im Ziel an verschiedenen
                 * Diensttagen; welcher davon „der" Zieltag ist, sagt die Datei
                 * nicht. Schritt 2 entscheidet, und die Zahl steht in der
                 * Rueckmeldung, damit jemand nachsehen kann. */
                $grund['tag_mehrdeutig']++;
            }
            // Schritt 2: Fingerabdruck.
            if ($vorhanden === false) {
                $findeTag->execute([$userId, $tagWert, $startedAt, $startedAt,
                                    $endedAt, $endedAt, $kindWert, $kindWert,
                                    $vName, $vName, $bName, $bName]);
                $w = $findeTag->fetchColumn();
                if ($w !== false) { $vorhanden = (int)$w; }
            }
            if ($vorhanden !== false) {
                /* ANGABEN WERDEN NICHT UEBERSCHRIEBEN — und das gilt auch fuer
                 * den Loeschzustand (E-S1-05). Ein hier vorhandener Tag bleibt,
                 * wie er ist: Ein aktiver Zieltag wird nicht wegen der Datei
                 * geloescht, ein geloeschter nicht wegen der Datei
                 * zurueckgeholt. Was aus der Datei kommt, sind die FEHLENDEN
                 * Einsaetze und Ruhesegmente — und die richten sich nach dem
                 * Zustand des Zieltags. */
                $zielId = (int)$vorhanden;
                if ($altId > 0) { $dayIdMap[$altId] = $zielId; }
                if (!array_key_exists($zielId, $zieltagGeloescht)) {
                    $tagZustand->execute([$zielId, $userId]);
                    /* fetchColumn() liefert `false` ohne Zeile und `null` bei
                     * einem leeren Feld — beides heisst "nicht im Papierkorb",
                     * und beide muessen ausdruecklich dastehen. `!== null`
                     * allein haette die fehlende Zeile als geloescht gewertet. */
                    $w = $tagZustand->fetchColumn();
                    $zieltagGeloescht[$zielId] = ($w !== false && $w !== null);
                }
                continue;
            }

            /* Angelegt wird mit den EINGEFRORENEN Angaben aus der Datei (E8) —
             * nicht ueber dt_zuordnen(), das sie aus den heutigen Stammdaten
             * frisch holen wuerde. Eine Sicherung soll den Bestand abbilden,
             * nicht ihn neu ableiten: Ein inzwischen umbenanntes Rettungsmittel
             * traegt in der Datei seinen damaligen Namen, und der gehoert
             * zurueck (A4, A13p). Die Fremdschluessel werden daneben ueber den
             * NAMEN wieder verknuepft, soweit der Stammdatensatz existiert. */
            $vehId  = null;
            if (!empty($d['vehicle_ref'])) {
                $x = $pdo->prepare('SELECT id FROM vehicles
                                    WHERE name = ? AND (user_id = ? OR user_id IS NULL)
                                    ORDER BY user_id IS NULL LIMIT 1');
                $x->execute([(string)$d['vehicle_ref'], $userId]);
                $w = $x->fetchColumn();
                $vehId = $w === false ? null : (int)$w;
            }
            $baseId = $baseIdByName(isset($d['base_ref']) ? (string)$d['base_ref'] : null);

            $st = $pdo->prepare('INSERT INTO days
                (user_id, day, started_at, ended_at, vehicle_id, base_id, kind,
                 base_name, base_lat, base_lon, vehicle_name, notes, deleted_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([$userId, $tagWert, $startedAt, $endedAt,
                $vehId, $baseId, $kindWert,
                $bName, $d['base_lat'] ?? null, $d['base_lon'] ?? null,
                $vName, $d['notes'] ?? null,
                $dateiGeloescht ? $loeschZeit : null]);
            $neuId = (int)$pdo->lastInsertId();
            $stats['days']++;
            $zieltagGeloescht[$neuId] = $dateiGeloescht;
            if ($dateiGeloescht) { $stats['papierkorb']['diensttage']++; }
            if ($altId > 0) { $dayIdMap[$altId] = $neuId; }

            // Rollensatz und Faehigkeiten sind Teil des Snapshots (E8).
            $insDC = $pdo->prepare('INSERT IGNORE INTO day_crew (day_id, role_code, name)
                                    VALUES (?,?,?)');
            foreach ((array)($d['crew'] ?? []) as $rc => $nm) {
                if (!array_key_exists((string)$rc, CREW_ROLES)) { continue; }
                $insDC->execute([$neuId, (string)$rc,
                                 ($nm === null || $nm === '') ? null : mb_substr((string)$nm, 0, 120)]);
            }
            $insDCap = $pdo->prepare('INSERT IGNORE INTO day_capabilities (day_id, capability)
                                      VALUES (?,?)');
            foreach ((array)($d['capabilities'] ?? []) as $cap) {
                if (array_key_exists((string)$cap, VEHICLE_CAPABILITIES)) {
                    $insDCap->execute([$neuId, (string)$cap]);
                }
            }

            /* UHR-KENNUNGEN WIEDERHERSTELLEN (Konzept 4.8, A9).
             *
             * Ohne sie legt ein spaeter eintreffender Upload derselben Uhr den
             * Diensttag erneut an — genau der Fall, den A8 ausschliesst. Die
             * Geraetekennung ist der oeffentliche Name; ein Geraet, das es hier
             * nicht (mehr) gibt, ergibt device_id = NULL, und die Kennung bleibt
             * trotzdem gespeichert. */
            $insRef = $pdo->prepare('INSERT IGNORE INTO day_refs (day_id, device_id, day_ref)
                                     VALUES (?,?,?)');
            $findeDev = $pdo->prepare('SELECT id FROM devices WHERE device_id = ? AND user_id = ?');
            foreach ((array)($d['refs'] ?? []) as $ref) {
                if (!is_array($ref) || empty($ref['day_ref'])) { continue; }
                $devId = null;
                if (!empty($ref['device_id'])) {
                    $findeDev->execute([(string)$ref['device_id'], $userId]);
                    $w = $findeDev->fetchColumn();
                    $devId = $w === false ? null : (int)$w;
                }
                $insRef->execute([$neuId, $devId, mb_substr((string)$ref['day_ref'], 0, 64)]);
            }
        }

        /* Einsaetze: Dublette = gleiche client_ref bei dieser NutzerIn */
        $exists = $pdo->prepare('SELECT id FROM missions WHERE user_id = ? AND client_ref = ?');
        $insPoint = $pdo->prepare('INSERT INTO track_points
            (owner_type, owner_id, seq, lat, lon, ele, ts) VALUES (?,?,?,?,?,?,?)');

        /* SPUR SCHREIBEN — EINE STELLE FUER BEIDE ARTEN (Backlog Nr. 31).
         *
         * Es waren zwei, und sie waren verschieden: Der Einsatz begrenzte die
         * Menge, pruefte den Aufbau und liess pruef_breite/pruef_laenge ueber
         * die Koordinaten laufen; das Ruhesegment schrieb roh, was in der
         * Datei stand. `(float)"Unfug"` ist 0.0 — aus einem unbrauchbaren
         * Punkt wurde damit still eine gueltige Koordinate im Golf von Guinea,
         * und die Punktzahl war unbegrenzt.
         *
         * Zwei Kopien einer Pruefung sind eine Kopie zu viel: Die zweite
         * bleibt zurueck. Jetzt gibt es eine.
         *
         * ZUSAETZLICH GEPRUEFT werden `seq` und `ts` gegen den Wertebereich
         * ihrer Spalten (beide INT UNSIGNED NOT NULL) und `ele` auf
         * Numerik. Auch das war auf BEIDEN Wegen offen: Ein negatives `seq`
         * oder ein Text in `ele` bringt nicht einen Punkt zu Fall, sondern
         * ueber die Ausnahme die ganze Wiederherstellung.
         *
         * UND DIE EINDEUTIGKEIT VON `seq` (Backlog Nr. 35). Der Wertebereich
         * allein reicht nicht: `track_points` hat den Primaerschluessel
         * (owner_type, owner_id, seq), zwei Punkte mit derselben Nummer loesen
         * also einen Schluesselkonflikt aus — und der reisst wieder den
         * gesamten Lauf mit. Ein eigener Export erzeugt keine Wiedergaenger;
         * eine von Hand bearbeitete oder fremde Datei kann es. Der zweite
         * Punkt wird deshalb uebersprungen und gemeldet.
         *
         * Der kuerzere Weg waere INSERT IGNORE gewesen. Er ist der stille:
         * Die Datei behielte einen Fehler, den niemand zu sehen bekommt.
         * $gesehen wird je Eigentuemer neu angelegt — der Schluessel gilt nur
         * innerhalb einer Spur. */
        $spurSchreiben = function (string $typ, int $ownerId, $liste) use ($insPoint, $pruef): void {
            $gesehen = [];
            /* ABLEHNEN STATT KAPPEN (F-S2-02).
             *
             * Bis Web 9.14.0 stand hier pruef_menge() mit derselben Konstante,
             * die ingest.php je ANFRAGE anwendet — hier gilt sie aber je
             * SPUR. Was die Uhr ueber viele Anfragen aufbauen darf, wurde beim
             * Zurueckspielen bei 2000 Punkten abgeschnitten; die Datei trug
             * die ganze Spur, zurueck kam ihr Anfang. In ein frisches Konto
             * eingespielt war der Verlust endgueltig.
             *
             * Jetzt: eigene, hohe Grenze — und eine Spur darueber wird GANZ
             * abgelehnt und benannt. Eine halbe Spur sieht aus wie eine ganze. */
            $punkte = pruef_menge_streng($liste ?? [], LIMIT_TRACKPUNKTE_SPUR,
                                         $typ . '.track', $pruef);
            if ($punkte === null) { return; }
            foreach ($punkte as $p) {
                if (!is_array($p) || count($p) < 5) { continue; }
                $la = pruef_breite($p[1], $typ . '.track.lat', $pruef);
                $lo = pruef_laenge($p[2], $typ . '.track.lon', $pruef);
                $seq = pruef_zahl($p[0], 0, 4294967295, $typ . '.track.seq', $pruef);
                $ts  = pruef_zahl($p[4], 0, 4294967295, $typ . '.track.ts', $pruef);
                if ($la === null || $lo === null || $seq === null || $ts === null) { continue; }
                if (isset($gesehen[$seq])) {
                    $pruef->melde($typ . '.track.seq', 'Nummer doppelt');
                    continue;
                }
                $gesehen[$seq] = true;
                // Hoehe darf fehlen (Spalte ist NULL-faehig), aber nicht Text sein.
                $ele = is_numeric($p[3]) ? (float)$p[3] : null;
                $insPoint->execute([$typ, $ownerId, $seq, $la, $lo, $ele, $ts]);
            }
        };
        $FIELDS = require __DIR__ . '/mission_fields.php';
        require_once __DIR__ . '/site_elevation_lib.php';
        /* WELCHE SPALTEN AUS DER DATEI UEBERNOMMEN WERDEN.
         *
         * Grundlage ist der Feldkatalog — er ist die eine Liste, gegen die auch
         * das Formular arbeitet. Drei Ergaenzungen, die er nicht hergibt:
         *
         *  - mf_ist_spalte() statt einer Pruefung auf 'resources' allein: Seit
         *    Web 6.0.0 liegt auch die BESATZUNG nicht mehr in `missions`
         *    (`crew_p1` … als 'store' => 'crew'). Sie stand hier weiterhin in
         *    der Liste; eine Datei mit einem Schluessel `crew_p1` — etwa eine
         *    von Hand bearbeitete — haette ein INSERT auf eine Spalte erzeugt,
         *    die es nicht mehr gibt, und damit nicht eine Zeile, sondern die
         *    ganze Wiederherstellung zum Scheitern gebracht. Die Besatzung
         *    kommt unten aus `crew`.
         *  - Die KOORDINATENSPALTEN der Ortsfelder (Web 6.1.0): Sie heissen
         *    nicht wie ihr Feld (`transport_dest` -> `dest_lat`/`dest_lon`) und
         *    stehen deshalb in mf_ort_spalten().
         *  - `start_src`, die Abfahrtortregel: kein Katalogfeld (siehe
         *    mission_fields.php), aber eine gewoehnliche Spalte, die gesichert
         *    wird und zurueckkommen muss.
         *  - `created_at`, der Anlegezeitpunkt der ZEILE (E-S1-06, Backlog
         *    Nr. 25). Er wurde immer gesichert und kam nie zurueck: Nach einer
         *    Wiederherstellung trugen alle Einsaetze den Zeitpunkt des
         *    Einspielens — am Referenzdatensatz gemessen 79 verschiedene Werte
         *    davor, 5 danach. Die Angabe ist keine fachliche Zeit (das ist
         *    `started_at`), aber sie ist eine ANGABE, und eine Sicherung, die
         *    eine Angabe stillschweigend fallenlaesst, ist keine. Der Wert
         *    laeuft durch pruef_utc_oder_sql(); ist er unbrauchbar, wird die
         *    Spalte weggelassen und die Datenbank-Vorgabe greift — die Zeile
         *    bleibt.
         *
         * Wer eine Spalte hinzufuegt, die NICHT im Katalog steht, traegt sie
         * hier ein — sonst wird sie gesichert und beim Einspielen verworfen.
         *
         * NICHT hier stehen `deleted_at` und `deleted_with_day`: Sie werden
         * nicht uebernommen, sondern nach E-S1-03/04 NEU BESTIMMT (Zustand aus
         * der Datei, Zeitpunkt und Bindung an den Tag aus diesem Lauf). Sie
         * stehen deshalb unten ausdruecklich im INSERT. */
        $extraCols = [];
        $collectCols = function (array $fs) use (&$collectCols, &$extraCols) {
            foreach ($fs as $col => $f) {
                if (mf_ist_spalte($f)) { $extraCols[] = $col; }
                foreach (mf_ort_spalten($f) as $ortCol) { $extraCols[] = $ortCol; }
                if (!empty($f['children'])) { $collectCols($f['children']); }
            }
        };
        $collectCols($FIELDS);
        // Alt-Backups: loc_* wird ignoriert
        $extraCols = array_merge($extraCols, ['start_src', 'pat_blob', 'created_at']);

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
            $startedAt = pruef_utc_oder_sql($m['started_at'] ?? null, 'started_at', $pruef);
            /* DER DIENSTTAG IST PFLICHT. `missions.day_id` ist ein
             * Fremdschluessel; ohne ihn waere der Einsatz verwaist (A11). Die
             * Kennung aus der Datei wird ueber $dayIdMap auf die hier vergebene
             * umgeschrieben — fehlt sie dort, wurde der Diensttag uebersprungen
             * (Papierkorb, unbrauchbares Datum), und der Einsatz gehoert
             * ebenfalls uebersprungen und gezaehlt. Bis Web 5.10.0 trug der
             * Einsatz sein Datum selbst und konnte ohne Tag existieren. */
            $altDayId = isset($m['day_id']) ? (int)$m['day_id'] : 0;
            $dayId    = $dayIdMap[$altDayId] ?? 0;
            if ($dayId === 0) {
                /* ZWEI VERSCHIEDENE GRUENDE, GETRENNT GEZAEHLT (E-S1-08).
                 *
                 * Bis Web 7.3.1 liefen beide unter `datum_oder_zeit`, und das
                 * war irrefuehrend: Am Datum dieser Einsaetze ist nichts
                 * auszusetzen — ihr Diensttag wurde uebersprungen (Papierkorb
                 * im Ziel, unbrauchbares Datum am Tag). Wer die Rueckmeldung
                 * las, suchte den Fehler an der falschen Stelle und fand
                 * nichts.
                 *
                 * Nennt die Datei ueberhaupt keinen Tag, ist es dagegen
                 * wirklich ein Datumsproblem der Zeile. */
                $stats['missions_skipped']++;
                $grund[$altDayId > 0 ? 'tag_uebersprungen' : 'datum_oder_zeit']++;
                continue;
            }
            if ($startedAt === null) {
                $stats['missions_skipped']++; $grund['datum_oder_zeit']++; continue;
            }
            $endedAt = pruef_utc_oder_sql($m['ended_at'] ?? null, 'ended_at', $pruef);

            /* LOESCHZUSTAND (E-S1-03/04/19). Aus der Datei kommt das OB, aus
             * diesem Lauf der Zeitpunkt.
             *
             * `deleted_with_day` braucht BEIDE Seiten, und die erste Fassung
             * las nur eine: Sie setzte 1, sobald Einsatz und Zieltag geloescht
             * sind. Das ist zu grob, denn die Kombination „Einsatz EINZELN
             * geloescht, Tag danach geloescht" ist ein regulaerer Zustand —
             * `trash_delete_day()` fasst bereits geloeschte Einsaetze nicht an
             * (`WHERE deleted_at IS NULL`), sie behalten also
             * `deleted_with_day = 0` an einem geloeschten Tag. Wer so einen
             * Einsatz einspielte, bekam ihn als „mit dem Tag geloescht"
             * zurueck: aus der Einzelliste des Papierkorbs verschwunden und
             * beim Wiederherstellen des Tages wieder aktiv, obwohl er es
             * vorher nicht wurde.
             *
             * Richtig ist die UND-Verknuepfung: Der Wert aus der Datei sagt,
             * ob der Eintrag am Tag hing; der Zieltag sagt, ob das hier
             * ueberhaupt gelten kann. E-S1-04 formuliert das als „nur wenn" —
             * eine notwendige, keine hinreichende Bedingung. */
            $mGeloescht = ($m['deleted_at'] ?? null) !== null;
            $zielTagWeg = !empty($zieltagGeloescht[$dayId]);
            $mitTag     = ($mGeloescht && $zielTagWeg
                           && (int)($m['deleted_with_day'] ?? 0) === 1) ? 1 : 0;

            /* AKTIVER EINTRAG AN EINEM GELOESCHTEN ZIELTAG: ABLEHNEN (E-S1-19).
             *
             * Die Gegenrichtung der Invariante. Landet ein in der Datei
             * AKTIVER Einsatz auf einem Zieltag, der hier im Papierkorb liegt,
             * dann stuende er an einem Tag, den die Tagesliste nicht zeigt —
             * in der Suche sichtbar, in der Uebersicht nicht, im Papierkorb
             * auch nicht, und beim endgueltigen Loeschen des Tages bliebe er
             * ohne Diensttag zurueck.
             *
             * Das ist dieselbe Regel wie D1, nur eine Ebene tiefer: Was hier
             * im Papierkorb liegt, nimmt nichts Neues auf. Die Datumspruefung
             * oben kann den Fall nicht abfangen — sie vergleicht Kalenderdaten,
             * und die Zuordnung ueber `client_ref` (Schritt 1) kann auf einen
             * Tag ANDEREN Datums fuehren. */
            if (!$mGeloescht && $zielTagWeg) {
                $stats['missions_skipped']++; $grund['tag_im_papierkorb']++; continue;
            }

            $oe = edbak_origin_edited($m);

            $cols = ['user_id', 'client_ref', 'day_id', 'started_at', 'ended_at',
                     'manual', 'origin', 'edited', 'final', 'distance_m', 'ascent_m',
                     'deleted_at', 'deleted_with_day'];
            $vals = [$userId,
                     pruef_text($m['client_ref'] ?? null, 64, 'client_ref', $pruef)
                        ?? ('bak-' . bin2hex(random_bytes(6))),
                     $dayId, $startedAt, $endedAt,
                     /* pruef_flag statt (int): Beide Spalten sind TINYINT(1),
                      * und (int) einer Zahl jenseits von 127 laeuft dort ueber
                      * — ein Fehler, der die ganze Transaktion kostet. Fuer
                      * gueltige Werte ist das Ergebnis dasselbe. */
                     pruef_flag($m['manual'] ?? 0), $oe['origin'], $oe['edited'],
                     pruef_flag($m['final'] ?? 1),
                     pruef_zahl($m['distance_m'] ?? null, 0, 100000000, 'distance_m', $pruef),
                     pruef_zahl($m['ascent_m'] ?? null, 0, 100000, 'ascent_m', $pruef),
                     $mGeloescht ? $loeschZeit : null, $mitTag];
            foreach ($extraCols as $c) {
                if (!array_key_exists($c, $m)) { continue; }
                if ($c === 'created_at') {
                    /* Unbrauchbarer Wert -> Spalte WEGLASSEN, nicht NULL
                     * schreiben (E-S1-06). `missions.created_at` traegt eine
                     * Datenbank-Vorgabe; ein ausdrueckliches NULL wuerde sie
                     * umgehen und die Zeile ohne Anlegezeitpunkt hinterlassen.
                     * Die Zeile bleibt in jedem Fall — ein Komfortwert darf
                     * eine Wiederherstellung nicht kosten. */
                    $wert = pruef_utc_oder_sql($m[$c], 'created_at', $pruef);
                    if ($wert === null) { continue; }
                    $cols[] = $c; $vals[] = $wert;
                    continue;
                }
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

            /* Abweichende Besatzung (`mission_crew`, E7). Bis Web 5.10.0 waren
             * es fuenf Spalten und wanderten ueber $extraCols mit; jetzt sind es
             * Zeilen. Nur belegte Rollen: `mission_crew` fuehrt Abweichungen,
             * keine Leerzeilen. */
            $insMC = $pdo->prepare('INSERT IGNORE INTO mission_crew
                (mission_id, role_code, name) VALUES (?,?,?)');
            foreach ((array)($m['crew'] ?? []) as $rc => $nm) {
                if (!array_key_exists((string)$rc, CREW_ROLES)) { continue; }
                if ($nm === null || trim((string)$nm) === '') { continue; }
                $insMC->execute([$mid, (string)$rc, mb_substr(trim((string)$nm), 0, 120)]);
            }

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
            /* ZWEI WEGE, UND DIE FASSUNG ENTSCHEIDET — nicht das Vorhandensein
             * eines `track`-Feldes. Eine Spur ohne Punkte saehe genauso aus
             * wie ein Verweis, und dann liefe eine Fassung-8-Datei still in
             * den Altweg und verloere alle Spuren. */
            if ($mitVerweisen) {
                if (isset($m['spur_ref'])) {
                    $spurKarte[(int)$m['spur_ref']] = ['art' => 'mission', 'id' => $mid];
                }
            } else {
                $spurSchreiben('mission', $mid, $m['track'] ?? []);
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
            if ($mGeloescht) { $stats['papierkorb']['einsaetze']++; }
        }

        /* Ruhesegmente.
         *
         * SIE ZAEHLEN IHRE GRUENDE JETZT MIT (E-S1-08). Bisher erhoehte sich
         * hier nur `rests_skipped`, und in einem Fall auch `datum_oder_zeit` —
         * „bereits vorhanden" fiel unter den Tisch. Bei 95 Ruhesegmenten und
         * einem wiederholten Einspielen ist das die haeufigste Ursache
         * ueberhaupt, und sie war die einzige, die nicht dastand. */
        $rexists = $pdo->prepare('SELECT id FROM rest_segments WHERE user_id = ? AND client_ref = ?');
        foreach (($data['rest_segments'] ?? []) as $r) {
            if (!is_array($r)) { $stats['rests_skipped']++; $grund['aufbau']++; continue; }
            $rexists->execute([$userId, (string)($r['client_ref'] ?? '')]);
            if ($rexists->fetchColumn()) {
                $stats['rests_skipped']++; $grund['bereits_vorhanden']++; continue;
            }
            // Wie beim Einsatz: ohne Diensttag kein Ruhe-Segment (A11), und
            // die beiden Gruende dafuer werden getrennt gezaehlt.
            $altRDayId = isset($r['day_id']) ? (int)$r['day_id'] : 0;
            $rDayId    = $dayIdMap[$altRDayId] ?? 0;
            if ($rDayId === 0) {
                $stats['rests_skipped']++;
                $grund[$altRDayId > 0 ? 'tag_uebersprungen' : 'datum_oder_zeit']++;
                continue;
            }
            /* PRUEFSCHICHT — SIE FEHLTE HIER VOLLSTAENDIG (Backlog Nr. 31).
             *
             * `started_at` und `ended_at` gingen roh ins INSERT, `client_ref`
             * ohne Laengengrenze. `rest_segments.started_at` ist
             * DATETIME NOT NULL und `client_ref` VARCHAR(64) NOT NULL: Ein
             * unbrauchbarer Zeitwert oder eine zu lange Kennung kostete damit
             * nicht die eine Zeile, sondern ueber die Ausnahme die GANZE
             * Wiederherstellung — der Aufrufer sah nur noch eine
             * Fehlermeldung. Beim Einsatz war genau diese Richtung im Review
             * ausdruecklich umgedreht worden (siehe Kommentar oben); die
             * Ruhesegmente sind damals uebersehen worden.
             *
             * Jetzt gilt hier dasselbe Muster: pruefen, im Zweifel die Zeile
             * ueberspringen und den Grund zaehlen. */
            $rRef = pruef_text($r['client_ref'] ?? null, 64, 'rest.client_ref', $pruef)
                    ?? ('bak-' . bin2hex(random_bytes(6)));
            $rStart = pruef_utc_oder_sql($r['started_at'] ?? null, 'rest.started_at', $pruef);
            if ($rStart === null) {
                $stats['rests_skipped']++; $grund['datum_oder_zeit']++; continue;
            }
            $rEnde = pruef_utc_oder_sql($r['ended_at'] ?? null, 'rest.ended_at', $pruef);

            // Loeschzustand nach denselben Regeln wie beim Einsatz
            // (E-S1-03/04/19), einschliesslich der UND-Verknuepfung mit dem
            // Wert aus der Datei und der Ablehnung aktiver Eintraege an einem
            // geloeschten Zieltag.
            $rGeloescht = ($r['deleted_at'] ?? null) !== null;
            $rZielTagWeg = !empty($zieltagGeloescht[$rDayId]);
            $rMitTag    = ($rGeloescht && $rZielTagWeg
                           && (int)($r['deleted_with_day'] ?? 0) === 1) ? 1 : 0;
            if (!$rGeloescht && $rZielTagWeg) {
                $stats['rests_skipped']++; $grund['tag_im_papierkorb']++; continue;
            }
            $pdo->prepare('INSERT INTO rest_segments
                (user_id, client_ref, day_id, started_at, ended_at, final,
                 deleted_at, deleted_with_day)
                VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$userId, $rRef, $rDayId, $rStart, $rEnde,
                           pruef_flag($r['final'] ?? 1),
                           $rGeloescht ? $loeschZeit : null, $rMitTag]);
            $rid = (int)$pdo->lastInsertId();
            if ($mitVerweisen) {
                if (isset($r['spur_ref'])) {
                    $spurKarte[(int)$r['spur_ref']] = ['art' => 'rest', 'id' => $rid];
                }
            } else {
                $spurSchreiben('rest', $rid, $r['track'] ?? []);
            }
            $stats['rests']++;
            if ($rGeloescht) { $stats['papierkorb']['ruhezeiten']++; }
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

        if ($eigeneTransaktion) { $pdo->commit(); }
    } catch (Throwable $ex) {
        if ($eigeneTransaktion) { $pdo->rollBack(); }
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

    if ($mitVerweisen) { $stats['spur_karte'] = $spurKarte; }
    return $stats;
}
