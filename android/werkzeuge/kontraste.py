#!/usr/bin/env python3
"""Kontraste der App-Farbpaare nachrechnen -- und nachzaehlen, ob die Liste
vollstaendig ist (CLAUDE.md 5, Zielwert AA).

Anlass: Nr. 116 (das Werkzeug mass nur, was in seiner Paarliste stand).

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
         (1.4.11) -- Punkte, Cursor, Rahmen und Linien von Bedienelementen

ZWEI PRUEFUNGEN (seit Android 0.16.0, Konzept AR, E-AR-09):

  1. Jedes Paar der Liste erreicht seinen Zielwert.
  2. VOLLSTAENDIGKEIT JE MODUL UND ROLLE. Jede Farbe, die im Quelltext eines
     Moduls als Schrift, Zeichen, Linie oder Flaeche vorkommt, steht in der
     Paarliste DIESES Moduls in DIESER Rolle -- sonst ist das ein Befund.
     Eine Farbe, deren Rolle das Werkzeug nicht erkennt, ist ebenfalls ein
     Befund, kein Schweigen.

  Bis 0.16.0 gab es nur die erste. Ein Paar, das nicht in der Liste stand,
  wurde nie gemessen und meldete folglich keinen Fehler: So standen der orange
  Punkt auf der Karte (B-S5Z-13) und Rot als Schrift auf der Uhr (B-S5Z-15)
  unter dem Zielwert, waehrend jeder Lauf gruen war. Die Selbstprobe baut
  genau diese zwei Fehler ein.

WIE DIE ROLLE BESTIMMT WIRD. Je Vorkommen von `Farbe.x` der Aufruf, in dem es
steht, und der Parameter: `Text(color = …)`, `TextStyle(color = …)`,
`schrift = …`, `schriftfarbe = …` sind Schrift; `punktfarbe = …`, ein Kreis
(`.background(…, CircleShape)`), der Cursor (`SolidColor`) und `Icon(tint = …)`
sind Zeichen; `.border(…)`, `randfarbe = …` und eine 1-dp-Box sind Linie;
`.background(…)`, `containerColor = …` und `flaeche = …` sind Flaeche.
Positionelle Argumente an eigene Bausteine werden ueber deren Parameternamen
gelesen. Steht die Farbe in einem `when`, einem `if` oder hinter `?:`, zaehlt
die Stelle, an die dessen Ergebnis geht -- auch ueber eine Hilfsvariable oder
eine Hilfsfunktion derselben Datei.

WAS ES NICHT SIEHT. Eine bekannte Schrift auf einem NEUEN Grund: Steht
`gedaempft` als Schrift in der Liste und kommt spaeter auf `blau_hell` zu
liegen, meldet das Werkzeug nichts. Die Rolle kennt es, den Grund nicht --
der steht fast nie in derselben Funktion. Und Farben, die nicht ueber `Farbe.`
kommen (`colorResource`, `ContextCompat.getColor`), zaehlt es nicht.

Aufruf:  werkzeuge/kontraste.py [--selbstprobe]
Rueckgabe: 0 gruen, 1 Befund, 2 nicht gelaufen (Datei fehlt).
"""
import re
import sys
import tempfile
from pathlib import Path

WURZEL = Path(__file__).resolve().parents[1]
FARBEN = WURZEL / "gemeinsam" / "res" / "values" / "farben.xml"
QUELLEN = {
    "handy": ["handy/src/main", "gemeinsam/quelle"],
    "uhr": ["uhr/src/main", "gemeinsam/quelle"],
}

# (Modul, Beschreibung, Vordergrund-Token, Hintergrund-Token, Zielwert)
PAARE = [
    # -- Handy, heller Grund --
    ("handy", "Titel auf Karte",              "marke_dunkelblau", "marke_schnee",     4.5),
    ("handy", "Nebentext auf Karte",          "marke_gedaempft",  "marke_schnee",     4.5),
    ("handy", "Primaerknopf: Schrift",        "marke_dunkelblau", "marke_orange",     4.5),
    ("handy", "Beenden-Knopf: Schrift",       "marke_auf_dunkel", "marke_rot",        4.5),
    ("handy", "Hinweiskasten: Schrift",       "marke_asphalt",    "marke_blau_hell",  4.5),
    ("handy", "Auswahl aktiv: Schrift",       "marke_blau_tief",  "marke_blau_hell",  4.5),
    ("handy", "Kopfleiste: Schrift",          "marke_auf_dunkel", "marke_dunkelblau", 4.5),
    # Seit 0.16.0 (AR-04), gefunden von der Vollstaendigkeitspruefung: Der
    # Seitengrund Rauch hatte kein einziges Paar. Unmittelbar darauf stehen
    # die Fassungszeile und der Rahmen des Zurueck-Knopfs.
    ("handy", "Fassungszeile auf Seitengrund", "marke_gedaempft", "marke_rauch",      4.5),
    # -- Zustandszeile der Ortung (E1, E-S5Z-22) --
    #
    # Das Konzept sah fuer UNGENAU Orange als SCHRIFT vor. Nachgerechnet:
    # marke_orange 2,23:1, marke_orange_tief 4,32:1 -- beide unter AA. Rot
    # traegt hier, und alle vier Zustaende ohne Aufzeichnung sind deshalb rot
    # (E-S5Z-22); sie unterscheiden sich am Wortlaut.
    ("handy", "Ortung ok: Schrift",           "marke_asphalt",    "marke_schnee",     4.5),
    ("handy", "Ortung sucht: Schrift",        "marke_gedaempft",  "marke_schnee",     4.5),
    ("handy", "Ortung fehlt: Schrift",        "marke_rot_tief",   "marke_schnee",     4.5),
    # -- grafische Objekte (1.4.11): 3:1 --
    ("handy", "Aufnahmepunkt auf Karte",      "marke_rot",        "marke_schnee",     3.0),
    # B-S5Z-13: Der Punkt der Zeile "Rueckstand N Pakete" trug bis E1
    # `marke_orange` -- 2,23:1 gegen Schnee und damit unter den 3,0, die
    # WCAG 1.4.11 fuer ein grafisches Objekt verlangt. Er trug sie deshalb so
    # lange, weil dieses Paar in dieser Liste fehlte.
    ("handy", "Rueckstandspunkt auf Karte",   "marke_orange_tief", "marke_schnee",    3.0),
    ("handy", "Warnpunkt auf Karte",          "marke_rot",        "marke_schnee",     3.0),
    # Seit 0.16.0 (AR-04): der blaue Zustandspunkt (Sync, Kopplung) -- er
    # bestand immer, stand aber nicht in der Liste.
    ("handy", "Zustandspunkt blau auf Karte", "marke_blau",       "marke_schnee",     3.0),
    # Der Cursor im Eingabefeld war bis 0.16.0 `marke_orange`, 2,23:1.
    ("handy", "Cursor im Eingabefeld",        "marke_orange_tief", "marke_schnee",    3.0),
    # Die Rahmen echter Bedienelemente: Eingabefeld, Umschalter, Nebenknopf --
    # auf der Karte und auf dem Seitengrund (Zurueck-Knopf).
    ("handy", "Rahmen Bedienelement, Karte",  "marke_gedaempft",  "marke_schnee",     3.0),
    ("handy", "Rahmen Bedienelement, Seite",  "marke_gedaempft",  "marke_rauch",      3.0),
    # -- Uhr: derselbe Zustand am Handgelenk (E-S5Z-15) --
    ("uhr",   "Uhr: keine Ortung",            "marke_rosa",       "marke_asphalt",    4.5),
    # B-S5Z-15: Dieselbe Zeile in ihrer aelteren Fassung. `marke_rot` als
    # SCHRIFT auf Asphalt traegt 4,12:1 -- unter AA. Als FLAECHE mit weisser
    # Schrift traegt dasselbe Rot 4,78:1 und ist richtig; der Unterschied
    # stand nie in einer Zahl, weil das Paar hier fehlte.
    ("uhr",   "Uhr: Dienst schwebt",          "marke_rosa",       "marke_asphalt",    4.5),
    ("uhr",   "Uhr: GPS sucht",               "marke_sand",       "marke_asphalt",    4.5),
    # Seit 0.16.0 (AR-04): "Handy nicht erreichbar" war bis dahin `marke_rot`
    # als Schrift -- 4,12:1, dieselbe Luecke wie B-S5Z-15 an einer zweiten
    # Stelle. Gefunden von der Vollstaendigkeitspruefung.
    ("uhr",   "Uhr: Handy nicht erreichbar",  "marke_rosa",       "marke_asphalt",    4.5),
    # -- Uhr, Asphalt als Grund --
    ("uhr",   "Uhr: Hauptschrift",            "marke_auf_dunkel", "marke_asphalt",    4.5),
    ("uhr",   "Uhr: Nebenschrift",            "marke_sand",       "marke_asphalt",    4.5),
    ("uhr",   "Uhr: gesetzte Phasenzeit",     "marke_blau",       "marke_asphalt",    4.5),
    ("uhr",   "Uhr: naechste Phase",          "marke_orange",     "marke_asphalt",    4.5),
    ("uhr",   "Uhr: Durchlaufknopf Schrift",  "marke_dunkelblau", "marke_orange",     4.5),
    ("uhr",   "Uhr: Abschluss-Rueckfrage",    "marke_auf_dunkel", "marke_rot",        4.5),
    ("uhr",   "Aufnahmepunkt auf Uhr",        "marke_rot",        "marke_asphalt",    3.0),
    ("uhr",   "Zustandspunkt blau auf Uhr",   "marke_blau",       "marke_asphalt",    3.0),
]

# ZIERDE -- eine Farbe in einer Rolle, die KEINEN Kontrast tragen muss, mit
# Begruendung. Keine Ausnahme fuer einen zu schwachen Wert: Was hier steht,
# wird gar nicht gerechnet, und deshalb steht der Grund dabei.
ZIERDE = {
    ("handy", "marke_linie", "Linie"):
        "Rahmen um Karte, Phasenzeile und Auswahlzeile. Die Karte ist kein "
        "Bedienelement, sondern ein Behaelter; Phasen- und Auswahlzeile erkennt "
        "man an ihrer Beschriftung und die Wahl an Flaeche und Schrift (Blau hell, "
        "Blau tief). WCAG 1.4.11 verlangt 3:1 nur fuer das, was man zum Erkennen "
        "braucht. Die Rahmen echter Bedienelemente -- Eingabefeld, Umschalter, "
        "Nebenknopf -- sind `gedaempft` und stehen mit 5,66:1 in der Liste.",
}

ROLLE_NACH_NAME = {
    "schrift": "Schrift", "schriftfarbe": "Schrift", "contentColor": "Schrift",
    "textColor": "Schrift",
    "punktfarbe": "Zeichen", "tint": "Zeichen",
    "randfarbe": "Linie",
    "flaeche": "Flaeche", "containerColor": "Flaeche",
}
SCHRIFT_AUFRUFE = {"Text", "BasicText", "TextStyle"}
VORDER_ROLLEN = ("Schrift", "Zeichen", "Linie")
ALIAS = {"knopfPrimaerFlaeche": "orange", "knopfPrimaerSchrift": "dunkelblau",
         "knopfBeendenFlaeche": "rot", "knopfBeendenSchrift": "aufDunkel"}


def token_von(name):
    name = ALIAS.get(name, name)
    return "marke_" + re.sub(r"([A-Z])", lambda m: "_" + m.group(1).lower(), name)


def token():
    text = re.sub(r"<!--.*?-->", "", FARBEN.read_text(encoding="utf-8"), flags=re.S)
    return dict(re.findall(r'<color\s+name="([^"]+)"\s*>\s*(#[0-9A-Fa-f]{6})\s*</color>', text))


def leuchtdichte(hexwert):
    h = hexwert.lstrip("#")
    werte = []
    for i in (0, 2, 4):
        c = int(h[i:i + 2], 16) / 255
        werte.append(c / 12.92 if c <= 0.04045 else ((c + 0.055) / 1.055) ** 2.4)
    return 0.2126 * werte[0] + 0.7152 * werte[1] + 0.0722 * werte[2]


def kontrast(a, b):
    la, lb = leuchtdichte(a), leuchtdichte(b)
    return (max(la, lb) + 0.05) / (min(la, lb) + 0.05)


# ------------------------------------------------------------ Quelltext lesen

def ohne_kommentare(text):
    """Kommentare und Zeichenketten durch Leerzeichen ersetzen -- die
    Positionen bleiben, damit Zeilennummern stimmen."""
    def leer(m):
        return re.sub(r"[^\n]", " ", m.group(0))
    return re.sub(r'/\*.*?\*/|//[^\n]*|"(?:\\.|[^"\\\n])*"', leer, text, flags=re.S)


def offene_klammer(t, pos):
    """Rueckwaerts bis zur ersten nicht geschlossenen Klammer. Liefert
    (Zeichen, Stelle) -- `(` fuer einen Aufruf, `{` fuer einen Block."""
    tiefe = 0
    for i in range(pos - 1, -1, -1):
        c = t[i]
        if c in ")]}":
            tiefe += 1
        elif c in "([{":
            if tiefe == 0:
                return c, i
            tiefe -= 1
    return None, -1


def argument(t, auf, pos):
    """Parametername (benannt) oder Stelle (positionell) des Arguments, in dem
    `pos` steht, fuer den Aufruf mit der Klammer bei `auf`."""
    tiefe, kommas, anfang = 0, 0, auf + 1
    for i in range(auf + 1, pos):
        c = t[i]
        if c in "([{":
            tiefe += 1
        elif c in ")]}":
            tiefe -= 1
        elif c == "," and tiefe == 0:
            kommas += 1
            anfang = i + 1
    m = re.match(r"\s*(\w+)\s*=(?!=)", t[anfang:pos])
    return (m.group(1) if m else None), kommas


def ende_des_aufrufs(t, auf):
    tiefe = 0
    for i in range(auf, len(t)):
        if t[i] == "(":
            tiefe += 1
        elif t[i] == ")":
            tiefe -= 1
            if tiefe == 0:
                return i
    return len(t)


def signaturen(texte):
    """Parameternamen der eigenen Bausteine: `fun Name(a: T, b: Color, …)`."""
    sig = {}
    for t in texte:
        for m in re.finditer(r"\bfun\s+(\w+)\s*\(", t):
            auf = m.end() - 1
            koerper = t[auf + 1:ende_des_aufrufs(t, auf)]
            namen, tiefe, stueck = [], 0, ""
            for c in koerper + ",":
                if c in "([{<":
                    tiefe += 1
                elif c in ")]}>":
                    tiefe -= 1
                if c == "," and tiefe == 0:
                    n = re.match(r"\s*(?:@\w+\s+)*(\w+)\s*:", stueck)
                    if n:
                        namen.append(n.group(1))
                    stueck = ""
                else:
                    stueck += c
            sig[m.group(1)] = namen
    return sig


def rolle_an(t, pos, sig, tiefe=0):
    """Die Rolle des Ausdrucks, der bei `pos` beginnt oder in dem `pos` steht."""
    if tiefe > 4:
        return None
    zeichen, auf = offene_klammer(t, pos)
    # Im Block eines `when` oder hinter `if`/`?:`: die Stelle, an die das
    # Ergebnis geht, bestimmt die Rolle.
    if zeichen == "{":
        vor = t[:auf]
        w = re.search(r"\bwhen\s*(\([^()]*\))?\s*$", vor)
        if w:
            return rolle_an(t, w.start(), sig, tiefe + 1)
        return rolle_der_deklaration(t, pos, sig, tiefe)
    if zeichen != "(":
        return rolle_der_deklaration(t, pos, sig, tiefe)
    aufruf = re.search(r"(\w+)\s*$", t[:auf])
    aufruf = aufruf.group(1) if aufruf else ""
    name, stelle = argument(t, auf, pos)
    if aufruf in ("if", "when"):
        return rolle_an(t, re.search(r"\w+\s*$", t[:auf]).start(), sig, tiefe + 1)
    if name == "color":
        if aufruf in SCHRIFT_AUFRUFE:
            return "Schrift"
        if aufruf == "Icon":
            return "Zeichen"
        return None
    if name in ROLLE_NACH_NAME:
        return ROLLE_NACH_NAME[name]
    if name == "cursorBrush" or aufruf == "SolidColor":
        return "Zeichen"
    if aufruf == "background":
        ganz = t[auf:ende_des_aufrufs(t, auf)]
        if "CircleShape" in ganz:
            return "Zeichen"
        zeile = t[t.rfind("\n", 0, auf) + 1:auf]
        if re.search(r"\.(width|height)\(\s*1\.dp\s*\)", zeile):
            return "Linie"
        return "Flaeche"
    if aufruf == "border":
        return "Linie"
    if name is None and aufruf in sig and stelle < len(sig[aufruf]):
        return ROLLE_NACH_NAME.get(sig[aufruf][stelle])
    if name is not None and aufruf in sig:
        return ROLLE_NACH_NAME.get(name)
    return None


def rolle_der_deklaration(t, pos, sig, tiefe):
    """`val x = … Farbe.y …` oder `fun f(…) = when … Farbe.y`: die Rolle der
    Stellen, an denen x oder f benutzt wird (in derselben Datei)."""
    anfang = t.rfind("\n", 0, pos) + 1
    vor = t[:pos]
    kandidaten = [m for m in re.finditer(r"\b(?:val|var)\s+(\w+)\s*(?::[^=]+)?=|\bfun\s+(\w+)\s*\([^)]*\)\s*(?::[^=]+)?=", vor)]
    if not kandidaten:
        return None
    m = kandidaten[-1]
    if m.start() < t.rfind("\n\n", 0, anfang):
        return None
    name = m.group(1) or m.group(2)
    rollen = set()
    for n in re.finditer(r"\b%s\b" % re.escape(name), t):
        if n.start() == m.start(1 if m.group(1) else 2):
            continue
        r = rolle_an(t, n.start(), sig, tiefe + 1)
        if r:
            rollen.add(r)
    return rollen.pop() if len(rollen) == 1 else None


def vorkommen(wurzel=WURZEL, quellen=QUELLEN):
    """Je Modul: {(Token, Rolle): [Fundstelle, …]} und die Liste der Stellen
    ohne erkannte Rolle."""
    aus = {}
    for modul, ordner in quellen.items():
        dateien = [f for o in ordner for f in sorted((wurzel / o).rglob("*.kt"))
                   if f.name != "Farbe.kt"]
        texte = {f: ohne_kommentare(f.read_text(encoding="utf-8")) for f in dateien}
        sig = signaturen(texte.values())
        gefunden, unbekannt = {}, []
        for f, t in texte.items():
            for m in re.finditer(r"\bFarbe\.(\w+)", t):
                zeile = t.count("\n", 0, m.start()) + 1
                ort = f"{f.relative_to(wurzel)}:{zeile}"
                r = rolle_an(t, m.start(), sig)
                if r is None:
                    unbekannt.append((ort, m.group(1)))
                else:
                    gefunden.setdefault((token_von(m.group(1)), r), []).append(ort)
        aus[modul] = (gefunden, unbekannt)
    return aus


# ------------------------------------------------------------------ Pruefen

def pruefen(paare, zierde, farben, gefunden_je_modul, ausgabe=print):
    befunde = 0
    ausgabe(f"{'Modul':<6} {'Paar':<30} {'Vorder':<8} {'Grund':<8} {'Ist':>7}  {'Soll':>5}")
    for modul, name, vorne, hinten, soll in paare:
        if vorne not in farben or hinten not in farben:
            ausgabe(f"{modul:<6} {name:<30} TOKEN FEHLT ({vorne} / {hinten})")
            befunde += 1
            continue
        wert = kontrast(farben[vorne], farben[hinten])
        marke = " " if wert >= soll else "!"
        if wert < soll:
            befunde += 1
        ausgabe(f"{modul:<6} {name:<30} {farben[vorne]:<8} {farben[hinten]:<8} {wert:6.2f}:1 {soll:5.1f} {marke}")

    ausgabe("\nVollstaendigkeit je Modul und Rolle")
    for modul, (gefunden, unbekannt) in gefunden_je_modul.items():
        eigene = [p for p in paare if p[0] == modul]
        for (tok, rolle), orte in sorted(gefunden.items()):
            if (modul, tok, rolle) in zierde:
                ausgabe(f"  {modul:<6} {tok:<18} {rolle:<8} Zierde ({len(orte)} Stellen)")
                continue
            if rolle == "Schrift":
                da = any(p[2] == tok and p[4] >= 4.5 for p in eigene)
            elif rolle in VORDER_ROLLEN:
                da = any(p[2] == tok and p[4] >= 3.0 for p in eigene)
            else:
                da = any(p[3] == tok for p in eigene)
            if not da:
                befunde += 1
                ausgabe(f"  FEHLT  {modul:<6} {tok:<18} als {rolle:<8} -- z. B. {orte[0]}"
                        f" ({len(orte)} Stellen); kein Paar dieses Moduls in dieser Rolle")
        for ort, name in unbekannt:
            befunde += 1
            ausgabe(f"  ROLLE? {modul:<6} Farbe.{name:<12} {ort} -- Rolle nicht erkannt")
        ausgabe(f"  {modul}: {sum(len(o) for o in gefunden.values())} Stellen, "
                f"{len(gefunden)} Paare aus Farbe und Rolle, {len(unbekannt)} ohne Rolle")
    ausgabe(f"\nPaare geprueft: {len(paare)}   Befunde: {befunde}")
    return befunde


def selbstprobe():
    """Die zwei historischen Fehler, eingebaut -- und eine Gegenprobe."""
    farben = token()
    faelle = []
    kopf = "package x\n@Composable fun Punkt(punktfarbe: Color) {}\n"

    def lauf(dateien, paare, erwartet, name):
        with tempfile.TemporaryDirectory() as d:
            w = Path(d)
            for pfad, text in dateien.items():
                (w / pfad).parent.mkdir(parents=True, exist_ok=True)
                (w / pfad).write_text(text, encoding="utf-8")
            q = {m: [o] for m, o in (("handy", "h"), ("uhr", "u"))}
            b = pruefen(paare, {}, farben, vorkommen(w, q), ausgabe=lambda *_: None)
        ok = (b > 0) == erwartet
        faelle.append(ok)
        print(f"  {'ok ' if ok else 'FEHL'} {name}: {b} Befunde, erwartet {'rot' if erwartet else 'gruen'}")

    grund = [("handy", "Karte", "marke_dunkelblau", "marke_schnee", 4.5),
             ("uhr", "Uhr-Schrift", "marke_auf_dunkel", "marke_asphalt", 4.5),
             ("uhr", "Uhr-Knopf", "marke_auf_dunkel", "marke_rot", 4.5)]
    # B-S5Z-13: oranger Punkt auf der Handy-Karte, das Paar fehlt.
    lauf({"h/A.kt": kopf + "fun a() { Karte { Text(\"x\", color = Farbe.dunkelblau) }\n"
          "  .background(Farbe.schnee)\n  Punkt(punktfarbe = Farbe.orange) }\n"},
         grund, True, "B-S5Z-13 oranger Punkt auf der Karte, nicht gelistet")
    # B-S5Z-15: Rot als SCHRIFT auf der Uhr -- gelistet nur als Flaeche.
    lauf({"u/U.kt": "fun u() { Box(Modifier.background(Farbe.rot)) { Text(\"a\", color = Farbe.aufDunkel) }\n"
          "  Text(\"b\", color = when (z) {\n    true -> Farbe.aufDunkel\n    false -> Farbe.rot\n  })\n"
          "  Box(Modifier.background(Farbe.asphalt)) }\n"},
         grund, True, "B-S5Z-15 Rot als Schrift auf der Uhr, nur als Flaeche gelistet")
    # Eine Farbe ohne erkennbare Rolle meldet sich.
    lauf({"h/B.kt": "fun b() { irgendwas(Farbe.dunkelblau) }\n"},
         grund, True, "Farbe ohne Rolle")
    # Ein Paar unter dem Zielwert.
    lauf({}, [("handy", "zu hell", "marke_orange", "marke_schnee", 4.5)], True,
         "Paar unter dem Zielwert")
    # Gegenprobe: dieselben Stellen, alles gelistet -> gruen.
    voll = grund + [("handy", "Punkt", "marke_orange_tief", "marke_schnee", 3.0),
                    ("uhr", "Rosa", "marke_rosa", "marke_asphalt", 4.5)]
    lauf({"h/A.kt": kopf + "fun a() { Text(\"x\", color = Farbe.dunkelblau)\n"
          "  Box(Modifier.background(Farbe.schnee))\n  Punkt(Farbe.orangeTief) }\n",
          "u/U.kt": "fun u() { Box(Modifier.background(Farbe.rot)) { Text(\"a\", color = Farbe.aufDunkel) }\n"
          "  Text(\"b\", color = when (z) {\n    true -> Farbe.aufDunkel\n    false -> Farbe.rosa\n  })\n"
          "  Box(Modifier.background(Farbe.asphalt)) }\n"},
         voll, False, "Gegenprobe: alles gelistet")
    fehl = faelle.count(False)
    print(f"{len(faelle)} Faelle, {fehl} Fehlschlaege")
    return 1 if fehl else 0


def main():
    if not FARBEN.exists():
        print(f"nicht gelaufen: {FARBEN} fehlt")
        return 2
    if "--selbstprobe" in sys.argv[1:]:
        return selbstprobe()
    befunde = pruefen(PAARE, ZIERDE, token(), vorkommen())
    return 1 if befunde else 0


if __name__ == "__main__":
    sys.exit(main())
