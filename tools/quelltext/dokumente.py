#!/usr/bin/env python3
"""Zeigen die Dokumente auf etwas, das es gibt?

    python3 tools/quelltext/dokumente.py
    python3 tools/quelltext/dokumente.py --selbstprobe

Anlass: Nr. 188 — kein Prüfmittel misst Verweise zwischen Dokumenten

Rückgabewert 0 = kein Befund · 1 = Befund (auch eine ungenutzte Ausnahme)
· 2 = gemessen wurde nichts (kein Dokument gefunden).

WOFÜR. Die Dokumentation war überall dort richtig, wo ein
Werkzeug sie hielt, und dort falsch, wo niemand zählte: Pfade auf Dateien,
die es nicht mehr gab, Zahlen neben Verzeichnissen, die längst andere waren,
Kopfzeilen „Stand: 06.09.2026" über Dokumenten, die seither hundertmal
geändert wurden, und eine `Technik.md`, deren Abschnitte nicht mehr in
Reihenfolge standen. Vier Regeln, je eine für diese Klassen:

1. `pfad` — jeder Pfad in Backticks und jedes Linkziel gegen `git ls-files`.
   Aufgelöst wird wohlwollend: wörtlich, relativ zum Dokument, unter
   `server/`, `server/api/`, `docs/`, `tools/`, `android/`, `watch/`,
   `.github/workflows/`, `server/assets/`, als bloßer Dateiname irgendwo im
   Baum, als Endstück eines Pfads. Was dann noch fehlt, steht entweder in
   `dokumente-ausnahmen.md` (mit Klasse und Grund) oder ist ein Befund.
2. `zahl` — eine Zahl am Verzeichnis („`symbole/` 55 Zeichen" im Baum,
   „58 Dateien unter `…/symbole/`" im Text) muss eine der Zahlen sein, die
   `ls` dort liefert: Einträge, Dateien, Ordner, Dateien der häufigsten
   Endung, Dateien rekursiv. Das ist großzügig; es fängt eine veraltete Zahl,
   nicht jede. Im Text zählt nur ein Hauptwort für Einträge (Dateien,
   Zeichen, Prüfungen …). Eine Zahl, die ausdrücklich einen früheren Stand
   nennt, steht mit Klasse und Grund in der Ausnahmeliste — gegen den Satz,
   nicht gegen die Zeile.
3. `stand` — keine Kopfzeile „Stand: …" in den ersten acht Zeilen. Den Stand
   eines Dokuments kennt Git (Entscheidung der Betreiberin).
4. `gliederung` — in `docs/Technik.md` sind die Kapitel `## N.` und die
   Abschnitte `### N.M` fortlaufend, ohne Lücke und ohne Doppel, und jede
   Überschrift dieser Ebenen trägt eine Nummer.
5. `abschnitt` — im CODE (`server/`, `tools/`, `android/`, `watch/`,
   `.github/`, ohne die Markdown-Dateien dort): Ein Verweis der Form
   „`Technik.md` 4.14", „docs/Pruefablauf.md 6.4" oder „CLAUDE.md,
   Abschnitt 4" nennt ein Dokument, das es gibt, und eine Nummer, die dort
   als Überschrift steht. Das ist der Wegweiser, der nach einer neuen
   Gliederung still auf den falschen Abschnitt zeigt. Ausgenommen sind die
   zwei Fassungsgeschichten (`server/version.php`,
   `android/version.properties`): Sie erzählen, was zur jeweiligen Fassung
   galt, und nennen die Nummern von damals.

WELCHE DOKUMENTE. `README.md`, `CLAUDE.md`, `docs/*.md` und jede
`LIESMICH.md` im Baum — ohne die eingefrorenen (Changelog, Archive,
Verlauf, erledigte Backlog-Einträge, die Konkordanz der alten Gliederung)
und ohne `docs/konzepte/`: Konzepte und Prüfdokumente sind Arbeitsprotokolle
ihres Schritts und beschreiben den Stand, an dem sie geschrieben wurden.

WAS SIE NICHT SIEHT. Im Code nur die Abschnittsverweise (Regel 5) — einen
Pfad oder eine Zahl in einem Code-Kommentar liest sie nicht. Einen
Verweis auf eine Zeilennummer („db.php 912") auch nicht.
Ob ein Verweis auf die RICHTIGE Datei zeigt: Sie prüft, dass es das Ziel
gibt. Und ob ein Satz stimmt, der keinen Pfad und keine Zahl trägt.
"""
import os
import re
import subprocess
import sys
import tempfile
from collections import Counter

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
AUSNAHMEN = 'tools/quelltext/dokumente-ausnahmen.md'
TECHNIK = 'docs/Technik.md'
CODE = ('server/', 'tools/', 'android/', 'watch/', '.github/')
FASSUNGSGESCHICHTE = {'server/version.php', 'android/version.properties'}
# „`Technik.md` 4.14", „docs/Pruefablauf.md 6.4", „CLAUDE.md, Abschnitt 4" —
# „Abschnitt"/„Kapitel" auch nach einem Zeilenumbruch im Kommentar
ABSCHNITT_RE = re.compile(r'(?<![\w/.-])((?:[\w.-]+/)*([A-Z][\w-]*\.md))`?'
                          r'(?:`?,?\s+(?:(?:\*|//|#|--)\s+)?(?:Abschnitt|Kapitel) |`?,? )'
                          r'(\d+(?:\.\d+)*[a-z]?)\b')
UEBERSCHRIFT_RE = re.compile(r'^#+ (?:Abschnitt |Kapitel )?(\d+(?:\.\d+)*[a-z]?)\.?\s')
EINGEFROREN = {
    'docs/CHANGELOG.md', 'docs/Rahmenplan-Archiv.md', 'docs/Rahmenplan-Archiv-2.md',
    'docs/Backlog-Erledigt.md', 'docs/Rahmenplan-Verlauf.md',
    'docs/Technik-Gliederung-alt-neu.md',
}
ENDUNG = (r'(?:php|js|mjs|py|md|sh|json|yml|yaml|kt|kts|mc|xml|css|sql|txt|toml|'
          r'properties|html|jungle|svg|png|woff2|ini|conf)')
PFAD_RE = re.compile(r'`([A-Za-z0-9_.\-/]+(?:/|\.' + ENDUNG + r'))`')
LINK_RE = re.compile(r'\]\(([^)#\s]+)\)')
VORSILBEN = ('server/', 'server/api/', 'docs/', 'tools/', 'android/', 'watch/',
             '.github/workflows/', 'server/assets/')

EINER = {'ein': 1, 'eins': 1, 'eine': 1, 'zwei': 2, 'drei': 3, 'vier': 4, 'fünf': 5,
         'sechs': 6, 'sieben': 7, 'acht': 8, 'neun': 9}
FEST = {'zehn': 10, 'elf': 11, 'zwölf': 12, 'dreizehn': 13, 'vierzehn': 14,
        'fünfzehn': 15, 'sechzehn': 16, 'siebzehn': 17, 'achtzehn': 18,
        'neunzehn': 19}
ZEHNER = {'zwanzig': 20, 'dreißig': 30, 'vierzig': 40, 'fünfzig': 50,
          'sechzig': 60, 'siebzig': 70, 'achtzig': 80, 'neunzig': 90}


def zahlwort(w):
    """„27", „siebenundzwanzig", „elf" → Zahl; sonst None."""
    w = w.lower()
    if w.isdigit():
        return int(w)
    if w in EINER:
        return EINER[w]
    if w in FEST:
        return FEST[w]
    if w in ZEHNER:
        return ZEHNER[w]
    m = re.fullmatch(r'(ein|zwei|drei|vier|fünf|sechs|sieben|acht|neun)und(\w+)', w)
    if m and m.group(2) in ZEHNER:
        return EINER[m.group(1)] + ZEHNER[m.group(2)]
    return None


ZAHL = (r'(\d+|(?:ein|zwei|drei|vier|fünf|sechs|sieben|acht|neun)und\w+zig|'
        r'zwei|drei|vier|fünf|sechs|sieben|acht|neun|zehn|elf|zwölf|\w+zehn|\w+zig)')
# Nur Hauptwörter, die Einträge eines Ordners zählen — „acht Fehler in
# `tools/`" oder „167 Stellen in `server/`" zählen etwas anderes.
EINTRAG = r'(?:Dateien|Datei|Ordner|Verzeichnisse|Zeichen|Symbole|Prüfungen|Proben|Skripte|Befehle|Bilder|Schriften)'
ZAHL_VOR_RE = re.compile(r'(?<![\w.,\-])' + ZAHL + r' ' + EINTRAG + r' (?:unter|in|im Ordner) `([\w.\-/]+/)`',
                         re.IGNORECASE)
BAUM_RE = re.compile(r'^([│ ]*)(?:├──|└──) ([\w.\-]+/)\s+' + ZAHL + r'\b')
STAND_RE = re.compile(r'^\*{0,2}\(?Stand:?\*{0,2}\s')


def baum(wurzel):
    """(Dateien, Ordner) des Repositoriums — über Git, sonst über das Dateisystem."""
    dateien = set()
    if os.path.isdir(os.path.join(wurzel, '.git')):
        aus = subprocess.run(['git', '-C', wurzel, 'ls-files', '--cached', '--others',
                              '--exclude-standard'], capture_output=True, text=True).stdout
        dateien = {z for z in aus.split('\n') if z}
    else:
        for ordner, _, namen in os.walk(wurzel):
            for n in namen:
                dateien.add(os.path.relpath(os.path.join(ordner, n), wurzel).replace(os.sep, '/'))
    ordner = set()
    for f in dateien:
        p = f
        while '/' in p:
            p = p.rsplit('/', 1)[0]
            ordner.add(p)
    return dateien, ordner


def dokumente(dateien):
    return sorted(f for f in dateien
                  if (f in ('README.md', 'CLAUDE.md') or re.fullmatch(r'docs/[^/]+\.md', f)
                      or f.endswith('/LIESMICH.md') or f == 'LIESMICH.md')
                  and f not in EINGEFROREN and not f.startswith('docs/konzepte/'))


def ausnahmen_lesen(wurzel):
    """[(Muster, Klasse, Grund, Zeile)] aus der Tabelle in dokumente-ausnahmen.md."""
    pfad = os.path.join(wurzel, AUSNAHMEN)
    regeln = []
    if not os.path.isfile(pfad):
        return regeln
    for nr, z in enumerate(open(pfad, encoding='utf-8'), 1):
        m = re.match(r'^\|\s*`([^`]+)`\s*\|\s*([^|]+?)\s*\|\s*(.+?)\s*\|\s*$', z)
        if m:
            # In einer Markdown-Tabelle steht `|` als `\|` — auch im Code.
            muster = m.group(1).replace('\\|', '|')
            regeln.append((re.compile('^(?:' + muster + ')$'), m.group(2), m.group(3), nr))
    return regeln


def gibt_es(p, doc, dateien, ordner, namen):
    q = p[2:] if p.startswith('./') else p
    q = q.rstrip('/') if q.endswith('/') else q
    if not q:
        return False
    kand = [q, os.path.normpath(os.path.join(os.path.dirname(doc), q)).replace(os.sep, '/')]
    kand += [v + q for v in VORSILBEN]
    if any(k in dateien or k in ordner for k in kand):
        return True
    if '/' not in q and q in namen:
        return True
    return any(f.endswith('/' + q) for f in dateien) or any(o.endswith('/' + q) for o in ordner)


def zahlen_im_ordner(o, dateien, ordner):
    pre = o.rstrip('/') + '/'
    direkt = {f[len(pre):].split('/')[0] for f in dateien if f.startswith(pre)}
    dat = {f for f in dateien if f.startswith(pre) and '/' not in f[len(pre):]}
    unter = {d for d in direkt if (pre + d) in ordner}
    endungen = Counter(os.path.splitext(f)[1] for f in dat if os.path.splitext(f)[1])
    haeufig = endungen.most_common(1)[0][1] if endungen else 0
    rek = sum(1 for f in dateien if f.startswith(pre))
    return {len(direkt), len(dat), len(unter), haeufig, rek}


def ordner_aufloesen(o, doc, ordner):
    q = o.rstrip('/')
    for k in [q, os.path.normpath(os.path.join(os.path.dirname(doc), q)).replace(os.sep, '/')] + [v + q for v in VORSILBEN]:
        if k in ordner:
            return k
    t = [x for x in ordner if x.endswith('/' + q)]
    return t[0] if len(t) == 1 else None


def nummern(wurzel, doc, vorrat={}):
    """Die Nummern der Überschriften eines Dokuments, außerhalb von Codezäunen."""
    schluessel = (wurzel, doc)
    if schluessel not in vorrat:
        s, zaun = set(), False
        for zl in open(os.path.join(wurzel, doc), encoding='utf-8'):
            if zl.startswith('```'):
                zaun = not zaun
            m = None if zaun else UEBERSCHRIFT_RE.match(zl)
            if m:
                s.add(m.group(1))
        vorrat[schluessel] = s
    return vorrat[schluessel]


def code_dateien(dateien):
    return sorted(f for f in dateien if f.startswith(CODE) and not f.endswith('.md')
                  and f not in FASSUNGSGESCHICHTE)


def ausnahme(regeln, text, genutzt):
    for r in regeln:
        if r[0].match(text):
            genutzt.add(r[3])
            return True
    return False


def messen(wurzel):
    """(Zahlen als dict, Befunde als [(Regel, Ort, Text)])."""
    dateien, ordner = baum(wurzel)
    namen = {f.rsplit('/', 1)[-1] for f in dateien}
    docs = dokumente(dateien)
    regeln = ausnahmen_lesen(wurzel)
    genutzt = set()
    befunde = []
    z = Counter(dokumente=len(docs))
    for d in docs:
        text = open(os.path.join(wurzel, d), encoding='utf-8').read()
        zeilen = text.split('\n')
        # 3. stand — der Rahmenplan ausgenommen: Seine Kopfzeile ist keine
        # Datumsangabe über das Dokument, sondern die Messung von `origin/main`,
        # die Rahmenplan 9 verlangt und `tools/steuerung/` in Decken hält.
        for nr, zl in enumerate(zeilen[:8], 1):
            if d != 'docs/Rahmenplan.md' and STAND_RE.match(zl):
                befunde.append(('stand', f'{d}:{nr}', 'Kopfzeile „Stand:" — den Stand kennt Git'))
        # 1. pfad (ausserhalb von Codezäunen: dort stehen Befehle und Bäume)
        zaun = False
        baumstapel = []
        for nr, zl in enumerate(zeilen, 1):
            if zl.startswith('```'):
                zaun = not zaun
                baumstapel = []
                continue
            if zaun:
                # 2. zahl im Verzeichnisbaum
                m = BAUM_RE.match(zl)
                tiefe = None
                mb = re.match(r'^([│ ]*)(?:├──|└──) ([\w.\-]+/)', zl)
                if mb:
                    tiefe = len(mb.group(1)) // 4
                    baumstapel = baumstapel[:tiefe] + [mb.group(2).rstrip('/')]
                if m:
                    z['zahlen'] += 1
                    n = zahlwort(m.group(3))
                    pfad = '/'.join(baumstapel)
                    o = pfad if pfad in ordner else ordner_aufloesen(pfad, d, ordner)
                    if o is None:
                        z['zahlen_unaufgeloest'] += 1
                    elif (n is not None and n not in zahlen_im_ordner(o, dateien, ordner)
                          and not ausnahme(regeln, zl.strip(), genutzt)):
                        befunde.append(('zahl', f'{d}:{nr}', f'„{m.group(3)}" an `{o}/` — ls zählt dort '
                                        + '/'.join(str(x) for x in sorted(zahlen_im_ordner(o, dateien, ordner)))))
                continue
            for m in list(PFAD_RE.finditer(zl)) + list(LINK_RE.finditer(zl)):
                p = m.group(1)
                if p.startswith(('http', 'mailto')):
                    continue
                z['pfade'] += 1
                if gibt_es(p, d, dateien, ordner, namen):
                    continue
                if ausnahme(regeln, p, genutzt):
                    z['pfade_ausnahme'] += 1
                    continue
                befunde.append(('pfad', f'{d}:{nr}', f'`{p}` gibt es nicht'))
            # 2. zahl vor einem Verzeichnis im Fließtext
            for m in ZAHL_VOR_RE.finditer(zl):
                z['zahlen'] += 1
                n = zahlwort(m.group(1))
                o = ordner_aufloesen(m.group(2), d, ordner)
                if o is None:
                    z['zahlen_unaufgeloest'] += 1
                    continue
                if (n is not None and n not in zahlen_im_ordner(o, dateien, ordner)
                        and not ausnahme(regeln, m.group(0), genutzt)):
                    befunde.append(('zahl', f'{d}:{nr}', f'„{m.group(1)}" vor `{o}/` — ls zählt dort '
                                    + '/'.join(str(x) for x in sorted(zahlen_im_ordner(o, dateien, ordner)))))
    # 5. abschnitt — Verweise aus dem Code
    md = {}
    for f in dateien:
        if f.endswith('.md'):
            md.setdefault(f.rsplit('/', 1)[-1], []).append(f)
    for c in code_dateien(dateien):
        try:
            text = open(os.path.join(wurzel, c), encoding='utf-8').read()
        except (UnicodeDecodeError, OSError):
            continue
        z['code_dateien'] += 1
        for m in ABSCHNITT_RE.finditer(text):
            z['abschnitte'] += 1
            kand = md.get(m.group(2), [])
            if m.group(1) != m.group(2):
                kand = [k for k in kand if k.endswith(m.group(1))]
            if any(m.group(3) in nummern(wurzel, k) for k in kand):
                continue
            if ausnahme(regeln, m.group(0), genutzt):
                z['abschnitte_ausnahme'] += 1
                continue
            nr = text.count('\n', 0, m.start()) + 1
            befunde.append(('abschnitt', f'{c}:{nr}', f'„{m.group(0)}" — '
                            + ('das Dokument gibt es nicht' if not kand
                               else f'keine Überschrift {m.group(3)} in {", ".join(kand)}')))
    # 4. gliederung von Technik.md
    if TECHNIK in docs:
        kap, ab, zaun = 0, 0, False
        for nr, zl in enumerate(open(os.path.join(wurzel, TECHNIK), encoding='utf-8').read().split('\n'), 1):
            if zl.startswith('```'):
                zaun = not zaun
            if zaun:
                continue
            m2 = re.match(r'^## (?:(\d+)\. )?', zl)
            m3 = re.match(r'^### (?:(\d+)\.(\d+) )?', zl)
            if zl.startswith('## '):
                z['ueberschriften'] += 1
                if not m2.group(1):
                    befunde.append(('gliederung', f'{TECHNIK}:{nr}', 'Kapitel ohne Nummer'))
                elif int(m2.group(1)) != kap + 1:
                    befunde.append(('gliederung', f'{TECHNIK}:{nr}', f'Kapitel {m2.group(1)} nach {kap}'))
                    kap, ab = int(m2.group(1)), 0
                else:
                    kap, ab = kap + 1, 0
            elif zl.startswith('### '):
                z['ueberschriften'] += 1
                if not m3.group(1):
                    befunde.append(('gliederung', f'{TECHNIK}:{nr}', 'Abschnitt ohne Nummer'))
                elif int(m3.group(1)) != kap or int(m3.group(2)) != ab + 1:
                    befunde.append(('gliederung', f'{TECHNIK}:{nr}',
                                    f'Abschnitt {m3.group(1)}.{m3.group(2)} nach {kap}.{ab}'))
                    ab = int(m3.group(2))
                else:
                    ab += 1
    for r in regeln:
        if r[3] not in genutzt:
            befunde.append(('ausnahme', f'{AUSNAHMEN}:{r[3]}', f'ungenutzt: `{r[0].pattern[4:-2]}`'))
    z['ausnahmen'] = len(regeln)
    return z, befunde


def lauf(wurzel):
    z, befunde = messen(wurzel)
    for regel, ort, text in befunde:
        if os.environ.get('GITHUB_ACTIONS'):
            datei, _, zeile = ort.partition(':')
            print(f'::error file={datei},line={zeile}::{regel}: {text}')
        print(f'  ! [{regel}] {ort}: {text}')
    print(f"{z['dokumente']} Dokumente · {z['pfade']} Pfadverweise, davon {z['pfade_ausnahme']} "
          f"über {z['ausnahmen']} Ausnahmen · {z['zahlen']} Zahlen an Verzeichnissen "
          f"({z['zahlen_unaufgeloest']} nicht auflösbar) · {z['ueberschriften']} Überschriften "
          f"in {TECHNIK} · {z['abschnitte']} Abschnittsverweise in {z['code_dateien']} Code-Dateien, "
          f"davon {z['abschnitte_ausnahme']} über Ausnahmen · {len(befunde)} Befunde")
    if z['dokumente'] == 0:
        print('Kein Dokument gefunden — gemessen wurde nichts.')
        return 2
    return 1 if befunde else 0


def selbstprobe():
    """Ein stimmiger Baum und je Regel ein Fehler, dazu ein leerer Baum."""
    gut = {
        'README.md': '# R\n\nSiehe `docs/Technik.md`, `api/day.php` und [x](docs/Technik.md).\n',
        'docs/Technik.md': '# T\n\n## 1. A\n\n### 1.1 B\n\n## 2. C\n\n### 2.1 D\n\n### 2.2 E\n'
                           'Drei Dateien unter `server/sym/`.\n\n```\n├── server/\n│   ├── sym/   drei Zeichen\n```\n',
        # Der Verweis ist zerteilt, damit der echte Lauf ihn in dieser Datei nicht liest.
        'server/api/day.php': '<?php\n// Siehe `docs/Technik' + '.md` 2.2 und Technik' + '.md, Abschnitt 1.\n',
        'server/version.php': '<?php\n// Damals Technik' + '.md 9.9.\n', 'server/sym/a.svg': '', 'server/sym/b.svg': '', 'server/sym/c.svg': '',
        AUSNAHMEN: '| Muster | Klasse | Grund |\n|---|---|---|\n',
    }
    def mit(aend):
        b = dict(gut)
        b.update(aend)
        return b
    faelle = [
        ('GEGENPROBE: Pfade, Zahlen, Gliederung stimmen', gut, 0),
        ('ein Pfad, den es nicht gibt', mit({'README.md': 'Siehe `server/gibtsnicht.php`.\n'}), 1),
        ('derselbe Pfad mit Ausnahme', mit({'README.md': 'Siehe `config.php`.\n',
         AUSNAHMEN: '| `config\\.php` | Server | nur auf dem Server |\n'}), 0),
        ('eine ungenutzte Ausnahme', mit({AUSNAHMEN: '| `x\\.php` | Früher | weg |\n'}), 1),
        ('eine falsche Zahl vor einem Verzeichnis', mit({'README.md': 'Vier Dateien unter `server/sym/`.\n'}), 1),
        ('eine falsche Zahl im Baum', mit({TECHNIK: gut[TECHNIK].replace('drei Zeichen', 'fünf Zeichen')}), 1),
        ('eine Kopfzeile „Stand:"', mit({'README.md': '# R\n\n*Stand: 06.09.2026*\n'}), 1),
        ('ein Abschnitt doppelt', mit({TECHNIK: '## 1. A\n\n### 1.1 B\n\n### 1.1 C\n'}), 1),
        ('ein Abschnitt ohne Nummer', mit({TECHNIK: '## 1. A\n\n### B\n'}), 1),
        ('ein Code-Verweis auf einen Abschnitt, den es nicht gibt',
         mit({'tools/x.py': '# Siehe Technik' + '.md 3.1\n'}), 1),
        ('derselbe über einen Zeilenumbruch („Technik.md,\n# Abschnitt 7")',
         mit({'tools/x.py': '# Siehe Technik' + '.md,\n# Abschnitt 7\n'}), 1),
        ('ein Code-Verweis auf ein Dokument, das es nicht gibt',
         mit({'tools/x.py': '# Siehe SPEC' + '.md 2.1\n'}), 1),
        ('derselbe Code-Verweis mit Ausnahme',
         mit({'tools/x.py': '# Siehe SPEC' + '.md 2.1\n',
              AUSNAHMEN: '| `SPEC\\.md 2\\.1` | Fremd | liegt beim Auftraggeber |\n'}), 0),
        ('nichts da — gemessen wurde nichts', {AUSNAHMEN: ''}, 2),
    ]
    fehl = 0
    for name, b, soll in faelle:
        with tempfile.TemporaryDirectory(prefix='dokumente-') as d:
            for rel, inhalt in b.items():
                os.makedirs(os.path.dirname(os.path.join(d, rel)) or d, exist_ok=True)
                with open(os.path.join(d, rel), 'w', encoding='utf-8') as f:
                    f.write(inhalt)
            z, befunde = messen(d)
            ist = 2 if z['dokumente'] == 0 else (1 if befunde else 0)
        ok = ist == soll
        fehl += not ok
        print(f"  [{'ok  ' if ok else 'FEHL'}] {name}  (erwartet {soll}, gemessen {ist})"
              + ('' if ok else '  ' + repr(befunde[:3])))
    print(f'{len(faelle)} Fälle, {fehl} Fehlschläge')
    return 1 if fehl else 0


if __name__ == '__main__':
    sys.exit(selbstprobe() if '--selbstprobe' in sys.argv[1:] else lauf(WURZEL))
