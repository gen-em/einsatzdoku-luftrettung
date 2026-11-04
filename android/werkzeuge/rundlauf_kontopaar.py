#!/usr/bin/env python3
"""Rundlauf: dem Prüfkonto ein Schlüsselpaar geben, falls es keines hat.

Anlass: Nr. 491. Seit S11 verschließt das Handy jedes Paket mit dem
öffentlichen Schlüssel des Kontos und sendet ohne ihn nichts. Konto 1 einer
frisch eingerichteten örtlichen Anlage (`admin@example.invalid`) hat keines —
das legt erst ein Browser nach der Anmeldung an. Die Sende-Rundläufe
(`MissionRundlaufTest`, `SendeRundlaufTest`) meldeten deshalb „0 Anfragen",
und die App verhielt sich dabei richtig.

DER WEG DES BROWSERS, KEIN SONDERZUGANG. Angelegt wird über
`api/kontopaar.php`, mit dem Passwortnachweis und dem privaten Teil unter
dem Inhaltsschlüssel — durch `Sitzung.kontoschluessel()` im
Einspielwerkzeug, das genau das tut. Ein Paar, das schon da ist, bleibt;
der Server ersetzt ohnehin keines (409).

Aufruf (aus `Kopplungshilfe.kontopaarSichern()`):
    python3 android/werkzeuge/rundlauf_kontopaar.py [--basis URL]
Rückgabe 0 = Paar steht, sonst 1 mit dem Grund auf stdout.
"""
import argparse
import pathlib
import sys

WURZEL = pathlib.Path(__file__).resolve().parents[2]
sys.path.insert(0, str(WURZEL / "tools" / "referenzdatensatz" / "einspielen"))
sys.path.insert(0, str(WURZEL / "tools" / "referenzdatensatz" / "vergleich"))
import sitzung as sitzungsmodul   # noqa: E402
import kreislauf                  # noqa: E402 — Prüfkonto und Passwort, EINE Stelle


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    # 8443, nicht 8080: Das Sitzungs-Cookie trägt `secure`.
    p.add_argument("--basis", default="https://127.0.0.1:8443")
    a = p.parse_args()
    email, passwort = kreislauf.ADMIN_VORGABE
    try:
        s = sitzungsmodul.Sitzung(a.basis).anmelden(email, passwort)
        sec1 = s.kontoschluessel()
    except Exception as e:  # noqa: BLE001 — der Grund geht an den Prüffall
        print(f"Kein Schlüsselpaar für {email}: {e}")
        return 1
    print(f"Schlüsselpaar für {email} steht ({len(sec1)} Byte öffentlich).")
    return 0


if __name__ == "__main__":
    sys.exit(main())
