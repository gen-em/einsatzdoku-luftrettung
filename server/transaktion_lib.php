<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) { http_response_code(404); exit; }   // nur eingebunden (KA-05, F-KA-23)
/**
 * TRANSAKTION — der eine Rahmen um eine Datenbanktransaktion
 * ===========================================================================
 *
 * WARUM EINE EIGENE DATEI. `install.php` legt das erste Konto ueber
 * `konto_anlegen()` an, und das laeuft in diesem Rahmen. Der
 * Einrichter laeuft aber, BEVOR es eine `config.php` gibt, und `db.php`
 * verweigert sich ohne sie mit Absicht (Kopf dort). `konto_lib.php` laedt
 * `db.php` deshalb nur mit Konfiguration — in `db.php` stuende
 * `db_transaktion()` genau dem einen Aufrufer nicht zur Verfuegung, fuer den
 * die Klammer am wichtigsten ist: Keine neue Anlage liesse sich einrichten
 * („Call to undefined function db_transaktion()").
 *
 * DESHALB LAEDT DIESE DATEI NICHTS und braucht nichts ausser PDO — dieselbe
 * Bedingung wie bei `email_lib.php`, `konfig_lib.php` und `format_lib.php`.
 * `db.php` bindet sie ein, damit jeder bisherige Aufrufer sie ohne Aenderung
 * findet; `konto_lib.php` bindet sie UNBEDINGT ein.
 *
 * NIE EIN ZWEITES MAL DEFINIEREN, auch nicht als Rueckfall hinter
 * `function_exists()`. Die Erfolgsseite des Einrichters laedt `db.php` nach,
 * sobald `config.php` geschrieben ist; eine zweite Definition bricht dort mit
 * „Cannot redeclare" ab — NACHDEM `config.php` und `install.lock` stehen. Die
 * Anlage waere gesperrt, und der Einrichtungslink erschiene nie. Beide laden
 * deshalb per `require_once` genau diese Datei.
 */

/* ---- EIN TRANSAKTIONSRAHMEN -----------------------------------------------
 *
 * 33 Stellen in 22 Dateien schrieben denselben Rahmen von Hand, und der
 * Tokenizer hat sie in drei Bauformen sortiert:
 *
 *   19x beginnen, versuchen, bestaetigen, bei Fehler zurueckrollen und
 *       WEITERGEBEN — zwoelf werfen weiter, sieben antworten selbst
 *       (`json_fehler()`, `json_out()`);
 *   12x dasselbe, aber der `catch` SCHLUCKT und setzt stattdessen eine
 *       Meldung fuer die Seite;
 *    2x gar kein `try` — `beginTransaction()`, arbeiten, `commit()`. Bricht
 *       es dazwischen ab, bleibt die Transaktion offen, bis PHP sie beim
 *       Verbindungsabbau still zurueckrollt.
 *
 * WAS DABEI AUSEINANDERGELAUFEN IST, ist nicht die Absicht, sondern die
 * Sorgfalt: 14 der 42 `rollBack()`-Aufrufe stehen hinter einer Wache
 * (`inTransaction()` oder ein eigener Merker), 28 nicht. Ein `rollBack()` auf
 * einer Verbindung ohne offene Transaktion wirft — und zwar AUS DEM CATCH
 * HERAUS, womit die urspruengliche Ausnahme verlorengeht und im Protokoll
 * „There is no active transaction" steht statt des Grundes. Dieser Rahmen
 * fragt deshalb IMMER nach.
 *
 * VERSCHACHTELUNGSFEST, UND ZWAR ASYMMETRISCH: PDO kennt keine echten
 * verschachtelten Transaktionen; ein zweites `beginTransaction()` wirft. Wer
 * schon in einer fremden Transaktion steht, oeffnet deshalb keine eigene —
 * und bestaetigt und verwirft dann auch nichts. Das Zurueckrollen bleibt dem
 * ueberlassen, der begonnen hat; die Ausnahme kommt als Ausnahme heraus, und
 * er entscheidet. Neun Dateien hatten diesen Merker schon selbst gebaut
 * (`$eigeneTransaktion` in `backup_lib.php` erklaert ihn im Kommentar) — jetzt
 * steht er einmal.
 *
 * DIE AUSNAHME, NAMENTLICH: `ingest.php`. Sein Rahmen spannt sich ueber 670
 * Zeilen, gehoert zum Geraetevertrag und hat eine Deadlock-Behandlung: eine
 * Schleife mit hoechstens drei Anlaeufen um `beginTransaction()`/`try`, die
 * zwei Zeilen, die alle Pakete teilen, hinter dem Commit. Der Rahmen steht
 * dort und nicht hier als `db_transaktion_wiederholt()`, solange es den
 * einen Verbraucher gibt (zentralisiert wird beim zweiten).
 */

/**
 * Einen Rumpf in einer Transaktion laufen lassen.
 *
 * @template T
 * @param callable(PDO):T $fn bekommt dieselbe Verbindung uebergeben
 * @return T der Rueckgabewert des Rumpfs, unveraendert durchgereicht
 * @throws Throwable jede Ausnahme des Rumpfs, nach dem Zurueckrollen
 */
function db_transaktion(PDO $pdo, callable $fn): mixed {
    $eigene = !$pdo->inTransaction();
    if ($eigene) { $pdo->beginTransaction(); }
    try {
        $ergebnis = $fn($pdo);
        if ($eigene) { $pdo->commit(); }
        return $ergebnis;
    } catch (Throwable $ex) {
        /* NUR DIE EIGENE, UND NUR WENN SIE NOCH STEHT. Der zweite Teil ist
         * kein Uebereifer: Ein DDL-Befehl (`ALTER`, `CREATE`) bestaetigt in
         * MySQL still, und der Rumpf darf selbst zurueckgerollt haben. In
         * beiden Faellen wuerfe `rollBack()` hier eine ZWEITE Ausnahme und
         * verdeckte die erste. */
        if ($eigene && $pdo->inTransaction()) { $pdo->rollBack(); }
        throw $ex;
    }
}

/* ---- EIN GESCHLUCKTER FEHLER, DER DIE TRANSAKTION MITNAHM -----------------
 *
 * Mehrere Schreibhelfer fangen ihre Fehler mit Absicht: `protokoll()` (ein
 * Protokoll haelt den Betrieb nicht an), `app_state_setzen()` und seine zwei
 * Geschwister (eine fehlende Tabelle ist der Zustand jeder Anlage zwischen
 * Deploy und `update.php`). Das ist richtig, solange der Fehler nur die eine
 * Anweisung trifft.
 *
 * EIN DEADLOCK TRIFFT MEHR. InnoDB rollt dann die GANZE Transaktion zurueck
 * und beendet sie. Schluckt der Helfer das, laeuft der Rest des Rumpfs von
 * `db_transaktion()` im Autocommit weiter, und jede weitere Anweisung BLEIBT;
 * erst `commit()` wirft. Nachgestellt in `totp_abschalten()` (MariaDB 10.11,
 * Backlog Nr. 354): Faktor an, Codes und Geraete zurueck, die Passkeys
 * geloescht.
 *
 * DESHALB fragt der Helfer VOR dem Schreiben, ob eine Transaktion laeuft, und
 * reicht den Fehler im `catch` hierher. Ist es einer, der die Transaktion
 * beendet hat, wirft diese Funktion ihn weiter; der Rahmen gibt ihn an den
 * Aufrufer, und der Rest des Rumpfs laeuft nicht mehr.
 *
 * DER FEHLERCODE ENTSCHEIDET, NICHT `inTransaction()`. Gemessen am
 * 03.11.2026 (Konzept BR5, F-BR5-15): Nach dem Deadlock meldet PDO weiter
 * `inTransaction() === true`, der Server `@@in_transaction = 0` — PDO fuehrt
 * einen eigenen Merker und erfaehrt vom Zuruecksetzen durch InnoDB nichts.
 * `@@in_transaction` wiederum kennt nur MariaDB. Was die Transaktion beendet,
 * sagen die Dokumentationen von MySQL und MariaDB an den Codes:
 *   1213  Deadlock — die ganze Transaktion ist zurueckgerollt;
 *   2006, 2013  Verbindung weg — die Transaktion mit ihr.
 * Eine abgelaufene Sperrzeit (1205) rollt ohne `innodb_rollback_on_timeout`
 * nur die Anweisung zurueck; sie und jeder andere Fehler (fehlende Tabelle)
 * werden weiter geschluckt wie zuvor (E-BR5-04).
 */

/** Fehlercodes, nach denen keine Transaktion mehr steht. */
const DB_TRANSAKTION_ENDET = [1213, 2006, 2013];

/**
 * Den Fehler eines Schreibhelfers weiterwerfen, wenn er die Transaktion
 * beendet hat.
 *
 * @param bool $vorher lief vor dem Schreiben eine Transaktion?
 * @throws Throwable `$ex`, wenn ja und der Fehler sie beendet hat
 */
function db_transaktion_verloren(bool $vorher, Throwable $ex): void {
    if (!$vorher || !$ex instanceof PDOException) { return; }
    $code = (int)($ex->errorInfo[1] ?? 0);
    if (in_array($code, DB_TRANSAKTION_ENDET, true)) { throw $ex; }
}
