"""Krypto des Referenzdatensatzes — nachgebildet nach server/assets/crypto.js.

WARUM NACHGEBILDET UND NICHT UMGANGEN. Das Einspielskript geht ueber die
regulaeren Endpunkte. `einsatz_form.php` nimmt `pat_blob` ausschliesslich
als CHIFFRETEXT entgegen (`pruef_pat_blob`) — der Server hat den Schluessel
bauartbedingt nicht. Ein Skript, das nachtragen will, muss also selbst
verschluesseln, und zwar genau so, wie der Browser es taete. Sonst liest die
Anwendung den Bestand spaeter nicht.

Die Groessen (docs/Backup-Format.md, assets/crypto.js, assets/inhalt2.js):

  Ableitung   nach der Rundenzahl: unter `ABLEITUNG2_AB` PBKDF2-SHA256 mit
              512 Bit (erste Haelfte, Token), ab dort Ableitung 2
              (`referenz_inhalt.ableitung2()`)
  Inhalts-    zufaellige 32 Byte, liegen verpackt in users.pat_wrap_pw
  schluessel  (`edk2:` bzw. `edka2:<kennung>:`, Zweck `wrap`)
  Chiffretext `edk2:` — gebunden an Zweck und Bezug, mit Zaehler und
              Groessenklasse (`referenz_inhalt.verschliessen()`)

DER DATENSCHLUESSEL HAENGT AM SERVER-ANTEIL:

  Datenschluessel = HKDF-SHA256(ikm  = Haelfte (32 Byte),
                                salt = Konto-Anteil (32 Byte),
                                info = "edka1|dk") -> 32 Byte

`edka1|dk` ist der Name der Ableitung, kein Format, und bleibt. Der
Konto-Anteil kommt als 64 Hexzeichen aus der Seite (`KONTO_ANTEILE`) und
geht als ROHBYTES in das Salz — hier und in `EdCrypto.datenschluessel()`.

WELCHE HUELLE WELCHEN SCHLUESSEL BRAUCHT, steht in ihrem Praefix:
  `edka2:<kennung>:…`  ->  HKDF mit dem Anteil zu <kennung>
  `edk2:…`             ->  die Haelfte unveraendert (Demo-Konto, ohne Anteil)

NUR FASSUNG 2 (seit KA-07, Web 27.0.0). Bis dahin schrieb dieses Modul
`edk1:` und `edka1:` und las beide Fassungen; der Server nimmt die erste seit
Web 26 nicht mehr an, und seit Web 27 liest sie niemand. Das Format steht in
`tools/proben/huelle/referenz_inhalt.py`, und von dort wird es gerufen,
nicht nachgebaut.
"""
from __future__ import annotations

import hashlib
import hmac
import json
import re

import pathlib
import sys


sys.path.insert(0, str(pathlib.Path(__file__).resolve().parents[2] / "proben" / "huelle"))
import referenz_inhalt  # noqa: E402  (edk2 und Ableitung 2 — eine Stelle)

PRAEFIX2 = "edk2:"
HUELLE_PRAEFIX2 = "edka2:"
ABLEITUNG2_AB = 1_200_000           # wie `ABLEITUNG2_AB` in assets/crypto.js


def ableiten(passwort: str, salt_hex: str, runden: int) -> tuple[str, str]:
    """(dataKey als Hex, authToken als Hex) — wie EdCrypto.deriveKeys."""
    if not isinstance(runden, int) or not (1000 <= runden <= 10_000_000):
        # Dieselbe Haltung wie crypto.js: lieber ein lauter Fehler beim
        # Entwickeln als ein leiser im Betrieb. Ein Vorgabewert liesse eine
        # vergessene Aufrufstelle stillschweigend falsch rechnen.
        raise ValueError(f"Rundenzahl fehlt oder ist unbrauchbar: {runden!r}")
    if runden >= ABLEITUNG2_AB:
        # Ableitung 2: Rundenzahl und Fassung wechseln zusammen (F-KA-29).
        _, haelfte, token = referenz_inhalt.ableitung2(passwort, bytes.fromhex(salt_hex), runden)
        return haelfte.hex(), token.hex()
    bits = hashlib.pbkdf2_hmac("sha256", passwort.encode("utf-8"),
                               bytes.fromhex(salt_hex), runden, 64)
    return bits[:32].hex(), bits[32:].hex()


HKDF_INFO = b"edka1|dk"            # der Name der Ableitung, kein Format


def huelle_kennung(huelle: str | None) -> str | None:
    """Die Anteil-Kennung im Praefix einer Huelle — oder None (ohne Anteil)."""
    if not huelle:
        return None
    m = re.match(r"^edka2:([0-9a-f]{8}):", huelle)
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
    Fremdbestandteil fuer vier Zeilen waere ein schlechter Tausch.
    """
    prk = hmac.new(salt, ikm, hashlib.sha256).digest()
    aus, block, zaehler = b"", b"", 1
    while len(aus) < laenge:
        block = hmac.new(prk, block + info + bytes([zaehler]), hashlib.sha256).digest()
        aus += block
        zaehler += 1
    return aus[:laenge]


def entpacken(wrap: str, schluessel_hex: str) -> str:
    """Verpackten Inhaltsschluessel auspacken -> Inhaltsschluessel als Hex.
    `edk2:` und `edka2:<kennung>:` sind an Zweck und Bezug gebunden (`wrap`,
    `pw` bzw. `pw:<kennung>`); eine andere Huelle wirft (`kennung`)."""
    return referenz_inhalt.huelle_oeffnen(bytes.fromhex(schluessel_hex), wrap, "pw")


def huelle_bauen(inhaltsschluessel_hex: str, datenschluessel_hex: str,
                 kennung: str | None) -> str:
    """Eine Passworthuelle bauen — `edka2:<kennung>:` oder ohne Anteil `edk2:`
    (Demo-Konto, Installation ohne Anteil). Wie `EdCrypto.huelleBauen()`."""
    return referenz_inhalt.huelle_bauen(bytes.fromhex(datenschluessel_hex),
                                        inhaltsschluessel_hex, "pw", kennung)


def verschluesseln(klartext: str, schluessel_hex: str, zweck: str, bezug: str = "",
                   z: int = 0) -> str:
    """Ein gebundener Text `edk2:` — wie `EdInhalt.schreiben()`."""
    return referenz_inhalt.verschliessen(bytes.fromhex(schluessel_hex), zweck, bezug,
                                         klartext.encode("utf-8"), z)


def entschluesseln(chiffre: str, schluessel_hex: str, zweck: str, bezug: str = "") -> str:
    """Einen gebundenen Text oeffnen. Er geht nur an seiner Stelle auf: Wer
    ihn oeffnet, nennt `zweck` (`pat`, `kp`, `rw`) und `bezug` (bei `pat` die
    client_ref des Einsatzes). Ein Text, der nicht `edk2:` ist, wirft."""
    return referenz_inhalt.oeffnen(bytes.fromhex(schluessel_hex), chiffre, zweck, bezug)[0].decode("utf-8")


def pat_blob(geschuetzt: dict, inhaltsschluessel_hex: str, ref: str, z: int = 1) -> str | None:
    """Geschuetzte Angaben -> `edk2:`-Block (Zweck `pat`, Bezug die client_ref,
    Zaehler `z`), oder None wenn nichts da ist.

    LEERE FELDER FLIEGEN RAUS, nicht als null mit. Der Browser baut den Block
    genauso auf (einsatz_form.php, sammlePat): Ein `"dx": null` im Klartext
    waere eine Angabe ueber eine Angabe, die es nicht gibt -- und wuerde beim
    Rueckweg als leerer String wieder auftauchen.
    """
    if not geschuetzt:
        return None
    o: dict = {}
    # `notes` und `ortshoehe` liegen im Blob. Was nicht in dieser Liste
    # steht, faellt still heraus -- 85 Notizen des Referenzbestands kamen
    # einmal nie an.
    for schluessel in ("dx", "dob", "age", "mission_no", "site_desc", "notes", "ortshoehe"):
        wert = geschuetzt.get(schluessel)
        if wert not in (None, ""):
            o[schluessel] = wert
    # `transport_dest` ist ein Ortsfeld im Blob, wie `loc`.
    for schluessel in ("loc", "start", "transport_dest"):
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
                          inhaltsschluessel_hex, "pat", ref, z)
