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
**mit** Wiederverwendung (das ist `curl`s Verhalten ohne Zutun); mit
`--ohne-sitzungswiederverwendung` laeuft sie ohne (`--no-sessionid`).
`--ssl-reqd` steht in BEIDEN Betriebsarten -- es verlangt TLS und hat
mit der Wiederverwendung nichts zu tun.

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

# `curl`-Rueckgabewert 60 = "peer certificate cannot be authenticated".
# Er bekommt eine eigene Behandlung, weil er etwas anderes bedeutet als jeder
# andere Fehlschlag: Der Transport ist in Ordnung, die IDENTITAET nicht.
CURL_ZERTIFIKAT = 60

# `curl`-Rueckgabewert 2 = "failed to initialize" -- das meldet er unter
# anderem bei einem Schalter, den er nicht kennt.
CURL_BEDIENFEHLER = 2

# DIE BEIDEN SCHALTER, UND WARUM SIE SO HEISSEN.
#
# `--ssl-reqd` steht IMMER dabei: Es verlangt TLS und bricht ab, wenn der
# Server keines anbietet. Es hat mit der Wiederverwendung NICHTS zu tun --
# bis zum 20.09.2026 stand es hier so, als waere es der Schalter fuer die
# Betriebsart "mit". Das war falsch beschriftet: "Mit Wiederverwendung" ist
# schlicht das Verhalten von `curl` ohne Zutun.
#
# `--no-sessionid` schaltet sie ab ("Disable SSL session-ID reusing").
#
# HIER STAND `--no-ssl-session-reuse`, UND DEN GIBT ES NICHT. Gemessen im
# ersten Trennversuch (Lauf 35534784406, 20.09.2026):
#
#     curl: option --no-ssl-session-reuse: is unknown
#
# Der ganze Lauf hat damit NICHTS gemessen. Die Selbstprobe hatte geprueft,
# dass die Zeichenkette im ausgefuehrten Befehl LANDET -- nicht, dass `curl`
# sie kennt. Genau diese Fehlerklasse beschreibt `CLAUDE.md` 6 fuer die
# Kette ("ein Schalter, der still verworfen wird"); hier war er wenigstens
# laut. Deshalb gibt es jetzt `curl_kennt()`.
SCHALTER_TLS_PFLICHT = "--ssl-reqd"
SCHALTER_OHNE_SITZUNG = "--no-sessionid"

# DER PRAEFIX HAT KEINEN FUEHRENDEN PUNKT -- UND DAS IST EINE BEHEBUNG.
#
# Bis zum 20.09.2026 hiess er `.zielprobe-`. Die Begruendung stand hier und
# klang gut: `.htaccess` (Z. 64) antwortet auf jeden Pfad mit fuehrendem Punkt
# mit 403, also laege eine vergessene Probedatei hinter einem zweiten Riegel.
#
# GEMESSEN (Lauf 35532390449, 19:30 UTC) WAR ES EIN DENKFEHLER: Der Riegel
# sperrt nicht nur Fremde aus, sondern die Probe selbst. Der Rundlauf geht
# FTPS hinauf und HTTPS zurueck -- und der Rueckweg lief in genau dieses 403.
# Schlimmer als der Fehlschlag war die Diagnose: Die Probe meldete "Das
# FTP-Verzeichnis und die oeffentliche Adresse zeigen nicht auf dasselbe", und
# das war FALSCH. Sie zeigten sehr wohl auf dasselbe; die Datei war nur
# gesperrt. Ein Pruefmittel, das eine richtige Anlage fuer falsch erklaert,
# ist schlimmer als keines.
#
# WAS DER PUNKT SCHUETZEN SOLLTE, WIEGT NICHTS: Der Inhalt sind 48 Zeichen
# Zufall ohne Bedeutung, die Datei wird im selben Schritt geloescht, und jeder
# Lauf raeumt Reste des vorigen weg. Eine liegengebliebene Probedatei ist eine
# oeffentlich lesbare Zufallszahl.
PRAEFIX = "zielprobe-"

# Der alte Praefix wird beim Aufraeumen MITGENOMMEN, sonst bliebe liegen, was
# die alte Fassung hinterlassen hat -- und zwar unsichtbar, weil der Punkt sie
# vor dem Abruf ueber HTTP verbirgt.
PRAEFIX_ALT = ".zielprobe-"

# DER ZWEITE RUNDLAUF GEHT DURCH EIN UNTERVERZEICHNIS -- und das ist keine
# Verfeinerung, sondern der Kern (gemessen 20.09.2026).
#
# Die Auslieferungsaktion stirbt an dieser Stelle:
#
#     creating folder "api/"
#       at Client._openDir -> Client.ensureDir -> ECONNRESET (data socket)
#
# `ensureDir` legt ein Verzeichnis an, das es NOCH NICHT GIBT, und listet es
# danach ueber den Datenkanal. Der flache Rundlauf beruehrt diese Folge nie:
# Er schreibt eine Datei in ein BESTEHENDES Verzeichnis. Genau deshalb war
# der Trennversuch am 20.09.2026 viermal gruen, waehrend der echte Upload
# viermal rot war -- die Probe hat die kranke Stelle gar nicht angefasst.
#
# Der zweite Rundlauf stellt sie nach: Verzeichnis anlegen (`--ftp-create-dirs`),
# hineinschreiben, es AUFLISTEN (das ist `_openDir`), ueber HTTPS holen,
# aufraeumen. Er laeuft immer mit -- ein Pruefmittel, das den Weg misst, den
# die Auslieferung NICHT geht, ist eine gruene Zahl ohne Aussage.
ORDNERDATEI = "probe.txt"

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


def curl_kennt(option: str, lauf=subprocess.run) -> bool:
    """Kennt dieses `curl` den Schalter überhaupt?

    DIE LEHRE AUS DEM 20.09.2026. Ein Schalter in einer Befehlszeile ist
    keine Zusage, sondern eine Behauptung — und `--no-ssl-session-reuse` war
    frei erfunden. Die Selbstprobe hatte nachgesehen, ob die Zeichenkette im
    ausgeführten Befehl landet; `curl` hat sie dann abgelehnt, und der
    Trennversuch maß nichts.

    `curl --help all` listet jede Option dieser Fassung. Das ist eine Frage
    an das Werkzeug selbst statt an unsere Erinnerung, kostet Millisekunden
    und braucht kein Netz.
    """
    try:
        e = lauf([curl_da() or "curl", "--help", "all"],
                 capture_output=True, text=True, timeout=10)
    except Exception:                                # noqa: BLE001
        return False
    return option in ((e.stdout or "") + (e.stderr or ""))


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


def datenkanal(ausfuehrlich: str) -> str:
    """WIE kam die Datenverbindung zustande? EPSV, PASV — oder erst nach einem
    Fehlschlag?

    WARUM DAS GEMESSEN WIRD (20.09.2026, nach dem dritten ergebnislosen
    Trennversuch). Ausgeschlossen sind inzwischen Laeuferabbild, Node-Fassung,
    Zertifikat, TLS-Sitzungswiederverwendung und das Anlegen eines
    Verzeichnisses. Was zwischen `curl` und der Auslieferungsaktion noch
    verschieden sein KANN, ist der Weg zum Datenkanal:

    `curl` versucht `EPSV` und faellt bei Fehlschlag selbsttaetig auf `PASV`
    zurueck. Eine Bibliothek, die das nicht tut, bleibt an derselben Stelle
    haengen -- und das saehe von aussen aus wie ein `ECONNRESET` auf der
    ersten Datenverbindung. Gelingt die Probe hier ERST NACH einem
    EPSV-Fehlschlag, ist das die Erklaerung.

    Die Auskunft steht nur in `--verbose`, und die gab es bisher nur im
    Fehlerfall zu sehen. Ein gelungener Lauf hat damit die interessanteste
    Zeile verschluckt.
    """
    if not ausfuehrlich:
        return "nicht feststellbar (keine ausführliche Ausgabe)"
    epsv_weg = bool(re.search(r"(EPSV.*(fail|not|refus)|disabling EPSV|"
                              r"Failed EPSV)", ausfuehrlich, re.I))
    # `229` und "Extended Passive" SIND EPSV, auch wenn das Wort nicht fällt:
    # `curl` schreibt den Befehl als `> EPSV`, die Antwort aber als
    # `< 229 Entering Extended Passive Mode`. Wer nur auf "EPSV" prüft,
    # übersieht die Antwort — und meldet "nicht feststellbar", obwohl es
    # dasteht.
    hat_epsv = ("EPSV" in ausfuehrlich.upper()
                or "EXTENDED PASSIVE" in ausfuehrlich.upper())
    hat_pasv = re.search(r"(^|[^D])PASV|227 ", ausfuehrlich, re.M | re.I) is not None
    m229 = re.search(r"229 .*\(\|+(\d+)\|\)", ausfuehrlich)
    m227 = re.search(r"227 .*?(\d+,\d+,\d+,\d+,\d+,\d+)", ausfuehrlich)
    if epsv_weg and hat_pasv:
        return "PASV — NACH einem EPSV-Fehlschlag (curl ist zurückgefallen)"
    if hat_epsv and not epsv_weg and m229:
        return f"EPSV, Antwort 229, Port {m229.group(1)}"
    if hat_epsv and not epsv_weg:
        return "EPSV (Antwort 229)"
    if hat_pasv and m227:
        return f"PASV, Antwort 227 ({m227.group(1)})"
    if hat_pasv:
        return "PASV (Antwort 227)"
    return "nicht feststellbar (weder EPSV noch PASV in der Ausgabe)"


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
             if z.strip().rsplit("/", 1)[-1].startswith((PRAEFIX, PRAEFIX_ALT))]
    weg = []
    for name in reste:
        if name.endswith(".txt"):
            befehle = [f"-DELE {pfad.rstrip('/')}/{name}"]
        else:
            # EIN REST OHNE `.txt` IST EIN PROBEVERZEICHNIS. Erst die Datei
            # darin, dann das Verzeichnis — `RMD` auf ein volles Verzeichnis
            # weist jeder Server ab. Der innere Name ist fest (`ORDNERDATEI`),
            # genau damit dieses Aufräumen ihn kennt, ohne zu suchen.
            befehle = [f"-DELE {pfad.rstrip('/')}/{name}/{ORDNERDATEI}",
                       f"-RMD {pfad.rstrip('/')}/{name}"]
        r = 0
        for b in befehle:
            rc_, _, _ = curl_ftp(["--ssl-reqd", "-Q", b,
                                  ftp_adresse(server, pfad)],
                                 konto, passwort, lauf)
            r = rc_          # der LETZTE zaehlt: die Datei darf fehlen
        if r == 0:
            weg.append(name)
    return weg


def probe(basis: str, server: str, pfad: str, konto: str, passwort: str,
          sitzung_wiederverwenden: bool = True,
          lauf=subprocess.run, hol=holen, im_ordner: bool = False) -> int:
    """Ein Rundlauf. 0 = gelungen.

    `im_ordner` legt die Probedatei in ein VERZEICHNIS, das es noch nicht
    gibt, und listet es danach auf — die Nachstellung von `ensureDir`.
    Siehe den Kommentar bei `ORDNERDATEI`.

    JEDE Ausgabe geht durch `sag()` und ist damit maskiert — auch die
    wörtlichen Servermeldungen, gerade die.
    """
    geheim = [passwort, konto]
    def a(text): sag(text, geheim)
    def f(text): sag(text, geheim, True)

    ordner = PRAEFIX + secrets.token_hex(8) if im_ordner else ""
    name = f"{ordner}/{ORDNERDATEI}" if im_ordner else PRAEFIX + secrets.token_hex(8) + ".txt"
    inhalt = secrets.token_hex(24).encode("ascii")
    reuse = SCHALTER_TLS_PFLICHT
    if not sitzung_wiederverwenden:
        reuse += " " + SCHALTER_OHNE_SITZUNG

    a(f"Zielprobe gegen {basis.rstrip('/')}"
      + ("  —  Rundlauf 2: DURCH EIN NEUES VERZEICHNIS (`ensureDir`-Nachstellung)"
         if im_ordner else "  —  Rundlauf 1: flach"))
    a(f"  FTPS-Ziel:      {ftp_adresse(server, pfad)}")
    a(f"  Betriebsart:    {'MIT' if sitzung_wiederverwenden else 'OHNE'} "
      f"Wiederverwendung der TLS-Sitzung ({reuse})")
    a(f"  Probedatei:     {name} ({len(inhalt)} Byte)")

    weg = aufraeumen(server, pfad, konto, passwort, lauf)
    a(f"  Reste weggeräumt: {len(weg)}" + (f" ({', '.join(weg)})" if weg else ""))

    quelle = os.path.join(os.environ.get("RUNNER_TEMP", "/tmp"),
                          name.replace("/", "_"))
    fehler = 0
    hochgeladen = False
    try:
        with open(quelle, "wb") as fh:
            fh.write(inhalt)

        # ---- 1. hochladen --------------------------------------------------
        hoch = [*reuse.split(), "--verbose"]
        if im_ordner:
            # `--ftp-create-dirs` IST DAS GEGENSTUECK ZU `ensureDir`: `curl`
            # wechselt ins Verzeichnis und legt es an, wenn das misslingt.
            hoch.append("--ftp-create-dirs")
        rc, _, err = curl_ftp([*hoch, "--upload-file", quelle,
                               ftp_adresse(server, pfad, name)],
                              konto, passwort, lauf)
        wieder = sitzung_wiederverwendet(err)
        a(f"  Datenkanal:     {datenkanal(err)}")
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
            if im_ordner:
                # DAS IST DIE STELLE, AN DER DIE AUSLIEFERUNGSAKTION STIRBT.
                # `_openDir` listet ein eben angelegtes Verzeichnis über den
                # Datenkanal. Gelingt der Upload und scheitert DAS hier, ist
                # F3 auf diese eine Operation eingegrenzt.
                rl, _, rerr = curl_ftp(
                    [*reuse.split(), "--list-only",
                     ftp_adresse(server, pfad.rstrip("/") + "/" + ordner) + "/"],
                    konto, passwort, lauf)
                if rl != 0:
                    f(f"FEHLGESCHLAGEN beim AUFLISTEN des neuen Verzeichnisses "
                      f"(curl {rl}).")
                    f("  Hochladen und Anlegen haben funktioniert, das Auflisten "
                      "nicht. Das ist genau die Operation, an der die "
                      "Auslieferungsaktion abbricht (`_openDir` in `ensureDir`) — "
                      "der Befund ist damit auf sie eingegrenzt.")
                    for z in (rerr or "(keine Meldung)").splitlines():
                        f(f"    {z}")
                    fehler = 1
                else:
                    a("  Neues Verzeichnis aufgelistet (die `_openDir`-Stelle).")
            # ---- 2. über HTTPS zurückholen und vergleichen ------------------
            adresse = web_adresse(basis, name)
            status, koerper, netzfehler = hol(adresse)
            a(f"  HTTPS-Abruf {adresse} → {status or 'kein Status'}"
              + (f" ({netzfehler})" if netzfehler else ""))
            if status == 403:
                # 403 IST NICHT "FALSCHES ZIEL", SONDERN "GESPERRT". Die Datei
                # ist da — der Server gibt sie nur nicht heraus. Wer das als
                # falschen Zielpfad meldet, schickt jemanden auf die Suche nach
                # einem Fehler, den es nicht gibt (gemessen 20.09.2026).
                f(f"FEHLGESCHLAGEN: Die Datei liegt im FTP-Ziel und ist unter "
                  f"{adresse} GESPERRT (403) — nicht unauffindbar. Das ist etwas "
                  f"anderes als ein falscher Zielpfad: Hochladen hat funktioniert, "
                  f"nur der Rückweg ist zu.")
                f("  Wahrscheinlich eine Regel des Servers (`.htaccess`, eine "
                  "Sperre des Hosters, ein Botschutz). Der Rundlauf ist damit "
                  "NICHT belegt — aber auch nicht widerlegt.")
                fehler = 1
            elif status != 200:
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
            rc, _, err = curl_ftp([*reuse.split(), "-Q",
                                   f"-DELE {pfad.rstrip('/')}/{name}",
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
            if im_ordner:
                ro, _, oerr = curl_ftp(
                    [*reuse.split(), "-Q",
                     f"-RMD {pfad.rstrip('/')}/{ordner}",
                     ftp_adresse(server, pfad)],
                    konto, passwort, lauf)
                if ro != 0:
                    f(f"WARNUNG: Das Probeverzeichnis {ordner} ließ sich nicht "
                      f"entfernen (curl {ro}). Es liegt jetzt im Zielverzeichnis.")
                    for z in (oerr or "(keine Meldung)").splitlines():
                        f(f"    {z}")
                    fehler = 1
                else:
                    a("  Probeverzeichnis entfernt.")

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
    import contextlib
    import io as _io

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

    # ---- Wie kam die Datenverbindung zustande? ----
    for text, soll, was in (
        ("> EPSV\n< 229 Entering Extended Passive Mode (|||51234|)",
         "EPSV, Antwort 229, Port 51234", "EPSV sauber, mit Port"),
        ("< 229 Entering Extended Passive Mode (|||51234|)",
         "EPSV, Antwort 229, Port 51234", "nur die 229-Antwort — zählt trotzdem als EPSV"),
        ("* Failed EPSV, disabling EPSV\n> PASV\n< 227 Entering Passive Mode (1,2,3,4,200,1)",
         "PASV — NACH einem EPSV-Fehlschlag (curl ist zurückgefallen)",
         "der RÜCKFALL wird als solcher benannt"),
        ("> PASV\n< 227 Entering Passive Mode (1,2,3,4,200,1)",
         "PASV, Antwort 227 (1,2,3,4,200,1)", "PASV ohne EPSV-Versuch"),
        ("", "nicht feststellbar (keine ausführliche Ausgabe)",
         "leere Ausgabe → nicht feststellbar, nicht geraten"),
    ):
        pruefe(datenkanal(text) == soll, f"Datenkanal: {was}", datenkanal(text))

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
    ohne = any(SCHALTER_OHNE_SITZUNG in b for b in l.befehle)
    pruefe(ohne, "`--ohne-sitzungswiederverwendung` steht im AUSGEFÜHRTEN Befehl")
    pruefe(all(SCHALTER_TLS_PFLICHT in b for b in l.befehle if "--help" not in b),
           "…und `--ssl-reqd` steht TROTZDEM dabei — TLS bleibt Pflicht")
    merker.clear()
    l = LaufMitInhalt()
    probe("https://a.example", "h", "/", "k", "p", True, l, hol_ok)
    pruefe(not any(SCHALTER_OHNE_SITZUNG in b for b in l.befehle),
           "…und im Normalbetrieb steht der Gegenschalter NICHT da")

    # DIE LAGE, DIE AM 20.09.2026 GEFEHLT HAT. Dass ein Schalter im Befehl
    # landet, sagt nichts darüber, ob `curl` ihn kennt — `--no-ssl-session-reuse`
    # landete brav und wurde abgelehnt. Diese Frage geht an `curl` selbst.
    if curl_da():
        for opt in (SCHALTER_TLS_PFLICHT, SCHALTER_OHNE_SITZUNG):
            pruefe(curl_kennt(opt), f"`curl` dieser Fassung KENNT `{opt}`")
        pruefe(not curl_kennt("--no-ssl-session-reuse"),
               "…und die Gegenprobe: den erfundenen Schalter kennt er nicht")
    else:
        pruefe(True, "`curl` nicht vorhanden — Schalterprobe übersprungen und GESAGT")

    # ---- Aufräumen ----
    l = Lauf(liste="zielprobe-neu.txt\nindex.php\n.zielprobe-alt.txt\n")
    weg = aufraeumen("h", "/", "k", "passwort", l)
    pruefe(weg == ["zielprobe-neu.txt", ".zielprobe-alt.txt"],
           "Reste früherer Proben werden erkannt — und nur die", str(weg))
    pruefe("index.php" not in weg, "…`index.php` fasst sie nicht an")
    pruefe(".zielprobe-alt.txt" in weg,
           "…auch die Reste der alten Fassung MIT Punkt — sonst bleiben sie "
           "unsichtbar liegen")
    pruefe(not PRAEFIX.startswith("."),
           "Der Probedateiname beginnt NICHT mit einem Punkt", PRAEFIX)

    # 403 auf dem Rückweg ist „gesperrt", nicht „falsches Ziel".
    merker.clear()
    p403 = _io.StringIO()
    with contextlib.redirect_stderr(p403):
        probe("https://a.example", "h", "/", "k", "p", True, LaufMitInhalt(),
              lambda a, zeitgrenze=0: (403, b"", ""))
    t403 = p403.getvalue()
    pruefe("GESPERRT (403)" in t403, "403 wird als Sperre gemeldet")
    pruefe("zeigen nicht auf" not in t403,
           "…und NICHT als falscher Zielpfad — das wäre eine Falschdiagnose")

    # ---- Das Passwort steht NICHT in der Befehlszeile ----
    l = Lauf()
    curl_ftp(["--ssl-reqd", "ftp://h/"], "k", "streng-geheim", l)
    inzeile = any("streng-geheim" in str(x) for b in l.befehle for x in b)
    pruefe(not inzeile, "Das Passwort steht in keinem Befehlszeilenargument "
                        "(/proc/<pid>/cmdline ist lesbar)")

    # ---- Der Rundlauf DURCH EIN NEUES VERZEICHNIS (`ensureDir`) ----
    #
    # OHNE DIESE LAGEN WAERE DER ZWEITE RUNDLAUF UNGEPRUEFT -- und genau das
    # war am 20.09.2026 der Fehler beim ersten Trennversuch: Ein Schalter, den
    # niemand gegengeprueft hat, maass vier Laeufe lang nichts.
    merker.clear()
    l = LaufMitInhalt()
    rc = probe("https://a.example", "h", "/", "k", "p", True, l, hol_ok,
               im_ordner=True)
    pruefe(rc == 0, "Rundlauf durch ein neues Verzeichnis: gelingt gegen die Attrappe")
    pruefe(any("--ftp-create-dirs" in b for b in l.befehle),
           "…`--ftp-create-dirs` steht im AUSGEFÜHRTEN Befehl (das `ensureDir`-Gegenstück)")
    pruefe(any("--list-only" in b and any(str(x).count("/") >= 3 for x in b)
               for b in l.befehle),
           "…und das neue Verzeichnis wird AUFGELISTET (die `_openDir`-Stelle)")
    pruefe(any(any(str(x).startswith("-RMD") for x in b) for b in l.befehle),
           "…und am Ende wieder entfernt")
    pruefe(curl_kennt("--ftp-create-dirs") if curl_da() else True,
           "`curl` dieser Fassung KENNT `--ftp-create-dirs`")

    # Die Gegenprobe: Scheitert das AUFLISTEN, ist der Lauf rot — und sagt,
    # dass genau diese Operation die der Auslieferungsaktion ist.
    class LaufListeRot(LaufMitInhalt):
        def __call__(self, befehl, **kw):
            e = super().__call__(befehl, **kw)
            if "--list-only" in befehl and any("zielprobe-" in str(x)
                                               and str(x).count("/") >= 3
                                               for x in befehl):
                e.returncode, e.stderr = 5, "425 Unable to build data connection"
            return e

    merker.clear()
    pl = _io.StringIO()
    with contextlib.redirect_stderr(pl):
        rcl = probe("https://a.example", "h", "/", "k", "p", True,
                    LaufListeRot(), hol_ok, im_ordner=True)
    tl = pl.getvalue()
    pruefe(rcl == 1, "Scheitert das Auflisten des neuen Verzeichnisses → rot")
    pruefe("_openDir" in tl,
           "…und die Meldung nennt die Stelle der Auslieferungsaktion")
    pruefe("425" in tl, "…und gibt die Servermeldung wörtlich aus", "425 …")

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
    if a.ohne_sitzungswiederverwendung and not curl_kennt(SCHALTER_OHNE_SITZUNG):
        # LIEBER GAR NICHT MESSEN ALS FALSCH. Kennt dieses `curl` den
        # Schalter nicht, liefe die Probe in der VORGABE-Betriebsart und
        # meldete am Ende ein Ergebnis, das über den Trennversuch nichts
        # sagt — schlimmer: eines, das wie ein Ergebnis aussieht.
        print(f"ABBRUCH: Dieses `curl` kennt `{SCHALTER_OHNE_SITZUNG}` nicht. Der "
              f"Trennversuch ließe sich damit nicht fahren, und ein Lauf in der "
              f"Vorgabe-Betriebsart sähe aus wie ein Ergebnis. Fassung: "
              f"`curl --version | head -1`.", file=sys.stderr)
        return 2
    if not curl_da():
        print("ABBRUCH: `curl` ist auf diesem Läufer nicht vorhanden. Die Probe "
              "benutzt ihn ausdrücklich als ZWEITEN FTPS-Client neben der "
              "Auslieferungsaktion — ein Rückfall auf dieselbe Bibliothek "
              "beantwortete die Frage nicht, für die es sie gibt.",
              file=sys.stderr)
        return 2
    # BEIDE RUNDLAEUFE, IMMER. Der flache zuerst, weil er billig ist und die
    # Grundlagen klaert; der durch ein Verzeichnis danach, weil er die Stelle
    # misst, an der die Auslieferung stirbt. Ein Fehlschlag im ersten macht
    # den zweiten nicht ueberfluessig -- beide laufen, beide melden.
    rc1 = probe(a.basis, a.ftp_server, a.ftp_pfad, a.ftp_konto, a.ftp_pass,
                not a.ohne_sitzungswiederverwendung)
    rc2 = probe(a.basis, a.ftp_server, a.ftp_pfad, a.ftp_konto, a.ftp_pass,
                not a.ohne_sitzungswiederverwendung, im_ordner=True)
    if rc1 == 0 and rc2 == 0:
        sag("\nBEIDE Rundläufe gelungen — flach UND durch ein neues Verzeichnis.",
            [a.ftp_pass, a.ftp_konto])
    elif rc1 == 0:
        sag("\nDer flache Rundlauf gelingt, der durch ein NEUES VERZEICHNIS "
            "nicht. Das ist der Unterschied, auf den es ankommt: Die "
            "Auslieferung legt Verzeichnisse an.", [a.ftp_pass, a.ftp_konto], True)
    return max(rc1, rc2)


if __name__ == "__main__":
    try:
        sys.exit(main(sys.argv[1:]))
    except Exception as ex:                          # noqa: BLE001
        print(f"Die Zielprobe selbst ist gescheitert: {ex}", file=sys.stderr)
        sys.exit(2)
