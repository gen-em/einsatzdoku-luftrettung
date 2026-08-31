#!/usr/bin/env python3
"""Kreislauftest: Referenz-Export in ein FRISCHES Konto und wieder heraus (B5).

    Referenz  →  leeres Konto  →  erneut exportieren  →  vergleichen

WARUM EIN FRISCHES KONTO. Ein Rueckspielen in dasselbe Konto beantwortet die
Frage nicht: Die Dublettenerkennung ueberspringt dort alles, und was
herauskaeme, waere der unveraenderte Ausgangsbestand — ein Vergleich mit sich
selbst. Erst ein leeres Konto zwingt jeden Datensatz durch den Einspielweg.

WAS DIESES SKRIPT SELBST TUT: nichts, was es schon gibt. Konto anlegen,
Passwort setzen und Stammdaten fuellen erledigen die vorhandenen, geprueften
Bausteine (einspielen.py, passwort_setzen.mjs). Ein zweiter Weg zum Anlegen
eines Kontos waere ein zweiter Weg, den niemand pflegt.

Aufruf:
    python3 kreislauf.py --art edbak
    python3 kreislauf.py --art csv
    ... [--basis …] [--konto …] [--frisch] [--behalten]

--frisch loescht ein bereits vorhandenes Umlaufkonto vorher ueber den
Adminbereich. Ohne diesen Schalter bricht der Lauf ab, wenn das Konto schon
besteht — ein Umlauf in ein GEFUELLTES Konto misst nichts.
"""
from __future__ import annotations

import argparse
import json
import pathlib
import re
import subprocess
import sys

HIER = pathlib.Path(__file__).resolve().parent
WURZEL = HIER.parent
sys.path.insert(0, str(WURZEL / "einspielen"))
sys.path.insert(0, str(HIER))

import sitzung as sitzungsmodul      # noqa: E402
import vergleichen                    # noqa: E402

PLAYWRIGHT = "/opt/node22/lib/node_modules/playwright/index.mjs"


def melde(t: str) -> None:
    print(t, flush=True)


def lauf(befehl: list[str], **kw) -> subprocess.CompletedProcess:
    e = subprocess.run(befehl, capture_output=True, text=True, **kw)
    if e.returncode != 0:
        melde(e.stdout[-3000:])
        melde(e.stderr[-3000:])
        raise RuntimeError(f"Fehlgeschlagen: {' '.join(befehl[:3])} … (rc={e.returncode})")
    return e


# ------------------------------------------------------- Konto vorbereiten

# Nur Konten mit diesem Praefix darf dieses Werkzeug loeschen.
#
# WARUM ES DEN RIEGEL GIBT: Die erste Fassung von konto_loeschen() suchte die
# Kennung mit einem Regex, der VOR der E-Mail-Adresse nach `name="id"` sah.
# Im Markup steht die Adresse aber in der ERSTEN Zelle der Zeile und das
# Loeschformular in der letzten — der Ausdruck fand damit die Kennung der
# VORHERGEHENDEN Zeile. Beim ersten Lauf hat er das Referenzkonto geloescht
# statt des Umlaufkontos.
#
# Die Kennung wird jetzt eindeutig aus dem Verweis derselben Zeile gelesen
# (`admin_user.php?id=N`). Der Riegel steht trotzdem da: Ein Werkzeug, das
# loeschen kann, braucht eine Grenze, die nicht davon abhaengt, dass sein
# Parser stimmt.
UMLAUF_PRAEFIX = "umlauf-"


def konto_loeschen(basis: str, admin: tuple[str, str], konto: str,
                   praefix: str = UMLAUF_PRAEFIX) -> bool:
    """Loescht ein PRUEFKONTO ueber den Adminbereich, falls es besteht.

    `praefix` ist der Riegel und hat den Umlauf-Vorgabewert. Ein zweites
    Werkzeug mit eigenen Pruefkonten (der Messstand aus S2) benennt hier sein
    eigenes Praefix, statt sich einen zweiten Loeschweg zu schreiben — der
    waere ein zweiter Weg, den niemand pflegt, und ausgerechnet einer, der
    Konten loescht. Weggelassen werden darf das Praefix nicht: Ein leerer
    Riegel ist keiner.
    """
    if len(praefix) < 4:
        raise RuntimeError(
            f"Das Loeschpraefix '{praefix}' ist zu kurz, um ein Riegel zu sein.")
    if not konto.startswith(praefix):
        raise RuntimeError(
            f"Dieses Werkzeug loescht nur Konten mit dem Praefix "
            f"'{praefix}'. Angefragt war '{konto}'.")
    s = sitzungsmodul.Sitzung(basis).anmelden(*admin)
    html = s.get("admin_users.php").text
    if konto not in html:
        return False

    # DIE KENNUNG STEHT VOR DER ADRESSE, NICHT DARIN (P3/O11, F-P3-BB).
    #
    # Bis Web 9.8.x war die Kontenzeile <a href="admin_user.php?id=N">adresse</a>,
    # und der Ausdruck `id=(\d+)"[^>]*>([^<]+)</a>` holte beides in einem Griff.
    # Mit dem Umbau der Liste (P3/O9b) stimmt das nicht mehr: Ab 720 px ist sie
    # eine Tabelle, deren Zeile die Kennung in `data-ziel` traegt und deren
    # Verweis „Öffnen" heisst; darunter ist sie eine `.zeile`, deren Verweis den
    # Text in ein <span class="zeile-haupt"> wickelt. In beiden Faellen findet
    # `>([^<]+)</a>` die Adresse nicht — der Ausdruck lieferte NULL Paare.
    #
    # Aufgefallen ist es erst in O11, weil `--frisch` nur dann hier hereinlaeuft,
    # wenn das Umlaufkonto schon besteht; beim ersten Lauf auf einer frischen
    # Datenbank endet die Funktion eine Zeile hoeher mit `return False`.
    #
    # Statt eines neuen, ebenso zerbrechlichen Musters wird jetzt die STELLE
    # gesucht: Die Kennung steht in beiden Fassungen VOR der Adresse — als
    # `data-ziel` der Tabellenzeile bzw. als `href` des Zeilenverweises. Also
    # die letzte Kennung vor dem Vorkommen der Adresse.
    stelle = html.find(konto)
    treffer = [m for m in re.finditer(r'admin_user\.php\?id=(\d+)', html)
               if m.start() < stelle]
    if not treffer:
        raise RuntimeError(f"Kennung von {konto} nicht gefunden")
    kennung = treffer[-1].group(1)

    # DAS LOESCHEN LIEGT AUF DER KONTOSEITE, NICHT IN DER LISTE (P3/O9a, E-P3-41).
    #
    # Bis Web 9.8.x nahm `admin_users.php` ein `action=user_del` mit der
    # Kennung entgegen. Seit die Kontoseite die Drehscheibe ist, steht die
    # Loeschung dort — `admin_user.php?id=N` mit `action=user_delete` — und sie
    # verlangt eine ZWEITE STUFE: Die E-Mail-Adresse muss abgetippt werden,
    # serverseitig geprueft. Der alte Aufruf lief ins Leere und meldete nichts;
    # die Funktion merkte es erst an der Gegenprobe unten (F-P3-BB).
    #
    # `sicherungen_mit=1` ist die Vorbelegung der Oberflaeche: Zum Umlaufkonto
    # gehoerende Admin-Sicherungen gehen mit. Genau das ist hier richtig — ein
    # Pruefkonto soll keine verwaisten Sicherungen hinterlassen.
    seite = f"admin_user.php?id={kennung}"
    s.csrf_auffrischen(seite)
    s.post(seite, {"csrf": s.csrf, "action": "user_delete",
                   "confirm_email": konto, "sicherungen_mit": "1"})
    nachher = s.get("admin_users.php").text
    if konto in nachher:
        raise RuntimeError(f"{konto} liess sich nicht loeschen")
    return True


def konto_anlegen(basis: str, admin: tuple[str, str], konto: str,
                  passwort: str) -> None:
    """Konto ueber den Einladungsweg anlegen und das Passwort im Browser setzen."""
    s = sitzungsmodul.Sitzung(basis).anmelden(*admin)
    s.csrf_auffrischen("admin_users.php")
    antwort = s.post("admin_users.php", {"csrf": s.csrf, "action": "user_add",
                                         "email": konto, "role": "user"})
    m = re.search(r"pw_handling\.php\?token=([0-9a-f]{64})", antwort.text)
    if not m:
        raise RuntimeError("Kontoanlage ohne Einrichtungslink: "
                           + (sitzungsmodul.fehlertext(antwort.text) or "unbekannt"))
    link = f"{basis}/pw_handling.php?token={m.group(1)}"
    melde(f"  Konto {konto} angelegt.")
    lauf(["node", str(WURZEL / "einspielen" / "passwort_setzen.mjs"), link, passwort],
         env={**__import__("os").environ, "PLAYWRIGHT_MODUL": PLAYWRIGHT})
    melde("  Passwort im Browser gesetzt (dort entsteht das Schlüsselmaterial).")


# ---------------------------------------------------------------- Umlaeufe

def umlauf_edbak(a) -> tuple[str, str]:
    quelle = neueste(a.referenz, "*.edbak")
    melde(f"[Sicherung] Referenz: {quelle.name}")
    ordner = pathlib.Path(a.ausgabe) / "edbak"
    ordner.mkdir(parents=True, exist_ok=True)
    e = lauf(["node", str(WURZEL / "browser" / "kreislauf_edbak.mjs"),
              a.basis, str(quelle), a.backup_passwort, str(ordner)],
             env={**__import__("os").environ, "PLAYWRIGHT_MODUL": PLAYWRIGHT,
                  "UMLAUF_KONTO": a.konto, "UMLAUF_PASSWORT": a.konto_passwort})
    melde(e.stdout.rstrip())
    ergebnis = json.loads((ordner / "lauf.json").read_text("utf-8"))
    return str(quelle), ergebnis["ergebnisdatei"]


def umlauf_csv(a) -> tuple[str, str]:
    quelle = neueste(a.referenz, "*.zip")
    melde(f"[CSV] Referenz: {quelle.name}")
    ordner = pathlib.Path(a.ausgabe) / "csv"
    ordner.mkdir(parents=True, exist_ok=True)
    melde("  Stammdaten im frischen Konto anlegen (die Importseite verlangt "
          "ein Rettungsmittel und einen Standort).")
    lauf([sys.executable, str(WURZEL / "einspielen" / "einspielen.py"),
          "--basis", a.basis, "--stufen", "stammdaten",
          "--konto", a.konto, "--konto-passwort", a.konto_passwort,
          "--zustand", str(ordner / "einspiellauf.json")])
    e = lauf(["node", str(WURZEL / "browser" / "kreislauf_csv.mjs"),
              a.basis, str(quelle), str(ordner)],
             env={**__import__("os").environ, "PLAYWRIGHT_MODUL": PLAYWRIGHT,
                  "UMLAUF_KONTO": a.konto, "UMLAUF_PASSWORT": a.konto_passwort})
    melde(e.stdout.rstrip())
    ergebnis = json.loads((ordner / "lauf.json").read_text("utf-8"))
    return str(quelle), ergebnis["ergebnisdatei"]


def neueste(ordner: str, muster: str) -> pathlib.Path:
    treffer = sorted(pathlib.Path(ordner).glob(muster))
    if not treffer:
        raise RuntimeError(f"Keine Datei {muster} in {ordner}")
    if len(treffer) > 1:
        raise RuntimeError(f"Mehrdeutig: {len(treffer)} Dateien {muster} in {ordner}. "
                           f"Der Referenzordner traegt genau EINE je Format.")
    return treffer[0]


def main() -> int:
    import os
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("--art", choices=["csv", "edbak"], required=True)
    p.add_argument("--basis", default="https://127.0.0.1:8443")
    p.add_argument("--referenz", default=str(WURZEL / "referenz"))
    p.add_argument("--ausgabe", default="/tmp/kreislauf")
    p.add_argument("--konto", default=None)
    p.add_argument("--konto-passwort", default="umlaufpruefung2026")
    p.add_argument("--backup-passwort", default="nadokudemo0815")
    p.add_argument("--admin-email", default="admin@gen-em.org")
    p.add_argument("--admin-passwort", default="adminlokal2026")
    p.add_argument("--ausnahmen", default=None)
    p.add_argument("--frisch", action="store_true",
                   help="vorhandenes Umlaufkonto vorher löschen")
    a = p.parse_args()
    a.konto = a.konto or f"umlauf-{a.art}@gen-em.org"
    a.ausnahmen = a.ausnahmen or str(HIER / "ausnahmen" / f"{a.art}_umlauf.json")
    admin = (a.admin_email, a.admin_passwort)

    melde(f"Kreislauf {a.art} — Zielkonto {a.konto}")
    if a.frisch and konto_loeschen(a.basis, admin, a.konto):
        melde(f"  Vorhandenes Konto {a.konto} gelöscht.")
    konto_anlegen(a.basis, admin, a.konto, a.konto_passwort)

    quelle, ergebnis = umlauf_edbak(a) if a.art == "edbak" else umlauf_csv(a)

    melde(f"\n[Vergleich] {pathlib.Path(quelle).name} ↔ {pathlib.Path(ergebnis).name}")
    befehl = [sys.executable, str(HIER / "vergleichen.py"), "--art", a.art,
              quelle, ergebnis, "--bericht",
              str(pathlib.Path(a.ausgabe) / a.art / "bericht")]
    if pathlib.Path(a.ausnahmen).exists():
        befehl += ["--ausnahmen", a.ausnahmen]
    else:
        melde(f"  (noch keine Ausnahmeliste unter {a.ausnahmen})")
    if a.art == "edbak":
        befehl += ["--passwort", a.backup_passwort]
    e = subprocess.run(befehl, text=True)
    return e.returncode


if __name__ == "__main__":
    sys.exit(main())
