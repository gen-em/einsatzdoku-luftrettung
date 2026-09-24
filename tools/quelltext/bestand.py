#!/usr/bin/env python3
"""Der Bestandsriegel — hält der Werkzeugbestand unter tools/ die Regeln aus
docs/Pruefablauf.md 6?

    python3 tools/quelltext/bestand.py
    python3 tools/quelltext/bestand.py --selbstprobe

Rückgabewert 0 = keine Befunde · 1 = Befunde · 2 = die Prüfung selbst kam
nicht zum Laufen (kein tools/, kein Backlog).

WOFÜR (Konzept BR). PK hat den DURCHLAUF einer Änderung als Riegel gebaut
und den BESTAND als Regel gelassen: Anlass-Zeile, Fünf-Abschnitte-Form mit
höchstens 40 Zeilen, Streichliste. Drei Tage nach PK-04 standen die Zahlen
unter den Regeln, und niemand hatte es gemerkt, weil es niemand zählte. Eine
Regel ohne Messung ist eine Hoffnung.

WAS ER MISST — sechs Regeln, jede mit Namen im Befund:

  form       jede LIESMICH.md unter tools/, auch in Unterordnern (E-BR-04):
             genau die fünf Abschnitte aus 6.2 in dieser Reihenfolge,
             höchstens 40 Zeilen
  anleitung  jeder Ordner direkt unter tools/ hat eine LIESMICH.md
  anlass     deren Anleitung trägt GENAU EINE Zeile, die mit „Anlass:"
             beginnt und mindestens eine Backlog-Nummer nennt, die es gibt
             (E-BR-07) — oder die Erzeugerform (E-BR-05), die einzige andere
  inventur   der Ordner wird gerufen: `tools/<ordner>/` steht in
             pruefablauf.json, in einem Workflow, in tools/pruefstand/
             pruefen.sh oder in docs/Sandbox-Setup.md (E-BR-03 (3))
  lose       keine Datei direkt unter tools/ außer motor.mjs (E-BR-03 (4))
  probe      jede Probe in tools/proben/proben.sh hat eine Einstiegsdatei,
             und deren Kopfkommentar trägt genau eine Anlass-Zeile mit
             Backlog-Nummer; kein Probenordner ohne Eintrag im Läufer

WAS ER NICHT MISST, und das ist die benannte Grenze: den Inhalt einer
Anleitung, ob der Anlass zur Probe passt, ob ein Werkzeug überflüssig ist.
Das bleibt Lesearbeit (docs/Pruefablauf.md 6.12). Die Anlass-SPALTE in
pruefablauf.json und in der Tabelle der Quelltextprüfungen misst er nicht —
nur die Zeile (E-BR-07).

GELESEN WIRD, WAS `git add -A` IN DEN BAUM LEGTE: versionierte und neue,
nicht ignorierte Dateien. Eine Ausgabe wie tools/screenshots/ausgabe/ zählt
nicht mit — sonst hinge die Zahl davon ab, wer wo zuletzt gemessen hat.

KEINE DECKE, KEIN ALTBESTAND (E-BR-01). Der Riegel steht auf null, weil der
Altbestand im selben Konzept bereinigt wird. Wer hier eine Ausnahmeliste
einführt, führt die Streichliste wieder ein, die niemand liest.
"""
import argparse
import os
import re
import shutil
import subprocess
import sys
import tempfile

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))

ABSCHNITTE = ['Aufruf', 'Was es misst', 'Was es braucht', 'Erwartete Zahl',
              'Was es nicht kann']          # docs/Pruefablauf.md 6.2
HOECHSTENS = 40
ERZEUGER = 'Anlass: entfällt — Erzeuger (E-BR-05)'
LOSE_ERLAUBT = {'motor.mjs'}                 # E-BR-03 (4), beim Namen — kein zweiter
# Wo ein Ordner gerufen sein kann (E-BR-03 (3)). Workflows kommen als Muster dazu.
RUFER = ['tools/pruefstand/pruefablauf.json', 'tools/pruefstand/pruefen.sh',
         'docs/Sandbox-Setup.md']
REGELN = ['form', 'anleitung', 'anlass', 'inventur', 'lose', 'probe']

ZAUN_RE = re.compile(r'^\s*(```|~~~)')
ABSCHNITT_RE = re.compile(r'^##(?!#)\s*(.+?)\s*$')
NUMMER_RE = re.compile(r'Nr\.\s*(\d+(?:\s*(?:,|und)\s*\d+)*)')
BACKLOG_RE = re.compile(r'^(\d+)\.')
# Was vor „Anlass:" stehen darf: Kommentarzeichen und Hervorhebung, sonst nichts.
VORSATZ_RE = re.compile(r'^\s*(?:>\s*)?(?:/\*+|\*+(?!\*)|//+|#+|<!--)?\s*[*_]*\s*')


def melde(t=''):
    try:
        print(t, flush=True)
    except BrokenPipeError:
        sys.exit(0)


# ------------------------------------------------------------------ Lesen

def dateien(wurzel):
    """Relative Pfade unter tools/ — versioniert oder neu, nicht ignoriert.
    Ohne Git (nur denkbar außerhalb eines Auschecks) alles, was da ist."""
    r = subprocess.run(['git', '-C', wurzel, 'ls-files', '-co', '--exclude-standard', '--', 'tools'],
                       capture_output=True, text=True)
    if r.returncode == 0:
        return sorted({p for p in r.stdout.splitlines()
                       if p and os.path.isfile(os.path.join(wurzel, p))})
    aus = []
    for ordner, _, namen in os.walk(os.path.join(wurzel, 'tools')):
        for n in namen:
            aus.append(os.path.relpath(os.path.join(ordner, n), wurzel).replace(os.sep, '/'))
    return sorted(aus)


def lies(wurzel, rel):
    with open(os.path.join(wurzel, rel), encoding='utf-8', errors='replace') as f:
        return f.read()


def zeilenzahl(text):
    """Wie `wc -l`, dazu eine letzte Zeile ohne Umbruch."""
    return text.count('\n') + (1 if text and not text.endswith('\n') else 0)


def ausserhalb_zaun(text):
    """(Zeilennummer, Zeile) außerhalb von Codeblöcken. Ein `## x` in einem
    Bash-Beispiel ist kein Abschnitt, ein `Anlass:` darin keine Zeile."""
    im_zaun = False
    for i, z in enumerate(text.split('\n'), 1):
        if ZAUN_RE.match(z):
            im_zaun = not im_zaun
            continue
        if not im_zaun:
            yield i, z


def anlass_zeilen(zeilen):
    """Die Zeilen, die mit „Anlass:" BEGINNEN — nach Kommentarzeichen und
    Hervorhebung. `**Anlass: Nr. 1**` zählt, `… fährt. **Anlass: Nr. 1**`
    nicht: Eine Zeile, die man nur findet, wenn man sie sucht, ist keine."""
    aus = []
    for i, z in zeilen:
        rest = VORSATZ_RE.sub('', z, count=1)
        if rest.startswith('Anlass:'):
            aus.append((i, rest.rstrip()))
    return aus


def backlog_nummern(wurzel):
    pfad = os.path.join(wurzel, 'docs', 'Backlog.md')
    with open(pfad, encoding='utf-8') as f:
        return {int(m.group(1)) for m in map(BACKLOG_RE.match, f) if m}


def anlass_pruefen(text, backlog, erzeuger_erlaubt):
    """Befundtext für eine einzelne Anlass-Zeile, oder None."""
    ohne_hervorhebung = re.sub(r'\*', '', text).strip()
    if ohne_hervorhebung == ERZEUGER:
        return None if erzeuger_erlaubt else 'die Erzeugerform gilt nur für eine Anleitung, nicht hier'
    text = ohne_hervorhebung
    nummern = [int(n) for g in NUMMER_RE.findall(text) for n in re.findall(r'\d+', g)]
    if not nummern:
        return (f'„{text[:70]}" nennt keine Backlog-Nummer — ein Anlass ist eine '
                f'Backlog-Nummer (E-BR-07), keine Befund-Kennung und kein Kürzel')
    fehlt = [n for n in nummern if n not in backlog]
    if fehlt:
        return (f'Nr. {", ".join(map(str, fehlt))} steht nicht im Backlog '
                f'(„{text[:60]}")')
    return None


def kopfkommentar(text):
    """(Zeilennummer, Zeile) des ERSTEN Kommentarblocks einer Quelldatei.

    Davor darf nur stehen, was jede Datei ihrer Art trägt: `#!…`, `<?php`,
    `declare(strict_types=1);`, `'use strict';` und Leerzeilen. Der Kopf endet
    mit dem Block — eine Anlass-Zeile weiter unten im Code ist keine Zeile im
    Kopf, auch wenn sie so aussieht.
    """
    zeilen = text.split('\n')
    i = 0
    while i < len(zeilen):
        s = zeilen[i].strip()
        if (not s or s.startswith('#!') or s == '<?php' or s.startswith('declare(')
                or s in ("'use strict';", '"use strict";')):
            i += 1
            continue
        break
    if i >= len(zeilen):
        return []
    s = zeilen[i].lstrip()
    aus = []
    if s.startswith('/*'):
        for j in range(i, len(zeilen)):
            aus.append((j + 1, zeilen[j]))
            if '*/' in zeilen[j][(zeilen[j].find('/*') + 2) if j == i else 0:]:
                break
    elif s.startswith('<!--'):
        for j in range(i, len(zeilen)):
            aus.append((j + 1, zeilen[j]))
            if '-->' in zeilen[j]:
                break
    elif s.startswith(('"""', "'''")):
        zeichen = s[:3]
        for j in range(i, len(zeilen)):
            aus.append((j + 1, zeilen[j]))
            if zeichen in (zeilen[j].lstrip()[3:] if j == i else zeilen[j]):
                break
    else:
        for vorsatz in ('//', '#'):
            if s.startswith(vorsatz):
                j = i
                while j < len(zeilen) and zeilen[j].lstrip().startswith(vorsatz):
                    aus.append((j + 1, zeilen[j]))
                    j += 1
                break
    return aus


AUFRUF_RE = re.compile(r'(?:php|node|python3?|bash)\s+(tools/proben/[\w./-]+\.(?:php|mjs|py|sh))')


def einstiege(quelle):
    """{Probe: Einstiegsdatei oder None} aus dem Verteiler `declare -A RUF=(…)`
    von proben.sh.

    Ist der Wert eine Funktion (die Versandprobe startet erst ihre
    Gegenstellen), zählt der EINE Aufruf im Vordergrund. Eine Gegenstelle läuft
    im Hintergrund (`… &`). Die erste Fassung nahm den ersten Aufruf im Rumpf
    und hielt damit eine Gegenstelle für die Probe, sobald sie oben stand —
    gefunden von der eigenen Selbstprobe.
    """
    m = re.search(r'declare -A RUF=\((.*?)\n\)', quelle, re.S)
    if not m:
        return None
    aus = {}
    for name, wert in re.findall(r'\[([\w-]+)\]="([^"]*)"', m.group(1)):
        pfad = re.search(r'(tools/proben/[\w./-]+\.(?:php|mjs|py|sh))', wert)
        if pfad:
            aus[name] = pfad.group(1)
            continue
        vorne = set()
        rumpf = re.search(r'^' + re.escape(wert.split()[0]) + r'\(\)\s*\{(.*?)^\}', quelle, re.S | re.M) \
            if wert.split() else None
        for z in (rumpf.group(1).split('\n') if rumpf else []):
            z = z.split('#', 1)[0].rstrip()
            if z.endswith('&') and not z.endswith('&&'):
                continue
            vorne |= set(AUFRUF_RE.findall(z))
        aus[name] = vorne.pop() if len(vorne) == 1 else None
    return aus


# ----------------------------------------------------------------- Messen

def messen(wurzel):
    """(Befunde als [(regel, text)], Zahlen). Wirft FileNotFoundError, wenn
    die Grundlage fehlt — das ist rc 2, kein grüner Lauf."""
    if not os.path.isdir(os.path.join(wurzel, 'tools')):
        raise FileNotFoundError(os.path.join(wurzel, 'tools'))
    backlog = backlog_nummern(wurzel)
    alle = dateien(wurzel)
    ordner = sorted({p.split('/')[1] for p in alle if p.count('/') >= 2})
    lose = [p for p in alle if p.count('/') == 1]
    anleitungen = [p for p in alle if p.endswith('/LIESMICH.md')]
    befunde = []
    zahlen = {'ordner': len(ordner), 'anleitungen': len(anleitungen), 'erzeuger': [],
              'proben': 0, 'lose': [os.path.basename(p) for p in lose]}

    # form — jede Anleitung, rekursiv (E-BR-04)
    for p in anleitungen:
        text = lies(wurzel, p)
        n = zeilenzahl(text)
        if n > HOECHSTENS:
            befunde.append(('form', f'{p}: {n} Zeilen, erlaubt sind {HOECHSTENS}'))
        ist = [ABSCHNITT_RE.match(z).group(1) for _, z in ausserhalb_zaun(text) if ABSCHNITT_RE.match(z)]
        if ist != ABSCHNITTE:
            fehlt = [a for a in ABSCHNITTE if a not in ist]
            fremd = [a for a in ist if a not in ABSCHNITTE]
            teile = []
            if fehlt:
                teile.append('es fehlt ' + ', '.join(f'„{a}"' for a in fehlt))
            if fremd:
                teile.append(f'{len(fremd)} fremde' + (': ' + ', '.join(f'„{a}"' for a in fremd[:3])
                                                         + (' …' if len(fremd) > 3 else '')))
            if not teile:
                teile.append('Reihenfolge oder Zahl stimmt nicht: ' + ' · '.join(ist))
            befunde.append(('form', f'{p}: {len(ist)} Abschnitte statt der fünf aus 6.2 — '
                                    + '; '.join(teile)))

    # anleitung, anlass — je Ordner direkt unter tools/
    for d in ordner:
        p = f'tools/{d}/LIESMICH.md'
        if p not in anleitungen:
            befunde.append(('anleitung', f'tools/{d}/ hat keine LIESMICH.md'))
            continue
        text = lies(wurzel, p)
        zeilen = list(ausserhalb_zaun(text))
        treffer = anlass_zeilen(zeilen)
        if not treffer:
            mitten = [i for i, z in zeilen if 'Anlass:' in z]
            befunde.append(('anlass', f'{p}: keine Zeile, die mit „Anlass:" beginnt'
                                      + (f' (mitten in Zeile {mitten[0]})' if mitten else '')))
        elif len(treffer) > 1:
            befunde.append(('anlass', f'{p}: {len(treffer)} Anlass-Zeilen '
                                      f'({", ".join(str(i) for i, _ in treffer)}), erlaubt ist eine'))
        else:
            fehler = anlass_pruefen(treffer[0][1], backlog, erzeuger_erlaubt=True)
            if fehler:
                befunde.append(('anlass', f'{p}:{treffer[0][0]}: {fehler}'))
            elif re.sub(r'\*', '', treffer[0][1]).strip() == ERZEUGER:
                zahlen['erzeuger'].append(d)

    # inventur — wird der Ordner gerufen?
    rufer = ''
    for r in RUFER:
        if os.path.isfile(os.path.join(wurzel, r)):
            rufer += lies(wurzel, r)
    wf = os.path.join(wurzel, '.github', 'workflows')
    if os.path.isdir(wf):
        for n in sorted(os.listdir(wf)):
            if n.endswith(('.yml', '.yaml')):
                rufer += lies(wurzel, f'.github/workflows/{n}')
    for d in ordner:
        if f'tools/{d}/' not in rufer:
            befunde.append(('inventur', f'tools/{d}/ wird nirgends gerufen — weder in '
                                        f'pruefablauf.json noch in einem Workflow, pruefen.sh oder '
                                        f'Sandbox-Setup.md: ein Kandidat für die Streichliste'))

    # lose — nur motor.mjs, beim Namen
    for p in lose:
        if os.path.basename(p) not in LOSE_ERLAUBT:
            befunde.append(('lose', f'{p} liegt lose direkt unter tools/ — erlaubt ist nur '
                                    f'{", ".join(sorted(LOSE_ERLAUBT))} (E-BR-03 (4))'))

    # probe — die Anlass-Zeile jeder Probe im Kopf ihrer Einstiegsdatei
    if 'proben' in ordner:
        laeufer = 'tools/proben/proben.sh'
        ein = einstiege(lies(wurzel, laeufer)) if laeufer in alle else None
        if ein is None:
            befunde.append(('probe', f'{laeufer} fehlt oder hat keinen Verteiler RUF — '
                                     f'keine Probe ist messbar'))
            ein = {}
        zahlen['proben'] = len(ein)
        belegt = set()
        for name, datei in sorted(ein.items()):
            if not datei:
                befunde.append(('probe', f'{name}: Einstiegsdatei im Läufer nicht ermittelbar'))
                continue
            belegt.add(datei.split('/')[2])
            if datei not in alle:
                befunde.append(('probe', f'{name}: {datei} fehlt'))
                continue
            treffer = anlass_zeilen(kopfkommentar(lies(wurzel, datei)))
            if not treffer:
                befunde.append(('probe', f'{datei}: keine Anlass-Zeile im Kopfkommentar'))
            elif len(treffer) > 1:
                befunde.append(('probe', f'{datei}: {len(treffer)} Anlass-Zeilen im Kopf, erlaubt ist eine'))
            else:
                fehler = anlass_pruefen(treffer[0][1], backlog, erzeuger_erlaubt=False)
                if fehler:
                    befunde.append(('probe', f'{datei}:{treffer[0][0]}: {fehler}'))
        for u in sorted({p.split('/')[2] for p in alle if p.startswith('tools/proben/') and p.count('/') >= 3}):
            if u not in belegt:
                befunde.append(('probe', f'tools/proben/{u}/ hat keinen Eintrag im Läufer — '
                                         f'ihr Anlass ist nicht messbar'))
    befunde.sort(key=lambda b: REGELN.index(b[0]))     # stabil: je Regel in Fundreihenfolge
    return befunde, zahlen


# ------------------------------------------------------------------- Lauf

def bericht(befunde, zahlen):
    melde('Bestandsriegel — tools/ gegen docs/Pruefablauf.md 6 (Konzept BR)')
    melde()
    erz = zahlen['erzeuger']
    melde(f"  Ordner unter tools/:     {zahlen['ordner']}"
          + (f"  (davon {len(erz)} mit Erzeugerform: {', '.join(erz)})" if erz else ''))
    melde(f"  Anleitungen (rekursiv):  {zahlen['anleitungen']}")
    melde(f"  Proben im Läufer:        {zahlen['proben']}")
    melde(f"  lose Dateien:            {len(zahlen['lose'])}  ({', '.join(zahlen['lose']) or '—'})")
    melde()
    for r in REGELN:
        melde(f'  {r:<10} {sum(1 for b in befunde if b[0] == r):>3} Befunde')
    if befunde:
        melde()
        melde('BEFUNDE:')
        for r, t in befunde:
            melde(f'  ! {r} · {t}')
        melde()
        melde(f'{len(befunde)} Befunde. Der Weg für ein neues Prüfmittel: docs/Pruefablauf.md 6.12.')
        return 1
    melde()
    melde('0 Befunde — der Bestand hält die Form.')
    return 0


# ------------------------------------------------------------ Selbstprobe

ANLEITUNG = '''# {titel}

Ein Werkzeug der Selbstprobe.
{anlass}

## Aufruf

```bash
python3 tools/{ordner}/lauf.py
## kein Abschnitt, nur ein Kommentar im Beispiel
```

## Was es misst

Etwas.

## Was es braucht

Nichts.

## Erwartete Zahl

Null.

## Was es nicht kann

Alles andere.
'''


def _anleitung(ordner, anlass='Anlass: Nr. 1 — ein erfundener Fehler.', titel='Werkzeug'):
    return ANLEITUNG.format(ordner=ordner, anlass=anlass, titel=titel)


PROBE_PHP = '''<?php
declare(strict_types=1);

/**
 * Probe eins — misst etwas.
 *
{anlass}
 */

$x = 1;
{unten}
'''

PROBE_MJS = '''/* Probe zwei — misst etwas anderes.
 *
 * Anlass: Nr. 2 — ein zweiter erfundener Fehler.
 */
import {{ x }} from './y.mjs';
'''

LAEUFER = '''#!/usr/bin/env bash
zwei_mit_nachbau() {
    python3 tools/proben/zwei/gegenstelle.py &
    node tools/proben/zwei/probe.mjs "$@"
}
declare -A RUF=(
  [eins]="php tools/proben/eins/probe.php"
  [zwei]="zwei_mit_nachbau"
)
'''


def _grundbestand():
    """Ein Bestand, der jede Regel hält — mit je einer Gegenprobe darin:
    ein `##` im Codeblock, eine hervorgehobene Anlass-Zeile, die Erzeugerform,
    motor.mjs, eine ignorierte Ausgabe mit einer übergroßen Anleitung."""
    return {
        'docs/Backlog.md': 'Kopf.\n\n1. **Eins.**\n\n2. **Zwei.**\n',
        'docs/Sandbox-Setup.md': 'Die Anlage braucht `tools/erzeuger/bild.sh`.\n',
        '.gitignore': 'tools/werkzeug/ausgabe/\n',
        '.github/workflows/p.yml': 'run: python3 tools/werkzeug/lauf.py\n',
        'tools/motor.mjs': 'export const x = 1;\n',
        'tools/werkzeug/LIESMICH.md': _anleitung('werkzeug', '**Anlass: Nr. 1** — ein erfundener Fehler.'),
        'tools/werkzeug/lauf.py': 'print(1)\n',
        'tools/werkzeug/unter/LIESMICH.md': _anleitung('werkzeug/unter', '', 'Unterteil'),
        'tools/werkzeug/ausgabe/LIESMICH.md': 'x\n' * 100,
        'tools/erzeuger/LIESMICH.md': _anleitung('erzeuger', ERZEUGER, 'Erzeuger'),
        'tools/erzeuger/bild.sh': 'echo\n',
        'tools/proben/LIESMICH.md': _anleitung('proben', 'Anlass: Nr. 1, 2 — je Probe im Kopf ihrer Datei.', 'Proben'),
        'tools/proben/proben.sh': LAEUFER,
        'tools/proben/eins/probe.php': PROBE_PHP.format(anlass=' * Anlass: Nr. 1 — ein erfundener Fehler.', unten=''),
        'tools/proben/zwei/probe.mjs': PROBE_MJS,
        'tools/proben/zwei/gegenstelle.py': 'print(2)\n',
        'tools/pruefstand/LIESMICH.md': _anleitung('pruefstand', 'Anlass: Nr. 2 — ein zweiter.', 'Prüfstand'),
        'tools/pruefstand/pruefablauf.json': '{"a": "bash tools/proben/proben.sh eins"}\n',
        'tools/pruefstand/pruefen.sh': '# tools/pruefstand/ ruft sich hier selbst\n',
    }


def _mehr(pfad, alt, neu):
    def f(b):
        assert alt in b[pfad], (pfad, alt)
        b[pfad] = b[pfad].replace(alt, neu, 1)
    return f


def _setze(pfad, inhalt):
    def f(b):
        b[pfad] = inhalt
    return f


def _weg(pfad):
    def f(b):
        del b[pfad]
    return f


def _auf(pfad, zeilen):
    """Füllt eine Anleitung auf GENAU `zeilen` Zeilen auf — die Grenze wird
    an ihr selbst gemessen, nicht irgendwo darüber."""
    def f(b):
        b[pfad] += 'Mehr.\n' * (zeilen - zeilenzahl(b[pfad]))
        assert zeilenzahl(b[pfad]) == zeilen
    return f


FAELLE = [
    # (Name, erwartete Regel oder None für „0 Befunde", Eingriff)
    ('GEGENPROBE: der Grundbestand hält jede Regel', None, lambda b: None),
    ('form — Anleitung mit 41 Zeilen', 'form', _auf('tools/werkzeug/LIESMICH.md', 41)),
    ('GEGENPROBE: Anleitung mit genau 40 Zeilen', None, _auf('tools/werkzeug/LIESMICH.md', 40)),
    ('form — ein Abschnitt fehlt', 'form',
     _mehr('tools/werkzeug/LIESMICH.md', '## Was es nicht kann\n\nAlles andere.\n', '')),
    ('form — zwei Abschnitte vertauscht', 'form',
     _mehr('tools/werkzeug/LIESMICH.md', '## Was es braucht\n\nNichts.\n\n## Erwartete Zahl\n\nNull.\n',
           '## Erwartete Zahl\n\nNull.\n\n## Was es braucht\n\nNichts.\n')),
    ('form — ein sechster Abschnitt', 'form',
     _mehr('tools/werkzeug/LIESMICH.md', 'Alles andere.\n', 'Alles andere.\n\n## Geschichte\n\nFrüher.\n')),
    ('form — Unteranleitung mit 41 Zeilen (E-BR-04)', 'form', _auf('tools/werkzeug/unter/LIESMICH.md', 41)),
    ('anleitung — Ordner ohne LIESMICH.md', 'anleitung',
     lambda b: (b.__setitem__('tools/neu/lauf.py', 'print(3)\n'),
                b.__setitem__('tools/pruefstand/pruefen.sh', b['tools/pruefstand/pruefen.sh'] + 'python3 tools/neu/lauf.py\n'))),
    ('anlass — keine Anlass-Zeile', 'anlass',
     _mehr('tools/werkzeug/LIESMICH.md', '**Anlass: Nr. 1** — ein erfundener Fehler.', '')),
    ('anlass — Befund-Kennung statt Backlog-Nummer (E-BR-07)', 'anlass',
     _mehr('tools/werkzeug/LIESMICH.md', '**Anlass: Nr. 1**', '**Anlass: F-XY-01**')),
    ('anlass — eine Nummer, die es im Backlog nicht gibt', 'anlass',
     _mehr('tools/werkzeug/LIESMICH.md', 'Nr. 1**', 'Nr. 999**')),
    ('anlass — zwei Anlass-Zeilen', 'anlass',
     _mehr('tools/werkzeug/LIESMICH.md', 'Ein Werkzeug der Selbstprobe.\n',
           'Ein Werkzeug der Selbstprobe.\nAnlass: Nr. 2 — noch einer.\n')),
    ('anlass — mitten in einer Zeile', 'anlass',
     _mehr('tools/werkzeug/LIESMICH.md', '**Anlass: Nr. 1**', 'Es misst. **Anlass: Nr. 1**')),
    ('probe — die Erzeugerform gilt in einer Probe nicht', 'probe',
     _mehr('tools/proben/eins/probe.php', ' * Anlass: Nr. 1 — ein erfundener Fehler.', ' * ' + ERZEUGER)),
    ('inventur — ein Ordner, den niemand ruft', 'inventur',
     _setze('docs/Sandbox-Setup.md', 'Nichts.\n')),
    ('lose — eine zweite lose Datei', 'lose',
     _setze('tools/hilfe.php', '<?php\n')),
    ('GEGENPROBE: motor.mjs fehlt — auch das ist grün', None, _weg('tools/motor.mjs')),
    ('probe — keine Anlass-Zeile im Kopf', 'probe',
     _mehr('tools/proben/eins/probe.php', ' * Anlass: Nr. 1 — ein erfundener Fehler.', ' * Kein Anlass.')),
    ('probe — Anlass-Zeile unter dem Kopf, im Code', 'probe',
     lambda b: b.__setitem__('tools/proben/eins/probe.php', PROBE_PHP.format(
         anlass=' * Kein Anlass.', unten='// Anlass: Nr. 1 — zu spät, das ist kein Kopf.'))),
    ('probe — Anlass mit Nummer, die es nicht gibt', 'probe',
     _mehr('tools/proben/zwei/probe.mjs', 'Nr. 2', 'Nr. 22')),
    ('probe — Probenordner ohne Eintrag im Läufer', 'probe',
     _setze('tools/proben/drei/probe.php', '<?php\n/* Anlass: Nr. 1 — x. */\n')),
    ('probe — Einstiegsdatei aus einer Funktion fehlt', 'probe',
     _weg('tools/proben/zwei/probe.mjs')),
]


def _bauen(wurzel, bestand):
    for rel, inhalt in bestand.items():
        pfad = os.path.join(wurzel, rel)
        os.makedirs(os.path.dirname(pfad), exist_ok=True)
        with open(pfad, 'w', encoding='utf-8') as f:
            f.write(inhalt)
    subprocess.run(['git', '-C', wurzel, 'init', '-q'], capture_output=True, check=True)


def selbstprobe():
    """Je Regel ein eingebauter Fehler, und jeder muss als Befund DIESER Regel
    kommen — dazu Gegenproben, die grün bleiben müssen.

    EINE SELBSTPROBE, DIE NUR DEN GRÜNEN FALL FÄHRT, BELEGT NICHTS: Ein Riegel,
    der kaputt ist und darum nichts findet, meldet auch grün.
    """
    melde('Selbstprobe des Bestandsriegels')
    melde()
    fehl = 0
    for name, regel, eingriff in FAELLE:
        bestand = _grundbestand()
        eingriff(bestand)
        ordner = tempfile.mkdtemp(prefix='bestand-')
        try:
            _bauen(ordner, bestand)
            befunde, _ = messen(ordner)
        finally:
            shutil.rmtree(ordner, ignore_errors=True)
        regeln = sorted({r for r, _ in befunde})
        ok = (not befunde) if regel is None else (regeln == [regel])
        fehl += not ok
        melde(f"  [{'ok  ' if ok else 'FEHL'}] {name}")
        if befunde and (regel is not None or not ok):
            melde(f'         {befunde[0][0]} · {befunde[0][1][:110]}')
        if not ok:
            melde(f'         erwartet: {regel or "keine Befunde"}, gemessen: {", ".join(regeln) or "keine"}')
    abgedeckt = {f[1] for f in FAELLE if f[1]}
    ohne = [r for r in REGELN if r not in abgedeckt]
    if ohne:
        melde(f'  [FEHL] Regeln ohne eingebauten Fehler: {", ".join(ohne)}')
        fehl += 1
    melde()
    melde(f'{len(FAELLE)} Fälle, {fehl} Fehlschläge  '
          f'({sum(1 for f in FAELLE if f[1])} mit eingebautem Fehler, '
          f'{sum(1 for f in FAELLE if not f[1])} Gegenproben; {len(REGELN)} Regeln abgedeckt)')
    return 1 if fehl else 0


def main():
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('--selbstprobe', action='store_true', help='je Regel ein eingebauter Fehler')
    p.add_argument('--wurzel', default=WURZEL, help='anderer Auscheck (z. B. ein Arbeitsbaum)')
    a = p.parse_args()
    if a.selbstprobe:
        return selbstprobe()
    try:
        befunde, zahlen = messen(a.wurzel)
    except FileNotFoundError as e:
        melde(f'Grundlage fehlt: {e} — gemessen wurde nichts.')
        return 2
    return bericht(befunde, zahlen)


if __name__ == '__main__':
    sys.exit(main())
