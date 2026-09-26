#!/usr/bin/env python3
"""Ein Pruefkonto anlegen oder loeschen — ueber die Wege des Kreislaufs.

    python3 tools/referenzdatensatz/vergleich/pruefkonto.py anlegen umlauf-freigabe@gen-em.org
    python3 tools/referenzdatensatz/vergleich/pruefkonto.py loeschen umlauf-freigabe@gen-em.org

KEIN ZWEITER ANLEGEWEG. `konto_anlegen()` und `konto_loeschen()` stehen in
`kreislauf.py`; dieses Skript macht sie fuer Proben aufrufbar, die ihr
eigenes Konto brauchen, statt auf das eines Kreislaufs zu warten (E-RP-02).
Geloescht wird nur, was mit `umlauf-` beginnt — der Riegel sitzt in
`konto_loeschen()`, nicht hier.

`anlegen` legt FRISCH an: Ein vorhandenes Konto gleichen Namens wird vorher
geloescht, damit keine Probe in einen Bestand vom letzten Lauf misst.

Rueckgabewert 0 = erledigt · 2 = nicht gelaufen (Anlage nicht erreichbar,
Konto ohne Praefix).

Anlass: F-RP-07 — die Freigabeprobe lief vor dem Kreislauf, dessen Konto
sie brauchte, und meldete „Zielkonto nicht gefunden".
"""
import argparse
import pathlib
import sys

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent))
import kreislauf  # noqa: E402


def main() -> int:
    p = argparse.ArgumentParser(description="Pruefkonto anlegen oder loeschen")
    p.add_argument("befehl", choices=["anlegen", "loeschen"])
    p.add_argument("konto")
    p.add_argument("--passwort", default=kreislauf.UMLAUF_PASSWORT)
    p.add_argument("--basis", default="https://127.0.0.1:8443")
    p.add_argument("--admin-email", default=kreislauf.ADMIN_VORGABE[0])
    p.add_argument("--admin-passwort", default=kreislauf.ADMIN_VORGABE[1])
    # Das Geheimnis des Zweitfaktors (P5c/AP5, E-P5c-43) -- derselbe Schalter
    # wie in `kreislauf.py`, mit derselben Bedeutung: leer heisst
    # `NADOKU_TOTP`, sonst das der Sandbox.
    p.add_argument("--admin-totp", default="",
                   help="Geheimnis des Zweitfaktors des Admin-Kontos (Base32)")
    # Seit Konzept RW (RW-03): die Rolle des neuen Kontos und wohin der
    # Wiederherstellungsschluessel geschrieben wird, den `passwort_setzen.mjs`
    # von der Seite liest. Die Rueckwegprobe braucht beides.
    p.add_argument("--rolle", default="user", choices=["user", "betreiberin"],
                   help="Rolle des angelegten Kontos (Vorgabe user)")
    p.add_argument("--rc-datei", default="",
                   help="JSON-Datei fuer den Wiederherstellungsschluessel (Feld recovery_code)")
    a = p.parse_args()
    admin = (a.admin_email, a.admin_passwort, a.admin_totp)
    try:
        weg = kreislauf.konto_loeschen(a.basis, admin, a.konto)
        if a.befehl == "loeschen":
            kreislauf.melde(f"  Konto {a.konto} {'geloescht' if weg else 'bestand nicht'}.")
            return 0
        kreislauf.konto_anlegen(a.basis, admin, a.konto, a.passwort,
                                rolle=a.rolle, rc_datei=a.rc_datei or None)
    except Exception as e:  # noqa: BLE001 — die Meldung ist der Befund
        print(f"Pruefkonto {a.befehl} gescheitert: {e}", file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    sys.exit(main())
