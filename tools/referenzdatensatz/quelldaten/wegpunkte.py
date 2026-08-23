"""Wegpunkte einer Route auf Koordinaten aufloesen.

EINE STELLE FUER ZWEI LESER. Das Pruefskript (pruefen.py) und der Generator
(../generator/) muessen dieselbe Antwort geben, sonst prueft das eine etwas
anderes, als das andere erzeugt. Deshalb steht die Aufloesung hier und nicht
zweimal.

DIE TRENNUNG, DIE DAHINTER STECKT. Trackpunkte und Phasenkoordinaten liegen
in der Anwendung im KLARTEXT (Tabellen `track_points` und `mission_phases`);
verschluesselt ist die ADRESSE des Einsatzorts (`pat_blob.loc.addr`). Ein
Einsatz ohne geschuetzte Angaben hat deshalb sehr wohl eine Spur — sie kommt
dann aus `spur`, nicht aus `geschuetzt`.

Wegpunkte:
  basis        Standortkoordinate; bei einem Standort ohne Koordinaten der
               `spur_ausgangspunkt` des Dienstes (E-P1-02: Der Standort
               Talwang fuehrt bewusst keine Koordinaten)
  start        manueller Abfahrtort, `geschuetzt.start` (nur start_src='manual')
  ort          Einsatzort: `spur.ort`, sonst `geschuetzt.loc`
  ziel         Transportziel: `spur.ziel`, sonst `felder.dest_lat/dest_lon`
  ort_vorher   Einsatzort des VORIGEN Einsatzes (start_src='prev_site')
  ziel_vorher  Transportziel des VORIGEN Einsatzes (start_src='prev_dest')
"""
from __future__ import annotations

import math

R_ERDE = 6371000.0


def abstand_m(a_lat: float, a_lon: float, b_lat: float, b_lon: float) -> float:
    """Haversine — dieselbe Formel, mit der die Uhr `distance_m` bildet.

    Sie steht HIER und nicht in gelaende.py, weil auch die Quelldaten sie
    brauchen (Einsatzort in erreichbarer Entfernung). Zwei Formeln fuer
    denselben Abstand liefen frueher oder spaeter auseinander.
    """
    p1, p2 = math.radians(a_lat), math.radians(b_lat)
    dp = p2 - p1
    dl = math.radians(b_lon - a_lon)
    h = math.sin(dp / 2) ** 2 + math.cos(p1) * math.cos(p2) * math.sin(dl / 2) ** 2
    return 2 * R_ERDE * math.asin(math.sqrt(h))


def _koord(x):
    if not x:
        return None
    lat, lon = x.get("lat"), x.get("lon")
    return None if lat is None or lon is None else (float(lat), float(lon))


def basis_von(dienst: dict, standorte: dict) -> tuple[float, float] | None:
    return _koord(dienst.get("spur_ausgangspunkt")) or _koord(standorte.get(dienst["standort"]))


def ort_von(einsatz: dict) -> tuple[float, float] | None:
    spur = einsatz.get("spur") or {}
    return _koord(spur.get("ort")) or _koord((einsatz.get("geschuetzt") or {}).get("loc"))


def ziel_von(einsatz: dict) -> tuple[float, float] | None:
    spur = einsatz.get("spur") or {}
    if _koord(spur.get("ziel")):
        return _koord(spur["ziel"])
    f = einsatz.get("felder") or {}
    return _koord({"lat": f.get("dest_lat"), "lon": f.get("dest_lon")})


def aufloesen(dienst: dict, einsatz: dict, vorheriger: dict | None,
              standorte: dict) -> list[tuple[str, tuple[float, float] | None]]:
    """Liste (Wegpunktname, Koordinate) — Koordinate None heisst: loest nicht auf."""
    ergebnis = []
    for w in (einsatz.get("route") or []):
        if w == "basis":
            k = basis_von(dienst, standorte)
        elif w == "start":
            k = _koord((einsatz.get("geschuetzt") or {}).get("start"))
        elif w == "ort":
            k = ort_von(einsatz)
        elif w == "ziel":
            k = ziel_von(einsatz)
        elif w == "ort_vorher":
            k = ort_von(vorheriger) if vorheriger else None
        elif w == "ziel_vorher":
            k = ziel_von(vorheriger) if vorheriger else None
        else:
            k = None
        ergebnis.append((w, k))
    return ergebnis


def tagesablauf(dienst: dict, einsaetze: list, ruhesegmente: list,
                standorte: dict) -> list[dict]:
    """Der Dienst als zeitliche Folge — mit der Position, an der jedes Stueck
    beginnt und endet.

    WOFUER. Nach einem Einsatz beginnt die Uhr sofort ein Ruhe-Segment
    (Model.mc, `_endMission` -> `_startRestSegment`). Steht das Fahrzeug dann
    nicht an seinem Standort, sondern an der Zielklinik, gehoert der RUECKWEG
    in dieses Ruhe-Segment und nicht mehr zum Einsatz. Wer das anders
    modelliert, muss den Rueckweg in die Spanne zwischen Uebergabe und Endzeit
    pressen -- und erhaelt Rueckfluege mit 666 km/h.

    Diese Ableitung brauchen zwei: der Generator (Spuren) und der Routenabruf
    (welche Fahrstrecke ueberhaupt gebraucht wird). Deshalb steht sie hier und
    nicht zweimal.

    Rueckgabe je Stueck: {art, ref, beginn, ende, von, nach} -- `von` und
    `nach` sind Koordinaten oder None.
    """
    basis = basis_von(dienst, standorte)
    stuecke = []
    for e in einsaetze:
        stuecke.append({"art": "einsatz", "obj": e, "beginn": e["beginn"],
                        "ende": e["ende"] or dienst["ende"]})
    for r in ruhesegmente:
        stuecke.append({"art": "ruhe", "obj": r, "beginn": r["beginn"],
                        "ende": r["ende"] or dienst["ende"]})
    stuecke.sort(key=lambda s: s["beginn"])

    # --- 1. Einsaetze aufloesen: wo faengt jeder an, wo hoert er auf ------
    vorheriger_einsatz = None
    for s in stuecke:
        if s["art"] != "einsatz":
            continue
        e = s["obj"]
        koords = [k for _, k in aufloesen(dienst, e, vorheriger_einsatz, standorte) if k]
        s["wegpunkte"] = koords
        s["von"] = koords[0] if koords else basis
        s["nach"] = koords[-1] if koords else basis
        vorheriger_einsatz = e

    # --- 2. Ruhe-Segmente sind die Brücken dazwischen --------------------
    #
    # Ein Ruhe-Segment fuehrt von dort, wo das Fahrzeug steht, dorthin, wo der
    # NAECHSTE Einsatz beginnt. Meistens ist das der Standort -- aber nicht
    # immer: Bei `start_src = prev_dest` wird die Besatzung alarmiert, waehrend
    # sie noch an der Klinik steht, und faehrt gar nicht erst heim. Wer das
    # Ruhe-Segment blind zum Standort fuehren laesst, schickt sie in sechs
    # Minuten zwanzig Kilometer weit und wieder zurueck.
    position = basis
    for i, s in enumerate(stuecke):
        if s["art"] == "einsatz":
            position = s["nach"]
            continue
        naechster = next((x for x in stuecke[i + 1:] if x["art"] == "einsatz"), None)
        s["von"] = position
        s["nach"] = naechster["von"] if naechster else basis
        s["wegpunkte"] = ([s["von"], s["nach"]] if s["von"] != s["nach"] else [s["von"]])
        position = s["nach"]

    for s in stuecke:
        s["ref"] = s["obj"]["client_ref"] or s["obj"].get("quell_kennung")
    return stuecke
