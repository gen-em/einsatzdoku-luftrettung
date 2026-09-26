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
         python3 tools/screenshots/kontrast.py --selbstprobe
Rueckgabewert != 0, wenn ein Paar seine Schwelle verfehlt oder ein Paar des
Stylesheets in keiner der beiden Listen steht.

DIE LISTE IST NICHT MEHR DAS MASS (seit R4-08, Nr. 116). Bis dahin rechnete
das Werkzeug nur, was in PAARE stand, und ein Paar, das dort fehlte, meldete
keinen Fehler — so standen in der App der orange Rueckstandspunkt (2,23:1)
und Rot als Schrift auf der Uhr unter dem Zielwert, waehrend jeder Lauf
gruen war (B-S5Z-13, -15; fuer Android geloest mit E-AR-09). Jetzt leitet
`ableiten()` die Paare aus den Regeln des Stylesheets ab:

  - Steht in EINER Regel eine Vordergrundfarbe (`color`, `border*`,
    `outline*`, `fill`, `stroke`, `text-decoration*`, `caret-color`) neben
    einer Flaeche (`background`, `background-color`), ist das ein Paar.
  - Eine Vordergrundfarbe ohne Flaeche in ihrer Regel muss als Vordergrund
    in einer Liste stehen; eine Flaeche ohne Vordergrund irgendwo.

Jedes abgeleitete Paar steht in PAARE (dann wird es gerechnet) oder in
AUSNAHMEN (mit Grund) — sonst ist es ein Befund. WAS ES NICHT SIEHT: eine
bekannte Vordergrundfarbe auf einer NEUEN Flaeche, die eine Elternregel
setzt. Die Flaeche steht fast nie in derselben Regel wie die Schrift; dafuer
gibt es den Bilderlauf. Dieselbe Grenze nennt das Android-Werkzeug.
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
    ('Orange tief auf Rauch',             'orange-tief', 'rauch',      3.0, 'nur gross oder fett; Strich des aktiven Reiters'),
    ('Orange tief auf Orange hell',       'orange-tief', 'orange-hell', 3.0, 'Warnung, Auftakt fett'),
    # Der Zielzustand: aktive Kennzahl, aktiver Listenfilter, aktives
    # Sprungziel. Die Kombination steht seit O6 in der Anwendung
    # (.kennzahl.aktiv, .listenfilter.aktiv) und wurde nie gerechnet —
    # eingetragen mit S9/AP5, als das Sprungziel als dritte Stelle dazukam.
    ('Dunkelblau auf Orange hell',        'dunkelblau', 'orange-hell', 4.5, 'aktives Sprungziel, aktive Kennzahl'),
    ('Rot auf Schnee (Gefahrknopf)',      'rot',        'schnee',      3.0, 'Rand und Schrift ab 18 px'),
    ('Blau als Fokusring',                'blau',       'schnee',      3.0, 'Rand'),
    ('Linie stark auf Schnee',            'linie-stark', 'schnee',     3.0, 'Rand von Bedienelementen'),
    ('Linie stark auf Rauch',             'linie-stark', 'rauch',      3.0, 'Rand von Bedienelementen'),
    # P5c/AP1 (E-P5c-05, -55, -59; M-P5c-02 a): die Kopfleiste einer Anlage
    # mit Etikett und der Umgebungsstreifen. Anlass: F-P5c-28 — das Orange
    # des aktiven Punkts haette auf Rot 2,10 : 1 und fiele unter 3 : 1.
    ('Weiss auf Rot (Kopfleiste Staging)', 'auf-dunkel', 'rot',        4.5, 'Kopfleiste mit Etikett'),
    ('Schnee auf Rot (Name im Kopf)',     'schnee',     'rot',         4.5, 'Name in der Kopfleiste mit Etikett'),
    ('Orange hell auf Rot (aktiver Punkt)', 'orange-hell', 'rot',      3.0, 'Strich des aktiven Kopfpunkts'),
    # SEIT R4-08 AUS DEN REGELN ABGELEITET (Nr. 116): Diese acht standen in
    # style.css, aber in keiner Liste. Alle erreichen ihren Sollwert.
    ('Asphalt auf Sand',                  'asphalt',    'sand',        4.5, 'Nummer eines offenen Schritts (Erststart)'),
    ('Blau auf Blau hell (Zitatstrich)',  'blau',       'blau-hell',   3.0, 'Strich am Zitat im Handbuch'),
    ('Blau tief auf Rauch',               'blau-tief',  'rauch',       4.5, 'Uebersichtszeile der Einstellungen unter dem Zeiger'),
    ('Dunkelblau auf Orange (Zaehler)',   'dunkelblau', 'orange',      4.5, 'oranger Zaehler, Zeichen im Einsatzort-Kreis'),
    ('Dunkelblau auf Sand (Zaehler)',     'dunkelblau', 'sand',        4.5, 'neutraler Zaehler'),
    ('Primaerschrift auf Orange (Auswahl)', 'knopf-primaer-schrift', 'orange', 4.5, 'Zahl des aktiven Filters, gewaehlte Segmenttaste'),
    ('Rauch auf Dunkelblau (dunkler Fuss)', 'rauch',    'dunkelblau',  4.5, 'Fusszeile auf dunklem Grund'),
    ('Rot auf Rosa (rote Kennzahl)',      'rot',        'rosa',        3.0, 'Rahmen der roten Kennzahl'),
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
     'Dunkelblau beschriftet. Dasselbe gilt fuer den Winkel der '
     'Einstellungs-Uebersicht und fuer den abgeschalteten Blaetterknopf '
     '(.seitenknopf.aus) — ein deaktiviertes Bedienelement nimmt WCAG 1.4.3 '
     'ausdruecklich aus. Sand als FLAECHE (Blattgriff, ausgeschalteter '
     'Schalter) faellt ohnehin nicht darunter, und Sand auf DUNKELBLAU '
     '(.kopf-nutzer) steht mit 8,15:1 weit ueber der Schwelle. '
     'SEIT O10 IST DAS DIE GANZE LISTE: Die Versionsnummer der Fusszeile trug '
     'Sand ebenfalls, und dort stimmte die Begruendung nicht — sie ist die '
     'Auskunft, mit der ein Fehlerbericht anfaengt, also ein zu LESENDER Text. '
     'Sie steht jetzt in --gedaempft (5,30:1). Wer diese Ausnahme kuenftig '
     'weiterreicht, pruefe zuerst, ob der Text gelesen werden soll.'),
    # SEIT R4-08 (Nr. 116) — die Paare, die die Ableitung fand und die ihren
    # Sollwert nicht erreichen, jedes mit Grund.
    ('Weiss auf Orange tief (Primaerknopf unter dem Zeiger)', 'auf-dunkel', 'orange-tief',
     'E-R4-30: 4,42:1 bei 15 px, Gewicht 600 — knapp unter 4,5. Die Betreiberin '
     'hat am 26.09.2026 entschieden, dass der Ton von --orange-tief passt '
     '(4,32:1 auf Schnee), und keine Farbe geaendert. Der Zustand ist '
     'voruebergehend; in Ruhe traegt der Knopf Dunkelblau auf Orange (5,97:1).'),
    ('Linie auf Rauch', 'linie', 'rauch',
     'Rahmen von Codeblock, <pre> im Handbuch und Tagesgruppe des Imports — '
     'Zierrat, kein Bedienelement; dieselbe Lage wie „Linie auf Schnee".'),
    ('Orange als Strich auf Orange hell', 'orange', 'orange-hell',
     'Aktiver Leisteneintrag, aktive Kennzahl, Kennzahl im Ton orange '
     '(1,97:1): Der Strich begleitet die orange-helle Flaeche, er traegt den '
     'Zustand nicht allein — dieselbe Sprache wie „Orange als Flaeche auf '
     'Schnee" (Design.md 3.1). Wo ein oranger Strich allein stuende, tritt '
     '--orange-tief an seine Stelle, wie seit Web 21.1.6 am Vorschlag.'),
    ('Orange tief auf Orange (hervorgehobener Phasenpunkt)', 'orange-tief', 'orange',
     'Rand des hervorgehobenen Phasenpunkts auf der Karte (.pm-chip.hl): Die '
     'orange Flaeche ist das Zeichen, der Rand fasst sie nur ein.'),
    ('Spurfarbe als Farbschluessel', 'spur-1', 'schnee',
     'Die Linie der Legende (.legende-linie) zeigt die Farbe der Spur auf der '
     'Karte; was sie bedeutet, sagt die Beschriftung daneben.'),
]


VORN = re.compile(r'(?:^|;)\s*(color|border(?:-[a-z]+)*|outline(?:-[a-z]+)*|fill|stroke|'
                  r'text-decoration(?:-color)?|caret-color)\s*:\s*([^;]*)')
HINTEN = re.compile(r'(?:^|;)\s*(background(?:-color)?)\s*:\s*([^;]*)')


def regeln_lesen(text):
    """[(Selektor, Deklarationen)] — Kommentare weg, @media-Bloecke aufgeloest."""
    text = re.sub(r'/\*.*?\*/', ' ', text, flags=re.S)
    aus = []

    def zerlege(s):
        i = 0
        while i < len(s):
            j = s.find('{', i)
            if j < 0:
                return
            sel, tiefe, k = s[i:j].strip(), 1, j + 1
            while tiefe and k < len(s):
                tiefe += {'{': 1, '}': -1}.get(s[k], 0)
                k += 1
            inhalt = s[j + 1:k - 1]
            if sel.startswith('@'):
                if '{' in inhalt:
                    zerlege(inhalt)
            elif sel != ':root':
                aus.append((' '.join(sel.split()), inhalt))
            i = k
    zerlege(text)
    return aus


def ableiten(text, tok):
    """(paare, nur_vorn, nur_hinten) — je Token bzw. Paar die Selektoren."""
    paare, nur_vorn, nur_hinten = {}, {}, {}
    for sel, d in regeln_lesen(text):
        def token(wert):
            return [x for x in re.findall(r'var\(--([\w-]+)\)', wert) if x in tok]
        vorn = [x for _, w in VORN.findall(d) for x in token(w)]
        hinten = [x for _, w in HINTEN.findall(d) for x in token(w)]
        if vorn and hinten:
            for a in vorn:
                for b in hinten:
                    if a != b:
                        paare.setdefault((a, b), set()).add(sel)
        elif vorn:
            for a in vorn:
                nur_vorn.setdefault(a, set()).add(sel)
        elif hinten:
            for b in hinten:
                nur_hinten.setdefault(b, set()).add(sel)
    return paare, nur_vorn, nur_hinten


def ohne_eintrag(text, tok, paare_liste=None, ausnahmen=None):
    """(Zahl der Paare, [(Schluessel, Befund)]) — Paare und Farben des
    Stylesheets, die in keiner Liste stehen. Der Schluessel ist das Paar oder
    die Farbe, damit die Selbstprobe nach DEM Befund fragen kann, den sie
    eingebaut hat, statt nur zu zaehlen."""
    paare_liste = PAARE if paare_liste is None else paare_liste
    ausnahmen = AUSNAHMEN if ausnahmen is None else ausnahmen
    bekannt = {(a, b) for _, a, b, *_ in paare_liste} | {(a, b) for _, a, b, _ in ausnahmen}
    vorn_bekannt = {a for a, _ in bekannt}
    alle_bekannt = vorn_bekannt | {b for _, b in bekannt}
    paare, nur_vorn, nur_hinten = ableiten(text, tok)
    befunde = []
    for (a, b), sels in sorted(paare.items()):
        if (a, b) not in bekannt:
            befunde.append(((a, b), '%s auf %s (%.2f:1) in %s' % (
                a, b, kontrast(tok[a], tok[b]), ', '.join(sorted(sels)[:3]))))
    for a, sels in sorted(nur_vorn.items()):
        if a not in vorn_bekannt:
            befunde.append((a, '%s als Vordergrund ohne Paar in %s' % (a, ', '.join(sorted(sels)[:3]))))
    for b, sels in sorted(nur_hinten.items()):
        if b not in alle_bekannt:
            befunde.append((b, '%s als Flaeche ohne Paar in %s' % (b, ', '.join(sorted(sels)[:3]))))
    return len(paare), befunde


def selbstprobe():
    """Die zwei Fehler, die Nr. 116 ausgeloest haben, im Web nachgebaut."""
    text = io.open(CSS, encoding='utf-8').read()
    tok = token_lesen()
    faelle = [
        ('GEGENPROBE: das Stylesheet, wie es ist', '', None),
        ('ein oranger Punkt auf Rauch, den keine Liste kennt (wie B-S5Z-13)',
         '\n.rueckstand-punkt{background:var(--rauch);color:var(--orange)}\n', ('orange', 'rauch')),
        ('Rot als Schrift auf einer neuen Flaeche (wie B-S5Z-15)',
         '\n.wartet{color:var(--rot);background:var(--blau-hell)}\n', ('rot', 'blau-hell')),
        ('eine Flaeche in einer Farbe, die in keiner Liste steht',
         '\n.neu-kasten{background:var(--spur-3)}\n', 'spur-3'),
    ]
    fehl = 0
    for name, zusatz, erwartet in faelle:
        _, befunde = ohne_eintrag(text + zusatz, tok)
        schluessel = [s for s, _ in befunde]
        ok = (not befunde) if erwartet is None else (erwartet in schluessel)
        fehl += 0 if ok else 1
        print('  [%s] %s (%d Befunde)' % ('ok  ' if ok else 'FEHL', name, len(befunde)))
    print('%d Faelle, %d Fehlschlaege' % (len(faelle), fehl))
    return 1 if fehl else 0


def main():
    if '--selbstprobe' in sys.argv:
        return selbstprobe()
    tok = token_lesen()
    abgeleitet, befunde = ohne_eintrag(io.open(CSS, encoding='utf-8').read(), tok)
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
                          'verfehlt': schlecht, 'abgeleitet': abgeleitet,
                          'ohne_eintrag': [b for _, b in befunde]}, indent=2, ensure_ascii=False))
        return 1 if schlecht or befunde else 0

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
    print('%d Paare aus den Regeln des Stylesheets abgeleitet, %d ohne Listeneintrag.'
          % (abgeleitet, len(befunde)))
    for _, b in befunde:
        print('  ! %s' % b)
    return 1 if schlecht or befunde else 0


if __name__ == '__main__':
    sys.exit(main())
