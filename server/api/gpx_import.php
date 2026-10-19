<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';   // liefert $userId
require_once __DIR__ . '/../validate_lib.php';
require_once __DIR__ . '/../diensttag_lib.php';
require_once __DIR__ . '/../spur_lib.php';

/**
 * GPX-Import (S4/A3, E-S4-18).
 *
 * POST api/gpx_import.php   JSON-Body, Header X-CSRF wie bei api/day.php
 *
 *   { day_id:     26,
 *     ziel:       'ruhe' | 'einsatz',
 *     client_ref: 'imp-' + 24 Hexzeichen,   vom Browser gezogen
 *     stuecke:    [{seq_from, punkte, ts_von, ts_bis, huelle}, …] }
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
 * SEIT S11-05d LIEST DER BROWSER DIE DATEI (`assets/gpx.js`) und schickt
 * die Punkte als verschlossene Stuecke (SPUR2) — wie jede andere Spur seit
 * S11-04. Bis Web 22.2.0 las der Server die Zeichenkette (`gpx_lesen()`)
 * und legte die Punkte im Klartext ab; die Begruendung damals war, eine
 * GPX-Datei enthalte nichts Verschluesseltes. Seit Schritt 12a ist die Spur
 * selbst das, was verschluesselt wird (R78), und der Server darf sie nicht
 * mehr sehen. DER PREIS: Die Ablehnungsregeln (`time` ist Pflicht, kein
 * DOCTYPE, Koordinaten im Bereich) stehen jetzt im Browser und sind damit
 * eine Bequemlichkeit, keine Schranke. Was der Server noch haelt, ist die
 * Form der Stuecke (`pruef_spurstueck()`), die Menge und die Kennung — eine
 * Hülle, die sich nicht oeffnen laesst, schadet nur dem eigenen Konto.
 *
 * DER CLIENT_REF KOMMT VOM BROWSER, weil die Huellen an ihn gebunden sind
 * (Bindung art|client_ref|platz, E-S11-08) und der Browser verschliesst,
 * bevor er fragt. Der Server prueft Form und Eindeutigkeit.
 */

try {
    api_methode();
    csrf_check();   // Feld ODER Kopfzeile X-CSRF (Nr. 67)
    $b = api_rumpf();

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

    /* ---- Kennung und Stuecke ------------------------------------------- */
    $ref = (string)($b['client_ref'] ?? '');
    if (!preg_match('/^imp-[0-9a-f]{24}$/', $ref)) {
        json_out(['error' => 'eingabe', 'meldung' => 'Die Kennung der Aufzeichnung fehlt.'], 400);
    }
    $roh = $b['stuecke'] ?? null;
    if (!is_array($roh) || !$roh || count($roh) > LIMIT_TRACKPUNKTE_SPUR) {
        json_out(['error' => 'eingabe', 'meldung' => 'Die GPS-Daten kamen nicht vollständig an.'], 400);
    }
    $stuecke = []; $summe = 0; $vonTs = PHP_INT_MAX; $bisTs = 0;
    foreach (array_values($roh) as $i => $t) {
        $st = pruef_spurstueck($t, 'stuecke[' . $i . ']', $pruef);
        if ($st === null) {
            json_out(['error' => 'eingabe', 'meldung' => 'Ein Stück der GPS-Daten ist unbrauchbar: '
                      . ($pruef->text() ?: 'Stück unvollständig') . '. Es wurde nichts übernommen.'], 400);
        }
        $summe += $st['punkte'];
        $vonTs = min($vonTs, $st['ts_von']);
        $bisTs = max($bisTs, $st['ts_bis']);
        $stuecke[] = $st;
    }
    if ($summe > LIMIT_TRACKPUNKTE_SPUR) {
        json_out(['error' => 'gpx', 'meldung' => 'Die Datei hat mehr als '
                  . LIMIT_TRACKPUNKTE_SPUR . ' Punkte.'], 422);
    }
    $von = gmdate('Y-m-d H:i:s', $vonTs);
    $bis = gmdate('Y-m-d H:i:s', $bisTs);

    /* ---- Und jetzt in EINEM Zug ------------------------------------------ */
    try {
        [$id, $typ] = db_transaktion($pdo, function (PDO $pdo) use ($userId, $ziel, $dayId,
                                                                    $von, $bis, $ref,
                                                                    $stuecke): array {
        $devId = geraet_virtuell_sicherstellen($pdo, $userId);
        /* `imp-` WIE BEIM UEBRIGEN IMPORT (E-S4-18). Daran haengt die
         * Sperrliste: Ein geloeschter Eintrag mit dieser Kennung wird von
         * `ingest.php` nicht wieder angelegt (`deleted_refs`). Doppelt
         * vergeben ist sie nur, wenn derselbe Ruf zweimal kommt — dann
         * lieber nein als zweimal dieselbe Spur. */
        $tabelle = $ziel === 'einsatz' ? 'missions' : 'rest_segments';
        $dop = $pdo->prepare('SELECT 1 FROM ' . $tabelle . ' WHERE user_id = ? AND client_ref = ?');
        $dop->execute([$userId, $ref]);
        if ($dop->fetchColumn()) {
            throw new DomainException('doppelt');
        }

        if ($ziel === 'einsatz') {
            $pdo->prepare("INSERT INTO missions
                             (user_id, device_id, client_ref, day_id, started_at,
                              ended_at, final, uhr_gesperrt, origin)
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

        /* ALS STUECKE (SPUR2, S11-05d). Bis Web 22.2.0 ein SPUR1-Blob im
         * Klartext; die Begruendung „fertig, also gleich verdichtet" gilt
         * weiter — Stufe 1, nichts kommt nach. */
        foreach ($stuecke as $st) {
            spur2_stueck_schreiben($pdo, $typ, $id, $st['seq_from'], $st['punkte'],
                                   $st['ts_von'], $st['ts_bis'], $st['huelle']);
        }

        /* Der Diensttag muss umschliessen, was an ihm haengt
         * (JSON-Vertrag 4.4) — dieselbe Regel wie beim Nachtragen und beim
         * Schneiden. */
        dt_zeitraum_fortschreiben($pdo, $dayId, $von, $bis);
        return [$id, $typ];
        });
    } catch (DomainException $ex) {
        json_out(['error' => 'doppelt',
                  'meldung' => 'Diese Datei ist schon übernommen.'], 409);
    } catch (Throwable $ex) {
        json_fehler($ex, 'gpx_import');
    }

    /* Hier stand bis Web 22.2.0 `compute_site_elevation()`; die Hoehe des
     * Einsatzorts entsteht seit S11-05b im Browser. Was nicht mitkam
     * (Punkte ohne Zeit, unbrauchbare Koordinaten), nennt jetzt der Browser
     * — er hat die Datei gelesen. */
    $antwort = ['ok' => true, 'art' => $typ, 'id' => $id, 'punkte' => $summe,
                'von' => fmt_local($von), 'bis' => fmt_local($bis)];
    if (!$pruef->sauber()) { $antwort['rejected'] = $pruef->nachUrsache(); }
    json_out($antwort);
} catch (Throwable $ex) {
    json_fehler($ex, 'gpx_import');
}
