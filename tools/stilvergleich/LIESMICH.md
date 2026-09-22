# Stilvergleich

Hat sich am Erscheinungsbild etwas geändert, das niemand wollte?
**Anlass: P0/A3** — ein Umbau des Stylesheets ohne Netz und doppelten Boden.

## Aufruf

```bash
bash tools/stilvergleich/gegen.sh [<ref>]     # Vorgabe: origin/main
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

**0 Abweichungen.** Zuletzt gemessen 21.09.2026: **40 989 Elementmessungen,
0 Abweichungen, 175 Eigenschaften je Element** (Chromium).

## Was es nicht kann

Es sieht keine Bilder — ein Layoutfehler, der aus dem Markup kommt, fällt
ihm nicht auf. Dafür ist der Bilderlauf da.
