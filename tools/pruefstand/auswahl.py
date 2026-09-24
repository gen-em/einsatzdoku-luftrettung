#!/usr/bin/env python3
"""Welche Proben gehören zu dieser Berührung? — liest pruefablauf.json.

    python3 tools/pruefstand/auswahl.py --stufe klein --basis origin/main
    python3 tools/pruefstand/auswahl.py --stufe neben --datei server/spur_lib.php
    python3 tools/pruefstand/auswahl.py --abdeckung
    python3 tools/pruefstand/auswahl.py --stufe-ermitteln --basis origin/main
    python3 tools/pruefstand/auswahl.py --selbstprobe

Rückgabewert 0 = in Ordnung · 1 = Befund (bei `--abdeckung`: Dateien ohne
eigene Probe) · 2 = die Probe selbst kam nicht zum Laufen.

EINE ZUORDNUNG, NICHT ZWANZIG. Bis PK-03 stand in jeder LIESMICH, wann ihr
Werkzeug zu fahren sei — und in `CLAUDE.md` 6 eine zweite, kürzere Liste.
Welche Probe zu einer Änderung gehörte, wusste man oder man wusste es nicht.

Anlass: Nr. 217 (ein Kettenschritt, der zur Schnittstelle nicht passte).
"""
import argparse
import fnmatch
import json
import os
import re
import subprocess
import sys

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
ABLAUF = os.path.join(HIER, 'pruefablauf.json')
STUFEN = ['klein', 'neben', 'haupt']
AUFFANG = 'grundlage'


def melde(t=''):
    print(t, flush=True)


def git(*args):
    return subprocess.run(['git', '-C', WURZEL, *args],
                          capture_output=True, text=True).stdout


def lade():
    with open(ABLAUF, encoding='utf-8') as f:
        return json.load(f)


def passt(pfad, muster):
    """Ein Muster trifft einen Pfad. `**` trifft auch über Verzeichnisse."""
    for m in muster:
        if m.endswith('/**'):
            if pfad.startswith(m[:-2]):
                return True
        elif fnmatch.fnmatch(pfad, m):
            return True
        elif '**' in m and fnmatch.fnmatch(pfad, m.replace('**', '*')):
            return True
    return False


def treffer(dateien, stufe, a):
    """Welche Muster greifen — und welche Proben folgen daraus."""
    grenze = STUFEN.index(stufe)
    muster, proben = [], []
    for m in a['muster']:
        if STUFEN.index(m['ab']) > grenze:
            continue
        if any(passt(d, m['pfade']) for d in dateien):
            muster.append(m['id'])
            for p in m['proben']:
                if p not in proben:
                    proben.append(p)
    return muster, mit_voraussetzungen(proben, a)


def mit_voraussetzungen(proben, a):
    """`nach` in pruefablauf.json: Eine Probe, die auf eine andere angewiesen
    ist, bekommt sie mit — und läuft NACH ihr. Sonst hinge ihr Grün an der
    Reihenfolge der Muster, und `--datei` wählte sie ohne ihre Voraussetzung
    aus (F-RP-08: die Wegprobe braucht das Konto des csv-Kreislaufs)."""
    aus = []

    def rein(p, kette=()):
        if p in kette:
            raise ValueError(f"`nach` im Kreis: {' -> '.join(kette + (p,))}")
        for v in a['proben'].get(p, {}).get('nach', []):
            rein(v, kette + (p,))
        if p not in aus:
            aus.append(p)
    for p in proben:
        rein(p)
    return aus


# DIE FASSUNG STEHT EINMAL GELESEN — hier; `bericht.py` holt sie von hier.
# Bis PK-05 las jede der beiden Dateien selbst, beide mit demselben Muster
# `WEB_VERSION', '…'` (der Schreibweise von `define()`). Die Datei trägt seit
# Web 20.8.0 `const WEB_VERSION = '…';` — das Muster traf nie, die Stufe hieß
# still „klein", und die rote Lage „Stufe zu klein" konnte nie anschlagen
# (F-PK-30). Deshalb: Wer die Fassung nicht lesen kann, sagt es und hört auf.
FASSUNG_RE = re.compile(r"WEB_VERSION'?\s*[,=]\s*'([0-9]+)\.([0-9]+)\.([0-9]+)'")
UNLESBAR = 'unlesbar'


def fassung_aus_text(t):
    m = FASSUNG_RE.search(t or '')
    return tuple(int(x) for x in m.groups()) if m else None


def fassung(ref):
    return fassung_aus_text(git('show', f'{ref}:server/version.php'))


def stufe_aus_fassungen(alt, neu):
    """(stufe, grund) — stufe ist UNLESBAR, wenn eine Seite fehlt."""
    if not alt or not neu:
        return UNLESBAR, 'Fassung in server/version.php nicht lesbar'
    txt = f"{'.'.join(map(str, alt))} -> {'.'.join(map(str, neu))}"
    if alt == neu:
        return 'klein', 'kein Versionssprung'
    if neu[0] != alt[0]:
        return 'haupt', f'Hauptstufe {txt}'
    if neu[1] != alt[1]:
        return 'neben', f'Nebenstufe {txt}'
    return 'klein', f'Korrekturstufe {txt}'


def fassung_arbeitsbestand():
    try:
        with open(os.path.join(WURZEL, 'server', 'version.php'), encoding='utf-8') as f:
            return fassung_aus_text(f.read())
    except OSError:
        return None


def stufe_aus_version(basis, commit=None):
    """commit=None heißt: der ARBEITSBESTAND, wie beim Baum-Hash. Gemessen wird
    vor dem Commit; wer die Fassung aus HEAD liest, sieht den Sprung nicht, der
    erst mit dem Bericht committet wird — und das Tor meldet „Stufe zu klein"
    (F-PK-33)."""
    neu = fassung_arbeitsbestand() if commit is None else fassung(commit)
    return stufe_aus_fassungen(fassung(basis), neu)


def beruehrte(basis):
    """Was diese Arbeit gegenüber `basis` ändert: der Zweig seit dem gemeinsamen
    Vorfahren, dazu der Arbeitsbestand — aber nur Dateien, die im
    Arbeitsbestand WIRKLICH anders sind als in `basis`, und neue Dateien.

    WARUM DER ABGLEICH MIT `basis`: Mitten in einem Merge von `origin/main`
    (Pruefablauf.md 5.3) zeigt `git status` jede Datei, die `main` mitbringt,
    als geändert gegenüber HEAD. Berührt hat sie diese Arbeit nicht; im Tor
    zählt der Unterschied gegen `main` (F-PK-39)."""
    roh = git('diff', '--name-only', f'{basis}...HEAD') + git('status', '--porcelain')
    neu, aus = set(), set()
    for z in roh.splitlines():
        z = z.strip()
        if not z:
            continue
        if z.startswith('??'):
            neu.add(z[2:].strip())
            continue
        if z[:2].strip() in ('M', 'A', 'D', 'R', 'MM', 'AM'):
            z = z.split(None, 1)[-1]
        aus.add(z.split(' -> ')[-1])
    anders = set(git('diff', '--name-only', basis).splitlines())
    return sorted(p for p in (aus & anders) | neu if p)


def abdeckung(a):
    """Jede versionierte Datei unter server/ trifft ein Muster — und welche
    trifft NUR das Auffangmuster, hat also keine eigene Probe?"""
    dateien = [d for d in git('ls-files', 'server').splitlines()
               if d and '/vendor/' not in d]
    ohne, nur_auffang = [], []
    for d in dateien:
        ids = [m['id'] for m in a['muster'] if passt(d, m['pfade'])]
        if not ids:
            ohne.append(d)
        elif set(ids) <= {AUFFANG, 'nebenstufe', 'mengen'}:
            nur_auffang.append(d)
    melde(f'Dateien unter server/ (versioniert, ohne vendor): {len(dateien)}')
    melde(f'  ohne jedes Muster:                 {len(ohne)}')
    melde(f'  nur das Auffangmuster, keine eigene Probe: {len(nur_auffang)}')
    for d in ohne[:20]:
        melde(f'    ! {d}')
    if nur_auffang:
        melde('  (die laufen durch die billigen Riegel und den Bilderlauf, '
              'haben aber keine eigene Probe — das ist eine Aussage, kein Fehler)')
    return 1 if ohne else 0


def selbstprobe(a):
    """Die Musterlogik gegen Fälle, bei denen sie schiefgehen kann."""
    faelle = [
        ('server/spur_lib.php trifft „spur"',            'server/spur_lib.php', 'spur', True),
        ('server/index.php trifft NICHT „spur"',         'server/index.php',    'spur', False),
        ('android/handy/x.kt trifft „android"',          'android/handy/x.kt',  'android', True),
        ('server/index.php trifft NICHT „android"',      'server/index.php',    'android', False),
        ('server/assets/style.css trifft „stylesheet"',  'server/assets/style.css', 'stylesheet', True),
        ('server/assets/unlock.js trifft „krypto"',      'server/assets/unlock.js', 'krypto', True),
        ('watch/source/App.mc trifft „uhr"',             'watch/source/App.mc', 'uhr', True),
        # Ein Glob gegen eine VERSIONIERTE Datei. Bis BR-05 stand hier
        # server/api/spurteil.php gegen das Muster server/api/spur*.php — eine
        # erfundene Datei gegen einen Pfad, der nie eine echte traf; die Probe
        # war grün, das Muster griff nie (Gegenprobe des Bestandsriegels).
        ('server/adminbackup_lib.php trifft „backup"',   'server/adminbackup_lib.php', 'backup', True),
        ('jede server-Datei trifft das Auffangmuster',   'server/beliebig.php', AUFFANG, True),
    ]
    nach_id = {m['id']: m for m in a['muster']}
    fehl = 0
    for name, pfad, mid, erwartet in faelle:
        ist = passt(pfad, nach_id[mid]['pfade'])
        ok = ist == erwartet
        melde(f"  [{'ok  ' if ok else 'FEHL'}] {name}")
        fehl += 0 if ok else 1

    # Die Stufengrenzen: „nebenstufe" erst ab neben, „mengen" erst ab haupt.
    _, klein = treffer(['server/index.php'], 'klein', a)
    _, neben = treffer(['server/index.php'], 'neben', a)
    _, haupt = treffer(['server/index.php'], 'haupt', a)
    grenzen = [('bei klein kein Kreislauf',            'kreislauf-csv' not in klein),
               ('bei neben beide Kreisläufe',          {'kreislauf-csv', 'kreislauf-edbak'} <= set(neben)),
               ('bei neben die Bedienprobe',           'bedienprobe' in neben),
               ('bei neben kein Messstand',            'messstand' not in neben),
               ('bei haupt Messstand und Schemaprobe', {'messstand', 'schemaprobe'} <= set(haupt)),
               ('bei haupt alles von neben',           set(neben) <= set(haupt))]

    # `nach`: Die Voraussetzung kommt mit und läuft davor (RP-01).
    probe_a = {'proben': {'x': {}, 'y': {'nach': ['x']}, 'z': {'nach': ['y']}}}
    grenzen += [
        ('nach: die Voraussetzung kommt mit',   mit_voraussetzungen(['y'], probe_a) == ['x', 'y']),
        ('nach: sie steht davor, auch später genannt',
         mit_voraussetzungen(['y', 'x'], probe_a) == ['x', 'y']),
        ('nach: über zwei Stufen',              mit_voraussetzungen(['z'], probe_a) == ['x', 'y', 'z']),
        ('nach: die Wegprobe läuft nach dem edbak-Kreislauf',
         'spaltenregister-wegprobe' in neben
         and neben.index('kreislauf-edbak') < neben.index('spaltenregister-wegprobe'))]

    # Die Fassung — gegen die ECHTE Schreibweise der Datei, nicht gegen eine
    # ausgedachte. Genau das fehlte, als das Muster nie traf (F-PK-30).
    echt = open(os.path.join(WURZEL, 'server', 'version.php'), encoding='utf-8').read()
    grenzen += [
        ('die Fassung dieser Datei ist lesbar',  fassung_aus_text(echt) is not None),
        ("const WEB_VERSION = '1.2.3'",          fassung_aus_text("const WEB_VERSION = '1.2.3';") == (1, 2, 3)),
        ("define('WEB_VERSION', '1.2.3')",       fassung_aus_text("define('WEB_VERSION', '1.2.3');") == (1, 2, 3)),
        ('20.37.2 -> 20.37.3 heißt klein',       stufe_aus_fassungen((20, 37, 2), (20, 37, 3))[0] == 'klein'),
        ('20.36.0 -> 20.37.0 heißt neben',       stufe_aus_fassungen((20, 36, 0), (20, 37, 0))[0] == 'neben'),
        ('20.37.3 -> 21.0.0 heißt haupt',        stufe_aus_fassungen((20, 37, 3), (21, 0, 0))[0] == 'haupt'),
        ('gleiche Fassung heißt klein',          stufe_aus_fassungen((1, 2, 3), (1, 2, 3))[0] == 'klein'),
        ('unlesbar heißt unlesbar, nicht klein', stufe_aus_fassungen(None, (1, 2, 3))[0] == UNLESBAR),
    ]
    for name, bed in grenzen:
        melde(f"  [{'ok  ' if bed else 'FEHL'}] {name}")
        fehl += 0 if bed else 1

    melde()
    melde(f'{len(faelle) + len(grenzen)} Lagen, {fehl} Fehlschlaege.')
    return 1 if fehl else 0


def main():
    p = argparse.ArgumentParser(description=__doc__)
    p.add_argument('--stufe', choices=STUFEN)
    p.add_argument('--basis', default='origin/main')
    p.add_argument('--datei', action='append', default=[])
    p.add_argument('--abdeckung', action='store_true')
    p.add_argument('--stufe-ermitteln', action='store_true')
    p.add_argument('--selbstprobe', action='store_true')
    p.add_argument('--nur-proben', action='store_true',
                   help='nur die Probennamen, einer je Zeile — für pruefen.sh')
    args = p.parse_args()

    try:
        a = lade()
    except FileNotFoundError:
        melde(f'pruefablauf.json fehlt: {ABLAUF}')
        return 2

    if args.selbstprobe:
        return selbstprobe(a)
    if args.abdeckung:
        return abdeckung(a)
    if args.stufe_ermitteln:
        stufe, grund = stufe_aus_version(args.basis)
        melde(f'{stufe}\t{grund}')
        return 2 if stufe == UNLESBAR else 0

    stufe = args.stufe
    if not stufe:
        stufe, grund = stufe_aus_version(args.basis)
        if stufe == UNLESBAR:
            melde(f'Die Stufe lässt sich nicht bestimmen: {grund}.')
            return 2
    dateien = args.datei or beruehrte(args.basis)
    muster, proben = treffer(dateien, stufe, a)
    riegel = a['riegel']['proben']

    if args.nur_proben:
        for n in riegel + [x for x in proben if x not in riegel]:
            melde(n)
        return 0

    melde(f'Stufe {stufe} · {len(dateien)} berührte Dateien · '
          f'{len(muster)} Muster · {len(proben)} Proben (plus {len(riegel)} Riegel)')
    melde()
    melde('Muster: ' + (', '.join(muster) or '—'))
    melde('Riegel: ' + ', '.join(riegel))
    melde('Proben: ' + (', '.join(proben) or '—'))
    return 0


if __name__ == '__main__':
    sys.exit(main())
