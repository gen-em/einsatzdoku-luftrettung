#!/usr/bin/env python3
"""Fahrzeiten-Tafel fuer die Bodeneinsaetze holen (einmalig).

WOZU. `aufbauen.py` waehlt den Einsatzort danach aus, ob er in der Zeit, die
die Phasen dafuer vorsehen, ueberhaupt zu erreichen ist. Fuer die Luft genuegt
dafuer die Luftlinie. Auf der Strasse nicht: Im Voralpenland liegt ein Ort
15 km Luftlinie entfernt und 40 km Fahrstrecke -- das Tal geht in die andere
Richtung. Mit der Luftlinie gerechnet entstanden Fahrten mit 205 km/h.

Diese Tafel haelt die ECHTE Fahrzeit zwischen den Punkten, die der Datensatz
verbindet: vom Standort zu jedem befahrbaren Ort und von dort zu jeder
Zielklinik des Standorts. Sie wird einmal geholt und eingecheckt; danach
laeuft alles offline.

Aufruf:  python3 fahrzeiten_holen.py [--neu]
"""
from __future__ import annotations

import json
import pathlib
import sys
import time
import urllib.request

HIER = pathlib.Path(__file__).resolve().parent
QUELLE = HIER.parent.parent / "quelldaten"
sys.path.insert(0, str(QUELLE))
import katalog  # noqa: E402

OSRM = "https://router.project-osrm.org/route/v1/driving/{}?overview=false"
TAFEL = HIER / "fahrzeiten.json"
PAUSE = 1.1


def schluessel(von, nach) -> str:
    return f"{von[0]:.4f},{von[1]:.4f}>{nach[0]:.4f},{nach[1]:.4f}"


def hole(von, nach) -> dict:
    paar = f"{von[1]},{von[0]};{nach[1]},{nach[0]}"
    with urllib.request.urlopen(OSRM.format(paar), timeout=90) as r:
        a = json.loads(r.read().decode("utf-8"))
    if a.get("code") != "Ok" or not a.get("routes"):
        raise RuntimeError(f"OSRM antwortet {a.get('code')!r} für {paar}")
    r0 = a["routes"][0]
    return {"distanz_m": round(r0["distance"], 1), "dauer_s": round(r0["duration"], 1)}


def main() -> int:
    stamm = json.loads((QUELLE / "stammdaten.json").read_text("utf-8"))
    tafel = json.loads(TAFEL.read_text("utf-8")) if TAFEL.exists() and "--neu" not in sys.argv else {}

    paare: list[tuple] = []
    for standort in stamm["standorte"]:
        rm = [r for r in stamm["rettungsmittel"]
              if r["standort"] == standort["name"] and r["art"] == "ground"]
        if not rm:
            continue
        # Der Standort Talwang fuehrt keine Koordinaten; der Spur-Ausgangspunkt
        # steht an den Diensten. Er ist fuer alle derselbe.
        ausgang = None
        for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
            d = json.loads(pfad.read_text("utf-8"))
            if d["dienst"]["standort"] == standort["name"] and d["dienst"].get("spur_ausgangspunkt"):
                sp = d["dienst"]["spur_ausgangspunkt"]
                ausgang = (sp["lat"], sp["lon"])
                break
        basis = ausgang or (standort["lat"], standort["lon"])
        kliniken = [(k["lat"], k["lon"]) for k in stamm["zielkliniken"]
                    if k["standort"] == standort["name"] and k["lat"] is not None]
        orte = [(a, b) for _, a, b in katalog.ORTE_BODEN]
        for o in orte:
            paare.append((basis, o))
            for k in kliniken:
                paare.append((o, k))

    geholt = 0
    for von, nach in paare:
        s = schluessel(von, nach)
        if s in tafel:
            continue
        tafel[s] = hole(von, nach)
        geholt += 1
        time.sleep(PAUSE)
        if geholt % 20 == 0:
            print(f"  {geholt} geholt …")
            TAFEL.write_text(json.dumps(tafel, ensure_ascii=False, indent=0) + "\n", "utf-8")

    TAFEL.write_text(json.dumps(tafel, ensure_ascii=False, indent=0) + "\n", "utf-8")
    print(f"{len(paare)} Paare, {geholt} neu geholt, Tafel hat {len(tafel)} Einträge.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
