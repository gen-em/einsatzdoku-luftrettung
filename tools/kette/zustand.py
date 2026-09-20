#!/usr/bin/env python3
"""Zustandsdatei der Auslieferungsaktion prüfen und anlegen (AP4, F-KH-U-25).

WARUM ES DAS GIBT. Die Auslieferungsaktion holt vor jeder Übertragung ihre
Zustandsdatei vom Server (`getServerFiles` → `downloadFileList`). Fehlt sie,
sendet sie `RETR` auf einen Namen, den es nicht gibt — und der Datenkanal
steht zu diesem Zeitpunkt schon (`EPSV`, Antwort 229). Der Server schliesst
ihn; `basic-ftp` liest `ECONNRESET` auf dem Datensocket statt der `550` auf
dem Steuerkanal, und die Verbindung ist tot.

DIE AKTION MERKT ES NICHT. `getServerFiles` faengt jeden Fehler ab und deutet
ihn als „first publish"; danach rechnet sie mit einem toten Client weiter und
stirbt beim ersten `MKD`. Gemeldet wird also eine Stelle drei Schritte hinter
der Ursache -- deshalb stand `ensureDir` acht Trennversuche lang im Verdacht.

UND DER ZUSTAND ERHAELT SICH SELBST: Solange keine Zustandsdatei da ist,
stirbt jeder Lauf daran, und weil er stirbt, wird nie eine geschrieben. Jeder
Lauf ist der erste.

Dieses Werkzeug legt sie hin, bevor die Aktion laeuft -- einmal, und danach
nur noch, wenn sie fehlt. Es ist der kleinste Eingriff, den es gibt: Der
Transport bleibt, die Aktion bleibt, das Loeschverhalten bleibt.

WAS ES NICHT TUT: eine vorhandene Datei anfassen. Sie traegt den Bestand des
Servers; sie zu ueberschreiben hiesse, der Aktion zu sagen, der Server sei
leer -- und das waere beim naechsten Lauf eine Voll-Uebertragung von 688
Dateien. Gefunden wird sie, oder es wird eine neue angelegt. Nie beides.
"""

from __future__ import annotations

import argparse
import json
import os
import subprocess
import sys
import time

# Der Kopf der Datei, wortgleich aus `types.js` der Aktion (Fassung 1.2.5).
# WORTGLEICH IST PFLICHT: Die Aktion liest ihn nicht, aber ein Mensch, der die
# Datei auf dem Server findet, soll dieselbe Warnung lesen wie bei einer, die
# die Aktion selbst geschrieben hat.
BESCHREIBUNG = (
    "DO NOT DELETE THIS FILE. This file is used to keep track of which files "
    "have been synced in the most recent deployment. If you delete this file "
    "a resync will need to be done (which can take a while) - read more: "
    "https://github.com/SamKirkland/FTP-Deploy-Action"
)
FASSUNG = "1.0.0"

FTP_ZEITGRENZE_S = 60
MASKE = "***"


def zustand_json(zeitstempel_ms: int | None = None) -> str:
    """Eine gültige, leere Zustandsdatei.

    `data: []` heisst „auf dem Server liegt nichts". Beim ersten echten Lauf
    ist das richtig: Die Aktion uebertraegt dann alles und schreibt die Datei
    danach selbst fort. Eine erfundene Dateiliste waere schlimmer als keine --
    sie liesse die Aktion Dateien ueberspringen, die es nicht gibt.
    """
    return json.dumps({
        "description": BESCHREIBUNG,
        "version": FASSUNG,
        "generatedTime": zeitstempel_ms if zeitstempel_ms is not None
        else int(time.time() * 1000),
        "data": [],
    }, indent=4)


def maskieren(text: str, geheimnisse: list[str]) -> str:
    for g in geheimnisse:
        if g and len(g) >= 4:
            text = text.replace(g, MASKE)
    return text


def sag(text: str, geheimnisse: list[str], nach_stderr: bool = False) -> None:
    strom = sys.stderr if nach_stderr else sys.stdout
    print(maskieren(text, geheimnisse), file=strom, flush=True)


def zerlegen(pfad: str) -> tuple[str, str]:
    """`./httpdocs/../.deploy-state.json` → (Verzeichnis, Dateiname).

    DER PFAD KOMMT AUS `state-name` UND IST RELATIV ZUM ZIELVERZEICHNIS --
    mit `../` darin, und das ist der Regelfall und kein Sonderfall: Die
    Zustandsdatei liegt absichtlich UEBER dem Webroot, damit sie nicht
    oeffentlich abrufbar ist.
    """
    pfad = pfad.strip()
    if "/" not in pfad:
        return "", pfad
    verzeichnis, name = pfad.rsplit("/", 1)
    return verzeichnis, name


def curl_ftp(argumente: list[str], konto: str, passwort: str,
             lauf=subprocess.run) -> tuple[int, str, str]:
    """Ein `curl`-Aufruf. Passwort über `--config -`, nie über die Zeile."""
    befehl = ["curl", "--config", "-", *argumente]
    try:
        e = lauf(befehl, input=f'user = "{konto}:{passwort}"\n',
                 capture_output=True, text=True, timeout=FTP_ZEITGRENZE_S)
    except subprocess.TimeoutExpired:
        return -1, "", "(Zeitgrenze)"
    return e.returncode, (e.stdout or ""), (e.stderr or "")


def ftp_adresse(server: str, pfad: str) -> str:
    return "ftp://" + server.rstrip("/") + "/" + pfad.lstrip("/")


def vorhanden(server: str, zielpfad: str, zustandspfad: str,
              konto: str, passwort: str,
              lauf=subprocess.run) -> bool | None:
    """Liegt die Zustandsdatei auf dem Server? Dreiwertig.

    GEPRUEFT WIRD DURCH AUFLISTEN, NICHT DURCH ABRUFEN. Ein `RETR` auf eine
    fehlende Datei ist genau die Operation, die den Fehler ausloest -- eine
    Pruefung, die ihn ausloest, um ihn zu vermeiden, waere ein Witz.

    `None` heisst: nicht feststellbar. Das Verzeichnis liess sich nicht
    auflisten; dann wird NICHT angelegt, denn eine vorhandene Datei zu
    ueberschreiben waere schlimmer als gar nichts zu tun.
    """
    verzeichnis, name = zerlegen(zustandspfad)
    voll = (zielpfad.rstrip("/") + "/" + verzeichnis).rstrip("/") + "/"
    rc, aus, _ = curl_ftp(["--ssl-reqd", "--list-only", ftp_adresse(server, voll)],
                          konto, passwort, lauf)
    if rc != 0:
        return None
    namen = [z.strip().rsplit("/", 1)[-1] for z in aus.splitlines() if z.strip()]
    return name in namen


def anlegen(server: str, zielpfad: str, zustandspfad: str,
            konto: str, passwort: str, lauf=subprocess.run,
            trocken: bool = False) -> int:
    """Prüfen und, wenn sie fehlt, anlegen. 0 = der Weg ist frei."""
    geheim = [passwort, konto]
    def a(text): sag(text, geheim)
    def f(text): sag(text, geheim, True)

    verzeichnis, name = zerlegen(zustandspfad)
    a(f"Zustandsdatei der Auslieferungsaktion: {zustandspfad}")
    a(f"  Zielverzeichnis: {zielpfad}")

    da = vorhanden(server, zielpfad, zustandspfad, konto, passwort, lauf)
    if da is None:
        f("NICHT FESTSTELLBAR: Das Verzeichnis liess sich nicht auflisten. "
          "Es wird NICHTS angelegt — eine vorhandene Zustandsdatei zu "
          "überschreiben wäre schlimmer, als nichts zu tun.")
        return 1
    if da:
        a("  Vorhanden. Es wird nichts angelegt und nichts angefasst — sie "
          "trägt den Bestand des Servers.")
        return 0

    a("  FEHLT. Ohne sie sendet die Aktion `RETR` auf einen Namen, den es "
      "nicht gibt, und die Verbindung stirbt (F-KH-U-25).")
    if trocken:
        a("  TROCKENLAUF — es wird nichts geschrieben.")
        return 0

    inhalt = zustand_json()
    quelle = os.path.join(os.environ.get("RUNNER_TEMP", "/tmp"), name)
    try:
        with open(quelle, "w", encoding="utf-8") as fh:
            fh.write(inhalt)
        voll = (zielpfad.rstrip("/") + "/" + verzeichnis).rstrip("/") + "/" + name
        rc, _, err = curl_ftp(["--ssl-reqd", "--upload-file", quelle,
                               ftp_adresse(server, voll)],
                              konto, passwort, lauf)
        if rc != 0:
            f(f"FEHLGESCHLAGEN beim Anlegen (curl {rc}).")
            for z in (err or "(keine)").splitlines()[-20:]:
                f(f"    {z}")
            return 1
    finally:
        try:
            os.unlink(quelle)
        except OSError:
            pass

    # NACHGEMESSEN, NICHT GEGLAUBT. Ein `curl`, das 0 zurueckgibt, sagt, dass
    # es gesendet hat -- nicht, dass die Datei liegt.
    nach = vorhanden(server, zielpfad, zustandspfad, konto, passwort, lauf)
    if nach is not True:
        f("FEHLGESCHLAGEN: Nach dem Hochladen ist sie NICHT in der Liste. "
          f"Nachgemessen, nicht geglaubt — Ergebnis: {nach!r}.")
        return 1
    a(f"  Angelegt und nachgemessen ({len(inhalt)} Byte, `data: []`).")
    a("  Der nächste Lauf der Aktion findet sie, überträgt alles und schreibt "
      "sie danach selbst fort.")
    return 0


def selbstprobe() -> int:
    """Tut es, was es sagt — ohne Netz?"""
    erfuellt = offen = 0
    import contextlib
    import io as _io

    def pruefe(b: bool, was: str, wert: str = "") -> None:
        nonlocal erfuellt, offen
        erfuellt, offen = (erfuellt + 1, offen) if b else (erfuellt, offen + 1)
        print(f"  [{'ok ' if b else 'FEHL'}] {was}" + (f"  {wert}" if wert else ""))

    print("Selbstprobe der Zustandsdatei — ohne Netz\n")

    d = json.loads(zustand_json(1758400000000))
    pruefe(d["data"] == [], "`data` ist leer — der Server gilt als leer")
    pruefe(d["version"] == "1.0.0", "Fassung wie in `types.js` der Aktion",
           d["version"])
    pruefe(d["description"].startswith("DO NOT DELETE THIS FILE."),
           "Der Warnkopf steht wortgleich darin")
    pruefe(d["generatedTime"] == 1758400000000,
           "Zeitstempel in Millisekunden", str(d["generatedTime"]))
    pruefe(isinstance(d["generatedTime"], int),
           "…und als Zahl, nicht als Zeichenkette")

    pruefe(zerlegen("../.deploy-state-produktion.json") == ("..", ".deploy-state-produktion.json"),
           "Pfad mit `../` wird richtig zerlegt",
           str(zerlegen("../.deploy-state-produktion.json")))
    pruefe(zerlegen(".deploy-state.json") == ("", ".deploy-state.json"),
           "…und einer ohne Verzeichnis auch")

    class Lauf:
        def __init__(self, liste="", rc_liste=0, rc_upload=0):
            self.liste, self.rc_liste, self.rc_upload = liste, rc_liste, rc_upload
            self.befehle: list[list[str]] = []
            self.uploads = 0

        def __call__(self, befehl, **kw):
            self.befehle.append(befehl)

            class E:
                pass
            e = E()
            if "--list-only" in befehl:
                e.returncode, e.stdout, e.stderr = self.rc_liste, self.liste, ""
            else:
                self.uploads += 1
                e.returncode, e.stdout, e.stderr = self.rc_upload, "", "550 nope"
                if self.rc_upload == 0:
                    for x in befehl:
                        t = str(x)
                        if t.startswith("ftp://"):
                            drin = [y for y in self.liste.splitlines() if y.strip()]
                            self.liste = "\n".join(drin + [t.rsplit("/", 1)[-1]])
            return e

    # Vorhanden -> NICHTS anfassen. Die wichtigste Lage von allen: Eine
    # ueberschriebene Zustandsdatei hiesse „der Server ist leer" und loeste
    # eine Voll-Uebertragung aus.
    l = Lauf(liste=".deploy-state-produktion.json\nindex.php\n")
    p = _io.StringIO()
    with contextlib.redirect_stdout(p):
        rc = anlegen("h", "/", "../.deploy-state-produktion.json", "k", "passwort", l)
    pruefe(rc == 0, "Vorhandene Datei: Rückgabe 0")
    pruefe(l.uploads == 0, "…und sie wird NICHT überschrieben", f"{l.uploads} Upload(s)")
    pruefe("nichts angefasst" in p.getvalue(), "…und das wird gesagt")

    # Fehlt -> anlegen, und danach nachmessen.
    l2 = Lauf(liste="index.php\n")
    p2 = _io.StringIO()
    with contextlib.redirect_stdout(p2):
        rc2 = anlegen("h", "/", "../.deploy-state-produktion.json", "k", "passwort", l2)
    pruefe(rc2 == 0, "Fehlende Datei: wird angelegt")
    pruefe(l2.uploads == 1, "…mit genau einem Upload", f"{l2.uploads}")
    pruefe(l2.befehle.count([b for b in l2.befehle if "--list-only" in b][0]) >= 1
           and len([b for b in l2.befehle if "--list-only" in b]) == 2,
           "…und es wird VORHER und NACHHER aufgelistet — nachgemessen, "
           "nicht geglaubt",
           f"{len([b for b in l2.befehle if '--list-only' in b])} Auflistungen")

    # Auflisten scheitert -> NICHTS tun. Dreiwertig.
    l3 = Lauf(rc_liste=9)
    p3 = _io.StringIO()
    with contextlib.redirect_stderr(p3):
        rc3 = anlegen("h", "/", "../x.json", "k", "passwort", l3)
    pruefe(rc3 == 1, "Auflisten scheitert → rot")
    pruefe(l3.uploads == 0,
           "…und es wird NICHTS angelegt (eine vorhandene zu überschreiben "
           "wäre schlimmer)", f"{l3.uploads} Upload(s)")
    pruefe("NICHT FESTSTELLBAR" in p3.getvalue(),
           "…und es heißt NICHT FESTSTELLBAR, nicht FEHLT")

    # Hochladen scheitert -> rot, mit Servermeldung.
    l4 = Lauf(liste="index.php\n", rc_upload=7)
    p4 = _io.StringIO()
    with contextlib.redirect_stdout(_io.StringIO()), contextlib.redirect_stderr(p4):
        rc4 = anlegen("h", "/", "../x.json", "k", "passwort", l4)
    pruefe(rc4 == 1, "Hochladen scheitert → rot")
    pruefe("550" in p4.getvalue(), "…und die Servermeldung steht da")

    # Trockenlauf schreibt nichts.
    l5 = Lauf(liste="index.php\n")
    with contextlib.redirect_stdout(_io.StringIO()):
        rc5 = anlegen("h", "/", "../x.json", "k", "passwort", l5, trocken=True)
    pruefe(rc5 == 0 and l5.uploads == 0,
           "Trockenlauf: meldet den Befund und schreibt nichts")

    # Das Passwort steht nicht in der Befehlszeile.
    l6 = Lauf()
    curl_ftp(["--ssl-reqd", "ftp://h/"], "k", "streng-geheim", l6)
    pruefe(not any("streng-geheim" in str(x) for b in l6.befehle for x in b),
           "Das Passwort steht in keinem Befehlszeilenargument")

    print(f"\n  -> {erfuellt + offen} Lagen, {offen} nicht erfüllt")
    return 0 if offen == 0 else 1


def main(argv: list[str]) -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--ftp-server")
    p.add_argument("--ftp-konto")
    p.add_argument("--ftp-pass")
    p.add_argument("--ftp-pfad", default="/",
                   help="Zielverzeichnis der Auslieferung (wie `server-dir`)")
    p.add_argument("--zustandspfad", default="../.deploy-state-produktion.json",
                   help="wie `state-name` der Aktion, relativ zum Zielverzeichnis")
    p.add_argument("--trocken", action="store_true",
                   help="nur prüfen und sagen, nichts schreiben")
    p.add_argument("--selbstprobe", action="store_true")
    a = p.parse_args(argv)

    if a.selbstprobe:
        return selbstprobe()
    fehlt = [n for n, v in (("--ftp-server", a.ftp_server),
                            ("--ftp-konto", a.ftp_konto),
                            ("--ftp-pass", a.ftp_pass)) if not v]
    if fehlt:
        print("Pflicht: " + ", ".join(fehlt), file=sys.stderr)
        return 2
    return anlegen(a.ftp_server, a.ftp_pfad, a.zustandspfad,
                   a.ftp_konto, a.ftp_pass, trocken=a.trocken)


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
