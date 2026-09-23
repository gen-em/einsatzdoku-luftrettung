# Stilvergleich

Hat sich am Erscheinungsbild etwas geändert, das niemand wollte?
**Anlass: P0/A3** — ein Umbau des Stylesheets ohne Netz und doppelten Boden.

## Aufruf

```bash
bash tools/stilvergleich/gegen.sh [<ref>]              # Vorgabe: origin/main
bash tools/stilvergleich/gegen.sh --schreiben [<ref>]  # Messung als geplant.txt ablegen
```

`gegen.sh` holt den Vergleichsstand aus git, baut die vier Proben und fährt
`stilvergleich.js`. Die Einzelschritte (`proben.py`, `kaskade.py`,
`chunks.py`) sind für die Handarbeit da.

## Was es misst

Die **berechneten** Stile zweier Stylesheets an denselben Elementen, in acht
Breiten: 175 Eigenschaften je Element. Vier Proben liefern die Elemente —
das Markup aller Seiten, das erst im Browser entstehende Markup, je ein
Element pro Selektor und dasselbe für die Zustände (`:hover`, `:focus`).

## Was es braucht

Zwei Stylesheets und einen Browser. **Keine Installation** — es vergleicht
Dateien, nicht eine laufende Anlage. `NODE_PATH` setzt `gegen.sh`, weil
`stilvergleich.js` als CJS an `tools/motor.mjs` vorbeigeht (Pflaster, PK-04).

## Erwartete Zahl

**Genau die Abweichungen in `geplant.txt` — ohne Datei 0** (seit P5c/AP1,
F-P5c-72; `docs/Pruefablauf.md` 6.10). Jede Abweichung wird zur Signatur
„Probe · Element <Elternteil> : Eigenschaften"; der Lauf ist grün, wenn die
gemessenen Signaturen und die Datei gleich sind, und nennt sonst jede
ungeplante und jede nicht gemessene Zeile. Die Datei wird im Pull Request
gelesen und nach dem Merge geleert.

Zuletzt gemessen 23.09.2026 (P5c/AP1, gegen `origin/main`): **41 483
Elementmessungen, 546 Abweichungen in 30 Signaturen, alle 30 geplant**
(Chromium). Gegenproben: eine Zeile gestrichen → 1 ungeplant, rot; eine
erfundene dazu → 1 nicht gemessen, rot. Davor, ohne gewollte Änderung:
21.09.2026, 40 989 Elementmessungen, 0 Abweichungen.

## Was es nicht kann

Es sieht keine Bilder — ein Layoutfehler, der aus dem Markup kommt, fällt
ihm nicht auf. Dafür ist der Bilderlauf da.
