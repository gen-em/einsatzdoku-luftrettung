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
