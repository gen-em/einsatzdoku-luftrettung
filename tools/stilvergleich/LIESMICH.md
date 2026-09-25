# Stilvergleich

Hat sich am Erscheinungsbild etwas geändert, das niemand wollte?
**Anlass: Nr. 312** — ein Umbau des Stylesheets ohne Netz und doppelten Boden (P0/A3).

## Aufruf

```bash
bash tools/stilvergleich/gegen.sh [<ref>]              # Vorgabe: origin/main
bash tools/stilvergleich/gegen.sh --schreiben [<ref>]  # Messung als geplant.txt ablegen
```

`gegen.sh` holt den Vergleichsstand aus git, baut die vier Proben und fährt `stilvergleich.js`. Die Einzelschritte (`proben.py`, `kaskade.py`, `chunks.py`) sind für die Handarbeit da.

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

**Genau die Signaturen in `geplant.txt` — ohne Datei 0** (`Pruefablauf.md` 6.10); gelesen im Pull Request, nach dem Merge geleert.
Gemessen 25.09.2026 gegen `origin/main` (P5c/AP9): **42 276 Elementmessungen, 170 Signaturen** (Chromium). Neu gegenüber AP8 sind 57: Einstellungsleiste (Option 1), Bereichskarten, Schlüssel- und Notfallblatt auf dem Druckblatt, die Vorschau der Rechtstexte und die einspaltige Installation; 26 aus AP8 sind entfallen (die alten Blätter, `.uebersicht-block`). Die Diensttage-Leiste ist davon nicht betroffen — eigens gemessen, 156 Werte, 0 Abweichungen (Konzept P5c, Prüfdokument 1i). Davor, 24.09.2026 (AP5): 44 954 Elementmessungen, 128 Signaturen.

## Was es nicht kann

Es sieht keine Bilder — ein Layoutfehler, der aus dem Markup kommt, fällt
ihm nicht auf. Dafür ist der Bilderlauf da.
