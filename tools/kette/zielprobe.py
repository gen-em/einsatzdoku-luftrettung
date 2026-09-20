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
denselben Client benutzt, könnte diese Frage nicht beantworten.

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
Sie räumt Reste frueherer Proben weg (`NLST` auf das Zielverzeichnis, alles
mit dem Praefix `.zielprobe-`), und sie loescht ihre eigene Datei **auch im
Fehlerfall** -- sonst liegt nach dem dritten roten Lauf Muell im Webroot, den
jeder abrufen kann. Servermeldungen gibt sie **wörtlich** aus: Eine
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

# WIE VIEL ZEIT EIN ZIEL DER MENGENPROBE BEKOMMT (20.09.2026, gemessen).
# Der erste echte Lauf hat 21 Verzeichnisse in 60 Sekunden angelegt -- rund
# 2,9 s je Stueck, weil jedes ein MKD, ein CWD und eine eigene Datenverbindung
# braucht. Mit der festen Minute wurde `curl` mitten im Lauf abgewuergt, und
# die Probe meldete einen Abbruch, den NICHT der Server verursacht hatte.
# Eine Zeitgrenze, die das Messgeraet toetet, misst das Messgeraet.
MENGE_JE_ZIEL_S = 8
MENGE_GRUNDZEIT_S = 30

# EIGENER RUECKGABEWERT FUER "ZEITGRENZE". `curl` gibt ihn nie zurueck; er
# sagt, dass WIR abgebrochen haben und nicht der Server. Der Unterschied ist
# der ganze Zweck der Probe.
CURL_ZEITGRENZE = -1

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
# Lauf räumt Reste des vorigen weg. Eine liegengebliebene Probedatei ist eine
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
             lauf=subprocess.run,
             zeitgrenze: int = FTP_ZEITGRENZE_S) -> tuple[int, str, str]:
    """Ein `curl`-Aufruf gegen FTPS. Gibt (Rückgabewert, stdout, stderr) zurück.

    DAS PASSWORT GEHT UEBER `--config -` UND NICHT UEBER DIE BEFEHLSZEILE.
    Auf einem geteilten Laeufer liest jeder Prozess `/proc/<pid>/cmdline`;
    `-u konto:passwort` stuende dort im Klartext. Dass hier heute niemand
    mitliest, ist kein Argument — es ist eine Zusage, die nichts kostet.
    """
    befehl = [curl_da() or "curl", "--config", "-", *argumente]
    try:
        e = lauf(befehl, input=f'user = "{konto}:{passwort}"\n',
                 capture_output=True, text=True, timeout=zeitgrenze)
    except subprocess.TimeoutExpired as ex:
        # DIE ANGEFANGENE AUSGABE IST DAS WERTVOLLSTE AM ABBRUCH, und sie ging
        # bis zum 20.09.2026 verloren: Die Ausnahme flog bis nach oben und
        # druckte dort die Befehlszeile mit achtzig Adressen -- alles, nur
        # nicht die Antwort des Servers. `TimeoutExpired` traegt das bereits
        # Gelesene mit sich; hier wird es herausgeholt.
        def text(x):
            return x.decode("utf-8", "replace") if isinstance(x, bytes) else (x or "")
        return CURL_ZEITGRENZE, text(ex.stdout), text(ex.stderr)
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


# WIE VIELE RUNDEN DAS AUFRAEUMEN HOECHSTENS DREHT. Eine Runde raeumt alles,
# was in einem Rutsch geht; scheitert ein Befehl, bricht `curl` die Kette ab
# und der Rest bleibt fuer die naechste Runde liegen. Ohne Obergrenze liefe
# das gegen einen Server, der gar nichts loescht, endlos.
AUFRAEUM_RUNDEN = 4


def _reste(server: str, pfad: str, konto: str, passwort: str,
           lauf=subprocess.run) -> list[str]:
    """Was liegt im Zielverzeichnis, das von einer Probe stammt?"""
    rc, aus, _ = curl_ftp(["--ssl-reqd", "--list-only",
                           ftp_adresse(server, pfad)],
                          konto, passwort, lauf)
    if rc != 0:
        return []
    return [z.strip().rsplit("/", 1)[-1] for z in aus.splitlines()
            if z.strip().rsplit("/", 1)[-1].startswith((PRAEFIX, PRAEFIX_ALT))]


def aufraeumen(server: str, pfad: str, konto: str, passwort: str,
               lauf=subprocess.run) -> list[str]:
    """Reste früherer Proben wegräumen. Gibt die verschwundenen Namen zurück.

    WARUM ES DAS GIBT: Ein Lauf, der zwischen Upload und Loeschen abbricht
    (Zeitgrenze des Jobs, Abbruch von Hand), laesst seine Datei liegen. Ohne
    diesen Schritt sammeln sich die Reste im Webroot — und ein abgebrochener
    Lauf ist genau die Lage, in der niemand nachsieht.

    ALLE LOESCHBEFEHLE GEHEN IN EINEN EINZIGEN `curl`-AUFRUF, und das ist
    eine Behebung vom 20.09.2026. Vorher war es einer je Befehl: bei der
    Mengenprobe mit 80 Verzeichnissen also 160 Aufrufe, jeder mit eigenem
    TLS-Aufbau. Der erste echte Lauf hat dafuer Minuten gebraucht — bei 500
    Verzeichnissen waere er in die Zeitgrenze des Jobs gelaufen und haette
    genau das liegengelassen, was er wegraeumen soll.

    GEMESSEN WIRD NACH, NICHT GEGLAUBT. Der Rueckgabewert des Loeschaufrufs
    sagt wenig: `curl` bricht die Befehlskette beim ersten Fehler ab (eine
    fehlende Datei genuegt), der Rest bleibt ungetan. Deshalb wird danach
    neu aufgelistet, und zurueckgegeben wird, was tatsaechlich verschwunden
    ist. Solange jede Runde etwas wegbekommt, wird weitergedreht — hoechstens
    `AUFRAEUM_RUNDEN` mal.
    """
    anfangs = _reste(server, pfad, konto, passwort, lauf)
    offen = list(anfangs)
    for _ in range(AUFRAEUM_RUNDEN):
        if not offen:
            break
        argumente = ["--ssl-reqd", "--list-only"]
        for name in offen:
            if name.endswith(".txt"):
                argumente += ["-Q", f"-DELE {pfad.rstrip('/')}/{name}"]
            else:
                # EIN REST OHNE `.txt` IST EIN PROBEVERZEICHNIS. Erst die
                # Datei darin, dann das Verzeichnis — `RMD` auf ein volles
                # Verzeichnis weist jeder Server ab. Der innere Name ist fest
                # (`ORDNERDATEI`), genau damit dieses Aufraeumen ihn kennt,
                # ohne zu suchen.
                argumente += ["-Q", f"-DELE {pfad.rstrip('/')}/{name}/{ORDNERDATEI}",
                              "-Q", f"-RMD {pfad.rstrip('/')}/{name}"]
        argumente.append(ftp_adresse(server, pfad))
        curl_ftp(argumente, konto, passwort, lauf)
        vorher = len(offen)
        offen = [n for n in _reste(server, pfad, konto, passwort, lauf)
                 if n in anfangs]
        if len(offen) >= vorher:
            # KEIN FORTSCHRITT — weitere Runden aendern daran nichts.
            break
    return [n for n in anfangs if n not in offen]

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


def mengenprobe(basis: str, server: str, pfad: str, konto: str, passwort: str,
                anzahl: int, lauf=subprocess.run) -> int:
    """Viele Verzeichnisse und Dateien in EINER FTP-Sitzung. 0 = gelungen.

    WARUM ES DAS GIBT (20.09.2026, nach fünf ergebnislosen Trennversuchen).
    Ausgeschlossen sind Läuferabbild, Node-Fassung, Zertifikat, die
    TLS-Sitzungswiederverwendung, das Anlegen eines Verzeichnisses und der
    Weg zum Datenkanal (gemessen: sauberes EPSV, kein Rückfall).

    **Der Unterschied, der übrig bleibt, ist die Sitzung selbst.** Die
    Zielprobe ruft `curl` je Operation einmal auf — jede Operation bekommt
    eine EIGENE Steuerverbindung, eine eigene Anmeldung, einen eigenen
    TLS-Aufbau. Die Auslieferungsaktion hält EINE Verbindung offen und fährt
    688 Dateien und 62 Verzeichnisse darüber.

    Das ist keine Kleinigkeit: Ein Server, der die zweite oder dritte
    Datenverbindung EINER Sitzung abweist (Zeitgrenze, Portbereich,
    `MaxConnectionsPerHost`, ein Ratenschutz), sieht in der Zielprobe wie ein
    gesunder Server aus — sie fragt ihn ja jedes Mal neu.

    Diese Probe stellt es nach: EIN `curl`-Aufruf, `anzahl` Verzeichnisse,
    je eine Datei, über eine einzige Verbindung.

    SIE LÄUFT NIE VON SELBST. Sie legt bis zu 500 Verzeichnisse auf einem
    echten Server an. In der Kette steht sie hinter zwei Riegeln: der Eingabe
    `probelauf_mengenprobe` des Arbeitslaufs „Auslieferung", die nur zusammen
    mit dem Häkchen `probelauf` wirkt. Ein Tag-Lauf und ein Push haben das
    Feld nicht. Hier schaltet sie `--mengenprobe N` ein, und sie tritt dann
    AN DIE STELLE der beiden Rundläufe.
    """
    geheim = [passwort, konto]
    def a(text): sag(text, geheim)
    def f(text): sag(text, geheim, True)

    marke = secrets.token_hex(6)
    inhalt = secrets.token_hex(8).encode("ascii")
    a(f"Mengenprobe gegen {basis.rstrip('/')}  —  {anzahl} Verzeichnisse in EINER Sitzung")
    a(f"  FTPS-Ziel:      {ftp_adresse(server, pfad)}")
    a(f"  Namensmarke:    {PRAEFIX}{marke}-NN")

    weg = aufraeumen(server, pfad, konto, passwort, lauf)
    a(f"  Reste weggeräumt: {len(weg)}")

    quelle = os.path.join(os.environ.get("RUNNER_TEMP", "/tmp"),
                          f"{PRAEFIX}{marke}.bin")
    ordner = [f"{PRAEFIX}{marke}-{i:03d}" for i in range(1, anzahl + 1)]
    # VORBELEGT, WEIL DAS `finally` IMMER LAEUFT: Scheitert schon das Schreiben
    # der Quelldatei, gaebe ein unbelegtes `rc` hinter dem `finally` einen
    # NameError statt eines Befunds -- und ein Werkzeug, das beim Scheitern
    # abstuerzt, misst nichts.
    rc = 1
    try:
        with open(quelle, "wb") as fh:
            fh.write(inhalt)

        # EIN AUFRUF, VIELE ZIELE. `curl` haelt die Steuerverbindung offen und
        # fuehrt sie der Reihe nach ab -- genau wie die Auslieferungsaktion.
        # DIE ZEITGRENZE WAECHST MIT DER ZAHL DER ZIELE. Mit der festen
        # Minute hat der erste echte Lauf sein eigenes `curl` nach 21 von 80
        # Verzeichnissen erschlagen und das als Abbruch gemeldet -- ein
        # Befund ueber die Probe, nicht ueber den Server.
        grenze = MENGE_GRUNDZEIT_S + MENGE_JE_ZIEL_S * anzahl
        a(f"  Zeitgrenze:     {grenze} s ({MENGE_GRUNDZEIT_S} + "
          f"{MENGE_JE_ZIEL_S} je Ziel)")
        argumente = [SCHALTER_TLS_PFLICHT, "--verbose", "--ftp-create-dirs",
                     # `curl` BEENDET SICH SELBST, kurz bevor wir ihn toeten
                     # wuerden. Dann schreibt er seine eigene Schlusszeile,
                     # statt mitten im Satz zu verstummen.
                     "--max-time", str(grenze - 5)]
        for o in ordner:
            argumente += ["--upload-file", quelle,
                          ftp_adresse(server, pfad, f"{o}/{ORDNERDATEI}")]
        rc, _, err = curl_ftp(argumente, konto, passwort, lauf,
                              zeitgrenze=grenze)

        fertig = len(re.findall(r"226 ", err or ""))
        a(f"  Datenkanal:     {datenkanal(err)}")
        a(f"  Übertragungen abgeschlossen (226): {fertig} von {anzahl}")
        if rc == CURL_ZEITGRENZE:
            # DAS IST KEIN BEFUND UEBER DEN SERVER, und es wird auch nicht so
            # gemeldet. Wer hier "der Server hat abgewiesen" liest, sucht am
            # falschen Ende.
            f(f"ABGEBROCHEN VON DER PROBE SELBST nach {grenze} s — NICHT vom "
              f"Server. {fertig} von {anzahl} Übertragungen waren fertig.")
            f("  Der Server hat nichts abgewiesen; die Zeit war zu knapp. "
              "Entweder mit einer kleineren Zahl wiederholen, oder "
              f"`MENGE_JE_ZIEL_S` erhöhen (steht auf {MENGE_JE_ZIEL_S} s; "
              f"gemessen wurden rund {grenze / max(fertig, 1):.1f} s je Ziel).")
        elif rc != 0:
            f(f"FEHLGESCHLAGEN (curl {rc}) nach {fertig} von {anzahl} "
              f"Übertragungen.")
            f("  DAS IST DAS ERGEBNIS, AUF DAS ES ANKOMMT: Einzeln geht jede "
              "dieser Operationen durch — in EINER Sitzung nicht. Die Zahl "
              "oben sagt, bei der wievielten Schluss war.")
            f("  Servermeldung, wörtlich (letzte 40 Zeilen):")
            for z in (err or "(keine)").splitlines()[-40:]:
                f(f"    {z}")
        else:
            a(f"  Alle {anzahl} Verzeichnisse in einer Sitzung angelegt und "
              f"beschrieben.")
    finally:
        try:
            os.unlink(quelle)
        except OSError:
            pass
        # AUFRAEUMEN IST HIER PFLICHT UND NICHT KUER: Es liegen sonst bis zu
        # `anzahl` Verzeichnisse auf einem echten Server.
        rest = aufraeumen(server, pfad, konto, passwort, lauf)
        a(f"  Aufgeräumt: {len(rest)} Verzeichnisse entfernt.")
        # NACHGEMESSEN, NICHT GERECHNET. Bis zum 20.09.2026 stand hier
        # `[o for o in ordner if o not in rest]` — die Zahl der GEWOLLTEN
        # minus der weggeraeumten. Nach dem Abbruch bei 21 von 80 meldete
        # sie „59 Verzeichnisse konnten nicht entfernt werden", und 59 davon
        # hatte es nie gegeben. Eine Warnung, die auf dem Server nichts
        # findet, schickt jemanden suchen.
        uebrig = [n for n in _reste(server, pfad, konto, passwort, lauf)
                  if n.startswith(f"{PRAEFIX}{marke}")]
        if uebrig:
            f(f"WARNUNG: {len(uebrig)} Probeverzeichnisse liegen noch auf dem "
              f"Server. Erstes: {uebrig[0]}. Der nächste Lauf nimmt sie mit.")
    return 0 if rc == 0 else 1


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
        """Stellt `subprocess.run` nach. `dele` zählt die Löschbefehle.

        SIE FUEHRT SEIT DEM 20.09.2026 BUCH UEBER DAS ZIELVERZEICHNIS. Vorher
        gab sie immer dieselbe Dateiliste zurueck, egal was geloescht worden
        war — gegen eine solche Attrappe sah ein Aufraeumen, das NICHTS tut,
        genauso aus wie eines, das alles wegraeumt. Jetzt verschwinden die
        geloeschten Namen aus der Liste, und das Werkzeug misst nach, statt
        dem Rueckgabewert zu glauben.
        """

        def __init__(self, rc_upload=0, rc_dele=0, liste="", verbose=""):
            self.rc_upload, self.rc_dele = rc_upload, rc_dele
            self.liste, self.verbose = liste, verbose
            self.dele = 0
            self.loeschaufrufe = 0
            self.befehle: list[list[str]] = []

        def _weg(self, namen: list[str]) -> None:
            drin = [z for z in self.liste.splitlines() if z.strip()]
            self.liste = "\n".join(z for z in drin
                                   if z.strip().rsplit("/", 1)[-1] not in namen)

        def __call__(self, befehl, **kw):
            self.befehle.append(befehl)

            class E:
                pass
            e = E()
            loeschen = [str(x) for x in befehl
                        if str(x).startswith(("-DELE ", "-RMD "))]
            if loeschen:
                # EIN AUFRUF, VIELE BEFEHLE — so schickt das Werkzeug sie seit
                # der Buendelung. `dele` zaehlt die BEFEHLE, `loeschaufrufe`
                # die Aufrufe; erst beide zusammen zeigen, ob gebuendelt wurde.
                self.dele += len(loeschen)
                self.loeschaufrufe += 1
                if self.rc_dele == 0:
                    self._weg([b.split(" ", 1)[1].rsplit("/", 1)[-1]
                               if b.startswith("-RMD ")
                               else b.split(" ", 1)[1].rsplit("/", 2)[-2]
                               if b.endswith("/" + ORDNERDATEI)
                               else b.split(" ", 1)[1].rsplit("/", 1)[-1]
                               for b in loeschen])
                e.returncode, e.stdout, e.stderr = self.rc_dele, self.liste, "550 nope"
            elif "--list-only" in befehl:
                e.returncode, e.stdout, e.stderr = 0, self.liste, ""
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

    # DIE BUENDELUNG DES AUFRAEUMENS (20.09.2026). Der erste echte Lauf der
    # Mengenprobe hat Minuten im Aufraeumen verbracht: ein `curl`-Aufruf je
    # Befehl, bei 80 Verzeichnissen 160 TLS-Aufbauten. Bei 500 waere er in
    # die Zeitgrenze des Jobs gelaufen -- und haette liegengelassen, was er
    # wegraeumen soll.
    lb = Lauf(liste="\n".join(f"{PRAEFIX}x-{i:03d}" for i in range(1, 21)))
    weg = aufraeumen("h", "/", "k", "passwort", lb)
    pruefe(len(weg) == 20, "Aufräumen: 20 Probeverzeichnisse verschwinden",
           f"{len(weg)}")
    pruefe(lb.loeschaufrufe == 1,
           "…in EINEM Löschaufruf, nicht in 40 — sonst kostet die Mengenprobe "
           "mehr Zeit im Aufräumen als in der Messung",
           f"{lb.loeschaufrufe} Aufruf(e) für {lb.dele} Befehle")
    pruefe(lb.dele == 40,
           "…und es sind trotzdem 40 Befehle (je Datei und Verzeichnis einer)",
           f"{lb.dele}")

    # GEMESSEN WIRD NACH, NICHT GEGLAUBT. Loescht der Server nichts, darf das
    # Aufraeumen nicht melden, es habe geraeumt -- sonst sucht niemand nach
    # den Resten, die im Webroot liegen.
    lz = Lauf(rc_dele=9, liste=f"{PRAEFIX}y-001\n{PRAEFIX}y-002\n")
    weg_z = aufraeumen("h", "/", "k", "passwort", lz)
    pruefe(weg_z == [],
           "Löscht der Server nichts, meldet das Aufräumen auch nichts",
           str(weg_z))
    pruefe(lz.loeschaufrufe <= AUFRAEUM_RUNDEN,
           "…und es dreht nicht endlos: ohne Fortschritt ist nach einer Runde "
           "Schluss", f"{lz.loeschaufrufe} Runde(n), Grenze {AUFRAEUM_RUNDEN}")

    # Und die Gegenprobe zur Buchfuehrung der Attrappe selbst: Ohne sie waere
    # die Lage oben wertlos -- eine Liste, die sich nie aendert, laesst jedes
    # Aufraeumen gleich aussehen.
    lp = Lauf(liste=f"{PRAEFIX}z-001\nindex.php\n")
    aufraeumen("h", "/", "k", "passwort", lp)
    pruefe("index.php" in lp.liste and f"{PRAEFIX}z-001" not in lp.liste,
           "Die Attrappe führt wirklich Buch: die Probe ist weg, `index.php` "
           "steht noch da", repr(lp.liste))

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

    # ---- Die Mengenprobe: EINE Sitzung, viele Verzeichnisse ----
    #
    # WARUM SIE HIER MITGEPRUEFT WIRD: Sie ist das Mittel, mit dem der
    # verbliebene Unterschied zwischen Zielprobe und Auslieferungsaktion
    # gemessen wird -- eine lange Sitzung gegen viele kurze. Fände sie in
    # Wahrheit mehrere Sitzungen auf, mäße sie dasselbe wie der Rundlauf und
    # könnte den Unterschied nie zeigen. Genau das prueft die erste Lage.
    class LaufMenge(Lauf):
        """Merkt sich die angelegten Verzeichnisse und listet sie danach auf."""

        def __init__(self, rc_upload=0, verbose=""):
            super().__init__(rc_upload=rc_upload, verbose=verbose)
            self.angelegt: list[str] = []
            self.hochladebefehle: list[list[str]] = []

        def __call__(self, befehl, **kw):
            if "--upload-file" in befehl:
                self.hochladebefehle.append(befehl)
                for x in befehl:
                    t = str(x)
                    if t.startswith("ftp://") and t.endswith("/" + ORDNERDATEI):
                        self.angelegt.append(t.rsplit("/", 2)[-2])
                self.liste = "\n".join(self.angelegt)
            return super().__call__(befehl, **kw)

    lm = LaufMenge(verbose=("> EPSV\n< 229 Entering Extended Passive Mode "
                            "(|||51234|)\n" + "226 Transfer complete\n" * 5))
    pm = _io.StringIO()
    with contextlib.redirect_stdout(pm):
        rcm = mengenprobe("https://a.example", "h", "/", "k", "p", 5, lm)
    tm = pm.getvalue()
    pruefe(rcm == 0, "Mengenprobe: Regelfall gelingt gegen die Attrappe")
    pruefe(len(lm.hochladebefehle) == 1,
           "…und alle Ziele stehen in EINEM curl-Aufruf — sonst misst sie "
           "wieder viele kurze Sitzungen", f"{len(lm.hochladebefehle)} Aufruf(e)")
    pruefe(lm.hochladebefehle[0].count("--upload-file") == 5,
           "…mit genau so vielen Zielen, wie verlangt waren",
           f"{lm.hochladebefehle[0].count('--upload-file')}")
    pruefe("--ftp-create-dirs" in lm.hochladebefehle[0],
           "…und `--ftp-create-dirs` steht dabei (das `ensureDir`-Gegenstück)")
    pruefe(SCHALTER_TLS_PFLICHT in lm.hochladebefehle[0],
           "…und `--ssl-reqd` ebenfalls — TLS bleibt auch hier Pflicht")
    pruefe("226): 5 von 5" in tm,
           "…die Zahl der abgeschlossenen Übertragungen wird GENANNT", "5 von 5")
    pruefe("EPSV" in tm, "…und der Weg zum Datenkanal steht in JEDEM Lauf da")
    pruefe(lm.dele >= 5,
           "…und am Ende wird jedes Verzeichnis wieder weggeräumt",
           f"{lm.dele} Löschbefehl(e)")
    pruefe("WARNUNG" not in tm, "…ohne Warnung ueber liegengebliebene Reste")

    # DIE GEGENPROBE, UM DIE ES GEHT: Die Sitzung bricht mittendrin ab. Dann
    # muss die Probe rot sein, sagen, bei der wievielten Schluss war, den
    # Servertext wörtlich zeigen -- und TROTZDEM aufraeumen.
    lr = LaufMenge(rc_upload=18,
                   verbose=("> EPSV\n< 229 Entering Extended Passive Mode "
                            "(|||51234|)\n" + "226 Transfer complete\n" * 2
                            + "425 Unable to build data connection\n"))
    pr_ = _io.StringIO()
    pf_ = _io.StringIO()
    with contextlib.redirect_stdout(pr_), contextlib.redirect_stderr(pf_):
        rcr = mengenprobe("https://a.example", "h", "/", "k", "p", 5, lr)
    tr = pr_.getvalue() + pf_.getvalue()
    pruefe(rcr == 1, "Mengenprobe: bricht die Sitzung ab → rot")
    pruefe("226): 2 von 5" in tr,
           "…und sagt, bei der wievielten Übertragung Schluss war", "2 von 5")
    pruefe("425 Unable to build data connection" in tr,
           "…und gibt die Servermeldung wörtlich aus")
    pruefe("Einzeln geht jede" in tr,
           "…und benennt, WORAUF es ankommt: einzeln ginge es durch")
    pruefe(lr.dele >= 2,
           "…und räumt das bereits Angelegte trotzdem weg",
           f"{lr.dele} Löschbefehl(e)")

    # DIE ZEITGRENZE ALS BEFUND (20.09.2026). Der erste echte Lauf der
    # Mengenprobe wurde von der eigenen festen Minute erschlagen. Die Ausnahme
    # flog bis nach oben und druckte dort achtzig Adressen -- alles ausser der
    # Antwort des Servers. Und die Meldung sah aus wie ein Abbruch DURCH den
    # Server; das ist die eine Falschdiagnose, die diese Probe nie stellen darf.
    class LaufZeit(Lauf):
        """Stellt eine Zeitgrenze nach -- mit bereits gelesener Ausgabe."""

        def __call__(self, befehl, **kw):
            self.befehle.append(befehl)
            if "--upload-file" in befehl:
                raise subprocess.TimeoutExpired(
                    befehl, kw.get("timeout", 0),
                    output="",
                    stderr="> EPSV\n< 229 Entering Extended Passive Mode (|||5|)\n"
                           + "226 Transfer complete\n" * 21)
            return super().__call__(befehl, **kw)

    rc_z, aus_z, err_z = curl_ftp(["--ssl-reqd", "--upload-file", "x",
                                   "ftp://h/a"], "k", "p", LaufZeit())
    pruefe(rc_z == CURL_ZEITGRENZE,
           "Zeitgrenze: eigener Rückgabewert statt einer Ausnahme", str(rc_z))
    pruefe(err_z.count("226 ") == 21,
           "…und die bereits gelesene Ausgabe bleibt erhalten — sie ist das "
           "Wertvollste am Abbruch", f"{err_z.count('226 ')} Übertragungen")

    lzp = LaufZeit()
    pz_ = _io.StringIO()
    pzf = _io.StringIO()
    with contextlib.redirect_stdout(pz_), contextlib.redirect_stderr(pzf):
        rcz = mengenprobe("https://a.example", "h", "/", "k", "p", 80, lzp)
    tz = pz_.getvalue() + pzf.getvalue()
    pruefe(rcz == 1, "Mengenprobe: Zeitgrenze → rot")
    pruefe("226): 21 von 80" in tz,
           "…und sagt, wie weit sie gekommen war", "21 von 80")
    pruefe("NICHT vom Server" in tz,
           "…und sagt AUSDRÜCKLICH, dass NICHT der Server abgebrochen hat — "
           "das ist die Falschdiagnose, die sie nie stellen darf")
    pruefe("Einzeln geht jede" not in tz,
           "…und behauptet gerade NICHT, die Sitzung sei die Ursache")
    hoch = [b for b in lzp.befehle if "--upload-file" in b]
    pruefe(hoch and "--max-time" in hoch[0],
           "…`curl` bekommt eine eigene Zeitgrenze, damit er sich selbst "
           "beendet, statt mitten im Satz zu verstummen")
    i = hoch[0].index("--max-time")
    pruefe(int(hoch[0][i + 1]) < MENGE_GRUNDZEIT_S + MENGE_JE_ZIEL_S * 80,
           "…und sie liegt UNTER unserer — sonst käme sie nie zum Zuge",
           f"{hoch[0][i + 1]} s")

    # Und die Grenzen der Zahl -- sie sind nicht Zierde: 500 Verzeichnisse auf
    # einem echten Server anzulegen ist nichts, was ein Vertipper auslösen darf.
    for zahl, soll in ((0, 2), (501, 2), (-1, 2)):
        stumm = _io.StringIO()
        with contextlib.redirect_stdout(stumm), contextlib.redirect_stderr(stumm):
            r = main(["--mengenprobe", str(zahl), "--basis", "https://a.example",
                      "--ftp-server", "h", "--ftp-konto", "k", "--ftp-pass", "p"])
        pruefe(r == soll, f"`--mengenprobe {zahl}` wird abgewiesen, nicht gefahren",
               f"rc {r}")

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
    # `default=None`, NICHT `0`: Sonst ist `--mengenprobe 0` von "gar nicht
    # angegeben" nicht zu unterscheiden, und ein Vertipper fährt still den
    # Rundlauf statt der Mengenprobe -- ein Lauf, der etwas anderes misst,
    # als daransteht.
    p.add_argument("--mengenprobe", type=int, default=None, metavar="N",
                   help="STATT des Rundlaufs: N Verzeichnisse in EINER "
                        "FTP-Sitzung anlegen und beschreiben — die "
                        "Nachstellung der Auslieferung (F3). Legt Dateien auf "
                        "dem Server an und räumt sie wieder weg.")
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
    if a.mengenprobe is not None:
        if a.mengenprobe < 1 or a.mengenprobe > 500:
            print("--mengenprobe braucht eine Zahl zwischen 1 und 500.",
                  file=sys.stderr)
            return 2
        return mengenprobe(a.basis, a.ftp_server, a.ftp_pfad, a.ftp_konto,
                           a.ftp_pass, a.mengenprobe)

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
