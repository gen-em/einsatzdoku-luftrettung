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
 * SEIT P3/O2 STEHT HIER EIN DATEINAME, KEIN EMOJI (E-P3-18). Die beiden
 * Emoji fuer Hubschrauber und Rettungswagen wurden je Betriebssystem in
 * anderer Zeichnung, Farbe und Groesse gerendert und liessen sich weder
 * faerben noch auf Kontrast pruefen — und in
 * Tagesleiste, Tabellen und Rettungsmittel-Auswahl waren sie die einzige
 * Artauskunft neben dem Tooltip. Jetzt verweist `symbol` auf eine Datei unter
 * assets/images/symbole/; ausgegeben wird sie mit ui_symbol() in PHP und
 * edSymbol() in JavaScript.
 *
 * `text` ist damit nicht weniger wichtig, sondern mehr: In einem <option>
 * laesst sich kein SVG unterbringen — dort steht seither das WORT.
 *
 * @return array<string,array{symbol:string,text:string}>
 */
function dt_art_symbole(): array
{
    return [
        'air'    => ['symbol' => 'hubschrauber',   'text' => 'luftgebunden'],
        'ground' => ['symbol' => 'fahrzeug',       'text' => 'bodengebunden'],
        ''       => ['symbol' => 'ohne-zuordnung', 'text' => 'ohne Zuordnung'],
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
 * @return array{symbol:string,text:string}
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
 *
 * EIN GELOESCHTER DIENSTTAG ZAEHLT NICHT (Backlog Nr. 33, seit Web 8.0.0).
 *
 * Weder der Nachschlag ueber `day_refs` noch $vorhandenerDayId darf auf einen
 * Tag im Papierkorb fuehren. Sonst legte die naechste Nachlieferung einen
 * AKTIVEN Einsatz an einem GELOESCHTEN Tag an — den halb sichtbaren Zustand,
 * den E-S1-19 beim Einspielen einer Sicherung ablehnt und den die
 * Papierkorbseite seit Web 8.0.0 nicht mehr herstellen kann. Die Uhr weiss
 * nichts vom Papierkorb und liefert weiter; ohne diese Bedingung waere sie
 * die letzte offene Tuer.
 *
 * STATTDESSEN ENTSTEHT EIN NEUER TAG, und die Kennung wird auf ihn UMGEBOGEN
 * (`ON DUPLICATE KEY UPDATE` — `day_refs` ist auf (device_id, day_ref)
 * eindeutig). Zwei Gruende gegen das Verwerfen des Uploads:
 *
 *  - Die Uhr hat den Dienst tatsaechlich geflogen. Ihn wegzuwerfen, weil im
 *    Web jemand den Vortag geloescht hat, waere Datenverlust aus einem
 *    Zusammenhang, den die NutzerIn nicht sieht.
 *  - Ein neuer Tag ist umkehrbar: Stellt sich heraus, dass er doch zum alten
 *    gehoert, fuehrt diensttag_zusammenfuehren.php beide zusammen. Ein
 *    verworfener Upload dagegen ist fort — die Uhr sendet ein Paket nur,
 *    bis der Server es quittiert.
 *
 * Der geloeschte Tag verliert dabei seine Kennung. Das ist richtig so: Wird
 * er spaeter aus dem Papierkorb zurueckgeholt, gehoert die weiterlaufende
 * Uhr-Sitzung zum NEUEN Tag, nicht zu ihm.
 */
function dt_zu_dayref(PDO $pdo, int $userId, int $deviceId, string $dayRef,
                      string $day, ?string $startedAt = null,
                      ?int $vorhandenerDayId = null): int
{
    $q = $pdo->prepare('SELECT r.day_id FROM day_refs r
                          JOIN days d ON d.id = r.day_id
                         WHERE r.device_id = ? AND r.day_ref = ? AND d.user_id = ?
                           AND d.deleted_at IS NULL');
    $q->execute([$deviceId, $dayRef, $userId]);
    $id = $q->fetchColumn();
    if ($id !== false) { return (int)$id; }

    // Auch der schon zugeordnete Tag muss aktiv sein, sonst bindet die
    // Kennung an einen Papierkorbeintrag.
    if ($vorhandenerDayId !== null) {
        $v = $pdo->prepare('SELECT id FROM days
                             WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
        $v->execute([$vorhandenerDayId, $userId]);
        if ($v->fetchColumn() === false) { $vorhandenerDayId = null; }
    }

    $dayId = $vorhandenerDayId ?? dt_anlegen($pdo, $userId, $day, $startedAt);
    $pdo->prepare('INSERT INTO day_refs (day_id, device_id, day_ref) VALUES (?,?,?)
                   ON DUPLICATE KEY UPDATE day_id = VALUES(day_id)')
        ->execute([$dayId, $deviceId, $dayRef]);
    return $dayId;
}

/* ---- Zusammenfuehren (Konzept 3.4 und 4.5, E10-E13, E25) ----------------- */

/**
 * Wie weit reicht "zeitlich benachbart" in der Kandidatenliste?
 *
 * Abschnitt 8 laesst die Zahl ausdruecklich offen. Drei Tage sind die Antwort
 * auf den Anwendungsfall: Die App wurde WAEHREND EINES DIENSTES mehrfach
 * gestartet — die Bruchstuecke liegen dann am selben Kalendertag oder ueber
 * Mitternacht am folgenden. Der dritte Tag ist Luft fuer den Fall, dass ein
 * Bruchstueck erst spaeter von Hand nachgetragen wurde.
 *
 * Eine laengere Liste waere nicht hilfreicher, sondern gefaehrlicher: Der
 * Vorgang ist nicht umkehrbar (E13), und je mehr Zeilen zur Wahl stehen, desto
 * eher greift jemand daneben. Wer zwei weit auseinanderliegende Diensttage
 * zusammenfuehren will, datiert erst einen davon um (diensttag_datum.php).
 */
const DT_NACHBARSCHAFT_TAGE = 3;

/**
 * Vertragen sich die Arten zweier Diensttage? (E11)
 *
 * Gleiche Art ja, ein neutraler passt zu beidem, `air` gegen `ground` nicht.
 * Der Grund fuer das Verbot steht in Abschnitt 3.4: Sonst landete ein Einsatz
 * mit Windendokumentation an einem bodengebundenen Tag und verloere seine
 * Felder — das `cap_gate` blendete sie aus, und die Werte staenden in Spalten,
 * die niemand mehr sieht.
 */
function dt_art_vereinbar(?string $a, ?string $b): bool
{
    return $a === null || $b === null || $a === $b;
}

/** Art des Ergebnisses: der nicht-NULL-Wert, sonst NULL (Konzept 4.5 Nr. 1). */
function dt_art_ergebnis(?string $a, ?string $b): ?string
{
    return $a ?? $b;
}

/**
 * Kandidaten zum Aufnehmen in einen Zieldiensttag.
 *
 * Zeitlich benachbarte Diensttage derselben NutzerIn, ohne Papierkorbeintraege
 * und ohne den Zieltag selbst (Konzept 4.5).
 *
 * UNVEREINBARE TAGE WERDEN MITGELIEFERT, nicht weggelassen — mit `vereinbar`
 * auf `false`. Ein Kandidat, der schlicht fehlt, sieht aus wie ein Fehler der
 * Anwendung; A7 verlangt ausdruecklich eine VERSTAENDLICHE MELDUNG, und die
 * laesst sich nur an einer Zeile anbringen, die auch dasteht. Die Oberflaeche
 * zeigt sie als nicht waehlbar samt Grund.
 *
 * Die Zahlen je Kandidat (Einsaetze, Ruhesegmente, Uhr-Kennungen) sind keine
 * Zierde: Sie sind das Einzige, woran sich zwei Bruchstuecke desselben Dienstes
 * vor dem Zusammenfuehren auseinanderhalten lassen.
 */
function dt_merge_kandidaten(int $userId, int $zielId,
                             int $tage = DT_NACHBARSCHAFT_TAGE): array
{
    $ziel = dt_laden($userId, $zielId);
    if ($ziel === null) { return []; }

    $q = db()->prepare(
        'SELECT d.*,
                (SELECT COUNT(*) FROM missions m
                  WHERE m.day_id = d.id AND m.deleted_at IS NULL)      AS einsaetze,
                (SELECT COUNT(*) FROM rest_segments r
                  WHERE r.day_id = d.id AND r.deleted_at IS NULL)      AS segmente,
                (SELECT COUNT(*) FROM day_refs f WHERE f.day_id = d.id) AS kennungen
           FROM days d
          WHERE d.user_id = ? AND d.id <> ? AND d.deleted_at IS NULL
            AND d.day BETWEEN DATE_SUB(?, INTERVAL ' . (int)$tage . ' DAY)
                          AND DATE_ADD(?, INTERVAL ' . (int)$tage . ' DAY)
          ORDER BY d.day DESC, d.started_at DESC, d.id DESC');
    $q->execute([$userId, $zielId, $ziel['day'], $ziel['day']]);

    $zielKind = $ziel['kind'] === null ? null : (string)$ziel['kind'];
    $liste = [];
    foreach ($q->fetchAll() as $z) {
        $kind = $z['kind'] === null ? null : (string)$z['kind'];
        $z['vereinbar'] = dt_art_vereinbar($zielKind, $kind);
        $liste[] = $z;
    }
    return $liste;
}

/**
 * Beide Diensttage laden und pruefen, ob sie sich zusammenfuehren lassen.
 *
 * Die Pruefung liegt hier und nicht in der Seite, weil sie ZWEIMAL laufen muss:
 * einmal fuer die Vorschau und einmal unmittelbar vor dem Schreiben. Zwischen
 * beiden liegt eine Bestaetigung durch einen Menschen — in dieser Zeit kann der
 * aufzunehmende Tag im Papierkorb gelandet sein oder eine Zuordnung bekommen
 * haben, die die Arten unvereinbar macht.
 *
 * @return array{ok:bool,meldung:?string,ziel:?array,quelle:?array}
 */
function dt_merge_pruefen(int $userId, int $zielId, int $quellId): array
{
    $nein = static fn(string $m): array
        => ['ok' => false, 'meldung' => $m, 'ziel' => null, 'quelle' => null];

    if ($zielId === $quellId) {
        return $nein('Ein Diensttag lässt sich nicht mit sich selbst zusammenführen.');
    }
    /* Mit Papierkorb laden, um ihn BENENNEN zu koennen: "nicht gefunden" waere
     * auf einen gerade geloeschten Tag eine irrefuehrende Auskunft. */
    $ziel   = dt_laden($userId, $zielId, true);
    $quelle = dt_laden($userId, $quellId, true);
    if ($ziel === null || $quelle === null) {
        return $nein('Diensttag nicht gefunden.');
    }
    if ($ziel['deleted_at'] !== null) {
        return $nein('Dieser Diensttag liegt im Papierkorb. Bitte ihn zuerst wiederherstellen.');
    }
    if ($quelle['deleted_at'] !== null) {
        return $nein('Der aufzunehmende Diensttag liegt im Papierkorb. Bitte ihn zuerst wiederherstellen.');
    }

    $zk = $ziel['kind']   === null ? null : (string)$ziel['kind'];
    $qk = $quelle['kind'] === null ? null : (string)$quelle['kind'];
    if (!dt_art_vereinbar($zk, $qk)) {
        $sz = dt_art_symbol($zk); $sq = dt_art_symbol($qk);
        return $nein('Diese beiden Diensttage lassen sich nicht zusammenführen: '
            . 'Der eine ist ' . $sz['text'] . ', der andere ' . $sq['text'] . '. '
            . 'Ein Einsatz mit Windendokumentation würde an einem bodengebundenen '
            . 'Diensttag seine Felder verlieren. Ein noch nicht zugeordneter '
            . 'Diensttag lässt sich dagegen mit beiden Arten zusammenführen.');
    }
    return ['ok' => true, 'meldung' => null, 'ziel' => $ziel, 'quelle' => $quelle];
}

/**
 * Vorschau auf das Ergebnis: Zeitraum, Umfang und die Stellen, an denen die
 * beiden Tage sich widersprechen (Konzept 4.5 Nr. 2).
 *
 * ZUM ZEITRAUM. `started_at` wandert nach vorne, `ended_at` nach hinten — die
 * Regel von dt_zeitraum_fortschreiben(), hier auf zwei Diensttage angewandt:
 * Ein Dienst umschliesst alles, was in ihm dokumentiert ist (A6).
 *
 * Ein NULL in `ended_at` heisst "noch nicht bekannt", nicht "endet nie". Das
 * Ergebnis nimmt deshalb das SPAETESTE BEKANNTE Ende und bleibt nur dann ohne,
 * wenn beide keines haben. Anders herum — ein einziges NULL macht das Ergebnis
 * offen — traefe genau den Regelfall dieser Funktion am haertesten: Das
 * aufzunehmende Bruchstueck ist typischerweise das, auf dem "Einsatztag
 * beenden" nie gedrueckt wurde. Ein sauber beendeter Zieltag verloere sein Ende
 * an ein Bruchstueck von zwanzig Minuten. Bleibt der Dienst tatsaechlich noch
 * offen, schreibt der naechste Upload das Ende ohnehin fort.
 *
 * ZUM DATUM. `day` wird NICHT aus `started_at` zurueckgerechnet, sondern vom
 * frueher beginnenden Tag uebernommen. Genau dieses Feld ist bereits das
 * ORTSDATUM seines Dienstbeginns; eine Umrechnung koennte daran nur scheitern.
 *
 * @return array Vorschau samt `wahlen`: die Widersprueche, ueber die beim
 *               Zusammenfuehren zu entscheiden ist.
 */
function dt_merge_vorschau(int $userId, array $ziel, array $quelle): array
{
    $zahl = static function (string $sql, array $p): int {
        $q = db()->prepare($sql); $q->execute($p); return (int)$q->fetchColumn();
    };
    $zid = (int)$ziel['id']; $qid = (int)$quelle['id'];

    /* Der frueher beginnende Tag bestimmt Datum und Beginn. Ein fehlender
     * `started_at` (Bestandsdaten) verliert gegen einen vorhandenen, sonst
     * entscheidet das Datum. */
    $zs = $ziel['started_at']   !== null ? (string)$ziel['started_at']   : null;
    $qs = $quelle['started_at'] !== null ? (string)$quelle['started_at'] : null;
    if ($zs !== null && $qs !== null) { $frueher = $qs < $zs ? $quelle : $ziel; }
    elseif ($zs !== null)             { $frueher = $ziel; }
    elseif ($qs !== null)             { $frueher = $quelle; }
    else { $frueher = (string)$quelle['day'] < (string)$ziel['day'] ? $quelle : $ziel; }

    $enden = array_filter([$ziel['ended_at'], $quelle['ended_at']],
                          static fn($v): bool => $v !== null && $v !== '');

    $zk = $ziel['kind']   === null ? null : (string)$ziel['kind'];
    $qk = $quelle['kind'] === null ? null : (string)$quelle['kind'];

    /* ---- Widersprueche, ueber die zu entscheiden ist --------------------- */
    $wahlen = [];
    $txt = static fn($v): string => $v === null ? '' : trim((string)$v);

    /* Rettungsmittel: nur eine Wahl, wenn BEIDE eines fuehren und es ein
     * anderes ist. Fuehrt nur einer eines, gewinnt er kampflos — dieselbe
     * Regel wie bei der Art (E11), nur eine Ebene tiefer. */
    if ($ziel['vehicle_id'] !== null && $quelle['vehicle_id'] !== null
        && ((int)$ziel['vehicle_id'] !== (int)$quelle['vehicle_id']
            || $txt($ziel['vehicle_name']) !== $txt($quelle['vehicle_name']))) {
        $wahlen['vehicle'] = [
            'titel'  => 'Rettungsmittel',
            'ziel'   => $txt($ziel['vehicle_name'])   ?: '—',
            'quelle' => $txt($quelle['vehicle_name']) ?: '—',
        ];
    }
    if ($ziel['base_id'] !== null && $quelle['base_id'] !== null
        && ((int)$ziel['base_id'] !== (int)$quelle['base_id']
            || $txt($ziel['base_name']) !== $txt($quelle['base_name']))) {
        $wahlen['base'] = [
            'titel'  => 'Standort',
            'ziel'   => $txt($ziel['base_name'])   ?: '—',
            'quelle' => $txt($quelle['base_name']) ?: '—',
        ];
    }

    /* Besatzung: nur vergleichen, was BELEGT ist. Zwei Tage mit demselben
     * Rollensatz und lauter leeren Zeilen widersprechen sich nicht. */
    $belegt = static function (array $crew): array {
        $b = [];
        foreach ($crew as $code => $name) {
            if ($name !== null && trim((string)$name) !== '') { $b[$code] = trim((string)$name); }
        }
        return $b;
    };
    $cz = $belegt(dt_crew($zid));
    $cq = $belegt(dt_crew($qid));
    if ($cz && $cq && $cz != $cq) {
        $satz = static function (array $c): string {
            $t = [];
            foreach ($c as $code => $name) { $t[] = crew_role_label((string)$code) . ': ' . $name; }
            return implode(', ', $t);
        };
        $wahlen['crew'] = [
            'titel'  => 'Besatzung',
            'ziel'   => $satz($cz),
            'quelle' => $satz($cq),
        ];
    }

    return [
        'ziel_id'    => $zid,
        'quell_id'   => $qid,
        'day'        => (string)$frueher['day'],
        'started_at' => $frueher['started_at'] !== null ? (string)$frueher['started_at'] : null,
        'ended_at'   => $enden ? max($enden) : null,
        /* Das Ergebnis hat gar kein Ende — beide Tage sind offen. */
        'ende_offen' => !$enden,
        /* Genau einer hatte ein Ende: Die Vorschau sagt dazu, woher es kommt,
           sonst sieht der ausgewiesene Zeitraum genauer aus, als er ist. */
        'ende_geerbt' => count($enden) === 1,
        'kind'       => dt_art_ergebnis($zk, $qk),
        'einsaetze'  => $zahl('SELECT COUNT(*) FROM missions
                                WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL',
                              [$userId, $qid]),
        'einsaetze_papierkorb' => $zahl('SELECT COUNT(*) FROM missions
                                          WHERE user_id = ? AND day_id = ? AND deleted_at IS NOT NULL',
                                        [$userId, $qid]),
        'segmente'   => $zahl('SELECT COUNT(*) FROM rest_segments
                                WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL',
                              [$userId, $qid]),
        'segmente_papierkorb' => $zahl('SELECT COUNT(*) FROM rest_segments
                                         WHERE user_id = ? AND day_id = ? AND deleted_at IS NOT NULL',
                                       [$userId, $qid]),
        'kennungen'  => $zahl('SELECT COUNT(*) FROM day_refs WHERE day_id = ?', [$qid]),
        'wahlen'     => $wahlen,
    ];
}

/**
 * Zwei Diensttage zusammenfuehren (Konzept 4.5 Nr. 3, E10-E13).
 *
 * Der Zieltag bleibt, der aufgenommene verschwindet ENDGUELTIG — nicht ueber
 * den Papierkorb (E13): Dort laege ein leerer Tag, dessen Wiederherstellung die
 * Einsaetze nicht zurueckholen koennte, weil sie inzwischen am Zieltag haengen.
 * Ein Papierkorbeintrag, der beim Wiederherstellen etwas anderes ergibt als das
 * Geloeschte, ist schlimmer als gar keiner.
 *
 * EIN CODEPFAD FUER UHR- UND HANDTAGE (E10). Der Unterschied zwischen beiden
 * sind allein die Zeilen in `day_refs`; sie wandern mit, und ein Handtag hat
 * eben keine. Eine zweite Fassung "fuer Uhrtage" haette denselben Code mit
 * einer Zeile mehr.
 *
 * DIE SPERRLISTE `deleted_refs` WIRD NICHT BEDIENT (Konzept 4.5 Nr. 4). Die
 * Uhr-Kennungen sind nicht verschwunden, sie zeigen jetzt auf den Zieltag —
 * genau deshalb liegen sie in einer eigenen Tabelle. Ein spaeterer Upload mit
 * einer Kennung des aufgenommenen Tags landet damit von selbst richtig (A8),
 * ohne jede Umleitungslogik.
 *
 * $wahl entscheidet die Widersprueche aus dt_merge_vorschau(): je Schluessel
 * 'ziel' oder 'quelle'. Fehlt einer, gilt der Zieltag — der geoeffnete Tag ist
 * der, den die Nutzerin vor Augen hat (E25).
 *
 * Laeuft in der Transaktion des Aufrufers.
 *
 * @return array{ok:bool,meldung:?string,einsaetze:int,segmente:int,kennungen:int}
 */
function dt_zusammenfuehren(PDO $pdo, int $userId, int $zielId, int $quellId,
                            array $wahl = []): array
{
    $p = dt_merge_pruefen($userId, $zielId, $quellId);
    if (!$p['ok']) {
        return ['ok' => false, 'meldung' => $p['meldung'],
                'einsaetze' => 0, 'segmente' => 0, 'kennungen' => 0];
    }
    $ziel = $p['ziel']; $quelle = $p['quelle'];
    $vor  = dt_merge_vorschau($userId, $ziel, $quelle);

    $nimm = static fn(string $feld): array
        => (($wahl[$feld] ?? 'ziel') === 'quelle') ? [$quelle, $ziel] : [$ziel, $quelle];

    /* ---- Einsaetze und Ruhesegmente umhaengen --------------------------- */
    /* AUCH DIE PAPIERKORBEINTRAEGE. `missions.day_id` steht auf
     * ON DELETE SET NULL; ein zurueckgelassener Einsatz verloere beim Entfernen
     * des Quelltags still seinen Diensttag und waere verwaist (A11). Der
     * Papierkorb selbst arbeitet mit `deleted_at`, nicht mit dem Diensttag —
     * das Wiederherstellen findet ihn am Zieltag genauso. */
    $u = $pdo->prepare('UPDATE missions SET day_id = ? WHERE user_id = ? AND day_id = ?');
    $u->execute([$zielId, $userId, $quellId]);
    $mCount = $u->rowCount();

    $u = $pdo->prepare('UPDATE rest_segments SET day_id = ? WHERE user_id = ? AND day_id = ?');
    $u->execute([$zielId, $userId, $quellId]);
    $sCount = $u->rowCount();

    $u = $pdo->prepare('UPDATE day_refs SET day_id = ? WHERE day_id = ?');
    $u->execute([$zielId, $quellId]);
    $rCount = $u->rowCount();

    /* ---- Zeitraum, Art und die gewaehlten Angaben ----------------------- */
    [$vGewinner] = $nimm('vehicle');
    [$bGewinner] = $nimm('base');

    /* Fuehrt nur EINER ein Rettungsmittel, gewinnt er — unabhaengig von der
     * Wahl, die es dann gar nicht zu treffen gab. Dasselbe beim Standort. */
    if ($ziel['vehicle_id'] === null || $quelle['vehicle_id'] === null) {
        $vGewinner = $ziel['vehicle_id'] !== null ? $ziel : $quelle;
    }
    if ($ziel['base_id'] === null || $quelle['base_id'] === null) {
        $bGewinner = $ziel['base_id'] !== null ? $ziel : $quelle;
    }

    /* Die Art folgt dem Rettungsmittel — sie ist aus ihm eingefroren (E8).
     * Ist der Gewinner neutral, bleibt die Art des anderen: Sie kommt dann aus
     * Bestandsdaten ohne Rettungsmittelbezug und darf nicht verschwinden. */
    $kind = $vGewinner['kind'] !== null
        ? (string)$vGewinner['kind']
        : $vor['kind'];

    /* Notizen aneinanderhaengen (Abschnitt 3.4) — nichts wird ueberschrieben,
     * auch nicht das, was niemand zur Wahl gestellt bekommen hat. */
    $nz = trim((string)($ziel['notes']   ?? ''));
    $nq = trim((string)($quelle['notes'] ?? ''));
    $notes = null;
    if ($nz !== '' && $nq !== '') { $notes = $nz . "\n\n" . $nq; }
    elseif ($nz !== '')           { $notes = $nz; }
    elseif ($nq !== '')           { $notes = $nq; }
    if ($notes !== null) { $notes = mb_substr($notes, 0, 2000); }

    $pdo->prepare('UPDATE days
                      SET day = ?, started_at = ?, ended_at = ?, kind = ?,
                          vehicle_id = ?, vehicle_name = ?,
                          base_id = ?, base_name = ?, base_lat = ?, base_lon = ?,
                          notes = ?
                    WHERE id = ? AND user_id = ?')
        ->execute([$vor['day'], $vor['started_at'], $vor['ended_at'], $kind,
                   $vGewinner['vehicle_id'], $vGewinner['vehicle_name'],
                   $bGewinner['base_id'], $bGewinner['base_name'],
                   $bGewinner['base_lat'], $bGewinner['base_lon'],
                   $notes, $zielId, $userId]);

    /* ---- Faehigkeiten: der eingefrorene Satz des gewinnenden Tages ------ */
    /* NICHT aus `vehicle_capabilities` neu abgeleitet: Das waere ein Blick in
     * die heutigen Stammdaten und damit genau der Durchgriff, den E8
     * ausschliesst. Wurde der Windenhaken seit dem Dienst entfernt, verloere
     * der Tag hier seine Windenfelder (A13e). */
    $capQuelle = (int)$vGewinner['id'];
    $caps = $pdo->prepare('SELECT capability FROM day_capabilities WHERE day_id = ?');
    $caps->execute([$capQuelle]);
    $capListe = $caps->fetchAll(PDO::FETCH_COLUMN);

    $pdo->prepare('DELETE FROM day_capabilities WHERE day_id = ?')->execute([$zielId]);
    $insC = $pdo->prepare('INSERT IGNORE INTO day_capabilities (day_id, capability) VALUES (?,?)');
    foreach ($capListe as $cap) { $insC->execute([$zielId, (string)$cap]); }

    /* ---- Besatzung ------------------------------------------------------ */
    /* Der gewaehlte Satz gilt. Eine Rolle, die er NICHT BELEGT, der andere aber
     * schon, wird von dort uebernommen: Ein Name ist eine Angabe, und sie
     * stillschweigend zu verwerfen waere Datenverlust — dieselbe Regel, die
     * dt_zuordnen() und die Migration anwenden ("belegte Rollen gehen nie
     * verloren"). Widersprechen sich zwei Namen derselben Rolle, gewinnt die
     * Wahl; nur dort geht ueberhaupt etwas verloren, und genau darueber wurde
     * entschieden. */
    [$cGewinner, $cVerlierer] = $nimm('crew');
    $crewG = dt_crew((int)$cGewinner['id']);
    $crewV = dt_crew((int)$cVerlierer['id']);

    $ergebnis = [];
    foreach ($crewG as $code => $name) { $ergebnis[(string)$code] = $name; }
    foreach ($crewV as $code => $name) {
        $code = (string)$code;
        $hat  = isset($ergebnis[$code]) && trim((string)$ergebnis[$code]) !== '';
        $gibt = $name !== null && trim((string)$name) !== '';
        /* Nur BELEGTE Rollen des Verlierers wandern mit. Eine leere Zeile, die
           es nur dort gibt, waere eine Rolle, die das gewaehlte Rettungsmittel
           gar nicht anbietet — der Rollensatz folgt dem Rettungsmittel (E8). */
        if (!$hat && $gibt) { $ergebnis[$code] = $name; }
    }

    $pdo->prepare('DELETE FROM day_crew WHERE day_id = ?')->execute([$zielId]);
    $insR = $pdo->prepare('INSERT IGNORE INTO day_crew (day_id, role_code, name) VALUES (?,?,?)');
    foreach ($ergebnis as $code => $name) {
        $n = ($name === null || trim((string)$name) === '') ? null : mb_substr(trim((string)$name), 0, 120);
        $insR->execute([$zielId, (string)$code, $n]);
    }

    /* ---- Aufgenommenen Diensttag endgueltig entfernen -------------------- */
    /* `day_refs`, `day_crew` und `day_capabilities` des Quelltags haengen an
     * einem ON DELETE CASCADE. Die Kennungen sind zu diesem Zeitpunkt bereits
     * umgehaengt und damit nicht mehr betroffen. */
    $pdo->prepare('DELETE FROM days WHERE id = ? AND user_id = ?')
        ->execute([$quellId, $userId]);

    return ['ok' => true, 'meldung' => null,
            'einsaetze' => $mCount, 'segmente' => $sCount, 'kennungen' => $rCount];
}
