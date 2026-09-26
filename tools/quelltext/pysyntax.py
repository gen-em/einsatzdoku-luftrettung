#!/usr/bin/env python3
r"""Lassen sich die Python-Werkzeuge unter tools/ überhaupt übersetzen?

    python3 tools/quelltext/pysyntax.py
    python3 tools/quelltext/pysyntax.py --selbstprobe

Rückgabewert 0 = jede Datei übersetzt, ohne Warnung · 1 = Syntaxfehler oder
Warnung · 2 = gemessen wurde nichts (keine Datei gefunden).

WOFÜR. Viele Python-Werkzeuge haben keine Selbstprobe und werden selten von
Hand gefahren. Ein Syntaxfehler darin fällt erst auf, wenn der Schritt
läuft, der sie ruft — in Kette II/AP4 war das der Beweislauf gegen Produktiv,
der nach 31 Sekunden an einem „ in `zustand.py` starb. Übersetzen kostet
eine Sekunde und fängt alle.

BIS BR-03 EIN SCHRITT IN STUFE 1, den Station B nie fuhr (F-BR-03). Seither
die zehnte Quelltextprüfung, im Läufer und im Tor mit derselben Datei.

WARNUNGEN SIND FEHLER (seit R4-07, Nr. 318). Eine ungültige Escape-Folge
(`"\d"` statt `r"\d"`) übersetzt heute mit einer Warnung — unter Python 3.11
als unterdrückte `DeprecationWarning`, ab 3.12 als sichtbare `SyntaxWarning`
im Tor —, und eine künftige Fassung macht einen `SyntaxError` daraus: Dann
bricht das Werkzeug ab, statt zu prüfen. Übersetzt wird deshalb zweimal:
einmal wie Python selbst (ein Fehler dort ist ein Syntaxfehler), einmal mit
Warnungen als Fehler (was erst dort scheitert, ist eine Warnung). Beides ist
rot, beide Zahlen stehen getrennt da. Bis R4-07 zählte die Prüfung „57
Python-Werkzeuge geprüft, 0 mit Syntaxfehler", während das Tor zwei
Warnungen ausgab.

GEZÄHLT WIRD, WAS `git add -A` IN DEN BAUM LEGTE (versioniert und neu, nicht
ignoriert) — wie beim Baum-Hash des Prüfberichts. Ohne Git alles unter tools/.
"""
import os
import subprocess
import sys
import tempfile
import warnings

HIER = os.path.dirname(os.path.abspath(__file__))
WURZEL = os.path.dirname(os.path.dirname(HIER))


def dateien(wurzel):
    # `-z`: sonst stünde ein Pfad mit Umlaut in Anführungszeichen mit
    # Oktalfolgen da und wäre unsichtbar (Gegenprobe des Bestandsriegels, BR-05).
    r = subprocess.run(['git', '-C', wurzel, 'ls-files', '-z', '-co', '--exclude-standard', '--', 'tools'],
                       capture_output=True, text=True)
    if r.returncode == 0:
        return sorted(p for p in r.stdout.split('\0')
                      if p.endswith('.py') and os.path.isfile(os.path.join(wurzel, p)))
    aus = []
    for ordner, _, namen in os.walk(os.path.join(wurzel, 'tools')):
        aus += [os.path.relpath(os.path.join(ordner, n), wurzel) for n in namen if n.endswith('.py')]
    return sorted(aus)


def messen(wurzel):
    """(Zahl der Dateien, Fehler, Warnungen) — je Befund (Datei, Zeile, Meldung)."""
    liste = dateien(wurzel)
    fehler, warnung = [], []
    for rel in liste:
        with open(os.path.join(wurzel, rel), encoding='utf-8', errors='replace') as f:
            text = f.read()
        with warnings.catch_warnings():
            # Erst wie Python selbst: Warnungen UNTERDRÜCKT, damit sie hier
            # nicht als Rauschen auf der Konsole stehen — gezählt werden sie
            # im zweiten Durchgang.
            warnings.simplefilter('ignore')
            try:
                compile(text, rel, 'exec')
            except SyntaxError as e:
                fehler.append((rel, e.lineno or 0, e.msg))
                continue
        with warnings.catch_warnings():
            # Dann mit Warnungen als Fehler. Der Übersetzer wandelt eine zum
            # Fehler erhobene Warnung selbst in einen `SyntaxError` — die
            # beiden Warnungsklassen stehen trotzdem da, falls eine Fassung
            # das einmal anders hält.
            warnings.simplefilter('error')
            try:
                compile(text, rel, 'exec')
            except (SyntaxError, SyntaxWarning, DeprecationWarning) as e:
                warnung.append((rel, getattr(e, 'lineno', None) or 0,
                                getattr(e, 'msg', None) or str(e)))
    return len(liste), fehler, warnung


def lauf(wurzel):
    n, fehler, warnung = messen(wurzel)
    for art, befunde in (('Syntaxfehler', fehler), ('Warnung', warnung)):
        for rel, zeile, msg in befunde:
            if os.environ.get('GITHUB_ACTIONS'):
                print(f'::error file={rel},line={zeile}::{art}: {msg}')
            print(f'  ! {rel}:{zeile}: {art}: {msg}')
    print(f'{n} Python-Werkzeuge geprüft, {len(fehler)} mit Syntaxfehler, '
          f'{len(warnung)} mit Warnung')
    if n == 0:
        print('Keine Python-Datei unter tools/ gefunden — gemessen wurde nichts.')
        return 2
    return 1 if fehler or warnung else 0


def selbstprobe():
    """Eine gute Datei, der Fehler aus Kette II/AP4, eine Warnung, ein leerer Baum."""
    faelle = [
        ('GEGENPROBE: eine Datei, die übersetzt', {'tools/a/gut.py': 'x = 1\n'}, 0),
        ('ein deutsches Schlusszeichen beendet die Zeichenkette',
         {'tools/a/gut.py': 'x = 1\n', 'tools/b/kaputt.py': 'f("Abfrage: „da" oder nicht")\n'}, 1),
        ('eine ungültige Escape-Folge übersetzt nur mit Warnung (Nr. 318)',
         {'tools/a/gut.py': 'x = 1\n', 'tools/c/warnt.py': 'import re\nMUSTER = re.compile("\\d+")\n'}, 1),
        ('kein Python unter tools/ — gemessen wurde nichts', {'tools/a/LIESMICH.md': '# x\n'}, 2),
    ]
    fehl = 0
    for name, baum, soll in faelle:
        with tempfile.TemporaryDirectory(prefix='pysyntax-') as d:
            for rel, inhalt in baum.items():
                os.makedirs(os.path.dirname(os.path.join(d, rel)), exist_ok=True)
                with open(os.path.join(d, rel), 'w', encoding='utf-8') as f:
                    f.write(inhalt)
            subprocess.run(['git', '-C', d, 'init', '-q'], capture_output=True)
            n, fehler, warnung = messen(d)
            ist = 2 if n == 0 else (1 if fehler or warnung else 0)
        ok = ist == soll
        fehl += not ok
        print(f"  [{'ok  ' if ok else 'FEHL'}] {name}  (erwartet {soll}, gemessen {ist})")
    print(f'{len(faelle)} Fälle, {fehl} Fehlschläge')
    return 1 if fehl else 0


if __name__ == '__main__':
    sys.exit(selbstprobe() if '--selbstprobe' in sys.argv[1:] else lauf(WURZEL))
