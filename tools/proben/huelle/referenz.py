"""Die Gerätehülle, Fassung 1 — Referenz und Prüfvektoren (S11-02, E-S11-17).

Anlass: Nr. 43 — Spur, Phasenkoordinaten und Reanimation gehen verschlüsselt
an den Server; drei Umsetzungen (Garmin, Android, Browser) müssen dasselbe
Format byte-gleich schreiben und lesen.

WOFUER. Die Hülle entsteht an drei Stellen und wird an einer geöffnet. Ob
drei Umsetzungen dasselbe tun, zeigt nur ein gemeinsamer Satz fester Eingaben
mit erwarteten Ausgaben: diese Datei. Sie ist die Referenz, gegen die die
Browserprobe (`probe.mjs`, gegen `server/assets/crypto.js`), die Probe-App der Uhr
(`tools/uhr-pruefstand/krypto-probe/`) und später die Android-Prüffälle
laufen. Wer das Format ändert, ändert es HIER und erzeugt die Vektoren neu.

DAS FORMAT (Fassung 1, vorläufig bis zum Ergebnis der Uhrprobe, S11-02):

    Kennbyte   1 Byte    0x01 — die Fassung
    EPK       65 Byte    flüchtiger öffentlicher Schlüssel, P-256, SEC1
                         unkomprimiert (0x04 ‖ X ‖ Y, big-endian)
    IV        16 Byte    zufällig
    CT       16·n Byte   AES-256-CBC, Polsterung PKCS#7
    TAG       32 Byte    HMAC-SHA256(k_mac, Kennbyte ‖ EPK ‖ IV ‖ CT ‖ Bindung)

    Z      = ECDH(flüchtig privat, Empfänger öffentlich) — die X-Koordinate,
             32 Byte big-endian (was WebCrypto `deriveBits` liefert)
    OKM    = HKDF-SHA256(IKM = Z, salt = EPK,
                         info = "nadoku-huelle-1" ‖ Empfänger öffentlich (SEC1))
    k_enc  = OKM[0:32], k_mac = OKM[32:64]
    Bindung = je Feld 2 Byte Länge (big-endian) ‖ UTF-8:
              Art ("spur", "ruhe", "einsatz"), client_ref des Eigentümers,
              Platz ("<seq_from>:<punkte>" bei Spurstücken, sonst leer)

Erst verschlüsseln, dann prüfen: Wer öffnet, prüft das TAG vor jedem
Entschlüsseln und vergleicht in fester Zeit. Die Bindung steht NICHT in der
Hülle — der Öffnende setzt sie aus dem Datensatz zusammen, an dem die Hülle
hängt. Eine Hülle, die jemand mit Schreibzugriff auf die Datenbank an einen
anderen Einsatz hängt, geht dort nicht auf (E-S11-17). Die Zeiten gehören
nicht dazu (E-S11-18).

AUFRUF
    python3 referenz.py erzeugen     # vektoren.json und Vektoren.mc schreiben
    python3 referenz.py pruefen      # vektoren.json gegen diese Umsetzung
    python3 referenz.py --selbstprobe
"""
from __future__ import annotations

import base64
import hashlib
import hmac
import json
import pathlib
import struct
import sys

from cryptography.hazmat.primitives import hashes, padding
from cryptography.hazmat.primitives.asymmetric import ec
from cryptography.hazmat.primitives.ciphers import Cipher, algorithms, modes
from cryptography.hazmat.primitives.kdf.hkdf import HKDF
from cryptography.hazmat.primitives.serialization import Encoding, PublicFormat

HIER = pathlib.Path(__file__).resolve().parent
WURZEL = HIER.parents[2]
VEKTOREN = HIER / "vektoren.json"
UHR_VEKTOREN = WURZEL / "tools" / "uhr-pruefstand" / "krypto-probe" / "source" / "Vektoren.mc"

FASSUNG = 1
INFO = b"nadoku-huelle-1"
KOPF = 1 + 65 + 16
TAG = 32
# Ordnung der Kurve P-256 — feste Schlüssel werden aus einem Namen abgeleitet
# und in den gültigen Bereich gebracht, damit jede Zahl nachvollziehbar ist.
N_P256 = 0xFFFFFFFF00000000FFFFFFFFFFFFFFFFBCE6FAADA7179E84F3B9CAC2FC632551


class HuelleFehler(ValueError):
    """Eine Hülle, die nicht aufgeht — mit benanntem Grund."""


def schluessel_aus_name(name: str) -> ec.EllipticCurvePrivateKey:
    d = int.from_bytes(hashlib.sha256(name.encode()).digest(), "big") % (N_P256 - 1) + 1
    return ec.derive_private_key(d, ec.SECP256R1())


def sec1(pub: ec.EllipticCurvePublicKey) -> bytes:
    return pub.public_bytes(Encoding.X962, PublicFormat.UncompressedPoint)


def bindung(art: str, client_ref: str, platz: str) -> bytes:
    aus = b""
    for feld in (art, client_ref, platz):
        b = feld.encode("utf-8")
        if len(b) > 0xFFFF:
            raise HuelleFehler("bindung-zu-lang")
        aus += struct.pack(">H", len(b)) + b
    return aus


def schluessel_ableiten(z: bytes, epk: bytes, empf_pub: bytes) -> tuple[bytes, bytes]:
    okm = HKDF(algorithm=hashes.SHA256(), length=64, salt=epk,
               info=INFO + empf_pub).derive(z)
    return okm[:32], okm[32:]


def verschliessen(klartext: bytes, empf_pub: bytes, art: str, client_ref: str,
                  platz: str = "", *, fluechtig: ec.EllipticCurvePrivateKey | None = None,
                  iv: bytes | None = None, zwischen: dict | None = None) -> bytes:
    """Eine Hülle bauen. `fluechtig` und `iv` nur für Prüfvektoren fest."""
    empf = ec.EllipticCurvePublicKey.from_encoded_point(ec.SECP256R1(), empf_pub)
    fluechtig = fluechtig or ec.generate_private_key(ec.SECP256R1())
    iv = iv if iv is not None else __import__("os").urandom(16)
    epk = sec1(fluechtig.public_key())
    z = fluechtig.exchange(ec.ECDH(), empf)
    k_enc, k_mac = schluessel_ableiten(z, epk, empf_pub)
    p = padding.PKCS7(128).padder()
    gepolstert = p.update(klartext) + p.finalize()
    e = Cipher(algorithms.AES(k_enc), modes.CBC(iv)).encryptor()
    ct = e.update(gepolstert) + e.finalize()
    kopf = bytes([FASSUNG]) + epk + iv
    tag = hmac.new(k_mac, kopf + ct + bindung(art, client_ref, platz), hashlib.sha256).digest()
    if zwischen is not None:
        zwischen.update(epk=epk, z=z, k_enc=k_enc, k_mac=k_mac, gepolstert=gepolstert,
                        ct=ct, tag=tag)
    return kopf + ct + tag


def oeffnen(huelle: bytes, empf_priv: ec.EllipticCurvePrivateKey, art: str,
            client_ref: str, platz: str = "") -> bytes:
    if len(huelle) < KOPF + 16 + TAG or (len(huelle) - KOPF - TAG) % 16:
        raise HuelleFehler("laenge")
    if huelle[0] != FASSUNG:
        raise HuelleFehler("fassung")
    epk, iv = huelle[1:66], huelle[66:KOPF]
    ct, tag = huelle[KOPF:-TAG], huelle[-TAG:]
    try:
        eph = ec.EllipticCurvePublicKey.from_encoded_point(ec.SECP256R1(), epk)
    except ValueError as f:
        raise HuelleFehler("schluessel") from f
    z = empf_priv.exchange(ec.ECDH(), eph)
    k_enc, k_mac = schluessel_ableiten(z, epk, sec1(empf_priv.public_key()))
    soll = hmac.new(k_mac, huelle[:-TAG] + bindung(art, client_ref, platz),
                    hashlib.sha256).digest()
    if not hmac.compare_digest(soll, tag):
        raise HuelleFehler("pruefwert")
    d = Cipher(algorithms.AES(k_enc), modes.CBC(iv)).decryptor()
    gepolstert = d.update(ct) + d.finalize()
    try:
        u = padding.PKCS7(128).unpadder()
        return u.update(gepolstert) + u.finalize()
    except ValueError as f:
        raise HuelleFehler("polsterung") from f


# ------------------------------------------------------------- Vektoren

HOEHE_UNBEKANNT = -32768


def innen_binaer(punkte: list[tuple[float, float, float | None, int]]) -> bytes:
    """Die Innenform eines Spurstücks (E-S11-31): je Punkt 14 Byte —
    Breite und Länge in 1e-7 Grad (int32), Höhe in Dezimetern (int16), Zeit
    in Sekunden relativ zu ts_von (uint32), alles big-endian. Ein Punkt ohne
    Höhe trägt HOEHE_UNBEKANNT (-32768, E-S11-39); jede echte Höhe liegt
    zwischen -3276,7 und 3276,7 m."""
    return b"".join(struct.pack(">iihI", round(la * 1e7), round(lo * 1e7),
                                HOEHE_UNBEKANNT if h is None else round(h * 10), t)
                    for la, lo, h, t in punkte)


def innen_json(punkte: list[tuple[float, float, float, int]]) -> bytes:
    """Kandidat B: JSON, Koordinaten mit sechs Nachkommastellen, Höhe mit einer."""
    return json.dumps({"p": [[round(la, 6), round(lo, 6), round(h, 1), t]
                             for la, lo, h, t in punkte]}, separators=(",", ":")).encode()


def beispielpunkte(n: int) -> list[tuple[float, float, float, int]]:
    """Eine feste, erfundene Spur bei Kempten — kein Ort aus einem Einsatz."""
    return [(47.7261 + i * 0.00031, 10.3159 + i * 0.00047, 674.0 + (i % 37) * 0.7, i * 5)
            for i in range(n)]


EMPFAENGER = "nadoku-huelle-vektor-empfaenger"
FAELLE = [
    # Name, Art, client_ref, Platz, Klartext
    ("leer", "einsatz", "w-20261011-0001", "", b""),
    ("kurz", "einsatz", "w-20261011-0001", "", b"nadoku"),
    ("ein-block", "spur", "w-20261011-0002", "0:1", b"0123456789abcdef"),
    ("spur-binaer-3", "spur", "w-20261011-0002", "120:3", innen_binaer(beispielpunkte(3))),
    ("ruhe-json-3", "ruhe", "r-20261011-0007", "0:3", innen_json(beispielpunkte(3))),
    ("umlaut-ref", "einsatz", "Prüf-ß-€", "", b'{"ph":{"4":[47.7,10.3]}}'),
]
# Was beim Öffnen scheitern MUSS — je mit dem erwarteten Grund.
STOERUNGEN = [
    ("tag-gekippt", "pruefwert"), ("ct-gekippt", "pruefwert"), ("iv-gekippt", "pruefwert"),
    ("andere-ref", "pruefwert"), ("anderer-platz", "pruefwert"), ("andere-art", "pruefwert"),
    ("fassung-2", "fassung"), ("gekuerzt", "laenge"),
]


def b64(b: bytes) -> str:
    return base64.b64encode(b).decode()


def vektoren_bauen() -> dict:
    empf = schluessel_aus_name(EMPFAENGER)
    empf_pub = sec1(empf.public_key())
    d = empf.private_numbers().private_value.to_bytes(32, "big")
    aus = {
        "format": "nadoku-huelle-1",
        "hinweis": "Erzeugt von tools/proben/huelle/referenz.py — nicht von Hand ändern.",
        "empfaenger": {"privat_hex": d.hex(), "oeffentlich_hex": empf_pub.hex()},
        "faelle": [], "stoerungen": [],
    }
    for name, art, ref, platz, klar in FAELLE:
        fl = schluessel_aus_name(f"nadoku-huelle-vektor-fluechtig-{name}")
        iv = hashlib.sha256(f"nadoku-huelle-vektor-iv-{name}".encode()).digest()[:16]
        zw: dict = {}
        h = verschliessen(klar, empf_pub, art, ref, platz, fluechtig=fl, iv=iv, zwischen=zw)
        aus["faelle"].append({
            "name": name, "art": art, "client_ref": ref, "platz": platz,
            "klartext_hex": klar.hex(),
            "fluechtig_privat_hex": fl.private_numbers().private_value.to_bytes(32, "big").hex(),
            "iv_hex": iv.hex(), "epk_hex": zw["epk"].hex(), "z_hex": zw["z"].hex(),
            "k_enc_hex": zw["k_enc"].hex(), "k_mac_hex": zw["k_mac"].hex(),
            "bindung_hex": bindung(art, ref, platz).hex(),
            "ct_hex": zw["ct"].hex(), "tag_hex": zw["tag"].hex(), "huelle_b64": b64(h),
        })
    basis = aus["faelle"][3]
    h = base64.b64decode(basis["huelle_b64"])
    for name, grund in STOERUNGEN:
        art, ref, platz = basis["art"], basis["client_ref"], basis["platz"]
        g = bytearray(h)
        if name == "tag-gekippt": g[-1] ^= 1
        elif name == "ct-gekippt": g[KOPF] ^= 1
        elif name == "iv-gekippt": g[66] ^= 1
        elif name == "andere-ref": ref = ref + "x"
        elif name == "anderer-platz": platz = "121:3"
        elif name == "andere-art": art = "ruhe"
        elif name == "fassung-2": g[0] = 2
        elif name == "gekuerzt": g = g[:-1]
        aus["stoerungen"].append({"name": name, "grund": grund, "art": art,
                                  "client_ref": ref, "platz": platz, "huelle_b64": b64(bytes(g))})
    return aus


def mc_bytes(hexwert: str) -> str:
    b = bytes.fromhex(hexwert)
    return "[" + ", ".join(f"0x{x:02x}" for x in b) + "]b"


def uhr_vektoren(v: dict) -> str:
    """Die Vektoren als Monkey-C-Quelltext für die Probe-App. Die Uhr bekommt
    die Werte so, wie der Server sie liefern wird (SEC1, big-endian); das
    Umsetzen in Garmins Byteordnung ist Teil dessen, was die Probe prüft."""
    zeilen = ["// ERZEUGT von tools/proben/huelle/referenz.py erzeugen — nicht von Hand ändern.",
              "// Anlass: entfällt — Erzeuger (E-BR-05)",
              "import Toybox.Lang;", "", "module Vektoren {",
              f"    const EMPF_PUB = {mc_bytes(v['empfaenger']['oeffentlich_hex'])};",
              "    const FAELLE = ["]
    for f in v["faelle"]:
        if f["name"] not in ("kurz", "spur-binaer-3"):
            continue
        zeilen += ["        {",
                   f"            :name => \"{f['name']}\",",
                   f"            :klar => {mc_bytes(f['klartext_hex'] or '00')},",
                   f"            :klarLeer => {'true' if not f['klartext_hex'] else 'false'},",
                   f"            :bindung => {mc_bytes(f['bindung_hex'])},",
                   f"            :fluechtig => {mc_bytes(f['fluechtig_privat_hex'])},",
                   f"            :iv => {mc_bytes(f['iv_hex'])},",
                   f"            :epk => {mc_bytes(f['epk_hex'])},",
                   f"            :z => {mc_bytes(f['z_hex'])},",
                   f"            :kEnc => {mc_bytes(f['k_enc_hex'])},",
                   f"            :kMac => {mc_bytes(f['k_mac_hex'])},",
                   f"            :ct => {mc_bytes(f['ct_hex'])},",
                   f"            :tag => {mc_bytes(f['tag_hex'])}",
                   "        },"]
    zeilen += ["    ];", "}", ""]
    return "\n".join(zeilen)


def pruefen(v: dict) -> list[str]:
    """Jeden Fall neu rechnen, jede Hülle öffnen, jede Störung scheitern sehen."""
    fehler = []
    neu = vektoren_bauen()
    if neu != v:
        fehler.append("vektoren.json weicht von der Umsetzung ab — neu erzeugen?")
    empf = ec.derive_private_key(int(v["empfaenger"]["privat_hex"], 16), ec.SECP256R1())
    for f in v["faelle"]:
        try:
            k = oeffnen(base64.b64decode(f["huelle_b64"]), empf, f["art"], f["client_ref"], f["platz"])
            if k.hex() != f["klartext_hex"]:
                fehler.append(f"{f['name']}: Klartext weicht ab")
        except HuelleFehler as e:
            fehler.append(f"{f['name']}: geht nicht auf ({e})")
    for s in v["stoerungen"]:
        try:
            oeffnen(base64.b64decode(s["huelle_b64"]), empf, s["art"], s["client_ref"], s["platz"])
            fehler.append(f"Störung {s['name']}: ging auf")
        except HuelleFehler as e:
            if str(e) != s["grund"]:
                fehler.append(f"Störung {s['name']}: Grund {e}, erwartet {s['grund']}")
    return fehler


def selbstprobe() -> int:
    """Je Fehlerart ein Fall mit eingebautem Fehler und eine Gegenprobe."""
    v = vektoren_bauen()
    faelle = []
    faelle.append(("Gegenprobe: frische Vektoren", not pruefen(v)))
    w = json.loads(json.dumps(v)); w["faelle"][1]["klartext_hex"] = "00"
    faelle.append(("Fehler: Klartext verfälscht", bool(pruefen(w))))
    w = json.loads(json.dumps(v)); w["stoerungen"][0]["grund"] = "fassung"
    faelle.append(("Fehler: falscher Grund erwartet", bool(pruefen(w))))
    w = json.loads(json.dumps(v)); w["stoerungen"][0]["huelle_b64"] = w["faelle"][3]["huelle_b64"]
    faelle.append(("Fehler: Störung geht auf", bool(pruefen(w))))
    empf = schluessel_aus_name(EMPFAENGER)
    pub = sec1(empf.public_key())
    zufall = verschliessen(b"x" * 100, pub, "spur", "r", "0:1")
    faelle.append(("Gegenprobe: zufällige Hülle geht auf",
                   oeffnen(zufall, empf, "spur", "r", "0:1") == b"x" * 100))
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
        UHR_VEKTOREN.parent.mkdir(parents=True, exist_ok=True)
        UHR_VEKTOREN.write_text(uhr_vektoren(v), "utf-8")
        print(f"{len(v['faelle'])} Fälle, {len(v['stoerungen'])} Störungen → {VEKTOREN.name}, "
              f"{UHR_VEKTOREN.relative_to(WURZEL)}")
        return 0
    if arg == "pruefen":
        if not VEKTOREN.exists():
            print("vektoren.json fehlt"); return 2
        v = json.loads(VEKTOREN.read_text("utf-8"))
        fehler = pruefen(v)
        if not fehler and UHR_VEKTOREN.read_text("utf-8") != uhr_vektoren(v):
            fehler.append("Vektoren.mc weicht ab — neu erzeugen")
        for f in fehler:
            print("  FEHL  " + f)
        print(f"{len(v['faelle'])} Fälle geöffnet, {len(v['stoerungen'])} Störungen abgewiesen, "
              f"{len(fehler)} Abweichungen")
        return 1 if fehler else 0
    print(__doc__)
    return 2


if __name__ == "__main__":
    sys.exit(main())
