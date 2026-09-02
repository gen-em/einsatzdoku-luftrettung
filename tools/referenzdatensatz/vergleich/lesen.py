"""Exportdateien einlesen und in eine vergleichbare Form bringen.

Zwei Formate, ein Ziel: ein Baum aus Listen und Zeichenketten, in dem nichts
mehr steht, was sich bei jedem Export aendert.

  CSV-Archiv (.zip)   LIESMICH.txt, felder.csv, einsaetze.csv,
                      diensttage.csv, ruhezeiten.csv, tracks/*.gpx
  Backup (.edbak)  Container v2/v3 (einteilig) oder v4 (ZIP mit
                      versiegelten Teilen), entsiegelt mit dem
                      Backup-Passwort; das innere JSON traegt die
                      geschuetzten Angaben im KLARTEXT (Backup-Format.md 2) —
                      deshalb ist es vergleichbar, ohne dass Chiffretext
                      angefasst wird.

Fassung 4 wird beim Lesen wieder ZUSAMMENGESETZT: Die Spuren stehen dort als
SPUR1-Blobs in eigenen Teilen und werden hier zu `track`-Listen an ihrem
Einsatz beziehungsweise Ruhesegment aufgeloest. Der gelieferte Baum sieht
danach aus wie der einer einteiligen Datei — sonst muessten normalisieren.py,
vergleichen.py und beide Ausnahmelisten zweimal gepflegt werden, und die Zahl
286 739 waere nicht mehr mit der von gestern vergleichbar.
"""
from __future__ import annotations

import csv
import io
import json
import re
import zipfile

# --------------------------------------------------------------- CSV-Archiv

CSV_DATEIEN = ["felder.csv", "einsaetze.csv", "diensttage.csv", "ruhezeiten.csv"]


def _csv_zeilen(roh: bytes) -> list[dict]:
    """UTF-8 mit BOM, Semikolon, CRLF (Export-Format.md 3.1)."""
    text = roh.decode("utf-8-sig")
    return list(csv.DictReader(io.StringIO(text, newline=""), delimiter=";"))


def lesen_archiv(pfad: str) -> dict:
    aus: dict = {"tracks": {}, "dateiliste": []}
    with zipfile.ZipFile(pfad) as z:
        aus["dateiliste"] = sorted(z.namelist())
        for name in z.namelist():
            if name == "LIESMICH.txt":
                aus["liesmich"] = z.read(name).decode("utf-8-sig")
            elif name in CSV_DATEIEN:
                aus[name[:-4]] = _csv_zeilen(z.read(name))
            elif name.startswith("tracks/") and name.endswith(".gpx"):
                aus["tracks"][name] = z.read(name).decode("utf-8")
    for n in CSV_DATEIEN:
        aus.setdefault(n[:-4], [])
    aus.setdefault("liesmich", "")
    return aus


# ------------------------------------------------------------------ .edbak
#
# Container Fassung 3 (assets/crypto.js, sealBackup/openBackup):
#
#   "EDBAK2" 0x00 0x03 | Flag(1) | Runden(4, big endian) | Salt(16) | IV(12) | AES-GCM
#   AAD: die ersten 13 Bytes (Magie + Fassung + Flag + Runden)
#   Flag: 1 = Inhalt gzip-gepackt, 0 = roh
#
# Fassung 2 (bis Web 4.7.0) traegt keine Rundenzahl; fuer sie gilt 310 000.
# Beide Fassungen werden hier gelesen — eine Referenzdatei soll auch dann noch
# aufgehen, wenn die Anwendung weitergezogen ist. Das ist derselbe Grund, aus
# dem die Rundenzahl ueberhaupt in den Kopf gewandert ist (S7).
#
# Der Inhalt ist bereits KLARTEXT: Der Browser entschluesselt vor dem
# Versiegeln, damit sich das Backup in jedes Konto einspielen laesst
# (Backup-Format.md 2). Dieses Werkzeug fasst also nie einen `edk1:`-
# Chiffretext an — ausser dort, wo ein Backup ihn als `pat_blob`
# unveraendert mitfuehrt, weil sie ihn beim Export nicht lesen konnte.

MAGIE = b"EDBAK2"
RUNDEN_V2 = 310000


def lesen_edbak(pfad: str, passwort: str) -> dict:
    import gzip as gziplib
    from cryptography.hazmat.primitives.ciphers.aead import AESGCM
    from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2HMAC
    from cryptography.hazmat.primitives import hashes

    roh = open(pfad, "rb").read()
    # FASSUNGSWEICHE VORN. Fassung 4 ist ein ZIP und beginnt mit "PK"; die
    # einteiligen Fassungen 2 und 3 mit "EDBAK2". Ohne diese Weiche meldete
    # das Werkzeug "Keine EDBAK2-Datei (Signatur b'PK\x03\x04')" — richtig
    # und unbrauchbar.
    if roh[:2] == b"PK":
        return lesen_edbak_v4(pfad, passwort)
    if roh[:6] != MAGIE:
        raise ValueError(f"Keine EDBAK2-Datei (Signatur {roh[:6]!r})")
    fassung = roh[7]
    flag = roh[8]
    if fassung == 3:
        runden = int.from_bytes(roh[9:13], "big")
        kopf_len = 13
    elif fassung == 2:
        runden = RUNDEN_V2
        kopf_len = 9
    else:
        raise ValueError(f"Unbekannte Containerfassung {fassung}")

    kopf = roh[:kopf_len]
    salt = roh[kopf_len:kopf_len + 16]
    iv = roh[kopf_len + 16:kopf_len + 28]
    ct = roh[kopf_len + 28:]

    kdf = PBKDF2HMAC(algorithm=hashes.SHA256(), length=32, salt=salt,
                     iterations=runden)
    schluessel = kdf.derive(passwort.encode("utf-8"))
    koerper = AESGCM(schluessel).decrypt(iv, ct, kopf)
    if flag == 1:
        koerper = gziplib.decompress(koerper)

    daten = json.loads(koerper.decode("utf-8"))
    # Kennzahlen des Containers als eigener Zweig — sie gehoeren zum Befund,
    # sind aber kein Inhalt und werden getrennt verglichen.
    daten["$container"] = {"fassung": fassung, "gepackt": bool(flag),
                           "runden": runden}
    return daten


# ------------------------------------------------------- .edbak Fassung 4
#
# Aufbau (docs/Backup-Format.md, Konzept S2 3.2):
#
#   ZIP (Speichern ohne Kompression)
#     manifest.edbak         versiegelt, AAD "EDBAK4|manifest"
#     kopf.edbak             Stammdaten und Diensttage
#     eintraege/0001.edbak … Einsaetze und Ruhesegmente, in Fenstern
#     spuren/0001.edbak …    die Spuren als SPUR1-Blobs
#   AAD je Teil: "EDBAK4|<kennung>|<name>|<nr>/<gesamt>"
#
# Teilkopf: "EDBAK2" 0x00 0x04 | Flag(1) | Runden(4, BE) | Salt(16) | IV(12)
# Zusatzdaten (AAD): die ersten 13 Bytes PLUS die Zeichenkette oben.
#
# EINE PBKDF2 JE DATEI. Salz und Rundenzahl stehen in jedem Teilkopf gleich;
# abgeleitet wird einmal, aus dem Manifest, und der Schluessel dann
# weitergereicht. Bei zwoelf Teilen waeren zwoelf Ableitungen zu je 600 000
# Runden reine Wartezeit — und das Werkzeug faehrt sie im Kreislauf mehrfach.

MAGIE_TEIL = b"EDBAK2"
FASSUNG_TEIL = 4
AAD_MARKE = "EDBAK4"


def _teil_kopf(roh: bytes) -> tuple[int, int, bytes]:
    """Flag, Rundenzahl und Salz eines Teils — ohne zu entsiegeln."""
    if len(roh) < 41 or roh[:6] != MAGIE_TEIL:
        raise ValueError("Das ist kein Teil dieser Anwendung")
    fassung = (roh[6] << 8) | roh[7]
    if fassung != FASSUNG_TEIL:
        raise ValueError(f"Unerwartete Teilfassung {fassung} (erwartet {FASSUNG_TEIL})")
    import struct as _s
    return roh[8], _s.unpack(">I", roh[9:13])[0], roh[13:29]


def _teil_oeffnen(schluessel: bytes, roh: bytes, aad_text: str, was: str) -> bytes:
    """Ein Teil entsiegeln. Die AAD bindet seinen Platz — s. Kopfkommentar."""
    import gzip as gziplib
    from cryptography.hazmat.primitives.ciphers.aead import AESGCM
    from cryptography.exceptions import InvalidTag

    flag, _runden, _salz = _teil_kopf(roh)
    kopf = roh[:13]
    iv = roh[29:41]
    ct = roh[41:]
    aad = kopf + aad_text.encode("utf-8")
    try:
        koerper = AESGCM(schluessel).decrypt(iv, ct, aad)
    except InvalidTag:
        # DIE MELDUNG UNTERSCHEIDET. Ein fehlendes, vertauschtes oder fremdes
        # Teil scheitert an derselben Stelle wie ein falsches Passwort; wer
        # das nicht auseinanderhaelt, sucht den Fehler beim Passwort.
        raise ValueError(
            f"{was} liess sich nicht oeffnen: Passwort falsch, oder das Teil "
            f"gehoert nicht an diese Stelle (Zusatzdaten {aad_text!r})") from None
    return gziplib.decompress(koerper) if flag == 1 else koerper


def spur1_lesen(blob: bytes) -> list:
    """SPUR1-Blob zu [[seq, lat, lon, ele|None, ts], ...].

    DAS REZEPT STEHT IN docs/Backup-Format.md UND HIER — und das ist Absicht:
    Ein Werkzeug, das den Vergleich fuehrt, darf sich nicht auf eine
    Dokumentation stuetzen, die es selbst pruefen soll. Wer das Format
    aendert, muss beide Stellen anfassen; genau das ist der Sinn.
    """
    import struct
    import zlib

    if blob[:2] != b"SP":
        raise ValueError("kein SPUR-Blob")
    fassung, _stufe, aufl = blob[2], blob[3], blob[4]
    if fassung != 1 or aufl != 1:
        raise ValueError(f"SPUR-Fassung {fassung}, Aufloesung {aufl}")
    _n_original, n = struct.unpack("<II", blob[5:13])
    roh = zlib.decompress(blob[13:])

    def spalte(pos: int, anzahl: int):
        werte, lauf = [], 0
        for d in struct.unpack(f"<{anzahl}i", roh[pos:pos + 4 * anzahl]):
            lauf += d
            werte.append(lauf)
        return werte, pos + 4 * anzahl

    lat, pos = spalte(0, n)
    lon, pos = spalte(pos, n)
    bits = roh[pos:pos + (n + 7) // 8]
    pos += (n + 7) // 8
    hat = [bool(bits[i // 8] & (1 << (i % 8))) for i in range(n)]
    hoehen, pos = spalte(pos, sum(hat))
    ts, pos = spalte(pos, n)

    h = iter(hoehen)
    return [[i, lat[i] / 1e6, lon[i] / 1e6,
             (next(h) / 10 if hat[i] else None), ts[i]] for i in range(n)]


def lesen_edbak_v4(pfad: str, passwort: str) -> dict:
    """Ein mehrteiliges Backup oeffnen und wieder zusammensetzen."""
    from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2HMAC
    from cryptography.hazmat.primitives import hashes
    import base64
    import hashlib

    with zipfile.ZipFile(pfad) as z:
        namen = z.namelist()
        if "manifest.edbak" not in namen:
            raise ValueError("ZIP ohne manifest.edbak — kein Backup Fassung 4")

        roh_manifest = z.read("manifest.edbak")
        _flag, runden, salz = _teil_kopf(roh_manifest)
        kdf = PBKDF2HMAC(algorithm=hashes.SHA256(), length=32, salt=salz,
                         iterations=runden)
        schluessel = kdf.derive(passwort.encode("utf-8"))

        manifest = json.loads(_teil_oeffnen(
            schluessel, roh_manifest, f"{AAD_MARKE}|manifest", "Das Manifest"
        ).decode("utf-8"))

        teile = manifest.get("teile", [])
        gesamt = len(teile)
        pruefsummen_ok = True
        pruefsummen_fehler = []
        kern = None
        eintraege: dict = {"missions": [], "rest_segments": []}
        spuren: dict = {}

        for nr, t in enumerate(teile, start=1):
            name = t["name"]
            if name not in namen:
                raise ValueError(f"Teil {name} fehlt im Archiv (Manifest nennt es)")
            roh = z.read(name)

            # SHA-256 DES VERSIEGELTEN TEILS — die zweite, unabhaengige
            # Backup neben der AAD, und sie schlaegt ZUERST zu.
            #
            # Beide fangen dieselben Faelle (vertauscht, veraendert, fremd),
            # aber sie sagen Verschiedenes: Die AAD sagt „liess sich nicht
            # oeffnen — Passwort oder falscher Platz", die Pruefsumme sagt
            # „DIESES Teil ist nicht das, das hier stehen soll". Fuer wen
            # ein Backup nicht aufgeht, ist der Unterschied der zwischen
            # zehnmal Passwort tippen und die richtige Datei suchen.
            ist = hashlib.sha256(roh).hexdigest()
            if t.get("sha256") and ist != t["sha256"]:
                pruefsummen_ok = False
                pruefsummen_fehler.append(f"{name}: {ist[:16]}… statt {t['sha256'][:16]}…")
                raise ValueError(
                    f"Teil {name} ist nicht das, das laut Manifest hier stehen soll: "
                    f"Pruefsumme {ist[:16]}… statt {t['sha256'][:16]}…. Das Teil ist "
                    f"veraendert, vertauscht oder stammt aus einem anderen Backup.")

            aad = f"{AAD_MARKE}|{manifest['kennung']}|{name}|{nr}/{gesamt}"
            inhalt = json.loads(_teil_oeffnen(schluessel, roh, aad, f"Teil {name}")
                                .decode("utf-8"))
            art = t.get("art")
            if art == "kopf":
                # NUR "kopf". Web 11.0.0 hatte hier ein "kern"; diese Fassung
                # ist nie ausgeliefert worden, und der Browser weist sie ab.
                # Ein zweiter Leser, der grosszuegiger ist als der erste,
                # pruefte ein Format, das es nicht gibt.
                kern = inhalt
            elif art == "eintraege":
                # DIE FENSTER WERDEN IN DER REIHENFOLGE DES MANIFESTS
                # ANEINANDERGEHAENGT. Sie ist die des Formats; eine andere
                # ergaebe einen Baum, der zwar dieselben Objekte, aber eine
                # andere Folge traegt — und der Vergleich meldete das zu Recht.
                eintraege["missions"].extend(inhalt.get("missions", []))
                eintraege["rest_segments"].extend(inhalt.get("rest_segments", []))
            else:
                for eintrag in inhalt.get("spuren", []):
                    spuren[int(eintrag["spur_ref"])] = base64.b64decode(eintrag["blob"])

        # Fremde Eintraege im Archiv sind ein Befund, kein Achselzucken.
        ueberzaehlig = sorted(set(namen) - {"manifest.edbak"}
                              - {t["name"] for t in teile})

    if kern is None:
        raise ValueError("Kein Kopfteil im Manifest")
    # Der gelieferte Baum sieht aus wie der einer einteiligen Datei: Kopf und
    # Eintraege wieder zusammen.
    kern.setdefault("missions", []).extend(eintraege["missions"])
    kern.setdefault("rest_segments", []).extend(eintraege["rest_segments"])
    kern.pop("eintraege_gesamt", None)   # Angabe des Abrufs, kein Inhalt

    # ---- Zusammensetzen: aus spur_ref + Blob wieder eine track-Liste --------
    #
    # Der gelieferte Baum soll aussehen wie der einer einteiligen Datei.
    # Fehlzuordnungen werden GEMELDET und nicht stillschweigend zu einer
    # leeren Spur: Eine spur_ref ohne Blob und ein Blob ohne Objekt sind
    # beides Befunde, und beide wuerden sonst als "Spur ist eben leer"
    # durchgehen.
    offen = dict(spuren)
    ohne_blob = []
    for schluessel_name in ("missions", "rest_segments"):
        for obj in kern.get(schluessel_name, []):
            ref = obj.pop("spur_ref", None)
            if ref is None:
                # OHNE VERWEIS IST DIE SPUR LEER, NICHT ABWESEND. Die
                # einteilige Datei traegt fuer einen Einsatz ohne Aufzeichnung
                # `"track": []`; wer das Feld hier weglaesst, erzeugt im
                # Vergleich eine Abweichung `[] -> None` fuer jeden solchen
                # Eintrag — vier davon im Referenzbestand, und keine davon
                # sagt etwas ueber die Daten.
                obj["track"] = []
                continue
            blob = offen.pop(int(ref), None)
            if blob is None:
                if int(obj.get("n", 0) or 0) > 0:
                    ohne_blob.append(f"{schluessel_name}#{ref}")
                obj["track"] = []
                continue
            obj["track"] = spur1_lesen(blob)

    kern["$container"] = {
        "fassung": 4,
        "gepackt": True,
        "runden": runden,
        "teile": gesamt,
        "eintragsteile": sum(1 for t in teile if t.get("art") == "eintraege"),
        "teilenamen": [t["name"] for t in teile],
        # Die Zahl, an der der Einspielweg entscheidet, ob er vor unlesbaren
        # Angaben warnt (S2/AP5b). Hier steht sie, damit ein Lauf sie NENNT
        # statt sie zu unterstellen: `None` heisst „das Manifest sagt nichts",
        # und das ist etwas anderes als eine Null.
        "unlesbar": manifest.get("unlesbar"),
        "pruefsummen_ok": pruefsummen_ok,
        "pruefsummen_fehler": pruefsummen_fehler,
        "blobs_ohne_objekt": sorted(offen.keys()),
        "objekte_ohne_blob": ohne_blob,
        "ueberzaehlige_dateien": ueberzaehlig,
    }
    return kern
