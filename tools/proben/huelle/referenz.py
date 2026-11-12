"""Die Gerätehülle, Fassung 1 und 2 — Referenz und Prüfvektoren.

Anlass: Nr. 43 und 563 — Spur, Phasenkoordinaten und Reanimation gehen
verschlüsselt an den Server; drei Umsetzungen (Garmin, Android, Browser)
müssen dasselbe Format byte-gleich schreiben und lesen. Fassung 2 (Nr. 563,
Konzept KA, KA-06a) belegt dazu Herkunft, Zeit und Vollständigkeit.

WOFUER. Die Hülle entsteht an drei Stellen und wird an einer geöffnet. Ob
drei Umsetzungen dasselbe tun, zeigt nur ein gemeinsamer Satz fester Eingaben
mit erwarteten Ausgaben: diese Datei. Sie ist die Referenz, gegen die die
Browserprobe (`probe.mjs`, gegen `server/assets/crypto.js`), die Probe-App der Uhr
(`tools/uhr-pruefstand/krypto-probe/`) und die Android-Prüffälle
laufen. Wer das Format ändert, ändert es HIER und erzeugt die Vektoren neu.

Die Chiffretexte unter dem Inhaltsschlüssel (`edk2:`), das Standbuch gegen
das Zurücklegen, das Gerätebuch und die Ableitung aus dem Passwort stehen in
der Schwesterdatei `referenz_inhalt.py`: Sie haben andere Umsetzer (Browser
und PHP, nie Uhr oder Handy) und ein anderes Verfahren (AES-GCM).

=========================================================================
FASSUNG 1 (S11) — bleibt lesbar, ihre Vektoren (`vektoren.json`) gelten fort
=========================================================================

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
anderen Einsatz hängt, geht dort nicht auf. Die Zeiten gehören
nicht dazu.

WAS FASSUNG 1 NICHT BELEGT (Audit KA, F-KA-12): Wer den öffentlichen
Kontoschlüssel kennt — der Server, jedes gekoppelte Gerät —, baut gültige
Hüllen. Die Zeiten stehen relativ zu einem Klartextanker (`ts_von`,
`started_at`) und lassen sich verschieben. Ein fehlendes Stück und eine
ältere Einsatzhülle fallen nicht auf.

=========================================================================
FASSUNG 2 (KA-06) — Herkunft, Zeit, Zähler, Vollständigkeit
=========================================================================

    Kennbyte   1 Byte    0x02
    SID        8 Byte    Kennung des Absenders:
                         SHA-256("nadoku-absender-1" ‖ SPK)[0:8]
    EPK       65 Byte    flüchtiger öffentlicher Schlüssel (wie Fassung 1)
    IV        16 Byte    zufällig
    CT       16·n Byte   AES-256-CBC, Polsterung PKCS#7
    TAG       32 Byte    HMAC-SHA256(k_mac,
                                     Kennbyte ‖ SID ‖ EPK ‖ IV ‖ CT ‖ Bindung)

    SPK    = der STATISCHE öffentliche Schlüssel des Absenders (SEC1, 65 Byte)
    Z_e    = ECDH(flüchtig privat,          Konto öffentlich)
    Z_s    = ECDH(Absender statisch privat, Konto öffentlich)
    OKM    = HKDF-SHA256(IKM = Z_e ‖ Z_s, salt = EPK,
                         info = "nadoku-huelle-2" ‖ Konto öffentlich ‖ SPK)
    k_enc  = OKM[0:32], k_mac = OKM[32:64]
    Bindung  wie Fassung 1 (Art, client_ref, Platz)

DIE HERKUNFT OHNE SIGNATUR. Die Garmin-Uhr kann nicht signieren (Connect IQ
9.2.0 hat nur ECDH, F-KG-11), ein dauerhaftes P-256-Paar hält sie (F-KG-12).
Der Schlüssel der Hülle entsteht deshalb aus ZWEI Geheimnissen: dem
flüchtigen (wie bisher — eine gefundene Uhr öffnet keine alte Hülle) und dem
statischen des Absenders. Das TAG stimmt nur, wenn der Absender den privaten
Teil zu SPK hatte. Das Muster ist das „One-Pass Unified Model" C(1e, 2s) aus
NIST SP 800-56A; es braucht nur, was alle drei Geräte heute schon rechnen.
Z_s ist je Paar (Absender, Konto) fest — ein Gerät darf es einmal rechnen
und neben seinem Schlüssel ablegen; dann kostet eine Hülle so viel wie in
Fassung 1.

WER ABSENDER IST.
    Gerät    SPK = sein Geräteschlüssel, erzeugt bei der Kopplung. Der
             Browser kennt ihn aus dem GERÄTEBUCH (unter dem Inhaltsschlüssel,
             `referenz_inhalt.py`) — bestätigt über den Vergleichswert unten.
    Konto    SPK = der öffentliche Kontoschlüssel selbst; Z_s = ECDH(Konto
             privat, Konto öffentlich). So verschließt der Browser (Formular,
             Wartungslauf, Schnitt, Import): Nur wer den privaten Teil des
             Kontopaars hat, kann das.
Eine SID, die weder das Konto noch ein Gerät des Gerätebuchs ist, scheitert
mit `absender` — vor jeder Rechnung.

WAS DIE HERKUNFT BEWEIST UND WAS NICHT. Sie beweist dem BROWSER DES KONTOS,
dass der Absender den privaten Teil eines bestätigten Schlüssels hatte. Sie
beweist nichts gegenüber Dritten und nichts gegenüber dem Server: Prüfen
kann nur, wer den privaten Kontoschlüssel hat — und wer den hat, kann jede
Gerätehülle auch selbst bauen (es ist ein gemeinsames Geheimnis, keine
Signatur). Der Server prüft weiter nur Form und Größe.

INNEN, SPURSTÜCK (alle Stufen):

    ts_von      8 Byte   uint64 big-endian — ABSOLUTE Zeit (Unix-Sekunden),
                         auf die sich die Punktzeiten beziehen
    Merker      1 Byte   Bit 0 SCHLUSS       letztes Stück der Aufzeichnung
                         Bit 1 LUECKE        (nur Konto) beim Zusammenfügen
                                             fehlte etwas, bekannt
                         Bit 2 UEBERNOMMEN   (nur Konto) enthält Daten ohne
                                             Herkunftsnachweis (aus Fassung 1)
                         übrige Bits 0
    n_herkunft  1 Byte   Zahl der Herkunftseinträge (Geräte: 0; Konto: 0–8)
    Reserve     2 Byte   0
    Herkunft    8·n      SIDs der Geräte, deren Stücke das Konto geprüft und
                         hier zusammengefügt hat
    uebernommen 8 Byte   nur mit Bit 2: wann das Konto übernommen hat (uint64)
    Nutzlast             Stufe 1: je Punkt 14 Byte wie Fassung 1
                         Stufe 2, 3: die gepackte Form „B fest" wie Fassung 1
                         — Zeiten relativ zum ts_von DIESES Kopfs

    Platz wie Fassung 1: "<seq_from>:<punkte>", gepackt
    "<seq_from>:<punkte>:<stufe>:<n_original>". NEU: ein leeres Schlussstück
    "<seq>:0" (nur Kopf, Bit SCHLUSS) — wenn beim Abschluss kein Punkt mehr
    offen ist.

INNEN, EINSATZHÜLLE: JSON, UTF-8, Schlüssel in dieser Reihenfolge:

    { "fassung": 2,
      "z": 3,                    Zähler — steigt mit jedem Verschließen
                                 dieses Einsatzes durch diesen Absender
      "start": 1784277065,       ABSOLUTE Zeit des Einsatzbeginns
      "schluss": true,           die Hülle des abgeschlossenen Einsatzes
      "phasen": [ … ], "rea": [ … ],      wie Fassung 1, t seit "start"
      "herkunft": { "von": ["<sid hex>"], "uebernommen": 1794300000 } }
                                 nur Konto: wessen Daten es weiterträgt

DIE STÜCKFOLGE — verkettet über die DECKUNG, nicht über einen Hash. Die Uhr
verschließt ein Stück bei jedem Sendeversuch neu und schneidet es nach einem
Fehlschlag anders zu; eine Kette über den Prüfwert des Vorgängers risse im
Regelbetrieb. Was fest ist: Die Punkte einer Aufzeichnung tragen die Nummern
0, 1, 2 … ohne Lücke, und der Platz (gebunden) nennt, welche ein Stück trägt.
Daraus die Regeln beim Lesen (`spur_lesen()`):
    luecke         die Stücke decken die Nummern nicht lückenlos ab 0, und
                   kein belegter Löschvermerk erklärt die Lücke
    ohne-schluss   die Aufzeichnung gilt als abgeschlossen, aber kein Stück
                   mit SCHLUSS steht am Ende
    zeit           Klartext-`ts_von` − `ts_von` der Hülle ≠ belegter Versatz
    zurueckgelegt  das Standbuch kennt einen weiteren Stand als den gelieferten
    absender-gesperrt   das Gerät war zu dieser Zeit schon ausgetragen
    innen          ein Gerät gibt sich als Konto aus (Herkunftsliste, Merker,
                   Stufe ≥ 2) oder der Kopf ist unbrauchbar
    ohne-herkunft  eine Hülle der Fassung 1, wo Fassung 2 verlangt ist
Versatz, Löschvermerke und Stand kommen aus dem STANDBUCH
(`referenz_inhalt.py`), nie aus dem Klartext der Datenbank.

DER VERGLEICHSWERT DER KOPPLUNG — der zweite Kanal für beide öffentlichen
Schlüssel (F-KA-17, Bedrohungsmodell 6.3). Sechs Ziffern, die Gerät und
Webseite zeigen. Kurz darf er nur sein, weil ihn niemand vorab kennt:

    1. Gerät, `start`:    schickt SPK und die
                          Zusage = SHA-256("nadoku-kopplung-zusage-1" ‖ N_g ‖ SPK)
                          N_g: 16 Byte Zufall, noch geheim
    2. Browser:           holt SPK und Zusage zum Code, DANN schickt er
                          N_b (16 Byte Zufall)
    3. Gerät, `status`:   bekommt Kontoschlüssel K und N_b, DANN legt es N_g
                          offen
    4. Beide:             Wert = dezimal6(SHA-256("nadoku-kopplung-wert-1"
                                          ‖ K ‖ SPK ‖ N_g ‖ N_b)[0:4])
                          der Browser prüft vorher die Zusage (`zusage`)

Ohne die Zusage rechnete, wer den Server beschreibt, in Millisekunden ein
Schlüsselpaar aus, dessen Wert passt (eine Million Möglichkeiten). Mit ihr
muss er sich festlegen, bevor er die Zufallszahl der Gegenseite kennt, und
trifft je Versuch mit eins zu einer Million — sichtbar als falscher Wert.

AUFRUF
    python3 referenz.py erzeugen     # vektoren.json, vektoren-2.json und die vier .mc-Dateien der Uhr
                                     # (Vektoren.mc, HuelleVektor.mc, Vektoren2.mc, HuelleVektor2.mc)
    python3 referenz.py pruefen      # beide Vektordateien gegen diese Umsetzung, die .mc-Dateien dagegen
    python3 referenz.py --selbstprobe
"""
from __future__ import annotations

import base64
import hashlib
import hmac
import json
import pathlib
import struct
import zlib
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
# Der Selbsttest der Uhr-App: EIN Fall, mit dem die App
# beim ersten Senden ihre Byteordnung und die ganze Kette prüft.
APP_VEKTOR = WURZEL / "watch" / "source" / "HuelleVektor.mc"
# Dasselbe für Fassung 2 (KG-03a): die Fälle der Probe-App und der eine Fall
# des Selbsttests `Huelle2.bereit()`. Eigene Dateien — die der Fassung 1
# bleiben byte-gleich.
UHR_VEKTOREN2 = UHR_VEKTOREN.with_name("Vektoren2.mc")
APP_VEKTOR2 = APP_VEKTOR.with_name("HuelleVektor2.mc")

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
HOEHE_GRENZE = 32767


def hoehe_innen(h: float | None) -> int:
    """Höhe → int16 in GANZEN METERN, gerundet von der Null weg wie
    PHPs `round()`, geklemmt auf ±32 767 m — ein Ausreißer läuft nicht über.
    Bis zum 16.10.2026 stand hier ein Faktor 10 (Dezimeter): Er
    reichte nur bis 3276,7 m und lief darüber still in eine negative Höhe.
    """
    if h is None:
        return HOEHE_UNBEKANNT
    m = int(abs(h) + 0.5) * (1 if h >= 0 else -1)
    return max(-HOEHE_GRENZE, min(HOEHE_GRENZE, m))


def innen_binaer(punkte: list[tuple[float, float, float | None, int]]) -> bytes:
    """Die Innenform eines Spurstücks: je Punkt 14 Byte —
    Breite und Länge in 1e-7 Grad (int32), Höhe in ganzen Metern (int16), Zeit
    in Sekunden relativ zu ts_von (uint32), alles big-endian. Ein Punkt ohne
    Höhe trägt HOEHE_UNBEKANNT (-32768); jede echte Höhe liegt
    zwischen -32 767 und 32 767 m."""
    return b"".join(struct.pack(">iihI", round(la * 1e7), round(lo * 1e7), hoehe_innen(h), t)
                    for la, lo, h, t in punkte)


def gepackt_roh(punkte: list[tuple[float, float, float | None, int]], ts_von: int) -> bytes:
    """Die Innenform der Stufen 2 und 3, VOR dem Packen („B fest"):
    vier Spalten zu je n Werten int32 big-endian — Breite, Länge (1e-7 Grad),
    Höhe (ganze Meter, HOEHE_UNBEKANNT = keine), Zeit (Sekunden seit ts_von) —,
    jeder Wert als Abstand zum vorigen derselben Spalte, der erste zu 0. Die
    Höhe geht als Wert der Spalte ein, auch HOEHE_UNBEKANNT: Der Abstand
    zweier int16-Werte passt immer in int32."""
    spalten = [[round(la * 1e7) for la, _, _, _ in punkte],
               [round(lo * 1e7) for _, lo, _, _ in punkte],
               [hoehe_innen(h) for _, _, h, _ in punkte],
               [t - ts_von for _, _, _, t in punkte]]
    aus = []
    for sp in spalten:
        alt = 0
        for v in sp:
            aus.append(v - alt)
            alt = v
    return struct.pack(">%di" % len(aus), *aus)


def innen_gepackt(punkte: list[tuple[float, float, float | None, int]], ts_von: int) -> bytes:
    """`gepackt_roh()` gepackt: zlib (RFC 1950), wie `CompressionStream('deflate')`
    im Browser und `gzcompress()` in PHP. Die Bytes nach dem Packen müssen
    zwischen den dreien nicht gleich sein — gleich sein muss, was herauskommt."""
    return zlib.compress(gepackt_roh(punkte, ts_von), 6)


def gepackt_lesen(innen: bytes, n: int, ts_von: int) -> list[tuple[float, float, float | None, int]]:
    """Die Gegenrichtung zu `innen_gepackt()`: n Punkte (Breite, Länge, Höhe, Zeit)."""
    # Ein kaputtes gepacktes Stück scheitert mit benanntem Grund (`innen`),
    # wie im Browser — bis KA-06b-1 kam hier `zlib.error` ungefangen durch.
    try:
        roh = zlib.decompress(innen)
    except zlib.error as f:
        raise HuelleFehler("innen") from f
    if len(roh) != 16 * n:
        raise HuelleFehler("innen")
    werte = struct.unpack(">%di" % (4 * n), roh)
    spalten = []
    for k in range(4):
        alt, sp = 0, []
        for v in werte[k * n:(k + 1) * n]:
            alt += v
            sp.append(alt)
        spalten.append(sp)
    return [(spalten[0][i] / 1e7, spalten[1][i] / 1e7,
             None if spalten[2][i] == HOEHE_UNBEKANNT else spalten[2][i], ts_von + spalten[3][i])
            for i in range(n)]


def platz_gepackt(seq_from: int, punkte: int, stufe: int, n_original: int) -> str:
    """Der Ort einer Hülle der Stufe 2 oder 3: Stufe und Marke sind
    mitgebunden, damit der Server sie nicht umetikettieren kann."""
    return f"{seq_from}:{punkte}:{stufe}:{n_original}"


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
# Gepackte Stücke (Stufe 2, 3): Name, Art, client_ref, Stufe, seq_from,
# n_original, Punkte (Breite, Länge, Höhe, Zeit) — eine Höhe fehlt, eine ist
# negativ, die Länge fällt (negativer Abstand).
GEPACKT = [
    ("gepackt-stufe-2", "ruhe", "r-20261105-0024", 2, 40, 5,
     [(47.7261, 10.3159, 674.0, 1000), (47.7264, 10.3155, None, 1060), (47.7268, 10.315, 681.0, 1120),
      (47.7271, 10.3143, -12.0, 1180), (47.7275, 10.3139, 690.0, 1240)]),
    ("gepackt-stufe-3", "spur", "m-20261105-0024", 3, 0, 120,
     [(47.5591, 10.217, 812.0, 2000), (47.5644, 10.2311, 1405.0, 2093), (47.6123, 10.2899, 930.0, 2290)]),
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
    # GEPACKT (Stufe 2 und 3, Konzept RD, Nr. 500): dieselbe Hülle um die
    # Innenform „B fest". Ein eigener Block und keine Zeile in FAELLE — die
    # Uhr und die App lesen FAELLE, und packen tun sie nicht. `probe_server.php`
    # hält `gh_gepackt_bauen()` byte-gleich dagegen. Die Punkte liegen auf
    # ganzen 1e-7 Grad, damit keine Rundung an einer halben Stelle entscheidet
    # (Python rundet dort zur geraden Zahl, PHP von der Null weg).
    aus["gepackt"] = []
    for name, art, ref, stufe, seq, n_orig, punkte in GEPACKT:
        fl = schluessel_aus_name(f"nadoku-huelle-vektor-fluechtig-{name}")
        iv = hashlib.sha256(f"nadoku-huelle-vektor-iv-{name}".encode()).digest()[:16]
        ts_von = min(t for _, _, _, t in punkte)
        platz = platz_gepackt(seq, len(punkte), stufe, n_orig)
        h = verschliessen(innen_gepackt(punkte, ts_von), empf_pub, art, ref, platz, fluechtig=fl, iv=iv)
        aus["gepackt"].append({
            "name": name, "art": art, "client_ref": ref, "stufe": stufe, "seq_from": seq,
            "n_original": n_orig, "punkte": [[seq + i, la, lo, hh, t] for i, (la, lo, hh, t) in enumerate(punkte)],
            "roh_hex": gepackt_roh(punkte, ts_von).hex(), "platz": platz,
            "fluechtig_privat_hex": fl.private_numbers().private_value.to_bytes(32, "big").hex(),
            "iv_hex": iv.hex(), "huelle_b64": b64(h),
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


# =========================================================== Fassung 2

FASSUNG2 = 2
INFO2 = b"nadoku-huelle-2"
ABSENDER_INFO = b"nadoku-absender-1"
SID_LAENGE = 8
KOPF2 = 1 + SID_LAENGE + 65 + 16
M_SCHLUSS, M_LUECKE, M_UEBERNOMMEN = 1, 2, 4
SPUR_KOPF2 = 12
HERKUNFT_MAX = 8
KONTO = "konto"                      # der Absender „Konto" in den Befunden


def sid(spk: bytes) -> bytes:
    """Die Kennung eines Absenders: 8 Byte aus seinem statischen Schlüssel."""
    return hashlib.sha256(ABSENDER_INFO + spk).digest()[:SID_LAENGE]


def schluessel_ableiten2(z_e: bytes, z_s: bytes, epk: bytes, konto_pub: bytes,
                         spk: bytes) -> tuple[bytes, bytes]:
    okm = HKDF(algorithm=hashes.SHA256(), length=64, salt=epk,
               info=INFO2 + konto_pub + spk).derive(z_e + z_s)
    return okm[:32], okm[32:]


def verschliessen2(klartext: bytes, konto_pub: bytes, absender: ec.EllipticCurvePrivateKey,
                   art: str, client_ref: str, platz: str = "", *,
                   fluechtig: ec.EllipticCurvePrivateKey | None = None,
                   iv: bytes | None = None, zwischen: dict | None = None) -> bytes:
    """Eine Hülle der Fassung 2. `absender` ist der statische private Schlüssel
    des Geräts — oder der private Kontoschlüssel, wenn das Konto verschließt."""
    empf = ec.EllipticCurvePublicKey.from_encoded_point(ec.SECP256R1(), konto_pub)
    fluechtig = fluechtig or ec.generate_private_key(ec.SECP256R1())
    iv = iv if iv is not None else __import__("os").urandom(16)
    epk = sec1(fluechtig.public_key())
    spk = sec1(absender.public_key())
    z_e = fluechtig.exchange(ec.ECDH(), empf)
    z_s = absender.exchange(ec.ECDH(), empf)
    k_enc, k_mac = schluessel_ableiten2(z_e, z_s, epk, konto_pub, spk)
    p = padding.PKCS7(128).padder()
    gepolstert = p.update(klartext) + p.finalize()
    e = Cipher(algorithms.AES(k_enc), modes.CBC(iv)).encryptor()
    ct = e.update(gepolstert) + e.finalize()
    kopf = bytes([FASSUNG2]) + sid(spk) + epk + iv
    tag = hmac.new(k_mac, kopf + ct + bindung(art, client_ref, platz), hashlib.sha256).digest()
    if zwischen is not None:
        zwischen.update(epk=epk, spk=spk, sid=sid(spk), z_e=z_e, z_s=z_s, k_enc=k_enc,
                        k_mac=k_mac, ct=ct, tag=tag)
    return kopf + ct + tag


def oeffnen2(huelle: bytes, konto_priv: ec.EllipticCurvePrivateKey, absender_spk,
             art: str, client_ref: str, platz: str = "") -> tuple[bytes, bytes]:
    """Eine Hülle der Fassung 2 öffnen. `absender_spk(sid)` liefert den
    statischen öffentlichen Schlüssel zur Kennung oder None. Gibt Klartext
    und SID zurück. Reihenfolge der Prüfungen: Länge, Fassung, Absender,
    Schlüssel, Prüfwert, Polsterung."""
    if len(huelle) < KOPF2 + 16 + TAG or (len(huelle) - KOPF2 - TAG) % 16:
        raise HuelleFehler("laenge")
    if huelle[0] != FASSUNG2:
        raise HuelleFehler("fassung")
    s, epk, iv = huelle[1:9], huelle[9:74], huelle[74:KOPF2]
    ct, tag = huelle[KOPF2:-TAG], huelle[-TAG:]
    spk = absender_spk(s)
    if spk is None:
        raise HuelleFehler("absender")
    try:
        eph = ec.EllipticCurvePublicKey.from_encoded_point(ec.SECP256R1(), epk)
        stat = ec.EllipticCurvePublicKey.from_encoded_point(ec.SECP256R1(), spk)
    except ValueError as f:
        raise HuelleFehler("schluessel") from f
    z_e = konto_priv.exchange(ec.ECDH(), eph)
    z_s = konto_priv.exchange(ec.ECDH(), stat)
    k_enc, k_mac = schluessel_ableiten2(z_e, z_s, epk, sec1(konto_priv.public_key()), spk)
    soll = hmac.new(k_mac, huelle[:-TAG] + bindung(art, client_ref, platz),
                    hashlib.sha256).digest()
    if not hmac.compare_digest(soll, tag):
        raise HuelleFehler("pruefwert")
    d = Cipher(algorithms.AES(k_enc), modes.CBC(iv)).decryptor()
    gepolstert = d.update(ct) + d.finalize()
    try:
        u = padding.PKCS7(128).unpadder()
        return u.update(gepolstert) + u.finalize(), s
    except ValueError as f:
        raise HuelleFehler("polsterung") from f


# ------------------------------------------------- Innenformen, Fassung 2

def spur_innen2(ts_von: int, nutzlast: bytes, *, schluss: bool = False, luecke: bool = False,
                herkunft: tuple[bytes, ...] = (), uebernommen: int | None = None) -> bytes:
    """Kopf und Nutzlast eines Spurstücks der Fassung 2. `herkunft`, `luecke`
    und `uebernommen` setzt nur das Konto."""
    if len(herkunft) > HERKUNFT_MAX or any(len(h) != SID_LAENGE for h in herkunft):
        raise HuelleFehler("innen")
    merker = ((M_SCHLUSS if schluss else 0) | (M_LUECKE if luecke else 0)
              | (M_UEBERNOMMEN if uebernommen is not None else 0))
    aus = struct.pack(">QBBH", ts_von, merker, len(herkunft), 0) + b"".join(herkunft)
    if uebernommen is not None:
        aus += struct.pack(">Q", uebernommen)
    return aus + nutzlast


def spur_innen2_lesen(innen: bytes) -> dict:
    if len(innen) < SPUR_KOPF2:
        raise HuelleFehler("innen")
    ts_von, merker, n, reserve = struct.unpack(">QBBH", innen[:SPUR_KOPF2])
    if reserve or merker & ~(M_SCHLUSS | M_LUECKE | M_UEBERNOMMEN) or n > HERKUNFT_MAX:
        raise HuelleFehler("innen")
    o = SPUR_KOPF2
    if len(innen) < o + 8 * n + (8 if merker & M_UEBERNOMMEN else 0):
        raise HuelleFehler("innen")
    herkunft = [innen[o + 8 * i:o + 8 * i + 8] for i in range(n)]
    o += 8 * n
    uebernommen = None
    if merker & M_UEBERNOMMEN:
        uebernommen = struct.unpack(">Q", innen[o:o + 8])[0]
        o += 8
    return {"ts_von": ts_von, "merker": merker, "herkunft": herkunft,
            "uebernommen": uebernommen, "nutzlast": innen[o:]}


def einsatz_innen2(z: int, start: int, schluss: bool, phasen: list, rea: list,
                   herkunft: dict | None = None) -> bytes:
    """Die Einsatzhülle innen — die Schlüssel in genau dieser Reihenfolge,
    ohne Leerraum, UTF-8 (nicht \\u-maskiert)."""
    d: dict = {"fassung": 2, "z": z, "start": start, "schluss": schluss,
               "phasen": phasen, "rea": rea}
    if herkunft is not None:
        d["herkunft"] = herkunft
    return json.dumps(d, separators=(",", ":"), ensure_ascii=False).encode("utf-8")


# ------------------------------------------------------ Lesen mit Regeln

class Leser:
    """Was der Browser des Kontos zum Öffnen hat: den privaten Kontoschlüssel
    und das Gerätebuch — `{sid hex: {"spk": bytes, "bis": int | None}}`.
    `verlangt2`: Fassung 1 gilt nicht mehr (nach dem Stichtag, oder wo das
    Standbuch für diesen Datensatz schon einen Stand der Fassung 2 kennt)."""

    def __init__(self, konto_priv: ec.EllipticCurvePrivateKey, geraete: dict,
                 verlangt2: bool = False):
        self.priv = konto_priv
        self.pub = sec1(konto_priv.public_key())
        self.konto_sid = sid(self.pub)
        self.geraete = geraete
        self.verlangt2 = verlangt2

    def _spk(self, s: bytes) -> bytes | None:
        if s == self.konto_sid:
            return self.pub
        g = self.geraete.get(s.hex())
        return g["spk"] if g else None

    def huelle(self, huelle: bytes, art: str, ref: str, platz: str = "") -> tuple[bytes, str | None]:
        """Klartext und Absender: None (Fassung 1), "konto" oder die SID (hex)."""
        if huelle[:1] == bytes([FASSUNG]):
            if self.verlangt2:
                raise HuelleFehler("ohne-herkunft")
            return oeffnen(huelle, self.priv, art, ref, platz), None
        klar, s = oeffnen2(huelle, self.priv, self._spk, art, ref, platz)
        return klar, KONTO if s == self.konto_sid else s.hex()

    def gesperrt(self, absender: str | None, zeit: int) -> bool:
        """War dieses Gerät zur Zeit der Daten schon ausgetragen?"""
        g = self.geraete.get(absender or "")
        return bool(g) and g.get("bis") is not None and zeit > g["bis"]


def _ueberdeckt(sperren, t_vor: int | None, t_nach: int | None) -> bool:
    """Erklärt ein belegter Löschvermerk die Lücke zwischen zwei Zeiten?
    None heißt offen (vor dem ersten, nach dem letzten Punkt)."""
    for von, bis in sperren:
        if (t_nach is None or von < t_nach) and (t_vor is None or bis > t_vor):
            return True
    return False


def spur_lesen(leser: Leser, art: str, ref: str, stuecke: list[dict], *, versatz: int = 0,
               sperren: tuple = (), abgeschlossen: bool = False,
               stand: tuple[int, int] | None = None) -> tuple[list, dict]:
    """Die Stücke einer Aufzeichnung öffnen und die Folge prüfen.

    `stuecke`: je Stück der Klartext der Datenbank — `seq_from`, `punkte`,
    `stufe`, `n_original` (ab Stufe 2), `ts_von` — und `huelle` (Bytes).
    `versatz`, `sperren` [(von, bis) in der Zeit der Hülle] und `stand`
    (gedeckt bis, Stufe) kommen aus dem Standbuch. `abgeschlossen` ist das
    Klartextkennzeichen `final`.

    Gibt die Punkte (Nummer, Breite, Länge, Höhe, Zeit + Versatz) und einen
    Befund zurück: `herkunft` (wer verschlossen hat), `schluss`, `fassung1`,
    `gedeckt`. Wirft `HuelleFehler` mit benanntem Grund."""
    offen = []
    for st in stuecke:
        stufe = st.get("stufe", 1)
        platz = (platz_gepackt(st["seq_from"], st["punkte"], stufe, st["n_original"])
                 if stufe >= 2 else f"{st['seq_from']}:{st['punkte']}")
        klar, absender = leser.huelle(st["huelle"], art, ref, platz)
        if absender is None:                          # Fassung 1
            kopf = {"ts_von": st["ts_von"] - versatz, "merker": 0, "herkunft": [],
                    "uebernommen": None, "nutzlast": klar}
        else:
            kopf = spur_innen2_lesen(klar)
            if st["ts_von"] - kopf["ts_von"] != versatz:
                raise HuelleFehler("zeit")
            if absender != KONTO and (stufe >= 2 or kopf["herkunft"]
                                      or kopf["merker"] & (M_LUECKE | M_UEBERNOMMEN)):
                raise HuelleFehler("innen")
        if stufe >= 2:
            pkt = gepackt_lesen(kopf["nutzlast"], st["punkte"], kopf["ts_von"]) if st["punkte"] else []
            bis = st["seq_from"] + st["n_original"]
        else:
            if len(kopf["nutzlast"]) != 14 * st["punkte"]:
                raise HuelleFehler("innen")
            pkt = []
            for i in range(st["punkte"]):
                la, lo, h, t = struct.unpack(">iihI", kopf["nutzlast"][14 * i:14 * i + 14])
                pkt.append((la / 1e7, lo / 1e7, None if h == HOEHE_UNBEKANNT else h,
                            kopf["ts_von"] + t))
            bis = st["seq_from"] + st["punkte"]
        zeiten = [p[3] for p in pkt]
        if absender not in (None, KONTO) and leser.gesperrt(absender, max(zeiten, default=kopf["ts_von"])):
            raise HuelleFehler("absender-gesperrt")
        offen.append({"von": st["seq_from"], "bis": bis, "stufe": stufe, "pkt": pkt,
                      "absender": absender, "merker": kopf["merker"],
                      "t_erst": min(zeiten, default=None), "t_letzt": max(zeiten, default=None)})

    fassung1 = any(o["absender"] is None for o in offen)
    offen.sort(key=lambda o: (o["von"], -o["bis"]))
    ende, t_ende = 0, None
    for o in offen:
        if o["von"] > ende and not fassung1 and not _ueberdeckt(sperren, t_ende, o["t_erst"]):
            raise HuelleFehler("luecke")
        if o["bis"] > ende:
            ende = o["bis"]
            t_ende = o["t_letzt"] if o["t_letzt"] is not None else t_ende
    # SCHLUSS zählt nur an einem Stück, das bis ans Ende der Deckung reicht.
    schluss = any(o["merker"] & M_SCHLUSS and o["bis"] == ende for o in offen)
    if stand is not None and (ende < stand[0] or max((o["stufe"] for o in offen), default=0) < stand[1]):
        raise HuelleFehler("zurueckgelegt")
    if abgeschlossen and not schluss and not fassung1 and not _ueberdeckt(sperren, t_ende, None):
        raise HuelleFehler("ohne-schluss")

    # Entdoppeln wie bisher: gepackte Stücke zuerst, dann Gerätestücke über der Marke.
    nach: dict[int, tuple] = {}
    marke = 0
    for o in offen:
        if o["stufe"] >= 2:
            marke = max(marke, o["bis"])
            for i, p in enumerate(o["pkt"]):
                nach.setdefault(o["von"] + i, p)
    for o in offen:
        if o["stufe"] < 2:
            for i, p in enumerate(o["pkt"]):
                if o["von"] + i >= marke:
                    nach.setdefault(o["von"] + i, p)
    punkte = [(n, la, lo, h, t + versatz) for n, (la, lo, h, t) in sorted(nach.items())
              if not any(von <= t <= bis for von, bis in sperren)]
    herkunft = sorted({("fassung-1" if o["absender"] is None else o["absender"]) for o in offen})
    return punkte, {"herkunft": herkunft, "schluss": schluss, "fassung1": fassung1, "gedeckt": ende}


def einsatz_lesen(leser: Leser, ref: str, huelle: bytes, started_ts: int, *, versatz: int = 0,
                  stand: dict | None = None) -> tuple[dict, str | None]:
    """Die Einsatzhülle öffnen und gegen Klartext und Standbuch halten.
    `started_ts`: der Klartextbeginn des Einsatzes. `stand`: der Eintrag des
    Standbuchs — `o` (Zähler der zuletzt abgenommenen Hülle), `k` (1: sie kam
    vom Konto). Gibt Inhalt und Absender zurück."""
    klar, absender = leser.huelle(huelle, "einsatz", ref, "")
    try:
        d = json.loads(klar.decode("utf-8"))
    except ValueError as f:
        raise HuelleFehler("innen") from f
    if absender is None:
        if stand and stand.get("o"):
            raise HuelleFehler("zurueckgelegt")       # Fassung 1 nach einer abgenommenen Fassung 2
        return d, None
    if (not isinstance(d, dict) or d.get("fassung") != 2 or type(d.get("z")) is not int
            or d["z"] < 1 or type(d.get("start")) is not int):
        raise HuelleFehler("innen")
    if absender != KONTO and "herkunft" in d:
        raise HuelleFehler("innen")
    if started_ts - d["start"] != versatz:
        raise HuelleFehler("zeit")
    if absender != KONTO and leser.gesperrt(absender, d["start"]):
        raise HuelleFehler("absender-gesperrt")
    if stand and stand.get("o"):
        if d["z"] < stand["o"] or (stand.get("k") and absender != KONTO):
            raise HuelleFehler("zurueckgelegt")
    return d, absender


# ----------------------------------------------- Kopplung: Vergleichswert

ZUSAGE_INFO = b"nadoku-kopplung-zusage-1"
WERT_INFO = b"nadoku-kopplung-wert-1"
NONCE_LAENGE = 16


def kopplung_zusage(n_g: bytes, spk: bytes) -> bytes:
    """Was das Gerät bei `start` schickt, bevor es N_g offenlegt."""
    if len(n_g) != NONCE_LAENGE or len(spk) != 65:
        raise HuelleFehler("laenge")
    return hashlib.sha256(ZUSAGE_INFO + n_g + spk).digest()


def kopplung_wert(konto_pub: bytes, spk: bytes, n_g: bytes, n_b: bytes,
                  zusage: bytes | None = None) -> str:
    """Der Vergleichswert: sechs Ziffern. Mit `zusage` prüft er sie zuerst
    (das tut der Browser) und wirft `zusage`, wenn sie nicht passt."""
    if len(konto_pub) != 65 or len(spk) != 65 or len(n_g) != NONCE_LAENGE or len(n_b) != NONCE_LAENGE:
        raise HuelleFehler("laenge")
    if zusage is not None and not hmac.compare_digest(zusage, kopplung_zusage(n_g, spk)):
        raise HuelleFehler("zusage")
    h = hashlib.sha256(WERT_INFO + konto_pub + spk + n_g + n_b).digest()
    return "%06d" % (int.from_bytes(h[:4], "big") % 1000000)


# ------------------------------------------------- Vektoren, Fassung 2

VEKTOREN2 = HIER / "vektoren-2.json"
T0 = 1784277065                      # 16.07.2026 08:31:05 UTC — erfunden
GERAETE2 = ("uhr", "handy", "fremd")  # „fremd" steht in keinem Gerätebuch
REF_A, REF_B = "w-20261111-0002", "w-20261111-0003"
PHASEN2 = [{"phase": 4, "t": 1197, "lat": 47.5601, "lon": 10.7002},
           {"phase": 5, "t": 1310, "lat": None, "lon": None}]
REA2 = [{"t": 1445, "ereignisse": [{"art": "adrenalin", "t": 1617}]}]


def _g(name: str) -> ec.EllipticCurvePrivateKey:
    return schluessel_aus_name(f"nadoku-huelle2-vektor-geraet-{name}")


def _hex32(k: ec.EllipticCurvePrivateKey) -> str:
    return k.private_numbers().private_value.to_bytes(32, "big").hex()


def _punkte(von: int, n: int) -> list[tuple[float, float, float, int]]:
    """Punkte `von` … `von+n-1` der Beispielspur, Zeit relativ zum ersten."""
    alle = beispielpunkte(von + n)[von:]
    return [(la, lo, h, t - alle[0][3]) for la, lo, h, t in alle]


def _fest(name: str) -> tuple[ec.EllipticCurvePrivateKey, bytes]:
    return (schluessel_aus_name(f"nadoku-huelle2-vektor-fluechtig-{name}"),
            hashlib.sha256(f"nadoku-huelle2-vektor-iv-{name}".encode()).digest()[:16])


def _zu(name: str, wer: str, konto: ec.EllipticCurvePrivateKey, art: str, ref: str, platz: str,
        klar: bytes, zwischen: dict | None = None) -> bytes:
    fl, iv = _fest(name)
    absender = konto if wer == KONTO else _g(wer)
    return verschliessen2(klar, sec1(konto.public_key()), absender, art, ref, platz,
                          fluechtig=fl, iv=iv, zwischen=zwischen)


def _zu1(name: str, konto_pub: bytes, art: str, ref: str, platz: str, klar: bytes) -> bytes:
    fl, iv = _fest(name)
    return verschliessen(klar, konto_pub, art, ref, platz, fluechtig=fl, iv=iv)


def _gefaelscht(name: str, konto_pub: bytes, art: str, ref: str, platz: str, klar: bytes) -> bytes:
    """Die stärkste Fälschung ohne Geräteschlüssel: Der Angreifer kennt den
    Kontoschlüssel und SPK der Uhr, rechnet aber mit SEINEM statischen
    Schlüssel und schreibt die SID der Uhr in den Kopf."""
    fl, iv = _fest(name)
    empf = ec.EllipticCurvePublicKey.from_encoded_point(ec.SECP256R1(), konto_pub)
    epk, spk = sec1(fl.public_key()), sec1(_g("uhr").public_key())
    k_enc, k_mac = schluessel_ableiten2(fl.exchange(ec.ECDH(), empf),
                                        _g("fremd").exchange(ec.ECDH(), empf), epk, konto_pub, spk)
    p = padding.PKCS7(128).padder()
    e = Cipher(algorithms.AES(k_enc), modes.CBC(iv)).encryptor()
    ct = e.update(p.update(klar) + p.finalize()) + e.finalize()
    kopf = bytes([FASSUNG2]) + sid(spk) + epk + iv
    return kopf + ct + hmac.new(k_mac, kopf + ct + bindung(art, ref, platz), hashlib.sha256).digest()


def _buecher(konto_pub: bytes) -> dict:
    eintrag = lambda n, bis=None: {"spk_hex": sec1(_g(n).public_key()).hex(), "bis": bis}
    s = lambda n: sid(sec1(_g(n).public_key())).hex()
    return {"standard": {s("uhr"): eintrag("uhr"), s("handy"): eintrag("handy")},
            "uhr-ausgetragen": {s("uhr"): eintrag("uhr", T0 + 10), s("handy"): eintrag("handy")}}


def _leser(v: dict, buch: str = "standard", verlangt2: bool = False) -> Leser:
    konto = ec.derive_private_key(int(v["konto"]["privat_hex"], 16), ec.SECP256R1())
    return Leser(konto, {k: {"spk": bytes.fromhex(e["spk_hex"]), "bis": e["bis"]}
                         for k, e in v["buecher"][buch].items()}, verlangt2)


def vektoren2_bauen() -> dict:
    konto = schluessel_aus_name(EMPFAENGER)
    kpub = sec1(konto.public_key())
    sid_uhr = sid(sec1(_g("uhr").public_key()))
    aus: dict = {
        "format": "nadoku-huelle-2",
        "hinweis": "Erzeugt von tools/proben/huelle/referenz.py — nicht von Hand ändern.",
        "konto": {"privat_hex": _hex32(konto), "oeffentlich_hex": kpub.hex(), "sid_hex": sid(kpub).hex()},
        "geraete": {n: {"privat_hex": _hex32(_g(n)), "oeffentlich_hex": sec1(_g(n).public_key()).hex(),
                        "sid_hex": sid(sec1(_g(n).public_key())).hex()} for n in GERAETE2},
        "buecher": _buecher(kpub),
        "faelle": [], "gepackt": [], "stoerungen": [], "folgen": [], "einsaetze": [],
    }

    # --- Fälle: je Absender und Innenform einer, byte-gleich nachzubauen ---
    faelle = [
        ("kurz", "uhr", "einsatz", "w-20261111-0001", "", b"nadoku"),
        ("leer", "handy", "einsatz", "w-20261111-0001", "", b""),
        ("spur-3", "uhr", "spur", REF_A, "120:3", spur_innen2(T0 + 600, innen_binaer(_punkte(120, 3)))),
        ("spur-schluss", "uhr", "spur", REF_A, "123:2",
         spur_innen2(T0 + 615, innen_binaer(_punkte(123, 2)), schluss=True)),
        ("ruhe-schluss-leer", "handy", "ruhe", "r-20261111-0007", "40:0",
         spur_innen2(T0, b"", schluss=True)),
        ("einsatz-json", "handy", "einsatz", "m-20261111-0004", "",
         einsatz_innen2(3, T0, True, PHASEN2, REA2)),
        ("konto-einsatz", KONTO, "einsatz", "man-0123456789abcdef", "",
         einsatz_innen2(4, T0, True, PHASEN2, REA2,
                        {"von": [sid_uhr.hex()], "uebernommen": T0 + 86400})),
        ("umlaut-ref", "uhr", "einsatz", "Prüf-ß-€", "",
         einsatz_innen2(1, T0, False, [{"phase": 2, "t": 0, "lat": 47.7, "lon": 10.3}], [])),
    ]
    for name, wer, art, ref, platz, klar in faelle:
        zw: dict = {}
        h = _zu(name, wer, konto, art, ref, platz, klar, zw)
        fl, iv = _fest(name)
        aus["faelle"].append({
            "name": name, "absender": wer, "art": art, "client_ref": ref, "platz": platz,
            "klartext_hex": klar.hex(), "fluechtig_privat_hex": _hex32(fl), "iv_hex": iv.hex(),
            "sid_hex": zw["sid"].hex(), "epk_hex": zw["epk"].hex(), "z_e_hex": zw["z_e"].hex(),
            "z_s_hex": zw["z_s"].hex(), "k_enc_hex": zw["k_enc"].hex(), "k_mac_hex": zw["k_mac"].hex(),
            "bindung_hex": bindung(art, ref, platz).hex(), "ct_hex": zw["ct"].hex(),
            "tag_hex": zw["tag"].hex(), "huelle_b64": b64(h),
        })

    # --- Gepackt: nur das Konto packt; die Bytes nach zlib dürfen abweichen ---
    for name, art, ref, stufe, seq, n_orig, punkte in GEPACKT:
        ts_von = min(t for _, _, _, t in punkte)
        platz = platz_gepackt(seq, len(punkte), stufe, n_orig)
        kopf = spur_innen2(T0 + ts_von, b"", schluss=True, herkunft=(sid_uhr,),
                           uebernommen=(T0 + 86400) if stufe == 3 else None)
        h = _zu(name, KONTO, konto, art, ref, platz, kopf + innen_gepackt(punkte, ts_von))
        fl, iv = _fest(name)
        aus["gepackt"].append({
            "name": name, "absender": KONTO, "art": art, "client_ref": ref, "stufe": stufe,
            "seq_from": seq, "n_original": n_orig, "ts_von": T0 + ts_von, "platz": platz,
            "punkte": [[seq + i, la, lo, hh, T0 + t] for i, (la, lo, hh, t) in enumerate(punkte)],
            "kopf_hex": kopf.hex(), "roh_hex": gepackt_roh(punkte, ts_von).hex(),
            "fluechtig_privat_hex": _hex32(fl), "iv_hex": iv.hex(), "huelle_b64": b64(h),
        })

    # --- Störungen an der Hülle selbst (Fall „spur-3") ---
    b = aus["faelle"][2]
    h = base64.b64decode(b["huelle_b64"])
    klar = bytes.fromhex(b["klartext_hex"])

    def stoer(name, grund, huelle, art=b["art"], ref=b["client_ref"], platz=b["platz"], verlangt2=False):
        aus["stoerungen"].append({"name": name, "grund": grund, "art": art, "client_ref": ref,
                                  "platz": platz, "verlangt2": verlangt2, "huelle_b64": b64(bytes(huelle))})

    def gekippt(i):
        g = bytearray(h); g[i] ^= 1; return g

    stoer("tag-gekippt", "pruefwert", gekippt(-1))
    stoer("ct-gekippt", "pruefwert", gekippt(KOPF2))
    stoer("iv-gekippt", "pruefwert", gekippt(74))
    stoer("epk-gekippt", "schluessel", gekippt(9))                    # 0x04 → 0x05: kein Punkt
    stoer("sid-gekippt", "absender", gekippt(1))
    stoer("sid-des-handys", "pruefwert", h[:1] + bytes.fromhex(aus["geraete"]["handy"]["sid_hex"]) + h[9:])
    stoer("andere-ref", "pruefwert", h, ref=REF_B)                    # vertauscht
    stoer("anderer-platz", "pruefwert", h, platz="121:3")
    stoer("andere-art", "pruefwert", h, art="ruhe")
    stoer("fassung-3", "fassung", bytes([3]) + h[1:])
    stoer("gekuerzt", "laenge", h[:-1])
    stoer("fremdes-geraet", "absender", _zu("stoer-fremd", "fremd", konto, b["art"], b["client_ref"], b["platz"], klar))
    stoer("ohne-geraeteschluessel", "pruefwert",
          _gefaelscht("stoer-gefaelscht", kpub, b["art"], b["client_ref"], b["platz"], klar))
    stoer("fassung-1-wo-2-verlangt", "ohne-herkunft",
          _zu1("stoer-fassung1", kpub, b["art"], b["client_ref"], b["platz"], innen_binaer(_punkte(120, 3))),
          verlangt2=True)

    # --- Folgen: eine Aufzeichnung in drei Stücken, 8 Punkte, alle 5 s ---
    def stueck(name, von, n, *, wer="uhr", ref=REF_A, schluss=False, fassung=2, ts_klar=0, **kopf):
        p = _punkte(von, n)
        ts = T0 + 5 * von
        platz = f"{von}:{n}"
        if fassung == 1:
            h_ = _zu1(name, kpub, "spur", ref, platz, innen_binaer(p))
        else:
            h_ = _zu(name, wer, konto, "spur", ref, platz,
                     spur_innen2(ts, innen_binaer(p), schluss=schluss, **kopf))
        return {"seq_from": von, "punkte": n, "stufe": 1, "ts_von": ts + ts_klar, "huelle_b64": b64(h_)}

    A, B, C = stueck("f-a", 0, 3), stueck("f-b", 3, 3), stueck("f-c", 6, 2, schluss=True)
    packpunkte = [(la, lo, float(int(h_)), 5 * i) for i, (la, lo, h_, _) in enumerate(beispielpunkte(8))]
    P = {"seq_from": 0, "punkte": 8, "stufe": 2, "n_original": 8, "ts_von": T0,
         "huelle_b64": b64(_zu("f-p", KONTO, konto, "spur", REF_A, platz_gepackt(0, 8, 2, 8),
                               spur_innen2(T0, innen_gepackt(packpunkte, 0), schluss=True,
                                           herkunft=(sid_uhr,))))}

    def folge(name, stuecke, erwartet, *, abgeschlossen=True, versatz=0, sperren=(), stand=None,
              buch="standard", verlangt2=False):
        f = {"name": name, "art": "spur", "client_ref": REF_A, "stuecke": stuecke,
             "abgeschlossen": abgeschlossen, "versatz": versatz, "sperren": [list(s) for s in sperren],
             "stand": list(stand) if stand else None, "buch": buch, "verlangt2": verlangt2}
        f.update({"grund": erwartet} if isinstance(erwartet, str) else {"erwartet": erwartet})
        aus["folgen"].append(f)

    def gut(n, erste, letzte, herkunft, schluss=True, fassung1=False):
        return {"punkte": n, "erste_zeit": erste, "letzte_zeit": letzte, "herkunft": herkunft,
                "schluss": schluss, "fassung1": fassung1}

    uhr = sid_uhr.hex()
    verschoben = lambda st, d: dict(st, ts_von=st["ts_von"] + d)
    folge("ganz", [A, B, C], gut(8, T0, T0 + 35, [uhr]))
    folge("ungeordnet-und-doppelt", [C, A, B, A], gut(8, T0, T0 + 35, [uhr]))
    folge("laeuft-noch", [A, B], gut(6, T0, T0 + 25, [uhr], schluss=False), abgeschlossen=False)
    folge("stueck-entfernt", [A, C], "luecke")
    folge("anfang-entfernt", [B, C], "luecke")
    folge("schluss-entfernt", [A, B], "ohne-schluss")
    folge("zeit-verschoben", [verschoben(s, 3600) for s in (A, B, C)], "zeit")
    folge("ein-stueck-verschoben", [A, verschoben(B, 60), C], "zeit")
    folge("umdatiert-belegt", [verschoben(s, 86400) for s in (A, B, C)],
          gut(8, T0 + 86400, T0 + 86435, [uhr]), versatz=86400)
    folge("luecke-mit-loeschvermerk", [A, C], gut(5, T0, T0 + 35, [uhr]), sperren=[(T0 + 15, T0 + 25)])
    folge("ende-mit-loeschvermerk", [A, B], gut(6, T0, T0 + 25, [uhr], schluss=False),
          sperren=[(T0 + 30, T0 + 600)])
    folge("vertauscht", [A, stueck("f-b-fremd", 3, 3, ref=REF_B), C], "pruefwert")
    folge("fremdes-geraet", [A, stueck("f-b-geraet", 3, 3, wer="fremd"), C], "absender")
    folge("ohne-geraeteschluessel",
          [A, dict(B, huelle_b64=b64(_gefaelscht("f-b-falsch", kpub, "spur", REF_A, "3:3",
                                                 spur_innen2(T0 + 15, innen_binaer(_punkte(3, 3)))))), C],
          "pruefwert")
    folge("geraet-ausgetragen", [A, B, C], "absender-gesperrt", buch="uhr-ausgetragen")
    folge("geraet-gibt-sich-als-konto", [A, stueck("f-b-anmassung", 3, 3, herkunft=(sid_uhr,)), C], "innen")
    alt = [stueck("f1-a", 0, 3, fassung=1), stueck("f1-b", 3, 3, fassung=1), stueck("f1-c", 6, 2, fassung=1)]
    folge("fassung-1-geduldet", alt, gut(8, T0, T0 + 35, ["fassung-1"], schluss=False, fassung1=True))
    folge("fassung-1-wo-2-verlangt", alt, "ohne-herkunft", verlangt2=True)
    folge("gepackt-vom-konto", [P], gut(8, T0, T0 + 35, [KONTO]), stand=(8, 2))
    folge("zurueckgelegt-auf-geraetestuecke", [A, B, C], "zurueckgelegt", stand=(8, 2))
    folge("zurueckgelegt-kuerzer", [A, B], "zurueckgelegt", abgeschlossen=False, stand=(8, 1))

    # --- Einsatzhüllen gegen Klartextbeginn und Standbuch ---
    REF_E = "m-20261111-0004"
    e3 = aus["faelle"][5]["huelle_b64"]
    e2 = b64(_zu("e-z2", "handy", konto, "einsatz", REF_E, "", einsatz_innen2(2, T0, False, PHASEN2[:1], [])))
    e5 = b64(_zu("e-z5", "handy", konto, "einsatz", REF_E, "", einsatz_innen2(5, T0, True, PHASEN2, REA2)))
    ef = b64(_zu("e-fremd", "fremd", konto, "einsatz", REF_E, "", einsatz_innen2(9, T0, True, PHASEN2, REA2)))
    eu = b64(_zu("e-uhr", "uhr", konto, "einsatz", REF_E, "", einsatz_innen2(3, T0 + 60, True, PHASEN2, REA2)))
    e1 = b64(_zu1("e-fassung1", kpub, "einsatz", REF_E, "",
                  json.dumps({"fassung": 1, "phasen": PHASEN2, "rea": REA2}, separators=(",", ":")).encode()))

    def einsatz(name, huelle_b64, erwartet, *, started_ts=T0, versatz=0, stand=None, ref=REF_E,
                buch="standard"):
        f = {"name": name, "client_ref": ref, "started_ts": started_ts, "versatz": versatz,
             "stand": stand, "buch": buch, "huelle_b64": huelle_b64}
        f.update({"grund": erwartet} if isinstance(erwartet, str) else {"erwartet": erwartet})
        aus["einsaetze"].append(f)

    handy = aus["geraete"]["handy"]["sid_hex"]
    einsatz("ganz", e3, {"z": 3, "absender": handy})
    einsatz("stand-gleich", e3, {"z": 3, "absender": handy}, stand={"o": 3})
    einsatz("zurueckgelegt", e2, "zurueckgelegt", stand={"o": 3})
    einsatz("beginn-verschoben", e3, "zeit", started_ts=T0 + 86400)
    einsatz("umdatiert-belegt", e3, {"z": 3, "absender": handy}, started_ts=T0 + 86400, versatz=86400)
    einsatz("vertauscht", e3, "pruefwert", ref="m-20261111-0005")
    einsatz("geraet-nach-konto", e5, "zurueckgelegt", stand={"o": 4, "k": 1})
    einsatz("fassung-1-nach-abnahme", e1, "zurueckgelegt", stand={"o": 3})
    einsatz("fassung-1-geduldet", e1, {"z": None, "absender": None})
    einsatz("fremdes-geraet", ef, "absender")
    einsatz("geraet-ausgetragen", eu, "absender-gesperrt", started_ts=T0 + 60, buch="uhr-ausgetragen")

    # --- Kopplung: Zusage und Vergleichswert ---
    n_g = hashlib.sha256(b"nadoku-kopplung-vektor-n-g").digest()[:NONCE_LAENGE]
    n_b = hashlib.sha256(b"nadoku-kopplung-vektor-n-b").digest()[:NONCE_LAENGE]
    spk = sec1(_g("uhr").public_key())
    fremd = sec1(_g("fremd").public_key())
    aus["kopplung"] = {
        "konto_hex": kpub.hex(), "spk_hex": spk.hex(), "n_g_hex": n_g.hex(), "n_b_hex": n_b.hex(),
        "zusage_hex": kopplung_zusage(n_g, spk).hex(), "wert": kopplung_wert(kpub, spk, n_g, n_b),
        "gegenlagen": [
            {"name": "zusage-passt-nicht", "grund": "zusage", "konto_hex": kpub.hex(), "spk_hex": spk.hex(),
             "n_g_hex": n_b.hex(), "n_b_hex": n_b.hex(), "zusage_hex": kopplung_zusage(n_g, spk).hex()},
            {"name": "geraeteschluessel-getauscht", "grund": "zusage", "konto_hex": kpub.hex(),
             "spk_hex": fremd.hex(), "n_g_hex": n_g.hex(), "n_b_hex": n_b.hex(),
             "zusage_hex": kopplung_zusage(n_g, spk).hex()},
            {"name": "kontoschluessel-getauscht", "anderer_wert": kopplung_wert(fremd, spk, n_g, n_b),
             "konto_hex": fremd.hex(), "spk_hex": spk.hex(), "n_g_hex": n_g.hex(), "n_b_hex": n_b.hex()},
            {"name": "geraet-mit-eigener-zusage-getauscht",
             "anderer_wert": kopplung_wert(kpub, fremd, n_g, n_b),
             "konto_hex": kpub.hex(), "spk_hex": fremd.hex(), "n_g_hex": n_g.hex(), "n_b_hex": n_b.hex()},
        ],
    }
    return aus


def pruefen2(v: dict) -> list[str]:
    """Fassung 2: jeden Fall neu rechnen und öffnen, jede Störung und jede
    Folge mit ihrem Grund scheitern sehen."""
    fehler = []
    if vektoren2_bauen() != v:
        fehler.append("vektoren-2.json weicht von der Umsetzung ab — neu erzeugen?")
    le = _leser(v)
    for f in v["faelle"]:
        try:
            k, wer = le.huelle(base64.b64decode(f["huelle_b64"]), f["art"], f["client_ref"], f["platz"])
            soll = KONTO if f["absender"] == KONTO else v["geraete"][f["absender"]]["sid_hex"]
            if k.hex() != f["klartext_hex"] or wer != soll:
                fehler.append(f"2/{f['name']}: Klartext oder Absender weicht ab")
        except HuelleFehler as e:
            fehler.append(f"2/{f['name']}: geht nicht auf ({e})")
    for g in v["gepackt"]:
        try:
            k, wer = le.huelle(base64.b64decode(g["huelle_b64"]), g["art"], g["client_ref"], g["platz"])
            kopf = spur_innen2_lesen(k)
            pkt = gepackt_lesen(kopf["nutzlast"], len(g["punkte"]), kopf["ts_von"])
            if wer != KONTO or [list(p) for p in pkt] != [p[1:] for p in g["punkte"]]:
                fehler.append(f"2/{g['name']}: Punkte oder Absender weichen ab")
        except (HuelleFehler, ValueError) as e:
            fehler.append(f"2/{g['name']}: geht nicht auf ({e})")
    for s in v["stoerungen"]:
        try:
            _leser(v, verlangt2=s["verlangt2"]).huelle(base64.b64decode(s["huelle_b64"]), s["art"],
                                                      s["client_ref"], s["platz"])
            fehler.append(f"2/Störung {s['name']}: ging auf")
        except HuelleFehler as e:
            if str(e) != s["grund"]:
                fehler.append(f"2/Störung {s['name']}: Grund {e}, erwartet {s['grund']}")
    for f in v["folgen"]:
        stuecke = [dict(s, huelle=base64.b64decode(s["huelle_b64"])) for s in f["stuecke"]]
        try:
            pkt, bef = spur_lesen(_leser(v, f["buch"], f["verlangt2"]), f["art"], f["client_ref"], stuecke,
                                  versatz=f["versatz"], sperren=tuple(map(tuple, f["sperren"])),
                                  abgeschlossen=f["abgeschlossen"],
                                  stand=tuple(f["stand"]) if f["stand"] else None)
            ist = {"punkte": len(pkt), "erste_zeit": pkt[0][4], "letzte_zeit": pkt[-1][4],
                   "herkunft": bef["herkunft"], "schluss": bef["schluss"], "fassung1": bef["fassung1"]}
            if "grund" in f:
                fehler.append(f"2/Folge {f['name']}: ging auf, erwartet {f['grund']}")
            elif ist != f["erwartet"]:
                fehler.append(f"2/Folge {f['name']}: {ist}, erwartet {f['erwartet']}")
        except HuelleFehler as e:
            if str(e) != f.get("grund"):
                fehler.append(f"2/Folge {f['name']}: Grund {e}, erwartet {f.get('grund', 'geht auf')}")
    for f in v["einsaetze"]:
        try:
            d, wer = einsatz_lesen(_leser(v, f["buch"]), f["client_ref"], base64.b64decode(f["huelle_b64"]),
                                   f["started_ts"], versatz=f["versatz"], stand=f["stand"])
            ist = {"z": d.get("z"), "absender": wer}
            if "grund" in f:
                fehler.append(f"2/Einsatz {f['name']}: ging auf, erwartet {f['grund']}")
            elif ist != f["erwartet"]:
                fehler.append(f"2/Einsatz {f['name']}: {ist}, erwartet {f['erwartet']}")
        except HuelleFehler as e:
            if str(e) != f.get("grund"):
                fehler.append(f"2/Einsatz {f['name']}: Grund {e}, erwartet {f.get('grund', 'geht auf')}")
    k = v["kopplung"]
    hx = bytes.fromhex
    if (kopplung_zusage(hx(k["n_g_hex"]), hx(k["spk_hex"])).hex() != k["zusage_hex"]
            or kopplung_wert(hx(k["konto_hex"]), hx(k["spk_hex"]), hx(k["n_g_hex"]), hx(k["n_b_hex"]),
                             hx(k["zusage_hex"])) != k["wert"]):
        fehler.append("2/Kopplung: Zusage oder Vergleichswert weicht ab")
    for g in k["gegenlagen"]:
        try:
            w = kopplung_wert(hx(g["konto_hex"]), hx(g["spk_hex"]), hx(g["n_g_hex"]), hx(g["n_b_hex"]),
                              hx(g["zusage_hex"]) if "zusage_hex" in g else None)
            if "grund" in g:
                fehler.append(f"2/Kopplung {g['name']}: ging durch, erwartet {g['grund']}")
            elif w != g["anderer_wert"] or w == k["wert"]:
                fehler.append(f"2/Kopplung {g['name']}: Wert {w} unterscheidet sich nicht wie erwartet")
        except HuelleFehler as e:
            if str(e) != g.get("grund"):
                fehler.append(f"2/Kopplung {g['name']}: Grund {e}")
    return fehler


def zahlen2(v: dict) -> str:
    rot = (len(v["stoerungen"]) + sum("grund" in f for f in v["folgen"])
           + sum("grund" in f for f in v["einsaetze"]) + len(v["kopplung"]["gegenlagen"]))
    gut = (len(v["faelle"]) + len(v["gepackt"]) + sum("grund" not in f for f in v["folgen"])
           + sum("grund" not in f for f in v["einsaetze"]) + 1)
    return f"Fassung 2: {gut} Fälle geöffnet, {rot} Gegenlagen abgewiesen"


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


def app_vektor(v: dict) -> str:
    """Der Fall „kurz" als Monkey-C-Quelltext für die App. Die App
    rechnet ihn mit festem flüchtigem Schlüssel und festem IV nach: stimmt Z
    erst umgedreht, ist die Ordnung little-endian; stimmt danach
    das TAG, trägt die ganze Kette — ECDH, HKDF, AES, HMAC, Bindung."""
    f = next(f for f in v["faelle"] if f["name"] == "kurz")
    return "\n".join([
        "// ERZEUGT von tools/proben/huelle/referenz.py erzeugen — nicht von Hand ändern.",
        "// Anlass: entfällt — Erzeuger (E-BR-05)",
        "// Der Selbsttest der Gerätehülle (S11-08, E-S11-30): Fall „kurz“ aus",
        "// tools/proben/huelle/vektoren.json.",
        "import Toybox.Lang;", "", "module HuelleVektor {",
        f"    const EMPF_PUB = {mc_bytes(v['empfaenger']['oeffentlich_hex'])};",
        f"    const KLAR = {mc_bytes(f['klartext_hex'])};",
        f"    const BINDUNG = {mc_bytes(f['bindung_hex'])};",
        f"    const D = {mc_bytes(f['fluechtig_privat_hex'])};",
        f"    const IV = {mc_bytes(f['iv_hex'])};",
        f"    const Z = {mc_bytes(f['z_hex'])};",
        f"    const TAG = {mc_bytes(f['tag_hex'])};",
        "}", ""])


UHR_FAELLE2 = ("kurz", "spur-3")


def uhr_vektoren2(v2: dict) -> str:
    """Fassung 2 als Monkey-C-Quelltext für die Probe-App (KG-03a): die Fälle
    „kurz" und „spur-3" — beide von der Uhr verschlossen — mit der ganzen
    Hülle, dazu Kennung und Z_s der Uhr und die Kopplung. Alles aus
    `vektoren-2.json`, nichts neu gerechnet."""
    faelle = [f for f in v2["faelle"] if f["name"] in UHR_FAELLE2]
    if [f["name"] for f in faelle] != list(UHR_FAELLE2) or any(f["absender"] != "uhr" for f in faelle):
        raise ValueError("Vektoren2.mc: die Fälle der Uhr fehlen oder haben einen anderen Absender")
    if len({f["z_s_hex"] for f in faelle}) != 1:
        raise ValueError("Vektoren2.mc: Z_s ist je Paar fest und muss in beiden Fällen gleich sein")
    uhr, k = v2["geraete"]["uhr"], v2["kopplung"]
    if k["konto_hex"] != v2["konto"]["oeffentlich_hex"] or k["spk_hex"] != uhr["oeffentlich_hex"]:
        raise ValueError("Vektoren2.mc: die Kopplung nennt andere Schlüssel als Konto und Uhr")
    spur = faelle[1]
    zeilen = ["// ERZEUGT von tools/proben/huelle/referenz.py erzeugen — nicht von Hand ändern.",
              "// Anlass: entfällt — Erzeuger (E-BR-05)",
              "// Fassung 2 der Gerätehülle (KG-03a): Fälle aus tools/proben/huelle/vektoren-2.json.",
              "import Toybox.Lang;", "", "module Vektoren2 {",
              f"    const KONTO = {mc_bytes(v2['konto']['oeffentlich_hex'])};",
              f"    const GERAET_D = {mc_bytes(uhr['privat_hex'])};",
              f"    const SPK = {mc_bytes(uhr['oeffentlich_hex'])};",
              f"    const SID = {mc_bytes(uhr['sid_hex'])};",
              f"    const Z_S = {mc_bytes(faelle[0]['z_s_hex'])};",
              # Womit die Probe die Innenform von „spur-3" nachbaut: die Nummer
              # des ersten Punkts (aus dem Platz) und ts_von (aus dem Kopf).
              f"    const SPUR_VON = {int(spur['platz'].split(':')[0])};",
              f"    const SPUR_TS = {int(spur['klartext_hex'][:16], 16)};",
              f"    const N_G = {mc_bytes(k['n_g_hex'])};",
              f"    const N_B = {mc_bytes(k['n_b_hex'])};",
              f"    const ZUSAGE = {mc_bytes(k['zusage_hex'])};",
              f"    const WERT = \"{k['wert']}\";",
              "    const FAELLE = ["]
    for f in faelle:
        zeilen += ["        {",
                   f"            :name => \"{f['name']}\",",
                   f"            :klar => {mc_bytes(f['klartext_hex'])},",
                   f"            :bindung => {mc_bytes(f['bindung_hex'])},",
                   f"            :fluechtig => {mc_bytes(f['fluechtig_privat_hex'])},",
                   f"            :iv => {mc_bytes(f['iv_hex'])},",
                   f"            :huelle => {mc_bytes(base64.b64decode(f['huelle_b64']).hex())}",
                   "        },"]
    zeilen += ["    ];", "}", ""]
    return "\n".join(zeilen)


def app_vektor2(v: dict, v2: dict) -> str:
    """Der Fall „kurz" der Fassung 2 für den Selbsttest `Huelle2.bereit()`.
    Kontoschlüssel und Klartext sind dieselben wie in Fassung 1 und stehen
    schon in `HuelleVektor.mc` — die App trägt sie nicht zweimal."""
    f = next(f for f in v2["faelle"] if f["name"] == "kurz")
    f1 = next(f for f in v["faelle"] if f["name"] == "kurz")
    if (v2["konto"]["oeffentlich_hex"] != v["empfaenger"]["oeffentlich_hex"]
            or f["klartext_hex"] != f1["klartext_hex"] or f["absender"] != "uhr"):
        raise ValueError("HuelleVektor2.mc: Konto, Klartext oder Absender passen nicht zu HuelleVektor.mc")
    return "\n".join([
        "// ERZEUGT von tools/proben/huelle/referenz.py erzeugen — nicht von Hand ändern.",
        "// Anlass: entfällt — Erzeuger (E-BR-05)",
        "// Der Selbsttest der Gerätehülle, Fassung 2 (KG-03a): Fall „kurz“ aus",
        "// tools/proben/huelle/vektoren-2.json. Kontoschlüssel und Klartext: HuelleVektor.mc.",
        "import Toybox.Lang;", "", "module HuelleVektor2 {",
        f"    const D_GERAET = {mc_bytes(v2['geraete']['uhr']['privat_hex'])};",
        f"    const BINDUNG = {mc_bytes(f['bindung_hex'])};",
        f"    const D = {mc_bytes(f['fluechtig_privat_hex'])};",
        f"    const IV = {mc_bytes(f['iv_hex'])};",
        f"    const SID = {mc_bytes(f['sid_hex'])};",
        f"    const TAG = {mc_bytes(f['tag_hex'])};",
        "}", ""])


def uhr_dateien2(v: dict, v2: dict) -> list[tuple[pathlib.Path, str]]:
    return [(UHR_VEKTOREN2, uhr_vektoren2(v2)), (APP_VEKTOR2, app_vektor2(v, v2))]


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
    # Fassung 2: dieselben vier Fehlerarten, dazu je Vektorart eine.
    v2 = vektoren2_bauen()
    faelle.append(("Gegenprobe 2: frische Vektoren", not pruefen2(v2)))
    w = json.loads(json.dumps(v2)); w["faelle"][0]["klartext_hex"] = "00"
    faelle.append(("Fehler 2: Klartext verfälscht", bool(pruefen2(w))))
    w = json.loads(json.dumps(v2)); w["stoerungen"][0]["grund"] = "fassung"
    faelle.append(("Fehler 2: falscher Grund erwartet", bool(pruefen2(w))))
    w = json.loads(json.dumps(v2)); w["stoerungen"][0]["huelle_b64"] = w["faelle"][2]["huelle_b64"]
    faelle.append(("Fehler 2: Störung geht auf", bool(pruefen2(w))))
    w = json.loads(json.dumps(v2)); w["folgen"][3]["stuecke"] = w["folgen"][0]["stuecke"]
    faelle.append(("Fehler 2: Folge mit entferntem Stück geht auf", bool(pruefen2(w))))
    w = json.loads(json.dumps(v2)); w["folgen"][0]["erwartet"]["punkte"] = 7
    faelle.append(("Fehler 2: Folge mit falscher Punktzahl", bool(pruefen2(w))))
    w = json.loads(json.dumps(v2)); w["einsaetze"][2]["stand"] = None
    faelle.append(("Fehler 2: zurückgelegte Einsatzhülle geht auf", bool(pruefen2(w))))
    w = json.loads(json.dumps(v2)); w["kopplung"]["wert"] = "000000"
    faelle.append(("Fehler 2: falscher Vergleichswert", bool(pruefen2(w))))
    w = json.loads(json.dumps(v2)); w["kopplung"]["gegenlagen"][0]["n_g_hex"] = w["kopplung"]["n_g_hex"]
    faelle.append(("Fehler 2: Zusage passt doch", bool(pruefen2(w))))
    konto = schluessel_aus_name(EMPFAENGER)
    geraet = ec.generate_private_key(ec.SECP256R1())
    buch = {sid(sec1(geraet.public_key())).hex(): {"spk": sec1(geraet.public_key()), "bis": None}}
    zufall2 = verschliessen2(b"y" * 100, pub, geraet, "spur", "r", "0:1")
    faelle.append(("Gegenprobe 2: zufällige Hülle geht auf, Absender erkannt",
                   Leser(konto, buch).huelle(zufall2, "spur", "r", "0:1")
                   == (b"y" * 100, sid(sec1(geraet.public_key())).hex())))
    try:
        Leser(konto, {}).huelle(zufall2, "spur", "r", "0:1")
        faelle.append(("Fehler 2: Gerät fehlt im Gerätebuch", False))
    except HuelleFehler as e:
        faelle.append(("Fehler 2: Gerät fehlt im Gerätebuch", str(e) == "absender"))
    try:
        oeffnen(zufall2, konto, "spur", "r", "0:1")
        faelle.append(("Fehler 2: Fassung 2 mit dem Öffner der Fassung 1", False))
    except HuelleFehler as e:
        faelle.append(("Fehler 2: Fassung 2 mit dem Öffner der Fassung 1", str(e) in ("fassung", "laenge")))
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
        APP_VEKTOR.write_text(app_vektor(v), "utf-8")
        print(f"{len(v['faelle'])} Fälle, {len(v['stoerungen'])} Störungen → {VEKTOREN.name}, "
              f"{UHR_VEKTOREN.relative_to(WURZEL)}, {APP_VEKTOR.relative_to(WURZEL)}")
        # Fassung 2: Die Quelltexte der Uhr für Fassung 1 (Vektoren.mc,
        # HuelleVektor.mc) bleiben, wie sie sind; Fassung 2 bekommt seit
        # KG-03a eigene Dateien daneben.
        v2 = vektoren2_bauen()
        VEKTOREN2.write_text(json.dumps(v2, ensure_ascii=False, indent=2) + "\n", "utf-8")
        for pfad, text in uhr_dateien2(v, v2):
            pfad.write_text(text, "utf-8")
        print(f"{zahlen2(v2)} → {VEKTOREN2.name}, "
              f"{UHR_VEKTOREN2.relative_to(WURZEL)}, {APP_VEKTOR2.relative_to(WURZEL)}")
        return 0
    if arg == "pruefen":
        if not VEKTOREN.exists():
            print("vektoren.json fehlt"); return 2
        v = json.loads(VEKTOREN.read_text("utf-8"))
        fehler = pruefen(v)
        if not fehler and UHR_VEKTOREN.read_text("utf-8") != uhr_vektoren(v):
            fehler.append("Vektoren.mc weicht ab — neu erzeugen")
        if not fehler and (not APP_VEKTOR.exists()
                           or APP_VEKTOR.read_text("utf-8") != app_vektor(v)):
            fehler.append("watch/source/HuelleVektor.mc weicht ab — neu erzeugen")
        for f in fehler:
            print("  FEHL  " + f)
        print(f"{len(v['faelle'])} Fälle geöffnet, {len(v['stoerungen'])} Störungen abgewiesen, "
              f"{len(fehler)} Abweichungen")
        if not VEKTOREN2.exists():
            print("vektoren-2.json fehlt"); return 2
        v2 = json.loads(VEKTOREN2.read_text("utf-8"))
        fehler2 = pruefen2(v2)
        if not fehler and not fehler2:
            for pfad, text in uhr_dateien2(v, v2):
                if not pfad.exists() or pfad.read_text("utf-8") != text:
                    fehler2.append(f"{pfad.relative_to(WURZEL)} weicht ab — neu erzeugen")
        for f in fehler2:
            print("  FEHL  " + f)
        print(f"{zahlen2(v2)}, {len(fehler2)} Abweichungen")
        return 1 if fehler or fehler2 else 0
    print(__doc__)
    return 2


if __name__ == "__main__":
    sys.exit(main())
