<?php
declare(strict_types=1);

/**
 * Der Job-Einstieg — ein Weg, drei Auslöser (Konzept S2, E-S2-17).
 *
 * Diese Anwendung läuft auf einfachem Webspace, und dort gibt es oft keinen
 * Cron. Wer einen hat, soll ihn benutzen; wer keinen hat, soll trotzdem
 * versorgt sein. Deshalb dieselbe Arbeit über drei Wege:
 *
 *   1. KOMMANDOZEILE — der empfohlene Regelfall.
 *
 *          * * * * *  php /pfad/zu/server/jobs.php
 *
 *      Jede Minute ist unbedenklich: Ein Lauf, für den nichts zu tun ist,
 *      kostet zwei Abfragen. Die tägliche Aufräumarbeit läuft trotzdem nur
 *      einmal am Tag, das entscheidet der Job und nicht der Zeitplan.
 *
 *   2. ADRESSE MIT TOKEN — wo es keinen CLI-Cron gibt, aber einen
 *      zeitgesteuerten Abruf (viele Hoster bieten „Cronjob per URL"):
 *
 *          https://…/jobs.php?token=<Token aus dem Wartungsbereich>
 *
 *   3. HUCKEPACK AUF EINER ANFRAGE — der Rückfall, und bis Web 10.0.0 der
 *      einzige Weg. `auth_guard.php` stößt ihn bei einer angemeldeten
 *      Anfrage an. Er bleibt eingeschaltet, damit eine Installation ohne
 *      jede Einrichtung nicht stillsteht; wer 1. oder 2. eingerichtet hat,
 *      merkt ihn nicht, weil dann nichts mehr zu tun ist.
 *
 * WAS DIESE DATEI NICHT TUT: arbeiten. Sie prüft, wer fragt, und ruft
 * `jobs_lauf()`. Die Jobs stehen in `jobs_lib.php`.
 *
 * SEIT P5a IST DER TOKEN-WEG AUCH DER EINSTIEG DER AUSLIEFERUNGSKETTE
 * (E-P5a-12). Er nimmt dafür einen Parameter `aktion`:
 *
 *   (ohne)        wie bisher — alle fälligen Jobs, ein Häppchen
 *   komplett      NUR das Komplett-Backup, und zwar mit Auftrag: Steht keiner
 *                 an, legt dieser Aufruf einen an. Ohne das täte der Aufruf
 *                 bei Plan „Nur von Hand" nichts und meldete sofort `fertig`
 *                 — das Tor stünde offen, ohne dass ein Backup entstanden
 *                 wäre.
 *   wartung_an    Wartungsmodus einschalten, Urheber `kette`
 *   wartung_aus   ausschalten
 *   pause         Die Hintergrundjobs anhalten (`sekunden=N`) oder wieder
 *                 freigeben (`sekunden=0`). Dieselbe Wirkung wie
 *                 `php jobs.php --pause N` und wie die beiden Knoepfe unter
 *                 Betrieb -> Hintergrundjobs; derselbe `jobs_pause()` aus
 *                 `jobs_lib.php` dahinter.
 *   zustand       Auskunft ohne Nebenwirkung: jüngster Komplett-Stand mit
 *                 Zeit, Wartung an/aus, Migration ausstehend ja/nein,
 *                 `WEB_VERSION`
 *
 * WARUM `pause` UEBER DIE ADRESSE ERREICHBAR SEIN MUSS (Web 20.16.0).
 * Der Kreislauftest haelt die Jobs an, bevor er ein Backup in ein frisches
 * Konto spielt — sonst verdichtet und duennt der Verdichtungsjob die
 * wiederhergestellten Spuren aus, und der Vergleich misst nicht mehr „kommt
 * zurueck, was hineinging", sondern „hat der Job dazwischen zugeschlagen".
 * Nachgemessen: ein Lauf ohne Pause verdichtete 125 Spuren des Umlaufkontos.
 *
 * Bis dahin gab es dafuer nur den Weg ueber die Kommandozeile, und der
 * verlangt, dass das Pruefmittel AUF DEMSELBEN RECHNER laeuft wie die
 * Installation. Stufe 2 der Kette laeuft aber auf einem GitHub-Laeufer gegen
 * ein fernes Staging: Dort gibt es keine `config.php`, keine Datenbank, und
 * `php server/jobs.php --pause` bricht mit „Failed to open stream" ab. Genau
 * so ist der erste echte Lauf gescheitert (17.09.2026, Backlog Nr. 219).
 *
 * WAS DIE AKTION DAMIT AN MACHT GIBT: Sie kann die Hintergrundjobs bis zu
 * `JOB_PAUSE_MAX_S` still stellen — weniger als `wartung_an`, das die ganze
 * Anwendung schliesst und laengst ueber denselben Token erreichbar ist.
 * Daten liest und schreibt sie keine.
 *
 * WARUM EIN FEHLENDES `sekunden` EIN FEHLER IST UND NICHT NULL. `jobs_pause(0)`
 * HEBT die Pause auf. Wuerde ein vergessener Parameter als 0 gelesen, gaebe
 * ein `?aktion=pause` ohne Zahl die Jobs frei — der Aufrufer bekaeme ein
 * `ok` und glaubte, sie stuenden still. Deshalb 400 statt Vorgabewert.
 *
 * WARUM DIE KETTE WIEDERHOLT RUFEN MUSS. Ein Aufruf hat 20 s Budget
 * (`JOB_BUDGET_TOKEN`); ein Komplett-Backup von 10 GB braucht mehr. Der Lauf
 * arbeitet in Häppchen und sagt je Aufruf, ob er fertig ist. `fertig` heisst
 * hier ausserdem mehr als „der Job hat aufgehört": Es heisst, dass auch KEIN
 * Auftrag mehr offen steht (`komp_zustand()`). Sonst meldete ein Häppchen,
 * das sein Budget aufgebraucht hat, dasselbe wie ein fertiges Backup.
 *
 * DIESE DATEI BLEIBT IN `WARTUNG_AUSNAHMEN` (`wartung_lib.php`) — und das ist
 * jetzt tragend, nicht bequem: Die Kette schaltet die Wartung ein und ruft
 * danach `zustand` und `wartung_aus`. Stünde sie nicht in der Liste, sperrte
 * sie sich nach dem ersten Aufruf selbst aus.
 *
 * KEINE ANMELDUNG. Der Aufruf über die Adresse legitimiert sich mit dem
 * Token, nicht mit einer Sitzung — ein Zeitplandienst hat keine. Deshalb
 * lädt diese Datei ausdrücklich NICHT `auth_guard.php`: Der würde den
 * huckepack-Weg auslösen und damit den Job aus dem Job heraus starten.
 */

/* BIS WEB 20.26.3 STAND HIER `require_once __DIR__ . '/config.php';` —
 * und der Rueckgabewert wurde weggeworfen. Die Zeile war ein Ueberbleibsel:
 * `db.php` lud die Datei ohnehin. Mit Schritt 15 AP2 liest sie `konfig()`,
 * einmal und fuer alle. */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jobs_lib.php';

$aufKommandozeile = PHP_SAPI === 'cli';

/* ---- 1. Kommandozeile ---------------------------------------------------- */

if ($aufKommandozeile) {
    $nur = null;
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--hilfe' || $arg === '-h') {
            fwrite(STDOUT, "Aufruf: php jobs.php [jobname …]\n"
                 . "        php jobs.php --pause <Sekunden>   Jobs anhalten (0 = aufheben)\n"
                 . "Ohne Angabe laufen alle fälligen Jobs.\n"
                 . "Bekannt: " . implode(', ', array_keys(jobs_katalog())) . "\n");
            exit(0);
        }
        /* --pause: fuer Pruefmittel, die den Bestand messen, waehrend die
         * Jobs ihn aendern wuerden (S2/AP3). Sie laeuft von selbst ab. */
        if (str_starts_with($arg, '--pause')) {
            $s = (int)(explode('=', $arg, 2)[1] ?? ($argv[array_search($arg, $argv, true) + 1] ?? 0));
            jobs_pause($s);
            $bis = jobs_pause_bis();
            fwrite(STDOUT, $bis === null ? "Jobs laufen wieder.\n"
                                         : "Jobs angehalten bis $bis UTC.\n");
            exit(0);
        }
        if (is_numeric($arg)) { continue; }   // Zahl hinter --pause
        $nur[] = $arg;
    }
    $bericht = jobs_lauf('cli', $nur);
    $fehler = 0;
    foreach ($bericht as $name => $b) {
        if (isset($b['uebersprungen'])) {
            fwrite(STDOUT, sprintf("%-14s übersprungen (%s)\n", $name, $b['uebersprungen']));
            continue;
        }
        if (!empty($b['fehler'])) { $fehler++; }
        /* `uebergangen` hängt an die Zeile an, statt sie zu ersetzen —
         * anders als `uebersprungen` weiter oben, das „dieser Job lief gar
         * nicht" heisst (S10/AP4, E-S10-U-09). */
        fwrite(STDOUT, sprintf("%-14s %s · erledigt %d%s%s%s%s%s\n", $name,
            $b['fertig'] ? 'fertig' : 'Rest offen',
            $b['erledigt'],
            !empty($b['uebergangen']) ? ' · ' . (int)$b['uebergangen'] . ' übergangen' : '',
            !empty($b['geloescht']) ? ' · ' . (int)$b['geloescht'] . ' am Ziel entfernt' : '',
            /* „gelöscht" OHNE ZUSATZ heisst: hier, im eigenen Dateisystem.
             * Die Zeile darueber sagt „am Ziel entfernt" und meint eine
             * FREMDE Maschine (Backup-Ziel, P5a/AP10). Heute fuellt diesen
             * Zaehler nur der Aufraeumteil „Sitzungsdateien" (Schritt 16). */
            !empty($b['geloescht_dateien']) ? ' · ' . (int)$b['geloescht_dateien'] . ' gelöscht' : '',
            $b['rueckstand'] !== null ? ' · Rückstand ' . $b['rueckstand'] : '',
            !empty($b['fehler']) ? ' · FEHLER: ' . $b['fehler'] : ''));
    }
    exit($fehler === 0 ? 0 : 1);
}

/* ---- 2. Adresse mit Token ------------------------------------------------ */

$t0 = microtime(true);

/* DIE KOPFZEILEN SETZT `json_roh_out()` (P5a/AP4a, Nr. 203). Hier standen
 * zwei `header()`-Zeilen; `nosniff` und `Referrer-Policy` fehlten, und das
 * ist an einem Endpunkt, der mit einem Token erreichbar ist und den Zustand
 * der Auslieferung ausgibt, kein Schoenheitsfehler. */
require_once __DIR__ . '/ratelimit_lib.php';

/* SPERRE VOR JEDER WEITEREN ARBEIT — dasselbe Muster wie in `pair.php`.
 * Sonst bliebe der Aufwand, den eine Anfrage ausloest, trotz Sperre als
 * Angriffsflaeche offen. Derselbe Topf wie beim Koppeln: zehn Fehlversuche
 * in zehn Minuten, dann zehn Minuten Ruhe. */
if (!rate_erlaubt('pair')) {
    rate_gleiche_dauer($t0);
    json_out(['error' => 'zu_viele_versuche'], 429);
}

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');

$erwartet = '';
try {
    $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
    $st->execute([JOB_TOKEN_SCHLUESSEL]);
    $erwartet = (string)($st->fetchColumn() ?: '');
} catch (Throwable $ex) {
    rate_gleiche_dauer($t0);
    json_out(['error' => 'datenbank'], 500);
}

/* `hash_equals` und nicht `===`: Ein Zeichenvergleich, der beim ersten
 * Unterschied abbricht, verraet ueber die Laufzeit, wie viele Zeichen
 * stimmten. Dieselbe Regel wie bei jedem anderen Geheimnisvergleich hier.
 *
 * `rate_gleiche_dauer` gleicht die Antwortzeit zusaetzlich an — ein „Token
 * gibt es gar nicht" darf nicht schneller kommen als ein „Token ist falsch". */
if ($erwartet === '' || $token === '' || !hash_equals($erwartet, $token)) {
    rate_misserfolg('pair');
    rate_gleiche_dauer($t0);
    // Nicht sagen, ob ueberhaupt ein Token eingerichtet ist. Wer den Weg
    // benutzen darf, kennt es aus dem Wartungsbereich.
    json_out(['error' => 'token'], 403);
}

/* ---- 2b. Die Aktionen der Auslieferungskette (E-P5a-12) ------------------ */

/** Antwort schreiben und Schluss. */
function kette_antwort(array $feld, int $code = 200): never
{
    /* UEBER `json_roh_out()` und nicht `json_out()`, weil die beiden
     * `json_encode`-Schalter gebraucht werden: Die Antwort nennt Dateinamen
     * und Umlaute, und ein `\/` oder `\u00e4` darin ist zwar gueltiges JSON,
     * aber in einem Cron-Protokoll nicht mehr zu lesen. */
    json_roh_out((string)json_encode($feld,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $code);
}

/**
 * Der jüngste Komplett-Stand — Datei, Zeit, Größe. `null`, wenn es keinen gibt.
 *
 * Die Zeit kommt aus dem DATEINAMEN (`komp_zeit_aus_name()`) und nicht aus
 * `filemtime()`: Der Name trägt den Zeitpunkt, zu dem der Stand GEMEINT ist,
 * die Änderungszeit den, zu dem zuletzt jemand die Datei angefasst hat. Die
 * Kette vergleicht gegen ihren Laufbeginn; dafür ist nur die erste Zahl
 * brauchbar.
 */
function kette_komplett_stand(): ?array
{
    require_once __DIR__ . '/komplett_lib.php';
    $staende = komp_staende();
    if ($staende === []) { return null; }
    return ['datei'   => $staende[0]['datei'],
            'zeit'    => $staende[0]['zeit'],
            'groesse' => $staende[0]['groesse']];
}

/**
 * Steht eine Migration aus?
 *
 * UNGEPUFFERT, UND DAS BLEIBT SO. AP3 gibt `migration_lib.php` ein
 * `migrationen_ausstehend()` mit Zwischenspeicher — das braucht der
 * Torwächter, der die Frage bei JEDER Anfrage stellt. Hier fällt sie
 * höchstens zweimal je Auslieferung an; ein Zwischenspeicher wäre an dieser
 * Stelle nur eine zweite Wahrheit.
 */
function kette_migration_offen(): bool
{
    require_once __DIR__ . '/migration_lib.php';
    $m = migrationen_lauf(db(), false);
    return ((int)$m['offen'] + (int)$m['blockiert']) > 0;
}

$aktion = (string)($_GET['aktion'] ?? $_POST['aktion'] ?? '');

if ($aktion === 'zustand') {
    $w = wartung_daten();
    kette_antwort([
        'ok'                   => true,
        'aktion'               => 'zustand',
        'version'              => WEB_VERSION,
        'wartung'              => ['aktiv' => wartung_aktiv(),
                                   'seit'  => $w['seit'], 'von' => $w['von']],
        'komplett'             => kette_komplett_stand(),
        'migration_ausstehend' => kette_migration_offen(),
    ]);
}

if ($aktion === 'wartung_an' || $aktion === 'wartung_aus') {
    /* `wartung_einschalten()` ist idempotent und überschreibt Zeitpunkt und
     * Urheber NICHT — ein zweiter Aufruf der Kette nimmt einer von Hand
     * eingeschalteten Wartung also nicht ihre Herkunft. */
    $ok = $aktion === 'wartung_an'
        ? wartung_einschalten('kette')
        : wartung_ausschalten();
    kette_antwort([
        'ok'      => $ok,
        'aktion'  => $aktion,
        'wartung' => wartung_aktiv(),
        'meldung' => $ok ? null
            : 'Der Schalter liess sich nicht setzen: ' . WARTUNG_DATEI
              . ' — Schreibrechte der Anwendungswurzel prüfen.',
    ], $ok ? 200 : 500);
}

if ($aktion === 'pause') {
    /* Siehe Kopf der Datei. `jobs_pause()` ist derselbe Weg wie auf der
     * Kommandozeile und in `betrieb_jobs.php` — hier kommt kein vierter
     * Mechanismus dazu, nur ein vierter Aufrufer. */
    $roh = $_GET['sekunden'] ?? $_POST['sekunden'] ?? null;

    /* KEIN VORGABEWERT. Ein fehlendes `sekunden` als 0 zu lesen hiesse, die
     * Pause bei einem vergessenen Parameter AUFZUHEBEN und dafuer `ok` zu
     * melden. */
    if (!is_string($roh) && !is_int($roh)) {
        kette_antwort(['ok' => false, 'aktion' => 'pause', 'error' => 'sekunden',
                       'meldung' => 'Parameter `sekunden` fehlt. '
                                  . '0 hebt die Pause auf, N haelt N Sekunden an.'], 400);
    }
    /* GANZE ZAHL, NICHT „irgendwie numerisch". Die erste Fassung schrieb
     * `!is_numeric($roh) || (int)$roh < 0` — und liess damit genau das durch,
     * wogegen der Absatz darueber steht: `sekunden=-0.5` ist numerisch,
     * `(int)"-0.5"` ist 0, 0 ist nicht kleiner als 0. Der Aufruf hob die
     * laufende Pause auf und quittierte es mit `ok`. Nachgemessen am
     * 17.09.2026 gegen eine echte Installation, gefunden von einer
     * unabhaengigen Durchsicht — meine eigenen Proben (-5, abc) trafen die
     * Luecke nicht, weil beide schon vorher scheitern.
     *
     * `^\d+$` laesst nur Ziffern zu: kein Vorzeichen, kein Punkt, kein `1e3`,
     * kein fuehrendes Leerzeichen. Danach ist `(int)` verlustfrei. */
    if (!preg_match('/^\d+$/', (string)$roh)) {
        kette_antwort(['ok' => false, 'aktion' => 'pause', 'error' => 'sekunden',
                       'meldung' => 'Parameter `sekunden` ist keine ganze Zahl >= 0. '
                                  . 'Erlaubt sind nur Ziffern — `-0.5` oder `1e3` '
                                  . 'wuerden zu 0 und HOEBEN die Pause auf.'], 400);
    }

    $sek = (int)$roh;
    jobs_pause($sek);
    $bis = jobs_pause_bis();

    /* `bis` ist die ANTWORT AUF DIE FRAGE, nicht die Wiederholung des
     * Wunsches: `jobs_pause()` deckelt auf JOB_PAUSE_MAX_S, und bei 0 steht
     * hier null. Wer 9999 schickt, sieht an `bis`, dass er 7200 bekommen
     * hat. */
    kette_antwort([
        'ok'       => true,
        'aktion'   => 'pause',
        'sekunden' => $sek,
        'grenze'   => JOB_PAUSE_MAX_S,
        'bis'      => $bis,
        'meldung'  => $bis === null ? 'Jobs laufen wieder.'
                                    : "Jobs angehalten bis $bis UTC.",
    ]);
}

if ($aktion === 'komplett') {
    require_once __DIR__ . '/komplett_lib.php';

    /* EINEN AUFTRAG ANLEGEN, WENN KEINER STEHT. Ohne das täte der Job bei
     * Plan „Nur von Hand" nichts (`komp_faellig()` ist dann immer false) und
     * meldete sofort `fertig` — das Backup-Tor der Kette stünde offen, ohne
     * dass ein Backup entstanden wäre. */
    $offenerAuftrag = static fn(): bool =>
        in_array((string)(komp_zustand()['stand'] ?? ''), ['dump', 'siegel'], true);

    if (!$offenerAuftrag()) {
        $r = komp_auftrag_starten();
        if (!$r['ok'] && !$offenerAuftrag()) {
            /* Kein Auftrag zustande gekommen UND keiner offen: Das ist ein
             * echter Grund (kein Serverschlüssel, Speichergrenze, Ablage nicht
             * beschreibbar) und kein Warten. Die Kette bricht daran sofort ab,
             * statt vierzigmal zu fragen. */
            kette_antwort(['ok' => false, 'aktion' => 'komplett', 'fertig' => false,
                           'error' => 'auftrag', 'meldung' => $r['meldung']], 409);
        }
    }

    $bericht = jobs_lauf('token', ['komplett'])['komplett'] ?? [];
    /* `fertig` heisst: Der Job hat aufgehört UND es steht kein Auftrag mehr
     * offen. Ein Häppchen, das sein Budget aufgebraucht hat, meldete sonst
     * dasselbe wie ein fertiges Backup. */
    $fertig = !empty($bericht['fertig']) && !$offenerAuftrag();
    kette_antwort(['ok' => true, 'aktion' => 'komplett', 'fertig' => $fertig,
                   'bericht' => $bericht, 'komplett' => kette_komplett_stand()]);
}

if ($aktion !== '') {
    kette_antwort(['ok' => false, 'error' => 'aktion',
                   'meldung' => 'Unbekannte Aktion. Bekannt: komplett, pause, '
                              . 'wartung_an, wartung_aus, zustand.'], 400);
}

/* ---- 2c. Ohne Aktion: wie bisher alle fälligen Jobs ---------------------- */

$bericht = jobs_lauf('token');
kette_antwort(['ok' => true, 'jobs' => $bericht]);
