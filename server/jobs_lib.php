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

/** Schluessel des Token in `app_state`. */
const JOB_TOKEN_SCHLUESSEL = 'jobs_token';

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
        'waisen' => [
            'titel'        => 'Verwaiste Spuren',
            'beschreibung' => 'Spurpunkte und Blobs ohne Eigentümer entfernen '
                            . '— bereichsweise über den Primärschlüssel',
            'taeglich'     => false,
            'rueckstand'   => 'job_waisen_rueckstand',
            'lauf'         => 'job_waisen',
        ],
    ];

    /* AP3 haengt hier Verdichtung und Ausduennung ein. Der Rahmen kennt sie
     * nicht; er kennt nur diesen Katalog. */
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
