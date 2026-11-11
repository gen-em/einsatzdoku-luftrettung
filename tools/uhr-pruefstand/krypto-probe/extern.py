"""Gegenprobe zur Krypto-Probe: öffnet, was die Uhr mit Zufall verschlossen hat.

Anlass: Nr. 563 — Fassung 2 der Gerätehülle. Die Probe-App vergleicht feste
Fälle byte-gleich gegen die Vektoren; eine Hülle mit frischem Gerätepaar,
flüchtigem Schlüssel und zufälligem IV lässt sich so nicht prüfen. Die App
schreibt solche Hüllen deshalb als Zeilen `EXTERN …` auf die Konsole, und
dieses Skript öffnet sie mit der Referenz (`tools/proben/huelle/referenz.py`)
und dem privaten Kontoschlüssel der Vektoren — also mit einer Umsetzung, die
nicht auf der Uhr läuft.

Je Zeile geprüft: die Hülle geht auf, der Absender ist die Kennung des
Schlüssels, den die Uhr nennt, der Kopf des Spurstücks trägt das absolute
ts_von und den Merker, die Punkte sind die der Beispielspur, die
Einsatzhülle trägt Zähler, Beginn und den Inhalt von PHASEN2/REA2; bei der
Kopplung passen Zusage und Vergleichswert zu den offengelegten Werten.

AUFRUF
    python3 extern.py <Konsolenmitschnitt> […]     # z. B. ~/.ciq-pruefstand/krypto_fenix6pro.txt
Ohne EXTERN-Zeile ist das Ergebnis rot, nicht leer.
"""
from __future__ import annotations

import base64
import json
import pathlib
import sys

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[2] / "proben" / "huelle"))
import referenz as r  # noqa: E402


def pruefen(zeilen: list[str]) -> list[tuple[str, bool, str]]:
    konto = r.schluessel_aus_name(r.EMPFAENGER)
    kpub = r.sec1(konto.public_key())
    n_b = bytes.fromhex(json.loads(r.VEKTOREN2.read_text("utf-8"))["kopplung"]["n_b_hex"])
    aus = []
    for z in zeilen:
        t = z.split()
        if len(t) < 6 or t[0] != "EXTERN":
            continue
        spk = bytes.fromhex(t[2])
        eigen = r.sid(spk).hex()
        if t[1] == "kopplung":
            n_g, zusage, wert = bytes.fromhex(t[3]), bytes.fromhex(t[4]), t[5]
            try:
                ok = r.kopplung_wert(kpub, spk, n_g, n_b, zusage) == wert
            except r.HuelleFehler:
                ok = False
            aus.append(("kopplung", ok, wert))
            continue
        leser = r.Leser(konto, {eigen: {"spk": spk, "bis": None}}, verlangt2=True)
        huelle = base64.b64decode(t[-1])
        name = " ".join(t[1:2] + t[3:5])
        try:
            if t[1] == "einsatz":
                d, wer = r.einsatz_lesen(leser, t[3], huelle, int(t[5]))
                soll = json.loads(r.einsatz_innen2(int(t[4]), r.T0, True, r.PHASEN2, r.REA2))
                aus.append((name, d == soll and wer == eigen, f"z {d.get('z')}"))
            else:
                von, n = (int(x) for x in t[4].split(":"))
                klar, wer = leser.huelle(huelle, t[1], t[3], t[4])
                kopf = r.spur_innen2_lesen(klar)
                soll = r.spur_innen2(int(t[5]), r.innen_binaer(r._punkte(von, n)),
                                     schluss=bool(kopf["merker"] & r.M_SCHLUSS))
                aus.append((name, klar == soll and wer == eigen, f"Merker {kopf['merker']}"))
        except (r.HuelleFehler, ValueError) as e:
            aus.append((name, False, f"geht nicht auf: {e}"))
    return aus


def main() -> int:
    if len(sys.argv) < 2:
        print(__doc__)
        return 2
    schlecht = gesamt = 0
    for pfad in sys.argv[1:]:
        befunde = pruefen(pathlib.Path(pfad).read_text("utf-8", errors="replace").splitlines())
        print(f"== {pfad}")
        if not befunde:
            print("  FEHL  keine EXTERN-Zeile")
            schlecht += 1
        for name, ok, zusatz in befunde:
            print(f"  {'ok  ' if ok else 'FEHL'}  {name} ({zusatz})")
            gesamt += 1
            schlecht += 0 if ok else 1
    print(f"{gesamt} Hüllen und Kopplungen geprüft, {schlecht} Fehler")
    return 1 if schlecht else 0


if __name__ == "__main__":
    sys.exit(main())
