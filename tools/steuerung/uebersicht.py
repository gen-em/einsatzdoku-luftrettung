#!/usr/bin/env python3
"""Die offenen Backlog-Punkte nach Ziel — und ob jede Kopfzeile ihre Grammatik hält.

    python3 tools/steuerung/uebersicht.py               # Übersicht, gruppiert nach Ziel
    python3 tools/steuerung/uebersicht.py --ziel 17     # nur ein Ziel
    python3 tools/steuerung/uebersicht.py --pruefen     # nur der Prüfwert
    python3 tools/steuerung/uebersicht.py --selbstprobe

Rückgabewert 0 = jede Kopfzeile hält Grammatik und Ziel · 1 = eine nicht ·
2 = nicht gelaufen (Backlog oder Fahrplan-Tabelle nicht lesbar).

WOFÜR. Bis zum Schnitt (Konzept SD, 26.09.2026) führte der Rahmenplan in
Abschnitt 5 eine Zuordnung der offenen Backlog-Punkte zu Schritten — von
Hand, und darum mit 96 Zeilen gegen 113 offene Punkte (Konzept SD 1.1). Seit
dem Schnitt steht die Zuordnung an EINER Stelle: in der Kopfzeile jedes
Eintrags (`gehört zu`). Dieses Werkzeug liest sie und gibt die Übersicht aus,
die Abschnitt 5 war — erzeugt, nicht gepflegt. Und es hält jede Kopfzeile an
die Grammatik aus Konzept SD 4.6 und jedes Ziel an die Fahrplan-Tabelle.
"""
import argparse
import os
import re
import shutil
import signal
import sys
import tempfile

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
BACKLOG = 'docs/Backlog.md'
RAHMENPLAN = 'docs/Rahmenplan.md'

KOPFZEILE = re.compile(r'^(\d+)\. \*\*(.+?)\*\* · gehört zu: (.+?) · Stand: '
                       r'(offen|teilweise|zurückgestellt|nur auf Anlass|nicht umsetzen) · seit (\d{2}\.\d{2}\.\d{4})$')
EINTRAG = re.compile(r'^(\d+)\. ')
FESTE_ZIELE = ('nächste Backlog-Runde', 'Zuarbeit', 'Pflegeaufgabe', 'nach v1.0')


class NichtGelaufen(Exception):
    pass


def lesen(wurzel, rel):
    pfad = os.path.join(wurzel, *rel.split('/'))
    if not os.path.isfile(pfad):
        raise NichtGelaufen(f'{rel} fehlt')
    with open(pfad, encoding='utf-8') as f:
        return f.read().split('\n')


def fahrplan(wurzel):
    """[(Kennung, Name)] aus der Tabelle in Abschnitt 3 des Rahmenplans, in Reihenfolge.

    Die Kennung ist die Spalte „Schritt"; steht dort `—` (Betriebsübergang),
    zählt das erste Wort der fett gesetzten Kennung."""
    aus = []
    drin = False
    for z in lesen(wurzel, RAHMENPLAN):
        if z.startswith('## 3.'):
            drin = True
            continue
        if drin and z.startswith('## '):
            break
        if not drin or not z.startswith('| ') or z.startswith('|---'):
            continue
        c = [s.strip() for s in z.strip().strip('|').split('|')]
        if len(c) < 3 or c[0] == 'Schritt':
            continue
        name = re.sub(r'\*\*', '', c[1])
        kennung = c[0] if c[0] != '—' else name.split(' — ')[0].split(' (')[0].strip()
        if kennung:
            aus.append((kennung, name))
    if not aus:
        raise NichtGelaufen(f'{RAHMENPLAN}: keine Fahrplan-Tabelle in Abschnitt 3')
    return aus


def kopfzeilen(wurzel):
    """([(Zeile, Nr, Titel, Ziel, Stand, seit)], [(Zeile, Text) ohne Grammatik])."""
    zeilen = lesen(wurzel, BACKLOG)
    try:
        start = zeilen.index('## Offen')
    except ValueError:
        raise NichtGelaufen(f'{BACKLOG}: kein `## Offen`')
    gut, schlecht = [], []
    for i, z in enumerate(zeilen[start + 1:], start + 2):
        if not EINTRAG.match(z):
            continue
        m = KOPFZEILE.match(z)
        if m:
            gut.append((i, int(m.group(1)), m.group(2), m.group(3), m.group(4), m.group(5)))
        else:
            schlecht.append((i, z[:80]))
    return gut, schlecht


def ziel_gueltig(ziel, kennungen):
    kern = ziel.split(' AP')[0].strip()
    return ziel in FESTE_ZIELE or kern in kennungen


def pruefen(wurzel):
    """(Kopfzeilen, ohne Grammatik, ohne Ziel, Fahrplan)."""
    plan = fahrplan(wurzel)
    kennungen = {k for k, _ in plan}
    gut, schlecht = kopfzeilen(wurzel)
    ohne_ziel = [(i, nr, ziel) for i, nr, _, ziel, _, _ in gut if not ziel_gueltig(ziel, kennungen)]
    return gut, schlecht, ohne_ziel, plan


def melden(befunde_grammatik, befunde_ziel, gesamt):
    for i, text in befunde_grammatik:
        print(f'  ! {BACKLOG}:{i}: ohne Grammatik — {text}')
        if os.environ.get('GITHUB_ACTIONS'):
            print(f'::error file={BACKLOG},line={i}::Kopfzeile ohne Grammatik (Konzept SD 4.6)')
    for i, nr, ziel in befunde_ziel:
        print(f'  ! {BACKLOG}:{i}: Nr. {nr} — Ziel {ziel!r} ist weder Fahrplan-Kennung noch festes Wort')
        if os.environ.get('GITHUB_ACTIONS'):
            print(f'::error file={BACKLOG},line={i}::Nr. {nr}: Ziel {ziel!r} unbekannt')
    print(f'{gesamt} offene Einträge, {len(befunde_grammatik)} Kopfzeilen ohne Grammatik, '
          f'{len(befunde_ziel)} ohne gültiges Ziel')


def lauf(wurzel, nur_pruefen, nur_ziel):
    try:
        gut, schlecht, ohne_ziel, plan = pruefen(wurzel)
    except NichtGelaufen as e:
        print(f'nicht gelaufen: {e}')
        return 2
    gesamt = len(gut) + len(schlecht)
    if gesamt == 0:
        print(f'nicht gelaufen: {BACKLOG} hat keinen Eintrag unter `## Offen`')
        return 2
    if not nur_pruefen:
        namen = dict(plan)
        reihenfolge = [k for k, _ in plan] + list(FESTE_ZIELE)
        gruppen = {}
        for i, nr, titel, ziel, stand, seit in gut:
            kern = ziel.split(' AP')[0].strip() if ziel not in FESTE_ZIELE else ziel
            gruppen.setdefault(kern if kern in reihenfolge else ziel, []).append((nr, titel, ziel, stand, seit))
        for kern in reihenfolge + sorted(set(gruppen) - set(reihenfolge)):
            if kern not in gruppen or (nur_ziel and kern != nur_ziel):
                continue
            name = f' — {namen[kern]}' if kern in namen else ''
            print(f'\n## {kern}{name} ({len(gruppen[kern])})')
            for nr, titel, ziel, stand, seit in sorted(gruppen[kern]):
                paket = f' [{ziel}]' if ziel != kern else ''
                print(f'- Nr. {nr}{paket} · {stand} · seit {seit} · {titel}')
        print()
    melden(schlecht, ohne_ziel, gesamt)
    return 1 if (schlecht or ohne_ziel) else 0


# ------------------------------------------------------------- Selbstprobe

def selbstprobe(wurzel):
    def kopie(d):
        os.makedirs(os.path.join(d, 'docs'), exist_ok=True)
        for rel in (BACKLOG, RAHMENPLAN):
            shutil.copy(os.path.join(wurzel, *rel.split('/')), os.path.join(d, 'docs'))

    def wandeln(d, rel, f):
        pfad = os.path.join(d, *rel.split('/'))
        with open(pfad, encoding='utf-8') as h:
            z = h.read().split('\n')
        z = f(z)
        with open(pfad, 'w', encoding='utf-8') as h:
            h.write('\n'.join(z))

    def erste_kopfzeile(z):
        return next(i for i in range(z.index('## Offen'), len(z)) if KOPFZEILE.match(z[i]))

    def f_seit_weg(z):
        i = erste_kopfzeile(z); z[i] = re.sub(r' · seit \d{2}\.\d{2}\.\d{4}$', '', z[i]); return z
    def f_stand_fremd(z):
        i = erste_kopfzeile(z); z[i] = re.sub(r' · Stand: [^·]+ · ', ' · Stand: erledigt · ', z[i]); return z
    def f_ziel_fremd(z):
        i = erste_kopfzeile(z); z[i] = re.sub(r' · gehört zu: [^·]+ · ', ' · gehört zu: 99 · ', z[i]); return z
    def f_ziel_paket(z):
        i = erste_kopfzeile(z); z[i] = re.sub(r' · gehört zu: [^·]+ · ', ' · gehört zu: 17 AP3 · ', z[i]); return z
    def f_ohne_offen(z):
        return [x for x in z if x != '## Offen']
    def f_ohne_tabelle(z):
        return [x for x in z if not (x.startswith('| ') or x.startswith('|---'))]

    faelle = [
        ('GEGENPROBE: die Kopie misst wie der Baum', None, None, 'gleich'),
        ('Kopfzeile ohne `seit` — ohne Grammatik', BACKLOG, f_seit_weg, 'grammatik'),
        ('Stand außerhalb des Vokabulars — ohne Grammatik', BACKLOG, f_stand_fremd, 'grammatik'),
        ('Ziel `99`, keine Fahrplan-Kennung — ohne Ziel', BACKLOG, f_ziel_fremd, 'ziel'),
        ('GEGENPROBE: Kennung mit Paket (`17 AP3`) gilt', BACKLOG, f_ziel_paket, 'gleich'),
        ('kein `## Offen` — nicht gelaufen', BACKLOG, f_ohne_offen, 'nicht-gelaufen'),
        ('Rahmenplan ohne Fahrplan-Tabelle — nicht gelaufen', RAHMENPLAN, f_ohne_tabelle, 'nicht-gelaufen'),
    ]
    fehl = 0
    with tempfile.TemporaryDirectory(prefix='uebersicht-') as d:
        kopie(d)
        gut, schlecht, ohne_ziel, _ = pruefen(d)
        basis = (len(schlecht), len(ohne_ziel))
        for name, rel, f, soll in faelle:
            kopie(d)
            if f:
                wandeln(d, rel, f)
            try:
                gut, schlecht, ohne_ziel, _ = pruefen(d)
                ist = ('grammatik' if len(schlecht) > basis[0] else
                       'ziel' if len(ohne_ziel) > basis[1] else 'gleich')
            except NichtGelaufen:
                ist = 'nicht-gelaufen'
            ok = ist == soll
            fehl += not ok
            print(f"  [{'ok  ' if ok else 'FEHL'}] {name}  (erwartet {soll}, gemessen {ist})")
    print(f'{len(faelle)} Fälle, {fehl} Fehlschläge')
    return 1 if fehl else 0


def main():
    # `| head` soll die Übersicht abschneiden dürfen, ohne einen Traceback zu hinterlassen.
    signal.signal(signal.SIGPIPE, signal.SIG_DFL)
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('--pruefen', action='store_true', help='nur der Prüfwert, keine Übersicht')
    p.add_argument('--ziel', default='', help='nur dieses Ziel ausgeben (Kennung oder festes Wort)')
    p.add_argument('--selbstprobe', action='store_true', help='eingebaute Fehler in einer Kopie')
    p.add_argument('--wurzel', default=WURZEL, help='anderer Auscheck')
    a = p.parse_args()
    if a.selbstprobe:
        return selbstprobe(a.wurzel)
    return lauf(a.wurzel, a.pruefen, a.ziel)


if __name__ == '__main__':
    sys.exit(main())
