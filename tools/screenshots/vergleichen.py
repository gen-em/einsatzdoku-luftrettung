# -*- coding: utf-8 -*-
"""Bildvergleich — haelt zwei Laeufe des Bilderlaufs gegeneinander.

WOZU. Der Bilderlauf selbst misst Ueberlauf, Konsolenfehler und Knopfhoehen.
Er beantwortet damit NICHT die Frage, die ein Umbau ohne beabsichtigte
Gestaltungsaenderung stellt: **Hat sich ein Pixel bewegt?** Ein Paket, das
Formatierung zentralisiert (Schritt 15 AP7, F-ZE-3), steht und faellt mit
dieser Frage — 0 Ueberlauf und 0 Konsolenfehler meldet auch eine Seite, die
seit gestern ein anderes Datum zeigt.

DIE FALLE, DIE ES ZU VERMEIDEN GILT, steht in der LIESMICH daneben: **Jeder
Lauf loescht den vorigen** (`rmSync` auf `ausgabe/`). Wer vergleichen will,
sichert den ersten Lauf weg, BEVOR er die Aenderung baut. Danach ist es zu
spaet, und keine Zahl bringt ihn zurueck.

ZWEI VERGLEICHE, UND DER WICHTIGERE IST DER TEXT.

  BILD   SHA-256 je Einzelbild. Streng, aber laut: Er meldet jede
         Schriftrasterung mit.
  TEXT   `document.body.innerText` je Seite und Breite, zeilenweise
         verglichen. Das ist die Frage, die ein Formatierungsumbau stellt:
         Steht ein Buchstabe anders da als vorher?

WARUM BEIDE. Gemessen am 22.09.2026 auf **unveraendertem** Code: **303 von 496
Bildern** wichen ab. Ursache war nicht die Anwendung, sondern der Countdown im
Demo-Banner („in etwa 43 188 Minuten"), der auf jeder Seite des Demo-Kontos
steht. Ein Bildvergleich kann so eine Zeile nicht benennen — er sieht nur
Pixel. Der Textvergleich sieht die Zeile, und die Ausnahmeliste kann sie
ausnehmen, ohne die uebrigen 1 200 Zeilen derselben Seite mit auszunehmen.

Der Bildvergleich bleibt trotzdem: Er findet, was kein Text ist — eine
verrutschte Spalte, ein anderer Abstand, eine Farbe.

ZEITABHAENGIGES IST DER GRUND FUER DIE AUSNAHMELISTE. `ausnahmen.json` fuehrt
zwei Abschnitte: `zeitabhaengig` nimmt ganze SEITEN vom Bildvergleich aus,
`zeilenmuster` nimmt einzelne ZEILEN vom Textvergleich aus. Beide mit
Begruendung, und nach derselben Regel wie ueberall im Projekt: **Eine
Ausnahme, die nichts mehr trifft, ist selbst ein Befund.** Sonst waechst die
Liste zu, und der Vergleich meldet eine Null, die nichts mehr gemessen hat.

    python3 tools/screenshots/vergleichen.py <vorher> [<nachher>]
    python3 tools/screenshots/vergleichen.py <vorher> --erwartet 46-betrieb-updates
    python3 tools/screenshots/vergleichen.py <vorher> --nur-text
    python3 tools/screenshots/vergleichen.py --selbstprobe

`--erwartet` nennt Seiten, deren Abweichung in DIESEM Lauf beabsichtigt ist
(Praefix des Dateinamens ohne Breite). Sie werden gezaehlt und benannt, aber
nicht als Befund gewertet — anders als die Ausnahmeliste, die dauerhaft gilt.

Rueckgabe 0 = keine unerklaerte Abweichung, 1 = Abweichungen, 2 = nicht
gelaufen.
"""
import argparse
import hashlib
import json
import pathlib
import re
import sys

WURZEL = pathlib.Path(__file__).resolve().parents[2]
VORGABE_NACHHER = WURZEL / "tools" / "screenshots" / "ausgabe"
AUSNAHMEN = pathlib.Path(__file__).resolve().parent / "ausnahmen.json"


def pruefsummen(verzeichnis: pathlib.Path) -> dict:
    """SHA-256 je Einzelbild, Schluessel ist der Dateiname."""
    ordner = verzeichnis / "einzeln"
    if not ordner.is_dir():
        return {}
    werte = {}
    for p in sorted(ordner.glob("*.png")):
        werte[p.name] = hashlib.sha256(p.read_bytes()).hexdigest()
    return werte


def texte(verzeichnis: pathlib.Path) -> dict:
    """Sichtbarer Text je Seite und Breite, in Zeilen zerlegt."""
    ordner = verzeichnis / "texte"
    if not ordner.is_dir():
        return {}
    werte = {}
    for p in sorted(ordner.glob("*.txt")):
        werte[p.name] = p.read_text("utf-8").splitlines()
    return werte


def ist_ausgenommen(zeile: str, muster: dict) -> str:
    """Name der greifenden Zeilenausnahme, sonst leer."""
    for name, eintrag in muster.items():
        if re.search(eintrag["muster"], zeile):
            return name
    return ""


def seite_von(dateiname: str) -> str:
    """`46-betrieb-updates-1280.png` -> `46-betrieb-updates`."""
    stamm = dateiname[:-4] if dateiname.endswith(".png") else dateiname
    teile = stamm.rsplit("-", 1)
    return teile[0] if len(teile) == 2 and teile[1].isdigit() else stamm


def main() -> int:
    p = argparse.ArgumentParser(description="Bild- und Textvergleich zweier Bilderlaeufe")
    p.add_argument("vorher", nargs="?", help="Verzeichnis des weggesicherten Laufs")
    p.add_argument("nachher", nargs="?", default=str(VORGABE_NACHHER))
    p.add_argument("--erwartet", action="append", default=[],
                   help="Seitenname, dessen Abweichung in diesem Lauf beabsichtigt ist")
    p.add_argument("--nur-text", action="store_true",
                   help="nur den Textvergleich fahren (der Bildvergleich ist laut, siehe Kopf)")
    p.add_argument("--selbstprobe", action="store_true",
                   help="haelt das Werkzeug gegen Faelle mit Sollwert")
    a = p.parse_args()

    if a.selbstprobe:
        return selbstprobe()
    if not a.vorher:
        sys.stderr.write("Ohne <vorher> geht es nicht.\n")
        return 2

    vor = pathlib.Path(a.vorher)
    nach = pathlib.Path(a.nachher)
    for v in (vor, nach):
        if not (v / "einzeln").is_dir():
            sys.stderr.write(f"{v}/einzeln nicht gefunden\n")
            return 2

    v_summen = pruefsummen(vor)
    n_summen = pruefsummen(nach)
    if not v_summen or not n_summen:
        sys.stderr.write("Mindestens ein Lauf enthaelt keine Einzelbilder\n")
        return 2

    ausnahmen, zeilenmuster = {}, {}
    if AUSNAHMEN.is_file():
        roh = json.loads(AUSNAHMEN.read_text("utf-8"))
        ausnahmen = roh.get("zeitabhaengig", {})
        zeilenmuster = roh.get("zeilenmuster", {})

    nur_vor = sorted(set(v_summen) - set(n_summen))
    nur_nach = sorted(set(n_summen) - set(v_summen))
    gemeinsam = sorted(set(v_summen) & set(n_summen))

    gleich, anders = [], []
    for name in gemeinsam:
        (gleich if v_summen[name] == n_summen[name] else anders).append(name)

    erklaert_ausnahme, erklaert_erwartet, offen = [], [], []
    for name in anders:
        s = seite_von(name)
        if s in ausnahmen:
            erklaert_ausnahme.append(name)
        elif s in a.erwartet:
            erklaert_erwartet.append(name)
        else:
            offen.append(name)

    # Eine Ausnahme, die nichts trifft, ist selbst ein Befund.
    getroffen = {seite_von(n) for n in erklaert_ausnahme}
    tote_ausnahmen = sorted(set(ausnahmen) - getroffen)
    tote_erwartungen = sorted(set(a.erwartet) - {seite_von(n) for n in erklaert_erwartet})

    print("Bildvergleich — zwei Laeufe des Bilderlaufs")
    print("=" * 74)
    print(f"  vorher:   {vor}  ({len(v_summen)} Bilder)")
    print(f"  nachher:  {nach}  ({len(n_summen)} Bilder)")
    print()
    print(f"  verglichen:                  {len(gemeinsam):4d}")
    print(f"  Bit fuer Bit gleich:         {len(gleich):4d}")
    print(f"  abweichend:                  {len(anders):4d}")
    print(f"    davon zeitabhaengig:       {len(erklaert_ausnahme):4d}"
          + (f"  -> {', '.join(sorted(getroffen))}" if getroffen else ""))
    print(f"    davon beabsichtigt:        {len(erklaert_erwartet):4d}"
          + (f"  -> {', '.join(sorted({seite_von(n) for n in erklaert_erwartet}))}"
             if erklaert_erwartet else ""))
    print(f"    UNERKLAERT:                {len(offen):4d}")
    print(f"  nur im alten Lauf:           {len(nur_vor):4d}")
    print(f"  nur im neuen Lauf:           {len(nur_nach):4d}")

    if offen:
        print("\n  UNERKLAERTE ABWEICHUNGEN:")
        for name in offen:
            print(f"    {name}")
    if nur_vor:
        print("\n  nur im alten Lauf: " + ", ".join(nur_vor[:20])
              + (" …" if len(nur_vor) > 20 else ""))
    if nur_nach:
        print("\n  nur im neuen Lauf: " + ", ".join(nur_nach[:20])
              + (" …" if len(nur_nach) > 20 else ""))
    if tote_ausnahmen:
        print("\n  AUSNAHME OHNE TREFFER (die Liste waechst zu): "
              + ", ".join(tote_ausnahmen))
    if tote_erwartungen:
        print("\n  ERWARTUNG OHNE TREFFER: " + ", ".join(tote_erwartungen))

    befunde = len(offen) + len(nur_vor) + len(nur_nach) \
        + len(tote_ausnahmen) + len(tote_erwartungen)
    if a.nur_text:
        befunde = 0
        print("\n  (--nur-text: der Bildvergleich zaehlt in diesem Lauf nicht mit)")

    # ---- Teil 2: der Text -------------------------------------------------
    v_texte, n_texte = texte(vor), texte(nach)
    print()
    print("-" * 74)
    print("Textvergleich — steht ein Buchstabe anders da?")
    print("-" * 74)
    if not v_texte or not n_texte:
        print("  Kein Textabzug vorhanden. `texte/` entsteht erst seit Schritt 15 AP7;")
        print("  ein aelterer weggesicherter Lauf hat ihn nicht. NICHT GEMESSEN.")
        text_befunde = 0
        nicht_gemessen = True
    else:
        nicht_gemessen = False
        t_gemeinsam = sorted(set(v_texte) & set(n_texte))
        t_nur_vor = sorted(set(v_texte) - set(n_texte))
        t_nur_nach = sorted(set(n_texte) - set(v_texte))
        gleiche_seiten, zeilen_offen, zeilen_erklaert = 0, [], {}
        for name in t_gemeinsam:
            av, an = v_texte[name], n_texte[name]
            if av == an:
                gleiche_seiten += 1
                continue
            unterschiede = []
            for alt_z, neu_z in zip_laengst(av, an):
                if alt_z == neu_z:
                    continue
                treffer = ist_ausgenommen(alt_z or "", zeilenmuster) \
                    or ist_ausgenommen(neu_z or "", zeilenmuster)
                if treffer:
                    zeilen_erklaert[treffer] = zeilen_erklaert.get(treffer, 0) + 1
                else:
                    unterschiede.append((alt_z, neu_z))
            if unterschiede:
                zeilen_offen.append((name, unterschiede))
            else:
                gleiche_seiten += 1
        zeilen_zahl = sum(len(u) for _, u in zeilen_offen)
        print(f"  Seiten verglichen:           {len(t_gemeinsam):4d}")
        print(f"  ohne offene Abweichung:      {gleiche_seiten:4d}")
        print(f"  mit offener Abweichung:      {len(zeilen_offen):4d}  "
              f"({zeilen_zahl} Zeilen)")
        for name, zahl in sorted(zeilen_erklaert.items()):
            print(f"  erklaert durch '{name}':      {zahl:4d} Zeilen")
        print(f"  nur im alten Lauf:           {len(t_nur_vor):4d}")
        print(f"  nur im neuen Lauf:           {len(t_nur_nach):4d}")
        tote_zeilenmuster = sorted(set(zeilenmuster) - set(zeilen_erklaert))
        if zeilen_offen:
            print("\n  OFFENE TEXTABWEICHUNGEN:")
            for name, unterschiede in zeilen_offen[:40]:
                print(f"    {name}")
                for alt_z, neu_z in unterschiede[:4]:
                    print(f"      - {str(alt_z)[:100]}")
                    print(f"      + {str(neu_z)[:100]}")
                if len(unterschiede) > 4:
                    print(f"      … und {len(unterschiede) - 4} weitere Zeilen")
            if len(zeilen_offen) > 40:
                print(f"    … und {len(zeilen_offen) - 40} weitere Seiten")
        if tote_zeilenmuster:
            print("\n  ZEILENAUSNAHME OHNE TREFFER: " + ", ".join(tote_zeilenmuster))
        text_befunde = zeilen_zahl + len(t_nur_vor) + len(t_nur_nach) \
            + len(tote_zeilenmuster)

    gesamt = befunde + text_befunde
    print("\n" + "=" * 74)
    if nicht_gemessen and gesamt == 0:
        print("Bild: keine unerklaerte Abweichung. TEXT: NICHT GEMESSEN.\n")
    elif gesamt == 0:
        print("Kein Pixel und kein Buchstabe hat sich unerklaert bewegt.\n")
    else:
        print(f"BEFUNDE: {gesamt}  (Bild {befunde}, Text {text_befunde})\n")
    return 0 if gesamt == 0 else 1


def zip_laengst(a: list, b: list):
    """Beide Listen zeilenweise nebeneinander, die kuerzere mit None aufgefuellt.

    KEIN ECHTER DIFF, und das ist Absicht: Eine eingefuegte Zeile verschiebt
    hier alles dahinter und erzeugt lauter Abweichungen. Das ist die richtige
    Lautstaerke — ein Umbau, der keinen Buchstaben aendern darf, fuegt auch
    keine Zeile ein. Wer einen echten Diff braucht, hat ein anderes Problem.
    """
    n = max(len(a), len(b))
    return [(a[i] if i < len(a) else None, b[i] if i < len(b) else None)
            for i in range(n)]


def selbstprobe() -> int:
    """Findet das Werkzeug ueberhaupt etwas? (CLAUDE.md 6)"""
    ok = fehl = 0

    def pruefe(was, ist, soll):
        nonlocal ok, fehl
        gut = ist == soll
        print(f"  {'ok  ' if gut else 'FEHL'}  {was:<52} erwartet {soll!r}, gemessen {ist!r}")
        if gut:
            ok += 1
        else:
            fehl += 1

    print("Selbstprobe des Bild- und Textvergleichs")
    print("=" * 74)
    pruefe("seite_von: Breite wird abgeschnitten",
           seite_von("46-betrieb-updates-1280.png"), "46-betrieb-updates")
    pruefe("seite_von: Name ohne Breite bleibt",
           seite_von("bericht.md"), "bericht.md")
    pruefe("seite_von: Seitenname mit Ziffer am Ende",
           seite_von("02a-registrieren-360.png"), "02a-registrieren")
    pruefe("zip_laengst: gleich lang",
           zip_laengst(["a", "b"], ["a", "c"]), [("a", "a"), ("b", "c")])
    pruefe("zip_laengst: rechts kuerzer",
           zip_laengst(["a", "b"], ["a"]), [("a", "a"), ("b", None)])
    muster = {"demo": {"muster": r"in etwa \d+ Minuten"}}
    pruefe("Zeilenausnahme greift",
           ist_ausgenommen("Zuruecksetzen in etwa 27 Minuten.", muster), "demo")
    pruefe("Zeilenausnahme greift NICHT auf fremdem Text",
           ist_ausgenommen("2,00 GB von 2,00 GB belegt.", muster), "")
    print("=" * 74)
    print(f"Selbstprobe: {ok} von {ok + fehl}")
    return 0 if fehl == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
