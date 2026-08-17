<?php
declare(strict_types=1);
/**
 * Tages- und Einsatzzuordnung korrigieren (Block A5, Web 5.6.0).
 *
 * Zwei Handlungen, die auf den ersten Blick dasselbe tun und es ausdruecklich
 * nicht tun:
 *
 *   tz_einsatz_verschieben()  Ein Einsatz gehoert zum falschen Diensttag. Seine
 *                             UHRZEITEN STIMMEN — nur die Zuordnung nicht.
 *                             Beispiel: ein Dienst ueber Mitternacht, bei dem
 *                             ein Einsatz beim nachtraeglichen Erfassen am
 *                             falschen Tag gelandet ist.
 *   tz_tag_datum_aendern()    Der ganze Tag liegt falsch, weil die UHR FALSCH
 *                             GESTELLT war. Dann sind Datum UND Uhrzeit falsch,
 *                             und die Zeitstempel ziehen mit (E3).
 *
 * Wer die beiden verwechselt, verschiebt Zeitstempel, die richtig waren. Die
 * Oberflaeche benennt den Unterschied deshalb ausdruecklich, und die beiden
 * Funktionen stehen hier nebeneinander, damit er beim Lesen sichtbar bleibt.
 *
 * WARUM EINE EIGENE DATEI. Beide Handlungen schreiben ueber mehrere Tabellen
 * in einer Transaktion. In einer Seitendatei stuende die Logik zwischen
 * Markup — und die Pruefung, ob wirklich alles oder gar nichts geschieht,
 * liesse sich nur ueber die Seite fuehren.
 *
 * WAS SICH MIT WEB 6.0.0 GEAENDERT HAT. Beide Handlungen arbeiteten mit dem
 * DATUM als Schluessel. Seit dem Umbau auf Diensttage ist der Schluessel die
 * Kennung `days.id`, und das Datum ist nur noch Sortier- und Anzeigewert.
 * Drei Dinge sind dadurch entfallen:
 *
 *   - Das Anlegen eines fehlenden Zieltags (tz_zieltag_sichern). Der Zieltag
 *     wird jetzt aus einer Liste vorhandener Diensttage gewaehlt; ein Datum
 *     ohne Tag gibt es als Ziel nicht mehr.
 *   - Die Kollisionspruefung beim Umdatieren. Sie entstand allein aus
 *     `UNIQUE KEY uq_user_day`; mehrere Diensttage an einem Kalendertag sind
 *     jetzt ausdruecklich zulaessig (E9, A1).
 *   - Das Mitwandern von Papierkorb-Eintraegen als eigene Regel. Sie haengen
 *     ueber `day_id` am Tag und wandern zwangslaeufig mit.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/diensttag_lib.php';

/**
 * Umfang eines Diensttages fuer die Rueckfrage (Akzeptanzkriterium 33).
 *
 * Papierkorb-Eintraege werden GETRENNT gezaehlt, nicht weggelassen: Sie wandern
 * mit (siehe tz_tag_datum_aendern()), also muessen sie in der Rueckfrage
 * vorkommen — sonst nennt sie eine kleinere Zahl, als sie anfasst.
 *
 * @return array{einsaetze:int,segmente:int,einsaetze_papierkorb:int,
 *               segmente_papierkorb:int,punkte:int}
 */
function tz_tag_umfang(int $userId, int $dayId): array
{
    $one = function (string $sql, array $p): int {
        $s = db()->prepare($sql); $s->execute($p); return (int)$s->fetchColumn();
    };
    $ids = function (string $tabelle) use ($userId, $dayId): array {
        $s = db()->prepare("SELECT id, deleted_at FROM `$tabelle` WHERE user_id = ? AND day_id = ?");
        $s->execute([$userId, $dayId]);
        return $s->fetchAll();
    };

    $m = $ids('missions');
    $r = $ids('rest_segments');

    $punkte = 0;
    foreach ($m as $z) {
        $punkte += $one("SELECT COUNT(*) FROM track_points
                         WHERE owner_type = 'mission' AND owner_id = ?", [(int)$z['id']]);
    }
    foreach ($r as $z) {
        $punkte += $one("SELECT COUNT(*) FROM track_points
                         WHERE owner_type = 'rest' AND owner_id = ?", [(int)$z['id']]);
    }

    $offen = fn(array $liste): int => count(array_filter($liste, fn($z) => $z['deleted_at'] === null));

    return [
        'einsaetze'            => $offen($m),
        'segmente'             => $offen($r),
        'einsaetze_papierkorb' => count($m) - $offen($m),
        'segmente_papierkorb'  => count($r) - $offen($r),
        'punkte'               => $punkte,
    ];
}

/**
 * Einen einzelnen Einsatz einem anderen Diensttag zuordnen (A5.2).
 *
 * Die UHRZEITEN BLEIBEN UNVERAENDERT — das ist der Unterschied zu
 * tz_tag_datum_aendern(). Hier wird eine Fehlzuordnung korrigiert, dort eine
 * falsch gestellte Uhr.
 *
 * Datenseitig unkritisch: `ingest.php` fuehrt beim Upsert ein
 * `ON DUPLICATE KEY UPDATE`, das die Spalte `day_id` NICHT mitschreibt. Eine
 * nachliefernde Uhr zieht einen verschobenen Einsatz also nicht zurueck
 * (Akzeptanzkriterium 28).
 *
 * DER ZIELTAG WIRD NICHT ANGELEGT. Er wird aus den vorhandenen Diensttagen
 * gewaehlt — ein Datum allein bestimmt seit E9 keinen Tag mehr, und einen
 * neuen anzulegen waere beim Verschieben eine Ueberraschung.
 *
 * @return array{ok:bool,meldung:string,day_id:int}
 */
function tz_einsatz_verschieben(int $userId, int $missionId, int $zielDayId): array
{
    $pdo = db();
    $mq = $pdo->prepare('SELECT day_id FROM missions
                         WHERE id = ? AND user_id = ? AND deleted_at IS NULL');   // Datentrennung!
    $mq->execute([$missionId, $userId]);
    $altId = $mq->fetchColumn();
    if ($altId === false) {
        return ['ok' => false, 'meldung' => 'Einsatz nicht gefunden.', 'day_id' => 0];
    }
    $altId = $altId === null ? 0 : (int)$altId;

    $ziel = dt_laden($userId, $zielDayId, true);
    if ($ziel === null) {
        return ['ok' => false, 'day_id' => $altId,
                'meldung' => 'Der gewählte Diensttag ist nicht vorhanden. '
                           . 'Es wurde nichts geändert.'];
    }
    if ($ziel['deleted_at'] !== null) {
        return ['ok' => false, 'day_id' => $altId,
                'meldung' => 'Der Zieldiensttag liegt im Papierkorb. Bitte ihn zuerst '
                           . 'wiederherstellen (Einstellungen → Papierkorb) oder einen '
                           . 'anderen wählen. Es wurde nichts geändert.'];
    }
    if ($zielDayId === $altId) {
        return ['ok' => false, 'day_id' => $altId,
                'meldung' => 'Der Einsatz gehört bereits zum Diensttag '
                           . dt_lesbar($ziel, true) . '. Es wurde nichts geändert.'];
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE missions SET day_id = ? WHERE id = ? AND user_id = ?')
            ->execute([$zielDayId, $missionId, $userId]);
        /* Der Zieltag muss den Einsatz umschliessen. Sonst stuende ein Einsatz
         * ausserhalb des Zeitraums seines eigenen Dienstes — und die Statistik,
         * die nach Diensttag rechnet, haette einen Tag, dessen Ende vor seinem
         * letzten Einsatz liegt. */
        $z = $pdo->prepare('SELECT started_at, ended_at FROM missions WHERE id = ?');
        $z->execute([$missionId]);
        $m = $z->fetch();
        dt_zeitraum_fortschreiben($pdo, $zielDayId, $m['started_at'] ?? null,
                                  $m['ended_at'] ?? null);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['ok' => false, 'day_id' => $altId,
                'meldung' => 'Verschieben fehlgeschlagen. Es wurde nichts geändert.'];
    }

    return ['ok' => true, 'day_id' => $zielDayId,
            'meldung' => 'Der Einsatz gehört jetzt zum Diensttag '
                       . dt_lesbar($ziel, true)
                       . '. Die Uhrzeiten sind unverändert geblieben.'];
}

/**
 * Das Datum eines ganzen Diensttages aendern (A5.3).
 *
 * ALLES ODER NICHTS. Die Handlung fasst sechs Tabellen an; ein Abbruch in der
 * Mitte hinterliesse einen Bestand, in dem Einsaetze und ihre Phasen an
 * verschiedenen Tagen liegen. Die Transaktion ist deshalb nicht optional.
 *
 * DIE ZEITSTEMPEL ZIEHEN MIT (E3). Der Anwendungsfall ist die falsch gestellte
 * Uhr — dabei sind Datum UND Uhrzeit falsch.
 *
 * Verschoben wird um die Differenz der beiden ORTSMITTERNACHTEN, nicht um
 * `tage * 86400` Sekunden. Der Unterschied wird genau dann sichtbar, wenn die
 * Verschiebung ueber den Wechsel von Sommer- auf Winterzeit laeuft: Eine feste
 * Sekundenzahl verschoebe dann jede dokumentierte Uhrzeit um eine Stunde. Der
 * Abstand der Ortsmitternachte ist an dieser einen Stelle um genau diese Stunde
 * groesser oder kleiner und haelt die Ortszeit fest — und die Ortszeit ist das,
 * was jemand abgelesen und dokumentiert hat.
 *
 * KEINE KOLLISIONSPRUEFUNG MEHR (Web 6.0.0). Sie war die Folge des
 * Tagesschluessels `UNIQUE KEY uq_user_day`: Zwei Flugtage an einem Datum waren
 * ein Datenbankfehler, also musste die Umdatierung ein belegtes Zieldatum
 * ablehnen. Seit E9 sind mehrere Diensttage je Kalendertag der vorgesehene Fall
 * (A1) — es gibt nichts mehr zu kollidieren.
 *
 * @return array{ok:bool,meldung:string}
 */
function tz_tag_datum_aendern(int $userId, int $dayId, string $neuTag): array
{
    global $CFG;

    $tag = dt_laden($userId, $dayId);
    if ($tag === null) {
        return ['ok' => false, 'meldung' => 'Diensttag nicht gefunden. Es wurde nichts geändert.'];
    }
    $altTag = (string)$tag['day'];
    if ($altTag === $neuTag) {
        return ['ok' => false, 'meldung' => 'Das Datum ist unverändert. Es wurde nichts geändert.'];
    }

    $tz  = new DateTimeZone($CFG['app']['timezone'] ?? 'Europe/Berlin');
    $von = new DateTime($altTag . ' 00:00:00', $tz);
    $bis = new DateTime($neuTag . ' 00:00:00', $tz);
    $delta = $bis->getTimestamp() - $von->getTimestamp();   // Sekunden, vorzeichenbehaftet

    $pdo = db();

    // Die Kennungen VOR der Umstellung holen: track_points kennt nur owner_id,
    // und die Blockabfragen unten brauchen die Liste ohnehin zweimal.
    $holeIds = function (string $tabelle) use ($pdo, $userId, $dayId): array {
        $s = $pdo->prepare("SELECT id FROM `$tabelle` WHERE user_id = ? AND day_id = ?");
        $s->execute([$userId, $dayId]);
        return array_map('intval', $s->fetchAll(PDO::FETCH_COLUMN));
    };
    $mIds = $holeIds('missions');
    $rIds = $holeIds('rest_segments');

    /* Spurpunkte tragen die Unix-Epoche in einer VORZEICHENLOSEN Spalte. Eine
     * Rueckwaerts-Verschiebung unter null waere ein Datenbankfehler mitten in
     * der Transaktion — hier wird sie vorher benannt. Bei echten Zeitstempeln
     * (rund 1,7 Milliarden) kann das nur ein Altbestand mit Unsinn ausloesen;
     * genau der soll aber nicht als "Verschieben fehlgeschlagen" erscheinen. */
    if ($delta < 0 && ($mIds || $rIds)) {
        $min = null;
        foreach ([['mission', $mIds], ['rest', $rIds]] as [$typ, $ids]) {
            foreach ($ids as $id) {
                $s = $pdo->prepare('SELECT MIN(ts) FROM track_points
                                    WHERE owner_type = ? AND owner_id = ?');
                $s->execute([$typ, $id]);
                $w = $s->fetchColumn();
                if ($w !== null && $w !== false && ($min === null || (int)$w < $min)) {
                    $min = (int)$w;
                }
            }
        }
        if ($min !== null && $min + $delta <= 0) {
            return ['ok' => false,
                    'meldung' => 'Die Verschiebung würde Spurpunkte vor den 1.1.1970 '
                               . 'zurückdatieren. Das deutet auf fehlerhafte Zeitstempel '
                               . 'im Bestand hin. Es wurde nichts geändert.'];
        }
    }

    $pdo->beginTransaction();
    try {
        /* 1. Der Diensttag selbst — Datum UND eigener Zeitraum. `started_at`
         *    und `ended_at` sind echte Zeitstempel und gehoeren damit zu dem,
         *    was bei falsch gestellter Uhr mitwandert. Blieben sie stehen,
         *    laege der Dienst nach der Umdatierung an einem Datum, das seine
         *    eigenen Zeiten nicht mehr enthaelt. */
        $pdo->prepare('UPDATE days
                       SET day = ?,
                           started_at = DATE_ADD(started_at, INTERVAL ? SECOND),
                           ended_at   = DATE_ADD(ended_at,   INTERVAL ? SECOND)
                       WHERE id = ? AND user_id = ?')
            ->execute([$neuTag, $delta, $delta, $dayId, $userId]);

        // 2. Einsaetze und Ruhesegmente. Sie haengen ueber day_id am Tag und
        //    wandern deshalb zwangslaeufig mit — auch die im Papierkorb.
        $pdo->prepare('UPDATE missions
                       SET started_at = DATE_ADD(started_at, INTERVAL ? SECOND),
                           ended_at   = DATE_ADD(ended_at,   INTERVAL ? SECOND)
                       WHERE user_id = ? AND day_id = ?')
            ->execute([$delta, $delta, $userId, $dayId]);
        $pdo->prepare('UPDATE rest_segments
                       SET started_at = DATE_ADD(started_at, INTERVAL ? SECOND),
                           ended_at   = DATE_ADD(ended_at,   INTERVAL ? SECOND)
                       WHERE user_id = ? AND day_id = ?')
            ->execute([$delta, $delta, $userId, $dayId]);

        // 3. Alles, was an einem Einsatz haengt. In Bloecken, damit die Zahl
        //    der Abfragen nicht mit der Zahl der Einsaetze waechst.
        if ($mIds) {
            foreach (sql_in_bloecke_sql($mIds) as [$platzhalter, $werte]) {
                $pdo->prepare("UPDATE mission_phases
                               SET occurred_at = DATE_ADD(occurred_at, INTERVAL ? SECOND)
                               WHERE mission_id IN ($platzhalter)")
                    ->execute(array_merge([$delta], $werte));
                $pdo->prepare("UPDATE resus_events e
                               JOIN resus_sessions s ON s.id = e.session_id
                               SET e.occurred_at = DATE_ADD(e.occurred_at, INTERVAL ? SECOND)
                               WHERE s.mission_id IN ($platzhalter)")
                    ->execute(array_merge([$delta], $werte));
                $pdo->prepare("UPDATE resus_sessions
                               SET started_at = DATE_ADD(started_at, INTERVAL ? SECOND)
                               WHERE mission_id IN ($platzhalter)")
                    ->execute(array_merge([$delta], $werte));
                $pdo->prepare("UPDATE track_points SET ts = ts + ?
                               WHERE owner_type = 'mission' AND owner_id IN ($platzhalter)")
                    ->execute(array_merge([$delta], $werte));
            }
        }
        // 4. Spurpunkte der Ruhesegmente. Leicht zu uebersehen: Sie haengen
        //    nicht an einem Einsatz und tragen die Epoche, kein DATETIME.
        if ($rIds) {
            foreach (sql_in_bloecke_sql($rIds) as [$platzhalter, $werte]) {
                $pdo->prepare("UPDATE track_points SET ts = ts + ?
                               WHERE owner_type = 'rest' AND owner_id IN ($platzhalter)")
                    ->execute(array_merge([$delta], $werte));
            }
        }

        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['ok' => false,
                'meldung' => 'Die Umdatierung ist fehlgeschlagen. Es wurde nichts '
                           . 'geändert — der Diensttag steht unverändert am '
                           . dt_datum_lesbar($altTag) . '.'];
    }

    $stunden = intdiv(abs($delta), 3600);
    $tage    = intdiv($stunden, 24);
    return ['ok' => true,
            'meldung' => 'Der Diensttag liegt jetzt am ' . dt_datum_lesbar($neuTag)
                       . '. Alle Zeitstempel sind um ' . $tage . ' '
                       . ($tage === 1 ? 'Tag' : 'Tage')
                       . ($delta < 0 ? ' zurück' : ' vor') . 'verschoben worden; '
                       . 'die Uhrzeiten stehen unverändert da.'];
}

/**
 * Kennungsliste in Bloecke zerlegen und je Block Platzhalter liefern.
 *
 * Gegenstueck zu sql_in_bloecken() in db.php, das die Ergebnisse einer ABFRAGE
 * zusammenfuehrt. Hier werden Aenderungen ausgefuehrt, es gibt nichts
 * zusammenzufuehren — gebraucht wird nur die Zerlegung.
 *
 * @return list<array{0:string,1:list<int>}>
 */
function sql_in_bloecke_sql(array $ids, int $proBlock = 500): array
{
    $bloecke = [];
    foreach (array_chunk(array_values($ids), $proBlock) as $teil) {
        $bloecke[] = [implode(',', array_fill(0, count($teil), '?')), $teil];
    }
    return $bloecke;
}
