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
