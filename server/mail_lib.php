<?php
declare(strict_types=1);

/**
 * MAIL: EIN KATALOG UND EINE WARTESCHLANGE (P5a/AP5, E-P5a-14, E-P5a-39).
 *
 * ---------------------------------------------------------------------------
 * WARUM ES DIESE DATEI GIBT — zwei Gruende, und der zweite ist der groessere
 * ---------------------------------------------------------------------------
 *
 * 1. NICHTS DARF VERLORENGEHEN, WEIL EIN SMTP-SERVER SCHWEIGT. Bis Web
 *    20.7.0 rief jeder Aufrufer `smtp_send()` unmittelbar. Scheiterte der
 *    Versand, war die Nachricht WEG — der Reset-Link, die Einladung, die
 *    Warnung vor der vollen Platte. `smtp_versand_vermerken(false)` hielt nur
 *    fest, DASS etwas schiefging.
 *
 * 2. DIE TEXTE STANDEN AN ACHT STELLEN. 82 Zeilen Mailtext, verteilt ueber
 *    `admin_users.php`, `admin_user.php`, `email_lib.php`, `pair.php`,
 *    `reset_request.php`, `speicher_lib.php`, `adminbackup_lib.php` und
 *    `betrieb_status.php`. Dass das driftet, ist keine Vermutung: Neun
 *    Betreffzeilen hiessen „… — Gen-EM Einsatzdokumentation Notarzt", eine
 *    hiess „Testmail — Einsatzdokumentation Notarzt" — ohne „Gen-EM".
 *    Niemandem aufgefallen, weil man dafuer acht Dateien nebeneinanderlegen
 *    muesste.
 *
 *    Das Haus arbeitet ueberall sonst mit Katalogen (`mission_fields.php`,
 *    `RATE_GRENZEN`, `migrationen_katalog()`, `jobs_katalog()`), und
 *    `CLAUDE.md` 4 sagt es als Grundsatz: „Ein neues Feld, das an fuenf
 *    Stellen von Hand eingebaut wird, ist ein Fehler." Mail war die letzte
 *    Ausnahme davon.
 *
 * ---------------------------------------------------------------------------
 * DER ERSTE VERSUCH LAEUFT SOFORT — und das ist keine Bequemlichkeit
 * ---------------------------------------------------------------------------
 *
 * E-PP-05: Double-Opt-In, Kopplungsmail und Passwort-Reset warten nicht auf
 * den naechsten Job-Lauf. Und sie duerfen es auch gar nicht: Der Job laeuft
 * huckepack auf einer Web-Anfrage, hoechstens alle fuenf Minuten, und nur
 * wenn ueberhaupt jemand angemeldet eine Seite aufruft. Nachts, am
 * Wochenende oder auf einer stillen Installation laeuft er GAR NICHT. Eine
 * Warteschlange ohne synchronen ersten Versuch waere fuer einen Reset-Link
 * kein Fortschritt, sondern ein Rueckschritt.
 *
 * ---------------------------------------------------------------------------
 * DREI ZUSTAENDE STATT EINES BOOL — aus dem Angriff auf den Entwurf
 * ---------------------------------------------------------------------------
 *
 * `smtp_send()` gab `true`/`false`. Sieben Aufrufer lasen `false` als „geht
 * nie". Mit einer Warteschlange heisst es aber „noch nicht", und das ist
 * etwas anderes:
 *
 *   - `admin_users.php` zeigte bei `false` den Einladungslink IM KLARTEXT am
 *     Bildschirm, damit die Admin ihn von Hand weitergeben kann. Bei „noch
 *     nicht" waere das ein unnoetig offengelegter Token — die Mail geht in
 *     fuenf Minuten ja doch hinaus.
 *   - `adminbackup_lib.php` und `speicher_lib.php` setzen ihre Schwellenmarke
 *     NUR bei Erfolg („DIE MARKE WIRD NACH DEM VERSAND GESETZT, nicht
 *     davor"). Bei „noch nicht" gilt die Schwelle als ungemeldet, und der
 *     naechste taegliche Lauf reiht dieselbe Warnung erneut ein. Eine
 *     dreitaegige Mailstoerung ergaebe drei Zeilen fuer dieselbe Schwelle.
 *
 * Deshalb `MAIL_ZUGESTELLT` | `MAIL_WARTET` | `MAIL_ABGELEHNT`. Die
 * Markenstellen zaehlen `WARTET` als erledigt; `ABGELEHNT` heisst „gar nicht
 * erst eingereiht" (unbekannter Schluessel, fehlender Pflichtwert, kein SMTP
 * eingerichtet) und ist der einzige Fall, in dem ein Aufrufer einen Ersatzweg
 * anbieten muss.
 *
 * ---------------------------------------------------------------------------
 * WAS BEIM ENDZUSTAND GELEERT WIRD — und warum nicht alles
 * ---------------------------------------------------------------------------
 *
 *   Zustand        empfaenger   betreff   text
 *   offen          bleibt       bleibt    bleibt   (sonst kann nicht gesendet werden)
 *   zugestellt     FAELLT       FAELLT    FAELLT
 *   unzustellbar   bleibt       bleibt    FAELLT
 *   ueberholt      FAELLT       FAELLT    FAELLT
 *   zu_spaet       FAELLT       FAELLT    FAELLT
 *
 * `text` faellt IMMER: Einladung und Reset tragen einen GUELTIGEN Token im
 * Rumpf. Bisher lebte der nur in der Mail; in der Datenbank stand allein sein
 * Hash. Er darf nicht 30 Tage hier liegen und in jeder Komplettsicherung
 * mitfahren.
 *
 * BEI `unzustellbar` BLEIBT DIE ADRESSE, und das ist eine ausdrueckliche
 * Ausnahme von der Zusage in `smtp.php`: „Die Einladung an X kam nie an" ist
 * ohne X wertlos, und E-P5a-14 verlangt die Liste „mit Empfaenger und Grund".
 * Eine Liste GESCHEITERTER Zustellungen ist kein Protokoll darueber, wer Post
 * BEKOMMEN hat, sondern eine Maengelliste. Die Ausnahme steht im Kopf von
 * `smtp.php` neben der Zusage — nicht davon getrennt.
 *
 * ---------------------------------------------------------------------------
 * DIE LEITER UND DIE LEBENSDAUER — der zweite Angriffsbefund
 * ---------------------------------------------------------------------------
 *
 * Fuenf Versuche ueber 24 h (5 min, 30 min, 2 h, 8 h, 24 h). Ein Reset-Token
 * lebt aber EINE STUNDE. Die Stufen 4 und 5 koennten fuer `passwort_reset`
 * gar nichts anderes mehr zustellen als einen toten Link — samt dem Satz „es
 * gilt immer nur der zuletzt verschickte" im Rumpf.
 *
 * Deshalb traegt jeder Katalogeintrag eine `frist` in Sekunden. Beim
 * Einreihen wird daraus `gueltig_bis`; ein Versuch, der nach diesem Zeitpunkt
 * faellig waere, wird nicht mehr unternommen — die Zeile geht auf `zu_spaet`.
 * Besser gar nichts als ein toter Link.
 *
 * UND: `mail_einreihen()` setzt offene Zeilen mit gleichem Schluessel und
 * gleicher Adresse auf `ueberholt`. Fordert jemand zweimal einen Link an,
 * entwertet der zweite den ersten ohnehin (`password_resets`) — die erste
 * Zeile wuerde sonst Minuten spaeter einen toten Link ausliefern.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/smtp.php';
require_once __DIR__ . '/instanz_lib.php';

/** Ergebnis von `mail_einreihen()`. Siehe den Kopf, Abschnitt „drei Zustaende". */
const MAIL_ZUGESTELLT = 'zugestellt';
const MAIL_WARTET     = 'wartet';
const MAIL_ABGELEHNT  = 'abgelehnt';

/**
 * Die Wiederholungsleiter in Sekunden — fuenf Versuche ueber 24 h.
 * Der Abstand ZUM VORIGEN Versuch, nicht zum Einreihen.
 */
const MAIL_LEITER = [300, 1800, 7200, 28800, 86400];

/** Zeitbudget des einzelnen Zustellversuchs, Verbindung UND Senden zusammen. */
const MAIL_BUDGET_S = 5;

/**
 * Unter so vielen Sekunden Restzeit wird gar nicht erst angefangen.
 *
 * DIE ERSTE FASSUNG VERLANGTE DIE VOLLEN 5 s, und das war ein stiller
 * Totalausfall: Am Huckepack-Weg stehen 3,0 s fuer ALLE Jobs zur Verfuegung,
 * also war `$zeitLinks() <= 5` beim ersten Durchgang immer wahr — der Job
 * brach ab, bevor er eine einzige Nachricht versuchte. Gemessen: „erledigt 0"
 * bei drei faelligen Zeilen. Auf einer Installation ohne Cron waere die
 * Warteschlange nie geleert worden, und nichts haette es gemeldet.
 *
 * Richtig ist, mit dem zu arbeiten, was da ist: Der Versuch bekommt die
 * RESTZEIT als Budget, hoechstens MAIL_BUDGET_S. Ein gesunder Mailserver
 * antwortet in Millisekunden (gemessen: 0,05 s) — 1,5 s reichen dafuer
 * reichlich. Ein haengender wird dafuer frueher abgeschnitten, und das ist
 * genau der Zweck.
 */
const MAIL_MINDEST_S = 1.5;

/** Hoechstens so viele Zeilen je Job-Lauf — der Huckepack-Weg hat 3 s. */
const MAIL_JE_LAUF = 10;

/**
 * DER NACHRICHTENKATALOG.
 *
 * Je Eintrag:
 *   art       Fuer die Liste und spaeter den Reiter im Protokoll (P5c).
 *   frist     Sekunden, die die Nachricht sinnvoll bleibt. `null` = unbegrenzt.
 *             Ein Versuch nach Ablauf wird nicht mehr unternommen.
 *   pflicht   Werte, die `$daten` tragen MUSS. Fehlt einer, wirft die
 *             Funktion — statt „Hallo, , dein Link: " zu verschicken.
 *   betreff   fn(array $daten): string
 *   text      fn(array $daten): string
 *
 * DER NAME KOMMT AUS `instanz_name()`, nicht aus einer Zeichenkette. Sonst
 * haette der Katalog den Fehler, den er beseitigen soll — nur an einer
 * Stelle statt an acht.
 */
function mail_katalog(): array
{
    $n = instanz_name();

    return [
        'einladung' => [
            'art' => 'konto', 'frist' => 86400, 'pflicht' => ['link'],
            'betreff' => fn(array $d): string => 'Willkommen bei der ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                "für dich wurde ein Zugang zur " . $n . " angelegt.\n"
                . "Über den folgenden Link legst du dein persönliches Passwort fest — der Link ist\n"
                . "24 Stunden gültig:\n\n"
                . $d['link'],
                "Dabei wird auch dein Wiederherstellungsschlüssel angezeigt. Bitte notiere ihn dir\n"
                . "sicher — ohne ihn lassen sich die verschlüsselten Angaben nach einem späteren\n"
                . "Passwort-Reset von niemandem mehr öffnen."),
        ],

        /* ZWEI EINTRAEGE FUER DENSELBEN LINK, und das ist kein Versehen:
         * `admin_user.php` (Verwaltung loest aus) und `reset_request.php`
         * (die Nutzerin selbst) schickten bis Web 20.7.0 fast denselben Text
         * — die Selbstbedienung aber MIT dem Satz „Falls du das nicht
         * angefordert hast, kannst du diese E-Mail einfach ignorieren".
         * Der gehoert dorthin und nur dorthin: Wer die Verwaltung gebeten
         * hat zurueckzusetzen, hat es angefordert. Beim Zusammenlegen waere
         * dieser Unterschied verlorengegangen, ohne dass es auffiele. */
        'passwort_neu' => [
            'art' => 'konto', 'frist' => 3600, 'pflicht' => ['link'],
            'betreff' => fn(array $d): string => 'Neues Passwort — ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                "für deinen Zugang zur " . $n . " wurde ein neues\n"
                . "Passwort angefordert. Über den folgenden Link kannst du es setzen — der Link ist\n"
                . "eine Stunde gültig:\n\n"
                . $d['link'],
                "Dafür brauchst du deinen Wiederherstellungsschlüssel, den du bei der Einrichtung\n"
                . "erhalten hast.\n\n"
                . "Ein zuvor angeforderter Link ist damit ungültig geworden — es gilt immer nur der\n"
                . "zuletzt verschickte."),
        ],
        'passwort_reset' => [
            'art' => 'konto', 'frist' => 3600, 'pflicht' => ['link'],
            'betreff' => fn(array $d): string => 'Neues Passwort — ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                "für deinen Zugang zur " . $n . " wurde ein neues\n"
                . "Passwort angefordert. Über den folgenden Link kannst du es setzen — der Link ist\n"
                . "eine Stunde gültig:\n\n"
                . $d['link'],
                "Dafür brauchst du deinen Wiederherstellungsschlüssel, den du bei der Einrichtung\n"
                . "erhalten hast.\n\n"
                . "Ein zuvor angeforderter Link ist damit ungültig geworden — es gilt immer nur der\n"
                . "zuletzt verschickte.\n\n"
                . "Falls du das nicht angefordert hast, kannst du diese E-Mail einfach ignorieren —\n"
                . "es wurde nichts geändert."),
        ],

        'adresswechsel' => [
            'art' => 'konto', 'frist' => null, 'pflicht' => ['alt', 'neu', 'durch'],
            'betreff' => fn(array $d): string => 'Anmeldeadresse geändert — ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                "die Anmeldeadresse deines Zugangs zur " . $n . "\n"
                . "wurde geändert:\n\n"
                . "  bisher: " . $d['alt'] . "\n"
                . "  jetzt:  " . $d['neu'] . "\n\n"
                . $d['durch'],
                "WARST DU DAS NICHT, handle bitte sofort: Melde dich mit deinem Passwort an\n"
                . "und setze die Adresse zurück, oder wende dich an die Verwaltung deiner\n"
                . "Installation. Diese Nachricht geht bewusst an die ALTE Adresse — sie ist die\n"
                . "einzige, die im Missbrauchsfall noch dir gehört."),
        ],

        /* DIE DREI NACHRICHTEN DES KONTO-LEBENSZYKLUS (P5b/AP5, E-P5b-16). */

        'adresse_bestaetigen' => [
            'art' => 'konto', 'frist' => 86400, 'pflicht' => ['link', 'alt'],
            'betreff' => fn(array $d): string => 'Neue Anmeldeadresse bestätigen — ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                "für deinen Zugang zur " . $n . " soll diese Adresse die neue\n"
                . "Anmeldeadresse werden. Bisher ist es " . $d['alt'] . ".\n\n"
                . "Bestätige den Wechsel hier:\n\n"
                . "  " . $d['link'] . "\n\n"
                . "Der Link gilt 24 Stunden. Bis zum Klick bleibt die bisherige Adresse\n"
                . "die gültige — du kannst dich also weiter wie gewohnt anmelden.",
                "HAST DU DAS NICHT VERANLASST, tu nichts. Ohne Klick ändert sich nichts,\n"
                . "und ohne Zugang zu diesem Postfach kommt niemand an deinen Zugang.")
        ],

        'loeschung_beantragt' => [
            'art' => 'konto', 'frist' => null, 'pflicht' => ['termin', 'link'],
            'betreff' => fn(array $d): string => 'Löschung deines Zugangs — ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                "du hast die Löschung deines Zugangs zur " . $n . " beantragt.\n\n"
                . "  Endgültig gelöscht wird am: " . $d['termin'] . "\n\n"
                . "Bis dahin ist der Zugang gesperrt, aber nichts ist fort. **Melde dich\n"
                . "einfach an, und die Löschung ist zurückgenommen** — ein eigener Knopf\n"
                . "dafür ist nicht nötig:\n\n"
                . "  " . $d['link'] . "\n\n"
                . "Nach dem Termin sind deine Einsätze, GPS-Daten und Stammdaten endgültig\n"
                . "fort. Es gibt danach keinen Weg zurück — auch nicht über die\n"
                . "Verwaltung, denn deine Daten sind mit deinem Passwort verschlüsselt.",
                "WOLLTEST DU DAS NICHT, melde dich an. Das genügt.")
        ],

        'geraet_gekoppelt' => [
            'art' => 'geraet', 'frist' => null,
            'pflicht' => ['geraet', 'geraet_id', 'zeitpunkt'],
            'betreff' => fn(array $d): string => 'Neues Gerät gekoppelt — ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                "mit deinem Konto der " . $n . " wurde soeben ein\n"
                . "neues Gerät gekoppelt. Das Gerät hat den Code gezeigt, du hast ihn im Web\n"
                . "eingegeben und am Gerät mit Ja bestätigt:\n\n"
                . "  Gerät:     " . $d['geraet'] . "\n"
                . "  Geräte-ID: " . $d['geraet_id'] . "\n"
                . "  Zeitpunkt: " . $d['zeitpunkt'] . " Uhr",
                "War das dein Gerät, ist alles in Ordnung — du musst nichts tun.\n\n"
                . "War es das nicht, deaktiviere oder lösche das Gerät bitte umgehend unter\n"
                . "Einstellungen, Bereich Geräte. Ab diesem Moment kann es keine Daten mehr\n"
                . "hochladen.\n"
                . app_url('/einstellungen.php?t=geraete')),
        ],
        'geraet_getrennt' => [
            'art' => 'geraet', 'frist' => null, 'pflicht' => ['geraet_id', 'zeitpunkt'],
            'betreff' => fn(array $d): string => 'Gerät getrennt — ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                "ein Gerät hat seine Verbindung zu deinem Konto der\n"
                . $n . " soeben selbst getrennt:\n\n"
                . "  Geräte-ID: " . $d['geraet_id'] . "\n"
                . "  Zeitpunkt: " . $d['zeitpunkt'] . " Uhr",
                "Das geschieht, wenn jemand das Gerät an sein Konto koppelt. Bereits\n"
                . "hochgeladene Einsätze bleiben vollständig erhalten.\n\n"
                . "War das nicht beabsichtigt, verbinde es einfach wieder: Starte die\n"
                . "Kopplung auf dem Gerät (Sync-Seite, Punkt „Gerät koppeln\") und gib den\n"
                . "Code, den es zeigt, hier ein:\n"
                . app_url('/einstellungen.php?t=geraete')),
        ],

        'speicher_kontingent' => [
            'art' => 'betrieb', 'frist' => null,
            'pflicht' => ['titel', 'prozent', 'belegt', 'kontingent', 'rat'],
            'betreff' => fn(array $d): string =>
                $d['titel'] . ': ' . $d['prozent'] . ' % des Kontingents erreicht',
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                'das Kontingent „' . $d['titel'] . '" hat ' . $d['prozent'] . " % erreicht.\n\n"
                . 'Belegt:     ' . $d['belegt'] . "\n"
                . 'Kontingent: ' . $d['kontingent'],
                $d['rat'] . "\n\n"
                . 'Die Schwellen stehen unter Betrieb, Seite Servereinstellungen.'),
        ],
        'backup_grenze' => [
            'art' => 'betrieb', 'frist' => null,
            'pflicht' => ['prozent', 'belegt', 'grenze', 'pakete', 'ordner'],
            'betreff' => fn(array $d): string =>
                'Backups: ' . $d['prozent'] . ' % der Speichergrenze erreicht',
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                'die Ablage der Backups hat ' . $d['prozent'] . " % der Speichergrenze erreicht.\n\n"
                . 'Belegt:  ' . $d['belegt'] . "\n"
                . 'Grenze:  ' . $d['grenze'] . "\n"
                . 'Pakete:  ' . $d['pakete'] . ' in ' . $d['ordner'] . ' Konten',
                'Ist die Grenze erreicht, wird nicht mehr gesichert — es wird nichts still '
                . 'verdrängt. Bitte alte Backups entfernen, die Aufbewahrung senken oder die '
                . 'Grenze erhöhen.'),
        ],
        'backup_faellig' => [
            'art' => 'betrieb', 'frist' => 86400, 'pflicht' => ['kern'],
            'betreff' => fn(array $d): string => 'Backups fällig — ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,', $d['kern']),
        ],

        'sicherheit_sammel' => [
            'art' => 'betrieb', 'frist' => 7200, 'pflicht' => ['kern'],
            /* FRIST ZWEI STUNDEN. Eine Sicherheitsmeldung, die drei Tage
             * spaeter ankommt, weil der Mailserver so lange stillstand, ist
             * keine Meldung mehr, sondern eine Verwirrung — die Lage, ueber
             * die sie berichtet, ist laengst vorbei. Zwei Stunden decken die
             * ersten drei Sprossen der Wiederholungsleiter ab. */
            'betreff' => fn(array $d): string => 'Auffällige Anmeldeversuche — ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,', $d['kern']),
        ],

        'testmail' => [
            'art' => 'betrieb', 'frist' => 3600, 'pflicht' => [],
            /* DIE TESTMAIL BEKOMMT DENSELBEN RAHMEN wie jede andere, und das
             * ist der Punkt: Bis Web 20.7.0 war sie die einzige ohne Anrede,
             * ohne Kontaktzeile und ohne Grussformel — und die einzige, deren
             * Betreff „Gen-EM" fehlte. Wer den Versand prueft, soll sehen,
             * wie eine ECHTE Mail dieser Anlage aussieht. */
            'betreff' => fn(array $d): string => 'Testmail — ' . $n,
            'text' => fn(array $d): string => mail_rahmen('Hallo,',
                "diese Nachricht wurde auf der Seite Betrieb, Bereich Status, ausgelöst.\n"
                . "Kommt sie an, funktioniert der Versand dieser Installation."),
        ],
    ];
}

/**
 * WER BETRIEBSPOST BEKOMMT — an einer Stelle (E-P5a-40, R83).
 *
 * DREI STELLEN BAUTEN DIESELBE LISTE: `speicher_lib.php` (Kontingente),
 * `adminbackup_lib.php` zweimal (Speichergrenze, faellige Sicherungen). Drei
 * Verbraucher sind zwei mehr als der eine, ab dem R83 das Zentralisieren
 * verlangt — und die dritte hatte bereits eine abweichende Sortierung
 * (`ORDER BY email` statt `ORDER BY id`) und eine zusaetzliche Bedingung.
 *
 * STEHT EINE BETREIBERADRESSE IN DEN EINSTELLUNGEN, GEHT DIE POST NUR DORTHIN.
 * Sonst an alle Konten mit Verwaltungsrecht, wie bisher. Die Richtung ist
 * bewusst die vorsichtige: Eine leere Einstellung darf keine Warnung
 * verschlucken, und deshalb ist „leer" der Rueckfall auf die alte Liste und
 * nicht auf „niemand".
 *
 * @param bool $nurAngemeldete Nur Konten mit gesetztem Passwort. Die
 *        Sicherungserinnerung will keine Post an ein Konto schicken, das noch
 *        nie angemeldet war — die Einladung dorthin ist noch offen.
 * @return string[] Adressen; leer heisst „es gibt niemanden".
 */
function mail_betriebsziele(bool $nurAngemeldete = false): array
{
    $b = betrieb_mail();
    if ($b !== '') { return [$b]; }

    $sql = 'SELECT email FROM users WHERE ' . ROLLEN_VERWALTUNG_SQL
         . ($nurAngemeldete ? ' AND password_hash IS NOT NULL' : '')
         . ' ORDER BY id';
    $ziele = [];
    foreach (db()->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $m) {
        if (is_string($m) && $m !== '') { $ziele[] = $m; }
    }
    return $ziele;
}

/**
 * Betreff-Praefix aus `config.php` — Staging traegt „[Staging]".
 *
 * AN EINER STELLE, und das ist der Punkt: Bis Web 20.7.0 haette man ihn in
 * zehn Betreffzeilen einsetzen muessen, und die zehnte haette ihn vergessen.
 */
function mail_praefix(): string
{
    static $p = null;
    if ($p === null) {
        $alles = require __DIR__ . '/config.php';
        $roh = trim((string)($alles['mail']['betreff_praefix'] ?? ''));
        $p = $roh === '' ? '' : $roh . ' ';
    }
    return $p;
}

/**
 * Eine Nachricht einreihen UND sofort versuchen.
 *
 * @param string $schluessel Eintrag aus `mail_katalog()`
 * @param string $empfaenger Eine Adresse
 * @param array  $daten      Die Pflichtwerte des Eintrags
 * @return string MAIL_ZUGESTELLT | MAIL_WARTET | MAIL_ABGELEHNT
 *
 * WIRFT NICHT BEI EINEM FEHLENDEN PFLICHTWERT, sondern schreibt ins
 * Fehlerprotokoll und gibt `MAIL_ABGELEHNT` zurueck. Eine Ausnahme mitten im
 * Anlegen eines Kontos riesse den ganzen Vorgang mit — und die Mail ist
 * nicht der Vorgang. Der Fehler ist trotzdem einer und steht als solcher da.
 */
function mail_einreihen(string $schluessel, string $empfaenger, array $daten = []): string
{
    $katalog = mail_katalog();
    if (!isset($katalog[$schluessel])) {
        error_log('mail: unbekannter Schluessel "' . $schluessel . '"');
        return MAIL_ABGELEHNT;
    }
    $e = $katalog[$schluessel];

    /* DIE ADRESSE WIRD HIER GEPRUEFT, nicht erst beim Senden: Eine Zeile mit
     * unbrauchbarer Adresse wuerde fuenfmal versucht und fuenfmal scheitern. */
    if ($empfaenger === '' || strcspn($empfaenger, "\r\n") !== strlen($empfaenger)
        || !filter_var($empfaenger, FILTER_VALIDATE_EMAIL)) {
        error_log('mail: unzulaessige Empfaengeradresse abgewiesen (' . $schluessel . ')');
        return MAIL_ABGELEHNT;
    }

    foreach ($e['pflicht'] as $k) {
        if (!array_key_exists($k, $daten) || (string)$daten[$k] === '') {
            error_log('mail: "' . $schluessel . '" ohne Pflichtwert "' . $k . '" — nicht eingereiht');
            return MAIL_ABGELEHNT;
        }
    }

    if (!smtp_eingerichtet()) {
        /* OHNE SMTP GAR NICHT ERST EINREIHEN. Sonst fuellte sich die
         * Warteschlange auf einer Installation, die nie Post verschicken
         * wird, mit Zeilen, die fuenfmal scheitern und dann als
         * „unzustellbar" stehen bleiben — lauter rote Zeilen fuer einen
         * Zustand, der gewollt ist. Die Statusseite sagt bereits „SMTP nicht
         * eingerichtet"; das ist die richtige Auskunft. */
        return MAIL_ABGELEHNT;
    }

    $betreff = mail_praefix() . ($e['betreff'])($daten);
    $text    = ($e['text'])($daten);
    $frist   = $e['frist'] === null ? null : gmdate('Y-m-d H:i:s', time() + (int)$e['frist']);

    try {
        $pdo = db();

        /* UEBERHOLTE ZEILEN SCHLIESSEN. Fordert jemand zweimal einen
         * Reset-Link an, entwertet der zweite den ersten ohnehin
         * (`password_resets`). Die erste Zeile wuerde sonst Minuten spaeter
         * einen toten Link ausliefern — samt dem Satz „es gilt immer nur der
         * zuletzt verschickte" im Rumpf. */
        $pdo->prepare('UPDATE mail_warteschlange
                          SET zustand = \'ueberholt\', beendet = UTC_TIMESTAMP(),
                              empfaenger = NULL, betreff = NULL, text = NULL
                        WHERE zustand = \'offen\' AND schluessel = ? AND empfaenger = ?')
            ->execute([$schluessel, $empfaenger]);

        $pdo->prepare('INSERT INTO mail_warteschlange
                         (schluessel, art, empfaenger, betreff, text, zustand,
                          versuche, erstellt, naechster_versuch, gueltig_bis)
                       VALUES (?, ?, ?, ?, ?, \'offen\', 0, UTC_TIMESTAMP(), UTC_TIMESTAMP(), ?)')
            ->execute([$schluessel, $e['art'], $empfaenger, $betreff, $text, $frist]);
        $id = (int)$pdo->lastInsertId();
    } catch (Throwable $ex) {
        /* Die Tabelle fehlt (Migration steht aus) oder die Datenbank ist weg.
         * DANN WIRD TROTZDEM VERSUCHT — ohne Warteschlange, wie vor Web
         * 20.8.0. Eine Einladung, die hinausgeht, ist besser als eine, die
         * an der fehlenden Warteschlange scheitert. */
        error_log('mail: Warteschlange nicht verfuegbar (' . $ex->getMessage()
                . ') — es wird ohne sie versucht');
        return smtp_send($empfaenger, $betreff, $text, MAIL_BUDGET_S)
            ? MAIL_ZUGESTELLT : MAIL_ABGELEHNT;
    }

    return mail_zeile_versuchen($id) ? MAIL_ZUGESTELLT : MAIL_WARTET;
}

/**
 * Eine Zeile versuchen und ihren Zustand fortschreiben.
 *
 * DER ZUSTAND WIRD SOFORT GESCHRIEBEN, nicht im Jobzustand gesammelt:
 * `jobs_einen_lauf()` schreibt bei einer Ausnahme den ALTEN Zustand zurueck
 * (`jobs_lib.php`). Eine Mail, die schon hinausging, bevor die naechste eine
 * Ausnahme warf, ginge sonst beim naechsten Lauf ERNEUT hinaus.
 */
function mail_zeile_versuchen(int $id, ?float $budget = null): bool
{
    $pdo = db();
    $budget = $budget === null ? (float)MAIL_BUDGET_S
                               : max(MAIL_MINDEST_S, min((float)MAIL_BUDGET_S, $budget));
    $st = $pdo->prepare('SELECT * FROM mail_warteschlange WHERE id = ? AND zustand = \'offen\'');
    $st->execute([$id]);
    $z = $st->fetch(PDO::FETCH_ASSOC);
    if (!$z) { return false; }

    $versuche = (int)$z['versuche'] + 1;
    $ok = smtp_send((string)$z['empfaenger'], (string)$z['betreff'],
                    (string)$z['text'], (int)ceil($budget));

    if ($ok) {
        /* ZUGESTELLT: Adresse, Betreff und Rumpf fallen. Was bleibt, ist die
         * Auskunft „eine Nachricht dieser Art ging zu dieser Zeit hinaus". */
        $pdo->prepare('UPDATE mail_warteschlange
                          SET zustand = \'zugestellt\', versuche = ?, beendet = UTC_TIMESTAMP(),
                              naechster_versuch = NULL, fehler = NULL,
                              empfaenger = NULL, betreff = NULL, text = NULL
                        WHERE id = ?')->execute([$versuche, $id]);
        return true;
    }

    /* DER GRUND SAMT KENNUNG — siehe `smtp_letzter_fehler()`. Das
     * Fehlerprotokoll nennt seit E-P5a-37 keinen Empfaenger mehr; die
     * Kennung ist der Faden, an dem sich beides wieder zusammenfuehren
     * laesst. Ohne sie waere die Unzustellbar-Liste eine Aussage ohne
     * Ursache und das Protokoll eine Ursache ohne Adressat. */
    $f = smtp_letzter_fehler();
    $grund = $f === null
        ? 'Der Mailserver hat die Nachricht nicht angenommen.'
        : $f['grund'] . ' (Kennung ' . $f['kennung'] . ')';

    /* KEINE STUFE MEHR? Dann endgueltig unzustellbar — und DIE ADRESSE
     * BLEIBT STEHEN (E-P5a-39): „Die Einladung an X kam nie an" ist ohne X
     * wertlos. Der Rumpf faellt trotzdem, wegen des Tokens. */
    if ($versuche >= count(MAIL_LEITER)) {
        $pdo->prepare('UPDATE mail_warteschlange
                          SET zustand = \'unzustellbar\', versuche = ?, beendet = UTC_TIMESTAMP(),
                              naechster_versuch = NULL, fehler = ?, text = NULL
                        WHERE id = ?')->execute([$versuche, $grund, $id]);
        return false;
    }

    $wartet  = MAIL_LEITER[$versuche - 1];
    $naechst = time() + $wartet;

    /* ZU SPAET? Ein Reset-Token lebt eine Stunde, die Leiter geht bis 24 h.
     * Ein Versuch, der erst nach Ablauf faellig waere, wird nicht mehr
     * unternommen — besser gar nichts als ein toter Link. */
    if ($z['gueltig_bis'] !== null && $naechst > strtotime((string)$z['gueltig_bis'] . ' UTC')) {
        $pdo->prepare('UPDATE mail_warteschlange
                          SET zustand = \'zu_spaet\', versuche = ?, beendet = UTC_TIMESTAMP(),
                              naechster_versuch = NULL, fehler = ?,
                              empfaenger = NULL, betreff = NULL, text = NULL
                        WHERE id = ?')
            ->execute([$versuche,
                       'Der nächste Versuch läge nach dem Ablauf dieser Nachricht.', $id]);
        return false;
    }

    $pdo->prepare('UPDATE mail_warteschlange
                      SET versuche = ?, naechster_versuch = ?, fehler = ?
                    WHERE id = ?')
        ->execute([$versuche, gmdate('Y-m-d H:i:s', $naechst), $grund, $id]);
    return false;
}

/**
 * Faellige Zeilen abarbeiten — der Job `mail`.
 *
 * NIMMT `$zeitLinks` ENTGEGEN, und zwar zwingend: Am Huckepack-Weg stehen
 * 3 s fuer ALLE Jobs zusammen zur Verfuegung (`jobs_lib.php`). Ein Versuch
 * kostet bis zu MAIL_BUDGET_S. Ohne diese Bremse riesse ein einziger
 * haengender Mailserver das Budget aller Jobs.
 *
 * @return array{zustand: array, erledigt: int, fertig: bool}
 */
function mail_job(PDO $pdo, array $zustand, callable $zeitLinks): array
{
    $erledigt = 0;
    try {
        $st = $pdo->prepare('SELECT id FROM mail_warteschlange
                              WHERE zustand = \'offen\' AND naechster_versuch <= UTC_TIMESTAMP()
                              ORDER BY naechster_versuch LIMIT ' . MAIL_JE_LAUF);
        $st->execute();
        $ids = $st->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable $ex) {
        /* Tabelle fehlt — Migration steht aus. Kein Grund fuer einen Fehler. */
        return ['zustand' => $zustand, 'erledigt' => 0, 'fertig' => true];
    }

    foreach ($ids as $id) {
        /* Vor JEDEM Versuch, nicht nur am Anfang: Der vorige kann die Zeit
         * schon aufgebraucht haben.
         *
         * DER VERSUCH BEKOMMT DIE RESTZEIT, nicht die vollen 5 s — sonst
         * liefe der Job am Huckepack-Weg (3,0 s fuer alle Jobs) nie an. Ein
         * halber Sicherheitsabstand bleibt fuer das Schreiben des Zustands. */
        $rest = $zeitLinks() - 0.5;
        if ($rest < MAIL_MINDEST_S) { break; }
        mail_zeile_versuchen((int)$id, $rest);
        $erledigt++;
    }
    return ['zustand' => $zustand, 'erledigt' => $erledigt,
            'fertig'  => $erledigt >= count($ids)];
}

/**
 * DIE LAGE DER WARTESCHLANGE — fuer die eine Zeile auf der Statusseite.
 *
 * KEINE EIGENE SEITE UND KEIN KNOPF, und das ist eine Entscheidung
 * (E-P5a-41): Eine Liste ist eine neue Darstellung und braucht nach
 * `CLAUDE.md` 5 eine Freigabe mit Mockup. Was eine BetreiberIn hier braucht,
 * ist zudem keine Liste, sondern eine Antwort auf eine Frage — „ist etwas
 * liegengeblieben?" —, und die passt in eine Zeile. Der volle Bereich mit
 * Reitern kommt in P5c (Protokoll); bis dahin ist diese Zeile die Auskunft.
 *
 * DIE ADRESSEN STEHEN IN DER ZEILE, hoechstens drei. „2 unzustellbar" ohne
 * Adresse ist eine Aussage, mit der niemand etwas anfangen kann — und der
 * Grund, aus dem E-P5a-39 die Adresse beim Endzustand `unzustellbar`
 * ueberhaupt stehenlaesst.
 *
 * @return array{offen:int, unzustellbar:int, zuspaet:int, adressen:string[],
 *               grund:?string}|null  `null` = die Tabelle gibt es nicht
 */
function mail_lage(): ?array
{
    try {
        $pdo = db();
        $z = $pdo->query("SELECT
                 SUM(zustand = 'offen')        AS offen,
                 SUM(zustand = 'unzustellbar') AS unzustellbar,
                 SUM(zustand = 'zu_spaet')     AS zuspaet
               FROM mail_warteschlange")->fetch(PDO::FETCH_ASSOC);
        $st = $pdo->query("SELECT empfaenger, fehler FROM mail_warteschlange
                            WHERE zustand = 'unzustellbar' AND empfaenger IS NOT NULL
                         ORDER BY beendet DESC LIMIT 3");
        $adressen = []; $grund = null;
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $adressen[] = (string)$r['empfaenger'];
            if ($grund === null) { $grund = (string)$r['fehler']; }
        }
        return ['offen'        => (int)($z['offen'] ?? 0),
                'unzustellbar' => (int)($z['unzustellbar'] ?? 0),
                'zuspaet'      => (int)($z['zuspaet'] ?? 0),
                'adressen'     => $adressen,
                'grund'        => $grund];
    } catch (Throwable $ex) {
        return null;
    }
}

/** Wie viele Zeilen warten noch? Fuer die Rueckstandsanzeige. */
function mail_rueckstand(PDO $pdo, array $zustand): ?int
{
    try {
        return (int)$pdo->query('SELECT COUNT(*) FROM mail_warteschlange
                                  WHERE zustand = \'offen\'')->fetchColumn();
    } catch (Throwable $ex) {
        return null;
    }
}

/**
 * Alle Zeilen einer Adresse entfernen — beim Loeschen eines Kontos.
 *
 * DIE TABELLE HAENGT AN KEINEM FREMDSCHLUESSEL, und zwar aus demselben Grund
 * wie die Spuren und die Sperrvermerke: Nicht jede Nachricht gehoert zu einem
 * Konto (`speicher_kontingent` und `backup_grenze` gehen an alle
 * Verwaltungsadressen, `adresswechsel` an eine Adresse, die danach keinem
 * Konto mehr gehoert). Deshalb steht sie in derselben Handlaufliste wie
 * jene — wer ein Konto loescht, ruft das hier mit auf. Sonst ueberlebte die
 * Adresse ihr Konto, in der Datenbank und in jeder Komplettsicherung.
 */
function mail_konto_entfernen(PDO $pdo, string $empfaenger): int
{
    if ($empfaenger === '') { return 0; }
    try {
        $st = $pdo->prepare('DELETE FROM mail_warteschlange WHERE empfaenger = ?');
        $st->execute([$empfaenger]);
        return $st->rowCount();
    } catch (Throwable $ex) {
        return 0;
    }
}
