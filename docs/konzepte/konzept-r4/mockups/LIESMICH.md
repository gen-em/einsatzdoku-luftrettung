# Mockups zum Konzept R4 (Backlog-Runde 4)

Je neuer Darstellung eine HTML-Datei und die gerenderten Bilder. Die
Web-Mockups binden das **echte** `server/assets/style.css` ein (relativer
Pfad) und nutzen die Schriften des Repositoriums; die vorgeschlagenen neuen
Klassen stehen im `<style>`-Block der Datei unter „Vorschlag für style.css".
Das Handy-Mockup ist eine HTML-Nachbildung der Compose-Bausteine
(`Bausteine.kt`, `Rueckfrage()` in `HauptActivity.kt`) mit denselben
Markenwerten. Alle Zahlen sind erfunden.

| Datei | Was | Paket | Stand |
|---|---|---|---|
| `M-R4-23-statistik-zeitraum.html` / `.png` / `-handy.png` | Betrieb → Statistik mit Zeitraumwahl: die vier festen Fenster als Listenfilter-Pillen, dazu Von/Bis-Datumsfelder und „Anwenden" in einer Reihe (`.zeitraumwahl`); Zustand A festes Fenster, Zustand B eigener Zeitraum mit einspaltiger Tabelle und Wochen-/Tagesschnitt | R4-23 (Nr. 122 a, E-R4-07) | 26.09.2026, **zur Freigabe** (H-R4-01) |
| `M-R4-24-statistik-diagramme.html` / `.png` / `-handy.png` | Baustein „Diagramm" in zwei Formen: Säulen je Woche als Inline-SVG aus PHP (`.diagramm-saeulen`), Anteile als HTML-Balken (`.diagramm-balken`); Zustand A Reiter Einsätze, Zustand B Reiter NutzerInnen als drei kleine Vielfache | R4-24 (Nr. 122 b, E-R4-07) | 26.09.2026, **zur Freigabe** (H-R4-01) |
| `M-R4-22-handy-verwerfen.html` / `.png` | Dienstansicht der Android-App: neutraler Knopf „Abgewiesene verwerfen …" unter der roten Zustandszeile, Rückfrage als Material-3-`AlertDialog` nach dem Muster „Gerät trennen", Quittung danach | R4-22 (Nr. 114, E-R4-09) | 26.09.2026, **zur Freigabe** (H-R4-01) |

**Bilder:** gerendert mit Chromium 141 über Playwright (die Engine des
Bilderlaufs) bei 1280 px (Faktor 1,5) und 390 px (Faktor 2, `-handy`), das
Handy-Mockup bei 1240 px; Skript im Scratchpad der Konzeptsitzung, nicht
eingecheckt (drei Aufrufe, kein Prüfmittel). Überlauf in keiner Breite
(`scrollWidth > clientWidth` → nein, alle fünf Bilder). Die Datumsfelder
zeigen im Bild das Muster des Renderers (`mm/dd/yyyy`); in der Anwendung
mit `lang="de"` zeigt Chromium `tt.mm.jjjj`.

**Gestaltungsregeln, die die Diagramme einhalten** (`Design.md` 3.1, 3.2,
9; Palettenprüfung des dataviz-Verfahrens, 26.09.2026):

- **Eine Farbe je Diagramm** — Blau („hier wird erklärt"); der Höchstwert in
  Orange („Hervorhebung", wie die Extremwerte der Zeitraumübersicht) **mit
  Beschriftung**, weil Farbe nie der einzige Träger ist. Kein Rot: Ein
  Höchstwert ist kein Fehler.
- **Schrift nur in Schrifttoken** (`--asphalt`, `--gedaempft`), nie in der
  Reihenfarbe; Gitter in `--linie` (zeichnerisch, 1,36:1 ist dort erlaubt).
  Orange erreicht auf Schnee 2,23:1 und trägt deshalb nie Text — die Zahl
  steht über der Säule in Asphalt (F-P3-J).
- **Keine zweite Achse, kein Kreis, keine Legende bei einer Reihe**; drei
  Reihen verschiedener Größenordnung werden drei kleine Vielfache mit
  eigener Skala (Konten je Zeitraum), nicht eine Grafik mit drei Farben.
- **Die Tabelle bleibt** neben jedem Diagramm — sie ist die Tabellensicht
  und die Auskunft am Fingergerät; am Zeigergerät zeigt jede Säule beim
  Überfahren ihre Zahl (`:hover`/`:focus-within`, ohne Skript).
- Säulen 18 px breit mit 5 px Lücke, Ecken 2 px, Nullpunkt als stärkere
  Linie (`--gedaempft`); Balken 12 px hoch, 4 px Radius, Grund `--linie`.
- Prüfung der zwei Töne mit `validate_palette.js` (Fläche `#FFFCFA`):
  Lichtwert, Chroma, CVD-Trennung (ΔE 29,9 protan), Normalsicht (ΔE 36,4)
  bestanden; Kontrastwarnung für Orange (2,23:1) — abgefangen durch die
  Beschriftung, wie oben.

**Neue Klassen (Vorschlag):** `.zeitraumwahl`, `.zeitraumwahl-felder`;
`.diagramm`, `.diagramm-saeulen` (mit `.gitter`, `.achse`, `.saeule`,
`.hoechst`), `.diagramm-balken` (mit `.balken-text`, `.balken-klein`,
`.balken`, `.balken-zahl`), `.kleinvielfach`. Kein neuer Farbwert, keine
neue Schriftgröße (12/13 px sind Skala 1 und 2). `Design.md` 9 bekommt den
Baustein „Diagramm" mit der Umsetzung; Kontraste sind mit
`tools/screenshots/kontrast.py` nachzumessen, bevor die Regeln in
`style.css` landen.

**Offen für die Umsetzung** (im Konzept bei R4-23/-24 vermerkt): Am
Fingergerät unter 480 px sind die Achsentexte des SVG klein (der `viewBox`
skaliert mit); Vorschlag: dort halbes Fenster (13 Wochen) oder Achsentext
über `.nur-breit` ausblenden und die Tabelle die Auskunft sein lassen. Die
Android-Mockups liegen nach `CLAUDE.md` 7 hier beim Konzept; die
Emulatorbilder der Umsetzung kommen nach `android/mockups/bilder/`.
