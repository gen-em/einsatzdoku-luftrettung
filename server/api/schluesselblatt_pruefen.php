<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth_guard.php';        // liefert $userId
require_betreiberin();
require_once __DIR__ . '/../serverkrypto_lib.php';
require_once __DIR__ . '/../einstieg_lib.php';
require_once __DIR__ . '/../ratelimit_lib.php';

/**
 * api/schluesselblatt_pruefen.php — die Betreiber-Rueckfrage (P5b/AP9,
 * E-P5b-10, -21; Mockup M-P5b-02c, Teil 2).
 *
 * ===========================================================================
 * ES WIRD EINGEGEBEN, NICHT BESTAETIGT
 * ===========================================================================
 *
 * Die Frage lautet nicht „haben Sie das Blatt?", sondern „was steht in Gruppe
 * 3?". Ein Haken waere wertlos: Er kostet einen Klick und beweist nichts. Wer
 * vier zufaellig gewaehlte Vierergruppen abtippt, hat das Blatt vor sich
 * gehabt — und genau das ist die Auskunft, die diese Frage haben will.
 *
 * VIER GRUPPEN UND NICHT DER GANZE WERT. 128 Hexzeichen abzutippen alle drei
 * Monate ist eine Zumutung, der man ausweicht; sechzehn Zeichen sind eine
 * Minute. Und der Beweis ist derselbe: Wer Gruppe 3 und 11 des
 * Serverschluessels kennt, hat das Blatt.
 *
 * DIE POSITIONEN WAEHLT DER SERVER, JE ANZEIGE NEU. Ein Blatt, das immer nach
 * denselben Gruppen gefragt wird, laesst sich mit einem Zettel beantworten,
 * auf dem vier Gruppen stehen. `random_int()` und nichts anderes.
 *
 * ===========================================================================
 * ZWEI AUFRUFE, EINE SITZUNG
 * ===========================================================================
 *
 *   `aktion=stellen`  Der Server wuerfelt die Positionen, LEGT SIE IN DIE
 *                     SITZUNG und nennt sie. Er gibt KEINE Werte heraus —
 *                     nur die Nummern und die achtstellige Kennung, damit
 *                     erkennbar ist, welches Blatt gemeint ist.
 *   `aktion=pruefen`  Die vier Antworten kommen zurueck und werden gegen die
 *                     Gruppen aus `config.php` gehalten.
 *
 * WARUM DIE POSITIONEN IN DIE SITZUNG UND NICHT INS FORMULAR. Ein verstecktes
 * Feld waere vom Browser waehlbar — wer die Positionen selbst bestimmt, sucht
 * sich die zwei aus, die er kennt. Der Server muss sich merken, was er
 * gefragt hat.
 *
 * ===========================================================================
 * GLEICHE DAUER BEI ERFOLG UND FEHLSCHLAG
 * ===========================================================================
 *
 * Verglichen wird mit `hash_equals()`, und zwar ALLE VIER Gruppen, auch wenn
 * die erste schon falsch ist. Ein Abbruch bei der ersten Abweichung waere
 * messbar: Wer raet, saehe an der Antwortzeit, wie weit er gekommen ist —
 * und haette aus einem 16-Zeichen-Raetsel vier Raetsel zu vier Zeichen
 * gemacht. Das ist der Unterschied zwischen 30^16 und 4 x 30^4.
 *
 * ===========================================================================
 * KEINE SCHLUESSELERNEUERUNG AN DIESER STELLE
 * ===========================================================================
 *
 * E-P5b-10 sagt es ausdruecklich, und es ist die wichtigste Grenze dieses
 * Endpunkts: Den Serverschluessel zu wechseln hiesse, jede versiegelte
 * Sicherung neu zu umhuellen. Das ist ein S10-Vorgang mit eigenem Ablauf,
 * kein Knopf in einem Dialog. Wer sein Blatt verloren hat, DRUCKT ES NEU
 * (Betrieb -> Schluesselblatt) — der Schluessel aendert sich dabei nicht,
 * und genau deshalb ist das der richtige Weg.
 */

header('Content-Type: application/json; charset=utf-8');

api_methode();
csrf_check();

/**
 * Die Werte, nach denen gefragt wird — in fester Reihenfolge.
 *
 * NUR DIE BEIDEN AUS E-P5b-10: Serverschluessel und Server-Anteil. Der
 * BISHERIGE Anteil (`kdf_anteil_alt`) steht waehrend einer Rotation auch auf
 * dem Blatt und wird hier NICHT gefragt — er verschwindet wieder, und eine
 * Frage, die je nach Betriebslage vier oder sechs Felder hat, verwirrt mehr,
 * als sie prueft.
 *
 * @return list<array{schl:string, name:string, hex:string}>
 */
function blatt_werte(): array
{
    $aus = [];
    foreach ([['sk', 'Serverschlüssel', 'server_key'],
              ['an', 'Server-Anteil',   'kdf_anteil']] as [$schl, $name, $k]) {
        $hex = strtolower((string)konfig($k, ''));
        if (preg_match('/^[0-9a-f]{64}$/', $hex)) {
            $aus[] = ['schl' => $schl, 'name' => $name, 'hex' => $hex];
        }
    }
    return $aus;
}

/** Die Vierergruppe Nr. `$nr` (1-16) eines 64-Hex-Werts. */
function blatt_gruppe(string $hex, int $nr): string
{
    return substr($hex, ($nr - 1) * 4, 4);
}

$werte = blatt_werte();
if (!$werte) {
    /* Kein Schluessel in `config.php` — dann gibt es nichts zu bestaetigen.
     * Das ist kein Fehler des Aufrufers, sondern ein Zustand der Anlage; das
     * Schluesselblatt sagt an derselben Stelle dasselbe. */
    http_response_code(409);
    echo json_encode(['error' => 'kein_schluessel',
                      'text'  => 'In config.php steht noch kein Schlüssel. '
                               . 'Unter Betrieb → Servereinstellungen anlegen.']);
    exit;
}

$aktion = (string)($_POST['aktion'] ?? '');
$email  = (string)($_SESSION['blatt_email'] ?? '');
if ($email === '') {
    $st = db()->prepare('SELECT email FROM users WHERE id = ?');
    $st->execute([$userId]);
    $email = (string)($st->fetchColumn() ?: ('konto-' . $userId));
    $_SESSION['blatt_email'] = $email;
}

/* ---- Stellen: die Positionen wuerfeln ----------------------------------- */

if ($aktion === 'stellen') {
    if (!rate_erlaubt('blatt', $email)) {
        http_response_code(429);
        echo json_encode(['error' => 'gesperrt', 'text' => blatt_sperrtext()]);
        exit;
    }

    $fragen = [];
    $merken = [];
    foreach ($werte as $w) {
        /* ZWEI VERSCHIEDENE POSITIONEN je Wert. `random_int` zweimal koennte
         * dieselbe liefern — dann stuenden zwei gleiche Felder da, und die
         * Frage waere um ein Viertel leichter. */
        $a = random_int(1, 16);
        do { $b = random_int(1, 16); } while ($b === $a);
        foreach ([$a, $b] as $nr) {
            $fragen[] = ['schl' => $w['schl'], 'name' => $w['name'], 'nr' => $nr];
            $merken[] = [$w['schl'], $nr];
        }
    }
    $_SESSION['blatt_fragen'] = $merken;

    echo json_encode([
        'ok'      => true,
        'fragen'  => $fragen,
        /* DIE KENNUNG, NICHT DER WERT — die Regel des ganzen Hauses. Sie sagt,
         * WELCHES Blatt gemeint ist, und verraet nichts. */
        'kennung' => array_map(
            static fn(array $w): array => ['schl' => $w['schl'],
                                           'kennung' => schluessel_kennung($w['hex'])],
            $werte),
    ]);
    exit;
}

/* ---- Pruefen ------------------------------------------------------------ */

if ($aktion !== 'pruefen') {
    http_response_code(400);
    echo json_encode(['error' => 'aktion']);
    exit;
}

if (!rate_erlaubt('blatt', $email)) {
    http_response_code(429);
    echo json_encode(['error' => 'gesperrt', 'text' => blatt_sperrtext()]);
    exit;
}

$gefragt = $_SESSION['blatt_fragen'] ?? null;
if (!is_array($gefragt) || $gefragt === []) {
    /* Die Sitzung kennt die Frage nicht — abgelaufen, oder jemand ruft
     * `pruefen` ohne `stellen`. Kein Fehlversuch: Es wurde nichts geraten. */
    http_response_code(409);
    echo json_encode(['error' => 'keine_frage',
                      'text'  => 'Die Frage ist abgelaufen. Bitte die Seite neu laden.']);
    exit;
}

$nachSchl = [];
foreach ($werte as $w) { $nachSchl[$w['schl']] = $w['hex']; }

$antworten = $_POST['gruppen'] ?? [];
if (!is_array($antworten)) { $antworten = []; }

/* ALLE VIER VERGLEICHEN, OHNE ABKUERZUNG — siehe Kopf. `$gut` wird mit `&`
 * fortgeschrieben statt mit einem `break`; die Schleife laeuft immer ganz
 * durch, gleich wie die erste Gruppe ausfaellt. */
$gut = true;
foreach ($gefragt as $i => [$schl, $nr]) {
    $soll = isset($nachSchl[$schl]) ? blatt_gruppe($nachSchl[$schl], (int)$nr) : '';
    /* `schluessel_eingabe_normalisieren()` PASST HIER NICHT — sie verlangt
     * 64 Hexzeichen und gibt sonst `null`. Hier kommen vier. Dieselbe Regel
     * von Hand: Leerraum und Bindestriche weg, klein schreiben. */
    $ist  = strtolower(preg_replace('/[\s\-]+/', '', (string)($antworten[$i] ?? '')) ?? '');
    $gut  = hash_equals($soll, $ist) && $gut;
}

if (!$gut) {
    /* FEHLVERSUCH ZAEHLEN UND DIE FRAGE STEHEN LASSEN. Neue Positionen bei
     * jedem Fehlversuch waeren freundlicher und falsch: Wer raet, bekaeme
     * bei jedem Versuch neue Wuerfel. Dieselbe Frage noch einmal ist die
     * Frage, die gestellt wurde. */
    rate_misserfolg('blatt', $email);
    $rest = max(0, (int)rate_grenze('blatt')['max'] - (int)rate_versuche('blatt', $email));

    protokoll_sicherheit_blatt($email, $rest);

    http_response_code(403);
    echo json_encode([
        'error' => 'falsch',
        'rest'  => $rest,
        'text'  => $rest > 0
            /* DIE DAUER KOMMT AUS DER LEITER, nicht aus `sperre`: Die
             * Sprosse ueberholt sie (siehe RATE_GRENZEN['blatt']). Wer hier
             * `sperre` teilt, schreibt eine Zahl hin, die nicht eintritt —
             * gemessen am 17.09.2026: gemeldet 10 Minuten, gesperrt 15. */
            ? 'Stimmt nicht. Noch ' . $rest . ' ' . ($rest === 1 ? 'Versuch' : 'Versuche')
              . ', dann sperrt die Anmeldung diesen Weg für '
              . (int)round(rate_stufe_dauer(0) / 60) . ' Minuten.'
            : 'Stimmt nicht. Dieser Weg ist jetzt für eine Weile gesperrt.',
    ]);
    exit;
}

/* ---- Bestaetigt --------------------------------------------------------- */

rate_erfolg('blatt', $email);
unset($_SESSION['blatt_fragen']);
blatt_bestaetigt();

/* PROTOKOLL OHNE WERTE, REITER VERWALTUNG (E-P5b-10: „Schluesselblatt
 * bestaetigt, von …"). Der Reiter Sicherheit sammelt Sperren und Angriffe;
 * eine gelungene Bestaetigung ist eine Handlung der BetreiberIn und gehoert
 * ins Audit. Der FEHLVERSUCH geht den anderen Weg (siehe unten). */
require_once __DIR__ . '/../protokoll_lib.php';
protokoll('verwaltung', 'schluesselblatt_bestaetigt',
          'Schlüsselblatt bestätigt, von ' . $email, [], $userId);

echo json_encode(['ok' => true]);

/* ------------------------------------------------------------------------ */

/** Der Text der Sperre — einmal geschrieben, zweimal gebraucht. */
function blatt_sperrtext(): string
{
    return 'Zu viele Fehlversuche. Bitte in einigen Minuten erneut — das '
         . 'Schlüsselblatt lässt sich inzwischen unter Betrieb → '
         . 'Schlüsselblatt neu drucken.';
}

/**
 * Der Fehlversuch ins Sicherheitsprotokoll — OHNE WERTE (E-P5b-21).
 *
 * Nicht `protokoll()`: Der Reiter `sicherheit` existiert dort nicht (er kommt
 * mit S10c). Sperren und Fehlversuche gehen seit P5a nach
 * `sicherheit_ereignisse`, und dorthin gehoert auch dieser.
 */
function protokoll_sicherheit_blatt(string $email, int $rest): void
{
    /* `merkmal` IN DER NORMALISIERTEN FORM (`id:…`) — so, wie es auch in
     * `rate_limits` steht. Ich hatte hier die blanke Adresse stehen; die
     * Zeile sah richtig aus und liess sich doch nicht mit den Zeilen der
     * Sperre zusammenbringen, die `rate_misserfolg()` unter `id:…` schreibt.
     * Die LESBARE Fassung steht in `wer`, dafuer gibt es die Spalte. */
    rate_ereignis('blatt_fehlversuch', 'blatt', rate_merkmal_kennung($email), 0,
                  (int)rate_grenze('blatt')['max'] - $rest, null, $email);
}
