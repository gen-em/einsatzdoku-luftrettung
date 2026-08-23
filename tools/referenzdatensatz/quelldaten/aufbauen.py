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

HIER = pathlib.Path(__file__).resolve().parent
TZ = ZoneInfo("Europe/Berlin")
UTC = ZoneInfo("UTC")
SAMEN = 20260101          # fester Zufallssamen (B2-Abnahme: Determinismus)

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
                 standort: str, faehigkeiten: list[str], monat: int) -> None:
        self.z = zufall
        self.art = art
        self.standort = standort
        self.faehigkeiten = faehigkeiten
        self.monat = monat
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

    def _adresse(self) -> tuple[str, float, float]:
        ort, lat, lon = self.z.choice(self.orte)
        strasse = self.z.choice(katalog.STRASSEN)
        nr = self.z.randint(1, 128)
        plz = self.z.choice(katalog.PLZ)
        # Leichte Streuung, damit nicht zwanzig Einsaetze auf demselben Punkt liegen.
        return (f"{strasse} {nr}, {plz} {ort}",
                round(lat + self.z.uniform(-0.004, 0.004), 5),
                round(lon + self.z.uniform(-0.004, 0.004), 5))

    def bauen(self, beginn: datetime, geraet: int, max_dauer: int) -> dict:
        dx, site, transport, schockraum, bergtauglich = self.z.choice(self.bilder)
        addr, lat, lon = self._adresse()

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
        phasen = [[nr, nach_lokal(beginn + timedelta(minutes=m))] for nr, m in p]

        # --- Zielklinik ------------------------------------------------------
        dest = dest_lat = dest_lon = None
        if transport in ("air", "ground"):
            k = self.z.choice(self.kliniken)
            dest, dest_lat, dest_lon = k["name"], k["lat"], k["lon"]

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

        route = ["basis", "ort", "ziel", "basis"] if dest_lat is not None else ["basis", "ort", "basis"]

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
        belegt = [(nach_utc(e["beginn"]), nach_utc(e["ende"] or dn["ende"])) for e in fest]
        von, bis = nach_utc(dn["beginn"]), nach_utc(dn["ende"])

        werk = Werk(z, dn["art"], stammdaten, dn["standort"],
                    rm["faehigkeiten"], int(dn["day"][5:7]))
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
