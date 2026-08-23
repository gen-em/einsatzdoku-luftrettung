# Stilvergleich — nachrechnen, dass sich am Erscheinungsbild nichts geändert hat

Wird **nicht ausgeliefert** (`tools/` ist vom Deploy ausgenommen).

## Wozu

`assets/style.css` ist eine einzige Datei mit über 500 Regeln. Sobald man
darin umsortiert, entdoppelt oder Media Queries zusammenzieht, kann an jeder
Stelle etwas kippen, an der **zwei gleich starke Regeln ihre Reihenfolge
tauschen** — und zwar lautlos: Es gibt keine Fehlermeldung, nur eine Seite,
die anders aussieht als vorher. Sich durch ein paar Seiten zu klicken findet
das nicht zuverlässig.

Diese Werkzeuge rechnen es stattdessen aus. Entstanden in P0/A3, wo sie genau
einen echten Fall gefunden haben (`.keybox` und `.paircode` sitzen auf
`pair.php` am selben Element und setzen beide den Rahmen — nach dem
Umsortieren hätte der Kopplungscode seine orange Umrandung verloren).

## Die zwei Prüfungen

**1. Kaskadenvergleich (`kaskade.py`) — schnell, ohne Browser.**
Vergleicht zwei Stände auf Textebene und beantwortet zwei Fragen:

- Ist für jede Kombination aus Selektor und Eigenschaft der **wirksame Wert**
  derselbe geblieben?
- Hat sich bei zwei Regeln mit **gleicher Spezifität**, die **dieselbe
  Eigenschaft** setzen und **dasselbe Element treffen können**, die
  Reihenfolge umgedreht?

Für die dritte Bedingung liest `klassen.py` aus PHP und JS, welche Klassen im
Markup jemals **gemeinsam** an einem Element stehen. Ohne diese Auskunft
müsste der Prüfer jede Klasse für mit jeder kombinierbar halten und meldete
Hunderte Scheinfälle.

```
python3 kaskade.py <alt.css> <neu.css>
```

**2. Berechnete Stile im Browser (`stilvergleich.js`) — der eigentliche
Nachweis.** Lädt **dieselbe DOM** einmal mit dem alten und einmal mit dem
neuen Stylesheet in Chromium und vergleicht für **jedes Element** alle
Eigenschaften, die in einem der beiden Stylesheets überhaupt vorkommen — bei
neun Fensterbreiten, damit auch Media Queries mitgeprüft sind.

```
python3 proben.py <alt.css> [<neu.css>] [<ausgabeordner>]
node stilvergleich.js <ausgabeordner> <alt.css> <neu.css>
```

`PROBEN=seiten.html,katalog.html` wählt einzelne Proben aus (Vorgabe: die
ersten beiden). `CHROMIUM=<pfad>` setzt den Browser.

## Die vier Proben

| Probe | Was sie abdeckt |
|---|---|
| `seiten.html` | Das Markup aller Seiten (PHP entfernt, **alle** Zweige bleiben stehen) |
| `js_markup.html` | Markup, das erst im Browser entsteht — HTML-Zeichenketten der JS-Module |
| `katalog.html` | Ein Element je Selektor aus `style.css` — fängt Regeln, die im echten Markup nicht vorkommen |
| `pseudo.html` | Hover-, Fokus- und Aktiv-Zustände; die Pseudoklassen werden in **beiden** Ständen gleich durch echte Klassen ersetzt |

## Härtetest für die Meldungen aus `kaskade.py`

Meldet `kaskade.py` vertauschte Paare, heißt das noch nicht, dass etwas kaputt
ist — die beiden Selektoren müssen dasselbe Element treffen **können**. Der
zuverlässige Weg: für jedes gemeldete Paar ein Element bauen, das **beide**
gleichzeitig trifft, und messen. Was dabei auffällt, im Markup nachsehen (ein
`<a>` gleichzeitig in `.aktionsliste` und `.daylist` gibt es nicht). In A3
waren so 44 von 253 Paaren auffällig und alle 44 unerreichbar.

## Grenzen

- Die Proben sind **statisches** Markup. Zustände, die erst durch Bedienung
  entstehen (aufgeklappte Menüs, gefüllte Tabellen), sind nur so weit
  abgedeckt, wie sie in den Vorlagen stehen.
- Verglichen werden **berechnete Werte**, nicht Pixel. Ein Unterschied, der
  sich erst aus dem Zusammenspiel mehrerer Elemente ergibt, fällt nur auf,
  wenn er sich in einer Eigenschaft niederschlägt.
- `kaskade.py` kennt kein `!important` — in `style.css` gibt es drei, alle in
  Regeln, die nicht verschoben werden.
- Die Klassen-Koexistenz wird aus dem Quelltext erhoben. Was dort nie
  vorkommt, gilt als **unbenutzt** und damit als kollisionsfrei; eine Klasse,
  die ein Skript vollständig zur Laufzeit zusammensetzt, kann so falsch
  eingeordnet werden.

## Dateien

| Datei | Rolle |
|---|---|
| `cssparse.py` | kleiner CSS-Leser (Regeln in Dokumentreihenfolge, @-Kontext) |
| `klassen.py` | erhebt aus PHP/JS, welche Klassen gemeinsam an einem Element stehen |
| `kaskade.py` | Kaskadenvergleich zweier Stände |
| `chunks.py` | zerlegt `style.css` in Blöcke — Gliederungsansicht beim Umsortieren |
| `proben.py` | erzeugt die vier Proben und die Pseudoklassen-Stylesheets |
| `stilvergleich.js` | misst die berechneten Stile in Chromium (braucht Playwright) |
