<?php
declare(strict_types=1);

/**
 * Das Fehlerprotokoll — ein Helfer und drei Behandler (P5c/AP3, E-P5c-12, -58).
 *
 * WOFUER. Bis Web 20.39.0 schrieben 75 Stellen in 30 Dateien mit `error_log()`
 * in das Fehlerprotokoll des Webspace: eine Datei beim Hoster, die die
 * Anwendung nicht zeigt und die niemand liest, der nicht danach sucht. Die
 * Fehlerseite sagte der NutzerIn, dort stehe es — und ihr blieb nichts, als
 * eine Kennung weiterzugeben, von der sie nicht wusste, an wen. Jetzt steht
 * jede dieser Meldungen im Reiter **System** (Verwaltung -> Protokoll), mit
 * einer Kennung, und die Fehlerseite sagt, wohin man sie meldet.
 *
 * WAS HIER STEHT
 *   `system_melden()`            der eine Weg fuer eine Stelle, die etwas
 *                                melden will
 *   `system_rueckfall()`         derselbe Satz, nur ins Fehlerprotokoll des
 *                                Webspace — fuer die Stellen, an denen die
 *                                Datenbank das Problem ist (unten)
 *   `system_ausnahme_behandeln()` / `system_fehler_behandeln()` /
 *   `system_abbruch_pruefen()`   die Behandler; eingerichtet in `db.php`,
 *                                genau dort und genau einmal
 *                                (`tools/quelltext/behandler.php`)
 *
 * DREI EIGENSCHAFTEN, und alle drei muessen bleiben:
 *
 *   1. DIESE DATEI LAEDT NICHTS. `install.php` laedt `ui.php` ohne `db.php`,
 *      und `wartung_lib.php` darf keine Datenbank voraussetzen. Die
 *      Protokollbibliothek kommt erst im Rumpf von `system_melden()` dazu,
 *      und nur, wenn `db.php` schon da ist. Wer oben ein `require` einfuegt,
 *      zieht die Datenbank in die Wartungsseite.
 *
 *   2. KEINE SCHLEIFE. `protokoll()` scheitert -> `protokoll_fehler_vermerken()`
 *      -> `app_state_setzen()` scheitert -> `system_melden()` -> `protokoll()`
 *      ... waere ein Kreis bis zum Speicherende, und zwar genau dann, wenn
 *      die Datenbank ohnehin wackelt. Die Sperre in `system_melden()`
 *      unterbricht ihn: Wer waehrend des Meldens meldet, landet im Rueckfall.
 *
 *   3. EIN RUECKFALL, EINE KENNUNG. Geht die Datenbank nicht, steht die
 *      Meldung mit derselben Kennung im Fehlerprotokoll des Webspace — die
 *      Kennung auf der Fehlerseite fuehrt also auch dann zu ihr. Das ist eine
 *      der zwei Stellen, an denen die Anwendung noch `error_log()` ruft
 *      (Register Z38, Decke 2); die andere ist `protokoll_fehler_vermerken()`.
 *
 * WAS NICHT HINEINGEHT (E-P5c-12). Keine Anfragedaten, kein Sitzungsinhalt,
 * keine IP. Werte in Anfuehrungszeichen, E-Mail- und IPv4-Adressen werden in
 * fremden Meldungen ersetzt, bevor sie geschrieben werden: `Duplicate entry
 * '…' for key` traegt sonst Adressen und Geraetekennungen in ein Archiv, das
 * 365 Tage liegt und ausser Haus geht (E-P5c-39). Der Pfad der Anlage wird
 * abgeschnitten — `datei` heisst `api/tag.php`, nicht der Ordner beim Hoster.
 * Was bleibt, ist der Urheber: `protokoll()` nimmt ihn aus der Sitzung wie
 * bei jedem anderen Eintrag. Wer den Fehler ausgeloest hat, gehoert zur
 * Fehlersuche; was er dabei geschickt hat, nicht.
 */

/** Hoechstens so viele PHP-Meldungen je Anfrage — eine Schleife mit einer
 *  Warnung wuerde sonst tausend Zeilen schreiben, die alle dasselbe sagen.
 *  Dieselbe Datei und Zeile zaehlt ohnehin nur einmal. */
const SYSTEM_PHP_JE_ANFRAGE = 20;

/** Wie viel einer fremden Meldung bleibt. `protokoll()` kuerzt den ganzen
 *  Satz noch einmal auf 500 Zeichen. */
const SYSTEM_GRUND_MAX = 400;

/** Die Arten im Reiter System — Beschriftung und Ton stehen in
 *  `PROTOKOLL_ARTEN` (`protokoll_lib.php`). */
const SYSTEM_ARTEN = ['stoerung', 'ausnahme', 'abbruch', 'php_warnung', 'php_hinweis'];

/** Eine achtstellige Kennung, gross geschrieben — dieselbe Form, die
 *  `json_fehler()` seit M3-10 ausgibt und die Geraete kennen. */
function system_kennung(): string
{
    return strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Eine fremde Meldung so kuerzen, dass sie ins Protokoll darf.
 *
 * @param bool $werte auch Werte in Anfuehrungszeichen ersetzen. `true` fuer
 *        alles, was nicht aus diesem Quelltext stammt (Ausnahmen, PHP,
 *        Gegenstellen); `false` fuer die eigenen Worte einer Stelle, die
 *        einen Schluessel in Anfuehrungszeichen nennen duerfen.
 */
function system_bereinigen(string $s, bool $werte = true): string
{
    /* Der Ordner der Anlage — auf dem Hoster traegt er den Kontonamen. */
    $s = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $s);
    $s = (string)preg_replace('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}/', '[Adresse]', $s);
    $s = (string)preg_replace('/(?<![\d.])(?:\d{1,3}\.){3}\d{1,3}(?![\d.])/', '[IP]', $s);
    if ($werte) {
        /* Nur innerhalb einer Zeile und nur bis 300 Zeichen: Ein einzelnes
         * Hochkomma in einem Satz („can't") soll nicht den Rest der Meldung
         * mitnehmen. */
        $s = (string)preg_replace(
            ["/'[^'\\n]{0,300}'/u", '/"[^"\n]{0,300}"/u', '/„[^“"\n]{0,300}[“"]/u'],
            ["'…'", '"…"', '„…“'], $s);
    }
    return mb_substr(trim($s), 0, SYSTEM_GRUND_MAX);
}

/** Der Pfad einer Datei, bezogen auf `server/`. */
function system_datei(string $pfad): string
{
    $pre = __DIR__ . DIRECTORY_SEPARATOR;
    return str_starts_with($pfad, $pre) ? substr($pfad, strlen($pre)) : basename($pfad);
}

/**
 * Den Satz und die Angaben eines Eintrags bauen — fuer beide Wege gleich.
 *
 * @return array{0: string, 1: array<string, scalar>}
 */
function system_eintrag(string $bereich, string $text, Throwable|string|null $grund,
                        array $daten): array
{
    $satz = $bereich . ': ' . system_bereinigen($text, false);
    if ($grund instanceof Throwable) {
        $daten += ['klasse' => get_class($grund),
                   'datei'  => system_datei($grund->getFile()),
                   'zeile'  => $grund->getLine()];
        $grund = $grund->getMessage();
    }
    if (is_string($grund) && trim($grund) !== '') {
        $satz .= ' — ' . system_bereinigen($grund);
    }
    /* DIE SEITE, nicht die Adresse: der Name der ausgefuehrten Datei, ohne
     * Pfad und ohne Anfrage. Er sagt, wo gesucht werden muss, wenn die
     * Ausnahme tief in einer Bibliothek entstand. */
    $skript = (string)($_SERVER['SCRIPT_FILENAME'] ?? '');
    if ($skript !== '') { $daten += ['seite' => system_datei($skript)]; }
    return [$satz, $daten];
}

/**
 * Ins Fehlerprotokoll des Webspace — und nur dorthin.
 *
 * FUER DIE STELLEN, AN DENEN DIE DATENBANK DAS PROBLEM IST (E-P5c-58): die
 * Verbindungsgrenze und das Gedraengel (`wartung_lib.php`), der
 * Torwaechter (`migration_lib.php`) und die beiden Anmeldewege, die eine
 * ausstehende Migration ueberbruecken (`auth_guard.php`, `login.php`). Dort
 * noch einen Protokolleintrag zu versuchen hiesse, unter Last eine weitere
 * Verbindung zu oeffnen oder in eine Tabelle zu schreiben, die es vielleicht
 * noch nicht gibt.
 *
 * @return string die Kennung
 */
function system_rueckfall(string $bereich, string $text,
                          Throwable|string|null $grund = null, array $daten = [],
                          ?string $kennung = null): string
{
    $kennung ??= system_kennung();
    [$satz, $daten] = system_eintrag($bereich, $text, $grund, $daten);
    $wo = isset($daten['datei']) ? ' @ ' . $daten['datei'] . ':' . $daten['zeile'] : '';
    error_log('[' . $kennung . '] ' . $satz . $wo);
    return $kennung;
}

/**
 * Die Eintraege, die auf das Ende einer Transaktion warten.
 *
 * WARUM ES DAS GIBT. Meldet eine Stelle mitten in einer offenen Transaktion,
 * waere ihr Eintrag Teil davon — und eine Transaktion, die gerade etwas
 * meldet, wird oft gleich zurueckgerollt. Die Meldung ginge mit dem Fehler
 * unter, den sie beschreibt. Deshalb wartet sie bis zum Ende der Anfrage.
 *
 * @return list<array{0:string, 1:string, 2:array}>
 */
function &system_warteschlange(): array
{
    static $w = [];
    return $w;
}

/**
 * Etwas melden (E-P5c-58). Der eine Weg fuer jede Stelle, die bis Web 20.39.0
 * `error_log()` rief.
 *
 * @param string $bereich wer meldet, kurz (`app_state`, `jobs`, `pair`) —
 *        steht vorn im Satz, damit die Suche ihn findet
 * @param string $text    was geschah, in eigenen Worten
 * @param Throwable|string|null $grund die fremde Ursache: eine Ausnahme
 *        (Datei, Zeile und Klasse kommen dann mit) oder der Text einer
 *        Gegenstelle. Wird bereinigt — siehe Kopf.
 * @param string $art     eine aus `SYSTEM_ARTEN`
 * @param string|null $kennung eine schon vergebene Kennung — nur fuer eine
 *        Stelle, die sie vorher braucht (`smtp.php` legt sie neben den
 *        Empfaenger in die Warteschlange, damit beide zusammen zu finden
 *        sind)
 *
 * @return string die Kennung — auch dann, wenn nur der Rueckfall schrieb
 */
function system_melden(string $bereich, string $text,
                       Throwable|string|null $grund = null, array $daten = [],
                       string $art = 'stoerung', ?string $kennung = null): string
{
    static $sperre = false;
    /* FAELLT DIE DATENBANK EINMAL AUS, BLEIBT SIE FUER DIESE ANFRAGE AUS.
     * Jeder weitere Versuch waere ein weiterer Verbindungsaufbau mit
     * Zeitgrenze — ausgerechnet in einer Anfrage, die schon Probleme hat. */
    static $ohneDb = false;

    $kennung ??= system_kennung();
    if ($sperre || $ohneDb || !function_exists('db')) {
        return system_rueckfall($bereich, $text, $grund, $daten, $kennung);
    }
    [$satz, $daten] = system_eintrag($bereich, $text, $grund, $daten);
    $daten = ['kennung' => $kennung] + $daten;

    $sperre = true;
    try {
        require_once __DIR__ . '/protokoll_lib.php';
        if (db()->inTransaction()) {
            $w = &system_warteschlange();
            if ($w === []) { register_shutdown_function('system_nachtragen'); }
            $w[] = [$art, $satz, $daten];
        } elseif (!protokoll('system', $art, $satz, $daten)) {
            /* `protokoll()` hat seinen Fehlschlag schon vermerkt — aber ohne
             * den Satz. Der soll nicht verlorengehen. */
            system_rueckfall($bereich, $text, $grund, $daten, $kennung);
        }
    } catch (Throwable $ex) {
        $ohneDb = true;
        system_rueckfall($bereich, $text, $grund, $daten, $kennung);
    } finally {
        $sperre = false;
    }
    return $kennung;
}

/** Am Ende der Anfrage: was auf eine Transaktion gewartet hat. Steht sie
 *  dann noch offen, wird sie verworfen — und der Eintrag mit ihr, deshalb
 *  geht er dann in den Rueckfall. */
function system_nachtragen(): void
{
    $w = &system_warteschlange();
    foreach ($w as [$art, $satz, $daten]) {
        $geschrieben = false;
        try {
            if (!db()->inTransaction()) {
                $geschrieben = protokoll('system', $art, $satz, $daten);
            }
        } catch (Throwable) {
            /* dann der Rueckfall */
        }
        if (!$geschrieben) {
            system_rueckfall('nachgetragen', $satz, null, [], (string)$daten['kennung']);
        }
    }
    $w = [];
}

/* ---- Die Behandler ---------------------------------------------------------
 *
 * EINGERICHTET IN `db.php`, gleich hinter dem Laden dieser Datei — frueh,
 * vor jeder Seite (E-P5c-12). Eine Datei, die `db.php` nicht laedt, hat
 * keine Behandler; das sind `install.php` vor der Einrichtung und
 * `jobs.php` im Huckepack, und beide antworten dort selbst.
 */

/** Gibt es eine Adresse, an die man eine Kennung melden kann? Leer, wenn
 *  keine eingetragen ist oder die Datenbank nicht antwortet. */
function system_kontakt(): string
{
    try {
        return function_exists('instanz_kontakt') ? instanz_kontakt() : '';
    } catch (Throwable) {
        return '';
    }
}

/**
 * Der Satz, der sagt, wohin eine Kennung geht (R38). Eine Stelle fuer alle
 * Antworten, die eine Kennung nennen: Fehlerseite, `json_fehler()`, die
 * Meldungen der Verwaltungsseiten.
 */
function system_meldesatz(string $kennung): string
{
    $kontakt = system_kontakt();
    return $kontakt !== ''
        ? 'Melde die Kennung ' . $kennung . ' an ' . $kontakt . '.'
        : 'Nenne die Kennung ' . $kennung . ', wenn du den Fehler meldest.';
}

/** Die Antwort auf einen Fehler, den niemand gefangen hat: 500, Kennung,
 *  Meldeweg. Als JSON, wo ein Skript fragt; sonst als Seite. */
function system_fehlerseite(string $kennung): never
{
    $json = function_exists('wartung_json_gefragt') && wartung_json_gefragt();
    $meldung = 'Es ist ein unerwarteter Fehler aufgetreten (Kennung ' . $kennung . '). '
             . system_meldesatz($kennung);
    /* WAS SCHON IM PUFFER STEHT, KOMMT WEG (F-P5c-97). Viele Hoster puffern
     * die ersten Kilobyte (`output_buffering`); bricht eine Seite dort ab,
     * sind die Kopfzeilen noch nicht gesendet — und ohne diese Zeilen
     * stuende das ganze Dokument der Fehlerseite hinter der halben Seite.
     * Ist schon etwas hinaus, aendert das Verwerfen nichts mehr daran. */
    if (!headers_sent()) {
        while (ob_get_level() > 0 && @ob_end_clean()) { /* leeren */ }
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'server', 'kennung' => $kennung,
                              'meldung' => $meldung], JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
    }
    $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    $kontakt = system_kontakt();
    $innen = '      <h1>Unerwarteter Fehler</h1>' . "\n"
           . '      <div class="meldung meldung-fehler" role="alert">' . "\n"
           . '        <p><strong>Hier ist etwas schiefgegangen.</strong> Die Kennung'
           . ' des Fehlers ist <strong>' . $h($kennung) . '</strong>.</p>' . "\n"
           . '      </div>' . "\n"
           . '      <p>' . ($kontakt !== ''
                 ? 'Melde diese Kennung an <a href="mailto:' . $h($kontakt) . '">'
                   . $h($kontakt) . '</a> — damit lässt sich der Fehler finden.'
                 : 'Nenne diese Kennung, wenn du den Fehler meldest — damit lässt'
                   . ' sich der Fehler finden.') . '</p>' . "\n"
           . '      <p><a href="index.php">Zur Startseite</a></p>' . "\n";
    if (headers_sent() || !function_exists('stoerung_seite_html')) {
        /* Die Seite hat schon angefangen: Dann wenigstens die Kennung ans
         * Ende dessen, was schon dasteht. */
        echo "\n" . $innen;
        exit;
    }
    echo stoerung_seite_html('Unerwarteter Fehler', $innen);
    exit;
}

/**
 * Eine Ausnahme, die niemand gefangen hat.
 *
 * AUF DER KOMMANDOZEILE bleibt es beim gewohnten Bild: der volle Text samt
 * Aufrufkette auf stderr und Rueckgabewert 255 — ein Job oder eine Probe,
 * die scheitert, soll das sagen und nicht eine HTML-Seite ausgeben.
 */
function system_ausnahme_behandeln(Throwable $ex): void
{
    /* GEDRAENGEL IST KEIN DEFEKT — dieselbe Antwort wie in `json_fehler()`. */
    $cli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    if (!$cli && function_exists('gedraengel_erkannt') && gedraengel_erkannt($ex)) {
        gedraengel_vermerken($ex, 'unbehandelt');
        ueberlast_antwort();
    }
    $kennung = system_melden('unbehandelt', get_class($ex), $ex, [], 'ausnahme');
    if ($cli) {
        fwrite(STDERR, 'PHP Fatal error:  Uncaught ' . $ex . "\n  Kennung " . $kennung . "\n");
        exit(255);
    }
    system_fehlerseite($kennung);
}

/** Die Art eines PHP-Fehlers im Protokoll — und das Wort vorn im Satz. */
function system_php_art(int $no): array
{
    return match (true) {
        in_array($no, [E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING,
                       E_RECOVERABLE_ERROR], true) => ['php_warnung', 'Warnung'],
        in_array($no, [E_DEPRECATED, E_USER_DEPRECATED], true) => ['php_hinweis', 'Veraltet'],
        default => ['php_hinweis', 'Hinweis'],
    };
}

/**
 * Warnungen, Hinweise, Veraltetes.
 *
 * MIT `@` UNTERDRUECKT HEISST: NICHT DA. 136 Stellen in 19 Dateien (Stand
 * 23.09.2026) rechnen mit einem Fehlschlag und fragen danach selbst nach —
 * `@filemtime()`, `@unlink()`, `@fsockopen()`. `false` zurueck laesst PHP
 * seinen gewohnten Weg gehen, und damit bleibt auch `error_get_last()`
 * gefuellt, auf das einige davon sehen.
 *
 * IM WEB wird die Meldung danach verschluckt (`true`), auf der
 * Kommandozeile nicht: Dort liest ein Mensch oder eine Probe stderr, und
 * eine Probe, die „keine Warnung" zaehlt, soll weiter zaehlen koennen.
 */
function system_fehler_behandeln(int $no, string $text, string $datei = '', int $zeile = 0): bool
{
    if (!(error_reporting() & $no)) { return false; }
    /* `trigger_error(…, E_USER_ERROR)` soll die Anfrage beenden. Kaeme hier
     * `true` zurueck, liefe sie weiter; so beendet PHP sie, und
     * `system_abbruch_pruefen()` schreibt den Eintrag. */
    if ($no === E_USER_ERROR) { return false; }
    static $gesehen = [];
    $wo = system_datei($datei) . ':' . $zeile;
    if (!isset($gesehen[$wo]) && count($gesehen) < SYSTEM_PHP_JE_ANFRAGE) {
        $gesehen[$wo] = true;
        [$art, $wort] = system_php_art($no);
        system_melden('php', $wort, $text,
                      ['datei' => system_datei($datei), 'zeile' => $zeile], $art);
    }
    return !(PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg');
}

/**
 * Am Ende jeder Anfrage: Ist sie an einem schweren Fehler gestorben?
 *
 * Ein Parse-Fehler, ein Speicherende, ein Zeitende erreichen keinen der
 * beiden Behandler — PHP bricht ab. Hier ist die letzte Gelegenheit, es ins
 * Protokoll zu schreiben. Beim Speicherende kann auch das scheitern; dann
 * steht die Meldung, wie bisher, im Fehlerprotokoll des Webspace, denn PHP
 * schreibt sie selbst dorthin.
 */
function system_abbruch_pruefen(): void
{
    $e = error_get_last();
    if ($e === null || !in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR,
                                               E_COMPILE_ERROR, E_USER_ERROR], true)) {
        return;
    }
    $kennung = system_melden('php', 'Abbruch', (string)$e['message'],
                             ['datei' => system_datei((string)$e['file']),
                              'zeile' => (int)$e['line']], 'abbruch');
    if (!(PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') && !headers_sent()) {
        /* `exit` in einem Abschlussaufruf beendet auch alle folgenden — die
         * wartenden Eintraege also vorher. */
        system_nachtragen();
        system_fehlerseite($kennung);
    }
}
