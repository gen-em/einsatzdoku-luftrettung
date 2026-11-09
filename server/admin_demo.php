<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();

/**
 * Demo-Konto — nur noch eine Umleitung (seit Web 24.9.0).
 *
 * Bis Web 24.5 trug diese Seite das Demo-Konto ganz; mit OF-05 (E-OF-08,
 * -46) wanderten Anlegen, Zuruecksetzen, Entfernen und der Schalter in die
 * Karte „Demo-Konto" unter Installation, die Kontrollzahlen blieben hier
 * lesend stehen. Seit OF-06/OF-09 (E-OF-71, -72) stehen Bestand und die drei
 * Papierkorbzahlen als zwei Zeilen in der Karte „Konten" der Statusseite —
 * eine Stelle je Sache. Die Adresse bleibt, damit alte Lesezeichen
 * ankommen: die BetreiberIn auf der Statuskarte, die Administration (die
 * den Betrieb nicht sieht) auf der Demo-Karte unter Installation.
 */

header('Location: ' . (ist_betreiberin()
    ? 'betrieb_status.php#k-konten'
    : 'admin_installation.php#k-demo'));
exit;
