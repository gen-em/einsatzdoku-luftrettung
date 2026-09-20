#!/usr/bin/env python3
"""Zielprobe — liegt eine hochgeladene Datei hinterher wirklich im Web?

    python3 tools/kette/zielprobe.py --basis https://nadoku.example \\
            --ftp-server HOST --ftp-konto NAME --ftp-pass … [--ftp-pfad /]
    python3 tools/kette/zielprobe.py … --ohne-sitzungswiederverwendung
    python3 tools/kette/zielprobe.py --selbstprobe

Rueckgabewert 0 = Rundlauf gelungen, 1 = nicht, 2 = Bedienfehler.

WOGEGEN SIE GEBAUT IST (F4, E-KH-07)
Die Kette hat bis zum 20.09.2026 geglaubt, was ihr die FTP-Aktion sagte. Ein
gruener Upload-Schritt heisst aber nur: Die Bibliothek hat keinen Fehler
gemeldet. Er heisst NICHT, dass die Dateien unter der Adresse liegen, die
`PRODUKTION_URL` nennt -- ein falscher Zielpfad, ein zweiter Webspace, ein
Konto, das woandershin eingesperrt ist, sehen von innen genauso aus. Die
Zielprobe schreibt deshalb eine Datei mit Zufallsnamen und Zufallsinhalt ins
Zielverzeichnis, holt sie ueber HTTPS zurueck, vergleicht Byte fuer Byte,
loescht sie und prueft das Loeschen (danach 404). Das belegt den **lebenden**
Weg vom FTP-Konto bis zur oeffentlichen Adresse, und zwar in der Richtung,
in der er im Ernstfall benutzt wird.

WARUM `curl` UND NICHT DIE FREMD-AKTION (E-KH-07)
`curl` liegt auf jedem GitHub-Laeufer. Das ist bequem, aber nicht der Grund:
Er ist bewusst ein **zweiter** FTPS-Client neben `SamKirkland/FTP-Deploy-Action`.
Scheitert der Upload dort und die Zielprobe hier gelingt, liegt es an der
Bibliothek; scheitern beide an derselben Stelle, liegt es an der Plattform.
**Das ist der Trennschnitt, den F3 braucht** -- der `ECONNRESET` bei
`ensureDir('api/')`, den bis heute niemand erklaeren kann. Ein Werkzeug, das
denselben Client benutzt, koennte diese Frage nicht beantworten.

DIE ZWEI BETRIEBSARTEN, UND WARUM SIE GEMESSEN WERDEN
Viele FTPS-Server verlangen, dass der Datenkanal die TLS-Sitzung des
Steuerkanals **wiederverwendet**; wer es nicht tut, bekommt die
Datenverbindung abgeschnitten -- das sieht aus wie ein `ECONNRESET`. Genau
danach sucht der Trennversuch. Im Normalbetrieb laeuft die Probe deshalb
**mit** Wiederverwendung (`--ssl-reqd`, Vorgabe); mit
`--ohne-sitzungswiederverwendung` laeuft sie ohne
(`--no-ssl-session-reuse`).

**Ob `curl` die Sitzung in der Fassung dieses Laeufers tatsaechlich
wiederverwendet, wird GEMESSEN und nicht angenommen.** Der Schalter kann in
einer Fassung fehlen, er kann still ignoriert werden, und dann belegte ein
gelungener Lauf gar nichts. `--verbose` zeigt es an; die Ausgabe wird
mitgeschrieben, nach `SSL re-using`/`SSL reusing` durchsucht und **mit
maskierten Geheimnissen** ausgegeben. Was nicht messbar war, sagt die Probe,
statt es zu behaupten.

WAS SIE HINTERLAESST: NICHTS
Sie raeumt Reste frueherer Proben weg (`NLST` auf das Zielverzeichnis, alles
mit dem Praefix `.zielprobe-`), und sie loescht ihre eigene Datei **auch im
Fehlerfall** -- sonst liegt nach dem dritten roten Lauf Muell im Webroot, den
jeder abrufen kann. Servermeldungen gibt sie **woertlich** aus: Eine
umformulierte FTP-Antwort ist bei einer Fehlersuche wertlos.

WAS SIE NICHT KANN
Sie misst den Weg fuer eine **statische** Datei. Ob PHP laeuft, ob die
Anwendung antwortet, ob `.htaccess` greift -- davon sagt sie nichts. Und sie
misst EIN Verzeichnis: das, auf das `--ftp-pfad` zeigt. Liegt der Webroot
woanders, faellt das hier auf, und das ist der Zweck.
"""
from __future__ import annotations

import argparse
import os
import re
import secrets
import shutil
import subprocess
import sys
import urllib.error
import urllib.parse
import urllib.request

# ZEITGRENZEN. Eine Probedatei von 64 Byte braucht keine Minute; laenger heisst
# haengen, und ein haengender Kettenschritt kostet mehr als ein roter.
FTP_ZEITGRENZE_S = 60
WEB_ZEITGRENZE_S = 30

# DER PRAEFIX IST TEIL DER AUFRAEUMREGEL. Er beginnt mit einem Punkt, damit
# `.htaccess` (Z. 64, jeder Pfad mit fuehrendem Punkt -> 403) eine
# liegengebliebene Datei nicht ausliefert -- ein zweiter Riegel neben dem
# Loeschen. Der HTTPS-Abruf umgeht ihn nicht: Er holt die Datei ueber ihren
# vollen Namen, und wenn `.htaccess` sie sperrt, MELDET die Probe das, statt
# es fuer einen fehlenden Upload zu halten.
# `curl`-Rueckgabewert 60 = "peer certificate cannot be authenticated".
# Er bekommt eine eigene Behandlung, weil er etwas anderes bedeutet als jeder
# andere Fehlschlag: Der Transport ist in Ordnung, die IDENTITAET nicht.
CURL_ZERTIFIKAT = 60

PRAEFIX = ".zielprobe-"

# Was in einer Ausgabe nie stehen darf. Wird vor JEDER Ausgabe ersetzt.
MASKE = "***"


def maskieren(text: str, geheimnisse: list[str]) -> str:
    """Geheimnisse aus einer Ausgabe nehmen — bevor sie ins Protokoll geht.

    `curl --verbose` schreibt die Adresse mit, und in ihr steht bei FTP das
    Passwort. Ein Kettenprotokoll ist fuer jeden lesbar, der den Lauf sehen
    darf. Deshalb laeuft JEDE Ausgabe dieses Werkzeugs hier durch, nicht nur
    die, bei der es gerade auffaellt.
    """
    for g in geheimnisse:
        # UNTER VIER ZEICHEN WIRD NICHT ERSETZT. Ein „Geheimnis" von ein bis
        # drei Zeichen kommt in jedem zweiten Wort vor; die Maskierung
        # zerschnitte die Ausgabe, ohne irgendetwas zu schuetzen. Genau das
        # hat die Selbstprobe am 20.09.2026 gefunden: Mit dem Passwort "p"
        # wurde aus `.zielprobe-alt.txt` ein `.zielpro***e-alt.txt`, und das
        # Aufraeumen fand seine eigenen Reste nicht mehr. Ein so kurzes
        # Passwort ist ohnehin keines.
        if g and len(g) >= 4:
            text = text.replace(g, MASKE)
            # Auch prozentkodiert — so steht es in der Adresse.
            text = text.replace(urllib.parse.quote(g, safe=""), MASKE)
    return text


def sag(text: str, geheimnisse: list[str], nach_stderr: bool = False) -> None:
    """Die EINE Stelle, an der dieses Werkzeug etwas ausgibt.

    Sie maskiert. Wer daran vorbei `print()` schreibt, umgeht den Schutz —
    deshalb gibt es sie, und deshalb steht sie hier oben.
    """
    strom = sys.stderr if nach_stderr else sys.stdout
    print(maskieren(text, geheimnisse), file=strom)
    # SOFORT SCHREIBEN. `stdout` ist in einer Kette kein Bildschirm, sondern
    # eine Datei — und damit gepuffert, `stderr` nicht. Ohne diesen Aufruf
    # erscheint im Protokoll die Fehlermeldung VOR dem Kopf, zu dem sie
    # gehoert. Gemessen am 20.09.2026 im ersten Probelauf: Der Block
    # "Zielprobe gegen …" stand hinter seinem eigenen Fehlschlag.
    strom.flush()


def curl_da() -> str | None:
    return shutil.which("curl")


def ftp_adresse(server: str, pfad: str, name: str = "") -> str:
    pfad = "/" + pfad.strip("/")
    if pfad != "/":
        pfad += "/"
    return f"ftp://{server}{pfad}{name}"


def curl_ftp(argumente: list[str], konto: str, passwort: str,
             lauf=subprocess.run) -> tuple[int, str, str]:
    """Ein `curl`-Aufruf gegen FTPS. Gibt (Rückgabewert, stdout, stderr) zurück.

    DAS PASSWORT GEHT UEBER `--config -` UND NICHT UEBER DIE BEFEHLSZEILE.
    Auf einem geteilten Laeufer liest jeder Prozess `/proc/<pid>/cmdline`;
    `-u konto:passwort` stuende dort im Klartext. Dass hier heute niemand
    mitliest, ist kein Argument — es ist eine Zusage, die nichts kostet.
    """
    befehl = [curl_da() or "curl", "--config", "-", *argumente]
    e = lauf(befehl, input=f'user = "{konto}:{passwort}"\n',
             capture_output=True, text=True, timeout=FTP_ZEITGRENZE_S)
    # ROH ZURUECK, NICHT MASKIERT. Maskiert wird am RAND — dort, wo etwas
    # ausgegeben wird (`sag()`), nicht in der Mitte.
    #
    # Andersherum war es zuerst, und die Selbstprobe hat es gefunden:
    # `aufraeumen()` liest die Dateiliste, die dieser Aufruf zurueckgibt.
    # War sie vorher maskiert, suchte es Namen in einem Text, in dem das
    # Passwort schon durch `***` ersetzt war — und fand seine eigenen Reste
    # nicht mehr. Eine Maskierung, die den Text zerschneidet, den das
    # Werkzeug danach auswertet, ist kein Schutz, sondern ein Fehler.
    return e.returncode, (e.stdout or ""), (e.stderr or "")


def sitzung_wiederverwendet(ausfuehrlich: str) -> bool | None:
    """Hat `curl` die TLS-Sitzung auf dem Datenkanal wiederverwendet?

    DREIWERTIG, UND DAS IST DER PUNKT (wie `plattform_pruefen()`,
    `Technik.md` 5b.1): `True` belegt, `False` belegt das Gegenteil, **`None`
    heisst „nicht feststellbar"** — die Fassung dieses `curl` sagt nichts
    darueber. Ein `False` an dieser Stelle waere eine Behauptung ueber etwas,
    das gar nicht gemessen wurde, und genau daran ist am 20.09.2026 die
    OPcache-Auskunft gescheitert (F-KH-U-05).
    """
    if not ausfuehrlich:
        return None
    if re.search(r"SSL re-?us(ing|ed)", ausfuehrlich, re.I):
        return True
    if re.search(r"SSL (session )?re-?use (failed|not)", ausfuehrlich, re.I):
        return False
    # `curl` nennt den Datenkanal-Handshake nur, wenn er STATTFINDET. Findet
    # er statt, ist die Sitzung NICHT wiederverwendet worden.
    if re.search(r"Server did not (accept|agree)", ausfuehrlich, re.I):
        return False
    return None


def holen(adresse: str, zeitgrenze: int = WEB_ZEITGRENZE_S) -> tuple[int, bytes, str]:
    """HTTPS-Abruf. Gibt (Status, Körper, Fehlertext) zurück — wirft nicht.

    EIN 404 IST HIER EIN ERGEBNIS UND KEIN FEHLER: Nach dem Loeschen ist er
    genau das, was belegt werden soll. Deshalb wird der Status zurueckgegeben
    und nicht geworfen.
    """
    anfrage = urllib.request.Request(
        adresse, headers={"User-Agent": "nadoku-zielprobe"})
    try:
        with urllib.request.urlopen(anfrage, timeout=zeitgrenze) as antwort:
            return antwort.status, antwort.read(), ""
    except urllib.error.HTTPError as ex:
        try:
            koerper = ex.read()
        except Exception:                            # noqa: BLE001
            koerper = b""
        return ex.code, koerper, ""
    except (urllib.error.URLError, OSError, TimeoutError) as ex:
        return 0, b"", str(ex)


def web_adresse(basis: str, name: str) -> str:
    return basis.rstrip("/") + "/" + name


def aufraeumen(server: str, pfad: str, konto: str, passwort: str,
               lauf=subprocess.run) -> list[str]:
    """Reste früherer Proben wegräumen. Gibt die gelöschten Namen zurück.

    WARUM ES DAS GIBT: Ein Lauf, der zwischen Upload und Loeschen abbricht
    (Zeitgrenze des Jobs, Abbruch von Hand), laesst seine Datei liegen. Ohne
    diesen Schritt sammeln sich die Reste im Webroot — und ein abgebrochener
    Lauf ist genau die Lage, in der niemand nachsieht.
    """
    rc, aus, _ = curl_ftp(["--ssl-reqd", "--list-only",
                           ftp_adresse(server, pfad)],
                          konto, passwort, lauf)
    if rc != 0:
        return []
    reste = [z.strip().rsplit("/", 1)[-1] for z in aus.splitlines()
             if z.strip().rsplit("/", 1)[-1].startswith(PRAEFIX)]
    weg = []
    for name in reste:
        r, _, _ = curl_ftp(["--ssl-reqd", "-Q", f"-DELE {pfad.rstrip('/')}/{name}",
                            ftp_adresse(server, pfad)],
                           konto, passwort, lauf)
        if r == 0:
            weg.append(name)
    return weg


def probe(basis: str, server: str, pfad: str, konto: str, passwort: str,
          sitzung_wiederverwenden: bool = True,
          lauf=subprocess.run, hol=holen) -> int:
    """Der Rundlauf. 0 = gelungen.

    JEDE Ausgabe geht durch `sag()` und ist damit maskiert — auch die
    wörtlichen Servermeldungen, gerade die.
    """
    geheim = [passwort, konto]
    def a(text): sag(text, geheim)
    def f(text): sag(text, geheim, True)

    name = PRAEFIX + secrets.token_hex(8) + ".txt"
    inhalt = secrets.token_hex(24).encode("ascii")
    reuse = "--ssl-reqd" if sitzung_wiederverwenden else "--no-ssl-session-reuse"

    a(f"Zielprobe gegen {basis.rstrip('/')}")
    a(f"  FTPS-Ziel:      {ftp_adresse(server, pfad)}")
    a(f"  Betriebsart:    {'MIT' if sitzung_wiederverwenden else 'OHNE'} "
      f"Wiederverwendung der TLS-Sitzung ({reuse})")
    a(f"  Probedatei:     {name} ({len(inhalt)} Byte)")

    weg = aufraeumen(server, pfad, konto, passwort, lauf)
    a(f"  Reste weggeräumt: {len(weg)}" + (f" ({', '.join(weg)})" if weg else ""))

    quelle = os.path.join(os.environ.get("RUNNER_TEMP", "/tmp"), name)
    fehler = 0
    hochgeladen = False
    try:
        with open(quelle, "wb") as fh:
            fh.write(inhalt)

        # ---- 1. hochladen --------------------------------------------------
        rc, _, err = curl_ftp([reuse, "--verbose", "--upload-file", quelle,
                               ftp_adresse(server, pfad, name)],
                              konto, passwort, lauf)
        wieder = sitzung_wiederverwendet(err)
        a("  TLS-Sitzung wiederverwendet: "
          + {True: "JA (gemessen)", False: "NEIN (gemessen)",
             None: "NICHT FESTSTELLBAR — diese `curl`-Fassung sagt nichts darüber; "
                   "der Lauf belegt die Betriebsart NICHT"}[wieder])
        if rc != 0:
            f(f"FEHLGESCHLAGEN beim Hochladen (curl {rc}).")
            if rc == CURL_ZERTIFIKAT:
                # DAS IST KEIN TRANSPORTFEHLER, UND DIE UNTERSCHEIDUNG ZÄHLT.
                # `curl` prüft das Zertifikat gegen den Hostnamen; passt es
                # nicht, bricht er ab, BEVOR eine einzige Datei bewegt wird.
                # Eine Auslieferungsbibliothek, die dasselbe Ziel ohne Murren
                # beschreibt, prüft es NICHT — und dann ist die Verbindung
                # zwar verschlüsselt, aber der Gegenüber ist ungeprüft.
                f("")
                f("  DAS IST EIN NAMENSFEHLER IM ZERTIFIKAT, KEIN TRANSPORTFEHLER.")
                f("  Der FTPS-Server weist sich mit einem Namen aus, der nicht der")
                f("  ist, den `--ftp-server` nennt. Die Verbindung ist damit zwar")
                f("  verschlüsselt, aber NICHT beglaubigt: Wer sich dazwischen")
                f("  setzt, fiele nicht auf. Über genau diese Verbindung gehen die")
                f("  FTPS-Zugangsdaten und der ganze Inhalt von `server/`.")
                f("")
                f("  Abhilfe (in dieser Reihenfolge): (1) `--ftp-server` auf den")
                f("  Namen setzen, den das Zertifikat trägt — dann stimmt beides;")
                f("  (2) beim Hoster ein Zertifikat für den benutzten Namen")
                f("  verlangen. Die Prüfung abzuschalten ist KEINE Abhilfe, und")
                f("  dieses Werkzeug bietet dafür keinen Schalter.")
            f("  Servermeldung, wörtlich:")
            for z in (err or "(keine)").splitlines():
                f(f"    {z}")
            fehler = 1
        else:
            hochgeladen = True
            # ---- 2. über HTTPS zurückholen und vergleichen ------------------
            adresse = web_adresse(basis, name)
            status, koerper, netzfehler = hol(adresse)
            a(f"  HTTPS-Abruf {adresse} → {status or 'kein Status'}"
              + (f" ({netzfehler})" if netzfehler else ""))
            if status != 200:
                f(f"FEHLGESCHLAGEN: Die Datei liegt im FTP-Ziel, ist aber unter "
                  f"{adresse} nicht abrufbar (Status {status or netzfehler}). Das "
                  f"FTP-Verzeichnis und die öffentliche Adresse zeigen nicht auf "
                  f"dasselbe.")
                fehler = 1
            elif koerper.strip() != inhalt:
                f(f"FEHLGESCHLAGEN: Abgerufen wurden {len(koerper)} Byte, "
                  f"hochgeladen {len(inhalt)}. Der Inhalt stimmt nicht überein.")
                fehler = 1
            else:
                a(f"  Inhalt stimmt überein ({len(inhalt)} Byte).")
    finally:
        # ---- 3. löschen — AUCH IM FEHLERFALL -------------------------------
        try:
            os.unlink(quelle)
        except OSError:
            pass
        if not hochgeladen:
            # NICHT ÜBER EINEN REST WARNEN, DEN ES NICHT GIBT. Scheitert schon
            # der Verbindungsaufbau, ist nie eine Datei entstanden — eine
            # Warnung „sie liegt jetzt im Zielverzeichnis" schickte dann
            # jemanden auf die Suche nach nichts. Gemessen am 20.09.2026: Der
            # erste Probelauf gegen Produktiv scheiterte am Zertifikat, und
            # die Probe warnte trotzdem vor einem Rest.
            a("  Nichts zu löschen — es ist nie eine Datei entstanden.")
        else:
            rc, _, err = curl_ftp([reuse, "-Q", f"-DELE {pfad.rstrip('/')}/{name}",
                                   ftp_adresse(server, pfad)],
                                  konto, passwort, lauf)
            if rc != 0:
                f(f"WARNUNG: Die Probedatei {name} ließ sich nicht löschen "
                  f"(curl {rc}). Sie liegt jetzt im Zielverzeichnis.")
                for z in (err or "(keine Meldung)").splitlines():
                    f(f"    {z}")
                fehler = 1
            else:
                a("  Probedatei gelöscht.")

    if fehler:
        return 1

    # ---- 4. und das Löschen prüfen -----------------------------------------
    status, _, netzfehler = hol(web_adresse(basis, name))
    a(f"  HTTPS-Abruf nach dem Löschen → {status or 'kein Status'}")
    if status == 200:
        f("FEHLGESCHLAGEN: Die Datei ist nach dem Löschen weiter abrufbar. Entweder "
          "liegt ein Zwischenspeicher davor, oder gelöscht wurde woanders als "
          "abgerufen.")
        return 1
    if status == 0:
        f(f"FEHLGESCHLAGEN: Nach dem Löschen war die Adresse nicht erreichbar "
          f"({netzfehler}) — das ist kein 404 und belegt nichts.")
        return 1

    a(f"\nRundlauf gelungen: geschrieben, abgerufen, verglichen, gelöscht, "
      f"danach {status}.")
    return 0


# ---------------------------------------------------------------- Selbstprobe

def selbstprobe() -> int:
    """Tut die Probe, was sie sagt — ohne Netz und ohne Server?

    Ohne diese Probe ist ein grüner Lauf eine Zahl ohne Aussage. Die
    interessanten Lagen — „Datei liegt im FTP, aber nicht im Web", „nach dem
    Löschen weiter abrufbar" — lassen sich gegen eine echte Anlage nicht
    herstellen, ohne sie zu beschädigen.
    """
    erfuellt = offen = 0

    def pruefe(b: bool, was: str, wert: str = "") -> None:
        nonlocal erfuellt, offen
        erfuellt, offen = (erfuellt + 1, offen) if b else (erfuellt, offen + 1)
        print(f"  [{'ok ' if b else 'FEHL'}] {was}" + (f"  {wert}" if wert else ""))

    print("Selbstprobe der Zielprobe — ohne Netz\n")

    # ---- Maskierung: das Wichtigste zuerst ----
    t = maskieren("ftp://konto:gehe!m@host/ und gehe!m nochmal", ["gehe!m", "konto"])
    pruefe("gehe!m" not in t, "Das Passwort steht in keiner Ausgabe", t)
    pruefe(MASKE in t, "…sondern die Maske", t)
    t2 = maskieren("vor a%21bcd nach", ["a!bcd"])
    pruefe(MASKE in t2, "Auch prozentkodiert maskiert (so steht es in der Adresse)", t2)
    # DIE GEGENPROBE ZUR LAENGENGRENZE. Ohne sie zerschnitt die Maskierung die
    # Ausgabe, die `aufraeumen()` danach auswerten muss — gefunden 20.09.2026.
    t3 = maskieren(".zielprobe-alt.txt", ["p"])
    pruefe(t3 == ".zielprobe-alt.txt",
           'Ein Geheimnis von EINEM Zeichen zerschneidet die Ausgabe NICHT', t3)

    # ---- Adressen ----
    pruefe(ftp_adresse("h", "/") == "ftp://h/", "FTP-Adresse bei Zielpfad `/`",
           ftp_adresse("h", "/"))
    pruefe(ftp_adresse("h", "/web", "x.txt") == "ftp://h/web/x.txt",
           "FTP-Adresse mit Unterverzeichnis", ftp_adresse("h", "/web", "x.txt"))
    pruefe(web_adresse("https://a.example/", "x") == "https://a.example/x",
           "Ein Schrägstrich am Ende der Basis verdoppelt sich nicht")

    # ---- Dreiwertigkeit der Sitzungsmessung ----
    pruefe(sitzung_wiederverwendet("* SSL re-using session ID") is True,
           "`SSL re-using` → True")
    pruefe(sitzung_wiederverwendet("* Server did not accept SSL session reuse") is False,
           "Server lehnt ab → False")
    pruefe(sitzung_wiederverwendet("* irgendwas anderes") is None,
           "Nichts dazu gesagt → None (NICHT False)")
    pruefe(sitzung_wiederverwendet("") is None, "Leere Ausgabe → None")

    # ---- Der Rundlauf, gegen Attrappen ----
    class Lauf:
        """Stellt `subprocess.run` nach. `dele` zählt die Löschversuche."""

        def __init__(self, rc_upload=0, rc_dele=0, liste="", verbose=""):
            self.rc_upload, self.rc_dele = rc_upload, rc_dele
            self.liste, self.verbose = liste, verbose
            self.dele = 0
            self.befehle: list[list[str]] = []

        def __call__(self, befehl, **kw):
            self.befehle.append(befehl)

            class E:
                pass
            e = E()
            if "--list-only" in befehl:
                e.returncode, e.stdout, e.stderr = 0, self.liste, ""
            elif any(str(x).startswith("-DELE") for x in befehl):
                self.dele += 1
                e.returncode, e.stdout, e.stderr = self.rc_dele, "", "550 nope"
            else:
                e.returncode, e.stdout, e.stderr = self.rc_upload, "", self.verbose
            return e

    merker: dict = {}

    def hol_ok(adresse, zeitgrenze=0):
        """Erst 200 mit dem richtigen Inhalt, danach 404."""
        if adresse in merker:
            return 404, b"", ""
        merker[adresse] = True
        return 200, merker["inhalt"], ""

    # Der Inhalt ist zufällig; die Attrappe muss ihn dem Werkzeug abnehmen.
    class LaufMitInhalt(Lauf):
        def __call__(self, befehl, **kw):
            for i, x in enumerate(befehl):
                if x == "--upload-file":
                    with open(befehl[i + 1], "rb") as f:
                        merker["inhalt"] = f.read()
            return super().__call__(befehl, **kw)

    merker.clear()
    l = LaufMitInhalt(verbose="* SSL re-using session ID")
    rc = probe("https://a.example", "h", "/", "k", "p", True, l, hol_ok)
    pruefe(rc == 0, "Regelfall: hochgeladen, abgerufen, verglichen, gelöscht, 404")
    pruefe(l.dele == 1, "Genau ein Löschversuch im Regelfall", f"{l.dele}")

    merker.clear()
    l = LaufMitInhalt()
    rc = probe("https://a.example", "h", "/", "k", "p", True, l,
               lambda a, zeitgrenze=0: (404, b"", ""))
    pruefe(rc == 1, "Datei liegt im FTP, ist über HTTPS aber 404 → rot")
    pruefe(l.dele == 1, "…und wird TROTZDEM gelöscht", f"{l.dele} Löschversuch(e)")

    merker.clear()
    l = LaufMitInhalt()
    rc = probe("https://a.example", "h", "/", "k", "p", True, l,
               lambda a, zeitgrenze=0: (200, b"etwas anderes", ""))
    pruefe(rc == 1, "Abgerufener Inhalt weicht ab → rot")
    pruefe(l.dele == 1, "…und wird trotzdem gelöscht", f"{l.dele} Löschversuch(e)")

    merker.clear()
    l = LaufMitInhalt()
    rc = probe("https://a.example", "h", "/", "k", "p", True, l,
               lambda a, zeitgrenze=0: (200, merker.get("inhalt", b""), ""))
    pruefe(rc == 1, "Nach dem Löschen weiter abrufbar (200 statt 404) → rot")

    merker.clear()
    l = LaufMitInhalt(rc_upload=7)
    rc = probe("https://a.example", "h", "/", "k", "p", True, l, hol_ok)
    pruefe(rc == 1, "Hochladen scheitert → rot")
    # KEIN LÖSCHVERSUCH, UND DAS IST DIE BEHEBUNG (20.09.2026). Scheitert
    # schon der Verbindungsaufbau, ist nie eine Datei entstanden. Vorher
    # löschte die Probe trotzdem und warnte dann, die Datei „liege jetzt im
    # Zielverzeichnis" — sie schickte jemanden auf die Suche nach nichts.
    # Der erste echte Probelauf gegen Produktiv hat genau das getan.
    pruefe(l.dele == 0, "…und es wird NICHTS gelöscht, weil nichts entstanden ist",
           f"{l.dele} Löschversuch(e)")

    # Die Gegenprobe dazu bleibt: Ist die Datei OBEN und der Rundlauf
    # scheitert danach, MUSS gelöscht werden — sonst bleibt sie liegen.
    merker.clear()
    l = LaufMitInhalt()
    probe("https://a.example", "h", "/", "k", "p", True, l,
          lambda a, zeitgrenze=0: (404, b"", ""))
    pruefe(l.dele == 1, "Hochladen gelungen, Abruf rot → es WIRD gelöscht",
           f"{l.dele} Löschversuch(e)")

    # Und der Zertifikatsfehler wird benannt, nicht bloß gezählt.
    import io as _io, contextlib
    merker.clear()
    puffer = _io.StringIO()
    with contextlib.redirect_stderr(puffer):
        probe("https://a.example", "h", "/", "k", "p", True,
              LaufMitInhalt(rc_upload=CURL_ZERTIFIKAT), hol_ok)
    txt = puffer.getvalue()
    pruefe("NAMENSFEHLER IM ZERTIFIKAT" in txt,
           "curl 60 wird als Namensfehler benannt, nicht als Transportfehler")
    pruefe("keinen Schalter" in txt,
           "…und es wird kein Weg angeboten, die Prüfung abzuschalten")

    merker.clear()
    l = LaufMitInhalt(rc_dele=9)
    rc = probe("https://a.example", "h", "/", "k", "p", True, l, hol_ok)
    pruefe(rc == 1, "Löschen scheitert → rot (die Datei bleibt sonst still liegen)")

    # ---- Betriebsart landet tatsächlich im Befehl ----
    merker.clear()
    l = LaufMitInhalt()
    probe("https://a.example", "h", "/", "k", "p", False, l, hol_ok)
    ohne = any("--no-ssl-session-reuse" in b for b in l.befehle)
    pruefe(ohne, "`--ohne-sitzungswiederverwendung` steht im AUSGEFÜHRTEN Befehl")
    merker.clear()
    l = LaufMitInhalt()
    probe("https://a.example", "h", "/", "k", "p", True, l, hol_ok)
    mit = any("--ssl-reqd" in b for b in l.befehle)
    pruefe(mit and not any("--no-ssl-session-reuse" in b for b in l.befehle),
           "…und im Normalbetrieb steht `--ssl-reqd` da, nicht der Gegenschalter")

    # ---- Aufräumen ----
    l = Lauf(liste=".zielprobe-alt.txt\nindex.php\n.zielprobe-zwei.txt\n")
    weg = aufraeumen("h", "/", "k", "passwort", l)
    pruefe(weg == [".zielprobe-alt.txt", ".zielprobe-zwei.txt"],
           "Reste früherer Proben werden erkannt — und nur die", str(weg))
    pruefe("index.php" not in weg, "…`index.php` fasst sie nicht an")

    # ---- Das Passwort steht NICHT in der Befehlszeile ----
    l = Lauf()
    curl_ftp(["--ssl-reqd", "ftp://h/"], "k", "streng-geheim", l)
    inzeile = any("streng-geheim" in str(x) for b in l.befehle for x in b)
    pruefe(not inzeile, "Das Passwort steht in keinem Befehlszeilenargument "
                        "(/proc/<pid>/cmdline ist lesbar)")

    print(f"\n  -> {erfuellt + offen} Lagen, {offen} nicht erfüllt")
    return 0 if offen == 0 else 1


def main(argv: list[str]) -> int:
    p = argparse.ArgumentParser(add_help=True, description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--basis", help="öffentliche Adresse, z. B. https://nadoku.example")
    p.add_argument("--ftp-server", help="FTP-Host (ohne Schema)")
    p.add_argument("--ftp-konto")
    p.add_argument("--ftp-pass")
    p.add_argument("--ftp-pfad", default="/",
                   help="Zielverzeichnis auf dem Server (Vorgabe: /)")
    p.add_argument("--ohne-sitzungswiederverwendung", action="store_true",
                   help="Datenkanal ohne Wiederverwendung der TLS-Sitzung — "
                        "für den Trennversuch (F3), nicht für den Normalbetrieb")
    p.add_argument("--selbstprobe", action="store_true",
                   help="ohne Netz prüfen, ob die Probe überhaupt anschlägt")
    a = p.parse_args(argv)

    if a.selbstprobe:
        return selbstprobe()
    fehlt = [n for n, v in (("--basis", a.basis), ("--ftp-server", a.ftp_server),
                            ("--ftp-konto", a.ftp_konto), ("--ftp-pass", a.ftp_pass))
             if not v]
    if fehlt:
        print("Pflicht: " + ", ".join(fehlt), file=sys.stderr)
        return 2
    if not curl_da():
        print("ABBRUCH: `curl` ist auf diesem Läufer nicht vorhanden. Die Probe "
              "benutzt ihn ausdrücklich als ZWEITEN FTPS-Client neben der "
              "Auslieferungsaktion — ein Rückfall auf dieselbe Bibliothek "
              "beantwortete die Frage nicht, für die es sie gibt.",
              file=sys.stderr)
        return 2
    return probe(a.basis, a.ftp_server, a.ftp_pfad, a.ftp_konto, a.ftp_pass,
                 not a.ohne_sitzungswiederverwendung)


if __name__ == "__main__":
    try:
        sys.exit(main(sys.argv[1:]))
    except Exception as ex:                          # noqa: BLE001
        print(f"Die Zielprobe selbst ist gescheitert: {ex}", file=sys.stderr)
        sys.exit(2)
