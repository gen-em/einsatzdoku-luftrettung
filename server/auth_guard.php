<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

session_set_cookie_params([
    'httponly' => true, 'secure' => true, 'samesite' => 'Strict', 'path' => '/',
]);
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int)$_SESSION['user_id'];

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

/**
 * Ist der Aufruf ein Datenabruf des Browser-Skripts (server/api/...)?
 *
 * Gebraucht, wenn die Sitzung MITTEN in einer Anfrage endet. session_beenden()
 * liefert eine HTML-Seite aus — die raeumt die Schluessel im Browser und ist
 * fuer eine Seitenanfrage genau richtig. Ein fetch() aus dem Skript bekommt
 * damit aber HTML, wo es JSON erwartet: Der Aufrufer sieht einen Syntaxfehler
 * beim Auswerten und meldet irgendetwas Allgemeines statt "die Sitzung ist
 * beendet".
 *
 * Fuer diese Aufrufe gibt es deshalb 401 mit JSON und einem lesbaren Grund.
 * Das Raeumen der Schluessel uebernimmt die naechste Seitenanfrage, die
 * ohnehin auf der Anmeldeseite landet.
 */
function ist_api_aufruf(): bool {
    $pfad = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    return str_contains($pfad, '/api/');
}

/** Sitzung beenden — als JSON, wenn das Gegenueber JSON erwartet. */
function sitzung_beenden_passend(string $grund): never {
    require_once __DIR__ . '/session_lib.php';
    if (ist_api_aufruf()) {
        session_verwerfen();
        json_out(['error'   => 'session_ende',
                  'grund'   => $grund,
                  'meldung' => session_ende_text($grund)], 401);
    }
    session_beenden($grund);
}

// Inaktivitaets-Timeout: nach 30 Minuten ohne Anfrage neu anmelden
const SESSION_TIMEOUT_S = 1800;
if (isset($_SESSION['last_seen']) && (time() - (int)$_SESSION['last_seen']) > SESSION_TIMEOUT_S) {
    /* ABGELAUFENE SITZUNG: ueber den gemeinsamen Weg beenden.
     *
     * Frueher stand hier eine reine Weiterleitung per Kopfzeile. Die fuehrt
     * NIE JavaScript aus — Daten- und Inhaltsschluessel blieben also im
     * sessionStorage des Tabs liegen, obwohl die Sitzung abgelaufen war. Wer
     * seinen Rechner nach der Frist stehen laesst, hatte eine abgelaufene
     * Sitzung und einen liegengebliebenen Schluessel.
     *
     * Der Abmeldeweg loeste dasselbe Problem bereits richtig; session_lib.php
     * ist die eine Fassung fuer beide, damit sie nicht wieder auseinander-
     * laufen. Sie nennt ausserdem den GRUND: Der frueher angehaengte
     * Parameter ?timeout=1 wurde von der Anmeldeseite gar nicht ausgewertet.
     */
    sitzung_beenden_passend('abgelaufen');
}
$_SESSION['last_seen'] = time();

/* ---- Die Nutzerzeile ist die Wahrheit, nicht die Sitzung (M1-05) ----------
 *
 * Die Rolle wurde bei der Anmeldung EINMAL in die Sitzung geschrieben und nie
 * wieder geprueft. Zwei Folgen, beide unbegrenzt haltbar, solange die Sitzung
 * offen blieb:
 *
 *   - Wem die Administratorrolle entzogen wird, behaelt seine Rechte.
 *   - Wessen Konto geloescht wird, bleibt angemeldet und arbeitet weiter.
 *     Die Einsaetze sind ueber die Fremdschluesselkaskade zwar fort, die
 *     Oberflaeche merkt davon aber nichts.
 *
 * Die Zeile wurde OHNEHIN bei jeder Anfrage gelesen — nur eben erst weiter
 * unten und nur fuer den Anzeigenamen. Sie wandert deshalb hierher: Sie kostet
 * keine zusaetzliche Abfrage, und die Sitzungskopie der Rolle entfaellt
 * ersatzlos.
 */
/* Spalten benennen statt SELECT * (M1-20).
 *
 * Diese Abfrage laeuft bei JEDER Anfrage. Mit * kam die ganze Zeile ins
 * Gedaechtnis des Prozesses, darunter password_hash — der Hash des
 * Anmeldetokens, der hier nirgends gebraucht wird. Ein Speicherabbild, ein
 * Fehlerbericht mit vollem Kontext oder ein var_dump beim Suchen enthielt ihn
 * damit ebenfalls, und zwar auf jeder einzelnen Seite.
 *
 * Der zweite Grund ist Lesbarkeit: Was diese Datei aus der Nutzerzeile
 * braucht, steht jetzt hier und nicht verteilt in acht Zugriffen weiter
 * unten. Kommt eine Spalte hinzu, wandert sie nicht mehr automatisch mit.
 *
 * (name existiert seit der Migration von Web 2.x; wer die nicht gefahren hat,
 * kann sich schon heute nicht anmelden — die Spalte wird in ui.php gelesen.) */
$u = db()->prepare('SELECT id, email, name, role, session_epoch,
                           pat_wrap_pw, pat_key_check, kdf_salt
                    FROM users WHERE id = ?');
$u->execute([$userId]);
$row = $u->fetch();

if (!$row) {
    // Konto existiert nicht mehr. Nicht bloss "Kein Zugriff" melden und die
    // Sitzung stehen lassen — dann klickte man sich weiter durch eine
    // Anwendung, die einem nicht mehr gehoert.
    sitzung_beenden_passend('konto');
}

/* ---- Sitzungszaehler: Passwortwechsel beendet andere Sitzungen (M1-09/D6) --
 *
 * Wer sein Passwort wechselt, WEIL er Missbrauch vermutet, will genau eines
 * erreichen: dass der andere draussen ist. Bisher erreichte er das nicht — die
 * offene Sitzung des Angreifers lief unbeeindruckt weiter, denn sie haengt am
 * Sitzungscookie und nicht am Passwort.
 *
 * users.session_epoch (S3, seit P0 im Schema) wird bei jedem Passwortwechsel
 * erhoeht. Jede Anfrage vergleicht ihren mitgefuehrten Stand dagegen; wer noch
 * den alten hat, fliegt hier heraus.
 *
 * Die Sitzung, die den Wechsel selbst ausloest, zieht ihren Stand mit (siehe
 * einstellungen.php) und bleibt bestehen. Abnahmekriterium A5 sagt "alle
 * ANDEREN Sitzungen"; die handelnde Person mitten im eigenen Vorgang
 * abzumelden waere kein Sicherheitsgewinn, sondern nur laestig.
 *
 * Sitzungen aus der Zeit vor dieser Fassung fuehren den Wert noch nicht mit.
 * Sie werden uebernommen, statt beim Aufspielen alle Angemeldeten auszusperren
 * — der Stand wird beim ersten Zugriff aus der Zeile nachgetragen.
 */
$epocheDb = (int)($row['session_epoch'] ?? 0);
if (!isset($_SESSION['epoch'])) {
    $_SESSION['epoch'] = $epocheDb;
} elseif ((int)$_SESSION['epoch'] !== $epocheDb) {
    sitzung_beenden_passend('passwort');
}

/* ---- Rolle: aus der Zeile, nicht aus der Sitzung -------------------------- */
$userRole = (($row['role'] ?? 'user') === 'admin') ? 'admin' : 'user';

/**
 * Die EINE Rollenpruefung (M1-15).
 *
 * Vorher gab es drei Schreibweisen fuer dieselbe Frage: require_admin() in
 * drei Dateien, eine handgeschriebene Pruefung mit eigener Meldung in
 * admin_user.php und zwei Vergleiche in ui.php fuer die Anzeige. Eine
 * Rollenpruefung, die an fuenf Stellen unabhaengig formuliert ist, wird beim
 * naechsten Zusatz — etwa einer dritten Rolle — an vier Stellen richtig
 * geaendert.
 */
function ist_admin(): bool {
    global $userRole;
    return $userRole === 'admin';
}

function require_admin(): void {
    if (!ist_admin()) {
        if (ist_api_aufruf()) { json_out(['error' => 'forbidden'], 403); }
        http_response_code(403);
        exit('Kein Zugriff.');
    }
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403); exit('Ungültiges Formular-Token.');
    }
}

// Anzeigename fuer die Kopfleiste (name-Spalte existiert erst nach Migration)
$userEmail = (string)$row['email'];
$userName  = $row['name'] ?? null;

// Pflicht-Verschlüsselung: aktiv, sobald der Inhaltsschluessel verpackt
// vorliegt. Seit Web 2.7.0 entstehen Passwort und beide Huellen gemeinsam in
// pw_handling.php — ein anmeldbares Konto ohne Huelle kann es nicht mehr
// geben, deshalb entfaellt die frueher hier erzwungene Ersteinrichtung.
$patWrapPw = $row['pat_wrap_pw'] ?? null;
$patReady  = $patWrapPw !== null;
// Pruefsumme des Inhaltsschluessels (NULL bei Konten aus der Zeit vor Web
// 4.0.0 — ein gueltiger Zustand, siehe M1-12).
$patKeyCheck = $row['pat_key_check'] ?? null;
$kdfSalt     = $row['kdf_salt'] ?? null;

require_once __DIR__ . '/ui.php';

run_cleanup_if_due();   // taegliche Wartung, huckepack auf Web-Anfragen
