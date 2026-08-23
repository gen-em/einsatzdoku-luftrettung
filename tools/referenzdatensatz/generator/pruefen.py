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

Aufruf:  python3 pruefen.py
Rueckgabe: 0 = in Ordnung, 1 = Befunde
"""
from __future__ import annotations

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
