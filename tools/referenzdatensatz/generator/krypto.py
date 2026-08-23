"""Krypto des Referenzdatensatzes — nachgebildet nach server/assets/crypto.js.

WARUM NACHGEBILDET UND NICHT UMGANGEN. Das Einspielskript geht ueber die
regulaeren Endpunkte (R4). `einsatz_form.php` nimmt `pat_blob` ausschliesslich
als CHIFFRETEXT entgegen (`pruef_pat_blob`) — der Server hat den Schluessel
bauartbedingt nicht. Ein Skript, das nachtragen will, muss also selbst
verschluesseln, und zwar genau so, wie der Browser es taete. Sonst liest die
Anwendung den Bestand spaeter nicht.

Die drei Groessen (docs/Backup-Format.md, assets/crypto.js):

  Ableitung   PBKDF2-SHA256(Passwort, kdf_salt, kdf_iter) -> 512 Bit
              erste 32 Byte = dataKey  (bleibt lokal, verschluesselt nichts
                                        selbst, sondern packt den Inhalts-
                                        schluessel aus)
              zweite 32 Byte = authToken (ersetzt das Passwort zum Server)
  Inhalts-    zufaellige 32 Byte, liegen passwortverpackt in users.pat_wrap_pw
  schluessel
  Chiffretext 'edk1:' + Base64(IV(12) || AES-256-GCM(Klartext))

DER PRAEFIX IST PFLICHT fuer neu geschriebene Werte (M2-10, seit Web 5.1.0).
Aeltere Chiffretexte tragen ihn nicht; beide Formen sind gueltig und stehen
dauerhaft nebeneinander, weil der Server sie nicht nachtragen kann.
"""
from __future__ import annotations

import base64
import hashlib
import json
import os

from cryptography.hazmat.primitives.ciphers.aead import AESGCM

PRAEFIX = "edk1:"


def ableiten(passwort: str, salt_hex: str, runden: int) -> tuple[str, str]:
    """(dataKey als Hex, authToken als Hex) — wie EdCrypto.deriveKeys."""
    if not isinstance(runden, int) or not (1000 <= runden <= 10_000_000):
        # Dieselbe Haltung wie crypto.js: lieber ein lauter Fehler beim
        # Entwickeln als ein leiser im Betrieb. Ein Vorgabewert liesse eine
        # vergessene Aufrufstelle stillschweigend falsch rechnen.
        raise ValueError(f"Rundenzahl fehlt oder ist unbrauchbar: {runden!r}")
    bits = hashlib.pbkdf2_hmac("sha256", passwort.encode("utf-8"),
                               bytes.fromhex(salt_hex), runden, 64)
    return bits[:32].hex(), bits[32:].hex()


def entpacken(wrap: str, schluessel_hex: str) -> str:
    """Verpackten Inhaltsschluessel auspacken -> Inhaltsschluessel als Hex."""
    return entschluesseln(wrap, schluessel_hex)


def verschluesseln(klartext: str, schluessel_hex: str) -> str:
    iv = os.urandom(12)
    ct = AESGCM(bytes.fromhex(schluessel_hex)).encrypt(iv, klartext.encode("utf-8"), None)
    return PRAEFIX + base64.b64encode(iv + ct).decode("ascii")


def entschluesseln(chiffre: str, schluessel_hex: str) -> str:
    roh = chiffre[len(PRAEFIX):] if chiffre.startswith(PRAEFIX) else chiffre
    b = base64.b64decode(roh)
    return AESGCM(bytes.fromhex(schluessel_hex)).decrypt(b[:12], b[12:], None).decode("utf-8")


def pat_blob(geschuetzt: dict, inhaltsschluessel_hex: str) -> str | None:
    """Geschuetzte Angaben -> `edk1:`-Chiffretext, oder None wenn nichts da ist.

    LEERE FELDER FLIEGEN RAUS, nicht als null mit. Der Browser baut den Block
    genauso auf (einsatz_form.php, sammlePat): Ein `"dx": null` im Klartext
    waere eine Angabe ueber eine Angabe, die es nicht gibt -- und wuerde beim
    Rueckweg als leerer String wieder auftauchen.
    """
    if not geschuetzt:
        return None
    o: dict = {}
    for schluessel in ("dx", "dob", "age", "mission_no", "site_desc"):
        wert = geschuetzt.get(schluessel)
        if wert not in (None, ""):
            o[schluessel] = wert
    for schluessel in ("loc", "start"):
        wert = geschuetzt.get(schluessel)
        if wert and (wert.get("addr") or wert.get("lat") is not None):
            teil = {}
            if wert.get("addr"):
                teil["addr"] = wert["addr"]
            if wert.get("lat") is not None:
                teil["lat"] = wert["lat"]
                teil["lon"] = wert["lon"]
            o[schluessel] = teil
    if not o:
        return None
    return verschluesseln(json.dumps(o, ensure_ascii=False, separators=(",", ":")),
                          inhaltsschluessel_hex)
