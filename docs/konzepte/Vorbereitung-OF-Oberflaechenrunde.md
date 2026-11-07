# Vorbereitung — Oberflächenrunde (OF)

**Zielpfad:** `docs/konzepte/Vorbereitung-OF-Oberflaechenrunde.md` — eine
Sammlung der Punkte, der gefallenen Entscheidungen und der offenen Fragen,
aus der das Konzept OF entsteht. Kein Konzept nach K1, keine Fahrplanzeile,
kein Code.
**Zweig:** `claude/oberflaechenrunde-of` (aufgesetzt auf den Abschluss von
Schritt 26, PR #157).
**Grundlage:** Durchsicht von Staging (Web 23.11.3) und Produktiv (Web
23.8.2) durch die Betreiberin am 07.11.2026, je Punkt ein Bildschirmfoto im
Gespräch. Wo das Bild von Produktiv stammt, ist der Punkt auf dem neuen
Stand (Staging) gegenzuprüfen, bevor er gebaut wird.
**Modell:** Das Konzept schreibt **Fable** (Auftrag der Betreiberin,
07.11.2026). Diese Datei sammelt nur.
**Mockups (07.11.2026):** A Navigation https://claude.ai/artifact/3q3V9kYSGenWHKSeJCMYmp · B Einstellungen https://claude.ai/artifact/FLBQ9RmPrm7quLHSUdoxA9 · C Betrieb https://claude.ai/artifact/Se94QDeSS1Vkktmmv6ZPug · D Einsatz und Karte https://claude.ai/artifact/WHAx1KgVdVudfBPRmKqX5Q · E Startseite und Suche (in Arbeit). Befund zu P-37: Der gestrichelte Kreis ist das Zeichen „ohne Rettungsmittel“, nur falsch platziert. Form: als Artifact (private Seiten auf claude.ai), im Live-Umfeld
(echtes Stylesheet, echte Texte, Demo-Daten). Entschiedene Mockups kommen
als Bild nach `docs/konzepte/konzept-of/mockups/`.

**Bezeichner:** Punkte **OF-P-nn** (Nummer aus der Sammlung), Entscheidungen
**E-OF-nn**, Fragen an die Betreiberin **Q-OF-nn**. Nummern zweistellig, nie
wiederverwendet.

---

## 1. Auftrag

Anlass (07.11.2026, nach dem Merge von Schritt 26): Die Betreiberin sammelt
Probleme der Oberfläche; Claude nimmt sie auf, ohne zu bauen. Danach:
Fragen klären, die die Mockups brauchen; Mockups im Hintergrund bauen;
übrige Fragen klären; das Konzept schreibt Fable.

Grundsatz aus mehreren Punkten (7, 10, 19, 23–26): **eine zentrale Regel
statt einer Korrektur je Seite** — als Baustein in `ui.php`/`style.css`,
in `Design.md` beschrieben, und wo möglich von einem Prüfmittel gemessen,
damit es nicht wieder passiert.

## 1a. Verhältnis zu Konzept NV (Navigation)

Am 07.11.2026 kam die Übergabe **NV** dazu (`Konzept-NV-Navigation.md`,
Mockups in `konzept-nv/mockups/`): Variante D ist entschieden (E-NV-01) —
Bereichszeile unter der Kopfleiste, Leiste und Inhalt in einer
Rahmenkarte, Karten darin als Abschnitte. NV ist ein fertiges Konzept für
Opus, OF wird von Fable geschrieben. Überschneidungen:

| OF | NV | Folge |
|---|---|---|
| P-04 Markierung aktiver Elemente | Leiste zeigt nur den aktiven Bereich | Mockups von P-04 in der NV-Leiste |
| P-07 Knopf zwischen Karten | E-NV-03: Karten werden in der Rahmenkarte zu Abschnitten | Regel P-07 gilt im NV-Gerüst für Abschnitte |
| P-19 Seitenbreite | Rahmenkarte 1680 px, Lesespalte im Inhalt | P-19 geht weitgehend in NV-02 auf |
| P-20 Reiter | Bereichszeile ist ein Reiter-Baustein | Seitenreiter und Bereichszeile in einer Sprache (Q-OF-09) |
| P-29 Fußzeile | Fußzeile „unverändert" unter der Rahmenkarte | Mockups von P-29 auf dem NV-Gerüst |
| Q-OF-08 Übersicht doppelt | Q-NV-03: Übersicht bleibt (Empfehlung) | Q-OF-08 wird mit Q-NV-03 entschieden |

Die Mockups der Gruppen Einstellungen und Betrieb entstehen deshalb im
NV-Gerüst.

## 2. Die Punkte

| Nr. | Punkt | Bereich | Art | Stand |
|---|---|---|---|---|
| OF-P-01 | Stufe 2 nach dem Merge rot: Staging in Wartung wegen offener Migration (HTTP 503) | Kette | Ablauf | erledigt (`update.php`, Stufe 2 neu gestartet) |
| OF-P-02 | Tagesübersicht: Abstand zwischen Karte und Kachel „Ruhesegmente" zu klein | Tag | Abstand | erledigt sich mit P-03, sonst prüfen |
| OF-P-03 | Kachel „Ruhesegmente" entfällt; Schneiden ins Aktionsmenü des Diensttags | Tag | Umbau | entschieden (E-OF-05) |
| OF-P-04 | Markierung aktiver Elemente (Strich links, mehrere Farben/Orte) | alle | Gestaltung | entschieden (E-OF-16): ohne Markierung |
| OF-P-05 | Segmentwahl „Wochentage": Beschriftung nicht senkrecht mittig | Baustein | Korrektur | alle Segmentwahlen prüfen |
| OF-P-06 | Datumsfelder zu schmal, Format je Browser („dd/mm/yyyy") | Baustein | Umbau | entschieden (E-OF-06) |
| OF-P-07 | Speicherknopf zwischen Karten ohne Bezug („Profil speichern", „Passwort ändern") | alle | Regel | entschieden (E-OF-32): Fuß im Abschnitt oder klebende Speicherleiste; Prüfmittel offen (Q-OF-03) |
| OF-P-08 | Zweitfaktor, Zeile „Gemerkte Geräte": Plakette und Knopf senkrecht verschoben | Profil | Korrektur | Zeilenbaustein prüfen |
| OF-P-09 | Karte „Zweitfaktor" zu kompliziert | Profil | Gestaltung | entschieden (E-OF-19): ein Satz plus „Verwalten" |
| OF-P-10 | Zu viel Erklärtext (Konto hält, Konto löschen u. a.) | alle | Regel | Regel „ein Satz je Karte" durchsetzen, Prüfmittel; bei „Konto löschen" das Wesentliche sichtbar lassen |
| OF-P-11 | Seite „Geräte" unintuitiv (Reihenfolge, Text, Warnung, Platzzahl, Menühinweis, APK) | Einstellungen | Gestaltung | entschieden (E-OF-25): nach Typ, Koppeln im Dialog |
| OF-P-12 | Hinweis zur Adresssuche unter jedem Ortsfeld zu lang | Baustein | Umbau | entschieden (E-OF-07) |
| OF-P-13 | ~~Adresssuche über den Server leiten~~ | — | — | gestrichen (E-OF-04) |
| OF-P-14 | Kachel „GPS-Daten verdichten" für NutzerInnen kaum relevant | Backup | Umbau | Q-OF-04 |
| OF-P-15 | Seitenerklärung „Backup": schwer lesbar, zu lang | Backup | Text/Regel | mit P-10 |
| OF-P-16 | Untermenü listet „Für dich freigegebenes Backup", die Karte fehlt | Navigation | Fehler | Sprungliste listet nur vorhandene Karten; alle Seiten prüfen |
| OF-P-17 | Seite „Import / Export" unintuitiv, zu viel Text, zu breite Elemente | Einstellungen | Gestaltung | entschieden (E-OF-26): erst wählen, dann füllen |
| OF-P-18 | Kontenliste: Spalte „Öffnen" verlangt waagerechtes Scrollen | Verwaltung | Umbau | Vorschlag Q-OF-05 |
| OF-P-19 | Seitenbreite uneinheitlich (Installation schmal, NutzerInnen breit) | alle | Regel | entschieden (E-OF-31): Felder begrenzt, nicht Abschnitte; Prüfmittel offen (Q-OF-03) |
| OF-P-20 | Reiter kaum als Bedienelement erkennbar | Baustein | Gestaltung | entschieden (E-OF-17): gleiche Form wie die Bereichszeile |
| OF-P-21 | Demo-Konto als Karte unter Installation (an/aus, letzter Reset) | Verwaltung | Umbau | entschieden (E-OF-08); Kontrollzahlen zur Statusseite |
| OF-P-22 | Listenkopf (Suche, Zeitfilter, Art) unübersichtlich, aktiver Zeitraum nicht erkennbar | Baustein | Gestaltung | entschieden (E-OF-18): Filterknopf |
| OF-P-23 | Statusseite zu voll | Betrieb | Gestaltung | entschieden (E-OF-20): Probleme zuerst |
| OF-P-24 | Hintergrundjobs: ausdünnen, Plaketten brechen um, „Anhalten" umbenennen | Betrieb | Umbau | entschieden (E-OF-10, E-OF-21) |
| OF-P-25 | Servereinstellungen vereinfachen | Betrieb | Gestaltung | entschieden (E-OF-22): Klappkarten |
| OF-P-26 | Komplett-Backup vereinfachen | Betrieb | Gestaltung | entschieden (E-OF-23): Stand oben, Klappkarten |
| OF-P-27 | Backup-Ziele als eigene Seite auflösen | Betrieb | Umbau | entschieden (E-OF-24): an beiden Orten |
| OF-P-28 | Dark Mode mit Umschalter in der Kopfleiste | alle | Funktion | entschieden (E-OF-09) |
| OF-P-29 | Fußzeile als heller Streifen unter allem | alle | Gestaltung | entschieden (E-OF-34): dunkelblauer Abschluss |
| OF-P-30 | Symbole „download"/„upload" für GPX | Tag, Einsatz | Symbol | entschieden (E-OF-11) |
| OF-P-31 | Plakette „GPS-Daten ausgedünnt" weglassen | Einsatz | Umbau | entschieden (E-OF-12) |
| OF-P-32 | Einsatzformular (nachtragen und bearbeiten): Schalter und abhängige Felder ordnen | Einsatz | Gestaltung | entschieden (E-OF-33): eine Karte, Transport als drei abwählbare Felder; zentral im Feldkatalog |
| OF-P-33 | Handbuch-Link in Seitentexten | alle | Text | entschieden (E-OF-02) |
| OF-P-34 | Schlösser in einer Spalte statt hinter der Beschriftung | Einsatz | Gestaltung | entschieden (E-OF-28): Schloss links in eigener Spalte; Riegel `kennzeichnung` muss halten |
| OF-P-35 | Kartenauswahl ohne Anbieternamen | Karte | Text | entschieden (E-OF-13) |
| OF-P-36 | Leaflet-Bedienelemente ins Branding | Karte | Gestaltung | entschieden (E-OF-29): Vorschlag |
| OF-P-37 | Papierkorb: gestrichelter Kreis neben „Wiederherstellen", Symbol des Knopfs | Papierkorb | Fehler/Symbol | entschieden (E-OF-30): Symbol restore; Kreis klären |

Nebenbefunde aus den Bildern, ohne eigene Nummer bis zur Entscheidung:
Plakette im Kartentitel bei schmaler Breite abgeschnitten, Leerzeile unter
„Einsatzort" (bei P-34); Übersichtsseite „Einstellungen" doppelt zur
Seitenleiste und „Abmelden" doppelt (Q-OF-08); auf der Statusseite fünf
Wörter für denselben grünen Zustand (bei P-23); Auslöser „token" auf der
Seite Hintergrundjobs (bei P-24).

## 3. Entscheidungen

| Nr. | Entscheidung | Von wem |
|---|---|---|
| E-OF-01 | Mockups als Artifact im Live-Umfeld; entschiedene Mockups als Bild nach `konzept-of/mockups/` | Betreiberin, 07.11.2026 |
| E-OF-02 | Kein Verweis „Handbuch: …" mehr in Seitentexten und Karten, überall; der Weg ins Handbuch ist das „?" der Kopfleiste. Gekürzte Erklärungen stehen ohne Verweis | Betreiberin, 07.11.2026 |
| E-OF-03 | Eigenes Konzept OF auf eigenem Zweig; das Konzept schreibt Fable | Betreiberin, 07.11.2026 |
| E-OF-04 | P-13 gestrichen: Die Adresssuche bleibt im Browser. Über den Server ginge der Klartext der verschlüsselten Ortsfelder durch ihn hindurch (`CLAUDE.md` 4) | Betreiberin, 07.11.2026 |
| E-OF-05 | P-03: Die Kachel „Ruhesegmente" entfällt; im Aktionsmenü des Diensttags heißt der Weg „Einsatz aus GPS-Daten ausschneiden" | Betreiberin, 07.11.2026 |
| E-OF-06 | P-06: Eigenes Datumsfeld `TT.MM.JJJJ` mit Maske wie die Zeitfelder, ohne Kalender | Betreiberin, 07.11.2026 |
| E-OF-07 | P-12: Am Ortsfeld nur ein Symbol; der Satz zur Adresssuche erscheint beim Antippen bzw. Darüberfahren und ist mit Tastatur und Screenreader erreichbar | Betreiberin, 07.11.2026 |
| E-OF-08 | P-21: „Aus" sperrt die Anmeldung des Demo-Kontos; Konto und Daten bleiben, der Reset ruht, die Warnung zum Schlüsselmaterial verschwindet. Ob es heute schon einen Schalter gibt, ist zu prüfen | Betreiberin, 07.11.2026 |
| E-OF-09 | P-28: Vier Einstellungen im Profil — **Umschalter (Vorgabe)**: Mond/Sonne zwischen „?" und Zahnrad, Wahl gespeichert; **Immer hell** und **Immer dunkel** ohne Umschalter; **Folgt System**: folgt dem Gerät und zeigt den Umschalter, ein Klick gilt **bis zur Abmeldung**. Gespeichert **im Konto** (Migration). Erster Aufruf im Umschalter-Modus nach dem Gerät; ohne Anmeldung nach dem Gerät, ohne Umschalter (Vorschlag, nicht widersprochen). Symbole Tabler `moon`/`sun` | Betreiberin, 07.11.2026 |
| E-OF-10 | P-24: „Anhalten" heißt „Jobs vorübergehend pausieren"; die Begriffe werden überall nachgezogen (Knopf, Meldungen, Handbuch, Protokoll) | Betreiberin, 07.11.2026 |
| E-OF-11 | P-30: Tabler `download` für „GPS-Daten als GPX", `upload` für „GPX importieren"; beide neuen Zeichen freigegeben | Betreiberin, 07.11.2026 |
| E-OF-12 | P-31: Die Plakette „GPS-Daten ausgedünnt" entfällt; die Auskunft bleibt in der GPX-Datei und im Handbuch | Betreiberin, 07.11.2026 |
| E-OF-13 | P-35: Kartenauswahl ohne Anbieternamen (Standard, Wanderkarte, Topografisch, Satellit); die Zuschreibung unten rechts muss je Ebene den Anbieter nennen — vorher prüfen | Betreiberin, 07.11.2026 |

| E-OF-14 | Q-NV-04: Die rote Zahl steht am Reiter jedes Bereichs, summiert über seine Punkte (Verwaltung z. B. überfällige Konto-Backups, Betrieb die Statusbefunde) | Betreiberin, 07.11.2026 |
| E-OF-15 | Q-OF-09: Ob Seitenreiter wie die Bereichszeile aussehen, wird an den Mockups zu P-20 entschieden | Betreiberin, 07.11.2026 |
| E-OF-16 | P-04: Variante **A — ohne Markierung**: Der aktive Eintrag trägt nur die Auswahlfläche, kein Strich links; gilt für jede Leiste (Einstellungen, Diensttage, Vorschläge). Bild `konzept-of/mockups/OF-P04-A-ohne-markierung.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup A) |
| E-OF-17 | P-20 und Q-OF-09: Variante **A — gleiche Form**: Seitenreiter (Rechtstexte, Protokoll, Statistik u. a.) sehen aus wie die Bereichszeile aus NV; ein Reiter-Baustein für beide Ebenen. Bild `konzept-of/mockups/OF-P20-A-gleiche-form.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup A) |
| E-OF-18 | P-22: Variante **B — Filterknopf**: Neben dem Suchfeld ein Knopf „Filter" mit Plakette (Zahl der gesetzten Filter), der die Filter aufklappt. Nachgebessert auf Hinweis: Der Knopf ist so hoch wie das Suchfeld, kräftiger Rand, fett, mit Symbol Tabler „filter" (neu im Vorrat, Design.md 6). Bild `konzept-of/mockups/OF-P22-B-filterknopf.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup A) |
| E-OF-19 | P-09: Variante **B — ein Satz plus „Verwalten"**: Die Karte Zweitfaktor zeigt zugeklappt einen Satz mit allem Wichtigen und einen Knopf „Verwalten"; aufgeklappt die Zeilen mit ihren Handlungen. Bild `konzept-of/mockups/OF-P09-B-satz-verwalten.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup B) |
| E-OF-20 | P-23: Variante **A — Probleme zuerst, Bereiche zugeklappt**: Oben ein Abschnitt mit genau den Zeilen, die etwas brauchen, je mit ihrem Bereich; darunter jeder Bereich als zugeklappter Abschnitt mit Zahl und Plakette. Bild `konzept-of/mockups/OF-P23-A-probleme-zuerst.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup C) |
| E-OF-21 | P-24: **Vorschlag** übernommen: „Jobs vorübergehend pausieren“ als eigene Karte oben; im Zustand je Job ein kurzer Satz, Plaketten brechen nicht um („heute 23:24“ statt Datum); Auslöser, Kettentoken und Monitoring in der üblichen Klappkarte, Zustand als Plakette im Kopf. Bild `konzept-of/mockups/OF-P24-vorschlag.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup C) |
| E-OF-22 | P-25: **Vorschlag** übernommen: Jede Karte der Servereinstellungen wird Klappkarte; zugeklappt zeigt sie den eingestellten Stand in einer Zeile und die Plakette, aufgeklappt das Formular. Der Klappbaustein ist derselbe wie bei P-24. Ob die Backup-Ziele dorthin wandern, ist P-27 und noch offen. Bild `konzept-of/mockups/OF-P25-vorschlag.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup C) |
| E-OF-23 | P-26: **Vorschlag** übernommen: Oben der Stand (offen), darunter Regeln, Stände und Backup-Ziele als Klappkarten in derselben Form wie die Servereinstellungen; „Jetzt sichern“ ist die eine orange Hauptaktion. Ob die Backup-Ziele zusätzlich dort und in den Servereinstellungen stehen, ist P-27 und noch offen. Bild `konzept-of/mockups/OF-P26-vorschlag.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup C) |
| E-OF-24 | P-27 und Q-OF-06: Die Seite „Backup-Ziele“ entfällt. Die Ziele stehen **an beiden Orten** als dieselbe Klappkarte (ein Baustein): als letzte Karte in den Servereinstellungen und unter den Ständen im Komplett-Backup. „Ziel anlegen“ öffnet den heutigen Dialog. Bilder `konzept-of/mockups/OF-P27-servereinstellungen.png`, `…/OF-P27-komplett-backup.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup C) |
| E-OF-25 | P-11: Variante **B2 — nach Gerätetyp, Koppeln im Dialog**: Je eine Karte für Garmin und für Android/Wear OS mit den gekoppelten Geräten; darunter nur ein Knopf „Weitere Uhr koppeln“ bzw. „Weiteres Gerät koppeln“. Er öffnet einen Dialog mit den drei Schritten aus Variante A (App installieren, Kopplung am Gerät starten, Code eingeben), angepasst an den Gerätetyp. Platzzahl als Kleinzeile unter den Karten. Bild `konzept-of/mockups/OF-P11-B2-dialog.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup B: „B, unter ‚weitere Uhr koppeln‘ dann ein Popup“, danach „so wie Mockup“) |
| E-OF-26 | P-17: Variante **C — erst wählen, dann füllen**: Zwei Wahlkarten „Liste übernehmen“ und „Liste ausgeben“ mit je einem Satz, darunter nur das passende kurze Formular. Bild `konzept-of/mockups/OF-P17-C-wahlkarten.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup B) |
| E-OF-27 | Regel (aus P-29): „Abmelden“ steht am Rechner nur unten in der Leiste, nie im Inhalt; der große Knopf am Ende der Einstellungs-Übersicht entfällt dort. Am Handy steht es in der Schublade. Die Lage der Fußzeile (P-29) ist damit noch nicht entschieden | Betreiberin, 07.11.2026 (Kommentar im Mockup A: „Abmelden hat in der Mitte nix zu suchen“) |
| E-OF-28 | P-34: Variante **Links vor der Beschriftung**: feste Beschriftungsspalte (14 rem), das Schloss steht vor dem Wort in einer eigenen schmalen Spalte, Felder ohne Schloss rücken gleich weit ein. Der Riegel `kennzeichnung` (Sollliste) muss weiter halten. Bild `konzept-of/mockups/OF-P34-links.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup D) |
| E-OF-29 | P-36 (mit P-35): **Vorschlag** übernommen: Kartenknöpfe in Bedienhöhe der App (44 px, am Zeigergerät 36 px), Kartenfläche, starke Linie, Radius wie Knöpfe, kein Schatten; Ebenenwahl mit dem App-Symbol „Karte“, offen mit Überschrift „Kartenart“, Zeilen in Bedienhöhe, Auswahl Dunkelblau, Namen ohne Anbieter (die Anbieter bleiben im Quellennachweis). Bilder `konzept-of/mockups/OF-P36-vorschlag.png`, `…/OF-P36-vorschlag-ebenen.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup D) |
| E-OF-30 | P-37 und Q-OF-07: Variante **A — Tabler „restore“** (kreisender Pfeil mit Punkt) für „Wiederherstellen“; nicht direkt neben „Aktualisieren“ stellen, die beiden sind verwandt. Der gestrichelte Kreis (Zeichen „ohne Rettungsmittel“, falsch platziert) wird unabhängig davon entfernt. Bild `konzept-of/mockups/OF-P37-A-restore.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup D) |
| E-OF-31 | P-19: **Vorschlag** übernommen: Jeder Abschnitt und seine Linie reichen bis zur rechten Kante der Rahmenkarte; begrenzt werden die Felder (36 rem), nicht die Abschnitte. Alle Seiten haben dieselbe rechte Kante; Listenseiten bleiben, wie sie sind. Ob ein Prüfmittel die Regel misst, ist Q-OF-03. Bild `konzept-of/mockups/OF-P19-vorschlag.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup B) |
| E-OF-32 | P-07, Regel für alle Seiten: Speichert ein Knopf **einen** Abschnitt, steht er als letztes Element in ihm. Speichert er **mehrere**, erscheint am unteren Rand der Rahmenkarte eine klebende Speicherleiste, sobald etwas geändert ist: Fläche Auswahlblau (`--auswahl-flaeche`, schaltet im Dunkelmodus mit), kein Strich und kein Bogen darüber, links fett „Geändert: …“, rechts der Knopf. Sie gehört zu keinem einzelnen Abschnitt. Ob ein Prüfmittel Knöpfe zwischen Abschnitten meldet, ist Q-OF-03. Bild `konzept-of/mockups/OF-P07-speicherleiste.png` | Betreiberin, 07.11.2026 (Kommentare im Mockup B, nach zwei Nachbesserungen) |
| E-OF-33 | P-32 (nachtragen und bearbeiten): eine Karte „Einsatz und Transport“. Oben die Schalter Sekundärtransport und Fehleinsatz nebeneinander; dann die Transportart als Segmentwahl mit **drei Feldern Luft, Boden, Ambulant**, höchstens eines gewählt, ein zweiter Klick nimmt die Wahl zurück (dann offen, kein eigenes Feld „offen“); darunter Transportziel und die Schalter NA-Begleitung und Schockraum nebeneinander, ohne Einzug. Bei „Ambulant“ fallen Ziel und Schalter weg. Umsetzung zentral im Feldkatalog. Bilder `konzept-of/mockups/OF-P32-vorschlag-leer.png`, `…/OF-P32-vorschlag-gefuellt.png` | Betreiberin, 07.11.2026 (Kommentare im Mockup D) |
| E-OF-34 | P-29: Variante **C — dunkelblauer Abschluss**: Die Fußzeile ist ein schmaler Streifen in Dunkelblau wie die Kopfleiste, unter der Rahmenkarte über die ganze Breite; die Seite ist oben und unten gerahmt. Im Inhalt steht kein „Abmelden“ (E-OF-27). Entschieden an einer ganzen, scrollbaren Seite im Gerüst aus Konzept NV. Bild `konzept-of/mockups/OF-P29-C-dunkelblau.png` | Betreiberin, 07.11.2026 (Kommentar auf der Vergleichsseite A/C) |
| E-OF-35 | Q-OF-10: **NV geht in OF auf.** Fable plant beides in einem Konzept, gebaut wird einmal. Weg (Vorschlag Claude, Betreiberin ohne starke Präferenz): Die Pakete NV-01 bis NV-04 werden die ersten Pakete von OF — das Gerüst zuerst, weil alle Mockups darauf stehen —, NV-05 geht im Abschlusspaket von OF auf. E-NV- und Q-NV-Nummern bleiben, wie sie sind, und werden nicht umnummeriert. `Konzept-NV-Navigation.md` bleibt als Quelle liegen, bis das Konzept OF steht, und wird dann mit seinen Mockups in dessen ersten Commit übernommen und gelöscht | Betreiberin, 07.11.2026 |
| E-OF-36 | Q-NV-01, Teil Startseite (Mockup E, E1): Die Startseite bekommt die gemeinsame Rahmenkarte (Diensttage-Leiste und Inhalt in einer Fläche), Unterform **2 — Karten bleiben Karten** darin. Damit liegen auf der Startseite Karten mit Rand in der umrandeten Rahmenkarte; das widerspricht Prüfpunkt P-NV-04 („keine Karte in der Karte“), der für die Startseite deshalb nicht gilt — Fable zieht ihn im Konzept OF nach. Suche (E3) und das Scrollen der Leiste (E2) sind noch offen. Bilder `konzept-of/mockups/OF-E1-2-karten-1920.png`, `…/OF-E1-2-karten-390.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup E) |
| E-OF-37 | Startseite beim Scrollen (Mockup E, E2), Unterform **2 — Karten**: Die Diensttage-Leiste klebt unter der Kopfleiste, ist höchstens so hoch wie das Fenster und scrollt in sich; „Diensttag anlegen“ und „Papierkorb“ bleiben unten sichtbar; die Fläche der Leistenspalte reicht bis zum Boden der Rahmenkarte. Bild `konzept-of/mockups/OF-E2-2-karten-gescrollt.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup E) |
| E-OF-38 | Q-NV-01, Teil Suche (Mockup E, E3): Die Suche bekommt die gemeinsame Rahmenkarte wie die Startseite, Unterform **2 — Karten bleiben Karten**; die lange Filterleiste klebt und scrollt in sich. Damit ist Q-NV-01 ganz entschieden: Rahmenkarte auch für Startseite und Suche, dort mit Karten statt Abschnitten (P-NV-04 gilt für beide nicht). Bild `konzept-of/mockups/OF-E3-2-suche-karten.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup E) |

Aus Schritt 26 übernommen: E-MA-36 (Beenden-Knöpfe der App bleiben
vollflächig rot) ist bestätigt.

## 4. Offene Fragen

| Nr. | Frage | Hängt an |
|---|---|---|
| Q-OF-01 | Auswahl je Mockup (P-04, -09, -11, -17, -20, -22, -23, -29: je 3 Varianten; P-07, -19, -24 bis -27, -32, -34, -36: je ein Vorschlag) | Mockups |
| Q-OF-02 | P-23: Welche Zeilen der Statusseite entfallen? Claude legt je Zeile vor: Kann sie rot werden, muss dann jemand handeln, steht es woanders schon | P-23 |
| Q-OF-03 | P-10, -07, -19: Sollen die neuen Regeln von einem Prüfmittel gemessen werden (Erklärtext je Karte, Knopf zwischen Karten, Kartenbreite je Seitenart)? | P-07, -10, -19 |
| Q-OF-04 | P-14: Kachel „GPS-Daten verdichten" nur zeigen, wenn etwas fällig ist oder ein Lauf scheiterte — oder ganz weg bzw. als Zeile unter „Was dein Konto hält"? | P-14 |
| Q-OF-05 | P-18: Name des Kontos als Link, Spalte „Öffnen" entfällt (Tastatur und Screenreader brauchen ein fokussierbares Element) — bestätigt? | P-18 |
| Q-OF-06 | ~~P-27: Backup-Ziele nach Komplett-Backup (Vorschlag) oder nach Servereinstellungen?~~ entschieden (E-OF-24): beides | P-27 |
| Q-OF-07 | ~~P-37: Symbol für „Wiederherstellen": `restore` (Vorschlag), `arrow-back-up` oder `trash-off`~~ entschieden (E-OF-30) | P-37 |
| Q-OF-08 | Übersichtsseite „Einstellungen" doppelt zur Seitenleiste, „Abmelden" doppelt — eigener Punkt? | mit Q-NV-03 |
| Q-OF-09 | ~~Seitenreiter wie die Bereichszeile?~~ entschieden (E-OF-17) | P-20, NV-01 |
| Q-OF-10 | ~~Reihenfolge: NV jetzt bauen (echter Stand auf Staging, Fable plant gegen Code) oder NV in OF aufgehen lassen (einmal bauen, beide Pakete berühren `ui.php`/`style.css`)? Die Mockups sind schon im NV-Gerüst~~ entschieden (E-OF-35): NV geht in OF auf | NV, OF |
| Q-NV-01 (Stand) | ~~Gemeinsamer Kasten auch für Startseite und Suche: Tendenz **ja**, Mockups (Gruppe E) zur Entscheidung~~ entschieden (E-OF-36 bis -38): ja, mit Karten | NV |
| Q-NV-01 bis -04 | Offene Fragen aus Konzept NV (Rahmenkarte auch für Startseite und Suche; Ziel des Reiters „Betrieb"; Übersicht bleibt; Zähler in allen Bereichen) | NV |
