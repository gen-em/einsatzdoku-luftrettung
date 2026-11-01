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
    return a.get('umfang', {}).get('stil', [])


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
        engines, grund = motoren_aus(args.stufe, args.umfang, args.datei or beruehrte(args.basis), stil_aus(a))
        melde(f"{' '.join(engines)}\t{grund}")
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
