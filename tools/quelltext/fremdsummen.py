#!/usr/bin/env python3
"""Stimmen die Prüfsummen im Kopf der Fremdbestandteile — und in Lizenzen.md?

    python3 tools/quelltext/fremdsummen.py
    python3 tools/quelltext/fremdsummen.py --selbstprobe

Rückgabewert 0 = jede Textdatei unter server/assets/vendor/ trägt einen
Herkunftskopf, dessen SHA-256 zur Datei passt, und Lizenzen.md nennt dieselbe
Summe; die Bibliotheken unter server/vendor/ stimmen mit ihren Listen; die
Gradle-Wrapper-JAR trägt ihre Summe · 1 = eine Abweichung · 2 = gemessen
wurde nichts (keine Datei).

WOFÜR. CLAUDE.md 4 und Lizenzen.md 3 versprechen:
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

SEIT KA-01 AUCH DIE ZWEI, DIE KEIN RIEGEL MASS (F-KA-26). Die Bibliotheken
auf dem Server (`server/vendor/`) haben ihre Summen in zwei Listen
(Lizenzen.md 3a); jede Zeile wird nachgerechnet, und jede Datei des
Verzeichnisses muss in der Liste stehen — sonst schöbe man eine neue
daneben. `Parsedown.php` trägt ihre Summe im Kopf, gerechnet nach dem Rezept
in `server/vendor/HERKUNFT.md`. Die Wrapper-JAR von Gradle bestimmt, was der
Bau lädt und damit die APK, die der Signaturjob unterschreibt; ihr Sollwert
steht hier und in `android/LIESMICH.md`.

WAS NICHT. Die Bilder unter `vendor/leaflet/images/` tragen keinen Kopf und
werden nicht gezählt.
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

# server/vendor/: Liste → das Verzeichnis, das sie vollständig abdeckt.
SERVER_VENDOR = 'server/vendor'
SERVER_LISTEN = {'phpseclib3.sha256': 'phpseclib3',
                 'ParagonIE-ConstantTime.sha256': 'ParagonIE/ConstantTime'}
PARSEDOWN = 'Parsedown.php'
LISTE_RE = re.compile(r'^([0-9a-f]{64})\s+\*?(\S.*)$')

# Die Wrapper-JAR aus Gradle 9.7.1 (android/LIESMICH.md, nachgerechnet am
# 24.09.2026 an der geprüften Verteilung). Wer Gradle hebt, tauscht beide.
WRAPPER = 'android/gradle/wrapper/gradle-wrapper.jar'
WRAPPER_SOLL = '7a9ce74cff467ca1bf60a4fcd9f05185acceda4d0f382434d393e17864262c5d'


def sha(pfad):
    with open(pfad, 'rb') as f:
        return hashlib.sha256(f.read()).hexdigest()


def messen_server(wurzel):
    """(Zahl der Dateien, Befunde) für server/vendor/ und die Wrapper-JAR."""
    befunde = []
    n = 0
    basis = os.path.join(wurzel, SERVER_VENDOR)
    for liste, ordner in SERVER_LISTEN.items():
        lp = os.path.join(basis, liste)
        if not os.path.isfile(lp):
            befunde.append((f'{SERVER_VENDOR}/{liste}', 'Liste fehlt'))
            continue
        gelistet = set()
        with open(lp, encoding='utf-8') as f:
            for nr, zeile in enumerate(f, 1):
                if not zeile.strip():
                    continue
                m = LISTE_RE.match(zeile.rstrip('\n'))
                if not m:
                    befunde.append((f'{SERVER_VENDOR}/{liste}', f'Zeile {nr} unlesbar'))
                    continue
                soll, rel = m.group(1), m.group(2).strip()
                gelistet.add(rel)
                pfad = os.path.join(basis, rel)
                n += 1
                if not os.path.isfile(pfad):
                    befunde.append((f'{SERVER_VENDOR}/{rel}', f'steht in {liste}, fehlt aber'))
                elif sha(pfad) != soll:
                    befunde.append((f'{SERVER_VENDOR}/{rel}', f'SHA-256 weicht von {liste} ab'))
        for o, _, namen in os.walk(os.path.join(basis, ordner)):
            for nm in namen:
                rel = os.path.relpath(os.path.join(o, nm), basis).replace(os.sep, '/')
                if rel not in gelistet:
                    befunde.append((f'{SERVER_VENDOR}/{rel}', f'liegt im Verzeichnis, fehlt in {liste}'))
    pd = os.path.join(basis, PARSEDOWN)
    if os.path.isfile(pd):
        n += 1
        with open(pd, 'rb') as f:
            roh = f.read()
        zeilen = roh.split(b'\n')
        ende = next((i for i, z in enumerate(zeilen) if z == b' */'), None)
        m = SUMME_RE.search(roh[:4000].decode('utf-8', 'replace'))
        if ende is None or not m:
            befunde.append((f'{SERVER_VENDOR}/{PARSEDOWN}', 'Kopf ohne SHA-256 oder ohne Schluss ` */`'))
        else:
            ist = hashlib.sha256(b'<?php\n' + b'\n'.join(zeilen[ende + 1:])).hexdigest()
            if ist != m.group(1):
                befunde.append((f'{SERVER_VENDOR}/{PARSEDOWN}', 'SHA-256 weicht vom Kopf ab'))
    else:
        befunde.append((f'{SERVER_VENDOR}/{PARSEDOWN}', 'fehlt'))
    jar = os.path.join(wurzel, WRAPPER)
    if os.path.isfile(jar):
        n += 1
        if sha(jar) != WRAPPER_SOLL:
            befunde.append((WRAPPER, f'SHA-256 ist nicht {WRAPPER_SOLL[:16]}… (android/LIESMICH.md)'))
    else:
        befunde.append((WRAPPER, 'fehlt'))
    return n, befunde


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
    n2, befunde2 = messen_server(wurzel)
    for rel, text in befunde:
        if os.environ.get('GITHUB_ACTIONS'):
            print(f'::error file={VENDOR}/{rel}::{text}')
        print(f'  ! {rel}: {text}')
    for rel, text in befunde2:
        if os.environ.get('GITHUB_ACTIONS'):
            print(f'::error file={rel}::{text}')
        print(f'  ! {rel}: {text}')
    print(f'{n} Fremdbestandteile unter {VENDOR}/ nachgerechnet, {len(befunde)} Befunde')
    print(f'{n2} Dateien unter {SERVER_VENDOR}/ und die Wrapper-JAR nachgerechnet, '
          f'{len(befunde2)} Befunde')
    if n == 0 or n2 == 0:
        print('Keine Datei gefunden — gemessen wurde nichts.')
        return 2
    return 1 if befunde or befunde2 else 0


def selbstprobe():
    """Eine stimmige Datei; die Summe des Originals mit CRLF; ein falscher Rumpf; kein
    Kopf; Lizenzen.md abweichend oder ohne Zeile; ein leerer Baum."""
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
    # KA-01, F-KA-26: server/vendor/ und die Wrapper-JAR.
    php = b'<?php\nclass A {}\n'
    liste = f'{hashlib.sha256(php).hexdigest()}  phpseclib3/A.php\n'.encode()
    ct = b'<?php\nclass B {}\n'
    liste_ct = f'{hashlib.sha256(ct).hexdigest()}  ParagonIE/ConstantTime/B.php\n'.encode()
    pd_rumpf = b'class Parsedown {}\n'
    pd_summe = hashlib.sha256(b'<?php\n' + pd_rumpf).hexdigest()
    pd = ('<?php\n/*\n * Parsedown 1.7.4\n * SHA-256: ' + pd_summe + '\n */\n').encode() + pd_rumpf
    jar = b'PK\x03\x04probe'
    sv = SERVER_VENDOR

    def server(**ueber):
        b = {f'{sv}/phpseclib3.sha256': liste, f'{sv}/phpseclib3/A.php': php,
             f'{sv}/ParagonIE-ConstantTime.sha256': liste_ct,
             f'{sv}/ParagonIE/ConstantTime/B.php': ct,
             f'{sv}/{PARSEDOWN}': pd, WRAPPER: jar}
        b.update(ueber)
        return {k: v for k, v in b.items() if v is not None}

    global WRAPPER_SOLL
    echt_soll = WRAPPER_SOLL
    WRAPPER_SOLL = hashlib.sha256(jar).hexdigest()
    sfaelle = [
        ('GEGENPROBE: server/vendor/ und JAR stimmen', server(), 0),
        ('server/vendor: eine Datei geändert (F-KA-26)',
         server(**{f'{sv}/phpseclib3/A.php': php + b'// x\n'}), 1),
        ('server/vendor: eine Datei neben der Liste',
         server(**{f'{sv}/phpseclib3/C.php': b'<?php\n'}), 1),
        ('server/vendor: eine gelistete Datei fehlt',
         server(**{f'{sv}/ParagonIE/ConstantTime/B.php': None}), 1),
        ('Parsedown.php: Rumpf geändert',
         server(**{f'{sv}/{PARSEDOWN}': pd + b'// x\n'}), 1),
        ('Wrapper-JAR getauscht (F-KA-26)', server(**{WRAPPER: jar + b'x'}), 1),
        ('Wrapper-JAR fehlt', server(**{WRAPPER: None}), 1),
    ]
    try:
        for name, baum, soll in sfaelle:
            with tempfile.TemporaryDirectory(prefix='fremdsummen-') as d:
                for rel, inhalt in baum.items():
                    os.makedirs(os.path.dirname(os.path.join(d, rel)), exist_ok=True)
                    with open(os.path.join(d, rel), 'wb') as f:
                        f.write(inhalt)
                n, befunde = messen_server(d)
                ist = 2 if n == 0 else (1 if befunde else 0)
            ok = ist == soll
            fehl += not ok
            print(f"  [{'ok  ' if ok else 'FEHL'}] {name}  (erwartet {soll}, gemessen {ist})")
    finally:
        WRAPPER_SOLL = echt_soll
    print(f'{len(faelle) + len(sfaelle)} Fälle, {fehl} Fehlschläge')
    return 1 if fehl else 0


if __name__ == '__main__':
    sys.exit(selbstprobe() if '--selbstprobe' in sys.argv[1:] else lauf(WURZEL))
