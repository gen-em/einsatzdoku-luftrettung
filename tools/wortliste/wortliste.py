#!/usr/bin/env python3
"""Wortliste: sucht luftgebundene Begriffe dort, wo die Anwendung neutral
sprechen soll.

    Steht in einem sichtbaren Text oder in der normativen Dokumentation ein
    Wort, das nur von der Luftrettung her gedacht ist?

Beantwortet wird das mit **drei Zahlen je Bereich**: Treffer gesamt, Treffer
ausserhalb der Ausnahmen, ungenutzte Ausnahmen. Die zweite und die dritte
muessen null sein; sonst ist der Rueckgabewert 1.

WARUM DIE DRITTE ZAHL. Eine Ausnahme, die nicht greift, beschreibt entweder
etwas, das es nicht mehr gibt, oder der Lauf hat die Stelle gar nicht
angesehen — dann prueft er weniger als gedacht. Dasselbe Prinzip wie die
ungenutzten Regeln im Kreislaufvergleich (tools/referenzdatensatz/vergleich).

WAS DIESES WERKZEUG NICHT KANN. Es findet Woerter, keine Perspektive. Ein
Satz wie „das Rettungsmittel landet am Einsatzort" enthaelt kein Sperrwort
und ist trotzdem von der Luft her gedacht. Dafuer gibt es kein Werkzeug,
nur Lesen (Konzept P2, Paket D4, Schritt 11).

Aufruf:
    python3 wortliste.py                  # alle Bereiche
    python3 wortliste.py --bereich a      # nur die PHP-Dateien des Servers
    python3 wortliste.py --alle           # auch die erklaerten Treffer zeigen
    python3 wortliste.py --probe          # Selbstprobe des Zerlegers
    python3 wortliste.py --bericht /tmp/w.txt

Rueckgabewert: 0 = sauber, 1 = Treffer ausserhalb der Ausnahmen oder
ungenutzte Ausnahmen, 2 = Fehler (fehlende Datei, unbrauchbare Regel,
Selbstprobe nicht bestanden).
"""
from __future__ import annotations

import argparse
import fnmatch
import json
import pathlib
import re
import sys

HIER = pathlib.Path(__file__).resolve().parent
WURZEL = HIER.parent.parent

sys.path.insert(0, str(HIER))
import zerlegen                                    # noqa: E402

# Die Bereiche aus dem Konzept P2, Abschnitt 5.1 (Klassen A und B), seit
# S4/D1 um die Android-Apps erweitert.
#
# DIE REGEL DAHINTER (S4, B-S4-06, auf Ansage vom 01.09.2026):
#
#   Jeder sichtbare Text der Anwendung laeuft durch die Wortliste — gleich,
#   in welchem Client er steht. Ein Bereich fehlt nicht, weil ein Verzeichnis
#   jung ist; er fehlt, weil ihn niemand eingetragen hat. Wer einen Client
#   hinzufuegt, traegt seine Textdateien im selben Paket ein, in dem der
#   Client entsteht. Ein Lauf, der einen Client uebergeht, meldet keine Null —
#   er meldet gar nichts.
#
# Genau das war passiert: Die Android-Apps entstanden in S4/B1, und der Lauf
# nach C2 meldete 0 Treffer, ohne eine einzige Zeile der App angesehen zu
# haben. Der Fall, vor dem CLAUDE.md 6 warnt — eine gruene Zahl, die etwas
# anderes gemessen hat.
#
# Was hier NICHT steht, ist Absicht: CHANGELOG.md (Historie), die Konzept-
# und Pruefdokumente, Geraete-Eingabe.md und Uhr-Layout_Regeln.md
# (plattformspezifisch, Klasse G), Backlog.md und tools/ (Klasse H).
#
# `watch/` FEHLT WEITERHIN, und das ist eine Arbeitsteilung, kein Versehen:
# Die sichtbaren Texte der Garmin-App (`watch/resources/**/*.xml`) sind die
# aeltesten des Projekts und damit die wahrscheinlichste Fundstelle. Ihre
# Pruefung geht an eine andere Instanz (Ansage 01.09.2026); sie braucht
# Kenntnis der Monkey-C-Ressourcen und der historischen Begriffe. Der Bereich
# heisst dort `e` und gehoert in dieselbe Liste, sobald er kommt.
BEREICHE: dict[str, dict] = {
    "a": {
        "titel": "server/*.php, server/api/*.php (sichtbare Texte, ohne Kommentare)",
        "art": "php",
        "glob": ["server/*.php", "server/api/*.php"],
        # config.php gehoert nicht zum Repositorium (sie steht in .gitignore
        # und liegt nur auf dem Server). Waere sie dabei, haenge die
        # Dateizahl davon ab, ob gerade eine lokale Installation eingerichtet
        # ist — und die Zahlen zweier Laeufe waeren nicht vergleichbar.
        "ausser": ["server/config.php"],
    },
    "b": {
        "titel": "server/assets/*.js ohne vendor/ (Zeichenketten, ohne Kommentare)",
        "art": "js",
        "glob": ["server/assets/*.js"],
    },
    "d": {
        "titel": "android/*/src/main/res/values/strings.xml (Handy und Uhr)",
        "art": "xml",
        # `values/` OHNE Sprachkennung: Das ist die deutsche Fassung, und eine
        # andere gibt es nicht (die Apps sind einsprachig wie die
        # Weboberflaeche). Kaeme eine hinzu, gehoerte sie mit in dieses
        # Muster — `values-*/strings.xml` waere dann die Erweiterung.
        "glob": ["android/*/src/main/res/values/strings.xml"],
    },
    "c": {
        "titel": "normative Dokumentation",
        "art": "md",
        "dateien": [
            "README.md",
            "docs/Handbuch.md",
            "docs/Export-Format.md",
            "docs/Technik.md",
            "docs/Backup-Format.md",
            "docs/JSON-Vertrag.md",
            "docs/Design.md",
            "docs/Lizenzen.md",
        ],
    },
}


def melde(t: str = "") -> None:
    print(t, flush=True)


# ------------------------------------------------------------------ Laden

def lade_sperrliste(pfad: pathlib.Path) -> tuple[list[dict], list[dict]]:
    d = json.loads(pfad.read_text(encoding="utf-8"))
    muster = []
    for m in d["muster"]:
        for pflicht in ("id", "regex", "grund"):
            if not m.get(pflicht):
                raise SystemExit(f"Muster ohne {pflicht}: {m}")
        flags = 0 if m.get("gross") else re.IGNORECASE
        m = dict(m)
        m["_re"] = re.compile(m["regex"], flags)
        muster.append(m)
    fallen = []
    for f in d.get("fallen", []):
        f = dict(f)
        f["_re"] = re.compile(f["regex"], re.IGNORECASE)
        fallen.append(f)
    return muster, fallen


def lade_ausnahmen(pfad: pathlib.Path) -> list[dict]:
    d = json.loads(pfad.read_text(encoding="utf-8"))
    regeln = []
    for r in d["regeln"]:
        # Ohne Begruendung keine Regel — dieselbe Vorschrift wie bei den
        # Ausnahmelisten des Kreislaufvergleichs. Eine Ausnahme ohne Grund
        # ist ein Filter, und ein Filter verdeckt genau das, wofuer die
        # Liste da ist.
        if not r.get("begruendung"):
            raise SystemExit(f"Ausnahme ohne Begruendung: {r.get('id') or r}")
        if not r.get("klasse"):
            raise SystemExit(f"Ausnahme ohne Klasse: {r.get('id') or r}")
        if not r.get("id"):
            raise SystemExit(f"Ausnahme ohne id: {r}")
        r = dict(r)
        r["_zeile"] = re.compile(r["zeile"], re.IGNORECASE) if r.get("zeile") else None
        r["_von"] = re.compile(r["von"]) if r.get("von") else None
        r["_bis"] = re.compile(r["bis"]) if r.get("bis") else None
        r["_abschnitt"] = re.compile(r["abschnitt"]) if r.get("abschnitt") else None
        r["_treffer"] = 0
        regeln.append(r)
    return regeln


# ------------------------------------------------------------- Fundstellen

def dateien_des_bereichs(kennung: str) -> list[pathlib.Path]:
    b = BEREICHE[kennung]
    pfade: list[pathlib.Path] = []
    for muster in b.get("glob", []):
        pfade += sorted(WURZEL.glob(muster))
    for name in b.get("dateien", []):
        p = WURZEL / name
        if not p.exists():
            raise SystemExit(f"Datei fehlt: {name}")
        pfade.append(p)
    # vendor/ ist fremder Quelltext und wird nie angefasst.
    ausser = set(b.get("ausser", []))
    return [p for p in pfade
            if "vendor" not in p.parts and str(p.relative_to(WURZEL)) not in ausser]


def _bloecke(zeilen: list[str], regel: dict) -> list[tuple[int, int]]:
    """Zeilenbereiche (1-basiert, einschliesslich), die eine Regel abdeckt."""
    if regel["_von"]:
        bereiche, offen = [], None
        for nr, z in enumerate(zeilen, 1):
            if offen is None and regel["_von"].search(z):
                offen = nr
            elif offen is not None and regel["_bis"] and regel["_bis"].search(z):
                bereiche.append((offen, nr))
                offen = None
        if offen is not None:
            bereiche.append((offen, len(zeilen)))
        return bereiche
    if regel["_abschnitt"]:
        # Markdown: von der passenden Ueberschrift bis zur naechsten
        # Ueberschrift gleicher oder hoeherer Ebene.
        bereiche, offen, ebene = [], None, 0
        for nr, z in enumerate(zeilen, 1):
            k = re.match(r"(#+)\s", z)
            if offen is not None and k and len(k.group(1)) <= ebene:
                bereiche.append((offen, nr - 1))
                offen = None
            if offen is None and k and regel["_abschnitt"].search(z):
                offen, ebene = nr, len(k.group(1))
        if offen is not None:
            bereiche.append((offen, len(zeilen)))
        return bereiche
    return [(1, len(zeilen))]


def passt(regel: dict, bereich: str, rel: str, zeilennr: int,
          zeile: str, muster_id: str, bloecke_zwischenspeicher: dict) -> bool:
    if regel.get("bereich") and regel["bereich"] != bereich:
        return False
    if regel.get("datei") and not fnmatch.fnmatch(rel, regel["datei"]):
        return False
    if regel.get("muster") and muster_id not in regel["muster"]:
        return False
    if regel["_zeile"] and not regel["_zeile"].search(zeile):
        return False
    if regel["_von"] or regel["_abschnitt"]:
        schluessel = (regel["id"], rel)
        bereiche = bloecke_zwischenspeicher.get(schluessel)
        if bereiche is None:
            return False
        if not any(a <= zeilennr <= e for a, e in bereiche):
            return False
    return True


def suche(kennung: str, muster: list[dict], fallen: list[dict],
          regeln: list[dict]) -> dict:
    b = BEREICHE[kennung]
    treffer_gesamt = 0
    offen: list[tuple[str, int, str, str]] = []
    erklaert: list[tuple[str, int, str, str, str]] = []
    fallen_zahl = {f["wort"]: 0 for f in fallen}
    fallen_durchgerutscht: list[str] = []
    dateien = dateien_des_bereichs(kennung)

    for pfad in dateien:
        rel = str(pfad.relative_to(WURZEL))
        roh = pfad.read_text(encoding="utf-8")
        text = zerlegen.ohne_kommentare(roh, b["art"])
        zeilen_roh = roh.splitlines()
        zeilen = text.splitlines()

        zwischenspeicher: dict = {}
        for r in regeln:
            if r["_von"] or r["_abschnitt"]:
                if r.get("datei") and not fnmatch.fnmatch(rel, r["datei"]):
                    continue
                zwischenspeicher[(r["id"], rel)] = _bloecke(zeilen_roh, r)

        for nr, zeile in enumerate(zeilen, 1):
            fallen_spannen = []
            for f in fallen:
                for tr in f["_re"].finditer(zeile):
                    fallen_zahl[f["wort"]] += 1
                    fallen_spannen.append((tr.start(), tr.end(), f["wort"]))
            for m in muster:
                for tr in m["_re"].finditer(zeile):
                    for a, e, wort in fallen_spannen:
                        if a <= tr.start() and tr.end() <= e:
                            fallen_durchgerutscht.append(
                                f"{rel}:{nr} — Muster {m['id']} traf in der Falle „{wort}“")
                    treffer_gesamt += 1
                    grund = None
                    for r in regeln:
                        if passt(r, kennung, rel, nr, zeile, m["id"], zwischenspeicher):
                            r["_treffer"] += 1
                            grund = r["id"]
                            break
                    zeigetext = zeilen_roh[nr - 1].strip() if nr <= len(zeilen_roh) else zeile.strip()
                    if len(zeigetext) > 150:
                        zeigetext = zeigetext[:147] + "…"
                    if grund:
                        erklaert.append((rel, nr, m["id"], zeigetext, grund))
                    else:
                        offen.append((rel, nr, m["id"], zeigetext))
    return {
        "kennung": kennung,
        "titel": b["titel"],
        "dateien": len(dateien),
        "gesamt": treffer_gesamt,
        "offen": offen,
        "erklaert": erklaert,
        "fallen": fallen_zahl,
        "durchgerutscht": fallen_durchgerutscht,
    }


# ------------------------------------------------------------------ Bericht

def bericht(ergebnisse: list[dict], regeln: list[dict], alle: bool,
            geprueft: list[str] | None = None) -> tuple[str, int]:
    aus: list[str] = []
    offen_gesamt = 0
    for e in ergebnisse:
        aus.append("")
        aus.append(f"Bereich ({e['kennung']}) — {e['titel']}")
        aus.append(f"  Dateien:                       {e['dateien']}")
        aus.append(f"  Treffer gesamt:                {e['gesamt']}")
        aus.append(f"  davon durch Ausnahmen erklärt: {len(e['erklaert'])}")
        stellen = len({(rel, nr) for rel, nr, _, _ in e["offen"]})
        aus.append(f"  außerhalb der Ausnahmen:       {len(e['offen'])} "
                   f"(in {stellen} Zeilen)")
        offen_gesamt += len(e["offen"])
        for rel, nr, mid, txt in e["offen"]:
            aus.append(f"    {rel}:{nr}  [{mid}]  {txt}")
        if alle and e["erklaert"]:
            aus.append("  erklärte Treffer:")
            for rel, nr, mid, txt, grund in e["erklaert"]:
                aus.append(f"    {rel}:{nr}  [{mid}]  ({grund})  {txt}")

    fallen_summe: dict[str, int] = {}
    durchgerutscht: list[str] = []
    for e in ergebnisse:
        for wort, n in e["fallen"].items():
            fallen_summe[wort] = fallen_summe.get(wort, 0) + n
        durchgerutscht += e["durchgerutscht"]

    aus.append("")
    aus.append("Teilstring-Fallen (Wörter, die ein Sperrwort enthalten, aber keines sind)")
    for wort, n in fallen_summe.items():
        aus.append(f"  {wort:<18} {n:>4} Vorkommen")
    aus.append(f"  als Treffer gezählt: {len(durchgerutscht)}")
    for d in durchgerutscht:
        aus.append(f"    {d}")

    # Bei einem Teillauf koennen Regeln nicht greifen, deren Dateien gar nicht
    # angesehen wurden. Sie als „ungenutzt" zu melden waere eine falsche
    # Auskunft — die Zahl gilt nur fuer den vollstaendigen Lauf.
    unbeteiligt = []
    if geprueft is not None:
        for r in regeln:
            if r["_treffer"] == 0 and r.get("datei") \
               and not any(fnmatch.fnmatch(f, r["datei"]) for f in geprueft):
                unbeteiligt.append(r)
    ungenutzt = [r for r in regeln if r["_treffer"] == 0 and r not in unbeteiligt]
    aus.append("")
    aus.append(f"Ausnahmen: {len(regeln)} Regeln, {len(regeln) - len(ungenutzt) - len(unbeteiligt)} "
               f"gegriffen, {len(ungenutzt)} ungenutzt")
    for r in ungenutzt:
        aus.append(f"    ungenutzt: {r['id']}  (Klasse {r['klasse']})")
    if unbeteiligt:
        aus.append(f"  {len(unbeteiligt)} Regeln betreffen Dateien, die dieser Teillauf "
                   f"nicht angesehen hat — sie zählen nicht mit:")
        for r in unbeteiligt:
            aus.append(f"    nicht geprüft: {r['id']}  ({r['datei']})")

    schlecht = offen_gesamt + len(ungenutzt) + len(durchgerutscht)
    stellen_gesamt = len({(rel, nr) for e in ergebnisse for rel, nr, _, _ in e["offen"]})
    aus.append("")
    aus.append(f"Ergebnis: {offen_gesamt} Treffer außerhalb der Ausnahmen "
               f"(in {stellen_gesamt} Zeilen), "
               f"{len(ungenutzt)} ungenutzte Ausnahmen, "
               f"{len(durchgerutscht)} durchgerutschte Fallen.")
    return "\n".join(aus), (1 if schlecht else 0)


def main() -> int:
    p = argparse.ArgumentParser(description="Wortliste der Phase P2")
    p.add_argument("--bereich", choices=sorted(BEREICHE), action="append",
                   help="nur diesen Bereich prüfen (mehrfach möglich)")
    p.add_argument("--alle", action="store_true",
                   help="auch die durch Ausnahmen erklärten Treffer auflisten")
    p.add_argument("--probe", action="store_true",
                   help="Selbstprobe des Zerlegers fahren und beenden")
    p.add_argument("--sperrliste", default=str(HIER / "sperrliste.json"))
    p.add_argument("--ausnahmen", default=str(HIER / "ausnahmen.json"))
    p.add_argument("--bericht", help="Bericht zusätzlich in diese Datei schreiben")
    a = p.parse_args()

    if a.probe:
        gut, gesamt, fehler = zerlegen.selbstprobe()
        for f in fehler:
            melde(f)
        melde(f"Selbstprobe des Zerlegers: {gut}/{gesamt} bestanden.")
        return 0 if gut == gesamt else 2

    muster, fallen = lade_sperrliste(pathlib.Path(a.sperrliste))
    regeln = lade_ausnahmen(pathlib.Path(a.ausnahmen))
    kennungen = a.bereich or sorted(BEREICHE)

    melde(f"Sperrliste: {len(muster)} Muster, {len(fallen)} Fallen. "
          f"Ausnahmen: {len(regeln)} Regeln.")
    ergebnisse = [suche(k, muster, fallen, regeln) for k in kennungen]
    geprueft = [str(p.relative_to(WURZEL)) for k in kennungen
                for p in dateien_des_bereichs(k)]
    text, rc = bericht(ergebnisse, regeln, a.alle, geprueft)
    melde(text)
    if a.bericht:
        pathlib.Path(a.bericht).write_text(text + "\n", encoding="utf-8")
    return rc


if __name__ == "__main__":
    raise SystemExit(main())
