# -*- coding: utf-8 -*-
"""Kleiner CSS-Leser: liefert die Regeln in Dokumentreihenfolge.

Kein vollstaendiger Parser — er kann genau das, was style.css braucht:
Kommentare, @media/@supports-Bloecke, @font-face, Regeln mit
Selektorlisten und Deklarationen.
"""
import re, io
import os
HIER   = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.normpath(os.path.join(HIER, '..', '..'))
SERVER = os.path.join(WURZEL, 'server')
CSS    = os.path.join(SERVER, 'assets', 'style.css')

class Regel:
    def __init__(self, sel, decls, at, zeile, roh):
        self.sel = sel          # einzelner Selektor (Listen sind aufgeteilt)
        self.decls = decls      # [(prop, wert)]
        self.at = at            # umgebender @-Kontext ('' = keiner)
        self.zeile = zeile
        self.roh = roh          # Rohtext der Regel (mit Selektorliste)
    def __repr__(self):
        return '<%s @%s Z%d>' % (self.sel, self.at, self.zeile)

def _decls(text):
    out = []
    for teil in re.split(r';(?![^(]*\))', text):
        teil = teil.strip()
        if not teil or ':' not in teil:
            continue
        p, _, w = teil.partition(':')
        out.append((p.strip().lower(), ' '.join(w.split())))
    return out

def lies(pfad):
    src = io.open(pfad, encoding='utf-8').read()
    # Kommentare merken (fuer Zeilennummern) und entfernen
    ohne = re.sub(r'/\*.*?\*/', lambda m: '\n' * m.group(0).count('\n'), src, flags=re.S)
    regeln, i, n = [], 0, len(ohne)
    def zeile_von(pos): return ohne.count('\n', 0, pos) + 1

    def block(start, ende, at):
        i = start
        while i < ende:
            j = ohne.find('{', i)
            if j < 0 or j >= ende: break
            kopf = ohne[i:j].strip()
            if kopf.startswith('@') and not kopf.startswith('@font-face'):
                # verschachtelter At-Block: passende schliessende Klammer suchen
                tiefe, k = 1, j + 1
                while k < ende and tiefe:
                    if ohne[k] == '{': tiefe += 1
                    elif ohne[k] == '}': tiefe -= 1
                    k += 1
                block(j + 1, k - 1, (at + ' ' + ' '.join(kopf.split())).strip())
                i = k
                continue
            k = ohne.find('}', j)
            if k < 0: break
            koerper = ohne[j+1:k]
            d = _decls(koerper)
            for sel in [s.strip() for s in re.split(r',(?![^(]*\))', kopf) if s.strip()]:
                regeln.append(Regel(' '.join(sel.split()), d, at, zeile_von(j), kopf))
            i = k + 1
    block(0, n, '')
    return regeln
