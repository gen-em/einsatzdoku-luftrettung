#!/usr/bin/env python3
"""Abgleich Demo-Fixture <-> Referenz: tragen beide denselben Bestand?

Anlass: Nr. 388 — die eingecheckte Fixture war vom 27.09.2026, die Referenz
vom 19.10.2026, und beide trugen verschiedene Bestände (67 292 gegen
67 478 Punkte), ohne dass etwas es meldete (Konzept RD, F-RD-01, Q-RD-08).

Verglichen werden Zahlen, kein Inhalt: Diensttage, Einsätze und Ruhesegmente
mit ihrem Papierkorb, Schnitte und Löschvermerke, und je Spur (über die
`client_ref`) die Punktzahl `n`, `n_original` und ob sie ausgedünnt ist
(Stufe 3). Stufe 1 und 2 unterscheidet der Kern einer Sicherung nicht — er
nennt beide 2 (`edbak_build()`, Umriss) —, deshalb hier auch nicht. Und
`n_original` trägt der Kern nur bei Stufe 3 so, wie es im Konto liegt; bei
Stufe 1 und 2 nennt er `n` („die Zahlen beschreiben, was in der Datei
steht"), auch wo die Marke des Stücks weiter reicht — nach einer
Sequenzlücke oder wenn geschnittene Punkte beim Packen gefallen sind. Die
Fixture trägt die Marke; verglichen wird sie deshalb nur bei Stufe 3
(gemessen beim ersten Lauf: drei Segmente der Stufe 2, Konzept RD).

DREI DATEIEN, EINE KETTE. Die Referenz-`.edbak` ist versiegelt; sie zu
öffnen braucht `cryptography`, und Stufe 1 hat nur die Standardbibliothek.
Deshalb liegt ihr Umriss daneben (`referenz/umriss.json`, mit SHA-256 der
`.edbak`), und der Abgleich hält:

  Standard   Fixture gegen umriss.json, umriss.json gegen die SHA-256 der
             eingecheckten .edbak — Stufe 1, ohne Installation
  --tief     dazu den Umriss aus der .edbak neu rechnen und gegen
             umriss.json halten (Prüfstand; braucht `cryptography`)
  --schreiben  umriss.json aus der .edbak erzeugen (nach referenz_export.mjs)

Wer eine der drei Dateien allein erneuert, macht den Abgleich rot.

Aufruf:  python3 tools/referenzdatensatz/fixture/abgleich.py [--tief | --schreiben]
                 [--fixture PFAD] [--selbstprobe]
Rückgabe: 0 gleich · 1 Abweichung · 2 nicht gelaufen
"""
from __future__ import annotations

import gzip
import hashlib
import json
import pathlib
import sys

HIER = pathlib.Path(__file__).resolve().parent
WURZEL = HIER.parents[2]
REFERENZ = HIER.parent / "referenz"
UMRISS = REFERENZ / "umriss.json"
FIXTURE = WURZEL / "server" / "demo" / "fixture.json.gz"
EDBAK_PASSWORT = "nadokudemo0815"      # wie referenz_export.mjs und kreislauf.py


def umriss_aus(daten: dict, n_von) -> dict:
    """Die Zahlen eines Bestands. `n_von(eintrag)` liefert die Punktzahl einer
    Spur oder None — in der Fixture die Länge der Punktliste, im Kern `n`."""
    def zaehl(liste: list) -> tuple[int, int]:
        return len(liste), sum(1 for x in liste if x.get("deleted_at") is not None)
    m, r, d = daten.get("missions", []), daten.get("rest_segments", []), daten.get("days", [])
    spuren = {}
    for e in m + r:
        n = n_von(e)
        if n:
            duenn = int(e.get("stufe") or 0) == 3
            spuren[e["client_ref"]] = [duenn, int(e.get("n_original") or n) if duenn else int(n), int(n)]
    zahlen = {
        "diensttage": list(zaehl(d)), "einsaetze": list(zaehl(m)), "ruhesegmente": list(zaehl(r)),
        "schnitte": sum(len(e.get("schnitte") or []) for e in m),
        "loeschvermerke": sum(len(e.get("loeschungen") or []) for e in r),
        "spuren": len(spuren), "punkte": sum(v[2] for v in spuren.values()),
        "punkte_stufe3": sum(v[2] for v in spuren.values() if v[0]),
    }
    return {"zahlen": zahlen, "spuren": dict(sorted(spuren.items()))}


def aus_fixture(pfad: pathlib.Path) -> dict:
    fx = json.loads(gzip.decompress(pfad.read_bytes()).decode("utf-8"))
    return umriss_aus(fx["daten"], lambda e: len(e.get("track") or []))


def edbak_datei() -> pathlib.Path:
    liste = sorted(REFERENZ.glob("*.edbak"))
    if len(liste) != 1:
        raise RuntimeError(f"referenz/ trägt {len(liste)} .edbak, erwartet ist genau eine")
    return liste[0]


def aus_edbak(pfad: pathlib.Path) -> dict:
    sys.path.insert(0, str(HIER.parent / "vergleich"))
    import lesen  # noqa: PLC0415 — nur hier, braucht `cryptography`
    kern = lesen.lesen_edbak(str(pfad), EDBAK_PASSWORT)
    return umriss_aus(kern, lambda e: e.get("n"))


def unterschiede(a: dict, b: dict, wa: str, wb: str) -> list[str]:
    aus = []
    for k in sorted(set(a["zahlen"]) | set(b["zahlen"])):
        if a["zahlen"].get(k) != b["zahlen"].get(k):
            aus.append(f"{k}: {wa} {a['zahlen'].get(k)}, {wb} {b['zahlen'].get(k)}")
    sa, sb = a["spuren"], b["spuren"]
    nur_a, nur_b = sorted(set(sa) - set(sb)), sorted(set(sb) - set(sa))
    if nur_a:
        aus.append(f"{len(nur_a)} Spuren nur in {wa}, z. B. {nur_a[:3]}")
    if nur_b:
        aus.append(f"{len(nur_b)} Spuren nur in {wb}, z. B. {nur_b[:3]}")
    anders = [r for r in sorted(set(sa) & set(sb)) if sa[r] != sb[r]]
    for r in anders[:5]:
        aus.append(f"Spur {r}: {wa} {sa[r]}, {wb} {sb[r]} (Stufe 3, n_original, n)")
    if len(anders) > 5:
        aus.append(f"… und {len(anders) - 5} weitere Spuren")
    return aus


def selbstprobe() -> int:
    """Der Abgleich muss eine Abweichung sehen — je Art eine verbogene Kopie."""
    basis = {"days": [{"deleted_at": None}], "missions": [
        {"client_ref": "m-1", "track": [[0, 1, 1, 1, 1]] * 4, "stufe": 3, "n_original": 9,
         "schnitte": [{}], "deleted_at": None}],
        "rest_segments": [{"client_ref": "r-1", "track": [[0, 1, 1, 1, 1]] * 2, "loeschungen": [{}],
                           "deleted_at": "x"}]}
    a = umriss_aus(basis, lambda e: len(e.get("track") or []))
    faelle = 0
    for name, biegen in [
        ("ein Punkt mehr", lambda d: d["missions"][0]["track"].append([0, 1, 1, 1, 1])),
        ("Stufe 3 verloren", lambda d: d["missions"][0].update(stufe=2)),
        ("n_original anders", lambda d: d["missions"][0].update(n_original=8)),
        ("ein Schnitt weniger", lambda d: d["missions"][0].update(schnitte=[])),
        ("Löschvermerk weg", lambda d: d["rest_segments"][0].update(loeschungen=[])),
        ("Papierkorb anders", lambda d: d["rest_segments"][0].update(deleted_at=None)),
        ("Spur fehlt", lambda d: d["rest_segments"][0].update(track=[])),
    ]:
        kopie = json.loads(json.dumps(basis))
        biegen(kopie)
        b = umriss_aus(kopie, lambda e: len(e.get("track") or []))
        gesehen = bool(unterschiede(a, b, "a", "b"))
        print(f"  [{'ok ' if gesehen else 'FEHL'}] {name}")
        faelle += 0 if gesehen else 1
    gleich = not unterschiede(a, umriss_aus(json.loads(json.dumps(basis)),
                                            lambda e: len(e.get("track") or [])), "a", "b")
    print(f"  [{'ok ' if gleich else 'FEHL'}] eine unveränderte Kopie ist gleich")
    faelle += 0 if gleich else 1
    print(f"Selbstprobe: 8 Fälle, {faelle} Fehlschläge")
    return 1 if faelle else 0


def main() -> int:
    args = sys.argv[1:]
    if "--selbstprobe" in args:
        return selbstprobe()
    fixture = pathlib.Path(args[args.index("--fixture") + 1]) if "--fixture" in args else FIXTURE
    try:
        edbak = edbak_datei()
        sha = hashlib.sha256(edbak.read_bytes()).hexdigest()
        if "--schreiben" in args:
            u = aus_edbak(edbak)
            UMRISS.write_text(json.dumps({"edbak": edbak.name, "sha256": sha, **u},
                                         ensure_ascii=False, indent=1) + "\n", "utf-8")
            print(f"umriss.json geschrieben: {u['zahlen']}")
            return 0
        if not UMRISS.exists():
            print("NICHT GELAUFEN: referenz/umriss.json fehlt — abgleich.py --schreiben")
            return 2
        umriss = json.loads(UMRISS.read_text("utf-8"))
        fx = aus_fixture(fixture)
    except Exception as ex:  # noqa: BLE001 — die Meldung ist der Befund
        print(f"NICHT GELAUFEN: {ex}")
        return 2
    befunde = []
    if umriss.get("edbak") != edbak.name or umriss.get("sha256") != sha:
        befunde.append(f"umriss.json gehört zu {umriss.get('edbak')} ({str(umriss.get('sha256'))[:12]}…), "
                       f"eingecheckt ist {edbak.name} ({sha[:12]}…) — abgleich.py --schreiben")
    befunde += unterschiede(fx, umriss, "Fixture", "Referenz")
    if "--tief" in args:
        try:
            befunde += unterschiede(aus_edbak(edbak), umriss, ".edbak", "umriss.json")
        except Exception as ex:  # noqa: BLE001
            print(f"NICHT GELAUFEN (--tief): {ex}")
            return 2
    z = fx["zahlen"]
    print(f"Fixture {fixture.name} gegen Referenz {edbak.name}"
          + (" (dazu die .edbak nachgerechnet)" if "--tief" in args else ""))
    print(f"  Diensttage {z['diensttage']}, Einsätze {z['einsaetze']}, Ruhesegmente {z['ruhesegmente']} "
          f"(gesamt, davon im Papierkorb)")
    print(f"  {z['spuren']} Spuren, {z['punkte']} Punkte, davon {z['punkte_stufe3']} auf Stufe 3; "
          f"{z['schnitte']} Schnitte, {z['loeschvermerke']} Löschvermerke")
    if befunde:
        print(f"ABWEICHUNG ({len(befunde)})")
        for b in befunde:
            print(f"  {b}")
        return 1
    print("Gleich.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
