<?php
declare(strict_types=1);
/**
 * WARTUNG — Notausgang auf der Kommandozeile, Weiterleitung im Web (S8/AP3).
 *
 * WAS AUS DIESER SEITE GEWORDEN IST. Sie trug bis Web 15.0.0 neun Bloecke auf
 * einer Flaeche: Wartungsmodus, Schluesselableitung, Logo, Umgebung,
 * Hintergrundjobs, Job-Ausloeser, Einsaetze ohne Diensttag, Migrationsliste
 * und den Balken darueber. Das war der Ausloeser fuer Backlog Nr. 77 und
 * einer der Befunde der S8-Sichtung (B-S8-03: vier Anliegen auf einer Seite).
 *
 * S8 teilt sie nicht auf, sondern loest sie AUF (E-S8-05). Wohin was gegangen
 * ist:
 *
 *   Wartungsmodus, Migrationen, Fassung     -> betrieb_updates.php
 *   Hintergrundjobs, Ausloeser, Token       -> betrieb_jobs.php
 *   Speichergrenze, Schwellen, Ablage       -> betrieb_server.php
 *   Schluesselableitung, Umgebung           -> betrieb_status.php (AP4)
 *   Logo der Installation                   -> admin_installation.php (AP3)
 *   Einsaetze ohne Diensttag                -> ersatzlos entfallen (E-S8-17)
 *
 * WARUM DIE DATEI TROTZDEM BLEIBT: der Notausgang `php update.php` auf der
 * Kommandozeile. Er laeuft ohne Sitzung — fuer den Fall, dass die Anmeldung
 * selbst von einer Migration abhaengt — und steht so in Runbook, Handbuch und
 * in jeder aelteren Anleitung. Der Web-Teil ist seit AP3 eine Weiterleitung
 * und bleibt es bis P6 (Nr. 77).
 */
// Notausgang: Aufruf per Kommandozeile (SSH) laeuft ohne Web-Session —
// fuer den Fall, dass der Login selbst von einer Migration abhaengt.
//   php update.php
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/db.php';              // liefert auch e()
} else {
    require_once __DIR__ . '/auth_guard.php';
    require_admin();
}
require_once __DIR__ . '/migration_lib.php';

/* ---- Kommandozeile: einstufig, dann Schluss --------------------------------
 *
 * Wer "php update.php" eintippt, hat die bewusste Handlung bereits vollzogen —
 * anders als beim Aufruf einer Seite im Browser. Die zweite Stufe (Vorschau,
 * dann Knopf) gibt es hier deshalb nicht, und die FREIGABE einer blockierten
 * Migration ebenfalls nicht: Ein Argument "--force" waere zu leicht aus einer
 * Anleitung abgeschrieben. Der Weg dorthin steht in der Ausgabe.
 */
if (php_sapi_name() === 'cli') {
    $lauf = migrationen_lauf(db(), true);
    foreach ($lauf['results'] as [$id, $label, $status, $detail, $zerstoert, $blockId, $web]) {
        printf("%-6s %-9s %-46s %s\n", strtoupper($status),
               $web !== null ? ('Web ' . $web) : '', $id, $detail);
        if ($status === 'stopp') {
            printf("%-6s %-46s %s\n", '', '',
                   '-> Daten sichern, dann unter Betrieb → Updates einzeln freigeben.');
        }
    }
    exit(0);
}

/* ---- Web: Weiterleitung auf Betrieb -> Updates (S8/AP3) -------------------
 *
 * Ab hier ist der Web-Teil eine Weiterleitung. Was hier stand, steht in den
 * drei Betriebsseiten; die Logo-Karte ist mit AP3 auf „Installation" gezogen
 * und war der letzte Grund, warum diese Seite noch etwas anzeigte.
 *
 * 302 UND NICHT 301: Eine dauerhafte Weiterleitung merkt sich der Browser, und
 * wer sie einmal falsch ausgeliefert hat, bekommt sie nicht mehr aus den
 * Zwischenspeichern heraus. Die Adresse verschwindet erst mit Nr. 77 in P6 —
 * bis dahin steht sie in Lesezeichen, im Runbook und in jeder aelteren
 * Anleitung.
 *
 * DER NOTAUSGANG DARUEBER BLEIBT UNBERUEHRT: `php update.php` fuehrt die
 * Migrationen weiter aus, ohne Sitzung, fuer den Fall, dass die Anmeldung
 * selbst von einer Migration abhaengt.
 */
header('Location: betrieb_updates.php', true, 302);
exit;
