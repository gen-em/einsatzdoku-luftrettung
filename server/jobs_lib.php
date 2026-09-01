<?php
declare(strict_types=1);

/**
 * Hintergrundarbeit in Haeppchen — der Rahmen (Konzept S2, E-S2-17/18).
 *
 * WOFUER. Diese Anwendung hat bewusst keinen Cron: Sie soll auf einfachem
 * Webspace laufen, und dort gibt es oft keinen. Der einzige Zeitgeber war
 * bislang `run_cleanup_if_due()` — huckepack auf der Anfrage der ersten
 * Nutzerin des Tages. Das trug, solange die Arbeit klein war.
 *
 * Mit S2 wird sie das nicht mehr bleiben: Millionen Punkte verdichten und
 * ausduennen laesst sich nicht in einer Webanfrage erledigen. Gemessen hat
 * schon die heutige Waisenpruefung bei 9,46 Mio. Zeilen **4,07 Sekunden**
 * gekostet — in genau der Anfrage, die jemand gerade gestellt hat; bei der
 * Zielmenge Z2 (190 Mio. Zeilen) waeren es Minuten.
 *
 * Deshalb drei Dinge:
 *
 * 1. **Haeppchen.** Jeder Lauf hat ein Zeitbudget und hoert auf, wenn es
 *    erschoepft ist. Wo er stehengeblieben ist, merkt er sich in `jobs`.
 * 2. **Drei Ausloeser** (E-S2-17). Ein CLI-Cron ist der empfohlene Regelfall;
 *    wo es keinen gibt, tut es ein zeitgesteuerter Abruf der Adresse mit
 *    Token; und wo auch das nicht geht, laeuft es weiter huckepack. Damit
 *    bleibt die Hosterwahl offen, und die Anwendung bleibt in allen drei
 *    Faellen dieselbe.
 * 3. **Sichtbarkeit.** Die Wartungsseite zeigt je Job den letzten Lauf, den
 *    Ausloeser, den Rueckstand und den letzten Fehler. Ein Job, der
 *    dauerhaft scheitert, war bisher von einem laufenden nicht zu
 *    unterscheiden.
 *
 * WAS HIER NICHT STEHT: die Jobs selbst. Verdichtung und Ausduennung kommen
 * mit AP3; dieser Rahmen kennt sie ueber `jobs_katalog()` und sonst gar
 * nicht.
 */

require_once __DIR__ . '/db.php';

/**
 * Zeitbudget eines Haeppchens in Sekunden, je nach Ausloeser.
 *
 * ANFRAGE: 3 Sekunden. Hier wartet eine Nutzerin auf eine Seite. Zwanzig
 * Sekunden waeren zwar innerhalb der Z3-Grenze von 30 s je Anfrage — aber
 * eine Seite, die zwanzig Sekunden braucht, weil sie nebenbei aufraeumt, ist
 * kaputt, auch wenn kein Zeitlimit greift. Zusammen mit
 * JOB_ANFRAGE_PAUSE_S ist der Huckepack-Weg damit ein Rueckfall und keine
 * Last.
 *
 * TOKEN: 20 Sekunden, dieselbe Ueberlegung wie bei „Alle sichern"
 * (`admin_sicherungen.php`) — sie liegen unter der `max_execution_time`, die
 * geteilter Webspace ueblicherweise setzt. Dort wartet niemand auf eine
 * Seite; der Aufruf kommt von einem Zeitplandienst.
 *
 * CLI: 300 Sekunden. Dort gibt es keine wartende Nutzerin und meist keine
 * Laufzeitgrenze; ein Haeppchen darf laenger sein, damit ein Rueckstand in
 * weniger Laeufen abgetragen wird. Trotzdem endlich — ein Job, der nie
 * zurueckkehrt, laesst sich nicht ueberwachen.
 */
const JOB_BUDGET_ANFRAGE = 3.0;
const JOB_BUDGET_TOKEN   = 20.0;
const JOB_BUDGET_CLI     = 300.0;

/**
 * Mindestabstand zwischen zwei Laeufen desselben Jobs am HUCKEPACK-Weg.
 *
 * Ohne ihn liefe ein nicht-taeglicher Job bei JEDER angemeldeten Anfrage —
 * und jede Seite truege bis zu drei Sekunden Wartung mit. Fuenf Minuten sind
 * fuer ein Sicherheitsnetz reichlich; wer es schneller braucht, richtet einen
 * der beiden anderen Ausloeser ein, und genau das ist die Empfehlung
 * (E-S2-17).
 *
 * Fuer `cli` und `token` gilt der Abstand NICHT: Dort bestimmt der Zeitplan
 * die Haeufigkeit, und wer jede Minute aufruft, will das auch.
 */
const JOB_ANFRAGE_PAUSE_S = 300;

/**
 * Nach dieser Zeit gilt eine Laufsperre als verwaist.
 *
 * Ein Lauf, der mitten im Haeppchen abstuerzt (Speichergrenze, Zeitablauf,
 * abgebrochene Verbindung), hinterlaesst `laeuft_seit`. Ohne Verfall liefe
 * der Job nie wieder — und zwar stillschweigend, was der teuerste Fall ist.
 * Eine Stunde ist reichlich fuer jedes Haeppchen und kurz genug, dass ein
 * Absturz nicht den Tag kostet.
 */
const JOB_SPERRE_VERFALL_S = 3600;

/**
 * Ab wie viel belegtem Speicher ein Job sein Haeppchen beendet (S2/AP6).
 *
 * DER RAHMEN HAT BIS WEB 12.0.0 NUR DIE ZEIT GEMESSEN. Das reichte, solange
 * jeder Job in Bloecken ueber Zeilen lief — dort waechst der Speicher mit der
 * Blockgroesse und nicht mit dem Bestand. Der Sicherungsjob ist anders: Ein
 * einzelnes Konto kostet beim 5000er-Bestand 24 MB, und das ist die Groesse,
 * an der es klemmt, nicht die Sekunde.
 *
 * 48 von 64 MB (Z3): Was darueber liegt, reicht fuer ein weiteres Konto
 * womoeglich nicht mehr — und ein Abbruch mitten im Bau kostet mehr als ein
 * Haeppchen, das eines frueher aufhoert. Gemessen wird `true` (der vom System
 * belegte Block), denn das ist die Zahl, gegen die `memory_limit` prueft.
 */
const JOB_SPEICHER_DECKEL_MB = 48;

/** Ist das Speicherbudget des Haeppchens erschoepft? */
function jobs_speicher_knapp(): bool
{
    return memory_get_usage(true) >= JOB_SPEICHER_DECKEL_MB * 1024 * 1024;
}

/** Schluessel des Token in `app_state`. */
const JOB_TOKEN_SCHLUESSEL = 'jobs_token';

/**
 * Schluessel der Laufpause in `app_state` (UTC-Zeitstempel, bis wann).
 *
 * WOFUER (S2/AP3). Seit die Jobs Zeilen LOESCHEN und Blobs ERSETZEN, aendern
 * sie waehrend ihres Laufs den Bestand — und das trifft jede Messung, die
 * gerade nebenher laeuft. Aufgefallen ist es am Kreislauf: Er spielt eine
 * Sicherung in ein frisches Konto und exportiert sie sofort wieder. Die
 * wiederhergestellten Einsaetze sind alt; der Verdichtungsjob haelt sie fuer
 * reif, verdichtet sie, und der Ausduennungsjob duennt aus, was aelter als
 * sechs Monate ist. Der Vergleich misst dann nicht mehr „kommt zurueck, was
 * hineinging", sondern „hat der Job dazwischen zugeschlagen" — und je nach
 * Laufzeit faellt er mal so und mal so aus.
 *
 * Beim ersten Lauf nach AP3 war er sauber, aber nur ZUFAELLIG: Der
 * Mindestabstand des Huckepack-Wegs hatte gerade gegriffen. Eine Zahl, die
 * vom Zufall abhaengt, ist kein Beleg.
 *
 * Die Pause gilt fuer ALLE drei Ausloeser, nicht nur fuer den Huckepack-Weg —
 * sonst raeumte ein Cron weg, was die Messung braucht. Sie laeuft von selbst
 * ab; eine Pause ohne Ende waere eine, die jemand vergisst. Und die
 * Wartungsseite zeigt sie an, damit eine vergessene Pause nicht aussieht wie
 * ein arbeitender Job.
 *
 * Im Betrieb ist sie ebenfalls nuetzlich: Wer eine grosse Sicherung
 * einspielt, will die Jobs so lange still haben.
 */
const JOB_PAUSE_SCHLUESSEL = 'jobs_pause_bis';

/** Laenger als das laesst sich nicht am Stueck pausieren. */
const JOB_PAUSE_MAX_S = 7200;

/**
 * Die Jobs anhalten — bis zu JOB_PAUSE_MAX_S Sekunden.
 *
 * @param int $sekunden 0 = Pause sofort aufheben
 */
function jobs_pause(int $sekunden): void
{
    $pdo = db();
    if ($sekunden <= 0) {
        $pdo->prepare('DELETE FROM app_state WHERE k = ?')->execute([JOB_PAUSE_SCHLUESSEL]);
        return;
    }
    $bis = (new DateTime('now', new DateTimeZone('UTC')))
         ->modify('+' . min($sekunden, JOB_PAUSE_MAX_S) . ' seconds')
         ->format('Y-m-d H:i:s');
    $pdo->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE v = VALUES(v)')
        ->execute([JOB_PAUSE_SCHLUESSEL, $bis]);
}

/** Bis wann sind die Jobs angehalten? null = sie laufen. */
function jobs_pause_bis(): ?string
{
    try {
        $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute([JOB_PAUSE_SCHLUESSEL]);
        $wert = (string)($st->fetchColumn() ?: '');
    } catch (Throwable $ex) { return null; }
    if ($wert === '') { return null; }
    $bis = new DateTime($wert, new DateTimeZone('UTC'));
    return $bis > new DateTime('now', new DateTimeZone('UTC')) ? $wert : null;
}

/* ---- Katalog ------------------------------------------------------------- */

/**
 * Alle bekannten Jobs.
 *
 * Je Job:
 *   titel        fuer die Wartungsseite
 *   beschreibung was er tut, in einem Satz
 *   taeglich     true  = hoechstens einmal je Kalendertag (Aufraeumarbeiten)
 *                false = laeuft, solange es Rueckstand gibt
 *   rueckstand   fn(PDO, array $zustand): ?int — wie viel steht aus?
 *                null = nicht zaehlbar. Bekommt den FRISCHEN Zustand des
 *                soeben gelaufenen Haeppchens, nicht den aus der Tabelle:
 *                der ist zu diesem Zeitpunkt noch der alte.
 *   lauf         fn(PDO, array $zustand, callable $zeitLinks): array
 *                gibt ['zustand' => array, 'erledigt' => int, 'fertig' => bool]
 *                zurueck. `fertig` heisst: fuer diesmal ist nichts mehr zu
 *                tun. `$zeitLinks()` liefert die verbleibenden Sekunden — wer
 *                laenger arbeitet, als er hat, bringt die Anfrage um.
 */
function jobs_katalog(): array
{
    $katalog = [
        'aufraeumen' => [
            'titel'        => 'Aufräumen',
            'beschreibung' => 'Papierkorb, Kopplungscodes, Ratenschutz, '
                            . 'Passwort-Token, Erinnerung an die Administration',
            'taeglich'     => true,
            'rueckstand'   => fn(PDO $pdo, array $z): ?int => null,
            'lauf'         => 'job_aufraeumen',
        ],
        'verdichtung' => [
            'titel'        => 'Spuren verdichten',
            'beschreibung' => 'Abgeschlossene Spuren von Zeilen in den '
                            . 'verlustfreien Blob — eine Transaktion je Spur, '
                            . 'Rundlaufprüfung vor dem Löschen',
            'taeglich'     => false,
            'rueckstand'   => 'job_verdichtung_rueckstand',
            'lauf'         => 'job_verdichtung',
        ],
        'ausduennen' => [
            'titel'        => 'Spuren ausdünnen',
            'beschreibung' => 'Sechs Monate nach Einsatzende: Douglas-Peucker '
                            . '2 m waagerecht / 3 m senkrecht, Phasenpunkte '
                            . 'bleiben erhalten',
            'taeglich'     => false,
            'rueckstand'   => 'job_ausduennen_rueckstand',
            'lauf'         => 'job_ausduennen',
        ],
        /* DER SICHERUNGSJOB STEHT VOR `waisen` UND NACH DER SPURARBEIT.
         *
         * Er arbeitet nur, wenn ein Auftrag vorliegt („Alle sichern"); ohne
         * Auftrag kostet er eine Abfrage. Er darf deshalb weit vorn stehen,
         * ohne den anderen Jobs im Regelfall Budget wegzunehmen — und wenn er
         * etwas zu tun hat, ist es das, worauf jemand gerade wartet. */
        'adminbackup' => [
            'titel'        => 'Sicherungen aller Konten',
            'beschreibung' => 'Den Auftrag „Alle sichern" in Schüben abarbeiten '
                            . '— je Konto ein Paket, mit Wiederaufnahme',
            'taeglich'     => false,
            'rueckstand'   => 'job_adminbackup_rueckstand',
            'lauf'         => 'job_adminbackup',
        ],
        /* DER VERSAND STEHT NACH DEM SICHERN UND VOR `waisen` (S2/AP7).
         * Nach dem Sichern, weil er schickt, was jenes erzeugt hat — in
         * derselben Reihenfolge kommt ein frisches Paket noch im selben Lauf
         * hinaus. Vor `waisen` aus demselben Grund wie dort beschrieben.
         *
         * Ohne aktives Ziel kostet er eine Abfrage und ist fertig. */
        'versand' => [
            'titel'        => 'Sicherungen versenden',
            'beschreibung' => 'Neue Pakete auf die aktiven Sicherungsziele '
                            . 'schieben — nur was dort fehlt, und nichts wird '
                            . 'dort gelöscht',
            'taeglich'     => false,
            'rueckstand'   => 'job_versand_rueckstand',
            'lauf'         => 'job_versand',
        ],
        /* DIE KOMPLETTSICHERUNG STEHT NACH DEM VERSAND, nicht davor — und das
         * kostet bewusst einen Lauf.
         *
         * Davor waere sie am rechten Platz: Was hier entsteht, ginge im selben
         * Lauf hinaus. Nur ist sie die schwerste Arbeit dieser Anwendung, und
         * jeder Job hinter ihr bekaeme nur noch, was sie uebrig laesst. Ein
         * Versand, der wochenlang nicht drankommt, weil vor ihm eine
         * Datenbank abgeschrieben wird, waere der teurere Fehler: Die
         * Sicherung laege dann zwar da, aber nur hier.
         *
         * Der Preis ist, dass ein frischer Stand erst im NAECHSTEN Lauf
         * hinausgeht — bei taeglichem Cron also am Tag darauf. Zusaetzlich
         * begrenzt sich der Job auf KOMP_LAUF_MAX_S, damit auch `waisen`
         * hinter ihm noch zum Zug kommt. */
        'komplett' => [
            'titel'        => 'Komplettsicherung der Installation',
            'beschreibung' => 'Die ganze Datenbank als versiegelter SQL-Dump '
                            . '— tabellenweise in Häppchen, mit Wiederaufnahme',
            'taeglich'     => false,
            'rueckstand'   => 'job_komplett_rueckstand',
            'lauf'         => 'job_komplett',
        ],
        'waisen' => [
            'titel'        => 'Verwaiste Spuren',
            'beschreibung' => 'Spurpunkte und Blobs ohne Eigentümer entfernen '
                            . '— bereichsweise über den Primärschlüssel',
            'taeglich'     => false,
            'rueckstand'   => 'job_waisen_rueckstand',
            'lauf'         => 'job_waisen',
        ],
    ];

    /* DIE REIHENFOLGE IST ABSICHT. `jobs_lauf()` arbeitet den Katalog der
     * Reihe nach ab und ueberspringt, was ins Restbudget nicht mehr passt.
     * `waisen` ist nach eigener Auskunft ein Sicherheitsnetz und kein
     * Hauptweg — die eigentliche Arbeit gehoert deshalb nach vorn, sonst
     * bekaeme sie am Huckepack-Weg (3 s) nur noch den Rest. */
    return $katalog;
}

/* ---- Ausfuehrung --------------------------------------------------------- */

/**
 * Faellige Jobs abarbeiten, bis das Budget erschoepft ist.
 *
 * @param string $ausloeser 'cli' | 'token' | 'anfrage'
 * @param list<string>|null $nur nur diese Jobs (sonst alle faelligen)
 * @return array<string,array> je Job ein Bericht
 */
function jobs_lauf(string $ausloeser, ?array $nur = null): array
{
    $budget = match ($ausloeser) {
        'cli'   => JOB_BUDGET_CLI,
        'token' => JOB_BUDGET_TOKEN,
        default => JOB_BUDGET_ANFRAGE,
    };
    $start  = microtime(true);
    $zeitLinks = fn(): float => $budget - (microtime(true) - $start);

    $pause = jobs_pause_bis();
    if ($pause !== null) {
        $aus = [];
        foreach (jobs_katalog() as $name => $job) {
            if ($nur !== null && !in_array($name, $nur, true)) { continue; }
            $aus[$name] = ['uebersprungen' => 'angehalten bis ' . $pause . ' UTC'];
        }
        return $aus;
    }

    $bericht = [];
    foreach (jobs_katalog() as $name => $job) {
        if ($nur !== null && !in_array($name, $nur, true)) { continue; }
        if ($zeitLinks() <= 1.0) {
            $bericht[$name] = ['uebersprungen' => 'kein Budget mehr'];
            continue;
        }
        $bericht[$name] = jobs_einen_lauf($name, $job, $ausloeser, $zeitLinks);
    }
    return $bericht;
}

/**
 * Einen Job laufen lassen — mit Sperre, Zustand und Fehlerprotokoll.
 *
 * DIE SPERRE WIRD VOR DER ARBEIT GESETZT und in derselben Anweisung geprueft
 * (bedingtes UPDATE). Ein `SELECT … dann UPDATE` haette ein Zeitfenster, in
 * dem zwei Anfragen beide zu dem Schluss kommen, sie duerften.
 */
function jobs_einen_lauf(string $name, array $job, string $ausloeser,
                         callable $zeitLinks): array
{
    $pdo = db();
    $heute = (new DateTime('now'))->format('Y-m-d');

    // Zeile anlegen, falls es sie noch nicht gibt.
    $pdo->prepare('INSERT IGNORE INTO jobs (job) VALUES (?)')->execute([$name]);

    /* Faellig? Und Sperre setzen — in EINER Anweisung.
     *
     * Die Bedingungen:
     *   - keine gueltige Sperre (nie gelaufen, oder Sperre verfallen)
     *   - bei taeglichen Jobs zusaetzlich: heute noch nicht gelaufen
     */
    $sql = 'UPDATE jobs
               SET laeuft_seit = UTC_TIMESTAMP(),
                   letzter_ausloeser = ?
             WHERE job = ?
               AND (laeuft_seit IS NULL
                    OR laeuft_seit < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? SECOND))';
    $p = [$ausloeser, $name, JOB_SPERRE_VERFALL_S];
    if (!empty($job['taeglich'])) {
        $sql .= ' AND (letzter_lauf IS NULL OR DATE(letzter_lauf) < ?)';
        $p[] = $heute;
    } elseif ($ausloeser === 'anfrage') {
        /* Am Huckepack-Weg zusaetzlich der Mindestabstand. Ohne ihn traege
         * JEDE angemeldete Anfrage ein Haeppchen Wartung mit. */
        $sql .= ' AND (letzter_lauf IS NULL
                       OR letzter_lauf < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? SECOND))';
        $p[] = JOB_ANFRAGE_PAUSE_S;
    }
    $st = $pdo->prepare($sql);
    $st->execute($p);
    if ($st->rowCount() === 0) {
        return ['uebersprungen' => !empty($job['taeglich'])
            ? 'heute schon gelaufen'
            : ($ausloeser === 'anfrage'
                ? 'läuft bereits oder Mindestabstand noch nicht erreicht'
                : 'läuft bereits')];
    }

    // Fortsetzungsmarke holen.
    $z = $pdo->prepare('SELECT zustand FROM jobs WHERE job = ?');
    $z->execute([$name]);
    $zustand = json_decode((string)($z->fetchColumn() ?: '{}'), true);
    if (!is_array($zustand)) { $zustand = []; }

    $erledigt = 0; $fertig = false; $fehler = null;
    try {
        $e = ($job['lauf'])($pdo, $zustand, $zeitLinks);
        $zustand  = $e['zustand'] ?? [];
        $erledigt = (int)($e['erledigt'] ?? 0);
        $fertig   = (bool)($e['fertig'] ?? false);
    } catch (Throwable $ex) {
        $fehler = get_class($ex) . ': ' . $ex->getMessage();
        // Still gegenueber der Anfrage — die Wartung darf keine Seite
        // kaputtmachen —, aber nachlesbar, und ab jetzt auch sichtbar.
        error_log("jobs: \"$name\" fehlgeschlagen: $fehler");
    }

    /* Rueckstand fuer die Anzeige — MIT DEM FRISCHEN ZUSTAND.
     *
     * Die erste Fassung rief `rueckstand($pdo)` und liess die Funktion den
     * Zustand aus der Tabelle lesen. Dort stand aber noch der ALTE: Geschrieben
     * wird er erst zwei Zeilen weiter unten. Der Waisenjob meldete deshalb
     * direkt nach einem vollstaendigen Durchlauf „Rueckstand 33093" — die
     * ganze Tabelle als ausstehend, obwohl er gerade fertig geworden war.
     * Derselbe Reihenfolgefehler wie in der Serverprobe des Messstands. */
    $rueckstand = null;
    if (is_callable($job['rueckstand'] ?? null)) {
        try { $rueckstand = ($job['rueckstand'])($pdo, $zustand); }
        catch (Throwable $ex) { /* eine Zahl fuer die Anzeige ist es nicht wert */ }
    }

    $pdo->prepare('UPDATE jobs
                      SET zustand = ?, rueckstand = ?, letzter_lauf = UTC_TIMESTAMP(),
                          letzter_erfolg = CASE WHEN ? IS NULL THEN UTC_TIMESTAMP()
                                                ELSE letzter_erfolg END,
                          letzter_fehler = ?, erledigt_zuletzt = ?,
                          laeuft_seit = NULL
                    WHERE job = ?')
        ->execute([json_encode($zustand), $rueckstand, $fehler, $fehler,
                   $erledigt, $name]);

    return ['erledigt' => $erledigt, 'fertig' => $fertig,
            'rueckstand' => $rueckstand, 'fehler' => $fehler];
}

/** Zustand aller Jobs — fuer die Wartungsseite. */
function jobs_zustand(): array
{
    $pdo = db();
    $zeilen = [];
    try {
        foreach ($pdo->query('SELECT * FROM jobs')->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $zeilen[$r['job']] = $r;
        }
    } catch (Throwable $ex) {
        // Vor der Migration gibt es die Tabelle nicht. Das ist kein Fehler,
        // sondern der Zustand vor dem ersten update.php-Aufruf.
        return [];
    }
    $aus = [];
    foreach (jobs_katalog() as $name => $job) {
        $aus[$name] = ($zeilen[$name] ?? []) + [
            'job' => $name, 'titel' => $job['titel'],
            'beschreibung' => $job['beschreibung'],
            'zustand' => null, 'rueckstand' => null, 'letzter_lauf' => null,
            'letzter_erfolg' => null, 'letzter_ausloeser' => null,
            'letzter_fehler' => null, 'erledigt_zuletzt' => 0, 'laeuft_seit' => null,
        ];
    }
    return $aus;
}

/* ---- Token fuer den Abruf ueber die Adresse ------------------------------ */

/**
 * Das Token fuer `jobs.php?token=…` — erzeugt es beim ersten Lesen.
 *
 * WARUM IN `app_state` UND NICHT IN `config.php`. Die Anwendung schreibt
 * `config.php` genau einmal, bei der Einrichtung; sie danach anzufassen
 * hiesse, auf jedem Webspace Schreibrecht auf die eigene Konfiguration zu
 * brauchen. Bestandsinstallationen haetten ausserdem kein Token, und niemand
 * saehe, warum der zeitgesteuerte Abruf nicht geht.
 *
 * Das Token ist ein Geheimnis wie ein Passwort — 32 Byte Zufall, hex. Wer es
 * hat, kann die Wartung anstossen; mehr nicht. Er kann damit weder Daten
 * lesen noch schreiben, und der Ratenschutz begrenzt die Versuche.
 */
function jobs_token(bool $neu = false): string
{
    $pdo = db();
    if (!$neu) {
        $st = $pdo->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute([JOB_TOKEN_SCHLUESSEL]);
        $wert = $st->fetchColumn();
        if (is_string($wert) && $wert !== '') { return $wert; }
    }
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE v = VALUES(v)')
        ->execute([JOB_TOKEN_SCHLUESSEL, $token]);
    return $token;
}

/* ---- Die Jobs selbst ----------------------------------------------------- */

/**
 * Aufraeumen — die taeglichen Schritte, die bisher in `run_cleanup_if_due()`
 * standen.
 *
 * Jeder Schritt hat weiterhin seinen eigenen Fehlerblock: Einer, der
 * scheitert, haelt die anderen nicht auf. Der Unterschied zu vorher ist, dass
 * das Ergebnis in `jobs` landet statt nur im Fehlerprotokoll.
 */
function job_aufraeumen(PDO $pdo, array $zustand, callable $zeitLinks): array
{
    $schritte = [
        'Kopplungscodes' => function (PDO $pdo): void {
            $pdo->exec('DELETE FROM pair_codes
                        WHERE used_at IS NOT NULL
                           OR created_at < DATE_SUB(NOW(), INTERVAL ' . PAIR_TTL_MIN . ' MINUTE)');
        },
        'Sperrliste geloeschter Kennungen' => function (PDO $pdo): void {
            $pdo->exec('DELETE FROM deleted_refs
                        WHERE deleted_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
        },
        'Ratenschutz-Zaehler' => function (PDO $pdo): void {
            $pdo->exec('DELETE FROM rate_limits
                        WHERE fenster_start < DATE_SUB(NOW(), INTERVAL 1 DAY)
                          AND (gesperrt_bis IS NULL OR gesperrt_bis < NOW())');
        },
        'Papierkorb' => function (PDO $pdo): void {
            require_once __DIR__ . '/trash_lib.php';
            trash_purge_expired($pdo);
        },
        'Passwort-Tokens' => function (PDO $pdo): void {
            $pdo->exec('DELETE FROM password_resets
                        WHERE used_at IS NOT NULL
                           OR expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)');
        },
        'Erinnerung an die Administration' => function (PDO $pdo): void {
            require_once __DIR__ . '/adminbackup_lib.php';
            edbak_erinnerung_planen();
        },
    ];

    $fehler = [];
    foreach ($schritte as $name => $schritt) {
        try { $schritt($pdo); }
        catch (Throwable $ex) {
            $fehler[] = $name . ': ' . $ex->getMessage();
            error_log('jobs: Aufraeumschritt "' . $name . '" fehlgeschlagen: '
                      . $ex->getMessage());
        }
    }
    if ($fehler) {
        throw new RuntimeException(implode(' · ', $fehler));
    }
    return ['zustand' => [], 'erledigt' => count($schritte), 'fertig' => true];
}

/**
 * Verwaiste Spuren — BEREICHSWEISE statt als Vollscan (E-S2-18).
 *
 * WAS DARAN NEU IST. Bis Web 10.0.0 lief hier ein Anti-Join ueber die GANZE
 * Tabelle:
 *
 *     DELETE tp FROM track_points tp
 *     LEFT JOIN missions m ON m.id = tp.owner_id
 *     WHERE tp.owner_type = 'mission' AND m.id IS NULL
 *
 * Gemessen kostete das bei 9,46 Mio. Zeilen 4,07 Sekunden — in der Anfrage
 * der ersten Nutzerin des Tages. Bei der Zielmenge Z2 (190 Mio. Zeilen) waeren
 * es Minuten, und niemand wuesste, warum die Seite haengt.
 *
 * Jetzt wandert eine Marke ueber den Primaerschluessel. Je Haeppchen werden
 * hoechstens JOB_WAISEN_BLOCK Eigentuemerkennungen angesehen; der Index
 * traegt die Abfrage, und der Lauf hoert auf, wenn das Budget zu Ende ist.
 * Am Ende der Tabelle faengt die Marke wieder von vorn an — es ist ein
 * Sicherheitsnetz, kein Hauptweg: Seit AP1 raeumen die Loeschwege selbst ab
 * (F-S2-B).
 */
const JOB_WAISEN_BLOCK = 2000;

function job_waisen(PDO $pdo, array $zustand, callable $zeitLinks): array
{
    require_once __DIR__ . '/spur_lib.php';

    $erledigt = 0;
    $fertig = true;

    foreach ([['mission', 'missions'], ['rest', 'rest_segments']] as [$typ, $tabelle]) {
        $marke = (int)($zustand[$typ] ?? 0);
        while (true) {
            if ($zeitLinks() <= 2.0) { $fertig = false; break; }

            /* Die naechsten Eigentuemerkennungen — aus BEIDEN Tabellen, denn
             * eine Waise kann als Zeile, als Blob oder als beides dastehen.
             * Getrennt gefragt und in PHP zusammengelegt: Ein UNION mit zwei
             * ORDER BY … LIMIT waere hier zwar moeglich, aber schwerer zu
             * lesen als der Gewinn wert ist — beide Abfragen laufen auf dem
             * Primaerschluessel und holen hoechstens JOB_WAISEN_BLOCK Zeilen. */
            $ids = [];
            $st = $pdo->prepare('SELECT DISTINCT owner_id FROM track_points
                                  WHERE owner_type = ? AND owner_id > ?
                                  ORDER BY owner_id LIMIT ' . JOB_WAISEN_BLOCK);
            $st->execute([$typ, $marke]);
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $i) { $ids[(int)$i] = true; }

            $st = $pdo->prepare('SELECT owner_id FROM track_blobs
                                  WHERE owner_type = ? AND owner_id > ?
                                  ORDER BY owner_id LIMIT ' . JOB_WAISEN_BLOCK);
            $st->execute([$typ, $marke]);
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $i) { $ids[(int)$i] = true; }

            if (!$ids) {
                /* Ende der Tabelle. Die Marke faengt wieder von vorn an: Das
                 * hier ist ein Sicherheitsnetz, kein Hauptweg — seit AP1
                 * raeumen die Loeschwege selbst ab (F-S2-B). Ein Netz, das
                 * einmal durchlaeuft und dann liegen bleibt, ist keines.
                 *
                 * `durch` haelt fest, DASS der Durchlauf zu Ende kam. Ohne
                 * diese Marke waere eine Marke von 0 nicht zu unterscheiden
                 * von „noch nie gelaufen" — und der Rueckstand meldete nach
                 * einem vollstaendigen Durchlauf die ganze Tabelle als
                 * ausstehend. */
                $zustand[$typ] = 0;
                $zustand['durch'][$typ] = true;
                break;
            }
            $zustand['durch'][$typ] = false;
            $ids = array_keys($ids);
            sort($ids);
            /* Beide Abfragen haben je bis zu BLOCK Kennungen geliefert; die
             * Vereinigung kann groesser sein. Auf BLOCK kuerzen, damit die
             * Marke lueckenlos weiterwandert — der Rest kommt im naechsten
             * Durchgang. */
            $ids = array_slice($ids, 0, JOB_WAISEN_BLOCK);

            // Welche davon gibt es noch? EINE Abfrage fuer den ganzen Block.
            $platz = implode(',', array_fill(0, count($ids), '?'));
            $da = $pdo->prepare("SELECT id FROM `$tabelle` WHERE id IN ($platz)");
            $da->execute($ids);
            $vorhanden = array_flip(array_map('intval', $da->fetchAll(PDO::FETCH_COLUMN)));
            $waisen = array_values(array_filter($ids, fn($i) => !isset($vorhanden[$i])));

            if ($waisen) {
                $weg = spur_loeschen($pdo, $typ, $waisen);
                $erledigt += $weg['zeilen'] + $weg['blobs'];
            }
            $marke = (int)end($ids);
            $zustand[$typ] = $marke;
        }
    }
    return ['zustand' => $zustand, 'erledigt' => $erledigt, 'fertig' => $fertig];
}

/**
 * Wie weit ist die Marke? — NICHT: wie viele Waisen gibt es.
 *
 * Die naheliegende Zahl waere „Eigentuemer ohne Zeile in missions" — und die
 * kostet genau den Vollscan, den dieser Job abschafft. Fuer eine Anzeige ist
 * das der falsche Preis.
 *
 * Stattdessen der Fortschritt: wie viele Kennungen die Marke noch vor sich
 * hat. Beide Abfragen laufen auf dem Primaerschluessel.
 */
function job_waisen_rueckstand(PDO $pdo, array $z): ?int
{
    $offen = 0;
    foreach (['mission', 'rest'] as $typ) {
        // Durchlauf abgeschlossen? Dann steht nichts aus.
        if (!empty($z['durch'][$typ])) { continue; }

        $st = $pdo->prepare('SELECT MAX(owner_id) FROM track_points WHERE owner_type = ?');
        $st->execute([$typ]);
        $max = (int)($st->fetchColumn() ?: 0);
        $st = $pdo->prepare('SELECT MAX(owner_id) FROM track_blobs WHERE owner_type = ?');
        $st->execute([$typ]);
        $max = max($max, (int)($st->fetchColumn() ?: 0));

        $offen += max(0, $max - (int)($z[$typ] ?? 0));
    }
    return $offen;
}

/* ---- Verdichten und Ausduennen (S2/AP3) ---------------------------------- */

/**
 * Kandidaten je Haeppchen.
 *
 * NICHT JOB_WAISEN_BLOCK (2000). Der Waisenjob materialisiert nie Punkte — er
 * liest Kennungen und loescht. Die Verdichtung muss jede Kandidatenspur
 * wirklich lesen, und eine Punktliste kostet in PHP gemessen 237 bis 294 Byte
 * je Punkt. Bei 524 Punkten je Spur (Messstand) waeren 2000 Spuren rund
 * 300 MB; 200 Spuren sind rund 25 MB, und weil Spur fuer Spur gelesen wird,
 * ist die tatsaechliche Spitze die EINER Spur — gemessen 4,0 MB.
 */
const JOB_VERDICHTUNG_BLOCK = 200;

/** Dasselbe fuer die Ausduennung; dort werden Blobkoepfe gelesen, keine Punkte. */
const JOB_AUSDUENNEN_BLOCK = 500;

/**
 * Reserve, unter der keine neue Spur mehr angefangen wird.
 *
 * Abgebrochen wird IMMER vor der naechsten Spur, nie mitten in einer — sonst
 * stuende eine halbe Verdichtung da. Die groesste erlaubte Spur
 * (LIMIT_TRACKPUNKTE_SPUR = 50 000) kostet gemessen 0,64 s Kodieren plus
 * 0,04 s Rundlauf; mit dem DELETE bleibt sie unter 1,0 s.
 */
const JOB_VERDICHTUNG_RESERVE_S = 1.0;

/** Hoechstens so viele Kennungen je Sammelliste auf der Wartungsseite. */
const JOB_LISTE_MAX = 50;

/**
 * Verdichten: Stufe 1 -> Stufe 2 (Konzept 3.1.4, E-S2-06/07).
 *
 * DER EINSTIEG KOMMT VON DER PUNKTSEITE, wie beim Waisenjob, und das ist
 * keine Bequemlichkeit: Die Menge „final = 1 und Ankunft aelter als 14 Tage"
 * enthaelt JEDEN je abgeschlossenen Einsatz, auch alle laengst verdichteten.
 * Sie waechst monoton; ein Index darauf faende bei Z2 Millionen Zeilen, von
 * denen 99,9 % nichts mehr zu tun haben. Der Punkteinstieg dagegen raeumt
 * seinen eigenen Vorrat ab — eine verdichtete Spur hat keine Zeilen mehr und
 * erscheint nie wieder. Uebrig bleibt nur der Rueckstand: Spuren in Karenz,
 * ohne `final`, mit Luecke, im Papierkorb. Und der noetige Index gibt es
 * bereits: den Primaerschluessel.
 *
 * ES WIRD NICHT AUSGEDUENNT. Konzept 3.1.4 sah vor, dass die Ausduennung
 * unmittelbar hinterherlaeuft, wenn die Sechsmonatsfrist schon abgelaufen
 * ist. Dagegen sprach beim Bauen: zwei unwiderrufliche Schritte mit zwei
 * verschiedenen Rundlaufbegriffen in einem Budgetfenster, deren Scheitern
 * sich hinterher nicht mehr zuordnen laesst. Getrennt kostet es einen
 * Jobzyklus — die frisch verdichtete Spur traegt Stufe 2 und wird beim
 * naechsten Lauf von `job_ausduennen` gefunden. Entschieden am 31.08.2026;
 * das Konzept ist an dieser Stelle fortgeschrieben.
 */
function job_verdichtung(PDO $pdo, array $zustand, callable $zeitLinks): array
{
    require_once __DIR__ . '/spur_lib.php';

    $erledigt = 0;
    $fertig   = true;
    $sammeln  = ['luecke' => [], 'zu_gross' => [], 'stufe3' => [], 'fehler' => []];
    $offen    = 0;

    foreach ([['mission', 'missions'], ['rest', 'rest_segments']] as [$typ, $tabelle]) {
        $marke = (int)($zustand[$typ] ?? 0);
        while (true) {
            if ($zeitLinks() <= JOB_VERDICHTUNG_RESERVE_S) { $fertig = false; break 2; }

            $st = $pdo->prepare('SELECT DISTINCT owner_id FROM track_points
                                  WHERE owner_type = ? AND owner_id > ?
                                  ORDER BY owner_id LIMIT ' . JOB_VERDICHTUNG_BLOCK);
            $st->execute([$typ, $marke]);
            $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            if (!$ids) {
                $zustand[$typ] = 0;
                $zustand['durch'][$typ] = true;
                break;
            }
            $zustand['durch'][$typ] = false;

            // Eigentuemerseite, EINE Abfrage fuer den ganzen Block.
            $platz = implode(',', array_fill(0, count($ids), '?'));
            $ow = $pdo->prepare("SELECT id, final, letzter_punkt_am, deleted_at
                                   FROM `$tabelle` WHERE id IN ($platz)");
            $ow->execute($ids);
            $eigner = [];
            foreach ($ow->fetchAll(PDO::FETCH_ASSOC) as $r) { $eigner[(int)$r['id']] = $r; }

            $umriss = spur_umriss($pdo, $typ, $ids);

            foreach ($ids as $id) {
                if ($zeitLinks() <= JOB_VERDICHTUNG_RESERVE_S) { $fertig = false; break 3; }
                $u = $umriss[$id] ?? null;
                if ($u === null || $u['zeilen'] === 0) { continue; }

                // 1. Eigentuemer weg -> der Waisenjob raeumt, nicht dieser hier.
                if (!isset($eigner[$id])) { continue; }
                $e = $eigner[$id];

                // 2. Papierkorb: nicht anfassen. Jede unwiderrufliche Handlung,
                //    die der Job nicht tun MUSS, ist eine, bei der er sich nicht
                //    irren kann; nach TRASH_DAYS ist die Spur ohnehin weg.
                if ($e['deleted_at'] !== null) { $offen++; continue; }

                // 3. Stufe 3: nicht anfassen. Ein ausgeduennter Blob gibt seine
                //    Punkte mit der POSITION zurueck, nicht mit der
                //    Originalnummer — die Vereinigung mit Zeilen saehe immer
                //    nach Luecke aus. Solche Zeilen sind Nachzuegler auf einer
                //    ausgeduennten Spur; ingest.php nimmt sie seit AP3 gar nicht
                //    mehr an (E-S2-08). Erwartungswert: 0.
                if ($u['stufe'] === SPUR_STUFE_DUENN) {
                    if (count($sammeln['stufe3']) < JOB_LISTE_MAX) { $sammeln['stufe3'][] = "$typ:$id"; }
                    $offen++; continue;
                }

                // 4. Zu gross: ablehnen, nicht verdichten. Eine solche Spur ist
                //    aus einer Sicherung nicht wiederherstellbar (F-S2-02); sie
                //    zu verdichten hiesse, Zeilen unwiderruflich gegen einen
                //    Blob zu tauschen, den der Rueckweg ablehnt.
                if ($u['gesamt'] > LIMIT_TRACKPUNKTE_SPUR) {
                    if (count($sammeln['zu_gross']) < JOB_LISTE_MAX) { $sammeln['zu_gross'][] = "$typ:$id"; }
                    $offen++; continue;
                }

                // 5. Ankunftszeit nachtragen, wo sie fehlt (Altbestand vor der
                //    Migration). LEAST gegen jetzt: Ein Punkt kann nicht spaeter
                //    angekommen sein als in diesem Augenblick — der Riegel gegen
                //    eine Uhr mit Zeit in der Zukunft.
                $letzter = $e['letzter_punkt_am'];
                if ($letzter === null && $u['max_ts'] !== null) {
                    $pdo->prepare("UPDATE `$tabelle`
                                      SET letzter_punkt_am = LEAST(FROM_UNIXTIME(?), UTC_TIMESTAMP())
                                    WHERE id = ? AND letzter_punkt_am IS NULL")
                        ->execute([$u['max_ts'], $id]);
                    $q = $pdo->prepare("SELECT letzter_punkt_am FROM `$tabelle` WHERE id = ?");
                    $q->execute([$id]);
                    $letzter = $q->fetchColumn() ?: null;
                }
                if ($letzter === null) { $offen++; continue; }

                // 6. Karenz (E-S2-06).
                $still = (int)((new DateTime('now', new DateTimeZone('UTC')))->getTimestamp()
                             - (new DateTime($letzter, new DateTimeZone('UTC')))->getTimestamp());
                $reif = ((int)$e['final'] === 1 && $still >= 14 * 86400)
                     || ($still >= 60 * 86400);
                if (!$reif) { $offen++; continue; }

                // 7. Luecke (E-S2-06): nicht verdichten, sondern benennen.
                if (!$u['lueckenlos']) {
                    if (count($sammeln['luecke']) < JOB_LISTE_MAX) { $sammeln['luecke'][] = "$typ:$id"; }
                    $offen++; continue;
                }

                $meldung = spur_verdichten_eine($pdo, $typ, $id, $u);
                if ($meldung === null) { $erledigt++; }
                else {
                    if (count($sammeln['fehler']) < JOB_LISTE_MAX) { $sammeln['fehler'][] = "$typ:$id — $meldung"; }
                    $offen++;
                    error_log("jobs: Verdichtung $typ/$id abgelehnt: $meldung");
                }
            }
            $marke = (int)end($ids);
            $zustand[$typ] = $marke;
        }
    }

    /* Die Sammellisten ersetzen den angezeigten Stand erst, wenn BEIDE Marken
     * einmal umgelaufen sind. Sonst zeigte die Liste eine Mischung aus
     * mehreren Durchlaeufen, in der behobene Faelle stehenbleiben. */
    $zustand['sammeln'] = $sammeln;
    $zustand['offen_lauf'] = ($zustand['offen_lauf'] ?? 0) + $offen;
    if (!empty($zustand['durch']['mission']) && !empty($zustand['durch']['rest'])) {
        $zustand['stand'] = $sammeln;
        $zustand['offen'] = $zustand['offen_lauf'];
        $zustand['offen_lauf'] = 0;
    }

    return ['zustand' => $zustand, 'erledigt' => $erledigt, 'fertig' => $fertig];
}

/**
 * Eine Spur verdichten — eine Transaktion, Rundlauf davor (E-S2-07).
 *
 * DIE RUNDLAUFPRUEFUNG BRAUCHT KEIN PDO und laeuft deshalb VOR der
 * Transaktion. Schlaegt sie an, geschieht gar nichts: kein Blob, kein DELETE.
 * Die Transaktion bleibt auf zwei Anweisungen kurz.
 *
 * REIHENFOLGE ZWINGEND: erst Blob, dann Zeilen. Der Zwischenzustand ist im
 * Code bereits vorgesehen — `spur_lesen_viele()` uebergeht Zeilen unterhalb
 * `n_original` ausdruecklich als Rest eines abgebrochenen Laufs. Umgekehrt
 * waere ein Abbruch Datenverlust.
 *
 * @return string|null null = verdichtet, sonst der Grund
 */
function spur_verdichten_eine(PDO $pdo, string $typ, int $id, array $umriss): ?string
{
    /* SONDERFALL RESTE: Zeilen, die vollstaendig UNTERHALB n_original liegen,
     * stehen beweisbar schon im Blob — Rest eines abgebrochenen Laufs oder
     * eine Uhr, die ein altes Teilstueck erneut geschickt hat. Nichts neu
     * kodieren, nur wegraeumen. Ohne diesen Fall laegen sie fuer immer
     * unsichtbar da. */
    if ($umriss['n_original'] > 0 && $umriss['max_seq'] < $umriss['n_original']) {
        $pdo->beginTransaction();
        spur_loeschen_nur_zeilen($pdo, $typ, $id, $umriss['n_original']);
        $pdo->commit();
        return null;
    }

    $punkte = spur_lesen($pdo, $typ, $id);
    $n = count($punkte);
    if ($n === 0) { return 'keine Punkte'; }

    /* Zweite, kostenlose Lueckenpruefung auf der TATSAECHLICH gelesenen Liste.
     * Die erste lief in SQL auf dem Umriss; zwei unabhaengige Pruefungen vor
     * einem unwiderruflichen DELETE sind eine mehr, als es kostet. */
    foreach ($punkte as $i => $p) {
        if ((int)$p[0] !== $i) { return "Luecke bei Nummer $i (gelesen {$p[0]})"; }
    }

    $blob = spur_kodieren($punkte, SPUR_STUFE_ROH, $n);
    $m = spur_rundlauf_pruefen($punkte, $blob);
    if ($m !== null) { return 'Rundlauf: ' . $m; }

    $pdo->beginTransaction();
    spur_blob_schreiben($pdo, $typ, $id, $blob, SPUR_STUFE_ROH, $n, $n);
    spur_loeschen_nur_zeilen($pdo, $typ, $id, $n);
    $pdo->commit();
    return null;
}

/** Wie viel bleibt liegen? — die Zahl des letzten VOLLSTAENDIGEN Durchlaufs. */
function job_verdichtung_rueckstand(PDO $pdo, array $z): ?int
{
    return isset($z['offen']) ? (int)$z['offen'] : null;
}

/**
 * Ausduennen: Stufe 2 -> Stufe 3, sechs Monate nach Einsatzende (E-S2-03/05).
 *
 * DER EINSTIEG GEHT UEBER DEN PRIMAERSCHLUESSEL von `track_blobs`, nicht ueber
 * den Index `stufe_alter (stufe, geaendert_am)`. Der traegt das Aenderungsdatum
 * des BLOBS, nicht das Einsatzende, und ist als Naeherung in beide Richtungen
 * falsch: Das Einspielen einer Sicherung schreibt einen frischen
 * `geaendert_am` auf zwei Jahre alte Punkte, und `spur_zeit_verschieben()`
 * schreibt ihn bei jeder Umdatierung eines Diensttags neu.
 *
 * BEZUGSGROESSE IST `COALESCE(ended_at, started_at)`. `started_at` ist in
 * beiden Tabellen NOT NULL, die Bedingung kann also nie ins Leere laufen; ein
 * Einsatz dauert Stunden, und bei sechs Monaten Frist ist der Unterschied
 * zwischen Beginn und Ende Rauschen — er geht ausserdem in die sichere
 * Richtung, ausgeduennt wird nie zu frueh. Und er erfasst genau die Menge,
 * die E-S2-06 ueber die 60-Tage-Regel ohne `final` nach Stufe 2 bringt und
 * die bei `ended_at IS NULL` sonst ewig dort laege.
 */
function job_ausduennen(PDO $pdo, array $zustand, callable $zeitLinks): array
{
    require_once __DIR__ . '/spur_lib.php';

    $erledigt = 0;
    $fertig   = true;
    $sammeln  = ['nachzuegler' => [], 'fehler' => []];
    $offen    = 0;

    foreach ([['mission', 'missions'], ['rest', 'rest_segments']] as [$typ, $tabelle]) {
        $marke = (int)($zustand[$typ] ?? 0);
        while (true) {
            if ($zeitLinks() <= JOB_VERDICHTUNG_RESERVE_S) { $fertig = false; break 2; }

            /* OHNE blob_daten. Die Spalte ist ein MEDIUMBLOB; sie im
             * Kandidatenlauf mitzulesen hiesse, je Haeppchen Megabyte fuer
             * eine Entscheidung zu holen, die drei Zahlen braucht. */
            $st = $pdo->prepare('SELECT owner_id, n_original, n_gespeichert
                                   FROM track_blobs
                                  WHERE owner_type = ? AND stufe = ? AND owner_id > ?
                                  ORDER BY owner_id LIMIT ' . JOB_AUSDUENNEN_BLOCK);
            $st->execute([$typ, SPUR_STUFE_ROH, $marke]);
            $kandidaten = $st->fetchAll(PDO::FETCH_ASSOC);
            if (!$kandidaten) {
                $zustand[$typ] = 0;
                $zustand['durch'][$typ] = true;
                break;
            }
            $zustand['durch'][$typ] = false;
            $ids = array_map(fn($r) => (int)$r['owner_id'], $kandidaten);

            $platz = implode(',', array_fill(0, count($ids), '?'));
            $ow = $pdo->prepare("SELECT id, COALESCE(ended_at, started_at) bezug, deleted_at
                                   FROM `$tabelle` WHERE id IN ($platz)");
            $ow->execute($ids);
            $eigner = [];
            foreach ($ow->fetchAll(PDO::FETCH_ASSOC) as $r) { $eigner[(int)$r['id']] = $r; }

            // Welche haben noch Zeilen? EINE Abfrage fuer den Block.
            $zl = $pdo->prepare("SELECT DISTINCT owner_id FROM track_points
                                  WHERE owner_type = ? AND owner_id IN ($platz)");
            $zl->execute(array_merge([$typ], $ids));
            $mitZeilen = array_flip(array_map('intval', $zl->fetchAll(PDO::FETCH_COLUMN)));

            $grenze = (new DateTime('now', new DateTimeZone('UTC')))
                    ->modify('-' . SPUR_AUSDUENN_FRIST_MONATE . ' months');

            foreach ($kandidaten as $k) {
                if ($zeitLinks() <= JOB_VERDICHTUNG_RESERVE_S) { $fertig = false; break 3; }
                $id = (int)$k['owner_id'];
                if (!isset($eigner[$id])) { continue; }          // Waise
                $e = $eigner[$id];
                if ($e['deleted_at'] !== null) { $offen++; continue; }
                if ($e['bezug'] === null) { $offen++; continue; }
                if (new DateTime($e['bezug'], new DateTimeZone('UTC')) > $grenze) { continue; }

                /* NACHZUEGLER GEHEN VOR. Eine Spur mit Zeilen gehoert der
                 * Verdichtung, die Blob und Nachzuegler zu einem neuen
                 * verlustfreien Blob zusammenfuehrt. Duennte man jetzt aus,
                 * nummerierte der Blob 0..n_gespeichert-1 und die Nachzuegler
                 * begaennen bei n_original — eine Nummernluecke, die der
                 * Rueckweg der Sicherung nicht vertraegt. */
                if (isset($mitZeilen[$id])) {
                    if (count($sammeln['nachzuegler']) < JOB_LISTE_MAX) { $sammeln['nachzuegler'][] = "$typ:$id"; }
                    $offen++; continue;
                }

                // Passt die Spur ins Restbudget? Vorher rechnen, nicht hinterher.
                if (spur_ausduenn_dauer_s((int)$k['n_gespeichert']) > $zeitLinks() - 0.5) {
                    $fertig = false; $offen++; continue;
                }

                $meldung = spur_ausduennen_eine($pdo, $typ, $id);
                if ($meldung === null) { $erledigt++; }
                else {
                    if (count($sammeln['fehler']) < JOB_LISTE_MAX) { $sammeln['fehler'][] = "$typ:$id — $meldung"; }
                    $offen++;
                    error_log("jobs: Ausduennung $typ/$id abgelehnt: $meldung");
                }
            }
            $marke = (int)end($ids);
            $zustand[$typ] = $marke;
        }
    }

    $zustand['sammeln'] = $sammeln;
    $zustand['offen_lauf'] = ($zustand['offen_lauf'] ?? 0) + $offen;
    if (!empty($zustand['durch']['mission']) && !empty($zustand['durch']['rest'])) {
        $zustand['stand'] = $sammeln;
        $zustand['offen'] = $zustand['offen_lauf'];
        $zustand['offen_lauf'] = 0;
    }

    return ['zustand' => $zustand, 'erledigt' => $erledigt, 'fertig' => $fertig];
}

/**
 * Eine Spur ausduennen — eine Transaktion, eigene Pruefung davor.
 *
 * AUSGEDUENNT WIRD AUS DEM DEKODIERTEN STUFE-2-BLOB, nie aus `track_points`.
 * Nur so sind die behaltenen Punkte bitgleich mit dem, was die
 * Stufe-2-Rundlaufpruefung bereits abgenommen hat.
 *
 * @return string|null null = ausgeduennt, sonst der Grund
 */
function spur_ausduennen_eine(PDO $pdo, string $typ, int $id): ?string
{
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('SELECT stufe, n_original, blob_daten FROM track_blobs
                             WHERE owner_type = ? AND owner_id = ? FOR UPDATE');
        $q->execute([$typ, $id]);
        $r = $q->fetch(PDO::FETCH_ASSOC);
        if (!$r || (int)$r['stufe'] !== SPUR_STUFE_ROH) {
            $pdo->rollBack();
            return null;                       // jemand war schneller
        }
        $nOriginal = (int)$r['n_original'];
        $punkte = spur_dekodieren($r['blob_daten']);
        $zeiten = spur_schutzzeiten($pdo, $typ, $id);

        $behalten = spur_ausduennen($punkte, $zeiten);
        $m = spur_ausduennung_pruefen($punkte, $behalten, $zeiten);
        if ($m !== null) { $pdo->rollBack(); return $m; }

        $duenn = [];
        foreach ($behalten as $i) { $duenn[] = $punkte[$i]; }

        /* n_original MUSS aus dem Stufe-2-Kopf kommen. Ohne den dritten
         * Parameter setzte spur_kodieren() es auf die Zahl der BEHALTENEN
         * Punkte — die Fortsetzungsmarke fiele um zwei Drittel, und die Uhr
         * saende einen halben Dienst noch einmal, der dann nach E-S2-08
         * verworfen wird: eine Schleife ohne jede Fehlermeldung. */
        $blob = spur_kodieren($duenn, SPUR_STUFE_DUENN, $nOriginal);
        $m = spur_rundlauf_pruefen($duenn, $blob);
        if ($m !== null) { $pdo->rollBack(); return 'Rundlauf: ' . $m; }

        spur_blob_schreiben($pdo, $typ, $id, $blob, SPUR_STUFE_DUENN,
                            $nOriginal, count($duenn));
        $pdo->commit();
        return null;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        return get_class($ex) . ': ' . $ex->getMessage();
    }
}

/** Wie viel bleibt liegen? — die Zahl des letzten VOLLSTAENDIGEN Durchlaufs. */
function job_ausduennen_rueckstand(PDO $pdo, array $z): ?int
{
    return isset($z['offen']) ? (int)$z['offen'] : null;
}

/* ---- Job: Sicherungen aller Konten (S2/AP6, E-S2-14) --------------------
 *
 * ER ARBEITET NUR AUF AUFTRAG. „Alle sichern" legt eine Warteschlange an
 * (`edbak_auftrag_starten()`); dieser Job leert sie in Schueben. Ohne Auftrag
 * kostet er eine Abfrage und meldet „fertig".
 *
 * WARUM NICHT AUTOMATISCH. E-S2-19 hat naechtliche Sicherungen je Konto
 * ausdruecklich abgelehnt (Beschluss 29.08.2026). Der Job ist das Fuhrwerk
 * fuer die Schaltflaeche, kein Zeitplan.
 *
 * DIE RESERVE IST GROSS, und das ist Absicht: Ein Konto mit 5000 Einsaetzen
 * kostet gemessen 14,13 s. Am Huckepack-Weg (JOB_BUDGET_ANFRAGE = 3 s) fängt
 * dieser Job deshalb gar nicht erst an — eine Anfrage einer NutzerIn soll
 * keine fremde Sicherung mittragen.
 */
const JOB_ADMINBACKUP_RESERVE_S = 15.0;

function job_adminbackup(PDO $pdo, array $zustand, callable $zeitLinks): array
{
    require_once __DIR__ . '/adminbackup_lib.php';
    $e = edbak_auftrag_schub($zeitLinks, JOB_ADMINBACKUP_RESERVE_S);
    return ['zustand' => [], 'erledigt' => $e['erledigt'], 'fertig' => $e['offen'] === 0];
}

/** Wie viele Konten warten noch? `null` heisst „kein Auftrag". */
function job_adminbackup_rueckstand(PDO $pdo, array $zustand): ?int
{
    require_once __DIR__ . '/adminbackup_lib.php';
    $a = edbak_auftrag_lesen();
    return $a === null ? null : edbak_auftrag_offen($a);
}


/* ---- Versand auf die Sicherungsziele (S2/AP7, E-S2-22) ------------------- */

/**
 * Neue Pakete auf die aktiven Ziele schieben.
 *
 * NUR MIT EINGESCHALTETEM SCHALTER. Ein Versand ist eine Verbindung nach
 * draussen; er passiert nicht, weil jemand einmal ein Ziel eingetragen hat,
 * sondern weil jemand ihn eingeschaltet hat. Der Knopf „Jetzt versenden"
 * auf der Zielseite geht denselben Weg und fragt den Schalter NICHT — dort
 * hat gerade jemand geklickt, und das ist die Zustimmung.
 *
 * DIE RESERVE IST GROSS (25 s): Ein SFTP-Verbindungsaufbau kostet in reinem
 * PHP je nach Schlüssellänge ueber eine Sekunde, und danach soll wenigstens
 * eine Datei hinausgehen. Am Huckepack-Weg (JOB_BUDGET_ANFRAGE = 3 s) fängt
 * dieser Job deshalb gar nicht erst an.
 */
function job_versand(PDO $pdo, array $zustand, callable $zeitLinks): array
{
    require_once __DIR__ . '/sicherungsziel_lib.php';
    if (!sz_auto_an()) {
        return ['zustand' => [], 'erledigt' => 0, 'fertig' => true];
    }
    $e = sz_versand_schub($zeitLinks, SZ_VERSAND_RESERVE_S);
    /* Die Fehler stehen am ZIEL (backup_targets.letzter_fehler) und damit auf
     * der Zielseite. Hier kommen sie zusätzlich in `jobs.letzter_fehler`,
     * damit die Wartungsseite nicht „grün" meldet, während seit drei
     * Wochen nichts hinausgeht. */
    if ($e['fehler'] !== []) {
        throw new RuntimeException(implode(' | ', array_slice($e['fehler'], 0, 3)));
    }
    return ['zustand' => [], 'erledigt' => $e['gesendet'], 'fertig' => $e['fertig']];
}

/** Wie viele Dateien warten noch? Eine Schätzung — siehe sz_versand_rueckstand(). */
function job_versand_rueckstand(PDO $pdo, array $zustand): ?int
{
    require_once __DIR__ . '/sicherungsziel_lib.php';
    return sz_auto_an() ? sz_versand_rueckstand() : null;
}


/* ---- Komplettsicherung der Installation (S2/AP8, E-S2-19 bis E-S2-21) ----- */

/**
 * Wie viel Zeit ein Lauf hoechstens fuer die Komplettsicherung verwendet.
 *
 * OHNE DIESE SCHRANKE FRAESSE SIE DEN GANZEN CLI-LAUF (300 s). Sie arbeitet,
 * solange sie Zeit hat, und Zeit hat sie auf dem Cron-Weg reichlich — hinter
 * ihr staende dann `waisen` und kaeme nie dran. Zwei Minuten reichen auf dem
 * Messbestand (5000 Einsaetze, 1,12 Mio. Zeilen) fuer den ganzen Durchlauf
 * mit weitem Abstand; eine groessere Datenbank braucht mehrere Laeufe, und
 * genau dafuer gibt es die Haeppchen.
 */
const KOMP_LAUF_MAX_S = 120.0;

function job_komplett(PDO $pdo, array $zustand, callable $zeitLinks, float $reserve = KOMP_RESERVE_S): array
{
    require_once __DIR__ . '/komplett_lib.php';

    /* Die eigene Frist: das Kleinere aus Restbudget und KOMP_LAUF_MAX_S. */
    $start = microtime(true);
    $eigen = function () use ($zeitLinks, $start): float {
        return min($zeitLinks(), KOMP_LAUF_MAX_S - (microtime(true) - $start));
    };

    $e = komp_schub($pdo, $zustand, $eigen, $reserve);
    return ['zustand' => $zustand, 'erledigt' => $e['erledigt'], 'fertig' => $e['fertig']];
}

/** Wie viele Tabellen warten noch? `null` heisst „nichts vorgemerkt". */
function job_komplett_rueckstand(PDO $pdo, array $zustand): ?int
{
    require_once __DIR__ . '/komplett_lib.php';
    return komp_rueckstand_aus($zustand);
}
