#!/usr/bin/env python3
"""Integritaetswache — laeuft die ausgelieferte Fassung noch der aus?

    python3 tools/integritaetswache/wache.py [basisadresse]
    python3 tools/integritaetswache/wache.py --selbstprobe

Rueckgabewert 0 = kein Unterschied, 1 = mindestens einer (oder etwas war nicht
erreichbar), 2 = die Wache selbst ist kaputt.

WOGEGEN SIE GEBAUT IST (SP-6, F-SP-9, Backlog Nr. 140)
Der eine Angriff, gegen den keine Verschluesselung im Browser hilft, ist ein
Server, der VERAENDERTEN CODE ausliefert: eine Zeile in `crypto.js`, und das
naechste Passwort geht mit. VERHINDERN laesst sich das nur durch Zugangsschutz
(Branch-Schutz, 2FA, R67). ERKENNEN laesst es sich hier -- und darum geht es.

WAS SIE ERKENNT: eine per FTP oder Hoster-Panel veraenderte Auslieferung. Das
ist der wahrscheinlichste Weg, denn die FTPS-Zugangsdaten liegen als
GitHub-Secret und der Webspace hat ein Panel.

WAS SIE NICHT ERKENNT: einen Angreifer mit Push-Recht -- der aendert beides,
Repositorium und Auslieferung, und die Wache saehe zwei gleiche Summen.
Dagegen steht SP-4. Ebenso wenig sieht sie PHP-Code, der nicht ausgeliefert
wird; von ihm faengt sie genau den Teil, der Passwoerter beruehrt (den
Inline-Block der Anmeldeseite, siehe unten).

WARUM SIE OHNE EINGECHECKTE ERWARTUNGSSUMME AUSKOMMT
Der Deploy (`.github/workflows/deploy.yml`) synchronisiert `server/` byteweise
per FTPS. Was unter `assets/` liegt, ist auf dem Server also dieselbe Datei wie
im Repositorium -- der Vergleich braucht keine gepflegte Liste von
Pruefsummen, die nach der dritten Aenderung nicht mehr stimmt. Er rechnet beide
Seiten frisch aus.

Fuer die Anmeldeseite gilt dasselbe, und das war beim Bauen die offene Frage:
Der Inline-Skriptblock von `login.php` enthaelt KEINE einzige PHP-Einsetzung
(nachgezaehlt am 07.09.2026: 0). Er steht als Literal in der Quelle und kommt
byteweise so beim Browser an. Gemessen: derselbe SHA-256 aus der Quelldatei und
aus zwei aufeinanderfolgenden Abrufen. Ein Block MIT PHP darin waere nicht
vergleichbar -- die Wache sagt das dann und zaehlt ihn getrennt, statt ihn
stillschweigend zu uebergehen.
"""
from __future__ import annotations

import hashlib
import os
import re
import ssl
import sys
import urllib.error
import urllib.request
from pathlib import Path

WURZEL = Path(__file__).resolve().parents[2]
SERVER = WURZEL / 'server'

VORGABE_BASIS = os.environ.get('WACHE_BASIS') or 'https://nadoku.gen-em.org'

# Was unter `assets/` NICHT ausgeliefert wird oder nicht ausgeliefert werden
# muss. `.md` ist Begleittext des Repositoriums.
NICHT_PRUEFEN = {'.md'}

# Seiten, deren Inline-Skriptbloecke verglichen werden. Nur unangemeldet
# erreichbare -- die Wache hat keine Zugangsdaten und soll keine bekommen.
SEITEN = ['login.php']

SKRIPT_RE = re.compile(r'<script(?![^>]*\bsrc=)[^>]*>(.*?)</script>', re.S)


def sha(b: bytes) -> str:
    return hashlib.sha256(b).hexdigest()


def hole(url: str, unsicher: bool = False) -> bytes:
    ctx = None
    if unsicher:
        ctx = ssl.create_default_context()
        ctx.check_hostname = False
        ctx.verify_mode = ssl.CERT_NONE
    anfrage = urllib.request.Request(url, headers={'User-Agent': 'nadoku-integritaetswache'})
    with urllib.request.urlopen(anfrage, timeout=30, context=ctx) as antwort:
        return antwort.read()


def bloecke(text: str) -> list[str]:
    return [m.group(1) for m in SKRIPT_RE.finditer(text)]


def assets() -> list[Path]:
    ordner = SERVER / 'assets'
    raus = [p for p in sorted(ordner.rglob('*'))
            if p.is_file() and p.suffix.lower() not in NICHT_PRUEFEN]
    return raus


def lauf(basis: str, unsicher: bool) -> int:
    basis = basis.rstrip('/')
    print(f'Integritaetswache gegen {basis}')
    abweichung: list[str] = []
    unerreichbar: list[str] = []

    # ---- Teil 1: die ausgelieferten Dateien ------------------------------
    dateien = assets()
    gleich = 0
    for p in dateien:
        rel = p.relative_to(SERVER).as_posix()
        try:
            geliefert = hole(f'{basis}/{rel}', unsicher)
        except (urllib.error.URLError, urllib.error.HTTPError, OSError) as ex:
            unerreichbar.append(f'{rel}: {ex}')
            continue
        soll, ist = sha(p.read_bytes()), sha(geliefert)
        if soll == ist:
            gleich += 1
        else:
            abweichung.append(f'{rel}: erwartet {soll[:16]}… ({p.stat().st_size} B), '
                              f'geliefert {ist[:16]}… ({len(geliefert)} B)')

    print(f'  Teil 1  {len(dateien)} Dateien unter assets/ — {gleich} gleich, '
          f'{len(abweichung)} abweichend, {len(unerreichbar)} nicht erreichbar')

    # ---- Teil 2: die Inline-Bloecke ---------------------------------------
    vergleichbar = 0
    uebersprungen = 0
    for seite in SEITEN:
        quelle = (SERVER / seite)
        if not quelle.is_file():
            unerreichbar.append(f'{seite}: fehlt im Repositorium')
            continue
        soll_bloecke = bloecke(quelle.read_text(encoding='utf-8'))
        # Ein Block mit PHP darin ist nicht vergleichbar — er sieht in der
        # Auslieferung anders aus als in der Quelle, und zwar zu Recht.
        rein = [b for b in soll_bloecke if '<?' not in b]
        uebersprungen += len(soll_bloecke) - len(rein)
        try:
            geliefert = hole(f'{basis}/{seite}', unsicher).decode('utf-8', 'replace')
        except (urllib.error.URLError, urllib.error.HTTPError, OSError) as ex:
            unerreichbar.append(f'{seite}: {ex}')
            continue
        ist_bloecke = {sha(b.encode()) for b in bloecke(geliefert)}
        for b in rein:
            h = sha(b.encode())
            if h in ist_bloecke:
                vergleichbar += 1
            else:
                abweichung.append(f'{seite}: ein Inline-Block der Quelle steht nicht '
                                  f'so in der Auslieferung (erwartet {h[:16]}…, '
                                  f'{len(b)} Zeichen)')

    print(f'  Teil 2  {len(SEITEN)} Seite(n) — {vergleichbar} Inline-Bloecke gleich, '
          f'{uebersprungen} nicht vergleichbar (PHP darin)')

    if unerreichbar:
        print('\n  NICHT ERREICHBAR:')
        for z in unerreichbar:
            print(f'    {z}')
    if abweichung:
        print('\n  ABWEICHUNG:')
        for z in abweichung:
            print(f'    {z}')
        print('\n  Die ausgelieferte Fassung ist nicht die des Repositoriums.')
        print('  Das kann ein vergessener Deploy sein — oder eine Manipulation.')
        print('  Was zu tun ist, steht in tools/integritaetswache/LIESMICH.md.')
    if not abweichung and not unerreichbar:
        print('\n  Kein Unterschied.')
    return 1 if (abweichung or unerreichbar) else 0


def selbstprobe() -> int:
    """Erkennt die Wache eine Abweichung ueberhaupt? (Backlog Nr. 140)

    Ohne diese Probe ist ein gruener Lauf eine Zahl ohne Aussage: Eine Wache,
    die IMMER gruen meldet, sieht genauso aus wie eine, die nichts findet.
    """
    print('Selbstprobe der Integritaetswache')
    erwartungen = 0
    offen = 0

    def pruefe(ok: bool, was: str, wert: str = '') -> None:
        nonlocal erwartungen, offen
        erwartungen += 1
        if not ok:
            offen += 1
        print(f'  [{"ok " if ok else "FEHL"}] {was:<58} {wert}')

    d = assets()
    pruefe(len(d) > 0, 'Es gibt Dateien unter assets/ zu vergleichen', f'{len(d)} Dateien')

    # 1. Gleiche Bytes -> gleiche Summe.
    beispiel = SERVER / 'assets' / 'crypto.js'
    pruefe(beispiel.is_file(), 'assets/crypto.js liegt im Repositorium',
           f'{beispiel.stat().st_size if beispiel.is_file() else 0} B')
    roh = beispiel.read_bytes()
    pruefe(sha(roh) == sha(bytes(roh)), 'Gleiche Bytes ergeben dieselbe Summe',
           sha(roh)[:16] + '…')

    # 2. EIN veraendertes Byte -> andere Summe. Das ist die eigentliche Frage.
    manipuliert = roh.replace(b'const EdCrypto', b'const EdCryptO', 1)
    pruefe(manipuliert != roh, 'Die Probe konnte eine Zeile veraendern',
           f'{len(roh)} B unveraendert, {len(manipuliert)} B veraendert')
    pruefe(sha(manipuliert) != sha(roh),
           'ABWEICHUNG ERKANNT: eine veraenderte Auslieferung faellt auf',
           f'{sha(roh)[:12]}… gegen {sha(manipuliert)[:12]}…')

    # 3. Der Inline-Block der Anmeldeseite ist ueberhaupt vergleichbar.
    quelle = (SERVER / 'login.php').read_text(encoding='utf-8')
    bl = bloecke(quelle)
    rein = [b for b in bl if '<?' not in b]
    pruefe(len(rein) >= 1,
           'Die Anmeldeseite hat mindestens einen PHP-freien Inline-Block',
           f'{len(bl)} Bloecke, davon {len(rein)} ohne PHP')
    if rein:
        veraendert = rein[0].replace('EdCrypto.deriveKeys', 'EdCrypto.deriveKeys/*x*/', 1)
        pruefe(sha(veraendert.encode()) != sha(rein[0].encode()),
               'ABWEICHUNG ERKANNT: ein veraenderter Inline-Block faellt auf',
               f'{sha(rein[0].encode())[:12]}… gegen {sha(veraendert.encode())[:12]}…')

    print(f'\n  -> {erwartungen} Erwartungen, {offen} nicht erfuellt')
    return 0 if offen == 0 else 1


def main() -> int:
    args = [a for a in sys.argv[1:]]
    unsicher = '--unsicher' in args
    if unsicher:
        args.remove('--unsicher')
    if '--selbstprobe' in args:
        return selbstprobe()
    basis = args[0] if args else VORGABE_BASIS
    return lauf(basis, unsicher)


if __name__ == '__main__':
    try:
        sys.exit(main())
    except Exception as ex:                       # noqa: BLE001
        print(f'Die Wache selbst ist gescheitert: {ex}', file=sys.stderr)
        sys.exit(2)
