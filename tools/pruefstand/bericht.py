#!/usr/bin/env python3
"""Der Prüfbericht — erzeugen, gegenlesen, als Tabelle ausgeben.

    python3 tools/pruefstand/bericht.py schreiben --stufe neben --konfiguration web \\
        --zahl syntax-php=php:486/0 --zahl bilderlauf=0 --basis origin/main
    python3 tools/pruefstand/bericht.py lesen [--commit HEAD] [--datei -]
    python3 tools/pruefstand/bericht.py lesen --selbstprobe
    python3 tools/pruefstand/bericht.py erzeugen-doku

Rückgabewert 0 = in Ordnung · 1 = der Bericht trägt nicht · 2 = die Probe
selbst kam nicht zum Laufen (fehlende Datei, unbekannter Schalter, kein Git).

MIT EINEM WERKZEUG UND NICHT MIT EINER SHELL-ZEILE (E-P5a-12): Eine Zeile,
die einen Wert aus Text zieht, ist die Stelle, an der ein Riegel still
durchlässt. Hier ist jede der fünf roten Lagen eine Funktion mit einem
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
from auswahl import UNLESBAR, beruehrte, fassung, stufe_aus_fassungen, stufe_aus_version  # noqa: E402 — die EINE Lesestelle (F-PK-30)

STUFEN = ['klein', 'neben', 'haupt']
KOPF_RE = re.compile(
    r'^Prüfstand:\s*(?P<stufe>klein|neben|haupt)\s*·\s*'
    r'Baum\s+(?P<baum>[0-9a-f]{7,40})\s*·\s*'
    r'Konfiguration\s+(?P<konf>[a-z]+)\s*$')
PAAR_RE = re.compile(r'([a-zA-Z][\w-]*)=(\S+)')

# Die Flächen: die ORDNER, deren Berührung sie verrät, und die PROBE, die sie
# baut. EINE Stelle für Schreiben und Lesen. Bis PK-05 stand hier
# `'uhr': 'uhr'` — ein Ordner, den es nicht gibt, und Lage 3 konnte für die
# Uhr nie anschlagen (F-PK-31). Der Uhr-Prüfstand gehört dazu, wie in der
# alten Bereichserkennung (F-PK-36).
FLAECHEN = {'handy': (('android',), 'android-bau'),
            'uhr': (('watch', 'tools/uhr-pruefstand'), 'uhr-stufe1')}


def flaeche_beruehrt(flaeche, dateien):
    ordner = FLAECHEN[flaeche][0]
    return any(d.startswith(o + '/') for o in ordner for d in dateien)

# DER WEG NACH EINEM FREMDEN MERGE (E-PK-42). Das Tor ist streng: Der Baum im
# Bericht muss der Baum des geprüften Commits sein. „Update branch" auf GitHub
# schreibt einen Merge-Commit ohne Bericht — also rot, mit diesem Weg.
WEG = ('Weg: `bash tools/pruefstand/pruefen.sh` auf genau diesem Stand fahren und den Bericht in die '
       'Nachricht des Kopf-Commits schreiben. Nach einem fremden Merge vorher örtlich '
       '`git merge --no-commit origin/main` statt „Update branch" (Pruefablauf.md 5.3).')


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

    DER ORT DES INDEX FRAGT GIT (BR-04, Nr. 314). Bis dahin stand hier
    `WURZEL/.git/…`; in einem Worktree ist `.git` eine Datei, `add -A` brach
    ab, und der Prüfstand meldete trotzdem grün.
    """
    eigener = subprocess.run(['git', '-C', WURZEL, 'rev-parse', '--path-format=absolute',
                              '--git-path', 'pruefstand-index'],
                             capture_output=True, text=True, check=True).stdout.strip()
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


def flaechen_aus_lauf(zahlen, dateien):
    """„gebaut" NUR nach einem grünen Bau (F-PK-34). Bis PK-05 schrieb
    `pruefen.sh` „gebaut", sobald der Ordner berührt war — auch nach einem
    roten Bau oder einem, der mangels SDK nie lief."""
    aus = {}
    for flaeche, (_, probe) in FLAECHEN.items():
        if not flaeche_beruehrt(flaeche, dateien):
            aus[flaeche] = 'nicht-beruehrt'
        elif probe not in zahlen or zahlen[probe] == 'nicht-gemessen':
            aus[flaeche] = 'nicht-gemessen'
        else:
            aus[flaeche] = 'gebaut' if zahlen[probe] == '0' else 'rot'
    return aus


def schreiben(args):
    zahlen = dict(p.split('=', 1) for p in args.zahl)
    flaechen = flaechen_aus_lauf(zahlen, beruehrte(args.basis)) if args.basis else {}
    flaechen.update(dict(p.split('=', 1) for p in args.flaeche))
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
    """Welche Stufe verlangt der Unterschied — die Fassung in
    server/version.php und die Stufenregeln (E-P5c-88: eine neue Migration
    heißt haupt)? Gelesen in `auswahl.py` — eine Stelle für beide. UNLESBAR,
    wenn eine Seite keine Fassung trägt."""
    return stufe_aus_version(basis, commit)[0]


def beruehrt(basis, commit):
    dateien = git('diff', '--name-only', f'{basis}...{commit}').splitlines()
    return [d for d in dateien if d]


def pruefen(bericht, basis=None, commit='HEAD', riegel=None, dateien=None, baum=None,
            stufe_verlangt=None, riegel_namen=None):
    """Die fünf roten Lagen. Gibt eine Liste von Beanstandungen zurück."""
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

    # (3) Berührte Fläche ohne grünen Bau. Verlangt ist „gebaut" — nicht
    # „alles außer nicht berührt": Ein roter oder nie gelaufener Bau ist
    # kein Bau (F-PK-34).
    liste = dateien if dateien is not None else (beruehrt(basis, commit) if basis else [])
    for flaeche, (ordner, _) in FLAECHEN.items():
        wert = bericht['werte'].get(flaeche)
        if flaeche_beruehrt(flaeche, liste) and wert != 'gebaut':
            wo = ' oder '.join(o + '/' for o in ordner)
            schlecht.append(
                f"„{flaeche}={wert or '(fehlt)'}\", aber {wo} ist berührt — ein grüner Bau fehlt.")

    # (4) Billiger Riegel mit anderer Zahl
    for name, zahl in (riegel or {}).items():
        gemeldet = bericht['werte'].get(name)
        if gemeldet is None:
            schlecht.append(f"Riegel „{name}\" fehlt im Bericht (im Tor gemessen: {zahl}).")
        elif gemeldet != str(zahl):
            schlecht.append(
                f"Riegel „{name}\": Bericht {gemeldet}, im Tor gemessen {zahl}.")

    # (5) Eine rote oder nicht gemessene Probe im Bericht (E-PK-44, -46). Der
    # Prüfstand druckt den Bericht auch nach einem roten Lauf; ohne diese Lage
    # käme ein Commit mit „kreislauf-edbak=1" durch, solange die billigen
    # Riegel stimmen. Überspringen ist rot (E-KH-12). Riegel und Flächen haben
    # ihre eigene Lage.
    eigene = set(riegel_namen if riegel_namen is not None else lade_ablauf()['riegel']['proben'])
    eigene |= set(riegel or {}) | set(FLAECHEN)
    for name, wert in bericht['werte'].items():
        if name in eigene or wert == '0':
            continue
        if wert == 'nicht-gemessen':
            schlecht.append(f"Probe „{name}\" ist nicht gemessen — Überspringen ist rot (E-KH-12).")
        else:
            schlecht.append(f"Probe „{name}\" meldet {wert} — ein roter Lauf ist kein Nachweis.")
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
    riegel = dict(p.split('=', 1) for p in args.riegel)
    schlecht = []
    if args.alle_riegel:
        # JEDER RIEGEL AUS pruefablauf.json LÄUFT AUCH IM TOR — sonst gäbe es zwei
        # Listen, und die im Tor würde still kürzer.
        for r in lade_ablauf()['riegel']['proben']:
            if r not in riegel:
                schlecht.append(f'Riegel „{r}" steht in pruefablauf.json, läuft aber nicht im Tor.')
    schlecht += pruefen(bericht, basis=args.basis, commit=args.commit, riegel=riegel)
    if schlecht:
        melde('Der Prüfbericht trägt nicht:')
        for s in schlecht:
            melde(f'  ! {s}')
        melde(WEG)
        return 1
    melde(f"Prüfbericht in Ordnung: Stufe {bericht['stufe']}, Baum {bericht['baum']}, "
          f"Konfiguration {bericht['konfiguration']}, {len(bericht['werte'])} Zahlen.")
    return 0


# ------------------------------------------------------------ Selbstprobe

def selbstprobe():
    """Fünf rote Lagen und eine grüne — jede einzeln, mit Namen.

    EINE SELBSTPROBE, DIE NUR DEN GRÜNEN FALL FÄHRT, BELEGT NICHTS: Sie
    würde auch dann grün melden, wenn `pruefen()` immer eine leere Liste
    zurückgibt. Deshalb ist jede rote Lage ein eigener Fall, und der Lauf
    ist erst grün, wenn jede von ihnen WIRKLICH rot wird.
    """
    guter = ('Prüfstand: neben · Baum abc1234 · Konfiguration web\n'
             '  syntax-php=php:486/0  wortliste=0  vollstaendigkeit=0\n'
             '  spurprobe=0  kreislauf-edbak=0  bilderlauf=0\n'
             '  handy=nicht-beruehrt  uhr=nicht-beruehrt\n')
    gebaut = guter.replace('handy=nicht-beruehrt', 'handy=gebaut') + '  android-bau=0\n'
    namen = ['syntax-php', 'wortliste', 'vollstaendigkeit']
    faelle = []

    faelle.append(('grün — Bericht passt zu allem',
                   zerlegen(guter), dict(baum='abc1234', dateien=['server/index.php'],
                                         riegel={'vollstaendigkeit': '0'}, riegel_namen=namen), 0))
    faelle.append(('grün (3) — Handy berührt und grün gebaut',
                   zerlegen(gebaut), dict(baum='abc1234', dateien=['android/handy/src/Main.kt'],
                                          riegel={}, riegel_namen=namen), 0))
    faelle.append(('rot (1) — Baum-Hash passt nicht',
                   zerlegen(guter), dict(baum='9999999', dateien=[], riegel={}, riegel_namen=namen), 1))
    faelle.append(('rot (2) — Stufe kleiner als verlangt',
                   zerlegen(guter.replace('neben', 'klein')),
                   dict(baum='abc1234', dateien=[], riegel={}, stufe_verlangt='haupt',
                        riegel_namen=namen), 1))
    faelle.append(('rot (3) — berührte Fläche als „nicht berührt" gemeldet',
                   zerlegen(guter), dict(baum='abc1234', dateien=['android/handy/src/Main.kt'],
                                         riegel={}, riegel_namen=namen), 1))
    faelle.append(('rot (3b) — die Uhr berührt und als „nicht berührt" gemeldet',
                   zerlegen(guter), dict(baum='abc1234', dateien=['watch/source/App.mc'],
                                         riegel={}, riegel_namen=namen), 1))
    faelle.append(('rot (3c) — Handy berührt, Bau rot',
                   zerlegen(gebaut.replace('handy=gebaut', 'handy=rot')),
                   dict(baum='abc1234', dateien=['android/handy/src/Main.kt'], riegel={},
                        riegel_namen=namen), 1))
    faelle.append(('rot (3d) — Uhr-Prüfstand berührt, Bau nicht gemessen',
                   zerlegen(guter.replace('uhr=nicht-beruehrt', 'uhr=nicht-gemessen')),
                   dict(baum='abc1234', dateien=['tools/uhr-pruefstand/pruefstand.sh'], riegel={},
                        riegel_namen=namen), 1))
    faelle.append(('rot (4) — Riegel meldet eine andere Zahl',
                   zerlegen(guter), dict(baum='abc1234', dateien=[],
                                         riegel={'vollstaendigkeit': '1'}, riegel_namen=namen), 1))
    faelle.append(('rot (5) — eine rote Probe im Bericht',
                   zerlegen(guter.replace('kreislauf-edbak=0', 'kreislauf-edbak=1')),
                   dict(baum='abc1234', dateien=[], riegel={}, riegel_namen=namen), 1))
    faelle.append(('rot (5b) — eine nicht gemessene Probe im Bericht',
                   zerlegen(guter.replace('spurprobe=0', 'spurprobe=nicht-gemessen')),
                   dict(baum='abc1234', dateien=[], riegel={}, riegel_namen=namen), 1))
    faelle.append(('rot (2b) — Versionsstufe nicht lesbar',
                   zerlegen(guter), dict(baum='abc1234', dateien=[], riegel={},
                                         stufe_verlangt=UNLESBAR, riegel_namen=namen), 1))
    faelle.append(('rot (0) — gar kein Bericht in der Nachricht',
                   zerlegen('Ein Commit ganz ohne Block.\n'),
                   dict(baum='abc1234', dateien=[], riegel={}, riegel_namen=namen), 1))

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
    gruen = sum(1 for f in faelle if f[3] == 0)
    melde(f'{len(faelle)} Lagen, {fehl} Fehlschlaege.  '
          f'({len(faelle) - gruen} rote, {gruen} gruene)')
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
    for r in a.get('stufenregeln', []):
        melde()
        melde(f"**Stufenregel `{r['id']}`:** eine neue Kennung in `{r['datei']}` "
              f"heißt mindestens **{r['stufe']}** — {r['anlass']}")
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
    s.add_argument('--basis', help='Flächen aus Berührung und Bau ableiten (F-PK-34)')
    s.set_defaults(fn=schreiben)

    l = u.add_parser('lesen')
    l.add_argument('--commit', default='HEAD')
    l.add_argument('--basis')
    l.add_argument('--datei')
    l.add_argument('--selbstprobe', action='store_true')
    l.add_argument('--riegel', action='append', default=[], metavar='name=wert',
                   help='eine Zahl, die das Tor selbst gemessen hat (Lage 4)')
    l.add_argument('--alle-riegel', action='store_true',
                   help='jeder Riegel aus pruefablauf.json muss als --riegel kommen')
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
