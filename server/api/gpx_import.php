<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';   // liefert $userId
require_once __DIR__ . '/../validate_lib.php';
require_once __DIR__ . '/../diensttag_lib.php';
require_once __DIR__ . '/../spur_lib.php';
require_once __DIR__ . '/../gpx_lib.php';

/**
 * GPX-Import (S4/A3, E-S4-18).
 *
 * POST api/gpx_import.php   JSON-Body, Header X-CSRF wie bei api/day.php
 *
 *   { day_id: 26,
 *     ziel:   'ruhe' | 'einsatz',
 *     xml:    '<?xml ...>',        der Dateiinhalt als Zeichenkette
 *     name:   'Dateiname.gpx' }  nur fuer die Rueckmeldung, optional
 *
 * WOFUER. Das Gegenstueck zum GPX-Abruf (S2/AP4). Eine Spur, die auf einem
 * anderen Geraet entstanden ist — ein Wanderuhr-Track, ein Export aus einer
 * Leitstellensoftware, das eigene Backup —, kommt damit in die Anwendung.
 *
 * ZWEI ZIELE, UND DIE WAHL IST NICHT KOSMETIK (E-R45-4):
 *
 *   ruhe      Die Datei ist die Aufzeichnung eines ganzen Dienstes. Sie wird
 *             EIN Ruhesegment, und die Einsaetze schneidet man danach heraus
 *             (4.97e). Das ist der Regelfall.
 *   einsatz   Die Datei IST genau ein Einsatz. Sie wird unmittelbar einer,
 *             die Phasenzeiten traegt man danach im Formular nach.
 *
 * DIE DATEI WIRD AUF DEM SERVER GELESEN, nicht im Browser — anders als beim
 * CSV-Import. Der Grund ist der Unterschied der beiden Faelle: Beim CSV
 * stehen PatientInnendaten in der Datei, die der Server nie sehen darf, also
 * muss der Browser lesen. Eine GPX-Datei enthaelt nichts Verschluesseltes.
 * Und die Ablehnungsregeln — allen voran „`time` ist Pflicht" — sind
 * verbindlich; eine verbindliche Regel im Browser ist keine.
 *
 * DER EINGANG IST EINE ZEICHENKETTE IM JSON-KOERPER und kein Dateiupload.
 * Diese Anwendung hat nirgends ein `$_FILES`; ein erster Upload-Weg braechte
 * `upload_max_filesize`, `post_max_size`, temporaere Verzeichnisse und deren
 * Rechte mit — vier Stellschrauben auf geteiltem Hosting fuer einen Vorgang,
 * den eine Zeichenkette genauso traegt. Der Browser liest die Datei mit
 * `FileReader`, wie beim CSV-Import.
 */

/**
 * Das virtuelle Geraet fuer von Hand entstandene Eintraege.
 *
 * DASSELBE wie beim CSV-Import und beim Nachtragen: `manual-<userId>`,
 * abgeschaltet, kann nie hochladen. Die Nutzerkennung gehoert IN die Abfrage
 * (M3-12/M6-09) — dass der Schluessel die Zugehoerigkeit im Namen traegt, ist
 * eine Zeichenkette und keine Bedingung.
 */
function gpx_import_geraet(PDO $pdo, int $userId): int
{
    $devKey = 'manual-' . $userId;
    $q = $pdo->prepare('SELECT id FROM devices WHERE device_id = ? AND user_id = ?');
    $q->execute([$devKey, $userId]);
    $devId = $q->fetchColumn();
    if ($devId !== false) { return (int)$devId; }

    $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label, active)
                   VALUES (?,?,?,?,0)')
        ->execute([$userId, $devKey,
                   password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
                   'Manuelle Einträge']);
    return (int)$pdo->lastInsertId();
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

    $ziel = (string)($b['ziel'] ?? 'ruhe');
    if ($ziel !== 'ruhe' && $ziel !== 'einsatz') {
        json_out(['error' => 'eingabe', 'meldung' => 'Unbekanntes Ziel.'], 400);
    }

    $pdo   = db();
    $pruef = new Pruefliste();

    /* DER DIENSTTAG MUSS DEM KONTO GEHOEREN, und die Bedingung steht in der
     * Abfrage. Ein Import in den Diensttag einer fremden Person waere der
     * schwerste Fehler, den dieser Endpunkt machen kann. */
    $dayId = pruef_zahl($b['day_id'] ?? null, 1, PHP_INT_MAX, 'day_id', $pruef);
    if ($dayId === null) {
        json_out(['error' => 'eingabe', 'meldung' => 'Kein Diensttag angegeben.'], 400);
    }
    $q = $pdo->prepare('SELECT id, day, deleted_at FROM days WHERE id = ? AND user_id = ?');
    $q->execute([$dayId, $userId]);
    $tag = $q->fetch(PDO::FETCH_ASSOC);
    if (!$tag) {
        json_out(['error' => 'nicht_gefunden',
                  'meldung' => 'Diesen Diensttag gibt es nicht (mehr).'], 404);
    }
    if ($tag['deleted_at'] !== null) {
        json_out(['error' => 'papierkorb',
                  'meldung' => 'Der Diensttag liegt im Papierkorb. Erst wiederherstellen.'], 409);
    }

    /* ---- Die Datei lesen --------------------------------------------------
     *
     * `gpx_lesen()` wirft mit einem Satz, der einer BedienerIn etwas sagt; er
     * geht unveraendert in die Meldung. Das ist Absicht: Eine Meldung wie
     * „Import fehlgeschlagen" liesse jemanden dreimal dieselbe Datei
     * hochladen, ohne je zu erfahren, dass ihr die Zeitstempel fehlen. */
    $xml = (string)($b['xml'] ?? '');
    try {
        $gelesen = gpx_lesen($xml, $pruef);
    } catch (InvalidArgumentException $ex) {
        json_out(['error' => 'gpx', 'meldung' => $ex->getMessage()], 422);
    }

    $punkte = $gelesen['punkte'];
    $vonTs  = (int)$punkte[0][4];
    $bisTs  = (int)$punkte[count($punkte) - 1][4];
    $von    = gmdate('Y-m-d H:i:s', $vonTs);
    $bis    = gmdate('Y-m-d H:i:s', $bisTs);

    /* ---- Und jetzt in EINEM Zug ------------------------------------------ */
    $pdo->beginTransaction();
    try {
        $devId = gpx_import_geraet($pdo, $userId);
        /* `imp-` WIE BEIM UEBRIGEN IMPORT (E-S4-18). Daran haengt die
         * Sperrliste: Ein geloeschter Eintrag mit dieser Kennung wird von
         * `ingest.php` nicht wieder angelegt (`deleted_refs`). */
        $ref = 'imp-' . bin2hex(random_bytes(12));

        if ($ziel === 'einsatz') {
            $pdo->prepare("INSERT INTO missions
                             (user_id, device_id, client_ref, day_id, started_at,
                              ended_at, final, manual, origin)
                           VALUES (?,?,?,?,?,?,1,1,'import')")
                ->execute([$userId, $devId, $ref, $dayId, $von, $bis]);
            $id  = (int)$pdo->lastInsertId();
            $typ = 'mission';
        } else {
            $pdo->prepare('INSERT INTO rest_segments
                             (user_id, device_id, client_ref, day_id,
                              started_at, ended_at, final)
                           VALUES (?,?,?,?,?,?,1)')
                ->execute([$userId, $devId, $ref, $dayId, $von, $bis]);
            $id  = (int)$pdo->lastInsertId();
            $typ = 'rest';
        }

        /* ALS BLOB UND NICHT ALS ZEILEN. Eine importierte Spur ist fertig —
         * es kommt nichts mehr nach, denn ihr „Geraet" ist eine Datei. Sie
         * gleich verdichtet abzulegen erspart dem Verdichtungsjob einen Lauf
         * und der Datenbank 62,4 Byte je Punkt (S2). `n_original` ist die
         * volle Punktzahl: Es ist zugleich die Fortsetzungsmarke, und eine
         * Nachlieferung unter derselben Kennung gaebe es nur, wenn jemand
         * dieselbe Datei ein zweites Mal importiert — dann soll sie NICHT
         * hinter die vorhandene gehaengt werden. */
        spur_blob_schreiben($pdo, $typ, $id,
            spur_kodieren($punkte, SPUR_STUFE_ROH, count($punkte)),
            SPUR_STUFE_ROH, count($punkte), count($punkte));

        /* Der Diensttag muss umschliessen, was an ihm haengt
         * (JSON-Vertrag 4.4) — dieselbe Regel wie beim Nachtragen und beim
         * Schneiden. */
        dt_zeitraum_fortschreiben($pdo, $dayId, $von, $bis);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        json_fehler($ex, 'gpx_import');
    }

    if ($typ === 'mission') {
        try {
            require_once __DIR__ . '/../site_elevation_lib.php';
            compute_site_elevation($pdo, $id);
        } catch (Throwable $ex) { /* Komfortwert, bewusst still */ }
    }

    $antwort = ['ok' => true, 'art' => $typ, 'id' => $id,
                'punkte' => count($punkte), 'segmente' => $gelesen['segmente'],
                'von' => fmt_local($von), 'bis' => fmt_local($bis)];
    /* WAS NICHT MITKAM, WIRD GENANNT — auch wenn der Import gelungen ist.
     * Eine Datei, von der die Haelfte der Punkte keine Zeit hatte, ist
     * angekommen und trotzdem nicht das, was jemand erwartet hat. */
    if ($gelesen['ohne_zeit'] > 0) { $antwort['ohne_zeit'] = $gelesen['ohne_zeit']; }
    if ($gelesen['verworfen'] > 0) { $antwort['verworfen'] = $gelesen['verworfen']; }
    if (!$pruef->sauber()) { $antwort['rejected'] = $pruef->nachUrsache(); }
    json_out($antwort);
} catch (Throwable $ex) {
    json_fehler($ex, 'gpx_import');
}
