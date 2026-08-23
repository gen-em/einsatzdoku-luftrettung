#!/usr/bin/env python3
"""Diensttage auf ihre Zielzahl auffuellen und die Ruhesegmente bauen.

WAS DIESES SKRIPT IST UND WAS NICHT. Die Quelle des Referenzdatensatzes sind
die Dateien unter `dienste/`. Die Faelle, die eine Zeile der Abdeckungsmatrix
belegen, stehen dort VON HAND und tragen jeweils eine Begruendung. Dieses
Skript ruehrt sie nicht an — es fuellt den Rest: den Betriebsalltag, der einen
Dienst erst nach einem Dienst aussehen laesst.

Erzeugte Einsaetze tragen `"erzeugt": true`. Nur sie werden bei einem erneuten
Lauf ersetzt; alles Handgeschriebene bleibt. Das Skript ist damit beliebig oft
ausfuehrbar und liefert bei gleichem Samen dasselbe Ergebnis.

DIE RUHESEGMENTE ENTSTEHEN IMMER NEU. Sie sind die Zwischenraeume zwischen den
Einsaetzen und lassen sich gar nicht von Hand pflegen, ohne beim naechsten
Einsatz falsch zu werden. Ausnahme ist das letzte Segment eines Dienstes, den
niemand beendet hat (D16): Es bleibt offen, weil das ein Pruefall ist.

ZEITEN WERDEN IN UTC GERECHNET und erst am Schluss nach Ortszeit gewandelt.
Anders waere der Dienst ueber die Zeitumstellung (D06, D14) nicht sauber zu
belegen: In Ortszeit gerechnet entstuenden Zeitpunkte, die es an dem Tag nicht
gibt oder die es zweimal gibt.

Aufruf:  python3 aufbauen.py
"""
from __future__ import annotations

import json
import pathlib
import random
import sys
from datetime import datetime, timedelta
from zoneinfo import ZoneInfo

import katalog
import wegpunkte


HIER = pathlib.Path(__file__).resolve().parent
TZ = ZoneInfo("Europe/Berlin")

# Fahrzeiten-Tafel: echte Strassenzeiten fuer die Bodeneinsaetze. Sie wird
# einmalig geholt (../generator/routen/fahrzeiten_holen.py) und eingecheckt.
# OHNE SIE waehlt der Generator den Einsatzort nach der Luftlinie -- und im
# Voralpenland liegt ein Ort 15 km Luftlinie und 40 km Fahrstrecke entfernt,
# weil das Tal in die andere Richtung geht. Dabei entstanden Fahrten mit
# 205 km/h.
_TAFEL_PFAD = HIER.parent / "generator" / "routen" / "fahrzeiten.json"
UTC = ZoneInfo("UTC")
SAMEN = 20260101          # fester Zufallssamen (B2-Abnahme: Determinismus)

# Reisegeschwindigkeit, aus der sich der ERREICHBARE Radius ergibt.
# Ohne diese Bindung waehlte der Generator den Einsatzort frei aus dem
# Katalog, und die Phasen gaben ihm dafuer sieben Minuten -- der Hubschrauber
# floege dann 385 km/h. Ein Referenzdatensatz mit unmoeglichen
# Geschwindigkeiten ist als Anschauung wertlos und als Regressionsgrundlage
# irrefuehrend.
# LUFTLINIE, nicht Strassenkilometer. Ein NEF faehrt 65 km/h auf der Strasse,
# aber die Strasse ist im Voralpenland rund anderthalbmal so lang wie die
# Luftlinie -- gemessen an der Luftlinie kommt es also deutlich langsamer
# voran. Wer hier die Strassengeschwindigkeit einsetzt, waehlt Einsatzorte,
# die in der vorgesehenen Zeit nicht zu erreichen sind.
TEMPO_KMH = {"air": 190.0, "ground": 42.0}

PUFFER_MIN = 12           # Mindestabstand zwischen zwei Einsaetzen
MIN_LUECKE = 46           # kuerzeste Luecke, in die noch ein Einsatz passt


def nach_utc(s: str) -> datetime:
    return datetime.strptime(s, "%Y-%m-%d %H:%M").replace(tzinfo=TZ).astimezone(UTC)


def nach_lokal(t: datetime) -> str:
    return t.astimezone(TZ).strftime("%Y-%m-%d %H:%M")


def lokal_brauchbar(t: datetime) -> bool:
    """Ortszeit existiert und ist eindeutig (Zeitumstellung)."""
    s = t.astimezone(TZ).strftime("%Y-%m-%d %H:%M")
    roh = datetime.strptime(s, "%Y-%m-%d %H:%M")
    a = roh.replace(tzinfo=TZ, fold=0)
    b = roh.replace(tzinfo=TZ, fold=1)
    if a.utcoffset() != b.utcoffset():
        return False
    return a.astimezone(UTC).astimezone(TZ).replace(tzinfo=None) == roh


class Werk:
    """Erzeugt einen Einsatz aus dem Katalog — plausibel, nicht kunstvoll."""

    def __init__(self, zufall: random.Random, art: str, stammdaten: dict,
                 standort: str, faehigkeiten: list[str], monat: int,
                 basis: tuple[float, float]) -> None:
        self.z = zufall
        self.art = art
        self.standort = standort
        self.faehigkeiten = faehigkeiten
        self.monat = monat
        self.basis = basis
        self.tafel = (json.loads(_TAFEL_PFAD.read_text("utf-8"))
                      if art == "ground" and _TAFEL_PFAD.exists() else {})
        self.tempo = TEMPO_KMH[art]
        self.orte = katalog.ORTE_LUFT if art == "air" else katalog.ORTE_BODEN
        self.bilder = katalog.BILDER_LUFT if art == "air" else katalog.BILDER_BODEN
        self.notizen = list(katalog.NOTIZEN_LUFT if art == "air" else katalog.NOTIZEN_BODEN)
        if monat in (11, 12, 1, 2, 3):
            self.notizen += katalog.NOTIZEN_WINTER
        # NUR KLINIKEN MIT KOORDINATE. Ein erzeugter Transport, dessen Ziel
        # keine Koordinate traegt, haette eine Spur, die die Klinik auslaesst,
        # obwohl die Phasen 7 und 8 dort stehen -- ein sichtbarer Widerspruch.
        # Der Prueffall "Zielklinik ohne Koordinate" bleibt den von Hand
        # geschriebenen Einsaetzen vorbehalten; die tragen dafuer ein
        # `spur.ziel` (siehe FORMAT.md).
        self.kliniken = [k for k in stammdaten["zielkliniken"]
                         if k["standort"] == standort and k["lat"] is not None]
        self.mittel = [m["name"] for m in stammdaten["weitere_rettungsmittel"]
                       if m["standort"] == standort]
        self.bereitschaften = [b["name"] for b in stammdaten["bereitschaften"]
                               if b["standort"] == standort]
        self.hat_koordinaten = any(
            s["lat"] is not None for s in stammdaten["standorte"] if s["name"] == standort)

    def _ort_in_reichweite(self, von: tuple[float, float], minuten: float,
                           kandidaten=None):
        """Ort, der in der verfuegbaren Zeit ueberhaupt zu erreichen ist.

        Ohne diese Auswahl entstanden Einsaetze, bei denen der Hubschrauber
        45 km in sieben Minuten zuruecklegte. Die Phasen sind die Wahrheit
        ueber den Ablauf (FORMAT.md) — also muss sich der ORT nach ihnen
        richten und nicht umgekehrt.
        """
        liste = kandidaten if kandidaten is not None else self.orte

        if self.tafel:
            # BODEN: echte Fahrzeit aus der Tafel. 0,85 als Reserve -- die
            # Phasen geben die Zeit vor, und ein Einsatz, der sie exakt
            # ausschoepft, hat keinen Spielraum fuer Ampeln und Ortsdurchfahrt.
            mit_zeit = []
            for n, a, b in liste:
                s = f"{von[0]:.4f},{von[1]:.4f}>{a:.4f},{b:.4f}"
                eintrag = self.tafel.get(s)
                if eintrag:
                    mit_zeit.append((eintrag["dauer_s"], (n, a, b)))
            if mit_zeit:
                passend = [x for d, x in mit_zeit if 120.0 <= d <= minuten * 60.0 * 0.85]
                return self.z.choice(passend) if passend else min(mit_zeit)[1]

        radius = max(self.tempo * minuten / 60.0, 3.0) * 1000.0
        mit_abstand = [(wegpunkte.abstand_m(von[0], von[1], a, b), (n, a, b))
                       for n, a, b in liste]
        passend = [x for d, x in mit_abstand if 1500.0 <= d <= radius]
        if passend:
            return self.z.choice(passend)
        # Nichts in Reichweite: den naechstgelegenen nehmen. Er ist dann
        # naeher als noetig, aber nie weiter als moeglich.
        return min(mit_abstand)[1]

    def _adresse(self, ort) -> tuple[str, float, float]:
        name, lat, lon = ort
        strasse = self.z.choice(katalog.STRASSEN)
        nr = self.z.randint(1, 128)
        plz = self.z.choice(katalog.PLZ)
        # Leichte Streuung, damit nicht zwanzig Einsaetze auf demselben Punkt liegen.
        return (f"{strasse} {nr}, {plz} {name}",
                round(lat + self.z.uniform(-0.004, 0.004), 5),
                round(lon + self.z.uniform(-0.004, 0.004), 5))

    def bauen(self, beginn: datetime, geraet: int, max_dauer: int) -> dict:
        dx, site, transport, schockraum, bergtauglich = self.z.choice(self.bilder)

        # --- Phasen ------------------------------------------------------
        #
        # DER EINSATZ WAECHST IN DIE LUECKE. Ein festes Zufallsmass ergab
        # Einsaetze, die nicht mehr in den Zwischenraum passten -- und damit
        # Diensttage, die unter ihrer Zielzahl blieben. Stattdessen: ein
        # Grundmuster in Minuten, gestreckt auf die gewuenschte Dauer, und die
        # ist durch den verfuegbaren Platz gedeckelt.
        if transport in ("air", "ground"):
            muster = [(2, 0), (3, 5), (4, 16), (5, 20), (6, 42), (7, 60), (8, 68), (9, 78)]
        else:
            muster = [(2, 0), (3, 5), (4, 16), (5, 20), (9, 50)]
        grund = muster[-1][1]
        wunsch = self.z.randint(52, 96) if transport in ("air", "ground") else self.z.randint(38, 68)
        dauer = max(46 if transport in ("air", "ground") else 34,
                    min(wunsch, max_dauer))
        f = dauer / grund
        p, letzte = [], -1
        for nr, m in muster:
            wert = round(m * f)
            wert = max(wert, letzte + 1)       # streng steigend, auch nach dem Runden
            letzte = wert
            p.append((nr, wert))
        ende = p[-1][1]
        nach_minute = dict(p)

        # --- Einsatzort: so weit, wie die Anfahrtszeit hergibt --------------
        anfahrt = nach_minute[4] - nach_minute[3]
        ort = self._ort_in_reichweite(self.basis, anfahrt)
        addr, lat, lon = self._adresse(ort)
        phasen = [[nr, nach_lokal(beginn + timedelta(minutes=m))] for nr, m in p]

        # --- Zielklinik ------------------------------------------------------
        dest = dest_lat = dest_lon = None
        if transport in ("air", "ground") and self.kliniken:
            fahrt = nach_minute[7] - nach_minute[6]
            # Nachgeschlagen wird von der KATALOGKOORDINATE aus, nicht von der
            # gestreuten Adresse: Die Tafel kennt nur die Katalogpunkte.
            gewaehlt = self._ort_in_reichweite(
                (ort[1], ort[2]), fahrt,
                kandidaten=[(k["name"], k["lat"], k["lon"]) for k in self.kliniken])
            dest, dest_lat, dest_lon = gewaehlt

        # --- Winde und Bergwacht: nur, wo das Rettungsmittel es kann ---------
        winch = bergwacht = 0
        cycles = cycles_pat = None
        airload = 0
        bw_unit = bw_info = None
        if bergtauglich and "bergwacht" in self.faehigkeiten and self.z.random() < 0.55:
            bergwacht = 1
            bw_unit = self.z.choice(self.bereitschaften)
        if bergtauglich and "winch" in self.faehigkeiten and self.z.random() < 0.35:
            winch = 1
            cycles = self.z.randint(1, 4)
            cycles_pat = self.z.randint(0, min(2, cycles))
            airload = 1 if self.z.random() < 0.3 else 0

        mittel = self.z.sample(self.mittel, k=self.z.randint(0, min(2, len(self.mittel))))
        if bergwacht and bw_unit:
            mittel = [bw_unit] + mittel

        # --- Geschuetzte Angaben ---------------------------------------------
        mit_geburtsdatum = self.z.random() < 0.45
        alter = self.z.randint(2, 94)
        geburtsjahr = 2026 - alter
        geschuetzt = {
            "dx": dx,
            "dob": (f"{geburtsjahr}-{self.z.randint(1,12):02d}-{self.z.randint(1,28):02d}"
                    if mit_geburtsdatum else None),
            "age": None if mit_geburtsdatum else alter,
            "mission_no": None,          # wird am Ende chronologisch vergeben
            "loc": {"addr": addr, "lat": lat, "lon": lon},
            "site_desc": site,
            "start": None,
        }

        # --- Reanimation (selten) --------------------------------------------
        rea = []
        if "Kreislaufstillstand" in dx or "Vorderwandinfarkt" in dx or self.z.random() < 0.06:
            verlauf = self.z.choice(katalog.REA_VERLAEUFE)
            bei_pat = dict(p)[5]          # Minute der Phase 5 (Ankunft PatientIn)
            start = bei_pat + self.z.randint(1, 4)
            if start + verlauf[-1][1] < ende:
                rea = [{"beginn": nach_lokal(beginn + timedelta(minutes=start)),
                        "ereignisse": [[typ, nach_lokal(beginn + timedelta(minutes=start + dt))]
                                       for typ, dt in verlauf]}]

        # ENDET AN DER ZIELKLINIK, nicht an der Basis: Der Rueckweg gehoert
        # in das Ruhe-Segment danach (Model.mc, _endMission ->
        # _startRestSegment). Solange er zum Einsatz gezaehlt wurde, musste
        # er in die Spanne zwischen Uebergabe und Endzeit passen -- und
        # dabei entstanden Rueckfluege mit 666 km/h.
        route = ["basis", "ort", "ziel"] if dest_lat is not None else ["basis", "ort", "basis"]

        return {
            "client_ref": f"m-{geraet}-{self.z.randrange(10**9, 10**10)}",
            "kanal": "ingest", "nachtrag": True, "papierkorb": None, "erzeugt": True,
            "beginn": nach_lokal(beginn),
            "ende": nach_lokal(beginn + timedelta(minutes=ende)),
            "final": True,
            "phasen": phasen,
            "route": route,
            "rea": rea,
            "felder": {
                "transport_mode": transport,
                "na_escort": 1 if transport in ("air", "ground") else 0,
                "transport_dest": dest, "dest_lat": dest_lat, "dest_lon": dest_lon,
                "schockraum": schockraum if transport in ("air", "ground") else 0,
                "false_alarm": 0,
                # Eine innerklinische Uebernahme IST ein Sekundaertransport.
                # Ohne diesen Haken widerspraeche sich der Einsatz selbst.
                "secondary": 1 if site.startswith("Innerklinische") else 0,
                "start_src": "base" if self.hat_koordinaten else None,
                "winch": winch, "winch_cycles": cycles, "winch_cycles_pat": cycles_pat,
                "winch_airload": airload,
                "bergwacht": bergwacht, "bw_unit": bw_unit, "bw_info": bw_info,
                "other_ema": None, "other_resources": mittel,
                "crew_override": 0, "crew": {},
                "notes": self.z.choice(self.notizen),
            },
            "geschuetzt": geschuetzt,
            "abdeckung": [],
        }, ende


def freie_luecken(von: datetime, bis: datetime,
                  belegt: list[tuple[datetime, datetime]]) -> list[tuple[datetime, datetime]]:
    luecken, zeiger = [], von
    for a, b in sorted(belegt):
        if a - zeiger >= timedelta(minutes=MIN_LUECKE):
            luecken.append((zeiger, a - timedelta(minutes=PUFFER_MIN)))
        zeiger = max(zeiger, b + timedelta(minutes=PUFFER_MIN))
    if bis - zeiger >= timedelta(minutes=MIN_LUECKE):
        luecken.append((zeiger, bis))
    return luecken


def main() -> int:
    stammdaten = json.loads((HIER / "stammdaten.json").read_text("utf-8"))
    fahrzeuge = {r["name"]: r for r in stammdaten["rettungsmittel"]}
    refs: set[str] = set()
    erzeugt_gesamt = ruhe_gesamt = 0

    for pfad in sorted((HIER / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        dn, ziel = d["dienst"], d.get("ziel_einsaetze", 0)
        z = random.Random(f"{SAMEN}-{d['kennung']}")
        rm = fahrzeuge[dn["rettungsmittel"]]
        geraet = 11 if dn["art"] == "air" else 12

        fest = [e for e in d["einsaetze"] if not e.get("erzeugt")]
        standorte_map = {s["name"]: s for s in stammdaten["standorte"]}
        basis_k = wegpunkte.basis_von(dn, standorte_map)

        def rueckweg_min(einsatz: dict, vorheriger: dict | None) -> float:
            """Zeit, die das Fahrzeug braucht, um von seinem Einsatzende
            zurueck an den Standort zu kommen.

            Sie gehoert VOR den naechsten Einsatz und nicht in den davor:
            Nach einem Transport 80 km weit steht der Hubschrauber an der
            Klinik, und der naechste Einsatz kann nicht siebzehn Minuten
            spaeter am Standort beginnen."""
            k = [x for _, x in wegpunkte.aufloesen(dn, einsatz, vorheriger, standorte_map) if x]
            if not k or not basis_k:
                return 0.0
            weg = wegpunkte.abstand_m(*k[-1], *basis_k) / 1000.0
            return weg / TEMPO_KMH[dn["art"]] * 60.0

        belegt = []
        vorher = None
        for e in sorted(fest, key=lambda x: x["beginn"]):
            ende = nach_utc(e["ende"] or dn["ende"])
            belegt.append((nach_utc(e["beginn"]),
                           ende + timedelta(minutes=rueckweg_min(e, vorher))))
            vorher = e
        von, bis = nach_utc(dn["beginn"]), nach_utc(dn["ende"])

        basis = wegpunkte.basis_von(dn, {s["name"]: s for s in stammdaten["standorte"]})
        werk = Werk(z, dn["art"], stammdaten, dn["standort"],
                    rm["faehigkeiten"], int(dn["day"][5:7]), basis)
        neue: list[dict] = []
        fehlend = ziel - len(fest)
        for a, b in freie_luecken(von, bis, belegt):
            zeiger = a
            while fehlend > 0 and b - zeiger >= timedelta(minutes=MIN_LUECKE):
                start = zeiger + timedelta(minutes=z.randint(4, 14))
                platz = int((b - start).total_seconds() // 60)
                if platz < 46:
                    break
                e, dauer = werk.bauen(start, geraet, platz)
                schluss = start + timedelta(minutes=dauer)
                alle = [start, schluss] + [
                    datetime.strptime(s, "%Y-%m-%d %H:%M").replace(tzinfo=TZ)
                    for _, s in e["phasen"]]
                if not all(lokal_brauchbar(x) for x in alle):
                    # Der Einsatz laege in der uebersprungenen oder der
                    # doppelten Stunde einer Zeitumstellung: eine Stunde
                    # weiterruecken statt einen unbrauchbaren Zeitpunkt
                    # zu schreiben.
                    zeiger = start + timedelta(minutes=60)
                    continue
                if e["client_ref"] in refs:
                    continue
                refs.add(e["client_ref"])
                neue.append(e)
                fehlend -= 1
                zeiger = schluss + timedelta(minutes=PUFFER_MIN)
            if fehlend <= 0:
                break

        d["einsaetze"] = sorted(fest + neue, key=lambda e: e["beginn"])
        erzeugt_gesamt += len(neue)

        # --- Ruhesegmente: die Zwischenraeume ------------------------------
        offen_am_ende = d["kennung"] == "D16"
        fenster = sorted((nach_utc(e["beginn"]), nach_utc(e["ende"] or dn["ende"]))
                         for e in d["einsaetze"])
        segmente, zeiger, lauf = [], von, 0
        def segment(a: datetime, b: datetime | None) -> None:
            nonlocal lauf
            lauf += 1
            segmente.append({
                "client_ref": f"r-{geraet}-{z.randrange(10**9, 10**10)}",
                "beginn": nach_lokal(a),
                "ende": nach_lokal(b) if b else None,
                "final": b is not None,
            })
        for a, b in fenster:
            if a - zeiger >= timedelta(minutes=5):
                segment(zeiger, a)
            zeiger = max(zeiger, b)
        if bis - zeiger >= timedelta(minutes=5):
            segment(zeiger, None if offen_am_ende else bis)
        d["ruhesegmente"] = segmente
        ruhe_gesamt += len(segmente)

        pfad.write_text(json.dumps(d, ensure_ascii=False, indent=2) + "\n", "utf-8")
        print(f"  {d['kennung']}  {dn['day']}  {dn['art']:6s}  "
              f"{len(fest)} fest + {len(neue)} erzeugt = {len(d['einsaetze'])}  "
              f"({len(segmente)} Ruhesegmente)")

    # --- Einsatznummern chronologisch, ueber den ganzen Bestand ----------
    #
    # Eine Einsatznummer kommt von der Leitstelle und laeuft im Jahr hoch.
    # Sie erst hier zu vergeben ist der einzige Weg, das ueber Tagesgrenzen
    # hinweg richtig hinzubekommen -- innerhalb eines Tages weiss das Skript
    # noch nicht, der wievielte Einsatz des Jahres es ist. Die von Hand
    # geschriebenen Prueffaelle behalten ihre eigenen Nummern (0031..0796);
    # die erzeugten laufen ab 1001 und sind daran erkennbar.
    alle = []
    for pfad in sorted((HIER / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        for e in d["einsaetze"]:
            if e.get("erzeugt"):
                alle.append((e["beginn"], pfad, e["client_ref"]))
    alle.sort()
    zuordnung = {ref: f"2026-{1000 + i + 1:04d}" for i, (_, _, ref) in enumerate(alle)}
    for pfad in sorted((HIER / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        for e in d["einsaetze"]:
            if e.get("erzeugt"):
                e["geschuetzt"]["mission_no"] = zuordnung[e["client_ref"]]
        pfad.write_text(json.dumps(d, ensure_ascii=False, indent=2) + "\n", "utf-8")

    print(f"\n{erzeugt_gesamt} Einsätze erzeugt, {ruhe_gesamt} Ruhesegmente gebaut, "
          f"{len(zuordnung)} Einsatznummern chronologisch vergeben.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
