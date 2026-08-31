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
 * KEINE ANMELDUNG. Der Aufruf über die Adresse legitimiert sich mit dem
 * Token, nicht mit einer Sitzung — ein Zeitplandienst hat keine. Deshalb
 * lädt diese Datei ausdrücklich NICHT `auth_guard.php`: Der würde den
 * huckepack-Weg auslösen und damit den Job aus dem Job heraus starten.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jobs_lib.php';

$aufKommandozeile = PHP_SAPI === 'cli';

/* ---- 1. Kommandozeile ---------------------------------------------------- */

if ($aufKommandozeile) {
    $nur = null;
    foreach (array_slice($argv, 1) as $arg) {
        if ($arg === '--hilfe' || $arg === '-h') {
            fwrite(STDOUT, "Aufruf: php jobs.php [jobname …]\n"
                 . "Ohne Angabe laufen alle fälligen Jobs.\n"
                 . "Bekannt: " . implode(', ', array_keys(jobs_katalog())) . "\n");
            exit(0);
        }
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
        fwrite(STDOUT, sprintf("%-14s %s · erledigt %d%s%s\n", $name,
            $b['fertig'] ? 'fertig' : 'Rest offen',
            $b['erledigt'],
            $b['rueckstand'] !== null ? ' · Rückstand ' . $b['rueckstand'] : '',
            !empty($b['fehler']) ? ' · FEHLER: ' . $b['fehler'] : ''));
    }
    exit($fehler === 0 ? 0 : 1);
}

/* ---- 2. Adresse mit Token ------------------------------------------------ */

$t0 = microtime(true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/ratelimit_lib.php';

/* SPERRE VOR JEDER WEITEREN ARBEIT — dasselbe Muster wie in `pair.php`.
 * Sonst bliebe der Aufwand, den eine Anfrage ausloest, trotz Sperre als
 * Angriffsflaeche offen. Derselbe Topf wie beim Koppeln: zehn Fehlversuche
 * in zehn Minuten, dann zehn Minuten Ruhe. */
if (!rate_erlaubt('pair')) {
    rate_gleiche_dauer($t0);
    http_response_code(429);
    echo json_encode(['error' => 'zu_viele_versuche']);
    exit;
}

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');

$erwartet = '';
try {
    $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
    $st->execute([JOB_TOKEN_SCHLUESSEL]);
    $erwartet = (string)($st->fetchColumn() ?: '');
} catch (Throwable $ex) {
    rate_gleiche_dauer($t0);
    http_response_code(500);
    echo json_encode(['error' => 'datenbank']);
    exit;
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
    http_response_code(403);
    // Nicht sagen, ob ueberhaupt ein Token eingerichtet ist. Wer den Weg
    // benutzen darf, kennt es aus dem Wartungsbereich.
    echo json_encode(['error' => 'token']);
    exit;
}

$bericht = jobs_lauf('token');
echo json_encode(['ok' => true, 'jobs' => $bericht],
                 JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
