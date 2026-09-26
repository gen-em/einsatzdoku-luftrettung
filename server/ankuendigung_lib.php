<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * ANKUENDIGUNG UND RUNDMAIL (P5c/AP1, E-P5c-13, -55, -56, -60; R38)
 * ===========================================================================
 *
 *     ankuendigung()                 // die geltende, oder null
 *     ankuendigung_gespeichert()     // was dasteht, auch abgelaufen (Karte)
 *     ankuendigung_setzen($t, $ton, $bisUtc)   // null oder Fehlersatz
 *     ankuendigung_entfernen()
 *     ankuendigung_weggeklickt($a) / ankuendigung_wegklicken()
 *     rundmail_empfaenger()  rundmail_heute_gesendet()  rundmail_senden()
 *
 * WOFUER. Eine Wartung am Dienstagabend wusste bis hierher nur, wer die
 * BetreiberIn fragte. Die Ankuendigung steht als Streifen ueber jeder Seite
 * — auch ueber der Anmeldung, denn eine angekuendigte Wartung betrifft den,
 * der sich gerade anmelden will, am meisten (E-P5c-60) —, bis sie ablaeuft.
 * Die Rundmail traegt denselben Text zu denen, die gerade nicht hinsehen.
 *
 * DREI `app_state`-SCHLUESSEL, KEINE MIGRATION (E-P5c-55). Der Text ist
 * deshalb hoechstens APP_STATE_MAX Byte lang (190); das Formular sagt es und
 * zaehlt mit. Fuer eine Zeile ueber jeder Seite ist das die richtige Laenge
 * — was laenger ist, gehoert in eine Mail oder ins Handbuch, nicht in einen
 * Streifen, den jede Seite traegt.
 *
 * WEGKLICKEN JE SITZUNG, nicht je Browser. Wer die Ankuendigung schliesst,
 * sieht sie bis zum Abmelden nicht mehr; beim naechsten Anmelden steht sie
 * wieder da, bis sie ablaeuft (E-P5c-13). Gemerkt wird deshalb in der
 * Sitzung und nicht in einem Cookie oder im `localStorage` — beide
 * ueberlebten das Abmelden. `login.php` loescht die Marke beim Anmelden,
 * sonst truege ein Schliessen AUF der Anmeldeseite in die Sitzung danach
 * hinueber (`session_regenerate_id()` behaelt die Daten).
 *
 * GEMERKT WIRD DIE KENNUNG DER ANKUENDIGUNG, nicht „weggeklickt ja". Eine
 * NEUE Ankuendigung — anderer Text, anderer Ton, anderes Ende — soll auch
 * bei denen erscheinen, die die alte geschlossen haben.
 */

/* Der Schluessel und seine drei Teile. `_bis` ist die ISO-UTC-Marke. */
const ANKUENDIGUNG_K_TEXT = 'ankuendigung_text';
const ANKUENDIGUNG_K_TON  = 'ankuendigung_ton';
const ANKUENDIGUNG_K_BIS  = 'ankuendigung_bis';

/** Wann die letzte Rundmail hinausging (ISO-UTC-Marke). */
const RUNDMAIL_K_ZULETZT = 'rundmail_zuletzt';

/**
 * Die Toene — dieselben zwei, die `.meldung` dafuer hat (Design.md 9.5).
 * Die Beschriftung nennt die Farbe mit, weil die BetreiberIn sie waehlt,
 * bevor sie sie sieht.
 */
const ANKUENDIGUNG_TOENE = [
    'info' => 'Hinweis — blau',
    'warn' => 'Warnung — orange',
];

/** Der Sitzungsschluessel fuer das Wegklicken. */
const ANKUENDIGUNG_SITZUNG = 'ankuendigung_weg';

/**
 * Was gespeichert ist — auch eine abgelaufene Ankuendigung.
 *
 * @return array{text:string, ton:string, bis:int, abgelaufen:bool, kennung:string}|null
 *
 * Die Karte in den Servereinstellungen braucht auch die abgelaufene: Sie
 * zeigt den Text zum Wiederverwenden und sagt, dass er nicht mehr erscheint.
 */
function ankuendigung_gespeichert(): ?array
{
    $w = app_state_mehrere([ANKUENDIGUNG_K_TEXT, ANKUENDIGUNG_K_TON, ANKUENDIGUNG_K_BIS]);
    $text = trim((string)($w[ANKUENDIGUNG_K_TEXT] ?? ''));
    if ($text === '') { return null; }
    $ton = (string)($w[ANKUENDIGUNG_K_TON] ?? '');
    if (!isset(ANKUENDIGUNG_TOENE[$ton])) { $ton = 'info'; }
    $bis = iso_utc_lesen($w[ANKUENDIGUNG_K_BIS] ?? null) ?? 0;
    return [
        'text'       => $text,
        'ton'        => $ton,
        'bis'        => $bis,
        'abgelaufen' => $bis <= time(),
        /* Die Kennung bindet das Wegklicken an GENAU diese Ankuendigung.
         * Kurz, weil sie in der Sitzung steht und nichts schuetzt. */
        'kennung'    => substr(hash('sha256', $ton . "\n" . $bis . "\n" . $text), 0, 16),
    ];
}

/** Die geltende Ankuendigung, oder `null` (keine, oder abgelaufen). */
function ankuendigung(): ?array
{
    $a = ankuendigung_gespeichert();
    return ($a === null || $a['abgelaufen']) ? null : $a;
}

/**
 * Eine Ankuendigung setzen. `null` heisst gespeichert, sonst der Satz, warum
 * nicht.
 *
 * @param int $bisUtc Unix-Sekunden. Muss in der Zukunft liegen: Eine
 *                    Ankuendigung, die schon abgelaufen ist, erschiene nie —
 *                    und die BetreiberIn saehe ein „gespeichert" fuer nichts.
 */
function ankuendigung_setzen(string $text, string $ton, int $bisUtc): ?string
{
    /* Zeilenumbrueche werden zu Leerzeichen: Der Streifen ist EIN Absatz
     * (`.meldung` ist eine Flexzeile, ein zweiter Absatz stellte sich daneben)
     * und die Mail bekommt den Text so, wie der Streifen ihn zeigt. */
    $text = trim((string)preg_replace('/\s+/u', ' ', $text));
    if ($text === '') {
        return 'Der Text fehlt. Zum Abschalten gibt es „Entfernen".';
    }
    if (strlen($text) > APP_STATE_MAX) {
        return 'Der Text ist ' . strlen($text) . ' Byte lang, erlaubt sind '
             . APP_STATE_MAX . ' — Umlaute zählen doppelt.';
    }
    if (!isset(ANKUENDIGUNG_TOENE[$ton])) {
        return 'Unbekannter Ton.';
    }
    if ($bisUtc <= time()) {
        return '„Sichtbar bis" liegt in der Vergangenheit — die Ankündigung erschiene nie.';
    }
    $ok = app_state_setzen_mehrere([
        ANKUENDIGUNG_K_TEXT => $text,
        ANKUENDIGUNG_K_TON  => $ton,
        ANKUENDIGUNG_K_BIS  => iso_utc($bisUtc),
    ]);
    return $ok ? null : 'Die Ankündigung ließ sich nicht speichern.';
}

/** Die Ankuendigung entfernen — alle drei Teile. */
function ankuendigung_entfernen(): void
{
    app_state_loeschen(ANKUENDIGUNG_K_TEXT, ANKUENDIGUNG_K_TON, ANKUENDIGUNG_K_BIS);
}

/** Hat diese Sitzung GENAU diese Ankuendigung geschlossen? */
function ankuendigung_weggeklickt(array $a): bool
{
    return session_status() === PHP_SESSION_ACTIVE
        && ($_SESSION[ANKUENDIGUNG_SITZUNG] ?? '') === $a['kennung'];
}

/**
 * Die geltende Ankuendigung fuer diese Sitzung schliessen.
 *
 * Ohne geltende Ankuendigung geschieht nichts — auch das ist die richtige
 * Antwort auf ein spaetes Klicken nach dem Ablauf.
 */
function ankuendigung_wegklicken(): void
{
    $a = ankuendigung();
    if ($a !== null && session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION[ANKUENDIGUNG_SITZUNG] = $a['kennung'];
    }
}

/* ---- Rundmail ------------------------------------------------------------ */

/**
 * Die Konten, die eine Rundmail bekommen — „erreichbar" (E-P5c-56).
 *
 * DREI BEGRIFFE STATT EINMAL „AKTIV". Erreichbar heisst: Status `aktiv`,
 * Passwort gesetzt, nicht das Demo-Konto. Ein Konto ohne Passwort hat seine
 * Einladung nie angenommen — es bekaeme eine Ankuendigung fuer eine
 * Anwendung, die es gar nicht benutzt. Ein gesperrtes Konto kommt nicht
 * herein, also betrifft es die Wartung nicht. Und die Adresse des
 * Demo-Kontos steht im Handbuch; sie gehoert niemandem.
 *
 * @return list<string> Adressen, nach Kontonummer
 */
function rundmail_empfaenger(): array
{
    require_once __DIR__ . '/demo_lib.php';
    $st = db()->prepare("SELECT email FROM users
                         WHERE status = 'aktiv' AND password_hash IS NOT NULL AND id <> ?
                         ORDER BY id");
    $st->execute([demo_id() ?? 0]);
    return array_values(array_filter(
        array_map('strval', $st->fetchAll(PDO::FETCH_COLUMN)),
        static fn(string $m): bool => $m !== ''));
}

/**
 * Ging heute schon eine Rundmail hinaus? Dann ihr Zeitpunkt (UTC-Marke),
 * sonst `null`.
 *
 * „HEUTE" IST DER KALENDERTAG DER ANLAGE (`app.timezone`), nicht 24 Stunden.
 * Eine Rundmail um 23:50 und eine um 00:10 sind zwei Tage — und eine
 * Ankuendigung, die am Abend kommt, soll am naechsten Morgen berichtigt
 * werden koennen.
 */
function rundmail_heute_gesendet(): ?string
{
    $zuletzt = app_state_lesen(RUNDMAIL_K_ZULETZT);
    if ($zuletzt === null || $zuletzt === '') { return null; }
    return fmt_local($zuletzt, 'Y-m-d') === heute_lokal() ? $zuletzt : null;
}

/**
 * Die geltende Ankuendigung als Rundmail einreihen.
 *
 * @return array{ok:bool, zahl:int, meldung:string}
 *
 * NUR EINREIHEN, NICHT SOFORT VERSUCHEN (F-P5c-29). `mail_einreihen()`
 * versucht jede Nachricht sofort, bis zu MAIL_BUDGET_S je Stueck — bei 40
 * Empfaengern und einem zaehen Mailserver hinge diese Seite bis zu 200 s.
 * Die Warteschlange traegt sie hinaus, zehn je Joblauf.
 *
 * HOECHSTENS EINE JE TAG, und die Marke wird GESETZT, BEVOR eingereiht
 * wird: Ein zweiter Klick, waehrend der erste noch einreiht, findet sie
 * schon vor. Scheitert danach alles, steht die Marke trotzdem — lieber eine
 * Rundmail, die morgen wiederholt werden muss, als zwei an einem Tag.
 */
function rundmail_senden(): array
{
    require_once __DIR__ . '/mail_lib.php';
    require_once __DIR__ . '/protokoll_lib.php';

    $a = ankuendigung();
    if ($a === null) {
        return ['ok' => false, 'zahl' => 0,
                'meldung' => 'Es gibt keine geltende Ankündigung, die hinausgehen könnte.'];
    }
    if (rundmail_heute_gesendet() !== null) {
        return ['ok' => false, 'zahl' => 0,
                'meldung' => 'Heute ging schon eine Rundmail hinaus — höchstens eine je Tag.'];
    }
    if (!smtp_eingerichtet()) {
        return ['ok' => false, 'zahl' => 0,
                'meldung' => 'Der Mailversand ist nicht eingerichtet (Betrieb → Status).'];
    }
    $ziele = rundmail_empfaenger();
    if ($ziele === []) {
        return ['ok' => false, 'zahl' => 0, 'meldung' => 'Es gibt kein erreichbares Konto.'];
    }

    app_state_setzen(RUNDMAIL_K_ZULETZT, iso_utc());

    $eingereiht = 0;
    foreach ($ziele as $m) {
        if (mail_einreihen('rundmail', $m, ['text' => $a['text']], false) === MAIL_WARTET) {
            $eingereiht++;
        }
    }

    /* EIN EINTRAG FUER DIE RUNDMAIL, NICHT EINER JE EMPFAENGER. Das
     * Protokoll sagt, wer wann was an wie viele geschickt hat; wer es im
     * Einzelnen bekommen hat, steht nirgends — und soll es auch nicht
     * (E-P5c-38: der Reiter E-Mail nennt nie Empfaenger). */
    protokoll('verwaltung', 'rundmail',
              'Rundmail an ' . $eingereiht . ' von ' . count($ziele) . ' erreichbaren Konten eingereiht',
              ['eingereiht' => $eingereiht, 'erreichbar' => count($ziele)]);

    return ['ok' => $eingereiht > 0, 'zahl' => $eingereiht,
            'meldung' => $eingereiht === count($ziele)
                ? 'Rundmail an ' . $eingereiht . ' Konten eingereiht — sie geht über die '
                  . 'Warteschlange hinaus.'
                : 'Rundmail an ' . $eingereiht . ' von ' . count($ziele)
                  . ' Konten eingereiht; die übrigen Adressen wurden abgewiesen.'];
}
