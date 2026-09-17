<?php
declare(strict_types=1);
/**
 * Das Handbuch als Seite der Anwendung (P5b/AP8, E-P5b-08).
 *
 * Der Inhalt ist `docs/Handbuch.md` aus dem Repositorium — dieselbe Datei,
 * die auf GitHub steht und durch die Wortliste laeuft. Die Seite selbst ist
 * `doku_seite.php`; sie traegt beide Dokumente, weil sie sich nur im
 * Dateinamen und im Inhaltsverzeichnis unterscheiden.
 *
 * SPRUNGMARKEN: Hilfe-Verweise aus der Oberflaeche zeigen auf
 * `hilfe.php#abschnitt`. Die Marken entstehen aus dem Ueberschriftentext
 * (`doku_marke()`) und aendern sich nur, wenn jemand eine Ueberschrift
 * umbenennt — dann brechen sie, und zwar still. Wer eine Ueberschrift des
 * Handbuchs umbenennt, sucht deshalb vorher nach ihrer Marke im Quelltext.
 */
$dokuName = 'handbuch';
require __DIR__ . '/doku_seite.php';
