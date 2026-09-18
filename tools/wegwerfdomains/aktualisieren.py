#!/usr/bin/env python3
"""Zieht `server/wegwerfdomains.txt` von der Quelle nach (P5b/AP3, E-P5b-23).

WOFUER. Die Registrierung weist Wegwerfadressen ab. Die Liste dafuer liegt ALS
DATEI im Repositorium und wird mit ausgeliefert — sie wird **nie zur Laufzeit
geholt**, weil die Zusage „keine fremde Quelle zur Laufzeit" (R36, CLAUDE.md 4)
keine Ausnahme kennt, auch nicht fuer eine Textdatei. Der Preis dafuer ist
dieser Handgriff: Die Datei altert genau so lange, wie niemand ihn macht.

UND SIE ALTERT IN EINE RICHTUNG. Neue Wegwerfanbieter kommen dazu, die Datei
bleibt stehen. Auffallen kann das niemandem — eine durchgelassene
Registrierung sieht aus wie eine richtige, und die Seite antwortet ohnehin auf
jede gleich (E-P5b-13). Deshalb steht der Lauf im Auslieferungs-Runbook
(`docs/Technik.md`, Abschnitt 7) und nicht im guten Willen. Backlog Nr. 230.

    python3 tools/wegwerfdomains/aktualisieren.py            # holen und pruefen
    python3 tools/wegwerfdomains/aktualisieren.py --schreiben  # auch schreiben
    python3 tools/wegwerfdomains/aktualisieren.py --pruefen   # nur messen, nichts holen

ZWEI ZAHLEN, NICHT EINE. Der Unterschied zur alten Datei ist die harmlose
Zahl. Die gefaehrliche ist die zweite: **Landet eine Klinik- oder
Providerdomain auf der Liste**, bekommt die Aerztin dahinter dieselbe neutrale
Antwort wie alle anderen und erfaehrt NIE, woran es lag. Deshalb misst dieses
Werkzeug bei jedem Lauf beide Richtungen und schreibt nicht, wenn die zweite
Messung anschlaegt.

HERKUNFT. `disposable-email-domains/disposable-email-domains`, Datei
`disposable_email_blocklist.conf`, **CC0 1.0** (die Lizenzdatei des Projekts
heisst `LICENSE.txt`, nicht `LICENSE` — ein `curl` auf `LICENSE` gibt 404 und
ist kein Befund). Verworfen wurden `7c/fakefilter` (BSD-3, mit Kommentarzeilen
und Doppelungen) und `FGRibreau/mailchecker` (MIT, sechsmal so gross und damit
sechsmal so viel Risiko fuer echte Domains). Der Eintrag steht in
`docs/Lizenzen.md`.
"""
from __future__ import annotations

import argparse
import pathlib
import subprocess
import sys

WURZEL = pathlib.Path(__file__).resolve().parents[2]
ZIEL = WURZEL / "server" / "wegwerfdomains.txt"
QUELLE = (
    "https://raw.githubusercontent.com/disposable-email-domains/"
    "disposable-email-domains/main/disposable_email_blocklist.conf"
)

# ---------------------------------------------------------------------------
# DIE ZWEI MESSUNGEN
#
# Beide Listen sind klein und stehen hier im Quelltext, nicht in einer Datei
# daneben: Eine Probe, die man bearbeiten kann, ohne den Code zu lesen, wird
# irgendwann bearbeitet, bis sie besteht.
# ---------------------------------------------------------------------------

# Muss DRIN sein. Acht bekannte Wegwerfanbieter, vier davon deutschsprachig —
# eine Liste, die nur englische Anbieter faengt, faengt fuer dieses Projekt zu
# wenig.
WEGWERF = [
    "trash-mail.com", "wegwerfemail.de", "spambog.de", "byom.de",
    "mailinator.com", "10minutemail.com", "guerrillamail.com", "yopmail.com",
]

# Darf NICHT drin sein. Fuenf Massenprovider und fuenf Einrichtungen aus dem
# Gesundheitswesen — die Adressen, mit denen sich die Zielgruppe tatsaechlich
# anmeldet.
ECHT = [
    "gmail.com", "web.de", "gmx.de", "posteo.de", "mailbox.org",
    "charite.de", "lmu-klinikum.de", "ukaachen.de", "klinikum-muenchen.de",
    "drk.de",
]


def holen() -> str:
    """Die Datei von der Quelle holen — mit `curl`, nicht mit `urllib`.

    Die Arbeitsumgebung schickt HTTPS ueber einen Proxy mit eigenem CA-Bundel;
    `curl` nimmt beides aus der Umgebung, `urllib` nicht ohne Zutun.
    """
    lauf = subprocess.run(
        ["curl", "-sS", "--fail", "--max-time", "60", QUELLE],
        capture_output=True, text=True,
    )
    if lauf.returncode != 0:
        raise SystemExit(f"Abruf gescheitert ({lauf.returncode}): {lauf.stderr.strip()}")
    return lauf.stdout


def zerlegen(text: str) -> list[str]:
    return [z.strip() for z in text.splitlines() if z.strip()]


def form_pruefen(zeilen: list[str]) -> list[str]:
    """Was die Datei sein muss, damit `registrieren.php` sie einfach lesen darf.

    Die Leseseite macht KEINE Normalisierung ausser Kleinschreibung — sie laeuft
    bei jeder Registrierung. Alles, was hier nicht abgefangen wird, ist dort
    ein stiller Fehltreffer.
    """
    mangel = []
    gross = [z for z in zeilen if z != z.lower()]
    if gross:
        mangel.append(f"{len(gross)} Zeilen mit Grossbuchstaben (z. B. {gross[0]})")
    komm = [z for z in zeilen if z.startswith("#")]
    if komm:
        mangel.append(f"{len(komm)} Kommentarzeilen (z. B. {komm[0]})")
    doppelt = len(zeilen) - len(set(zeilen))
    if doppelt:
        mangel.append(f"{doppelt} doppelte Eintraege")
    ohne_punkt = [z for z in zeilen if "." not in z]
    if ohne_punkt:
        mangel.append(f"{len(ohne_punkt)} Zeilen ohne Punkt (z. B. {ohne_punkt[0]})")
    return mangel


def messen(zeilen: list[str]) -> tuple[list[str], list[str]]:
    menge = set(zeilen)
    fehlend = [d for d in WEGWERF if d not in menge]
    falsch = [d for d in ECHT if d in menge]
    return fehlend, falsch


def main() -> int:
    p = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    p.add_argument("--schreiben", action="store_true",
                   help="die Zieldatei ersetzen (ohne dies wird nur gemessen)")
    p.add_argument("--pruefen", action="store_true",
                   help="nur die vorhandene Datei messen, nichts holen")
    a = p.parse_args()

    alt = zerlegen(ZIEL.read_text(encoding="utf-8")) if ZIEL.exists() else []

    if a.pruefen:
        neu = alt
        print(f"Gemessen wird die vorhandene Datei: {ZIEL.relative_to(WURZEL)}")
    else:
        print(f"Hole {QUELLE}")
        neu = zerlegen(holen())

    print(f"\nAlt: {len(alt):5d} Domains   Neu: {len(neu):5d} Domains")
    if not a.pruefen and alt:
        dazu = sorted(set(neu) - set(alt))
        weg = sorted(set(alt) - set(neu))
        print(f"     +{len(dazu)} dazu, -{len(weg)} weg")
        for d in dazu[:10]:
            print(f"       + {d}")
        if len(dazu) > 10:
            print(f"       … und {len(dazu) - 10} weitere")
        for d in weg[:10]:
            print(f"       - {d}")
        if len(weg) > 10:
            print(f"       … und {len(weg) - 10} weitere")

    mangel = form_pruefen(neu)
    fehlend, falsch = messen(neu)

    print(f"\nForm:             {'in Ordnung' if not mangel else 'MANGEL'}")
    for m in mangel:
        print(f"  ! {m}")
    print(f"Wegwerfanbieter:  {len(WEGWERF) - len(fehlend)} von {len(WEGWERF)} enthalten")
    for d in fehlend:
        print(f"  ! fehlt: {d}")
    print(f"Echte Domains:    {len(falsch)} von {len(ECHT)} faelschlich getroffen")
    for d in falsch:
        print(f"  ! FAELSCHLICH GETROFFEN: {d}")

    # DIE ZWEITE MESSUNG IST DAS TOR. Eine fehlende Wegwerfdomain laesst eine
    # Registrierung zu viel durch — aergerlich. Eine getroffene Klinikdomain
    # sperrt eine Aerztin aus, ohne dass sie erfaehrt, warum. Das eine ist ein
    # Mangel, das andere ein Schaden.
    if falsch:
        print("\nNICHT GESCHRIEBEN — eine echte Domain steht auf der Liste.")
        print("Das ist kein Grund, die Liste zu kuerzen, sondern einer, die")
        print("Quelle zu wechseln oder den Eintrag dort zu melden.")
        return 1
    if mangel:
        print("\nNICHT GESCHRIEBEN — die Form stimmt nicht.")
        return 1

    if a.schreiben and not a.pruefen:
        ZIEL.write_text("\n".join(neu) + "\n", encoding="utf-8")
        print(f"\nGeschrieben: {ZIEL.relative_to(WURZEL)} ({len(neu)} Domains)")
        print("Nicht vergessen: Zahl und Datum in `docs/Lizenzen.md` nachziehen.")
    elif not a.pruefen:
        print("\nNichts geschrieben (ohne --schreiben wird nur gemessen).")

    return 0


if __name__ == "__main__":
    sys.exit(main())
