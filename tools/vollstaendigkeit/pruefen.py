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
  5  Zusagen -- Regeln, die bisher nur im Kopf standen: kein natives
     confirm()/alert()/prompt() ausser den begruendeten Rueckfaellen
     (Backlog Nr. 47), und jede Seite mit eigener Huelle hat ihr Geruest
     (Nr. 58). Ausnahmen mit Grund in zusagen.md.

Dazu die Ausgabe: je Pruefung Zahl und Liste, Rueckgabewert != 0 bei Befund.

Aufruf und Bedeutung stehen in LIESMICH.md daneben.
Kein PHP noetig; nur Python 3.
"""
import argparse
import io
import json
import os
import re
import sys

# Tag-Rumpf einer Quelldatei: ein PHP-Stueck am Stueck ODER ein Zeichen, das
# kein `>` ist. Ein `?>` beendet das Tag nicht (CLAUDE.md 6).
TAG_REST = r'(?:<\?(?:php\b|=).*?\?>|[^>])*'

HIER   = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
SERVER = os.path.join(WURZEL, 'server')
CSS    = os.path.join(SERVER, 'assets', 'style.css')
SYMBOLE = os.path.join(SERVER, 'assets', 'images', 'symbole')

# Verzeichnisse, die nicht uns gehoeren: fremde Bibliotheken und Schriften.
FREMD = ('vendor', 'fonts', 'demo')

# EINE DATEI, DIE NICHT GELESEN WIRD (S10/AP5).
#
# `config.php` liegt nur auf installierten Instanzen, steht in `.gitignore`
# und traegt seit S10 ZWEI Geheimnisse: den Serverschluessel und den
# Server-Anteil. Sie hier auszunehmen hat zwei Gruende, und beide zaehlen:
#
#   1. DIE ZAHL SOLL REPRODUZIERBAR SEIN. Auf einem blanken Auscheck gibt es
#      die Datei nicht, auf einer Installation schon — dasselbe Werkzeug
#      meldete also zwei verschiedene Zahlen, je nachdem wo es lief. Eine
#      Zahl, die von der Umgebung abhaengt, ist als Vergleich unbrauchbar.
#   2. EIN GEHEIMNIS GEHOERT IN KEINEN BERICHT. Heute traegt kein Muster auf
#      einen 64-Hex-Wert zu; morgen kann eines dazukommen, und dann stuende
#      ein Schluesselbruchstueck in einer Datei unter `tools/ausgabe/`.
#      Der billigste Zeitpunkt, das zu verhindern, ist der, bevor es
#      passiert.
#
# Gemessen beim Ausnehmen: Die Datei steuerte GENAU EINEN Treffer bei
# (ein `\u2192` in ihrem Kopfkommentar).
AUSGENOMMEN = ('config.php',)



# ---------------------------------------------------------------- Einlesen
def quelldateien(wurzel=SERVER, endungen=('.php', '.js')):
    """Alle eigenen Quelldateien unter server/ — ohne vendor, fonts, demo."""
    for dp, dns, fns in os.walk(wurzel):
        dns[:] = [d for d in dns if d not in FREMD]
        for fn in sorted(fns):
            if fn.endswith(endungen) and fn not in AUSGENOMMEN:
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
def ohne_php_js_kommentare(text, ist_php):
    """Kommentare durch Leerzeichen ersetzen, Zeilenumbrueche erhalten.

    WARUM NICHT MIT EINEM AUSDRUCK. `//` steht in jeder URL, `#` in jeder
    Farbe, `/*` in mancher Zeichenkette. Ein regulaerer Ausdruck, der das
    trennen soll, wird entweder zu grob (und streicht Code weg) oder zu fein
    (und laesst Kommentare stehen) -- beides macht die Pruefung wertlos, und
    zwar lautlos. Dieser Abtaster geht stattdessen Zeichen fuer Zeichen und
    merkt sich, ob er gerade in einer Zeichenkette steht.

    DIE UMBRUECHE MUESSEN BLEIBEN, sonst zeigt jede Fundstelle daneben --
    dieselbe Regel wie bei ohne_kommentare() fuer CSS.

    `#` GILT NUR IN PHP. In JavaScript begaenne es ein privates Feld, und im
    eigenen Code gibt es davon keines (nachgesehen am 13.09.2026) -- aber die
    Unterscheidung kostet nichts und nimmt der naechsten Fassung eine Falle.

    WAS ER NICHT KANN: Heredoc/Nowdoc (`<<<`) und Regex-Literale mit `//`
    darin. Beides kommt im eigenen Code nicht vor (nachgesehen; die Treffer
    liegen alle unter server/vendor/, und das ist ausgenommen). Wer das
    aendert, erweitert diesen Abtaster -- oder die Pruefung liest Kommentar
    fuer Code.
    """
    aus = []
    i, n = 0, len(text)
    while i < n:
        c = text[i]
        if c in ('"', "'", '`'):
            ende = c
            aus.append(c); i += 1
            while i < n:
                if text[i] == '\\' and i + 1 < n:
                    aus.append(text[i]); aus.append(text[i+1]); i += 2; continue
                aus.append(text[i])
                if text[i] == ende:
                    i += 1; break
                i += 1
            continue
        if c == '/' and i + 1 < n and text[i+1] == '*':
            j = text.find('*/', i + 2)
            j = n if j < 0 else j + 2
            aus.append(''.join(z if z == '\n' else ' ' for z in text[i:j]))
            i = j; continue
        if (c == '/' and i + 1 < n and text[i+1] == '/') or (c == '#' and ist_php):
            j = text.find('\n', i)
            j = n if j < 0 else j
            aus.append(' ' * (j - i))
            i = j; continue
        aus.append(c); i += 1
    return ''.join(aus)


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
        if felder[0].lower() in ('klasse', 'muster', 'wert', 'datei', 'prüfung'):
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

# JS-Escape-Folgen fuer dieselben Zeichen. WARUM DAS NOETIG IST:
# `x.textContent = '\u00D7'` ist dasselbe Malzeichen wie `'\u00D7'`, nur anders
# geschrieben \u2014 und bis Web 19.4.2 hat diese Pruefung es nicht gesehen.
# Gefunden am 13.09.2026 beim Zeichnen von M-MR-02 (Fehlerfund 1): Der
# Koordinaten-Chip stand als Treffer da, der Rettungsmittel-Chip daneben
# nicht, obwohl beide dasselbe taten. Eine Pruefung, die sich mit einer
# anderen Schreibweise umgehen laesst, prueft nichts.
ESCAPE = re.compile(r'\\u([0-9a-fA-F]{4})')


def escapes_aufloesen(text):
    """`\\uXXXX` durch das Zeichen ersetzen, LAENGENTREU.

    Aus den sechs Stellen der Folge werden ein Zeichen und fuenf Leerzeichen.
    So bleibt jede spaetere Fundstelle auf ihrer Zeile und in ihrer Spalte \u2014
    ohne das zeigte jeder Treffer nach der ersten Escape-Folge daneben,
    dieselbe Falle wie bei ohne_kommentare() fuer CSS.
    """
    return ESCAPE.sub(lambda m: chr(int(m.group(1), 16)) + '     ', text)


def pruefung_symbole(bericht):
    inline, unicode_, emoji, fehlend = [], [], [], []
    verwendet = set()

    for pfad in quelldateien():
        t = lies(pfad)
        # ESCAPE-FOLGEN AUFLOESEN, KOMMENTARE NICHT AUSBLENDEN — und das
        # zweite ist eine Entscheidung, keine Auslassung (Web 19.4.2).
        #
        # Naheliegend waere gewesen, hier `ohne_php_js_kommentare()` aus
        # Gruppe 5 dazwischenzuschalten: Von 255 Treffern stehen rund 250 in
        # Kommentaren oder in Fliesstext, die Zahl faellt damit auf 108.
        # Nachgemessen am 14.09.2026 taugt der Abtaster dafuer aber nicht: In
        # einer PHP-Datei mit HTML schickt ihn ein ungepaartes `"` im
        # Fliesstext in den Zeichenketten-Modus, und er verschluckt alles bis
        # zum naechsten — in `einsatz_form.php` ab Zeile 1547 ganze 800
        # Zeilen am Stueck. Eine kleinere Zahl, die durch Wegsehen entsteht,
        # ist schlechter als eine grosse, die alles zeigt. Backlog Nr. 184.
        markup = escapes_aufloesen(t)
        istr_ui = pfad.endswith('ui.php')
        istr_js = pfad.endswith(os.sep + 'symbol.js')
        # TAG_REST statt `[^>]*` (CLAUDE.md 6, Backlog Nr. 218): `t` ist die
        # rohe PHP-Quelle. Heute traegt keines der 3 <svg>-Tags PHP im Rumpf
        # (gemessen 17.09.2026) -- das erste, das es tut, waere mit der kurzen
        # Form am `?>` abgeschnitten und der Inline-Svg damit ungezaehlt.
        for m in re.finditer(r'<svg\b' + TAG_REST + r'>(.*?)</svg>', t, re.S):
            if '<path' in m.group(1) or '<circle' in m.group(1) or '<polyline' in m.group(1):
                inline.append('%s:%d' % (kurz(pfad), zeile_von(t, m.start())))
        for m in re.finditer(r'symbole/([a-z0-9-]+)\.svg', t):
            verwendet.add(m.group(1))
        for m in re.finditer(r"(?:ui_symbol|edSymbol)\(\s*['\"]([a-z0-9-]+)['\"]", t):
            verwendet.add(m.group(1))
        if istr_ui or istr_js:
            continue
        for m in re.finditer('[' + re.escape(UNICODE_SYMBOLE) + ']', markup):
            unicode_.append((kurz(pfad), zeile_von(markup, m.start()), m.group(0)))
        for m in EMOJI.finditer(markup):
            emoji.append('%s:%d  %s' % (kurz(pfad), zeile_von(markup, m.start()), m.group(0)))

    vorhanden = set()
    if os.path.isdir(SYMBOLE):
        vorhanden = {f[:-4] for f in os.listdir(SYMBOLE) if f.endswith('.svg')}
    fehlend = sorted(verwendet - vorhanden)
    ohne_verweis = sorted(vorhanden - verwendet)

    bericht.zahl('3 Symbole', 'Symboldateien vorhanden', len(vorhanden))
    bericht.zahl('3 Symbole', 'davon im Code verwendet', len(vorhanden & verwendet))
    bericht.befund('3 Symbole', 'Inline-SVG mit Pfaden in PHP/JS', inline)
    # KEINE AUSNAHMELISTE FUER DIESE PRUEFUNG, und auch das ist entschieden.
    # Das Konzept der Mockup-Runde sah eine vor (`ausnahmen.md`, E-MR-02);
    # diese Datei liest aber ausschliesslich die Token-Pruefung, ein Eintrag
    # dort stuende wirkungslos da (nachgesehen 13.09.2026, E-MR-24). Die
    # Zusagenliste aus Gruppe 5 waere der richtige Ort — nur bliebe dann eine
    # Liste mit rund hundert Eintraegen fuer „…" und „→", und die liest
    # niemand. Solange die Zahl aus Typografie besteht, ist sie eine ZAHL und
    # kein Befund je Zeile; wer sie klein bekommen will, braucht zuerst
    # Nr. 184 (der Abtaster) und dann eine engere Zeichenliste.
    bericht.befund('3 Symbole', 'Unicode-Zeichen als Symbol im Markup',
                   ['%s:%d  %s' % z for z in unicode_])
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
# =========================================================== 5. Zusagen
NATIVE_DIALOGE = re.compile(r'(?<![\w$.])(?:window\s*\.\s*)?(confirm|alert|prompt)\s*\(')

# ---- Fremde Quellen zur Laufzeit (Backlog Nr. 179) -------------------------
#
# DIE ZUSAGE. CLAUDE.md 4: kein CDN, keine Google Fonts, kein externes Skript;
# Schriften und Bibliotheken liegen unter server/assets/ mit Herkunft und
# SHA-256 im Dateikopf. Bis Web 19.3.1 hat das KEIN Mittel nachgezaehlt, und
# eine CSP schickt die Anwendung auch nicht -- aufgefallen ist die Luecke, als
# ein Kommentar im Bilderlauf sie an genau diese Datei weiterschob (Nr. 176).
#
# WARUM DAS MUSTER SO GROB IST -- und das ist Absicht. Ein Ausdruck, der nur
# die Ladekonstrukte kennt (src=, <link href=, fetch(, url(), @import), findet
# in DIESEM Bestand NICHTS: Die Kartenkacheln gehen ueber `L.tileLayer(...)`,
# die Anschrift des Adressdienstes steht als PHP-Konstante. Beides gemessen am
# 13.09.2026 -- ein solcher Ausdruck meldete 0 Treffer, waehrend fuenf echte
# Laufzeitquellen im Code standen. Deshalb wird JEDE absolute Adresse in
# eigenem Quelltext gemeldet, und die Ausnahmeliste traegt die Begruendung.
# Das ist mehr Arbeit beim Eintragen und dafuer eine Liste, die vollstaendig
# ist: Sie nennt jede fremde Adresse im ausgelieferten Code, mit ihrer Art.
#
# NICHT JEDER TREFFER IST EIN LADEN. Die Liste unterscheidet vier Arten, und
# die Spalte "Grund" sagt sie: gewollte Laufzeitquelle (Kacheln, Adressdienst),
# Navigationsziel (ein <a href>, das ein Mensch anklickt -- das laedt nichts),
# XML-Namensraum (eine Kennung, keine Adresse) und Beispieltext.
FREMDE_QUELLE = re.compile(
    r'(?<![\w])(?:https?:)?//[a-z0-9{][a-z0-9.\-{}]*\.[a-z]{2,}', re.I)


UMLAUTE = {'ä': 'ae', 'ö': 'oe', 'ü': 'ue', 'Ä': 'Ae', 'Ö': 'Oe', 'Ü': 'Ue', 'ß': 'ss'}


def umlautfrei(t):
    """Umlaute aufloesen und kleinschreiben -- fuer den Vergleich, nicht fuer die Anzeige.

    WARUM DAS NOETIG IST. Die Beschriftungen in dieser Datei sind ASCII
    ("Gegenstueck", "Knopfhoehe", "Seite ohne Geruest"); die Hilfslisten sind
    Markdown und werden von Menschen gelesen, also stehen dort Umlaute
    ("Seite ohne Gerüst"). Beim ersten Lauf hat deshalb keine der sieben
    Ausnahmen gegriffen, und die Pruefung meldete sieben Befunde, die alle
    erklaert waren -- ohne dass irgendwo "Vergleich fehlgeschlagen" stand.
    Genau die Sorte Fehler, gegen die diese Gruppe gebaut ist."""
    return ''.join(UMLAUTE.get(c, c) for c in t).lower()


def zusagen_treffer(muster, endungen=('.php', '.js')):
    """Alle Fundstellen eines Musters in server/, ohne Kommentare.

    Liefert (kurzer Pfad, Zeile, Fundtext) -- das Format, das die
    Ausnahmeliste vergleicht und der Bericht zeigt.

    GRENZE FUER .css: Der Kommentar-Abtaster kennt zwei Sprachen, PHP und JS.
    Auf ein Stylesheet wird die JS-Lesart angewendet -- die trifft `/* */`
    richtig, hielte aber ein unquotiertes `url(//host)` fuer einen
    Kommentaranfang und schnitte den Rest der Zeile weg. Heute gibt es keines
    (gemessen: 0 absolute Adressen in server/assets/*.css, die Schriften
    liegen lokal). Wer eines einfuehrt, faellt hier durch -- deshalb steht die
    Grenze hier und nicht in einer Fussnote."""
    for pfad in quelldateien(endungen=endungen):
        text = lies(pfad)
        ohne = ohne_php_js_kommentare(text, pfad.endswith('.php'))
        for m in muster.finditer(ohne):
            yield kurz(pfad), zeile_von(ohne, m.start()), m.group(0).strip()


def zusagen_werten(bericht, name, treffer, listenname='zusagen.md'):
    """Treffer gegen die Ausnahmeliste halten -- in beide Richtungen.

    EINE AUSNAHME, DIE NICHTS MEHR ERKLAERT, IST EIN BEFUND. Sonst verwahrlost
    die Liste so still wie die Sache, gegen die sie schuetzt -- dieselbe Regel
    wie bei ohne-regel.md (Backlog Nr. 39).

    DIE LISTE HAT VIER SPALTEN: Pruefung | Datei | Muster | Grund. Die erste
    sagt, zu welcher Pruefung die Zeile gehoert -- so tragen alle Zusagen EINE
    Liste, und ein Mensch sieht beim Lesen, wovon eine Zeile spricht. Die
    zweite ist die DATEI und nicht die Zeile: Eine Zeilennummer altert mit dem
    naechsten Paket, das die Datei anfasst (genau daran ist der Eintrag zu
    `phasen-name` in ohne-regel.md gealtert, AP5).
    """
    regeln = [(z[1].strip('`'), z[2].strip('`'))
              for z in liste_lesen(listenname, 4)
              if umlautfrei(z[0].strip('*` ')) == umlautfrei(name)]

    offen, benutzt = [], set()
    for pfad, zeile, text in treffer:
        for k, (datei, mus) in enumerate(regeln):
            if pfad.endswith(datei) and mus in text:
                benutzt.add(k); break
        else:
            offen.append('%s:%d  %s' % (pfad, zeile, text))
    ungenutzt = ['%s  (%s)' % (d, m) for k, (d, m) in enumerate(regeln) if k not in benutzt]
    bericht.befund('5 Zusagen', name, offen)
    bericht.zahl('5 Zusagen', name + ': Ausnahmen mit Grund', len(regeln))
    bericht.befund('5 Zusagen', name + ': Ausnahme ungenutzt', ungenutzt)


def geruest_treffer():
    """Dateien mit Seitenhuelle, denen das Geruest fehlt -- und die Gegenrichtung.

    DAS KRITERIUM IST `ui_seite_start(`, NICHT `require_admin()` (E-BR3-06).
    Die naeheliegende Regel waere "bindet die Wache ein und ruft kein Geruest";
    sie liefert heute 15 Treffer, und alle 15 sind richtig so -- Bibliotheken,
    Endpunkte ohne Seite, Seiten vor der Anmeldung, der Notausgang. Ein Mittel,
    das mit 15 Rot anfaengt, wird nie wieder gelesen.

    `ui_seite_start()` dagegen ist der Anfang JEDER Seitenhuelle: Wer ihn ruft,
    gibt eine Seite aus, und eine Seite der angemeldeten Anwendung hat ein
    Geruest. Beide Haelften werden verlangt -- `ui_geruest_ende()` ebenso, denn
    ein Geruest, das nicht geschlossen wird, ist keines.
    """
    seite, anfang, ende = {}, set(), set()
    for pfad in quelldateien():
        text = ohne_php_js_kommentare(lies(pfad), pfad.endswith('.php'))
        k = kurz(pfad)
        if 'ui_seite_start(' in text:
            seite[k] = zeile_von(text, text.index('ui_seite_start('))
        if 'ui_geruest_start(' in text: anfang.add(k)
        if 'ui_geruest_ende(' in text:  ende.add(k)
    ohne = [(k, z, 'ui_seite_start') for k, z in sorted(seite.items())
            if k not in anfang or k not in ende]
    gegen = sorted(anfang - set(seite))
    return ohne, gegen


def pruefung_zusagen(bericht):
    """Zusagen, die bisher nur im Kopf standen (Backlog Nr. 47, 58).

    WOFUER. Die Anwendung gibt Versprechen, die kein Mittel nachzaehlt: "kein
    natives confirm()", "jede Seite hat ihr Geruest". Ein Versprechen ohne
    Pruefmittel haelt genau so lange, wie sich jemand daran erinnert."""
    zusagen_werten(bericht, 'native Dialoge', zusagen_treffer(NATIVE_DIALOGE))
    zusagen_werten(bericht, 'fremde Quelle',
                   zusagen_treffer(FREMDE_QUELLE, ('.php', '.js', '.css')))

    ohne, gegen = geruest_treffer()
    zusagen_werten(bericht, 'Seite ohne Geruest', ohne)
    # GEGENRICHTUNG ALS HINWEIS, NICHT ALS BEFUND: Geruest ohne Seitenhuelle
    # ist nicht zwangslaeufig falsch -- es KANN aber eine Seite ohne `<head>`
    # sein, und genau eine war es (apk.php, behoben mit Web 19.3.1: die
    # 404-Seite ging ohne Doctype und ohne Stylesheet hinaus). Deshalb steht
    # die Zahl da, auch wenn sie null ist.
    bericht.hinweis('5 Zusagen', 'Geruest ohne Seitenhuelle', gegen)


class Bericht:
    def __init__(self, ausfuehrlich, grenze=None):
        self.ausfuehrlich = ausfuehrlich
        self.grenze = grenze
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

        # ---- Rueckgabewert: gegen eine SCHWELLE, nicht gegen Null ----------
        #
        # DIESES WERKZEUG MISST EINEN ALTBESTAND, KEINE GUETE. Die Zahl ist
        # ein VERGLEICH gegen den Stand vor P3 — 366 davon sind Unicode-
        # Zeichen im Markup, `style=`-Attribute in JavaScript und Emoji, die
        # seit Jahren dastehen und nicht in einem Zug verschwinden. „0
        # Befunde" ist deshalb kein erreichbarer Zustand, sondern ein Ziel.
        #
        # BIS WEB 20.8.0 GAB DIE FUNKTION TROTZDEM 1 ZURUECK, SOBALD DIE ZAHL
        # UEBER NULL LAG. Fuer einen Lauf von Hand war das gleichgueltig —
        # man liest die Zahl. Als das Werkzeug mit P5a/AP1 in Stufe 1 der
        # Auslieferungskette kam, war es ein Dauerrot: Der Pruefschritt
        # verlangte `exit 0` und bekam 366, bei JEDEM Push. Ein Tor, das
        # immer rot ist, sagt nichts mehr — und wird abgeschaltet.
        #
        # `--hoechstens N` macht daraus, was gemeint war: Waechst der
        # Altbestand, ist das ein Befund; schrumpft er, ist es einer im
        # guten Sinn und wird GEMELDET, damit die Schwelle nachgezogen wird
        # und nicht stillschweigend Luft bekommt.
        if self.grenze is not None:
            if self.befunde > self.grenze:
                print('UEBER DER SCHWELLE: %d statt hoechstens %d — um %d gewachsen.'
                      % (self.befunde, self.grenze, self.befunde - self.grenze))
                return 1
            if self.befunde < self.grenze:
                print('UNTER DER SCHWELLE: %d statt %d — %d weniger. Bitte die '
                      'Schwelle in .github/workflows/pruefung.yml nachziehen, '
                      'sonst bekommt der Altbestand stillschweigend wieder Luft.'
                      % (self.befunde, self.grenze, self.grenze - self.befunde))
                return 1
            print('AUF DER SCHWELLE: %d — unveraendert.' % self.befunde)
            return 0
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
    p.add_argument('--hoechstens', type=int, default=None,
                   help='Schwelle: hoechstens so viele Befunde. Abweichung nach '
                        'OBEN und nach UNTEN ergibt Rueckgabewert 1 — der '
                        'Altbestand soll weder wachsen noch stillschweigend '
                        'Luft bekommen.')
    a = p.parse_args()

    if a.vorher:
        return vorher_sichern()

    b = Bericht(a.ausfuehrlich, a.hoechstens)
    pruefung_klassen(b)
    pruefung_werte(b)
    pruefung_symbole(b)
    pruefung_knopf(b)
    pruefung_zusagen(b)
    return b.drucken()


if __name__ == '__main__':
    sys.exit(main())
