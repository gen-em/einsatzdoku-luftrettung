# Konzept MA — Markenabgleich der Weboberfläche mit dem GenEM-Branding-Repo (Schritt 26)

**Kürzel:** MA. Pakete `MA-NN`, Entscheidungen `E-MA-NN`, Befunde `F-MA-NN`,
Fragen `Q-MA-NN`, Prüfpunkte `P-MA-NN` (`docs/Pruefablauf.md` 7).

**Zweig:** `claude/lucid-knuth-2yu16r` · **Herkunft:** Übergabe aus der
Branding-Sitzung vom 06.11.2026 (Betreiberin und Claude), als Einschub vor
Schritt 13 umgesetzt (Auftrag der Betreiberin, 06.11.2026) ·
**Backlog-Spanne:** 520 bis 529 · **Quelle:** `gen-em/gen-em-branding`,
Commit `95897f3` (die Übergabe nannte `bd5c1ad`; `95897f3` ergänzt nur die
Variante „farbig-negativ" der Produktmarke, die Token-Dateien sind
byte-gleich, nachgemessen mit `cmp`) · **Mockups:** `konzept-ma/mockups/`
· **Prüfdokument:** am Ende nach `docs/konzepte/Pruefliste.md` (E-PU-09).

**Modell:** Opus für alle Pakete (K2); kein Fable-Schritt.
**Fächerung:** keine, in keinem Paket. Der Kern ist eine Datei
(`server/assets/style.css`), die fast jedes Paket anfasst, und der Rest ist
Prüfarbeit an der einen örtlichen Anlage (CLAUDE.md 7).

**Keine Fassungsnummer in diesem Dokument** — die Umsetzung legt sie fest
(K3).

---

## Statusblock

| Paket | Inhalt | Stand |
|---|---|---|
| MA-00 | Konzept, Fahrplanzeile, Backlog-Spanne | **erledigt** 06.11.2026 |
| MA-01 | Token in `:root` auf das Repo umstellen, Flächen tauschen | **erledigt** 07.11.2026 (Web 23.9.0, zusammen mit 03 bis 05) |
| MA-02 | Schriften: 600er raus, 12 px raus | **erledigt** 07.11.2026 (Web 23.9.1) |
| MA-03 | Meldungen, Plaketten, Ampel, Kennzahl: Grün für „in Ordnung" | **erledigt** 07.11.2026 (Code; `Design.md`-Text in MA-08) |
| MA-04 | Knöpfe und Aktivzustände: Aktion Dunkelblau | **erledigt** 07.11.2026 |
| MA-05 | Spurfarben auf die Tonstufen des Repos (neu gefasst, E-MA-11) | **erledigt** 07.11.2026 (Ring im Dark Mode in MA-07) |
| MA-06 | Produktmarke NAdoku: Logos, Favicons, Kopfleiste | **erledigt** 07.11.2026 (Web 23.9.2) |
| MA-07 | Dark Mode mit Schalter je Konto | **erledigt** 07.11.2026 (Web 23.10.0, Migration) |
| MA-09 | Android-Nachzug: Farbwerte und Bildmarken der App (neu, F-MA-12) | **erledigt** 07.11.2026 (Android 0.21.1; Bau im Prüfstand) |
| MA-08 | Dokumentation, Prüfmittel, Prüfstand, Abschluss | **gebaut** 07.11.2026 — Prüfstand und Pull Request; Abschluss nach der Freigabe |
| *Zweite Runde (Auftrag der Betreiberin, 07.11.2026)* | | |
| MA-10 | Nachzug des Repos auf `e7bf4fc`: Token hell und dunkel, Gefahrknopf als Umriss, Warnung immer mit Symbol | **erledigt** 07.11.2026 (Web 23.10.1) |
| MA-11 | Kurzname der Installation zurück in die Kopfleiste (E-MA-21 aufgehoben) | **erledigt** 07.11.2026 (Web 23.10.2) |
| MA-12 | Hauptaktion in Orange, einmal je Seite, auf sechs Seiten | **erledigt** 07.11.2026 (Web 23.11.0) |
| MA-13 | Favicon-Sets aus dem Repo | **erledigt** 07.11.2026 (Web 23.11.1) |
| MA-14 | Barrierefreiheit: die Funde von axe beheben, Abruf beim Verlassen der Seite abbrechen | **erledigt** 07.11.2026 (Web 23.11.2) |
| MA-15 | axe-core als Prüfmittel in drei Engines | **erledigt** 07.11.2026 (nur `tools/`, keine Fassung) |
| MA-16 | Android: Rollen wie im Web, Dark Mode, Hauptaktion „Dienst beginnen" (E-MA-24 aufgehoben) | **erledigt** 07.11.2026 (Android 0.22.0) |
| MA-17 | Garmin: die Zeichen der Produktmarke (Backlog Nr. 520) | offen |
| MA-18 | Dokumentation, Prüfstand zweimal grün, Pull Request nachgezogen | offen |

**In Arbeit:** zweite Runde, MA-17 (Statusblock oben). Erste Runde: MA-08, Prüfstand und Pull Request stehen. Prüfdokument:
`Pruefdokument-MA-Markenabgleich.md` (15 Handpunkte). **Zur Bestätigung
vorgelegt:** E-MA-21 (Kopfleiste fest „NAdoku") und E-MA-24 (Android nur
Werte). **Es hakt:** nirgends; alle Fragen sind entschieden
(E-MA-08 bis -20). MA-01, -03, -04 und -05 sind ein Commit geworden: Sie
stellen dieselben rund 540 Farbstellen im Stylesheet um, und ein Zwischenstand
hätte Token gezeigt, die es nicht mehr gibt.

---

## 1. Anlass und Ziel

Die Oberfläche ist markentreu gebaut — Nines Farben, beide Schriften, selbst
gehostet, Kontraste nachgerechnet, Token-Disziplin in `:root`. Die
Abweichungen entstanden, weil `Design.md` eigene Ableitungen treffen musste
(Tief- und Hellwerte, Farbrollen, Schriftschnitte), bevor es das
Branding-Repo gab. Seit dem 06.11.2026 liegen diese Ableitungen zentral in
`gen-em/gen-em-branding`, von der Betreiberin freigegeben. **Ziel:** Die App
bezieht ihre Werte aus dem Repo statt aus eigener Rechnung, Orange ist keine
Aktionsfarbe mehr, Grün kommt als Statusfarbe dazu, die Produktmarke
„NAdoku" steht in der Kopfleiste, und es gibt einen Dark Mode.

**Es geht überwiegend um Werte, nicht um Formen.** Radien (6/10/12 px),
Abstände, Bedienhöhe 44/36 px, Kopfhöhe, Leistenbreiten, Bausteine und
Seitentypen bleiben. Neues Markup gibt es an vier Stellen: Plakettenton
`gruen`, Meldungston `ok` grün, Kopfleiste mit Produktname, Feld
„Darstellung" im Profil samt `data-theme` am `<html>`.

## 2. Befund

Gelesen am 06.11.2026 auf `origin/main` `7df2818` (Web 23.8.2).

- **F-MA-01 — Die „Zeilenmarken" sind die Spurfarben.** Die Übergabe las
  die farbigen Balken links in der Einsatztabelle als Kategoriecode in
  Gründerfarben und schlug eine Status-Spalte vor (alt MA-05, Q-MA-01). Sie
  sind die Legende der Karte: `--spur-1` bis `--spur-8` in `style.css`,
  dieselben Werte liest `geo.js` über `getComputedStyle` für die Linien.
  Eine Status-Spalte hätte Tabelle und Karte getrennt. Entschieden mit
  E-MA-11.
- **F-MA-02 — Die Bildgrundlage des Befunds ist Web 9.12.**
  `docs/bilder/tagesuebersicht-desktop.png` ist ein alter Stand; im heutigen
  Stylesheet stehen **57** Stellen mit `font-weight:600`, nicht 13
  (gezählt 06.11.2026). Die Zuordnung gilt trotzdem — sie nennt Token,
  keine Stellen —, aber der Aufwand ist größer als beschrieben.
- **F-MA-03 — MA-06 überschneidet sich mit Schritt 13.** P7 führt „Logos
  (Nr. 62)" und „Umbenennung überall". Entschieden mit E-MA-12: Die
  Produktmarke kommt hier, Nr. 62 wird hier erledigt; die Umbenennung der
  übrigen Texte bleibt in 13.
- **F-MA-04 — Sechs Seiten binden `style.css` selbst ein**
  (`install.php`, `kopfzeilen_lib.php`, `codeblatt.php`, `notfallblatt.php`,
  `betrieb_schluesselblatt.php`, dazu `ui.php`). Eine zweite Datei
  `farben.css` müsste an jeder davon nachgetragen werden. Daraus E-MA-17.

## 3. Quellen

| Quelle | Wo | Was |
|---|---|---|
| Branding-Repo | `gen-em/gen-em-branding` @ `95897f3` | `9 Technik/Tokens/farben.css` (Leitwerte, Tonstufen, Semantik hell und dunkel), `typografie.css`, `radien.css`, `5 Regeln/*.md`, `1 Logos/Produktmarken/NAdoku/` |
| Mockups | `docs/konzepte/konzept-ma/mockups/MA-01 … MA-08.png` | Befund, Tagesübersicht hell/dunkel, Dark-Mode-Token, Einsatzformular, Betrieb → Status hell/dunkel, Bausteinzuordnung |
| Canvas | „Einsatzdoku nach GenEM-Vorgaben" (Claude Design, Konto der Betreiberin) | dieselben acht Boards, bearbeitbar |

Die Kopie der Repo-Dateien aus der Übergabe (rund 3 MB, Schriften und PNG)
wird **nicht** eingecheckt (E-MA-18); was die Anwendung braucht, kommt mit
Herkunftsangabe an seinen Ort unter `server/assets/`.

## 4. Was sich ändert — Übersicht

| Nr. | Ist | Soll | Paket |
|---|---|---|---|
| 1 | Orange = Handeln (Primärknopf, aktiver Menüpunkt, Anlegen-Links, Kartenmarker, Vorbelegungsstern, aktive Kachel) | Aktion **Dunkelblau**, Links **blau 600**, Auswahl **blau 100** Fläche; Orange nur als Status „Aufmerksamkeit" und als Markenfläche | MA-01, MA-04 |
| 2 | eigene Töne `--orange-tief`, `--blau-tief`, `--rot-tief`, `--gedaempft`, `--linie` | Tonstufen des Repos | MA-01 |
| 3 | Bricolage 600, Open Sans 600 | Bricolage **500**, Open Sans **400/700** | MA-02 |
| 4 | Skala 12/13/15/16/19/24/28–30 | 15 px bleibt (E-MA-07), **12 px entfällt** | MA-02 |
| 5 | kein Grün; Vollzug = Blau mit Haken | **Grün** für Vollzug und „in Ordnung"; Blau nur noch Eigenschaft und Auskunft | MA-03 |
| 6 | Logo Hubschrauber/NEF mit Näherungswerten | Produktmarke aus dem Repo, Kopfleiste Zeichen + „NAdoku" | MA-06 |
| 7 | Rauch = Seite, Schnee = Karte | Schnee = Seite, neutral 75 = Karte, Rauch = abgesetzt | MA-01 |
| 8 | Spurfarben 5–7 aus den Tief-Tönen, 8 als eigener Hexwert | Spurfarben aus den Tonstufen des Repos | MA-05 |
| 9 | kein Dark Mode | Dark Mode, Vorgabe System, Schalter je Konto | MA-07 |

## 5. Arbeitspakete

### MA-01 Token in `:root` umstellen

Der Token-Block des Repos (`farben.css`, ohne den Dark-Mode-Teil, der in
MA-07 kommt) steht **wörtlich** am Kopf von Abschnitt 2 in `style.css`,
eingefasst von einem Kommentar mit Herkunft (Repo, Commit, Pfad) und
SHA-256 der Quelldatei (E-MA-17). Die App-Token verweisen darauf (Weg B,
E-MA-16). Daneben entsteht eine **Rollenschicht**, weil ein Leitwert heute
mehrere Rollen trägt (`--schnee` ist Karte, Eingabefeld und Schrift auf
Dunkel) und der Dark Mode nur Rollen umschalten kann:

| Rolle (App-Token) | hell | Quelle |
|---|---|---|
| `--flaeche-seite` | Schnee | `--genem-flaeche-standard` |
| `--flaeche-karte` | neutral 75 | `--genem-flaeche-karte` |
| `--flaeche-abgesetzt` | Rauch | `--genem-flaeche-abgesetzt` |
| `--text` | Asphalt | `--genem-text-standard` |
| `--text-titel` | Dunkelblau | `--genem-text-alternativ` |
| `--gedaempft` | neutral 600 | `--genem-text-sekundaer` |
| `--text-link` | blau 600 | `--genem-text-blau` |
| `--linie`, `--linie-stark` | neutral 200, neutral 600 | `--genem-linie`, `--genem-linie-stark` |
| `--aktion`, `--aktion-hover`, `--auf-aktion` | Dunkelblau, blau 700, Schnee | `--genem-aktion`, `--genem-aktion-hover`, `--genem-text-auf-dunkelblau` |
| `--auswahl-flaeche`, `--auswahl-text` | blau 100, Dunkelblau | Board MA-04 („Auswahl") |
| `--info-*`, `--erfolg-*`, `--warnung-*`, `--fehler-*` (`-flaeche`, `-text`, `-linie`) | Stufe 100 / 700 / 600 | `--genem-status-*` |
| `--auf-dunkel` | Schnee | `--genem-schnee` (Weiß auf Dunkelblau ist in der Marke Schnee) |

Die Leitwerte (`--schnee`, `--rauch`, `--sand`, `--asphalt`,
`--dunkelblau`, `--orange`, `--blau`, `--rot`) bleiben als Namen und zeigen
auf `--genem-*`; benutzt werden sie nur noch, wo die **Marke** gemeint ist
(Kopfleiste, Spurfarben, Fokusring, Markenflächen). `--orange-tief`,
`--blau-tief`, `--rot-tief`, `--orange-hell`, `--blau-hell`, `--rosa`,
`--knopf-primaer-*` entfallen. Der Fokusring bleibt blau 500, 2 px, Versatz
2 px.

**Abnahme:** `tools/screenshots/kontrast.py` mit der neuen Paarliste,
0 verfehlt; kein Hexwert außerhalb `:root` (`vollstaendigkeit.py`).

### MA-02 Schriften

- `assets/fonts/`: die vier 600er-Dateien (`bricolage-…-600-…`,
  `open-sans-…-600-…`) entfallen; 500 (Bricolage), 400 und 700 (Open Sans)
  bleiben als Fontsource-Subsets `latin` und `latin-ext` (E-MA-15).
- Jedes `font-weight:600` wird `500` (Bricolage) bzw. `700` (Open Sans).
- `--groesse-1` (12 px) entfällt, seine Verwendungen heben auf
  `--groesse-2` (13 px). Zeilenhöhen bleiben.
- `docs/Lizenzen.md` 2 nachziehen.

### MA-03 Meldungen, Plaketten, Ampel, Kennzahl

- **Meldung** (9.5): Ton `ok` wird grün (`--erfolg-*`); `info` bleibt blau,
  `warn` orange, `fehler` und `schutz` rot (E-MA-10).
- **Plakette** (9.6): neuer Ton **`gruen`**; Reihe
  `neutral · gruen · blau · orange · rot`. Grün = Zustand in Ordnung
  (Vollzug, freigegeben, gesichert), Blau = Eigenschaft (Sekundär,
  Rettungsmittel, aktuell), Orange = Aufmerksamkeit, Rot = arbeitet nicht,
  Neutral = Zahl ohne Wertung (E-MA-08). Jede Übergabe von `'blau'`, die
  einen Zustand meint, wird `'gruen'`. `vollstaendigkeit.py` kennt den Ton.
- **Ampel** (9.23): „in Ordnung" wird grün; Tabelle in `Design.md` 9.23
  und die Statuskacheln ziehen nach.
- **Kennzahl** (9.10): Töne wie die Plakette; Extremwert bleibt orange,
  null bleibt neutral.

### MA-04 Knöpfe und Aktivzustände

- `knopf-primaer`: Fläche `--aktion`, Schrift `--auf-aktion`, hover
  `--aktion-hover`.
- `knopf-neutral`: Rahmen `--linie-stark`, Schrift `--text-titel`.
  Leise Knöpfe und Links: `--text-link`.
- `knopf-gefahr`: Fläche rot 700, Schrift Schnee (E-MA-09).
- Aktiver Punkt in Leiste und Schublade: Fläche `--auswahl-flaeche`,
  Schrift `--auswahl-text`, fett; Kopfleiste: Aktivstrich blau 300.
  Vorbelegungsstern, aktive Kachel, Zeilenhervorhebung, Anlegen-Wege:
  Auswahl bzw. Aktion statt Orange.
- Kartenmarker (`.geo-*`): Dunkelblau mit Schnee-Symbol. Orange nur, wo es
  eine Aussage trägt.
- `.kopf-umgebung` (Etikett Staging/Demo): Fläche orange 800, Schrift
  orange 200.

### MA-05 Spurfarben (neu gefasst)

Die Marken bleiben (E-MA-11). `--spur-1` bis `-4` bleiben die Leitwerte;
`--spur-5` bis `-7` wechseln von den entfallenden Tief-Tönen auf **orange
600, blau 600, rot 600** (die nächstgelegenen Stufen), `--spur-8` vom
eigenen Hexwert `#867146` auf **neutral 500**. Die Ruhespur fährt in
`--gedaempft`. Im Dark Mode bleibt die Karte hell (E-MA-14); die
Tabellenmarke auf dunkler Karte bekommt einen Ring in `--linie-stark`,
damit `--spur-4` (Dunkelblau) auf Dunkelblau sichtbar bleibt.

### MA-06 Produktmarke

- Aus `1 Logos/Produktmarken/NAdoku/` nach `server/assets/images/`:
  `nadoku-{hubschrauber,nef}-{farbig,farbig-negativ,weiss}.svg`. Die
  alten `gen-em_logo_*` werden gelöscht; `logo_stamm()`, `logo_src()`,
  `ui_logo()`, `ui_logo_masse()`, `favicon_tags()` zeigen auf die neuen.
  „farbig-negativ" ersetzt „weiß" in der Kopfleiste.
- Kopfleiste: Zeichen 34 px hoch + **„NAdoku"** in Bricolage 500
  (E-MA-06); die Logo-Wahl je Konto bleibt.
- Favicons neu aus der farbigen Variante (16/32/48 ICO, 180 Apple,
  192/512 PNG).
- Backlog Nr. 62 ist damit erledigt (E-MA-12).

### MA-07 Dark Mode

- Die dunkle Zuordnung des Repos (`@media (prefers-color-scheme: dark)` mit
  `:root:not([data-theme="light"])` und `:root[data-theme="dark"]`) kommt
  wörtlich in den Token-Block; die App-Rollen, die das Repo nicht hat
  (`--auf-aktion`, `--auswahl-*`, Gefahrknopf, Schatten, Schleier), bekommen
  ihre dunkle Zuordnung daneben, nach Board MA-04 und MA-08.
- Kopfleiste bleibt Dunkelblau, mit 1-px-Linie blau 700 nach unten.
- Logo: Variante farbig-negativ auf dunklen Flächen.
- Karte (Leaflet) bleibt hell, mit Rahmen (E-MA-14). Diagramme:
  Gründerfarben auf dunklem Grund nach Board MA-04.
- **Schalter je Konto** (E-MA-13): Einstellungen → Profil (das Zahnrad oben
  in der Kopfleiste) bekommt neben „Logo" das Feld **„Darstellung"** mit
  System · Hell · Dunkel. Gespeichert in `users.darstellung` (neue
  Migration, Vorgabe `''` = System), bei der Anmeldung in die Sitzung
  gelesen wie `logo_wahl`, ausgegeben als `data-theme` am `<html>` —
  serverseitig, damit nichts aufblitzt. **Nach dem Deploy muss
  `update.php` laufen**; bis dahin gilt für jedes Konto „System".

### MA-09 Android-Nachzug (neu, aus F-MA-12)

Die App übernimmt Farbwerte (E-S4-22a) und Bildmarken aus dem Web; zwei
Proben halten beides gegeneinander. Nachgezogen werden **nur Werte**: die
sechs Farben, die es im Web so nicht mehr gibt, auf die Tonstufen des Repos,
die vier Bildmarken auf die Produktmarke. Die **Rollen** der App (Orange
handelt) bleiben; sie nachzuziehen ist ein eigenes Konzept mit Mockup und
Gerätetest (E-MA-24). Eigene Android-Fassung (Korrektur).

### MA-08 Dokumentation, Prüfmittel, Abschluss

- `Design.md`: Kapitel 2 (Quelle = Repo, Produktmarke), 3 (Farbrollen neu),
  4 (Token-Tabelle erzeugt), 5 (ohne 12 px), 9.5/9.6/9.10/9.23 (grün), neues
  Kapitel Dark Mode; erzeugte Tabellen mit `tools/erzeugen/design.py
  schreiben`.
- `tools/screenshots/kontrast.py`: Paarliste um Grün, ohne Orange-Schrift,
  mit den Dark-Mode-Paaren.
- Handbuch (Darstellung im Profil), Technik (Migration, Token-Herkunft),
  Lizenzen (Schriften, Produktmarke), Changelog, `version.php`,
  Rahmenplan, Backlog.
- Prüfstand voll (Stylesheet berührt, Migration ⇒ Hauptstufe), Bericht im
  letzten Commit, Prüfdokument in die Prüfliste, Pull Request.

### Zweite Runde (07.11.2026)

Auftrag der Betreiberin nach dem ersten Pull Request: Kurzname zurück,
Android und Garmin mit, das Repo auf seinen neuen Stand nachziehen; dazu die
Ausnahmen der Prüfmittel bewerten, die Oberflächenproben stabil machen und
eine Barrierefreiheitsprüfung aufnehmen. Vor dem Umbau gemessen (Abschnitt
10): Bedienprobe 77 von 77 in drei Engines, axe-core 11 Regeln verletzt.

- **MA-10 Nachzug `e7bf4fc`.** Beide übernommenen Blöcke ersetzen,
  Prüfsumme; Gefahrknopf als Umriss (E-MA-26), Warnkästen ohne `ui.php`
  mit Symbol. Korrektur.
- **MA-11 Kurzname.** `ui_kopf()` zeigt wieder `ui_instanz_kurz()`;
  Beschriftung in der Installation, Handbuch, Design (E-MA-28). Korrektur.
- **MA-12 Hauptaktion.** Neue Knopfart `haupt` in `ui_knopf()` (keine neue
  Darstellung: die Form des Primärknopfs mit den Repo-Token der
  Hauptaktion, freigegeben mit Repo-Board 9D und E-MA-27), Option an
  `ui_speichern_leiste()`, die sechs Seiten. Neben.
- **MA-13 Favicons.** Die Sets aus `1 Logos/Produktmarken/NAdoku/Favicon/`
  statt eigener Erzeugung; ohne Manifest (R70). Korrektur.
- **MA-14 Barrierefreiheit.** Die Funde aus Abschnitt 10.2 beheben; Links
  im Fließtext unterstrichen; den stillen Abruf beim Verlassen der Seite
  abbrechen (E-MA-31). Korrektur.
- **MA-15 axe-core als Prüfmittel.** Vendoriert unter
  `tools/barrierefreiheit/vendor/`, Herkunft und SHA-256; Seitenliste hell
  und dunkel, drei Engines; Eintrag in `Pruefablauf.md` und im Register
  der Motoren; Anlass Backlog Nr. 524. Nur `tools/` — keine Fassung.
- **MA-16 Android.** Rollen und Dark Mode in `Farbe.kt` und im Thema,
  Hauptaktion „Dienst beginnen", Bilderlauf hell und dunkel; Backlog
  Nr. 522 erledigt. Eigene Android-Fassung (Neben).
- **MA-17 Garmin.** `tools/erzeugen/uhr-bilder.sh`, Uhr-Fassung in
  `Const.mc`; Backlog Nr. 520 erledigt.
- **MA-18 Abschluss.** Dokumente, Prüfstand voll, die Oberflächenproben
  zweimal (E-MA-34), Pull Request, Prüfdokument.

**Modell:** Opus. **Fächerung:** keine — dieselbe Lage wie in der ersten
Runde (eine Datei im Kern, Prüfarbeit an der einen Anlage).

## 6. Entscheidungen

| Nr. | Entscheidung | Von wem |
|---|---|---|
| E-MA-01 | Gründerfarben sind Markenflächen, keine Funktionsfarben; Aktion ist Dunkelblau | Repo (Betreiberin, 06.11.2026) |
| E-MA-02 | Statusfarben: Symbol + Fläche 100 + Text 700; Grün als einzige neue Farbe, nur für Status | Betreiberin, Canvas Board 6 |
| E-MA-03 | Dark Mode als zweite Zuordnung der semantischen Token auf die Blau-Reihe | Betreiberin, Canvas Board 3/4 |
| E-MA-04 | Flächen wie im Repo: Schnee Seite, neutral 75 Karte, Rauch abgesetzt | Betreiberin |
| E-MA-05 | Produktmarken erlaubt (Variante 3); Grundform Dunkelblau | Betreiberin |
| E-MA-06 | Produktname in der Kopfleiste ist „NAdoku" | Betreiberin |
| E-MA-07 | 15 px Grundgröße bleibt als dokumentierte Ausnahme; 12 px entfällt | Repo-Regel |
| E-MA-08 | Plakette `blau` wird in Zustand (→ `gruen`) und Eigenschaft (bleibt `blau`) getrennt | Betreiberin, 06.11.2026 bestätigt |
| E-MA-09 | `knopf-gefahr` = rot 700 Fläche, Schnee Schrift | Betreiberin, 06.11.2026 bestätigt |
| E-MA-10 | `schutz`-Meldung bleibt rot mit Schloss, nutzt die Fehler-Token | Vorschlag, nicht widersprochen |
| E-MA-11 | Die Tabellenmarken sind Spurfarben und bleiben; keine Status-Spalte (F-MA-01, Q-MA-01). Die Spurfarben wechseln auf Tonstufen des Repos | Betreiberin, 06.11.2026 |
| E-MA-12 | Die Produktmarke (alt MA-06) kommt in diesem Einschub; Backlog Nr. 62 wird hier erledigt, Schritt 13 behält die Umbenennung der übrigen Texte (F-MA-03) | Betreiberin, 06.11.2026 |
| E-MA-13 | Dark Mode kommt in diesem Einschub, mit Schalter je Konto (System · Hell · Dunkel) unter Einstellungen → Profil, erreichbar über das Zahnrad der Kopfleiste (Q-MA-02). Gespeichert in `users.darstellung` wie `logo_wahl` | Betreiberin, 06.11.2026 („Schalter je Konto — oben in der Leiste bei Klick auf Einstellungen"); Ort im Profil gelesen von der Umsetzung |
| E-MA-14 | Die Karte bleibt im Dark Mode hell und bekommt einen Rahmen; kein dunkler Kacheldienst (Q-MA-03, Bedrohungsmodell Lage G) | Betreiberin, 06.11.2026 |
| E-MA-15 | Schriften bleiben Fontsource-Subsets (`latin`, `latin-ext`), nur ohne die 600er (Q-MA-04) | Betreiberin, 06.11.2026 („wenn es damit geht") |
| E-MA-16 | Weg B: App-Token verweisen auf `--genem-*` | Betreiberin, 06.11.2026 |
| E-MA-17 | Der `farben.css`-Block steht wörtlich in `style.css`, nicht als zweite Datei: sechs Seiten binden `style.css` selbst ein (F-MA-04), und eine zweite Anfrage je Seite kostet mehr als sie bringt. Herkunft und SHA-256 der Quelldatei stehen im Kommentar darüber; wer das Repo nachzieht, ersetzt den Block und die Prüfsumme | Umsetzung |
| E-MA-18 | Die Kopie der Repo-Dateien aus der Übergabe wird nicht eingecheckt; Quelle ist das Repo mit Commit | Betreiberin, 06.11.2026 |
| E-MA-19 | Schritt 26, Kennung MA, als Einschub vor 13 (Reihenfolge 26 → 13 → 14) | Betreiberin, 06.11.2026 |
| E-MA-20 | Ein Pull Request am Ende, keine Zwischen-PRs; Halt nur, wo eine Entscheidung fehlt | Betreiberin, 06.11.2026 |
| E-MA-21 | Kopfleiste fest „NAdoku", Kurzname der Installation nur noch in Tab, Anmeldung, Mails, Druckblättern | Umsetzung (aus E-MA-06) — **aufgehoben** durch E-MA-28 |
| E-MA-22 | Wartungsseite fest Hubschrauber statt Münze (Produktmarken.md, Regel 9) | Umsetzung |
| E-MA-23 | Keine 192/512-px-Symbole; sie gehören zum Manifest (R70, Schritt 13) | Umsetzung |
| E-MA-24 | Android-Nachzug nur der Werte (MA-09), Rollen der App unverändert; Uhr-Bilder nicht neu erzeugt (Nr. 520) | Umsetzung — **aufgehoben** durch E-MA-29 und -30 |
| E-MA-25 | Das Repo wird auf `e7bf4fc` nachgezogen (Aktionsfarbe Variante 3, Knopfregeln, Favicon-Sets) | Betreiberin, 07.11.2026 („Änderungen im Branding-Repo … mit beachten & umsetzen") |
| E-MA-26 | Gefahrknopf wie im Repo: Umriss rot 600, Schrift rot 700, unter dem Zeiger rot 100. Ersetzt E-MA-09; die volle rote Fläche bleibt nur dem Zähler offener Fehler | Betreiberin, 07.11.2026 |
| E-MA-27 | Hauptaktion (`knopf-haupt`, Orange mit Asphalt) einmal je Seite auf sechs Seiten: Tagesübersicht „Einsatz nachtragen" als Knopf in der Titelzeile neben „Aktionen" (Mockup MA-02), Einsatzansicht „Bearbeiten", Einsatzformular „Speichern" in der Speicherleiste, Diensttag anlegen „Diensttag anlegen", Anmeldung „Anmelden", Registrierung „Konto anlegen". Keine Hauptaktion auf Suche, Import/Export, Einstellungen, Verwaltung, Betrieb und seltenen Abläufen | Betreiberin, 07.11.2026 („Passt so, bitte umsetzen") |
| E-MA-28 | Die Kopfleiste zeigt wieder den einstellbaren Kurznamen der Installation; E-MA-21 ist aufgehoben | Betreiberin, 07.11.2026 |
| E-MA-29 | Android bekommt die Rollen des Webs (Aktion Dunkelblau, Hauptaktion Orange „Dienst beginnen", die Wear-Uhr keine) und einen Dark Mode nach dem System. Statt eines Mockups liegen die Bilder des Android-Bilderlaufs hell und dunkel im Pull Request zur Abnahme | Betreiberin, 07.11.2026 |
| E-MA-30 | Garmin: nur die Zeichen der Produktmarke, keine Farbumstellung | Betreiberin, 07.11.2026 |
| E-MA-31 | Die Ausnahmen der Prüfmittel je Browser bleiben bis auf eine: den Abruf, der beim Verlassen der Seite noch läuft, bricht die Anwendung selbst ab (Bewertung in Abschnitt 10) | Betreiberin beauftragt die Prüfung, 07.11.2026; Bewertung Umsetzung |
| E-MA-32 | Die Funde der Barrierefreiheitsprüfung (axe-core) werden in diesem Einschub behoben, Links im Fließtext werden unterstrichen | Betreiberin, 07.11.2026 („1. Ja") |
| E-MA-33 | axe-core wird vendoriert und als Prüfmittel in drei Engines aufgenommen | Betreiberin, 07.11.2026 („2. Ja") |
| E-MA-34 | Am Ende laufen alle Proben über die Oberfläche zweimal hintereinander grün, in allen drei Engines | Betreiberin, 07.11.2026 („stabil und reproduzierbar") |
| E-MA-35 | Die Favicons werden aus dem Set des Repos geholt, nicht mehr selbst gezeichnet; `logos.mjs` kopiert sie und rastert nur noch die PNG für Android. Symbole 192/512, maskable und Manifest bleiben im Repo (R70) | Umsetzung, aus E-MA-25 |
| E-MA-36 | Die beendenden Knöpfe der App (Einsatz abschließen, Dienst beenden, Gerät trennen, Abschluss der Uhr) bleiben vollflächig rot, statt dem Umriss des Web zu folgen: Auf einem Gerät im Einsatz muss das Beenden ohne Lesen erkennbar sein (Begründung von E-S4-22a). Werte wie der Zähler offener Fehler im Web (`--gefahr`) | Umsetzung — **zur Bestätigung vorgelegt** |

## 7. Fragen an die Betreiberin

Alle beantwortet am 06.11.2026: Q-MA-01 → E-MA-11, Q-MA-02 → E-MA-13,
Q-MA-03 → E-MA-14, Q-MA-04 → E-MA-15.

## 8. Prüfpunkte

| Nr. | Was | Mittel |
|---|---|---|
| P-MA-01 | Alle Kontrastpaare ≥ 4,5 (Text) bzw. ≥ 3 (groß, Bedienelemente), hell und dunkel | `tools/screenshots/kontrast.py` |
| P-MA-02 | Kein Hexwert, keine Größe außerhalb `:root`; kein unbekannter Plaketten- oder Meldungston | `tools/quelltext/vollstaendigkeit.py` |
| P-MA-03 | Bilderlauf in drei Engines, Finger- und Zeigergerät | Prüfstand, voll |
| P-MA-04 | Handprüfung: Tagesübersicht, Einsatzformular mit Fehlern, Betrieb → Status, Dialog, Schublade mobil — je hell und dunkel; Favicon in Firefox und Safari; Logo-Wahl je Konto; Darstellung umschalten | Browser, Staging |
| P-MA-05 | `Design.md`-Tabellen erzeugt, Stufe 1 grün | Prüfstand |

## 9. Prüfprotokoll

### MA-01, -03, -04, -05 (Web 23.9.0, 07.11.2026)

**Was gebaut ist.** Der Block `farben.css` (Repo @ `95897f3`, Zeilen 1 bis
119, SHA-256 der Datei `3afe1c86…d2b5cf`) steht wörtlich am Kopf von
Abschnitt 2; darunter die Rollen der Anwendung (Flächen, Schrift, Linien,
Mechanik, Aktion, Auswahl, Status, Gefahr, Fokus, Umgebung, Diagramme,
Papier). Die Farbverwendungen im Stylesheet wurden mit einem Skript nach
Selektor und Eigenschaft umgestellt (271 Zeilen), danach 16 Stellen von Hand
berichtigt (Warnmeldung, offener Blattöffner, Auswahlschrift, Codeblock auf
dem Schirm statt Papier, Kartenzeichen) und rund 40 Erklärkommentare
nachgezogen. Plakettentöne: 75 Übergaben von `blau` auf `gruen` (Zustand), eine weitere
bedingt (`vollständig` grün, `N offen` blau),
die übrigen bleiben `blau` (Eigenschaft, Zahl, Wert). Spurfarben 5 bis 8 auf
Stufe 600 bzw. neutral 500, Ruhespur neutral 600.

**Befunde dabei.**
- **F-MA-05 — Das Umgebungsetikett hieß in der `config.php` `'rot'`.** Die
  Kopfleiste einer Testanlage ist jetzt orange 800; der Wert `rot` würde
  sonst auf der Statusseite als „Farbe unbekannt" gemeldet. Gelöst mit
  `UMGEBUNG_FARBEN_FRUEHER` (`'rot' => 'orange'`): Staging braucht keine
  Änderung an der Datei.
- **F-MA-06 — `kennzahl-*` kennt nur `orange` und `rot`.** Kein Aufruf übergibt
  einen guten Ton an eine Kennzahl; `.kennzahl-gruen` ist deshalb nicht
  gebaut (keine Regel ohne Verwender).
- **F-MA-07 — Der Klassenname `.knopf-leise-orange` stimmt nicht mehr.** Er
  trägt jetzt die Linkfarbe. Umbenannt ist er nicht — das berührt
  `ui_knopf()`, die Streichliste und die Tonprüfung für einen Namen; der
  Kommentar sagt es. Kandidat für Schritt 13.

**Gemessen.**
- `python3 tools/quelltext/vollstaendigkeit.py`: **Keine Befunde** —
  0 Hexwerte, 0 rgb()-Literale, 0 Schriftgrößen außerhalb `:root`;
  413 Tonübergaben geprüft, 0 ohne Regel.
- `python3 tools/screenshots/kontrast.py` (umgebaut auf Rollen, liest beide
  `:root`-Blöcke): **58 Paare, 0 verfehlt; 36 aus den Regeln abgeleitet,
  0 ohne Listeneintrag**; Selbstprobe 4 von 4.
- `python3 tools/erzeugen/design.py schreiben`: Token- und Bausteintabelle
  neu erzeugt.
- Sichtprüfung `index.php` als Demo-Konto bei 1440 px in Chromium:
  Primärknopf dunkelblau, „erledigt" grün, aktiver Diensttag blau 100 mit
  dunkelblauem Strich.

### MA-02 (Web 23.9.1, 07.11.2026)

**Was gebaut ist.** 53 Stellen `font-weight:600` nach Regel umgestellt: Regel
mit `--schrift-kopf` (oder Kind einer solchen, `.eintrag.aktiv
.eintrag-text`) → 500, sonst → 700; dazu zwei `font:600 …`-Kurzformen an den
Kartenzeichen, die das Muster nicht fand. Vier `@font-face` und vier
`.woff2` entfernt (bleiben sechs). `--groesse-1` gestrichen, zehn
Verwendungen auf `--groesse-2`. `docs/Lizenzen.md` 4 nachgezogen.

**Befunde dabei.**
- **F-MA-08 — Der aktive Leisteneintrag verliert seine Gewichtsstufe.** Er
  stand in Bricolage 600 gegen 500 (eigentlich 400, das auf 500 fällt); mit
  nur einem Schnitt ist beides 500. Er trägt sich jetzt über Auswahlfläche,
  Strich und Farbe — so zeigt es auch Mockup MA-02. Kein Eingriff.
- **F-MA-09 — Alte Mockups binden die entfernten 600er-Dateien ein**
  (`konzept-s9/mockups/*.html`, acht Dateien). Sie fallen auf die
  Systemschrift zurück; sie sind Protokoll freigegebener Entwürfe und werden
  nicht nachgezogen.

**Gemessen.** `vollstaendigkeit.py` keine Befunde (0 Schriftgrößen außerhalb
der Skala); `kontrast.py` 58 Paare, 0 verfehlt, 0 ohne Eintrag;
`design.py schreiben`: Token-Tabelle neu. Sichtprüfung `index.php` 1440 und
390 px in Chromium.

### MA-06 (Web 23.9.2, 07.11.2026)

**Was gebaut ist.** Sechs SVG der Produktmarke aus dem Repo (@ `95897f3`,
byte-gleich mit der Übergabe) nach `server/assets/images/`; die zehn alten
Dateien `gen-em_logo_*` und `favicon_*.png` gelöscht. `logo_stamm()` liefert
`nadoku-hubschrauber` bzw. `nadoku-nef`, `ui_logo(true)` die Fassung
farbig-negativ, `logo_src()` die farbige und erkennt alte wie neue Namen in
`logo_path`. `favicon_tags()` bietet SVG, PNG 32, Apple-Symbol 180 und ICO.
`tools/erzeugen/logos.mjs` neu geschrieben (erzeugt Favicons, ICO und die
500-px-PNG für die Android-App); `uhr-bilder.sh`, `lokal_einrichten.sh`,
Schemaprobe, Wartungsprobe, `pruefablauf.json` auf die neuen Namen.
Kopfleiste und Schublade zeigen `UI_PRODUKTNAME` („NAdoku").
`logo_platzhalter_liegt()` samt Meldung entfernt. Design.md 2 neu
geschrieben, Technik, Lizenzen, Handbuch 3, 3.1a, 11.5, Pruefablauf 4
nachgezogen; Zuarbeit „Logo-Vorlagen" im Rahmenplan erledigt.

**Entscheidungen dabei.**
- **E-MA-21 — Die Kopfleiste zeigt fest „NAdoku", nicht den Kurznamen der
  Installation.** E-MA-06 sagt es, und die Produktmarke ist Zeichen und Name
  zusammen. Der Kurzname war bis dahin dort einstellbar — gedacht für eine
  fremde Betreiberin (`instanz_lib.php`); er bleibt im Tab, auf der
  Anmeldeseite, in Mails und Druckblättern. **Zur Bestätigung durch die
  Betreiberin vorgelegt** (Abschlussbericht).
- **E-MA-22 — Die Wartungsseite zeigt fest den Hubschrauber.** Sie warf eine
  Münze je Aufruf; die Produktmarke darf nicht je Seitenaufruf wechseln
  (Produktmarken.md, Regel 9). Die Wartungsprobe prüft jetzt das Gegenteil
  von früher (zehn Aufrufe, ein Logo).
- **E-MA-23 — Keine 192/512-px-Symbole.** Sie gehören zum Web-App-Manifest
  (R70, Schritt 13).
- Produktname in 19 px (`--groesse-5`), nicht 20 wie im Mockup: Die Skala ist
  geschlossen.

**Befunde dabei.**
- **F-MA-10 — Die Uhr-Bilder sind aus den alten Dateien gerastert.** Neu
  erzeugen gehört zu einer Uhr-Fassung → Backlog Nr. 520 (gehört zu 13).
- **F-MA-12 — Die Android-App übernimmt Farbwerte und Bildmarken aus dem
  Web** (E-S4-22a; Proben `android-farbabgleich`, `android-bildmarken`).
  Beide sind seit MA-01 bzw. MA-06 rot: Neun der 17 App-Farben gibt es im Web
  nicht mehr, und die Quell-PNG heißen anders. Das Konzept sah das nicht vor.
  Daraus MA-09 (neu).

**Gemessen.** `php -l` über die 135 PHP-Dateien in `server/` ohne Fehler;
`logos.mjs` schreibt 11 Dateien (ICO: 3 Bilder 16/32/48); Sichtprüfung der
Kopfleiste bei 1440 und 390 px (Chromium), Favicons als Kontaktbogen.

### MA-09 (Android 0.21.1, 07.11.2026)

**Was gebaut ist.** `farben.xml`: sechs Werte auf Repo-Tonstufen
(Gedämpft `#725D49`, Linie `#E5DECD`, Orange tief `#974600`, Blau tief
`#27487C`, Rot tief `#890415`, Schrift auf Dunkel `#FFFCFA`); drei Hellwerte
sind schon Stufe 100. `farbabgleich.py` liest beide `:root`-Blöcke (bis dahin
das erste `:root{` im Rohtext — es stand nach MA-01 in einem Kommentar) und
zeigt Tief- und Hellwerte auf `--genem-*`. `bildmarken.sh` holt die
Produktmarke; die App-Fassung „weiss" ist die Repo-Fassung
„farbig-negativ" (Korpus Schnee, Federn farbig — dieselbe Aussage wie
vorher). `logos.mjs` erzeugt deshalb `…-farbig-negativ.png` statt
`…-weiss.png`, und `uhr-bilder.sh` zeigt ebenfalls auf farbig-negativ.
Zwei Kommentare in `DienstAnsicht.kt` mit Kontrastzahlen nachgezogen.

**Gemessen.** `farbabgleich.py`: 17 App-Token, **0 Abweichungen, 0 eigene
Werte**. `bildmarken.sh pruefen`: **0 Abweichungen** (vier Dateien).
`kontraste.py`: **29 Paare, 0 Befunde** (u. a. Rückstandspunkt orange 700
6,44:1, Ortung fehlt rot 700 9,83:1, Beenden-Knopf Schnee auf Rot 4,68:1).
Der Android-Bau läuft im Prüfstand (Abschluss).

### MA-07 (Web 23.10.0, 07.11.2026)

**Was gebaut ist.** Abschnitt 2a in `style.css`: der Dark-Mode-Block des Repos
wörtlich (`farben.css` ab Zeile 120), danach die Rollen der Anwendung in
denselben zwei Auslösern (Schrift auf der Aktion Dunkelblau, Auswahl blau 700
mit Schnee, Mechanik blau 400, Gefahrknopf rot 300 mit rot 900, Fokus
blau 100, Markenfläche blau 900, Diagramme Stufe 300/400), `color-scheme`,
Kante unter der Kopfleiste, Rahmen um die Karte, Rand um die Spurstreifen
der Tabelle und der Legende (MA-05), Druck immer hell (`:root:root:root`).
`.bereich-zeichen` und `.erststart-nr` von Leitwert auf `--aktion`. Neue
Rolle `--text-link-hover` (F-MA-13). Migration `2026_11_07_darstellung`,
`schema.sql`, `darstellung_lesen()` / `_sitzung_setzen()` / `_attribut()`
in `session_lib.php`, `login.php` liest bei der Anmeldung,
`ui_seite_start()` setzt `data-theme`, Profilkarte „Darstellung" mit
`ui_wahlliste()` (vorhandener Baustein), Speichern in eigener Anweisung.
`logo_bild_anmeldung()` für die sieben Anmeldelogos. `kontrast.py` rechnet
jedes Paar hell und dunkel und liest den Druckblock nicht mehr als Wert
(F-MA-14). Handbuch 3.1a, Technik 3 und 4.41.

**Befunde dabei.**
- **F-MA-13 — Link unter dem Zeiger im Dunkeln 4,45:1.** blau 300 auf
  blau 700 verfehlt 4,5 knapp. Neue Rolle `--text-link-hover` (hell = Link,
  dunkel = blau 200) an den vier Hover-Regeln, deren Zeile auf Rauch wechselt.
- **F-MA-14 — `kontrast.py` las den Druckblock als Wert.** Das letzte Glied von
  `:root:root:root {` passte auf `:root\s*\{`; die hellen Werte kamen dann
  aus dem Druck (Karte Schnee statt neutral 75). Gefunden, weil im Dunkeln
  zwölf Paare scheinbar verfehlten. Behoben mit einem Rückblick im Muster.
- **F-MA-15 — Der Bilderlauf nimmt nur hell auf.** Ein Lauf dunkel verdoppelte
  seine Zeit (über 20 Minuten bei zehn Breiten); er ist nicht gebaut. Dunkel
  ist mit eigenen Aufnahmen geprüft (unten) und steht in der Prüfliste.

**Gemessen.** `kontrast.py`: **58 Paare je Modus, 0 verfehlt (hell 0,
dunkel 0), 36 abgeleitet, 0 ohne Eintrag**, Selbstprobe 4 von 4.
`vollstaendigkeit.py`: keine Befunde. `update.php` örtlich: Migration
„Erfolgreich angewendet". Repo-Blöcke hell und dunkel byte-gleich im
Stylesheet (Teilzeichenkettenvergleich). In Chromium: `index.php` und
`einstellungen.php?t=profil` dunkel bei 1440 und 390 px angesehen; Konto auf
„dunkel" gesetzt, Browser hell → `data-theme="dark"`, Seitenfläche
`rgb(16, 27, 44)` (blau 900); als Druck → `rgb(255, 252, 250)` (Schnee);
Anmeldeseite ohne Konto → kein Attribut, Logo dunkel `…-farbig-negativ.svg`.

### MA-08 (Dokumentation, Prüfmittel, 07.11.2026)

**Was gebaut ist.** `Design.md`: Kapitel 3 neu (Rollen, Status 100/700/600,
Kontrasttabelle aus `kontrast.py --json` mit Spalten hell und dunkel),
**neues Kapitel 3a Dark Mode**, Kapitel 5 ohne 12 px, 9.5, 9.6, 9.10, 9.23,
9.36, 9.37, 9.40, Vorschlagsliste, Ladeplatz und Balken mit den neuen
Rollen, Eintrag im Änderungsverlauf; erzeugte Tabellen neu. Handbuch:
Kopfleiste, Profil (Logo, Darstellung), Farbangaben an zwölf Stellen
(Auswahl hellblau, Vollzug grün, Ampel grün, Testanlage orange).
Technik: 3 (`users.darstellung`), 4.41 neu, Runbook Umgebungsetikett.
`tools/screenshots/LIESMICH.md` mit den neuen Sollzahlen. Backlog: Nr. 62
und Nr. 411 erledigt, **521** (Bilderlauf dunkel), **522** (Rollen der
Android-App), **523** (Klassennamen mit „orange") neu. Prüfdokument angelegt.

**Der erste Prüfstandslauf** (Hauptstufe, voll, 8 597 s; auf einem Baum,
der danach noch geändert wurde — deshalb kein Bericht): **59 grün, 7 rot**,
alle sieben vom Umbau verursacht und behoben:
- **F-MA-16 — Die Wortliste kannte die neuen Dateinamen nicht.** Die alten
  hießen englisch (`gen-em_logo_helicopter`), die neuen deutsch
  (`nadoku-hubschrauber-*`); „Hubschrauber" ist ein Luftbegriff (P2).
  Zwölf Ausnahmen der Klasse Homonym dazu, zwei bestehende auf den neuen
  Wortlaut; „Betreiberin" in `Design.md` → „BetreiberIn".
- **F-MA-17 — Die Dokumentenprobe** fand Dateien des Branding-Repos und die
  alten Logonamen im Änderungsverlauf; zwei Zeilen in
  `dokumente-ausnahmen.md` (Fremd, Früher).
- **F-MA-18 — Zweitfaktor- und Rückwegprobe erwarteten `blau`** für „in
  Ordnung"; jetzt `gruen`.
- **F-MA-19 — Die Bedienprobe prüfte gegen `--orange-hell`/`--orange-tief`**
  (offener Öffner, angesprungene Zeile, Landung nach dem Anlegen); jetzt
  gegen die Auswahl (blau 100, Dunkelblau).
- **F-MA-20 — Die Verbindungsprobe meldete den Datenbankbenutzer** auf der
  503-Seite: Örtlich heißt er `nadoku`, und so beginnen die neuen
  Logodateien. Sie sucht jetzt im Text der Seite, nicht in `src`/`href`.
- **F-MA-21 — Der Stilvergleich konnte nicht für drei Engines grün werden**
  (Backlog Nr. 411): Sie benennen dieselbe Änderung verschieden. Je Engine
  eine Liste `geplant-<motor>.txt`; gemessen 2 757 / 2 757 / 2 763
  Signaturen, je 0 ungeplant; eine zusätzliche Regel rot in allen dreien.
  Nr. 411 erledigt.
- **F-MA-22 — Die Oberfläche war nie auf Barrierefreiheit gemessen.**
  axe-core fand 11 Regeln verletzt, in drei Engines gleich (Abschnitt
  10.2); keine davon war eine Kontrastfrage. Behoben in MA-14, gemessen
  wird künftig mit MA-15 (Backlog Nr. 524).
- **F-MA-23 — `pagehide` kommt in WebKit zu spät.** Der erste Versuch, die
  Hintergrundabrufe beim Seitenwechsel abzubrechen, änderte nichts (3
  abgebrochene Abrufe vorher und nachher, `pat_anheben.php` zweimal,
  `wartungslauf.php` einmal); WebKit bricht ab, bevor `pagehide` feuert. Mit
  `beforeunload` 0. Preis: Bleibt die Seite nach `beforeunload` stehen,
  ist eine Sekunde lang kein Hintergrundabruf möglich.
- **F-MA-24 — Zwei Wege der Bedienprobe wurden durch MA-14 rot** und
  sind behoben: Der Vorlesetext „Spurfarbe" der Farbspalte zählte für die
  Wortprobe als „Spur" (jetzt „Farbe"); die Statistikprobe las den
  Vorlesetext der ersten Kopfzelle als Spalte (sie lässt `.nur-vorlesen`
  jetzt weg).
- **F-MA-25 — `farbabgleich.py` las den Druckblock als hellen Modus.** Das
  Muster `:root\s*\{` traf das letzte Glied von `:root:root:root{`; dessen
  Werte (immer hell) lagen über allem. Hell fiel es nicht auf, weil dort
  Druck und Bildschirm gleich sind; dunkel ergab es Schnee als Kartenfläche.
  Jetzt derselbe Ausschluss wie in `kontrast.py`.
- **F-MA-26 — Im Dark Mode verschwand der Korpus der Bildmarke** auf der
  dunklen Karte der Dienstansicht (farbige Fassung, dunkelblauer Korpus).
  Gefunden beim Ansehen der Übersichtsbilder, nicht von einer Probe; jetzt
  die negative Fassung bei `isSystemInDarkTheme()`.
- **F-MA-27 — Lint meldet im Handy-Modul 6 Warnungen**, alle „neuere
  Fassung verfügbar" (AGP 9.5.1, Kotlin 2.4.21, wear-compose 1.7.1,
  Robolectric 4.17). Sie hängen am Datum, nicht an MA-16; nicht behoben.

**Gemessen** (vor dem Prüfstand, auf demselben Baum): `decken.py` 21
Decken, 0 gerissen; `bestand.py` 0 Befunde; `uebersicht.py` läuft;
`vollstaendigkeit.py` keine Befunde; `kontrast.py` 58 Paare je Modus,
0 verfehlt, 0 ohne Eintrag. **Der Prüfstand** (Hauptstufe wegen der
Migration, voll) steht mit seinem Bericht in der Nachricht des letzten
Commits; seine Zahlen stehen dort und nicht hier, weil jede Zeile hier nach
der Messung den Baum änderte.

### MA-10 (Web 23.10.1, 07.11.2026)

**Was gebaut ist.** Beide übernommenen Blöcke aus `farben.css` @ `e7bf4fc`
(SHA-256 `ba55f312…ab26`, 192 Zeilen; hell 1 bis 123, dunkel ab 124).
`--auf-aktion` zeigt auf `--genem-text-auf-aktion`, die beiden
App-Überschreibungen im Dunkeln entfallen; `--aktion-haupt`,
`-haupt-hover`, `--auf-aktion-haupt` neu. `knopf-gefahr` trägt die
Fehler-Rollen als Umriss; `--gefahr-hover` entfällt. Vier Warnkästen ohne
Symbol gefunden (`wartung_lib.php` dreimal, `kopfzeilen_lib.php`) und
ergänzt.

**Geprüft.** Der Block zwischen den Marken ist zeilengleich mit der
Quelldatei (`diff` ohne Leerzeilen, keine Abweichung). `kontrast.py`: 60
Paare je Modus, 0 verfehlt; 38 aus Regeln abgeleitet, 0 ohne Eintrag.
`vollstaendigkeit.py`: keine Befunde. `php -l` der drei PHP-Dateien sauber.

### MA-11 (Web 23.10.2, 07.11.2026)

**Was gebaut ist.** `ui_kopf()` und der Kopf der Schublade zeigen
`ui_instanz_kurz()`; `UI_PRODUKTNAME` ist entfernt (keine Verwendung mehr,
`grep` über `server/`, `tools/`, `docs/`). Beschriftung des Kurznamens in
Verwaltung → Installation, Handbuch 3 und 11.5, Design 2.3,
`instanz_lib.php` nachgezogen. **Geprüft:** `php -l` der drei Dateien.

### MA-12 (Web 23.11.0, 07.11.2026)

**Was gebaut ist.** `.knopf-haupt` (Form des Primärknopfs, Token der
Hauptaktion), Knopfart `haupt` in `ui_knopf()` ohne Sonderweg (die Klasse
folgt der Art), Option `haupt` an `ui_speichern_leiste()`. Sechs Seiten nach
E-MA-27; in der Tagesübersicht `#addmission-haupt` rechts neben „Aktionen",
Menüeintrag und „+ Nachtragen" an der Einsatzkarte bleiben wie im Mockup
MA-02.

**Geprüft.** `kontrast.py`: 60 Paare je Modus, 0 verfehlt (neu: Asphalt auf
Orange 8,6:1 hell, auf orange 400 dunkel; unter dem Zeiger); 40 aus Regeln
abgeleitet, 0 ohne Eintrag. `vollstaendigkeit.py`: keine Befunde (die Art
`haupt` hat ihre Regel). Sichtprüfung in Chromium: Tagesübersicht 1440 px
hell und 390 px dunkel (am Handy rückt die Knopfreihe unter den Titel),
Anmeldung 1440 px dunkel. **Nebenbefund:** Der Container war zwischen MA-11
und MA-12 neu gestartet worden; Startfolge 6.1a gefahren, kein Wartungsschalter.

### MA-13 (Web 23.11.1, 07.11.2026)

**Was gebaut ist.** `logos.mjs` holt je Zeichen `favicon.svg`,
`favicon-32.png`, `apple-touch-icon.png` und einmal `favicon.ico` aus dem
Set des Repos (Pfad über `BRANDING_REPO`, Vorgabe neben dem Repositorium;
fehlt es, bricht das Skript ab und schreibt nichts). Die eigene Zeichnung
der Favicons und der ICO-Schreiber sind entfernt.

**Geprüft.** Lauf: 7 Dateien geholt, die vier Android-PNG byte-gleich
neu gerastert (`git status` zeigt sie nicht). `php -l server/db.php`.

### MA-14 (Web 23.11.2, 07.11.2026)

**Was gebaut ist.** Die elf Regeln aus 10.2, je an ihrer Quelle (Liste in
`version.php`, 23.11.2); Links im Fließtext unterstrichen (Design.md 6);
`EdApi` bricht beim Verlassen der Seite ab (F-MA-23).

**Geprüft.** axe-core 4.14.0 mit demselben Lauf wie in 10.2 (46 Aufrufe je
Engine): Chromium, Firefox, WebKit je **0 Verstöße** (vorher je 11 Regeln,
409 Knoten in Chromium und Firefox, 412 in WebKit). Bedienprobe WebKit 77/77, 0 abgebrochene Abrufe
(vorher 3); dabei zwei Wege zuerst rot (F-MA-24), behoben. Handbuch nach der
Nachbesserung: 0 leere Kopfzellen, 4 `<pre>` und 48 `<table>` mit
`tabindex`. Sichtprüfung Chromium: Seitenleiste (Übersichtssymbol an
derselben Stelle), Import (unterstrichene Links). `node --check` der vier
geänderten Skripte, `php -l` der sieben PHP-Dateien.

### MA-15 (Prüfmittel, 07.11.2026)

**Was gebaut ist.** `tools/barrierefreiheit/` nach `Pruefablauf.md` 6.12:
Backlog Nr. 524 (gleich erledigt, Backlog-Erledigt), `probe.mjs` über
`starten()`, `LIESMICH.md` mit fünf Abschnitten und Anlass, axe-core 4.14.0
byteweise unverändert unter `vendor/` (SHA-256 im Werkzeug nachgerechnet,
Lizenz daneben, `Lizenzen.md` 7.0a), Probe `barrierefreiheit` in
`pruefablauf.json` (installation, demo, motoren) an den Mustern
`oberflaeche`, `stylesheet`, `motoren` und einem eigenen
`barrierefreiheit-werkzeug`, Zeile im Register `motoren.md`, Zeile in
`Pruefablauf.md` 6.11, Tabelle in 4 erzeugt. 28 Seiten, 74 Aufrufe je
Engine (dunkel nur bei 1440 px — dort wechseln die Flächen, die Breite
nicht).

**Geprüft.** Chromium: 74 Aufrufe, 0 Befunde, 292 Knoten unentschieden,
197 s. Gegenprobe (`aria-label` an „Aktionen" entfernt, `--nur index.php`):
1 Befund `button-name`, Rückgabewert 1; danach `ui.php` zurück, `git diff`
leer. Riegel: `bestand` 0 Befunde in allen Regeln, `pruefen.sh alle` 16 von
16, Selbstproben 14 von 14, `kettenaufrufe` ohne Widerspruch. Firefox und
WebKit fährt der Prüfstand (MA-18).

### MA-16 (Android 0.22.0, 07.11.2026)

**Was gebaut ist.** `Rolle` in `Farbe.kt` mit 24 Rollen, `rollen.xml` hell
und dunkel (Werte aus dem Web errechnet), alle 92 Farbstellen des
Handy-Moduls auf Rollen (übrig: Kopfleiste als Leitwert), `KnopfHaupt` für
„Dienst beginnen", `KnopfPrimaer` Dunkelblau, Zustand in Ordnung grün,
Thema mit `values-night`. Uhr: großer Knopf und nächste Phase blau 300
(`marke_blau_300`). `farbabgleich.py` und `kontraste.py` rechnen beide
Modi; der Bilderlauf zeichnet hell und dunkel und liest die Knopffarben aus
den Ressourcen. Übersichtsbilder für die Abnahme:
`konzept-ma/android/MA-16-handy-hell.png`, `-dunkel.png`, `MA-16-uhr.png`.

**Geprüft.** `./gradlew build` grün (3 min 25 s): 377 Prüffälle, 15
übersprungen, 0 Fehlschläge; Lint 0 Fehler (Warnungen F-MA-27). Bilderlauf
Handy 162 Bilder (27 Bildschirme × 3 Breiten × hell/dunkel), alle paarweise
verschieden, Bedienhöhe 48 dp; Uhr 7 Bilder. `kontraste.py` 36 Paare in 59
Rechnungen, 0 Befunde, Selbstprobe 7 von 7; `farbabgleich.py` 48
Rollenwerte, 0 Abweichungen, 0 eigene; `bildmarken.sh pruefen` 0
Abweichungen. Nicht geprüft: Gerät (P-MA-12).

## 10. Vorher-Messung der zweiten Runde (07.11.2026, Web 23.10.0)

### 10.1 Ausnahmen der Prüfmittel je Engine

| Ausnahme (`tools/motor.mjs`, `motoren.md`) | Engine | Bewertung |
|---|---|---|
| CSP-Bericht nach `screenshot()` (`webkitScreenshotOhneBericht`) | WebKit | Werkzeug: vor dem Bild 0 Berichte, danach genau 1 (`style-src-elem`, das `<style>`, das Playwright selbst einsetzt); mit `caret:'initial'` und `animations:'allow'` unverändert. Bleibt |
| Abgebrochener Abruf beim Seitenwechsel (`webkitAbgangLeise`, „access control checks") | WebKit | **Anwendung:** ein Hintergrundabruf läuft beim Verlassen noch. Abbruch über `pagehide` in `EdApi` (MA-14), danach Zählung prüfen |
| `NS_BINDING_ABORTED` am Schriftabruf | Firefox | Werkzeug: Abbruch durch die nächste Navigation des Werkzeugs. Ein Vorladen würde ihn seltener machen, nicht abschaffen. Bleibt |
| `ResizeObserver loop completed …` | WebKit | Spezifikation; der Kartencode ändert dabei keine Größe. Bleibt |
| Wiederholung bei „WebKit encountered an internal error" | WebKit | Browserfehler; im letzten Lauf 0 Wiederholungen. Bleibt als Sicherung |
| Passkey (CDP), `page.pdf`, `--drossel`, Messstand nur Chromium | Chromium | Diese Mittel gibt es nur dort. Bleibt |
| Stilvergleich mit einer Liste je Engine (Nr. 411) | alle | Namen der Eigenschaften (`inset`, `size`), kein Gestaltungsunterschied. Bleibt |

### 10.2 Bedienprobe und Barrierefreiheit vorher

**Bedienprobe** (`tools/bedienprobe/probe.mjs`): Chromium 77/77 (377 s),
Firefox 77/77 (346 s), WebKit 77/77 (341 s).

**axe-core 4.14.0**, Regeln WCAG 2.0 bis 2.2 A und AA und „best-practice",
46 Aufrufe je Engine (5 Seiten abgemeldet und 13 als Demo-Konto je bei 1440
und 390 px, 10 Verwaltungs- und Betriebsseiten als BetreiberIn bei 1440 px).
**Kein Kontrastverstoß.** In allen drei Engines dieselben 11 Regeln:

| Regel | Seiten | Knoten | Ursache |
|---|---|---|---|
| `nested-interactive` | 16 | 208 | Bedienelemente in der Kopfzeile (`summary`) des Monatsakkordeons der Seitenleiste |
| `dlitem` | 8 | 88 | `dt`/`dd` der Tagesfelder ohne `dl` |
| `link-in-text-block` | 14 | 35 | Links im Fließtext nur durch Farbe abgesetzt (1,05:1 zum Text) |
| `select-name` | 2 | 16 | Phasennummer im Einsatzformular ohne Beschriftung |
| `label-title-only` | 2 | 16 | Phasenzeit im Einsatzformular nur mit `title` |
| `empty-table-header` | 8 | 18 | Kopfzelle der Spaltenwahl leer |
| `landmark-unique` | 11 | 11 | zwei `<nav>` „Hauptbereiche" bei 390 px |
| `button-name` | 4 | 4 (WebKit 7) | „Aktionen" bei 390 px ohne Namen (der Text ist ausgeblendet); WebKit dazu „Karte sortieren" |
| `heading-order` | 2 | 4 | Hilfe: Überschrift springt eine Ebene |
| `scrollable-region-focusable` | 2 | 8 | Hilfe: Codeblock und Tabelle mit eigener Scrollleiste ohne Tastaturzugang |
| `aria-prohibited-attr` | 1 | 1 | Protokoll: `aria-label` an einem `<a>` ohne Rolle |

Unentschieden ließ axe 140 Kontraststellen (Text auf Verlauf, Bild oder
halbdurchsichtiger Fläche); die Paare rechnet `kontrast.py`.

