<?php
declare(strict_types=1);
/**
 * WARTUNG — Notausgang auf der Kommandozeile, Weiterleitung im Web.
 *
 * IM WEB IST DIESE SEITE AUFGELOEST. Was einmal hier zusammen stand, steht
 * heute an diesen Stellen:
 *
 *   Wartungsmodus, Migrationen, Fassung     -> betrieb_updates.php
 *   Hintergrundjobs, Ausloeser, Token       -> betrieb_jobs.php
 *   Speichergrenze, Schwellen, Ablage       -> betrieb_server.php
 *   Schluesselableitung, Umgebung           -> betrieb_status.php
 *   Logo der Installation                   -> admin_installation.php
 *
 * WARUM DIE DATEI TROTZDEM BLEIBT: der Notausgang `php update.php` auf der
 * Kommandozeile. Er laeuft ohne Sitzung — fuer den Fall, dass die Anmeldung
 * selbst von einer Migration abhaengt — und steht so in Runbook, Handbuch und
 * in jeder aelteren Anleitung. Der Web-Teil ist eine Weiterleitung.
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
        /* Ohne Freigabe-Kennung ist es eine Vorbedingung: Sie wird
         * hergestellt, nicht freigegeben — der Weg steht in der Zeile. */
        if ($status === 'stopp') {
            printf("%-6s %-46s %s\n", '', '',
                   $blockId !== null
                       ? '-> Daten sichern, dann unter Betrieb → Updates einzeln freigeben.'
                       : '-> Nicht freigebbar: erst die Vorbedingung herstellen, dann erneut ausführen.');
        }
    }
    exit(0);
}

/* ---- Web: Weiterleitung auf Betrieb -> Updates ---------------------------
 *
 * Ab hier ist der Web-Teil eine Weiterleitung. Was hier stand, steht in den
 * Betriebsseiten.
 *
 * 302 UND NICHT 301: Eine dauerhafte Weiterleitung merkt sich der Browser, und
 * wer sie einmal falsch ausgeliefert hat, bekommt sie nicht mehr aus den
 * Zwischenspeichern heraus. Die Adresse steht in Lesezeichen, im Runbook und
 * in jeder aelteren Anleitung.
 *
 * DER NOTAUSGANG DARUEBER BLEIBT UNBERUEHRT: `php update.php` fuehrt die
 * Migrationen weiter aus, ohne Sitzung, fuer den Fall, dass die Anmeldung
 * selbst von einer Migration abhaengt.
 */
header('Location: betrieb_updates.php', true, 302);
exit;
