#!/usr/bin/env python3
"""Anker der S5-Fundstellen — findet sie am INHALT, nicht an der Zeilennummer.

WOZU. Das Konzept `docs/konzepte/Konzept-S5-Kopplung-umgekehrt.md` nennt zu
jeder Aussage eine Fundstelle mit Zeilennummer. Diese Nummern sind am
02.09.2026 an `main` (c2ac707) erhoben und stimmten alle. Sie halten aber nur
bis zum naechsten Paket, das dieselben Dateien anfasst — S7 ersetzt
"Sicherung" durch "Backup" und beruehrt dabei `einstellungen.php` (46
Treffer), `jobs_lib.php` (22), `update.php` (16), `Handbuch.md` (71) und
`Technik.md` (114). Danach zeigt jede Zeilennummer des Konzepts auf etwas
anderes.

Ein Konzept deswegen umzuschreiben waere die falsche Antwort: Die Nummern
sind Beleg, kein Wegweiser. Dieses Werkzeug sucht statt ihrer den TEXT und
sagt, wo er heute steht.

DREI ANTWORTEN, und die dritte ist die wichtige:

  gefunden, eine Stelle    Zeile hat sich vielleicht verschoben — kein Problem
  gefunden, mehrere        das Muster taugt nicht mehr (oder es gibt die
                           Stelle jetzt zweimal) — nachsehen
  NICHT gefunden           jemand hat die Stelle umgeschrieben oder entfernt.
                           Dann ist der Konzeptabsatz dazu neu zu lesen.

Aufruf:
    python3 tools/s5-anker/anker.py            # alle Anker
    python3 tools/s5-anker/anker.py --knapp    # nur Abweichungen
    python3 tools/s5-anker/anker.py --paket A  # nur die eines Pakets

Rueckgabewert 0 = jeder Anker genau einmal gefunden, 1 = mindestens einer
fehlt oder ist mehrdeutig.
"""
from __future__ import annotations

import argparse
import pathlib
import re
import sys

WURZEL = pathlib.Path(__file__).resolve().parents[2]

# (Paket, Kennung, Datei, Sollzeile am 02.09.2026, Muster [, erwartete Trefferzahl])
#
# Das Muster ist ein regulaerer Ausdruck auf EINER Zeile. Gewaehlt ist
# jeweils der kuerzeste Text, der die Stelle eindeutig macht und der eine
# Umbenennung "Sicherung" -> "Backup" ueberlebt.
#
# Die Trefferzahl ist 1, wo nichts anderes dasteht. Sie ausdruecklich auf 2
# zu setzen ist keine Nachsicht, sondern eine Aussage: An dieser Stelle steht
# derselbe Text zweimal, und das ist so gewollt. Wird daraus eine 1 oder eine
# 3, meldet das Werkzeug es.
ANKER: list[tuple] = [
    # ---- Paket A: Server ---------------------------------------------------
    ("A", "pair.abweisen",        "server/pair.php",  56,
     r"function abweisen\(int \$status"),
    ("A", "pair.sperre-zuerst",   "server/pair.php",  68,
     r"if \(!rate_erlaubt\('pair'\)\)"),
    ("A", "pair.trennen",         "server/pair.php", 104,
     r"=== 'trennen'"),
    ("A", "pair.muster",          "server/pair.php", 171,
     r"preg_match\(PAIR_RE, \$code\)"),
    ("A", "pair.entwerten",       "server/pair.php", 190,
     r"UPDATE pair_codes SET used_at"),
    ("A", "pair.rowcount",        "server/pair.php", 194,
     r"\$entwerten->rowCount\(\) !== 1"),
    ("A", "pair.device-limit",    "server/pair.php", 220,
     r"geraete_grenze_erreicht\(\$pdo, \$ownerId\)"),
    ("A", "pair.kennung",         "server/pair.php", 249,
     r"'dev-' \. bin2hex\(random_bytes\(16\)\)"),
    ("A", "pair.block-lesen",     "server/pair.php", 270,
     r"geraet_block_lesen\(\$b\['geraet'\]"),
    ("A", "pair.mail",            "server/pair.php", 341,
     r"Neues Ger.t gekoppelt"),
    ("A", "db.pair-chars",        "server/db.php",   459,
     r"^const PAIR_CHARS"),
    ("A", "db.pair-ttl",          "server/db.php",   461,
     r"^const PAIR_TTL_MIN"),
    ("A", "db.pair-re",           "server/db.php",   462,
     r"^const PAIR_RE"),
    ("A", "db.vergleichswert",    "server/db.php",   483,
     r"^const AUTH_VERGLEICHSWERT"),
    ("A", "db.max-geraete",       "server/db.php",   580,
     r"^const MAX_GERAETE"),
    ("A", "rate.grenzen",         "server/ratelimit_lib.php",  51,
     r"^const RATE_GRENZEN"),
    ("A", "rate.topf-pair",       "server/ratelimit_lib.php",  55,
     r"'pair'\s+=> \['max'"),
    ("A", "rate.merkmale",        "server/ratelimit_lib.php",  96,
     r"^function rate_merkmale"),
    ("A", "rate.zaehlen",         "server/ratelimit_lib.php", 196,
     r"^function rate_zaehlen"),
    ("A", "rate.erfolg",          "server/ratelimit_lib.php", 209,
     r"^function rate_erfolg"),
    ("A", "rate.gleiche-dauer",   "server/ratelimit_lib.php", 234,
     r"^function rate_gleiche_dauer"),
    ("A", "rate.gesperrt-bis",    "server/ratelimit_lib.php", 245,
     r"^function rate_gesperrt_bis"),
    ("A", "jobs.katalog",         "server/jobs_lib.php", 202,
     r"Papierkorb, Kopplungscodes"),
    ("A", "jobs.schritt-codes",   "server/jobs_lib.php", 503,
     r"'Kopplungscodes' => function"),
    ("A", "jobs.php-topf",        "server/jobs.php", 99,
     r"if \(!rate_erlaubt\('pair'\)\)"),
    ("A", "schema.pair-codes",    "server/schema.sql", 420,
     r"^CREATE TABLE pair_codes"),
    ("A", "schema.register-ende", "server/schema.sql", 0,
     r"'2026_09_02_geraetemodell_breiter'"),
    ("A", "demo.tabellenliste",   "server/demo_lib.php", 387,
     r"'devices', 'pair_codes'"),
    ("A", "geraete.block-lesen",  "server/geraete_lib.php", 91,
     r"^function geraet_block_lesen"),

    # ---- Paket B: Web ------------------------------------------------------
    ("B", "einst.manuell-anlegen", "server/einstellungen.php", 261,
     r"'dev-' \. bin2hex\(random_bytes\(4\)\)"),
    ("B", "einst.pair-code-grenze", "server/einstellungen.php", 274,
     r"\$action === 'pair_code' && geraete_grenze_erreicht"),
    ("B", "einst.pair-code",        "server/einstellungen.php", 281,
     r"elseif \(\$action === 'pair_code'\)"),
    ("B", "einst.karte-koppeln",    "server/einstellungen.php", 2943,
     r"ui_karte_start\(\['titel' => 'Ger.t koppeln'"),
    ("B", "einst.code-eintippen",   "server/einstellungen.php", 2945,
     r"Ger.t koppeln . Code eintippen"),
    ("B", "einst.codeblock",        "server/einstellungen.php", 2961,
     r'class="codeblock-titel">Kopplungscode'),
    ("B", "einst.knopf-erzeugen",   "server/einstellungen.php", 2974,
     r"'text' => 'Kopplungscode erzeugen'"),
    ("B", "einst.geraeteliste",     "server/einstellungen.php", 2979,
     r"ui_karte_start\(\['titel' => 'Ger.te'"),
    ("B", "einst.haupthandlung",    "server/einstellungen.php", 3075,
     r"bleibt .Kopplungscode erzeugen"),
    ("B", "style.codeblock",        "server/assets/style.css", 2786,
     r"^\.codeblock\{"),
    ("B", "screenshots.seite33",    "tools/screenshots/seiten.json", 165,
     r'"33-einstellungen-geraete"'),

    # ---- Paket C: Uhr ------------------------------------------------------
    ("C", "pair.mc.kopf",         "watch/source/Pair.mc",   3,
     r"UP halten -> Code eintippen"),
    ("C", "pair.mc.trennen-dlg",  "watch/source/Pair.mc",  35,
     r"^class TrennenDelegate"),
    ("C", "pair.mc.zeile-max",    "watch/source/Pair.mc",  64,
     r"^\s*const ZEILE_MAX"),
    ("C", "pair.mc.start",        "watch/source/Pair.mc",  93,
     r"^\s*function start\(\) as Void"),
    ("C", "pair.mc.trennen",      "watch/source/Pair.mc", 113,
     r"^\s*function trennen\(\) as Void"),
    ("C", "pair.mc.openinput",    "watch/source/Pair.mc", 176,
     r"^\s*function openInput"),
    ("C", "pair.mc.geraeteinfo",  "watch/source/Pair.mc", 217,
     r"^\s*function _geraeteInfo"),
    ("C", "pair.mc.request",      "watch/source/Pair.mc", 237,
     r"^\s*function request\(code"),
    ("C", "pair.mc.keine-domain", "watch/source/Pair.mc", 240,
     r'"Erst Server-Domain setzen"'),
    ("C", "pair.mc.onresponse",   "watch/source/Pair.mc", 289,
     r"^\s*function onResponse\(code as Lang.Number, data as Lang.Object"),
    ("C", "pair.mc.verbindung",   "watch/source/Pair.mc", 317,
     r"\} else if \(code < 0\) \{"),
    ("C", "pair.mc.unbekannt",    "watch/source/Pair.mc", 330,
     r'"Kopplung fehlgeschlagen \("'),
    ("C", "pair.mc.textpicker",   "watch/source/Pair.mc", 339,
     r"^class PairTextDelegate"),
    ("C", "sync.timer",           "watch/source/SyncView.mc",  25,
     r"_timer\.start\(method\(:refresh\), 2000, true\)"),
    ("C", "sync.einrichtung",     "watch/source/SyncView.mc",  96,
     r'"Erst Server-Adresse setzen"'),
    ("C", "sync.koppeln-hinweis", "watch/source/SyncView.mc",  98,
     r'lSelectHold\(\) \+ ": Ger.t koppeln"'),
    ("C", "sync.selectlong",      "watch/source/SyncView.mc", 231,
     r"^\s*function actSelectLong"),
    ("C", "ui.fitfont",           "watch/source/Ui.mc", 125,
     r"^\s*function fitFont"),
    ("C", "ui.fonthint",          "watch/source/Ui.mc", 140,
     r"^\s*function fontHint"),
    # Derselbe Dialogtext steht zweimal in der Datei (216 und 245) — der
    # Endlauf und der Abbruch fragen wortgleich. Deshalb anzahl=2.
    ("C", "clock.confirm-lang",   "watch/source/ClockView.mc", 216,
     r"Sync unvollst.ndig", 2),
    ("C", "uploader.credentials", "watch/source/Uploader.mc", 180,
     r"^\s*function credentials"),
    ("C", "uploader.beispiel",    "watch/source/Uploader.mc", 216,
     r"nadoku\.beispieldomain\.de"),
    ("C", "props.serverurl",      "watch/resources/settings/properties.xml", 6,
     r'property id="serverUrl"'),
    ("C", "props.ohne-vorgabe",   "watch/resources/settings/properties.xml", 5,
     r"Bewusst ohne Vorgabewert"),
    ("C", "settings.serverurl",   "watch/resources/settings/settings.xml", 3,
     r"Server-Adresse der eigenen NAdoku"),
    ("C", "wortliste.bereiche",   "tools/wortliste/wortliste.py", 75,
     r"^BEREICHE"),
    ("C", "wortliste.watch-fehlt", "tools/wortliste/wortliste.py", 69,
     r"`watch/` FEHLT WEITERHIN"),

    # ---- Paket D: Doku -----------------------------------------------------
    ("D", "vertrag.durchsetzung",  "docs/JSON-Vertrag.md",  45,
     r"beschrieben, nicht umgesetzt"),
    ("D", "vertrag.1b-429",        "docs/JSON-Vertrag.md", 206,
     r"gilt f.r beide Anliegen von"),
    ("D", "technik.datenmodell",   "docs/Technik.md",  422,
     r"^\| `pair_codes` \|"),
    ("D", "technik.mail-frist",    "docs/Technik.md", 1760,
     r"deshalb steht das Zeitlimit bei der Kopplung"),
    ("D", "technik.antwortgleich", "docs/Technik.md", 1936,
     r"Zwei Stellen, an denen die Gleichheit von Antworten"),
    ("D", "technik.jobs-topf",     "docs/Technik.md", 2294,
     r"Ratenschutz-Topf `pair` \(zehn Fehlversuche"),
    ("D", "technik.zeitrechnung",  "docs/Technik.md", 3701,
     r"`TIMESTAMP` und `DATETIME` verhalten sich verschieden"),
    ("D", "handbuch.abschnitt12",  "docs/Handbuch.md", 2682,
     r"Ger.t koppeln . Code eintippen"),
    ("D", "handbuch.12-1",         "docs/Handbuch.md", 2718,
     r"im Web einen Code erzeugen und eintippen"),
    ("D", "backup.pair-codes",     "docs/Backup-Format.md", 1006,
     r"\*\*Kopplungscodes\*\* \(`pair_codes`\)"),
    ("D", "backlog.66",            "docs/Backlog.md", 697,
     r"^66\. \*\*Der Garmin-Uhrcode"),
    ("D", "backlog.84",            "docs/Backlog.md", 1067,
     r"^84\. \*\*Die Android-App kennt nur"),
    ("D", "rahmenplan.sperren",    "docs/Rahmenplan.md", 486,
     r"S5-Umsetzung zu S6 und S7"),
    ("D", "claude.watch-fehlt",    "CLAUDE.md", 203,
     r"`watch/` fehlt noch und ist einer"),
    ("D", "android.rundlauf-sql",  "android/LIESMICH.md", 71,
     r"INSERT INTO pair_codes"),
    ("D", "uhrbilder.bitgleich",   "tools/uhr-bilder/erzeugen.sh", 13,
     r"sie BITGLEICH \(geprueft"),
]


def suche(datei: pathlib.Path, muster: str) -> list[int]:
    if not datei.is_file():
        return []
    r = re.compile(muster)
    return [nr for nr, z in enumerate(datei.read_text(encoding="utf-8",
                                                      errors="replace").splitlines(), 1)
            if r.search(z)]


def main() -> int:
    p = argparse.ArgumentParser(description="Anker der S5-Fundstellen nachziehen")
    p.add_argument("--knapp", action="store_true",
                   help="nur Anker mit Abweichung oder Fehlschlag")
    p.add_argument("--paket", action="append", choices=list("ABCD"),
                   help="nur die Anker dieses Pakets (mehrfach moeglich)")
    a = p.parse_args()

    anker = [x for x in ANKER if not a.paket or x[0] in a.paket]
    fehlend = mehrdeutig = verschoben = 0

    print(f"S5-Anker gegen {WURZEL}")
    print(f"  {len(anker)} Anker, Sollzeilen erhoben am 02.09.2026 (main c2ac707)\n")
    print(f"  {'P':1} {'Kennung':24} {'Datei':40} {'Soll':>5} {'Ist':>5}  Befund")
    print("  " + "-" * 96)

    for eintrag in anker:
        paket, kennung, rel, soll, muster = eintrag[:5]
        erwartet = eintrag[5] if len(eintrag) > 5 else 1
        treffer = suche(WURZEL / rel, muster)
        if not treffer:
            befund, ist = "NICHT GEFUNDEN — Stelle neu lesen", "—"
            fehlend += 1
        elif len(treffer) != erwartet:
            befund = (f"MEHRDEUTIG ({len(treffer)}× statt {erwartet}: "
                      f"{', '.join(map(str, treffer[:5]))})")
            ist = str(treffer[0])
            mehrdeutig += 1
        else:
            ist = str(treffer[0])
            zusatz = f" ({erwartet}×)" if erwartet > 1 else ""
            if soll == 0:
                befund = "ohne Sollzeile" + zusatz
            elif treffer[0] == soll:
                befund = "unveraendert" + zusatz
            else:
                befund = f"verschoben um {treffer[0] - soll:+d}"
                verschoben += 1
        if a.knapp and befund.startswith(("unveraendert", "ohne Sollzeile")):
            continue
        print(f"  {paket} {kennung:24} {rel:40} {soll or '—':>5} {ist:>5}  {befund}")

    print()
    print(f"  nicht gefunden: {fehlend} · mehrdeutig: {mehrdeutig} · "
          f"verschoben: {verschoben} · unveraendert: "
          f"{len(anker) - fehlend - mehrdeutig - verschoben}")
    if fehlend or mehrdeutig:
        print("\n  Ein fehlender Anker ist eine Auskunft, keine Panne: Die Stelle ist")
        print("  umgeschrieben worden. Den zugehoerigen Konzeptabsatz neu lesen.")
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main())
