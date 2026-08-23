# -*- coding: utf-8 -*-
"""Zerlegt style.css in Bloecke: ein Kommentar am Zeilenanfang beginnt einen
neuen Block, alles bis zum naechsten gehoert dazu."""
import io, re
from cssparse import CSS

def zerlege(pfad):
    zeilen = io.open(pfad, encoding='utf-8').read().split('\n')
    bloecke, akt, in_c = [], [], False
    for l in zeilen:
        if not in_c and l.startswith('/*'):
            if akt: bloecke.append(akt)
            akt = []
            if '*/' not in l: in_c = True
        elif in_c and '*/' in l:
            in_c = False
        akt.append(l)
    if akt: bloecke.append(akt)
    return ['\n'.join(b) for b in bloecke]

def titel(b):
    for l in b.split('\n'):
        s = l.strip()
        if s.startswith('/*'):
            return re.sub(r'^/\*+\s*|\s*\*+/?\s*$', '', s)[:70]
    return '(ohne Kommentar)'

def selektoren(b):
    out = []
    ohne = re.sub(r'/\*.*?\*/', '', b, flags=re.S)
    for m in re.finditer(r'(^|\})\s*([^{}@][^{}]*?)\{', ohne, re.S):
        out.append(' '.join(m.group(2).split())[:60])
    for m in re.finditer(r'@[a-z-]+[^{]*', ohne):
        out.append(m.group(0).strip()[:50])
    return out

if __name__ == '__main__':
    bs = zerlege(CSS)
    for i, b in enumerate(bs):
        print('%3d [%3d Z] %-70s | %s' % (i, b.count('\n')+1, titel(b), '; '.join(selektoren(b))[:120]))
