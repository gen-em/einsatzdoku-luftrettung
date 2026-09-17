<?php
declare(strict_types=1);

/**
 * WAS NACH DER ANMELDUNG FAELLIG IST (P5b/AP9, E-P5b-09, -10, -19).
 *
 * Nach dem Anmelden koennen vier Dinge anstehen. Diese Bibliothek sagt,
 * welches — und zwar genau EINES, in dieser Reihenfolge:
 *
 *   1. Einwilligungstor        `einwilligung.php`, eine eigene SEITE
 *   2. Schluesselblatt-Rueckfrage   Dialog, nur BetreiberIn, alle 3 Monate
 *   3. Konto-Rueckfrage        Dialog, nach 30 Tagen / 6 Monaten / jaehrlich
 *   4. Erststart               KARTE ueber der Tagesuebersicht
 *
 * ---------------------------------------------------------------------------
 * DER VIERTE IST KEIN DIALOG, UND DAS IST ABSICHT
 * ---------------------------------------------------------------------------
 *
 * E-P5b-19 schreibt „die Seite zeigt genau einen Dialog" und zaehlt alle vier
 * in einer Reihe auf. Das Mockup M-P5b-02b sagt es genauer, und es ist
 * freigegeben: **„Die Karte steht ueber der Tagesuebersicht, nicht als
 * Dialog: Wer sie ignoriert, arbeitet trotzdem."**
 *
 * Der Unterschied ist keine Geschmacksfrage. Die Rueckfragen sind
 * SICHERHEITSFRAGEN mit einer Frist — sie duerfen stoeren. Der Erststart ist
 * eine EINLADUNG; wer ihn wegklicken muss, um an seine Diensttage zu kommen,
 * lernt in der ersten Minute, dass diese Anwendung im Weg steht.
 *
 * Die Reihenfolge gilt trotzdem fuer alle vier: Solange ein Dialog faellig
 * ist, erscheint die Karte nicht. Zwei Aufforderungen auf einmal sind eine zu
 * viel.
 *
 * ---------------------------------------------------------------------------
 * DAS EINWILLIGUNGSTOR STEHT HIER NUR IN DER LISTE, NICHT IM CODE
 * ---------------------------------------------------------------------------
 *
 * Es ist eine Umleitung in `auth_guard.php` (AP4) und laeuft, bevor eine
 * Seite ueberhaupt etwas ausgibt. Wer hier ankommt, hat es schon hinter sich.
 * Genannt wird es dennoch, weil die Reihenfolge sonst unvollstaendig waere
 * und der naechste Leser sie fuer dreistellig hielte.
 */

require_once __DIR__ . '/db.php';

/**
 * Den Zwischenspeicher dieses Kontos verwerfen.
 *
 * Jede schreibende Funktion dieser Datei ruft das. Siehe
 * `einstieg_zustand()` — dort steht, was ohne diese Zeile passiert.
 */
function einstieg_vergessen(int $userId): void
{
    $GLOBALS['__einstieg_vergessen'][$userId] = true;
}

/** Die drei Schritte des Erststarts. Bit 0, 1, 2. */
const ERSTSTART_STANDORT      = 1;
const ERSTSTART_RETTUNGSMITTEL = 2;
const ERSTSTART_GERAET        = 4;
const ERSTSTART_ALLE          = 7;

/** `erststart_stand` = -1: nicht mehr zeigen. */
const ERSTSTART_NIE = -1;

/**
 * Abstaende der Konto-Rueckfrage, nach Runde (E-P5b-09).
 *
 * 30 Tage, dann 6 Monate, dann jaehrlich. Als `DateInterval`-Angaben und
 * nicht als Tageszahlen: „6 Monate" sind je nach Jahreszeit 181 bis 184 Tage,
 * und ein Konto, das im Januar angelegt wurde, soll im Juli gefragt werden —
 * nicht am 28. Juni.
 */
const RUECKFRAGE_ABSTAENDE = ['P30D', 'P6M', 'P1Y'];

/** „Spaeter" schiebt um 7 Tage, hoechstens dreimal (E-P5b-19). */
const RUECKFRAGE_SPAETER      = 'P7D';
const RUECKFRAGE_SPAETER_MAX  = 3;

/** Die Betreiber-Rueckfrage kommt alle 3 Monate (E-P5b-10). */
const BLATT_ABSTAND = 'P3M';

/** Schluessel in `app_state` fuer die letzte Bestaetigung des Schluesselblatts. */
const BLATT_BESTAETIGT_K = 'schluesselblatt_bestaetigt_am';

/**
 * Was ist fuer dieses Konto faellig? `null`, wenn nichts.
 *
 * @return 'schluesselblatt'|'konto'|'erststart'|null
 */
function einstieg_faellig(int $userId, bool $istBetreiberin): ?string
{
    $z = einstieg_zustand($userId);
    if ($z === null) { return null; }

    if ($istBetreiberin && blatt_faellig()) { return 'schluesselblatt'; }
    if (rueckfrage_faellig($z))             { return 'konto'; }
    if (erststart_offen($z['erststart_stand']) !== []) { return 'erststart'; }

    return null;
}

/**
 * Die Zustandsspalten dieses Kontos. `null`, wenn es sie nicht gibt.
 *
 * DIE TABELLE KANN DIE SPALTEN NOCH NICHT HABEN — zwischen dem Hochladen und
 * dem Aufruf von `update.php` liegt ein Fenster, und in dem laeuft die
 * Anwendung. Sie faellt dann auf „nichts faellig" zurueck, wie das
 * Einwilligungstor auch: Eine Anwendung, die nach einem Deploy alle mit einer
 * Fehlermeldung begruesst, WEIL die Migration noch aussteht, ist das
 * Gegenteil von dem, was diese Einstiege sollen.
 *
 * @return array{erststart_stand:int, rueckfrage_naechste:?string, rueckfrage_runde:int, rueckfrage_verschoben:int}|null
 */
function einstieg_zustand(int $userId): ?array
{
    static $merker = [];

    /* DER ZWISCHENSPEICHER MUSS NACH JEDEM SCHREIBEN WEG, und das hat mich
     * hier eine Probe gekostet: `einstieg_faellig()` fuellt ihn am Anfang der
     * Anfrage, `rueckfrage_beantwortet()` schreibt danach und liest dabei die
     * Runde — aus dem Speicher, also die alte. Die Runde blieb bei 1 stehen:
     * 30 Tage, dann 6 Monate, dann WIEDER 6 Monate, fuer immer. Gemessen am
     * 17.09.2026 in vier Durchgaengen.
     *
     * Aufgefallen waere es im Betrieb erst nach einem halben Jahr, und dann
     * als „die Anwendung fragt zu oft" — ein Satz, aus dem niemand auf einen
     * statischen Speicher schliesst. */
    if (isset($GLOBALS['__einstieg_vergessen'][$userId])) {
        unset($merker[$userId], $GLOBALS['__einstieg_vergessen'][$userId]);
    }
    if (array_key_exists($userId, $merker)) { return $merker[$userId]; }

    try {
        $st = db()->prepare('SELECT erststart_stand, rueckfrage_naechste,
                                    rueckfrage_runde, rueckfrage_verschoben
                               FROM users WHERE id = ?');
        $st->execute([$userId]);
        $z = $st->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $ex) {
        return $merker[$userId] = null;
    }
    if (!$z) { return $merker[$userId] = null; }

    return $merker[$userId] = [
        'erststart_stand'       => (int)$z['erststart_stand'],
        'rueckfrage_naechste'   => $z['rueckfrage_naechste'] !== null
                                     ? (string)$z['rueckfrage_naechste'] : null,
        'rueckfrage_runde'      => (int)$z['rueckfrage_runde'],
        'rueckfrage_verschoben' => (int)$z['rueckfrage_verschoben'],
    ];
}

/**
 * Welche Erststart-Schritte stehen noch offen?
 *
 * @return list<'standort'|'rettungsmittel'|'geraet'>
 */
function erststart_offen(int $stand): array
{
    if ($stand === ERSTSTART_NIE || ($stand & ERSTSTART_ALLE) === ERSTSTART_ALLE) {
        return [];
    }

    $offen = [];
    if (!($stand & ERSTSTART_STANDORT))       { $offen[] = 'standort'; }
    if (!($stand & ERSTSTART_RETTUNGSMITTEL)) { $offen[] = 'rettungsmittel'; }
    if (!($stand & ERSTSTART_GERAET))         { $offen[] = 'geraet'; }
    return $offen;
}

/**
 * Einen Erststart-Schritt als erledigt vermerken.
 *
 * ODER-VERKNUEPFT UND NIE ZURUECKGESETZT. Wer sein einziges Rettungsmittel
 * wieder loescht, bekommt den Einstieg nicht erneut: Er hat ihn gesehen und
 * verstanden, und eine Anwendung, die beim Aufraeumen wieder bei null
 * anfaengt, wirkt kaputt.
 *
 * AUF `-1` WIRD NICHT GESCHRIEBEN. Wer „nicht mehr zeigen" gewaehlt hat, hat
 * eine Entscheidung getroffen; ein spaeter angelegter Standort darf sie nicht
 * stillschweigend umkehren.
 */
function erststart_erledigt(int $userId, int $bit): void
{
    try {
        db()->prepare('UPDATE users
                          SET erststart_stand = erststart_stand | ?
                        WHERE id = ? AND erststart_stand >= 0')
            ->execute([$bit, $userId]);
        einstieg_vergessen($userId);
    } catch (Throwable $ex) {
        /* Die Spalte fehlt (Migration steht aus). Kein Grund, den Aufrufer
         * scheitern zu lassen — er hat gerade etwas ANDERES getan, und das
         * ist gelungen. */
        error_log('erststart_erledigt: ' . $ex->getMessage());
    }
}

/** „Nicht mehr zeigen." */
function erststart_nie_mehr(int $userId): void
{
    db()->prepare('UPDATE users SET erststart_stand = ? WHERE id = ?')
        ->execute([ERSTSTART_NIE, $userId]);
    einstieg_vergessen($userId);
}

/**
 * Ist die Konto-Rueckfrage faellig?
 *
 * NULL HEISST NICHT „SOFORT". Ein Konto, das noch nie gefragt wurde, traegt
 * `rueckfrage_naechste = NULL` — und bekommt hier `false`, nicht `true`. Das
 * Datum setzt `rueckfrage_anstossen()` beim ersten Anmelden; wer stattdessen
 * NULL als faellig lesen wuerde, fragte die Aerztin nach dem
 * Wiederherstellungsschluessel in derselben Minute, in der sie ihn bekommen
 * hat.
 */
function rueckfrage_faellig(array $zustand): bool
{
    $naechste = $zustand['rueckfrage_naechste'] ?? null;
    if ($naechste === null || $naechste === '') { return false; }

    return $naechste <= gmdate('Y-m-d');
}

/**
 * Die Uhr in Gang setzen: erste Frage in 30 Tagen.
 *
 * Aufgerufen beim Anmelden, wenn noch kein Datum steht. NICHT beim Anlegen
 * des Kontos: Ein Konto, das nie benutzt wird, soll keine Frist mit sich
 * herumtragen — und die 30 Tage sollen ab dem Tag laufen, an dem jemand den
 * Schluessel tatsaechlich in der Hand hatte.
 */
function rueckfrage_anstossen(int $userId): void
{
    try {
        db()->prepare("UPDATE users
                          SET rueckfrage_naechste = DATE_ADD(UTC_DATE(), INTERVAL 30 DAY)
                        WHERE id = ? AND rueckfrage_naechste IS NULL")
            ->execute([$userId]);
        einstieg_vergessen($userId);
    } catch (Throwable $ex) {
        error_log('rueckfrage_anstossen: ' . $ex->getMessage());
    }
}

/**
 * Beantwortet: naechste Runde, Zaehler zurueck.
 *
 * Gilt fuer BEIDE Antworten — „ja, liegt sicher" und „nein, neuen Schluessel
 * erzeugen". Wer einen neuen Schluessel erzeugt hat, hat ihn gerade notiert;
 * ihn deswegen frueher zu fragen waere Strafe fuer die richtige Antwort.
 */
function rueckfrage_beantwortet(int $userId): void
{
    $z = einstieg_zustand($userId);
    $runde = min(($z['rueckfrage_runde'] ?? 0) + 1, count(RUECKFRAGE_ABSTAENDE) - 1);
    $ab    = RUECKFRAGE_ABSTAENDE[$runde];

    $naechste = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                  ->add(new DateInterval($ab))->format('Y-m-d');

    db()->prepare('UPDATE users
                      SET rueckfrage_naechste = ?, rueckfrage_runde = ?,
                          rueckfrage_verschoben = 0
                    WHERE id = ?')
        ->execute([$naechste, $runde, $userId]);
    einstieg_vergessen($userId);
}

/**
 * „Spaeter" — 7 Tage, hoechstens dreimal.
 *
 * DANACH KOMMT DIE FRAGE WIEDER, und sie geht nicht weg. Das ist der Sinn des
 * Zaehlers: „Spaeter" soll den Tag retten, an dem man keine Zeit hat, nicht
 * die Frage abschaffen. Beim vierten Mal wird nicht mehr geschoben; der
 * Dialog erscheint bei der naechsten Anmeldung erneut.
 *
 * @return bool `false`, wenn nicht mehr geschoben wurde
 */
function rueckfrage_spaeter(int $userId): bool
{
    $z = einstieg_zustand($userId);
    if (($z['rueckfrage_verschoben'] ?? 0) >= RUECKFRAGE_SPAETER_MAX) { return false; }

    db()->prepare('UPDATE users
                      SET rueckfrage_naechste = DATE_ADD(UTC_DATE(), INTERVAL 7 DAY),
                          rueckfrage_verschoben = rueckfrage_verschoben + 1
                    WHERE id = ?')
        ->execute([$userId]);
    einstieg_vergessen($userId);
    return true;
}

/**
 * Ist die Betreiber-Rueckfrage faellig? (E-P5b-10)
 *
 * INSTALLATIONSWEIT UND NICHT JE KONTO: Es gibt EIN Schluesselblatt, und wer
 * es bestaetigt, bestaetigt es fuer die Anlage. Zwei BetreiberInnen sollen
 * nicht beide gefragt werden, nachdem eine von ihnen nachgesehen hat.
 *
 * KEIN DATUM HEISST FAELLIG — hier anders als bei der Konto-Rueckfrage, und
 * das ist der Unterschied zwischen den beiden Fragen: Der
 * Wiederherstellungsschluessel wird im Lauf der Einrichtung ausgegeben, das
 * Schluesselblatt liegt beim Betreiber schon. Wurde es noch nie bestaetigt,
 * ist genau das die Frage.
 */
function blatt_faellig(): bool
{
    $wann = app_state_lesen(BLATT_BESTAETIGT_K);
    if ($wann === null || $wann === '') { return true; }

    $grenze = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                ->sub(new DateInterval(BLATT_ABSTAND))->format('Y-m-d');
    return substr($wann, 0, 10) <= $grenze;
}

/** Das Schluesselblatt wurde bestaetigt. */
function blatt_bestaetigt(): void
{
    app_state_setzen(BLATT_BESTAETIGT_K, gmdate('Y-m-d H:i:s'));
}
