"""Eine Backup-Datei SCHREIBEN — das Gegenstück zu `vergleich/lesen.py`.

SEIT WEB 23 FASSUNG 4 (S11-12, E-S11-85). Der Einspielweg nimmt nur noch die
mehrteilige Datei mit Nutzlast 13; die einteilige Fassung 3 mit Nutzlast 7,
die dieses Werkzeug bis Web 22.7.0 schrieb, weist er ab. `schreiben_edbak_v4()`
baut deshalb das ZIP, das auch der Browser baut (`einstellungen.php`,
`assets/crypto.js` `sealTeil`): Manifest, Kopf, Eintragsteile, Spurteile mit
Punktlisten. Backlog Nr. 46 hatte genau das vorhergesagt.

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

DAS FORMAT (Fassung 4, `server/assets/crypto.js`, `sealTeil`; ausführlich
`docs/Backup-Format.md`):

    ZIP (gespeichert) mit manifest.edbak, kopf.edbak, eintraege/NNNN.edbak,
    spuren/NNNN.edbak; jedes Teil:
    "EDBAK2" 0x00 0x04 | Flag(1) | Runden(4, big endian) | Salt(16) | IV(12) | AES-GCM
    AAD: die ersten 13 Bytes + "EDBAK4|manifest" bzw.
         "EDBAK4|<kennung>|<name>|<nr>/<gesamt>"
    Flag: 1 = Inhalt gzip-gepackt
    Schlüssel: PBKDF2-SHA256(Backup-Passwort, Salt, Runden, 256 Bit), ein
    Salz für alle Teile

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

# Dieselbe Rundenzahl, die `einstellungen.php` beim Versiegeln benutzt
# (KDF_ITER des Kontos). Die Referenzdatei trägt 320 000; wer eine andere
# braucht, übergibt sie.
RUNDEN_VORGABE = 320000


AAD_MARKE = "EDBAK4"


def _teil_versiegeln(schluessel: bytes, salt: bytes, runden: int, roh: bytes,
                     aad_text: str) -> bytes:
    """Ein Teil der Fassung 4 — wie `sealTeil()` in `assets/crypto.js`:
    Kopf "EDBAK2" 0x00 0x04 | Flag | Runden(4, BE), dann Salz, IV und
    AES-GCM; Zusatzdaten sind die 13 Kopfbytes plus die AAD-Zeichenkette."""
    from cryptography.hazmat.primitives.ciphers.aead import AESGCM
    koerper = gzip.compress(roh, 6)
    kopf = MAGIE + bytes([0, 4, 1]) + runden.to_bytes(4, "big")
    iv = os.urandom(12)
    ct = AESGCM(schluessel).encrypt(iv, koerper, kopf + aad_text.encode("utf-8"))
    return kopf + salt + iv + ct


def schreiben_edbak_v4(kopf: dict, eintragsteile: list, spurteile: list, passwort: str,
                       runden: int = RUNDEN_VORGABE) -> bytes:
    """Ein mehrteiliges Backup (Fassung 4) als ZIP-Bytes.

    `kopf` ist der Kopf der Nutzlast 13 (Stammdaten, Diensttage,
    `eintraege_gesamt`), `eintragsteile` eine Liste {missions, rest_segments},
    `spurteile` eine Liste {spuren: [{spur_ref, stufe, n_original, n, punkte}]}.
    Die Reihenfolge der Teile und die Form des Manifests folgen
    `einstellungen.php` (Sichern im Browser)."""
    import hashlib
    import io
    import zipfile
    from cryptography.hazmat.primitives import hashes
    from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2HMAC

    if not 1000 <= runden <= 10_000_000:
        raise ValueError(f"Rundenzahl unbrauchbar: {runden}")
    salt = os.urandom(16)
    schluessel = PBKDF2HMAC(algorithm=hashes.SHA256(), length=32, salt=salt,
                            iterations=runden).derive(passwort.encode("utf-8"))
    kennung = os.urandom(8).hex()
    plan = ([("kopf.edbak", "kopf", kopf)]
            + [(f"eintraege/{i + 1:04d}.edbak", "eintraege", t) for i, t in enumerate(eintragsteile)]
            + [(f"spuren/{i + 1:04d}.edbak", "spuren", t) for i, t in enumerate(spurteile)])
    gesamt = len(plan)
    puffer = io.BytesIO()
    teile = []
    with zipfile.ZipFile(puffer, "w", zipfile.ZIP_STORED) as z:
        for nr, (name, art, inhalt) in enumerate(plan, start=1):
            roh = json.dumps(inhalt, ensure_ascii=False, separators=(",", ":")).encode("utf-8")
            b = _teil_versiegeln(schluessel, salt, runden, roh,
                                 f"{AAD_MARKE}|{kennung}|{name}|{nr}/{gesamt}")
            teile.append({"name": name, "art": art, "sha256": hashlib.sha256(b).hexdigest()})
            z.writestr(name, b)
        spuren = sum(len(t.get("spuren", [])) for t in spurteile)
        punkte = sum(len(s.get("punkte", [])) for t in spurteile for s in t.get("spuren", []))
        manifest = {"format": "einsatzdoku-backup-manifest", "fassung": 4, "kennung": kennung,
                    "erzeugt_am": kopf.get("created_at"), "web_version": "messstand",
                    "nutzlast": kopf.get("version"), "teile": teile,
                    "eintragsteile": len(eintragsteile),
                    "eintraege": kopf.get("eintraege_gesamt"), "spurteile": len(spurteile),
                    "spuren": spuren, "punkte": punkte,
                    "pat_key_check": kopf.get("pat_key_check"), "unlesbar": 0, "ort_unlesbar": 0}
        roh = json.dumps(manifest, ensure_ascii=False, separators=(",", ":")).encode("utf-8")
        z.writestr("manifest.edbak",
                   _teil_versiegeln(schluessel, salt, runden, roh, f"{AAD_MARKE}|manifest"))
    return puffer.getvalue()


def rundlauf_pruefen_v4(roh: bytes, passwort: str, einsaetze: int, ruhe: int, punkte: int) -> None:
    """Die eben gebaute Datei mit dem unabhaengigen Leser oeffnen und nachzaehlen."""
    import tempfile

    with tempfile.NamedTemporaryFile(suffix=".edbak", delete=False) as f:
        f.write(roh)
        pfad = f.name
    try:
        d = lesen_edbak(pfad, passwort)
        c = d.pop("$container", {})
        n = (sum(len(m.get("track") or []) for m in d.get("missions", []))
             + sum(len(r.get("track") or []) for r in d.get("rest_segments", [])))
        if (len(d.get("missions", [])) != einsaetze or len(d.get("rest_segments", [])) != ruhe
                or n != punkte or not c.get("pruefsummen_ok") or c.get("blobs_ohne_objekt")
                or c.get("objekte_ohne_blob")):
            raise RuntimeError(f"Rundlauf des Containers gescheitert: {len(d.get('missions', []))} "
                               f"Einsaetze, {len(d.get('rest_segments', []))} Ruhesegmente, {n} Punkte")
    finally:
        os.unlink(pfad)
