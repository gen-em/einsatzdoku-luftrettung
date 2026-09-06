# Stilvergleich — nachrechnen, dass sich am Erscheinungsbild nichts geändert hat

Wird **nicht ausgeliefert** (`tools/` ist vom Deploy ausgenommen).

> ## Neu geeicht nach P3 (O12)
>
> Dieses Werkzeug ist auf die Frage gebaut: **Hat sich etwas geändert?** In
> Phase P3 (Oberflächen-Redesign) hat sich alles geändert — Stylesheet,
> Bausteine, Klassennamen, Schwellen. Es hat deshalb während der ganzen Phase
> **geruht**; an seiner Stelle standen `tools/vollstaendigkeit/` (ist etwas
> verlorengegangen?) und `tools/screenshots/` (sieht es in allen acht Breiten
> so aus, wie es soll?). Beide bleiben in Gebrauch.
>
> **Ab P4 wacht der Stilvergleich wieder.** Drei Dinge sind dafür in O12
> geändert worden:
>
> - **Dreizehn Breiten statt neun.** Die alten (1400 … 500) lagen um die
>   Schwellen von P0. Das Redesign hat andere — 720, 1024, 1200, 1600 —, und
>   ohne 1024 und 1600 hätte der Vergleich die halben Media-Blöcke nie
>   gesehen. Jetzt: `1920, 1680, 1440, 1280, 1100, 1024, 900, 768, 720, 560,
>   420, 390, 360`, jede Schwelle von beiden Seiten.
> - **Die Seitenprobe liest jetzt auch PHP-Zeichenketten.** Das war der blinde
>   Fleck, vor dem die Grenzen-Liste unten seit P0 warnte: `entphp()` schneidet
>   alles zwischen `<?php` und `?>` heraus, und seit P3 baut `ui.php` das
>   Markup mit `echo '<div class="zeile">'` — also *innerhalb* eines
>   PHP-Blocks. Gemessen: Die Probe wuchs von 114 205 auf 119 726 Zeichen, die
>   Zahl der abgedeckten Klassen von **228 auf 253**. Neu dabei sind die
>   Innereien der Bausteine — `zeile-haupt`, `zeile-klein`, `zeile-text`,
>   `zeile-aktionen`, `kennzahl-wert`, `uebersicht-zeile`, `plakette-weg` und
>   neunzehn weitere.
> - **`klassen.py` bleibt in Gebrauch, aber nicht als Sollmenge.** Es zählt
>   jedes Wort im Quelltext als möglichen Klassennamen (14 784 zum Stand
>   Web 8.0.1) und ist damit für die Kaskadenfrage richtig gebaut, für eine
>   Vollständigkeitsprüfung aber unbrauchbar. Die rauschfreie Menge — die
>   Klassen aus den **Selektoren** des Stylesheets — steht in
>   `tools/vollstaendigkeit/vorher-klassen.txt`.
>
> **Und die Regel, unter der er ab P4 gelesen wird:** Bei einer
> *beabsichtigten* Gestaltungsänderung ist das Ergebnis keine Null, sondern
> eine **Liste**. Sie wird gegen die Liste der geplanten Änderungen gehalten;
> jede Abweichung darüber hinaus ist unbeabsichtigt und wird geklärt, bevor
> committet wird.

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
**dreizehn** Fensterbreiten, damit auch Media Queries mitgeprüft sind.

```
python3 proben.py <alt.css> [<neu.css>] [<ausgabeordner>]
PROBEN=seiten.html,katalog.html,js_markup.html \
  node stilvergleich.js <ausgabeordner> <alt.css> <neu.css>
PROBEN=pseudo.html \
  node stilvergleich.js <ausgabeordner> <ausgabeordner>/pseudo_alt.css \
                                        <ausgabeordner>/pseudo_neu.css
```

`PROBEN=…` wählt die Proben aus (Vorgabe: die ersten beiden).
`CHROMIUM=<pfad>` setzt den Browser.

> **Zwei Läufe, und der zweite braucht die umgeschriebenen Stylesheets.** Die
> Pseudoprobe misst Zustände, die es als Pseudoklasse gibt; `proben.py` hat
> sie dafür in beiden Ständen durch echte Klassen ersetzt und die Ergebnisse
> als `pseudo_alt.css` und `pseudo_neu.css` abgelegt. Wer `pseudo.html` gegen
> die **Original**-Stylesheets misst, misst einen Katalog ohne Zustände — und
> bekommt eine Zahl, die aussieht wie ein Nachweis. In S8/AP7 ist genau das
> passiert: Der Lauf meldete 6197 Abweichungen und **schwieg** zu der einen
> neuen Regel, um die es ging (`.feld-eingabe:disabled`). Erst der Lauf gegen
> `pseudo_neu.css` zeigte sie.

## Die vier Proben

| Probe | Was sie abdeckt |
|---|---|
| `seiten.html` | Das Markup aller Seiten (PHP entfernt, **alle** Zweige bleiben stehen) |
| `js_markup.html` | Markup, das erst im Browser entsteht — HTML-Zeichenketten der JS-Module |
| `katalog.html` | Ein Element je Selektor aus `style.css` — fängt Regeln, die im echten Markup nicht vorkommen |
| `pseudo.html` | Hover-, Fokus-, Aktiv- **und Sperrzustände**; die Pseudoklassen werden in **beiden** Ständen gleich durch echte Klassen ersetzt |

**`:disabled` steht seit S8/AP7 mit in der Ersetzungsliste.** Es ist kein
Bedienzustand wie `:hover`, aber es teilt dessen Problem: Der Katalog baut aus
einem Selektor ohne Tag ein `<div>`, und ein `<div>` lässt sich nicht sperren.
Die Regel `.feld-eingabe:disabled` wäre damit in **keiner** Probe gemessen
worden — und ein Werkzeug, das zu einer Regel schweigt, sieht aus wie eines,
das sie für unverändert hält.

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
- ~~Die Seitenprobe schneidet das Markup aus den **Seiten**; was aus einem
  Baustein in `ui.php` kommt, fehlt.~~ **Geschlossen in O12** (siehe oben):
  Die Probe liest jetzt zusätzlich die HTML-Schnipsel aus PHP-Zeichenketten,
  und `ui.php` steckt ohnehin im selben Glob. Was bleibt: Ein Baustein, der
  sein Markup über mehrere Zeichenketten hinweg zusammensetzt, erscheint in
  der Probe **zerlegt** — die einzelnen Stücke werden gemessen, ihr
  Zusammenspiel nicht.
- Die beiden Dialogkaesten (`.confirmbox`, `.unlockbox`) entstehen als
  `<dialog>` im Skript und stehen in **keiner** Markup-Probe; fuer sie traegt
  der Selektorkatalog den Nachweis.

## Dateien

| Datei | Rolle |
|---|---|
| `cssparse.py` | kleiner CSS-Leser (Regeln in Dokumentreihenfolge, @-Kontext) |
| `klassen.py` | erhebt aus PHP/JS, welche Klassen gemeinsam an einem Element stehen |
| `kaskade.py` | Kaskadenvergleich zweier Stände |
| `chunks.py` | zerlegt `style.css` in Blöcke — Gliederungsansicht beim Umsortieren |
| `proben.py` | erzeugt die vier Proben und die Pseudoklassen-Stylesheets |
| `stilvergleich.js` | misst die berechneten Stile in Chromium (braucht Playwright) |
