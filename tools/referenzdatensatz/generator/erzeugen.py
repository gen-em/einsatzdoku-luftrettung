#!/usr/bin/env python3
"""Generator des Referenzdatensatzes (Arbeitspaket B2).

Erzeugt aus den Quelldaten (`../quelldaten/`) alles, was der Einspiellauf
braucht:

  payloads/     Ingest-Anfragen, vertragskonform in Teilstuecken, in der
                Reihenfolge, in der die Uhr sie senden wuerde
  sendeplan.json  Soll-Sendeplan: wann welche Anfrage faellig ist
                (Grundlage des Messprotokolls, E-P1-14 / R19)
  formular/     Daten fuer das Nachtragen ueber einsatz_form.php und fuer
                die von Hand angelegten Einsaetze — im KLARTEXT; das
                Verschluesseln passiert im Einspielskript mit dem
                Kontoschluessel (krypto.py)
  import/       einsaetze.csv im Format export_csv_v1
  gpx/          Sichtpruefformat, abgeleitet (E-P1-04)
  kennzahlen.json  Umfang in Zahlen

DETERMINISTISCH. Zweimal ausgefuehrt entsteht dasselbe. Der einzige
Zufallsanteil steckt in `spur.py` und ist ueber die Einsatzkennung gesaet.

DIE ZEITEN. Die Quelle steht in Ortszeit (siehe ../quelldaten/FORMAT.md),
der JSON-Vertrag verlangt UTC mit Z. Umgerechnet wird hier, an einer Stelle.
"""
from __future__ import annotations

import csv
import io
import json
import pathlib
import re
import sys
from datetime import datetime, timedelta, timezone
from zoneinfo import ZoneInfo

import krypto  # noqa: F401  (vom Einspielskript mitbenutzt; hier fuer den Selbsttest)
import spur

HIER = pathlib.Path(__file__).resolve().parent
QUELLE = HIER.parent / "quelldaten"
ROUTEN = HIER / "routen"
AUS = HIER / "ausgabe"
sys.path.insert(0, str(QUELLE))
import wegpunkte  # noqa: E402

TZ = ZoneInfo("Europe/Berlin")
UTC = timezone.utc

CHUNK = 500              # JSON-Vertrag Abschnitt 6
REST_SYNC_S = 3600       # Const.REST_SYNC_INTERVAL_S
CREW_ROLLEN = ["p1", "p2", "hems", "fr", "driver", "trainee", "other"]
PHASE_SLUG = {2: "alarmierung", 3: "ausruecken", 4: "ankunft_einsatzort",
              5: "ankunft_patientin", 6: "transportbeginn", 7: "ankunft_klinik",
              8: "uebergabezeit", 9: "endzeit"}


def epoche(lokal: str) -> int:
    return int(datetime.strptime(lokal, "%Y-%m-%d %H:%M").replace(tzinfo=TZ).timestamp())


def iso_z(ts: int | None) -> str | None:
    return None if ts is None else datetime.fromtimestamp(ts, UTC).strftime("%Y-%m-%dT%H:%M:%SZ")


def iso_offset(ts: int) -> str:
    """Ortszeit mit Zonenversatz, Schreibweise wie im Export der Anwendung.

    MIT DOPPELPUNKT im Versatz ("+02:00", nicht "+0200"). `assets/export.js`
    baut ihn in `isoOffset()` von Hand genau so zusammen, und der Rueckweg
    (`PARSERS.isoTs` in `assets/import.js`) prueft gegen
    `[+-]\d{2}:\d{2}` — die Kurzform faellt dort als "Zeitstempel nicht
    lesbar" durch. Pythons %z liefert die Kurzform, deshalb der Nachbau.
    """
    s = datetime.fromtimestamp(ts, TZ).strftime("%Y-%m-%dT%H:%M:%S%z")
    return s[:-2] + ":" + s[-2:]


# --------------------------------------------------------------- Spuren bauen
# Wie oft ist eine Strasse gesucht und nicht gefunden worden (R64/AP4,
# B-R64-03)? Die Zahl steht am Ende des Laufs und ist die Antwort auf die
# stillste Falle dieses Werkzeugs.
STRASSE_GEFUNDEN = 0
STRASSE_ERSATZ = 0


def _routen_nachschlagen(routen: dict, ref: str, abschnitt: int):
    """Eine Strecke nachschlagen UND mitzaehlen, ob es sie gab.

    WARUM DIESER UMWEG STATT `routen.get(...)`. Findet der Index nichts,
    zeichnet der Generator eine Luftlinie -- ohne Meldung, ohne Zaehler.
    Fuer die acht LUFTdienste ist das richtig: ein Hubschrauber faehrt
    keine Strasse, und fuer Geraet 11 steht bewusst keine einzige Strecke in
    `routen_soll.json`. Fuer die acht BODENdienste ist es ein Fehler, der
    sich als Erfolg meldet -- 190 km/h Luftlinie statt der OSRM-Route,
    sichtbar erst auf der Karte.

    Aufgefallen in R64/AP4: Dort wurden die Kennungen des Geraets 12 von
    `m-`/`r-` auf `am-`/`ar-` umgestellt, und `routen_soll.json` schluesselt
    genau auf diese Kennungen. Waere die Datei nicht mitgezogen worden,
    haetten alle 117 Strecken lautlos ihre Strassengeometrie verloren. Seit
    diesem Paket sagt der Lauf, wie oft er eine Strecke ersetzt hat; ein
    Sprung in dieser Zahl ist der Befund, den es vorher nicht gab.
    """
    global STRASSE_GEFUNDEN, STRASSE_ERSATZ
    geo = routen.get((ref, abschnitt))
    if geo is None:
        STRASSE_ERSATZ += 1
    else:
        STRASSE_GEFUNDEN += 1
    return geo


def _routen_index() -> dict:
    soll = json.loads((ROUTEN / "routen_soll.json").read_text("utf-8"))
    index: dict[tuple[str, int], dict] = {}
    fehlend = []
    for a in soll:
        pfad = ROUTEN / a["datei"]
        if pfad.exists():
            index[(a["client_ref"], a["abschnitt"])] = json.loads(pfad.read_text("utf-8"))
        else:
            fehlend.append(a["datei"])
    if fehlend:
        print(f"  ACHTUNG: {len(fehlend)} Streckendateien aus routen_soll.json "
              f"fehlen auf der Platte, z. B. {fehlend[0]}")
    return index


def _fenster(einsatz: dict, dienst: dict, legs: int) -> list[tuple[int, int]]:
    """Zeitfenster der Bewegungsabschnitte, aus den Phasen abgeleitet.

    Die Phasen sind die Wahrheit ueber den Ablauf: 3 -> 4 ist der Weg zum
    Einsatzort, 6 -> 7 der Transport, danach der Rueckweg bis Phase 9. Der
    Track wird an sie GEBUNDEN und nicht daneben erfunden -- sonst zeigte die
    Karte den Hubschrauber am Einsatzort, waehrend die Phasentabelle ihn schon
    in der Klinik fuehrt.
    """
    p = {}
    for nr, zeit in einsatz["phasen"]:
        p.setdefault(nr, epoche(zeit))
    start = epoche(einsatz["beginn"])
    ende = epoche(einsatz["ende"]) if einsatz["ende"] else epoche(dienst["ende"])
    kandidaten = []
    if 3 in p and 4 in p:
        kandidaten.append((p[3], p[4]))
    elif 4 in p:
        kandidaten.append((start, p[4]))
    if 6 in p and 7 in p:
        kandidaten.append((p[6], p[7]))
    if 9 in p:
        ab = p.get(8) or p.get(7) or p.get(5) or p.get(4) or start
        if p[9] > ab:
            kandidaten.append((ab, p[9]))
    if len(kandidaten) < legs:                     # Notnagel: gleichmaessig teilen
        spanne = (ende - start) / max(legs, 1)
        kandidaten = [(int(start + i * spanne), int(start + (i + 1) * spanne))
                      for i in range(legs)]
    return kandidaten[:legs]


def spur_bauen(dienst: dict, einsatz: dict, koords: list, routen: dict) -> list[tuple]:
    punkte_roh: list[tuple] = []
    if len(koords) < 2:
        return []
    legs = len(koords) - 1
    fenster = _fenster(einsatz, dienst, legs)
    ref = einsatz["client_ref"] or einsatz.get("quell_kennung") or "?"
    start = epoche(einsatz["beginn"])

    # --- 1. Die Bewegungsabschnitte ------------------------------------
    abschnitte = []
    for i in range(legs):
        t0, t1 = fenster[i]
        geo = _routen_nachschlagen(routen, einsatz["client_ref"], i)
        if geo:
            abschnitte.append(spur.fahrt(geo["geometry"]["coordinates"], t0, t1))
        else:
            abschnitte.append(spur.flug(koords[i], koords[i + 1], t0, t1, f"{ref}-{i}"))

    # --- 2. Die Halte dazwischen, an den TATSAECHLICHEN Enden -----------
    #
    # Nicht am Wegpunkt: OSRM rastet Anfang und Ende einer Route auf die
    # naechste Strasse. Steht der Halt davor exakt auf dem Wegpunkt und die
    # Route beginnt zweihundert Meter weiter, springt die Spur -- und aus
    # dem Sprung wird eine Momentangeschwindigkeit von 175 km/h. Der Halt
    # gehoert deshalb dorthin, wo die Fahrt wirklich endet.
    def punkt_von(abschnitt, ende: bool, ersatz):
        if not abschnitt:
            return ersatz
        p = abschnitt[-1] if ende else abschnitt[0]
        return (p[0], p[1])

    if fenster and fenster[0][0] > start:
        punkte_roh += spur.halt(punkt_von(abschnitte[0], False, koords[0]),
                                start, fenster[0][0], spur.TAKT_HALT, ref)
    for i in range(legs):
        punkte_roh += abschnitte[i]
        if i + 1 < legs and fenster[i + 1][0] > fenster[i][1]:
            zwischen = punkt_von(abschnitte[i], True, koords[i + 1])
            punkte_roh += spur.halt(zwischen, fenster[i][1], fenster[i + 1][0],
                                    spur.TAKT_HALT, ref)

    ende = epoche(einsatz["ende"]) if einsatz["ende"] else None
    if ende and fenster and ende > fenster[-1][1]:
        punkte_roh += spur.halt(punkt_von(abschnitte[-1], True, koords[-1]),
                                fenster[-1][1], ende, spur.TAKT_HALT, ref)

    punkte_roh.sort(key=lambda p: p[3])
    return spur.ausduennen(punkte_roh)


def ruhespur(dienst: dict, stueck: dict, routen: dict) -> list[tuple]:
    """Ruhe-Segment: erst der Rueckweg, dann Stillstand.

    Bis zum Umbau stand das Fahrzeug hier einfach an seinem Standort. Das war
    falsch: Nach einem Transport beginnt das Ruhe-Segment AN DER KLINIK, und
    der Weg zurueck wird darin aufgezeichnet (Model.mc, `_endMission` ->
    `_startRestSegment`). Wer ihn weglaesst, hat eine Spur, die springt.
    """
    von, nach = stueck["von"], stueck["nach"]
    t0 = epoche(stueck["beginn"])
    t1 = epoche(stueck["ende"])
    if not von or not nach:
        return []
    if von == nach:
        return spur.ausduennen(spur.halt(nach, t0, t1, spur.TAKT_RUHE, stueck["ref"]))

    geo = _routen_nachschlagen(routen, stueck["ref"], 0)
    # 0,95 und nicht 0,8: Ist das Ruhe-Segment kurz, wurde der Rueckweg
    # frueher in vier Fuenftel davon gepresst -- und der Rueckflug erreichte
    # 346 km/h. Er darf das Segment fast ganz ausfuellen; danach steht das
    # Fahrzeug eben nur kurz.
    weg_s = (min(geo["properties"]["dauer_s"], (t1 - t0) * 0.95) if geo
             else min(wegpunkte.abstand_m(*von, *nach) / 1000.0 / 190.0 * 3600.0,
                      (t1 - t0) * 0.95))
    tw = t0 + int(max(weg_s, 60))
    punkte = (spur.fahrt(geo["geometry"]["coordinates"], t0, tw) if geo
              else spur.flug(von, nach, t0, tw, stueck["ref"]))
    if t1 > tw:
        # Am tatsaechlichen Ende der Fahrt stehen, nicht am Wegpunkt daneben
        # (OSRM rastet auf die Strasse) -- sonst springt die Spur.
        letzte = (punkte[-1][0], punkte[-1][1]) if punkte else nach
        punkte += spur.halt(letzte, tw, t1, spur.TAKT_RUHE, stueck["ref"])
    return spur.ausduennen(sorted(punkte, key=lambda p: p[3]))


# ------------------------------------------------------- Phasen mit Koordinate
def phasen_mit_ort(einsatz: dict, punkte: list[tuple], dienst: dict,
                   vorheriger: dict | None, standorte: dict) -> list[dict]:
    """Phasenkoordinate = Position der Uhr zu diesem Zeitpunkt.

    Mit Track wird der naechstgelegene Spurpunkt genommen -- genau das tut die
    Uhr, sie liest ihre letzte Position. Ohne Track (nachtraeglich erfasste
    Einsaetze) folgt die Koordinate der Regel aus FORMAT.md: 2/3 am
    Abfahrtort, 4/5/6 am Einsatzort, 7/8 am Ziel, 9 an der Basis.
    """
    ergebnis = []
    if punkte:
        for nr, zeit in einsatz["phasen"]:
            t = epoche(zeit)
            nah = min(punkte, key=lambda p: abs(p[3] - t))
            ergebnis.append({"phase": nr, "at": iso_z(t), "lat": nah[0], "lon": nah[1]})
        return ergebnis

    aufgeloest = dict(wegpunkte.aufloesen(dienst, einsatz, vorheriger, standorte))
    basis = wegpunkte.basis_von(dienst, standorte)
    ort = wegpunkte.ort_von(einsatz)
    ziel = wegpunkte.ziel_von(einsatz)
    ab = aufgeloest.get("start") or aufgeloest.get("ort_vorher") or \
        aufgeloest.get("ziel_vorher") or basis
    zuordnung = {2: ab, 3: ab, 4: ort, 5: ort, 6: ort, 7: ziel, 8: ziel, 9: basis}
    for nr, zeit in einsatz["phasen"]:
        k = zuordnung.get(nr) or ort or basis
        ergebnis.append({"phase": nr, "at": iso_z(epoche(zeit)),
                         "lat": k[0] if k else None, "lon": k[1] if k else None})
    return ergebnis


# ------------------------------------------------------------- Sendeplan
def sendeplan(dienst: dict, pakete: list[dict]) -> list[dict]:
    """Wann die Uhr welches Paket sendet — nachgebildet nach Uploader.mc.

    Ausloeser sind drei (grep syncAll in watch/source):
      · alle REST_SYNC_INTERVAL_S waehrend der Aufzeichnung (Track.mc)
      · das Ende eines Einsatzes (Model.mc, _endMission)
      · das Ende des Dienstes (Model.mc, endDay)

    Ein Ausloeser arbeitet die WARTESCHLANGE AB, nicht nur ein Paket:
    `onResponse` ruft `_next()`. Deshalb entstehen an einem Ausloeser so viele
    Anfragen, wie Teilstuecke offen sind — und genau das ist die Zahl, die
    R19 braucht.

    Reihenfolge wie in `_findJob()`: abgeschlossene Einsaetze, dann
    Ruhe-Segmente, dann der laufende Einsatz.
    """
    tag_start, tag_ende = epoche(dienst["beginn"]), epoche(dienst["ende"])
    ausloeser = {tag_ende}
    t = tag_start + REST_SYNC_S
    while t < tag_ende:
        ausloeser.add(t)
        t += REST_SYNC_S
    for p in pakete:
        if p["ende"]:
            ausloeser.add(p["ende"])

    rang = {"mission": 0, "rest_segment": 1}
    bestaetigt: dict[str, int] = {}
    meta_bestaetigt: set[str] = set()
    plan: list[dict] = []

    for zeit in sorted(ausloeser):
        offen = True
        while offen:
            offen = False
            for p in sorted(pakete, key=lambda x: (rang[x["kind"]] if x["ende"] and x["ende"] <= zeit
                                                   else rang[x["kind"]] + 2, x["start"])):
                if p["start"] > zeit:
                    continue
                seq_from = bestaetigt.get(p["ref"], 0)
                verfuegbar = [i for i, pt in enumerate(p["punkte"]) if pt[3] <= zeit]
                bis = (verfuegbar[-1] + 1) if verfuegbar else 0
                if seq_from >= bis and p["ref"] in meta_bestaetigt:
                    continue
                n = min(CHUNK, bis - seq_from)
                plan.append({
                    "zeit": zeit, "kind": p["kind"], "ref": p["ref"],
                    "seq_from": seq_from, "punkte": max(n, 0),
                    "final": bool(p["ende"] and p["ende"] <= zeit),
                })
                bestaetigt[p["ref"]] = seq_from + max(n, 0)
                meta_bestaetigt.add(p["ref"])
                offen = True
    return plan


def payload(dienst: dict, paket: dict, eintrag: dict) -> dict:
    """Eine Ingest-Anfrage nach docs/JSON-Vertrag.md 3 und 4."""
    seq, n = eintrag["seq_from"], eintrag["punkte"]
    punkte = [[p[0], p[1], p[2], p[3]] for p in paket["punkte"][seq:seq + n]]
    body = {
        "kind": paket["kind"],
        "client_ref": paket["ref"],
        "day": dienst["day"],
        "day_ref": dienst["day_ref"],
        "started_at": iso_z(paket["start"]),
        "ended_at": iso_z(paket["ende"]) if eintrag["final"] else None,
        "final": eintrag["final"],
        "track": {"seq_from": seq, "points": punkte},
    }
    if paket["kind"] == "mission":
        bis = seq + n
        strecke, steigung = spur.kennzahlen(paket["punkte"][:bis] or paket["punkte"])
        body["distance_m"] = strecke
        body["ascent_m"] = steigung
        body["phases"] = [p for p in paket["phasen"]
                          if datetime.strptime(p["at"], "%Y-%m-%dT%H:%M:%SZ")
                          .replace(tzinfo=UTC).timestamp() <= eintrag["zeit"]]
        sitzungen = []
        for s in paket["rea"]:
            wann = epoche(s["beginn"])
            if wann <= eintrag["zeit"]:
                sitzungen.append({
                    "started_at": iso_z(wann),
                    "events": [{"type": typ, "at": iso_z(epoche(zeit))}
                               for typ, zeit in s["ereignisse"]
                               if epoche(zeit) <= eintrag["zeit"]],
                })
        if sitzungen:
            body["resus_sessions"] = sitzungen
    return body


# ------------------------------------------------------------------ GPX
def gpx(ref: str, punkte: list[tuple], name: str) -> str:
    kopf = ('<?xml version="1.0" encoding="UTF-8"?>\n'
            '<gpx version="1.1" creator="Referenzdatensatz Gen-EM NAdoku" '
            'xmlns="http://www.topografix.com/GPX/1/1">\n'
            f'  <trk><name>{name}</name><trkseg>\n')
    zeilen = [
        f'    <trkpt lat="{p[0]}" lon="{p[1]}">'
        f'<ele>{p[2]}</ele><time>{iso_z(p[3])}</time></trkpt>'
        for p in punkte]
    return kopf + "\n".join(zeilen) + "\n  </trkseg></trk>\n</gpx>\n"


# ------------------------------------------------------------------ CSV
def csv_spalten() -> list[str]:
    """Spaltenfolge von `einsaetze.csv` — dieselbe wie in assets/export.js.

    Sie steht hier nachgebaut und nicht abgeleitet, weil export.js Browsercode
    ist. Die Reihenfolge ist fuer den Import gleichgueltig (er ordnet ueber
    Spaltennamen zu), fuer die PROFILERKENNUNG aber nicht: `erkenneProfil`
    waehlt das Profil mit den meisten Kopfzeilentreffern. Je vollstaendiger
    die Kopfzeile, desto sicherer faellt die Wahl auf `export_csv_v1`.
    """
    s = ["einsatz_id", "diensttag", "diensttag_id", "datum", "uhrzeit_ortszeit",
         "herkunft", "final", "manual", "edited", "rettungsmittel", "art", "standort"]
    s += [f"tag_crew_{r}" for r in CREW_ROLLEN]
    s += ["crew_abweichend"]
    s += [f"crew_{r}" for r in CREW_ROLLEN]
    s += ["beginn", "ende", "dauer_min"]
    for n in sorted(PHASE_SLUG):
        s += [f"phase_0{n}_{PHASE_SLUG[n]}", f"phase_0{n}_lat", f"phase_0{n}_lon"]
    s += ["strecke_m", "hoehenmeter_m", "hoehe_einsatzort_m",
          "transport_art", "na_begleitung", "fehleinsatz", "transport_dest",
          "ziel_lat", "ziel_lon", "abfahrt_regel", "schockraum", "secondary",
          "winch", "winch_cycles", "winch_cycles_pat", "winch_airload",
          "bergwacht", "bw_unit", "bw_info", "other_ema",
          "weitere_rettungsmittel", "notizen",
          "pat_mission_no", "pat_nachname", "pat_vorname", "pat_geburtsdatum",
          "pat_alter", "pat_diagnose", "pat_ort_adresse", "pat_ort_lat",
          "pat_ort_lon", "pat_ort_beschreibung", "pat_start_adresse",
          "pat_start_lat", "pat_start_lon", "rea_json",
          "track_datei", "track_punkte"]
    return s


def csv_zeile(dienst: dict, einsatz: dict, phasen: list[dict]) -> dict:
    f, g = einsatz["felder"] or {}, einsatz["geschuetzt"] or {}
    start = epoche(einsatz["beginn"])
    ende = epoche(einsatz["ende"]) if einsatz["ende"] else None
    nach_phase = {p["phase"]: p for p in phasen}
    z = {s: "" for s in csv_spalten()}
    z.update({
        "diensttag": dienst["day"],
        "datum": datetime.fromtimestamp(start, TZ).strftime("%Y-%m-%d"),
        "uhrzeit_ortszeit": datetime.fromtimestamp(start, TZ).strftime("%H:%M"),
        "herkunft": "import", "final": 1 if einsatz["final"] else 0, "manual": 1, "edited": 0,
        "rettungsmittel": dienst["rettungsmittel"], "standort": dienst["standort"],
        "art": "luft" if dienst["art"] == "air" else "boden",
        "beginn": iso_offset(start), "ende": iso_offset(ende) if ende else "",
        "crew_abweichend": f.get("crew_override", 0),
        "transport_art": f.get("transport_mode") or "",
        "na_begleitung": f.get("na_escort", 0),
        "fehleinsatz": f.get("false_alarm", 0),
        "transport_dest": f.get("transport_dest") or "",
        "ziel_lat": f.get("dest_lat") if f.get("dest_lat") is not None else "",
        "ziel_lon": f.get("dest_lon") if f.get("dest_lon") is not None else "",
        "abfahrt_regel": f.get("start_src") or "",
        "schockraum": f.get("schockraum", 0), "secondary": f.get("secondary", 0),
        "winch": f.get("winch", 0),
        "winch_cycles": f.get("winch_cycles") if f.get("winch_cycles") is not None else "",
        "winch_cycles_pat": (f.get("winch_cycles_pat")
                             if f.get("winch_cycles_pat") is not None else ""),
        "winch_airload": f.get("winch_airload", 0),
        "bergwacht": f.get("bergwacht", 0),
        "bw_unit": f.get("bw_unit") or "", "bw_info": f.get("bw_info") or "",
        "other_ema": f.get("other_ema") or "",
        # Getrennt mit Komma: der Import zerlegt an Komma UND Semikolon, aber
        # ein Semikolon waere hier zugleich das Feldtrennzeichen der Datei.
        "weitere_rettungsmittel": ", ".join(f.get("other_resources") or []),
        "notizen": f.get("notes") or "",
        "pat_mission_no": g.get("mission_no") or "",
        "pat_geburtsdatum": g.get("dob") or "",
        "pat_alter": g.get("age") if g.get("age") is not None else "",
        "pat_diagnose": g.get("dx") or "",
        "pat_ort_beschreibung": g.get("site_desc") or "",
    })
    if ende:
        p2, p9 = nach_phase.get(2), nach_phase.get(9)
        if p2 and p9:
            z["dauer_min"] = round((epoche(einsatz["phasen"][-1][1]) - start) / 60)
    loc = g.get("loc") or {}
    z["pat_ort_adresse"] = loc.get("addr") or ""
    z["pat_ort_lat"] = loc.get("lat") if loc.get("lat") is not None else ""
    z["pat_ort_lon"] = loc.get("lon") if loc.get("lon") is not None else ""
    st = g.get("start") or {}
    z["pat_start_adresse"] = st.get("addr") or ""
    z["pat_start_lat"] = st.get("lat") if st.get("lat") is not None else ""
    z["pat_start_lon"] = st.get("lon") if st.get("lon") is not None else ""
    for nr, p in nach_phase.items():
        z[f"phase_0{nr}_{PHASE_SLUG[nr]}"] = iso_offset(
            int(datetime.strptime(p["at"], "%Y-%m-%dT%H:%M:%SZ").replace(tzinfo=UTC).timestamp()))
        if p.get("lat") is not None:
            z[f"phase_0{nr}_lat"] = p["lat"]
            z[f"phase_0{nr}_lon"] = p["lon"]
    for rolle, name in (dienst.get("besatzung") or {}).items():
        if name:
            z[f"tag_crew_{rolle}"] = name
            z[f"crew_{rolle}"] = name
    for rolle, name in (f.get("crew") or {}).items():
        if name:
            z[f"crew_{rolle}"] = name
    return z


# Zeichen, mit denen ein Tabellenprogramm eine Zelle als FORMEL liest, und die
# Zahlenform, die davon ausgenommen ist — beides wie in assets/export.js.
CSV_FORMELSTART = re.compile(r"^[=+\-@\t\r]")
CSV_ZAHL = re.compile(r"^-?\d+(\.\d+)?$")


def csv_wert(v) -> str:
    """Formelschutz wie im Export (`csvEscape`).

    NOTWENDIG, DAMIT DIE DATEI EINE ECHTE export_csv_v1-DATEI IST. Ohne den
    Apostroph liest SheetJS — die vendorierte Fassung, die die Anwendung
    selbst benutzt — eine Zelle mit fuehrendem '=' als FORMEL, und ihr Wert
    ist danach leer. Gemessen: '=SUMME(B1:B2)' kommt als '' zurueck.

    Der Import entfernt den Apostroph NICHT wieder (F-P1-G). Der
    Referenzdatensatz fuehrt deshalb keine Formelzeichen ueber den CSV-Weg;
    diese Funktion ist trotzdem richtig — die Datei soll sein, was sie
    vorgibt zu sein.
    """
    if v is None:
        return ""
    s = str(v)
    if CSV_FORMELSTART.match(s) and not CSV_ZAHL.match(s):
        s = "'" + s
    return s


def csv_schreiben(zeilen: list[dict], ziel: pathlib.Path) -> None:
    """UTF-8 MIT BOM, Semikolon, CRLF — die Konventionen des Exports."""
    puffer = io.StringIO()
    schreiber = csv.DictWriter(puffer, fieldnames=csv_spalten(),
                               delimiter=";", lineterminator="\r\n",
                               quoting=csv.QUOTE_MINIMAL)
    schreiber.writeheader()
    for z in zeilen:
        schreiber.writerow({k: csv_wert(v) for k, v in z.items()})
    ziel.write_text("﻿" + puffer.getvalue(), "utf-8")


# ------------------------------------------------------------------ Hauptlauf
def main() -> int:
    stammdaten = json.loads((QUELLE / "stammdaten.json").read_text("utf-8"))
    standorte = {s["name"]: s for s in stammdaten["standorte"]}
    routen = _routen_index()

    if AUS.exists():
        for p in sorted(AUS.rglob("*"), reverse=True):
            p.unlink() if p.is_file() else p.rmdir()
    for unter in ("payloads", "formular", "import", "gpx"):
        (AUS / unter).mkdir(parents=True, exist_ok=True)

    plan_gesamt: list[dict] = []
    csv_zeilen: list[dict] = []
    zahl = {"dienste": 0, "einsaetze": 0, "ruhesegmente": 0, "spurpunkte": 0,
            "spurpunkte_luft": 0, "spurpunkte_boden": 0, "spurpunkte_ruhe": 0,
            "anfragen": 0, "anfragen_mit_teilstuecken": 0,
            "formular_nachtrag": 0, "formular_neu": 0, "import_zeilen": 0,
            "groesster_body_bytes": 0, "gpx_dateien": 0}

    dateien = sorted((QUELLE / "dienste").glob("D*.json"))
    sperr = json.loads((QUELLE / "pruefschritte" / "sperrliste.json").read_text("utf-8"))

    for pfad in dateien:
        d = json.loads(pfad.read_text("utf-8"))
        dn, kennung = d["dienst"], d["kennung"]
        zahl["dienste"] += 1
        pakete: list[dict] = []

        # Zusatzeinsatz des Sperrlisten-Prüfschritts an seinem Diensttag
        einsaetze = list(d["einsaetze"])
        if sperr["einsatz"]["dienst"] == kennung:
            einsaetze = sorted(einsaetze + [sperr["einsatz"]], key=lambda e: e["beginn"])

        # DER TAGESABLAUF ist die gemeinsame Ableitung (wegpunkte.tagesablauf):
        # Er sagt fuer jedes Stueck, wo es anfaengt und wo es aufhoert -- und
        # damit auch, welches Ruhe-Segment einen Rueckweg traegt.
        ablauf = wegpunkte.tagesablauf(dn, einsaetze, d["ruhesegmente"], standorte)
        stueck_je_ref = {s["ref"]: s for s in ablauf}
        vorheriger = None

        for e in einsaetze:
            ist_pruefschritt = e is sperr["einsatz"]
            if not ist_pruefschritt:
                zahl["einsaetze"] += 1
            ref = e["client_ref"] or e.get("quell_kennung")
            koords = stueck_je_ref[ref]["wegpunkte"] if e.get("route") else []
            punkte = spur_bauen(dn, e, koords, routen) if koords else []
            phasen = phasen_mit_ort(e, punkte, dn, vorheriger, standorte)
            vorheriger = e

            if punkte:
                zahl["spurpunkte"] += len(punkte)
                zahl["spurpunkte_luft" if dn["art"] == "air" else "spurpunkte_boden"] += len(punkte)
                (AUS / "gpx" / f"{ref}.gpx").write_text(
                    gpx(ref, punkte, f"{kennung} {ref}"), "utf-8")
                zahl["gpx_dateien"] += 1

            if e["kanal"] == "ingest":
                pakete.append({"kind": "mission", "ref": e["client_ref"],
                               "start": epoche(e["beginn"]),
                               "ende": epoche(e["ende"]) if e["ende"] else None,
                               "punkte": punkte, "phasen": phasen, "rea": e["rea"]})
            elif e["kanal"] == "import":
                csv_zeilen.append(csv_zeile(dn, e, phasen))
                zahl["import_zeilen"] += 1

            if e["kanal"] == "formular" or (e["kanal"] == "ingest" and e.get("nachtrag")):
                art = "neu" if e["kanal"] == "formular" else "nachtrag"
                zahl["formular_" + art] += 1
                (AUS / "formular" / f"{kennung}_{ref}.json").write_text(json.dumps({
                    "kennung": ref, "dienst": kennung, "art": art,
                    "client_ref": e["client_ref"], "quell_kennung": e.get("quell_kennung"),
                    "beginn": e["beginn"], "ende": e["ende"], "final": e["final"],
                    "felder": e["felder"], "geschuetzt": e["geschuetzt"],
                    "phasen": e["phasen"], "rea": e["rea"],
                }, ensure_ascii=False, indent=2) + "\n", "utf-8")

        for r in d["ruhesegmente"]:
            zahl["ruhesegmente"] += 1
            punkte = ruhespur(dn, stueck_je_ref[r["client_ref"]], routen)
            zahl["spurpunkte"] += len(punkte)
            zahl["spurpunkte_ruhe"] += len(punkte)
            pakete.append({"kind": "rest_segment", "ref": r["client_ref"],
                           "start": epoche(r["beginn"]),
                           "ende": epoche(r["ende"]) if r["ende"] else None,
                           "punkte": punkte, "phasen": [], "rea": []})

        plan = sendeplan(dn, pakete)
        nach_ref = {p["ref"]: p for p in pakete}
        ordner = AUS / "payloads" / kennung
        ordner.mkdir(parents=True, exist_ok=True)
        je_ref: dict[str, int] = {}
        for i, eintrag in enumerate(plan, start=1):
            body = payload(dn, nach_ref[eintrag["ref"]], eintrag)
            roh = json.dumps(body, ensure_ascii=False, separators=(",", ":"))
            (ordner / f"{i:04d}.json").write_text(roh + "\n", "utf-8")
            zahl["anfragen"] += 1
            zahl["groesster_body_bytes"] = max(zahl["groesster_body_bytes"],
                                               len(roh.encode("utf-8")))
            je_ref[eintrag["ref"]] = je_ref.get(eintrag["ref"], 0) + 1
            eintrag["datei"] = f"payloads/{kennung}/{i:04d}.json"
            eintrag["dienst"] = kennung
        zahl["anfragen_mit_teilstuecken"] += sum(1 for n in je_ref.values() if n > 1)
        plan_gesamt += plan

    csv_schreiben(csv_zeilen, AUS / "import" / "einsaetze.csv")
    (AUS / "sendeplan.json").write_text(
        json.dumps(plan_gesamt, ensure_ascii=False, indent=1) + "\n", "utf-8")
    (AUS / "kennzahlen.json").write_text(
        json.dumps(zahl, ensure_ascii=False, indent=2) + "\n", "utf-8")

    print(f"Dienste                  {zahl['dienste']}")
    print(f"Einsätze                 {zahl['einsaetze']}")
    print(f"Ruhesegmente             {zahl['ruhesegmente']}")
    print(f"Spurpunkte               {zahl['spurpunkte']}  "
          f"(Luft {zahl['spurpunkte_luft']}, Boden {zahl['spurpunkte_boden']}, "
          f"Ruhe {zahl['spurpunkte_ruhe']})")
    print(f"Ingest-Anfragen          {zahl['anfragen']}  "
          f"({zahl['anfragen_mit_teilstuecken']} Pakete in mehreren Teilstücken)")
    print(f"größter Body             {zahl['groesster_body_bytes'] / 1024:.1f} KB "
          f"(Grenze 512 KB)")
    print(f"Formular: nachtragen     {zahl['formular_nachtrag']}")
    print(f"Formular: neu anlegen    {zahl['formular_neu']}")
    print(f"CSV-Importzeilen         {zahl['import_zeilen']}")
    print(f"GPX-Dateien              {zahl['gpx_dateien']}")
    print(f"Strecken aus OSRM        {STRASSE_GEFUNDEN}  "
          f"(Luftlinie ersatzweise: {STRASSE_ERSATZ})")
    return 0


if __name__ == "__main__":
    sys.exit(main())
