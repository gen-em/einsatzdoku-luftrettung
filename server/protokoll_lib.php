<?php
declare(strict_types=1);
/**
 * DAS BETRIEBSPROTOKOLL — der Schreibweg (P5b/AP1, E-P5b-12, V1).
 *
 * ---------------------------------------------------------------------------
 * WAS HIER HINEINGESCHRIEBEN WIRD — UND WAS AUSDRUECKLICH NICHT
 * ---------------------------------------------------------------------------
 *
 * Betriebsereignisse. Konto angelegt, freigeschaltet, gesperrt, geloescht;
 * Rolle oder Adresse geaendert; Sicherung eingespielt; Wartung gefahren;
 * Schluesselblatt bestaetigt; Mail versandt; Job gelaufen.
 *
 * NICHT: wer wann welchen Einsatz geoeffnet, gelesen oder exportiert hat.
 * Das ist V1, entschieden am 16.09.2026, und es ist keine Nachlaessigkeit,
 * sondern die Zusage: Diese Anwendung fuehrt KEIN Zugriffsprotokoll. Wer hier
 * einen Eintrag „Einsatz 417 angesehen" ergaenzen will, aendert eine
 * Programmentscheidung und nicht eine Funktion.
 *
 * KEINE IP-ADRESSEN (V2, E-P5b-06). Sie stehen ausschliesslich im Reiter
 * *Sicherheit*, und der liegt in einer anderen Tabelle
 * (`sicherheit_ereignisse`, P5a/AP6) mit einer eigenen, festen Frist von 30
 * Tagen. Diese Trennung ist der Grund, warum P5b die bestehende Tabelle NICHT
 * anfasst: Zwei verschiedene Fristen und zwei verschiedene
 * Datenschutz-Begruendungen gehoeren nicht in eine Tabelle, die man
 * versehentlich gemeinsam aufbewahrt. Ob die beiden spaeter zusammenrueckten,
 * entscheidet 10c (V6).
 *
 * ---------------------------------------------------------------------------
 * WARUM DAS SCHEITERN DES SCHREIBENS DIE HANDLUNG NICHT SCHEITERN LAESST
 * ---------------------------------------------------------------------------
 *
 * V7, so entschieden: **Still scheitern ist schlechter als laut, laut
 * abbrechen ist schlechter als still.** Ein Protokoll, das eine
 * Kontoloeschung verhindert, weil seine Tabelle fehlt, ist ein Protokoll, das
 * den Betrieb anhaelt, um ueber den Betrieb zu berichten. Ein Protokoll, das
 * unbemerkt nichts schreibt, ist keines.
 *
 * Der Mittelweg hat drei Stufen, und alle drei muessen da sein:
 *   1. `error_log()` mit der Kennung `protokoll:` — fuer die Betreiberin, die
 *      ins Serverprotokoll sieht.
 *   2. Der Zaehler `protokoll_fehler` in `app_state` — er ueberlebt die
 *      Anfrage und laesst sich zaehlen.
 *   3. Der Hinweis auf der Statusseite — er faellt jemandem auf, der nicht
 *      sucht.
 *
 * ---------------------------------------------------------------------------
 * DIE SECHS REITER
 * ---------------------------------------------------------------------------
 *
 * `verwaltung` ist das Audit und der einzige mit einer langen, EINSTELLBAREN
 * Frist (365 Tage, 90 bis 1095). Die uebrigen fuenf verfallen nach 30 Tagen,
 * fest — dieselbe Zahl wie bei den Sperrereignissen und aus demselben Grund
 * (E-P5a-09): Betriebsdaten sollen nicht versehentlich Jahre liegen.
 *
 * LESBAR WIRD DAS PROTOKOLL ERST MIT 10c. 10b baut den Schreibweg und
 * schreibt hinein; bis dahin zeigt Betrieb -> Status eine Zaehlkarte
 * (Eintraege je Reiter, letzte 24 Stunden) — mehr nicht. Das ist kein
 * halbfertiger Zustand, sondern die Reihenfolge: Ein Reiter mit Archiv,
 * Filter und Download ist eine eigene Oberflaeche, und die haengt an
 * Entscheidungen (V4, V5, V8, V9), die noch nicht gefallen sind.
 *
 * ---------------------------------------------------------------------------
 * WAS `error_log()` NICHT ERSETZT
 * ---------------------------------------------------------------------------
 *
 * Die 42 `error_log()`-Aufrufe in 21 Dateien bleiben, wo sie sind. Sie
 * flaechendeckend auf `protokoll()` umzustellen waere Backlog Nr. 202 Paket 3
 * in anderem Gewand — und der richtige Zeitpunkt dafuer ist, wenn der Reiter
 * „System" steht und jemand die Eintraege auch lesen kann. Bis dahin waere es
 * ein Umbau ohne Nutzen und mit Risiko.
 */

require_once __DIR__ . '/db.php';

/* ---- Die Reiter ---------------------------------------------------------- */

/**
 * Die sechs Reiter. Die Reihenfolge ist die der Anzeige in 10c; sie steht
 * hier, damit die Zaehlkarte auf der Statusseite sie nicht selbst erfindet.
 *
 * `sicherheit` steht bewusst NICHT dabei — siehe Kopf.
 */
const PROTOKOLL_REITER = [
    'verwaltung' => 'Verwaltung',
    'email'      => 'E-Mail',
    'jobs'       => 'Jobs',
    'sicherung'  => 'Sicherung',
    'ziele'      => 'Ziele',
    'system'     => 'System',
];

/** Schluessel in `app_state`. */
const PROTOKOLL_K_FRIST_VERWALTUNG = 'protokoll_frist_verwaltung';
const PROTOKOLL_K_FEHLER           = 'protokoll_fehler';

/** Frist des Reiters *Verwaltung* in Tagen — Vorgabe und Grenzen (E-P5b-06). */
const PROTOKOLL_FRIST_VORGABE = 365;
const PROTOKOLL_FRIST_MIN     = 90;
const PROTOKOLL_FRIST_MAX     = 1095;

/** Frist aller uebrigen Reiter in Tagen. FEST, keine Einstellung. */
const PROTOKOLL_FRIST_UEBRIGE = 30;

/**
 * Wie lange die Verwaltungseintraege bleiben, in Tagen.
 *
 * Ein Wert ausserhalb der Grenzen faellt auf die Vorgabe zurueck, statt zu
 * gelten: Eine Frist von drei Tagen, die jemand verschrieben hat, raeumte das
 * Audit weg, bevor es jemand liest.
 */
function protokoll_frist_verwaltung(): int
{
    $v = app_state_lesen(PROTOKOLL_K_FRIST_VERWALTUNG);
    if ($v === null || $v === '' || !ctype_digit($v)) { return PROTOKOLL_FRIST_VORGABE; }
    $n = (int)$v;
    return ($n >= PROTOKOLL_FRIST_MIN && $n <= PROTOKOLL_FRIST_MAX)
        ? $n : PROTOKOLL_FRIST_VORGABE;
}

/** Die Frist setzen. `false`, wenn sie ausserhalb der Grenzen liegt. */
function protokoll_frist_verwaltung_setzen(int $tage): bool
{
    if ($tage < PROTOKOLL_FRIST_MIN || $tage > PROTOKOLL_FRIST_MAX) { return false; }
    return app_state_setzen(PROTOKOLL_K_FRIST_VERWALTUNG, (string)$tage);
}

/* ---- Der Schreibweg ------------------------------------------------------ */

/**
 * Ein Betriebsereignis vermerken.
 *
 * @param string   $reiter    einer aus `PROTOKOLL_REITER`
 * @param string   $art       kurze Kennung, z. B. `konto_angelegt` — sie ist
 *                            das, wonach 10c filtert, und deshalb maschinell
 *                            und nicht menschlich formuliert
 * @param string   $text      der Satz, den ein Mensch liest
 * @param array    $daten     zusaetzliche Angaben, als JSON abgelegt
 * @param int|null $betroffen Konto, um das es geht (nicht: wer gehandelt hat)
 *
 * @return bool `true`, wenn geschrieben wurde. **Der Rueckgabewert ist ein
 *         Hinweis und kein Grund abzubrechen** — siehe Kopf.
 *
 * DER URHEBER KOMMT AUS DER SITZUNG und wird nicht uebergeben: Ein Aufrufer,
 * der ihn selbst setzen darf, kann ihn auch falsch setzen, und ein Audit, in
 * dem der Urheber ein Parameter ist, ist kein Audit. Laeuft kein Mensch
 * (Kommandozeile, Huckepack-Job), steht `0` — und `urheber_art` sagt, welche
 * der beiden Arten es war. Ein `NULL` waere hier die schlechtere Wahl: Es
 * liesse offen, ob niemand gehandelt hat oder ob jemand vergessen wurde.
 */
function protokoll(string $reiter, string $art, string $text,
                   array $daten = [], ?int $betroffen = null): bool
{
    if (!isset(PROTOKOLL_REITER[$reiter])) {
        /* Ein unbekannter Reiter ist ein Programmierfehler und kein
         * Betriebszustand — er landet trotzdem nicht in einer Ausnahme,
         * sondern unter `system`, damit die Handlung weiterlaeuft und der
         * Eintrag nicht verlorengeht. Die Meldung sagt, wo zu suchen ist. */
        error_log('protokoll: unbekannter Reiter "' . $reiter . '" bei "' . $art
                . '" — der Eintrag steht unter "system".');
        $reiter = 'system';
    }

    /* URHEBER. `php_sapi_name() === 'cli'` unterscheidet den Notausgang auf
     * der Kommandozeile vom Huckepack-Lauf in einer Anfrage — beide haben
     * keine Sitzung, aber es ist nicht dasselbe: Der eine ist eine
     * Betreiberin an der Konsole, der andere ist niemand. */
    $urheber = (int)($_SESSION['user_id'] ?? 0);
    $urheberArt = $urheber > 0 ? 'mensch'
                : (PHP_SAPI === 'cli' ? 'cli' : 'job');

    try {
        $st = db()->prepare(
            'INSERT INTO protokoll_ereignisse
                 (reiter, art, urheber_user_id, urheber_art, betroffen_user_id, text, daten)
             VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute([
            $reiter, $art, $urheber, $urheberArt, $betroffen,
            /* Die Spalte ist TEXT, aber ein Satz, der eine Bildschirmseite
             * fuellt, ist kein Protokolleintrag. Gekuerzt wird hier und nicht
             * beim Lesen — sonst traegt die Datenbank, was niemand je sieht. */
            mb_substr($text, 0, 500),
            $daten === [] ? null : json_encode($daten, JSON_UNESCAPED_UNICODE),
        ]);
        return true;
    } catch (Throwable $ex) {
        protokoll_fehler_vermerken($ex->getMessage());
        return false;
    }
}

/**
 * Einen Fehlschlag des Schreibens festhalten — Protokoll, Zaehler, Statuszeile.
 *
 * WARUM DER ZAEHLER NICHT IN EINER TRANSAKTION MIT DEM EINTRAG STEHT: Er soll
 * gerade dann noch gehen, wenn der Eintrag nicht geht. Faellt auch er aus
 * (die Datenbank ist ganz weg), bleibt `error_log()` — und das ist dann die
 * richtige und einzige Antwort.
 */
function protokoll_fehler_vermerken(string $grund): void
{
    error_log('protokoll: Eintrag nicht geschrieben — ' . $grund);
    try {
        $alt = (int)(app_state_lesen(PROTOKOLL_K_FEHLER) ?? '0');
        app_state_setzen(PROTOKOLL_K_FEHLER, (string)($alt + 1));
    } catch (Throwable $ex) {
        /* Dann geht auch `app_state` nicht mehr. `error_log()` steht oben. */
    }
}

/** Wie oft ein Eintrag nicht geschrieben werden konnte. 0 = alles in Ordnung. */
function protokoll_fehler_zahl(): int
{
    return (int)(app_state_lesen(PROTOKOLL_K_FEHLER) ?? '0');
}

/** Den Zaehler zuruecksetzen (Betrieb -> Status, nachdem jemand hingesehen hat). */
function protokoll_fehler_quittieren(): bool
{
    return app_state_setzen(PROTOKOLL_K_FEHLER, '0');
}

/* ---- Lesen: nur so viel, wie die Zaehlkarte braucht ----------------------- */

/**
 * Eintraege je Reiter der letzten 24 Stunden, plus Gesamtzahl je Reiter.
 *
 * Mehr liest 10b nicht. Die Oberflaeche mit Reitern, Archiv und Download
 * kommt mit 10c (V4 bis V9).
 *
 * @return array{tag: array<string,int>, gesamt: array<string,int>, alle: int}
 */
function protokoll_zaehlkarte(): array
{
    $tag = []; $gesamt = [];
    foreach (array_keys(PROTOKOLL_REITER) as $r) { $tag[$r] = 0; $gesamt[$r] = 0; }
    $alle = 0;

    try {
        $st = db()->query(
            'SELECT reiter,
                    COUNT(*) AS gesamt,
                    SUM(zeit >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)) AS tag
               FROM protokoll_ereignisse
              GROUP BY reiter');
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $z) {
            $r = (string)$z['reiter'];
            if (!isset($tag[$r])) { continue; }
            $tag[$r]    = (int)$z['tag'];
            $gesamt[$r] = (int)$z['gesamt'];
            $alle      += (int)$z['gesamt'];
        }
    } catch (Throwable $ex) {
        /* Tabelle fehlt (Migration noch nicht gelaufen) — die Karte zeigt
         * dann Nullen und die Statusseite sagt, dass ein Update aussteht.
         * Das ist die richtige Antwort und kein Fehler dieser Funktion. */
    }

    return ['tag' => $tag, 'gesamt' => $gesamt, 'alle' => $alle];
}

/**
 * Die Bereinigung — zwei Fristen, ein Lauf (E-P5b-06).
 *
 * Wird aus `job_aufraeumen()` gerufen. Eigene Funktion, damit die Fristen an
 * EINER Stelle stehen und der Job sie nicht noch einmal auslegt.
 *
 * @return int Zahl der geloeschten Zeilen.
 */
function protokoll_bereinigen(PDO $pdo): int
{
    $weg = 0;

    /* Verwaltung: die lange, einstellbare Frist. */
    $st = $pdo->prepare(
        'DELETE FROM protokoll_ereignisse
          WHERE reiter = "verwaltung"
            AND zeit < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)');
    $st->execute([protokoll_frist_verwaltung()]);
    $weg += $st->rowCount();

    /* Alle uebrigen: 30 Tage, fest. `!=` statt einer Aufzaehlung der fuenf —
     * so faellt ein Reiter, den 10c ergaenzt, von selbst unter die kurze
     * Frist, statt unbegrenzt zu liegen. Das ist die sichere Richtung. */
    $st = $pdo->prepare(
        'DELETE FROM protokoll_ereignisse
          WHERE reiter != "verwaltung"
            AND zeit < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)');
    $st->execute([PROTOKOLL_FRIST_UEBRIGE]);
    $weg += $st->rowCount();

    return $weg;
}
