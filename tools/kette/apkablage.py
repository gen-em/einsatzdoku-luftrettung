#!/usr/bin/env python3
"""APK-Ablage — ein signiertes APK nach `server/apk/` legen, per FTPS (PK-08).

    python3 tools/kette/apkablage.py --art handy|uhr --datei nadoku-X.Y.Z.apk \\
            --ftp-server HOST --ftp-konto NAME --ftp-pass … [--ftp-pfad /]
            [--probelauf] [--zusammenfassung DATEI]
    python3 tools/kette/apkablage.py --selbstprobe

Rückgabewert 0 = abgelegt (bzw. im Probelauf: Weg gemessen), 1 = nicht,
2 = Bedienfehler.

Anlass: Nr. 100 (die App-Auslieferung lief von Hand), E-PK-23, E-PK-58/-59.

DIE REIHENFOLGE IST DER RIEGEL. Hochladen unter `<name>.teil`, zurückholen,
SHA-256 vergleichen, erst dann auf den endgültigen Namen umbenennen — und
erst NACH dem Umbenennen die älteren Fassungen desselben Musters löschen
(E-PK-59: nur die neueste bleibt liegen). Scheitert irgendein Schritt davor,
wird nichts gelöscht: Lieber zwei Fassungen im Ordner als keine.

`apk_liste()` (`server/apk_lib.php`) zeigt nur Namen auf `*.apk`; ein
`…apk.teil` erscheint deshalb nie auf dem Geräte-Reiter, auch nicht halb.

ZWEI MUSTER, UND KEINES FASST DAS ANDERE AN. `handy` ist
`nadoku-X.Y.Z.apk`, `uhr` ist `nadoku-uhr-X.Y.Z.apk` (Wear OS, E-PK-58).
Was im Ordner sonst liegt, bleibt liegen.

DER FTPS-WEG IST DER DER ZIELPROBE: `curl` mit `--ssl-reqd`, Passwort über
`--config -`, jede Ausgabe maskiert (`zielprobe.py`, dort begründet).
"""
from __future__ import annotations

import argparse
import hashlib
import os
import re
import sys
import tempfile

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from zielprobe import (curl_da, curl_ftp, ftp_adresse,  # noqa: E402
                       maskieren, sag)

MUSTER = {
    "handy": re.compile(r"^nadoku-\d+\.\d+\.\d+\.apk$"),
    "uhr": re.compile(r"^nadoku-uhr-\d+\.\d+\.\d+\.apk$"),
}
TEIL = ".teil"
# Ein APK hat rund 10 bis 25 MB; über FTPS beim Hoster ist das eine Frage
# von Sekunden. Fünf Minuten begrenzen das Hängen, nicht die Übertragung.
ZEITGRENZE_S = 300


def sha256(pfad: str) -> str:
    h = hashlib.sha256()
    with open(pfad, "rb") as f:
        for stueck in iter(lambda: f.read(1 << 16), b""):
            h.update(stueck)
    return h.hexdigest()


def apk_ordner(pfad: str) -> str:
    """FTP-Pfad von `server/apk/`: Zielpfad der Umgebung plus `apk`."""
    return "/" + "/".join(t for t in (pfad.strip("/"), "apk") if t)


def liste(server, ordner, konto, pw, lauf) -> tuple[int, list[str], str]:
    rc, aus, err = curl_ftp(["-sS", "--ssl-reqd", "--list-only",
                             ftp_adresse(server, ordner) ], konto, pw, lauf,
                            ZEITGRENZE_S)
    namen = [z.strip().rsplit("/", 1)[-1] for z in aus.splitlines() if z.strip()]
    return rc, namen, err


def befehle(server, ordner, konto, pw, lauf, kommandos: list[str]):
    """Mehrere FTP-Befehle NACH einer Auflistung, in einer Sitzung."""
    arg = ["-sS", "--ssl-reqd", "--list-only"]
    for k in kommandos:
        arg += ["-Q", "-" + k]
    return curl_ftp([*arg, ftp_adresse(server, ordner)], konto, pw, lauf,
                    ZEITGRENZE_S)


def ablegen(art: str, datei: str, server: str, pfad: str, konto: str, pw: str,
            probelauf: bool = False, lauf=None, zusammenfassung: str | None = None
            ) -> int:
    import subprocess
    lauf = lauf or subprocess.run
    geh = [pw, konto]
    name = os.path.basename(datei)
    if art not in MUSTER:
        sag(f"ABBRUCH: unbekannte Art '{art}' — erlaubt sind handy und uhr.", geh, True)
        return 2
    if not MUSTER[art].match(name):
        sag(f"ABBRUCH: '{name}' passt nicht zum Muster der Art {art} "
            f"({MUSTER[art].pattern}). Die Fassung liest die Geräteseite aus "
            f"dem Namen; ein anderer Name wäre dort eine Datei ohne Fassung.",
            geh, True)
        return 2
    if not os.path.isfile(datei):
        sag(f"ABBRUCH: {datei} gibt es nicht.", geh, True)
        return 2

    ordner = apk_ordner(pfad)
    soll = sha256(datei)
    groesse = os.path.getsize(datei)
    sag(f"APK-Ablage ({art}): {name}, {groesse} Byte, SHA-256 {soll}", geh)
    sag(f"Ziel: ftp://…{ordner}/", geh)

    if probelauf:
        rc, namen, err = liste(server, ordner, konto, pw, lauf)
        if rc not in (0, 9):         # 9 = Verzeichnis gibt es (noch) nicht
            sag(f"PROBELAUF: Auflistung von {ordner} gescheitert (curl {rc}): "
                f"{err.strip()}", geh, True)
            return 1
        alt = sorted(n for n in namen if MUSTER[art].match(n) and n != name)
        sag(f"PROBELAUF — nichts abgelegt, nichts gelöscht. Im Ordner jetzt: "
            f"{', '.join(sorted(namen)) or '(leer oder fehlt)'}", geh)
        sag(f"Würde ablegen: {name}; würde danach löschen: "
            f"{', '.join(alt) or 'nichts'}", geh)
        schreibe(zusammenfassung, art, name, groesse, soll, [], alt, True)
        return 0

    # 1. HOCHLADEN UNTER `.teil`
    teil = name + TEIL
    rc, _, err = curl_ftp(["-sS", "--ssl-reqd", "--ftp-create-dirs", "--upload-file", datei,
                           ftp_adresse(server, ordner, teil)], konto, pw, lauf,
                          ZEITGRENZE_S)
    if rc != 0:
        sag(f"Hochladen gescheitert (curl {rc}): {err.strip()} — NICHTS gelöscht.",
            geh, True)
        return 1

    # 2. ZURÜCKHOLEN UND VERGLEICHEN
    with tempfile.TemporaryDirectory() as tmp:
        zurueck = os.path.join(tmp, "zurueck.apk")
        rc, _, err = curl_ftp(["-sS", "--ssl-reqd", "-o", zurueck,
                               ftp_adresse(server, ordner, teil)], konto, pw, lauf,
                              ZEITGRENZE_S)
        ist = sha256(zurueck) if rc == 0 and os.path.isfile(zurueck) else ""
    if ist != soll:
        befehle(server, ordner, konto, pw, lauf, [f"DELE {ordner}/{teil}"])
        sag(f"Die Datei auf dem Server ist NICHT die hochgeladene (SHA-256 "
            f"{ist or 'nicht lesbar, curl ' + str(rc)} statt {soll}). "
            f"`.teil` entfernt, NICHTS gelöscht, nichts umbenannt.", geh, True)
        return 1
    sag("Zurückgeholt, SHA-256 gleich.", geh)

    # 3. UMBENENNEN — eine gleichnamige Datei (derselbe Tag noch einmal)
    #    geht vorher weg, sonst schlägt RNTO bei manchen Servern fehl.
    rc, namen, err = liste(server, ordner, konto, pw, lauf)
    kommandos = [f"DELE {ordner}/{name}"] if name in namen else []
    kommandos += [f"RNFR {ordner}/{teil}", f"RNTO {ordner}/{name}"]
    rc, _, err = befehle(server, ordner, konto, pw, lauf, kommandos)
    rc_l, namen, err_l = liste(server, ordner, konto, pw, lauf)
    if name not in namen or teil in namen:
        sag(f"Umbenennen gescheitert (curl {rc}): {err.strip()} — im Ordner: "
            f"{', '.join(sorted(namen))}. NICHTS gelöscht.", geh, True)
        return 1
    sag(f"Liegt: {ordner}/{name}", geh)

    # 4. ERST JETZT: ÄLTERE FASSUNGEN DESSELBEN MUSTERS (E-PK-59)
    alt = sorted(n for n in namen if n != name and (
        MUSTER[art].match(n) or (n.endswith(TEIL) and MUSTER[art].match(n[:-len(TEIL)]))))
    if alt:
        rc, _, err = befehle(server, ordner, konto, pw, lauf,
                             [f"DELE {ordner}/{n}" for n in alt])
        _, namen, _ = liste(server, ordner, konto, pw, lauf)
        uebrig = [n for n in alt if n in namen]
        if uebrig:
            sag(f"{name} LIEGT, aber {len(uebrig)} ältere Fassung(en) ließen sich "
                f"nicht löschen (curl {rc}: {err.strip()}): {', '.join(uebrig)}. "
                f"Von Hand per FTP entfernen.", geh, True)
            schreibe(zusammenfassung, art, name, groesse, soll,
                     [n for n in alt if n not in uebrig], uebrig, False)
            return 1
    sag(f"Gelöscht: {', '.join(alt) or 'nichts (keine ältere Fassung)'}", geh)
    sag(f"Im Ordner jetzt: {', '.join(sorted(namen))}", geh)
    schreibe(zusammenfassung, art, name, groesse, soll, alt, [], False)
    return 0


def schreibe(ziel, art, name, groesse, sha, geloescht, rest, probelauf) -> None:
    if not ziel:
        return
    gruppen = " ".join(sha[i:i + 4] for i in range(0, len(sha), 4))
    with open(ziel, "a", encoding="utf-8") as f:
        f.write(f"## APK-Ablage ({art}){' — PROBELAUF, nichts abgelegt' if probelauf else ''}\n\n")
        f.write("| | |\n|---|---|\n")
        f.write(f"| Datei | `{name}` |\n| Größe | {groesse} Byte |\n")
        f.write(f"| SHA-256 | `{gruppen}` |\n")
        wort = "würde löschen" if probelauf else "gelöscht"
        f.write(f"| {wort} | {', '.join(f'`{n}`' for n in (rest if probelauf else geloescht)) or '—'} |\n")
        if rest and not probelauf:
            f.write(f"| **nicht gelöscht** | {', '.join(f'`{n}`' for n in rest)} |\n")
        f.write("\nDie SHA-256 steht auch auf dem Geräte-Reiter; beide müssen gleich sein.\n\n")


# --------------------------------------------------------------- Selbstprobe
class Server:
    """Stellt `subprocess.run` für `curl` gegen EIN FTP-Verzeichnis nach.

    Sie führt Buch über den Ordner — gelöschte und umbenannte Namen
    verschwinden wirklich —, damit die Ablage nachmisst, statt dem
    Rückgabewert zu glauben (dieselbe Lehre wie in `zielprobe.py`).
    """

    def __init__(self, dateien=None, kaputt=False, rc_hoch=0, rc_dele=0):
        self.dateien = dict(dateien or {})
        self.kaputt, self.rc_hoch, self.rc_dele = kaputt, rc_hoch, rc_dele
        self.protokoll: list[str] = []
        self.eingaben: list[str] = []
        self.argumente: list[str] = []

    def __call__(self, befehl, input=None, **kw):
        self.eingaben.append(input or "")
        self.argumente += [str(x) for x in befehl]

        class E:
            pass
        e = E()
        e.returncode, e.stdout, e.stderr = 0, "", ""
        b = [str(x) for x in befehl]
        adresse = b[-1]
        name = adresse.rsplit("/", 1)[-1]
        if "--upload-file" in b:
            self.protokoll.append(f"HOCH {name}")
            if self.rc_hoch:
                e.returncode, e.stderr = self.rc_hoch, "550 kein Platz"
                return e
            with open(b[b.index("--upload-file") + 1], "rb") as f:
                self.dateien[name] = f.read()
            return e
        if "-o" in b:
            self.protokoll.append(f"HOL {name}")
            inhalt = self.dateien.get(name, b"")
            if self.kaputt:
                inhalt = inhalt[:-1] + bytes([inhalt[-1] ^ 1]) if inhalt else b"x"
            with open(b[b.index("-o") + 1], "wb") as f:
                f.write(inhalt)
            return e
        if "--list-only" in b:
            von = None
            for i, x in enumerate(b):
                if x == "-Q":
                    k = b[i + 1].lstrip("-")
                    wort, pfad = k.split(" ", 1)
                    n = pfad.rsplit("/", 1)[-1]
                    self.protokoll.append(f"{wort} {n}")
                    if wort == "DELE":
                        if self.rc_dele:
                            e.returncode, e.stderr = self.rc_dele, "550 gesperrt"
                            break
                        self.dateien.pop(n, None)
                    elif wort == "RNFR":
                        von = n
                    elif wort == "RNTO" and von in self.dateien:
                        self.dateien[n] = self.dateien.pop(von)
            if not any(x == "-Q" for x in b):
                self.protokoll.append("LISTE")
            e.stdout = "\n".join(sorted(self.dateien))
            return e
        e.returncode = 2
        return e


def selbstprobe() -> int:
    import contextlib
    import io
    erfuellt = offen = 0

    def pruefe(b: bool, was: str, wert: str = "") -> None:
        nonlocal erfuellt, offen
        erfuellt, offen = (erfuellt + 1, offen) if b else (erfuellt, offen + 1)
        print(f"  [{'ok ' if b else 'FEHL'}] {was}" + (f"  {wert}" if wert else ""))

    print("Selbstprobe der APK-Ablage — ohne Netz\n")
    for n, art, soll in (("nadoku-0.17.0.apk", "handy", True),
                         ("nadoku-uhr-0.17.0.apk", "handy", False),
                         ("nadoku-uhr-0.17.0.apk", "uhr", True),
                         ("nadoku-0.17.0.apk", "uhr", False),
                         ("nadoku-0.17.apk", "handy", False),
                         ("../nadoku-0.17.0.apk", "handy", False),
                         ("nadoku-0.17.0.apk.teil", "handy", False)):
        pruefe(bool(MUSTER[art].match(n)) is soll, f"Muster {art}: {n} → {soll}")
    pruefe(apk_ordner("/") == "/apk" and apk_ordner("/web/") == "/web/apk",
           "Ordner: Zielpfad plus apk", f"{apk_ordner('/')} · {apk_ordner('/web/')}")

    tmp = tempfile.mkdtemp()
    neu = os.path.join(tmp, "nadoku-0.17.0.apk")
    with open(neu, "wb") as f:
        f.write(os.urandom(4096))
    geheim = "streng-geheim!"

    def fahre(server, **kw):
        aus = io.StringIO()
        with contextlib.redirect_stdout(aus), contextlib.redirect_stderr(aus):
            rc = ablegen(kw.pop("art", "handy"), kw.pop("datei", neu), "ftp.example",
                         "/", "konto", geheim, lauf=server, **kw)
        return rc, aus.getvalue()

    # Der gute Weg
    s = Server({"nadoku-0.16.0.apk": b"alt", "nadoku-0.15.0.apk.teil": b"rest",
                "nadoku-uhr-0.16.0.apk": b"uhr", "liesmich.txt": b"x"})
    rc, aus = fahre(s)
    pruefe(rc == 0, "Guter Weg: Rückgabewert 0", str(rc))
    pruefe(sorted(s.dateien) == ["liesmich.txt", "nadoku-0.17.0.apk", "nadoku-uhr-0.16.0.apk"],
           "Danach liegen: neue Handy-Fassung, Uhr-Fassung, fremde Datei — sonst nichts",
           ", ".join(sorted(s.dateien)))
    pruefe(s.dateien.get("nadoku-0.17.0.apk") == open(neu, "rb").read(),
           "Die abgelegte Datei ist Byte für Byte die hochgeladene")
    p = s.protokoll
    erste_dele = next((i for i, x in enumerate(p) if x.startswith("DELE nadoku-0.16")), 99)
    pruefe(p.index("RNTO nadoku-0.17.0.apk") < erste_dele,
           "Gelöscht wird ERST NACH dem Umbenennen", " → ".join(p))
    pruefe(p.index("HOL nadoku-0.17.0.apk.teil") < p.index("RNFR nadoku-0.17.0.apk.teil"),
           "Umbenannt wird ERST NACH dem Vergleich")
    pruefe(geheim not in aus and all(geheim not in x for x in s.protokoll),
           "Das Passwort steht in keiner Ausgabe")
    pruefe(not any(geheim in x for x in s.argumente) and any(geheim in e for e in s.eingaben),
           "…steht in keinem Befehlsargument und geht nur über `--config -` an curl")

    # Die Uhr fasst das Handy nicht an
    uhr = os.path.join(tmp, "nadoku-uhr-0.17.0.apk")
    with open(uhr, "wb") as f:
        f.write(os.urandom(2048))
    s = Server({"nadoku-0.16.0.apk": b"alt", "nadoku-uhr-0.16.0.apk": b"uhr"})
    rc, _ = fahre(s, art="uhr", datei=uhr)
    pruefe(rc == 0 and sorted(s.dateien) == ["nadoku-0.16.0.apk", "nadoku-uhr-0.17.0.apk"],
           "Art uhr löscht nur Uhr-Fassungen", ", ".join(sorted(s.dateien)))

    # Kaputt zurückgeholt → nichts gelöscht
    s = Server({"nadoku-0.16.0.apk": b"alt"}, kaputt=True)
    rc, aus = fahre(s)
    pruefe(rc == 1, "Abweichende Prüfsumme: Rückgabewert 1", str(rc))
    pruefe(sorted(s.dateien) == ["nadoku-0.16.0.apk"],
           "…die alte Fassung bleibt, `.teil` ist weg, nichts umbenannt",
           ", ".join(sorted(s.dateien)))

    # Hochladen scheitert → nichts gelöscht
    s = Server({"nadoku-0.16.0.apk": b"alt"}, rc_hoch=25)
    rc, _ = fahre(s)
    pruefe(rc == 1 and sorted(s.dateien) == ["nadoku-0.16.0.apk"]
           and not any(x.startswith("DELE") for x in s.protokoll),
           "Hochladen gescheitert: rot, kein einziges DELE")

    # Derselbe Tag noch einmal
    s = Server({"nadoku-0.17.0.apk": b"vorher", "nadoku-0.16.0.apk": b"alt"})
    rc, _ = fahre(s)
    pruefe(rc == 0 and sorted(s.dateien) == ["nadoku-0.17.0.apk"]
           and s.dateien["nadoku-0.17.0.apk"] == open(neu, "rb").read(),
           "Derselbe Tag noch einmal: genau eine Datei, die neue")

    # Löschen scheitert → rot, aber die neue liegt
    s = Server({"nadoku-0.16.0.apk": b"alt"}, rc_dele=21)
    rc, aus = fahre(s)
    pruefe(rc == 1 and "nadoku-0.17.0.apk" in s.dateien and "nadoku-0.16.0.apk" in s.dateien,
           "Alte Fassung nicht löschbar: rot, die neue liegt trotzdem",
           ", ".join(sorted(s.dateien)))
    pruefe("Von Hand" in aus, "…und die Meldung sagt, was zu tun ist")

    # Probelauf
    s = Server({"nadoku-0.16.0.apk": b"alt"})
    rc, aus = fahre(s, probelauf=True)
    pruefe(rc == 0 and sorted(s.dateien) == ["nadoku-0.16.0.apk"]
           and not any(x.startswith(("HOCH", "DELE", "RN")) for x in s.protokoll),
           "Probelauf: nichts hochgeladen, nichts gelöscht, nichts umbenannt")
    pruefe("nadoku-0.16.0.apk" in aus, "…und er nennt, was er löschen würde")

    # Falscher Name → Bedienfehler, kein Kontakt
    s = Server({})
    rc, _ = fahre(s, datei=uhr)
    pruefe(rc == 2 and not s.protokoll, "Uhr-Datei als handy abgelegt: Bedienfehler, kein FTP")

    print(f"\n{erfuellt} erfüllt, {offen} nicht erfüllt")
    return 0 if offen == 0 else 1


def main(argv: list[str]) -> int:
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--art", choices=sorted(MUSTER))
    p.add_argument("--datei")
    p.add_argument("--ftp-server")
    p.add_argument("--ftp-konto")
    p.add_argument("--ftp-pass")
    p.add_argument("--ftp-pfad", default="/")
    p.add_argument("--probelauf", action="store_true",
                   help="nur auflisten und sagen, was geschähe")
    p.add_argument("--zusammenfassung", help="Datei, an die eine Markdown-Tabelle angehängt wird")
    p.add_argument("--selbstprobe", action="store_true")
    a = p.parse_args(argv)
    if a.selbstprobe:
        return selbstprobe()
    fehlt = [n for n, v in (("--art", a.art), ("--datei", a.datei),
                            ("--ftp-server", a.ftp_server), ("--ftp-konto", a.ftp_konto),
                            ("--ftp-pass", a.ftp_pass)) if not v]
    if fehlt:
        print("Pflicht: " + ", ".join(fehlt), file=sys.stderr)
        return 2
    if not curl_da():
        print("ABBRUCH: `curl` fehlt auf diesem Läufer.", file=sys.stderr)
        return 2
    return ablegen(a.art, a.datei, a.ftp_server, a.ftp_pfad, a.ftp_konto, a.ftp_pass,
                   a.probelauf, zusammenfassung=a.zusammenfassung)


if __name__ == "__main__":
    try:
        sys.exit(main(sys.argv[1:]))
    except Exception as ex:                          # noqa: BLE001
        print(f"Die APK-Ablage selbst ist gescheitert: {maskieren(str(ex), [])}",
              file=sys.stderr)
        sys.exit(2)
