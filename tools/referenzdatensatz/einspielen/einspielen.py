#!/usr/bin/env python3
"""Einspiellauf des Referenzdatensatzes.

Spielt den erzeugten Datensatz ueber die REGULAEREN Wege in eine
Installation ein — kein roher SQL-Weg. Die vier Wege:

  1. `ingest.php`        Geraeteschnittstelle: Diensttage, Einsaetze,
                         Ruhe-Segmente, Phasen, Reanimationen, Spur
  2. `api/day.php`       Zuordnung der neutralen Diensttage
  3. `einsatz_form.php`  Nachtragen der Felder und der geschuetzten Angaben,
                         `pat_blob` als `edk1:`-Chiffretext
  4. Weboberflaeche      Papierkorb (`einsatz_loeschen.php`,
                         `diensttag_loeschen.php`, `papierkorb.php`)

Der CSV-Import laeuft bewusst NICHT hier, sondern im Browser.

Stufen einzeln aufrufbar:
    python3 einspielen.py --stufen konto,stammdaten,geraet
    python3 einspielen.py                      (alle)
    python3 einspielen.py --basis https://…    (andere Installation)
"""
from __future__ import annotations

import argparse
import base64
import json
import os
import pathlib
import re
import subprocess
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
import ortsdaten        # noqa: E402  (Huellen und Stuecke wie der Browser)
import sitzung as sitzungsmodul   # noqa: E402
from sitzung import fehlertext    # noqa: E402  (eine Stelle, siehe dort)

QUELLE = WURZEL / "quelldaten"
AUSGABE = WURZEL / "generator" / "ausgabe"
TZ = ZoneInfo("Europe/Berlin")

# Zugangsdaten des Referenzkontos. Beide sind ueber die Befehlszeile
# umstellbar: Die KREISLAUFPRUEFUNG legt ein zweites, frisches Konto an und
# fuellt dort nur die Stammdaten, damit der CSV-Import ueberhaupt ein
# Rettungsmittel zur Auswahl hat. Ohne diesen Schalter muesste sie das Konto
# nachbauen -- und damit einen zweiten Weg pflegen.
DEMO_EMAIL = "demo@gen-em.org"
DEMO_PASSWORT = "nadokudemo0815"

ALLE_STUFEN = ["konto", "stammdaten", "geraet", "ingest", "zuordnen",
               "nachtragen", "manuell", "papierkorb", "sperrliste", "schneiden",
               "loeschen", "verdichten"]
# `loeschen` UND `verdichten` KOMMEN NACH `schneiden`, in dieser Folge
# (Konzept RD, E-RD-15): erst schneiden, dann einen zweiten Zeitraum desselben
# Segments loeschen, dann verdichten. So ist der Loeschvermerk juenger als der
# Schnitt, und der Wartungslauf sieht ein Segment, das alle drei Zustaende
# durchlaufen hat. Verdichtet wird zuletzt, weil ein Schnitt in einer
# gepackten Spur andere Punkte faende als in den Geraetestuecken.
# `schneiden` STEHT AM ENDE und nicht, wie das Konzept es zuerst vorsah,
# zwischen `zuordnen` und `nachtragen`. Der Grund sind die drei
# Stufen dazwischen: `nachtragen`, `papierkorb` und `sperrliste` suchen ihre
# Einsaetze ueber `start_hhmm` -- die erste bricht bei zwei Treffern ab, die
# zweite nimmt still `treffer[0]`. Ein geschnittener Einsatz waere ab der
# Stufe ein zusaetzlicher Einsatz in derselben Liste. Am Ende gibt es diese
# Ueberschneidung nicht, und der geschnittene Einsatz braucht ohnehin keine
# der drei: Er bleibt bewusst leer (api/schneiden.php fuellt keine Felder).


class Lauf:
    """Zustand ueber die Stufen hinweg — und das Messprotokoll."""

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


def tagesversatz(tag: str, zeitstempel: str) -> int:
    """0 oder 1: Liegt `zeitstempel` (lokal, „JJJJ-MM-TT hh:mm") am Diensttag
    oder am Tag danach? Der Versatz steht nicht als Feld in den Quelldaten —
    er folgt aus dem Datum (Konzept RD, E-RD-21), und `api/schneiden.php`
    nimmt ihn als `beginn_tag`/`ende_tag` (F-RD-10)."""
    from datetime import date
    d = (date.fromisoformat(zeitstempel[:10]) - date.fromisoformat(tag)).days
    if d not in (0, 1):
        raise RuntimeError(f"{zeitstempel} liegt {d} Tage nach dem Diensttag {tag}")
    return d


def segment_finden(lauf: "Lauf", s, kennung: str, d: dict, ref: str) -> tuple[dict, dict]:
    """Das Ruhesegment `ref` des Dienstes `kennung` im Bestand: (Tagesliste, Segment).

    WIE DAS SEGMENT GEFUNDEN WIRD. `api/schneiden.php` will die INTERNE
    Kennung (`rest_id`); die Quelldaten kennen nur die `client_ref`, und
    `api/day.php` liefert die client_ref nicht mit. Die Bruecke ist die
    Beginnzeit: Sie ist innerhalb eines Diensttags eindeutig, und
    `api/day.php` gibt sie als `start_hhmm` fertig in Ortszeit heraus. Ueber
    UTC zu rechnen waere der naheliegende und falsche Weg -- daran ist der
    Schnitt schon einmal zwei Stunden danebengegriffen (api/day.php,
    Kommentar zu den Zeiten)."""
    tag_id = lauf.zustand.get("diensttage", {}).get(kennung)
    if not tag_id:
        raise RuntimeError(f"{kennung}: kein Diensttag im Zustand (Stufe `zuordnen`)")
    quelle = next((r for r in d["ruhesegmente"] if r["client_ref"] == ref), None)
    if quelle is None:
        raise RuntimeError(f"{kennung}: Segment {ref} steht nicht im Dienst")
    # LESEN IST EIN GET. Der POST auf api/day.php ist ein reines UPDATE des
    # Diensttags (Kopfkommentar dort); die Segmente kommen ueber `?d=<day_id>`.
    a = s.get(f"api/day.php?d={tag_id}")
    if a.status_code != 200:
        raise RuntimeError(f"{kennung}: api/day.php?d={tag_id} antwortete {a.status_code}")
    liste = a.json()
    treffer = [r for r in liste.get("rest_segments", [])
               if r.get("start_hhmm") == quelle["beginn"][11:16]]
    if len(treffer) != 1:
        raise RuntimeError(f"{kennung}: {len(treffer)} Ruhesegmente beginnen um "
                           f"{quelle['beginn'][11:16]} — erwartet war genau eines")
    return liste, treffer[0]


# ---------------------------------------------------------------- 1. Konto
def stufe_konto(lauf: Lauf, admin: tuple[str, ...]) -> None:
    """Demo-Konto ueber den REGULAEREN Einladungsweg anlegen.

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
        # MIT ?t: einstellungen.php zeigt ohne `t` die UEBERSICHT
        # und beendet sich VOR der POST-Verarbeitung — ein POST ohne
        # Parameter versandete stillschweigend.
        s.csrf_auffrischen("einstellungen.php?t=standorte")
        antwort = s.post("einstellungen.php?t=standorte", {**daten, "csrf": s.csrf})
        # Fehlerkasten heute als Meldungs-Baustein; die alte Klasse davor.
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
        # TYP, KURZNAME UND "OHNE STANDORT".
        #
        # `standort` nennt die Karte, aus der das Formular kommt. Traegt der
        # Eintrag `ohne_standort`, geht statt der Kennung eine 0 hinaus -- das
        # ist der Eintrag "Ohne Standort" der Auswahlliste, und
        # `dt_base_erlaubt()` macht daraus NULL. Ob der Typ das darf,
        # entscheidet weiterhin die Pruefschicht (Pflicht nur bei "standard").
        #
        # EIN EIGENES FELD `ohne_standort=1` GEHT NICHT. Es war einmal ein
        # Haken neben der Standortauswahl, und den hat der erste Eintrag der
        # Auswahlliste ersetzt -- mit Begruendung (der Haken "schlug eine
        # verborgene Standortkennung; wer ihn setzte, sah nicht, WAS er damit
        # ueberschrieb"). Das Feld las seither niemand mehr, und weil daneben
        # weiterhin eine echte Kennung stand, bekamen BEIDE Eintraege einen
        # Standort. Aufgefallen ist es erst am Referenzbestand: 0 von 6 statt
        # 2 von 6 ohne Standort. Heute sind es VIER von
        # ZEHN -- der Demo-Ausbau hat zwei Veranstaltungs-Rettungsmittel ohne
        # Standort dazugelegt. Wer die Zahl auf null fallen sieht,
        # hat denselben Fehler wieder.
        #
        # Die Lehre steht in der LIESMICH: Ein Sender, der ein Feld schickt,
        # das niemand liest, meldet keinen Fehler -- er wird still ignoriert.
        daten = {"action": "veh_save", "name": r["name"], "kind": r["art"],
                 "typ": r.get("typ", "standard"),
                 "kurz": r.get("kurz") or "",
                 "base_id": 0 if r.get("ohne_standort")
                            else ids["standorte"][r["standort"]],
                 "roles[]": r["rollen"], "caps[]": r["faehigkeiten"]}
        speichern(daten)
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
    genau das, was die Anwendung dem Konto anbietet, samt Standortbindung.
    """
    html = s.get("diensttag_neu.php").text

    def lesen(feld: str, aufbereiten) -> dict:
        # NICHT auf "<select name=..." verankern. Der
        # Baustein ui_feld() rendert das Feld als
        # `<select class="feld-eingabe" id="..." name="...">` — `name` steht
        # also nicht mehr vorn. Der alte Ausdruck fand nichts mehr, lieferte
        # ein leeres Verzeichnis, und die Stammdaten-Stufe brach zwei Zeilen
        # spaeter mit einem KeyError auf den STANDORTNAMEN ab. Die Ursache
        # stand nirgends: Es sah aus, als fehle ein Standort, der in der
        # Datenbank laengst lag.
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

    # Der Standort steht schlicht da. Frueher konnte „ (zentral)"
    # dahinter stehen; das schrieb diensttag_neu.php fuer zentrale Standorte.
    standorte = lesen("base_id", lambda x: x.strip())
    # Das Rettungsmittel traegt ein Artzeichen davor und den Standort dahinter:
    # „🚁 Alpenfalke 1 · Luftrettungsstation Hochkreuth". Beides gehoert zur
    # ANZEIGE und nicht zum Namen.
    mittel = lesen("vehicle_id",
                   lambda x: x.split(" · ")[0].lstrip("🚁🚑🚒🚐 ").strip())
    return {"standorte": standorte, "rettungsmittel": mittel}


# --------------------------------------------------------------- 3. Geraet
def _geraete_ids(s) -> set[str]:
    """Die internen Kennungen der echten Geraete des Kontos.

    UEBER DIE FORMULARKENNUNG, nicht ueber die Nachbarschaft der Felder. Die
    Geraeteliste rendert je Geraet ein eigenes Loeschformular
    `<form … id="f-devdel-<id>">`; die Knoepfe verweisen mit `form=` darauf.
    Zwei aeltere Ausdruecke suchten nebeneinanderliegende
    <input>-Felder und fanden seither NICHTS -- das Aufraeumen lief leer, und
    jeder Wiederholungslauf hinterliess ein weiteres unbrauchbares Geraet,
    bis die Grenze von fuenf je Konto erreicht war und `add` still scheiterte.
    """
    return set(re.findall(r'id="f-devdel-(\d+)"', s.get("einstellungen.php?t=geraete").text))


def stufe_geraet(lauf: Lauf) -> None:
    """Die zwei Referenzgeraete -- ueber den ECHTEN Kopplungsweg.

    WARUM NICHT MEHR ueber die Geraeteseite (`action=add`). Der kurze Weg legt
    ein Geraet mit Beschriftung an und sonst nichts: `geraet_art` und
    `geraet_modell` bleiben NULL. `ingest.php` kopiert beide
    Spalten beim Anlegen an jeden Einsatz und jedes Segment (Momentaufnahme) -- mit NULL an der Quelle trug der ganze Referenzbestand eine leere
    Momentaufnahme, und der edbak-Kreislauf belegte fuer die Momentaufnahme nichts. Art und
    Modell entstehen AUSSCHLIESSLICH in `pair.php` im Ja-Zweig; einen
    Nachtragsweg gibt es nicht.

    VIER SCHRITTE JE GERAET, und der dritte laeuft im Browser des Kontos:

      1. `POST pair.php {"aktion":"start","geraet":{...}}`, OHNE Kopfzeilen.
         Die Antwort traegt Code, Kennung und Schluessel im JSON -- damit
         faellt das Abklauben des Markups weg, das frueher an jeder
         Umgestaltung der Seite zerbrach.
      2. Zustand sichern, SOFORT. Der Schluessel geht genau einmal ueber die
         Leitung; ein Abbruch zwischen Schritt 1 und 4 hinterliesse sonst
         eine Kopplungssitzung, zu der niemand mehr den Schluessel hat.
      3. `POST einstellungen.php?t=geraete action=koppeln_bestaetigen` mit dem
         Code -- das Einloesen im Konto.
      4. `POST pair.php {"aktion":"bestaetigen","antwort":"ja"}` MIT
         X-Device-Id/X-Api-Key. Erst hier entsteht die Zeile in `devices`,
         und erst hier stehen Art und Modell darin.

    DER BELEG FUER SCHRITT 3 IST SCHRITT 4, nicht die HTTP-Antwort. Erfolg ist
    dort eine 302, Misserfolg eine 200 mit Fehlertext im HTML -- nach dem
    automatischen Folgen der Umleitung sind beide 200. Wer sich darauf
    verlaesst, meldet Erfolg und faellt erst spaeter auf. Bleibt Schritt 3
    ohne Wirkung, antwortet Schritt 4 mit 409 `nicht_beansprucht`; das ist der
    positive Beleg, und er wird hier ausdruecklich geprueft.

    DIE BESCHRIFTUNG KOMMT ZULETZT. `pair.php` setzt beim Ja
    `label = geraet_vorgabename(art)` -- also schlicht „Uhr" oder „Handy".
    Die sprechenden Namen der Referenz entstehen danach ueber
    `action=rename`.

    RATENSCHUTZ: `pair_start` laesst 20 Anfragen je 600 s und Adresse zu, und
    NICHTS setzt den Topf zurueck (kein `rate_erfolg('pair_start')` im Baum).
    Zwei Geraete je Lauf heisst zehn Laeufe je zehn Minuten. Wer beim
    Entwickeln oefter fahren muss, raeumt den Topf so ab, wie es
    `tools/proben/kopplung/probe.php` tut -- hier steht kein SQL.
    """
    s = lauf.demo_sitzung()
    if lauf.zustand.get("geraete"):
        melde("  Geräte bestehen bereits.")
        return

    quelle = json.loads((QUELLE / "geraete.json").read_text("utf-8"))["geraete"]

    # ZUERST AUFRAEUMEN, UND NUR HIER. Liegt im Zustand kein Schluessel, ist
    # ein etwa vorhandenes Geraet unbrauchbar -- es laesst sich nicht mehr
    # benutzen und nimmt nur einen Platz der Grenze von fuenf weg.
    #
    # DIESE SCHLEIFE DARF NIE NACH DEM INGEST LAUFEN: `devices` haengt an
    # `missions`, `rest_segments` und `day_refs` mit ON DELETE SET NULL
    # (schema.sql 252/357/430). Ein Aufraeumen danach loeschte nicht zwei
    # Zeilen, sondern trennte den ganzen Bestand von seinen Geraeten -- ohne
    # Fehlermeldung, sichtbar erst als leeres `days[].refs[].device_id` im
    # Referenz-Export.
    alt_ids = _geraete_ids(s)
    for gid in alt_ids:
        s.csrf_auffrischen("einstellungen.php?t=geraete")
        s.post("einstellungen.php?t=geraete",
               {"csrf": s.csrf, "action": "delete", "id": gid})
    if alt_ids:
        melde(f"  {len(alt_ids)} Gerät(e) ohne bekannten Schlüssel entfernt.")

    geraete = {}
    bekannt = _geraete_ids(s)
    for g in quelle:
        kennung, beschriftung, block = g["nummer"], g["beschriftung"], g["block"]

        # --- 1. Sitzung eroeffnen (ohne Kopfzeilen) ------------------------
        a = s.json_post("pair.php", {"aktion": "start", "geraet": block})
        if a.status_code != 200:
            raise RuntimeError(f"Gerät {kennung}: pair.php start antwortete "
                               f"{a.status_code}: {a.text[:200]}")
        o = a.json()
        code, dev, key = o.get("code"), o.get("device_id"), o.get("api_key")
        if not code or not dev or not key:
            raise RuntimeError(f"Gerät {kennung}: start ohne Code, Kennung oder Schlüssel: {o}")

        # --- 2. Zustand sofort sichern -------------------------------------
        geraete[kennung] = {"device_id": dev, "api_key": key, "label": beschriftung,
                            "art": block["art"], "schwebend": True}
        lauf.zustand["geraete"] = geraete
        lauf.sichern()

        # --- 3. Code im Konto einloesen ------------------------------------
        s.csrf_auffrischen("einstellungen.php?t=geraete")
        s.post("einstellungen.php?t=geraete",
               {"csrf": s.csrf, "action": "koppeln_bestaetigen", "code": code})

        # --- 4. Am Geraet mit Ja bestaetigen -- und DAS ist der Beleg ------
        b = s.s.post(f"{s.basis}/pair.php",
                     json={"aktion": "bestaetigen", "antwort": "ja"}, timeout=60,
                     headers={"X-Device-Id": dev, "X-Api-Key": key,
                              "Content-Type": "application/json"})
        s.anfragen += 1
        if b.status_code != 200 or not b.json().get("ok"):
            raise RuntimeError(
                f"Gerät {kennung}: das Ja am Gerät antwortete {b.status_code}: "
                f"{b.text[:200]} — bei 409 `nicht_beansprucht` hat das Einlösen "
                f"des Codes im Konto nicht gewirkt.")
        geraete[kennung]["schwebend"] = False

        # --- 5. Sprechende Beschriftung ------------------------------------
        neu_ids = _geraete_ids(s) - bekannt
        if len(neu_ids) != 1:
            raise RuntimeError(f"Gerät {kennung}: nach dem Koppeln stehen "
                               f"{len(neu_ids)} neue Geräte in der Liste, erwartet war eins")
        intern = neu_ids.pop()
        bekannt.add(intern)
        s.csrf_auffrischen("einstellungen.php?t=geraete")
        s.post("einstellungen.php?t=geraete",
               {"csrf": s.csrf, "action": "rename", "id": intern, "label": beschriftung})
        geraete[kennung]["intern"] = intern

        melde(f"  Gerät {kennung} ({block['art']}): {dev} — {beschriftung}")

    lauf.zustand["geraete"] = geraete


# --------------------------------------------------------------- 4. Ingest
def pakete_verschliessen(sec1: bytes, roh: list[dict]) -> list[dict]:
    """Pakete im Format des Sendeplans (Ortsdaten im Klartext) in das Format
    bringen, das `ingest.php` annimmt: Phasenorte, Reanimation und Spur in
    Gerätehüllen, verschlossen mit dem Kontoschlüssel. Dieselbe Gerätehilfe
    wie in den Proben (`tools/proben/huelle/geraet_cli.php`)."""
    umbau = subprocess.run(
        ["php", str(HIER.parents[2] / "tools" / "proben" / "huelle" / "geraet_cli.php")],
        input=json.dumps({"sec1": base64.b64encode(sec1).decode("ascii"), "pakete": roh}),
        capture_output=True, text=True, check=True)
    return json.loads(umbau.stdout)


def stufe_ingest(lauf: Lauf) -> None:
    """Sendeplan abspielen — in der Reihenfolge, in der die Uhr senden wuerde.

    DAS MESSPROTOKOLL entsteht hier. Es haelt die SOLL-Zeit
    fest, nicht die Wanduhr des Replays: Das Einspielen laeuft in Sekunden ab,
    wo der Dienst zwoelf Stunden dauerte. Fuer die Bemessung einer Mengenbremse
    zaehlt, wie eine Uhr im BETRIEB sendet, nicht wie schnell ein Skript
    schaufelt.
    """
    plan = json.loads((AUSGABE / "sendeplan.json").read_text("utf-8"))
    geraete = lauf.zustand["geraete"]
    s = lauf.demo_sitzung()          # Basisadresse und Kontoschluessel; Ingest ist tokenfrei
    bereits = set(lauf.zustand.get("ingest_erledigt", []))
    messung: list[dict] = []
    fehler = 0

    # SEIT WEB 22 VERSCHLOSSEN. Der Sendeplan liegt im
    # alten Format vor (Punkte, Koordinaten und Reanimation im Klartext);
    # `ingest.php` nimmt das nicht mehr an. Umgebaut wird mit DERSELBEN
    # Geraetehilfe wie in den Proben (`tools/proben/huelle/geraet.php`), in
    # einem Aufruf fuer alle offenen Pakete, verschlossen mit dem
    # Kontoschluessel des Demokontos -- den legt die Sitzung an, wenn das
    # Konto noch keinen hat, so wie der Browser beim Entsperren.
    offen = [e for e in plan if e["datei"] not in bereits]
    sec1 = s.kontoschluessel()
    roh = [json.loads((AUSGABE / e["datei"]).read_text("utf-8")) for e in offen]
    verschlossen = dict(zip((e["datei"] for e in offen), pakete_verschliessen(sec1, roh)))
    melde(f"  {len(offen)} Pakete verschlossen (Kontoschlüssel {sec1[:4].hex()}…).")

    for eintrag in plan:
        datei = eintrag["datei"]
        if datei in bereits:
            continue
        koerper = verschlossen[datei]
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
            "kept_ort": erg.get("kept_ort"),
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
    """Neutrale Diensttage zuordnen — ueber `POST api/day.php`.

    VORHER WIRD DER NEUTRALE ZUSTAND BEOBACHTET. Er ist kein
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
        # DER DIENSTTAG OHNE STANDORT. `base_id: ""` ist der Weg,
        # den auch das Formular geht: `dt_base_erlaubt()` macht daraus NULL.
        # Die Kennung ganz WEGZULASSEN taete es nicht -- `api/day.php` liesse
        # den Standort dann stehen, wie er ist, und ein zweiter Lauf haette
        # einen anderen Zustand als der erste.
        antwort = s.json_post("api/day.php", {
            "day_id": tag["id"],
            "base_id": "" if dn["standort"] is None else ids["standorte"][dn["standort"]],
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
def formularfelder(daten: dict, s, bestand: dict | None = None) -> list:
    """Aus einem Formular-Dokument des Generators die POST-Felder bauen.

    `pat_blob` entsteht HIER als `edk1:`-Chiffretext. Der Server nimmt ihn
    bauartbedingt nur so entgegen (`pruef_pat_blob`) — er hat den Schluessel
    nicht und koennte den Inhalt gar nicht pruefen. Das ist keine Luecke,
    sondern die Zusage: Klartext dieser Felder geht nie an den Server.

    WIE DAS FORMULAR. Phasenorte und
    Reanimation gehen in die Einsatzhuelle, verschlossen mit dem oeffentlichen
    Schluessel des Kontos und gebunden an die `client_ref`; die Phasenzeiten
    gehen als `ort_phasen` mit, und der Server haelt sie gegen seine eigene
    Rechnung. Transportziel und Ortshoehe liegen im `pat_blob`. Beim
    Nachtragen (`bestand`) kommen die Orte der Phasen aus der Huelle, die das
    Geraet geliefert hat, und die Hoehe aus der Spur — wie im Browser
    (`EdOrtsdaten.einsatzInnen()`, `.ortshoehe()`).
    """
    sec1, priv = s.kontopaar()
    f = daten["felder"] or {}
    geschuetzt = dict(daten["geschuetzt"] or {})
    felder: dict = {}
    for spalte, wert in f.items():
        if spalte in ("crew", "other_resources", "start_src",
                      "transport_dest", "dest_lat", "dest_lon"):
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
    # Die Zielklinik liegt im Blob, mit ihren Koordinaten.
    if f.get("transport_dest") or f.get("dest_lat") is not None:
        ziel = {"addr": f.get("transport_dest") or ""}
        if f.get("dest_lat") is not None and f.get("dest_lon") is not None:
            ziel.update(lat=float(f["dest_lat"]), lon=float(f["dest_lon"]))
        geschuetzt["transport_dest"] = ziel
    for rolle, name in (f.get("crew") or {}).items():
        if name:
            felder[f"f_crew_{rolle}"] = name

    liste: list[tuple[str, str]] = list(felder.items())
    for name in (f.get("other_resources") or []):
        liste.append(("f_other_resources[]", name))
    for nr, zeit in daten["phasen"]:
        liste.append(("ph_no[]", str(nr)))
        liste.append(("ph_time[]", zeit[11:16]))

    # Die Phasen in der Reihenfolge des Servers: nach Nummer, stabil.
    phasen = sorted(enumerate(daten["phasen"]), key=lambda x: (x[1][0], x[0]))
    rea = daten["rea"] or []
    anfragen = [(z[:10], z[11:16], 0) for _, (_, z) in phasen]
    for sitzung in rea:
        anfragen.append((sitzung["beginn"][:10], sitzung["beginn"][11:16], 0))
        anfragen += [(z[:10], z[11:16], 0) for _, z in sitzung["ereignisse"]]
    ts = ortsdaten.zeiten(anfragen)
    if any(t is None for t in ts):
        raise RuntimeError(f"{daten['kennung']}: eine Zeit liess sich nicht umrechnen")
    phasen_ts = [(nr, ts[i]) for i, (_, (nr, _)) in enumerate(phasen)]
    beginn = phasen_ts[0][1]
    k = len(phasen_ts)
    rea_innen = []
    for sitzung in rea:
        start = ts[k]; k += 1
        ev = []
        for typ, _ in sitzung["ereignisse"]:
            ev.append({"art": typ, "t": ts[k] - beginn}); k += 1
        rea_innen.append({"t": start - beginn, "ereignisse": ev})

    # Orte: beim Nachtragen aus der vorhandenen Huelle, je Nummer der Reihe nach.
    # Die Reanimation ebenso, wenn das Dokument keine nennt -- das Formular
    # zeigt sie aus der Huelle an und schickt sie unveraendert zurueck.
    vorrat: dict[int, list] = {}
    if bestand is not None:
        alt = ortsdaten.einsatz_oeffnen(bestand, priv) or {}
        for o in alt.get("phasen", []):
            vorrat.setdefault(int(o["phase"]), []).append(o)
        if not rea_innen and alt.get("rea"):
            versatz = int(bestand["started_ts"]) - beginn
            rea_innen = [{"t": r["t"] + versatz,
                          "ereignisse": [{"art": e["art"], "t": e["t"] + versatz}
                                         for e in r.get("ereignisse", [])]}
                         for r in alt["rea"]]
    innen_phasen = []
    for nr, t in phasen_ts:
        o = vorrat.get(nr, []).pop(0) if vorrat.get(nr) else None
        innen_phasen.append({"phase": nr, "t": t - beginn,
                             "lat": o.get("lat") if o else None,
                             "lon": o.get("lon") if o else None})
    ref = bestand["client_ref"] if bestand is not None else "man-" + os.urandom(8).hex()
    liste.append(("ort_huelle", ortsdaten.einsatz_verschliessen(
        {"fassung": 1, "phasen": innen_phasen, "rea": rea_innen}, sec1, ref)))
    liste.append(("ort_phasen", json.dumps([[nr, t] for nr, t in phasen_ts])))
    if bestand is None:
        liste.append(("client_ref", ref))
    else:
        punkte = ortsdaten.spur_oeffnen(bestand.get("spur_stuecke") or [], "spur", ref,
                                        bestand.get("spur_sperren") or [], priv)
        h = ortsdaten.ortshoehe(punkte, phasen_ts)
        if h is not None:
            geschuetzt["ortshoehe"] = h

    blob = krypto.pat_blob(geschuetzt, s.inhaltsschluessel)
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
        bestand = s.get(f"api/mission.php?id={treffer[0]['id']}").json()
        felder = formularfelder(daten, s, bestand)
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
        felder = formularfelder(daten, s)
        formular_senden(s, felder, {"day_id": tage[daten["dienst"]]})
        getan.add(daten["kennung"])
        n += 1
    lauf.zustand["manuell"] = sorted(getan)
    melde(f"  {n} Einsätze von Hand angelegt (origin=manual).")


# ------------------------------------------------------------ 8. Papierkorb
def stufe_papierkorb(lauf: Lauf) -> None:
    """Dauerzustand herstellen: ein gelöschter Einsatz und ein gelöschter
    Diensttag.

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
    """Senden, löschen, endgültig entfernen, ERNEUT senden.

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

    # Ortsdaten im Klartext weist `ingest.php` ab (400 klartext) — die Pakete
    # werden verschlossen wie in der Stufe `ingest`, sonst misst der Schritt
    # eine Abweisung statt der Sperrliste.
    pakete = pakete_verschliessen(s.kontoschluessel(),
                                  [json.loads(p.read_text("utf-8")) for p in dateien])

    def senden() -> None:
        for koerper in pakete:
            r = s.s.post(f"{lauf.basis}/ingest.php", data=json.dumps(koerper),
                         headers=kopf, timeout=60)
            if r.status_code >= 400:
                raise RuntimeError(f"ingest.php antwortet {r.status_code}: {r.text[:200]}")

    senden()                                              # 1. senden
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

    senden()                                              # 4. erneut senden
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


# ----------------------------------------------------------- 10. Schneiden
def stufe_schneiden(lauf: Lauf) -> None:
    """Aus einem Ruhesegment einen Einsatz schneiden.

    WOFUER. Der Referenzbestand hatte keinen Schnitt -- und damit keinen
    Sperrvermerk in `track_cuts`. Die Zusage (der Vermerk muss die
    Konto-Sicherung ueberstehen) war deshalb nur ueber eine eigens gebaute
    Probe zu belegen. Mit einem Schnitt IM Bestand prueft ihn der Demo-Reset
    auf dem Produktivserver bei jedem Reset von selbst.

    WAS DER SCHNITT ANLEGT: einen Einsatz mit `origin = schnitt`, die drei
    Phasen 3/4/7, die gewanderten Spurpunkte und den Sperrvermerk am
    Quellsegment. Er fuellt KEINE Einsatzfelder -- Einsatzort, Alter und
    Diagnose sind Ende-zu-Ende-verschluesselt und entstehen im Browser.

    Das Segment findet `segment_finden()` (ueber `start_hhmm`).
    """
    s = lauf.demo_sitzung()
    if lauf.zustand.get("schnitte"):
        melde("  Schnitt besteht bereits.")
        return
    if not lauf.zustand.get("diensttage"):
        raise RuntimeError("Ohne die Stufe `zuordnen` gibt es keine Diensttag-Kennungen.")

    getan = []
    for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        for sc in d.get("schnitte", []):
            kennung = d["kennung"]
            liste, seg = segment_finden(lauf, s, kennung, d, sc["segment"])
            rest_id = seg["id"]
            # DER TAGESVERSATZ (F-RD-10): Ein Schnitt nach Mitternacht in einem
            # Segment ueber Mitternacht (D22) liegt am Folgetag. Bis RD-03
            # stand hier immer 0, und der Bestand hatte keinen solchen Fall.
            bt = tagesversatz(liste["day"], sc["beginn"])
            et = tagesversatz(liste["day"], sc["ende"])

            # DER BROWSER SCHNEIDET -- und darum hier das Werkzeug:
            # Segment oeffnen, die Punkte im Zeitraum nehmen und als
            # Spur des neuen Einsatzes verschliessen, gebunden an dessen Kennung.
            # Der Zeitraum wird wie auf dem Server gerechnet (`local_to_utc()`).
            sec1, priv = s.kontopaar()
            sperren = [{"von": x["von_ts"], "bis": x["bis_ts"]} for x in seg.get("schnitte", [])]
            punkte = ortsdaten.spur_oeffnen(seg.get("spur_stuecke") or [], "ruhe",
                                            seg["client_ref"], sperren, priv)
            von, bis = ortsdaten.zeiten([(liste["day"], sc["beginn"][11:16], bt),
                                         (liste["day"], sc["ende"][11:16], et)])
            drin = [p for p in punkte if von <= p["ts"] <= bis]
            if not drin:
                raise RuntimeError(f"{kennung}: im Zeitraum {sc['beginn'][11:16]}–"
                                   f"{sc['ende'][11:16]} liegt kein Punkt des Segments")
            ref = "cut-" + os.urandom(8).hex()
            antwort = s.json_post("api/schneiden.php", {
                "action": "schneiden", "rest_id": rest_id,
                "beginn": sc["beginn"][11:16], "ende": sc["ende"][11:16],
                "beginn_tag": bt, "ende_tag": et,
                "phasen": {nr: wann[11:16] for nr, wann in sc["phasen"].items()},
                "client_ref": ref,
                "stuecke": ortsdaten.stuecke_bauen(drin, "spur", ref, sec1, neu=True),
            })
            if antwort.status_code != 200:
                raise RuntimeError(f"{kennung}: api/schneiden.php antwortete "
                                   f"{antwort.status_code}: {antwort.text[:300]}")
            o = antwort.json()
            # `genommen > 0` IST KEINE PRUEFUNG: Bei null Punkten rollt der
            # Server zurueck und antwortet 409 -- eine 200 traegt darum immer
            # mehr als null. Was hier wirklich schiefgehen kann, ist eine
            # STILL verworfene Phase; sie steht als `rejected` im Rumpf.
            if o.get("rejected"):
                raise RuntimeError(f"{kennung}: der Schnitt hat Angaben verworfen: "
                                   f"{o['rejected']}")
            getan.append({"dienst": kennung, "segment": sc["segment"], "rest_id": rest_id,
                          "mission_id": o.get("mission_id"), "genommen": o.get("genommen"),
                          "tag": bt, "phasen": len(sc["phasen"])})
            # „geblieben" gibt es seit Web 22.2.0 nicht mehr: Die Punkte
            # bleiben gesperrt im Segment (F-RD-17; hier stand „None geblieben").
            melde(f"  {kennung}: aus {sc['segment']} geschnitten — Einsatz "
                  f"{o.get('mission_id')}, {o.get('genommen')} Punkte genommen "
                  f"(im Segment gesperrt), {len(sc['phasen'])} Phasen"
                  + (", am Folgetag" if bt else ""))

    # SO VIELE, WIE IN DEN QUELLDATEN STEHEN. Die Zeile hiess bis
    # zum Demo-Ausbau `!= 1`. Sie ist nicht gestrichen, sondern an die Quelle
    # gebunden: Ein Schnitt, der still ausfaellt, bliebe sonst unbemerkt -- und
    # mit ihm der Sperrvermerk, den die Zusage im Bestand haben will.
    soll = sum(len(json.loads(pfad.read_text("utf-8")).get("schnitte", []))
               for pfad in sorted((QUELLE / "dienste").glob("D*.json")))
    if len(getan) != soll:
        raise RuntimeError(f"erwartet werden {soll} Schnitte (so viele stehen in den "
                           f"Quelldaten), ausgefuehrt: {len(getan)}")
    lauf.zustand["schnitte"] = getan


# ------------------------------------------------------------- 11. Loeschen
def stufe_loeschen(lauf: Lauf) -> None:
    """Zeitraeume eines Ruhesegments loeschen — wie der Knopf „Zeitraum
    löschen" (`assets/schneiden.js`, `zeitraumLoeschen()`), Konzept RD.

    Der Browser oeffnet das Segment OHNE die Sperren der Schnitte (deren
    Punkte bleiben im Segment, gesperrt), nimmt die Punkte ausserhalb des
    Zeitraums, verschliesst sie neu — als Geraetestuecke, oder gepackt in
    derselben Stufe, wenn das Segment schon verdichtet ist — und schickt sie
    mit dem gelesenen Stand. Der Server tauscht nur, wenn sich nichts
    geaendert hat, und vermerkt den Zeitraum (`track_cuts` ohne Einsatz).

    VORHER EINE PROBE AUFS EXEMPEL (F-RD-09): ein Versuch ueber die ganze
    Aufzeichnung, ohne ein einziges Stueck. Der Endpunkt muss `409 alles`
    antworten — dafuer ist der Papierkorb da. Die Antwort steht im Zustand.
    """
    s = lauf.demo_sitzung()
    if lauf.zustand.get("loeschungen") is not None:
        melde("  Loeschungen bestehen bereits.")
        return
    sec1, priv = s.kontopaar()
    getan, probe = [], None
    for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
        d = json.loads(pfad.read_text("utf-8"))
        for x in d.get("loeschungen", []):
            kennung = d["kennung"]
            liste, seg = segment_finden(lauf, s, kennung, d, x["segment"])
            stand = [[st["seq_from"], st["punkte"]] for st in seg.get("spur_stuecke") or []]
            punkte = ortsdaten.spur_oeffnen(seg.get("spur_stuecke") or [], "ruhe",
                                            seg["client_ref"], [], priv)
            if probe is None:
                quelle = next(r for r in d["ruhesegmente"] if r["client_ref"] == x["segment"])
                a = s.json_post("api/schneiden.php", {
                    "action": "loeschen", "rest_id": seg["id"],
                    "beginn": quelle["beginn"][11:16], "ende": quelle["ende"][11:16],
                    "beginn_tag": tagesversatz(liste["day"], quelle["beginn"]),
                    "ende_tag": tagesversatz(liste["day"], quelle["ende"]),
                    "stand": stand, "stuecke": [], "geloescht": len(punkte)})
                probe = {"status": a.status_code, "error": (a.json() if a.headers.get(
                    "content-type", "").startswith("application/json") else {}).get("error")}
                if (probe["status"], probe["error"]) != (409, "alles"):
                    raise RuntimeError(f"{kennung}: Loeschen der ganzen Aufzeichnung antwortete "
                                       f"{probe} — erwartet war 409 alles (F-RD-09)")
                melde(f"  Probe: die ganze Aufzeichnung loeschen → {probe['status']} {probe['error']}")
            bt, et = tagesversatz(liste["day"], x["beginn"]), tagesversatz(liste["day"], x["ende"])
            von, bis = ortsdaten.zeiten([(liste["day"], x["beginn"][11:16], bt),
                                         (liste["day"], x["ende"][11:16], et)])
            drin = [p for p in punkte if von <= p["ts"] <= bis]
            rest = [p for p in punkte if p["ts"] < von or p["ts"] > bis]
            if not drin or not rest:
                raise RuntimeError(f"{kennung}: {len(drin)} Punkte im Zeitraum, {len(rest)} daneben")
            alle = seg.get("spur_stuecke") or []
            stufe = max([1] + [int(st.get("stufe") or 1) for st in alle])
            if stufe >= 2:
                vonseq = min(st["seq_from"] for st in alle)
                marke = max([st["seq_from"] + (st["n_original"] if int(st.get("stufe") or 1) >= 2
                                               else st["punkte"]) for st in alle] + [rest[-1]["seq"] + 1])
                stuecke = [ortsdaten.gepackt_bauen(rest, "ruhe", seg["client_ref"], sec1, stufe,
                                                   vonseq, marke - vonseq)]
            else:
                stuecke = ortsdaten.stuecke_bauen(rest, "ruhe", seg["client_ref"], sec1)
            a = s.json_post("api/schneiden.php", {
                "action": "loeschen", "rest_id": seg["id"],
                "beginn": x["beginn"][11:16], "ende": x["ende"][11:16],
                "beginn_tag": bt, "ende_tag": et,
                "stand": stand, "stuecke": stuecke, "geloescht": len(drin)})
            if a.status_code != 200:
                raise RuntimeError(f"{kennung}: Loeschen antwortete {a.status_code}: {a.text[:300]}")
            getan.append({"dienst": kennung, "segment": x["segment"], "rest_id": seg["id"],
                          "geloescht": len(drin), "uebrig": len(rest)})
            melde(f"  {kennung}: in {x['segment']} {x['beginn'][11:16]}–{x['ende'][11:16]} "
                  f"geloescht — {len(drin)} Punkte, {len(rest)} bleiben")
    soll = sum(len(json.loads(pfad.read_text("utf-8")).get("loeschungen", []))
               for pfad in sorted((QUELLE / "dienste").glob("D*.json")))
    if len(getan) != soll:
        raise RuntimeError(f"erwartet {soll} Loeschungen, ausgefuehrt {len(getan)}")
    lauf.zustand["loeschungen"] = getan
    lauf.zustand["probe_alles"] = probe


# ----------------------------------------------------------- 12. Verdichten
def demo_kennzeichnung(aufheben: bool) -> None:
    """Die Demo-Kennzeichnung des Referenzkontos aufheben oder wieder setzen
    (`demo_kennzeichnen.php`). Siehe `stufe_verdichten()`."""
    befehl = ["php", str(HIER / "demo_kennzeichnen.php")] + (["--aufheben"] if aufheben else [])
    r = subprocess.run(befehl, capture_output=True, text=True, check=False)
    if r.returncode != 0:
        raise RuntimeError(f"demo_kennzeichnen.php: {r.stderr.strip() or r.stdout.strip()}")
    melde("  " + r.stdout.strip().splitlines()[0])


def stufe_verdichten(lauf: Lauf) -> None:
    """Spuren auf Stufe 2 oder 3 bringen — ueber den regulaeren Wartungslauf
    (`api/wartungslauf.php`), wie `assets/wartungslauf.js` es tut (Konzept RD,
    E-RD-05): Liste holen, je Spur die Stuecke oeffnen (geschnittene Punkte
    fallen dabei weg), fuer Stufe 3 ausduennen und die Ausduennung pruefen —
    beides mit der PHP-Referenz (`ortsdaten.ausduennen()`) —, EIN gepacktes
    Stueck bauen, zur Probe wieder oeffnen und vergleichen, dann tauschen.

    WELCHE STUFE, SAGT DIE QUELLE. Der Wartungslauf nennt fuer jede Spur des
    Bestands Stufe 3 (sie sind alle aelter als 31 Tage); er nimmt aber jede
    Stufe an, die nicht unter dem Bestand liegt (F-RD-07). Was nicht in einer
    Liste `verdichten` steht, kommt auf die Auslassliste und bleibt Stufe 1.

    DIE DEMO-KENNZEICHNUNG RUHT WAEHRENDDESSEN. Das Referenzkonto ist auf
    dieser Anlage das Demo-Konto, und dem antwortet der Wartungslauf mit
    leeren Listen und `409 demo` (Nr. 459, F-RD-20). Die Stufe hebt die
    Kennzeichnung auf und setzt sie im `finally` wieder — mit neuer
    Resetmarke wie beim ersten Kennzeichnen. Dazwischen arbeitet nur dieses
    Werkzeug; im Browser meldet sich niemand an (die stille Umstellung der
    Huelle auf `edka1:` liefe nur dort).
    """
    if lauf.zustand.get("verdichtet") is not None:
        melde("  Verdichtung besteht bereits.")
        return
    ziele: dict[str, int] = {}
    for pfad in sorted((QUELLE / "dienste").glob("D*.json")):
        for v in json.loads(pfad.read_text("utf-8")).get("verdichten", []):
            ziele[v.get("segment") or v["einsatz"]] = v["stufe"]
    s = lauf.demo_sitzung()
    sec1, priv = s.kontopaar()
    getan: dict[str, dict] = {}
    demo_kennzeichnung(aufheben=True)
    try:
        # DIE AUSLASSLISTE TRAEGT HOECHSTENS 200 EINTRAEGE: Der Server liest
        # nur so viele (`spur2_faellig_kandidaten()`, `array_slice(…, 0, 200)`).
        # Der Bestand hat ueber 200 Spuren, und jede auf Stufe 1 ist faellig
        # — die Liste wird also nie leer. Die Stufe hoert auf, sobald jeder
        # Auftrag getauscht ist; wer sie erst ans Ende der Liste schickt, laeuft
        # im Kreis (gemessen beim ersten Lauf: nach 60 Listen kein Ende).
        auslassen: list = []
        while set(ziele) - set(getan):
            if len(auslassen) > 200:
                raise RuntimeError(f"nach {len(auslassen)} ausgelassenen Spuren fehlen noch "
                                   f"{sorted(set(ziele) - set(getan))} — die Auslassliste des "
                                   f"Servers reicht nur 200 weit")
            a = s.json_post("api/wartungslauf.php", {"aktion": "liste", "auslassen": auslassen})
            if a.status_code != 200:
                raise RuntimeError(f"Wartungslauf liste: {a.status_code} {a.text[:300]}")
            spuren = a.json().get("spuren", [])
            if not spuren:
                break
            for e in spuren:
                auslassen.append([e["art"], e["id"]])
                if e["ref"] not in ziele or e["ref"] in getan:
                    continue
                getan[e["ref"]] = verdichten_eine(s, e, ziele[e["ref"]], sec1, priv)
                g = getan[e["ref"]]
                melde(f"  {e['art']:7s} {e['ref']}: Stufe {g['stufe']}, {g['vorher']} → "
                      f"{g['nachher']} Punkte (n_original {g['n_original']})")
    finally:
        demo_kennzeichnung(aufheben=False)
    fehlt = sorted(set(ziele) - set(getan))
    if fehlt:
        raise RuntimeError(f"nicht verdichtet (der Wartungslauf nannte sie nicht): {fehlt}")
    lauf.zustand["verdichtet"] = getan


def verdichten_eine(s, e: dict, stufe: int, sec1: bytes, priv) -> dict:
    """Eine Spur — `eine()` aus `assets/wartungslauf.js`, ohne die Ortshoehe."""
    art_h = "ruhe" if e["art"] == "rest" else "spur"
    punkte = ortsdaten.spur_oeffnen(e["stuecke"], art_h, e["ref"], e["sperren"], priv)
    if not punkte:
        raise RuntimeError(f"{e['ref']}: nach dem Ausblenden kein Punkt (F-RD-08)")
    behalten = punkte
    if stufe == 3:
        zeiten = [int(datetime.strptime(p["occurred_at"], "%Y-%m-%d %H:%M:%S")
                      .replace(tzinfo=timezone.utc).timestamp()) for p in e.get("phases") or []]
        behalten = [punkte[i] for i in ortsdaten.ausduennen(punkte, zeiten)]
    von = min(st["seq_from"] for st in e["stuecke"])
    marke = max(st["seq_from"] + (st["n_original"] if int(st.get("stufe") or 1) >= 2 else st["punkte"])
                for st in e["stuecke"])
    stueck = ortsdaten.gepackt_bauen(behalten, art_h, e["ref"], sec1, stufe, von, marke - von)
    # DER RUNDLAUF: das neue Stueck oeffnen und Punkt fuer Punkt vergleichen.
    wieder = ortsdaten.spur_oeffnen([stueck], art_h, e["ref"], [], priv)
    runde = ortsdaten._runde
    if len(wieder) != len(behalten) or any(
            runde(a["lat"] * 1e7) != runde(b["lat"] * 1e7) or runde(a["lon"] * 1e7) != runde(b["lon"] * 1e7)
            or a["ts"] != b["ts"] or (a["ele"] is None) != (b["ele"] is None)
            for a, b in zip(wieder, behalten)):
        raise RuntimeError(f"{e['ref']}: Der Rundlauf weicht ab — nichts geschickt")
    a = s.json_post("api/wartungslauf.php", {
        "aktion": "tauschen", "art": e["art"], "id": e["id"],
        "stand": [[st["seq_from"], st["punkte"]] for st in e["stuecke"]], "stueck": stueck})
    if a.status_code != 200:
        raise RuntimeError(f"{e['ref']}: tauschen antwortete {a.status_code}: {a.text[:300]}")
    return {"art": e["art"], "id": e["id"], "stufe": stufe, "vorher": len(punkte),
            "nachher": len(behalten), "n_original": marke - von,
            "gesperrt": len(e["sperren"])}


# ------------------------------------------------------------------ Ablauf
def main() -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--basis", default="https://127.0.0.1:8443")
    p.add_argument("--stufen", default=",".join(ALLE_STUFEN))
    p.add_argument("--admin-email", default="admin@example.invalid")
    p.add_argument("--admin-passwort", default="pruefstandzugang2026")
    # Das Geheimnis des Zweitfaktors: Das Admin-Konto ist
    # eine Pflichtrolle. Leer heisst `NADOKU_TOTP`, sonst das der Sandbox --
    # gegen eine andere Anlage (`--basis`) gehoert deren Geheimnis hierher.
    # Das Demo-Konto braucht keines; nur die Stufe `konto` meldet sich als
    # Admin an.
    p.add_argument("--admin-totp", default="",
                   help="Geheimnis des Zweitfaktors des Admin-Kontos (Base32)")
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
        "konto": lambda: stufe_konto(lauf, (a.admin_email, a.admin_passwort,
                                             a.admin_totp)),
        "stammdaten": lambda: stufe_stammdaten(lauf),
        "geraet": lambda: stufe_geraet(lauf),
        "ingest": lambda: stufe_ingest(lauf),
        "zuordnen": lambda: stufe_zuordnen(lauf),
        "nachtragen": lambda: stufe_nachtragen(lauf),
        "manuell": lambda: stufe_manuell(lauf),
        "papierkorb": lambda: stufe_papierkorb(lauf),
        "sperrliste": lambda: stufe_sperrliste(lauf),
        "schneiden": lambda: stufe_schneiden(lauf),
        "loeschen": lambda: stufe_loeschen(lauf),
        "verdichten": lambda: stufe_verdichten(lauf),
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
