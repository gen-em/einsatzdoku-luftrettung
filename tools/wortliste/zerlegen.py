#!/usr/bin/env python3
"""Kommentare aus PHP und JavaScript entfernen — zeilentreu.

WOFUER. Die Wortliste (`wortliste.py`) prueft *sichtbare* Texte. Ein
Kommentar ist Klasse E der Fundort-Klassen (Konzept P2, Abschnitt 5.1) und
bleibt bis P6 stehen; er darf den Befund nicht fuellen. Also muss er weg,
bevor gesucht wird.

ZEILENTREU heisst: Der Kommentarinhalt wird durch Leerzeichen ersetzt, die
Zeilenumbrueche bleiben stehen. Datei und Zeilennummer eines Treffers zeigen
danach immer noch auf die Stelle im Original — sonst waere jeder Befund
unbrauchbar.

WARUM KEIN REGULAERER AUSDRUCK. Der naheliegende Einzeiler
`re.sub(r'//.*$', '', zeile)` loescht die Haelfte jeder Zeile, in der eine
URL steht (`https://…`), und damit sichtbaren Text. Ein Treffer, der so
verschwindet, faellt niemandem auf: Das Werkzeug meldet null und niemand
weiss, ob es nichts gefunden oder nichts gesucht hat. Deshalb ein Zerleger,
der Zeichenketten kennt.

GRENZEN, ausdruecklich (siehe LIESMICH.md, Abschnitt „Was der Zerleger
nicht kann"):

- Der JS-Zerleger ist eine Heuristik, keine ECMAScript-Grammatik. Er
  unterscheidet Division und regulaeren Ausdruck am zuletzt gesehenen
  bedeutungstragenden Zeichen. Verschachtelte `${…}` in Template-Literalen
  behandelt er als Teil der Zeichenkette.
- Der PHP-Zerleger folgt `<?php`/`?>`, kennt Heredoc und Nowdoc und
  reicht `<script>`- und `<style>`-Bloecke der HTML-Anteile an den
  JS- bzw. CSS-Zerleger weiter.
- Beide irren im Zweifel zugunsten des Textes: Was nicht sicher ein
  Kommentar ist, bleibt stehen. Ein Treffer zu viel kostet eine Ausnahme,
  ein Treffer zu wenig kostet die Aussage.

`--probe` faehrt die Selbstprobe (siehe unten): neunzehn Faelle mit
Sollergebnis, darunter die Fallen, an denen der Einzeiler scheitert.
"""
from __future__ import annotations

import re

# Zeichen, nach denen ein `/` einen regulaeren Ausdruck beginnen kann und
# keine Division ist. Nach `)` oder `]` steht ein Wert — dort ist `/`
# Division. Diese Unterscheidung ist der ganze Trick.
VOR_REGEX_ZEICHEN = set("(,=:[!&|?{};+-*%~^<>\n")
VOR_REGEX_WOERTER = {
    "return", "typeof", "case", "in", "of", "new", "delete", "void",
    "instanceof", "do", "else", "yield", "await", "throw",
}


def _leeren(zeichen: list[str], von: int, bis: int) -> None:
    """Bereich durch Leerzeichen ersetzen, Zeilenumbrueche behalten."""
    for k in range(von, min(bis, len(zeichen))):
        if zeichen[k] != "\n":
            zeichen[k] = " "


def _wort_davor(text: str, i: int) -> str:
    """Das Wort unmittelbar vor Stelle i (fuer `return /…/`)."""
    j = i
    while j > 0 and (text[j - 1].isalnum() or text[j - 1] == "_"):
        j -= 1
    return text[j:i]


def js_bereiche(text: str, von: int = 0, bis: int | None = None) -> list[tuple[int, int]]:
    """Kommentarbereiche in JavaScript-Quelltext, als (start, ende)."""
    n = len(text) if bis is None else bis
    i = von
    zuletzt = "\n"          # als stuende der Zerleger am Zeilenanfang
    bereiche: list[tuple[int, int]] = []
    while i < n:
        c = text[i]
        if c in "\"'":
            j = i + 1
            while j < n:
                if text[j] == "\\":
                    j += 2
                    continue
                if text[j] == c or text[j] == "\n":
                    break
                j += 1
            i = j + 1
            zuletzt = c
            continue
        if c == "`":
            j = i + 1
            while j < n:
                if text[j] == "\\":
                    j += 2
                    continue
                if text[j] == "`":
                    break
                j += 1
            i = j + 1
            zuletzt = "`"
            continue
        if c == "/" and i + 1 < n and text[i + 1] == "/":
            j = text.find("\n", i)
            if j < 0 or j > n:
                j = n
            bereiche.append((i, j))
            i = j
            continue
        if c == "/" and i + 1 < n and text[i + 1] == "*":
            j = text.find("*/", i + 2)
            j = n if j < 0 or j + 2 > n else j + 2
            bereiche.append((i, j))
            i = j
            continue
        if c == "/" and (zuletzt in VOR_REGEX_ZEICHEN
                         or _wort_davor(text, i).lower() in VOR_REGEX_WOERTER):
            j = i + 1
            klasse = False
            gefunden = -1
            while j < n:
                if text[j] == "\\":
                    j += 2
                    continue
                if text[j] == "\n":
                    break
                if text[j] == "[":
                    klasse = True
                elif text[j] == "]":
                    klasse = False
                elif text[j] == "/" and not klasse:
                    gefunden = j
                    break
                j += 1
            if gefunden >= 0:
                i = gefunden + 1
                zuletzt = "/"
                continue
            # Kein Abschluss in derselben Zeile: dann war es doch Division.
        if not c.isspace() or c == "\n":
            zuletzt = c
        i += 1
    return bereiche


def css_bereiche(text: str, von: int = 0, bis: int | None = None) -> list[tuple[int, int]]:
    """Kommentarbereiche in CSS. CSS kennt nur `/* … */`."""
    n = len(text) if bis is None else bis
    i = von
    bereiche: list[tuple[int, int]] = []
    while i < n:
        if text[i] in "\"'":
            c = text[i]
            j = i + 1
            while j < n:
                if text[j] == "\\":
                    j += 2
                    continue
                if text[j] == c or text[j] == "\n":
                    break
                j += 1
            i = j + 1
            continue
        if text[i] == "/" and i + 1 < n and text[i + 1] == "*":
            j = text.find("*/", i + 2)
            j = n if j < 0 or j + 2 > n else j + 2
            bereiche.append((i, j))
            i = j
            continue
        i += 1
    return bereiche


_BLOCK = re.compile(r"<(script|style)\b[^>]*>", re.IGNORECASE)


def _html_bereiche(text: str, php_inseln: list[tuple[int, int]]) -> list[tuple[int, int]]:
    """Kommentare in <script>- und <style>-Bloecken der HTML-Anteile.

    ZWEI DURCHGAENGE, und der Grund dafuer ist eine Falle, in die die erste
    Fassung dieses Moduls gelaufen ist: Ein <script>-Block einer PHP-Seite
    enthaelt fast immer `<?= … ?>`. Wer die HTML-Anteile nur zwischen zwei
    PHP-Inseln betrachtet, sieht das oeffnende <script> in einem Stueck und
    den Rest des Blocks in einem anderen — und findet die Kommentare dort
    nicht mehr. In index.php blieben so 400 Zeilen JS-Kommentar stehen.

    Deshalb: die PHP-Inseln erst durch Leerzeichen ersetzen (zeilentreu),
    dann den Rest als ein zusammenhaengendes HTML-Dokument lesen. Was der
    JS-Zerleger dann sieht, ist der Block mit Loechern — und Loecher aus
    Leerzeichen stoeren ihn nicht.
    """
    zeichen = list(text)
    for von, bis in php_inseln:
        _leeren(zeichen, von, bis)
    nur_html = "".join(zeichen)

    bereiche: list[tuple[int, int]] = []
    klein = nur_html.lower()
    for tr in _BLOCK.finditer(nur_html):
        art = tr.group(1).lower()
        anfang = tr.end()
        ende = klein.find(f"</{art}>", anfang)
        if ende < 0:
            ende = len(nur_html)
        if art == "script":
            bereiche += js_bereiche(nur_html, anfang, ende)
        else:
            bereiche += css_bereiche(nur_html, anfang, ende)
    return bereiche


_HEREDOC = re.compile(r"<<<[ \t]*(['\"]?)([A-Za-z_]\w*)\1\r?\n")


def php_bereiche(text: str) -> list[tuple[int, int]]:
    """Kommentarbereiche in einer PHP-Datei (mit HTML-Anteilen)."""
    n = len(text)
    i = 0
    bereiche: list[tuple[int, int]] = []
    inseln: list[tuple[int, int]] = []
    while i < n:
        auf = text.find("<?", i)
        if auf < 0:
            break
        i = auf + 2
        if text.startswith("php", i):
            i += 3
        elif text.startswith("=", i):
            i += 1
        # ---- innerhalb von PHP ------------------------------------------
        while i < n:
            c = text[i]
            if c == "?" and i + 1 < n and text[i + 1] == ">":
                i += 2
                break
            if c == "'":
                j = i + 1
                while j < n:
                    if text[j] == "\\":
                        j += 2
                        continue
                    if text[j] == "'":
                        break
                    j += 1
                i = j + 1
                continue
            if c == '"':
                j = i + 1
                while j < n:
                    if text[j] == "\\":
                        j += 2
                        continue
                    if text[j] == '"':
                        break
                    j += 1
                i = j + 1
                continue
            if c == "<" and text.startswith("<<<", i):
                tr = _HEREDOC.match(text, i)
                if tr:
                    marke = tr.group(2)
                    schluss = re.compile(r"^[ \t]*" + re.escape(marke) + r"\b", re.MULTILINE)
                    ende = schluss.search(text, tr.end())
                    i = ende.end() if ende else n
                    continue
            if c == "/" and i + 1 < n and text[i + 1] == "/":
                j = i + 2
                while j < n and text[j] != "\n":
                    if text[j] == "?" and j + 1 < n and text[j + 1] == ">":
                        break       # `?>` beendet den Zeilenkommentar
                    j += 1
                bereiche.append((i, j))
                i = j
                continue
            if c == "#" and not text.startswith("#[", i):
                j = i + 1
                while j < n and text[j] != "\n":
                    if text[j] == "?" and j + 1 < n and text[j + 1] == ">":
                        break
                    j += 1
                bereiche.append((i, j))
                i = j
                continue
            if c == "/" and i + 1 < n and text[i + 1] == "*":
                j = text.find("*/", i + 2)
                j = n if j < 0 else j + 2
                bereiche.append((i, j))
                i = j
                continue
            i += 1
        inseln.append((auf, i))
    return bereiche + _html_bereiche(text, inseln)


def xml_bereiche(text: str) -> list[tuple[int, int]]:
    """Alles, was in einer XML-Datei KEIN sichtbarer Text ist.

    Das ist mehr als die Kommentare: Auch die Tags selbst gehoeren dazu.
    In einer Android-`strings.xml` steht der sichtbare Text ZWISCHEN den
    Tags; `<string name="dienst_beginnen">` ist ein Bezeichner, den niemand
    liest. Bliebe er stehen, meldete die Wortliste jeden Schluesselnamen als
    Treffer — und eine Liste, die zu neun Zehnteln aus Falschmeldungen
    besteht, liest bald niemand mehr.

    BEWUSST OHNE XML-PARSER. Gebraucht wird eine Abbildung, die die
    ZEILENNUMMERN erhaelt (die Ausgabe nennt Datei und Zeile) und die auch
    eine kaputte Datei uebersteht. Ein Parser gaebe einen Baum und keine
    Positionen, und bei einem Syntaxfehler gar nichts.
    """
    bereiche: list[tuple[int, int]] = []
    i, n = 0, len(text)
    while i < n:
        if text.startswith("<!--", i):
            j = text.find("-->", i + 4)
            j = n if j < 0 else j + 3
            bereiche.append((i, j))
            i = j
            continue
        if text[i] == "<":
            j = text.find(">", i + 1)
            j = n if j < 0 else j + 1
            bereiche.append((i, j))
            i = j
            continue
        i += 1
    return bereiche


def ohne_kommentare(text: str, art: str) -> str:
    """Kommentare durch Leerzeichen ersetzen. `art` ist php, js, css, xml oder md."""
    if art == "md":
        return text
    if art == "php":
        bereiche = php_bereiche(text)
    elif art == "js":
        bereiche = js_bereiche(text)
    elif art == "css":
        bereiche = css_bereiche(text)
    elif art == "xml":
        bereiche = xml_bereiche(text)
    else:
        raise ValueError(f"Unbekannte Art: {art}")
    zeichen = list(text)
    for von, bis in bereiche:
        _leeren(zeichen, von, bis)
    return "".join(zeichen)


# --------------------------------------------------------------- Selbstprobe

# Je Fall: Art, Eingabe, was danach NOCH da sein muss, was WEG sein muss.
#
# Die Faelle 3, 4, 9 und 10 sind die eigentliche Begruendung dieses Moduls:
# An ihnen scheitert jeder zeilenweise regulaere Ausdruck.
PROBEN: list[tuple[str, str, list[str], list[str]]] = [
    ("js", "var a = 1; // Flugtag\n", ["var a"], ["Flugtag"]),
    ("js", "/* Hubschrauber */ var b = 2;\n", ["var b"], ["Hubschrauber"]),
    ("js", "var u = 'https://luftrettung.net/pfad';\n", ["luftrettung.net"], []),
    ("js", "var r = /a\\/\\/b/; var t = 'Flugtag';\n", ["Flugtag"], []),
    ("js", "var s = \"// kein Kommentar: Flugtag\";\n", ["Flugtag"], []),
    ("js", "var q = a / b; var w = 'Pilot';\n", ["Pilot"], []),
    ("js", "return /x/.test(s) ? 'Hubschrauber' : '';\n", ["Hubschrauber"], []),
    ("js", "var v = `Backtick // Flugtag`;\n", ["Flugtag"], []),
    ("php", "<?php /* Flugtag */ $x = 'Hubschrauber'; ?>\n",
     ["Hubschrauber"], ["Flugtag"]),
    ("php", "<p>Sichtbar: Hubschrauber</p>\n<?php // Flugtag\n?>\n",
     ["Hubschrauber"], ["Flugtag"]),
    ("php", "<?php $s = '// Flugtag'; ?>\n", ["Flugtag"], []),
    ("php", "<?php $s = <<<'T'\nFlugtag im Nowdoc\nT;\n?>\n", ["Flugtag"], []),
    ("php", "<script>/* Flugtag */ var x = 'Pilot';</script>\n",
     ["Pilot"], ["Flugtag"]),
    ("php", "<style>/* Hubschrauber */ .a { color: red }</style>\n",
     ["color"], ["Hubschrauber"]),
    ("php", "<?php # Flugtag\n$y = 'Pilot'; ?>\n", ["Pilot"], ["Flugtag"]),
    # Der Fall, an dem die erste Fassung gescheitert ist: eine PHP-Insel
    # MITTEN im <script>-Block. Ohne die zwei Durchgaenge in
    # _html_bereiche() blieben in index.php vierhundert Zeilen
    # JS-Kommentar stehen.
    ("php", "<script>\nvar id = <?= json_encode($x) ?>;\n/* Flugtag */\n"
            "var n = 'Pilot';\n</script>\n", ["Pilot"], ["Flugtag"]),
    # XML (Android-`strings.xml`, S4/D1). Hier ist MEHR wegzuraeumen als der
    # Kommentar: Der Schluesselname im Tag ist ein Bezeichner, den niemand
    # liest — bliebe er stehen, meldete die Wortliste jeden davon als Treffer.
    ("xml", '<resources>\n  <!-- Flugtag -->\n'
            '  <string name="flugtag_titel">Hubschrauber</string>\n</resources>\n',
     ["Hubschrauber"], ["Flugtag", "flugtag_titel"]),
    # Ein `<` im TEXT (als &lt; maskiert, wie XML es verlangt) darf keinen
    # Tag eroeffnen und den Rest der Datei verschlucken.
    ("xml", '<string name="a">Weniger &lt; als Pilot</string>\n', ["Pilot"], []),
    # Eine kaputte Datei — ein Tag ohne schliessende Klammer. Sie muss
    # durchlaufen und darf nicht werfen: Ein Pruefmittel, das an einer
    # fehlerhaften Datei abbricht, prueft die uebrigen nicht mehr.
    ("xml", '<string name="a">Pilot</string>\n<string name="b"\n',
     ["Pilot"], []),
]


def selbstprobe() -> tuple[int, int, list[str]]:
    """Faehrt die Proben. Liefert (bestanden, gesamt, Fehlerbeschreibungen)."""
    fehler: list[str] = []
    gut = 0
    for nr, (art, eingabe, bleibt, weg) in enumerate(PROBEN, 1):
        ergebnis = ohne_kommentare(eingabe, art)
        schlecht = []
        for wort in bleibt:
            if wort not in ergebnis:
                schlecht.append(f'„{wort}“ verschwunden')
        for wort in weg:
            if wort in ergebnis:
                schlecht.append(f'„{wort}“ geblieben')
        if len(ergebnis) != len(eingabe):
            schlecht.append("Laenge veraendert (nicht zeilentreu)")
        if ergebnis.count("\n") != eingabe.count("\n"):
            schlecht.append("Zeilenzahl veraendert")
        if schlecht:
            fehler.append(f"Probe {nr} ({art}): " + "; ".join(schlecht))
        else:
            gut += 1
    return gut, len(PROBEN), fehler


if __name__ == "__main__":
    gut, gesamt, fehler = selbstprobe()
    for f in fehler:
        print(f)
    print(f"Selbstprobe des Zerlegers: {gut}/{gesamt} bestanden.")
    raise SystemExit(0 if gut == gesamt else 2)
