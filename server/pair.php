<?php
declare(strict_types=1);
/**
 * Geraete-Kopplung: Die Uhr tauscht einen kurzlebigen Einmal-Code gegen
 * frische Zugangsdaten. POST JSON {"code": "..."} — keine Auth-Header.
 * Antwort: {"device_id": "...", "api_key": "..."}
 *
 * Dieser Endpunkt ist OHNE ANMELDUNG erreichbar und gibt bei Erfolg
 * Zugangsdaten heraus, mit denen sich Einsaetze in ein fremdes Konto
 * einschleusen lassen. Er braucht deshalb drei Dinge, und alle drei muessen
 * gleichzeitig gelten:
 *
 *   1. Genug Moeglichkeiten     PAIR_LEN Zeichen aus PAIR_CHARS (siehe db.php)
 *   2. Kurze Gueltigkeit        PAIR_TTL_MIN Minuten
 *   3. Eine wirksame Bremse     Ratenschutz (ratelimit_lib.php)
 *
 * Punkt 3 traegt die Hauptlast. Die frueher hier stehende feste Verzoegerung
 * von 0,3 s je Anfrage war KEINE Bremse: Sie verzoegert die einzelne Anfrage,
 * behindert parallele Anfragen aber ueberhaupt nicht. Mit genuegend
 * gleichzeitigen Verbindungen war der damalige Coderaum (5 Zeichen, 60
 * Minuten gueltig) in gut einer Stunde vollstaendig durchlaufbar.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ratelimit_lib.php';

header('Content-Type: application/json; charset=utf-8');

// Zeitpunkt fuer die konstante Antwortdauer. Jeder Fehlerzweig endet ueber
// abweisen(), damit ein Angreifer aus der Dauer nicht ablesen kann, WIE weit
// er gekommen ist: "Muster falsch" darf nicht schneller sein als "Code
// unbekannt" und dieses nicht schneller als "Code bereits verbraucht".
$t0 = microtime(true);

function abweisen(int $status, string $fehler, bool $zaehlen = true): never
{
    global $t0;
    if ($zaehlen) { rate_misserfolg('pair'); }
    rate_gleiche_dauer($t0);
    http_response_code($status);
    echo json_encode(['error' => $fehler]);
    exit;
}

// Sperre VOR jeder weiteren Arbeit pruefen — sonst bleibt der Aufwand, den
// eine Anfrage ausloest, trotz Sperre als Angriffsflaeche offen.
if (!rate_erlaubt('pair')) {
    rate_gleiche_dauer($t0);
    http_response_code(429);
    echo json_encode(['error' => 'zu_viele_versuche',
                      'meldung' => 'Zu viele Kopplungsversuche. Bitte spaeter erneut.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { abweisen(405, 'method', false); }

$b = json_decode(file_get_contents('php://input'), true);
$code = strtoupper(trim((string)($b['code'] ?? '')));

// Das Muster bildet jetzt das TATSAECHLICHE Alphabet und die TATSAECHLICHE
// Laenge ab. Frueher liess es vier bis acht Zeichen zu, und ausserdem die
// Zeichen 0, O, 1 und I — die im Alphabet bewusst fehlen, weil sie sich auf
// einem Uhrendisplay nicht unterscheiden lassen. Ein Muster, das mehr erlaubt
// als der Erzeuger je ausgibt, prueft nichts.
if (!preg_match(PAIR_RE, $code)) { abweisen(400, 'code'); }

$pdo = db();

/* ENTWERTEN ZUERST, DANN PRUEFEN.
 *
 * Frueher lief es andersherum: Erst suchte eine Abfrage den Code, dann
 * entwertete eine Aktualisierung ihn — und deren Ergebnis wurde nicht
 * ausgewertet. Zwei gleichzeitige Anfragen mit demselben Code fanden ihn also
 * beide gueltig und legten beide ein Geraet an. Der Code war damit nicht
 * einmalig, obwohl die Dokumentation genau das zusicherte.
 *
 * Diese Reihenfolge macht die Datenbank zum Schiedsrichter: Genau eine
 * Anfrage aendert die Zeile, alle anderen sehen used_at bereits gesetzt. Die
 * Auswertung ist hier eindeutig, weil used_at immer von NULL auf einen Wert
 * wechselt — es gibt keinen Fall, in dem "nichts geaendert" auch "war schon
 * richtig" bedeuten koennte.
 */
$entwerten = $pdo->prepare(
    'UPDATE pair_codes SET used_at = NOW()
     WHERE code = ? AND used_at IS NULL
       AND created_at > DATE_SUB(NOW(), INTERVAL ' . PAIR_TTL_MIN . ' MINUTE)');
$entwerten->execute([$code]);
if ($entwerten->rowCount() !== 1) {
    // Unbekannt, abgelaufen oder bereits verbraucht — alle drei bekommen
    // dieselbe Antwort. Der Unterschied ginge nur den Angreifer etwas an.
    abweisen(404, 'invalid');
}

$st = $pdo->prepare('SELECT user_id FROM pair_codes WHERE code = ?');
$st->execute([$code]);
$ownerId = $st->fetchColumn();
if ($ownerId === false) { abweisen(404, 'invalid'); }

$devId = 'dev-' . bin2hex(random_bytes(4));
$key   = bin2hex(random_bytes(24));
try {
    $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label)
                   VALUES (?,?,?,?)')
        ->execute([(int)$ownerId, $devId,
                   password_hash($key, PASSWORD_DEFAULT),
                   'Uhr (gekoppelt ' . date('d.m.Y') . ')']);
} catch (Throwable $ex) {
    // Der Code bleibt in diesem Fall verbraucht. Das ist die sichere
    // Richtung: Ein Code, der nach einem Fehlschlag wieder gueltig waere,
    // liesse sich beliebig oft anlaufen. Die NutzerIn erzeugt einfach einen
    // neuen — das kostet sie einen Klick.
    error_log('Kopplung fehlgeschlagen: ' . $ex->getMessage());
    rate_gleiche_dauer($t0);
    http_response_code(500);
    echo json_encode(['error' => 'server']);
    exit;
}

// Erfolg: Zaehler dieses Aufrufers leeren, damit ihn frueheres Vertippen
// nicht spaeter aussperrt.
rate_erfolg('pair');
echo json_encode(['device_id' => $devId, 'api_key' => $key]);
