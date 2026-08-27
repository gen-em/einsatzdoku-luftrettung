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
 * drei geschuetzten Spalten unmaskiert in die Einsatztabellen. Maskiert wird
 * jetzt in zelleGeschuetzt() selbst statt an der Aufrufstelle. Dabei mit
 * geraeumt: das Vormerkfach des Passwortwechsels (edk_neu), das den neuen
 * Datenschluessel bis dahin ueber das Abmelden hinaus tragen konnte.
 *
 * Fuer gueltige Eingaben aendert sich NICHTS — das ist nicht behauptet,
 * sondern gemessen (tools/maskierungs-probe/). Kein Schema, keine Migration,
 * keine Handlung der Betreiberin ausser dem Deploy.
 *
 * NACHTRAG: Dieselbe Luecke ist in Phase P1 unabhaengig ein zweites Mal
 * gefunden worden (Fund F-P1-I, mit dem Referenzdatensatz im Browser
 * gemessen: gegen den Stand 7.2.0 sechs Befunde ueber drei Seiten, gegen
 * diesen keiner bei 42 Einzelpruefungen, nach dem Zusammenfuehren beider
 * Arbeitslinien noch einmal gefahren). Zwei Wege, ein Befund — die Messung
 * aus P1 belegt diese Fassung, sie hat sie nicht ausgeloest.
 *
 * 7.2.2 behebt einen stillen Datenverlust im CSV-Rueckimport: gruppiere() in
 * assets/import.js fuehrte eine zweite, von Hand gepflegte Feldliste neben
 * EINFACHE_ZIELE — und die sechs Felder der Etappe 2 (Transportart,
 * NA-Begleitung, Fehleinsatz, Zielkoordinate, Abfahrtortregel) waren dort nie
 * nachgetragen worden. Sie wurden gelesen, in der Prueftabelle angezeigt und
 * danach fallengelassen. Die Liste wird jetzt aus EINFACHE_ZIELE abgeleitet.
 *
 * 7.2.3 berichtigt zwei Formatbeschreibungen, die etwas anderes sagten als
 * der Code tut: Die ausgelieferte LIESMICH.txt des CSV-Exports nannte die
 * Spalte weiterhin `hubschrauber` (sie heisst seit 5.10.0 `rettungsmittel`),
 * und docs/Backup-Format.md fuehrte `days[].id` unter „nicht in der Datei",
 * obwohl sie darin steht und stehen MUSS — die Einsaetze verweisen darauf.
 *
 * 7.3.0 bringt das DEMO-KONTO: ein Konto zum Ausprobieren mit erfundenen
 * Daten, oeffentlichen Zugangsdaten und einer selbsttaetigen Ruecksetzung
 * alle 30 Minuten. Neue Funktion, KEINE Migration — `app_state` liegt seit
 * jeher, und der Bestand wird ueber die vorhandene Einspielroutine
 * (`edbak_restore()`) hergestellt.
 *
 * Es ist zugleich die einzige Stelle der Anwendung, an der die
 * Ende-zu-Ende-Verschluesselung bewusst ausgesetzt ist: Das
 * Schluesselmaterial dieses einen Kontos liegt in einer Fixture auf dem
 * Server. Vertretbar nur unter vier erzwungenen Bedingungen — erfundene
 * Daten, Rolle `user`, jede Funktion arbeitet ausschliesslich auf der
 * Kennung aus `app_state.demo_user_id`, und die Zugangsdaten sind ohnehin
 * oeffentlich.
 *
 * 7.3.1 behebt eine stille Datenverfaelschung im CSV-Rueckimport: Einsaetze
 * eines Dienstes ueber Mitternacht landeten 24 Stunden zu frueh, weil die
 * Alarmzeit auf den DIENSTTAG gerechnet wurde statt auf das Einsatzdatum.
 * Die Angabe, die das behebt, stand die ganze Zeit in der Datei — die Spalte
 * `datum` war im Importprofil auf target:null gesetzt, mit einem Kommentar,
 * der das Gegenteil dessen behauptete, was der Code tat.
 *
 * 8.0.0 macht die SICHERUNG VOLLSTAENDIG, und die Hauptnummer steigt dafuer
 * aus einem einzigen Grund: Die NUTZLAST einer Sicherung ist eine andere
 * geworden (Formatversion 6 -> 7). Sie fuehrt jetzt den Papierkorb, und der
 * Einspielweg bringt ihn als Papierkorb zurueck — nicht als aktiven Bestand.
 * Was vorher galt, war ein stiller Verlust: Wer am Tag nach einem
 * versehentlichen Loeschen sicherte und die Datei spaeter zurueckspielte,
 * verlor genau das, was er retten wollte.
 *
 * Eine Migration gibt es NICHT — die Spalten `deleted_at` und
 * `deleted_with_day` liegen seit jeher, sie standen nur bisher immer leer in
 * der Datei. Zu beachten ist etwas anderes: Der Sprung auf Nutzlast 7
 * kennzeichnet, er SPERRT NICHT. Bereits ausgelieferte Staende nehmen eine
 * v7-Datei an und braechten ihren Papierkorb aktiv zurueck; das steht als
 * Warnung in docs/Backup-Format.md 4 und liess sich nachtraeglich nicht mehr
 * verhindern.
 *
 * Dazu zwei Fehler des CSV-Kreislaufs, die die Phase P1 gemessen hatte:
 * mehrzeilige Notizen verloren beim Rueckimport ihre Zeilenumbrueche, und
 * `final = 0` samt leerem Ende wurde ueberschrieben — ein nicht
 * abgeschlossener Einsatz kam als abgeschlossen zurueck. Beide Kreislaeufe
 * (Sicherung und CSV) stehen danach auf null unerklaerten Abweichungen.
 *
 * Und drei Stellen im Einspielweg, an denen eine kaputte Datei bisher nicht
 * ihre Zeile kostete, sondern den ganzen Lauf: die fehlende Pruefschicht der
 * Ruhesegmente, ihre ungeprueft geschriebene Spur und ein doppeltes `seq`,
 * das ueber den Primaerschluessel von `track_points` einen Konflikt ausloest.
 * Alle drei betreffen nur Dateien fremder oder von Hand bearbeiteter
 * Herkunft — aber eine Wiederherstellung ist der Moment, in dem jemand
 * ohnehin schon etwas verloren hat.
 *
 * DAZU DIE BEIDEN ENTSCHEIDUNGEN, die nach der Nachlese offen waren
 * (Backlog Nr. 33 und 34) — beide betreffen den halb sichtbaren Einsatz:
 * einen aktiven Eintrag an einem GELOESCHTEN Diensttag.
 *
 * Nr. 33 hatte drei Tueren dorthin. Sie sind zu: Der Papierkorb lehnt das
 * Zurueckholen ab, solange der Diensttag selbst darin liegt; die Uhr loest
 * ueber eine Kennung auf einem geloeschten Tag jetzt einen NEUEN Tag aus
 * (zusammenfuehren laesst er sich, verwerfen waere Datenverlust); und das
 * endgueltige Loeschen eines Tages nimmt alles mit, statt ein Waisenkind
 * ohne Diensttag zurueckzulassen — die Rueckfrage nennt es vorher.
 * Altbestand meldet die Wartungsseite, ohne ihn anzufassen.
 *
 * Nr. 34: Schritt 1 der Diensttag-Wiedererkennung beim Einspielen nahm den
 * ERSTEN gefundenen Einsatz und verhaengte dessen Tag ueber den ganzen
 * Datei-Tag. Jetzt zaehlen alle Kennungen; nur ein eindeutiges Ergebnis
 * gilt, ein Widerspruch wird als `tag_mehrdeutig` gemeldet und der
 * Fingerabdruck entscheidet.
 *
 * KEINE eigene Nummer fuer die Nachlese (Paket C8). Sie hat zwei Fehler
 * behoben, die in DIESER Fassung entstanden sind und nie auf einem Server
 * standen — 8.0.0 ist zu keinem Zeitpunkt ausgeliefert gewesen. Eine 8.0.1,
 * die eine 8.0.0 berichtigt, die es nirgends gab, waere eine Zahl ohne
 * Gegenstueck in der Welt. Was die Nachlese geaendert hat, steht im
 * CHANGELOG unter 8.0.0.
 *
 * 8.0.1 ist die Terminologie-Phase P2: Die Oberflaeche und die normative
 * Dokumentation sprechen neutral von Land und Luft. Eine Korrekturnummer,
 * weil kein Feld, keine Funktion und kein Datenformat hinzukommt — was sich
 * aendert, sind Texte und drei Aussagen, die nicht mehr stimmten
 * (Kopplungsanleitung, Warntext des Excel-Rueckimports, Excel-Spaltentabelle
 * der Format-Doku). KEINE Migration: `update.php` muss nach diesem Deploy
 * NICHT aufgerufen werden. Die Uhr bleibt unveraendert und wird nicht
 * ausgeliefert. Neu im Repositorium, aber nicht auf dem Server:
 * `tools/wortliste/` zaehlt nach, ob die Texte neutral sind — es laeuft in
 * P3 und P6 mit.
 *
 * In 8.0.1 EINGEFALTET, obwohl es keine Terminologie ist: zwei Funde
 * derselben Phase, die dort nur gesammelt wurden. Eine eigene Nummer haetten
 * sie verdient, aber 8.0.1 stand zu diesem Zeitpunkt auf keinem Server —
 * eine 8.0.2, die eine 8.0.1 berichtigt, die es nirgends gab, waere dieselbe
 * Zahl ohne Gegenstueck, gegen die schon der Absatz zur 8.0.0 argumentiert.
 *
 *   Das Anlegen des Demo-Kontos brach ab, sobald eine Installation auch den
 *   Bestand fuehrte, aus dem die Fixture stammt: Sie brachte das virtuelle
 *   Geraet "Manuelle Einträge" mit, dessen Kennung die KONTONUMMER traegt
 *   ('manual-2') und in `devices.device_id` global eindeutig ist. Es wird
 *   jetzt weder eingespielt noch ueberhaupt in die Fixture aufgenommen —
 *   im Zielkonto entsteht es bei Bedarf mit der richtigen Nummer von selbst.
 *   Dazu zaehlt die Adminansicht die Geraete jetzt nach derselben Regel wie
 *   Geraeteliste und Grenze (ohne das virtuelle), und ein Datenbankfehler
 *   erscheint dort nicht mehr im Wortlaut, sondern als lesbare Meldung mit
 *   einer Kennung, unter der die Ursache im Fehlerprotokoll steht.
 *
 *   Die Sicherungsbeschreibung nannte einen Rollencode 'tc', den es nie
 *   gegeben hat; gemeint ist 'hems'. Nur Dokumentation.
 *
 * 9.0.0 ist der Beginn von P3 — die WEBOBERFLAECHE WIRD NEU GEBAUT, mobil
 * zuerst. Die Hauptnummer steigt aus demselben Grund wie bei 7.0.0: nicht
 * wegen des Datenmodells (es bleibt unangetastet, eine Migration gibt es in
 * diesem Schritt NICHT), sondern weil sich die Wege durch die Anwendung
 * aendern werden — Schublade statt Seitenleiste, Kachel statt Tabelle,
 * Aktionsblatt statt Knopfreihe.
 *
 * 9.0.0 selbst ist das FUNDAMENT und noch keine fertige Oberflaeche
 * (Arbeitspaket O1). Das Stylesheet ist von Grund auf neu und enthaelt
 * bisher nur Token und Grundlagen; die Bausteine folgen in 9.1.0. Ein
 * Zwischenstand, in dem die Anwendung roh aussieht, ist eingeplant und
 * ausdruecklich kein Fehler.
 *
 * Was 9.0.0 mitbringt:
 *
 *   - Ein Stylesheet mit allen Werten an EINER Stelle. Vorher: 78 Hexwerte
 *     ausserhalb von :root, 21 verschiedene Schriftgroessen, die Kopfhoehe
 *     fuenfmal als 50px fest verdrahtet, zwei Graufamilien nebeneinander.
 *     Jetzt: ein Token-Block, eine Schriftskala (Major Third), eine
 *     Graustufe, eine Kopfhoehe. Gruen und Gelb entfallen — beide waren
 *     markenfremd.
 *   - 44 Symbole als einzelne Dateien (Tabler Icons, MIT) statt fuenf
 *     Inline-SVG, zwoelf Unicode-Zeichen und zwei Emoji. ui_symbol() in PHP
 *     und edSymbol() in assets/symbol.js erzeugen dieselbe Zeichenkette.
 *   - Die Logodateien tragen die Markenfarben; vorher trugen sie
 *     Naeherungen. Dazu ein NEF-Platzhalter in denselben Maassen und
 *     Fassungen, damit die Logo-Wahl gebaut werden kann, bevor die echte
 *     Datei vorliegt.
 *   - Zwei neue Pruefmittel (tools/vollstaendigkeit/, tools/screenshots/),
 *     die den Stilvergleich fuer die Dauer der Phase ersetzen.
 *
 * Der Symbolvorrat und die Logos sind die einzigen Teile, die schon jetzt
 * ausgeliefert werden muessen: Ohne sie zeigt 9.1.0 leere Rahmen.
 *
 * 9.1.0 ist O2: DIE OBERFLAECHE HAT WIEDER EINE GESTALT — Seitenhuelle und
 * Bausteine. Ab hier sieht die Anwendung aus wie das, was sie werden soll;
 * die Seiteninhalte selbst folgen Paket fuer Paket (O3 bis O11).
 *
 * Was sich fuer den Menschen aendert:
 *
 *   - EIN MENUE STATT EINER LEISTE, DIE DEN BILDSCHIRM FRISST. Unter 1024 px
 *     liegt die Seitenleiste als Schublade hinter einem Knopf in der
 *     Kopfleiste. Vorher fuellte sie bei 360 px den ganzen ersten Bildschirm,
 *     Inhalt begann nach etwa anderthalb Bildschirmen Scrollen, und die
 *     Tagesliste lief rechts aus dem Bild. Das galt fuer ALLE zwanzig
 *     Inhaltsseiten, weil das Einstellungsmenue dieselbe Klasse trug.
 *   - DIE GANZE ZEILE KLAPPT das Akkordeon der Diensttage; der Weg in die
 *     Jahres- und Monatsuebersicht ist ein eigenes Symbol rechts. Vorher war
 *     der Text der Link und nur das Dreieck der Schalter — mit dem Finger
 *     nicht auseinanderzuhalten.
 *   - DAS ZAHNRAD FUEHRT AUF EINE UEBERSICHT statt ungefragt auf „Profil".
 *   - EINE FUSSZEILE AUF JEDER SEITE, auch vor der Anmeldung, und ausserhalb
 *     von <main>. Sie fehlte bisher auf jeder Seite ohne Inhalt.
 *   - DIE ARTZEICHEN SIND KEINE EMOJI MEHR. Die beiden Emoji fuer
 *     Hubschrauber und Rettungswagen wurden je Betriebssystem anders
 *     gezeichnet; jetzt sind es Symbole aus dem Vorrat,
 *     die sich mitfaerben. Wo kein Bild hineinpasst — in einer Auswahlliste —
 *     steht das WORT.
 *   - DER EINRICHTER BENUTZT DAS GEMEINSAME STYLESHEET (Backlog Nr. 18). Er
 *     war die einzige Seite mit eigener Gestaltung, eigenen Knopfklassen und
 *     ohne Fusszeile.
 *
 * Keine Migration. Verhalten, Endpunkte, Datenmodell und Feldkatalog sind
 * unveraendert; geaendert sind Huelle, Klassennamen und Markup der Huelle.
 *
 * 9.1.1 ist die NACHARBEIT DER FABLE-KONTROLLE zu O1/O2: Der Stand wurde
 * Mockup fuer Mockup gegen die Screenshots gehalten und der Konzeptumfang
 * gegen den Code. Neun Funde (F-P3-Q bis F-P3-Y, Konzept 9.2), alle behoben:
 *
 *   - Winkelrichtung im Akkordeon war falsch herum (zu = rechts, offen =
 *     unten, wie in den Mockups — nicht offen = oben).
 *   - Der Balken-Link zur Zeitraumuebersicht war an zugeklappten Zeilen
 *     unsichtbar: Er lag ausserhalb des <summary>, und der Inhalt eines
 *     geschlossenen <details> wird nicht gerendert. Jetzt steht er in der
 *     Zeile; daylist.js faengt den Klick ab.
 *   - confirm.js, unlock.js und die Archiv-Passwortabfrage des Imports
 *     benutzen den Dialog-Baustein (.dialog, .knopf, .feld) — dieser Teil
 *     des O2-Umfangs war schlicht vergessen. Jede Rueckfrage und der
 *     Entsperrdialog erschienen unformatiert.
 *   - Die Alt-Meldungsklassen (.alert-Familie), .muted und .swatch haben
 *     eine begruendete Uebergangsregel: Eine Fehlermeldung, die aussieht wie
 *     Fliesstext, warnt niemanden.
 *   - "Einstellungen" als Rueckweg ueber jeder Unterseite (E-P3-11, mobil).
 *   - Beim Oeffnen der Schublade traegt das X keinen ungebetenen Fokusring
 *     mehr; der Fokus liegt auf der Leiste selbst.
 *   - "Administration" steht als Blockueberschrift UEBER der Karte, wie im
 *     Mockup — nicht als Kartentitel.
 *   - Der Rueckweg der oeffentlichen Huelle ist auch unter 1024 px sichtbar.
 *   - Leaflet zeichnete ueber die Schublade (interner z-index bis 1000);
 *     die Karte hat jetzt ihren eigenen Stapelkontext.
 *
 * 9.2.0 ist O3: DIE STARTSEITE NACH DEN MOCKUPS 02-05 UND 10. Titelzeile
 * mit Aktionsblatt statt Knopfreihe; Diensttag-Daten als Karte mit
 * LESEANSICHT und aufklappendem Formular; die Einsaetze unter 720 px als
 * dreizeilige KACHEL (Streifen, Ort, Diagnose, Plaketten) statt einer
 * Tabelle, deren Ort- und Diagnosespalte auf 360 px null Pixel bekamen;
 * Sortieren mobil ueber ein Blatt, mit derselben Reihenfolge wie am
 * Desktop. Auf der Karte der MARKER-SATZ nach E-P3-40: Standort-Haus,
 * Ziel-Klinik-Schild, Einsatzort orange, Start/Ende-Ringe, Richtungspfeile;
 * die Spurfarben kommen als Token aus dem Stylesheet (EdGeo statt der
 * COLORS-Liste). Nebenbei behoben: fitBounds bekam sein Padding mit
 * vertauschten Achsen (F-P3-Z, Bestandsfehler) — die Tageskarte blieb
 * deshalb oft auf der Rueckfallzoomstufe haengen; und ein globaler
 * [hidden]-Waechter, weil display:grid das Attribut ueberstimmte und
 * Lese- und Formularzustand gleichzeitig zu sehen waren.
 *
 * Keine Migration; Endpunkte und Feldkatalog unveraendert.
 *
 * 9.3.0 ist O4: DIE EINSATZANSICHT NACH DEN MOCKUPS 19-21 UND 26. Die eine
 * lange Feldliste ist VIER KARTEN gewichen (Einsatz, PatientIn, Transport,
 * Reanimation; die Besatzung behaelt ihre eigene) — die RANG-Ordnung
 * sortiert jetzt je Karte. Titelzeile mit Rueckweg zum Diensttag,
 * "Bearbeiten" als Primaerknopf, Verschieben und Loeschen im Blatt (das
 * alte <details>-Aktionsmenue samt aktionsmenu.js ist fort). Der Zustand
 * der geschuetzten Angaben steht als EINE Meldung ueber den Karten; die
 * neun Schloss-Emojis an den Zeilen sind entfallen. Winde, Bergwacht,
 * Sekundaer und Fehleinsatz erscheinen als Plaketten am Fuss der
 * Einsatz-Karte; Hoehe, Luftlinie und Strecke als Kleinzeile unter dem
 * Einsatzort. Auf der Karte der EdGeo-Marker-Satz statt des doppelten
 * SVG-Pfads: Haus- und Klinik-Schild, oranger Einsatzort-Kreis,
 * Start/Ende-Ringe an Schild oder als eigener Ringpunkt, Richtungspfeile.
 * Die Phasenliste nennt den MINUTENABSTAND zur vorigen Phase und die
 * Gesamtdauer; die angetippte Phase faerbt ihr TEILSTUECK der Spur blau —
 * dafuer liefert api/mission.php je Phase den naechstliegenden Trackpunkt
 * nach ZEITSTEMPEL (track_idx), denn GPS traegt nicht jede Phase. Dazu
 * base_lat/lon des Tages in der Antwort (Haus-Schild, Klartext wie der
 * Name). fitBounds auch hier mit den richtigen Achsen (F-P3-Z).
 *
 * Keine Migration; Feldkatalog unveraendert, api/mission.php nur ERWEITERT.
 *
 * 9.4.0 ist O5: DAS EINSATZFORMULAR NACH DEN MOCKUPS 22/23/25, mit zwei
 * Funktionsaenderungen (E-P3-34). Die Rahmengruppen sind KARTEN geworden
 * (ab 1200 px zwei Spalten); der Einsatzort steht bei den uebrigen
 * verschluesselten Feldern in der Karte PatientIn. Ja/Nein-Felder sind
 * SCHALTER, ihre Detailfelder ruecken hinter einer orangen Linie ein.
 * Die Phasenzeilen SORTIEREN SICH SOFORT beim Verlassen eines Zeitfelds
 * (Mitternachtsregel wie beim Speichern); der Hinweistext entfaellt, der
 * Kartenkopf zaehlt mit ("8 von 9"). Gespeichert wird ueber die
 * SPEICHERN-LEISTE, die mit der ersten Aenderung erscheint (forms.js);
 * der Abbrechen-Link entfaellt zugunsten des Rueckwegs oben. Am Ortsfeld
 * ersetzt der LUPEN-Knopf das zweite Suchfeld ("Lokalisation ..."), und
 * der PIN-Knopf oeffnet das Blatt "Meine Position uebernehmen / Auf der
 * Karte waehlen" (neues assets/ortswahl.js: Geolocation, Leaflet-Dialog
 * mit Fadenkreuz, Photon-Umkehrsuche — die Anfrage traegt NUR die
 * Koordinate). Speicherlogik und Felder sind unveraendert; der
 * 5-Einsaetze-Rundlauf und beide Kreislaeufe belegen es.
 *
 * Dabei gefunden: Ein POST an einstellungen.php OHNE ?t versandete seit
 * der O2-Uebersichts-Weiche stillschweigend — die Browser-Formulare tragen
 * das t, das Einspielwerkzeug trug es nicht (F-P3-AF, im Werkzeug behoben).
 *
 * Keine Migration; Endpunkte und Feldkatalog unveraendert.
 */
const WEB_VERSION = '9.4.0';
