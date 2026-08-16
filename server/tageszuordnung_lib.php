<?php
declare(strict_types=1);
/**
 * Tages- und Einsatzzuordnung korrigieren (Block A5, Web 5.6.0).
 *
 * Zwei Handlungen, die auf den ersten Blick dasselbe tun und es ausdruecklich
 * nicht tun:
 *
 *   tz_einsatz_verschieben()  Ein Einsatz gehoert zum falschen Tag. Seine
 *                             UHRZEITEN STIMMEN — nur die Zuordnung nicht.
 *                             Beispiel: ein Dienst ueber Mitternacht, bei dem
 *                             ein Einsatz beim nachtraeglichen Erfassen auf dem
 *                             falschen Kalendertag gelandet ist.
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
 */

require_once __DIR__ . '/db.php';

/**
 * Zustand eines Kalendertages fuer diese NutzerIn.
 *
 * @return array{vorhanden:bool,im_papierkorb:bool,einsaetze:int,segmente:int}
 *   'vorhanden'      es gibt eine Zeile in `days`
 *   'im_papierkorb'  diese Zeile ist geloescht (deleted_at gesetzt)
 *   'einsaetze'      Einsaetze auf diesem Tag, Papierkorb eingeschlossen
 *   'segmente'       Ruhesegmente ebenso
 *
 * Der Papierkorb zaehlt mit, und zwar aus einem harten Grund: `days` traegt
 * `UNIQUE KEY uq_user_day (user_id, day)`. Ein Tag im Papierkorb belegt sein
 * Datum weiterhin. Wer ihn beim Pruefen uebergeht, laeuft in einen
 * Datenbankfehler statt in eine lesbare Meldung.
 */
function tz_tag_zustand(int $userId, string $tag): array
{
    $q = db()->prepare('SELECT deleted_at FROM days WHERE user_id = ? AND day = ?');
    $q->execute([$userId, $tag]);
    $zeile = $q->fetch();

    $zaehle = function (string $tabelle) use ($userId, $tag): int {
        $s = db()->prepare("SELECT COUNT(*) FROM `$tabelle` WHERE user_id = ? AND day = ?");
        $s->execute([$userId, $tag]);
        return (int)$s->fetchColumn();
    };

    return [
        'vorhanden'     => $zeile !== false,
        'im_papierkorb' => $zeile !== false && $zeile['deleted_at'] !== null,
        'einsaetze'     => $zaehle('missions'),
        'segmente'      => $zaehle('rest_segments'),
    ];
}

/**
 * Umfang eines Tages fuer die Rueckfrage (Akzeptanzkriterium 33).
 *
 * Papierkorb-Eintraege werden GETRENNT gezaehlt, nicht weggelassen: Sie wandern
 * mit (siehe tz_tag_datum_aendern()), also muessen sie in der Rueckfrage
 * vorkommen — sonst nennt sie eine kleinere Zahl, als sie anfasst.
 *
 * @return array{einsaetze:int,segmente:int,einsaetze_papierkorb:int,
 *               segmente_papierkorb:int,punkte:int}
 */
function tz_tag_umfang(int $userId, string $tag): array
{
    $one = function (string $sql, array $p): int {
        $s = db()->prepare($sql); $s->execute($p); return (int)$s->fetchColumn();
    };
    $ids = function (string $tabelle) use ($userId, $tag): array {
        $s = db()->prepare("SELECT id, deleted_at FROM `$tabelle` WHERE user_id = ? AND day = ?");
        $s->execute([$userId, $tag]);
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
 * Sorgt dafuer, dass es den Zieltag gibt (E14).
 *
 * Fehlt er, wird er angelegt — mit der Standard-Vorbelegung fuer Standort und
 * Maschine aus `user_defaults`. Alles andere zwaenge dazu, vor dem Verschieben
 * einen Tag von Hand anzulegen: ein Umweg ohne Nutzen.
 *
 * Liegt der Zieltag im Papierkorb, wird NICHT angelegt und nicht still
 * wiederhergestellt, sondern eine Meldung zurueckgegeben — dieselbe Haltung wie
 * in api/day.php: Das Loeschen war eine bewusste Handlung, und sie durch eine
 * unsichtbare Nebenwirkung aufzuheben waere eine Ueberraschung.
 *
 * @return string|null Meldung im Fehlerfall, sonst null
 */
function tz_zieltag_sichern(PDO $pdo, int $userId, string $tag): ?string
{
    $q = $pdo->prepare('SELECT deleted_at FROM days WHERE user_id = ? AND day = ?');
    $q->execute([$userId, $tag]);
    $zeile = $q->fetch();

    if ($zeile !== false) {
        if ($zeile['deleted_at'] !== null) {
            return 'Der Zieltag ' . tz_datum_lesbar($tag) . ' liegt im Papierkorb. '
                 . 'Bitte ihn zuerst wiederherstellen (Einstellungen → Papierkorb) '
                 . 'oder ein anderes Datum wählen. Es wurde nichts geändert.';
        }
        return null;   // vorhanden und in Ordnung
    }

    // Standard-Vorbelegung wie beim manuellen Anlegen (E14)
    $d = $pdo->prepare('SELECT kind, item_id FROM user_defaults WHERE user_id = ?');
    $d->execute([$userId]);
    $ac = null; $base = null;
    foreach ($d->fetchAll() as $z) {
        if ($z['kind'] === 'aircraft') { $ac   = (int)$z['item_id']; }
        if ($z['kind'] === 'base')     { $base = (int)$z['item_id']; }
    }
    $pdo->prepare('INSERT INTO days (user_id, day, aircraft_id, base_id) VALUES (?,?,?,?)')
        ->execute([$userId, $tag, $ac, $base]);
    return null;
}

/** 'YYYY-MM-DD' -> 'TT.MM.JJJJ'; unveraendert, wenn das Muster nicht passt. */
function tz_datum_lesbar(string $tag): string
{
    return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tag, $t)
        ? "$t[3].$t[2].$t[1]" : $tag;
}

/**
 * Einen einzelnen Einsatz einem anderen Tag zuordnen (A5.2).
 *
 * Die UHRZEITEN BLEIBEN UNVERAENDERT — das ist der Unterschied zu
 * tz_tag_datum_aendern(). Hier wird eine Fehlzuordnung korrigiert, dort eine
 * falsch gestellte Uhr.
 *
 * Datenseitig unkritisch: `ingest.php` fuehrt beim Upsert ein
 * `ON DUPLICATE KEY UPDATE`, das die Spalte `day` NICHT mitschreibt. Eine
 * nachliefernde Uhr zieht einen verschobenen Einsatz also nicht zurueck
 * (Akzeptanzkriterium 28).
 *
 * @return array{ok:bool,meldung:string,tag:string}
 */
function tz_einsatz_verschieben(int $userId, int $missionId, string $zielTag): array
{
    $pdo = db();
    $mq = $pdo->prepare('SELECT day FROM missions
                         WHERE id = ? AND user_id = ? AND deleted_at IS NULL');   // Datentrennung!
    $mq->execute([$missionId, $userId]);
    $altTag = $mq->fetchColumn();
    if ($altTag === false) {
        return ['ok' => false, 'meldung' => 'Einsatz nicht gefunden.', 'tag' => ''];
    }
    if ($zielTag === (string)$altTag) {
        return ['ok' => false, 'tag' => (string)$altTag,
                'meldung' => 'Der Einsatz liegt bereits auf dem '
                           . tz_datum_lesbar($zielTag) . '. Es wurde nichts geändert.'];
    }

    $pdo->beginTransaction();
    try {
        $fehler = tz_zieltag_sichern($pdo, $userId, $zielTag);
        if ($fehler !== null) {
            $pdo->rollBack();
            return ['ok' => false, 'meldung' => $fehler, 'tag' => (string)$altTag];
        }
        $pdo->prepare('UPDATE missions SET day = ? WHERE id = ? AND user_id = ?')
            ->execute([$zielTag, $missionId, $userId]);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return ['ok' => false, 'tag' => (string)$altTag,
                'meldung' => 'Verschieben fehlgeschlagen. Es wurde nichts geändert.'];
    }

    return ['ok' => true, 'tag' => $zielTag,
            'meldung' => 'Der Einsatz gehört jetzt zum ' . tz_datum_lesbar($zielTag)
                       . '. Die Uhrzeiten sind unverändert geblieben.'];
}

/**
 * Das Datum eines ganzen Einsatztages aendern (A5.3).
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
 * PAPIERKORB-EINTRAEGE WANDERN MIT. Sie haengen ueber den natuerlichen
 * Schluessel (user_id, day) am Tag; blieben sie liegen, kaemen sie beim
 * Wiederherstellen an einem Datum zurueck, das es nicht mehr gibt.
 *
 * @return array{ok:bool,meldung:string}
 */
function tz_tag_datum_aendern(int $userId, string $altTag, string $neuTag): array
{
    global $CFG;

    if ($altTag === $neuTag) {
        return ['ok' => false, 'meldung' => 'Das Datum ist unverändert. Es wurde nichts geändert.'];
    }

    // Kollision (E2): Ein belegtes Zieldatum wird abgelehnt, nicht
    // zusammengefuehrt. Zusammenfuehren wuerfe Fragen zu widerspruechlichen
    // Tages-Metadaten auf (Standort, Maschine, Besatzung), die sich nicht
    // automatisch beantworten lassen.
    $ziel = tz_tag_zustand($userId, $neuTag);
    if ($ziel['vorhanden'] || $ziel['einsaetze'] > 0 || $ziel['segmente'] > 0) {
        $anzahl = fn(int $n, string $eins, string $viele): string
            => $n . ' ' . ($n === 1 ? $eins : $viele);
        $was = [];
        if ($ziel['im_papierkorb'])   { $was[] = 'ein Einsatztag im Papierkorb'; }
        elseif ($ziel['vorhanden'])   { $was[] = 'ein Einsatztag'; }
        if ($ziel['einsaetze'] > 0)   { $was[] = $anzahl($ziel['einsaetze'], 'Einsatz', 'Einsätze'); }
        if ($ziel['segmente'] > 0)    { $was[] = $anzahl($ziel['segmente'], 'Ruhesegment', 'Ruhesegmente'); }
        // Aufzählung mit Komma und einem abschließenden „und" — bei drei
        // Bestandteilen las sich das dreifache „und" wie ein Fehler.
        $letztes = array_pop($was);
        $text = $was ? implode(', ', $was) . ' und ' . $letztes : $letztes;
        return ['ok' => false,
                'meldung' => 'Am ' . tz_datum_lesbar($neuTag) . ' liegt bereits '
                           . $text . '. Zwei Einsatztage lassen sich '
                           . 'nicht zusammenführen — bitte ein freies Datum wählen '
                           . 'oder den vorhandenen Tag zuerst auflösen. '
                           . 'Es wurde nichts geändert.'];
    }

    $tz  = new DateTimeZone($CFG['app']['timezone'] ?? 'Europe/Berlin');
    $von = new DateTime($altTag . ' 00:00:00', $tz);
    $bis = new DateTime($neuTag . ' 00:00:00', $tz);
    $delta = $bis->getTimestamp() - $von->getTimestamp();   // Sekunden, vorzeichenbehaftet

    $pdo = db();

    // Die Kennungen VOR der Umstellung holen: Danach steht der alte Tag nicht
    // mehr in den Zeilen, und track_points kennt ohnehin nur owner_id.
    $holeIds = function (string $tabelle) use ($pdo, $userId, $altTag): array {
        $s = $pdo->prepare("SELECT id FROM `$tabelle` WHERE user_id = ? AND day = ?");
        $s->execute([$userId, $altTag]);
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
        // 1. Der Tag selbst. Auch ein Tag im Papierkorb wandert mit — sonst
        //    kaeme er beim Wiederherstellen an einem Datum zurueck, an dem
        //    seine Einsaetze nicht mehr liegen.
        $pdo->prepare('UPDATE days SET day = ? WHERE user_id = ? AND day = ?')
            ->execute([$neuTag, $userId, $altTag]);

        // 2. Einsaetze und Ruhesegmente: Tag und eigene Zeitstempel
        $pdo->prepare('UPDATE missions
                       SET day = ?,
                           started_at = DATE_ADD(started_at, INTERVAL ? SECOND),
                           ended_at   = DATE_ADD(ended_at,   INTERVAL ? SECOND)
                       WHERE user_id = ? AND day = ?')
            ->execute([$neuTag, $delta, $delta, $userId, $altTag]);
        $pdo->prepare('UPDATE rest_segments
                       SET day = ?,
                           started_at = DATE_ADD(started_at, INTERVAL ? SECOND),
                           ended_at   = DATE_ADD(ended_at,   INTERVAL ? SECOND)
                       WHERE user_id = ? AND day = ?')
            ->execute([$neuTag, $delta, $delta, $userId, $altTag]);

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
                           . 'geändert — der Tag steht unverändert am '
                           . tz_datum_lesbar($altTag) . '.'];
    }

    $stunden = intdiv(abs($delta), 3600);
    $tage    = intdiv($stunden, 24);
    return ['ok' => true,
            'meldung' => 'Der Einsatztag liegt jetzt am ' . tz_datum_lesbar($neuTag)
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
