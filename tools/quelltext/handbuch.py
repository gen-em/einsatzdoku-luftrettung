#!/usr/bin/env python3
"""Rendern Handbuch und „Was ist NAdoku" überhaupt?

    python3 tools/quelltext/handbuch.py
    python3 tools/quelltext/handbuch.py --selbstprobe

Rückgabewert 0 = beide rendern, kein Bild aus fremder Quelle · 1 = Befund ·
2 = nicht gelaufen (`cmark-gfm` fehlt).

WOFÜR (P5b/AP8, E-P5b-08). Die Anwendung zeigt beide Dokumente als Seiten
(`hilfe.php`, `ueber.php`) und rendert sie zur Laufzeit aus dem Markdown im
Repositorium. Fehlt eines, zeigt die Seite „Dieses Dokument fehlt"; bricht
die Kodierung, rendert es nicht; und ein Bild aus fremder Quelle zeigt die
Anwendung nicht an, weil sie keine fremde Quelle zur Laufzeit lädt
(`CLAUDE.md` 4).

BIS BR-03 EIN SCHRITT IN STUFE 1, den Station B nie fuhr (F-BR-03). Die
Prüfung ist dieselbe geblieben — `cmark-gfm --validate-utf8`, dazu das
Muster für ein Bild mit Adresse — und um EINE Zeile gewachsen: Die Kodierung
wird streng gelesen. `--validate-utf8` scheitert an kaputten Bytes nicht, es
ERSETZT sie still durch U+FFFD und endet mit 0; der Tor-Schritt hätte ein
kaputtes Handbuch grün gemeldet. Gefunden von der Selbstprobe dieser Datei
beim Umzug (F-BR-16). `cmark-gfm` kommt mit der Ausbaustufe `web`
(`tools/sandbox/aufbauen.sh`) und im Tor über `apt-get`. Fehlt es, ist das
rc 2 — rot, nicht still grün.
"""
import os
import re
import shutil
import subprocess
import sys
import tempfile

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
DOKUMENTE = ['docs/Handbuch.md', 'docs/Was-ist-NAdoku.md']
FREMD_RE = re.compile(r'!\[[^]]*\]\((https?:)?//')


def messen(wurzel, dokumente=DOKUMENTE):
    """Liste der Befunde; wirft FileNotFoundError, wenn cmark-gfm fehlt."""
    if not shutil.which('cmark-gfm'):
        raise FileNotFoundError('cmark-gfm')
    befunde = []
    for rel in dokumente:
        pfad = os.path.join(wurzel, rel)
        if not os.path.isfile(pfad):
            befunde.append(f'{rel} fehlt — hilfe.php bzw. ueber.php zeigen dann „Dieses Dokument fehlt".')
            continue
        with open(pfad, 'rb') as f:
            roh = f.read()
        try:
            roh.decode('utf-8')
        except UnicodeDecodeError as e:
            befunde.append(f'{rel}: kein gültiges UTF-8 ab Byte {e.start} — cmark-gfm ersetzte es still')
        r = subprocess.run(['cmark-gfm', '--validate-utf8', '--to', 'html', pfad],
                           capture_output=True)
        if r.returncode != 0:
            befunde.append(f'{rel} rendert nicht: {r.stderr.decode("utf-8", "replace").strip()[:120]}')
        with open(pfad, encoding='utf-8', errors='replace') as f:
            for i, z in enumerate(f, 1):
                if FREMD_RE.search(z):
                    befunde.append(f'{rel}:{i}: Bild aus fremder Quelle — die Anwendung zeigt es nicht an')
    return befunde


def lauf():
    try:
        befunde = messen(WURZEL)
    except FileNotFoundError:
        print('cmark-gfm fehlt — Ausbaustufe web (tools/sandbox/aufbauen.sh). Gemessen wurde nichts.')
        return 2
    for b in befunde:
        if os.environ.get('GITHUB_ACTIONS'):
            print(f'::error::{b}')
        print(f'  ! {b}')
    for rel in DOKUMENTE:
        pfad = os.path.join(WURZEL, rel)
        if os.path.isfile(pfad):
            print(f'  {rel}: {os.path.getsize(pfad)} Bytes')
    print(f'{len(DOKUMENTE)} Dokumente, {len(befunde)} Befunde')
    return 1 if befunde else 0


def selbstprobe():
    gut = '# Titel\n\nText mit ![Bild](bilder/a.png).\n'
    faelle = [
        ('GEGENPROBE: zwei Dokumente, die rendern', {'a.md': gut, 'b.md': gut}, 0),
        ('ein Dokument fehlt', {'a.md': gut}, 1),
        ('ein Bild aus fremder Quelle', {'a.md': gut, 'b.md': '![x](https://cdn.example/x.png)\n'}, 1),
        ('ein Bild ohne Schema, aber fremd', {'a.md': gut, 'b.md': '![x](//cdn.example/x.png)\n'}, 1),
        ('kaputte Kodierung', {'a.md': gut, 'b.md': b'# Titel\n\n\xff\xfe kaputt\n'}, 1),
    ]
    try:
        messen(WURZEL, [])
    except FileNotFoundError:
        print('cmark-gfm fehlt — die Selbstprobe kann nichts messen.')
        return 2
    fehl = 0
    for name, dateien, soll in faelle:
        with tempfile.TemporaryDirectory(prefix='handbuch-') as d:
            for rel, inhalt in dateien.items():
                with open(os.path.join(d, rel), 'wb') as f:
                    f.write(inhalt if isinstance(inhalt, bytes) else inhalt.encode('utf-8'))
            ist = 1 if messen(d, ['a.md', 'b.md']) else 0
        ok = ist == soll
        fehl += not ok
        print(f"  [{'ok  ' if ok else 'FEHL'}] {name}  (erwartet {soll}, gemessen {ist})")
    print(f'{len(faelle)} Fälle, {fehl} Fehlschläge')
    return 1 if fehl else 0


if __name__ == '__main__':
    sys.exit(selbstprobe() if '--selbstprobe' in sys.argv[1:] else lauf())
