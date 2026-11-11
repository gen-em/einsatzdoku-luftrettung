#!/usr/bin/env python3
"""Schranken der Auslieferung — Bibliotheken, Regex-Anker, .htaccess (KA-05).

    python3 tools/quelltext/schranken.py
    python3 tools/quelltext/schranken.py --selbstprobe

Anlass: Nr. 562.

Rückgabewert 0 = keine Befunde · 1 = Befunde · 2 = gemessen wurde nichts
(kein `server/` oder keine `.htaccess`).

WOFÜR (KA-05, F-KA-23, -34, -38). Bis Web 24.12.1 ließen sich Bibliotheken
und Teil-Vorlagen direkt aufrufen (`blatt_dialog.php` schrieb je Abruf eine
Systemmeldung), `migrations/*.sql` lagen offen, und `$` ohne `D` ließ einen
angehängten Zeilenumbruch an jeder Kennungsprüfung vorbei. Dieser Riegel hält,
dass keins davon zurückkommt.

WAS ER MISST:

  waechter  jede `*_lib.php`, `*_teile.php`, `*_dialog.php` unter `server/`
            und `server/api/` trägt in ihren ersten fünf Zeilen den Wächter
            auf `SCRIPT_FILENAME` (404, wenn sie selbst aufgerufen wird)
  anker     kein `preg_match`/`preg_match_all` mit `^…$` ohne `D` oder `m`
            in `server/` (außer `vendor/`) — `\\z` statt `$`
  htaccess  `server/.htaccess` sperrt `_(lib|teile|dialog).php` und
            `migrations/`

WAS NICHT. Ob Apache die `.htaccess` liest, misst die Integritätswache gegen
die Anlage; hier wird nur Quelltext gelesen. Muster, die nicht über
`preg_match` laufen (`preg_replace`, `preg_split`), zählt der Riegel nicht.
"""
import glob
import os
import re
import sys
import tempfile

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
WAECHTER_RE = re.compile(r"\$_SERVER\['SCRIPT_FILENAME'\][^\n]*===\s*__FILE__[^\n]*http_response_code\(\s*404\s*\)")
ANKER_RE = re.compile(r"""preg_match(?:_all)?\(\s*(['"])([/~#])\^.*?\$\2([a-zA-Z]*)\1""")
HT_TEILE_RE = re.compile(r'<FilesMatch\s+"_\(lib\|teile\|dialog\)\\\.php\$">\s*\n\s*Require all denied', re.I)
HT_MIGR_RE = re.compile(r'^RewriteRule\s+\^\([^)]*\bmigrations\b[^)]*\)', re.M)


def messen(wurzel):
    """(Zahl der gelesenen Dateien, Befunde als [(Ort, Regel, Text)])."""
    srv = os.path.join(wurzel, 'server')
    ht = os.path.join(srv, '.htaccess')
    if not os.path.isdir(srv) or not os.path.isfile(ht):
        return 0, []
    befunde = []
    n = 0
    teile = []
    for muster in ('*_lib.php', '*_teile.php', '*_dialog.php', 'api/*_lib.php'):
        teile += glob.glob(os.path.join(srv, muster))
    for pfad in sorted(set(teile)):
        rel = os.path.relpath(pfad, wurzel).replace(os.sep, '/')
        with open(pfad, encoding='utf-8', errors='replace') as f:
            kopf = ''.join(f.readline() for _ in range(5))
        if not WAECHTER_RE.search(kopf):
            befunde.append((rel, 'waechter', 'kein Wächter in den ersten fünf Zeilen (F-KA-23)'))
    for pfad in glob.glob(os.path.join(srv, '**', '*.php'), recursive=True):
        rel = os.path.relpath(pfad, wurzel).replace(os.sep, '/')
        if '/vendor/' in rel:
            continue
        n += 1
        with open(pfad, encoding='utf-8', errors='replace') as f:
            for i, zeile in enumerate(f, 1):
                for m in ANKER_RE.finditer(zeile):
                    if 'D' in m.group(3) or 'm' in m.group(3):
                        continue
                    befunde.append((f'{rel}:{i}', 'anker', '`$` ohne `D` — `\\z` (F-KA-34)'))
    with open(ht, encoding='utf-8') as f:
        text = f.read()
    if not HT_TEILE_RE.search(text):
        befunde.append(('server/.htaccess', 'htaccess', 'sperrt _lib/_teile/_dialog.php nicht (F-KA-23)'))
    if not HT_MIGR_RE.search(text):
        befunde.append(('server/.htaccess', 'htaccess', 'sperrt migrations/ nicht (F-KA-38)'))
    return n, befunde


def lauf(wurzel):
    n, befunde = messen(wurzel)
    for ort, regel, text in befunde:
        print(f'  ! {ort} [{regel}]: {text}')
    if n == 0:
        print('Kein server/ oder keine .htaccess — gemessen wurde nichts.')
        return 2
    print(f'{n} PHP-Dateien unter server/ gelesen, {len(befunde)} Befunde')
    return 1 if befunde else 0


WACH = ("<?php\ndeclare(strict_types=1);\n"
        "if (PHP_SAPI !== 'cli' && realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) "
        "{ http_response_code(404); exit; }\n")
HT_GUT = ('RewriteRule ^(apk|demo|migrations)(/|$) - [F,L]\n'
          '<FilesMatch "_(lib|teile|dialog)\\.php$">\n  Require all denied\n</FilesMatch>\n')


def selbstprobe():
    def baum(**ueber):
        b = {'server/.htaccess': HT_GUT,
             'server/x_lib.php': WACH + "preg_match('/^[a-f0-9]{16}\\z/', $k);\n",
             'server/seite.php': "<?php preg_match('/^\\d{4}$/D', $j); preg_match('/^a$/m', $t);\n"}
        b.update(ueber)
        return {k: v for k, v in b.items() if v is not None}

    faelle = [
        ('GEGENPROBE: Wächter, \\z, D und m, .htaccess gut', baum(), 0),
        ('eine Bibliothek ohne Wächter', baum(**{'server/y_lib.php': "<?php\n// nichts\n"}), 1),
        ('eine Teil-Vorlage ohne Wächter', baum(**{'server/z_teile.php': "<?php\n"}), 1),
        ('ein Anker mit $ ohne D', baum(**{'server/seite.php': "<?php preg_match('/^\\d{4}$/', $j);\n"}), 1),
        ('ein Anker mit $ in Tilden', baum(**{'server/api/a.php': "<?php preg_match('~^x$~i', $j);\n"}), 1),
        ('.htaccess ohne die Teilesperre', baum(**{'server/.htaccess': 'RewriteRule ^(apk|demo|migrations)(/|$) - [F,L]\n'}), 1),
        ('.htaccess ohne migrations', baum(**{'server/.htaccess': HT_GUT.replace('|migrations', '')}), 1),
        ('keine .htaccess — gemessen wurde nichts', baum(**{'server/.htaccess': None}), 2),
    ]
    fehl = 0
    for name, b, soll in faelle:
        with tempfile.TemporaryDirectory(prefix='schranken-') as d:
            for rel, inhalt in b.items():
                os.makedirs(os.path.dirname(os.path.join(d, rel)), exist_ok=True)
                with open(os.path.join(d, rel), 'w', encoding='utf-8') as f:
                    f.write(inhalt)
            n, befunde = messen(d)
            ist = 2 if n == 0 else (1 if befunde else 0)
        ok = ist == soll
        fehl += not ok
        print(f"  [{'ok  ' if ok else 'FEHL'}] {name}  (erwartet {soll}, gemessen {ist})")
    print(f'{len(faelle)} Fälle, {fehl} Fehlschläge')
    return 1 if fehl else 0


if __name__ == '__main__':
    sys.exit(selbstprobe() if '--selbstprobe' in sys.argv[1:] else lauf(WURZEL))
