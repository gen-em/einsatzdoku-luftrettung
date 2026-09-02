<?php
declare(strict_types=1);
/**
 * Geraete-Kopplung. Der Endpunkt kennt zwei Anliegen:
 *
 *   KOPPELN   POST JSON {"code": "...", "geraet": {...}} — keine Auth-Header.
 *             Antwort: {"device_id": "...", "api_key": "..."}
 *             Die Uhr tauscht einen kurzlebigen Einmal-Code gegen frische
 *             Zugangsdaten. Der Block "geraet" ist FREIWILLIG und traegt eine
 *             Selbstauskunft des Geraets (JSON-Vertrag 1a, R42); er wird ueber
 *             geraete_lib.php gelesen und darf die Kopplung nie zum Scheitern
 *             bringen. Bis Web 12.9.0 verwarf dieser Endpunkt ihn
 *             stillschweigend.
 *
 *   TRENNEN   POST JSON {"aktion": "trennen"} mit den Kopfzeilen
 *             X-Device-Id und X-Api-Key. Antwort: {"ok": true}
 *             Die Uhr gibt ihre Kopplung zurueck, das Geraet wird geloescht.
 *             Seit Web 9.15.0, Backlog Nr. 14 — Begruendung am Zweig selbst.
 *
 * Der Ratenschutz unten gilt fuer BEIDE Zweige.
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
require_once __DIR__ . '/geraete_lib.php';

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

/* ---- Trennen: die Uhr gibt ihre Kopplung zurueck (Backlog Nr. 14) ---------
 *
 * WOZU. Eine geteilt genutzte Uhr wechselt die Person. Bis hierher gab es
 * dafuer nur den Weg "neuen Code eintippen": Gelang das nicht, dokumentierte
 * die Uhr stillschweigend weiter auf das VORHERIGE Konto — niemand sah es ihr
 * an. Die Uhr trennt sich jetzt zuerst ausdruecklich, und erst danach koppelt
 * sie neu.
 *
 * WARUM HIER UND NICHT IN EINEM EIGENEN ENDPUNKT. Die Adresse dieses
 * Endpunkts kennt die Uhr bereits; ein zweiter waere eine weitere
 * anmeldungsfreie Tuer, die dieselbe Bremse noch einmal braeuchte. Der
 * Ratenschutz oben gilt fuer beide Zweige.
 *
 * ES WIRD GELOESCHT, NICHT DEAKTIVIERT. Ein deaktiviertes Geraet belegt
 * weiter einen der MAX_GERAETE Plaetze — und "zu viele Geraete" ist genau der
 * Fehler, in den eine geteilte Uhr sonst laeuft. Der Fremdschluessel setzt
 * device_id in Einsaetzen und Segmenten auf NULL; bereits hochgeladene Daten
 * bleiben unberuehrt (dieselbe Wirkung wie beim Loeschen im Web).
 *
 * ANTWORTZEIT wie in ingest.php: Auch der unbekannte Zweig laeuft gegen
 * AUTH_VERGLEICHSWERT. Sonst waere aus der Dauer ablesbar, welche
 * Geraetekennungen es gibt — und die Kennung ist die Haelfte dessen, was ein
 * Upload braucht.
 */
if (($b['aktion'] ?? '') === 'trennen') {
    $devId  = (string)($_SERVER['HTTP_X_DEVICE_ID'] ?? '');
    $apiKey = (string)($_SERVER['HTTP_X_API_KEY']   ?? '');

    $pdo = db();
    $st  = $pdo->prepare('SELECT id, user_id, api_key_hash FROM devices WHERE device_id = ?');
    $st->execute([$devId]);
    $dev = $st->fetch();
    if (!$dev) {
        password_verify($apiKey, AUTH_VERGLEICHSWERT);
        abweisen(401, 'auth');
    }
    if (!password_verify($apiKey, (string)$dev['api_key_hash'])) {
        abweisen(401, 'auth');
    }

    try {
        $pdo->prepare('DELETE FROM devices WHERE id = ?')->execute([(int)$dev['id']]);
    } catch (Throwable $ex) {
        error_log('Trennen fehlgeschlagen: ' . $ex->getMessage());
        rate_gleiche_dauer($t0);
        http_response_code(500);
        echo json_encode(['error' => 'server']);
        exit;
    }

    rate_erfolg('pair');
    echo json_encode(['ok' => true]);

    /* Den Kontoinhaber unterrichten — dieselbe Ueberlegung wie beim Koppeln:
     * Es ist die eine Gelegenheit, es zu erfahren, ohne sich zufaellig
     * anzumelden. Erst antworten, dann versenden; ein Fehlschlag des Versands
     * darf die Trennung nicht beruehren, sie ist abgeschlossen. */
    antwort_abschliessen();
    try {
        $ust = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $ust->execute([(int)$dev['user_id']]);
        $mail = $ust->fetchColumn();
        if ($mail !== false && $mail !== null && $mail !== '') {
            smtp_send((string)$mail,
                'Gerät getrennt — Gen-EM Einsatzdokumentation Notarzt',
                "Hallo,\n\n"
                . "ein Gerät hat seine Verbindung zu deinem Konto der Gen-EM\n"
                . "Einsatzdokumentation Notarzt soeben selbst getrennt:\n\n"
                . "  Geräte-ID: " . $devId . "\n"
                . "  Zeitpunkt: " . fmt_local(gmdate('Y-m-d H:i:s'), 'd.m.Y H:i') . " Uhr\n\n"
                . "Das geschieht, wenn jemand die Uhr an ihr neu koppelt. Bereits\n"
                . "hochgeladene Einsätze bleiben vollständig erhalten.\n\n"
                . "War das nicht beabsichtigt, koppel die Uhr einfach wieder:\n"
                . $CFG['app']['base_url'] . "/einstellungen.php?t=geraete\n\n"
                . "Bei Fragen oder Problemen wende dich gerne an philipp@gen-em.org.\n\n"
                . "Viele Grüße\nGen-EM Einsatzdokumentation Notarzt\n",
                5);
        }
    } catch (Throwable $ex) {
        error_log('Hinweis auf getrenntes Geraet konnte nicht verschickt werden: ' . $ex->getMessage());
    }
    exit;
}

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

/* ---- Was fuer ein Geraet koppelt hier? (R42, S6) --------------------------
 *
 * Die Uhr sendet den Block seit 1.9.0, die Handy-App seit 0.2.0 — bis Web
 * 12.9.0 ist er hier ins Leere gelaufen. Gelesen wird er ueber
 * geraete_lib.php; die Datei kennt beide Formen (Teilenummer bei Garmin,
 * Hersteller/Modell beim Handy) und gibt IMMER drei Werte zurueck.
 *
 * DER TRY-BLOCK IST NICHT UEBERVORSICHTIG, SONDERN DIE ZUSAGE DES VERTRAGS:
 * "Eine Kopplung darf an einer Statistikangabe nie scheitern" (Abschnitt 1a).
 * geraet_block_lesen() ist so gebaut, dass es nicht wirft — aber die Zusage
 * soll nicht davon abhaengen, dass das auch nach der naechsten Aenderung noch
 * stimmt. Am anderen Ende steht eine Notaerztin, die eine Uhr koppeln will,
 * und drei NULL-Spalten sind ein besserer Ausgang als ein 500er.
 *
 * VOR DEM INSERT und nicht danach als UPDATE: Ein zweiter Schreibzugriff
 * koennte fehlschlagen, nachdem die Zugangsdaten bereits herausgegeben sind —
 * dann stuende das Geraet ohne Angabe da, und niemand erfuehre davon. */
try {
    $geraet = geraet_block_lesen($b['geraet'] ?? null);
} catch (Throwable $ex) {
    error_log('Geraeteangabe unlesbar, Kopplung laeuft weiter: ' . $ex->getMessage());
    $geraet = ['art' => null, 'modell' => null, 'teil' => null];
}

try {
    $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label,
                                        geraet_art, geraet_modell, geraet_teil)
                   VALUES (?,?,?,?,?,?,?)')
        ->execute([$ownerId, $devId,
                   password_hash($key, PASSWORD_DEFAULT),
                   /* Die Geraeteart als Name, OHNE Datum.
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
                    * gehoert der Zeile.
                    *
                    * BIS WEB 12.9.0 STAND HIER FEST 'Uhr'. Seit der Handy-App
                    * (S4) war das schlicht falsch: Ein frisch gekoppeltes
                    * Handy hiess in der Geraeteliste "Uhr". Jetzt folgt die
                    * Vorgabe der gemeldeten Art. */
                   geraet_vorgabename($geraet['art']),
                   $geraet['art'], $geraet['modell'], $geraet['teil']]);
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
            'Neues Gerät gekoppelt — Gen-EM Einsatzdokumentation Notarzt',
            "Hallo,\n\n"
            . "mit deinem Konto der Gen-EM Einsatzdokumentation Notarzt wurde soeben ein\n"
            . "neues Gerät gekoppelt:\n\n"
            . "  Gerät:     " . geraet_bezeichnung($geraet['art'], $geraet['modell'],
                                                        $geraet['teil']) . "\n"
            . "  Geräte-ID: " . $devId . "\n"
            . "  Zeitpunkt: " . fmt_local(gmdate('Y-m-d H:i:s'), 'd.m.Y H:i') . " Uhr\n\n"
            . "War das dein Gerät, ist alles in Ordnung — du musst nichts tun.\n\n"
            . "War es das nicht, deaktiviere oder lösche das Gerät bitte umgehend unter\n"
            . "Einstellungen → Geräte. Ab diesem Moment kann es keine Daten mehr hochladen.\n"
            . $CFG['app']['base_url'] . "/einstellungen.php?t=geraete\n\n"
            . "Bei Fragen oder Problemen wende dich gerne an philipp@gen-em.org.\n\n"
            . "Viele Grüße\nGen-EM Einsatzdokumentation Notarzt\n",
            5);
    }
} catch (Throwable $ex) {
    error_log('Hinweis auf neues Geraet konnte nicht verschickt werden: ' . $ex->getMessage());
}
