# Mockups zur Mockup-Runde (Fahrplan Schritt 9c)

Je Darstellung eine HTML-Datei mit den Token aus `server/assets/style.css`
(Auszug im `<style>`, Stand 13.09.2026; die beiden **neuen** Token
`--symbol-text` und `--karte-gross` sind dort als Vorschlag markiert). Die
Schriften kommen aus `server/assets/fonts/` (relativer Pfad), keine fremde
Quelle. Symbole als Inline-`<symbol>` aus dem Vorrat (Tabler, MIT) — nur
für das Bild; die Anwendung bindet sie über die Dateien ein.

| Datei | Was | Stand |
|---|---|---|
| `M-MR-01-importvorschau.html` / `.png` / `-handy.png` | Nr. 41: Kopfzeile der Tagesgruppe und Warnhinweis — Ist, Vorschlag A (Plakette), Vorschlag B (eigene Regel) | 13.09.2026 |
| `M-MR-02-symbole.html` / `.png` / `-handy.png` | Nr. 42: Entfernen-Knopf in **beiden** Chips (Koordinaten, Rettungsmittel) — Ist, verworfener 16-px-Vorschlag, Vorschlag C (SVG 12 px in der Mitte des 28-px-Ziels, **6 px zum Text = 6 px zum Rand**, mit Lupe) und C′ (Textzeichen, Ziel 28 px); `⚠` als Textsymbol in drei Schriftgrößen und als Zellmarke | 13.09.2026, Fassung 4 |
| `M-MR-03-kartengroesse.html` / `.png` / `-handy.png` | Nr. 45: die Seite mit Leiste und Kopf, echte Kacheln — Schreibtisch 1200–1599 (300 gegen 520 px), ab 1600 (rechte Spalte gegen „breit" in voller Inhaltsbreite), Handy 390 (160 gegen 480 px) | 13.09.2026, Fassung 4 |
| `M-MR-03-bg-*.png` | Kartenhintergründe: Kacheln von `tile.openstreetmap.org` (Zoom 12, Oberallgäu), © OpenStreetMap-Mitwirkende, ODbL — nur für diese Bilder zusammengesetzt; Spur und Marken gezeichnet | 13.09.2026 |
| `M-MR-05-gruppenkopf.html` / `.png` / `-handy.png` | **Nr. 182** (Fehlerfund 2 aus AP1): Der Gruppenkopf der Importvorschau steht außerhalb des Sichtfensters — drei Wege gegeneinander, **am laufenden Bestand gebaut und gemessen**. Belegbilder `M-MR-05-weg-{A,B,C}-{400,1280}.png` (Vollabzug) und `…-aus.png` (Ausschnitt der Kopfzeile in Originalgröße) | 14.09.2026 |
| `M-MR-04-aktionsblatt.html` / `.png` / `-handy.png` | Nr. 124: drei Standbilder des Auffahrens mit Variante C, dann **orange Füllungen D1–D4** (Lupe, Kontraste), D1 und D4 im Zusammenhang, danach die Ringfassungen C1–C4 (2 px `--orange-tief` oder 3 px `--orange`, je mit und ohne orange Punkte, Lupe 1,6×, Kontrastwerte), C1 und C3 im Zusammenhang, A und B zum Vergleich, Schreibtisch | 13.09.2026, Fassung 4 |

> **M-MR-05 ist anders gebaut als M-MR-01 bis -04, und zwar mit Absicht.**
> Die vier Mockups der Freigaberunde zeichnen einen Vorschlag; M-MR-05
> beantwortet eine Frage, die **nur der Bestand** beantworten kann — wie
> breit die Vorschautabelle wirklich ist und was davon ins Sichtfenster
> passt. Eine gezeichnete Tabelle mit fünf Spalten hätte genau den Fehler
> wiederholt, der zu diesem Befund geführt hat (das Mockup zu Nr. 41 zeigte
> fünf Spalten, die Anwendung hat vierzehn). Die drei Wege sind deshalb in
> die **laufende Anwendung** eingesetzt und darin gemessen worden; die
> Bilder sind Bildschirmfotos, keine Skizzen. Gerendert wurde das Dokument
> selbst mit **Chromium 141 über Playwright**, nicht mit `wkhtmltoimage` —
> es kennt `gap` und braucht die Ersatzabstände unten nicht.

**Bilder (M-MR-01 bis -04):** gerendert mit `wkhtmltoimage 0.12.6` (QtWebKit) bei 1280 bzw.
400 px Breite, danach auf 256 Farben reduziert (Floyd-Steinberg). QtWebKit
kennt kein `gap` im Flex-Raster; die HTML-Dateien tragen dafür
Ersatzabstände, die nur das Bild betreffen und nicht in die Anwendung
gehören. Der Kartenhintergrund in M-MR-04 ist eine gezeichnete Attrappe; M-MR-03
benutzt echte Kacheln (siehe Tabelle).

**Freigabe:** die dreizehn Fragen F-MR-1 bis F-MR-13 — dazu **F-MR-14** aus
M-MR-05 — stehen im Konzept
(`../../Konzept-Mockup-Runde.md`, Abschnitt 3) und am Fuß jedes Mockups.
