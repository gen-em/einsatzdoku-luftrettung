#!/usr/bin/env python3
"""Strassengeometrie fuer die Bodeneinsaetze holen (E-P1-03).

EINMALIGER ABRUF, ERGEBNIS INS REPO. Der Generator laeuft danach OFFLINE:
Er liest die hier abgelegten GeoJSON-Dateien und braucht kein Netz. Das ist
der Kern von E-P1-03 -- ein Referenzdatensatz, dessen Erzeugung von einem
fremden Dienst abhaengt, ist genau dann nicht mehr reproduzierbar, wenn
dieser Dienst sich aendert oder verschwindet.

Geroutet werden AUSSCHLIESSLICH die Bodeneinsaetze. Lufttracks entstehen
geometrisch -- ein Hubschrauber folgt keiner Strasse.

ENTDOPPELT UEBER DAS KOORDINATENPAAR. Viele Teilstuecke wiederholen sich
(Wache -> Klinik faehrt derselbe Wagen zwanzigmal). Der Dateiname ist
deshalb der Hash des gerundeten Paares, nicht die Einsatzkennung: Gleiche
Strecke, gleiche Datei, ein Abruf. `routen_soll.json` haelt fest, welches
Teilstueck zu welcher Datei gehoert.

Aufruf:  python3 routen_holen.py           (holt, was fehlt)
         python3 routen_holen.py --neu     (holt alles erneut)

Quelle: OSRM-Demoserver (https://router.project-osrm.org), Profil 'driving'.
"""
from __future__ import annotations

import hashlib
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
PAUSE = 1.1


def schluessel(von, nach) -> str:
    roh = f"{von[0]:.5f},{von[1]:.5f}->{nach[0]:.5f},{nach[1]:.5f}"
    return hashlib.sha256(roh.encode()).hexdigest()[:16]


def teilstuecke() -> list[dict]:
    """Alle Fahrstrecken, die der Datensatz braucht — Einsatz UND Rueckweg.

    Der Rueckweg gehoert in das Ruhe-Segment nach dem Einsatz (siehe
    wegpunkte.tagesablauf); ohne ihn faehrt das NEF von der Klinik nach Hause,
    ohne dass dafuer eine Strasse vorliegt.
    """
    stamm = json.loads((QUELLE / "stammdaten.json").read_text("utf-8"))
    standorte = {s["name"]: s for s in stamm["standorte"]}
    auftraege: list[dict] = []
    for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        if d["dienst"]["art"] != "ground":
            continue
        for s in wegpunkte.tagesablauf(d["dienst"], d["einsaetze"],
                                       d["ruhesegmente"], standorte):
            koords = s["wegpunkte"]
            for i in range(len(koords) - 1):
                von, nach = koords[i], koords[i + 1]
                auftraege.append({
                    "dienst": d["kennung"], "art": s["art"], "client_ref": s["ref"],
                    "abschnitt": i, "von": list(von), "nach": list(nach),
                    "datei": f"strecke_{schluessel(von, nach)}.geojson",
                })
    return auftraege


def hole(von, nach) -> dict:
    paar = f"{von[1]},{von[0]};{nach[1]},{nach[0]}"
    with urllib.request.urlopen(OSRM.format(paar), timeout=90) as r:
        antwort = json.loads(r.read().decode("utf-8"))
    if antwort.get("code") != "Ok" or not antwort.get("routes"):
        raise RuntimeError(f"OSRM antwortet {antwort.get('code')!r} für {paar}")
    route = antwort["routes"][0]
    return {
        "type": "Feature",
        "geometry": route["geometry"],
        "properties": {
            "quelle": "OSRM router.project-osrm.org, Profil driving",
            "von": list(von), "nach": list(nach),
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

    eindeutig: dict[str, dict] = {}
    for a in auftraege:
        eindeutig.setdefault(a["datei"], a)

    geholt = vorhanden = 0
    for datei, a in sorted(eindeutig.items()):
        ziel = HIER / datei
        if ziel.exists() and not neu:
            vorhanden += 1
            continue
        merkmal = hole(a["von"], a["nach"])
        ziel.write_text(json.dumps(merkmal, ensure_ascii=False, indent=1) + "\n", "utf-8")
        geholt += 1
        e = merkmal["properties"]
        print(f"  {datei}  {e['punkte']:4d} Punkte  {e['distanz_m']/1000:6.1f} km  "
              f"{e['dauer_s']/60:5.1f} min")
        time.sleep(PAUSE)

    print(f"\n{len(auftraege)} Teilstücke, davon {len(eindeutig)} verschieden "
          f"({len(auftraege) - len(eindeutig)} Wiederholungen gespart).")
    print(f"{geholt} geholt, {vorhanden} bereits vorhanden.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
