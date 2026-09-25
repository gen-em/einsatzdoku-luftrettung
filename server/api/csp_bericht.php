<?php
declare(strict_types=1);

/**
 * BERICHTE DER CONTENT-SECURITY-POLICY (P5a/AP4, E-P5a-15; SP-5).
 *
 * WOZU. Die Richtlinie kommt zweistufig: zuerst `…-Report-Only`, zwei Wochen
 * im Betrieb, dann scharf (Einstellung `csp_scharf`). Dieser Endpunkt ist die
 * Stelle, an der der Browser meldet, was die Richtlinie blockiert HAETTE. Ohne
 * ihn waere die Report-Only-Phase eine Wartezeit ohne Erkenntnis: Die Meldung
 * stuende in der Konsole desjenigen, bei dem sie auftritt, und sonst nirgends.
 *
 * ER BRAUCHT KEINE ANMELDUNG, UND ZWAR ZWINGEND. Ein Verstoss auf der
 * ANMELDESEITE ist der interessanteste von allen — dort steht der Weg des
 * Passworts (SP-6). Ein Endpunkt, der eine Sitzung verlangt, saehe genau den
 * nicht. Deshalb laedt diese Datei `auth_guard.php` NICHT.
 *
 * WAS DARAUS FOLGT: Er ist von aussen erreichbar, und jeder kann ihn fuellen.
 * Drei Schranken dagegen, und keine davon ist eine Anmeldung:
 *
 *   1. RATENSCHUTZ je Adresse (Topf `csp`, 200 je Stunde). Er zaehlt die
 *      MENGE, nicht Fehlversuche — dasselbe Muster wie beim Demo-Konto.
 *   2. ZUSAMMENFASSUNG STATT PROTOKOLL. Der UNIQUE-Schluessel ueber
 *      (Richtlinie, Quelle, Seite) macht aus tausend gleichen Meldungen EINE
 *      Zeile mit einem Zaehler. Eine gebrochene Kartenseite meldet sonst je
 *      Kachel einmal.
 *   3. LAENGEN WERDEN GEKAPPT, und der Rumpf wird bei 8 kB abgeschnitten.
 *
 * WAS NICHT GESPEICHERT WIRD: keine IP, kein Konto, kein Abfrageteil der
 * Adresse. Diese Anwendung fuehrt kein Protokoll darueber, wer wann welchen
 * Einsatz geoeffnet hat, und eine CSP-Meldung soll daran nichts aendern. Was
 * bleibt, ist der PFAD der Seite — und den braucht man, um die Quelle zu
 * finden.
 *
 * ZWEI FORMATE, EIN ENDPUNKT. `report-uri` schickt
 * `{"csp-report": {"document-uri":…, "violated-directive":…, "blocked-uri":…}}`,
 * die neuere Reporting-API (`report-to`) eine LISTE von
 * `{"type":"csp-violation","body":{"documentURL":…,"effectiveDirective":…,"blockedURL":…}}`.
 * Beide werden angenommen; welche ein Browser schickt, ist seine Sache.
 *
 * ANTWORT: immer 204, auch wenn nichts gespeichert wurde. Ein Browser wertet
 * die Antwort nicht aus, und eine Fehlermeldung an dieser Stelle waere eine
 * Auskunft an jemanden, der nichts zu fragen hatte.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../ratelimit_lib.php';

/** Immer dasselbe Ende: 204, kein Rumpf. */
function csp_ende(): never
{
    http_response_code(204);
    header('Cache-Control: no-store');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { csp_ende(); }
if (!rate_erlaubt('csp')) { csp_ende(); }
rate_zaehlen('csp');

$roh = (string)file_get_contents('php://input', false, null, 0, 8192);
if ($roh === '') { csp_ende(); }
$d = json_decode($roh, true);
if (!is_array($d)) { csp_ende(); }

/**
 * Aus einem der beiden Formate die drei Angaben holen.
 *
 * @return list<array{0:string,1:string,2:string}> Richtlinie, Quelle, Seite
 */
function csp_auslesen(array $d): array
{
    $aus = [];
    /* Format 1: report-uri */
    if (isset($d['csp-report']) && is_array($d['csp-report'])) {
        $r = $d['csp-report'];
        $aus[] = [(string)($r['effective-directive'] ?? $r['violated-directive'] ?? ''),
                  (string)($r['blocked-uri'] ?? ''),
                  (string)($r['document-uri'] ?? '')];
        return $aus;
    }
    /* Format 2: Reporting-API, eine Liste */
    foreach ($d as $e) {
        if (!is_array($e) || ($e['type'] ?? '') !== 'csp-violation') { continue; }
        $b = is_array($e['body'] ?? null) ? $e['body'] : [];
        $aus[] = [(string)($b['effectiveDirective'] ?? ''),
                  (string)($b['blockedURL'] ?? ''),
                  (string)($b['documentURL'] ?? $e['url'] ?? '')];
    }
    return $aus;
}

/**
 * Die Adresse auf das kuerzen, was gebraucht wird.
 *
 * Fuer die SEITE: nur der Pfad — kein Abfrageteil, kein Fragment, kein Host.
 * `einsatz.php?id=4711` wird zu `einsatz.php`; die Kennung des Einsatzes hat
 * in einer Sicherheitsmeldung nichts zu suchen.
 *
 * Fuer die QUELLE: Schema und Host genuegen, um sie zu erkennen — ein voller
 * Kachelpfad `…/12/2145/1398.png` erzeugte sonst je Kachel eine eigene Zeile
 * und machte die Zusammenfassung wirkungslos. Die Sonderwerte `inline`,
 * `eval` und `data` bleiben, wie sie sind.
 */
function csp_kuerzen(string $u, bool $nurHost): string
{
    $u = trim($u);
    if ($u === '') { return '(leer)'; }
    if (in_array($u, ['inline', 'eval', 'data', 'blob', 'self', 'wasm-eval'], true)) { return $u; }
    $t = parse_url($u);
    if ($t === false) { return mb_substr($u, 0, 190); }
    if ($nurHost) {
        $h = (string)($t['host'] ?? '');
        return $h !== '' ? ((string)($t['scheme'] ?? 'https') . '://' . $h) : mb_substr($u, 0, 190);
    }
    $p = (string)($t['path'] ?? '');
    $p = ltrim($p, '/');
    return mb_substr($p !== '' ? $p : '(wurzel)', 0, 190);
}

$eintraege = csp_auslesen($d);
if ($eintraege === []) { csp_ende(); }

try {
    $st = db()->prepare(
        'INSERT INTO csp_berichte (richtlinie, quelle, seite, anzahl, erstellt, zuletzt)
         VALUES (?, ?, ?, 1, UTC_TIMESTAMP(), UTC_TIMESTAMP())
         ON DUPLICATE KEY UPDATE anzahl = anzahl + 1, zuletzt = UTC_TIMESTAMP()');
    foreach ($eintraege as [$richtlinie, $quelle, $seite]) {
        $richtlinie = mb_substr(trim($richtlinie), 0, 64);
        if ($richtlinie === '') { continue; }
        $st->execute([$richtlinie,
                      csp_kuerzen($quelle, true),
                      csp_kuerzen($seite, false)]);
    }
} catch (Throwable $ex) {
    /* Still. Die Tabelle fehlt (Migration noch nicht gelaufen) oder die
     * Datenbank ist weg — beides ist kein Grund, dem Browser etwas zu
     * antworten. Nachlesbar bleibt es trotzdem. */
    system_melden('csp_bericht', 'Bericht nicht gespeichert', $ex);
}

csp_ende();
