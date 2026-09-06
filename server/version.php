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
 *
 * 9.5.0 ist O6: DIE SUCHE NACH DEN MOCKUPS 27/28 (E-P3-36). Die eigene
 * Filterspalte ist in die gemeinsame LEISTE gezogen — damit hat die Suche
 * unter 1024 px zum ersten Mal ein Menue (vorher stand die Filterspalte
 * als anderthalb Bildschirme VOR dem Ergebnis). Die fuenf Bloecke aus
 * Web 7.0.0 (Einsatz, PatientIn, Transport, Beteiligte, Bergrettung)
 * bleiben in ihrem Zuschnitt und sind AKKORDEONS wie die Diensttage
 * geworden — jedes mit einer PLAKETTE, die zaehlt, wie viele Filter
 * darin gesetzt sind; der Fuss der Schublade traegt "Filter zuruecksetzen"
 * und "n Treffer zeigen" mit der Zahl aus der laufenden Suche. Ueber der
 * Trefferliste steht dieselbe Zahl noch einmal als PLAKETTENZEILE: je ein
 * gesetzter Filter, einzeln abwaehlbar.
 *
 * Das Freitextfeld ist 48 px hoch mit Lupe und Loeschkreuz; die
 * Suchsyntax steht nicht mehr dauerhaft darunter, sondern hinter
 * "Syntaxhilfe". TREFFERWOERTER werden HERVORGEHOBEN (<mark>) — in den
 * beiden angezeigten Textspalten Einsatzort und Diagnose, also in
 * ENTSCHLUESSELTEN Feldern: Die Hervorhebung geschieht deshalb ERST NACH
 * dem Maskieren im Browser (suchtext.js: woerter()/hervor()), die
 * Suchlogik selbst ist unberuehrt. Durchsucht wird wie bisher mehr, als
 * die Liste zeigt (Notizen, Besatzung, Rettungsmittel) — dort ist nichts
 * hervorzuheben, weil nichts davon in der Liste steht.
 * Unter 720 px zeigt die Suche KACHELN statt Tabelle, mit Artzeichen und
 * Datum in der Kopfzeile und einzeiliger Diagnose.
 *
 * Dabei gefunden und behoben: Seit dem Wegfall der Klasse `filterspalte`
 * in O2 hing der Zuhoerer der Filter an einem Selektor, der NICHTS mehr
 * traf — kein Filter der Seitenleiste wirkte (F-P3-AG). Der Zuhoerer
 * haengt jetzt an der Leiste selbst.
 *
 * Keine Migration; Suchlogik, Endpunkte und Feldkatalog unveraendert —
 * acht Proben (fuenf Suchbegriffe, drei Filterkombinationen) liefern vor
 * und nach O6 dieselben Treffer.
 *
 * 9.6.0 ist O7: DIE ZEITRAUMUEBERSICHT NACH DEN MOCKUPS 29/30/31, mit einer
 * FUNKTIONSAENDERUNG an den Kachelsaetzen (E-P3-37). „GEMISCHT" ZEIGT VIER
 * KENNZAHLEN STATT ACHT: Einsaetze, Diensttage, ihr Verhaeltnis und die
 * Sekundaertransporte. Bisher teilte Gemischt den Bodensatz mit acht — ueber
 * beide Arten hinweg sind Kilometer, Dauern und Fehleinsaetze aber Aepfel
 * und Birnen: Eine Flugstrecke von 61 km und eine Fahrstrecke von 12 km
 * stehen fuer verschiedene Einsaetze, und ihre Summe beantwortet keine
 * Frage, die jemand stellt. Luft (10) und Boden (8) bleiben unveraendert;
 * ihre Zahlen sind belegt gleich geblieben (88 Kachelwerte verglichen).
 *
 * Die Tableiste nach Art ist eine SEGMENTWAHL in der Titelzeile geworden
 * („Gemischt / Luft / Boden") — mobil vollbreit. Aus <button role="tab">
 * sind Radios geworden: Der Wechsel mit den Pfeiltasten kommt damit vom
 * Browser. Unter 720 px sind je Satz VIER Kacheln sichtbar, der Rest steht
 * hinter „Weitere Statistik (n)"; welche vier, sagt die Kachel selbst.
 * Extremwerte tragen den TAG in der Beschriftung („Laengste Flugstrecke
 * · 14.08.") und sind HELL ORANGE statt rot — Rot heisst in dieser
 * Oberflaeche „Aufmerksamkeit", und ein Hoechstwert ist kein Fehler.
 * Darunter Karte mit STANDORT-HAUS (E-P3-40), dann die Einsaetze als
 * Tabelle ab 720 px und als Kacheln darunter.
 *
 * api/range.php liefert dafuer neu die STANDORTE der Diensttage
 * (`bases`) — Klartext wie `kind` und `vehicle_name`, entdupliziert nach
 * Koordinate. Die verschluesselten Angaben bleiben unberuehrt im pat_blob.
 *
 * Dabei gefunden: Das Screenshot-Werkzeug fotografierte `zeitraum.php`
 * OHNE `?y=` — die Seite leitet dann auf die Startseite um. Die
 * Zeitraumuebersicht war damit seit O1 nie im Bilderlauf (F-P3-AH,
 * im Werkzeug behoben; jetzt zwei Seiten: Jahr und Monat).
 *
 * Keine Migration; Kennzahlen, Endpunkte und Feldkatalog unveraendert.
 *
 * 9.7.0 ist O8a: PROFIL, LOGO-WAHL UND DIE VERWALTUNGSLISTEN AM MUSTER DER
 * STANDORTE — der erste Teil eines Pakets, das sich beim Bauen als zu gross
 * fuer einen Zug erwiesen hat (Rettungsmittel, Geraete, Sicherung und
 * Import folgen als O8b).
 *
 * !!! DIESE FASSUNG BRAUCHT EINE MIGRATION !!!
 * `2026_08_27_logo_wahl` legt `users.logo_wahl` an. Nach dem Ausrollen muss
 * eine Administratorin update.php aufrufen; ohne die Spalte scheitert JEDE
 * Anmeldung, weil login.php sie mitliest. Es ist die erste Schemaaenderung
 * dieser Phase.
 *
 * DIE LOGO-WAHL (E-P3-20) steht im Profil: Standard der Installation /
 * Hubschrauber (RTH) / Fahrzeug (NEF) / wechselnd. Aufgeloest wird sie
 * EINMAL bei der Anmeldung (session_lib.php) — in der Sitzung steht danach
 * das Ergebnis, nicht die Wahl; sonst wuerfelte „wechselnd" bei jedem
 * Seitenaufruf neu, und das Logo spraenge beim Blaettern. Kopfleiste und
 * Favicon fragen dieselbe Stelle (logo_stamm()) und koennen deshalb nicht
 * auseinanderlaufen. Die Anmeldeseite zeigt immer den Standard — dort ist
 * noch niemand angemeldet, und die Wahl haengt am Konto.
 *
 * DIE VERWALTUNGSLISTEN (E-P3-35) am Muster der Standorte: Erklaertext auf
 * drei Zeilen statt zweier Absaetze, Karte mit Zeilen statt Tabelle,
 * Zeilenaktionen am Schreibtisch als Knoepfe und mobil als „···"-Blatt
 * (neuer Baustein ui_zeilenaktionen), das Anlegen-Formular IN derselben
 * Karte, die vordefinierten Eintraege als zweite, zugeklappte Karte mit
 * „n · m ausgewaehlt". Die POST-Formulare stehen dabei nur EINMAL im
 * Markup; Knopf und Blatt zeigen ueber `form=` darauf.
 *
 * DIE PASSWORTSTAERKE ist ein Balken aus vier Segmenten geworden (E-P3-16,
 * Mockup 11). Vorher war es eine Textzeile in fuenf Farben, darunter Gruen
 * und Gelb — zwei Toene, die es in der Marke nicht gibt.
 *
 * Dabei gefunden: Seit O5 gab es KEIN EINGABEFELD FUER DIE LAGE mehr. Der
 * Ausbau des zweiten Suchfelds hat die Nur-Lage-Fassung von ui_ortsfeld()
 * leer zurueckgelassen — die Lage eines Standorts oder einer Zielklinik
 * liess sich seither nicht mehr eingeben, nur noch behalten (F-P3-AI).
 *
 * 9.7.1 ist O8b: DIE UEBRIGEN VERWALTUNGSLISTEN nach dem Muster aus O8a —
 * der Reiter „Rettungsmittel" mit seinen fuenf Listen je Standort
 * (Rettungsmittel, Besatzung, Zielkliniken, weitere Rettungsmittel,
 * Bergwacht) und der Reiter „Geraete". Sicherung und Import folgen als O8c.
 *
 * Das Muster stand im Bestand FUENFMAL AUSGESCHRIEBEN, und es war bereits
 * auseinandergelaufen: Die Rettungsmittel trugen „★ Standard", die uebrigen
 * nicht, und die Loeschrueckfragen lauteten verschieden. Zwei Schliessungen
 * ($sdZeile, $sdForm) rendern es jetzt einmal.
 *
 * Dabei behoben, was O8a noch offen liess: Das wiederhergestellte Lage-Feld
 * trug DIESELBE KENNUNG wie das Namensfeld (`<praefix>addr`) — getElementById
 * fand das erste, und das Lage-Feld war Zierde. Die Kennung gehoert jetzt dem
 * Lage-Feld, der Name hat eine eigene (F-P3-AI, wirklich behoben).
 *
 * Zwei weitere Funde: Der Lupen-Knopf nimmt dem Feld den Fokus, und der
 * blur-Aufschub loeschte die eben gefuellte Vorschlagsliste nach 150 ms —
 * gegen den echten Photon-Dienst faellt das nie auf, hinter einem
 * Zwischenspeicher schon (F-P3-AJ). Und ui_zeilenaktionen() leitete seine
 * Kennung aus einem HASH ueber Titel und Aktionstexte ab; zwei gleichnamige
 * Zeilen bekamen dieselbe, und `data-blatt` oeffnete beide oder keines —
 * jetzt eine laufende Nummer.
 *
 * 9.7.2 ist O8c und schliesst O8 ab: DER BACKUP-REITER UND DER IMPORT.
 * Beide sind lange Wege mit vielen Zwischenmeldungen, und beide meldeten sie
 * bis hierher in EINER grauen Zeile — ein misslungener Export sah aus wie ein
 * Zwischenstand. Jetzt tragen die Meldungen ihren TON (E-P3-16): rot fuer
 * einen Fehlschlag, blau mit Haken fuer ein Ergebnis, schlicht fuer den
 * laufenden Fortschritt. Ein Fortschrittstext bekommt bewusst KEIN Symbol —
 * er ist kein Ergebnis, und ein Haken daneben behauptete eines.
 *
 * Ein Sonderfall dabei: Ein Export mit unlesbaren Bloecken ist KEIN reiner
 * Erfolg. Die Datei ist vollstaendig, aber ein Teil ihrer Angaben laesst sich
 * nur in diesem Konto wieder oeffnen — das meldet sich jetzt als Warnung
 * statt mit einem Haken.
 *
 * Der Import zeigt seine drei Schritte als drei KARTEN mit der Zahl im Kopf;
 * Schritt 2 und 3 bleiben verborgen, bis der vorige getan ist. Die
 * Zeilenwahl (Alle / Nur Probleme / Nur Dubletten) ist eine Segmentwahl
 * geworden — drei Zustaende, von denen genau einer gilt; die
 * Pfeiltastenbedienung bringt der Browser mit. Die Haken des Exports sind
 * Schalter (E-P3-28).
 *
 * Keine Migration. Damit ist O8 vollstaendig: Profil und Logo-Wahl (O8a),
 * die Verwaltungslisten (O8a/O8b), Sicherung und Import (O8c).
 *
 * 9.8.0 ist O9a: DIE KONTOSEITE ALS DREHSCHEIBE (E-P3-41). O9 ist mit fuenf
 * Seiten, drei Funktionsaenderungen und einer Migration erneut zu gross fuer
 * einen Zug; es zerfaellt in Kontoseite (O9a), NutzerInnen-Liste (O9b) und
 * Regeln, Stammdaten, Demo, Wartung (O9c).
 *
 * !!! DIESE FASSUNG BRAUCHT EINE MIGRATION !!!
 * `2026_08_28_last_login` legt `users.last_login` an. Ohne die Spalte zeigen
 * Kontoseite und NutzerInnen-Liste kein „zuletzt angemeldet"; die Anmeldung
 * selbst laeuft weiter (login.php faengt den Fall). Nach dem Ausrollen also
 * update.php aufrufen — dringlich ist es nicht, notwendig schon.
 *
 * WARUM EINE SPALTE, DIE IM KONZEPT NICHT STAND. E-P3-41 verlangt „zuletzt
 * angemeldet" in der Unterzeile der Kontoseite UND als Spalte der Liste; die
 * Migrationsliste des Konzepts nennt sie nicht. Es gab dafuer bisher keine
 * Quelle: `devices.last_seen` ist der Stand einer UHR, nicht der einer
 * Anmeldung. Der Bestand bekommt NULL und nicht NOW() — sonst saehe jedes
 * Konto so aus, als haette es sich am Tag der Migration angemeldet, und genau
 * in dieser Spalte sucht man ungenutzte Konten.
 *
 * ALLES ZU EINEM KONTO LIEGT JETZT AUF DESSEN SEITE. Vorher waren die
 * Kontodaten drei Formulare mit drei Speichern-Knoepfen (Rolle, E-Mail,
 * Name), und die SICHERUNGEN des Kontos standen woanders — auf
 * admin_sicherungen.php, in einer Tabelle ueber alle Konten, in der man
 * seine Zeile suchen musste. Jetzt: EIN Formular mit EINEM Speichern, dazu
 * Karten fuer Geraete, Sicherungen, Abonnement (reservierter Platz, R33) und
 * die Loeschung als rote Gefahrenzone. Ab 1200 px zweispaltig.
 *
 * DAS IST AUCH EINE ANTWORT AUF DIE MENGE. Die alte Uebersicht las fuer
 * JEDES Konto ein Verzeichnis und eine Begleitdatei, um eine einzige Zeile
 * zu zeigen — Arbeit, die mit der Zahl der Konten waechst, obwohl man immer
 * nur ein Konto ansieht. edbak_konto_stand() liest genau einen Ordner.
 *
 * DREI HANDLUNGEN BRAUCHEN MEHR ALS EINE RUECKFRAGE — Einspielen, Freigeben,
 * Loeschen einer Sicherung. Sie stehen in Dialogen (neu: assets/dialog.js),
 * die im Markup stehen und ihre Werte vom oeffnenden Knopf bekommen; EIN
 * Dialog fuer alle Zeilen statt eines je Zeile. Geprueft wird serverseitig.
 * Das Einspielen zielt auf DIESES Konto — ein Auswahlfeld mit allen Konten
 * stuende fuer einen Fall, den es hier nicht gibt.
 *
 * DIE AUFBEWAHRUNG IST EINSTELLBAR GEWORDEN (E-P3-41): `EDBAK_MAX_JE_KONTO`
 * war fest verdrahtet, jetzt liest edbak_aufbewahrung() den Wert aus
 * app_state (die Einstellung dazu entsteht in O9c; die Vorgabe bleibt drei,
 * damit ein Bestand ohne Einstellung sich verhaelt wie vorher). Zwei Pakete
 * sind von der Verdraengung ausgenommen: das juengste — sonst raeumte eine
 * Aufbewahrung von 0 beim Sichern alles weg — und ein freigegebenes, weil
 * die NutzerIn es im eigenen Backup-Bereich angeboten bekommt.
 *
 * „PASSWORT ZURUECKSETZEN" ist neu im Aktionsmenue und setzt KEIN Passwort:
 * Es verschickt denselben Link wie „Passwort vergessen". Kommt die Mail
 * nicht weg, steht der Link auf der Seite — ein gueltiger Token, von dem
 * niemand weiss, ist die schlechteste aller Lagen (Muster aus admin_users).
 *
 * Bewusst gekuerzt: Die Umfangszeile einer Sicherung nannte den Papierkorb
 * bisher nach Art aufgeteilt („5 Einsätze, 1 Diensttag, 5 Ruhezeiten"). In
 * einer Kartenzeile waren das drei Zeilen Umbruch fuer eine Frage, die eine
 * Zahl beantwortet: wie viel davon ist geloeschter Bestand. Jetzt „davon 11
 * im Papierkorb"; das Paket selbst fuehrt die Zahlen weiter je Art.
 *
 * 9.9.0 ist O9b: DIE NUTZERINNEN-LISTE, AUSGELEGT AUF MEHRERE HUNDERT KONTEN.
 * Keine Migration.
 *
 * Vorher war es eine ungefilterte Tabelle ueber ALLE Konten mit vier Spalten,
 * ein Anlegen-Formular darunter und je Zeile ein Loeschknopf. Jetzt: vier
 * Statuskacheln (jede ein Weg in die Liste, die sie meint), Suche nach Name
 * oder Adresse, fuenf Filterplaketten mit Zahl, sechs sortierbare Spalten,
 * FUENFZIG Konten je Seite mit Seitenwechsel, Auswahlkaestchen und eine
 * klebende Sammelleiste, deren Auswahl UEBER SEITEN HINWEG gilt. Das Anlegen
 * ist ein Dialog im Kartenkopf; das Loeschen steht nur noch auf der
 * Kontoseite, wo die Entscheidung ueber die Sicherungen dazugehoert (E25).
 *
 * WO DIE ARBEIT LIEGT. Der Sicherungsstand eines Kontos steht nicht in der
 * Datenbank, sondern im Dateisystem — daran haengen zwei Kacheln, zwei Filter
 * und eine Spalte. Ihn je Zeile zu holen waeren bei 300 Konten 300
 * Verzeichnisdurchlaeufe. edbak_staende() macht daraus EINEN Durchlauf der
 * Ablagewurzel plus je Ordner eine kleine JSON-Datei; wer nie gesichert
 * wurde, hat gar keinen Ordner und kostet nichts. Gemessen an 304 Konten:
 * 3,2 ms Ablage, 3,3 ms Abfrage, 3,2 ms Werten.
 *
 * DER PREIS steht im Code: Die Angabe stammt aus konto.json, nicht aus den
 * Paketdateien. Wer ein Paket von Hand entfernt, sieht in der LISTE einen
 * Stand, den es nicht mehr gibt — die KONTOSEITE zeigt dann das Richtige,
 * weil sie die Dateien zaehlt.
 *
 * DABEI GEMESSEN UND BEHOBEN: edbak_intervall() fragte je Zeile die Datenbank.
 * Bei 304 Konten waren das 304 Abfragen und 27,7 ms fuer eine Rechnung aus
 * einer Subtraktion; mit einem Zwischenspeicher je Anfrage sind es 3,2 ms.
 *
 * UMLAUTE SORTIEREN JETZT RICHTIG. `mb_strtolower` macht aus Ö ein ö, und ö
 * liegt in der Byte-Reihenfolge hinter z: „Ömer" stand an erster Stelle der
 * ABSTEIGENDEN Sortierung, also hinter allem. sortschluessel() schreibt
 * Umlaute nach deutscher Lesart aus (ae/oe/ue/ss) — dieselbe Regel wie in
 * slug() (assets/export.js) — und fuehrt uebrige Akzente auf den
 * Grundbuchstaben zurueck. Kein `Collator`: intl ist auf geteiltem Webspace
 * nicht verlaesslich da, und eine Sortierung, die je nach Installation anders
 * ausfaellt, ist schlimmer als eine, die ueberall gleich naeherungsweise ist.
 *
 * ZWEI FUNDE AUS DER PRUEFUNG DIESES PAKETS:
 *
 *   F-P3-AL  Die Nachladeknoepfe der gemeinsamen Einsatztabelle („Weitere 200
 *            anzeigen", „Alle n anzeigen") trugen noch `btn-plain` — eine
 *            Klasse ohne Regel im neuen Stylesheet. Sie standen seit dem
 *            Redesign in der Grundform des Browsers. Aufgefallen ist es
 *            niemandem, weil sie erst ab 200 Treffern erscheinen und der
 *            Referenzbestand 88 Einsaetze hat.
 *
 *   F-P3-AM  ZWEI KLASSENKOLLISIONEN, beide vor dem Festschreiben abgefangen
 *            — und jede von einem anderen Pruefmittel. `.filterzahl` gehoert
 *            seit O6 den Zaehlern der Filtergruppen auf der Suchseite; die
 *            neue Regel haette deren Hintergrund ueberschrieben (gefunden
 *            durch LESEN). `.filterknopf` gehoert seit O6 dem Knopf, der dort
 *            die Filterschublade oeffnet — und der ist 48 px hoch, die
 *            einzige benannte Ausnahme von der 44-px-Regel; die neue Regel
 *            haette ihn auf 44 gesetzt (gefunden vom BILDERLAUF, achtmal:
 *            „15-suche · Filter 0 · 44 px (soll 48)"). Jetzt `.listenfilter`
 *            und `.listenfilter-zahl`.
 *
 *            Die Lehre ist nicht „vorher greppen", sondern: nach jedem Paket
 *            auch die Seiten mitmessen, die es NICHT anfasst. Die
 *            Vollstaendigkeitspruefung haette beides nicht gemeldet — sie
 *            zaehlt Klassen OHNE Regel, nicht zwei Regeln fuer EINE Klasse.
 *
 * NEU ALS PRUEFMITTEL: tools/pruefkonten/ legt 300 Konten mit gemischten
 * Sicherungsstaenden an und entfernt sie wieder — reproduzierbar, weil der
 * Zufall einen festen Startwert hat.
 *
 * NEUN FUNDE AUS DER GEGNERISCHEN PRUEFUNG des Stands, alle behoben. Die
 * beiden, die am weitesten reichten:
 *
 *   edbak_verdraengen() schonte eine EINGELOESTE Freigabe dauerhaft. Die
 *   Ausnahme war damit begruendet, dass die NutzerIn das Paket angeboten
 *   bekommt — nach dem Einloesen stimmt das nicht mehr, und die eingestellte
 *   Aufbewahrung wurde still ueberschritten, fuer immer.
 *
 *   Ein fehlgeschlagener Sicherungslauf liess einen LEEREN Ordner zurueck
 *   (mkdir stand vor edbak_build()). Die Liste meldete dann „Stand
 *   unbekannt", die Kontoseite „nie gesichert" — zwei Seiten, zwei Antworten
 *   aus demselben Fehlschlag. Der Ordner entsteht jetzt erst, wenn es etwas
 *   hineinzulegen gibt.
 *
 * Dazu: Die Statuskacheln behielten beim Klick die Suche und lieferten dann
 * weniger, als sie versprachen. Jedes Konto hatte zwei Auswahlkaestchen im
 * Markup (Tabelle und Kachelzeile), von denen nur eines nachgefuehrt wurde.
 * Die Auswahl im sessionStorage ueberlebte den Wechsel der angemeldeten
 * Person. `?q[]=x` erzeugte „Array to string conversion". Ein kaputtes
 * konto.json (Zahl statt Zeichenkette) haette unter strict_types die ganze
 * Liste lahmgelegt, und ein unbrauchbarer Zeitwert haette das Konto mit
 * zwanzigtausend Tagen als dringendsten Fall nach oben sortiert.
 *
 * 9.10.0 ist O9c: DIE DREI UEBRIGEN ADMINSEITEN. Keine Migration.
 *
 * SICHERUNGEN ist nicht mehr die Liste aller Konten — die steht seit 9.9.0 in
 * der NutzerInnen-Liste, und die Pakete eines Kontos seit 9.8.0 auf dessen
 * Kontoseite. Was bleibt, ist das, was fuer ALLE gilt: vier Zahlen, die Regeln
 * (Erinnerungsintervall, Aufbewahrung je Konto, Erinnerungsmail) in EINEM
 * Formular mit EINEM Speichern, der Zustand der Ablage und die Sicherungen
 * ohne Konto. „Alle sichern" arbeitet die faelligen Konten ab, das aelteste
 * zuerst, in einem Zeitbudget von 20 Sekunden; wer nicht mehr hineinpasst,
 * ist beim naechsten Klick der aelteste und kommt zuerst — die Reihenfolge
 * sorgt selbst dafuer, dass es konvergiert.
 *
 * DIE WOECHENTLICHE ERINNERUNG (E-P3-41) haengt am Aufraeumjob, weil es auf
 * diesem Webspace keinen Cron gibt. Sie kommt hoechstens einmal je Woche, nur
 * wenn ueberfaellige Konten da sind, und nur wenn die Anwendung an dem Tag
 * ueberhaupt benutzt wurde — das steht so auf der Seite, denn eine Zusage,
 * die an der Benutzung haengt, muss man als solche kennzeichnen. Verschickt
 * wird NACH der Antwort (register_shutdown_function): Der Aufraeumjob laeuft
 * vor der Seitenausgabe, und ein SMTP-Gespraech dort waere eine messbare
 * Verzoegerung fuer jemanden, der damit nichts zu tun hat. In der Mail stehen
 * Adressen und Tage, keine Namen und keine Zahlen aus den Konten.
 *
 * STAMMDATEN SYSTEMWEIT ist EIN Menuepunkt statt zweier. „Standorte
 * systemweit" und „Rettungsmittel systemweit" zeigten auf dieselbe Datei mit
 * demselben Symbol und unterschieden sich nur im Reiter; der Reiter ist jetzt
 * eine Segmentwahl in der Titelzeile. Dabei ist das Markup der Zeilen und
 * Formulare nach server/stammdaten_ui.php gewandert — es stand bis 9.9.0 zu
 * grossen Teilen zeichengleich in zwei Dateien.
 *
 * DEMO-KONTO war seit dem Redesign eine ungestaltete Seite: `table.data`,
 * `pre.mono`, `div.rowactions`, `button.btn-primary` — Klassen, deren Regeln
 * in den Bausteinen aufgegangen sind. Jetzt vier Kacheln fuer den Bestand,
 * die Papierkorbzahlen als Kontrollzeilen, die Handlungen in der Titelzeile.
 * Das Pruefwerkzeug tools/referenzdatensatz/browser/demo_pruefen.mjs las die
 * alte Tabelle und ist mitgezogen.
 *
 * DER LOGO-STANDARD ist einstellbar (E-P3-19/20) — in der Wartung, weil er
 * eine Eigenschaft der Installation ist und nicht eines Kontos. Er wirkt
 * SOFORT, auch fuer bereits angemeldete Konten: In der Sitzung steht seit
 * jetzt die WAHL und nicht mehr ihr Ergebnis; nur „wechselnd" wird bei der
 * Anmeldung ausgewuerfelt, sonst spraenge das Logo beim Blaettern. Wer im
 * Profil eine eigene Wahl getroffen hat, bleibt unberuehrt.
 *
 * DREI FUNDE AUS DIESEM PAKET:
 *
 *   F-P3-AN  logo_src() — die Funktion fuer die beiden Seiten OHNE Sitzung
 *            (Anmeldung, Passwort setzen) — las `app.logo_path` aus der
 *            config.php und ignorierte die Logo-Wahl. Der Einrichter schreibt
 *            dort den Hubschrauber hinein; die Anmeldeseite zeigte damit nie
 *            den Standard der Installation, obwohl E-P3-20 genau das zusagt.
 *            `logo_path` gilt jetzt nur noch fuer eine FREMDE Datei.
 *
 *   F-P3-AO  Die Standorteliste war die einzige der sechs Stammdatenlisten
 *            ohne den weichen Hinweis auf gleichnamige eigene Eintraege. Ein
 *            systemweiter Standort, den ein Dutzend Konten bereits selbst
 *            angelegt hatte, entstand ohne jeden Hinweis — und stand danach
 *            zweimal in deren Auswahlliste.
 *
 *   F-P3-AP  Die Radios der Segmentwahl waren 20 x 20 px gross statt 0 x 0:
 *            `.segment-box` (0,1,0) verliert gegen `input[type=radio]`
 *            (0,1,1) im Abschnitt davor. Absolut positioniert und
 *            durchsichtig lagen sie ueber der Umgebung und fingen Klicks ab.
 *            Das betraf JEDE Segmentwahl — Zeitraum, Suchfilter, die neuen
 *            Reiter. Aufgefallen beim Bedienen im Browser („intercepts
 *            pointer events"), nicht beim Lesen.
 *
 * Dazu behoben: Die Sammelleiste der NutzerInnen-Liste zeigte ihre Zahl in
 * jeder Breite, aber der Knopf daneben war unter 720 px 100 % breit — die
 * Zahl brach auf zwei Zeilen. Die Breitenausnahme haengt jetzt an der Zahl
 * statt an der Schwelle.
 *
 * 9.10.1 REPARIERT DREI DINGE, DIE VOR O10 STEHEN MUSSTEN. Keine Migration —
 * aber schema.sql aendert sich, und das betrifft NEUINSTALLATIONEN.
 *
 * DER EINRICHTER WAR TOT. install.php lud ui.php erst INNERHALB von
 * render_page(); die Aufrufer bauen ihr Argument aber mit
 * ui_meldung_markup(), ui_knopf() und ui_symbol(), und PHP wertet Argumente
 * VOR dem Aufruf aus. Alle drei Zweige endeten in „Call to undefined
 * function" — seit Web 9.1.0, also seit O2. Das traf JEDE Neuinstallation:
 * index.php leitet ohne config.php dorthin, und der Deploy liefert die Datei
 * aus. Niemandem aufgefallen, weil der Einrichter genau einmal im Leben
 * einer Installation laeuft und die bestehende laengst laeuft. Die Huelle
 * wird jetzt am Dateianfang geladen.
 *
 * schema.sql WAR ZWEI MIGRATIONEN IM RUECKSTAND. `users.last_login` (Web
 * 9.8.0) fehlte als SPALTE, und die Kennungen der beiden Migrationen
 * 2026_08_27_logo_wahl und 2026_08_28_last_login fehlten in der
 * Erledigt-Liste. Eine frisch eingerichtete Anwendung haette die Spalte gar
 * nicht gehabt; die Nachtragsmigrationen waeren erneut angesetzt und
 * entweder haengengeblieben oder — schlimmer — still durchgelaufen, weil
 * update.php MySQL 1060 („Duplicate column") schluckt.
 *
 * DIE BILDAUFNAHME FOTOGRAFIERTE DIE ANMELDESEITE. Das ist der schwerste
 * der drei Funde, weil er ein PRUEFMITTEL betrifft: Der Lauf meldete „31
 * Seiten, 0 Ueberlauf, 0 Konsolenfehler", und 22 dieser 31 Seiten waren
 * Bilder von login.php — 176 von 248 Einzelbildern, byteweise identisch
 * (nachgewiesen mit md5sum: 23 Dateien je Breite mit derselben Pruefsumme).
 *
 * Zwei unabhaengige Ursachen, beide behoben:
 *
 *   1. DIE SITZUNG STARB MITTEN IM LAUF. Das Demo-Konto setzt sich alle 30
 *      Minuten zurueck, und demo_zuruecksetzen() erhoeht dabei die
 *      Sitzungs-Epoche; auth_guard.php beendet daraufhin jede offene
 *      Sitzung. Der Lauf braucht Minuten und loest den faelligen Reset durch
 *      seine EIGENEN Anfragen aus. Die alte Pruefung stand einmal,
 *      unmittelbar nach dem Anmelden — danach hat nichts mehr hingesehen.
 *      Jetzt prueft die Aufnahme nach JEDEM Seitenaufruf, meldet sich bei
 *      Bedarf neu an und wiederholt einmal; hilft das nicht, entsteht KEIN
 *      Bild, sondern ein Fehler.
 *
 *   2. VIER PLATZHALTER WURDEN NIE AUFGELOEST. Die Kennungen der
 *      Einsatzseiten holt platzhalter() aus der Tagesuebersicht — und lief
 *      als erste Funktion des Laufs in denselben Sitzungsverlust. Fehlte
 *      die Kennung, war das Verzeichnis LEER, und die vier Seiten wurden mit
 *      ihrem eigenen Platzhalter als Adresse aufgerufen; der Server
 *      antwortet darauf mit 200 und der Startseite. Ein nicht aufgeloester
 *      Platzhalter ist jetzt ausdruecklich `null` und fuehrt dazu, dass die
 *      Seite nicht fotografiert wird.
 *
 * NACH DER REPARATUR: 248 Bilder, 248 VERSCHIEDENE Pruefsummen, alle sieben
 * Platzhalter aufgeloest, ein bemerkter und behobener Sitzungsverlust im
 * Bericht. Die Zahlen aus O9c sind im Konzept berichtigt (F-P3-AQ).
 *
 * Dazu: Vier Wortlisten-Ausnahmen fuer die Logo-Abschnitte der Dokumentation
 * (O9c hatte die Doku nach dem Lauf des Werkzeugs geschrieben; die Pruefung
 * stand auf 5 Treffern, gemeldet worden waren 0).
 *
 * !!! DIESE FASSUNG BRAUCHT EINE MIGRATION !!!
 * Nach dem Aufspielen muss eine Administratorin update.php aufrufen. Ohne den
 * Aufruf gibt es die Tabelle `rechtstexte` nicht; Impressum und Datenschutz
 * zeigen dann ihren Leerzustand — die Anwendung laeuft weiter, aber die neue
 * Funktion ist nicht da (rechtstexte_lib.php faengt die fehlende Tabelle ab).
 *
 * 9.11.0 ist O10: ANMELDUNG, OEFFENTLICHE SEITEN UND RECHTSTEXTE (R32).
 *
 * DIE ANWENDUNG HAT ZUM ERSTEN MAL EIN IMPRESSUM UND EINE
 * DATENSCHUTZERKLAERUNG — und zwar keine mitgelieferten. Was darin steht, ist
 * Sache des Betreibers; wir stellen zwei oeffentliche Seiten, einen Editor in
 * der Administration und die Verweise in jeder Fusszeile. Der Leerzustand ist
 * die Auslieferung und eine gueltige Antwort: „Der Betreiber dieser
 * Installation hat noch kein Impressum hinterlegt", fuer angemeldete Admins
 * mit dem Weg zum Editor daneben.
 *
 * DER RENDERER MASKIERT ZUERST UND ERKENNT DANN STRUKTUR. rt_html()
 * (rechtstexte_lib.php) ist die einzige Stelle des Projekts, an der aus einer
 * Eingabe HTML wird. Sie schickt den GANZEN Text durch htmlspecialchars,
 * bevor der Parser das erste Zeichen ansieht — rohes HTML ist damit nicht
 * gefiltert, sondern unmoeglich. Eine Sperrliste von Tags waere der falsche
 * Ansatz; sie ist immer unvollstaendig, und die Luecke findet man erst, wenn
 * sie jemand benutzt hat.
 *
 * Erzeugt werden ausschliesslich h2, h3, p, br, ul, ol, li und a mit href.
 * Linkziele stehen auf einer POSITIVLISTE (https, http, mailto, eigene .php,
 * Anker) — javascript:, data:, vbscript:, blob:, file: und alles, was es
 * morgen gibt, fallen ohne eigenen Eintrag durch. Ein abgelehntes Ziel laesst
 * die ganze Konstruktion als TEXT stehen: Stilles Schlucken macht aus einem
 * Fehler eine Unsichtbarkeit.
 *
 * GEPRUEFT MIT tools/rechtstexte/: 81 Proben (rohes HTML, Linkziele,
 * Attribut-Ausbruch, Autolinks, Bidi-Steuerzeichen, Kodierung, Raender),
 * dazu 65 Ausgaben gegen eine Positivliste erlaubter Tags und Attribute
 * gehalten — die eigentliche Pruefung: Sie sagt nicht, was schiefgehen kann,
 * sondern dass nichts anderes herauskommt.
 *
 * DIE ABLAGE IST EINE EIGENE TABELLE, nicht app_state. Dort ist der Wert
 * VARCHAR(190); eine Datenschutzerklaerung hat 8000 bis 20000 Zeichen. Ohne
 * strict mode kuerzt MySQL still — ein Rechtstext, der ab Zeichen 191
 * verschwindet, sieht in der Vorschau vollstaendig aus, solange niemand ans
 * Ende scrollt.
 *
 * DAS STANDDATUM WIRD VON HAND GESETZT. Automatisch waere bequemer und an
 * einem Rechtstext falsch: Das Datum sagt, auf welchem Stand der Text
 * INHALTLICH ist — eine Kommakorrektur soll ihn nicht neu datieren.
 *
 * DIE FUSSZEILE FUEHRT JETZT IMMER auf beide Seiten. Die is_file()-Pruefung
 * von O2 war richtig, solange es die Seiten nicht gab, und danach tote Logik:
 * zwei Dateisystemzugriffe je Seitenaufruf fuer eine Frage, deren Antwort
 * feststeht. Ausnahme bleibt der Einrichter — er laeuft vor der
 * Ersteinrichtung, die beiden Seiten brauchen aber eine Datenbank.
 *
 * DER EINRICHTER TRAEGT DIE OEFFENTLICHE HUELLE. Er hatte die Anmeldehuelle
 * (dunkelblaue Flaeche, 400-px-Karte) und half sich mit `.anmeldung-breit` —
 * der Lesespalte unter falschem Namen. Jetzt: helle Lesespalte, fuenf Karten
 * statt fuenf <fieldset>, alle Felder ueber ui_feld(). Das Konzept
 * widersprach sich an dieser Stelle (E-P3-38 gegen Tabelle 5.4); es gilt die
 * Tabelle.
 *
 * ANMELDUNG, PASSWORT-VERGESSEN UND PASSWORT-SETZEN sind jetzt DREI SEITEN
 * DERSELBEN FAMILIE: gleiche Kartenbreite (400 px), gleiches Logo, gleiche
 * Bausteine. Die Passwortseite war 760 px breit, die Anmeldung daneben 400 —
 * zwei Seiten, die man unmittelbar nacheinander sieht, sprangen dabei.
 *
 * KEIN DEMO-HINWEIS AUF DER ANMELDESEITE. E-P3-38 sieht ihn vor, Mockup 32
 * zeigt ihn mit Zugangsdaten. Entschieden wurde dagegen: Die Anmeldeseite
 * einer Anwendung mit Patientendaten ist nicht der Ort fuer ein
 * Werbefeld — und die Zugangsdaten stehen ohnehin in README und Handbuch.
 * Im Konzept ausgetragen.
 *
 * DREI FUNDE AUS DIESEM PAKET:
 *
 *   F-P3-AS  Das div mit der Klasse `login-wrap` in pw_handling.php war
 *            NICHT GESCHLOSSEN (drei <div>, zwei </div>) und hatte im neuen
 *            Stylesheet keine Regel. Es stand zwischen `.anmeldung-body` und
 *            `<main class="anmeldung">`; damit war main kein direktes
 *            Flex-Kind mehr, `flex:1 1 auto` griff nicht, und die Fusszeile
 *            klebte unter der Karte statt am unteren Rand.
 *
 *   F-P3-AT  Die Fusszeile zeigte im Einrichter ein nacktes „v" ohne Zahl:
 *            WEB_VERSION ist dort nicht definiert (version.php kommt ueber
 *            db.php, und das braucht die config.php, die es noch nicht gibt).
 *            Eine Auskunft, die keine ist.
 *
 *   F-P3-AU  `.seiten-erklaerung` hat einen NEGATIVEN Rand oben, abgestimmt
 *            auf die Titelzeile darueber. Unter einem blanken <h1> — auf den
 *            oeffentlichen Seiten und im Einrichter, die kein Geruest und
 *            damit keine Titelzeile haben — zog er den Erklaertext an die
 *            Ueberschrift heran.
 *
 * Dazu zwei freigegebene Aenderungen an geteilten Bausteinen: Die
 * Versionsnummer der Fusszeile steht in --gedaempft statt --sand (1,53:1 auf
 * 5,30:1 — sie ist die Auskunft, mit der ein Fehlerbericht anfaengt, also ein
 * zu LESENDER Text), und „Passwort vergessen?" steht linksbuendig statt
 * zentriert.
 *
 * 9.11.1 — VIER REPARATUREN AN GETEILTEN BAUSTEINEN, gefunden beim Aufraeumen
 * vor O11. Sie stehen vor dem Paket und nicht darin, weil sie nichts mit den
 * neun Seiten zu tun haben, die O11 umbaut: Jede von ihnen war schon vorher
 * kaputt, und drei davon an Stellen, die O11 gar nicht anfasst.
 *
 *   F-P3-AW  DER VOLLBILDKNOPF DER KARTE TAT AUF iOS NICHTS. map_fullscreen.js
 *            nimmt die Fullscreen-API, wo es sie gibt, und sonst einen
 *            CSS-Rueckfall ueber die Klassen `map-fs` und `map-fs-lock`.
 *            Diese beiden Klassen haben seit dem Neubau des Stylesheets
 *            (9.0.0) keine Regel mehr — der Rueckfall war also seit vier
 *            Monaten tot. Gemessen: 366 x 160 px vor wie nach dem Druck, nur
 *            die Beschriftung wechselte auf „Vollbild verlassen". Jetzt
 *            390 x 800 px. Unbemerkt geblieben, weil der Weg nur auf iOS
 *            Safari genommen wird und die Bildaufnahme den Vollbildzustand
 *            nicht herstellt.
 *
 *   F-P3-AX  „LOESCHEN" WAR IM BLATT NICHT ROT. ui_zeilenaktionen() vergab
 *            `knopf-gefahr` auch im Blatt; dort setzt aber `.blatt-zeile`
 *            seine Schriftfarbe selbst, mit gleicher Spezifitaet und spaeter
 *            in der Datei — also gewinnt sie. Gemessen an „Loeschen" in der
 *            Stammdatenliste: rgb(26,5,0), dieselbe Farbe wie „Bearbeiten";
 *            jetzt rgb(158,34,38). Betroffen waren sechs Aufrufstellen,
 *            darunter „Geraet entkoppeln" und „Konto loeschen" — mobil sah
 *            die unumkehrbarste Handlung der Anwendung harmlos aus.
 *
 *   F-P3-AY  ZWEI RUECKFRAGEN HINTEREINANDER. Ein Formular mit `data-confirm`
 *            UND `data-dirty-track` fragte nach der bestaetigten Rueckfrage
 *            ein zweites Mal, diesmal der Browser: „Aenderungen werden
 *            moeglicherweise nicht gespeichert." Ursache ist das
 *            stopPropagation() der Erfassungsphase in confirm.js — forms.js
 *            haengt in der Blasenphase und erfaehrt vom Absenden nie.
 *            confirm.js sagt jetzt ab. Betroffen war diensttag_datum.php,
 *            und dort praktisch immer.
 *
 *   F-P3-AZ  DAS UNSICHTBARE KAESTCHEN LAG NICHT, WO ES SOLLTE. `.schalter-box`
 *            und `.wahl-box` (0,1,0) verlieren gegen `input[type=checkbox]`
 *            aus den Grundformen (0,1,1), die jedem Kaestchen 20 x 20 px geben.
 *            Gemessen: 20 x 20 statt 0 x 0, und weil keine Huelle
 *            `position:relative` traegt, sass das Kaestchen ueber dem linken
 *            Rand der Beschriftung. Dieselbe Falle wie F-P3-AP, drittes Mal.
 *
 * Dazu eine freigegebene Aenderung: Der Rueckfragedialog hat jetzt eine
 * Ueberschrift („Bestaetigen", je Aufrufstelle ueberschreibbar) und
 * `role="alertdialog"`. Er war die anonymste Stelle der Oberflaeche — ein
 * Screenreader las Text und zwei Knoepfe, ohne zu sagen, was da fragt.
 *
 * 9.12.0 — O11: DIE UEBRIGEN SEITEN, UND DIE UEBERGANGSSCHICHT FAELLT.
 *
 * Neun Seiten sind aus Bausteinen neu gebaut: Papierkorb, Zuordnung
 * nachtragen, Diensttag anlegen / Datum aendern / loeschen /
 * zusammenfuehren, Einsatz verschieben / loeschen und die Wartungsseite.
 * Damit ist keine Seite der Anwendung mehr im alten Zustand.
 *
 * ES GIBT KEINE VERWALTUNGSTABELLE MEHR. Sechs Tabellen sind zu Karten mit
 * Zeilen geworden — der Papierkorb hatte fuenf Spalten, die Wartungsseite
 * vier, die Zusammenfuehrung sechs. Bei 360 px lief jede von ihnen waagerecht
 * aus dem Bild; die Notbremse `table{display:block;overflow-x:auto}` hat das
 * abgefangen, aber abgefangen ist nicht geloest. Geblieben sind die drei
 * Einsatztabellen (Tagesuebersicht, Suche, Zeitraum), die unter 720 px zur
 * Kachel werden, und die Importtabelle — sie tragen alle `.tabelle`.
 *
 * LOESCHBESTAETIGUNGEN BLEIBEN SEITEN, keine Dialoge. Was dort steht, ist
 * eine Aufstellung — Einsaetze, Phasen, Reanimationen, Ruhesegmente,
 * Trackpunkte —, und eine Aufstellung gehoert nicht in einen
 * Rueckfragedialog. Der ist fuer das Gegenteil da: eine Handlung, die sich in
 * einem Satz beschreiben laesst. Die Aufstellungen selbst sind von
 * Aufzaehlungen zu Zeilen mit Plakette geworden: Die Zahl ist die Auskunft,
 * und im Fliesstext war sie beim Ueberfliegen nicht zu finden.
 *
 * KEINE SPEICHERN-LEISTE AUF DIESEN SEITEN. Sie gehoert zu Formularen, die
 * man BEARBEITET und deren Stand man verlieren kann. Hier ist der Knopf das
 * Ziel des Weges und steht am Ende des Formulars, wo man ihn sucht.
 * `data-dirty-track` bleibt trotzdem — es traegt die Verlassen-Warnung und
 * die bedingte Abbrechen-Rueckfrage; die Leiste ist nur einer seiner
 * Verwender.
 *
 * DIE UEBERGANGSSCHICHT IST AUFGELOEST. Abschnitt 17 des Stylesheets hiess
 * ROHSCHICHT und war ausdruecklich befristet: „dieser Block stirbt mit O11".
 * Er tut es. Weg sind die beiden Klassen-Ausnahmen `.alert` und `.muted`
 * (zuletzt 1 und 16 Stellen), die Elementregeln fuer `table`/`th`/`td`
 * (die letzte Tabelle ohne eigene Regel war die des Imports), fuer
 * `fieldset`/`legend`
 * und fuer `hr` (jeweils null Verwendungen). Der Abschnitt heisst jetzt
 * GRUNDFORMEN und traegt nur noch, worauf die Bausteine aufsetzen:
 * `input`/`select`/`textarea`, Kaestchen und Radios, das Muster
 * `<label>Text <input></label>`, `summary` und `code`/`kbd`/`pre`.
 *
 * DIE LABEL-REGELN BLEIBEN — abweichend vom urspruenglichen Plan. Das Muster
 * steht an 46 Stellen, darunter die Filterreihen der Suche und das
 * Einsatzformular. Sie zu tilgen hiesse, die beiden kompliziertesten Seiten
 * der Anwendung fuer eine Regel umzubauen, die nichts falsch macht: `.feld`
 * ist der BAUSTEIN fuer ein beschriftetes Feld, nicht das Gebot, dass jede
 * Beschriftung einer sein muesse.
 *
 * ZWEI FUNDE BEIM STREICHEN:
 *
 *   F-P3-BA  DER EXPORT-KNOPF WAR UNGESTALTET. `import.php` trug an einer
 *            Stelle noch `btn-primary` — eine Klasse ohne Regel seit Web
 *            9.0.0. Gemessen: 23 px hoch, ohne Flaeche, ohne Rahmen, ohne
 *            Radius, in der Textschrift; der Nachbarknopf im selben Formular
 *            ist 44 px, orange, Bricolage. O8c hat die Seite umgebaut und
 *            diesen einen Knopf uebersehen.
 *
 *   F-P3-BB  `kreislauf.py --frisch` KONNTE SEIT WEB 9.9.0 KEIN UMLAUFKONTO
 *            MEHR LOESCHEN, und zwar aus zwei Gruenden gleichzeitig: Sein
 *            Ausdruck suchte `<a href="admin_user.php?id=N">adresse</a>` und
 *            fand nichts mehr (die Liste ist seit O9b eine Tabelle mit
 *            `data-ziel` bzw. eine `.zeile` mit gewickeltem Text), und die
 *            Loeschung liegt seit O9a auf der Kontoseite und verlangt die
 *            abgetippte Adresse. Unbemerkt, weil der Weg nur betreten wird,
 *            wenn das Konto schon besteht.
 *
 * 9.13.0 ist O12 und schliesst P3 ab: DIE GESTALTUNGSRICHTLINIE. Keine
 * Migration, keine Aenderung am Datenmodell; am Server aendert sich eine
 * einzige Zeile (siehe F-P3-BC).
 *
 * ZWOELF PAKETE HABEN EINE OBERFLAECHE GEBAUT, ABER KEINE REGEL
 * HINTERLASSEN, die man nachschlagen kann. Das Wissen stand verteilt: die
 * Token im Stylesheet, die Bausteine in `ui.php`, die Begruendungen in den
 * Kopfkommentaren dieser Datei, die Entscheidungen im Konzept. Wer eine neue
 * Seite baut, findet dort alles — aber erst, nachdem er alles gelesen hat.
 * `docs/Design.md` ist die eine Stelle: Marke, Farbrollen, Token, Schrift,
 * Grundregeln, Schwellen, Symbole, Bausteine, Seitentypen, Pruefmittel.
 *
 * DER EINSTIEG IST EINE TABELLE, KEINE EINLEITUNG. Kapitel 9 beginnt mit
 * „Wenn du X willst, nimm Y" — 27 Zeilen von der Absicht zum Baustein. Das
 * ist die Frage, mit der jemand das Dokument aufschlaegt; alles andere ist
 * die Antwort auf die zweite Frage. Am Ende desselben Kapitels stehen die
 * Anti-Muster: zehn Fallen, jede davon in P3 tatsaechlich hineingetreten
 * (Spezifitaet gegen `input[type=checkbox]`, `blatt-gefahr` gegen
 * `knopf-gefahr`, die doppelte Rueckfrage, das fehlende `forms.js`, eine
 * Klasse ohne Regel, `:nth-child` fuer Spaltenbreiten, Unicode statt Symbol).
 *
 * VIER TABELLEN WERDEN ERZEUGT, NICHT ABGESCHRIEBEN. `tools/design/`
 * liest 87 Token aus `:root`, 19 Medienbloecke aus den Schwellen, 44
 * Symboldateien und 32 Bausteine aus `ui.php` und setzt daraus das Markup
 * der Kapitel 4, 7, 8 und 9. Eine abgeschriebene Tabelle ist ab dem ersten
 * Tag falsch; diese ist mit einem Aufruf wieder richtig.
 *
 * DIE LIZENZEN STEHEN JETZT ZUSAMMEN. `docs/Lizenzen.md` nennt die drei
 * Bibliotheken mit Version, Lizenz und SHA-256, die zwei Schriftfamilien,
 * den Symbolvorrat und — getrennt davon — die Dienste, die zur Laufzeit
 * angesprochen werden, wenn die Nutzerin eine Karte oeffnet. Genau diese
 * Trennung fehlte: „keine fremde Quelle zur Laufzeit" gilt fuer Code und
 * Schriften, nicht fuer Kartenkacheln, und das war nirgends gesagt.
 *
 * `docs/Branding.md` IST ABGELOEST und entfernt. Sein Verbindliches steht in
 * `Design.md`, seine drei offenen Punkte sind erledigt: B1 (die Logodateien
 * trugen Naeherungen der Markenfarben) in O1, B2 (keine geschlossene
 * Groessenskala) mit den Schriftstufen, B3 (78 Hexwerte im Stylesheet) mit
 * den Token — heute steht kein Farbwert mehr ausserhalb von `:root`.
 *
 *   F-P3-BC  ZWEI TOTE TOKEN, UND DAHINTER EINE ZU SCHMALE LEISTE. Die
 *            Vollstaendigkeitspruefung meldete `--leiste-filter` und
 *            `--leiste-filter-schmal` als unbenutzt. Sie waren es: Die
 *            Filterleiste der Suche trug seit O6 nur `.leiste` und damit
 *            220/260 px statt der fuer sie vorgesehenen 240/280 px. Sie
 *            traegt mehr als eine Tagesliste — Datum von/bis, drei
 *            Auswahlfelder, Freitext —, und dafuer waren die 220 px zu
 *            knapp. `ui_geruest_start()` vergibt jetzt zusaetzlich
 *            `leiste-filter`. Zwei Pakete lang unbemerkt, weil eine zu
 *            schmale Leiste nicht bricht, sondern nur enger umbricht.
 *
 * EIN PRUEFMITTEL, DAS WIEDER GELESEN WIRD. Die Vollstaendigkeitspruefung
 * meldet Klassen, die im Markup stehen und im Stylesheet keine Regel haben —
 * die Gegenprobe, die in O11 den ungestalteten Export-Knopf fand (F-P3-BA).
 * Genau EIN echter Fund unter 29 Zeilen: acht Bruchstuecke zusammengesetzter
 * Klassennamen (`'plakette-' . $ton` — das Werkzeug liest Zeichenketten,
 * nicht ausgefuehrten Code), fuenfzehn Skriptanker ohne eigenes Aussehen.
 * Eine Liste in diesem Verhaeltnis wird nach dem dritten Mal ueberflogen
 * statt gelesen, und findet dann auch den naechsten echten Fund nicht.
 * `tools/vollstaendigkeit/ohne-regel.md` traegt jetzt zu jedem Namen einen
 * Grund: `[bleibt]` verschwindet aus dem Befund, `[offen]` bleibt einer.
 * 0 ohne eingetragenen Grund statt 29, 6 offen, Befunde 247 -> 224. Und die
 * Liste meldet ihre eigenen toten Eintraege, sonst wird sie in zwei Paketen
 * das, wogegen sie schuetzt.
 *
 * DER STILVERGLEICH WACHT WIEDER. Er ruhte waehrend P3, weil er dort die
 * falsche Frage stellte (jede beabsichtigte Aenderung ist ein Treffer). Neu
 * geeicht auf 13 Fensterbreiten von 360 bis 1920 px — die alten neun endeten
 * bei 500 px und kannten die 390er-Klasse der Telefone nicht. Und die
 * Seitenproben lesen jetzt auch die HTML-Schnipsel aus PHP-Zeichenketten:
 * Der blinde Fleck, vor dem seine LIESMICH seit P0 warnte, ist zu. Gemessen:
 * 228 Klassen vorher, 253 nachher.
 *
 * DAS HANDBUCH BLEIBT STEHEN — ausdrueckliche Entscheidung. Es beschreibt
 * die Bedienung, und die aendert sich bis 1.0 noch; es einmal jetzt und
 * einmal vor der Auslieferung zu schreiben, waere dieselbe Arbeit zweimal.
 * Angepasst wurde nur, was ohne Wert veraltet: die 14 Unicode-Zeichen im
 * Text (kein Bildschirmleser spricht das Kreuzzeichen als „Schliessen") und
 * drei Bildschirmfotos.
 *
 * 9.14.0 — DIE ERSTE RUECKMELDUNGSRUNDE NACH P3. Keine Migration. Vierzehn
 * Punkte aus einer Durchsicht mit Bildschirmfotos, dazu vier Fehler, die
 * dabei ans Licht kamen. Was sie verbindet: Kein einziger davon haette von
 * einem Pruefmittel gefunden werden koennen — sie brechen nichts, sie sehen
 * nur falsch aus.
 *
 *   F-N1-A  DIE SEITENLEISTE LIEF UEBER DIE KOPFLEISTE, und zwar aus zwei
 *           Gruenden in derselben Regel. `.leiste` bekommt ab 1024 px
 *           `position:sticky; top:var(--kopf); inset:auto` — und `inset` ist
 *           die Kurzform fuer alle vier Seiten, setzt das `top` eine Zeile
 *           davor also wieder auf `auto`. Gemessen bei 600 px Scrollhoehe:
 *           Die Leiste stand auf -544 px. Dazu blieb ihr `z-index:60` aus der
 *           Schubladen-Regel stehen, waehrend die Kopfleiste auf 40 liegt —
 *           sie malte darueber statt dahinter. Jetzt steht `inset` ZUERST,
 *           `top` danach, und der z-index geht auf 1 zurueck.
 *
 *   F-N1-L  EIN TOTER STREIFEN UNTER JEDER SEGMENTWAHL. Die Taste ist ein
 *           `<label>`, und die Grundformen geben jedem `label` 12 px Abstand
 *           nach unten. Im Segmentrahmen ist das kein Abstand, sondern Leere
 *           im Kasten: Rahmen 58 px, Tasten 44. Betroffen war jede
 *           Segmentwahl der Anwendung — Wochentage, Dreiwertfilter,
 *           Zeitraum-Reiter, Logo-Wahl. Genau das erklaert zwei
 *           Rueckmeldungen auf einmal („Wochentagauswahl sieht komisch aus",
 *           „Tabelle passt irgendwie nicht").
 *
 *           UND DIE ZWEITE HAELFTE DES FUNDES: `.segment-taste{margin:0}`
 *           allein half nicht. In der Filterleiste stand
 *           `.filterfelder label{margin-bottom:var(--abstand-3)}` — eine
 *           Regel, die GENAU DEN WERT setzt, den die Grundform schon setzt.
 *           Sie tat nichts, ausser mit ihrer hoeheren Spezifitaet (0,1,1) den
 *           Baustein (0,1,0) zu schlagen. Eine Dublette ist nie harmlos: Sie
 *           tut nichts, bis sie etwas verhindert.
 *
 *   F-N1-B  WELCHES FELD VERSCHLUESSELT IST, STAND NICHT MEHR DA. Bis O4 trug
 *           jedes geschuetzte Feld ein Schloss-Emoji; O4 ersetzte sie durch
 *           EINE Meldung ueber den Karten — und verlor damit die Auskunft je
 *           Feld. Sie kommt zurueck, aber auf der richtigen Ebene: In der
 *           Karte „PatientIn" ist alles verschluesselt, das sagt jetzt eine
 *           Plakette am Kartenkopf. Die drei geschuetzten Felder der
 *           Einsatz-Karte (Einsatzort, Beschreibung, Diagnose) stehen
 *           zwischen Klartextfeldern und tragen ihr Schloss einzeln.
 *
 *   F-N1-C  „WECHSELND" GAB ES NUR IM PROFIL, nicht fuer die Installation.
 *           Die Wartung kannte zwei Werte. Der dritte ist nicht einfach
 *           dazugekommen: `logo_stamm()` haette „wechselnd" durchgereicht und
 *           stumm beim Hubschrauber landen lassen — die Einstellung waere da
 *           gewesen und haette nichts getan. Es gibt deshalb
 *           `logo_standard_aufgeloest()`, und der Wuerfel faellt je SITZUNG,
 *           nicht je Seitenaufruf; sonst spraenge das Logo beim Blaettern.
 *           Ein Adminwechsel wirkt trotzdem sofort: Gemerkt wird nur das
 *           Ergebnis des Wuerfelns.
 *
 * DIE UEBRIGEN VIERZEHN, knapp:
 *
 *   Kopfleiste  Wortzeichen „Gen-EM Einsatzdoku" (vorher „Einsatzdoku"), Logo
 *               von 26 auf 34 px, der Kontoname von 13 auf 15 px und auf
 *               dieselbe Zeilenhoehe wie das Wortzeichen — beide Mitten
 *               liegen jetzt auf 28 px. Unter 480 px faellt das Wortzeichen
 *               auf 16 px: Bei 360 px braeuchte es 193 px und hat 187
 *               (F-N1-D).
 *   Startseite  Besatzung und Notizen laufen ab 720 px ueber BEIDE
 *               Rasterspalten — die Besatzung brach in der halben Breite um,
 *               neben einer leeren Spalte (F-N1-E). Das Aktionsmenue steht
 *               auf 400 statt 600; ein 500er-Schnitt von Open Sans existiert
 *               nicht und waere still auf 400 gefallen (F-N1-F).
 *   Tabelle     Spaltentitel zentriert, „Dauer" ohne Umbruch,
 *               „Sekundaertransport" und „Fehleinsatz" mit weichem
 *               Trennzeichen statt hartem <br> (F-N1-G).
 *   Einsatz     Die Reanimations-Karte erscheint nur noch, wenn es eine
 *               Sitzung gibt — sie war die einzige Karte der Seite, die leer
 *               stehen blieb und „keine" sagte (F-N1-H).
 *   Formular    Das Ortsfeld hatte 12 px zwischen Beschriftung und Feld, jedes
 *               andere Feld 4 — „Einsatzort" hing zwischen den Feldern statt
 *               zu seinem zu gehoeren. Der Kleintext unter einem Feld ruecht
 *               an dieses heran. Die Zustandszeile passt in eine Zeile
 *               (gemessen: 480 von 532 px) (F-N1-I).
 *   Suche       Die vier von/bis-Paare tragen ihren Namen jetzt UEBER sich;
 *               „Strecke von (km)" brach in der 280 px breiten Leiste um,
 *               „bis" daneben nicht, und die Felder standen versetzt. Drei
 *               weitere Paare waren dabei zu finden (F-N1-J). „FILTER" 12 →
 *               13 px, die Gruppen 15 → 16 px: Die Ueberschrift war das
 *               kleinste Element in der Leiste, die sie ordnet (F-N1-K).
 *   Einstell.   Die erste Stammdatenliste bekommt Abstand nach oben (F-N1-M);
 *               „luftgebunden" und „bodengebunden" stehen nebeneinander
 *               (F-N1-N); „Kopplungscode erzeugen" steht im
 *               `.listen-form-fuss` wie jeder andere Knopf am Formularende
 *               (F-N1-O).
 *
 * EIN NEUES TOKEN: `--symbol-klein` (16 px), das Zusatzzeichen an einer
 * Beschriftung. Die Symbolskala hiess 20 und 24; 16 setzt sie im selben
 * 4-px-Schritt nach unten fort.
 *
 * 9.14.1 — SIEBEN VERWEISE ZEIGTEN AUF GELOESCHTE BILDDATEIEN. Keine
 * Migration. Der Logo-Wechsel (Commit „Update Logos") hat die Dateien
 * getauscht, ohne den Code nachzuziehen — und weil ein Push auf main mit
 * Aenderungen unter server/ sofort deployt, war der Stand live: ui.php lud auf
 * JEDER Seite ein 404-Favicon, und wer „Fahrzeug" gewaehlt hatte, sah gar kein
 * Logo. Geaendert hat sich allein der DATEISTAMM; der Einstellungswert heisst
 * weiter 'fahrzeug' und steht so in users.logo_wahl und
 * app_state.logo_standard — das spart eine Migration.
 * (Nachgetragen mit 9.15.0: Der Block fehlte, die Datei fuehrt zu jeder
 * Nummer einen.)
 *
 * 9.15.0 — DIE UHR KANN SICH SELBST TRENNEN. Keine Migration.
 *
 * `pair.php` kennt jetzt zwei Anliegen statt einem: koppeln wie bisher, und
 * neu `{"aktion":"trennen"}` mit den Kopfzeilen X-Device-Id und X-Api-Key.
 * Backlog Nr. 14.
 *
 * Der Fall ist die GETEILT GENUTZTE UHR. Bis hierher gab es fuer den Wechsel
 * der Person nur den Weg „neuen Code eintippen". Gelang das nicht — falscher
 * Code, kein Telefon in Reichweite, Geraetegrenze erreicht —, dokumentierte
 * die Uhr stillschweigend weiter auf das VORHERIGE Konto. Niemand sah es ihr
 * an, und die Person davor bekam Einsaetze, die sie nicht gefahren ist.
 *
 * Die Uhr trennt sich deshalb ZUERST ausdruecklich und koppelt erst danach
 * neu. Schlaegt das Koppeln fehl, steht sie sichtbar ohne Kopplung da statt
 * unsichtbar mit der falschen (die Sync-Seite sagt seit Uhr 1.10.1 „Nicht
 * eingerichtet").
 *
 * DREI ENTSCHEIDUNGEN AM ZWEIG:
 *   Loeschen statt deaktivieren   Ein deaktiviertes Geraet belegt weiter einen
 *                                 der MAX_GERAETE Plaetze — „zu viele Geraete"
 *                                 ist genau der Fehler, in den eine geteilte
 *                                 Uhr sonst laeuft. Der Fremdschluessel setzt
 *                                 device_id auf NULL; hochgeladene Daten
 *                                 bleiben.
 *   Kein eigener Endpunkt         Die Adresse kennt die Uhr schon, und der
 *                                 Ratenschutz von pair.php gilt fuer beide
 *                                 Zweige. Ein zweiter waere eine weitere
 *                                 anmeldungsfreie Tuer.
 *   E-Mail an den Kontoinhaber    Symmetrisch zum Koppeln: die eine
 *                                 Gelegenheit, es zu erfahren, ohne sich
 *                                 zufaellig anzumelden.
 *
 * Die Antwortzeit folgt ingest.php: Auch der unbekannte Zweig laeuft gegen
 * AUTH_VERGLEICHSWERT, sonst waere aus der Dauer ablesbar, welche
 * Geraetekennungen es gibt.
 * ---------------------------------------------------------------------------
 *
 * 10.0.0 ist der Umbau der SPURSPEICHERUNG (Phase S2). Die Hauptnummer steht
 * hier fuer das, wofuer sie da ist: ein geaendertes Datenmodell mit
 * zwingender Migration. Spurpunkte liegen nicht mehr nur als Zeilen in
 * `track_points`, sondern zusaetzlich als Blob in der neuen Tabelle
 * `track_blobs` — im Format SPUR1, spaltenweise Differenzen und zlib. Der
 * Grund ist die Menge: gemessen 62,4 Byte je Punkt als Zeile gegen 3,58 als
 * Blob, ein Siebzehntel. Bei 5000 Einsaetzen sind das 194 statt 3,3 MB.
 *
 * `track_points` bleibt und wird zum EINGANGSPUFFER der Uhr; die Verdichtung
 * selbst kommt mit AP3. Gelesen und geschrieben wird ausschliesslich ueber
 * `server/spur_lib.php` — das ist eine Pflegepflicht, keine Empfehlung
 * (CLAUDE.md 4). Alle sechs bisherigen SQL-Lesestellen sind darauf
 * umgestellt, ebenso jeder Loeschweg: Weder `track_points` noch
 * `track_blobs` haengen an einem Fremdschluessel, was hier nicht
 * ausdruecklich mitgeloescht wird, bleibt als Positionsdatensatz ohne
 * Eigentuemer liegen (F-S2-B).
 *
 * NACH DEM AUSROLLEN MUSS `update.php` AUFGERUFEN WERDEN. Ohne die Migration
 * gibt es die Tabelle nicht, und jeder Spurzugriff scheitert.
 *
 * 10.1.0 ist AP2 derselben Phase: DER JOB-EINSTIEG. Keine Hauptnummer, weil
 * sich weder Datenmodell noch Wege durch die Anwendung aendern — aber eine
 * Migration gibt es trotzdem (2026_08_31_jobs, Tabelle `jobs`), und ohne sie
 * laeuft kein Wartungsjob mehr.
 *
 * Bis hierher hing die Wartung an einer angemeldeten Anfrage: `db.php` rief
 * `run_cleanup_if_due()`, und darin standen zwei Anti-Joins ueber die ganze
 * Spurtabelle. Bei 9,46 Mio. Zeilen dauerte das gemessen 4,07 s — bezahlt von
 * der NutzerIn, die zufaellig die erste des Tages war. Bei Z2 (190 Mio.
 * Zeilen) waeren es Minuten.
 *
 * Jetzt gibt es einen Rahmen mit drei Ausloesern und EINEM Katalog
 * (`jobs_lib.php`): Kommandozeile (`php jobs.php`, der Regelfall),
 * Adresse mit Token (fuer Hoster ohne CLI-Cron) und weiterhin huckepack auf
 * einer Anfrage — jetzt aber mit 3 s Zeitbudget und fruehestens alle fuenf
 * Minuten. Jeder Job arbeitet in Haeppchen und merkt sich in `jobs.zustand`,
 * wo er stehengeblieben ist; die Waisensuche laeuft bereichsweise am
 * Primaerschluessel entlang statt als Anti-Join ueber alles.
 *
 * Ehrlich gemessen ist der bereichsweise Durchlauf bei 3,31 Mio. Zeilen NICHT
 * schneller (0,85-1,05 s gegen 0,78-0,90 s, je fuenf Laeufe). Der Gewinn ist
 * ein anderer: Er ist begrenzt, fortsetzbar und liegt nicht mehr auf dem Weg
 * einer Anfrage. Die eine faellige Anfrage traegt 887 ms, jede weitere
 * innerhalb von fuenf Minuten 0,5-1,3 ms — vorher waren bis zu 18 Sekunden
 * Budget je Anfrage moeglich.
 *
 * AUCH HIER MUSS NACH DEM AUSROLLEN `update.php` AUFGERUFEN WERDEN.
 *
 * 10.2.0 ist AP3: VERDICHTEN UND AUSDUENNEN. Damit stehen die drei Stufen
 * aus E-S2-03 wirklich, statt nur beschrieben zu sein. Eine Migration gibt es
 * (2026_09_01_letzter_punkt_am), und ohne sie laeuft der Verdichtungsjob
 * nicht.
 *
 * ZWEI NEUE JOBS im Katalog aus 10.1.0. `verdichtung` holt abgeschlossene
 * Spuren aus den Zeilen in den verlustfreien Blob — eine Transaktion je Spur,
 * Rundlaufpruefung davor. `ausduennen` ersetzt sechs Monate nach Einsatzende
 * den verlustfreien durch einen ausgeduennten Blob: Douglas-Peucker
 * dreidimensional, 2 m waagerecht und 3 m senkrecht als GETRENNTE Toleranzen,
 * und je Phasenzeitpunkt bleibt der zeitnaechste Punkt erhalten, damit die
 * Hoehenermittlung des Einsatzorts nicht leer ausgeht.
 *
 * DIE NEUE SPALTE `letzter_punkt_am` ist die Groesse, auf der die Karenz aus
 * E-S2-06 steht — und die es bislang nirgends gab. `track_points.ts` ist die
 * Aufzeichnungszeit, nicht die Ankunftszeit. Ueber sie gerechnet waere die
 * Karenz Zierrat: Die Uhr setzt `final` in JEDEM Teilstueck, eine Uhr ohne
 * Empfang laedt ihren Puffer spaeter hoch, und dann ist die
 * Aufzeichnungszeit schon Wochen alt.
 *
 * AUSGEDUENNT WIRD UNWIDERRUFLICH. Deshalb prueft `spur_ausduennung_pruefen()`
 * vor dem Ersetzen unabhaengig nach, dass kein verworfener Punkt weiter als
 * zugesagt vom endgueltigen Streckenzug entfernt liegt — nicht aus der
 * Buchfuehrung der Rekursion, sondern neu gerechnet.
 *
 * WAS DIE AUSDUENNUNG WIRKLICH SPART, ist weniger, als die Punktzahl
 * vermuten laesst: Am Referenzbestand bleiben 38 % der Punkte, aber 74 % der
 * Bytes — die Ausduennung entfernt genau die vorhersagbaren Punkte, und die
 * verbleibenden Differenzen packen sich schlechter. Am Messstand sind es 32 %
 * der Punkte und 57 % der Bytes. Beide Stufen halten E-S2-24 mit Abstand:
 * gemessen 1,60 MB je 1000 Einsaetzen gegen 3 MB Zielwert.
 *
 * `ingest.php` verwirft nach der Ausduennung eingehende Punkte und quittiert
 * sie trotzdem (E-S2-08), damit die Uhr ihren Puffer leert. Der JSON-Vertrag
 * bleibt Fassung 1.3; neu ist allein das zusaetzliche Antwortfeld
 * `dropped_points`. Nebenbei behoben: Scheiterte der LETZTE Punkt eines
 * Teilstuecks an der Wertepruefung, meldete der Server eine zu kleine Marke —
 * und die Uhr sandte dasselbe Stueck endlos.
 *
 * ZWEI FUNDE aus AP3, beide behoben: `spur_loeschen_nur_zeilen()` loeschte
 * ALLE Zeilen eines Eigentuemers, auch die, die waehrend des Laufs eintrafen
 * (jetzt mit verpflichtender seq-Obergrenze); und `compute_site_elevation()`
 * haette auf einer ausgeduennten Spur eine vorhandene Ortshoehe still durch
 * NULL ersetzen koennen, sobald jemand eine Phasenzeit berichtigt.
 *
 * AUCH HIER MUSS NACH DEM AUSROLLEN `update.php` AUFGERUFEN WERDEN.
 *
 * 10.3.0 ist AP4: DER GPX-ABRUF (E-S2-09, Backlog Nr. 3). Neue Funktion,
 * keine Migration.
 *
 * Eine Spur laesst sich jetzt einzeln herunterladen — je Einsatz aus dessen
 * Aktionsmenue, und je Einsatz UND Ruhesegment ueber die neue Seite
 * `tag_spuren.php`: die Karte des Diensttages, darunter jede Spur als eigene
 * Zeile mit Stufe, Punktzahl und Abruf. Wer auf eine Zeile zeigt, sieht auf
 * der Karte, welche Linie gemeint ist.
 *
 * DIE SEITE WAR NOETIG, weil Ruhesegmente in der Oberflaeche bis hierher
 * ueberhaupt keine Identitaet hatten: nur eine schwarze Linie auf der
 * Tageskarte, ohne Zeile, ohne Popup, und `api/day.php` liefert nicht einmal
 * ihre Kennung. Ein Knopf je Ruhesegment haette nirgendwo hingekonnt.
 *
 * DIE ERSTE DATEI, DIE DIESER SERVER AUSLIEFERT. Alle uebrigen Downloads der
 * Anwendung entstehen im Browser, und zwar aus gutem Grund: Ihr Inhalt ist
 * Ende-zu-Ende verschluesselt, der Server KANN ihn nicht zusammensetzen. Fuer
 * eine Spur gilt das nicht — Spurpunkte sind Klartext, und die Stufe, die
 * E-S2-09 sichtbar verlangt, kennt ohnehin nur der Server. Dazu ein
 * Sicherheitsargument: Ein serverseitig gebauter DATEINAME kann keine
 * geschuetzte Angabe tragen, weil der Server sie nicht lesen kann.
 *
 * DIE KENNZEICHNUNG Original/ausgeduennt steht an DREI Stellen: in der Datei
 * (`<metadata><desc>` und `<trk><desc>`), im Dateinamen — nur der ueberlebt
 * das Verschieben in einen anderen Ordner — und auf der Seite, vor dem
 * Herunterladen.
 *
 * MEHRERE SPUREN AUF EINMAL: Ein Kaestchen je Zeile, eine Sammelleiste, eine
 * Datei. Die ausgewaehlten Spuren bleiben darin MEHRERE `<trk>` und werden
 * nicht zusammengeklebt — sonst zoege jedes Kartenprogramm eine gerade Linie
 * vom Ende der einen zum Anfang der naechsten, quer ueber das Land. Die Liste
 * steht dabei chronologisch, wie der Tag verlaufen ist, und nicht nach Art
 * gruppiert; die Datei folgt derselben Folge.
 *
 * NEBENBEI BEHOBEN: `.plakette-warn` gibt es im Stylesheet nicht. Der Ton
 * `warn` wurde an drei Stellen benutzt, zwei davon aus AP2 und AP3 — die
 * Plaketten standen dort ohne Hintergrund da. Der Grund, warum es niemandem
 * auffiel: Der Klassenname wird zusammengesetzt (`'plakette-' . $ton`) und
 * taucht als Literal nirgends auf, die Vollstaendigkeitspruefung kann ihn also
 * nicht finden. Der Fall ist in Backlog Nr. 36 vermerkt.
 *
 * `ui_zeile()` kennt jetzt `attr` — dieselbe Zusatzoption, die `ui_knopf()`
 * und `ui_aktionen()` schon haben. Kein neuer Baustein.
 *
 * 11.0.0 IST AP5: DIE SICHERUNG WIRD MEHRTEILIG (E-S2-10 bis E-S2-12).
 * Hauptnummer, weil das Dateiformat der Sicherung wechselt — der erste
 * Wechsel seit Web 5.0.0.
 *
 * WARUM. Eine Sicherung mit 5000 Einsaetzen traegt rund drei Millionen
 * Spurpunkte. Bis hierher entstand sie als EINE Zeichenkette im Browser und
 * ging als EIN POST zurueck; beides sprengt jedes Budget, das ein Telefon
 * oder ein einfacher Webspace hat. Fassung 4 zerlegt sie deshalb in
 * versiegelte Teile in einem ZIP:
 *
 *   manifest.edbak        Teileliste mit SHA-256 je Teil und Sicherungskennung
 *   kern.edbak            die Nutzlast OHNE Punktlisten
 *   spuren/0001.edbak …   je Teil eine Liste {spur_ref, SPUR1-Blob}
 *
 * Dieser Zuschnitt hat eine Woche gehalten: Der Kern SELBST ist bei grossen
 * Bestaenden zu gross — 11.1.0 zerlegt ihn weiter. Wer die Aufteilung sucht,
 * liest sie dort; die hier genannte gibt es nur noch in diesem Absatz.
 *
 * JEDES TEIL KENNT SEINEN PLATZ. Die Zusatzdaten der Verschluesselung (AAD)
 * binden Sicherungskennung, Teilname und Nummer — ein fehlendes, doppeltes,
 * vertauschtes oder aus einer ANDEREN Sicherung stammendes Teil faellt damit
 * beim Oeffnen auf und nicht erst beim Datenvergleich. Ohne diese Bindung
 * liesse sich ein fremdes Spurteil unterschieben: Mit demselben Passwort
 * ginge es klaglos auf und braechte den Bestand eines anderen Kontos mit.
 * Das Muster ist von Cryptomator und age abgeschaut.
 *
 * EINE PBKDF2 JE VORGANG. Salz und Rundenzahl sind in allen Teilen dieselben;
 * bei zwoelf Teilen waeren zwoelf Ableitungen zu je 320 000 Runden auf einem
 * gedrosselten Telefon eine knappe Minute reines Warten — zweimal, beim
 * Sichern und beim Einspielen.
 *
 * DAS ALTFORMAT WIRD WEITER GELESEN, aber nicht mehr geschrieben. Es ist der
 * Weg, auf dem ein vorhandener Bestand einmal herueberkommt; mit NaDoku 1.0
 * wird es abgeschafft (Entscheidung vom 31.08.2026, Backlog).
 *
 * KEINE MIGRATION. Das Format der Datei aendert sich, das Datenmodell nicht —
 * `update.php` braucht diesmal niemand aufzurufen.
 *
 * 11.1.0 IST AP5b: AUCH DER KERN WIRD MEHRTEILIG (E-S2-11, Z3).
 *
 * AP5 hatte die Punktlisten aus dem Kern geholt und in Spurteile gelegt. Was
 * blieb, war der Kern selbst — und der ist beim 5000er-Bestand 10,5 MB. Auf
 * dem Rueckweg ginge er als EIN POST von 9,4 MB gegen ein Limit, das niemand
 * kennt: nginx deckelt in der Vorgabe bei 1 MB. Und im Server kostet der Bau
 * am Stueck 39,5 MB von 64 (Z3) — noch unter dem Budget, aber wachsend mit
 * dem Bestand, waehrend ein Fenster gleich gross bleibt.
 *
 * Der Kern zerfaellt deshalb in einen Kopf und Eintragsfenster:
 *
 *   manifest.edbak            Teileliste mit SHA-256 je Teil und Kennung
 *   kopf.edbak                Stammdaten, Diensttage, Zahl der Eintraege
 *   eintraege/0001.edbak …    je 250 Eintraege ohne Punktlisten
 *   spuren/0001.edbak …       je Teil eine Liste {spur_ref, SPUR1-Blob}
 *
 * `kern.edbak` aus 11.0.0 gibt es nicht mehr; eine solche Datei wird beim
 * Oeffnen mit Namen abgewiesen. Sie kann nur im Werkstattbestand liegen —
 * 11.0.0 ist nie ausgeliefert worden.
 *
 * GEMESSEN am 31.08.2026, `memory_get_peak_usage(true)`:
 *
 *                     Demo (187 Eintraege)   Messstand (10 797)
 *   am Stueck          4,0 MB                39,5 MB
 *   in Fenstern        4,0 MB                10,0 MB
 *
 * Am Stueck sind das rund 3,3 kB je Eintrag; auf 64 MB fortgeschrieben waere
 * bei etwa 18 000 Eintraegen Schluss. Groesstes Fenster 0,44 MB bei 250
 * Eintraegen (bei 500 waeren es 0,87 MB — unter nginx' Grenze, aber ohne
 * Reserve). 10 797 Eintraege ergeben 44 Fenster.
 *
 * DIE 92 MB, die hier vorher standen, gehoeren zu AP5 und nicht hierher: Es
 * war der Stand VOR den Fenstern der Kindtabellen. Beim Nachmessen fuer
 * dieses Paket kam 37,5 MB heraus — die Zahl war weitergetragen worden, ohne
 * dass jemand sie noch einmal erhoben hatte.
 *
 * ZWEI FEHLER FIELEN DABEI AUF, beide nicht im Umbau:
 *
 * 1. Die Rueckfrage vor dem Einspielen kam bei Fassung 4 IMMER, wenn die
 *    Datei aus einem anderen Konto stammte — also im Regelfall. Sie warnt
 *    vor Angaben, die unlesbar ankommen; ob es welche gibt, konnte der
 *    Einspielweg nicht mehr sehen, seit die Eintraege in versiegelten Teilen
 *    liegen. Der Erzeuger weiss es und schreibt es jetzt ins Manifest
 *    (`unlesbar`). Ohne die Zahl wird weiter gefragt: „nicht erhoben" ist
 *    etwas anderes als „keine".
 * 2. Die beiden Rueckfragen des Sicherungsbereichs benutzten noch das
 *    native `confirm()` — abschaltbar im Browser, und genau dagegen gibt es
 *    confirm.js. Sie laufen jetzt ueber `window.edConfirm`.
 *
 * KEINE MIGRATION. Nur das Dateiformat aendert sich, das Datenmodell nicht.
 *
 * 11.1.1 IST EIN NACHTRAG ZU 11.1.0 (F-S2-E).
 *
 * Eine Datei, die Nutzlast 8 nennt UND Punktlisten in den Eintraegen traegt,
 * verlor beim Einspielen alle Spuren — ohne ein Wort. Der Verweisweg
 * entscheidet an der Fassung (richtig so: eine Spur ohne Punkte sieht aus wie
 * ein Verweis), aber die Kehrseite war nicht bedacht.
 *
 * Solche Dateien schreibt diese Anwendung nicht; sie kamen aus dem
 * Vervielfaeltiger des Messstands, der die Fassung aus der Referenz geerbt
 * hat, seit diese Fassung 4 ist. Gemessen an einem Lauf: 164 Einsaetze
 * angelegt, 91 208 Punkte verloren, Meldung „fertig".
 *
 * Jetzt wird es gesagt — ueber die gemeinsame Pruefschicht, also dort, wo die
 * Ablehnungen ohnehin stehen. Abgewiesen wird die Datei nicht: Der uebrige
 * Bestand ist brauchbar, und ihn wegen der Spuren zu verweigern machte aus
 * einem Teilverlust einen Totalverlust.
 *
 * 12.0.0 IST AP6: DIE ADMIN-SICHERUNG WIRD MEHRTEILIG (E-S2-13 bis E-S2-15).
 *
 * Hauptnummer aus zwei Gruenden: Das Dateiformat der Admin-Sicherung wechselt
 * von 1 auf 2, und der erste Lauf danach ENTFERNT die einteiligen Pakete
 * eines Kontos (Entscheidung vom 31.08.2026). Das ist ein spuerbar
 * veraenderter Weg durch die Anwendung, kein Feinschliff.
 *
 * WARUM. Die Admin-Sicherung war der letzte Weg, der das Budget sprengte —
 * und zwar nicht knapp. Gemessen am 5000er-Konto: 19,81 s, 94,28 MB Paket,
 * 1077,6 MB Speicherspitze; mit `memory_limit=64M` (Z3) brach der Lauf in
 * `spur_lib.php` ab. Auf genau der Sorte Webspace, fuer die diese Anwendung
 * gebaut ist, war die Admin-Sicherung eines grossen Kontos unmoeglich.
 *
 * Der Grund stand in einer Zeile: `json_decode(edbak_build($userId), true)` —
 * derselbe Bestand als Zeichenkette, als Feld und beim Schreiben noch einmal
 * als Zeichenkette.
 *
 * DAS PAKET IST JETZT EIN ZIP, unversiegelt (es liegt serverseitig und traegt
 * `pat_blob` als Chiffretext):
 *
 *   manifest.json           Umfang, Huellen, Teileliste, `geschuetzte`
 *   kopf.json               Stammdaten, Diensttage, Zahl der Eintraege
 *   eintraege/NNNN.json     je 250 Eintraege ohne Punktlisten
 *   spuren/NNNN.json        je Teil {spur_ref, blob} (SPUR1, Base64)
 *
 * GEMESSEN nach dem Umbau, 5000er-Konto: 14,13 s, **24,0 MB von 64**, Datei
 * **11,42 MB** statt 94,28 — und mit `memory_limit=64M` laeuft es durch.
 * Demokonto: 28,1 -> 4,0 MB, 2,14 -> 0,22 MB.
 *
 * EIN UMWEG, DEN DIE MESSUNG ERZWUNGEN HAT: `ZipArchive::addFromString()`
 * haelt jede uebergebene Zeichenkette bis zum `close()` im Speicher — damit
 * laege am Ende doch wieder alles gleichzeitig da. Gemessen an 34,6 MB
 * Inhalt, je eigener Prozess: `addFromString` 42,0 MB Spitze, `addFile`
 * **2,0 MB**. Die Teile gehen deshalb einzeln in einen Bauordner und von dort
 * ins Archiv.
 *
 * SPEICHERGRENZE UND SCHWELLEN (E-S2-15). Vorgabe 2 GB, Warnschwellen 70 und
 * 90 Prozent, beides im Adminbereich einstellbar. Geprueft wird VOR dem Bau:
 * abgelehnt mit Meldung, nie still verdraengt. Die Zaehlung misst das GANZE
 * Verzeichnis — es fuellt sich auch mit dem, was nicht auf der Paketliste
 * steht. Je Schwelle geht einmal eine Meldung heraus, und die Marke wird
 * NACH dem Versand gesetzt: Scheitert er, kaeme die Warnung sonst nie.
 *
 * AUFBEWAHRUNG 2 STATT 3. Das Konzept nennt seit E-S2-14 die Zwei; Code und
 * drei Dokumente standen auf drei. Eine Installation, die die Einstellung nie
 * angefasst hat, verliert beim naechsten Sichern je Konto den aeltesten von
 * drei Staenden — die Rueckmeldung nennt jede verdraengte Datei.
 *
 * KEINE MIGRATION. Nur das Dateiformat der Sicherung aendert sich.
 *
 * 12.1.0 ist S2/AP7: SICHERUNGSZIELE. Die Sicherungen bleiben nicht mehr auf
 * demselben Server liegen, dessen Ausfall der Grund fuer eine Sicherung waere
 * — sie gehen ueber FTP, FTPS oder SFTP auf eine Gegenstelle (E-S2-22).
 *
 * DREI ADAPTER HINTER EINER SCHNITTSTELLE (`sicherungsziel_lib.php`): FTP und
 * FTPS ueber `ext/ftp`, SFTP ueber phpseclib 3.0.57 (MIT, vendoriert unter
 * `server/vendor/`, docs/Lizenzen.md). Wer eine Datei wegschiebt, sieht das
 * Protokoll nicht — das Komplettbackup aus AP8 soll dieselbe Schnittstelle
 * benutzen, ohne davon zu wissen.
 *
 * WAS DIE DREI TAUGEN, ohne Beschoenigung: SFTP erkennt den Server am
 * Fingerabdruck des Hostschluessels wieder und bricht ab, BEVOR ein Passwort
 * hinausgeht, wenn er sich geaendert hat. FTPS verschluesselt die Leitung,
 * prueft aber kein Zertifikat — nachgemessen in `tools/versandprobe/` gegen
 * einen Server mit selbst ausgestelltem Zertifikat ohne Vertrauenskette. FTP
 * ist Klartext. Die Oberflaeche sagt das an der Stelle, an der man waehlt.
 *
 * DER SERVERSCHLUESSEL (E-S2-21) ist neu und liegt in `config.php`, nicht in
 * der Datenbank: 32 Byte Zufall, AES-256-GCM, der Zweck (Ziel und Feld) in
 * den Zusatzdaten. Damit sind die Zugangsdaten der Ziele im Datenbankdump
 * NICHT enthalten, und eine Chiffre laesst sich nicht von einem Ziel auf ein
 * anderes umhaengen. Neue Installationen bekommen ihn vom Installer;
 * bestehende tragen ihn ueber die Seite „Sicherungsziele" nach — mit einem
 * Klick, wenn `config.php` beschreibbar ist, sonst mit einer Zeile von Hand.
 * ER GEHOERT INS WIEDERANLAUFPAKET (docs/Technik.md, Runbook).
 *
 * DER VERSAND ist ein Joblauf (`versand`) und ein Knopf. Was „neu" ist, wird
 * AM ZIEL abgelesen — Name und Groesse — und nicht in einer Merkliste
 * gefuehrt, die behauptet „schon versandt", nachdem das Ziel neu aufgesetzt
 * wurde. Es wird nur ergaenzt; auf dem Ziel loescht diese Anwendung nie
 * (Backlog Nr. 49). GEMESSEN gegen oertliche Gegenstellen, 64 Pakete zu
 * 63,89 MB aus 33 Kontoordnern: FTP 0,13 s, FTPS 0,68 s, SFTP 3,08 s;
 * Speicherspitze 2,0 bzw. 8,0 MB von 64 (Z3). Alle 192 angekommenen Dateien
 * byteweise verglichen, 0 Abweichungen.
 *
 * MIGRATION ZWINGEND: `2026_09_01_sicherungsziele` legt `backup_targets` an.
 * Ohne sie zeigt die neue Seite einen Hinweis und tut nichts.
 *
 * 12.1.1 ist S2/AP9: DIE SUCHE. Zwei Maessigungen aus E-S2-16, beide klein:
 * `EdCrypto` merkt sich den importierten Schluessel (bei 5000 Einsaetzen
 * gemessen 4880 Importe fuer denselben Schluessel — jetzt EINER), und
 * `EdPat.entschluessleListe()` entschluesselt in Stapeln zu 200 statt einzeln
 * nacheinander (Schleife 1954 -> 958 ms).
 *
 * WAS DAS BRINGT, UND WAS NICHT: Bis die geschuetzten Spalten lesbar sind,
 * 4,11 -> 3,77 s (Drossel 6x, Median von drei Laeufen, beide Staende
 * unmittelbar nacheinander). Das Ziel von 5 s ist gehalten. Der Loewenanteil
 * der Zeit liegt aber NICHT im Entschluesseln — Backlog Nr. 51.
 *
 * DER GROESSERE FUND STECKT IM PRUEFMITTEL. `entsperren()` in
 * `tools/messstand/browserprobe.mjs` wartete vier Sekunden auf einen
 * Entsperr-Dialog, der bei entsperrter Sitzung nie kommt — mitten im
 * gemessenen Abschnitt. Die Ausgangsmessung von AP0 nennt „Suche 4,53 s" und
 * „Tagesansicht 4,81 s"; beide liegen dicht ueber vier Sekunden, weil beide
 * `max(4 s, tatsaechliche Dauer)` waren. Das Warten rennt jetzt gegen die
 * Abschlussbedingung des Schritts.
 *
 * KEINE MIGRATION, keine Schnittstellenaenderung.
 *
 * ---------------------------------------------------------------------------
 *
 * 12.2.0 ist S2/AP8: DIE KOMPLETTSICHERUNG (E-S2-19 bis E-S2-21). Bis hierher
 * konnte diese Anwendung ein KONTO sichern. Jetzt kann sie die INSTALLATION
 * sichern: jede Tabelle der Datenbank als SQL-Dump, versiegelt mit dem
 * Serverschluessel aus 12.1.0, und einen Weg zurueck.
 *
 * DREI SCHICHTEN, IN DIESER REIHENFOLGE: SQL-Text (ein Statement je Zeile,
 * INSERT-Stapel bis 1 MB, Tabellen in einspielbarer Reihenfolge) — gzip — und
 * darueber das Siegel EDKOMP1 (AES-256-GCM je 256-KB-Block, Blockzaehler und
 * Endemarkierung in den Zusatzdaten, der Dateikopf ueber seinen SHA-256
 * mitgebunden). Erzeugt wird in Haeppchen ueber den Job-Einstieg, mit
 * Fortsetzungszustand in `jobs.zustand` — nie als Array am Stueck.
 *
 * ZWEI WEGE HERAUS, und der Unterschied ist der Punkt: „Herunterladen" gibt
 * den Dump UNVERSCHLUESSELT als `.sql.gz`, damit `mysql` und phpMyAdmin ihn
 * einspielen koennen (E-S2-20); was das Haus verlaesst — der Versand aufs
 * Sicherungsziel — ist immer die versiegelte Fassung. Wahlweise gibt es die
 * Datei unter einer PASSPHRASE (PBKDF2, 320 000 Runden wie im Browser).
 *
 * DER RUECKWEG ist `wiederherstellen.php`, die Luecke zwischen `install.php`
 * (verweigert sich, sobald es eine config.php gibt) und `update.php`
 * (verlangt eine Anmeldung, die es ohne Konten nicht gibt). Drei Schranken:
 * die Datenbank muss LEER sein, ein Nachweis wie beim Einrichter (M1-11)
 * belegt Dateizugriff, und die Datei kommt aus `sicherungen/eingang/` statt
 * aus einem Formular. Der Migrationslauf laeuft dort BEWUSST NICHT mit — er
 * gehoert einer angemeldeten Administration, und genau dafuer ist
 * `update.php` seit M6-01 zweistufig.
 *
 * NEU: `server/komplett_lib.php`, `server/admin_komplettsicherung.php`,
 * `server/wiederherstellen.php`, `tools/komplettprobe/`. Der Job `komplett`
 * steht im Katalog NACH `versand` — ein frischer Stand geht damit erst im
 * naechsten Lauf hinaus, dafuer nimmt ihm die schwerste Arbeit der Anwendung
 * nicht das Budget weg.
 *
 * GEMESSEN am Messbestand (5000 Einsaetze, 1 121 802 Zeilen, 34 Tabellen):
 * 8,5 s in 14 Haeppchen, Speicherspitze 26 von 64 MB (Z3), 122,5 MB SQL ->
 * 43,7 MB versiegelt. Zurueckgespielt in eine leere Datenbank: 34 von 34
 * Schemata zeichengleich, 34 von 34 Pruefsummen gleich (CHECKSUM TABLE
 * EXTENDED).
 *
 * KEINE MIGRATION. Die Zaehlweise ist Neben und nicht Haupt, wie schon bei
 * 12.1.0: ein neues Dateiformat und zwei neue Seiten, aber kein Datenmodell,
 * das sich aendert, und kein bestehender Weg, der anders verlaeuft.
 *
 * 12.2.1 IST DIE ZWEITE RUECKMELDUNGSRUNDE nach P3 — drei kleine Dinge an
 * zwei Seiten, keine Migration, kein neues Feld. Sie sind waehrend S2 auf
 * einem eigenen Zweig entstanden und bewusst auf Dateien beschraenkt worden,
 * die S2 nicht hielt; die Buchfuehrung (diese Datei, Changelog, Handbuch,
 * Backlog) ist deshalb erst jetzt nachgezogen.
 *
 * DER AUSWAEHLEN-KNOPF DES DATEIFELDS KLEBTE OBEN. `.feld-eingabe` gibt jedem
 * Feld 44 px Hoehe und nur waagerechte Polsterung; ein `input[type=file]`
 * stellt seinen nativen Knopf auf die Textzeile, und die steht damit am
 * oberen Innenrand — gemessen 0 px Luft darueber, 19 px darunter bei einem
 * 23 px hohen Knopf. `display:flex; align-items:center` aendert daran NICHTS:
 * Chromium legt den Schatteninhalt eines Eingabefeldes nicht in einen
 * Flex-Fluss, nachgemessen blieb es bei 0/19. Was wirkt, ist die Zeilenhoehe
 * auf den Innenraum — jetzt 10 px oben gegen 9 px unten; mittiger geht es
 * nicht, der Innenraum ist mit 42 px ungerade. Die 44 px bleiben. Der
 * Attributselektor ist Abgrenzung, kein Spezifitaetsgewinn: Es gibt keine
 * Regel `input[type=file]`, gegen die er gewinnen muesste. Getroffen werden
 * genau die beiden Dateifelder der Anwendung (Backup einspielen, Import).
 *
 * DIE ERZEUGTE DATEI SAGTE NICHT, DASS ES SIE GIBT. Sicherung und Datenexport
 * zaehlten auf, was drinsteht, und schwiegen darueber, dass ein Download
 * gelaufen ist. Der laeuft ohne Dialog und ohne Ton durch; wer nicht gerade
 * auf die Leiste des Browsers sieht, sucht anschliessend eine Datei, deren
 * Namen er nicht kennt. Beide Meldungen nennen ihn jetzt — aus EINER
 * Variablen, denn zwei getrennte Ausdruecke liefen mit dem naechsten
 * Tageswechsel auseinander. WO die Datei liegt, sagen sie bewusst nicht: Das
 * entscheidet die Einstellung des Browsers. Im Export sind es drei Wege mit
 * drei Namen (Tabelle roh, Tabelle im Archiv, Archiv des Profils B), und beim
 * Archivweg ist die heruntergeladene Datei das ARCHIV, nicht die Tabelle
 * darin.
 *
 * UND `warn` TRUG DAS FALSCHE ZEICHEN. Der melde()-Nachbau der
 * Einstellungsseite liess den Ton in den Sonst-Zweig fallen und zeigte das
 * Hinweiszeichen statt der Warnung — entgegen Design.md 9.5 und entgegen
 * ui_meldung_markup(), das die vollstaendige Tabelle fuehrt. Betroffen waren
 * gerade die Meldungen, die auffallen sollen.
 *
 * 12.2.2 SCHREIBT DEN VERTIKALEN RHYTHMUS FEST (S3/AP1 und AP2). Die
 * Abstandsskala --abstand-1 bis -5 steht seit P3 und wird eingehalten:
 * gemessen 269 Abstandsdeklarationen, davon KEINE mit einem Rohwert. Was
 * fehlte, war die Stufe darueber -- eine Regel, WELCHE Stufe WO gilt. Sie
 * steht jetzt in docs/Design.md, Kapitel 6.
 *
 * Der Befund dahinter in einem Satz: .karte und .feld trugen beide 16 px.
 * Zwei Karten standen genauso weit auseinander wie zwei Felder INNERHALB
 * einer Karte, und die Flaeche sagte damit nichts mehr darueber, was wozu
 * gehoert. Jetzt trennen Karten mit 24 px und Felder binden mit 12 px.
 * Dreizehn Regeln des Stylesheets sind darauf eingestellt, dazu zwoelf
 * freistehende Absendeknoepfe, die jetzt im Formularfuss-Baustein stehen.
 *
 * KORREKTURSTUFE, obwohl es auf jeder Seite zu sehen ist: Es gibt keine neue
 * Funktion und kein neues Feld, und kein Weg durch die Anwendung hat sich
 * geaendert. Was sich aendert, ist die Groesse von Zwischenraeumen.
 *
 * 12.2.3 GIBT DER SAMMELLEISTE DIE KARTENFORM (S3/AP3, E-R43-1). Sie brach
 * mit `margin: <oben> calc(var(--abstand-3) * -1) <unten>` seitlich aus dem Inhalt aus und
 * lief ohne Radius von Rand zu Rand -- daher der Eindruck „eckig und
 * breiter". Jetzt Radius und Breite wie die Karte darueber; klebender Sitz,
 * Trennlinie und Schatten bleiben, denn sie tragen die Funktion. Dazu die
 * Reihenfolge im Markup umgedreht: Hinweis zuerst, Knopf danach -- der Knopf
 * steht damit rechts, und die Vorlesereihenfolge stimmt ohne `order`.
 *
 * 12.2.4 NIMMT DIE AUSZEICHNUNG AUS DER NAVIGATION ZURUECK (S3/AP4,
 * E-R43-2). Alle Menuepunkte standen fett; in einer Liste, in der jede Zeile
 * fett ist, hebt das nichts hervor. Jetzt normal, und nur der AKTIVE Punkt
 * fett -- die Auszeichnung wandert von „alle" zu „einer".
 *
 * Die Leistenueberschrift („Diensttage", „Einstellungen", „Administration",
 * „Filter") wirkte verloren. Das war ein Problem von Groesse und Kontrast,
 * nicht von Ausrichtung: eine Stufe hoeher (15 statt 13 px) und --asphalt
 * statt --gedaempft, linksbuendig wie bisher. Versalien und Sperrung sind
 * dabei entfallen -- am Bild entschieden, denn bei 15 px liest sich der
 * gesperrte Versalsatz als Etikett und konkurriert mit dem Eintrag darunter.
 *
 * 12.3.0 BRINGT DEN FUENFTEN MELDUNGSTON und raeumt zwei Tabellen auf
 * (S3/AP5 und die Rueckmeldung vom 01.09.2026).
 *
 * NEUE NEBENNUMMER wegen `schutz`: ein Meldungston in der Flaeche von
 * `fehler`, aber mit role="status" und dem Schloss statt der Warnung. Er ist
 * fuer eine Meldung da, die DAUERHAFT steht und trotzdem die Farbe des
 * Ernstfalls braucht -- der Datenschutzhinweis der Spurenseite, dort wo
 * jemand gleich GPX herunterlaedt. Kein neuer Farbwert.
 *
 * DAHINTER STECKT EIN FEHLER, DER LANGE UNSICHTBAR WAR: ui_meldung_markup()
 * setzte die Klasse aus dem uebergebenen Wort zusammen. Ein Ton, den es nicht
 * gibt, ergab `meldung-<wort>` ohne Regel im Stylesheet -- einen weissen
 * Kasten ohne Flaeche und ohne Fehlermeldung. Die Spurenseite trug zwei
 * Meldungen mit dem Ton „hinweis", den diese Funktion nie gekannt hat. Die
 * Vollstaendigkeitspruefung kann das nicht finden, weil die Klasse
 * zusammengesetzt wird; die Funktion prueft den Ton jetzt selbst.
 *
 * UND DER SPURENSEITE FEHLTE DAS GERUEST. Sie rief ui_seite_start() und
 * schrieb ihren Inhalt danach unmittelbar in den <body> -- ohne
 * ui_geruest_start(). Damit fehlten ihr die Diensttag-Leiste UND der
 * seitliche Innenabstand: Titel, Karte und Kartenbaustein sassen am blanken
 * Fensterrand (gemessen auf 412 px: linke Kante 0 statt 12). Der Bilderlauf
 * hat es nicht gefunden, weil er waagerechten UEBERLAUF misst -- eine Seite
 * ohne Innenabstand laeuft nicht ueber, sie ist nur randlos.
 *
 * DAZU AP5: Fuenf Spalten der NutzerInnen-Liste stehen mittig statt links --
 * ihre Titel waren seit P3 zentriert und standen ueber nichts. Nr., Beginn
 * und Alter der Tagesuebersicht ebenso. Die Dauer traegt dort endlich
 * `zeit-spalte` und bricht nicht mehr nach der Stunde um. Kennzahl-Kacheln
 * zentrieren ihren Inhalt senkrecht, sobald eine Nachbarkachel hoeher ist.
 *
 * 12.3.1 RAEUMT DIE EINSATZANSICHT (S3/AP6). Die Hoehe des Einsatzortes stand
 * als nacktes „706 m" in einer Zeile mit „Strecke 40,9 km" -- der Nachbarwert
 * trug sein Wort, dieser nicht. Jetzt „Hoehe 706 m". Angezeigt wird sie
 * weiterhin nur luftgebunden; bodengebunden ist es die Hoehe der Strasse und
 * die Zeile entfaellt ersatzlos.
 *
 * SCHUTZ WIRD REDUNDANT ANGEZEIGT, und das ist die Umkehr von F-N1-B. Dort
 * galt: entweder die Plakette an der Karte ODER das Schloss an der Zeile,
 * nie beides. Die Rueckmeldung vom 31.08.2026 will beides, und die
 * Begruendung traegt: Die Plakette sagt „hier stehen verschluesselte
 * Angaben", das Schloss sagt „diese hier". Bei einer Schutzauskunft ist
 * Redundanz kein Laerm. Neu sind die Plakette am Block „Einsatz" und die
 * Schloesser an Name und Geburtsdatum.
 *
 * DER BLAUE BALKEN „Geschuetzte Angaben sind entsperrt, bis du dich
 * abmeldest" ENTFAELLT. Er stand nach dem Entsperren auf JEDEM Einsatz und
 * sagte beim zwanzigsten Mal nichts mehr; sichtbar ist der Zustand ohnehin
 * daran, dass die geschuetzten Angaben dastehen. Der GESPERRT-Balken mit dem
 * Entsperren-Knopf bleibt, ebenso die Fehlermeldung fuer unlesbare Angaben.
 * Die Aussage „entsperrt bis zur Abmeldung" steht jetzt im Handbuch.
 *
 * Dazu das Schloss senkrecht mittig zum Wort daneben: `vertical-align`
 * -0.1em statt `baseline`, nachgemessen an einer echten Zeile.
 *
 * 12.3.2 BEHEBT DEN MARKERVERSATZ (S3/AP7) -- den einen echten Fehler der
 * Rueckmeldungsliste.
 *
 * DER FEHLER: Standort- und Klinik-Schilder sassen umso weiter oestlich, je
 * weiter herausgezoomt wurde. Drei Glieder, jedes fuer sich richtig:
 * `.geo-schild` ist eine Flex-SPALTE (wird so breit wie ihr breitestes
 * Kind), das breiteste Kind war das NAMENSSCHILD (nowrap, bei „Klinikum
 * Immenstadt" rund 150 px statt 44), und `iconSize: null` liess Leaflet die
 * Groesse aus dem Markup nehmen -- `iconAnchor: [22, 22]` verankerte damit
 * rund 50 px links der Kastenmitte. Ein KONSTANTER Pixelversatz:
 * herausgezoomt sind dieselben 50 px Kilometer, hereingezoomt Meter.
 *
 * Nachgemessen im Browser: 51,7 px vorher, 0,0 px nachher, ueber sechs
 * Zoomstufen unveraendert.
 *
 * Die Namensschilder entfallen ohnehin (sie machten die Karte voll und
 * standen bei mehreren Markern uebereinander); der Name steht jetzt im
 * title-Attribut. `iconSize` wird TROTZDEM ausdruecklich gesetzt, an ALLEN
 * fuenf Markerarten -- wer dem Marker kuenftig etwas danebenstellt, traegt
 * den Fehler sonst wieder ein, und zwar wieder ohne Fehlermeldung.
 *
 * Dazu: Das Schildkaestchen wird enger (36 statt 44 px -- es ist eine
 * Zeichnung und kein Bedienelement, die 44-px-Regel gilt fuer das, was man
 * drueckt), der Einsatzort-Kreis verliert seine weisse Umrandung und wird
 * 32 statt 36 px, und die Tagesuebersicht zeigt keine Zielkliniken mehr.
 *
 * 12.3.3 LAESST DAS ORTSFELD BEIM TIPPEN SUCHEN (S3/AP8, E-S3-06) --
 * FUNKTIONSAENDERUNG. Bei Standort und Zielklinik suchte bis hierher nur die
 * Lupe; O5 hatte das ausdruecklich so entschieden. Fuer einen Weg, den man
 * zwanzigmal am Tag geht, ist ein Klick eine Handlung zu viel. Drei Grenzen
 * fassen es ein: 400 ms Entprellung, ab drei Zeichen, hoechstens EINE offene
 * Anfrage (eine laufende wird abgebrochen). Nachgemessen mit abgefangenen
 * Anfragen: fluessiges Tippen eines Ortsnamens ergibt genau eine.
 *
 * DAS AENDERT EINE ZUSAGE, UND DIE STEHT IN docs/Lizenzen.md 6.2. Dort hiess
 * es, die Suche laufe nicht bei jedem Tastendruck UND nur auf ausdrueckliches
 * Ausloesen. Der erste Teil stimmt weiter, der zweite nicht mehr. Der
 * Abschnitt ist neu geschrieben und nennt die drei Grenzen. Die
 * E2E-Zusage ist unberuehrt: Gesucht wird, BEVOR aus der Eingabe ein
 * gespeicherter -- und damit verschluesselter -- Wert wird.
 *
 * PLATZHALTER TRAGEN JETZT PHANTASIENAMEN (E-S3-13). „z. B. Standort
 * Kempten" bevorzugte einen realen Ort und las sich fuer manche als die
 * erwartete Antwort, fuer andere als Auskunft darueber, wer diese Anwendung
 * betreibt. Elf Stellen getauscht, mit Namen aus der Welt des
 * Referenzdatensatzes (Talwang, Westried, Sonnenau, Alpenfalke).
 *
 * Dazu die Wahlliste als schlichte Liste statt vier umrandeter Einzelzeilen:
 * 248 auf 224 px bei gleicher Zeilenhoehe.
 *
 * 12.4.0 BLENDET FILTER OHNE BESTAND AUS (S3/AP9, E-S3-08) --
 * FUNKTIONSAENDERUNG, deshalb die Nebennummer.
 *
 * Bis hierher galt die Regel fuer den Block „Bergrettung" und das Einzelfeld
 * „Fehleinsatz", und sie stand als ZWEI HANDGEPFLEGTE LISTEN im Code
 * (GRUPPE_NUR_WENN, FELD_NUR_WENN) -- genau der Einzelfall-Wildwuchs, den der
 * Feldkatalog abschaffen sollte. Jedes neue Feld haette einen dritten Eintrag
 * gebraucht, und wer ihn vergisst, merkt es nie: Ein dauerhaft leerer Filter
 * sieht aus wie ein Filter.
 *
 * JETZT ENTSTEHT DIE REGEL AUS DEM KATALOG. Jeder Filter, der zu einer
 * Katalogspalte gehoert, traegt sie; KATALOG_ART sagt (aus
 * mission_fields.php erzeugt), welcher Art sie ist -- denn „gefuellt" heisst
 * je nach Art etwas anderes: Bei einem Haken zaehlt nur wahr, bei einer
 * Auswahl ist auch die Null eine Angabe. Ein Filter OHNE Spalte -- Zeitraum,
 * Uhrzeit, Wochentag, Strecke, Dauer, Alter, Standort, Rettungsmittel,
 * Besatzung -- ist immer sinnvoll und bleibt. Ein Block verschwindet, wenn
 * alle seine Filter verschwunden sind; eine eigene Bedingung braucht er nicht
 * mehr.
 *
 * KEINE ZUSAETZLICHE SERVERABFRAGE. Der ganze Bestand liegt seit Web 5.10.0
 * ohnehin im Browser (api/suchindex.php, einmal je Seitenaufruf, fuenf
 * SQL-Abfragen unabhaengig von der Zahl der Einsaetze). Die Sichtbarkeit
 * entsteht in EINEM Durchgang darueber, gemessen 0,06 ms bei 82 Einsaetzen.
 *
 * 12.4.1 SPERRT DAS DEMO-KONTO AUF DER KONTOSEITE (S3/AP10, E-S3-07) --
 * FUNKTIONSAENDERUNG.
 *
 * Es wird zentral verwaltet: angelegt, zurueckgesetzt und entfernt ueber den
 * Reiter „Demo-Konto“. Was auf der Kontoseite haengenbliebe, waere
 * spaetestens nach dreissig Minuten weg -- der Reset ueberschreibt Konto- und
 * Schluesselmaterial und loescht den ganzen Bestand. Eine Aenderung, die
 * lautlos verfaellt, ist schlimmer als eine, die gar nicht erst geht.
 * Gesichert wird das Konto ebenfalls nicht: Sein Bestand ist erfunden und
 * liegt als Fixture im Repositorium.
 *
 * DIE SPERRE SITZT IM SCHREIBWEG, nicht im Markup. Ein `disabled` allein ist
 * Kulisse -- ein direkt abgesetzter POST geht daran vorbei. Sieben Aktionen
 * werden serverseitig abgewiesen (konto, sichern, einspielen, freigeben,
 * widerrufen, paket_loeschen, user_delete); die Anzeige graut zusaetzlich
 * aus, damit man es sieht, bevor man es versucht. NICHT gesperrt sind die
 * Geraete-Aktionen: Das Demo-Konto laedt ausdruecklich zum Koppeln einer Uhr
 * ein, und was dabei entsteht, raeumt der Reset selbst ab.
 *
 * Dazu der Anzeigename „Demo NutzerIn“ statt des Namens aus der Fixture --
 * gesetzt beim Anlegen UND beim Zuruecksetzen, sonst holte der naechste Reset
 * den alten zurueck.
 *
 * 12.4.2 BESCHNEIDET DAS BODENLOGO (S3/AP11). Es wirkte neben dem Luftlogo
 * kleiner, und das lag nicht an einer Regel: Seine viewBox war 420 x 420, die
 * Zeichnung darin aber 420 x 335 ab y=42,5 -- oben und unten je ein Zehntel
 * leer, ein Artefakt des Exports. Skaliert wird ueber die HOEHE, also war ein
 * Zehntel dieser Hoehe Luft. Gemessen bei 34 px: sichtbare Flaeche 1 853
 * gegen 921 px², das Doppelte.
 *
 * Jetzt ist der Rahmen deckungsgleich mit der Zeichnung: 54,5 x 34 gegen
 * 42,6 x 34 px, Flaechenverhaeltnis 1,28. Eine Feinkorrektur braucht es
 * nicht -- die Hoehen sind gleich, und die Restdifferenz ist der ehrliche
 * Unterschied zweier Motive (E-S3-12 b, am Bild entschieden). AN DER
 * ZEICHNUNG IST NICHTS GEAENDERT, nur am Rahmen.
 *
 * DIE KOPFLEISTE GIBT DIE BILDMASSE JETZT JE LOGO AUS. `width="54"
 * height="34"` galt fuer beide; 54:34 ist das Verhaeltnis des Luftlogos, das
 * Bodenlogo ist 43 px breit. Der Browser reservierte damit einen Kasten, in
 * den das Bild nicht passt, und rueckte beim Laden nach.
 *
 * Dazu: ein Rahmen-Clip am Luftlogo (ein blauer Streifen laeuft 156 Einheiten
 * ueber den Rahmen hinaus -- unsichtbar, bis jemand den Rahmen weitet), neu
 * abgeleitete Favicons und vier neu gerasterte Uhr-Kacheln. Die Uhr-Kacheln
 * reisen mit der S5-Auslieferung (E-S3-04); die Uhr-Version steigt hier
 * NICHT.
 *
 * 12.5.0 LEGT DAS FUNDAMENT DES SCHNEIDEWERKZEUGS (S4/A2). Wer einen
 * vergessenen Einsatz nachtraegt, soll ihn aus dem Ruhesegment
 * HERAUSSCHNEIDEN koennen: Der gewaehlte Zeitbereich wandert samt Punkten vom
 * Segment zum Einsatz. `spur_teilen()` in spur_lib.php tut das -- und zwar
 * VERSCHIEBEND, nicht kopierend (E-S4-53), sonst laege die Einsatzfahrt
 * hinterher in beiden Spuren und das Ruhesegment zeigte eine Ruhezeit ueber
 * 40 km.
 *
 * MIGRATION ERFORDERLICH (2026_09_02_schnitte): die Tabelle `track_cuts`.
 * Sie ist der Sperrvermerk, ohne den sich der Schnitt still wieder aufloest.
 * Das Geraet weiss von ihm nichts; hat es die Punkte des geschnittenen
 * Zeitraums noch im Puffer (Funkloch), liefert es sie nach, und sie faenden
 * in das Segment zurueck, aus dem sie eben genommen wurden.
 *
 * DASS DAFUER `n_original` NICHT REICHT, ist der Ertrag dieses Pakets und war
 * zuerst falsch angenommen. `ingest.php` vergibt die Sequenznummern aus
 * `seq_from` -- der Marke, die das Geraet zuletzt bekam. Gepufferte Punkte
 * kommen deshalb OBERHALB jeder Sperrgrenze an und laufen glatt daran vorbei;
 * `n_original` faengt nur die Wiederholung schon gelieferter Punkte ab. Was
 * die Nachzuegler kenntlich macht, ist ihre `ts`. Der Vermerk haelt deshalb
 * einen ZEITRAUM und nicht, wie das Konzept es vorsah, einen Sequenzbereich
 * -- den gibt es beim Schnitt noch gar nicht.
 *
 * Beide Boeden bleiben also noetig, und sie tun Verschiedenes: `n_original`
 * haelt die Fortsetzungsmarke (sonst faellt sie mit den geloeschten Zeilen
 * zurueck und das Geraet sendet den ganzen Dienst noch einmal), der Vermerk
 * haelt den Zeitraum. Nachgewiesen mit 20 Erwartungen in
 * `tools/spurprobe/probe.php`, Teil 6.
 *
 * Die Bedienung folgt in einem eigenen Paket; hier stehen Bibliothek, Schema,
 * die Pruefung in ingest.php und die Loeschwege.
 *
 * 12.6.0 MACHT DAS SCHNEIDEN BEDIENBAR (S4/A2b). Die Tagesansicht bekommt die
 * Karte „Ruhesegmente" -- bis hierher lagen die Segmente nur als graue Linie
 * auf der Karte und waren nicht anfassbar, obwohl genau dort die Spur eines
 * vergessenen Einsatzes liegt. An einer Segmentzeile klappt der
 * Schneide-Bereich auf: Zeitleiste, Beginn und Ende (Pflicht), drei
 * Phasenzeiten (optional). `api/schneiden.php` legt den Einsatz auf dem
 * BESTANDSWEG an (virtuelles Geraet `manual-<userId>`, `origin = 'manual'`,
 * `manual = 1`) und verschiebt die Punkte in EINER Transaktion. Rueckgaengig
 * ist derselbe Aufruf mit vertauschten Enden.
 *
 * OHNE MIGRATION -- die Tabelle steht seit 12.5.0.
 *
 * ZWEI FUNDE AUS DER BROWSERPRUEFUNG, beide behoben und beide von derselben
 * Art: etwas, das auf EINEM Rechner richtig aussieht.
 *
 * (1) DIE ZEITEN GINGEN ROH HINAUS. `api/day.php` lieferte `started_at` als
 *     UTC-Zeichenkette, und der Browser rechnete mit `new Date(...)` in SEINE
 *     Zone um. Auf einem Rechner in der Zone der Anwendung faellt das nie
 *     auf; im Container ist sie UTC, und der Schnitt griff zwei Stunden
 *     daneben und nahm NULL Punkte mit -- mit Erfolgsmeldung. Jetzt geht
 *     `start_hhmm` hinaus, fertig formatiert, wie es die Einsatztabelle seit
 *     jeher bekommt. Der Browser rechnet nur noch in Minuten.
 *
 * (2) DIE ZEITLEISTE WAR EIN SVG mit `viewBox` und skalierte ihre
 *     Beschriftung mit der Breite: auf 1280 px richtig, auf 390 px sechs
 *     Pixel hoch. Jetzt HTML mit Prozentbreiten -- der Text bleibt Text, nur
 *     der Balken skaliert.
 *
 * Dazu: Ein Schnitt, in dessen Zeitraum kein Punkt liegt, wird ABGELEHNT
 * statt einen leeren Einsatz anzulegen. Ohne gewanderte Punkte gaebe es
 * keinen Sperrvermerk, und ohne Vermerk faende das Rueckgaengig den Weg
 * zurueck nicht -- die Bedienerin bliebe mit einem Einsatz sitzen, den nur
 * der Papierkorb noch loswird.
 *
 * 12.7.0 LIEST GPX (S4/A3, E-S4-18) — das Gegenstueck zum Abruf aus S2/AP4.
 * Eine Spur, die auf einem anderen Geraet entstanden ist, kommt damit herein:
 * ueber „···" -> „GPX importieren" in der Tagesansicht, als Dialog. Zwei
 * Ziele, und die Wahl ist keine Kosmetik (E-R45-4): Ein RUHESEGMENT ist die
 * Aufzeichnung eines ganzen Dienstes, aus der man die Einsaetze danach
 * herausschneidet (der Regelfall); ein EINSATZ ist eine Datei, die genau
 * einer ist. Keine Migration.
 *
 * `time` IST PFLICHT, und eine Datei ohne Zeitstempel wird mit dieser
 * Begruendung abgelehnt statt still angenommen: Ohne Zeit gibt es keine
 * Punktreihenfolge, kein Schneiden und keine Phasenzeiten.
 *
 * DER LESER STEHT IN `gpx_lib.php`, neben dem Schreiber. GPX hat damit genau
 * eine Stelle in dieser Anwendung, die es kennt — ein Leser, der woanders
 * wohnt, laeuft frueher oder spaeter mit anderen Annahmen als der Schreiber,
 * und das faellt erst auf, wenn eine Datei hinaus, aber nicht wieder hinein
 * kommt.
 *
 * DIE FALLE, IN DIE DAS PAKET GETRETEN IST: Nach `children($ns)` schaltet
 * SimpleXML die Namensraum-Umgebung eines Knotens um — AUCH fuer Attribute.
 * `$pt['lat']` sucht danach ein `lat` IM GPX-Namensraum, und ein
 * unpraefigiertes Attribut liegt in KEINEM. Das Ergebnis war ein leerer
 * String, kein Fehler: Jeder Punkt fiel durch die Koordinatenpruefung, und
 * die Meldung lautete „enthält keinen einzigen Trackpunkt" — bei 61
 * vorhandenen. Jetzt ueber `attributes()`.
 *
 * 12.8.0 VERTEILT DIE ANDROID-APP (S4/A1 zur Haelfte, E-S4-16). Der
 * Geraete-Reiter bekommt die Karte „NAdoku fuer Android": Sie zeigt, was in
 * `server/apk/` LIEGT — Name, Groesse, Fassung, Datum und den gerechneten
 * SHA-256. Von Hand gepflegt wird nichts; eine Versionsangabe, die jemand
 * eintippt, stimmt am Tag des Eintippens und danach nie wieder. Liegt keine
 * Datei, erscheint die Karte gar nicht.
 *
 * DIE DATEI IST WEDER IM REPOSITORIUM NOCH IM DEPLOY. `server/apk/` steht in
 * `.gitignore` UND in der Ausnahmeliste des Deploys — dasselbe Muster wie
 * `config.php` und `sicherungen/`, und beides ist noetig: Ohne den zweiten
 * Eintrag loeschte der naechste Push die Dateien, denn die Action
 * synchronisiert `server/` und entfernt, was nicht ausgenommen ist.
 * Hochgeladen wird per FTPS durch die Betreiberin.
 *
 * DER NAME WIRD NICHT GEPRUEFT, SONDERN GESUCHT: `apk.php` liest den Ordner
 * und waehlt daraus aus. Ein Pfad, den der Aufrufer zusammensetzt, kommt
 * damit nie an `fopen()` — auch keiner mit `..`, keiner mit Nullbyte und
 * keiner mit einem Zeilenumbruch fuer die Content-Disposition-Kopfzeile. Der
 * Unterschied zu „gefaehrliche Zeichen entfernen" ist, dass hier nichts
 * vergessen werden kann.
 *
 * WAS AN A1 NOCH FEHLT und hier NICHT dabei ist: der QR-Kopplungscode
 * (E-S4-15) und der Nachtrag im JSON-Vertrag. Beides haengt an S5 und R42,
 * die noch nicht durch sind.
 *
 * 12.9.0 NIMMT AN, WAS DIE GERAETE SEIT EINEM JAHR SENDEN (S6, R42 und R44).
 * Die Uhr schickt beim Koppeln seit 1.9.0 einen Block ueber sich selbst, die
 * Handy-App seit 0.2.0 — `pair.php` hat ihn stillschweigend verworfen. Jetzt
 * landet er in drei Spalten an `devices` (Art, Modell, Rohangabe), und die
 * Geraeteliste sagt, was da eigentlich gekoppelt ist. MIT MIGRATION
 * (2026_09_02_geraetekennung).
 *
 * DIE UHR SENDET IHRE TEILENUMMER, NICHT IHREN MODELLNAMEN — den kennt sie
 * nicht. Aufgeloest wird sie auf dem Server (`geraetemodelle.php`, erzeugt
 * aus den Geraetedateien der Uhr-Plattform). Die dritte Spalte haelt die
 * Rohangabe daneben: Eine Uhr, die es beim Erzeugen der Tabelle noch nicht
 * gab, fiele sonst dauerhaft auf "unbekannt" — und zwar unwiederbringlich.
 * Mit der Rohangabe loest `tools/geraetemodelle/nachaufloesen.php` jede Zeile
 * spaeter erneut auf. Das Werkzeug gehoert zur Sache und nicht zum Komfort:
 * Bis dahin steht in `geraet_art` die ungepruefte Selbstauskunft, und die
 * Garmin-App sendet dort fest "uhr" — ein Radcomputer waere falsch gezaehlt.
 *
 * 12.9.1 FUELLT DIE MODELLTABELLE — 325 Teilenummern auf 173 Modelle, genau
 * die Zahl, die der JSON-Vertrag seit der Uhr-Seite nennt. Die Zuarbeit aus
 * Rahmenplan Abschnitt 6 ist damit erledigt; erzeugt wurde die Datei aus den
 * Geraetedateien der Uhr-Plattform, die Adresse ihrer Bereitstellung steht
 * weiterhin nicht im Repositorium.
 *
 * UND DIE ECHTEN DATEN HABEN EINE ANNAHME WIDERLEGT. `geraet_modell` stand auf
 * VARCHAR(64) — geraten, als die Dateien noch nicht vorlagen. Die Dateien
 * fuehren je Teilenummer die HARDWARE, und Garmin verkauft dieselbe Hardware
 * unter mehreren Namen: "fēnix 6X Pro / 6X Sapphire / … / quatix 6X Dual
 * Power" sind 153 Zeichen. Fuenf der 173 Modelle liegen ueber 64. Die Spalte
 * geht deshalb auf 191 (zweite Migration, 2026_09_02_geraetemodell_breiter);
 * gespeichert wird der volle Name, gekuerzt wird erst fuer die Anzeige
 * ("Uhr · fēnix 6X Pro …"). Die spaetere Zaehlung soll Hardwaregruppen
 * zaehlen, und genau die bezeichnet der Sammelname.
 *
 * DIE ZWEITE MIGRATION IST KEIN VERSEHEN, SONDERN DIE EINZIGE VERLAESSLICHE
 * RICHTUNG: Die erste ist gepusht, und `update.php` fuehrt jede Kennung genau
 * einmal aus — eine Installation, die sie schon gefahren haette, saehe eine
 * Aenderung an ihrem Rumpf nie.
 *
 * 12.9.2 NIMMT DIE MARKENZEICHEN AUS DEN MODELLNAMEN. Aus "Forerunner® 945"
 * wird "Forerunner 945"; 171 der 173 Namen waren betroffen. Drei Gruende, und
 * der erste wiegt am schwersten: Ein ® in UNSERER Oberflaeche sieht aus wie
 * eine Aussage ueber unsere Marke. Dazu: Ein Wechsel von ® auf ™ bei Garmin
 * ergaebe in der Zaehlung zwei Geraete, und ein Sammelname traegt bis zu drei
 * davon. Entfernt wird im ERZEUGER, nicht in der erzeugten Datei. `í`, `ē` und
 * der Halbgeviertstrich bleiben — sie sind Bestandteil der Namen und keine
 * Zeichen ueber ihnen. Keine Migration.
 *
 * NEBENBEI EIN FEHLER AUS S4: Beim Koppeln stand der Name eines Geraets fest
 * auf "Uhr". Seit es die Handy-App gibt, hiess ein frisch gekoppeltes Handy
 * in der Geraeteliste "Uhr". Die Vorgabe folgt jetzt der gemeldeten Art.
 *
 * UND DIE ZWEITE UHR GEHT JETZT RICHTIG (R44). Sitzung und Inhaltsschluessel
 * standen beide auf 30 Minuten und massen trotzdem Verschiedenes: die Sitzung
 * Inaktivitaet (erneuert bei jeder Anfrage), der Schluessel die Zeit seit dem
 * Entsperren (nie erneuert). `keyguard.js` erneuert den Zeitstempel jetzt bei
 * jedem Treffer.
 *
 * WAS DAS NICHT IST: das Ende des Entsperrdialogs. Der R44-Eintrag schrieb
 * dem Fristablauf den Dialog zu; das ist im Rahmenplan-Archiv am 01.09.2026
 * berichtigt worden und stimmt nicht. `verwerfeInhalt()` laesst den
 * Datenschluessel liegen, und der Inhaltsschluessel wird eine Zeile spaeter
 * OHNE Passwort neu entpackt — der Ablauf kostete ein stilles Neu-Entpacken,
 * gemessen 17 statt 1 ueber acht Stunden Dienst (`tools/fristprobe/`). Der
 * Dialog kommt vom tabweisen sessionStorage und bleibt; er steht jetzt als
 * gewollte Eigenschaft im Handbuch statt als unerklaerter Fehler.
 *
 *
 * 12.9.3 STELLT DEN BEGRIFF UM: „Sicherung" heisst ueberall „Backup" (R50,
 * Schritt 4 des Rahmenplans, S7). Anlass war eine Rueckmeldung zur Seite
 * selbst — die Karte hiess „Backup erstellen", der Knopf darin „Sicherung
 * erstellen": dieselbe Handlung, zwei Woerter, ein Bildschirm. Es ist eine
 * KORREKTURSTUFE, kein Nebenschritt: Es kommt keine Funktion und kein Feld
 * hinzu, nur Text.
 *
 * DAS GENUS ZIEHT MIT. „Die Sicherung" ist weiblich, „das Backup"
 * saechlich — Artikel, Possessiv, Adjektivendung, Relativpronomen und die
 * Pronomen im Folgesatz aendern sich mit. Komposita bekommen den
 * Bindestrich: Komplett-Backup, Backup-Ziel, Backup-Datei, Backup-Lauf.
 * Wo der Kopf des Kompositums nicht „Sicherung" war, bleibt das Genus, wie
 * es ist: „die Backup-Datei", „der Backup-Lauf".
 *
 * WAS BEWUSST STEHEN BLEIBT: der Ablagepfad `sicherungen/` (er steht in der
 * Ausnahmeliste des Deploys, ein umbenanntes Verzeichnis waere beim
 * naechsten Aufspielen weg), saemtliche Bezeichner, Dateinamen und
 * Formatkennungen (R5, R56), der Symbolname `sicherung`, das Verb „sichern"
 * in den Knoepfen (R56), die Versionsgeschichte in dieser Datei und der
 * Changelog — beide sind Beleg, nicht Oberflaeche.
 *
 * UND EIN FALLSTRICK, DEN DIE UMSTELLUNG FAST GESTELLT HAETTE: Die Kopfzeile
 * des Komplett-Backup-Dumps ist zugleich Text und Erkennungsmarke.
 * `wiederherstellen.php` prueft an ihr, ob ein Dump aus dieser Anwendung
 * stammt, und verlangt nur dann die Endmarke. Haette sie nur die neue
 * Schreibweise gesucht, gaelte jeder aeltere Dump als fremd — und ein
 * abgebrochener Stand waere klaglos eingespielt worden. Der Leser kennt
 * deshalb beide Schreibweisen; die alte darf am v1.0-Schnitt weg (R60).
 *
 * 12.9.4 BEHEBT EINEN FEHLER, DER SEIT 12.2.0 UNBEMERKT LAG und beim
 * Pruefen von S7 auffiel (Backlog Nr. 89): Der Job „Komplett-Backup der
 * Installation" lief nie. `job_komplett()` trug eine Konstante als
 * Parameter-Vorgabewert, die erst im Rumpf geladen wird — PHP wertet
 * Vorgabewerte beim Aufruf aus. Das geplante Komplett-Backup war damit
 * seit S2/AP8 ohne Wirkung.
 *
 *
 * 13.0.0 DREHT DIE KOPPLUNG UM (S5, Paket A; R49) — und wechselt das
 * Verfahren fuer den Geraeteschluessel. Beides zusammen ist die Hauptnummer:
 * ein anderer Weg durch die Anwendung UND eine Aenderung an der
 * Verschluesselung, mit Migration.
 *
 * DER WEG: Bis 12.9.4 erzeugte das Web den Code, und die Uhr tippte ihn.
 * Jetzt holt sich das GERAET mit `start` eine Kopplungssitzung und zeigt den
 * Code, ein Mensch gibt ihn im Web in sein Konto ein, und das Geraet
 * bestaetigt mit Ja — erst dann entsteht die devices-Zeile. Bis dahin sind
 * Kennung und Schluessel schwebend (Tabelle pair_sessions), und ingest.php
 * weist sie ab. Vier Anliegen an pair.php (start, status, bestaetigen,
 * trennen), drei Ratenschutz-Toepfe, eine Obergrenze offener Sitzungen. Die
 * Migration 2026_09_03_kopplungssitzungen legt pair_sessions an und LOESCHT
 * pair_codes. Paket A ist die Serverseite; die Geraeteseite im Web (B) und
 * die Uhr (C) folgen auf demselben Zweig, und das Ganze kommt einmal auf
 * main — bis dahin laeuft der Knopf „Kopplungscode erzeugen" ins Leere.
 *
 * DAS VERFAHREN: Der Geraeteschluessel sind 24 Zufallsbytes. bcrypt bremst das
 * Raten eines schwachen Geheimnisses; bei 192 Bit Zufall bremst es nur den
 * Server — 228 ms je Upload, und beim Abfragetakt der neuen Kopplung 27 s je
 * Sitzung. Geraete- und Sitzungsschluessel liegen jetzt als SHA-256,
 * verglichen in konstanter Zeit; das Anmeldetoken bleibt bcrypt, weil es
 * gestrecktes Passwort ist (Regel bei GERAET_VERGLEICHSWERT in db.php). Der
 * Preis, bewusst gezahlt: Ein vor 13.0.0 gekoppeltes Geraet traegt einen
 * bcrypt-Hash, der nie mehr passt, und koppelt einmal neu. Einen Umhash-Pfad
 * gibt es absichtlich nicht — ab 1.0 gibt es genau eine, frisch installierte
 * Installation (R60).
 *
 * 13.0.1 BEHEBT EINEN STILLEN DATENVERLUST IM UPLOAD, der aelter ist als S5
 * und bei der Gegenlesung des S5-Zusatzes auffiel (Befund B5.3). Der Upsert in
 * `ingest.php` schrieb `ended_at`, `distance_m` und `ascent_m` bedingungslos
 * aus dem eintreffenden Paket — waehrend `final` seit jeher mit GREATEST
 * geschuetzt war. Genau diese drei Spalten traegt ein NICHT-finales Paket
 * aber nicht. Kam eines nach dem finalen an — jede Wiederholung eines
 * frueheren Teilstuecks ist so eines —, blieb ein abgeschlossener Einsatz
 * ohne Ende, ohne Strecke und ohne Anstieg zurueck. Die Antwort lautete "ok".
 * Jetzt steht dort COALESCE: Ein Wert ueberschreibt, ein NULL laesst stehen;
 * eine Berichtigung bleibt moeglich. Nachgestellt und seither gehalten von
 * Teil 7 der Ingestprobe. Keine Migration — was einmal geloescht wurde, laesst
 * sich nicht zurueckholen; auf der Betreiberinstallation ist kein Fall
 * bekannt.
 *
 * 13.1.0 IST DIE GERAETESEITE ZUM NEUEN WEG (S5 Paket B). Die Karte „Gerät
 * koppeln" hat jetzt drei Zustaende statt einem Knopf: ein Feld „Code vom
 * Geraet", eine Rueckfrage mit Art, Modell und Kennung — das erste der beiden
 * Tore aus E-S5-05 —, und einen Wartezustand, der von selbst nachlaedt, sobald
 * das Geraet Ja gesagt hat (E-S5-53). Dafuer kommen ein angemeldeter Endpunkt
 * (api/kopplung_stand.php, nimmt KEINE Eingabe) und eine kleine Skriptdatei
 * (assets/kopplung.js) dazu; ohne JavaScript bleibt der Weg vollstaendig.
 *
 * NEBENNUMMER UND NICHT HAUPTNUMMER, obwohl sich der Weg durch die Seite
 * aendert: Es ist derselbe Reiter, dieselbe Karte, dieselben Bausteine, und
 * die Migration lag in 13.0.0. Was hier dazukommt, sind Felder und Zustaende —
 * genau das, wofuer die Nebennummer da ist.
 *
 * DAZU ZWEI DINGE, DIE AELTER SIND ALS S5. Die Handanlage vergab
 * Geraetekennungen aus VIER Zufallsbytes, waehrend die Kopplung seit M4-08
 * sechzehn nimmt — zwei Wege zu derselben Spalte, und der schwaechere war der,
 * den niemand geprueft hat (B-S5-01). Und der Reiter trug ZWEI primaere
 * Knoepfe; Design.md 9.16 nennt das als Anti-Muster („Keiner ist mehr die
 * Haupthandlung"). Die Handanlage ist jetzt neutral — sie ist ausdruecklich
 * „die Alternative zum Koppeln" (B-S5-09).
 *
 * 13.1.1 NIMMT ZWEI DINGE ZURUECK, die bei der Vorarbeit zu Paket D auffielen.
 * Der Topf `pair` hat DREI Verbraucher, nicht zwei: pair.php, das Token von
 * jobs.php — und gpx.php, das damit die Freigabelinks der Spuren schuetzt (an
 * sieben Zaehlstellen). Ein gelungenes `trennen` rief `rate_erfolg('pair')`
 * und leerte den Zaehler fuer alle drei; wer Freigabelinks durchprobierte,
 * holte sich mit einem getrennten eigenen Geraet zehn frische Versuche. Der
 * Aufruf ist ersatzlos weg — seit 13.0.0 gibt es an diesem Endpunkt nichts
 * mehr zu vertippen. Dazu vier Meldungen an das Geraet, die in
 * Ersatzschreibung standen, obwohl die Uhr sie anzeigt.
 *
 * 13.1.2 IST DIE DOKUMENTATION ZUM NEUEN WEG (S5 Paket D, erste Haelfte) —
 * und die Stellen im Server, die noch die alte Richtung beschrieben. Zwei
 * Dinge daran sind mehr als Text: Die Trennen-Mail schickte den Empfaenger
 * auf einen Knopf, den es seit 13.0.0 nicht mehr gibt („Kopplungscode
 * erzeugen"), und der Demo-Hinweis sagte „Uhr koppeln", obwohl seit 12.9.0
 * auch Handys koppeln. Beides sind sichtbare Texte, deshalb ueberhaupt eine
 * Nummer; alles andere sind Kommentare, die eine falsche Begruendung trugen —
 * etwa die Obergrenze MAX_GERAETE, die sich auf „wer einen Kopplungscode
 * abfaengt" berief. Das Abfangen traegt seit E-S5-03 nicht mehr: Der Code
 * weist nichts aus. Die Grenze bleibt richtig, ihre Begruendung war es nicht.
 *
 * KORREKTURSTUFE UND NICHT NEBENNUMMER: Es kommt keine Funktion dazu und
 * keine weg. Was sich aendert, sind zwei Saetze auf dem Bildschirm und die
 * Erzaehlung darum herum. Der Rest des Pakets — Handbuch, Geraete-Eingabe,
 * die Uhr-Abschnitte der Technik — wartet auf Paket C und kommt in der
 * zweiten Haelfte.
 *
 * 13.2.0 IST DER WARTUNGSMODUS (S5 Paket W). Ein Schalter auf der
 * Wartungsseite schliesst die Installation voruebergehend fuer alle ausser
 * der Verwaltung: Jede andere Anfrage bekommt 503 statt eines 500 aus einer
 * halb umgebauten Datenbank. Das ist der Unterschied, auf den es ankommt —
 * der JSON-Vertrag sagt zu 5xx „spaeter unveraendert erneut versuchen", und
 * Uhr wie Handy halten sich daran. Sie puffern und liefern nach. KEIN CLIENT
 * WURDE DAFUER GEAENDERT (E-S5W-08); das Verhalten ist seit S4 da.
 *
 * DER ZUSTAND IST EINE DATEI (`server/wartung.lock`), keine Zeile in
 * `app_state`. Der Wartungsmodus wird gerade dann gebraucht, wenn die
 * Datenbank umgebaut wird oder eine Migration auf halber Strecke gescheitert
 * ist; ein Schalter, der die Datenbank fragt, ob er schalten darf, ist im
 * entscheidenden Moment stumm. Die Datei steht in `.gitignore` UND in der
 * Ausnahmeliste des Deploys — ohne den zweiten Eintrag loeschte der Push sie
 * mitten im Update, fuer das sie da ist.
 *
 * DAS TOR SITZT IN `db.php`, hinter `json_out()` und vor jeder Verbindung,
 * und NICHT in `auth_guard.php`: Dort liefen nur die Seiten durch.
 * `ingest.php` und `pair.php` laden `db.php` direkt — und das sind die
 * beiden, auf die es ankommt, weil sie die Daten der Uhr bringen.
 *
 * AUSGENOMMEN sind sechs Skripte (E-S5W-04): update.php und
 * wiederherstellen.php (die Arbeit selbst und der Rueckweg), jobs.php (das
 * Komplett-Backup laeuft WAEHREND der Wartung — genau dann ist es
 * konsistent), login.php und logout.php, install.php. Alles unter `assets/`
 * laeuft ohnehin nicht durch PHP.
 *
 * NEBENNUMMER: eine neue Funktion, keine Migration, kein geaenderter
 * Datenweg. Wer nicht schaltet, merkt nichts — der Aufruf kostet einen
 * `file_exists()`.
 *
 * EINE ENTSCHEIDUNG GEGEN DIE EMPFEHLUNG (E-S5W-09, Auftraggeber): Wer sich
 * waehrend der Wartung anmeldet und NICHT verwaltet, wird sofort wieder
 * abgemeldet und sieht die Wartungsseite — nicht das Anmeldeformular, das
 * laese sich wie „Passwort falsch". Damit liegt waehrend des Umbaus keine
 * Sitzung mit entsperrtem Inhaltsschluessel herum, und keine Anmeldung
 * schreibt `last_login`, waehrend `update.php` das Schema aendert. Die
 * Ratenschutz-Zaehler werden trotzdem geleert: Das Passwort WAR richtig.
 *
 * 13.3.0 MACHT EINE DOPPELUNG SICHTBAR, DIE ES SEIT S4 GIBT (R57, E-S4-76).
 * Zeichnen zwei Geraete denselben Dienst auf — die Uhr am Handgelenk und das
 * Handy in der Tasche —, legt JEDES einen eigenen Diensttag an: `day_refs` ist
 * je Geraet geschluesselt, das eine findet die Kennung des anderen nicht. Es
 * geht dabei nichts verloren und nichts wird ueberschrieben; es steht alles
 * doppelt. Derselbe Einsatz zweimal, dieselbe Spur zweimal, und in der
 * Jahresuebersicht zaehlt der Dienst doppelt.
 *
 * Gemessen wurde das in S4 (F-S4-D, zwei Geraete gegen eine oertliche
 * Installation: Diensttag 53 und 54, je ein Einsatz). Aufgefallen waere es
 * sonst erst in der Statistik.
 *
 * DIE NEBENNUMMER, KEINE HAUPTNUMMER: Es kommt eine Anzeige dazu, sonst
 * nichts. Kein Datenmodell, keine Migration, kein veraenderter Weg durch die
 * Anwendung — wer die Doppelung nicht hat, merkt von dieser Fassung nichts.
 *
 * UND KEINE AUTOMATIK, mit Absicht (E-S4-76 gegen E-S4-50): Die beiden Tage
 * bleiben stehen. Sie sind zwei vollstaendige Aufzeichnungen, und ein stiller
 * Automatismus muesste raten, welche gilt. Der Hinweis macht sie sichtbar und
 * fuehrt auf `diensttag_zusammenfuehren.php`, wo ein Mensch entscheidet.
 *
 * Die Schwelle steht auf einer VIERTELSTUNDE (`DT_UEBERLAPPUNG_MIN`). Der
 * eigene Dienstwechsel ueberschneidet sich regelmaessig um Minuten — wer den
 * neuen Tag beginnt, bevor er den alten beendet hat. Ein Hinweis, der dabei
 * jedes Mal erschiene, wuerde ueberlesen und stuende dann unbemerkt da, wenn
 * er einmal wirklich gemeint ist.
 *
 * 14.0.0 SAGT ENDLICH, WOHER EIN EINSATZ KOMMT — UND MIT WELCHEM GERAET
 * (R64, Backlog Nr. 83; Migration 2026_09_04_herkunft_geraet). Die
 * Hauptnummer steht fuer beides zusammen: Das Datenmodell aendert sich, und
 * eine Migration ist zwingend.
 *
 * ZWEI DINGE WAREN FALSCH, UND ZWAR STILL. `missions.origin` kannte drei
 * Werte — watch, manual, import. Seit Web 12.8.0 sendet auch ein
 * Android-Handy, seit S4 laesst sich ein Einsatz an einer Wear-OS-Uhr
 * beginnen; beide landeten auf 'watch'. Ein Handy-Einsatz trug die Plakette
 * "Uhr", und niemand konnte es der Anzeige ansehen. Und der Schnitt
 * (api/schneiden.php) legte seinen Einsatz als 'manual' an, obwohl ihn
 * niemand von Hand eingegeben hat. Die Herkunft kennt jetzt SECHS Werte —
 * watch, android, wear, manual, import, schnitt —, einen je Client-App und
 * nicht je Hersteller (E-R64-02).
 *
 * DIE SPALTE IST DABEI VOM ENUM AUF VARCHAR(16) GEWECHSELT. Dieselbe
 * Begruendung wie bei `devices.geraet_art`: Ein ENUM braucht fuer jeden neuen
 * Client eine Migration. Der Wertevorrat steht jetzt EINMAL im Code
 * (HERKUNFT_WERTE in geraete_lib.php), die Ableitung aus dem
 * `client_ref`-Praefix ebenso (herkunft_ableiten()). Bis hierher stand diese
 * Regel DREIMAL — in der Migration 2026_07_30 als SQL, in
 * edbak_origin_edited() als PHP und als Beschreibung in einem Kommentar in
 * api/export_data.php —, obwohl der Kommentar in backup_lib.php ausdruecklich
 * verlangte, sie nicht zweimal unterschiedlich hinzuschreiben. Als Android
 * und Wear dazukamen, wuchs keine der drei mit. Genau so entsteht der Fehler
 * eine Zeile weiter oben.
 *
 * DAZU DIE MOMENTAUFNAHME DES GERAETS. `missions` und `rest_segments`
 * bekommen `geraet_art` und `geraet_modell`, kopiert aus `devices` in dem
 * Augenblick, in dem der Datensatz entsteht — und danach nie nachgezogen.
 * Der Grund fuer die Kopie statt eines Verweises ist gemessen: 82 von 82
 * Einsaetzen und 95 von 95 Segmenten des Demo-Kontos standen am 02.09.2026
 * OHNE Geraeteverweis da, obwohl 76 davon von einer Uhr stammen. `device_id`
 * steht auf ON DELETE SET NULL, und Trennen ist bei geteilter Uhr der
 * vorgesehene Normalfall (R47). Ein Verweis, der im Regelbetrieb reisst,
 * beantwortet die Frage "welches Geraet hat das aufgezeichnet" nie.
 *
 * DER PREIS IST BENANNT (E-R64-05): Ein Einsatz, dessen Modell beim Anlegen
 * unbekannt war, traegt dauerhaft "unbekannt". Das ist die Definition einer
 * Momentaufnahme, und dafuer ueberlebt sie Trennen, Loeschen des Geraets und
 * die Konto-Sicherung.
 *
 * DIE MIGRATION FUELLT DEN BESTAND NACH, solange die Verweise noch stehen —
 * deshalb laeuft sie jetzt und nicht spaeter; jedes Trennen bis dahin liesse
 * eine Zeile mehr unwiederbringlich leer. Wo `device_id` schon NULL ist,
 * bleibt die Momentaufnahme NULL, dauerhaft: "Unbekannt" ist eine Sache der
 * ANZEIGE und nicht der Spalte (dieselbe Linie wie an `devices`).
 *
 * WAS DIESE FASSUNG NOCH NICHT TUT: Die Sicherung traegt die neuen Felder
 * noch nicht (Nutzlast 9 kommt als naechstes, zusammen mit den
 * Sperrvermerken des Schnitts, Backlog Nr. 63), und die Beschriftungen von
 * CSV und Einsatzansicht kennen die drei neuen Werte noch nicht — sie zeigen
 * bis dahin den ROHWERT. Das ist Absicht: Ein unbekannter Herkunftswert darf
 * nicht als "Uhr" erscheinen (E-R64-09), und der Rueckfall auf 'uhr' ist
 * genau in dieser Fassung entfallen.
 *
 * MIGRATION ERFORDERLICH: 2026_09_04_herkunft_geraet. Ohne sie fehlen die
 * vier Spalten, und jeder Upload der Uhr scheitert.
 *
 * NEBENBEI GERADEGERUECKT: Der Absatz zu 13.3.0 stand in dieser Datei VOR dem
 * zu 13.2.0 — beim Eintragen war er an die falsche Stelle geraten. Die
 * Erzaehlung liest sich der Reihe nach; das ist ihr einziger Zweck.
 *
 * 14.1.0 BESCHRIFTET, WAS 14.0.0 ERFASST HAT — und raeumt damit einen Zustand
 * ab, der zwischen den beiden Fassungen kurz schlechter war als vorher: Die
 * Einsatzansicht zeigte fuer `android`, `wear` und `schnitt` weiter "Uhr",
 * weil ihre Zuordnungstabelle nur drei Werte kannte, und der CSV-Export gab
 * den Rohwert aus.
 *
 * SECHS PLAKETTEN STATT DREI: Uhr, Handy, Wear, manuell, importiert, Schnitt.
 * Und der Rueckfall ist ueberall der ROHWERT (E-R64-09) — nicht "Uhr". Ein
 * kuenftiger Client, dessen Beschriftung hier noch fehlt, soll auffallen und
 * nicht in einer falschen Kategorie verschwinden. Das ist der ganze Grund fuer
 * die Aenderung an dieser Stelle: Eine falsche Antwort, die wie eine richtige
 * aussieht, ist schlechter als eine unschoene.
 *
 * IM CSV HEISST DIE ZWEITE UHR `wear` UND NICHT `uhr`. `uhr` ist seit dem
 * ersten Export die Garmin-App; eine zweite Uhr unter demselben Wort machte
 * jede Auswertung, die auf `uhr` filtert, rueckwirkend mehrdeutig — und zwar
 * ohne dass sich das der Datei ansehen liesse.
 *
 * DAZU ZWEI SPALTEN IN ZWEI DATEIEN: `geraet_art` und `geraet_modell` am ENDE
 * von `einsaetze.csv` und `ruhezeiten.csv`, dazu ihre Beschreibung in
 * `felder.csv`. Am Ende und nicht neben `herkunft`, weil Auswertungen Spalten
 * von links zaehlen — und weil die beiden nicht den Einsatz beschreiben,
 * sondern das Geraet, das ihn aufgezeichnet hat.
 *
 * EXCEL BLEIBT UNVERAENDERT, beide Fassungen (E-R64-10, Auftraggeber). Die
 * Uebersichtstabelle liest ein Mensch, und sie beantwortet "was ist passiert",
 * nicht "womit wurde es aufgezeichnet". Ihr Spaltensatz ist damit derselbe wie
 * in 13.3.0.
 *
 * DER RUECKIMPORT NIMMT DIE ZWEI SPALTEN NICHT — wie `herkunft` und `edited`:
 * Sie beschreiben das Quellkonto. Sie stehen trotzdem mit `target: null` im
 * Profil, weil dessen Schluesselliste zugleich die Beschreibung dessen ist,
 * was das Format kennt; eine fehlende Spalte saehe aus wie eine vergessene.
 * Unbekannte Spalten stoert der Rueckimport ohnehin nicht — er ordnet ueber
 * Namen zu und geht ueber alles hinweg, was er nicht kennt.
 *
 * NEBENNUMMER: zwei Spalten und sechs Beschriftungen, kein Datenmodell, keine
 * Migration. Die vier Spalten dafuer hat 14.0.0 angelegt.
 *
 * 14.2.0 BRINGT DIE SPERRVERMERKE DES SCHNITTS DURCH DIE SICHERUNG — und die
 * Momentaufnahme des Geraets gleich mit (Backlog Nr. 63 und R64, Nutzlast
 * 8 -> 9).
 *
 * DER FEHLER, DEN ES BEHEBT, IST STILL UND ENDGUELTIG. Wer eine Ruhezeit
 * schneidet, hinterlaesst einen Vermerk in `track_cuts`: Dieser Zeitraum ist
 * fuer nachgelieferte Punkte gesperrt, sonst laege die Fahrt hinterher in
 * Einsatz UND Segment. Der Vermerk stand in keiner Konto-Sicherung. Nach
 * einem Wiedereinspielen lieferte eine Uhr mit gepuffertem Speicher den
 * geschnittenen Bereich nach, und er kam durch — ohne Meldung, ohne Weg
 * zurueck.
 *
 * ER REIST UEBER VERWEISE, nicht ueber Kennungen: `quelle_ref` ist die
 * `client_ref` der Quelle. Datenbanknummern vergibt das Einspielen neu; das
 * ist dieselbe Ueberlegung, die schon `day_refs` und `spur_ref` tragen.
 * `erstellt_am` reist mit — ein Vermerk sagt, WANN geschnitten wurde, und das
 * ist ein Ereignis der Vergangenheit, keine Frist dieser Installation
 * (anders als `deleted_at`, das neu entsteht).
 *
 * DREI AUSGAENGE BEIM EINSPIELEN, und sie werden gezaehlt: uebernommen,
 * uebersprungen (der Einsatz stand schon da — eine bewusst zurueckgenommene
 * Sperre darf nicht wiederbelebt werden), verworfen (kein Ziel, keine Quelle,
 * unbrauchbare Werte). „Uebersprungen" und „verworfen" in eine Zahl zu legen
 * waere derselbe Fehler wie bei den uebersprungenen Einsaetzen: nicht deutbar.
 *
 * NEBENNUMMER UND KEINE HAUPTNUMMER, und das ist eine Entscheidung mit
 * Praezedenzfall. Es aendert sich KEINE Spalte, es gibt KEINE Migration — die
 * vier Spalten hat 14.0.0 angelegt. Was sich aendert, ist ein Dateiformat,
 * und genau dafuer steht 11.1.0 (Nutzlast 7 -> 8, „nur das Dateiformat
 * aendert sich, das Datenmodell nicht"). Die Hauptnummer fuer R64 ist mit
 * 14.0.0 bereits gestiegen; dies ist die zweite Haelfte derselben Aenderung.
 *
 * WAS MITGEZOGEN WERDEN MUSSTE, alle fuenf Stellen: `edbak_build()` schreibt
 * 9, `NUTZLAST_HOECHSTENS` steht auf 9, das Admin-Manifest nennt 9, und die
 * beiden Stellen, die die Fassung beim Einspielen SETZEN
 * (`adminbackup_lib.php`, `api/backup_eintraege_restore.php`). Der eine
 * Vergleich, der ueber den Spurweg entscheidet, bleibt auf `>= 8` — eine
 * Anhebung auf 9 wuerfe jede vorhandene 8er-Datei in den Punktlisten-Zweig
 * und verloere still alle Spuren. Das ist der Fund F-S2-E in Gegenrichtung.
 *
 * DREI ALTE FEHLER SIND DABEI MIT AUFGEFALLEN und behoben worden. Sie haben
 * nichts mit R64 zu tun, standen ihm aber im Weg:
 *
 *   1. DIE UMDATIERUNG EINES DIENSTTAGS verschob Einsaetze, Segmente,
 *      Phasen, Reanimationsereignisse und jeden Spurpunkt — die Sperrvermerke
 *      nicht. Der Vermerk sperrte danach einen Zeitraum, in dem die Spur gar
 *      nicht mehr liegt: Nachgelieferte Punkte kamen wieder durch, und die
 *      Fahrt lag doppelt. Seit Web 12.5.0 so, folgenlos nur, solange der
 *      Vermerk die Datenbank nicht verliess. Ab Nutzlast 9 reiste das falsche
 *      Fenster in jede Sicherung.
 *   2. DER DEMO-RESET raeumte `track_points` und `track_blobs` ausdruecklich
 *      ab und `track_cuts` nicht. Mit dem Schnitt im Demo-Bestand (E-R64-16)
 *      haette das alle 30 Minuten einen verwaisten Vermerk hinterlassen —
 *      48 am Tag, keiner davon je wieder auffindbar.
 *   3. FIXTURE UND DEMO-RESET fuehrten von `devices` nur drei Spalten mit.
 *      Die Geraeteseite des Demo-Kontos haette „Gerät unbekannt" gezeigt,
 *      waehrend die Einsaetze daneben ihre Momentaufnahme tragen.
 *
 * UND ZWEI PRUEFMITTEL HABEN AUFGEHOERT ZU PRUEFEN, ohne dass es auffiel:
 * Die Wiederherstellungsprobe starb mitten in Teil 9 an einem fehlenden
 * `require` — nach dreiundvierzig gruenen Zeilen, und alles dahinter lief
 * NIE. Die Containerprobe suchte in einer Meldung einen Wortlaut, den es
 * nicht mehr gibt, und stand deshalb dauerhaft auf einem Fehlschlag. Beides
 * ist behoben; die Probe zaehlt jetzt 94 Erwartungen statt der 30, die in
 * ihrem Kopf standen.
 */
/*
 * 14.2.1 SCHLIESST DEN KREIS DES REFERENZBESTANDS — und behebt dabei einen
 * Fehler, den erst er sichtbar gemacht hat.
 *
 * DER REFERENZBESTAND TRAEGT JETZT, WAS ER PRUEFEN SOLL. Bis hierher legte
 * das Einspielwerkzeug seine zwei Geraete ueber die Geraeteseite an — mit
 * Beschriftung und sonst nichts. `geraet_art` und `geraet_modell` blieben
 * NULL, und weil `ingest.php` die Momentaufnahme beim Anlegen von dort
 * kopiert, trug der ganze Bestand eine leere. Der edbak-Kreislauf verglich
 * damit NULL gegen NULL und belegte fuer R64 nichts. Seit diesem Stand gehen
 * die zwei Geraete den echten Kopplungsweg (`pair.php`), und eines davon ist
 * ein Handy: Von den sechs Herkunftswerten belegte der Bestand vorher einen,
 * jetzt alle sechs.
 *
 * UND ER TRAEGT EINEN SCHNITT. Damit prueft der Demo-Reset auf dem
 * Produktivserver den Sperrvermerk aus 14.2.0 alle 30 Minuten von selbst —
 * ein besserer Beleg als jede eigens gebaute Probe.
 *
 * DER FEHLER: DIE ANWENDUNG SCHRIEB EINE DATEI, DIE SIE NICHT LESEN KONNTE.
 * Die CSV-Spalte `uhrzeit_ortszeit` kam aus Phase 2 („Alarmierung"); der
 * eigene Import liest sie als den START des Einsatzes und verlangt sie. Ein
 * geschnittener Einsatz hat keine Phase 2 — die Spalte blieb leer, und der
 * Import wies die Zeile ab. Bei einem Einsatz von der Uhr fallen Alarmierung
 * und Beginn zusammen; deshalb ist es nie aufgefallen. Jetzt faellt die
 * Spalte auf den Einsatzbeginn zurueck. Fuer jeden Einsatz mit Phase 2
 * aendert sich nichts.
 *
 * WAS SICH FUER EINE BESTEHENDE INSTALLATION AENDERT: nichts am Datenmodell,
 * keine Migration. Das Demo-Konto zeigt nach dem naechsten Reset den neuen
 * Bestand — mit Geraetemodellen auf der Geraeteseite und einem geschnittenen
 * Einsatz mit Plakette.
 */
/*
 * 14.2.2 SAGT „DAUER" UND MEINT ES.
 *
 * Die Einsatztabelle rechnete die Dauer aus dem Beginn und der PHASE 9
 * („Endzeit des Einsatzes"). Fehlte die Phase, stand dort „kein Ende" --
 * auch an einem Einsatz, der abgeschlossen ist und ein `ended_at` traegt.
 * Der Kommentar an der Stelle nannte das ausdruecklich gewollt, und fuer
 * einen Einsatz von der Uhr fiel es nie auf: Sie setzt beim Abschliessen
 * beides, und beides ist derselbe Zeitpunkt.
 *
 * ZWEI ARTEN HABEN KEINE PHASE 9 UND SIND TROTZDEM ZU ENDE: der
 * GESCHNITTENE Einsatz (`api/schneiden.php` vergibt nur 3, 4 und 7) und der
 * IMPORTIERTE, dessen Datei keine Endphase fuehrt. Seit 14.2.1 steht ein
 * geschnittener dauerhaft im Demo-Konto -- also auf dem Produktivserver,
 * sichtbar fuer jeden, der die Anwendung ausprobiert.
 *
 * GEMESSEN, DASS SICH NICHTS ANDERES AENDERT: Ueber 330 aktive Einsaetze
 * fallen Phase 9 und `ended_at` NULLMAL auseinander (323 gleich, 3 mit Ende
 * ohne Phase 9, 4 offene ohne beides). „kein Ende" bleibt genau dort, wo es
 * hingehoert -- am Einsatz ohne Ende.
 *
 * VIER STELLEN, NICHT EINE. Dieselbe Rechnung stand in `api/day.php`,
 * `api/range.php` und `api/suchindex.php`; die Einsatzansicht fragte
 * ueber `has_p9` dasselbe. Das Merkmal heisst jetzt `hat_ende` und sagt,
 * was gemeint ist. Drei korrelierte Unterabfragen auf `mission_phases`
 * sind dabei ersatzlos entfallen -- der Wert stand als Spalte daneben.
 */
/*
 * 15.0.0 GIBT DEM BETRIEB EINE EIGENE ROLLE.
 *
 * Bis hierher gab es zwei Rollen und drei Zielgruppen. Wer Konten anlegt und
 * Rechtstexte pflegt, und wer den Serverschluessel erzeugt, den Wartungsmodus
 * schaltet, Migrationen ausfuehrt und die Speichergrenze setzt, war dieselbe
 * Rolle `admin` — obwohl das Erste ein Konto trifft und das Zweite die ganze
 * Installation. Die Sichtung in S8 hat das als Erstes gefunden (B-S8-15), und
 * der Auftraggeber hat daraus eine Rolle gemacht: `betreiberin`, dritter Wert
 * in `users.role`, mit einer Migration (R75).
 *
 * WARUM DAS EINE HAUPTNUMMER IST. Nicht wegen der Spalte — ein ENUM um einen
 * Wert zu erweitern ist wenig. Sondern weil sich die Wege durch die Anwendung
 * aendern, und zwar je nachdem, wer angemeldet ist: Ein Admin, der gestern
 * die Wartungsseite sah, sieht sie ab hier nicht mehr, wenn ihn jemand
 * zurueckstuft. Dieselbe Begruendung wie bei 7.0.0, die ebenfalls ohne
 * Datenmodell-Umbau eine Hauptnummer bekam.
 *
 * DIE HIERARCHIE IST DAS GANZE MODELL: BetreiberIn ⊇ Admin ⊇ NutzerIn. Es
 * gibt keine Handlung, die nur ein Admin darf. Deshalb liefert `ist_admin()`
 * fuer beide obere Rollen wahr und keine bestehende Seite braucht eine zweite
 * Pruefung — die Aenderung war eine Zeile. Genau dafuer stand die eine
 * Rollenpruefung (M1-15) seit Web 4.0.0 da; der Kommentar dort hat den Fall
 * „eine dritte Rolle" wortwoertlich vorhergesagt.
 *
 * ALLE VORHANDENEN ADMINS WERDEN BETREIBERINNEN. Die Alternative — nur das
 * aelteste Konto — waere enger und praktisch falsch: Sie naehme bestehenden
 * Admins ohne Ankuendigung Zugriff auf Seiten, die sie gestern bedient haben.
 * Wer zurueckstufen will, tut es danach von Hand.
 *
 * ZWEI SCHRANKEN, DIE DAS MODELL TRAGEN, und beide serverseitig: Nur eine
 * BetreiberIn vergibt oder entzieht die Rolle, und das LETZTE
 * BetreiberIn-Konto laesst sich weder zurueckstufen noch loeschen. Ohne die
 * zweite koennte sich eine Installation aus ihrem eigenen Betriebsbereich
 * aussperren, und der Rueckweg fuehrte ueber die Datenbank — auf geteiltem
 * Hosting also nirgendwohin.
 *
 * MIGRATION ZWINGEND: `2026_09_05_rolle_betreiberin`. Sie ist idempotent (der
 * zweite Lauf sieht den Wert im ENUM und tut nichts) und nimmt nichts weg.
 * `install.php` legt das erste Konto ab hier als BetreiberIn an.
 *
 * WAS NOCH NICHT DA IST: der Bereich „Betrieb" selbst. Er entsteht in den
 * folgenden Paketen von S8 (Updates, Hintergrundjobs, Servereinstellungen,
 * Status, Statistik); bis dahin sieht eine BetreiberIn genau das, was ein
 * Admin sieht. Die Rolle kommt zuerst, weil jede dieser Seiten mit
 * `require_betreiberin()` beginnt.
 */
/*
 * 15.1.0 LOEST DIE WARTUNGSSEITE AUF.
 *
 * Sie trug neun Bloecke auf einer Flaeche — Wartungsmodus, Schluesselableitung,
 * Logo, Umgebung, Hintergrundjobs, Job-Ausloeser, Einsaetze ohne Diensttag,
 * Migrationsliste und den Balken darueber. Backlog Nr. 77 hiess „aufteilen";
 * die S8-Sichtung hat daraus „aufloesen" gemacht (E-S8-05, B-S8-03: vier
 * verschiedene Anliegen auf einer Seite).
 *
 * DREI NEUE SEITEN, JEDE MIT EINEM ANLIEGEN:
 *
 *   betrieb_updates.php   Wartungsmodus und ausstehende Migrationen. Beides
 *                         gehoert zusammen, weil es DERSELBE Vorgang ist: Der
 *                         Ablauf eines Updates ist fuenfstufig, und drei
 *                         Stufen finden hier statt. Die Karte nennt ihn.
 *   betrieb_jobs.php      Zustand je Job und die drei Ausloeser.
 *   betrieb_server.php    Speichergrenze, Warnschwellen, Belegung, Ablage.
 *
 * Alle drei verlangen `require_betreiberin()` (R75) — sie sind der erste
 * Inhalt des Blocks BETRIEB, den 15.0.0 vorbereitet hat. Bis AP5 sind sie nur
 * ueber die Adresse erreichbar; `update.php` traegt uebergangsweise eine Liste
 * mit den drei Zielen und die Logo-Karte, die in AP3 umzieht.
 *
 * WAS DIE SPEICHERGRENZE HIER ZU SUCHEN HAT. Sie stand unter „Backups", galt
 * aber auch fuer die Komplett-Staende, und die Komplett-Seite verwies mit
 * einem Satz auf sie (B-S8-06). Jetzt steht sie mit der Belegung an einer
 * Stelle. Dazu ein ZWEITER Bezug, den es vorher nicht gab: die ganze
 * Installation gegen den Webspace laut Hosting. Datenbank und Dateien werden
 * einmal taeglich im Aufraeumjob gemessen (`speicher_lib.php`); der freie
 * Webspace wird NICHT gemessen, weil `disk_free_space()` auf geteiltem Hosting
 * den Datentraeger des Hosts zeigt und nicht die Quota. Er ist eine ANGABE.
 *
 * DER MIGRATIONSKATALOG STEHT JETZT IN `migration_lib.php`. Zwei Aufrufer
 * brauchen ihn — die neue Seite und der Notausgang `php update.php`, der ohne
 * Sitzung laeuft. Ein Katalog an zwei Stellen waere die schlimmste Loesung:
 * Die Reihenfolge der Migrationen IST der Mechanismus.
 *
 * ZWEI BAUSTEINE SIND DAZUGEKOMMEN, beide freigegeben (E-S8-10, E-S8-18):
 * `codeblock-lang` — der Wertekasten in kleiner Stufe mit „Kopieren", fuer
 * Werte mit hundert Zeichen statt sechs (Nr. 78) — und `speicher-balken` mit
 * Legende und Schwellenstrich. Kein neues Symbol, keine neue Farbe.
 *
 * KEINE MIGRATION. Alle neuen Werte liegen in `app_state`, das es laengst
 * gibt: `webspace_gb`, `speicher_db_bytes`, `speicher_dateien_bytes`,
 * `speicher_stand`.
 */
/*
 * 15.2.0 ORDNET DIE VERWALTUNG.
 *
 * Drei Seiten, drei Befunde aus der S8-Sichtung:
 *
 * AUS „RECHTSTEXTE" WIRD „INSTALLATION" (E-S8-05, B-S8-10). Impressum,
 * Datenschutz und das Logo beantworten dieselbe Frage — was zeigt diese
 * Anlage Menschen, die noch nicht angemeldet sind? Das Logo lag auf der
 * Wartungsseite und war dort falsch: Der Logo-Standard ist Gestaltung, keine
 * Wartung. Damit ist der letzte Grund fort, warum `update.php` im Browser
 * noch etwas anzeigte; sie ist jetzt eine Weiterleitung auf Betrieb →
 * Updates und bleibt es bis P6 (Nr. 77).
 *
 * AUS „BACKUPS" WERDEN „KONTO-BACKUPS" (E-S8-06, B-S8-08). Das Wort hiess
 * dreierlei: die Pakete der Verwaltung, das `.edbak`, das eine NutzerIn sich
 * selbst herunterlaedt, und der Komplett-Stand der Installation. Der
 * Untertitel der Seite sagt jetzt in einem Satz, welches gemeint ist — und
 * Kennzahl, Filter und Tabellenspalte heissen ueberall gleich. Vorher gab es
 * VIER Namen fuer ZWEI Filter, und wer den einen suchte, fand den anderen
 * nicht.
 *
 * DIE FREIGABE WIRD SICHTBAR (B-S8-09). Sie war ein Zustand ohne Anzeige:
 * ein Paket dieses Kontos stand fuer jemand anderen offen, und zu sehen war
 * das als Plakette an einer Zeile und als Eintrag im Aktionsmenue. Jetzt
 * sagt eine Zustandszeile in der Karte, fuer wen, seit wann, welches Paket
 * und was die andere Seite noch tun muss.
 *
 * ERSATZLOS ENTFALLEN ist die Karte „Abonnement · ab P5" (B-S8-11). Sie
 * stand seit Web 9.9.0 auf jeder Kontoseite und wiederholte eine Zusage, die
 * niemand terminiert hat. R33 steht im Rahmenplan; die Karte entsteht mit
 * ihrem Inhalt.
 *
 * ZWEI BAUSTEINE, beide aus dem freigegebenen Mockup 09: die Logo-Vorschau
 * (Kachel so hoch wie die Kopfleiste, dunkelblau wie sie) und die Kopfaktion
 * als Absendeknopf — „Jetzt sichern" ist ein POST, kein Link, und ein
 * <form> um den Knopf ginge nicht, weil der Kartenkopf schon in einem steht.
 *
 * KEINE MIGRATION. Es aendert sich kein Feld und keine Tabelle.
 */
/*
 * 15.3.0 GIBT DEM BETRIEB SEIN BILD.
 *
 * ZWEI SEITEN, UND SIE BEANTWORTEN ZWEI VERSCHIEDENE FRAGEN.
 *
 * STATUS: „Ist hier etwas zu tun?" Der Befund war die Verstreuung (B-S8-12).
 * Der Serverschluessel meldete sich als rote Karte bei den Backup-Zielen, die
 * Schluesselableitung als rote Karte auf der Wartungsseite, der Speicherstand
 * als Balken unter den Backups, die Job-Fehler als Plakette in einer Liste.
 * Jede fuer sich richtig; zusammen ergaben sie kein Bild. Wer wissen wollte,
 * ob diese Installation in Ordnung ist, musste sechs Seiten aufrufen und auf
 * jeder wissen, worauf zu achten ist.
 *
 * Jetzt: vier Karten, eine Ampelzeile je Sache, eine Meldung oben, die zaehlt.
 * Die Ampel ist eine TABELLE und keine Meinung — blau heisst „in Ordnung",
 * orange „braucht Aufmerksamkeit und arbeitet", rot „arbeitet nicht", neutral
 * „nicht eingerichtet". Keine neuen Toene; neu ist die feste Bedeutung.
 *
 * Die Seite aendert nichts. Die einzige Ausnahme ist der fehlende
 * Serverschluessel: Von der Seite, die das Problem meldet, auf eine andere zu
 * schicken, wo derselbe Knopf steht, waere ein Umweg ohne Zweck.
 *
 * STATISTIK: „Was traegt diese Installation?" Konten nach Rolle, Geraete nach
 * Art, Einsaetze in drei Zeitraeumen, Geraetemodelle als sortierbare Tabelle
 * mit CSV. Durchgaengig OHNE Demo-Konto — sein Bestand ist erfunden und wird
 * alle dreissig Minuten aus der Fixture neu hergestellt; ihn mitzuzaehlen
 * hiesse, 88 erfundene Einsaetze als Nutzung auszugeben.
 *
 * ZWEI OFFENE FRAGEN DES KONZEPTS SIND BEANTWORTET:
 *
 *   Z-01: Eine letzte Mailzustellung wurde NICHT aufgezeichnet.
 *         `smtp_eingerichtet()` prueft die config.php, nicht den Mailserver —
 *         ein falsches Passwort fiel erst auf, wenn jemand einen Setz-Link
 *         erwartete, der nie ankam. `smtp_send()` vermerkt jetzt Zeitpunkt und
 *         Erfolg in `app_state`, gekapselt und ohne Datenbankzwang. Ohne den
 *         Vermerk waere „SMTP-Fehler beim letzten Versand" ein Ampelzustand,
 *         den es nie zu sehen gaebe.
 *
 *   Z-02: Eine Wear-OS-Uhr bekommt NIE eine Geraetezeile. Sie hat weder
 *         Serveradresse noch Schluessel (E-S4-11) und koppelt nicht; gekoppelt
 *         ist das Handy. Die im Mockup vorgesehene Zeile „Wear-OS-Uhren" waere
 *         dauerhaft null gewesen — eine Zeile, die bauartbedingt nie etwas
 *         zaehlt, sagt nicht „null", sondern verschweigt, dass es hier nichts
 *         zu zaehlen gibt. Statt der Zeile steht ein Satz.
 *
 * KEINE MIGRATION. `smtp_last` und `smtp_last_ok` liegen in `app_state`.
 *
 * 15.3.1  ZWEI GEMELDETE FEHLER, beide ausserhalb von S8 aufgefallen.
 *
 *   Die KARTE der Tagesuebersicht nutzte ab 1600 px nur ihren oberen Teil.
 *   Leaflet misst seinen Behaelter einmal beim Anlegen; ab 1600 px waechst
 *   die Karte aber erst, wenn die Einsatztabelle daneben steht, und die
 *   entsteht aus nachgeladenen Daten. Gemessen bei 1920 x 1080: Behaelter
 *   400 x 840 px, Leaflet rechnete mit 400 x 324 px — 516 px ohne Kachel,
 *   und Herauszoomen half nicht, weil auch der Kachelbereich aus der
 *   gemerkten Groesse folgt. Behoben mit einem ResizeObserver in
 *   `attachBaseLayers()`, dem einen Aufruf, den jede Karte macht.
 *   Betroffen waren `index.php` und `zeitraum.php`.
 *
 *   Der TAB-TITEL hiess „<Seite> — Einsatzdoku". Er heisst jetzt
 *   „<Seite> — Gen-EM NAdoku", wie das Programm.
 *
 * KEINE MIGRATION.
 *
 * 15.3.2  DIE WORTMARKE HEISST GEN-EM NADOKU. Die Uhr traegt den Namen seit
 *         Uhr 2.0.0; Web und Handbuch hiessen weiter „Einsatzdoku", und der
 *         Rahmenplan hatte die Umbenennung fuer P7 (Schritt 13) vorgesehen.
 *         Auf Anweisung vom 05.09.2026 vorgezogen, weil ein Programm, das
 *         sich an vier Stellen anders nennt als an fuenf anderen, jeden
 *         dieser Namen schwaecht.
 *
 *         Geaendert: Kopfleiste und Schublade (`ui.php`), Anmeldeseite
 *         (`login.php`), Passwortseiten (`pw_handling.php`), Einrichter
 *         (`install.php`), Absendername der System-E-Mails (Vorgabe in
 *         `install.php` und `config.example.php`), Urheberfeld der GPX-
 *         (`gpx_lib.php`, `assets/export.js`) und CSV-Ausgabe, die
 *         Markierungsdateien von Einrichtung und Wiederherstellung, die
 *         Dateikopf-Kommentare von neun Skripten und die Titel von README,
 *         Handbuch, Technik, Backlog, Changelog und zwei weiteren Dokumenten.
 *
 *         NICHT GEAENDERT: die Langform „Gen-EM Einsatzdokumentation
 *         Notarzt" in den Texten der System-E-Mails (20 Stellen in sechs
 *         Dateien). Sie ist der beschreibende Name des Vorhabens, nicht die
 *         Marke — und sie steht in Betreffzeilen, die Bestandsnutzerinnen in
 *         ihren Postfaechern wiederfinden. Die Entscheidung darueber liegt
 *         bei P7. Ebenfalls unveraendert bleibt diese Datei ab hier
 *         aufwaerts: Sie erzaehlt, was WANN hiess, und waere falsch, wenn man
 *         den alten Namen darin ueberschriebe.
 *
 * KEINE MIGRATION.
 *
 * 15.3.3  DAS PASSWORTFELD DER BACKUP-SEITE HEISST WIE DAS DER ANMELDUNG,
 *         sobald der Schalter „Mein Kontopasswort verwenden" an ist. Ein
 *         Passwortverwalter entscheidet an `name` und `autocomplete`, ob er
 *         ein bekanntes Passwort anbietet oder ein neues vorschlaegt. Das
 *         Feld trug fest `autocomplete="new-password"` und gar kein `name` —
 *         also sah jeder Verwalter ein neues Feld und bot nichts an, auch in
 *         dem Augenblick nicht, in dem das Feld nach dem Kontopasswort
 *         fragte. Jetzt: `name="password"`, und der Schalter setzt
 *         `current-password` bzw. `new-password`. Derselbe `name` steht am
 *         Entsperr-Dialog (`assets/unlock.js`), der ohnehin immer nach dem
 *         Kontopasswort fragt.
 *
 *         NICHT GEPRUEFT werden konnte das Verhalten eines echten
 *         Passwortverwalters — die Pruefumgebung hat keinen. Gemessen ist,
 *         was der Verwalter liest: `name=password autocomplete=current-password`
 *         bei angeschaltetem Schalter, wortgleich mit dem Feld der
 *         Anmeldeseite, und `new-password` im Ausgangszustand.
 *
 * KEINE MIGRATION.
 */

/* ---------------------------------------------------------------------------
 * 15.4.0  MENUE UND LEISTE DES EINSTELLUNGSBEREICHS (S8/AP5)
 *
 * EINE QUELLE FUER DAS MENUE. Die Punkte standen zweimal im Code — einmal
 * fuer die Leiste, einmal fuer die Uebersichtsseite — und waren
 * auseinandergelaufen. `ui_einstellungen_punkte()` ist jetzt die eine Stelle;
 * ein neuer Punkt kostet eine Zeile.
 *
 * DREI BLOECKE, DIE KLAPPEN. Fuer eine BetreiberIn stehen siebzehn Punkte
 * untereinander: gemessen bei 1280 x 900 eine 896 px hohe Liste in einer
 * 783 px hohen Leiste. Offen sind „Einstellungen" und der Block der aktiven
 * Seite; was von Hand umgestellt wird, gilt fuer die Sitzung
 * (`sessionStorage`).
 *
 * ZAEHLER AN VIER PUNKTEN — Status, Updates, Hintergrundjobs,
 * Konto-Backups —, und nur ueber null. Damit die Zahl nicht etwas anderes
 * sagt als die Seite, auf die sie fuehrt, ist die Erhebung der Statusseite
 * nach `status_lib.php` gewandert: eine Erhebung, die Seite zeichnet sie,
 * der Zaehler zaehlt sie. Zwischenspeicher 60 s in `app_state`; warm kostet
 * er 0,46 ms, die volle Erhebung 8,15 ms.
 *
 * UNTERPUNKTE unter dem aktiven Eintrag: die Kartentitel der Seite als
 * Sprungmarken, mit der obersten sichtbaren Karte fett. Sie entstehen im
 * Browser aus den Karten selbst — dafuer haben 27 Karten in sieben Dateien
 * eine `id` bekommen.
 *
 * DIE UEBERSICHT STEHT AM SCHREIBTISCH IN DREI SPALTEN, das Demo-Konto hat
 * statt zweier Erklaerkarten eine, und `.karten-raster` verteilt vier Karten
 * ohne thematische Ordnung selbst auf zwei Spalten (Betrieb -> Updates:
 * 1206 px einspaltig, 977 px zweispaltig).
 *
 * FUENF NEUE ZEICHEN (Mockup 13) und die Wortmarke aus 15.3.2 sind darin
 * schon enthalten.
 *
 * KEINE MIGRATION. Der Zwischenspeicher der Zaehler legt zwei Schluessel in
 * `app_state` an, sobald er zum ersten Mal rechnet.
 */
/* 15.4.1  S8/AP6: die Geraeteseite nach Mockup 10 (Reihenfolge nach
 *          Haeufigkeit, Zeile mit Modell und Datum, alle Handlungen im
 *          Punkte-Menue, „Entkoppeln" statt „Loeschen", Karte „App
 *          installieren" mit zwei Store-Wegen und dem APK als Rueckfall,
 *          „Geraet ohne Code" zugeklappt am Ende) · Wertekasten der kleinen
 *          Stufe an den letzten drei Stellen (Setz-Link, Einladungslink,
 *          Serverschluessel-Zeile) · Filterreihe: das Suchfeld steht in
 *          eigener Zeile, die Filter brechen darunter (Backlog Nr. 73).
 *
 *          KORREKTURNUMMER, nicht Nebennummer: Es kommt keine Funktion
 *          hinzu. Was da war, steht anders — und zwei Store-Adressen sind
 *          als leere Konstanten vorbereitet.
 */
const WEB_VERSION = '15.4.1';
