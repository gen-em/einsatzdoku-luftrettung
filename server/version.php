<?php
declare(strict_types=1);

/**
 * Version der Weboberflaeche.
 *
 * Bei jeder Auslieferung erhoehen — die Nummer erscheint in der Fusszeile.
 *
 * Seit Web 5.4.0 haengt sie NICHT mehr an den Stylesheet- und Skript-Adressen:
 * asset() (db.php) nimmt dafuer den Zeitstempel der jeweiligen Datei, damit
 * eine Versionserhoehung nur die tatsaechlich geaenderten Dateien neu laden
 * laesst (Backlog Nr. 9). WEB_VERSION bleibt dort der Rueckfall, wenn eine
 * Datei nicht gefunden wird.
 *
 * Zaehlweise (nach dem Muster "Haupt.Neben.Korrektur"):
 *   Haupt      grundlegende Umbauten, die ein bewusstes Vorgehen verlangen
 *              (z. B. Datenmodell, Verschluesselung, Migrationen)
 *   Neben      neue Funktionen und Felder
 *   Korrektur  Fehlerbehebungen und Feinschliff
 *
 * Die Uhr-App zaehlt getrennt (watch/source/Const.mc) — deshalb im Changelog
 * die Praefixe "Web" und "Uhr". Der Sprung auf 2.0.0 grenzt die eigenstaendige
 * Zaehlung von den fruehen Spezifikations-Staenden 1.0-1.2 ab; 3.0.0 markiert
 * den Umbau am Lebenszyklus des Inhaltsschluessels (Entsperren in der Sitzung);
 * 4.0.0 den Beginn der Umsetzung des Code-Reviews (gemeinsame Bausteine und
 * Schemaaenderungen, siehe Changelog); 6.0.0 die Erweiterung auf bodengebundene
 * Notarzteinsaetze — der Flugtag ist zum DIENSTTAG geworden, die Besatzung ist
 * normalisiert, und der Standort ist der Anker der Stammdaten. Eine Migration
 * ist zwingend (2026_08_17_notarzt_erweiterung), und Sicherungen aelterer
 * Formatversionen werden nicht mehr eingelesen.
 *
 * 6.1.0 ist Etappe 2 derselben Erweiterung: Einsatzfelder (Transportart,
 * NA-Begleitung, Fehleinsatz), das Ortsfeld als Komponente und der Abfahrtort
 * samt Luftlinie. OHNE Migration — die Spalten dafuer hat die Migration der
 * 6.0.0 bereits angelegt (Konzept, Berichtigung B5).
 *
 * 6.2.0 ist Etappe 3: Auswertung nach Art (Tabs in der Zeitraum-Uebersicht),
 * die neuen Filter der Einsatzsuche und der Nachzug in Export und Import.
 * Ebenfalls OHNE Migration.
 */
const WEB_VERSION = '6.2.0';
