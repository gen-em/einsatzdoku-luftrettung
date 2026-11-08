# Konzept OF — Oberflächenrunde: Gerüst, Regeln, Bausteine und 37 Punkte (Schritt 27)

**Kürzel:** OF. Pakete `OF-NN` und — für das übernommene Gerüst — `NV-NN`,
Entscheidungen `E-OF-NN` und `E-NV-NN`, Befunde `F-OF-NN`, Fragen `Q-OF-NN`
und `Q-NV-NN`, Prüfpunkte `P-OF-NN` und `P-NV-NN` (`docs/Pruefablauf.md` 7).
Die Punkte der Betreiberin heißen `OF-P-01` bis `OF-P-37`; sie sind keine
Pakete. **Alle NV-Nummern behalten ihren Wert** (E-OF-35).

**Zweig:** Konzept auf `claude/oberflaechenrunde-of` (bis `843a72e`), Umsetzung auf `claude/of-oberflachenrunde-umsetzung-8riu6b` (F-OF-18) · **Herkunft:** Durchsicht von
Staging (Web 23.11.3) und Produktiv (Web 23.8.2) durch die Betreiberin am
07.11.2026, gesammelt in `Vorbereitung-OF-Oberflaechenrunde.md` (letzter
Stand `0464977`, hier aufgegangen und gelöscht, E-OF-47), dazu die Übergabe
`Konzept-NV-Navigation.md` aus der Branding-Sitzung (hier aufgegangen und
gelöscht, E-OF-35) · **Backlog-Spanne:** 530 bis 539 · **Mockups:**
`konzept-of/mockups/` — `NV-00` bis `NV-05` aus der Übergabe,
`OF-E…` und `OF-P…` aus den fünf Artifact-Mockups der Vorbereitung
(Dateiname = Punkt + gewählte Variante) · **Prüfdokument:** am Ende nach
`docs/konzepte/Pruefliste.md` (E-PU-09).

**Modell:** Dieses Konzept schreibt **Fable** (E-OF-03). Die Umsetzung läuft
in **allen Paketen mit Opus** (K2); kein Paket verlangt Fable.
**Fächerung:** je Paket eine Zeile im Statusblock. Grundsatz: Gerüst,
Regeln und Bausteine seriell, weil sie `server/ui.php` und
`server/assets/style.css` gemeinsam anfassen; nur die beiden Seitenpakete
OF-06 und OF-07 fächern den Umbau getrennter Seitendateien, Stylesheet und
Prüfarbeit bleiben auch dort seriell (CLAUDE.md 7).

**Keine Fassungsnummer in diesem Dokument** — die Umsetzung legt sie fest
(K3). NV-02 verändert den Weg durch die Einstellungen sichtbar; ob das die
Hauptnummer hebt, entscheidet die Umsetzung nach CLAUDE.md 2 und begründet
es im Kopf von `server/version.php`.

---

## Statusblock

| Paket | Inhalt | Modell · Fächerung | Stand |
|---|---|---|---|
| OF-00 | Konzept, Fahrplanzeile, Backlog-Spanne, Übernahme von NV und der Vorbereitung | Fable · keine | **erledigt** 07.11.2026 |
| NV-01 | Bereichszeile unter der Kopfleiste — als Reiter-Baustein für beide Ebenen (E-OF-17, E-OF-40); Seitenreiter in derselben Form (OF-P-20); Ziel „Betrieb“ ist der erste Punkt (E-OF-51) | Opus · keine | **erledigt** 08.11.2026 (Web 23.12.0) |
| NV-02 | Rahmenkarte für alle drei Leistentypen: Abschnitte in den Einstellungen, Karten auf Startseite und Suche (E-OF-36 bis -38); Leiste klebt und rollt innen (E-OF-37); Fußzeile als dunkelblauer Abschluss (E-OF-34) | Opus · keine | **erledigt** 08.11.2026 (Web 24.0.0) |
| NV-03 | Leiste der Einstellungen auf den aktiven Bereich kürzen; aktiver Eintrag ohne Strich in jeder Leiste (OF-P-04, E-OF-16); „Abmelden“ nur in der Leiste (E-OF-27); Sprungmarken nur zu sichtbaren Karten (OF-P-16) | Opus · keine | **erledigt** 08.11.2026 (Web 24.1.0) |
| NV-04 | Schmale Breiten: Bereichszeile rollt, Rahmenkarte ohne Rand, Schublade mit drei Bereichen; die Übersicht bleibt (E-OF-50) | Opus · keine | **erledigt** 08.11.2026 (ohne Codeänderung, E-OF-63) |
| OF-01 | Regeln für alle Seiten: eine rechte Kante, Felder 36 rem (E-OF-31); Speicherknopf im Abschnitt oder Speicherleiste (E-OF-32, E-OF-41); Handbuch-Verweise raus, das „?“ öffnet das Kapitel der Seite (E-OF-02, -56); ein Satz je Karte (OF-P-10, -15); drei Messungen im Bilderlauf (E-OF-49, Nr. 530) | Opus · keine | **erledigt** 08.11.2026 (Web 24.2.0) |
| OF-02 | Bausteine I: Klappkarte mit Stand und Plakette (E-OF-42); Segmentwahl abwählbar und senkrecht mittig (OF-P-05, E-OF-33); Zeile mit Plakette und Knopf auf einer Linie (OF-P-08); sechs neue Zeichen | Opus · keine | **erledigt** 08.11.2026 (Web 24.3.0) |
| OF-03 | Bausteine II, Eingabe: Datumsfeld `TT.MM.JJJJ` an allen acht Stellen (E-OF-06, -44, -54); Ortsfeld-Hinweis als Zeichen (E-OF-07); Listenkopf mit Filterknopf (E-OF-18) | Opus · keine | **erledigt** 08.11.2026 (Web 24.4.0) |
| OF-04 | Einstellungen: Profil mit Zweitfaktor „ein Satz plus Verwalten“ (E-OF-19); Geräte nach Typ, Koppeln im Dialog (E-OF-25); Import / Export „erst wählen, dann füllen“ (E-OF-26); Backup (E-OF-52, OF-P-15) | Opus · keine | **erledigt** 08.11.2026 (Web 24.5.0) |
| OF-05 | Verwaltung: Kontenliste ohne Spalte „Öffnen“ (E-OF-53); Demo-Konto als Karte unter Installation, ein Schalter statt zwei (E-OF-08, E-OF-46) | Opus · keine | **erledigt** 08.11.2026 (Web 24.6.0; Kontrollzahlen und Umleitung von `admin_demo.php` mit OF-06, E-OF-71) |
| OF-06 | Betrieb: Status „Probleme zuerst“ (E-OF-20; Q-OF-02 → OF-M1); Hintergrundjobs (E-OF-10, -21); Servereinstellungen als Klappkarten (E-OF-22); Komplett-Backup (E-OF-23); Backup-Ziele an beiden Orten, die Seite entfällt (E-OF-24, E-OF-45) | Opus · **gefächert** nach OF-M1: je ein Agent für Status, Hintergrundjobs, Servereinstellungen und Komplett-Backup samt Ziele; `ui.php`, `style.css`, `status_lib.php`, Texte und Prüfarbeit seriell | offen |
| OF-07 | Einsatz, Diensttag, Karte, Papierkorb: Ruhesegmente-Kachel entfällt (E-OF-05); GPX-Zeichen (E-OF-11); Plakette „ausgedünnt“ entfällt (E-OF-12); Karte „Einsatz und Transport“ im Feldkatalog (E-OF-33); Schlösser in eigener Spalte (E-OF-28); Kartenart ohne Anbieter (E-OF-13); Leaflet-Bedienelemente im Branding (E-OF-29); Papierkorb mit `restore`, Artzeichen vor das Datum (E-OF-30, E-OF-48) | Opus · **gefächert**: ein Agent Formular und Leseansicht (`einsatz_form.php`, `mission_fields.php`, `einsatz.php`), einer Karte (`geo.js`, `map_layers.js`, Leaflet-Regeln), einer Tagesübersicht und Papierkorb (`index.php`, `tag_spuren.php`, `papierkorb.php`); `style.css`, Design.md und Prüfarbeit seriell | offen |
| OF-08 | Darstellung: Umschalter Mond/Sonne in der Kopfleiste, vier Einstellungen im Profil, keine Migration (E-OF-09, -43, -55) | Opus · keine | offen |
| OF-09 | Abschluss (NV-05 geht auf): Dokumentation, Prüfmittel, Prüfstand voll, Pull Request, Prüfdokument | Opus · keine | offen |

**In Arbeit:** OF-06 Betrieb (OF-M1 entschieden 08.11.2026: E-OF-72,
-73). **Erledigt:** OF-00, NV-01 bis NV-04 (das
Gerüst), OF-01 bis OF-05. **Je Paket mitzunehmen:** seine Zeilen unter
`seitenregeln` in `tools/screenshots/ausnahmen.json` (E-OF-65) — OF-06 Jobs, Sicherheit, Servereinstellungen; OF-07
Tagesübersicht, Papierkorb, Diensttag- und Einsatzseiten.
**Prüfweise seit NV-02:** E-OF-60. **Hakt:**
nichts; F-OF-19 (eine Rolle mit einem Bereich sieht einen einzelnen
Reiter) ist der Betreiberin zur Kenntnis vorgelegt. **Offen bei der Betreiberin:** nichts — alle Fragen sind entschieden (Abschnitt 7); Q-OF-02 wird in OF-M1 vorgelegt.
**Meilenstein:** OF-M1 (Statuszeilen, zu Beginn von OF-06).

---

## 1. Anlass und Ziel

Nach dem Markenabgleich (Schritt 26) stimmen Farben, Schriften und Zeichen,
und auf dem breiten Bildschirm fällt auf, was davor die Farben verdeckt
haben: Die Seitenleiste schwimmt neben Karten mit Rand (NV-00), Speicherknöpfe
stehen zwischen Karten und gehören optisch zur falschen, Erklärtexte stehen
im Weg, drei Seiten des Betriebs tragen je neun Formularkarten untereinander,
und ein Dutzend Kleinigkeiten (Datumsfelder je nach Browser, ein Pfeil, der
„zurück“ heißt und „wiederherstellen“ meint, Plaketten, die umbrechen) summiert
sich zu einer Oberfläche, die man bedienen kann, aber nicht gern. Die
Betreiberin hat am 07.11.2026 37 Punkte gesammelt; zu 27 davon gibt es
freigegebene Mockups im Live-Umfeld (echtes Stylesheet, Demo-Daten), die
übrigen sind Korrekturen oder Regeln.

**Ziel:** Ein Gerüst, in dem Leiste und Inhalt sichtbar zusammengehören
(Bereichszeile und Rahmenkarte, Variante D aus NV, E-NV-01) — und auf diesem
Gerüst die Punkte der Betreiberin, **als Regeln und Bausteine, nicht als
Korrektur je Seite** (Vorbereitung 1): Speicherknopf, Seitenbreite,
Erklärtext und Handbuch-Verweis gelten überall; Klappkarte, Filterknopf,
Koppel-Dialog, Datumsfeld und die abwählbare Segmentwahl entstehen einmal
und werden von den Seiten benutzt.

**Was NV war, ist Teil von OF** (E-OF-35): Die Mockups der Punkte stehen
alle im NV-Gerüst, und beide Vorhaben fassen `ui.php` und `style.css` an —
zweimal gebaut wären zwei Umbauten derselben Regeln. Deshalb zuerst das
Gerüst (NV-01 bis NV-04), dann die Regeln, dann die Bausteine, dann die
Seiten; NV-05 geht in OF-09 auf.

**Was dieses Konzept nicht tut:** keine neuen Farben, Schriften oder Token
außer dem einen Maß `--bereiche` (48 px, NV-01); keine Änderung an
Verschlüsselung, Datenmodell oder Schnittstellen — die einzige Änderung an
einer Spalte ist ein vierter Wert in `users.darstellung`, der ohne Migration
passt (E-OF-43). Die Android-Apps und die Uhr bleiben unberührt.

## 2. Befund

Gelesen am 07.11.2026 auf `claude/oberflaechenrunde-of` `0464977`; der Zweig
enthält `origin/main` `ac4a7bd` (Merge von PR #157).

- **F-OF-01 — PR #157 ist gemergt.** Die Vorbereitung sagte „aufgesetzt auf
  den Abschluss von Schritt 26, PR #157“ und der Auftrag „noch offen“; der
  Merge ist gelaufen (`ac4a7bd`, 07.11.2026). Der Zweig baut auf dem
  gemergten Stand, nichts ist nachzuziehen.
- **F-OF-02 — Die Speicherleiste gibt es schon.** `ui_speichern_leiste()`
  (Design.md 9.9) erscheint mit der ersten Änderung und klebt unten, in der
  Form der Karte. Was E-OF-32 verlangt, ist nicht ein zweiter Baustein,
  sondern ein Umbau des ersten: Sitz am unteren Rand der Rahmenkarte statt
  des Inhalts, Fläche `--auswahl-flaeche` statt Karte mit Linie und Schatten,
  links fett „Geändert: …“ mit den Namen der geänderten Abschnitte. Die
  zweite Verwendung als Sammelleiste bleibt (E-OF-41).
- **F-OF-03 — Die Klappkarte gibt es schon, aber ohne Plakette.**
  `ui_karte_start(['zu' => true, 'vorschau' => …])` kehrt im `<details>`-Zweig
  zurück, bevor Plakette und Aktion ausgegeben werden; Design.md 9.1 nennt das
  ausdrücklich als Sperre. Die Mockups zu OF-P-24 bis -27 zeigen zugeklappt
  Winkel, Titel, eine Standzeile **und** die Plakette. Die Sperre fällt
  (E-OF-42).
- **F-OF-04 — 62 Handbuch-Verweise in 25 Dateien.** `hilfe.php#…` steht in
  25 PHP-Dateien unter `server/`, dazu 49 wörtliche „Handbuch:“. Design.md 6
  („Erklärtext: ein Satz je Karte“) schreibt den Verweis **vor**, und der
  Riegel `anker` hält jedes Ziel. E-OF-02 dreht die Regel: ein Satz **ohne**
  Verweis. Der Riegel bleibt — er misst dann, was übrig ist — und das „?“ der
  Kopfleiste führt heute auf das ganze Handbuch, nicht auf das Kapitel der
  Seite (Q-OF-14).
- **F-OF-05 — Datumsfelder in drei Formen an 17 Stellen in 8 Dateien:**
  `<input type="date">` von Hand (`suche.php`, `einsatz_form.php`
  Geburtsdatum), `ui_feld(['art' => 'date'])` (`diensttag_neu.php`,
  `import.php`, `diensttag_datum.php`, `admin_rechtstexte.php`,
  `betrieb_server.php`) und die Zeitraumwahl in `ui.php`. Das Geburtsdatum
  wird im Browser verschlüsselt — dort muss die Umrechnung im Skript liegen,
  nicht im PHP (E-OF-44, Q-OF-12).
- **F-OF-06 — Den Demo-Schalter gibt es schon.** Betrieb → Servereinstellungen,
  Karte „Konten“, „Demo-Anmeldung zulassen“ (`KONTEN_K_DEMO_ANMELDUNG`,
  `betrieb_server.php`); dort hängt auch die Rückfrage, die beim Wechsel auf
  „nur auf Einladung“ die Demo-Anmeldung abschaltet. Die Frage aus E-OF-08
  ist damit beantwortet: Er wandert in die neue Karte unter Installation,
  die Konten-Karte verliert ihn — ein Schalter, eine Stelle (E-OF-46).
- **F-OF-07 — Zwei Menüpunkte entfallen, die Zähler folgen.** „Demo-Konto“
  (`admin_demo.php`, Verwaltung) geht in der Karte unter Installation auf,
  seine Kontrollzahlen (Diensttage, Einsätze, Ruhesegmente, Papierkorb) in
  der Statusseite; „Backup-Ziele“ (`admin_sicherungsziele.php`, Betrieb) wird
  die Klappkarte an zwei Orten (E-OF-24). Verwaltung zählt dann 5 statt 6,
  Betrieb 6 statt 7; die Bereichszeile zählt `count(punkte)` und folgt von
  selbst.
- **F-OF-08 — Die Kachel „Ruhesegmente“** steht in `index.php` als eigene
  Karte mit der Wahl „die ganze Aufzeichnung; Einsätze danach
  herausschneiden“. Das Aktionsmenü des Diensttags (`ui_aktionen()`) ist der
  Ort für „Einsatz aus GPS-Daten ausschneiden“ (E-OF-05); der Schneide-Bereich
  (Design.md 9.17) bleibt die Seite dahinter.
- **F-OF-09 — „Abmelden“ steht zweimal:** in `ui_leiste_einstellungen()`
  (Leistenfuß, mit Rückfrage) und als leiser Knopf am Ende von
  `ui_einstellungen_uebersicht()`. Der zweite entfällt (E-OF-27).
- **F-OF-10 — Die Kontenliste trägt eine Spalte `oeffnen-spalte`** mit
  verstecktem Kopf und je Zeile dem Verweis „Öffnen“ (`admin_users.php`);
  Backlog Nr. 342 misst, dass genau diese Spalte bei 1024 px angeschnitten
  ist. OF-P-18 löst den Kontenlisten-Teil von Nr. 342; Suche und Zeitraum
  bleiben bei Schritt 13 (die Kopfzeile von Nr. 342 wird in OF-05
  nachgezogen).
- **F-OF-11 — Die Sprungmarke ohne Karte (OF-P-16) hat eine Ursache:**
  `menue.js` sammelt `.karte[id]` im Inhalt, ohne zu fragen, ob die Karte
  sichtbar ist. „Für dich freigegebenes Backup“ liegt in `#freigabebox hidden`
  und erscheint nur, wenn eine Freigabe vorliegt — die Marke steht immer.
  Weg: verborgene Karten (`hidden`, `offsetParent === null`) überspringen und
  die Marken neu bauen, wenn die Seite eine Karte einblendet (NV-03).
- **F-OF-12 — Die Statusseite** (`betrieb_status.php`, `status_lib.php`
  mit `status_erhebung()` und `plattform_pruefen()`) zeigt nach dem Mockup 68
  Zeilen in vier Karten. Welche davon entfallen (Q-OF-02), lässt sich nicht
  aus dem Konzept heraus entscheiden, sondern nur Zeile für Zeile am Code:
  Kann sie rot werden, muss dann jemand handeln, steht es woanders schon.
  Das ist die erste Aufgabe von OF-06 und der Meilenstein OF-M1.
- **F-OF-13 — `users.darstellung` ist `VARCHAR(10)`** mit den Werten `''`
  (System), `'hell'`, `'dunkel'` (`DARSTELLUNGEN` in `session_lib.php`). Ein
  vierter Wert passt ohne Migration (E-OF-43).
- **F-OF-14 — Kein Prüfmittel misst die drei Seitenregeln.** Die
  Erklärtext-Zählung aus Design.md 6 (E-P5c-128) ist eine Handregel; der
  Bilderlauf (`tools/screenshots/aufnehmen.mjs`) misst Knopfhöhen, Überlauf
  und „Karten außerhalb `main.inhalt`“ an der gerenderten Seite — er ist die
  Stelle, an der drei weitere Messungen hingehören (E-OF-49). Ein neues
  Werkzeug braucht es nicht.
- **F-OF-15 — Der Reiter-Baustein (`ui_reiter()`, Design.md 9.37) und die
  Bereichszeile aus NV-01 wären zwei Reihen in zwei Formen.** E-OF-17
  entscheidet eine Form für beide Ebenen; NV-01 baut die Bereichszeile
  deshalb **auf `ui_reiter()`** statt auf einem eigenen `.bereiche`-Markup
  (E-OF-40). Das verschiebt den NV-Plan an einer Stelle und spart einen
  Baustein.
- **F-OF-16 — Der gestrichelte Kreis im Papierkorb** ist `ohne-zuordnung.svg`
  (Tabler „circle-dashed“), das Artzeichen „ohne Rettungsmittel“ aus
  `ui_artzeichen()`, neben die Knöpfe gesetzt statt vor das Datum. Im Mockup  zu OF-P-37 steht das Artzeichen vor dem Datum wie in der Leiste (E-OF-48).
- **F-OF-17 — Den Verdichtungsstand sieht nur die NutzerIn.** Der
  Wartungslauf (`wartungslauf.js`, Karte `k-verdichten`) arbeitet von selbst:
  still auf der Startseite (20 Stücke je Aufruf) und auf der Backup-Seite,
  solange der Reiter offen ist — ohne Knopf. Die Statusseite kennt ihn
  nicht; `status_lib.php` nennt „Verdichten“ nur als Jobart. Der Server
  könnte die ungepackten Stücke zählen (`spur2_faellig()` liest nur den
  Klartext-Umriss), tut es aber für den Betrieb heute nicht. Daraus E-OF-52
  und eine Option für OF-M1.
- **F-OF-18 — Der Konzeptzweig enthielt `main` nur dem Inhalt nach.**
  `843a72e` hat beide Eltern von `ac4a7bd` als Vorfahren, den Merge-Commit
  selbst aber nicht; „enthält `origin/main` `ac4a7bd`“ (F-OF-01, OF-00)
  stimmte für den Baum, nicht für den Graphen. Für „Require branches to be
  up to date“ zählt der Graph. Die Umsetzung arbeitet auf dem Sitzungszweig
  `claude/of-oberflachenrunde-umsetzung-8riu6b`, gesetzt auf `843a72e` mit
  `ac4a7bd` hineingemergt (`38a4393`, Baum unverändert; Betreiberin,
  08.11.2026). `claude/oberflaechenrunde-of` bleibt auf `843a72e` stehen.
- **F-OF-19 — Eine Rolle mit einem Bereich sieht einen einzelnen Reiter.**
  Die NutzerIn (und das Demo-Konto) sieht nur „Einstellungen 5“ in der
  Bereichszeile; das Konzept sagt nur „Rollen, die einen Bereich nicht
  sehen, sehen seinen Reiter nicht“. Umgesetzt wie geschrieben — die Zeile
  steht auch mit einem Reiter, damit das Gerüst für alle Rollen dasselbe
  ist. Soll sie bei einem Bereich entfallen, ist das eine Bedingung in
  `ui_geruest_start()`.

- **F-OF-21 — Der Versatz der Segmentwahl (OF-P-05) ist im Bestand nicht
  nachzustellen.** Gemessen am 08.11.2026 an allen sichtbaren Segmenttasten
  von Suche (28), Installation (3), Hintergrundjobs (4),
  Servereinstellungen (4) und Import / Export (2) in Chromium, Firefox und
  WebKit: Mitte des Textes gegen Mitte der Taste, **0 Abweichungen ab
  1 px**. `.segment-taste` trägt `align-items: center` seit jeher. Gemeint
  war vermutlich die Transportart im Mockup `OF-P32`, die es als
  Segmentwahl erst mit OF-07 gibt — dort wird nachgemessen.
- **F-OF-22 — Die Plakette neben einem Knopf sass zu tief, und zwar
  wegen einer fremden Regel.** `.karte-inhalt .zeile-plaketten { margin-top }`
  war für die Plaketten am Fuß der Einsatzkarte gedacht und traf jede Zeile
  in einer Karte: auf den Konto-Backups 6 px unter der Mitte des Knopfs, im
  Schneide-Bereich ebenso. Das ist OF-P-08; behoben mit `>` (nur das direkte
  Kind) und `align-items: center` an beiden Gruppen.
- **F-OF-20 — Die Höhenschwelle der Sprungmarken ist vielleicht
  überholt.** Unter 950 px Fensterhöhe fallen die Sprungmarken in der
  festen Leiste weg (Design.md 9.25), weil sie bei 18 Einträgen die
  Einträge aus dem Bild schoben. Seit NV-03 zeigt die Leiste höchstens
  sieben Einträge (Betrieb). Die Schwelle bleibt in OF stehen — sie zu
  heben, wäre eine eigene Messung und ist nicht beauftragt.

## 3. Quellen

| Quelle | Wo | Was |
|---|---|---|
| Mockups NV | `konzept-of/mockups/NV-00-ist-staging.png` … `NV-05-mobil-390.png` | Ausgangslage; Variante D auf 1920 (mit und ohne Maße), Betrieb → Status kurz, 1366 px, 390 px |
| Mockups OF | `konzept-of/mockups/OF-E1-2-karten-1920.png`, `OF-E1-2-karten-390.png`, `OF-E2-2-karten-gescrollt.png`, `OF-E3-2-suche-karten.png`, `OF-P04-A-ohne-markierung.png`, `OF-P07-speicherleiste.png`, `OF-P09-B-satz-verwalten.png`, `OF-P11-B2-dialog.png`, `OF-P17-C-wahlkarten.png`, `OF-P19-vorschlag.png`, `OF-P20-A-gleiche-form.png`, `OF-P22-B-filterknopf.png`, `OF-P23-A-probleme-zuerst.png`, `OF-P24-vorschlag.png`, `OF-P25-vorschlag.png`, `OF-P26-vorschlag.png`, `OF-P27-servereinstellungen.png`, `OF-P27-komplett-backup.png`, `OF-P29-C-dunkelblau.png`, `OF-P32-vorschlag-leer.png`, `OF-P32-vorschlag-gefuellt.png`, `OF-P34-links.png`, `OF-P36-vorschlag.png`, `OF-P36-vorschlag-ebenen.png`, `OF-P37-A-restore.png` | je Punkt die gewählte Variante, im Live-Umfeld (echtes Stylesheet, Demo-Daten) |
| Artifacts | A Navigation, B Einstellungen, C Betrieb, D Einsatz und Karte, E Startseite und Suche (private Seiten auf claude.ai, Konto der Betreiberin, 07.11.2026) | alle Varianten je Punkt mit Kommentaren; die Bilder oben sind daraus |
| Canvas | „GenEM Vorschläge“, Board „Navigation auf breitem Bildschirm“ (Claude Design) | die vier Layouts A bis D mit Begründung |
| Branding-Repo | `gen-em/gen-em-branding`, Stand `e7bf4fc` | Token, Regeln; Aktionsfarbe Variante 3 ist mit MA-12 umgesetzt |
| Bestand | `server/ui.php` (`ui_geruest_start()`, `ui_kopf()`, `ui_einstellungen_punkte()`, `ui_leiste_einstellungen()`, `ui_einstellungen_uebersicht()`, `ui_karte_start()`, `ui_reiter()`, `ui_listenkopf()`, `ui_segment()`, `ui_speichern_leiste()`, `ui_feld()`, `ui_ortsfeld()`, `ui_fuss_seite()`), `server/assets/style.css` (`.rahmen`, `.leiste`, `.inhalt`, `.karte`, `.reiter`, `.speichern`, `.fuss-seite`, Blöcke ab 1024 und 1200 px), `server/assets/menue.js`, `zeitfeld.js`, `forms.js`, `listenkopf.js`, `map_layers.js`, `geo.js` | was umgebaut wird |

## 4. Was sich ändert — Übersicht

| Nr. | Ist | Soll | Paket |
|---|---|---|---|
| 1 | Drei Bereiche als Akkordeon in der Leiste; Leiste als eigene Fläche am Containerrand | Bereichszeile unter der Kopfleiste; Leiste und Inhalt in einer Rahmenkarte; Leiste zeigt nur den aktiven Bereich | NV-01 bis -03 |
| 2 | Karten mit Rand im Inhalt der Einstellungen | Abschnitte mit Trennlinie in der Rahmenkarte; auf Startseite und Suche bleiben Karten Karten | NV-02 |
| 3 | Fußzeile hell am Ende des Inhalts; „Abmelden“ zweimal | dunkelblauer Streifen unter der Rahmenkarte; „Abmelden“ nur in der Leiste (Handy: Schublade) | NV-02, NV-03 |
| 4 | Aktiver Eintrag mit Strich links und Fläche | nur die Fläche, in jeder Leiste | NV-03 |
| 5 | Seitenreiter leise (gedämpft, Unterstrich 2 px), Bereichszeile anders | eine Form: Open Sans 700, 48 px, Unterstrich 3 px Dunkelblau | NV-01 |
| 6 | Abschnitte je Seite verschieden breit (Lesespalte, volle Breite) | eine rechte Kante; begrenzt sind die Felder (36 rem) | OF-01 |
| 7 | Speicherknopf zwischen zwei Karten | im Abschnitt, den er speichert — oder Speicherleiste am Rand der Rahmenkarte | OF-01 |
| 8 | Ein Satz je Karte plus Verweis „Handbuch: …“; Einstellungen ausgenommen | ein Satz je Karte, ohne Verweis, überall; das „?“ führt ins Handbuch | OF-01 |
| 9 | Klappkarte ohne Plakette | Klappkarte mit Standzeile und Plakette im Kopf | OF-02 |
| 10 | Segmentwahl: eines muss gewählt sein; Beschriftung nicht mittig | zweiter Klick nimmt die Wahl zurück; mittig | OF-02 |
| 11 | `<input type="date">` nach Browser („dd/mm/yyyy“), schmal | `TT.MM.JJJJ` mit Maske wie die Zeitfelder, ohne Kalender | OF-03 |
| 12 | Satz zur Adresssuche unter jedem Ortsfeld | ein Zeichen; der Satz beim Antippen, per Tastatur und Screenreader erreichbar | OF-03 |
| 13 | Listenkopf: Suche, Zeitpillen, Auswahlfeld in zwei Zeilen | Suche plus Knopf „Filter“ mit Zahl; gesetzte Filter als entfernbare Pillen | OF-03 |
| 14 | Zweitfaktor: sechs Zeilen offen; Geräte: Anleitung über allem; Import / Export: zwei Formulare untereinander | ein Satz plus „Verwalten“; Geräte nach Typ mit Koppel-Dialog; erst wählen, dann füllen | OF-04 |
| 15 | Kontenliste mit Spalte „Öffnen“; Demo-Konto als Seite mit Kennzahlen | Name ist der Verweis; Demo-Konto als Karte unter Installation, Zahlen auf Status | OF-05 |
| 16 | Status: vier Karten, 68 Zeilen; Jobs: „Anhalten“ versteckt, Plaketten brechen um; Servereinstellungen und Komplett-Backup: neun Formularkarten; Backup-Ziele als Seite | Probleme zuerst, Bereiche zugeklappt; „Jobs vorübergehend pausieren“ oben; Klappkarten mit Stand; Ziele an beiden Orten | OF-06 |
| 17 | Kachel „Ruhesegmente“; Pfeil-links für „Wiederherstellen“; Schloss hinter dem Wort; Leaflet-Knöpfe; Anbieternamen in der Ebenenwahl; „Einsatz“ und „Transport“ als zwei Karten | Weg im Aktionsmenü; `restore`; Schloss in eigener Spalte; Knöpfe in App-Form; Namen ohne Anbieter; eine Karte „Einsatz und Transport“ | OF-07 |
| 18 | Darstellung: System · Hell · Dunkel im Profil | Umschalter in der Kopfleiste als Vorgabe; dazu Immer hell, Immer dunkel, Folgt System | OF-08 |

## 5. Arbeitspakete

Die Reihenfolge ist Abhängigkeit, nicht Gewicht: Jedes Mockup steht im
Gerüst (NV), jede Seite benutzt die Regeln (OF-01) und Bausteine (OF-02,
-03). Ein Paket, das vor seiner Grundlage gebaut wird, wird zweimal gebaut.

### NV-01 Bereichszeile

- **Ein Baustein für beide Ebenen** (E-OF-17, E-OF-40). `ui_reiter()`
  bekommt die Form der Bereichszeile: Open Sans 700, Zeile **48 px**
  (`--bereiche`, neues Maß neben `--kopf`; E-NV-04 gilt damit für Seitenreiter
  mit — eine begründete Ausnahme zur 36-px-Regel, nur die Zeilenhöhe), inaktiv
  `--gedaempft`, aktiv `--text-titel` mit Unterstrich `3px` in
  `--auswahl-linie` (E-NV-05), Hover Fläche `--flaeche-abgesetzt`, Fokusring
  wie überall. Der Zähler der Punkte steht als `.bereich-zahl`
  (`--gedaempft`, 400) hinter dem Namen, der Aufmerksamkeits-Zähler als
  vorhandene Plakette `ui_zaehler()`, **summiert über den Block** (E-OF-14).
- **`ui_bereichszeile(string $aktiv)`** bleibt als Funktion: Sie baut aus
  `ui_einstellungen_punkte()` die Punkte (Ziel = erster Punkt des Blocks, E-OF-51) und ruft `ui_reiter(['klasse' => 'reiter-bereiche', …])`. Die
  Variante `.reiter-bereiche` setzt nur Lage und Breite: volle Fensterbreite,
  Fläche Schnee, Linie unten, Inhalt auf `--rahmen` zentriert mit dem
  Seitenabstand der Kopfleiste. Aufruf in `ui_geruest_start()` nach
  `ui_kopf()`, nur bei `leiste === 'einstellungen'`. Rollen, die einen Bereich
  nicht sehen, sehen seinen Reiter nicht.
- **Seitenreiter** (Protokoll, Statistik, Rechtstexte) ändern kein Markup;
  sie erben die Form. `reiter.js` (Rollen, Verlauf am Rand) bleibt.
- Welcher Block aktiv ist, folgt aus `$o['menue']` wie heute `$hatAkt`; für
  die Übersicht ist „Einstellungen“ aktiv.
- **Abnahme:** P-NV-01, P-NV-06, P-NV-07, P-NV-08, P-OF-01.

### NV-02 Rahmenkarte

- `ui_geruest_start()` öffnet nach `.rahmen` ein Element `.rahmenkarte`
  (Flex, 1 px `--linie`, `--radius-gross`, Fläche Schnee, **`overflow:
  clip`** — nicht `hidden`, weil `hidden` am Elternteil `position: sticky`
  bricht; F-NV-02 ist damit vorweggenommen), `ui_geruest_ende()` schließt
  es — **für alle drei Leistentypen** (E-OF-36, -38): Einstellungen,
  Diensttage (Startseite, Tagesübersicht, Einsatzansicht, Formular,
  Papierkorb, Zeitraum) und Filter (Suche). `.rahmen` bekommt
  `padding: var(--abstand-4) var(--abstand-3)`; ohne Bereichszeile klebt die
  Leiste unter `--kopf` allein.
- **Leistenspalte:** ein Hüllelement `.leiste-spalte` um `aside.leiste`
  (`flex: 0 0 var(--leiste)`, Fläche `--flaeche-karte`, Linie rechts) trägt
  den Hintergrund bis zum Kartenboden; `.leiste` selbst ist `sticky` unter
  Kopf und Bereichszeile, `height: auto`, `max-height: calc(100vh - top)`,
  `align-self: flex-start`, rollt in sich (`.leiste-liste` hat
  `overflow-y: auto` schon). Auf der Startseite bleiben „Diensttag anlegen“
  und „Papierkorb“ im Leistenfuß sichtbar (E-OF-37, Bild `OF-E2…`); die
  Filterleiste der Suche (280 px ab 1200) verhält sich gleich (E-OF-38).
- **Zwei Darstellungen des Inhalts, eine Klasse.** Mit `leiste ===
  'einstellungen'` trägt die Rahmenkarte `.rahmenkarte-abschnitte`:
  `.rahmenkarte-abschnitte .karte { border: 0; border-radius: 0; background:
  transparent; border-top: 1px solid var(--linie); margin: 0; padding-top:
  var(--abstand-4) }`, die erste Karte unter dem Titel ohne Linie — **kein
  PHP in `ui_karte_start()`** (E-NV-03). Ohne die Klasse (Startseite, Suche)
  bleiben Karten Karten mit Rand, Radius und Kartenfläche auf der
  Rahmenkarte (Unterform 2, E-OF-36). Meldungen (`.meldung`) sind keine
  `.karte` und bleiben farbige Kästen in beiden Fällen.
- **Fußzeile** (E-OF-34): `ui_fuss_seite()` wird ein Streifen in
  `--dunkelblau` über die ganze Fensterbreite unter der Rahmenkarte, Schrift
  `--auf-dunkel`, Verweise `--auf-dunkel` fett, Inhalt auf `--rahmen`
  zentriert; Höhe aus dem Inhalt (zwei Zeilen wie heute). Im Dunkelmodus
  dieselbe Fläche wie die Kopfleiste (MA-07). Druck: ausgeblendet wie heute.
- **Lesespalte** (`rahmen-lesespalte`): bezieht sich künftig auf `.inhalt`
  in der Karte; die Karte bleibt 1680 breit. Seiten ohne Leiste (Rechtstexte,
  Anmeldung, Wiederherstellung) tragen **keine** Rahmenkarte — ihr Gerüst
  ist die Lesespalte, und ein Rahmen um eine einzelne Karte wäre eine Karte in
  der Karte.
- **Abnahme:** P-NV-02, P-NV-03, P-NV-04 (nachgezogen), P-NV-09, P-NV-10,
  P-OF-02, P-OF-03.

### NV-03 Leiste der Einstellungen kürzen

- `ui_leiste_einstellungen()`: ein Markup, zwei Darstellungen. Der Server
  rendert alle Blöcke; ab 1024 px zeigt CSS nur den aktiven
  (`.leiste-gruppe:not(.aktiv) { display: none }` im 1024er-Block, der
  aktive ohne `summary`-Zeile und ohne Einzug), unter 1024 px alle als
  Akkordeon wie heute. `menue.js` (Klappzustände) bleibt unverändert und
  wirkt nur noch in der Schublade.
- **Aktiver Eintrag ohne Strich** (E-OF-16): Die Regel `.eintrag.aktiv` (und
  ihre Geschwister in Diensttage-Leiste und Vorschlagsliste) verliert den
  linken Randstrich; die Fläche `--auswahl-flaeche` mit `--auswahl-text` fett
  bleibt. Gilt in jeder Breite und in der Schublade. Design.md 9.25, 9.28 und
  die Diensttage-Leiste ziehen nach.
- **„Abmelden“ nur im Leistenfuß** (E-OF-27): Der Knopf am Ende der
  Übersicht entfällt; `.leiste-fuss` bekommt eine Linie oben statt des
  Fensterbodens. In der Schublade steht es wie heute unten.
- **Sprungmarken nur zu sichtbaren Karten** (OF-P-16, F-OF-11): `menue.js`
  überspringt Karten, die verborgen sind, und baut die Marken neu, wenn eine
  Seite eine Karte einblendet (`#freigabebox`); die Backup-Seite löst das
  nach dem Einblenden aus. Alle Seiten mit bedingten Karten werden
  durchgesehen (Backup, Zweitfaktor, Geräte).
- **Abnahme:** P-NV-05, P-OF-04, P-OF-05.

### NV-04 Schmale Breiten

- Unter 1024 px: `.rahmenkarte { border: 0; border-radius: 0; background:
  transparent }`, `.rahmen { padding: var(--abstand-3) }`; die Bereichszeile
  rollt waagerecht (`overflow-x: auto`, Rollbalken verborgen — das kann
  `.reiter-rahmen` mit `reiter.js` schon); die Schublade bleibt mit allen
  drei Bereichen; der Rückweg „‹ Einstellungen“ bleibt.
- **Die Übersicht (`einstellungen.php` ohne `t`) bleibt** (E-OF-50): Einstieg vom Zahnrad und auf dem Handy der einzige
  Überblick. Am Desktop zeigt sie die Bereichszeile mit „Einstellungen“
  aktiv und die drei Bereichskarten ohne „Abmelden“ (E-OF-27).
- **Abnahme:** P-NV-05, P-NV-06, P-NV-08, P-OF-06.

### OF-01 Regeln für alle Seiten

Drei Regeln, einmal im Stylesheet, einmal in Design.md 6 — und dreimal im
Bilderlauf gemessen (E-OF-49, Anlass Nr. 530): (1) kein `.knopf` als
direktes Kind von `.inhalt` zwischen zwei `.karte`; (2) in
`.rahmenkarte-abschnitte` endet jede `.karte` an der rechten Kante von
`.inhalt`; (3) je `.karte` höchstens ein Satz in `.feld-hinweis` und
`.seiten-erklaerung` außerhalb von Meldungen, Leerzuständen und
Befundzeilen (Zählung nach Design.md 6, E-P5c-128). Jede Abweichung ist ein
Befund wie ein Knopf falscher Höhe; die Sollzahl steht in der `LIESMICH.md`
des Bilderlaufs.

- **Eine rechte Kante** (E-OF-31, Bild `OF-P19-vorschlag.png`): Jeder
  Abschnitt und seine Linie reichen bis zur rechten Kante des Inhalts;
  begrenzt werden **Felder**, nicht Abschnitte: `.rahmenkarte-abschnitte
  .feld-eingabe, .wahlliste, .segment { max-width: 36rem }` (das Maß der
  Listensuche, `--listensuche-breit`, bekommt dafür einen allgemeinen Namen
  `--feld-breit`). `rahmen-lesespalte` fällt für Einstellungsseiten weg
  (Servereinstellungen, Installation); sie bleibt den Seiten ohne Leiste.
  Listenseiten (Konten, Protokoll, Suche) bleiben, wie sie sind.
- **Speicherknopf** (E-OF-32, E-OF-41, Bild `OF-P07-speicherleiste.png`):
  Speichert ein Knopf **einen** Abschnitt, steht er als letztes Element in
  ihm, in `.listen-form-fuss`, vor dem Abstand zur nächsten Linie. Speichert
  er **mehrere**, erscheint die Speicherleiste — `ui_speichern_leiste()`
  umgebaut: `position: sticky; bottom: 0` **am unteren Rand der
  Rahmenkarte** (sie wird letztes Kind von `.inhalt`), Fläche
  `--auswahl-flaeche` (schaltet im Dunkelmodus mit), ohne Linie, Bogen und
  Schatten, links fett „Geändert: Logo, Darstellung“ (die Titel der
  Abschnitte, in denen `forms.js` eine Änderung sieht — `data-dirty-track`
  liefert das Formular, die Karte mit `id` den Namen), rechts der Knopf. Die
  Sammelleiste (zweite Verwendung) bekommt dieselbe Form. Betroffen: Profil
  (ein Formular über Angaben, Logo, Darstellung, Datenschutz), Installation,
  Servereinstellungen — jede Seite entscheidet beim Umbau, ob ihre Knöpfe
  einen Abschnitt oder mehrere speichern; „Passwort ändern“ ist eines.
- **Erklärtext ohne Verweis** (E-OF-02, OF-P-10, -15): Design.md 6 wird
  neu gefasst — je Karte höchstens ein Satz, **kein** Verweis „Handbuch: …“,
  die Regel gilt jetzt auch für den Bereich Einstellungen (E-P5c-06 hebt
  sich damit auf); die 62 Verweise und 49 Wortnennungen fallen (F-OF-04),  der Riegel `anker` bleibt. **Das „?“ der Kopfleiste öffnet das Kapitel
  der aktuellen Seite** (E-OF-56): `ui_einstellungen_punkte()` bekommt je
  Punkt die Handbuchmarke (`hilfe.php#…`), `ui_kopf()` setzt sie als Ziel;
  Seiten mit Diensttage-Leiste (Tagesübersicht, Einsatz, Formular,
  Papierkorb, Suche) tragen ihre Marke in `ui_geruest_start()`; ohne Marke
  bleibt das ganze Handbuch. `anker` hält die Ziele. Wo der eine Satz nicht reicht („Konto löschen“:
  was mit den Daten geschieht, die Frist), bleibt das Wesentliche als
  Meldung (`ui_meldung()`), nicht als Absatz. Seitenerklärung „Backup“
  (OF-P-15) auf einen Satz.
- **Abnahme:** P-OF-07 bis P-OF-09.

### OF-02 Bausteine I

- **Klappkarte mit Stand und Plakette** (E-OF-42, Bilder `OF-P24…` bis
  `OF-P27…`): `ui_karte_start(['zu' => true, 'stand' => 'kein Streifen',
  'plakette' => …])` gibt im `<details>`-Zweig Winkel links, Titel, die
  Standzeile `.karte-stand` (gedämpft, rechts) und die Plakette aus; die
  Sperre aus Design.md 9.1 fällt für `plakette`, **nicht für `aktion`** (eine
  Kopfaktion in einem `<summary>` wäre ein Knopf im Knopf). `vorschau` wird
  zu `stand` umbenannt — ein Wort für eine Sache; die drei heutigen Verwender
  ziehen mit. Offen gespeicherter Zustand: `open` nach dem Absenden eines
  Formulars in der Karte, damit die Meldung sichtbar ist.
- **Segmentwahl** (OF-P-05, E-OF-33): Beschriftung senkrecht mittig (die
  Zeile ist `--knopf` hoch, `align-items: center` fehlte an einer Stelle —
  alle Segmentwahlen werden nachgemessen); Option `'abwaehlbar' => true` an
  `ui_segment()`: Radios mit einem Skript, das einen zweiten Klick auf das
  gewählte Feld in „keines“ verwandelt (ein verstecktes Radio ohne Wert
  trägt den Zustand, damit das Formular ohne Skript gültig bleibt). Verwender:
  die Transportart (OF-07).
- **Zeile** (OF-P-08): Plakette und Knopf stehen auf einer Linie;
  `.zeile-aktionen` und `.zeile-plakette` bekommen dieselbe `align-items`;
  Bedienprobe misst die Zweitfaktor-Zeilen.
- **Plakette im Kartentitel** bricht bei schmaler Breite nicht ab, sondern
  rückt in eine zweite Zeile (`flex-wrap` am `.karte-kopf`, Nebenbefund zu
  OF-P-34).
- **Sechs neue Zeichen** aus Tabler, unverändert, deutsche Namen, Zeile in
  `server/assets/images/symbole/LIESMICH.md`: `filter.svg` („filter“,
  Filterknopf, E-OF-18), `wiederherstellen.svg` („restore“, Papierkorb,
  E-OF-30), `herunterladen.svg` („download“, GPX ausgeben, E-OF-11),
  `hochladen.svg` („upload“, GPX importieren, E-OF-11), `mond.svg` („moon“)
  und `sonne.svg` („sun“, Umschalter, E-OF-09). Alle sechs sind mit den
  Mockups freigegeben (Vorgabe 4 des Auftrags); Design.md 8 zieht nach,
  `vollstaendigkeit.py` kennt sie über den Ordner.
- **Abnahme:** P-OF-10, P-OF-11.

### OF-03 Bausteine II — Eingabe

- **Datumsfeld** (E-OF-06, E-OF-44): `ui_feld(['art' => 'date'])` rendert
  `<input type="text" inputmode="numeric" class="feld-eingabe feld-datum"
  placeholder="TT.MM.JJJJ" maxlength="10">`; `assets/datumsfeld.js` setzt
  die Punkte beim Tippen wie `zeitfeld.js` die Doppelpunkte und prüft
  Tag/Monat/Jahr; ohne Skript bleibt ein Textfeld mit `pattern`. **Eine
  Umrechnung je Seite:** PHP `datum_de_lesen()` / `datum_de()` in
  `server/eingabe_lib.php` (Register `tools/zaehlung/register.php` bekommt
  eine Zeile: kein `strtotime`/`DateTime::createFromFormat('d.m.Y')` außerhalb
  der Lib), Browser `EdFormat.datumLesen()` / `.datum()` in `format.js`. Die  acht Stellen aus F-OF-05 ziehen um (E-OF-54), das Geburtsdatum
  (verschlüsselt) im Browser vor dem Verschließen; `min`/`max` werden von der Prüfung in der
  Lib gehalten. Breite: `--feld-datum` (11 ch), kein Kalender.
- **Ortsfeld** (E-OF-07): Der Satz zur Adresssuche (`.loc-datenschutz`,
  heute einmal je Seite unter dem ersten Ortsfeld) wird ein Zeichen `hinweis`
  im Feld rechts neben der Lupe; der Satz liegt in einem `<button
  aria-describedby>` mit `title` und erscheint als Tooltip-Kasten (eine
  Meldung `info`, positioniert unter dem Zeichen) bei Hover, Fokus und
  Antippen — mit Tastatur erreichbar, für den Screenreader als Beschreibung.
  Ist die Adresssuche aus, fehlt das Zeichen wie heute der Satz.
- **Listenkopf mit Filterknopf** (E-OF-18, Bild `OF-P22-B-filterknopf.png`):
  `ui_listenkopf()` rendert neben dem Suchfeld (gleiche Höhe, `--suchfeld`
  48 px) einen Knopf „Filter“ mit `filter`-Zeichen, kräftigem Rand
  (`--linie-stark`), fett, und der Zahl der gesetzten Filter als Plakette;
  er klappt die `.filterreihe` auf (`<details>`-Mechanik, `listenkopf.js`
  merkt den Zustand nicht — offen heißt, jemand filtert gerade). Gesetzte
  Filter stehen als Pillen mit Kreuz unter der Suche (die Form gibt es,
  `kreuz => true`), daneben „18 von 2.580“. Ohne Skript ist die Filterreihe
  offen. Verwender: Protokoll, Konten; die Suche (`suche.php`) hat eine
  eigene Filterleiste und bleibt.
- **Abnahme:** P-OF-12 bis P-OF-14.

### OF-04 Einstellungen

- **Profil.** Zweitfaktor (E-OF-19, Bild `OF-P09-B-satz-verwalten.png`):
  Karte zeigt zugeklappt einen Satz („Eingeschaltet seit 28.09.2026 · 10
  Codes übrig · Rückweg eingerichtet · 1 gemerktes Gerät · kein Passkey“) und
  den Knopf „Verwalten“ als Kopfaktion (die Karte ist offen, nicht
  klappbar — das `<details>` liegt im Inhalt); aufgeklappt die vier Zeilen
  Codes, Gemerkte Geräte, Passkeys, Rückweg mit je einer Handlung (OF-P-08).
  „Was dein Konto hält“ und „Konto löschen“ auf einen Satz (OF-01), die
  Kachel „GPS-Daten verdichten“ nach Q-OF-04. Das Profil-Formular bekommt die
  Speicherleiste, „Passwort ändern“ den Knopf im Abschnitt.
- **Geräte** (E-OF-25, Bild `OF-P11-B2-dialog.png`): zwei Karten „Garmin-Uhr“
  und „Android-Handy und Wear-OS-Uhr“ mit den gekoppelten Geräten als Zeilen
  (Modell, zuletzt gemeldet, Plakette, Aktionsmenü), darunter je ein leiser
  Knopf „Weitere Uhr koppeln“ / „Weiteres Gerät koppeln“; er öffnet den
  **Koppel-Dialog** (Design.md 9.11, Formulardialog): drei Schritte „App
  installieren“, „Kopplung starten“, „Code eingeben“ mit Texten je Gerätetyp,
  Codefeld `feld-code`, Knopf „Gerät koppeln“. Die Platzzahl als Kleinzeile
  unter den Karten; die Warnung (Platz voll) als Meldung nur, wenn sie gilt;
  „App installieren“ wird Schritt 1 des Dialogs (Connect-IQ-Store bzw. APK
  von `apk.php`). `kopplung.js` zieht in den Dialog um; `geraeteprobe` und
  `kopplungsprobe` bleiben Kern, die Bedienprobe bekommt den neuen Weg.
- **Import / Export** (E-OF-26, Bild `OF-P17-C-wahlkarten.png`): zwei
  **Wahlkarten** „Liste übernehmen“ / „Liste ausgeben“ mit je einem Satz
  (ein Baustein `.wahlkarten`: Radios mit Kartenform, gewählt
  `--auswahl-flaeche` mit Linie Dunkelblau; freigegeben mit dem Mockup),
  darunter nur das passende Formular; Felder 36 rem (OF-01). Die
  Sprungmarken (1. Datei wählen, 2. Prüfen, 3. Übernehmen, Export) folgen
  der Wahl (F-OF-11-Mechanik).
- **Backup** (OF-P-14, -15, -16): Seitenerklärung ein Satz; Karte
  „GPS-Daten verdichten“ nur, wenn ein Lauf gescheitert ist oder nach dem
  stillen Abarbeiten Fälliges bleibt; sonst steht die Zahl als Zeile unter
  „Was dein Konto hält“ (E-OF-52). Kommt in OF-M1 eine Statuszeile
  „Verdichtung fällig“ für den Betrieb dazu, entfällt die Karte beim User
  ganz (Bedingung der Betreiberin, E-OF-52). „Für dich freigegebenes Backup“
  wie heute nur bei Freigabe, jetzt auch ohne Marke (NV-03).
- **Abnahme:** P-OF-15 bis P-OF-18.

### OF-05 Verwaltung

- **Kontenliste** (E-OF-53): Der Name des Kontos ist
  der Verweis auf die Kontoseite, die Spalte `oeffnen-spalte` entfällt. Die
  Zeile bleibt per Tastatur erreichbar (ein Verweis je Zeile, mit
  Vorlesetext „Konto … öffnen“). Nr. 342 wird nachgezogen: der
  Kontenlisten-Teil erledigt, Suche und Zeitraum bleiben bei 13.
- **Demo-Konto als Karte unter Installation** (E-OF-08, E-OF-46): Karte
  „Demo-Konto“ auf `admin_installation.php` mit Schalter „Demo-Anmeldung
  zulassen“ (der Schalter aus den Servereinstellungen zieht um; die
  Rückfrage beim Wechsel auf „nur auf Einladung“ bleibt in
  `betrieb_server.php` und schaltet denselben Wert), letzter Reset als
  Zeile, „Auf Standard zurücksetzen“ als Kopfaktion. „Aus“ sperrt nur die
  Anmeldung; Konto und Daten bleiben, der Reset ruht, die Warnung zum
  Schlüsselmaterial verschwindet. Die Kontrollzahlen von `admin_demo.php`
  werden Zeilen der Statusseite (Bereich Konten, OF-06); `admin_demo.php`
  leitet um, der Menüpunkt entfällt (F-OF-07). `demo_lib.php` bleibt
  unverändert; `rollenprobe` und `pruefablauf.json` ziehen den Pfad nach.
- **Abnahme:** P-OF-19, P-OF-20.

### OF-06 Betrieb

**Zuerst OF-M1 (Q-OF-02):** Die Umsetzung liest jede Zeile von
`status_erhebung()` und `plattform_pruefen()` und legt der Betreiberin eine
Tabelle vor — Zeile, kann sie rot werden, muss dann jemand handeln, steht es
woanders schon, Vorschlag (bleibt / entfällt / wandert) — und wartet auf die
Antwort, bevor die Statusseite gebaut wird. Die Tabelle steht danach in
diesem Konzept unter 9 (Prüfprotokoll OF-06). Danach gefächert (Statusblock).

- **Status** (E-OF-20, Bild `OF-P23-A-probleme-zuerst.png`): oben der
  Abschnitt „Braucht Aufmerksamkeit“ mit genau den Zeilen, die nicht grün
  sind, je mit ihrem Bereich als Vorsatz („Server · …“); darunter jeder
  Bereich als Klappkarte (OF-02) mit Zahl der Prüfungen und Plakette
  (`in Ordnung` grün, `2 Hinweise` orange, rot bei Fehler). Fünf Wörter für
  denselben grünen Zustand werden eines (Nebenbefund). Die Kennzahlen des
  Demo-Kontos (OF-05) werden Zeilen im Bereich Konten; `admin_demo.php`
  leitet danach auf die Statusseite um, `demo_pruefen.mjs` und der
  Bilderlauf (`44-demo-konto`) ziehen nach (E-OF-71). „Sicherheit“ und
  „Aktualisieren“ bleiben Kopfaktionen; „Aktualisieren“ steht nicht neben
  einem `restore`-Zeichen (E-OF-30).
- **Hintergrundjobs** (E-OF-10, -21, Bild `OF-P24-vorschlag.png`): oben
  eine offene Karte „Jobs vorübergehend pausieren“ mit Plakette (`laufen` /
  `pausiert bis …`), Segmentwahl 15 Min · 30 Min · 1 Std · 2 Std und Knopf
  „Pausieren“; der Begriff „Anhalten“ wird überall ersetzt (Knopf, Rückfrage,
  Meldungen, Protokolltexte, Handbuch 12, Technik 4.7, Wortliste). Zustand je
  Job als Zeile mit einem Satz, rechts zwei Plaketten, die nicht umbrechen
  („heute 23:24“ statt Datum — `format_lib.php` bekommt `zeit_relativ_kurz()`,
  `EdFormat` das Gegenstück; „Anfrage“ / „Kommandozeile“ für den Auslöser,
  statt „token“). Auslöser, Auslieferungskette (Kettentoken) und Monitoring
  werden Klappkarten mit Stand und Plakette.
- **Servereinstellungen** (E-OF-22, Bild `OF-P25-vorschlag.png`): jede der
  neun Karten wird Klappkarte; zugeklappt Titel, Stand in einer Zeile („5000
  Einsätze · 250 MB je Konto“) und Plakette, aufgeklappt das Formular mit
  seinem Speicherknopf im Abschnitt (OF-01). Der Stand wird je Karte aus den
  Werten gebildet, die das Formular zeigt — eine Funktion je Karte in
  `betrieb_server.php`, keine zweite Quelle. Die Karte „Konten“ verliert den
  Demo-Schalter (OF-05). Als letzte Karte „Backup-Ziele“ (unten).
- **Komplett-Backup** (E-OF-23, Bild `OF-P26-vorschlag.png`): oben die
  offene Karte „Stand“ mit Plakette (jüngster Stand, belegter Speicher, nächster
  Lauf), „Jetzt sichern“ als die eine orange Hauptaktion in der Titelzeile  (E-MA-27 bekommt eine siebte Seite, E-OF-55),
  darunter Regeln, Stände und Backup-Ziele als Klappkarten.
- **Backup-Ziele an beiden Orten** (E-OF-24, E-OF-45, Bilder `OF-P27-…`):
  Die Karte entsteht **einmal** in `server/sicherungsziele_teile.php`
  (Muster: `zweitfaktor_teile.php`) — Klappkarte mit Schalter „Backups
  automatisch versenden“, Zeilen „Aktive Ziele“ und „Wartet auf den nächsten
  Lauf“, Knöpfe „Speichern“ und „Ziel anlegen“ (öffnet den heutigen Dialog;
  Dialoge und Skript ziehen mit in die Teile-Datei) — und wird von
  `betrieb_server.php` und `admin_komplettsicherung.php` eingebunden.
  `admin_sicherungsziele.php` bleibt Ziel der Formulare (POST) und leitet bei
  GET auf `betrieb_server.php#k-backup-ziele` um; der Menüpunkt entfällt
  (F-OF-07). `versandprobe`, Statuszeilen („jede Zeile führt dorthin, wo sich
  etwas ändern lässt“), Handbuch 12 und `pruefablauf.json` ziehen die Adresse
  nach.
- **Abnahme:** P-OF-21 bis P-OF-25.

### OF-07 Einsatz, Diensttag, Karte, Papierkorb

- **Tagesübersicht:** Die Kachel „Ruhesegmente“ entfällt (E-OF-05); das
  Aktionsmenü des Diensttags bekommt „Einsatz aus GPS-Daten ausschneiden“
  und führt in den Schneide-Bereich (Design.md 9.17) mit der heutigen Wahl.
  OF-P-02 (Abstand) erledigt sich damit; wird nachgemessen.
- **GPX** (E-OF-11, -12): „GPS-Daten als GPX“ trägt `herunterladen`, „GPX
  importieren“ `hochladen` (Tagesübersicht, Spurenseite, Einsatzansicht);
  die Plakette „GPS-Daten ausgedünnt“ (`einsatz.php`, `tag_spuren.php`)
  entfällt, die Auskunft bleibt in der GPX-Datei und im Handbuch 4.
- **Einsatzformular** (E-OF-33, Bilder `OF-P32-…`): eine Karte „Einsatz und
  Transport“ — oben die Schalter Sekundärtransport und Fehleinsatz
  nebeneinander, dann die Transportart als Segmentwahl **Luft · Boden ·
  Ambulant**, abwählbar (OF-02; keine Wahl = offen), darunter Transportziel
  (Ortsfeld, Schloss) und die Schalter NA-Begleitung und Schockraum
  nebeneinander ohne Einzug; bei „Ambulant“ fallen Ziel und Schalter weg.
  **Umsetzung im Feldkatalog** (`mission_fields.php`): Gruppe, Reihenfolge
  und Abhängigkeit („sichtbar wenn“) als Katalogeigenschaften, die
  `einsatz_form.php` rendert — kein handgeschriebenes Markup je Feld
  (CLAUDE.md 4). Leseansicht (`einsatz.php`) zeigt dieselbe Gruppe; der
  gespeicherte Wert ändert sich nicht (Transportart bleibt die bestehende
  Spalte samt „offen“).
- **Schlösser in eigener Spalte** (E-OF-28, Bild `OF-P34-links.png`): in der
  Leseansicht eine feste Beschriftungsspalte (14 rem) und davor eine schmale
  Spalte für das Schloss; Felder ohne Schloss rücken gleich weit ein. Im
  Formular bleibt das Schloss hinter dem Wort (dort trägt die Beschriftung
  den Fokus). **Der Riegel `kennzeichnung` muss halten:** `dtGeschuetzt()`
  behält Name und Sollliste (`tools/quelltext/kennzeichnung-soll.md`), nur
  das Markup wandert; `kennzeichnung.php` wird vorher mit `--selbstprobe`
  gegen die neue Stelle gehalten. Die Leerzeile unter „Einsatzort“
  (Nebenbefund) wird dabei entfernt.
- **Karte** (E-OF-13, -29, Bilder `OF-P36-…`): Leaflet-Bedienelemente
  (`.leaflet-bar a`, `.leaflet-control-layers`) im Branding — Höhe `--knopf`
  (44/36 px), Fläche `--flaeche-karte`, `--linie-stark`, Radius wie Knöpfe,
  kein Schatten; die Ebenenwahl trägt das App-Zeichen `karte`, offen die
  Überschrift „Kartenart“ und Zeilen in Bedienhöhe, Auswahl Dunkelblau; Namen
  **Standard · Wanderkarte · Topografisch · Satellitenbild** ohne Anbieter
  (`map_layers.js`); **vorher prüfen**, dass die Zuschreibung unten rechts je
  Ebene den Anbieter nennt (Lizenzpflicht, `docs/Lizenzen.md` 6). Regeln im
  Stylesheet nach `vendor/leaflet`, keine Änderung an der Bibliothek.
- **Papierkorb** (E-OF-30, E-OF-48, Bild `OF-P37-A-restore.png`):
  „Wiederherstellen“ trägt `wiederherstellen` (Tabler „restore“); das
  Artzeichen des Diensttags steht vor dem Datum wie in Leiste und
  Vorschlägen, nicht neben den Knöpfen.
- **Abnahme:** P-OF-26 bis P-OF-31.

### OF-08 Darstellung

- **Vier Einstellungen im Profil** (E-OF-09): Umschalter (Vorgabe) · Immer
  hell · Immer dunkel · Folgt System, als `ui_wahlliste()` wie heute. Werte in
  `users.darstellung` (E-OF-43): `''` = Umschalter (Vorgabe; bisher System —
  bestehende Konten ohne Wahl bekommen damit den Umschalter, Q-OF-13),  `'hell'`, `'dunkel'`, **neu** `'system'`; `DARSTELLUNGEN`, `schema.sql`
  (Kommentar) und `darstellung_attribut()` ziehen nach, **keine Migration**
  (F-OF-13); dass Bestandskonten ohne Wahl damit den Umschalter bekommen,
  ist entschieden (E-OF-55).
- **Umschalter in der Kopfleiste:** ein Symbolknopf `mond`/`sonne` zwischen
  „?“ und Zahnrad (44 px, `aria-pressed`, Name „Dunkel ein“/„Dunkel aus“),
  sichtbar bei Umschalter und bei Folgt System, nicht bei Immer hell/dunkel
  und nicht ohne Anmeldung. Ein Klick setzt `data-theme` sofort im Browser
  und meldet die Wahl per `EdApi.postJson()` an `api/darstellung.php`; der
  Server legt sie **in der Sitzung** ab (`$_SESSION['darstellung_klick']`),
  nicht im Konto, und `ui_seite_start()` gibt sie als `data-theme` aus — kein
  Aufblitzen. Bei Umschalter gilt der Klick bis zur Abmeldung; „Erster
  Aufruf“ folgt dem Gerät (kein Attribut). Bei Folgt System ebenso, nur dass
  die Vorgabe ohne Klick das Gerät ist (E-OF-09 trennt beide nur darin, ob
  die Wahl als Vorgabe gilt — Umschalter merkt sich nichts über die Sitzung
  hinaus; beide verhalten sich in der Sitzung gleich, und die Betreiberin
  sieht das in P-OF-32).
- Handbuch 3.1a, Technik 4.41, Design.md 3a; `kontrast.py` kennt kein
  neues Paar (Zeichen in `--auf-dunkel` auf Dunkelblau gibt es schon).
- **Abnahme:** P-OF-32, P-OF-33.

### OF-09 Abschluss (NV-05 geht auf)

- **Design.md:** Kapitel 6 (drei Seitenregeln: Kante, Speicherknopf,
  Erklärtext ohne Verweis), 8 (sechs Zeichen), 9.1 (Klappkarte mit Stand und
  Plakette), 9.7 (Segment abwählbar; Datumsfeld), 9.9 (Speicherleiste neu),
  9.11 (Koppel-Dialog als Verwender), 9.13 (Ortsfeld-Zeichen), 9.18a
  (Filterknopf), 9.25 (Leiste nur aktiver Bereich, ohne Strich), 9.27
  (Übersicht ohne „Abmelden“), 9.37 (Reiter in beiden Ebenen, 48 px), neu
  9.42 Wahlkarten, 9.43 Leaflet im Branding; Kapitel 10 Gerüst neu
  (Bereichszeile, Rahmenkarte mit zwei Darstellungen, Leistenspalte,
  Fußzeile) und Seitentypen; 3a (Umschalter). Erzeugte Tabellen mit
  `tools/erzeugen/design.py schreiben`.
- **Handbuch:** 3 (Bereichszeile, Rahmenkarte, Umschalter, Fußzeile), 3.1a
  (Darstellung, Zweitfaktor), 4 (Einsatz und Transport, Schlösser,
  Kartenart, Ausschneiden im Aktionsmenü), 6, 7, 8, 10 (Koppel-Dialog), 11
  (Kontenliste, Demo-Konto unter Installation), 12 (Status, „pausieren“,
  Klappkarten, Backup-Ziele an zwei Orten). Entfallene Seiten und Begriffe
  austragen.
- **Technik:** 2 (neue Dateien), 3 (`darstellung`-Werte), 4.41, 13 (Runbook
  Feldkatalog: Gruppe und Abhängigkeit). **CHANGELOG:** ein Eintrag Web je
  Fassung, höchstens fünf Sätze. **Lizenzen:** keine neue Abhängigkeit.
- **Prüfmittel:** Bilderlauf (`seiten.json`: Backup-Ziele und Demo-Konto
  raus, Koppel-Dialog offen dazu; Sollzahlen in der `LIESMICH.md`),
  `kontrast.py` (Fußzeile, Reiter, Speicherleiste, Leaflet-Knöpfe,
  Wahlkarten), Bedienprobe-Wege (Datumsfeld, Koppel-Dialog, Filterknopf,
  Segment abwählbar), `kennzeichnung`, `textprobe` (neue Wörter),
  `vollstaendigkeit` (neue Klassen, Streichliste), `register.php` (Zeile
  Datum), `pruefablauf.json` (neue Dateien → Proben); die drei
  Messungen des Bilderlaufs aus OF-01 (E-OF-49, Nr. 530) mit Selbstprobe.
- **Prüfstand** Hauptstufe voll, drei Engines, zweimal grün (E-MA-34 als
  Maßstab); Pull Request; Prüfdokument nach CLAUDE.md 7 und am Ende in
  `Pruefliste.md`; Erledigt-Zeile, Verlaufszeile, Konzept gelöscht.

## 6. Entscheidungen

Die Entscheidungen E-OF-01 bis -38 und E-NV-01 bis -05 sind **wörtlich** aus
der Vorbereitung und der NV-Übergabe übernommen; neu ab E-OF-39.

| Nr. | Entscheidung | Von wem |
|---|---|---|
| E-NV-01 | Layout **Variante D** (Bereichszeile + gemeinsame Karte für Leiste und Inhalt) aus vier Varianten: A App-Rahmen (Leiste am Fensterrand, volle Höhe), B Tab-Zeile mit nackter Leiste, C eine Karte ohne Tab-Zeile, D = B + C | Betreiberin, 07.11.2026, Canvas „GenEM Vorschläge“, Board Navigation |
| E-NV-02 | Die Leiste zeigt am Desktop nur den aktiven Bereich; die Bereiche wandern in die Bereichszeile. In der Schublade (unter 1024 px) bleiben alle drei Bereiche als Akkordeon | folgt aus E-NV-01; Schubladen-Teil Vorschlag Claude |
| E-NV-03 | Abschnitte der Einstellungsseiten werden innerhalb der Rahmenkarte als Trennlinien gezeigt, nicht als Karten — per CSS-Geltungsbereich, ohne Änderung an `ui_karte_start()` | Vorschlag Claude, im Mockup D so gezeigt und freigegeben |
| E-NV-04 | Bereichszeile 48 px hoch in jeder Breite (keine 36-px-Variante am Zeigergerät) | Vorschlag Claude |
| E-NV-05 | Reiter-Unterstrich und aktive Farbe in Dunkelblau (`aktion`), nicht Orange — folgt der Aktionsfarben-Entscheidung (Variante 3, Branding-Repo `e7bf4fc`): Orange nur für die eine Hauptaktion einer Seite, Navigation bleibt Blau | Betreiberin, 07.11.2026 (Aktionsfarbe), Anwendung hier Claude |
| E-OF-01 | Mockups als Artifact im Live-Umfeld; entschiedene Mockups als Bild nach `konzept-of/mockups/` | Betreiberin, 07.11.2026 |
| E-OF-02 | Kein Verweis „Handbuch: …“ mehr in Seitentexten und Karten, überall; der Weg ins Handbuch ist das „?“ der Kopfleiste. Gekürzte Erklärungen stehen ohne Verweis | Betreiberin, 07.11.2026 |
| E-OF-03 | Eigenes Konzept OF auf eigenem Zweig; das Konzept schreibt Fable | Betreiberin, 07.11.2026 |
| E-OF-04 | P-13 gestrichen: Die Adresssuche bleibt im Browser. Über den Server ginge der Klartext der verschlüsselten Ortsfelder durch ihn hindurch (`CLAUDE.md` 4) | Betreiberin, 07.11.2026 |
| E-OF-05 | P-03: Die Kachel „Ruhesegmente“ entfällt; im Aktionsmenü des Diensttags heißt der Weg „Einsatz aus GPS-Daten ausschneiden“ | Betreiberin, 07.11.2026 |
| E-OF-06 | P-06: Eigenes Datumsfeld `TT.MM.JJJJ` mit Maske wie die Zeitfelder, ohne Kalender | Betreiberin, 07.11.2026 |
| E-OF-07 | P-12: Am Ortsfeld nur ein Symbol; der Satz zur Adresssuche erscheint beim Antippen bzw. Darüberfahren und ist mit Tastatur und Screenreader erreichbar | Betreiberin, 07.11.2026 |
| E-OF-08 | P-21: „Aus“ sperrt die Anmeldung des Demo-Kontos; Konto und Daten bleiben, der Reset ruht, die Warnung zum Schlüsselmaterial verschwindet. Ob es heute schon einen Schalter gibt, ist zu prüfen *(geprüft: ja, F-OF-06)* | Betreiberin, 07.11.2026 |
| E-OF-09 | P-28: Vier Einstellungen im Profil — **Umschalter (Vorgabe)**: Mond/Sonne zwischen „?“ und Zahnrad, Wahl gespeichert; **Immer hell** und **Immer dunkel** ohne Umschalter; **Folgt System**: folgt dem Gerät und zeigt den Umschalter, ein Klick gilt **bis zur Abmeldung**. Gespeichert **im Konto** (Migration). Erster Aufruf im Umschalter-Modus nach dem Gerät; ohne Anmeldung nach dem Gerät, ohne Umschalter (Vorschlag, nicht widersprochen). Symbole Tabler `moon`/`sun` | Betreiberin, 07.11.2026 |
| E-OF-10 | P-24: „Anhalten“ heißt „Jobs vorübergehend pausieren“; die Begriffe werden überall nachgezogen (Knopf, Meldungen, Handbuch, Protokoll) | Betreiberin, 07.11.2026 |
| E-OF-11 | P-30: Tabler `download` für „GPS-Daten als GPX“, `upload` für „GPX importieren“; beide neuen Zeichen freigegeben | Betreiberin, 07.11.2026 |
| E-OF-12 | P-31: Die Plakette „GPS-Daten ausgedünnt“ entfällt; die Auskunft bleibt in der GPX-Datei und im Handbuch | Betreiberin, 07.11.2026 |
| E-OF-13 | P-35: Kartenauswahl ohne Anbieternamen (Standard, Wanderkarte, Topografisch, Satellit); die Zuschreibung unten rechts muss je Ebene den Anbieter nennen — vorher prüfen | Betreiberin, 07.11.2026 |
| E-OF-14 | Q-NV-04: Die rote Zahl steht am Reiter jedes Bereichs, summiert über seine Punkte (Verwaltung z. B. überfällige Konto-Backups, Betrieb die Statusbefunde) | Betreiberin, 07.11.2026 |
| E-OF-15 | Q-OF-09: Ob Seitenreiter wie die Bereichszeile aussehen, wird an den Mockups zu P-20 entschieden | Betreiberin, 07.11.2026 |
| E-OF-16 | P-04: Variante **A — ohne Markierung**: Der aktive Eintrag trägt nur die Auswahlfläche, kein Strich links; gilt für jede Leiste (Einstellungen, Diensttage, Vorschläge). Bild `OF-P04-A-ohne-markierung.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup A) |
| E-OF-17 | P-20 und Q-OF-09: Variante **A — gleiche Form**: Seitenreiter (Rechtstexte, Protokoll, Statistik u. a.) sehen aus wie die Bereichszeile aus NV; ein Reiter-Baustein für beide Ebenen. Bild `OF-P20-A-gleiche-form.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup A) |
| E-OF-18 | P-22: Variante **B — Filterknopf**: Neben dem Suchfeld ein Knopf „Filter“ mit Plakette (Zahl der gesetzten Filter), der die Filter aufklappt. Nachgebessert auf Hinweis: Der Knopf ist so hoch wie das Suchfeld, kräftiger Rand, fett, mit Symbol Tabler „filter“ (neu im Vorrat, Design.md 8). Bild `OF-P22-B-filterknopf.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup A) |
| E-OF-19 | P-09: Variante **B — ein Satz plus „Verwalten“**: Die Karte Zweitfaktor zeigt zugeklappt einen Satz mit allem Wichtigen und einen Knopf „Verwalten“; aufgeklappt die Zeilen mit ihren Handlungen. Bild `OF-P09-B-satz-verwalten.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup B) |
| E-OF-20 | P-23: Variante **A — Probleme zuerst, Bereiche zugeklappt**: Oben ein Abschnitt mit genau den Zeilen, die etwas brauchen, je mit ihrem Bereich; darunter jeder Bereich als zugeklappter Abschnitt mit Zahl und Plakette. Bild `OF-P23-A-probleme-zuerst.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup C) |
| E-OF-21 | P-24: **Vorschlag** übernommen: „Jobs vorübergehend pausieren“ als eigene Karte oben; im Zustand je Job ein kurzer Satz, Plaketten brechen nicht um („heute 23:24“ statt Datum); Auslöser, Kettentoken und Monitoring in der üblichen Klappkarte, Zustand als Plakette im Kopf. Bild `OF-P24-vorschlag.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup C) |
| E-OF-22 | P-25: **Vorschlag** übernommen: Jede Karte der Servereinstellungen wird Klappkarte; zugeklappt zeigt sie den eingestellten Stand in einer Zeile und die Plakette, aufgeklappt das Formular. Der Klappbaustein ist derselbe wie bei P-24. Ob die Backup-Ziele dorthin wandern, ist P-27 *(entschieden: E-OF-24)*. Bild `OF-P25-vorschlag.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup C) |
| E-OF-23 | P-26: **Vorschlag** übernommen: Oben der Stand (offen), darunter Regeln, Stände und Backup-Ziele als Klappkarten in derselben Form wie die Servereinstellungen; „Jetzt sichern“ ist die eine orange Hauptaktion. Ob die Backup-Ziele zusätzlich dort und in den Servereinstellungen stehen, ist P-27 *(entschieden: E-OF-24)*. Bild `OF-P26-vorschlag.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup C) |
| E-OF-24 | P-27 und Q-OF-06: Die Seite „Backup-Ziele“ entfällt. Die Ziele stehen **an beiden Orten** als dieselbe Klappkarte (ein Baustein): als letzte Karte in den Servereinstellungen und unter den Ständen im Komplett-Backup. „Ziel anlegen“ öffnet den heutigen Dialog. Bilder `OF-P27-servereinstellungen.png`, `OF-P27-komplett-backup.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup C) |
| E-OF-25 | P-11: Variante **B2 — nach Gerätetyp, Koppeln im Dialog**: Je eine Karte für Garmin und für Android/Wear OS mit den gekoppelten Geräten; darunter nur ein Knopf „Weitere Uhr koppeln“ bzw. „Weiteres Gerät koppeln“. Er öffnet einen Dialog mit den drei Schritten aus Variante A (App installieren, Kopplung am Gerät starten, Code eingeben), angepasst an den Gerätetyp. Platzzahl als Kleinzeile unter den Karten. Bild `OF-P11-B2-dialog.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup B: „B, unter ‚weitere Uhr koppeln‘ dann ein Popup“, danach „so wie Mockup“) |
| E-OF-26 | P-17: Variante **C — erst wählen, dann füllen**: Zwei Wahlkarten „Liste übernehmen“ und „Liste ausgeben“ mit je einem Satz, darunter nur das passende kurze Formular. Bild `OF-P17-C-wahlkarten.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup B) |
| E-OF-27 | Regel (aus P-29): „Abmelden“ steht am Rechner nur unten in der Leiste, nie im Inhalt; der große Knopf am Ende der Einstellungs-Übersicht entfällt dort. Am Handy steht es in der Schublade. Die Lage der Fußzeile (P-29) ist damit noch nicht entschieden *(entschieden: E-OF-34)* | Betreiberin, 07.11.2026 (Kommentar im Mockup A: „Abmelden hat in der Mitte nix zu suchen“) |
| E-OF-28 | P-34: Variante **Links vor der Beschriftung**: feste Beschriftungsspalte (14 rem), das Schloss steht vor dem Wort in einer eigenen schmalen Spalte, Felder ohne Schloss rücken gleich weit ein. Der Riegel `kennzeichnung` (Sollliste) muss weiter halten. Bild `OF-P34-links.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup D) |
| E-OF-29 | P-36 (mit P-35): **Vorschlag** übernommen: Kartenknöpfe in Bedienhöhe der App (44 px, am Zeigergerät 36 px), Kartenfläche, starke Linie, Radius wie Knöpfe, kein Schatten; Ebenenwahl mit dem App-Symbol „Karte“, offen mit Überschrift „Kartenart“, Zeilen in Bedienhöhe, Auswahl Dunkelblau, Namen ohne Anbieter (die Anbieter bleiben im Quellennachweis). Bilder `OF-P36-vorschlag.png`, `OF-P36-vorschlag-ebenen.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup D) |
| E-OF-30 | P-37 und Q-OF-07: Variante **A — Tabler „restore“** (kreisender Pfeil mit Punkt) für „Wiederherstellen“; nicht direkt neben „Aktualisieren“ stellen, die beiden sind verwandt. Der gestrichelte Kreis (Zeichen „ohne Rettungsmittel“, falsch platziert) wird unabhängig davon entfernt. Bild `OF-P37-A-restore.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup D) |
| E-OF-31 | P-19: **Vorschlag** übernommen: Jeder Abschnitt und seine Linie reichen bis zur rechten Kante der Rahmenkarte; begrenzt werden die Felder (36 rem), nicht die Abschnitte. Alle Seiten haben dieselbe rechte Kante; Listenseiten bleiben, wie sie sind. Ob ein Prüfmittel die Regel misst, ist Q-OF-03. Bild `OF-P19-vorschlag.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup B) |
| E-OF-32 | P-07, Regel für alle Seiten: Speichert ein Knopf **einen** Abschnitt, steht er als letztes Element in ihm. Speichert er **mehrere**, erscheint am unteren Rand der Rahmenkarte eine klebende Speicherleiste, sobald etwas geändert ist: Fläche Auswahlblau (`--auswahl-flaeche`, schaltet im Dunkelmodus mit), kein Strich und kein Bogen darüber, links fett „Geändert: …“, rechts der Knopf. Sie gehört zu keinem einzelnen Abschnitt. Ob ein Prüfmittel Knöpfe zwischen Abschnitten meldet, ist Q-OF-03. Bild `OF-P07-speicherleiste.png` | Betreiberin, 07.11.2026 (Kommentare im Mockup B, nach zwei Nachbesserungen) |
| E-OF-33 | P-32 (nachtragen und bearbeiten): eine Karte „Einsatz und Transport“. Oben die Schalter Sekundärtransport und Fehleinsatz nebeneinander; dann die Transportart als Segmentwahl mit **drei Feldern Luft, Boden, Ambulant**, höchstens eines gewählt, ein zweiter Klick nimmt die Wahl zurück (dann offen, kein eigenes Feld „offen“); darunter Transportziel und die Schalter NA-Begleitung und Schockraum nebeneinander, ohne Einzug. Bei „Ambulant“ fallen Ziel und Schalter weg. Umsetzung zentral im Feldkatalog. Bilder `OF-P32-vorschlag-leer.png`, `OF-P32-vorschlag-gefuellt.png` | Betreiberin, 07.11.2026 (Kommentare im Mockup D) |
| E-OF-34 | P-29: Variante **C — dunkelblauer Abschluss**: Die Fußzeile ist ein schmaler Streifen in Dunkelblau wie die Kopfleiste, unter der Rahmenkarte über die ganze Breite; die Seite ist oben und unten gerahmt. Im Inhalt steht kein „Abmelden“ (E-OF-27). Entschieden an einer ganzen, scrollbaren Seite im Gerüst aus Konzept NV. Bild `OF-P29-C-dunkelblau.png` | Betreiberin, 07.11.2026 (Kommentar auf der Vergleichsseite A/C) |
| E-OF-35 | Q-OF-10: **NV geht in OF auf.** Fable plant beides in einem Konzept, gebaut wird einmal. Die Pakete NV-01 bis NV-04 werden die ersten Pakete von OF — das Gerüst zuerst, weil alle Mockups darauf stehen —, NV-05 geht im Abschlusspaket von OF auf. E-NV- und Q-NV-Nummern bleiben, wie sie sind. `Konzept-NV-Navigation.md` wird mit seinen Mockups in den ersten Commit von OF übernommen und gelöscht | Betreiberin, 07.11.2026 (Vorschlag Claude, ohne starke Präferenz) |
| E-OF-36 | Q-NV-01, Teil Startseite (Mockup E, E1): Die Startseite bekommt die gemeinsame Rahmenkarte (Diensttage-Leiste und Inhalt in einer Fläche), Unterform **2 — Karten bleiben Karten** darin. Damit liegen auf der Startseite Karten mit Rand in der umrandeten Rahmenkarte; das widerspricht Prüfpunkt P-NV-04 („keine Karte in der Karte“), der für die Startseite deshalb nicht gilt *(nachgezogen in Abschnitt 8)*. Bilder `OF-E1-2-karten-1920.png`, `OF-E1-2-karten-390.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup E) |
| E-OF-37 | Startseite beim Scrollen (Mockup E, E2), Unterform **2 — Karten**: Die Diensttage-Leiste klebt unter der Kopfleiste, ist höchstens so hoch wie das Fenster und scrollt in sich; „Diensttag anlegen“ und „Papierkorb“ bleiben unten sichtbar; die Fläche der Leistenspalte reicht bis zum Boden der Rahmenkarte. Bild `OF-E2-2-karten-gescrollt.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup E) |
| E-OF-38 | Q-NV-01, Teil Suche (Mockup E, E3): Die Suche bekommt die gemeinsame Rahmenkarte wie die Startseite, Unterform **2 — Karten bleiben Karten**; die lange Filterleiste klebt und scrollt in sich. Damit ist Q-NV-01 ganz entschieden: Rahmenkarte auch für Startseite und Suche, dort mit Karten statt Abschnitten (P-NV-04 gilt für beide nicht). Bild `OF-E3-2-suche-karten.png` | Betreiberin, 07.11.2026 (Kommentar im Mockup E) |
| E-OF-39 | Schritt **27**, Kennung OF, als Einschub vor 13 (Reihenfolge 27 → 13 → 14), weil die Betreiberin die Runde nach dem Merge von 26 beauftragt hat (E-OF-03) und 13 (Gesicht v1.0) auf der fertigen Oberfläche aufsetzen soll | Konzept, aus E-OF-03 — **bestätigt** von der Betreiberin, 07.11.2026 (Q-OF-11) |
| E-OF-40 | Die Bereichszeile ist der Reiter-Baustein `ui_reiter()` mit der Lagevariante `.reiter-bereiche`; `ui_bereichszeile()` baut nur die Punkte. Beide Ebenen haben damit eine Regel im Stylesheet; die 48 px (E-NV-04) gelten auch für Seitenreiter | Konzept, aus E-OF-17 (F-OF-15) |
| E-OF-41 | Die Speicherleiste ist der Umbau von `ui_speichern_leiste()` (Design.md 9.9), kein zweiter Baustein: Sitz am Rand der Rahmenkarte, Fläche `--auswahl-flaeche`, Text „Geändert: …“; die Sammelleiste bleibt zweite Verwendung in derselben Form | Konzept, aus E-OF-32 (F-OF-02) |
| E-OF-42 | Die Klappkarte (`ui_karte_start(['zu' => true])`) trägt `stand` (bisher `vorschau`) und `plakette`; die Sperre aus Design.md 9.1 fällt für die Plakette, nicht für die Kopfaktion. Ein Baustein für Status, Hintergrundjobs, Servereinstellungen, Komplett-Backup und Backup-Ziele | Konzept, aus E-OF-21 bis -24 (F-OF-03) |
| E-OF-43 | Darstellung: `users.darstellung` bekommt den vierten Wert `'system'`; `''` heißt Umschalter (Vorgabe nach E-OF-09), `'hell'`, `'dunkel'` bleiben. Keine Migration (`VARCHAR(10)`). Die Sitzungswahl des Umschalters liegt in der Sitzung, nicht im Konto | Konzept, aus E-OF-09 (F-OF-13); Folge für bestehende Konten in Q-OF-13 |
| E-OF-44 | Datumsfeld: eine Umrechnung je Seite — `datum_de_lesen()` / `datum_de()` in `eingabe_lib.php` (Zeile im Register) und `EdFormat.datumLesen()` / `.datum()`; das verschlüsselte Geburtsdatum rechnet der Browser um | Konzept, aus E-OF-06 (F-OF-05) |
| E-OF-45 | Backup-Ziele: die Karte entsteht einmal in `server/sicherungsziele_teile.php` und wird von Servereinstellungen und Komplett-Backup eingebunden; `admin_sicherungsziele.php` bleibt Ziel der Formulare und leitet bei GET um; der Menüpunkt entfällt | Konzept, aus E-OF-24 (F-OF-07) |
| E-OF-46 | Demo-Schalter: Der vorhandene Schalter „Demo-Anmeldung zulassen“ zieht aus den Servereinstellungen in die Karte „Demo-Konto“ unter Installation; ein Wert, eine Stelle. `admin_demo.php` leitet um, die Kontrollzahlen werden Statuszeilen | Konzept, aus E-OF-08 (F-OF-06, -07) |
| E-OF-47 | `Vorbereitung-OF-Oberflaechenrunde.md` geht in diesem Konzept auf (Punkte, Entscheidungen, Fragen wörtlich) und wird mit diesem Commit gelöscht; zwei Dateien mit derselben Entscheidungstabelle altern an einer unbemerkt. Die Git-Historie behält sie (`0464977`) | Konzept |
| E-OF-49 | Q-OF-03: Die drei Seitenregeln (Knopf zwischen Abschnitten, eine rechte Kante, ein Satz je Karte) misst der Bilderlauf an der gerenderten Seite; kein neues Werkzeug. Anlass Backlog Nr. 530 | Betreiberin, 07.11.2026 („ok, so machen wir es“) |
| E-OF-50 | Q-OF-08 und Q-NV-03: Die Übersichtsseite „Einstellungen“ bleibt neben Bereichszeile und Leiste, unverändert bis auf „Abmelden“ (E-OF-27) | Betreiberin, 07.11.2026 |
| E-OF-51 | Q-NV-02: Der Reiter „Betrieb“ führt auf den ersten Punkt des Blocks, keine Sonderregel; heute ist das Status | Betreiberin, 07.11.2026 |
| E-OF-52 | Q-OF-04: Die Karte „GPS-Daten verdichten“ zeigt die NutzerIn nur, wenn ein Lauf gescheitert ist oder Fälliges nach dem stillen Abarbeiten bleibt; sonst eine Zeile unter „Was dein Konto hält“. Bedingung der Betreiberin: Sobald der Betrieb den Zustand im Status sieht (Option in OF-M1, F-OF-17), entfällt die Karte beim User ganz | Betreiberin, 07.11.2026 („beim User nie zeigen, wenn der Admin es sieht; wenn nicht, wie der Vorschlag“) |
| E-OF-53 | Q-OF-05: Der Name des Kontos ist der Verweis auf die Kontoseite, die Spalte „Öffnen“ entfällt | Betreiberin, 07.11.2026 |
| E-OF-54 | Q-OF-12: Das Datumsfeld `TT.MM.JJJJ` an allen acht Stellen, auch Geburtsdatum und Suchfilter | Betreiberin, 07.11.2026 |
| E-OF-55 | Q-OF-13: Bestandskonten ohne Wahl bekommen mit der neuen Vorgabe den Umschalter; „Jetzt sichern“ ist die siebte Hauptaktion in Orange (ergänzt E-MA-27) | Betreiberin, 07.11.2026 |
| E-OF-56 | Q-OF-14: Das „?“ der Kopfleiste öffnet das Handbuchkapitel der aktuellen Seite; Zuordnung je Menüpunkt, der Riegel `anker` hält die Ziele | Betreiberin, 07.11.2026 |
| E-OF-57 | NV-01: Die Bereichszeile klebt ab 1024 px unter der Kopfleiste, die Leiste unter beiden (`.rahmen-bereiche`); darunter rollt sie mit der Seite weg — auf dem Handy wären 104 px feste Kopfzone ein Sechstel des Bildes. Unter 720 px enger (`--abstand-2` statt `-4`), damit drei Bereiche bei 390 px ohne Rollen stehen (P-NV-06) | Umsetzung, 08.11.2026 |
| E-OF-58 | NV-01: Der Unterstrich von 3 px (E-NV-05) ist ein abgeleitetes Maß `--strich-reiter: calc(var(--strich-stark) + var(--strich))`, keine neue Zahl in der Skala; „keine Token außer `--bereiche`“ (Abschnitt 1) gilt damit für die Skala, nicht für die Rechnung | Umsetzung, 08.11.2026 |
| E-OF-59 | NV-01: Fassung — NV-01 hebt die Nebennummer (23.12.0); die Hauptnummer kommt mit NV-02 (24.0.0), weil erst die Rahmenkarte und die gekürzte Leiste den Weg sichtbar ändern (K3) | Betreiberin, 08.11.2026 |
| E-OF-60 | Prüfweise der Umsetzung: Nach jedem Paket die billigen Mittel — 24 Riegel aus `pruefablauf.json` (`braucht: nichts`), Stilvergleich in drei Engines mit Durchsicht der neuen Signaturen, `kontrast.py`, gezielte Bilder der berührten Seiten; Bilderlauf, Barrierefreiheit und Bedienprobe in Chromium nach NV-04; der volle Prüfstand mit Bericht vor dem Pull Request (OF-09). Grund: Zwei Neustarts des Containers brachen den vollen Prüfstand (rund 2 h) nach 20 und nach 180 Minuten ab (NV-01) | Betreiberin, 08.11.2026 (Vorschlag Claude) |
| E-OF-61 | NV-02: (1) Auch die Bereichskarten der Übersicht werden Abschnitte, ohne Rauchfläche im Kopf — so zeigt es das freigegebene Bild `OF-P29-C`; E-OF-50 („unverändert“) bezieht sich auf Inhalt und Zweck der Seite. (2) Jeder Abschnitt trägt die Linie oben, auch der erste; sie steht damit unter dem Seitentitel wie in `NV-01` — eine Ausnahme für die erste Karte (Abschnitt 5, NV-02) braucht es dafür nicht. (3) Die Fußzeile bleibt im Druck weg (Abschnitt 5, NV-02); bis Web 23 wurde sie gedruckt — „wie heute“ dort stimmte nicht. (4) Unter 1024 px haben Rahmenkarte und Leistenspalte keine Box (`display: contents`); was die schmale Breite sonst braucht, bringt NV-04 | Umsetzung, 08.11.2026 |
| E-OF-62 | NV-03: Die Sprungmarken ziehen über einen `MutationObserver` auf `hidden` im Inhalt nach, nicht über einen Aufruf der Backup-Seite (Abschnitt 5, NV-03). So kann keine Seite mit bedingter Karte — Backup, Zweitfaktor, Geräte, Import / Export nach OF-04 — das Nachziehen vergessen; die Durchsicht je Seite entfällt damit. Der Vorschlag verliert mit dem Strich seine einzige Markierung auf Rauch und bekommt deshalb die Auswahlfläche (Blau 100) statt Rauch; zwei Kontrastpaare, deren Beschreibung den Strich nannte, tragen weiter andere Stellen und sind neu beschrieben | Umsetzung, 08.11.2026 |
| E-OF-63 | NV-04 ohne Codeänderung: Was Abschnitt 5 für unter 1024 px verlangt, leistet `display: contents` an Rahmenkarte und Leistenspalte aus NV-02 (E-OF-61) — keine Box, kein Rand, keine Fläche; die Bereichszeile rollt (NV-01), Schublade und Rückweg sind unverändert. Die Regel `.rahmen { padding: --abstand-3 }` entfällt: `.inhalt` trägt den Abstand schon, und der Inhalt liegt wie im Bild `NV-05` 12 px vom Rand; mit ihr wären es 24. Keine neue Fassung — es ändert sich nichts, was ausgeliefert wird | Umsetzung, 08.11.2026 |
| E-OF-64 | OF-01: Den Verweis ins Handbuch behalten die Druckblätter (Notfall-, Code-, Schlüsselblatt — auf Papier gibt es kein „?“) und die öffentlichen Seiten ohne Kopfmenü (Wiederherstellung, Notzugang zum Zweitfaktor), auf denen er der einzige Weg ins Handbuch ist. „Überall“ in E-OF-02 meint die Seiten mit Kopfleiste | Umsetzung, 08.11.2026 |
| E-OF-65 | OF-01: Die drei Seitenregeln sind ab sofort rot im Bilderlauf; die 31 bekannten Verstöße stehen mit dem Paket, das sie behebt, unter `seitenregeln` in `ausnahmen.json`. Jedes Paket streicht seine Zeilen, eine Ausnahme ohne Befund ist selbst rot — so läuft die Liste bis OF-09 auf null, und ein neuer Verstoß fällt sofort auf. Seiten ohne eigenes Paket sind zugeordnet: Standorte zu OF-04, Rückfrage beim Konto (`02h`) sowie Diensttag anlegen / Datum / zusammenführen und Einsatz verschieben zu OF-07 | Umsetzung, 08.11.2026 |
| E-OF-66 | OF-01: Die Handbuchmarken stehen als Zuordnung Menüpunkt → Marke in `ui_hilfe_marke()`, nicht als sechstes Glied der Punkt-Tupel — die Tupel werden an fünf Stellen positionsweise gelesen. Seiten mit eigenem Kapitel (Kontoseite, Sicherheit, die Diensttage-Seiten, Suche) geben `hilfe` an `ui_geruest_start()` | Umsetzung, 08.11.2026 |
| E-OF-67 | OF-01: Die Speicherleiste bekommt ihre Form jetzt (Fläche, bündig, „Geändert: …“ als Vorgabe jeder Formularleiste); welche Seite Knöpfe im Abschnitt und welche die Leiste trägt, entscheidet das Paket der Seite (Profil OF-04, Installation OF-05, Servereinstellungen OF-06), wie Abschnitt 5 es sagt. „Ungespeichert“ der Rechtstexte wird „Geändert“ | Umsetzung, 08.11.2026 |
| E-OF-68 | OF-02: `vorschau` heißt `stand` bei allen **neun** Verwendern, nicht bei dreien (Abschnitt 5 zählte drei); `.karte-vorschau` heißt `.karte-stand`. Das Öffnen „nach dem Absenden“ macht ein Gerüstskript `klappkarte.js` am Anker der Adresse, nicht der Server — die Seiten leiten nach dem Speichern auf `#k-…` um, und den Anker sieht nur der Browser; dasselbe Skript öffnet die Karte beim Sprung über eine Sprungmarke. Die Leertaste der abwählbaren Segmentwahl behandelt `segment.js` selbst, weil Chromium und WebKit auf einem gewählten Radio keinen Klick auslösen | Umsetzung, 08.11.2026 |
| E-OF-69 | OF-03: (1) Datumsformatieren bleibt bei den vorhandenen `EdFormat.tag()` (Browser) und kommt als `datum_de()` nach `format_lib.php` (Register: Text aus Zeitpunkt → `format_lib`); nur das Lesen steht in `eingabe_lib.php` (`datum_de_lesen()`, `post_datum()`, `get_datum()`) und als `EdFormat.datumLesen()`. Beide Formen gelten beim Lesen; ein Wert, der kein Tag ist, geht unverändert an die Prüfung der Seite. Die Suche trägt in der Adresse weiter ISO (Art `datum`), damit verschickte Links gelten. (2) Das Zeichen zur Adresssuche bleibt **einmal je Seite** am ersten Ortsfeld, wie der Satz vorher; der Tooltip ist so breit wie die Feldzeile, kein `title` am Knopf (er stünde zweimal im Vorlesetext). (3) Der Filterknopf ist ein Knopf mit `aria-expanded`, kein `<details>`: Knopf neben dem Suchfeld und Reihe darunter liegen nicht in einem Kasten. „Gesetzt“ heißt aktiv und nicht `vorgabe`; die Pille nimmt über `weg` zurück | Umsetzung, 08.11.2026 |
| E-OF-70 | OF-04: (1) „Verwalten“ ist ein `<details>` im Inhalt der Zweitfaktor-Karte (`zf-verwalten`), sein `<summary>` die Zeile mit dem Satz und dem Knopfbild; offen ist es von selbst, solange der Code frisch ist (`zweitfaktor_frisch()`) — dann steht die Handlung, für die man gerade bestätigt hat, schon da. Die Zahlen (Geräte, Passkeys, Rückweg) stehen in der Kleinzeile, nicht als Plakette (OF-P-08). Das Ausschalten hat ein eigenes Formular, weil es am Codes-Formular zwei `action`-Felder gesendet hätte. (2) Das Anlegen eines Passkeys bleibt der Block unter der Zeile „Passkeys“ (Bezeichnung, Knopf), keine Aktion in der Zeile — `passkey.js` blendet ihn erst ein, wenn der Browser Passkeys kann. (3) Geräte: Art `handy` gehört zur Karte „Android-Handy und Wear-OS-Uhr“, jede andere zur Garmin-Karte; der Dialog trägt den Typ als verborgenes Feld und öffnet nach einem Fehler wieder. (4) Die Speicherleiste kann zu einem Formular außerhalb gehören: `ui_speichern_leiste(['form'])` setzt `data-fuer`, `forms.js` findet sie darüber. (5) Wahlkarten (`wahlkarten.js`): der Anker der Adresse wählt, beim Laden **und** bei `hashchange` — eine Sprungmarke auf `#exportform` lädt nicht neu. (6) Ein Leerzustand („Noch kein …“, „Kein …“) zählt in der Seitenregel `saetze` nicht als Satz der Karte; damit fällt die Ausnahme des Protokoll-Archivs (`44c`, OF-05) ohne Umbau weg. (7) Korrektur an E-OF-65: „Rückfrage beim Konto“ (`02h`) steht in `ausnahmen.json` bei OF-07, nicht bei OF-04; der Text von E-OF-65 ist angeglichen | Umsetzung, 08.11.2026 |
| E-OF-71 | OF-05: Die Kontrollzahlen des Demo-Kontos (Bestand, drei Papierkorbzahlen) und die Umleitung von `admin_demo.php` wandern mit **OF-06**, nicht mit OF-05 — sie werden Zeilen der Statusseite, und deren Bau wartet auf OF-M1. Bis dahin zeigt `admin_demo.php` sie nur lesend, ohne Menüpunkt; `demo_pruefen.mjs` liest dort weiter und klickt die Knöpfe unter Installation. Im Zweig ist die Seite nur über ihre Adresse erreichbar; ausgeliefert wird erst nach OF-09. Weiter: (1) die Kopfaktion der Karte ist „Auf Standard zurücksetzen“ bzw. „Demo-Konto anlegen“, „Entfernen“ steht an der Zeile „Konto“ (eine Kopfaktion je Karte, Design.md 9.1); (2) die Konten-Karte der Servereinstellungen schreibt den Demo-Wert nicht mehr — sonst schaltete jedes Speichern dort ihn ab; ihre Rückfrage prüft den gespeicherten Wert; (3) „Der Reset ruht“ gilt, weil ihn nur Anfragen des Demo-Kontos auslösen — ein gekoppeltes Demo-Gerät, das hochlädt, löst ihn weiter aus, und so steht es in Karte und Handbuch; (4) „Namen“ und „Adressen speichern“ bekommen Haken und Fußzeile wie der Rest der Seite (E-OF-67); (5) die Kontenliste rollte nach dem Wegfall von „Öffnen“ bei 1024 px noch 43 px — Spaltentitel und Adresse brechen jetzt um | Umsetzung, 08.11.2026 |
| E-OF-72 | Q-OF-02 (OF-M1): Die Tabelle der Umsetzung gilt wie vorgelegt. **Entfallen:** Server · Datenbank (Größe), Gerätemodelle, PHP und Zeitzone, Gesperrt; E-Mail · SMTP; Backups · Ablage; Plattform · „Empfohlen insgesamt“ (wird Plakette der Karte); die Karte Betriebsprotokoll. **Nur bei Abweichung:** Umgebung. **Wandert:** „Antwort und Versand“ zu den Empfehlungen der Plattform (färbt die Ampel nicht mehr); Verwaltungskonten und Konto-Backups in eine neue Karte **„Konten“** mit zwei Zeilen zum Demo-Konto — „Bestand“ (Information) und „Papierkorb“ (orange, wenn eine der drei Zahlen 0 ist). **Hintergrundjobs:** nur die nicht grünen Jobs einzeln, dazu eine Sammelzeile. **Bleiben** alle übrigen; die Karte der `config.php` zugeklappt, ohne Zählung. Tabelle mit Begründung je Zeile: Prüfprotokoll OF-06 | Betreiberin, 08.11.2026 („passt“) |
| E-OF-73 | Option F-OF-17: **keine** Statuszeile „Verdichtung fällig“ — die Betreiberin kann daran nichts tun (nur der Browser der NutzerIn verdichtet), ein Konto ohne Anmeldung bliebe dauerhaft orange. Damit gilt E-OF-52 ohne die Bedingung: Die Karte bei der NutzerIn bleibt wie in OF-04 gebaut | Betreiberin, 08.11.2026 („nein“) |
| E-OF-48 | Papierkorb: Das Artzeichen des Diensttags (`ohne-zuordnung`, Hubschrauber, Fahrzeug) steht vor dem Datum wie in Leiste und Vorschlägen — so zeigt es das freigegebene Bild; „entfernt“ in E-OF-30 meint die Stelle neben den Knöpfen. Will die Betreiberin es im Papierkorb ganz weglassen, ist das eine Zeile | Konzept, aus E-OF-30 (F-OF-16) |

Aus Schritt 26 übernommen: E-MA-36 (Beenden-Knöpfe der App bleiben
vollflächig rot) ist bestätigt.

## 7. Fragen an die Betreiberin

Beantwortet und in Entscheidungen aufgegangen: Q-OF-01 (Auswahl je Mockup →
E-OF-16 bis -34), Q-OF-06 → E-OF-24, Q-OF-07 → E-OF-30, Q-OF-09 → E-OF-17,
Q-OF-10 → E-OF-35, Q-NV-01 → E-OF-36 bis -38, Q-NV-04 → E-OF-14; am
07.11.2026 dazu Q-OF-03 → E-OF-49, Q-OF-08 und Q-NV-03 → E-OF-50, Q-NV-02 →
E-OF-51, Q-OF-11 → E-OF-39 bestätigt, Q-OF-04 → E-OF-52, Q-OF-05 → E-OF-53,
Q-OF-12 → E-OF-54, Q-OF-13 → E-OF-55, Q-OF-14 → E-OF-56.

Offen ist nur Q-OF-02; sie wird zu Beginn von OF-06 als Tabelle vorgelegt
(OF-M1).

| Nr. | Frage | Empfehlung | Hängt an |
|---|---|---|---|
| Q-OF-02 | P-23: Welche Zeilen der Statusseite entfallen? Claude legt je Zeile vor: Kann sie rot werden, muss dann jemand handeln, steht es woanders schon | Vorlage zu Beginn von OF-06 (Meilenstein OF-M1); bis dahin nichts entscheiden | OF-06 |

## 8. Prüfpunkte

P-NV-01 bis -10 aus der Übergabe bleiben; P-NV-04 ist nachgezogen (E-OF-36,
-38). P-OF ab 01. Die Handprüfliste entsteht am Ende als Prüfdokument und
geht in `Pruefliste.md` auf.

| Nr. | Was | Wie | Erwartung |
|---|---|---|---|
| P-NV-01 | Bereichszeile und Rahmenkarte auf demselben Raster | Screenshot 1920 | linke Kante der Reiter-Beschriftung = linke Kante der Karte + Seitenabstand; Logo darüber; drei Kanten fluchten |
| P-NV-02 | Leiste klebt, Hintergrund bis zum Kartenboden | Profil auf 1366 × 700 scrollen | Leiste bleibt sichtbar; keine helle Lücke unter ihr |
| P-NV-03 | Kurze Seite (Betrieb → Status) | Screenshot 1920 | Karte endet unter dem Inhalt, Leiste gleich hoch, „Abmelden“ unten in der Leiste |
| P-NV-04 | **Einstellungsseiten:** keine Karte in der Karte; **Startseite und Suche:** Karten mit Rand auf der Rahmenkarte (E-OF-36, -38) | alle `t=`-Seiten, Startseite, Suche durchklicken | Einstellungen: Abschnitte als Linien, Meldungen farbig; Startseite/Suche: Karten wie heute, Rahmenkarte darum |
| P-NV-05 | Schublade unter 1024 px | 390 und 768 px, Menü öffnen | drei Bereiche als Akkordeon, Klappzustand bleibt (`menue.js`) |
| P-NV-06 | Bereichszeile schmal | 390 px | drei Reiter ohne Rollen; bei vier Bereichen rollt die Zeile |
| P-NV-07 | Kontraste | `kontrast.py` auf Reiter inaktiv/aktiv, Zähler, Plakette, Fußzeile, Speicherleiste | AA gegen Schnee bzw. Dunkelblau |
| P-NV-08 | Bedienhöhen | Bilderlauf beide Sollwerte | Reiter 48 ≥ 44/36; Einträge der Leiste unverändert; Umschalter 44/36 |
| P-NV-09 | Lesespalte | `hilfe.php`, Rechtstexte ab 1200 | Text 760 px breit; Seiten ohne Leiste ohne Rahmenkarte |
| P-NV-10 | Drei Engines | Chromium, Firefox, WebKit | Sticky-Verhalten der Leiste in der Karte identisch (`overflow: clip`) |
| P-OF-01 | Seitenreiter und Bereichszeile eine Form | Protokoll, Statistik, Rechtstexte, 1920 und 390 | gleiche Schrift, Höhe, Unterstrich; Rechtstexte-Rückfrage bei ungespeichertem Text hält |
| P-OF-02 | Startseite gescrollt (E-OF-37) | Tagesübersicht mit 12 Monaten, 1366 × 700, bis zum Tabellenende rollen | Leiste klebt, rollt in sich, „Diensttag anlegen“ und „Papierkorb“ sichtbar, Leistenfläche bis zum Kartenboden |
| P-OF-03 | Fußzeile | jede Seitenart, hell und dunkel, Druckvorschau | dunkelblauer Streifen volle Breite unter der Karte; im Druck nicht |
| P-OF-04 | Aktiver Eintrag ohne Strich | Einstellungen, Diensttage, Vorschlagsliste, Schublade | nur Fläche; Bilderlauf-Vergleich zeigt keinen Strich |
| P-OF-05 | Sprungmarken (OF-P-16) | Backup-Seite ohne und mit Freigabe; Import / Export nach Wahl | Marke nur, wenn die Karte sichtbar ist; nach Einblenden erscheint sie |
| P-OF-06 | Übersicht | Zahnrad, 1920 und 390 | Bereichszeile „Einstellungen“ aktiv, drei Bereichskarten, kein „Abmelden“ im Inhalt |
| P-OF-07 | Eine rechte Kante | Installation, Servereinstellungen, Profil, NutzerInnen bei 1920 | alle Abschnittslinien enden an derselben Kante; Felder höchstens 36 rem |
| P-OF-08 | Speicherknopf | Profil: Logo ändern; Passwort ändern | Speicherleiste erscheint am unteren Rand der Rahmenkarte mit „Geändert: Logo“; Passwort-Knopf steht im Abschnitt; kein Knopf zwischen zwei Linien |
| P-OF-09 | Erklärtext ohne Verweis | `grep -rn "hilfe.php#" server/` und `grep -rn "Handbuch:" server/` | 0 Treffer in Karten- und Seitentexten; Riegel `anker` grün; „?“ öffnet das Kapitel (Q-OF-14) |
| P-OF-10 | Klappkarte | Servereinstellungen zugeklappt, aufgeklappt, nach Speichern | Stand und Plakette zugeklappt sichtbar; nach Speichern offen mit Meldung |
| P-OF-11 | Segmentwahl abwählbar | Einsatzformular: Boden wählen, Boden erneut klicken; ohne Skript | Wahl geht zurück auf offen; ohne Skript bleibt die letzte Wahl, Formular gültig |
| P-OF-12 | Datumsfeld | Diensttag anlegen „07112026“ tippen; Suche von/bis; Geburtsdatum; Firefox und WebKit | Punkte erscheinen; Server speichert ISO; Alter stimmt; kein Kalender; `pattern` ohne Skript |
| P-OF-13 | Ortsfeld-Zeichen | Einsatzformular: Tab bis zum Zeichen, Enter; Hover; Adresssuche aus | Satz erscheint; Screenreader liest ihn als Beschreibung; aus → kein Zeichen |
| P-OF-14 | Filterknopf | Protokoll: zwei Filter setzen, Seite neu laden; ohne Skript | Knopf „Filter 2“, Pillen mit Kreuz, „18 von 2.580“; ohne Skript Filterreihe offen |
| P-OF-15 | Zweitfaktor | Profil mit und ohne Zweitfaktor, mit Passkey | ein Satz mit allem Wichtigen; „Verwalten“ klappt vier Zeilen auf, Plakette und Knopf auf einer Linie |
| P-OF-16 | Koppel-Dialog | Geräte: „Weitere Uhr koppeln“, Code eingeben; 390 px | drei Schritte je Typ, Dialog rollt in sich, `kopplungsprobe` grün |
| P-OF-17 | Import / Export | Wahlkarte wechseln; Sprungmarken | nur das gewählte Formular; Marken folgen |
| P-OF-18 | Backup-Seite | Erklärung; Backup-Seite mit und ohne gescheiterten Lauf | ein Satz; Karte „verdichten“ nur bei Bedarf, sonst Zeile unter „Was dein Konto hält“ |
| P-OF-19 | Kontenliste | 1024 und 1920, Tab-Taste durch die Zeilen | kein waagerechtes Rollen; jeder Name ist ein fokussierbarer Verweis |
| P-OF-20 | Demo-Konto | Installation: Schalter aus; Demo-Anmeldung versuchen; Status | Anmeldung gesperrt, Konto bleibt, Warnung weg; Servereinstellungen ohne Schalter; Kennzahlen auf Status |
| P-OF-21 | Status | mit und ohne Befunde | nur nicht-grüne Zeilen oben, Bereiche zugeklappt mit Zahl und Plakette; ein Wort für grün |
| P-OF-22 | Hintergrundjobs | pausieren 15 Min, zurück | Plakette „pausiert bis …“, Begriff „Anhalten“ nirgends mehr (`grep`), Protokoll sagt „pausiert“ |
| P-OF-23 | Servereinstellungen | jede Karte zu/auf, Stand gegen Formular | Stand nennt die Werte des Formulars; Speichern öffnet die Karte mit Meldung |
| P-OF-24 | Komplett-Backup | „Jetzt sichern“, Stände | eine orange Hauptaktion; Stand oben; `komplettprobe` grün |
| P-OF-25 | Backup-Ziele | Servereinstellungen und Komplett-Backup; `admin_sicherungsziele.php` aufrufen | dieselbe Karte an beiden Orten, „Ziel anlegen“ öffnet den Dialog; Aufruf leitet um; `versandprobe` grün |
| P-OF-26 | Ruhesegmente | Tagesübersicht, Aktionsmenü | keine Kachel; „Einsatz aus GPS-Daten ausschneiden“ führt in den Schneide-Bereich |
| P-OF-27 | GPX | Tagesübersicht, Einsatz, Spurenseite | `herunterladen`/`hochladen`; keine Plakette „ausgedünnt“; `gpxprobe` grün |
| P-OF-28 | Einsatz und Transport | nachtragen und bearbeiten, Ambulant wählen, Katalog lesen | eine Karte; bei Ambulant kein Ziel, keine Schalter; Gruppe steht im Katalog, kein Markup je Feld |
| P-OF-29 | Schlösser | Einsatzansicht 1920 und 390; `kennzeichnung` | Schloss links in eigener Spalte, Werte fluchten; Riegel 26 von 26 |
| P-OF-30 | Karte | Ebenenwahl öffnen, jede Ebene | App-Knöpfe 44/36; Namen ohne Anbieter; Zuschreibung nennt den Anbieter je Ebene |
| P-OF-31 | Papierkorb | Diensttag löschen, Papierkorb | `wiederherstellen`-Zeichen; Artzeichen vor dem Datum; kein Kreis neben den Knöpfen |
| P-OF-32 | Darstellung | Profil: jede der vier Einstellungen; Umschalter klicken; abmelden, anmelden | Umschalter nur bei Umschalter und Folgt System; Klick wirkt sofort ohne Aufblitzen; nach Abmeldung Vorgabe |
| P-OF-33 | Darstellung ohne Migration | Staging nach dem Deploy: Konto auf „Folgt System“ | gespeichert, kein `update.php` nötig; Status meldet keine offene Migration |
| P-OF-34 | Barrierefreiheit | `tools/barrierefreiheit/` drei Engines | 0 Befunde, auch mit offenem Koppel-Dialog und aufgeklappter Klappkarte |

## 9. Prüfprotokoll

### OF-00 (Konzept, 07.11.2026)

**Was getan ist.** Konzept geschrieben; `Konzept-NV-Navigation.md` und
`Vorbereitung-OF-Oberflaechenrunde.md` aufgegangen und gelöscht; die sechs
NV-Mockups nach `konzept-of/mockups/` verschoben; Fahrplanzeile 27 und
Reihenfolge 27 → 13 → 14 im Rahmenplan (Fassung 228), Spanne 530–539 im
Backlog. Kein Code.

**Gemessen.** Zweig enthält `origin/main` `ac4a7bd` (`git merge-base
--is-ancestor`); 62 `hilfe.php#`-Verweise in 25 Dateien, 49 „Handbuch:“,
17 Datumsstellen in 8 Dateien (`grep`, 07.11.2026); `users.darstellung
VARCHAR(10)` (`migration_lib.php`); Demo-Schalter `KONTEN_K_DEMO_ANMELDUNG`
in `betrieb_server.php`; `#freigabebox hidden` in `einstellungen.php`,
`.karte[id]` ohne Sichtbarkeitsprüfung in `menue.js`. Riegel nach dem
Schreiben: `decken.py` **21 Decken, 0 gerissen** (im ersten Lauf 1: die
Fahrplanzelle „Inhalt“ von 27 hatte über 300 Zeichen, gekürzt auf 243);
`uebersicht.py --pruefen` **34 offene Einträge, 0 ohne Grammatik, 0 ohne
Ziel**; `dokumente` **0 Befunde** (3 768 Pfadverweise in 53 Dokumenten);
`bestand` **0 Befunde** in 13 Regeln; `textprobe` **0 Treffer außerhalb der
Ausnahmen, 0 ungenutzte Ausnahmen**.

**Nicht geprüft:** nichts im Browser — es gibt noch keinen Code. Die
Prüfpunkte in Abschnitt 8 nennen je Paket, was dann zu messen ist.

### NV-01 Bereichszeile (08.11.2026, Web 23.12.0)

**Was getan ist.** `ui_reiter()` nimmt `klasse` (Lage am Rahmen) und je
Punkt `zahl` und `zaehler`; das Skript `reiter.js` lädt einmal je Seite
(vorher je Reihe — mit zwei Reihen hätte jede zwei Horcher bekommen).
`ui_bereichszeile()` baut die Punkte aus `ui_einstellungen_punkte()`:
Ziel = erster Punkt des Blocks (E-OF-51), Zahl der Punkte, Zähler als Summe
(rot, sobald einer rot ist; E-OF-14). `ui_geruest_start()` ruft sie nach
`ui_kopf()` nur bei `leiste === 'einstellungen'` und setzt
`.rahmen-bereiche`. Stylesheet: `--bereiche` (48 px), `--strich-reiter`
(E-OF-58), `.reiter-punkt` 700 / 48 px / Unterstrich 3 px / Hover
`--flaeche-abgesetzt` für alle Reiter (E-OF-17), `.reiter-bereiche` als
Lage, ab 1024 px `sticky` samt Leiste und `scroll-padding-top` (E-OF-57).
Design.md 9.37 nachgezogen, Tabellen mit `design.py schreiben` erzeugt;
Handbuch 2 (Bereichszeile); CHANGELOG; `version.php` 23.12.0 (E-OF-59).

**Gemessen** (Chromium, eigenes Aufnahmeskript gegen die örtliche Anlage,
Konto BetreiberIn): Bereichszeile bei 1920 neben Protokoll-Reitern —
gleiche Form (Bild gegen `OF-P20-A`); bei **390 px `scrollWidth` 390 =
`clientWidth` 390**, drei Reiter ohne Rollen (vorher 1. Versuch: rollte,
daher E-OF-57 enger); Höhe jedes Reiters **48 px**. Servereinstellungen
1366 × 700, 900 px gerollt, **in Chromium, Firefox und WebKit gleich**:
Zeile oben bei 56 px, 49 px hoch (48 + Linie), Leiste von 105 bis 700 px
(= Fensterboden). Demo-Konto: ein Reiter „Einstellungen 5“ (F-OF-19).

**Probleme und Lösung.** (1) Der Stilvergleich meldete in allen drei
Engines 18 ungeplante Signaturen — die neue Reiterform, die Lage der
Bereichszeile und die längere Katalogseite (Höhen, statische Lage
absolut gesetzter Elemente); alle gewollt, mit `gegen.sh --schreiben`
als `geplant-<motor>.txt` abgelegt (je 18). (2) Der erste vollständige
Prüfstand (7 401 s) war **57 grün, 2 rot**: Bedienprobe (drei Engines,
Weg `admin-protokoll-reiter`) und Rollenprobe („Protokoll: genau zwei
Reiter“: 4). Beide suchten `.reiter-punkt` seitenweit und fanden die
Bereichszeile mit — ein Fehler der Prüfmittel, nicht der Seite. Die Wege
in `tools/bedienprobe/wege/` (Protokoll, Statistik, Rechtstexte) suchen
jetzt unter `#inhalt`, die Rollenprobe zählt nur in der Reihe „Bereiche
des Protokolls“. Einzeln nachgefahren: Rollenprobe 4 von 4 Rollen,
Bedienprobe (Chromium) 77 von 77 Wegen. (3) Ein Neustart des Containers
brach den zweiten Lauf nach rund 20 Minuten ab (`Exit 137`, `uptime`
0 min) — Startfolge nach `Sandbox-Setup.md` 6.1a neu gefahren, Anlage
mit `--neu`. (4) Die örtliche Kopie von `origin/main` stand auf PR #149
(`a1521a6`); `auswahl.py` hielt den Unterschied deshalb für `haupt`
(Migration aus Schritt 26). Neu geholt: `neben`, 23.11.3 → 23.12.0.
Prüfstand über den endgültigen Baum: Bericht im Commit.

**Nicht geprüft:** P-NV-07 (Kontraste) läuft mit `kontrast.py` im
Prüfstand; P-NV-01 (Raster der Kanten) wird erst mit der Rahmenkarte
(NV-02) messbar.

### NV-02 Rahmenkarte (08.11.2026, Web 24.0.0)

**Was getan ist.** `ui_geruest_start()` öffnet für `diensttage`,
`einstellungen` und `filter` eine `.rahmenkarte` (bei Einstellungen mit
`.rahmenkarte-abschnitte`) und darin `.leiste-spalte` um die Leiste;
`ui_leiste_ende()` und `ui_geruest_ende()` schließen sie, gesteuert über
`ui_hat_rahmenkarte()`. Ohne Leiste (404 von `apk.php`) keine Karte.
Stylesheet: Rahmenkarte ab 1024 px (Rand, `--radius-gross`, `overflow:
clip`), `.rahmen` mit Karte `padding: --abstand-4 --abstand-3`; Spalte mit
Fläche und Linie; Leiste klebt, `height: min(Fenster unter den Leisten,
100 %)`. Abschnitte: Karte ohne Rand und Fläche, Linie oben, Kopf und Inhalt
ohne Einzug (E-OF-61). Fußzeile dunkelblau für alle Seiten, Kante im
Dunkelmodus wie die Kopfleiste, im Druck ausgeblendet. Design.md 10.1 und
Änderungsverlauf, Tabellen erzeugt; Handbuch 2; CHANGELOG;
`version.php` 24.0.0 mit Begründung der Hauptnummer (E-OF-59).

**Gemessen.** Bilder (Chromium): Profil 1920 ganze Seite gegen `NV-01`,
Status 1920 gegen `NV-03`, Startseite 1366 × 700 600 px gerollt gegen
`OF-E2` (Leiste klebt, „Diensttag anlegen“ und „Papierkorb“ sichtbar),
Suche gegen `OF-E3`. Lagen in **Chromium, Firefox und WebKit gleich**:
kurze Seite (Updates, 1920 × 1080) Karte 121–902, Leiste 122–901, Fuß
848–901 = Kartenboden, Fußzeile ab 918; Übersicht ebenso (WebKit 50 px
höher, Inhalt); lange Seite (NutzerInnen, 1366 × 700, 400 px gerollt)
Leiste 12–607 an der Unterkante der Karte (608). 390 px: `scrollWidth`
390, Rahmenkarte `display: contents`. **Riegel:** 24 von 24 grün.
**Stilvergleich:** 95 / 96 / 101 Signaturen (Chromium / Firefox / WebKit,
kumuliert seit `main`), durchgesehen — Fußzeile, Rahmenkarte, Abschnitte,
Leistenspalte; dazu Breiten in `seiten.html[ui.php]` (die Quelltextprobe
liest `ui.php` als Markup, LIESMICH des Werkzeugs) —, als geplant
abgelegt; danach 0 ungeplant in allen dreien. **Kontrast:** 60 Paare je
Modus, 0 verfehlt, 40 abgeleitet, 0 ohne Eintrag. **Bilderlauf**
(Chromium, drei Breiten, alle Seiten): 258 Bilder, Überlauf 0,
Konsolenfehler 0, Knöpfe falscher Höhe 0, 211 Karten / 0 außerhalb.
**Barrierefreiheit** (Chromium): 74 Seitenaufrufe, 0 Befunde.
**Bedienprobe** (Chromium): 77 von 77 Wegen. **Rollenprobe:** 4 von 4.

**Nicht geprüft:** Bilderlauf, Barrierefreiheit und Bedienprobe in
Firefox und WebKit (E-OF-60: nach NV-04 bzw. im vollen Prüfstand);
Druckvorschau von Hand.

### NV-03 Leiste kürzen (08.11.2026, Web 24.1.0)

**Was getan ist.** `ui_leiste_einstellungen()` zeichnet den aktiven Block
mit `.leiste-gruppe-aktiv` aus (auf der Übersicht „Einstellungen“); ab
1024 px blendet das Stylesheet die übrigen und dessen Kopfzeile aus.
`menue.js` hält den aktiven Block offen, auch ohne aktiven Eintrag; die
Sprungmarken entstehen nur aus Karten ohne `[hidden]`-Vorfahren und ziehen
über einen Beobachter nach (E-OF-62); der Saum der Markierung rechnet die
Bereichszeile mit. Strich weg an `.eintrag.aktiv`, `.akkordeon.aktiv` und
`.vorschlag` (Vorschlag auf Blau 100); „Abmelden“ aus der Übersicht
entfernt (E-OF-27). Design.md 9.25, 9.28, 3.4 (zwei Beschreibungen,
gleichlautend in `kontrast.py`), Verlauf; Handbuch 2; CHANGELOG;
`version.php` 24.1.0.

**Gemessen** (Prüfweise E-OF-60). Bilder Chromium 1920: Profil gegen
`NV-01` (nur der aktive Bereich, kein Strich, Marken darunter), Übersicht
gegen `OF-P29-C` (kein „Abmelden“ im Inhalt). Backup-Seite: Marken
„Backup erstellen | Backup einspielen | GPS-Daten verdichten“ — die
verborgene Freigabe-Karte fehlt; Box eingeblendet → **4 Marken**, wieder
ausgeblendet → **3**, **gleich in Chromium, Firefox und WebKit** (P-OF-05).
Status bei 390 px: drei Kopfzeilen in der Schublade (`flex`); bei 1366 px
`none, none, block`. Riegel **24 von 24** (`bestand` erst nach dem
Erzeugen der Tabellen grün — die Reihenfolge war falsch, nicht der Stand).
Stilvergleich 105 / 106 / 111 Signaturen, die neuen durchgesehen
(ausgeblendete Blöcke, Strich, Vorschlagsfläche, dazu die
Quelltextprobe von `ui.php` unter dem neuen Klassennamen), 0 ungeplant.
Kontrast 60 Paare je Modus, 0 verfehlt, 39 abgeleitet (eins weniger: der
Strich am Vorschlag), 0 ohne Eintrag.

**Nicht geprüft:** Bilderlauf, Barrierefreiheit, Bedienprobe (nach NV-04,
E-OF-60); die Durchsicht der übrigen Seiten mit bedingten Karten von Hand
(E-OF-62 macht sie überflüssig, belegt ist nur die Backup-Seite).

### NV-04 Schmale Breiten (08.11.2026, ohne neue Fassung)

**Was getan ist.** Nichts am Code (E-OF-63) — nachgemessen, dass das
Gerüst aus NV-01 bis NV-03 die schmalen Breiten schon trägt.

**Gemessen** (Chromium, örtliche Anlage). 390 × 844, Profil: Seitentitel
und erstes Feld bei **x = 12**, Feld bis 378, `scrollWidth` 390 — wie
`NV-05`. Übersicht 390: drei Bereichskarten untereinander, kein
„Abmelden“ im Inhalt (P-OF-06). Startseite 390 gegen `OF-E1-2-karten-390`:
Demo-Hinweis, Titel, Karten mit Rand ohne Rahmenkarte, gleiche Lage.
Bereichszeile bei 390: `scrollWidth` = `clientWidth` (NV-01). Schublade
bei 390: drei Bereiche (NV-03). **Nach dem Gerüst der schwere Lauf in
Chromium (E-OF-60):** Bilderlauf **fünf Breiten** (390, 768, 1024, 1200,
1600), 86 Seiten, **430 Bilder, Überlauf 0, Konsolenfehler 0, Knöpfe
falscher Höhe 0**, 201 Karten / 0 außerhalb `main.inhalt` (in NV-02 mit
drei Breiten 211 — die Zahl hängt am Breitensatz; warum, ist nicht
untersucht), 0 gleiche Bilder über Breiten; **Barrierefreiheit** 74
Seitenaufrufe, **0 Befunde**; **Bedienprobe 77 von 77**.

**Nicht geprüft:** dieselben Läufe in Firefox und WebKit (voller
Prüfstand vor dem Pull Request); 768 px und 1024 px nur im Bilderlauf,
nicht im Bild durchgesehen.

### OF-01 Regeln für alle Seiten (08.11.2026, Web 24.2.0)

**Was getan ist.** (1) **Eine rechte Kante:** `--listensuche-breit` heißt
`--feld-breit`; in `.rahmenkarte-abschnitte` sind `.feld-eingabe` und
`.wahlliste` auf 36 rem begrenzt, `.form-raster-einspaltig` ohne Deckel;
die Servereinstellungen ohne `lesespalte`. (2) **Speicherleiste:**
`.speichern` als Auswahlfläche ohne Linie, Bogen, Schatten; direkt im
Inhalt oder seinem Formular bündig (negativer Rand); `.speichern-hinweis`
fett in Auswahlschrift links; „Geändert“ als Vorgabe (E-OF-67).
(3) **Erklärtext ohne Verweis:** 62 Verweise in 25 Dateien maschinell und
von Hand geräumt (vier Titelzeilen und Sätze mit Verweis im Satz von
Hand), dazu „Anleitung im Handbuch.“ der Erststart-Karte; ausgenommen
E-OF-64. Das „?“ öffnet das Kapitel: `ui_hilfe_marke()` (19 Menüpunkte),
`ui_kopf(['hilfe'])`, `ui_geruest_start(['hilfe'])` auf zehn Seiten
(E-OF-66). Design.md 6 neu gefasst (E-P5c-06 aufgehoben), 9.0, 10.2.
(4) **Drei Messungen** im Bilderlauf (`seitenregelnMessen()`), Selbstprobe
um die Fälle 25–32 im Browser, bekannte Verstöße in `ausnahmen.json`
(E-OF-65); LIESMICH (Anlass Nr. 530, Sollzahl); Nr. 530 nach
`Backlog-Erledigt.md`. Handbuch 2 (das „?“, die Leiste), CHANGELOG,
`version.php` 24.2.0.

**Gemessen.** Rechte Kante (Chromium 1920): Installation,
Servereinstellungen, NutzerInnen — alle Karten enden bei 1771 px =
Innenkante von `.inhalt`; Felder höchstens 576 px; Rechtstexte zweispaltig,
rechte Spalte an derselben Kante. Speicherleiste (Rechtstexte 1366 × 700,
eine Eingabe): sichtbar, „Geändert: Impressum“, 0 px Abstand links und
rechts zu `.inhalt`, Unterkante 700 = Fensterboden, Fläche
`rgb(217, 236, 253)` (Blau 100) — gegen `OF-P07`. `anker`: **34 Verweise
auf 109 Marken, 0 ohne Ziel** (die 19 + 10 neuen Kapitelmarken
eingeschlossen). `grep "Handbuch:"` in `server/`: nur Schlüsselblatt
(ausgenommen) und ein Kommentar. **Bilderlauf** (Chromium, drei Breiten):
258 Bilder, Überlauf 0, Konsolenfehler 0, Knöpfe 0; **Seitenregeln: 187
Karten auf Sätze, 78 Aufnahmen auf die Kante, 0 Befunde, 31 ausgenommen**,
rc 0. Erster Lauf ohne Ausnahmen: 31 Befunde (30 + Servereinstellungen
„Anmeldung“ im zweiten Lauf), davon 0 an der rechten Kante.
**Selbstprobe** `aufnehmen.mjs` **32 von 32** (Knopf zwischen /
im Abschnitt, Lesespalte / volle Breite, zwei Sätze / einer mit
Abkürzung und Fassungsnummer / Meldung / Rest ab vier Wörtern), `vergleichen.py`
14 von 14. Riegel 24 von 24 (`wortliste` zuerst rot: „Patient“ im
Handbuch-Beispiel, ersetzt). Stilvergleich 116 / 117 / 122, die neuen 11
durchgesehen (Felder, Einspalter, Speicherleiste), 0 ungeplant. Kontrast
60 / 0.

**Probleme.** Der erste Bilderlauf fand den Satz-Befund der
Servereinstellungen nicht — die Messung lief noch in der großen
Auswertung und fiel mit ihr aus; seit sie eine eigene Funktion mit eigenem
`evaluate` ist, kommt sie an.

**Nicht geprüft:** Bilderlauf, Barrierefreiheit, Bedienprobe in Firefox
und WebKit; die Speicherleiste des Einsatzformulars im Bild (gemessen nur
an den Rechtstexten); das „?“ je Seite von Hand (belegt über `anker`).

### OF-02 Bausteine I (08.11.2026, Web 24.3.0)

**Was getan ist.** Klappkarte: `stand` (statt `vorschau`, neun Verwender,
E-OF-68) und `plakette` im `<summary>`, `.karte-stand`; Gerüstskript
`assets/klappkarte.js`. Segmentwahl: Option `abwaehlbar` mit verborgenem
`.segment-leer` und `assets/segment.js` (einmal je Seite);
`segment-leer` in `vollstaendigkeit-ohne-regel.md` als Anker. Zeile:
`align-items: center` an `.zeile-plaketten` und `.zeile-aktionen`,
Einsatzkarten-Abstand nur am direkten Kind (F-OF-22). Kartenkopf mit
Plakette: bricht schon um (`flex-wrap`, Plakette `nowrap`) — nichts zu
tun. Sechs Zeichen aus `@tabler/icons` 3.31.0 (`arrow-left` darin
byte-gleich mit `zurueck.svg`), LIESMICH des Vorrats (64), Lizenzen.md
5 und Verlauf. Design.md 9.1, 9.2, 9.7, **9.9 (in OF-01 vergessen, hier
nachgezogen)**, Verlauf; Tabellen erzeugt. CHANGELOG, `version.php`
24.3.0.

**Gemessen.** Segmenttasten: 41 Tasten auf sechs Seiten in drei Engines,
Textmitte gegen Tastenmitte **0 Abweichungen ≥ 1 px** (F-OF-21).
Abwählen an einer aus `ui_segment_markup()` erzeugten Gruppe, Wert über
`FormData`: Start „“ → Boden „boden“ → Boden „“ → Luft „luft“ →
Leertaste „“ → Leertaste „luft“, **in Chromium, Firefox und WebKit
gleich** (erster Versuch: Chromium und WebKit blieben bei der Leertaste
auf „luft“, E-OF-68). Plakette neben Knopf, Konto-Backups 1920: vorher
**6 px** unter der Mitte (3 Zeilen), nachher **0** in drei Engines.
Klappkarte: Status mit `#k-konfig` geladen → offen; zugeklappt und Anker
neu gesetzt → offen, Oberkante 120 px; drei Engines gleich. Riegel **24
von 24** (`vollstaendigkeit` zuerst rot: `segment-leer` ohne Regel,
eingetragen). Stilvergleich 131 / 132 / 137, die neuen 17 durchgesehen
(`align-items`, `.karte-stand`, Segment-Markup in der Quelltextprobe von
`ui.php`; Einsatzkarte: nur `align-items`, der Abstand bleibt), 0
ungeplant. Kontrast 60 / 0.

**Nicht geprüft:** eine Klappkarte mit Plakette im Bild (erster Verwender
OF-06); die abwählbare Segmentwahl in einer echten Seite (erster Verwender
OF-07, P-OF-11); Bilderlauf, Barrierefreiheit und Bedienprobe (E-OF-60).

### OF-03 Bausteine II — Eingabe (08.11.2026, Web 24.4.0)

**Was getan ist.** Datumsfeld: `ui_datum_eingabe()` (Textfeld,
`inputmode=numeric`, `pattern`, `data-min`/`data-max`), `ui_feld(['art' =>
'date'])` nutzt es; `assets/datumsfeld.js` (delegiert; Punkte beim Tippen,
Auffüllen, ISO-Einfügen, Markierung); `--feld-datum`; Umrechnung nach
E-OF-69, Registerzeile **Z50**. Die acht Stellen: Diensttag anlegen,
Datum ändern, Ankündigung „Sichtbar bis“, Rechtstext-Stand (samt Vorschau
im Browser), Suche von/bis (Art `datum`), Export von/bis, Geburtsdatum
(Anzeige, Alter, Zweistellig-Regel jetzt auf TT.MM.JJ beim Verlassen,
Verschließen als ISO), Zeitraumwahl der Statistik (`statistik_lib.php`
liest beide Formen, `listenkopf.js` schickt erst bei zwei gültigen Tagen).
Ortsfeld: `ui_geocoder_hinweis()` gibt Knopf und Tooltip zurück, beide
Fassungen von `ui_ortsfeld()` setzen ihn neben die Lupe; Verhalten in
`ortsfeld.js`. Listenkopf: Filterknopf, `.filtergesetzt`, `vorgabe`, `weg`,
`treffer`, `auswahl.kurz`/`weg`; Verwender NutzerInnen und Protokoll;
`listenkopf.js` mit Ladesperre. Nebenbei: `.listen-form-felder` mit
`minmax(0,1fr)` (siehe Probleme). Prüfmittel: Bilderlauf
(`suchzwilling` für den Filterknopf), Bedienprobe (Statistik tippt
TT.MM.JJJJ; Protokoll öffnet den Filterknopf; ap2 und ap5 zählen sichtbare
Meldungen bzw. holen das Zeichen ins Bild); `vollstaendigkeit-ohne-regel`
um `ortsfeld-hinweis-knopf`. Doku: Design.md 9.7, 9.13, 9.18a, 9.18b,
Verlauf; Handbuch (Zeiten/Daten, Ortsfeld, NutzerInnen, Protokoll);
Technik (Baum, Zentralisierungstabelle); CHANGELOG; `version.php` 24.4.0.

**Gemessen.** Datumsfeld auf „Diensttag anlegen“, **drei Engines gleich**:
„07112026“ → „07.11.2026“; „7.1.2026“ → beim Verlassen „07.01.2026“;
„31022026“ → rot, „Bitte ein Datum als TT.MM.JJJJ eintragen.“; „2026-03-01“
eingefügt → „01.03.2026“; Breite 120–125 px, `type=text`. Ende zu Ende:
„05102026“ getippt, abgeschickt → `index.php?d=…`, Titel „Montag,
05.10.2026“. PHP: `datum_de_lesen()` auf 07.11.2026 / 7.1.2026 /
2026-11-07 / 31.02.2026 / leer / 07112026 → ISO, ISO, ISO, null, null,
null. Z50: Muster trifft `'d.m.Y'` und `"!d.m.Y"`, nicht `'!Y-m-d'`; 50
Registerzeilen, 0 über der Decke. Ortsfeld (Einsatzformular, drei
Engines): ein Zeichen, kein Satz mehr in der Seite, verborgen zu Beginn,
Fokus zeigt, Esc schließt, Darüberfahren zeigt, Beschreibung am Knopf
beginnt mit „Vorschläge und Umkehrsuche kommen von ph…“; Tooltip 482 px
breit ab der Feldkante (erster Versuch 576 px ab x = 169, also über der
Leiste — an der Zeile ausgerichtet). Filterknopf (Protokoll `z=7`,
NutzerInnen `f=nie`): sichtbar, 48 px, Reihe zu, „Filter 1“, Pillen „7
Tage“ bzw. „nie Konto-Backup“ + „4 von 4“; Klick auf/zu mit
`aria-expanded`, **ohne Skript Knopf verborgen und Reihe offen — drei
Engines gleich**. Bild gegen `OF-P22-B`. Riegel **24 von 24**
(`vollstaendigkeit` zuerst rot: `ortsfeld-hinweis-knopf`). Stilvergleich
146 / 146 / 153, die neuen durchgesehen, 0 ungeplant. Kontrast 60 / 0.
**Bilderlauf** (Chromium, drei Breiten): 258 Bilder, Überlauf 0,
Konsolenfehler 0, Knöpfe 0, Seitenregeln 0 Befunde / 31 ausgenommen.
**Bedienprobe** (Chromium): 72 von 77 im ganzen Lauf, die 5 übrigen
danach allein mit gesetzter Demo-Marke grün — zusammen 77 von 77, aber
aus **zwei** Läufen.

**Probleme.** (1) Der erste Bilderlauf meldete **21 Knöpfe falscher
Höhe** — meine Erweiterung `suchzwilling` griff mit `.listensuche` auch den
verborgenen Suchknopf; eingegrenzt auf `.listensuche .filterknopf`. (2)
**Überlauf 8 px bei 360 px** auf der Standortseite: Das neue Zeichen machte
die Ortsfeldzeile breiter, und die automatische Rasterspalte der
Formularfelder folgte ihr (336 → 356 px); behoben mit `minmax(0,1fr)` —
danach 360 / 390 / 768 ohne Überlauf, Stilvergleich nur die
Spaltenangabe auf zwei Seiten. (3) Bedienprobe: `ap2-hinweis-am-ortsfeld`
holte den jetzt verborgenen Satz ins Bild (Zeitüberschreitung),
`ap5-dialog-anlegen…` zählte die verborgene Tooltip-Meldung als Meldung am
Seitenkopf — beide auf das Sichtbare umgestellt. (4) Fünf Wege des ganzen
Laufs fielen einem **Demo-Reset mitten im Lauf** zum Opfer (der
Prüfstand schiebt die Demo-Marke, mein Handlauf nicht); allein mit Marke
grün. (5) Viele Anmeldungen in kurzer Zeit sperrten das Demo-Konto
(Ratenschutz, wie vorgesehen); weitergeprüft mit dem Konto der
BetreiberIn, Anlage danach mit `--neu`.

**Nicht geprüft:** das Geburtsdatum Ende zu Ende im Einsatzformular
(Verschließen und Öffnen); die Ankündigung „Sichtbar bis“ und der
Rechtstext-Stand im Bild; Bilderlauf, Barrierefreiheit und Bedienprobe in
Firefox und WebKit (E-OF-60).

### OF-04 Einstellungen (08.11.2026, Web 24.5.0)

**Was getan ist.** Profil: Zweitfaktor als ein Satz plus „Verwalten“ mit
den Zeilen Codes, Gemerkte Geräte, Passkeys, Rückweg (E-OF-70 (1), (2));
Einleitungssatz nur ohne Zweitfaktor; Profil-Formular mit Speicherleiste
(`data-fuer`, E-OF-70 (4)), „Passwort ändern“ als Knopf in der Karte; Sätze
gekürzt (Rolle, Logo, Darstellung, Datenschutz, Wiederherstellungsschlüssel,
„Was hilft“); „Konto löschen“ als Warnmeldung plus ein Satz; Zeile „GPS-Daten
verdichten“ unter „Was dein Konto hält“. Geräte: zwei Karten nach Typ
(E-OF-70 (3)), Koppeln im Dialog mit drei Schritten, App-Fach als Schritt 1,
App-Karte entfällt, Platzzahl als Kleinzeile, Meldung nur bei vollem Platz.
Import / Export: Baustein Wahlkarten (Design.md 9.42, `wahlkarten.js`).
Backup: ein Satz, Karte „GPS-Daten verdichten“ nur bei Fälligem (E-OF-52 —
ob sie ganz entfällt, entscheidet OF-M1). Standorte und Standortseite:
Sätze gekürzt. Prüfmittel: Bilderlauf (Kopplungszustand im Dialog,
Leerzustand-Regel, Selbstprobe 33 von 33), Kopplungs-Rundlauf, Bedienprobe
(Passkey, Rückweg, Zweitfaktor-merken lesen die Kleinzeile bzw. öffnen
„Verwalten“), Ortsdaten-Probe, Textprobe-Ausnahmen (Garmin-Stellen nach
Typ), `vollstaendigkeit-ohne-regel` (`zf-verwalten`, `zf-satz`), neun
Seiten aus `ausnahmen.json` gestrichen, dazu `44c`. Doku: Handbuch 3.1f, 6,
7, 10; Design.md 9.9, 9.42, Verlauf; Technik (Baum); CHANGELOG;
`version.php` 24.5.0.

**Gemessen** (Prüfweise E-OF-60). Riegel **24 von 24**. Kontrast **60
Paare je Modus, 0 verfehlt**. Stilvergleich gegen `origin/main`: 66 638
Elementmessungen, geplant und gemessen **173 / 173 / 180** Signaturen
(Chromium / Firefox / WebKit), **0 ungeplant**, die neuen durchgesehen.
**Bilderlauf** Chromium, drei Breiten, voller Lauf: Überlauf 0,
Konsolenfehler 0, Knöpfe 0, Seitenregeln 1 Befund (die Ausnahme `44c` ohne
Befund, gestrichen); danach die zehn berührten Seiten nachgefahren: 30
Bilder, Überlauf 0, Konsolenfehler 0, Knöpfe 0, Seitenregeln 39 Karten /
20 Kanten, **0 Befunde, 0 ausgenommen**. **Bedienprobe** Chromium 74 von
77, die drei roten (siehe Probleme) danach allein **3 von 3**. Proben:
Zweitfaktor **139 ok, 0 fehlen**; Rollen **612 Erwartungen, 0 nicht
erfüllt**; Ortsdaten **58 ok, 0 fehlen**; Kopplung **85 / 0**,
Kopplungs-Rundlauf **25 / 0**, 0 Konsolenfehler.

**Probleme.** (1) Drei Bedienwege lasen die Zahlen als Plakette und
rechneten mit offenem „Verwalten“ — der Rückweg-Weg klickte 30 s auf einen
verborgenen Knopf; umgestellt auf die Kleinzeile bzw. das Öffnen des
`<summary>`, wie eine Nutzerin es tut. (2) Die Ortsdaten-Probe fand das
Exportformular verborgen: Sie kam von `import.php` und sprang auf
`#exportform`, ohne dass die Seite neu lud — dasselbe hätte eine Nutzerin
mit der Sprungmarke erlebt; `wahlkarten.js` hört jetzt auf `hashchange`
(E-OF-70 (5)). (3) Das Ausschalten des Zweitfaktors hing zuerst am
Codes-Formular und hätte zwei `action`-Felder gesendet; eigenes Formular.
(4) Die Wortliste stieß sich an den neuen Garmin-Stellen; die zwei alten
Ausnahmen sind durch zwei neue ersetzt, die die Geräte nach Typ nennen.

**Nicht geprüft:** Bilderlauf, Barrierefreiheit und Bedienprobe in Firefox
und WebKit (E-OF-60, kommt mit dem vollen Prüfstand); der Koppel-Dialog mit
echter Garmin-Uhr und echtem Handy (Handprüfung); die Karte „GPS-Daten
verdichten“ mit einem gescheiterten Lauf im Bild.

### OF-05 Verwaltung (08.11.2026, Web 24.6.0)

**Was getan ist.** Kontenliste (`admin_users.php`): Name als Verweis mit
Vorlesetext „Konto … öffnen“, ohne Namen trägt die Adresse ihn; Spalte
„Öffnen“ und `.oeffnen` fort; Spaltentitel und Adresse brechen um. Demo-Konto:
Karte `#k-demo` auf `admin_installation.php` mit Kopfaktion, Zeilen Konto
(„Entfernen“), letzter und nächster Reset, Schalter mit eigenem Formular,
Warnung nur bei zugelassener Anmeldung, Bericht als Klappkarte; die
Handlungen samt Fehlerbehandlung aus `admin_demo.php` übernommen.
`admin_demo.php` nur lesend (E-OF-71); Menüpunkt und Hilfe-Zuordnung fort;
Verweise der Kontoseite zeigen auf `#k-demo`. Servereinstellungen: Schalter
fort, Rückfrage bleibt (E-OF-71 (2)). Kontoseite: Leerzustand „Noch kein
Konto-Backup …“ (Seitenregel). Prüfmittel: `demo_pruefen.mjs`
(Knöpfe unter Installation), `ausnahmen.json` (`41-kontoseite`), Stilplan.
Doku: Handbuch 3.2, 11 (Tabelle), 11.5, 11.6, 12.5; Technik (Baum,
Rollenmatrix: fünf Zeilen, eine neu, Seitenliste); Backlog Nr. 342 auf
Suche und Zeitraum gekürzt; CHANGELOG; `version.php` 24.6.0.

**Gemessen.** Riegel **24 von 24**. Kontrast **60 / 0**. Stilvergleich:
**8 neue Signaturen** je Engine (Umbruch in der Kontenliste: `th.sortable`,
`a`, `span.arrow` — `white-space`, `text-wrap`; `konto-name`,
`konto-mail`, `nur-vorlesen` — `overflow-wrap`), durchgesehen, geplant;
danach 181 / 181 / 188. Kontenliste rollt bei 1024 und 1440 px um **0 px**
(BetreiberIn und Support; vorher 81 / 24, nach dem ersten Umbau 43).
Bilderlauf Chromium, drei Breiten, sieben Seiten (NutzerInnen ×3,
Kontoseite, Installation, Demo-Konto, Server): 21 Bilder, Überlauf 0,
Konsole 0, Knöpfe 0, Seitenregeln 25 Karten / 14 Kanten, **0 Befunde**;
rollend nur die Bereichszeile bei 360 px. `demo_pruefen.mjs`
(anlegen, reset): **16 Einzelprüfungen, keine Befunde**, Anlegen nicht
gemessen (Konto vorhanden, vom Werkzeug so ausgewiesen). Eigener Weg
(Chromium): Schalter aus → Wert aus, Warnung fort, „ruht …“; Servereinstellungen
ohne Schalter, Speichern der Konten-Karte lässt den Wert **an**; Entfernen →
`demo_id` null, Kopfaktion „Demo-Konto anlegen“; Anlegen → Konto da,
Bericht da. Rollenprobe **616 Erwartungen, 0 nicht erfüllt**. Bedienprobe
(Chromium) der Installations-, NutzerInnen- und Serverwege **7 von 7**.

**Probleme.** (1) Der Konzepttext legte Umleitung und Kontrollzahlen in
OF-05, die Statuszeilen aber in OF-06 — gelöst mit E-OF-71. (2) Die
Konten-Karte hätte den Demo-Wert bei jedem Speichern abgeschaltet, weil das
Feld fehlt; der Wert ist aus `$neu` genommen. (3) Nach dem Wegfall der
Spalte rollte die Tabelle bei 1024 px weiter (43 px); Umbruch erlaubt.

**Nicht geprüft:** Bilderlauf und Bedienprobe in Firefox und WebKit
(E-OF-60); das Entfernen mit einem Wurf mittendrin (der Zweig ist aus
`admin_demo.php` übernommen, nicht neu gemessen); eine Kontenliste mit
vielen und langen Adressen (örtlich vier Konten).

