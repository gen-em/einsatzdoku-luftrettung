#!/usr/bin/env python3
"""Einspiellauf des Referenzdatensatzes (Arbeitspaket B3).

Spielt den erzeugten Datensatz ueber die REGULAEREN Wege in eine
Installation ein — kein roher SQL-Weg (R4). Die vier Wege sind die aus
B-01 des Konzepts:

  1. `ingest.php`        Geraeteschnittstelle: Diensttage, Einsaetze,
                         Ruhe-Segmente, Phasen, Reanimationen, Spur
  2. `api/day.php`       Zuordnung der neutralen Diensttage
  3. `einsatz_form.php`  Nachtragen der Felder und der geschuetzten Angaben,
                         `pat_blob` als `edk1:`-Chiffretext
  4. Weboberflaeche      Papierkorb (`einsatz_loeschen.php`,
                         `diensttag_loeschen.php`, `papierkorb.php`)

Der CSV-Import laeuft bewusst NICHT hier, sondern im Browser (B4).

Stufen einzeln aufrufbar:
    python3 einspielen.py --stufen konto,stammdaten,geraet
    python3 einspielen.py                      (alle)
    python3 einspielen.py --basis https://…    (andere Installation)
"""
from __future__ import annotations

import argparse
import json
import pathlib
import re
import sys
import time
from datetime import datetime, timezone
from zoneinfo import ZoneInfo

HIER = pathlib.Path(__file__).resolve().parent
WURZEL = HIER.parent
sys.path.insert(0, str(HIER))
sys.path.insert(0, str(WURZEL / "generator"))
sys.path.insert(0, str(WURZEL / "quelldaten"))

import krypto           # noqa: E402
import sitzung as sitzungsmodul   # noqa: E402
from sitzung import fehlertext    # noqa: E402  (eine Stelle, siehe dort)

QUELLE = WURZEL / "quelldaten"
AUSGABE = WURZEL / "generator" / "ausgabe"
TZ = ZoneInfo("Europe/Berlin")

# Zugangsdaten des Referenzkontos (F-P1-01). Beide sind ueber die Befehlszeile
# umstellbar: Die KREISLAUFPRUEFUNG (B5) legt ein zweites, frisches Konto an und
# fuellt dort nur die Stammdaten, damit der CSV-Import ueberhaupt ein
# Rettungsmittel zur Auswahl hat. Ohne diesen Schalter muesste sie das Konto
# nachbauen -- und damit einen zweiten Weg pflegen.
DEMO_EMAIL = "demo@gen-em.org"
DEMO_PASSWORT = "nadokudemo0815"

ALLE_STUFEN = ["konto", "stammdaten", "geraet", "ingest", "zuordnen",
               "nachtragen", "manuell", "papierkorb", "sperrliste"]


class Lauf:
    """Zustand ueber die Stufen hinweg — und das Messprotokoll (E-P1-14)."""

    def __init__(self, basis: str, zustand: pathlib.Path) -> None:
        self.basis = basis
        self.zustand_datei = zustand
        self.zustand = json.loads(zustand.read_text("utf-8")) if zustand.exists() else {}
        self.s: sitzungsmodul.Sitzung | None = None
        self.messung: list[dict] = self.zustand.get("messung", [])

    def sichern(self) -> None:
        self.zustand["messung"] = self.messung
        self.zustand_datei.write_text(
            json.dumps(self.zustand, ensure_ascii=False, indent=2) + "\n", "utf-8")

    def demo_sitzung(self) -> sitzungsmodul.Sitzung:
        if self.s is None or self.s.email != DEMO_EMAIL:
            self.s = sitzungsmodul.Sitzung(self.basis).anmelden(DEMO_EMAIL, DEMO_PASSWORT)
        return self.s


def melde(text: str) -> None:
    print(text, flush=True)


# ---------------------------------------------------------------- 1. Konto
def stufe_konto(lauf: Lauf, admin: tuple[str, str]) -> None:
    """Demo-Konto ueber den REGULAEREN Einladungsweg anlegen (E-P1-10).

    Nicht per SQL und nicht mit einem Sonderendpunkt: Das Konto entsteht so,
    wie jedes andere entsteht — Admin legt an, es gibt einen Einrichtungslink,
    und Passwort samt Schluesselmaterial entstehen im BROWSER. Nur so passen
    Chiffretexte und Konto auf jeder Installation zusammen.
    """
    a = sitzungsmodul.Sitzung(lauf.basis).anmelden(*admin)
    html = a.get("admin_users.php").text
    if DEMO_EMAIL in html:
        melde(f"  Konto {DEMO_EMAIL} besteht bereits.")
        return
    a.csrf_auffrischen("admin_users.php")
    antwort = a.post("admin_users.php", {
        "csrf": a.csrf, "action": "user_add", "email": DEMO_EMAIL, "role": "user"})
    m = re.search(r"pw_handling\.php\?token=([0-9a-f]{64})", antwort.text)
    if not m:
        raise RuntimeError("Kontoanlage ohne Einrichtungslink: "
                           + (fehlertext(antwort.text) or "unbekannt"))
    lauf.zustand["einrichtungslink"] = f"{lauf.basis}/pw_handling.php?token={m.group(1)}"
    melde(f"  Konto angelegt. Einrichtungslink steht im Zustand.")
    melde(f"  NÄCHSTER SCHRITT (Browser): node passwort_setzen.mjs "
          f"'{lauf.zustand['einrichtungslink']}' '{DEMO_PASSWORT}' rc.json")


# ----------------------------------------------------------- 2. Stammdaten
def stufe_stammdaten(lauf: Lauf) -> None:
    s = lauf.demo_sitzung()
    st = json.loads((QUELLE / "stammdaten.json").read_text("utf-8"))

    def speichern(daten: dict) -> None:
        # MIT ?t: Seit P3/O2 zeigt einstellungen.php ohne `t` die UEBERSICHT
        # und beendet sich VOR der POST-Verarbeitung (E-P3-11) — ein POST ohne
        # Parameter versandete stillschweigend (Fund F-P3-AF in O5).
        s.csrf_auffrischen("einstellungen.php?t=standorte")
        antwort = s.post("einstellungen.php?t=standorte", {**daten, "csrf": s.csrf})
        # Fehlerkasten seit P3 als Meldungs-Baustein; die alte Klasse davor.
        fehler = fehlertext(antwort.text)
        if fehler:
            raise RuntimeError(f"{daten.get('action')}: {fehler}")

    for b in st["standorte"]:
        speichern({"action": "base_save", "name": b["name"],
                   "lat": "" if b["lat"] is None else b["lat"],
                   "lon": "" if b["lon"] is None else b["lon"]})
    ids = kennungen(s)
    for b in st["standorte"]:
        if b["standard"]:
            speichern({"action": "base_default", "id": ids["standorte"][b["name"]]})

    for r in st["rettungsmittel"]:
        speichern({"action": "veh_save", "name": r["name"], "kind": r["art"],
                   "base_id": ids["standorte"][r["standort"]],
                   "roles[]": r["rollen"], "caps[]": r["faehigkeiten"]})
    ids = kennungen(s)
    for r in st["rettungsmittel"]:
        if r["standard"]:
            speichern({"action": "veh_default", "id": ids["rettungsmittel"][r["name"]]})

    for c in st["besatzung"]:
        speichern({"action": "crew_save", "role": c["rolle"], "name": c["name"],
                   "base_id": ids["standorte"][c["standort"]]})
    for k in st["zielkliniken"]:
        speichern({"action": "td_save", "name": k["name"],
                   "lat": "" if k["lat"] is None else k["lat"],
                   "lon": "" if k["lon"] is None else k["lon"],
                   "base_id": ids["standorte"][k["standort"]]})
    for b in st["bereitschaften"]:
        speichern({"action": "bw_save", "name": b["name"],
                   "base_id": ids["standorte"][b["standort"]]})
    for w in st["weitere_rettungsmittel"]:
        speichern({"action": "res_save", "name": w["name"],
                   "base_id": ids["standorte"][w["standort"]]})

    lauf.zustand["stammdaten_ids"] = kennungen(s)
    melde(f"  {len(st['standorte'])} Standorte, {len(st['rettungsmittel'])} Rettungsmittel, "
          f"{len(st['besatzung'])} Vorbelegungen, {len(st['zielkliniken'])} Zielkliniken, "
          f"{len(st['bereitschaften'])} Bereitschaften, "
          f"{len(st['weitere_rettungsmittel'])} weitere Rettungsmittel angelegt.")


def kennungen(s) -> dict:
    """Kennungen von Standort und Rettungsmittel aus den Auswahllisten lesen.

    Ueber `diensttag_neu.php` und nicht ueber die Datenbank: Die Seite listet
    genau das, was die Anwendung dem Konto anbietet — einschliesslich
    zentraler Eintraege und der Standortbindung.
    """
    html = s.get("diensttag_neu.php").text

    def lesen(feld: str, aufbereiten) -> dict:
        # NICHT auf "<select name=..." verankern. Seit P3/O11 (Web 9.12.0)
        # rendert der Baustein ui_feld() das Feld als
        # `<select class="feld-eingabe" id="..." name="...">` — `name` steht
        # also nicht mehr vorn. Der alte Ausdruck fand nichts mehr, lieferte
        # ein leeres Verzeichnis, und die Stammdaten-Stufe brach zwei Zeilen
        # spaeter mit einem KeyError auf den STANDORTNAMEN ab. Die Ursache
        # stand nirgends: Es sah aus, als fehle ein Standort, der in der
        # Datenbank laengst lag (F-S2-A).
        #
        # Jetzt wird das oeffnende Tag als Ganzes gesucht und darin nach dem
        # Namen gefragt. Damit ist die Reihenfolge der Attribute egal.
        block = re.search(r'<select\b[^>]*\bname="' + re.escape(feld)
                          + r'"[^>]*>.*?</select>', html, re.S)
        if not block:
            # Kein leeres Verzeichnis zurueckgeben. Ein leeres Verzeichnis ist
            # von "die Liste ist leer" nicht zu unterscheiden und verschiebt
            # den Abbruch an eine Stelle, die die Ursache nicht mehr kennt.
            raise RuntimeError(
                f"Auswahlliste '{feld}' steht nicht in diensttag_neu.php. "
                "Vermutlich hat sich das Markup geaendert — dieser Leser haengt "
                "an der Seite und muss dann nachgezogen werden.")
        gefunden = {}
        for wert, roh in re.findall(r'<option value="(\d+)"[^>]*>\s*(.*?)\s*</option>',
                                    block.group(0), re.S):
            gefunden[aufbereiten(re.sub(r"\s+", " ", roh).strip())] = int(wert)
        return gefunden

    # Der Standort steht schlicht da (evtl. mit „ (zentral)").
    standorte = lesen("base_id", lambda x: x.replace(" (zentral)", "").strip())
    # Das Rettungsmittel traegt ein Artzeichen davor und den Standort dahinter:
    # „🚁 Alpenfalke 1 · Luftrettungsstation Hochkreuth". Beides gehoert zur
    # ANZEIGE und nicht zum Namen.
    mittel = lesen("vehicle_id",
                   lambda x: x.split(" · ")[0].lstrip("🚁🚑🚒🚐 ").strip())
    return {"standorte": standorte, "rettungsmittel": mittel}


# --------------------------------------------------------------- 3. Geraet
def stufe_geraet(lauf: Lauf) -> None:
    s = lauf.demo_sitzung()
    if lauf.zustand.get("geraete"):
        melde("  Geräte bestehen bereits.")
        return
    # ZUERST AUFRAEUMEN. Ein Geraeteschluessel wird GENAU EINMAL angezeigt
    # (einstellungen.php). Liegt im Zustand keiner, ist ein etwa vorhandenes
    # Geraet unbrauchbar — es laesst sich nicht mehr benutzen und nimmt nur
    # einen Platz der Geraetegrenze weg. Also weg damit, bevor neue entstehen.
    html = s.get("einstellungen.php?t=geraete").text
    # UEBER DIE FORMULARKENNUNG, nicht ueber die Nachbarschaft der Felder.
    # Die Geraeteliste rendert je Geraet ein eigenes Loeschformular
    # `<form … id="f-devdel-<id>">`; die Knoepfe verweisen mit `form=` darauf
    # (P3/O11). Die beiden alten Ausdruecke suchten nebeneinanderliegende
    # <input>-Felder und fanden seither NICHTS — das Aufraeumen lief leer, und
    # jeder Wiederholungslauf hinterliess ein weiteres unbrauchbares Geraet,
    # bis die Grenze von fuenf je Konto erreicht war und `add` still scheiterte
    # (F-S2-A). Die Formularkennung ist der stabilere Anker: Sie traegt die
    # Geraetekennung selbst.
    alt_ids = re.findall(r'id="f-devdel-(\d+)"', html)
    for gid in set(alt_ids):
        s.csrf_auffrischen("einstellungen.php?t=geraete")
        s.post("einstellungen.php?t=geraete",
               {"csrf": s.csrf, "action": "delete", "id": gid})
    if alt_ids:
        melde(f"  {len(set(alt_ids))} Gerät(e) ohne bekannten Schlüssel entfernt.")

    geraete = {}
    for kennung, beschriftung in (("11", "Uhr Luftrettung (Referenz)"),
                                  ("12", "Uhr Bodendienst (Referenz)")):
        s.csrf_auffrischen("einstellungen.php?t=geraete")
        # MIT REITER. Die Einstellungsseite fuehrt ihre Abschnitte ueber `t`;
        # ohne ihn antwortet sie mit dem Profil-Reiter, und der Schluesselkasten
        # steht nur im Geraete-Reiter. Das Geraet entsteht trotzdem — der
        # Schluessel waere dann fuer immer weg, denn er wird genau einmal
        # angezeigt.
        antwort = s.post("einstellungen.php?t=geraete",
                         {"csrf": s.csrf, "action": "add", "label": beschriftung})
        # Markup seit P3/O11 (Web 9.12.0) — der Kasten „Zugangsdaten des neuen
        # Geraets" als Baustein `codeblock`, Titel und Wert als eigene <p>:
        #   <p class="codeblock-titel">Geräte-ID</p>
        #   <p class="codeblock-wert">dev-…</p>
        #   <p class="codeblock-titel">API-Schlüssel</p>
        #   <p class="codeblock-wert">…</p>
        # Vorher stand dort <code>…</code>; danach fand dieser Leser nichts
        # mehr, und das Geraet war mitsamt seinem nur einmal angezeigten
        # Schluessel verloren (F-S2-A).
        def wert_nach(titel: str) -> str | None:
            m = re.search(r'codeblock-titel"[^>]*>\s*' + re.escape(titel)
                          + r'\s*</p>\s*<p class="codeblock-wert"[^>]*>\s*(.*?)\s*</p>',
                          antwort.text, re.S)
            return m.group(1).strip() if m else None

        geraeteId = wert_nach("Geräte-ID")
        schluessel = wert_nach("API-Schlüssel")
        if not geraeteId or not schluessel:
            raise RuntimeError(
                "Gerät angelegt, aber Kennung oder Schlüssel nicht gefunden"
                + (f" — Seite meldet: {fehlertext(antwort.text)}"
                   if fehlertext(antwort.text) else "")
                + ". Der Schlüssel wird nur EINMAL angezeigt; das Gerät ist damit "
                  "unbrauchbar und wird beim nächsten Lauf aufgeräumt.")
        geraete[kennung] = {"device_id": geraeteId, "api_key": schluessel,
                            "label": beschriftung}
        melde(f"  Gerät {kennung}: {geraete[kennung]['device_id']}")
    lauf.zustand["geraete"] = geraete


# --------------------------------------------------------------- 4. Ingest
def stufe_ingest(lauf: Lauf) -> None:
    """Sendeplan abspielen — in der Reihenfolge, in der die Uhr senden wuerde.

    DAS MESSPROTOKOLL (E-P1-14 / R19) entsteht hier. Es haelt die SOLL-Zeit
    fest, nicht die Wanduhr des Replays: Das Einspielen laeuft in Sekunden ab,
    wo der Dienst zwoelf Stunden dauerte. Fuer die Bemessung einer Mengenbremse
    in P5 zaehlt, wie eine Uhr im BETRIEB sendet, nicht wie schnell ein Skript
    schaufelt.
    """
    plan = json.loads((AUSGABE / "sendeplan.json").read_text("utf-8"))
    geraete = lauf.zustand["geraete"]
    s = lauf.demo_sitzung()          # nur fuer die Basisadresse; Ingest ist tokenfrei
    bereits = set(lauf.zustand.get("ingest_erledigt", []))
    messung: list[dict] = []
    fehler = 0

    for eintrag in plan:
        datei = eintrag["datei"]
        if datei in bereits:
            continue
        koerper = json.loads((AUSGABE / datei).read_text("utf-8"))
        geraet = geraete[eintrag["ref"].split("-")[1]]
        t0 = time.monotonic()
        antwort = s.s.post(f"{lauf.basis}/ingest.php", data=json.dumps(koerper),
                           headers={"Content-Type": "application/json",
                                    "X-Device-Id": geraet["device_id"],
                                    "X-Api-Key": geraet["api_key"]},
                           timeout=120)
        dauer_ms = round((time.monotonic() - t0) * 1000)
        try:
            erg = antwort.json()
        except ValueError:
            erg = {"error": "keine JSON-Antwort", "status": antwort.status_code}
        if antwort.status_code != 200 or not erg.get("ok"):
            fehler += 1
            melde(f"  FEHLER {datei}: HTTP {antwort.status_code} {erg}")
            if fehler > 5:
                raise RuntimeError("Zu viele Fehler beim Ingest — Abbruch.")
        messung.append({
            "soll_zeit": eintrag["zeit"], "dienst": eintrag["dienst"],
            "kind": eintrag["kind"], "ref": eintrag["ref"],
            "seq_from": eintrag["seq_from"], "punkte": eintrag["punkte"],
            "final": eintrag["final"], "bytes": len(json.dumps(koerper).encode("utf-8")),
            "status": antwort.status_code, "dauer_ms": dauer_ms,
            "stored_points": erg.get("stored_points"), "next_seq": erg.get("next_seq"),
            "rejected": erg.get("rejected"), "kept_phases": erg.get("kept_phases"),
            "kept_resus": erg.get("kept_resus"),
        })
        bereits.add(datei)
        if len(messung) % 100 == 0:
            melde(f"  {len(messung)} Anfragen …")
            lauf.zustand["ingest_erledigt"] = sorted(bereits)
            lauf.sichern()          # nur den Fortschritt sichern

    lauf.zustand["ingest_erledigt"] = sorted(bereits)
    # EINMAL anhaengen, am Ende. Der Zwischenstand oben setzte die Liste
    # zusaetzlich — dabei stand am Schluss jede Anfrage zweimal drin, und das
    # Messprotokoll haette die doppelte Last behauptet.
    lauf.messung = messung
    verworfen = sum(1 for m in messung if m["rejected"])
    melde(f"  {len(messung)} Anfragen gesendet, {fehler} Fehler, "
          f"{verworfen} mit verworfenen Einzelwerten.")


# ------------------------------------------------------------ 5. Zuordnen
def stufe_zuordnen(lauf: Lauf) -> None:
    """Neutrale Diensttage zuordnen — ueber `POST api/day.php` (B-03).

    VORHER WIRD DER NEUTRALE ZUSTAND BEOBACHTET (E-P1-11). Er ist kein
    Nebenprodukt, sondern eine Zusage des Datenmodells: Ein von der Uhr
    angelegter Diensttag hat keine Art, keine Rollen und keine artabhaengigen
    Felder. Das wird hier gezaehlt und im Zustand festgehalten, damit es
    belegt ist und nicht behauptet.
    """
    s = lauf.demo_sitzung()
    tage = s.get("api/day.php").json()["days"]
    neutral = [t for t in tage if t["kind"] is None]
    lauf.zustand["neutral_beobachtet"] = {
        "diensttage_gesamt": len(tage), "davon_neutral": len(neutral),
        "beispiel": neutral[0] if neutral else None,
    }
    melde(f"  Neutraler Zustand beobachtet: {len(neutral)} von {len(tage)} "
          f"Diensttagen ohne Art (E-P1-11).")

    ids = lauf.zustand.get("stammdaten_ids") or kennungen(s)
    zuordnung = {}
    for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        dn = d["dienst"]
        stunde = dn["beginn"][11:16]
        passend = [t for t in tage if t["day"] == dn["day"]
                   and (t["start_hhmm"] == stunde or len(
                       [x for x in tage if x["day"] == dn["day"]]) == 1)]
        if not passend:
            raise RuntimeError(f"{d['kennung']}: kein Diensttag zu {dn['day']} {stunde}")
        tag = passend[0]
        antwort = s.json_post("api/day.php", {
            "day_id": tag["id"],
            "base_id": ids["standorte"][dn["standort"]],
            "vehicle_id": ids["rettungsmittel"][dn["rettungsmittel"]],
            "crew": {r: n for r, n in (dn["besatzung"] or {}).items() if n},
            "notes": dn["notizen"] or "",
        })
        erg = antwort.json()
        if not erg.get("ok"):
            raise RuntimeError(f"{d['kennung']}: api/day.php meldet {erg}")
        zuordnung[d["kennung"]] = tag["id"]
    lauf.zustand["diensttage"] = zuordnung
    melde(f"  {len(zuordnung)} Diensttage zugeordnet.")


# ------------------------------------------------- Formular: Felder bauen
def formularfelder(daten: dict, inhaltsschluessel: str, tag_beginn: str | None) -> dict:
    """Aus einem Formular-Dokument des Generators die POST-Felder bauen.

    `pat_blob` entsteht HIER als `edk1:`-Chiffretext. Der Server nimmt ihn
    bauartbedingt nur so entgegen (`pruef_pat_blob`) — er hat den Schluessel
    nicht und koennte den Inhalt gar nicht pruefen. Das ist keine Luecke,
    sondern die Zusage: Klartext dieser Felder geht nie an den Server.
    """
    f = daten["felder"] or {}
    felder: dict = {}
    for spalte, wert in f.items():
        if spalte in ("crew", "other_resources", "start_src"):
            continue
        if wert is None or wert == "":
            continue
        if isinstance(wert, bool):
            wert = 1 if wert else 0
        if spalte in ("na_escort", "schockraum", "false_alarm", "secondary",
                      "winch", "winch_airload", "bergwacht", "crew_override"):
            if not wert:
                continue          # Haken: nur gesetzte Werte senden
            felder[f"f_{spalte}"] = "1"
        else:
            felder[f"f_{spalte}"] = str(wert)
    if f.get("start_src"):
        felder["start_src"] = f["start_src"]
    # Zielklinik ist ein Ortsfeld: die Koordinaten heissen nach dem FELD.
    if f.get("dest_lat") is not None:
        felder["f_transport_dest_lat"] = str(f["dest_lat"])
        felder["f_transport_dest_lon"] = str(f["dest_lon"])
        felder.pop("f_dest_lat", None)
        felder.pop("f_dest_lon", None)
    for rolle, name in (f.get("crew") or {}).items():
        if name:
            felder[f"f_crew_{rolle}"] = name

    liste: list[tuple[str, str]] = list(felder.items())
    for name in (f.get("other_resources") or []):
        liste.append(("f_other_resources[]", name))
    for nr, zeit in daten["phasen"]:
        liste.append(("ph_no[]", str(nr)))
        liste.append(("ph_time[]", zeit[11:16]))
    for i, sitzung in enumerate(daten["rea"] or []):
        liste.append((f"rea[{i}][start]", sitzung["beginn"][11:16]))
        for j, (typ, zeit) in enumerate(sitzung["ereignisse"]):
            liste.append((f"rea[{i}][ev][{j}][typ]", typ))
            liste.append((f"rea[{i}][ev][{j}][zeit]", zeit[11:16]))
    blob = krypto.pat_blob(daten["geschuetzt"], inhaltsschluessel)
    if blob:
        liste.append(("pat_blob", blob))
    return liste


def formular_senden(s, liste, zusatz: dict) -> None:
    s.csrf_auffrischen("index.php")
    daten = list(liste) + [(k, str(v)) for k, v in zusatz.items()] + [("csrf", s.csrf)]
    antwort = s.post("einsatz_form.php", daten, allow_redirects=True)
    fehler = fehlertext(antwort.text)
    if fehler:
        raise RuntimeError(fehler)
    return antwort


# ------------------------------------------------------------ 6. Nachtragen
def stufe_nachtragen(lauf: Lauf) -> None:
    s = lauf.demo_sitzung()
    tage = lauf.zustand["diensttage"]
    getan = set(lauf.zustand.get("nachgetragen", []))
    n = 0
    for pfad in sorted((AUSGABE / "formular").glob("*.json")):
        daten = json.loads(pfad.read_text("utf-8"))
        if daten["art"] != "nachtrag" or daten["kennung"] in getan:
            continue
        tag_id = tage[daten["dienst"]]
        liste = s.get(f"api/day.php?d={tag_id}").json()
        stunde = daten["beginn"][11:16]
        treffer = [m for m in liste["missions"] if m["start_hhmm"] == stunde]
        if len(treffer) != 1:
            raise RuntimeError(f"{daten['kennung']}: {len(treffer)} Einsätze um {stunde} "
                               f"am Diensttag {tag_id}")
        felder = formularfelder(daten, s.inhaltsschluessel, None)
        formular_senden(s, felder, {"id": treffer[0]["id"]})
        getan.add(daten["kennung"])
        n += 1
        if n % 20 == 0:
            melde(f"  {n} nachgetragen …")
            lauf.zustand["nachgetragen"] = sorted(getan)
            lauf.sichern()
    lauf.zustand["nachgetragen"] = sorted(getan)
    melde(f"  {n} Einsätze nachgetragen (Felder und geschützte Angaben).")


# --------------------------------------------------------------- 7. Manuell
def stufe_manuell(lauf: Lauf) -> None:
    s = lauf.demo_sitzung()
    tage = lauf.zustand["diensttage"]
    getan = set(lauf.zustand.get("manuell", []))
    n = 0
    for pfad in sorted((AUSGABE / "formular").glob("*.json")):
        daten = json.loads(pfad.read_text("utf-8"))
        if daten["art"] != "neu" or daten["kennung"] in getan:
            continue
        felder = formularfelder(daten, s.inhaltsschluessel, None)
        formular_senden(s, felder, {"day_id": tage[daten["dienst"]]})
        getan.add(daten["kennung"])
        n += 1
    lauf.zustand["manuell"] = sorted(getan)
    melde(f"  {n} Einsätze von Hand angelegt (origin=manual).")


# ------------------------------------------------------------ 8. Papierkorb
def stufe_papierkorb(lauf: Lauf) -> None:
    """Dauerzustand herstellen: ein gelöschter Einsatz und ein gelöschter
    Diensttag (E-P1-21).

    Ueber die regulaeren Loeschwege, nicht per SQL: `einsatz_loeschen.php`
    und `diensttag_loeschen.php` setzen `deleted_at` und — beim Diensttag —
    `deleted_with_day` an seinen Einsaetzen. Genau dieser Unterschied soll
    im Referenzzustand liegen.
    """
    s = lauf.demo_sitzung()
    tage = lauf.zustand["diensttage"]
    getan = lauf.zustand.get("papierkorb", {})

    for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        tag_id = tage[d["kennung"]]

        for e in d["einsaetze"]:
            if e.get("papierkorb") != "einsatz" or e["client_ref"] in getan:
                continue
            liste = s.get(f"api/day.php?d={tag_id}").json()
            stunde = e["beginn"][11:16]
            treffer = [m for m in liste["missions"] if m["start_hhmm"] == stunde]
            if not treffer:
                raise RuntimeError(f"{e['client_ref']}: Einsatz um {stunde} nicht gefunden")
            s.csrf_auffrischen("index.php")
            s.post("einsatz_loeschen.php",
                   {"id": treffer[0]["id"], "confirm": "ja", "csrf": s.csrf})
            getan[e["client_ref"]] = {"art": "einsatz", "id": treffer[0]["id"]}
            melde(f"  Einsatz {e['client_ref']} in den Papierkorb gelegt.")

        if d.get("papierkorb") == "diensttag" and d["kennung"] not in getan:
            s.csrf_auffrischen("index.php")
            s.post("diensttag_loeschen.php", {"d": tag_id, "confirm": "ja", "csrf": s.csrf})
            getan[d["kennung"]] = {"art": "diensttag", "id": tag_id}
            melde(f"  Diensttag {d['kennung']} mit seinen Einsätzen in den Papierkorb gelegt.")

    lauf.zustand["papierkorb"] = getan


# ------------------------------------------------------------ 9. Sperrliste
def stufe_sperrliste(lauf: Lauf) -> None:
    """E-P1-16: senden, löschen, endgültig entfernen, ERNEUT senden.

    Erwartet wird, dass der Einsatz NICHT wieder entsteht. Ohne diese Sperre
    könnte eine Uhr mit gepufferten Daten jeden gelöschten Einsatz wieder
    auferstehen lassen — und niemand wüsste, woher er kommt.
    """
    s = lauf.demo_sitzung()
    sperr = json.loads((QUELLE / "pruefschritte" / "sperrliste.json").read_text("utf-8"))
    e = sperr["einsatz"]
    tag_id = lauf.zustand["diensttage"][e["dienst"]]
    geraet = lauf.zustand["geraete"][e["client_ref"].split("-")[1]]
    kopf = {"Content-Type": "application/json",
            "X-Device-Id": geraet["device_id"], "X-Api-Key": geraet["api_key"]}

    def zaehle() -> int:
        return len(s.get(f"api/day.php?d={tag_id}").json()["missions"])

    # Der Payload steht im Sendeplan (der Prüfschritt läuft am Diensttag mit).
    dateien = [p for p in sorted((AUSGABE / "payloads" / e["dienst"]).glob("*.json"))
               if json.loads(p.read_text("utf-8")).get("client_ref") == e["client_ref"]]
    if not dateien:
        raise RuntimeError("Kein Payload für den Sperrlisten-Prüfschritt gefunden.")

    # EINMALIG JE INSTALLATION. Nach dem endgueltigen Loeschen steht die
    # client_ref auf der Sperrliste (`deleted_refs`) und verfaellt erst nach
    # 90 Tagen. Ein zweiter Durchlauf koennte den Prueffall also gar nicht
    # mehr anlegen -- er ist dann schon erbracht.
    if lauf.zustand.get("sperrliste", {}).get("bestanden"):
        melde("  Bereits erbracht (die client_ref steht auf der Sperrliste). "
              "Übersprungen.")
        return

    protokoll = {"schritte": []}
    vorher = zaehle()

    for p in dateien:                                     # 1. senden
        s.s.post(f"{lauf.basis}/ingest.php", data=p.read_text("utf-8"),
                 headers=kopf, timeout=60)
    nach_senden = zaehle()
    protokoll["schritte"].append({"schritt": "gesendet", "einsaetze": nach_senden})

    liste = s.get(f"api/day.php?d={tag_id}").json()["missions"]
    stunde = e["beginn"][11:16]
    treffer = [m for m in liste if m["start_hhmm"] == stunde]
    if not treffer:
        raise RuntimeError(
            "Prüfeinsatz nach dem Senden nicht auffindbar. Wahrscheinlichste "
            "Ursache: Die client_ref steht bereits auf der Sperrliste — dann "
            "ist der Prüfschritt in dieser Installation schon erbracht, und "
            "der Lauf-Zustand (lauf.json) sagt es nicht. Für einen frischen "
            "Durchlauf braucht es ein frisches Konto.")
    mid = treffer[0]["id"]

    s.csrf_auffrischen("index.php")                       # 2. Papierkorb
    s.post("einsatz_loeschen.php", {"id": mid, "confirm": "ja", "csrf": s.csrf})
    protokoll["schritte"].append({"schritt": "papierkorb", "einsaetze": zaehle()})

    s.csrf_auffrischen("papierkorb.php")                  # 3. endgültig
    s.post("papierkorb.php",
           {"action": "purge_mission", "id": mid, "confirm": "ja", "csrf": s.csrf})
    protokoll["schritte"].append({"schritt": "endgueltig_geloescht", "einsaetze": zaehle()})

    for p in dateien:                                     # 4. erneut senden
        s.s.post(f"{lauf.basis}/ingest.php", data=p.read_text("utf-8"),
                 headers=kopf, timeout=60)
    nachher = zaehle()
    protokoll["schritte"].append({"schritt": "erneut_gesendet", "einsaetze": nachher})

    # DER MASSSTAB IST DER ZUSTAND NACH DEM ENDGUELTIGEN LOESCHEN, nicht der
    # davor. `vorher` enthaelt den Prueffall bereits: Er gehoert zum Sendeplan
    # und ist in der Ingest-Stufe mitgesendet worden. Wer gegen `vorher` misst,
    # meldet ein BESTANDEN als Fehlschlag — genau das ist beim ersten Lauf
    # passiert.
    nach_purge = protokoll["schritte"][2]["einsaetze"]
    protokoll["erwartet"] = nach_purge
    protokoll["tatsaechlich"] = nachher
    protokoll["bestanden"] = (nachher == nach_purge)
    protokoll["vor_dem_pruefschritt"] = vorher
    lauf.zustand["sperrliste"] = protokoll
    melde(f"  Sperrliste: nach dem Senden {nach_senden}, im Papierkorb "
          f"{protokoll['schritte'][1]['einsaetze']}, nach endgültigem Löschen "
          f"{nach_purge}, nach ERNEUTEM Senden {nachher} — "
          + ("BESTANDEN, der Einsatz kam nicht wieder" if protokoll["bestanden"]
             else "NICHT BESTANDEN"))
    if not protokoll["bestanden"]:
        raise RuntimeError("Sperrliste greift nicht: Der Einsatz wurde wieder angelegt.")


# ------------------------------------------------------------------ Ablauf
def main() -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--basis", default="https://127.0.0.1:8443")
    p.add_argument("--stufen", default=",".join(ALLE_STUFEN))
    p.add_argument("--admin-email", default="admin@gen-em.org")
    p.add_argument("--admin-passwort", default="adminlokal2026")
    p.add_argument("--zustand", default=str(HIER / "lauf.json"))
    p.add_argument("--konto", default=None,
                   help="abweichendes Zielkonto (Kreislaufpruefung B5)")
    p.add_argument("--konto-passwort", default=None)
    a = p.parse_args()

    if a.konto:
        globals()["DEMO_EMAIL"] = a.konto
    if a.konto_passwort:
        globals()["DEMO_PASSWORT"] = a.konto_passwort

    lauf = Lauf(a.basis, pathlib.Path(a.zustand))
    stufen = [x.strip() for x in a.stufen.split(",") if x.strip()]
    unbekannt = [x for x in stufen if x not in ALLE_STUFEN]
    if unbekannt:
        p.error(f"Unbekannte Stufe(n): {', '.join(unbekannt)}")

    funktionen = {
        "konto": lambda: stufe_konto(lauf, (a.admin_email, a.admin_passwort)),
        "stammdaten": lambda: stufe_stammdaten(lauf),
        "geraet": lambda: stufe_geraet(lauf),
        "ingest": lambda: stufe_ingest(lauf),
        "zuordnen": lambda: stufe_zuordnen(lauf),
        "nachtragen": lambda: stufe_nachtragen(lauf),
        "manuell": lambda: stufe_manuell(lauf),
        "papierkorb": lambda: stufe_papierkorb(lauf),
        "sperrliste": lambda: stufe_sperrliste(lauf),
    }

    for name in stufen:
        melde(f"[{name}]")
        t0 = time.monotonic()
        try:
            funktionen[name]()
        finally:
            lauf.sichern()
        melde(f"  ({time.monotonic() - t0:.1f} s)")
    melde(f"\nFertig. {lauf.s.anfragen if lauf.s else 0} Anfragen in der Sitzung, "
          f"Zustand in {a.zustand}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
