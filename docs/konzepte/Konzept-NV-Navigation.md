# Konzept NV – Navigation der Einstellungen auf breiten Bildschirmen

Kürzel **NV**. Erstellt am 07.11.2026 aus der Branding-Sitzung (Philipp + Claude) als Übergabe an die
Instanz, die `server/ui.php` und `server/assets/style.css` umbaut. Dieses Dokument ist die
**Planungsgrundlage**, nicht die Freigabe jedes Details: Was die Betreiberin entschieden hat, steht unter
E-NV; was offen ist, unter Q-NV. Mockups liegen in `docs/konzepte/konzept-nv/mockups/NV-*.png`.

## Statusblock

| Paket | Stand |
|---|---|
| NV-01 Bereichszeile (Markup + CSS) | offen |
| NV-02 Rahmenkarte: Leiste und Inhalt in einer Fläche | offen |
| NV-03 Leiste der Einstellungen auf den aktiven Bereich kürzen | offen |
| NV-04 Schmale Breiten (unter 1024 px) | offen |
| NV-05 Dokumentation, Prüfmittel, Version | offen |

Modellwahl: Opus, keine Fächerung (zwei Dateien im Kern, `ui.php` und `style.css`, alle Pakete berühren
beide).

## 1. Anlass und Ziel

Nach dem Markenabgleich (Konzept MA) steht die Weboberfläche farblich richtig – aber auf einem breiten
Bildschirm (ab etwa 1600 px) wirkt die Seitenleiste der Einstellungen „fehl am Platz“ (Befund der
Betreiberin am 07.11.2026 auf Staging, `einstellungen.php?t=profil`). Ursache, nachvollzogen am Quelltext:

- Die Kopfleiste (`.kopf`) läuft über die volle Fensterbreite, ihr Inhalt ist auf `--rahmen` (1680 px)
  zentriert.
- Darunter liegt `.rahmen` (ebenfalls 1680 px, zentriert) mit `aside.leiste` und `main.inhalt` nebeneinander.
  Die Leiste ist ab 1024 px `position: sticky`, `height: calc(100vh - var(--kopf))`, Hintergrund Schnee,
  rechts eine Linie – also eine eigene helle Fläche, die am linken Rand des **Containers** steht, nicht am
  linken Rand des **Fensters**. Auf 1920 px bleiben links von ihr 120 px, auf 2560 px 440 px Seitengrund.
- Der Inhalt daneben besteht aus Karten (Schnee auf Seitengrund). Die Leiste ist damit eine Karte ohne
  Rand neben Karten mit Rand – zwei Flächenlogiken nebeneinander.

Ergebnis: Die Leiste hat keine Kante, an der sie hängt, und keine Fläche, zu der sie gehört. Sie schwimmt.

**Ziel von NV:** Die Einstellungen (und, nach Q-NV-01, auch Startseite und Suche) bekommen ein Layout, in
dem Leiste und Inhalt sichtbar zusammengehören und die drei Bereiche Einstellungen / Verwaltung / Betrieb
nicht mehr als Akkordeon in der Leiste, sondern als **Bereichszeile** unter der Kopfleiste stehen.
Entschieden ist **Variante D** von vier auf dem Canvas gezeigten Layouts (E-NV-01).

**Es geht um Layout, nicht um Farben, Schriften oder Bausteine.** Alle Werte kommen aus den Tokens, die
MA eingeführt hat. Neue Bausteine: genau einer, die Bereichszeile (NV-01); sie ist durch das Mockup
freigegeben (Design.md 9 verlangt das).

## 2. Quellen

| Quelle | Wo | Was |
|---|---|---|
| Mockups | `docs/konzepte/konzept-nv/mockups/NV-01 … NV-05.png` | 1920 px Profil, dasselbe mit Maßen, Betrieb → Status (kurze Seite), 1366 px, Mobil 390 px |
| Canvas | „GenEM Vorschläge“, Board „Navigation auf breitem Bildschirm“ (Claude Design, Konto der Betreiberin) | die vier Varianten A–D mit Begründung; D ist markiert |
| Branding-Repo | `gen-em/gen-em-branding`, Stand `e7bf4fc` | Tokens (`farben.css`, `radien.css`), Regeln |
| Bestand | `server/ui.php` (`ui_geruest_start()`, `ui_kopf()`, `ui_einstellungen_punkte()`, `ui_leiste_einstellungen()`), `server/assets/style.css` (`.rahmen`, `.leiste`, `.inhalt`, `.karte`, Blöcke `@media (min-width:1024px)` / `1200px`) | was umgebaut wird |

## 3. Zielbild (Variante D)

Von oben nach unten, Desktop ab 1024 px:

1. **Kopfleiste** – unverändert (56 px, Dunkelblau, Inhalt auf 1680 px zentriert, Startseite/Suche/Hilfe/
   Zahnrad rechts).
2. **Bereichszeile** – neu, 48 px hoch, volle Fensterbreite, Fläche Schnee (`flaeche-standard`), unten 1 px
   `linie`. Inhalt auf 1680 px zentriert, gleicher Seitenabstand wie die Kopfleiste. Darin die Bereiche als
   Reiter: `Einstellungen · 5`, `Verwaltung · 6`, `Betrieb (3)`. Schrift Open Sans 700, 15 px; inaktiv
   `text-alternativ`, aktiv `marke.dunkelblau` mit 3 px Unterstrich in Dunkelblau (`aktion`). Zähler: die
   Anzahl der Punkte als `text-sekundaer` 400; **Aufmerksamkeits-Zähler** (heute die rote Plakette am
   Status) bleibt die rote Plakette, jetzt im Reiter. Hover: Fläche `flaeche-abgesetzt` (Rauch), kein
   Unterstrich. Fokus: Ring 2 px Max Blau. Die Zeile erscheint auf allen Seiten mit `aktiv ===
   'einstellungen'`; Rollen, die einen Bereich nicht sehen, sehen seinen Reiter nicht (gleiche Logik wie
   heute in `ui_einstellungen_punkte()`).
3. **Rahmenkarte** – der bisherige `.rahmen` (1680 px, zentriert, Abstand 24 px oben) bekommt eine
   gemeinsame Fläche: 1 px `linie`, Radius `--radius-gross` (12 px), Hintergrund Schnee, `overflow: hidden`.
   Sie ist so hoch wie ihr Inhalt. In ihr:
   - **Leiste** links, 260 px (ab 1200), 220 px (1024–1199), Hintergrund `flaeche-karte` (neutral 75),
     rechts 1 px `linie`. Sie zeigt **nur den aktiven Bereich**: keine `<details>`-Gruppen mehr, die Punkte
     direkt untereinander, der aktive Punkt wie heute (Fläche blau 100, fett), seine Sprungmarken
     (Unterpunkte, `--unterpunkt`) darunter. Unten, durch eine Linie abgesetzt, „Abmelden“. Die Leiste
     streckt sich auf Kartenhöhe (Flex-Kind, `align-self: stretch`), **nicht** mehr auf Fensterhöhe; sie
     ist weiter `sticky` unter Kopf + Bereichszeile (`top: calc(var(--kopf) + 48px)`), damit sie bei
     langen Seiten mitläuft, mit `align-self: flex-start` und Karten-Hintergrund auf der Spalte, nicht auf
     dem Element (sonst endet der Hintergrund mit dem Element).
   - **Inhalt** rechts, Abstand 24 px oben/32 px seitlich. Die Karten der Einstellungsseiten
     (`ui_karte_start()`) werden **in dieser Karte** als Abschnitte gezeigt: kein Rand, kein Radius, kein
     eigener Hintergrund, stattdessen 1 px `linie` oben und 20 px Abstand – eine CSS-Regel mit Geltung
     nur innerhalb der Rahmenkarte (`.rahmenkarte .karte { … }`), **kein PHP**. Meldungen (`ui_meldung`)
     bleiben Karten in Statusfarbe, wie heute.
4. **Fußzeile** – unverändert, unter der Rahmenkarte.

Maße auf 1920 px (Mockup NV-02): Seitengrund links/rechts der Karte je 120 px, Leiste 260, Inhalt 1356
netto. Auf 1366 px (NV-04) berührt die Karte den Seitenabstand von 24 px; die Leiste bleibt 260.

Schmale Breiten (unter 1024 px, NV-04, Mockup NV-05): Die Bereichszeile bleibt und scrollt bei Bedarf
waagerecht (drei Reiter passen bei 390 px ohne Scrollen). Die Leiste bleibt die Schublade von heute
(`.leiste` als `position: fixed`, Schleier, `schublade.js`) – **mit allen drei Bereichen** als Gruppen, weil
die Schublade dort der einzige Überblick ist; die Rahmenkarte verliert Rand und Radius (Inhalt direkt auf
dem Seitengrund), der Rückweg „‹ Einstellungen“ bleibt. Die Einstellungs-Übersicht (`einstellungen.php`
ohne `t`) bleibt als Einstieg für Handy und Zahnrad.

Startseite und Suche: siehe Q-NV-01. Bis zur Antwort gilt NV nur für `aktiv === 'einstellungen'`.

## 4. Arbeitspakete

### NV-01 Bereichszeile

- `ui.php`: neue Funktion `ui_bereichszeile(string $aktiv): void`, gibt `<nav class="bereiche"
  aria-label="Bereiche"><div class="bereiche-innen"> … </div></nav>` aus; ein `<a class="bereich">` je
  Block aus `ui_einstellungen_punkte()`, Ziel = erster Punkt des Blocks (Einstellungen → Profil, Verwaltung
  → erster Verwaltungspunkt, Betrieb → Status), `aria-current="page"` am aktiven. Zähler: `count(punkte)`
  als `<span class="bereich-zahl">`; Aufmerksamkeits-Zähler aus `menue_zaehler_*()` als vorhandene
  Plakette (`ui_zaehler()`), **summiert über den Block**. Aufruf in `ui_geruest_start()` direkt nach
  `ui_kopf()`, nur wenn `leiste === 'einstellungen'`.
- `style.css`: `.bereiche`, `.bereiche-innen` (max-width `--rahmen`, Seitenabstand wie `.kopf-innen`),
  `.bereich`, `.bereich.aktiv`, `.bereich:hover`, `.bereich:focus-visible`, `.bereich-zahl`. Neues Token
  `--bereiche: 48px` neben `--kopf`. Unter 1024 px: `overflow-x: auto`, `scrollbar-width: none`.
- Welcher Block „aktiv“ ist, ergibt sich aus dem aktiven Punkt (`$o['menue']`), wie heute `$hatAkt` in
  `ui_leiste_einstellungen()`. Für die Übersicht (`menue === ''`) ist „Einstellungen“ aktiv.

### NV-02 Rahmenkarte

- `style.css`, Block ab 1024 px: `.rahmen` bekommt `padding: var(--abstand-4) var(--abstand-3)`; neues
  inneres Element `.rahmenkarte` (Flex, Rand, Radius, Schnee, `overflow: hidden`). `ui_geruest_start()`
  öffnet es nach `.rahmen`, `ui_geruest_ende()` schließt es – **für alle Leistentypen**, auch
  `diensttage` und `filter`, sonst bekommt die Suchseite ein anderes Gerüst als die Einstellungen
  (siehe Q-NV-01: ob die Karte dort sichtbar wird, entscheidet eine Klasse `rahmenkarte-ohne` mit Rand 0).
- `.leiste` ab 1024 px: `position: sticky; top: calc(var(--kopf) + var(--bereiche))` (ohne Bereichszeile
  nur `--kopf`), `height: auto`, `max-height: calc(100vh - top)`, `align-self: flex-start`,
  Hintergrund **auf einer Hüllspalte** `.leiste-spalte` (neues Element um `aside`, nur CSS-Zweck:
  `flex: 0 0 var(--leiste); background: var(--flaeche-karte); border-right: 1px solid var(--linie)`), damit
  die Fläche bis zum Kartenboden reicht, auch wenn die klebende Leiste kürzer ist.
- `.rahmenkarte .karte { border: 0; border-radius: 0; background: transparent; border-top: 1px solid
  var(--linie); margin: 0; padding-top: var(--abstand-4) }` und `.rahmenkarte .karte:first-of-type` ohne
  Linie, wenn direkt unter dem Titel. `ui_meldung`-Karten ausgenommen (eigene Klasse `.meldung`, nicht
  `.karte` – prüfen, F-NV-01 falls doch).
- `.rahmen-lesespalte`: die Lesespalte (760 px) bezieht sich künftig auf `.inhalt` innerhalb der Karte;
  die Karte selbst bleibt 1680 breit – Rand und Leiste stehen, der Text ist schmal. Prüfen an
  `hilfe.php`, Rechtstexten.

### NV-03 Leiste der Einstellungen kürzen

- `ui_leiste_einstellungen()`: ab 1024 px nur der aktive Block, ohne `<details>`; unter 1024 px alle
  Blöcke als Akkordeon wie heute. **Ein Markup, zwei Darstellungen** – wie beim Schubladen-Prinzip:
  Server rendert alle Blöcke, die inaktiven bekommen die Klasse `nur-schublade` (gibt es schon), der
  aktive verliert `summary`-Zeile und Winkel ab 1024 px per CSS (`.leiste-gruppe.aktiv > .akkordeon-zeile
  { display: none }` im 1024er-Block, `.leiste-gruppe.aktiv > .akkordeon-inhalt { padding-left: 0 }`).
  Damit bleibt `menue.js` (sessionStorage der Klappzustände) unverändert und wirkt nur noch in der
  Schublade.
- Der Blocktitel entfällt am Desktop (steht in der Bereichszeile). „Abmelden“ bleibt in `.leiste-fuss`,
  jetzt mit Linie oben statt Fensterboden.
- Unterpunkte (Sprungmarken `--unterpunkt`) unverändert.

### NV-04 Schmale Breiten

- Unter 1024 px: `.rahmenkarte { border: 0; border-radius: 0; background: transparent }`, `.rahmen {
  padding: var(--abstand-3) }`. Bereichszeile scrollt waagerecht. Schublade unverändert (alle Blöcke,
  Akkordeon). Rückweg „‹ Einstellungen“ unverändert.
- Bedienhöhen: Reiter 48 px (Zeilenhöhe) ≥ 44 px Finger; am Zeigergerät ab 1024 px bleibt 48, weil die
  Zeile sonst zu flach gegen die 56-px-Kopfleiste wirkt (Ausnahme zur 36-px-Regel, nur Höhe der Zeile,
  nicht eines Knopfs – E-NV-04).

### NV-05 Dokumentation, Prüfmittel, Version

- `docs/Design.md`: Seitentyp „Einstellungen“ und Baustein „Bereichszeile“ nachtragen, Gerüst-Abschnitt
  (`.rahmen`, `.leiste`, `.inhalt`) um `.rahmenkarte`, `.leiste-spalte`, `--bereiche` ergänzen; erzeugte
  Tabellen mit `tools/erzeugen/design.py schreiben` neu ziehen.
- `docs/Handbuch.md`: Einstellungen – „die drei Bereiche stehen als Reiter unter der Kopfleiste“ (ein
  Absatz), Schublade auf dem Handy unverändert.
- `docs/CHANGELOG.md`: ein Eintrag Web, höchstens fünf Sätze (Problem: Leiste schwimmt auf breiten
  Bildschirmen; Lösung: Bereichszeile + gemeinsame Fläche; was bleibt: Schublade, Übersicht).
- `server/version.php`: **Neben**-Stufe (neuer Baustein, veränderter Weg durch die Einstellungen).
- Bilderlauf: Einstellungen in 1024 / 1200 / 1366 / 1920, Chromium + Firefox + WebKit (`style.css` und
  `ui.php` berührt → dreifach ab `neben`); Bedienhöhen gegen beide Sollwerte; Kontrast der Reiter
  (`text-alternativ` auf Schnee, Dunkelblau auf Schnee, Plakette). `tools/screenshots/kontrast.py`.

## 5. Entscheidungen

| Nr. | Entscheidung | Wer, wann, wo |
|---|---|---|
| E-NV-01 | Layout **Variante D** (Bereichszeile + gemeinsame Karte für Leiste und Inhalt) aus vier Varianten: A App-Rahmen (Leiste am Fensterrand, volle Höhe), B Tab-Zeile mit nackter Leiste, C eine Karte ohne Tab-Zeile, D = B + C. | Philipp, 07.11.2026, Canvas „GenEM Vorschläge“, Board Navigation |
| E-NV-02 | Die Leiste zeigt am Desktop nur den aktiven Bereich; die Bereiche wandern in die Bereichszeile. In der Schublade (unter 1024 px) bleiben alle drei Bereiche als Akkordeon. | folgt aus E-NV-01; Schubladen-Teil Vorschlag Claude |
| E-NV-03 | Abschnitte der Einstellungsseiten werden innerhalb der Rahmenkarte als Trennlinien gezeigt, nicht als Karten – per CSS-Geltungsbereich, ohne Änderung an `ui_karte_start()`. | Vorschlag Claude, im Mockup D so gezeigt und freigegeben |
| E-NV-04 | Bereichszeile 48 px hoch in jeder Breite (keine 36-px-Variante am Zeigergerät). | Vorschlag Claude |
| E-NV-05 | Reiter-Unterstrich und aktive Farbe in Dunkelblau (`aktion`), nicht Orange – folgt der Aktionsfarben-Entscheidung (Variante 3, Branding-Repo `e7bf4fc`): Orange nur für die eine Hauptaktion einer Seite, Navigation bleibt Blau. | Philipp, 07.11.2026 (Aktionsfarbe), Anwendung hier Claude |

## 6. Fragen an die Betreiberin

| Nr. | Frage | Empfehlung |
|---|---|---|
| Q-NV-01 | Gilt die Rahmenkarte auch für Startseite (Diensttage-Leiste) und Suche (Filterleiste)? Dort gibt es keine Bereiche, also keine Bereichszeile; die Leiste sitzt heute genauso lose. | **Ja** – gleiche Karte, ohne Bereichszeile. Sonst hat die App zwei Gerüste, und der Wechsel Startseite → Einstellungen springt. Die Diensttage-Leiste ist lang; sie bleibt `sticky` mit `max-height`, scrollt innen. |
| Q-NV-02 | Ziel des Reiters „Betrieb“: Status (hat den Zähler) oder der erste Punkt der Liste? Heute ist Status der erste Punkt – bleibt so, falls die Reihenfolge in `ui_einstellungen_punkte()` nicht geändert wird. | erster Punkt des Blocks, keine Sonderregel |
| Q-NV-03 | Soll die Einstellungs-Übersicht (`einstellungen.php` ohne `t`) am Desktop bleiben, obwohl die Bereichszeile sie überflüssig macht? | Ja, unverändert: Sie ist der Einstieg vom Zahnrad und auf dem Handy der einzige Überblick. Am Desktop zeigt sie die Bereichszeile mit „Einstellungen“ aktiv. |
| Q-NV-04 | Soll das Zählersymbol im Reiter (rote Plakette „3“) auf den Reiter aller Bereiche angewendet werden, in denen es Aufmerksamkeits-Zähler gibt (Verwaltung: offene Bestätigungen?), oder nur Betrieb? | alle Blöcke, Summe je Block – die Logik steht schon in `menue_zaehler_konto()` / `menue_zaehler_betrieb()` |

## 7. Prüfpunkte

| Nr. | Was | Wie | Erwartung |
|---|---|---|---|
| P-NV-01 | Bereichszeile und Rahmenkarte auf demselben Raster | Screenshot 1920, linke Kante der Reiter-Beschriftung = linke Kante der Karte + Seitenabstand; Kopfleisten-Logo darüber | drei Kanten fluchten |
| P-NV-02 | Leiste klebt und ihr Hintergrund reicht bis zum Kartenboden | Profil-Seite auf 1366 × 700 scrollen | Leiste bleibt sichtbar; keine helle Lücke unter ihr |
| P-NV-03 | Kurze Seite (Betrieb → Status) | Screenshot 1920 | Karte endet unter dem Inhalt, Leiste gleich hoch, „Abmelden“ unten in der Leiste |
| P-NV-04 | Kein Karten-in-Karte | alle `t=`-Seiten der Einstellungen durchklicken | Abschnitte als Linien; Meldungen weiter farbige Karten |
| P-NV-05 | Schublade unter 1024 px | 390 und 768 px, Menü öffnen | alle drei Bereiche als Akkordeon, Klappzustand bleibt (menue.js) |
| P-NV-06 | Bereichszeile schmal | 390 px | drei Reiter sichtbar ohne Scrollen; bei vier Bereichen scrollt die Zeile |
| P-NV-07 | Kontraste | `kontrast.py` auf Reiter inaktiv/aktiv, Zähler, Plakette | AA gegen Schnee |
| P-NV-08 | Bedienhöhen | Bilderlauf beide Sollwerte | Reiter 48 ≥ 44/36; Einträge der Leiste unverändert |
| P-NV-09 | Lesespalte | `hilfe.php` ab 1200 | Text 760 px breit, Karte und Leiste normal |
| P-NV-10 | Drei Engines | Chromium, Firefox, WebKit | Sticky-Verhalten der Leiste in der Karte identisch (WebKit prüft `overflow: hidden` auf dem Elternteil – bricht `sticky`! Falls ja: `overflow: clip` statt `hidden` auf `.rahmenkarte`, F-NV-02 vorsorglich) |

## 8. Zuarbeit außerhalb von NV

- **Aktionsfarbe Variante 3** (Branding-Repo `e7bf4fc`, 07.11.2026): Die MA-Übergabe sagte „alle Knöpfe
  Dunkelblau“. Neu: `aktion` Dunkelblau für alles, `aktion-haupt` Philipp Orange mit Asphalt-Schrift für
  die **eine** Hauptaktion je Seite („Einsatz nachtragen“ auf der Tagesübersicht); Hover eine Tonstufe
  heller ohne Schriftwechsel (`aktion-hover` blau 700, `aktion-haupt-hover` orange 400); Dark Mode
  orange 400 / orange 300. Tokens stehen in `farben.css` des Repos. Betrifft MA-04, nicht NV.
- **NAdoku-Zeichen** werden extern neu gestaltet (Hubschrauber und Fahrzeug, ggf. RTW-Silhouette statt
  NEF). Kopfleiste und Favicon ziehen nach, sobald die Dateien im Branding-Repo liegen; Dateinamen
  `nadoku-nef-*` könnten zu `nadoku-fahrzeug-*` werden. Kein Handlungsbedarf in NV.

## 9. Anhang: Dateien dieser Übergabe

```
docs/konzepte/Konzept-NV-Navigation.md       dieses Dokument
docs/konzepte/konzept-nv/mockups/NV-01-einstellungen-1920.png        Profil, Variante D, 1920 px
docs/konzepte/konzept-nv/mockups/NV-02-einstellungen-1920-masse.png  dasselbe mit Maßangaben
docs/konzepte/konzept-nv/mockups/NV-03-betrieb-kurz-1920.png         Betrieb → Status, kurze Seite
docs/konzepte/konzept-nv/mockups/NV-04-einstellungen-1366.png        Profil auf 1366 px
docs/konzepte/konzept-nv/mockups/NV-05-mobil-390.png                 Profil auf 390 px (Schublade zu)
docs/konzepte/konzept-nv/mockups/NV-00-ist-staging.png               Ausgangslage auf Staging (Screenshot der Betreiberin)
```
