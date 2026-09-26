#!/usr/bin/env python3
"""Der Bestandsriegel — hält der Werkzeugbestand unter tools/ die Regeln aus
docs/Pruefablauf.md 6?

    python3 tools/quelltext/bestand.py
    python3 tools/quelltext/bestand.py --selbstprobe
    python3 tools/quelltext/bestand.py --wurzel <anderer Auscheck>

Rückgabewert 0 = keine Befunde · 1 = Befunde · 2 = die Prüfung selbst kam
nicht zum Laufen (kein tools/, kein Backlog; in der Selbstprobe: ein
Werkzeug fehlt).

WOFÜR (Konzept BR). PK hat den DURCHLAUF einer Änderung als Riegel gebaut
und den BESTAND als Regel gelassen: Anlass-Zeile, Fünf-Abschnitte-Form mit
höchstens 40 Zeilen, Streichliste. Drei Tage nach PK-04 standen die Zahlen
unter den Regeln, und niemand hatte es gemerkt, weil es niemand zählte. Eine
Regel ohne Messung ist eine Hoffnung.

WAS ER MISST — dreizehn Regeln, jede mit Namen im Befund:

  form       jede LIESMICH.md unter tools/, auch in Unterordnern (E-BR-04):
             genau die fünf Abschnitte aus 6.2 in dieser Reihenfolge,
             höchstens 40 Zeilen
  anleitung  jeder Ordner direkt unter tools/ hat eine LIESMICH.md
  anlass     deren Anleitung trägt GENAU EINE Zeile, die mit „Anlass:"
             beginnt und mindestens eine Backlog-Nummer nennt, die es gibt
             (E-BR-07) — oder die Erzeugerform (E-BR-05), die einzige andere
  inventur   der Ordner wird gerufen: `tools/<ordner>/` steht in
             pruefablauf.json, in einem Workflow, in tools/pruefstand/
             pruefen.sh oder in docs/Sandbox-Setup.md (E-BR-03 (3))
  lose       keine Datei direkt unter tools/ außer motor.mjs (E-BR-03 (4))
  probe      jede Probe in tools/proben/proben.sh hat eine Einstiegsdatei,
             und deren Kopfkommentar trägt genau eine Anlass-Zeile mit
             Backlog-Nummer; kein Probenordner ohne Eintrag im Läufer
  backlog    keine Nummer zweimal, über docs/Backlog.md UND
             docs/Backlog-Erledigt.md zusammen (seit SD-02) — bis BR-03 ein
             eigener Schritt in Stufe 1, den Station B nie fuhr (F-BR-03)

Die vier letzten sind die Schritte beim Einhängen eines Prüfmittels, deren
Fehlen bis BR-05 niemand meldete (Nr. 315, gefunden von der Gegenlesung des
Runbooks durch Befolgen, F-BR-19):

  selbst     eine Quelltextprüfung, deren CODE den Schalter --selbstprobe
             auswertet, steht in SELBST von pruefen.sh — sonst läuft ihre
             Selbstprobe nirgends; umgekehrt hat jeder Name in SELBST eine;
             eine Selbstprobe hinter --probe oder in einer Datei, die NAMEN
             nicht kennt, ist ein Befund; die Datei, die starter() startet,
             gibt es, und sie ist Python oder PHP; und `pruefen.sh
             --selbstprobe` steht in einem Workflow oder einem Aufruf
  zeile      jeder Name in NAMEN hat genau eine Zeile in der Tabelle von
             tools/quelltext/LIESMICH.md, so wie GitHub sie zeigt, mit Anlass
             in der Spalte „Anlass"; keine Zeile ohne oder mit fremdem Namen
             und keine mit mehr Zellen als die Kopfzeile. Die Spalte ist
             KEINE Backlog-Nummer (E-BR-07)
  ablauf     pruefablauf.json ist vollständig und so, wie auswahl.py und das
             Tor es lesen: jede Probe erreichbar, jede Probe der Läufer
             aufgerufen, jeder Name definiert, kein Kreis in `nach`, keine
             Schlüssel, die niemand liest, jedes Muster mit seinen fünf
             Feldern, einer Stufe und Pfaden, die eine Datei treffen; jede
             Datei einer Fläche des Berichts wählt schon in der kleinsten
             Stufe deren Bauprobe aus
  tabelle    Abschnitt 4 von docs/Pruefablauf.md trägt genau einmal die
             Ausgabe von `bericht.py erzeugen-doku`, Zeile für Zeile, und
             nirgends eine veraltete Kopie davon — erkannt an den ersten zwei
             Zellen einer Zeile oder am fetten Vorsatz, nicht an Gleichheit

Die zwei letzten halten den Prüfstand an das Tor (Nr. 329, R4-02): Örtlich
steht die Anlage, in Stufe 1 nicht — was dazwischen fällt, war im Prüfstand
grün und im Pull Request rot, nach sechs Läufen.

  anlage     keine Probe mit `braucht: nichts` lädt über ihre require-Kette
             auf oberster Ebene server/db.php oder server/config.php — db.php
             bricht ohne config.php ab, und Stufe 1 hat keine. Gelesen mit
             token_get_all; Pfade werden nur aus Zeichenketten, __DIR__,
             __FILE__, dirname(), realpath() und so gebauten Variablen
             ausgewertet — alles andere ist „nicht auflösbar", ein Befund
  tor        jeder Riegel aus pruefablauf.json steht als `--riegel NAME=` im
             Aufruf `bericht.py lesen --alle-riegel` von
             .github/workflows/pruefung.yml, und dort steht kein fremder
             Name — sonst ist der Riegel im Tor rot, und örtlich fährt diesen
             Schritt niemand (F-P5c-172). Die Datei wird gelesen, nicht
             geschrieben

WIE ER LIEST — MIT DEN ECHTEN WERKZEUGEN, NICHT MIT NACHBAUTEN (BR-05).
Drei Gegenprüfrunden haben gezeigt: Wer Markdown, PHP und Bash mit eigenen
Mustern nachliest, liest jede Runde eine andere Randschreibweise anders als
das Original. Deshalb: Tabellen über `cmark-gfm` (wie GitHub), PHP über
`token_get_all` (wie PHP), die Listen der Läufer über `--liste` (wie bash),
Pfadmuster über `auswahl.passt()` und die Auswahl über `auswahl.treffer()`
(wie der Prüfstand), die Flächen aus dem geladenen bericht.py. Eine
Quelltextprüfung in einer anderen Sprache als Python oder PHP ist ein Befund,
keine Schätzung. Fehlt `cmark-gfm` oder `php`, ist das ein Befund der Regel,
nicht grün. Die vierte Runde fand danach keine Randschreibweise mehr, sondern
Lücken im Umfang — sie sind zu, oder sie stehen unten als Grenze.

JEDE BEFUNDSTELLE HAT EINE KENNUNG (KENNUNGEN unten), und die Selbstprobe
schlägt an, wenn eine Kennung in keinem Fall fällt. Bis Runde 3 ließen sich
23 von 67 Befundstellen streichen, ohne dass die Selbstprobe es merkte.

WAS ER NICHT MISST, und das ist die benannte Grenze: den Inhalt einer
Anleitung, ob der Anlass zur Probe passt, ob ein Werkzeug überflüssig ist.
Das bleibt Lesearbeit (docs/Pruefablauf.md 6.1). Positionsargumente eines
Aufrufs sieht er so wenig wie kettenaufrufe — der Aufruf wird einmal von
Hand gefahren (6.12, Nr. 316). Eine Selbstprobe, die ein gemeinsamer Rahmen
auswertet (E-PK-24, nicht gebaut), sieht er nicht. Der Erzeuger der Tabelle
maskiert kein `|` und kein `*` — was er ausgibt, beglaubigt der Riegel.
Aus Runde 4 benannt statt gebaut (Konzept BR, Abschnitt 7 — gelöscht, in der
Historie unter d4e96e6): Den Schalter liest er nur
als Zeichenkette in der Datei selbst — in einer Konstanten für getopt() oder
in einem Hilfsmodul sieht er ihn nicht; Unterordner von tools/quelltext/
durchsucht er nicht nach fremden Selbstproben; Konstanten in auswahl.py, die
eine Muster-id nennen, gleicht er nicht ab; `Nr. 12a` liest er als Nr. 12.
Ein Hilfsmodul in tools/quelltext/, das den Schalter als Datum trägt, gilt
als Datei mit Selbstprobe — rot zu Unrecht, nicht still. Eine Endmarke unter
der erzeugten Tabelle (wie in Design.md) zählt als zweiter Block, ebenso rot.
Zwei Fälle der Selbstprobe ändern den Erzeuger selbst und greifen dafür in
seinen Text; ändert der sich, melden sie „Anker fehlt", rot und benannt.
Zur Regel `anlage`: Ein require im Rumpf einer Funktion folgt er nicht — es
läuft erst beim Aufruf, und ob der Riegel die Funktion ruft, sieht er nicht.
Python-Proben liest er nicht auf PHP, das sie starten. Zur Regel `tor`: ob
der Wert hinter `NAME=` der richtige Schritt ist, liest er nicht.

GELESEN WIRD, WAS `git add -A` IN DEN BAUM LEGTE: versionierte und neue,
nicht ignorierte Dateien. Eine Ausgabe wie tools/screenshots/ausgabe/ zählt
nicht mit — sonst hinge die Zahl davon ab, wer wo zuletzt gemessen hat.

KEINE DECKE, KEIN ALTBESTAND (E-BR-01). Der Riegel steht auf null, weil der
Altbestand im selben Konzept bereinigt wird. Wer hier eine Ausnahmeliste
einführt, führt die Streichliste wieder ein, die niemand liest.
"""
import argparse
import html.parser
import importlib.util
import json
import os
import re
import shutil
import subprocess
import sys
import tempfile

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))

ABSCHNITTE = ['Aufruf', 'Was es misst', 'Was es braucht', 'Erwartete Zahl',
              'Was es nicht kann']          # docs/Pruefablauf.md 6.2
HOECHSTENS = 40
ERZEUGER = 'Anlass: entfällt — Erzeuger (E-BR-05)'
LOSE_ERLAUBT = {'motor.mjs'}                 # E-BR-03 (4), beim Namen — kein zweiter
# Wo ein Ordner gerufen sein kann (E-BR-03 (3)). Workflows kommen als Muster dazu.
RUFER = ['tools/pruefstand/pruefablauf.json', 'tools/pruefstand/pruefen.sh',
         'docs/Sandbox-Setup.md']
REGELN = ['form', 'anleitung', 'anlass', 'inventur', 'lose', 'probe', 'backlog',
          'selbst', 'zeile', 'ablauf', 'tabelle', 'anlage', 'tor']
PROBEN_LAEUFER = 'tools/proben/proben.sh'
QUELLTEXT = 'tools/quelltext/pruefen.sh'
QUELLTEXT_ANLEITUNG = 'tools/quelltext/LIESMICH.md'
ABLAUF = 'tools/pruefstand/pruefablauf.json'
ERZEUGER_DOKU = 'tools/pruefstand/bericht.py'
AUSWAHL = 'tools/pruefstand/auswahl.py'
PRUEFSTAND = 'tools/pruefstand/pruefen.sh'
TOR = '.github/workflows/pruefung.yml'
# Was ohne config.php beim Laden abbricht (db.php prüft es in seinen ersten
# Zeilen, Nr. 288) — erreicht eine Probe ohne Anlage eines davon, ist sie im
# Tor rot.
ANLAGE = ('server/db.php', 'server/config.php')
PRUEFABLAUF = 'docs/Pruefablauf.md'
MUSTER_FELDER = ['id', 'ab', 'pfade', 'proben', 'anlass']   # auswahl.py und erzeugen-doku lesen alle fünf
# Die Schlüssel, die jemand liest (gemessen am Bestand 24.09.2026). Ein anderer
# — `nach` am Muster, `Nach` an der Probe — wurde still übergangen, und die
# Voraussetzung fehlte ohne Meldung (Gegenprobe BR-05, Runde 3).
SCHLUESSEL = {
    # `stufenregeln` lesen auswahl.py und bericht.py seit P5c/AP4 (E-P5c-88, Stufenregel
    # `migration`); beim Aufnehmen von BR in den P5c-Zweig eingetragen.
    'oben': {'beschreibung', 'fassung', 'muster', 'proben', 'riegel', 'stufen', 'stufenregeln'},
    'riegel': {'beschreibung', 'proben'},
    'probe': {'aufruf', 'bemerkung', 'braucht', 'demo', 'nach'},    # `demo`: R4-04, Nr. 322
    'muster': set(MUSTER_FELDER),
}
QUELLTEXT_SPRACHEN = ('.py', '.php')          # was der Riegel lesen kann — alles andere ist ein Befund
DATEN_ENDUNGEN = ('.md', '.json', '.txt', '.csv', '.yml', '.yaml')
PAAR_RE = re.compile(r'[a-zA-Z][\w-]*')        # so liest das Tor einen Namen im Bericht (bericht.py)
ERZEUGT_RE = re.compile(r'^<!-- ERZEUGT von tools/pruefstand/bericht\.py erzeugen-doku(?![\w-])')

ZAUN_AUF_RE = re.compile(r'^( {0,3})(`{3,}|~{3,})(.*)$')
ABSCHNITT_RE = re.compile(r'^##(?!#)\s*(.+?)\s*$')
NUMMER_RE = re.compile(r'Nr\.\s*(\d+(?:\s*(?:,|und)\s*\d+)*)')
BACKLOG_RE = re.compile(r'^(\d+)\.')
# Was vor „Anlass:" stehen darf: Kommentarzeichen und Hervorhebung, sonst nichts.
VORSATZ_RE = re.compile(r'^\s*(?:>\s*)?(?:/\*+|\*+(?!\*)|//+|#+|<!--)?\s*[*_]*\s*')

# Jede Befundstelle — und die Selbstprobe muss jede mindestens einmal auslösen.
KENNUNGEN = {
    'form-zeilen': 'Anleitung über 40 Zeilen',
    'form-abschnitte': 'nicht die fünf Abschnitte in dieser Reihenfolge',
    'anleitung-fehlt': 'Ordner ohne LIESMICH.md',
    'anlass-keine': 'keine Zeile, die mit „Anlass:" beginnt',
    'anlass-mehrere': 'mehr als eine Anlass-Zeile',
    'anlass-keine-nummer': 'Anlass ohne Backlog-Nummer',
    'anlass-nummer-fehlt': 'Nummer steht nicht im Backlog',
    'inventur-ungerufen': 'Ordner wird nirgends gerufen',
    'lose-datei': 'lose Datei unter tools/',
    'probe-laeufer': 'Läufer fehlt oder --liste liefert nichts',
    'probe-einstieg-unklar': 'Einstiegsdatei nicht ermittelbar',
    'probe-datei-fehlt': 'Einstiegsdatei fehlt',
    'probe-keine-zeile': 'keine Anlass-Zeile im Kopf',
    'probe-mehrere': 'mehrere Anlass-Zeilen im Kopf',
    'probe-keine-nummer': 'Anlass im Kopf ohne Backlog-Nummer',
    'probe-nummer-fehlt': 'Nummer im Kopf steht nicht im Backlog',
    'probe-erzeuger': 'Erzeugerform in einer Probe',
    'probe-ordner-ohne-eintrag': 'Probenordner ohne Eintrag im Läufer',
    'backlog-doppelt': 'Nummer zweimal',
    'selbst-laeufer': 'pruefen.sh fehlt oder --liste liefert nichts',
    'selbst-keine-namen': '--liste nennt keinen Namen',
    'selbst-abbruch': 'die Gruppe selbst/zeile brach ab',
    'selbst-tor': 'pruefen.sh --selbstprobe wird nirgends gerufen',
    'selbst-werkzeug': 'php fehlt oder scheitert',
    'selbst-befehl-ohne-datei': 'starter() nennt keine Datei',
    'selbst-datei-fehlt': 'die Datei, die starter() startet, fehlt',
    'selbst-sprache': 'starter() startet eine Datei, die weder Python noch PHP ist',
    'selbst-sprache-datei': 'Quelltext in tools/quelltext/ weder Python noch PHP',
    'selbst-probe-schalter': 'Selbstprobe hinter --probe',
    'selbst-nicht-in-selbst': 'Selbstprobe, aber nicht in SELBST',
    'selbst-ohne-auswertung': 'in SELBST, aber keine Selbstprobe',
    'selbst-ohne-namen': 'in SELBST, aber nicht in NAMEN',
    'selbst-fremde-datei': 'Datei mit Selbstprobe außerhalb NAMEN',
    'zeile-werkzeug': 'cmark-gfm fehlt oder scheitert',
    'zeile-anleitung-fehlt': 'tools/quelltext/LIESMICH.md fehlt',
    'zeile-tabellen': 'nicht genau eine Tabelle mit Spalte „Anlass"',
    'zeile-keine': 'Name ohne Tabellenzeile',
    'zeile-mehrfach': 'Name mehrmals in der Tabelle',
    'zeile-anlass-leer': 'Anlass-Zelle leer',
    'zeile-fremd': 'Tabellenzeile mit fremdem Namen',
    'zeile-ohne-namen': 'Tabellenzeile ohne Namen',
    'zeile-zellen': 'Tabellenzeile mit mehr Zellen als die Kopfzeile',
    'ablauf-fehlt': 'pruefablauf.json fehlt',
    'ablauf-json': 'kein gültiges JSON',
    'ablauf-doppelt': 'Schlüssel zweimal',
    'ablauf-form-oben': 'oben kein Objekt',
    'ablauf-form-proben': 'proben kein Objekt mit Einträgen',
    'ablauf-form-muster': 'muster keine Liste mit Einträgen',
    'ablauf-form-riegel': 'riegel.proben keine Liste von Namen',
    'ablauf-abbruch': 'die Gruppe ablauf brach ab',
    'ablauf-flaechen': 'FLAECHEN in bericht.py nicht lesbar',
    'ablauf-flaeche-probe': 'die Bauprobe einer Fläche fehlt unter proben',
    'ablauf-flaeche': 'eine Datei der Fläche wählt ihre Bauprobe nicht aus',
    'ablauf-schluessel': 'Schlüssel, den niemand liest',
    'ablauf-auswahl': 'auswahl.py nicht lesbar',
    'ablauf-stufen': 'stufen weicht von auswahl.py ab',
    'ablauf-braucht-quelle': 'Verteiler case "$braucht" fehlt',
    'ablauf-probe-unvollstaendig': 'Probe ohne aufruf oder braucht',
    'ablauf-braucht': 'braucht unbekannt',
    'ablauf-nach-form': 'nach keine Liste von Namen',
    'ablauf-probe-name': 'Probenname kollidiert im Bericht',
    'ablauf-muster-form': 'Muster kein Objekt',
    'ablauf-muster-felder': 'Muster ohne Pflichtfeld',
    'ablauf-muster-id': 'Muster-id keine Zeichenkette',
    'ablauf-muster-ab': 'ab keine Stufe',
    'ablauf-muster-anlass': 'anlass leer',
    'ablauf-muster-pfade': 'pfade keine Liste von Mustern',
    'ablauf-pfad-trifft-nie': 'Pfadmuster trifft keine Datei',
    'ablauf-muster-proben': 'proben keine Liste von Namen',
    'ablauf-muster-doppelt': 'Muster-id zweimal',
    'ablauf-unbekannt': 'genannter Name fehlt unter proben',
    'ablauf-kreis': 'nach im Kreis',
    'ablauf-riegel-nach': 'nach an einem Riegel',
    'ablauf-unerreichbar': 'Probe nicht erreichbar',
    'ablauf-laeufer': 'Probe eines Läufers ohne Aufruf',
    'tabelle-abbruch': 'die Gruppe tabelle brach ab',
    'tabelle-erzeuger-fehlt': 'bericht.py fehlt',
    'tabelle-erzeuger-fehler': 'erzeugen-doku scheitert',
    'tabelle-doku-fehlt': 'Pruefablauf.md fehlt',
    'tabelle-kein-block': 'kein erzeugter Block',
    'tabelle-mehrere-bloecke': 'mehr als ein erzeugter Block',
    'tabelle-abweichung': 'Block weicht von der Ausgabe ab',
    'tabelle-fortsetzung': 'Text direkt unter dem Block',
    'tabelle-abschnitt': 'Block nicht in Abschnitt 4',
    'tabelle-kopie': 'eine Zeile der Ausgabe steht ein zweites Mal',
    'anlage-abbruch': 'die Gruppe anlage brach ab',
    'anlage-werkzeug': 'php fehlt oder scheitert',
    'anlage-db': 'eine Probe ohne Anlage lädt db.php oder config.php',
    'anlage-unklar': 'ein require, dessen Ziel sich nicht auflösen lässt',
    'tor-abbruch': 'die Gruppe tor brach ab',
    'tor-datei': 'pruefung.yml fehlt oder ruft bericht.py lesen --alle-riegel nicht',
    'tor-fehlt': 'ein Riegel ohne --riegel im Tor',
    'tor-fremd': '--riegel für einen Namen, der kein Riegel ist',
}

# Nur für die Selbstprobe: Werkzeuge, die als fehlend gelten sollen, und
# Gruppen, die abbrechen sollen — damit das Auffangnetz in messen() eine
# Probe hat und nicht nur ein Versprechen ist (Runde 4).
_OHNE = set()
_ABBRUCH = set()


def melde(t=''):
    try:
        print(t, flush=True)
    except BrokenPipeError:
        sys.exit(0)


def bef(kennung, text):
    """Ein Befund: (Regel, Kennung, Text). Die Regel ist der Anfang der
    Kennung; eine Kennung, die KENNUNGEN nicht kennt, ist ein Fehler im
    Riegel selbst."""
    assert kennung in KENNUNGEN, kennung
    return (kennung.split('-', 1)[0], kennung, text)


# ------------------------------------------------------------------ Lesen

def _ls_files(wurzel, *pfade):
    # `-z`: Ohne ihn setzt git einen Pfad mit Umlaut in Anführungszeichen mit
    # Oktalfolgen, `isfile()` findet ihn nicht, und die Datei ist für jede
    # Regel unsichtbar (Gegenprobe BR-05, Runde 2).
    r = subprocess.run(['git', '-C', wurzel, 'ls-files', '-z', '-co', '--exclude-standard', '--', *pfade],
                       capture_output=True, text=True)
    if r.returncode != 0:
        return None
    return sorted({p for p in r.stdout.split('\0') if p and os.path.isfile(os.path.join(wurzel, p))})


def dateien(wurzel):
    """Relative Pfade unter tools/ — versioniert oder neu, nicht ignoriert.
    Ohne Git (nur denkbar außerhalb eines Auschecks) alles, was da ist."""
    aus = _ls_files(wurzel, 'tools')
    if aus is not None:
        return aus
    aus = []
    for ordner, _, namen in os.walk(os.path.join(wurzel, 'tools')):
        for n in namen:
            aus.append(os.path.relpath(os.path.join(ordner, n), wurzel).replace(os.sep, '/'))
    return sorted(aus)


def lies(wurzel, rel):
    with open(os.path.join(wurzel, rel), encoding='utf-8', errors='replace') as f:
        return f.read()


def zeilenzahl(text):
    """Wie `wc -l`, dazu eine letzte Zeile ohne Umbruch."""
    return text.count('\n') + (1 if text and not text.endswith('\n') else 0)


def ausserhalb_zaun(text):
    """(Zeilennummer, Zeile) außerhalb von Codeblöcken. Ein `## x` in einem
    Bash-Beispiel ist kein Abschnitt, ein `Anlass:` darin keine Zeile.

    NACH COMMONMARK (Gegenprobe BR-05, Runde 2): Ein Zaun schließt nur mit
    demselben Zeichen und mindestens derselben Länge, und eine Zeile, die mit
    Code aus drei Backticks BEGINNT (```x``` im Text), öffnet keinen."""
    zaun = None                                   # (Zeichen, Länge) des offenen Zauns
    for i, z in enumerate(text.split('\n'), 1):
        if zaun is None:
            m = ZAUN_AUF_RE.match(z)
            if m and not (m.group(2)[0] == '`' and '`' in m.group(3)):
                zaun = (m.group(2)[0], len(m.group(2)))
                continue
            yield i, z
        elif re.match(r'^ {0,3}(' + re.escape(zaun[0]) + r'{' + str(zaun[1]) + r',})\s*$', z):
            zaun = None


def anlass_zeilen(zeilen):
    """Die Zeilen, die mit „Anlass:" BEGINNEN — nach Kommentarzeichen und
    Hervorhebung. `**Anlass: Nr. 1**` zählt, `… fährt. **Anlass: Nr. 1**`
    nicht: Eine Zeile, die man nur findet, wenn man sie sucht, ist keine."""
    aus = []
    for i, z in zeilen:
        rest = VORSATZ_RE.sub('', z, count=1)
        if rest.startswith('Anlass:'):
            aus.append((i, rest.rstrip()))
    return aus


BACKLOG_DATEIEN = ('docs/Backlog.md', 'docs/Backlog-Erledigt.md')


def backlog_zeilen(wurzel):
    """{Nummer: [„Datei:Zeile"]} — jede Zeile, die mit „Zahl und Punkt" beginnt,
    über BEIDE Backlog-Dateien (seit Konzept SD, SD-02: die offenen Punkte in
    `Backlog.md`, die erledigten wörtlich in `Backlog-Erledigt.md`, E-SD-18).
    Eine Nummer, die in beiden steht, ist damit dieselbe Doppelung wie zweimal
    in einer — und die Anlass-Zeilen der Prüfmittel dürfen erledigte Nummern
    nennen, weil ein Anlass nicht verschwindet, wenn der Punkt erledigt ist.

    DIESELBE LESART WIE DER SCHRITT, DEN DIESE REGEL ABLÖST (`grep -oE
    '^[0-9]+\\.'`): Auch ein Datum am Zeilenanfang zählt als Nummer. Das ist
    Absicht und steht im Kopf von Backlog.md — wer dort umbricht, setzt den
    Umbruch vor das Datum. Fehlt die zweite Datei, wird nur die erste gelesen
    (die Selbstprobe baut nur `Backlog.md`; vor SD-02 gab es die zweite nicht)."""
    aus = {}
    for datei in BACKLOG_DATEIEN:
        pfad = os.path.join(wurzel, *datei.split('/'))
        if not os.path.exists(pfad):
            continue
        with open(pfad, encoding='utf-8') as f:
            for i, z in enumerate(f, 1):
                m = BACKLOG_RE.match(z)
                if m:
                    aus.setdefault(int(m.group(1)), []).append(f'{datei}:{i}')
    return aus


def anlass_pruefen(text, backlog, erzeuger_erlaubt):
    """(Art, Text) für eine einzelne Anlass-Zeile, oder None. Art ist
    'erzeuger', 'keine-nummer' oder 'nummer-fehlt'."""
    ohne_hervorhebung = re.sub(r'\*', '', text).strip()
    if ohne_hervorhebung == ERZEUGER:
        return None if erzeuger_erlaubt else ('erzeuger', 'die Erzeugerform gilt nur für eine Anleitung, nicht hier')
    text = ohne_hervorhebung
    nummern = [int(n) for g in NUMMER_RE.findall(text) for n in re.findall(r'\d+', g)]
    if not nummern:
        return ('keine-nummer', f'„{text[:70]}" nennt keine Backlog-Nummer — ein Anlass ist eine '
                                f'Backlog-Nummer (E-BR-07), keine Befund-Kennung und kein Kürzel')
    fehlt = [n for n in nummern if n not in backlog]
    if fehlt:
        return ('nummer-fehlt', f'Nr. {", ".join(map(str, fehlt))} steht nicht im Backlog („{text[:60]}")')
    return None


def kopfkommentar(text):
    """(Zeilennummer, Zeile) des ERSTEN Kommentarblocks einer Quelldatei.

    Davor darf nur stehen, was jede Datei ihrer Art trägt: `#!…`, `<?php`,
    `declare(strict_types=1);`, `'use strict';` und Leerzeilen. Der Kopf endet
    mit dem Block — eine Anlass-Zeile weiter unten im Code ist keine Zeile im
    Kopf, auch wenn sie so aussieht.
    """
    zeilen = text.split('\n')
    i = 0
    while i < len(zeilen):
        s = zeilen[i].strip()
        if (not s or s.startswith('#!') or s == '<?php' or s.startswith('declare(')
                or s in ("'use strict';", '"use strict";')):
            i += 1
            continue
        break
    if i >= len(zeilen):
        return []
    s = zeilen[i].lstrip()
    aus = []
    if s.startswith('/*'):
        for j in range(i, len(zeilen)):
            aus.append((j + 1, zeilen[j]))
            if '*/' in zeilen[j][(zeilen[j].find('/*') + 2) if j == i else 0:]:
                break
    elif s.startswith('<!--'):
        for j in range(i, len(zeilen)):
            aus.append((j + 1, zeilen[j]))
            if '-->' in zeilen[j]:
                break
    elif s.startswith(('"""', "'''")):
        zeichen = s[:3]
        for j in range(i, len(zeilen)):
            aus.append((j + 1, zeilen[j]))
            if zeichen in (zeilen[j].lstrip()[3:] if j == i else zeilen[j]):
                break
    else:
        for vorsatz in ('//', '#'):
            if s.startswith(vorsatz):
                j = i
                while j < len(zeilen) and zeilen[j].lstrip().startswith(vorsatz):
                    aus.append((j + 1, zeilen[j]))
                    j += 1
                break
    return aus


AUFRUF_RE = re.compile(r'(?:php|node|python3?|bash)\s+(tools/proben/[\w./-]+\.(?:php|mjs|py|sh))')


def einstieg(wert, quelle):
    """Die Einstiegsdatei eines RUF-Werts, oder None.

    Ist der Wert eine Funktion (die Versandprobe startet erst ihre
    Gegenstellen), zählt der EINE Aufruf im Vordergrund. Eine Gegenstelle läuft
    im Hintergrund (`… &`). Fortsetzungszeilen (`\\` am Ende) werden vorher
    verbunden — sonst hielt der Riegel die zweite Zeile einer umbrochenen
    Hintergrundzeile für einen Vordergrundaufruf (Gegenprobe BR-05, Runde 3)."""
    pfad = re.search(r'(tools/proben/[\w./-]+\.(?:php|mjs|py|sh))', wert)
    if pfad:
        return pfad.group(1)
    if not wert.split():
        return None
    rumpf = re.search(r'^' + re.escape(wert.split()[0]) + r'\(\)\s*\{(.*?)^\}', quelle, re.S | re.M)
    vorne = set()
    for z in (re.sub(r'\\\n', ' ', rumpf.group(1)).split('\n') if rumpf else []):
        z = z.split('#', 1)[0].rstrip()
        if z.endswith('&') and not z.endswith('&&'):
            continue
        vorne |= set(AUFRUF_RE.findall(z))
    return vorne.pop() if len(vorne) == 1 else None


# ------------------------------------------------------- Die echten Werkzeuge

class WerkzeugFehlt(Exception):
    pass


def _werkzeug(name):
    if name in _OHNE or not shutil.which(name):
        raise WerkzeugFehlt(name)
    return name


def laeufer_liste(wurzel, rel, schalter):
    """Die Ausgabe von `bash <rel> <schalter>` als Zeilen, oder None. So sieht
    BASH die Listen — bis BR-05 las der Riegel sie mit eigenen Mustern, und
    jede Gegenprüfrunde fand eine Schreibweise, die er anders las."""
    if not os.path.isfile(os.path.join(wurzel, rel)):
        return None
    r = subprocess.run(['bash', rel, schalter], cwd=wurzel, capture_output=True, text=True,
                       encoding='utf-8', errors='replace')
    zeilen = [z for z in r.stdout.split('\n') if z.strip()]
    return zeilen if r.returncode == 0 and zeilen else None


_PHP_LESER = r'''
$aus = [];
foreach (array_slice($argv, 1) as $datei) {
    $t = token_get_all(file_get_contents($datei));
    $s = []; $g = []; $in = false; $tiefe = 0;
    foreach ($t as $x) {
        if (is_array($x)) {
            $name = ltrim($x[1], '\\');
            if (in_array($x[0], [T_STRING, defined('T_NAME_FULLY_QUALIFIED') ? T_NAME_FULLY_QUALIFIED : -1], true)
                    && strtolower($name) === 'getopt') { $in = true; $tiefe = 0; continue; }
            if ($x[0] === T_CONSTANT_ENCAPSED_STRING) {
                $roh = substr($x[1], 1, -1);
                $v = $x[1][0] === "'" ? str_replace(["\\'", '\\\\'], ["'", '\\'], $roh) : stripcslashes($roh);
                $s[] = $v;
                if ($in && $tiefe > 0) { $g[] = $v; }
            }
        } elseif ($in) {
            if ($x === '(') { $tiefe++; }
            elseif ($x === ')') { $tiefe--; if ($tiefe <= 0) { $in = false; } }
        }
    }
    $aus[$datei] = ['s' => $s, 'g' => $g];
}
echo json_encode($aus, JSON_INVALID_UTF8_SUBSTITUTE);
'''


def php_zeichenketten(wurzel, dateien):
    """{Datei: (Zeichenketten, Zeichenketten in getopt())} über token_get_all
    — so, wie PHP die Datei liest: Kommentare, Heredocs und Text außerhalb von
    `<?php` sind keine Zeichenketten. Bis BR-05 las hier ein eigener
    Zustandsautomat, und ein `?>` in einem Kommentar oder ein Apostroph in
    einem Heredoc brachten ihn aus dem Tritt (Gegenprobe, Runden 2 und 3)."""
    if not dateien:
        return {}
    php = _werkzeug('php')
    r = subprocess.run([php, '-r', _PHP_LESER, '--', *dateien], cwd=wurzel, capture_output=True, text=True,
                       encoding='utf-8', errors='replace')
    # JSON_INVALID_UTF8_SUBSTITUTE: Eine Zeichenkette mit kaputtem UTF-8
    # ("\xC3\x28", die Hausform solcher Prüffälle) ließ json_encode scheitern und
    # leerte den Leser für ALLE Dateien (Gegenprobe, Runde 4).
    if r.returncode != 0 or not r.stdout.strip().startswith('{'):
        raise WerkzeugFehlt(f'php (rc {r.returncode}: {(r.stderr or r.stdout).strip()[:80]})')
    daten = json.loads(r.stdout)
    return {d: (set(v['s']), set(v['g'])) for d, v in daten.items()}


def py_zeichenketten(text):
    """Die Zeichenketten-Literale einer Python-Datei — über den Tokenizer,
    also ohne Kommentare; ein Docstring ist eine Zeichenkette, aber nie gleich
    '--selbstprobe'."""
    import ast
    import io
    import tokenize
    aus = set()
    try:
        for tok in tokenize.generate_tokens(io.StringIO(text).readline):
            if tok.type == tokenize.STRING:
                try:
                    wert = ast.literal_eval(tok.string)
                except (ValueError, SyntaxError):
                    continue
                if isinstance(wert, str):
                    aus.add(wert)
    except (tokenize.TokenError, IndentationError, SyntaxError):
        pass
    return aus


def schalter_aus(ketten, getopt, endung):
    """'--selbstprobe', '--probe' oder None. In PHP zählt die getopt-Form
    `selbstprobe` nur INNERHALB eines getopt()-Aufrufs — ein Moduswort
    „selbstprobe" irgendwo in der Datei schaltete sonst den Befund zu
    `--probe` ab (Runden 2 und 3)."""
    if '--selbstprobe' in ketten:
        return '--selbstprobe'
    if endung == '.php' and any(re.fullmatch(r'selbstprobe:{0,2}', w) for w in getopt):
        return '--selbstprobe'
    if '--probe' in ketten:
        return '--probe'
    return None


class _TabellenLeser(html.parser.HTMLParser):
    """Liest die Tabellen aus der HTML-Ausgabe von cmark-gfm --sourcepos:
    je Tabelle die Kopfzellen (Text) und je Zeile (Zeilennummer, Zellen);
    eine Zelle ist (Text, [Code-Stücke])."""

    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.tabellen, self._tab, self._zeile, self._zelle, self._code = [], None, None, None, None

    def handle_starttag(self, tag, attrs):
        if tag == 'table':
            self._tab = {'kopf': [], 'reihen': []}
        elif tag == 'tr' and self._tab is not None:
            pos = dict(attrs).get('data-sourcepos', '0:0')
            self._zeile = (int(pos.split(':')[0]), [])
        elif tag in ('td', 'th') and self._zeile is not None:
            self._zelle = [tag, '', []]
        elif tag == 'code' and self._zelle is not None:
            self._code = ''

    def handle_endtag(self, tag):
        if tag == 'code' and self._code is not None and self._zelle is not None:
            self._zelle[2].append(self._code)
            self._code = None
        elif tag in ('td', 'th') and self._zelle is not None:
            self._zeile[1].append(self._zelle)
            self._zelle = None
        elif tag == 'tr' and self._zeile is not None:
            art = self._zeile[1][0][0] if self._zeile[1] else 'td'
            if art == 'th':
                self._tab['kopf'] = [z[1].strip() for z in self._zeile[1]]
            else:
                self._tab['reihen'].append((self._zeile[0], [(z[1], z[2]) for z in self._zeile[1]]))
            self._zeile = None
        elif tag == 'table' and self._tab is not None:
            self.tabellen.append(self._tab)
            self._tab = None

    def handle_data(self, data):
        if self._zelle is not None:
            self._zelle[1] += data
        if self._code is not None:
            self._code += data


def gfm_tabellen(wurzel, rel):
    """Die Tabellen einer Markdown-Datei, wie GitHub sie zeigt — gerendert von
    cmark-gfm, nicht nachgebaut. Bis Runde 3 las ein eigener Leser, und drei
    Runden fanden je neue Schreibweisen, die er anders las als GitHub."""
    cmark = _werkzeug('cmark-gfm')
    r = subprocess.run([cmark, '-e', 'table', '--sourcepos', '--to', 'html', rel], cwd=wurzel,
                       capture_output=True, text=True, encoding='utf-8', errors='replace')
    if r.returncode != 0:
        raise WerkzeugFehlt(f'cmark-gfm (rc {r.returncode})')
    leser = _TabellenLeser()
    leser.feed(r.stdout)
    return leser.tabellen


def gezeigt_leer(text):
    """Zeigt GitHub diese Zelle leer oder als bloße Striche? Gelesen am
    GERENDERTEN Text: Hervorhebung, Entitäten und Kommentare sind schon weg.
    Unsichtbare Zeichen zählen nicht — auch der weiche Trennstrich (`&shy;`)
    nicht —, und `--` ist so leer wie `-` (Runde 4)."""
    t = re.sub(r'[\s\u00a0\u00ad\u200b\u200c\u200d\u2060\ufeff]+', '', text)
    return re.fullmatch(r'[-\u2010\u2012\u2013\u2014\u2212]*', t) is not None


def rohe_zellen(zeile):
    """Die Zahl der Zellen einer Tabellenzeile im QUELLTEXT, so wie GFM sie
    trennt: an jedem `|` ohne `\\` davor — auch in einer Code-Spanne —, je ein
    führendes und abschließendes `|` zählt nicht. GitHub wirft Zellen über
    der Zahl der Kopfzeile WEG: Ein `|` in `a|b` verschob so die Spalten, und
    die Anlass-Spalte zeigte ein Stück der Beschreibung (Runde 4)."""
    t = zeile.strip()
    if t.startswith('|'):
        t = t[1:]
    if t.endswith('|') and not t.endswith('\\|'):
        t = t[:-1]
    return len(re.split(r'(?<!\\)\|', t))


def modul_aus(wurzel, rel, name):
    """Ein Modul aus dem Auscheck laden (auswahl.py, bericht.py) — oder None.

    SEIN ORDNER STEHT DABEI VORN IN sys.path, wie beim Start als Skript. Ohne
    das galt ein auswahl.py, das ein Geschwistermodul einbindet, als „fehlt",
    und `ab` und die Pfade blieben ungeprüft (Runde 4). Danach wird beides
    zurückgesetzt: Ein Geschwistermodul dieses Auschecks darf nicht in den
    nächsten Auscheck hinüberreichen (die Selbstprobe misst viele)."""
    pfad = os.path.join(wurzel, rel)
    if not os.path.isfile(pfad):
        return None
    ordner = os.path.dirname(os.path.abspath(pfad))
    pfad_vorher, module_vorher = list(sys.path), set(sys.modules)
    sys.path.insert(0, ordner)
    try:
        spec = importlib.util.spec_from_file_location(name, pfad)
        mod = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(mod)
        return mod
    except Exception:                                   # noqa: BLE001 — ein kaputtes Modul ist ein Befund
        return None
    finally:
        sys.path[:] = pfad_vorher
        for m in set(sys.modules) - module_vorher:
            datei = getattr(sys.modules[m], '__file__', None) or ''
            if datei and os.path.abspath(datei).startswith(ordner + os.sep):
                del sys.modules[m]


def flaechen_aus(wurzel):
    """{Fläche: ([Ordner], Bauprobe)} — FLAECHEN aus dem geladenen bericht.py,
    oder None, wenn es nicht lesbar ist.

    Eine Probe, die wie eine Fläche heißt, verschwände im Bericht (Runde 3).
    Und eine Bauprobe, die das Muster für eine Datei der Fläche nicht
    auswählt, macht jede Berührung dort zur Sackgasse: Das Tor verlangt
    „gebaut", und kein Lauf liefert es (Runde 4)."""
    mod = modul_aus(wurzel, ERZEUGER_DOKU, 'bestand_bericht')
    f = getattr(mod, 'FLAECHEN', None) if mod else None
    if not isinstance(f, dict) or not f:
        return None
    aus = {}
    for name, wert in f.items():
        if not (isinstance(name, str) and isinstance(wert, (tuple, list)) and len(wert) == 2
                and isinstance(wert[0], (tuple, list)) and wert[0] and all(isinstance(o, str) and o for o in wert[0])
                and isinstance(wert[1], str) and wert[1]):
            return None
        aus[name] = (list(wert[0]), wert[1])
    return aus


def workflows(wurzel):
    """[(Pfad, Text)] der Workflows."""
    wf = os.path.join(wurzel, '.github', 'workflows')
    if not os.path.isdir(wf):
        return []
    return [(f'.github/workflows/{n}', lies(wurzel, f'.github/workflows/{n}'))
            for n in sorted(os.listdir(wf)) if n.endswith(('.yml', '.yaml'))]


def braucht_werte(wurzel):
    """`nichts`, `installation` und die Zweige des Verteilers `case "$braucht"`
    im Prüfstand, oder None."""
    if not os.path.isfile(os.path.join(wurzel, PRUEFSTAND)):
        return None
    m = re.search(r'case\s+"\$braucht"\s+in(.*?)esac', lies(wurzel, PRUEFSTAND), re.S)
    if not m:
        return None
    werte = {'nichts', 'installation'}
    for zweig in re.findall(r'^\s*([\w|-]+)\)', m.group(1), re.M):
        werte |= set(zweig.split('|'))
    return werte


def _json_mit_doppelten(text):
    """(Daten, [doppelte Schlüssel]). `json.loads` behält beim zweiten
    gleichnamigen Schlüssel still den zweiten."""
    doppelt = []

    def paare(liste):
        d = {}
        for k, v in liste:
            if k in d:
                doppelt.append(k)
            d[k] = v
        return d
    return json.loads(text, object_pairs_hook=paare), doppelt


# ----------------------------------------------------------------- Messen

def messen(wurzel):
    """(Befunde als [(Regel, Kennung, Text)], Zahlen). Wirft
    FileNotFoundError, wenn die Grundlage fehlt — das ist rc 2."""
    # Absolut: Der Erzeuger der Tabelle läuft mit cwd=wurzel, ein relativer
    # Pfad hätte sich verdoppelt (Gegenprobe BR-05).
    wurzel = os.path.abspath(wurzel)
    if not os.path.isdir(os.path.join(wurzel, 'tools')):
        raise FileNotFoundError(os.path.join(wurzel, 'tools'))
    backlog = set(backlog_zeilen(wurzel))
    alle = dateien(wurzel)
    ordner = sorted({p.split('/')[1] for p in alle if p.count('/') >= 2})
    lose = [p for p in alle if p.count('/') == 1]
    anleitungen = [p for p in alle if p.endswith('/LIESMICH.md')]
    befunde = []
    zahlen = {'ordner': len(ordner), 'anleitungen': len(anleitungen), 'erzeuger': [],
              'proben': 0, 'lose': [os.path.basename(p) for p in lose]}

    # form — jede Anleitung, rekursiv (E-BR-04)
    for p in anleitungen:
        text = lies(wurzel, p)
        n = zeilenzahl(text)
        if n > HOECHSTENS:
            befunde.append(bef('form-zeilen', f'{p}: {n} Zeilen, erlaubt sind {HOECHSTENS}'))
        ist = [ABSCHNITT_RE.match(z).group(1) for _, z in ausserhalb_zaun(text) if ABSCHNITT_RE.match(z)]
        if ist != ABSCHNITTE:
            fehlt = [a for a in ABSCHNITTE if a not in ist]
            fremd = [a for a in ist if a not in ABSCHNITTE]
            teile = []
            if fehlt:
                teile.append('es fehlt ' + ', '.join(f'„{a}"' for a in fehlt))
            if fremd:
                teile.append(f'{len(fremd)} fremde: ' + ', '.join(f'„{a}"' for a in fremd[:3])
                             + (' …' if len(fremd) > 3 else ''))
            if not teile:
                teile.append('Reihenfolge oder Zahl stimmt nicht: ' + ' · '.join(ist))
            befunde.append(bef('form-abschnitte', f'{p}: {len(ist)} Abschnitte statt der fünf aus 6.2 — '
                                                  + '; '.join(teile)))

    # anleitung, anlass — je Ordner direkt unter tools/
    for d in ordner:
        p = f'tools/{d}/LIESMICH.md'
        if p not in anleitungen:
            befunde.append(bef('anleitung-fehlt', f'tools/{d}/ hat keine LIESMICH.md'))
            continue
        zeilen = list(ausserhalb_zaun(lies(wurzel, p)))
        treffer = anlass_zeilen(zeilen)
        if not treffer:
            mitten = [i for i, z in zeilen if 'Anlass:' in z]
            befunde.append(bef('anlass-keine', f'{p}: keine Zeile, die mit „Anlass:" beginnt'
                                               + (f' (mitten in Zeile {mitten[0]})' if mitten else '')))
        elif len(treffer) > 1:
            befunde.append(bef('anlass-mehrere', f'{p}: {len(treffer)} Anlass-Zeilen '
                                                 f'({", ".join(str(i) for i, _ in treffer)}), erlaubt ist eine'))
        else:
            fehler = anlass_pruefen(treffer[0][1], backlog, erzeuger_erlaubt=True)
            if fehler:
                befunde.append(bef(f'anlass-{fehler[0]}', f'{p}:{treffer[0][0]}: {fehler[1]}'))
            elif re.sub(r'\*', '', treffer[0][1]).strip() == ERZEUGER:
                zahlen['erzeuger'].append(d)

    # inventur — wird der Ordner gerufen?
    rufer = ''
    for r in RUFER:
        if os.path.isfile(os.path.join(wurzel, r)):
            rufer += lies(wurzel, r)
    for _, text in workflows(wurzel):
        rufer += text
    for d in ordner:
        if f'tools/{d}/' not in rufer:
            befunde.append(bef('inventur-ungerufen', f'tools/{d}/ wird nirgends gerufen — weder in '
                                                     f'pruefablauf.json noch in einem Workflow, pruefen.sh oder '
                                                     f'Sandbox-Setup.md: ein Kandidat für die Streichliste'))

    # lose — nur motor.mjs, beim Namen
    for p in lose:
        if os.path.basename(p) not in LOSE_ERLAUBT:
            befunde.append(bef('lose-datei', f'{p} liegt lose direkt unter tools/ — erlaubt ist nur '
                                             f'{", ".join(sorted(LOSE_ERLAUBT))} (E-BR-03 (4))'))

    # probe — die Anlass-Zeile jeder Probe im Kopf ihrer Einstiegsdatei.
    # UNBEDINGT: Fehlt der Läufer, ist das ein Befund (Runde 3).
    liste = laeufer_liste(wurzel, PROBEN_LAEUFER, '--liste')
    ruf = {}
    if liste is None:
        befunde.append(bef('probe-laeufer', f'{PROBEN_LAEUFER} fehlt oder `--liste` liefert nichts — '
                                            f'keine Probe ist messbar'))
    else:
        quelle = lies(wurzel, PROBEN_LAEUFER)
        for z in liste:
            m = re.match(r'^\s*(\S+)\s+(.*?)\s*(?:\(nicht in »alle«:.*\))?$', z)
            if m:
                ruf[m.group(1)] = m.group(2)
        zahlen['proben'] = len(ruf)
        belegt = set()
        for name, wert in sorted(ruf.items()):
            datei = einstieg(wert, quelle)
            if not datei:
                befunde.append(bef('probe-einstieg-unklar', f'{name}: Einstiegsdatei im Läufer nicht ermittelbar'))
                continue
            belegt.add(datei.split('/')[2])
            if datei not in alle:
                befunde.append(bef('probe-datei-fehlt', f'{name}: {datei} fehlt'))
                continue
            treffer = anlass_zeilen(kopfkommentar(lies(wurzel, datei)))
            if not treffer:
                befunde.append(bef('probe-keine-zeile', f'{datei}: keine Anlass-Zeile im Kopfkommentar'))
            elif len(treffer) > 1:
                befunde.append(bef('probe-mehrere', f'{datei}: {len(treffer)} Anlass-Zeilen im Kopf, erlaubt ist eine'))
            else:
                fehler = anlass_pruefen(treffer[0][1], backlog, erzeuger_erlaubt=False)
                if fehler:
                    befunde.append(bef(f'probe-{fehler[0]}', f'{datei}:{treffer[0][0]}: {fehler[1]}'))
        for u in sorted({p.split('/')[2] for p in alle if p.startswith('tools/proben/') and p.count('/') >= 3}):
            if u not in belegt:
                befunde.append(bef('probe-ordner-ohne-eintrag', f'tools/proben/{u}/ hat keinen Eintrag im Läufer — '
                                                                f'ihr Anlass ist nicht messbar'))

    # backlog — keine Nummer zweimal
    for nr, zeilen in sorted(backlog_zeilen(wurzel).items()):
        if len(zeilen) > 1:
            befunde.append(bef('backlog-doppelt', f'Backlog: Nr. {nr} steht {len(zeilen)}-mal '
                                                  f'({", ".join(zeilen)})'))

    # selbst, zeile, ablauf, tabelle — UNBEDINGT (Runde 2). Scheitert eine
    # Gruppe an einer Eingabe, die sie nicht erwartet, ist das ein Befund
    # DIESER Regel — kein Traceback, der die übrigen verschluckt. `tabelle`
    # ist eine eigene Gruppe: Hing sie an `ablauf`, fiel sie mit einem Komma
    # zu viel in der Ablaufdatei still aus (Runde 4).
    for kennung, pruefung in (('selbst-abbruch', quelltext_pruefen), ('ablauf-abbruch', ablauf_pruefen),
                              ('tabelle-abbruch', tabelle_pruefen), ('anlage-abbruch', anlage_pruefen),
                              ('tor-abbruch', tor_pruefen)):
        try:
            if kennung in _ABBRUCH:
                raise RuntimeError('eingebauter Abbruch der Selbstprobe')
            befunde += pruefung(wurzel, alle, zahlen, ruf)
        except Exception as e:                          # noqa: BLE001 — genau das ist der Zweck
            befunde.append(bef(kennung, f'die Prüfung brach ab: {type(e).__name__}: {str(e)[:100]} — '
                                        f'ihre Regeln sind NICHT gemessen'))

    befunde.sort(key=lambda b: REGELN.index(b[0]))     # stabil: je Regel in Fundreihenfolge
    return befunde, zahlen


SELBSTPROBE_RUF_RE = re.compile(r'(?<![\w./-])(?:\./)?' + re.escape(QUELLTEXT) + r'\s+--selbstprobe(?![\w-])')


def selbstprobe_gerufen(wurzel):
    """Steht `tools/quelltext/pruefen.sh --selbstprobe` in einem Workflow
    (nicht in einer Kommentarzeile) oder in einem Aufruf der Ablaufdatei?"""
    for _, text in workflows(wurzel):
        for z in text.split('\n'):
            if not z.lstrip().startswith('#') and SELBSTPROBE_RUF_RE.search(z.split(' #', 1)[0]):
                return True
    try:
        a = json.loads(lies(wurzel, ABLAUF))
        return any(isinstance(d, dict) and isinstance(d.get('aufruf'), str) and SELBSTPROBE_RUF_RE.search(d['aufruf'])
                   for d in a.get('proben', {}).values())
    except (OSError, ValueError, AttributeError):
        return False


def quelltext_pruefen(wurzel, alle, zahlen, ruf):
    """Regeln `selbst` und `zeile`."""
    befunde = []
    liste = laeufer_liste(wurzel, QUELLTEXT, '--liste')
    if liste is None:
        return [bef('selbst-laeufer', f'{QUELLTEXT} fehlt oder `--liste` liefert nichts — '
                                      f'keine Quelltextprüfung ist messbar')]
    befehle, selbst = {}, []
    for z in liste:
        teile = z.split('\t')
        if teile[0] == 'NAME' and len(teile) >= 3:
            befehle[teile[1]] = teile[2]
        elif teile[0] == 'SELBST' and len(teile) >= 2:
            selbst.append(teile[1])
    namen = list(befehle)
    if not namen:
        return [bef('selbst-keine-namen', f'{QUELLTEXT} --liste nennt keinen Namen in NAMEN')]
    zahlen['quelltext'] = len(namen)
    zahlen['selbst'] = len(selbst)

    # selbst — WER RUFT die Selbstproben? SELBST ist nur eine Liste; gefahren
    # wird sie von `pruefen.sh --selbstprobe`, und das steht in einem Workflow
    # oder einem Aufruf des Prüfstands. Fehlt der Aufruf, läuft keine, und
    # alles darunter ist grün (Runde 4).
    if selbst and not selbstprobe_gerufen(wurzel):
        befunde.append(bef('selbst-tor', f'SELBST nennt {len(selbst)} Prüfungen, aber `{QUELLTEXT} --selbstprobe` '
                                         f'steht in keinem Workflow und keinem Aufruf von {ABLAUF} — '
                                         f'keine Selbstprobe läuft'))

    # selbst — je Name die Datei, die starter() WIRKLICH startet
    je_name = {}
    for n in namen:
        m = re.search(r'(tools/\S+?\.\w+)(?=\s|$)', befehle[n])
        if not m:
            befunde.append(bef('selbst-befehl-ohne-datei', f'{n}: starter() startet „{befehle[n][:50]}" — '
                                                           f'keine Datei unter tools/, nicht prüfbar'))
            continue
        datei = m.group(1)
        if datei not in alle:
            befunde.append(bef('selbst-datei-fehlt', f'{n}: {datei} fehlt — starter() startet diese Datei'))
            continue
        if os.path.splitext(datei)[1] not in QUELLTEXT_SPRACHEN:
            befunde.append(bef('selbst-sprache', f'{datei}: eine Quelltextprüfung in '
                                                 f'{os.path.splitext(datei)[1] or "ohne Endung"} — der Riegel liest '
                                                 f'nur {" und ".join(QUELLTEXT_SPRACHEN)}; nicht messbar'))
            continue
        je_name[n] = datei
    # Alle Dateien direkt in tools/quelltext/ — auch die, die NAMEN nicht kennt
    fremde = []
    for datei in alle:
        endung = os.path.splitext(datei)[1]
        if os.path.dirname(datei) != 'tools/quelltext' or datei == QUELLTEXT or datei in je_name.values():
            continue
        if endung in QUELLTEXT_SPRACHEN:
            fremde.append(datei)
        elif endung not in DATEN_ENDUNGEN and not any(befehle[n].endswith(datei) or datei in befehle[n] for n in befehle):
            befunde.append(bef('selbst-sprache-datei', f'{datei}: Quelltext in {endung or "ohne Endung"} — der Riegel '
                                                 f'liest nur {" und ".join(QUELLTEXT_SPRACHEN)}; ob er eine '
                                                 f'Selbstprobe hat, ist nicht messbar'))
    php = [d for d in list(je_name.values()) + fremde if d.endswith('.php')]
    try:
        php_ketten = php_zeichenketten(wurzel, php)
    except WerkzeugFehlt as e:
        befunde.append(bef('selbst-werkzeug', f'{e} fehlt oder scheitert — die PHP-Prüfungen sind nicht messbar'))
        php_ketten = None

    def schalter(datei):
        if datei.endswith('.php'):
            if php_ketten is None:
                return 'unbekannt'
            k, g = php_ketten.get(datei, (set(), set()))
            return schalter_aus(k, g, '.php')
        return schalter_aus(py_zeichenketten(lies(wurzel, datei)), set(), '.py')

    for n, datei in je_name.items():
        s = schalter(datei)
        if s == 'unbekannt':
            continue
        if s == '--probe':
            befunde.append(bef('selbst-probe-schalter', f'{datei} hat eine Selbstprobe hinter --probe; pruefen.sh '
                                                        f'ruft --selbstprobe — sie läuft nirgends'))
        elif s and n not in selbst:
            befunde.append(bef('selbst-nicht-in-selbst', f'{datei} wertet --selbstprobe aus, aber {n} steht nicht '
                                                         f'in SELBST von {QUELLTEXT} — die Selbstprobe läuft nirgends'))
        elif not s and n in selbst:
            befunde.append(bef('selbst-ohne-auswertung', f'{n} steht in SELBST, aber {datei} wertet '
                                                         f'--selbstprobe nicht aus'))
    for x in selbst:
        if x not in namen:
            befunde.append(bef('selbst-ohne-namen', f'{x} steht in SELBST, aber nicht in NAMEN von {QUELLTEXT}'))
    for datei in fremde:
        s = schalter(datei)
        if s not in (None, 'unbekannt'):
            befunde.append(bef('selbst-fremde-datei', f'{datei} hat eine Selbstprobe, steht aber nicht in NAMEN '
                                                      f'von {QUELLTEXT} — sie läuft nirgends'))

    # zeile — die Tabelle der Anleitung, wie GitHub sie zeigt
    if QUELLTEXT_ANLEITUNG not in alle:
        befunde.append(bef('zeile-anleitung-fehlt', f'{QUELLTEXT_ANLEITUNG} fehlt — keine Prüfung hat eine Zeile'))
        return befunde
    try:
        tabellen = gfm_tabellen(wurzel, QUELLTEXT_ANLEITUNG)
    except WerkzeugFehlt as e:
        befunde.append(bef('zeile-werkzeug', f'{e} fehlt oder scheitert — die Tabelle ist nicht messbar '
                                             f'(Ausbaustufe web; im Tor „cmark-gfm bereitstellen")'))
        return befunde

    def name_aus(zelle):
        text, codes = zelle
        return codes[0].strip() if len(codes) == 1 and text.strip() == codes[0].strip() else None
    passend = [t for t in tabellen if 'Anlass' in t['kopf'] and any(name_aus(z[0]) for _, z in t['reihen'] if z)]
    if len(passend) != 1:
        befunde.append(bef('zeile-tabellen', f'{QUELLTEXT_ANLEITUNG}: {len(passend)} Tabellen mit Spalte „Anlass" '
                                             f'und Namen in der ersten Spalte, wie GitHub sie zeigt — erwartet ist eine'))
        return befunde
    tab = passend[0]
    spalte = tab['kopf'].index('Anlass')
    roh = lies(wurzel, QUELLTEXT_ANLEITUNG).split('\n')
    zeilen = []
    for i, zellen in tab['reihen']:
        if 0 < i <= len(roh) and rohe_zellen(roh[i - 1]) > len(tab['kopf']):
            befunde.append(bef('zeile-zellen', f'{QUELLTEXT_ANLEITUNG}:{i}: {rohe_zellen(roh[i - 1])} Zellen, die Kopfzeile '
                                               f'hat {len(tab["kopf"])} — GitHub wirft den Rest weg; ein `|` im Text '
                                               f'braucht ein `\\` davor, auch in einer Code-Spanne'))
        n = name_aus(zellen[0]) if zellen else None
        if n is None:
            befunde.append(bef('zeile-ohne-namen', f'{QUELLTEXT_ANLEITUNG}:{i}: die erste Zelle ist kein Name '
                                                   f'(„{(zellen[0][0] if zellen else "").strip()[:40]}") — '
                                                   f'erwartet ist genau `name`'))
            continue
        zeilen.append((i, n, zellen[spalte][0] if spalte < len(zellen) else ''))
    for n in namen:
        treffer = [z for z in zeilen if z[1] == n]
        if not treffer:
            befunde.append(bef('zeile-keine', f'{QUELLTEXT_ANLEITUNG}: keine Zeile für `{n}` in der Tabelle — '
                                              f'ihr Gegenstand und ihr Anlass stehen nirgends'))
        elif len(treffer) > 1:
            befunde.append(bef('zeile-mehrfach', f'{QUELLTEXT_ANLEITUNG}: `{n}` steht {len(treffer)}-mal in der '
                                                 f'Tabelle (Zeilen {", ".join(str(z[0]) for z in treffer)})'))
        elif gezeigt_leer(treffer[0][2]):
            befunde.append(bef('zeile-anlass-leer', f'{QUELLTEXT_ANLEITUNG}:{treffer[0][0]}: `{n}` hat keinen '
                                                    f'Anlass in der Spalte „Anlass"'))
    for i, n, _ in zeilen:
        if n not in namen:
            befunde.append(bef('zeile-fremd', f'{QUELLTEXT_ANLEITUNG}:{i}: `{n}` steht in der Tabelle, aber nicht '
                                              f'in NAMEN von {QUELLTEXT} — der Läufer fährt sie nie'))
    return befunde


def ablauf_pruefen(wurzel, alle, zahlen, ruf):
    """Regel `ablauf`."""
    if ABLAUF not in alle:
        return [bef('ablauf-fehlt', f'{ABLAUF} fehlt — der Prüfstand hat keine Zuordnung')]
    try:
        a, doppelt = _json_mit_doppelten(lies(wurzel, ABLAUF))
    except ValueError as e:
        return [bef('ablauf-json', f'{ABLAUF} ist kein gültiges JSON: {e}')]
    befunde = [bef('ablauf-doppelt', f'{ABLAUF}: der Schlüssel „{k}" steht zweimal — der erste ist still verloren')
               for k in doppelt]
    if not isinstance(a, dict):
        return befunde + [bef('ablauf-form-oben', f'{ABLAUF}: oben steht kein Objekt')]
    proben = a.get('proben')
    riegel_obj = a.get('riegel')
    riegel = riegel_obj.get('proben') if isinstance(riegel_obj, dict) else None
    muster = a.get('muster')
    if not isinstance(proben, dict) or not proben:
        return befunde + [bef('ablauf-form-proben', f'{ABLAUF}: proben ist kein Objekt mit Einträgen')]
    if not isinstance(muster, list) or not muster:
        return befunde + [bef('ablauf-form-muster', f'{ABLAUF}: muster ist keine Liste mit Einträgen')]
    if not isinstance(riegel, list) or not all(isinstance(r, str) for r in riegel):
        return befunde + [bef('ablauf-form-riegel', f'{ABLAUF}: riegel.proben ist keine Liste von Namen')]
    zahlen['ablauf'] = (len(proben), len(muster), len(riegel))

    # ablauf — Schlüssel, die niemand liest
    def fremde_schluessel(obj, ebene, wo):
        for k in obj:
            if k not in SCHLUESSEL[ebene]:
                zusatz = (' — `nach` gehört an die PROBE, nicht an das Muster' if ebene == 'muster' and k == 'nach'
                          else '')
                befunde.append(bef('ablauf-schluessel', f'{ABLAUF}: {wo} trägt „{k}", und das liest niemand{zusatz} '
                                                        f'— liest ihn ein Werkzeug, in SCHLUESSEL von '
                                                        f'tools/quelltext/bestand.py eintragen'))
    fremde_schluessel(a, 'oben', 'die Datei')
    fremde_schluessel(riegel_obj, 'riegel', 'riegel')

    auswahl = modul_aus(wurzel, AUSWAHL, 'bestand_auswahl')
    stufen = list(getattr(auswahl, 'STUFEN', []) or []) if auswahl else []
    passt = getattr(auswahl, 'passt', None) if auswahl else None
    if not stufen or not callable(passt):
        befunde.append(bef('ablauf-auswahl', f'{AUSWAHL} fehlt oder liefert STUFEN und passt() nicht — '
                                             f'`ab` und die Pfade sind nicht prüfbar'))
    elif 'stufen' in a and a['stufen'] != stufen:
        befunde.append(bef('ablauf-stufen', f'{ABLAUF}: stufen={a["stufen"]} weicht von STUFEN in auswahl.py ab '
                                            f'({stufen}) — gelesen wird nur auswahl.py'))
    braucht = braucht_werte(wurzel)
    if braucht is None:
        befunde.append(bef('ablauf-braucht-quelle', f'{PRUEFSTAND} hat keinen Verteiler case "$braucht" — '
                                                    f'`braucht` ist nicht prüfbar'))
    flaechen = flaechen_aus(wurzel)
    if flaechen is None:
        befunde.append(bef('ablauf-flaechen', f'{ERZEUGER_DOKU}: FLAECHEN ist nicht lesbar (das Modul lädt nicht, oder '
                                              f'es ist kein {{Fläche: ((Ordner, …), Bauprobe)}}) — Flächen und '
                                              f'Probennamen sind nicht prüfbar'))
        flaechen = {}

    # ablauf — jede Probe
    nach = {}
    for p, d in proben.items():
        if not PAAR_RE.fullmatch(p) or p in flaechen:
            befunde.append(bef('ablauf-probe-name', f'{ABLAUF}: Probe „{p}" — ' + (
                'heißt wie eine Fläche des Berichts; ihr Ergebnis verschwände dort' if p in flaechen else
                'das Tor liest den Namen unter einem anderen (erlaubt: Buchstabe, dann Buchstaben, Ziffern, _ und -)')))
        if not isinstance(d, dict) or not isinstance(d.get('aufruf'), str) or not d.get('aufruf', '').strip() \
                or not isinstance(d.get('braucht'), str) or not d.get('braucht'):
            befunde.append(bef('ablauf-probe-unvollstaendig', f'{ABLAUF}: Probe {p} ohne aufruf oder braucht'))
            continue
        fremde_schluessel(d, 'probe', f'Probe {p}')
        if braucht is not None and d['braucht'] not in braucht:
            befunde.append(bef('ablauf-braucht', f'{ABLAUF}: Probe {p}: braucht="{d["braucht"]}" kennt der Prüfstand '
                                                 f'nicht ({", ".join(sorted(braucht))}) — die Vorabprüfung entfiele still'))
        v = d.get('nach', [])
        if not isinstance(v, list) or not all(isinstance(x, str) for x in v):
            befunde.append(bef('ablauf-nach-form', f'{ABLAUF}: Probe {p}: nach ist keine Liste von Namen'))
            continue
        nach[p] = v

    # ablauf — jedes Muster
    dateien_alle = _ls_files(wurzel, '.') or []
    gesehen, wurzeln = {}, []
    for k, m in enumerate(muster, 1):
        if not isinstance(m, dict):
            befunde.append(bef('ablauf-muster-form', f'{ABLAUF}: Muster {k} ist kein Objekt'))
            continue
        name = m['id'] if isinstance(m.get('id'), str) and m['id'].strip() else f'Nr. {k}'
        fremde_schluessel(m, 'muster', f'Muster {name}')
        fehlt = [f for f in MUSTER_FELDER if f not in m]
        if fehlt:
            befunde.append(bef('ablauf-muster-felder', f'{ABLAUF}: Muster {name} ohne {", ".join(fehlt)} — '
                                                       f'auswahl.py oder erzeugen-doku stürzt daran ab (F-BR-19)'))
        if 'id' in m and not (isinstance(m['id'], str) and m['id'].strip()):
            befunde.append(bef('ablauf-muster-id', f'{ABLAUF}: Muster {k}: id ist keine Zeichenkette mit Inhalt'))
        if 'ab' in m and stufen and m['ab'] not in stufen:
            befunde.append(bef('ablauf-muster-ab', f'{ABLAUF}: Muster {name}: ab="{m["ab"]}" ist keine Stufe '
                                                   f'({", ".join(map(str, stufen))})'))
        if 'anlass' in m and not (isinstance(m['anlass'], str) and not gezeigt_leer(m['anlass'])):
            befunde.append(bef('ablauf-muster-anlass', f'{ABLAUF}: Muster {name}: anlass ist leer'))
        pf = m.get('pfade')
        if 'pfade' in m and (not isinstance(pf, list) or not pf or not all(isinstance(x, str) and x for x in pf)):
            befunde.append(bef('ablauf-muster-pfade', f'{ABLAUF}: Muster {name}: pfade ist keine Liste von Mustern '
                                                      f'— es greift nie, seine Proben laufen nie'))
        elif isinstance(pf, list) and callable(passt):
            # Jeder Pfad muss eine Datei treffen — gelesen mit DEM Abgleicher des
            # Prüfstands. Der erste Treffer dieser Prüfung war `server/api/spur*.php`,
            # der seit PK-03 keine Datei traf (Runde 3, F-BR-24).
            for x in pf:
                if not any(passt(f, [x]) for f in dateien_alle):
                    befunde.append(bef('ablauf-pfad-trifft-nie', f'{ABLAUF}: Muster {name}: „{x}" trifft keine '
                                                                 f'Datei im Auscheck — das Muster greift dafür nie'))
        pr = m.get('proben')
        if 'proben' in m and (not isinstance(pr, list) or not all(isinstance(x, str) for x in pr)):
            befunde.append(bef('ablauf-muster-proben', f'{ABLAUF}: Muster {name}: proben ist keine Liste von Namen'))
        elif isinstance(pr, list):
            wurzeln += [(x, f'Muster {name}') for x in pr]
        if isinstance(m.get('id'), str) and m['id'].strip():
            if m['id'] in gesehen:
                befunde.append(bef('ablauf-muster-doppelt', f'{ABLAUF}: Muster-id „{m["id"]}" steht zweimal '
                                                            f'(Muster {gesehen[m["id"]]} und {k})'))
            gesehen.setdefault(m['id'], k)

    # ablauf — jeder genannte Name existiert
    genannt = [(r, 'riegel') for r in riegel] + wurzeln + [(v, f'nach von {p}') for p, vs in nach.items() for v in vs]
    for n, wo in genannt:
        if n not in proben:
            befunde.append(bef('ablauf-unbekannt', f'{ABLAUF}: {wo} nennt {n}, das es unter proben nicht gibt'))

    # ablauf — `nach` im Kreis legt auswahl.py lahm (ValueError); jeder Kreis einmal
    # OHNE REKURSION: Eine Kette aus tausend `nach` brachte die rekursive
    # Fassung an Pythons Grenze, und die Gruppe brach ab (Runde 4).
    kreise, zustand = [], {}
    for start in sorted(nach):
        if zustand.get(start):
            continue
        zustand[start] = 1
        pfad, stapel = [start], [iter(nach.get(start, []))]
        while stapel:
            v = next(stapel[-1], None)
            if v is None:
                zustand[pfad.pop()] = 2
                stapel.pop()
            elif zustand.get(v) == 1:
                kreise.append(pfad[pfad.index(v):] + [v])
            elif v in nach and not zustand.get(v):
                zustand[v] = 1
                pfad.append(v)
                stapel.append(iter(nach.get(v, [])))
    for k in kreise:
        befunde.append(bef('ablauf-kreis', f'{ABLAUF}: nach im Kreis ({" -> ".join(k)}) — auswahl.py bricht daran ab'))

    # ablauf — `nach` an einem Riegel: auswahl.py zieht es nicht mit
    for r in riegel:
        if nach.get(r):
            befunde.append(bef('ablauf-riegel-nach', f'{ABLAUF}: Riegel {r} hat nach ({", ".join(nach[r])}) — '
                                                     f'auswahl.py löst nach nur für Proben aus Mustern auf'))

    # ablauf — jede Probe erreichbar: vom Riegel aus, oder von einem Muster aus
    # über `nach`. „Irgendwo genannt" reicht nicht (Runde 1).
    erreicht = set(r for r in riegel if r in proben)
    offen, gesehen_m = [x for x, _ in wurzeln if x in proben], set()
    while offen:
        x = offen.pop()
        if x in gesehen_m:
            continue
        gesehen_m.add(x)
        erreicht.add(x)
        offen += [v for v in nach.get(x, []) if v in proben]
    for p in proben:
        if p not in erreicht:
            befunde.append(bef('ablauf-unerreichbar', f'{ABLAUF}: Probe {p} ist von keinem Muster und keinem '
                                                      f'Riegel aus erreichbar — sie läuft nie'))

    # ablauf — jede Fläche des Berichts bekommt ihren Bau (Runde 4). Das Tor
    # verlangt „gebaut", sobald eine Datei der Fläche berührt ist, in JEDER
    # Stufe. Wählt das Muster die Bauprobe für eine solche Datei nicht aus,
    # ist jede Berührung dort rot, und kein Lauf kann es ändern. Gefragt wird
    # auswahl.treffer() selbst, für jede Datei der Fläche einzeln.
    treffer = getattr(auswahl, 'treffer', None) if auswahl else None
    for fl, (ordner_fl, probe) in sorted(flaechen.items()):
        if probe not in proben:
            befunde.append(bef('ablauf-flaeche-probe', f'{ERZEUGER_DOKU}: die Fläche {fl} baut mit „{probe}", und das '
                                                       f'steht nicht unter proben — jede Berührung von '
                                                       f'{", ".join(o + "/" for o in ordner_fl)} wäre rot'))
            continue
        if not callable(treffer) or not stufen:
            continue                                     # ablauf-auswahl ist schon gemeldet
        for o in ordner_fl:
            beispiele = [d for d in dateien_alle if d.startswith(o + '/')] or [o + '/neu']
            ohne = None
            for d in beispiele:
                try:
                    if probe not in treffer([d], stufen[0], a)[1]:
                        ohne = d
                        break
                except Exception:                        # noqa: BLE001 — eine kaputte Zuordnung meldet ihre Regel oben
                    break
            if ohne:
                befunde.append(bef('ablauf-flaeche', f'{ABLAUF}: {ohne} gehört zur Fläche {fl}, aber kein Muster ab '
                                                     f'Stufe {stufen[0]} wählt dafür {probe} aus — das Tor verlangt '
                                                     f'„{fl}=gebaut", und kein Lauf liefert es'))

    # ablauf — jede Probe eines Läufers hat einen Aufruf in der Ablaufdatei (Runde 2)
    aufrufe = ' '.join(d['aufruf'] for d in proben.values() if isinstance(d, dict) and isinstance(d.get('aufruf'), str))
    namen_q = [z.split('\t')[1] for z in (laeufer_liste(wurzel, QUELLTEXT, '--liste') or [])
               if z.startswith('NAME\t') and len(z.split('\t')) >= 2]
    for rel, kurz, namen in ((PROBEN_LAEUFER, 'proben.sh', sorted(ruf)), (QUELLTEXT, 'pruefen.sh', namen_q)):
        for n in namen:
            if not re.search(re.escape(f'{os.path.dirname(rel)}/{kurz}') + r'\s+' + re.escape(n) + r'(?![\w-])', aufrufe):
                befunde.append(bef('ablauf-laeufer', f'{ABLAUF}: {n} steht in {rel}, aber kein Eintrag unter proben '
                                                     f'ruft „{kurz} {n}" — im Prüfstand läuft es nie'))

    return befunde


def tabelle_pruefen(wurzel, alle, zahlen, ruf):
    """Regel `tabelle` — Abschnitt 4 ist genau die Ausgabe des Erzeugers."""
    befunde = []
    erzeuger = os.path.join(wurzel, ERZEUGER_DOKU)
    if not os.path.isfile(erzeuger):
        befunde.append(bef('tabelle-erzeuger-fehlt', f'{ERZEUGER_DOKU} fehlt — die Tabelle in {PRUEFABLAUF} 4 ist '
                                                     f'nicht prüfbar'))
        return befunde
    r = subprocess.run([sys.executable, erzeuger, 'erzeugen-doku'], cwd=wurzel, capture_output=True, text=True)
    if r.returncode != 0 or not r.stdout.strip():
        letzte = (r.stderr.strip().splitlines() or ['(keine Ausgabe)'])[-1]
        befunde.append(bef('tabelle-erzeuger-fehler', f'{ERZEUGER_DOKU} erzeugen-doku endete mit rc {r.returncode}: '
                                                      f'{letzte[:100]}'))
        return befunde
    if not os.path.isfile(os.path.join(wurzel, PRUEFABLAUF)):
        befunde.append(bef('tabelle-doku-fehlt', f'{PRUEFABLAUF} fehlt'))
        return befunde
    befunde += tabelle_vergleichen(lies(wurzel, PRUEFABLAUF), r.stdout)
    return befunde


def tabelle_vergleichen(doku, soll):
    """Befunde `tabelle`: Die Ausgabe des Erzeugers steht genau einmal im
    Dokument, in Abschnitt 4, Zeile für Zeile, gefolgt von einer Leerzeile —
    und keine ihrer Zeilen steht ein zweites Mal.

    ANFANG UND LÄNGE SAGT DER ERZEUGER, gesucht wird außerhalb von
    Codeblöcken, verglichen der ROHE Text. Eine veraltete Kopie — ein zweiter
    Block, die alte Riegelzeile unter der neuen, eine angehängte Tabelle ohne
    Kopfzeile — zeigt sich daran, dass eine Zeile der Ausgabe zweimal steht
    (Runden 1 bis 3)."""
    soll_zeilen = soll.rstrip('\n').split('\n')
    roh = doku.split('\n')
    draussen = list(ausserhalb_zaun(doku))
    bloecke = [i for i, z in draussen if ERZEUGT_RE.match(z) or z == soll_zeilen[0]]
    if not bloecke:
        return [bef('tabelle-kein-block', f'{PRUEFABLAUF}: keine Zeile „{soll_zeilen[0][:60]} …" außerhalb eines '
                                          f'Codeblocks — die erzeugte Tabelle fehlt')]
    if len(bloecke) > 1:
        return [bef('tabelle-mehrere-bloecke', f'{PRUEFABLAUF}: {len(bloecke)} erzeugte Blöcke (Zeilen '
                                               f'{", ".join(map(str, bloecke))}) — erwartet ist einer')]
    anfang = bloecke[0]
    aus = []
    ueberschrift = next((z for i, z in reversed(draussen) if i < anfang and z.startswith('## ')), '')
    if not ueberschrift.startswith('## 4'):
        aus.append(bef('tabelle-abschnitt', f'{PRUEFABLAUF}:{anfang}: der erzeugte Block steht unter '
                                            f'„{ueberschrift[:40] or "(keiner Überschrift)"}", nicht in Abschnitt 4'))
    for k, s in enumerate(soll_zeilen):
        ist = roh[anfang - 1 + k] if anfang - 1 + k < len(roh) else None
        if ist != s:
            zeigen = (ist if ist is not None else '(Ende)')[:70]
            return aus + [bef('tabelle-abweichung', f'{PRUEFABLAUF}:{anfang + k}: weicht von erzeugen-doku ab '
                                                    f'(„{zeigen}") — neu erzeugen: python3 {ERZEUGER_DOKU} '
                                                    f'erzeugen-doku (6.12, Schritt 6)')]
    danach = anfang - 1 + len(soll_zeilen)
    if danach < len(roh) and roh[danach].strip():
        aus.append(bef('tabelle-fortsetzung', f'{PRUEFABLAUF}:{danach + 1}: direkt unter der erzeugten Tabelle '
                                              f'steht Text — er setzt ihren letzten Absatz fort'))
    im_block = set(range(anfang, anfang + len(soll_zeilen)))
    kennungen = {kopie_schluessel(z) for z in soll_zeilen} - {None}
    for i, z in draussen:
        if i not in im_block and kopie_schluessel(z) in kennungen:
            aus.append(bef('tabelle-kopie', f'{PRUEFABLAUF}:{i}: diese Zeile beginnt wie eine Zeile der erzeugten '
                                            f'Tabelle („{z[:50]}") — eine veraltete Kopie?'))
            break
    return aus


def kopie_schluessel(z):
    """Woran eine Zeile der erzeugten Tabelle zu erkennen ist, auch wenn sie
    veraltet ist: eine Tabellenzeile an ihren ersten ZWEI Zellen (Berührung
    und Stufe), eine Textzeile an ihrem fetten Vorsatz bis `:**`, sonst an
    den ersten 30 Zeichen. Bis Runde 4 zählte nur die GLEICHE Zeile — die
    alte Riegelzeile mit einer Probe weniger war nicht gleich, sondern alt."""
    t = z.strip()
    if len(t) < 20 or t.startswith('<!--') or re.fullmatch(r'[|\s:-]+', t):
        return None
    if t.startswith('|'):
        zellen = [c.strip() for c in re.split(r'(?<!\\)\|', t.strip('|'))]
        return ('zeile', tuple(zellen[:2])) if len(zellen) >= 2 else None
    if ':**' in t:
        return ('text', t[:t.index(':**') + 3])
    return ('text', t[:30])


# ------------------------------------------------------------------- Lauf

# PHP liest PHP (E-BR-22): Je Datei die require/include-Stellen, ihr Ziel und
# ob sie im Rumpf einer Funktion stehen. Ein Ziel wird nur aus einer
# POSITIVLISTE ausgewertet — Zeichenketten, Zahlen, __DIR__, __FILE__,
# dirname(), realpath(), `.`, Klammern und Variablen, die vorher genauso
# gebaut wurden. Alles andere ist „nicht auflösbar", und kein eval() sieht es.
_PHP_LADEKETTE = r"""
$wurzel = rtrim($argv[1], '/');
$offen = array_slice($argv, 2); $aus = []; $gesehen = [];
function _lk_wert(array $toks, string $datei, array $vars) {
    $code = '';
    foreach ($toks as $x) {
        if (is_array($x)) {
            switch ($x[0]) {
                case T_WHITESPACE: case T_COMMENT: case T_DOC_COMMENT: continue 2;
                case T_DIR: $code .= var_export(dirname($datei), true); break;
                case T_FILE: $code .= var_export($datei, true); break;
                case T_CONSTANT_ENCAPSED_STRING: case T_LNUMBER: $code .= $x[1]; break;
                case T_VARIABLE:
                    if (!array_key_exists($x[1], $vars) || $vars[$x[1]] === null) { return null; }
                    $code .= var_export($vars[$x[1]], true); break;
                default:
                    $f = strtolower(ltrim($x[1], '\\'));
                    if (in_array($x[0], [T_STRING, defined('T_NAME_FULLY_QUALIFIED') ? T_NAME_FULLY_QUALIFIED : -1], true)
                            && in_array($f, ['dirname', 'realpath'], true)) { $code .= $f; break; }
                    return null;
            }
        } elseif (in_array($x, ['.', '(', ')', ','], true)) {
            $code .= $x;
        } else {
            return null;
        }
    }
    if ($code === '') { return null; }
    try { $v = eval('return ' . $code . ';'); } catch (Throwable $e) { return null; }
    return is_string($v) ? $v : null;
}
function _lk_norm(string $pfad): string {
    $teile = [];
    foreach (explode('/', $pfad) as $s) {
        if ($s === '' || $s === '.') { continue; }
        if ($s === '..') { array_pop($teile); continue; }
        $teile[] = $s;
    }
    return '/' . implode('/', $teile);
}
while ($offen) {
    $datei = _lk_norm(array_shift($offen));
    if (isset($gesehen[$datei])) { continue; }
    $gesehen[$datei] = true;
    $rel = substr($datei, strlen($wurzel) + 1);
    $aus[$rel] = [];
    if (!is_file($datei)) { continue; }
    $t = token_get_all(file_get_contents($datei));
    $n = count($t); $vars = []; $stapel = []; $funk = false;
    for ($i = 0; $i < $n; $i++) {
        $x = $t[$i];
        $faul = in_array('f', $stapel, true);
        if (is_array($x) && $x[0] === T_FUNCTION) { $funk = true; continue; }
        if ($x === ';') { $funk = false; continue; }
        if ($x === '{' || (is_array($x) && in_array($x[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true))) {
            $stapel[] = ($x === '{' && $funk) ? 'f' : 'x'; $funk = false; continue;
        }
        if ($x === '}') { array_pop($stapel); continue; }
        $j = $i + 1;
        $zuweisung = is_array($x) && $x[0] === T_VARIABLE && !$faul;
        if ($zuweisung) {
            while ($j < $n && is_array($t[$j]) && $t[$j][0] === T_WHITESPACE) { $j++; }
            $zuweisung = $j < $n && $t[$j] === '=';
        }
        $lade = is_array($x) && in_array($x[0], [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE], true);
        if (!$zuweisung && !$lade) { continue; }
        $start = $zuweisung ? $j + 1 : $i + 1; $tiefe = 0; $toks = [];
        for ($k = $start; $k < $n; $k++) {
            $y = $t[$k];
            if ($y === '(') { $tiefe++; } elseif ($y === ')') { if ($tiefe === 0) { break; } $tiefe--; }
            if (($y === ';' || $y === ',') && $tiefe === 0) { break; }
            if (is_array($y) && in_array($y[0], [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE], true)) { break; }
            $toks[] = $y;
        }
        $wert = _lk_wert($toks, $datei, $vars);
        if ($zuweisung) { $vars[$x[1]] = $wert; continue; }
        $ausdruck = trim(implode('', array_map(fn($y) => is_array($y) ? $y[1] : $y, $toks)));
        $ziel = $wert === null ? null : _lk_norm($wert !== '' && $wert[0] === '/' ? $wert : dirname($datei) . '/' . $wert);
        $zielrel = ($ziel !== null && strpos($ziel, $wurzel . '/') === 0) ? substr($ziel, strlen($wurzel) + 1) : null;
        $aus[$rel][] = ['ausdruck' => $ausdruck, 'ziel' => $zielrel, 'extern' => $ziel !== null && $zielrel === null,
                        'faul' => $faul, 'zeile' => $x[2]];
        if (!$faul && $zielrel !== null && is_file($ziel)) { $offen[] = $ziel; }
    }
}
echo json_encode($aus, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_SLASHES);
"""


def ladekette(wurzel, einstiege):
    """{relativer Pfad: [{ausdruck, ziel, extern, faul, zeile}]} über alle
    Dateien, die die Einstiege auf oberster Ebene laden — oder WerkzeugFehlt."""
    if not einstiege:
        return {}
    php = _werkzeug('php')
    w = os.path.abspath(wurzel)
    r = subprocess.run([php, '-r', _PHP_LADEKETTE, '--', w, *[os.path.join(w, e) for e in einstiege]],
                       capture_output=True, text=True, encoding='utf-8', errors='replace')
    if r.returncode != 0 or not r.stdout.strip().startswith(('{', '[')):
        raise WerkzeugFehlt(f'php (rc {r.returncode}: {(r.stderr or r.stdout).strip()[:80]})')
    daten = json.loads(r.stdout)
    return daten if isinstance(daten, dict) else {}


def anlage_pruefen(wurzel, alle, zahlen, ruf):
    """Regel `anlage` — lädt eine Probe ohne Anlage db.php oder config.php?"""
    befunde = []
    try:
        a = json.loads(lies(wurzel, ABLAUF))
    except (OSError, ValueError):
        return []                               # die Regel `ablauf` meldet das
    if not isinstance(a, dict) or not isinstance(a.get('proben'), dict):
        return []                               # ebenso: oben kein Objekt, proben kein Objekt
    namen = {}
    for z in laeufer_liste(wurzel, QUELLTEXT, '--liste') or []:
        teile = z.split('\t')
        if teile[0] == 'NAME' and len(teile) >= 3:
            namen[teile[1]] = teile[2]
    quelle = lies(wurzel, PROBEN_LAEUFER) if os.path.isfile(os.path.join(wurzel, PROBEN_LAEUFER)) else ''
    einstieg_je = {}                            # Probe → [PHP-Einstiege]
    ohne = [(n, d) for n, d in a['proben'].items()
            if isinstance(d, dict) and d.get('braucht') == 'nichts' and isinstance(d.get('aufruf'), str)]
    for n, d in ohne:
        aufruf = d['aufruf']
        php = set(re.findall(r'(tools/[\w./-]+\.php)\b', aufruf))
        for q in re.findall(re.escape(QUELLTEXT) + r'\s+([\w-]+)', aufruf):
            php |= set(re.findall(r'(tools/[\w./-]+\.php)\b', namen.get(q, '')))
        for q in re.findall(re.escape(PROBEN_LAEUFER) + r'\s+([\w-]+)', aufruf):
            e = einstieg(ruf[q], quelle) if q in ruf else None
            if e and e.endswith('.php'):
                php.add(e)
        einstieg_je[n] = sorted(x for x in php if x in alle)
    try:
        kette = ladekette(wurzel, sorted({e for v in einstieg_je.values() for e in v}))
    except WerkzeugFehlt as e:
        return [bef('anlage-werkzeug', f'{e} fehlt oder scheitert — die Ladeketten der Proben ohne Anlage '
                                       f'sind nicht messbar')]
    zahlen['anlage'] = (len(ohne), sum(len(v) for v in einstieg_je.values()), len(kette),
                        sum(1 for d in kette if d.startswith('server/')))

    def weg(start):
        """Kürzester Weg von start zu einer Datei aus ANLAGE, oder None."""
        vorher, offen = {start: None}, [start]
        while offen:
            d = offen.pop(0)
            for s in kette.get(d, []):
                z = s.get('ziel')
                if s.get('faul') or not z or z in vorher:
                    continue
                vorher[z] = d
                if z in ANLAGE:
                    pfad = [z]
                    while vorher[pfad[-1]] is not None:
                        pfad.append(vorher[pfad[-1]])
                    return list(reversed(pfad))
                offen.append(z)
        return None
    for n, einst in sorted(einstieg_je.items()):
        for e in einst:
            w = weg(e)
            if w:
                befunde.append(bef('anlage-db', f'{n}: {" → ".join(w)} — die Probe braucht keine Anlage, lädt aber '
                                                f'{w[-1]}; ohne config.php (Stufe 1) bricht sie ab (Nr. 329)'))
    for d, stellen in sorted(kette.items()):
        for s in stellen:
            if s.get('faul'):
                continue
            if s.get('ziel') is None:
                befunde.append(bef('anlage-unklar', f'{d}:{s.get("zeile")}: `{s.get("ausdruck", "")[:60]}` — '
                                                    + ('Ziel liegt außerhalb des Repositoriums'
                                                       if s.get('extern') else 'Ziel nicht auflösbar')
                                                    + '; ob es die Anlage lädt, ist nicht messbar'))
            elif s['ziel'] not in ANLAGE and not os.path.isfile(os.path.join(wurzel, s['ziel'])):
                befunde.append(bef('anlage-unklar', f'{d}:{s.get("zeile")}: lädt {s["ziel"]}, und das gibt es nicht'))
    return befunde


TOR_RIEGEL_RE = re.compile(r'--riegel\s+["\']?([\w-]+)=')


def tor_pruefen(wurzel, alle, zahlen, ruf):
    """Regel `tor` — übergibt das Tor jeden Riegel, und nur die?"""
    try:
        riegel = json.loads(lies(wurzel, ABLAUF)).get('riegel', {}).get('proben', [])
    except (OSError, ValueError, AttributeError):
        return []                               # die Regel `ablauf` meldet das
    if not isinstance(riegel, list):
        return []
    if not os.path.isfile(os.path.join(wurzel, TOR)):
        return [bef('tor-datei', f'{TOR} fehlt — kein Riegel wird im Tor gegengelesen')]
    # Kommentarzeilen zählen nicht; Fortsetzungszeilen (`\`) werden verbunden.
    text = '\n'.join(z.split(' #', 1)[0] for z in lies(wurzel, TOR).split('\n') if not z.lstrip().startswith('#'))
    text = re.sub(r'\\\n', ' ', text)
    aufrufe = [z for z in text.split('\n') if 'bericht.py lesen' in z and '--alle-riegel' in z]
    if not aufrufe:
        return [bef('tor-datei', f'{TOR} ruft `bericht.py lesen --alle-riegel` nicht — kein Riegel wird '
                                 f'im Tor gegengelesen')]
    im_tor = set()
    for z in aufrufe:
        im_tor |= set(TOR_RIEGEL_RE.findall(z))
    zahlen['tor'] = (len(riegel), len(im_tor & set(riegel)))
    befunde = [bef('tor-fehlt', f'{n}: steht in {ABLAUF} unter riegel, aber nicht als `--riegel {n}=` in {TOR} '
                                f'— mit --alle-riegel ist das im Tor rot, und örtlich fährt es niemand (F-P5c-172)')
               for n in riegel if n not in im_tor]
    befunde += [bef('tor-fremd', f'{n}: `--riegel {n}=` in {TOR}, aber kein Riegel in {ABLAUF}')
                for n in sorted(im_tor - set(riegel))]
    return befunde


def bericht(befunde, zahlen):
    melde('Bestandsriegel — tools/ gegen docs/Pruefablauf.md 6 (Konzept BR)')
    melde()
    erz = zahlen['erzeuger']
    melde(f"  Ordner unter tools/:     {zahlen['ordner']}"
          + (f"  (davon {len(erz)} mit Erzeugerform: {', '.join(erz)})" if erz else ''))
    melde(f"  Anleitungen (rekursiv):  {zahlen['anleitungen']}")
    melde(f"  Proben im Läufer:        {zahlen['proben']}")
    melde(f"  lose Dateien:            {len(zahlen['lose'])}  ({', '.join(zahlen['lose']) or '—'})")
    if 'quelltext' in zahlen:
        melde(f"  Quelltextprüfungen:      {zahlen['quelltext']}  (davon {zahlen['selbst']} mit Selbstprobe)")
    if 'ablauf' in zahlen:
        pr, mu, ri = zahlen['ablauf']
        melde(f"  pruefablauf.json:        {pr} Proben, {mu} Muster, {ri} Riegel")
    if 'anlage' in zahlen:
        ohne, ein, ke, se = zahlen['anlage']
        melde(f"  ohne Anlage:             {ohne} Proben, {ein} PHP-Einstiege, {ke} Dateien in der Ladekette "
              f"(davon {se} unter server/)")
    if 'tor' in zahlen:
        melde(f"  im Tor:                  {zahlen['tor'][1]} von {zahlen['tor'][0]} Riegeln übergeben")
    melde()
    for r in REGELN:
        melde(f'  {r:<10} {sum(1 for b in befunde if b[0] == r):>3} Befunde')
    if befunde:
        melde()
        melde('BEFUNDE:')
        for r, k, t in befunde:
            melde(f'  ! {r} · {t}  [{k}]')
        melde()
        melde(f'{len(befunde)} Befunde. Der Weg für ein neues Prüfmittel: docs/Pruefablauf.md 6.12.')
        return 1
    melde()
    melde('0 Befunde — der Bestand hält die Form.')
    return 0


# ------------------------------------------------------------ Selbstprobe

ANLEITUNG = '''# {titel}

Ein Werkzeug der Selbstprobe.
{anlass}

## Aufruf

```bash
python3 tools/{ordner}/lauf.py
## kein Abschnitt, nur ein Kommentar im Beispiel
```

## Was es misst

Etwas.

## Was es braucht

Nichts.

## Erwartete Zahl

Null.

## Was es nicht kann

Alles andere.
'''


def _anleitung(ordner, anlass='Anlass: Nr. 1 — ein erfundener Fehler.', titel='Werkzeug'):
    return ANLEITUNG.format(ordner=ordner, anlass=anlass, titel=titel)


PROBE_PHP = '''<?php
declare(strict_types=1);

/**
 * Probe eins — misst etwas.
 *
{anlass}
 */

$x = 1;
{unten}
'''

PROBE_MJS = '''/* Probe zwei — misst etwas anderes.
 *
 * Anlass: Nr. 2 — ein zweiter erfundener Fehler.
 */
import {{ x }} from './y.mjs';
'''

LAEUFER = '''#!/usr/bin/env bash
zwei_mit_nachbau() {
    python3 tools/proben/zwei/gegenstelle.py &
    node tools/proben/zwei/probe.mjs "$@"
}
declare -A RUF=(
  [eins]="php tools/proben/eins/probe.php"
  [zwei]="zwei_mit_nachbau"
)
case "${1:-}" in
  --liste) for n in $(printf '%s\\n' "${!RUF[@]}" | sort); do printf '  %-20s %s\\n' "$n" "${RUF[$n]}"; done ;;
esac
'''

QUELLTEXT_LAEUFER = '''#!/usr/bin/env bash
NAMEN=(eins
       zwei)
SELBST=(eins)
starter() {
    case "$1" in
        zwei)   echo "python3 tools/quelltext/$1.py" ;;
        *)      echo "php tools/quelltext/$1.php" ;;
    esac
}
case "${1:-}" in
  --liste)
    for n in "${NAMEN[@]}"; do printf 'NAME\\t%s\\t%s\\n' "$n" "$(starter "$n")"; done
    for n in "${SELBST[@]}"; do printf 'SELBST\\t%s\\n' "$n"; done ;;
esac
'''

QUELLTEXT_TABELLE = '''
| Name | Prüft | Anlass |
|---|---|---|
| `eins` | das Erste | Nr. 1 |
| `zwei` | das Zweite | PP-1 |
'''

ABLAUF_JSON = '''{
  "fassung": 1,
  "beschreibung": "Die Zuordnung der Selbstprobe.",
  "stufen": ["klein", "neben", "haupt"],
  "proben": {
    "q-eins": {"aufruf": "bash tools/quelltext/pruefen.sh eins", "braucht": "nichts"},
    "q-zwei": {"aufruf": "bash tools/quelltext/pruefen.sh zwei", "braucht": "nichts"},
    "eins":   {"aufruf": "bash tools/proben/proben.sh eins", "braucht": "installation"},
    "zwei":   {"aufruf": "bash tools/proben/proben.sh zwei", "braucht": "installation", "nach": ["eins"]},
    "android-bau": {"aufruf": "cd android && ./gradlew assembleDebug", "braucht": "android"},
    "uhr-stufe1":  {"aufruf": "bash tools/uhr-pruefstand/pruefstand.sh reihe liste.txt", "braucht": "uhr"}
  },
  "riegel": {"beschreibung": "x", "proben": ["q-eins", "q-zwei"]},
  "muster": [
    {"id": "grund", "ab": "klein", "pfade": ["server/**"], "proben": [], "anlass": "Auffang"},
    {"id": "zwei", "ab": "neben", "pfade": ["server/zwei.php"], "proben": ["zwei"], "anlass": "Nr. 2"},
    {"id": "android", "ab": "klein", "pfade": ["android/**"], "proben": ["android-bau"], "anlass": "Nr. 1"},
    {"id": "uhr", "ab": "klein", "pfade": ["watch/**", "tools/uhr-pruefstand/**"], "proben": ["uhr-stufe1"], "anlass": "Nr. 2"}
  ]
}
'''

TOR_YML = '''jobs:
  tor:
    steps:
      - run: |
          python3 tools/pruefstand/bericht.py lesen --commit "$KOPF" --alle-riegel \\
            --riegel "q-eins=$q" \\
            --riegel "q-zwei=$(r "$ZWEI")" > /tmp/x.txt
'''

_TABELLE = {}


def _pruefstand_dateien():
    """Der ganze Prüfstand dieses Auschecks (alle .py) — nicht zwei Dateien
    beim Namen: Bindet bericht.py ein weiteres Modul ein, stürzte die
    Selbstprobe sonst ab (Runde 3)."""
    d = os.path.join(WURZEL, 'tools', 'pruefstand')
    return {f'tools/pruefstand/{n}': lies(WURZEL, f'tools/pruefstand/{n}')
            for n in sorted(os.listdir(d)) if n.endswith('.py')}


def _erzeugte_tabelle(name='', eingriff=None):
    """Die Ausgabe von erzeugen-doku für ABLAUF_JSON — oder, mit `eingriff`,
    für eine geänderte Fassung davon: eine VERALTETE Tabelle. Einmal je Name
    erzeugt, mit dem ECHTEN Erzeuger dieses Auschecks. Die Selbstprobe baut
    ihn nicht nach, und eine veraltete Kopie hängt so an keinem Ausgabeformat
    — bis Runde 4 entstand sie durch Ersetzen von `| neben |`, und ein
    regelgerecht geänderter Erzeuger hätte sie still gleich gemacht."""
    if name not in _TABELLE:
        a = json.loads(ABLAUF_JSON)
        if eingriff:
            eingriff(a)
        d = tempfile.mkdtemp(prefix='bestand-tabelle-')
        try:
            for rel, inhalt in {**_pruefstand_dateien(),
                                ABLAUF: json.dumps(a, ensure_ascii=False, indent=2) if eingriff else ABLAUF_JSON}.items():
                os.makedirs(os.path.dirname(os.path.join(d, rel)), exist_ok=True)
                with open(os.path.join(d, rel), 'w', encoding='utf-8') as f:
                    f.write(inhalt)
            r = subprocess.run([sys.executable, os.path.join(d, ERZEUGER_DOKU), 'erzeugen-doku'],
                               cwd=d, capture_output=True, text=True, check=True)
            _TABELLE[name] = r.stdout
        finally:
            shutil.rmtree(d, ignore_errors=True)
    return _TABELLE[name]


def _alt_anlass(a):
    a['muster'][1]['anlass'] = 'Nr. 2 — die alte Fassung'


def _alt_stufe(a):
    a['muster'][1]['ab'] = 'klein'


def _alt_riegel(a):
    a['riegel']['proben'] = a['riegel']['proben'][:-1]


def _veraltet(name, eingriff):
    """Die Zeilen der veralteten Tabelle, die in der aktuellen nicht stehen."""
    neu = _erzeugte_tabelle().split('\n')
    aus = [z for z in _erzeugte_tabelle(name, eingriff).split('\n') if z not in neu]
    if not aus:
        raise AnkerFehlt(f'der Eingriff {name} ändert die erzeugte Tabelle nicht')
    return aus


def _grundbestand():
    """Ein Bestand, der jede Regel hält — mit Gegenproben darin: ein `##` im
    Codeblock, eine hervorgehobene Anlass-Zeile, die Erzeugerform, motor.mjs,
    eine ignorierte Ausgabe mit einer übergroßen Anleitung, eine Prüfung ohne
    Selbstprobe (`zwei`), eine Anlass-Spalte mit Kürzel (E-BR-07), eine Probe,
    die nur über `nach` hängt, eine Gegenstelle im Hintergrund und die
    beiden Flächen des Berichts mit ihren Bauproben."""
    return {
        **_pruefstand_dateien(),
        'docs/Backlog.md': 'Kopf.\n\n1. **Eins.**\n\n2. **Zwei.**\n',
        'docs/Sandbox-Setup.md': 'Die Anlage braucht `tools/erzeuger/bild.sh`.\n',
        'docs/Pruefablauf.md': '# Prüfablauf\n\n## 4. Berührung\n\nText.\n\n' + _erzeugte_tabelle()
                               + '\nDanach Text.\n\n## 5. Bericht\n',
        '.gitignore': 'tools/werkzeug/ausgabe/\n',
        '.github/workflows/p.yml': ('run: python3 tools/werkzeug/lauf.py\n'
                                    'run: |\n  bash tools/quelltext/pruefen.sh --selbstprobe\n'),
        'server/zwei.php': '<?php\n',
        TOR: TOR_YML,
        'android/app/Main.kt': 'fun main() {}\n',
        'watch/source/App.mc': 'class App {}\n',
        'tools/uhr-pruefstand/LIESMICH.md': _anleitung('uhr-pruefstand', 'Anlass: Nr. 2 — die Uhr.', 'Uhr'),
        'tools/motor.mjs': 'export const x = 1;\n',
        'tools/werkzeug/LIESMICH.md': _anleitung('werkzeug', '**Anlass: Nr. 1** — ein erfundener Fehler.'),
        'tools/werkzeug/lauf.py': 'print(1)\n',
        'tools/werkzeug/unter/LIESMICH.md': _anleitung('werkzeug/unter', '', 'Unterteil'),
        'tools/werkzeug/ausgabe/LIESMICH.md': 'x\n' * 100,
        'tools/erzeuger/LIESMICH.md': _anleitung('erzeuger', ERZEUGER, 'Erzeuger'),
        'tools/erzeuger/bild.sh': 'echo\n',
        'tools/proben/LIESMICH.md': _anleitung('proben', 'Anlass: Nr. 1, 2 — je Probe im Kopf ihrer Datei.', 'Proben'),
        'tools/proben/proben.sh': LAEUFER,
        'tools/proben/eins/probe.php': PROBE_PHP.format(anlass=' * Anlass: Nr. 1 — ein erfundener Fehler.', unten=''),
        'tools/proben/zwei/probe.mjs': PROBE_MJS,
        'tools/proben/zwei/gegenstelle.py': 'print(2)\n',
        'tools/quelltext/pruefen.sh': QUELLTEXT_LAEUFER,
        'tools/quelltext/eins.php': ("<?php\n$server = dirname(__DIR__, 2) . '/server';\n"
                                     "require_once $server . '/zwei.php';\n"
                                     "if (in_array('--selbstprobe', $argv, true)) { exit(0); }\n"),
        'tools/quelltext/zwei.py': 'print(2)  # hat keine --selbstprobe, und das ist hier richtig\n',
        'tools/quelltext/eins-ausnahmen.json': '{}\n',
        'tools/quelltext/LIESMICH.md': _anleitung('quelltext', 'Anlass: Nr. 1 — je Prüfung in der Tabelle.',
                                                  'Quelltext').replace('Etwas.\n', 'Etwas.\n' + QUELLTEXT_TABELLE),
        'tools/pruefstand/LIESMICH.md': _anleitung('pruefstand', 'Anlass: Nr. 2 — ein zweiter.', 'Prüfstand'),
        'tools/pruefstand/pruefablauf.json': ABLAUF_JSON,
        'tools/pruefstand/pruefen.sh': '# tools/pruefstand/ ruft sich hier selbst\n'
                                       'case "$braucht" in\n  uhr) : ;;\n  android) : ;;\nesac\n',
    }


class AnkerFehlt(Exception):
    """Ein Eingriff der Selbstprobe findet seinen Anker nicht mehr — ein roter
    Fall, kein Absturz (Runde 3: eine regelgerechte Änderung am Erzeuger
    brachte die Selbstprobe mit AssertionError zu Fall)."""


def _mehr(pfad, alt, neu):
    def f(b):
        if alt not in b.get(pfad, ''):
            raise AnkerFehlt(f'{pfad}: „{alt[:40]}"')
        b[pfad] = b[pfad].replace(alt, neu, 1)
    return f


def _setze(pfad, inhalt):
    def f(b):
        b[pfad] = inhalt
    return f


def _weg(pfad):
    def f(b):
        b.pop(pfad, None)
    return f


def _und(*eingriffe):
    def f(b):
        for e in eingriffe:
            e(b)
    return f


def _ohne(*werkzeuge):
    def f(b):
        b['__ohne__'] = set(werkzeuge)
    return f


def _abbruch(kennung):
    def f(b):
        b['__abbruch__'] = {kennung}
    return f


def _json(eingriff):
    """Ein Eingriff an der Ablaufdatei als Daten statt als Text."""
    def f(b):
        a = json.loads(b[ABLAUF])
        eingriff(a)
        b[ABLAUF] = json.dumps(a, ensure_ascii=False, indent=2) + '\n'
    return f


def _auf(pfad, zeilen):
    """Füllt eine Anleitung auf GENAU `zeilen` Zeilen auf."""
    def f(b):
        b[pfad] += 'Mehr.\n' * (zeilen - zeilenzahl(b[pfad]))
    return f


TABELLE_VIER = '''
| Name | Prüft | Anlass | Seit |
|---|---|---|---|
| `eins` | das Erste | {eins} | PK-04 |
| `zwei` | das Zweite | PP-1 | {seit} |
'''


def _tabelle(eins='Nr. 1', seit='BR'):
    return _mehr(QUELLTEXT_ANLEITUNG, QUELLTEXT_TABELLE, TABELLE_VIER.format(eins=eins, seit=seit))


def _zelle(alt, neu):
    return _mehr(QUELLTEXT_ANLEITUNG, alt, neu)


def _doku(alt, neu):
    return _mehr(PRUEFABLAUF, alt, neu)


def _tausche(name, eingriff):
    """Die erzeugte Tabelle im Dokument durch eine veraltete ersetzen."""
    def f(b):
        t = _erzeugte_tabelle()
        if t not in b[PRUEFABLAUF]:
            raise AnkerFehlt('die erzeugte Tabelle steht nicht im Grundbestand')
        b[PRUEFABLAUF] = b[PRUEFABLAUF].replace(t, _erzeugte_tabelle(name, eingriff))
    return f


def _veraltete_kopie(b):
    t = _erzeugte_tabelle()
    _tausche('alt-stufe', _alt_stufe)(b)
    b[PRUEFABLAUF] = b[PRUEFABLAUF].replace('## 4. Berührung\n', '## 4. Berührung\n\n```\n' + t + '```\n', 1)


def _kopfzeile(neu):
    """Die erste Zeile der erzeugten Tabelle, wie der Erzeuger sie heute schreibt, ersetzen."""
    def f(b):
        kopf = _erzeugte_tabelle().split('\n')[0]
        b[PRUEFABLAUF] = b[PRUEFABLAUF].replace(kopf + '\n', neu(kopf), 1)
    return f


def _darunter(zeilen):
    def f(b):
        b[PRUEFABLAUF] = b[PRUEFABLAUF].replace('\nDanach Text.', '\n' + '\n'.join(zeilen()) + '\n\nDanach Text.', 1)
    return f


# (Name, erwartete Kennungen — None für „keine Befunde", ein Str oder ein
# Tupel —, Eingriff). Gezählt wird die MENGE der Kennungen: genau diese, keine
# andere. Und jede Kennung aus KENNUNGEN muss in mindestens einem Fall fallen.
FAELLE = [
    ('GEGENPROBE: der Grundbestand hält jede Regel', None, lambda b: None),
    # form, anleitung, anlass
    ('form — Anleitung mit 41 Zeilen', 'form-zeilen', _auf('tools/werkzeug/LIESMICH.md', 41)),
    ('GEGENPROBE: Anleitung mit genau 40 Zeilen', None, _auf('tools/werkzeug/LIESMICH.md', 40)),
    ('form — ein Abschnitt fehlt', 'form-abschnitte',
     _mehr('tools/werkzeug/LIESMICH.md', '## Was es nicht kann\n\nAlles andere.\n', '')),
    ('form — zwei Abschnitte vertauscht', 'form-abschnitte',
     _mehr('tools/werkzeug/LIESMICH.md', '## Was es braucht\n\nNichts.\n\n## Erwartete Zahl\n\nNull.\n',
           '## Erwartete Zahl\n\nNull.\n\n## Was es braucht\n\nNichts.\n')),
    ('form — ein sechster Abschnitt', 'form-abschnitte',
     _mehr('tools/werkzeug/LIESMICH.md', 'Alles andere.\n', 'Alles andere.\n\n## Geschichte\n\nFrüher.\n')),
    ('form — Unteranleitung mit 41 Zeilen (E-BR-04)', 'form-zeilen', _auf('tools/werkzeug/unter/LIESMICH.md', 41)),
    ('GEGENPROBE: ```x``` am Zeilenanfang öffnet keinen Zaun', None,
     _mehr('tools/werkzeug/LIESMICH.md', 'Etwas.\n', '```x``` ist Code im Text.\n')),
    ('anleitung — Ordner ohne LIESMICH.md', 'anleitung-fehlt',
     _und(_setze('tools/neu/lauf.py', 'print(3)\n'),
          _mehr('tools/pruefstand/pruefen.sh', '# tools/pruefstand/', '# tools/neu/ tools/pruefstand/'))),
    ('anlass — keine Anlass-Zeile', 'anlass-keine',
     _mehr('tools/werkzeug/LIESMICH.md', '**Anlass: Nr. 1** — ein erfundener Fehler.', '')),
    ('anlass — mitten in einer Zeile', 'anlass-keine',
     _mehr('tools/werkzeug/LIESMICH.md', '**Anlass: Nr. 1**', 'Es misst. **Anlass: Nr. 1**')),
    ('anlass — Befund-Kennung statt Backlog-Nummer (E-BR-07)', 'anlass-keine-nummer',
     _mehr('tools/werkzeug/LIESMICH.md', '**Anlass: Nr. 1**', '**Anlass: F-XY-01**')),
    ('anlass — eine Nummer, die es im Backlog nicht gibt', 'anlass-nummer-fehlt',
     _mehr('tools/werkzeug/LIESMICH.md', 'Nr. 1**', 'Nr. 999**')),
    ('anlass — zwei Anlass-Zeilen', 'anlass-mehrere',
     _mehr('tools/werkzeug/LIESMICH.md', 'Ein Werkzeug der Selbstprobe.\n',
           'Ein Werkzeug der Selbstprobe.\nAnlass: Nr. 2 — noch einer.\n')),
    ('inventur — ein Ordner, den niemand ruft', 'inventur-ungerufen', _setze('docs/Sandbox-Setup.md', 'Nichts.\n')),
    ('lose — eine zweite lose Datei', 'lose-datei', _setze('tools/hilfe.php', '<?php\n')),
    ('GEGENPROBE: motor.mjs fehlt — auch das ist grün', None, _weg('tools/motor.mjs')),
    # probe
    ('probe — der Läufer fehlt', 'probe-laeufer', _weg('tools/proben/proben.sh')),
    ('probe — Einstiegsdatei aus einer Funktion nicht ermittelbar', ('probe-einstieg-unklar', 'probe-ordner-ohne-eintrag'),
     _mehr('tools/proben/proben.sh', '    node tools/proben/zwei/probe.mjs "$@"\n',
           '    node tools/proben/zwei/probe.mjs "$@"\n    php tools/proben/eins/probe.php\n')),
    ('GEGENPROBE: eine umbrochene Hintergrundzeile in der Funktion', None,
     _mehr('tools/proben/proben.sh', '    python3 tools/proben/zwei/gegenstelle.py &\n',
           '    python3 \\\n        tools/proben/zwei/gegenstelle.py &\n')),
    ('probe — Einstiegsdatei aus einer Funktion fehlt', 'probe-datei-fehlt', _weg('tools/proben/zwei/probe.mjs')),
    ('probe — keine Anlass-Zeile im Kopf', 'probe-keine-zeile',
     _mehr('tools/proben/eins/probe.php', ' * Anlass: Nr. 1 — ein erfundener Fehler.', ' * Kein Anlass.')),
    ('probe — Anlass-Zeile unter dem Kopf, im Code', 'probe-keine-zeile',
     _setze('tools/proben/eins/probe.php', PROBE_PHP.format(
         anlass=' * Kein Anlass.', unten='// Anlass: Nr. 1 — zu spät, das ist kein Kopf.'))),
    ('probe — zwei Anlass-Zeilen im Kopf', 'probe-mehrere',
     _mehr('tools/proben/eins/probe.php', ' * Anlass: Nr. 1 — ein erfundener Fehler.',
           ' * Anlass: Nr. 1 — ein erfundener Fehler.\n * Anlass: Nr. 2 — noch einer.')),
    ('probe — Kürzel statt Nummer im Kopf', 'probe-keine-nummer',
     _mehr('tools/proben/zwei/probe.mjs', 'Nr. 2', 'F-XY-02')),
    ('probe — Anlass mit Nummer, die es nicht gibt', 'probe-nummer-fehlt',
     _mehr('tools/proben/zwei/probe.mjs', 'Nr. 2', 'Nr. 22')),
    ('probe — die Erzeugerform gilt in einer Probe nicht', 'probe-erzeuger',
     _mehr('tools/proben/eins/probe.php', ' * Anlass: Nr. 1 — ein erfundener Fehler.', ' * ' + ERZEUGER)),
    ('probe — Probenordner ohne Eintrag im Läufer', 'probe-ordner-ohne-eintrag',
     _setze('tools/proben/drei/probe.php', '<?php\n/* Anlass: Nr. 1 — x. */\n')),
    # backlog
    ('backlog — eine Nummer zweimal', 'backlog-doppelt',
     _mehr('docs/Backlog.md', '2. **Zwei.**\n', '2. **Zwei.**\n\n2. **Noch einmal zwei.**\n')),
    ('backlog — ein Datum am Zeilenanfang zählt wie eine Nummer', 'backlog-doppelt',
     _mehr('docs/Backlog.md', '2. **Zwei.**\n', '2. **Zwei.** Aus der Durchsicht vom\n1.09.2026.\n')),
    ('backlog — eine Nummer steht in Backlog.md UND in Backlog-Erledigt.md (SD-02)', 'backlog-doppelt',
     _setze('docs/Backlog-Erledigt.md', 'Erledigt.\n\n2. **Zwei, ein zweites Mal.**\n')),
    # selbst
    ('selbst — der Quelltextläufer fehlt', 'selbst-laeufer', _weg(QUELLTEXT)),
    ('selbst — --liste nennt nur SELBST, keinen Namen', 'selbst-keine-namen',
     _mehr(QUELLTEXT, 'NAMEN=(eins\n       zwei)\n', 'NAMEN=()\n')),
    ('selbst — die Gruppe selbst/zeile bricht ab (Auffangnetz)', 'selbst-abbruch', _abbruch('selbst-abbruch')),
    ('selbst — pruefen.sh --selbstprobe steht in keinem Workflow', 'selbst-tor',
     _mehr('.github/workflows/p.yml', '  bash tools/quelltext/pruefen.sh --selbstprobe\n', '  bash tools/quelltext/pruefen.sh\n')),
    ('selbst — der Aufruf steht nur in einer Kommentarzeile', 'selbst-tor',
     _mehr('.github/workflows/p.yml', '  bash tools/quelltext/pruefen.sh --selbstprobe\n',
           '  # bash tools/quelltext/pruefen.sh --selbstprobe\n')),
    ('GEGENPROBE: der Aufruf steht statt im Workflow in der Ablaufdatei', None,
     _und(_mehr('.github/workflows/p.yml', '  bash tools/quelltext/pruefen.sh --selbstprobe\n', ''),
          _json(lambda a: a['proben']['q-eins'].update(aufruf='bash tools/quelltext/pruefen.sh --selbstprobe && '
                                                              'bash tools/quelltext/pruefen.sh eins')))),
    ('selbst — php fehlt (auch die Ladekette ist dann nicht messbar)', ('selbst-werkzeug', 'anlage-werkzeug'),
     _ohne('php')),
    ('GEGENPROBE: kaputtes UTF-8 in einer PHP-Zeichenkette (json_encode scheiterte)', None,
     _setze('tools/quelltext/eins.php', b"<?php\n$x = '\xc3\x28';\nif (in_array('--selbstprobe', $argv, true)) { exit(0); }\n")),
    ('selbst — starter() startet keine Datei unter tools/', 'selbst-befehl-ohne-datei',
     _mehr(QUELLTEXT, 'python3 tools/quelltext/$1.py', 'python3 -c pass')),
    ('selbst — ein Tippfehler im Pfad des starter()-Zweigs', 'selbst-datei-fehlt',
     _mehr(QUELLTEXT, 'python3 tools/quelltext/$1.py', 'python3 tools/quelltxt/$1.py')),
    ('selbst — .py-Datei, aber starter() startet .php', 'selbst-datei-fehlt',
     _mehr(QUELLTEXT, '        zwei)   echo "python3 tools/quelltext/$1.py" ;;\n', '')),
    ('selbst — starter() startet node (weder Python noch PHP)', 'selbst-sprache',
     _und(_mehr(QUELLTEXT, 'python3 tools/quelltext/$1.py', 'node tools/quelltext/$1.mjs'),
          _setze('tools/quelltext/zwei.mjs', 'console.log(2);\n'), _weg('tools/quelltext/zwei.py'))),
    ('selbst — eine .sh-Datei in tools/quelltext/, die NAMEN nicht kennt', 'selbst-sprache-datei',
     _setze('tools/quelltext/vier.sh', 'case "$1" in\n  --selbstprobe) exit 0 ;;\nesac\n')),
    ('selbst — Selbstprobe hinter --probe (wie textprobe bis BR-05)', 'selbst-probe-schalter',
     _mehr('tools/quelltext/zwei.py', 'print(2)', "import sys\nif '--probe' in sys.argv:\n    sys.exit(0)\nprint(2)")),
    ('selbst — PHP-Moduswort „selbstprobe" außerhalb getopt() schaltet --probe nicht ab', 'selbst-probe-schalter',
     _und(_mehr(QUELLTEXT, 'SELBST=(eins)', 'SELBST=()'),
          _setze('tools/quelltext/eins.php', "<?php\n$o = getopt('', ['hilfe']);\n$m = 'selbstprobe';\n"
                                             "if (in_array('--probe', $argv, true)) { exit(0); }\n"))),
    ('selbst — Selbstprobe im Code, Name fehlt in SELBST', 'selbst-nicht-in-selbst',
     _mehr(QUELLTEXT, 'SELBST=(eins)', 'SELBST=()')),
    ('selbst — PHP mit getopt(), nicht in SELBST', 'selbst-nicht-in-selbst',
     _und(_mehr(QUELLTEXT, 'SELBST=(eins)', 'SELBST=()'),
          _setze('tools/quelltext/eins.php', "<?php\n$o = getopt('', ['selbstprobe']);\n"))),
    ('GEGENPROBE: PHP mit getopt(), in SELBST', None,
     _setze('tools/quelltext/eins.php', "<?php\n$o = getopt('', ['selbstprobe']);\n")),
    ('selbst — Name in SELBST, aber keine Selbstprobe im Code', 'selbst-ohne-auswertung',
     _mehr(QUELLTEXT, 'SELBST=(eins)', 'SELBST=(eins zwei)')),
    ('selbst — PHP-Kommentar zitiert den Schalter (bis BR-05 grün)', 'selbst-ohne-auswertung',
     _setze('tools/quelltext/eins.php', "<?php\n// '--selbstprobe' kommt später\nexit(0);\n")),
    ('selbst — ein ?> im Kommentar, die Auswertung danach ist Ausgabetext', 'selbst-ohne-auswertung',
     _setze('tools/quelltext/eins.php', "<?php\n// Ende ?>\nif (in_array('--selbstprobe', $argv, true)) {}\n")),
    ('GEGENPROBE: PHP-Heredoc mit Apostroph vor der Auswertung', None,
     _setze('tools/quelltext/eins.php', "<?php\n$t = <<<TXT\nDas gibt's, und server/*.php auch.\nTXT;\n"
                                         "if (in_array('--selbstprobe', $argv, true)) { exit(0); }\n")),
    ('GEGENPROBE: \'--selbstprobe\' in Kommentar und Docstring ist keine Auswertung', None,
     _mehr('tools/quelltext/zwei.py', 'print(2)', '"""Hat kein \'--selbstprobe\'."""\n# ruft \'--selbstprobe\' NICHT\nprint(2)')),
    ('selbst — ein Name in SELBST, der nicht in NAMEN steht', 'selbst-ohne-namen',
     _mehr(QUELLTEXT, 'SELBST=(eins)', 'SELBST=(eins drei)')),
    ('selbst — eine Datei mit Selbstprobe, die NAMEN nicht kennt', 'selbst-fremde-datei',
     _setze('tools/quelltext/drei.py', "import sys\nif '--selbstprobe' in sys.argv:\n    sys.exit(0)\n")),
    ('selbst — dieselbe Datei mit Umlaut im Namen (git ohne -z sah sie nicht)', 'selbst-fremde-datei',
     _setze('tools/quelltext/prüfung.py', "import sys\nif '--selbstprobe' in sys.argv:\n    sys.exit(0)\n")),
    ('GEGENPROBE: NAMEN mit Kommentar (Klammer), in Anführungszeichen, mit +=', None,
     _mehr(QUELLTEXT, 'NAMEN=(eins\n       zwei)\n', 'NAMEN=("eins"   # die erste (siehe unten)\n       )\nNAMEN+=(zwei)\n')),
    # zeile
    ('zeile — cmark-gfm fehlt', 'zeile-werkzeug', _ohne('cmark-gfm')),
    ('zeile — die Anleitung fehlt', ('zeile-anleitung-fehlt', 'anleitung-fehlt'), _weg(QUELLTEXT_ANLEITUNG)),
    ('zeile — die Tabelle direkt unter einem zweizeiligen Listenpunkt (GitHub zeigt keine)', 'zeile-tabellen',
     _zelle('\n| Name | Prüft | Anlass |', '\n- ein Punkt\n  noch einer\n| Name | Prüft | Anlass |')),
    ('zeile — Trennzeile mit weniger Zellen als die Kopfzeile', 'zeile-tabellen', _zelle('|---|---|---|', '|---|---|')),
    ('GEGENPROBE: Trennzeile mit einem Strich je Zelle', None, _zelle('|---|---|---|', '|:-|-|-:|')),
    ('zeile — ein Name ohne Zeile in der Tabelle', 'zeile-keine', _zelle('| `zwei` | das Zweite | PP-1 |\n', '')),
    ('zeile — ein HTML-Kommentar beendet die Tabelle, die Zeile danach zählt nicht', 'zeile-keine',
     _zelle('| `zwei` | das Zweite | PP-1 |\n', '<!-- Pause -->\n| `zwei` | das Zweite | PP-1 |\n')),
    ('zeile — ein Name zweimal in der Tabelle', 'zeile-mehrfach',
     _zelle('| `zwei` | das Zweite | PP-1 |\n', '| `zwei` | das Zweite | PP-1 |\n| `zwei` | noch einmal | PP-1 |\n')),
    ('zeile — leere Anlass-Spalte', 'zeile-anlass-leer', _zelle('| `eins` | das Erste | Nr. 1 |', '| `eins` | das Erste |  |')),
    ('zeile — vierte Spalte, Anlass leer (gelesen wurde einmal die letzte Zelle)', 'zeile-anlass-leer', _tabelle(eins='')),
    ('GEGENPROBE: vierte Spalte mit „—", Anlass gefüllt', None, _tabelle(seit='—')),
    ('zeile — Zeile ohne Anlass-Zelle', 'zeile-anlass-leer', _zelle('| `eins` | das Erste | Nr. 1 |', '| `eins` | das Erste |')),
    ('zeile — leere Anlass-Zelle als „||"', 'zeile-anlass-leer', _zelle('| `eins` | das Erste | Nr. 1 |', '| `eins` | das Erste ||')),
    ('zeile — Anlass als -- (zwei Striche)', 'zeile-anlass-leer', _zelle('| Nr. 1 |', '| -- |')),
    ('zeile — Anlass als weicher Trennstrich (&shy;)', 'zeile-anlass-leer', _zelle('| Nr. 1 |', '| &shy; |')),
    ('zeile — ein | ohne \\ in einer Code-Spanne verschiebt die Spalten', 'zeile-zellen',
     _zelle('| `eins` | das Erste |', '| `eins` | das `a|b` Erste |')),
    ('GEGENPROBE: ein \\| in der Code-Spanne', None, _zelle('| `eins` | das Erste |', '| `eins` | das `a\\|b` Erste |')),
    ('zeile — Anlass als **—** (GitHub zeigt einen Strich)', 'zeile-anlass-leer',
     _zelle('| `eins` | das Erste | Nr. 1 |', '| `eins` | das Erste | **&mdash;** |')),
    ('zeile — eine Zeile, die der Läufer nicht kennt', 'zeile-fremd',
     _zelle('| `zwei` | das Zweite | PP-1 |\n', '| `zwei` | das Zweite | PP-1 |\n| `drei` | das Dritte | Nr. 2 |\n')),
    ('zeile — eine Zeile, deren Namenszelle ein Dateiname ohne Backticks ist', 'zeile-ohne-namen',
     _zelle('| `zwei` | das Zweite | PP-1 |\n', '| `zwei` | das Zweite | PP-1 |\n| drei.py | das Dritte | Nr. 2 |\n')),
    ('GEGENPROBE: ein verlinkter Name `[\\`eins\\`](eins.php)`', None,
     _zelle('| `eins` | das Erste |', '| [`eins`](eins.php) | das Erste |')),
    ('zeile + ablauf — NAMEN+=(drei) ohne Tabellenzeile und ohne Aufruf', ('zeile-keine', 'ablauf-laeufer'),
     _und(_mehr(QUELLTEXT, 'SELBST=(eins)', 'NAMEN+=(drei)\nSELBST=(eins)'), _setze('tools/quelltext/drei.php', '<?php\n'))),
    # ablauf
    ('ablauf — die Ablaufdatei fehlt', ('ablauf-fehlt', 'inventur-ungerufen', 'tabelle-erzeuger-fehler'), _weg(ABLAUF)),
    ('ablauf + tabelle — kein gültiges JSON (bis Runde 4 fiel tabelle still aus)', ('ablauf-json', 'tabelle-erzeuger-fehler'),
     _mehr(ABLAUF, '"stufen"', 'stufen')),
    ('ablauf — ein Probenschlüssel zweimal', 'ablauf-doppelt',
     _mehr(ABLAUF, '"proben": {\n', '"proben": {\n    "zwei": {"aufruf": "a", "braucht": "nichts"},\n')),
    ('ablauf — oben steht kein Objekt', ('ablauf-form-oben', 'tabelle-erzeuger-fehler'),
     _setze(ABLAUF, '["bash tools/quelltext/pruefen.sh eins", "bash tools/proben/proben.sh eins", '
                    '"bash tools/uhr-pruefstand/pruefstand.sh reihe"]\n')),
    ('ablauf — proben ist eine Liste', 'ablauf-form-proben',
     _json(lambda a: a.update(proben=['bash tools/quelltext/pruefen.sh eins', 'bash tools/proben/proben.sh eins']))),
    ('ablauf — muster ist leer', ('ablauf-form-muster', 'tabelle-abweichung'), _json(lambda a: a.update(muster=[]))),
    ('ablauf — riegel.proben ist keine Liste', ('ablauf-form-riegel', 'tabelle-abweichung'), _json(lambda a: a['riegel'].update(proben='q-eins'))),
    ('ablauf — nach am Muster statt an der Probe', 'ablauf-schluessel',
     _json(lambda a: a['muster'][1].update(nach=['eins']))),
    ('ablauf — vertippter Schlüssel an der Probe (Nach)', 'ablauf-schluessel',
     _json(lambda a: a['proben']['zwei'].update(Nach=['eins']))),
    ('ablauf — die Gruppe ablauf/tabelle bricht ab (Auffangnetz)', 'ablauf-abbruch', _abbruch('ablauf-abbruch')),
    ('GEGENPROBE: bemerkung an einer Probe', None, _json(lambda a: a['proben']['zwei'].update(bemerkung='x'))),
    ('ablauf — auswahl.py fehlt (bericht.py lädt dann auch nicht)', ('ablauf-auswahl', 'ablauf-flaechen', 'tabelle-erzeuger-fehler'),
     _weg(AUSWAHL)),
    ('ablauf — stufen in der Datei weicht von auswahl.py ab', 'ablauf-stufen', _mehr(ABLAUF, '"haupt"]', '"haupt", "gross"]')),
    ('GEGENPROBE: STUFEN in auswahl.py als Tupel', None,
     _mehr(AUSWAHL, "STUFEN = ['klein', 'neben', 'haupt']", "STUFEN = ('klein', 'neben', 'haupt')")),
    ('ablauf — der Prüfstand hat keinen braucht-Verteiler', 'ablauf-braucht-quelle',
     _setze('tools/pruefstand/pruefen.sh', '# tools/pruefstand/\n')),
    ('ablauf — Probe ohne braucht', 'ablauf-probe-unvollstaendig', _json(lambda a: a['proben']['q-eins'].pop('braucht'))),
    ('ablauf — braucht, den der Prüfstand nicht kennt', 'ablauf-braucht',
     _json(lambda a: a['proben']['eins'].update(braucht='andriod'))),
    ('ablauf — braucht in anderer Schreibung (Android)', 'ablauf-braucht',
     _json(lambda a: a['proben']['android-bau'].update(braucht='Android'))),
    ('ablauf — FLAECHEN in bericht.py nicht lesbar', 'ablauf-flaechen',
     _mehr(ERZEUGER_DOKU, "FLAECHEN = {'handy'", "FLAECHEN_ALT = {'handy'")),
    ('ablauf + tabelle — die Bauprobe einer Fläche umbenannt', ('ablauf-flaeche-probe', 'tabelle-abweichung'),
     _json(lambda a: (a['proben'].__setitem__('android-build', a['proben'].pop('android-bau')),
                      a['muster'][2].update(proben=['android-build'])))),
    ('ablauf + tabelle — ein Ordner der Fläche fehlt im Muster (tools/uhr-pruefstand/)', ('ablauf-flaeche', 'tabelle-abweichung'),
     _json(lambda a: a['muster'][3].update(pfade=['watch/**']))),
    ('ablauf + tabelle — das Muster der Fläche greift erst ab neben', ('ablauf-flaeche', 'tabelle-abweichung'),
     _json(lambda a: a['muster'][2].update(ab='neben'))),
    ('GEGENPROBE: auswahl.py bindet ein Geschwistermodul ein', None,
     _und(_setze('tools/pruefstand/stufen_hilfe.py', "STUFEN = ['klein', 'neben', 'haupt']\n"),
          _mehr(AUSWAHL, "STUFEN = ['klein', 'neben', 'haupt']", "from stufen_hilfe import STUFEN  # noqa: E402"))),
    ('ablauf — nach als Zeichenkette', ('ablauf-nach-form', 'ablauf-unerreichbar'),
     _json(lambda a: a['proben']['zwei'].update(nach='eins'))),
    ('ablauf — eine Probe heißt wie eine Fläche des Berichts', 'ablauf-probe-name',
     _json(lambda a: (a['proben'].update(handy={'aufruf': 'x', 'braucht': 'nichts'}), a['proben']['zwei']['nach'].append('handy')))),
    ('ablauf — ein Probenname, den das Tor anders liest', 'ablauf-probe-name',
     _json(lambda a: (a['proben'].update({'2fa': {'aufruf': 'x', 'braucht': 'nichts'}}), a['proben']['zwei']['nach'].append('2fa')))),
    ('ablauf + tabelle — ein Muster ist kein Objekt', ('ablauf-muster-form', 'tabelle-erzeuger-fehler'),
     _json(lambda a: a['muster'].append('text'))),
    ('ablauf — Muster ohne id (der KeyError aus F-BR-19)', 'ablauf-muster-felder', _json(lambda a: a['muster'][1].pop('id'))),
    ('ablauf — Muster-id als Liste', 'ablauf-muster-id', _json(lambda a: a['muster'][1].update(id=['zwei']))),
    ('ablauf — Muster-id als Zahl (die Tabelle zeigt keine id)', 'ablauf-muster-id',
     _json(lambda a: a['muster'][1].update(id=5))),
    ('ablauf + tabelle — ab ist keine Stufe', ('ablauf-muster-ab', 'tabelle-abweichung'),
     _json(lambda a: a['muster'][1].update(ab='mittel'))),
    ('ablauf + tabelle — leerer anlass', ('ablauf-muster-anlass', 'tabelle-abweichung'),
     _json(lambda a: a['muster'][1].update(anlass=''))),
    ('ablauf + tabelle — anlass ist nur ein Strich', ('ablauf-muster-anlass', 'tabelle-abweichung'),
     _json(lambda a: a['muster'][1].update(anlass='—'))),
    ('ablauf + tabelle — pfade als Zeichenkette', ('ablauf-muster-pfade', 'tabelle-abweichung'),
     _json(lambda a: a['muster'][1].update(pfade='server/zwei.php'))),
    ('ablauf + tabelle — ein pfad, der keine Datei trifft (wie server/api/spur*.php)', ('ablauf-pfad-trifft-nie', 'tabelle-abweichung'),
     _json(lambda a: a['muster'][1]['pfade'].append('server/api/spur*.php'))),
    ('ablauf — proben am Muster ist keine Liste', ('ablauf-muster-proben', 'ablauf-unerreichbar', 'tabelle-abweichung'),
     _json(lambda a: a['muster'][1].update(proben='zwei'))),
    ('ablauf — eine Muster-id zweimal', ('ablauf-muster-doppelt', 'tabelle-abweichung'),
     _json(lambda a: a['muster'].append(dict(a['muster'][1])))),
    ('ablauf — nach nennt eine Probe, die es nicht gibt', ('ablauf-unbekannt', 'ablauf-unerreichbar'),
     _json(lambda a: a['proben']['zwei'].update(nach=['einss']))),
    ('ablauf — nach im Kreis', 'ablauf-kreis', _json(lambda a: a['proben']['eins'].update(nach=['zwei']))),
    ('ablauf — tausend Waisen an einer nach-Kette (die rekursive Suche brach ab)', 'ablauf-unerreichbar',
     _json(lambda a: a['proben'].update({f'k{i}': {'aufruf': 'a', 'braucht': 'nichts', 'nach': [f'k{i + 1}'] if i < 1199 else []}
                                         for i in range(1200)}))),
    ('ablauf — eine Probe nur am nach eines Riegels', ('ablauf-riegel-nach', 'ablauf-unerreichbar'),
     _json(lambda a: (a['proben']['q-zwei'].update(nach=['vier']), a['proben'].update(vier={'aufruf': 'a', 'braucht': 'nichts'})))),
    ('ablauf — zwei Waisen halten einander per nach', ('ablauf-unerreichbar', 'ablauf-kreis'),
     _json(lambda a: a['proben'].update(w1={'aufruf': 'a', 'braucht': 'nichts', 'nach': ['w2']},
                                        w2={'aufruf': 'a', 'braucht': 'nichts', 'nach': ['w1']}))),
    ('ablauf — eine Probe des Läufers ohne Eintrag unter proben', 'ablauf-laeufer',
     _und(_mehr('tools/proben/proben.sh', '  [zwei]="zwei_mit_nachbau"\n',
                '  [zwei]="zwei_mit_nachbau"\n  [drei]="php tools/proben/drei/probe.php"\n'),
          _setze('tools/proben/drei/probe.php', PROBE_PHP.format(anlass=' * Anlass: Nr. 1 — x.', unten='')))),
    # tabelle
    ('tabelle — der Erzeuger fehlt (und mit ihm FLAECHEN)', ('tabelle-erzeuger-fehlt', 'ablauf-flaechen'), _weg(ERZEUGER_DOKU)),
    ('tabelle — die Gruppe tabelle bricht ab (Auffangnetz)', 'tabelle-abbruch', _abbruch('tabelle-abbruch')),
    ('tabelle — Pruefablauf.md fehlt', 'tabelle-doku-fehlt', _weg(PRUEFABLAUF)),
    ('tabelle — die Kopfzeile des erzeugten Blocks fehlt', 'tabelle-kein-block',
     _kopfzeile(lambda kopf: '<!-- von Hand -->\n')),
    ('tabelle — ein zweiter, veralteter Block darunter', 'tabelle-mehrere-bloecke',
     lambda b: b.__setitem__(PRUEFABLAUF, b[PRUEFABLAUF] + '\n' + _erzeugte_tabelle('alt-anlass', _alt_anlass))),
    ('GEGENPROBE: die Kopfzeile, zitiert in einem Codeblock davor', None,
     _kopfzeile(lambda kopf: '```\n' + kopf + '\n```\n\n' + kopf + '\n')),
    ('GEGENPROBE: ein Block einer anderen Erzeugerart desselben Werkzeugs', None,
     _doku('## 5. Bericht\n', '## 5. Bericht\n\n<!-- ERZEUGT von tools/pruefstand/bericht.py erzeugen-doku-stufen -->\nx\n')),
    ('tabelle — Ablaufdatei geändert, Tabelle nicht neu erzeugt', 'tabelle-abweichung',
     _mehr(ABLAUF, '"anlass": "Nr. 2"', '"anlass": "Nr. 2 und mehr"')),
    ('tabelle — eine Zeile von Hand geändert (die Tabelle der alten Fassung)', 'tabelle-abweichung',
     _tausche('alt-anlass', _alt_anlass)),
    ('tabelle — echte Tabelle von Hand geändert, aktuelle Kopie im Codeblock davor', 'tabelle-abweichung',
     _veraltete_kopie),
    ('tabelle — Text direkt unter der Tabelle setzt ihren Absatz fort', 'tabelle-fortsetzung',
     _doku('\nDanach Text.', 'Angehängt, ohne Leerzeile.')),
    ('tabelle — der Block steht in einem anderen Abschnitt', 'tabelle-abschnitt',
     _doku('## 4. Berührung\n', '## 3. Stufen\n')),
    ('tabelle — die letzte Zeile steht eine Leerzeile darunter noch einmal', 'tabelle-kopie',
     _darunter(lambda: [_erzeugte_tabelle().rstrip('\n').split('\n')[-1]])),
    ('tabelle — die alte Riegelzeile mit einer Probe weniger steht darunter', 'tabelle-kopie',
     _darunter(lambda: _veraltet('alt-riegel', _alt_riegel))),
    ('tabelle — eine veraltete Musterzeile steht über dem Block', 'tabelle-kopie',
     lambda b: _doku('## 4. Berührung\n\nText.\n', '## 4. Berührung\n\nText.\n\n'
                     + '\n'.join(_veraltet('alt-anlass', _alt_anlass)) + '\n')(b)),
    ('GEGENPROBE: der Erzeuger formuliert seine letzte Zeile um, Tabelle neu erzeugt', None,
     _und(_mehr(ERZEUGER_DOKU, "'**Die billigen Riegel laufen in jeder Stufe, ohne Muster:** '", "'**Riegel, jede Stufe:** '"),
          _doku('**Die billigen Riegel laufen in jeder Stufe, ohne Muster:** ', '**Riegel, jede Stufe:** '))),
    ('GEGENPROBE: der Erzeuger gibt selbst einen Codeblock aus', None,
     _und(_mehr(ERZEUGER_DOKU, "    melde('| Berührung | ab Stufe | Proben | Anlass |')",
                "    melde('```')\n    melde('x')\n    melde('```')\n    melde('| Berührung | ab Stufe | Proben | Anlass |')"),
          _doku('| Berührung | ab Stufe | Proben | Anlass |', '```\nx\n```\n| Berührung | ab Stufe | Proben | Anlass |'))),
    # anlage — Proben ohne Anlage laden weder db.php noch config.php (R4-02, Nr. 329)
    ('anlage — eine Quelltextprüfung lädt über eine Serverbibliothek db.php', 'anlage-db',
     _und(_setze('server/zwei.php', "<?php\nrequire_once __DIR__ . '/db.php';\n"), _setze('server/db.php', '<?php\n'))),
    ('anlage — eine Probe aus dem Läufer lädt config.php über eine Variable', 'anlage-db',
     _und(_json(lambda a: a['proben']['eins'].__setitem__('braucht', 'nichts')),
          _mehr('tools/proben/eins/probe.php', '$x = 1;',
                "$s = dirname(__DIR__, 3) . '/server';\nrequire $s . '/config.php';\n$x = 1;"))),
    ('anlage — nach einer Klasse mit {$x} im Text steht db.php wieder oben', 'anlage-db',
     _und(_setze('server/zwei.php', '<?php\nclass K {\n    public function f(): string { $a = 1; return "{$a}"; }\n}\n'
                                    "require_once __DIR__ . '/db.php';\n"), _setze('server/db.php', '<?php\n'))),
    ('GEGENPROBE: db.php nur im Rumpf einer Funktion — es läuft erst beim Aufruf', None,
     _setze('server/zwei.php', "<?php\nfunction verbinden(): void\n{\n    require_once __DIR__ . '/db.php';\n}\n")),
    ('anlage — ein require mit einem Pfad, der erst zur Laufzeit feststeht', 'anlage-unklar',
     _setze('server/zwei.php', "<?php\nrequire $irgendwas . '/x.php';\n")),
    ('anlage — ein require auf eine Datei, die es nicht gibt', 'anlage-unklar',
     _setze('server/zwei.php', "<?php\nrequire_once __DIR__ . '/fehlt.php';\n")),
    ('anlage — die Gruppe bricht ab', 'anlage-abbruch', _abbruch('anlage-abbruch')),
    # tor — jeder Riegel steht im Aufruf von bericht.py lesen (R4-02, F-P5c-172)
    ('tor — pruefung.yml fehlt', 'tor-datei', _weg(TOR)),
    ('tor — bericht.py lesen ohne --alle-riegel', 'tor-datei', _mehr(TOR, ' --alle-riegel', '')),
    ('tor — ein Riegel fehlt im Tor', 'tor-fehlt', _mehr(TOR, '            --riegel "q-eins=$q" \\\n', '')),
    ('tor — der Riegel steht nur in einer Kommentarzeile', 'tor-fehlt',
     _mehr(TOR, '            --riegel "q-eins=$q" \\\n', '            \\\n          # --riegel "q-eins=$q"\n')),
    ('tor — ein Name im Tor, der kein Riegel ist', 'tor-fremd',
     _mehr(TOR, ' --alle-riegel', ' --alle-riegel --riegel "q-drei=$q"')),
    ('GEGENPROBE: der Aufruf in einer Zeile statt umbrochen', None,
     _setze(TOR, 'run: python3 tools/pruefstand/bericht.py lesen --alle-riegel --riegel q-eins=1 --riegel "q-zwei=2"\n')),
    ('tor — die Gruppe bricht ab', 'tor-abbruch', _abbruch('tor-abbruch')),
]


def _bauen(wurzel, bestand):
    for rel, inhalt in bestand.items():
        if rel.startswith('__'):
            continue
        pfad = os.path.join(wurzel, rel)
        os.makedirs(os.path.dirname(pfad), exist_ok=True)
        with open(pfad, 'wb') as f:
            f.write(inhalt if isinstance(inhalt, bytes) else inhalt.encode('utf-8'))
    subprocess.run(['git', '-C', wurzel, 'init', '-q'], capture_output=True, check=True)


def selbstprobe():
    """Je Befundstelle mindestens ein eingebauter Fehler, und jeder muss GENAU
    die erwarteten Kennungen ergeben — dazu Gegenproben, die grün bleiben.

    EINE SELBSTPROBE, DIE NUR DEN GRÜNEN FALL FÄHRT, BELEGT NICHTS. Und eine,
    die nur Regelnamen vergleicht, belegt nicht, welche Prüfung anschlug: Bis
    Runde 3 ließen sich 23 von 67 Befundstellen streichen, und sie blieb grün.
    Seither muss jede Kennung aus KENNUNGEN in einem Fall fallen."""
    melde('Selbstprobe des Bestandsriegels')
    melde()
    for w in ('php', 'cmark-gfm', 'bash', 'git'):
        if not shutil.which(w):
            melde(f'{w} fehlt — die Selbstprobe kann nichts messen (Ausbaustufe web).')
            return 2
    try:
        _erzeugte_tabelle()
    except subprocess.CalledProcessError as e:
        melde(f'{ERZEUGER_DOKU} erzeugen-doku scheitert am Grundbestand der Selbstprobe (rc {e.returncode}): '
              f'{(e.stderr or "").strip()[-200:]}')
        melde('ABLAUF_JSON muss nachgezogen werden — gemessen wurde nichts.')
        return 2
    fehl, gefallen = 0, set()
    for name, erwartet, eingriff in FAELLE:
        bestand = _grundbestand()
        ordner = tempfile.mkdtemp(prefix='bestand-')
        try:
            try:
                eingriff(bestand)
            except AnkerFehlt as e:
                fehl += 1
                melde(f'  [FEHL] {name}\n         Anker fehlt: {e} — die Selbstprobe muss nachgezogen werden')
                continue
            _OHNE.clear()
            _OHNE.update(bestand.get('__ohne__', set()))
            _ABBRUCH.clear()
            _ABBRUCH.update(bestand.get('__abbruch__', set()))
            _bauen(ordner, bestand)
            befunde, _ = messen(ordner)
        finally:
            _OHNE.clear()
            _ABBRUCH.clear()
            shutil.rmtree(ordner, ignore_errors=True)
        ist = {k for _, k, _ in befunde}
        soll = set() if erwartet is None else ({erwartet} if isinstance(erwartet, str) else set(erwartet))
        gefallen |= ist
        ok = ist == soll
        fehl += not ok
        melde(f"  [{'ok  ' if ok else 'FEHL'}] {name}")
        if befunde and (soll or not ok):
            melde(f'         {befunde[0][1]} · {befunde[0][2][:100]}')
        if not ok:
            melde(f'         erwartet: {", ".join(sorted(soll)) or "keine Befunde"} · '
                  f'gemessen: {", ".join(sorted(ist)) or "keine"}')
    # --wurzel als RELATIVER Pfad (bis BR-05 rot: der Erzeuger lief mit cwd=wurzel)
    ordner = tempfile.mkdtemp(prefix='bestand-')
    try:
        _bauen(ordner, _grundbestand())
        befunde, _ = messen(os.path.relpath(ordner))
    finally:
        shutil.rmtree(ordner, ignore_errors=True)
    ok = not befunde
    fehl += not ok
    melde(f"  [{'ok  ' if ok else 'FEHL'}] GEGENPROBE: --wurzel als relativer Pfad")
    nie = sorted(set(KENNUNGEN) - gefallen)
    if nie:
        fehl += 1
        melde(f'  [FEHL] {len(nie)} Befundstellen fallen in keinem Fall: {", ".join(nie)}')
    melde()
    mit = sum(1 for f in FAELLE if f[1])
    melde(f'{len(FAELLE) + 1} Fälle, {fehl} Fehlschläge  ({mit} mit eingebautem Fehler, '
          f'{len(FAELLE) + 1 - mit} Gegenproben; {len(gefallen & set(KENNUNGEN))} von {len(KENNUNGEN)} '
          f'Befundstellen gefallen)')
    return 1 if fehl else 0


def main():
    p = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('--selbstprobe', action='store_true', help='je Befundstelle ein eingebauter Fehler')
    p.add_argument('--wurzel', default=WURZEL, help='anderer Auscheck (z. B. ein Arbeitsbaum)')
    a = p.parse_args()
    if a.selbstprobe:
        return selbstprobe()
    try:
        befunde, zahlen = messen(a.wurzel)
    except FileNotFoundError as e:
        melde(f'Grundlage fehlt: {e} — gemessen wurde nichts.')
        return 2
    return bericht(befunde, zahlen)


if __name__ == '__main__':
    sys.exit(main())
