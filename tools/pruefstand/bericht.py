#!/usr/bin/env python3
"""Der Prüfbericht — erzeugen, gegenlesen, als Tabelle ausgeben.

    python3 tools/pruefstand/bericht.py schreiben --stufe neben --konfiguration web \\
        --zahl syntax=php:511/0 --zahl bilderlauf=62/0/0/0 --flaeche handy=nicht-beruehrt
    python3 tools/pruefstand/bericht.py lesen [--commit HEAD] [--datei -]
    python3 tools/pruefstand/bericht.py lesen --selbstprobe
    python3 tools/pruefstand/bericht.py erzeugen-doku

Rückgabewert 0 = in Ordnung · 1 = der Bericht trägt nicht · 2 = die Probe
selbst kam nicht zum Laufen (fehlende Datei, unbekannter Schalter, kein Git).

MIT EINEM WERKZEUG UND NICHT MIT EINER SHELL-ZEILE (E-P5a-12): Eine Zeile,
die einen Wert aus Text zieht, ist die Stelle, an der ein Riegel still
durchlässt. Hier ist jede der vier roten Lagen eine Funktion mit einem
Prüffall in `--selbstprobe`.

Anlass: O9c — gemessen wurde vor der letzten Änderung, gemeldet wurde die
Zahl von davor. Der Baum-Hash fängt genau das.
"""
import argparse
import json
import os
import re
import subprocess
import sys

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
ABLAUF = os.path.join(HIER, 'pruefablauf.json')
sys.path.insert(0, HIER)
from auswahl import UNLESBAR, fassung, stufe_aus_fassungen  # noqa: E402 — die EINE Lesestelle (F-PK-30)

STUFEN = ['klein', 'neben', 'haupt']
KOPF_RE = re.compile(
    r'^Prüfstand:\s*(?P<stufe>klein|neben|haupt)\s*·\s*'
    r'Baum\s+(?P<baum>[0-9a-f]{7,40})\s*·\s*'
    r'Konfiguration\s+(?P<konf>[a-z]+)\s*$')
PAAR_RE = re.compile(r'([a-zA-Z][\w-]*)=(\S+)')

# Die Flächen, die als „nicht berührt" gemeldet werden dürfen — und das
# Muster aus pruefablauf.json, das ihre Berührung erkennt.
FLAECHEN = {'handy': 'android', 'uhr': 'uhr'}


def melde(t=''):
    # `| head` schliesst die Leitung; ein Werkzeug, das daran abstuerzt,
    # sieht kaputt aus und ist es nicht.
    try:
        print(t, flush=True)
    except BrokenPipeError:
        sys.exit(0)


def git(*args, **kw):
    return subprocess.run(['git', '-C', kw.get('wurzel', WURZEL), *args],
                          capture_output=True, text=True).stdout.strip()


def lade_ablauf():
    with open(ABLAUF, encoding='utf-8') as f:
        return json.load(f)


# --------------------------------------------------------------- schreiben

def baum_hash(commit='HEAD'):
    """Der Baum-Hash des Arbeitsbestands — nicht der des letzten Commits.

    WARUM NICHT `git rev-parse HEAD^{tree}`: Der Bericht soll den Stand
    belegen, der GEMESSEN wurde, und das ist der Arbeitsbestand im Augenblick
    des Laufs. Der Commit entsteht danach. `write-tree` schreibt den Baum des
    Index; damit er den Arbeitsbestand trifft, wird vorher `add -A` in einem
    EIGENEN Index gefahren, der den echten nicht anfasst.
    """
    eigener = os.path.join(WURZEL, '.git', 'pruefstand-index')
    umgebung = dict(os.environ, GIT_INDEX_FILE=eigener)
    try:
        subprocess.run(['git', '-C', WURZEL, 'add', '-A'], env=umgebung,
                       capture_output=True, check=True)
        baum = subprocess.run(['git', '-C', WURZEL, 'write-tree'], env=umgebung,
                              capture_output=True, text=True, check=True).stdout.strip()
    finally:
        if os.path.exists(eigener):
            os.unlink(eigener)
    return baum


def schreiben(args):
    zahlen = dict(p.split('=', 1) for p in args.zahl)
    flaechen = dict(p.split('=', 1) for p in args.flaeche)
    baum = args.baum or baum_hash()
    zeilen = [f'Prüfstand: {args.stufe} · Baum {baum} · Konfiguration {args.konfiguration}']
    if zahlen:
        rest = list(zahlen.items())
        while rest:
            teil, rest = rest[:3], rest[3:]
            zeilen.append('  ' + '  '.join(f'{k}={v}' for k, v in teil))
    if flaechen:
        zeilen.append('  ' + '  '.join(f'{k}={v}' for k, v in flaechen.items()))
    melde('\n'.join(zeilen))
    return 0


# ------------------------------------------------------------------- lesen

def zerlegen(text):
    """Aus einem Text (Commit-Nachricht) den Berichtsblock herausziehen."""
    zeilen = text.splitlines()
    for i, z in enumerate(zeilen):
        m = KOPF_RE.match(z.strip())
        if not m:
            continue
        b = {'stufe': m['stufe'], 'baum': m['baum'], 'konfiguration': m['konf'],
             'werte': {}}
        for w in zeilen[i + 1:]:
            if not w.startswith('  ') or not w.strip():
                break
            for k, v in PAAR_RE.findall(w):
                b['werte'][k] = v
        return b
    return None


def versionsstufe(basis, commit):
    """Welche Stufe verlangt der Unterschied in server/version.php?
    Gelesen in `auswahl.py` — eine Stelle für beide. UNLESBAR, wenn eine
    Seite keine Fassung trägt."""
    return stufe_aus_fassungen(fassung(basis), fassung(commit))[0]


def beruehrt(basis, commit):
    dateien = git('diff', '--name-only', f'{basis}...{commit}').splitlines()
    return [d for d in dateien if d]


def pruefen(bericht, basis=None, commit='HEAD', riegel=None, dateien=None, baum=None,
            stufe_verlangt=None):
    """Die vier roten Lagen. Gibt eine Liste von Beanstandungen zurück."""
    schlecht = []
    if bericht is None:
        return ['Kein Prüfbericht in der Nachricht — der Block fehlt ganz.']

    # (1) Baum-Hash
    ist = baum if baum is not None else git('rev-parse', f'{commit}^{{tree}}')
    if ist and not ist.startswith(bericht['baum']) and not bericht['baum'].startswith(ist[:len(bericht['baum'])]):
        schlecht.append(
            f"Baum-Hash passt nicht: Bericht {bericht['baum']}, Commit {ist[:len(bericht['baum'])]} "
            f"— gemessen wurde ein anderer Stand als der eingereichte (O9c).")

    # (2) Stufe. `stufe_verlangt` setzt die Versionsstufe direkt — die
    # Selbstprobe braucht sie ohne Git, und der Vergleich selbst ist derselbe.
    verlangt = stufe_verlangt or (versionsstufe(basis, commit) if basis else None)
    if verlangt == UNLESBAR:
        schlecht.append('Die Versionsstufe lässt sich nicht bestimmen: server/version.php trägt '
                        'keine lesbare WEB_VERSION — gemessen wird dann gegen nichts (F-PK-30).')
    elif verlangt:
        if STUFEN.index(bericht['stufe']) < STUFEN.index(verlangt):
            schlecht.append(
                f"Stufe zu klein: Bericht „{bericht['stufe']}\", die Versionsstufe verlangt "
                f"„{verlangt}\".")

    # (3) Berührte Fläche als „nicht berührt" gemeldet
    liste = dateien if dateien is not None else (beruehrt(basis, commit) if basis else [])
    for flaeche, ordner in FLAECHEN.items():
        wert = bericht['werte'].get(flaeche, '')
        gemeldet_frei = wert.replace('-', ' ').lower().startswith('nicht')
        wirklich = any(d.startswith(ordner + '/') for d in liste)
        if wirklich and gemeldet_frei:
            schlecht.append(
                f"„{flaeche}={wert}\", aber {ordner}/ ist berührt — der Bau fehlt.")

    # (4) Billiger Riegel mit anderer Zahl
    for name, zahl in (riegel or {}).items():
        gemeldet = bericht['werte'].get(name)
        if gemeldet is None:
            schlecht.append(f"Riegel „{name}\" fehlt im Bericht (im Tor gemessen: {zahl}).")
        elif gemeldet != str(zahl):
            schlecht.append(
                f"Riegel „{name}\": Bericht {gemeldet}, im Tor gemessen {zahl}.")
    return schlecht


def lesen(args):
    if args.selbstprobe:
        return selbstprobe()
    if args.datei == '-':
        text = sys.stdin.read()
    elif args.datei:
        text = open(args.datei, encoding='utf-8').read()
    else:
        text = git('log', '-1', '--format=%B', args.commit)
    bericht = zerlegen(text)
    schlecht = pruefen(bericht, basis=args.basis, commit=args.commit)
    if schlecht:
        melde('Der Prüfbericht trägt nicht:')
        for s in schlecht:
            melde(f'  ! {s}')
        return 1
    melde(f"Prüfbericht in Ordnung: Stufe {bericht['stufe']}, Baum {bericht['baum']}, "
          f"Konfiguration {bericht['konfiguration']}, {len(bericht['werte'])} Zahlen.")
    return 0


# ------------------------------------------------------------ Selbstprobe

def selbstprobe():
    """Vier rote Lagen und eine grüne — jede einzeln, mit Namen.

    EINE SELBSTPROBE, DIE NUR DEN GRÜNEN FALL FÄHRT, BELEGT NICHTS: Sie
    würde auch dann grün melden, wenn `pruefen()` immer eine leere Liste
    zurückgibt. Deshalb ist jede rote Lage ein eigener Fall, und der Lauf
    ist erst grün, wenn jede von ihnen WIRKLICH rot wird.
    """
    guter = ('Prüfstand: neben · Baum abc1234 · Konfiguration web\n'
             '  syntax=php:511/0  wortliste=0/0/0  vollstaendigkeit=398\n'
             '  handy=nicht-beruehrt  uhr=nicht-beruehrt\n')
    faelle = []

    faelle.append(('grün — Bericht passt zu allem',
                   zerlegen(guter), dict(baum='abc1234', dateien=['server/index.php'],
                                         riegel={'vollstaendigkeit': '398'}), 0))
    faelle.append(('rot (1) — Baum-Hash passt nicht',
                   zerlegen(guter), dict(baum='9999999', dateien=[], riegel={}), 1))
    faelle.append(('rot (2) — Stufe kleiner als verlangt',
                   zerlegen(guter.replace('neben', 'klein')),
                   dict(baum='abc1234', dateien=[], riegel={}, stufe_verlangt='haupt'), 1))
    faelle.append(('rot (3) — berührte Fläche als „nicht berührt" gemeldet',
                   zerlegen(guter), dict(baum='abc1234',
                                         dateien=['android/handy/src/Main.kt'], riegel={}), 1))
    faelle.append(('rot (4) — Riegel meldet eine andere Zahl',
                   zerlegen(guter), dict(baum='abc1234', dateien=[],
                                         riegel={'vollstaendigkeit': '399'}), 1))
    faelle.append(('rot (2b) — Versionsstufe nicht lesbar',
                   zerlegen(guter), dict(baum='abc1234', dateien=[], riegel={},
                                         stufe_verlangt=UNLESBAR), 1))
    faelle.append(('rot (0) — gar kein Bericht in der Nachricht',
                   zerlegen('Ein Commit ganz ohne Block.\n'),
                   dict(baum='abc1234', dateien=[], riegel={}), 1))

    fehl = 0
    for name, bericht, kw, erwartet in faelle:
        # JEDER FALL GEHT DURCH pruefen() — auch der mit der Stufe. Eine
        # Selbstprobe, die den Fall daneben nachbaut, prueft ihren Nachbau.
        schlecht = pruefen(bericht, **kw)
        ist = 1 if schlecht else 0
        ok = (ist == erwartet)
        melde(f"  [{'ok  ' if ok else 'FEHL'}] {name}"
              + ('' if ok else f'  — erwartet {erwartet}, gemessen {ist}'))
        if schlecht and erwartet == 1:
            melde(f"           {schlecht[0]}")
        if not ok:
            fehl += 1

    melde()
    melde(f'{len(faelle)} Lagen, {fehl} Fehlschlaege.  '
          f'({len(faelle) - 1} rote, 1 gruene)')
    return 1 if fehl else 0


# -------------------------------------------------------- erzeugen-doku

def erzeugen_doku(args):
    """Die Tabelle Berührung -> Probe für docs/Pruefablauf.md 4.

    ERZEUGT, NICHT GEPFLEGT: Wer sie von Hand ändert, ändert sie an der
    falschen Stelle (wie die Tabellen in Design.md).
    """
    a = lade_ablauf()
    melde('<!-- ERZEUGT von tools/pruefstand/bericht.py erzeugen-doku — nicht von Hand ändern. -->')
    melde()
    melde('| Berührung | ab Stufe | Proben | Anlass |')
    melde('|---|---|---|---|')
    for m in a['muster']:
        pfade = ', '.join(f'`{p}`' for p in m['pfade'])
        proben = ', '.join(f'`{p}`' for p in m['proben']) or '— (nur die Riegel)'
        melde(f"| {pfade} | {m['ab']} | {proben} | {m['anlass']} |")
    melde()
    melde('**Die billigen Riegel laufen in jeder Stufe, ohne Muster:** '
          + ', '.join(f"`{p}`" for p in a['riegel']['proben']) + '.')
    return 0


# ------------------------------------------------------------------- Lauf

def main():
    p = argparse.ArgumentParser(add_help=True, description=__doc__)
    u = p.add_subparsers(dest='befehl', required=True)

    s = u.add_parser('schreiben')
    s.add_argument('--stufe', required=True, choices=STUFEN)
    s.add_argument('--konfiguration', default='web')
    s.add_argument('--baum')
    s.add_argument('--zahl', action='append', default=[], metavar='name=wert')
    s.add_argument('--flaeche', action='append', default=[], metavar='name=wert')
    s.set_defaults(fn=schreiben)

    l = u.add_parser('lesen')
    l.add_argument('--commit', default='HEAD')
    l.add_argument('--basis')
    l.add_argument('--datei')
    l.add_argument('--selbstprobe', action='store_true')
    l.set_defaults(fn=lesen)

    d = u.add_parser('erzeugen-doku')
    d.set_defaults(fn=erzeugen_doku)

    args = p.parse_args()
    try:
        return args.fn(args)
    except FileNotFoundError as e:
        melde(f'Datei fehlt: {e}')
        return 2


if __name__ == '__main__':
    sys.exit(main())
