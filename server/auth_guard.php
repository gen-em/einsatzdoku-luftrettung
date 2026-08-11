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
$userId   = (int)$_SESSION['user_id'];
$userRole = (string)($_SESSION['role'] ?? 'user');

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function require_admin(): void {
    global $userRole;
    if ($userRole !== 'admin') { http_response_code(403); exit('Kein Zugriff.'); }
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
}

function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403); exit('Ungültiges Formular-Token.');
    }
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
    require_once __DIR__ . '/session_lib.php';
    session_beenden('abgelaufen');
}
$_SESSION['last_seen'] = time();

// Anzeigename fuer die Kopfleiste (name-Spalte existiert erst nach Migration)
$u = db()->prepare('SELECT * FROM users WHERE id = ?');
$u->execute([$userId]);
$row = $u->fetch();
$userEmail = $row ? (string)$row['email'] : '';
$userName  = ($row && isset($row['name'])) ? $row['name'] : null;

// Pflicht-Verschlüsselung: aktiv, sobald der Inhaltsschluessel verpackt
// vorliegt. Seit Web 2.7.0 entstehen Passwort und beide Huellen gemeinsam in
// pw_handling.php — ein anmeldbares Konto ohne Huelle kann es nicht mehr
// geben, deshalb entfaellt die frueher hier erzwungene Ersteinrichtung.
$patWrapPw = ($row && isset($row['pat_wrap_pw'])) ? $row['pat_wrap_pw'] : null;
$patReady  = $patWrapPw !== null;
// Pruefsumme des Inhaltsschluessels (NULL bei Konten aus der Zeit vor Web
// 4.0.0 — ein gueltiger Zustand, siehe M1-12).
$patKeyCheck = ($row && isset($row['pat_key_check'])) ? $row['pat_key_check'] : null;
$kdfSalt   = ($row && isset($row['kdf_salt'])) ? $row['kdf_salt'] : null;

require_once __DIR__ . '/ui.php';

run_cleanup_if_due();   // taegliche Wartung, huckepack auf Web-Anfragen
