<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Ratenschutz (Baustein B3).
 *
 * WARUM ES DIESE DATEI GIBT
 * Der bisherige Rateschutz bei der Anmeldung lag in der SITZUNG des
 * Aufrufers — wer das Cookie wegwirft, hat wieder fuenf Versuche frei. Das ist
 * kein Schutz, sondern eine Bequemlichkeitsbremse fuer versehentliche
 * Tippfehler. Die Bremse bei der Kopplung war eine feste Verzoegerung je
 * Anfrage; sie behindert parallele Anfragen ueberhaupt nicht.
 *
 * Diese Zaehlung liegt in der Datenbank und haengt an Kontokennung UND
 * IP-Adresse. Der Aufrufer kann sie nicht zuruecksetzen.
 *
 * ZWEI EIGENSCHAFTEN, DIE NICHT VERHANDELBAR SIND
 *
 *  1. Die Sperre greift, BEVOR eine teure Pruefung laeuft (bcrypt, PBKDF2).
 *     Sonst bleibt der Rechenaufwand als Angriffsflaeche fuer Ueberlastung
 *     offen: Wer gesperrt ist, kann den Server trotzdem rechnen lassen.
 *
 *  2. Die Antwortzeit bei Misserfolg ist konstant. Ein Zeitunterschied
 *     zwischen "Konto gibt es nicht" und "Passwort falsch" verraet dasselbe
 *     wie eine unterschiedliche Meldung, nur leiser.
 *
 * VERHALTEN, WENN DIE TABELLE FEHLT
 * Zwischen dem Aufspielen dieser Fassung und dem Lauf der Migration gibt es
 * ein Zeitfenster ohne Tabelle. In diesem Fenster laesst der Schutz durch und
 * schreibt eine Zeile ins Fehlerprotokoll. Die Gegenrichtung — Anmeldung fuer
 * alle sperren, bis jemand die Wartungsseite oeffnet — waere ein
 * selbstgebauter Ausfall.
 */

/**
 * Grenzen je Anwendungsfall.
 *
 *   max     Versuche, bis gesperrt wird
 *   fenster Beobachtungszeitraum in Sekunden
 *   sperre  Dauer der Sperre in Sekunden
 *
 * Die Werte sind so gewaehlt, dass eine Person mit Tippfehlern sie im Alltag
 * nicht bemerkt, ein Durchprobieren aber aussichtslos wird. Beispiel Kopplung:
 * Sechs Zeichen aus einem Alphabet von 32 sind 30 Bit, also rund 1,07
 * Milliarden Moeglichkeiten. Mit 10 Versuchen je 10 Minuten und einer
 * Gueltigkeit von 10 Minuten bleibt der Coderaum praktisch unerreichbar —
 * frueher war er mit 5 Zeichen, 60 Minuten Gueltigkeit und ohne Ratenschutz in
 * rund 1,4 Stunden vollstaendig durchlaufbar.
 */
const RATE_GRENZEN = [
    'login' => ['max' => 10, 'fenster' =>  900, 'sperre' =>  900],
    'salt'  => ['max' => 30, 'fenster' =>  900, 'sperre' =>  900],
    'reset' => ['max' =>  5, 'fenster' => 3600, 'sperre' => 3600],
    'pair'  => ['max' => 10, 'fenster' =>  600, 'sperre' =>  600],
];

/**
 * IP-Adresse des Aufrufers, in der Form, in der sie als Merkmal taugt.
 *
 * Kopfzeilen von Zwischenstationen (X-Forwarded-For und Verwandte) werden
 * BEWUSST NICHT ausgewertet: Sie stammen vom Aufrufer und liessen sich zum
 * Zuruecksetzen des Zaehlers frei erfinden — genau der Fehler, den dieser
 * Baustein beheben soll. Steht die Anwendung hinter einem Proxy, gehoert das
 * Auswerten in die Serverkonfiguration, nicht hierher.
 */
function rate_ip(): string
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return $ip !== '' ? mb_substr($ip, 0, 45) : 'unbekannt';
}

/**
 * Die Merkmale, unter denen gezaehlt wird.
 * Immer die IP-Adresse; zusaetzlich die Kontokennung, wo es eine gibt.
 * @return array<int, string>
 */
function rate_merkmale(?string $konto = null): array
{
    $m = ['ip:' . rate_ip()];
    if ($konto !== null && $konto !== '') {
        // Kleinschreibung, damit "A@b.de" und "a@b.de" denselben Zaehler
        // treffen — sonst waere die Sperre mit der Umschalttaste zu umgehen.
        $m[] = 'id:' . mb_substr(mb_strtolower(trim($konto)), 0, 180);
    }
    return $m;
}

/**
 * Ist der Zugriff erlaubt? VOR jeder teuren Pruefung aufrufen.
 *
 * Liefert true, wenn weitergemacht werden darf, und false, wenn eine Sperre
 * greift. Der Zaehler wird hier NICHT erhoeht — das geschieht erst bei einem
 * Misserfolg (rate_misserfolg), damit gelungene Anmeldungen nicht auf das
 * Kontingent gehen.
 */
function rate_erlaubt(string $topf, ?string $konto = null): bool
{
    $grenze = RATE_GRENZEN[$topf] ?? null;
    if ($grenze === null) { return true; }

    try {
        $pdo = db();
        $st = $pdo->prepare(
            'SELECT 1 FROM rate_limits
             WHERE topf = ? AND merkmal = ? AND gesperrt_bis IS NOT NULL
               AND gesperrt_bis > NOW() LIMIT 1');
        foreach (rate_merkmale($konto) as $merkmal) {
            $st->execute([$topf, $merkmal]);
            if ($st->fetchColumn() !== false) { return false; }
        }
        return true;
    } catch (Throwable $ex) {
        error_log('Ratenschutz nicht verfuegbar (' . $topf . '): ' . $ex->getMessage());
        return true;   // s. Kopfkommentar: durchlassen statt selbstgebauter Ausfall
    }
}

/**
 * Einen Fehlversuch verbuchen. Erreicht ein Merkmal seine Grenze, wird es
 * gesperrt.
 *
 * Das Zeitfenster wandert nicht mit: Nach Ablauf beginnt die Zaehlung von
 * vorn. Das ist die einfache Variante und fuer den Zweck ausreichend — sie
 * erlaubt im schlechtesten Fall die doppelte Zahl an Versuchen ueber eine
 * Fenstergrenze hinweg, was gegenueber "unbegrenzt" keine Rolle spielt.
 */
function rate_misserfolg(string $topf, ?string $konto = null): void
{
    $grenze = RATE_GRENZEN[$topf] ?? null;
    if ($grenze === null) { return; }

    try {
        $pdo = db();
        // REIHENFOLGE DER ZUWEISUNGEN IST WESENTLICH: MySQL wertet sie von
        // links nach rechts aus. 'versuche' und 'gesperrt_bis' muessen den
        // ALTEN 'fenster_start' sehen, deshalb wird dieser zuletzt gesetzt.
        // Andersherum verglichen sie gegen den soeben gesetzten Wert — die
        // Bedingung waere nie wahr und eine Sperre liefe nie ab.
        $st = $pdo->prepare(
            'INSERT INTO rate_limits (topf, merkmal, versuche, fenster_start)
             VALUES (?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE
               versuche = IF(fenster_start < DATE_SUB(NOW(), INTERVAL ? SECOND),
                             1, versuche + 1),
               gesperrt_bis = IF(fenster_start < DATE_SUB(NOW(), INTERVAL ? SECOND),
                                 NULL, gesperrt_bis),
               fenster_start = IF(fenster_start < DATE_SUB(NOW(), INTERVAL ? SECOND),
                                  NOW(), fenster_start)');
        $sperren = $pdo->prepare(
            'UPDATE rate_limits SET gesperrt_bis = DATE_ADD(NOW(), INTERVAL ? SECOND)
             WHERE topf = ? AND merkmal = ? AND versuche >= ?');

        foreach (rate_merkmale($konto) as $merkmal) {
            $st->execute([$topf, $merkmal,
                          $grenze['fenster'], $grenze['fenster'], $grenze['fenster']]);
            $sperren->execute([$grenze['sperre'], $topf, $merkmal, $grenze['max']]);
        }
    } catch (Throwable $ex) {
        error_log('Ratenschutz konnte nicht zaehlen (' . $topf . '): ' . $ex->getMessage());
    }
}

/**
 * Nach einem Erfolg die Zaehler der beteiligten Merkmale leeren.
 *
 * Bewusst auch fuer die IP-Adresse: Wer sich erfolgreich anmeldet, ist mit
 * hoher Wahrscheinlichkeit kein Angreifer, und mehrere Personen hinter einer
 * gemeinsamen Adresse sollen sich nicht gegenseitig aussperren.
 */
function rate_erfolg(string $topf, ?string $konto = null): void
{
    try {
        $st = db()->prepare('DELETE FROM rate_limits WHERE topf = ? AND merkmal = ?');
        foreach (rate_merkmale($konto) as $merkmal) {
            $st->execute([$topf, $merkmal]);
        }
    } catch (Throwable $ex) {
        error_log('Ratenschutz konnte nicht zuruecksetzen (' . $topf . '): ' . $ex->getMessage());
    }
}

/**
 * Konstante Antwortzeit bei Misserfolg.
 *
 * Aufruf am ENDE des Fehlerzweigs mit dem Zeitpunkt vom Anfang der Anfrage.
 * Die Funktion wartet, bis die Mindestdauer erreicht ist. Damit dauert eine
 * abgewiesene Anfrage immer gleich lang — ob das Konto existiert, ob eine
 * bcrypt-Pruefung lief oder ob die Sperre sofort gegriffen hat.
 *
 * Das ist eine NOTLOESUNG und als solche gekennzeichnet: Sie verlangsamt jede
 * abgewiesene Anfrage. Wo ein Zeitunterschied aus einem MAILVERSAND stammt,
 * hilft sie nicht zuverlaessig — dort gehoert der Versand aus der Anfrage
 * heraus in eine Warteschlange.
 */
function rate_gleiche_dauer(float $beginn, float $mindestSekunden = 0.35): void
{
    $verstrichen = microtime(true) - $beginn;
    $rest = $mindestSekunden - $verstrichen;
    if ($rest > 0) { usleep((int)round($rest * 1000000)); }
}

/**
 * Wann laeuft die Sperre ab? Fuer eine Meldung, die nicht raten laesst.
 * Liefert null, wenn nichts gesperrt ist.
 */
function rate_gesperrt_bis(string $topf, ?string $konto = null): ?string
{
    try {
        $st = db()->prepare(
            'SELECT MAX(gesperrt_bis) FROM rate_limits
             WHERE topf = ? AND merkmal = ? AND gesperrt_bis > NOW()');
        $spaetestens = null;
        foreach (rate_merkmale($konto) as $merkmal) {
            $st->execute([$topf, $merkmal]);
            $v = $st->fetchColumn();
            if ($v !== false && $v !== null && ($spaetestens === null || $v > $spaetestens)) {
                $spaetestens = (string)$v;
            }
        }
        return $spaetestens;
    } catch (Throwable $ex) {
        return null;
    }
}
