<?php
declare(strict_types=1);

/**
 * E-Mail-Adressen: eine Schreibweise, eine Pruefung (M1-13).
 *
 * WARUM ES DIESE DATEI GIBT
 * Die Adresse ist die Kontokennung. Sie wurde an sieben Stellen entgegen-
 * genommen und an sieben Stellen unterschiedlich behandelt:
 *
 *   auth_salt.php        strtolower(trim(...))
 *   login.php            trim(...)                    -- Formularfeld
 *   login.php (Skript)   trim().toLowerCase()         -- fuer den Salz-Endpunkt
 *   reset_request.php    trim(...)
 *   admin_users.php      trim(...)
 *   admin_user.php       trim(...)
 *   einstellungen.php    trim(...)
 *   install.php          trim(...)
 *
 * DASS DAS BISHER FUNKTIONIERT HAT, LAG AN DER SORTIERREGEL DER DATENBANK,
 * nicht am Code: utf8mb4_unicode_ci vergleicht ohne Ruecksicht auf Gross- und
 * Kleinschreibung. Auf einer Installation mit unterscheidender Sortierregel
 * haette jede Anmeldung fehlgeschlagen, bei der die Adresse nicht exakt so
 * eingetippt wurde wie beim Anlegen — mit der Meldung "Anmeldung
 * fehlgeschlagen" und ohne jeden Hinweis auf die Ursache. Seit S6 (P0) legt
 * das Schema die Sortierregel ausdruecklich fest; diese Datei nimmt dem Code
 * die Abhaengigkeit davon ganz.
 *
 * EIN FEHLER, DEN DAS NEBENBEI BEHEBT
 * login.php meldete den Erfolg an den Zaehler des Salz-Endpunkts mit der
 * Adresse WIE GETIPPT, waehrend auth_salt.php unter der kleingeschriebenen
 * Fassung zaehlte. Wer "Max@..." tippte, leerte damit nie den eigenen
 * Salz-Zaehler — jede Anmeldung verbrauchte dort einen Versuch, ohne ihn je
 * zurueckzugeben.
 *
 * BESTANDSADRESSEN BLEIBEN, WIE SIE SIND
 * Bewusst keine Migration, die vorhandene Zeilen kleinschreibt: Die Spalte
 * traegt utf8mb4_unicode_ci, der Vergleich trifft also ohnehin. Eine
 * Datenaenderung ohne Wirkung waere Risiko ohne Gegenwert.
 *
 * WARUM EINE EIGENE DATEI
 * install.php laeuft VOR der Ersteinrichtung und kann weder db.php noch
 * validate_lib.php laden — beide brauchen config.php, die es dort noch nicht
 * gibt. Eine gemeinsame Fassung fuer alle acht Stellen kann deshalb nur in
 * einer Datei ohne Abhaengigkeiten stehen.
 */

/**
 * Die Schreibweise, unter der eine Adresse gespeichert und gesucht wird.
 *
 * strtolower und nicht mb_strtolower: Der Teil vor dem @ ist laut Norm
 * gross-/kleinschreibungsempfindlich, der Teil danach nicht. Wir schreiben
 * trotzdem alles klein — das tut das Browser-Skript seit jeher und die
 * Datenbank vergleicht ohnehin so. Eine Umwandlung nach Unicode-Regeln waere
 * hier riskanter als hilfreich: Sie ist sprachabhaengig (das tuerkische I) und
 * koennte zwei Adressen zusammenfallen lassen, die es nicht sollen.
 */
function email_normalisieren(?string $roh): string
{
    return strtolower(trim((string)$roh));
}

/**
 * Normalisieren UND pruefen in einem Schritt — fuer die vier Stellen, an
 * denen eine Adresse GESCHRIEBEN wird.
 *
 * Liefert die normalisierte Adresse oder null, wenn sie nicht als Adresse
 * durchgeht. Die Laengengrenze entspricht der Spalte (VARCHAR(190)): Eine
 * laengere Adresse wuerde beim Schreiben stillschweigend abgeschnitten und
 * damit zu einer ANDEREN Adresse, unter der sich niemand mehr anmelden kann.
 */
function email_pruefen(?string $roh): ?string
{
    $e = email_normalisieren($roh);
    if ($e === '' || strlen($e) > 190)                     { return null; }
    if (!filter_var($e, FILTER_VALIDATE_EMAIL))            { return null; }
    return $e;
}

/**
 * Ist dieser Datenbankfehler ein Verstoss gegen einen eindeutigen Schluessel?
 *
 * Gebraucht an den beiden Stellen, an denen eine Adresse geaendert wird
 * (M1-16). Dort wurde bisher JEDER Datenbankfehler als "diese Adresse wird
 * bereits verwendet" gemeldet — eine Meldung, die bei einer vollen Platte
 * oder einer abgerissenen Verbindung die Fehlersuche zuverlaessig in die
 * falsche Richtung schickt.
 *
 * Geprueft wird beides: die SQLSTATE-Klasse 23000 (Integritaetsverletzung,
 * herstellerunabhaengig) UND der Treibercode 1062 (MySQL/MariaDB:
 * "Duplicate entry"). 23000 allein waere zu weit — darunter faellt auch eine
 * verletzte Fremdschluesselbeziehung.
 */
function ist_dublettenfehler(PDOException $ex): bool
{
    return $ex->getCode() === '23000'
        && isset($ex->errorInfo[1]) && (int)$ex->errorInfo[1] === 1062;
}

/**
 * Hinweismail an die ALTE Adresse, wenn die Anmeldeadresse eines Kontos
 * gewechselt ist (Backlog Nr. 128, K-7).
 *
 * WOZU. Der Wechsel selbst ist jetzt nachweispflichtig (Profil: aktuelles
 * Passwort; Verwaltung: eine Adminsitzung). Beides kann uebernommen sein --
 * und dann ist die Mail an die alte Adresse die EINZIGE Stelle, an der die
 * rechtmaessige Besitzerin davon erfaehrt. Sie geht deshalb an die alte
 * Adresse und nicht an die neue: Die neue gehoert im Missbrauchsfall dem
 * anderen.
 *
 * WAS SIE NICHT IST. Keine Bestaetigung der neuen Adresse -- der
 * Double-Opt-In kommt mit R37.6 in P5. Bis dahin gilt die neue Adresse
 * sofort; die Mail ist die Warnung, nicht das Tor.
 *
 * SCHEITERT SIE, PASSIERT NICHTS WEITER. Der Wechsel ist zu diesem Zeitpunkt
 * geschrieben, und ihn zurueckzurollen, weil ein Mailserver klemmt, waere die
 * schlechtere Wahl: Dann steht ein Konto mit einer Adresse da, die die
 * Besitzerin gerade berichtigt hat. Der Fehlschlag geht ins Fehlerprotokoll.
 *
 * @param string $alt  bisherige Adresse (Empfaengerin)
 * @param string $neu  neue Adresse
 * @param string $wer  'profil' oder 'verwaltung'
 */
function profil_adresswechsel_melden(string $alt, string $neu,
                                     string $wer = 'profil'): bool
{
    require_once __DIR__ . '/smtp.php';
    if ($alt === '' || $alt === $neu) { return false; }

    $durch = $wer === 'verwaltung'
        ? "Die Änderung wurde in der Verwaltung vorgenommen."
        : "Die Änderung wurde im Profil dieses Kontos vorgenommen, nach Eingabe\n"
          . "des Passworts.";

    $ok = smtp_send($alt,
        'Anmeldeadresse geändert — Gen-EM Einsatzdokumentation Notarzt',
        "Hallo,\n\n"
        . "die Anmeldeadresse deines Zugangs zur Gen-EM Einsatzdokumentation Notarzt\n"
        . "wurde geändert:\n\n"
        . "  bisher: " . $alt . "\n"
        . "  jetzt:  " . $neu . "\n\n"
        . $durch . "\n\n"
        . "WARST DU DAS NICHT, handle bitte sofort: Melde dich mit deinem Passwort an\n"
        . "und setze die Adresse zurück, oder wende dich an die Verwaltung deiner\n"
        . "Installation. Diese Nachricht geht bewusst an die ALTE Adresse -- sie ist die\n"
        . "einzige, die im Missbrauchsfall noch dir gehört.\n\n"
        . "Bei Fragen oder Problemen wende dich gerne an philipp@gen-em.org.\n\n"
        . "Viele Grüße\nGen-EM Einsatzdokumentation Notarzt\n");
    if (!$ok) {
        error_log('Adresswechsel: Hinweismail an die alte Adresse ging nicht weg');
    }
    return $ok;
}
