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
 *
 * 6.3.0 schliesst die Erweiterung ab (Etappe 4): das Zusammenfuehren von
 * Diensttagen, die Uhr-Fassung 1.8.0 mit Dienstkennung (`day_ref`,
 * JSON-Vertrag 1.3) und der Nachzug der Dokumentation. Ebenfalls OHNE
 * Migration — `day_refs` und die Fremdschluessel auf `days` liegen seit 6.0.0.
 *
 * 7.0.0 ist eine Runde an der OBERFLAECHE, und die Hauptnummer steigt trotzdem:
 * Nicht wegen des Datenmodells — es bleibt unangetastet, und eine Migration
 * gibt es NICHT —, sondern weil sich die Wege durch die Anwendung geaendert
 * haben. Das Einsatzformular ist in benannte Gruppen zerlegt, „Standortdaten"
 * ist in zwei Menuepunkte („Standorte" und „Rettungsmittel") zerfallen, die
 * Filterspalte der Suche ist neu geschnitten, und das Feld „Einsatzdatum" ist
 * ersatzlos entfallen (der Tageswechsel wird jetzt aus dem Dienstbeginn
 * erkannt). Wer die Anwendung kennt, findet Dinge an neuer Stelle — das ist die
 * Aussage, fuer die die Hauptnummer da ist.
 *
 * Der Feldkatalog (mission_fields.php) hat dafuer drei neue Schluessel
 * ('gruppe', 'nebeneinander', 'vorbelegt_bei') und eine neue Reihenfolge; zwei
 * Beschriftungen sind umbenannt („Transportart", „Weiterer Notarzt"). Spalten
 * und Werte sind dieselben geblieben — Export, Import und Sicherungen ordnen
 * ueber Spaltennamen zu und sind unberuehrt.
 *
 * 7.0.1 berichtigt drei Dinge aus der Runde davor, alle in der Anzeige:
 * die Ueberschriften der Formulargruppen (eine <legend> sitzt in der
 * Voreinstellung MITTIG AUF der Rahmenlinie — mit gesperrter Versalschrift und
 * abgerundetem Rahmen sah das nach Fehler aus), die Eingabe fuer Standort und
 * Zielklinik (Namensfeld und Ortssuche standen auf zwei Hoehen, weil nur eines
 * von beiden eine Beschriftung trug) und das Kennzeichen der Vorbelegung (★).
 * Dazu eine Fehlbedingung: Die NA-Begleitung wurde nur beim NACHTRAGEN
 * vorbelegt, nicht beim Bearbeiten — die Einschraenkung war unbegruendet, weil
 * die Vorbelegung ohnehin nur auf eine Aenderung der Transportart hin greift.
 *
 * 7.0.2 setzt die Gruppenueberschriften des Einsatzformulars ab (sie waren
 * KLEINER als die Feldbeschriftungen darunter und lasen sich als Vorbemerkung
 * des ersten Feldes; jetzt groesser, in Dunkelblau, mit Trennlinie) und
 * vereinheitlicht die Groesse der Zeilenaktionen. Sie liefen aus zwei
 * unabhaengigen Gruenden auseinander: `.btn-plain` ist in style.css ZWEIMAL
 * definiert (die spaetere, groessere Fassung gewann), und `.btn-primary` traegt
 * die Formularmasse — „Auswaehlen" war damit sichtbar groesser als „Abwaehlen"
 * eine Zeile darueber. Die Groesse gilt jetzt fuer den ORT: Was in
 * `.rowactions` steht, ist eine Zeilenaktion. Damit faellt auch die naechste
 * Schaltflaechenklasse dort nicht mehr aus der Reihe.
 *
 * 7.1.0 ist eine AUFRAEUMRUNDE (Paket P0 des Programms Gen-EM NAdoku) und
 * bringt bewusst nichts Neues: keine Funktion, kein Feld, kein Datenmodell,
 * KEINE Migration, kein sichtbarer Unterschied. Entfernt sind vier Seiten, die
 * aus der Anwendung heraus nicht erreichbar waren (drei flugtag_*-Reste und
 * die Weiterleitung geraete.php); die Seitenhuelle steht statt 25-mal von Hand
 * jetzt einmal in ui.php (ui_seite_start/ui_seite_ende); style.css ist
 * entdoppelt und in 19 benannte Abschnitte gegliedert.
 *
 * WARUM DIE NEBENNUMMER STEIGT, obwohl die Zaehlweise dort "neue Funktionen
 * und Felder" vorsieht: Die Auslieferung verlangt eine Handlung der
 * Betreiberin — die vier Dateien muessen auch auf dem Webspace verschwinden —,
 * und angefasst ist jede einzelne Seite. Eine Korrekturfassung kuendigt beides
 * nicht an. Die Zaehlweise oben bleibt der Regelfall; dies ist die benannte
 * Ausnahme.
 *
 * 7.2.0 ist die NACHARBEIT zu P0: was in den Befundpaketen A4 und A6 gefunden
 * und danach einzeln freigegeben wurde, plus die Fehler, die dabei aufgefallen
 * sind. Kein neues Feld, keine Migration. Drei weitere Bausteine sind aus dem
 * Markup nach ui.php gewandert (das Ruestzeug der Verschluesselung, die
 * Meldungszeile, die Abbruchseite), und style.css hat zwoelf Regelpaare und
 * die Schaltflaechenfamilie zusammengefuehrt.
 *
 * SICHTBAR wird davon dreierlei: Die Rueckfragen auf der Sicherungsseite
 * erscheinen jetzt ueberhaupt (und die auf drei Seiten nicht mehr doppelt),
 * die Tagesuebersicht zeigt fuer nicht lesbare Angaben dasselbe Warnzeichen
 * wie Suche und Zeitraum, und ein veralteter Link fuehrt nicht mehr auf eine
 * weisse Seite mit sechs Woertern, sondern auf eine Seite mit Kopfleiste und
 * Rueckweg.
 *
 * 7.2.1 ist eine SICHERHEITSKORREKTUR mit eng begrenztem Umfang
 * (Sofortpaket Backlog Nr. 22, vor Phase P1). Das Alter ging als einzige der
 * drei geschuetzten Spalten unmaskiert in die Einsatztabellen — ueber das
 * Formular unerreichbar (parseInt), ueber den Import sehr wohl. Maskiert wird
 * jetzt in zelleGeschuetzt() selbst statt an der Aufrufstelle. Dabei mit
 * geraeumt: das Vormerkfach des Passwortwechsels (edk_neu), das den neuen
 * Datenschluessel bis dahin ueber das Abmelden hinaus tragen konnte.
 *
 * Fuer gueltige Eingaben aendert sich NICHTS — das ist nicht behauptet,
 * sondern gemessen (tools/maskierungs-probe/). Kein Schema, keine Migration,
 * keine Handlung der Betreiberin ausser dem Deploy.
 */
const WEB_VERSION = '7.2.1';
