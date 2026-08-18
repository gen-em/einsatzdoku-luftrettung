<?php
declare(strict_types=1);
/**
 * Diensttage: anlegen, zuordnen, einfrieren, auflisten (Web 6.0.0).
 *
 * WARUM DIESE DATEI ES GIBT. Bis Web 5.10.0 war ein Flugtag ein KALENDERTAG:
 * `days` trug `UNIQUE KEY (user_id, day)`, und Einsaetze fanden ihren Tag ueber
 * genau dieses Paar. Ein Tag brauchte deshalb nicht angelegt zu werden — er
 * ergab sich aus dem Datum. Seit dem Umbau auf Diensttage (E9) ist das anders:
 * Jeder Dienst ist eine EIGENE ZEILE mit eigener Kennung, mehrere pro
 * Kalendertag sind zulaessig, und `missions.day_id` ist ein Fremdschluessel.
 * Damit wird das Anlegen zu einer echten Handlung mit Folgen — und die Folgen
 * stehen hier an einer Stelle, statt in sechs Seitendateien verstreut.
 *
 * DIE EINE REGEL, DIE ALLES ERKLAERT (E8): Alles, was ein Diensttag aus
 * Standort und Rettungsmittel ableitet — Art, Rollensatz, Faehigkeiten,
 * Bezeichnungen, Standortkoordinaten — wird beim Zuordnen KOPIERT.
 * `base_id`/`vehicle_id` bleiben daneben stehen, aber nur zum Filtern und
 * Auswerten, NIEMALS fuer die Anzeige. Ein Diensttag ist ein abgeschlossener
 * Dienstnachweis, kein Blick auf den heutigen Stammdatenbestand.
 *
 * Alles in dieser Datei arbeitet mit dem uebergebenen PDO, damit die Aufrufer
 * ihre Transaktionsgrenzen selbst bestimmen. Die lesenden Funktionen nehmen
 * db() selbst.
 */

require_once __DIR__ . '/db.php';

/* ---- Lesen ---------------------------------------------------------------- */

/**
 * Einen Diensttag laden — nur den eigenen (Datentrennung).
 *
 * $mitPapierkorb === false laesst geloeschte Tage aus. Das ist der Regelfall;
 * die Ausnahmen sind der Papierkorb selbst und die Pruefungen, die einen Tag im
 * Papierkorb ausdruecklich BENENNEN wollen statt ihn als "nicht vorhanden"
 * erscheinen zu lassen (siehe api/day.php).
 */
function dt_laden(int $userId, int $dayId, bool $mitPapierkorb = false): ?array
{
    $sql = 'SELECT * FROM days WHERE id = ? AND user_id = ?';
    if (!$mitPapierkorb) { $sql .= ' AND deleted_at IS NULL'; }
    $q = db()->prepare($sql);
    $q->execute([$dayId, $userId]);
    return $q->fetch() ?: null;
}

/**
 * Besatzung eines Diensttags: role_code => ?name, in KATALOGREIHENFOLGE.
 *
 * Die Zeilenmenge ist der eingefrorene Rollensatz (E8): Welche Rollen ein
 * Diensttag anbietet, sagt diese Tabelle — nicht das Rettungsmittel. Ein
 * neutraler Diensttag hat keine Zeilen und damit keine Rollen (E26).
 *
 * Eine Rolle, die der Katalog nicht (mehr) kennt, wird ANGEHAENGT statt
 * verschwiegen: Sie steht in der Datenbank, also gehoert sie angezeigt.
 */
function dt_crew(int $dayId): array
{
    $q = db()->prepare('SELECT role_code, name FROM day_crew WHERE day_id = ?');
    $q->execute([$dayId]);
    $roh = [];
    foreach ($q->fetchAll() as $z) { $roh[(string)$z['role_code']] = $z['name']; }

    $sortiert = [];
    foreach (array_keys(CREW_ROLES) as $code) {
        if (array_key_exists($code, $roh)) { $sortiert[$code] = $roh[$code]; unset($roh[$code]); }
    }
    return $sortiert + $roh;
}

/** Eingefrorene Faehigkeiten eines Diensttags, in Katalogreihenfolge. */
function dt_faehigkeiten(int $dayId): array
{
    $q = db()->prepare('SELECT capability FROM day_capabilities WHERE day_id = ?');
    $q->execute([$dayId]);
    $vorhanden = $q->fetchAll(PDO::FETCH_COLUMN);
    return array_values(array_filter(
        array_keys(VEHICLE_CAPABILITIES),
        static fn(string $c): bool => in_array($c, $vorhanden, true)
    ));
}

/**
 * Anzeigename eines Diensttags: Datum, bei mehreren am Tag zusaetzlich die
 * Uhrzeit des Dienstbeginns.
 *
 * Die Uhrzeit erscheint NUR, wenn sie gebraucht wird ($mitZeit). Sie an jedem
 * Tag mitzufuehren machte die Seitenleiste in der Breite unbrauchbar, und im
 * Regelfall — ein Dienst am Tag — traegt sie nichts bei.
 */
function dt_lesbar(array $tag, bool $mitZeit = false): string
{
    $datum = dt_datum_lesbar((string)$tag['day']);
    if (!$mitZeit || empty($tag['started_at'])) { return $datum; }
    return $datum . ' ' . fmt_local((string)$tag['started_at']);
}

/** 'YYYY-MM-DD' -> 'TT.MM.JJJJ'; unveraendert, wenn das Muster nicht passt. */
function dt_datum_lesbar(string $tag): string
{
    return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $tag, $t)
        ? "$t[3].$t[2].$t[1]" : $tag;
}

/**
 * Alle Artsymbole samt Textalternative — die EINE Fassung im Projekt.
 *
 * Der Schluessel `''` steht fuer den neutralen Diensttag (kein Rettungsmittel,
 * E26). Die Liste geht auch an den Browser: `zeitraum.php` und `suche.php`
 * setzen sie vor `assets/missiontable.js` als `ART_SYMBOLE`, damit die
 * Einsatztabelle dieselben Zeichen fuehrt wie die Tagesleiste. Eine zweite,
 * fest verdrahtete Liste in JavaScript waere die Stelle, an der beide beim
 * naechsten Symbolwechsel auseinanderlaufen (dieselbe Ueberlegung wie bei
 * CREW_ROLES, Befund P9).
 *
 * @return array<string,array{zeichen:string,text:string}>
 */
function dt_art_symbole(): array
{
    return [
        'air'    => ['zeichen' => '🚁', 'text' => 'luftgebunden'],
        'ground' => ['zeichen' => '🚑', 'text' => 'bodengebunden'],
        ''       => ['zeichen' => '◌',  'text' => 'ohne Zuordnung'],
    ];
}

/**
 * Art eines Diensttags als Symbol mit Textalternative (E27, A7c).
 *
 * In der Tagesleiste ist die Art bewusst KEINE eigene Spalte: Die Uebersichten
 * sind auf schmalen Geraeten ohnehin eng, und der Name des Rettungsmittels
 * verraet sie meist schon — das Symbol steht deshalb am Namen. Neutrale
 * Diensttage tragen ein eigenes, klar unterscheidbares Zeichen.
 *
 * Die Textalternative ist Pflicht, nicht Zierde: Ohne sie haengt die Auskunft
 * allein an der Grafik.
 *
 * @return array{zeichen:string,text:string}
 */
function dt_art_symbol(?string $kind): array
{
    $alle = dt_art_symbole();
    return $alle[(string)$kind] ?? $alle[''];
}

/**
 * Diensttage einer NutzerIn fuer Seitenleiste und Auswahllisten.
 *
 * Absteigend nach Datum und Dienstbeginn — die juengste Zeile zuerst, wie
 * bisher. `mehrfach` sagt, ob am selben Kalendertag mehr als ein Diensttag
 * liegt; nur dann muss die Anzeige die Uhrzeit dazusagen.
 */
function dt_liste(int $userId, int $limit = 500): array
{
    $q = db()->prepare('SELECT id, day, started_at, ended_at, kind, vehicle_name, base_name
                          FROM days
                         WHERE user_id = ? AND deleted_at IS NULL
                         ORDER BY day DESC, started_at DESC, id DESC
                         LIMIT ' . (int)$limit);
    $q->execute([$userId]);
    $tage = $q->fetchAll();

    $jeDatum = [];
    foreach ($tage as $t) { $jeDatum[(string)$t['day']] = ($jeDatum[(string)$t['day']] ?? 0) + 1; }
    foreach ($tage as $i => $t) {
        $tage[$i]['mehrfach'] = $jeDatum[(string)$t['day']] > 1;
    }
    return $tage;
}

/** Kennung des jüngsten Diensttags dieser NutzerIn; null, wenn es keinen gibt. */
function dt_neuester(int $userId): ?int
{
    $q = db()->prepare('SELECT id FROM days WHERE user_id = ? AND deleted_at IS NULL
                        ORDER BY day DESC, started_at DESC, id DESC LIMIT 1');
    $q->execute([$userId]);
    $id = $q->fetchColumn();
    return $id === false ? null : (int)$id;
}

/* ---- Stammdaten pruefen und Vorbelegung --------------------------------- */

/**
 * Standard-Vorbelegung aus `user_defaults`.
 *
 * @return array{base_id:?int,vehicle_id:?int}
 */
function dt_standardwerte(int $userId): array
{
    $q = db()->prepare('SELECT kind, item_id FROM user_defaults WHERE user_id = ?');
    $q->execute([$userId]);
    $w = ['base_id' => null, 'vehicle_id' => null];
    foreach ($q->fetchAll() as $z) {
        if ($z['kind'] === 'base')    { $w['base_id']    = (int)$z['item_id']; }
        if ($z['kind'] === 'vehicle') { $w['vehicle_id'] = (int)$z['item_id']; }
    }
    return $w;
}

/**
 * Gehoert diese Kennung der NutzerIn oder ist sie zentral UND ausgewaehlt?
 *
 * Muss zu den Listen passen, aus denen die Oberflaeche ihre Auswahlfelder baut
 * (dt_bases(), dt_vehicles()) — sonst wird ein zentraler Standort beim
 * Speichern stillschweigend auf NULL zurueckgesetzt.
 *
 * Zentrale Eintraege gelten erst als verfuegbar, wenn die NutzerIn den Standort
 * ausgewaehlt hat (E16). Bei `vehicles` haengt das am Standort des
 * Rettungsmittels, nicht am Rettungsmittel selbst.
 */
function dt_base_erlaubt(PDO $pdo, int $userId, ?int $baseId): ?int
{
    if ($baseId === null || $baseId <= 0) { return null; }
    $q = $pdo->prepare('SELECT b.id FROM bases b
                         LEFT JOIN user_bases ub ON ub.base_id = b.id AND ub.user_id = ?
                        WHERE b.id = ? AND (b.user_id = ? OR (b.user_id IS NULL AND ub.base_id IS NOT NULL))');
    $q->execute([$userId, $baseId, $userId]);
    return $q->fetchColumn() !== false ? $baseId : null;
}

/** Wie dt_base_erlaubt(), fuer Rettungsmittel; prueft den Standort mit. */
function dt_vehicle_erlaubt(PDO $pdo, int $userId, ?int $vehicleId): ?int
{
    if ($vehicleId === null || $vehicleId <= 0) { return null; }
    $q = $pdo->prepare('SELECT v.id FROM vehicles v
                         LEFT JOIN user_bases ub ON ub.base_id = v.base_id AND ub.user_id = ?
                        WHERE v.id = ? AND (v.user_id = ? OR (v.user_id IS NULL AND ub.base_id IS NOT NULL))');
    $q->execute([$userId, $vehicleId, $userId]);
    return $q->fetchColumn() !== false ? $vehicleId : null;
}

/**
 * Standorte, die dieser NutzerIn zur Verfuegung stehen: die eigenen und die
 * ausgewaehlten zentralen (E16).
 *
 * Eigene Standorte brauchen keinen Eintrag in `user_bases` — sie gelten immer
 * als ausgewaehlt.
 */
function dt_bases(int $userId): array
{
    $q = db()->prepare('SELECT b.id, b.name, b.lat, b.lon, b.user_id IS NULL AS zentral
                          FROM bases b
                          LEFT JOIN user_bases ub ON ub.base_id = b.id AND ub.user_id = ?
                         WHERE b.user_id = ? OR (b.user_id IS NULL AND ub.base_id IS NOT NULL)
                         ORDER BY b.name');
    $q->execute([$userId, $userId]);
    return $q->fetchAll();
}

/**
 * Rettungsmittel der verfuegbaren Standorte, mit Art und Standortnamen.
 *
 * Ein Rettungsmittel OHNE Standort (Bestandsdaten vor der Nachbearbeitung,
 * Problem P6) erscheint bewusst mit: Sonst verschwaende es aus jeder Auswahl,
 * bevor die Nachbearbeitung ueberhaupt aufgerufen wurde.
 */
function dt_vehicles(int $userId): array
{
    $q = db()->prepare('SELECT v.id, v.name, v.kind, v.base_id, b.name AS base_name
                          FROM vehicles v
                          LEFT JOIN bases b ON b.id = v.base_id
                          LEFT JOIN user_bases ub ON ub.base_id = v.base_id AND ub.user_id = ?
                         WHERE v.user_id = ?
                            OR (v.user_id IS NULL AND (ub.base_id IS NOT NULL OR v.base_id IS NULL))
                         ORDER BY v.name');
    $q->execute([$userId, $userId]);
    return $q->fetchAll();
}

/** Rollen eines Rettungsmittels, in Katalogreihenfolge. */
function dt_vehicle_rollen(PDO $pdo, int $vehicleId): array
{
    $q = $pdo->prepare('SELECT role_code FROM vehicle_roles WHERE vehicle_id = ?');
    $q->execute([$vehicleId]);
    $vorhanden = $q->fetchAll(PDO::FETCH_COLUMN);
    return array_values(array_filter(
        array_keys(CREW_ROLES),
        static fn(string $c): bool => in_array($c, $vorhanden, true)
    ));
}

/** Faehigkeiten eines Rettungsmittels, in Katalogreihenfolge. */
function dt_vehicle_faehigkeiten(PDO $pdo, int $vehicleId): array
{
    $q = $pdo->prepare('SELECT capability FROM vehicle_capabilities WHERE vehicle_id = ?');
    $q->execute([$vehicleId]);
    $vorhanden = $q->fetchAll(PDO::FETCH_COLUMN);
    return array_values(array_filter(
        array_keys(VEHICLE_CAPABILITIES),
        static fn(string $c): bool => in_array($c, $vorhanden, true)
    ));
}

/* ---- Anlegen und zuordnen ------------------------------------------------ */

/**
 * Einen Diensttag anlegen (E9: jeder Start ein eigener).
 *
 * KEIN `INSERT IGNORE` und keine Pruefung auf ein vorhandenes Datum — das war
 * die Logik des Kalendertags und ist mit dem Tagesschluessel entfallen. Wer
 * zwei Dienste am selben Tag hat, bekommt zwei Zeilen; genau darum ging es.
 *
 * $startedAt ist der echte Dienstbeginn in UTC. Fehlt er, wird Ortsmitternacht
 * des Datums genommen — dieselbe Ersatzregel wie in der Migration, damit die
 * Sortierung nicht an einem NULL haengt.
 */
function dt_anlegen(PDO $pdo, int $userId, string $day, ?string $startedAt = null,
                    ?int $vehicleId = null, ?int $baseId = null): int
{
    if ($startedAt === null) { $startedAt = local_to_utc($day, '00:00', 0); }

    $pdo->prepare('INSERT INTO days (user_id, day, started_at) VALUES (?,?,?)')
        ->execute([$userId, $day, $startedAt]);
    $dayId = (int)$pdo->lastInsertId();

    if ($vehicleId !== null || $baseId !== null) {
        dt_zuordnen($pdo, $userId, $dayId, $vehicleId, $baseId);
    }
    return $dayId;
}

/**
 * Standort und Rettungsmittel eines Diensttags setzen und alles Abgeleitete
 * EINFRIEREN (E8).
 *
 * Geschrieben werden: `vehicle_id`, `base_id` (Filter und Auswertung), die
 * Snapshot-Spalten `kind`, `base_name`, `base_lat`, `base_lon`, `vehicle_name`
 * sowie die Zeilen in `day_crew` und `day_capabilities`.
 *
 * WARUM AUCH BEIM SPAETEREN NACHTRAGEN NEU EINGEFROREN WIRD. Der Zeitpunkt des
 * Einfrierens ist der Zeitpunkt der ZUORDNUNG, nicht der des Anlegens. Ein von
 * der Uhr angelegter Diensttag ist zunaechst neutral (E26); wird die Zuordnung
 * nachgetragen (A7b) oder korrigiert, muss der Snapshot mitkommen — sonst
 * traegt der Tag eine Art ohne Bezeichnung oder umgekehrt. Was E8 ausschliesst,
 * ist etwas anderes: dass eine Aenderung AN DEN STAMMDATEN auf bestehende
 * Diensttage durchschlaegt. Das tut sie hier nicht, weil sie diese Funktion
 * nicht aufruft (A4).
 *
 * BELEGTE ROLLEN GEHEN NIE VERLOREN. Bietet das neue Rettungsmittel eine Rolle
 * nicht mehr an, deren Zeile aber einen Namen traegt, bleibt sie stehen —
 * dieselbe Regel wie in der Migration. Nur leere Zeilen werden entfernt.
 */
function dt_zuordnen(PDO $pdo, int $userId, int $dayId, ?int $vehicleId, ?int $baseId): void
{
    $vehicleId = dt_vehicle_erlaubt($pdo, $userId, $vehicleId);
    $baseId    = dt_base_erlaubt($pdo, $userId, $baseId);

    $kind = null; $vehicleName = null;
    if ($vehicleId !== null) {
        $q = $pdo->prepare('SELECT name, kind FROM vehicles WHERE id = ?');
        $q->execute([$vehicleId]);
        if ($v = $q->fetch()) {
            $kind        = (string)$v['kind'];
            $vehicleName = (string)$v['name'];
        }
    }
    $baseName = null; $baseLat = null; $baseLon = null;
    if ($baseId !== null) {
        $q = $pdo->prepare('SELECT name, lat, lon FROM bases WHERE id = ?');
        $q->execute([$baseId]);
        if ($b = $q->fetch()) {
            $baseName = (string)$b['name'];
            $baseLat  = $b['lat'];
            $baseLon  = $b['lon'];
        }
    }

    $pdo->prepare('UPDATE days SET vehicle_id = ?, base_id = ?, kind = ?,
                     base_name = ?, base_lat = ?, base_lon = ?, vehicle_name = ?
                   WHERE id = ? AND user_id = ?')
        ->execute([$vehicleId, $baseId, $kind, $baseName, $baseLat, $baseLon,
                   $vehicleName, $dayId, $userId]);

    /* ---- Rollensatz einfrieren ---------------------------------------- */
    $soll = $vehicleId !== null ? dt_vehicle_rollen($pdo, $vehicleId) : [];

    $q = $pdo->prepare('SELECT role_code, name FROM day_crew WHERE day_id = ?');
    $q->execute([$dayId]);
    $ist = [];
    foreach ($q->fetchAll() as $z) { $ist[(string)$z['role_code']] = $z['name']; }

    $ins = $pdo->prepare('INSERT INTO day_crew (day_id, role_code, name) VALUES (?,?,NULL)');
    foreach ($soll as $code) {
        if (!array_key_exists($code, $ist)) { $ins->execute([$dayId, $code]); }
    }
    $del = $pdo->prepare('DELETE FROM day_crew WHERE day_id = ? AND role_code = ?');
    foreach ($ist as $code => $name) {
        $leer = ($name === null || trim((string)$name) === '');
        if ($leer && !in_array($code, $soll, true)) { $del->execute([$dayId, $code]); }
    }

    /* ---- Faehigkeitssatz einfrieren ----------------------------------- */
    $pdo->prepare('DELETE FROM day_capabilities WHERE day_id = ?')->execute([$dayId]);
    if ($vehicleId !== null) {
        $insC = $pdo->prepare('INSERT INTO day_capabilities (day_id, capability) VALUES (?,?)');
        foreach (dt_vehicle_faehigkeiten($pdo, $vehicleId) as $cap) {
            $insC->execute([$dayId, $cap]);
        }
    }
}

/**
 * Fehlende `day_crew`-Zeilen fuer die genannten Rollen nachtragen.
 *
 * Gebraucht dort, wo eine Besatzungsangabe von AUSSEN kommt und der Rollensatz
 * des Diensttags sie nicht vorsieht — beim Import und beim Wiedereinspielen. Der
 * Name ist dann eine Angabe, und sie stillschweigend zu verwerfen waere
 * Datenverlust. Dieselbe Regel hat die Migration angewandt: „Belegte Rolle, die
 * das Rettungsmittel nicht vorsieht, geht nicht verloren."
 *
 * Nicht aufgerufen wird sie beim Speichern des Diensttag-Formulars: Dort kommen
 * die Rollen aus dem Formular, das sie zuvor aus `day_crew` gebaut hat — eine
 * Rolle ausserhalb des Satzes koennte dort gar nicht entstehen.
 */
function dt_crew_ergaenzen(PDO $pdo, int $dayId, array $rollen): void
{
    if (!$rollen) { return; }
    $ins = $pdo->prepare('INSERT IGNORE INTO day_crew (day_id, role_code, name)
                          VALUES (?,?,NULL)');
    foreach ($rollen as $code) {
        if (!array_key_exists((string)$code, CREW_ROLES)) { continue; }
        $ins->execute([$dayId, (string)$code]);
    }
}

/** Besatzungsnamen eines Diensttags schreiben; nur bekannte Rollen des Tages. */
function dt_crew_speichern(PDO $pdo, int $dayId, array $namenJeRolle): void
{
    $q = $pdo->prepare('SELECT role_code FROM day_crew WHERE day_id = ?');
    $q->execute([$dayId]);
    $rollen = $q->fetchAll(PDO::FETCH_COLUMN);

    $upd = $pdo->prepare('UPDATE day_crew SET name = ? WHERE day_id = ? AND role_code = ?');
    foreach ($rollen as $code) {
        if (!array_key_exists($code, $namenJeRolle)) { continue; }
        $name = mb_substr(trim((string)$namenJeRolle[$code]), 0, 120);
        $upd->execute([$name !== '' ? $name : null, $dayId, (string)$code]);
    }
}

/**
 * Zeitraum eines Diensttags fortschreiben (JSON-Vertrag 4.4).
 *
 * `started_at` wandert nur nach VORNE, `ended_at` nur nach HINTEN: Ein Dienst
 * umschliesst alles, was in ihm dokumentiert ist. Ein Einsatz, der um 00:40 des
 * Folgetags endet, verlaengert den Dienst — er verschiebt ihn nicht.
 */
function dt_zeitraum_fortschreiben(PDO $pdo, int $dayId, ?string $start, ?string $ende): void
{
    if ($start !== null) {
        $pdo->prepare('UPDATE days SET started_at = ?
                       WHERE id = ? AND (started_at IS NULL OR started_at > ?)')
            ->execute([$start, $dayId, $start]);
    }
    if ($ende !== null) {
        $pdo->prepare('UPDATE days SET ended_at = ?
                       WHERE id = ? AND (ended_at IS NULL OR ended_at < ?)')
            ->execute([$ende, $dayId, $ende]);
    }
}

/**
 * Rueckfallebene fuer Uploads ohne `day_ref` (JSON-Vertrag 4.4).
 *
 * Aeltere Uhr-Fassungen kennen keine Dienstkennung; fuer sie gilt weiterhin der
 * Weg ueber (user_id, day). DIESE EBENE BLEIBT DAUERHAFT — sie ist der Grund,
 * warum ein Update die Uhr nicht zwingend mitziehen muss.
 *
 * Liegen mehrere Diensttage auf dem Datum, entscheidet die ZEIT des
 * hochgeladenen Datensatzes, nicht die Reihenfolge:
 *
 *   1. Der Diensttag, dessen Zeitraum ihn UMSCHLIESST. Das ist keine Vermutung,
 *      sondern eine Auskunft — der Dienst lief, als der Einsatz begann.
 *   2. Sonst der letzte, der VOR ihm begonnen hat. Ein noch offener Dienst hat
 *      kein `ended_at`; sein Einsatz liegt dann zwangslaeufig dahinter.
 *   3. Sonst der FRUEHESTE des Datums — der zeitlich naechste, wenn keiner
 *      vorher begonnen hat. Nur wenn gar keine Startzeit vorliegt, gewinnt der
 *      juengste.
 *
 * Ohne diese Reihenfolge griff Regel 3 immer, und ein Frueheinsatz landete am
 * Abenddienst — der dadurch seinen Beginn um Stunden nach vorne zog
 * (dt_zeitraum_fortschreiben). Die Uhr sagt nicht, welcher Dienst gemeint ist;
 * ihre Zeitstempel sagen es aber sehr wohl. Wer es dennoch anders braucht,
 * ordnet den Einsatz nachtraeglich um (einsatz_verschieben.php).
 *
 * Ein Diensttag im PAPIERKORB wird uebergangen und nicht wiederbelebt: Das
 * Loeschen war eine bewusste Handlung.
 */
function dt_rueckfall(PDO $pdo, int $userId, string $day, ?string $startedAt = null): int
{
    if ($startedAt !== null) {
        // 1. Umschliessender Dienst.
        $q = $pdo->prepare('SELECT id FROM days
                             WHERE user_id = ? AND day = ? AND deleted_at IS NULL
                               AND started_at IS NOT NULL AND started_at <= ?
                               AND (ended_at IS NULL OR ended_at >= ?)
                             ORDER BY started_at DESC, id DESC LIMIT 1');
        $q->execute([$userId, $day, $startedAt, $startedAt]);
        $id = $q->fetchColumn();
        if ($id !== false) { return (int)$id; }

        // 2. Letzter Dienst, der vorher begonnen hat.
        $q = $pdo->prepare('SELECT id FROM days
                             WHERE user_id = ? AND day = ? AND deleted_at IS NULL
                               AND started_at IS NOT NULL AND started_at <= ?
                             ORDER BY started_at DESC, id DESC LIMIT 1');
        $q->execute([$userId, $day, $startedAt]);
        $id = $q->fetchColumn();
        if ($id !== false) { return (int)$id; }
    }

    /* 3. Der FRUEHESTE des Datums, wenn eine Startzeit vorliegt — dann hat
     *    keiner vorher begonnen, und der zeitlich naechste ist der erste.
     *    Ohne Startzeit der juengste: Eine Nachlieferung ohne Zeitangabe
     *    betrifft am wahrscheinlichsten den laufenden Dienst. */
    $q = $pdo->prepare('SELECT id FROM days
                         WHERE user_id = ? AND day = ? AND deleted_at IS NULL
                         ORDER BY started_at ' . ($startedAt !== null ? 'ASC' : 'DESC')
                         . ', id ASC LIMIT 1');
    $q->execute([$userId, $day]);
    $id = $q->fetchColumn();
    if ($id !== false) { return (int)$id; }

    // Kein Tag dieses Datums: einen neutralen anlegen. Standort und
    // Rettungsmittel bleiben offen (E26) — die Uhr kennt sie nicht (E21).
    return dt_anlegen($pdo, $userId, $day, $startedAt);
}

/**
 * Diensttag zu einer Uhr-Kennung finden oder anlegen (JSON-Vertrag 4.4).
 *
 * Die Kennung liegt in `day_refs`, nicht in `days`: Nach dem Zusammenfuehren
 * traegt ein Diensttag legitim MEHRERE Kennungen, und ein Nachschlagen findet
 * dann von selbst den Zieltag — ohne jede Umleitungslogik (Konzept 4.5).
 *
 * Der Nachschlag geht ueber (device_id, day_ref), also ueber den eindeutigen
 * Schluessel der Tabelle. Die Nutzerkennung wird zusaetzlich geprueft: Ein
 * Geraet gehoert genau einem Konto, aber die Bedingung soll in der Abfrage
 * stehen und nicht als Zusicherung daneben.
 *
 * $vorhandenerDayId ist der Diensttag, an dem der hochgeladene Datensatz schon
 * haengt. Ist die Kennung unbekannt, wird sie an IHN gebunden statt einen neuen
 * Tag anzulegen. Der Fall tritt beim Umstieg auf eine Uhr-Fassung MIT Kennung
 * auf: Der laufende Dienst liegt bereits als Diensttag vor, angelegt ueber die
 * Rueckfallebene. Ohne diese Bindung entstuenden aus einem Dienst zwei — der
 * erste mit den bisherigen Einsaetzen, der zweite mit allen weiteren.
 */
function dt_zu_dayref(PDO $pdo, int $userId, int $deviceId, string $dayRef,
                      string $day, ?string $startedAt = null,
                      ?int $vorhandenerDayId = null): int
{
    $q = $pdo->prepare('SELECT r.day_id FROM day_refs r
                          JOIN days d ON d.id = r.day_id
                         WHERE r.device_id = ? AND r.day_ref = ? AND d.user_id = ?');
    $q->execute([$deviceId, $dayRef, $userId]);
    $id = $q->fetchColumn();
    if ($id !== false) { return (int)$id; }

    $dayId = $vorhandenerDayId ?? dt_anlegen($pdo, $userId, $day, $startedAt);
    $pdo->prepare('INSERT INTO day_refs (day_id, device_id, day_ref) VALUES (?,?,?)')
        ->execute([$dayId, $deviceId, $dayRef]);
    return $dayId;
}
