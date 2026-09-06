<?php
declare(strict_types=1);
/**
 * WEITERLEITUNG auf „Installation" (S8/AP3).
 *
 * Aus dieser Seite ist `admin_installation.php` geworden — Impressum und
 * Datenschutz stehen dort neben dem Logo der Installation, weil alle drei
 * dieselbe Frage beantworten: was zeigt diese Anlage nach aussen?
 *
 * DIE ADRESSE BLEIBT, WEIL SIE IN LESEZEICHEN STEHT. Ein 404 waere die
 * richtige Antwort auf eine Adresse, die es nie gab — nicht auf eine, die
 * jemand seit Web 9.11.0 benutzt. 302 und nicht 301: Eine dauerhafte
 * Weiterleitung merkt sich der Browser, und wer sie einmal falsch ausgeliefert
 * hat, bekommt sie nicht mehr aus den Zwischenspeichern heraus.
 */
require_once __DIR__ . '/auth_guard.php';
require_admin();
header('Location: admin_installation.php', true, 302);
exit;
