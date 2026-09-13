# Prüfdokument — Mockup-Runde

*Angelegt am 13.09.2026 (Fable) mit `Konzept-Mockup-Runde.md` als Vorlage;
**die Zahlen trägt die Umsetzung ein**. Fortgeschrieben nach jedem
Arbeitspaket — was noch `[eckige Klammern]` trägt, ist noch nicht gemessen.*

> **Stand: AP1 gemessen (13.09.2026) und Nr. 182 behoben (14.09.2026, Web 19.4.1). AP2 bis AP5 offen.**
>
> **Vier Sollwerte der Vorlage waren veraltet** — sie entstand vor E-MR-16,
> E-MR-19 und E-MR-21 — und sind hier berichtigt: Symboldateien **55** statt
> 54 (E-MR-19 verlangt zwei Symbole), Chip-Ziel **28 px** statt 24
> (F-MR-6b), ab 1600 px **bleibt der Knopf** (E-MR-16), und F-MR-6 ist durch
> 6a/6b ersetzt. Die Änderungen stehen im Konzept, Abschnitt 5.

> | | |
> |---|---|
> | Stufe | **Web 19.4.1** — 19.4.0 die Nebenstufe aus AP1, 19.4.1 die Korrektur zu Nr. 182. Keine Migration. Uhr und Android unberührt |
> | Punkte | Backlog **Nr. 41 und 182 erledigt**; 42, 45, 124 offen |
> | Neu entstanden | Token `--symbol-text`, `--karte-gross`; **`--dauer` von .18s auf .24s** (alle Bewegungen); Klassen `.symbol-text`, `.geo-gross`; Symbole `karte-gross.svg`, `karte-breit.svg` (54., 55.); Regel `.imp-daygroup`; Knopf offen = `--orange-hell`/`--orange-tief` (D4); Ausnahme `'✕'` in `ausnahmen.md`; Streichliste `imp-warn` |
> | Prüfumgebung | Wegwerf-Container: PHP 8.4.19, MariaDB 10.11.14, Node 22.22.2, Playwright 1.56.1, Chromium 141.0.7390.37. Lokale Installation über `lokal_einrichten.sh` (88 Einsätze, 16 Diensttage, 2 Geräte) |
> | Ergebnis | AP1 **grün, Vorbehalt aufgelöst** (Nr. 182 behoben). Rest offen |

## 0. Was NICHT geprüft werden konnte

- ~~**AP1: Die Kopfzeile der Tagesgruppe ist NICHT sichtbar.**~~ **Erledigt
  mit Web 19.4.1** (14.09.2026, Backlog Nr. 182, F-MR-14 = Weg B). Die
  Kopfzeile nimmt über eine Container-Abfrage die **sichtbare** Breite an; die
  Abnahme von AP1 („am Handy bricht die Kopfzeile in zwei Zeilen") ist damit
  nachträglich erfüllt. Zahlen in Abschnitt 3.
- **Nr. 182 in einer ZWEITEN Engine: erledigt, WebKitGTK 2.52.6** (Safaris
  Familie). Gemessen am 14.09.2026 mit einem Schnappschuss der echten Vorschau
  (dasselbe Markup, dasselbe Stylesheet über den laufenden Server): 400 px →
  Kopf 342 = Sicht 342 · 720 → 654 = 654 · 1280 → 1214 = 1214; `container-type`
  löst zu `inline-size` auf, `position` zu `sticky`; Datum, Besatzung, Plakette
  **und** Auswahlfeld in allen drei im Sichtfenster; Kopfhöhe 233/195/95 px, sie
  bricht also. (Die 1214 statt 954 bei 1280 px kommen daher, dass der
  Schnappschuss ohne Seitenleiste steht — mehr Platz, gleiche Aussage.)
- **Was weiterhin NICHT geprüft ist: Firefox (Gecko).** Playwrights
  Browser-Downloads sind in dieser Arbeitsumgebung gesperrt
  (`cdn.playwright.dev` und `playwright.download.prss.microsoft.com`, beide
  403), und Ubuntus `firefox`-Paket ist nur eine Snap-Hülle. Zwei von drei
  Engines sind damit belegt. Auf einem Browser ohne Container-Abfragen fiele
  `width:100cqi` aus und die Kopfzeile stünde wie in Web 19.4.0 da — kein
  Absturz, kein Datenverlust, nur der alte Zustand. **Prüfliste Punkt 6.**
- **AP1 und Nr. 182: Der Bilderlauf sieht die Vorschau nicht.** `seiten.json` führt
  `import.php` im Grundzustand — ohne gewählte Datei gibt es keine
  Vorschautabelle. Die acht Bilder der Seite belegen die Seite, **nicht** die
  Kopfzeile. Diese ist von Hand mit Playwright und einer eigens gebauten
  Importdatei geprüft (Abschnitt 3); der Bilderlauf würde sie erst sehen,
  wenn `seiten.json` einen Schritt „Datei wählen" bekäme.
- **Die Bewegung des Blatts (Nr. 124) auf einem echten Gerät.** Der
  Bilderlauf zeigt Ruhezustände; ob 240 ms auf einem S24 als „kommt von
  unten" gelesen werden, sagt erst Punkt 1 der Prüfliste — und ob die
  Schublade mit 240 statt 180 ms noch flink genug wirkt (Punkt 1a).
- **`localStorage` der Kartengröße über Browserwechsel** — gilt je Browser,
  nicht je Konto; so gewollt (F-MR-8). [Bestätigen.]
- [Weiteres.]

## 1. Zusagen, die sich geändert haben

- `Design.md` **9.34 neu** (AP1), **fortgeschrieben mit 19.4.1**: Aus „Was sie
  nicht kann" wird „Wie sie das Sichtfenster findet". Der Baustein taugt jetzt
  auch in **breiten** Tabellen — das war vorher ausdrücklich nicht so.
- `Design.md` 9.6 Plakette (AP1): eine begründete Abweichung — in dieser
  Kopfzeile darf sie umbrechen, weil sie dort einen Satz trägt, keine Vokabel.
- `Design.md` 9.12 (E-P3-27): Weg (b) ergänzt — Knopf markiert, Blatt fährt auf.
- P-P3-03 „null Unicode-Symbole": erreicht mit einer begründeten Ausnahme (`'✕'`-Rückfall).
- [Weiteres.]

## 2. Maschinell — Mittel und Zahl

| Mittel | Vorher (Backlog-Runde 3) | Soll | Ergebnis |
|---|---|---|---|
| `tools/vollstaendigkeit/pruefen.py` — `[offen]` | 2 | **0** | **0** ✓ (AP1) |
| — Unicode-Zeichen als Symbol, echte Treffer | **4**, nicht 3 (nachgemessen 13.09.2026: `einsatz_form.php:1617` Rückfall, `ortsfeld.js:244`, `patient.js:133`, dazu `einsatz_form.php:2227` als Escape-Folge, die das Mittel heute nicht sieht) | **0** echte; Ausnahme für den Rückfall in **`zusagen.md`** (nicht `ausnahmen.md` — E-MR-24); die Gesamtzahl fällt mit dem Ausblenden der Kommentare deutlich unter die heutigen **255** und wird genannt | [AP2] |
| — Hexfarben außerhalb `:root` | 0 | **0** (Hover des Chips als Token) | **0** ✓ (nach AP1) |
| — Symboldateien | 53 | **55** (E-MR-19: zwei Symbole), alle mit Anker `id="i"` und Verweis | 53 nach AP1 (AP3 legt sie an) |
| `tools/screenshots/aufnehmen.mjs` — Importvorschau, Einsatzformular, Suche, Tagesübersicht (klein/groß), Seiten mit `data-blatt` | — | 8 Breiten je Seite, **0** waagerechter Überlauf, Knopfhöhen ≥ 44 px | **AP1:** `--nur 35-` → **8 Einzelbilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe falscher Höhe** (Zeiger, 44/36 px). **Achtung, was das misst:** die Importseite im Grundzustand, nicht die Vorschau — siehe Abschnitt 0 |
| `tools/screenshots/kontrast.py` | 0 verfehlt | **0** verfehlt (Orange-Symbol auf `--orange-hell`, Blau-hell-Knopf auf Dunkelblau: Werte nennen) | **22 Paare gerechnet, 0 verfehlt** ✓ (nach AP1; AP1 führt keine neue Farbe ein) |
| `tools/stilvergleich/` (Browser) | — | Abweichungen nur auf den berührten Seiten, alle erklärt | **AP1:** Kaskade **0 entfallen, 14 neu, 0 anderer Endwert, 0 Reihenfolgeumkehrungen** — genau die 14 Deklarationen des Pakets. Berechnete Stile **45 812 Elementmessungen, 273 Abweichungen**, sämtlich an `.imp-kopfzeile`, `.imp-tag`, `.imp-rest`, der `.plakette` darin und den Hüllen (Gesamthöhe der Probe 24 302,7 → 24 299,6 px, 3,1 px kürzer durch den Flex-Fluss) |
| Klickprobe | 40 von 40 | **40 von 40** + [n] neue Wege (Blatt auf/zu, Karte groß/klein) | [AP5] |
| Kreisläufe csv/edbak (R24) | 0 / 0 | **0 unerklärt, 0 ungenutzt** | [AP5] |
| Wortliste | 0 / 0 | **0 / 0** | **0 / 0** ✓ (nach AP1; zwei Treffer im neuen `Design.md`-Beispiel sind beim Schreiben entstanden und sofort neutral gefasst worden) |

## 3. Im Browser

- **AP1 — Importvorschau, von Hand mit Playwright** (`import.php`, Datei aus
  dem Referenz-Export umgebaut, sodass alle drei Fälle vorkommen: vorhandener
  Diensttag mit abweichender Besatzung, neuer Diensttag, eine Zeile ohne
  verwertbares Datum). Gemessen bei 400, 720 und 1280 px:
  - Drei Kopfzeilen, alle mit `.imp-kopfzeile`. ✓
  - Datum in Kopfschrift (`Bricolage Grotesque`) und Dunkelblau
    (`rgb(26,46,77)`), deutsch formatiert. ✓
  - Zelle: Rauch (`rgb(247,245,237)`), Oberlinie **2 px**. ✓
  - Konfliktgruppe: `plakette plakette-orange` **mit** Symbol; die Gruppe der
    nicht zuordenbaren Zeilen: `plakette plakette-rot` **mit** Symbol und der
    Zahl der Zeilen. ✓
  - `.imp-warn` im DOM: **0**. ✓
  - Waagerechter Überlauf der **Seite**: **0** in allen drei Breiten. ✓
  - Konsolenfehler: **keine**. ✓
  - ~~**Nicht erfüllt:** Plakette und Auswahl in zwei Zeilen am Handy.~~
    **Erfüllt seit Web 19.4.1**, siehe den Block darunter.
- **Nr. 182 — die Kopfzeile im Sichtfenster** (Web 19.4.1, dieselbe
  Importdatei, Playwright). Gemessen bei **sieben** Fensterbreiten — 360, 400,
  720, 1024, 1280, 1600, 1920 px:
  - Kopfbreite **gleich** der sichtbaren Breite in jeder: 302 = 302, 342 = 342,
    654 = 654, 738 = 738, 954 = 954, 1274 = 1274, 1354 = 1354. ✓
  - Datum, Besatzung, Plakette **und** Auswahlfeld im Sichtfenster — in
    **allen sieben**. ✓
  - Höhe der Kopfzeile: 270 / 231 / 193 / 189 / 122 / 87 / 87 px — sie bricht
    also, und zwar je schmaler desto mehr. ✓
  - Die Fläche bleibt durchgehend: das `<td>` misst in jeder Breite 2677 px,
    nur sein Inhalt folgt dem Blick. ✓
  - Waagerechter Überlauf der **Seite**: **0** in allen sieben. ✓
  - Waagerecht um 1500 px gescrollt: Kopfzeile bleibt bei 0 px stehen, die
    Datenzeilen laufen durch. ✓
  - Konsolenfehler: **keine**. ✓
  - **Die drei Bedienwege am delegierten Behandler**, weil Weg C sie
    mitgerissen hätte: Zellbearbeitung — der getippte Wert `PROBE-4711`
    überlebt **zwei** Neuzeichnungen (steht also im Datenmodell, nicht nur im
    Feld) ✓ · Überspringen-Kästchen setzt `imp-skipped` ✓ · Tageswahl
    (`imp-daymode`) behält `update` ✓. **Eine** Tabelle, **ein** Element mit
    der Kennung `tabelle`. ✓
- Koordinaten-Chip: Ziel **28 × 28 px** (DevTools messen; F-MR-6b, E-MR-21), Hover sichtbar, Entfernen wirkt. **Beide** Chips — Koordinaten und beteiligte Rettungsmittel. [ ]
- Suche mit einem unlesbaren Eintrag: Symbol sitzt auf der Grundlinie in 19/15/13 px. [ ]
- Tagesübersicht: groß ↔ klein, Kacheln füllen nach `invalidateSize`; **ab 1600 px bleibt der Knopf und macht die Karte breit** (E-MR-16), mit Querpfeilen statt senkrechten (E-MR-19). [ ]
- Aktionsblatt auf `index.php` und in der Geräteliste (Zeilenaktion): Knopf markiert, Blatt fährt auf, `prefers-reduced-motion` ohne Bewegung. [ ]

## 4. Prüfliste — Auftraggeber

| # | Bedienweg | Erwartet | Wenn nicht |
|---|---|---|---|
| 1 | Am S24: Tagesübersicht, „⋯" tippen | Knopf färbt sich hell-orange (D4), Blatt kommt erkennbar von unten; nach „Abbrechen" beides zurück | Bewegung zu schnell/zu langsam: `--dauer` nachjustieren (nur der Wert) |
| 1a | Am S24: ☰ tippen (Schublade), ein Akkordeon auf- und zuklappen | Beides in 240 ms — fühlt sich gleich an wie das Blatt, nicht träge | Wenn träge: `--dauer` zurück auf .18s und fürs Blatt doch ein eigener Wert — dann als Entscheidung ins Konzept |
| 2 | Am S24: Tagesübersicht, Kartenknopf „vergrößern" | Karte wird deutlich höher, Liste bleibt darunter erreichbar; nach Neuladen bleibt der Zustand | Höhe unpassend: F-MR-7 nachjustieren **Ab 1600 px macht derselbe Knopf die Karte breit statt hoch** (E-MR-16) — am Gerät nicht prüfbar, dafür am Schreibtisch nachsehen |
| 3 | CSV-Import mit einer Datei, deren Crew von einem gespeicherten Tag abweicht | Tagesgruppe hat Kopfzeile, Warnung als orange Plakette mit Symbol — **und am Handy ist alles davon ohne waagerechtes Scrollen zu sehen** (seit Web 19.4.1, Nr. 182) | Wenn die Kopfzeile fehlt oder wie eine Datenzeile aussieht: AP1 hat nicht gegriffen. Wenn nach dem Datum nichts mehr kommt: die Container-Abfrage greift auf diesem Browser nicht — siehe Punkt 6 |
| 4 | Einsatz bearbeiten, Koordinaten setzen, Chip-`×` tippen | Ziel trifft sich leicht (**28 px**, F-MR-6b), Koordinaten weg, Textfeld bleibt | Ziel zu klein: F-MR-6b nachjustieren |
| 5 | Freigabe des Abschlusses | — | — |
| 6 | **In Firefox** (Handy genügt): Importvorschau mit derselben Datei öffnen. *Chromium 141 und WebKitGTK 2.52.6 sind bereits belegt — es fehlt nur Gecko* | Die Kopfzeile bricht um und steht vollständig im Bild | Steht nach dem Datum nichts: Der Browser kennt keine Container-Abfragen. Kein Absturz, kein Datenverlust — es ist der Zustand von Web 19.4.0. Dann im Konzept vermerken und `--sicht` über einen `ResizeObserver` nachrüsten (die Fassung ist gemessen und liegt im Prüfprotokoll) |

## 5. Grenzen · 6. Offen

- Der Bilderlauf misst Ruhezustände; die Bewegung ist nur am Gerät zu bewerten.
- **Nr. 182 ist in ZWEI Engines gemessen** — Chromium 141 (Playwright) und
  WebKitGTK 2.52.6 (`xvfb-run -a /usr/bin/python3.12 webkit-mess.py`, gegen
  einen Schnappschuss der echten Vorschau). **Gecko fehlt**, weil die
  Browser-Downloads gesperrt sind; Backlog **Nr. 183**.
- **Fehlerfund 1** (Konzept Abschnitt 6): dasselbe Malzeichen als
  JavaScript-Escape im zweiten Chip — das Prüfmittel sieht Escape-Folgen
  nicht. Läuft in **AP2** mit (E-MR-24), keine eigene Backlog-Nummer.
- **Fehlerfund 2** (aus AP1): die Kopfzeile außerhalb des Sichtfensters —
  **Backlog Nr. 182**, Entscheidung steht aus. Abschnitt 0.
