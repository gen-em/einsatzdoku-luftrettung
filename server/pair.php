<?php
declare(strict_types=1);
/**
 * Geraete-Kopplung — VIER Anliegen an einem Endpunkt (JSON-Vertrag 1a und 1b).
 *
 * Alle per POST mit JSON-Rumpf und Pflichtfeld "aktion":
 *
 *   start        OHNE Kopfzeilen. {"aktion":"start","geraet":{...}}
 *                -> 200 {"code","device_id","api_key","frist_s"}
 *                Das Geraet bittet um eine Kopplungssitzung. Es bekommt den
 *                ANZEIGECODE fuer den Menschen und die ZUGANGSDATEN, die es ab
 *                jetzt traegt, aber erst nach dem Ja benutzen darf.
 *   status       mit X-Device-Id / X-Api-Key. {"aktion":"status"}
 *                -> 200 {"zustand":"offen"|"beansprucht"|"gekoppelt", ...}
 *   bestaetigen  mit Kopfzeilen. {"aktion":"bestaetigen","antwort":"ja"|"nein"}
 *                -> 200 {"ok":true}. Nach Ja gibt es das Geraet, nach Nein die
 *                Sitzung nicht mehr.
 *   trennen      mit Kopfzeilen. {"aktion":"trennen"} -> 200 {"ok":true}
 *                Die Uhr gibt ihre Kopplung zurueck, das Geraet wird geloescht
 *                (seit Web 9.15.0, Backlog Nr. 14 — Begruendung am Zweig).
 *
 * DER ABLAUF HAT SICH MIT WEB 13.0.0 UMGEDREHT (S5; R49, E-R49-1 bis -8). Bis
 * 12.9.4 erzeugte das Web den Code, und die Uhr tippte ihn — sechs Zeichen
 * ueber einen TextPicker am Handgelenk. Jetzt ZEIGT das Geraet den Code, ein
 * Mensch tippt ihn im Browser, und das Geraet hat das letzte Wort: Es sieht
 * die maskierte Adresse des Kontos, das seinen Code eingegeben hat, und sagt
 * Ja oder Nein. Zwei Angriffsflaechen, zwei Tore (E-S5-05): Ein fremdes
 * Geraet im eigenen Konto scheitert an der Bestaetigungsseite im Web (sie
 * zeigt Art und Modell); das eigene Geraet im fremden Konto scheitert am Ja
 * auf dem Geraet (die falsche Adresse faellt auf).
 *
 * DER CODE IST NUR FUER DEN MENSCHEN (E-S5-03). Er weist nirgends etwas aus;
 * wer ihn abliest, kann am Geraet nichts ausloesen. Was das Geraet ausweist,
 * sind Kennung und Schluessel aus `start` — und die sind bis zum Ja
 * SCHWEBEND (E-R49-2): Sie stehen in pair_sessions, nicht in devices, und
 * ingest.php weist sie ab. Ohne Bestaetigung sind sie wertlos.
 *
 * Dieser Endpunkt ist OHNE ANMELDUNG erreichbar. Was ihn schuetzt, in der
 * Reihenfolge, in der es greift — jede Bremse VOR der Arbeit, die sie begrenzt:
 *
 *   1. Ratenschutz je Anliegen (ratelimit_lib.php, E-S5-16): `start` zaehlt
 *      JEDE Anfrage je IP (Topf pair_start); status, bestaetigen und trennen
 *      zaehlen 401 je IP (Topf pair). Die Code-Eingabe im Web hat ihren
 *      eigenen Topf (pair_code, einstellungen.php).
 *   2. Obergrenze offener Sitzungen (PAIR_SITZUNGEN_MAX, db.php), per SQL
 *      ueber unverfallene Zeilen (E-S5-14). `start` raeumt nichts vorab auf.
 *   3. Genug Moeglichkeiten (PAIR_LEN aus PAIR_CHARS) und eine kurze Frist
 *      (PAIR_TTL_MIN), beides db.php — EINE Frist ab `start` fuer alles.
 *   4. Die Rueckbestaetigung am Geraet: das Tor, in das ein geratener Code
 *      immer noch laeuft.
 *   5. Geraetelimit (MAX_GERAETE) beim Ja und die Kopplungsmail danach. Beides
 *      aendert nichts an der Wahrscheinlichkeit, aber viel daran, wie lange
 *      ein fremdes Geraet unbemerkt bliebe.
 *
 * KONSTANTE ANTWORTDAUER (E-S5-31): Jeder Zweig, der einem Fremden etwas
 * sagen koennte, endet ueber abweisen() -> rate_gleiche_dauer(). Der
 * unbekannte Zweig rechnet gegen GERAET_VERGLEICHSWERT dieselben Schritte
 * wie der bekannte; die Ruempfe fuer „Kennung unbekannt“ und „Schluessel
 * falsch“ sind byteweise gleich. 410 (abgelaufen) und 409 (nicht beansprucht,
 * Geraetelimit) kommen OHNE Verzoegerung: Sie setzen die richtige Kennung UND
 * den richtigen Schluessel voraus und sagen einem Fremden nichts.
 *
 * WAS ZAEHLT UND WAS NICHT (E-S5-17): 401 zaehlt im Topf pair. Ein Rumpf ohne
 * oder mit unbekannter Aktion zaehlt nicht — das ist eine alte Uhr, kein
 * Raten. 410 und 409 zaehlen nicht („der Code war richtig, hier ist niemand
 * am Raten“). Und rate_erfolg() ruft nur `trennen`, wie bisher: Ein
 * gelungenes `status` darf den Zaehler NICHT leeren — sonst setzte ein
 * Angreifer mit einer eigenen, gueltigen Sitzung alle fuenf Sekunden den
 * IP-Zaehler zurueck, waehrend er daneben fremde Kennungen durchprobiert.
 *
 * SCHLUESSEL LIEGEN ALS SHA-256 (E-S5-41, E-S5-42; db.php erklaert die
 * Verfahrenswahl): 24 Zufallsbytes brauchen kein bcrypt, und 120
 * status-Abfragen je Sitzung haetten bei bcrypt rund 27 s Rechenzeit
 * gekostet. Der Klartext des Schluessels wird GENAU EINMAL uebertragen — in
 * der Antwort auf `start` — und steht in keinem Protokoll: Die catch-Zweige
 * loggen die Ausnahme, nie den Rumpf.
 *
 * EINE ALTE UHR (2.0.0 sendet {"code": ...}) bekommt 400 mit der Meldung
 * „Uhr-App aktualisieren“ — der einzige Kanal, auf dem sie erfaehrt, was zu
 * tun ist (E-S5-19). Eine Uebergangszeit mit beiden Wegen gibt es nicht
 * (E-R49-7): Der alte setzte einen im Web erzeugten Code voraus, und den gibt
 * es nicht mehr. Bestehende Kopplungen sind davon nicht beruehrt.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ratelimit_lib.php';
require_once __DIR__ . '/smtp.php';
require_once __DIR__ . '/geraete_lib.php';
require_once __DIR__ . '/kopplung_lib.php';

header('Content-Type: application/json; charset=utf-8');

// Zeitpunkt fuer die konstante Antwortdauer. Jeder Fehlerzweig, der etwas
// ueber Fremdes aussagen koennte, endet ueber abweisen(), damit ein Angreifer
// aus der Dauer nicht ablesen kann, WIE weit er gekommen ist.
$t0 = microtime(true);

function abweisen(int $status, string $fehler, bool $zaehlen = true, array $mehr = []): never
{
    global $t0;
    if ($zaehlen) { rate_misserfolg('pair'); }
    rate_gleiche_dauer($t0);
    http_response_code($status);
    echo json_encode(['error' => $fehler] + $mehr);
    exit;
}

/** Antwort OHNE Verzoegerung — fuer Zweige, die einem Fremden nichts sagen (E-S5-31). */
function antworten(int $status, array $rumpf): never
{
    http_response_code($status);
    echo json_encode($rumpf);
    exit;
}

/** 429 — mit Verzoegerung, damit eine Sperre die Schleife eines Angreifers mitbremst. */
function gesperrt(string $fehler, string $meldung): never
{
    global $t0;
    rate_gleiche_dauer($t0);
    antworten(429, ['error' => $fehler, 'meldung' => $meldung]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { abweisen(405, 'method', false); }

$b      = json_decode((string)file_get_contents('php://input'), true);
$aktion = is_array($b) ? (string)($b['aktion'] ?? '') : '';

/* =========================================================================
 * start — das Geraet bittet um eine Sitzung (1a.1)
 * ========================================================================= */
if ($aktion === 'start') {
    // Sperre VOR jeder weiteren Arbeit; dann zaehlt die Anfrage — auch die,
    // die gleich an der Obergrenze scheitert.
    if (!rate_erlaubt('pair_start')) {
        gesperrt('zu_viele_versuche', 'Zu viele Kopplungsversuche. Bitte spaeter erneut.');
    }
    rate_zaehlen('pair_start');

    $pdo = db();
    if (pair_sitzungen_offen($pdo) >= PAIR_SITZUNGEN_MAX) {
        gesperrt('zu_viele_sitzungen', 'Der Server ist gerade ausgelastet. Bitte spaeter erneut.');
    }

    /* ---- Was fuer ein Geraet koppelt hier? (R42, S6) ----------------------
     *
     * Der Block wird HIER gelesen und aufgeloest und liegt bis zum Ja in der
     * Sitzung; bestaetigen uebernimmt die drei Werte von dort in die
     * devices-Zeile (Vertrag 1a.5). Die Kontoinhaberin sieht Art und Modell
     * auf der Bestaetigungsseite — ein Geraet, das nichts ueber sich sagt,
     * erscheint dort als „Geraet unbekannt“.
     *
     * DER TRY-BLOCK IST DIE ZUSAGE DES VERTRAGS: „Eine Kopplung darf an einer
     * Statistikangabe nie scheitern.“ geraet_block_lesen() wirft nicht — aber
     * die Zusage soll nicht davon abhaengen, dass das nach der naechsten
     * Aenderung noch stimmt. */
    try {
        $geraet = geraet_block_lesen(is_array($b) ? ($b['geraet'] ?? null) : null);
    } catch (Throwable $ex) {
        error_log('Geraeteangabe unlesbar, Kopplung laeuft weiter: ' . $ex->getMessage());
        $geraet = ['art' => null, 'modell' => null, 'teil' => null];
    }

    /* ---- Kennung 128 Bit (M4-08), Schluessel 192 Bit ------------------------
     *
     * Die Kennung ist KEIN Geheimnis — sie steht im Kopf jeder Anfrage; die
     * Berechtigung haengt am Schluessel. Sechzehn Bytes machen ein zufaelliges
     * Zusammentreffen zweier Geraete so unwahrscheinlich, dass es praktisch
     * nicht vorkommt (Geburtstagsproblem bei vier Bytes: 50 % bei einigen
     * zehntausend Geraeten). Der Schluessel geht genau einmal ueber die
     * Leitung, jetzt; gespeichert wird sein SHA-256. */
    $devId = 'dev-' . bin2hex(random_bytes(16));
    $key   = bin2hex(random_bytes(24));

    try {
        $code = pair_sitzung_anlegen($pdo, $devId, geraet_schluessel_hash($key), $geraet);
    } catch (Throwable $ex) {
        error_log('Kopplungssitzung konnte nicht angelegt werden: ' . $ex->getMessage());
        antworten(500, ['error' => 'server']);
    }

    antworten(200, ['code' => $code, 'device_id' => $devId, 'api_key' => $key,
                    'frist_s' => PAIR_TTL_MIN * 60]);
}

/* =========================================================================
 * status, bestaetigen, trennen — mit Kopfzeilen ausgewiesen (1a.2, 1a.3, 1b)
 * ========================================================================= */
if (!rate_erlaubt('pair')) {
    gesperrt('zu_viele_versuche', 'Zu viele Kopplungsversuche. Bitte spaeter erneut.');
}
if (!in_array($aktion, ['status', 'bestaetigen', 'trennen'], true)) {
    // Ohne oder mit unbekannter Aktion: eine alte Uhr (E-S5-19) oder Unsinn.
    // Zaehlt nicht — hier raet niemand. Die Meldung hat 21 Zeichen und passt
    // in die Hinweiszeile der Uhr (ZEILE_MAX 26).
    abweisen(400, 'aktion', false, ['meldung' => 'Uhr-App aktualisieren']);
}

$devId  = (string)($_SERVER['HTTP_X_DEVICE_ID'] ?? '');
$apiKey = (string)($_SERVER['HTTP_X_API_KEY']   ?? '');
$antwort = is_array($b) ? (string)($b['antwort'] ?? '') : '';
$pdo = db();

/* ---- Erst die Sitzung, dann das Geraet (E-S5-15) --------------------------
 *
 * Eine Kennung steht entweder in pair_sessions (schwebend) oder in devices
 * (gekoppelt) — nie in beiden: bestaetigen legt das Geraet an und loescht die
 * Sitzung in EINER Transaktion. Trifft ein wiederholtes Ja oder ein status
 * ein bereits angelegtes Geraet (die Antwort ging auf dem Rueckweg verloren,
 * die Uhr wiederholt), antwortet der Geraete-Zweig unten „gekoppelt“ bzw.
 * ok — sonst stuende ein Geraet im Konto, von dem die Uhr nichts weiss, und
 * die Kopplung hinge an einem einzigen Funkpaket. */
$sitzung = $devId !== '' ? pair_sitzung_nach_kennung($pdo, $devId) : null;

if ($sitzung !== null) {
    if (!geraet_schluessel_gueltig($apiKey, (string)$sitzung['api_key_hash'])) {
        abweisen(401, 'auth');
    }
    $rest = (int)$sitzung['rest_s'];
    $sid  = (int)$sitzung['id'];

    if ($aktion === 'status') {
        if ($rest <= 0) { antworten(410, ['error' => 'abgelaufen']); }
        if ($sitzung['user_id'] === null) {
            antworten(200, ['zustand' => 'offen', 'rest_s' => $rest]);
        }
        antworten(200, ['zustand' => 'beansprucht',
                        'konto'   => email_maskieren((string)$sitzung['konto_email']),
                        'rest_s'  => $rest]);
    }

    if ($aktion === 'bestaetigen' && !in_array($antwort, ['ja', 'nein'], true)) {
        abweisen(400, 'payload', false);
    }

    /* Nein — in JEDEM Zustand, auch verfallen: So bricht ein Geraet ab, das
     * zurueck auf die Sync-Seite geht (E-S5-23). Ein `trennen` mit schwebenden
     * Zugangsdaten wirkt genauso: Es gibt kein Geraet, das sich trennen
     * liesse, nur eine Sitzung, die dann niemand mehr braucht. */
    if ($aktion === 'trennen' || $antwort === 'nein') {
        $pdo->prepare('DELETE FROM pair_sessions WHERE id = ?')->execute([$sid]);
        antworten(200, ['ok' => true]);
    }

    // Ja
    if ($rest <= 0) { antworten(410, ['error' => 'abgelaufen']); }
    if ($sitzung['user_id'] === null) { antworten(409, ['error' => 'nicht_beansprucht']); }

    /* ---- Das Geraet entsteht: eine Transaktion (E-S5-13) --------------------
     *
     * SELECT ... FOR UPDATE sperrt die Zeile; ein zweites Ja fuer dieselbe
     * Sitzung wartet und findet sie dann nicht mehr — es faellt unten in den
     * Geraete-Zweig und bekommt ok, weil das Geraet inzwischen existiert.
     * Anlegen und Loeschen stehen in EINEM Commit: Scheitert eines, bleibt die
     * Sitzung bestehen, das Geraet bekommt 500 und darf wiederholen.
     *
     * DAS GERAETELIMIT WIRD HIER NOCH EINMAL GEPRUEFT (E-S5-18), obwohl das
     * Web es beim Eingeben schon getan hat: Zwischen Klick und Ja kann ein
     * Geraet von Hand dazugekommen sein. Beanspruchte, unbestaetigte Sitzungen
     * zaehlen nicht mit — zwei gleichzeitige Kopplungen enden deterministisch
     * mit einem 409 bei der zweiten Bestaetigung. Die Sitzung ist danach weg;
     * der Weg zurueck ist ein Geraet loeschen und neu beginnen. */
    $verschwunden = false;
    $frisch = null;
    try {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT * FROM pair_sessions WHERE id = ? FOR UPDATE');
        $st->execute([$sid]);
        $frisch = $st->fetch();
        if (!$frisch) {
            $pdo->rollBack();
            $verschwunden = true;
        } elseif ($frisch['user_id'] === null) {
            // Zwischen Lesen und Sperren aus der Hand gegeben? Gibt es nicht —
            // user_id wird nie zurueckgesetzt. Trotzdem der sichere Ausgang.
            $pdo->rollBack();
            antworten(409, ['error' => 'nicht_beansprucht']);
        } else {
            $ownerId = (int)$frisch['user_id'];
            if (geraete_grenze_erreicht($pdo, $ownerId)) {
                $pdo->prepare('DELETE FROM pair_sessions WHERE id = ?')->execute([$sid]);
                $pdo->commit();
                antworten(409, ['error'   => 'device_limit',
                                'meldung' => 'Es sind bereits ' . MAX_GERAETE . ' Geraete mit diesem '
                                           . 'Konto verbunden. Erst eines loeschen, dann neu koppeln.']);
            }
            $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label,
                                                geraet_art, geraet_modell, geraet_teil)
                           VALUES (?,?,?,?,?,?,?)')
                ->execute([$ownerId, (string)$frisch['device_id'], (string)$frisch['api_key_hash'],
                           /* Vorgabename nach der gemeldeten Art, OHNE Datum — das
                            * Datum gehoert der Zeile (devices.created_at). */
                           geraet_vorgabename($frisch['geraet_art']),
                           $frisch['geraet_art'], $frisch['geraet_modell'], $frisch['geraet_teil']]);
            $pdo->prepare('DELETE FROM pair_sessions WHERE id = ?')->execute([$sid]);
            $pdo->commit();
        }
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log('Kopplung fehlgeschlagen: ' . $ex->getMessage());
        antworten(500, ['error' => 'server']);
    }

    if (!$verschwunden) {
        echo json_encode(['ok' => true]);

        /* ---- Den Kontoinhaber benachrichtigen (M4-10, E-S5-20) ---------------
         *
         * Dies ist die Stelle, an der ein Geraet zum Konto DAZUKOMMT — und die
         * einzige, an der die betroffene Person es erfahren kann, ohne sich
         * zufaellig anzumelden. Beim Klick im Web gab es das Geraet noch nicht,
         * und ein Nein haette die Mail falsch gemacht; deshalb hier.
         *
         * ERST ANTWORTEN, DANN VERSENDEN: Die Uhr wartet auf diese Antwort.
         * Bricht sie wegen eines langsamen Mailservers ab, wiederholt sie das
         * Ja — und bekommt ok aus dem Geraete-Zweig. Ein Fehlschlag des
         * Versands beruehrt die Kopplung nicht; er landet im Fehlerprotokoll. */
        antwort_abschliessen();
        try {
            $ust = $pdo->prepare('SELECT email FROM users WHERE id = ?');
            $ust->execute([(int)$frisch['user_id']]);
            $mail = $ust->fetchColumn();
            if ($mail !== false && $mail !== null && $mail !== '') {
                smtp_send((string)$mail,
                    'Neues Gerät gekoppelt — Gen-EM Einsatzdokumentation Notarzt',
                    "Hallo,\n\n"
                    . "mit deinem Konto der Gen-EM Einsatzdokumentation Notarzt wurde soeben ein\n"
                    . "neues Gerät gekoppelt. Das Gerät hat den Code gezeigt, du hast ihn im Web\n"
                    . "eingegeben und am Gerät mit Ja bestätigt:\n\n"
                    . "  Gerät:     " . geraet_bezeichnung($frisch['geraet_art'], $frisch['geraet_modell'],
                                                                $frisch['geraet_teil']) . "\n"
                    . "  Geräte-ID: " . (string)$frisch['device_id'] . "\n"
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
        exit;
    }
    // verschwunden: weiter unten im Geraete-Zweig — das Geraet gibt es jetzt.
}

/* ---- Der Geraete-Zweig: gekoppelte Zugangsdaten ---------------------------
 *
 * ANTWORTZEIT wie in ingest.php: Der unbekannte Zweig laeuft gegen
 * GERAET_VERGLEICHSWERT, damit aus der Dauer nicht ablesbar ist, welche
 * Geraetekennungen es gibt — die Kennung ist die Haelfte dessen, was ein
 * Upload braucht. */
$st = $pdo->prepare('SELECT id, user_id, api_key_hash FROM devices WHERE device_id = ?');
$st->execute([$devId]);
$dev = $st->fetch();
if (!$dev) {
    geraet_schluessel_gueltig($apiKey, GERAET_VERGLEICHSWERT);
    abweisen(401, 'auth');
}
if (!geraet_schluessel_gueltig($apiKey, (string)$dev['api_key_hash'])) {
    abweisen(401, 'auth');
}

if ($aktion === 'status') { antworten(200, ['zustand' => 'gekoppelt']); }

if ($aktion === 'bestaetigen') {
    if (!in_array($antwort, ['ja', 'nein'], true)) { abweisen(400, 'payload', false); }
    /* Ja: Das Geraet gibt es schon — die Antwort auf das erste Ja ging
     * verloren (Idempotenz, E-S5-15). Nein: Es gibt keine Sitzung mehr, die
     * sich verwerfen liesse; das Geraet bleibt stehen. Das ist die sichere
     * Richtung: Ein Nein, das ein fertiges Geraet loeschte, waere ein Trennen
     * ohne Trennen-Mail (E-S5-48). */
    antworten(200, ['ok' => true]);
}

/* ---- Trennen: die Uhr gibt ihre Kopplung zurueck (Backlog Nr. 14) ---------
 *
 * WOZU. Eine geteilt genutzte Uhr wechselt die Person. Bis Web 9.15.0 gab es
 * dafuer nur den Weg „neu koppeln“: Gelang das nicht, dokumentierte die Uhr
 * stillschweigend weiter auf das VORHERIGE Konto — niemand sah es ihr an. Die
 * Uhr trennt sich jetzt zuerst ausdruecklich, und erst danach koppelt sie neu.
 *
 * ES WIRD GELOESCHT, NICHT DEAKTIVIERT. Ein deaktiviertes Geraet belegt
 * weiter einen der MAX_GERAETE Plaetze — und „zu viele Geraete“ ist genau der
 * Fehler, in den eine geteilte Uhr sonst laeuft. Der Fremdschluessel setzt
 * device_id in Einsaetzen und Segmenten auf NULL; bereits hochgeladene Daten
 * bleiben unberuehrt (dieselbe Wirkung wie beim Loeschen im Web).
 */
try {
    $pdo->prepare('DELETE FROM devices WHERE id = ?')->execute([(int)$dev['id']]);
} catch (Throwable $ex) {
    error_log('Trennen fehlgeschlagen: ' . $ex->getMessage());
    antworten(500, ['error' => 'server']);
}

rate_erfolg('pair');
echo json_encode(['ok' => true]);

/* Den Kontoinhaber unterrichten — dieselbe Ueberlegung wie beim Koppeln: Es
 * ist die eine Gelegenheit, es zu erfahren, ohne sich zufaellig anzumelden.
 * Erst antworten, dann versenden; ein Fehlschlag des Versands darf die
 * Trennung nicht beruehren, sie ist abgeschlossen. */
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
