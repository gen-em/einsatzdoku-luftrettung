# Kettenaufrufe

Passt jeder Werkzeugaufruf zu der Schnittstelle des Werkzeugs, das er ruft?
**Anlass: Nr. 217** — ein Aufruf mit einem Schalter, den es nicht gab.

## Aufruf

```bash
python3 tools/kettenaufrufe/pruefen.py    # --liste · --probe
```

## Was es misst

Jede `uses:`- und `run:`-Zeile in `.github/workflows/` und jeden `aufruf`
in `tools/pruefstand/pruefablauf.json`. Zu jedem Aufruf wird die
Schnittstelle **aus dem Quelltext des Werkzeugs** ermittelt — `argparse`,
`getopt`, ein `case`-Block, eine `BEKANNT`-Menge, die Namensliste eines
Sammelläufers (`NAMEN=(…)`, `RUF`) — und der Aufruf dagegen gehalten.

## Was es braucht

Nichts. Kein Netz, keine Datenbank, keine Installation.

## Erwartete Zahl

**0 Befunde.** Die zweite Zahl ist „ungeprüft": Aufrufe, deren Werkzeug
keine erkennbare Schnittstelle hat. **Sie ist kein Beiwerk** — der
Stilvergleich stand darin und war kaputt (F-PK-20), `uhr-stufe1` stand
nicht einmal darin und war es auch (F-PK-21). Stand 24.09.2026: **85
Aufrufe, 0 Befunde, 2 ungeprüft**; `--probe` → **21 von 21** Fällen.

## Was es nicht kann

**Pflichtargumente sieht es nur halb.** Bei Python-Werkzeugen mit argparse
kennt es seit PK-05 die Unterbefehle und deren Pflichtschalter (F-PK-32);
Positionsargumente und die Pflichtargumente eines Shell-Unterbefehls sieht es
nicht (F-PK-21). Und es misst nur die Form — ob ein Aufruf das Richtige tut, sagt
erst ein Lauf.
