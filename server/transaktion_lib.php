<?php
declare(strict_types=1);

/**
 * TRANSAKTION — der eine Rahmen um eine Datenbanktransaktion
 * ===========================================================================
 *
 * Entstanden in Schritt 15 AP5 (Zentralisierung, R83, E-ZE-20) — damals in
 * `db.php`. Eigene Datei seit Web 20.37.3 (Backlog Nr. 288).
 *
 * WARUM EINE EIGENE DATEI. `install.php` legt das erste Konto ueber
 * `konto_anlegen()` an, und das laeuft seit Schritt 15 in diesem Rahmen. Der
 * Einrichter laeuft aber, BEVOR es eine `config.php` gibt, und `db.php`
 * verweigert sich ohne sie mit Absicht (Kopf dort). `konto_lib.php` laedt
 * `db.php` deshalb nur mit Konfiguration — und so stand `db_transaktion()`
 * genau dem einen Aufrufer nicht zur Verfuegung, fuer den die Klammer am
 * wichtigsten ist. Von Web 20.30.0 bis 20.37.2 liess sich keine neue Anlage
 * einrichten: „Call to undefined function db_transaktion()".
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

/* ---- EIN TRANSAKTIONSRAHMEN (Schritt 15/AP5, E-ZE-20) --------------------
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
 * Zeilen, gehoert zum Geraetevertrag und bekommt in Schritt 18 eine
 * Deadlock-Behandlung (Backlog Nr. 210). Schritt 15 fasst ihn nicht an.
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
