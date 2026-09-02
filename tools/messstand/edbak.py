"""Eine Backup-Datei SCHREIBEN — das Gegenstück zu `vergleich/lesen.py`.

WOFUER. Der Vervielfältiger (`vervielfaeltigen.py`) baut aus einem echten
Referenz-Backup einen Großbestand. Er muss die entstandenen Nutzlasten
wieder versiegeln, denn eingespielt wird über den **regulären** Weg: der
Browser öffnet eine `.edbak`, entschlüsselt sie, verschlüsselt die geschützten
Angaben für das Zielkonto um und schickt sie an `api/backup_restore.php`. Eine
Nutzlast, die nur als JSON auf der Platte liegt, kommt dort nicht hinein — und
ein eigener Einspielweg am Container vorbei wäre genau der zweite Weg, den
niemand pflegt (R4).

GELESEN WIRD NICHT HIER. `tools/referenzdatensatz/vergleich/lesen.py` liest
den Container bereits, und zwar geprüft; diese Datei importiert von dort. Zwei
Leser desselben Formats laufen früher oder später auseinander, und dann glaubt
man dem falschen.

DAS FORMAT (Fassung 3, `server/assets/crypto.js`, `sealBackup`):

    "EDBAK2" 0x00 0x03 | Flag(1) | Runden(4, big endian) | Salt(16) | IV(12) | AES-GCM
    AAD: die ersten 13 Bytes (Magie + Fassung + Flag + Runden)
    Flag: 1 = Inhalt gzip-gepackt, 0 = roh
    Schlüssel: PBKDF2-SHA256(Backup-Passwort, Salt, Runden, 256 Bit)

Der Inhalt ist KLARTEXT: Der Browser entschlüsselt vor dem Versiegeln, damit
sich das Backup in jedes Konto einspielen lässt (`Backup-Format.md` 2).
Dieses Werkzeug fasst deshalb nie einen `edk1:`-Chiffretext an.

WARUM DAS GZIP HIER NACHGEBAUT WIRD UND NICHT WEGGELASSEN. Ohne das Flag wäre
die Datei zwar lesbar (der Öffner kennt beide Fälle), aber sie wäre nicht die
Datei, die die Anwendung erzeugt — und der Messstand soll den Weg messen, den
es wirklich gibt. Ein ungepacktes Paket wäre außerdem viermal so groß und
verschöbe genau die Größe, um die es hier geht.
"""
from __future__ import annotations

import gzip
import json
import os
import pathlib
import sys

# Der Leser kommt aus dem Referenzdatensatz — ein Format, ein Leser.
sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[1]
                       / "referenzdatensatz" / "vergleich"))
from lesen import lesen_edbak  # noqa: E402  (Pfad muss vorher stehen)

MAGIE = b"EDBAK2"
FASSUNG = 3

# Dieselbe Rundenzahl, die `einstellungen.php` beim Versiegeln benutzt
# (KDF_ITER des Kontos). Die Referenzdatei trägt 320 000; wer eine andere
# braucht, übergibt sie.
RUNDEN_VORGABE = 320000


def schreiben_edbak(daten: dict, passwort: str, runden: int = RUNDEN_VORGABE,
                    packen: bool = True) -> bytes:
    """Nutzlast versiegeln. Gibt die fertigen Dateibytes zurück."""
    from cryptography.hazmat.primitives import hashes
    from cryptography.hazmat.primitives.ciphers.aead import AESGCM
    from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2HMAC

    if not 1000 <= runden <= 10_000_000:
        raise ValueError(f"Rundenzahl unbrauchbar: {runden}")

    # Die Anwendung schreibt das JSON ohne Leerraum und ohne \u-Fluchten
    # (JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES bzw. JSON.stringify).
    roh = json.dumps(daten, ensure_ascii=False, separators=(",", ":")).encode("utf-8")
    koerper = gzip.compress(roh, 6) if packen else roh
    flag = 1 if packen else 0

    kopf = bytearray(13)
    kopf[0:6] = MAGIE
    kopf[6] = 0
    kopf[7] = FASSUNG
    kopf[8] = flag
    kopf[9:13] = runden.to_bytes(4, "big")
    kopf = bytes(kopf)

    salt = os.urandom(16)
    iv = os.urandom(12)
    kdf = PBKDF2HMAC(algorithm=hashes.SHA256(), length=32, salt=salt,
                     iterations=runden)
    schluessel = kdf.derive(passwort.encode("utf-8"))
    ct = AESGCM(schluessel).encrypt(iv, koerper, kopf)
    return kopf + salt + iv + ct


def rundlauf_pruefen(daten: dict, passwort: str, runden: int = RUNDEN_VORGABE) -> None:
    """Versiegeln, wieder öffnen, vergleichen — bevor die Datei irgendwo landet.

    Eine Backup-Datei, die sich nicht öffnen lässt, fällt sonst erst im
    Browser auf, und dort sieht sie aus wie ein falsches Passwort.
    """
    import tempfile

    bytes_ = schreiben_edbak(daten, passwort, runden)
    with tempfile.NamedTemporaryFile(suffix=".edbak", delete=False) as f:
        f.write(bytes_)
        pfad = f.name
    try:
        zurueck = lesen_edbak(pfad, passwort)
        zurueck.pop("$container", None)
        if zurueck != daten:
            raise RuntimeError("Rundlauf des Containers gescheitert: "
                               "geöffnete Nutzlast weicht ab.")
    finally:
        os.unlink(pfad)
