<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_lib.php';

/**
 * Abmelden.
 *
 * Die eigentliche Arbeit steckt in session_lib.php: PHP-Sitzung beenden UND
 * die Schluessel im sessionStorage des Browsers raeumen (Daten- und
 * Inhaltsschluessel). Eine reine Weiterleitung per Kopfzeile fuehrt nie
 * JavaScript aus, deshalb wird dafuer eine kurze Seite ausgeliefert.
 *
 * Diese Datei ist nur noch der Einstieg — den zweiten Weg (Ablauf der Frist in
 * auth_guard.php) hatte man frueher leicht uebersehen, weil die Loesung
 * ausschliesslich hier stand.
 */
session_beenden('abgemeldet');
