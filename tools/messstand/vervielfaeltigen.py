"""Aus einem Referenz-Backup einen Großbestand bauen (E-S2-23, R35).

WOFUER. S2 verspricht, dass 5000 Einsätze in einem Konto tragen (Z1). Diese
Zusage lässt sich nur an 5000 Einsätzen prüfen, und die muss jemand herstellen
können — reproduzierbar, ohne Handarbeit und ohne an der Prüfschicht vorbei.

DER WEG. Das Referenz-Backup wird geöffnet, ihre Nutzlast **r-mal** mit
versetzten Zeiten und eigenen Kennungen vervielfältigt und als Folge
einteiliger `.edbak`-Dateien wieder versiegelt. Eingespielt werden sie über
den **regulären** Wiederherstellungsweg im Browser (`einspielen.mjs`) — kein
SQL, kein Sonderendpunkt (Geist von R4). Was dabei entsteht, ist damit ein
Bestand, den die Anwendung selbst so angelegt hätte.

WARUM NICHT EINFACH SQL. Weil ein per SQL erzeugter Bestand die Frage nicht
beantwortet, die der Messstand stellt. Er misst nicht nur „wie schnell ist die
Suche bei 5000 Einsätzen", sondern auch „kommen 5000 Einsätze überhaupt durch
den Einspielweg" — und die zweite Antwort ist die interessantere (B-S2-03).

WIE DER VERSATZ GEWAEHLT IST. Jede Runde verschiebt **alle** Zeitangaben um
`-runde × abstand` Tage und zusätzlich um `+runde` Minuten. Beides zusammen
ist nötig:

  * Die Tage sorgen dafür, dass der Bestand in die Vergangenheit wächst. Das
    ist kein Schönheitsgrund: Die Ausdünnung (E-S2-03) greift sechs Monate
    nach Einsatzende, und ein Bestand, der nur in der Zukunft liegt, ließe
    AP3 nichts zu tun.
  * Die Minuten sorgen dafür, dass zwei Runden einander nicht auffressen. Die
    Wiedererkennung des Einspielwegs kennt zwei Schritte (`backup_lib.php`):
    Schritt 1 sucht über die Einsatzkennungen — die sind hier je Runde eigen,
    also findet er nichts. Schritt 2 ist ein Fingerabdruck aus **Datum,
    Beginn, Ende, Art, Rettungsmittel und Station**. Landeten zwei Runden auf
    demselben Datum und derselben Uhrzeit, verschmölzen ihre Diensttage zu
    einem — aus 5000 Einsätzen würden stillschweigend weniger. Eine Minute
    Unterschied schließt das aus, ohne die Zeiten unkenntlich zu machen.

Die Referenz spannt vom 17.01. bis zum 27.12.2026, also 345 Tage. Der Abstand
zwischen zwei Runden ist mit drei Tagen bewusst KLEINER: Die Runden sollen
sich überlagern, sonst reichte der Bestand über Jahrzehnte und die
Zeitstempel liefen aus dem Wertebereich von `track_points.ts`
(`INT UNSIGNED`, Unix-Epoche).

WAS NICHT VERVIELFAELTIGT WIRD: die Stammdaten (Standorte, Rettungsmittel,
Besatzungen, Zielkliniken) und die Kontoangaben. Sie stehen in jeder Datei
gleich; der Einspielweg erkennt sie wieder und legt sie genau einmal an. Das
ist gewollt — ein Konto mit 5000 Einsätzen hat nicht 5000 Standorte.

UND DER PAPIERKORB BLEIBT DRAUSSEN. Das war nicht der erste Entwurf, und der
Grund ist eine gemessene Überraschung: Der erste Lauf meldete 5046 Einsätze
und legte 4744 an — 302 fehlten, ab Runde 26 dreizehn je Runde.

Die Ursache ist die Regel D1 des Einspielwegs, und sie ist richtig so: Ein in
der Datei AKTIVER Diensttag, dessen Datum im Zielkonto einen Tag im Papierkorb
trifft, wird übersprungen ("Ablehnen statt Zurückholen", `backup_lib.php`).
Die Referenz trägt einen Diensttag im Papierkorb; vervielfältigt trägt ihn
jede Runde. Bei drei Tagen Versatz landen spätere Runden mit ihren aktiven
Tagen zwangsläufig auf den Papierkorbdaten früherer — und verlieren sie
mitsamt ihren Einsätzen.

Der Papierkorb ist für einen MENGENPRUEFSTAND ohnehin nicht die Frage: Was
er beim Sichern und Zurückspielen bedeutet, prüfen der Kreislauf (R24) und
`tools/wiederherstellungs-probe/` (R27), und die tun es gründlicher, als ein
58-fach kopierter Papierkorb es je könnte. Hier gilt deshalb: gelöschte Tage,
Einsätze und Ruhesegmente werden NICHT mitvervielfältigt. Damit trägt jede
Runde genau 82 Einsätze, 95 Ruhesegmente und 15 Diensttage — eine Zahl, die
sich vorher ausrechnen und hinterher nachzählen lässt. Genau das tut
`einspielen.mjs`.

AUFRUF

    python3 vervielfaeltigen.py --einsaetze 5000 --ziel /tmp/messstand
    python3 vervielfaeltigen.py --runden-je-datei 2      # kleinere Pakete

Wie viele Einsätze in eine Datei passen, entscheidet nicht der Geschmack,
sondern `post_max_size` des Zielservers: Die Nutzlast wiegt rund **28 KB je
Einsatz** (gemessen: 2,42 MB für 87 Einsätze mit 55 861 Spurpunkten). Bei den
verbreiteten 8 MB sind das rund 280 Einsätze je Datei — die im Konzept
genannten 400–500 gehen sich dort NICHT aus. Der Vorgabewert richtet sich
danach; `--runden-je-datei` hebt ihn an, wenn der Server mehr zulässt.
"""
from __future__ import annotations

import argparse
import datetime as dt
import json
import pathlib
import re
import sys

HIER = pathlib.Path(__file__).resolve().parent
sys.path.insert(0, str(HIER))
from edbak import lesen_edbak, schreiben_edbak, rundlauf_pruefen  # noqa: E402

# Zeitangaben der Nutzlast. AUSDRUECKLICH AUFGEZAEHLT und nicht geraten:
# Ein Werkzeug, das jede Zeichenkette verschiebt, die nach einem Datum
# aussieht, verschiebt irgendwann auch ein Geburtsdatum (`pat.dob`) oder
# einen Freitext — und das fiele niemandem auf.
ZEITFELDER_TAG = ("started_at", "ended_at", "deleted_at")
ZEITFELDER_EINSATZ = ("started_at", "ended_at", "created_at", "deleted_at")
ZEITFELDER_RUHE = ("started_at", "ended_at", "deleted_at")

_DT = "%Y-%m-%d %H:%M:%S"
_TAG = "%Y-%m-%d"


def _verschieben(wert, delta: dt.timedelta):
    """Eine Zeitangabe verschieben. `None` bleibt `None`."""
    if wert is None or wert == "":
        return wert
    text = str(wert)
    for form in (_DT, _TAG):
        try:
            neu = dt.datetime.strptime(text, form) + delta
            return neu.strftime(form)
        except ValueError:
            continue
    raise ValueError(f"Unerwartete Zeitangabe: {text!r}")


def _kennung(alt: str, runde: int) -> str:
    """Kennung je Runde eindeutig machen, ohne ihre Form zu verlieren.

    Die Kennungen der Uhr haben die Form `m-<geraet>-<zahl>` bzw.
    `r-…`, `d-…` (JSON-Vertrag). Der Rundenzusatz hängt sich hinten an, damit
    Präfix und Gerätenummer lesbar bleiben — wer im Bestand sucht, soll noch
    erkennen, woher ein Datensatz stammt. `validate_lib` prüft die Länge
    (64 Zeichen), nicht die Form.
    """
    return f"{alt}-v{runde:03d}"


def _aktiv(eintrag: dict) -> bool:
    """Steht der Eintrag im Papierkorb? Siehe Kopfkommentar, Abschnitt
    „UND DER PAPIERKORB BLEIBT DRAUSSEN"."""
    return (eintrag.get("deleted_at") or None) is None


def runde_bauen(quelle: dict, runde: int, abstand_tage: int) -> dict:
    """Eine verschobene Kopie von Tagen, Einsätzen und Ruhesegmenten.

    Gelöschte Einträge bleiben draußen; welche Tagesnummern damit wegfallen,
    merkt sich `entfallene_tage`, damit auch die daran hängenden Einsätze und
    Ruhesegmente gehen — ein Einsatz ohne Diensttag wäre verwaist, und der
    Einspielweg würde ihn ohnehin ablehnen.
    """
    delta = dt.timedelta(days=-runde * abstand_tage, minutes=runde)
    tage, einsaetze, ruhe = [], [], []
    entfallene_tage = {int(t["id"]) for t in quelle["days"] if not _aktiv(t)}

    for t in quelle["days"]:
        if not _aktiv(t):
            continue
        n = json.loads(json.dumps(t))          # tiefe Kopie, ohne copy-Import
        n["day"] = _verschieben(n["day"], delta)
        for f in ZEITFELDER_TAG:
            if f in n:
                n[f] = _verschieben(n[f], delta)
        # Die Dienstkennungen der Uhr müssen je Runde eigen sein, sonst
        # ordnet der Einspielweg die Runde einem fremden Tag zu.
        for ref in (n.get("refs") or []):
            if ref.get("day_ref"):
                ref["day_ref"] = _kennung(ref["day_ref"], runde)
        # Die Tagesnummer ist der Anker, über den die Einsätze der Datei auf
        # ihren Tag zeigen. Sie muss innerhalb der DATEI eindeutig sein.
        n["id"] = runde * 100000 + int(n["id"])
        tage.append(n)

    for m in quelle["missions"]:
        if not _aktiv(m) or int(m.get("day_id") or 0) in entfallene_tage:
            continue
        n = json.loads(json.dumps(m))
        n["client_ref"] = _kennung(n["client_ref"], runde)
        n["day_id"] = runde * 100000 + int(n["day_id"])
        for f in ZEITFELDER_EINSATZ:
            if f in n:
                n[f] = _verschieben(n[f], delta)
        for p in (n.get("phases") or []):
            p["occurred_at"] = _verschieben(p.get("occurred_at"), delta)
        for rea in (n.get("resus") or []):
            rea["started_at"] = _verschieben(rea.get("started_at"), delta)
            for e in (rea.get("events") or []):
                e["occurred_at"] = _verschieben(e.get("occurred_at"), delta)
        # Spurpunkte: [seq, lat, lon, ele, ts] — nur der Zeitstempel wandert.
        # Die Koordinaten bleiben, wo sie sind: Ein Bestand, der über die
        # halbe Alpenkette verstreut wäre, misst nichts Besseres, und die
        # Ausduennung rechnet dann an einer Geometrie, die es nicht gibt.
        sek = int(delta.total_seconds())
        for pt in (n.get("track") or []):
            pt[4] = int(pt[4]) + sek
        einsaetze.append(n)

    for r in quelle["rest_segments"]:
        if not _aktiv(r) or int(r.get("day_id") or 0) in entfallene_tage:
            continue
        n = json.loads(json.dumps(r))
        n["client_ref"] = _kennung(n["client_ref"], runde)
        n["day_id"] = runde * 100000 + int(n["day_id"])
        for f in ZEITFELDER_RUHE:
            if f in n:
                n[f] = _verschieben(n[f], delta)
        sek = int(delta.total_seconds())
        for pt in (n.get("track") or []):
            pt[4] = int(pt[4]) + sek
        ruhe.append(n)

    return {"days": tage, "missions": einsaetze, "rest_segments": ruhe}


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--quelle", default=None,
                   help="Referenz-.edbak (Vorgabe: die eine in referenzdatensatz/referenz/)")
    p.add_argument("--passwort", default="nadokudemo0815")
    p.add_argument("--einsaetze", type=int, default=5000,
                   help="Zielzahl der Einsätze (Vorgabe 5000, Zielmaß Z1)")
    p.add_argument("--runden-je-datei", type=int, default=3,
                   help="Runden je .edbak. 3 Runden ≈ 261 Einsätze ≈ 7,3 MB "
                        "Nutzlast — knapp unter den verbreiteten 8 MB "
                        "post_max_size.")
    p.add_argument("--abstand-tage", type=int, default=3,
                   help="Um wie viele Tage jede Runde nach hinten rückt")
    p.add_argument("--ziel", default="/tmp/messstand/bestand")
    a = p.parse_args()

    quelle = a.quelle
    if quelle is None:
        ordner = HIER.parents[0] / "referenzdatensatz" / "referenz"
        treffer = sorted(ordner.glob("*.edbak"))
        if len(treffer) != 1:
            print(f"In {ordner} liegen {len(treffer)} .edbak-Dateien — "
                  "erwartet wird genau eine. Pfad mit --quelle angeben.",
                  file=sys.stderr)
            return 2
        quelle = str(treffer[0])

    print(f"Quelle: {quelle}")
    ref = lesen_edbak(quelle, a.passwort)
    ref.pop("$container", None)

    # Die Rundenzahl rechnet mit dem, was eine Runde WIRKLICH trägt.
    # Der erste Entwurf nahm `len(ref["missions"])` = 87 — die Zahl VOR dem
    # Aussortieren des Papierkorbs. Bestellt waren 5000 Einsätze, geliefert
    # wurden 4756: 58 Runden zu 82 statt zu 87. Eine Zielzahl, die um 5 %
    # danebenliegt, ist keine Zielzahl.
    probe = runde_bauen(ref, 0, a.abstand_tage)
    je_runde = len(probe["missions"])
    punkte_je_runde = (sum(len(m.get("track") or []) for m in probe["missions"])
                       + sum(len(r.get("track") or []) for r in probe["rest_segments"]))
    runden = -(-a.einsaetze // je_runde)          # aufrunden
    print(f"  {je_runde} Einsätze, {len(probe['rest_segments'])} Ruhesegmente, "
          f"{punkte_je_runde} Spurpunkte je Runde (Papierkorb aussortiert)")
    print(f"  {runden} Runden für {a.einsaetze} Einsätze "
          f"→ {runden * je_runde} Einsätze, {runden * punkte_je_runde} Spurpunkte")

    ziel = pathlib.Path(a.ziel)
    ziel.mkdir(parents=True, exist_ok=True)
    for alt in ziel.glob("bestand-*.edbak"):
        alt.unlink()

    verzeichnis = []
    datei_nr = 0
    for anfang in range(0, runden, a.runden_je_datei):
        gruppe = range(anfang, min(anfang + a.runden_je_datei, runden))
        nutzlast = {k: v for k, v in ref.items()
                    if k not in ("days", "missions", "rest_segments")}
        nutzlast["days"], nutzlast["missions"], nutzlast["rest_segments"] = [], [], []

        # DIE FASSUNG WIRD GESETZT, NICHT GEERBT (S2/AP6, F-S2-E).
        #
        # Bis hierher wurde `version` aus der Referenz uebernommen. Das ging
        # gut, solange die Referenz Nutzlast 7 war. Seit Web 11.0.0 ist sie
        # Fassung 4 mit Nutzlast **8** — und Nutzlast 8 sagt zu, dass die
        # Punkte NICHT in den Eintraegen stehen, sondern in eigenen Teilen.
        #
        # Diese Datei hier ist aber einteilig und traegt ihre Punkte als
        # `track` in den Eintraegen. Mit `version: 8` nimmt der Einspielweg
        # deshalb den Verweisweg, findet keine `spur_ref` — und legt jeden
        # Einsatz OHNE Spur an. Gemessen am ersten Lauf danach: 164 Einsaetze
        # angelegt, 91 208 Punkte verloren, Meldung „fertig".
        #
        # Die Datei IST Nutzlast 7; sie wird jetzt auch so ausgezeichnet.
        # Die Zusatzfelder `stufe`, `n` und `n_original` bleiben stehen: Sie
        # stoeren nicht, und der Vergleich fuehrt sie ohnehin in seiner
        # Ausnahmeliste (edbak-alt_umlauf.json).
        #
        # Mit NaDoku 1.0 faellt das Altformat weg; dann braucht dieses
        # Werkzeug einen Container-Schreiber in Python (Backlog Nr. 46).
        nutzlast["version"] = 7
        for r in gruppe:
            teil = runde_bauen(ref, r, a.abstand_tage)
            nutzlast["days"] += teil["days"]
            nutzlast["missions"] += teil["missions"]
            nutzlast["rest_segments"] += teil["rest_segments"]

        datei_nr += 1
        name = f"bestand-{datei_nr:03d}.edbak"
        rundlauf_pruefen(nutzlast, a.passwort)     # erst prüfen, dann ablegen
        roh = schreiben_edbak(nutzlast, a.passwort)
        (ziel / name).write_bytes(roh)
        eintrag = {"datei": name, "runden": list(gruppe),
                   "einsaetze": len(nutzlast["missions"]),
                   "ruhesegmente": len(nutzlast["rest_segments"]),
                   "diensttage": len(nutzlast["days"]),
                   "spurpunkte": sum(len(m.get("track") or [])
                                     for m in nutzlast["missions"])
                              + sum(len(r.get("track") or [])
                                    for r in nutzlast["rest_segments"]),
                   "dateigroesse": len(roh)}
        verzeichnis.append(eintrag)
        print(f"  {name}: {eintrag['einsaetze']} Einsätze, "
              f"{eintrag['spurpunkte']} Punkte, {len(roh)/1024/1024:.2f} MB")

    (ziel / "verzeichnis.json").write_text(
        json.dumps({"quelle": quelle, "passwort_hinweis": "wie die Referenz",
                    "abstand_tage": a.abstand_tage,
                    "einsaetze_gesamt": sum(v["einsaetze"] for v in verzeichnis),
                    "spurpunkte_gesamt": sum(v["spurpunkte"] for v in verzeichnis),
                    "dateien": verzeichnis}, ensure_ascii=False, indent=2) + "\n",
        "utf-8")
    print(f"\n{len(verzeichnis)} Dateien in {ziel}, "
          f"{sum(v['einsaetze'] for v in verzeichnis)} Einsätze, "
          f"{sum(v['spurpunkte'] for v in verzeichnis)} Spurpunkte.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
