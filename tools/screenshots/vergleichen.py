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

ZEITABHAENGIGE SEITEN SIND DER GRUND FUER DIE AUSNAHMELISTE. Eine Seite, die
„vor 3 Minuten" zeigt, unterscheidet sich zwischen zwei Laeufen, ohne dass
jemand etwas geaendert haette. Solche Seiten stehen in `ausnahmen.json`
daneben — mit Begruendung, und nach derselben Regel wie ueberall im Projekt:
**Eine Ausnahme, die nichts mehr trifft, ist selbst ein Befund.** Sonst
waechst die Liste zu, und der Vergleich meldet eine Null, die nichts mehr
gemessen hat.

    python3 tools/screenshots/vergleichen.py <vorher> [<nachher>]
    python3 tools/screenshots/vergleichen.py <vorher> --erwartet 46-betrieb-updates

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


def seite_von(dateiname: str) -> str:
    """`46-betrieb-updates-1280.png` -> `46-betrieb-updates`."""
    stamm = dateiname[:-4] if dateiname.endswith(".png") else dateiname
    teile = stamm.rsplit("-", 1)
    return teile[0] if len(teile) == 2 and teile[1].isdigit() else stamm


def main() -> int:
    p = argparse.ArgumentParser(description="Bildvergleich zweier Bilderlaeufe")
    p.add_argument("vorher", help="Verzeichnis des weggesicherten Laufs")
    p.add_argument("nachher", nargs="?", default=str(VORGABE_NACHHER))
    p.add_argument("--erwartet", action="append", default=[],
                   help="Seitenname, dessen Abweichung in diesem Lauf beabsichtigt ist")
    a = p.parse_args()

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

    ausnahmen = {}
    if AUSNAHMEN.is_file():
        ausnahmen = json.loads(AUSNAHMEN.read_text("utf-8")).get("zeitabhaengig", {})

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
    print("\n" + "=" * 74)
    print("Kein Pixel hat sich unerklaert bewegt.\n" if befunde == 0
          else f"BEFUNDE: {befunde}\n")
    return 0 if befunde == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
