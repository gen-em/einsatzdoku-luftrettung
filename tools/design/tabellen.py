#!/usr/bin/env python3
"""Erzeugt die Tabellen von docs/Design.md aus den Quellen.

WARUM ES DIESES WERKZEUG GIBT. Das Konzept verlangt fuer O12 ausdruecklich
eine Token-Tabelle, die aus dem Stylesheet *erzeugt* und nicht abgeschrieben
ist (Konzept-P3-Oberflaeche.md, O12 Punkt 1). Der Grund ist derselbe wie bei
jeder abgeschriebenen Zahl: Sie stimmt am Tag des Abschreibens und danach nie
wieder. Eine Gestaltungsrichtlinie, deren Farbwerte von denen der Anwendung
abweichen, ist schlimmer als keine — man glaubt ihr.

Das Werkzeug liest und schreibt nichts am Stylesheet. Es gibt Markdown auf die
Standardausgabe; wer Design.md fortschreibt, ersetzt damit die entsprechenden
Abschnitte.

    python3 tools/design/tabellen.py token       # Abschnitt 4
    python3 tools/design/tabellen.py schwellen   # Abschnitt 7
    python3 tools/design/tabellen.py symbole     # Abschnitt 8
    python3 tools/design/tabellen.py bausteine   # Abschnitt 9
    python3 tools/design/tabellen.py alle

GRENZE: Es erzeugt Tabellen, keine Prosa. Was ein Token BEDEUTET, steht als
Kommentar im Stylesheet und wird uebernommen, wenn einer da ist; wo keiner
steht, bleibt die Spalte leer — und das ist dann eine Luecke im Stylesheet,
nicht in dieser Datei.
"""
from __future__ import annotations

import pathlib
import re
import sys

WURZEL = pathlib.Path(__file__).resolve().parents[2]
CSS = WURZEL / 'server' / 'assets' / 'style.css'
UI = WURZEL / 'server' / 'ui.php'
SYMBOLE = WURZEL / 'server' / 'assets' / 'images' / 'symbole'


def css_text() -> str:
    return CSS.read_text(encoding='utf-8')


# ---------------------------------------------------------------- Token

def root_block(text: str) -> tuple[str, int]:
    """Der :root-Block samt seiner Anfangszeile."""
    m = re.search(r'^:root\{', text, re.M)
    if not m:
        raise SystemExit('FEHLER: kein :root-Block in style.css gefunden.')
    tiefe, i = 0, m.end() - 1
    while i < len(text):
        if text[i] == '{':
            tiefe += 1
        elif text[i] == '}':
            tiefe -= 1
            if tiefe == 0:
                break
        i += 1
    zeile = text[:m.start()].count('\n') + 1
    return text[m.end():i], zeile


def token_lesen() -> list[dict]:
    """Jede Custom Property aus :root — mit ihrer Gruppe und ihrem Randglossar.

    DIE GRUPPEN STEHEN SCHON IM STYLESHEET. Es gliedert seinen :root-Block mit
    Kommentarzeilen der Form `---- Flaechen ----`; die werden hier als Gruppe
    uebernommen, statt eine zweite Gliederung danebenzustellen, die
    auseinanderlaufen kann.

    Als BEDEUTUNG gilt nur ein Kommentar am Zeilenende (`--auf-dunkel:#FFF;
    /* Schrift auf Dunkelblau */`). Die langen Bloecke davor sind die
    Begruendung — sie gehoeren in die Prosa des Kapitels, nicht in eine
    Tabellenzelle.
    """
    text = css_text()
    block, startzeile = root_block(text)
    ohne_root = text.replace(block, '', 1)

    eintraege: list[dict] = []
    aktuelle_gruppe = 'Ohne Gruppe'
    zeile = startzeile
    for roh in block.split('\n'):
        zeile += 1
        z = roh.strip()
        mg = re.match(r'/\*\s*-{2,}\s*(.+?)\s*-{2,}', z)
        if mg:
            aktuelle_gruppe = mg.group(1)
            continue
        m = re.match(r'(--[\w-]+)\s*:\s*([^;]+);(?:\s*/\*\s*(.*?)\s*\*/)?', z)
        if not m:
            continue
        name, wert, ende = m.group(1), m.group(2).strip(), (m.group(3) or '').strip()
        eintraege.append({
            'name': name,
            'wert': wert,
            'zeile': zeile,
            'gruppe': aktuelle_gruppe,
            'bedeutung': re.sub(r'\s+', ' ', ende),
            'nutzung': len(re.findall(r'var\(' + re.escape(name) + r'[,)]', ohne_root)),
        })
    return eintraege


def tabelle_token() -> str:
    eintraege = token_lesen()
    reihenfolge: list[str] = []
    for e in eintraege:
        if e['gruppe'] not in reihenfolge:
            reihenfolge.append(e['gruppe'])

    zeilen = ['<!-- ERZEUGT von tools/design/tabellen.py — nicht von Hand ändern. -->',
              '',
              f'{len(eintraege)} Token in {len(reihenfolge)} Gruppen, alle aus `:root` in '
              '`server/assets/style.css`. Die Spalte **benutzt** zählt die '
              '`var()`-Verweise im übrigen Stylesheet.',
              '']
    for g in reihenfolge:
        teil = [e for e in eintraege if e['gruppe'] == g]
        zeilen += [f'**{g}**', '', '| Token | Wert | benutzt | |', '|---|---|--:|---|']
        for e in teil:
            zeilen.append(f"| `{e['name']}` | `{e['wert']}` | {e['nutzung']} | {e['bedeutung']} |")
        zeilen.append('')
    ungenutzt = [e['name'] for e in eintraege if e['nutzung'] == 0]
    zeilen.append('**Ungenutzt:** '
                  + (', '.join('`' + n + '`' for n in ungenutzt) if ungenutzt else 'keines')
                  + '.')
    return '\n'.join(zeilen)


# ------------------------------------------------------------ Schwellen

def tabelle_schwellen() -> str:
    text = css_text()
    treffer: dict[str, int] = {}
    for m in re.finditer(r'@media\s*\(([^)]*width:\s*\d+px)\)', text):
        schl = re.sub(r'\s+', '', m.group(1))
        treffer[schl] = treffer.get(schl, 0) + 1

    def sortwert(s: str) -> tuple[int, int]:
        px = int(re.search(r'(\d+)px', s).group(1))
        return (0 if 'min-width' in s else 1, -px)

    zeilen = ['<!-- ERZEUGT von tools/design/tabellen.py — nicht von Hand ändern. -->',
              '', '| Abfrage | Regelblöcke |', '|---|--:|']
    for s in sorted(treffer, key=sortwert):
        zeilen.append(f'| `@media ({s})` | {treffer[s]} |')
    breiten = {int(re.search(r'(\d+)px', s).group(1)) for s in treffer}
    zeilen += ['', f'Zusammen {sum(treffer.values())} Medienblöcke über '
                   f'{len(breiten)} verschiedene Breiten: '
                   + ', '.join(f'{b} px' for b in sorted(breiten)) + '.']
    return '\n'.join(zeilen)


# -------------------------------------------------------------- Symbole

def tabelle_symbole() -> str:
    """Der Symbolvorrat mit Herkunft und Fundstellen.

    GEZAEHLT WIRD JEDE NENNUNG DES NAMENS als Zeichenkette in PHP und JS, nicht
    nur `ui_symbol('haus')`. Der Grund steht in geo.js: Die Kartenschilder
    reichen den Namen durch eine eigene Funktion (`schild('haus', …)`), und ein
    Ausdruck, der nur die beiden Erzeuger kennt, meldete `haus` als ungenutzt —
    was es sichtbar nicht ist. Die Zahl ist damit eine OBERGRENZE: Sie kann ein
    gleichlautendes Wort mitzaehlen (`'gruppe' => 'wer'` in suche.php). Wo sie
    0 ist, ist das Symbol aber wirklich nirgends genannt, und das ist die
    Aussage, auf die es ankommt.
    """
    quelle = '\n'.join(
        p.read_text(encoding='utf-8', errors='replace')
        for p in list((WURZEL / 'server').rglob('*.php')) + list((WURZEL / 'server').rglob('*.js'))
        if 'vendor' not in str(p))

    zeilen = ['<!-- ERZEUGT von tools/design/tabellen.py — nicht von Hand ändern. -->',
              '', '| Datei | Herkunft (Tabler-Name) | Nennungen im Code |', '|---|---|--:|']
    dateien = sorted(SYMBOLE.glob('*.svg'))
    ohne = []
    for datei in dateien:
        kopf = datei.read_text(encoding='utf-8')[:800]
        mq = re.search(r'Quelle:\s*(.+?)(?:\s*--&gt;|\s*-->|\n|\*/)', kopf)
        herkunft = re.sub(r'\s+', ' ', mq.group(1)).strip() if mq else ''
        herkunft = herkunft.rstrip('-> ').strip()
        name = datei.stem
        n = len(re.findall(r"['\"]" + re.escape(name) + r"['\"]", quelle))
        if n == 0:
            ohne.append(name)
        zeilen.append(f'| `{name}.svg` | {herkunft or "—"} | {n} |')
    zeilen += ['', f'{len(dateien)} Dateien in `server/assets/images/symbole/`, '
                   'dazu `LICENSE-tabler-icons.txt` und `LIESMICH.md`.']
    if ohne:
        zeilen.append('**Nirgends genannt:** ' + ', '.join('`' + n + '`' for n in ohne) + '.')
    return '\n'.join(zeilen)


# ------------------------------------------------------------ Bausteine

def bausteine_lesen() -> list[dict]:
    """Die Bausteine aus ui.php — nur die, die MARKUP ausgeben.

    `ui_e()`, `ui_asset()` und `ui_hat_tagesleiste()` stehen in derselben Datei
    und sind keine Bausteine, sondern Handwerkszeug. Unterschieden wird daran,
    ob der Rumpf ueberhaupt ein Tag erzeugt.

    DIE HAUPTKLASSE WIRD AUS DEM NAMEN ABGELEITET, nicht aus der ersten
    Zeichenkette im Rumpf: `ui_knopf()` gibt als erstes `nur-vorlesen` aus (das
    Vorleseetikett eines Symbolknopfs), und eine Tabelle, die daraufhin
    `.nur-vorlesen` als Hauptklasse des Knopfes fuehrt, ist schlimmer als
    keine. Die Ableitung wird gegen das Stylesheet geprueft; wo sie fehlgeht,
    steht ein Fragezeichen und keine Behauptung.
    """
    text = UI.read_text(encoding='utf-8')
    css = css_text()
    eintraege: list[dict] = []
    # WO DER NAME NICHT DIE KLASSE IST. Die Ableitung aus dem Funktionsnamen
    # trifft zwei Drittel der Bausteine; die uebrigen heissen anders als ihre
    # Klasse, und ein Werkzeug, das das errät, rät falsch. Sie stehen deshalb
    # hier — und weil die Zuordnung anschliessend gegen das Stylesheet
    # geprueft wird, faellt ein veralteter Eintrag hier als "keine" auf.
    ABWEICHEND = {
        'ui_zeilenaktionen': 'zeile-aktionen',
        'ui_speichern_leiste': 'speichern',
        'ui_abbruch': 'rahmen',
        'ui_geruest_start': 'inhalt',
        'ui_geruest_ende': 'inhalt',
        'ui_leiste_diensttage': 'leiste-liste',
        'ui_leiste_einstellungen': 'leiste-liste',
        'ui_einstellungen_uebersicht': 'uebersicht-block',
        'ui_ortsfeld': 'ortsfeld-zeile',
        'ui_seite_start': '', 'ui_seite_ende': '', 'ui_favicon': '',
        'ui_krypto_bootstrap': '', 'ui_logo': '',
    }
    treffer = list(re.finditer(r'^function (ui_\w+)\(', text, re.M))
    for i, m in enumerate(treffer):
        name = m.group(1)
        bis = treffer[i + 1].start() if i + 1 < len(treffer) else len(text)
        rumpf = text[m.end():bis]
        if '<' not in rumpf:
            continue                       # Handwerkszeug, kein Baustein
        zeile = text[:m.start()].count('\n') + 1
        stamm = name[3:]
        for suffix in ('_start', '_ende', '_markup'):
            if stamm.endswith(suffix):
                stamm = stamm[:-len(suffix)]
        klasse = ABWEICHEND.get(name, stamm.replace('_', '-'))
        if klasse == '':
            eintraege.append({'funktion': name, 'klasse': None, 'regel': False,
                              'familie': 0, 'zeile': zeile})
            continue
        # Die Klasse SELBST — oder ihre Familie: `.ortsfeld-zeile` zaehlt fuer
        # `.ortsfeld`, denn der Baustein ist gestaltet, nur traegt seine Huelle
        # keine eigene Regel.
        selbst = bool(re.search(r'\.' + re.escape(klasse) + r'[\s,{:.\[]', css))
        familie = len(re.findall(r'\.' + re.escape(klasse) + r'-[\w-]+', css))
        eintraege.append({'funktion': name, 'klasse': klasse, 'regel': selbst,
                          'familie': familie, 'zeile': zeile})
    return eintraege


def tabelle_bausteine() -> str:
    eintraege = bausteine_lesen()
    zeilen = ['<!-- ERZEUGT von tools/design/tabellen.py — nicht von Hand ändern. -->',
              '', '| Baustein | Klasse | Regel im Stylesheet | `ui.php` |', '|---|---|---|--:|']
    for e in eintraege:
        if e['klasse'] is None:
            zeilen.append(f"| `{e['funktion']}()` | — | Hüllenfunktion, kein "
                          f"eigenes Element | {e['zeile']} |")
            continue
        if e['regel']:
            regel = 'ja' + (f" (+{e['familie']} Unterklassen)" if e['familie'] else '')
        elif e['familie']:
            regel = f"nur die {e['familie']} Unterklassen"
        else:
            regel = '**keine**'
        zeilen.append(f"| `{e['funktion']}()` | `.{e['klasse']}` | {regel} | {e['zeile']} |")
    ohne = [e['funktion'] for e in eintraege
            if e['klasse'] is not None and not e['regel'] and not e['familie']]
    huelle = sum(1 for e in eintraege if e['klasse'] is None)
    zeilen += ['', f'{len(eintraege)} Funktionen mit Markup in `server/ui.php`, '
                   f'davon {huelle} Hüllenfunktionen ohne eigenes Element.']
    if ohne:
        zeilen.append('**Ohne Regel im Stylesheet:** '
                      + ', '.join('`' + n + '()`' for n in ohne)
                      + ' — jede davon ist zu prüfen: entweder ein Behälter, '
                        'der zu Recht keine Gestaltung braucht, oder eine Lücke.')
    return '\n'.join(zeilen)


TEILE = {'token': tabelle_token, 'schwellen': tabelle_schwellen,
         'symbole': tabelle_symbole, 'bausteine': tabelle_bausteine}

if __name__ == '__main__':
    was = sys.argv[1] if len(sys.argv) > 1 else 'alle'
    if was == 'alle':
        for k, f in TEILE.items():
            print(f'\n## {k}\n')
            print(f())
    elif was in TEILE:
        print(TEILE[was]())
    else:
        raise SystemExit(f'Unbekannt: {was}. Bekannt: {", ".join(TEILE)}, alle')
