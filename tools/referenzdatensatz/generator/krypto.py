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

SEIT S10 HAENGT DER DATENSCHLUESSEL AM SERVER-ANTEIL (E-S10-04). Die
PBKDF2-Ableitung oben bleibt, wie sie ist; ihre erste Haelfte heisst jetzt
HAELFTE und ist nicht mehr selbst der Datenschluessel:

  Datenschluessel = HKDF-SHA256(ikm  = Haelfte (32 Byte),
                                salt = Konto-Anteil (32 Byte),
                                info = "edka1|dk") -> 32 Byte

Der Konto-Anteil kommt als 64 Hexzeichen aus der Seite (`KONTO_ANTEILE`) und
geht als ROHBYTES in das Salz — nicht als Hextext. Diese Festlegung steht an
zwei Orten gleich: hier und in `EdCrypto.datenschluessel()` (assets/crypto.js).

WELCHE HUELLE WELCHEN SCHLUESSEL BRAUCHT, steht in ihrem Praefix:
  `edka1:<kennung>:…`  ->  HKDF mit dem Anteil zu <kennung>
  `edk1:…` oder ohne   ->  die Haelfte unveraendert (Altfassung)

DIESES MODUL STELLT NICHT UM. Es liest beide Fassungen und baut auf
Verlangen die neue; ob ein Konto umgestellt WIRD, entscheidet der Browser
(`unlock.js`). Ein Konto, das nur ueber den Pruefstand angemeldet war, bleibt
deshalb auf `edk1:` — das ist Absicht und steht so im Pruefdokument.
"""
from __future__ import annotations

import base64
import hashlib
import hmac
import json
import os
import re

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


HUELLE_PRAEFIX = "edka1:"
HKDF_INFO = b"edka1|dk"


def huelle_kennung(huelle: str | None) -> str | None:
    """Die Anteil-Kennung im Praefix einer Huelle — oder None (Altfassung)."""
    if not huelle:
        return None
    m = re.match(r"^edka1:([0-9a-f]{8}):", huelle)
    return m.group(1) if m else None


def datenschluessel(haelfte_hex: str, huelle: str | None,
                    anteile: dict[str, str] | None) -> str:
    """Der Datenschluessel zu DIESER Huelle — wie EdCrypto.datenschluessel().

    `haelfte_hex` ist die erste Haelfte der PBKDF2-Ableitung, `anteile` das
    Objekt `KONTO_ANTEILE` aus der Seite ({kennung: 64 Hex}).

    ENTSCHIEDEN WIRD AM PRAEFIX DER HUELLE, nicht am Zustand der Installation.
    Das ist der Unterschied zwischen „dieses Konto ist umgestellt" und „diese
    Installation liefert einen Anteil aus": Waehrend einer Rotation gilt beides
    gleichzeitig, aber nur fuer je einen Teil der Konten.

    Faellt die Kennung nicht in `anteile`, ist der Schluessel nicht zu bilden
    — dann wurde die Huelle mit einem Anteil gebaut, den diese Installation
    nicht (mehr) kennt. Das ist ein lauter Fehler und kein Rueckfall auf die
    Haelfte: Ein Rueckfall ergaebe einen Schluessel, der nicht passt, und der
    Fehlschlag saehe aus wie ein falsches Passwort.
    """
    kennung = huelle_kennung(huelle)
    if kennung is None:
        return haelfte_hex
    hexwert = (anteile or {}).get(kennung)
    if not hexwert:
        raise ValueError(
            f"Die Huelle traegt die Anteil-Kennung {kennung}; dazu liefert die "
            f"Seite keinen Anteil (bekannt: {sorted((anteile or {}).keys())}).")
    return hkdf_sha256(bytes.fromhex(haelfte_hex), bytes.fromhex(hexwert),
                       HKDF_INFO, 32).hex()


def hkdf_sha256(ikm: bytes, salt: bytes, info: bytes, laenge: int) -> bytes:
    """HKDF nach RFC 5869 — dasselbe, was WebCrypto `deriveBits` rechnet.

    Ausgeschrieben statt aus `cryptography` geholt: Es sind vier Zeilen, und
    `hashlib`/`hmac` liegen in jeder Python-Installation. Ein weiterer
    Fremdbestandteil fuer vier Zeilen waere ein schlechter Tausch (E-S10-15).
    """
    prk = hmac.new(salt, ikm, hashlib.sha256).digest()
    aus, block, zaehler = b"", b"", 1
    while len(aus) < laenge:
        block = hmac.new(prk, block + info + bytes([zaehler]), hashlib.sha256).digest()
        aus += block
        zaehler += 1
    return aus[:laenge]


def entpacken(wrap: str, schluessel_hex: str) -> str:
    """Verpackten Inhaltsschluessel auspacken -> Inhaltsschluessel als Hex."""
    return entschluesseln(wrap, schluessel_hex)


def huelle_bauen(inhaltsschluessel_hex: str, datenschluessel_hex: str,
                 kennung: str | None) -> str:
    """Eine Schluesselhuelle bauen — mit Anteil-Kennung oder ohne.

    `kennung is None` ergibt die Altfassung `edk1:…`; das ist der Weg fuer das
    Demo-Konto und fuer eine Installation ohne Anteil.
    """
    iv = os.urandom(12)
    ct = AESGCM(bytes.fromhex(datenschluessel_hex)).encrypt(
        iv, inhaltsschluessel_hex.encode("utf-8"), None)
    roh = base64.b64encode(iv + ct).decode("ascii")
    return (PRAEFIX + roh) if kennung is None \
        else f"{HUELLE_PRAEFIX}{kennung}:{roh}"


def verschluesseln(klartext: str, schluessel_hex: str) -> str:
    iv = os.urandom(12)
    ct = AESGCM(bytes.fromhex(schluessel_hex)).encrypt(iv, klartext.encode("utf-8"), None)
    return PRAEFIX + base64.b64encode(iv + ct).decode("ascii")


def entschluesseln(chiffre: str, schluessel_hex: str) -> str:
    """Chiffretext oeffnen — beide Huellenfassungen und der Bestand ohne Praefix.

    DIE KENNUNG WIRD HIER NUR ABGESCHNITTEN, NICHT AUSGEWERTET. Welcher
    Schluessel zu `edka1:<kennung>:` gehoert, entscheidet `datenschluessel()`
    eine Ebene darueber; hier steht nur, wo der Base64-Teil anfaengt. Die beiden
    zu vermengen hiesse, dieser Funktion das Konto und die Anteile
    mitzugeben, die sie fuer einen `pat_blob` gar nicht braucht.
    """
    if chiffre.startswith(HUELLE_PRAEFIX):
        roh = chiffre.split(":", 2)[2]
    elif chiffre.startswith(PRAEFIX):
        roh = chiffre[len(PRAEFIX):]
    else:
        roh = chiffre
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
