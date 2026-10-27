#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Haelt jeden Werkzeugaufruf der Arbeitslaeufe gegen die echte Schnittstelle.

    python3 tools/kettenaufrufe/pruefen.py            # pruefen
    python3 tools/kettenaufrufe/pruefen.py --probe    # Selbstprobe
    python3 tools/kettenaufrufe/pruefen.py --liste    # nur auflisten, was es sieht

Rueckgabewert 0 = keine Befunde, 1 = Befunde, 2 = die Pruefung selbst kaputt.

WOFUER. Die Auslieferungskette ruft Werkzeuge dieses Projekts auf. Ob ein
Aufruf zur Schnittstelle des Werkzeugs passt, zeigte sich bisher erst, wenn
der Schritt LIEF -- und viele Schritte laufen selten: Stufe 2 erst mit einer
eingerichteten Staging-Anlage, der Produktionslauf erst beim ersten Tag.

Am 16./17.09.2026 sind drei Aufrufe beim jeweils ERSTEN echten Lauf
gescheitert, und alle drei hatten gueltiges YAML und saubere Shell-Syntax:

  * `kreislauf.py` ohne das Pflichtargument `--art`, mit einem `--passwort`,
    das es dort nicht gibt.
  * `aufnehmen.mjs` mit `--konto`/`--passwort` -- das Werkzeug kannte beide
    nicht und verwarf sie STILL.
  * `pruefstand.sh aufbau` mit einem Unterbefehl, der `apt-get` ohne `sudo`
    ruft (nicht von dieser Pruefung zu finden, aber derselbe Anlass).

Diese Pruefung faengt die ersten beiden Klassen VOR dem Lauf, in Stufe 1, wo
sie nichts kostet.

WIE SIE DIE SCHNITTSTELLE ERMITTELT -- und was sie dabei NICHT tut: Sie
FUEHRT KEIN WERKZEUG AUS. Ein `--help` in einem Pruefschritt waere ein
Programmstart mit allem, was daran haengt (Datenbank, Netz, Schreibrechte).
Sie LIEST stattdessen den Quelltext:

  .py   `add_argument("--x", …)`, dazu `required=True`
  .mjs  `wert('--x'`, `flag('--x'` und eine etwaige Menge `BEKANNT`
  .sh   die Zweige des `case`-Verteilers und die dort genannten Befehle
  .php  `$argv`-Vergleiche auf `--x`

DIE GRENZE, UND SIE IST BENANNT: Was die Pruefung nicht im Quelltext findet,
kann sie nicht wissen. Ein Werkzeug, das seine Schalter zur Laufzeit
zusammensetzt, ist fuer sie unsichtbar -- sie sagt das dann und zaehlt es als
UNGEPRUEFT, statt zu schweigen. Ein Aufruf mit einer Variablen als Schalter
(`$x --$y`) ebenso.
"""
from __future__ import annotations

import argparse
import pathlib
import re
import shlex
import sys

WURZEL = pathlib.Path(__file__).resolve().parents[2]
# `.yml` UND `.yaml` (Gegenpruefung R17-19): GitHub faehrt beide, und eine neue
# Datei `x.yaml` mit `uses: foo/bar@main` blieb sonst ungesehen.
LAEUFE = sorted([*(WURZEL / '.github' / 'workflows').glob('*.yml'),
                 *(WURZEL / '.github' / 'workflows').glob('*.yaml')])
# SEIT PK-03 AUCH DIE ZUORDNUNG DES PRUEFSTANDS. Sie fuehrt dieselbe Art von
# Aufruf wie ein Kettenschritt — und sie ist beim ersten Lauf an genau
# denselben Fehlern gescheitert (`--format` statt `--art`, ein `./gradlew`
# im falschen Verzeichnis). Wer einen Aufruf dort aendert, faehrt dieses
# Werkzeug davor.
PRUEFABLAUF = WURZEL / 'tools' / 'pruefstand' / 'pruefablauf.json'

# Ein Aufruf sieht so aus: ein Starter (python3/node/php/bash) und danach ein
# Pfad, der auf ein Werkzeug dieses Projekts zeigt -- oder der Pfad allein.
STARTER = r'(?:python3?|node|php|bash|sh)'
PFAD    = r'((?:tools|server|android)/[\w./-]+\.(?:py|mjs|js|php|sh)|\./gradlew)'
# NICHT jeder Pfad in einer Zeile ist ein Aufruf. Zwei Faelle haben die erste
# Fassung dieser Pruefung als "ungeprueft" rauschen lassen, und beide sind
# keine Werkzeugaufrufe:
#
#   "$STAGING_URL/install.php"   -- eine ADRESSE. Davor steht ein `/`, der Pfad
#                                  ist Teil einer URL.
#   php -r "require 'server/version.php'; echo WEB_VERSION;"
#                               -- CODE, kein Dateiaufruf. `-r` (php), `-c`
#                                  (python) und `-e` (node) schalten in den
#                                  Code-Modus; was danach kommt, ist ein
#                                  Programmtext und kein Werkzeug mit
#                                  Schnittstelle.
#
# Ein Aufruf ist der Pfad am Wortanfang -- nicht hinter `/` und nicht hinter
# einem Anfuehrungszeichen, das einen Code-Text eroeffnet.
AUFRUF_RE = re.compile(r'(?:' + STARTER + r'\s+)?(?<![\w/\'"$-])' + PFAD)
CODE_MODUS_RE = re.compile(r'\b(?:php\s+-r|python3?\s+-c|node\s+-e)\b')
# Wo eine Zeile in mehrere Befehle zerfaellt (BR-04). Nur mit Leerraum
# davor und danach -- ein `|` in einem Muster ("a|b") trennt nichts.
KETTE_RE = re.compile(r'\s+(?:&&|\|\||\|)\s+|;\s+')


def melde(t: str = '') -> None:
    print(t)


# ----------------------------------------------------------- Schnittstellen

def schalter_py(quelle: str) -> tuple[set[str], set[str], bool]:
    """(bekannt, pflicht, lesbar) aus argparse UND aus Handparsern.

    Nicht jedes Python-Werkzeug hier nimmt argparse: `wache.py` und
    `kontrast.py` lesen `sys.argv` von Hand. Wer nur `add_argument` sucht,
    haelt beide fuer unlesbar und meldet sie als UNGEPRUEFT -- ehrlich, aber
    unnoetig blind.
    """
    bekannt, pflicht = set(), set()
    gefunden = False
    unter = set(unterbefehle_py(quelle).values())
    for m in re.finditer(r'(\w+)\.add_argument\(\s*([\'"])(--[\w-]+)\2(.*?)\)',
                         quelle, re.S):
        gefunden = True
        bekannt.add(m.group(3))
        # Ein Pflichtschalter EINES UNTERBEFEHLS ist keiner des Werkzeugs —
        # er zählt nur, wenn der Aufruf diesen Unterbefehl nennt (F-PK-32).
        if re.search(r'required\s*=\s*True', m.group(4)) and m.group(1) not in unter:
            pflicht.add(m.group(3))
    if not gefunden:
        # Handparser: '--x' in sys.argv / sys.argv[i] == '--x' / argv.index('--x')
        hand = set(re.findall(r'[\'"](--[\w-]+)[\'"]', quelle))
        if hand and re.search(r'sys\.argv|\bargv\b', quelle):
            return hand, set(), True
    return bekannt, pflicht, gefunden


def unterbefehle_py(quelle: str) -> dict[str, str]:
    """{Unterbefehl: Variablenname} aus den `add_parser`-Aufrufen von argparse."""
    return {m.group(2): m.group(1) for m in re.finditer(
        r'(\w+)\s*=\s*\w+\.add_parser\(\s*[\'"]([\w-]+)[\'"]', quelle)}


def pflicht_je_befehl_py(quelle: str) -> dict[str, set[str]]:
    """{Unterbefehl: seine Pflichtschalter}. Bis PK-05 zählte `required=True` im
    Unterbefehl `schreiben` von `bericht.py` für JEDEN Aufruf, auch für
    `bericht.py lesen` — ein Fehlalarm im Tor (F-PK-32)."""
    je = {}
    for befehl, var in unterbefehle_py(quelle).items():
        je[befehl] = {m.group(1) for m in re.finditer(
            re.escape(var) + r'\.add_argument\(\s*[\'"](--[\w-]+)[\'"]([^)]*?)required\s*=\s*True',
            quelle, re.S)}
    return je


def schalter_mjs(quelle: str) -> tuple[set[str], set[str], bool]:
    """(bekannt, pflicht, lesbar) aus Handparsern und einer BEKANNT-Menge."""
    bekannt = set(re.findall(r'(?:wert|flag)\(\s*[\'"](--[\w-]+)[\'"]', quelle))
    # Eine ausdrueckliche Menge zaehlt mit -- sie ist die verlaesslichere Quelle.
    m = re.search(r'BEKANNT\s*=\s*new Set\(\[(.*?)\]\)', quelle, re.S)
    if m:
        bekannt |= set(re.findall(r'[\'"](--[\w-]+)[\'"]', m.group(1)))
    return bekannt, set(), bool(bekannt)


def schalter_sh(quelle: str) -> tuple[set[str], set[str], bool]:
    bekannt = set(re.findall(r'[\'"]?(--[\w-]+)[\'"]?\s*\)', quelle))
    bekannt |= set(re.findall(r'(--[\w-]+)', quelle))
    return bekannt, set(), bool(bekannt)


def schalter_php(quelle: str) -> tuple[set[str], set[str], bool]:
    bekannt = set(re.findall(r'[\'"](--[\w-]+)[\'"]', quelle))
    return bekannt, set(), bool(bekannt)


LESER = {'.py': schalter_py, '.mjs': schalter_mjs, '.js': schalter_mjs,
         '.sh': schalter_sh, '.php': schalter_php}


def namen_liste(quelle: str) -> list[str]:
    """Die Woerter aller `NAMEN=(…)` und `NAMEN+=(…)`, zeilenweise und
    kommentarfest -- dieselbe Lesart wie `bash_liste()` in
    tools/quelltext/bestand.py. Bis BR-05 las hier ein Muster bis zur ersten
    `)`: Ein Kommentar mit Klammer in der Liste zerlegte sie, und ein Name
    aus `NAMEN+=(…)` galt als unbekannt (Gegenprobe des Bestandsriegels)."""
    woerter: list[str] = []
    offen = False
    for zeile in quelle.split('\n'):
        rest = zeile
        if not offen:
            m = re.match(r'^\s*NAMEN(\+?)=\(', rest)
            if not m:
                continue
            if not m.group(1):
                woerter = []
            rest, offen = rest[m.end():], True
        rest = re.sub(r'(^|\s)#.*$', '', rest)
        if ')' in rest:
            rest, offen = rest[:rest.index(')')], False
        woerter += rest.split()
    return woerter


def befehle_sh(quelle: str) -> set[str]:
    """Unterbefehle eines Shell-Werkzeugs: die Zweige seines case-Verteilers
    (`case "$befehl"` oder `case "$fall"`) und bei den Sammellaeufern unter
    tools/ die Namen aus `NAMEN=(…)` oder den Schluesseln von
    `declare -A RUF=(…)`.

    Bis BR-01 las die Pruefung nur `case "$befehl"` und sah bei
    `pruefen.sh <name>` und `proben.sh <name>` keinen einzigen Namen: Von
    30 solchen Aufrufen (28 in pruefablauf.json, 2 in den Workflows) prueften
    alle nur die Schalter, und ein Tippfehler im Namen waere erst im Lauf
    aufgefallen.
    """
    namen: set[str] = set()
    m = re.search(r'case\s+"\$(?:befehl|fall)"\s+in(.*?)esac', quelle, re.S)
    if m:
        for zweig in re.finditer(r'^\s*([\w|-]+)\)', m.group(1), re.M):
            namen |= {t for t in zweig.group(1).split('|') if t and t != '*'}
    namen |= set(namen_liste(quelle))
    ruf = re.search(r'declare -A RUF=\((.*?)\n\)', quelle, re.S)
    if ruf:
        namen |= set(re.findall(r'\[([\w-]+)\]=', ruf.group(1)))
    return namen


# ------------------------------------------------------------------ Pruefen

def run_bloecke(pfad: pathlib.Path) -> list[tuple[str, str]]:
    """(Schrittname, run-Text) je Schritt -- ohne YAML-Bibliothek, damit die
    Pruefung in Stufe 1 ohne Zusatzpaket laeuft."""
    text = pfad.read_text(encoding='utf-8')
    treffer: list[tuple[str, str]] = []
    name = '(ohne Namen)'
    zeilen = text.split('\n')
    i = 0
    while i < len(zeilen):
        z = zeilen[i]
        mn = re.match(r'\s*-?\s*name:\s*(.+?)\s*$', z)
        if mn:
            name = mn.group(1).strip('"\'')
        mr = re.match(r'(\s*)run:\s*\|?\s*$', z)
        if mr:
            tiefe = len(mr.group(1))
            block, i = [], i + 1
            while i < len(zeilen):
                zz = zeilen[i]
                if zz.strip() and (len(zz) - len(zz.lstrip())) <= tiefe:
                    break
                block.append(zz)
                i += 1
            treffer.append((name, '\n'.join(block)))
            continue
        mr1 = re.match(r'\s*run:\s*(\S.*)$', z)
        if mr1:
            treffer.append((name, mr1.group(1)))
        i += 1
    return treffer


def pruefe_block(lauf: str, schritt: str, block: str) -> tuple[list[str], list[str], int]:
    r"""Befunde, Hinweise und die Zahl der geprueften Aufrufe.

    ZUERST WERDEN FORTSETZUNGSZEILEN VERBUNDEN, und das ist keine Feinheit:
    Der Kreislauf-Aufruf der Kette steht ueber fuenf Zeilen, mit `\` am Ende
    jeder. Wer zeilenweise liest, sieht `kreislauf.py` ohne ein einziges
    Argument und meldet jedes Pflichtargument als fehlend. Die erste Fassung
    dieser Pruefung tat genau das -- drei Befunde beim ersten Lauf, alle drei
    falsch. Eine Pruefung mit Fehlalarmen wird nach dem dritten Mal
    abgeschaltet; das ist der eigentliche Schaden.
    """
    ab: list[str] = []
    hin: list[str] = []
    n = 0
    block = re.sub(r'\\\s*\n\s*', ' ', block)
    for roh in block.split('\n'):
        zeile = roh.strip()
        if not zeile or zeile.startswith('#'):
            continue
        if CODE_MODUS_RE.search(zeile):
            continue          # `php -r "…"` ist Programmtext, kein Aufruf
        # EINE ZEILE KANN MEHRERE AUFRUFE TRAGEN (BR-04). `a && b`, `a || b`,
        # `a | b`, `a; b`: Bis BR-04 nahm die Pruefung je Zeile den ERSTEN
        # Aufruf und rechnete ihm jedes Wort dahinter zu. Der zweite Befehl
        # einer Kette blieb ungeprueft -- ein vertippter Unterbefehl von
        # `pruefstand.sh` hinter `geraeteklassen.py &&` meldete 0 Befunde --,
        # und seine Schalter waeren dem ersten angelastet worden.
        for zeile in KETTE_RE.split(zeile):
            zeile = zeile.strip()
            # Eine Zeile, die AUSGIBT, ruft nicht auf. Die Fehlermeldungen der
            # Kette nennen Werkzeuge beim Namen ("die Datei server/install.php
            # EINMAL von Hand hochladen") -- das ist Prosa, kein Aufruf. Ebenso
            # ein case-Muster (`*install.php*)`).
            # EIN CASE-MUSTER HAT VOR SEINEM `)` KEINE OEFFNENDE KLAMMER. Bis BR-03
            # stand hier `[^|;&]*\)`, und das traf auch `x=$(werkzeug --schalter)`:
            # Jede Befehlsersetzung am Zeilenende ging ungeprueft durch.
            if re.match(r'(?:echo|printf)\b', zeile) or re.match(r'[^|;&(]*\)\s*$', zeile):
                continue
            m = AUFRUF_RE.search(zeile)
            if not m:
                continue
            rel = m.group(1)
            datei = WURZEL / rel
            n += 1

            # `./gradlew` liegt nicht an der Wurzel, sondern in `android/` --
            # der Schritt setzt `working-directory: android`. Diese Pruefung liest
            # kein YAML und kennt das Arbeitsverzeichnis nicht; sie sucht die Datei
            # deshalb auch dort. Schalter hat gradle genug, aber es ist kein
            # Werkzeug DIESES Projekts -- seine Schnittstelle steht nicht hier.
            if rel == './gradlew':
                if not (WURZEL / 'android' / 'gradlew').exists():
                    ab.append(f'{lauf} · {schritt}: android/gradlew fehlt')
                continue
            if not datei.exists():
                ab.append(f'{lauf} · {schritt}: aufgerufene Datei fehlt: {rel}')
                continue

            # Die Argumente hinter dem Pfad zerlegen. Was sich nicht zerlegen
            # laesst (Fortsetzungszeilen, Anfuehrungszeichen ueber Zeilen), wird
            # als UNGEPRUEFT gemeldet und nicht stillschweigend gutgeheissen.
            rest = zeile[m.end():]
            rest = rest.replace('\\', ' ')
            try:
                teile = shlex.split(rest, posix=True)
            except ValueError:
                hin.append(f'{lauf} · {schritt}: {rel} — Argumente nicht zerlegbar, UNGEPRUEFT')
                continue

            endung = datei.suffix
            leser = LESER.get(endung)
            if leser is None:
                hin.append(f'{lauf} · {schritt}: {rel} — Art unbekannt, UNGEPRUEFT')
                continue
            quelle = datei.read_text(encoding='utf-8', errors='replace')
            bekannt, pflicht, lesbar = leser(quelle)
            if not lesbar:
                hin.append(f'{lauf} · {schritt}: {rel} — keine Schnittstelle im '
                           f'Quelltext gefunden, UNGEPRUEFT')
                continue

            benutzt = set()
            for t in teile:
                if not t.startswith('--'):
                    continue
                # `x=$(werkzeug --schalter)`: Die schliessende Klammer der
                # Befehlsersetzung haengt am letzten Wort. Bis BR-03 las die
                # Pruefung `--erster)` als Schalternamen (baumsuche.py).
                name = t.split('=', 1)[0].rstrip(')')
                if '$' in name:          # ein Schalter aus einer Variablen
                    hin.append(f'{lauf} · {schritt}: {rel} — Schalter aus Variable '
                               f'({name}), UNGEPRUEFT')
                    continue
                benutzt.add(name)
                if name not in bekannt:
                    ab.append(f'{lauf} · {schritt}: {rel} kennt {name} nicht — '
                              f'bekannt: {" ".join(sorted(bekannt)) or "(keine)"}')

            if endung == '.py':
                je = pflicht_je_befehl_py(quelle)
                if je:
                    wort = next((t for t in teile if not t.startswith('-')), None)
                    if wort not in je:
                        ab.append(f'{lauf} · {schritt}: {rel} kennt den Befehl '
                                  f'"{wort}" nicht — bekannt: {" ".join(sorted(je))}')
                    else:
                        pflicht = pflicht | je[wort]
            for p in sorted(pflicht - benutzt):
                ab.append(f'{lauf} · {schritt}: {rel} verlangt {p}, der Aufruf '
                          f'uebergibt es nicht')

            # Unterbefehle eines Shell-Werkzeugs
            if endung == '.sh':
                namen = befehle_sh(quelle)
                wort = next((t for t in teile if not t.startswith('-')), None)
                if namen and wort and wort not in namen:
                    ab.append(f'{lauf} · {schritt}: {rel} kennt den Befehl '
                              f'"{wort}" nicht — bekannt: {" ".join(sorted(namen))}')
    return ab, hin, n



# ------------------------------------------- die zwei Riegel der Kette (R17-19)
#
# Anlass: F-R17-16. `CLAUDE.md` 3 nannte zwei Pruefwerte der Kette — „keine
# fremde `uses:`-Zeile ohne SHA" und „sieben Pfade (bis Web 23.3.0 acht), die Zahl ist der Pruefwert"
# — und kein Werkzeug mass sie; nur ein Handbefehl, und diese Anleitung
# behauptete das Gegenteil („jede `uses:`-Zeile"). Beide stehen jetzt hier,
# weil dieses Werkzeug die Arbeitslaeufe ohnehin Zeile fuer Zeile liest und in
# Stufe 1 und im Pruefstand laeuft. Die Funktionen bekommen ihre Eingaben als
# Text — die Selbstprobe legt ihnen damit jeden Fehler hin, ohne das
# Repositorium anzufassen.

# AUCH IN DER FLOW-SCHREIBWEISE (Gegenpruefung R17-19): `- { uses: x@v7 }` ist
# gueltiges YAML und laeuft; der Anker `^\s*(-\s+)?uses:` sah es nicht. Hinter
# `{` oder `,` zaehlt `uses:` deshalb ebenso.
USES_RE = re.compile(r'(?:^\s*(?:-\s+)?|[{,]\s*)uses:\s*([^\s,}]+)(.*)$')
SHA_RE = re.compile(r'^[\w.-]+/[\w.-]+(?:/[\w./-]+)?@[0-9a-f]{40}$')


def fremde_aktionen(texte: dict[str, str]) -> tuple[list[str], int, int]:
    """RIEGEL 1: Haengt jede fremde `uses:`-Zeile an einer 40-stelligen SHA,
    mit der Fassung als Kommentar daneben?

    Lokale Aufrufe (`./.github/workflows/…`) nehmen keinen Ref und sind
    ausgenommen. Ein Tag (`@v7`) oder ein Zweig ist rot: Er kann sich unter
    dem Lauf aendern (E-KH-10). Ohne Kommentar ist die Zeile ebenfalls rot —
    wer eine Aktion aktualisiert, tauscht SHA UND Kommentar, und eine SHA
    ohne Fassung liest niemand nach.

    Rueckgabe: (Befunde, fremde Zeilen gesamt, davon gepinnt).
    """
    ab: list[str] = []
    fremd = gepinnt = 0
    for name, text in sorted(texte.items()):
        for nr, zeile in enumerate(text.split('\n'), 1):
            m = USES_RE.search(zeile)
            if not m or zeile.lstrip().startswith('#'):
                continue
            ziel, rest = m.group(1).strip('\'"'), m.group(2)
            if ziel.startswith('./'):
                continue
            fremd += 1
            if not SHA_RE.match(ziel):
                ab.append(f'{name}:{nr}: `uses: {ziel}` haengt nicht an einer '
                          f'40-stelligen Commit-SHA (E-KH-10)')
            elif not re.search(r'#\s*\S', rest):
                ab.append(f'{name}:{nr}: `uses: {ziel}` ohne Fassung als Kommentar '
                          f'daneben')
            else:
                gepinnt += 1
    return ab, fremd, gepinnt


# DIE SOLLLISTE der Ausnahmen (`CLAUDE.md` 3): drei Dateien, die nur auf dem
# Server liegen oder nicht ausgeliefert werden, und vier Verzeichnisse — je
# als `x/**` und `x/`, weil die Aktion beides getrennt prueft. SIEBEN PFADE.
# Dazu drei allgemeine Muster, die keine Serverpfade sind. Bis Web 23.3.0
# waren es acht: `wartung.lock` und `ueberlast.json` lagen als Dateien im
# Webroot und sind seither das Verzeichnis `.zustand/` (R17-22, F-R17-47).
AUSNAHMEN_DATEIEN = ('config.php', 'install.php', 'install.lock')
AUSNAHMEN_VERZEICHNISSE = ('sicherungen', 'apk', '.sitzungen', '.zustand')
AUSNAHMEN_ALLGEMEIN = ('**/.git*', '**/.git*/**', '**/node_modules/**')
# `install.php` liegt im Repositorium und wird nur nicht ausgeliefert (Nr. 214);
# alle anderen stehen AUCH in `.gitignore`.
NICHT_IN_GITIGNORE = ('install.php',)


def ausnahmen_bloecke(lauf_text: str) -> list[list[str]]:
    """Die Eintraege JEDES Blocks `exclude: |`, in der Reihenfolge der Datei.

    ALLE, nicht der erste (Gegenpruefung R17-19): Bekaeme ein zweiter
    Abgleich-Schritt eine eigene, kuerzere Liste, bliebe sie ungeprueft — und
    genau die zweite Liste schliesst `CLAUDE.md` 3 aus (E-KH-20 (1))."""
    zeilen = lauf_text.split('\n')
    bloecke: list[list[str]] = []
    for i, z in enumerate(zeilen):
        m = re.match(r'^(\s*)exclude:\s*\|\s*$', z)
        if not m:
            continue
        tiefe = len(m.group(1))
        aus: list[str] = []
        for w in zeilen[i + 1:]:
            if not w.strip():
                continue
            if len(w) - len(w.lstrip()) <= tiefe:
                break
            aus.append(w.strip())
        bloecke.append(aus)
    return bloecke


def ausnahmen_lesen(lauf_text: str) -> list[str] | None:
    """Die Eintraege des ersten Blocks `exclude: |` — oder None, wenn es keinen gibt."""
    bloecke = ausnahmen_bloecke(lauf_text)
    return bloecke[0] if bloecke else None


def ausnahmeliste_pruefen(lauf_text: str, ist_ignoriert, getrackt: list[str]
                          ) -> tuple[list[str], int]:
    """RIEGEL 2: Haelt die Ausnahmeliste der Kette ihre Sollliste, steht jeder
    Serverpfad auch in `.gitignore`, und ist keine `server/config.php*`
    eingecheckt?

    `ist_ignoriert(pfad) -> bool` fragt `.gitignore` (im Lauf `git
    check-ignore`, in der Selbstprobe eine Attrappe); `getrackt` sind die
    eingecheckten Pfade unter `server/config.php*`.

    Rueckgabe: (Befunde, Zahl der Serverpfade in der Liste).
    """
    ab: list[str] = []
    ist = ausnahmen_lesen(lauf_text)
    if ist is None:
        return ['ausliefern-lauf.yml: kein Block `exclude: |` gefunden — die '
                'Ausnahmeliste fehlt ganz'], 0
    zahl = len(ausnahmen_bloecke(lauf_text))
    if zahl > 1:
        ab.append(f'ausliefern-lauf.yml: {zahl} Bloecke `exclude: |` — die Liste steht '
                  f'EINMAL (E-KH-20 (1)); zwei Listen sind keine zwei Riegel')
    soll = set(AUSNAHMEN_DATEIEN) | set(AUSNAHMEN_ALLGEMEIN)
    for v in AUSNAHMEN_VERZEICHNISSE:
        soll |= {f'{v}/**', f'{v}/'}
    for fehlt in sorted(soll - set(ist)):
        ab.append(f'Ausnahmeliste: `{fehlt}` fehlt — der naechste Abgleich '
                  f'ueberschriebe oder loeschte, was nur auf dem Server liegt')
    for fremd in sorted(set(ist) - soll):
        ab.append(f'Ausnahmeliste: `{fremd}` steht nicht in der Sollliste — eine '
                  f'Ausnahme liefert still etwas NICHT aus; erst Sollliste und '
                  f'`CLAUDE.md` 3, dann hier')
    pfade = sum(1 for d in AUSNAHMEN_DATEIEN if d in ist) + \
        sum(1 for v in AUSNAHMEN_VERZEICHNISSE if f'{v}/**' in ist and f'{v}/' in ist)
    for d in AUSNAHMEN_DATEIEN:
        if d in NICHT_IN_GITIGNORE:
            continue
        if not ist_ignoriert(f'server/{d}'):
            ab.append(f'.gitignore: `server/{d}` ist nicht ignoriert — ein `git add -A` '
                      f'nahme es mit, und die Kette liefert es aus')
    for v in AUSNAHMEN_VERZEICHNISSE:
        if not ist_ignoriert(f'server/{v}/probe'):
            ab.append(f'.gitignore: `server/{v}/` ist nicht ignoriert')
    for g in getrackt:
        ab.append(f'eingecheckt: `{g}` — eine Kopie der Konfiguration im Baum '
                  f'(F-R17-55); entfernen, und den Zugang darin drehen')
    return ab, pfade


def riegel_lauf() -> tuple[list[str], list[str]]:
    """Beide Riegel gegen dieses Repositorium. Rueckgabe: (Befunde, Zeilen)."""
    import subprocess
    texte = {p.name: p.read_text(encoding='utf-8') for p in LAEUFE}
    ab1, fremd, gepinnt = fremde_aktionen(texte)
    lauf_pfad = WURZEL / '.github' / 'workflows' / 'ausliefern-lauf.yml'
    lauf_text = lauf_pfad.read_text(encoding='utf-8') if lauf_pfad.exists() else ''

    def ist_ignoriert(pfad: str) -> bool:
        r = subprocess.run(['git', '-C', str(WURZEL), 'check-ignore', '-q', pfad])
        return r.returncode == 0

    # UEBERALL, nicht nur `server/config.php*` (Gegenpruefung R17-19): Eine
    # Kopie in `server/api/` oder unter `tools/` ist dasselbe Leck.
    r = subprocess.run(['git', '-C', str(WURZEL), 'ls-files', '--',
                        ':(glob)**/config.php?*', ':(glob)config.php?*'],
                       capture_output=True, text=True)
    getrackt = [z for z in r.stdout.split('\n') if z.strip()]
    ab2, pfade = ausnahmeliste_pruefen(lauf_text, ist_ignoriert, getrackt)
    zeilen = [f'  Fremd-Aktionen:     {fremd} Zeilen, davon an SHA mit Fassung: {gepinnt}',
              f'  Ausnahmeliste:      {pfade} Serverpfade (Soll: '
              f'{len(AUSNAHMEN_DATEIEN) + len(AUSNAHMEN_VERZEICHNISSE)}), '
              f'eingecheckte config.php*: {len(getrackt)}']
    return ab1 + ab2, zeilen


def lauf() -> int:
    if not LAEUFE:
        melde('Keine Arbeitslaeufe unter .github/workflows/ gefunden.')
        return 2
    alle_ab: list[str] = []
    alle_hin: list[str] = []
    ges = 0
    for p in LAEUFE:
        for schritt, block in run_bloecke(p):
            ab, hin, n = pruefe_block(p.name, schritt, block)
            alle_ab += ab
            alle_hin += hin
            ges += n

    proben = 0
    if PRUEFABLAUF.exists():
        import json
        a = json.loads(PRUEFABLAUF.read_text(encoding='utf-8'))
        for name, d in a.get('proben', {}).items():
            ab, hin, n = pruefe_block('pruefablauf.json', name, d['aufruf'])
            alle_ab += ab
            alle_hin += hin
            ges += n
            proben += 1

    # Die zwei Riegel der Kette (R17-19, F-R17-16) — ihre Befunde zaehlen wie
    # die der Aufrufe.
    rab, rzeilen = riegel_lauf()
    alle_ab += rab

    melde('Werkzeugaufrufe der Arbeitslaeufe und des Pruefstands gegen ihre Schnittstellen')
    melde()
    melde(f'  Arbeitslaeufe:      {len(LAEUFE)}')
    melde(f'  Proben (pruefablauf.json): {proben}')
    melde(f'  Aufrufe geprueft:   {ges}')
    melde(f'  Befunde:            {len(alle_ab)}')
    melde(f'  ungeprueft:         {len(alle_hin)}')
    for z in rzeilen:
        melde(z)
    if alle_hin:
        melde()
        melde('UNGEPRUEFT (die Pruefung sagt, was sie NICHT wissen kann):')
        for h in alle_hin:
            melde(f'  · {h}')
    if alle_ab:
        melde()
        melde('BEFUNDE:')
        for a in alle_ab:
            melde(f'  ! {a}')
        return 1
    melde()
    melde('Kein Aufruf widerspricht der Schnittstelle seines Werkzeugs; jede fremde '
          'Aktion haengt an einer SHA, die Ausnahmeliste haelt ihre Sollliste.')
    return 0


# --------------------------------------------------------------- Selbstprobe

def selbstprobe() -> int:
    """Legt der Pruefung die drei Fehler vom 16./17.09.2026 hin.

    Eine Pruefung, die nichts meldet, ist zweideutig: Entweder ist alles gut,
    oder sie sieht an der falschen Stelle hin. Die zweite Lesart schliesst nur
    aus, wer ihr etwas hinlegt, das sie finden MUSS -- und etwas, das sie
    NICHT melden darf.
    """
    faelle = [
        ('kreislauf.py ohne das Pflichtargument --art', True,
         'python3 tools/referenzdatensatz/vergleich/kreislauf.py --basis "$U"'),
        ('kreislauf.py mit einem --passwort, das es nicht gibt', True,
         'python3 tools/referenzdatensatz/vergleich/kreislauf.py --art csv --passwort "$P"'),
        ('aufnehmen.mjs mit --konto und --passwort', True,
         'node tools/screenshots/aufnehmen.mjs --basis "$U" --konto "$K" --passwort "$P"'),
        ('pruefstand.sh mit einem Befehl, den es nicht gibt', True,
         'tools/uhr-pruefstand/pruefstand.sh aufbauen-bitte'),
        ('ein Werkzeug, das es nicht gibt', True,
         'python3 tools/gibtesnicht/probe.py --x'),
        ('GEGENPROBE: der berichtigte Kreislauf-Aufruf', False,
         'python3 tools/referenzdatensatz/vergleich/kreislauf.py --art edbak --frisch '
         '--basis "$U" --admin-email "$K" --admin-passwort "$P"'),
        ('GEGENPROBE: der berichtigte Bilderlauf', False,
         'node tools/screenshots/aufnehmen.mjs --basis "$U" --admin "$K" --admin-pw "$P"'),
        ('GEGENPROBE: ein gueltiger Unterbefehl des Pruefstands', False,
         'tools/uhr-pruefstand/pruefstand.sh aufbau-uebersetzen'),
        ('bericht.py schreiben ohne sein Pflichtargument --stufe', True,
         'python3 tools/pruefstand/bericht.py schreiben --konfiguration web'),
        ('bericht.py mit einem Unterbefehl, den es nicht gibt', True,
         'python3 tools/pruefstand/bericht.py pruefen --commit "$K"'),
        ('GEGENPROBE: bericht.py lesen braucht --stufe nicht (F-PK-32)', False,
         'python3 tools/pruefstand/bericht.py lesen --commit "$K" --basis origin/main'),
        ('Quelltextlaeufer mit einem Namen, den es nicht gibt', True,
         'bash tools/quelltext/pruefen.sh bestnd'),
        ('Probenlaeufer mit einer Probe, die es nicht gibt', True,
         'bash tools/proben/proben.sh wiederherstelung'),
        ('GEGENPROBE: der neunte Name des Quelltextlaeufers (BR-01)', False,
         'bash tools/quelltext/pruefen.sh bestand'),
        ('GEGENPROBE: der letzte Schalter in einer Befehlsersetzung (BR-03)', False,
         'treffer=$(python3 tools/kette/baumsuche.py --baum "$B" --fenster 30 --erster) || rc=$?'),
        ('ein unbekannter Schalter in einer Befehlsersetzung', True,
         'treffer=$(python3 tools/kette/baumsuche.py --baum "$B" --fenster 30 --zuerst)'),
        ('ein unbekannter Befehl HINTER `&&` (BR-04)', True,
         'python3 tools/kette/tor.py --selbstprobe && bash tools/quelltext/pruefen.sh bestnd'),
        ('GEGENPROBE: zwei richtige Aufrufe in einer Kette (BR-04)', False,
         'python3 tools/kette/tor.py --selbstprobe && bash tools/quelltext/pruefen.sh bestand'),
        ('GEGENPROBE: eine Zeile ohne Werkzeugaufruf', False,
         'echo "nichts zu sehen" >> "$GITHUB_STEP_SUMMARY"'),
        ('GEGENPROBE: ein auskommentierter Aufruf', False,
         '# python3 tools/screenshots/aufnehmen.mjs --konto x'),
    ]
    gut = 0
    melde('Selbstprobe der Kettenaufruf-Pruefung')
    melde()
    for name, soll, zeile in faelle:
        ab, _, _ = pruefe_block('probe.yml', 'Probe', zeile)
        ist = bool(ab)
        ok = ist == soll
        gut += ok
        melde(f'  [{"ok " if ok else "FEHL"}] {name:<52} '
              f'{"gemeldet" if ist else "still"} ({len(ab)})')
        if not ok and ab:
            for a in ab:
                melde(f'         {a}')
    # Die Lesart der Namensliste, unmittelbar (BR-05): ein Kommentar mit
    # Klammer darin und ein angehaengtes `NAMEN+=(…)`.
    liste = namen_liste('NAMEN=(eins   # die erste (siehe unten)\n       zwei)\nNAMEN+=(drei)\n')
    ok = liste == ['eins', 'zwei', 'drei']
    gut += ok
    melde(f'  [{"ok " if ok else "FEHL"}] {"NAMEN mit Kommentar (Klammer) und NAMEN+= (BR-05)":<52} '
          f'{" ".join(liste)}')

    # DIE ZWEI RIEGEL DER KETTE (R17-19, F-R17-16). Je Riegel die Fehler,
    # die er finden MUSS, und die Zeile, die er NICHT melden darf.
    sha = 'a' * 40
    riegel1 = [
        ('Riegel 1: Aktion an einem Tag (@v7)', True,
         '      - uses: actions/checkout@v7'),
        ('Riegel 1: Aktion an einem Zweig (@main)', True,
         '        uses: SamKirkland/FTP-Deploy-Action@main'),
        ('Riegel 1: Aktion an einer KURZEN SHA', True,
         f'      - uses: actions/checkout@{sha[:12]} # v7'),
        ('Riegel 1: SHA ohne Fassung als Kommentar', True,
         f'      - uses: actions/checkout@{sha}'),
        ('GEGENPROBE: SHA mit Fassung', False,
         f'      - uses: actions/checkout@{sha} # v7'),
        ('GEGENPROBE: lokaler Aufruf ./.github/…', False,
         '    uses: ./.github/workflows/ausliefern-lauf.yml'),
        ('GEGENPROBE: auskommentierte uses-Zeile', False,
         '      # - uses: actions/checkout@v7'),
        ('Riegel 1: Flow-Schreibweise `- { uses: …@v7 }`', True,
         '      - { uses: actions/checkout@v7 }'),
        ('GEGENPROBE: Flow-Schreibweise an SHA mit Fassung', False,
         f'      - {{ name: x, uses: actions/checkout@{sha} }} # v7'),
    ]
    for name, soll, zeile in riegel1:
        ab, _, _ = fremde_aktionen({'probe.yml': zeile})
        ok = bool(ab) == soll
        gut += ok
        melde(f'  [{"ok " if ok else "FEHL"}] {name:<52} '
              f'{"gemeldet" if ab else "still"} ({len(ab)})')

    liste_gut = ('        exclude: |\n'
                 + ''.join(f'          {e}\n' for e in AUSNAHMEN_ALLGEMEIN + AUSNAHMEN_DATEIEN)
                 + ''.join(f'          {v}/**\n          {v}/\n' for v in AUSNAHMEN_VERZEICHNISSE)
                 + '      - name: weiter\n')
    alles_ignoriert = lambda _p: True
    riegel2 = [
        ('GEGENPROBE: Sollliste vollstaendig, alles ignoriert', False,
         liste_gut, alles_ignoriert, []),
        ('Riegel 2: `apk/` fehlt (nur `apk/**` steht da)', True,
         liste_gut.replace('          apk/\n', ''), alles_ignoriert, []),
        ('Riegel 2: `.sitzungen/**` fehlt', True,
         liste_gut.replace('          .sitzungen/**\n', ''), alles_ignoriert, []),
        ('Riegel 2: eine Ausnahme ausserhalb der Sollliste', True,
         liste_gut.replace('          apk/\n', '          apk/\n          assets/**\n'),
         alles_ignoriert, []),
        ('Riegel 2: `server/install.lock` nicht in .gitignore', True,
         liste_gut, lambda pf: pf != 'server/install.lock', []),
        ('Riegel 2: `server/.zustand/` nicht in .gitignore', True,
         liste_gut, lambda pf: pf != 'server/.zustand/probe', []),
        ('Riegel 2: `.zustand/` fehlt (nur `.zustand/**` steht da)', True,
         liste_gut.replace('          .zustand/\n', ''), alles_ignoriert, []),
        ('Riegel 2: `install.php` darf in .gitignore FEHLEN (Nr. 214)', False,
         liste_gut, lambda pf: pf != 'server/install.php', []),
        ('Riegel 2: `config.php.vor-probe` eingecheckt (F-R17-55)', True,
         liste_gut, alles_ignoriert, ['server/config.php.vor-probe']),
        ('Riegel 2: kein Block `exclude: |`', True,
         '      - name: nur ein Schritt\n', alles_ignoriert, []),
        ('Riegel 2: eine ZWEITE Ausnahmeliste', True,
         liste_gut + liste_gut, alles_ignoriert, []),
    ]
    for name, soll, text, ign, getr in riegel2:
        ab, _ = ausnahmeliste_pruefen(text, ign, getr)
        ok = bool(ab) == soll
        gut += ok
        melde(f'  [{"ok " if ok else "FEHL"}] {name:<52} '
              f'{"gemeldet" if ab else "still"} ({len(ab)})')
        if not ok and ab:
            for a in ab:
                melde(f'         {a}')
    _, pfade = ausnahmeliste_pruefen(liste_gut, alles_ignoriert, [])
    ok = pfade == 7
    gut += ok
    melde(f'  [{"ok " if ok else "FEHL"}] {"Die Sollliste zaehlt SIEBEN Serverpfade (CLAUDE.md 3)":<52} {pfade}')

    gesamt = len(faelle) + 1 + len(riegel1) + len(riegel2) + 1
    melde()
    melde(f'  -> {gut} von {gesamt} Faellen erwartungsgemaess, {gesamt - gut} nicht.')
    return 0 if gut == gesamt else 2


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('--probe', action='store_true', help='Selbstprobe fahren')
    p.add_argument('--liste', action='store_true', help='nur auflisten, was gesehen wird')
    a = p.parse_args()
    if a.probe:
        return selbstprobe()
    if a.liste:
        for f in LAEUFE:
            for schritt, block in run_bloecke(f):
                for roh in block.split('\n'):
                    z = roh.strip()
                    if z and not z.startswith('#') and AUFRUF_RE.search(z):
                        melde(f'{f.name} · {schritt}: {z[:100]}')
        return 0
    return lauf()


if __name__ == '__main__':
    sys.exit(main())
