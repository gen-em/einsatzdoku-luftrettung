#!/usr/bin/env python3
"""Rundlauf der Uhr, Fassung 2 — die Rechnung der Gegenseite.

Anlass: Nr. 577 — die Garmin-Uhr koppelt seit Uhr 5.0.0 mit Vergleichswert
und sendet Fassung 2; ob ihr Wert der des Browsers ist und ob ihre Hüllen
aufgehen, zeigt nur ein Paket, das den Weg Uhr -> pair.php/ingest.php ->
Datenbank gegangen ist.

Beides rechnet die REFERENZ (`tools/proben/huelle/referenz.py`), nicht eine
zweite Fassung hier: `kopplung_wert` und `oeffnen2`.

Aufruf:
  fassung2.py wert <kontoPub> <spk> <n_g> <n_b>     (alles Base64)
      -> die sechs Ziffern
  fassung2.py oeffnen < JSON {"priv": <PKCS8, Base64>, "spk", "huelle"
                              (Base64), "art", "ref", "platz"}
      -> JSON {"klar": <Base64>} oder {"fehler": <Grund>}
"""
import base64
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[2] / "proben" / "huelle"))
import referenz  # noqa: E402
from cryptography.hazmat.primitives.serialization import load_der_private_key  # noqa: E402


def b(x: str) -> bytes:
    return base64.b64decode(x, validate=True)


def main() -> int:
    if len(sys.argv) == 6 and sys.argv[1] == "wert":
        print(referenz.kopplung_wert(b(sys.argv[2]), b(sys.argv[3]), b(sys.argv[4]), b(sys.argv[5])))
        return 0
    if len(sys.argv) == 2 and sys.argv[1] == "oeffnen":
        e = json.load(sys.stdin)
        priv = load_der_private_key(b(e["priv"]), password=None)
        spk = b(e["spk"])
        soll = referenz.sid(spk)
        try:
            klar, _ = referenz.oeffnen2(b(e["huelle"]), priv,
                                        lambda s: spk if s == soll else None,
                                        e["art"], e["ref"], e.get("platz", ""))
        except referenz.HuelleFehler as f:
            print(json.dumps({"fehler": str(f)}))
            return 0
        print(json.dumps({"klar": base64.b64encode(klar).decode()}))
        return 0
    print(__doc__)
    return 2


if __name__ == "__main__":
    sys.exit(main())
