#!/usr/bin/env python3
"""Zwei Exporte feldgenau vergleichen (Arbeitspaket B5, E-P1-13).

WOFUER. Das Projekt hat keine automatisierten Tests. Was es stattdessen haben
kann, ist ein REFERENZZUSTAND und die Frage: Kommt derselbe Bestand nach einem
Umlauf unveraendert wieder heraus? Dieses Werkzeug beantwortet sie mit einer
Zahl statt mit einem Eindruck.

DREI SCHRITTE.

  1. LESEN        CSV-Archiv oder .edbak in einen Baum aus Listen (lesen.py)
  2. NORMALISIEREN  fluechtige Anteile durch Marken ersetzen
                  (normalisieren.py) — IDs, Zeitpunkte, App-Version
  3. VERGLEICHEN  Zeile fuer Zeile, Feld fuer Feld, ueber einen natuerlichen
                  Schluessel statt ueber die Reihenfolge

AUSNAHMEN SIND KEINE FILTER. Eine Abweichung, die eine Ausnahmeregel trifft,
verschwindet nicht — sie wandert in einen eigenen Abschnitt des Berichts, mit
der Begruendung daneben. Wer den Bericht liest, sieht also beides: was sich
geaendert hat und was sich aendern DURFTE. Eine Ausnahme ohne Begruendung
wird abgewiesen.

Aufruf:
    python3 vergleichen.py --art csv   referenz.zip   aktuell.zip
    python3 vergleichen.py --art edbak referenz.edbak aktuell.edbak --passwort …
    ... [--ausnahmen ausnahmen/csv_umlauf.json] [--bericht bericht.json]
        [--testabweichung]

Rueckgabe: 0 = keine unerklaerte Abweichung, 1 = Abweichungen, 2 = Fehler.
"""
from __future__ import annotations

import argparse
import json
import re
import pathlib
import sys

HIER = pathlib.Path(__file__).resolve().parent
sys.path.insert(0, str(HIER))

import lesen          # noqa: E402
import normalisieren  # noqa: E402


# ------------------------------------------------------------- Schluessel
#
# Verglichen wird ueber einen NATUERLICHEN Schluessel, nicht ueber die
# Zeilennummer. Sonst verschoebe eine einzige fehlende Zeile alles dahinter
# und der Bericht meldete hundert Abweichungen, wo eine ist.

def _schluessel(zeilen: list[dict], felder: list[str]) -> dict:
    """Ordnet jeder Zeile einen Schluessel zu; Gleichstand bekommt eine Nummer."""
    aus: dict = {}
    zaehler: dict = {}
    for z in zeilen:
        roh = "|".join(str(z.get(f, "")) for f in felder)
        n = zaehler.get(roh, 0)
        zaehler[roh] = n + 1
        aus[roh if n == 0 else f"{roh}#{n}"] = z
    return aus


# DER SCHLUESSEL DARF NICHTS ENTHALTEN, WAS SICH BEIM UMLAUF AENDERT.
#
# `diensttage` stand zuerst auf ["diensttag", "dienst_beginn"]. Der
# Dienstbeginn entsteht beim Import aber neu (aus den Einsaetzen, nicht aus
# der Datei) — damit passte KEIN Tag mehr auf seinen Vorgaenger, und der
# Bericht meldete 15 fehlende und 13 zusaetzliche Zeilen statt der zwei
# Diensttage, die tatsaechlich verlorengehen. Ein Schluessel, der sich mit
# den Daten aendert, verdeckt genau das, was er finden soll.
#
# Mehrere Dienste an einem Kalendertag sind seit E9 zulaessig; sie bekommen
# vom Schluessel eine laufende Nummer (`2026-03-28#1`).
SCHLUESSEL_CSV = {
    "einsaetze":  ["diensttag", "beginn", "uhrzeit_ortszeit"],
    "diensttage": ["diensttag"],
    "ruhezeiten": ["diensttag", "beginn"],
    "felder":     ["datei", "feld"],
}
# Im Backup gibt es einen echten, tragfaehigen Schluessel: client_ref
# ist die Referenz, ueber die auch die Dublettenerkennung laeuft.
SCHLUESSEL_EDBAK = {
    "missions":      ["client_ref"],
    "rest_segments": ["client_ref"],
    "days":          ["day", "started_at"],
}


# ------------------------------------------------------------- Ausnahmen

class Ausnahmen:
    """Regeln, die eine Abweichung als erwartet ausweisen.

    Eine Regel trifft, wenn Bereich und Feld passen und — falls angegeben —
    auch die Werte davor und danach. `*` steht fuer „jedes Feld".
    """

    def __init__(self, daten: dict | None):
        self.name = (daten or {}).get("name", "keine")
        self.beschreibung = (daten or {}).get("beschreibung", "")
        self.regeln = (daten or {}).get("regeln", [])
        for i, r in enumerate(self.regeln):
            if not r.get("begruendung"):
                raise ValueError(
                    f"Ausnahmeregel {i} ({r.get('bereich')}/{r.get('feld')}) ohne "
                    f"Begruendung. Eine Ausnahme ohne Grund ist ein Filter.")
        self.getroffen = [0] * len(self.regeln)

    def treffer(self, abw: dict) -> dict | None:
        for i, r in enumerate(self.regeln):
            if r.get("bereich") not in (abw["bereich"], "*"):
                continue
            if r.get("feld") not in (abw.get("feld"), "*"):
                continue
            if "art" in r and r["art"] != abw["art"]:
                continue
            if "von" in r and str(r["von"]) != str(abw.get("referenz", "")):
                continue
            if "nach" in r and str(r["nach"]) != str(abw.get("aktuell", "")):
                continue
            self.getroffen[i] += 1
            return r
        return None

    def ungenutzte(self) -> list[dict]:
        """Regeln, die nie gegriffen haben.

        Sie sind nicht harmlos: Entweder beschreibt die Regel etwas, das es
        nicht mehr gibt (dann gehoert sie weg), oder der Umlauf hat gar nicht
        stattgefunden, den sie betrifft (dann prueft der Lauf weniger als
        gedacht).
        """
        return [r for r, n in zip(self.regeln, self.getroffen) if n == 0]


# ----------------------------------------------------------- Vergleichen

class Vergleich:
    def __init__(self, ausnahmen: Ausnahmen):
        self.a = ausnahmen
        self.abweichungen: list[dict] = []
        self.erwartet: list[dict] = []
        self.n = 0

    def melde(self, bereich: str, schluessel: str, feld: str | None,
              art: str, referenz=None, aktuell=None) -> None:
        abw = {"bereich": bereich, "schluessel": schluessel, "feld": feld,
               "art": art, "referenz": referenz, "aktuell": aktuell}
        regel = self.a.treffer(abw)
        if regel:
            abw["begruendung"] = regel["begruendung"]
            self.erwartet.append(abw)
        else:
            self.abweichungen.append(abw)

    def tabelle(self, bereich: str, ref: list[dict], ist: list[dict],
                schluesselfelder: list[str]) -> None:
        r = _schluessel(ref, schluesselfelder)
        i = _schluessel(ist, schluesselfelder)
        for k in sorted(set(r) - set(i)):
            self.n += 1
            self.melde(bereich, k, None, "fehlt", referenz="Zeile vorhanden",
                       aktuell="Zeile fehlt")
        for k in sorted(set(i) - set(r)):
            self.n += 1
            self.melde(bereich, k, None, "zusaetzlich", referenz="keine Zeile",
                       aktuell="Zeile vorhanden")
        for k in sorted(set(r) & set(i)):
            for feld in sorted(set(r[k]) | set(i[k])):
                a, b = r[k].get(feld), i[k].get(feld)
                if isinstance(a, (dict, list)) or isinstance(b, (dict, list)):
                    # Phasen, Reanimationen, Spur: hineinsteigen statt als
                    # Ganzes vergleichen. Sonst meldet eine um einen Punkt
                    # verschobene Spur „ein Feld anders" und sagt nicht, wo.
                    self._eingebettet(bereich, k, feld, a, b)
                else:
                    self.n += 1
                    if a != b:
                        self.melde(bereich, k, feld, "wert", a, b)

    def _eingebettet(self, bereich: str, schluessel: str, feld: str, a, b) -> None:
        unter = Vergleich(self.a)
        unter.baum(bereich, a, b, feld)
        self.n += unter.n
        # DAS FELD BLEIBT DAS BLATT, nicht der Sammelname. Es wurde hier
        # zuerst auf den Sammelnamen ('refs', 'track') umgeschrieben — mit der
        # Folge, dass eine Ausnahmeregel gegen das BLATT geprueft, im Bericht
        # aber der Sammelname gezeigt wurde. Wer dann eine Regel nach dem
        # Bericht schreibt, schreibt eine, die nie greift. Der Sammelname
        # steht ohnehin am Anfang des Pfades im Schluessel.
        for x in unter.abweichungen:
            x["schluessel"] = f"{schluessel} → {x['schluessel']}"
            self.abweichungen.append(x)
        for x in unter.erwartet:
            x["schluessel"] = f"{schluessel} → {x['schluessel']}"
            self.erwartet.append(x)

    def text(self, bereich: str, ref: str, ist: str) -> None:
        """Zeilenweiser Vergleich eines Textes (LIESMICH.txt)."""
        zr, zi = ref.split("\r\n"), ist.split("\r\n")
        for nr in range(max(len(zr), len(zi))):
            self.n += 1
            a = zr[nr] if nr < len(zr) else None
            b = zi[nr] if nr < len(zi) else None
            if a != b:
                self.melde(bereich, f"Zeile {nr + 1}", None, "wert", a, b)

    def dateien(self, bereich: str, ref: dict, ist: dict) -> None:
        """Trackdateien: Bestand und Inhalt."""
        for k in sorted(set(ref) - set(ist)):
            self.n += 1
            self.melde(bereich, k, None, "fehlt", "Datei vorhanden", "Datei fehlt")
        for k in sorted(set(ist) - set(ref)):
            self.n += 1
            self.melde(bereich, k, None, "zusaetzlich", "keine Datei", "Datei vorhanden")
        for k in sorted(set(ref) & set(ist)):
            self.n += 1
            if ref[k] != ist[k]:
                self.melde(bereich, k, None, "wert",
                           f"{len(ref[k])} Zeichen", f"{len(ist[k])} Zeichen")

    def baum(self, bereich: str, ref, ist, pfad: str = "") -> None:
        """Freier Vergleich verschachtelter Strukturen (Backup).

        Fuer alles, was keine Tabelle mit Schluessel ist: Stammdaten,
        Kopfangaben, Phasenlisten innerhalb eines Einsatzes.
        """
        if isinstance(ref, dict) and isinstance(ist, dict):
            for k in sorted(set(ref) | set(ist)):
                p = f"{pfad}.{k}" if pfad else k
                if k not in ref:
                    self.n += 1
                    self.melde(bereich, p, k, "zusaetzlich", None, _kurz(ist[k]))
                elif k not in ist:
                    self.n += 1
                    self.melde(bereich, p, k, "fehlt", _kurz(ref[k]), None)
                else:
                    self.baum(bereich, ref[k], ist[k], p)
        elif isinstance(ref, list) and isinstance(ist, list):
            if len(ref) != len(ist):
                self.n += 1
                self.melde(bereich, pfad, "Anzahl", "wert", len(ref), len(ist))
            for nr in range(min(len(ref), len(ist))):
                self.baum(bereich, ref[nr], ist[nr], f"{pfad}[{nr}]")
        else:
            self.n += 1
            if ref != ist:
                self.melde(bereich, pfad, pfad.rsplit(".", 1)[-1], "wert",
                           _kurz(ref), _kurz(ist))


def _kurz(v, grenze: int = 120):
    s = json.dumps(v, ensure_ascii=False) if isinstance(v, (dict, list)) else v
    s = str(s)
    return s if len(s) <= grenze else s[:grenze] + " …"


# ------------------------------------------------------------------ Laeufe

def vergleiche_csv(r: dict, i: dict, a: Ausnahmen) -> Vergleich:
    v = Vergleich(a)
    for bereich in ("einsaetze", "diensttage", "ruhezeiten", "felder"):
        v.tabelle(bereich, r[bereich], i[bereich], SCHLUESSEL_CSV[bereich])
    v.text("liesmich", r["liesmich"], i["liesmich"])
    v.dateien("tracks", r["tracks"], i["tracks"])
    return v


def vergleiche_edbak(r: dict, i: dict, a: Ausnahmen) -> Vergleich:
    v = Vergleich(a)
    for bereich in ("missions", "rest_segments", "days"):
        v.tabelle(bereich, r.get(bereich, []), i.get(bereich, []),
                  SCHLUESSEL_EDBAK[bereich])
    # Alles ausserhalb der drei Tabellen: Kopf und Stammdaten.
    kopf_r = {k: val for k, val in r.items() if k not in SCHLUESSEL_EDBAK}
    kopf_i = {k: val for k, val in i.items() if k not in SCHLUESSEL_EDBAK}
    v.baum("kopf", kopf_r, kopf_i)
    return v


# --------------------------------------------------- Probe aufs Exempel
#
# EIN VERGLEICH, DER NICHTS MELDET, IST ZWEIDEUTIG: Entweder ist alles gleich,
# oder das Werkzeug schaut an der falschen Stelle hin. Die zweite Lesart laesst
# sich nur ausschliessen, indem man dem Werkzeug etwas hinlegt, das es finden
# MUSS -- und etwas, das es NICHT melden darf.
#
# Die letzte Probe jeder Liste ist deshalb eine Gegenprobe: Sie aendert genau
# das, was die Normalisierung wegnehmen soll. Meldet das Werkzeug sie, ist die
# Normalisierung wirkungslos; meldet es sie nicht, greift sie.

def _tief(x):
    return json.loads(json.dumps(x))


PROBEN_CSV = [
    ("Wert in einsaetze.csv",
     lambda d: d["einsaetze"][3].__setitem__("strecke_m", "999999"), True),
    ("Zeile in diensttage.csv entfernt",
     lambda d: d["diensttage"].pop(2), True),
    ("Zeile in ruhezeiten.csv ergänzt",
     lambda d: d["ruhezeiten"].append(dict(d["ruhezeiten"][0],
                                           beginn="2099-01-01T00:00:00+01:00")), True),
    ("Inhalt einer GPX-Datei geändert",
     lambda d: d["tracks"].__setitem__(sorted(d["tracks"])[0],
                                       d["tracks"][sorted(d["tracks"])[0]] + "<!-- -->"), True),
    ("Zeitraum in LIESMICH.txt geändert",
     lambda d: d.__setitem__("liesmich", d["liesmich"].replace(
         "Zeitraum: gesamter Zeitraum", "Zeitraum: 2026-01-01 bis 2026-12-31")), True),
    ("Feldbeschreibung in felder.csv geändert",
     lambda d: d["felder"][5].__setitem__("beschreibung", "verändert"), True),
    ("Zeitstempel in einer GPX-Datei geändert",
     lambda d: d["tracks"].__setitem__(
         sorted(d["tracks"])[0],
         re.sub(r"(<metadata><time>)[^<]*", r"\g<1>2099-01-01T00:00:00.000Z",
                d["tracks"][sorted(d["tracks"])[0]])), False),
    ("GEGENPROBE: interne Einsatz-ID geändert",
     lambda d: [z.__setitem__("einsatz_id", str(4711 + n))
                for n, z in enumerate(d["einsaetze"])], False),
    ("GEGENPROBE: Erzeugungszeitpunkt im LIESMICH geändert",
     lambda d: d.__setitem__("liesmich", re.sub(
         r"Erzeugt am: [^\r\n]*", "Erzeugt am: 01.01.2099 03:00 (Europe/Berlin)",
         d["liesmich"])), False),
    ("GEGENPROBE: App-Version im LIESMICH geändert",
     lambda d: d.__setitem__("liesmich", re.sub(
         r"App-Version: [^\r\n]*", "App-Version: Web 99.9.9", d["liesmich"])), False),
]

def _kennungen_verschieben(d: dict) -> None:
    """Vergibt jeder Diensttag-Kennung eine neue Zahl — und zieht die Verweise mit.

    Das ist genau der Fall, den ein Einspielen in ein anderes Konto erzeugt:
    dieselbe Struktur, andere Zahlen. Die Normalisierung ersetzt die Kennung
    durch ihre STELLE in der Liste; wenn sie taugt, faellt hier nichts auf.
    """
    neu = {}
    for n, tag in enumerate(d.get("days", [])):
        neu[tag["id"]] = 900000 + n * 7
        tag["id"] = neu[tag["id"]]
    for name in ("missions", "rest_segments"):
        for z in d.get(name, []):
            if z.get("day_id") in neu:
                z["day_id"] = neu[z["day_id"]]


def _papierkorb_zustand_aendern(d: dict) -> None:
    """Einen AKTIVEN Einsatz in den Papierkorb setzen (E-S1-15, Hinprobe).

    Die Normalisierung ersetzt den Zeitwert durch eine Marke, den ZUSTAND aber
    nicht. Ein Eintrag, der in der einen Datei aktiv und in der anderen
    geloescht ist, MUSS gemeldet werden — sonst belegt der Kreislauf nicht,
    dass ein Papierkorbeintrag als Papierkorbeintrag zurueckkommt.
    """
    for m in d.get("missions", []):
        if not m.get("deleted_at"):
            m["deleted_at"] = "2099-01-01 00:00:00"
            return
    raise RuntimeError("kein aktiver Einsatz in der Datei")


def _papierkorb_zeit_verschieben(d: dict) -> None:
    """Nur den ZEITPUNKT aller Papierkorbeintraege aendern (Gegenprobe).

    Genau das passiert bei jedem Einspielen (E-S1-03): Die Frist beginnt neu.
    Es darf NICHT gemeldet werden.
    """
    n = 0
    for name in ("missions", "rest_segments", "days"):
        for z in d.get(name, []):
            if z.get("deleted_at"):
                z["deleted_at"] = "2099-01-01 00:00:00"
                n += 1
    if not n:
        raise RuntimeError("kein Papierkorbeintrag in der Datei")


PROBEN_EDBAK = [
    ("Wert in missions",
     lambda d: d["missions"][2].__setitem__("distance_m", 424242), True),
    ("Phase eines Einsatzes entfernt",
     lambda d: d["missions"][0]["phases"].pop(), True),
    ("Einzelner Spurpunkt verschoben",
     lambda d: d["missions"][0]["track"][0].__setitem__(1, 0.0), True),
    ("Stammdatensatz entfernt",
     lambda d: d["stammdaten"]["transport_dests"].pop(), True),
    ("Diensttag entfernt", lambda d: d["days"].pop(), True),
    ("Zuordnung Einsatz → Diensttag vertauscht",
     lambda d: d["missions"][0].__setitem__("day_id", d["days"][-1]["id"]), True),
    ("Papierkorb-Zustand eines Einsatzes geändert", _papierkorb_zustand_aendern, True),
    ("created_at eines Einsatzes geändert",
     lambda d: d["missions"][0].__setitem__("created_at", "2099-01-01 00:00:00"), True),
    ("GEGENPROBE: created_at der Datei geändert",
     lambda d: d.__setitem__("created_at", "2099-01-01T00:00:00+00:00"), False),
    ("GEGENPROBE: Herkunftskonto geändert",
     lambda d: d.__setitem__("user", {"email": "wer@anders.example", "name": None}), False),
    ("GEGENPROBE: Loeschzeitpunkte verschoben", _papierkorb_zeit_verschieben, False),
    ("GEGENPROBE: alle Diensttag-Kennungen verschoben",
     _kennungen_verschieben, False),
]


def probelauf(roh_r: dict, roh_i: dict, art: str, ausnahmen: Ausnahmen) -> tuple[list, bool]:
    """Probe aufs Exempel — die Aenderung greift VOR der Normalisierung an.

    Das ist der Punkt: Eine Gegenprobe, die erst nach dem Normalisieren
    ansetzt, prueft die Normalisierung gar nicht. Sie wuerde in jedem Fall
    gemeldet und bewiese nur, dass der Vergleich Unterschiede findet — was
    die Hinproben schon sagen.
    """
    proben = PROBEN_CSV if art == "csv" else PROBEN_EDBAK
    normieren = normalisieren.archiv if art == "csv" else normalisieren.edbak
    fahre = vergleiche_csv if art == "csv" else vergleiche_edbak
    ref = normieren(roh_r)
    aus, alles_gut = [], True
    for name, aendern, erwartet_meldung in proben:
        kopie = _tief(roh_i)
        try:
            aendern(kopie)
        except Exception as e:                       # noqa: BLE001
            aus.append({"probe": name, "ergebnis": f"Probe nicht anwendbar: {e}",
                        "bestanden": False})
            alles_gut = False
            continue
        v = fahre(ref, normieren(kopie),
                  Ausnahmen({"name": ausnahmen.name, "beschreibung": "",
                             "regeln": ausnahmen.regeln}))
        gemeldet = len(v.abweichungen)
        ok = (gemeldet > 0) == erwartet_meldung
        alles_gut = alles_gut and ok
        aus.append({"probe": name,
                    "erwartet": "Meldung" if erwartet_meldung else "keine Meldung",
                    "gemeldet": gemeldet, "bestanden": ok})
    return aus, alles_gut


def bericht_markdown(v: Vergleich, art: str, ref: str, ist: str) -> str:
    z = [f"# Abweichungsbericht — {art}", "",
         f"Referenz: `{pathlib.Path(ref).name}`  ",
         f"Aktuell:  `{pathlib.Path(ist).name}`  ",
         f"Ausnahmeliste: `{v.a.name}`", "",
         f"- Einzelvergleiche: **{v.n}**",
         f"- unerklärte Abweichungen: **{len(v.abweichungen)}**",
         f"- erwartete Abweichungen: **{len(v.erwartet)}**", ""]
    if v.abweichungen:
        z += ["## Unerklärte Abweichungen", "",
              "| Bereich | Schlüssel | Feld | Art | Referenz | Aktuell |",
              "|---|---|---|---|---|---|"]
        for a in v.abweichungen[:200]:
            z.append(f"| {a['bereich']} | `{a['schluessel']}` | {a['feld'] or '—'} "
                     f"| {a['art']} | {_md(a['referenz'])} | {_md(a['aktuell'])} |")
        if len(v.abweichungen) > 200:
            z.append(f"\n… und {len(v.abweichungen) - 200} weitere.")
        z.append("")
    else:
        z += ["## Unerklärte Abweichungen", "", "Keine.", ""]

    if v.erwartet:
        nach_feld: dict = {}
        for a in v.erwartet:
            nach_feld.setdefault((a["bereich"], a["feld"], a["begruendung"]), 0)
            nach_feld[(a["bereich"], a["feld"], a["begruendung"])] += 1
        z += ["## Erwartete Abweichungen", "",
              "Nicht weggefiltert, sondern begründet — deshalb stehen sie hier "
              "mit Anzahl.", "",
              "| Bereich | Feld | Anzahl | Begründung |", "|---|---|---:|---|"]
        for (b, f, g), n in sorted(nach_feld.items(), key=lambda x: -x[1]):
            z.append(f"| {b} | {f or '(ganze Zeile)'} | {n} | {g} |")
        z.append("")

    unge = v.a.ungenutzte()
    if unge:
        z += ["## Ausnahmeregeln, die nicht gegriffen haben", "",
              "Entweder beschreiben sie etwas, das es nicht mehr gibt — dann "
              "gehören sie weg. Oder der Umlauf hat den Fall gar nicht "
              "berührt — dann prüft der Lauf weniger als gedacht.", ""]
        for r in unge:
            z.append(f"- `{r.get('bereich')}` / `{r.get('feld')}` — {r['begruendung']}")
        z.append("")
    return "\n".join(z)


def _md(v) -> str:
    if v is None:
        return "—"
    s = str(v).replace("|", "\\|").replace("\n", " ")
    return f"`{s[:80]}`" + (" …" if len(s) > 80 else "")


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("referenz")
    p.add_argument("aktuell")
    p.add_argument("--art", choices=["csv", "edbak"], required=True)
    p.add_argument("--passwort", default="nadokudemo0815",
                   help="Backup-Passwort der Referenzdatei")
    p.add_argument("--passwort-aktuell", default=None,
                   help="abweichendes Passwort der Vergleichsdatei")
    p.add_argument("--ausnahmen", default=None)
    p.add_argument("--bericht", default=None, help="Pfad für bericht.json/.md")
    p.add_argument("--testabweichung", action="store_true",
                   help="baut eine Abweichung ein und prüft, dass sie gemeldet wird")
    args = p.parse_args()

    daten = json.loads(pathlib.Path(args.ausnahmen).read_text(encoding="utf-8")) \
        if args.ausnahmen else None
    a = Ausnahmen(daten)

    if args.art == "csv":
        roh_r = lesen.lesen_archiv(args.referenz)
        roh_i = lesen.lesen_archiv(args.aktuell)
        r, i = normalisieren.archiv(roh_r), normalisieren.archiv(roh_i)
        v = vergleiche_csv(r, i, a)
    else:
        roh_r = lesen.lesen_edbak(args.referenz, args.passwort)
        roh_i = lesen.lesen_edbak(args.aktuell, args.passwort_aktuell or args.passwort)
        r, i = normalisieren.edbak(roh_r), normalisieren.edbak(roh_i)
        v = vergleiche_edbak(r, i, a)

    if args.testabweichung:
        proben, alles_gut = probelauf(roh_r, roh_i, args.art, a)
        print("Probe aufs Exempel")
        for p_ in proben:
            zeichen = "ok " if p_["bestanden"] else "FEHL"
            print(f"  [{zeichen}] {p_['probe']:<45} erwartet {p_.get('erwartet','—'):<14} "
                  f"gemeldet {p_.get('gemeldet','—')}")
        print(f"  → {sum(1 for x in proben if x['bestanden'])}/{len(proben)} bestanden")
        print()
        if not alles_gut:
            return 2

    md = bericht_markdown(v, args.art, args.referenz, args.aktuell)
    if args.bericht:
        ziel = pathlib.Path(args.bericht)
        ziel.with_suffix(".md").write_text(md, encoding="utf-8")
        ziel.with_suffix(".json").write_text(json.dumps(
            {"art": args.art, "referenz": args.referenz, "aktuell": args.aktuell,
             "ausnahmeliste": a.name, "einzelvergleiche": v.n,
             "abweichungen": v.abweichungen, "erwartet": v.erwartet,
             "ungenutzte_regeln": a.ungenutzte()},
            ensure_ascii=False, indent=1), encoding="utf-8")

    print(f"Art:                    {args.art}")
    print(f"Einzelvergleiche:       {v.n}")
    print(f"unerklärte Abweichungen:{len(v.abweichungen):>6}")
    print(f"erwartete Abweichungen: {len(v.erwartet):>6}")
    if a.ungenutzte():
        print(f"ungenutzte Regeln:      {len(a.ungenutzte()):>6}")
    if v.abweichungen:
        print()
        for x in v.abweichungen[:25]:
            print(f"  {x['bereich']}/{x['schluessel']} {x['feld'] or ''} "
                  f"[{x['art']}]: {_kurz(x['referenz'], 60)!r} -> "
                  f"{_kurz(x['aktuell'], 60)!r}")
        if len(v.abweichungen) > 25:
            print(f"  … und {len(v.abweichungen) - 25} weitere")
    return 1 if v.abweichungen else 0


if __name__ == "__main__":
    sys.exit(main())
