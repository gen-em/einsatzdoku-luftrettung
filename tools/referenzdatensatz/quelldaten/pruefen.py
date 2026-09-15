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
# SPEICHERBARE Ereignisarten: NEUN, nicht zehn. 'beginn' steht zwar in
# RESUS_LABELS (db.php) und in docs/JSON-Vertrag.md 3.3, wird aber von KEINEM
# Schreibweg als Ereignis angenommen -- ingest.php verwirft es still,
# einsatz_form.php weist es ab. Der Reanimationsbeginn steckt in `started_at`
# der Sitzung. Siehe Fehlerfund F-P1-F.
REA_TYPEN = {"zugang", "adrenalin", "rhythmuskontrolle", "defibrillation",
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

def teilenummern() -> set[str]:
    """Die Teilenummern aus `server/geraetemodelle.php`.

    GELESEN, NICHT ABGESCHRIEBEN. Die Tabelle ist erzeugt und waechst; eine
    Kopie hier waere nach dem naechsten Lauf des Holskripts falsch, ohne dass
    etwas anschlaegt. Der Umweg ueber einen regulaeren Ausdruck ist haesslich
    und ehrlich: Er scheitert sichtbar, wenn die Tabelle ihre Form aendert,
    statt eine leere Menge zu liefern.
    """
    quelle = HIER.parent.parent.parent / "server" / "geraetemodelle.php"
    if not quelle.exists():
        return set()
    return set(re.findall(r"'(\d{3}-[A-Z0-9]+-\d{2})'\s*=>", quelle.read_text("utf-8")))


# --- Abdeckungsmatrix (Konzept Abschnitt 5) --------------------------------
# Je Zeile: (Dimension, Anforderung, Marken -- eine davon genuegt).
# Zeilen mit leerer Markenmenge werden STRUKTURELL geprueft (siehe unten).
MATRIX = [
    ("Erfassungsart (R4)", "luftgebunden mit Track (Ingest)", ["erfassung-luft-track"]),
    ("Erfassungsart (R4)", "bodengebunden mit Track (Ingest)", ["erfassung-boden-track"]),
    ("Erfassungsart (R4)", "nachträglich ohne Track", ["erfassung-nachtraeglich-ohne-track"]),
    # SEIT DEM DEMO-AUSBAU EIGENS: ein nachgetragener Einsatz, der zwar keine
    # Spur hat, aber Ort- UND Zielkoordinate. Die Karte zeichnet daraus eine
    # gestrichelte Luftlinie — die einzige Darstellung, die es ohne Track
    # ueberhaupt gibt, und bis dahin belegte sie kein Dokument.
    ("Erfassungsart (R4)", "ohne Track, mit Ort- und Zielkoordinate (Luftlinie)",
     ["luftlinie-ort-ziel"]),
    # ALLE SECHS HERKUNFTSWERTE (server/geraete_lib.php HERKUNFT_WERTE), seit
    # R64/AP4. Bis dahin standen hier drei -- und die Zeile "watch" blieb
    # gruen, obwohl sechs der Einsaetze, die sie trugen, an einem HANDY
    # haengen. Eine Matrix, die drei von sechs Werten prueft und dabei gruen
    # meldet, ist genau das Muster F-P3-AQ.
    ("Herkunft", "watch", ["herkunft-watch"]),
    ("Herkunft", "android", ["herkunft-android"]),
    ("Herkunft", "wear", ["herkunft-wear"]),
    ("Herkunft", "manual", ["herkunft-manuell"]),
    ("Herkunft", "import", ["herkunft-import"]),
    ("Herkunft", "schnitt", ["herkunft-schnitt"]),
    ("Herkunft", "Schnitte an mehr als einem Diensttag", []),
    ("Geräte", "beide Geräte mit Block (Momentaufnahme möglich)", []),
    ("Geräte", "eine Uhr und ein Handy", []),
    ("Diensttage", "Luftdienst", ["dienst-luft"]),
    ("Diensttage", "Bodendienst", ["dienst-boden"]),
    ("Diensttage", "Kalendertag mit zwei Diensten", ["zwei-dienste-ein-tag"]),
    ("Diensttage", "Dienst über Mitternacht", ["dienst-ueber-mitternacht"]),
    ("Diensttage", "Einsatzdatum ≠ Diensttag", ["einsatzdatum-abweichend"]),
    ("Diensttage", "Diensttag ohne Einsatz", ["dienst-ohne-einsatz"]),
    ("Diensttage", "Tagesnotizen", ["notizen-diensttag"]),
    # DIE DREI TYPEN AUS S9 IM BETRIEB (Demo-Ausbau). Sie standen seit Web
    # 16.0.0 als Stammdaten im Bestand und trugen keinen einzigen Diensttag —
    # der Bestand kannte sie, die Anwendung zeigte sie nie.
    ("Diensttage", "Typ Bergwacht", ["dienst-bergwacht"]),
    ("Diensttage", "Typ Veranstaltung", ["dienst-veranstaltung"]),
    ("Diensttage", "ohne Standort (base_id NULL)", ["tag-ohne-standort"]),
    ("Diensttage", "ohne Rollensatz (Typ ohne Vorlagen)", ["tag-ohne-rollen"]),
    ("Diensttage", "zweiter Dienst am Abend, ohne Überschneidung", ["abenddienst"]),
    ("Besatzung", "alle Rollen des Katalogs belegt", []),
    ("Besatzung", "abweichende Besatzung (crew_override)", ["besatzung-abweichend"]),
    ("Phasen", "alle Phasen 2–9 im Datensatz", []),
    ("Phasen", "Mehrfacheintrag derselben Phase", ["phasen-mehrfach"]),
    ("Phasen", "unvollständige Phasen", ["phasen-unvollstaendig"]),
    ("Phasen", "nicht abgeschlossener Einsatz", ["einsatz-nicht-abgeschlossen"]),
    ("Reanimation", "Einsatz mit einer Sitzung", ["rea-einzeln"]),
    ("Reanimation", "Einsatz mit mehreren Sitzungen", ["rea-mehrere-sitzungen"]),
    ("Reanimation", "alle speicherbaren Ereignisarten (neun)", []),
    ("Transport", "Transportart air", ["transport-air"]),
    ("Transport", "Transportart ground", ["transport-ground"]),
    ("Transport", "Transportart ambulant", ["transport-ambulant"]),
    ("Transport", "Transportart leer", ["transport-leer"]),
    ("Transport", "NA-Begleitung", ["na-escort"]),
    ("Transport", "Fehleinsatz / Storno", ["fehleinsatz"]),
    ("Transport", "Sekundärtransport", ["sekundaertransport"]),
    ("Transport", "Sekundärtransport bodengebunden", ["sekundaer-boden"]),
    ("Transport", "Transportziel ad hoc (Freitext mit Koordinate)", ["ziel-adhoc"]),
    ("Transport", "Schockraum", ["schockraum"]),
    ("Transport", "Zielklinik mit Koordinate", ["zielklinik-koordinate"]),
    ("Transport", "Zielklinik ohne Koordinate", ["zielklinik-ohne-koordinate"]),
    ("Abfahrtort", "Regel base", ["start-base"]),
    ("Abfahrtort", "Regel prev_site", ["start-prevsite"]),
    ("Abfahrtort", "Regel prev_dest", ["start-prevdest"]),
    ("Abfahrtort", "Regel manual (verschlüsselter pat.start)", ["start-manual"]),
    # DIE DIMENSION HIESS „LUFTSPEZIFIK" UND IST ES NICHT MEHR (E-DA-06):
    # Winde und Bergwacht haengen an der Faehigkeit des Diensttags, nicht an
    # seiner Art, und seit Web 20.3.0 fuehrt ein Bergwachtnotarzt sie auch am
    # Boden. Der Name der Dimension steht in `matrix_abgleich.md` und im
    # Prueffdokument; ein falscher Name dort faerbt jede spaetere Lesung.
    ("Bergrettung", "Winde mit Cycles", ["winde-cycles"]),
    ("Bergrettung", "Cycles mit Patient", ["winde-cycles-patient"]),
    ("Bergrettung", "Luftverladung", ["winde-luftverladung"]),
    ("Bergrettung", "Bergwacht mit Einheit und bw_info", ["bergwacht-info"]),
    ("Bergrettung", "Winde am bodengebundenen Bergwacht-Dienst",
     ["winde-boden-bergwacht"]),
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
    ("Spur", "Fußweg (Wegpunkt `zustieg`)", ["fussweg-zustieg"]),
    ("Ruhezeiten", "Segmente mit Track", ["ruhe-track"]),
    ("Ruhezeiten", "mehrere Segmente je Dienst", ["ruhe-mehrere"]),
    ("Ruhezeiten", "nicht abgeschlossenes Segment", ["ruhe-nicht-abgeschlossen"]),
    ("Papierkorb", "gelöschter Einsatz (einzeln)", ["papierkorb-einsatz"]),
    ("Papierkorb", "gelöschter Diensttag", ["papierkorb-diensttag"]),
    ("Papierkorb", "Einsätze mit deleted_with_day", ["papierkorb-einsatz-mit-tag"]),
    ("Papierkorb", "Sperrlisten-Fall als Ablaufschritt", ["sperrliste-ablaufschritt"]),
    ("Stammdaten", "≥ 3 Standorte, alle mit Koordinaten", []),
    ("Stammdaten", "≥ 1 Diensttag ohne Standort mit `spur_ausgangspunkt`", []),
    ("Stammdaten", "≥ 2 Luft-Rettungsmittel mit/ohne Fähigkeiten", []),
    ("Stammdaten", "≥ 1 Boden-Rettungsmittel", []),
    ("Stammdaten", "Fähigkeiten am bodengebundenen Bergwacht-Rettungsmittel", []),
    ("Stammdaten", "Rettungsmittel mit und ohne Standort", []),
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


def bewegungsfenster(dienst: dict, einsatz: dict, namen: list[str],
                     koords: list) -> list[tuple[float, float]]:
    """Zeitfenster der Bewegungsabschnitte — als Dauer in Minuten je Abschnitt.

    SIE STAND BIS ZUM DEMO-AUSBAU ZWEIMAL, hier und in `erzeugen._fenster()`.
    Der Kommentar begruendete das mit „die Regel selbst ist kurz". Sie war es;
    mit dem Wegpunkt `zustieg` (E-DA-13) ist sie es nicht mehr — vier
    Teilstuecke und drei Phasenfenster verlangen eine Zuteilung. Zwei
    Fassungen davon hiessen, dass dieses Skript die Erreichbarkeit eines
    ANDEREN Ablaufs misst als den, den der Generator zeichnet, und dass beide
    dabei Erfolg melden. Die Ableitung steht deshalb jetzt in `wegpunkte.py`,
    dem Modul, das es fuer genau diesen Zweck schon gibt.
    """
    return wegpunkte.fenster(einsatz, dienst, namen, koords)


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
    # DIE ZWEI GERAETEBLOECKE (R64/AP4). Sie bestimmen, was beim Koppeln an
    # `pair.php` geht -- und damit die Momentaufnahme, die an jedem Einsatz
    # und jedem Segment haengt. Ohne sie stuende sie im ganzen Bestand auf
    # NULL, und der edbak-Kreislauf belegte fuer R64 nichts.
    geraete = json.loads((HIER / "geraete.json").read_text("utf-8"))["geraete"]
    MODELLE = teilenummern()
    # DER NEUESTE DIENSTTAG. `index.php` oeffnet ihn ohne Zutun, und daran
    # haengen sichtpruefung.mjs und vier Seiten des Bilderlaufs -- deshalb
    # darf der Schnitt nicht dort liegen.
    neuester_tag = max(d["dienst"]["day"] for d in dienste)

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

        lauf.pruefe(dn["rettungsmittel"] in fahrzeug, f"{n}: Rettungsmittel {dn['rettungsmittel']!r} fehlt in den Stammdaten")
        rm = fahrzeug.get(dn["rettungsmittel"], {})
        lauf.pruefe(rm.get("art") == dn["art"], f"{n}: Art {dn['art']!r} passt nicht zum Rettungsmittel ({rm.get('art')!r})")

        # DER DIENSTTAG OHNE STANDORT (E-DA-08, seit Web 16.0.0 moeglich).
        #
        # Er ist kein Sonderfall der Quelldaten, sondern einer der Anwendung:
        # `index.php` (`vehicleBaseSync()`) laesst das Standortfeld weg, sobald
        # der Typ keinen verlangt, und `api/day.php` bietet dann keine
        # Vorschlagslisten an. Was daran haengt, prueft dieser Block — und zwar
        # in BEIDE Richtungen: Ein Tag ohne Standort an einem Rettungsmittel
        # MIT Standort waere so falsch wie umgekehrt.
        ohne_standort = dn["standort"] is None
        if ohne_standort:
            lauf.pruefe(bool(rm.get("ohne_standort")),
                        f"{n}: Diensttag ohne Standort, aber {dn['rettungsmittel']!r} "
                        f"hat einen — der Einspielweg setzt base_id aus dem Rettungsmittel")
            lauf.pruefe("spur_ausgangspunkt" in dn,
                        f"{n}: Diensttag ohne Standort braucht einen 'spur_ausgangspunkt' "
                        f"— ohne ihn loest kein Wegpunkt 'basis' auf")
            lauf.pruefe(not any(dn["besatzung"].values()),
                        f"{n}: Diensttag ohne Standort darf keine Besatzung führen "
                        f"— die Vorbelegungen hängen am Standort (E15)")
            merke(["tag-ohne-standort"], n)
        else:
            lauf.pruefe(dn["standort"] in standort,
                        f"{n}: Standort {dn['standort']!r} fehlt in den Stammdaten")
            lauf.pruefe(rm.get("standort") == dn["standort"] and not rm.get("ohne_standort"),
                        f"{n}: Rettungsmittel gehört zu "
                        f"{'keinem Standort' if rm.get('ohne_standort') else repr(rm.get('standort'))}, "
                        f"nicht zu {dn['standort']!r}")

        lauf.pruefe(dn["day_ref"] not in day_refs, f"{n}: day_ref {dn['day_ref']} kommt schon in {day_refs.get(dn['day_ref'])} vor")
        day_refs[dn["day_ref"]] = n

        # MARKEN AUS DEM INHALT, NICHT AUS DER BEHAUPTUNG (FORMAT.md). Welcher
        # Typ ein Diensttag ist und ob er einen Rollensatz bekommt, steht am
        # Rettungsmittel — nicht in einer Markenliste, die jemand tippt.
        typ = rm.get("typ", "standard")
        if typ in ("bergwacht", "veranstaltung", "sonstiges"):
            merke([f"dienst-{typ}"], n)
        if not rm.get("rollen"):
            merke(["tag-ohne-rollen"], n)

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
        # SPUR-AUSGANGSPUNKT: PFLICHT UND VERBOT IN EINEM (E-DA-09).
        #
        # Er ist noetig, wo der Wegpunkt `basis` sonst auf nichts aufloest —
        # an einem Standort ohne Koordinaten und an einem Tag ohne Standort.
        # Er ist VERBOTEN, wo der Standort selbst Koordinaten fuehrt: Dann
        # stuenden zwei Wahrheiten uebereinander, `basis_von()` naehme
        # stillschweigend die eine, und niemand wuesste, welche. Bis zum
        # Demo-Ausbau gab es nur die Pflicht; die acht Bodendienste an Talwang
        # trugen den Punkt, und der Standort trug keine Koordinaten.
        standort_hat_koord = (not ohne_standort
                              and standort.get(dn["standort"], {}).get("lat") is not None)
        if not standort_hat_koord:
            lauf.pruefe("spur_ausgangspunkt" in dn,
                        f"{n}: ohne Standortkoordinaten, aber ohne 'spur_ausgangspunkt'")
        else:
            lauf.pruefe("spur_ausgangspunkt" not in dn,
                        f"{n}: 'spur_ausgangspunkt' neben einem Standort MIT Koordinaten "
                        f"— eine zweite Wahrheit (E-DA-09)")

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
                    lauf.pruefe(typ in REA_TYPEN,
                                f"{wo}: Reanimationsart {typ!r} ist als Ereignis nicht "
                                f"speicherbar (F-P1-F)")
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
            grenze_fahrt = 250.0 if dn["art"] == "air" else 80.0
            koords = [k for _, k in aufgeloest if k]
            namen = [nm for nm, k in aufgeloest if k]
            fenster = bewegungsfenster(dn, e, namen, koords)
            for i in range(min(len(koords) - 1, len(fenster))):
                # DER FUSSWEG HAT SEINE EIGENE GRENZE (E-DA-13). 80 km/h waeren
                # fuer ihn keine Pruefung, sondern eine Erlaubnis: Ein Steig,
                # fuer den zwei Minuten vorgesehen sind, laege mit 12 km/h weit
                # darunter und trotzdem weit ueber allem, was ein Mensch mit
                # einem Akja geht. Acht km/h ist zuegiges Bergabgehen ohne Last
                # — darueber ist die Zeitangabe der Phasen falsch, nicht der
                # Generator.
                zu_fuss = wegpunkte.ist_fussweg(namen[i], namen[i + 1])
                grenze = 8.0 if zu_fuss else grenze_fahrt
                strecke = wegpunkte.abstand_m(*koords[i], *koords[i + 1]) / 1000.0
                minuten = (fenster[i][1] - fenster[i][0]) / 60.0
                if minuten <= 0:
                    lauf.pruefe(False, f"{wo}: Abschnitt {i} hat keine Dauer")
                    continue
                tempo = strecke / (minuten / 60.0)
                if zu_fuss:
                    merke(["fussweg-zustieg"], wo)
                lauf.pruefe(tempo <= grenze,
                            f"{wo}: Abschnitt {i}"
                            + (" (zu Fuß)" if zu_fuss else "")
                            + f" verlangt {tempo:.1f} km/h "
                            f"({strecke:.1f} km in {minuten:.0f} min), Grenze {grenze:.0f}")
            vorheriger = e

            f = e["felder"]
            # Die Notiz des Einsatzes liegt seit S9/AP7 unter `geschuetzt`;
            # die Abdeckung braucht deshalb beide Bloecke.
            gs = e["geschuetzt"]
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
                if f["secondary"] and dn["art"] == "ground":
                    merke(["sekundaer-boden"], wo)
                if f["start_src"]:
                    merke([{"base": "start-base", "prev_site": "start-prevsite",
                            "prev_dest": "start-prevdest", "manual": "start-manual"}[f["start_src"]]], wo)
                else:
                    merke(["start-leer"], wo)
                if f["crew_override"]:
                    merke(["besatzung-abweichend"], wo)
                if f["other_ema"]:
                    merke(["weiterer-notarzt"], wo)
                # SEIT S9/AP7 unter `geschuetzt`: Die Notiz des Einsatzes liegt
                # im verschluesselten pat_blob. `f["notes"]` liefe hier in
                # einen KeyError — und zwar sofort, was besser ist als ein
                # `.get()`, das die Abdeckung still verloere.
                if (gs or {}).get("notes"):
                    merke(["notizen-einsatz"], wo)
                if len(f["other_resources"]) > 1:
                    merke(["weitere-rettungsmittel-mehrere"], wo)
                elif f["other_resources"]:
                    merke(["weitere-rettungsmittel"], wo)
                if f["transport_dest"]:
                    merke(["zielklinik-koordinate" if f["dest_lat"] is not None
                           else "zielklinik-ohne-koordinate"], wo)
                    # OHNE STANDORT GIBT ES KEINE VORSCHLAGSLISTE (E-DA-08).
                    # Das Ziel ist dann Freitext — `nachtragen` schickt es mit
                    # `f_transport_dest` samt Koordinatenpaar, und die Marke
                    # kommt aus dem INHALT und nicht aus einer Behauptung.
                    if ohne_standort:
                        merke(["ziel-adhoc"], wo)
                        lauf.pruefe(f["dest_lat"] is not None,
                                    f"{wo}: Transportziel ad hoc ohne Koordinate — dann "
                                    f"bliebe die Karte leer, und der Fall verlöre seinen "
                                    f"Gegenstand")
                    else:
                        lauf.pruefe((dn["standort"], f["transport_dest"]) in kliniken,
                                    f"{wo}: Zielklinik {f['transport_dest']!r} fehlt als Vorbelegung am Standort")

                # Faehigkeiten: Winde und Bergwacht nur, wo das Rettungsmittel
                # sie hat -- UNABHAENGIG von der Betriebsart. Die Frage, ob es
                # sie haben DARF, steht weiter unten bei den Stammdaten; hier
                # zaehlt nur, ob der Diensttag sie eingefroren hat. Genau so
                # entscheidet die Anwendung (`cap_gate` ueber
                # `day_capabilities`, kein `kind_gate`), und deshalb zeigt ein
                # bodengebundener Bergwacht-Dienst seine Windenfelder.
                for haken, faehigkeit in (("winch", "winch"), ("bergwacht", "bergwacht")):
                    lauf.pruefe(not f[haken] or faehigkeit in rm.get("faehigkeiten", []),
                                f"{wo}: {haken}=1, aber {dn['rettungsmittel']!r} hat die Fähigkeit {faehigkeit!r} nicht")
                if f["winch"] and rm.get("art") == "ground":
                    merke(["winde-boden-bergwacht"], wo)
                if f["winch"] and f["winch_cycles"]:
                    merke(["winde-cycles"], wo)
                if f["winch"] and f["winch_cycles_pat"]:
                    merke(["winde-cycles-patient"], wo)
                if f["bergwacht"]:
                    lauf.pruefe(ohne_standort or not f["bw_unit"]
                                or (dn["standort"], f["bw_unit"]) in bereitsch,
                                f"{wo}: Bereitschaft {f['bw_unit']!r} fehlt als Vorbelegung am Standort")
                    if f["bw_unit"] and f["bw_info"]:
                        merke(["bergwacht-info"], wo)
                # WEITERE RETTUNGSMITTEL: Vorbelegung des eigenen Standorts --
                # es sei denn, der Einsatz ist ausdruecklich als Freitextfall
                # gekennzeichnet. Das Feld ist Freitext mit Vorschlagsliste
                # (mission_fields.php); ein Wert ausserhalb der Liste ist
                # gueltig und muss vorkommen, sonst prueft der Datensatz nur
                # den bequemen Teil des Feldes.
                freitext_erlaubt = ohne_standort or "stammdaten-freitext" in e["abdeckung"]
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
                    lauf.pruefe(not ohne_standort
                                and standort.get(dn["standort"], {}).get("lat") is not None,
                                f"{wo}: start_src='base' ohne Standort mit Koordinaten")

            # OHNE TRACK, ABER MIT BEIDEN KOORDINATEN — die gestrichelte
            # Luftlinie der Karte (E-DA-08). Aus dem INHALT abgeleitet: kein
            # `route`, ein Einsatzort mit Koordinate und ein Ziel mit
            # Koordinate. Fehlt eines davon, zeichnet die Karte nichts, und
            # die Zeile waere unbelegt — auch wenn eine Marke es behauptete.
            if not e.get("route") and wegpunkte.ort_von(e) and wegpunkte.ziel_von(e):
                merke(["luftlinie-ort-ziel"], wo)

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
                for feld in ((g or {}).get("notes"), (f or {}).get("bw_info"), (f or {}).get("other_ema")):
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

    # ---- Zwei Dienste an einem Kalendertag: der zweite am Abend (E-DA-04) --
    #
    # OHNE UEBERSCHNEIDUNG. R57 meldet ueberlappende Dienste, und sie hat
    # recht: Niemand faehrt zwei Dienste gleichzeitig. Der Fall, den der
    # Bestand belegen soll, ist der ANDERE — Notarztdienst tagsueber, danach
    # der Sanitaetsdienst auf der Veranstaltung. Beides derselbe Kalendertag,
    # beides derselbe Mensch, und nichts davon gleichzeitig.
    nach_datum: dict[str, list[dict]] = {}
    for d in dienste:
        nach_datum.setdefault(d["dienst"]["day"], []).append(d)
    for tag, gruppe in nach_datum.items():
        if len(gruppe) < 2:
            continue
        sortiert = sorted(gruppe, key=lambda d: d["dienst"]["beginn"])
        for d in sortiert:
            merke(["zwei-dienste-ein-tag"], d["kennung"])
        for vorher, danach in zip(sortiert, sortiert[1:]):
            lauf.pruefe(lokal(vorher["dienst"]["ende"]) <= lokal(danach["dienst"]["beginn"]),
                        f"{danach['kennung']}: überschneidet sich mit "
                        f"{vorher['kennung']} am {tag} (R57)")
            if lokal(danach["dienst"]["beginn"]).hour >= 17:
                merke(["abenddienst"], danach["kennung"])

    # ---- Import nur an Kalendertagen mit genau EINEM Dienst (B-04) --------
    for pfad, d in zip(dienstdateien, dienste):
        mehrere = len(dienste_je_datum[d["dienst"]["day"]]) > 1
        for e in d["einsaetze"]:
            lauf.pruefe(not (mehrere and e["kanal"] == "import"),
                        f"{d['kennung']}: Import-Einsatz an einem Datum mit mehreren Diensten "
                        f"— der Import löst nur über das Datum auf (B-04)")

    # ---- Sperrwoerter in den Geraetebeschriftungen (R64/AP4) ---------------
    #
    # WARUM HIER UND NICHT IN tools/wortliste/. Die Beschriftungen der zwei
    # Referenzgeraete werden ueber `server/demo/fixture.json.gz` zu SICHTBAREM
    # TEXT des Demo-Kontos -- auf dem Produktivserver, alle 30 Minuten neu.
    # Die Wortliste kennt fuenf Bereiche (server/*.php, assets/*.js,
    # normative Dokumentation, Android, watch/); `tools/` ist in keinem davon,
    # und das war kein Versehen: Dort steht Werkzeug, kein Client. Diese zwei
    # Zeichenketten sind aber weder Werkzeug noch Client, sondern Daten, die
    # als Text herauskommen -- und bis R64/AP4 hiess das eine davon
    # „Uhr Luftrettung (Referenz)", mit einem Sperrwort darin, das nie jemand
    # gemessen hat.
    #
    # DIE LISTE WIRD GELESEN, NICHT KOPIERT. Eine zweite Liste ginge beim
    # naechsten Eintrag auseinander, und dann prueft diese Stelle gegen einen
    # Stand, den es nicht mehr gibt.
    sperr = HIER.parent.parent / "wortliste" / "sperrliste.json"
    if sperr.exists():
        muster = json.loads(sperr.read_text("utf-8"))["muster"]
        for g in geraete:
            for m in muster:
                if re.search(m["regex"], g["beschriftung"], re.IGNORECASE):
                    lauf.befunde.append(
                        f"geraete.json/{g['nummer']}: die Beschriftung "
                        f"{g['beschriftung']!r} enthaelt das Sperrwort "
                        f"{m['id']!r} (tools/wortliste/sperrliste.json). Sie wird "
                        f"ueber die Fixture zu sichtbarem Text des Demo-Kontos. "
                        f"Ersatz: {m['ersatz']}")
                lauf.pruefungen += 1

    # ---- Geraete (R64/AP4) ------------------------------------------------
    nummern = sorted(g["nummer"] for g in geraete)
    lauf.pruefe(nummern == ["11", "12"],
                f"geraete.json: erwartet werden die Nummern 11 und 12, da stehen {nummern}")
    for g in geraete:
        wo, b = f"geraete.json/{g['nummer']}", g["block"]
        lauf.pruefe(b["art"] in ("uhr", "handy", "sonstiges"),
                    f"{wo}: art {b['art']!r} ist keine der drei des Vertrags")
        if b["art"] == "uhr":
            # DIE TEILENUMMER MUSS ES GEBEN UND SIE MUSS ECHT SEIN. Eine
            # erfundene liefe in den Rueckfall `geraet_teil`; das Demo-Konto
            # zeigte dann eine Nummer statt eines Modells -- also gerade
            # nicht das, was R64 belegen soll.
            lauf.pruefe(bool(b.get("teil")), f"{wo}: eine Uhr braucht eine Teilenummer")
            lauf.pruefe(not MODELLE or b.get("teil") in MODELLE,
                        f"{wo}: Teilenummer {b.get('teil')!r} steht nicht in "
                        f"server/geraetemodelle.php ({len(MODELLE)} bekannte)")
            lauf.pruefe("ciq" in b, f"{wo}: eine Uhr sendet ciq (JSON-Vertrag 1a.4)")
        else:
            lauf.pruefe(b.get("teil") is None, f"{wo}: ein Handy hat keine Teilenummer")
            lauf.pruefe(bool(b.get("hersteller")) and bool(b.get("modell")),
                        f"{wo}: ein Handy sendet hersteller und modell")
            lauf.pruefe("ciq" not in b,
                        f"{wo}: ciq entfaellt beim Handy und wird nicht auf null gesetzt")
            lauf.pruefe("sdk" in b, f"{wo}: ein Handy sendet sdk")
        for feld in ("br", "ho", "touch", "fw", "app"):
            lauf.pruefe(feld in b, f"{wo}: Feld {feld} fehlt im Geraeteblock")
        # DIE PRAEFIXE SIND DIE HERKUNFT. Steht hier etwas anderes als in den
        # Quelldaten, leitet der Server eine andere Herkunft ab, als dieses
        # Dokument behauptet -- und niemand merkt es.
        soll = ({"einsatz": "m-", "ruhe": "r-", "dienst": "d-"} if b["art"] == "uhr"
                else {"einsatz": "am-", "ruhe": "ar-", "dienst": "ad-"})
        lauf.pruefe(g["praefix"] == soll,
                    f"{wo}: Praefixe {g['praefix']} passen nicht zur Art {b['art']!r} "
                    f"(erwartet {soll})")
        erlaubt = tuple(soll.values()) + (("wm-",) if b["art"] == "handy" else ())
        for kennung, wo2 in refs.items():
            teile = kennung.split("-")
            if len(teile) < 3 or teile[1] != g["nummer"]:
                continue
            lauf.pruefe(kennung.startswith(erlaubt),
                        f"{wo2}: Kennung {kennung} passt nicht zu Geraet {g['nummer']} "
                        f"({b['art']}, erlaubt: {', '.join(erlaubt)})")

    # ---- Schnitte (R64/AP4, E-R64-16) -------------------------------------
    schnittzahl = 0
    for d in dienste:
        n = d["kennung"]
        segmente = {r["client_ref"]: r for r in d["ruhesegmente"]}
        startminuten = [e["beginn"][11:16] for e in d["einsaetze"]]
        for sc in d.get("schnitte", []):
            schnittzahl += 1
            wo = f"{n}/schnitt {sc['segment']}"
            merke(sc["abdeckung"], wo)
            seg = segmente.get(sc["segment"])
            lauf.pruefe(seg is not None,
                        f"{wo}: das Quellsegment steht nicht in diesem Dienst")
            if seg is None:
                continue
            sb, se = lokal(sc["beginn"]), lokal(sc["ende"])
            lauf.pruefe(sb < se, f"{wo}: Ende liegt nicht nach dem Beginn")
            # INNERHALB DER SPUR DES SEGMENTS. Liegt das Fenster daneben,
            # wandert kein Punkt; api/schneiden.php antwortet 409 `leer` und
            # der Einspiellauf braeche ab -- mit einer Ursache, die nirgends
            # steht.
            lauf.pruefe(lokal(seg["beginn"]) <= sb,
                        f"{wo}: Beginn liegt vor dem Segment ({seg['beginn']})")
            lauf.pruefe(seg["ende"] is not None and se <= lokal(seg["ende"]),
                        f"{wo}: Ende liegt hinter dem Segment ({seg['ende']})")
            # NICHT UEBER MITTERNACHT: api/schneiden.php rechnet die Phasen
            # mit dem Tagesversatz des BEGINNS und verwirft still, was
            # danach liegt.
            lauf.pruefe(sc["beginn"][:10] == sc["ende"][:10],
                        f"{wo}: der Schnitt laeuft ueber Mitternacht")
            for nr, wann in sorted(sc["phasen"].items()):
                lauf.pruefe(sb <= lokal(wann) <= se,
                            f"{wo}: Phase {nr} ({wann}) liegt ausserhalb von Beginn und Ende")
                lauf.pruefe(wann[:10] == sc["beginn"][:10],
                            f"{wo}: Phase {nr} liegt an einem anderen Kalendertag")
            # DIE BEGINNMINUTE MUSS EINMALIG SEIN: Die Stufen `nachtragen`,
            # `papierkorb` und `sperrliste` suchen Einsaetze ueber start_hhmm.
            lauf.pruefe(sc["beginn"][11:16] not in startminuten,
                        f"{wo}: Beginnminute {sc['beginn'][11:16]} kommt an diesem "
                        f"Diensttag schon als Einsatzbeginn vor")
            # NICHT AM NEUESTEN DIENSTTAG: index.php oeffnet ihn von selbst,
            # und sichtpruefung.mjs wie vier Bilder des Bilderlaufs greifen
            # auf seine erste Einsatzzeile -- ein geschnittener Einsatz hat
            # aber keine entschluesselbare Diagnose.
            lauf.pruefe(d["dienst"]["day"] != neuester_tag,
                        f"{wo}: der Schnitt liegt am neuesten Diensttag ({neuester_tag})")
    # WIE VIELE SCHNITTE — die Zahl steht nicht mehr fest (E-DA-11).
    #
    # E-R64-16 verlangte GENAU EINEN, und die Zeile hiess `schnittzahl == 1`.
    # Sie prueft seither die Summe gegen die Liste: So viele, wie in den
    # Quelldaten stehen, und mindestens einer. Der Sinn der alten Zeile bleibt
    # damit erhalten — sie sollte verhindern, dass der Bestand den Schnitt
    # STILL verliert (Backlog Nr. 63: Der Sperrvermerk muss die Sicherung
    # ueberstehen, und ohne einen Schnitt belegt ihn niemand). Was sie
    # zusaetzlich verhinderte, war ein zweiter Schnitt, und dafuer gab es nie
    # einen Grund.
    auftraege = sum(len(d.get("schnitte", [])) for d in dienste)
    lauf.pruefe(schnittzahl == auftraege and schnittzahl >= 1,
                f"erwartet wird mindestens ein Schnitt, und so viele wie Auftraege "
                f"in den Quelldaten stehen (E-DA-11): {auftraege} Auftraege, "
                f"{schnittzahl} gezaehlt")

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
        "beide Geräte mit Block (Momentaufnahme möglich)": (
            len(geraete) == 2 and all(g.get("block") for g in geraete),
            f"Bloecke: {[bool(g.get('block')) for g in geraete]}"),
        "eine Uhr und ein Handy": (
            sorted(g["block"]["art"] for g in geraete) == ["handy", "uhr"],
            f"Arten: {sorted(g['block']['art'] for g in geraete)}"),
        "Schnitte an mehr als einem Diensttag": (
            sum(1 for d in dienste if d.get("schnitte")) >= 2,
            f"Dienste mit Schnitt: {[d['kennung'] for d in dienste if d.get('schnitte')]}"),
        "alle Rollen des Katalogs belegt": (rollen_belegt == CREW_ROLES,
                                            f"belegt: {sorted(rollen_belegt)}"),
        "alle Phasen 2–9 im Datensatz": (alle_phasen == PHASEN,
                                         f"vorhanden: {sorted(alle_phasen)}"),
        "alle speicherbaren Ereignisarten (neun)": (alle_rea_typen == REA_TYPEN,
                                    f"fehlen: {sorted(REA_TYPEN - alle_rea_typen)}"),
        # FRUEHER: „≥ 2 Standorte, einer ohne Koordinaten". Diese Zeile ist mit
        # dem Demo-Ausbau umformuliert (F-DA-1, Weg a). Der Auftrag gibt allen
        # Standorten Koordinaten (E-DA-09) — die alte Zeile pruefte danach
        # einen Zustand, den es nicht mehr gibt, und mit ihr den einzigen
        # lebenden Fall fuer `spur_ausgangspunkt`. Den gibt es weiterhin, nur
        # an anderer Stelle: am DIENSTTAG OHNE STANDORT, den die Anwendung
        # seit Web 16.0.0 kennt. Die Zeile prueft jetzt ihn.
        "≥ 3 Standorte, alle mit Koordinaten": (
            len(stammdaten["standorte"]) >= 3
            and all(s["lat"] is not None and s["lon"] is not None
                    for s in stammdaten["standorte"]), ""),
        "≥ 1 Diensttag ohne Standort mit `spur_ausgangspunkt`": (
            any(d["dienst"]["standort"] is None and "spur_ausgangspunkt" in d["dienst"]
                for d in dienste), ""),
        "≥ 2 Luft-Rettungsmittel mit/ohne Fähigkeiten": (
            sum(1 for r in stammdaten["rettungsmittel"] if r["art"] == "air") >= 2
            and any(r["faehigkeiten"] for r in stammdaten["rettungsmittel"])
            and any(not r["faehigkeiten"] and r["art"] == "air" for r in stammdaten["rettungsmittel"]), ""),
        "≥ 1 Boden-Rettungsmittel": (
            any(r["art"] == "ground" for r in stammdaten["rettungsmittel"]), ""),
        # SEIT WEB 20.3.0 ZULAESSIG (E-DA-06) und damit pruefbar: Faehigkeiten
        # an einem BODENgebundenen Rettungsmittel — aber nur beim Typ
        # Bergwacht. Die Zeile prueft beide Haelften: dass es den Fall gibt,
        # und dass er auf diesen einen Typ beschraenkt bleibt.
        "Fähigkeiten am bodengebundenen Bergwacht-Rettungsmittel": (
            any(r["art"] == "ground" and r.get("typ") == "bergwacht" and r["faehigkeiten"]
                for r in stammdaten["rettungsmittel"])
            and not any(r["art"] == "ground" and r.get("typ", "standard") != "bergwacht"
                        and r["faehigkeiten"] for r in stammdaten["rettungsmittel"]), ""),
        "Rettungsmittel mit und ohne Standort": (
            sum(1 for r in stammdaten["rettungsmittel"] if r.get("ohne_standort")) >= 2
            and sum(1 for r in stammdaten["rettungsmittel"] if not r.get("ohne_standort")) >= 2, ""),
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
    # Umfang: 21 Diensttage, im Schnitt rund fünf Einsätze je Dienst (Nachtrag
    # B1 zur Abdeckungsmatrix — die ursprünglichen 30–40 stammten aus einem
    # Entwurf mit deutlich weniger Bodendiensten; das Fenster 80–100 aus dem
    # Stand vor dem Demo-Ausbau).
    #
    # WOZU DIESE ZEILE ÜBERHAUPT. Sie prüft keine Regel der Anwendung, sondern
    # fängt das Versehen: einen Lauf von `aufbauen.py`, der die Hälfte der
    # Dienste nicht gefüllt hat, oder eine Quelldatei, die niemand mehr lädt.
    # Beides sieht in jeder Einzelprüfung in Ordnung aus. Das Fenster ist
    # deshalb weit und die Zahl daneben genau.
    lauf.pruefe(95 <= einsatzzahl <= 125,
                f"Umfang {einsatzzahl} Einsätze liegt außerhalb von 95–125")

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
