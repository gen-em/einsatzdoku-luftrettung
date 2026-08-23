# -*- coding: utf-8 -*-
"""
Kaskaden-Vergleich zweier CSS-Staende.

Die Frage, die beim Umsortieren zaehlt, ist nicht "steht ueberall dasselbe
drin", sondern: Gewinnt fuer jede Eigenschaft weiterhin dieselbe Deklaration?
Das haengt an drei Dingen — Spezifitaet, Dokumentreihenfolge und daran, ob
zwei Selektoren ueberhaupt dasselbe Element treffen koennen.

Geprueft wird deshalb paarweise: Fuer je zwei Regeln, die
  (a) dieselbe Eigenschaft setzen,
  (b) dieselbe Spezifitaet haben und
  (c) nicht nachweislich verschiedene Elemente treffen,
muss die Reihenfolge erhalten bleiben. Kehrt sie sich um, aendert sich das
Ergebnis — solche Faelle werden gemeldet.

Zusaetzlich: Der Bestand an (Selektor, @-Kontext, Eigenschaft, Wert) wird
verglichen, damit nichts still verschwindet oder dazukommt.
"""
import re, sys, os
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import cssparse
import klassen

_ALLE, _PAARE, _ = klassen.erhebe([cssparse.SERVER])

def spezifitaet(sel):
    s = re.sub(r'::[a-z-]+', '', sel)                 # Pseudoelemente zaehlen als Typ
    pe = len(re.findall(r'::[a-z-]+', sel))
    a = len(re.findall(r'#[\w-]+', s))
    b = len(re.findall(r'\.[\w-]+', s)) + len(re.findall(r'\[[^\]]+\]', s)) \
        + len(re.findall(r':(?!:)(?!not\b)[a-z-]+', s))
    # :not(...) zaehlt selbst nicht, sein Inhalt schon
    for inner in re.findall(r':not\(([^)]*)\)', s):
        a += len(re.findall(r'#[\w-]+', inner))
        b += len(re.findall(r'\.[\w-]+', inner))
    rest = re.sub(r'[#.][\w-]+|\[[^\]]+\]|:[a-z-]+(\([^)]*\))?', ' ', s)
    c = len([t for t in re.split(r'[\s>+~,]+', rest) if t and t != '*']) + pe
    return (a, b, c)

def schluessel(sel):
    """Rechtester Verbund eines Selektors — was das Element selbst tragen muss."""
    teil = re.split(r'\s*[>+~]\s*|\s+', sel.strip())[-1]
    return teil

def disjunkt(s1, s2):
    """True, wenn die beiden Selektoren sicher NIE dasselbe Element treffen."""
    k1, k2 = schluessel(s1), schluessel(s2)
    def zerlege(k):
        pe  = re.findall(r'::([a-z-]+)', k)
        k   = re.sub(r'::[a-z-]+', '', k)
        ids = set(re.findall(r'#([\w-]+)', k))
        cls = set(re.findall(r'\.([\w-]+)', k))
        tag = re.match(r'^([a-zA-Z][\w-]*)', k)
        return (tag.group(1).lower() if tag else None), ids, cls, set(pe)
    t1, i1, c1, p1 = zerlege(k1)
    t2, i2, c2, p2 = zerlege(k2)
    if p1 != p2:                    return True   # verschiedene Pseudoelemente
    if t1 and t2 and t1 != t2:      return True   # verschiedene Elementtypen
    if i1 and i2 and i1 != i2:      return True   # verschiedene Kennungen
    # Klassen: Was im Markup nie gemeinsam an einem Element steht, kann sich
    # auch nicht in die Quere kommen. Unbekannte Klassen gelten als moeglich.
    for a in c1:
        for b in c2:
            if a == b:                      continue
            # Eine Klasse, die in keiner Quelle vorkommt, steht an keinem
            # Element — sie kann mit nichts kollidieren. (Sie ist ein Fund
            # fuer A4, aber kein Kaskadenrisiko.)
            if a not in _ALLE or b not in _ALLE: return True
            if (a, b) not in _PAARE:        return True
    return False

def bestand(regeln):
    m = {}
    for r in regeln:
        for p, w in r.decls:
            m.setdefault((r.at, r.sel, p), []).append(w)
    return m

def letzte_stelle(regeln):
    """{(at, sel, prop): letzter Index}. Die letzte Deklaration entscheidet —
    genau sie tritt gegen die anderer Selektoren an."""
    m = {}
    for i, r in enumerate(regeln):
        for p, w in r.decls:
            m[(r.at, r.sel, p)] = i
    return m

def ordnung(regeln):
    """Menge der Paare (S vor T), die bei gleicher Spezifitaet entscheiden."""
    letzte = letzte_stelle(regeln)
    nach_prop = {}
    for (at, sel, p), i in letzte.items():
        nach_prop.setdefault(p, []).append((i, sel, at))
    out = set()
    for p, liste in nach_prop.items():
        liste.sort()
        for x in range(len(liste)):
            for y in range(x + 1, len(liste)):
                _, sa, aa = liste[x]
                _, sb, ab = liste[y]
                if spezifitaet(sa) != spezifitaet(sb): continue
                if disjunkt(sa, sb):                   continue
                out.add((sa, aa, sb, ab, p))
    return out

def vergleich(alt_pfad, neu_pfad):
    A = cssparse.lies(alt_pfad)
    N = cssparse.lies(neu_pfad)
    print('Regeln: alt %d, neu %d' % (len(A), len(N)))

    ba, bn = bestand(A), bestand(N)
    fehlt = {k: v for k, v in ba.items() if k not in bn}
    neu_d = {k: v for k, v in bn.items() if k not in ba}
    geaendert = {k: (ba[k], bn[k]) for k in ba if k in bn and ba[k][-1] != bn[k][-1]}

    print('\n== Wirksamer Wert je (Selektor, Eigenschaft) ==')
    print('  entfallen: %d, neu: %d, anderer Endwert: %d' % (len(fehlt), len(neu_d), len(geaendert)))
    for k, v in sorted(fehlt.items())[:60]:      print('   - %s %s: %s' % (k[1], k[2], v))
    for k, v in sorted(neu_d.items())[:60]:      print('   + %s %s: %s' % (k[1], k[2], v))
    for k, (v1, v2) in sorted(geaendert.items())[:60]:
        print('   ~ %s %s: %s -> %s' % (k[1], k[2], v1[-1], v2[-1]))

    oa, on = ordnung(A), ordnung(N)
    umgedreht = [(sa, aa, sb, ab, p) for (sa, aa, sb, ab, p) in oa if (sb, ab, sa, aa, p) in on]
    print('\n== Umgekehrte Reihenfolge bei gleicher Spezifitaet und gleicher Eigenschaft ==')
    print('  Faelle: %d' % len(umgedreht))
    for sa, aa, sb, ab, p in sorted(umgedreht)[:80]:
        print('   %-40s @%-18s  <->  %-40s @%-18s  %s' % (sa, aa or '-', sb, ab or '-', p))
    return len(umgedreht), len(fehlt), len(neu_d), len(geaendert)

if __name__ == '__main__':
    vergleich(sys.argv[1], sys.argv[2])
