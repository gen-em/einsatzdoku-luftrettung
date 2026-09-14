"""Anteilprobe, Teil E — `api/kdf_upgrade.php` als Huellenfassung (S10, E-S10-07).

WOFUER. Seit S10 ist dieser Endpunkt der eine Weg, auf dem sich die
Schluesselhuelle eines Kontos still ersetzen laesst. Er entscheidet dabei ueber
einen Fehler, der teuer und spaet auffaellt: eine Huelle, die auf den ALTEN
Server-Anteil zurueckgestellt wird. Sie liesse sich in dem Augenblick nicht
mehr oeffnen, in dem `kdf_anteil_alt` aus `config.php` verschwindet — also
genau dann, wenn die Statusseite meldet, es stehe niemand mehr auf dem alten
Anteil. Ein Praefix, das der Server nicht prueft, ist an dieser Stelle eine
Zeitbombe; dass er es prueft, misst diese Datei.

WARUM PYTHON UND NICHT PHP wie der Rest der Anteilprobe: Gemessen wird ein
ENDPUNKT, und dazu braucht es eine angemeldete Sitzung samt gueltigem
Anmelde-Token. Das Token entsteht aus PBKDF2 ueber das Passwort — im Browser,
und in `tools/referenzdatensatz/einspielen/sitzung.py` auf demselben Weg
nachgebildet. Diese Datei benutzt es, statt es ein drittes Mal zu schreiben.

DIE HUELLEN SIND ECHT, KEINE ATTRAPPEN. Jede hier gebaute Huelle enthaelt den
tatsaechlichen Inhaltsschluessel des Kontos, mit dem tatsaechlichen
Datenschluessel aus HKDF verpackt. Eine Attrappe waere billiger und liesse im
Fehlerfall etwas Unlesbares in der Datenbank stehen; E8 misst deshalb auch den
Rundlauf — anmelden, oeffnen, derselbe Inhaltsschluessel.

SIE FASST DIE HUELLE DES ADMIN-KONTOS AN und legt sie im `finally` zurueck
(`pat_wrap_pw`, `pat_key_check`, `kdf_iter`), mit byteweisem Vergleich.
Gegen eine Testinstallation fahren, nicht gegen den Produktivserver; dieselbe
Ansage wie bei `tools/wartungsprobe/`.

Voraussetzung: eine laufende lokale Installation mit Referenzbestand
(`tools/referenzdatensatz/einspielen/lokal_einrichten.sh`) und ein
Server-Anteil in `config.php` — sonst meldet E1 sofort, dass nichts
ausgeliefert wird.

Aufruf:
  python3 tools/anteilprobe/endpunkt.py [basisadresse]
  (Vorgabe: https://127.0.0.1:8443)

Rueckgabewert: 0 = alle Erwartungen erfuellt, 1 = mindestens eine nicht.
"""
from __future__ import annotations

import hashlib
import json
import os
import pathlib
import subprocess
import sys

HIER = pathlib.Path(__file__).resolve().parent
WURZEL = HIER.parent.parent
sys.path.insert(0, str(WURZEL / "tools" / "referenzdatensatz" / "einspielen"))
sys.path.insert(0, str(WURZEL / "tools" / "referenzdatensatz" / "generator"))

import krypto                                   # noqa: E402
from sitzung import Sitzung                     # noqa: E402

BASIS = sys.argv[1] if len(sys.argv) > 1 else "https://127.0.0.1:8443"
ADMIN, ADMIN_PW = "admin@gen-em.org", "pruefstandzugang2026"
DEMO, DEMO_PW = "demo@gen-em.org", "nadokudemo0815"
DB = str(WURZEL / "server" / "db.php")

zahl = {"ok": 0, "offen": 0}


def pruefe(was: str, ist, soll) -> None:
    gleich = ist == soll
    zahl["ok" if gleich else "offen"] += 1
    zusatz = "" if gleich else f"  erwartet: {soll!r}"
    print(f"  {'ok  ' if gleich else 'FEHL'} {was:<56} {ist!r}{zusatz}")


def teil(name: str) -> None:
    print(f"\n{name}")


def antwort(r):
    """Die JSON-Antwort — oder ein lesbarer Befund statt eines Absturzes.

    WARUM DAS HIER STEHT. Am 14.09.2026 ist dieser Lauf EINMAL beim Lesen
    der Antwort gescheitert: Sie war kein JSON. Vier Laeufe danach
    waren gruen, im Serverprotokoll standen 0 Antworten mit 5xx und keine
    PHP-Meldung — die Ursache liess sich nicht feststellen, weil der
    ROHTEXT nicht mehr da war. Eine Ausnahme, die die interessante Zeile
    wegwirft, ist die teuerste Art zu scheitern.

    Seither faengt die Probe das ab und zeigt Statuscode, Kopfzeile und die
    ersten 300 Zeichen. Der naechste Fehlschlag ist damit ein Befund mit
    Text statt eines Ratespiels.
    """
    try:
        return r.json()
    except ValueError:
        print(f"  !! Antwort ist kein JSON — Status {r.status_code}, "
              f"Content-Type {r.headers.get('Content-Type')!r}")
        print(f"  !! Rohtext: {r.text[:300]!r}")
        return {}


def php(code: str) -> str:
    """Ein Stueck PHP gegen dieselbe Installation laufen lassen.

    SO KOMMEN DIE ZUGANGSDATEN NICHT EIN ZWEITES MAL VOR. Ein eigener
    Datenbankzugang in dieser Datei waere ein zweiter Ort, an dem das Passwort
    der Installation steht — und einer, der beim naechsten Wechsel vergessen
    wird. `db.php` weiss es ohnehin.
    """
    return subprocess.run(["php", "-r", 'require ' + json.dumps(DB) + ';' + code],
                          capture_output=True, text=True, check=True).stdout


def feld(spalte: str, uid: str) -> str:
    return php(f'$st = db()->prepare("SELECT {spalte} FROM users WHERE id = ?");'
               f'$st->execute([{uid}]); echo (string)$st->fetchColumn();')


print(f"Anteilprobe, Teil E — {BASIS}")

# DIE VORAUSSETZUNG WIRD HERGESTELLT, NICHT VORAUSGESETZT.
#
# E4 bis E6 messen die Umstellung einer `edk1:`-Huelle. Steht das Konto schon
# auf `edka1:` — und das tut es nach jedem Lauf dieser Datei, nach dem
# Umstellungslauf und nach jedem Anmelden im Browser —, dann antwortet der
# Endpunkt mit 'nicht_noetig', und der Lauf meldet 11 offene Erwartungen, die
# in Wahrheit nur eine falsche Ausgangslage sind. Genau so gelesen in S10/AP3:
# 22 von 33, ohne dass an der Anwendung etwas fehlte (F-S10-AP3-03).
#
# `huelle_stellen.py` kann das rueckwaerts; hier wird es aufgerufen statt
# nachgebaut, damit es EINE Umsetzung bleibt.
_stellen = subprocess.run(
    [sys.executable, str(HIER / "huelle_stellen.py"), ADMIN, ADMIN_PW, "edk1"],
    capture_output=True, text=True, env={**os.environ, "BASIS": BASIS})
print("  Ausgangslage: " + (_stellen.stdout.strip().splitlines() or ["(stumm)"])[-1])
if _stellen.returncode != 0:
    print("  !! Die Ausgangslage liess sich nicht herstellen — der Lauf misst "
          "ab E4 etwas anderes als gemeint.")
    print("  !! " + (_stellen.stderr or "").strip()[:400])

s = Sitzung(BASIS).anmelden(ADMIN, ADMIN_PW)
uid = php('$st = db()->prepare("SELECT id FROM users WHERE email = ?");'
          '$st->execute(["admin@gen-em.org"]); echo (string)$st->fetchColumn();').strip()
wrap_vorher = feld("pat_wrap_pw", uid)
chk_vorher = feld("pat_key_check", uid)
iter_vorher = feld("kdf_iter", uid).strip()
salz = feld("kdf_salt", uid).strip()

print(f"  Konto {uid}, Rundenzahl {iter_vorher}, "
      f"Huelle {wrap_vorher[:14]}…, ANTEIL_STAND {s.anteil_stand}")

try:
    teil("E1. Die Seite liefert Anteil und Kennung")
    pruefe("ANTEIL_STAND ist 'bereit'", s.anteil_stand, "bereit")
    pruefe("ANTEIL_KENNUNG sind 8 Hexzeichen",
           bool(s.anteil_kennung) and len(s.anteil_kennung) == 8, True)
    pruefe("KONTO_ANTEILE nennt genau diese eine Kennung",
           sorted(s.anteile.keys()), [s.anteil_kennung])
    pruefe("der Anteil sind 64 Hexzeichen", len(s.anteile[s.anteil_kennung]), 64)
    pruefe("der Inhaltsschluessel liess sich entpacken",
           bool(s.inhaltsschluessel) and len(s.inhaltsschluessel) == 64, True)

    ck = s.inhaltsschluessel
    kennung = s.anteil_kennung
    alt_token = krypto.ableiten(ADMIN_PW, salz, int(iter_vorher))[1]
    chk = hashlib.sha256(("edk-ckchk:" + ck).encode()).hexdigest()[:32]

    dk_neu = krypto.datenschluessel(s.haelfte, f"edka1:{kennung}:", s.anteile)
    huelle_neu = krypto.huelle_bauen(ck, dk_neu, kennung)

    def ruf(koerper: dict):
        return s.json_post("api/kdf_upgrade.php", koerper)

    def koerper(wrap: str | None, token: str = None, it: int = None) -> dict:
        k = {"alt_token": token or alt_token, "neu_token": token or alt_token,
             "neu_iter": it or int(iter_vorher), "key_check": chk}
        if wrap is not None:
            k["wrap_pw"] = wrap
        return k

    teil("E2. Eine Huelle mit FREMDER Anteil-Kennung wird abgewiesen")
    # Genau der Fall „Rueckstellung auf den alten Anteil". Der Server kann die
    # Huelle nicht oeffnen; was er pruefen kann, ist das Praefix — und das
    # genuegt, weil der Fehler im Praefix steckt.
    r = ruf(koerper(krypto.huelle_bauen(ck, dk_neu, "deadbeef")))
    pruefe("HTTP-Status", r.status_code, 400)
    pruefe("Fehlerkennung", antwort(r).get("error"), "anteil_kennung")
    pruefe("die Huelle in der Datenbank ist unveraendert",
           feld("pat_wrap_pw", uid), wrap_vorher)

    teil("E3. Eine Huelle OHNE Anteil wird abgewiesen, solange einer ausgeliefert wird")
    r = ruf(koerper(krypto.huelle_bauen(ck, s.haelfte, None)))
    pruefe("HTTP-Status", r.status_code, 400)
    pruefe("Fehlerkennung", antwort(r).get("error"), "anteil_kennung")

    teil("E4. Ein falsches alt_token wird abgewiesen (der Nachweis)")
    r = ruf(koerper(huelle_neu, token="0" * 64))
    pruefe("HTTP-Status", r.status_code, 403)
    pruefe("Fehlerkennung", antwort(r).get("error"), "nachweis")

    teil("E5. Eine abweichende Pruefsumme wird abgewiesen")
    k = koerper(huelle_neu)
    k["key_check"] = "f" * 32
    r = ruf(k)
    pruefe("HTTP-Status", r.status_code, 409)
    pruefe("Fehlerkennung", antwort(r).get("error"), "key_check_abweichung")
    pruefe("die Huelle ist unveraendert", feld("pat_wrap_pw", uid), wrap_vorher)

    teil("E6. Die Umstellung bei GLEICHER Rundenzahl gelingt")
    # Die eigentliche Neuerung: Bis S10 stand in kdf_upgrade.php `<=`, und
    # dieser Aufruf waere mit 'nicht_noetig' abgewiesen worden.
    r = ruf(koerper(huelle_neu))
    pruefe("HTTP-Status", r.status_code, 200)
    pruefe("ok", antwort(r).get("ok"), True)
    pruefe("die Antwort nennt die neue Kennung", antwort(r).get("huelle"), kennung)
    pruefe("die Huelle traegt jetzt das edka1-Praefix",
           feld("pat_wrap_pw", uid).startswith(f"edka1:{kennung}:"), True)
    pruefe("die Pruefsumme ist unveraendert", feld("pat_key_check", uid), chk_vorher)
    pruefe("die Rundenzahl ist unveraendert", feld("kdf_iter", uid).strip(), iter_vorher)

    teil("E7. Derselbe Aufruf ein zweites Mal hat nichts mehr zu tun")
    r = ruf(koerper(huelle_neu))
    pruefe("HTTP-Status", r.status_code, 400)
    pruefe("Fehlerkennung", antwort(r).get("error"), "nicht_noetig")

    teil("E8. Der Rundlauf — die neue Huelle laesst sich wieder oeffnen")
    # Waere der Inhaltsschluessel danach ein anderer, waeren alle Daten des
    # Kontos verloren. Das ist die Zahl, auf die es ankommt.
    s2 = Sitzung(BASIS).anmelden(ADMIN, ADMIN_PW)
    pruefe("die Seite liefert jetzt eine edka1-Huelle",
           (s2.wrap or "").startswith("edka1:"), True)
    pruefe("der Inhaltsschluessel ist derselbe", s2.inhaltsschluessel, ck)

    teil("E9. Ohne Formular-Token kommt nichts durch")
    r = s.s.post(f"{BASIS}/api/kdf_upgrade.php", json=koerper(huelle_neu),
                 timeout=60, headers={"Content-Type": "application/json"})
    pruefe("HTTP-Status", r.status_code, 403)
    pruefe("Fehlerkennung", antwort(r).get("error"), "csrf")

    teil("E10. Das Demo-Konto wird uebersprungen und bleibt auf edk1:")
    d = Sitzung(BASIS).anmelden(DEMO, DEMO_PW)
    pruefe("ANTEIL_STAND ist 'demo'", d.anteil_stand, "demo")
    # AM ROHTEXT DER SEITE gemessen, nicht am Ergebnis von `sitzung.py`: Jenes
    # macht aus `null` ein leeres Feld, damit es damit rechnen kann — und ein
    # leeres Feld ist etwas anderes als „dieses Konto bekommt nie einen".
    # E-S10-06 verlangt ausdruecklich `null`.
    import re as _re
    roh = _re.search(r"const\s+KONTO_ANTEILE\s*=\s*(.+?);\s*$",
                     d.get("index.php").text, _re.M)
    pruefe("KONTO_ANTEILE steht als null in der Seite",
           roh.group(1).strip() if roh else "(fehlt)", "null")
    pruefe("ANTEIL_KENNUNG ist null", d.anteil_kennung, None)
    pruefe("die Demo-Huelle traegt kein edka1-Praefix",
           (d.wrap or "").startswith("edka1:"), False)
    r = d.json_post("api/kdf_upgrade.php",
                    {"alt_token": "0" * 64, "neu_token": "0" * 64,
                     "neu_iter": 600000})
    pruefe("HTTP-Status", r.status_code, 200)
    pruefe("der Endpunkt meldet 'uebersprungen'",
           antwort(r).get("uebersprungen"), "demo")

finally:
    php('$st = db()->prepare("UPDATE users SET pat_wrap_pw = ?, pat_key_check = ?,'
        ' kdf_iter = ? WHERE id = ?"); $st->execute(['
        + json.dumps(wrap_vorher) + ', ' + json.dumps(chk_vorher) + ', '
        + iter_vorher + ', ' + uid + ']);')
    zurueck = feld("pat_wrap_pw", uid)
    print(f"\nHuelle zurueckgelegt: "
          f"{'byte-gleich' if zurueck == wrap_vorher else 'ABWEICHUNG'}")

print(f"\nErgebnis: {zahl['ok']} von {zahl['ok'] + zahl['offen']} "
      f"Erwartungen erfuellt, {zahl['offen']} offen.")
sys.exit(0 if zahl["offen"] == 0 else 1)
