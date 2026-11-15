#!/usr/bin/env python3
"""Demo-Fixture neu verschließen: Rohbestand oder Format 3 → Format 4.

Anlass: Nr. 563 — mit umgelegter Weiche (Web 25.0.0) zeigte der Demo-Bestand
sonst Gerätedaten ohne Herkunftsnachweis (Konzept KA, F-KA-104, E-KA-44).

WAS HIER ENTSTEHT. Format 3 trug Orte, Reanimation und Spur im Klartext, und
der Reset verschloss sie auf der Zielanlage — mit dem öffentlichen
Kontoschlüssel, also in Fassung 1, die jeder bauen kann, der diesen
Schlüssel kennt. Format 4 trägt den Bestand so, wie er nach dem Umlegen der
Weiche in einem Konto liegt, und der Reset reicht ihn unverändert durch:

  Gerätedaten    Gerätehülle Fassung 2, verschlossen von den zwei
                 DEMO-GERÄTEN mit festem Schlüssel (`geraeteschluessel()`)
  Kontodaten     Gerätehülle Fassung 2 vom Absender „Konto" — wo der
                 Browser verschließt: Formular, Schnitt, Löschen, Wartungslauf
  Patientenblock `edk2:`, Zweck `pat`, gebunden an die client_ref, Zähler 1
  Standbuch      16 Blätter, alle geschlossen (`zu`), und das Gerätebuch
                 mit beiden Demo-Geräten (Blatt 16 in `stand_blaetter`)
  Konto          Kontopaar mit `kp_privat` als `edk2:`, Passworthülle
                 `edk2:` (Bezug `pw`, ohne Anteil), Ableitung nach Rundenzahl

DIE SCHLÜSSEL SIND FEST UND STEHEN HIER. Das Demo-Konto ist das eine Konto,
dessen Passwort im Handbuch steht (`DEMO_PASSWORT`); wer es hat, öffnet den
Inhaltsschlüssel und damit alles. Geräteschlüssel, Kontopaar und
Inhaltsschlüssel kommen deshalb aus einem Namen — wie `schluessel_aus_name()`
der Referenz —, und zweimal ausgeführt entsteht dieselbe Datei, Byte für
Byte (auch die flüchtigen Schlüssel und die Nonces kommen aus dem Inhalt).
Der Reset tauscht den Inhaltsschlüssel auf der Zielanlage gegen einen
frischen (`demo_lib.php`, „DER INHALTSSCHLÜSSEL WECHSELT"); der feste hier
ist nur der Träger.

WER WAS VERSCHLIESST — die Regel, an der die Datei zu messen ist:

  Spur    Gerät    der Eintrag kommt von einem Gerät, liegt auf Stufe 1 und
                   trägt keinen Löschvermerk: Stücke zu höchstens 500
                   Punkten, das letzte mit SCHLUSS, wenn `final` gesetzt ist
          Konto    sonst — ein gepacktes Stück (Stufe 2, 3; Wartungslauf),
                   die Spur eines geschnittenen Einsatzes, die übrigen
                   Stücke nach „Zeitraum löschen" (enden an der Lücke)
  Einsatz Gerät    Herkunft Gerät und nie bearbeitet (`edited` 0): `z` ist
                   die Sekunde des Abschlusses, `schluss` wie `final`
          Konto    bearbeitet, von Hand angelegt oder importiert — das
                   Formular verschließt bei jedem Speichern; nach einem
                   Gerät mit `z` + 1 und `schluss` (E-KA-76), sonst `z` 1
          keine    ein geschnittener, nie bearbeiteter Einsatz
                   (`api/schneiden.php` kann nicht verschließen)

Das Standbuch trägt je Datensatz, was der Browser nach dem Lesen eintrüge
(`stand_nach_abnahme()` der Referenz, `p` = Zähler des Blocks, `x` für
Schnitt und Löschen): Wer den Demo-Bestand nur ansieht, schreibt kein Blatt.

DIE FORMATE stehen in `tools/proben/huelle/referenz.py` und
`referenz_inhalt.py` und nur dort; diese Datei ruft sie und baut nichts
nach. Am Ende liest sie alles, was sie geschrieben hat, mit den Regeln der
Referenz (`Leser`, `spur_lesen()`, `einsatz_lesen()`, `pat_lesen()`) und
hält es gegen die Eingabe — weicht etwas ab, entsteht keine Datei.

Aufruf:  python3 verschliessen.py EINGABE.json.gz [AUSGABE.json.gz]
                 [--runden N] [--selbstprobe]
         EINGABE   der Rohbestand aus `erzeugen.php` (verschlossen, wie er im
                   Konto liegt — beide Fassungen) oder eine Fixture im Format 3
         AUSGABE   Vorgabe `server/demo/fixture.json.gz`
         --runden  Rundenzahl des Kontos; Vorgabe `KDF_ITER_ZIEL` aus
                   `server/db.php`. Ab 1 200 000 Ableitung 2 (E-KA-84).
Rückgabe: 0 geschrieben · 1 Gegenlesung weicht ab · 2 nicht gelaufen
"""
from __future__ import annotations

import base64
import gzip
import hashlib
import json
import pathlib
import re
import struct
import sys
from datetime import datetime, timezone

HIER = pathlib.Path(__file__).resolve().parent
WURZEL = HIER.parents[2]
sys.path.insert(0, str(WURZEL / "tools" / "proben" / "huelle"))
import referenz as gh            # noqa: E402  (Gerätehülle — eine Stelle)
import referenz_inhalt as inh    # noqa: E402  (edk2, Standbuch, Ableitung — eine Stelle)

from cryptography.hazmat.primitives import serialization  # noqa: E402

ZIEL = WURZEL / "server" / "demo" / "fixture.json.gz"
DEMO_PASSWORT = "nadokudemo0815"          # wie `DEMO_PASSWORT` in server/demo_lib.php
ABLEITUNG2_AB = 1_200_000                 # wie `ABLEITUNG2_AB` (crypto.js), `KDF_ABLEITUNG2_AB`
STUECK_GERAET = 500                       # so stückelt die Uhr (`Uploader.mc`, Chunking ≤ 500)
STUECK_KONTO = 2000                       # `EdOrtsdaten.stueckeBauen()`
GERAETEHERKUNFT = ("watch", "android", "wear")
BCRYPT_KOSTEN = 10


class Abbruch(RuntimeError):
    pass


# ------------------------------------------------------------ feste Schlüssel

def name_bytes(name: str, n: int) -> bytes:
    return hashlib.sha256(name.encode("utf-8")).digest()[:n]


def inhaltsschluessel() -> bytes:
    """Der Inhaltsschlüssel der Fixture (32 Byte) — nur der Träger: Der Reset
    setzt auf der Zielanlage einen frischen an seine Stelle."""
    return name_bytes("nadoku-demo-inhaltsschluessel-1", 32)


def kontopaar():
    return gh.schluessel_aus_name("nadoku-demo-kontopaar-1")


def geraeteschluessel(nummer: str):
    """Der Geräteschlüssel eines Demo-Geräts — `nummer` ist die Gerätenummer
    der Quelldaten (`geraete.json`: 11 die Uhr, 12 das Handy), wie sie in
    jeder client_ref steht."""
    return gh.schluessel_aus_name(f"nadoku-demo-geraet-{nummer}")


def fest(was: str, *teile) -> bytes:
    """Ein fester Wert aus dem, was er schützt: flüchtiger Schlüssel, IV und
    Nonce hängen am Inhalt — derselbe Inhalt, dieselben Bytes; ein anderer
    Inhalt, ein anderer Wert."""
    h = hashlib.sha256(("nadoku-demo-fest|" + was).encode())
    for t in teile:
        b = t if isinstance(t, bytes) else str(t).encode("utf-8")
        h.update(struct.pack(">I", len(b)) + b)
    return h.digest()


def huelle2(klar: bytes, konto_pub: bytes, absender, art: str, ref: str, platz: str = "") -> bytes:
    kern = fest("huelle", art, ref, platz, gh.sec1(absender.public_key()), klar)
    return gh.verschliessen2(klar, konto_pub, absender, art, ref, platz,
                             fluechtig=gh.schluessel_aus_name("nadoku-demo-fluechtig-" + kern.hex()),
                             iv=fest("iv", kern)[:16])


def edk2(schluessel: bytes, zweck: str, bezug: str, nutzlast: bytes, z: int = 0) -> str:
    return inh.verschliessen(schluessel, zweck, bezug, nutzlast, z,
                             nonce=fest("nonce", schluessel, zweck, bezug, z, nutzlast)[:12])


# ------------------------------------------------------------------- Konto

def ableiten(passwort: str, salz: bytes, runden: int) -> tuple[bytes, bytes]:
    """(Hälfte, Token) — Ableitung 2 ab `ABLEITUNG2_AB`, sonst 1."""
    if runden >= ABLEITUNG2_AB:
        _, haelfte, token = inh.ableitung2(passwort, salz, runden)
        return haelfte, token
    return inh.ableitung1(passwort, salz, runden)


def bcrypt_fest(token_hex: str, salz16: bytes) -> str:
    """`password_hash()` von PHP mit festem Salz — `$2y$`, Kosten wie
    `PASSWORD_DEFAULT` unter PHP 8.3. Das Demo-Konto trägt keinen Pfeffer
    (`anmeldehash_bilden(…, istDemo: true)`)."""
    import bcrypt  # noqa: PLC0415 — nur hier
    alphabet = "./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789"
    bits = int.from_bytes(salz16, "big") << 4          # 128 Bit → 22 Zeichen zu 6 Bit
    salz = "".join(alphabet[(bits >> (6 * (21 - i))) & 63] for i in range(22))
    roh = bcrypt.hashpw(token_hex.encode(), f"$2b${BCRYPT_KOSTEN:02d}${salz}".encode()).decode()
    return "$2y$" + roh[4:]


def konto_bauen(alt: dict, ck: bytes, kp, runden: int) -> dict:
    salz = bytes.fromhex(alt["kdf_salt"])
    haelfte, token = ableiten(DEMO_PASSWORT, salz, runden)
    ck_hex = ck.hex()
    pkcs8 = kp.private_bytes(serialization.Encoding.DER, serialization.PrivateFormat.PKCS8,
                             serialization.NoEncryption())
    spki = kp.public_key().public_bytes(serialization.Encoding.DER,
                                        serialization.PublicFormat.SubjectPublicKeyInfo)
    # DER RÜCKWEG: eine Hülle unter einem Wiederherstellungsschlüssel, den es
    # nur hier gibt. Das Demo-Konto kann sein Passwort nicht ändern; die
    # Hülle steht, damit das Konto aussieht wie jedes andere, und sie hängt
    # nie am Anteil (`huelle_rc_pruefen()`).
    rc_code = "".join("ABCDEFGHJKMNPQRSTVWXYZ23456789"[b % 30]
                      for b in name_bytes("nadoku-demo-wiederherstellung-1", 20))
    rc = hashlib.sha256(("edk-rc:" + rc_code).encode()).digest()
    return {
        "email": alt["email"], "name": alt.get("name"),
        "password_hash": bcrypt_fest(token.hex(), fest("bcrypt", salz, runden)[:16]),
        "kdf_salt": alt["kdf_salt"], "kdf_iter": runden,
        "pat_wrap_pw": inh.verschliessen(haelfte, "wrap", "pw", ck_hex.encode(),
                                         nonce=fest("nonce", "wrap-pw", salz, runden)[:12]),
        "pat_wrap_rc": inh.verschliessen(rc, "wrap", "rc", ck_hex.encode(),
                                         nonce=fest("nonce", "wrap-rc")[:12]),
        "rc_hex": rc.hex(),
        "pat_key_check": hashlib.sha256(("edk-ckchk:" + ck_hex).encode()).hexdigest()[:32],
        "account_key": alt.get("account_key"),
        "kp_oeffentlich": base64.b64encode(spki).decode(),
        "kp_privat": edk2(ck, "kp", "", base64.b64encode(pkcs8)),
    }


def ck_alt(konto: dict) -> bytes:
    """Der Inhaltsschlüssel der Eingabe — aus dem Demo-Passwort, wie
    `demo_inhaltsschluessel()`."""
    haelfte, _ = ableiten(DEMO_PASSWORT, bytes.fromhex(konto["kdf_salt"]), int(konto["kdf_iter"]))
    wrap = konto["pat_wrap_pw"]
    try:
        hexwert = (inh.huelle_oeffnen(haelfte, wrap, "pw") if wrap.startswith(inh.KENNUNG)
                   else inh.oeffnen1(haelfte, wrap).decode())
    except inh.InhaltFehler as f:
        raise Abbruch(f"Das Demo-Passwort öffnet die Schlüsselhülle der Eingabe nicht ({f}).") from f
    if not re.fullmatch(r"[0-9a-f]{64}", hexwert):
        raise Abbruch("Die Schlüsselhülle der Eingabe trägt keinen Inhaltsschlüssel.")
    return bytes.fromhex(hexwert)


def pat_klar(ck: bytes, blob: str, ref: str) -> bytes:
    """Der Klartext eines Patientenblocks der Eingabe — beide Fassungen."""
    if blob.startswith(inh.KENNUNG):
        return inh.oeffnen(ck, blob, "pat", ref)[0]
    return inh.oeffnen1(ck, blob)


# ---------------------------------------------- Rohbestand öffnen (Eingabe)

def roh_oeffnen(roh: dict) -> dict:
    """Den Rohbestand aus `erzeugen.php` öffnen und in die Klartextform
    bringen, die `bauen()` nimmt (Fixture-Format 3): je Spur eine Punktliste
    mit `stufe` und `n_original`, Phasenorte an den Phasen, Reanimation als
    `resus`; `pat_blob` bleibt Chiffretext der Eingabe.

    BEIDE FASSUNGEN. Ein Konto von vor der Weiche trägt Fassung 1 und
    `edk1:`; danach liegt beides nebeneinander. Geöffnet wird mit dem, was
    ein Browser des Kontos hat — Demo-Passwort, Kontopaar, Gerätebuch und
    Standbuch des Kontos (Versatz) —, über `Leser` der Referenz. Die
    Vermerke des Standbuchs blenden hier NICHTS aus: Was im Segment liegt,
    geht in die Fixture, auch wenn ein Schnitt es sperrt (so lag es auch in
    Format 3)."""
    if int(roh.get("version") or 0) != 1:
        raise Abbruch(f"Der Rohbestand hat Fassung {roh.get('version')!r}; gelesen wird 1.")
    k = roh["konto"]
    ck = ck_alt(k)
    try:
        text = k["kp_privat"]
        pkcs8 = inh.oeffnen(ck, text, "kp", "")[0] if text.startswith(inh.KENNUNG) else inh.oeffnen1(ck, text)
        priv = serialization.load_der_private_key(base64.b64decode(pkcs8), password=None)
        spki = serialization.load_der_public_key(base64.b64decode(k["kp_oeffentlich"]))
        buch, blaetter = {}, {}
        for b in roh.get("standbuch") or []:
            if int(b["wer"]) == 16:
                buch, _ = inh.geraetebuch_oeffnen(ck, b["text"])
            else:
                blaetter[int(b["wer"])] = inh.blatt_oeffnen(ck, b["text"], int(b["wer"]))[0]
    except (inh.InhaltFehler, ValueError) as f:
        raise Abbruch(f"Kontopaar oder Standbuch des Kontos geht nicht auf: {f!r}") from f
    if gh.sec1(priv.public_key()) != gh.sec1(spki):
        raise Abbruch("Der private Teil des Kontopaars passt nicht zum öffentlichen.")
    leser = gh.Leser(priv, buch)
    versatz = lambda ref: int((blaetter.get(inh.blatt_nr(ref)) or {}).get("e", {})  # noqa: E731
                              .get(inh.kennung(ref), {}).get("d", 0))
    stuecke = {(s["art"], int(s["spur_ref"])): s["stuecke"] for s in roh["spuren"]}
    daten = json.loads(json.dumps(roh["daten"]))
    daten["version"] = 7

    def spur(e: dict, typ: str, art: str) -> None:
        if "spur_ref" not in e:
            return
        ref, d = str(e["client_ref"]), versatz(str(e["client_ref"]))
        punkte, stufen, n_original = [], set(), 0
        for st in sorted(stuecke.get((typ, int(e["spur_ref"])), []), key=lambda x: x["seq_from"]):
            stufe, n, seq = int(st["stufe"]), int(st["punkte"]), int(st["seq_from"])
            if n == 0:
                continue                              # das leere Schlussstück trägt keinen Punkt
            stufen.add(stufe)
            n_original += int(st["n_original"])
            platz = gh.platz_gepackt(seq, n, stufe, int(st["n_original"])) if stufe >= 2 else f"{seq}:{n}"
            try:
                klar, absender = leser.huelle(base64.b64decode(st["huelle"]), art, ref, platz)
                if absender is None:
                    ts_von, nutz = int(st["ts_von"]) - d, klar
                else:
                    kopf = gh.spur_innen2_lesen(klar)
                    if int(st["ts_von"]) - kopf["ts_von"] != d:
                        raise gh.HuelleFehler("zeit")
                    ts_von, nutz = kopf["ts_von"], kopf["nutzlast"]
                if stufe >= 2:
                    pkt = gh.gepackt_lesen(nutz, n, ts_von)
                else:
                    if len(nutz) != 14 * n:
                        raise gh.HuelleFehler("innen")
                    pkt = []
                    for i in range(n):
                        la, lo, h, t = struct.unpack(">iihI", nutz[14 * i:14 * i + 14])
                        pkt.append((la / 1e7, lo / 1e7, None if h == gh.HOEHE_UNBEKANNT else h, ts_von + t))
            except (gh.HuelleFehler, ValueError) as f:
                raise Abbruch(f"Stück {seq}:{n} (Stufe {stufe}) von {art} {ref} geht nicht auf: {f}") from f
            punkte += [[seq + i, round(la, 7), round(lo, 7), h, t + d] for i, (la, lo, h, t) in enumerate(pkt)]
        # EINE STUFE JE SPUR: Die Klartextform trägt `stufe` am Eintrag. Der
        # Wartungslauf fügt eine Spur immer ganz zusammen; die Mischung kommt
        # im Referenzbestand nicht vor — und wenn doch, wird es gesagt.
        if len(stufen) != 1:
            raise Abbruch(f"{art} {ref} hat Stücke der Stufen {sorted(stufen)} — erwartet ist genau eine.")
        if len({p[0] for p in punkte}) != len(punkte) or len(punkte) != int(e["n"]):
            raise Abbruch(f"{typ} {ref} hat {len(punkte)} Punkte, der Umriss nennt {e['n']} "
                          f"(oder eine Nummer steht doppelt).")
        del e["spur_ref"], e["n"]
        e["track"], e["stufe"], e["n_original"] = punkte, stufen.pop(), n_original

    for m in daten["missions"]:
        ref = str(m["client_ref"])
        spur(m, "mission", "spur")
        huelle = m.pop("ort_huelle", None)
        for p in m["phases"]:
            p["lat"], p["lon"] = None, None
        m["resus"] = []
        if huelle is None:
            continue
        start = sek(m["started_at"])
        try:
            innen, _ = gh.einsatz_lesen(leser, ref, base64.b64decode(huelle), start, versatz=versatz(ref))
        except gh.HuelleFehler as f:
            raise Abbruch(f"Die Einsatzhülle von {ref} geht nicht auf: {f}") from f
        if not isinstance(innen, dict) or innen.get("fassung") not in (1, 2):
            raise Abbruch(f"Die Einsatzhülle von {ref} hat keine bekannte Innenform.")
        zeit = lambda t: datetime.fromtimestamp(start + t, timezone.utc).strftime("%Y-%m-%d %H:%M:%S")  # noqa: E731
        # Phasenorte an die Phasen des Kerns, über Nummer UND Zeit: Die Folge
        # der Hülle ist die des Absenders, die des Kerns die nach Zeit.
        orte: dict[tuple, list] = {}
        for p in innen.get("phasen") or []:
            if p.get("lat") is None and p.get("lon") is None:
                continue
            orte.setdefault((int(p["phase"]), zeit(int(p["t"]))), []).append((p["lat"], p["lon"]))
        for p in m["phases"]:
            vorrat = orte.get((int(p["phase"]), p["occurred_at"]))
            if vorrat:
                p["lat"], p["lon"] = vorrat.pop(0)
        if any(orte.values()):
            raise Abbruch(f"{ref}: Phasenorte ohne passende Phase im Kern.")
        m["resus"] = [{"started_at": zeit(int(r["t"])),
                       "events": [{"type": str(x["art"]), "occurred_at": zeit(int(x["t"]))}
                                  for x in r.get("ereignisse") or []]}
                      for r in innen.get("rea") or []]
    for r in daten["rest_segments"]:
        spur(r, "rest", "ruhe")
    return {"format": "einsatzdoku-demo-fixture", "version": 3, "erzeugt_am": roh["erzeugt_am"],
            "web_version": roh.get("web_version"), "quelle": roh.get("quelle"),
            "konto": k, "geraete": [{f: g.get(f) for f in ("device_id", "api_key_hash", "label",
                                                            "geraet_art", "geraet_modell")}
                                    for g in roh["geraete"]],
            "daten": daten}


# ------------------------------------------------------------- Ortsdaten

def sek(text: str) -> int:
    return int(datetime.strptime(text, "%Y-%m-%d %H:%M:%S").replace(tzinfo=timezone.utc).timestamp())


def runde(x: float) -> int:
    """Rundung wie PHPs `round()` und `ortsdaten.js`: von der Null weg."""
    return int(abs(x) + 0.5) * (1 if x >= 0 else -1)


def geraet_von(eintrag: dict) -> str | None:
    """Die Gerätenummer aus der client_ref (`m-11-…`, `ar-12-…`, `wm-12-…`)
    — None, wo kein Gerät beteiligt war (`man-`, `imp-`, `cut-`)."""
    m = re.match(r"^[a-z]+-(\d\d)-\d+$", str(eintrag["client_ref"]))
    return m.group(1) if m else None


def innen14(punkte: list, ts_von: int) -> bytes:
    return b"".join(struct.pack(">iihI", runde(p[1] * 1e7), runde(p[2] * 1e7),
                                gh.hoehe_innen(p[3]), p[4] - ts_von) for p in punkte)


def spur_bauen(eintrag: dict, art: str, konto_pub: bytes, kp, geraete: dict) -> tuple[list, str]:
    """Die Stücke einer Spur und wer sie verschlossen hat ('konto' oder die
    Gerätenummer). Die Regel steht im Kopf."""
    punkte = sorted(eintrag["track"], key=lambda p: p[0])
    ref = str(eintrag["client_ref"])
    stufe = int(eintrag.get("stufe") or 1)
    nummer = geraet_von(eintrag)
    aus = []
    if stufe >= 2:
        von = min(p[4] for p in punkte)
        st = {"seq_from": punkte[0][0], "punkte": len(punkte), "n_original": int(eintrag["n_original"]),
              "stufe": stufe, "ts_von": von, "ts_bis": max(p[4] for p in punkte)}
        klar = gh.spur_innen2(von, gh.innen_gepackt([(p[1], p[2], p[3], p[4]) for p in punkte], von))
        platz = gh.platz_gepackt(st["seq_from"], st["punkte"], stufe, st["n_original"])
        st["huelle"] = base64.b64encode(huelle2(klar, konto_pub, kp, art, ref, platz)).decode()
        return [st], gh.KONTO
    vom_geraet = (nummer in geraete and not eintrag.get("loeschungen")
                  and eintrag.get("origin", "geraet") != "schnitt")
    grenze = STUECK_GERAET if vom_geraet else STUECK_KONTO
    gruppen: list[list] = []
    for p in punkte:
        if gruppen and p[0] == gruppen[-1][-1][0] + 1 and len(gruppen[-1]) < grenze:
            gruppen[-1].append(p)
        else:
            gruppen.append([p])
    absender = geraete[nummer] if vom_geraet else kp
    for g in gruppen:
        von = min(p[4] for p in g)
        letzte = g is gruppen[-1]
        klar = gh.spur_innen2(von, innen14(g, von),
                              schluss=bool(vom_geraet and letzte and eintrag.get("final")))
        platz = f"{g[0][0]}:{len(g)}"
        aus.append({"seq_from": g[0][0], "punkte": len(g), "ts_von": von,
                    "ts_bis": max(p[4] for p in g),
                    "huelle": base64.b64encode(huelle2(klar, konto_pub, absender, art, ref, platz)).decode()})
    return aus, (nummer if vom_geraet else gh.KONTO)


def einsatz_bauen(m: dict, konto_pub: bytes, kp, geraete: dict) -> tuple[bytes | None, dict, str | None]:
    """Die Einsatzhülle, der Standbucheintrag danach (o, k|a, f) und der
    Absender — (None, {}, None), wo es keine gibt."""
    if not m.get("phases") or (m.get("origin") == "schnitt" and not m.get("edited")):
        return None, {}, None
    ref = str(m["client_ref"])
    start = sek(m["started_at"])
    phasen = [{"phase": int(p["phase"]), "t": sek(p["occurred_at"]) - start,
               "lat": p.get("lat"), "lon": p.get("lon")} for p in m["phases"]]
    rea = [{"t": sek(r["started_at"]) - start,
            "ereignisse": [{"art": e["type"], "t": sek(e["occurred_at"]) - start} for e in r["events"]]}
           for r in (m.get("resus") or [])]
    nummer = geraet_von(m)
    stand: dict = {}
    absender = None
    if nummer in geraete and m.get("origin") in GERAETEHERKUNFT:
        # Das Gerät: `z` ist die Sekunde der letzten Änderung am Inhalt — der
        # Abschluss (E-KA-69), sonst der Beginn.
        z = sek(m["ended_at"]) if m.get("ended_at") else start
        schluss = bool(m.get("final"))
        innen = json.loads(gh.einsatz_innen2(z, start, schluss, phasen, rea))
        absender = gh.sid(gh.sec1(geraete[nummer].public_key())).hex()
        stand = gh.stand_nach_abnahme(innen, absender, stand)
        if not m.get("edited"):
            klar = gh.einsatz_innen2(z, start, schluss, phasen, rea)
            return huelle2(klar, konto_pub, geraete[nummer], "einsatz", ref), stand, absender
    # Das Konto: der nächste Zähler; `schluss` trägt es weiter (E-KA-76).
    z = int(stand.get("o", 0)) + 1
    schluss = bool(stand.get("f"))
    klar = gh.einsatz_innen2(z, start, schluss, phasen, rea)
    stand = gh.stand_nach_abnahme(json.loads(klar), gh.KONTO, stand)
    return huelle2(klar, konto_pub, kp, "einsatz", ref), stand, gh.KONTO


# ---------------------------------------------------------------- Der Lauf

def bauen(fx3: dict, runden: int) -> tuple[dict, dict]:
    """Format 3 → Format 4, dazu die Zahlen des Laufs."""
    if fx3.get("format") != "einsatzdoku-demo-fixture" or int(fx3.get("version") or 0) != 3:
        raise Abbruch(f"Die Eingabe ist kein Fixture-Format 3 (version {fx3.get('version')!r}).")
    ck, kp = inhaltsschluessel(), kontopaar()
    konto_pub = gh.sec1(kp.public_key())
    alt_ck = ck_alt(fx3["konto"])
    t_fx = int(datetime.fromisoformat(fx3["erzeugt_am"]).timestamp())

    # Die Demo-Geräte: je Gerät der Fixture eines, über die Gerätenummer, die
    # in den Dienstkennungen des Bestands steht (`d-11-…` → 11).
    nummern: dict[str, str] = {}
    for tag in fx3["daten"].get("days", []):
        for r in tag.get("refs") or []:
            m = re.match(r"^[a-z]+-(\d\d)-", str(r.get("day_ref", "")))
            if m and r.get("device_id"):
                nummern[r["device_id"]] = m.group(1)
    geraete, geraeteliste, buch = {}, [], []
    for g in fx3["geraete"]:
        nummer = nummern.get(g["device_id"])
        if nummer is None:
            raise Abbruch(f"Gerät {g['device_id']} steht in keiner Dienstkennung — welcher "
                          f"Geräteschlüssel gehört ihm?")
        priv = geraeteschluessel(nummer)
        spk = gh.sec1(priv.public_key())
        geraete[nummer] = priv
        geraeteliste.append({**g, "spk": base64.b64encode(spk).decode(), "herkunft": 1})
        buch.append({"sid": gh.sid(spk).hex(), "spk": base64.b64encode(spk).decode(),
                     "geraet": g["device_id"], "seit": t_fx, "bis": None})

    daten = json.loads(json.dumps(fx3["daten"]))
    daten["version"] = 13                      # Hüllen und Verweise, wie eine Kontosicherung
    eintraege: dict[str, dict] = {}            # client_ref → Standbucheintrag
    spuren: list[dict] = []
    zeiten: dict[str, tuple[int, list[int]]] = {}   # client_ref → (Platz in `spuren`, Punktzeiten)
    zahl = {"punkte": 0, "stufe": {1: 0, 2: 0, 3: 0}, "spuren": 0, "stuecke": 0,
            "spuren_geraet": 0, "spuren_konto": 0, "huellen_geraet": 0, "huellen_konto": 0,
            "ohne_huelle": 0, "bloecke": 0, "sperren": 0}

    def spur(e: dict, typ: str, art: str) -> None:
        if not e.get("track"):
            for f in ("track", "stufe", "n_original"):
                e.pop(f, None)
            return
        stuecke, wer = spur_bauen(e, art, konto_pub, kp, geraete)
        nr = len(spuren) + 1
        spuren.append({"art": typ, "spur_ref": nr, "client_ref": e["client_ref"], "stuecke": stuecke})
        n, stufe = len(e["track"]), int(e.get("stufe") or 1)
        zeiten[e["client_ref"]] = (len(spuren) - 1, [p[4] for p in e["track"]])
        zahl["punkte"] += n
        zahl["stufe"][stufe] += n
        zahl["spuren"] += 1
        zahl["stuecke"] += len(stuecke)
        zahl["spuren_konto" if wer == gh.KONTO else "spuren_geraet"] += 1
        for f in ("track", "stufe", "n_original"):
            e.pop(f, None)
        e["spur_ref"], e["n"] = nr, n

    for m in daten["missions"]:
        ref = str(m["client_ref"])
        huelle, stand, absender = einsatz_bauen(m, konto_pub, kp, geraete)
        if huelle is None:
            zahl["ohne_huelle"] += 1
        else:
            m["ort_huelle"] = base64.b64encode(huelle).decode()
            eintraege.setdefault(ref, {}).update(stand)
            zahl["huellen_konto" if absender == gh.KONTO else "huellen_geraet"] += 1
        for p in m["phases"]:
            p.pop("lat", None)
            p.pop("lon", None)
        m.pop("resus", None)
        if m.get("pat_blob"):
            try:
                klar = pat_klar(alt_ck, m["pat_blob"], ref)
            except inh.InhaltFehler as f:
                raise Abbruch(f"Der Patientenblock von {ref} geht nicht auf ({f}).") from f
            m["pat_blob"] = edk2(ck, "pat", ref, klar, 1)
            eintraege.setdefault(ref, {})["p"] = 1
            zahl["bloecke"] += 1
        # Ein Schnitt sperrt seinen Zeitraum im Quellsegment — der Vermerk
        # des Kontos dazu (`EdOrtsdaten.sperreVermerken()`).
        for c in m.get("schnitte") or []:
            eintraege.setdefault(str(c["quelle_ref"]), {}).setdefault("x", []).append(
                [int(c["von_ts"]), int(c["bis_ts"])])
            zahl["sperren"] += 1
        spur(m, "mission", "spur")
    for r in daten["rest_segments"]:
        for c in r.get("loeschungen") or []:
            eintraege.setdefault(str(r["client_ref"]), {}).setdefault("x", []).append(
                [int(c["von_ts"]), int(c["bis_ts"])])
            zahl["sperren"] += 1
        spur(r, "rest", "ruhe")

    # `sichtbar`: wie viele Punkte die Anwendung von dieser Spur zeigt — die
    # gespeicherten ohne die, die ein Vermerk sperrt (ein Schnitt lässt seine
    # Punkte im Segment, bis der Wartungslauf es packt). Im Klartext, für die
    # Browserprobe (`demo_fassung2.mjs`); der Reset liest es nicht.
    for ref, (platz, ts) in zeiten.items():
        x = eintraege.get(ref, {}).get("x", [])
        spuren[platz]["sichtbar"] = sum(1 for t in ts if not any(von <= t <= bis for von, bis in x))

    # Die Blätter: alle 16, alle geschlossen — der Anhebelauf hat hier nichts
    # mehr zu tun, und Fassung 1 wäre in diesem Konto ein Befund.
    je_blatt: dict[int, dict] = {nr: {} for nr in range(inh.BLAETTER)}
    for ref, e in eintraege.items():
        if "x" in e:
            e["x"] = sorted(e["x"])
        je_blatt[inh.blatt_nr(ref)][inh.kennung(ref)] = inh.eintrag_ordnen(e)
    standbuch = []
    for nr in range(inh.BLAETTER):
        e = dict(sorted(je_blatt[nr].items()))
        nutz = inh._json({"fassung": 1, "t": t_fx, "zu": True, "e": e})
        standbuch.append({"wer": nr, "version": 1, "text": edk2(ck, "stand", str(nr), nutz, 1)})
    standbuch.append({"wer": 16, "version": 1,
                      "text": edk2(ck, "geraete", "", inh._json({"fassung": 1, "geraete": buch}), 1)})

    fx4 = {
        "format": "einsatzdoku-demo-fixture", "version": 4,
        "erzeugt_am": fx3["erzeugt_am"], "web_version": fx3.get("web_version"),
        "quelle": fx3.get("quelle"),
        "konto": konto_bauen(fx3["konto"], ck, kp, runden),
        "geraete": geraeteliste, "standbuch": standbuch, "daten": daten, "spuren": spuren,
    }
    zahl["eintraege"] = len(eintraege)
    return fx4, zahl


# ------------------------------------------------------------- Gegenlesung

def gegenlesen(fx3: dict, fx4: dict) -> list[str]:
    """Alles öffnen, was `bauen()` verschlossen hat — mit dem, was ein
    Browser hat: Demo-Passwort, Konto der Datei, Standbuch der Datei —, nach
    den Regeln der Referenz, und gegen die Eingabe halten."""
    bef: list[str] = []
    k = fx4["konto"]
    haelfte, token = ableiten(DEMO_PASSWORT, bytes.fromhex(k["kdf_salt"]), int(k["kdf_iter"]))
    try:
        import bcrypt  # noqa: PLC0415
        if not bcrypt.checkpw(token.hex().encode(), ("$2b$" + k["password_hash"][4:]).encode()):
            bef.append("Konto: Das Anmelde-Token passt nicht zum Hash.")
        ck = bytes.fromhex(inh.huelle_oeffnen(haelfte, k["pat_wrap_pw"], "pw"))
        if bytes.fromhex(inh.huelle_oeffnen(bytes.fromhex(k["rc_hex"]), k["pat_wrap_rc"], "rc")) != ck:
            bef.append("Konto: Die Wiederherstellungshülle trägt einen anderen Inhaltsschlüssel.")
        pkcs8, _ = inh.oeffnen(ck, k["kp_privat"], "kp", "")
        priv = serialization.load_der_private_key(base64.b64decode(pkcs8), password=None)
        spki = serialization.load_der_public_key(base64.b64decode(k["kp_oeffentlich"]))
        if gh.sec1(priv.public_key()) != gh.sec1(spki):
            bef.append("Konto: Der private Teil des Kontopaars passt nicht zum öffentlichen.")
        buch, _ = inh.geraetebuch_oeffnen(ck, fx4["standbuch"][16]["text"])
        blaetter = [inh.blatt_oeffnen(ck, b["text"], b["wer"])[0] for b in fx4["standbuch"][:16]]
    except (inh.InhaltFehler, ValueError, KeyError) as f:
        return bef + [f"Konto oder Standbuch geht nicht auf: {f!r}"]
    if any(not b.get("zu") for b in blaetter):
        bef.append("Standbuch: nicht jedes Blatt ist geschlossen.")
    for g in fx4["geraete"]:
        s = gh.sid(base64.b64decode(g["spk"])).hex()
        if s not in buch or buch[s]["spk"] != base64.b64decode(g["spk"]):
            bef.append(f"Gerät {g['device_id']} steht nicht im Gerätebuch.")
    leser = gh.Leser(priv, buch, verlangt2=True)
    alt_ck = ck_alt(fx3["konto"])
    eintrag = lambda ref: blaetter[inh.blatt_nr(ref)]["e"].get(inh.kennung(ref), {})  # noqa: E731
    stuecke = {(s["art"], s["spur_ref"]): s["stuecke"] for s in fx4["spuren"]}

    def spur(alt: dict, neu: dict, typ: str, art: str) -> None:
        ref = str(alt["client_ref"])
        if not alt.get("track"):
            if "spur_ref" in neu:
                bef.append(f"{ref}: eine Spur, wo die Eingabe keine hat")
            return
        e = eintrag(ref)
        liste = [dict(st, huelle=base64.b64decode(st["huelle"])) for st in stuecke[(typ, neu["spur_ref"])]]
        try:
            pkt, befund = gh.spur_lesen(leser, art, ref, liste, versatz=e.get("d", 0),
                                        sperren=tuple(map(tuple, e.get("x", []))),
                                        stand=tuple(e["s"]) if e.get("s") else None)
        except gh.HuelleFehler as f:
            bef.append(f"{ref}: die Spur geht nicht auf ({f})")
            return
        # Was die Vermerke des Standbuchs ausblenden, blendet die Anwendung
        # auch über die Sperren des Klartexts aus — verglichen wird der Rest.
        soll = [p for p in sorted(alt["track"], key=lambda p: p[0])
                if not any(von <= p[4] <= bis for von, bis in e.get("x", []))]
        ist = [(runde(p[1] * 1e7), runde(p[2] * 1e7), p[3], p[4]) for p in pkt]
        if ist != [(runde(p[1] * 1e7), runde(p[2] * 1e7),
                    None if p[3] is None else gh.hoehe_innen(p[3]), p[4]) for p in soll]:
            bef.append(f"{ref}: die geöffnete Spur weicht ab ({len(ist)} gegen {len(soll)} Punkte)")
        if befund["fassung1"]:
            bef.append(f"{ref}: ein Stück der Fassung 1")

    for alt, neu in zip(fx3["daten"]["missions"], fx4["daten"]["missions"]):
        ref = str(alt["client_ref"])
        e = eintrag(ref)
        if neu.get("pat_blob"):
            try:
                klar, b = inh.pat_lesen(ck, neu["pat_blob"], ref, blaetter[inh.blatt_nr(ref)])
                if b["blatt_hinkt"] or not b["gebunden"]:
                    bef.append(f"{ref}: der Block ist dem Blatt voraus oder nicht gebunden")
                if klar != pat_klar(alt_ck, alt["pat_blob"], ref):
                    bef.append(f"{ref}: der Block trägt einen anderen Inhalt")
            except inh.InhaltFehler as f:
                bef.append(f"{ref}: der Block geht nicht auf ({f})")
        if neu.get("ort_huelle"):
            stand = {f: e[f] for f in gh.STANDFELDER if f in e}
            try:
                d, absender = gh.einsatz_lesen(leser, ref, base64.b64decode(neu["ort_huelle"]),
                                               sek(alt["started_at"]), versatz=e.get("d", 0), stand=stand)
            except gh.HuelleFehler as f:
                bef.append(f"{ref}: die Einsatzhülle geht nicht auf ({f})")
            else:
                if gh.stand_nach_abnahme(d, absender, stand) != stand:
                    bef.append(f"{ref}: der Browser schriebe das Standbuch beim Lesen fort")
                if gh.einsatz_befunde(d, absender, stand):
                    bef.append(f"{ref}: Befund {gh.einsatz_befunde(d, absender, stand)}")
                start = sek(alt["started_at"])
                orte = [(p["phase"], start + p["t"], p["lat"], p["lon"]) for p in d["phasen"]]
                if orte != [(int(p["phase"]), sek(p["occurred_at"]), p.get("lat"), p.get("lon"))
                            for p in alt["phases"]]:
                    bef.append(f"{ref}: Phasen oder Orte weichen ab")
                if len(d["rea"]) != len(alt.get("resus") or []):
                    bef.append(f"{ref}: Reanimationen weichen ab")
        elif any(p.get("lat") is not None for p in alt["phases"]) or alt.get("resus"):
            bef.append(f"{ref}: Orte oder Reanimation in der Eingabe, aber keine Hülle")
        spur(alt, neu, "mission", "spur")
    for alt, neu in zip(fx3["daten"]["rest_segments"], fx4["daten"]["rest_segments"]):
        spur(alt, neu, "rest", "ruhe")
    return bef


# -------------------------------------------------------------- Selbstprobe

def selbstprobe(fx3: dict, runden: int) -> int:
    """Die Gegenlesung muss einen Fehler sehen — je Art eine verbogene Datei."""
    fx4, _ = bauen(fx3, runden)
    if gegenlesen(fx3, fx4):
        print("  [FEHL] die unveränderte Datei hat Befunde")
        return 1

    def geraet_fehlt(d):
        ck = inhaltsschluessel()
        d["standbuch"][16]["text"] = edk2(ck, "geraete", "", inh._json({"fassung": 1, "geraete": []}), 1)

    def fremd(d):       # ein Stück, das ein Gerät verschließt, das in keinem Buch steht
        s = next(x for x in d["spuren"] if x["client_ref"].startswith("m-11-")
                 and "stufe" not in x["stuecke"][0])
        e = next(m for m in fx3["daten"]["missions"] if m["client_ref"] == s["client_ref"])
        st, _ = spur_bauen(e, "spur", gh.sec1(kontopaar().public_key()), kontopaar(),
                           {"11": gh.schluessel_aus_name("ein-fremdes-geraet")})
        s["stuecke"] = st

    def stueck_fehlt(d):
        s = next(x for x in d["spuren"] if len(x["stuecke"]) > 1)
        del s["stuecke"][0]

    def vertauscht(d):
        m = [x for x in d["daten"]["missions"] if x.get("pat_blob")]
        m[0]["pat_blob"], m[1]["pat_blob"] = m[1]["pat_blob"], m[0]["pat_blob"]

    def zeit(d):
        s = d["spuren"][0]["stuecke"][0]
        s["ts_von"] += 60

    def huelle_getauscht(d):
        m = [x for x in d["daten"]["missions"] if x.get("ort_huelle")]
        m[0]["ort_huelle"], m[1]["ort_huelle"] = m[1]["ort_huelle"], m[0]["ort_huelle"]

    def blatt_offen(d):
        ck = inhaltsschluessel()
        blatt, _ = inh.blatt_oeffnen(ck, d["standbuch"][3]["text"], 3)
        blatt["zu"] = False
        d["standbuch"][3]["text"] = edk2(ck, "stand", "3", inh._json(blatt), 1)

    def passwort(d):
        d["konto"]["kdf_salt"] = "00" * 16

    fehl = 0
    for name, biegen in [("Demo-Gerät nicht im Gerätebuch", geraet_fehlt),
                         ("ein Stück von einem fremden Gerät", fremd),
                         ("ein Stück fehlt", stueck_fehlt),
                         ("zwei Patientenblöcke vertauscht", vertauscht),
                         ("Klartextzeit eines Stücks verschoben", zeit),
                         ("zwei Einsatzhüllen vertauscht", huelle_getauscht),
                         ("ein Blatt nicht geschlossen", blatt_offen),
                         ("ein anderes Salz am Konto", passwort)]:
        kopie = json.loads(json.dumps(fx4))
        biegen(kopie)
        gesehen = bool(gegenlesen(fx3, kopie))
        print(f"  [{'ok ' if gesehen else 'FEHL'}] {name}")
        fehl += 0 if gesehen else 1
    zweite, _ = bauen(fx3, runden)
    gleich = json.dumps(zweite, sort_keys=True) == json.dumps(fx4, sort_keys=True)
    print(f"  [{'ok ' if gleich else 'FEHL'}] zweimal gebaut ist byte-gleich")
    fehl += 0 if gleich else 1
    print(f"Selbstprobe: 9 Fälle, {fehl} Fehlschläge")
    return 1 if fehl else 0


def runden_ziel() -> int:
    m = re.search(r"^const KDF_ITER_ZIEL\s*=\s*(\d+);", (WURZEL / "server" / "db.php").read_text("utf-8"), re.M)
    if not m:
        raise Abbruch("`KDF_ITER_ZIEL` steht nicht in server/db.php — --runden angeben.")
    return int(m.group(1))


def main() -> int:
    args = sys.argv[1:]
    try:
        runden = None
        if "--runden" in args:
            i = args.index("--runden")
            runden = int(args[i + 1])
            del args[i:i + 2]
        probe = "--selbstprobe" in args
        args = [a for a in args if a != "--selbstprobe"]
        if not args:
            print(__doc__.split("Aufruf:")[1])
            return 2
        eingabe = pathlib.Path(args[0])
        ziel = pathlib.Path(args[1]) if len(args) > 1 else ZIEL
        roh = eingabe.read_bytes()
        fx3 = json.loads(gzip.decompress(roh) if roh[:2] == b"\x1f\x8b" else roh)
        if fx3.get("format") == "einsatzdoku-demo-rohbestand":
            fx3 = roh_oeffnen(fx3)
        runden = runden if runden is not None else runden_ziel()
        if probe:
            return selbstprobe(fx3, runden)
        fx4, zahl = bauen(fx3, runden)
        bef = gegenlesen(fx3, fx4)
    except (Abbruch, OSError, ValueError, KeyError) as f:
        print(f"NICHT GELAUFEN: {f}", file=sys.stderr)
        return 2
    if bef:
        print(f"GEGENLESUNG WEICHT AB ({len(bef)}) — keine Datei geschrieben", file=sys.stderr)
        for b in bef[:20]:
            print(f"  {b}", file=sys.stderr)
        return 1
    text = json.dumps(fx4, ensure_ascii=False, separators=(",", ":"))
    # `mtime=0`: Die gepackte Datei trägt sonst die Uhrzeit des Laufs.
    gepackt = gzip.compress(text.encode("utf-8"), 9, mtime=0)
    ziel.parent.mkdir(parents=True, exist_ok=True)
    ziel.write_bytes(gepackt)
    d = fx4["daten"]
    print(f"Fixture geschrieben: {ziel} ({len(gepackt) / 1024:.1f} KB gepackt, "
          f"{len(text.encode()) / 1024:.1f} KB roh)")
    print(f"  Format       4, Nutzlast 13, Quelle {fx4['quelle']}")
    print(f"  Konto        {fx4['konto']['email']}, kdf_iter {runden} "
          f"(Ableitung {2 if runden >= ABLEITUNG2_AB else 1})")
    print(f"  Geraete      {len(fx4['geraete'])}, beide im Gerätebuch")
    print(f"  Einsaetze    {len(d['missions'])}  ({zahl['bloecke']} Patientenblöcke; Einsatzhüllen: "
          f"{zahl['huellen_geraet']} vom Gerät, {zahl['huellen_konto']} vom Konto, "
          f"{zahl['ohne_huelle']} ohne)")
    print(f"  Ruhesegmente {len(d['rest_segments'])}")
    print(f"  Spuren       {zahl['spuren']} in {zahl['stuecke']} Stücken "
          f"({zahl['spuren_geraet']} vom Gerät, {zahl['spuren_konto']} vom Konto)")
    print(f"  Spurpunkte   {zahl['punkte']}  (Stufe 1: {zahl['stufe'][1]}, Stufe 2: {zahl['stufe'][2]}, "
          f"Stufe 3: {zahl['stufe'][3]})")
    print(f"  Standbuch    16 Blätter geschlossen, {zahl['eintraege']} Einträge, "
          f"{zahl['sperren']} gesperrte Zeiträume")
    print("  Gegenlesung  ohne Befund")
    return 0


if __name__ == "__main__":
    sys.exit(main())
