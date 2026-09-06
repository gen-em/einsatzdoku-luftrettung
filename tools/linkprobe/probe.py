# -*- coding: utf-8 -*-
"""
Linkprobe — zeigt jeder Verweis auf einen Parameter, den die Zielseite liest?

WOFUER. Ein Verweis `xyz.php?a=1` auf eine Seite, die `$_GET['b']` liest,
ist ein Fehler, den kein vorhandenes Pruefmittel sieht: Der Bilderlauf
fotografiert Seiten und klickt keine Knoepfe, die Wortliste zaehlt Woerter,
die Vollstaendigkeitspruefung sieht das Stylesheet an. Der Fehler faellt
deshalb erst im Betrieb auf — und dort auch nur dann, wenn jemand genau
diesen Knopf drueckt.

Genau so ist Backlog Nr. 148 entstanden: Die Ueberschneidungswarnung der
Startseite verwies auf `diensttag_zusammenfuehren.php?ziel=`, die Seite liest
`$_GET['d']`. Ergebnis war eine 404-Seite — und zwar in genau dem Fall, fuer
den die Warnung gebaut ist. Zwei andere Verweise auf dieselbe Seite, keine
vierzig Zeilen entfernt, benutzten `?d=` richtig.

WAS SIE MISST. Alle Zeichenketten der Form `<seite>.php?…` in `server/`
(PHP und JavaScript, auch in zusammengesetzten Adressen), gehalten gegen die
Parameter, die die Zielseite tatsaechlich liest: `$_GET[…]`, `$_REQUEST[…]`,
`filter_input(INPUT_GET, …)`. JEDER Parameter der Adresse, nicht nur der
erste, und `&amp;` gilt als Trenner wie `&`. Drei Ergebnisse je Fund:

  FEHLT      die Zielseite liest diesen Namen nicht — ein Befund
  ZIEL WEG   die Zieldatei gibt es nicht — ein Befund
  DYNAMISCH  die Zielseite liest ihre Parameter ueber eine Variable
             (`$_GET[$name]`, wie `konten_param()` in `admin_users.php`) —
             statisch nicht entscheidbar, wird gezaehlt und nicht gemeldet

WAS SIE NICHT KANN, und warum das kein Mangel ist:

  - Ein Verweis, dessen ZIEL erst zur Laufzeit entsteht, taucht hier nicht
    auf. Es gibt einen: `admin_stammdaten.php:571` baut
    `$seite . '&ev=' . $vid`. Von Hand nachgesehen und richtig — aber die
    Probe koennte es nicht sagen, und das steht hier statt in einer
    Ausnahmeliste, die es verschwinden liesse.
  - Sie prueft NAMEN, nicht WERTE. Ein `?d=<Kalendertag>` an einer Seite, die
    unter `d` eine Kennung erwartet, ist fuer sie in Ordnung — er ist es
    nicht. Das ist die Grenze eines statischen Abgleichs und steht so in
    LIESMICH.md.
  - Sie ersetzt keinen Klick. Ob der Knopf sichtbar ist und wohin er fuehrt,
    sagt der Browser.

Ausnahmen stehen in `ausnahmen.md` daneben — mit Begruendung, nicht als
Ausblendung.

Aufruf und Bedeutung stehen in LIESMICH.md daneben.
Kein PHP noetig; nur Python 3.
"""
import argparse
import os
import re
import sys

HIER   = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))
SERVER = os.path.join(WURZEL, 'server')

# Verzeichnisse, die nicht uns gehoeren: fremde Bibliotheken und Schriften.
FREMD = ('vendor', 'fonts', 'demo')

# Ein Verweis: `seite.php?…` in einer Zeichenkette, einfach oder doppelt
# gequotet — samt dem Rest DIESER Zeichenkette, damit auch der zweite und
# dritte Parameter mitkommen (`?t=rettungsmittel&ev=`). Der Punkt vor `php`
# ist Pflicht: sonst faengt das Muster jede Zuweisung `a?b=` eines
# Fragezeichenoperators mit ein.
#
# Der erste Entwurf las nur den ERSTEN Parameter und uebersah damit jedes
# `&name=` — in diesem Bestand allein acht Stueck. Ein Pruefmittel, das die
# Haelfte misst und die ganze Zahl meldet, ist schlimmer als keines.
VERWEIS = re.compile(r"""['"]([A-Za-z0-9_./-]+\.php)\?([^'"]*)""")
# Ein Parametername steht am Anfang des Abfrageteils oder hinter einem
# Trenner. `&amp;` gehoert dazu: Im Markup steht der Trenner maskiert
# (`zeitraum.php?y=…&amp;m=…`, ui.php), und ohne diesen Zweig fiele jeder
# zweite Parameter einer solchen Adresse still durch.
PARAMETER = re.compile(r"""(?:^|&amp;|&)([A-Za-z0-9_]+)=""")

# Was eine Seite liest. Vier Formen, damit keine still durchrutscht.
LIEST = (
    re.compile(r"""\$_GET\s*\[\s*'([^']+)'\s*\]"""),
    re.compile(r'''\$_GET\s*\[\s*"([^"]+)"\s*\]'''),
    re.compile(r"""\$_REQUEST\s*\[\s*'([^']+)'\s*\]"""),
    re.compile(r"""INPUT_GET\s*,\s*'([^']+)'"""),
)

# `$_GET[$irgendwas]` — die Seite entscheidet zur Laufzeit, welchen Namen sie
# liest. Ein statischer Abgleich kann darueber nichts sagen.
DYNAMISCH = re.compile(r"""\$_(?:GET|REQUEST)\s*\[\s*\$""")


def lies(pfad):
    with open(pfad, encoding='utf-8', errors='replace') as f:
        return f.read()


def quelldateien(endungen):
    """Alle eigenen Quelldateien unter server/ — ohne vendor, fonts, demo."""
    for dp, dns, fns in os.walk(SERVER):
        dns[:] = [d for d in dns if d not in FREMD]
        for fn in sorted(fns):
            if fn.endswith(endungen):
                yield os.path.join(dp, fn)


def zielseiten():
    """Je Seite unter server/: welche Parameter liest sie, liest sie dynamisch?"""
    seiten = {}
    for pfad in quelldateien(('.php',)):
        s = lies(pfad)
        namen = set()
        for muster in LIEST:
            namen |= set(muster.findall(s))
        rel = os.path.relpath(pfad, SERVER).replace(os.sep, '/')
        seiten[rel] = {'liest': namen, 'dynamisch': DYNAMISCH.search(s) is not None}
    return seiten


def listen():
    """Die zwei Tabellen aus ausnahmen.md.

    Erste Tabelle: Ausnahmen (der Abgleich ist nicht zu fuehren).
    Zweite Tabelle: bekannte Abweichungen mit Backlog-Nummer.
    Unterschieden werden sie an der Ueberschrift, nicht an der Reihenfolge der
    Zeilen — eine umsortierte Datei soll nichts still umdeuten.
    """
    pfad = os.path.join(HIER, 'ausnahmen.md')
    aus, bekannt = {}, {}
    if not os.path.exists(pfad):
        return aus, bekannt
    ziel = None
    for zeile in lies(pfad).splitlines():
        if zeile.startswith('## '):
            ziel = bekannt if 'Bekannte Abweichungen' in zeile else aus
            continue
        if not zeile.startswith('|') or ziel is None:
            continue
        spalten = [t.strip() for t in zeile.strip('|').split('|')]
        if len(spalten) < 2 or spalten[0].startswith('---') or spalten[0] == 'Verweis':
            continue
        ziel[spalten[0].strip('`')] = ' · '.join(spalten[1:])
    return aus, bekannt


def kurz(pfad):
    return os.path.relpath(pfad, WURZEL).replace(os.sep, '/')


def pruefen(ausfuehrlich=False):
    seiten = zielseiten()
    aus, bekannt = listen()

    befunde, dyn, gezaehlt = [], [], 0
    genutzte_ausnahmen, genutzte_bekannte = set(), set()

    for pfad in quelldateien(('.php', '.js')):
        for nr, zeile in enumerate(lies(pfad).splitlines(), 1):
            gestutzt = zeile.strip()
            # Kommentarzeilen tragen Beispieladressen — sie sind Erklaerung,
            # kein Verweis. (Ein Kommentar AM ENDE einer Codezeile wird
            # mitgelesen; das ist die sichere Richtung.)
            if gestutzt.startswith(('*', '//', '#')):
                continue
            for treffer in VERWEIS.finditer(zeile):
                ziel = treffer.group(1)
                rel  = ziel.lstrip('./')
                for name in PARAMETER.findall(treffer.group(2)):
                    gezaehlt += 1
                    schluessel = rel + '?' + name + '='
                    if schluessel in aus:
                        genutzte_ausnahmen.add(schluessel)
                        continue
                    if schluessel in bekannt:
                        genutzte_bekannte.add(schluessel)
                        continue
                    if rel not in seiten:
                        befunde.append((kurz(pfad), nr, ziel, name, 'ZIEL WEG',
                                        'die Datei gibt es nicht'))
                    elif name in seiten[rel]['liest']:
                        continue
                    elif seiten[rel]['dynamisch']:
                        dyn.append((kurz(pfad), nr, ziel, name))
                    else:
                        gelesen = ', '.join(sorted(seiten[rel]['liest'])) or '—'
                        befunde.append((kurz(pfad), nr, ziel, name, 'FEHLT',
                                        'die Seite liest: ' + gelesen))

    # Tote Zeilen in beiden Tabellen. Bei einer bekannten Abweichung ist das
    # der Regelfall NACH ihrer Behebung — und genau dann soll der Lauf rot
    # werden, damit die Zeile hier verschwindet statt liegenzubleiben.
    tote = sorted((set(aus) - genutzte_ausnahmen)
                  | (set(bekannt) - genutzte_bekannte))

    print('  %d Zielseiten unter server/ eingelesen' % len(seiten))
    print('  %d Verweise mit Parameter geprueft' % gezaehlt)
    print('  %d ueber eine Variable gelesen (nicht statisch entscheidbar)' % len(dyn))
    if dyn and ausfuehrlich:
        for d in dyn:
            print('      %s:%d -> %s?%s=' % (d[0], d[1], d[2], d[3]))
    print('  %d Ausnahmen, davon %d genutzt' % (len(aus), len(genutzte_ausnahmen)))
    print('  %d bekannte Abweichungen mit Backlog-Nummer, davon %d wiedergefunden'
          % (len(bekannt), len(genutzte_bekannte)))
    for s in sorted(genutzte_bekannte):
        print('      %s — %s' % (s, bekannt[s].split(' · ')[0]))

    if tote:
        print('\n  TOTE ZEILEN in ausnahmen.md (%d) — den Verweis gibt es nicht '
              'mehr, die Zeile gehoert heraus:' % len(tote))
        for t in tote:
            print('      %s' % t)

    if befunde:
        print('\n  BEFUNDE (%d):' % len(befunde))
        for f in befunde:
            print('      %s:%d  %s?%s=  [%s] %s' % (f[0], f[1], f[2], f[3], f[4], f[5]))
    else:
        print('\n  Keine unbekannte Abweichung: jeder Verweis nennt einen '
              'Parameter, den seine Zielseite liest — oder steht mit Nummer '
              'in ausnahmen.md.')

    print('\n  -> %d Verweise, %d Abweichungen, %d bekannt mit Nummer, '
          '%d tote Zeilen' % (gezaehlt, len(befunde), len(genutzte_bekannte), len(tote)))
    return 0 if not befunde and not tote else 1


def main():
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument('--ausfuehrlich', action='store_true',
                   help='auch die dynamisch gelesenen Verweise einzeln nennen')
    a = p.parse_args()
    return pruefen(a.ausfuehrlich)


if __name__ == '__main__':
    sys.exit(main())
