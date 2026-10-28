#!/usr/bin/env python3
"""Stimmen die Prüfsummen im Kopf der Fremdbestandteile — und in Lizenzen.md?

    python3 tools/quelltext/fremdsummen.py
    python3 tools/quelltext/fremdsummen.py --selbstprobe

Rückgabewert 0 = jede Textdatei unter server/assets/vendor/ trägt einen
Herkunftskopf, dessen SHA-256 zur Datei passt, und Lizenzen.md nennt dieselbe
Summe · 1 = eine Abweichung · 2 = gemessen wurde nichts (keine Datei).

WOFÜR (R17-26, F-R17-50, -51). CLAUDE.md 4 und Lizenzen.md 3 versprechen:
jede vendorierte Datei „mit Herkunft und SHA-256 im Dateikopf". Nachgerechnet
hat das niemand. Das Review fand zwei Lücken: `leaflet.css` trug die Summe
des npm-Originals mit Windows-Zeilenenden, abgelegt ist sie mit LF — wer
nachrechnete, fand eine Abweichung, die kein Eingriff war; und
`openlocationcode.js` lag ohne Kopf außerhalb von `vendor/`, Lizenzen.md
kannte sie nicht. Eine Prüfsumme, die niemand nachrechnet, ist eine Zahl im
Kommentar.

WAS ALS KOPF GILT. Der erste Kommentarblock `/*! … */` am Dateianfang, mit
einer Zeile `SHA-256: <64 Hexzeichen>`. Die Summe gilt für die Datei OHNE
diesen Block (alles nach dem ersten `*/` samt Zeilenumbruch) — so stehen alle
sechs Köpfe, und so sagt es der Kopf von `qrcode.js` seit jeher.

WAS NICHT. Die Bilder unter `vendor/leaflet/images/` tragen keinen Kopf und
werden nicht gezählt; die Bibliotheken auf dem Server (`server/vendor/`)
haben ihre Summen in Listen (Lizenzen.md 3a) und eine eigene Anleitung.
"""
import hashlib
import os
import re
import sys
import tempfile

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
VENDOR = 'server/assets/vendor'
LIZENZEN = 'docs/Lizenzen.md'
ENDUNGEN = ('.js', '.css')
SUMME_RE = re.compile(r'SHA-256:\s*([0-9a-f]{64})')
# Eine Zeile der Prüfsummentabelle in Lizenzen.md: | `datei` | `summe` … |
TABELLE_RE = re.compile(r'^\|\s*`([^`]+)`\s*\|\s*`([0-9a-f]{64})`', re.M)


def dateien(wurzel):
    aus = []
    for ordner, _, namen in os.walk(os.path.join(wurzel, VENDOR)):
        for n in namen:
            if n.endswith(ENDUNGEN):
                aus.append(os.path.relpath(os.path.join(ordner, n), os.path.join(wurzel, VENDOR))
                           .replace(os.sep, '/'))
    return sorted(aus)


def kopf_und_rest(roh):
    """(Kopftext, Rest als Bytes) — oder (None, roh) ohne Herkunftskopf."""
    if not roh.startswith(b'/*!'):
        return None, roh
    ende = roh.find(b'*/\n')
    if ende < 0:
        return None, roh
    return roh[:ende + 3].decode('utf-8', 'replace'), roh[ende + 3:]


def messen(wurzel):
    """(Zahl der Dateien, Befunde als [(Datei, Text)])."""
    liste = dateien(wurzel)
    befunde = []
    tabelle = {}
    pfad = os.path.join(wurzel, LIZENZEN)
    if os.path.isfile(pfad):
        with open(pfad, encoding='utf-8') as f:
            for name, summe in TABELLE_RE.findall(f.read()):
                tabelle[os.path.basename(name)] = summe
    else:
        befunde.append((LIZENZEN, 'fehlt — die Prüfsummentabelle ist nicht lesbar'))
    for rel in liste:
        with open(os.path.join(wurzel, VENDOR, rel), 'rb') as f:
            roh = f.read()
        kopf, rest = kopf_und_rest(roh)
        if kopf is None:
            befunde.append((rel, 'kein Herkunftskopf (`/*! … SHA-256: … */` am Dateianfang)'))
            continue
        m = SUMME_RE.search(kopf)
        if not m:
            befunde.append((rel, 'der Kopf nennt keine SHA-256'))
            continue
        soll, ist = m.group(1), hashlib.sha256(rest).hexdigest()
        if ist != soll:
            crlf = hashlib.sha256(rest.replace(b'\n', b'\r\n')).hexdigest() == soll
            befunde.append((rel, f'SHA-256 im Kopf {soll[:16]}…, gerechnet {ist[:16]}…'
                                 + (' — passt nur mit Windows-Zeilenenden (F-R17-50)' if crlf else '')))
        name = os.path.basename(rel)
        if name not in tabelle:
            befunde.append((rel, f'fehlt in der Prüfsummentabelle von {LIZENZEN}'))
        elif tabelle[name] != soll:
            befunde.append((rel, f'{LIZENZEN} nennt {tabelle[name][:16]}…, der Kopf {soll[:16]}…'))
    return len(liste), befunde


def lauf(wurzel):
    n, befunde = messen(wurzel)
    for rel, text in befunde:
        if os.environ.get('GITHUB_ACTIONS'):
            print(f'::error file={VENDOR}/{rel}::{text}')
        print(f'  ! {rel}: {text}')
    print(f'{n} Fremdbestandteile unter {VENDOR}/ nachgerechnet, {len(befunde)} Befunde')
    if n == 0:
        print('Keine Datei gefunden — gemessen wurde nichts.')
        return 2
    return 1 if befunde else 0


def selbstprobe():
    """Eine stimmige Datei; die Falle aus F-R17-50; ein falscher Rumpf; kein
    Kopf (F-R17-51); Lizenzen.md abweichend oder ohne Zeile; ein leerer Baum."""
    rumpf = b'a{b:c}\nd{e:f}\n'
    lf = hashlib.sha256(rumpf).hexdigest()
    crlf = hashlib.sha256(rumpf.replace(b'\n', b'\r\n')).hexdigest()

    def datei(summe, inhalt=rumpf):
        return '/*!\n * Probe 1.0 — MIT\n * SHA-256: '.encode() + summe.encode() + b'\n */\n' + inhalt

    def lizenzen(summe):
        return f'| Datei | SHA-256 |\n|---|---|\n| `probe.css` | `{summe}` |\n'.encode()

    faelle = [
        ('GEGENPROBE: Kopf, Datei und Lizenzen.md stimmen',
         {f'{VENDOR}/probe.css': datei(lf), LIZENZEN: lizenzen(lf)}, 0),
        ('die Summe des Originals mit CRLF, abgelegt mit LF (F-R17-50)',
         {f'{VENDOR}/probe.css': datei(crlf), LIZENZEN: lizenzen(crlf)}, 1),
        ('ein veränderter Rumpf',
         {f'{VENDOR}/probe.css': datei(lf, rumpf + b'g{h:i}\n'), LIZENZEN: lizenzen(lf)}, 1),
        ('eine Datei ohne Herkunftskopf (F-R17-51)',
         {f'{VENDOR}/probe.css': rumpf, LIZENZEN: lizenzen(lf)}, 1),
        ('Lizenzen.md nennt eine andere Summe',
         {f'{VENDOR}/probe.css': datei(lf), LIZENZEN: lizenzen(crlf)}, 1),
        ('Lizenzen.md kennt die Datei nicht',
         {f'{VENDOR}/probe.css': datei(lf), LIZENZEN: b'| Datei | SHA-256 |\n|---|---|\n'}, 1),
        ('nichts unter vendor/ — gemessen wurde nichts', {LIZENZEN: lizenzen(lf)}, 2),
    ]
    fehl = 0
    for name, baum, soll in faelle:
        with tempfile.TemporaryDirectory(prefix='fremdsummen-') as d:
            for rel, inhalt in baum.items():
                os.makedirs(os.path.dirname(os.path.join(d, rel)), exist_ok=True)
                with open(os.path.join(d, rel), 'wb') as f:
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
