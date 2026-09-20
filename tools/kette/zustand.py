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

# `curl`-RUECKGABEWERTE, DIE „DIE DATEI GIBT ES NICHT" HEISSEN -- und nur die.
# 78 = CURLE_REMOTE_FILE_NOT_FOUND, 19 = CURLE_FTP_COULDNT_RETR_FILE.
# Jeder andere Wert heisst NICHT „fehlt", sondern „nicht feststellbar": Ein
# Netzfehler, der als „fehlt" durchginge, liesse das Werkzeug eine vorhandene
# Zustandsdatei ueberschreiben.
CURL_NICHT_DA = (19, 78)


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

    GEFRAGT WIRD MIT `--head`, ALSO `SIZE`/`MDTM` AUF DEM STEUERKANAL.
    Kein `RETR` -- das ist genau die Operation, die den Fehler ausloest, und
    eine Pruefung, die ihn ausloest, um ihn zu vermeiden, waere ein Witz.
    Und **keine Datenverbindung**, also auch kein Datenkanal, der sterben
    koennte.

    AUFGELISTET WIRD NICHT MEHR, UND DAS IST EINE BEHEBUNG (20.09.2026).
    Die erste Fassung fragte per `--list-only`, also `NLST` -- und **`NLST`
    zeigt Punktdateien nicht**. Die Zustandsdatei heisst
    `.deploy-state-…json` und faengt mit einem Punkt an. Die Folge waere
    nicht bloss ein Fehlalarm gewesen: Das Werkzeug haette auf dem
    Produktivserver **immer** „fehlt" gemeldet, auch wenn die Datei liegt --
    und sie dann ueberschrieben. Genau der Schaden, vor dem Vorsicht 1
    schuetzen soll. Gefunden hat es die Nachmessung, im Lauf 35544269232.

    `None` heisst: nicht feststellbar. Dann wird NICHT angelegt, denn eine
    vorhandene Datei zu ueberschreiben waere schlimmer als gar nichts zu tun.
    """
    verzeichnis, name = zerlegen(zustandspfad)
    voll = (zielpfad.rstrip("/") + "/" + verzeichnis).rstrip("/") + "/" + name
    rc, _, _ = curl_ftp(["--ssl-reqd", "--head", ftp_adresse(server, voll)],
                        konto, passwort, lauf)
    if rc == 0:
        return True
    if rc in CURL_NICHT_DA:
        return False
    return None


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
        f('NICHT FESTSTELLBAR: Die Abfrage hat weder „da" noch „nicht da" '
          'ergeben. Es wird NICHTS angelegt — eine vorhandene Zustandsdatei '
          'zu überschreiben wäre schlimmer, als nichts zu tun.')
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
        """Attrappe eines Servers, der eine Dateiliste führt.

        `da` ist die Menge der Dateien, die er hat. `--head` antwortet
        danach; ein gelungener Upload legt die Datei hinein. So sieht ein
        Aufraeumen, das nichts tut, NICHT aus wie eines, das etwas tut.
        """

        def __init__(self, da=(), rc_head=None, rc_upload=0):
            self.da = set(da)
            self.rc_head, self.rc_upload = rc_head, rc_upload
            self.befehle: list[list[str]] = []
            self.uploads = self.heads = 0

        def _name(self, befehl):
            for x in befehl:
                t = str(x)
                if t.startswith("ftp://"):
                    return t.rsplit("/", 1)[-1]
            return ""

        def __call__(self, befehl, **kw):
            self.befehle.append(befehl)

            class E:
                pass
            e = E()
            if "--head" in befehl:
                self.heads += 1
                if self.rc_head is not None:
                    e.returncode = self.rc_head
                else:
                    e.returncode = 0 if self._name(befehl) in self.da else 78
                e.stdout, e.stderr = "", ""
            else:
                self.uploads += 1
                e.returncode, e.stdout, e.stderr = self.rc_upload, "", "550 nope"
                if self.rc_upload == 0:
                    self.da.add(self._name(befehl))
            return e

    # Vorhanden -> NICHTS anfassen. Die wichtigste Lage von allen: Eine
    # ueberschriebene Zustandsdatei hiesse „der Server ist leer" und loeste
    # eine Voll-Uebertragung von 688 Dateien aus.
    l = Lauf(da={".deploy-state-produktion.json", "index.php"})
    p = _io.StringIO()
    with contextlib.redirect_stdout(p):
        rc = anlegen("h", "/", "../.deploy-state-produktion.json", "k", "passwort", l)
    pruefe(rc == 0, "Vorhandene Datei: Rückgabe 0")
    pruefe(l.uploads == 0, "…und sie wird NICHT überschrieben", f"{l.uploads} Upload(s)")
    pruefe("nichts angefasst" in p.getvalue(), "…und das wird gesagt")

    # DIE LAGE, DIE AM 20.09.2026 GEFEHLT HAT (Lauf 35544269232).
    # Die erste Fassung fragte per `--list-only`, also `NLST` -- und `NLST`
    # zeigt PUNKTDATEIEN NICHT. Die Zustandsdatei faengt mit einem Punkt an.
    # Das Werkzeug haette auf dem Produktivserver IMMER „fehlt" gemeldet,
    # auch wenn die Datei liegt, und sie dann ueberschrieben. Diese Lage
    # haelt fest, dass nicht mehr aufgelistet wird.
    pruefe(not any("--list-only" in b for b in l.befehle),
           "Punktdateien-Falle: es wird NICHT mehr aufgelistet (`NLST` zeigt "
           "sie nicht)")
    pruefe(all("--head" in b for b in l.befehle),
           "…gefragt wird mit `--head`, also `SIZE`/`MDTM` auf dem Steuerkanal")
    pruefe(not any("RETR" in str(x) for b in l.befehle for x in b),
           "…und nie mit `RETR` — das ist die Operation, die den Fehler "
           "auslöst")
    pruefe(any(str(x).endswith("/.deploy-state-produktion.json")
               for b in l.befehle for x in b),
           "…und gefragt wird nach der DATEI, nicht nach dem Verzeichnis")

    # Fehlt -> anlegen, und danach nachmessen.
    l2 = Lauf(da={"index.php"})
    p2 = _io.StringIO()
    with contextlib.redirect_stdout(p2):
        rc2 = anlegen("h", "/", "../.deploy-state-produktion.json", "k", "passwort", l2)
    pruefe(rc2 == 0, "Fehlende Datei: wird angelegt")
    pruefe(l2.uploads == 1, "…mit genau einem Upload", f"{l2.uploads}")
    pruefe(l2.heads == 2,
           "…und VORHER und NACHHER gefragt — nachgemessen, nicht geglaubt",
           f"{l2.heads} Abfragen")

    # DIE GEGENPROBE ZUR NACHMESSUNG: Der Upload meldet 0, die Datei liegt
    # aber nicht. Genau das ist im Lauf 35544269232 passiert (aus dem
    # falschen Grund) -- und die Nachmessung hat es gemeldet, statt
    # „angelegt" zu behaupten.
    class LaufStiller(Lauf):
        def __call__(self, befehl, **kw):
            e = super().__call__(befehl, **kw)
            if "--head" not in befehl:
                self.da.discard(self._name(befehl))   # der Upload verpufft
            return e

    l2b = LaufStiller(da={"index.php"})
    pb = _io.StringIO()
    with contextlib.redirect_stdout(_io.StringIO()), contextlib.redirect_stderr(pb):
        rcb = anlegen("h", "/", "../x.json", "k", "passwort", l2b)
    pruefe(rcb == 1,
           'Upload meldet 0, Datei liegt nicht → rot statt „angelegt"')
    pruefe("Nachgemessen, nicht geglaubt" in pb.getvalue(),
           "…und die Meldung sagt, woran es gemerkt wurde")

    # Nicht feststellbar -> NICHTS tun. Dreiwertig, und die Unterscheidung
    # ist der ganze Schutz: Ein Netzfehler, der als „fehlt" durchginge,
    # ueberschriebe eine vorhandene Datei.
    l3 = Lauf(rc_head=9)
    p3 = _io.StringIO()
    with contextlib.redirect_stderr(p3):
        rc3 = anlegen("h", "/", "../x.json", "k", "passwort", l3)
    pruefe(rc3 == 1, "Abfrage scheitert mit unbekanntem Wert → rot")
    pruefe(l3.uploads == 0,
           "…und es wird NICHTS angelegt (eine vorhandene zu überschreiben "
           "wäre schlimmer)", f"{l3.uploads} Upload(s)")
    pruefe("NICHT FESTSTELLBAR" in p3.getvalue(),
           "…und es heißt NICHT FESTSTELLBAR, nicht FEHLT")
    for wert in CURL_NICHT_DA:
        lx = Lauf(rc_head=wert)
        with contextlib.redirect_stdout(_io.StringIO()):
            anlegen("h", "/", "../x.json", "k", "passwort", lx)
        pruefe(lx.uploads == 1,
               f"…`curl {wert}` heißt FEHLT und löst das Anlegen aus",
               f"{lx.uploads} Upload(s)")

    # Hochladen scheitert -> rot, mit Servermeldung.
    l4 = Lauf(da={"index.php"}, rc_upload=7)
    p4 = _io.StringIO()
    with contextlib.redirect_stdout(_io.StringIO()), contextlib.redirect_stderr(p4):
        rc4 = anlegen("h", "/", "../x.json", "k", "passwort", l4)
    pruefe(rc4 == 1, "Hochladen scheitert → rot")
    pruefe("550" in p4.getvalue(), "…und die Servermeldung steht da")

    # Trockenlauf schreibt nichts.
    l5 = Lauf(da={"index.php"})
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
