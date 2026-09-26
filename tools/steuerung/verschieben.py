#!/usr/bin/env python3
"""Einen erledigten Backlog-Punkt nach `docs/Backlog-Erledigt.md` verschieben.

    python3 tools/steuerung/verschieben.py NR "Erledigt TT.MM.JJJJ mit …: Beleg."
    python3 tools/steuerung/verschieben.py NR "…" --trocken    # nur zeigen

Rückgabewert 0 = verschoben (oder gezeigt) · 1 = nicht verschoben (Nummer
fehlt, Kopfzeile ohne Grammatik, Nummer steht schon in der Erledigt-Datei).

WOFÜR. Konzept SD sagt „wörtlich", aber nicht, was mit dem Stand geschieht;
E-R4-06 hat die Form festgelegt: Die Kopfzeile bleibt, `Stand:` wird
`erledigt`, und ein Schlusssatz mit Datum, Anlass und Beleg ist die letzte
Folgezeile, eingerückt mit fünf Leerzeichen. Von Hand sind das drei Stellen
in zwei Dateien je Punkt — und eine Runde verschiebt Dutzende.

Der Schlusssatz wird umbrochen; keine Zeile beginnt dabei mit Zahl und Punkt
(der Bestandsriegel läse dort eine Nummer, Kopf von `docs/Backlog.md`).
"""
import argparse
import os
import re
import sys
import textwrap

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
BACKLOG = os.path.join(WURZEL, 'docs', 'Backlog.md')
ERLEDIGT = os.path.join(WURZEL, 'docs', 'Backlog-Erledigt.md')

KOPF = re.compile(r'^(\d+)\. \*\*.+?\*\* · gehört zu: .+? · Stand: '
                  r'(offen|teilweise|zurückgestellt|nur auf Anlass|nicht umsetzen) · seit \d{2}\.\d{2}\.\d{4}$')
GRENZE = re.compile(r'^(\d+\. \*\*|## |---)')
EINZUG = '     '


def umbrechen(satz, breite=76):
    """Zeilen mit fünf Leerzeichen Einzug; keine beginnt mit Zahl und Punkt."""
    while breite > 40:
        zeilen = textwrap.wrap(satz, width=breite, initial_indent=EINZUG,
                               subsequent_indent=EINZUG, break_on_hyphens=False)
        if not any(re.match(r'\d+\.', z.strip()) for z in zeilen):
            return zeilen
        breite -= 1
    raise SystemExit('Schlusssatz lässt sich nicht ohne Zeile „Zahl und Punkt" umbrechen')


def main():
    ap = argparse.ArgumentParser(description=__doc__.split('\n')[0])
    ap.add_argument('nr', type=int)
    ap.add_argument('satz')
    ap.add_argument('--trocken', action='store_true')
    a = ap.parse_args()

    with open(BACKLOG, encoding='utf-8') as f:
        zeilen = f.read().split('\n')
    with open(ERLEDIGT, encoding='utf-8') as f:
        erledigt = f.read()

    start = next((i for i, z in enumerate(zeilen) if z.startswith(f'{a.nr}. **')), None)
    if start is None:
        print(f'Nr. {a.nr} steht nicht in docs/Backlog.md')
        return 1
    m = KOPF.match(zeilen[start])
    if not m:
        print(f'Nr. {a.nr}: Kopfzeile ohne Grammatik — erst berichtigen:\n  {zeilen[start]}')
        return 1
    if re.search(rf'^{a.nr}\. ', erledigt, re.M):
        print(f'Nr. {a.nr} steht schon in docs/Backlog-Erledigt.md')
        return 1

    ende = next((i for i in range(start + 1, len(zeilen)) if GRENZE.match(zeilen[i])), len(zeilen))
    eintrag = zeilen[start:ende]
    while eintrag and not eintrag[-1].strip():
        eintrag.pop()
    kopf = zeilen[start].replace(f' · Stand: {m.group(2)} · ', ' · Stand: erledigt · ', 1)
    neu = [kopf] + eintrag[1:] + umbrechen(a.satz)

    # Aus Backlog.md: der Eintrag und die Leerzeilen danach bis zur Grenze,
    # damit genau eine Leerzeile zwischen den Nachbarn bleibt.
    rest = zeilen[:start] + zeilen[ende:]
    if a.trocken:
        print('\n'.join(neu))
        return 0
    with open(BACKLOG, 'w', encoding='utf-8') as f:
        f.write('\n'.join(rest))
    with open(ERLEDIGT, 'w', encoding='utf-8') as f:
        f.write(erledigt.rstrip('\n') + '\n\n' + '\n'.join(neu) + '\n')
    print(f'Nr. {a.nr} verschoben: {len(eintrag)} Zeilen, Schlusssatz {len(neu) - len(eintrag)} Zeilen')
    return 0


if __name__ == '__main__':
    sys.exit(main())
