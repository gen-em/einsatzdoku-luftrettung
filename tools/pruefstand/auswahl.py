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
    return muster, proben


def stufe_aus_version(basis):
    def fassung(ref):
        t = git('show', f'{ref}:server/version.php')
        m = re.search(r"WEB_VERSION'?\s*,\s*'([0-9]+)\.([0-9]+)\.([0-9]+)", t)
        return tuple(int(x) for x in m.groups()) if m else None
    alt, neu = fassung(basis), fassung('HEAD')
    if not alt or not neu or alt == neu:
        return 'klein', 'kein Versionssprung' if alt == neu else 'Fassung nicht lesbar'
    if neu[0] != alt[0]:
        return 'haupt', f"Hauptstufe {'.'.join(map(str, alt))} -> {'.'.join(map(str, neu))}"
    if neu[1] != alt[1]:
        return 'neben', f"Nebenstufe {'.'.join(map(str, alt))} -> {'.'.join(map(str, neu))}"
    return 'klein', f"Korrekturstufe {'.'.join(map(str, alt))} -> {'.'.join(map(str, neu))}"


def beruehrte(basis):
    roh = git('diff', '--name-only', f'{basis}...HEAD') + git('status', '--porcelain')
    aus = set()
    for z in roh.splitlines():
        z = z.strip()
        if not z:
            continue
        if z[:2].strip() in ('M', 'A', 'D', 'R', '??', 'MM', 'AM'):
            z = z.split(None, 1)[-1]
        aus.add(z.split(' -> ')[-1])
    return sorted(p for p in aus if p)


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
        elif ids == [AUFFANG] or set(ids) == {AUFFANG, 'mengen'}:
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
        ('server/api/spurteil.php trifft „spur"',        'server/api/spurteil.php', 'spur', True),
        ('jede server-Datei trifft das Auffangmuster',   'server/beliebig.php', AUFFANG, True),
    ]
    nach_id = {m['id']: m for m in a['muster']}
    fehl = 0
    for name, pfad, mid, erwartet in faelle:
        ist = passt(pfad, nach_id[mid]['pfade'])
        ok = ist == erwartet
        melde(f"  [{'ok  ' if ok else 'FEHL'}] {name}")
        fehl += 0 if ok else 1

    # Die Stufengrenze: „mengen" darf bei klein NICHT greifen.
    _, proben_klein = treffer(['server/index.php'], 'klein', a)
    _, proben_haupt = treffer(['server/index.php'], 'haupt', a)
    for name, bed in [('„mengen" greift bei klein nicht', 'messstand' not in proben_klein),
                      ('„mengen" greift bei haupt',       'messstand' in proben_haupt)]:
        melde(f"  [{'ok  ' if bed else 'FEHL'}] {name}")
        fehl += 0 if bed else 1

    melde()
    melde(f'{len(faelle) + 2} Lagen, {fehl} Fehlschlaege.')
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
        return 0

    stufe = args.stufe or stufe_aus_version(args.basis)[0]
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
