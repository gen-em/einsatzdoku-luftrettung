#!/usr/bin/env python3
"""Halten die Steuerungsdokumente ihre Decken? (Konzept SD, Abschnitt 5)

    python3 tools/steuerung/decken.py               # eine Zeile je Decke
    python3 tools/steuerung/decken.py --stellen     # dazu Datei und Zeile je Überschreitung
    python3 tools/steuerung/decken.py --selbstprobe # je Decke ein eingebauter Riss

Rückgabewert 0 = jede Decke gehalten · 1 = eine gerissen · 2 = nicht gelaufen
(eine der vier Dateien fehlt oder `cmark-gfm` fehlt — nie still grün).

WOFÜR. Rahmenplan und Backlog sind bis zum Schnitt am 26.09.2026 auf 3 700
und 11 300 Zeilen gewachsen, weil jede Fassung Erzähltext nachzog und nichts
es maß (Nr. 177, 196, 199). Seit dem Schnitt gilt je Stelle eine Decke — die
Zahlen unten, begründet in Konzept SD 5 —, und dieses Werkzeug hält sie im
Prüfstand und im Tor. Eine Decke ist ein Prüfwert, kein Ziel (E-SD-27): Wer
eine anheben will, begründet es in der Verlaufszeile und ändert sie HIER.

GEMESSEN WIRD DIE FORM, NICHT DER SINN. Ob ein Statussatz stimmt, sieht nur
ein Mensch; ob er 161 Zeichen hat, sieht dieses Skript.
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

RAHMENPLAN = 'docs/Rahmenplan.md'
VERLAUF = 'docs/Rahmenplan-Verlauf.md'
BACKLOG = 'docs/Backlog.md'
ERLEDIGT = 'docs/Backlog-Erledigt.md'
DATEIEN = (RAHMENPLAN, VERLAUF, BACKLOG, ERLEDIGT)

# Die Decken (Konzept SD 5). Eine Zahl je Stelle; die Beschreibung sagt, was gezählt wird.
DECKEN = {
    'rahmenplan-zeilen': (500, 'Rahmenplan.md gesamt, Zeilen'),
    'rahmenplan-kopf': (15, 'Rahmenplan.md Kopf bis zur ersten `## `, Zeilen'),
    'fahrplan-inhalt': (300, 'Fahrplan-Zelle „Inhalt" (Abschnitt 3), Zeichen'),
    'fahrplan-status': (240, 'Fahrplan-Zelle „Status" (Abschnitt 3), Zeichen'),
    'fahrplan-vokabular': (0, 'Fahrplan-Status, erstes Wort nicht aus dem Vokabular'),
    'fahrplan-block': (30, 'Block `### …` in Abschnitt 3, Zeilen'),
    'zuarbeiten-zeile': (300, 'Zuarbeiten-Zeile (Abschnitt 6), Zeichen'),
    'register-kern': (160, 'Register-Zeile (Abschnitt 7), Zelle „Kern", Zeichen'),
    'register-status': (160, 'Register-Zeile (Abschnitt 7), Zelle „Status", Zeichen'),
    'erledigt-zeile': (400, 'Erledigt-Zeile (Abschnitt 8), Zeichen'),
    'berichtigung-rahmenplan': (0, 'Berichtigungsmuster in Rahmenplan.md'),
    'berichtigung-backlog': (0, 'Berichtigungsmuster in Backlog.md'),
    'blockquote': (0, 'Blockquote-Zeilen in Rahmenplan.md'),
    'verlaufszeile': (300, 'Verlaufszeile (Rahmenplan-Verlauf.md), Zeichen'),
    'backlog-kopf': (60, 'Backlog.md Kopf bis `## Offen`, Zeilen'),
    'backlog-eintrag': (20, 'offener Backlog-Eintrag, Zeilen (Kopfzeile eingeschlossen)'),
    'backlog-einrueckung': (0, 'Folgezeilen mit anderer Einrückung als fünf Leerzeichen'),
    'backlog-schnittmenge': (0, 'Nummern in Backlog.md UND Backlog-Erledigt.md'),
    'backlog-codeblock': (0, 'Backlog-Einträge, die als Codeblock rendern (`<pre>`, cmark-gfm)'),
    'backlog-listenpunkte': (0, 'Abweichung `<li>` gegen Einträge (cmark-gfm)'),
}
VOKABULAR = ('offen', 'Konzept', 'freigegeben', 'Umsetzung', 'gebaut', 'gemergt', 'blockiert')
BERICHTIGUNG = re.compile(r'hier stand|stand bis (zur )?Fassung|berichtigt mit Fassung|ÜBERHOLT')
EINTRAG = re.compile(r'^(\d+)\. ')


class NichtGelaufen(Exception):
    pass


def lesen(wurzel, rel):
    pfad = os.path.join(wurzel, *rel.split('/'))
    if not os.path.isfile(pfad):
        raise NichtGelaufen(f'{rel} fehlt')
    with open(pfad, encoding='utf-8') as f:
        return f.read().split('\n')


def zellen(zeile):
    return [c.strip() for c in zeile.strip().strip('|').split('|')]


def abschnitte(zeilen):
    """{Nummer: [(Zeilennummer, Zeile)]} nach `## N.`."""
    aus, aktuell = {}, None
    for i, z in enumerate(zeilen, 1):
        m = re.match(r'^## (\d+)\.', z)
        if m:
            aktuell = int(m.group(1))
            aus[aktuell] = []
        if aktuell is not None:
            aus[aktuell].append((i, z))
    return aus


def tabellenzeilen(block):
    """Datenzeilen einer Markdown-Tabelle: ohne Trenner und ohne die Kopfzeile davor."""
    aus = []
    for k, (i, z) in enumerate(block):
        if not z.startswith('| ') or z.startswith('|---'):
            continue
        naechste = block[k + 1][1] if k + 1 < len(block) else ''
        if naechste.startswith('|---'):
            continue
        aus.append((i, z))
    return aus


def messen(wurzel):
    """{Decke: (Ist-Text, [(Datei, Zeile, Meldung)])} — Stellen leer heißt gehalten."""
    rp = lesen(wurzel, RAHMENPLAN)
    vl = lesen(wurzel, VERLAUF)
    bl = lesen(wurzel, BACKLOG)
    er = lesen(wurzel, ERLEDIGT)
    if rp[-1] == '':
        rp = rp[:-1]
    aus = {}

    def setze(name, ist, stellen):
        aus[name] = (ist, stellen)

    # --- Rahmenplan ---------------------------------------------------------
    n = len(rp)
    setze('rahmenplan-zeilen', f'{n} Zeilen',
          [(RAHMENPLAN, n, f'{n} Zeilen')] if n > DECKEN['rahmenplan-zeilen'][0] else [])
    kopf = next((i for i, z in enumerate(rp) if z.startswith('## ')), len(rp))
    setze('rahmenplan-kopf', f'{kopf} Zeilen',
          [(RAHMENPLAN, kopf, f'{kopf} Zeilen vor der ersten `## `')] if kopf > DECKEN['rahmenplan-kopf'][0] else [])
    ab = abschnitte(rp)
    fahrplan = tabellenzeilen(ab.get(3, []))
    inhalt, status, vokabular = [], [], []
    for i, z in fahrplan:
        c = zellen(z)
        if len(c) < 6:
            vokabular.append((RAHMENPLAN, i, f'nur {len(c)} Zellen'))
            continue
        if len(c[2]) > DECKEN['fahrplan-inhalt'][0]:
            inhalt.append((RAHMENPLAN, i, f'Inhalt {len(c[2])} Zeichen'))
        if len(c[5]) > DECKEN['fahrplan-status'][0]:
            status.append((RAHMENPLAN, i, f'Status {len(c[5])} Zeichen'))
        wort = c[5].replace('*', '').split()
        wort = wort[0].strip(',;:—') if wort else ''
        if wort not in VOKABULAR:
            vokabular.append((RAHMENPLAN, i, f'erstes Wort des Status: {wort!r}'))
    setze('fahrplan-inhalt', f'{len(fahrplan)} Zeilen', inhalt)
    setze('fahrplan-status', f'{len(fahrplan)} Zeilen', status)
    setze('fahrplan-vokabular', f'{len(fahrplan)} Zeilen', vokabular)
    bloecke, block = [], None
    for i, z in ab.get(3, []):
        if z.startswith('### '):
            block = [i, z[4:40], 0]
            bloecke.append(block)
        elif block is not None:
            block[2] += 1
    setze('fahrplan-block', f'{len(bloecke)} Blöcke',
          [(RAHMENPLAN, i, f'Block „{t}": {n} Zeilen') for i, t, n in bloecke if n > DECKEN['fahrplan-block'][0]])
    zu = tabellenzeilen(ab.get(6, []))
    setze('zuarbeiten-zeile', f'{len(zu)} Zeilen',
          [(RAHMENPLAN, i, f'{len(z)} Zeichen') for i, z in zu if len(z) > DECKEN['zuarbeiten-zeile'][0]])
    reg = [(i, z) for i, z in ab.get(7, []) if re.match(r'^\| R\d+ \|', z)]
    kern, rstatus = [], []
    for i, z in reg:
        c = zellen(z)
        if len(c) > 1 and len(c[1]) > DECKEN['register-kern'][0]:
            kern.append((RAHMENPLAN, i, f'{c[0]}: Kern {len(c[1])} Zeichen'))
        if len(c) > 2 and len(c[2]) > DECKEN['register-status'][0]:
            rstatus.append((RAHMENPLAN, i, f'{c[0]}: Status {len(c[2])} Zeichen'))
    setze('register-kern', f'{len(reg)} Zeilen', kern)
    setze('register-status', f'{len(reg)} Zeilen', rstatus)
    erl = tabellenzeilen(ab.get(8, []))
    setze('erledigt-zeile', f'{len(erl)} Zeilen',
          [(RAHMENPLAN, i, f'{len(z)} Zeichen') for i, z in erl if len(z) > DECKEN['erledigt-zeile'][0]])
    setze('berichtigung-rahmenplan', f'{n} Zeilen',
          [(RAHMENPLAN, i, z.strip()[:70]) for i, z in enumerate(rp, 1) if BERICHTIGUNG.search(z)])
    setze('blockquote', f'{n} Zeilen',
          [(RAHMENPLAN, i, z.strip()[:70]) for i, z in enumerate(rp, 1) if z.startswith('>')])

    # --- Verlauf ------------------------------------------------------------
    vz = [(i, z) for i, z in enumerate(vl, 1) if re.match(r'^\| \d+ \|', z)]
    setze('verlaufszeile', f'{len(vz)} Zeilen',
          [(VERLAUF, i, f'{len(z)} Zeichen') for i, z in vz if len(z) > DECKEN['verlaufszeile'][0]])

    # --- Backlog ------------------------------------------------------------
    try:
        i_offen = bl.index('## Offen')
    except ValueError:
        raise NichtGelaufen(f'{BACKLOG}: kein `## Offen`')
    setze('backlog-kopf', f'{i_offen} Zeilen',
          [(BACKLOG, i_offen + 1, f'{i_offen} Zeilen vor `## Offen`')] if i_offen > DECKEN['backlog-kopf'][0] else [])
    setze('berichtigung-backlog', f'{len(bl)} Zeilen',
          [(BACKLOG, i, z.strip()[:70]) for i, z in enumerate(bl, 1) if BERICHTIGUNG.search(z)])
    eintraege, aktuell = [], None
    einrueckung = []
    for i, z in enumerate(bl[i_offen + 1:], i_offen + 2):
        m = EINTRAG.match(z)
        if m:
            aktuell = [int(m.group(1)), i, 1]
            eintraege.append(aktuell)
        elif z.strip():
            if aktuell is not None:
                aktuell[2] = i - aktuell[1] + 1
            if not re.match(r'^ {5}\S', z):
                einrueckung.append((BACKLOG, i, z[:60].rstrip()))
    setze('backlog-eintrag', f'{len(eintraege)} Einträge',
          [(BACKLOG, i, f'Nr. {nr}: {n} Zeilen') for nr, i, n in eintraege if n > DECKEN['backlog-eintrag'][0]])
    setze('backlog-einrueckung', f'{len(eintraege)} Einträge', einrueckung)
    offen = {nr for nr, _, _ in eintraege}
    erledigt = {}
    for i, z in enumerate(er, 1):
        m = EINTRAG.match(z)
        if m:
            erledigt.setdefault(int(m.group(1)), i)
    schnitt = sorted(offen & set(erledigt))
    setze('backlog-schnittmenge', f'{len(offen)} offen, {len(erledigt)} erledigt',
          [(ERLEDIGT, erledigt[nr], f'Nr. {nr} steht auch in {BACKLOG}') for nr in schnitt])

    # --- Rendering (Nr. 196) ------------------------------------------------
    if not shutil.which('cmark-gfm'):
        raise NichtGelaufen('cmark-gfm fehlt (Ausbaustufe web)')
    r = subprocess.run(['cmark-gfm', '-e', 'table', '--to', 'html', os.path.join(wurzel, *BACKLOG.split('/'))],
                       capture_output=True, text=True, encoding='utf-8')
    if r.returncode != 0:
        raise NichtGelaufen(f'cmark-gfm: {r.stderr.strip()[:120]}')
    pre = r.stdout.count('<pre>')
    li = r.stdout.count('<li>')
    setze('backlog-codeblock', f'{pre} <pre>', [(BACKLOG, 0, f'{pre} Codeblock/Codeblöcke')] if pre else [])
    setze('backlog-listenpunkte', f'{li} <li> gegen {len(eintraege)} Einträge',
          [(BACKLOG, 0, f'{li} <li>, aber {len(eintraege)} Einträge — ein Eintrag zerfällt oder eine Liste steckt in einem')]
          if li != len(eintraege) else [])
    return aus


def lauf(wurzel, stellen):
    try:
        ergebnis = messen(wurzel)
    except NichtGelaufen as e:
        print(f'nicht gelaufen: {e}')
        return 2
    rot = 0
    for name, (decke, was) in DECKEN.items():
        ist, treffer = ergebnis[name]
        zustand = 'ROT ' if treffer else 'ok  '
        rot += bool(treffer)
        grenze = f'≤ {decke}' if decke else '= 0'
        print(f'  {zustand} {name:<24} {ist:<32} {grenze:<6} {was}')
        if treffer and stellen:
            for datei, zeile, text in treffer:
                ort = f'{datei}:{zeile}' if zeile else datei
                print(f'         {ort}: {text}')
                if os.environ.get('GITHUB_ACTIONS'):
                    print(f'::error file={datei},line={zeile or 1}::{name}: {text}')
        elif treffer and not stellen:
            print(f'         {len(treffer)} Stelle(n) — `--stellen` nennt sie')
    print(f'{len(DECKEN)} Decken, {rot} gerissen')
    return 1 if rot else 0


# ------------------------------------------------------------- Selbstprobe

def _einbauen(pfad, wandeln):
    with open(pfad, encoding='utf-8') as f:
        z = f.read().split('\n')
    z = wandeln(z)
    with open(pfad, 'w', encoding='utf-8') as f:
        f.write('\n'.join(z))


def _zeile(zeilen, muster, ab=0):
    for i in range(ab, len(zeilen)):
        if re.match(muster, zeilen[i]):
            return i
    raise AssertionError(f'kein Treffer für {muster!r}')


def _zelle_fuellen(z, index, laenge):
    c = z.strip().strip('|').split('|')
    c[index] = c[index] + 'x' * laenge
    return '| ' + ' | '.join(s.strip() for s in c) + ' |'


def _ab3(z, muster):
    """Index der ersten Zeile in Abschnitt N (muster) — Tabellenzeile nach dem Trenner."""
    start = _zeile(z, muster)
    trenner = _zeile(z, r'^\|---', start)
    return trenner + 1


def selbstprobe(wurzel):
    """Je Decke ein Riss in einer Kopie der vier Dateien; erwartet ist genau diese Decke rot."""
    def f_zeilen(z): return z + ['x'] * (DECKEN['rahmenplan-zeilen'][0] + 1 - len(z))
    def f_kopf(z):
        k = _zeile(z, r'^## ')
        return z[:k] + ['Zeile'] * (DECKEN['rahmenplan-kopf'][0] + 1) + z[k:]
    def f_inhalt(z):
        i = _ab3(z, r'^## 3\.'); z[i] = _zelle_fuellen(z[i], 2, 301); return z
    def f_status(z):
        i = _ab3(z, r'^## 3\.'); z[i] = _zelle_fuellen(z[i], 5, 241); return z
    def f_vokabular(z):
        i = _ab3(z, r'^## 3\.')
        c = z[i].strip().strip('|').split('|'); c[5] = ' erledigt' + c[5]
        z[i] = '|' + '|'.join(c) + '|'; return z
    def f_block(z):
        i = _zeile(z, r'^### ', _zeile(z, r'^## 3\.'))
        return z[:i + 1] + ['Text.'] * (DECKEN['fahrplan-block'][0] + 1) + z[i + 1:]
    def f_zuarbeit(z):
        i = _ab3(z, r'^## 6\.'); z[i] = z[i] + 'x' * 301; return z
    def f_kern(z):
        i = _zeile(z, r'^\| R\d+ \|'); z[i] = _zelle_fuellen(z[i], 1, 161); return z
    def f_rstatus(z):
        i = _zeile(z, r'^\| R\d+ \|'); z[i] = _zelle_fuellen(z[i], 2, 161); return z
    def f_erledigt(z):
        i = _ab3(z, r'^## 8\.'); z[i] = z[i] + 'x' * 401; return z
    def f_berichtigung(z): return z + ['Hier stand bis Fassung 3 etwas anderes.'.replace('Hier', 'hier')]
    def f_blockquote(z): return z + ['> ein Zitat']
    def f_verlauf(z):
        i = _zeile(z, r'^\| \d+ \|'); z[i] = z[i] + 'x' * 301; return z
    def f_bkopf(z):
        i = z.index('## Offen'); return z[:i] + ['Zeile.'] * (DECKEN['backlog-kopf'][0] + 1) + z[i:]
    def f_eintrag(z):
        i = _zeile(z, r'^\d+\. ', z.index('## Offen')); return z[:i + 1] + ['     Zeile.'] * 20 + z[i + 1:]
    def f_einrueckung(z):
        i = _zeile(z, r'^\d+\. ', z.index('## Offen')); return z[:i + 1] + ['    vier Leerzeichen.'] + z[i + 1:]
    def f_bberichtigung(z):
        i = _zeile(z, r'^\d+\. ', z.index('## Offen')); return z[:i + 1] + ['     Das ist berichtigt mit Fassung 9.'] + z[i + 1:]
    def f_schnitt(z, nr): return z + [f'{nr}. **Noch einmal.**']
    def f_codeblock(z):
        i = _zeile(z, r'^\d{3}\. ', z.index('## Offen')); return z[:i + 1] + ['', '    ein Codeblock'] + z[i + 1:]
    def f_li(z):
        i = _zeile(z, r'^\d+\. ', z.index('## Offen')); return z[:i + 1] + ['     - ein Unterpunkt'] + z[i + 1:]

    faelle = [
        ('rahmenplan-zeilen', RAHMENPLAN, f_zeilen), ('rahmenplan-kopf', RAHMENPLAN, f_kopf),
        ('fahrplan-inhalt', RAHMENPLAN, f_inhalt), ('fahrplan-status', RAHMENPLAN, f_status),
        ('fahrplan-vokabular', RAHMENPLAN, f_vokabular), ('fahrplan-block', RAHMENPLAN, f_block),
        ('zuarbeiten-zeile', RAHMENPLAN, f_zuarbeit), ('register-kern', RAHMENPLAN, f_kern),
        ('register-status', RAHMENPLAN, f_rstatus), ('erledigt-zeile', RAHMENPLAN, f_erledigt),
        ('berichtigung-rahmenplan', RAHMENPLAN, f_berichtigung), ('blockquote', RAHMENPLAN, f_blockquote),
        ('verlaufszeile', VERLAUF, f_verlauf), ('backlog-kopf', BACKLOG, f_bkopf),
        ('backlog-eintrag', BACKLOG, f_eintrag), ('backlog-einrueckung', BACKLOG, f_einrueckung),
        ('berichtigung-backlog', BACKLOG, f_bberichtigung), ('backlog-schnittmenge', ERLEDIGT, None),
        ('backlog-codeblock', BACKLOG, f_codeblock), ('backlog-listenpunkte', BACKLOG, f_li),
    ]
    # UNABHÄNGIG VOM ZUSTAND DES BAUMS (F-SD-13): Die Selbstprobe zählt je
    # Decke die Stellen VOR und NACH dem Riss. Ein Baum, der schon rot ist,
    # bleibt damit prüfbar — die erste Gegenprobe im Tor (Nr. 332 mit einer
    # 21. Zeile) fiel sonst hier, und `--stellen` kam nie dran.
    fehl = 0
    baum = {k: len(s) for k, (_, s) in messen(wurzel).items()}
    with tempfile.TemporaryDirectory(prefix='decken-') as d:
        os.makedirs(os.path.join(d, 'docs'))
        for rel in DATEIEN:
            shutil.copy(os.path.join(wurzel, *rel.split('/')), os.path.join(d, 'docs'))
        basis = {k: len(s) for k, (_, s) in messen(d).items()}
        ok = basis == baum
        fehl += not ok
        gerissen = sum(1 for v in basis.values() if v)
        print(f"  [{'ok  ' if ok else 'FEHL'}] GEGENPROBE: die Kopie misst wie der Baum — {gerissen} Decke(n) gerissen, in beiden")
        erste = None
        with open(os.path.join(wurzel, *BACKLOG.split('/')), encoding='utf-8') as f:
            for z in f:
                m = EINTRAG.match(z)
                if m:
                    erste = int(m.group(1))
                    break
        for name, rel, wandeln in faelle:
            for r2 in DATEIEN:
                shutil.copy(os.path.join(wurzel, *r2.split('/')), os.path.join(d, 'docs'))
            pfad = os.path.join(d, *rel.split('/'))
            _einbauen(pfad, wandeln if wandeln else (lambda z: f_schnitt(z, erste)))
            try:
                danach = {k: len(s) for k, (_, s) in messen(d).items()}
                neu_rot = sorted(k for k in danach if danach[k] > basis.get(k, 0))
                ist = name in neu_rot
                zusatz = '' if neu_rot == [name] else f' (neu rot: {", ".join(neu_rot)})'
            except NichtGelaufen as e:
                ist, zusatz = False, f' (nicht gelaufen: {e})'
            fehl += not ist
            print(f"  [{'ok  ' if ist else 'FEHL'}] {name}: eingebauter Riss wird gemeldet{zusatz}")
        # nicht gelaufen: eine Datei fehlt
        os.remove(os.path.join(d, 'docs', 'Rahmenplan-Verlauf.md'))
        try:
            messen(d)
            ist = False
        except NichtGelaufen:
            ist = True
        fehl += not ist
        print(f"  [{'ok  ' if ist else 'FEHL'}] eine Datei fehlt: nicht gelaufen (2), nicht grün")
    zahl = len(faelle) + 2
    print(f'{zahl} Fälle, {fehl} Fehlschläge ({len(faelle)} Decken je einmal gerissen, 2 Gegenproben)')
    return 1 if fehl else 0


def main():
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('--stellen', action='store_true', help='Datei und Zeile je Überschreitung')
    p.add_argument('--selbstprobe', action='store_true', help='je Decke ein eingebauter Riss in einer Kopie')
    p.add_argument('--wurzel', default=WURZEL, help='anderer Auscheck')
    a = p.parse_args()
    if a.selbstprobe:
        return selbstprobe(a.wurzel)
    return lauf(a.wurzel, a.stellen)


if __name__ == '__main__':
    sys.exit(main())
