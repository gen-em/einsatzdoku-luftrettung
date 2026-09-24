#!/usr/bin/env python3
"""Lassen sich die Python-Werkzeuge unter tools/ überhaupt übersetzen?

    python3 tools/quelltext/pysyntax.py
    python3 tools/quelltext/pysyntax.py --selbstprobe

Rückgabewert 0 = jede Datei übersetzt · 1 = Syntaxfehler · 2 = gemessen
wurde nichts (keine Datei gefunden).

WOFÜR. Viele Python-Werkzeuge haben keine Selbstprobe und werden selten von
Hand gefahren. Ein Syntaxfehler darin fällt erst auf, wenn der Schritt
läuft, der sie ruft — in Kette II/AP4 war das der Beweislauf gegen Produktiv,
der nach 31 Sekunden an einem „ in `zustand.py` starb. Übersetzen kostet
eine Sekunde und fängt alle.

BIS BR-03 EIN SCHRITT IN STUFE 1, den Station B nie fuhr (F-BR-03). Seither
die zehnte Quelltextprüfung, im Läufer und im Tor mit derselben Datei.

GEZÄHLT WIRD, WAS `git add -A` IN DEN BAUM LEGTE (versioniert und neu, nicht
ignoriert) — wie beim Baum-Hash des Prüfberichts. Ohne Git alles unter tools/.
"""
import os
import subprocess
import sys
import tempfile

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
    """(Zahl der Dateien, [(Datei, Zeile, Meldung)])."""
    liste = dateien(wurzel)
    fehler = []
    for rel in liste:
        with open(os.path.join(wurzel, rel), encoding='utf-8', errors='replace') as f:
            text = f.read()
        try:
            compile(text, rel, 'exec')
        except SyntaxError as e:
            fehler.append((rel, e.lineno or 0, e.msg))
    return len(liste), fehler


def lauf(wurzel):
    n, fehler = messen(wurzel)
    for rel, zeile, msg in fehler:
        if os.environ.get('GITHUB_ACTIONS'):
            print(f'::error file={rel},line={zeile}::{msg}')
        print(f'  ! {rel}:{zeile}: {msg}')
    print(f'{n} Python-Werkzeuge geprüft, {len(fehler)} mit Syntaxfehler')
    if n == 0:
        print('Keine Python-Datei unter tools/ gefunden — gemessen wurde nichts.')
        return 2
    return 1 if fehler else 0


def selbstprobe():
    """Eine gute Datei, eine mit dem Fehler aus Kette II/AP4, ein leerer Baum."""
    faelle = [
        ('GEGENPROBE: eine Datei, die übersetzt', {'tools/a/gut.py': 'x = 1\n'}, 0),
        ('ein deutsches Schlusszeichen beendet die Zeichenkette',
         {'tools/a/gut.py': 'x = 1\n', 'tools/b/kaputt.py': 'f("Abfrage: „da" oder nicht")\n'}, 1),
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
            n, fehler = messen(d)
            ist = 2 if n == 0 else (1 if fehler else 0)
        ok = ist == soll
        fehl += not ok
        print(f"  [{'ok  ' if ok else 'FEHL'}] {name}  (erwartet {soll}, gemessen {ist})")
    print(f'{len(faelle)} Fälle, {fehl} Fehlschläge')
    return 1 if fehl else 0


if __name__ == '__main__':
    sys.exit(selbstprobe() if '--selbstprobe' in sys.argv[1:] else lauf(WURZEL))
