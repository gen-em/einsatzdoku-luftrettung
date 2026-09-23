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


def ist_ausgenommen(zeile: str, muster: dict, seite: str = "") -> str:
    """Name der greifenden Zeilenausnahme, sonst leer.

    EINE AUSNAHME DARF AUF SEITEN EINGESCHRAENKT WERDEN (`seiten`), und fuer
    manche ist das Pflicht: Das Muster einer Datum-Zeit-Zeile trifft sonst
    JEDE Seite der Anwendung — und macht den Vergleich genau dort blind, wo
    ein Formatierungsumbau geprueft werden soll. Wer eine solche Ausnahme
    ohne `seiten` eintraegt, schaltet die Messung ab und meldet eine Null.
    """
    for name, eintrag in muster.items():
        seiten = eintrag.get("seiten")
        if seiten and seite not in seiten:
            continue
        if re.search(eintrag["muster"], zeile):
            return name
    return ""


def seite_von(dateiname: str) -> str:
    """`46-betrieb-updates-1280.png` -> `46-betrieb-updates`.

    BEIDE ENDUNGEN, und das war ein Fehler: Hier stand nur `.png`. Fuer einen
    Textabzug (`…-1280.txt`) blieb der Name damit ungekuerzt, die
    Seitenbindung einer Ausnahme (`seiten`) lief ins Leere, und die Ausnahme
    meldete sich als tot — waehrend die Abweichung als offen gezaehlt wurde.
    Gefunden in Schritt 15 AP7 beim ersten seitengebundenen Eintrag.
    """
    stamm = dateiname
    for endung in (".png", ".txt"):
        if stamm.endswith(endung):
            stamm = stamm[:-len(endung)]
            break
    teile = stamm.rsplit("-", 1)
    return teile[0] if len(teile) == 2 and teile[1].isdigit() else stamm


def main() -> int:
    p = argparse.ArgumentParser(description="Bild- und Textvergleich zweier Bilderlaeufe")
    p.add_argument("vorher", nargs="?", help="Verzeichnis des weggesicherten Laufs")
    p.add_argument("nachher", nargs="?", default=str(VORGABE_NACHHER))
    p.add_argument("--erwartet", action="append", default=[],
                   help="Seitenname, dessen Abweichung in diesem Lauf beabsichtigt ist")
    p.add_argument("--werte", action="store_true",
                   help="die Zeilen mit anderem WERT einzeln auflisten (Laerm, kein Befund)")
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
                unterschiede.append((alt_z, neu_z))
            if unterschiede:
                zeilen_offen.append((name, unterschiede))
            else:
                gleiche_seiten += 1
        zeilen_zahl = sum(len(u) for _, u in zeilen_offen)
        print("  (Der Zeilenvergleich ist eine ZAHL, kein Befund: Zwischen zwei")
        print("   Laeufen aendern sich Werte zwangslaeufig — eine Datenbank waechst,")
        print("   ein Alter laeuft weiter. Der BEFUND steht im Formvergleich unten.)")
        print(f"  Seiten verglichen:           {len(t_gemeinsam):4d}")
        print(f"  ohne offene Abweichung:      {gleiche_seiten:4d}")
        print(f"  mit offener Abweichung:      {len(zeilen_offen):4d}  "
              f"({zeilen_zahl} Zeilen)")
        print(f"  nur im alten Lauf:           {len(t_nur_vor):4d}")
        print(f"  nur im neuen Lauf:           {len(t_nur_nach):4d}")
        if zeilen_offen and "--werte" in sys.argv:
            print("\n  ZEILEN MIT ANDEREM WERT (--werte):")
            for name, unterschiede in zeilen_offen[:40]:
                print(f"    {name}")
                for alt_z, neu_z in unterschiede[:4]:
                    print(f"      - {str(alt_z)[:100]}")
                    print(f"      + {str(neu_z)[:100]}")
                if len(unterschiede) > 4:
                    print(f"      … und {len(unterschiede) - 4} weitere Zeilen")
            if len(zeilen_offen) > 40:
                print(f"    … und {len(zeilen_offen) - 40} weitere Seiten")
        elif zeilen_offen:
            print("  (`--werte` zeigt sie einzeln)")
        text_befunde = len(t_nur_vor) + len(t_nur_nach)

        # ---- Teil 3: die FORM ---------------------------------------------
        print()
        print("-" * 74)
        print("Formvergleich — steht eine Zahl anders GESCHRIEBEN?")
        print("-" * 74)
        form_offen, form_zeilen, form_erklaert = [], 0, {}
        for name in t_gemeinsam:
            av = [form(z) for z in v_texte[name]]
            an = [form(z) for z in n_texte[name]]
            if av == an:
                continue
            unt = [(a_, n_) for a_, n_ in zip_laengst(av, an) if a_ != n_]
            offen_hier = []
            for a_, n_ in unt:
                seite = seite_von(name)
                treffer = ist_ausgenommen(a_ or "", zeilenmuster, seite) \
                    or ist_ausgenommen(n_ or "", zeilenmuster, seite)
                if treffer:
                    form_erklaert[treffer] = form_erklaert.get(treffer, 0) + 1
                else:
                    offen_hier.append((a_, n_))
            unt = offen_hier
            if unt:
                form_offen.append((name, unt))
                form_zeilen += len(unt)
        print(f"  Seiten verglichen:           {len(t_gemeinsam):4d}")
        print(f"  gleiche Form:                {len(t_gemeinsam) - len(form_offen):4d}")
        print(f"  ABWEICHENDE FORM:            {len(form_offen):4d}  ({form_zeilen} Zeilen)")
        for nm, zahl in sorted(form_erklaert.items()):
            print(f"  erklaert durch '{nm}':{' ' * max(1, 22 - len(nm))}{zahl:4d} Zeilen")
        tote = sorted(set(zeilenmuster) - set(form_erklaert))
        if tote:
            print("\n  ZEILENAUSNAHME OHNE TREFFER (die Liste waechst zu): "
                  + ", ".join(tote))
        form_zeilen += len(tote)
        if form_offen:
            print("\n  ABWEICHENDE SCHREIBWEISEN:")
            for name, unt in form_offen[:30]:
                print(f"    {name}")
                for a_, n_ in unt[:4]:
                    print(f"      - {str(a_)[:110]}")
                    print(f"      + {str(n_)[:110]}")
            if len(form_offen) > 30:
                print(f"    … und {len(form_offen) - 30} weitere Seiten")
        text_befunde += form_zeilen

    gesamt = befunde + text_befunde
    print("\n" + "=" * 74)
    if nicht_gemessen and gesamt == 0:
        print("Bild: keine unerklaerte Abweichung. TEXT: NICHT GEMESSEN.\n")
    elif gesamt == 0:
        print("Kein Pixel und kein Buchstabe hat sich unerklaert bewegt.\n")
    else:
        print(f"BEFUNDE: {gesamt}  (Bild {befunde}, Text {text_befunde})\n")
    return 0 if gesamt == 0 else 1


def form(zeile) -> str:
    """Die FORM einer Zeile: jede Ziffernfolge wird zu `#`.

    WOZU. Ein Formatierungsumbau darf die SCHREIBWEISE nicht aendern; die
    WERTE aendern sich zwischen zwei Laeufen ohnehin (eine Datenbank waechst,
    ein Alter laeuft weiter, eine Uhrzeit rueckt vor). Der Zeilenvergleich
    sieht beides und kann es nicht trennen — er meldete deshalb die
    Statusseite als abweichend, obwohl dort nur andere ZAHLEN standen.

    Die Form trennt es: aus „1,0 MB" und „1,3 MB" wird beide Male
    „#,# MB", aus „2,00 GB" und „2 GB" dagegen „#,## GB" und „# GB" — und
    genau das ist der Unterschied, den dieses Paket ausschliessen muss.
    Auch „gerade eben" gegen „vor 1 Minuten" bleibt sichtbar, weil die
    Woerter verschieden sind.

    WAS SIE NICHT SIEHT, und das ist scharf zu lesen: JEDE Aenderung
    INNERHALB einer Ziffernfolge. `\\d+` ist gierig — aus „5“ und
    aus „05“ wird dasselbe „#“. Eine FUEHRENDE NULL ist damit
    unsichtbar, nicht nur eine andere Rundung bei gleicher Stellenzahl.

    Gemessen am 22.09.2026 (Schritt 15 AP8d): Die Einsatzansicht schrieb
    „2h 5min“ und schreibt jetzt „2h 05min“ — auf acht Aufnahmen
    derselben Seite. Dieser Vergleich meldete trotzdem „488 von 496
    formgleich“, und das war richtig gerechnet und irrefuehrend gelesen.
    Gefunden wurde die Aenderung erst durch Auszaehlen der VERSCHIEDENEN
    Dauern ueber alle Abzuege: 33 vorher, 32 nachher.

    Was ein Trennzeichen betrifft, sieht sie dagegen sehr wohl: aus
    „1633 km“ und „1.633 km“ wird „# km“ und „#.# km“.

    Wer eine Stellenzahl pruefen will, zaehlt die verschiedenen
    Schreibweisen ueber alle Abzuege aus. Die Rechnungen je Funktion
    stehen im Pruefdokument.
    """
    return re.sub(r"\d+", "#", zeile if zeile is not None else "\x00")


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
    pruefe("seite_von: Textabzug wird genauso gekuerzt",
           seite_von("41-kontoseite-1280.txt"), "41-kontoseite")
    pruefe("form: andere Zahl, gleiche Form",
           form("1,0 MB") == form("1,3 MB"), True)
    pruefe("form: andere Stellenzahl wird sichtbar",
           form("2,00 GB") == form("2 GB"), False)
    pruefe("form: anderes Trennzeichen wird sichtbar",
           form("1.234,5 MB") == form("1,234.5 MB"), False)
    pruefe("form: andere Worte werden sichtbar",
           form("gerade eben") == form("vor 1 Minuten"), False)
    pruefe("zip_laengst: gleich lang",
           zip_laengst(["a", "b"], ["a", "c"]), [("a", "a"), ("b", "c")])
    pruefe("zip_laengst: rechts kuerzer",
           zip_laengst(["a", "b"], ["a"]), [("a", "a"), ("b", None)])
    muster = {"demo": {"muster": r"in etwa \d+ Minuten"}}
    pruefe("Zeilenausnahme greift",
           ist_ausgenommen("Zuruecksetzen in etwa 27 Minuten.", muster), "demo")
    pruefe("Zeilenausnahme greift NICHT auf fremdem Text",
           ist_ausgenommen("2,00 GB von 2,00 GB belegt.", muster), "")
    eng = {"nur-dort": {"muster": r"\d{2}:\d{2}", "seiten": ["41-kontoseite"]}}
    pruefe("Seitengebundene Ausnahme greift auf ihrer Seite",
           ist_ausgenommen("22.09.2026 10:13", eng, "41-kontoseite"), "nur-dort")
    pruefe("Seitengebundene Ausnahme greift NICHT auf anderer Seite",
           ist_ausgenommen("22.09.2026 10:13", eng, "10-tagesuebersicht"), "")
    print("=" * 74)
    print(f"Selbstprobe: {ok} von {ok + fehl}")
    return 0 if fehl == 0 else 1


if __name__ == "__main__":
    sys.exit(main())
