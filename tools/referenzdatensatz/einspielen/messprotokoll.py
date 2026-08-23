#!/usr/bin/env python3
"""Messprotokoll aus dem Einspiellauf (E-P1-14, Vorarbeit zu R19).

WAS GEMESSEN WIRD UND WAS NICHT. Gemessen wird das SENDEVERHALTEN EINER UHR,
nicht die Geschwindigkeit dieses Skripts. Das Einspielen laeuft in Minuten ab,
wo die Dienste zwoelf Stunden dauerten; die Wanduhr des Replays sagt ueber
die Last im Betrieb nichts. Grundlage sind deshalb die SOLL-Zeitpunkte aus
dem Sendeplan — sie folgen den Ausloesern der Uhr (`Uploader.syncAll`:
stuendlich waehrend der Aufzeichnung, bei jedem Einsatzende, bei Dienstende).

Das ist eine ERHEBUNG, keine Schutzmassnahme. Ob und wie eine Mengenbremse
fuer `ingest.php` aussieht, entscheidet P5 — auf dieser Grundlage.

Aufruf:  python3 messprotokoll.py [--lauf lauf.json]
Schreibt: messprotokoll.json und messprotokoll.md
"""
from __future__ import annotations

import argparse
import collections
import json
import pathlib
import statistics
import sys
from datetime import datetime
from zoneinfo import ZoneInfo

HIER = pathlib.Path(__file__).resolve().parent
TZ = ZoneInfo("Europe/Berlin")


def main() -> int:
    p = argparse.ArgumentParser()
    p.add_argument("--lauf", default=str(HIER / "lauf.json"))
    a = p.parse_args()
    lauf = json.loads(pathlib.Path(a.lauf).read_text("utf-8"))
    m = lauf.get("messung") or []
    if not m:
        print("Keine Messwerte im Lauf-Zustand.")
        return 1

    je_dienst = collections.defaultdict(list)
    je_paket = collections.defaultdict(list)
    for e in m:
        je_dienst[e["dienst"]].append(e)
        je_paket[e["ref"]].append(e)

    # --- Abstaende zwischen den Anfragen EINES Dienstes (Soll) -----------
    abstaende: list[int] = []
    spitzen = []
    for dienst, liste in je_dienst.items():
        zeiten = sorted(x["soll_zeit"] for x in liste)
        abstaende += [b - a for a, b in zip(zeiten, zeiten[1:])]
        # Spitze: die meisten Anfragen, die auf denselben Ausloeser fallen
        haeufung = collections.Counter(zeiten)
        wann, wieviel = haeufung.most_common(1)[0]
        spitzen.append({"dienst": dienst, "anfragen": wieviel,
                        "zeitpunkt": datetime.fromtimestamp(wann, TZ).isoformat()})

    teilstuecke = [len(v) for v in je_paket.values()]
    bytes_ = [e["bytes"] for e in m]
    fehler = [e for e in m if e["status"] != 200]
    verworfen = [e for e in m if e.get("rejected")]
    uebergangen = [e for e in m if e.get("kept_phases") or e.get("kept_resus")]

    protokoll = {
        "grundlage": "Soll-Sendeplan des Generators; Auslöser wie watch/source/Uploader.mc",
        "anfragen_gesamt": len(m),
        "dienste": len(je_dienst),
        "pakete": len(je_paket),
        "anfragen_je_dienst": {
            "min": min(len(v) for v in je_dienst.values()),
            "max": max(len(v) for v in je_dienst.values()),
            "median": statistics.median(len(v) for v in je_dienst.values()),
        },
        "teilstuecke_je_paket": {
            "min": min(teilstuecke), "max": max(teilstuecke),
            "median": statistics.median(teilstuecke),
            "mehr_als_eins": sum(1 for x in teilstuecke if x > 1),
        },
        "soll_abstand_s": {
            "min": min(abstaende), "max": max(abstaende),
            "median": statistics.median(abstaende),
            "unter_60s": sum(1 for x in abstaende if x < 60),
            "null": sum(1 for x in abstaende if x == 0),
        },
        "spitze_je_dienst": sorted(spitzen, key=lambda x: -x["anfragen"])[:5],
        "body_bytes": {"min": min(bytes_), "max": max(bytes_),
                       "median": statistics.median(bytes_), "summe": sum(bytes_)},
        "punkte_gesamt": sum(e["punkte"] for e in m),
        "fehlversuche": len(fehler),
        "mit_verworfenen_werten": len(verworfen),
        "mit_uebergangener_liste": len(uebergangen),
    }
    (HIER / "messprotokoll.json").write_text(
        json.dumps(protokoll, ensure_ascii=False, indent=2) + "\n", "utf-8")

    s = protokoll
    md = f"""# Messprotokoll des Einspiellaufs

**Vorarbeit zu R19** (E-P1-14). Bemessungsgrundlage für den P5-Entwurf einer
Mengenbremse an `ingest.php`. **Erhebung, keine Schutzmaßnahme.**

## Was hier gemessen ist

Das Sendeverhalten **einer Uhr**, nicht die Geschwindigkeit des
Einspielskripts. Der Lauf schaufelt in Minuten, was im Betrieb über Tage
anfällt; die Wanduhr des Replays sagt über die Last nichts. Grundlage sind
die **Soll-Zeitpunkte** aus dem Sendeplan. Sie folgen den drei Auslösern der
Uhr (`grep syncAll watch/source`):

- stündlich während der Aufzeichnung (`Track.mc`, `REST_SYNC_INTERVAL_S`)
- am Ende jedes Einsatzes (`Model.mc`, `_endMission`)
- am Ende des Dienstes (`Model.mc`, `endDay`)

Ein Auslöser arbeitet die **Warteschlange** ab, nicht ein Paket:
`onResponse` ruft `_next()`. An einem Auslöser entstehen deshalb so viele
Anfragen, wie Teilstücke offen sind — und das ist die Zahl, auf die es
ankommt.

## Ergebnis

| Größe | Wert |
|---|---|
| Anfragen gesamt | {s['anfragen_gesamt']} |
| Dienste | {s['dienste']} |
| Pakete (Einsätze und Ruhe-Segmente) | {s['pakete']} |
| Anfragen je Dienst | {s['anfragen_je_dienst']['min']} … {s['anfragen_je_dienst']['max']} (Median {s['anfragen_je_dienst']['median']:.0f}) |
| Teilstücke je Paket | {s['teilstuecke_je_paket']['min']} … {s['teilstuecke_je_paket']['max']} (Median {s['teilstuecke_je_paket']['median']:.0f}) |
| Pakete in mehreren Teilstücken | {s['teilstuecke_je_paket']['mehr_als_eins']} |
| Trackpunkte gesamt | {s['punkte_gesamt']} |
| Body-Größe | {s['body_bytes']['min']} … {s['body_bytes']['max']} Bytes (Median {s['body_bytes']['median']:.0f}) |
| Übertragen gesamt | {s['body_bytes']['summe'] / 1024 / 1024:.1f} MB |
| **Fehlversuche** | **{s['fehlversuche']}** |
| Anfragen mit verworfenen Einzelwerten (`rejected`) | {s['mit_verworfenen_werten']} |
| Anfragen mit übergangener Liste (`kept_*`) | {s['mit_uebergangener_liste']} |

## Die Zahl, auf die es für P5 ankommt

**Der Soll-Abstand zwischen zwei Anfragen desselben Dienstes:**

| | Sekunden |
|---|---|
| kleinster Abstand | {s['soll_abstand_s']['min']} |
| Median | {s['soll_abstand_s']['median']:.0f} |
| größter Abstand | {s['soll_abstand_s']['max']} |
| Abstände unter 60 s | {s['soll_abstand_s']['unter_60s']} |
| Abstände von 0 s (gleicher Auslöser) | {s['soll_abstand_s']['null']} |

**Ein Abstand von 0 s ist der Regelfall, nicht die Ausnahme.** Er entsteht,
wann immer ein Auslöser mehrere offene Teilstücke vorfindet — die Uhr sendet
sie unmittelbar hintereinander, weil die Antwort das nächste Paket anstößt.
Eine Mengenbremse, die auf Abstände zwischen einzelnen Anfragen schaut,
würde genau das treffen, was die Uhr korrekt tut.

**Die Spitze je Dienst** (die meisten Anfragen an einem Auslöser):

| Dienst | Anfragen | Zeitpunkt |
|---|---|---|
"""
    for x in s["spitze_je_dienst"]:
        md += f"| {x['dienst']} | {x['anfragen']} | {x['zeitpunkt']} |\n"
    md += """
Daraus folgt für P5: Eine Bremse muss den **Ausbruch** zulassen und die
**Menge über die Zeit** begrenzen — nicht die Rate zwischen zwei Anfragen.
Wie hoch die Grenze liegt, entscheidet P5; diese Erhebung sagt nur, was
eine Uhr im Normalbetrieb tatsächlich tut.
"""
    (HIER / "messprotokoll.md").write_text(md, "utf-8")
    print(f"{s['anfragen_gesamt']} Anfragen, {s['fehlversuche']} Fehlversuche.")
    print("messprotokoll.json und messprotokoll.md geschrieben.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
