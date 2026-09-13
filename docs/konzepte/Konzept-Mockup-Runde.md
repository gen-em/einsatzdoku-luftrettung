# Konzept — Mockup-Runde (Fahrplan Schritt 9c)

*Erstellt am 13.09.2026 (Fable) gegen `gen-em/einsatzdoku-luftrettung`, Zweig
`main`, Stand **Web 19.3.0 · Uhr 3.1.0 · Android 0.15.0**. Grundlage:
Entscheidungen 7, 8, 9, 11 und 15 vom 12.09.2026
(`Backlog-Durchsicht-2026-09-12.md`) und Rahmenplan Fassung 46 (Schritt 9c).
Format nach K1; die Mockups liegen in `konzept-mockup-runde/mockups/`
(HTML mit den Token aus `style.css`, PNG in 1280 und 400 px). Prüfdokument
nach K9 als Vorlage daneben.*

> | | |
> |---|---|
> | Paket | **Mockup-Runde** — vier Gestaltungsaufgaben, eine Freigaberunde statt vier (Entscheidung 15) |
> | Punkte | **Nr. 41** (zwei Regeln in der Importvorschau), **Nr. 42** (zwei Zeichen werden Symbole), **Nr. 45** (dritte Kartengröße), **Nr. 124** (Aktionsblatt, Weg b) |
> | Stand | **FREIGEGEBEN am 13.09.2026** — alle dreizehn Fragen beantwortet, Mockups in Fassung 4. Die Umsetzung (AP1–AP5) kann beginnen. Freigegeben: F-MR-1 = A, F-MR-2, F-MR-3, F-MR-4, F-MR-5, F-MR-6a = C (Symbol zentriert), F-MR-7, F-MR-8, F-MR-9 (geändert: Knopf auch ab 1600 px, macht die Karte breit), F-MR-10. Dazu F-MR-6b = 28 px, F-MR-11 = **D4**, F-MR-12 = **240 ms für die ganze Anwendung** (`--dauer`), F-MR-13 = ja. |
> | Danach | vier Arbeitspakete für Opus (Abschnitt 4), eine Web-Stufe; Uhr und Android unberührt |
> | Rutschklausel | Nr. 124 ist der einzige Punkt aus einer Rückmeldung von außen und darf die Runde verlassen (AP4 ist allein baubar) |
> | Versionsnummer | legt die Umsetzung fest (K3) |

> **Statusblock — Umsetzung** (Opus, Zweig `claude/jolly-planck-vexxpd`;
> fortgeschrieben nach jedem Arbeitspaket, danach gepusht)
>
> | | |
> |---|---|
> | In Arbeit | **AP2** (Nr. 42) — als Nächstes. Backlog-Runde 3 ist am 13.09.2026 gemergt (PR #43, `8f1712c`, Web 19.3.1) und in den Arbeitszweig geholt; sie hat drei der fünf `[offen]`-Klassen gestrichen und bringt mit `zusagen.md` die Mechanik, die AP2 braucht (E-MR-24) |
> | Erledigt | **AP1** (Nr. 41, Web 19.4.0, 13.09.2026) — `imp-daygroup` hat eine Regel, `imp-warn` ist gestrichen. Gemessen `[offen]` **2 → 0**, „ohne Gegenstück" **52 → 50**, Befunde **330 → 326**. **Dazu Nr. 182** (Web 19.4.1, 14.09.2026, F-MR-14 = Weg B): die Kopfzeile nimmt über eine Container-Abfrage die sichtbare Breite an, **ohne JavaScript**; gemessen bei 7 Breiten, alle vier Teile im Sichtfenster, 0 Überlauf, keine Konsolenfehler |
> | Haken | **keiner offen.** Fehlerfund 2 ist mit Web 19.4.1 erledigt (Nr. 182). Die Abnahme von AP1 — „am Handy bricht die Kopfzeile in zwei Zeilen" — ist damit **nachträglich erfüllt**: Sie war in 19.4.0 unerfüllbar, weil `flex-wrap` in einer 2653 px breiten Zeile nie greift; seit 19.4.1 ist die Zeile so breit wie das Sichtfenster und bricht |
> | Stufe | **Web 19.4.1** — 19.4.0 war die Nebenstufe aus AP1, 19.4.1 die Korrektur zu Nr. 182. AP2 bis AP4 setzen darauf auf. Uhr und Android unberührt |

---

## 1. Befund — am Code gelesen, 13.09.2026

**Nr. 41 — Importvorschau.** `assets/import_ui.js` baut je Tag eine Zeile
`<tr class="imp-daygroup"><td colspan>` mit `<strong>Datum</strong> · Crew ·
n Einsätze · Diensttag vorhanden/wird angelegt`; bei abweichender Crew
folgt `<span class="imp-warn">abweichende Crew (…)</span>` und ein
`<select class="imp-daymode">`. Beide Klassen haben keine Regel
(`ohne-regel.md`, `[offen]`). Vorhandene Bausteine, die passen:
`.plakette-orange` (Zustand, der Aufmerksamkeit will) mit Symbol `warnung`;
für Gruppenköpfe gibt es keinen Baustein — `.phasen-zwischentitel` ist die
nächste Verwandte (Kopfschrift, Dunkelblau, `--groesse-3`).

**Nr. 42 — zwei Zeichen.** `.rmx` (`style.css`: `border:0; padding:0 4px;
font-size:var(--groesse-3); line-height:1`) trägt `×` als Text im
13-px-Chip `.rmchip`; das Ziel ist so groß wie das Zeichen. `patient.js`
setzt `ZEICHEN_UNLESBAR = '⚠'` in den Satz der Meldung („… und ist mit ⚠
gekennzeichnet") und als Marke in der Zelle. Symbole `schliessen.svg` und
`warnung.svg` liegen im Vorrat. **Einschränkung am Code:** `zeigeUnlesbar()`
sagt, dass `symbol.js` auf dieser Seite nicht in jedem Fall geladen ist —
das SVG-Markup wird dort als Zeichenkette gebaut, nicht über `edSymbol()`.
Die Bauart `.plakette-weg` (Entfernen-Ziel in einer Plakette, 20 px) ist
das Muster für den Chip.

**Nr. 45 — Kartengröße.** `index.php`: `.geo` mit `--karte-mobil` 160 px,
ab 720 px `--karte-tablet` 220, ab 1200 `--karte-desktop` 300, ab 1600 in
der rechten Spalte so hoch wie der Inhalt (`.geo-spalte .geo{height:100%}`);
Vollbild über `assets/map_fullscreen.js` (`.geo.map-fs`, Symbol
`vollbild`). Ein Zwischenzustand fehlt; es gibt kein Token und kein Symbol
dafür.

**Nr. 124 — Aktionsblatt.** `.blatt{position:fixed;inset:auto 0 0 0}` ohne
Übergang; `blatt.js` setzt beim Öffnen `hidden=false` und
`aria-expanded="true"` am Knopf `[data-blatt]`; am Knopf hängt heute nur die
Drehung des Winkels (`.aktionen-knopf[aria-expanded="true"] .symbol:last-child`).
Zwei Erzeuger, ein Markup: `ui_aktionen()` (Knopf `.aktionen-knopf`,
6 Aufrufe) und `ui_zeilenaktionen()` (Knopf `.knopf-symbol`, 9 Aufrufe).
Ab 1024 px dasselbe Markup als Aufklappmenü — dort ist nichts zu ändern.

---

## 2. Entscheidungen

| Nr. | Entscheidung | Quelle |
|---|---|---|
| **E-MR-01** | Nr. 41: `imp-warn` und `imp-daygroup` bekommen eine Regel; die drei Reste (`rea-kopf`, `rea-beginn`, `phasen-name`) werden in Backlog-Runde 3 gestrichen | Entscheidung 7; Konzept BR3 E-BR3-03 |
| **E-MR-02** | Nr. 42: `×` und `⚠` werden SVG; `✕` in `wegKnopf()` bleibt als begründete Ausnahme | Entscheidung 8 |
| **E-MR-03** | Nr. 45: bauen, Mockup zuerst — mittlere Fassung über die volle Breite, über der Liste | Entscheidung 9 |
| **E-MR-04** | Nr. 124: Weg (b) — Blatt bleibt unten, „⋯" bleibt hervorgehoben, Blatt fährt sichtbar aus seiner Richtung auf | Entscheidung 11 |
| **E-MR-05** | Alle vier in **einer** Freigaberunde; Nr. 124 darf sie verlassen, wenn sie rutscht | Entscheidung 15; Rahmenplan 9c |
| **E-MR-06** | Die Mockups zeigen **Ist gegen Vorschlag mit Maßen**; jede neue Größe, Klasse, Farbe oder jedes neue Symbol ist als solches benannt (`Design.md` 1.2) | Fable 13.09.2026 |
| **E-MR-07** | Die Änderung zu Nr. 124 sitzt **im Baustein** (`blatt.js`, Stylesheet Abschnitt 10) und erreicht damit beide Erzeuger; ob die Markierung auch für die Zeilenaktionen gilt, ist F-MR-13 | Fable 13.09.2026 |
| **E-MR-09** | Nr. 41: **Variante A** — Kopfzeile neu, Warnung als `.plakette-orange`, `imp-warn` entfällt; Datum als „12.09.2026" (F-MR-1, F-MR-2) | Auftraggeber 13.09.2026 |
| **E-MR-10** | Nr. 42: Warnzeichen als Textsymbol `1em` auf der Grundlinie in `--orange-tief` (F-MR-4, F-MR-5) | Auftraggeber 13.09.2026 |
| **E-MR-11** | Nr. 42, Chip: der 16-px-Vorschlag ist **verworfen** (zu groß, zu dicht). Eine Regel für **beide** Chips — Koordinaten und beteiligte Rettungsmittel teilen `.rmchip`/`.rmx`; Sichtgröße wie das kleine Zeichen, Abstand 8 px, größeres Ziel. Ob SVG (C) oder Textzeichen (C′): F-MR-6a | Auftraggeber 13.09.2026; Fable |
| **E-MR-12** | Nr. 124: Auffahren wie gezeigt; Knopfmarkierung als **Füllung und oranger Ring** (Variante C) zur Freigabe vorgelegt | Auftraggeber 13.09.2026 |
| **E-MR-13** | Nr. 41: „Nicht zuordenbar (n)" bekommt dieselbe Kopfzeile mit `.plakette-rot` (F-MR-3) | Auftraggeber 13.09.2026 |
| **E-MR-14** | Nr. 42, Chip: **Variante C** — `schliessen` in 12 px, **in der Mitte** eines runden Ziels, 8 px Abstand zum Text, Chip bleibt 26 px; eine Regel für Koordinaten- und Rettungsmittel-Chips (F-MR-6a). Zielmaß: F-MR-6b | Auftraggeber 13.09.2026 |
| **E-MR-15** | Nr. 45: Höhe `--karte-gross: min(60vh, 520px)`, Zustand je Gerät gemerkt, Symbol `karte-gross.svg` aus Tabler „arrows-vertical" (F-MR-7, 8, 10) | Auftraggeber 13.09.2026 („Rest passt") |
| **E-MR-16** | Nr. 45, ab 1600 px: **der Knopf bleibt** und macht die Karte **breit** — sie verlässt die rechte Spalte und liegt in voller Inhaltsbreite über der Liste, 520 px hoch, wie „groß" bei 1200–1599 px. Ein Zustand, ein Knopf, zwei Wirkungen je Breite (F-MR-9, geändert gegenüber Fassung 1) | Auftraggeber 13.09.2026 |
| **E-MR-17** | Nr. 124: Ring in `--orange` auf `--blau-hell` ist zu schwach (1,9 : 1). Zur Wahl gestellt: C1 Ring 2 px `--orange-tief` (3,7 : 1 gegen die Füllung, 3,1 : 1 gegen den Kopf), C2 Ring 3 px `--orange`, C3/C4 dieselben mit orangen Punkten. Fable empfiehlt C1 | Auftraggeber 13.09.2026; Fable |
| **E-MR-18** | Nr. 42, Chip: das Symbol hat **gleich viel Abstand zum Text wie zum Chiprand** — 6 px beidseits (Chip-Polster rechts 6 statt 8 px, Abstand Text→Symbol 6 px); das 28-px-Ziel bleibt um das Symbol zentriert und ragt unsichtbar 8 px über den Rand | Auftraggeber 13.09.2026 (Skizze) |
| **E-MR-19** | Nr. 45, ab 1600 px: der Knopf zeigt **Querpfeile** (`karte-breit.svg`, Tabler „arrows-horizontal"), bis 1599 px die senkrechten Pfeile — beide Symbole im Knopf, das Stylesheet blendet je Breite eins ein. Damit **zwei** neue Symbole (54., 55.) | Auftraggeber 13.09.2026 |
| **E-MR-20** | Nr. 124: Ringfassungen C1–C4 überzeugten nicht. Zur Wahl: der offene Knopf **orange hinterlegt** — D1 `--orange` mit dunkelblauen Punkten (6,0 · 6,0), D2 `--orange` mit weißen Punkten (2,3 — fällt durch), D3 `--orange-tief` mit weißen Punkten (3,1 · 4,4), D4 `--orange-hell` mit Punkten `--orange-tief` (11,7 · 3,8). Fable: wenn Orange, dann D1 oder D4; Hinweis auf `Design.md` 3.1 (Orange = Handlung) bleibt stehen | Auftraggeber 13.09.2026; Fable |
| **E-MR-21** | Nr. 124: der offene Knopf ist **orange hinterlegt, Fassung D4** — Füllung `--orange-hell`, Punkte `--orange-tief` (Kontrast 11,7 : 1 gegen den Kopf, 3,8 : 1 Punkte gegen Füllung); kein Ring. Am Schreibtisch dieselbe Füllung am „Aktionen"-Knopf. Chip-Ziel 28 px (F-MR-6b), Chip und Karte (Fassung 4) als „perfekt" freigegeben | Auftraggeber 13.09.2026 |
| **E-MR-22** | **`--dauer` wird `.24s`** — für die ganze Anwendung, nicht nur fürs Blatt (F-MR-12: „alles auf 240 ms"). Betroffen sind alle Nutzer des Tokens: Schublade und Schleier, Akkordeon-Winkel, Winkel am „Aktionen"-Knopf, Schalter-Griffe, Kennzahlen-Winkel — und neu das Blatt. Kein zweites Token; `prefers-reduced-motion` bleibt ohne Bewegung | Auftraggeber 13.09.2026 |
| **E-MR-23** | Die orange Markierung (D4) gilt für **beide** Knöpfe — `ui_aktionen()` und `ui_zeilenaktionen()` — über eine Regel an `[data-blatt][aria-expanded="true"]` (F-MR-13) | Auftraggeber 13.09.2026 |
| **E-MR-08** | Die Mockups sind HTML mit den echten Token, gerendert mit `wkhtmltoimage` (wie S9); Kartenhintergrund ist eine Attrappe, keine Kacheln | Fable 13.09.2026 |
| **E-MR-28** | Nr. 182: **Weg B, und zwar ohne JavaScript** (F-MR-14). Die im Mockup und im Backlog behauptete Notwendigkeit einer *gemessenen* Breite war **falsch**: `container-type:inline-size` am Rollbereich macht `100cqi` zur sichtbaren Breite, und beide Fassungen (Container-Abfrage und `ResizeObserver`) sind bei sechs Fensterbreiten auf den Pixel gleich gemessen. Die Eigenschaft sitzt an einer **eigenen Klasse** `.imp-roll`, nicht an `.tabelle-scroll` — die trägt neun Stellen auf sechs Seiten, und `container-type` bringt Containment mit | Opus 14.09.2026 (Messung); Auftraggeber 14.09.2026 (Freigabe) |
| **E-MR-27** | Nr. 182: **Weg C ist verworfen**, obwohl er am 13.09.2026 gewählt war. Grundlage ist eine Kartierung mit fünf Linsen (17 Agenten): **58 Befunde, 22 davon „bricht"**, alle drei Entwürfe von allen drei Skeptikern widerlegt. Ausschlaggebend sind vier **lautlose** Fehlerquellen und ein Widerspruch: C kann die dritte Bedingung seiner eigenen Abnahmezeile (Spaltenflucht über alle Gruppen) nachweislich nicht erfüllen. **Die Entscheidung ist dem Auftraggeber mit den Zahlen vorgelegt und von ihm getroffen worden** — nicht nebenbei umgestoßen | Auftraggeber 14.09.2026 |
| **E-MR-26** | Nr. 182: Das Mockup dazu (**M-MR-05**) wird **nicht gezeichnet, sondern gemessen** — die drei Wege werden in die laufende Anwendung eingesetzt und darin fotografiert. Grund: Der Befund entstand genau daran, dass M-MR-01 eine Tabelle mit **fünf** Spalten zeigte und die Anwendung **vierzehn** hat. Eine zweite Skizze hätte denselben Fehler wiederholen können. Die Bilder sind deshalb Bildschirmfotos, das Dokument selbst ist mit Chromium über Playwright gerendert | Opus 14.09.2026 |
| **E-MR-24** | Nr. 42, Prüfmittel: **Die Unicode-Prüfung bekommt eine Ausnahmeliste — sie hat heute keine.** AP2 verweist die `'✕'`-Ausnahme auf `tools/vollstaendigkeit/ausnahmen.md`; diese Datei wird aber ausschließlich von der **Token**-Prüfung gelesen (Eigenschaftsnamen wie `clip-path`), der Eintrag stünde wirkungslos da. Backlog-Runde 3 liefert die passende Mechanik mit: `zusagen.md` (vier Spalten Prüfung · Datei · Muster · Grund) und `zusagen_werten()`, das eine **ungenutzte Ausnahme als Befund** meldet. Die Unicode-Prüfung wird auf diese Mechanik umgestellt, `'✕'` in `einsatz_form.php` dort eingetragen. Damit ist die Zusage „keine Unicode-Zeichen als Symbol" zum ersten Mal **messbar** statt behauptet | Opus 13.09.2026 (Befund), Auftraggeber 13.09.2026 (Freigabe) |
| **E-MR-25** | Nr. 124: Die Markierung gilt für **alle** Öffner, nicht nur für die beiden Bausteine. Nachgezählt am Code: neben den 6 `ui_aktionen()`- und 9 `ui_zeilenaktionen()`-Aufrufen tragen **vier weitere Bauarten** `data-blatt` — der Pin-Knopf des Ortsfelds (`ui.php`, `…ortsblatt`; erscheint im Einsatzformular und in den Stammdaten) und drei handgeschriebene Sortierblatt-Öffner (`index.php`, `suche.php`, `zeitraum.php`). Die Regel an `[data-blatt][aria-expanded="true"]` erreicht sie von selbst, und das ist gewollt: „Blatt offen" ist dieselbe Aussage, gleich an welchem Knopf. Der Hinweis in `Design.md` 3.1 (Orange = Handlung) wird entsprechend um „und geöffnet" erweitert — siehe AP4 | Opus 13.09.2026 (Nachzählung), Auftraggeber 13.09.2026 |

---

## 3. Freigabefragen — F-MR-1 bis F-MR-13

Je Mockup die Fragen, die das Bild stellt. **Antwort je Frage**; „wie
vorgeschlagen" reicht. Die Empfehlung steht dabei.

**M-MR-01 — Importvorschau (Nr. 41)**

- **F-MR-1** Variante **A** (Kopfzeile neu, Warnung als vorhandene
  `.plakette-orange`, `imp-warn` entfällt) oder **B** (eigene Regel für
  `imp-warn`, Text in `--orange-tief` mit Symbol)? *Empfehlung A.*
- **F-MR-2** Datum in der Kopfzeile als „12.09.2026" (wie überall in der
  Oberfläche) statt „2026-09-12"? *Empfehlung ja.*
- **F-MR-3** „Nicht zuordenbar (n)" mit `.plakette-rot`? — **ja** (E-MR-13).

**M-MR-02 — Symbole (Nr. 42)**

- **F-MR-4** Textsymbol: neues Token `--symbol-text: 1em`, Klasse
  `.symbol-text` mit `vertical-align:-.15em` — so? *Empfehlung ja.*
- **F-MR-5** Farbe des Symbols im Satz `--orange-tief` (wie das Symbol der
  Meldung)? *Empfehlung ja.*
- ~~F-MR-6~~ ersetzt am 13.09.2026 (E-MR-11):
- **F-MR-6a** — **C** (E-MR-14), Symbol in der Mitte des Ziels; Mockup
  Fassung 3 zeigt es mit Lupe.
- **F-MR-6b** — **28 px** (Fassung 4 „perfekt", E-MR-21).

**M-MR-03 — Kartengröße (Nr. 45)**

- **F-MR-7** `min(60vh, 520px)` — **ja** (E-MR-15).
- **F-MR-8** je Gerät merken — **ja** (E-MR-15).
- **F-MR-9** ab 1600 px — **geändert:** der Knopf bleibt und macht die
  Karte breit (E-MR-16), Mockup Fassung 3.
- **F-MR-10** „arrows-vertical" — **ja** (E-MR-15).

**M-MR-04 — Aktionsblatt (Nr. 124)**

- **F-MR-11** — **D4** (E-MR-21).
- **F-MR-12** — **240 ms, und zwar als neuer Wert von `--dauer`** für alle
  Bewegungen (E-MR-22).
- **F-MR-13** — **ja** (E-MR-23).

**M-MR-05 — Gruppenkopf der Importvorschau (Nr. 182, nachgereicht 14.09.2026)**

- **F-MR-14** Welcher Weg? **A** so lassen (Nr. 182 bleibt offen) · **B**
  Kopfzeile am linken Rand heften · **C** je Gruppe eine eigene Tabelle.
  **Beantwortet am 14.09.2026: B** — und zwar **ohne JavaScript** (E-MR-28);
  die vier Zeilen `ResizeObserver`, die hier zuerst standen, braucht es nicht.
  **Der Weg dorthin gehört zur Antwort:** Am 13.09.2026 war **C** gewählt; die
  Kartierung davor (fünf Linsen, 17 Agenten) ergab **58 Befunde, 22 davon
  „bricht"**, und der Auftraggeber hat die Entscheidung daraufhin auf B
  geändert (E-MR-27).
  **Nebenfrage:** Besatzungszeile am Handy kürzen? — **nein, vorerst nicht.**
  Ohne Kürzung sind es bei zwei abweichenden Rollen 231 px, bei einer rund 130.
  Der Text ist der Grund, warum jemand hinsieht; Kürzen wäre eine Zeile CSS,
  wenn es am Gerät zu wuchtig wirkt.

---

## 4. Arbeitspakete nach der Freigabe

Freigabe liegt vor (13.09.2026). Jedes Paket einzeln abnehmbar;
zusammen **eine Web-Stufe** (Neben — neue Darstellungen). Die Pflichten aus
`Design.md` 1.3 laufen bei jedem mit: `Design.md` im selben Paket,
`pruefen.py`, Bilderlauf der berührten Seiten in allen acht Breiten,
`kontrast.py`, Stilvergleich.

#### AP1 · Nr. 41 — Importvorschau

*Was zu tun ist:* Regel für `.imp-daygroup td` (Hintergrund `--rauch`,
Oberlinie `--strich-stark`, Unterlinie `--strich`, Polster `--abstand-2`
`--abstand-3`), Kopfschrift für das Datum (`--groesse-3`, `--dunkelblau`),
Rest `--gedaempft --groesse-2`; Zeile als `flex-wrap` mit `--abstand-2`.
Bei A: `import_ui.js` baut die Warnung als
`<span class="plakette plakette-orange">` mit `warnung`-Symbol, `imp-warn`
entfällt (Streichliste, Grund: durch `.plakette-orange` ersetzt);
`ohne-regel.md` verliert `imp-daygroup` (Regel liegt vor) und `imp-warn`.
Nach F-MR-2 das Datum über die vorhandene Datumsformatierung. Nach F-MR-3
„Nicht zuordenbar" mit `.plakette-rot`.
*Abnahme:* `pruefen.py` „im Markup ohne Regel, als [offen] vermerkt" = **0**
(nach Backlog-Runde 3 standen dort noch diese zwei); Bilderlauf der
Importvorschau 8 Breiten, kein waagerechter Überlauf; am Handy bricht die
Kopfzeile in zwei Zeilen (Datum + Rest, Plakette + Auswahl). Backlog
Nr. 41 nach *Erledigt*.

#### AP2 · Nr. 42 — zwei Zeichen

*Was zu tun ist:* `.rmx` nach F-MR-6a/6b — bei C: `display:inline-flex;
width/height` = Zielmaß (28 px als neues Token `--ziel-chip` oder 24 px =
`--symbol-gross`), `border-radius:var(--radius-rund)`, negativer Rand, damit
der Chip 26 px bleibt, Symbol `schliessen` in **12 px** (neues Token
`--symbol-winzig` oder `calc(var(--symbol-klein) * .75)` — die Umsetzung
entscheidet nach `Design.md` 5, kein Hexwert und keine nackte Zahl außerhalb
`:root`), **6 px zum Text und 6 px zum Chiprand** (E-MR-18: Chip-Polster
rechts auf 6 px, Abstand über `gap`; 6 px ist kein Token — `--abstand-1`
ist 4, `--abstand-2` ist 8 — die Umsetzung nimmt `calc(var(--abstand-1) *
1.5)` oder legt mit Begründung ein Token an, `Design.md` 4). **Beide**
Chip-Erzeuger umstellen: `ortsfeld.js` (`zeichne()`) und `einsatz_form.php`
(Rettungsmittel, `'\u00d7'` — Fehlerfund 1). Bei C′: nur Ziel und Abstand,
Zeichen bleibt; dann Ausnahmen in `ausnahmen.md` für beide Stellen. Neues Token `--symbol-text: 1em`, Klasse
`.symbol-text{width:var(--symbol-text);height:var(--symbol-text);
vertical-align:-.15em}` in Abschnitt Symbole des Stylesheets; `patient.js`
ersetzt `ZEICHEN_UNLESBAR` durch eine Funktion, die das `<svg class="symbol
symbol-text">`-Markup als Zeichenkette liefert (Sprite-Verweis
`assets/images/symbole/warnung.svg#i`, Farbe `--orange-tief` über die
Meldung); die Zellmarke in `symbol-klein`. `wegKnopf()`: Kommentar
ergänzen, dass `✕` die begründete Ausnahme ist, und die Ausnahme in
`tools/vollstaendigkeit/ausnahmen.md` (Muster `'✕'`, Grund: Rückfall vor
dem Laden von `symbol.js`) eintragen — **berichtigt durch E-MR-24:** die
Ausnahme geht nach `tools/vollstaendigkeit/zusagen.md`, nicht nach
`ausnahmen.md`, und die Unicode-Prüfung wird dafür auf `zusagen_werten()`
umgestellt (Kommentare über `ohne_php_js_kommentare()` ausblenden,
`\uXXXX`-Folgen vor dem Zählen dekodieren — damit ist der Fehlerfund 1
im selben Zug erledigt und braucht keine eigene Backlog-Nummer).
*Abnahme:* `pruefen.py` „Unicode-Zeichen als Symbol im Markup": die vier
echten Treffer sind **null** — der Rückfall steht als Ausnahme und wird
nicht mehr gezählt.

> **Vier, nicht drei** (nachgezählt 13.09.2026 am Lauf gegen Web 19.3.0):
> `einsatz_form.php:1617` (`wegKnopf()`-Rückfall `✕` — bleibt als
> Ausnahme), `ortsfeld.js:244` (`×`), `patient.js:133` (`⚠`) und
> `einsatz_form.php:2227` (dasselbe Malzeichen als JavaScript-Escape
> geschrieben — vom Prüfmittel heute nicht gesehen, Fehlerfund 1). Der
> Lauf meldet daneben **255** Treffer; die
> übrigen 251 sind Kommentare (`‹`, `⋯`, `★`, `✓`, Erklärtexte, die das
> Zeichen nennen) und Typografie im Satz (`…`, `→`, „3× Standorte",
> „(2×)"). Nach dem Umbau aus E-MR-24 fallen die Kommentare heraus und
> die Zahl wird zum ersten Mal aussagekräftig — die verbleibende
> Restzahl (Typografie) wird im Prüfdokument genannt, nicht
> stillschweigend hingenommen. Bilderlauf Einsatzformular (Chip) und Suche (Meldung) in 8
Breiten; `kontrast.py` für Orange auf `--orange-hell` unverändert 0
verfehlt. Backlog Nr. 42 nach *Erledigt*; P-P3-03 im Prüfprotokoll
erreicht.

#### AP3 · Nr. 45 — dritte Kartengröße

*Was zu tun ist:* Token `--karte-gross` (E-MR-15); Klasse `.geo-gross` —
bis 1599 px: Höhe `var(--karte-gross)`; **ab 1600 px (E-MR-16):** das
Raster der Tagesübersicht fällt auf eine Spalte (`grid-template-columns`
ohne Kartenspalte), die Karte rückt zwischen Diensttag-Daten und Liste,
Höhe `var(--karte-gross)`; der Knopf ist in jeder Breite da; Symbole `karte-gross.svg` (senkrechte Pfeile) und
`karte-breit.svg` (Querpfeile; Tabler, Kommentar mit Quelle und Ort,
`id="i"`, Einträge in `symbole/LIESMICH.md`: 54 und 55); **ein** Knopf in
`map_fullscreen.js` neben „Vollbild" mit `aria-pressed`, beide Symbole im
Knopf, das Stylesheet zeigt bis 1599 px das eine, ab 1600 px das andere
(E-MR-19); Beschriftung „Karte vergrößern/verkleinern" bzw. ab 1600
„Karte verbreitern/verschmälern" (`aria-label` je Breite über dasselbe
Umschalten oder ein Satz, der beides trägt); nach F-MR-8 der Zustand in `localStorage`
unter einem benannten Schlüssel; Leaflet `invalidateSize()` nach dem
Umschalten, sonst bleiben die Kacheln auf der alten Höhe. Handbuch: ein Satz
bei der Karte des Diensttags.
*Abnahme:* Bilderlauf Tagesübersicht in 8 Breiten, je klein und groß
(zweimal); ab 1600 px wechselt „groß" das Raster (Bild vorher/nachher); Umschalten hinterlässt keine leeren
Kachelflächen (Bildschirmfoto nach `invalidateSize`). Backlog Nr. 45 nach
*Erledigt*.

#### AP4 · Nr. 124 — Aktionsblatt (allein baubar)

*Was zu tun ist:* Stylesheet Abschnitt 10: `.blatt{transform:
translateY(100%); transition:transform var(--dauer) ease-out}` und
`.blatt.offen{transform:none}` (oder `[data-offen]`, wie es zu `blatt.js`
passt); `blatt.js` setzt nach `hidden=false` im nächsten Frame die Klasse
(`requestAnimationFrame`), beim Schließen erst die Klasse weg, `hidden`
nach `transitionend` — sonst ist die Rückfahrt unsichtbar.
`prefers-reduced-motion: reduce` → keine Bewegung (`transition:none`).
**Token `--dauer` in `:root` von `.18s` auf `.24s`** (E-MR-22) — das ist
eine Zeile und trifft alle Nutzer des Tokens; `Design.md` 4 (Bewegung) und
der Kommentar am Token nennen den neuen Wert und den Anlass. Kein
zusätzliches Token fürs Blatt.
Markierung nach E-MR-21 (D4): `[data-blatt][aria-expanded="true"]{background:
var(--orange-hell);color:var(--orange-tief);border-color:var(--orange-hell)}`
— kein Ring; nach F-MR-13 und **E-MR-25 für ALLE Öffner**, nicht nur die
beiden Bausteine; ab 1024 px bleibt das
Aufklappmenü, die Markierung gilt dort mit (der Winkel dreht weiterhin).
`Design.md` 3.1: ein Satz, dass `--orange-hell` mit `--orange-tief` auch
„geöffnet" heißt (wie die Plakette „hier ist etwas"), nicht nur „Handlung".
*Abnahme:* Bilderlauf aller Seiten mit `data-blatt` in 8 Breiten.
**Nachgezählt 13.09.2026: 6 `ui_aktionen()`-Aufrufe (5 Dateien), 9
`ui_zeilenaktionen()`-Aufrufe (6 Dateien), der Pin-Knopf des Ortsfelds
(`ui.php`, in jedem Ortsfeld) und 3 handgeschriebene Sortierblatt-Öffner
(`index.php`, `suche.php`, `zeitraum.php`)** — also vier Bauarten mehr,
als die Zeile „15 Öffner auf elf Seiten" im Rahmenplan nennt. Die
Rahmenplanzeile wird beim Abschluss berichtigt. Klickprobe: Öffnen → Knopf markiert,
Blatt sichtbar; Schließen → Knopf zurück. `grep -c "var(--dauer)"
style.css` ist nach dem Umbau um genau die Blatt-Regel größer; kein
anderer Zeitwert im Stylesheet. `E-P3-27` in `Design.md` 9.12 um
Weg (b) ergänzen. Backlog Nr. 124 nach *Erledigt*.

#### AP5 · Abschluss

Regression R24 (beide Kreisläufe), Wortliste, `pruefen.py` gesamt,
Wartungsprobe, Linkprobe; `Design.md` Änderungsverlauf; Changelog mit
Begründung je Punkt; Backlog vier Punkte nach *Erledigt*, Rahmenplan
Abschnitt 5 (vier Zeilen raus, Selbstprüfzahl nachgerechnet), Schritt 9c
erledigt, Fassung. Prüfdokument nach K9 aus der Vorlage.

---

## 5. Prüfprotokoll / 6. Fehlerfunde / 7. Stand

Fehlerfunde, die beim Bauen auffallen, hierher (K4), nicht nebenbei beheben.

**Fehlerfund 1 (13.09.2026, beim Zeichnen von M-MR-02):**
`einsatz_form.php`, Chip der beteiligten Rettungsmittel, setzt
`x.textContent = '\u00d7'` — dasselbe Malzeichen wie in `ortsfeld.js`, nur
als JavaScript-Escape. `tools/vollstaendigkeit/pruefen.py` (Prüfung „Unicode-
Zeichen als Symbol") sucht Zeichen, keine Escape-Folgen, und sieht diese
Stelle nicht. Zweierlei folgt: (1) AP2 muss beide Chips umstellen, nicht
nur den Koordinaten-Chip; (2) das Prüfmittel sollte `\uXXXX`-Folgen in
JS-Zeichenketten dekodieren, bevor es zählt — als eigener Backlog-Punkt,
Nummer vergibt der Auftraggeber.

> **Erledigt in AP2 statt als Backlog-Punkt** (Auftraggeber 13.09.2026,
> Rückfrage zu (2)). Der Einwand war: Wenn AP2 die Fundstelle ohnehin
> beseitigt, wozu dann noch ein Punkt? Die Antwort trennt beides — die
> **Zeile** verschwindet mit AP2, die **Blindstelle des Prüfmittels**
> bleibt: Wer morgen ein anderes Zeichen als Escape-Folge schreibt,
> bekommt wieder keinen Treffer. Weil E-MR-24 dieselbe Funktion ohnehin
> umbaut, kostet das Dekodieren dort nur ein paar Zeilen; ein eigener
> Backlog-Punkt wäre teurer als die Behebung. **Nr. 176 wird nicht
> vergeben.** Stellt sich beim Bauen heraus, dass der Umbau größer ist
> als hier angenommen, wird der Punkt nachgemeldet — dann mit Nummer.

**Vier veraltete Sollwerte in der Prüfdokument-Vorlage** (gefunden
13.09.2026 beim Gegenlesen; die Vorlage entstand vor E-MR-16, -19 und -21).
Sie werden beim Ausfüllen berichtigt, nicht stillschweigend überschrieben:

| Stelle in `Pruefdokument-Mockup-Runde.md` | steht da | richtig ist |
|---|---|---|
| 2. Maschinell, „Symboldateien" | 53 → **54** | **55** — E-MR-19 verlangt zwei Symbole, nicht eines |
| 3. Im Browser, Chip | Ziel **24 × 24 px** | **28 px** — F-MR-6b, E-MR-21 |
| 3. Im Browser, Tagesübersicht | „ab 1600 px **kein Knopf**" | der Knopf bleibt und macht die Karte breit — E-MR-16 |
| 4. Prüfliste, Punkt 4 | „F-MR-6 nachjustieren" | F-MR-6 ist durch 6a/6b ersetzt (E-MR-11) |

**Fehlerfund 2 (13.09.2026, beim Prüfen von AP1 im Browser):**
Der Gruppenkopf der Importvorschau ist ein `<tr><td colspan>` **in derselben
Tabelle** wie die Datenzeilen — damit ist er so breit wie die Tabelle, nicht
wie das Sichtfenster. Gemessen mit einer Datei aus dem Referenz-Export:

| Fenster | Zelle | sichtbar | Plakette beginnt bei | sichtbar? |
|---|---|---|---|---|
| 400 px | 2677 px | 342 px | x = 940 | **nein** |
| 720 px | 2677 px | 654 px | x = 944 | **nein** |
| 1280 px | 2677 px | 954 px | x = 1204 | **nein** |
| 1920 px | 2677 px | 1354 px | x = 1324 | **nein** |

Zweierlei folgt: **(1) Die Abnahme von AP1 ist, wie sie dasteht, nicht
erfüllbar.** Sie verlangt „am Handy bricht die Kopfzeile in zwei Zeilen" —
das kann sie nicht, weil in einer 2677 px breiten Zelle nichts umbricht; die
Zeile bleibt in jeder Breite einzeilig (40/44 px). Die Erwartung stammt aus
dem Mockup, und dort hatte die Tabelle **fünf** Spalten statt zwanzig.
**(2) Es ist kein Rückschritt, aber ein neuer Widerspruch.** Der alte
Fließtext stand ebenso außerhalb (gemessen x = 1077 bei 400 px, x = 1341 bei
1280 px, beide unsichtbar) — neu ist, dass die Aussage jetzt eine
**Plakette** trägt: die Form für „Zustand, der Aufmerksamkeit will".

Nach K4 **nicht nebenbei behoben**: Jede der drei Lösungen wäre eine neue
Darstellung und braucht nach `Design.md` 1.2 eine Freigabe. Angelegt als
**Backlog Nr. 182** mit drei Wegen; der mittlere (Kopfzeile am linken Rand
festheften) braucht eine **gemessene** Breite — CSS allein reicht nicht,
weil das Stylesheet die Breite des Sichtfensters nicht kennt.

**Erledigt mit Web 19.4.1 am 14.09.2026 — Weg B, und ohne JavaScript**
(E-MR-27, E-MR-28). Der Rollbereich trägt `container-type:inline-size` an
einer eigenen Klasse `.imp-roll`, die Kopfzeile nimmt mit `width:100cqi` die
sichtbare Breite an und heftet sich mit `position:sticky;left:0` an den linken
Rand. Gemessen bei **sieben** Fensterbreiten (360 bis 1920 px): Datum,
Besatzung, Plakette **und** Auswahlfeld in jeder im Sichtfenster, Kopfbreite
immer gleich der sichtbaren Breite, 0 waagerechter Überlauf, keine
Konsolenfehler; waagerecht um 1500 px gescrollt bleibt die Kopfzeile stehen.
**Damit ist auch die Abnahme von AP1 nachträglich erfüllt** — „am Handy bricht
die Kopfzeile in zwei Zeilen" war in 19.4.0 unerfüllbar und ist es jetzt nicht
mehr.

**Zwei Irrtümer, die hier festgehalten gehören**, weil beide Dokumente sie
behauptet haben: **(1)** Weg B brauche eine *gemessene* Breite aus JavaScript
— falsch, die Container-Abfrage liefert sie, und beide Fassungen sind bei
sechs Fensterbreiten auf den Pixel gleich. **(2)** Kosten Nr. 1 von Weg C sei
„eine Spalte mit einer Breite" — auch falsch: `width` ist auf dieser Spalte
wirkungslos (183 px mit und ohne Regel), nur `min-width` beißt.

**Mockup M-MR-05 lag seit dem 14.09.2026 vor** (E-MR-26, Freigabefrage
F-MR-14). Alle drei Wege sind in die laufende Anwendung eingesetzt und darin
gemessen worden:

| Weg | Kopf sichtbar bei 400 px | Kopfhöhe 400 / 1280 | Tabellen | Spalten fluchten |
|---|---|---|---|---|
| **A** so lassen | **nein** | 44 / 40 px | 1 | ja |
| **B** geheftet | **ja** | 231 / 122 px | 1 | ja |
| **C** eigene Tabelle je Gruppe | **ja** | 233 / 124 px | 3 | **nein — 1 von 14** |

Waagerechter Überlauf der Seite in allen sechs Messungen **0**,
Konsolenfehler in allen drei Wegen **keine**. **C hat zwei Kosten**, die im
Backlog noch nicht standen und erst am Bestand sichtbar wurden: Die Spalten
fluchten schon bei drei Gruppen und fünf Zeilen nicht mehr (eine von
vierzehn weicht ab), und der **Spaltenkopf wiederholt sich je Gruppe** —
lässt man ihn weg, haben alle Gruppen außer der ersten keine
Spaltenbeschriftung. Empfehlung deshalb **B**.

| AP | Punkt | Stand | Probleme / wie gelöst |
|---|---|---|---|
| — | Freigabe F-MR-1 … F-MR-13 | **erteilt 13.09.2026** (vier Fassungen der Mockups) | |
| — | Rückfragen der Umsetzung | **beantwortet 13.09.2026** | Drei Befunde beim Vorbereiten: (1) die für AP2 vorgesehene Ausnahmeliste `ausnahmen.md` wird von der Unicode-Prüfung gar nicht gelesen → E-MR-24; (2) die Regel aus E-MR-23 trifft vier Bauarten mehr als gezählt → E-MR-25, gilt für alle; (3) Fehlerfund 1 bekommt keine eigene Nummer, sondern läuft in AP2 mit. Dazu der Rahmenplan: das Lieferpaket trägt Fassung 46, Runde 3 hat ihn auf 47 neu gefasst — die Freigabe wird nach dem Merge dort nachgetragen, die Fassung 46 aus dem Paket nicht übernommen |
| AP1 | 41 | **erledigt** (Web 19.4.0, 13.09.2026) | **Zwei Abweichungen vom Konzepttext, beide begründet.** (1) Die Kopfzeile verwendet nicht die Klassennamen des Mockups (`.zeile`, `.tag`, `.rest`): `.zeile` ist in dieser Anwendung ein **Baustein** (Design.md 9.2, Listeneintrag) und hätte seine Regel mitgebracht. Sie heißen `.imp-kopfzeile`, `.imp-tag`, `.imp-rest` — dieselbe Familie wie die übrigen sechs `imp-`-Anker. (2) `.plakette` steht auf `white-space:nowrap`; der Konflikttext wächst mit der Zahl der abweichenden Rollen und wäre bei 360 px breiter als das Gerät. In dieser Kopfzeile darf sie umbrechen — eine gescopte Regel, im Stylesheet und in Design.md 9.34 begründet. **Dazu Fehlerfund 2**, siehe unten. |
| AP2 | 42 | offen | |
| AP3 | 45 | offen | |
| AP4 | 124 | offen | |
| AP5 | Abschluss | offen | |
