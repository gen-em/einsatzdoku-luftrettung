<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Einsaetze: laden mit Besitzpruefung (Schritt 15/AP4, E-ZE-19).
 *
 * WARUM DIESE DATEI ES GIBT. „Einen Einsatz per Kennung holen und dabei
 * pruefen, dass er dem angemeldeten Konto gehoert" stand bis Web 20.28.0 an
 * ZWOELF Stellen in neun Dateien, jede mit ihrer eigenen Abfrage. Der
 * Unterschied zwischen ihnen war nie die Sache, sondern nur die
 * Spaltenliste und die Frage, wie der Papierkorb behandelt wird — mal
 * `deleted_at IS NULL`, mal `IS NOT NULL`, mal gar nicht. Genau das sind
 * jetzt die beiden Optionen.
 *
 * DIE BESITZPRUEFUNG IST DER PUNKT, nicht das Laden (M3-03, M3-12/M6-09).
 * Sie gehoert IN die Abfrage und nicht davor oder danach: Eine Bedingung,
 * die nicht in der Abfrage steht, wird von nichts durchgesetzt. Bei zwoelf
 * handgeschriebenen Abfragen ist „steht `user_id = ?` da?" eine Frage, die
 * man zwoelfmal stellen muss; bei einer Funktion ist sie einmal beantwortet.
 *
 * ZWEI STELLEN BLEIBEN, UND ZWAR NAMENTLICH:
 *
 *   `trash_restore_mission()` (`trash_lib.php`) — sie fragt den Einsatz UND
 *   den Loeschzustand seines Diensttags in einem Verbund mit `days`. Ein
 *   zweiter Zugriff waere eine zweite Abfrage fuer eine Entscheidung, die
 *   zusammen getroffen werden muss (E-ZE-19).
 *
 *   `api/import_commit.php` — dort ist die Abfrage eine EINMAL vorbereitete
 *   Anweisung, die in der Import-Schleife bis zu 3000-mal ausgefuehrt wird,
 *   neben `$insE` und `$updE`. Ein Funktionsaufruf je Zeile bereitete sie
 *   3000-mal neu vor. Das ist keine Zentralisierung mehr, sondern ein
 *   Messstand-Thema (Schritt 15/AP4, AP4-b).
 */

/**
 * Einen Einsatz laden — mit Besitzpruefung, ohne Umwege.
 *
 * @param array{spalten?:string, papierkorb?:string} $o
 *        `spalten`     Spaltenliste fuer das `SELECT`. Vorgabe `'*'`.
 *                      **Sie kommt aus dem Code, nie aus einer Anfrage** —
 *                      sie wird eingesetzt und nicht gebunden.
 *        `papierkorb`  `'nein'` (Vorgabe, `deleted_at IS NULL`) ·
 *                      `'ja'` (`deleted_at IS NOT NULL`) ·
 *                      `'egal'` (keine Bedingung)
 * @return array<string,mixed>|null `null` = gibt es nicht, gehoert nicht
 *         diesem Konto, oder liegt auf der falschen Seite des Papierkorbs.
 *         Die drei Faelle sind bewusst NICHT unterscheidbar: Wer sie
 *         unterscheidet, sagt einem Fremden, dass es die Kennung gibt.
 */
function einsatz_laden(int $id, int $userId, array $o = []): ?array
{
    $spalten = (string)($o['spalten'] ?? '*');
    $papierkorb = (string)($o['papierkorb'] ?? 'nein');

    $wo = match ($papierkorb) {
        'nein' => ' AND deleted_at IS NULL',
        'ja'   => ' AND deleted_at IS NOT NULL',
        'egal' => '',
        default => throw new InvalidArgumentException(
            'einsatz_laden: unbekannter Wert fuer papierkorb: ' . $papierkorb),
    };

    $st = db()->prepare("SELECT $spalten FROM missions
                          WHERE id = ? AND user_id = ?" . $wo);
    $st->execute([$id, $userId]);
    $m = $st->fetch();
    return $m === false ? null : $m;
}

/* ---- DIE VIER KINDTABELLEN (Schritt 15/AP5, E-ZE-21) ---------------------
 *
 * Phasen, Reanimation, Rettungsmittel und Besatzung haengen mit
 * `ON DELETE CASCADE` am Einsatz und werden auf FUENF Wegen geschrieben:
 * Formular, CSV-Import, Uhr-Eingang, Backup-Wiederherstellung und Schneiden.
 * Dreissig Anweisungen, und die Bauform war nicht ueberall dieselbe:
 *
 *   Formular, Import, Uhr   loeschen und neu einfuegen („ersetzen")
 *   Backup                  nur einfuegen — der Einsatz ist gerade entstanden,
 *                           es gibt nichts zu loeschen; die Besatzung mit
 *                           `INSERT IGNORE`
 *   Schneiden               einfuegen im einen Zweig, loeschen im anderen
 *
 * Deshalb zwei Schalter statt zweier Funktionsformen: `loeschen` (Vorgabe
 * `true`) und, nur fuer die Besatzung, `ignorieren`. Eine leere Liste mit
 * `loeschen => true` ist das Loeschen; das ist der Zweig des Schneidens.
 *
 * SIE PRUEFEN NICHTS. Was gueltig ist, entscheidet weiter der Aufrufer — das
 * Formular ueber `validate_lib.php`, der Import und das Backup ueber
 * `pruef_*()`, die Uhr ueber `ingest.php`. Diese vier nehmen fertige Werte
 * und schreiben sie. Eine Pruefpolitik hier waere eine sechste, die neben
 * den vorhandenen stuende und mit ihnen auseinanderliefe.
 *
 * DIE TRANSAKTION GEHOERT DEM AUFRUFER. Alle fuenf Wege stehen ohnehin in
 * einer (`db_transaktion()` oder einer der neun benannten Ausnahmen); eine
 * eigene hier waere eine verschachtelte.
 */

/**
 * Eine vorbereitete Anweisung je Verbindung und SQL-Text.
 *
 * WARUM DAS SEIN MUSS: `db.php` setzt `ATTR_EMULATE_PREPARES => false`, also
 * ist JEDES `prepare()` ein Roundtrip zum Server. `api/import_commit.php`
 * bereitete seine neun Anweisungen deshalb EINMAL vor und fuehrte sie je
 * Einsatz aus — bis zu 3000-mal. Ein Funktionsaufruf je Einsatz, der selbst
 * vorbereitet, machte daraus bis zu 27 000 Roundtrips. Mit diesem
 * Zwischenspeicher kostet der zweite und jeder weitere Aufruf einen
 * Feldzugriff.
 *
 * DIE VERBINDUNG WIRD MITGEHALTEN, nicht nur ihre Objektkennung: Eine
 * freigegebene PDO gaebe ihre `spl_object_id` an die naechste weiter, und der
 * Zwischenspeicher lieferte dann eine Anweisung, die an einer toten
 * Verbindung haengt. Die Referenz haelt sie am Leben und schliesst das aus —
 * `tools/schemaprobe/` arbeitet mit mehreren Verbindungen nebeneinander.
 */
function einsatz_anweisung(PDO $pdo, string $sql): PDOStatement
{
    static $speicher = [];
    $schl = spl_object_id($pdo) . '|' . $sql;
    if (isset($speicher[$schl]) && $speicher[$schl][0] === $pdo) {
        return $speicher[$schl][1];
    }
    $st = $pdo->prepare($sql);
    $speicher[$schl] = [$pdo, $st];
    return $st;
}

/**
 * Phasen eines Einsatzes ersetzen.
 *
 * @param list<array{phase:int, occurred_at:string, lat?:float|null, lon?:float|null}> $phasen
 * @param array{loeschen?:bool} $o
 */
function einsatz_phasen_ersetzen(PDO $pdo, int $missionId, array $phasen, array $o = []): void
{
    if ($o['loeschen'] ?? true) {
        einsatz_anweisung($pdo, 'DELETE FROM mission_phases WHERE mission_id = ?')
            ->execute([$missionId]);
    }
    if ($phasen === []) { return; }
    /* IMMER FUENF SPALTEN. Das Formular schrieb drei und liess `lat`/`lon` auf
     * ihrem Vorgabewert; die Spalten sind `DOUBLE NULL` ohne DEFAULT, der
     * Vorgabewert ist also NULL. Ausdruecklich NULL zu schreiben legt
     * denselben Wert ab. */
    $ins = einsatz_anweisung($pdo, 'INSERT INTO mission_phases
        (mission_id, phase, occurred_at, lat, lon) VALUES (?,?,?,?,?)');
    foreach ($phasen as $p) {
        $ins->execute([$missionId, $p['phase'], $p['occurred_at'],
                       $p['lat'] ?? null, $p['lon'] ?? null]);
    }
}

/**
 * Reanimations-Sitzungen samt Ereignissen ersetzen.
 *
 * Die Ereignisse raeumt der Fremdschluessel mit ab (`ON DELETE CASCADE` auf
 * `resus_sessions`) — sie brauchen kein eigenes `DELETE`.
 *
 * @param list<array{started_at:string, events:list<array{0:string,1:string}>}> $sitzungen
 * @param array{loeschen?:bool} $o
 */
function einsatz_reas_ersetzen(PDO $pdo, int $missionId, array $sitzungen, array $o = []): void
{
    if ($o['loeschen'] ?? true) {
        einsatz_anweisung($pdo, 'DELETE FROM resus_sessions WHERE mission_id = ?')
            ->execute([$missionId]);
    }
    if ($sitzungen === []) { return; }
    $insS = einsatz_anweisung($pdo,
        'INSERT INTO resus_sessions (mission_id, started_at) VALUES (?,?)');
    $insE = einsatz_anweisung($pdo,
        'INSERT INTO resus_events (session_id, type, occurred_at) VALUES (?,?,?)');
    foreach ($sitzungen as $s) {
        $insS->execute([$missionId, $s['started_at']]);
        $sid = (int)$pdo->lastInsertId();
        foreach ($s['events'] as $e) { $insE->execute([$sid, $e[0], $e[1]]); }
    }
}

/**
 * Rettungsmittel-Zeilen ersetzen.
 *
 * @param list<string> $namen fertig beschnitten und entdoppelt
 * @param array{loeschen?:bool} $o
 */
function einsatz_rettungsmittel_ersetzen(PDO $pdo, int $missionId, array $namen, array $o = []): void
{
    if ($o['loeschen'] ?? true) {
        einsatz_anweisung($pdo, 'DELETE FROM mission_resources WHERE mission_id = ?')
            ->execute([$missionId]);
    }
    if ($namen === []) { return; }
    $ins = einsatz_anweisung($pdo,
        'INSERT INTO mission_resources (mission_id, name) VALUES (?,?)');
    foreach ($namen as $n) { $ins->execute([$missionId, $n]); }
}

/**
 * Besatzungsnamen ersetzen.
 *
 * @param array<string,string> $nachRolle Rollenkennung => Name, fertig beschnitten
 * @param array{loeschen?:bool, ignorieren?:bool} $o `ignorieren` = `INSERT IGNORE`
 *        (die Backup-Wiederherstellung; ein doppelter Rollenschluessel im Paket
 *        soll den Lauf nicht abbrechen)
 */
function einsatz_besatzung_ersetzen(PDO $pdo, int $missionId, array $nachRolle, array $o = []): void
{
    if ($o['loeschen'] ?? true) {
        einsatz_anweisung($pdo, 'DELETE FROM mission_crew WHERE mission_id = ?')
            ->execute([$missionId]);
    }
    if ($nachRolle === []) { return; }
    $ins = einsatz_anweisung($pdo, 'INSERT ' . (($o['ignorieren'] ?? false) ? 'IGNORE ' : '')
        . 'INTO mission_crew (mission_id, role_code, name) VALUES (?,?,?)');
    foreach ($nachRolle as $rolle => $name) { $ins->execute([$missionId, (string)$rolle, $name]); }
}
