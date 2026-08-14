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
 * Seit Web 4.4.0 kommen zwei Dinge fuer den Fall dazu, dass es doch einmal
 * gelingt: eine Obergrenze fuer Geraete je Konto (MAX_GERAETE) und eine
 * E-Mail an den Kontoinhaber, sobald ein Geraet hinzukommt. Beides aendert
 * nichts an der Wahrscheinlichkeit, aber viel daran, wie lange ein fremdes
 * Geraet unbemerkt bleibt.
 *
 * Punkt 3 traegt die Hauptlast. Die frueher hier stehende feste Verzoegerung
 * von 0,3 s je Anfrage war KEINE Bremse: Sie verzoegert die einzelne Anfrage,
 * behindert parallele Anfragen aber ueberhaupt nicht. Mit genuegend
 * gleichzeitigen Verbindungen war der damalige Coderaum (5 Zeichen, 60
 * Minuten gueltig) in gut einer Stunde vollstaendig durchlaufbar.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ratelimit_lib.php';
require_once __DIR__ . '/smtp.php';

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
$ownerId = (int)$ownerId;

/* ---- Obergrenze fuer Geraete je Konto (M4-10) -----------------------------
 *
 * Ohne sie konnte ein Konto beliebig viele Zugangsdatensaetze ansammeln — ein
 * eingeschleustes Geraet stuende einfach als weitere Zeile in einer Liste, die
 * niemand zaehlt. Die Grenze steht in db.php (MAX_GERAETE).
 *
 * DER CODE IST HIER BEREITS VERBRAUCHT, und das bleibt so. Ihn wieder gueltig
 * zu machen hiesse, einen Code je nach Kontostand mehrfach anlaufen zu duerfen
 * — genau die Eigenschaft, die M4-03 beseitigt hat. Die Kosten sind ein
 * Klick: neuen Code erzeugen, nachdem ein Geraet geloescht wurde.
 *
 * Eigener Fehlerschluessel, damit die Ursache nicht in "invalid" verschwindet.
 * Kein rate_misserfolg: Der Code war richtig, hier ist niemand am Raten.
 */
if (geraete_grenze_erreicht($pdo, $ownerId)) {
    rate_gleiche_dauer($t0);
    http_response_code(409);
    echo json_encode(['error'   => 'device_limit',
                      'meldung' => 'Es sind bereits ' . MAX_GERAETE . ' Geraete mit diesem '
                                 . 'Konto verbunden. Erst eines loeschen, dann neu koppeln.']);
    exit;
}

/* ---- Gerätekennung: 128 statt 32 Bit (M4-08) ------------------------------
 *
 * Vier Zufallsbytes ergeben rund vier Milliarden Möglichkeiten. Das klingt
 * viel und ist es nicht: Beim Geburtstagsproblem liegt die Wahrscheinlichkeit
 * eines Zusammentreffens schon bei einigen zehntausend Geraeten bei 50 %. Ein
 * Zusammentreffen ist hier kein kosmetisches Problem — die Kennung ist der
 * Schluessel, ueber den ein Upload seinem Konto zugeordnet wird.
 *
 * Auffangen wuerde es der eindeutige Schluessel auf der Spalte; die Kopplung
 * schluege dann fehl und muesste wiederholt werden. Sechzehn Bytes machen den
 * Fall so unwahrscheinlich, dass er praktisch nicht mehr vorkommt.
 *
 * Die Kennung ist KEIN Geheimnis — sie steht im Kopf jeder Anfrage; die
 * Berechtigung haengt am api_key. Der Zugewinn liegt allein darin, dass sich
 * Kennungen weder zufaellig treffen noch durchzaehlen lassen.
 *
 * BESTANDSGERAETE BEHALTEN IHRE KURZE KENNUNG. Die Spalte ist VARCHAR(64), die
 * neue Kennung braucht 36 Zeichen — kein Schemawechsel, keine Migration, kein
 * Grund, eine gekoppelte Uhr neu zu verbinden.
 */
$devId = 'dev-' . bin2hex(random_bytes(16));
$key   = bin2hex(random_bytes(24));
try {
    $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label)
                   VALUES (?,?,?,?)')
        ->execute([$ownerId, $devId,
                   password_hash($key, PASSWORD_DEFAULT),
                   /* Nur "Uhr", OHNE Datum.
                    *
                    * Der Name trug bis Web 5.0.0 das Kopplungsdatum ('Uhr
                    * (gekoppelt 11.08.2026)'). Dieselbe Angabe steht in
                    * devices.created_at und wird von der Geraeteliste und vom
                    * Hinweis auf der Startseite ohnehin ausgegeben — dort
                    * stand sie deshalb zweimal hintereinander.
                    *
                    * Eine Angabe, die an zwei Stellen gefuehrt wird, laeuft
                    * frueher oder spaeter auseinander: Wer das Geraet
                    * umbenennt, hat ein Datum im Namen, das mit nichts mehr
                    * zusammenhaengt. Der Name ist frei waehlbar, das Datum
                    * gehoert der Zeile. */
                   'Uhr']);
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

/* ---- Den Kontoinhaber benachrichtigen (M4-10) -----------------------------
 *
 * Dies ist die Stelle, an der ein abgefangener Kopplungscode zu einem fremden
 * Geraet wird — und die einzige, an der die betroffene Person es erfahren
 * kann, ohne sich zufaellig anzumelden. Die Weboberflaeche zeigt neu
 * hinzugekommene Geraete zusaetzlich an; das erreicht aber nur, wer hinsieht.
 *
 * ERST ANTWORTEN, DANN VERSENDEN. Die Uhr wartet auf diese Antwort, und ihr
 * Code ist bereits verbraucht: Bricht sie wegen eines langsamen Mailservers
 * ab, haelt sie die Kopplung fuer gescheitert, obwohl das Geraet angelegt ist.
 * Deshalb der Abschluss der Antwort davor und ein kurzes Zeitlimit dahinter.
 *
 * Ein Fehlschlag des Versands darf die Kopplung nicht beruehren — sie ist
 * abgeschlossen und die Antwort ist raus. Er landet im Fehlerprotokoll.
 */
antwort_abschliessen();
try {
    $ust = $pdo->prepare('SELECT email FROM users WHERE id = ?');
    $ust->execute([$ownerId]);
    $mail = $ust->fetchColumn();
    if ($mail !== false && $mail !== null && $mail !== '') {
        smtp_send((string)$mail,
            'Neues Gerät gekoppelt — Gen-EM Einsatzdokumentation Luftrettung',
            "Hallo,\n\n"
            . "mit deinem Konto der Gen-EM Einsatzdokumentation Luftrettung wurde soeben ein\n"
            . "neues Gerät gekoppelt:\n\n"
            . "  Geräte-ID: " . $devId . "\n"
            . "  Zeitpunkt: " . fmt_local(gmdate('Y-m-d H:i:s'), 'd.m.Y H:i') . " Uhr\n\n"
            . "War das deine Uhr, ist alles in Ordnung — du musst nichts tun.\n\n"
            . "War es das nicht, deaktiviere oder lösche das Gerät bitte umgehend unter\n"
            . "Einstellungen → Geräte. Ab diesem Moment kann es keine Daten mehr hochladen.\n"
            . $CFG['app']['base_url'] . "/einstellungen.php?t=geraete\n\n"
            . "Bei Fragen oder Problemen wende dich gerne an philipp@gen-em.org.\n\n"
            . "Viele Grüße\nGen-EM Einsatzdokumentation Luftrettung\n",
            5);
    }
} catch (Throwable $ex) {
    error_log('Hinweis auf neues Geraet konnte nicht verschickt werden: ' . $ex->getMessage());
}
