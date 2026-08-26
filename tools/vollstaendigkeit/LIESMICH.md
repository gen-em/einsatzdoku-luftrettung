# Vollständigkeitsprüfung des Stylesheets

Entstanden in P3 (Konzept, Anlage E). Sie beantwortet die Frage, die der
Stilvergleich in einem Redesign nicht beantworten kann.

## Warum es sie gibt

`tools/stilvergleich/` misst, **ob sich etwas geändert hat**. Das ist die
richtige Frage nach einer Aufräumrunde und die falsche in P3: Dort ändert
sich alles, und das Werkzeug liefert Tausende Abweichungen, die niemand
gegen einen Plan hält. Es ruht während P3 (Vermerk in seiner `LIESMICH.md`)
und wird in O12 neu geeicht.

Diese Prüfung stellt zwei andere Fragen:

1. **Ist etwas verlorengegangen?** Das alte Stylesheet kannte 220 Klassen.
   Jede muss am Ende entweder eine Regel im neuen haben oder mit Begründung
   auf der Streichliste stehen. Eine Klasse, die stillschweigend verschwindet,
   nimmt eine Anzeige mit — und das fällt erst auf, wenn jemand die Seite
   aufruft, auf der sie stand.
2. **Steht jeder Wert an der einen Stelle?** Farben, Schriftgrößen und Maße
   gehören in `:root` und nirgends sonst. Das alte Stylesheet hatte 78
   Hexwerte außerhalb, 21 verschiedene Schriftgrößen und die Kopfhöhe fünfmal
   als `50px` fest verdrahtet.

## Aufruf

```
python3 tools/vollstaendigkeit/pruefen.py                 # prüfen
python3 tools/vollstaendigkeit/pruefen.py --ausfuehrlich  # alle Fundstellen
python3 tools/vollstaendigkeit/pruefen.py --vorher        # Sollmenge neu setzen
```

Kein PHP, keine Datenbank, kein Browser — nur Python 3. Rückgabewert ≠ 0,
sobald ein Befund vorliegt.

`--vorher` überschreibt `vorher-klassen.txt` mit den Klassen des **jetzigen**
Stylesheets. Das ist ein Werkzeug für den Anfang einer Phase, nicht für
zwischendurch: Wer es mitten in P3 laufen lässt, erklärt den Zwischenstand
zur Sollmenge und verliert damit genau die Auskunft, um die es geht.

## Die fünf Prüfungen

| Nr. | Was | Sollwert |
|---|---|---|
| 1 | Klassen ohne Gegenstück — aus `vorher-klassen.txt` gegen Stylesheet und `streichliste.md`; dazu die Gegenrichtung (im Markup benutzt, nirgends beschrieben) | 0 |
| 2 | Werte außerhalb der Token — Hexfarben, `rgb()`, Schriftgrößen, Pixelmaße, `50px`-Reste, `style="…"` in PHP/JS | 0 außer `ausnahmen.md` |
| 3 | Symbole — Inline-SVG mit Pfaden, Unicode-Zeichen als Symbol, Emoji, Verweise auf fehlende Dateien, Dateien ohne Anker `id="i"` | 0 |
| 4 | Knopfregel — jede Höhenangabe an einer `.knopf`-Regel kommt aus `--knopf` | 0 |
| 5 | Ausgabe — je Prüfung Zahl und Liste mit `Datei:Zeile` | — |

Zusätzlich als **Hinweis** (kein Befund): Regeln im Stylesheet, deren Klasse
im Markup nicht vorkommt, und Symboldateien, auf die nichts verweist. Beides
kann richtig sein — eine Klasse kann zur Laufzeit zusammengesetzt werden, ein
Symbol kann für ein späteres Paket schon dabeiliegen.

## Die beiden Hilfslisten

`streichliste.md` und `ausnahmen.md` sind Markdown-Tabellen, damit ein Mensch
sie liest. Beide verlangen eine **Begründung**; ein Eintrag ohne Grund ist
keiner, sondern ein weggedrücktes Ergebnis.

Die erste Spalte von `ausnahmen.md` ist der **Eigenschaftsname**, nicht die
Zeilennummer — so überlebt die Liste jede Umsortierung des Stylesheets.

## Was das Werkzeug nicht kann

- **Es misst Text, keine Darstellung.** Ob eine Regel richtig aussieht, sagt
  nur der Browser. Dafür ist `tools/screenshots/` da.
- **Klassen aus zusammengesetzten Zeichenketten** (`'imp-' + art`) erkennt es
  nicht als Literal und meldet sie in der Gegenrichtung nicht. Das ist
  Absicht: Die erste Fassung zählte jedes Wort im Quelltext als Klasse und
  kam auf 14 784 — eine Zahl, mit der niemand etwas anfangen kann. Gemeldet
  wird nur, was als Literal belegt ist.
- **Der Vorher-Stand ist die Klassenmenge des alten Stylesheets**, nicht die
  des Markups. Das ist die rauschfreie Menge; die Markupseite läuft als
  Gegenprobe mit.

## Erhebung vom 26.08.2026 (Beginn P3)

| | |
|---|---|
| Klassen im alten Stylesheet | **220** |
| Klassen im Markup als Literal belegt | 208 |
| davon ohne Regel und ohne Streichung | **22** — Bestandsfund, siehe Konzept 9.2 |
| Hexfarben außerhalb `:root` | **78** |
| `rgb()`/`rgba()` außerhalb `:root` | 8 |
| Schriftgrößen außerhalb der Skala | 71 Stellen |
| Pixelmaße außerhalb der Token | 154 |
| `50px`-Reste | **5** |
| `style="…"` in PHP/JS | **14** |
| Inline-SVG mit Pfaden | **5** |
| Unicode-Zeichen als Symbol | 147 |
| Emoji im Markup | 80 |
