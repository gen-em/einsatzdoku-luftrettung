#!/usr/bin/env python3
"""Farb-Token der App gegen die des Web abgleichen (E-S4-22a).

WOZU. E-S4-22a sagt: dieselben HEX-Werte wie ``:root`` im Web, kein eigener
Farbwert.  Das ist eine Zusage, die von Hand nicht haelt -- siebzehn Werte an
zwei Stellen laufen frueher oder spaeter auseinander, und niemand sieht es an,
weil eine App und eine Webseite nie nebeneinander liegen.

Dieses Skript liest beide Seiten und rechnet nach:

  Quelle Web   server/assets/style.css, Block ``:root`` (die EINE Stelle,
               an der ein Wert im Web steht -- CLAUDE.md 5)
  Quelle App   android/gemeinsam/res/values/farben.xml

Gemeldet wird dreierlei, und jede Zahl gehoert ins Pruefprotokoll:

  ABWEICHUNG   Ein Token gibt es auf beiden Seiten, die Werte sind verschieden.
               Das ist immer ein Fehler.
  EIGENER      Die App fuehrt einen Wert, den ``:root`` nicht kennt. Nach
               E-S4-22a soll das die leere Menge sein.
  UNGENUTZT    Das Web fuehrt ein Token, das die App nicht uebernommen hat.
               Das ist KEIN Fehler -- die App braucht die Filterleisten-
               breiten nicht --, wird aber genannt, damit die Auswahl
               absichtlich bleibt und nicht vergesslich wird.

Aufruf:  werkzeuge/farbabgleich.py
Rueckgabe: 0, wenn Abweichungen und eigene Werte je 0 sind; sonst 1.
"""
import re
import sys
from pathlib import Path

WURZEL = Path(__file__).resolve().parents[2]
STYLESHEET = WURZEL / "server" / "assets" / "style.css"
FARBEN = WURZEL / "android" / "gemeinsam" / "res" / "values" / "farben.xml"

# App-Token -> Web-Token.  Die Zuordnung steht hier und nicht in einem Namens-
# muster: "marke_auf_dunkel" hiesse sonst "--auf_dunkel", und das Web schreibt
# "--auf-dunkel".  Ein Muster, das an einem Bindestrich scheitert, ist keine
# Pruefung, sondern eine zweite Fehlerquelle.
ZUORDNUNG = {
    "marke_schnee":       "--schnee",
    "marke_rauch":        "--rauch",
    "marke_sand":         "--sand",
    "marke_asphalt":      "--asphalt",
    "marke_dunkelblau":   "--dunkelblau",
    "marke_gedaempft":    "--gedaempft",
    "marke_auf_dunkel":   "--auf-dunkel",
    "marke_linie":        "--linie",
    "marke_orange":       "--orange",
    # Seit Schritt 26 (Konzept MA) gibt es im Web keine eigenen Tief- und
    # Hellwerte mehr; die App zeigt auf die Tonstufen des Branding-Repos,
    # die dort an derselben Stelle stehen.
    "marke_orange_tief":  "--genem-orange-700",
    "marke_orange_hell":  "--genem-orange-100",
    "marke_blau":         "--blau",
    "marke_blau_tief":    "--genem-blau-700",
    "marke_blau_hell":    "--genem-blau-100",
    "marke_rot":          "--rot",
    "marke_rot_tief":     "--genem-rot-700",
    "marke_rosa":         "--genem-rot-100",
}


def web_token() -> dict[str, str]:
    """Alle Farbwerte aus den :root-Bloecken des Stylesheets, hell.

    SEIT SCHRITT 26 ZWEI BLOECKE: der des Branding-Repos (`:root {`, die
    Hexwerte) und der der Anwendung (`:root{`, Rollen und Leitwerte als
    `var(--genem-…)`). Gelesen werden beide, Kommentare vorher heraus — bis
    dahin suchte dieses Skript das erste `:root{` im Rohtext und fand es in
    einem Kommentar. Verweise werden aufgeloest, damit `--gedaempft` seinen
    Wert bekommt. Der Dark Mode (`:root[data-theme="dark"]`) zaehlt nicht:
    Die App hat keinen."""
    text = re.sub(r"/\*.*?\*/", "", STYLESHEET.read_text(encoding="utf-8"), flags=re.S)
    roh: dict[str, str] = {}
    for m in re.finditer(r":root\s*\{", text):
        tiefe, i = 1, m.end()
        while tiefe and i < len(text):
            tiefe += {"{": 1, "}": -1}.get(text[i], 0)
            i += 1
        for name, wert in re.findall(r"(--[a-z0-9-]+)\s*:\s*([^;]+);", text[m.end():i - 1]):
            roh[name] = wert.strip()
    werte: dict[str, str] = {}
    for name, wert in roh.items():
        for _ in range(6):
            v = re.fullmatch(r"var\((--[a-z0-9-]+)\)", wert)
            if not v:
                break
            wert = roh.get(v.group(1), wert)
        if re.fullmatch(r"#[0-9A-Fa-f]{6}", wert):
            werte[name] = wert.upper()
    return werte


def app_token() -> dict[str, str]:
    text = FARBEN.read_text(encoding="utf-8")
    text = re.sub(r"<!--.*?-->", "", text, flags=re.S)
    return {
        name: wert.upper()
        for name, wert in re.findall(
            r'<color\s+name="([^"]+)"\s*>\s*(#[0-9A-Fa-f]{3,8})\s*</color>', text
        )
    }


def main() -> int:
    web = web_token()
    app = app_token()

    abweichungen: list[str] = []
    eigene: list[str] = []
    for name, wert in sorted(app.items()):
        ziel = ZUORDNUNG.get(name)
        if ziel is None or ziel not in web:
            eigene.append(f"{name} = {wert}")
        elif web[ziel] != wert:
            abweichungen.append(f"{name} = {wert}, Web {ziel} = {web[ziel]}")

    genutzt = {ZUORDNUNG[n] for n in app if n in ZUORDNUNG}
    ungenutzt = sorted(t for t in web if t not in genutzt)

    print(f"Web-Token (Farben in :root): {len(web)}")
    print(f"App-Token (farben.xml):      {len(app)}")
    print(f"Abweichungen:                {len(abweichungen)}")
    for z in abweichungen:
        print(f"  ! {z}")
    print(f"Eigene Farbwerte der App:    {len(eigene)}")
    for z in eigene:
        print(f"  ! {z}")
    print(f"Vom Web nicht uebernommen:   {len(ungenutzt)}")
    print("  " + ", ".join(ungenutzt) if ungenutzt else "  -")

    return 1 if (abweichungen or eigene) else 0


if __name__ == "__main__":
    sys.exit(main())
