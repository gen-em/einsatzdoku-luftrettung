# Prüfdokument — Mockup-Runde

*Angelegt am 13.09.2026 (Fable) mit `Konzept-Mockup-Runde.md` als Vorlage;
**die Zahlen trägt die Umsetzung ein**. Fortgeschrieben nach jedem
Arbeitspaket — was noch `[eckige Klammern]` trägt, ist noch nicht gemessen.*

> **Stand: AP1, AP2, AP3 und AP3b gemessen, Nr. 182 behoben. AP4 und AP5
> offen.**
>
> **Seit dem 14.09.2026 hat der Prüfstand drei Engines** — Chromium 141,
> Firefox 142, WebKit 26 (Backlog Nr. 183, der Startvorgang beschafft sie).
> Nr. 182 und Nr. 42 sind in allen dreien gemessen; **Prüflistenpunkt 6
> entfällt damit**. **Seit AP3b fahren die Prüfmittel sie selbst**
> (`--motor`, Nr. 183 ganz erledigt); wie oft welches dreifach fährt, steht
> in `docs/Technik.md`.
>
> **Vier Sollwerte der Vorlage waren veraltet** — sie entstand vor E-MR-16,
> E-MR-19 und E-MR-21 — und sind hier berichtigt: Symboldateien **55** statt
> 54 (E-MR-19 verlangt zwei Symbole), Chip-Ziel **28 px** statt 24
> (F-MR-6b), ab 1600 px **bleibt der Knopf** (E-MR-16), und F-MR-6 ist durch
> 6a/6b ersetzt. Die Änderungen stehen im Konzept, Abschnitt 5.

> | | |
> |---|---|
> | Stufe | **Web 19.5.1** — 19.4.0 Nebenstufe (AP1), 19.4.1 Korrektur (Nr. 182), 19.4.2 AP2, 19.5.0 AP3, 19.5.1 Korrektur (Nr. 185, aus AP3b). Keine Migration. Uhr und Android unberührt |
> | Punkte | Backlog **Nr. 41, 42, 45, 182, 183, 185 und 186 erledigt**; 124 offen. **Nr. 184 neu** (Kommentar-Abtaster) |
> | Neu entstanden | Token `--symbol-text`, `--karte-gross`; **`--dauer` von .18s auf .24s** (alle Bewegungen); Klassen `.symbol-text`, `.geo-gross`; Symbole `karte-gross.svg`, `karte-breit.svg` (54., 55.); Regel `.imp-daygroup`; Knopf offen = `--orange-hell`/`--orange-tief` (D4); Ausnahme `'✕'` in `ausnahmen.md`; Streichliste `imp-warn` |
> | Prüfumgebung | Wegwerf-Container: PHP 8.4.19, MariaDB 10.11.14, Node 22.22.2, Playwright 1.56.1, **Chromium 141.0.7390.37 · Firefox 142.0.1 · WebKit 26.0**. Lokale Installation über `lokal_einrichten.sh` (88 Einsätze, 16 Diensttage, 2 Geräte) |
> | Ergebnis | AP1 **grün, Vorbehalt aufgelöst** (Nr. 182 behoben). Rest offen |

## 0. Was NICHT geprüft werden konnte

- ~~**AP1: Die Kopfzeile der Tagesgruppe ist NICHT sichtbar.**~~ **Erledigt
  mit Web 19.4.1** (14.09.2026, Backlog Nr. 182, F-MR-14 = Weg B). Die
  Kopfzeile nimmt über eine Container-Abfrage die **sichtbare** Breite an; die
  Abnahme von AP1 („am Handy bricht die Kopfzeile in zwei Zeilen") ist damit
  nachträglich erfüllt. Zahlen in Abschnitt 3.
- ~~**Nr. 182 nur in Chromium gemessen.**~~ **Erledigt: alle drei Engines.**
  Der Auftraggeber hat am 14.09.2026 die beiden Downloadadressen freigegeben
  (`cdn.playwright.dev`, `playwright.download.prss.microsoft.com`, bis dahin
  403); seither liegen Chromium 141, Firefox 142 und WebKit 26 im Prüfstand,
  und der Startvorgang beschafft sie. Zahlen in Abschnitt 3.
- ~~**Kein Prüfmittel fährt die drei.**~~ **Erledigt mit AP3b** (14.09.2026).
  Bilderlauf, Klickprobe und Stilvergleich kennen `--motor`; Motorwahl und
  Firefox-Voreinstellung liegen in `tools/motor.mjs`. Zahlen in Abschnitt 2
  und 3.
- **Was dabei NICHT geprüft werden konnte: echtes Safari und echter Firefox
  auf einem Gerät.** Der Prüfstand fährt Playwrights **WebKit** — derselbe
  Kern wie Safari, aber ein anderer Unterbau (Schriften, Textrasterung,
  Systemintegration) — und Firefox **headless**. Ein Befund von dort ist ein
  starkes Indiz, kein Beweis; `mousedown` auf iOS bleibt Punkt 6a der
  Prüfliste.
- **Und die Klickprobe misst dreifach nur mit frischem Bestand.** Sie legt
  an, ändert und löscht; drei Läufe hintereinander ohne
  `lokal_einrichten.sh` ergaben **40 / 38 / 36** von 40, und keiner der sechs
  Fehlschläge war ein Motorunterschied. Wer die Zahl aus einem solchen Lauf
  zitiert, zitiert Datenreste.
- **Und eine Zahl, die kleiner aussehen könnte, als sie ist.** Die
  Symbolprüfung meldet 252 Unicode-Zeichen. Mit ausgeblendeten Kommentaren
  wären es 108 — aber der Abtaster dafür verschluckt in `einsatz_form.php`
  rund **800 Zeilen am Stück** (Backlog **Nr. 184**), und dieselbe Schwäche
  trifft die drei Zusagen-Prüfungen aus Backlog-Runde 3, die dann **falsche
  Negative** liefern. Die 252 sind deshalb Absicht.
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
| — Unicode-Zeichen als Symbol | **4** echte, nicht 3 (nachgemessen: `einsatz_form.php` zweimal — Rückfall und Escape-Folge —, `ortsfeld.js`, `patient.js`) | **0** echte | **0** ✓ — alle vier namentlich weg. Gesamtzahl **255 → 252**; die dreizehn verbliebenen nicht-typografischen stehen in Kommentaren oder im Satz. **Keine Ausnahme nötig** (AP2): Der Rückfall in `wegKnopf()` war toter Code und ist entfallen |
| — Hexfarben außerhalb `:root` | 0 | **0** (Hover des Chips als Token) | **0** ✓ (nach AP1) |
| — Symboldateien | 53 | **55** (E-MR-19: zwei Symbole), alle mit Anker `id="i"` und Verweis | **55** ✓ (AP3), beide mit Anker und Verweis |
| `tools/screenshots/aufnehmen.mjs` — Importvorschau, Einsatzformular, Suche, Tagesübersicht (klein/groß), Seiten mit `data-blatt` | — | 8 Breiten je Seite, **0** waagerechter Überlauf, Knopfhöhen ≥ 44 px | **AP1:** `--nur 35-` → **8 Einzelbilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe falscher Höhe** (Zeiger, 44/36 px). **Achtung, was das misst:** die Importseite im Grundzustand, nicht die Vorschau — siehe Abschnitt 0 |
| `tools/screenshots/kontrast.py` | 0 verfehlt | **0** verfehlt (Orange-Symbol auf `--orange-hell`, Blau-hell-Knopf auf Dunkelblau: Werte nennen) | **22 Paare gerechnet, 0 verfehlt** ✓ (nach AP1; AP1 führt keine neue Farbe ein) |
| `tools/stilvergleich/` (Browser) | — | Abweichungen nur auf den berührten Seiten, alle erklärt | **AP1:** Kaskade **0 entfallen, 14 neu, 0 anderer Endwert, 0 Reihenfolgeumkehrungen** — genau die 14 Deklarationen des Pakets. Berechnete Stile **45 812 Elementmessungen, 273 Abweichungen**, sämtlich an `.imp-kopfzeile`, `.imp-tag`, `.imp-rest`, der `.plakette` darin und den Hüllen (Gesamthöhe der Probe 24 302,7 → 24 299,6 px, 3,1 px kürzer durch den Flex-Fluss) |
| Klickprobe | 40 von 40 | **40 von 40** + [n] neue Wege (Blatt auf/zu, Karte groß/klein) | [AP5] |
| Kreisläufe csv/edbak (R24) | 0 / 0 | **0 unerklärt, 0 ungenutzt** | [AP5] |
| Wortliste | 0 / 0 | **0 / 0** | **0 / 0** ✓ (nach AP1; zwei Treffer im neuen `Design.md`-Beispiel sind beim Schreiben entstanden und sofort neutral gefasst worden) |
| **AP3b — Stilvergleich in drei Motoren** | — | dieselbe Zahl in allen dreien | **Kaskade** 758 → 759 Regeln, **0 entfallen, 1 neu** (`select.feld-eingabe contain: paint`), **0 anderer Endwert, 0 Reihenfolgeumkehrungen**. **Berechnete Stile** je Motor **46 150 Elementmessungen, 104 Abweichungen** — 8 Auswahlfelder × 13 Breiten, einzige geänderte Eigenschaft `contain: none → paint`; Pseudoprobe **20 540 Messungen, 13 Abweichungen**. Chromium, Firefox und WebKit **identisch**, 14–18 s je Motor ✓ |
| **AP3b — Klickprobe in drei Motoren** | 40 von 40 (Chromium) | 40 von 40 je Motor | **40 / 40 / 40** ✓ — mit `lokal_einrichten.sh` (8 s) vor **jedem** Lauf, und bei Firefox und WebKit **erst im zweiten Anlauf**. Die drei Läufe im Einzelnen stehen in Abschnitt 3; **kein** Fehlschlag war ein Motorunterschied, alle waren Datenreste des Vorlaufs oder Zeitgrenzen. Der eine echte Motorbefund — WebKit meldete „1 von 12" bei den Richtungspfeilen — war ein Fehler der **Probe** (Nr. 186) und ist behoben |
| **AP3b — Bilderlauf in drei Motoren** | — | 0 Überlauf / 0 Konsolenfehler / 0 falsche Knopfhöhen je Motor | **Chromium voll: 360 Einzelbilder, 45 Kontaktbögen, 0 / 0 / 0** in 8 min 37 s. **Firefox `--risiko`: 80 Bilder, 0 / 0 / 0** in 2 min 36 s. **WebKit `--risiko`: 80 Bilder, 0 / 0 / 0** in 2 min 59 s ✓ — und das ist der Lauf **nach** der Behebung von Nr. 185; davor meldete WebKit als einziger „Überlauf bei 360" auf `import.php` |

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
- **AP2 (Nr. 42) — in DREI Engines** (Chromium 141, Firefox 142, WebKit 26),
  Einsatzformular und Startseite, 1280 px:
  - Chip: **3 von 3** mit SVG, **0** mit Zeichen; Symbol **12 × 12 px**; Ziel
    **28 × 28 px** und rund; Abstand **6 px** zum Text und **6 px** zum
    Chiprand — in allen drei Engines dieselben Zahlen. ✓
  - Chiphöhe 28,1 / 28,1–28,2 / 28,0 px — **unverändert gegenüber vorher**
    (nachgemessen am Stand davor: ebenfalls 28,1). Die „26 px" des Mockups
    waren gezeichnet. ✓
  - Das Treffziel ist von **17 × 15 px auf 28 × 28 px** gewachsen. ✓
  - Entfernen-Knöpfe der Phasen- und Reanimationszeilen: **8 von 8** mit SVG,
    **0** mit Zeichen — der Rückfall war tot. ✓
  - Meldung: SVG mit `symbol symbol-text`, **15 × 15 px** bei 15 px Schrift
    (also `1em`), Farbe `rgb(194, 90, 0)` = `--orange-tief`; **kein rohes
    Markup** im Text sichtbar. ✓
  - Konsolenfehler: **keine** in Chromium und WebKit. **Firefox meldet
    abgebrochene `latin-ext`-Schriftabrufe** (`NS_BINDING_ABORTED`) — ein
    Merkmal des Prüfstands, kein Anwendungsfehler: Bei ruhiger Seite lädt er
    alle fünf Schnitte, und der Chip misst dann 28,1 px wie in Chromium.
    Nachgemessen; steht in `tools/screenshots/LIESMICH.md` für den, der die
    Prüfmittel auf drei Engines bringt. ⚠
- Koordinaten-Chip: Ziel **28 × 28 px** (DevTools messen; F-MR-6b, E-MR-21), Hover sichtbar, Entfernen wirkt. **Beide** Chips — Koordinaten und beteiligte Rettungsmittel. [ ]
- Suche mit einem unlesbaren Eintrag: Symbol sitzt auf der Grundlinie in 19/15/13 px. [ ]
- **AP3 — Tagesübersicht, in drei Engines** (Chromium 141, Firefox 142,
  WebKit 26), je fünf Fensterbreiten, fünfzehn Messungen mit demselben Bild:
  - klein 160 / 220 / 300 px unter 1600 px, 820–864 px in der Spalte darüber. ✓
  - groß **520 px** in **jeder** Breite (`min(60vh, 520px)` bei 900 px Glas). ✓
  - ab 1600 px wechselt „groß" vom Raster in den Fluss (`display: grid → block`)
    und nimmt die volle Inhaltsbreite (1308 / 1388 px). ✓
  - Symbol wechselt von senkrecht auf quer genau an der Schwelle. ✓
  - `aria-pressed` folgt dem Zustand; die Liste steht in allen fünfzehn
    Messungen **unter** der Karte. ✓
  - **0** waagerechter Überlauf, **keine** Konsolenfehler. ✓
  - Nach dem Neuladen steht die Karte wieder groß (520 px) — `localStorage`
    trägt. ✓
  - Kacheln nach dem Umschalten: 10 → **15**, davon 5 am Unterrand, **0 px
    unbedeckt**, schon nach 200 ms. `invalidateSize()` greift. ✓
  - **Berichtigt:** Meine erste Messung meldete „410 px Kachellücke" — das war
    ein Messfehler, `.leaflet-tile-pane` hat kein aussagekräftiges Rechteck.
    An den Kacheln selbst gemessen ist nichts unbedeckt.
- **AP3b — die drei Motoren, und was dabei wirklich passiert ist.** Nicht
  „dreimal grün", sondern drei Läufe mit Zahlen:

  | Lauf | Vorbereitung | Chromium | Firefox | WebKit |
  |---|---|---|---|---|
  | 1 | einmal eingespielt, dann drei Läufe | 40/40 | 38/40 | 36/40 |
  | 2 | `lokal_einrichten.sh` vor jedem Lauf | 40/40 | 39/40 | 36/40 |
  | 3 | dasselbe, Firefox und WebKit erneut | — | **40/40** | **40/40** |

  **Lauf 1** zeigt, was passiert, wenn man es falsch macht: Die Probe legt an,
  ändert und löscht, und Lauf 2 und 3 lesen die Reste („3 Rettungsmittel ohne
  Standort statt 2, 7 insgesamt statt 6"). **Lauf 2** hat das behoben und
  trotzdem fünf Fehlschläge — einen Zeitgrenzenlauf in Firefox
  (`waitForFunction` auf `#savestate`) und vier `page.goto: WebKit
  encountered an internal error`. **Nachgemessen, ob Firefox zu langsam ist:
  nein.** Dreimal speichern je Motor, gemessen vom Klick bis „Gespeichert."
  im Feld: Chromium 95/69/72 ms, Firefox 137/99/140 ms, WebKit 91/90/77 ms —
  die Zeitgrenze steht bei 15 000 ms. Es ist also keine Langsamkeit, sondern
  Aussetzer des Containers; sie sind **nicht** reproduzierbar (derselbe Weg
  einzeln gefahren lief in Firefox durch).
  **Was daraus folgt, steht in `tools/klickprobe/LIESMICH.md`:** frischer
  Bestand vor jedem Lauf, und die Zahl eines Laufs ohne ihn ist keine Zahl.
- **AP3b — der eine Motorbefund der Anwendung:** `import.php` bei 360 px,
  Überlauf **6 px in WebKit**, 0 in Chromium und Firefox. Ursache und
  Behebung stehen im Changelog zu Web 19.5.1; nachgemessen nach der Behebung
  **0 / 0 / 0**. Die Kosten der Regel: fokussierter Ausschnitt 318 × 60 px,
  Firefox bitgleich, Chromium und WebKit **1 von 19 080 Pixeln** verschieden
  bei einer Abweichung von 6 von 255.
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
| 6a | **Am iPhone (echtes Safari): Import öffnen, eine Datei wählen, waagerecht wischen** | Die Seite lässt sich **nicht** seitwärts schieben; die Kopfzeile jeder Tagesgruppe bleibt am linken Rand stehen | Wenn sich die Seite um ein paar Pixel schieben lässt: Die Regel aus Web 19.5.1 (`select{contain:paint}`) greift auf echtem Safari nicht so wie auf Playwrights WebKit — dann mit der Breite melden, an der es auftritt. Wenn nach dem Datum nichts mehr kommt: Die Container-Abfrage greift dort nicht (Nr. 182) |
| 6b | **Am iPhone: einen Chip-`×` mit dem Daumen antippen, Taste kurz halten** | Der Chip verschwindet | Auf iOS ist `mousedown` eine eigene Geschichte; der Prüfstand fährt eine Maus und kann das nicht zeigen |
| ~~6~~ | ~~In Firefox nachsehen~~ | **Entfällt.** Seit dem 14.09.2026 hat der Prüfstand alle drei Engines; Nr. 182 und Nr. 42 sind in Chromium 141, Firefox 142 und WebKit 26 gemessen und stimmen überein | — |

## 5. Grenzen · 6. Offen

- Der Bilderlauf misst Ruhezustände; die Bewegung ist nur am Gerät zu bewerten.
- **Die Prüfmittel fahren die drei Engines seit AP3b selbst** (`--motor`,
  Backlog Nr. 183 vollständig erledigt). Zwei Grenzen bleiben: Playwrights
  **WebKit ist nicht Safari** (gleicher Kern, anderer Unterbau) und Firefox
  läuft **headless**; und der Container hat **Aussetzer** — vier
  `page.goto`-Abbrüche in einem WebKit-Lauf, im nächsten keiner.
- **AP4 ist noch in keiner Engine geprüft** — es ist nicht gebaut.
- **Fehlerfund 1** (Konzept Abschnitt 6): dasselbe Malzeichen als
  JavaScript-Escape im zweiten Chip — das Prüfmittel sieht Escape-Folgen
  nicht. Läuft in **AP2** mit (E-MR-24), keine eigene Backlog-Nummer.
- **Fehlerfund 2** (aus AP1): die Kopfzeile außerhalb des Sichtfensters —
  **Backlog Nr. 182**, Entscheidung steht aus. Abschnitt 0.
