<?php
declare(strict_types=1);
/**
 * ANKUENDIGUNG SCHLIESSEN — das Kreuz im Streifen (P5c/AP1, E-P5c-13, -60).
 *
 * Nimmt nur POST mit Formular-Token und merkt sich in der Sitzung, dass
 * DIESE Ankuendigung geschlossen ist (`ankuendigung_wegklicken()`).
 *
 * OHNE `auth_guard.php`, und das ist der Zweck: Die Ankuendigung steht auch
 * auf der Anmeldeseite (E-P5c-60), und dort ist niemand angemeldet. Die
 * Wache wuerde das Schliessen dort mit einer Weiterleitung auf genau die
 * Seite beantworten, von der es kam. Mehr als eine Marke in der eigenen
 * Sitzung kann hier niemand setzen — ein Angreifer, der das Token haette,
 * koennte einer NutzerIn einen Streifen ausblenden, und nichts sonst.
 *
 * ZWEI ANTWORTEN. `assets/ankuendigung.js` schickt `antwort=json` und
 * bekommt JSON; das Formular ohne Skript bekommt eine Weiterleitung zurueck
 * auf die Seite, von der es kam (303, damit ein Neuladen nicht erneut
 * sendet). Das Ziel ist nur ein Seitenname dieser Anwendung — eine freie
 * Adresse waere eine offene Weiterleitung.
 */
if (!file_exists(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_lib.php';
require_once __DIR__ . '/ankuendigung_lib.php';

https_tor();
sitzung_starten('app');

/* Ein GET ist ein Aufruf aus der Adresszeile — er landet auf der Startseite
 * (und von dort, ohne Anmeldung, auf der Anmeldeseite). JSON gibt es nur
 * auf ein POST hin; das Skript sendet nie anders. */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: index.php', true, 303);
    exit;
}
$alsJson = ($_POST['antwort'] ?? '') === 'json';
if (!csrf_ok()) {
    if ($alsJson) { json_out(['error' => 'csrf'], 403); }
    require_once __DIR__ . '/ui.php';
    ui_abbruch(403, 'Ungültiges Formular-Token.');
}

ankuendigung_wegklicken();

if ($alsJson) { json_out(['ok' => true]); }

$ziel = (string)($_POST['zurueck'] ?? '');
if (!preg_match('~^[a-z0-9_]+\.php(\?[^\s#]*)?$~', $ziel)) { $ziel = 'index.php'; }
header('Location: ' . $ziel, true, 303);
exit;
