<?php
declare(strict_types=1);

/**
 * DIE ANTWORT ABSCHLIESSEN, BEVOR LANGSAME ARBEIT BEGINNT.
 *
 * WARUM DAS HIER STEHT
 * Bei "Passwort vergessen" ist der Antworttext fuer eine vorhandene und eine
 * unbekannte Adresse absichtlich derselbe — die DAUER war es nicht: Bei einem
 * vorhandenen Konto lief ein vollstaendiges Mailgespraech, sonst kam die
 * Antwort sofort. Der Unterschied ist mit einer einzigen Anfrage je Adresse
 * messbar und verraet dasselbe wie eine unterschiedliche Meldung, nur leiser.
 *
 * Die Loesung ist nicht, den schnellen Zweig kuenstlich zu bremsen (das
 * verlangsamt jede Anfrage und schlaegt bei einem langsamen Mailserver
 * trotzdem durch), sondern den Versand aus der messbaren Antwortzeit
 * herauszunehmen: erst antworten, dann versenden.
 *
 * ZWEI WEGE, IN DIESER REIHENFOLGE
 *   1. fastcgi_finish_request() (PHP-FPM) bzw. litespeed_finish_request()
 *      (LiteSpeed). Beide beenden die Antwort verbindlich; das Skript laeuft
 *      danach ohne Zuhoerer weiter. Das ist der belastbare Weg.
 *   2. Sonst: Laenge nennen und Verbindungsende ankuendigen. Der Gegenpart
 *      hat den Rumpf dann vollstaendig und wartet ueblicherweise nicht
 *      weiter — aber "ueblicherweise" ist keine Zusicherung, weil ein
 *      vorgelagerter Server puffern darf.
 *
 * Rueckgabe: true nur bei Weg 1. Wer die Gleichheit der Antwortzeit
 * SICHERSTELLEN muss, prueft das ueber antwort_entkoppelbar() und legt bei
 * false zusaetzlich eine Mindestdauer darunter.
 *
 * Kein Cronjob noetig — und bewusst auch keine Warteschlange: Auf dieser
 * Installation laeuft die Wartung huckepack auf Anfragen, hoechstens einmal
 * taeglich. Eine Warteschlange haette den Link zum Zuruecksetzen genau so
 * lange liegen lassen, bis zufaellig jemand eine Seite aufruft.
 */
function antwort_entkoppelbar(): bool {
    return function_exists('fastcgi_finish_request')
        || function_exists('litespeed_finish_request');
}

function antwort_abschliessen(): bool {
    // Ohne diese Zeile beendet PHP das Skript, sobald der Gegenpart die
    // Verbindung schliesst — und genau das tut er hier gleich.
    ignore_user_abort(true);

    if (function_exists('fastcgi_finish_request')) {
        while (ob_get_level() > 0) { @ob_end_flush(); }
        @flush();
        fastcgi_finish_request();
        return true;
    }
    if (function_exists('litespeed_finish_request')) {
        while (ob_get_level() > 0) { @ob_end_flush(); }
        @flush();
        litespeed_finish_request();
        return true;
    }

    // Weg 2 braucht die Kopfzeilen. Sind sie schon raus, bleibt nur noch,
    // die Puffer zu leeren.
    if (headers_sent()) {
        while (ob_get_level() > 0) { @ob_end_flush(); }
        @flush();
        return false;
    }

    // Alles bisher Ausgegebene einsammeln. Aeussere Puffer stehen im
    // Ausgabestrom VOR den inneren, ob_get_clean() liefert aber von innen
    // nach aussen — deshalb wird vorn angefuegt.
    $inhalt = '';
    while (ob_get_level() > 0) {
        $stueck = ob_get_clean();
        if ($stueck !== false) { $inhalt = $stueck . $inhalt; }
    }
    header('Connection: close');
    header('Content-Length: ' . strlen($inhalt));
    echo $inhalt;
    @flush();
    return false;
}

/**
 * Minimaler SMTPS-Versand (implizites TLS, z. B. Port 465 bei Stalwart).
 * Bewusst ohne Composer-Abhaengigkeit, damit es auf jedem Webspace laeuft.
 * Rueckgabe: true bei Erfolg, sonst false (Details im error_log).
 *
 * $zeitlimit ist die Frist fuer Verbindungsaufbau und jede einzelne Antwort
 * des Mailservers. Der Vorgabewert gilt fuer Wege, bei denen niemand wartet.
 * Wo doch jemand wartet — die Uhr bei der Kopplung —, gehoert ein kuerzeres
 * Limit hin: Eine Kopplung darf nicht an einem langsamen Mailserver scheitern.
 */
/**
 * Ist SMTP ueberhaupt eingerichtet? (S2/AP6)
 *
 * WOFUER ES DIESE FUNKTION BRAUCHT. E-S2-15 verlangt zwei verschiedene Wege:
 * Bei ueberschrittener Speicherschwelle geht eine Mail an die Admin-Adresse —
 * „ohne eingerichtetes SMTP stattdessen dauerhafter Hinweis im
 * Admin-Bereich". Die Anwendung konnte diese beiden Faelle bis Web 11.2.0
 * nicht unterscheiden: `smtp_send()` liefert `false` fuer „Host falsch",
 * „Passwort falsch", „Netz weg" UND „gar nicht eingerichtet". Ein
 * Fehlschlagen ist aber etwas anderes als ein Nicht-Eingerichtetsein — beim
 * ersten soll es einen Versuch und eine Fehlermeldung geben, beim zweiten
 * gar keinen Versuch und einen stehenden Hinweis.
 *
 * GEPRUEFT WIRD DER HOST, nicht die Zugangsdaten: Ein Mailserver ohne
 * Authentifizierung ist eine gueltige Einrichtung (ein Relay im selben
 * Rechenzentrum), einer ohne Adresse nicht.
 */
function smtp_eingerichtet(): bool
{
    $alles = require __DIR__ . '/config.php';
    $cfg = $alles['smtp'] ?? [];
    return is_array($cfg) && trim((string)($cfg['host'] ?? '')) !== '';
}

/**
 * WAS AUS EINEM VERSAND WIRD (S8/AP4, Z-01).
 *
 * DIE FRAGE, DIE NIEMAND BEANTWORTEN KONNTE: „Kommt hier ueberhaupt Post
 * heraus?" `smtp_eingerichtet()` sagt nur, ob eine Adresse in der
 * `config.php` steht — nicht, ob der Server sie annimmt. Ein falsches
 * Passwort oder ein umgezogener Host fiel bisher erst auf, wenn jemand einen
 * Setz-Link erwartete, der nie ankam; im Fehlerprotokoll des Webspace stand
 * es, und dort sieht niemand nach.
 *
 * Vermerkt werden ZWEI Werte: wann zuletzt versendet wurde und ob es geklappt
 * hat. Nicht vermerkt wird, WAS versendet wurde und AN WEN — das gehoerte in
 * ein Protokoll, und ein Protokoll ueber Mailempfaenger ist etwas, das diese
 * Anwendung nicht fuehren will.
 *
 * SIE SCHREIBT NUR, WENN ES EINE DATENBANK GIBT. `smtp.php` ist die eine
 * Datei, die ohne alles auskommt — sie laeuft im Einrichter, bevor es eine
 * Datenbank gibt, und in `register_shutdown_function`, wo die Verbindung
 * schon zu sein kann. Ein Vermerk, der einen Versand scheitern liesse, waere
 * die Frage nicht wert; deshalb `function_exists` und `try`.
 */
function smtp_versand_vermerken(bool $ok): void
{
    if (!function_exists('db')) { return; }
    try {
        db()->prepare('INSERT INTO app_state (k, v) VALUES (?, ?), (?, ?)
                       ON DUPLICATE KEY UPDATE v = VALUES(v)')
            ->execute(['smtp_last', gmdate('Y-m-d\TH:i:s\Z'),
                       'smtp_last_ok', $ok ? '1' : '0']);
    } catch (Throwable $ex) {
        /* Still: Der Versand ist gelaufen, der Vermerk nicht. Das ist die
         * richtige Reihenfolge der Wichtigkeit. */
    }
}

function smtp_send(string $toEmail, string $subject, string $textBody,
                   int $zeitlimit = 15): bool {
    /* Einmal laden, beides entnehmen (M1-14).
     *
     * config.php wurde zweimal eingelesen — hier fuer den SMTP-Teil und
     * weiter unten noch einmal fuer die Basisadresse im EHLO. Das ist nicht
     * nur ein zweiter Dateizugriff: Zwei Ladevorgaenge koennen zwei
     * verschiedene Staende sehen, wenn die Datei dazwischen ersetzt wird.
     * Unwahrscheinlich, aber es gibt keinen Grund dafuer. */
    $alles = require __DIR__ . '/config.php';
    $cfg   = $alles['smtp'];

    /* ZEILENUMBRUECHE IM EMPFAENGER ABLEHNEN (M1-14).
     *
     * $toEmail geht ungeprueft in zwei Zeilen des Protokolls: in
     * "RCPT TO:<...>" und in den Kopf "To: <...>". Enthaelt die Adresse ein
     * CR oder LF, endet die Zeile dort — und alles danach ist fuer den
     * Mailserver eine EIGENE Anweisung beziehungsweise eine eigene Kopfzeile.
     * Damit lassen sich stille Mitleser (Bcc) eintragen oder der Nachricht
     * ein fremder Inhalt anhaengen.
     *
     * Seit Web 4.5.0 kommen alle Aufrufer aus email_pruefen() und koennen
     * gar keine solche Adresse liefern. Diese Pruefung steht trotzdem hier:
     * Die Absicherung gehoert an die Stelle, die das Protokoll spricht, nicht
     * in die Disziplin der Aufrufer. */
    if ($toEmail === '' || strcspn($toEmail, "\r\n") !== strlen($toEmail)
        || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        error_log('SMTP: unzulaessige Empfaengeradresse abgewiesen');
        return false;
    }

    $fp = @stream_socket_client('ssl://' . $cfg['host'] . ':' . $cfg['port'],
        $errno, $errstr, $zeitlimit, STREAM_CLIENT_CONNECT,
        stream_context_create(['ssl' => ['verify_peer' => true]]));
    if (!$fp) {
        error_log("SMTP connect: $errstr");
        smtp_versand_vermerken(false);
        return false;
    }
    stream_set_timeout($fp, $zeitlimit);

    $expect = function (string $code) use ($fp): bool {
        do { $line = fgets($fp, 1024); if ($line === false) return false; }
        while (isset($line[3]) && $line[3] === '-');           // Multiline-Antworten
        return strncmp($line, $code, 3) === 0;
    };
    $send = function (string $cmd) use ($fp): void { fwrite($fp, $cmd . "\r\n"); };

    $ok = $expect('220');
    $send('EHLO ' . parse_url($alles['app']['base_url'], PHP_URL_HOST));
    $ok = $ok && $expect('250');
    $send('AUTH LOGIN');                        $ok = $ok && $expect('334');
    $send(base64_encode($cfg['user']));         $ok = $ok && $expect('334');
    $send(base64_encode($cfg['pass']));         $ok = $ok && $expect('235');
    $send('MAIL FROM:<' . $cfg['from'] . '>');  $ok = $ok && $expect('250');
    $send('RCPT TO:<' . $toEmail . '>');        $ok = $ok && $expect('250');
    $send('DATA');                              $ok = $ok && $expect('354');

    $headers = 'From: ' . mb_encode_mimeheader($cfg['from_name']) . ' <' . $cfg['from'] . ">\r\n"
             . 'To: <' . $toEmail . ">\r\n"
             . 'Subject: ' . mb_encode_mimeheader($subject) . "\r\n"
             . "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n"
             . 'Date: ' . date(DATE_RFC2822) . "\r\n";
    $body = preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $textBody)); // Dot-Stuffing
    $send($headers . "\r\n" . $body . "\r\n.");
    $ok = $ok && $expect('250');
    $send('QUIT');
    fclose($fp);
    if (!$ok) error_log('SMTP: Versand an ' . $toEmail . ' fehlgeschlagen');
    smtp_versand_vermerken($ok);
    return $ok;
}
