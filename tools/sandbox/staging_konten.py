#!/usr/bin/env python3
"""Testkonten auf Staging aufräumen — am Ende jeder Sitzung, die dort prüft.

Anlass: Nr. 564 — auf Staging blieben Testkonten mit erfundenen Adressen
stehen, und jede Mail an sie stand danach als Störung im Reiter System.

WAS ES TUT. Es meldet sich mit dem eigenen BetreiberIn-Konto der
Arbeitsumgebung an (`NADOKU_STAGING_KONTO`, `_PASS`, `_TOTP` —
`docs/Sandbox-Setup.md` 4), liest die Kontenliste (`admin_users.php`, alle
Seiten) und löscht jedes Konto, das nicht auf der Liste der zu behaltenden
steht, über die Kontoseite mit der zweiten Stufe (`kreislauf.konto_loeschen`,
derselbe Weg wie der Kreislauf). Ohne `--wirklich` zeigt es nur an.

WAS ES BEHÄLT. Immer das eigene Konto und `demo@gen-em.org`; dazu die
Adressen aus `NADOKU_STAGING_BEHALTEN` (kommagetrennt). Die Liste steht in
der Umgebung der Arbeitsumgebung und nicht hier, weil dieses Repositorium
öffentlich ist und dort keine echten Adressen stehen (E-KA-65). **Fehlt die
Variable, ist es rot und löscht nichts** — eine leere Liste hieße „alles
außer mir", und darunter stünde das Konto der BetreiberIn.

WAS ES NICHT WEISS. Ob gerade ein Lauf der Kette auf Staging prüft: Die
Konten der Kette (`umlauf-…@example.invalid`, `neue@example.invalid`) legt
der Kreislauf bei Bedarf neu an; mitten in einem Lauf gelöscht, scheitert
er. Deshalb erst fahren, wenn der Auslieferungslauf durch ist (R91).

Aufruf:
    python3 tools/sandbox/staging_konten.py              # zeigt nur an
    python3 tools/sandbox/staging_konten.py --wirklich   # löscht

Rückgabewert: 0 = nichts zu tun oder alles gelöscht, 1 = Fehler.
"""
from __future__ import annotations

import os
import pathlib
import re
import sys

WURZEL = pathlib.Path(__file__).resolve().parents[2]
sys.path.insert(0, str(WURZEL / "tools" / "referenzdatensatz" / "einspielen"))
sys.path.insert(0, str(WURZEL / "tools" / "referenzdatensatz" / "vergleich"))

import sitzung as sitzungsmodul  # noqa: E402
import kreislauf  # noqa: E402

IMMER = ("demo@gen-em.org",)
ADRESSE = re.compile(r"[\w.+-]+@[\w-]+(?:\.[\w-]+)+")
KENNUNG = re.compile(r"admin_user\.php\?id=\d+")


def umgebung(name: str) -> str:
    wert = os.environ.get(name, "").strip()
    if not wert:
        raise SystemExit(f"FEHLER: {name} fehlt in der Umgebung — es wird nichts gelöscht.")
    return wert


def zeilen_adressen(html: str) -> set[str]:
    """Die Adressen der KONTOZEILEN, nicht jede Adresse der Seite: Das
    Einladungsformular trägt einen Platzhalter mit Adresse, und der ist kein
    Konto (gefunden beim ersten Lauf). Die Kennung steht in beiden Formen der
    Liste VOR der Adresse (`kreislauf.konto_loeschen`); also je Kennung die
    erste Adresse bis zur nächsten Kennung."""
    treffer = list(KENNUNG.finditer(html))
    aus: set[str] = set()
    for i, m in enumerate(treffer):
        ende = treffer[i + 1].start() if i + 1 < len(treffer) else len(html)
        a = ADRESSE.search(html, m.end(), ende)
        if a:
            aus.add(a.group(0).lower())
    return aus


def konten_lesen(s) -> set[str]:
    """Alle Adressen der Kontenliste, Seite für Seite (`?s=N`)."""
    alle: set[str] = set()
    seite = 1
    while True:
        html = s.get(f"admin_users.php?s={seite}").text
        neu = zeilen_adressen(html)
        if not neu - alle:
            break
        alle |= neu
        seite += 1
        if seite > 100:
            raise SystemExit("FEHLER: mehr als 100 Seiten Konten — das ist kein Testbestand.")
    return alle


def main() -> int:
    wirklich = "--wirklich" in sys.argv[1:]
    basis = umgebung("NADOKU_STAGING_URL").rstrip("/")
    eigen = umgebung("NADOKU_STAGING_KONTO").lower()
    admin = (eigen, umgebung("NADOKU_STAGING_PASS"), umgebung("NADOKU_STAGING_TOTP"))
    behalten = {eigen, *IMMER} | {a.strip().lower() for a in umgebung("NADOKU_STAGING_BEHALTEN").split(",")
                                   if a.strip()}

    s = sitzungsmodul.Sitzung(basis).anmelden(*admin)
    konten = konten_lesen(s)
    if eigen not in konten:
        print(f"FEHLER: das eigene Konto steht nicht in der Liste — die Liste ist nicht gelesen.")
        return 1
    weg = sorted(konten - behalten)
    print(f"Staging: {len(konten)} Konten, {len(konten & behalten)} behalten, {len(weg)} zu löschen.")
    for k in sorted(konten & behalten):
        print(f"  bleibt   {k}")
    if not wirklich:
        for k in weg:
            print(f"  würde    {k}")
        print("Nur angezeigt. Mit --wirklich wird gelöscht.")
        return 0
    fehler = 0
    for k in weg:
        try:
            # Das Präfix ist hier die ganze Adresse: Der Riegel von
            # `konto_loeschen()` hält damit genau dieses eine Konto.
            erg = kreislauf.konto_loeschen(basis, admin, k, praefix=k)
            print(f"  gelöscht {k}" if erg else f"  fehlte   {k}")
        except Exception as e:  # noqa: BLE001 — je Konto melden, weitermachen
            fehler += 1
            print(f"  FEHLER   {k}: {e}")
    return 1 if fehler else 0


if __name__ == "__main__":
    sys.exit(main())
