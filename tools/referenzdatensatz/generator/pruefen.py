#!/usr/bin/env python3
"""Erzeugnisse des Generators pruefen (Arbeitspaket B2).

Vier Pruefungen:

  1. VERTRAG    -- jede einzelne Ingest-Anfrage gegen docs/JSON-Vertrag.md 3.2.
                   ALLE, nicht eine Stichprobe: Eine Stichprobe beantwortet die
                   Frage nicht, ob der Datensatz vertragskonform IST, sondern
                   nur, ob die gezogenen Stuecke es sind.
  2. FOLGE      -- Teilstuecke eines Pakets luecken- und ueberlappungsfrei
                   (`seq_from` des naechsten = seq_from + Punktzahl davor)
  3. KRYPTO     -- Chiffretext entschluesselt zum Quell-Klartext zurueck
  4. SPUR       -- Spuren liegen im Zeitfenster ihres Einsatzes, Hoehen und
                   Geschwindigkeiten sind plausibel
  5. CSV        -- die Importdatei gegen die Parser der Anwendung: jede
                   Kopfzeile ist eine dem Profil `export_csv_v1` bekannte
                   Spalte, und jeder Wert genuegt der Regel des Parsers, der
                   fuer seine Spalte hinterlegt ist. Die Regeln werden aus
                   server/assets/import*.js GELESEN, nicht abgeschrieben --
                   sonst prueft diese Datei ihre eigene Annahme statt die
                   Anwendung. Anlass: Der Generator schrieb den Zonenversatz
                   als "+0200"; `PARSERS.isoTs` verlangt "+02:00" und verwarf
                   die Endzeit und alle acht Phasenzeiten stillschweigend --
                   der Import meldete trotzdem "0 Fehler".

Aufruf:  python3 pruefen.py
Rueckgabe: 0 = in Ordnung, 1 = Befunde
"""
from __future__ import annotations

import csv
import json
import os
import pathlib
import re
import sys
from datetime import datetime, timezone

import krypto

HIER = pathlib.Path(__file__).resolve().parent
AUS = HIER / "ausgabe"
QUELLE = HIER.parent / "quelldaten"

REA_TYPEN = {"zugang", "beginn", "adrenalin", "rhythmuskontrolle", "defibrillation",
             "intubation", "amiodaron", "sonographie", "rosc", "tod"}
MAX_BODY = 512 * 1024
TS_RE = re.compile(r"^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$")


class Lauf:
    def __init__(self):
        self.befunde: list[str] = []
        self.n = 0

    def pruefe(self, ok: bool, text: str) -> None:
        self.n += 1
        if not ok:
            self.befunde.append(text)


def kalendertag(s: str) -> bool:
    try:
        datetime.strptime(s[:10], "%Y-%m-%d")
        return True
    except ValueError:
        return False


def main() -> int:
    lauf = Lauf()
    if not AUS.exists():
        print("ausgabe/ fehlt — erst erzeugen.py laufen lassen.")
        return 1

    # ---- 1./2. Vertrag und Folge der Teilstuecke -------------------------
    dateien = sorted(AUS.glob("payloads/*/*.json"))
    folge: dict[str, int] = {}
    punkte_gesamt = 0
    for pfad in dateien:
        roh = pfad.read_bytes()
        b = json.loads(roh.decode("utf-8"))
        wo = f"{pfad.parent.name}/{pfad.name}"

        lauf.pruefe(len(roh) <= MAX_BODY, f"{wo}: Body {len(roh)} Bytes über 512 KB")
        lauf.pruefe(b["kind"] in ("mission", "rest_segment"), f"{wo}: unbekanntes kind")
        ref = b["client_ref"]
        lauf.pruefe(len(ref) <= 64 and " " not in ref, f"{wo}: client_ref unbrauchbar")
        lauf.pruefe(len(b["day_ref"]) <= 64 and " " not in b["day_ref"],
                    f"{wo}: day_ref unbrauchbar")
        lauf.pruefe(kalendertag(b["day"]), f"{wo}: day {b['day']!r} ist kein Kalendertag")
        lauf.pruefe(bool(TS_RE.match(b["started_at"])), f"{wo}: started_at nicht ISO-Z")
        if b["final"]:
            lauf.pruefe(b["ended_at"] is not None and bool(TS_RE.match(b["ended_at"])),
                        f"{wo}: final=true ohne brauchbares ended_at")
        else:
            lauf.pruefe(b["ended_at"] is None,
                        f"{wo}: ended_at gesetzt, obwohl final=false (Vertrag 3)")

        t = b["track"]
        lauf.pruefe(isinstance(t["points"], list),
                    f"{wo}: track.points ist keine Liste (Vertrag 3)")
        lauf.pruefe(len(t["points"]) <= 500,
                    f"{wo}: {len(t['points'])} Punkte über der Chunk-Grenze 500")
        for p in t["points"]:
            lauf.pruefe(isinstance(p, list) and len(p) == 4, f"{wo}: Trackpunkt nicht [lat,lon,ele,ts]")
            lauf.pruefe(-90 <= p[0] <= 90, f"{wo}: lat {p[0]} außerhalb")
            lauf.pruefe(-180 <= p[1] <= 180, f"{wo}: lon {p[1]} außerhalb")
        punkte_gesamt += len(t["points"])

        erwartet = folge.get(ref, 0)
        lauf.pruefe(t["seq_from"] == erwartet,
                    f"{wo}: seq_from {t['seq_from']}, erwartet {erwartet} — Lücke oder Überlappung")
        folge[ref] = t["seq_from"] + len(t["points"])

        if b["kind"] == "mission":
            lauf.pruefe(len(b.get("phases", [])) <= 500, f"{wo}: über 500 Phasen")
            for ph in b.get("phases", []):
                lauf.pruefe(2 <= ph["phase"] <= 9,
                            f"{wo}: Phase {ph['phase']} außerhalb von 2…9")
                lauf.pruefe(bool(TS_RE.match(ph["at"])), f"{wo}: Phasenzeit nicht ISO-Z")
                if ph.get("lat") is not None:
                    lauf.pruefe(-90 <= ph["lat"] <= 90, f"{wo}: Phasen-lat außerhalb")
                    lauf.pruefe(-180 <= ph["lon"] <= 180, f"{wo}: Phasen-lon außerhalb")
            sitzungen = b.get("resus_sessions", [])
            lauf.pruefe(len(sitzungen) <= 20, f"{wo}: über 20 Reanimationssitzungen")
            for s in sitzungen:
                lauf.pruefe(len(s["events"]) <= 200, f"{wo}: über 200 Ereignisse")
                for ev in s["events"]:
                    lauf.pruefe(ev["type"] in REA_TYPEN,
                                f"{wo}: unbekannte Reanimationsart {ev['type']!r}")
            lauf.pruefe(isinstance(b.get("distance_m"), int) and b["distance_m"] >= 0,
                        f"{wo}: distance_m unbrauchbar")
            lauf.pruefe(isinstance(b.get("ascent_m"), int) and b["ascent_m"] >= 0,
                        f"{wo}: ascent_m unbrauchbar")

    # ---- Jedes Paket vollstaendig gesendet -------------------------------
    #
    # NICHT "alles ist final". Ein Einsatz und ein Ruhe-Segment duerfen
    # unabgeschlossen bleiben -- das ist im Datensatz ein Pruefall (D09 und
    # D16, Matrixzeilen "nicht abgeschlossener Einsatz" und "nicht
    # abgeschlossenes Segment"). Die Pruefung fragt deshalb die QUELLE, ob das
    # Paket abgeschlossen sein sollte, statt es pauschal zu verlangen. Eine
    # Pruefung, die den Pruefall des Datensatzes als Fehler meldet, wird beim
    # ersten Mal weggeklickt und danach nie wieder gelesen.
    soll_final: dict[str, bool] = {}
    for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        for e in d["einsaetze"]:
            if e["client_ref"]:
                soll_final[e["client_ref"]] = bool(e["final"])
        for r in d["ruhesegmente"]:
            soll_final[r["client_ref"]] = bool(r["final"])

    plan = json.loads((AUS / "sendeplan.json").read_text("utf-8"))
    letzte = {}
    for e in plan:
        letzte[e["ref"]] = e
    offen_erwartet = 0
    for ref, e in letzte.items():
        erwartet = soll_final.get(ref, True)
        if not erwartet:
            offen_erwartet += 1
        lauf.pruefe(e["final"] == erwartet,
                    f"{ref}: letzte Anfrage final={e['final']}, erwartet {erwartet}")

    # ---- 3. Krypto: Rundlauf ---------------------------------------------
    ck = os.urandom(32).hex()
    formulare = sorted((AUS / "formular").glob("*.json"))
    rundlaeufe = 0
    for pfad in formulare:
        f = json.loads(pfad.read_text("utf-8"))
        g = f.get("geschuetzt")
        if not g:
            continue
        chiffre = krypto.pat_blob(g, ck)
        if chiffre is None:
            continue
        lauf.pruefe(chiffre.startswith("edk1:"), f"{pfad.name}: Chiffretext ohne Formatkennung")
        zurueck = json.loads(krypto.entschluesseln(chiffre, ck))
        for schluessel in ("dx", "age", "dob", "mission_no", "site_desc"):
            wert = g.get(schluessel)
            if wert in (None, ""):
                lauf.pruefe(schluessel not in zurueck,
                            f"{pfad.name}: leeres {schluessel} steht im Chiffretext")
            else:
                lauf.pruefe(zurueck.get(schluessel) == wert,
                            f"{pfad.name}: {schluessel} kommt verändert zurück")
        if (g.get("loc") or {}).get("addr"):
            lauf.pruefe(zurueck["loc"]["addr"] == g["loc"]["addr"],
                        f"{pfad.name}: Einsatzort kommt verändert zurück")
        rundlaeufe += 1

    # ---- 4. Spuren im Zeitfenster ihres Einsatzes ------------------------
    spuren = 0
    for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        refs = {e["client_ref"]: e for e in d["einsaetze"] if e["client_ref"]}
        for datei in sorted(AUS.glob(f"payloads/{d['kennung']}/*.json")):
            b = json.loads(datei.read_text("utf-8"))
            e = refs.get(b["client_ref"])
            if not e or not b["track"]["points"]:
                continue
            von = datetime.strptime(b["started_at"], "%Y-%m-%dT%H:%M:%SZ").replace(
                tzinfo=timezone.utc).timestamp()
            bis = (datetime.strptime(b["ended_at"], "%Y-%m-%dT%H:%M:%SZ").replace(
                tzinfo=timezone.utc).timestamp() if b["ended_at"] else None)
            for p in b["track"]["points"]:
                lauf.pruefe(p[3] >= von - 1, f"{datei.name}: Spurpunkt vor dem Einsatzbeginn")
                if bis:
                    lauf.pruefe(p[3] <= bis + 1, f"{datei.name}: Spurpunkt nach dem Einsatzende")
                lauf.pruefe(p[2] is None or -500 <= p[2] <= 5000,
                            f"{datei.name}: Höhe {p[2]} unplausibel")
            spuren += 1

    # ---- 5. Geschwindigkeiten und Hoehen ---------------------------------
    #
    # DIE PRUEFUNG, DIE ES OHNE EINEN BEFUND NICHT GAEBE. Der erste Generator
    # waehlte den Einsatzort frei aus dem Katalog, waehrend die Phasen die
    # Anfahrtszeit vorgaben -- dabei entstanden Fluege mit ueber 380 km/h und
    # ein NEF auf 2100 m Hoehe. Beides sah in keiner Einzelpruefung auffaellig
    # aus: Jeder Punkt lag im gueltigen Bereich, jede Anfrage hielt den
    # Vertrag ein. Sichtbar wurde es erst, als jemand die Strecke durch die
    # Zeit teilte.
    import gelaende  # noqa: PLC0415  (nur hier gebraucht)
    spuren_je_ref: dict[str, list] = {}
    art_je_ref: dict[str, str] = {}
    dienstart = {}
    for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        dienstart[d["kennung"]] = d["dienst"]["art"]
    for pfad in dateien:
        b = json.loads(pfad.read_text("utf-8"))
        # AUCH RUHE-SEGMENTE. Sie tragen seit dem Umbau den Rueckweg, und
        # genau dort lief der Rueckflug mit 346 km/h -- unbemerkt, weil die
        # Pruefung nur Einsaetze ansah. Eine Pruefung, die die Haelfte der
        # Spuren auslaesst, prueft die Haelfte.
        spuren_je_ref.setdefault(b["client_ref"], []).extend(b["track"]["points"])
        art_je_ref[b["client_ref"]] = dienstart[pfad.parent.name]

    GRENZE_KMH = {"air": 300.0, "ground": 140.0}
        # Die Bodengrenze liegt bei 1400 m, nicht tiefer: Im Allgaeu fuehren
    # Passstrassen tatsaechlich so hoch (Riedbergpass 1407 m). Sie liegt aber
    # auch nicht hoeher -- ein NEF auf 2100 m war der Fehler, den diese
    # Pruefung gefunden hat.
    GRENZE_HOEHE = {"air": 3500.0, "ground": 1400.0}
    geprueft = 0
    for ref, pts in spuren_je_ref.items():
        if len(pts) < 3:
            continue
        pts.sort(key=lambda p: p[3])
        art = art_je_ref[ref]
        schnellste = 0.0
        for a, b2 in zip(pts, pts[1:]):
            dt = b2[3] - a[3]
            if dt <= 0:
                continue
            v = gelaende.abstand_m(a[0], a[1], b2[0], b2[1]) / dt * 3.6
            schnellste = max(schnellste, v)
        lauf.pruefe(schnellste <= GRENZE_KMH[art],
                    f"{ref} ({art}): {schnellste:.0f} km/h über der Grenze "
                    f"{GRENZE_KMH[art]:.0f} km/h")
        hoechste = max(p[2] for p in pts if p[2] is not None)
        lauf.pruefe(hoechste <= GRENZE_HOEHE[art],
                    f"{ref} ({art}): {hoechste:.0f} m über der Grenze "
                    f"{GRENZE_HOEHE[art]:.0f} m")
        geprueft += 1

    # ---- 5. CSV gegen die Parser der Anwendung ------------------------------
    #
    # Gelesen wird aus server/assets/ — die Regeln werden NICHT abgeschrieben.
    # Eine abgeschriebene Regel prueft nur, ob der Generator mit sich selbst
    # einig ist; das war er auch, als er "+0200" schrieb.
    server = HIER.parent.parent.parent / "server" / "assets"
    profile_js = (server / "import_profiles.js").read_text(encoding="utf-8")
    import_js = (server / "import.js").read_text(encoding="utf-8")

    # Spalte -> Parserkette aus dem Profil `export_csv_v1`
    spalten_regel: dict[str, list[str]] = {}
    ohne_kommentar = re.sub(r"//[^\n]*", "", profile_js)
    for name, rest in re.findall(r"'([a-z0-9_]+)':\s*\{([^{}]*)\}", ohne_kommentar):
        kette = re.search(r"parse:\s*\[(.*?)\]", rest, re.S)
        spalten_regel[name] = ([x.strip().strip("'") for x in kette.group(1).split(",")]
                               if kette else [])
    # Zwei Spaltengruppen setzt import_profiles.js erst zur Laufzeit zusammen:
    # die Phasen (aus PHASE_SLUGS) und die Besatzung (aus CREW_ROLLEN, das die
    # Seite aus PHP mitbringt). Beide werden hier aus ihrer jeweiligen Quelle
    # nachgebildet, nicht abgeschrieben.
    from erzeugen import PHASE_SLUG      # eine Quelle für die Phasennamen
    for nr, slug in PHASE_SLUG.items():
        spalten_regel[f"phase_0{nr}_{slug}"] = ["isoTs"]
        spalten_regel[f"phase_0{nr}_lat"] = ["dezimal"]
        spalten_regel[f"phase_0{nr}_lon"] = ["dezimal"]
    db_php = (HIER.parent.parent.parent / "server" / "db.php").read_text(encoding="utf-8")
    rollen_block = re.search(r"const CREW_ROLES = \[(.*?)\];", db_php, re.S).group(1)
    rollen = re.findall(r"'([a-z0-9_]+)'\s*=>\s*\[", rollen_block)
    lauf.pruefe(len(rollen) >= 5, "CREW_ROLES nicht aus db.php lesbar")
    for r in rollen:
        spalten_regel[f"tag_crew_{r}"] = ["trim", "max:120"]
        spalten_regel[f"crew_{r}"] = ["trim", "max:120"]

    def js_regex(quelle: str, parser: str) -> re.Pattern | None:
        """Die erste Literal-Regex im Rumpf eines Parsers, nach Python uebersetzt."""
        m = re.search(r"\b%s: function \(v\) \{(.*?)\n        \}," % parser,
                      quelle, re.S)
        if not m:
            return None
        t = re.search(r"/\^(.+?)\$/\.test", m.group(1))
        return re.compile("^" + t.group(1).replace("\\/", "/") + "$") if t else None

    RE_ISO_TS = js_regex(import_js, "isoTs")
    RE_DEZIMAL = js_regex(import_js, "dezimal")
    RE_GANZZAHL = js_regex(import_js, "ganzzahl")
    RE_HHMM = re.compile(r"^(\d{1,2})\s*[:.]\s*(\d{2})(?:\s*[:.]\s*\d{2})?\s*$")
    BOOL_JN = {"j", "ja", "x", "1", "y", "yes", "n", "nein", "0", "-", "no"}
    # Zeichen, mit denen ein Tabellenprogramm eine Zelle als Formel liest
    # (assets/export.js, CSV_FORMELSTART) — inert nur mit vorangestelltem '.
    RE_FORMEL = re.compile(r"^[=+\-@\t\r]")
    RE_ZAHL = re.compile(r"^-?\d+(\.\d+)?$")

    lauf.pruefe(RE_ISO_TS is not None, "isoTs-Regex nicht aus import.js lesbar")
    lauf.pruefe(RE_DEZIMAL is not None, "dezimal-Regex nicht aus import.js lesbar")
    lauf.pruefe(RE_GANZZAHL is not None, "ganzzahl-Regex nicht aus import.js lesbar")

    csv_datei = AUS / "import" / "einsaetze.csv"
    zellen = 0
    with csv_datei.open(encoding="utf-8-sig", newline="") as fh:
        zeilen_csv = list(csv.DictReader(fh, delimiter=";"))
    kopf = list(zeilen_csv[0].keys()) if zeilen_csv else []

    for name in kopf:
        lauf.pruefe(name in spalten_regel,
                    f"CSV-Spalte '{name}' kennt das Profil export_csv_v1 nicht")

    for i, z in enumerate(zeilen_csv, start=2):
        for name, wert in z.items():
            regeln = spalten_regel.get(name, [])
            roh = (wert or "")
            if roh != "":
                zellen += 1
                if RE_FORMEL.match(roh) and not RE_ZAHL.match(roh):
                    lauf.pruefe(False,
                                f"CSV Zeile {i}, '{name}': beginnt mit einem "
                                f"Formelzeichen und ist nicht geschützt: {roh[:40]!r}")
            w = roh.strip()
            if w == "":
                continue
            if "isoTs" in regeln:
                lauf.pruefe(bool(RE_ISO_TS.match(w)),
                            f"CSV Zeile {i}, '{name}': isoTs verwirft {w!r} "
                            f"(erwartet ISO 8601 mit Zone, Versatz MIT Doppelpunkt)")
            elif "timeHHMM" in regeln:
                lauf.pruefe(bool(RE_HHMM.match(w)),
                            f"CSV Zeile {i}, '{name}': timeHHMM verwirft {w!r}")
            elif "dezimal" in regeln:
                lauf.pruefe(bool(RE_DEZIMAL.match(w.replace(" ", "").replace(",", "."))),
                            f"CSV Zeile {i}, '{name}': dezimal verwirft {w!r}")
            elif "ganzzahl" in regeln:
                lauf.pruefe(bool(RE_GANZZAHL.match(w.replace(" ", ""))),
                            f"CSV Zeile {i}, '{name}': ganzzahl verwirft {w!r}")
            elif "boolJN" in regeln:
                lauf.pruefe(w.lower() in BOOL_JN,
                            f"CSV Zeile {i}, '{name}': boolJN erkennt {w!r} nicht")

    # Kommt jedes Feld, das die Datei traegt, beim Server auch an? gruppiere()
    # kopiert nur, was in UEBERNAHME steht — eine Liste, die schon einmal
    # hinter EINFACHE_ZIELE zurueckgeblieben ist (Fund F-P1-H).
    def js_liste(name: str) -> list[str]:
        m = re.search(r"var %s = \[(.*?)\];" % name, import_js, re.S)
        if not m:
            return []
        roh = re.sub(r"//.*?$", "", m.group(1), flags=re.M)
        return [x.strip().strip("'") for x in roh.split(",") if x.strip()]

    einfache = js_liste("EINFACHE_ZIELE")
    lauf.pruefe(bool(einfache), "EINFACHE_ZIELE nicht aus import.js lesbar")
    abgeleitet = bool(re.search(r"var UEBERNAHME = EINFACHE_ZIELE", import_js))
    if abgeleitet:
        uebernahme = ([f for f in einfache if f not in ("day", "crew_override")]
                      + ["resources", "phases", "phasesLocal"])
    else:
        uebernahme = js_liste("UEBERNAHME")
    for f in einfache:
        if f in ("day", "crew_override"):
            continue
        lauf.pruefe(f in uebernahme,
                    f"import.js: '{f}' wird gelesen, aber von gruppiere() nicht "
                    f"weitergereicht — der Wert geht zwischen Prüftabelle und "
                    f"Nutzlast verloren")

    print(f"CSV-Zeilen:           {len(zeilen_csv)}")
    print(f"CSV-Spalten:          {len(kopf)}")
    print(f"CSV-Zellen belegt:    {zellen}")
    print(f"UEBERNAHME:           {'abgeleitet' if abgeleitet else 'von Hand geführt'}"
          f" ({len(uebernahme)} Felder)")

    print(f"Anfragen geprüft:     {len(dateien)}")
    print(f"Spuren auf Tempo/Höhe:{geprueft}")
    print(f"Trackpunkte darin:    {punkte_gesamt}")
    print(f"Pakete (Folge):       {len(folge)}")
    print(f"Krypto-Rundläufe:     {rundlaeufe}")
    print(f"Spuren im Zeitraum:   {spuren}")
    print(f"absichtlich offen:    {offen_erwartet} Pakete "
          f"(nicht abgeschlossener Einsatz und Ruhe-Segment)")
    print(f"Einzelprüfungen:      {lauf.n}")
    print()
    if lauf.befunde:
        print(f"BEFUNDE ({len(lauf.befunde)})")
        for b in lauf.befunde[:40]:
            print("  " + b)
        if len(lauf.befunde) > 40:
            print(f"  … und {len(lauf.befunde) - 40} weitere")
        return 1
    print("Keine Befunde.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
