# Ausnahmen — Werte, die außerhalb der Token stehen dürfen

Grundregel 4 aus dem Konzept (5.1) lautet: **kein Wert außerhalb der Token.**
Sie gilt für Farben, Schriftgrößen, Maße und Schwellen. Was hier steht, ist
die Liste der begründeten Ausnahmen — und sie ist absichtlich kurz. Ein
Eintrag heißt nicht „hier darf man schludern", sondern „diese Eigenschaft
beschreibt Geometrie, keine Gestaltung".

Die erste Spalte ist der **Eigenschaftsname**, nicht die Zeilennummer: So
überlebt die Liste jede Umsortierung des Stylesheets.

| Muster | Grund |
|---|---|
| `clip-path` | Der Baustein `.nur-vorlesen` versteckt Text vor dem Auge und lässt ihn dem Screenreader. Das Muster (1 px Fläche, negativer Rand, `clip-path`) ist reine Geometrie und seit Jahren dieselbe Zeile in jeder Anwendung; ein Token dafür hieße, eine Zahl zu benennen, die niemand je ändert. |
| `width` | Nur in `.nur-vorlesen` (1 px). Jede andere Breite kommt aus den Token — die Prüfung meldet sie. |
| `height` | Nur in `.nur-vorlesen` (1 px). |
| `margin` | Nur in `.nur-vorlesen` (−1 px). |
| `stroke-width` | Strichstärke innerhalb eines SVG ist Zeichnung, nicht Gestaltung (Grundregel 4, Ausnahme „reine Geometrie in SVG-Dateien"). |

## `style="…"` in PHP/JS — berechnete Werte (PK-04/1b, 22.09.2026)

Grundregel 4 verlangt Werte aus den Token. **Ein Wert, den erst die Laufzeit
kennt, steht in keinem Token und kann in keinem stehen** — eine Farbe aus den
Daten, ein Drehwinkel aus einer Peilung, eine Breite aus einem Zeitanteil.
Dafür ist das `style`-Attribut das richtige Mittel, und die drei Einträge
unten sind die drei Fälle, die es im Bestand gibt.

**Die Muster tragen die Form `style:<regex>` und greifen gegen den Text des
Treffers.** Wer hier etwas einträgt, schreibt kein `style:.` — das wäre eine
Ausnahme für alles und hübe die Prüfung auf.

| Muster | Grund |
|---|---|
| `style:^background:` | Die Farbe kommt aus den Daten (Einsatzart, Sicherungsziel, Stammdaten) und wird mit `esc()` gesetzt. Ein Token je möglicher Farbe gibt es nicht — die Menge wächst mit den Stammdaten. Fundstellen: `geo.js`, `missiontable.js` (2×). |
| `style:^transform:rotate\(` | Der Drehwinkel der Windrichtung, gerundet auf ein Grad. 360 Token wären keine Gestaltung, sondern eine Tabelle. Fundstelle: `geo.js`. |
| `style:^left:' ` | Prozentanteil der Schnittleiste: Anfang eines Abschnitts als Anteil der Gesamtdauer. Das Muster verlangt das **Anführungszeichen** hinter dem Doppelpunkt: Nur ein Wert, der sofort in eine Verkettung übergeht, ist berechnet — ein festes `left: 12px` bliebe ein Befund. Fundstellen: `schneiden.js` (6×). **Zwei Einträge statt einem mit `\|`:** Die Tabellenzeile wird an **jedem** `|` zerlegt, auch am escapeten — ein Muster mit Alternative zerbricht dabei in zwei halbe. |
| `style:^width:' ` | Dasselbe für die Breite. |

