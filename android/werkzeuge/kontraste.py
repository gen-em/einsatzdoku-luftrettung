#!/usr/bin/env python3
"""Kontraste der App-Farbpaare nachrechnen (CLAUDE.md 5, Zielwert AA).

WOZU EIN EIGENES SKRIPT, wo tools/screenshots/kontrast.py doch existiert: Das
dortige Werkzeug rechnet die Paare der WEBOBERFLAECHE nach -- Schnee, Rauch,
Karten. Die App stellt andere Paare zusammen: weisse Schrift auf vollflaechig
Rot (die beendenden Handlungen, E-S4-22a) und alles, was die Uhr auf Asphalt
zeichnet. Diese Paare kommen im Web nicht vor und stuenden dort ungeprueft.

Die Werte kommen aus gemeinsam/res/values/farben.xml -- also aus derselben
Datei, die die App benutzt, nicht aus einer Abschrift.

ZIELWERTE (WCAG 2.1):
  4,5:1  normale Schrift (AA)
  3,0:1  grosse Schrift ab 18,66 px fett / 24 px, und GRAFISCHE Objekte
         (1.4.11) -- darunter der rote Aufnahmepunkt

Aufruf:  werkzeuge/kontraste.py
Rueckgabe: 0, wenn jedes Paar seinen eigenen Zielwert erreicht; sonst 1.
"""
import re
import sys
from pathlib import Path

FARBEN = Path(__file__).resolve().parents[1] / "gemeinsam" / "res" / "values" / "farben.xml"

# (Beschreibung, Vordergrund-Token, Hintergrund-Token, Zielwert)
PAARE = [
    # -- Handy, heller Grund --
    ("Titel auf Karte",              "marke_dunkelblau", "marke_schnee",     4.5),
    ("Nebentext auf Karte",          "marke_gedaempft",  "marke_schnee",     4.5),
    ("Primaerknopf: Schrift",        "marke_dunkelblau", "marke_orange",     4.5),
    ("Beenden-Knopf: Schrift",       "marke_auf_dunkel", "marke_rot",        4.5),
    ("Hinweiskasten: Schrift",       "marke_asphalt",    "marke_blau_hell",  4.5),
    ("Auswahl aktiv: Schrift",       "marke_blau_tief",  "marke_blau_hell",  4.5),
    ("Kopfleiste: Schrift",          "marke_auf_dunkel", "marke_dunkelblau", 4.5),
    # -- Zustandszeile der Ortung (E1, E-S5Z-22) --
    #
    # DIE DREI STUFEN, DIE DIE ZEILE KENNT. Sie stehen hier, WEIL dieses
    # Werkzeug eine feste Paarliste fuehrt: Ein Paar, das nicht eingetragen
    # ist, wird nicht gemessen -- und meldet folglich auch keinen Fehler
    # (B6.2). Genau so blieb `marke_orange` als Punkt jahrelang unbemerkt
    # unter dem Zielwert (B-S5Z-13, unten).
    #
    # Das Konzept sah fuer UNGENAU Orange als SCHRIFT vor. Nachgerechnet:
    # marke_orange 2,23:1, marke_orange_tief 4,32:1 -- beide unter AA. Rot
    # traegt hier, und alle vier Zustaende ohne Aufzeichnung sind deshalb rot
    # (E-S5Z-22); sie unterscheiden sich am Wortlaut.
    ("Ortung ok: Schrift",           "marke_asphalt",    "marke_schnee",     4.5),
    ("Ortung sucht: Schrift",        "marke_gedaempft",  "marke_schnee",     4.5),
    ("Ortung fehlt: Schrift",        "marke_rot_tief",   "marke_schnee",     4.5),
    # -- Uhr: derselbe Zustand am Handgelenk (E-S5Z-15) --
    ("Uhr: keine Ortung",            "marke_rosa",       "marke_asphalt",    4.5),
    # B-S5Z-15: Dieselbe Zeile in ihrer aelteren Fassung. `marke_rot` als
    # SCHRIFT auf Asphalt traegt 4,12:1 -- unter AA. Als FLAECHE mit weisser
    # Schrift traegt dasselbe Rot 4,78:1 und ist richtig; der Unterschied
    # stand nie in einer Zahl, weil das Paar hier fehlte.
    ("Uhr: Dienst schwebt",          "marke_rosa",       "marke_asphalt",    4.5),
    ("Uhr: GPS sucht",               "marke_sand",       "marke_asphalt",    4.5),
    # -- Uhr, Asphalt als Grund --
    ("Uhr: Hauptschrift",            "marke_auf_dunkel", "marke_asphalt",    4.5),
    ("Uhr: Nebenschrift",            "marke_sand",       "marke_asphalt",    4.5),
    ("Uhr: gesetzte Phasenzeit",     "marke_blau",       "marke_asphalt",    4.5),
    ("Uhr: naechste Phase",          "marke_orange",     "marke_asphalt",    4.5),
    ("Uhr: Durchlaufknopf Schrift",  "marke_dunkelblau", "marke_orange",     4.5),
    ("Uhr: Abschluss-Rueckfrage",    "marke_auf_dunkel", "marke_rot",        4.5),
    # -- grafische Objekte (1.4.11): 3:1 --
    ("Aufnahmepunkt auf Karte",      "marke_rot",        "marke_schnee",     3.0),
    # B-S5Z-13: Der Punkt der Zeile "Rueckstand N Pakete" trug bis E1
    # `marke_orange` -- 2,23:1 gegen Schnee und damit unter den 3,0, die
    # WCAG 1.4.11 fuer ein grafisches Objekt verlangt. Er trug sie deshalb so
    # lange, weil dieses Paar in dieser Liste fehlte.
    ("Rueckstandspunkt auf Karte",   "marke_orange_tief", "marke_schnee",    3.0),
    ("Warnpunkt auf Karte",          "marke_rot",        "marke_schnee",     3.0),
    ("Aufnahmepunkt auf Uhr",        "marke_rot",        "marke_asphalt",    3.0),
    ("Zustandspunkt blau auf Uhr",   "marke_blau",       "marke_asphalt",    3.0),
]


def token() -> dict[str, str]:
    text = re.sub(r"<!--.*?-->", "", FARBEN.read_text(encoding="utf-8"), flags=re.S)
    return dict(re.findall(r'<color\s+name="([^"]+)"\s*>\s*(#[0-9A-Fa-f]{6})\s*</color>', text))


def leuchtdichte(hexwert: str) -> float:
    h = hexwert.lstrip("#")
    werte = []
    for i in (0, 2, 4):
        c = int(h[i:i + 2], 16) / 255
        werte.append(c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4)
    return 0.2126 * werte[0] + 0.7152 * werte[1] + 0.0722 * werte[2]


def kontrast(a: str, b: str) -> float:
    la, lb = leuchtdichte(a), leuchtdichte(b)
    return (max(la, lb) + 0.05) / (min(la, lb) + 0.05)


def main() -> int:
    t = token()
    durchgefallen = 0
    print(f"{'Paar':<30} {'Vorder':<8} {'Grund':<8} {'Ist':>7}  {'Soll':>5}")
    for name, vorne, hinten, soll in PAARE:
        if vorne not in t or hinten not in t:
            print(f"{name:<30} TOKEN FEHLT ({vorne} / {hinten})")
            durchgefallen += 1
            continue
        wert = kontrast(t[vorne], t[hinten])
        marke = " " if wert >= soll else "!"
        if wert < soll:
            durchgefallen += 1
        print(f"{name:<30} {t[vorne]:<8} {t[hinten]:<8} {wert:6.2f}:1 {soll:5.1f} {marke}")
    print(f"\nPaare geprueft: {len(PAARE)}   unter dem Zielwert: {durchgefallen}")
    return 1 if durchgefallen else 0


if __name__ == "__main__":
    sys.exit(main())
