#!/usr/bin/env python3
"""Wortliste: sucht luftgebundene Begriffe dort, wo die Anwendung neutral
sprechen soll.

    Steht in einem sichtbaren Text oder in der normativen Dokumentation ein
    Wort, das nur von der Luftrettung her gedacht ist?

Beantwortet wird das mit **drei Zahlen je Bereich**: Treffer gesamt, Treffer
ausserhalb der Ausnahmen, ungenutzte Ausnahmen. Die zweite und die dritte
muessen null sein; sonst ist der Rueckgabewert 1.

WARUM DIE DRITTE ZAHL. Eine Ausnahme, die nicht greift, beschreibt entweder
etwas, das es nicht mehr gibt, oder der Lauf hat die Stelle gar nicht
angesehen — dann prueft er weniger als gedacht. Dasselbe Prinzip wie die
ungenutzten Regeln im Kreislaufvergleich (tools/referenzdatensatz/vergleich).

WAS DIESES WERKZEUG NICHT KANN. Es findet Woerter, keine Perspektive. Ein
Satz wie „das Rettungsmittel landet am Einsatzort" enthaelt kein Sperrwort
und ist trotzdem von der Luft her gedacht. Dafuer gibt es kein Werkzeug,
nur Lesen (Konzept P2, Paket D4, Schritt 11).

Aufruf:
    python3 wortliste.py                  # alle Bereiche
    python3 wortliste.py --bereich a      # nur die PHP-Dateien des Servers
    python3 wortliste.py --alle           # auch die erklaerten Treffer zeigen
    python3 wortliste.py --probe          # Selbstprobe des Zerlegers
    python3 wortliste.py --bericht /tmp/w.txt

Rueckgabewert: 0 = sauber, 1 = Treffer ausserhalb der Ausnahmen oder
ungenutzte Ausnahmen, 2 = Fehler (fehlende Datei, unbrauchbare Regel,
Selbstprobe nicht bestanden).
"""
from __future__ import annotations

import argparse
import fnmatch
import json
import pathlib
import re
import sys

HIER = pathlib.Path(__file__).resolve().parent
WURZEL = HIER.parent.parent

sys.path.insert(0, str(HIER))
import zerlegen                                    # noqa: E402

# Die Bereiche aus dem Konzept P2, Abschnitt 5.1 (Klassen A und B), seit
# S4/D1 um die Android-Apps erweitert.
#
# DIE REGEL DAHINTER (S4, B-S4-06, auf Ansage vom 01.09.2026):
#
#   Jeder sichtbare Text der Anwendung laeuft durch die Wortliste — gleich,
#   in welchem Client er steht. Ein Bereich fehlt nicht, weil ein Verzeichnis
#   jung ist; er fehlt, weil ihn niemand eingetragen hat. Wer einen Client
#   hinzufuegt, traegt seine Textdateien im selben Paket ein, in dem der
#   Client entsteht. Ein Lauf, der einen Client uebergeht, meldet keine Null —
#   er meldet gar nichts.
#
# Genau das war passiert: Die Android-Apps entstanden in S4/B1, und der Lauf
# nach C2 meldete 0 Treffer, ohne eine einzige Zeile der App angesehen zu
# haben. Der Fall, vor dem CLAUDE.md 6 warnt — eine gruene Zahl, die etwas
# anderes gemessen hat.
#
# Was hier NICHT steht, ist Absicht: CHANGELOG.md (Historie), die Konzept-
# und Pruefdokumente, Geraete-Eingabe.md und Uhr-Layout_Regeln.md
# (plattformspezifisch, Klasse G), Backlog.md und tools/ (Klasse H).
#
# `watch/` IST SEIT S5/C DABEI, als Bereich `e` (Backlog 66, E-S5-40). Die
# sichtbaren Texte der Garmin-App sind die aeltesten des Projekts und damit
# die wahrscheinlichste Fundstelle; bis dahin waren sie als einziger Client
# ungeprueft.
#
# UND ZWAR XML UND MONKEY C (E-S5-61). Backlog 66 nannte nur
# `watch/resources/**/*.xml` — das sind vier Zeichenketten (AppName und die
# drei Namen der Bildmarken-Wahl). Die eigentlichen Texte der App stehen als
# Literale im Quelltext: "Nicht eingerichtet", "Zu viele Geräte",
# "Sync vollständig". Ein Bereich, der die XML ansieht und die `.mc` uebergeht,
# meldete wieder eine Null ueber etwas, das er nicht gelesen hat — genau der
# Fall B-S4-06, der die Regel oben ueberhaupt erst noetig gemacht hat.
BEREICHE: dict[str, dict] = {
    "a": {
        "titel": "server/*.php, server/api/*.php (sichtbare Texte, ohne Kommentare)",
        "art": "php",
        "glob": ["server/*.php", "server/api/*.php"],
        # config.php gehoert nicht zum Repositorium (sie steht in .gitignore
        # und liegt nur auf dem Server). Waere sie dabei, haenge die
        # Dateizahl davon ab, ob gerade eine lokale Installation eingerichtet
        # ist — und die Zahlen zweier Laeufe waeren nicht vergleichbar.
        "ausser": ["server/config.php"],
    },
    "b": {
        "titel": "server/assets/*.js ohne vendor/ (Zeichenketten, ohne Kommentare)",
        "art": "js",
        "glob": ["server/assets/*.js"],
    },
    "d": {
        "titel": "android/*/src/main/res/values/strings.xml (Handy und Uhr)",
        "art": "xml",
        # `values/` OHNE Sprachkennung: Das ist die deutsche Fassung, und eine
        # andere gibt es nicht (die Apps sind einsprachig wie die
        # Weboberflaeche). Kaeme eine hinzu, gehoerte sie mit in dieses
        # Muster — `values-*/strings.xml` waere dann die Erweiterung.
        "glob": ["android/*/src/main/res/values/strings.xml"],
    },
    "e": {
        "titel": "watch/ — Ressourcen und Quelltext der Garmin-App",
        # ZWEI ARTEN IN EINEM BEREICH. Die Ressourcen sind XML, der Quelltext
        # ist Monkey C; beides beantwortet dieselbe Frage ("steht in einem
        # sichtbaren Text der Uhr ein Luftbegriff?") und gehoert deshalb unter
        # eine Zahl. Zwei Bereiche haetten zwei Zahlen ergeben, die niemand
        # addiert — und die Regel oben verlangt eine Aussage je Client, nicht
        # je Dateiformat.
        "art": {".xml": "xml", ".mc": "monkeyc"},
        # `resources*` statt `resources`: Die vorgerasterten Bildmarken und
        # Launcher-Symbole liegen in Geschwisterordnern (resources-marke101,
        # resources-icon54 …), die monkey.jungle je Geraet zuweist. Sie
        # tragen heute keinen sichtbaren Text — aber ein Ordner, den niemand
        # ansieht, ist genau die Luecke, die dieser Bereich schliessen soll.
        # `watch/manifest.xml` bleibt draussen: Der App-Name steht dort als
        # Verweis (@Strings.AppName), der Text selbst in strings.xml.
        "glob": ["watch/resources*/**/*.xml", "watch/source*/*.mc"],
    },
    "c": {
        "titel": "normative Dokumentation",
        "art": "md",
        "dateien": [
            "README.md",
            "docs/Handbuch.md",
            "docs/Export-Format.md",
            "docs/Technik.md",
            "docs/Backup-Format.md",
            "docs/JSON-Vertrag.md",
            "docs/Design.md",
            "docs/Lizenzen.md",
            # DIE BEIDEN REGELDOKUMENTE DER PRUEFKETTE, seit PK-01. Sie sind
            # normative Dokumentation wie Technik.md und Design.md — und sie
            # stehen hier, weil die Regel es verlangt, die sie selbst
            # aufschreiben: Wer ein normatives Dokument hinzufuegt, traegt es
            # im selben Paket ein, in dem es entsteht (B-S4-06,
            # docs/Pruefablauf.md 6.6). Ein Bereich fehlt nicht, weil eine
            # Datei jung ist, sondern weil ihn niemand eingetragen hat.
            "docs/Pruefablauf.md",
            "docs/Sandbox-Setup.md",
            # DIE RECHTSTEXTE GEHOEREN HIERHER, seit 17.09.2026 (P5b, E-P5b-25).
            # Sie sind Entwuerfe in `docs/`, aber ihr Ziel ist die Tabelle
            # `rechtstexte`, und von dort rendern `nutzungsbedingungen.php`,
            # `avv.php` und `datenschutz.php` sie als Seiten der Anwendung.
            # Damit sind sie sichtbarer Text und fallen unter R28 — sie standen
            # nur nicht in der Liste, weil sie nach ihr entstanden sind.
            #
            # DER LAUF MELDETE DESHALB EINE NULL, DIE NICHTS BEDEUTETE: Am
            # 17.09.2026 wurden alle drei Texte ueberarbeitet, die Wortliste
            # lief mit 0 Treffern und hatte keine Zeile davon angesehen. Das
            # ist derselbe Fehler, den B-S4-06 fuer die Android-App
            # festgehalten hat — ein Bereich fehlt nicht, weil er jung ist,
            # sondern weil ihn niemand eingetragen hat.
            "docs/rechtstexte/Nutzungsbedingungen.md",
            "docs/rechtstexte/AVV.md",
            "docs/rechtstexte/Datenschutz-Ergaenzung-P5.md",
        ],
    },
}


def melde(t: str = "") -> None:
    print(t, flush=True)


# ------------------------------------------------------------------ Laden

def liste_namen() -> list[str]:
    """Die realen Rufnamen und Orte — GELESEN aus dem Referenzdatensatz.

    `VERBOTENE_NAMEN` in `tools/referenzdatensatz/quelldaten/pruefen.py`
    (E-P1-02) ist die eine Liste. Eine Kopie hier ginge beim naechsten
    Eintrag auseinander, und zwar lautlos: Der Referenzdatensatz pruefte
    weiter gegen seine Liste, die Textprobe gegen eine aeltere.

    FEHLT SIE, IST DAS EIN ABBRUCH und kein leeres Muster. Ein leeres
    Muster traefe alles oder nichts — beides waere eine falsche Auskunft.
    """
    quelle = WURZEL / "tools" / "referenzdatensatz" / "quelldaten" / "pruefen.py"
    tr = re.search(r"VERBOTENE_NAMEN = \[(.*?)\]", quelle.read_text(encoding="utf-8"), re.S) \
        if quelle.exists() else None
    if not tr:
        raise SystemExit(f"VERBOTENE_NAMEN nicht gefunden in {quelle} — "
                         "die Regelklasse `namen` kann nicht messen.")
    return re.findall(r'"([^"]+)"', tr.group(1))


def liste_lizenzen() -> list[str]:
    """Die erlaubten Adressen im Netz — GELESEN aus `docs/Lizenzen.md`.

    Wer eine Bibliothek vendoriert, traegt ihre Herkunft dort ein; damit ist
    sie hier von selbst erlaubt. Dieselbe Ueberlegung wie oben.
    """
    pfad = WURZEL / "docs" / "Lizenzen.md"
    if not pfad.exists():
        raise SystemExit(f"{pfad} fehlt — die Regelklasse `netz` kann nicht messen.")
    t = pfad.read_text(encoding="utf-8")
    # ZWEI SCHREIBWEISEN, UND DIE ZWEITE IST DIE HAEUFIGERE. `Lizenzen.md`
    # nennt Dienste teils als volle Adresse (`https://photon.komoot.io`),
    # teils nur als Rechnernamen in Rueckstrichen (`tile.openstreetmap.org`
    # in der Kartentabelle). Die erste Fassung dieser Funktion las nur die
    # Adressen — und meldete damit jede Kartenquelle als unerlaubt, obwohl
    # alle drei seit P0 dort stehen. Eine Erlaubnisliste, die die Haelfte
    # ihrer Quelle nicht liest, ist keine.
    hosts = set(re.findall(r"https?://([a-z0-9.-]+)", t))
    hosts |= set(re.findall(r"`([a-z0-9][a-z0-9.-]*\.[a-z]{2,})`", t))
    # BEIDE SCHREIBWEISEN IN DIE LISTE, mit und ohne `www.`: `Lizenzen.md`
    # nennt `topografix.com`, der Namensraum im GPX heisst
    # `www.topografix.com`. Ein optionales `(?:www\.)?` im Muster genuegt
    # dafuer NICHT — die Engine faellt beim Fehlschlag darauf zurueck, es
    # nicht zu nehmen, und prueft dann `www.topografix.com` gegen die Liste.
    # Gemessen beim Bauen: der Namensraum blieb ein Treffer.
    ohne = {h[4:] if h.startswith("www.") else h for h in hosts}
    return sorted(ohne | {"www." + h for h in ohne})


# Muster-Kennung -> Regelklasse, und die Klassen mit ihrem Titel. Beide
# werden von `lade_sperrliste()` gefuellt; der Bericht liest sie.
KLASSE_VON: dict[str, str] = {}
KLASSEN: dict[str, str] = {}


def lade_sperrliste(pfad: pathlib.Path) -> tuple[list[dict], list[dict]]:
    d = json.loads(pfad.read_text(encoding="utf-8"))
    KLASSEN.update(d.get("klassen", {}))
    muster = []
    for m in d["muster"]:
        for pflicht in ("id", "regex", "grund"):
            if not m.get(pflicht):
                raise SystemExit(f"Muster ohne {pflicht}: {m}")
        flags = 0 if m.get("gross") else re.IGNORECASE
        m = dict(m)
        # PLATZHALTER ZUR LAUFZEIT FUELLEN (PK-04/1c). Zwei Regelklassen
        # halten gegen eine Liste, die anderswo gepflegt wird; sie steht
        # deshalb NICHT in diesem Muster, sondern wird beim Lauf geholt.
        if m.get("quelle") == "namen":
            m["regex"] = m["regex"].replace(
                "@@NAMEN@@", "|".join(re.escape(x) for x in liste_namen()))
        elif m.get("quelle") == "lizenzen":
            m["regex"] = m["regex"].replace(
                "@@LIZENZEN@@", "|".join(re.escape(x) for x in liste_lizenzen()))
        m["_re"] = re.compile(m["regex"], flags)
        KLASSE_VON[m["id"]] = m.get("klasse", "luft")
        muster.append(m)
    fallen = []
    for f in d.get("fallen", []):
        f = dict(f)
        f["_re"] = re.compile(f["regex"], re.IGNORECASE)
        fallen.append(f)
    return muster, fallen


ALTBESTAND = HIER / "textprobe-altbestand.json"


def lade_altbestand() -> dict:
    """Was am Tag der Einfuehrung schon dastand — je (Datei, Muster) eine Zahl.

    WARUM UEBERHAUPT (E-PK-08). Die vier Regelklassen aus PK-04/1c finden
    einen Altbestand, den PK-04 Teilstueck 5 bereinigt. Ohne diese Datei
    waere die Textprobe von der ersten Minute an rot und bliebe es, bis
    jemand ein paar hundert Stellen angefasst hat — und eine Pruefung, die
    dauerhaft rot ist, liest nach der zweiten Woche niemand mehr.
    **Rot ist deshalb nur ein NEUER Treffer.**

    ZAHLEN JE DATEI UND MUSTER, NICHT ZEILENNUMMERN. Eine Zeilennummer
    verschiebt sich bei jeder Einfuegung darueber; der Altbestand waere nach
    dem naechsten Commit falsch, ohne dass etwas geschieht. Die Zahl je Datei
    haelt das aus: Wer eine Stelle bereinigt, senkt sie; wer eine neue
    schreibt, hebt sie — und nur das ist rot.
    """
    if not ALTBESTAND.exists():
        return {}
    d = json.loads(ALTBESTAND.read_text(encoding="utf-8"))
    return {tuple(k.split("\t")): v for k, v in d.get("stellen", {}).items()}


def lade_ausnahmen(pfad: pathlib.Path) -> list[dict]:
    d = json.loads(pfad.read_text(encoding="utf-8"))
    regeln = []
    for r in d["regeln"]:
        # Ohne Begruendung keine Regel — dieselbe Vorschrift wie bei den
        # Ausnahmelisten des Kreislaufvergleichs. Eine Ausnahme ohne Grund
        # ist ein Filter, und ein Filter verdeckt genau das, wofuer die
        # Liste da ist.
        if not r.get("begruendung"):
            raise SystemExit(f"Ausnahme ohne Begruendung: {r.get('id') or r}")
        if not r.get("klasse"):
            raise SystemExit(f"Ausnahme ohne Klasse: {r.get('id') or r}")
        if not r.get("id"):
            raise SystemExit(f"Ausnahme ohne id: {r}")
        r = dict(r)
        r["_zeile"] = re.compile(r["zeile"], re.IGNORECASE) if r.get("zeile") else None
        r["_von"] = re.compile(r["von"]) if r.get("von") else None
        r["_bis"] = re.compile(r["bis"]) if r.get("bis") else None
        r["_abschnitt"] = re.compile(r["abschnitt"]) if r.get("abschnitt") else None
        r["_treffer"] = 0
        regeln.append(r)
    return regeln


# ------------------------------------------------------------- Fundstellen

def dateien_des_bereichs(kennung: str) -> list[pathlib.Path]:
    b = BEREICHE[kennung]
    pfade: list[pathlib.Path] = []
    for muster in b.get("glob", []):
        pfade += sorted(WURZEL.glob(muster))
    for name in b.get("dateien", []):
        p = WURZEL / name
        if not p.exists():
            raise SystemExit(f"Datei fehlt: {name}")
        pfade.append(p)
    # vendor/ ist fremder Quelltext und wird nie angefasst.
    ausser = set(b.get("ausser", []))
    return [p for p in pfade
            if "vendor" not in p.parts and str(p.relative_to(WURZEL)) not in ausser]


def _bloecke(zeilen: list[str], regel: dict) -> list[tuple[int, int]]:
    """Zeilenbereiche (1-basiert, einschliesslich), die eine Regel abdeckt."""
    if regel["_von"]:
        bereiche, offen = [], None
        for nr, z in enumerate(zeilen, 1):
            if offen is None and regel["_von"].search(z):
                offen = nr
            elif offen is not None and regel["_bis"] and regel["_bis"].search(z):
                bereiche.append((offen, nr))
                offen = None
        if offen is not None:
            bereiche.append((offen, len(zeilen)))
        return bereiche
    if regel["_abschnitt"]:
        # Markdown: von der passenden Ueberschrift bis zur naechsten
        # Ueberschrift gleicher oder hoeherer Ebene.
        bereiche, offen, ebene = [], None, 0
        for nr, z in enumerate(zeilen, 1):
            k = re.match(r"(#+)\s", z)
            if offen is not None and k and len(k.group(1)) <= ebene:
                bereiche.append((offen, nr - 1))
                offen = None
            if offen is None and k and regel["_abschnitt"].search(z):
                offen, ebene = nr, len(k.group(1))
        if offen is not None:
            bereiche.append((offen, len(zeilen)))
        return bereiche
    return [(1, len(zeilen))]


def passt(regel: dict, bereich: str, rel: str, zeilennr: int,
          zeile: str, muster_id: str, bloecke_zwischenspeicher: dict) -> bool:
    if regel.get("bereich") and regel["bereich"] != bereich:
        return False
    if regel.get("datei") and not fnmatch.fnmatch(rel, regel["datei"]):
        return False
    # EINE AUSNAHME OHNE `muster` GILT NUR FUER DIE KLASSE `luft` (PK-04/1c).
    #
    # Die 89 Ausnahmen ohne Musterfilter sind fuer die LUFTBEGRIFFE
    # geschrieben worden — sie sagen Dinge wie „dieser Abschnitt des
    # Handbuchs erklaert die Garmin-Uhr und darf `Flug` sagen". Mit den vier
    # neuen Regelklassen aus 1c waeren sie stillschweigend breiter geworden:
    # Derselbe Abschnitt haette dann auch eine fremde E-Mail-Adresse, einen
    # realen Ortsnamen und jede Rollenform gedeckt.
    #
    # GEMESSEN BEIM BAUEN: Ein angehaengter Satz mit `Nutzern`,
    # `admin@fremd.example` und `Kempten` landete im Block der Ausnahme
    # `handbuch-geraete-verlust-garmin` und war damit erklaert — drei neue
    # Treffer, kein Befund. Genau das soll hier nicht passieren.
    if regel.get("muster"):
        if muster_id not in regel["muster"]:
            return False
    elif KLASSE_VON.get(muster_id, "luft") != "luft":
        return False
    if regel["_zeile"] and not regel["_zeile"].search(zeile):
        return False
    if regel["_von"] or regel["_abschnitt"]:
        schluessel = (regel["id"], rel)
        bereiche = bloecke_zwischenspeicher.get(schluessel)
        if bereiche is None:
            return False
        if not any(a <= zeilennr <= e for a, e in bereiche):
            return False
    return True


def _art_fuer(b: dict, pfad: pathlib.Path) -> str:
    """Welcher Zerleger fuer diese Datei?

    Ein Bereich hat in der Regel genau eine Art. Bereich `e` hat zwei — die
    Ressourcen der Uhr sind XML, ihr Quelltext ist Monkey C. Dann steht unter
    `art` eine Zuordnung von Dateiendung auf Art. Eine Endung, die dort fehlt,
    ist ein Fehler in der Bereichsdefinition und keine stille Ausnahme: Sie
    faellt hier auf und nicht erst daran, dass eine Datei ungeprueft blieb.
    """
    art = b["art"]
    if isinstance(art, dict):
        if pfad.suffix not in art:
            raise SystemExit(
                f"Bereich ohne Art fuer {pfad.suffix}: {pfad}")
        return art[pfad.suffix]
    return art


def suche(kennung: str, muster: list[dict], fallen: list[dict],
          regeln: list[dict]) -> dict:
    b = BEREICHE[kennung]
    treffer_gesamt = 0
    offen: list[tuple[str, int, str, str]] = []
    erklaert: list[tuple[str, int, str, str, str]] = []
    fallen_zahl = {f["wort"]: 0 for f in fallen}
    fallen_durchgerutscht: list[str] = []
    dateien = dateien_des_bereichs(kennung)

    for pfad in dateien:
        rel = str(pfad.relative_to(WURZEL))
        roh = pfad.read_text(encoding="utf-8")
        text = zerlegen.ohne_kommentare(roh, _art_fuer(b, pfad))
        zeilen_roh = roh.splitlines()
        zeilen = text.splitlines()

        zwischenspeicher: dict = {}
        for r in regeln:
            if r["_von"] or r["_abschnitt"]:
                if r.get("datei") and not fnmatch.fnmatch(rel, r["datei"]):
                    continue
                zwischenspeicher[(r["id"], rel)] = _bloecke(zeilen_roh, r)

        for nr, zeile in enumerate(zeilen, 1):
            fallen_spannen = []
            for f in fallen:
                for tr in f["_re"].finditer(zeile):
                    fallen_zahl[f["wort"]] += 1
                    fallen_spannen.append((tr.start(), tr.end(), f["wort"]))
            for m in muster:
                for tr in m["_re"].finditer(zeile):
                    for a, e, wort in fallen_spannen:
                        if a <= tr.start() and tr.end() <= e:
                            fallen_durchgerutscht.append(
                                f"{rel}:{nr} — Muster {m['id']} traf in der Falle „{wort}“")
                    treffer_gesamt += 1
                    grund = None
                    for r in regeln:
                        if passt(r, kennung, rel, nr, zeile, m["id"], zwischenspeicher):
                            r["_treffer"] += 1
                            grund = r["id"]
                            break
                    zeigetext = zeilen_roh[nr - 1].strip() if nr <= len(zeilen_roh) else zeile.strip()
                    if len(zeigetext) > 150:
                        zeigetext = zeigetext[:147] + "…"
                    if grund:
                        erklaert.append((rel, nr, m["id"], zeigetext, grund))
                    else:
                        offen.append((rel, nr, m["id"], zeigetext))
    return {
        "kennung": kennung,
        "titel": b["titel"],
        "dateien": len(dateien),
        "gesamt": treffer_gesamt,
        "offen": offen,
        "erklaert": erklaert,
        "fallen": fallen_zahl,
        "durchgerutscht": fallen_durchgerutscht,
    }


# ------------------------------------------------------------------ Bericht

def bericht(ergebnisse: list[dict], regeln: list[dict], alle: bool,
            geprueft: list[str] | None = None) -> tuple[str, int]:
    aus: list[str] = []
    offen_gesamt = 0
    for e in ergebnisse:
        aus.append("")
        aus.append(f"Bereich ({e['kennung']}) — {e['titel']}")
        aus.append(f"  Dateien:                       {e['dateien']}")
        aus.append(f"  Treffer gesamt:                {e['gesamt']}")
        aus.append(f"  davon durch Ausnahmen erklärt: {len(e['erklaert'])}")
        stellen = len({(rel, nr) for rel, nr, _, _ in e["offen"]})
        aus.append(f"  außerhalb der Ausnahmen:       {len(e['offen'])} "
                   f"(in {stellen} Zeilen)")
        offen_gesamt += len(e["offen"])
        for rel, nr, mid, txt in e["offen"]:
            aus.append(f"    {rel}:{nr}  [{mid}]  {txt}")
        if alle and e["erklaert"]:
            aus.append("  erklärte Treffer:")
            for rel, nr, mid, txt, grund in e["erklaert"]:
                aus.append(f"    {rel}:{nr}  [{mid}]  ({grund})  {txt}")

    fallen_summe: dict[str, int] = {}
    durchgerutscht: list[str] = []
    for e in ergebnisse:
        for wort, n in e["fallen"].items():
            fallen_summe[wort] = fallen_summe.get(wort, 0) + n
        durchgerutscht += e["durchgerutscht"]

    aus.append("")
    aus.append("Teilstring-Fallen (Wörter, die ein Sperrwort enthalten, aber keines sind)")
    for wort, n in fallen_summe.items():
        aus.append(f"  {wort:<18} {n:>4} Vorkommen")
    aus.append(f"  als Treffer gezählt: {len(durchgerutscht)}")
    for d in durchgerutscht:
        aus.append(f"    {d}")

    # Bei einem Teillauf koennen Regeln nicht greifen, deren Dateien gar nicht
    # angesehen wurden. Sie als „ungenutzt" zu melden waere eine falsche
    # Auskunft — die Zahl gilt nur fuer den vollstaendigen Lauf.
    unbeteiligt = []
    if geprueft is not None:
        for r in regeln:
            if r["_treffer"] == 0 and r.get("datei") \
               and not any(fnmatch.fnmatch(f, r["datei"]) for f in geprueft):
                unbeteiligt.append(r)
    ungenutzt = [r for r in regeln if r["_treffer"] == 0 and r not in unbeteiligt]
    aus.append("")
    aus.append(f"Ausnahmen: {len(regeln)} Regeln, {len(regeln) - len(ungenutzt) - len(unbeteiligt)} "
               f"gegriffen, {len(ungenutzt)} ungenutzt")
    for r in ungenutzt:
        aus.append(f"    ungenutzt: {r['id']}  (Klasse {r['klasse']})")
    if unbeteiligt:
        aus.append(f"  {len(unbeteiligt)} Regeln betreffen Dateien, die dieser Teillauf "
                   f"nicht angesehen hat — sie zählen nicht mit:")
        for r in unbeteiligt:
            aus.append(f"    nicht geprüft: {r['id']}  ({r['datei']})")

    # NACH KLASSEN (PK-04/1c, E-PK-08). Eine Summe ueber fuenf Regelklassen
    # sagt nicht, welche davon gewachsen ist.
    je_klasse: dict[str, int] = {}
    for e in ergebnisse:
        for rel, nr, mid, txt in e["offen"]:
            k = KLASSE_VON.get(mid, "?")
            je_klasse[k] = je_klasse.get(k, 0) + 1
    if je_klasse:
        aus.append("")
        aus.append("Offene Treffer je Regelklasse")
        for k in sorted(je_klasse):
            aus.append(f"  {k:<12} {je_klasse[k]:>5}   {KLASSEN.get(k, '')}")

    # ALTBESTAND VERRECHNEN. Rot ist, was ueber dem Stand vom Einfuehrungstag
    # liegt — und ebenso, was darunter liegt, aber nicht ausgetragen wurde:
    # Eine bereinigte Stelle, die im Altbestand stehen bleibt, verdeckt die
    # naechste neue an derselben Datei.
    jetzt: dict[tuple, int] = {}
    for e in ergebnisse:
        for rel, nr, mid, txt in e["offen"]:
            jetzt[(rel, mid)] = jetzt.get((rel, mid), 0) + 1
    alt = lade_altbestand()
    neu_stellen, gesunken = [], []
    for schluessel, n in sorted(jetzt.items()):
        a = alt.get(schluessel, 0)
        if n > a:
            neu_stellen.append((schluessel, a, n))
    for schluessel, a in sorted(alt.items()):
        n = jetzt.get(schluessel, 0)
        if n < a:
            gesunken.append((schluessel, a, n))
    if alt:
        aus.append("")
        aus.append(f"Altbestand: {sum(alt.values())} Treffer in {len(alt)} "
                   f"(Datei, Muster)-Paaren — sie halten den Lauf NICHT auf.")
        for (rel, mid), a, n in neu_stellen:
            aus.append(f"  NEU   {rel}  [{mid}]  {a} -> {n}")
        for (rel, mid), a, n in gesunken:
            aus.append(f"  weniger geworden (Altbestand austragen): {rel}  [{mid}]  {a} -> {n}")
        aus.append(f"  neue Treffer: {len(neu_stellen)} · "
                   f"bereinigt und nicht ausgetragen: {len(gesunken)}")

    schlecht = (len(neu_stellen) + len(gesunken) + len(ungenutzt) + len(durchgerutscht)
                if alt else offen_gesamt + len(ungenutzt) + len(durchgerutscht))
    stellen_gesamt = len({(rel, nr) for e in ergebnisse for rel, nr, _, _ in e["offen"]})
    aus.append("")
    aus.append(f"Ergebnis: {offen_gesamt} Treffer außerhalb der Ausnahmen "
               f"(in {stellen_gesamt} Zeilen), "
               f"{len(ungenutzt)} ungenutzte Ausnahmen, "
               f"{len(durchgerutscht)} durchgerutschte Fallen.")
    return "\n".join(aus), (1 if schlecht else 0)


def main() -> int:
    p = argparse.ArgumentParser(description="Wortliste der Phase P2")
    p.add_argument("--bereich", choices=sorted(BEREICHE), action="append",
                   help="nur diesen Bereich prüfen (mehrfach möglich)")
    p.add_argument("--alle", action="store_true",
                   help="auch die durch Ausnahmen erklärten Treffer auflisten")
    p.add_argument("--probe", action="store_true",
                   help="Selbstprobe des Zerlegers fahren und beenden")
    p.add_argument("--sperrliste", default=str(HIER / "textprobe-sperrliste.json"))
    p.add_argument("--ausnahmen", default=str(HIER / "textprobe-ausnahmen.json"))
    p.add_argument("--bericht", help="Bericht zusätzlich in diese Datei schreiben")
    p.add_argument("--altbestand-schreiben", action="store_true",
                   help="den heutigen Stand als Altbestand festschreiben "
                        "(nur beim VOLLSTÄNDIGEN Lauf sinnvoll)")
    a = p.parse_args()

    if a.probe:
        gut, gesamt, fehler = zerlegen.selbstprobe()
        for f in fehler:
            melde(f)
        melde(f"Selbstprobe des Zerlegers: {gut}/{gesamt} bestanden.")
        return 0 if gut == gesamt else 2

    muster, fallen = lade_sperrliste(pathlib.Path(a.sperrliste))
    regeln = lade_ausnahmen(pathlib.Path(a.ausnahmen))
    kennungen = a.bereich or sorted(BEREICHE)

    melde(f"Sperrliste: {len(muster)} Muster, {len(fallen)} Fallen. "
          f"Ausnahmen: {len(regeln)} Regeln.")
    ergebnisse = [suche(k, muster, fallen, regeln) for k in kennungen]
    geprueft = [str(p.relative_to(WURZEL)) for k in kennungen
                for p in dateien_des_bereichs(k)]
    if a.altbestand_schreiben:
        if a.bereich:
            melde("--altbestand-schreiben verlangt den VOLLSTÄNDIGEN Lauf: "
                  "ein Teillauf sähe die anderen Bereiche nicht und trüge "
                  "deren Stellen aus.")
            return 2
        stellen: dict[str, int] = {}
        for e in ergebnisse:
            for rel, nr, mid, txt in e["offen"]:
                k = f"{rel}\t{mid}"
                stellen[k] = stellen.get(k, 0) + 1
        ALTBESTAND.write_text(json.dumps({
            "beschreibung": "Was am Tag der Einfuehrung schon dastand (E-PK-08). "
                            "Je (Datei, Muster) eine Zahl; rot ist nur, was DARUEBER "
                            "liegt — und was darunter liegt, ohne ausgetragen zu sein. "
                            "Erzeugt mit `--altbestand-schreiben`, nie von Hand.",
            "stand": "PK-04/1c",
            "summe": sum(stellen.values()),
            "stellen": dict(sorted(stellen.items())),
        }, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
        melde(f"Altbestand geschrieben: {sum(stellen.values())} Treffer in "
              f"{len(stellen)} (Datei, Muster)-Paaren → {ALTBESTAND}")
        return 0

    text, rc = bericht(ergebnisse, regeln, a.alle, geprueft)
    melde(text)
    if a.bericht:
        pathlib.Path(a.bericht).write_text(text + "\n", encoding="utf-8")
    return rc


if __name__ == "__main__":
    raise SystemExit(main())
