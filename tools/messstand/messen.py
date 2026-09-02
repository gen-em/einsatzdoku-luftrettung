"""Der Messstand — Klammer über alle Schritte (E-S2-23, R35).

WOFUER. S2 gibt Zielzahlen aus (E-S2-24): Suche unter 5 s, Tagesansicht unter
3 s, Backup unter 5 min, Wiederherstellung unter 15 min, Backup-Datei
unter 25 MB, Spuren unter 3 MB je 1000 Einsätzen. Diese Zahlen brauchen zwei
Dinge: einen Bestand, an dem sie gemessen werden können, und einen
Ausgangswert, gegen den sich die Verbesserung halten lässt. Beides stellt
dieses Werkzeug her — reproduzierbar, ohne Handarbeit.

DIE SCHRITTE

  konto        Messstandkonto anlegen (bzw. mit --frisch neu anlegen)
  bestand      Großbestand erzeugen (vervielfaeltigen.py)
  einspielen   Bestand über den REGULAEREN Weg einspielen (einspielen.mjs)
  browser      Browserprobe unter CPU-Drossel (browserprobe.mjs)
  server       Serverprobe: Tabellengrößen, Speicherspitzen (serverprobe.py)
  protokoll    Alles zu einem Messprotokoll zusammenfassen

    python3 messen.py                       # alle Schritte
    python3 messen.py --schritte server     # einzeln
    python3 messen.py --frisch              # Konto vorher leeren

KEINE EIGENEN WEGE. Konto anlegen und löschen erledigen die geprüften
Bausteine des Referenzdatensatzes (`einspielen/passwort_setzen.mjs`,
`vergleich/kreislauf.py`). Der Messstand schreibt sich dafür nichts Eigenes —
ein zweiter Weg, der Konten anlegt oder löscht, ist genau der, den niemand
pflegt.

DER RIEGEL LIEGT IN DEN TEILWERKZEUGEN, nicht hier. `einspielen.mjs` weigert
sich, ein anderes als das Messstandkonto zu füllen, und `konto_loeschen()`
weigert sich, ein Konto ohne das Messstand-Präfix zu löschen. Beides schließt
nach innen: Wer nicht positiv feststellen kann, dass nichts kaputtgeht,
bricht ab.
"""
from __future__ import annotations

import argparse
import json
import os
import pathlib
import subprocess
import sys
import time

HIER = pathlib.Path(__file__).resolve().parent
WERKZEUGE = HIER.parent
sys.path.insert(0, str(WERKZEUGE / "referenzdatensatz" / "vergleich"))
sys.path.insert(0, str(WERKZEUGE / "referenzdatensatz" / "einspielen"))

PLAYWRIGHT = os.environ.get(
    "PLAYWRIGHT_MODUL", "/opt/node22/lib/node_modules/playwright/index.mjs")

KONTO = os.environ.get("MESSSTAND_KONTO", "messstand@gen-em.org")
KONTO_PW = os.environ.get("MESSSTAND_PASSWORT", "messstandpruefung2026")
BACKUP_PW = os.environ.get("MESSSTAND_BACKUP_PASSWORT", "nadokudemo0815")
PRAEFIX = "messstand"

ALLE_SCHRITTE = ["konto", "bestand", "einspielen", "browser", "server", "protokoll"]


def melde(t: str) -> None:
    print(t, flush=True)


def lauf(befehl: list[str], **kw) -> subprocess.CompletedProcess:
    e = subprocess.run(befehl, text=True, **kw)
    if e.returncode != 0:
        raise RuntimeError(f"{' '.join(befehl[:2])} endete mit {e.returncode}")
    return e


# ------------------------------------------------------------------ Schritte

def schritt_konto(a) -> None:
    import kreislauf
    admin = (a.admin_email, a.admin_passwort)
    if a.frisch:
        weg = kreislauf.konto_loeschen(a.basis, admin, KONTO, praefix=PRAEFIX)
        melde(f"  Vorhandenes Messstandkonto {'entfernt' if weg else 'nicht vorhanden'}.")
    import sitzung as sitzungsmodul
    s = sitzungsmodul.Sitzung(a.basis).anmelden(*admin)
    if KONTO in s.get("admin_users.php").text:
        melde(f"  Konto {KONTO} besteht bereits.")
        return
    kreislauf.konto_anlegen(a.basis, admin, KONTO, KONTO_PW)


def schritt_bestand(a) -> None:
    lauf([sys.executable, str(HIER / "vervielfaeltigen.py"),
          "--einsaetze", str(a.einsaetze),
          "--runden-je-datei", str(a.runden_je_datei),
          "--passwort", BACKUP_PW,
          "--ziel", str(pathlib.Path(a.ausgabe) / "bestand")])


def schritt_einspielen(a) -> None:
    ordner = str(pathlib.Path(a.ausgabe) / "bestand")
    lauf(["node", str(HIER / "einspielen.mjs"), a.basis, ordner,
          str(pathlib.Path(a.ausgabe) / "einspielprotokoll.json")],
         env={**os.environ, "PLAYWRIGHT_MODUL": PLAYWRIGHT,
              "MESSSTAND_KONTO": KONTO, "MESSSTAND_PASSWORT": KONTO_PW,
              "MESSSTAND_BACKUP_PASSWORT": BACKUP_PW})


def schritt_browser(a) -> None:
    lauf(["node", str(HIER / "browserprobe.mjs"), a.basis,
          str(pathlib.Path(a.ausgabe) / "browserprobe.json"), str(a.drossel)],
         env={**os.environ, "PLAYWRIGHT_MODUL": PLAYWRIGHT,
              "MESSSTAND_KONTO": KONTO, "MESSSTAND_PASSWORT": KONTO_PW,
              "MESSSTAND_BACKUP_PASSWORT": BACKUP_PW})


def schritt_server(a) -> None:
    lauf([sys.executable, str(HIER / "serverprobe.py"),
          "--ausgabe", str(pathlib.Path(a.ausgabe) / "serverprobe.json")])


def schritt_protokoll(a) -> None:
    ordner = pathlib.Path(a.ausgabe)
    zusammen: dict = {"gemessen_am": time.strftime("%Y-%m-%dT%H:%M:%S%z"),
                      "basis": a.basis, "konto": KONTO, "drossel": a.drossel}
    for name, datei in (("bestand", "bestand/verzeichnis.json"),
                        ("einspielen", "einspielprotokoll.json"),
                        ("browser", "browserprobe.json"),
                        ("server", "serverprobe.json")):
        p = ordner / datei
        zusammen[name] = json.loads(p.read_text("utf-8")) if p.exists() else None
        if zusammen[name] is None:
            melde(f"  {datei} fehlt — dieser Teil steht im Protokoll als null.")
    ziel = ordner / "messprotokoll.json"
    ziel.write_text(json.dumps(zusammen, ensure_ascii=False, indent=2) + "\n", "utf-8")
    melde(f"  Messprotokoll: {ziel}")


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--basis", default="https://127.0.0.1:8443")
    p.add_argument("--schritte", default=",".join(ALLE_SCHRITTE))
    p.add_argument("--ausgabe", default="/tmp/messstand")
    p.add_argument("--einsaetze", type=int, default=5000)
    p.add_argument("--runden-je-datei", type=int, default=3)
    p.add_argument("--drossel", type=int, default=6)
    p.add_argument("--frisch", action="store_true",
                   help="Messstandkonto vorher löschen (nur mit Präfix "
                        f"'{PRAEFIX}')")
    p.add_argument("--admin-email", default="admin@gen-em.org")
    p.add_argument("--admin-passwort", default="adminlokal2026")
    a = p.parse_args()

    pathlib.Path(a.ausgabe).mkdir(parents=True, exist_ok=True)
    schritte = [x.strip() for x in a.schritte.split(",") if x.strip()]
    unbekannt = [x for x in schritte if x not in ALLE_SCHRITTE]
    if unbekannt:
        p.error(f"Unbekannte(r) Schritt(e): {', '.join(unbekannt)}")

    funktionen = {"konto": schritt_konto, "bestand": schritt_bestand,
                  "einspielen": schritt_einspielen, "browser": schritt_browser,
                  "server": schritt_server, "protokoll": schritt_protokoll}
    for name in schritte:
        melde(f"[{name}]")
        t0 = time.monotonic()
        funktionen[name](a)
        melde(f"  ({time.monotonic() - t0:.1f} s)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
