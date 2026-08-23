#!/usr/bin/env python3
"""Quelldaten des Referenzdatensatzes pruefen (Arbeitspaket B1).

Drei Pruefungen in einem Lauf:

  1. SCHEMA      -- alle Dokumente gegen schema/*.json
  2. SACHE       -- Widersprueche, die ein Schema nicht sieht: Zeiten
                    ausserhalb ihres Dienstes, Rollen, die das
                    Rettungsmittel nicht anbietet, ein Windenhaken an
                    einem Rettungsmittel ohne Winde, Ortszeiten in einer
                    Stunde, die es an dem Tag nicht gibt, reale Namen
  3. ABDECKUNG   -- jede Zeile der Abdeckungsmatrix (Konzept Abschnitt 5)
                    gegen die Marken in den Dokumenten

WARUM MIT ZAHLEN. Eine Pruefung ohne Zahl ist keine Pruefung. Der Lauf
sagt am Ende, wie viele Dokumente, Einsaetze und Einzelpruefungen er
angesehen hat -- und meldet jede Matrixzeile, die kein Dokument belegt.

Aufruf:  python3 pruefen.py            (aus diesem Verzeichnis)
Rueckgabe: 0 = alles in Ordnung, 1 = Befunde
"""
from __future__ import annotations

import json
import pathlib
import re
import sys
from datetime import datetime, timedelta
from zoneinfo import ZoneInfo

from jsonschema import Draft202012Validator

import wegpunkte

HIER = pathlib.Path(__file__).resolve().parent
TZ = ZoneInfo("Europe/Berlin")

# --- Kataloge aus dem Bestand (server/db.php, docs/JSON-Vertrag.md) ---------
CREW_ROLES = {"p1", "p2", "hems", "fr", "driver", "trainee", "other"}
ROLLEN_ART = {"p1": "air", "p2": "air", "hems": "air", "fr": "air",
              "driver": "ground", "trainee": "ground", "other": "both"}
REA_TYPEN = {"zugang", "beginn", "adrenalin", "rhythmuskontrolle", "defibrillation",
             "intubation", "amiodaron", "sonographie", "rosc", "tod"}
PHASEN = set(range(2, 10))

# --- Reale Namen, die hier nicht vorkommen duerfen (E-P1-02) ---------------
# Die Geographie ist echt, die NAMEN sind erfunden. Diese Liste faengt das
# Naheliegende ab: reale Rufnamen der Luftrettung und die Orte, deren
# Koordinaten der Datensatz benutzt.
VERBOTENE_NAMEN = [
    "Christoph", "Christophorus", "Rega", "ADAC", "DRF", "Air Rescue",
    "Kempten", "Oberstdorf", "Immenstadt", "Sonthofen", "Memmingen",
    "Murnau", "Garmisch", "Partenkirchen", "Füssen", "Fuessen", "Lindau",
    "Nesselwang", "Pfronten", "Hindelang", "Balderschwang", "Oberjoch",
    "Grünten", "Gruenten", "Nebelhorn", "Hochgrat", "Kaufbeuren", "Isny",
    "Wertach", "Mittelberg", "Rettenberg", "Weitnau", "Durach", "Betzigau",
    "Wiggensbach", "Buchenberg", "Altusried", "Dietmannsried", "Waltenhofen",
    "Blaichach", "Burgberg", "Fischen", "Maiselstein", "Tiefenbach",
    "Marktoberdorf", "Ulm", "Augsburg", "München", "Muenchen",
]

# --- Abdeckungsmatrix (Konzept Abschnitt 5) --------------------------------
# Je Zeile: (Dimension, Anforderung, Marken -- eine davon genuegt).
# Zeilen mit leerer Markenmenge werden STRUKTURELL geprueft (siehe unten).
MATRIX = [
    ("Erfassungsart (R4)", "luftgebunden mit Track (Ingest)", ["erfassung-luft-track"]),
    ("Erfassungsart (R4)", "bodengebunden mit Track (Ingest)", ["erfassung-boden-track"]),
    ("Erfassungsart (R4)", "nachträglich ohne Track", ["erfassung-nachtraeglich-ohne-track"]),
    ("Herkunft", "watch", ["herkunft-watch"]),
    ("Herkunft", "manual", ["herkunft-manuell"]),
    ("Herkunft", "import", ["herkunft-import"]),
    ("Diensttage", "Luftdienst", ["dienst-luft"]),
    ("Diensttage", "Bodendienst", ["dienst-boden"]),
    ("Diensttage", "Kalendertag mit zwei Diensten", ["zwei-dienste-ein-tag"]),
    ("Diensttage", "Dienst über Mitternacht", ["dienst-ueber-mitternacht"]),
    ("Diensttage", "Einsatzdatum ≠ Diensttag", ["einsatzdatum-abweichend"]),
    ("Diensttage", "Diensttag ohne Einsatz", ["dienst-ohne-einsatz"]),
    ("Diensttage", "Tagesnotizen", ["notizen-diensttag"]),
    ("Besatzung", "alle Rollen des Katalogs belegt", []),
    ("Besatzung", "abweichende Besatzung (crew_override)", ["besatzung-abweichend"]),
    ("Phasen", "alle Phasen 2–9 im Datensatz", []),
    ("Phasen", "Mehrfacheintrag derselben Phase", ["phasen-mehrfach"]),
    ("Phasen", "unvollständige Phasen", ["phasen-unvollstaendig"]),
    ("Phasen", "nicht abgeschlossener Einsatz", ["einsatz-nicht-abgeschlossen"]),
    ("Reanimation", "Einsatz mit einer Sitzung", ["rea-einzeln"]),
    ("Reanimation", "Einsatz mit mehreren Sitzungen", ["rea-mehrere-sitzungen"]),
    ("Reanimation", "alle zehn Ereignisarten", []),
    ("Transport", "Transportart air", ["transport-air"]),
    ("Transport", "Transportart ground", ["transport-ground"]),
    ("Transport", "Transportart ambulant", ["transport-ambulant"]),
    ("Transport", "Transportart leer", ["transport-leer"]),
    ("Transport", "NA-Begleitung", ["na-escort"]),
    ("Transport", "Fehleinsatz / Storno", ["fehleinsatz"]),
    ("Transport", "Sekundärtransport", ["sekundaertransport"]),
    ("Transport", "Schockraum", ["schockraum"]),
    ("Transport", "Zielklinik mit Koordinate", ["zielklinik-koordinate"]),
    ("Transport", "Zielklinik ohne Koordinate", ["zielklinik-ohne-koordinate"]),
    ("Abfahrtort", "Regel base", ["start-base"]),
    ("Abfahrtort", "Regel prev_site", ["start-prevsite"]),
    ("Abfahrtort", "Regel prev_dest", ["start-prevdest"]),
    ("Abfahrtort", "Regel manual (verschlüsselter pat.start)", ["start-manual"]),
    ("Luftspezifik", "Winde mit Cycles", ["winde-cycles"]),
    ("Luftspezifik", "Cycles mit Patient", ["winde-cycles-patient"]),
    ("Luftspezifik", "Luftverladung", ["winde-luftverladung"]),
    ("Luftspezifik", "Bergwacht mit Einheit und bw_info", ["bergwacht-info"]),
    ("Geschützte Angaben", "Geburtsdatum (Alter gerechnet)", ["geschuetzt-dob"]),
    ("Geschützte Angaben", "Handalter (pat_alter)", ["geschuetzt-alter"]),
    ("Geschützte Angaben", "R20-Angriffswert im Altersfeld", ["r20-alter"]),
    ("Geschützte Angaben", "Diagnose", ["geschuetzt-dx"]),
    ("Geschützte Angaben", "Einsatzort mit Adresse und Koordinate", ["geschuetzt-loc"]),
    ("Geschützte Angaben", "Ortsbeschreibung", ["geschuetzt-sitedesc"]),
    ("Geschützte Angaben", "Einsatznummer", ["geschuetzt-nr"]),
    ("Geschützte Angaben", "Einsatz ohne jede geschützte Angabe", ["geschuetzt-keine"]),
    ("Sonderzeichen", "Semikolon", ["sonderzeichen-semikolon"]),
    ("Sonderzeichen", "Anführungszeichen", ["sonderzeichen-anfuehrung"]),
    ("Sonderzeichen", "Zeilenumbruch", ["sonderzeichen-zeilenumbruch"]),
    ("Sonderzeichen", "Formel-Anfangszeichen =",
     ["sonderzeichen-formel-gleich"]),
    ("Sonderzeichen", "Formel-Anfangszeichen +", ["sonderzeichen-formel-plus"]),
    ("Sonderzeichen", "Formel-Anfangszeichen -", ["sonderzeichen-formel-minus"]),
    ("Sonderzeichen", "Formel-Anfangszeichen @", ["sonderzeichen-formel-at"]),
    ("Sonderzeichen", "Umlaute und ß", ["sonderzeichen-umlaute"]),
    ("Ruhezeiten", "Segmente mit Track", ["ruhe-track"]),
    ("Ruhezeiten", "mehrere Segmente je Dienst", ["ruhe-mehrere"]),
    ("Ruhezeiten", "nicht abgeschlossenes Segment", ["ruhe-nicht-abgeschlossen"]),
    ("Papierkorb", "gelöschter Einsatz (einzeln)", ["papierkorb-einsatz"]),
    ("Papierkorb", "gelöschter Diensttag", ["papierkorb-diensttag"]),
    ("Papierkorb", "Einsätze mit deleted_with_day", ["papierkorb-einsatz-mit-tag"]),
    ("Papierkorb", "Sperrlisten-Fall als Ablaufschritt", ["sperrliste-ablaufschritt"]),
    ("Stammdaten", "≥ 2 Standorte, einer ohne Koordinaten", []),
    ("Stammdaten", "≥ 2 Luft-Rettungsmittel mit/ohne Fähigkeiten", []),
    ("Stammdaten", "≥ 1 Boden-Rettungsmittel", []),
    ("Stammdaten", "Zielkliniken mit und ohne Koordinate", []),
    ("Stammdaten", "Vorbelegungen aller Arten", []),
    ("Stammdaten", "Standard-Markierungen", []),
    ("Zeit", "Einsätze in MEZ", ["zeit-mez"]),
    ("Zeit", "Einsätze in MESZ", ["zeit-mesz"]),
    ("Zeit", "Dienst um die Umstellung im Frühjahr", ["zeitumstellung-fruehjahr"]),
    ("Zeit", "Dienst um die Umstellung im Herbst", ["zeitumstellung-herbst"]),
    ("Weitere Felder", "mehrere weitere Rettungsmittel je Einsatz",
     ["weitere-rettungsmittel-mehrere"]),
    ("Weitere Felder", "weiterer Notarzt", ["weiterer-notarzt"]),
    ("Weitere Felder", "Notizen am Einsatz", ["notizen-einsatz"]),
    ("Weitere Felder", "bearbeiteter Uhr-Einsatz (edited=1)", ["uhr-bearbeitet"]),
    ("Weitere Felder", "unbearbeiteter Uhr-Einsatz (edited=0)", ["uhr-unbearbeitet"]),
]


class Lauf:
    def __init__(self) -> None:
        self.befunde: list[str] = []
        self.pruefungen = 0

    def pruefe(self, bedingung: bool, text: str) -> None:
        self.pruefungen += 1
        if not bedingung:
            self.befunde.append(text)


def lokal(s: str) -> datetime:
    return datetime.strptime(s, "%Y-%m-%d %H:%M").replace(tzinfo=TZ)


def zeitpunkt_existiert(s: str) -> tuple[bool, bool]:
    """(existiert, eindeutig) fuer eine Ortszeit in Europa/Berlin.

    Eine Ortszeit in der uebersprungenen Stunde der Fruehjahrsumstellung
    EXISTIERT NICHT; eine in der doppelten Stunde der Herbstumstellung ist
    MEHRDEUTIG. Beides ist fuer einen Referenzdatensatz unbrauchbar: Das
    eine laesst sich nicht umrechnen, das andere nicht reproduzieren.
    """
    roh = datetime.strptime(s, "%Y-%m-%d %H:%M")
    a = roh.replace(tzinfo=TZ, fold=0)
    b = roh.replace(tzinfo=TZ, fold=1)
    existiert = a.astimezone(ZoneInfo("UTC")).astimezone(TZ).replace(tzinfo=None) == roh
    eindeutig = a.utcoffset() == b.utcoffset()
    return existiert, eindeutig


def ph_zeiten(einsatz: dict) -> dict[int, datetime]:
    """Erste Zeit je Phasennummer (Mehrfacheintraege sind Korrekturen)."""
    p: dict[int, datetime] = {}
    for nr, zeit in einsatz["phasen"]:
        p.setdefault(nr, lokal(zeit))
    return p


def bewegungsfenster(einsatz: dict, p: dict[int, datetime]) -> list[tuple]:
    """Zeitfenster der Bewegungsabschnitte — dieselbe Ableitung wie im
    Generator (`erzeugen._fenster`). Sie steht zweimal, weil Quelldaten und
    Generator sonst voneinander abhingen; die Regel selbst ist kurz und in
    FORMAT.md beschrieben."""
    f = []
    if 3 in p and 4 in p:
        f.append((p[3], p[4]))
    if 6 in p and 7 in p:
        f.append((p[6], p[7]))
    if 9 in p:
        ab = p.get(8) or p.get(7) or p.get(5) or p.get(4)
        if ab and p[9] > ab:
            f.append((ab, p[9]))
    return f


def alle_zeitpunkte(knoten, treffer: list[str]) -> None:
    if isinstance(knoten, str):
        if re.fullmatch(r"20\d\d-\d\d-\d\d \d\d:\d\d", knoten):
            treffer.append(knoten)
    elif isinstance(knoten, dict):
        for k, v in knoten.items():
            if not k.startswith("$"):
                alle_zeitpunkte(v, treffer)
    elif isinstance(knoten, list):
        for v in knoten:
            alle_zeitpunkte(v, treffer)


def main() -> int:
    lauf = Lauf()
    marken: dict[str, list[str]] = {}

    def merke(ms, wo) -> None:
        for m in ms:
            marken.setdefault(m, []).append(wo)

    # ---- Laden ------------------------------------------------------------
    stammdaten = json.loads((HIER / "stammdaten.json").read_text("utf-8"))
    dienstdateien = sorted((HIER / "dienste").glob("D*.json"))
    dienste = [json.loads(p.read_text("utf-8")) for p in dienstdateien]
    sperrliste = json.loads((HIER / "pruefschritte" / "sperrliste.json").read_text("utf-8"))

    # ---- 1. Schema --------------------------------------------------------
    v_dienst = Draft202012Validator(
        json.loads((HIER / "schema" / "dienst.schema.json").read_text("utf-8")))
    v_stamm = Draft202012Validator(
        json.loads((HIER / "schema" / "stammdaten.schema.json").read_text("utf-8")))

    for fehler in v_stamm.iter_errors(stammdaten):
        lauf.befunde.append(f"stammdaten.json: {'/'.join(map(str, fehler.path))}: {fehler.message}")
    lauf.pruefungen += 1
    for pfad, d in zip(dienstdateien, dienste):
        lauf.pruefungen += 1
        for fehler in v_dienst.iter_errors(d):
            lauf.befunde.append(f"{pfad.name}: {'/'.join(map(str, fehler.path))}: {fehler.message}")

    # ---- Nachschlagewerke aus den Stammdaten ------------------------------
    standort = {s["name"]: s for s in stammdaten["standorte"]}
    fahrzeug = {r["name"]: r for r in stammdaten["rettungsmittel"]}
    kliniken = {(k["standort"], k["name"]): k for k in stammdaten["zielkliniken"]}
    bereitsch = {(b["standort"], b["name"]) for b in stammdaten["bereitschaften"]}
    weitere = {(w["standort"], w["name"]) for w in stammdaten["weitere_rettungsmittel"]}
    vorbeleg = {(c["standort"], c["rolle"], c["name"]) for c in stammdaten["besatzung"]}

    # ---- 2. Sache ---------------------------------------------------------
    refs: dict[str, str] = {}
    day_refs: dict[str, str] = {}
    dienste_je_datum: dict[str, list[str]] = {}
    alle_phasen: set[int] = set()
    alle_rea_typen: set[str] = set()
    einsatzzahl = 0
    rollen_belegt: set[str] = set()

    def ref_pruefen(wert: str, wo: str) -> None:
        lauf.pruefe(len(wert) <= 64, f"{wo}: client_ref länger als 64 Zeichen")
        lauf.pruefe(" " not in wert, f"{wo}: client_ref enthält ein Leerzeichen")
        lauf.pruefe(wert not in refs,
                    f"{wo}: client_ref {wert} kommt schon in {refs.get(wert)} vor")
        refs[wert] = wo

    for pfad, d in zip(dienstdateien, dienste):
        n = d["kennung"]
        merke(d["abdeckung"], n)
        dn = d["dienst"]
        dienste_je_datum.setdefault(dn["day"], []).append(n)

        lauf.pruefe(dn["standort"] in standort, f"{n}: Standort {dn['standort']!r} fehlt in den Stammdaten")
        lauf.pruefe(dn["rettungsmittel"] in fahrzeug, f"{n}: Rettungsmittel {dn['rettungsmittel']!r} fehlt in den Stammdaten")
        rm = fahrzeug.get(dn["rettungsmittel"], {})
        lauf.pruefe(rm.get("art") == dn["art"], f"{n}: Art {dn['art']!r} passt nicht zum Rettungsmittel ({rm.get('art')!r})")
        lauf.pruefe(rm.get("standort") == dn["standort"], f"{n}: Rettungsmittel gehört zu {rm.get('standort')!r}, nicht zu {dn['standort']!r}")

        lauf.pruefe(dn["day_ref"] not in day_refs, f"{n}: day_ref {dn['day_ref']} kommt schon in {day_refs.get(dn['day_ref'])} vor")
        day_refs[dn["day_ref"]] = n

        dbeg, dend = lokal(dn["beginn"]), lokal(dn["ende"])
        lauf.pruefe(dbeg < dend, f"{n}: Dienstende liegt nicht nach dem Beginn")
        lauf.pruefe(dn["beginn"][:10] == dn["day"], f"{n}: 'day' ist nicht das Datum des Dienstbeginns")
        if dend.date() != dbeg.date():
            merke(["dienst-ueber-mitternacht"], n)
        if dn["notizen"]:
            merke(["notizen-diensttag"], n)

        # Besatzung: nur Rollen, die das Rettungsmittel anbietet
        for rolle, name in dn["besatzung"].items():
            lauf.pruefe(rolle in rm.get("rollen", []),
                        f"{n}: Rolle {rolle!r} wird von {dn['rettungsmittel']!r} nicht angeboten")
            if name:
                rollen_belegt.add(rolle)
                lauf.pruefe((dn["standort"], rolle, name) in vorbeleg,
                            f"{n}: Besatzung {name!r} ({rolle}) fehlt als Vorbelegung am Standort")
        # Standort ohne Koordinaten braucht einen Spur-Ausgangspunkt
        if standort.get(dn["standort"], {}).get("lat") is None:
            lauf.pruefe("spur_ausgangspunkt" in dn,
                        f"{n}: Standort ohne Koordinaten, aber ohne 'spur_ausgangspunkt'")

        # Ruhesegmente
        if len(d["ruhesegmente"]) > 1:
            merke(["ruhe-mehrere"], n)
        for r in d["ruhesegmente"]:
            ref_pruefen(r["client_ref"], f"{n}/{r['client_ref']}")
            lauf.pruefe((r["ende"] is None) == (not r["final"]),
                        f"{n}/{r['client_ref']}: final und ende widersprechen sich")
            rb = lokal(r["beginn"])
            lauf.pruefe(dbeg <= rb <= dend, f"{n}/{r['client_ref']}: Beginn liegt außerhalb des Dienstes")
            if r["ende"]:
                lauf.pruefe(rb < lokal(r["ende"]), f"{n}/{r['client_ref']}: Ende liegt nicht nach dem Beginn")
            if not r["final"]:
                merke(["ruhe-nicht-abgeschlossen"], n)
        if d["ruhesegmente"]:
            merke(["ruhe-track"], n)

        if not d["einsaetze"]:
            merke(["dienst-ohne-einsatz"], n)

        vorheriger = None
        for e in d["einsaetze"]:
            einsatzzahl += 1
            wo = f"{n}/{e['client_ref'] or e.get('quell_kennung')}"
            merke(e["abdeckung"], wo)
            if e["client_ref"]:
                ref_pruefen(e["client_ref"], wo)
                lauf.pruefe(e["kanal"] == "ingest",
                            f"{wo}: nur Ingest-Einsätze führen eine eigene client_ref")
            else:
                lauf.pruefe("quell_kennung" in e,
                            f"{wo}: ohne client_ref ist eine quell_kennung nötig")

            eb = lokal(e["beginn"])
            lauf.pruefe(dbeg <= eb <= dend, f"{wo}: Beginn liegt außerhalb des Dienstes")
            lauf.pruefe((e["ende"] is None) == (not e["final"]),
                        f"{wo}: final und ende widersprechen sich")
            ee = lokal(e["ende"]) if e["ende"] else dend
            if e["ende"]:
                lauf.pruefe(eb < ee, f"{wo}: Ende liegt nicht nach dem Beginn")
                lauf.pruefe(ee <= dend, f"{wo}: Ende liegt außerhalb des Dienstes")
            if e["beginn"][:10] != dn["day"]:
                merke(["einsatzdatum-abweichend"], wo)

            # Phasen
            gesehen: dict[int, int] = {}
            for nr, zeit in e["phasen"]:
                alle_phasen.add(nr)
                gesehen[nr] = gesehen.get(nr, 0) + 1
                lauf.pruefe(eb <= lokal(zeit) <= ee, f"{wo}: Phase {nr} liegt außerhalb des Einsatzes")
            if any(c > 1 for c in gesehen.values()):
                merke(["phasen-mehrfach"], wo)
            if len(gesehen) < 8:
                merke(["phasen-unvollstaendig"], wo)
            else:
                merke(["phasen-vollstaendig"], wo)
            if not e["final"]:
                merke(["einsatz-nicht-abgeschlossen"], wo)

            # Reanimation
            if len(e["rea"]) == 1:
                merke(["rea-einzeln"], wo)
            elif len(e["rea"]) > 1:
                merke(["rea-mehrere-sitzungen"], wo)
            for s in e["rea"]:
                lauf.pruefe(eb <= lokal(s["beginn"]) <= ee, f"{wo}: Reanimationsbeginn außerhalb des Einsatzes")
                for typ, zeit in s["ereignisse"]:
                    alle_rea_typen.add(typ)
                    merke([f"rea-typ-{typ}"], wo)
                    lauf.pruefe(typ in REA_TYPEN, f"{wo}: unbekannte Reanimationsart {typ!r}")
                    lauf.pruefe(eb <= lokal(zeit) <= ee, f"{wo}: Ereignis {typ!r} außerhalb des Einsatzes")

            # Route: jeder Wegpunkt muss auf eine Koordinate aufloesen
            aufgeloest = wegpunkte.aufloesen(dn, e, vorheriger, standort)
            for name, koord in aufgeloest:
                lauf.pruefe(koord is not None,
                            f"{wo}: Wegpunkt {name!r} löst auf keine Koordinate auf")

            # ERREICHBARKEIT. Jeder Abschnitt muss in der Zeit zu schaffen
            # sein, die die Phasen dafuer vorsehen. Das ist keine Feinheit:
            # Ohne diese Pruefung entstanden Fluege mit 666 km/h und ein NEF
            # mit 340 km/h -- und zwar unauffaellig, weil jeder einzelne Wert
            # fuer sich im gueltigen Bereich lag. Sichtbar wird es erst, wenn
            # jemand die Strecke durch die Zeit teilt.
            #
            # Gemessen wird die LUFTLINIE. Fuer den Boden ist die Grenze
            # deshalb deutlich niedriger als jede Strassengeschwindigkeit:
            # Die Strasse ist im Voralpenland rund anderthalbmal so lang.
            grenze = 250.0 if dn["art"] == "air" else 80.0
            koords = [k for _, k in aufgeloest if k]
            fenster = bewegungsfenster(e, ph_zeiten(e))
            for i in range(min(len(koords) - 1, len(fenster))):
                strecke = wegpunkte.abstand_m(*koords[i], *koords[i + 1]) / 1000.0
                minuten = (fenster[i][1] - fenster[i][0]).total_seconds() / 60.0
                if minuten <= 0:
                    lauf.pruefe(False, f"{wo}: Abschnitt {i} hat keine Dauer")
                    continue
                tempo = strecke / (minuten / 60.0)
                lauf.pruefe(tempo <= grenze,
                            f"{wo}: Abschnitt {i} verlangt {tempo:.0f} km/h "
                            f"({strecke:.1f} km in {minuten:.0f} min), Grenze {grenze:.0f}")
            vorheriger = e

            f = e["felder"]
            if f:
                if f["transport_mode"]:
                    merke([f"transport-{f['transport_mode']}"], wo)
                else:
                    merke(["transport-leer"], wo)
                for schluessel, marke in (("na_escort", "na-escort"), ("schockraum", "schockraum"),
                                          ("false_alarm", "fehleinsatz"), ("secondary", "sekundaertransport"),
                                          ("winch", "winde"), ("bergwacht", "bergwacht"),
                                          ("winch_airload", "winde-luftverladung")):
                    if f[schluessel]:
                        merke([marke], wo)
                if f["start_src"]:
                    merke([{"base": "start-base", "prev_site": "start-prevsite",
                            "prev_dest": "start-prevdest", "manual": "start-manual"}[f["start_src"]]], wo)
                else:
                    merke(["start-leer"], wo)
                if f["crew_override"]:
                    merke(["besatzung-abweichend"], wo)
                if f["other_ema"]:
                    merke(["weiterer-notarzt"], wo)
                if f["notes"]:
                    merke(["notizen-einsatz"], wo)
                if len(f["other_resources"]) > 1:
                    merke(["weitere-rettungsmittel-mehrere"], wo)
                elif f["other_resources"]:
                    merke(["weitere-rettungsmittel"], wo)
                if f["transport_dest"]:
                    merke(["zielklinik-koordinate" if f["dest_lat"] is not None
                           else "zielklinik-ohne-koordinate"], wo)
                    lauf.pruefe((dn["standort"], f["transport_dest"]) in kliniken,
                                f"{wo}: Zielklinik {f['transport_dest']!r} fehlt als Vorbelegung am Standort")

                # Faehigkeiten: Winde und Bergwacht nur, wo das Rettungsmittel sie hat
                for haken, faehigkeit in (("winch", "winch"), ("bergwacht", "bergwacht")):
                    lauf.pruefe(not f[haken] or faehigkeit in rm.get("faehigkeiten", []),
                                f"{wo}: {haken}=1, aber {dn['rettungsmittel']!r} hat die Fähigkeit {faehigkeit!r} nicht")
                if f["winch"] and f["winch_cycles"]:
                    merke(["winde-cycles"], wo)
                if f["winch"] and f["winch_cycles_pat"]:
                    merke(["winde-cycles-patient"], wo)
                if f["bergwacht"]:
                    lauf.pruefe(not f["bw_unit"] or (dn["standort"], f["bw_unit"]) in bereitsch,
                                f"{wo}: Bereitschaft {f['bw_unit']!r} fehlt als Vorbelegung am Standort")
                    if f["bw_unit"] and f["bw_info"]:
                        merke(["bergwacht-info"], wo)
                # WEITERE RETTUNGSMITTEL: Vorbelegung des eigenen Standorts --
                # es sei denn, der Einsatz ist ausdruecklich als Freitextfall
                # gekennzeichnet. Das Feld ist Freitext mit Vorschlagsliste
                # (mission_fields.php); ein Wert ausserhalb der Liste ist
                # gueltig und muss vorkommen, sonst prueft der Datensatz nur
                # den bequemen Teil des Feldes.
                freitext_erlaubt = "stammdaten-freitext" in e["abdeckung"]
                for res in f["other_resources"]:
                    lauf.pruefe(freitext_erlaubt
                                or (dn["standort"], res) in weitere
                                or (dn["standort"], res) in bereitsch,
                                f"{wo}: weiteres Rettungsmittel {res!r} fehlt als Vorbelegung am Standort")
                for rolle, name in f["crew"].items():
                    lauf.pruefe(rolle in rm.get("rollen", []),
                                f"{wo}: abweichende Rolle {rolle!r} wird vom Rettungsmittel nicht angeboten")
                # Abfahrtort 'base' braucht einen Standort MIT Koordinaten
                if f["start_src"] == "base":
                    lauf.pruefe(standort.get(dn["standort"], {}).get("lat") is not None,
                                f"{wo}: start_src='base' an einem Standort ohne Koordinaten")

            g = e["geschuetzt"]
            if g is None:
                merke(["geschuetzt-keine"], wo)
            else:
                if g["dx"]:
                    merke(["geschuetzt-dx"], wo)
                if g["dob"]:
                    merke(["geschuetzt-dob"], wo)
                if g["age"] is not None:
                    merke(["geschuetzt-alter"], wo)
                if g["mission_no"]:
                    merke(["geschuetzt-nr"], wo)
                if g["site_desc"]:
                    merke(["geschuetzt-sitedesc"], wo)
                if g["loc"] and g["loc"]["addr"] and g["loc"]["lat"] is not None:
                    merke(["geschuetzt-loc"], wo)
                # start_src='manual' und pat.start gehoeren zusammen
                hat_start = bool(g["start"])
                will_start = bool(f and f["start_src"] == "manual")
                lauf.pruefe(hat_start == will_start,
                            f"{wo}: start_src='manual' und geschuetzt.start passen nicht zusammen")

            # Sonderzeichen aus dem Inhalt ableiten statt behaupten
            text = json.dumps({"f": f, "g": g}, ensure_ascii=False)
            if ";" in text:
                merke(["sonderzeichen-semikolon"], wo)
            if '\\"' in text:
                merke(["sonderzeichen-anfuehrung"], wo)
            if "\\n" in text:
                merke(["sonderzeichen-zeilenumbruch"], wo)
            if re.search(r"[äöüÄÖÜß]", text):
                merke(["sonderzeichen-umlaute"], wo)
            for zeichen, marke in (("=", "gleich"), ("+", "plus"), ("-", "minus"), ("@", "at")):
                for feld in ((f or {}).get("notes"), (f or {}).get("bw_info"), (f or {}).get("other_ema")):
                    if isinstance(feld, str) and feld.startswith(zeichen):
                        merke([f"sonderzeichen-formel-{marke}"], wo)

            # Uhr-Einsatz bearbeitet / unbearbeitet
            if e["kanal"] == "ingest":
                merke(["uhr-bearbeitet" if e["nachtrag"] else "uhr-unbearbeitet"], wo)
                merke(["erfassung-luft-track" if dn["art"] == "air" else "erfassung-boden-track"], wo)
                merke(["herkunft-watch"], wo)
            elif e["kanal"] == "import":
                merke(["herkunft-import", "erfassung-nachtraeglich-ohne-track"], wo)
            else:
                merke(["herkunft-manuell", "erfassung-nachtraeglich-ohne-track"], wo)

            # Zeitzone des Einsatzbeginns
            merke(["zeit-mesz" if eb.dst() else "zeit-mez"], wo)

        if d["papierkorb"] == "diensttag":
            merke(["papierkorb-diensttag"], n)
            for e in d["einsaetze"]:
                merke(["papierkorb-einsatz-mit-tag"], f"{n}/{e['client_ref']}")
        for e in d["einsaetze"]:
            if e["papierkorb"] == "einsatz":
                merke(["papierkorb-einsatz"], f"{n}/{e['client_ref']}")

    # ---- Ruhe-Segmente: der Rueckweg muss in die Zeit passen -------------
    #
    # Das Ruhe-Segment traegt seit dem Umbau den Rueckweg (siehe
    # wegpunkte.tagesablauf). Damit gilt fuer es dieselbe Frage wie fuer einen
    # Einsatzabschnitt: Ist die Strecke in der Zeit ueberhaupt zu schaffen?
    for pfad, d in zip(dienstdateien, dienste):
        dn = d["dienst"]
        # Fuer den Rueckweg strenger als fuer den Einsatz: Er hat keinen
        # Sonderstatus -- niemand fliegt schneller zurueck als hin.
        grenze = 220.0 if dn["art"] == "air" else 70.0
        for s in wegpunkte.tagesablauf(dn, d["einsaetze"], d["ruhesegmente"], standort):
            if s["art"] != "ruhe" or s["von"] == s["nach"] or s["von"] is None:
                continue
            minuten = (lokal(s["ende"]) - lokal(s["beginn"])).total_seconds() / 60.0
            strecke = wegpunkte.abstand_m(*s["von"], *s["nach"]) / 1000.0
            if minuten <= 0:
                lauf.pruefe(False, f"{d['kennung']}/{s['ref']}: Ruhe-Segment ohne Dauer")
                continue
            tempo = strecke / (minuten / 60.0)
            lauf.pruefe(tempo <= grenze,
                        f"{d['kennung']}/{s['ref']}: Rückweg verlangt {tempo:.0f} km/h "
                        f"({strecke:.1f} km in {minuten:.0f} min), Grenze {grenze:.0f}")

    # Sperrlisten-Prüfschritt: Kennung eindeutig, Zeiten im genannten Dienst
    merke(sperrliste["einsatz"]["abdeckung"], sperrliste["kennung"])
    se = sperrliste["einsatz"]
    ref_pruefen(se["client_ref"], sperrliste["kennung"])
    ziel = next((x for x in dienste if x["kennung"] == se["dienst"]), None)
    lauf.pruefe(ziel is not None,
                f"{sperrliste['kennung']}: Diensttag {se['dienst']!r} gibt es nicht")
    if ziel:
        lauf.pruefe(lokal(ziel["dienst"]["beginn"]) <= lokal(se["beginn"])
                    and lokal(se["ende"]) <= lokal(ziel["dienst"]["ende"]),
                    f"{sperrliste['kennung']}: Zeiten liegen außerhalb von {se['dienst']}")

    # ---- Zeitzonen: keine nicht existierende oder mehrdeutige Ortszeit ----
    zeitpunkte: list[str] = []
    for d in dienste:
        alle_zeitpunkte(d, zeitpunkte)
    alle_zeitpunkte(sperrliste, zeitpunkte)
    for z in zeitpunkte:
        existiert, eindeutig = zeitpunkt_existiert(z)
        lauf.pruefe(existiert, f"Ortszeit {z} gibt es an diesem Tag nicht (Frühjahrsumstellung)")
        lauf.pruefe(eindeutig, f"Ortszeit {z} ist mehrdeutig (Herbstumstellung)")

    # ---- Import nur an Kalendertagen mit genau EINEM Dienst (B-04) --------
    for pfad, d in zip(dienstdateien, dienste):
        mehrere = len(dienste_je_datum[d["dienst"]["day"]]) > 1
        for e in d["einsaetze"]:
            lauf.pruefe(not (mehrere and e["kanal"] == "import"),
                        f"{d['kennung']}: Import-Einsatz an einem Datum mit mehreren Diensten "
                        f"— der Import löst nur über das Datum auf (B-04)")

    # ---- Reale Namen ------------------------------------------------------
    # NUR IN DEN DATEN, nicht in den Erlaeuterungen: Die $warum-Bloecke nennen
    # reale Namen absichtlich -- sie begruenden ja gerade, warum keiner
    # vorkommen darf. Wer sie mitdurchsucht, meldet die Begruendung als
    # Verstoss und bringt sich damit die eigene Dokumentation ab.
    def ohne_erlaeuterungen(knoten):
        if isinstance(knoten, dict):
            return {k: ohne_erlaeuterungen(v) for k, v in knoten.items() if not k.startswith("$")}
        if isinstance(knoten, list):
            return [ohne_erlaeuterungen(v) for v in knoten]
        return knoten

    volltext = json.dumps([ohne_erlaeuterungen(d) for d in dienste]
                          + [ohne_erlaeuterungen(stammdaten)]
                          + [ohne_erlaeuterungen(sperrliste)], ensure_ascii=False)
    for name in VERBOTENE_NAMEN:
        lauf.pruefe(name not in volltext,
                    f"realer Name {name!r} kommt in den Quelldaten vor (E-P1-02)")

    # ---- Strukturelle Matrixzeilen ---------------------------------------
    strukturell = {
        "alle Rollen des Katalogs belegt": (rollen_belegt == CREW_ROLES,
                                            f"belegt: {sorted(rollen_belegt)}"),
        "alle Phasen 2–9 im Datensatz": (alle_phasen == PHASEN,
                                         f"vorhanden: {sorted(alle_phasen)}"),
        "alle zehn Ereignisarten": (alle_rea_typen == REA_TYPEN,
                                    f"fehlen: {sorted(REA_TYPEN - alle_rea_typen)}"),
        "≥ 2 Standorte, einer ohne Koordinaten": (
            len(stammdaten["standorte"]) >= 2
            and any(s["lat"] is None for s in stammdaten["standorte"])
            and any(s["lat"] is not None for s in stammdaten["standorte"]), ""),
        "≥ 2 Luft-Rettungsmittel mit/ohne Fähigkeiten": (
            sum(1 for r in stammdaten["rettungsmittel"] if r["art"] == "air") >= 2
            and any(r["faehigkeiten"] for r in stammdaten["rettungsmittel"])
            and any(not r["faehigkeiten"] and r["art"] == "air" for r in stammdaten["rettungsmittel"]), ""),
        "≥ 1 Boden-Rettungsmittel": (
            any(r["art"] == "ground" for r in stammdaten["rettungsmittel"]), ""),
        "Zielkliniken mit und ohne Koordinate": (
            any(k["lat"] is not None for k in stammdaten["zielkliniken"])
            and any(k["lat"] is None for k in stammdaten["zielkliniken"]), ""),
        "Vorbelegungen aller Arten": (
            all(stammdaten[k] for k in ("besatzung", "zielkliniken", "bereitschaften",
                                        "weitere_rettungsmittel")), ""),
        "Standard-Markierungen": (
            any(s["standard"] for s in stammdaten["standorte"])
            and any(r["standard"] for r in stammdaten["rettungsmittel"]), ""),
    }

    # ---- 3. Abdeckung -----------------------------------------------------
    offen = []
    for dimension, anforderung, ms in MATRIX:
        lauf.pruefungen += 1
        if ms:
            if not any(m in marken for m in ms):
                offen.append((dimension, anforderung, "keine Marke " + "/".join(ms)))
        else:
            ok, hinweis = strukturell[anforderung]
            if not ok:
                offen.append((dimension, anforderung, hinweis))

    # ---- Umfang -----------------------------------------------------------
    # Umfang: 16 Diensttage, im Schnitt rund 6 Einsätze je Dienst (Nachtrag B1
    # zur Abdeckungsmatrix — die ursprünglichen 30–40 stammten aus einem
    # Entwurf mit deutlich weniger Bodendiensten).
    lauf.pruefe(80 <= einsatzzahl <= 100,
                f"Umfang {einsatzzahl} Einsätze liegt außerhalb von 80–100")

    # ---- Bericht ----------------------------------------------------------
    print(f"Dokumente:        {len(dienstdateien)} Dienste + Stammdaten + 1 Prüfschritt")
    print(f"Einsätze:         {einsatzzahl}")
    print(f"Ruhesegmente:     {sum(len(d['ruhesegmente']) for d in dienste)}")
    print(f"Zeitstempel:      {len(zeitpunkte)} auf Existenz und Eindeutigkeit geprüft")
    print(f"Einzelprüfungen:  {lauf.pruefungen}")
    print(f"Matrixzeilen:     {len(MATRIX)}, davon offen: {len(offen)}")
    print(f"Marken vergeben:  {len(marken)}")
    print()
    if offen:
        print("OFFENE MATRIXZEILEN")
        for dimension, anforderung, hinweis in offen:
            print(f"  [{dimension}] {anforderung}" + (f"  ({hinweis})" if hinweis else ""))
        print()
    if lauf.befunde:
        print(f"BEFUNDE ({len(lauf.befunde)})")
        for b in lauf.befunde:
            print(f"  {b}")
        return 1
    if offen:
        return 1
    print("Keine Befunde, keine offene Matrixzeile.")

    if "--matrix" in sys.argv:
        schreibe_matrix(marken, einsatzzahl, len(dienstdateien), lauf.pruefungen, len(zeitpunkte))
        print(f"matrix_abgleich.md geschrieben.")
    return 0


def schreibe_matrix(marken: dict[str, list[str]], einsatzzahl: int, dienstzahl: int,
                    pruefungen: int, zeitstempel: int) -> None:
    """Matrix-Abgleich als Markdown schreiben.

    ERZEUGT STATT GEPFLEGT. Ein von Hand geführtes Abgleichsdokument ist nach
    der zweiten Änderung an den Quelldaten falsch, ohne dass es jemand merkt —
    und es behauptet dann eine Abdeckung, die es nicht mehr gibt. Diese Datei
    entsteht deshalb aus denselben Marken, gegen die pruefen.py prüft.
    """
    zeilen = [
        "# Matrix-Abgleich — welcher Einsatz belegt welche Zeile",
        "",
        "**Diese Datei wird erzeugt, nicht gepflegt.** Sie entsteht aus",
        "`pruefen.py --matrix` und damit aus denselben Marken, gegen die das",
        "Prüfskript prüft. Wer sie von Hand ändert, verliert die Änderung beim",
        "nächsten Lauf — und das ist der Zweck: Ein handgeführtes",
        "Abgleichsdokument ist nach der zweiten Änderung an den Quelldaten",
        "falsch und behauptet trotzdem weiter eine Abdeckung, die es nicht",
        "mehr gibt.",
        "",
        "Grundlage ist die Abdeckungsmatrix aus Abschnitt 5 des Konzepts",
        "*P1 — Referenzdatensatz und Demo-Account*.",
        "",
        "## Umfang",
        "",
        f"| Größe | Wert |",
        f"|---|---|",
        f"| Dienste | {dienstzahl} |",
        f"| Einsätze | {einsatzzahl} |",
        f"| Matrixzeilen | {len(MATRIX)} |",
        f"| Zeitstempel auf Existenz und Eindeutigkeit geprüft | {zeitstempel} |",
        f"| Einzelprüfungen im Lauf | {pruefungen} |",
        "",
        "## Zuordnung",
        "",
        "„Strukturell\" heißt: Die Zeile wird nicht über eine Marke belegt,",
        "sondern über den Bestand selbst geprüft — etwa ob wirklich alle zehn",
        "Reanimationsarten vorkommen.",
        "",
        "| Dimension | Anforderung | Belegt durch |",
        "|---|---|---|",
    ]
    letzte_dim = None
    for dimension, anforderung, ms in MATRIX:
        if ms:
            treffer: list[str] = []
            for m in ms:
                treffer.extend(marken.get(m, []))
            # Reihenfolge bewahren, Doppel entfernen
            gesehen, eindeutig = set(), []
            for x in treffer:
                if x not in gesehen:
                    gesehen.add(x)
                    eindeutig.append(x)
            if len(eindeutig) > 4:
                belegt = ", ".join(f"`{x}`" for x in eindeutig[:4])
                belegt += f" … (+{len(eindeutig) - 4})"
            else:
                belegt = ", ".join(f"`{x}`" for x in eindeutig)
        else:
            belegt = "*strukturell geprüft*"
        spalte = dimension if dimension != letzte_dim else ""
        letzte_dim = dimension
        zeilen.append(f"| {spalte} | {anforderung} | {belegt} |")
    zeilen.append("")
    (HIER / "matrix_abgleich.md").write_text("\n".join(zeilen), "utf-8")


if __name__ == "__main__":
    sys.exit(main())
