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
 *            Referenzbestand 82 Einsaetze hat.
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
 *   F-P3-AS  `<div class="login-wrap">` in pw_handling.php war seit jeher
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
 */
const WEB_VERSION = '9.11.0';
