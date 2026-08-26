# -*- coding: utf-8 -*-
"""
Kontraste der Token nachrechnen (P3, P-P3-05).

WOFUER. Anlage G des Konzepts fuehrt eine Kontrasttabelle. Eine abgeschriebene
Tabelle veraltet in dem Augenblick, in dem jemand einen Token aendert — und
merkt es nicht. Dieses Skript liest die Werte aus dem Stylesheet und rechnet
sie nach; docs/Design.md wird daraus erzeugt, nicht gepflegt.

Gerechnet wird nach WCAG 2.1 (relative Luminanz, sRGB). Zwei Schwellen:
  4.5:1  Schrift in normaler Groesse
  3.0:1  grosse Schrift (ab 18 px oder 14 px fett) und Raender von
         Bedienelementen (WCAG 1.4.11)

Aufruf:  python3 tools/screenshots/kontrast.py [--json]
Rueckgabewert != 0, wenn ein Paar seine Schwelle verfehlt.
"""
import io
import json
import os
import re
import sys

HIER = os.path.dirname(os.path.abspath(__file__))
CSS = os.path.join(HIER, '..', '..', 'server', 'assets', 'style.css')


def token_lesen():
    """Alle --name:#RRGGBB aus dem :root-Block. var()-Verweise werden
    aufgeloest, damit --linie-stark:var(--gedaempft) einen Wert bekommt."""
    t = io.open(CSS, encoding='utf-8').read()
    t = re.sub(r'/\*.*?\*/', ' ', t, flags=re.S)
    m = re.search(r':root\s*\{(.*?)\n\}', t, re.S)
    roh = {}
    for name, wert in re.findall(r'--([\w-]+)\s*:\s*([^;]+);', m.group(1) if m else ''):
        roh[name] = wert.strip()
    aufgeloest = {}
    for name, wert in roh.items():
        for _ in range(4):
            v = re.match(r'var\(--([\w-]+)\)', wert)
            if not v:
                break
            wert = roh.get(v.group(1), wert)
        if re.fullmatch(r'#[0-9A-Fa-f]{6}', wert):
            aufgeloest[name] = wert.upper()
    return aufgeloest


def luminanz(hexwert):
    h = hexwert.lstrip('#')
    werte = []
    for i in (0, 2, 4):
        c = int(h[i:i + 2], 16) / 255
        werte.append(c / 12.92 if c <= 0.03928 else ((c + 0.055) / 1.055) ** 2.4)
    return 0.2126 * werte[0] + 0.7152 * werte[1] + 0.0722 * werte[2]


def kontrast(a, b):
    la, lb = luminanz(a), luminanz(b)
    hoch, tief = max(la, lb), min(la, lb)
    return (hoch + 0.05) / (tief + 0.05)


# Die Paare, die in der Oberflaeche tatsaechlich vorkommen — mit ihrer Rolle,
# denn die Rolle bestimmt die Schwelle.
PAARE = [
    ('Asphalt auf Schnee',                'asphalt',    'schnee',      4.5, 'Fliesstext'),
    ('Asphalt auf Rauch',                 'asphalt',    'rauch',       4.5, 'Fliesstext'),
    ('Dunkelblau auf Schnee',             'dunkelblau', 'schnee',      4.5, 'Titel, Symbole'),
    ('Dunkelblau auf Rauch',              'dunkelblau', 'rauch',       4.5, 'Titel, Symbole'),
    ('Gedaempft auf Schnee',              'gedaempft',  'schnee',      4.5, 'Kleinzeile'),
    ('Gedaempft auf Rauch',               'gedaempft',  'rauch',       4.5, 'Kleinzeile'),
    ('Blau tief auf Schnee',              'blau-tief',  'schnee',      4.5, 'Textlink'),
    ('Blau tief auf Blau hell',           'blau-tief',  'blau-hell',   4.5, 'Hinweis, Vollzug'),
    ('Rot tief auf Schnee',               'rot-tief',   'schnee',      4.5, 'Fehlertext'),
    ('Rot tief auf Rosa',                 'rot-tief',   'rosa',        4.5, 'Fehlermeldung'),
    ('Asphalt auf Orange hell',           'asphalt',    'orange-hell', 4.5, 'Warnung, Plakette'),
    ('Dunkelblau auf Blau hell',          'dunkelblau', 'blau-hell',   4.5, 'Plakette'),
    ('Dunkelblau auf Orange (Primaerknopf)', 'knopf-primaer-schrift', 'knopf-primaer-flaeche', 4.5, 'Primaerknopf'),
    ('Weiss auf Dunkelblau (Kopfleiste)', 'auf-dunkel', 'dunkelblau',  4.5, 'Kopfleiste'),
    ('Orange tief auf Schnee',            'orange-tief', 'schnee',     3.0, 'nur gross oder fett'),
    ('Orange tief auf Rauch',             'orange-tief', 'rauch',      3.0, 'nur gross oder fett'),
    ('Orange tief auf Orange hell',       'orange-tief', 'orange-hell', 3.0, 'Warnung, Auftakt fett'),
    ('Rot auf Schnee (Gefahrknopf)',      'rot',        'schnee',      3.0, 'Rand und Schrift ab 18 px'),
    ('Blau als Fokusring',                'blau',       'schnee',      3.0, 'Rand'),
    ('Linie stark auf Schnee',            'linie-stark', 'schnee',     3.0, 'Rand von Bedienelementen'),
    ('Linie stark auf Rauch',             'linie-stark', 'rauch',      3.0, 'Rand von Bedienelementen'),
]

# Diese Paare sind ausdruecklich AUSGENOMMEN, und zwar mit Grund. Ohne die
# Liste wuerde jeder Lauf sie melden, jemand wuerde sie wegdruecken, und beim
# naechsten Mal draengte sich ein echter Befund dazwischen.
AUSNAHMEN = [
    ('Orange als Flaeche auf Schnee', 'orange', 'schnee',
     'Orange traegt nirgends allein: Der Primaerknopf hat dunkelblaue Schrift '
     'darauf (Zeile oben), der aktive Menuepunkt zusaetzlich Flaeche und Fettung, '
     'die Zeilenhervorhebung zusaetzlich Text. Wo ein oranger Strich doch allein '
     'stuende, tritt --orange-tief an seine Stelle. Anlage G nennt 2,2:1 und '
     'meint dieselbe Sache.'),
    ('Linie auf Schnee', 'linie', 'schnee',
     'Trennlinie zwischen Zeilen und Rand einer Karte — Zierrat, kein '
     'Bedienelement. WCAG 1.4.11 nimmt rein dekorative Begrenzungen aus. Wo eine '
     'Linie ein Bedienelement begrenzt, steht --linie-stark.'),
    ('Sand auf Schnee', 'sand', 'schnee',
     'Der Winkel des Akkordeons ist Mechanik, keine Botschaft: Er sagt nichts, '
     'was die aufklappbare Zeile daneben nicht auch sagt, und die ist in '
     'Dunkelblau beschriftet.'),
]


def main():
    tok = token_lesen()
    zeilen, schlecht = [], 0
    for name, a, b, soll, rolle in PAARE:
        if a not in tok or b not in tok:
            zeilen.append({'paar': name, 'fehler': 'Token fehlt: %s / %s' % (a, b)})
            schlecht += 1
            continue
        v = kontrast(tok[a], tok[b])
        ok = v + 1e-9 >= soll
        if not ok:
            schlecht += 1
        zeilen.append({'paar': name, 'vorn': tok[a], 'hinten': tok[b],
                       'wert': round(v, 2), 'soll': soll, 'rolle': rolle, 'ok': ok})

    if '--json' in sys.argv:
        print(json.dumps({'token': tok, 'paare': zeilen,
                          'ausnahmen': [{'paar': n, 'wert': round(kontrast(tok[a], tok[b]), 2),
                                         'grund': g} for n, a, b, g in AUSNAHMEN
                                        if a in tok and b in tok],
                          'verfehlt': schlecht}, indent=2, ensure_ascii=False))
        return 1 if schlecht else 0

    print('Kontraste der Token (WCAG 2.1, gerechnet aus server/assets/style.css)\n')
    print('%-40s %9s %6s  %s' % ('Paar', 'Ist', 'Soll', 'Rolle'))
    print('-' * 92)
    for z in zeilen:
        if 'fehler' in z:
            print('%-40s  %s' % (z['paar'], z['fehler']))
            continue
        print('%-40s %7.2f:1 %5.1f  %s%s' % (z['paar'], z['wert'], z['soll'], z['rolle'],
                                             '' if z['ok'] else '   << VERFEHLT'))
    print('\nAusgenommen, mit Grund:')
    for n, a, b, g in AUSNAHMEN:
        if a in tok and b in tok:
            print('  %-38s %7.2f:1' % (n, kontrast(tok[a], tok[b])))
            for zeile in [g[i:i + 74] for i in range(0, len(g), 74)]:
                print('      %s' % zeile)
    print('\n%d Paare gerechnet, %d verfehlt.' % (len(PAARE), schlecht))
    return 1 if schlecht else 0


if __name__ == '__main__':
    sys.exit(main())
