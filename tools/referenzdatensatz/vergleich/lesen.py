"""Exportdateien einlesen und in eine vergleichbare Form bringen.

Zwei Formate, ein Ziel: ein Baum aus Listen und Zeichenketten, in dem nichts
mehr steht, was sich bei jedem Export aendert.

  CSV-Archiv (.zip)   LIESMICH.txt, felder.csv, einsaetze.csv,
                      diensttage.csv, ruhezeiten.csv, tracks/*.gpx
  Sicherung (.edbak)  Container v3, entsiegelt mit dem Backup-Passwort;
                      das innere JSON traegt die geschuetzten Angaben im
                      KLARTEXT (Backup-Format.md 2) — deshalb ist es
                      vergleichbar, ohne dass Chiffretext angefasst wird.
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
# Versiegeln, damit sich die Sicherung in jedes Konto einspielen laesst
# (Backup-Format.md 2). Dieses Werkzeug fasst also nie einen `edk1:`-
# Chiffretext an — ausser dort, wo eine Sicherung ihn als `pat_blob`
# unveraendert mitfuehrt, weil sie ihn beim Export nicht lesen konnte.

MAGIE = b"EDBAK2"
RUNDEN_V2 = 310000


def lesen_edbak(pfad: str, passwort: str) -> dict:
    import gzip as gziplib
    from cryptography.hazmat.primitives.ciphers.aead import AESGCM
    from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2HMAC
    from cryptography.hazmat.primitives import hashes

    roh = open(pfad, "rb").read()
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
