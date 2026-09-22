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
/* 15.5.0  ZWEI BEDIENHOEHEN (S8/AP7, E-S8-09, R76). 44 px bleibt die
 *          Vorgabe; am Zeigergeraet ab 1024 px sind es 36. Alle drei
 *          Bedingungen muessen gelten — `hover: hover`, `pointer: fine`,
 *          `min-width: 1024px`: Ein Touch-Laptop mit 1920 px ist ein
 *          Fingergeraet, ein iPad im Querformat meldet 1024 px. Eigene Token
 *          bleiben, wie sie sind: Kopfleiste 56, Schalter 46 x 26,
 *          Aktionsblatt 50 (nur mobil), Suchfeld 48, Sprungmarke 28.
 *
 *          Dazu der Schalter aus Backlog Nr. 123: Sein Griff stand am
 *          rechten Kartenrand, gemessen 832 px vom Ende der Beschriftung bei
 *          1440 px und 1072 px bei 1920 px. Jetzt steht er daneben; die
 *          Trefferflaeche bleibt die ganze Zeile.
 *
 *          Und ein gesperrtes Eingabefeld sieht endlich gesperrt aus:
 *          `.feld-eingabe` setzte Flaeche und Schrift selbst und uebermalte
 *          damit die Graufaerbung des Browsers (Fund aus S8/AP1). Es traegt
 *          jetzt die Seitenflaeche, gedaempfte Schrift und
 *          `cursor:not-allowed`.
 *
 *          NEBENNUMMER, weil sich das Verhalten der Oberflaeche aendert —
 *          nicht nur ihr Aussehen: Was am Zeigergeraet 44 px hoch war, ist
 *          jetzt 36. Keine Migration.
 */
/* 15.5.1  DER WARTUNGSBALKEN AUF ALLEN FUENF AUSNAHMESEITEN (S8/AP8).
 *          `betrieb_statistik.php` stand als einzige der fuenf Seiten, die im
 *          Wartungsmodus noch antworten, OHNE den Balken da — ausgerechnet
 *          die, auf der man am laengsten liest. Der Balken ist die einzige
 *          Stelle, an der ein stehengebliebener Wartungsmodus auffaellt
 *          (E-S5W-05); eine Luecke darin ist keine Kleinigkeit. Gefunden
 *          beim Nachrechnen fuer das Handbuch, nicht von einem Pruefmittel —
 *          die Wartungsprobe zaehlte, DASS die Seite antwortet, nicht WAS.
 *          Sie misst es jetzt (43 Erwartungen).
 *
 *          KORREKTURNUMMER: eine fehlende Zeile und ein veralteter
 *          Kopfkommentar. Keine Migration.
 */
/* 15.5.2  ZWEI FUNDE AM PRODUKTIVSTAND (Backlog Nr. 148 und 149). Kein neues
 *          Verhalten, keine neue Funktion — zwei Wege, die es schon gab und
 *          die nicht ankamen. Beide sind vom Auftraggeber gemeldet worden,
 *          keiner von einem Pruefmittel; das ist der eigentliche Befund
 *          dieser Stufe.
 *
 *          Nr. 148: Der Knopf „Diensttage zusammenfuehren" in der
 *          R57-Ueberschneidungswarnung verwies auf `?ziel=`, die Zielseite
 *          liest `d` — HTTP 404 in genau dem Fall, fuer den die Warnung
 *          gebaut ist. Eine Zeile. Dazu `tools/linkprobe/`, das jede Adresse
 *          `<seite>.php?<name>=` gegen die Parameter der Zielseite haelt:
 *          Der Bilderlauf fotografiert Warnungen und klickt keine Knoepfe,
 *          und automatisierte Tests gibt es fuer den Webteil nicht.
 *
 *          Nr. 149: Eine Migration, deren Schema aktuell ist, deren
 *          Registervermerk aber fehlt, zaehlte an Status und Menue als offen
 *          und lag auf der Seite Updates unter „Ausgefuehrt" — zwei
 *          Zaehlweisen, ein Sachverhalt, und der Vermerk war im Web nicht
 *          nachzuholen. Sie bekommt den eigenen Anzeigestatus `skip`, steht
 *          unter „Ausstehend" mit neutraler Plakette „nicht noetig", und der
 *          Knopf ist da. `status_lib.php` und der Menuezaehler bleiben
 *          unberuehrt: Sie lasen immer schon `offen`.
 *
 *          KORREKTURNUMMER: zwei falsche Zuordnungen, kein neuer Weg durch
 *          die Anwendung. Keine Migration — und das ist hier die Pointe: Was
 *          fehlt, ist eine ZEILE IM REGISTER, und die holt der Knopf nach.
 */
/* HIER TREFFEN ZWEI ZWEIGE AUFEINANDER (08.09.2026).
 *
 * Schritt 9a (Sofortpaket Sicherheit) und Schritt 8 (S9) sind nach Web 15.5.2
 * PARALLEL gelaufen — der Beschluss vom 07.09.2026 hat sie ausdruecklich
 * nebeneinander gestellt, weil sie sich in keiner Datei beruehren. Beide
 * haben danach weitergezaehlt, und beide haben 15.6.0 vergeben.
 *
 * 9a ist zuerst auf `main` und damit ausgeliefert; S9 hat deshalb
 * umnummeriert. Was in aelteren Dokumenten dieses Zweiges als 15.6.0, 15.6.1,
 * 15.7.0 oder 15.8.0 stand, heisst jetzt 15.7.0, 15.7.1, 15.8.0 und 15.9.0.
 * Die Erzaehlung darunter steht in der Reihenfolge der Nummern, nicht in der
 * Reihenfolge, in der sie geschrieben wurde.
 *
 * DIE LEHRE, falls wieder zwei Schritte parallel laufen: Eine Nummer ist
 * nicht frei, weil sie im eigenen Zweig frei ist.
 */
/* 15.6.0  SOFORTPAKET SICHERHEIT, WEB-TEIL (Rahmenplan Schritt 9a, R78).
 *          Elf Punkte aus dem Krypto-Review, je einer ein Commit, einzeln
 *          ruecknehmbar. NEBENNUMMER, weil neue Regeln und ein neues
 *          Pruefmittel dazukommen -- keine Migration, kein veraenderter Weg
 *          durch die Anwendung.
 *
 *          NR. 136 RUNDENZAHL UND PASSWORTREGELN. KDF_ITER_ZIEL von 320 000
 *          auf 600 000; gemessen 298 -> 551 ms je Ableitung, im Uebergang
 *          849 ms. Mindestlaenge 10 -> 12, als PW_MIN_LAENGE an einer Stelle.
 *          Dabei zwei Funde, die erst der Sprung sichtbar gemacht hat: Die
 *          stille Anhebung lief NICHT beim naechsten Anmelden -- sie braucht
 *          `CSRF`, und das gab ui_krypto_bootstrap() nur auf Anfrage aus;
 *          die erste Seite ohne CSRF verwarf das Vormerkfach und damit die
 *          Anhebung fuer die ganze Sitzung. Und die Wartungsseite meldete nur
 *          VERWAISTE Rundenzahlen, nicht, wer noch auf dem Altwert steht --
 *          also nicht die Zahl, die sagt, wann der Altwert weg darf.
 *
 *          DIE PASSWORTREGEL HAT EINE NEUE RECHNUNG. SP-2 empfiehlt
 *          Passphrasen und will zugleich die Sperrliste erweitern; beides
 *          zusammen ging nicht, weil jedes Passwort abgewiesen wurde, in dem
 *          irgendwo ein Listenwort vorkam -- "Anker-Winter-Regen-Glas"
 *          scheiterte an "winter". Gemessen wird jetzt der ANTEIL: Was bleibt
 *          uebrig, wenn man Listenwoerter und angehaengte Ziffern streicht?
 *
 *          NR. 127 LOGIN-CSRF. Das Anmeldeformular war das einzige ohne
 *          Token; `csrf_token()`, `csrf_field()` und `csrf_ok()` stehen
 *          deshalb jetzt in `session_lib.php` statt hinter der Anmeldung.
 *          NR. 128 E-MAIL-WECHSEL mit Passwortnachweis, dazu die Hinweismail
 *          an die ALTE Adresse -- auf beiden Wegen, Profil und Verwaltung.
 *          NR. 129 `apk/` und `demo/` per .htaccess gesperrt.
 *          NR. 130 DOCTYPE-SPERRE IM GPX-IMPORT: Ein UTF-16-Dokument ging
 *          durch, weil die Regex Bytes sucht und dort Nullbytes dazwischen
 *          stehen. NR. 131 `wiederherstellen.php` nennt unangemeldet weder
 *          Datenbank-Fehlertext noch Kontenzahl. NR. 133 Der Bauordner des
 *          Komplettbackups mit dem UNVERSCHLUESSELTEN Dump wird nach jedem
 *          Fehlschlag geraeumt, nicht erst beim naechsten faelligen Lauf.
 *
 *          NR. 134 ERSETZFENSTER 72 h ab Einsatzbeginn: Eine gefundene Uhr
 *          kann bestehende Einsaetze danach nicht mehr veraendern -- weder
 *          Phasen ersetzen noch Punkte anhaengen. Neue Einsaetze legt sie
 *          weiter an; der Weg dagegen bleibt das Trennen.
 *          NR. 135 `json_js()` fuer die 44 Stellen, die in einen
 *          `<script>`-Block schreiben; die 35 ausserhalb bleiben unberuehrt,
 *          weil dort Bytes an Pruefsummen haengen.
 *          NR. 138 WEG C, nur Dokumente: Die Zusage nennt jetzt beide
 *          Seiten -- was verschluesselt ist UND dass sich der Einsatzort aus
 *          Spur und Phasenkoordinaten rekonstruieren laesst.
 *          NR. 140 INTEGRITAETSWACHE als taegliche GitHub-Action. Sie
 *          braucht keine eingecheckten Pruefsummen: Der Deploy synchronisiert
 *          byteweise, und der Inline-Block der Anmeldeseite enthaelt keine
 *          einzige PHP-Einsetzung -- beide Seiten lassen sich frisch rechnen.
 *
 *          KEINE MIGRATION. Was Konten betrifft, zieht sich still nach: Die
 *          Rundenzahl beim naechsten Anmelden, alles andere gilt sofort.
 */
/* 15.7.0  EINE VORSCHLAGSLISTE STATT DREIER UND EINER VIERTEN VOM BROWSER
 *          (S9/AP1, E-S9-07 und E-S9-08; Backlog Nr. 68, 102, 106).
 *
 *          Unter einem Feld, das Vorschlaege macht, lag bisher eine von drei
 *          Fassungen — und am Transportziel zwei davon uebereinander: die
 *          eigene Liste des Ortsfelds und die native `<datalist>` des
 *          Browsers, die dieser UEBER dem Feld zeichnet. Auf dem Handy zeigte
 *          die native oft gar nichts; an den Besatzungsfeldern war sie die
 *          einzige Quelle und damit dort blind. Jetzt gibt es
 *          `assets/vorschlagsliste.js` — eine Liste, mit Gruppen
 *          („Zielkliniken" ueber „Adressen"), Symbol und Herkunftszeile je
 *          Eintrag, Pfeiltasten, Enter und Escape.
 *
 *          UND SIE UEBERNIMMT AUF `mousedown`. Das ist Nr. 102: Die alte
 *          Liste der weiteren Rettungsmittel wartete auf `click`, das Feld
 *          versteckte sie 150 ms nach `blur` — wer die Maus laenger haelt,
 *          bekommt kein `click`. Gemessen mit dem neuen `tools/klickprobe/`
 *          bei 300 ms gehaltener Maus: vorher 0 von 3 Uebernahmen, nachher
 *          3 von 3.
 *
 *          NEBENNUMMER, weil sich das VERHALTEN sichtbar aendert — eine neue
 *          Liste, eine neue Bedienung, ein neues Pruefmittel —, aber kein Weg
 *          durch die Anwendung ein anderer wird und keine Spalte sich regt.
 *          Keine Migration.
 */
/* 15.7.1  DIE VORSCHLAGSLISTE LAG HINTER DER SPEICHERN-LEISTE (S9/AP1).
 *          Gemeldet vom Auftraggeber am Bild, nicht von einem Pruefmittel.
 *          Die Liste stand auf `z-index:20` — dem Wert der alten `.rmlist`,
 *          die als einzige der drei Vorgaengerinnen ueberhaupt schwebte. Die
 *          klebende Speichern-Leiste liegt auf 30 und deckte damit genau die
 *          untersten Trefferzeilen zu: gemessen 61 px Ueberlappung, und
 *          `elementFromPoint` traf in der Schnittflaeche die Leiste.
 *
 *          Betroffen ist die Zeile, zu der man scrollt — je weiter unten das
 *          Feld steht, desto mehr Liste liegt darunter. Ein Bild zeigt das
 *          nur, wenn die Scrollposition zufaellig passt; deshalb misst die
 *          Klickprobe es jetzt in beide Richtungen (`elementFromPoint` in der
 *          Schnittflaeche, und die Kopfleiste muss ueber der Liste bleiben).
 *
 *          Ebene jetzt 35: ueber der Speichern-Leiste (30), unter der
 *          Kopfleiste (40) — eine Vorschlagsliste, die ueber die Kopfleiste
 *          malt, verdeckt den Weg aus der Seite heraus.
 *
 *          KORREKTURNUMMER: ein Zahlenwert, kein neues Verhalten. Keine
 *          Migration.
 */
/* 15.8.0  EINE ADRESSE STATT ZWEIER, UND ZWEI SCHALTER DAVOR (S9/AP2).
 *          Die Anschrift des Adressdienstes stand zweimal fest im
 *          Auslieferungsstand — `assets/ortsfeld.js` fuer die Suche,
 *          `assets/ortswahl.js` fuer die Umkehrsuche. Wer sie aendern wollte,
 *          musste den Code aendern; wer sie gar nicht wollte, konnte nichts
 *          tun. Beides ist vorbei: `assets/geocoder.js` ist der eine Weg nach
 *          draussen (`suche`, `umkehr`, `an`), `server/geocoder_lib.php` die
 *          eine Quelle der Einstellungen, und im Auslieferungsstand der
 *          Browserdateien steht der Name des Dienstes nun kein einziges Mal
 *          (`grep -rn komoot server/assets/` = 0).
 *
 *          ZWEI SCHALTER, WEIL ES ZWEI ENTSCHEIDUNGEN SIND. Die BetreiberIn
 *          entscheidet fuer die Installation (Betrieb -> Servereinstellungen,
 *          Karte „Adresssuche", dazu das Feld „Dienst" — wer einen eigenen
 *          Photon betreibt, traegt ihn dort ein, und die Anfragen mit dem
 *          Einsatzort verlassen das eigene Haus nicht mehr). Die NutzerIn
 *          entscheidet fuer ihr Konto (Einstellungen -> Profil, Karte
 *          „Datenschutz"). Die Installation ist die Obergrenze: Ist sie aus,
 *          steht der Kontoschalter ausgegraut da und sagt, wer ihn
 *          abgeschaltet hat. Aus heisst wirklich aus — gemessen mit der
 *          Klickprobe am Netzwerkprotokoll: an 2 Anfragen an den Dienst,
 *          aus 0, bei Tippen, Kartenwahl und Uebernehmen.
 *
 *          DER KARTENDIALOG IST JETZT UEBERALL DERSELBE und kann mehr: ein
 *          Suchfeld im Kopf (ein Treffer setzt das Kreuz und uebernimmt
 *          nichts), die aufgezeichnete Spur des Einsatzes als Linie mit
 *          Start- und Endpunkt, und bei leerem Ortsfeld faehrt die Karte auf
 *          diese Spur. Den Pin-Knopf tragen jetzt fuenf Felder statt zweier:
 *          Einsatzort, manueller Abfahrtort, Transportziel und die
 *          Lagefelder der Standorte in Konto- und Systemverwaltung — Backlog
 *          Nr. 70 („Karte fuer Standorte") war genau das fehlende Drittel.
 *
 *          NEBENNUMMER MIT MIGRATION: `users.adresssuche` kommt hinzu
 *          (`2026_09_07_adresssuche_konto`). Nach dem Deploy muss eine
 *          Administratorin `update.php` aufrufen; bis dahin gilt fuer jedes
 *          Konto die Vorgabe „an", und die Anwendung laeuft weiter — beide
 *          Leser vertragen die fehlende Spalte.
 */
/* 15.9.0  DIE KARTE WIRD LEISER, DIE PFEILE ZEIGEN WIEDER HIN (S9/AP3).
 *          Vier Dinge auf einmal, alle an derselben Karte und denselben
 *          Woertern.
 *
 *          KLEINERE ZEICHEN (E-S9-12, Mockup M-S9-01 V1). Der Farbring war
 *          bisher ein zweiter und dritter Rahmen AUSSERHALB des dunkelblauen
 *          Randes; ein Standort mit Doppelring mass dadurch 60 px und deckte
 *          auf der Handykarte mehr als ein Drittel der Hoehe. Jetzt ERSETZT
 *          der Farbrand den dunkelblauen: nachgemessen 32 px ohne
 *          Aufzeichnung, 32 mit Start oder Ende, 38 mit beidem, Einsatzort
 *          28, Ringpunkt 14 und 20. Aussen liegt am Schild immer 1 px Schnee,
 *          damit Blau nicht auf Gruen stoesst. Token `--geo-schild` 36 -> 30,
 *          `--geo-kreis` 32 -> 28; `geo.js` zieht seine Zahlen nach, wie sein
 *          Kopfkommentar es seit je verlangt.
 *
 *          DIE RICHTUNGSPFEILE DREHTEN SICH NIE (Backlog Nr. 72). `geo.js`
 *          setzte die Laufrichtung als `transform:rotate()` auf ein
 *          Inline-<span> — und dort wirkt `transform` nicht. Alle Pfeile
 *          zeigten nach Norden, und auf Nord-Sued-Abschnitten sah das richtig
 *          aus. Die Winkelrechnung war die ganze Zeit korrekt. Gemessen an
 *          der Bildschirmmatrix des SVG: vorher a=0,833 b=0 c=0 d=0,833 bei
 *          behaupteten 90 Grad, nachher 12 von 12 Pfeilen in 30-Grad-
 *          Schritten auf 0,1 Grad genau. Derselbe Fehler steckte drei Zeilen
 *          darueber im Punkt des Abfahrtorts (Nr. 162, neu aufgenommen): Er
 *          mass 4 x 18 px statt 12 x 12, und die Spurfarbe des Einsatzes lag
 *          in einem 0 px breiten Inhaltskasten.
 *
 *          WINDENKACHELN NACH FAEHIGKEIT (E-S9-04, Nr. 104). Sie erschienen
 *          nur, wenn im Zeitraum tatsaechlich eine Winde geflogen wurde —
 *          damit war „null Windeneinsaetze" von „Winde nicht eingerichtet"
 *          nicht zu unterscheiden. Das eine ist eine Aussage ueber den
 *          Dienst, das andere eine ueber die Stammdaten. `api/range.php`
 *          liefert jetzt `faehigkeiten`, und die Kacheln stehen, sobald ein
 *          Luft-Diensttag die Winde traegt — auch mit dem Wert 0.
 *
 *          „SPUR" HEISST FUER DIE NUTZERIN „GPS-DATEN" (E-S9-03, Nr. 110).
 *          72 sichtbare Zeichenketten in 18 Dateien und 41 Zeilen im
 *          Handbuch. Fachbegriff bleibt er, wo er einer ist: im Code, in
 *          Technik.md, im JSON-Vertrag und im Sicherungsformat. Die Wortliste
 *          bekommt dafuer eine Regel und sechs begruendete Ausnahmen.
 *
 *          DREI NEUE ZEICHEN (E-S9-13): Bergwacht, Veranstaltung, Sonstiges;
 *          Vorrat 49 -> 52. `dt_art_symbol()` nimmt den Diensttag-Typ schon
 *          entgegen — im Datenmodell steht er erst mit AP4.
 *
 *          NEBENNUMMER: neue Funktionen, ein neuer Antwortschluessel, neue
 *          Zeichen, geaenderte Texte — aber kein Weg durch die Anwendung wird
 *          ein anderer und keine Spalte regt sich. Keine Migration.
 *
 * 16.0.0 GIBT DEM RETTUNGSMITTEL EINE ZWEITE ACHSE (S9/AP4, E-S9-09).
 *
 * Bis hierher hatte ein Rettungsmittel genau eine Eigenschaft: `kind`, Luft
 * oder Boden. Daran hingen vier Dinge zugleich — Rollen-Vorlagen,
 * Faehigkeiten, Kachelsatz und Zeichen. Solange es Rettungshubschrauber und
 * Notarzteinsatzfahrzeuge gab, war das richtig. Ein Bergwacht-Dienst, ein
 * Sanitaetsdienst auf einer Veranstaltung und „sonst etwas" passen in keine
 * der beiden Schubladen, ohne eine Aussage mitzuschleppen, die niemand
 * gemeint hat.
 *
 * ZWEI ACHSEN STATT EINER ERWEITERTEN. `kind` bleibt die BETRIEBSART und
 * steuert weiter, was sie steuerte; `typ` ist die ART DES DIENSTES —
 * standard, bergwacht, veranstaltung, sonstiges. Die beiden sind unabhaengig:
 * Eine Bergwacht fliegt oder faehrt, und beides ist ein Bergwacht-Dienst.
 * Haette man `kind` um 'bergwacht' erweitert, muesste jede Stelle, die heute
 * air/ground unterscheidet, kuenftig raten, welche Betriebsart dahintersteckt
 * — und die Phasenbeschriftungen sind seit Web 6.0.0 gerade deshalb neutral
 * (E20/E21), damit die Uhr die Art nicht kennen muss.
 *
 * HAUPTNUMMER AUS DREI GRUENDEN, von denen jeder einzelne reicht: Das
 * Datenmodell aendert sich mit einer Migration (`vehicles.typ`,
 * `vehicles.kurz`, `days.vehicle_typ`, `days.vehicle_kurz`), das Dateiformat
 * der Sicherung wechselt (Nutzlast 9 -> 10, wie 11.0.0), und eine feste
 * Zusage wird eingeschraenkt: „Jedes Rettungsmittel gehoert zu genau einem
 * Standort" (E15) gilt nur noch fuer den Typ 'standard'. `vehicles.base_id`
 * ist wieder NULL-faehig — diesmal dauerhaft und mit Absicht.
 *
 * DER KURZNAME (Backlog Nr. 69) ist die kleinere Haelfte und die
 * sichtbarere: bis 16 Zeichen, freiwillig. Die DIENSTTAGE-LEISTE zeigt ihn
 * STATT der Bezeichnung, wo der Platz knapp ist; Formulare, Export und
 * Sicherung zeigen weiter den vollen Namen — und fuehren den Kurznamen
 * DANEBEN, damit er den Rueckweg uebersteht. Der Konzepttext nannte
 * daneben Kacheln und Plaketten; gebaut wurde das nie, weil beide gar kein
 * Rettungsmittel nennen und es dort nichts zu ersetzen gab (entschieden am
 * 08.09.2026, siehe 16.1.0).
 *
 * WAS DABEI BEINAHE STILL KAPUTTGEGANGEN WAERE, und deshalb steht es hier:
 * `nb_moeglich()` (nachbearbeitung_lib.php) entschied ALLEIN an der
 * Nullbarkeit von `vehicles.base_id`, ob die einmalige Nachbearbeitung aus
 * A12 ueberhaupt existiert. Mit dieser Migration waere sie in JEDER
 * Installation wiederauferstanden, haette die rechtmaessig standortlosen
 * Rettungsmittel als offene Punkte gemeldet — und ihr Knopf haette die
 * Migration mit einem `ALTER TABLE ... NOT NULL` zurueckgenommen. Gemessen
 * am 07.09.2026: nb_moeglich() false -> true, zwei falsche offene Punkte.
 * Die zweite Stufe kennt jetzt vier Tabellen statt fuenf; `vehicles` gehoert
 * nicht mehr dazu.
 *
 * ZWEI WEITERE STILLE STELLEN sind mitgegangen: `dt_vehicle_erlaubt()` liess
 * ein zentrales Rettungsmittel ohne Standort nicht durch, waehrend
 * `dt_vehicles()` es anbot — die Auswahl waere beim Speichern wortlos auf
 * NULL zurueckgefallen. Und beide Stammdatenseiten laden mit
 * `base_id IN (...)`; ein Rettungsmittel ohne Standort waere dort unsichtbar
 * und damit weder zu aendern noch zu loeschen gewesen. Es bekommt bis AP5
 * eine eigene Karte „Ohne Standort".
 *
 * DAS ZEICHEN FUER VERANSTALTUNG IST GETAUSCHT: Tabler „ticket" statt
 * „building-stadium". Gemessen: „ticket" haelt seine Binnenflaeche von 96 px
 * bis 16 px unveraendert, „building-stadium" verliert bei 18 px zwei seiner
 * vier auf einen einzelnen Pixel. Der Vorrat bleibt bei 52 Dateien.
 *
 * 16.1.0 RAEUMT ZWEI FREIGABEN NACH (S9/AP4a, 08.09.2026).
 *
 * Zwei Fragen waren nach AP4 offen geblieben, weil ihre Antwort eine
 * Gestaltungsentscheidung ist; die Mockups M-S9-08 und M-S9-09 haben sie
 * gestellt, der Auftraggeber hat sie beantwortet. Nichts davon ruehrt an
 * Datenmodell oder Format: keine Migration, keine Spalte, kein Schluessel.
 *
 * DER KURZNAME IM SCHMALEN BAND. Die Begruendung des Kurznamens war „die
 * schmalste Stelle der Anwendung" — und genau dort stand er nicht: Zwischen
 * 1024 und 1199 px ist die Leiste 220 px breit, und `.eintrag-neben` war
 * dort ausgeblendet. Jetzt bleibt er sichtbar, wenn einer gesetzt ist; der
 * volle Name bleibt fort. Das Akkordeon rueckt in diesem Band je Ebene 8
 * statt 12 px ein — die Einrueckung wirkt zweimal (Jahr und Monat), macht
 * also 8 px mehr Platz fuer den Eintrag.
 *
 * WIE VIEL VOM KURZNAMEN ANKOMMT, HAENGT AM DATUM DANEBEN, und das ist beim
 * Bauen zuerst uebersehen worden. `.eintrag-text` schrumpft nicht, und seine
 * Breite schwankt: Bricolage Grotesque setzt Ziffern PROPORTIONAL, und die
 * Regel `tabular-nums` nennt diesen `<span>` nicht. Gemessen an den dreizehn
 * Datumsangaben des Pruefbestands: Datum 76 bis 83 px, fuer den Nebentext
 * bleiben 48 bis 55 px. „BW Hoch" braucht 55 — VIER Datumsangaben tragen ihn
 * ganz, NEUN mit Auslassungszeichen. Ohne die Einrueckung waere es keine
 * einzige (40 bis 47 px). Die erste Messung stand auf 57 px und war an einer
 * einzigen, zufaellig schmalen Datumsangabe genommen; gefunden hat es die
 * adversarische Gegenprobe. „BW Ho..." sagt mehr als der leere Platz von
 * vorher, und der Tooltip nennt weiterhin alles — ob das genuegt oder das
 * Datum im schmalen Band kuerzer werden soll, entscheidet der Auftraggeber.
 *
 * DABEI KAM HERAUS, DASS DIE ALTE BEGRUENDUNG AN DREI STELLEN FALSCH STAND:
 * „Unter 1200 px entfaellt der Name ganz" (style.css, ui.php, Handbuch).
 * Die Regel steht im Block `@media (min-width:1024px)`, die Grundregel
 * setzt gar kein `display` — unter 1024 px, also in der Schublade, stand der
 * Name immer, und er steht dort weiter. Es sind DREI Zustaende, nicht zwei;
 * die Saetze sind berichtigt.
 *
 * DER TYP BEIM ZUSAMMENFUEHREN. Zwei Diensttage verschiedenen Typs lassen
 * sich zusammenfuehren — das bleibt so, `dt_merge_pruefen()` ist
 * unveraendert und prueft weiter allein die Betriebsart. Aber die Vorschau
 * verschwieg, was danach gilt: Der Typ folgt dem gewinnenden Rettungsmittel,
 * und wer das andere waehlt, aendert ihn. Jetzt nennt „Der Diensttag danach"
 * ihn in einer eigenen Zeile, und die beiden Wahlzeilen tragen Typ und
 * Kurznamen im Zusatz.
 *
 * DIE GEWINNERREGEL STEHT DESHALB JETZT AN EINER STELLE STATT AN ZWEIEN.
 * Sie lag in `dt_zusammenfuehren()`, also innerhalb der Transaktion; die
 * Vorschau haette sie nachbauen muessen. `dt_merge_rm_gewinner()` ist die
 * eine Fassung, die beide befragen — genau die Falle, vor der der Kommentar
 * ueber dem UPDATE warnt: Der Zieltag truege sonst den NAMEN des einen und
 * den TYP des anderen Rettungsmittels.
 *
 * 16.1.1 GIBT DEM KURZNAMEN DEN PLATZ, DEN ER BRAUCHT (S9, Freigabe M-S9-11).
 *
 * Die Frage aus 16.1.0 ist beantwortet. Das Mockup M-S9-11 hat drei Wege an
 * der laufenden Anwendung gemessen, und der Auftraggeber hat den zweiten
 * gewaehlt: Im Band 1024-1199 px rueckt das Akkordeon je Ebene 4 statt 12 px
 * ein, und der Abstand in der Zeile geht von 8 auf 4 px. Beides wirkt
 * zweimal — zwei Akkordeonebenen, zwei Zwischenraeume —, macht also 16 px.
 *
 * GEMESSEN AN DEN DREIZEHN DATUMSANGABEN DES PRUEFBESTANDS: Dem Nebentext
 * standen 48 bis 63 px zur Verfuegung, jetzt 64 bis 79. „BW Hoch" braucht 55
 * und „NEF 76/1" 53 — die stehen damit an JEDEM Datum ganz statt an dreien
 * von zwoelf; „RTH Murnau" (76 px) an den meisten. Vorher: 13 Kurznamen,
 * 10 mit Auslassungszeichen. Nachher: 13 Kurznamen, KEINES.
 *
 * DER ABSTAND IST GESCOPED, DIE EINRUECKUNG NICHT. `.eintrag` tragen auch
 * der Leistenfuss, die Hauptpunkte der Schublade und jede Zeile des
 * Einstellungsmenues; sie haben keinen Nebentext und damit kein
 * Platzproblem. Eine ungescopte `gap`-Regel haette sie ohne Not
 * zusammengerueckt — deshalb `.akkordeon:not(.leiste-gruppe) .eintrag`. Die
 * Einrueckung braucht das nicht: Dort schirmt eine vorhandene, spezifischere
 * Regel das Menue schon ab. Nachgemessen bei 1100 und 1280 px: Menue 0 px
 * Einrueckung und 8 px Abstand, Leistenfuss 8 px — in jeder Breite
 * unveraendert.
 *
 * WEG 3 IST VERWORFEN (08.09.2026): Im schmalen Band das JAHR aus dem Datum
 * zu nehmen — es steht als Akkordeon-Ueberschrift darueber — haette rund
 * 30 px gebracht und auch den laengsten erlaubten Kurznamen getragen. Es
 * aendert aber, was in einer Zeile STEHT, und zwar abhaengig von der
 * Fensterbreite; und nach einem Sprung aus der Suche ist die Jahreszeile
 * weggerollt, dann fehlt das Jahr ganz. Das Datum behaelt es in jeder
 * Breite.
 *
 * 16.2.0 MACHT AUS ZWEI REITERN EINE LISTE UND VIELE SEITEN (S9/AP5-1, PS-12).
 *
 * Seit Web 7.0.0 standen „Standorte" und „Rettungsmittel" nebeneinander im
 * Menue, geschnitten nach Taetigkeit. Der Schnitt hat sich nicht bewaehrt:
 * Beide Reiter luden DENSELBEN Bestand, und wer einen Standort einrichtete,
 * ging zwischen ihnen hin und her — anlegen dort, ausstatten hier. Jetzt
 * fuehrt „Standorte" auf die Liste, und die Liste auf je EINE Seite
 * (`t=standort&s=<id>`), die alles traegt, was an diesem Standort haengt.
 *
 * DIE ZEILE IST DER VERWEIS, nicht mehr der Name darin. Damit fallen die
 * Zeilenaktionen weg — ein Knopf in einem Link ist kein gueltiges Markup —,
 * und „Loeschen" und „Als Vorbelegung" stehen im Aktionsmenue der
 * Standortseite. Das ist zugleich der bessere Ort: Wer einen Standort
 * loescht, hat vorher gesehen, was daran haengt. Die Kleinzeile nennt statt
 * der Lage die DREI ZAHLEN (Rettungsmittel, Besatzung, Zielkliniken); die
 * Lage steht auf der Seite selbst.
 *
 * ZWEI WEICHEN STATT ZWEIER TOTER LINKS. `t=rettungsmittel` fuehrt auf die
 * Liste, wie `t=stammdaten` es seit Web 7.0.0 tut — der Name steht in
 * Lesezeichen und in aelterer Dokumentation. Und `t=standort` ohne gueltige
 * Kennung fuehrt ebenfalls dorthin: geprueft mit `dt_base_erlaubt()` GANZ
 * OBEN, vor der ersten Zeile Ausgabe. Weiter unten haette `header()` still
 * versagt und die Seite stuende halb da; genau das ist beim Bauen passiert.
 *
 * NEBENNUMMER, NOCH KEINE HAUPTNUMMER: Der Weg durch die Anwendung aendert
 * sich spuerbar, aber AP5 ist erst zum Teil gebaut — die Standortseite traegt
 * bis auf Weiteres die alten Bloecke des Reiters „Rettungsmittel". Wenn sie
 * ihre Gestalt aus M-S9-06 hat und die Verwaltung nachgezogen ist, steigt die
 * Hauptnummer.
 *
 * 16.2.1 REPARIERT ZWEI STELLEN, DIE 16.2.0 UEBERSEHEN HAT — beide gefunden
 * von der Codelesung, nicht im Browser, weil beide OHNE Fehlermeldung
 * ausfallen:
 *   - Das Leaflet-Stylesheet haengt an einer Reiterliste, in der noch
 *     „rettungsmittel" stand und „standort" fehlte. Der Pin am Nur-Lage-
 *     Ortsfeld der Standortseite haette eine unformatierte Karte gezeigt.
 *   - Das Geruest bekam `menue => $tab`, und `standort` ist kein
 *     Menueschluessel. Die Leiste haette KEINEN aktiven Eintrag gehabt — und
 *     `menue.js` haengt seine Unterpunkte genau daran, was in AP5 Teil 2
 *     gebraucht wird. Jetzt meldet die Seite „standorte" als aktiv.
 * Die Lehre steht hier, weil sie wiederkommt: Wer einen Reiter umbenennt,
 * greppt nach seinem Namen — er steht nicht nur in der Weissliste.
 *
 * 16.2.2 GIBT DER STANDORTSEITE IHRE GLIEDERUNG UND DREI WEGE ZURUECK
 * (S9/AP5-2). Sechs Karten mit Kennung, drei Kennzahlen als
 * Inhaltsverzeichnis und „Zum Anfang" am Ende jeder Karte standen mit 16.2.0
 * schon da; was fehlte, waren die Wege dazwischen. Drei davon fuehrten ins
 * Leere, und keiner hat sich beschwert:
 *
 *   - `ui_nach_oben()` sprang auf `#seitenanfang`. Diese Kennung gibt es in
 *     der ganzen Anwendung nicht; ein Verweis auf ein fehlendes Ziel erzeugt
 *     weder Fehler noch Meldung, er tut nur nichts. Ziel ist jetzt `#inhalt`
 *     — die Kennung, die das Geruest ohnehin an das `<main>` haengt.
 *   - Nach jedem Speichern und jedem Loeschen ging die Umleitung auf
 *     `t=rettungsmittel`. Diesen Reiter gibt es seit 16.2.0 nicht mehr; die
 *     Weiche am Seitenkopf warf damit JEDE Aenderung an einem
 *     Rettungsmittel, einer Rolle, einer Zielklinik oder einer Bereitschaft
 *     auf die Standortliste — mit einem Anker, der dort nichts findet. Wer
 *     zehn Zielkliniken eintraegt, klickte zehnmal zurueck. Das Ziel ist
 *     jetzt die Seite des Standorts, an dem die Sache haengt, und es steht
 *     als ganze Adresse (`sd_seite()`) statt als Reitername.
 *   - Dieselbe tote Adresse stand als Vorgabewert in `sd_zeile()` und
 *     `sd_form()` und in acht Aufrufen, die sie nicht ueberschrieben.
 *
 * UND „OHNE STANDORT" STEHT JETZT AUF DER LISTE. Bergwacht, Veranstaltung
 * und Sonstiges brauchen keinen Standort (16.0.0); ihre Karte hing bis
 * hierher unter der letzten Standortkarte und erschien damit auf JEDER
 * Standortseite — sichtbar als siebter Unterpunkt in der Leiste, wo die
 * Seite sechs Karten hat. Sie gehoert dorthin, wo die Standorte stehen und
 * keiner von ihnen gemeint ist. Bearbeitet wird ein solcher Eintrag
 * weiterhin im Formular des ersten Standorts; das loest erst der Dialog aus
 * AP5-4 auf.
 *
 * KORREKTURSTUFE UND KEINE NEBENNUMMER: Es kommt keine Funktion hinzu. Es
 * geht nur das, was 16.2.0 halb fertig hinterlassen hat.
 *
 * 16.3.0 GIBT DEN LANGEN LISTEN ZWEI HILFSMITTEL (S9/AP5-3, M-S9-05/-06).
 *
 * Eine Standortseite mit zwoelf Rettungsmitteln, zwei Dutzend
 * Besatzungseintraegen und drei Dutzend Zielkliniken ist mehrere Bildschirme
 * lang, und bis hierher half dagegen nichts. Jetzt bekommt jede Liste ab
 * SECHS Eintraegen ein Hilfsmittel — eine Zahl, eine Regel, keine
 * Sonderfaelle je Karte (`SD_HILFE_AB`, stammdaten_ui.php):
 *
 *   - Die RETTUNGSMITTEL bekommen die SPRUNGLISTE: eine umbrechende Zeile
 *     runder Pillen, jede mit dem Artzeichen ihres Rettungsmittels. Sie
 *     bekommen sie als einzige, weil ihre Eintraege ein Zeichen tragen, an
 *     dem man sie in einer Pillenreihe wiedererkennt. Eine Reihe aus zwoelf
 *     Namen ohne Zeichen waere keine Orientierung, sondern dieselbe Liste
 *     ein zweites Mal.
 *   - ALLE UEBRIGEN bekommen den KARTENFILTER: ein Feld mit Lupe, das im
 *     Browser ausblendet, was nicht passt. Konzept und Mockup nennen nur
 *     Besatzung und Zielkliniken; „Weitere Rettungsmittel" und „Bergwacht"
 *     sind dieselbe Listenform mit demselben Problem, und zwei Sorten Liste
 *     auf einer Seite waeren schwerer zu erklaeren als eine Regel.
 *
 * DIE ANGESPRUNGENE ZEILE FAERBT SICH — ueber `:target`, ohne Skript. Das
 * ist der erste `:target` dieser Anwendung; er ueberlebt den Ruecksprung aus
 * dem Verlauf, was die von Hand gesetzte `.zeile-hervor` nicht tut. Dafuer
 * tragen die Zeilen der fuenf Listen jetzt Kennungen (`veh-7`, `crew-12`,
 * `td-3`, `res-9`, `bw-2`) — dieselben Vorsaetze, die ihre verborgenen
 * Formulare seit jeher tragen. Das Konzept schrieb `#dest-<id>` fuer die
 * Zielkliniken; die heissen an jeder anderen Stelle `td`, und ein zweiter
 * Name fuer dieselbe Sache im selben Modul ist kein Gewinn — berichtigt ist
 * das Konzept.
 *
 * DAS ARTZEICHEN STEHT JETZT WIRKLICH IN DER ZEILE (F-S9-K-04). Der
 * Kommentar daneben behauptete es seit Web 7.0.0 („Das Symbol vor dem Namen
 * sagt die Art"), und `dt_art_symbol()` wurde dafuer sogar berechnet —
 * benutzt hat das Ergebnis niemand. Die Zeile zeigte Namen und Rollen und
 * sonst nichts. Jetzt steht es links, mit TYP: sonst zeigte eine Bergwacht
 * dasselbe Zeichen wie ein NEF.
 *
 * DREI DINGE, DIE DABEI GERADEGEZOGEN WURDEN:
 *
 *   - DER DOPPELTE TITEL IST WEG. Jede Karte trug ihren Namen und ihre Zahl
 *     zweimal — einmal im Kartenkopf, einmal als `h3.sd-titel` unmittelbar
 *     darunter. Auf dem Bild von 16.2.2 steht „Rettungsmittel 3" zweimal
 *     untereinander. Die Rollen der Besatzung sind dafuer von `h4` auf `h3`
 *     gerueckt, sonst klaffte zwischen dem `h2` der Karte und ihnen eine
 *     Ebene. In der VERWALTUNG bleibt beides, wie es ist — sie wird mit
 *     AP5-4 umgestellt.
 *   - DIE KARTEN TRAGEN DEN VORSATZ `k-`. `Design.md` 9.25 schreibt ihn fuer
 *     jede Karte vor, die Sprungziel sein soll, und der uebrige Bestand
 *     haelt sich an dreissig Stellen daran. Die Mockups zeichnen
 *     `#standort`; ein Bild ist aber keine Namensregel, und eine Regel, die
 *     man fuer die sechs neuesten Karten aufweicht, ist ab dann keine.
 *   - DER BILDERLAUF MISST DIE PILLE MIT. `.sprungziel` ist `--knopf` hoch,
 *     traegt aber nicht `.knopf` — und die Auswahl des Bilderlaufs kannte
 *     nur `.knopf`. Ohne diese Zeile stuende in `Design.md` eine
 *     44/36-Zusage, die kein Pruefmittel deckt. Genau so ist `.listenfilter`
 *     seit O6 ungemessen geblieben.
 *
 * NEBENNUMMER: Es kommen Funktionen hinzu, kein Datenmodell und keine
 * Migration. `update.php` muss NICHT laufen.
 *
 * ---------------------------------------------------------------------------
 * 17.0.0 — S9/AP5-4: Anlegen und Bearbeiten im Dialog; die Verwaltung
 * bekommt dieselben Standortseiten (E-S9-19, M-S9-07).
 *
 * HAUPTNUMMER OHNE MIGRATION, wie schon bei 7.0.0 und 16.2.0: Das Datenmodell
 * ist unangetastet — `update.php` muss NICHT laufen —, aber die Wege durch die
 * Anwendung sind andere. Wer sie kennt, findet zwei Dinge an neuer Stelle:
 *
 *   1. DIE EINGABE STEHT NICHT MEHR UNTER DER LISTE. „Anlegen" ist ein Knopf
 *      im Kartenkopf, „Bearbeiten" ein Eintrag im Zeilenmenue; beide oeffnen
 *      denselben Dialog. Die zehn Formulare der Standortseite (eines je Liste,
 *      und in der Besatzung eines je ROLLE) sind ersatzlos entfallen, mit
 *      ihnen `sd_form()` und die fuenf GET-Parameter `ev`, `ec`, `et`, `er`,
 *      `ew`.
 *   2. VERWALTUNG → STAMMDATEN hat keine zwei Reiter mehr. „Standorte
 *      systemweit" und „Rettungsmittel systemweit" sind zu einer Liste und je
 *      einer Standortseite geworden — dieselbe Gliederung wie im Konto seit
 *      16.2.0, dieselben sechs Karten, dieselben Kennzahlen, dieselben fuenf
 *      Dialoge aus derselben Datei. `t=rettungsmittel` bleibt als Weiche.
 *
 * WARUM UEBERHAUPT. Drei Dinge waren am Formular unter der Liste falsch, und
 * alle drei sind mit den Standortseiten schlimmer geworden: Wer den zwoelften
 * Eintrag anlegen wollte, rollte an elf vorbei; „Bearbeiten" lud die Seite neu
 * und aenderte still die Werte eines Formulars weiter unten (auf einem Handy
 * sah man davon nichts); und in der Besatzungskarte stand dasselbe Formular
 * fuenfmal, je Rolle einmal — der Kartenfilter musste sie eigens verbergen.
 *
 * FUENF DIALOGE AUS DREI FUNKTIONEN. E-S9-19 nennt drei ARTEN — Rettungsmittel,
 * Besatzungsmitglied, Zielklinik — und M-S9-07 zeichnet sie. „Weitere
 * Rettungsmittel" und „Bergwacht" zeichnet das Mockup nicht; sie haben genau
 * ein Feld, dasselbe wie das Besatzungsmitglied ohne die Rolle. Deshalb wird
 * `sd_dialog_eintrag()` dreimal aufgerufen. Der Gegenentwurf — EIN Dialog, der
 * seine Beschriftungen vom Oeffner holt — ist erwogen und verworfen: Die
 * Feldbeschriftung steht im `<label>` neben dem Pflichtstern, `data-fuell`
 * setzt `textContent` und wuerfe ihn weg; und ein Text, der erst im Browser
 * entsteht, laeuft an der Wortliste vorbei.
 *
 * DER STANDORT IST EIN FELD GEWORDEN, kein Haken. „Ohne Standort" war bis
 * hierher ein Kaestchen, das die verborgene Kennung der Standortkarte schlug:
 * Man sah beim Setzen nicht, WAS man ueberschrieb, und ein Rettungsmittel von
 * einem Standort auf einen anderen zu verschieben ging gar nicht. Jetzt ist es
 * der erste Eintrag einer Auswahl (Wert 0), und die Auswahl erscheint nur bei
 * den drei Typen ohne Standortpflicht — beim Typ Standard steht darunter, zu
 * welchem Standort der Dialog gehoert. Damit faellt auch der Kunstgriff, mit
 * dem ein standortloses Rettungsmittel auf der Seite des ERSTEN Standorts
 * bearbeitet wurde.
 *
 * FEHLER BLEIBEN IM DIALOG (E-S9-19). Bis hierher ging jeder Fehler denselben
 * Weg wie jede Meldung: in die Sitzung, Umleitung, Kasten am Seitenkopf. Fuer
 * ein Formular unter der Liste war das richtig — es stand danach wieder da,
 * mit seinen Werten. Ein Dialog steht nach dem Neuladen nicht wieder da: Er
 * waere zu, die Eingabe waere weg, und oben stuende „Bezeichnung fehlt" ueber
 * einer Liste, in der man gerade nichts eingegeben hat. Der Fehlerweg leitet
 * deshalb NICHT um; die Seite ist die Antwort auf den POST, und das
 * Seitenskript oeffnet den Dialog wieder (`window.edDialog.auf`).
 *
 * DER ERFOLGSFALL HAT DAFUER KEINE MELDUNG MEHR. Die Umleitung fuehrt auf die
 * geschriebene ZEILE (`#veh-7`), `:target` faerbt sie — das ist die
 * Bestaetigung. Und „Standort anlegen" landet auf der neuen Standortseite:
 * Sie ist zugleich der Ort, an dem als Naechstes etwas zu tun ist.
 *
 * FUENF FUNDE, DIE DABEI HERAUSKAMEN, und keiner davon war ein Bild wert:
 *
 *   - BACKLOG NR. 163: `crew_save` in der Verwaltung las `role`, das Formular
 *     schickte `role_code`. Seit Web 9.10.0 meldete die Anwendung „Bitte Rolle
 *     und Namen angeben." — bei ausgefuellter Rolle und ausgefuelltem Namen.
 *     Eine systemweite Besatzungs-Vorbelegung liess sich zwei Jahre lang weder
 *     anlegen noch aendern. Kein Bild zeigt eine Meldung, die erst nach einem
 *     Klick erscheint; die Klickprobe fuhr diesen Weg bis heute nicht.
 *   - F-S9-U-27: `.dialog{display:flex}` schlug die Browservorgabe
 *     `dialog:not([open]){display:none}` — eine UA-Regel verliert gegen JEDE
 *     Autorenregel. Alle fuenf Dialoge standen als Kaesten am Seitenende, mit
 *     Feldern, die man ausfuellen kann, und Knoepfen, die absenden. Sichtbar
 *     nur auf einem Vollseitenbild. `display` gehoert an `[open]`.
 *   - F-S9-U-28: `base_save` prueft `if ($n !== '')` — ohne `else`. Ein leerer
 *     Standortname wurde wortlos verworfen.
 *   - Ein UPDATE auf einen vorhandenen Namen lief in eine ungefangene
 *     PDOException (weisse Seite) — bei Besatzung, weiteren Rettungsmitteln,
 *     Bergwacht und Zielkliniken. Mit der Rolle als Feld wurde daraus ein
 *     wahrscheinlicher Fall statt eines seltenen.
 *   - `crew_save` schrieb die ROLLE nicht mit. Das war richtig, solange sie
 *     eine verborgene Kennung war; als Feld haette eine Rollenaenderung
 *     wortlos nichts getan.
 *
 * DER DIALOG ROLLT JETZT IN SICH. `.dialog` hatte keine Hoehenangabe — das
 * ging, solange jeder Dialog aus zwei Saetzen und zwei Knoepfen bestand. Der
 * Rettungsmittel-Dialog hat vier Felder, fuenf Rollenhaken und zwei
 * Faehigkeitshaken und ist am Handy hoeher als das Glas; die Browservorgabe
 * kappte unten ab, und unten steht der Fuss mit „Anlegen". Kopf und Fuss
 * stehen jetzt fest, der Inhalt rollt. Das trifft auch den GPX-Dialog, der
 * dasselbe Problem hatte, ohne dass es jemand gemeldet haette.
 *
 * HAUPTNUMMER: Die Wege sind andere. `update.php` muss NICHT laufen.
 *
 * ---------------------------------------------------------------------------
 * 17.1.0 — S9/AP5-5: Ein Standort loeschen kostet nicht mehr, was ohne ihn
 * bestehen darf (M-S9-10, Variante b).
 *
 * `vehicles_ibfk_2` steht auf ON DELETE CASCADE, und das ist E15 woertlich:
 * Was an einem Standort haengt, geht mit ihm. Seit E-S9-09 nahm es dabei aber
 * auch das mit, was ohne diesen Standort bestehen DUERFTE — Bergwacht,
 * Veranstaltung, Sonstiges. Ein Sanitaetsdienst, der nie zu einem Standort
 * gehoerte und nur zufaellig einem zugeordnet war, verschwand mit dem
 * Standort; die Rueckfrage sagte „6 Stammdatensaetze werden mitgeloescht" und
 * verschwieg, dass eines davon nicht haette mitgehen muessen.
 *
 * AP4 hatte das bewusst stehen lassen und begruendet: `ON DELETE SET NULL`
 * waere die falsche Antwort, weil sie JEDES Standard-Rettungsmittel
 * standortlos machte — einen Datensatz, den die Pruefschicht nie anlegen
 * wuerde. Die Antwort ist deshalb Anwendungslogik VOR dem `DELETE`: ein
 * `UPDATE`, das den drei Typen ohne Standortpflicht den Standort abnimmt, in
 * derselben Transaktion. Der Fremdschluessel bleibt, wie er ist.
 *
 * DREI FUNKTIONEN IN `db.php`, neben `stammdaten_dup_global()`:
 * `stammdaten_ohne_standortpflicht()` (wer ueberlebt, mit Namen),
 * `stammdaten_standort_loesen()` (das UPDATE) und `stammdaten_loeschfrage()`
 * (der Satz). Zwei Aufrufstellen, eine Fassung — und die REGEL steht in
 * keiner davon, sondern in `VEHICLE_TYPEN[...]['standort']`: derselben
 * Angabe, aus der `pruef_rettungsmittel()` entscheidet, ob ein Typ ohne
 * Standort angelegt werden darf. Zwei Fassungen liefen beim naechsten Typ
 * auseinander, und zwar still.
 *
 * NICHT in `validate_lib.php`, obwohl das Konzept „neben
 * `pruef_rettungsmittel()`" schreibt: Der Kopf jener Datei sagt „Diese Datei
 * aendert von sich aus nichts", und ein `UPDATE` darin waere der erste
 * Verstoss dagegen. Gemeint war „eine Fassung fuer beide Seiten" — und die
 * ist es.
 *
 * DIE RUECKFRAGE TRENNT DIE ZAHL und nennt das Ueberlebende mit NAMEN: „5
 * werden mitgeloescht. 1 Rettungsmittel ohne Standortpflicht — Bergwacht
 * Hochkreuth — bleibt bestehen und steht danach unter ,Ohne Standort'." Ein
 * Rettungsmittel, das einen Standort verlaesst, ist eine Nachricht und keine
 * Statistik (M-S9-10, Anmerkung 3). Ab vier Namen nur noch drei und „und N
 * weitere" — sonst waere die Aufzaehlung laenger als der uebrige Text.
 *
 * DIE UMLEITUNG ZEIGT AUF DAS UEBERLEBENDE, nicht auf die Standortliste: Wer
 * die Rueckfrage schnell wegklickt, findet den Eintrag sonst spaeter unter
 * „Ohne Standort" und fragt sich, woher er kommt. Die Karte ist zugeklappt;
 * das Ankerskript oeffnet sie.
 *
 * EIN FUND BEIM BAUEN, von der Klickprobe: Der erste Entwurf leitete die
 * deutsche Adjektivendung aus der Zeichenkette ab (`rtrim('eigene','e').'r'`)
 * und schrieb „Ein eigenr Stammdatensatz". Vier Formen stehen jetzt
 * ausgeschrieben.
 *
 * WAS DIESE FASSUNG NICHT TUT: Ein Rettungsmittel, das einer NutzerIn gehoert
 * und an einem SYSTEMWEITEN Standort haengt, geht weiterhin mit, wenn die
 * Verwaltung diesen Standort loescht — auch wenn sein Typ keinen Standort
 * braucht. Das ist Bestandsverhalten; die Rueckfrage der Verwaltung zaehlt
 * seit jeher nur den systemweiten Bestand. Vermerkt im Pruefdokument.
 *
 * NEBENNUMMER: veraendertes Verhalten beim Loeschen, kein Datenmodell, keine
 * Migration. `update.php` muss NICHT laufen.
 *
 * ---------------------------------------------------------------------------
 * 17.1.1 — S9/AP5-6: der Prueflauf, und was er gefunden hat.
 *
 * DER LEERZUSTAND DES KARTENFILTERS SAGTE ETWAS FALSCHES. „Kein Eintrag passt
 * dazu. Leere den Filter, um etwas anzulegen." — richtig, solange die
 * Anlegen-Formulare in der Liste standen und beim Filtern mitverschwanden.
 * Seit 17.0.0 steht „Anlegen" im KARTENKOPF, also ausserhalb der gefilterten
 * Liste; es ist auch bei null Treffern da. Der Satz beschrieb eine Sackgasse,
 * die es nicht mehr gibt, und sagt jetzt, was der Filter tatsaechlich
 * verdeckt: alles Uebrige.
 *
 * ZWEI PRUEFMITTEL HABEN DABEI SELBST ETWAS GELERNT, und beide Male ist es
 * dieselbe Sorte Fehler — ein Werkzeug, das das Richtige misst und den
 * falschen Grund nennt:
 *
 *   - DIE KLICKPROBE lief zum ersten Mal ueber ZWEI Breiten. Zwei Wege waren
 *     fuer 1280 px geschrieben: „Bearbeiten" steht unter 720 px im
 *     Aktionsblatt und nicht in der Zeile (der Weg oeffnet jetzt erst das
 *     „⋯"), und die Richtungspfeile auf der Spur gibt es bei 390 px gar
 *     nicht — `geo.js` zeichnet sie erst, wenn die Spur am Bildschirm laenger
 *     als 280 px ist (E-P3-33/40). Der Pfeilweg misst diese Laenge jetzt
 *     (`getTotalLength()` am Spurpfad) und erwartet Pfeile genau dann, wenn
 *     die Schwelle es sagt: gemessen 190 px bei 390er Fenster (0 Pfeile,
 *     richtig) und 358 px bei 1280 (2 Pfeile).
 *   - DER BILDERLAUF warf zwei Gruende in einen Topf. „OHNE BILD: 8
 *     Aufnahmen — Sitzung nicht zu halten" stand da, wo in Wahrheit ein
 *     Platzhalter nicht aufloesbar war (die Standortseite der Verwaltung; der
 *     Referenzbestand hat keinen systemweiten Standort, Backlog Nr. 166).
 *     Jede ausgefallene Aufnahme traegt jetzt ihren Grund, und die
 *     Zusammenfassung zaehlt nach Grund.
 *
 * EIN FUND AM CODE, beim Gegenlesen von AP5-5: Das `UPDATE`, das den
 * Rettungsmitteln ohne Standortpflicht den Standort abnimmt, lief VOR der
 * Pruefung, ob der Standort ueberhaupt der eigene ist. Das `DELETE` darunter
 * schuetzt sich selbst (`AND user_id = ?`) und tat bei einer fremden Kennung
 * nichts — das UPDATE davor tat etwas: Ein abgeschicktes `base_del` mit der
 * Kennung eines ZENTRALEN Standorts haette den eigenen Rettungsmitteln dort
 * den Standort abgenommen, ohne dass ein Standort geloescht worden waere.
 * Beide Seiten pruefen jetzt zuerst und fassen dann an.
 *
 * KORREKTURSTUFE: ein Text, zwei Pruefmittel, eine Absicherung. Keine
 * Migration, kein Datenmodell.
 *
 * ---------------------------------------------------------------------------
 * 18.0.0 — S9/AP5b: DIE ZENTRALEN STAMMDATEN VERLIEREN IHRE OBERFLAECHE.
 *
 * `admin_stammdaten.php` IST GESTRICHEN. Ersatzlos, mitsamt der Karte
 * „Vordefinierte Standorte" in den Einstellungen jedes Kontos und dem
 * Schreibweg `ub_toggle` dahinter. Damit kann in dieser Anwendung niemand
 * mehr einen zentralen (systemweiten) Standort, ein zentrales Rettungsmittel,
 * eine zentrale Besatzungs-Vorbelegung, Zielklinik, Bereitschaft oder ein
 * weiteres Rettungsmittel anlegen, aendern oder loeschen. Die zwoelf
 * Schreibwege, die das konnten, standen ausnahmslos in dieser einen Datei.
 *
 * WARUM EINE HAUPTNUMMER OHNE MIGRATION — schon zum zweiten Mal in dieser
 * Zaehlung (17.0.0 war die erste). Das Datenmodell bleibt unangetastet:
 * `user_id` ist in allen sechs Stammdatentabellen weiter NULL-faehig, die
 * Tabelle `user_bases` steht, das Feld `stammdaten.user_bases` der
 * Kontosicherung wird weiter geschrieben und gelesen, und die Abfragen, die
 * zentrale Eintraege in die Kontoansicht holen, bleiben Zeile fuer Zeile
 * dieselben. Was faellt, ist eine ZUSAGE der Anwendung: Sie hat bis hier
 * angeboten, Stammdaten fuer alle Konten zentral zu pflegen. Das Handbuch
 * verliert dafuer einen ganzen Abschnitt (9.4), die Einstellungen eine Karte,
 * die Verwaltung eine Seite. Eine Nebennummer waere fuer den Wegfall einer
 * Zusage die falsche Groesse.
 *
 * DER BESCHLUSS IST AELTER ALS DIESES PAKET. Rahmenplan R39 vom 30.08.2026
 * schafft die zentralen Stammdaten ab und stellt den Rueckbau nach P5. Das
 * S9-Konzept hat ihn nie aufgenommen — in AP5-4 ist die Verwaltungsseite
 * deshalb noch auf Dialoge umgebaut worden, also fuer ein Modell, das
 * abgeschafft wird. Am 09.09.2026 hat der Auftraggeber entschieden, die Tuer
 * jetzt zu schliessen und die Seite gleich ganz zu streichen: Es gibt genau
 * eine Installation, dort sind alle zentralen Standorte geloescht, und ein
 * Weg, der sie neu anlegen kann, waere Arbeit fuer die Migration in P5.
 *
 * WAS IN P5 FOLGT (Backlog Nr. 168): `user_id` auf NOT NULL, `user_bases`
 * weg, das Feld aus der Nutzlast, die Abfragen entschlacken. Die Vorbedingung
 * dafuer — null Zeilen mit `user_id IS NULL` in sechs Tabellen — haelt ab
 * jetzt von selbst, weil keine mehr entstehen koennen. Ein `ALTER TABLE`
 * laesst sich in MySQL nicht zurueckrollen; deshalb ist die geschlossene Tuer
 * die Voraussetzung und nicht der Rest.
 *
 * WAS DER ALTBESTAND MACHT, falls in einer Anlage doch noch eine zentrale
 * Zeile steht: Sie bleibt sichtbar und unveraenderlich — in der
 * Kontoansicht mit der Plakette „systemweit", in den Auswahllisten wie
 * bisher. Aendern und loeschen kann sie niemand mehr; das braucht dann den
 * Rueckbau in P5 oder einen Eingriff in der Datenbank. Der Hinweis der
 * Nachbearbeitung, der bisher zum Anlegen unter „Standorte systemweit"
 * aufforderte, sagt das jetzt statt in eine Sackgasse zu fuehren.
 *
 * MITGEGANGEN, weil es sonst ins Leere zeigt: der Verweis der
 * Sicherungsziele-Seite (jetzt auf „Standorte" im Konto),
 * `stammdaten_dup_personal_count()` in `db.php` (sechs Aufrufer, alle in der
 * geloeschten Datei), der zweite Parameter von `sd_seite()` und die Option
 * `bearbeiten_href` in `stammdaten_ui.php` (beide ohne Aufrufer), der fuenfte
 * Einbauort des Kartendialogs in der Klickprobe, zwei Seiten des Bilderlaufs
 * und der Platzhalter `__ADMIN_STANDORT__`, der sich ohnehin nie aufloesen
 * liess.
 *
 * DEPLOY: Der Deploy synchronisiert `server/` und loescht, was im
 * Repositorium entfaellt — die Datei verschwindet also vom Webspace. Das gilt
 * aber nur, solange die Zustandsdatei der Aktion dort intakt ist; ist sie es
 * nicht, laedt der Deploy alles neu hoch und loescht nichts. Nach dem Merge
 * gehoert deshalb ein Aufruf mit STATUSCODE dazu: `admin_stammdaten.php` muss
 * 404 antworten. Eine 200 mit Anmeldeseite sieht im Browser aus wie ein
 * Erfolg. `update.php` muss NICHT laufen.
 *
 * ---------------------------------------------------------------------------
 * 18.1.0 — S9/AP6: die Tageszuordnung antwortet sofort, und ein Dienst darf
 * auf einem Fahrzeug stattfinden, das es als Stammdatensatz nicht gibt.
 *
 * ZWEI DINGE, EIN FORMULAR.
 *
 * ERSTENS: DIE ROLLEN ERSCHEINEN OHNE SPEICHERN (E-S9-11). Die
 * Besatzungsfelder entstanden bisher ausschliesslich aus der Tagesantwort,
 * also aus dem eingefrorenen `day_crew`. Wer ein anderes Rettungsmittel
 * waehlte, sah weiter die Rollen des alten und bekam die neuen erst nach dem
 * Speichern — zwei Speichervorgaenge fuer eine Handlung, und dazwischen zeigte
 * das Formular etwas anderes an, als darueber ausgewaehlt war. Der neue
 * lesende Aufruf `api/day.php?vorschau=<vehicle_id>[&base=<base_id>]`
 * beantwortet dieselbe Frage fuer eine noch nicht gespeicherte Wahl und
 * SCHREIBT NICHTS; eingefroren wird weiterhin erst beim Speichern (E8).
 * Getippte Namen bleiben stehen, wo die Rolle bleibt — ohne das waere jede
 * versehentliche Auswahl ein Datenverlust.
 *
 * ZWEITENS: „ANDERES RETTUNGSMITTEL …" (E-S9-10). Der letzte Eintrag der
 * Auswahl klappt drei Felder auf — Bezeichnung, Typ mit Betriebsart und einen
 * Standort, der Auswahl UND Freitext ist. Gespeichert wird nur am Tag:
 * `vehicle_id` bleibt NULL, `vehicle_name`, `vehicle_typ`, `kind` und der
 * Standort stehen in der Momentaufnahme. ES ENTSTEHT KEIN STAMMDATENSATZ
 * (F17) — wer einmal auf einem fremden Fahrzeug Dienst tut, soll dafuer
 * keinen Eintrag anlegen muessen, den er danach nie wieder braucht. Suche,
 * Filter und Tagesliste finden ihn trotzdem: Sie lesen die Momentaufnahme.
 *
 * KEIN ROLLENSATZ, KEINE FAEHIGKEITEN (F19). Ein Rettungsmittel nur fuer den
 * Tag fuehrt keine Besatzungsrollen; das Formular sagt das. (Der Satz verwies
 * zunaechst auf die abweichende Besatzung am einzelnen Einsatz — das war
 * falsch, siehe 18.1.1.) Vorhandene NAMEN in
 * `day_crew` werden dabei NICHT geloescht — dieselbe Regel wie beim Wechsel
 * auf ein Rettungsmittel mit weniger Rollen: Ein Name, den jemand eingetragen
 * hat, ueberlebt und steht wieder da, sobald die Rolle zurueckkommt.
 *
 * DREI STELLEN, AN DENEN EINE REGEL JETZT NUR NOCH EINMAL STEHT:
 * `pruef_typ_betriebsart()` (Typ und Betriebsart, geteilt von Stammdatensatz
 * und Tagesfassung), `pruef_tagesrettungsmittel()` (die Pruefschicht auch fuer
 * diesen Schreibweg — CLAUDE.md 4) und `dt_rollensatz_einfrieren()` (das
 * Einfrieren, geteilt von beiden Wegen in `dt_zuordnen()`).
 *
 * EIN FUND BEIM BAUEN, und er waere still geblieben: Die Vorschlagsliste des
 * Standortfelds setzte die verborgene Kennung VOR dem `input`-Ereignis, das
 * die Speichern-Leiste weckt — und der eigene Zuhoerer darunter loeschte sie
 * gleich wieder, weil Tippen Freitext bedeutet. Der Treffer sah aus wie ein
 * Treffer und wurde als Freitext gespeichert, ohne Koordinate. Gemessen:
 * Kennung „" statt „77". Jetzt faellt erst das Ereignis, dann steht die
 * Kennung.
 *
 * ZWEI FUNDE BEIM PRUEFEN, beide vor der Auslieferung behoben:
 *
 *   - ZWEI FELDER „STANDORT" AUF EINER SEITE. Das Auswahlfeld des Diensttags
 *     blieb neben dem des Tagesfahrzeugs stehen, obwohl der Weg `adhoc`
 *     `base_id` gar nicht mitschickt: bedienbar, ohne dass etwas geschieht.
 *     Gefunden auf dem Bild des Bilderlaufs, nicht von einer Zahl. Das
 *     aeussere Feld geht jetzt mit.
 *   - STILLER VERLUST VON BESATZUNGSNAMEN (F-S9-U-34). Die Rollenvorschau
 *     uebernimmt getippte Namen aus den SICHTBAREN Feldern — und beim
 *     Tagesfahrzeug gibt es keine. Auf dem Rueckweg auf ein Rettungsmittel
 *     MIT Rollen rendert sie deshalb leere Felder fuer Rollen, die in
 *     `day_crew` einen Namen haben, und das Speichern schrieb die Leere
 *     zurueck. Gemessen an Diensttag 399: drei Namen vorher, null nachher,
 *     ohne Fehler und ohne Meldung. Das Formular merkt sich jetzt die
 *     geladenen Namen und legt die sichtbaren Felder darueber; ein geleertes
 *     Feld ueberschreibt weiterhin, sonst kaeme ein geloeschter Name zurueck.
 *
 * NEBENNUMMER: neue Funktionen, kein Datenmodell, keine Migration.
 * `update.php` muss NICHT laufen.
 *
 * 18.1.1 — S9/AP6: zwei Diensttage mit „Anderem Rettungsmittel" boten
 * verschiedene Besatzungsrollen an. Jetzt beide keine.
 *
 * WORAN ES LAG. `dt_rollensatz_einfrieren()` loescht beim Wechsel des
 * Rettungsmittels nur LEERE Rollen — ein eingetragener Name ueberlebt, damit
 * ein versehentlicher Wechsel keine Eingabe kostet. Ein Diensttag, der von
 * einem Rettungsmittel MIT Rollen auf „Anderes Rettungsmittel" umgestellt
 * wurde, behielt deshalb die benannten Zeilen in `day_crew`. Das
 * Einsatzformular liest genau diese Zeilenmenge (`role_gate`) — und bot dort
 * weiter die Rollen des frueheren Rettungsmittels an, waehrend ein FRISCH
 * angelegter Adhoc-Tag keine bot. Gemessen: derselbe Tag, 3 Rollen mit
 * Rettungsmittel, danach 2 (die beiden benannten) statt 0.
 *
 * WAS JETZT GILT. Gefragt ist der DIENST, und ein Rettungsmittel nur fuer den
 * Tag fuehrt keine Rollen (E-S9-10, F19) — `einsatz_form.php` fragt dafuer
 * `dt_ist_tagesrettungsmittel()`, und die Antwort steht an EINER Stelle, nicht
 * als Vergleich vor Ort. Die NAMEN bleiben unangetastet: Sie stehen weiter in
 * `day_crew`, die Leseansicht des Tages zeigt sie, und sie kehren zurueck,
 * sobald wieder ein Rettungsmittel mit dieser Rolle zugeordnet ist
 * (Auftraggeberentscheidung vom 09.09.2026, Weg a).
 *
 * WAS DAMIT SICHTBAR WIRD, und es ist keine Folge dieser Aenderung, sondern
 * eine Luecke, die sie nur noch gleichmaessig macht: An einem Diensttag mit
 * „Anderem Rettungsmittel" laesst sich BESATZUNG UEBERHAUPT NICHT ERFASSEN —
 * weder am Tag (kein Rollensatz) noch am einzelnen Einsatz (dasselbe Tor).
 * Der Hinweis im Tagesformular und das Handbuch behaupteten das Gegenteil;
 * beide sind berichtigt. Ob ein solcher Tag Rollen anbieten SOLL — und
 * welche —, ist eine Gestaltungsfrage und steht im Backlog.
 *
 * KORREKTURNUMMER: eine Ungleichheit beseitigt, kein neues Feld, kein
 * Datenmodell. `update.php` muss NICHT laufen.
 *
 * 19.0.0 — S9/AP7: DIE NOTIZEN DES EINSATZES WERDEN VERSCHLUESSELT.
 * (Schritt 1 von fuenf: Katalog und Formular.)
 *
 * WARUM EINE HAUPTNUMMER. Es kommt kein Feld dazu und keine Spalte weg — es
 * aendert sich, WER den Inhalt sehen kann. `missions.notes` war die letzte
 * Spalte, in der ein Freitext im Klartext auf dem Server lag; der Platzhalter
 * „Freitext (keine Patientendaten!)" war die einzige Sicherung dagegen, und
 * eine Bitte ist keine Sicherung. Ab hier gilt fuer die Notizen dasselbe wie
 * fuer Diagnose und Einsatzort: Der Browser ver- und entschluesselt, der
 * Server sieht Chiffretext.
 *
 * WAS DAS AUFRAEUMT, und es stand vorher nirgends: Die Suche lieferte jede
 * Notiz im Klartext an den Browser, fuer den GESAMTEN aktiven Bestand, ohne
 * dass jemand entsperrt haben musste (`api/suchindex.php`) — waehrend der
 * Kopfkommentar derselben Datei aufzaehlt, was der Server angeblich nicht
 * sieht. Und die Zusage E6 „Administration sieht keinen Klartext"
 * (`adminbackup_lib.php`) stimmte nicht: `notes` steht in der Spaltenliste
 * des Adminpakets. Beides ist kein Nebeneffekt von AP7, sondern sein Grund.
 *
 * DER WEG IST DER KATALOG, nicht ein Sonderfall. `'store' => 'pat'` neben dem
 * vorhandenen `'crew'`: mf_ist_spalte() nimmt das Feld damit VON SELBST aus
 * jedem SELECT, INSERT und UPDATE auf `missions`. Der Einsatzort ist seinerzeit
 * ohne Katalogeintrag verschluesselt worden — mit Markup von Hand und
 * Kennungen, an denen ein Dutzend Aufrufe haengen. Diesen Weg noch einmal zu
 * gehen hiesse, die Zusage „Feldkatalog statt Sonderfall" (CLAUDE.md 4) ein
 * zweites Mal zu brechen; S11 verschiebt die Zielklinik denselben Weg und
 * findet den Schluessel dann vor.
 *
 * VIER STELLEN, DIE STILL DANEBENGEGRIFFEN HAETTEN:
 *
 *   - `readField()` fragte `'store' === 'crew'` und machte aus ALLEM ANDEREN
 *     eine Spalte. Ein Feld ohne `name` sendet nichts, `$raw` waere leer, und
 *     jedes Speichern haette NULL in die Spalte geschrieben — bei gesperrter
 *     Sitzung waere die Notiz damit weg gewesen: kein Blob, keine Spalte,
 *     keine Meldung. Gefragt wird jetzt mf_ist_spalte().
 *   - Der Riegel `PAT_INPUTS` fasste nur `input`. Die Notiz ist das einzige
 *     mehrzeilige Feld; sie waere bei gesperrtem Schluessel bedienbar
 *     geblieben und der Text beim Speichern spurlos verfallen.
 *   - Ein leeres Feld beim Altbestand: Solange die Anhebung einen Einsatz
 *     nicht erreicht hat, steht der Text noch in der Spalte. Ohne Rueckfall
 *     (PAT_ALT) zeigte das Formular ein leeres Feld und das naechste
 *     Speichern deckte den Text zu.
 *   - Die Spalte geht jetzt im SELBEN UPDATE auf NULL, sobald ein Blob kam —
 *     nicht erst beim Anhebelauf. Nur dann: Bei gesperrter Sitzung bleibt
 *     beides unangetastet.
 *
 * GEMESSEN: Rundlauf am Einsatz 2324 — der POST traegt kein `f_notes` und
 * keinen Klartext (1163 Byte, Klartextprobe negativ), die Spalte ist danach
 * NULL, der Text kommt nach dem Neuladen wortgleich zurueck. Gesperrte
 * Sitzung: Riegelmeldung sichtbar, Notizfeld gesperrt, und ein Speichern
 * laesst den Blob byteweise unveraendert (397 Byte, Pruefsumme 94f8c377…
 * vorher wie nachher). 0 Konsolenfehler.
 *
 * NOCH NICHT IN DIESEM SCHRITT: die lesenden Wege (api/mission.php, Suche,
 * Export, Sicherung, Import), der Anhebelauf, die Kennzeichnung mit Schloss
 * und die normative Dokumentation (CLAUDE.md 4, Technik 4.98). Sie folgen in
 * den Schritten 2 bis 5; bis dahin zeigt die Einsatzansicht die Notiz eines
 * gerade gespeicherten Einsatzes nicht an.
 *
 * KEINE MIGRATION. `missions.notes` ist bereits NULL-faehig (schema.sql) und
 * bleibt stehen, bis P8 sie entfernt (R60). `update.php` muss NICHT laufen.
 *
 * 19.0.1 — S9/AP7 Schritt 2a: Anzeige und Suche lesen aus dem Blob.
 *
 * ZUR ZAEHLWEISE: Die Hauptnummer gehoert dem ganzen Umbau; seine Schritte
 * zaehlen an der Korrekturstelle weiter, bis er steht. Eine zweite
 * Hauptnummer fuer denselben Umbau waere eine zweite Erzaehlung.
 *
 * DIE SUCHE WAR DAS LOCH. `api/suchindex.php` lieferte `m.notes` im
 * Klartext — fuer den GESAMTEN aktiven Bestand, bei jedem Aufruf der
 * Suchseite, ohne dass irgendjemand entsperrt haben musste. Der Kopfkommentar
 * derselben Datei zaehlt seit jeher auf, was der Server angeblich nicht
 * sieht. Gemessen: alte Fassung 31 Schluessel je Einsatz mit `notes`, neue
 * Fassung 30 ohne. Der Heuhaufen in `suche.php` nimmt die Notiz jetzt aus dem
 * entschluesselten `_pat` — gefunden wird sie also nur nach dem Entsperren,
 * wie Diagnose und Einsatzort. Gemessen an einem Suchwort aus einer Notiz:
 * gesperrt 0 von 83, entsperrt 1 von 83, und ein Klartextwort findet
 * gesperrt weiter 31 Treffer.
 *
 * DIE ANZEIGE bekommt die Notiz aus `zeigePat()` statt aus `m.fields` —
 * dieselbe Karte, derselbe Rang 80, aber MIT Schloss, weil sie es jetzt
 * verdient. `api/mission.php` brauchte dafuer keine Zeile: Es fragt
 * mf_ist_spalte(), und die Antwort hat sich mit dem Katalogeintrag von selbst
 * geaendert — so soll ein Feldkatalog wirken.
 *
 * ZEILENUMBRUECHE BLEIBEN JETZT STEHEN. Die Notiz ist das einzige mehrzeilige
 * Feld; ueber `m.fields` und `esc()` wurden aus drei Zeilen bisher eine. Der
 * Umzug war der Moment, das zu entscheiden statt zu erben. Gemessen: ein
 * Umbruch im Text, ein <br> in der Zeile.
 *
 * DREI SICHTBARE SAETZE ZAEHLEN AUF, WAS GESPERRT IST — auf der Suchseite, in
 * der Einsatzansicht und auf der Startseite. Zwei davon nennen die Notizen
 * jetzt mit; sie waren eine Zusage und stimmten nicht mehr. Der dritte
 * (Startseite) nennt nur, was DORT verborgen bleibt, und bleibt unveraendert.
 *
 * NOCH OFFEN: Export, Sicherung, Import (Schritt 2b), der Anhebelauf
 * (Schritt 3), die Kennzeichnung (Schritt 4) und die normative Doku.
 *
 * 19.0.2 — S9/AP7 Schritt 2b: die Aussenwege. Export, Sicherung und Import
 * fuehren die Notiz im verschluesselten Block; NUTZLAST 10 -> 11.
 *
 * DIE NUTZLASTNUMMER MUSSTE STEIGEN. Eine 11er-Sicherung traegt die Notiz nur
 * noch im `pat`-Block. Eine Installation vor Web 19 suchte sie in der Spalte,
 * faende dort NULL, spielte die Datei ein und meldete Erfolg — mit lauter
 * leeren Notizen. Genau dieser stille Schaden ist der Grund fuer die Zahl;
 * eine aeltere Installation weist eine 11er-Datei jetzt ab
 * (`NUTZLAST_HOECHSTENS` in api/backup_restore.php zieht mit).
 *
 * DER WEG ZURUECK BLEIBT OFFEN, und das ist eine ausdrueckliche Entscheidung:
 * `notes` steht im Einspielweg weiter in `$extraCols` — von HAND, denn
 * mf_ist_spalte() sagt seit Schritt 1 nein. Eine 10er-Datei traegt den
 * Klartext in der Spalte; faellt sie aus der Liste, wird er beim Einspielen
 * stillschweigend verworfen. Er wird deshalb weiter geschrieben, und der
 * Anhebelauf holt ihn beim naechsten Entsperren in den Blob. Wenn P8 die
 * Spalte entfernt (R60), faellt mit ihr die Faehigkeit, eine 10er-Datei
 * vollstaendig einzuspielen — das gehoert dort ausdruecklich entschieden.
 *
 * EIN FUND, DEN NUR DER KREISLAUF GEMELDET HAT. `import.js` fuehrt die Ziele
 * des pat-Blocks in einem ABSCHLIESSENDEN `switch`; ein Ziel ohne `case`
 * faellt heraus, ohne Fehler und ohne Meldung. Das Profil zeigte nach dem
 * Umzug auf `pat.notes`, der `case` fehlte — und der CSV-Kreislauf meldete
 * 114 VERLORENE NOTIZEN. Kein anderes Pruefmittel haette das gesehen: Die
 * Seite sah richtig aus, die Zahl der Einsaetze stimmte, nur der Text war
 * weg. Nach der Behebung: 9120 Einzelvergleiche, 0 unerklaerte Abweichungen,
 * 1070 erwartete, 0 ungenutzte Regeln.
 *
 * DIE SCHRANKE WIRKT WEITER, aber ueber den Blob statt ueber eine eigene
 * Spalte: Ohne den Haken fuer personenbezogene Angaben faellt der `pat_blob`
 * schon serverseitig weg, und ohne entsperrte Sitzung gaebe es auch mit Haken
 * nichts zu lesen. Der Import behandelt die Notiz als `sensitive` — sie
 * verlaesst den Browser nur verschluesselt; `api/import_commit.php` nimmt die
 * Spalte gar nicht mehr entgegen.
 *
 * 19.0.3 — S9/AP7 Schritt 3: der Altbestand zieht um. `api/pat_anheben.php`.
 *
 * DER SERVER KANN NICHT VERSCHLUESSELN, und das ist der ganze Punkt der
 * Zusage. Der Inhaltsschluessel liegt in der Schluesselhuelle des Kontos und
 * wird aus dem Passwort abgeleitet; umziehen kann den Altbestand nur der
 * Browser. `assets/unlock.js` tut es im Hintergrund, sobald ein Schluessel
 * vorliegt — an ALLEN DREI Entsperrwegen, nicht nur nach der Anmeldung: Die
 * KDF-Anhebung haengt am Vormerkfach, weil sie die Passwortableitungen
 * braucht; diese hier braucht den Inhaltsschluessel, und den gibt es auch
 * Stunden spaeter ueber den Dialog.
 *
 * VIER REGELN, JEDE AUS EINEM KONKRETEN SCHADEN:
 *
 *   - EINE WACHE JE ZEILE. `missions` fuehrt kein `updated_at`. Jedes UPDATE
 *     traegt `notes IS NOT NULL` UND `pat_blob <=> ?` — der Blob muss noch
 *     genau der sein, den dieser Browser gelesen hat. Sonst ueberschriebe ein
 *     zweites Fenster eine inzwischen geaenderte Diagnose, lautlos. `<=>`
 *     statt `=`, weil der Blob NULL sein darf.
 *   - KEIN `manual = 1`, KEIN `edited = 1`. Das Formular setzt beides bei
 *     jedem Speichern, und `ingest.php` hoert bei `manual = 1` auf, Daten der
 *     Uhr zu uebernehmen. Ein Anhebelauf, der den Formularweg nachbaute,
 *     froere den gesamten Altbestand eines Kontos still gegen die Uhr ein —
 *     ungefragt, beim naechsten Entsperren. Geschrieben werden genau zwei
 *     Spalten. GEMESSEN: 88 Einsaetze, `manual = 1` bei 86 vorher wie
 *     nachher, `edited = 1` bei 79 vorher wie nachher.
 *   - DER BLOB GEWINNT. Steht dort schon eine Notiz, bleibt sie; die Spalte
 *     ist dann ein Rest. Gemessen an einem Einsatz, dessen Spalte einen
 *     anderen Text trug als sein Blob: Der Blobtext stand danach da.
 *   - EIN UNLESBARER BLOB WIRD NICHT ANGEFASST. Er gehoert zu einem anderen
 *     Schluessel; ihn zu ersetzen hiesse, fremde Angaben zu loeschen.
 *
 * KEIN GESPEICHERTER MERKER. „Einmal je Konto" ist die Wirkung, nicht der
 * Mechanismus: Sobald kein Einsatz mehr Klartext traegt, liefert GET eine
 * leere Liste. Ein Merker kostete eine Spalte samt Migration — und waere
 * falsch, sobald wieder Klartext hereinkommt: eine eingespielte Sicherung mit
 * Nutzlast 10, ein CSV-Import einer alten Datei, das Zuruecksetzen des
 * Demo-Kontos. Der abgeleitete Zustand kennt diesen Fall von selbst.
 *
 * GEMESSEN: 12 Einsaetze mit Klartext, ein Anmeldevorgang, drei Aufrufe
 * (GET 12, POST angehoben 12 / uebersprungen 0, GET leer mit offen 0), Spalte
 * danach 0, 0 Konsolenfehler. Sicherungsumlauf 287 842 Einzelvergleiche,
 * 0 unerklaerte Abweichungen, 159 erwartete — die Notiz steht dort in BEIDEN
 * Haelften: Spalte leer, `pat.notes` gefuellt, gleicher Wortlaut. Das ist der
 * Beleg, dass sie umzieht statt zu verschwinden.
 *
 * NOCH OFFEN: die Kennzeichnung mit Schloss und die Karte „Was hier gilt"
 * (Schritt 4), die normative Doku samt CLAUDE.md 4 (Schritt 5).
 *
 * 19.1.0 — S9/AP7 Schritt 4: zwei Zeichen sagen, wer mitliest (E-S9-02).
 *
 * ZUR ZAEHLWEISE, IN ABWANDLUNG VON 19.0.1: Die Korrekturstellen zaehlten die
 * Schritte, die nur VERSCHIEBEN — Katalog, Wege, Altbestand. Dieser Schritt
 * fuegt etwas hinzu, das man sieht und benutzt: ein Zeichen an jedem
 * verschluesselten Feld, eine Kleinzeile an jedem Klartext-Freitextfeld, eine
 * Legende. Das ist eine Nebennummer, kein Feinschliff.
 *
 * ZWEI ZEICHEN, DIE EINANDER AUSSCHLIESSEN. Ein verschluesseltes Feld traegt
 * das SCHLOSS, ein Klartext-Freitextfeld die KLEINZEILE „Klartext — keine
 * Patientendaten" (Nr. 132). Die Wahl steht an EINER Stelle
 * ($feldKennzeichen() in einsatz_form.php) und nicht in jedem Zweig; der Satz
 * selbst steht einmal im Katalog. Ein Satz, der an vier Feldern verschieden
 * lautet, ist kein Versprechen mehr, sondern vier. GEMESSEN: 8 Schloesser
 * (Einsatznummer, Nachname, Vorname, Geburtsdatum, Alter, Diagnose,
 * Einsatzort, Beschreibung Einsatzort — dazu die Karte „Notizen", die es als
 * Kartenzahl traegt), 9 Kleinzeilen (Bergwacht-Angaben, weiterer Notarzt,
 * sieben Rollen) und 0 Felder mit BEIDEM.
 *
 * DAS SCHLOSS STEHT RECHTS VOM WORT. Das Konzept sagt „links neben der
 * Beschriftung"; der vorhandene Baustein `.symbol-schutz` setzt es ueber
 * `margin-left` dahinter, und genauso steht es seit Web 15 in der
 * LESEANSICHT. Links hiesse eine neue CSS-Regel und damit eine neue
 * Darstellung — Mockup und Freigabe (CLAUDE.md 5), fuer einen Unterschied,
 * den niemand verlangt hat. Formular und Leseansicht zeigen jetzt dasselbe
 * Zeichen an derselben Stelle; das ist mehr wert als der Wortlaut.
 *
 * ZWEI FEHLER, DIE KEINE ZAHL GEMELDET HAT — beide auf dem Bild gefunden:
 *
 *   - DAS SCHLOSS DER KARTE „NOTIZEN" STAND ALLEIN IN EINER LEEREN ZEILE.
 *     Traegt ein Feld den Namen seiner Karte, blendet $labelSichtbar() das
 *     Wort aus (es stuende zweimal da) — das Zeichen blieb und zeigte auf
 *     nichts. Es entfaellt dort; die Karte sagt es als Kartenzahl.
 *   - „WEITERER NOTARZTKLARTEXT — KEINE PATIENTENDATEN". `.feld-klein-inline`
 *     bringt keinen eigenen Abstand mit; die uebrigen Verwender stehen im
 *     Markup auf einer eigenen Zeile und bekommen ihn vom HTML geschenkt.
 *
 * KEIN NEUER BAUSTEIN, KEIN NEUES TOKEN. Das Schloss ist `.symbol-schutz`,
 * die Kleinzeile `.feld-klein-inline`, die Legende dieselbe klappbare Karte
 * wie „Reanimation" darueber. `docs/Design.md` neu erzeugt: unveraendert.
 * Neu ist EIN Schluessel am Baustein Ortsfeld — 'geschuetzt' => true haengt
 * das Schloss an seine Beschriftung, weil 'label' escaped wird und das
 * bleiben soll.
 *
 * ---------------------------------------------------------------------------
 * 19.1.1 — DAS SCHLOSS AN DEN BEIDEN STELLEN, AN DENEN ES FEHLTE.
 *
 * Beide Befunde kamen von aussen, aus einem Blick auf den Bildschirm, nicht
 * aus einer Zahl dieses Hauses. Das gehoert zur Sache dazu: 19.1.0 hat
 * gezaehlt, WIE VIELE Zeichen stehen, und dabei nicht gepruefen, ob an jeder
 * Stelle eines steht, an der eines hingehoert. Eine Zahl, die ihr eigenes
 * Sollmass setzt, bestaetigt sich selbst.
 *
 *   - LESEANSICHT, EINSATZNUMMER (einsatz.php). Sieben der acht Zeilen des
 *     entschluesselten Blocks liefen ueber dtGeschuetzt(), die achte nicht.
 *     Die Nummer liegt seit Web 2.9.0 im `pat_blob` — die Migration
 *     `2026_07_29_einsatznummer_verschluesselt` hat die Spalte damals sogar
 *     geloescht —, und sie steht dort ueberhaupt nur, WEIL entschluesselt
 *     wurde. Wer die Karte las, schloss aus dem fehlenden Schloss auf
 *     Klartext, und ein Leitstellen-Aktenzeichen ist genau die Angabe, bei
 *     der jemand das wissen will.
 *
 *   - FORMULAR, KARTE „NOTIZEN" (einsatz_form.php). Hier war es die Kehrseite
 *     einer Berichtigung aus 19.1.0: Weil das Zeichen dort allein in einer
 *     leeren Zeile stand (die Feldbeschriftung heisst wie die Karte und wird
 *     ausgeblendet), hat 19.1.0 es entfernt und die Kartenzahl als Ersatz
 *     genommen. Ein Text ist aber kein Zeichen: Daneben zeigt „PatientIn" an
 *     jedem Feld ein Schloss, und der Unterschied las sich als Aussage ueber
 *     die Sache.
 *
 * Der Weg zurueck ist NICHT das alte, freistehende Zeichen, sondern ein neuer
 * Schluessel an ui_karte_start(): 'geschuetzt' => true haengt das Schloss an
 * den KARTENTITEL, im <h2> und nicht als eigenes Flex-Kind (`.karte-kopf` hat
 * `gap`; daneben bekaeme es den Abstand zweimal). Damit steht es dort, wo es
 * ueberall sonst steht — rechts vom Wort, `.symbol-schutz`, keine neue
 * CSS-Regel und keine neue Darstellung.
 *
 * Eine Korrekturnummer: keine neue Funktion, kein Feld, keine Migration.
 *
 * ---------------------------------------------------------------------------
 * 19.1.2 — BACKLOG-RUNDE (Rahmenplan Schritt 9): Einzelpunkte, die keiner
 * Phase beduerfen. Eine Korrekturstufe fuer die ganze Runde, je Punkt ein
 * Commit — dasselbe Muster wie die Korrekturstufe 15.5.2 (Nr. 148/149).
 *
 * Was sie gemeinsam haben: Es sind Fehler, die STILL sind. Keiner von ihnen
 * wirft eine Meldung, keiner faellt beim Bedienen auf, und drei von ihnen
 * zeigen stattdessen etwas Plausibles — den juengsten Diensttag statt des
 * importierten, eine leere Trefferliste statt 83 Einsaetzen, eine
 * Vorbelegung, die aussieht, als waere keine gesetzt. Genau deshalb standen
 * sie im Backlog und nicht in einem Fehlerbericht.
 *
 * Die Punkte im Einzelnen stehen im Changelog. Keine Migration.
 *
 * ---------------------------------------------------------------------------
 * 19.2.0 — DIE ALTE RUNDENZAHL IST WEG (Backlog Nr. 155, 136).
 *
 * `KDF_ITER_LISTE` traegt nur noch 600000. Damit rechnet jede Anmeldung
 * wieder EINMAL ab statt zweimal — gemessen 1580 -> 1369 ms (Median aus je
 * drei Anmeldungen im Browser), also rund 210 ms je Anmeldung.
 *
 * WARUM DAS EINE NEBENNUMMER IST UND KEINE KORREKTUR: Der Schritt nimmt der
 * Anmeldung einen angebotenen Wert. Wer ihn faelschlich geht, sperrt jedes
 * Konto aus, das noch auf dem Altwert steht — der Browser rechnet dessen
 * Token dann gar nicht erst. Rueckholbar ist das nur ueber eine
 * Codeaenderung samt Deploy, nicht in der Verwaltung.
 *
 * DAS TOR STEHT IM KOMMENTAR ZU KDF_ITER_LISTE und ist gefahren worden:
 * `SELECT COUNT(*) FROM users WHERE kdf_iter = 320000` ergab **0**, auf dem
 * Pruefstand wie auf der Produktivinstallation (Statuszeile
 * „Schluesselableitung": kein Konto im Uebergang, bestaetigt vom
 * Auftraggeber am 12.09.2026).
 *
 * WARUM ES NICHT FRUEHER GING, und das ist der eigentliche Fund: Die
 * Demo-Fixture brachte die alte Rundenzahl mit, der Reset schrieb sie alle
 * 30 Minuten zurueck, und die stille Anhebung ueberspringt das Demo-Konto
 * ausdruecklich (E-P1-19). Der Altwert konnte deshalb NIE von selbst
 * verschwinden — jede Abfrage haette ewig 1 ergeben. Aufgeloest hat es erst
 * der Neubau des Referenzbestands ueber die drei Laeufe
 * (`tools/referenzdatensatz/LIESMICH.md`), mit dem Passwortschritt im
 * Browser, der mit KDF_ITER_ZIEL ableitet.
 *
 * ZWEI RIEGEL HALTEN ES FEST (aus 19.1.2): `fixture/erzeugen.php` bricht ab,
 * wenn das Konto nicht auf dem Zielwert steht, und `demo_fixture_laden()`
 * weist eine Fixture ab, deren Rundenzahl diese Fassung nicht mehr anbietet.
 * Der zweite ist der wichtigere — ohne ihn waere ein Reset mit einer alten
 * Fixture STILL erfolgreich, und niemand kaeme mehr in das Demo-Konto.
 *
 * Keine Migration. Die Fixture ist neu erzeugt (gleiche Zahlen: 16
 * Diensttage, 88 Einsaetze, 2 Geraete, 55 861 Spurpunkte).
 *
 * ---------------------------------------------------------------------------
 * 19.3.0 — BACKLOG-RUNDE 2: der Block Betrieb wird bedienbar.
 *
 * Eine Nebennummer, weil zwei Handgriffe dazukommen, die es bisher NUR auf
 * der Kommandozeile gab oder gar nicht: die Hintergrundjobs anhalten
 * (Nr. 118) und eine Testmail schicken (Nr. 120). Beides sind Dinge, die
 * eine BetreiberIn auf geteiltem Hosting sonst nicht tun kann.
 *
 * Die uebrigen drei Punkte der Runde sind Korrekturen und laufen unter
 * derselben Nummer mit — das Muster der Korrekturstufe 15.5.2 und der
 * Runde 19.1.2: der Rueckweg aus der Wartungsseite (Nr. 126), die
 * Querverweise auf „Import / Export" (Nr. 119) und das Streichen der
 * doppelten Rasterregel `.zweispalter` (Nr. 125).
 *
 * ZWEI SEITEN GEBEN DABEI EINE ZUSAGE AUF ODER SCHRAENKEN SIE EIN, und
 * beides ist ausdruecklich freigegeben: Betrieb -> Status hatte „genau eine
 * Ausnahme" von „rein lesend" und hat jetzt zwei; „Import / Export" traegt
 * nicht mehr den Anspruch, alle Datenwege zu fuehren, sondern sagt, wo die
 * uebrigen liegen.
 *
 * Ein neues Zeichen im Symbolvorrat (`mail.svg`, Tabler „mail", das 53.).
 * Die Punkte im Einzelnen stehen im Changelog. Keine Migration.
 *
 * 19.3.1 — BACKLOG-RUNDE 3: neun Punkte, zwei davon am Server.
 *
 * Eine Korrekturnummer, und das ist eine Entscheidung (E-BR3-14): Von den
 * neun Punkten der Runde fassen genau zwei `server/` an, und beide sind
 * Fehlerbehebung beziehungsweise Feinschliff — die CSRF-Pruefung in
 * `api/kdf_upgrade.php` wandert vor den Demo-Ausstieg (Nr. 67, Unterpunkt),
 * und drei Klassen ohne Regel verlassen das Markup (Nr. 41, drei von fuenf).
 * Keine neue Funktion, kein neues Feld, keine Migration.
 *
 * DIE REIHENFOLGE IN `kdf_upgrade.php` WAR EINE GEERBTE LUECKE. Der
 * Demo-Ausstieg stand vor der Pruefung: Ein Aufruf ohne Formular-Token kam
 * fuer das Demo-Konto mit 200 zurueck, waehrend jedes andere Konto 403 sah.
 * Folgenlos war das nur, weil hinter dem Ausstieg nichts steht — wer dort
 * einmal etwas hinschreibt, erbt eine ungeschuetzte Stelle, ohne es zu
 * merken. Gemessen am Pruefstand, alter gegen neuer Stand: ohne Header
 * vorher 200, jetzt 403; mit Header unveraendert 200 und
 * `uebersprungen: demo`. Der HAUPTPUNKT von Nr. 67 — ein API-Zweig in
 * `csrf_check()`, damit die fuenfzehn Endpunkte unter `server/api/` die
 * Pruefung nicht jeder selbst schreiben — bleibt bei P5.
 *
 * DAZU EIN FUND, DEN DAS NEUE PRUEFMITTEL SELBST GEMACHT HAT (Nr. 58). Die
 * 404-Seite von `apk.php` rief `ui_geruest_start()` und `ui_seite_ende()`,
 * aber nie `ui_seite_start()` — und nur letzteres gibt `<!doctype>`, `<head>`
 * und `<body>` aus. Die Seite ging als Bruchstueck hinaus: ohne Doctype, ohne
 * Titel, OHNE STYLESHEET; der Browser las sie im Quirks-Modus und zeichnete
 * sie in Times New Roman. Gefunden ueber die GEGENRICHTUNG der Pruefung
 * ("Geruest ohne Seitenhuelle"), behoben mit einer Zeile. Erreichbar war es
 * ueber jeden Verweis auf ein APK, das nicht mehr im Ordner liegt.
 *
 * Die uebrigen sieben Punkte liegen in `tools/` und `docs/` und loesen nach
 * CLAUDE.md 2 keine Stufe aus; sie stehen im Changelog unter derselben
 * Ueberschrift, weil sie zur selben Runde gehoeren. Uhr und Android sind
 * unberuehrt. Keine Migration — `update.php` muss nach dem Deploy nicht
 * laufen.
 *
 * 19.4.0 — MOCKUP-RUNDE 9c, AP1: DIE IMPORTVORSCHAU BEKOMMT EINE UEBERSCHRIFT.
 *
 * Vier Gestaltungsaufgaben in einer Freigaberunde (Rahmenplan Schritt 9c,
 * `Konzept-Mockup-Runde.md`); dies ist die erste. Backlog Nr. 41 fragte, was
 * mit fuenf Klassen geschehen soll, die im Markup stehen und keine Regel
 * haben. Drei davon hat Backlog-Runde 3 gestrichen (19.3.1); die beiden
 * letzten sitzen in der Importvorschau und sind hier beantwortet — die eine
 * bekommt eine Regel, die andere faellt weg.
 *
 * `imp-daygroup` ist die Kopfzeile einer Tagesgruppe. Sie trug ihren Text in
 * `<strong>` und sah damit aus wie die Datenzeilen darunter — eine
 * Ueberschrift, die keine war. Jetzt traegt sie Rauch als Flaeche, eine
 * kraeftige Oberlinie, das Datum in Kopfschrift und Dunkelblau, den Rest
 * gedaempft und eine Stufe kleiner. Und das Datum steht deutsch
 * ("17.01.2026" statt "2026-01-17"): Die ISO-Form kam aus der Datei und ist
 * bis in die Oberflaeche durchgereicht worden.
 *
 * `imp-warn` ist ersatzlos gestrichen. Die Warnung "abweichende Crew" stand
 * als Fliesstext zwischen zwei Punkten; sie ist jetzt eine
 * `.plakette-orange` mit dem Symbol `warnung` — der Baustein, mit dem die
 * Anwendung ueberall "Zustand, der Aufmerksamkeit will" zeigt. Dieselbe
 * Kopfzeile traegt die Gruppe "Nicht zuordenbar", dort in Rot und mit der
 * Zahl der Zeilen statt einer Klammer.
 *
 * NEUE STUFE, KEINE KORREKTUR: Es sind neue Darstellungen (Mockup M-MR-01,
 * Variante A, freigegeben am 13.09.2026), kein Fehler, der behoben wird.
 * Uhr und Android sind unberuehrt. Keine Migration.
 *
 * WAS DIESE STUFE NICHT LOEST und was beim Pruefen im Browser aufgefallen
 * ist: Die Kopfzeile sitzt in einer Tabellenzelle, die so breit ist wie die
 * ganze Vorschautabelle — gemessen 2677 px bei 342 bis 1354 px Sichtfenster.
 * Besatzung und Plakette stehen deshalb in JEDER Breite ausserhalb des
 * Sichtfensters, bis jemand waagerecht scrollt. Das ist kein Rueckschritt
 * (der alte Fliesstext stand an derselben Stelle, gemessen bei x=1077 statt
 * x=940), aber es ist jetzt eine Plakette, die Aufmerksamkeit will und keine
 * bekommt. Steht als Fehlerfund 2 im Konzept.
 *
 * 19.4.1 — DIE KOPFZEILE STEHT DA, WO GELESEN WIRD (Backlog Nr. 182).
 *
 * Der Fehlerfund aus 19.4.0, behoben. Die Kopfzeile einer Tagesgruppe sitzt
 * in einer Tabellenzelle, die so breit ist wie die ganze Vorschautabelle —
 * gemessen 2653 px gegen 342 px Sichtfenster am Handy. `flex-wrap` griff
 * deshalb nie, und alles hinter dem Datum stand ausserhalb des
 * Sichtfensters: Besatzung, die orange Plakette „abweichende Crew" und das
 * Auswahlfeld daneben. In KEINER der gemessenen Breiten war die Plakette zu
 * sehen, ohne waagerecht zu scrollen — eine Plakette, die Aufmerksamkeit
 * will und keine bekommt.
 *
 * DIE LOESUNG BRAUCHT KEIN JAVASCRIPT, und das ist die Pointe. Der
 * Rollbereich der Vorschau wird ueber `container-type:inline-size` zum
 * Groessencontainer; `100cqi` ist damit die SICHTBARE Breite statt der
 * Tabellenbreite. Der Inhalt der Kopfzeile heftet sich mit
 * `position:sticky; left:0` an den linken Rand und bleibt stehen, waehrend
 * die Datenzeilen darunter durchlaufen. Vier Deklarationen im Stylesheet,
 * ein Klassenname in `import.php`, null Zeilen Skript.
 *
 * DER UMWEG GEHOERT ZUR SACHE. Zur Freigabe standen drei Wege (M-MR-05,
 * F-MR-14): so lassen, heften, oder je Gruppe eine eigene Tabelle. Der
 * dritte war zuerst gewaehlt und ist nach der Kartierung verworfen worden —
 * 22 Befunde der Art „bricht", darunter vier, die LAUTLOS scheitern: der
 * delegierte Ereignisbehandler haengt an `$('tabelle')` und haette beim
 * Seitenstart die ganze Importseite mitgerissen; das Auswahlfeld der
 * Tageswahl waere aus dem Tabellenbaum gefallen und sein Scheitern erst
 * NACH dem Import in den Daten sichtbar geworden; mehrere `id="tabelle"`
 * haetten nur die erste Gruppe bedienbar gelassen; und `.imp-daygroup td`
 * haette nichts mehr getroffen, womit der Kopf auf den Zustand vor 19.4.0
 * zurueckgefallen waere. Dazu haette der Weg die Spaltenflucht ueber die
 * Gruppen gebrochen — eine Bedingung, die in der Abnahme von Nr. 182 steht.
 *
 * KORREKTURSTUFE, KEINE NEBENSTUFE: Es ist dieselbe Darstellung an
 * derselben Stelle; sie ist jetzt zu sehen. Uhr und Android unberuehrt,
 * keine Migration.
 *
 * 19.4.2 — MOCKUP-RUNDE 9c, AP2: ZWEI ZEICHEN WERDEN SYMBOLE (Nr. 42).
 *
 * Der Entfernen-Knopf im Chip und das Warnzeichen im Satz einer Meldung
 * standen als Unicode-Zeichen im Markup. Beide sind jetzt Symbole aus dem
 * Vorrat — und der Knopf hat ein Treffziel, das diesen Namen verdient.
 *
 * DER CHIP. `.rmx` trug ein Malzeichen als TEXT, und das Ziel war so gross
 * wie das Zeichen: gemessen 17 x 15 px. Auf einem Handy mit Handschuhen ist
 * das kein Bedienelement, und ein Fehlgriff loescht eine Koordinate oder ein
 * Rettungsmittel. Jetzt: `schliessen` in 12 px, zentriert in einem 28-px-Ziel
 * (M-MR-02 Variante C, F-MR-6b), 6 px zum Text und 6 px zum Chiprand
 * (E-MR-18). Das Ziel liegt als Pseudoelement UEBER dem Symbol — ein
 * groesserer Knopf haette den Chip hoeher gemacht; so bleibt er bei seinen
 * 28,1 px und das Ziel ragt unsichtbar darueber hinaus.
 *
 * BEIDE CHIPS, EINE REGEL (E-MR-11). Koordinaten (`ortsfeld.js`) und
 * beteiligte Rettungsmittel (`einsatz_form.php`) setzten dasselbe Zeichen auf
 * ZWEI Arten — einmal als Zeichen, einmal als JavaScript-Escape. Die zweite
 * hat die Vollstaendigkeitspruefung nie gesehen (Fehlerfund 1 des Konzepts);
 * sie sieht Escape-Folgen jetzt.
 *
 * DAS WARNZEICHEN IM SATZ. `patient.js` trug das Zeichen als Konstante und
 * setzte den Satz mit `textContent`. Es sah in jedem System anders aus, nahm
 * die Schriftfarbe der Meldung nicht an und war etwas anderes als die Marke,
 * die dieselbe Sache in der Tabelle daneben traegt. Jetzt dasselbe Symbol,
 * ueber die neue Klasse `.symbol-text` so gross wie die Schrift (`1em`) und
 * auf der Grundlinie; die Farbe kommt aus `.meldung-warn .symbol`.
 *
 * EINE AUSNAHME WENIGER, NICHT EINE MEHR. Das Konzept sah vor, den
 * Zeichen-Rueckfall in `wegKnopf()` als begruendete Ausnahme stehen zu
 * lassen. Der Vermerk daneben („symbol.js laedt erst am Seitenende") war
 * falsch: `symbol.js` kommt aus `ui_geruest_ende()` und damit als erstes
 * Skript der Seite. Nachgemessen am laufenden Formular — `typeof edSymbol`
 * ist `function`, alle acht Entfernen-Knoepfe tragen ein SVG, keiner das
 * Zeichen. Der Zweig war seit seiner Entstehung tot und ist fort; damit
 * braucht Nr. 42 UEBERHAUPT keine Ausnahme.
 *
 * NEU: drei abgeleitete Token (`--symbol-winzig` 12 px, `--ziel-chip` 28 px,
 * `--symbol-text` 1em) und die Klasse `.symbol-text`. Kein neuer Farbwert,
 * kein neues Symbol — `schliessen` und `warnung` lagen im Vorrat.
 *
 * Uhr und Android unberuehrt, keine Migration.
 *
 * 19.5.0 — MOCKUP-RUNDE 9c, AP3: DIE DRITTE KARTENGROESSE (Nr. 45).
 *
 * Die Karte der Tagesuebersicht hatte zwei Zustaende: ihre Hoehe nach
 * Fensterbreite (160 / 220 / 300 px) und Vollbild. Dazwischen lag nichts —
 * wer mehr von der Spur sehen wollte, musste die Seite verlassen und
 * wiederkommen. Jetzt liegt dazwischen ein Knopf.
 *
 * EIN ZUSTAND, ZWEI WIRKUNGEN JE BREITE (E-MR-16, F-MR-9 geaendert). Bis
 * 1599 px wird die Karte HOEHER — `--karte-gross`, also min(60vh, 520px).
 * Ab 1600 px steht sie ohnehin in einer eigenen Spalte und ist dort schon
 * hoch; dort wird sie stattdessen BREIT: Das Raster faellt auf eine Spalte,
 * und die Karte rueckt zwischen Diensttag-Daten und Einsatzliste — dorthin,
 * wo sie unter 1600 px immer steht. Beides traegt dieselbe Klasse
 * `.geo-gross`; WELCHE Wirkung sie hat, entscheidet das Stylesheet. Die
 * Schwelle 1600 steht damit weiterhin an genau einer Stelle.
 *
 * DER KNOPF TRAEGT BEIDE ZEICHEN (E-MR-19) — senkrechte Pfeile bis 1599 px,
 * Querpfeile darueber; das Stylesheet blendet je Breite eines aus. Ein
 * Knopf, der sein Symbol per JavaScript tauscht, haette die Schwelle ein
 * zweites Mal im Code.
 *
 * NUR AUF DER TAGESUEBERSICHT. `attachGroessenControl()` wird ausdruecklich
 * einzeln gerufen; Einsatzansicht, Spurenseite und Zeitraumuebersicht haben
 * keine Liste unter der Karte, die vom Hoeherwerden etwas haette.
 *
 * DER ZUSTAND WIRD GEMERKT (F-MR-8), je Browser und Geraet, nicht je Konto:
 * Wer am Schreibtisch gross arbeitet, will das am Handy nicht zwangslaeufig.
 * Das ist der erste `localStorage` dieser Anwendung; er kann werfen und leer
 * zurueckkommen, und beides ist abgefangen — dann steht die Karte eben klein
 * da.
 *
 * NEU: Token `--karte-gross`, Klasse `.geo-gross`, zwei Symbole
 * (`karte-gross.svg`, `karte-breit.svg`; 54. und 55. des Vorrats). Kein
 * neuer Farbwert.
 *
 * NEBENSTUFE, KEINE KORREKTUR: Es ist eine neue Funktion an einer Stelle,
 * an der bisher keine war. Uhr und Android unberuehrt, keine Migration.
 *
 * 19.5.1 — EIN AUSWAHLFELD SCHOB DIE IMPORTSEITE ZUR SEITE (Nr. 185).
 *
 * Gefunden vom ersten dreifachen Bilderlauf (AP3b, Backlog Nr. 183):
 * `import.php` bei 360 px lief NUR IN WEBKIT um 6 px ueber — scrollWidth 366
 * gegen innerWidth 360, waehrend Chromium und Firefox 360 meldeten.
 *
 * DER BERICHT NANNTE KEINEN VERURSACHER, UND DAS WAR RICHTIG. Kein Element
 * der Seite ragte hinaus; das Auswahlfeld ist 302 px breit und endet bei 331.
 * Uebergelaufen ist sein INHALT: WebKit rechnet den laengsten Eintrag eines
 * `<select>` in den Ueberlauf des Kastens mit, auch wenn der Kasten ihn
 * abschneidet. Nachgewiesen durch Kuerzen — alle Eintragstexte auf „x"
 * gesetzt, und es waren 360; zurueckgesetzt, und es waren wieder 366. Der
 * laengste Eintrag hat 53 Zeichen; die drei anderen Auswahlfelder derselben
 * Seite haben 17, 17 und 30 und laufen nicht ueber. Ab 390 px verschwindet es.
 *
 * BEHOBEN MIT `select.feld-eingabe{contain:paint}` — der einzigen der vier
 * versuchten Regeln, die wirkt: `overflow:clip` am Feld half nicht (gemessen
 * 366), `max-width:100%` ebenso wenig, `appearance:none` nur zur Haelfte
 * (361). Was die Malbegrenzung kostet, ist nachgemessen: der fokussierte
 * Ausschnitt (318 x 60 px) vor und nach der Regel ist in Firefox bitgleich,
 * in Chromium und WebKit EIN Pixel verschieden bei einer Abweichung von 6 von
 * 255 — die Rundung des Fokusrings. Der Ring selbst bleibt stehen;
 * Malbegrenzung schneidet Inhalt, nicht Umriss.
 *
 * KORREKTURSTUFE: eine Regel, eine Datei, keine neue Funktion. Uhr und
 * Android unberuehrt, keine Migration.
 *
 * 19.6.0 — MOCKUP-RUNDE 9c, AP4: DAS BLATT FAEHRT AUF (Nr. 124).
 *
 * Das Aktionsblatt stand am Handy einfach da, und der Knopf, aus dem es kam,
 * verschwand darunter. Zweierlei aendert sich, und beides beantwortet
 * dieselbe Frage — „wo bin ich gerade?".
 *
 * ES FAEHRT AUF. Im Ruhezustand steht das Blatt um seine eigene Hoehe unter
 * dem Bildrand (`translateY(100%)`), `.blatt-auf` holt es herauf, in
 * `--dauer` mit `ease-out`. Ohne Bewegung las sich das Erscheinen wie ein
 * Seitenwechsel: Die halbe Flaeche war ploetzlich eine andere, und niemand
 * wusste, woher sie kam.
 *
 * AM SCHREIBTISCH NICHT. Ab 1024 px ist dasselbe Markup ein Aufklappmenue
 * unter dem Knopf; `translateY(100%)` hiesse dort „um die eigene Hoehe nach
 * unten" und schoebe es neben die Sache. Der 1024er Block setzt deshalb
 * `transform:none; transition:none` — und `blatt.js` fragt die GERECHNETE
 * Fahrtdauer, statt eine Zahl zu kennen: Ist sie ~0, geht `hidden` sofort.
 * Ohne diese Frage stuende das Aufklappmenue eine Viertelsekunde zu lange
 * offen.
 *
 * DER OFFENE OEFFNER IST MARKIERT (E-MR-21, Fassung D4): `--orange-hell` als
 * Flaeche, `--orange-tief` darauf — 11,7:1 gegen den dunkelblauen Kartenkopf,
 * 3,8:1 fuer die Punkte gegen die Flaeche. Vier Ringfassungen standen zur
 * Wahl und ueberzeugten nicht; der Ring auf Blau-hell lag bei 1,9:1.
 * Die Regel haengt am ATTRIBUT `[data-blatt][aria-expanded="true"]` und
 * erreicht damit alle vier Bauarten von Oeffnern: 6 `ui_aktionen()`, 9
 * `ui_zeilenaktionen()`, den Pin-Knopf des Ortsfelds und drei
 * handgeschriebene Sortierblatt-Knoepfe in index.php, suche.php und
 * zeitraum.php (E-MR-25). Eine Regel je Bauart waere vier Stellen gewesen,
 * die auseinanderlaufen koennen.
 *
 * `--dauer` STEHT JETZT AUF 240 ms, VORHER 180 — fuer die ganze Anwendung
 * (E-MR-22, F-MR-12), also auch fuer Schublade, Schleier, Akkordeon-Winkel,
 * Schalter-Griff und Kennzahlen-Winkel. Bei 180 ms war die Auffahrt eher ein
 * Aufblitzen als eine Bewegung. Ein zweites Token nur fuers Blatt waere die
 * Stelle, an der die Anwendung anfaengt, verschieden schnell zu sein.
 *
 * NEU: Klasse `.blatt-auf`. Kein neues Token, kein neues Symbol, kein neuer
 * Farbwert — das Kontrastpaar „Orange tief auf Orange hell" war schon
 * gerechnet.
 *
 * NEBENSTUFE: eine neue Darstellung an einer Stelle, an der bisher keine war.
 * Uhr und Android unberuehrt, keine Migration.
 *
 * 19.7.0 IST DER SERVERTEIL VON S10 — UND EINE STUFE, DIE NICHTS TUT. Schritt
 * 9b (R78) gibt der Installation ein ZWEITES Geheimnis neben dem
 * Serverschluessel: den SERVER-ANTEIL (`kdf_anteil` in config.php). Er geht
 * per HKDF in den Datenschluessel jedes Kontos ein, mit dem der Browser die
 * Schluesselhuelle oeffnet. Der Server kann damit weiterhin nichts oeffnen —
 * er kennt den Anteil, nicht die PBKDF2-Haelfte aus dem Passwort. Was sich
 * aendert, ist die Rechnung des Angreifers: Wer nur die Datenbank hat, hat
 * seit S10 nicht mehr alles, was er zum Durchprobieren braucht (Krypto-Review
 * K-3, Weg 1).
 *
 * DIESE STUFE BAUT NUR DIE GRUNDLAGE. `serverkrypto_lib.php` kann den Anteil
 * lesen, je Konto per HMAC ableiten, seine Kennung rechnen und die fuenf
 * Lagen aus E-S10-09 unterscheiden; `auth_guard.php` und
 * `ui_krypto_bootstrap()` liefern ihn an die angemeldete Sitzung;
 * `api/kdf_upgrade.php` nimmt eine Huelle mit Anteil an. NUR: Es gibt noch
 * keinen Browser, der ihn benutzt. Jede Huelle bleibt `edk1:`, kein Weg durch
 * die Anwendung aendert sich, und eine frisch eingerichtete Installation
 * verhaelt sich Zeile fuer Zeile wie unter 19.6.0.
 *
 * DESHALB NEBEN- UND NICHT HAUPTNUMMER, obwohl S10 als Ganzes eine
 * Hauptstufe ist (E-S10-16). Die Zaehlweise oben misst, was sich fuer die
 * BENUTZUNG aendert — „spuerbar veraenderte Wege durch die Anwendung". Nach
 * dieser Stufe ist das nichts. Die 20.0.0 gehoert an das naechste Paket, in
 * dem der Datenschluessel tatsaechlich am Anteil haengt und jede Huelle ihr
 * Format wechselt; eine 20.0.0 hier verspraeche einen Umbau, den erst der
 * naechste Commit vollzieht (E-S10-U-01).
 *
 * KEINE SCHEMAAENDERUNG, KEINE MIGRATION. Die zwei neuen Marken
 * (`kdf_anteil_kennung`, `server_key_kennung`) liegen in `app_state`, und die
 * Tabelle steht seit der Wartungs-Migration vom 17.07.2026. `update.php` muss
 * nach dem Merge NICHT laufen.
 *
 * WAS EINE BESTEHENDE INSTALLATION NACH DEM DEPLOY TUN MUSS: nichts — und
 * genau das ist der Punkt. Ohne `kdf_anteil` in `config.php` meldet
 * `anteil_zustand()` „nicht eingerichtet", es wird nichts ausgeliefert, und
 * alles laeuft wie vorher. Der Anteil entsteht erst, wenn ihn jemand anlegt;
 * die Karte dafuer kommt mit AP3, der Installer legt ihn ab sofort mit an.
 *
 * 20.0.0 IST DIE HAUPTNUMMER VON S10 — HIER HAENGT DER DATENSCHLUESSEL
 * TATSAECHLICH AM SERVER-ANTEIL. Die erste Haelfte der PBKDF2-Ableitung ist
 * nicht mehr selbst der Datenschluessel; zwischen ihr und ihm steht
 * HKDF-SHA256 mit dem Anteil dieses Kontos. Jede Schluesselhuelle wechselt
 * dabei ihr Format von `edk1:` auf `edka1:<kennung>:`, und zwar STILL beim
 * naechsten Anmelden — niemand gibt etwas ein, niemand sieht einen Dialog.
 *
 * DAS IST DER UMBAU, FUER DEN DIE HAUPTNUMMER DA IST. Nicht wegen des
 * Datenmodells — es bleibt unangetastet, und eine Migration gibt es NICHT —,
 * sondern weil sich die Schluesselkette der Anwendung aendert. Wer die
 * Datenbank hat, hatte bis 19.7.0 alles, was er zum Durchprobieren eines
 * Passworts braucht. Ab 20.0.0 nicht mehr.
 *
 * WAS SICH IM BROWSER AENDERT. `EdCrypto` bekommt drei Funktionen
 * (`datenschluessel()`, `huelleOeffnen()`, `huelleBauen()`), und die fuenf
 * Stellen, die bisher `deriveKeys().dataKeyHex` + `decrypt()` riefen, gehen
 * ueber sie: Anmeldung, Entsperrdialog, Passwortwechsel, Export-Passwortprobe
 * und Reset. `deriveKeys()` liefert kein `dataKeyHex` mehr, sondern
 * `haelfteHex` — der Name ist mitgewandert, weil ein Feld, das „dataKey"
 * heisst und keiner ist, beim naechsten Mal wieder als einer benutzt wird.
 *
 * `login.php` SETZT DEN DATENSCHLUESSEL NICHT MEHR SELBST, auch nicht bei
 * einer einzigen Rundenzahl. Ihm fehlt seit S10 eine zweite Angabe: Ob die
 * Huelle dieses Kontos den Anteil braucht, steht in IHREM Praefix — und die
 * kennt erst die angemeldete Seite. Das Vormerkfach liegt deshalb nach jeder
 * Anmeldung einen Seitenwechsel lang im sessionStorage statt gar nicht.
 *
 * DREI FUNDE AUS DEM GEGENLESEN DES KONZEPTS SIND HIER MIT BEHOBEN, und alle
 * drei waeren teuer geworden:
 *   F-1  `EdCrypto.getContentKey()` rief `decrypt()` unmittelbar — das wirft
 *        an jeder `edka1:`-Huelle. Ueber `EdKeyGuard.contentKey()` haette das
 *        JEDE Anzeigeseite gesperrt, und zwar erst beim ZWEITEN Seitenaufbau
 *        (der erste bekommt den Schluessel aus dem Vormerkfach).
 *   F-2  `WRAP_RE` prueft beide Huellen mit EINER Regel. Seit 19.7.0 nahm sie
 *        `edka1:` an — auch fuer `pat_wrap_rc`, das nie daran haengen darf.
 *        Jetzt zwei Ausdruecke: `WRAP_PW_RE` und `WRAP_RC_RE`.
 *   F-3  Die Kennungspruefung sass an EINEM von VIER Schreibwegen fuer
 *        `pat_wrap_pw`. Jetzt an allen vier, ueber eine Funktion
 *        (`huelle_pw_pruefen()`).
 *
 * GEMESSEN, NICHT GESCHAETZT: Die zusaetzliche HKDF-Ableitung kostet im
 * Browser **unter 0,12 ms** (500 Ableitungen am Stueck, drei Engines; die
 * Zahlen stehen im Changelog). Sie laeuft einmal je Anmeldung.
 *
 * KEINE SCHEMAAENDERUNG, KEINE MIGRATION — dieselbe Lage wie bei 19.7.0.
 * Eine Installation ohne `kdf_anteil` verhaelt sich weiterhin wie vor S10;
 * die Umstellung beginnt erst, wenn der Anteil angelegt wird.
 *
 * 20.1.0 GIBT DEM ANTEIL EINE BEDIENUNG — UND EINEN ZWEITEN ORT. Bis 20.0.0
 * war der Server-Anteil eine Zeile in `config.php`, die nur ein Mensch mit
 * Dateizugang anlegen konnte, und niemand sah ihr an, ob sie die richtige
 * war. Die Nebennummer traegt drei Dinge nach:
 *
 *   1. DIE KARTE „Schluessel des Servers" unter Betrieb -> Servereinstellungen
 *      fuehrt beide Geheimnisse an einer Stelle: anlegen, wechseln, alten
 *      Anteil entfernen, nachtragen, Neuanfang. Sie ZEIGT DEN WERT NICHT —
 *      sie nennt nur seine Kennung, die ersten acht Hexzeichen des SHA-256
 *      ueber den Wert. Damit laesst sich vergleichen, ohne vorzulesen.
 *   2. DAS SCHLUESSELBLATT (`betrieb_schluesselblatt.php`) ist die eine
 *      Seite, deren Zweck der Ausdruck ist. `config.php` traegt seit S10 die
 *      Schluessel der ganzen Installation, und ein zweiter Ort dafuer muss
 *      ueberleben, was die Datei nicht ueberlebt. Papier tut das.
 *   3. FUENF ZUSTAENDE statt „da oder nicht da": nicht eingerichtet, bereit,
 *      Rotation, abweichend, Neuanfang. Der interessante ist `abweichend` —
 *      `config.php` traegt einen anderen Wert, als die Huellen verlangen. Er
 *      entsteht nicht nur beim Verlieren der Datei, sondern PLANMAESSIG nach
 *      einem Komplett-Backup: Das Paket stellt `app_state` wieder her,
 *      `config.php` gehoert nicht dazu.
 *
 * NACHTRAGEN SCHREIBT NUR BEI UEBEREINSTIMMUNG. Der Server rechnet die
 * Kennung des eingegebenen Werts und vergleicht sie mit der erwarteten; passt
 * sie nicht, wird NICHTS geschrieben und die Meldung nennt beide Kennungen.
 * Ein falsch abgetippter Wert, der stillschweigend landet, macht aus einer
 * behebbaren Lage eine unbehebbare — er ueberschreibt den einzigen Ort, an
 * dem der richtige noch stehen koennte.
 *
 * DAS ERSTE @media print DES PROJEKTS. Drei Regeln, und sie gelten nur fuer
 * das Blatt: Bildschirmknoepfe fort, keine Flaechenfarbe, kein Seitenumbruch
 * mitten im Wert. Ein Druck-Stylesheet, das jede Seite umgestaltet, waere
 * eine zweite Oberflaeche mit eigenen Fehlern.
 *
 * KEINE SCHEMAAENDERUNG, KEINE MIGRATION. `app_state` bekommt zwei Marken
 * (`kdf_anteil_kennung`, `server_key_kennung`) — die Tabelle gibt es seit
 * langem, und beide entstehen beim ersten Anlegen von selbst.
 *
 * 20.2.0 VERSIEGELT DIE ADMINPAKETE UND SCHAFFT `ftp` AB (S10/AP4). Zwei
 * Dinge, die nichts miteinander zu tun haben ausser dem Ort, an dem sie
 * wehtun: Ein Adminpaket geht per Versand an fremde Gegenstellen — bis
 * hierher als blankes JSON, und bei einem `ftp`-Ziel auch noch ueber eine
 * offene Leitung.
 *
 * FASSUNG 3 DES ADMINPAKETS. Jeder Teil im ZIP ist gzip-gepackt und mit dem
 * SERVERSCHLUESSEL versiegelt (`edsk1:`), das Manifest eingeschlossen — und
 * die Begleitdatei `konto.json` daneben ebenso, denn sie trug E-Mail und
 * Namen im Klartext. Ohne sie haette die Abnahmezahl „kein lesbarer Name,
 * keine E-Mail" nur fuer das ZIP gegolten und nicht fuer den Ordner.
 *
 * DAS IST KEINE ENDE-ZU-ENDE-VERSCHLUESSELUNG. Der Server kann das Siegel
 * oeffnen — er haelt den Schluessel. Was es verhindert, ist der Zugriff OHNE
 * den Server: ein kopiertes Backup, ein mitgelesener Versand, ein Blick in
 * den Ablageordner. `pat_blob` bleibt davon unberuehrt Ende-zu-Ende
 * verschluesselt; das Siegel liegt darueber.
 *
 * DER SIEGELZWECK BINDET DEN PAKETNAMEN (E-S10-U-11). Ohne ihn liesse sich
 * ein Teil aus einem aelteren Paket desselben Kontos unterschieben — das
 * Manifest fuehrt nur Namen, keine Pruefsummen je Teil. Der Preis steht in
 * `docs/Backup-Format.md`: Wer ein Paket umbenennt, macht es unlesbar.
 *
 * GZIP VOR DEM SIEGEL, und die Zahl sagt warum (E-S10-U-12). Versiegelte
 * Teile sind Zufallsrauschen; das ZIP kann sie nicht mehr packen. Gemessen
 * am Referenzkonto (83 Einsaetze, 150 690 Byte Klartext): Fassung 2
 * **33 281** Byte, Siegel ohne Vorstufe **201 390** (+505 %), gzip davor
 * **45 290** (+36 %). Die verbleibenden 36 % sind der base64-Rahmen von
 * `edsk1:` — das Format der Versiegelung, nicht der Packlauf.
 *
 * OHNE SERVERSCHLUESSEL ENTSTEHT KEIN PAKET. Derselbe Riegel wie beim
 * Komplett-Backup: Die Wahl zwischen einem unversiegelten Paket und einem
 * Abbruch mitten im Bau ist keine.
 *
 * `ftp` IST FORT (E-S10-14). Nicht mehr waehlbar, nicht mehr speicherbar,
 * nicht mehr beschickt. Drei Stellen, und die mittlere war der Fund:
 * `sz_pruefen_eingabe()` prueft gegen `SZ_PORTS`, nicht gegen
 * `SZ_PROTOKOLLE` — wer nur aus dem Anzeigekatalog gestrichen haette, haette
 * gar nichts abgeschafft. Beide Listen fuehren jetzt dieselben Schluessel,
 * und `sz_protokoll_erlaubt()` ist die eine Frage.
 *
 * DER ENGPASS PRUEFT POSITIV (E-S10-U-10). `sz_weg()` hatte genau einen
 * benannten Zweig (`sftp`); alles andere fiel in `ZielFtp`, wo
 * `$prot === 'ftps'` ueber TLS entscheidet. FTPS war damit geschuetzt — ein
 * UNBEKANNTES oder LEERES Protokoll aber fiel still auf Klartext-FTP zurueck.
 * Geprueft wird jetzt gegen den Katalog, nicht auf `!== 'ftp'`.
 *
 * UEBERGANGEN STATT GESCHEITERT. Ein Altziel wird uebersprungen und bekommt
 * einen Vermerk — nicht einen Eintrag in `fehler`, denn `jobs_lib.php` wirft
 * darauf, und der Versandjob staende dauerhaft rot. Auf der Jobebene heisst
 * die Zahl `uebergangen` und nicht `uebersprungen`: Letzteres ist dort schon
 * belegt, und `jobs.php` ueberspringt bei diesem Schluessel die GANZE
 * Ergebniszeile.
 *
 * KEINE SCHEMAAENDERUNG, KEINE MIGRATION. Das `ENUM` behaelt `ftp` — ein
 * bestehendes Ziel bleibt lesbar, sichtbar und umstellbar. Der Rueckbau der
 * Spalte gehoert zum ENUM-Aufraeumen (Backlog Nr. 168 / Nr. 46).
 */
/* ---------------------------------------------------------------------------
 * 20.2.1 — DER ZWEITE RIEGEL AN DER DEMO-FIXTURE (S10/AP5)
 *
 * Eine Korrekturstufe mit fuenfzehn Zeilen und einem Gedanken: Ein Riegel,
 * der nur dort greift, wo das Werkzeug laeuft, greift nicht dort, wo die
 * Datei ankommt.
 *
 * S10/AP5 hat `tools/referenzdatensatz/fixture/erzeugen.php` beigebracht,
 * anzuhalten, wenn die Schluesselhuelle des Demo-Kontos nicht `edk1:` traegt.
 * Das verhindert, dass eine unbrauchbare Fixture ENTSTEHT. Es verhindert
 * nicht, dass eine eingespielt wird: Der Erzeuger laeuft auf der
 * Referenzmaschine, die Datei geht mit dem Deploy auf den Produktivserver.
 *
 * `demo_fixture_laden()` prueft deshalb jetzt beide Huellen — mit der
 * gemeinsamen Pruefschicht (`huelle_pw_pruefen($wrap, istDemo: true)` und
 * `huelle_rc_pruefen()`), nicht mit einem eigenen Ausdruck.
 *
 * WARUM DAS NOETIG IST. Seit S10 haengt der Datenschluessel am Server-Anteil,
 * und der ist je Installation ein anderer. Das Demo-Konto bekommt
 * bauartbedingt GAR KEINEN (E-P1-19/E-S10-06). Eine `edka1:`-Huelle in der
 * Fixture hiesse: Das Konto meldet sich an — der bcrypt-Hash stimmt ja —, und
 * erst das Entsperren scheitert. Auf der oeffentlichen Demo, alle 30 Minuten
 * aufs Neue, ohne dass jemand etwas bemerkt.
 *
 * DIESELBE PAARUNG WIE BEI DER RUNDENZAHL (Backlog Nr. 155): dort ein Riegel
 * im Erzeuger und einer in `demo_fixture_laden()`, mit derselben Begruendung
 * — „Ohne den zweiten Riegel waere ein Reset still erfolgreich und niemand
 * kaeme mehr herein."
 *
 * WAS EIN ABBRUCH KOSTET, und warum er trotzdem richtig ist:
 * `demo_reset_wenn_faellig()` faengt jede Ausnahme ab und schreibt ins
 * `error_log`. Eine verbogene Fixture laesst das Demo-Konto also aufhoeren,
 * sich zurueckzusetzen — es geht nichts verloren, und niemand wird
 * ausgesperrt. `demo_anlegen()` dagegen laesst die Ausnahme durch: Wer das
 * Konto von Hand anlegt, soll den Grund lesen.
 *
 * Gemessen: `tools/referenzdatensatz/fixture/riegelprobe.php` **10 von 10** —
 * beide Riegel in beide Richtungen, der abgefangene Reset (Demo-Konto 88 ->
 * 88 Einsaetze), und am Ende die Pruefsumme der echten Fixture
 * vorher/nachher.
 */
/* ---------------------------------------------------------------------------
 * 20.3.0 — FAEHIGKEITEN BEI TYP BERGWACHT, IN BEIDEN BETRIEBSARTEN
 *          (Demo-Ausbau, AP0)
 *
 * Eine Nebenstufe mit einer Regel und drei Kommentaren, die diese Regel bisher
 * als Herleitung fuehrten.
 *
 * WAS GALT. E29: Faehigkeiten (Winde, Bergwacht) kommen ausschliesslich an
 * luftgebundenen Rettungsmitteln vor. `pruef_rettungsmittel()` pruefte
 * `$kind === 'air'`, `db.php` begruendete daneben, warum VEHICLE_TYPEN dafuer
 * keine eigene Spalte brauche: Bei 'veranstaltung' folge „keine" schon aus der
 * festen Betriebsart, bei den uebrigen aus E29.
 *
 * WAS NICHT GALT. Ein Bergwachtnotarzt FAEHRT zum Einsatz und wird von dort
 * GEFLOGEN. Er braucht die Winde, und seine Betriebsart ist Boden. Die
 * Kopplung von Winde und Luft war eine Regel ueber Hubschrauber, nicht ueber
 * Bergwacht — und sie war die EINZIGE Stelle, die beides aneinanderband: Die
 * Einsatzfelder haengen laengst an der FAEHIGKEIT des Diensttags
 * (`cap_gate`), nicht an seiner Art.
 *
 * WAS SICH AENDERT. VEHICLE_TYPEN bekommt die Spalte `faehigkeiten`
 * ('luft' | 'immer'), `veh_caps_erlaubt()` wertet sie aus, und die
 * Pruefschicht fragt nur noch diese eine Funktion. Der Typ Bergwacht steht
 * auf 'immer', die drei anderen auf 'luft' — fuer 'veranstaltung' bleibt die
 * alte Herleitung gueltig (fest bodengebunden, also nie Faehigkeiten), und die
 * Spalte sagt deshalb nur, was NICHT schon aus der Betriebsart folgt.
 *
 * ZWEI LUECKEN FALLEN NEBENBEI ZU, und beide standen nicht im Auftrag.
 * Das Skript des Stammdatendialogs fuehrte mit `regel.rollen && kind === 'air'`
 * eine DRITTE Fassung der Regel — enger als der Server. Ein Rettungsmittel des
 * Typs Bergwacht oder Sonstiges mit Betriebsart LUFT durfte Faehigkeiten
 * fuehren (der Server nahm sie an), bekam die Haekchen aber nie zu sehen: Die
 * Zeile war an `regel.rollen` gehaengt, und das hat ausser 'standard' keiner.
 * Sichtbar wurde es erst, als jemand dieselbe Regel an zwei Stellen nebeneinander
 * las. Jetzt liest das Skript dieselbe Tabelle wie die Pruefschicht.
 *
 * Die zweite: Die Karte BERGWACHT-BEREITSCHAFTEN auf der Standortseite
 * erschien nur, wenn dort ein luftgebundenes Rettungsmittel stand. Eine
 * Bergwachtstation mit einem bodengebundenen Notarzt haette danach ein Feld
 * `bergwacht` im Einsatz gehabt und keinen Ort, an dem sich Bereitschaften
 * anlegen lassen — der Schreibweg legt sie naemlich trotzdem an. Gefragt wird
 * jetzt `veh_caps_erlaubt()`, also: Darf hier ueberhaupt jemand die Faehigkeit
 * fuehren? (Dieser Absatz hat bis zum 15.09.2026 gefehlt, waehrend die Zeile
 * darueber ZWEI Luecken ankuendigte — nachgetragen beim Gegenlesen.)
 *
 * WAS BEWUSST STEHEN BLEIBT. `api/range.php` zaehlt die Faehigkeiten des
 * Zeitraums weiter nur ueber `d.kind = 'air'`, und die beiden Windenkacheln
 * gibt es nur im Luft-Kachelsatz. Ein bodengebundener Bergwacht-Diensttag
 * zeigt seine Windenfelder also im EINSATZFORMULAR, wird in der
 * Zeitraumuebersicht aber nicht als Windendienst gezaehlt. Das aendern hiesse
 * zehn Kacheln in vier Spalten — eine Gestaltungsentscheidung, die eine
 * Freigabe mit Mockup braucht (Backlog Nr. 198). Der Kommentar an der Abfrage
 * sagt es jetzt, statt sich weiter auf E29 zu berufen.
 *
 * ZWEI KOMMENTARE BERICHTIGT (Backlog Nr. 189, beide Haelften). Erstens der
 * tote Konzeptpfad in `schema.sql` und `migration_lib.php` — dieses Paket ist
 * das naechste unter `server/`, auf das der Punkt ausdruecklich gewartet hat.
 * Zweitens, beim Zusammenfuehren mit `main` uebernommen: Der Kommentar an
 * `geraet_modell` nannte 156 Zeichen. Nachgemessen an `GERAETE_MODELLE` sind
 * es 153; die 156 war bis Web 12.9.2 richtig und ist mit dem Streichen der
 * Marken- und Schutzrechtszeichen ueberholt worden. Berichtigt sind die drei
 * LEBENDEN Stellen; die Protokollzeilen (Changelog 12.9.1/12.9.2, Rahmenplan
 * Fassung 19) bleiben, weil sie beschreiben, was damals galt.
 *
 * KEINE SCHEMAAENDERUNG, KEINE MIGRATION. `vehicle_capabilities` und
 * `day_capabilities` fuehren keine Art; sie konnten den Fall immer schon
 * tragen. `update.php` muss nach dem Deploy NICHT laufen.
 *
 * 20.4.0 ist das erste Paket von P5a (AP1): DIE AUSLIEFERUNGSKETTE.
 *
 * WAS BIS HIERHER GALT und in `CLAUDE.md` 3 wortwoertlich stand: „Ein Push
 * auf `main` mit Aenderungen unter `server/` laedt sofort auf den
 * Produktivserver hoch. Es gibt keine Zwischenstufe und keine Testumgebung."
 * Kein Prueftor, kein Freigabeschritt, kein Rueckweg — und kein Backup, von
 * dem jemand wuesste, dass es zu diesem Stand gehoert.
 *
 * WAS GILT: Push auf `main` geht nach STAGING, ein Tag `web-vX.Y.Z` geht nach
 * PRODUKTIV, und davor stehen drei Tore — Stufe 1 (jeder Push, ohne
 * Installation), Stufe 2 (gegen Staging) und die Pflichtfreigabe der
 * Betreiberin. Vor dem Schreiben auf Produktiv laeuft das Komplett-Backup
 * nachweislich zu Ende.
 *
 * AM CODE AENDERT SICH EINE EINZIGE DATEI, und das ist der Punkt: `jobs.php`
 * nimmt am Token-Weg einen Parameter `aktion` (`komplett`, `wartung_an`,
 * `wartung_aus`, `zustand`). Die Kette braucht ihn, weil sie von aussen
 * genau vier Dinge tun koennen muss, fuer die es bisher nur einen Browser
 * gab. Alles Weitere liegt in `.github/workflows/` und `tools/` — die
 * Anwendung weiss weiterhin nicht, wie sie auf den Server gekommen ist
 * (PP-9, Muss).
 *
 * ZWEI BEDINGUNGEN AM BACKUP-TOR, NICHT EINE (E-P5a-12). `fertig` allein
 * genuegt nicht: Ein Backup, das schon gestern fertig wurde, meldet
 * ebenfalls `fertig` und schuetzt diesen Deploy nicht. Der juengste Stand
 * muss deshalb JUENGER SEIN ALS DER LAUFBEGINN. Und `aktion=komplett` legt
 * einen Auftrag an, wenn keiner steht — ohne das taete der Aufruf bei Plan
 * „Nur von Hand" nichts und meldete sofort `fertig`.
 *
 * KEINE SCHEMAAENDERUNG, KEINE MIGRATION. `update.php` muss nach dem Deploy
 * NICHT laufen.
 *
 * 20.5.0 ist AP2 von P5a: DAS PLATTFORMPROFIL.
 *
 * WAS BIS HIERHER GEPRUEFT WURDE: vier Erweiterungen (`zip`, `zlib`,
 * `openssl`, `mbstring`) im Einrichter, und sonst nichts. Keine PHP-Version,
 * kein `pdo_mysql`, keine Weblimits, keine Datenbankfassung, keine
 * Verbindungsgrenze, Schreibrechte nur per `is_writable()`. Eine Installation
 * auf PHP 8.0 fiel erst beim ersten Formular mit einem Fatal Error auf.
 *
 * WAS GILT: `plattform_lib.php` mit `plattform_pruefen()` — EINE Funktion,
 * ZWEI Leser. `install.php` fragt sie vor der Einrichtung, die Statusseite im
 * Betrieb. Zwei Listen liefen auseinander, und ein Hoster kann eine
 * PHP-Fassung jederzeit umstellen, ohne jemanden zu fragen.
 *
 * `ok` IST DREIWERTIG: erfuellt, nicht erfuellt, NICHT FESTSTELLBAR. Nur das
 * mittlere haelt die Einrichtung auf. Der freie Plattenplatz ist der Fall, auf
 * den es ankommt — `disk_free_space()` meldet auf geteiltem Webspace den
 * Datentraeger des HOSTS, nicht das Kontingent dieses Kontos. Wer nichts
 * gemessen hat, darf nichts behaupten.
 *
 * DIE WEICHE IN `install.php` IST EINE ZEILE UND EINE ZUSAGE. Sie prueft die
 * Fassung, bevor irgendetwas anderes laeuft — und sie nuetzt nur, solange die
 * Datei auf der alten Fassung noch UEBERSETZT werden kann. `install.php`
 * bleibt deshalb PHP-7-lesbar, und `tools/installweiche/` zaehlt das mit dem
 * Tokenizer nach — fuer `install.php` UND fuer `php_mindest.php`. Jene drei
 * Zeilen tragen die Zahl, weil `plattform_lib.php` (PHP-8-Code) sie der
 * Weiche nicht geben kann; ohne sie stuende die 8.2 zweimal da. Was die Weiche NICHT leisten kann, steht in ihrem Kopf:
 * `index.php` ist PHP-8-Code und wird ganz uebersetzt, bevor seine
 * Weiterleitung liefe.
 *
 * SCHREIBRECHTE MIT PROBEDATEI statt `is_writable()`. Jenes antwortet anhand
 * der Rechtebits und liegt falsch, sobald ACLs, `open_basedir` oder ein
 * schreibgeschuetztes Dateisystem im Spiel sind — auf geteiltem Webspace der
 * Regelfall.
 *
 * ZWEI KONTINGENTE STATT EINEM (E-P5a-11). `db_gb` tritt neben
 * `webspace_gb`, mit denselben Schwellen. Der Unterschied ist die Vorgabe:
 * Der Webspace hat keine (ein geratener Wert waere schlimmer als keiner), die
 * Datenbank hat 10 GB — das ist die Untergrenze Z2, die diese Anwendung
 * tragen MUSS, also eine Zusage des Projekts und keine Vermutung ueber den
 * Hoster.
 *
 * UND EIN NEBENBEFUND, DER KEIN KLEINER IST: `edbak_schwellen_melden()` —
 * die Warnung fuer die Speichergrenze der Backups, seit S8 vorhanden —
 * WURDE IM BETRIEB VON NIEMANDEM AUFGERUFEN. Nachgemessen am 15.09.2026:
 * `grep -rn "schwellen_melden" --include=*.php` findet die Definition und
 * einen Aufruf im Pruefwerkzeug, sonst nichts. Geschrieben, geprueft, tot —
 * dieselbe Klasse wie Backlog Nr. 89. Der Aufruf steht jetzt im taeglichen
 * Aufraeumjob, direkt hinter der Messung.
 *
 * KEINE SCHEMAAENDERUNG, KEINE MIGRATION. `db_gb` und
 * `speicher_schwellen_gemeldet` sind Zeilen in `app_state`, und die Tabelle
 * steht seit Web 1.1. `update.php` muss nach dem Deploy NICHT laufen.
 *
 * 20.6.0 ist AP3 von P5a: DER TORWAECHTER (R40 (4), Backlog Nr. 54).
 *
 * WAS BIS HIERHER GALT. „Steht eine Migration aus?" war eine Frage AN DIE
 * SEITE `betrieb_updates.php` — jemand musste sie aufrufen. Zwischen dem
 * Hochladen neuer Dateien und diesem Aufruf erwartet neuer Code Tabellen, die
 * es noch nicht gibt; die Anwendung antwortet in diesem Fenster mit 500, und
 * zwar einer Uhr gegenueber, einem Handy gegenueber und einer Notaerztin
 * gegenueber, die gerade dokumentiert.
 *
 * WAS GILT: Die Anwendung schliesst sich selbst. `migrationen_ausstehend()`
 * beantwortet die Frage bei jeder angemeldeten Anfrage, und steht etwas aus,
 * schaltet `auth_guard.php` den Wartungsmodus mit dem Urheber `torwaechter`.
 * Der Unterschied zwischen 500 und 503 ist der zwischen „kaputt" und „gleich
 * wieder da" — Uhr und Handy puffern und liefern nach.
 *
 * DER ZWISCHENSPEICHER HAENGT AM KATALOG-HASH, nicht an einer Frist. Ein
 * voller Vorschaulauf geht 46 Katalogeintraege durch; das ist der Preis einer
 * Statusseite, nicht der Preis JEDER Seite. Der Hash geht ueber die
 * KENNUNGEN — `serialize()` ueber den Katalog scheiterte an den Closures.
 *
 * DREI STELLEN SCHREIBEN IHN FORT, und die zweite ist Nr. 54:
 * `migrationen_lauf(…, true)` nach einem Lauf (der Hash aendert sich dabei
 * nicht), `wiederherstellen.php` nach dem Einspielen (ein fremder Dump bringt
 * ein fremdes Register mit, und der Hash passt trotzdem) und der Deploy
 * selbst, aber nur mittelbar.
 *
 * BEI EINEM FEHLER BLEIBT DIE INSTALLATION OFFEN. Fehlt `app_state`,
 * antwortet die Datenbank nicht, wirft eine `skip`-Pruefung — dann heisst die
 * Antwort `false`. Der Torwaechter darf keine Installation schliessen, weil
 * er selbst nicht messen konnte; dieselbe Richtung wie beim Ratenschutz.
 *
 * AUS GEHT ER NIE VON SELBST (R66). Betrieb → Updates nennt den Grund und
 * bietet nach dem Lauf ein zweites „Wartung beenden" dort an, wo gerade
 * geklickt wurde.
 *
 * WAS OFFEN BLEIBT, UND DAS STEHT AUCH IM CODE: `ingest.php` und `pair.php`
 * laden `auth_guard.php` nicht. Bis zur ersten angemeldeten Anfrage bekommen
 * die Geraete weiter 500 statt 503 — verloren geht nichts, und fuer die
 * Auslieferungskette ist das Fenster null.
 *
 * KEINE SCHEMAAENDERUNG, KEINE MIGRATION. `migration_tor_hash` und
 * `migration_tor_offen` sind Zeilen in `app_state`.
 *
 * ------------------------------------------------------------------
 *
 * 20.7.0 ist AP4 von P5a: DIE KOPFZEILEN KOMMEN AUS DEM PROGRAMM
 * (R40 (5), E-P5a-15/17, SP-5).
 *
 * WAS BIS HIERHER GALT. Vier Sicherheitskopfzeilen standen in
 * `server/.htaccess`: `X-Content-Type-Options`, `Referrer-Policy`,
 * `X-Frame-Options` und `Strict-Transport-Security` mit einem Jahr. Eine
 * Content-Security-Policy gab es nicht. Das hatte drei Folgen, und jede
 * einzelne war ein Loch:
 *
 *   · KEIN SCHUTZ GEGEN EINGESCHLEUSTES SKRIPT. Ohne CSP genuegt ein
 *     einziges nicht maskiertes Feld irgendwo, und fremdes JavaScript laeuft
 *     im Kontext einer angemeldeten Sitzung — also neben dem entschluesselten
 *     Datenschluessel. Diese Anwendung entschluesselt Patientendaten IM
 *     BROWSER; ein Skript dort ist kein Schoenheitsfehler.
 *   · `.htaccess` GILT NUR AUF APACHE. Wer hinter nginx, Caddy oder einem
 *     Container laeuft, hatte gar keine Kopfzeilen — und merkte es nicht.
 *   · HSTS WAR NICHT VERHANDELBAR. Ein Jahr Bindung, fest in einer Datei,
 *     die beim Deploy mitkommt. Wer eine Installation aufsetzt und die
 *     Domain noch umziehen koennte, hat mit dem ersten Aufruf ein Jahr
 *     Bindung an einen Namen, den er vielleicht nicht behaelt.
 *
 * WAS GILT: `kopfzeilen_lib.php` schreibt sie. `kopfzeilen_seite()` steht in
 * `ui_seite_start()`, `kopfzeilen_json()` in `json_out()` — beide Wege gehen
 * durch eine Stelle, und deshalb bekommt jede Seite und jede API-Antwort die
 * Kopfzeilen, egal auf welchem Webserver.
 *
 * DIE RICHTLINIE BEGINNT BEI `default-src 'none'` und zaehlt auf, was
 * erlaubt ist — nicht umgekehrt. `script-src 'self' 'nonce-…'` ohne
 * `'unsafe-inline'`: Der Nonce ist 16 Zufallsbytes JE ANFRAGE, und ein
 * Skript, das ihn nicht traegt, laeuft nicht. Damit ist die klassische
 * XSS-Kette unterbrochen, auch wenn die Maskierung einmal versagt.
 *
 * ZWEI STUFEN, WEIL DIE ERSTE STUFE SONST DIE ANWENDUNG WAERE. Zuerst
 * `Content-Security-Policy-Report-Only` — der Browser meldet, was er
 * blockiert HAETTE, und fuehrt es trotzdem aus. Die Meldungen sammelt
 * `api/csp_bericht.php` zusammengefasst in `csp_berichte`; Betrieb →
 * Servereinstellungen zeigt sie. Erst wenn dort zwei Wochen lang nichts
 * Neues steht, legt eine BetreiberIn den Schalter `csp_scharf` um. Der
 * Endpunkt verlangt AUSDRUECKLICH KEINE ANMELDUNG: Ein Verstoss auf der
 * ANMELDESEITE ist der interessanteste von allen.
 *
 * DREI ABWEICHUNGEN VOM KONZEPT, alle im Kopf von `kopfzeilen_lib.php`
 * begruendet:
 *
 *   1. `style-src 'self'` PLUS `style-src-attr 'unsafe-inline'`
 *      (E-P5a-32). Drei statische Stilattribute sind weg; die zehn
 *      uebrigen entstehen zur Laufzeit in JavaScript (Leaflet-divIcons,
 *      Zeilenvorlagen, die Balken der Schnittleiste). Sie umzubauen hiesse,
 *      fuer jeden einzelnen Pfeil einer Spur einen eigenen Listener zu
 *      setzen — und es aenderte an der Angriffsflaeche nichts, weil
 *      `el.style.x` CSSOM ist und von CSP ohnehin nicht erfasst wird. Was
 *      ein Stilattribut anrichten kann, begrenzen `default-src 'none'` und
 *      das enge `img-src`.
 *   2. `connect-src` nennt den EINGESTELLTEN Geocoder, nicht einen fest
 *      verdrahteten Namen — und laesst ihn weg, wenn die Adresssuche aus ist.
 *   3. `img-src` MIT `data:` — und das war zuerst anders. Die Zaehlung im
 *      eigenen Quelltext ergab 0 Treffer, also wurde es gestrichen. Der
 *      Report-Only-Lauf meldete daraufhin 140 Verstoesse auf den vier
 *      Kartenseiten: `leaflet.js` traegt ein 1x1 grosses durchsichtiges
 *      GIF als `data:`-Konstante (`L.Util.emptyImageUrl`) und setzt es als
 *      `src`, wenn es eine Kachel wegraeumt — in einer minifizierten
 *      Bibliothek, die keine Zaehlung im Quelltext sieht. Der Preis ist
 *      benannt: `data:` ist die schwaechste Zeile dieser Richtlinie; sie
 *      bleibt, weil ein Bild kein Skript ausfuehrt.
 *
 * HSTS IST EINE EINSTELLUNG GEWORDEN (0 / 1 / 7 / 365 Tage, Vorgabe 1). Und
 * damit sie keine Luege ist, HAT `.htaccess` DIE ZEILE VERLOREN (E-P5a-31):
 * `Header always set` UEBERSCHREIBT, was PHP schickt — die Einstellung haette
 * auf Apache nichts bewirkt. Die drei uebrigen Kopfzeilen stehen dort
 * weiterhin, aber als `setifempty`: PHP fuehrt, wo PHP laeuft, und
 * `.htaccess` deckt weiterhin die statischen Dateien. DER PREIS IST BENANNT:
 * Auf einer bestehenden Installation faellt die Bindung von einem Jahr auf
 * einen Tag, bis jemand sie wieder hochstellt.
 *
 * DIE CLIENT-ADRESSE KOMMT AUS `netz_lib.php` (E-P5a-17). Hinter einem
 * Reverse Proxy war `REMOTE_ADDR` die Adresse DES PROXYS — der Ratenschutz
 * zaehlte alle Nutzerinnen als eine und sperrte sie gemeinsam aus.
 * `X-Forwarded-For` wird jetzt ausgewertet, ABER NUR, wenn die unmittelbare
 * Gegenstelle in `config.php` als vertrauenswuerdig eingetragen ist. Ohne
 * Eintrag rechnet alles wie vorher. Dieselbe Liste entscheidet ueber
 * `X-Forwarded-Proto`: Wer die Client-Adresse faelschen koennte, koennte
 * sonst auch behaupten, eine Anfrage sei ueber HTTPS gekommen.
 *
 * CSRF GEHT JETZT AUCH ALS KOPFZEILE (`X-CSRF`). Zwoelf API-Dateien hatten
 * denselben Block von Hand; sie rufen nun `csrf_check()`, und das nimmt Feld
 * ODER Kopfzeile.
 *
 * EINE MIGRATION: `2026_09_15_csp_berichte` legt die Tabelle an.
 *
 * UND EIN FUND, DER FAST DURCHGERUTSCHT WAERE. Die Richtlinie nannte beide
 * Meldewege — `report-uri` (relative Adresse) und `report-to csp` (nennt nur
 * einen Namen). Die zugehoerige `Reporting-Endpoints`-Zeile trug ebenfalls
 * eine relative Adresse, und die nimmt der Browser dort nicht an: Die Gruppe
 * `csp` war nie aufloesbar. CHROMIUM BEVORZUGT `report-to` UND VERWIRFT DEN
 * BERICHT DANN ERSATZLOS. Der Bilderlauf ueber 49 Seiten meldete daraufhin
 * „0 CSP-Berichte" — das Abnahmekriterium, woertlich erfuellt und voellig
 * wertlos. Gefunden hat es die Gegenprobe, die einen Verstoss ABSICHTLICH
 * ausloest: Er stand in der Konsole, die Tabelle blieb leer. Seither haengt
 * `report-to` an `kopf_melde_url()` — nur bei vollstaendiger HTTPS-Adresse.
 *
 * ------------------------------------------------------------------
 *
 * 20.8.0 ist AP5 von P5a, erster Teil: DER NAME DIESER INSTALLATION
 * (E-P5a-35; Auftrag vom 16.09.2026).
 *
 * WAS BIS HIERHER GALT. Der Name stand 38-mal von Hand im Quelltext, in DREI
 * Schreibweisen fuer dieselbe Sache: „Gen-EM NAdoku" (Tab-Titel, Kopfleiste,
 * Anmeldeseite, Wartungsseite, Schluesselblatt, Installer, GPX-Datei),
 * „Gen-EM Einsatzdokumentation Notarzt" (alle acht Mailtexte) und
 * „Einsatzdokumentation Notarzt" in der Testmail — ohne „Gen-EM".
 *
 * DIE DRITTE IST DER BEWEIS, nicht der Sonderfall: Eine abweichende
 * Schreibweise faellt niemandem auf, solange man acht Dateien
 * nebeneinanderlegen muesste, um sie zu sehen.
 *
 * DER ZWEITE GRUND WIEGT SCHWERER: „Gen-EM" IST EINE MARKE, KEINE FUNKTION.
 * Logo, Impressum und Datenschutztext sind laengst je Installation
 * einstellbar (E-P3-19/20, R32) — der Name war es nicht. Eine fremde
 * Betreiberin verschickte Post, die mit „Gen-EM" unterschrieben ist.
 *
 * WAS GILT: `instanz_lib.php` mit zwei Werten in `app_state` —
 * `instanz_name` (Langname, fuer Mailbetreff und Grussformel) und
 * `instanz_kurz` (Kurzname, fuer Browsertab und Kopfleiste). Gepflegt unter
 * Verwaltung → Installation, wo Logo und Rechtstexte schon stehen. Die
 * Vorgaben sind die heutigen Zeichenketten: Wer nichts einstellt, sieht nach
 * dem Update genau das, was vorher dastand.
 *
 * DIE DATEI LAEDT NICHTS, und das ist der Kniff. Drei Seiten duerfen hier
 * nicht anklopfen — `install.php` laeuft vor der Datenbank, die Wartungsseite
 * ist ausdruecklich ohne Datenbank gebaut, das HTTPS-Tor antwortet vor allem
 * anderen. Sie benutzen `INSTANZ_KURZ_VORGABE` unmittelbar; die Funktionen
 * pruefen selbst mit `function_exists()`, ob es `app_state_lesen()` gibt.
 *
 * DIE WARTUNGSSEITE BEKOMMT DEN NAMEN AUS DEM SCHALTER. `wartung_einschalten()`
 * schreibt ihn in `wartung.lock` — dort steht die Datenbank noch. Fehlt er
 * oder ist die Datei unlesbar, gilt die Vorgabe; „die Datei ist der Schalter,
 * nicht ihr Inhalt" bleibt unveraendert.
 *
 * ZWEI STELLEN BLEIBEN AUSDRUECKLICH FEST: die Fusszeile „© Gen-EM · Open
 * Source" (das ist die URHEBERSCHAFT der Software, nicht der Name des
 * Betriebs) und `creator` in jeder GPX-Datei. Letzteres ist ausgeschrieben
 * worden statt stillschweigend stehengelassen (Weg B): Das Feld benennt die
 * SOFTWARE, nicht die Installation — GPX 1.1 nennt es „the software that
 * created your GPX document", und wer die Anwendung aufsetzt, hat sie nicht
 * geschrieben. Es traegt seit 20.8.0 die Fassung mit (`Gen-EM NAdoku 20.8.0`),
 * weil diese Anwendung GPX auch wieder EINLIEST. Nachgemessen: Der
 * Referenz-Export enthaelt 204 GPX-Dateien, alle mit `creator`, und der
 * Vergleich blendete das Attribut nicht aus — seither ist dort die Fassung
 * maskiert (wie `App-Version:` seit jeher) und der Name weiterhin verglichen.
 * 204 von 204 normalisieren danach gleich, 0 Unterschiede; die Referenz
 * musste nicht neu erzeugt werden.
 *
 * DER LANGNAME GEHT IN EINEN BETREFF, deshalb prueft
 * `instanz_namen_setzen()` eng: Steuerzeichen und Zeilenumbrueche werden
 * abgewiesen. Ein Zeilenumbruch im Betreff waere eine eingeschleuste
 * Kopfzeile, und der Betreff ist die eine Stelle, an der ein selbst
 * eingetippter Wert das Haus verlaesst, ohne dass ein Mensch ihn noch einmal
 * ansieht.
 *
 * KEINE SCHEMAAENDERUNG: zwei Zeilen in `app_state`.
 *
 * ------------------------------------------------------------------
 *
 * 20.9.0 ist AP5 von P5a, ZWEITER Teil: ALLE ZEHN VERSANDSTELLEN GEHEN
 * DURCH DIE WARTESCHLANGE, und die Installation bekommt ihre Adressen
 * (E-P5a-36, E-P5a-40, E-P5a-41; Backlog Nr. 202 AP1, R83).
 *
 * WAS BIS HIERHER GALT. 20.8.0 hat das Geruest gebaut — Katalog,
 * Warteschlange, Job — und NIEMAND benutzte es. Zehn Stellen riefen
 * weiterhin `smtp_send()` unmittelbar. Ein Geruest ohne Benutzer ist kein
 * halber Fortschritt, sondern eine Zusage, die nicht gilt: „Eine Mail geht
 * nicht mehr verloren" stimmte fuer null von zehn Mails.
 *
 * WAS GILT: `pair.php` (2x), `admin_users.php`, `admin_user.php`,
 * `reset_request.php`, `email_lib.php`, `speicher_lib.php`,
 * `adminbackup_lib.php` (2x) und `betrieb_status.php` reihen ein. Der
 * einzige verbliebene Aufrufer von `smtp_send()` ist `mail_lib.php` selbst.
 *
 * DREI AUSGAENGE STATT ZWEI. `mail_einreihen()` liefert `zugestellt`,
 * `wartet` oder `abgelehnt`. Die Aufrufer, die am Rueckgabewert eine MARKE
 * setzen (Schwellenwarnungen, Einladungsmarke), zaehlen `wartet` als
 * erledigt — sonst reihte der naechste Lauf dieselbe Warnung erneut ein und
 * eine dreitaegige Mailstoerung ergaebe sie dreifach. Nur `abgelehnt` — gar
 * nicht erst eingereiht — laesst die Marke offen.
 *
 * ZWEI ADRESSEN WERDEN EINSTELLBAR (E-P5a-40), Verwaltung → Installation,
 * Karte „Adressen":
 *
 *   `instanz_kontakt`  Die Kontaktzeile JEDER Mail. Bis 20.8.0 stand in
 *                      SIEBEN Mailtexten dieselbe persoenliche Adresse des
 *                      Entwicklers, fest im Quelltext — dieselbe
 *                      Fehlerklasse wie der Instanzname. Bleibt sie leer,
 *                      FAELLT DIE ZEILE WEG, statt auf ein Postfach zu
 *                      verweisen, das niemand liest.
 *   `betrieb_mail`     Wohin Betriebspost geht (volles Kontingent,
 *                      ueberfaellige Sicherungen). Leer = weiterhin an alle
 *                      mit Verwaltungsrecht; eine leere Einstellung darf
 *                      keine Warnung verschlucken.
 *
 * `mail_betriebsziele()` haelt diese Auswahl an EINER Stelle. Drei Stellen
 * bauten dieselbe Liste, und die dritte hatte bereits eine abweichende
 * Sortierung — genau der Fall, fuer den R83 das Zentralisieren verlangt.
 *
 * `app_url()` ERSETZT SIEBEN HANDVERKETTUNGEN, fuenf davon ohne `rtrim()`.
 * Steht in `config.php` ein Schraegstrich am Ende, entstand
 * `https://host//pw_handling.php` — in einer Mail, die zum Passwortsetzen
 * auffordert, die falsche Stelle fuer eine Unsauberkeit. Nebenbei
 * behoben: `smtp.php` schickte bei leerer `base_url` ein nacktes „EHLO "
 * (`parse_url('')` liefert FALSE); jetzt steht dort ein Rueckfall.
 *
 * EINE ZEILE AUF DER STATUSSEITE (E-P5a-41), Karte E-Mail: „Warteschlange".
 * Blau wenn leer, ORANGE wenn etwas wartet (der Normalfall eines kurz
 * gestoerten Mailservers, er heilt von selbst), ROT mit ADRESSE, wenn etwas
 * endgueltig liegengeblieben ist. Keine eigene Seite und kein Knopf: Eine
 * Liste waere eine neue Darstellung und braucht eine Freigabe mit Mockup;
 * die Frage einer BetreiberIn — „ist etwas liegengeblieben?" — passt in eine
 * Zeile. Der volle Bereich mit Reitern kommt in P5c.
 *
 * NACHGEMESSEN mit `tools/mailprobe/` gegen eine eigene SMTPS-Gegenstelle,
 * die auf Kommando ablehnt, schweigt oder zwoelf Fortsetzungszeilen
 * schickt: 41 Pruefungen, 0 Befunde. Dazu `tools/jobprobe/` 35 von 35
 * (Teil 10 neu: der Job `mail` steht als ERSTER im Katalog — stuende er
 * hinter der Verdichtung, bekaeme er am Huckepack-Weg regelmaessig nichts,
 * und die Statusseite meldete trotzdem „in Ordnung").
 *
 * ZWEI FUNDE AUS DER PROBE, beide behoben:
 *
 *  1. `smtp_letzter_fehler()` konnte den Grund des VORIGEN Versuchs
 *     liefern. Der Merker wurde erst NACH der Adresspruefung geleert; eine
 *     abgewiesene Adresse liess also die Kennung des letzten Fehlschlags
 *     stehen — eine Kennung, die auf eine andere Nachricht zeigt, ist
 *     schlimmer als keine.
 *  2. Die Leiter laeuft bei kurzlebigen Nachrichten NICHT zu Ende, und das
 *     ist richtig: `passwort_reset` gilt 3600 s, die dritte Sprosse laege
 *     bei 9300 s. Die Zeile wird nach DREI Versuchen `zu_spaet`, nicht nach
 *     fuenf `unzustellbar`. Die Probe misst beides getrennt — die erste
 *     Fassung erwartete fuenf und meldete einen Befund, den es nicht gab.
 *
 * KEINE SCHEMAAENDERUNG: zwei weitere Zeilen in `app_state`.
 *
 * ------------------------------------------------------------------
 *
 * 20.9.1 ist AP4a von P5a — ZWEI SICHERHEITSZEILEN, die an den falschen
 * Stellen standen (E-P5a-38; Backlog Nr. 203 und 205, Nebenfunde der
 * Zentralisierungsanalyse vom 16.09.2026).
 *
 * ERSTENS: `session.use_strict_mode` FEHLTE AUF DEN ANMELDEWEGEN. Gesetzt war
 * es in `install.php` und `wiederherstellen.php` — ausgerechnet den beiden
 * Wegen, die KEINE Anmeldesitzung tragen. Auf den fuenf, die eine tragen
 * (`auth_guard.php`, `login.php`, `session_lib.php`, `pw_handling.php`,
 * `rechtstext_seite.php`), fehlte es.
 *
 * Ohne die Einstellung uebernimmt PHP eine Sitzungskennung, die der Browser
 * mitbringt, AUCH WENN ES SIE NIE VERGEBEN HAT. Wer eine Kennung setzen kann,
 * kennt damit die Sitzung, in der sich gleich jemand anmeldet — das ist
 * Session-Fixation, und der Schutz dagegen hing an der `php.ini` des
 * Hosters. Auf dem Pruefstand steht dort `Off`.
 *
 * GEMESSEN, NICHT BEHAUPTET: Ohne die Zeile nimmt `login.php` eine frei
 * erfundene Kennung an und schickt GAR KEIN `Set-Cookie` zurueck; mit ihr
 * verwirft es sie und vergibt eine neue. Beide Laeufe mit jeweils frischer
 * Zufallskennung — eine schon benutzte waere dem Server bekannt und wuerde
 * auch mit der Haertung angenommen, und der zweite Lauf haette das Gegenteil
 * des ersten gemessen.
 *
 * NEU DAZU: `tools/sitzungshaertung/`, in Stufe 1. Die Zeile ist unscheinbar
 * und steht neben dem Aufruf, den sie schuetzt; ein neuer Weg, der sie
 * vergisst, sieht genauso aus wie einer, der sie hat. Gemessen mit dem
 * Tokenizer, nicht mit `grep` — die erste Fassung meldete zwei Befunde, und
 * beide waren Kommentarzeilen ueber das Werkzeug selbst. Selbstprobe 8/8, im
 * Lauf 7 echte Aufrufe, 0 ohne Haertung.
 *
 * KEIN `sitzung_starten()`-HELFER. Der ist Schritt 15 (Backlog Nr. 202
 * Paket 3); hier steht nur die Zeile. Ein Helfer waere die groessere
 * Aenderung an denselben sieben Stellen und gehoert nicht in ein Paket, das
 * eine Luecke schliesst.
 *
 * ZWEITENS: `json_roh_out()` NEBEN `json_out()`. Sieben Stellen gaben JSON
 * aus, ohne durch `json_out()` zu gehen — `header('Content-Type: ...')` und
 * `echo` von Hand. Zwei davon (`api/export_data.php`) setzten KEIN
 * `Cache-Control: no-store`, und DIESE ANTWORT ENTHAELT GPS-SPURPUNKTE.
 * Die Begruendung, die seit M3-11 bei `json_out()` steht — „der Kopf gehoert
 * an die Stelle, durch die JEDE Antwort geht" —, galt fuer sie schlicht
 * nicht.
 *
 * Warum sie ueberhaupt vorbeigehen: Sie HABEN den Text schon.
 * `api/export_data.php` baut ihn stueckweise, `api/backup_data.php` reicht
 * Chiffretext durch, `jobs.php` braucht eigene `json_encode`-Schalter. Sie
 * sollen ihn nicht dekodieren muessen, nur um ihn wieder zu kodieren.
 *
 * DREI FUNKTIONEN, EINE STELLE: `json_kopf()` setzt den Satz, `json_roh_out()`
 * ruft sie und gibt fertigen Text aus, `json_out()` ruft `json_roh_out()`.
 * `json_kopf()` gibt es, weil `pair.php` an zwei Stellen antwortet und DANN
 * WEITERARBEITET — es schliesst die Antwort ab und reiht erst danach die
 * Hinweismail ein, weil die Uhr auf das `ok` wartet. Ein `never` schliesst
 * diese Stelle aus.
 *
 * UMGESTELLT WURDEN SIEBEN STELLEN, nicht die drei aus dem Auftrag:
 * `api/export_data.php` (2x), `api/backup_data.php`,
 * `api/adminbackup_freigabe.php` — und dazu `auth_salt.php`, `jobs.php`,
 * `pair.php`, weil sie denselben Mangel hatten. `auth_salt.php` liefert das
 * Salt JE KONTO und ist unangemeldet erreichbar; `pair.php` nennt die
 * maskierte Adresse des Kontos. Eine zwischengespeicherte Antwort ist dort
 * nicht unsauber, sondern falsch.
 *
 * `wartung_lib.php` BLEIBT AUSSEN VOR, und das ist keine Nachlaessigkeit: Die
 * Wartungsseite ist ausdruecklich ohne Datenbank gebaut und darf `db.php`
 * nicht laden. Sie setzt ihren Satz selbst — einschliesslich `no-store`,
 * nachgesehen.
 *
 * EINE NEBENWIRKUNG, ausgeschrieben: `json_out()` schickt jetzt
 * `application/json; charset=utf-8` statt `application/json`. RFC 8259
 * definiert fuer `application/json` keinen charset-Parameter; kein Client
 * bricht daran, und vier der sieben umgestellten Stellen schickten ihn
 * ohnehin. Die Uhr uebergeht ihn ganz
 * (`:responseType => HTTP_RESPONSE_CONTENT_TYPE_JSON`).
 *
 * KEINE SCHEMAAENDERUNG.
 *
 * ------------------------------------------------------------------
 *
 * 20.10.0 ist AP6 von P5a: DER RATENSCHUTZ BEKOMMT EIN GEDAECHTNIS
 * (E-P5a-04 bis -07, E-P5a-43 bis -46; R37 (8)).
 *
 * WAS BIS HIERHER GALT. Eine Sperre dauerte fest 15 Minuten — die erste wie
 * die hundertste. Wer geduldig ist, bekommt damit 10 Versuche je
 * Viertelstunde, dauerhaft, ohne dass irgendetwas eskaliert. Und alle
 * Fehlversuche der Installation zusammen wurden nirgends gezaehlt: Ein
 * Angriff ueber tausend Namen sah aus wie tausend einzelne Vertipperinnen.
 *
 * WAS GILT — VIER STUECKE:
 *
 * 1. EINE SPERRLEITER. Je Merkmal eine Stufe 1 bis 4, Dauer 15/20/30/60 min,
 *    Verfall nach 24 h ohne Fehlversuch. Einstellbar unter Betrieb ->
 *    Servereinstellungen.
 * 2. ZWEI SCHWELLEN STATT EINER. 10 Fehlversuche je Konto, 50 je Anschluss
 *    (neuer Topf `login_ip`). Hinter einem Klinik-NAT teilen sich viele eine
 *    Adresse; bei 10 sperrte die zehnte Vertipperin die uebrigen neunzehn aus.
 * 3. EINE VERLANGSAMUNG STATT EINER GLOBALEN SPERRE. Ab 200/400/800/1600
 *    Fehlversuchen je 15 min wartet jede FEHLGESCHLAGENE Anmeldung 1/2/4/8 s.
 *    Wer das richtige Passwort hat, kommt durch. Eine globale Sperre waere
 *    ein Schalter, den jeder von aussen umlegt.
 * 4. EINE SAMMELMAIL bei der hoechsten Stufe, hoechstens eine je Stunde,
 *    ueber die Warteschlange aus AP5.
 *
 * DAZU `sicherheit_ereignisse` — was WAR, nicht was IST. `rate_limits` haelt
 * den Zustand; laeuft die Sperre ab und raeumt der Job die Zeile weg, ist
 * nichts mehr da. Dieselbe Luecke, die `job_laeufe` in 20.8.0 fuer die
 * Hintergrundjobs geschlossen hat.
 *
 * ---------------------------------------------------------------------------
 * VIER ABWEICHUNGEN VOM KONZEPT, ALLE BENANNT
 * ---------------------------------------------------------------------------
 *
 * (a) DIE ERSTE SPROSSE IST 15 MINUTEN, NICHT 10 (E-P5a-43). Das Konzept sagt
 *     10/20/30/60 — und `login` sperrt heute fest 900 s, also 15. Mit 10 waere
 *     der ERSTE Verstoss nach dem Update MILDER als davor. Ein
 *     Sicherheitspaket, das eine Schranke senkt, ohne es zu sagen, ist die
 *     Art Fehler, die niemandem auffaellt. Die Leiter ist einstellbar.
 *
 * (b) `reset` BEKOMMT KEINE LEITER, obwohl das Konzept ihn nennt. Er sperrt
 *     heute 3600 s; jede Sprosse unterhalb der vierten waere SCHWAECHER.
 *     Dazu: `reset_request.php` antwortet im gesperrten Fall wortgleich wie
 *     im erlaubten — eine Leiter dort streckt ein Fenster, in dem jemand
 *     fuenfmal klickt, fuenfmal dieselbe Zusage liest und keine Mail bekommt.
 *
 * (c) DER COUNTDOWN STEHT NICHT IN `forms.js` (E-P5a-45). Das Konzept nennt
 *     „login.php, forms.js"; login.php LAEDT forms.js gar nicht, und jene
 *     Datei ist Aenderungsverfolgung, Strg-Enter und Abbrechen-Rueckfrage.
 *     Sie hier nachzutragen schaltete nebenbei eine beforeunload-Warnung auf
 *     einer Seite frei, auf der jemand ein Passwort tippt. Der Countdown
 *     steht im vorhandenen genoncten Block.
 *
 * (d) DIE SAMMELMAIL GEHT AN `mail_betriebsziele()`, nicht an eine eigene
 *     Liste „alle Konten mit Rolle BetreiberIn". Seit E-P5a-40 ist das die
 *     EINE Stelle, an der der Empfaengerkreis der Betriebspost steht (R83);
 *     eine zweite Liste waere der Rueckfall in den Zustand, den R83
 *     abgeschafft hat.
 *
 * ---------------------------------------------------------------------------
 * DREI FEHLER, DIE OHNE MELDUNG DURCHGEGANGEN WAEREN
 * ---------------------------------------------------------------------------
 *
 * 1. DER GESPERRTE HAETTE SICH DURCH KLOPFEN BEFREIT. `rate_misserfolg()`
 *    setzte `gesperrt_bis = NULL`, sobald das Zaehlfenster abgelaufen war.
 *    Folgenlos, solange bei ALLEN zehn Toepfen `sperre == fenster` galt — und
 *    das galt. Mit einer Leiter bis 60 min bei 15 min Fenster haette ein
 *    einziger Fehlversuch nach Fensterablauf die LAUFENDE Sperre geloescht.
 *    Die Bedingung heisst jetzt „und keine laufende Sperre".
 *
 * 2. DIE STUFE WAERE NIE ZURUECKGEFALLEN. Der Verfall (`stufe_bis`) wurde nur
 *    dort aufgefrischt, wo NICHT gesperrt wurde — also bei den ersten neun
 *    Fehlversuchen. Jeder schob die Frist um 24 h vor, sodass sie beim
 *    zehnten nie abgelaufen war: Ein Konto, das vor einem halben Jahr einmal
 *    die vierte Sprosse erreicht hatte, bekam beim naechsten Tippfehler
 *    sofort wieder 60 Minuten. Gefunden von `tools/ratenprobe/`.
 *
 * 3. EIN VARIABLENNAME. Die neue Schleife auf der Statusseite hiess `$sp` —
 *    so wie der Speicherstand, den `status_erhebung()` dreihundert Zeilen
 *    weiter unten braucht. Ergebnis: HTTP 500 auf Betrieb -> Status, aber nur,
 *    wenn gerade etwas gesperrt war.
 *
 * ZWEI MIGRATIONEN: `2026_09_16_ratenschutz_stufen` (zwei Spalten auf
 * `rate_limits`, dazu ein Index auf `gesperrt_bis`) und
 * `2026_09_16_sicherheit_ereignisse`. NACH DEM DEPLOY MUSS EINE
 * ADMINISTRATORIN BETRIEB -> UPDATES AUFRUFEN.
 *
 * DAS FENSTER DAZWISCHEN IST MITGEDACHT: Die Grundzaehlung nennt die neuen
 * Spalten mit keinem Wort und laeuft unveraendert weiter; die Leiter steht in
 * einem zweiten, eigen gefangenen Statement und tut in diesem Fenster nichts.
 * Gesperrt wird dann mit der festen Dauer wie vor 20.10.0 — der Ratenschutz
 * faellt NICHT aus, er ist nur wieder so streng wie vorher.
 *
 * ---------------------------------------------------------------------------
 * 20.11.0 — DIE LETZTE ASYMMETRIE ENDET: `ingest.php` BEKOMMT EINE BREMSE
 * ---------------------------------------------------------------------------
 *
 * (P5a/AP7; E-P5a-01, -02, -47, -48, -49; R19, Backlog Nr. 17.)
 *
 * Bis hierher war `ingest.php` der EINZIGE Endpunkt der Anwendung ohne
 * Ratenschutz — und zugleich der, an dem die Clients aus den Stores haengen.
 * E-R45-6 nennt genau diese Kombination als die Flutungsgefahr aus P5. Zwei
 * Toepfe schliessen sie: `ingest` je Geraetekennung, `ingest_ip` je Adresse
 * fuer Kennungen, die es nicht gibt. 30 Fehlversuche je 15 Minuten, danach
 * die Sperrleiter aus 20.10.0, erste Sprosse 15 Minuten, Antwort 429 mit
 * `Retry-After`.
 *
 * GEZAEHLT WERDEN NUR FEHLVERSUCHE. Ein gelungener Upload geht nie auf das
 * Kontingent; eine Uhr, die einen ganzen Dienst nachliefert, sendet beliebig
 * viele Stuecke. Nicht gezaehlt werden ausserdem 405, 413, 403
 * `device_disabled`, 400 und 500 — und der Wartungsmodus schon gar nicht, der
 * antwortet in `db.php`, bevor diese Datei laeuft.
 *
 * SICHTBAR AN ZWEI STELLEN, denn eine Uhr, die nichts mehr hochlaedt, ist
 * sonst ein Raetsel: `devices.abgewiesen_seit` und `abgewiesen_anzahl`
 * (Migration) stehen als Kleinzeile und orange Plakette auf der Kontoseite am
 * Geraet und als Zeile auf Betrieb -> Status. Beide werden beim naechsten
 * gelungenen Upload geleert — ein Vermerk, der stehenbleibt, nachdem neu
 * gekoppelt wurde, ist eine Falschmeldung.
 *
 * ---------------------------------------------------------------------------
 * DREI ABWEICHUNGEN VOM KONZEPT, ALLE BENANNT
 * ---------------------------------------------------------------------------
 *
 * (a) DER ADRESSTOPF HAT 30 UND NICHT 50 (E-P5a-47). Verschiedene Schwellen
 *     sind ein EXISTENZORAKEL: Wer dieselbe geratene Kennung haemmert,
 *     bekaeme sie bei existierender Kennung ab dem 31. Versuch abgewiesen,
 *     bei nicht existierender erst ab dem 51. Genau diese Auskunft hat M4-07
 *     in `ingest.php` mit einem Blindvergleich beseitigt; sie als
 *     Zaehlunterschied wieder einzubauen waere ein Rueckschritt durch die
 *     Hintertuer. Die Absenkung kostet keinen legitimen Verkehr: In den
 *     Adresstopf zaehlen ausschliesslich UNBEKANNTE Kennungen, und ein
 *     gekoppeltes Geraet sendet nie eine unbekannte. WAS BLEIBT, STEHT DA:
 *     Eine zweistufige Probe unterscheidet weiter. Sie zu schliessen hiesse,
 *     auch bekannte Kennungen in den Adresstopf zu zaehlen — dann sperrte ein
 *     einziges Geraet mit veraltetem Schluessel seine ganze Adresse,
 *     einschliesslich des neu gekoppelten, das die Abhilfe ist.
 *
 * (b) DIE INGEST-TOEPFE BEKOMMEN EINE LEITER, und die Trennlinie im
 *     Kopfkommentar von `ratelimit_lib.php` musste dafuer umgeschrieben
 *     werden (E-P5a-48). Sie lautete: Kopplungstoepfe bekommen keine, weil
 *     „dahinter ein Geraet steht, das nicht lesen kann, was auf der Seite
 *     steht". Auf `ingest.php` trifft das woertlich genauso zu — die Regel
 *     haette also gegen die Leiter entschieden, die AP7 baut. Die tragfaehige
 *     Trennlinie ist: Unterbricht die laengere Sperre einen Vorgang, der
 *     GERADE LAEUFT? Bei der Kopplung ja (jemand steht am Geraet mit einem
 *     Code, der in zehn Minuten verfaellt), bei `ingest.php` nein (die Daten
 *     liegen in der Warteschlange des Geraets).
 *
 * (c) ZWEI ABNAHMEZEILEN WAREN SO NICHT ERFUELLBAR (E-P5a-49). „Sperre
 *     10 min" — die erste Sprosse ist seit E-P5a-43 fuenfzehn. Und
 *     „Messstand-Zahlen fuer `ingest.php`" — der Messstand erhebt fuer
 *     `ingest.php` keine Zahl und hat nie eine erhoben. Gemessen wurde
 *     stattdessen der Sendeplan des Referenzdatensatzes: 612 echte Anfragen,
 *     64 478 Punkte, je zwei Laeufe mit und ohne Bremse.
 *
 * ---------------------------------------------------------------------------
 * WAS DIE MESSUNG SAGT, UND WAS SIE NICHT SAGT
 * ---------------------------------------------------------------------------
 *
 * Die Bremse kostet EINE zusaetzliche Abfrage je Upload — die Pruefung vor
 * der Geraeteabfrage, ueber beide Toepfe in EINEM Statement. Gemessen (612
 * Anfragen je Lauf): Median 14,43 ms ohne, 15,14 ms mit; Mittel 19,67 ms
 * ohne, 20,33 ms mit. Das sind +4,9 % beziehungsweise +3,4 %. Die Streuung
 * ZWISCHEN ZWEI GLEICHEN LAEUFEN liegt bei 3 bis 4 % — der Aufschlag liegt
 * also in derselben Groessenordnung wie das Rauschen und ist damit die obere
 * Schranke, nicht der Messwert.
 *
 * ---------------------------------------------------------------------------
 * EIN FEHLER, DER NEBENBEI AUFFIEL
 * ---------------------------------------------------------------------------
 *
 * `$pdo->rollBack()` im Fehlerzweig von `ingest.php` lief unbedingt — und
 * `commit()` steht MITTEN im try-Block, danach kommen noch Hoehenberechnung
 * und Antwortaufbau. Warf eine von beiden, traf der Rollback auf keine offene
 * Transaktion und warf seinerseits. Diese zweite Ausnahme ersetzte die erste:
 * Die `kennung` im Fehlerprotokoll benannte den Rollback statt der Ursache —
 * also genau das Schweigen, das M3-10 abgestellt hat.
 *
 * EINE MIGRATION: `2026_09_16_geraet_abgewiesen` (zwei Spalten auf
 * `devices`). NACH DEM DEPLOY MUSS EINE ADMINISTRATORIN
 * BETRIEB -> UPDATES AUFRUFEN. Das Fenster dazwischen ist mitgedacht: Die
 * Abfrage in `ingest.php` und die auf der Kontoseite haben einen Rueckfall
 * ohne die beiden Spalten, die Statusseite faengt. Der Schutz selbst haengt
 * nicht daran — er zaehlt in `rate_limits`, und die Tabelle steht seit
 * 20.10.0.
 *
 * ---------------------------------------------------------------------------
 * 20.12.0 — DER RATENSCHUTZ BEKOMMT EIN GESICHT: STATUS -> SICHERHEIT
 * ---------------------------------------------------------------------------
 *
 * (P5a/AP8; E-P5a-08, -09; M-P5a-01 ohne Mockup-Pause, Konzept 2.5.)
 *
 * AP6 und AP7 haben gebaut, was still arbeitet: Sperrleiter, Verlangsamung,
 * Mengenbremse, ein Ereignisprotokoll. Sichtbar war davon eine Zeile auf der
 * Statusseite je Sache — sie sagt, DASS etwas ist. Wer wissen wollte, WER
 * gesperrt ist, seit wann, auf welcher Sprosse, und wer es aufheben wollte,
 * fand nichts. `betrieb_sicherheit.php` ist diese Seite.
 *
 * SIE HAENGT AN STATUS UND IST KEIN ACHTZEHNTER MENUEPUNKT (E-P5a-08). Fuer
 * eine BetreiberIn stehen siebzehn Eintraege in der Leiste; einer mehr fuer
 * eine Seite, die man an guten Tagen nie braucht, waere an der falschen
 * Stelle teuer. `ui_geruest_start(['menue' => 'betrieb_status'])` haelt den
 * Eintrag „Status" aktiv — dasselbe Muster wie `admin_user.php`.
 *
 * SIE AENDERT GENAU EINES: Eine Sperre aufheben. Das ist der Unterschied zur
 * Elternseite, die rein liest, und er ist gewollt: Eine Kollegin, die sich
 * ausgesperrt hat, ruft an, und die Betreiberin soll ihr helfen koennen, ohne
 * in die Datenbank zu greifen.
 *
 * ---------------------------------------------------------------------------
 * VIER ABWEICHUNGEN, ALLE BENANNT
 * ---------------------------------------------------------------------------
 *
 * (a) FUENF KARTEN STATT SECHS. E-P5a-08 nennt „Loeschungen auf
 *     Sicherungszielen". Dafuer gibt es heute weder Tabelle noch Schreibweg
 *     noch `app_state`-Schluessel — die Loeschregel je Ziel entsteht erst in
 *     AP10 (E-P5a-03). Eine Karte, die sagt „hier steht noch nichts, weil es
 *     die Sache noch nicht gibt", ist kein Befund, sondern Laerm. Sie kommt
 *     mit AP10.
 *
 * (b) KEINE TABELLE, OBWOHL DIE MOCKUP-SKIZZE FUER DIE ERSTE KARTE EINE
 *     NENNT. `docs/Design.md` 9.0 fuehrt „eine Liste von Eintraegen"
 *     ausdruecklich auf `ui_zeile()` in einer Karte und die `<table>` unter
 *     „nicht"; die Tabelle ist dort fuer „Zahlen nebeneinander vergleichen"
 *     gedacht und im Bestand ueberhaupt kein Baustein, sondern rohes Markup
 *     auf sechs Seiten. Eine Liste mit einer Handlung je Eintrag ist der
 *     Fall, fuer den `ui_zeilenaktionen()` gebaut ist.
 *
 * (c) DIE KARTE HEISST „VERLANGSAMUNG" UND ZEIGT ANSTIEGE, KEINE PHASEN.
 *     Vermerkt wird, WENN DIE STUFE STEIGT; ein Ende hat kein eigenes
 *     Ereignis. Eine Karte, die „Phasen" verspricht und Punkte zeigt, gaebe
 *     eine falsche Auskunft — also steht „seit" da und nicht „von bis", und
 *     der Vorbehalt steht auf der Karte.
 *
 * (d) DER DATENSCHUTZTEXT WIRD VORGESCHLAGEN, NICHT GESCHRIEBEN. Die
 *     Anwendung liefert keinen Rechtstext mit (R32) — der Text ist eine Zeile
 *     in `rechtstexte` und gehoert der Betreiberin. AP8 legt deshalb einen
 *     zweiten Textbaustein unter Verwaltung -> Installation, neben den zur
 *     Adresssuche aus S9/AP2, und zwar OHNE Bedingung: Den Ratenschutz gibt
 *     es in jeder Installation, und er laesst sich nicht abschalten.
 *
 * ---------------------------------------------------------------------------
 * DREI FEHLER, DIE OHNE MELDUNG DURCHGEGANGEN WAEREN
 * ---------------------------------------------------------------------------
 *
 * 1. DAS PROTOKOLL HING AM MAILSCHALTER. `sicherheit_melden_pruefen()` kehrte
 *    als ERSTE Zeile zurueck, wenn `rate_mail_an()` false ist — und genau
 *    dort drin stand das Vermerken der Verlangsamungsstufe. Wer die
 *    Sammelmail abschaltete, weil er sie nicht braucht, schaltete
 *    stillschweigend auch das Protokoll ab; die Karte waere auf einer solchen
 *    Installation dauerhaft leer geblieben, ohne dass irgendwo stuende,
 *    warum. Protokollieren und Melden stehen jetzt in zwei Funktionen.
 *
 * 2. DER GERAETEVERMERK VERFIEL NIE. `devices.abgewiesen_seit` und
 *    `abgewiesen_anzahl` (AP7) werden beim naechsten gelungenen Upload
 *    geleert — und den gibt es nicht mehr, wenn das Geraet ausgemustert ist.
 *    Ein verlorenes Geraet truege seine orange Plakette „abgewiesen" fuer
 *    immer, und die Statuszeile stuende dauerhaft orange auf einer
 *    Installation, an der nichts mehr zu tun ist. Der Aufraeumjob raeumt den
 *    Vermerk jetzt nach 30 Tagen (E-P5a-09 nennt die „Bremse-Treffer").
 *
 * 3. `ui_knopf()` KENNT KEIN `form`. Der Schluessel, mit dem ein Knopf ein
 *    Formular ausserhalb seiner selbst absendet, gibt es nur in
 *    `ui_zeilenaktionen()` und in der Kopfaktion der Karte. Ein
 *    `ui_knopf(['form' => ...])` haette einen Knopf ergeben, der nichts tut —
 *    ohne Fehlermeldung, ohne dass ein Bild es zeigt. Gefunden bei der
 *    Durchsicht des Vorrats, nicht im Betrieb.
 *
 * DAZU EINE FALLE DES VORRATS, die jetzt in `docs/Design.md` steht: Eine
 * EINGEKLAPPTE Karte (`zu`/`vorschau`) zeigt weder `plakette` noch `aktion` —
 * `ui_karte_start()` kehrt im `<details>`-Zweig zurueck, bevor beides
 * ausgegeben wird. Wer eine Plakette an eine klappbare Karte haengt, verliert
 * sie still.
 *
 * DREI ABLEITUNGEN WURDEN EINE. „Ist dieses Merkmal ein Konto oder eine
 * Adresse?" stand dreimal im Bestand nachgebaut. `rate_sperren_aktiv()`
 * liefert `art` jetzt mit; die Statusseite rechnet es nicht mehr selbst, und
 * die Sicherheitsseite haette die vierte Kopie gebraucht.
 *
 * KEINE MIGRATION. Alle Tabellen stehen seit AP6 und AP7.
 *
 * ---------------------------------------------------------------------------
 * 20.13.0 — DIE VERBINDUNGSGRENZE: „AUSGELASTET" IST NICHT „KAPUTT"
 * ---------------------------------------------------------------------------
 *
 * AP9 von P5a (E-P5a-18). MySQL/MariaDB weist eine Verbindung ab, wenn eine
 * von drei Grenzen erreicht ist: `max_connections` des Servers (1040), die
 * Systemvariable `max_user_connections` (1203) oder die GRANT-Grenze dieses
 * einen Datenbankkontos (1226). Auf einem geteilten Webspace ist die dritte
 * die alltaegliche — der Hoster gibt jedem Kunden seine Zahl, und sie liegt
 * regelmaessig bei 10 bis 30.
 *
 * Bis 20.12.0 kam dann eine **500** heraus, mit dem ungefilterten Text der
 * PDO-Ausnahme. Darin stehen Hostname und Benutzername der Datenbank. Zwei
 * Fehler in einem: Die Anfrage war nicht kaputt, sie war verfrueht — und was
 * auf dem Bildschirm stand, ging niemanden etwas an.
 *
 * JETZT: 503 mit `Retry-After: 5`. Seiten bekommen eine Seite im Aufbau der
 * Wartungsseite („Der Server ist gerade ausgelastet — bitte in einer Minute
 * noch einmal"), JSON-Endpunkte `{"error":"ausgelastet"}`. Fuer die Geraete
 * ist das dieselbe Zusage wie der Wartungsmodus; KEIN CLIENT WIRD DAFUER
 * GEAENDERT.
 *
 * DIE DRITTE NUMMER STAND NICHT IM KONZEPT (E-P5a-51). Gemessen am
 * 16.09.2026 gegen MariaDB 10.11: Eine GRANT-Grenze am Konto meldet 1226,
 * nicht 1203. Ohne diese Zeile haette das Paket genau den Fall nicht
 * abgedeckt, fuer den es gebaut ist — und zwar still.
 *
 * DER ZAEHLER STEHT IN EINER DATEI (E-P5a-50), nicht in `app_state` wie
 * geplant. In dem Augenblick, in dem gezaehlt werden muesste, gibt es keine
 * Verbindung zur Datenbank. Derselbe Satz wie bei `wartung.lock`.
 * `server/ueberlast.json` liegt nur auf dem Server; die Statusseite zeigt die
 * Zahlen in der Karte Server, Zeile „Verbindungen", und sagt ausdruecklich,
 * wenn die Datei nicht schreibbar ist — „0 Vorfaelle" und „nicht gezaehlt"
 * saehen sonst gleich aus.
 *
 * ZWEI FEHLER, DIE OHNE MELDUNG DURCHGEGANGEN WAEREN
 *
 * 1. ZWOELF VON ZWANZIG GLEICHZEITIGEN UPLOADS BEKAMEN 500 (E-P5a-52).
 *    Deadlock 1213 auf der `days`-Zeile, die jeder Upload eines Diensttags
 *    fortschreibt. Derselbe Fehler wie die Verbindungsgrenze, eine Ebene
 *    hoeher — und nie gemessen, weil kein Pruefmittel bisher gleichzeitig
 *    hochgeladen hat. 1213 und 1205 antworten jetzt ebenfalls 503; die
 *    Abhilfe (Transaktion wiederholen) ist Backlog Nr. 210.
 *
 * 2. DER EINLADUNGSLINK WAR IN EINER SACKGASSE (E-P5a-54). AP5 las den
 *    Zustand `wartet` als „geht gleich hinaus" und verbarg den Setz-Link.
 *    `wartet` heisst aber, dass der ERSTE VERSUCH GESCHEITERT ist. Auf einer
 *    Installation mit eingetragenem, aber unerreichbarem SMTP-Server war der
 *    Link nirgends mehr zu bekommen, und das Konto blieb unbenutzbar.
 *
 * DAZU: `JSON_SKRIPTE_AUSSERHALB_API` waechst von zwei auf vier (E-P5a-55).
 * Der Wartungsmodus kam mit zweien aus, weil `auth_salt.php` und `jobs.php`
 * in seiner Ausnahmeliste stehen. DIE UEBERLAST KENNT KEINE AUSNAHMEN.
 *
 * KEINE MIGRATION.
 *
 * ---------------------------------------------------------------------------
 * 20.14.0 — AUFBEWAHRUNG AUF DEM SICHERUNGSZIEL: ERST SEHEN, DANN LOESCHEN
 * ---------------------------------------------------------------------------
 *
 * AP10 von P5a (E-P5a-03, Backlog Nr. 49 und Nr. 195). Der Versand ERGAENZT
 * nur; auf der Gegenstelle hat diese Anwendung nie geloescht. Das ist Absicht
 * und keine Luecke — der Zweck eines auswaertigen Ziels ist, den Ausfall
 * dieses Servers zu ueberleben, SAMT eines Fehlers, der HIER zu viel loescht.
 *
 * Bei zwei Sicherungen je Konto und Monat laeuft ein Ziel trotzdem voll, und
 * niemand merkt es hier. Zwei Stufen dagegen:
 *
 *   ANZEIGE ist die Grundlage und loescht nichts. Im Menue einer Zielzeile
 *   steht „Nachsehen, was dort liegt": Anzahl, Groesse, aeltester und
 *   juengster Stand — und wie viele FREMDE Dateien dort liegen. Auf
 *   Knopfdruck, nicht bei jedem Seitenaufruf.
 *
 *   LOESCHREGEL ist die Option, je Ziel, ausdruecklich einzuschalten. Mit
 *   drei Sicherungen: HERKUNFT (Namensmuster UND Versandprotokoll), MENGE
 *   (nie unter N bzw. M, nur eigene Dateien gezaehlt) und LAUF (nie nach
 *   einem gescheiterten Versand).
 *
 * DER KREISLAUF, DER BEIM BAUEN DER PROBE HERAUSKAM (E-P5a-57). Der zweite
 * Lauf raeumte drei alte Sicherungen weg, der DRITTE schickte dieselben drei
 * wieder hinueber — sie liegen hier ja noch —, und der vierte raeumte sie
 * erneut weg. Bandbreite bei jedem Job, ein volles Protokoll, und beide
 * Seiten taten genau das, wofuer sie gebaut sind. Gemessen: dritter Lauf
 * 3 geloescht statt 0. Seither geht nicht wieder hinueber, was die Regel
 * dort entfernt hat.
 *
 * NR. 195: Die Geraeteart kam auf dem Rueckweg der Sicherung ungeprueft
 * durch — geprueft wurde allein die Laenge. Jetzt dieselbe Verengung wie
 * beim Koppeln, und sie steht im Pruefprotokoll statt still zu geschehen.
 *
 * PP-5: Die Platzwarnung nannte das Zweifache des groessten Komplett-Backups
 * als Sollwert und pruefte gegen das Einfache. Jetzt zwei Schwellen: rot
 * unter dem Einfachen, orange unter dem Zweifachen.
 *
 * MIGRATION `2026_09_16_sicherungsziel_aufbewahrung`: zwei Spalten an
 * `backup_targets` und die Tabelle `sicherungsziel_dateien` (Versand- UND
 * Loeschprotokoll). NACH DEM DEPLOY MUSS UPDATE.PHP LAUFEN — sonst bleibt
 * die Regel aus, und sie sagt es.
 *
 * ---------------------------------------------------------------------------
 * 20.15.0 — DER NACHLOESE-JOB: DIE MODELLTABELLE HOLT DIE GERAETE EIN
 * ---------------------------------------------------------------------------
 *
 * AP11 von P5a (E-P5a-21, Backlog Nr. 80). `pair.php` loest die Teilenummer
 * einer Garmin-Uhr IM MOMENT DER KOPPLUNG auf, und nur dann. Trifft sie dabei
 * auf eine leere oder aeltere Tabelle, bleibt das Modell leer — und
 * `geraet_art` steht auf der UNGEPRUEFTEN SELBSTAUSKUNFT: Die Uhr-App sendet
 * dort fest „uhr", weil eine Connect-IQ-App Uhr und Radcomputer nicht
 * unterscheiden kann.
 *
 * Nachtragen ging seit Web 12.9.1 nur ueber die KOMMANDOZEILE. Auf einem
 * Webspace ohne SSH gibt es diesen Weg nicht; dort holten die betroffenen
 * Geraete ihre Angabe erst bei der naechsten Kopplung nach, also womoeglich
 * nie. Die Zahl, die Nr. 80 auswerten will, hing damit daran, ob jemand SSH
 * hat.
 *
 * JETZT: der Job `nachaufloesen`. Er laeuft nur nach einer neuen Tabelle,
 * erkannt am HASH und nicht an einem Datum — ein Deploy fasst die
 * Aenderungszeit jeder Datei an, der Inhalt bleibt derselbe. In Bloecken von
 * 200, eine Transaktion je Block, Fortsetzungsmarke im Jobzustand; der Hash
 * wird ERST AM ENDE geschrieben, sonst gaelte ein halb durchgegangener
 * Bestand als erledigt.
 *
 * DREI REGELN, UNVERAENDERT AUS DEM SKRIPT: nur aendern, was die Tabelle
 * wirklich kennt; die Rohangabe nie anfassen; Handy-Zeilen bleiben unberuehrt.
 *
 * Die Logik steckt in `geraetemodelle_lib.php` und wird von Job UND Skript
 * benutzt; das Skript behaelt die Vorschau. Die Modelltabelle ist dabei ein
 * PARAMETER geworden — die Naht fuer die Probe, die zwei Tabellen in einem
 * Lauf braucht.
 *
 * Statusseite, Karte Server, Zeile „Geraetemodelle": orange „steht aus", wenn
 * die Tabelle sich geaendert hat und der Job noch nicht gelaufen ist.
 *
 * NEBENBEI: Vier von acht Jobs fehlten im Register der Technik-Dokumentation
 * (`mail`, `adminbackup`, `versand`, `komplett`) — aufgefallen beim Eintragen
 * des neunten. Nachgetragen; die Ursache bleibt Nr. 208.
 *
 * KEINE MIGRATION.
 *
 * ---------------------------------------------------------------------------
 * 20.15.1 — DIE ZUSTANDSDATEI DER KETTE LAG IM WEBROOT
 * ---------------------------------------------------------------------------
 *
 * Backlog Nr. 213, gemeldet am 16.09.2026 aus einer Durchsicht. Die
 * Auslieferungskette benutzt `SamKirkland/FTP-Deploy-Action`, und die legt
 * ihre Zustandsdatei `.ftp-deploy-sync-state.json` in das ZIELVERZEICHNIS —
 * also in den Webroot, neben `.htaccess`. Darin steht je ausgelieferter
 * Datei Pfad, Groesse und Hash, dazu der Zeitpunkt der letzten Auslieferung.
 *
 * WAS NICHT DARIN STEHT, und das ist der einzige Trost: `config.php`,
 * `install.lock`, `wartung.lock`, `ueberlast.json`, `sicherungen/` und `apk/`
 * stehen in der Ausnahmeliste der Kette, werden also nie ausgeliefert und
 * tauchen folglich nicht auf. Es ist kein Schluesselleck. Es ist die
 * vollstaendige Struktur und — ueber die Hashes — der Versionsstand jeder
 * einzelnen Datei, also die Vorlage fuer einen gezielten Abgleich gegen
 * bekannte Schwachstellen.
 *
 * ZWEI SCHRANKEN, WIE BEI DEN NACHWEIS-DATEIEN. Erstens legt `state-name` die
 * Datei eine Ebene UEBER den Webroot; zweitens sperrt `server/.htaccess`
 * Punktdateien pauschal. Die zweite ist nicht Zierde: Erlaubt der Kaefig des
 * FTP-Zugangs kein `../`, landet die Datei wieder hier — und die Sperre
 * faengt sie. Sie faengt ausserdem jede kuenftige Punktdatei, an die niemand
 * denkt.
 *
 * DIE AUSNAHME `.well-known/` IST DER GEFAEHRLICHSTE TEIL DER AENDERUNG. Eine
 * pauschale Punktdatei-Sperre erschlaegt die ACME-Herausforderung und damit
 * die Zertifikatserneuerung — lautlos, bis das Zertifikat in bis zu 90 Tagen
 * ablaeuft. Deshalb prueft Stufe 2 der Kette BEIDE Richtungen: 403 fuer vier
 * Punktpfade, und **404 und nicht 403** fuer `.well-known/acme-challenge/`.
 *
 * WARUM DIE PRUEFUNG UEBERHAUPT ETWAS BEWEIST: `RewriteRule [F]` antwortet
 * 403, OB DIE DATEI DA IST ODER NICHT — mod_rewrite laeuft vor der
 * Dateisuche. Ein 404 wuerde dagegen auch von einer leeren Adresse kommen.
 * Genau daran ist die erste Abfrage zu diesem Befund gescheitert: Sie lief
 * gegen ein noch leeres Staging, gab 404, und das sah aus wie Entwarnung.
 *
 * KEINE MIGRATION. Kein Code der Anwendung ist angefasst — `.htaccess`,
 * die Kette und `.gitignore`.
 *
 * ---------------------------------------------------------------------------
 * 20.15.2 — `install.php` GEHOERT NICHT MEHR ZUR AUSLIEFERUNG
 * ---------------------------------------------------------------------------
 *
 * Backlog Nr. 214, auf Anweisung der Betreiberin vom 16.09.2026. Das Runbook
 * sagt zur Neuinstallation seit jeher "Nach Erfolg sperrt install.lock;
 * install.php danach loeschen" — und die Kette machte genau das bei jedem
 * Lauf wieder rueckgaengig. Jetzt steht `install.php` in der Ausnahmeliste
 * beider FTPS-Schritte, neben `config.php` und `install.lock`.
 *
 * WARUM DAS NICHT DER SCHUTZ IST, DER OHNEHIN SCHON GREIFT. `install.php:136`
 * verweigert sich selbst, solange `config.php` ODER `install.lock` existiert
 * ("Die Anwendung ist bereits eingerichtet"). Die Datei war also nie
 * gefaehrlich, nur ueberfluessig — und eine Datei, die das Runbook loeschen
 * heisst, gehoert nicht in eine Auslieferung, die sie zurueckbringt.
 *
 * DER PREIS, BENANNT: Eine LEERE Anlage laesst sich nicht mehr allein ueber
 * die Kette einrichten. `server/install.php` muss einmal von Hand hinauf,
 * dann wird eingerichtet, dann wird sie wieder geloescht. Ein Fehler IM
 * Einrichter erreicht ausserdem keinen Server mehr ueber die Kette — auch er
 * braucht dann den Handgriff. Beides ist in `docs/Technik.md`
 * (Neuinstallation) und im Backlog festgehalten, und Stufe 2 der Kette sagt
 * es von selbst: Landet der Aufruf auf `install.php` und antwortet die mit
 * 404, nennt die Fehlermeldung Nr. 214 und den Handgriff statt den
 * FTP-Zielpfad zu verdaechtigen.
 *
 * WARUM EINE NUMMER FUER EINE AENDERUNG OHNE SERVERDATEI. Angefasst sind nur
 * `.github/` und Dokumentation, und `CLAUDE.md` 2 stuft dafuer sonst nicht
 * hoch. Hier aendert sich aber, WAS auf dem Server landet — eine Datei
 * weniger —, und das ist eine Aussage ueber die Auslieferung selbst. Der
 * Changelog braucht dafuer eine Ueberschrift, unter der eine Betreiberin sie
 * findet.
 *
 * KEINE MIGRATION.
 *
 * ---------------------------------------------------------------------------
 * 20.15.3 — DIE ANWENDUNG LIESS SICH NICHT MEHR INSTALLIEREN (Backlog Nr. 223)
 * ---------------------------------------------------------------------------
 *
 * Gefunden beim Aufbau des Pruefstands fuer P5b, am Zweigstand nach P5a.
 * `install.php` antwortete HTTP 500 mit leerem Rumpf — kein Formular, keine
 * Meldung, nichts.
 *
 * DIE KETTE: `install.php` laedt `ui.php`, damit ihr Formular aussieht wie die
 * Anwendung. `ui_seite_start()` laedt seit P5a/AP4 `kopfzeilen_lib.php`, damit
 * die Kopfzeilen vor der ersten Ausgabezeile stehen. `kopfzeilen_lib.php` lud
 * `db.php`, und `db.php` verlangt `config.php` hart (`$CFG = require ...`).
 * Vor der Einrichtung gibt es keine `config.php` — das ist der Zweck der
 * Einrichtung. Fatal Error.
 *
 * WARUM ES NIEMAND GESEHEN HAT. Der Fehler trifft ausschliesslich die
 * Installation, die noch nicht stattgefunden hat. Jede bestehende Anlage hat
 * eine `config.php` und laeuft weiter; jedes Pruefmittel des Projekts setzt
 * eine laufende Installation voraus und richtet keine ein. Sichtbar wurde er
 * erst, als `tools/referenzdatensatz/einspielen/lokal_einrichten.sh` den Weg
 * einer Betreiberin ging — dieselbe Seite, dasselbe Formular.
 *
 * DIE BEHEBUNG steht in `kopfzeilen_lib.php` und nicht in `db.php`: Dort ist
 * das harte `require` richtig, weil jede regulaere Seite nach der Einrichtung
 * laeuft. `kopfzeilen_lib.php` dagegen sagt in ihrem eigenen Kopf, sie komme
 * „ohne Datenbank aus: Jede Einstellung hat eine Vorgabe, und faellt die
 * Abfrage aus, gilt die". Sie kam nur nicht ohne `config.php` aus. Jetzt zieht
 * sie `db.php` per `is_file()` nur, wenn es etwas zu ziehen gibt, und ihre
 * vier `app_state`-Aufrufe stehen hinter `function_exists()` mit Rueckfall auf
 * dieselben Vorgaben — Report-Only und ein Tag HSTS, die vorsichtigen Werte.
 * Nachgemessen: Mehr als diese beiden Funktionen braucht sie aus `db.php`
 * nicht.
 *
 * ZUM VERHAELTNIS ZU 20.15.2. Die beiden Befunde sind am selben Tag
 * entstanden, betreffen dieselbe Datei und sind trotzdem verschiedene Dinge:
 * 20.15.2 nimmt `install.php` aus der AUSLIEFERUNG, weil das Runbook sie
 * loeschen heisst. Dieser Eintrag macht sie ueberhaupt erst wieder
 * LAUFFAEHIG. Seit 20.15.2 muss sie von Hand hinauf — und eine Datei, die
 * man von Hand hinauflaedt, um genau einmal eine Anlage einzurichten, MUSS
 * beim ersten Aufruf funktionieren. Die beiden Fassungen greifen ineinander;
 * ohne diese hier waere die andere der Weg in eine Sackgasse.
 *
 * KEINE MIGRATION.
 * ---------------------------------------------------------------------------
 * 20.16.0 — DIE JOB-PAUSE IST JETZT AUCH UEBER DIE ADRESSE ERREICHBAR
 * ---------------------------------------------------------------------------
 *
 * Backlog Nr. 219. `jobs.php` nimmt eine fuenfte Aktion: `pause` mit
 * `sekunden=N` (0 hebt auf). Dahinter steht derselbe `jobs_pause()` aus
 * `jobs_lib.php` wie hinter `php jobs.php --pause` und hinter den beiden
 * Knoepfen unter Betrieb -> Hintergrundjobs — kein vierter Mechanismus,
 * nur ein vierter Aufrufer.
 *
 * WOFUER. Der Kreislauftest haelt die Hintergrundjobs an, bevor er ein
 * Backup in ein frisches Konto spielt; sonst duennt der Verdichtungsjob die
 * wiederhergestellten Spuren aus, und der Vergleich misst „hat der Job
 * dazwischen zugeschlagen" statt „kommt zurueck, was hineinging"
 * (nachgemessen: 125 verdichtete Spuren in einem Lauf ohne Pause).
 * Angehalten wurde bisher NUR ueber die Kommandozeile — und die setzt
 * voraus, dass das Pruefmittel auf demselben Rechner laeuft wie die
 * Installation.
 *
 * Stufe 2 der Auslieferungskette tut das nicht: Sie laeuft auf einem
 * GitHub-Laeufer gegen ein fernes Staging. Dort gibt es keine `config.php`,
 * und der erste echte Lauf nach dem Merge von PR #51 brach ab mit
 * „require_once(.../server/config.php): Failed to open stream". Der Aufruf
 * war nicht falsch geschrieben — das WERKZEUG nahm an, `--basis` sei
 * derselbe Rechner.
 *
 * WAS DAS AN MACHT GIBT, UND WAS NICHT. Wer das Job-Token hat, kann die
 * Jobs bis `JOB_PAUSE_MAX_S` still stellen. Das ist weniger, als er ohnehin
 * schon konnte: `wartung_an` schliesst die ganze Anwendung und haengt seit
 * P5a am selben Token. Daten liest und schreibt die Aktion keine, und der
 * Ratenschutz (`pair`) begrenzt die Versuche wie bei jedem Token-Aufruf.
 *
 * EIN FEHLENDES `sekunden` IST EIN FEHLER UND NICHT NULL — 400 statt
 * Vorgabewert. Denn 0 HEBT die Pause auf: Ein vergessener Parameter gaebe
 * sonst die Jobs frei und meldete dafuer `ok`, und der Aufrufer glaubte, sie
 * stuenden still. Dieselbe Regel gilt in `tor.py` fuer `--sekunden`.
 *
 * DAZU AUF DER WERKZEUGSEITE: `tools/kette/tor.py` bekommt den vierten
 * Unterbefehl `pause` (Selbstprobe 5 -> 10 Faelle), `kreislauf.py` den
 * Schalter `--jobs-token`. Ohne Token bleibt dort alles beim lokalen Weg —
 * wer auf seinem Rechner misst, merkt nichts.
 *
 * KEINE MIGRATION. `app_state` traegt den Schluessel `jobs_pause_bis` seit
 * Web 10.2.0.
 *
 * ---------------------------------------------------------------------------
 * 20.16.1 — WAS EINE UNABHAENGIGE DURCHSICHT AN 20.16.0 GEFUNDEN HAT
 * ---------------------------------------------------------------------------
 *
 * Neunzehn Befunde, jeder einzeln von einem zweiten Durchgang zu widerlegen
 * versucht. Drei davon brechen Zusagen, die 20.16.0 selbst aufgestellt hat:
 *
 * DIE ZIFFERNPRUEFUNG WAR DIE FALSCHE. `!is_numeric($roh) || (int)$roh < 0`
 * liess `sekunden=-0.5` durch: numerisch ja, `(int)"-0.5"` ist 0, und 0 ist
 * nicht kleiner als 0. Der Aufruf hob damit eine laufende Pause auf und
 * quittierte es mit `ok` — genau das, wogegen der Absatz darueber steht.
 * Nachgemessen gegen eine echte Installation: HTTP 200, „Jobs laufen
 * wieder", Pause weg. Jetzt `^\d+$`. Meine eigenen Proben (-5, abc) trafen
 * die Luecke nicht, weil beide schon vorher scheitern; die Luecke lag
 * zwischen ihnen.
 *
 * `rufen()` IN tor.py VERSCHLUCKTE JEDE FEHLERANTWORT. Eine HTTPError ist
 * eine URLError und fiel in den Netzfehler-Zweig — aus einer 400 mit
 * Begruendung wurde `{"_fehler": "HTTP Error 400"}`. Das ist AELTER als
 * diese Aenderung und kostete mehr als eine haessliche Meldung: Der Kommentar
 * in `backup_tor()` sagt „ein falsches Token ... wird beim vierzigsten Mal
 * nicht anders" und bricht bei `error` ab — der Zweig war nie erreichbar,
 * weil `error` nie ankam. Das Tor fragte vierzigmal, gut dreizehn Minuten,
 * und meldete dann „kein fertig" statt „falsches Token".
 *
 * UND DER NEUE SELBSTPROBENFALL BEWIES NICHTS. Er rief `adresse_bauen()`
 * unmittelbar mit einem von Hand geschriebenen Feld auf und mass damit
 * `urlencode`, nicht den Aufrufweg. Streicht man `felder=` in `main()` oder
 * reicht `rufen()` es nicht weiter, blieb die Probe gruen — beides
 * nachgemessen. Jetzt faehrt der Fall den ganzen Weg, nur der Abruf ist
 * ersetzt, und faellt bei beiden Mutationen um.
 *
 * Dazu die Kleinarbeit: der JOBS_TOKEN-Riegel steht jetzt VOR pip und dem
 * Chromium-Download (sonst wird ein fehlendes Token als Playwright-Fehler
 * sichtbar), `--jobs-token` hat keine stille Vorgabe aus der Umgebung mehr,
 * die Selbstprobe meldet nicht laenger „fuenf Lagen" und faehrt elf, und die
 * Geheimnis-Tabelle in `docs/Technik.md` war von einem eingeschobenen
 * Absatz zerrissen.
 *
 * KEINE MIGRATION.
 *
 * ---------------------------------------------------------------------------
 * 20.16.2 — DER WARTUNGSSCHALTER DES BILDERLAUFS, DRITTER FALL DERSELBEN
 *           ANNAHME
 * ---------------------------------------------------------------------------
 *
 * Backlog Nr. 220. `aufnehmen.mjs` schaltete den Wartungsmodus durch Anlegen
 * der Datei `server/wartung.lock` IM EIGENEN ARBEITSBAUM. Im Kopf der Stelle
 * stand die Annahme sogar wortwoertlich: „Der Bilderlauf laeuft auf derselben
 * Maschine wie die Installation und legt sie deshalb selbst an."
 *
 * Seit Stufe 2 der Kette stimmt das nicht mehr. Gemessen am 17.09.2026 gegen
 * Staging: acht Aufnahmen ohne Bild, acht Konsolenfehler, „Seite leitete auf
 * die Anmeldung um" — `index.php` antwortet ohne Wartung mit 302 statt 503.
 *
 * ES SIND ZWEI SEITEN, NICHT EINE, und die zweite ist die unangenehmere:
 * `07-wartungsseite` faellt auf, weil ihre Bilder ausbleiben. Bei
 * `46a-betrieb-updates-wartung` entstehen acht Bilder, die den Wartungsbalken
 * NICHT zeigen — eine stille Fehlmessung, die „kein Ueberlauf" meldet.
 *
 * ZWEI WEGE, wie bei `kreislauf.py`: mit `--jobs-token` ueber
 * `jobs.php?aktion=wartung_an` (gefahren von `tools/kette/tor.py`), ohne
 * Token weiter ueber die Datei. Dazu ein RIEGEL: Ist die Basis nicht diese
 * Maschine und fehlt das Token, bricht der Lauf ab, statt Bilder der
 * Anmeldeseite abzulegen.
 *
 * KEIN SERVERCODE ANGEFASST. Die Nummer steigt trotzdem, weil dieselbe
 * Ueberlegung wie bei 20.15.2 gilt: Hier aendert sich, was die Kette ueber
 * die Anwendung AUSSAGEN kann — und der Changelog braucht eine Ueberschrift,
 * unter der eine Betreiberin es findet.
 *
 * DAZU Nr. 221, NICHT BEHOBEN, SONDERN MESSBAR GEMACHT. Der Bilderlauf meldete
 * „Ueberlauf bei 360" auf `05-datenschutz`. Oertlich nicht nachstellbar;
 * ausgeschlossen wurde: Markup (die Seite ist byteidentisch, 2104 B),
 * Stylesheet (200 452 B, identisch), alle zehn Schriftdateien (vorhanden,
 * identisch), der Massstab (weder 1 noch 2 laeuft ueber). Der Bericht mit der
 * Spalte VERURSACHER wurde auf dem Laeufer weggeraeumt — er steht jetzt in der
 * Zusammenfassung des Laufs. Eine Zahl ohne Verursacher ist ein Befund, dem
 * niemand nachgehen kann.
 *
 * KEINE MIGRATION.
 *
 * ---------------------------------------------------------------------------
 * 20.16.3 — DIE DURCHSICHT FAND EINEN SCHLIMMEREN FEHLER ALS DEN BEHOBENEN
 * ---------------------------------------------------------------------------
 *
 * Zehn Befunde, jeder von einem zweiten Durchgang zu widerlegen versucht.
 * Der erste wiegt schwerer als Nr. 220 selbst:
 *
 * EIN MISSLUNGENES AUSSCHALTEN HAETTE STAGING GESCHLOSSEN — UND DER LAUF
 * HAETTE GRUEN GEMELDET. Der `catch` in `wartungAus()` setzte `wartungVonUns`
 * auf falsch und entwaffnete damit JEDEN weiteren Versuch: Die Funktion
 * laeuft nach jeder Seite und noch einmal am Prozessende, beide kehrten
 * danach sofort um. Der Rueckgabewert kannte den Fehlschlag nicht, der
 * Bericht auch nicht — der einzige Hinweis waere eine Zeile in einem
 * Protokoll mit hunderten gewesen. Der Kommentar darueber versprach genau das
 * Gegenteil („das muss auffallen"). Jetzt bleibt die Merkung stehen, der
 * naechste Versuch kommt, und ein haengender Wartungsmodus faerbt den Lauf
 * rot.
 *
 * UND DIE MERKUNG STAND HINTER DEM EINSCHALTEN. Ueber eine Datei ist das
 * gleichgueltig — `writeFileSync` schreibt oder wirft. Ueber HTTP gibt es
 * einen DRITTEN Ausgang: ausgefuehrt, aber nicht bestaetigt. Eine verlorene
 * Antwort haette die Anlage geschlossen zurueckgelassen, ohne dass jemand
 * ausschaltet. Sie steht jetzt VOR dem Aufruf.
 *
 * DAZU: Ein Schluckauf der Leitung warf den ganzen Lauf weg, samt Bericht
 * ueber die 48 gelungenen Seiten. Der Wartungsschalter wirft nicht mehr; ein
 * Fehlschlag laesst die betroffene Seite AUSFALLEN, und das geht in Bericht
 * und Rueckgabewert. Aus demselben Grund bricht der Lauf bei fehlendem Token
 * nicht mehr ab (Rueckgabewert 2), sondern misst die uebrigen und endet rot.
 * Zwei von fuenfzig Seiten sind kein Grund, achtundvierzig wegzuwerfen.
 *
 * Dokumentation: `docs/Technik.md` 6.3 sagte fuer dasselbe fehlende Token
 * zweierlei, und ein eingeschobener Absatz hatte den Satz ueber die
 * Kreislaeufe zerrissen — zum zweiten Mal dieselbe Art Fehler. Die Wegtabelle
 * in der LIESMICH war nach dem ORT geschluesselt, der Code entscheidet nach
 * dem TOKEN. Und ein Kettenkommentar nannte einen roten Lauf „den ersten
 * gruenen Durchlauf".
 *
 * KEINE MIGRATION.
 *
 * ---------------------------------------------------------------------------
 * 20.16.4 — VIER STELLEN, AN DENEN DIE DOKUMENTATION EINE ANDERE KETTE
 *           BESCHRIEB ALS DIE GEBAUTE
 * ---------------------------------------------------------------------------
 *
 * Kein Verhalten geaendert, kein Servercode ausser einem Kommentar. Die
 * Nummer steigt trotzdem, und zwar aus genau diesem einen Kommentar:
 * `adminbackup_lib.php` liegt unter `server/`, damit ist es keine Aenderung
 * mehr, die nur `tools/` und `docs/` anfasst (`CLAUDE.md` 2).
 *
 * DER MESSSTAND-SATZ BEHAUPTETE ZWEI DINGE, DIE BEIDE NICHT ZUTREFFEN.
 * `docs/Technik.md` 6.3 nannte als Teil von Stufe 2 „und NUR BEI TAG-LAEUFEN
 * der Messstand". Der Schritt fuehrt seit P5a/AP9 nichts mehr aus (Nr. 206,
 * ersatzlos gestrichen) — und Stufe 2 laeuft bei einem Tag-Lauf ueberhaupt
 * nicht, weil `staging` fuer Tags abgeschaltet ist und `stufe2` mit `needs`
 * daran haengt. Ein Tag laesst allein `produktion` laufen.
 *
 * DIE TABELLE DER STUFE 1 FUEHRTE 13 SCHRITTE, DER LAUF HAT 14. Es fehlte
 * die Zeile fuer `tools/kette/tor.py --selbstprobe`, die mit 20.16.1 aus dem
 * Produktionslauf nach Stufe 1 gezogen wurde.
 *
 * EINE HANDGEPFLEGTE ZAHL, ZUM ZWEITEN MAL VERALTET. Die Kopfzeile der
 * Selbstprobe von `tor.py` meldete bis 20.16.1 „fuenf Lagen" und fuhr zehn;
 * danach stand „fuenf und fuenf" da, und mit dem elften Fall stimmte auch das
 * nicht mehr. Behoben wurde nicht die Zahl, sondern ihre Bauart: Die
 * Kopfzeile nennt jetzt nur noch die GRUPPEN, gezaehlt wird am Ende des
 * Laufs. Eine Zahl, die niemand pflegen muss, kann nicht veralten.
 *
 * SECHS WERKZEUGE BEGRUENDETEN IHRE ARBEITSWEISE MIT `deploy.yml` (Nr. 215).
 * Die Datei ist mit 20.4.0 geloescht; die Kette heisst seither
 * `auslieferung.yml` und hat ZWEI FTPS-Schritte statt einem. Wer den alten
 * Namen las, suchte die Ausnahmeliste, die seine Sicherungen schuetzt, an
 * einer Datei, die es nicht gibt. Nicht angefasst sind die Protokolle
 * (`docs/konzepte/`, Backlog, CHANGELOG) und die Stellen, die den alten Namen
 * ausdruecklich als Historie nennen.
 *
 * KEINE MIGRATION.
 */
/* ---------------------------------------------------------------------------
 * 20.16.5 — DAS BETRIEBSPROTOKOLL BEKOMMT EINEN SCHREIBWEG (P5b/AP1)
 * ---------------------------------------------------------------------------
 *
 * EINE KORREKTURNUMMER FUER EINE NEUE FUNKTION, und das ist eine Abweichung
 * von der Zaehlweise, die begruendet gehoert.
 *
 * Dieses Paket hiess auf seinem Zweig **20.16.0** — geschrieben am
 * 16.09.2026. Am 17.09.2026 vergab `main` dieselbe Nummer fuer etwas
 * anderes: die Job-Pause ueber die Adresse (Backlog Nr. 219), und darauf
 * folgten dort 20.16.1 bis 20.16.4. Zwei Erzaehlungen unter derselben
 * Nummer gibt es nicht; `main` ist vorgelagert, also weicht der Zweig.
 *
 * GEWAEHLT WURDE DIE KLEINE VERSCHIEBUNG: nur dieses Paket rutscht, von
 * 20.16.0 auf 20.16.5 — 21 Nennungen in 9 Dateien. Die Alternative waere
 * gewesen, die ganze Kette P5b um eine Nebennummer hochzusetzen (AP1 auf
 * 20.17.0 bis AP9 auf 20.25.0); das waere nach der Zaehlweise sauberer und
 * haette rund 267 Nennungen angefasst, quer durch Changelog, Technik,
 * Handbuch, Konzept, Pruefdokument und Rahmenplan. Der Auftraggeber hat am
 * 17.09.2026 die kleine Verschiebung gewaehlt.
 *
 * WAS DAS KOSTET, steht hier, damit es niemand fuer einen Fluechtigkeitsfehler
 * haelt: Die dritte Stelle heisst sonst „Fehlerbehebung und Feinschliff".
 * Dieses Paket legt eine Tabelle an und baut einen Schreibweg — es ist eine
 * Nebenversion, die eine Korrekturnummer traegt. Wer die Erzaehlung dieser
 * Datei liest, findet unter 20.16.5 mehr, als die Nummer verspricht.
 *
 * Erstes Paket der Phase P5b (Konto und Registrierung). Es baut nichts, was
 * eine Nutzerin sieht — es baut das, worauf die neun folgenden Pakete
 * schreiben.
 *
 * DREI STUECKE:
 *
 * 1. `protokoll_lib.php` und die Tabelle `protokoll_ereignisse`. Sechs
 *    Reiter, zwei Fristen (Verwaltung 365 Tage einstellbar, alle uebrigen 30
 *    fest). Der siebte Reiter — Sicherheit — bleibt in seiner eigenen Tabelle
 *    aus P5a; zwei Fristen in einer Tabelle sind eine Einladung, die kuerzere
 *    zu vergessen.
 *
 * 2. `konten_einstellungen_lib.php` und die Karte „Konten" in den
 *    Servereinstellungen. Acht Werte, von denen in diesem Paket nur einer
 *    einen Verbraucher hat (Demo-Anmeldung). Die uebrigen stehen trotzdem
 *    schon da, weil die Reihenfolge es verlangt: Eine Einstellung, die es
 *    beim Bauen ihres Verbrauchers noch nicht gibt, wird dort erfunden — an
 *    einer zweiten Stelle, mit einer zweiten Vorgabe.
 *
 * 3. Die Zaehlkarte auf Betrieb -> Status. Lesbar wird das Protokoll erst mit
 *    10c; bis dahin belegt die Karte, DASS geschrieben wird — und meldet
 *    rot, wenn es nicht geht.
 *
 * WAS HIER NICHT HINEINGESCHRIEBEN WIRD, ist die eigentliche Aussage: kein
 * Zugriffsprotokoll. Dass jemand einen Einsatz geoeffnet, gelesen oder
 * exportiert hat, steht nicht darin und soll nicht darin stehen (V1,
 * 16.09.2026). Wer das aendert, aendert eine Programmentscheidung.
 *
 * SCHEITERT DAS SCHREIBEN, SCHEITERT DIE HANDLUNG NICHT (V7) — aber es faellt
 * auf: `error_log()`, ein Zaehler in `app_state`, eine rote Plakette auf der
 * Statusseite. Alle drei, weil still scheitern schlechter ist als laut und
 * laut abbrechen schlechter als still.
 *
 * MIGRATION: `2026_09_16_protokoll_ereignisse`. `update.php` ist faellig.
 */
/* ---------------------------------------------------------------------------
 * 20.17.0 — DER LEBENSZYKLUS EINES KONTOS (P5b/AP2)
 * ---------------------------------------------------------------------------
 *
 * `konto_lib.php`, und damit ist Backlog Nr. 202 Paket 1 (Token) erledigt.
 *
 * VIER FASSUNGEN DERSELBEN SACHE gab es vorher, und sie waren nicht gleich:
 * `admin_users.php` legte Konto und Token in einer Transaktion an,
 * `install.php` ohne; zwei Stellen entwerteten die Vorgaengertoken, zwei
 * nicht; die Laufzeiten standen als SQL-Literale an vier Stellen.
 *
 * `install.php` WAR DIE GEFAEHRLICHE. Ein Abbruch zwischen den beiden
 * `INSERT` hinterliess ein Konto ohne Weg hinein — anmelden ging nicht (kein
 * Passwort), und der Einrichter lief nicht mehr, weil `install.lock` stand.
 * Eine Installation, aus der man sich beim Einrichten selbst ausgesperrt hat.
 *
 * AUFGELOEST WURDE ES HIER UND NICHT IN SCHRITT 15, weil die
 * Selbstregistrierung aus AP3 sonst die fuenfte Fassung geworden waere — und
 * sie ist die einzige, die von aussen erreichbar ist.
 *
 * DIE BIBLIOTHEK LAEUFT OHNE `config.php`. `install.php` schreibt sie erst,
 * nachdem es das erste Konto angelegt hat, und bringt seine eigene
 * PDO-Verbindung mit. Dieselbe Falle wie Nr. 223, hier von vornherein
 * vermieden statt hinterher behoben.
 *
 * VIER ZUSTAENDE, und die Uebergaenge stehen als TABELLE statt als
 * `if`-Zweige. Die Rueckwege sind die interessanten: aus `gesperrt` nach
 * `aktiv` ja, aus `aktiv` nach `unbestaetigt` nein.
 *
 * ENTSPERREN RAEUMT `loeschung_am` MIT WEG. Ohne das faende der Loeschjob
 * einen Termin in der Vergangenheit und loeschte ein Konto, dessen
 * Besitzerin die Loeschung gerade zurueckgenommen hat — kein Schoenheits-,
 * sondern ein Datenverlustfehler.
 *
 * DIE SELBSTLOESCHUNG IST DER SONDERFALL: Waehrend der Karenz ist das Konto
 * `gesperrt`, aber DIE ANMELDUNG IST DER RUECKZUG (E-P5b-16). `login.php`
 * nimmt sie zurueck, statt abzuweisen.
 *
 * `ingest.php` ANTWORTET `403` MIT GRUND IM RUMPF. Die Uhr puffert dann und
 * schickt nach dem Entsperren alles nach — gemessen: zwei Einsaetze, einer
 * davon waehrend der Sperre abgewiesen, beide angekommen. Der Ratenschutz
 * zaehlt die Absage NICHT mit; sonst sperrte er eine Kennung, die nichts
 * falsch macht, und der Rueckstand kaeme nicht durch.
 *
 * MIGRATION: `2026_09_16_konto_lebenszyklus`. `update.php` ist faellig.
 */
/* ---------------------------------------------------------------------------
 * 20.18.0 — DIE DEMO-ANMELDUNG LAESST SICH ABSCHALTEN (P5b/AP7, E-P5b-07)
 * ---------------------------------------------------------------------------
 *
 * EINE ZEILE IN `login.php`, und die Stelle ist die ganze Ueberlegung: Bei
 * abgeschalteter Demo-Anmeldung wird `$u` auf `false` gesetzt, unmittelbar
 * nach dem `SELECT`. Ab da laeuft die Anfrage durch GENAU DENSELBEN Weg wie
 * eine erfundene Adresse — Blindvergleich gegen `AUTH_VERGLEICHSWERT`,
 * Fehlversuch im Topf, `rate_gleiche_dauer()` am Ende.
 *
 * WARUM NICHT WEITER UNTEN, MIT EIGENER MELDUNG: Jede eigene Meldung und
 * jeder eigene Zweig macht die beiden Faelle wieder unterscheidbar — an der
 * Antwort oder an der Dauer. Eine Auskunft „das Demo-Konto ist abgeschaltet"
 * waere freundlicher und genau deshalb falsch.
 *
 * GEMESSEN IM BROWSER (nicht mit curl — ohne die im Browser abgeleiteten
 * Token scheitert JEDE Anmeldung, und eine Messung davon belegte nichts):
 * Demo mit RICHTIGEM Passwort 1241 ms, Demo mit falschem 1270 ms, erfundene
 * Adresse 1263 ms — dieselbe Meldung, Spanne 29 ms. Das Adminkonto meldet
 * sich in derselben Lage normal an.
 *
 * DER BESTAND BLEIBT (R25). Abgeschaltet ist die ANMELDUNG, nicht das Konto.
 *
 * DIE RUECKFRAGE BEIM UMSCHALTEN steht als Meldung mit Knopf und nicht als
 * Bestaetigungsdialog: `data-confirm` kann nur ja/nein ZUM ABSENDEN, nicht
 * „und schalte dabei noch etwas anderes ab". Ein Dialog, der das koennte,
 * waere ein NEUER Baustein und braeuchte eine Freigabe mit Mockup
 * (Design.md 9).
 *
 * KEINE MIGRATION — die Einstellung steht seit 20.16.5 in `app_state`.
 */
/* ---------------------------------------------------------------------------
 * 20.19.0 — EINWILLIGUNGEN (P5b/AP4, E-P5b-05, -15)
 * ---------------------------------------------------------------------------
 *
 * VIER RECHTSTEXTE STATT ZWEI. Nutzungsbedingungen und die Vereinbarung zur
 * Auftragsverarbeitung kommen dazu; `nutzungsbedingungen.php` und `avv.php`
 * sind zwei Zeilen nach dem Muster von `impressum.php`.
 *
 * DREI HAEKCHEN, ZWEI WIRKUNGEN, und der Unterschied ist kein Rang, sondern
 * die Rechtsnatur: Ein Vertrag kommt durch ANNAHME zustande und darf ohne sie
 * nicht weiterlaufen — deshalb sperren Nutzungsbedingungen und AVV den
 * naechsten Login. Eine Datenschutzerklaerung informiert; Widerspruch dagegen
 * ist kein Vertragsschluss, sondern ein Recht — deshalb zeigt sie nur einen
 * Hinweis. Der Wortlaut traegt das („angenommen" gegen „zur Kenntnis
 * genommen") und steht im Katalog `RT_EINWILLIGUNG`, nicht im Markup.
 *
 * DREI WEGE BLEIBEN AM TOR OFFEN: Abmelden, Export, Konto loeschen. Wer nicht
 * zustimmen will, muss an seine Daten kommen und gehen koennen; ein Tor, das
 * auch den Ausgang versperrt, waere Noetigung.
 *
 * `stand_am` WIRD DATETIME (Fehlerfund F3 des Konzepts). Es war `DATE` —
 * zwei Aenderungen am selben Tag waeren EINE Fassung gewesen, und wer die
 * erste angenommen hat, gaelte als Annehmer der zweiten. Nachgemessen: Nach
 * der Umstellung sperrt eine zweite Fassung am selben Tag erneut.
 *
 * EIN TEXT OHNE STANDDATUM VERLANGT NICHTS. Sonst sperrte ein leer
 * angelegter Platzhalter alle Konten aus — genau der Zustand zwischen dem
 * Einspielen der Mechanik und dem Einspielen der geprueften Texte (R41).
 *
 * `ingest.php` UND DIE API BLEIBEN UNBERUEHRT. Die Uhr fragt niemanden um
 * Zustimmung; ein Tor davor liesse eine laufende Aufzeichnung ins Leere
 * laufen, ohne dass irgendwo jemand einen Haken setzen koennte. Gemessen:
 * Ingestprobe 83 Erwartungen, 0 Fehlschlaege, bei leerer
 * `konto_einwilligungen`.
 *
 * NEBENBEI BEHOBEN: In `rechtstext_seite.php` stand der Leerzustandstext als
 * ZWEIWERTIGER ternaerer Ausdruck — ein dritter Schluessel haette gemeldet,
 * es sei „noch keine Datenschutzerklaerung hinterlegt". Kein Fehler, keine
 * Meldung, nur ein falscher Satz. Jetzt ein Katalog (`RT_LEERTEXT`).
 *
 * MIGRATION: `2026_09_16_einwilligungen`. `update.php` ist faellig.
 */
/* ---------------------------------------------------------------------------
 * 20.20.0 — SELBSTLOESCHUNG MIT KARENZ, ADRESSWECHSEL MIT BESTAETIGUNG
 * ---------------------------------------------------------------------------
 *
 * P5b/AP5, E-P5b-16.
 *
 * DIE ADRESSE WIRD NICHT MEHR SOFORT GESCHRIEBEN. Bis Web 20.19.0 stand sie
 * unmittelbar in der Zeile — mit Passwortnachweis und einer Hinweismail an
 * die alte, aber OHNE jede Pruefung, ob die neue ueberhaupt erreichbar ist.
 * **Ein Tippfehler sperrte damit aus**: Die Anmeldung laeuft ueber die
 * Adresse, und „Passwort vergessen" schickt an eine Adresse, die es nicht
 * gibt. Jetzt geht ein Link an die NEUE Adresse (24 h), eine Warnung an die
 * ALTE, und **die alte bleibt die gueltige, bis der Klick kommt**.
 *
 * KEIN UNIQUE AUF `email_neu`. Zwei Konten duerfen dieselbe Adresse
 * vormerken; erst der Klick entscheidet, und dort faengt das UNIQUE auf
 * `email`. Eine Sperre schon beim Vormerken verriete, dass jemand anders
 * dieselbe Adresse vorgemerkt hat.
 *
 * SELBSTLOESCHUNG MIT DREISSIG TAGEN KARENZ, und **die Ruecknahme ist die
 * ANMELDUNG** — kein Knopf, kein zweiter Link, kein zweites Token. Ein
 * Ruecknahmeweg ohne Passwort waere genau das, was ein Angreifer wollte, der
 * die Loeschung verhindern will, um weiter mitzulesen.
 *
 * ENTSPERREN RAEUMT DEN TERMIN MIT WEG (schon seit 20.17.0) — ohne das faende
 * der Job einen Termin in der Vergangenheit und loeschte ein Konto, dessen
 * Besitzerin die Loeschung gerade zurueckgenommen hat.
 *
 * DER JOB LOESCHT HOECHSTENS FUENF JE LAUF. Eine Kontoloeschung raeumt die
 * Spuren von Hand, loescht einen Ordner im Dateisystem und kaskadiert ueber
 * vierzehn Tabellen; das Huckepack-Budget sind drei Sekunden fuer ALLE Jobs.
 * Einen Tag spaeter zu loeschen ist kein Zusagenbruch, eine haengende
 * Anfrage schon.
 *
 * DAS PROTOKOLL NENNT KEINE ADRESSEN beim Wechsel — nur die Kontonummer und
 * dass gewechselt wurde. Ein Audit, in dem jede je benutzte Adresse eines
 * Kontos steht, ist ein Verzeichnis von Adressen und nicht eines von
 * Handlungen. Gemessen: der Eintrag enthaelt kein `@`.
 *
 * MIGRATION: `2026_09_16_adresswechsel_bestaetigt`. `update.php` ist faellig.
 */
/* ---------------------------------------------------------------------------
 * 20.21.0 — MENGENGRENZE JE KONTO (P5b/AP6, E-P5b-04, -18; Nr. 37, 48)
 * ---------------------------------------------------------------------------
 *
 * `507 Insufficient Storage`, und der Code ist mit Bedacht gewaehlt: Er sagt
 * „der Server hat keinen Platz mehr", und genau das ist der Fall. `403`
 * hiesse „du darfst nicht", `429` hiesse „nicht so schnell" — beides waere
 * falsch und liesse die Uhr das Falsche tun. Bei `507` wie bei `429` behaelt
 * sie ihre Warteschlange; gemessen: Nach Anheben der Grenze kam die
 * abgewiesene Aufzeichnung vollstaendig nach.
 *
 * BEARBEITEN UND LOESCHEN BLEIBEN FREI. Die Grenze steht in `ingest.php` und
 * im Import — nirgends sonst. Wer sie erreicht, muss aufraeumen koennen;
 * eine Grenze, die auch das Loeschen sperrt, ist eine Falle.
 *
 * DER GROESSERE DER BEIDEN ANTEILE ZAEHLT, nicht der Durchschnitt: Wer 5000
 * Einsaetze mit wenigen GPS-Daten hat, ist genauso am Ende wie jemand mit
 * 250 MB in dreihundert Aufzeichnungen.
 *
 * GECACHT, WEIL DIE MESSUNG TEUER IST. Sie liest die Blob-Laengen aller
 * GPS-Daten eines Kontos; bei jedem Upload waere das genau an dem Weg teuer,
 * der schnell sein muss. `ingest.php` schreibt den Zaehler nach dem Schub
 * GESCHAETZT fort, der Aufraeumjob misst einmal am Tag nach.
 *
 * `spur_bytes()` STEHT IN `spur_lib.php` UND NICHT BEIM AUFRUFER
 * (CLAUDE.md 4). Die Punkte liegen je nach Alter als Zeilen ODER als Blob —
 * wer nur eine der beiden Tabellen zaehlt, misst je nach Bestand die
 * Haelfte, und zwar ohne Fehlermeldung.
 *
 * E-P5b-17 IST GEGENSTANDSLOS. Das Konzept sieht die Umstellung der
 * Geraeteschluessel von bcrypt auf SHA-256 vor — sie ist seit Web 13.0.0
 * erledigt (`db.php`, mit der Messung: bcrypt kostete 228 ms JE UPLOAD).
 * Nachgemessen am Code, nicht angenommen.
 *
 * UND EIN BEFUND AUS DEM PRUEFEN: `konto_loeschen()` liess `mengen:<id>` und
 * `mengen_gemeldet:<id>` in `app_state` stehen. `users.id` ist
 * AUTO_INCREMENT, aber ein Wiederanlauf aus einer Sicherung kann eine Id
 * erneut vergeben — das neue Konto faende dann den Mengenstand des alten vor
 * und stuende womoeglich sofort an seiner Grenze, ohne einen einzigen
 * Einsatz. Genau das ist beim Pruefen passiert. Behoben an der Wurzel, dazu
 * ein Aufraeumschritt fuer den Altbestand (3 verwaiste Eintraege gefunden,
 * 0 danach).
 *
 * MIGRATION: `2026_09_16_konto_grenzen`. `update.php` ist faellig.
 *
 * 20.21.1 — DIE PROFILSEITE BRACH AUS IHREM GERUEST AUS (Backlog Nr. 225).
 *
 * Ein `ui_karte_ende()` zu viel, seit dem 07.09.2026. Es schloss keine Karte,
 * sondern gab ein `</div></section>` ohne Gegenstueck aus; der Parser nahm
 * fuer das `</div>` das naechste offene, und das war `div.rahmen`. Damit
 * endeten `form`, `main.inhalt` und `rahmen` mitten auf der Seite, und alles
 * danach — Datenschutz, Passwort, Mengen, Loeschkarte — lag direkt am `body`
 * und lief unter der Seitenleiste hindurch ueber die volle Fensterbreite.
 *
 * KEIN PRUEFMITTEL HAT ANGESCHLAGEN, und das ist der eigentliche Befund:
 * `scrollWidth` blieb gleich `innerWidth` (es lief nichts ueber, es lag nur
 * falsch), die Konsole blieb still, die Knopfhoehen stimmten. Gefunden beim
 * ANSEHEN eines Bildes. Die Gegenprobe zaehlt seither Karten ausserhalb von
 * `main.inhalt` — voller Lauf ueber 53 Seiten: 149 geprueft, 0 ausserhalb.
 * Gegenprobe mit wieder eingebautem Fehler: dieselben drei Nullen und
 * „6 geprueft, 4 ausserhalb".
 *
 * DAZU ZWEI KLEINERE FUNDE DESSELBEN ABENDS. Ein Meldungskasten in
 * `betrieb_server.php` trug den Ton `meldung-blau`, den es nicht gibt — die
 * Toene heissen `fehler, warn, ok, info, schutz` —, und stand deshalb
 * ungestaltet da (Nr. 226). Und `einwilligung.php` und
 * `adresse_bestaetigen.php` fehlten in der Geruest-Ausnahmeliste der
 * Vollstaendigkeitspruefung; beide lassen das Geruest mit Absicht weg.
 * 20.22.0 — DIE SELBSTREGISTRIERUNG (P5b/AP3, E-P5b-01, -02, -03, -13, -23).
 *
 * `registrieren.php` und `bestaetigen.php`, drei Betriebsarten (offen / offen
 * mit Freischaltung / nur auf Einladung, Vorgabe die letzte), Double-Opt-In
 * mit 48-Stunden-Link, Wegwerfliste (8 883 Domains, CC0), drei Ratenschutz-
 * Toepfe, Honeypot mit signiertem Zeitstempel, Freischaltung mit Sammelmail
 * und der Job `konto_verfall` fuer beide Fristen.
 *
 * DIE SEITE GIBT KEINE KONTOAUSKUNFT. Freie, belegte, Wegwerf- und
 * Demo-Adresse bekommen dieselbe Karte und — gemessen ueber 120 Aufrufe —
 * dieselbe Antwortzeit: Mediane 507,5 / 507,7 / 507,6 ms, Spanne **0,2 ms**
 * (Soll < 50). Unterschieden wird nur in der Mail.
 *
 * DAS PASSWORT WIRD NICHT AUF DER REGISTRIERUNGSSEITE GESETZT, und das ist
 * die eine Abweichung vom freigegebenen Mockup M-P5b-02a. Der Grund ist
 * zwingend: Der Datenschluessel haengt am Server-Anteil, und der wird per
 * `HMAC(kdf_anteil, 'konto:<id>')` aus der KONTONUMMER abgeleitet (E-S10-17)
 * — die es bei der Registrierung noch nicht gibt. Der Link aus der Mail
 * fuehrt deshalb auf `pw_handling.php`, den einen geprueften Weg, auf dem
 * Passwort, Datenschluessel und Wiederherstellungsschluessel entstehen;
 * danach leitet er auf `bestaetigen.php`. Mit dem Auftraggeber geklaert am
 * 17.09.2026: lieber zwei Felder weniger als ein zweiter Weg, auf dem ein
 * Anteil das Haus verlaesst.
 *
 * KEINE MIGRATION. `users.status` kennt `unbestaetigt` und `wartet` seit AP2,
 * und `konto_status_setzen()` schreibt beim Uebergang nach `wartet` jetzt
 * `bestaetigt_am` mit — daran haengt die 30-Tage-Frist der Wartenden. Ohne
 * das zaehlte sie ab dem Absenden des Formulars: Wer die Mail erst nach 40
 * Tagen anklickt, waere sofort verfallen.
 *
 * 20.22.1 — DAS HAEKCHEN SAGTE DAS FALSCHE.
 *
 * „Ich habe die Nutzungsbedingungen angenommen." Das ist eine Aussage ueber
 * eine Vergangenheit, die es nicht gibt — angenommen wird in dem Augenblick,
 * in dem der Haken gesetzt und das Formular abgeschickt wird. Jetzt: „Ich
 * habe die Nutzungsbedingungen gelesen und NEHME SIE AN.", „Ich NEHME die
 * Vereinbarung zur Auftragsverarbeitung (AVV) AN.", „Ich habe die
 * Datenschutzerklaerung ZUR KENNTNIS GENOMMEN." — so, wie es das Mockup
 * M-P5b-02a von Anfang an hatte. Angemerkt vom Auftraggeber.
 *
 * DER SATZ STEHT JETZT IM KATALOG (`rt_haken_satz()`), nicht im Markup. Er
 * wird an ZWEI Stellen gebraucht — Registrierungsseite und
 * Einwilligungstor —, und zwei Fassungen desselben rechtlich erheblichen
 * Satzes laufen auseinander, ohne dass es auffiele: Beide sehen fuer sich
 * richtig aus.
 *
 * DAZU ZWEI FUNDE AM TOR. Es fuehrte SCHIEBESCHALTER statt Haekchen — ein
 * Schalter steht fuer eine Einstellung, die man an- und ausmacht, eine
 * Willenserklaerung kennt aber nur eine Richtung (das Mockup zeichnet
 * Haekchen). Und es nannte das Standdatum ZWEIMAL: einmal als „Stand
 * 17.09.2026" und zwei Zeilen darunter im Satz. Geblieben ist, was nur dort
 * steht — welche Fassung bisher angenommen war.
 *
 * 20.22.2 — DIE HAEKCHEN WURDEN VERLANGT UND VERGESSEN (Backlog Nr. 231).
 *
 * `registrieren.php` prueft seit 20.22.0 alle Schluessel aus
 * `RT_EINWILLIGUNG` als Pflichthaken und legt danach das Konto an. Dazwischen
 * fehlte eine Zeile: Es entstand nie ein Eintrag in `konto_einwilligungen`.
 * Der einzige Schreibweg, `einwilligung_setzen()`, wurde im ganzen Server
 * genau einmal aufgerufen — am TOR beim Login, nicht bei der Registrierung.
 * E-P5b-05 verlangt dagegen „Gespeichert je Konto mit Fassungskennung und
 * Zeit".
 *
 * DIE WIRKUNG WAR NICHT, DASS DER NACHWEIS FEHLTE. Das Tor fasst jedes Konto
 * beim ersten Login und schreibt dann; verloren ging nichts. Verloren ging
 * die STELLE: Die Erklaerung entstand nicht dort, wo der Vertrag geschlossen
 * wird, und die Registrierende beantwortete dieselben drei Fragen zweimal —
 * einmal im Formular, einmal beim ersten Anmelden.
 *
 * ENTSCHIEDEN (E-P5b-25): Festgehalten wird BEI DER REGISTRIERUNG. Dort wird
 * der Vertrag geschlossen; das Tor ist dafuer da, eine NEUE Fassung
 * nachzuholen, nicht die erste zu erheben. Dass das Konto in diesem Moment
 * noch `unbestaetigt` ist, steht dem nicht entgegen — festgehalten wird, was
 * an diesem Formular erklaert wurde, nicht, wem die Adresse gehoert. Wird die
 * Registrierung nie bestaetigt, raeumt `job_konto_verfall()` das Konto weg
 * und die Zeilen mit ihm (`ON DELETE CASCADE` an `fk_kew_user`).
 *
 * DER ZWEITE FUND AN DERSELBEN STELLE, und er war der unangenehmere: Die
 * Registrierung prueft `stand_am` nicht — der Begriff kam in
 * `registrieren.php` kein einziges Mal vor. Solange die geprueften Texte
 * nicht eingespielt sind (der geplante Zustand bis E-P5b-24), musste eine
 * Registrierende also den Haken „Ich nehme die Vereinbarung zur
 * Auftragsverarbeitung (AVV) an" setzen, waehrend `avv.php` daneben „noch
 * keine Vereinbarung zur Auftragsverarbeitung hinterlegt." anzeigte. Sie nahm
 * ein leeres Dokument an.
 *
 * Das Tor macht es seit jeher richtig; `docs/Technik.md` begruendet es mit
 * einem Satz, der auch hier gilt: „Ein Text ohne Standdatum verlangt nichts."
 * Neu ist `einwilligung_in_kraft()`, und beide Seiten der Registrierung —
 * Pruefung und Markup — ziehen aus derselben Liste. Solange kein Text
 * hinterlegt ist, zeigt das Formular KEINEN Haken; wer sich so registriert,
 * wird beim ersten Login nach dem Einspielen am Tor gefasst.
 *
 * GEFUNDEN BEIM NACHPRUEFEN einer Rueckfrage des Auftraggebers zur AVV, nicht
 * durch ein Pruefmittel. Es gibt keines, das „verlangt, aber nie
 * gespeichert" messen koennte.
 *
 * 20.23.0 — DAS HANDBUCH IST JETZT EINE SEITE DER ANWENDUNG
 * (P5b/AP8, E-P5b-08, -22, Mockup M-P5b-01).
 *
 * `hilfe.php` zeigt `docs/Handbuch.md`, `ueber.php` zeigt das neue
 * `docs/Was-ist-NAdoku.md` — beide OHNE Anmeldung. Die Quelle bleibt
 * Markdown im Repositorium: auf GitHub editierbar, die Wortliste laeuft
 * darueber, das Prueftor prueft die Rendertauglichkeit, und es gibt keine
 * zweite Fassung in einer Datenbank, die auseinanderliefe. Dazu die
 * Fusszeile der Anmeldeseite (Was ist NAdoku? · Handbuch · Impressum ·
 * Datenschutz) und ein Fragezeichen links vom Zahnrad.
 *
 * NEU VENDORIERT: Parsedown 1.7.4 (MIT, eine Datei). `rt_html()` bleibt, was
 * es ist — es kennt bewusst kein Fett, keine Tabellen, keine Codebloecke und
 * keine Bilder, weil sein Text aus der DATENBANK kommt und jede Erweiterung
 * dort eine Vertragsaenderung waere (E-P3-38). Das Handbuch braucht alle
 * vier. Zwei Renderer sind zwei Angriffsflaechen; der Preis ist bewusst
 * gezahlt, weil die Quellen verschieden sind.
 *
 * DREI FUNDE BEIM PRUEFEN, und alle drei waren meine:
 *
 *   BILDER. `![](bilder/x.png)` im Handbuch loeste zu `/bilder/x.png` auf —
 *   also `server/bilder/`, wo nichts liegt. PHP liest den TEXT aus
 *   `../docs/`; ein BILD holt der Browser, und der sieht nur `server/`.
 *   24 Konsolenfehler im Bilderlauf. `DokuMarkdown` schreibt relative
 *   Bildquellen jetzt auf `doku/` um, die Kette kopiert `docs/bilder/`
 *   dorthin.
 *
 *   TABELLEN. Das Handbuch hat 43; bei 360 px ist die schmalste 368 px breit
 *   und schob die Seite um 124 px nach rechts. Sie scrollen jetzt waagerecht
 *   wie die Codebloecke.
 *
 *   UND DER BILDERLAUF ZEIGTE AUF DEN FALSCHEN. Er nannte `code (626 px)`
 *   als Verursacher — ein `<code>` in einem scrollenden `<pre>`, das seine
 *   Seite gar nicht schiebt. Ich habe daraufhin den Code umbrechen lassen,
 *   und die Zahl blieb bei +124 px, weil sie nie von dort kam. Der
 *   Taeter-Finder ueberspringt jetzt, was in einem scrollenden Kasten
 *   steckt.
 *
 * WAS SAFEMODE NICHT TUT, und das ist der Satz, der hier stehen muss:
 * Parsedown liefert MIT `setSafeMode(true)` fuer
 * `![B](https://fremd.example/b.png)` ein `<img src="https://fremd...">` und
 * laedt damit eine fremde Quelle zur Laufzeit. SafeMode prueft das SCHEMA,
 * nicht die HERKUNFT. Auf einer Seite, die jede Besucherin vor der Anmeldung
 * sieht, haelt die Zusage aus CLAUDE.md 4 allein
 * `DokuMarkdown::inlineImage()`.
 *
 * KEIN CACHE, entgegen E-P5b-22: `app_state.v` ist VARCHAR(190), das
 * gerenderte Handbuch 303 KB — und 266 KB Markdown rendern in 11 bis 12 ms.
 *
 * 20.24.0 — ONBOARDING UND DIE BEIDEN RUECKFRAGEN
 *   (P5b/AP9, E-P5b-09, -10, -19, -20, -21; Mockups M-P5b-02b/c/d).
 *
 *   Vier Dinge koennen nach dem Anmelden anstehen, und die Anwendung zeigte
 *   bisher keines davon. Jetzt zeigt sie GENAU EINES, in dieser Reihenfolge:
 *   Einwilligungstor (eine Seite, seit AP4), Schluesselblatt-Rueckfrage,
 *   Konto-Rueckfrage, Erststart. `server/einstieg_lib.php` sagt, welches.
 *
 *   DER ERSTSTART IST EINE KARTE UND KEIN DIALOG. Das freigegebene Mockup
 *   sagt es in einem Satz: „Wer sie ignoriert, arbeitet trotzdem." Drei
 *   Schritte ueber der Tagesuebersicht, darunter die vollstaendige Seite.
 *   Die Rueckfragen duerfen stoeren — sie haben eine Frist; eine Einladung
 *   darf es nicht.
 *
 *   DIE KONTO-RUECKFRAGE fragt nach 30 Tagen, nach 6 Monaten, dann jaehrlich:
 *   „Hast du dein Notfallblatt noch?" Auf „Nein" folgt die Erneuerung im
 *   selben Dialog — Passwort, neuer Schluessel, Druckknopf.
 *
 *   DAS NOTFALLBLATT (`notfallblatt.php`) SPEICHERT NICHTS und kann nichts
 *   speichern: Der Schluessel entsteht im Browser, der Server kennt ihn
 *   nicht. Daraus folgt die Eigenschaft, die es ausmacht — es laesst sich
 *   spaeter nicht erneut drucken, und genau das steht darauf.
 *
 *   DIE BETREIBER-RUECKFRAGE wird EINGEGEBEN, nicht bestaetigt: vier
 *   zufaellig gewaehlte Vierergruppen vom Schluesselblatt, Positionen je
 *   Anzeige neu gewuerfelt, Vergleich mit `hash_equals()` und ohne Abkuerzung
 *   bei der ersten Abweichung. Ein Haken haette einen Klick gekostet und
 *   nichts bewiesen. Drei Fehlversuche, dann der Topf `blatt`.
 *
 *   EINE KOMPONENTE, ZWEI VERBRAUCHER (R83): `assets/schluessel.js` rechnet,
 *   `api/schluessel_erneuern.php` schreibt GENAU EINE Spalte (`pat_wrap_rc`),
 *   `schluessel_teile.php` zeigt. Dialog und Kontoseite rufen dasselbe.
 *
 *   DIE WACHE STEHT IM BROWSER, UND SIE MUSS DORT STEHEN. Vor dem Neupacken
 *   wird der entpackte Inhaltsschluessel gegen `pat_key_check` gehalten. Ohne
 *   diese Zeile koennte ein falsch entpackter Schluessel neu verpackt werden,
 *   der Server naehme ihn an, und der neue Zettel oeffnete nichts — der alte
 *   waere schon ueberschrieben. Es ist der einzige Weg, auf dem dieses Paket
 *   Daten haette unzugaenglich machen koennen.
 *
 *   NACHGEMESSEN statt behauptet: zweimal hintereinander erneuert, dann der
 *   erste Code gegen die zweite Huelle (oeffnet nicht) und der zweite dagegen
 *   (oeffnet) — und der erste gegen seine eigene alte Huelle, damit der
 *   Fehlschlag die Ersetzung belegt und nicht einen kaputten Erzeuger.
 *
 * 20.24.1 — DIE ANMELDUNG VERTRUG DAS FENSTER NICHT, IN DEM SIE GEBRAUCHT WIRD
 *   (Hotfix nach dem P5b-Merge, 18.09.2026, Backlog Nr. 234).
 *
 *   DER FEHLER WAR EIN RIEGEL. Zwischen dem Deploy und dem Aufruf von
 *   `update.php` gibt es die Spalten `users.status` und
 *   `users.gesperrt_grund` nicht — sie kommen aus
 *   `2026_09_16_konto_lebenszyklus`. Zwei SELECTs forderten sie trotzdem
 *   hart an, und zwar ausgerechnet die beiden auf dem Anmeldeweg:
 *   `login.php` beim Nachschlagen des Kontos und `auth_guard.php` bei jeder
 *   angemeldeten Anfrage.
 *
 *   Was daraus folgte: **HTTP 500 nach dem Absenden der Anmeldung.** Ohne
 *   Anmeldung kein `betrieb_updates.php`, ohne das keine Migration, ohne die
 *   keine Anmeldung. Die Anlage liess sich aus dem Browser nicht mehr
 *   aufschliessen.
 *
 *   NACHGEBAUT, NICHT ERSCHLOSSEN: Auf der Pruefinstallation die beiden
 *   Spalten entfernt, dann den Anmeldeweg im Browser gefahren. Ohne den
 *   Hotfix bleibt der Weg auf `login.php` stehen, **eine 5xx-Antwort**, die
 *   Seite leer. Mit ihm geht er auf `index.php`, **null** 5xx-Antworten, und
 *   die BetreiberIn kommt weiter bis `betrieb_updates.php`, wo die
 *   ausstehenden Migrationen stehen.
 *
 *   DER RUECKFALL UND NICHT EINE VORABFRAGE. Beide Stellen versuchen das
 *   SELECT mit den neuen Spalten und fallen bei einem Fehler auf die
 *   Spaltenliste ohne sie zurueck, mit einer Zeile im Fehlerprotokoll.
 *   `information_schema` bei jedem Seitenaufbau zu fragen kostete einen
 *   Roundtrip fuer einen Zustand, der nach dem ersten `update.php` nie
 *   wieder eintritt. Ohne die Spalten gilt, was die Migration selbst als
 *   Vorgabe setzt: jedes Konto `aktiv`, kein Sperrgrund.
 *
 *   WARUM ES NICHT AUFFIEL, und das ist der unangenehme Teil: Die
 *   Pruefinstallation hatte die Migrationen IMMER schon gelaufen. Das Fenster
 *   zwischen Deploy und `update.php` ist in P5b an mehreren Stellen bedacht
 *   worden — `einstieg_lib.php` faengt es ausdruecklich ab, `ingest.php` hat
 *   seit P5a eine eigene Spaltenfrage —, aber **genau der Weg zu `update.php`
 *   war nicht geprueft**. Das Pruefdokument nennt das jetzt unter F17, und
 *   die Pruefliste hat einen Punkt dafuer bekommen.
 *
 * 20.24.2 — DER RECHTSTEXT-BAUSTEIN BRACH LANGE ZEICHENKETTEN NICHT UM
 *   (Backlog Nr. 221, 18.09.2026).
 *
 *   Der Auslieferungslauf nach dem P5b-Deploy meldete zwei Ueberlaeufe bei
 *   360 px: `05-datenschutz` und `04b-nutzungsbedingungen`, beide
 *   Rechtstextseiten. Gemessen: Dokumentbreite 487 gegen Fensterbreite 360,
 *   also **127 px** nach rechts geschoben. Verursacher ist ein `<p>` in
 *   `.text` mit `scrollWidth` 350 gegen `clientWidth` 302 — eine Mailadresse
 *   und zwei Adressen im Fliesstext, keine davon mit einer Stelle, an der
 *   ein Browser von sich aus umbricht.
 *
 *   `.text` BEKOMMT `overflow-wrap:break-word`. Die Regel gehoert an den
 *   Baustein und nicht in den Text, weil der Inhalt aus der DATENBANK kommt:
 *   Die BetreiberIn schreibt ihn, und sie soll eine Adresse hinschreiben
 *   duerfen, ohne zu wissen, wie breit ein Handy ist.
 *
 *   `break-word` UND NICHT `anywhere`: Beide brechen das lange Wort, aber
 *   `anywhere` senkt zusaetzlich die intrinsische Mindestbreite — in einer
 *   Karte mit `max-width` faengt der Absatz dann an, auch dort zu brechen,
 *   wo er es nicht muesste.
 *
 *   DER BERICHT HAT DEN VERURSACHER NICHT GENANNT, obwohl Nr. 221 sich
 *   ausdruecklich darauf verlassen hatte („dann ist es in fuenf Minuten
 *   erledigt"). Die Spalte trug `—`. Gefunden wurde er mit zwanzig Zeilen
 *   von Hand; der eingebaute Finder steht jetzt als **Nr. 237** im Backlog.
 *
 *   WAS DAMIT NICHT ERKLAERT IST: Der Einzelfall vom 17.09.2026 hatte eine
 *   andere Lage — die Seite trug damals nur den leeren Zustand
 *   (230 Textzeichen, seinerzeit nachgemessen). Ein ueberlaufender
 *   Rechtstext kann es damals nicht gewesen sein. Der reproduzierbare
 *   Befund ist behoben, der damalige Einzelfall bleibt unerklaert.
 *
 * 20.25.0 — DIE SPALTE `manual` HEISST JETZT `uhr_gesperrt`
 *   (Backlog Nr. 238, 20.09.2026).
 *
 *   Die Einrichtung auf dem neuen Staging-Webspace scheiterte, und zwar
 *   nicht am Webspace: `SQLSTATE[42000] ... 1064 ... near 'manual TINYINT(1)
 *   NOT NULL DEFAULT 0` — Zeile 23 des Schemas. MySQL fuehrt **MANUAL von
 *   8.4.0 bis 8.4.10 als reserviertes Wort** (ab 8.4.11 wieder nicht);
 *   `schema.sql` legte die Spalte ungequotet an, und die Staging-Datenbank
 *   ist 8.4.x. Der Produktivserver war nicht betroffen — bei einem
 *   Hoster-Update waere er es gewesen.
 *
 *   NACHGEMESSEN, WAS SONST NOCH GEBROCHEN WAERE: 788 Bezeichner-Vorkommen
 *   in elf DDL-fuehrenden Dateien gegen 284 reservierte Woerter, dazu die
 *   Schreibwege. Ergebnis: **ZEHN Stellen**, nicht eine. Neben der
 *   Einrichtung der Uhr-Eingang (`ingest.php`), GPX- und Datei-Import, der
 *   Schnitt, beide Zweige des Einsatzformulars und **beide Richtungen der
 *   Sicherung** — eine Sicherung haette sich auf 8.4 weder erstellen noch
 *   einspielen lassen. `PARALLEL`, `QUALIFY` und `TABLESAMPLE`, die in 8.4
 *   ebenfalls neu reserviert sind, kommen im Repositorium nicht vor.
 *
 *   UMBENANNT UND NICHT GEQUOTET — die eigentliche Entscheidung
 *   (Philipp, 20.09.2026). Ueberall Backticks zu setzen haette den Fehler
 *   ebenso behoben. Aber eine dabei uebersehene Stelle waere nur auf genau
 *   diesen elf Fassungen aufgefallen, im Betrieb, bei jemand anderem. Mit
 *   dem neuen Namen scheitert sie auf JEDER Fassung sofort. Verworfen
 *   wurde auch „auf 8.4.11 warten": Das waere ein Zuschnitt auf den Hoster,
 *   und der widerspricht der Hosting-Entscheidung vom 15.09.2026.
 *
 *   DER NAME HOERT AUSSERDEM AUF ZU LUEGEN. `manual` las sich wie „von Hand
 *   angelegt" — so sehr, dass in `schema.sql` seit jeher ein Dementi
 *   danebenstand („NICHT von Hand angelegt — dafuer siehe origin"). Die
 *   Spalte sagt etwas anderes: Die Uhr ueberschreibt Metadaten, Phasen und
 *   Reanimation dieses Einsatzes nicht mehr. Genau das heisst sie jetzt.
 *
 *   `CHANGE` UND NICHT `RENAME COLUMN`, aus zwei praktischen Gruenden. Der
 *   Katalog kennt `CHANGE` schon dreimal und `RENAME COLUMN` kein einziges
 *   Mal. Und `tools/migrationsregister/pruefen.php` rechnet den Katalog
 *   durch, um ihn gegen `schema.sql` zu halten: Seine DDL-Simulation
 *   versteht ADD, DROP, CHANGE und RENAME TABLE — ein `RENAME COLUMN` liefe
 *   durch sie hindurch, ohne die Spalte umzubenennen, und Stufe 1 der Kette
 *   waere grundlos rot geworden. `CHANGE` ist ausserdem verlustfrei: Die
 *   Werte des Bestands bleiben Zeile fuer Zeile stehen.
 *
 *   DIE ALTE SKIP-PRUEFUNG WAERE ZUR FALLE GEWORDEN.
 *   `2026_07_18_manuelle_einsaetze` fragte „gibt es `missions.manual`?" —
 *   nach der Umbenennung: nein. Auf einer Datenbank ohne Registereintrag
 *   haette sie die Spalte ein zweites Mal angelegt, leer, neben der vollen.
 *   Und aufgefallen waere es nicht: `1060` steht in der Schluckliste, der
 *   Lauf ginge weiter, gelesen wuerde ab da die leere Spalte. Die Pruefung
 *   fragt jetzt nach BEIDEN Namen.
 *
 *   DIE NEUE MIGRATION HAT SELBST EINE ZWEITEILIGE BEDINGUNG, und die
 *   zweite ist die wichtige: Fehlt `manual`, darf das SQL nicht laufen.
 *   MySQL antwortete sonst mit `1054 Unknown column`, und 1054 steht NICHT
 *   in der Schluckliste — der ganze Migrationslauf braeche ab, an einer
 *   Migration, die nichts zu tun hat.
 *
 *   IN DER SICHERUNGS- UND EXPORTDATEI HEISST DAS FELD WEITER `manual`.
 *   Das ist Absicht und keine Nachlaessigkeit: Alte Sicherungen und alte
 *   Exporte muessen sich unveraendert einspielen lassen, und der
 *   ausgelieferte Demo-Bestand (`server/demo/fixture.json.gz`, 106
 *   Einsaetze, 104 davon mit dem Schutzflag) ist selbst eine solche Datei.
 *   Die Spalte wird beim Lesen per Alias auf den Dateinamen abgebildet und
 *   beim Schreiben zurueck.
 *
 *   HINTER DEM RESERVIERTEN WORT STAND EIN ZWEITER BLOCKER, und er war der
 *   groessere. Nach dem Umbenennen lief `schema.sql` gegen MySQL 8.4.0 bis
 *   Zeile 650 und brach dort wieder ab:
 *   `zeit DATETIME NOT NULL DEFAULT UTC_TIMESTAMP()`. MySQL laesst einen
 *   Funktionsaufruf als Spaltenvorgabe nur GEKLAMMERT zu --
 *   `DEFAULT (UTC_TIMESTAMP())`, seit 8.0.13. MariaDB nimmt beide
 *   Schreibweisen, und deshalb ist es nie aufgefallen: Entwickelt und
 *   geprueft wurde gegen MariaDB.
 *
 *   DAS IST KEIN 8.4-PROBLEM. Es betrifft JEDE MySQL-Fassung. Vier Stellen,
 *   zwei in `schema.sql` (`protokoll_ereignisse`, `konto_einwilligungen`),
 *   zwei in den Migrationen, die dieselben Tabellen anlegen. Folge: Seit
 *   Web 20.16.5 liess sich die Anwendung auf MySQL UEBERHAUPT NICHT
 *   einrichten -- die Zusage „MySQL >= 8.0" in `docs/Technik.md` 7 und
 *   `plattform_lib.php` war seither nicht eingeloest. Gemerkt hat es
 *   niemand, weil der Fehler am reservierten Wort schon vorher kam.
 *
 *   GEMESSEN, statt behauptet: Das alte `schema.sql` gegen MySQL 8.4.0
 *   bricht bei Zeile 386 (1064, wortgleich mit der Meldung vom Staging),
 *   das neue legt **42 Tabellen** fehlerfrei an. Der Migrationsprueflauf
 *   ueber vier Installationsfaelle meldet **18 Pruefungen, 0 Fehlschlaege**
 *   -- gegen MySQL 8.4.0 UND gegen MariaDB 10.11.
 *
 *   DIE AUSLASSUNGEN OBEN STEHEN ALS ASCII, nicht als U+2026. Das
 *   Auslassungszeichen zaehlt `tools/vollstaendigkeit/` als
 *   „Unicode-Zeichen als Symbol im Markup", und die zwei in jener einen
 *   Zeile haben Stufe 1 der Kette ueber ihre Schwelle geschoben --
 *   400 statt hoechstens 398, gemessen am 20.09.2026.
 *
 *   DIE KOPFZEILE VON `schema.sql` NANNTE „MySQL >= 5.7 / MariaDB >= 10.2".
 *   Das widersprach `plattform_lib.php` und `docs/Technik.md` und war
 *   ausserdem fuer sich genommen falsch, seit die geklammerte Vorgabe
 *   drinsteht. Sie nennt jetzt 8.0.13 / 10.6.
 *
 * 20.26.0 — DIE ANWENDUNG LEGT IHRE SITZUNGEN SELBST AB
 *   (Schritt 16, E-SA-01 bis -09; Backlog Nr. 241, 20.09.2026).
 *
 *   Bis hierher lag KEINE ZEILE CODE an `session.save_path` — wo PHP die
 *   Sitzungsdateien hinlegt, entschied allein der Hoster. Auf der neuen
 *   Staging-Anlage ist das ein GETEILTES Verzeichnis: `0773`, Eigentuemer
 *   root, `gc_probability = 0`. Es ist gutgegangen, weil der Hoster das
 *   Auflisten ueber das Web sperrt — die Anwendung haette es nicht gemerkt.
 *   Fuer Produktiv ist derselbe Wert nie erhoben worden, fuer jede
 *   Selbsthosterin ist er offen.
 *
 *   WAS AUF DEM SPIEL STAND, NUECHTERN: Eine Sitzungsdatei traegt KEIN
 *   Schluesselmaterial; die Zusage der Ende-zu-Ende-Verschluesselung ist
 *   nicht beruehrt. Sie traegt aber die Sitzung selbst, und ihr DATEINAME
 *   IST DIE SITZUNGSKENNUNG. Wer sie liest, ist angemeldet.
 *
 *   NEU IST `sitzung_lib.php` — eine Datei, die NICHTS laedt, weil
 *   `db.php` sie waehrend des eigenen Ladens ruft und `install.php` zu
 *   diesem Zeitpunkt keine `config.php` hat. Sie legt `.sitzungen/` mit
 *   `0700` an, prueft mit einer Probedatei und setzt `session_save_path()`.
 *
 *   ZWEI AUFRUFSTELLEN UND NICHT NEUN, und das ist die eigentliche
 *   Entscheidung. Es gibt NEUN `session_start()` in neun Dateien — nicht
 *   drei, wie der erste Entwurf des Konzepts annahm. Acht davon laden
 *   `db.php` vorher, die neunte ist `install.php`. Mit drei Aufrufstellen
 *   haette `login.php` die Sitzung beim Hoster abgelegt und
 *   `auth_guard.php` sie in `.sitzungen/` gesucht: NIEMAND HAETTE SICH
 *   ANMELDEN KOENNEN. Nachgemessen mit dem Tokenizer, zweimal unabhaengig.
 *
 *   DER CACHE IST EINE DATEI UND KEINE TABELLE. `app_state` waere der
 *   naheliegende Ort und der falsche: Vor dem Sitzungsstart ist die
 *   Datenbank nicht verbunden, in `install.php` gibt es sie gar nicht, und
 *   faellt sie aus, straeubte sich jede Anfrage schon vor der Fehlerseite.
 *   Stattdessen `.sitzungen/.geprueft` — ist ihr `mtime` juenger als eine
 *   Stunde, genuegt ein `is_dir()`. Gemessen im Pruefstand: zweiter Aufruf
 *   innerhalb der Stunde 0 Probedateien, mit gealtertem Marker 1.
 *
 *   `gc_probability = 0` IST NOTWENDIG UND KEIN FEINSCHLIFF. Der Hoster
 *   stellt `gc_maxlifetime` — auf lima-city 1440 s, also 24 Minuten. Die
 *   Anwendung sagt 30 zu. Liefe PHPs Zufallsraeumung mit, meldete sie
 *   Leute sechs Minuten VOR Ablauf der eigenen Frist ab. Geraeumt wird
 *   deshalb vom Aufraeumjob, Teil „Sitzungsdateien", nach
 *   `SESSION_TIMEOUT_S` plus einer Stunde Karenz und nur `sess_*`.
 *
 *   DIE KONSTANTE `SESSION_TIMEOUT_S` IST MITGEWANDERT, von
 *   `auth_guard.php` nach `sitzung_lib.php`. Der Aufraeumjob laeuft ueber
 *   `jobs.php`, das `db.php` laedt, aber NIE `auth_guard.php`: Der neue
 *   Raeumteil waere auf der Kommandozeile an `Undefined constant`
 *   gestorben und am Huckepack-Weg durchgelaufen — ein Fehler auf einem
 *   von drei Wegen. Dasselbe Argument hat `PAIR_TTL_MIN` nach `db.php`
 *   gebracht.
 *
 *   DER RUECKFALL IST KEINE VERSCHLECHTERUNG, seine SICHTBARKEIT ist die
 *   Verbesserung. Scheitert das Anlegen, gilt der Hosterpfad wie bisher.
 *   Neu sind zwei Zeilen auf der Statusseite: der vierte Schreibort
 *   (Empfohlen) und der Punkt „Sitzungsablage" (Muss, dreiwertig), der den
 *   WIRKSAMEN Pfad misst — Rechte, Eigentuemer, Auflistbarkeit, Dateizahl.
 *   Er wird rot bei jedem Recht fuer „andere" und dann, wenn das
 *   Verzeichnis uns nicht gehoert und sich trotzdem auflisten laesst: Dann
 *   sind WIR SELBST DER FREMDE, der es lesen konnte.
 *
 *   NACH DEM AUSROLLEN SIND ALLE EINMAL ABGEMELDET. Das ist eine
 *   Betriebsfolge und keine Wegaenderung — die Wege durch die Anwendung
 *   sind dieselben, es gibt kein Datenmodell und keine Migration. Deshalb
 *   die NEBENNUMMER und nicht die Hauptnummer. Es geht dabei nichts
 *   verloren; die Betroffene sieht die Anmeldeseite statt der erwarteten
 *   Seite. Der Satz steht im Runbook (Technik.md 7) und im Changelog.
 *
 *   EIN LATENTER FEHLER IST DABEI AUFGEFALLEN UND BEHOBEN: Ohne
 *   `clearstatcache()` nach dem `mkdir` konnte die Schreibprobe noch die
 *   Auskunft von vorher bekommen — der realpath-Cache gilt PROZESSWEIT und
 *   120 s. Gemessen im Pruefstand: `sitzung_ablage()` meldete einen
 *   Rueckfall, waehrend `plattform_schreibprobe()` zwei Zeilen spaeter auf
 *   demselben Pfad gelang. Nach der Berichtigung fuenf von fuenf Laeufen
 *   sauber.
 *
 *   MITGEZOGEN, WEIL ES SONST STILL GEBROCHEN WAERE:
 *   `tools/wartungsprobe/` legt ihre Sitzungsdateien selbst an — auf der
 *   KOMMANDOZEILE, waehrend der Server sie ueber HTTP liest. Da
 *   `sitzung_ablage()` im CLI bewusst nichts tut, haette die Probe in den
 *   Hosterpfad geschrieben und der Server in `.sitzungen/` gesucht: alle
 *   Sitzungsfaelle „nicht angemeldet", aussehend wie ein Fehler der
 *   ANWENDUNG statt der Probe.
 *
 *   UND DAS JOBREGISTER (Backlog Nr. 208) IST AN DER URSACHE GEFASST.
 *   Gemessen am 20.09.2026: 11 Jobs im Code gegen 9 in `docs/Technik.md`,
 *   16 Raeumschritte gegen „dreizehn", 15 von 16 in der sichtbaren
 *   Beschreibung. Zweimal waren die Zahlen schon von Hand berichtigt
 *   worden, zweimal wuchs der Abstand wieder. Jetzt wird die sichtbare
 *   Beschreibung aus `array_keys(job_aufraeumen_schritte())` ERZEUGT, und
 *   `tools/jobregister/pruefen.php` zaehlt das Register nach — mit dem
 *   Tokenizer und ohne Installation. Der Ketteneintrag dafuer gehoert zu
 *   Kette II und ist dort angemeldet.
 *
 * 20.26.1 — DIE STATUSSEITE NANNTE EINE URSACHE, DIE SIE NICHT GEMESSEN HATTE
 *   (Berichtigung zu Schritt 16, gefunden auf Staging am 20.09.2026).
 *
 *   BEFUND AUF DER ANLAGE: Die Karte „Plattform" zeigte
 *   „Sitzungsablage - fehlt - Hosterpfad, 0773 - RUECKFALL: Die Anwendung
 *   konnte kein eigenes Verzeichnis einrichten". Der Satz war FALSCH. Das
 *   Verzeichnis war angelegt und beschreibbar — die Zeile „Ablage der
 *   Sitzungen" fehlte auf der Seite, und weil sie nur bei Abweichung
 *   erscheint, heisst ihr Fehlen genau das. Was nicht gegriffen hatte, war
 *   `session_save_path()`.
 *
 *   ZWEI FEHLER, EIN BILD:
 *
 *   ERSTENS BEHAUPTETE `sitzung_ablage_setzen()`, STATT ZU MESSEN. Sie rief
 *   `session_save_path()` und vermerkte anschliessend `eigen = true`, ohne
 *   den Erfolg zu pruefen — also genau das, was `docs/Technik.md` 5b.1
 *   verbietet: „Wer nichts gemessen hat, darf nichts behaupten."
 *
 *   DER RUECKGABEWERT HAETTE ES AUCH NICHT GERETTET, und das ist der
 *   lehrreiche Teil. Gemessen am 20.09.2026 unter PHP 8.4.19:
 *
 *     Sitzung schon aktiv        `false`, dazu eine Warnung
 *     Kopfzeilen schon gesendet  `false`, dazu eine Warnung
 *     `open_basedir` sperrt      DEN ALTEN PFAD ALS ZEICHENKETTE — also
 *                                dasselbe wie bei Erfolg —, dazu eine Warnung
 *
 *   Ein `=== false` haette den dritten Fall durchgelassen. Belastbar ist
 *   allein das ZURUECKLESEN: der wirksame Pfad gegen den gewuenschten.
 *
 *   ZWEITENS KEHRTEN DREI VON SECHS AUSGAENGEN STUMM ZURUECK. „Auf der
 *   Kommandozeile" und „es lief schon eine Sitzung" vermerkten gar nichts,
 *   der Stand blieb auf seinem Vorgabewert, und die Statusseite hatte keinen
 *   Grund zu nennen. Sie nahm fuer das URTEIL die Messung und fuer die
 *   BEGRUENDUNG die Buchfuehrung — zwei Quellen, die auseinanderliefen.
 *
 *   WAS JETZT GILT: Der Stand traegt eine BENANNTE Lage
 *   (`eigen`, `kommandozeile`, `sitzung_lief`, `nicht_anlegbar`,
 *   `nicht_beschreibbar`, `nicht_uebernommen`), je Lage steht EIN Satz in
 *   `sitzung_ablage_satz()`, und die Statusseite druckt ihn statt zu raten.
 *   Die Farbe kommt weiter aus der Messung. Das Feld `gelaufen` ist
 *   ersatzlos entfallen — es wurde nirgends gelesen und war ausgerechnet der
 *   Wert, der zwei Ursachen voneinander getrennt haette.
 *
 *   DIE MUSS-ZEILE SAGT JETZT AUCH, OB DAS VERZEICHNIS DA IST. Der vierte
 *   Schreibort ist Stufe `empfohlen` und im guten Fall unsichtbar —
 *   ausgerechnet die Zeile, die den Widerspruch sofort gezeigt haette, war
 *   nicht da. Die Muss-Zeile steht immer und traegt es mit.
 *
 *   WARUM DER PRUEFSTAND ES NICHT FAND: Er ist ein `php -S` im
 *   Wegwerf-Behaelter. Dort sperrt kein Hoster `session.save_path`, das
 *   Fehlerbild war dort nicht erzeugbar. Der Rueckfall WURDE geprueft — aber
 *   ueber ein scheiterndes `mkdir`, und das ist ausgerechnet der eine
 *   Ausgang, der seinen Grund vermerkt. Aus einer Rueckfallform wurde auf
 *   alle geschlossen.
 *
 *   NICHT BEHOBEN, WEIL NICHT MESSBAR VON HIER: WARUM die Anlage den Pfad
 *   nicht uebernimmt. Die fuehrende Erklaerung ist ein festgeschriebenes
 *   `session.save_path` (dasselbe Muster wie `gc_maxlifetime` 1440 s und
 *   `gc_probability` 0, die dort ebenfalls vorgegeben sind). Die zweite,
 *   nicht ausgeschlossene, ist `session.auto_start` — und die haette eine
 *   stille Folge: Dann greifen auch `session_set_cookie_params()` und
 *   `use_strict_mode` in `auth_guard.php` nicht, das Sitzungscookie truege
 *   weder `secure` noch `SameSite`. Der Weg, beide zu trennen, steht im
 *   Pruefdokument.
 *
 * 20.26.2 — DER HANDGRIFF STEHT IM RUNBOOK, UND DER PFAD NUR NOCH EINMAL
 *   (Nachzug zu Schritt 16, 20.09.2026).
 *
 *   AUF STAGING HAT SICH DIE FRAGE VON 20.26.1 BEANTWORTET. Die Zeile stand
 *   auf `nicht_uebernommen` — Verzeichnis angelegt, 0700, Schreibprobe
 *   bestanden, und `session.save_path` blieb auf `/home/webpages/tmp`. Eine
 *   `.user.ini` neben `index.php` mit einer Zeile hat es behoben; die Zeile
 *   steht seither blau. Der Hoster hatte den Wert also nur GESETZT, nicht
 *   per `php_admin_value` GESPERRT.
 *
 *   DAMIT IST AUCH `session.auto_start` AUSGESCHLOSSEN: Dann hiesse die Lage
 *   `sitzung_lief`. Die Sorge aus 20.26.1, das Sitzungscookie truege weder
 *   `secure` noch `SameSite`, ist gegenstandslos — die Haertung in
 *   `auth_guard.php` wirkt.
 *
 *   DIE `.user.ini` GEHOERT NICHT INS REPOSITORIUM. Der Pfad ist
 *   anlagenabhaengig, und E-PP-04 sagt: Ein Hosterwechsel aendert
 *   `config.php`, keine Codezeile. Sie steht deshalb als HANDGRIFF im
 *   Runbook, mit den zwei Bedingungen (nur CGI/FastCGI; greift erst nach
 *   `user_ini.cache_ttl`) und mit dem Satz, an dem man erkennt, dass man sie
 *   braucht. Das Runbook traegt dazu eine Tabelle „welcher Satz heisst
 *   welchen Handgriff" fuer alle vier Rueckfall-Lagen.
 *
 *   DIE EINE CODEAENDERUNG: Der wirksame Pfad stand zweimal in derselben
 *   Zeile. Der Grund nennt jetzt nur den GESETZTEN; der wirksame steht
 *   ohnehin am Ende.
 *
 *   PRODUKTIV IST UNGEPRUEFT. Dort ist `session.save_path` nie erhoben
 *   worden. Ob derselbe Handgriff faellig ist, sagt dieselbe Zeile beim
 *   ersten Ausrollen — das ist der Pruefpunkt, keine Vermutung.
 *
 * 20.27.0 — EINE STELLE FUER DIE KONFIGURATION, EINE FUER DIE SITZUNG
 *   (21.09.2026, Schritt 15 AP2 — Zentralisierung, R83, E-ZE-02/-06/-12
 *   bis -14, F-ZE-2). NEBEN-Nummer: zwei neue Funktionen, kein Datenmodell,
 *   keine Migration, `update.php` nicht faellig.
 *
 *   DIE KONFIGURATION LAG AN ZWEI STELLEN GLEICHZEITIG. `db.php` las
 *   `config.php` beim Laden in die globale `$CFG`; fuenf weitere Dateien
 *   lasen dieselbe Datei bei Bedarf noch einmal — `smtp.php` dreimal.
 *   Gemessen: 7 Lesestellen in 5 Dateien, 46 Zugriffe auf `$CFG` in 11.
 *   Wer einen Wert brauchte, hatte die Wahl zwischen `global $CFG` (setzt
 *   voraus, dass `db.php` schon geladen ist) und einem eigenen `require`
 *   (liest die Datei noch einmal von der Platte). Jetzt:
 *   `konfig('app.timezone')` — eine Datei, ein Merker, 7 -> 1 und 46 -> 0.
 *
 *   `konfig_lib.php` LAEDT NICHTS, und das ist Bedingung, nicht Sparsamkeit:
 *   `install.php` laeuft ohne `config.php`, und `sitzung_lib.php` wird aus
 *   `db.php` heraus gerufen, WAEHREND diese laedt. Zoege der Leser etwas
 *   nach, das `db.php` erreicht, liefe `sitzung_ablage()` in einer halb
 *   geladenen `db.php` — und `require_once` verdeckte den Zyklus, statt ihn
 *   zu melden. Nachgemessen ueber `get_included_files()`: Der Leser zieht
 *   keine einzige Datei nach.
 *
 *   `db.php` VERLANGT `config.php` WEITERHIN HART. Der Leser toleriert die
 *   fehlende Datei — er muss, wegen `install.php`. `db.php` erbt das nicht:
 *   Ohne die Pruefung waere aus dem klaren Befund „config.php fehlt" eine
 *   PDO-Ausnahme auf einem leeren DSN geworden, also die Meldung „Datenbank
 *   nicht erreichbar" fuer ein Problem, das nichts mit der Datenbank zu tun
 *   hat. Eine falsche Diagnose ist teurer als ein Abbruch.
 *
 *   NEUN SITZUNGSSTARTS IN VIER FASSUNGEN, JETZT EINER. `sitzung_starten()`
 *   in `sitzung_lib.php` kennt vier Arten — `app`, `lesend`, `einrichtung`,
 *   `passwort` — mit genau den Cookie-Parametern, die vorher verstreut
 *   standen. Die Drift bei `secure` (zwei Arten fest, zwei HTTPS-abhaengig)
 *   BLEIBT, wie sie war: Sie zu schliessen ist eine Sicherheitsentscheidung
 *   fuer Schritt 18 (Backlog Nr. 251), keine Zentralisierung. Was sich
 *   aendert, ist dass man sie jetzt SIEHT — vorher stand sie in neun Dateien
 *   und niemand konnte sie zaehlen.
 *
 *   `PW_SESSION_NAME` und `pw_session_start()` sind damit entfallen; der
 *   Name steht neben der Tabelle, die ihn braucht.
 *
 *   `sitzung_ablage()` RUFT JETZT `sitzung_starten()` SELBST. Die beiden
 *   Aufrufe aus Schritt 16 (`db.php`, `install.php`) sind fort. Folge: Die
 *   Ablage wird nur noch eingerichtet, wenn wirklich eine Sitzung startet —
 *   nicht mehr bei jeder Anfrage, die `db.php` laedt. Ein API-Aufruf ohne
 *   Sitzung fasst `.sitzungen/` gar nicht mehr an.
 *
 *   DIE EINE SICHTBARE FOLGE (F-ZE-2): Handbuch, „Was ist NAdoku" und die
 *   Rechtstexte starten eine Sitzung nur noch, wenn ein Sitzungscookie da
 *   ist. Sie sind ohne Anmeldung erreichbar und fragten die Sitzung bisher
 *   nur, um den angemeldeten Kopf zeigen zu koennen — STARTETEN sie dabei
 *   aber fuer jeden Besucher, auch fuer jeden Bot, und seit Web 20.26.0
 *   landete jede davon als Datei in `.sitzungen/`. Das Konzept Sitzungsablage
 *   behauptete, die drei Seiten pruefen auf das Cookie; nachgemessen taten
 *   sie es nicht. Fuer Angemeldete aendert sich nichts.
 *
 *   DIE SITZUNGSHAERTUNG PRUEFT JETZT SCHAERFER: nicht mehr nur „steht die
 *   Haertung vor jedem Aufruf", sondern „es gibt genau einen Aufruf, er
 *   steht in `sitzung_lib.php`, er ist gehaertet, und jeder weitere ist ein
 *   Befund". Der Zugewinn ist der letzte Punkt: Ein neuer Sitzungsstart MIT
 *   Haertung war vorher gruen und haette die Cookie-Parameter trotzdem neu
 *   erfinden muessen. Genau so sind die neun entstanden.
 *
 * 20.28.0 — EIN EINGANG FUER DIE ENDPUNKTE, EINE MELDUNG UEBER DIE UMLEITUNG
 *   (21.09.2026, Schritt 15 AP3 — Zentralisierung, R83, E-ZE-15/-16,
 *   F-ZE-5). NEBEN-Nummer: drei neue Funktionen, kein Datenmodell, keine
 *   Migration, `update.php` nicht faellig.
 *
 *   EINUNDZWANZIG DATEIEN UNTER `api/` FINGEN GLEICH AN — und liefen
 *   auseinander. Methode pruefen, Rumpf lesen, „leer?", „ist das ein
 *   JSON-Objekt?": vier Handgriffe, in vier Schreibweisen. Siebzehn Dateien
 *   antworteten `'method'`, drei `'methode'`; acht sagten `'payload'`, drei
 *   `'format'`; den Hinweis auf `post_max_size` gab es in drei Fassungen,
 *   und acht Endpunkte hatten gar keinen Leer-Zweig — ein leerer POST endete
 *   dort in `payload`, also in „dein JSON ist falsch" fuer eine Anfrage, die
 *   gar keins mitbrachte. Jetzt: `api_methode()` und `api_rumpf()` in
 *   `db.php`, gemessen 12 -> 1 Rumpf-Lesestellen (die eine ist
 *   `api/csp_bericht.php`, benannte Ausnahme), 17 -> 0 Methodenpruefungen,
 *   11 -> 0 Handpruefungen auf „kein JSON-Objekt", 3 -> 0 Fassungen des
 *   `post_max_size`-Hinweises.
 *
 *   ZWEI FUNKTIONEN UND NICHT EINE — die Reihenfolge traegt. Das Konzept sah
 *   EINEN Aufruf vor, der Methode und Rumpf zusammen erledigt. Zwischen
 *   beiden steht in allen elf Rumpf-Dateien `csrf_check()`, und ein
 *   zusammengefasster Aufruf haette das Rumpflesen davor geschoben: Ein
 *   Aufrufer ohne gueltiges Token saehe `leer` oder `format` statt `csrf`,
 *   und in `api/kdf_upgrade.php` liefe er am Demo-Ausstieg vorbei, der
 *   zwischen csrf und Rumpf steht (200 wuerde zu 400). Dieselbe Klasse von
 *   Fehler ist am 13.09.2026 schon einmal behoben worden; der Kopfkommentar
 *   jener Datei erzaehlt es. Zwei Funktionen halten die Reihenfolge — gemessen
 *   an einer laufenden Anlage: POST ohne Token mit leerem Rumpf antwortet
 *   weiterhin `403 csrf`, GET ohne Token weiterhin `405 method`.
 *
 *   DIE FEHLERSCHLUESSEL AENDERN SICH, UND ZWAR ABSICHTLICH (F-ZE-5). 19 von
 *   46 gemessenen Zellen antworten anders als vorher: acht `payload` ->
 *   `format`, sechs `payload` -> `leer`, zwei `format` -> `leer`, drei
 *   `methode` -> `method`. Kein JavaScript wertet einen dieser Schluessel aus
 *   — nachgemessen ueber alle 40 Skripte, der einzige Vergleich auf `error`
 *   gilt `maintenance`. Die Geraete-Endpunkte (`ingest.php`, `pair.php`,
 *   `auth_salt.php`, `jobs.php`, `gpx.php`) sind nicht angefasst und behalten
 *   `payload` und `too_large` zeichengleich (JSON-Vertrag).
 *
 *   `max_bytes` HAT KEINEN VORGABEWERT. Eine Grenze fuer die Rumpfgroesse
 *   gab es unter `api/` bisher an keiner Stelle, und ein Konto-Backup kann
 *   zweistellig megabytegross sein. `app.max_body_bytes` (512 KB) ist die
 *   Grenze des GERAETE-Eingangs. Eine Vorgabe haette hier eine Pruefung
 *   eingefuehrt, die es nicht gab.
 *
 *   ZWEI SITZUNGSSCHLUESSEL FUER MELDUNGEN, JETZT EINER.
 *   `flash_setzen()`/`flash_holen()` in `session_lib.php`; drei Seiten —
 *   Einstellungen, Nachbearbeitung, Papierkorb — setzten und raeumten
 *   `flash_notice` und `flash_error` von Hand, 22 Stellen. Der Ton ist eine
 *   Eigenschaft der Meldung, keine eigene Ablage: Zwei Schluessel heissen,
 *   dass beide gleichzeitig gesetzt sein koennen, und dann entscheidet die
 *   Reihenfolge des Auslesens, was jemand sieht. Dass sie es NICHT koennen,
 *   ist fuer alle 24 Handlungszweige nachgelesen worden — jeder ist eine
 *   if/elseif/else-Kette oder ein try/catch, und der Demo-Riegel setzt
 *   `$action = ''`.
 *
 * 20.29.0 — VIER SACHEN, DIE AN EINER STELLE STEHEN STATT AN SIEBENUNDVIERZIG
 *   (21.09.2026, Schritt 15 AP4 — Zentralisierung, R83, E-ZE-04/-17/-18/-19).
 *   NEBEN-Nummer: neue Funktionen, kein Datenmodell, keine Migration,
 *   `update.php` nicht faellig.
 *
 *   `app_state`: 27 STELLEN IN 17 DATEIEN, JETZT ZWEI. Vier Helfer kommen zu
 *   `app_state_lesen()` und `app_state_setzen()` dazu:
 *   `app_state_mehrere()` (eine Abfrage statt n), `app_state_setzen_mehrere()`,
 *   `app_state_loeschen()` und `app_state_einmalig()` — letzteres fuer die
 *   beiden Servergeheimnisse (`salt_secret`, `reg_secret`). Die benutzten
 *   schon `INSERT IGNORE`, und das ist der Punkt: Zwei gleichzeitige Anfragen
 *   erzeugen beide einen Wert, aber nur EINER darf gewinnen — mit
 *   `ON DUPLICATE KEY UPDATE` gewaenne der letzte, und die Pseudo-Salts
 *   aenderten sich unter der Hand. Die Wrapper (`edbak_marke_*`,
 *   `geocoder_state*`, `schluessel_marke_*`, `demo_*`, `jobs_*`, `logo_*`)
 *   behalten Namen und Signatur; nur ihr Rumpf ruft die Helfer.
 *
 *   WAS DIE 27 STELLEN UNTERSCHIED, war nie die Sache, sondern die Antwort
 *   auf die Frage „was, wenn `app_state` fehlt?" — mal `try/catch` mit
 *   `null`, mal ohne, mal mit `error_log`. Das ist kein seltener Zustand: Es
 *   ist der Zustand JEDER Anlage zwischen Deploy und `update.php`.
 *
 *   ZWEI STELLEN BLEIBEN, NAMENTLICH. `job_aufraeumen_schritte()` loescht mit
 *   einem Verbund auf `users` (kein Schluesselzugriff). Und `jobs.php`, das
 *   ist der wichtigere Fall: Der Endpunkt gehoert zum GERAETEVERTRAG und
 *   antwortet bei unerreichbarer Datenbank mit `500 datenbank`. Der Helfer
 *   faengt und liefert `null` — daraus waere ein „Token falsch" geworden.
 *
 *   DAS VIRTUELLE GERAET „Manuelle Einträge": Der Block „gibt es das Geraet
 *   schon? sonst anlegen" stand VIERMAL — zweimal als eigene Funktion
 *   (`schnitt_geraet()`, `gpx_import_geraet()`), zweimal eingebettet, jedes
 *   Mal mit demselben zwanzigzeiligen Kommentar darueber, der erklaert, warum
 *   `user_id` IN der Abfrage stehen muss. Jetzt:
 *   `geraet_virtuell_sicherstellen()`. Die Kennung selbst stand an sieben
 *   Stellen; dafuer `geraet_virtuell_kennung()`, `geraete_echt_sql($alias)`
 *   fuer die Abfragen mit Tabellenalias und `GERAET_VIRTUELL_MUSTER` fuer die
 *   eine Stelle, die das Muster bindet statt es einzusetzen.
 *
 *   `einsatz_laden()` IN `einsatz_lib.php` (neu): „Einsatz per Kennung holen
 *   und dabei pruefen, dass er dem Konto gehoert" stand zwoelfmal in neun
 *   Dateien. Der Unterschied war die Spaltenliste und die Behandlung des
 *   Papierkorbs — mal `IS NULL`, mal `IS NOT NULL`, mal gar nicht. Genau das
 *   sind die beiden Optionen. Zwei Stellen bleiben: der Verbund mit `days` in
 *   `trash_restore_mission()` und die in der Import-Schleife bis zu 3000-mal
 *   ausgefuehrte vorbereitete Anweisung in `api/import_commit.php`.
 *
 *   DIE SCHEMA-FRAGEN WERDEN OEFFENTLICH (E-ZE-04). `db_hat_tabelle()`,
 *   `db_hat_spalte()`, `db_hat_index()` in `db.php`; die privaten `_hat_*` in
 *   `migration_lib.php` reichen nur noch durch, damit 42 gelaufene
 *   Migrationen unveraendert bleiben. Sie nehmen ein `PDO`, und das ist
 *   zwingend: `tools/schemaprobe/` laesst Migrationen gegen ein frisch
 *   angelegtes Schema laufen, also gegen eine ANDERE Verbindung als `db()`.
 *   Ein Helfer, der sich seine Verbindung selbst holte, fragte dort das
 *   falsche Schema — lautlos.
 *
 *   VIER ROLLENVERGLEICHE VON HAND (`=== 'betreiberin'`) rufen jetzt
 *   `rolle_ist_betreiberin()`.
 *
 * 20.30.0 — EIN TRANSAKTIONSRAHMEN, VIER KINDTABELLEN
 *   (22.09.2026, Schritt 15 AP5 — Zentralisierung, R83, E-ZE-20, E-ZE-21).
 *   NEBEN-Nummer: fuenf neue Funktionen, kein Datenmodell, keine Migration,
 *   `update.php` nicht faellig.
 *
 *   DREIUNDDREISSIG TRANSAKTIONSRAHMEN IN ZWEIUNDZWANZIG DATEIEN, und der
 *   Tokenizer hat sie in drei Bauformen sortiert (mit einem Muster ging es
 *   nicht: Drei Anlaeufe ergaben drei Verteilungen, weil geschweifte Klammern
 *   in Kommentaren und Zeichenketten mitzaehlen). 19x beginnen, versuchen,
 *   bestaetigen, bei Fehler zurueckrollen und weitergeben; 12x dasselbe, aber
 *   der `catch` schluckt; 2x gar kein `try`. Dazu 42 `rollBack()`-Aufrufe,
 *   von denen 14 hinter einer Wache standen und 28 nicht.
 *
 *   `db_transaktion(PDO $pdo, callable $fn): mixed` — 24 Rahmen ziehen um.
 *   Sie ist verschachtelungsfest und ASYMMETRISCH: Wer schon in einer fremden
 *   Transaktion steht, oeffnet keine eigene und bestaetigt und verwirft dann
 *   auch nichts; die Ausnahme kommt heraus, und der Aufrufer entscheidet.
 *   Und sie fragt vor dem `rollBack()` nach, ob die Transaktion noch steht:
 *   Ein DDL-Befehl bestaetigt in MySQL still, und der Rumpf darf selbst
 *   zurueckgerollt haben — sonst wuerfe `rollBack()` eine ZWEITE Ausnahme und
 *   verdeckte die erste. Genau das war der Zustand: „There is no active
 *   transaction" im Protokoll statt des Grundes.
 *
 *   NEUN RAHMEN BLEIBEN, NAMENTLICH (Auftraggeber, 22.09.2026; das Konzept
 *   liess acht zu, H-ZE-4). Drei wegen Groesse oder Vertrag: `ingest.php`
 *   (Geraetevertrag, Deadlock-Behandlung in Schritt 18), `backup_lib.php`
 *   (Rumpf 1153 Zeilen, 145 Variablen) und `api/import_commit.php` (542
 *   Zeilen, 78 Variablen) — eine `use`-Liste mit 145 Eintraegen ist kein
 *   Zentralisieren, sondern ein Rewrite. Sechs wegen Bauform: `pair.php`
 *   (Geraetevertrag), `jobs_lib.php`, `diensttag_zusammenfuehren.php`,
 *   `api/day.php`, `api/kdf_upgrade.php`, `api/schneiden.php` — sie rollen
 *   MITTEN im `try` zurueck und machen dann etwas anderes weiter.
 *
 *   EIN LATENTER FEHLER IST DABEI HERAUSGEFALLEN (`einsatz_form.php`).
 *   Hinter dem `commit()` standen noch die Hoehenermittlung und die
 *   Rettungsmittel-Zeilen — INNERHALB desselben `try`, dessen `catch` ein
 *   unbedingtes `$pdo->rollBack()` hatte. Warf eine der beiden, rollte der
 *   `catch` eine BEREITS BESTAETIGTE Transaktion zurueck: Das wirft
 *   seinerseits, und statt „Speichern fehlgeschlagen." gab es eine 500.
 *
 *   VIER KINDTABELLEN, FUENF SCHREIBWEGE, DREISSIG ANWEISUNGEN — jetzt vier
 *   Funktionen in `einsatz_lib.php`: `einsatz_phasen_ersetzen()`,
 *   `einsatz_reas_ersetzen()`, `einsatz_rettungsmittel_ersetzen()`,
 *   `einsatz_besatzung_ersetzen()`. Zwei Schalter statt zweier
 *   Funktionsformen: `loeschen` (Vorgabe `true`) fuer das Backup, das in
 *   einen gerade erst angelegten Einsatz schreibt, und `ignorieren` fuer
 *   dessen `INSERT IGNORE`. Eine leere Liste mit `loeschen => true` IST das
 *   Loeschen — das ist der Zweig des Schneidens.
 *
 *   SIE PRUEFEN NICHTS. Was gueltig ist, entscheidet weiter der Aufrufer;
 *   eine Pruefpolitik hier waere eine sechste neben den fuenf vorhandenen.
 *
 *   UND SIE HALTEN IHRE ANWEISUNGEN VOR (`einsatz_anweisung()`). `db.php`
 *   setzt `ATTR_EMULATE_PREPARES => false`, also ist jedes `prepare()` ein
 *   Roundtrip. `api/import_commit.php` bereitete seine sieben Anweisungen
 *   deshalb EINMAL vor und fuehrte sie je Einsatz aus — bis zu 3000-mal. Ohne
 *   Zwischenspeicher waeren daraus bis zu 21 000 Roundtrips geworden.
 *   Nachgemessen am csv-Kreislauf: 41,78 s und 41,31 s gegen 41,71 s und
 *   41,47 s davor — dieselbe Streuung.
 *
 * 20.31.0 — DAS SPALTENREGISTER VON `missions`
 *   (22.09.2026, Schritt 15 AP6 — Zentralisierung, R83, E-ZE-22).
 *   NEBEN-Nummer: vier neue Funktionen im Feldkatalog, kein Datenmodell,
 *   keine Migration, `update.php` nicht faellig.
 *
 *   `missions` HAT 41 SPALTEN, UND ZWOELF STELLEN FUEHRTEN EINE EIGENE LISTE
 *   DAVON — jede in ihrer eigenen Reihenfolge, keine sagte, warum eine Spalte
 *   fehlt. Was das kostet, stand im Bestand: Die tote Altspalte
 *   `other_resources` ging jahrelang in jedes Backup, weil dort `SELECT *`
 *   stand; `site_ele_m` steht im Backup, aber in keiner Einspielliste — der
 *   Wert kommt nur wieder, weil die Wiederherstellung ihn hinterher aus den
 *   Phasenkoordinaten NEU RECHNET. Beides fiel erst auf, als jemand die
 *   Listen nebeneinander legte.
 *
 *   JETZT FUEHRT `mf_missions_register()` JEDE SPALTE GENAU EINMAL und sagt
 *   je Zweck, ob sie dabei ist und AN WELCHER STELLE. `mf_spalten($zweck)`
 *   erzeugt daraus die Liste, `mf_spalten_sql()` den SQL-Text. Neun Zwecke:
 *   `export`, `backup`, `backup_restore`, `import_neu`, `import_aendern`,
 *   `ingest_neu`, `schnitt_neu`, `suchindex`, `range`.
 *
 *   WARUM DIE POSITION MITGEFUEHRT WIRD und nicht die Registerreihenfolge
 *   gilt: Die Listen sind in Menge UND Reihenfolge eingefroren. Eine andere
 *   Reihenfolge aendert die Spaltenfolge im CSV-Export — also in einer Datei,
 *   die Menschen aufheben. `mf_spalten()` verlangt deshalb je Zweck eine
 *   lueckenlose Positionsfolge ab 0 und bricht bei einer doppelten oder
 *   fehlenden ab.
 *
 *   NEUN ANWEISUNGEN WERDEN ERZEUGT, und alle neun sind Zeichen fuer Zeichen
 *   dieselben wie vorher (nachgemessen, nicht angenommen). Wo Werte fest im
 *   Satz stehen, haengt die Wertform seither an der SPALTE statt an ihrer
 *   Stelle: `['uhr_gesperrt' => '1', 'origin' => "'import'"]` beim Import,
 *   `COALESCE(?, spalte)` fuer die vier Felder unter der Export-Schranke
 *   (A9/P10), `NULL AS spalte` im Export ohne personenbezogene Angaben. Wer
 *   ein Feld unter die Schranke nimmt, traegt es an EINER Stelle ein.
 *
 *   ZWEI WERTELISTEN VERLIEREN IHRE POSITIONSBINDUNG. In `backup_lib.php`
 *   standen Spalten oben und Werte darunter, in `api/import_commit.php` eine
 *   namenlose Werteliste fuer zwei Anweisungen mit 31 und 28 Spalten. Beide
 *   Kommentare warnten davor, dass ein Einschub stumm alles dahinter
 *   verschiebt — die Warnung war die einzige Sicherung. Jetzt traegt jeder
 *   Wert seinen Spaltennamen, und das Register ordnet zu.
 *
 *   DREI ABBILDUNGEN BLEIBEN VON HAND, und zwar zu Recht: `export_data.php`,
 *   `import_commit.php` und `api/suchindex.php` rechnen je Wert um — nach
 *   Ortszeit, auf eine Laenge, in eine Beschriftung. Ein `implode()` ueber
 *   Spaltennamen kann das nicht. `tools/spaltenregister/pruefen.php` belegt
 *   dafuer, dass jede genau die Registerspalten ihres Zwecks fuehrt; eine
 *   Ausnahme braucht eine Begruendung im Feld, und eine, die nichts mehr
 *   trifft, ist selbst ein Befund.
 *
 * 20.32.0 — ZEIT UND ZAHL: EINE STELLE, AN DER TEXT ENTSTEHT
 *   (22.09.2026, Schritt 15 AP7 — Zentralisierung, R83, E-ZE-23, E-ZE-26;
 *   F-ZE-1, F-ZE-3). NEBEN-Nummer: zwei neue Dateien, kein Datenmodell,
 *   keine Migration, `update.php` nicht faellig.
 *
 *   ZWOELF SACHEN AN 197 STELLEN, und drei davon gab es mehrfach. Bytes
 *   hatten DREI Fassungen: `edbak_groesse_text()` in `adminbackup_lib.php`
 *   (42 Aufrufe in 10 Dateien), `apk_groesse()` und `plattform_groesse()`.
 *   Die dritte trug im Kopf den Satz „dieselbe Schreibweise wie
 *   edbak_groesse_text()" — und das stimmte nie: vierte Stufe „B",
 *   abgeschnittene Nachkommanullen, GB mit einer statt zwei Stellen.
 *   Dieselben Bytes sahen je nach Seite anders aus. Eine vierte Fassung
 *   stand als Inline-JavaScript in `einstellungen.php`.
 *   Zahlen hatten EINE Fassung, und die lag in einer SEITE (`stat_zahl()`)
 *   — ein zweiter Verbraucher konnte sie gar nicht erreichen, deshalb
 *   schrieben 26 Stellen `number_format(x, s, ',', '.')` von Hand.
 *   Anteile hatten KEINE: zehn Handrechnungen, drei Rundungen, fuenf
 *   Bauarten fuer „keine Bezugsgroesse".
 *   Die relative Zeit hatte ZWEI, und sie waren auseinandergelaufen.
 *
 *   `server/format_lib.php` FUEHRT SIE JETZT, DREIZEHN FUNKTIONEN, und jede
 *   ist gegen ihre Vorgaengerin NACHGERECHNET statt begutachtet:
 *   `groesse_text` 3 017 Byte-Werte, `groesse_kurz_text` 3 017,
 *   `zahl_text` 28 Faelle, `prozent_text` 6 030, `zeit_relativ` 10 811
 *   Zeitpunkte, `iso_utc`/`iso_utc_lesen` 5 000 Zeitstempel hin und
 *   zurueck — je 0 Abweichungen. Die Gegenleser haben unabhaengig
 *   nachgemessen; ein Lauf allein brachte 4 420 679 Vergleiche.
 *
 *   ZWEI FUNKTIONEN FUER DEN ANTEIL, NICHT EINE. Fuenf der zehn Stellen
 *   runden AB, weil sie eine SCHWELLE ausloesen; drei kaufmaennisch, weil
 *   sie nur gelesen werden. Eine Funktion ohne diesen Schalter verschoebe
 *   den Ausloesezeitpunkt der Speicher-Warnmail um bis zu einen
 *   Prozentpunkt.
 *
 *   `fmt_local()` ZIEHT AUS `db.php` HIERHER, unter demselben Namen — alle
 *   113 Aufrufer merken nichts. Der Grund ist zwingend: `datum_text()` baut
 *   auf ihr auf, und `format_lib.php` darf die Datenbankdatei nicht laden,
 *   weil `install.php` sie ueber `plattform_lib.php` erreicht, bevor es eine
 *   `config.php` gibt. `local_to_utc()` bleibt in `db.php`: Sie liest einen
 *   Formularwert, um damit zu RECHNEN — die andere Richtung.
 *
 *   DER TRENNER WIRD ANGEHAENGT, NICHT INS FORMAT GESCHRIEBEN. Die
 *   naheliegende Bauform ginge fuer die drei heutigen Trenner zufaellig gut,
 *   weil keiner einen Buchstaben enthaelt. Der erste mit einem Buchstaben
 *   wuerde still zu Formatzeichen — und so einer stand schon im Bestand.
 *
 *   DREI SICHTBARE FOLGEN, alle benannt und entschieden: die Altersangabe
 *   auf Betrieb -> Updates (E-ZE-23, zwei Baender mit zusammen 1 890
 *   Sekundenwerten), „heute" in der App-Zeitzone statt in der der `php.ini`
 *   (F-ZE-1 — auf einem Server in UTC war „heute" zwischen 0 und 2 Uhr
 *   Ortszeit GESTERN), und die Groessenangabe der heruntergeladenen
 *   Sicherungsdatei (E-ZE-26, jetzt dreistufig wie ueberall sonst).
 *
 *   `server/assets/format.js` IST DIE JS-SEITE DAVON (`EdFormat`). Sie
 *   traegt heute zwei Funktionen — genau die, die einen Verbraucher haben;
 *   AP8 baut sie aus. PHP und JavaScript sind ueber 2 014 Byte-Werte
 *   Zeichen fuer Zeichen gegeneinander geprueft.
 *
 *   20.32.1 raeumt einen Fehler genau dieses Umbaus weg: Die Fertigmeldung
 *   des Sicherns sagte „263 KB MB". `EdFormat.groesse()` bringt die Einheit
 *   selbst mit, das Literal „ MB" hinter der Variablen war aus der alten
 *   Rechnung stehengeblieben. Gefunden hat es nicht der Formvergleich
 *   (496 Seiten, 0 abweichende Schreibweisen) — er sieht nur, was eine
 *   aufgerufene Seite anzeigt, und diese Meldung entsteht erst NACH einem
 *   tatsaechlichen Sicherungslauf. Gefunden hat es der edbak-Kreislauf,
 *   und zwar im Protokoll, nicht im Vergleich: Seine 328 771
 *   Einzelvergleiche waren gruen, weil sie den Inhalt der Datei pruefen,
 *   nicht den Satz darueber.
 *
 * 20.33 — EINE KARTE ENTSTEHT AN EINER STELLE (Schritt 15 AP8a).
 *
 *   `EdKarte.anlegen(el, o)` in `assets/map_layers.js`. Vier Seiten bauten
 *   ihre Karte selbst auf, mit denselben vier Zeilen in DREI verschiedenen
 *   Reihenfolgen: L.map, setView, attachBaseLayers, attachFullscreenControl.
 *   Gemessen: 4 von 4 riefen attachBaseLayers(), 4 von 4
 *   attachFullscreenControl(), 1 von 4 attachGroessenControl() — und KEINE
 *   setzte auch nur eine der ueblichen Leaflet-Optionen. Der gemeinsame Teil
 *   war also fast alles, und die Unterschiede sind drei Parameter:
 *   `mitte`/`zoom` (zwei Ausschnitte bei vier Seiten, OHNE Vorgabewert —
 *   ein Vorgabewert zoege sie auf einen), `groesse` (der dritte Kartenknopf
 *   gehoert nur der Tagesuebersicht, Backlog Nr. 45) und `leaflet`
 *   (`preferCanvas` gilt nur der Zeitraumansicht).
 *
 *   EINE SICHTBARE FOLGE, benannt und entschieden: Auf der Tagesuebersicht
 *   kommt `setView()` jetzt ZUERST statt zuletzt. Drei der vier Seiten
 *   machten es schon so, und der Grund steht ausgeschrieben in ihren
 *   Kommentaren — ohne festen Ausschnitt gilt die Karte Leaflet als nicht
 *   bereit und rechnet Pin-Positionen nicht aus. Nachgemessen: 496 Seiten
 *   im Formvergleich, 0 Befunde ausserhalb des CSP-Verstossprotokolls;
 *   Klickprobe 48/48; die vier Karten im Browser mit Kacheln, Umschalter
 *   und 0 Konsolenfehlern.
 *
 * 20.34 — VIER ZENTRALEN IM BROWSER (Schritt 15 AP8b bis AP8f).
 *
 *   `assets/api.js` (`EdApi`), `EdHtml.meldung()`, der Ausbau von
 *   `EdFormat` und `EdPat.listeLaden()`. Sechsundvierzig Stellen in
 *   vierzehn Dateien.
 *
 *   DER BEFUND, der den Umbau geformt hat: Der zentralisierbare Kern liegt
 *   fast ueberall VOR dem eigentlichen Vorgang. Alle 15 Sendestellen waren
 *   im AUFRUF zeichengleich — POST, zwei Kopfzeilen, JSON.stringify — und
 *   gingen erst HINTER dem `fetch` auseinander, auf sieben Achsen: vier
 *   Regeln fuer „Erfolg" (ACHT von 15 prueften `res.ok` gar nicht), zwei
 *   Politiken bei Nicht-JSON, SECHS Vorrangketten, FUENF Satzbauten,
 *   SIEBEN Anzeigewege, und VIERZEHN von 15 zeigten im Netzfehler den
 *   englischen Browsertext „Failed to fetch".
 *
 *   DER SATZBAU IST JETZT EINER: `<Vorgang> ist fehlgeschlagen: <Grund>`.
 *   Das ist die siebte benannte Ausnahme von E-ZE-10 und vom Auftraggeber
 *   entschieden. Der Vorgangsname steht genau einmal; `error` erscheint
 *   nicht mehr als Satz (es traegt Maschinenwoerter), sondern als Kennung
 *   in einer Klammer; `hinweis` und `text` kommen in die Kette und damit
 *   der Satz zu `post_max_size` ueberall an, wo er bisher an elf von
 *   fuenfzehn Stellen fehlte.
 *
 *   DREI SCHREIBWEISEN WURDEN VEREINHEITLICHT, jede gemessen: die Minute
 *   einer Dauer ist immer zweistellig (16,0 % der Minutenwerte), „60min"
 *   gibt es nicht mehr (0,83 % der Sekundenwerte), und die Streckensumme
 *   traegt ueberall den Tausenderpunkt — die Startseite schrieb als
 *   einzige „1633 km", wo Suche und Zeitraum „1.633 km" zeigten.
 *
 *   `api.js` UND `format.js` STEHEN IM <head>, nicht in der Immer-Liste.
 *   Der erste Anlauf legte sie in `ui_geruest_ende()`, mit dem Satz, das
 *   trage schon, weil jeder Aufruf in einem Zuhoerer stecke. Ein
 *   gegenlesender Agent hat den Satz mit Zeilennummern widerlegt: Auf
 *   `einstellungen.php` und `import.php` steht diese Liste NACH den
 *   Seitenskripten, und dort laeuft `unlock.js` seinen Sendeweg zur
 *   LADEZEIT. `EdApi` waere undefiniert gewesen, der ReferenceError waere
 *   in einen absichtlich stillen catch gefallen — die KDF-Anhebung haette
 *   auf zwei Seiten wortlos aufgehoert zu laufen.
 *
 *   VIER ZIELZAHLEN ENDEN NICHT BEI NULL, jede mit Grund im Register
 *   (Entscheidung des Auftraggebers: „ehrliche Zahl statt runder Null").
 *   Z29 bei 2, Z34 bei 5, Z36 bei 1, Z37 bei 4. Was dort steht, ist kein
 *   zweiter Rechenweg, sondern die Zentrale selbst, ein Fehlalarm des
 *   Zaehlmusters oder eine benannte Vorgabe je Zusammenhang.
 *
 *   ZWANZIG AGENTEN HABEN GEGENGELESEN und 41 Maengel gefunden, zwei davon
 *   schwer. Beide waren echt. Was das kostet, steht im Konzept; was es
 *   bringt, steht in diesem Absatz und im vorigen.
 *
 *   ZWOELF STELLEN BLEIBEN NAMENTLICH STEHEN, jede mit Grund im Register:
 *   vier Formular- und Vergleichswerte in `betrieb_server.php` (der PUNKT
 *   als Dezimaltrenner ist dort Bedingung eines Vergleichs, nicht
 *   Geschmack), zwei CSS-Laengen, die bewusst gar nicht runden, drei
 *   Stellen in `wartung_lib.php` (deren Dateikopf zusagt, NICHTS zu laden),
 *   zwei Zeitstempel ohne Zonenumrechnung und eine `sprintf`-Groesse mit
 *   Punkt statt Komma.
 *
 * 20.35 — WINDE UND BERGWACHT: DIE AUSWERTUNG FOLGT DER BETRIEBSART
 *         (Schritt 15 AP9a, E-ZE-31).
 *
 *   Eine FUNKTIONSAENDERUNG, ausdruecklich freigegeben — kein Umbau.
 *   `cap_gate` stand seit Web 5.10.0 im Feldkatalog, wurde aber nur im
 *   Einsatzformular ausgewertet. Die Tagesuebersicht bekam es nie zu sehen:
 *   Sie zog ihre Spalten aus `mf_tagesspalten()`, und die Funktion nimmt
 *   keinen Parameter, kennt keinen Diensttag und cacht statisch. Gemessen
 *   am 22.09.2026: ALLE 69 Diensttage des Referenzbestands trugen die
 *   Windenspalte, auch ein NEF ohne Winde.
 *
 *   ZWEI ORTE, ZWEI REGELN, und das ist kein Widerspruch, sondern der
 *   Unterschied zwischen ERFASSEN und AUSWERTEN. Die Einsatzbearbeitung
 *   (`einsatz_form.php`, `einsatz.php`) folgt der FAEHIGKEIT allein, auch
 *   bodengebunden — unveraendert, das ist `cap_gate`. Tages- und
 *   Zeitraumuebersicht folgen der BETRIEBSART UND der Faehigkeit: Spalten
 *   nur an einem luftgebundenen Tag, der sie traegt. Die Suche folgt der
 *   Faehigkeit ueber Luft UND Boden — sie sucht im ganzen Bestand, nicht
 *   in einem Zeitraum.
 *
 *   WAS DAS KOSTET, ist vorgelegt und entschieden worden: vier
 *   bodengebundene Bergwacht-Diensttage mit Faehigkeiten, zwei davon mit
 *   einem dokumentierten Windeneinsatz. Diese Haken bleiben eintragbar und
 *   in der Einsatzbearbeitung sichtbar, erscheinen aber nicht mehr in der
 *   Tagestabelle.
 *
 *   DIE KACHELN UNTER 720 px SIND AUSGENOMMEN. Ihre Plaketten zeigen einen
 *   TATSAECHLICH GESETZTEN Haken, keine vorgehaltene Spalte. Eine Spalte
 *   ist Platz, eine Plakette ist ein Befund — vorhandene Daten zu verbergen
 *   hat niemand entschieden.
 *
 *   `api/range.php` BEHAELT seinen `d.kind = 'air'`-Filter. Er war als
 *   Luecke gemeldet (Backlog Nr. 198) und ist mit dieser Entscheidung genau
 *   die Regel. `api/suchindex.php` bekommt dieselbe Abfrage OHNE Artfilter.
 *
 *   Nachgemessen: fuenf Diensttage des Demo-Bestands, je vier Zahlen
 *   (sichtbare Koepfe, versteckte Koepfe, Zellen der ersten Zeile,
 *   Eintraege des Sortierblatts) — 11/0/11/10 am Lufttag mit Faehigkeit,
 *   9/2/9/8 an den vier uebrigen; vorher 11 an allen fuenf. Zeitraum: der
 *   bodengebundene Tab verliert zwei Spalten (10 -> 8), Luft und Mix
 *   unveraendert. Gegenprobe zur Suche: mit abgefangener Antwort
 *   `faehigkeiten = {false, false}` verschwinden beide Spalten, obwohl im
 *   Bestand 7 Einsaetze mit Winden- und 15 mit Bergwachthaken stehen — die
 *   Faehigkeit entscheidet, nicht das Datum. 0 Konsolenfehler.
 *
 * 20.36 — DIE SUCHE FOLGT DER FAEHIGKEIT AUCH IN IHREN FILTERN
 *         (Schritt 15 AP9a, Nachtrag; E-ZE-35).
 *
 *   20.35 hat die SPALTEN der Suchtabelle an die Faehigkeit gehaengt, die
 *   FILTER aber beim Bestand gelassen — das war als benannte Grenze
 *   stehengeblieben und ist jetzt entschieden worden: „Suche immer moeglich,
 *   sobald Faehigkeiten vorkommen."
 *
 *   WAS VORHER WAR: `spaltenMitBestand()` zeigt einen Filter nur, wenn
 *   IRGENDEIN Einsatz zu seiner Spalte etwas fuehrt. In einem Bestand mit
 *   eingerichteter, aber nie benutzter Winde verschwand damit der ganze
 *   Block „Bergrettung" — acht Filter —, und „Winde: nein" liess sich nicht
 *   suchen. Der Filter fehlte genau dann, wenn man ihn zum Nachweis einer
 *   Null gebraucht haette.
 *
 *   `KATALOG_CAP` bildet Spalte auf Faehigkeit ab, ERZEUGT aus 'cap_gate'
 *   und auf die Unterfelder VERERBT: `winch_cycles` steht unter `winch` und
 *   braucht keinen eigenen Eintrag. Keine zweite Liste — dieselbe
 *   Ueberlegung wie bei `KATALOG_ART` (S3/AP9). Felder ohne Faehigkeit
 *   folgen unveraendert dem Bestand.
 *
 *   Nachgemessen in drei Zustaenden, je acht Filter und der Block:
 *   Faehigkeit ja / Haken ja -> 8, Block da. Faehigkeit NEIN / Haken ja
 *   (abgefangene Antwort) -> 0, Block weg. Faehigkeit ja / Haken NIRGENDS
 *   -> 8, Block da; vorher waren es hier 0. 0 Konsolenfehler.
 */
const WEB_VERSION = '20.36.0';
