"""Chiffretexte unter dem Inhaltsschlüssel, Fassung 2 (`edk2:`) — Referenz und Prüfvektoren.

Anlass: Nr. 563 — Patientenblöcke und Schlüsselhüllen waren an nichts
gebunden (Audit KA, F-KA-04): vertauschen und zurücklegen ging ohne Fehler.

WOFUER. Die Schwesterdatei zu `referenz.py`. Dort steht die Gerätehülle, die
Uhr, Handy und Browser schreiben. Hier steht, was NUR der Browser schreibt
und liest (und PHP beim Demo-Bestand und in der Formprüfung): die
Chiffretexte unter dem Inhaltsschlüssel, das Standbuch gegen das
Zurücklegen, das Gerätebuch und die Ableitung aus dem Passwort. Eine eigene
Datei, weil Umsetzer und Verfahren andere sind (AES-GCM statt CBC+HMAC,
keine Uhr) — die Vektoren der Uhr hängen so an keiner Änderung hier.
Wer das Format ändert, ändert es HIER und erzeugt die Vektoren neu.

=========================================================================
`edk2:` — der gebundene Chiffretext
=========================================================================

    "edk2:" ‖ Base64( NONCE 12 Byte ‖ CT ‖ TAG 16 Byte )      AES-256-GCM
    Zusatzdaten = "edk2" ‖ Feld(Zweck) ‖ Feld(Bezug)
                  Feld = 2 Byte Länge (big-endian) ‖ UTF-8 — wie die Bindung
                  der Gerätehülle
    Innen       = Z (uint32 big-endian) ‖ L (uint32 big-endian)
                  ‖ Nutzlast (L Byte) ‖ 0x00 … bis zur Größenklasse

    Größenklassen der Innenlänge: 128, 256, 512, 1024, 2048, 4096, danach
    jedes Vielfache von 4096. Ein Patientenblock hat mindestens 512 — ein
    fast leerer sieht aus wie ein gewöhnlicher (F-KA-30).

    Zweck     Bezug                      Z
    pat       client_ref des Einsatzes   Zähler des Blocks (Standbuch, Feld p)
    kp        leer                       0 — privater Teil des Kontopaars
    rw        leer                       0 — privater Teil des Rückwegs
    wrap      "rc"                       0 — Inhaltsschlüssel unter dem
                                             Wiederherstellungsschlüssel
              "pw:<Anteil-Kennung>"      0 — unter dem Datenschlüssel; steht
                                             als "edka2:<Kennung>:<Base64>"
              "pw"                       0 — ohne Anteil (Demo-Konto)
    stand     Nummer des Blatts          Version des Blatts
    geraete   leer                       Version des Gerätebuchs

WAS DIE BINDUNG LEISTET. Ein Block, der an einem anderen Einsatz hängt, ein
`kp_privat` an der Stelle von `rw_privat`, eine Hülle unter anderer
Anteil-Kennung: Der Prüfwert bricht (`pruefwert`). Die Kontonummer steht
NICHT in den Zusatzdaten: Jedes Konto hat seinen eigenen Inhaltsschlüssel,
über Kontogrenzen geht schon heute nichts auf — und eine vom Server
vergebene Nummer bände den Block an die Installation statt an das Konto.

DER RÜCKWEG BLEIBT. `kp`, `rw` und `wrap`/"rc" brauchen zum Öffnen nur
ihren Schlüssel und feste Zeichenketten — nichts vom Server, keinen Anteil.
Der Wiederherstellungsschlüssel allein öffnet weiter alles (CLAUDE.md 4).

FASSUNG 1 BLEIBT LESBAR: "edk1:" ‖ Base64(NONCE ‖ CT ‖ TAG) oder dasselbe
ohne Kennung, AES-256-GCM ohne Zusatzdaten, die Nutzlast ungepolstert.

=========================================================================
Das Standbuch — der Zählerstand, den der Server nicht fälschen kann
=========================================================================

Gegen das Zurücklegen braucht der Browser einen Stand, gegen den er den
Zähler eines Blocks hält. Der Stand liegt IM BROWSER (IndexedDB, je Konto,
übersteht das Abmelden); der Server hält nur eine verschlossene Abschrift,
damit der nächste Browser nicht bei null anfängt.

    16 Blätter. Hash = SHA-256("nadoku-stand-1" ‖ client_ref)
    Blatt eines Datensatzes   = Hash[0] >> 4        (fest, nicht vom Server)
    Kennung im Blatt          = Base64url(Hash[1:10]), 12 Zeichen
    Ein Blatt = edk2, Zweck "stand", Bezug = Blattnummer, Z = seine Version.
    Nutzlast (JSON): { "fassung": 1, "t": <letzte Änderung, Unix-Sekunden>,
                       "zu": false, "e": { "<Kennung>": { … } } }

    Eintrag (jedes Feld darf fehlen):
      p   Zähler des Patientenblocks
      o   Zähler der zuletzt abgenommenen Einsatzhülle;  k: 1 = sie kam vom Konto
      s   [gedeckt bis, Stufe] der abgenommenen Spur
      d   Versatz in Sekunden (Umdatierung des Diensttags): Klartextzeit −
          Zeit in der Hülle
      x   Löschvermerke [[von, bis], …] in der Zeit der Hülle
    zu    true: Der Anhebelauf ist durch — ein Block der Fassung 1 in diesem
          Blatt ist kein Altbestand mehr, sondern ein Befund (`altformat`).

DIE REGELN BEIM LESEN (`blatt_oeffnen()`, `pat_lesen()`):
    blatt-zurueckgelegt   der Server liefert ein Blatt mit kleinerer Version,
                          als dieser Browser schon gesehen hat (der Anker)
    zurueckgelegt         Z des Blocks < p des Eintrags — oder ein Block der
                          Fassung 1, wo der Eintrag schon einen Zähler hat
    altformat             Fassung 1 in einem Blatt mit "zu"
    Z > p oder kein Eintrag: kein Fehler — das Blatt hinkt (ein verlorener
    Schreibvorgang); der Browser trägt nach. Fälschen kann das niemand: Ein
    höheres Z entsteht nur mit dem Schlüssel.

WAS ES KOSTET. Lesen: nichts zusätzlich — die Antwort des Servers nennt je
Blatt nur die Versionsnummer, und der Browser holt ein Blatt nur, wenn sie
über seiner liegt (ein anderer Browser hat geschrieben). Schreiben: Mit
jedem Block geht das eine Blatt mit, in derselben Anfrage (bei 5 000
Einsätzen rund 16 KB — gerechnet, nicht gemessen). WO ES NICHT HÄLT: Ein Browser ohne Anker (neu,
Speicher geleert) glaubt der Abschrift des Servers — einmal.

=========================================================================
Das Gerätebuch — welche Geräteschlüssel das Konto bestätigt hat
=========================================================================

    edk2, Zweck "geraete", Bezug leer, Z = Version (Anker wie beim Blatt:
    `buch-zurueckgelegt`). Nutzlast (JSON):
    { "fassung": 1, "geraete": [ { "sid": "<16 hex>", "spk": "<Base64, 65 Byte SEC1>",
        "geraet": "<Gerätekennung>", "seit": <Unix>, "bis": <Unix oder null> } ] }

Ein Eintrag entsteht, wenn die Person bei der Kopplung den Vergleichswert
bestätigt (`referenz.py`), und bekommt `bis`, wenn das Gerät ausgetragen
wird. `Leser` in `referenz.py` liest genau diese Form.

=========================================================================
Die Ableitung aus dem Passwort, Fassung 2 (F-KA-29)
=========================================================================

    Wurzel = PBKDF2-HMAC-SHA256(Passwort (UTF-8), Salz, Runden, 32 Byte)
    Hälfte = HKDF-SHA256(IKM = Wurzel, salt = leer,
                         info = "nadoku-ableitung-2|haelfte", 32 Byte)
    Token  = HKDF-SHA256(IKM = Wurzel, salt = leer,
                         info = "nadoku-ableitung-2|token", 32 Byte)

Fassung 1 zog 64 Byte aus PBKDF2 — zwei Blöcke; wer nur das Token angreift,
rechnet einen. Fassung 2 rechnet EINEN Block für beide; bei gleicher
Wartezeit trägt sie die doppelte Rundenzahl (Vorschlag: 1 200 000 statt
600 000). Das Token jedes Kontos ändert sich: Die Umstellung läuft still
beim Anmelden, wie eine Anhebung der Rundenzahl, und hüllt dabei den
Inhaltsschlüssel um (die Hälfte ist eine andere). Rundenzahl und Fassung
wechseln ZUSAMMEN — bei gleicher Rundenzahl wäre die Wurzel der Fassung 2
die Hälfte der Fassung 1.

AUFRUF
    python3 referenz_inhalt.py erzeugen     # vektoren-inhalt.json schreiben
    python3 referenz_inhalt.py pruefen      # vektoren-inhalt.json gegen diese Umsetzung
    python3 referenz_inhalt.py --selbstprobe
"""
from __future__ import annotations

import base64
import hashlib
import json
import re
import pathlib
import struct
import sys

from cryptography.exceptions import InvalidTag
from cryptography.hazmat.primitives import hashes
from cryptography.hazmat.primitives.ciphers.aead import AESGCM
from cryptography.hazmat.primitives.kdf.hkdf import HKDF

HIER = pathlib.Path(__file__).resolve().parent
VEKTOREN = HIER / "vektoren-inhalt.json"

KENNUNG, KENNUNG1 = "edk2:", "edk1:"
HUELLE_KENNUNG = "edka2:"
ZWECKE = ("pat", "kp", "rw", "wrap", "stand", "geraete")
KLASSEN = (128, 256, 512, 1024, 2048, 4096)
MINDESTKLASSE = {"pat": 512}
STAND_INFO = b"nadoku-stand-1"
BLAETTER = 16
ABLEITUNG_INFO = "nadoku-ableitung-2|"


class InhaltFehler(ValueError):
    """Ein Chiffretext, der nicht aufgeht oder nicht gilt — mit benanntem Grund."""


def feld(text: str) -> bytes:
    b = text.encode("utf-8")
    if len(b) > 0xFFFF:
        raise InhaltFehler("form")
    return struct.pack(">H", len(b)) + b


def zusatzdaten(zweck: str, bezug: str) -> bytes:
    if zweck not in ZWECKE:
        raise InhaltFehler("zweck")
    return b"edk2" + feld(zweck) + feld(bezug)


def klasse(n: int, zweck: str = "") -> int:
    """Die Größenklasse für eine Innenlänge von `n` Byte."""
    n = max(n, MINDESTKLASSE.get(zweck, KLASSEN[0]))
    for k in KLASSEN:
        if n <= k:
            return k
    return -(-n // 4096) * 4096


def ist_klasse(n: int) -> bool:
    return n in KLASSEN or (n > 4096 and n % 4096 == 0)


def verschliessen(schluessel: bytes, zweck: str, bezug: str, nutzlast: bytes, z: int = 0, *,
                  nonce: bytes | None = None, zwischen: dict | None = None) -> str:
    """`nonce` nur für Prüfvektoren fest."""
    aad = zusatzdaten(zweck, bezug)
    innen = struct.pack(">II", z, len(nutzlast)) + nutzlast
    innen += b"\x00" * (klasse(len(innen), zweck) - len(innen))
    nonce = nonce if nonce is not None else __import__("os").urandom(12)
    ct = AESGCM(schluessel).encrypt(nonce, innen, aad)
    if zwischen is not None:
        zwischen.update(aad=aad, innen_laenge=len(innen), nonce=nonce)
    return KENNUNG + base64.b64encode(nonce + ct).decode()


def _roh(text: str, kennung: str) -> bytes:
    if not text.startswith(kennung):
        raise InhaltFehler("kennung")
    try:
        return base64.b64decode(text[len(kennung):], validate=True)
    except ValueError as f:
        raise InhaltFehler("form") from f


def oeffnen(schluessel: bytes, text: str, zweck: str, bezug: str) -> tuple[bytes, int]:
    """Nutzlast und Zähler. Reihenfolge: Kennung, Form, Prüfwert, Polster."""
    aad = zusatzdaten(zweck, bezug)
    roh = _roh(text, KENNUNG)
    if len(roh) < 12 + 16 or not ist_klasse(len(roh) - 28) \
            or len(roh) - 28 < MINDESTKLASSE.get(zweck, 0):
        raise InhaltFehler("form")
    try:
        innen = AESGCM(schluessel).decrypt(roh[:12], roh[12:], aad)
    except InvalidTag as f:
        raise InhaltFehler("pruefwert") from f
    z, n = struct.unpack(">II", innen[:8])
    if n > len(innen) - 8 or any(innen[8 + n:]):
        raise InhaltFehler("polster")
    return innen[8:8 + n], z


def verschliessen1(schluessel: bytes, nutzlast: bytes, nonce: bytes) -> str:
    """Fassung 1, wie `EdCrypto.encrypt()` bis KA-06 — nur für die Vektoren."""
    return KENNUNG1 + base64.b64encode(nonce + AESGCM(schluessel).encrypt(nonce, nutzlast, None)).decode()


def oeffnen1(schluessel: bytes, text: str) -> bytes:
    """Fassung 1: mit oder ohne Kennung, ohne Zusatzdaten, ohne Polster."""
    if ":" in text and not text.startswith(KENNUNG1):
        raise InhaltFehler("kennung")
    try:
        roh = base64.b64decode(text[len(KENNUNG1):] if text.startswith(KENNUNG1) else text, validate=True)
    except ValueError as f:
        raise InhaltFehler("form") from f
    if len(roh) < 12 + 16:
        raise InhaltFehler("form")
    try:
        return AESGCM(schluessel).decrypt(roh[:12], roh[12:], None)
    except InvalidTag as f:
        raise InhaltFehler("pruefwert") from f


# ------------------------------------------- Schlüsselhüllen (`wrap`)

def huelle_bauen(schluessel: bytes, ck_hex: str, art: str, kennung: str | None = None, *,
                 nonce: bytes | None = None) -> str:
    """`art` "rc" (Wiederherstellung) oder "pw" (Datenschlüssel); `kennung`
    ist die des Server-Anteils und nur bei "pw" erlaubt."""
    if art == "rc" and kennung:
        raise InhaltFehler("kennung")                 # der Rückweg hängt nie am Anteil
    bezug = art + (":" + kennung if kennung else "")
    text = verschliessen(schluessel, "wrap", bezug, ck_hex.encode(), nonce=nonce)
    return HUELLE_KENNUNG + kennung + ":" + text[len(KENNUNG):] if kennung else text


def huelle_oeffnen(schluessel: bytes, text: str, art: str) -> str:
    kennung = None
    if text.startswith(HUELLE_KENNUNG):
        if art == "rc":
            raise InhaltFehler("kennung")
        kennung, _, rest = text[len(HUELLE_KENNUNG):].partition(":")
        # Die Kennung des Anteils hat eine Form (acht Hexzeichen, wie im
        # Browser). Eine leere öffnete sonst als Hülle OHNE Anteil — der
        # Browser weist sie ab, die Referenz tat es bis KA-06b-1 nicht.
        if not re.fullmatch(r"[0-9a-f]{8}", kennung):
            raise InhaltFehler("kennung")
        text = KENNUNG + rest
    return oeffnen(schluessel, text, "wrap", art + (":" + kennung if kennung else ""))[0].decode()


# ------------------------------------------------------- Das Standbuch

def _hash(ref: str) -> bytes:
    return hashlib.sha256(STAND_INFO + ref.encode("utf-8")).digest()


def blatt_nr(ref: str) -> int:
    return _hash(ref)[0] >> 4


def kennung(ref: str) -> str:
    return base64.urlsafe_b64encode(_hash(ref)[1:10]).decode()


def _json(d: dict) -> bytes:
    return json.dumps(d, separators=(",", ":"), ensure_ascii=False).encode("utf-8")


def blatt_bauen(schluessel: bytes, nr: int, version: int, t: int, eintraege: dict, *,
                zu: bool = False, nonce: bytes | None = None) -> str:
    return verschliessen(schluessel, "stand", str(nr),
                         _json({"fassung": 1, "t": t, "zu": zu, "e": eintraege}), version, nonce=nonce)


def blatt_oeffnen(schluessel: bytes, text: str, nr: int, anker: int | None = None) -> tuple[dict, int]:
    """Das Blatt und seine Version. `anker`: die höchste Version, die dieser
    Browser von diesem Blatt gesehen hat."""
    klar, version = oeffnen(schluessel, text, "stand", str(nr))
    if anker is not None and version < anker:
        raise InhaltFehler("blatt-zurueckgelegt")
    try:
        d = json.loads(klar.decode("utf-8"))
    except ValueError as f:
        raise InhaltFehler("innen") from f
    if not isinstance(d, dict) or d.get("fassung") != 1 or not isinstance(d.get("e"), dict):
        raise InhaltFehler("innen")
    return d, version


def pat_lesen(schluessel: bytes, text: str, ref: str, blatt: dict | None) -> tuple[bytes, dict]:
    """Einen Patientenblock gegen sein (schon geöffnetes) Blatt lesen. Gibt
    die Nutzlast und einen Befund zurück: `z`, `gebunden` (Fassung 2) und
    `blatt_hinkt` (der Browser trägt den Eintrag nach)."""
    eintrag = (blatt or {}).get("e", {}).get(kennung(ref), {})
    stand = eintrag.get("p", 0)
    if not text.startswith(KENNUNG):
        klar = oeffnen1(schluessel, text)
        if stand:
            raise InhaltFehler("zurueckgelegt")       # Fassung 1, wo schon Fassung 2 lag
        if blatt and blatt.get("zu"):
            raise InhaltFehler("altformat")
        return klar, {"z": 0, "gebunden": False, "blatt_hinkt": False}
    klar, z = oeffnen(schluessel, text, "pat", ref)
    if z < stand:
        raise InhaltFehler("zurueckgelegt")
    return klar, {"z": z, "gebunden": True, "blatt_hinkt": z > stand}


# ------------------------------------------------------ Das Gerätebuch

def geraetebuch_bauen(schluessel: bytes, version: int, geraete: list[dict], *,
                      nonce: bytes | None = None) -> str:
    return verschliessen(schluessel, "geraete", "", _json({"fassung": 1, "geraete": geraete}),
                         version, nonce=nonce)


def geraetebuch_oeffnen(schluessel: bytes, text: str, anker: int | None = None) -> tuple[dict, int]:
    """Das Buch in der Form, die `Leser` in `referenz.py` nimmt —
    `{sid hex: {"spk": bytes, "bis": int | None}}` — und seine Version."""
    klar, version = oeffnen(schluessel, text, "geraete", "")
    if anker is not None and version < anker:
        raise InhaltFehler("buch-zurueckgelegt")
    try:
        d = json.loads(klar.decode("utf-8"))
        buch = {}
        for g in d["geraete"]:
            # SID und SPK in ihrer Form, wie im Browser (`standbuch.js`):
            # 16 Hexzeichen, ein öffentlicher P-256-Schlüssel mit 65 Byte.
            spk = base64.b64decode(g["spk"], validate=True)
            if not re.fullmatch(r"[0-9a-f]{16}", g["sid"]) or len(spk) != 65 or spk[0] != 4:
                raise InhaltFehler("innen")
            buch[g["sid"]] = {"spk": spk, "bis": g.get("bis")}
        return buch, version
    except (ValueError, KeyError, TypeError) as f:
        raise InhaltFehler("innen") from f


# ------------------------------------------------------- Die Ableitung

def ableitung1(passwort: str, salz: bytes, runden: int) -> tuple[bytes, bytes]:
    """Fassung 1: 64 Byte aus PBKDF2, geteilt in Hälfte und Token."""
    alles = hashlib.pbkdf2_hmac("sha256", passwort.encode("utf-8"), salz, runden, 64)
    return alles[:32], alles[32:]


def ableitung2(passwort: str, salz: bytes, runden: int) -> tuple[bytes, bytes, bytes]:
    """Fassung 2: Wurzel (ein Block), daraus Hälfte und Token. Die Wurzel
    verlässt die Funktion nur für die Prüfvektoren."""
    wurzel = hashlib.pbkdf2_hmac("sha256", passwort.encode("utf-8"), salz, runden, 32)
    teil = lambda was: HKDF(algorithm=hashes.SHA256(), length=32, salt=None,
                            info=(ABLEITUNG_INFO + was).encode()).derive(wurzel)
    return wurzel, teil("haelfte"), teil("token")


# ------------------------------------------------------------ Vektoren

def _s(name: str) -> bytes:
    return hashlib.sha256(f"nadoku-inhalt-vektor-{name}".encode()).digest()


def _n(name: str) -> bytes:
    return _s("nonce-" + name)[:12]


REF_A, REF_B = "w-20261111-0001", "w-20261111-0002"
T0 = 1794300000                      # 10.11.2026 — erfunden
PAT_A = '{"name":"Erika Mustermann","dx":"Polytrauma","age":41}'
PAT_GROSS = json.dumps({"dx": "Sturz", "notes": "Zufahrt über Forstweg, letzte 300 m zu Fuß. " * 16},
                       separators=(",", ":"), ensure_ascii=False)
ABLEITUNGEN = [("Korrekt-Pferd-Batterie-Klammer", "000102030405060708090a0b0c0d0e0f"),
               ("Prüf-ß-€ Passwort", "f0e1d2c3b4a5968778695a4b3c2d1e0f")]


def vektoren_bauen() -> dict:
    ck, dk, rc = _s("ck"), _s("dk"), _s("rc")
    aus: dict = {
        "format": "nadoku-inhalt-2",
        "hinweis": "Erzeugt von tools/proben/huelle/referenz_inhalt.py — nicht von Hand ändern.",
        "schluessel": {"ck_hex": ck.hex(), "dk_hex": dk.hex(), "rc_hex": rc.hex()},
        "faelle": [], "huellen": [], "stoerungen": [], "lagen": [], "ableitungen": [],
    }

    def fall(name, schluessel, zweck, bezug, nutzlast: bytes, z=0):
        zw: dict = {}
        text = verschliessen(schluessel, zweck, bezug, nutzlast, z, nonce=_n(name), zwischen=zw)
        aus["faelle"].append({"name": name, "schluessel_hex": schluessel.hex(), "zweck": zweck,
                              "bezug": bezug, "z": z, "nutzlast_hex": nutzlast.hex(),
                              "nonce_hex": zw["nonce"].hex(), "zusatzdaten_hex": zw["aad"].hex(),
                              "innen_laenge": zw["innen_laenge"], "text": text})
        return text

    pat_a = fall("pat", ck, "pat", REF_A, PAT_A.encode(), 3)
    fall("pat-leer", ck, "pat", REF_B, b"{}", 1)
    fall("pat-umlaut-ref", ck, "pat", "Prüf-ß-€", '{"dx":"Schädel-Hirn-Trauma"}'.encode(), 7)
    fall("pat-gross", ck, "pat", REF_B, PAT_GROSS.encode(), 2)
    kp = fall("kp", ck, "kp", "", base64.b64encode(_s("pkcs8-kp") * 4 + b"\x01" * 10))
    fall("rw", ck, "rw", "", base64.b64encode(_s("pkcs8-rw") * 4 + b"\x02" * 10))
    fall("ueber-4096", ck, "pat", REF_A, b"x" * 5000, 9)

    for name, schluessel, art, anteil in (("wrap-rc", rc, "rc", None), ("wrap-pw-anteil", dk, "pw", "1a2b3c4d"),
                                          ("wrap-pw-ohne-anteil", dk, "pw", None)):
        aus["huellen"].append({"name": name, "schluessel_hex": schluessel.hex(), "art": art,
                               "anteil_kennung": anteil, "nonce_hex": _n(name).hex(),
                               "text": huelle_bauen(schluessel, ck.hex(), art, anteil, nonce=_n(name))})
    wrap_pw = aus["huellen"][1]["text"]

    # --- Das Standbuch: das Blatt von REF_A, Version 17 ---
    nr = blatt_nr(REF_A)
    eintraege = {kennung(REF_A): {"p": 3, "o": 2, "k": 1, "s": [1200, 2], "d": 86400,
                                  "x": [[T0 + 600, T0 + 900]]}}
    blatt = blatt_bauen(ck, nr, 17, T0, eintraege, nonce=_n("blatt"))
    blatt_alt = blatt_bauen(ck, nr, 16, T0 - 86400, {kennung(REF_A): {"p": 2}}, nonce=_n("blatt-alt"))
    blatt_leer = blatt_bauen(ck, nr, 17, T0, {}, nonce=_n("blatt-leer"))
    blatt_zu = blatt_bauen(ck, nr, 18, T0 + 60, {}, zu=True, nonce=_n("blatt-zu"))
    aus["stand"] = {"client_ref": REF_A, "blatt": nr, "kennung": kennung(REF_A), "version": 17, "t": T0,
                    "eintraege": eintraege, "nonce_hex": _n("blatt").hex(), "text": blatt,
                    "blattnummern": {r: blatt_nr(r) for r in (REF_A, REF_B, "man-0123456789abcdef", "Prüf-ß-€")}}

    # --- Das Gerätebuch ---
    from referenz import _g, sec1, sid, T0 as T_HUELLE
    geraete = [{"sid": sid(sec1(_g(n).public_key())).hex(),
                "spk": base64.b64encode(sec1(_g(n).public_key())).decode(), "geraet": f"dev-{n}",
                "seit": T_HUELLE - 86400, "bis": bis} for n, bis in (("uhr", None), ("handy", T_HUELLE - 1))]
    buch = geraetebuch_bauen(ck, 4, geraete, nonce=_n("buch"))
    aus["geraetebuch"] = {"version": 4, "geraete": geraete, "nonce_hex": _n("buch").hex(), "text": buch}

    # --- Störungen am Chiffretext ---
    def stoer(name, grund, text, zweck="pat", bezug=REF_A, schluessel=ck):
        aus["stoerungen"].append({"name": name, "grund": grund, "schluessel_hex": schluessel.hex(),
                                  "zweck": zweck, "bezug": bezug, "text": text})

    def gekippt(text, i):
        roh = bytearray(base64.b64decode(text[len(KENNUNG):])); roh[i] ^= 1
        return KENNUNG + base64.b64encode(bytes(roh)).decode()

    roh = lambda innen, name: KENNUNG + base64.b64encode(
        _n(name) + AESGCM(ck).encrypt(_n(name), innen, zusatzdaten("pat", REF_A))).decode()
    stoer("vertauscht", "pruefwert", pat_a, bezug=REF_B)
    stoer("anderer-zweck", "pruefwert", pat_a, zweck="kp")
    stoer("kp-als-rw", "pruefwert", kp, zweck="rw", bezug="")
    stoer("pat-als-blatt", "pruefwert", pat_a, zweck="stand", bezug=str(nr))
    stoer("tag-gekippt", "pruefwert", gekippt(pat_a, -1))
    stoer("nonce-gekippt", "pruefwert", gekippt(pat_a, 0))
    stoer("ct-gekippt", "pruefwert", gekippt(pat_a, 12))
    stoer("falscher-schluessel", "pruefwert", pat_a, schluessel=dk)
    stoer("kennung-edk3", "kennung", "edk3:" + pat_a[len(KENNUNG):])
    stoer("fassung-1-als-2", "kennung", verschliessen1(ck, PAT_A.encode(), _n("alt")))
    stoer("kein-base64", "form", KENNUNG + "das ist kein Base64!")
    stoer("zu-kurz", "form", KENNUNG + base64.b64encode(b"\x00" * 27).decode())
    stoer("keine-klasse", "form", roh(struct.pack(">II", 1, 2) + b"{}" + b"\x00" * 90, "keine-klasse"))
    stoer("unter-mindestklasse", "form", roh(struct.pack(">II", 1, 2) + b"{}" + b"\x00" * 118, "klein"))
    stoer("polster-nicht-null", "polster", roh(struct.pack(">II", 1, 2) + b"{}" + b"\x00" * 501 + b"\x01", "polster"))
    stoer("laenge-luegt", "polster", roh(struct.pack(">II", 1, 600) + b"\x00" * 504, "laenge"))
    aus["huellen_stoerungen"] = [
        {"name": "anteil-kennung-umetikettiert", "grund": "pruefwert", "schluessel_hex": dk.hex(), "art": "pw",
         "text": wrap_pw.replace("1a2b3c4d", "ffffffff")},
        {"name": "pw-huelle-als-rc", "grund": "kennung", "schluessel_hex": dk.hex(), "art": "rc", "text": wrap_pw},
        {"name": "pw-ohne-anteil-als-rc", "grund": "pruefwert", "schluessel_hex": dk.hex(), "art": "rc",
         "text": aus["huellen"][2]["text"]},
        {"name": "rc-huelle-als-pw", "grund": "pruefwert", "schluessel_hex": rc.hex(), "art": "pw",
         "text": aus["huellen"][0]["text"]},
    ]

    # --- Lagen: ein Patientenblock gegen Blatt und Anker ---
    def lage(name, text, blatt_text, erwartet, anker=None, blatt_als=nr):
        f = {"name": name, "client_ref": REF_A, "text": text, "blatt": blatt_als,
             "blatt_text": blatt_text, "anker": anker}
        f.update({"grund": erwartet} if isinstance(erwartet, str) else {"erwartet": erwartet})
        aus["lagen"].append(f)

    z2 = verschliessen(ck, "pat", REF_A, b'{"dx":"alt"}', 2, nonce=_n("z2"))
    z4 = verschliessen(ck, "pat", REF_A, b'{"dx":"neu"}', 4, nonce=_n("z4"))
    alt1 = verschliessen1(ck, b'{"dx":"uralt"}', _n("alt1"))
    ohne_kennung = alt1[len(KENNUNG1):]
    lage("gut", pat_a, blatt, {"z": 3, "gebunden": True, "blatt_hinkt": False}, anker=17)
    lage("erster-browser-ohne-anker", pat_a, blatt, {"z": 3, "gebunden": True, "blatt_hinkt": False})
    lage("blatt-hinkt", z4, blatt, {"z": 4, "gebunden": True, "blatt_hinkt": True}, anker=17)
    lage("ohne-eintrag", pat_a, blatt_leer, {"z": 3, "gebunden": True, "blatt_hinkt": True}, anker=17)
    lage("ohne-blatt", pat_a, None, {"z": 3, "gebunden": True, "blatt_hinkt": True})
    lage("altbestand", alt1, blatt_leer, {"z": 0, "gebunden": False, "blatt_hinkt": False}, anker=17)
    lage("altbestand-ohne-kennung", ohne_kennung, blatt_leer, {"z": 0, "gebunden": False, "blatt_hinkt": False})
    lage("zurueckgelegt", z2, blatt, "zurueckgelegt", anker=17)
    lage("zurueckgelegt-auf-fassung-1", alt1, blatt, "zurueckgelegt", anker=17)
    lage("blatt-zurueckgelegt", z2, blatt_alt, "blatt-zurueckgelegt", anker=17)
    lage("altformat-nach-anhebung", alt1, blatt_zu, "altformat", anker=17)
    lage("blatt-unter-fremder-nummer", pat_a, blatt, "pruefwert", blatt_als=(nr + 1) % BLAETTER)
    lage("vertauschter-block", verschliessen(ck, "pat", REF_B, b'{"dx":"fremd"}', 9, nonce=_n("fremd")),
         blatt, "pruefwert", anker=17)
    aus["geraetebuch"]["gegenlagen"] = [
        {"name": "buch-zurueckgelegt", "grund": "buch-zurueckgelegt", "anker": 5, "text": buch},
        {"name": "blatt-als-buch", "grund": "pruefwert", "anker": None, "text": blatt},
    ]

    # --- Ableitung ---
    for passwort, salz_hex in ABLEITUNGEN:
        h1, t1 = ableitung1(passwort, bytes.fromhex(salz_hex), 600000)
        w2, h2, t2 = ableitung2(passwort, bytes.fromhex(salz_hex), 1200000)
        aus["ableitungen"].append({
            "passwort": passwort, "salz_hex": salz_hex,
            "fassung_1": {"runden": 600000, "haelfte_hex": h1.hex(), "token_hex": t1.hex()},
            "fassung_2": {"runden": 1200000, "wurzel_hex": w2.hex(), "haelfte_hex": h2.hex(),
                          "token_hex": t2.hex()}})
    return aus


def pruefen(v: dict, *, neu: dict | None = None) -> list[str]:
    """Jeden Fall neu rechnen und öffnen, jede Gegenlage mit ihrem Grund
    scheitern sehen."""
    fehler = []
    hx = bytes.fromhex
    if (neu if neu is not None else vektoren_bauen()) != v:
        fehler.append("vektoren-inhalt.json weicht von der Umsetzung ab — neu erzeugen?")

    def erwarte(was: str, grund: str | None, tun):
        try:
            ist = tun()
            if grund is not None:
                fehler.append(f"{was}: ging auf, erwartet {grund}")
            return ist
        except InhaltFehler as e:
            if str(e) != grund:
                fehler.append(f"{was}: Grund {e}, erwartet {grund or 'geht auf'}")
            return None

    for f in v["faelle"]:
        ist = erwarte(f["name"], None, lambda: oeffnen(hx(f["schluessel_hex"]), f["text"], f["zweck"], f["bezug"]))
        if ist is not None and (ist[0].hex(), ist[1]) != (f["nutzlast_hex"], f["z"]):
            fehler.append(f"{f['name']}: Nutzlast oder Zähler weicht ab")
        if not ist_klasse(f["innen_laenge"]):
            fehler.append(f"{f['name']}: Innenlänge {f['innen_laenge']} ist keine Klasse")
    for f in v["huellen"]:
        ist = erwarte(f["name"], None, lambda: huelle_oeffnen(hx(f["schluessel_hex"]), f["text"], f["art"]))
        if ist is not None and ist != v["schluessel"]["ck_hex"]:
            fehler.append(f"{f['name']}: Inhaltsschlüssel weicht ab")
    for s in v["stoerungen"]:
        erwarte("Störung " + s["name"], s["grund"],
                lambda: oeffnen(hx(s["schluessel_hex"]), s["text"], s["zweck"], s["bezug"]))
    for s in v["huellen_stoerungen"]:
        erwarte("Störung " + s["name"], s["grund"],
                lambda: huelle_oeffnen(hx(s["schluessel_hex"]), s["text"], s["art"]))
    ck = hx(v["schluessel"]["ck_hex"])
    st = v["stand"]
    ist = erwarte("Standbuch", None, lambda: blatt_oeffnen(ck, st["text"], st["blatt"], st["version"]))
    if ist is not None and (ist[0]["e"], ist[1]) != (st["eintraege"], st["version"]):
        fehler.append("Standbuch: Einträge oder Version weichen ab")
    if any(blatt_nr(r) != n for r, n in st["blattnummern"].items()) or kennung(st["client_ref"]) != st["kennung"]:
        fehler.append("Standbuch: Blattnummer oder Kennung weicht ab")
    for f in v["lagen"]:
        def lesen():
            blatt = blatt_oeffnen(ck, f["blatt_text"], f["blatt"], f["anker"])[0] if f["blatt_text"] else None
            return pat_lesen(ck, f["text"], f["client_ref"], blatt)[1]
        ist = erwarte("Lage " + f["name"], f.get("grund"), lesen)
        if ist is not None and "erwartet" in f and ist != f["erwartet"]:
            fehler.append(f"Lage {f['name']}: {ist}, erwartet {f['erwartet']}")
    gb = v["geraetebuch"]
    ist = erwarte("Gerätebuch", None, lambda: geraetebuch_oeffnen(ck, gb["text"], gb["version"]))
    if ist is not None and (ist[1] != gb["version"] or
                            {k: e["bis"] for k, e in ist[0].items()} != {g["sid"]: g["bis"] for g in gb["geraete"]}):
        fehler.append("Gerätebuch: Inhalt oder Version weicht ab")
    for g in gb["gegenlagen"]:
        erwarte("Gerätebuch " + g["name"], g["grund"], lambda: geraetebuch_oeffnen(ck, g["text"], g["anker"]))
    for a in v["ableitungen"]:
        f1, f2 = a["fassung_1"], a["fassung_2"]
        if f1["token_hex"] == f2["token_hex"] or f1["haelfte_hex"] == f2["haelfte_hex"]:
            fehler.append(f"Ableitung {a['passwort']}: Fassung 1 und 2 gleichen sich")
    return fehler


def zahlen(v: dict) -> str:
    gut = (len(v["faelle"]) + len(v["huellen"]) + 2 + sum("grund" not in f for f in v["lagen"])
           + 2 * len(v["ableitungen"]))
    rot = (len(v["stoerungen"]) + len(v["huellen_stoerungen"]) + sum("grund" in f for f in v["lagen"])
           + len(v["geraetebuch"]["gegenlagen"]))
    return f"Inhalt: {gut} Fälle geöffnet, {rot} Gegenlagen abgewiesen"


def selbstprobe() -> int:
    """Je Fehlerart ein Fall mit eingebautem Fehler und eine Gegenprobe."""
    v = vektoren_bauen()
    kopie = lambda: json.loads(json.dumps(v))
    faelle = [("Gegenprobe: frische Vektoren", not pruefen(v, neu=v))]

    def falsch(name, aendern):
        w = kopie(); aendern(w)
        faelle.append((name, bool(pruefen(w, neu=w))))

    falsch("Fehler: Nutzlast verfälscht", lambda w: w["faelle"][0].update(nutzlast_hex="00"))
    falsch("Fehler: Zähler verfälscht", lambda w: w["faelle"][0].update(z=4))
    falsch("Fehler: falscher Grund erwartet", lambda w: w["stoerungen"][0].update(grund="form"))
    falsch("Fehler: vertauschter Block geht auf", lambda w: w["stoerungen"][0].update(bezug=REF_A))
    falsch("Fehler: zurückgelegter Block geht auf", lambda w: w["lagen"][7].update(text=w["lagen"][0]["text"]))
    falsch("Fehler: zurückgelegtes Blatt geht auf", lambda w: w["lagen"][9].update(anker=16))
    falsch("Fehler: Altformat nach der Anhebung geht auf",
           lambda w: w["lagen"][10].update(blatt_text=w["lagen"][5]["blatt_text"]))
    falsch("Fehler: Blattnummer verfälscht", lambda w: w["stand"]["blattnummern"].update({REF_B: 99}))
    falsch("Fehler: Hülle mit falschem Inhaltsschlüssel", lambda w: w["schluessel"].update(ck_hex="00" * 32))
    falsch("Fehler: Gerätebuch zurückgelegt geht auf", lambda w: w["geraetebuch"]["gegenlagen"][0].update(anker=4))
    falsch("Fehler: Ableitungen gleichen sich",
           lambda w: w["ableitungen"][0]["fassung_2"].update(token_hex=w["ableitungen"][0]["fassung_1"]["token_hex"]))
    # Die Vektoren gegen eine unabhängige Rechnung der Ableitung (ohne HKDF-Klasse).
    import hmac as _hmac
    a = v["ableitungen"][0]
    wurzel = hashlib.pbkdf2_hmac("sha256", a["passwort"].encode(), bytes.fromhex(a["salz_hex"]), 1200000, 32)
    prk = _hmac.new(b"\x00" * 32, wurzel, hashlib.sha256).digest()
    token = _hmac.new(prk, b"nadoku-ableitung-2|token\x01", hashlib.sha256).digest()
    faelle.append(("Gegenprobe: Token von Hand nachgerechnet (HMAC statt HKDF-Klasse)",
                   token.hex() == a["fassung_2"]["token_hex"]))
    faelle.append(("Gegenprobe: Wurzel der Fassung 2 bei 600 000 Runden = Hälfte der Fassung 1",
                   ableitung2(a["passwort"], bytes.fromhex(a["salz_hex"]), 600000)[0].hex()
                   == a["fassung_1"]["haelfte_hex"]))
    # Jede Länge landet in einer Klasse und kommt unverändert zurück.
    ck = _s("ck")
    rund = True
    for n in list(range(0, 600, 7)) + [1015, 1016, 1017, 4088, 4089, 8184, 8185, 20000]:
        text = verschliessen(ck, "pat", "r", bytes(range(256)) * (n // 256) + bytes(range(n % 256)), n)
        klar, z = oeffnen(ck, text, "pat", "r")
        rund = rund and len(klar) == n and z == n and ist_klasse(len(base64.b64decode(text[5:])) - 28)
    faelle.append(("Gegenprobe: 94 Längen — jede in einer Klasse, jede kommt zurück", rund))
    # Das Gerätebuch in der Form, die der Leser der Gerätehülle nimmt.
    import referenz as R
    v2 = R.vektoren2_bauen()
    buch = geraetebuch_oeffnen(ck, v["geraetebuch"]["text"])[0]
    leser = R.Leser(R.schluessel_aus_name(R.EMPFAENGER), buch)
    f = v2["faelle"][2]
    faelle.append(("Gegenprobe: Gerätebuch öffnet eine Hülle der Uhr aus vektoren-2",
                   leser.huelle(base64.b64decode(f["huelle_b64"]), f["art"], f["client_ref"], f["platz"])[1]
                   == v2["geraete"]["uhr"]["sid_hex"]))
    e = v2["einsaetze"][0]
    try:
        R.einsatz_lesen(leser, e["client_ref"], base64.b64decode(e["huelle_b64"]), e["started_ts"])
        faelle.append(("Fehler: Hülle des ausgetragenen Handys geht auf", False))
    except R.HuelleFehler as f_:
        faelle.append(("Fehler: Hülle des ausgetragenen Handys geht auf", str(f_) == "absender-gesperrt"))
    # Drei Fälle aus der Gegenlesung des Browserkerns (KA-06b-1): Wo der
    # Browser strenger war, war die Referenz falsch.
    def grund(fn):
        try:
            fn(); return None
        except (InhaltFehler, R.HuelleFehler) as x:
            return str(x)
        except Exception as x:          # ungefangen = kein benannter Grund
            return "ungefangen:" + type(x).__name__
    leer = huelle_bauen(ck, "ab" * 32, "pw", "0011aabb").replace("edka2:0011aabb:", "edka2::")
    faelle.append(("Fehler: Hülle mit leerer Anteil-Kennung geht auf (Grund kennung)",
                   grund(lambda: huelle_oeffnen(ck, leer, "pw")) == "kennung"))
    krumm = geraetebuch_bauen(ck, 1, [{"sid": "00" * 8, "spk": base64.b64encode(b"\x04" + b"\x01" * 10).decode()}])
    faelle.append(("Fehler: Gerätebuch mit kurzem SPK geht auf (Grund innen)",
                   grund(lambda: geraetebuch_oeffnen(ck, krumm)) == "innen"))
    faelle.append(("Fehler: kaputtes gepacktes Stück ohne benannten Grund (Grund innen)",
                   grund(lambda: R.gepackt_lesen(b"kein zlib", 1, 0)) == "innen"))
    gleich = {len(verschliessen(ck, "pat", "r", b"x" * n)) for n in (0, 2, 100, 400, 504)}
    faelle.append(("Gegenprobe: 0 bis 504 Byte Nutzlast ergeben dieselbe Länge", len(gleich) == 1))
    schlecht = 0
    for name, gut in faelle:
        print(f"  {'ok  ' if gut else 'FEHL'}  {name}")
        schlecht += 0 if gut else 1
    print(f"{len(faelle)} Fälle, {schlecht} Fehlschläge")
    return 1 if schlecht else 0


def main() -> int:
    arg = sys.argv[1] if len(sys.argv) > 1 else ""
    if arg == "--selbstprobe":
        return selbstprobe()
    if arg == "erzeugen":
        v = vektoren_bauen()
        VEKTOREN.write_text(json.dumps(v, ensure_ascii=False, indent=2) + "\n", "utf-8")
        print(f"{zahlen(v)} → {VEKTOREN.name}")
        return 0
    if arg == "pruefen":
        if not VEKTOREN.exists():
            print("vektoren-inhalt.json fehlt"); return 2
        v = json.loads(VEKTOREN.read_text("utf-8"))
        fehler = pruefen(v)
        for f in fehler:
            print("  FEHL  " + f)
        print(f"{zahlen(v)}, {len(fehler)} Abweichungen")
        return 1 if fehler else 0
    print(__doc__)
    return 2


if __name__ == "__main__":
    sys.path.insert(0, str(HIER))
    sys.exit(main())
