<?php
declare(strict_types=1);

/**
 * ADRESSSUCHE — die Einstellungen dazu (S9/AP2, E-S9-05, R79).
 *
 * WAS HIER STEHT UND WARUM AN EINER STELLE. Die Adresssuche haengt an drei
 * Werten, und alle drei werden an verschiedenen Enden der Anwendung gebraucht:
 * vom Ortsfeld (darf ich fragen?), vom Kartendialog (zeige ich ein Suchfeld?),
 * von der Servereinstellung (was steht im Formular?), vom Profil (ist der
 * Kontoschalter ueberhaupt bedienbar?) und vom Datenschutztext (wen nenne
 * ich?). Ohne eine gemeinsame Stelle stuende die Vorgabeadresse an fuenf
 * Stellen — genau der Zustand, den AP2 abschafft.
 *
 * DIE INSTALLATION IST DIE OBERGRENZE. `geocoder_an()` ist die einzige Frage,
 * die ein Aufrufer stellen sollte: Sie verundet beide Schalter. Wer sie
 * einzeln braucht — und das ist genau EINE Stelle, die Karte „Datenschutz" im
 * Profil, die sagen muss, WER abgeschaltet hat —, fragt die beiden Funktionen
 * darunter.
 *
 * ABLAGE:
 *   app_state `adresssuche`   '0' = aus. Fehlt der Schluessel, ist sie AN —
 *                             das ist die Vorgabe aus F-SP-4, und sie gilt
 *                             auch fuer jede Installation, die vor S9 lief.
 *   app_state `geocoder_url`  die Dienstadresse. Fehlt sie, gilt VORGABE.
 *   users.adresssuche         TINYINT(1) NOT NULL DEFAULT 1, Migration
 *                             `2026_09_07_adresssuche_konto`.
 *
 * BEIDE LESER VERTRAGEN EINE FEHLENDE SPALTE. Zwischen dem Deploy und dem
 * Lauf von `update.php` gibt es die Kontospalte noch nicht; bis dahin gilt die
 * Vorgabe. Ohne diese Nachsicht antwortete jede Seite mit einem Ortsfeld in
 * genau diesem Fenster mit einem Fehler — dieselbe Nachsicht, die
 * `edbak_marke_lesen()` fuer `app_state` selbst uebt.
 */

/* Die Vorgabeadresse steht GENAU EINMAL, und zwar hier. Der Browser bekommt
 * sie ueber ui_geocoder_bootstrap(); assets/geocoder.js traegt sie noch
 * einmal als Rueckfall fuer den Fall einer Seite ohne Bootstrap — das ist ein
 * Einbindungsfehler und keine zweite Quelle. */
const GEOCODER_VORGABE = 'https://photon.komoot.io';

const GEOCODER_K_AN     = 'adresssuche';
const GEOCODER_K_DIENST = 'geocoder_url';

/**
 * Ein Schluessel aus `app_state`, mit Zwischenspeicher je Anfrage.
 *
 * `$frisch` wirft den Speicher fuer diesen Schluessel weg und liest neu. Das
 * braucht genau eine Stelle — `geocoder_state_setzen()` unmittelbar nach dem
 * Schreiben —, und ohne sie zeigte die Seite, die gerade gespeichert hat, den
 * ALTEN Stand: `geocoder_installation_setzen()` liest vor dem Schreiben (um zu
 * wissen, ob sich etwas aendert) und fuellt damit den Speicher; die Ausgabe
 * derselben Anfrage las ihn dann wieder aus. Gemessen am 07.09.2026 mit der
 * Klickprobe: Meldung „Adresssuche ausgeschaltet gespeichert.", Schalter
 * weiter an, Plakette weiter „an" — erst ein Neuladen zeigte die Wahrheit.
 */
function geocoder_state(string $k, bool $frisch = false): ?string
{
    static $c = [];
    if ($frisch) { unset($c[$k]); }
    if (array_key_exists($k, $c)) { return $c[$k]; }
    try {
        $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute([$k]);
        $v = $st->fetchColumn();
        return $c[$k] = ($v === false ? null : (string)$v);
    } catch (Throwable) {
        return $c[$k] = null;      // app_state fehlt (Migration nicht gelaufen)
    }
}

/** Schalter der Installation. Vorgabe: an (F-SP-4). */
function geocoder_installation_an(): bool
{
    return geocoder_state(GEOCODER_K_AN) !== '0';
}

/** Die Dienstadresse der Installation. */
function geocoder_dienst(): string
{
    $v = trim((string)(geocoder_state(GEOCODER_K_DIENST) ?? ''));
    return $v !== '' ? rtrim($v, '/') : GEOCODER_VORGABE;
}

/** Der Rechnername des Dienstes — fuer Hinweis und Datenschutztext. */
function geocoder_host(): string
{
    $h = parse_url(geocoder_dienst(), PHP_URL_HOST);
    return is_string($h) && $h !== '' ? $h : geocoder_dienst();
}

/**
 * Schalter des Kontos. Vorgabe: an.
 *
 * OHNE KONTO IST SIE AN: Die Anmeldeseite und der Einrichter haben kein
 * Ortsfeld, aber ein kuenftiger Aufrufer koennte eines haben, und „aus, weil
 * niemand angemeldet ist" waere die falsche Auskunft.
 *
 * `$frisch` wie oben — nach dem Schreiben muss dieselbe Anfrage den neuen
 * Wert sehen koennen.
 */
function geocoder_konto_an(?int $userId, bool $frisch = false): bool
{
    if ($userId === null) { return true; }
    static $c = [];
    if ($frisch) { unset($c[$userId]); }
    if (array_key_exists($userId, $c)) { return $c[$userId]; }
    try {
        $st = db()->prepare('SELECT adresssuche FROM users WHERE id = ?');
        $st->execute([$userId]);
        $v = $st->fetchColumn();
        return $c[$userId] = ($v === false || $v === null ? true : (int)$v === 1);
    } catch (Throwable) {
        return $c[$userId] = true;  // Spalte fehlt (Migration nicht gelaufen)
    }
}

/**
 * Gilt die Adresssuche fuer dieses Konto? Installation UND Konto.
 *
 * Ohne Argument nimmt sie das angemeldete Konto aus der Sitzung — die
 * Aufrufer in `ui.php` haben keinen Kontext, und ein zweiter Weg zur selben
 * Zahl waere ein zweiter Weg, sie falsch zu bekommen.
 */
function geocoder_an(?int $userId = null): bool
{
    if ($userId === null) {
        $userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }
    return geocoder_installation_an() && geocoder_konto_an($userId);
}

/**
 * Die Einstellungen der Installation setzen (Betrieb -> Servereinstellungen).
 *
 * Liefert die Liste dessen, was sich TATSAECHLICH geaendert hat — die Seite
 * meldet daraus ihren Satz, und „Es gab nichts zu aendern" ist eine ehrliche
 * Antwort und keine Verlegenheit.
 */
function geocoder_installation_setzen(bool $an, string $dienst): array
{
    $teile = [];
    if ($an !== geocoder_installation_an()) {
        geocoder_state_setzen(GEOCODER_K_AN, $an ? '1' : '0');
        $teile[] = $an ? 'Adresssuche eingeschaltet' : 'Adresssuche ausgeschaltet';
    }
    $dienst = rtrim(trim($dienst), '/');
    if ($dienst === '') { $dienst = GEOCODER_VORGABE; }
    if ($dienst !== geocoder_dienst()) {
        geocoder_state_setzen(GEOCODER_K_DIENST, $dienst);
        $teile[] = 'Dienst ' . $dienst;
    }
    return $teile;
}

function geocoder_state_setzen(string $k, string $v): void
{
    db()->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE v = VALUES(v)')->execute([$k, $v]);
    /* UND DEN ZWISCHENSPEICHER NACHZIEHEN. Er ist ab hier veraltet, und die
     * Seite, die gerade gespeichert hat, gibt sich in derselben Anfrage aus —
     * ohne diese Zeile mit dem Stand von vorher. Ein Neuladen holt das nicht
     * nach: Falsch war die Anzeige NACH dem Speichern. */
    geocoder_state($k, true);
}

/**
 * Den Kontoschalter setzen. Liefert `false`, wenn die Spalte fehlt.
 *
 * WARUM HIER UND NICHT IN DER SEITE: Zum Schreiben gehoert das Nachziehen des
 * Zwischenspeichers, und der lebt in diesem Modul. Eine Seite, die selbst
 * `UPDATE users SET adresssuche` schriebe, zeigte danach den alten Stand —
 * genau der Fehler, den `geocoder_state_setzen()` eine Zeile weiter oben
 * behebt.
 *
 * DIE FEHLENDE SPALTE IST KEIN FEHLER, SONDERN EIN FENSTER: zwischen Deploy
 * und `update.php`. Die Seite soll dann speichern koennen, was sie speichern
 * kann, und diesen einen Wert eben nicht — sie erfaehrt es am Rueckgabewert.
 */
function geocoder_konto_setzen(int $userId, bool $an): bool
{
    try {
        db()->prepare('UPDATE users SET adresssuche = ? WHERE id = ?')
            ->execute([$an ? 1 : 0, $userId]);
    } catch (PDOException $e) {
        error_log('adresssuche speichern: ' . $e->getMessage());
        return false;
    }
    geocoder_konto_an($userId, true);
    return true;
}

/**
 * Ist die Adresse brauchbar? Nur `https://` mit Rechnernamen.
 *
 * KEIN `http://`, und das ist keine Strenge um ihrer selbst willen: Die
 * Anfrage traegt den getippten Ortsnamen, und die Anwendung selbst laeuft nur
 * ueber HTTPS (das Sitzungs-Cookie ist `secure`). Eine Klartextabfrage aus
 * einer verschluesselten Seite heraus waere zudem „mixed content" — der
 * Browser blockt sie, und der Fehler faende sich erst am Feld.
 */
function geocoder_adresse_pruefen(string $roh): ?string
{
    $roh = rtrim(trim($roh), '/');
    if ($roh === '') { return GEOCODER_VORGABE; }
    if (mb_strlen($roh) > 180) { return null; }        // app_state.v ist VARCHAR(190)
    $t = parse_url($roh);
    if (!is_array($t) || ($t['scheme'] ?? '') !== 'https') { return null; }
    if (($t['host'] ?? '') === '') { return null; }
    if (isset($t['query']) || isset($t['fragment'])) { return null; }
    return $roh;
}
