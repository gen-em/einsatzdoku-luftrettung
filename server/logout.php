<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Abmelden.
 *
 * Neben der PHP-Sitzung muessen auch die Schluessel im sessionStorage des
 * Browsers verschwinden (Daten-Schluessel und Inhaltsschluessel). Frueher
 * blieben sie liegen: Die Weiterleitung geschah per HTTP-Header, es lief also
 * nie JavaScript. Deshalb wird hier eine kurze Seite ausgeliefert, die
 * EdCrypto.clearSession() aufruft und danach zur Anmeldung wechselt.
 */

session_set_cookie_params(['httponly' => true, 'secure' => true, 'samesite' => 'Strict', 'path' => '/']);
session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Abmelden — Einsatzdoku</title>
<noscript><meta http-equiv="refresh" content="0;url=login.php"></noscript>
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?>
</head>
<body class="login-body">
<main class="login-card">
  <p class="muted">Du wirst abgemeldet …</p>
  <p class="login-aux"><a href="login.php">Zur Anmeldung</a></p>
</main>
<script src="<?= asset('assets/crypto.js') ?>"></script>
<script>
try { EdCrypto.clearSession(); } catch (e) { /* Skript blockiert — Seite bleibt nutzbar */ }
location.replace('login.php');
</script>
</body>
</html>
