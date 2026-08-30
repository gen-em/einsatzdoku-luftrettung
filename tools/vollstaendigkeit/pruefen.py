# -*- coding: utf-8 -*-
"""
Vollstaendigkeitspruefung des Stylesheets (P3, Anlage E).

WOFUER. Der Stilvergleich beantwortet die Frage "hat sich etwas geaendert?".
In P3 aendert sich alles; die Frage ist eine andere: "ist etwas verlorengegangen,
und steht jeder Wert an der einen Stelle, an die er gehoert?"

Fuenf Pruefungen:
  1  Klassen ohne Gegenstueck  -- jede Klasse des ALTEN Stylesheets hat eine
     Regel im neuen oder einen Eintrag in streichliste.md. Dazu die Gegenrichtung:
     Klassen, die im Markup stehen, aber in keinem Stylesheet.
  2  Werte ausserhalb der Token -- Hexfarben, rgb(), Schriftgroessen und
     Pixelmasse ausserhalb :root; 50px-Reste; style="..."-Attribute in PHP/JS.
  3  Symbole -- Inline-SVG mit Pfaden, Unicode-Symbolzeichen, Emoji im Markup;
     Verweise auf fehlende Symboldateien; Dateien ohne Verweis (Hinweis).
  4  Knopfregel -- jede Hoehenangabe an einer .knopf-Regel kommt aus --knopf.
  5  Ausgabe -- je Pruefung Zahl und Liste, Rueckgabewert != 0 bei Befund.

Aufruf und Bedeutung stehen in LIESMICH.md daneben.
Kein PHP noetig; nur Python 3.
"""
import argparse
import io
import json
import os
import re
import sys

HIER   = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
SERVER = os.path.join(WURZEL, 'server')
CSS    = os.path.join(SERVER, 'assets', 'style.css')
SYMBOLE = os.path.join(SERVER, 'assets', 'images', 'symbole')

# Verzeichnisse, die nicht uns gehoeren: fremde Bibliotheken und Schriften.
FREMD = ('vendor', 'fonts', 'demo')


# ---------------------------------------------------------------- Einlesen
def quelldateien(wurzel=SERVER, endungen=('.php', '.js')):
    """Alle eigenen Quelldateien unter server/ — ohne vendor, fonts, demo."""
    for dp, dns, fns in os.walk(wurzel):
        dns[:] = [d for d in dns if d not in FREMD]
        for fn in sorted(fns):
            if fn.endswith(endungen):
                yield os.path.join(dp, fn)


def lies(pfad):
    return io.open(pfad, encoding='utf-8', errors='replace').read()


def kurz(pfad):
    return os.path.relpath(pfad, WURZEL)


def zeile_von(text, pos):
    return text.count('\n', 0, pos) + 1


# ------------------------------------------------- CSS: Kommentare, :root
def ohne_kommentare(css):
    """/* ... */ durch Leerzeichen ersetzen, Zeilenumbrueche erhalten.

    Die Umbrueche muessen bleiben, sonst zeigen alle Zeilennummern daneben —
    und eine Fundstelle ohne richtige Zeile ist keine Fundstelle."""
    return re.sub(r'/\*.*?\*/', lambda m: re.sub(r'[^\n]', ' ', m.group(0)), css, flags=re.S)


def ohne_media_praeludien(css):
    """@media-Bedingungen durch Leerzeichen ersetzen, Umbrueche erhalten.

    Die vier Schwellen stehen dort als Zahlen, weil Custom Properties in
    Media-Abfragen nicht funktionieren. Das ist kein Verstoss gegen "kein
    Wert ausserhalb der Token", sondern die einzige Bauform, die CSS
    anbietet."""
    return re.sub(r'@media[^{]*', lambda m: re.sub(r'[^\n]', ' ', m.group(0)), css)


def root_bloecke(css):
    """Spannen der :root-Bloecke als (start, ende) — dort duerfen Werte stehen."""
    spannen = []
    for m in re.finditer(r':root\s*\{', css):
        tiefe, i = 1, m.end()
        while i < len(css) and tiefe:
            if css[i] == '{':
                tiefe += 1
            elif css[i] == '}':
                tiefe -= 1
            i += 1
        spannen.append((m.start(), i))
    return spannen


def in_spannen(pos, spannen):
    return any(a <= pos < b for a, b in spannen)


def css_klassen(css):
    """Klassennamen, die in Selektoren vorkommen — ohne Deklarationsteile.

    Nur der Selektorteil wird abgesucht. Sonst zaehlt jede Zeichenkette in
    einem content:"." als Klasse."""
    css = ohne_kommentare(css)
    namen = set()
    # Selektor = alles vor der naechsten oeffnenden Klammer, ohne @-Regeln.
    for m in re.finditer(r'(^|[}\{;])\s*([^{}@;]+?)\{', css, re.S):
        sel = m.group(2)
        for k in re.findall(r'\.(-?[_A-Za-z][\w-]*)', sel):
            namen.add(k)
    return namen


# --------------------------------------------------- Markup: Klassen ernten
def markup_klassen(dateien):
    """Klassen aus dem Markup, getrennt nach 'sicher' und 'vermutet'.

    SICHER  steht als reines Literal in class="..." bzw. classList.add('x') —
            daran ist nicht zu deuteln.
    VERMUTET stammt aus einem Attribut mit PHP- oder JS-Ausdruck darin. Solche
            Namen koennen Variablennamen sein; sie werden nur gemeldet, wenn
            sie ausserdem im alten Stylesheet standen. Ohne diese Trennung
            meldet das Werkzeug Hunderte Scheinfaelle — genau daran ist die
            Klassenliste des Stilvergleichs gescheitert (14 784 "Klassen").
    """
    sicher, vermutet = {}, {}

    def merke(topf, name, pfad, zeile):
        topf.setdefault(name, set()).add('%s:%d' % (kurz(pfad), zeile))

    for pfad in dateien:
        t = lies(pfad)
        for m in re.finditer(r'class\s*=\s*(["\'])(.*?)\1', t, re.S):
            roh, z = m.group(2), zeile_von(t, m.start())
            hat_ausdruck = ('<?' in roh) or ('${' in roh) or ("' ." in roh)
            if not hat_ausdruck:
                for name in roh.split():
                    if re.fullmatch(r'-?[_A-Za-z][\w-]*', name):
                        merke(sicher, name, pfad, z)
            else:
                nackt = re.sub(r'<\?.*?\?>', ' ', roh, flags=re.S)
                nackt = re.sub(r'\$\{.*?\}', ' ', nackt, flags=re.S)
                for name in nackt.split():
                    if re.fullmatch(r'-?[_A-Za-z][\w-]*', name):
                        merke(sicher, name, pfad, z)
                for lit in re.findall(r"['\"]([\w \-]+)['\"]", roh):
                    for name in lit.split():
                        if re.fullmatch(r'-?[_A-Za-z][\w-]*', name):
                            merke(vermutet, name, pfad, z)
        for m in re.finditer(r'classList\.(?:add|toggle|remove)\(([^)]*)\)', t):
            z = zeile_von(t, m.start())
            for name in re.findall(r"['\"]([\w-]+)['\"]", m.group(1)):
                merke(sicher, name, pfad, z)
        for m in re.finditer(r'className\s*=\s*(["\'`])(.*?)\1', t, re.S):
            z = zeile_von(t, m.start())
            for name in re.split(r'[\s${}]+', m.group(2)):
                if re.fullmatch(r'-?[_A-Za-z][\w-]*', name or ''):
                    merke(sicher, name, pfad, z)
    return sicher, vermutet


# ------------------------------------------------------- Hilfslisten lesen
def liste_lesen(name, spalten=1):
    """Zeilen einer Markdown-Tabelle als Liste von Spaltenlisten.

    Die Hilfslisten sind Markdown, damit sie ein Mensch liest und nicht nur
    ein Skript. Kopf- und Trennzeile werden uebersprungen."""
    pfad = os.path.join(HIER, name)
    if not os.path.exists(pfad):
        return []
    zeilen = []
    for roh in lies(pfad).splitlines():
        roh = roh.strip()
        if not roh.startswith('|'):
            continue
        felder = [f.strip() for f in roh.strip('|').split('|')]
        if not felder or set(felder[0]) <= set('-: '):
            continue
        if felder[0].lower() in ('klasse', 'muster', 'wert', 'datei'):
            continue
        zeilen.append(felder)
    return [z for z in zeilen if len(z) >= spalten]


# =========================================================== 1. Klassen
def pruefung_klassen(bericht):
    vorher_pfad = os.path.join(HIER, 'vorher-klassen.txt')
    if not os.path.exists(vorher_pfad):
        bericht.fehler('1 Klassen', 'vorher-klassen.txt fehlt — erst `pruefen.py --vorher` laufen lassen.')
        return
    vorher = set()
    for z in lies(vorher_pfad).splitlines():
        z = z.strip()
        if z and not z.startswith('#'):
            vorher.add(z)

    jetzt_css = css_klassen(lies(CSS)) if os.path.exists(CSS) else set()
    streich_zeilen = liste_lesen('streichliste.md', 3)
    streich = {z[0].strip('`') for z in streich_zeilen}
    # ZWEI SORTEN AUF EINER LISTE (O11). Die meisten Eintraege sind Klassen,
    # die aus dem Markup VERSCHWINDEN — ihr Vorkommen dort waere ein Rest.
    # Einige wenige aber bleiben mit Absicht stehen: Skriptanker (`ac-form`,
    # `rollehaken`) und Behaelter ohne eigene Gestaltung (`form-spalte`). Sie
    # tragen im Grund den Vermerk `[bleibt]`; ohne diese Unterscheidung
    # meldete die Pruefung unten sie als Rest und waere nach dem dritten Mal
    # nichts wert.
    bleibt = {z[0].strip('`') for z in streich_zeilen
              if len(z) > 1 and z[1].lstrip().startswith('[bleibt]')}

    ohne = sorted(k for k in vorher if k not in jetzt_css and k not in streich)
    doppelt = sorted(k for k in vorher if k in jetzt_css and k in streich)

    bericht.zahl('1 Klassen', 'Sollmenge (Klassen des alten Stylesheets)', len(vorher))
    bericht.zahl('1 Klassen', 'davon mit Regel im neuen Stylesheet', len(vorher & jetzt_css))
    bericht.zahl('1 Klassen', 'davon auf der Streichliste', len(vorher & streich))
    bericht.befund('1 Klassen', 'ohne Gegenstueck', ohne)
    bericht.befund('1 Klassen', 'zugleich gestrichen UND mit Regel (Streichliste veraltet)', doppelt)

    # Gegenrichtung: im Markup benutzt, aber nirgends beschrieben.
    sicher, vermutet = markup_klassen(list(quelldateien()))
    verwaist = sorted(k for k in sicher if k not in jetzt_css and k not in streich)

    # ohne-regel.md — DAMIT DIE LISTE GELESEN WIRD (O12, Backlog Nr. 39).
    # Von 29 Treffern waren 23 keine: acht Bruchstuecke zusammengesetzter
    # Klassennamen (das Werkzeug liest Zeichenketten, nicht ausgefuehrten
    # Code) und fuenfzehn Skriptanker und Behaelter, die zu Recht keine Regel
    # haben. Eine Liste, in der ein echter Fund neben 28 falschen steht, wird
    # nach dem dritten Mal nicht mehr gelesen — und findet dann auch den
    # echten nicht (genau so ist F-P3-BA durchgerutscht).
    #   [bleibt]  begruendet ohne Regel  -> kein Befund, nur eine Zahl
    #   [offen]   Frage noch offen       -> Befund, aber unter eigener
    #                                       Ueberschrift
    or_zeilen = liste_lesen('ohne-regel.md', 2)
    or_bleibt = {z[0].strip('`') for z in or_zeilen
                 if z[1].lstrip().startswith('[bleibt]')}
    or_offen = {z[0].strip('`') for z in or_zeilen
                if z[1].lstrip().startswith('[offen]')}
    or_ohne_vermerk = sorted(z[0].strip('`') for z in or_zeilen
                             if not z[1].lstrip().startswith(('[bleibt]', '[offen]')))
    # Ein Eintrag, dessen Klasse inzwischen eine Regel hat oder aus dem
    # Markup verschwunden ist, ist Ballast und wird gemeldet — sonst
    # verwahrlost die Liste so still wie die Sache, gegen die sie schuetzt.
    or_ungenutzt = sorted((or_bleibt | or_offen) - set(verwaist))

    bericht.zahl('1 Klassen', 'Klassen im Markup (als Literal belegt)', len(sicher))
    bericht.befund('1 Klassen', 'im Markup ohne Regel, Grund nicht eingetragen',
                   ['%s  (%s)' % (k, ', '.join(sorted(sicher[k])[:3]))
                    for k in verwaist if k not in or_bleibt and k not in or_offen])
    bericht.befund('1 Klassen', 'im Markup ohne Regel, als [offen] vermerkt',
                   ['%s  (%s)' % (k, ', '.join(sorted(sicher[k])[:3]))
                    for k in verwaist if k in or_offen])
    bericht.zahl('1 Klassen', 'davon ausdruecklich [bleibt] (Anker, Bruchstuecke)',
                 len([k for k in verwaist if k in or_bleibt]))
    bericht.befund('1 Klassen', 'ohne-regel.md: Eintrag ohne Vermerk', or_ohne_vermerk)
    bericht.befund('1 Klassen', 'ohne-regel.md: Eintrag ungenutzt', or_ungenutzt)
    # DER BLINDE FLECK (O11): Eine Klasse, die GESTRICHEN ist und trotzdem noch
    # im Markup steht, fiel bisher durch jedes Netz — `verwaist` schliesst sie
    # ausdruecklich aus (`k not in streich`), und `ohne` sieht nur die
    # Sollmenge. Die Streichliste behauptet dann, etwas sei ersetzt, und das
    # Markup sagt das Gegenteil; die Zahl „im Markup ohne Regel" liest sich
    # dabei als vollstaendiger Beleg, ist aber keiner.
    noch_da = sorted(k for k in sicher
                     if k in streich and k not in jetzt_css and k not in bleibt)
    bericht.befund('1 Klassen', 'auf der Streichliste, aber noch im Markup',
                   ['%s  (%s)' % (k, ', '.join(sorted(sicher[k])[:3])) for k in noch_da])
    bericht.zahl('1 Klassen', 'davon ausdruecklich [bleibt] (Skriptanker, Behaelter)',
                 len([k for k in sicher if k in bleibt]))
    unbenutzt = sorted(k for k in jetzt_css
                       if k not in sicher and k not in vermutet)
    bericht.hinweis('1 Klassen', 'Regel im Stylesheet, im Markup nicht gefunden', unbenutzt)


# ============================================================= 2. Werte
ERLAUBTE_EINHEITEN = ('rem', 'em', '%', 'fr', 'ch', 'vh', 'vw', 'vmin', 'vmax', 's', 'ms', 'deg')


def pruefung_werte(bericht):
    if not os.path.exists(CSS):
        return
    roh = lies(CSS)
    css = ohne_media_praeludien(ohne_kommentare(roh))
    root = root_bloecke(css)

    hex_ = []
    for m in re.finditer(r'#[0-9a-fA-F]{3,8}\b', css):
        if not in_spannen(m.start(), root):
            hex_.append('%s:%d  %s' % (kurz(CSS), zeile_von(css, m.start()), m.group(0)))
    bericht.befund('2 Werte', 'Hexfarben ausserhalb :root', hex_)

    rgb = []
    for m in re.finditer(r'\brgba?\(\s*\d', css):
        if not in_spannen(m.start(), root):
            rgb.append('%s:%d  %s' % (kurz(CSS), zeile_von(css, m.start()), m.group(0)))
    bericht.befund('2 Werte', 'rgb()/rgba() mit festen Zahlen ausserhalb :root', rgb)

    groessen = []
    for m in re.finditer(r'font-size\s*:\s*([^;}]+)', css):
        wert = m.group(1).strip()
        if in_spannen(m.start(), root) or wert.startswith('var(') or wert in ('inherit', 'unset'):
            continue
        groessen.append('%s:%d  font-size: %s' % (kurz(CSS), zeile_von(css, m.start()), wert))
    bericht.befund('2 Werte', 'Schriftgroessen ausserhalb der Skala', groessen)

    # Pixelmasse: alles ausser 0. Was bleiben darf, steht mit Grund in
    # ausnahmen.md — nach Eigenschaftsnamen, nicht nach Zeilennummer, damit
    # die Liste eine Umsortierung des Stylesheets ueberlebt.
    frei = {z[0].strip('`') for z in liste_lesen('ausnahmen.md', 2)}
    px = []
    for m in re.finditer(r'(?<![\w-])([a-z-]+)\s*:\s*([^;{}]*?\d+(?:\.\d+)?px[^;{}]*)', css):
        eig, wert = m.group(1), m.group(2).strip()
        if in_spannen(m.start(), root) or eig in frei:
            continue
        px.append('%s:%d  %s: %s' % (kurz(CSS), zeile_von(css, m.start()), eig, wert))
    bericht.befund('2 Werte', 'Pixelmasse ausserhalb der Token', px)

    # 50px darf als Token stehen (--blatt-zeile, die Zeilenhoehe des
    # Aktionsblatts). Gesucht sind die RESTE der alten Kopfhoehe, und die
    # standen ausserhalb von :root.
    reste = ['%s:%d' % (kurz(CSS), zeile_von(css, m.start()))
             for m in re.finditer(r'\b50px\b', css) if not in_spannen(m.start(), root)]
    bericht.befund('2 Werte', '50px-Reste (die alte Kopfhoehe)', reste)

    stil = []
    for pfad in quelldateien():
        t = lies(pfad)
        for m in re.finditer(r'style\s*=\s*(["\'])(.*?)\1', t, re.S):
            if m.group(2).strip():
                stil.append('%s:%d  %s' % (kurz(pfad), zeile_von(t, m.start()),
                                           m.group(2)[:60].replace('\n', ' ')))
    bericht.befund('2 Werte', 'style="..."-Attribute in PHP/JS', stil,
                   frei=[z for z in liste_lesen('ausnahmen.md', 2) if z[0] == 'style'])


# =========================================================== 3. Symbole
# Zeichen, die im Markup als SYMBOL dienen. Kein Emoji darunter — die stehen
# in EMOJI, und die beiden Listen ueberschneiden sich nicht. Ein Zeichen in
# beiden Listen wuerde zweimal gemeldet, und die Summe stimmte nie.
UNICODE_SYMBOLE = ('▸▾▴▿▲▼◂◃►◄✓✔✗✘✕✖×⚠★☆◌●○◆■□←→↑↓⌄⌃⌃⋯…⚙⋮❯❮›‹»«'
                   '⇧⇩⊕⊖⊗✎✓')

# Emoji im engeren Sinn: die Bloecke ab U+1F000 und der Variantenwaehler
# U+FE0F, der aus einem Textzeichen eines macht. Bewusst NICHT der Bereich
# U+2600-27BF: Dort liegen ⚠ und ✓, und die gehoeren oben hin.
EMOJI = re.compile('[\U0001F000-\U0001FAFF\U0001F900-\U0001F9FF]|\uFE0F')


def pruefung_symbole(bericht):
    inline, unicode_, emoji, fehlend = [], [], [], []
    verwendet = set()

    for pfad in quelldateien():
        t = lies(pfad)
        istr_ui = pfad.endswith('ui.php')
        istr_js = pfad.endswith(os.sep + 'symbol.js')
        for m in re.finditer(r'<svg\b[^>]*>(.*?)</svg>', t, re.S):
            if '<path' in m.group(1) or '<circle' in m.group(1) or '<polyline' in m.group(1):
                inline.append('%s:%d' % (kurz(pfad), zeile_von(t, m.start())))
        for m in re.finditer(r'symbole/([a-z0-9-]+)\.svg', t):
            verwendet.add(m.group(1))
        for m in re.finditer(r"(?:ui_symbol|edSymbol)\(\s*['\"]([a-z0-9-]+)['\"]", t):
            verwendet.add(m.group(1))
        if istr_ui or istr_js:
            continue
        for m in re.finditer('[' + re.escape(UNICODE_SYMBOLE) + ']', t):
            unicode_.append('%s:%d  %s' % (kurz(pfad), zeile_von(t, m.start()), m.group(0)))
        for m in EMOJI.finditer(t):
            emoji.append('%s:%d  %s' % (kurz(pfad), zeile_von(t, m.start()), m.group(0)))

    vorhanden = set()
    if os.path.isdir(SYMBOLE):
        vorhanden = {f[:-4] for f in os.listdir(SYMBOLE) if f.endswith('.svg')}
    fehlend = sorted(verwendet - vorhanden)
    ohne_verweis = sorted(vorhanden - verwendet)

    bericht.zahl('3 Symbole', 'Symboldateien vorhanden', len(vorhanden))
    bericht.zahl('3 Symbole', 'davon im Code verwendet', len(vorhanden & verwendet))
    bericht.befund('3 Symbole', 'Inline-SVG mit Pfaden in PHP/JS', inline)
    bericht.befund('3 Symbole', 'Unicode-Zeichen als Symbol im Markup', unicode_)
    bericht.befund('3 Symbole', 'Emoji im Markup', emoji)
    bericht.befund('3 Symbole', 'Verweis auf fehlende Symboldatei', fehlend)
    bericht.hinweis('3 Symbole', 'Symboldatei ohne Verweis', ohne_verweis)

    # Jede Datei traegt den Anker <g id="i"> — ohne ihn zeigt der Verweis
    # ins Leere, und zwar lautlos: der Browser malt einfach nichts.
    ohne_anker = []
    for name in sorted(vorhanden):
        if 'id="i"' not in lies(os.path.join(SYMBOLE, name + '.svg')):
            ohne_anker.append(name)
    bericht.befund('3 Symbole', 'Symboldatei ohne Anker id="i"', ohne_anker)


# =========================================================== 4. Knopfregel
def pruefung_knopf(bericht):
    if not os.path.exists(CSS):
        return
    css = ohne_kommentare(lies(CSS))
    verstoss = []
    for m in re.finditer(r'([^{}]*\.knopf[^{}]*)\{([^{}]*)\}', css):
        sel, koerper = m.group(1).strip(), m.group(2)
        for eig in ('height', 'min-height'):
            for h in re.finditer(eig + r'\s*:\s*([^;}]+)', koerper):
                wert = h.group(1).strip()
                if 'var(--knopf' not in wert and wert not in ('auto', 'inherit', '100%'):
                    verstoss.append('%s:%d  %s { %s: %s }' % (
                        kurz(CSS), zeile_von(css, m.start()), sel.replace('\n', ' '), eig, wert))
    bericht.befund('4 Knopf', 'Knopfhoehe nicht aus --knopf', verstoss)


# ============================================================== Bericht
class Bericht:
    def __init__(self, ausfuehrlich):
        self.ausfuehrlich = ausfuehrlich
        self.zeilen = []
        self.befunde = 0

    def zahl(self, gruppe, was, n):
        self.zeilen.append(('zahl', gruppe, was, n, []))

    def befund(self, gruppe, was, liste, frei=None):
        liste = list(liste)
        self.zeilen.append(('befund', gruppe, was, len(liste), liste))
        self.befunde += len(liste)

    def hinweis(self, gruppe, was, liste):
        liste = list(liste)
        self.zeilen.append(('hinweis', gruppe, was, len(liste), liste))

    def fehler(self, gruppe, text):
        self.zeilen.append(('befund', gruppe, text, 1, []))
        self.befunde += 1

    def drucken(self):
        gruppe = None
        for art, g, was, n, liste in self.zeilen:
            if g != gruppe:
                print('\n' + g)
                print('-' * len(g))
                gruppe = g
            marke = {'zahl': ' ', 'hinweis': 'i', 'befund': '!' if n else ' '}[art]
            if art == 'befund' and n == 0:
                marke = '.'
            print('  %s %-58s %5d' % (marke, was, n))
            if liste and (self.ausfuehrlich or art == 'befund'):
                grenze = len(liste) if self.ausfuehrlich else 25
                for e in liste[:grenze]:
                    print('        %s' % e)
                if len(liste) > grenze:
                    print('        … und %d weitere (--ausfuehrlich zeigt alle)' % (len(liste) - grenze))
        print('\n' + '=' * 72)
        if self.befunde:
            print('BEFUNDE: %d' % self.befunde)
        else:
            print('Keine Befunde.')
        return 1 if self.befunde else 0


# ================================================================ Aufruf
def vorher_sichern():
    """Klassenliste des ALTEN Stylesheets sichern (einmalig, vor dem Umbau)."""
    namen = sorted(css_klassen(lies(CSS)))
    pfad = os.path.join(HIER, 'vorher-klassen.txt')
    with io.open(pfad, 'w', encoding='utf-8') as f:
        f.write('# Klassen des Stylesheets VOR dem Umbau in P3 (O1, Schritt 1).\n')
        f.write('# Sollmenge der Pruefung 1: jede dieser Klassen hat am Ende\n')
        f.write('# eine Regel im neuen Stylesheet oder einen Eintrag in\n')
        f.write('# streichliste.md. Erhoben aus den Selektoren, nicht aus dem\n')
        f.write('# Markup — das ist die rauschfreie Menge.\n')
        for n in namen:
            f.write(n + '\n')
    print('Vorher-Stand gesichert: %d Klassen -> %s' % (len(namen), kurz(pfad)))
    return 0


def main():
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('--vorher', action='store_true',
                   help='Klassenliste des jetzigen Stylesheets als Sollmenge sichern')
    p.add_argument('--ausfuehrlich', action='store_true', help='alle Fundstellen zeigen')
    a = p.parse_args()

    if a.vorher:
        return vorher_sichern()

    b = Bericht(a.ausfuehrlich)
    pruefung_klassen(b)
    pruefung_werte(b)
    pruefung_symbole(b)
    pruefung_knopf(b)
    return b.drucken()


if __name__ == '__main__':
    sys.exit(main())
