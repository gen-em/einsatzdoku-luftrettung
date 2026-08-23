#!/usr/bin/env python3
"""Strassengeometrie fuer die Bodeneinsaetze holen (E-P1-03).

EINMALIGER ABRUF, ERGEBNIS INS REPO. Der Generator laeuft danach OFFLINE:
Er liest die hier abgelegten GeoJSON-Dateien und braucht kein Netz. Das ist
der Kern von E-P1-03 — ein Referenzdatensatz, dessen Erzeugung von einem
fremden Dienst abhaengt, ist zu dem Zeitpunkt nicht mehr reproduzierbar, an
dem dieser Dienst sich aendert oder verschwindet.

Geroutet werden AUSSCHLIESSLICH die Bodeneinsaetze. Lufttracks entstehen
geometrisch (Grosskreis mit Kurven, Geschwindigkeits- und Hoehenprofil) --
ein Hubschrauber folgt keiner Strasse.

Aufruf:  python3 routen_holen.py           (schreibt fehlende Routen)
         python3 routen_holen.py --neu     (holt alle erneut)

Quelle: OSRM-Demoserver (https://router.project-osrm.org), Profil 'driving'.
Der Dienst bittet um schonende Nutzung; das Skript wartet zwischen den
Anfragen und laeuft ohnehin nur, wenn eine Datei fehlt.
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
import wegpunkte  # noqa: E402

OSRM = "https://router.project-osrm.org/route/v1/driving/{}?overview=full&geometries=geojson"
PAUSE = 1.2


def teilstuecke() -> list[dict]:
    """Alle zu routenden Teilstuecke aus den Quelldaten ableiten."""
    stamm = json.loads((QUELLE / "stammdaten.json").read_text("utf-8"))
    standorte = {s["name"]: s for s in stamm["standorte"]}
    auftraege: list[dict] = []
    for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        if d["dienst"]["art"] != "ground":
            continue
        vorheriger = None
        for e in d["einsaetze"]:
            punkte = wegpunkte.aufloesen(d["dienst"], e, vorheriger, standorte)
            vorheriger = e
            koords = [k for _, k in punkte if k]
            for i in range(len(koords) - 1):
                auftraege.append({
                    "datei": f"{d['kennung']}_{e['client_ref']}_{i}.geojson",
                    "dienst": d["kennung"],
                    "client_ref": e["client_ref"],
                    "abschnitt": i,
                    "von": list(koords[i]),
                    "nach": list(koords[i + 1]),
                })
    return auftraege


def hole(von, nach) -> dict:
    paar = f"{von[1]},{von[0]};{nach[1]},{nach[0]}"
    with urllib.request.urlopen(OSRM.format(paar), timeout=60) as r:
        antwort = json.loads(r.read().decode("utf-8"))
    if antwort.get("code") != "Ok" or not antwort.get("routes"):
        raise RuntimeError(f"OSRM antwortet {antwort.get('code')!r} für {paar}")
    route = antwort["routes"][0]
    return {
        "type": "Feature",
        "geometry": route["geometry"],
        "properties": {
            "quelle": "OSRM router.project-osrm.org, Profil driving",
            "distanz_m": round(route["distance"], 1),
            "dauer_s": round(route["duration"], 1),
            "punkte": len(route["geometry"]["coordinates"]),
        },
    }


def main() -> int:
    neu = "--neu" in sys.argv
    auftraege = teilstuecke()
    (HIER / "routen_soll.json").write_text(
        json.dumps(auftraege, ensure_ascii=False, indent=2) + "\n", "utf-8")
    geholt = uebersprungen = 0
    for a in auftraege:
        ziel = HIER / a["datei"]
        if ziel.exists() and not neu:
            uebersprungen += 1
            continue
        merkmal = hole(a["von"], a["nach"])
        merkmal["properties"].update({k: a[k] for k in ("dienst", "client_ref", "abschnitt")})
        ziel.write_text(json.dumps(merkmal, ensure_ascii=False, indent=1) + "\n", "utf-8")
        geholt += 1
        print(f"  {a['datei']}: {merkmal['properties']['punkte']} Punkte, "
              f"{merkmal['properties']['distanz_m'] / 1000:.1f} km")
        time.sleep(PAUSE)
    print(f"\n{len(auftraege)} Teilstücke, {geholt} geholt, {uebersprungen} bereits vorhanden.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
