"""Teil 2 der Containerprobe: dieselbe Datei mit dem Python-Leser oeffnen.

WOFUER. Der Browser hat die Datei geschrieben. Dass er sie selbst wieder
aufbekommt, belegt wenig — ein Fehler im Format faellt so nicht auf, weil
beide Seiten denselben Fehler machen. Erst ein ZWEITER, unabhaengiger Leser
sagt, ob die Datei das Format haelt, das dokumentiert ist.

Dieser Leser ist `tools/referenzdatensatz/vergleich/lesen.py` — derselbe, der
im Kreislauf die Sicherungen vergleicht. Er entsiegelt nach dem Rezept aus
`docs/Backup-Format.md` und dekodiert SPUR1 mit einer eigenen Umsetzung.

Dazu die Schadensfaelle: Was passiert, wenn ein Teil FEHLT, wenn zwei
VERTAUSCHT sind, wenn eines aus einer FREMDEN Sicherung stammt, wenn eine
Pruefsumme im Manifest nicht stimmt und wenn eine Datei zu viel im Archiv
liegt? Jeder davon muss auffallen — und zwar benannt.

Aufruf (aus der Probe heraus): python3 lesen_pruefen.py <ordner> <passwort>
"""
from __future__ import annotations

import json
import os
import shutil
import sys
import zipfile

sys.path.insert(0, os.path.join(os.path.dirname(os.path.dirname(
    os.path.abspath(__file__))), "referenzdatensatz", "vergleich"))
import lesen  # noqa: E402

ordner, passwort = sys.argv[1], sys.argv[2]
gut = os.path.join(ordner, "gut.edbak")
futter = json.load(open(os.path.join(ordner, "futter.json"), encoding="utf-8"))

offen = 0


def pruefe(ok: bool, was: str, wert: str = "") -> None:
    global offen
    if not ok:
        offen += 1
    print(f"  [{'ok ' if ok else 'FEHL'}] {was:<56} {wert}")


def scheitert(fn) -> str | None:
    try:
        fn()
        return None
    except Exception as ex:      # noqa: BLE001 — genau das ist die Frage
        return str(ex)


def umbauen(ziel: str, aendern) -> str:
    """Eine Kopie der Datei mit veraenderten Eintraegen."""
    with zipfile.ZipFile(gut) as z:
        eintraege = [(i.filename, z.read(i.filename)) for i in z.infolist()]
    eintraege = aendern(eintraege)
    pfad = os.path.join(ordner, ziel)
    with zipfile.ZipFile(pfad, "w", zipfile.ZIP_STORED) as z:
        for name, roh in eintraege:
            z.writestr(name, roh)
    return pfad


# ---- Die heile Datei --------------------------------------------------------

daten = lesen.lesen_edbak(gut, passwort)
c = daten["$container"]
pruefe(c["fassung"] == 4, "Die Datei geht im Python-Leser auf",
       f"Fassung {c['fassung']}, {c['teile']} Teile")
pruefe(c["pruefsummen_ok"], "Jede SHA-256 des Manifests stimmt",
       ", ".join(c["teilenamen"]))
pruefe(not c["blobs_ohne_objekt"] and not c["objekte_ohne_blob"],
       "Jede spur_ref findet ihren Blob und umgekehrt",
       f"{len(daten['missions'])} Einsaetze, {len(daten['rest_segments'])} Ruhesegmente")
pruefe(not c["ueberzaehlige_dateien"], "Keine Datei im Archiv, die das Manifest nicht kennt")

# GESPEICHERT UND NICHT GEPACKT (Konzept 3.2.1). Die Teile sind bereits gzip
# UND verschluesselt; ein zweiter Packlauf kostet Zeit und bringt nichts.
# Gemessen wird das VERFAHREN je Eintrag (0 = gespeichert, 8 = deflate) und
# nicht die Dateigroesse — bei kleinen Teilen ist der ZIP-Rahmen groesser als
# jede denkbare Ersparnis, und ein Groessenvergleich misst dann den Rahmen.
with zipfile.ZipFile(gut) as _z:
    verfahren = {i.filename: i.compress_type for i in _z.infolist()}
pruefe(set(verfahren.values()) == {zipfile.ZIP_STORED},
       "Jeder Eintrag ist gespeichert, nicht gepackt",
       ", ".join(f"{n}: {v}" for n, v in verfahren.items()))

# ---- Punkt fuer Punkt gegen das, was PHP hineingegeben hat ------------------

soll = {int(k): v for k, v in futter["punkte"].items()}
vergleiche = 0
abweichung = None

# ZUORDNUNG UEBER `client_ref`, NICHT UEBER DIE REIHENFOLGE. Der Leser nimmt
# `spur_ref` beim Zusammensetzen heraus (es ist eine Nummer des
# Exportvorgangs, kein Sachdatum). Sich auf die Listenfolge zu verlassen
# hiesse, eine Vertauschung genau dort nicht zu bemerken, wo diese Probe sie
# suchen soll. Die Probe legt die Objekte als probe-m0…m3 und probe-r0…r1 an,
# in der Reihenfolge des Prueffutters.
nach_ref = {}
for i, eintrag in enumerate(futter["spuren"]):
    nach_ref[eintrag["spur_ref"]] = ("probe-m%d" % i if i < 4 else "probe-r%d" % (i - 4))
nach_client = {o["client_ref"]: o
               for o in daten["missions"] + daten["rest_segments"]}

for eintrag in futter["spuren"]:
    ref = eintrag["spur_ref"]
    obj = nach_client.get(nach_ref[ref])
    if obj is None:
        abweichung = f"Spur {ref}: kein Objekt {nach_ref[ref]} in der Datei"
        break
    ist = obj["track"]
    erwartet = soll[ref]
    if len(ist) != len(erwartet):
        abweichung = f"Spur {ref}: {len(ist)} statt {len(erwartet)} Punkten"
        break
    for k, p_soll in enumerate(erwartet):
        p_ist = ist[k]
        vergleiche += 5
        if (p_ist[0] != p_soll[0]
                or abs(p_ist[1] - p_soll[1]) > 1e-9
                or abs(p_ist[2] - p_soll[2]) > 1e-9
                or (p_ist[3] is None) != (p_soll[3] is None)
                or (p_ist[3] is not None and abs(p_ist[3] - p_soll[3]) > 1e-9)
                or p_ist[4] != p_soll[4]):
            abweichung = f"Spur {ref}, Punkt {k}: {p_ist} statt {p_soll}"
            break
    if abweichung:
        break

pruefe(abweichung is None and vergleiche > 0,
       "Jeder Punkt kommt so an, wie PHP ihn kodiert hat",
       abweichung or f"{vergleiche} Einzelvergleiche, 0 Abweichungen")

# ---- Die Schadensfaelle -----------------------------------------------------

fehlt = umbauen("fehlt.edbak",
                lambda e: [x for x in e if x[0] != "spuren/0002.edbak"])
m = scheitert(lambda: lesen.lesen_edbak(fehlt, passwort))
pruefe(bool(m) and "spuren/0002.edbak" in m,
       "Ein FEHLENDES Teil faellt auf und wird benannt", (m or "")[:52])


def tauschen(e):
    d = dict(e)
    a, b = d["spuren/0001.edbak"], d["spuren/0002.edbak"]
    d["spuren/0001.edbak"], d["spuren/0002.edbak"] = b, a
    return list(d.items())


vertauscht = umbauen("vertauscht.edbak", tauschen)
m = scheitert(lambda: lesen.lesen_edbak(vertauscht, passwort))
pruefe(bool(m) and "Pruefsumme" in m,
       "Zwei VERTAUSCHTE Teile fallen an der Pruefsumme auf", (m or "")[:60])

fremd_roh = open(os.path.join(ordner, "fremdes_teil.bin"), "rb").read()
fremd = umbauen("fremd.edbak",
                lambda e: [(n, fremd_roh if n == "spuren/0001.edbak" else r) for n, r in e])
m = scheitert(lambda: lesen.lesen_edbak(fremd, passwort))
pruefe(bool(m), "Ein Teil aus einer FREMDEN Sicherung faellt auf", (m or "")[:60])


# EINE PRUEFSUMME IM MANIFEST LAESST SICH NICHT FAELSCHEN, ohne das Passwort
# zu kennen — das Manifest ist selbst versiegelt. Geprueft wird deshalb der
# Fall, der ohne Passwort moeglich ist: ein veraendertes TEIL. Der Leser muss
# es an der Summe merken, noch bevor die Zusatzdaten zuschlagen.
def teil_verbiegen(e):
    aus = []
    for n, r in e:
        if n == "spuren/0001.edbak":
            b = bytearray(r)
            b[-20] ^= 0x01
            r = bytes(b)
        aus.append((n, r))
    return aus


verbogen = umbauen("verbogen.edbak", teil_verbiegen)
m = scheitert(lambda: lesen.lesen_edbak(verbogen, passwort))
pruefe(bool(m) and "Pruefsumme" in m,
       "Ein verfaelschtes Teil faellt an der Pruefsumme auf", (m or "")[:60])

# UND DIE AAD ALLEIN, ohne die Pruefsumme davor.
#
# Die beiden Sicherungen fangen dieselben Faelle; deshalb muss eine Probe
# zeigen, dass jede von ihnen fuer sich traegt. Sonst haengt in Wahrheit alles
# an der Pruefsumme — und die kann jeder mitschreiben, der das Passwort hat.
#
# Der Fall: Zwei Spurteile werden vertauscht UND das Manifest wird passend
# nachgezogen, sodass beide Pruefsummen stimmen. Dann bleibt nur die Bindung
# der Zusatzdaten. Dafuer braucht diese Probe einen Versiegler — sie ist hier
# die Angreiferin, nicht ein zweiter Schreibweg der Anwendung.
def versiegeln(schluessel: bytes, klartext: bytes, aad_text: str,
               flag: int, runden: int, salz: bytes) -> bytes:
    import gzip as gziplib
    import os as _os
    import struct as _s
    from cryptography.hazmat.primitives.ciphers.aead import AESGCM
    kopf = (lesen.MAGIE_TEIL + bytes([0, lesen.FASSUNG_TEIL, flag])
            + _s.pack(">I", runden))
    koerper = gziplib.compress(klartext) if flag == 1 else klartext
    iv = _os.urandom(12)
    ct = AESGCM(schluessel).encrypt(iv, koerper, kopf + aad_text.encode("utf-8"))
    return kopf + salz + iv + ct


def manifest_nachziehen(e):
    from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2HMAC
    from cryptography.hazmat.primitives import hashes
    import hashlib as _h

    d = dict(e)
    d["spuren/0001.edbak"], d["spuren/0002.edbak"] = (
        d["spuren/0002.edbak"], d["spuren/0001.edbak"])

    flag, runden, salz = lesen._teil_kopf(d["manifest.edbak"])
    kdf = PBKDF2HMAC(algorithm=hashes.SHA256(), length=32, salt=salz, iterations=runden)
    schluessel = kdf.derive(passwort.encode("utf-8"))
    manifest = json.loads(lesen._teil_oeffnen(
        schluessel, d["manifest.edbak"], "EDBAK4|manifest", "Das Manifest").decode("utf-8"))
    for t in manifest["teile"]:
        t["sha256"] = _h.sha256(d[t["name"]]).hexdigest()
    d["manifest.edbak"] = versiegeln(
        schluessel, json.dumps(manifest).encode("utf-8"),
        "EDBAK4|manifest", flag, runden, salz)
    return list(d.items())


nur_aad = umbauen("nur_aad.edbak", manifest_nachziehen)
daten3 = None
m = scheitert(lambda: lesen.lesen_edbak(nur_aad, passwort))
pruefe(bool(m) and "Pruefsumme" not in m,
       "Auch bei stimmenden Pruefsummen fangen die Zusatzdaten es",
       (m or "die Datei ging auf — die Bindung traegt NICHT")[:60])

m = scheitert(lambda: lesen.lesen_edbak(gut, "das ist nicht das passwort"))
pruefe(bool(m) and "Passwort" in m,
       "Ein falsches Passwort wird benannt", (m or "")[:52])

kein_manifest = umbauen("ohne_manifest.edbak",
                        lambda e: [x for x in e if x[0] != "manifest.edbak"])
m = scheitert(lambda: lesen.lesen_edbak(kein_manifest, passwort))
pruefe(bool(m) and "manifest" in m.lower(),
       "Ein ZIP ohne Manifest ist keine Sicherung", (m or "")[:52])

sys.exit(1 if offen else 0)
