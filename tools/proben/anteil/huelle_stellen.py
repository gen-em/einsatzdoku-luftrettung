"""Die Schluesselhuelle eines Kontos auf eine bestimmte Fassung stellen (S10).

WOZU. Der Umstellungslauf (`umstellungslauf.mjs`) misst, ob der Browser eine
`edk1:`-Huelle beim Anmelden von selbst auf `edka1:` umstellt. Das laesst sich
GENAU EINMAL messen — danach ist das Konto umgestellt, und der zweite Lauf
misst nur noch, dass nichts mehr passiert. Ein Pruefmittel, das beim zweiten
Aufruf etwas anderes misst als beim ersten, ist keines.

Diese Datei stellt die Voraussetzung wieder her: Sie oeffnet die vorhandene
Huelle mit dem Schluessel, den ihr Praefix verlangt, und baut sie mit dem
gewuenschten Praefix neu. Der INHALTSSCHLUESSEL bleibt dabei derselbe — sie
verschluesselt nichts um, sie verpackt anders. `pat_key_check` bleibt deshalb
gueltig, und kein Datensatz wird angefasst.

SIE BRAUCHT DAS PASSWORT DES KONTOS und geht den regulaeren Anmeldeweg
(`sitzung.py`) — kein Sonderzugang, kein Schluessel aus der Datenbank. Was
sie kann, kann auch der Browser; sie ist nur schneller darin, es rueckwaerts
zu tun.

**Gegen eine Testinstallation fahren, nicht gegen den Produktivserver.**

Aufruf:
  python3 tools/proben/anteil/huelle_stellen.py <konto> <passwort> edk1|edka1
  python3 tools/proben/anteil/huelle_stellen.py umlauf-csv@gen-em.org … edk1
  (Basisadresse ueber die Umgebungsvariable BASIS, Vorgabe https://127.0.0.1:8443)

Rueckgabewert: 0 = die Huelle steht auf der gewuenschten Fassung, 1 = nicht.
"""
from __future__ import annotations

import json
import os
import pathlib
import subprocess
import sys

HIER = pathlib.Path(__file__).resolve().parent
WURZEL = HIER.parent.parent.parent
sys.path.insert(0, str(WURZEL / "tools" / "referenzdatensatz" / "einspielen"))
sys.path.insert(0, str(WURZEL / "tools" / "referenzdatensatz" / "generator"))

import krypto                                   # noqa: E402
from sitzung import Sitzung                     # noqa: E402

DB = str(WURZEL / "server" / "db.php")


def php(code: str) -> str:
    return subprocess.run(["php", "-r", 'require ' + json.dumps(DB) + ';' + code],
                          capture_output=True, text=True, check=True).stdout


def main() -> int:
    if len(sys.argv) < 4:
        print(__doc__)
        return 2
    konto, passwort, fassung = sys.argv[1], sys.argv[2], sys.argv[3]
    if fassung not in ("edk1", "edka1"):
        print(f"Unbekannte Fassung {fassung!r} — erlaubt: edk1, edka1")
        return 2
    basis = os.environ.get("BASIS", "https://127.0.0.1:8443")

    s = Sitzung(basis).anmelden(konto, passwort)
    if not s.inhaltsschluessel:
        print("Dieses Konto hat keine Schluesselhuelle — nichts zu stellen.")
        return 1

    ist = "edka1" if krypto.huelle_kennung(s.wrap) else "edk1"
    if ist == fassung:
        print(f"{konto}: steht schon auf {fassung}: — nichts zu tun.")
        return 0

    if fassung == "edka1":
        if not s.anteil_kennung:
            print(f"{konto}: Diese Installation liefert keinen Server-Anteil aus "
                  f"(ANTEIL_STAND {s.anteil_stand}) — edka1: ist nicht baubar.")
            return 1
        kennung = s.anteil_kennung
        dk = krypto.datenschluessel(s.haelfte, f"edka1:{kennung}:", s.anteile)
    else:
        kennung = None
        dk = s.haelfte

    neu = krypto.huelle_bauen(s.inhaltsschluessel, dk, kennung)

    # GEGENPROBE VOR DEM SCHREIBEN: Die neue Huelle muss denselben
    # Inhaltsschluessel hergeben. Eine Huelle, die sich nicht oeffnen laesst,
    # kostet das Konto seine Daten — und zwar erst beim naechsten Anmelden.
    if krypto.entschluesseln(neu, dk) != s.inhaltsschluessel:
        print("Die neu gebaute Huelle gibt einen ANDEREN Inhaltsschluessel her. "
              "Es wurde nichts geschrieben.")
        return 1

    php('$st = db()->prepare("UPDATE users SET pat_wrap_pw = ? WHERE email = ?");'
        '$st->execute([' + json.dumps(neu) + ', ' + json.dumps(konto) + ']);')
    jetzt = php('$st = db()->prepare("SELECT LEFT(pat_wrap_pw, 14) FROM users '
                'WHERE email = ?"); $st->execute([' + json.dumps(konto) + ']);'
                'echo (string)$st->fetchColumn();')
    print(f"{konto}: {ist}: -> {fassung}:  (jetzt {jetzt.strip()}…)")
    return 0 if jetzt.startswith(fassung + ":") else 1


sys.exit(main())
