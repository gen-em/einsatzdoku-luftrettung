"""Ortsdaten wie der Browser — für das Einspielwerkzeug.

Anlass: Nr. 43 — seit S11-05b nimmt das Einsatzformular Phasenorte und
Reanimation nur noch in der Einsatzhülle an, Transportziel und Ortshöhe nur im
`pat_blob`; der Browser schneidet und schickt verschlossene
Stücke. `einspielen.py` bediente beides im alten Format. Diese
Datei tut für das Werkzeug, was `assets/ortsdaten.js` für den Browser tut:
Hüllen und Stücke öffnen und verschließen.

  privat_laden(pkcs8_b64)                     der private Schlüssel des Kontos
  einsatz_oeffnen(m, priv)                    die Einsatzhülle, geöffnet, oder None
  einsatz_verschliessen(innen, sec1, ref)     verschließen, Base64
  spur_oeffnen(stuecke, art, ref, sperren, priv)
                                              Punkte {seq, lat, lon, ele, ts}
  stuecke_bauen(punkte, art, ref, sec1, neu)  Punkte → Stücke (≤ 2000, an Lücken)
  gepackt_bauen(punkte, art, ref, sec1, …)    Punkte → ein Stück der Stufe 2/3
  ortshoehe(punkte, phasen)                   Höhe des Einsatzorts oder None
  zeiten([(tag, hh:mm, versatz), …])          Ortszeit → Sekunden, über
                                              `local_to_utc()` (zeiten_cli.php)

DAS FORMAT steht in `tools/proben/huelle/referenz.py` und nur dort; diese
Datei benutzt dessen `verschliessen()` und `oeffnen()`. Die Regeln beim
Öffnen sind die von `ortsdaten.js`: Punkt i eines Stücks hat die
Nummer `seq_from + i`, doppelte Nummern zählen einmal, Punkte in einer
Sperrzeit fallen weg, Höhe -32768 heißt keine.
"""
from __future__ import annotations

import base64
import json
import pathlib
import struct
import subprocess
import sys

HIER = pathlib.Path(__file__).resolve().parent
HUELLE = HIER.parents[1] / "proben" / "huelle"
sys.path.insert(0, str(HUELLE))
import referenz  # noqa: E402  (Format der Gerätehülle, eine Stelle)

from cryptography.hazmat.primitives import serialization  # noqa: E402

HOEHE_UNBEKANNT = referenz.HOEHE_UNBEKANNT
STUECK_MAX = 2000          # LIMIT_TRACKPUNKTE_ANFRAGE


def privat_laden(pkcs8_b64: str):
    return serialization.load_der_private_key(base64.b64decode(pkcs8_b64), password=None)


def einsatz_oeffnen(m: dict, priv) -> dict | None:
    if not m.get("ort_huelle"):
        return None
    innen = referenz.oeffnen(base64.b64decode(m["ort_huelle"]), priv, "einsatz",
                             str(m["client_ref"]), "")
    return json.loads(innen.decode("utf-8"))


def einsatz_verschliessen(innen: dict, sec1: bytes, ref: str) -> str:
    klar = json.dumps(innen, ensure_ascii=False, separators=(",", ":")).encode("utf-8")
    return base64.b64encode(referenz.verschliessen(klar, sec1, "einsatz", ref, "")).decode("ascii")


def spur_oeffnen(stuecke: list, art: str, ref: str, sperren: list, priv) -> list[dict]:
    """Wie `EdOrtsdaten.spur()`: Stücke der Stufe 2 und 3 zuerst —
    ihre Punkte tragen keine eigene Nummer; was ein Stück der Stufe 1 unter
    ihrer Marke trägt, steckt schon darin."""
    nach: dict[int, dict] = {}
    marke = 0
    for st in stuecke or []:
        stufe = int(st.get("stufe") or 1)
        if stufe < 2:
            continue
        n, n_orig = int(st["punkte"]), int(st["n_original"])
        innen = referenz.oeffnen(base64.b64decode(st["huelle"]), priv, art, ref,
                                 referenz.platz_gepackt(int(st["seq_from"]), n, stufe, n_orig))
        marke = max(marke, int(st["seq_from"]) + n_orig)
        for i, (la, lo, h, ts) in enumerate(referenz.gepackt_lesen(innen, n, int(st["ts_von"]))):
            if any(s["von"] <= ts <= s["bis"] for s in sperren or []):
                continue
            seq = int(st["seq_from"]) + i
            nach.setdefault(seq, {"seq": seq, "lat": la, "lon": lo, "ele": h, "ts": ts})
    for st in stuecke or []:
        if int(st.get("stufe") or 1) >= 2:
            continue
        innen = referenz.oeffnen(base64.b64decode(st["huelle"]), priv, art, ref,
                                 f"{st['seq_from']}:{st['punkte']}")
        for i in range(len(innen) // 14):
            la, lo, h, dt = struct.unpack(">iihI", innen[i * 14:(i + 1) * 14])
            ts = int(st["ts_von"]) + dt
            if any(s["von"] <= ts <= s["bis"] for s in sperren or []):
                continue
            seq = int(st["seq_from"]) + i
            if seq < marke or seq in nach:
                continue
            nach[seq] = {"seq": seq, "lat": la / 1e7, "lon": lo / 1e7,
                         "ele": None if h == HOEHE_UNBEKANNT else h, "ts": ts}
    return [nach[k] for k in sorted(nach)]


def _runde(x: float) -> int:
    """Rundung wie PHPs `round()` und `ortsdaten.js`: von der Null weg."""
    return int(abs(x) + 0.5) * (1 if x >= 0 else -1)


def stuecke_bauen(punkte: list[dict], art: str, ref: str, sec1: bytes,
                  neu: bool = False) -> list[dict]:
    liste = [dict(p, seq=i if neu else p["seq"]) for i, p in enumerate(punkte)]
    gruppen: list[list[dict]] = []
    for p in liste:
        if gruppen and p["seq"] == gruppen[-1][-1]["seq"] + 1 and len(gruppen[-1]) < STUECK_MAX:
            gruppen[-1].append(p)
        else:
            gruppen.append([p])
    aus = []
    for g in gruppen:
        von = min(p["ts"] for p in g)
        innen = b"".join(struct.pack(
            ">iihI", _runde(p["lat"] * 1e7), _runde(p["lon"] * 1e7),
            referenz.hoehe_innen(p["ele"]),
            max(0, p["ts"] - von)) for p in g)
        platz = f"{g[0]['seq']}:{len(g)}"
        aus.append({"seq_from": g[0]["seq"], "punkte": len(g), "ts_von": von,
                    "ts_bis": max(p["ts"] for p in g),
                    "huelle": base64.b64encode(referenz.verschliessen(innen, sec1, art, ref, platz))
                              .decode("ascii")})
    return aus


def gepackt_bauen(punkte: list[dict], art: str, ref: str, sec1: bytes, stufe: int,
                  seq_from: int, n_original: int) -> dict:
    """Ein Stück der Stufe 2 oder 3 aus Punkten {lat, lon, ele, ts}."""
    von = min(p["ts"] for p in punkte)
    innen = referenz.innen_gepackt([(p["lat"], p["lon"], p["ele"], p["ts"]) for p in punkte], von)
    platz = referenz.platz_gepackt(seq_from, len(punkte), stufe, n_original)
    return {"seq_from": seq_from, "punkte": len(punkte), "n_original": n_original, "stufe": stufe,
            "ts_von": von, "ts_bis": max(p["ts"] for p in punkte),
            "huelle": base64.b64encode(referenz.verschliessen(innen, sec1, art, ref, platz)).decode("ascii")}


def ortshoehe(punkte: list[dict], phasen: list[tuple[int, int]]) -> int | None:
    """Die Regel von `EdOrtsdaten.ortshoehe()`: Bezug die früheste Phase 5,
    sonst 6; der zeitlich nächste Punkt, höchstens 300 s entfernt, mit Höhe."""
    bezug = None
    for nr in (5, 6):
        t = sorted(ts for n, ts in phasen if n == nr)
        if t:
            bezug = t[0]
            break
    if bezug is None:
        return None
    abstand, hoehe = None, None
    for p in punkte:
        d = abs(p["ts"] - bezug)
        if abstand is None or d < abstand:
            abstand, hoehe = d, p["ele"]
    if hoehe is None or abstand is None or abstand > 300:
        return None
    return _runde(hoehe)


def zeiten(anfragen: list[tuple[str, str, int]]) -> list[int | None]:
    r = subprocess.run(["php", str(HUELLE / "zeiten_cli.php")],
                       input=json.dumps(anfragen), capture_output=True, text=True, check=False)
    if r.returncode != 0:
        raise RuntimeError(f"zeiten_cli.php: {r.stderr.strip() or r.returncode}")
    return json.loads(r.stdout)
