#!/usr/bin/env python3
"""Legt dieser Zweig eine Backlog-Nummer an, die ein anderer Zweig schon trägt?

    python3 tools/steuerung/nummern.py                # holt die Zweige und misst
    python3 tools/steuerung/nummern.py --ohne-holen   # misst gegen die Zweige, die schon da sind
    python3 tools/steuerung/nummern.py --selbstprobe

Rückgabewert 0 = keine Nummer auf zwei Zweigen · 1 = eine Nummer steht
doppelt · 2 = nicht gelaufen (kein Git, kein `origin/main`, Holen
gescheitert) — nie still grün.

WOFÜR (Nr. 339, E-BV-19). Zweimal an einem Tag ist eine Nummer aus der
Spanne von BV auf `main` kollidiert (F-BV-14, F-BV-18), ohne dass jemand
eine sichtbare Regel verletzt hätte: Der Kopf des Backlogs verlangt, die
Spanne vor dem ersten Push einzutragen (E-SD-21) — eingetragen wird sie aber
auf dem eigenen Zweig, und „auf allen offenen Zweigen nachsehen" ist ein
Blick, den kein Werkzeug abnahm. Stufe 1 sieht die anderen Zweige nicht;
deshalb läuft dieses Werkzeug örtlich, im Prüfstand.

WIE ES ZÄHLT. Eine Nummer ist die Zahl am Anfang einer Eintragszeile
(`NNN. **Titel.**`) in `docs/Backlog.md` ODER `docs/Backlog-Erledigt.md` —
ein Punkt, der nach Erledigt gewandert ist, bleibt vergeben. NEU ist eine
Nummer, die am gemeinsamen Vorfahren mit `origin/main` in keiner der beiden
Dateien stand: für den Arbeitsbaum (so, wie er auf der Platte liegt, auch
ungespeichert) gegen `merge-base(HEAD, origin/main)`, für `origin/main`
gegen denselben Vorfahren, für jeden anderen Remote-Zweig gegen
`merge-base(zweig, origin/main)`. Ein gemergter Zweig hat so nichts Neues,
und der eigene Remote-Zweig zählt nicht mit. Rot ist jede Nummer, die der
Arbeitsbaum neu anlegt und ein anderer auch.

WAS ES NICHT KANN. Zweige, die nur auf einem anderen Rechner liegen, und
Pull Requests aus Forks sieht es nicht; eine Nummer, die jemand erst morgen
vergibt, auch nicht — die Spanne im Kopf von `docs/Backlog.md` bleibt die
Regel, dieses Werkzeug ist ihr Nachweis.
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
DATEIEN = ('docs/Backlog.md', 'docs/Backlog-Erledigt.md')
EINTRAG = re.compile(r'^(\d+)\. \*\*', re.M)
HAUPT = 'origin/main'


class NichtGelaufen(Exception):
    pass


def git(wurzel, *args, pruefen=True):
    r = subprocess.run(['git', '-C', wurzel, *args], capture_output=True, text=True,
                       encoding='utf-8', errors='replace')
    if pruefen and r.returncode != 0:
        raise NichtGelaufen(f'git {" ".join(args)}: {(r.stderr or r.stdout).strip()[:160]}')
    return r


def nummern_text(text):
    return {int(n) for n in EINTRAG.findall(text)}


def nummern_ref(wurzel, ref):
    """Die Nummern beider Dateien an einem Stand; fehlt eine Datei dort, zählt sie leer."""
    aus = set()
    for d in DATEIEN:
        r = git(wurzel, 'show', f'{ref}:{d}', pruefen=False)
        if r.returncode == 0:
            aus |= nummern_text(r.stdout)
    return aus


def nummern_baum(wurzel):
    aus = set()
    for d in DATEIEN:
        p = os.path.join(wurzel, *d.split('/'))
        if os.path.isfile(p):
            with open(p, encoding='utf-8') as f:
                aus |= nummern_text(f.read())
    return aus


def messen(wurzel, holen=True):
    """(Überschneidungen [(Nummer, Zweig)], Zahlen). Wirft NichtGelaufen."""
    if git(wurzel, 'rev-parse', '--is-inside-work-tree', pruefen=False).returncode != 0:
        raise NichtGelaufen(f'{wurzel} ist kein Git-Arbeitsbaum')
    if holen:
        git(wurzel, 'fetch', '--prune', '--quiet', 'origin')
    if git(wurzel, 'rev-parse', '--verify', '--quiet', HAUPT, pruefen=False).returncode != 0:
        raise NichtGelaufen(f'{HAUPT} fehlt — ohne ihn gibt es keinen gemeinsamen Vorfahren')
    basis = git(wurzel, 'merge-base', 'HEAD', HAUPT).stdout.strip()
    alt = nummern_ref(wurzel, basis)
    meine = nummern_baum(wurzel) - alt
    eigen = git(wurzel, 'rev-parse', '--abbrev-ref', 'HEAD').stdout.strip()
    fremde = {HAUPT: nummern_ref(wurzel, HAUPT) - alt}
    refs = git(wurzel, 'for-each-ref', '--format=%(refname:short)', 'refs/remotes/origin/').stdout.split()
    for ref in refs:
        if ref in (HAUPT, 'origin/HEAD', 'origin', f'origin/{eigen}'):
            continue
        mb = git(wurzel, 'merge-base', ref, HAUPT, pruefen=False).stdout.strip()
        if not mb:
            continue                            # ohne gemeinsamen Vorfahren kein Vergleich
        fremde[ref] = nummern_ref(wurzel, ref) - nummern_ref(wurzel, mb)
    doppelt = sorted((n, ref) for ref, neu in fremde.items() for n in meine & neu)
    return doppelt, {'meine': sorted(meine), 'zweige': len(fremde),
                     'fremd': sum(len(v) for v in fremde.values())}


def bericht(doppelt, zahlen):
    meine = zahlen['meine']
    print(f"Neue Nummern dieses Arbeitsbaums: {len(meine)}"
          + (f" ({', '.join(str(n) for n in meine)})" if meine else ''))
    print(f"Verglichen mit {zahlen['zweige']} Zweigen ({HAUPT} und die übrigen Remote-Zweige), "
          f"die zusammen {zahlen['fremd']} Nummern neu anlegen")
    for n, ref in doppelt:
        print(f'  ! Nr. {n} legt auch {ref} an — eine der beiden muss eine andere Nummer '
              f'nehmen (Spanne im Kopf von docs/Backlog.md, E-SD-21)')
    print(f'{len(doppelt)} Überschneidungen')
    return 1 if doppelt else 0


# ------------------------------------------------------------ Selbstprobe

def _schreibe(wurzel, backlog, erledigt=''):
    os.makedirs(os.path.join(wurzel, 'docs'), exist_ok=True)
    for d, inhalt in zip(DATEIEN, (backlog, erledigt)):
        with open(os.path.join(wurzel, *d.split('/')), 'w', encoding='utf-8') as f:
            f.write(inhalt)


def _commit(wurzel, text):
    git(wurzel, 'add', '-A')
    git(wurzel, '-c', 'user.name=Probe', '-c', 'user.email=probe@example.invalid',
        'commit', '-q', '-m', text)


def _anlage(ordner):
    """Ein Ursprung mit main (Nr. 1, Erledigt Nr. 2), einem offenen Zweig
    `fremd` (+5), einem gemergten Zweig `gemergt` (+7) und main danach (+8);
    davon ein Klon `arbeit` auf Zweig `meins`, abgezweigt VOR 7 und 8."""
    q = os.path.join(ordner, 'quelle')
    os.makedirs(q)
    git(q, 'init', '-q', '-b', 'main')
    _schreibe(q, '1. **Eins.**\n', '2. **Zwei.**\n')
    _commit(q, 'eins')
    a = os.path.join(ordner, 'arbeit')
    git(ordner, 'clone', '-q', q, a)
    git(a, 'checkout', '-q', '-b', 'meins')
    git(q, 'checkout', '-q', '-b', 'fremd')
    _schreibe(q, '1. **Eins.**\n\n5. **Fünf.**\n', '2. **Zwei.**\n')
    _commit(q, 'fünf')
    git(q, 'checkout', '-q', '-b', 'gemergt', 'main')
    _schreibe(q, '1. **Eins.**\n\n7. **Sieben.**\n', '2. **Zwei.**\n')
    _commit(q, 'sieben')
    git(q, 'checkout', '-q', 'main')
    git(q, 'merge', '-q', '--ff-only', 'gemergt')
    _schreibe(q, '1. **Eins.**\n\n7. **Sieben.**\n\n8. **Acht.**\n', '2. **Zwei.**\n')
    _commit(q, 'acht')
    return a


def _meins(a, *nummern, erledigt=False, gepusht=False):
    zeilen = '1. **Eins.**\n' + ''.join(f'\n{n}. **Neu {n}.**\n' for n in nummern)
    if erledigt:
        _schreibe(a, '1. **Eins.**\n', '2. **Zwei.**\n' + ''.join(f'\n{n}. **Neu {n}.**\n' for n in nummern))
    else:
        _schreibe(a, zeilen, '2. **Zwei.**\n')
    if gepusht:
        _commit(a, 'meins')
        git(a, 'push', '-q', 'origin', 'meins')


FAELLE = [
    # (Name, erwartete Überschneidungen als {(Nummer, Zweig)}, Eingriff)
    ('GEGENPROBE: eine Nummer, die niemand sonst trägt', set(), lambda a: _meins(a, 6)),
    ('eine Nummer, die ein offener Zweig trägt', {(5, 'origin/fremd')}, lambda a: _meins(a, 5)),
    ('dieselbe Nummer, aber schon in Backlog-Erledigt.md', {(5, 'origin/fremd')},
     lambda a: _meins(a, 5, erledigt=True)),
    ('eine Nummer, die main nach dem Abzweigen vergeben hat', {(8, 'origin/main')}, lambda a: _meins(a, 8)),
    ('eine Nummer eines gemergten Zweigs — sie zählt als main, nicht zweimal', {(7, 'origin/main')},
     lambda a: _meins(a, 7)),
    ('GEGENPROBE: der eigene Zweig, gepusht, kollidiert nicht mit sich', set(),
     lambda a: _meins(a, 6, gepusht=True)),
    ('zwei Nummern, zwei Zweige', {(5, 'origin/fremd'), (8, 'origin/main')}, lambda a: _meins(a, 5, 6, 8)),
]


def selbstprobe():
    print('Selbstprobe des Nummernriegels')
    if not shutil.which('git'):
        print('git fehlt — nichts gemessen.')
        return 2
    fehl = 0
    for name, soll, eingriff in FAELLE:
        ordner = tempfile.mkdtemp(prefix='nummern-')
        try:
            a = _anlage(ordner)
            eingriff(a)
            doppelt, _ = messen(a)
            ist = set(doppelt)
        except NichtGelaufen as e:
            ist = {('nicht gelaufen', str(e)[:80])}
        finally:
            shutil.rmtree(ordner, ignore_errors=True)
        ok = ist == soll
        fehl += not ok
        print(f"  [{'ok  ' if ok else 'FEHL'}] {name}")
        if not ok:
            print(f'         erwartet: {sorted(soll)} · gemessen: {sorted(ist)}')
    # Ohne origin/main ist das nicht grün, sondern „nicht gelaufen"
    ordner = tempfile.mkdtemp(prefix='nummern-')
    try:
        git(ordner, 'init', '-q', '-b', 'main')
        _schreibe(ordner, '1. **Eins.**\n')
        _commit(ordner, 'eins')
        try:
            messen(ordner, holen=False)
            ok = False
        except NichtGelaufen:
            ok = True
    finally:
        shutil.rmtree(ordner, ignore_errors=True)
    fehl += not ok
    print(f"  [{'ok  ' if ok else 'FEHL'}] ohne origin/main: nicht gelaufen, nicht grün")
    print(f'{len(FAELLE) + 1} Fälle, {fehl} Fehlschläge')
    return 1 if fehl else 0


def main():
    p = argparse.ArgumentParser(description=__doc__.split('\n')[0])
    p.add_argument('--ohne-holen', action='store_true', help='kein git fetch — gegen die vorhandenen Zweige')
    p.add_argument('--selbstprobe', action='store_true')
    p.add_argument('--wurzel', default=WURZEL)
    a = p.parse_args()
    if a.selbstprobe:
        return selbstprobe()
    try:
        doppelt, zahlen = messen(a.wurzel, holen=not a.ohne_holen)
    except NichtGelaufen as e:
        print(f'Nicht gelaufen: {e}')
        return 2
    return bericht(doppelt, zahlen)


if __name__ == '__main__':
    sys.exit(main())
