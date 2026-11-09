#!/usr/bin/env python3
"""Welche Proben gehören zu dieser Berührung? — liest pruefablauf.json.

    python3 tools/pruefstand/auswahl.py --stufe klein --basis origin/main
    python3 tools/pruefstand/auswahl.py --stufe neben --datei server/spur_lib.php
    python3 tools/pruefstand/auswahl.py --abdeckung
    python3 tools/pruefstand/auswahl.py --stufe-ermitteln --basis origin/main
    python3 tools/pruefstand/auswahl.py --umfang-ermitteln --stufe neben [--voll]
    python3 tools/pruefstand/auswahl.py --motoren --stufe neben --umfang schmal
    python3 tools/pruefstand/auswahl.py --selbstprobe

Rückgabewert 0 = in Ordnung · 1 = Befund (bei `--abdeckung`: Dateien ohne
eigene Probe) · 2 = die Probe selbst kam nicht zum Laufen.

EINE ZUORDNUNG, NICHT ZWANZIG. Früher stand in jeder LIESMICH, wann ihr
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
    aus (die Wegprobe braucht das Konto des csv-Kreislaufs)."""
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
# Lasen beide Dateien selbst, beide mit dem Muster
# `WEB_VERSION', '…'` (der Schreibweise von `define()`), trafen sie
# `const WEB_VERSION = '…';` nie — die Stufe hieß
# still „klein", und die rote Lage „Stufe zu klein" konnte nie anschlagen.
# Deshalb: Wer die Fassung nicht lesen kann, sagt es und hört auf.
FASSUNG_RE = re.compile(r"WEB_VERSION'?\s*[,=]\s*'([0-9]+)\.([0-9]+)\.([0-9]+)'")
UNLESBAR = 'unlesbar'


def demo_proben(a):
    """Die Proben mit `"demo": true` — vor jeder schiebt der Prüfstand die
    Marke des Demo-Resets. Nur `true` zählt: Ein „ja" oder eine 1
    wäre in der Datei ein stiller Tippfehler, der nirgends auffiele."""
    return {n for n, d in a.get('proben', {}).items() if isinstance(d, dict) and d.get('demo') is True}


def hinweis_proben(a):
    """Die Proben mit `"hinweis": true` — rot gemessen heißt dort „Hinweis"
    (E-PU-05): gemeldet, im Bericht, hält nicht auf. Nur `true` zählt, wie
    bei `demo`."""
    return {n for n, d in a.get('proben', {}).items() if isinstance(d, dict) and d.get('hinweis') is True}


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


# ---- Der Umfang: schmal oder voll (E-PU-01) --------------------------------
#
# DIE ZEHNERREGEL. Der volle Lauf — drei Engines, alle Breiten, alle Seiten —
# fährt in jeder Hauptstufe und in `neben`, wenn die Spanne der Nebennummern
# zwischen `main` und dem Zweig eine durch 10 teilbare Zahl erreicht oder
# überschreitet (23.9 -> 23.10 und 23.9 -> 23.11 sind voll, 23.10 -> 23.11
# nicht). Sonst ist der Lauf schmal, und was er misst, folgt der Berührung
# (E-PU-02 bis -04). Die Regel liest nur die Fassungen und hält keinen
# Zustand; `--voll` am Prüfstand erzwingt den vollen Lauf jederzeit.
# Der Bericht trägt `umfang=…`, das Tor liest es gegen (bericht.py, Lage 7).
SCHMAL = 'schmal'
UMFAENGE = (SCHMAL, 'voll:hand', 'voll:haupt', 'voll:zehner')


def zehner(alt, neu):
    """Die erste durch 10 teilbare Nebennummer in (alt, neu] — oder None.
    Nur innerhalb derselben Hauptnummer; ein Hauptsprung ist ohnehin voll."""
    if not alt or not neu or alt[0] != neu[0]:
        return None
    for n in range(alt[1] + 1, neu[1] + 1):
        if n % 10 == 0:
            return n
    return None


def umfang_aus(stufe, alt, neu, voll=False):
    """(umfang, grund) — aus der wirksamen Stufe und den beiden Fassungen."""
    if voll:
        return 'voll:hand', 'mit --voll erzwungen'
    if stufe == 'haupt':
        return 'voll:haupt', 'Hauptstufe'
    z = zehner(alt, neu) if stufe == 'neben' else None
    if z is not None:
        return 'voll:zehner', f'Nebennummer {alt[1]} -> {neu[1]} erreicht {z} (Zehnerregel, E-PU-01)'
    return SCHMAL, 'keine Hauptstufe, keine Zehnergrenze, kein --voll'


# ---- Die Engines je Berührung (E-PU-02, R89) ------------------------------
#
# Browserproben laufen im schmalen Lauf in Chromium allein. Firefox und
# WebKit kommen dazu beim vollen Lauf und ab `neben`, wenn eine Datei aus
# `umfang.stil` in pruefablauf.json berührt ist (Stylesheet, ui.php) — dort
# entstehen die Unterschiede zwischen den Engines. In `klein` bleibt es bei
# Chromium (R87, E-DM-08), außer mit --voll. Stufe 2 misst unverändert
# dreifach. EINE Stelle: pruefen.sh fragt hier, bericht.py rechnet mit
# derselben Funktion nach (Lage 6).
MOTOREN_DREI = ('chromium', 'firefox', 'webkit')


def motoren_aus(stufe, umfang, dateien, stil):
    """(engines, grund) — engines als Tupel in fester Reihenfolge."""
    if umfang != SCHMAL:
        return MOTOREN_DREI, f'voller Lauf ({umfang})'
    if stufe != 'klein':
        treffer_stil = sorted(d for d in dateien if passt(d, stil))
        if treffer_stil:
            return MOTOREN_DREI, f"berührt: {', '.join(treffer_stil)} (E-PU-02)"
        return ('chromium',), 'schmaler Lauf, kein Stylesheet und kein ui.php berührt (E-PU-02)'
    return ('chromium',), 'Stufe klein (R87, E-DM-08)'


def stil_aus(a):
    """Die Dateien, die ab `neben` drei Engines UND zehn Breiten verlangen."""
    return a.get('umfang', {}).get('stil', [])


def drei_aus(a):
    """Die Dateien, die ab `neben` drei Engines verlangen: `stil` und die
    Engine-Wahl selbst (`tools/motor.mjs`, E-PU-16) — wer den Start der
    Engines ändert, misst ihn in allen dreien."""
    u = a.get('umfang', {})
    return u.get('stil', []) + u.get('engine_wahl', [])


# ---- Breiten und Seiten des Bilderlaufs (E-PU-03, -04, Q-PU-03) -----------
#
# DIE SEITEN ÜBER IHRE EIGENE DATEI. Eine Seite aus tools/screenshots/
# seiten.json gilt als berührt, wenn ihre Datei berührt ist (der Pfad ohne
# Anfrage, ein Platzhalter über `umfang.platzhalter`). Jede ANDERE Datei, die
# den Bilderlauf über ein Muster auslöst — eine Bibliothek, ein Skript,
# ui.php, das Stylesheet, ein Werkzeug —, gilt als geteilt und bringt alle
# Seiten. `umfang.neutral` löst nichts aus (die Fassungsnummer). Der volle
# Lauf nimmt alle Seiten. Dieselbe Auslöseregel gilt für die Bedienprobe;
# ihre Wege lassen sich keiner Seite zuordnen, sie fährt alle, wenn sie
# läuft. Bis PU-04 übergab der Prüfstand nie `--nur`: `klein` maß alle
# Seiten, obwohl die Doku „berührte Seiten" sagte (F-PU-07).
SEITEN_JSON = os.path.join(WURZEL, 'tools', 'screenshots', 'seiten.json')
SEITEN_PROBEN = ('bilderlauf', 'bedienprobe')
VORAB = ('erzeugen',)          # läuft vor den Riegeln, wenn ausgewählt (E-PU-07)
ALLE = 'alle'


def seiten_laden():
    with open(SEITEN_JSON, encoding='utf-8') as f:
        return json.load(f)['seiten']


def seitendatei(seite, platzhalter):
    """Die Datei unter server/ hinter einer Seite — None bei einem
    unbekannten Platzhalter (die Selbstprobe hält das bei null)."""
    # Ohne Abfrageteil UND ohne Anker: Seit OF-06 zeigt `43b` eine Karte der
    # Servereinstellungen (`betrieb_server.php#k-backup-ziele`), und bis OF-09
    # schnitt diese Zeile nur `?` ab — die Datei hiess dann mit Anker.
    pfad = seite['pfad'].split('?')[0].split('#')[0]
    if pfad.startswith('__'):
        return platzhalter.get(pfad)
    return 'server/' + pfad


def seiten_je_datei(seiten, platzhalter):
    aus = {}
    for s in seiten:
        d = seitendatei(s, platzhalter)
        if d:
            aus.setdefault(d, []).append(s['name'])
    return aus


def ausloeser(probe, dateien, stufe, a):
    """Die berührten Dateien, die `probe` über ein Muster ihrer Stufe auslösen."""
    grenze = STUFEN.index(stufe)
    return sorted({d for m in a['muster'] if probe in m['proben'] and STUFEN.index(m['ab']) <= grenze
                   for d in dateien if passt(d, m['pfade'])})


def seitenwahl(probe, stufe, umfang, dateien, a, seiten):
    """(seiten, grund): ALLE, eine Liste von Seitennamen, oder None — dann
    löst nichts die Probe aus, und sie fällt aus der Auswahl."""
    if umfang != SCHMAL:
        return ALLE, f'voller Lauf ({umfang})'
    u = a.get('umfang', {})
    ausl = [d for d in ausloeser(probe, dateien, stufe, a) if not passt(d, u.get('neutral', []))]
    if not ausl:
        return None, 'keine auslösende Datei (außer umfang.neutral)'
    je = seiten_je_datei(seiten, u.get('platzhalter', {}))
    geteilt = [d for d in ausl if d not in je]
    if probe != 'bilderlauf':
        return ALLE, f"ausgelöst von {', '.join(ausl[:3])}{' …' if len(ausl) > 3 else ''}"
    if geteilt:
        return ALLE, f"geteilt: {', '.join(geteilt[:3])}{' …' if len(geteilt) > 3 else ''}"
    namen = sorted({n for d in ausl for n in je[d]})
    return namen, f"Seiten von {', '.join(ausl)}"


def breiten_aus(stufe, umfang, dateien, stil):
    """(satz, grund) — der Breitensatz des Bilderlaufs (aufnehmen.mjs --breiten)."""
    if umfang != SCHMAL:
        return 'zehn', f'voller Lauf ({umfang})'
    if stufe == 'klein':
        return 'drei', 'Stufe klein (E-PK-14)'
    treffer_stil = sorted(d for d in dateien if passt(d, stil))
    if treffer_stil:
        return 'zehn', f"berührt: {', '.join(treffer_stil)} (E-PU-03)"
    return 'fuenf', 'schmaler Lauf (E-PU-03, -11)'


# ---- Der ererbte Bericht (E-PU-13) ------------------------------------------
#
# In `neben` darf der Prüfstand das Ergebnis einer Probe vom DIREKTEN
# VORGÄNGER auf demselben Zweig übernehmen, wenn dessen Bericht in seiner
# Nachricht steht, über seinen eigenen Baum, die Probe dort grün war und der
# Unterschied zwischen beiden Bäumen kein Muster dieser Probe trifft. Der
# Bericht schreibt `NAME=ererbt:<Baum des Vorgängers>`, das Tor rechnet mit
# derselben Funktion nach (bericht.py, Lage 8). Nie geerbt: die Riegel, der
# Kreislauf `edbak`, alles beim vollen Lauf und außerhalb von `neben`, und
# nichts von einem Bericht, der selbst erbt — spätestens jeder zweite Lauf
# misst wieder alles, was er auswählt. Dazu (E-PU-18): Ist der Prüfstand
# selbst berührt (`tools/pruefstand/`) oder eine Datei, die der Aufruf der
# Probe nennt, erbt nichts davon.
#
# Im Prüfstand ist der Vorgänger HEAD — gemessen wird der Arbeitsbestand,
# der Commit kommt danach; im Tor ist es der erste Elter des Kopfs.
KOPF_RE = re.compile(
    r'^Prüfstand:\s*(?P<stufe>klein|neben|haupt)\s*·\s*'
    r'Baum\s+(?P<baum>[0-9a-f]{7,40})\s*·\s*'
    r'Konfiguration\s+(?P<konf>[a-z]+)\s*$')
PAAR_RE = re.compile(r'([a-zA-Z][\w-]*)=(\S+)')
ERBT = 'ererbt:'
NIE_ERBEN = ('kreislauf-edbak',)


def zerlegen(text):
    """Aus einem Text (Commit-Nachricht) den Berichtsblock herausziehen."""
    zeilen = (text or '').splitlines()
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


def vorgaenger(commit, basis):
    """(bericht, baum) des Vorgängers — (None, '') wenn es ihn nicht gibt
    oder er schon auf `basis` liegt (dann ist er kein Stand dieses Zweigs)."""
    baum = git('rev-parse', '--verify', '-q', f'{commit}^{{tree}}').strip()
    if not baum:
        return None, ''
    if basis and subprocess.run(['git', '-C', WURZEL, 'merge-base', '--is-ancestor', commit, basis],
                                capture_output=True).returncode == 0:
        return None, baum
    return zerlegen(git('log', '-1', '--format=%B', commit)), baum


def unterschied_zum_arbeitsbestand(commit):
    """Was der Arbeitsbestand gegen `commit` ändert, samt neuer Dateien."""
    d = set(git('diff', '--name-only', commit).splitlines())
    d |= set(git('ls-files', '-o', '--exclude-standard').splitlines())
    return sorted(x for x in d if x)


def aufruf_pfade(aufruf):
    """Die Dateien, die ein Aufruf nennt; bei `proben.sh NAME` auch der Ordner
    tools/proben/NAME/."""
    pf = re.findall(r'(?:tools|android)/[\w./-]*\w', aufruf or '')
    m = re.search(r'tools/proben/proben\.sh\s+([\w-]+)', aufruf or '')
    if m:
        pf.append(f'tools/proben/{m.group(1)}/**')
    return pf


def erbfehler(name, stufe, umfang, vb, vbaum, diff, a, motoren=None, breiten=None, seiten=None):
    """Warum `name` NICHT erben darf — None heißt: es darf. `vb` ist der
    zerlegte Bericht des Vorgängers, `vbaum` dessen Baum, `diff` die Dateien
    zwischen beiden Bäumen; `motoren`, `breiten`, `seiten` die des Laufs."""
    if stufe != 'neben':
        return f'Stufe {stufe} — erben gibt es nur in neben'
    if umfang != SCHMAL:
        return f'voller Lauf ({umfang})'
    if name in a['riegel']['proben']:
        return 'ein Riegel — das Tor misst ihn selbst'
    if name in NIE_ERBEN:
        return 'wird nie geerbt (Nr. 267, F-S11-48)'
    if vb is None:
        return 'der Vorgänger trägt keinen Bericht (oder liegt schon auf main)'
    if not vbaum or not (vbaum.startswith(vb['baum']) or vb['baum'].startswith(vbaum)):
        return 'der Bericht des Vorgängers gilt nicht seinem Baum'
    if vb['stufe'] != 'neben' or vb['werte'].get('umfang') != SCHMAL:
        return f"der Vorgänger lief als {vb['stufe']}/{vb['werte'].get('umfang', '?')}, nicht neben/schmal"
    if any(w.startswith(ERBT) for w in vb['werte'].values()):
        return 'der Vorgänger erbt selbst — nur über eine Stufe'
    if vb['werte'].get(name) != '0':
        return f"beim Vorgänger nicht grün ({vb['werte'].get(name, 'fehlt')})"
    t = [d for d in diff if passt(d, ['tools/pruefstand/**'])]
    if t:
        return f'der Prüfstand selbst ist berührt ({t[0]}, E-PU-18)'
    grenze = STUFEN.index(stufe)
    for m in a['muster']:
        if name in m['proben'] and STUFEN.index(m['ab']) <= grenze:
            t = [d for d in diff if passt(d, m['pfade'])]
            if t:
                return f"Muster {m['id']} berührt ({t[0]})"
    p = a['proben'].get(name, {})
    t = [d for d in diff if passt(d, aufruf_pfade(p.get('aufruf', '')))]
    if t:
        return f'das Werkzeug der Probe ist berührt ({t[0]}, E-PU-18)'
    if p.get('motoren') is True and vb['werte'].get('motoren') not in (motoren, '+'.join(MOTOREN_DREI)):
        return f"der Vorgänger lief in {vb['werte'].get('motoren', '(keiner Angabe)')}, verlangt {motoren}"
    if name == 'bilderlauf' and (vb['werte'].get('breiten'), vb['werte'].get('seiten')) != (breiten, seiten):
        return 'der Vorgänger maß andere Breiten oder Seiten'
    return None


def erbschaft(proben, stufe, umfang, vb, vbaum, diff, a, motoren=None, breiten=None, seiten=None):
    """({name: Baum des Vorgängers}, {name: Grund}) — wer erbt, und warum der
    Rest misst. Wer misst, bekommt seine Voraussetzungen (`nach`) messend."""
    erben, gruende = {}, {}
    for n in proben:
        f = erbfehler(n, stufe, umfang, vb, vbaum, diff, a, motoren, breiten, seiten)
        if f is None:
            erben[n] = vbaum[:12]
        else:
            gruende[n] = f
    for n in mit_voraussetzungen([x for x in proben if x not in erben], a):
        if n in erben:
            del erben[n]
            gruende[n] = 'Voraussetzung einer Probe, die misst (nach)'
    return erben, gruende


def proben_mit_umfang(proben, stufe, umfang, dateien, a, seiten):
    """Der Umfang an der Probenliste: beim vollen Lauf laufen Bilderlauf und
    Bedienprobe immer, im schmalen nur, wenn eine Datei außer `neutral` sie
    auslöst."""
    aus = list(proben)
    for p in SEITEN_PROBEN:
        wahl, _ = seitenwahl(p, stufe, umfang, dateien, a, seiten)
        if wahl is None and p in aus:
            aus.remove(p)
        elif wahl is not None and p not in aus:
            aus.append(p)
    return aus


def umfang_aus_version(basis, stufe, commit=None, voll=False):
    """Wie stufe_aus_version: commit=None heißt Arbeitsbestand."""
    neu = fassung_arbeitsbestand() if commit is None else fassung(commit)
    return umfang_aus(stufe, fassung(basis), neu, voll)


def fassung_arbeitsbestand():
    try:
        with open(os.path.join(WURZEL, 'server', 'version.php'), encoding='utf-8') as f:
            return fassung_aus_text(f.read())
    except OSError:
        return None


# ---- Stufenregeln ---------------------------------------------------------
#
# EINE MIGRATION HEISST STUFE „haupt", AUCH BEI EINER NEBENNUMMER. Die Regel
# stand lange nur im Konzept: Die Plattformmatrix läuft nur in haupt, und
# dort sind Fehler an Migrationen gefunden worden. Ausgelöst wird von einer
# NEUEN KENNUNG im Katalog — nicht von einer Änderung an der Datei, die
# ändert sich auch ohne Migration (etwa um einen Protokollaufruf oder
# umgestellte Meldungen). Die Regel steht als Daten in
# `pruefablauf.json` (`stufenregeln`), damit sie in `Pruefablauf.md` 4
# erzeugt dasteht und nicht nur hier.

def kennungen_aus_text(text, muster):
    return set(re.findall(muster, text or '', re.M))


def kennungen(ref, regel):
    """Die Kennungen einer Regel in `ref`; None heißt Arbeitsbestand."""
    if ref is None:
        try:
            with open(os.path.join(WURZEL, regel['datei']), encoding='utf-8') as f:
                text = f.read()
        except OSError:
            text = ''
    else:
        text = git('show', f"{ref}:{regel['datei']}")
    return kennungen_aus_text(text, regel['muster'])


def stufe_mit_regeln(stufe, grund, neu_je_regel, regeln):
    """Hebt die Stufe, wenn eine Regel neue Kennungen sieht. Senkt nie.
    `neu_je_regel`: {regel-id: Menge der neuen Kennungen}."""
    if stufe == UNLESBAR:
        return stufe, grund
    for r in regeln:
        neu = sorted(neu_je_regel.get(r['id'], ()))
        if not neu:
            continue
        zusatz = f"neu in {r['datei']}: {', '.join(neu)} (Regel {r['id']})"
        if STUFEN.index(stufe) < STUFEN.index(r['stufe']):
            stufe, grund = r['stufe'], f"{grund}; {zusatz} -> {r['stufe']}"
        else:
            grund = f'{grund}; {zusatz}'
    return stufe, grund


def stufe_aus_version(basis, commit=None, a=None):
    """commit=None heißt: der ARBEITSBESTAND, wie beim Baum-Hash. Gemessen wird
    vor dem Commit; wer die Fassung aus HEAD liest, sieht den Sprung nicht, der
    erst mit dem Bericht committet wird — und das Tor meldet „Stufe zu klein".
    Danach die Stufenregeln."""
    neu = fassung_arbeitsbestand() if commit is None else fassung(commit)
    stufe, grund = stufe_aus_fassungen(fassung(basis), neu)
    regeln = (a if a is not None else lade()).get('stufenregeln', [])
    neu_je_regel = {r['id']: kennungen(commit, r) - kennungen(basis, r) for r in regeln}
    return stufe_mit_regeln(stufe, grund, neu_je_regel, regeln)


def beruehrte(basis):
    """Was diese Arbeit gegenüber `basis` ändert: der Zweig seit dem gemeinsamen
    Vorfahren, dazu der Arbeitsbestand — aber nur Dateien, die im
    Arbeitsbestand WIRKLICH anders sind als in `basis`, und neue Dateien.

    WARUM DER ABGLEICH MIT `basis`: Mitten in einem Merge von `origin/main`
    (Pruefablauf.md 5.3) zeigt `git status` jede Datei, die `main` mitbringt,
    als geändert gegenüber HEAD. Berührt hat sie diese Arbeit nicht; im Tor
    zählt der Unterschied gegen `main`."""
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
        # Ein Glob gegen eine VERSIONIERTE Datei. Früher stand hier
        # server/api/spurteil.php gegen das Muster server/api/spur*.php — eine
        # erfundene Datei gegen einen Pfad, der nie eine echte traf; die Probe
        # war grün, das Muster griff nie (Gegenprobe des Bestandsriegels).
        ('server/adminbackup_lib.php trifft „backup"',   'server/adminbackup_lib.php', 'backup', True),
        # Die Spur-Endpunkte der Schnittstelle — im Muster „spur"; gemeint
        # waren sie schon, als server/api/spur*.php geschrieben wurde.
        ('server/api/backup_spuren_restore.php trifft „spur"', 'server/api/backup_spuren_restore.php', 'spur', True),
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

    # `nach`: Die Voraussetzung kommt mit und läuft davor.
    probe_a = {'proben': {'x': {}, 'y': {'nach': ['x']}, 'z': {'nach': ['y']}}}
    grenzen += [
        ('nach: die Voraussetzung kommt mit',   mit_voraussetzungen(['y'], probe_a) == ['x', 'y']),
        ('nach: sie steht davor, auch später genannt',
         mit_voraussetzungen(['y', 'x'], probe_a) == ['x', 'y']),
        ('nach: über zwei Stufen',              mit_voraussetzungen(['z'], probe_a) == ['x', 'y', 'z']),
        ('nach: die Wegprobe läuft nach dem edbak-Kreislauf',
         'spaltenregister-wegprobe' in neben
         and neben.index('kreislauf-edbak') < neben.index('spaltenregister-wegprobe'))]

    # `demo`: nur true zählt, und gegen die ECHTE Datei —
    # zwei Proben, die das Demo-Konto anmelden, tragen es, und jede, die es
    # trägt, braucht die Anlage (sonst gäbe es keine Marke zu schieben).
    dp = demo_proben(a)
    grenzen += [
        ('demo: nur true zählt, nicht „ja"',
         demo_proben({'proben': {'x': {'demo': True}, 'y': {'demo': 'ja'}, 'z': {}}}) == {'x'}),
        ('demo: Bedienprobe und Bilderlauf tragen es', {'bedienprobe', 'bilderlauf'} <= dp),
        ('demo: jede Probe mit dem Feld braucht die Anlage',
         bool(dp) and all(a['proben'][n].get('braucht') == 'installation' for n in dp)),
        ('hinweis: nur true zählt, nicht „ja"',
         hinweis_proben({'proben': {'x': {'hinweis': True}, 'y': {'hinweis': 'ja'}, 'z': {}}}) == {'x'}),
        ('hinweis: kein Riegel trägt es — ein Riegel hält auf, oder er ist keiner',
         not (hinweis_proben(a) & set(a['riegel']['proben']))),
    ]

    # Die Fassung — gegen die ECHTE Schreibweise der Datei, nicht gegen eine
    # ausgedachte. Genau das fehlte, als das Muster nie traf.
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

    # Die Stufenregel „migration" — gegen den ECHTEN Katalog und
    # gegen die Formen, die sie nicht treffen darf.
    regel = {r['id']: r for r in a.get('stufenregeln', [])}.get('migration')
    if regel is None:
        grenzen.append(('die Stufenregel „migration" steht in pruefablauf.json', False))
    else:
        echt_m = open(os.path.join(WURZEL, regel['datei']), encoding='utf-8').read()
        k = kennungen_aus_text(echt_m, regel['muster'])
        neu1 = {'migration': {'2099_01_01_neu'}}
        grenzen += [
            ('der Katalog hat mindestens 60 Kennungen',    len(k) >= 60),
            ('jede Kennung beginnt mit einem Datum',       all(re.match(r'\d{4}_\d{2}_\d{2}_', x) for x in k)),
            ('eine Kennung im Kommentar zählt nicht',
             kennungen_aus_text("// 'id' => '2026_01_01_x'\n * 'id' => '2026_01_02_y'", regel['muster']) == set()),
            ("der Protokollaufruf ['id' => $m['id']] zählt nicht",
             kennungen_aus_text("    ['id' => $m['id'], 'x' => 1]", regel['muster']) == set()),
            ('neue Kennung: neben -> haupt',
             stufe_mit_regeln('neben', 'x', neu1, [regel])[0] == 'haupt'),
            ('neue Kennung: klein -> haupt',
             stufe_mit_regeln('klein', 'x', neu1, [regel])[0] == 'haupt'),
            ('keine neue Kennung: neben bleibt neben',
             stufe_mit_regeln('neben', 'x', {'migration': set()}, [regel])[0] == 'neben'),
            ('unlesbar bleibt unlesbar',
             stufe_mit_regeln(UNLESBAR, 'x', neu1, [regel])[0] == UNLESBAR),
        ]
    # Die Zehnerregel (E-PU-01) — gegen die Grenzfälle, an denen sie kippt.
    u = lambda s, a_, n_, v=False: umfang_aus(s, a_, n_, v)[0]
    grenzen += [
        ('zehner: 23.9 -> 23.11 ist voll (überschreitet)', u('neben', (23, 9, 0), (23, 11, 0)) == 'voll:zehner'),
        ('zehner: 23.9 -> 23.10 ist voll (erreicht)',      u('neben', (23, 9, 3), (23, 10, 0)) == 'voll:zehner'),
        ('zehner: 23.10 -> 23.11 ist schmal',               u('neben', (23, 10, 0), (23, 11, 0)) == SCHMAL),
        ('zehner: 23.11 -> 23.19 ist schmal',               u('neben', (23, 11, 0), (23, 19, 0)) == SCHMAL),
        ('zehner: 23.19 -> 23.20 ist voll',                 u('neben', (23, 19, 2), (23, 20, 0)) == 'voll:zehner'),
        ('zehner: main jünger als der Zweig ist schmal',    u('neben', (23, 12, 0), (23, 11, 0)) == SCHMAL),
        ('haupt ist immer voll',                            u('haupt', (23, 19, 0), (24, 0, 0)) == 'voll:haupt'),
        ('haupt aus der Migrationsregel ist voll',          u('haupt', (23, 6, 0), (23, 6, 1)) == 'voll:haupt'),
        ('klein ist schmal, auch an einer Zehnergrenze',    u('klein', (23, 10, 1), (23, 10, 2)) == SCHMAL),
        ('--voll macht auch klein voll',                    u('klein', (23, 6, 0), (23, 6, 0), True) == 'voll:hand'),
        ('unlesbare Fassung ist nicht zehner',              zehner(None, (23, 10, 0)) is None),
        ('jeder Umfang steht in UMFAENGE',                  {u('neben', (1, 9, 0), (1, 10, 0)), u('haupt', (1, 0, 0), (2, 0, 0)),
                                                             u('klein', (1, 1, 0), (1, 1, 1)), u('klein', (1, 1, 0), (1, 1, 1), True)}
                                                            == set(UMFAENGE)),
    ]
    # Die Engines (E-PU-02) — gegen die ECHTE Liste `umfang.stil`.
    st = stil_aus(a)
    m = lambda s, u_, d: motoren_aus(s, u_, d, st)[0]
    grenzen += [
        ('stil: style.css und ui.php stehen in umfang.stil',
         {'server/assets/style.css', 'server/ui.php'} <= set(st)),
        ('motoren: neben schmal ohne Stil -> Chromium',     m('neben', SCHMAL, ['server/index.php']) == ('chromium',)),
        ('motoren: neben schmal mit style.css -> drei',     m('neben', SCHMAL, ['server/assets/style.css']) == MOTOREN_DREI),
        ('motoren: neben schmal mit ui.php -> drei',        m('neben', SCHMAL, ['server/ui.php']) == MOTOREN_DREI),
        ('motoren: neben voll:zehner -> drei',              m('neben', 'voll:zehner', []) == MOTOREN_DREI),
        ('motoren: haupt -> drei',                          m('haupt', 'voll:haupt', []) == MOTOREN_DREI),
        ('motoren: klein mit style.css -> Chromium',        m('klein', SCHMAL, ['server/assets/style.css']) == ('chromium',)),
        ('motoren: klein mit --voll -> drei',               m('klein', 'voll:hand', []) == MOTOREN_DREI),
        ('motoren: ein anderes Stylesheet zieht nicht',     m('neben', SCHMAL, ['server/assets/druck.css']) == ('chromium',)),
    ]
    # Seiten und Breiten (E-PU-03, -04, Q-PU-03) — gegen die ECHTE Seitenliste.
    se = seiten_laden()
    ph = a.get('umfang', {}).get('platzhalter', {})
    ohne_datei = [s['name'] for s in se if seitendatei(s, ph) is None]
    fehlt = sorted({seitendatei(s, ph) for s in se if seitendatei(s, ph)
                    and not os.path.isfile(os.path.join(WURZEL, seitendatei(s, ph)))})
    w = lambda p_, s, u_, d: seitenwahl(p_, s, u_, d, a, se)[0]
    b = lambda s, u_, d: breiten_aus(s, u_, d, st)[0]
    idx = w('bilderlauf', 'neben', SCHMAL, ['server/index.php', 'server/version.php'])
    grenzen += [
        ('seiten: jeder Platzhalter in seiten.json hat eine Datei', not ohne_datei),
        ('seiten: jede Seitendatei liegt unter server/',           not fehlt),
        ('seiten: index.php bringt die Tagesübersicht, nicht alle',
         isinstance(idx, list) and '10-tagesuebersicht' in idx and '15-suche' not in idx),
        ('seiten: ein Platzhalter zählt (einsatz_form.php -> 13-einsatzformular)',
         w('bilderlauf', 'neben', SCHMAL, ['server/einsatz_form.php']) == ['13-einsatzformular']),
        ('seiten: eine Bibliothek ist geteilt -> alle',
         w('bilderlauf', 'neben', SCHMAL, ['server/spur_lib.php']) == ALLE),
        ('seiten: ein Skript ist geteilt -> alle',
         w('bilderlauf', 'klein', SCHMAL, ['server/assets/tag.js']) == ALLE),
        ('seiten: nur version.php löst nichts aus',
         w('bilderlauf', 'neben', SCHMAL, ['server/version.php']) is None
         and w('bedienprobe', 'neben', SCHMAL, ['server/version.php']) is None),
        ('seiten: ein Endpunkt unter api/ ist geteilt (server/*.php trifft ihn, wie in klein)',
         w('bilderlauf', 'neben', SCHMAL, ['server/api/day.php']) == ALLE),
        ('seiten: eine Doku-Datei löst nichts aus',
         w('bilderlauf', 'neben', SCHMAL, ['docs/Handbuch.md']) is None),
        ('seiten: voll heißt alle, auch ohne Auslöser',
         w('bilderlauf', 'neben', 'voll:zehner', []) == ALLE and w('bedienprobe', 'klein', 'voll:hand', []) == ALLE),
        ('seiten: das Werkzeug selbst ist geteilt',
         w('bilderlauf', 'klein', SCHMAL, ['tools/screenshots/aufnehmen.mjs']) == ALLE),
        ('seiten: nebenstufe trägt Bilderlauf und Bedienprobe nicht mehr',
         not ({'bilderlauf', 'bedienprobe'} & set(next(m for m in a['muster'] if m['id'] == 'nebenstufe')['proben']))),
        ('proben: neben schmal, nur version.php -> kein Bilderlauf',
         'bilderlauf' not in proben_mit_umfang(treffer(['server/version.php'], 'neben', a)[1], 'neben', SCHMAL,
                                                ['server/version.php'], a, se)),
        ('proben: neben voll:zehner, nur version.php -> Bilderlauf und Bedienprobe',
         {'bilderlauf', 'bedienprobe'} <= set(proben_mit_umfang(treffer(['server/version.php'], 'neben', a)[1],
                                                                 'neben', 'voll:zehner', ['server/version.php'], a, se))),
        ('breiten: klein drei, neben schmal fünf',
         b('klein', SCHMAL, ['server/index.php']) == 'drei' and b('neben', SCHMAL, ['server/index.php']) == 'fuenf'),
        ('breiten: neben mit style.css zehn, mit ui.php zehn',
         b('neben', SCHMAL, ['server/assets/style.css']) == 'zehn' and b('neben', SCHMAL, ['server/ui.php']) == 'zehn'),
        ('breiten: voll zehn, auch in klein',
         b('klein', 'voll:hand', []) == 'zehn' and b('haupt', 'voll:haupt', []) == 'zehn'),
        ('breiten: motor.mjs hebt die Engines, nicht die Breiten',
         b('neben', SCHMAL, ['tools/motor.mjs']) == 'fuenf'
         and motoren_aus('neben', SCHMAL, ['tools/motor.mjs'], drei_aus(a))[0] == MOTOREN_DREI),
        ('breiten: aufnehmen.mjs kennt die drei Sätze',
         all(f'{s}:' in open(os.path.join(WURZEL, 'tools', 'screenshots', 'aufnehmen.mjs'), encoding='utf-8').read()
             for s in ('drei', 'fuenf', 'zehn'))),
    ]
    # Der ererbte Bericht (E-PU-13) — jede Lage, in der nicht geerbt wird,
    # einzeln, gegen die ECHTE Zuordnung.
    vt = ('Prüfstand: neben · Baum aaaa1111bbbb2222 · Konfiguration web\n'
          '  syntax-php=php:1/0  spurprobe=0  jobprobe=0  ingestprobe=1\n'
          '  kreislauf-edbak=0  gpxprobe=0  motoren=chromium  umfang=schmal\n'
          '  bilderlauf=0  breiten=fuenf  seiten=11  spaltenregister-wegprobe=0\n')
    vb = zerlegen(vt)
    vbaum = 'aaaa1111bbbb2222cccc'
    e = lambda n, d=(), vb_=vb, s='neben', u_=SCHMAL, mo='chromium', br='fuenf', se_='11': \
        erbfehler(n, s, u_, vb_, vbaum, list(d), a, mo, br, se_)
    grenzen += [
        ('erbe: grün, nichts berührt -> erbt',                     e('jobprobe') is None),
        ('erbe: nur ein Doku-Satz berührt -> erbt',                e('spurprobe', ['docs/Handbuch.md']) is None),
        ('erbe: Vorgänger fehlt -> misst',                         e('jobprobe', vb_=None) is not None),
        ('erbe: beim Vorgänger rot -> misst',                      e('ingestprobe') is not None),
        ('erbe: beim Vorgänger nicht aufgeführt -> misst',         e('rollenprobe') is not None),
        ('erbe: eigenes Muster berührt -> misst',                  e('spurprobe', ['server/spur_lib.php']) is not None),
        ('erbe: server/** über nebenstufe berührt -> misst',       e('jobprobe', ['server/index.php']) is not None),
        ('erbe: der Vorgänger erbt selbst -> misst',
         e('jobprobe', vb_=zerlegen(vt.replace('spurprobe=0', 'spurprobe=ererbt:abc'))) is not None),
        ('erbe: der Kreislauf edbak nie',                          e('kreislauf-edbak') is not None),
        ('erbe: ein Riegel nie',                                   e('syntax-php') is not None),
        ('erbe: nicht in klein',                                   e('jobprobe', s='klein') is not None),
        ('erbe: nicht beim vollen Lauf',                           e('jobprobe', u_='voll:zehner') is not None),
        ('erbe: Vorgänger war voll -> misst',
         e('jobprobe', vb_=zerlegen(vt.replace('umfang=schmal', 'umfang=voll:hand'))) is not None),
        ('erbe: Bericht über einen anderen Baum -> misst',
         e('jobprobe', vb_=zerlegen(vt.replace('aaaa1111bbbb2222', 'ffff0000'))) is not None),
        ('erbe: Prüfstand berührt -> misst (E-PU-18)',             e('jobprobe', ['tools/pruefstand/pruefen.sh']) is not None),
        ('erbe: Werkzeug aus dem Aufruf berührt -> misst (E-PU-18)',
         e('jobprobe', ['tools/proben/jobs/probe.php']) is not None),
        ('erbe: Browserprobe, verlangt drei, Vorgänger Chromium -> misst',
         e('gpxprobe', mo='chromium+firefox+webkit') is not None),
        ('erbe: Bilderlauf mit anderen Seiten -> misst',           e('bilderlauf', se_='alle') is not None),
        ('erbe: Bilderlauf mit gleichen Breiten und Seiten -> erbt', e('bilderlauf') is None),
        ('erbe: wer misst, bekommt seine Voraussetzung messend',
         erbschaft(['jobprobe', 'spurprobe'], 'neben', SCHMAL, vb, vbaum, ['tools/proben/spur/probe.php'],
                   dict(a, proben=dict(a['proben'], spurprobe=dict(a['proben']['spurprobe'], nach=['jobprobe']))))[0]
         == {}),
        ('erbe: ohne nach erbt die andere',
         erbschaft(['jobprobe', 'spurprobe'], 'neben', SCHMAL, vb, vbaum, ['tools/proben/spur/probe.php'], a)[0]
         == {'jobprobe': vbaum[:12]}),
        ('erbe: zerlegen liest den eigenen Bericht',               vb['werte'].get('seiten') == '11'),
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
    p.add_argument('--umfang-ermitteln', action='store_true',
                   help='schmal oder voll:… mit Grund (E-PU-01); braucht --stufe')
    p.add_argument('--voll', action='store_true', help='den vollen Lauf erzwingen')
    p.add_argument('--motoren', action='store_true',
                   help='die Engines der Browserproben mit Grund (E-PU-02); braucht --stufe und --umfang')
    p.add_argument('--umfang', choices=UMFAENGE)
    p.add_argument('--ererbt', action='store_true',
                   help='welche Proben vom Vorgänger erben (E-PU-13), je Zeile Name und Baum; braucht --stufe und --umfang')
    p.add_argument('--bilderlauf', action='store_true',
                   help='Breitensatz und Seiten des Bilderlaufs mit Grund (E-PU-03, -04); braucht --stufe und --umfang')
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
    if args.motoren:
        if not args.stufe or not args.umfang:
            melde('--motoren braucht --stufe und --umfang.')
            return 2
        engines, grund = motoren_aus(args.stufe, args.umfang, args.datei or beruehrte(args.basis), drei_aus(a))
        melde(f"{' '.join(engines)}\t{grund}")
        return 0
    if args.ererbt:
        if not args.stufe or not args.umfang:
            melde('--ererbt braucht --stufe und --umfang.')
            return 2
        dateien = args.datei or beruehrte(args.basis)
        _, pr = treffer(dateien, args.stufe, a)
        se = seiten_laden()
        pr = proben_mit_umfang(pr, args.stufe, args.umfang, dateien, a, se)
        motoren = '+'.join(motoren_aus(args.stufe, args.umfang, dateien, drei_aus(a))[0])
        breiten = breiten_aus(args.stufe, args.umfang, dateien, stil_aus(a))[0]
        wahl = seitenwahl('bilderlauf', args.stufe, args.umfang, dateien, a, se)[0]
        seiten = ALLE if wahl == ALLE else (str(len(wahl)) if wahl else None)
        vb, vbaum = vorgaenger('HEAD', args.basis)
        erben, gruende = erbschaft(pr, args.stufe, args.umfang, vb, vbaum,
                                   unterschied_zum_arbeitsbestand('HEAD'), a, motoren, breiten, seiten)
        for n in pr:
            if n in erben:
                melde(f'{n}\t{erben[n]}')
        print(f'erbt {len(erben)} von {len(pr)} Proben'
              + (f' — sonst etwa: {next(iter(gruende.values()))}' if gruende else ''), file=sys.stderr)
        return 0
    if args.bilderlauf:
        if not args.stufe or not args.umfang:
            melde('--bilderlauf braucht --stufe und --umfang.')
            return 2
        dateien = args.datei or beruehrte(args.basis)
        satz, g1 = breiten_aus(args.stufe, args.umfang, dateien, stil_aus(a))
        wahl, g2 = seitenwahl('bilderlauf', args.stufe, args.umfang, dateien, a, seiten_laden())
        liste = '-' if wahl is None else (ALLE if wahl == ALLE else ','.join(wahl))
        melde(f'{satz}\t{liste}\t{g1}; {g2}')
        return 0
    if args.umfang_ermitteln:
        if not args.stufe:
            melde('--umfang-ermitteln braucht --stufe (die wirksame Stufe des Laufs).')
            return 2
        umfang, grund = umfang_aus_version(args.basis, args.stufe, voll=args.voll)
        melde(f'{umfang}\t{grund}')
        return 0
    if args.stufe_ermitteln:
        stufe, grund = stufe_aus_version(args.basis, a=a)
        melde(f'{stufe}\t{grund}')
        return 2 if stufe == UNLESBAR else 0

    stufe = args.stufe
    if not stufe:
        stufe, grund = stufe_aus_version(args.basis, a=a)
        if stufe == UNLESBAR:
            melde(f'Die Stufe lässt sich nicht bestimmen: {grund}.')
            return 2
    dateien = args.datei or beruehrte(args.basis)
    muster, proben = treffer(dateien, stufe, a)
    umfang = args.umfang or umfang_aus_version(args.basis, stufe, voll=args.voll)[0]
    proben = proben_mit_umfang(proben, stufe, umfang, dateien, a, seiten_laden())
    riegel = a['riegel']['proben']

    if args.nur_proben:
        # `erzeugen` vor den Riegeln (E-PU-07): `bestand` misst den frischen Stand.
        vorab = [x for x in VORAB if x in proben]
        for n in vorab + riegel + [x for x in proben if x not in riegel and x not in vorab]:
            melde(n)
        return 0

    melde(f'Stufe {stufe} · Umfang {umfang} · {len(dateien)} berührte Dateien · '
          f'{len(muster)} Muster · {len(proben)} Proben (plus {len(riegel)} Riegel)')
    melde()
    melde('Muster: ' + (', '.join(muster) or '—'))
    melde('Riegel: ' + ', '.join(riegel))
    melde('Proben: ' + (', '.join(proben) or '—'))
    for p_ in SEITEN_PROBEN:
        wahl, grund = seitenwahl(p_, stufe, umfang, dateien, a, seiten_laden())
        zahl = '—' if wahl is None else (ALLE if wahl == ALLE else f'{len(wahl)} Seiten')
        melde(f'{p_}: {zahl} ({grund})')
    return 0


if __name__ == '__main__':
    sys.exit(main())
