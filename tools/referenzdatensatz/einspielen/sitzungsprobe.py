#!/usr/bin/env python3
"""Sitzungsprobe — oeffnet `sitzung.py` BEIDE Huellenfassungen? (S10/AP5)

DIE FRAGE. Seit S10 traegt eine Schluesselhuelle entweder das alte Praefix
`edk1:` (Datenschluessel = PBKDF2-Haelfte) oder das neue `edka1:<kennung>:`
(Datenschluessel = HKDF aus Haelfte und Konto-Anteil). Der Bestand fuehrt beide
gleichzeitig und wird das noch eine Weile tun: Ein Konto stellt erst beim
ersten Anmelden IM BROWSER um, und `sitzung.py` stellt ausdruecklich nicht um
(E-S10-15) — sie liest, was dasteht.

Damit haengt der ganze Pruefstand daran, dass `sitzung.py` beide Fassungen
oeffnet. Tut sie es nicht, faellt das nicht als Fehler auf, sondern als etwas
viel Unangenehmeres: `einspielen.py`, die Kreislaeufe und jede Probe, die sich
anmeldet, laufen weiter und lesen leere oder falsche Angaben.

Genau das war in AP1 die Begruendung, `sitzung.py` und `krypto.py` aus AP5
vorzuziehen (E-S10-U-03, „sitzung.py waere eine Mine geworden"). Diese Probe
ist die Gegenrechnung dazu: Sie misst nach, dass die Mine entschaerft ist —
an ZWEI echten Konten des Referenzbestands, nicht an einer Attrappe.

WIE GEMESSEN WIRD — und warum „ein Schluessel kam heraus" nicht reicht.
`entpacken()` liefert bei einem falschen Datenschluessel keinen falschen
Schluessel, sondern eine Ausnahme (AES-GCM hat ein Pruefetikett). Ein Lauf,
der nur auf „keine Ausnahme" prueft, waere trotzdem zu schwach: Er saehe nicht,
ob der herausgekommene Inhaltsschluessel der des KONTOS ist. Deshalb wird er
gegen `users.pat_key_check` gehalten — dieselbe Rechnung wie
`EdCrypto.contentKeyCheck()`: die ersten 32 Hexzeichen von
SHA-256("edk-ckchk:" + Inhaltsschluessel).

DIESE PROBE SCHREIBT NICHTS. Sie meldet sich an, liest und rechnet. Die
Anmeldung selbst ist der einzige Seiteneffekt (eine Zeile in `sessions`, und
die Mengenbremse zaehlt zwei Anmeldungen).

AUFRUF

    python3 tools/referenzdatensatz/einspielen/sitzungsprobe.py
    python3 tools/referenzdatensatz/einspielen/sitzungsprobe.py https://127.0.0.1:8443

Die Konten kommen aus den Umgebungsvariablen oder aus den Vorgaben unten:

    SITZUNGSPROBE_ALT="konto:passwort"     erwartet `edk1:`
    SITZUNGSPROBE_NEU="konto:passwort"     erwartet `edka1:`

Erwartet: **2 von 2**, Rueckgabe 0.

WENN DIE PROBE MELDET „beide Konten tragen dasselbe Praefix": Das ist kein
Fehler der Anwendung, sondern eine fehlende Voraussetzung — der Bestand fuehrt
dann keine zwei Fassungen mehr. Herzustellen mit

    python3 tools/anteilprobe/huelle_stellen.py <konto> <passwort> edk1

Die Probe sagt es als eigenen Befund und nicht als gruene Zahl; eine Zahl, die
zweimal dasselbe gemessen hat, ist keine 2 von 2.
"""
import hashlib
import os
import subprocess
import sys
from pathlib import Path

HIER = Path(__file__).resolve().parent
sys.path.insert(0, str(HIER))
sys.path.insert(0, str(HIER.parent / "generator"))

import sitzung as sitzungsmodul  # noqa: E402

BASIS = sys.argv[1] if len(sys.argv) > 1 else "https://127.0.0.1:8443"
SERVER = HIER.parent.parent.parent / "server"


def konto(name: str, vorgabe: str) -> tuple[str, str]:
    roh = os.environ.get(name, vorgabe)
    email, _, passwort = roh.partition(":")
    return email, passwort


ALT = konto("SITZUNGSPROBE_ALT", "admin@gen-em.org:pruefstandzugang2026")
NEU = konto("SITZUNGSPROBE_NEU", "umlauf-csv@gen-em.org:umlaufpruefung2026")

gesamt = 0
offen = 0


def pruefe(ok: bool, was: str, wert: str = "") -> None:
    global gesamt, offen
    gesamt += 1
    if not ok:
        offen += 1
    print(f"  [{'ok ' if ok else 'FEHL'}] {was:<62} {wert}")


def pat_key_check(email: str) -> str | None:
    """`users.pat_key_check` aus der Datenbank — der Vergleichswert."""
    ruf = subprocess.run(
        ["php", "-r",
         'require $argv[1]."/db.php";'
         '$s=db()->prepare("SELECT pat_key_check FROM users WHERE email=?");'
         '$s->execute([$argv[2]]); echo (string)$s->fetchColumn();',
         str(SERVER), email],
        capture_output=True, text=True, check=True)
    wert = ruf.stdout.strip()
    return wert or None


def gerechnet(ck_hex: str) -> str:
    return hashlib.sha256(("edk-ckchk:" + ck_hex).encode("utf-8")).hexdigest()[:32]


def messen(email: str, passwort: str, erwartet: str) -> str | None:
    """Anmelden, Huelle oeffnen, Pruefsumme vergleichen. Liefert das Praefix."""
    try:
        s = sitzungsmodul.Sitzung(BASIS).anmelden(email, passwort)
    except Exception as e:  # noqa: BLE001 — der Grund gehoert in die Zeile
        pruefe(False, f"{erwartet}-Konto {email}: Anmeldung", str(e)[:60])
        return None

    huelle = s.wrap or ""
    praefix = huelle.split(":")[0] + ":" if ":" in huelle else "(keine Huelle)"
    ck = s.inhaltsschluessel
    soll = pat_key_check(email)

    pruefe(
        praefix.startswith(erwartet) and bool(ck) and soll is not None
        and gerechnet(ck) == soll,
        f"{erwartet[:-1]}-Konto: Huelle geoeffnet, Pruefsumme passt",
        f"{email}, Praefix {praefix}, Anteil-Stand {s.anteil_stand}, "
        f"pat_key_check {'gleich' if ck and soll and gerechnet(ck) == soll else 'ABWEICHEND'}")
    return praefix


print(f"Sitzungsprobe gegen {BASIS}")
print(f"  Altfassung  {ALT[0]}")
print(f"  Neufassung  {NEU[0]}\n")

p_alt = messen(ALT[0], ALT[1], "edk1:")
p_neu = messen(NEU[0], NEU[1], "edka1:")

# DIE GEGENPROBE ZUR ZAHL. Zwei gruene Haken sagen nichts, wenn beide Konten
# dieselbe Fassung tragen — dann ist eine Fassung schlicht nicht gemessen
# worden. Das ist genau der Fehler, den CLAUDE.md 6 meint: „Eine gruene Zahl
# ist erst dann ein Beleg, wenn sie das Gemessene benennt."
if p_alt and p_neu and p_alt == p_neu:
    print(f"\n  ACHTUNG: Beide Konten tragen {p_alt} — es wurde nur EINE Fassung "
          f"gemessen.\n  Voraussetzung herstellen mit tools/anteilprobe/huelle_stellen.py.")
    offen += 1
    gesamt += 1

print(f"\n  -> {gesamt} Erwartungen, {offen} nicht erfuellt")
sys.exit(0 if offen == 0 else 1)
