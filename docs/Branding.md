# Gen-EM NAdoku — Branding

Die Brand Guideline dieser Anwendung: verbindliche Quelle für Farben,
Schriften und Logo-Einsatz — damit niemand einen Farbwert raten muss und
eine Erweiterung nicht wieder eigene Töne erfindet.

**Geltungsbereich:** Weboberfläche (`server/`) und Uhr-App (`watch/`).

**Die technische Wahrheit steht im Stylesheet.** Was hier als Farbwert
aufgeführt ist, ist die *Herkunft*; im Code wird ausschließlich über die
CSS-Variablen aus `:root` in `server/assets/style.css` zugegriffen. Ein Hexwert
gehört nie direkt in eine Regel — sonst weiß beim nächsten Umbau niemand mehr,
ob er die Marke trifft oder geraten wurde.

---

## 1. Farben

### 1.1 Grundtöne (markenübergreifend)

| Name | HEX | RGB | Verwendung hier |
|---|---|---|---|
| Schnee | `#FFFCFA` | 255 252 250 | Karten, Kästen, Tabellenflächen |
| Rauch | `#F7F5ED` | 247 245 237 | Seitenhintergrund |
| Sand | `#D4C7AD` | 212 199 173 | Basis der Linienfarbe (nicht direkt benutzt) |
| Asphalt | `#1A0500` | 26 5 0 | Fließtext |
| Dunkelblau | `#1A2E4D` | 26 46 77 | Kopfleiste, Überschriften, Logo-Grundton |

### 1.2 Kernfarben (dunkel + hell)

| Name | HEX | RGB | Pantone |
|---|---|---|---|
| Orange | `#FF8F1F` | 255 143 31 | 1495 C |
| Hellorange | `#FFEBD6` | 255 235 214 | — |
| Blau | `#4280E5` | 66 128 229 | 2727 C |
| Hellblau | `#D9ECFD` | 217 236 253 | — |
| Rot | `#D63338` | 214 51 56 | 1797 C |
| Rosa | `#FCE2D6` | 252 226 214 | — |

Die drei Kernfarben tragen in der Anwendung eine Zustandsaussage
(siehe 1.4).

### 1.3 Abbildung auf die CSS-Variablen

Stand `server/assets/style.css`, `:root`:

| Variable | Wert | Herkunft |
|---|---|---|
| `--schnee` | `#FFFCFA` | Schnee |
| `--rauch` | `#F7F5ED` | Rauch |
| `--sand` | `#D4C7AD` | Sand |
| `--ink` | `#1A0500` | Asphalt |
| `--navy` | `#1A2E4D` | Dunkelblau |
| `--accent` | `#FF8F1F` | Orange |
| `--accent-light` | `#FFEBD6` | Hellorange |
| `--accent-dark` | `#E67C0E` | **abgeleitet** — Orange eine Stufe dunkler (Hover, Ränder) |
| `--blau` | `#4280E5` | Blau |
| `--blau-light` | `#D9ECFD` | Hellblau |
| `--blau-dark` | `#3670D8` | **abgeleitet** — 4,6:1 auf Schnee; reines Blau erreicht nur 3,8:1 und ist für kleine Beschriftungen zu schwach |
| `--rot` | `#D63338` | Rot |
| `--rot-light` | `#FCE2D6` | Rosa |
| `--line` | `#E3DAC6` | **abgeleitet** — aufgehelltes Sand, Trennlinien |
| `--muted` | `#6E6459` | **abgeleitet** — gedämpfter Text, Sekundärbeschriftungen |

Die vier abgeleiteten Werte sind Ableitungen für Kontrast und Feinabstufung
und dürfen so bleiben; wer sie ändert, prüft die Kontrastwerte neu
(siehe 1.5).

### 1.4 Farbeinsatz in dieser Anwendung

Die Anwendung ist ein Dokumentationswerkzeug, in dem Werte gelesen und
geprüft werden — die Beigetöne tragen die Fläche, die Kernfarben treten
gezielt auf, nie flächig. Mit dem Oberflächen-Redesign (Phase P3) werden die
drei Kernfarben bewusst präsenter eingesetzt — auch in Tabellen, Links,
Rahmen und Akzenten, in Summe ausgewogen über die drei Farben. Dieses
Dokument wird dabei fortgeschrieben.

| Rolle | Farbe |
|---|---|
| Seitengrund | Rauch |
| Flächen (Karten, Tabellen, Kästen) | Schnee |
| Kopfleiste | Dunkelblau, Logo in Weiß |
| Text | Asphalt; Überschriften Dunkelblau |
| Sekundärtext, Beschriftungen | `--muted` |
| Aktiver Menüpunkt, Akzent, Hervorhebung | Orange |
| Abweichung von einem übergeordneten Stand | `--blau-dark` |
| Löschen, Fehler, Warnung | Rot (+ Rosa als Fläche) |

### 1.5 Kontrast

Zielwert ist WCAG AA: **4,5:1** für Fließtext, **3:1** für großen Text und
Bedienelemente. Der Kontrast wird gegen die tatsächliche Fläche geprüft (Schnee
oder Rauch, nicht Weiß). Farbe darf nie der **einzige** Träger einer Aussage
sein — ein Zustand braucht zusätzlich Text, Symbol oder Position.

---

## 2. Typografie

Zwei quelloffene Schriften, beide **selbst ausgeliefert** aus
`server/assets/fonts/` (Herkunft: `@fontsource`, OFL-1.1). Ein Nachladen von
Google Fonts oder einem anderen CDN ist ausgeschlossen — die Anwendung lädt zur
Laufzeit keine fremde Quelle (siehe `docs/Technik.md`).

| Schrift | Schnitte im Repo | Einsatz |
|---|---|---|
| Bricolage Grotesque | 500, 600 | Überschriften, Kopfleiste, Schaltflächen, Tabellenköpfe |
| Open Sans | 400, 600, 700 | Fließtext, Formularfelder, Tabelleninhalt |

Je Schnitt liegen die Subsets `latin` und `latin-ext` als `woff2` vor.
**Ein Schnitt, der nicht in `@font-face` eingetragen ist, existiert nicht** —
die Datei allein genügt nicht.

**Variablen:** `--head` (Bricolage + Ersatzliste), `--zahl` (Open Sans mit
`tnum` für Zahlenspalten), `--mono` (nur wo die Schreibmaschinenschrift die
Aussage *ist*: Kopplungscode, Wiederherstellungsschlüssel, Phasenkachel).

**Die Ersatzliste bleibt normal breit.** Bricolage Grotesque ist eine normal
breite Grotesk; eine schmale Ersatzschrift (`Arial Narrow`) lässt bei
ausgefallenem Download die ganze Oberfläche gedrungen wirken und sieht nach
Gestaltungsfehler aus.

**Zeilenabstand:** je größer der Text, desto kleiner das Leading —
Überschriften ~1.15, Fließtext ~1.4. Für Größenstufen dient eine
Major-Third-Skala ab der Fließtextgröße als Richtschnur; die Anwendung folgt
dem heute nur lose (`15px` Grundgröße, Stufen historisch gewachsen). Eine
geschlossene Skala ist Gegenstand des Oberflächen-Redesigns (Phase P3),
nicht dieser Referenz.

---

## 3. Logo

### 3.1 Was hier verwendet wird

Die Bildmarke der Anwendung ist ein **Hubschrauber** mit drei Rotorblättern
in den Kernfarben. Mit dem Oberflächen-Redesign (Phase P3) kommt eine zweite
Bildmarke hinzu (**NEF**); Nutzer wählen dann RTH, NEF oder zufälligen
Wechsel, der Admin legt den Standard fest.

| Datei | Zweck |
|---|---|
| `server/assets/images/gen-em_logo_helicopter.svg` | farbig — Anmelde- und Einrichtungsseite (heller Grund) |
| `server/assets/images/gen-em_logo_helicopter_weiss.svg` | weiß — Kopfleiste (Dunkelblau) |
| `server/assets/images/favicon.png` | Browser-Symbol, Apple-Touch-Icon |
| `server/favicon.ico` | Rückfall im Wurzelverzeichnis |
| `watch/resources*/drawables/launcher_icon.png` | Uhr-App, je Geräteprofil (40 px / 70 px) |
| `watch/resources*/drawables/logo.png` | Uhr-App, Startbild (70 px / 105 px) |

Eingebunden wird über `logo_src()` und `favicon_tags()` in `server/db.php`,
nie über einen fest verdrahteten Pfad. Die Einstellung `app.logo_path` in
`config.php` erlaubt einer Betreiberin, ein eigenes Logo zu hinterlegen; fehlt
die Datei, greift das Standardlogo.

### 3.2 Einsatzregeln

- **Auf Dunkelblau und auf den drei Kernfarben:** weiße Fassung.
- **Auf hellem, ruhigem Grund:** farbige Fassung.
- **Auf unruhigem oder dunklem Bild:** Logo neben das Bild, nicht darauf.
- **Sehr kleine Anwendungen:** einfarbige Fassung (Schwarz oder Weiß).
- **Weißraum:** rundum mindestens die Breite und Höhe eines kleinen „e" der
  Wortmarke freihalten; in diesem Schutzbereich steht nichts anderes. In der
  Kopfleiste ist das über `.brand{gap:.55rem}` und das Innenmaß der Leiste
  abgebildet.

---

## 4. Regeln für Änderungen

1. Farben ausschließlich über die Variablen aus `:root`. Kein Hexwert direkt
   in einer Regel.
2. Ein **neuer** Farbwert braucht eine Herkunft: entweder aus Abschnitt 1
   oder als begründete Ableitung, die hier mit Begründung nachgetragen wird.
3. Eine neue Schriftgröße oder ein neuer Schnitt wird hier und in `@font-face`
   eingetragen, sonst nirgends.
4. Kontrast gegen die tatsächliche Fläche prüfen, nicht gegen Weiß.
5. Wird ein Logo ausgetauscht, alle Fassungen aus 3.1 mitziehen — auch die
   Uhr-Icons.

---

## 5. Offene Punkte

**B1 — Die Logodateien tragen nicht die Markenfarben.** Gemessen in
`gen-em_logo_helicopter.svg` (identisch in den PNG-Fassungen und den Uhr-Icons):

| Element | im Logo | Markenwert | Abweichung |
|---|---|---|---|
| rotes Rotorblatt | `#E3322B` | `#D63338` Rot | ja |
| blaues Rotorblatt | `#587ABC` | `#4280E5` Blau | ja, deutlich blasser |
| oranges Rotorblatt | `#F7941D` | `#FF8F1F` Orange | ja |
| Rumpf | `#1D0E0A` | `#1A0500` Asphalt | gering |

Die Oberfläche ist also markenkonform, das Logo darin nicht. Zu entscheiden:
(a) so belassen und die Abweichung hier festhalten, (b) die SVG- und
PNG-Fassungen auf die Markenwerte ziehen — dann in einem Zug alle Dateien aus
3.1 einschließlich der Uhr-Icons.

**B2 — Keine geschlossene Größenskala.** Siehe Abschnitt 2. Gehört ins
Oberflächen-Redesign (Phase P3), nicht hierher.
