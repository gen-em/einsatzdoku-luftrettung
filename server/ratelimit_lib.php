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

    /* DEMO ZAEHLT ANDERS ALS DIE VIER DARUEBER: nicht Fehlversuche, sondern
     * GELUNGENE Anmeldungen am Demo-Konto (E-P1-20).
     *
     * Warum ueberhaupt: Die Zugangsdaten dieses Kontos sind oeffentlich. Ein
     * Fehlversuchszaehler laeuft dort nie an — es gibt nichts zu erraten.
     * Begrenzt werden soll deshalb die MENGE der Nutzung: Das Konto ist zum
     * Ausprobieren da, nicht als Rechenzeit fuer Fremde.
     *
     * Die Werte sind so gewaehlt, dass sie im Alltag nicht auffallen. 20
     * Anmeldungen je Stunde und Adresse deckt jedes Ausprobieren ab,
     * einschliesslich mehrfachem Abmelden; wer sie ueberschreitet, laesst ein
     * Skript laufen. Die globale Grenze von 300 je Stunde greift erst, wenn
     * viele Adressen gleichzeitig kommen — dann ist der Server gemeint, nicht
     * eine Person. Beide Sperren dauern eine Stunde, so lang wie das Fenster:
     * laenger waere Strafe, kuerzer waere wirkungslos. */
    'demo'  => ['max' => 20, 'fenster' => 3600, 'sperre' => 3600],
    'demog' => ['max' => 300, 'fenster' => 3600, 'sperre' => 3600],
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
function rate_erlaubt(string $topf, ?string $konto = null,
                      ?array $merkmale = null): bool
{
    $grenze = RATE_GRENZEN[$topf] ?? null;
    if ($grenze === null) { return true; }

    try {
        $pdo = db();
        $st = $pdo->prepare(
            'SELECT 1 FROM rate_limits
             WHERE topf = ? AND merkmal = ? AND gesperrt_bis IS NOT NULL
               AND gesperrt_bis > NOW() LIMIT 1');
        foreach ($merkmale ?? rate_merkmale($konto) as $merkmal) {
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
function rate_misserfolg(string $topf, ?string $konto = null,
                         ?array $merkmale = null): void
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

        foreach ($merkmale ?? rate_merkmale($konto) as $merkmal) {
            $st->execute([$topf, $merkmal,
                          $grenze['fenster'], $grenze['fenster'], $grenze['fenster']]);
            $sperren->execute([$grenze['sperre'], $topf, $merkmal, $grenze['max']]);
        }
    } catch (Throwable $ex) {
        error_log('Ratenschutz konnte nicht zaehlen (' . $topf . '): ' . $ex->getMessage());
    }
}

/**
 * Eine Anfrage verbuchen, ohne dass es einen Misserfolg gaebe.
 *
 * Zwei der vier Toepfe kennen kein Scheitern: Der Salz-Endpunkt antwortet
 * jeder Adresse — das ist gerade der Sinn des Pseudo-Salts. Und die
 * Zuruecksetzen-Anforderung antwortet immer gleich, egal ob es das Konto gibt.
 * An beiden Stellen ist die MENGE der Anfragen das, was begrenzt werden soll,
 * nicht ein Fehlversuch.
 *
 * Technisch dasselbe wie rate_misserfolg(); der eigene Name steht hier, damit
 * an der Aufrufstelle nicht "Misserfolg" steht, wo es keinen gibt.
 */
function rate_zaehlen(string $topf, ?string $konto = null,
                      ?array $merkmale = null): void
{
    rate_misserfolg($topf, $konto, $merkmale);
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
function rate_gesperrt_bis(string $topf, ?string $konto = null,
                           ?array $merkmale = null): ?string
{
    try {
        $st = db()->prepare(
            'SELECT MAX(gesperrt_bis) FROM rate_limits
             WHERE topf = ? AND merkmal = ? AND gesperrt_bis > NOW()');
        $spaetestens = null;
        foreach ($merkmale ?? rate_merkmale($konto) as $merkmal) {
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

/* ------------------------------------------------- Demo-Konto (E-P1-20) --
 *
 * Zwei Toepfe, weil zwei verschiedene Fragen dahinterstehen:
 *
 *   demo   je IP-Adresse  — "nutzt EINE Stelle das Konto uebermaessig?"
 *   demog  global         — "wird das Konto insgesamt ueberrannt?"
 *
 * Ein einziger Topf mit beiden Merkmalen ginge nicht: Die Grenzen sind
 * verschieden (20 gegen 300), und RATE_GRENZEN haengt am Topf, nicht am
 * Merkmal.
 *
 * Das globale Merkmal ist eine feste Zeichenkette. Sie kann mit keinem
 * IP-Merkmal kollidieren, weil jene mit 'ip:' beginnen.
 */

/* Das globale Merkmal wird AUSDRUECKLICH uebergeben, nicht ueber
 * rate_merkmale() gebildet: Jene Funktion haengt die IP-Adresse immer an. Fuer
 * den globalen Topf hiesse das eine zweite, nutzlose Zeile je Adresse — ein
 * Zaehler, der bei 300 je IP sperren wuerde und damit nie vor dem Topf `demo`
 * greift, der schon bei 20 sperrt. Er stuende nur in der Tabelle herum. */
const RATE_DEMO_GLOBAL = ['alle'];

/** Darf sich jetzt jemand am Demo-Konto anmelden? */
function rate_demo_erlaubt(): bool
{
    return rate_erlaubt('demo') && rate_erlaubt('demog', null, RATE_DEMO_GLOBAL);
}

/**
 * Eine GELUNGENE Anmeldung am Demo-Konto verbuchen.
 *
 * Aufruf NACH der erfolgreichen Pruefung — anders als bei den uebrigen
 * Toepfen, wo gezaehlt wird, was scheitert. Deshalb steht hier auch kein
 * rate_erfolg(): Ein Erfolg leert den Zaehler nicht, er fuellt ihn.
 */
function rate_demo_zaehlen(): void
{
    rate_zaehlen('demo');
    rate_zaehlen('demog', null, RATE_DEMO_GLOBAL);
}

/** Bis wann ist gesperrt? Fuer die Meldung an der Anmeldeseite. */
function rate_demo_gesperrt_bis(): ?string
{
    return rate_gesperrt_bis('demo') ?? rate_gesperrt_bis('demog', null, RATE_DEMO_GLOBAL);
}
