#!/usr/bin/env python3
"""Geräteklassen — welche Uhren muss man prüfen, und welche sind Wiederholung?

Beantwortet eine Frage mit einer Liste statt mit einem Bauchgefühl:

    > Welche der über 170 Connect-IQ-Geräte verhalten sich für DIESE App
    > unterschiedlich, und welches eine steht stellvertretend für welche Gruppe?

Die App unterscheidet Geräte nicht nach Katalog, sondern nach vier Achsen, die
sie tatsächlich spürt. Alles andere — Farbtiefe, Displaytechnik, Teilenummern,
Preisklasse — berührt sie nicht:

| Achse            | Wo im Code                        | Warum sie eine Klasse trennt |
|------------------|-----------------------------------|------------------------------|
| Displayform      | `Ui.chordW()` rechnet eine Sehne  | die Formel nimmt ein RUNDES Display an |
| Schwelle 320 px  | `Ui.fontHint()`                   | springt dort von FONT_TINY auf FONT_XTINY |
| Eingabe          | `DeviceProfile.HAS_UP_DOWN`       | entscheidet, ob die App bedienbar ist |
| Speicher         | `appTypes[watchApp].memoryLimit`  | die App belegt im Leerlauf rund 54 kB |

Die Displayhöhe **innerhalb** einer Klasse ist dagegen keine eigene Klasse:
`Ui.s()` und `Ui.pct()` skalieren stetig mit `dc.getHeight()`. Deshalb wird je
Klasse nicht ein Gerät gezogen, sondern eine Spanne — kleinste und größte Höhe
zuerst, dann aufgefüllt (`--vertreter`).

Aufruf:

    python3 geraeteklassen.py ~/.Garmin/ConnectIQ/Devices
    python3 geraeteklassen.py <verz> --vertreter 5 --liste vertreter.txt
    python3 geraeteklassen.py <verz> --alle          # ohne Auswahlregeln

Rückgabewert: 0 = Auswertung erstellt · 1 = keine lesbaren Gerätedateien.
"""
import argparse
import collections
import json
import pathlib
import re
import sys

# --- Auswahlregeln (Stand 30.08.2026, abgestimmt) ---------------------------
#
# Die Regeln beantworten "welche Geräte wollen wir überhaupt unterstützen" —
# eine Produktentscheidung, keine technische. Sie stehen hier, damit sie
# nachvollziehbar und änderbar sind statt in einem Kopf.

MIN_API = 3.1          # minApiLevel aus watch/manifest.xml
MIN_SPEICHER_KB = 128  # darunter nur die Instinct-Reihe mit 96 kB; die App
                       # belegt im Leerlauf schon 54 kB, und der Punktpuffer
                       # wächst im Dienst.
GROESSTE_KLASSEN = 4   # die vier größten Klassen vollständig abdecken

# "Fenix ab Fenix 5": fenix3/fenix3_hr sind API 1.4 und fallen ohnehin heraus.
FENIX_AUSGESCHLOSSEN = {"fenix3", "fenix3_hr"}

# "Forerunner ab 2018": fr645 und fr935 sind API 3.1 und stammen beide von
# Anfang 2018 — die API-Grenze fällt hier mit der Jahresgrenze zusammen. Ein
# Erscheinungsdatum steht in den Gerätedateien NICHT, deshalb dieser Weg.
FR_MIN_API = 3.1


def lies_geraete(wurzel):
    """Liest je Gerät compiler.json und simulator.json ein."""
    geraete = []
    for d in sorted(wurzel.iterdir()):
        if not d.is_dir():
            continue
        cj, sj = d / "compiler.json", d / "simulator.json"
        if not (cj.exists() and sj.exists()):
            continue
        try:
            c = json.loads(cj.read_text())
            s = json.loads(sj.read_text())
        except (json.JSONDecodeError, OSError):
            continue

        m = re.search(r"([\d.]+)", c.get("deviceGroup", ""))
        familie = c.get("deviceFamily", "")
        res = c.get("resolution", {})
        disp = s.get("display", {}) if isinstance(s.get("display"), dict) else {}
        speicher = next((a.get("memoryLimit", 0) for a in c.get("appTypes", [])
                         if a.get("type") == "watchApp"), 0)

        geraete.append({
            "id": d.name,
            "name": c.get("displayName", ""),
            "art": c.get("webDocDeviceGroup", ""),
            "familie": familie,
            "form": familie.split("-")[0] if "-" in familie else "?",
            "breite": res.get("width", 0),
            "hoehe": res.get("height", 0),
            "touch": bool(disp.get("isTouch", False)),
            "tasten": len(s.get("keys") or []),
            "speicher_kb": speicher // 1024,
            "api": float(m.group(1)) if m else 0.0,
            "icon": (c.get("launcherIcon", {}).get("width"),
                     c.get("launcherIcon", {}).get("height")),
        })
    return geraete


def klasse_von(g):
    """Die vier Achsen, die diese App unterscheidet."""
    return (g["form"],
            "hoch" if g["hoehe"] >= 320 else "niedrig",
            g["touch"],
            g["tasten"])


def waehle_aus(geraete):
    """Wendet die Auswahlregeln an. Gibt (auswahl, begruendung) zurück."""
    # Grundmenge: Uhren, die die App überhaupt tragen können.
    tauglich = [g for g in geraete
                if g["art"] == "Watches/Wearables"
                and g["api"] >= MIN_API
                and g["speicher_kb"] >= MIN_SPEICHER_KB]

    klassen = collections.defaultdict(list)
    for g in tauglich:
        klassen[klasse_von(g)].append(g)

    # Regel 1 — die N größten Klassen vollständig
    nach_groesse = sorted(klassen, key=lambda k: (-len(klassen[k]), str(k)))
    gross = set(nach_groesse[:GROESSTE_KLASSEN])

    # Regel 2 — Fenix ab Fenix 5 (samt ihrer Klassen)
    fenix = {g["id"] for g in tauglich
             if g["id"].startswith("fenix") and g["id"] not in FENIX_AUSGESCHLOSSEN}

    # Regel 3 — Forerunner ab 2018
    forerunner = {g["id"] for g in tauglich
                  if g["id"].startswith("fr") and g["api"] >= FR_MIN_API}

    auswahl, grund = [], {}
    for g in tauglich:
        gruende = []
        if klasse_von(g) in gross:
            gruende.append("große Klasse")
        if g["id"] in fenix:
            gruende.append("Fenix ab 5")
        if g["id"] in forerunner:
            gruende.append("Forerunner ab 2018")
        if gruende:
            auswahl.append(g)
            grund[g["id"]] = ", ".join(gruende)
    return tauglich, auswahl, grund


def vertreter_je_klasse(gs, anzahl):
    """Zieht Vertreter: erst die Höhen-Extreme, dann gleichmäßig aufgefüllt.

    Die Extreme zuerst, weil `Ui.s()`/`Ui.pct()` mit der Höhe skalieren — dort
    zeigen sich Layoutfehler, nicht in der Mitte. Danach wird die Spanne
    gleichmäßig abgetastet, damit auch Zwischengrößen vorkommen.
    """
    sortiert = sorted(gs, key=lambda g: (g["hoehe"], g["id"]))
    if len(sortiert) <= anzahl:
        return sortiert
    gewaehlt = [sortiert[0], sortiert[-1]]
    rest = sortiert[1:-1]
    if rest and anzahl > 2:
        schritt = len(rest) / (anzahl - 2)
        for i in range(anzahl - 2):
            k = rest[min(int(i * schritt), len(rest) - 1)]
            if k not in gewaehlt:
                gewaehlt.append(k)
    return sorted(gewaehlt, key=lambda g: (g["hoehe"], g["id"]))


# --- Vorschläge für manifest.xml und monkey.jungle --------------------------
#
# Ein Gerät, das nicht im Manifest steht, lässt sich nicht übersetzen
# ("Target device id ... is not enabled in the application manifest file").
# Die Blöcke werden deshalb ausgegeben, aber NICHT eingetragen: Das Manifest
# ist eine Zusage — jedes Gerät darin muss jemand geprüft haben.

TASTEN_FUER_UP_DOWN = 4   # ab so vielen Tasten hat das Gerät eigene UP/DOWN


def profil_von(g):
    """Welches Eingabeprofil braucht das Gerät?

    Entscheidend ist DeviceProfile.HAS_UP_DOWN: Geräte mit eigenen UP/DOWN-
    Tasten bekommen das Fünf-Tasten-Profil, die übrigen das Drei-Tasten-Profil
    mit Touch. Wer das falsch zuordnet, merkt es NICHT beim Übersetzen — die
    App baut sauber und ist auf dem Gerät unbedienbar.
    """
    return "source-tasten5" if g["tasten"] >= TASTEN_FUER_UP_DOWN else "source-tasten3"


# Grundordner watch/resources: Launcher-Symbol 40 px (51 der 99 Geräte
# verlangen genau das) und Bildmarke 70 px.
ICON_GRUND = 40
KACHEL_GRUND = 70
KACHEL_BEZUG = 260      # fenix6pro, Bezugsgerät von Ui.s()


def kachel_von(hoehe):
    """Kachelhöhe der Bildmarke zu einer Displayhöhe.

    Die Bildmarke wird mit dc.drawBitmap 1:1 gezeichnet und kann `Ui.s()`
    als Bitmap nicht folgen. Vorgerasterte Stufen holen das nach: Die Kachel
    behält das Verhältnis des Bezugsgeräts, 70/260 — rund 27 % der
    Displayhöhe. Die beiden Größen, die es vor der Staffelung schon gab,
    liegen exakt auf dieser Geraden (260 → 70, 390 → 105).
    """
    return round(KACHEL_GRUND * hoehe / KACHEL_BEZUG)


def zeige_bloecke(auswahl, icon_von):
    vorgabe = "source-tasten5"   # base.sourcePath im Jungle
    print("\n=== Vorschlag: Geräte für watch/manifest.xml ===")
    for g in sorted(auswahl, key=lambda g: g["id"]):
        print(f'            <iq:product id="{g["id"]}"/>')

    print("\n=== Vorschlag: Zeilen für watch/monkey.jungle ===")
    print(f"# Geräte ohne eigene Zeile erben base.sourcePath ({vorgabe}).")
    print("# Aufgeführt sind deshalb nur die Abweichler.")
    abweichler = [g for g in sorted(auswahl, key=lambda g: g["id"])
                  if profil_von(g) != vorgabe]
    if not abweichler:
        print("# (keine — alle ausgewählten Geräte tragen eigene UP/DOWN-Tasten)")
    for g in abweichler:
        print(f'{g["id"]}.sourcePath = source;{profil_von(g)}')

    groessen = collections.Counter(icon_von.get(g["id"]) for g in auswahl)
    print("\n=== Launcher-Icons: welche Größen die Auswahl verlangt ===")
    print("# Eine unpassende Größe ist nur eine WARNUNG — monkeyc skaliert.")
    print("# Hochskaliert wird das Symbol allerdings unscharf.")
    for gr, n in sorted(groessen.items(), key=lambda x: -x[1]):
        if gr:
            print(f"#   {gr[0]}x{gr[1]:<4} {n:>3} Geräte")

    print("\n=== Vorschlag: resourcePath-Zeilen für watch/monkey.jungle ===")
    print(f"# Grundordner trägt Symbol {ICON_GRUND} px und Kachel"
          f" {KACHEL_GRUND} px (Bezugsgerät {KACHEL_BEZUG} px).")
    print("# Aufgeführt sind nur Geräte, die davon abweichen.")
    for g in sorted(auswahl, key=lambda g: (g["hoehe"], g["id"])):
        teile = []
        ic = (icon_von.get(g["id"]) or (None,))[0]
        if ic and ic != ICON_GRUND:
            teile.append(f"resources-icon{ic}")
        ka = kachel_von(g["hoehe"])
        if ka != KACHEL_GRUND:
            teile.append(f"resources-marke{ka}")
        if teile:
            print(f'{g["id"]}.resourcePath = resources;' + ";".join(teile))


def main():
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("verzeichnis", help="~/.Garmin/ConnectIQ/Devices")
    p.add_argument("--bloecke", action="store_true",
                   help="Vorschläge für manifest.xml und monkey.jungle ausgeben")
    p.add_argument("--vertreter", type=int, default=5,
                   help="Vertreter je Klasse für den Simulatorlauf (Vorgabe 5)")
    p.add_argument("--liste", metavar="DATEI",
                   help="Vertreter zeilenweise in eine Datei schreiben")
    p.add_argument("--alle-liste", metavar="DATEI",
                   help="alle ausgewählten Geräte zeilenweise schreiben (Stufe I)")
    p.add_argument("--alle", action="store_true",
                   help="Auswahlregeln übergehen, alle tauglichen Uhren zeigen")
    a = p.parse_args()

    wurzel = pathlib.Path(a.verzeichnis).expanduser()
    if not wurzel.is_dir():
        print(f"FEHLER: {wurzel} ist kein Verzeichnis", file=sys.stderr)
        return 1

    geraete = lies_geraete(wurzel)
    if not geraete:
        print(f"FEHLER: keine lesbaren Gerätedateien in {wurzel}", file=sys.stderr)
        return 1

    tauglich, auswahl, grund = waehle_aus(geraete)
    if a.alle:
        auswahl, grund = tauglich, {g["id"]: "alle" for g in tauglich}

    print(f"Gerätedateien gelesen:           {len(geraete)}")
    print(f"davon Uhren, API ≥ {MIN_API}, ≥ {MIN_SPEICHER_KB} kB: {len(tauglich)}")
    print(f"nach Auswahlregeln:              {len(auswahl)}\n")

    klassen = collections.defaultdict(list)
    for g in auswahl:
        klassen[klasse_von(g)].append(g)

    print(f"Klassen in der Auswahl: {len(klassen)}\n")
    kopf = f"{'Form':12} {'Höhe':8} {'Touch':6} {'Tast':>4} {'Anz':>4}  Vertreter"
    print(kopf)
    print("-" * max(len(kopf), 78))

    alle_vertreter = []
    for k in sorted(klassen, key=lambda k: (-len(klassen[k]), str(k))):
        gs = klassen[k]
        v = vertreter_je_klasse(gs, a.vertreter)
        alle_vertreter.extend(x["id"] for x in v)
        namen = ", ".join(f"{x['id']}({x['hoehe']})" for x in v)
        print(f"{k[0]:12} {k[1]:8} {str(k[2]):6} {k[3]:>4} {len(gs):>4}  {namen}")

    print(f"\nVertreter gesamt: {len(alle_vertreter)}  "
          f"(Stufe II)   ·   Auswahl gesamt: {len(auswahl)}  (Stufe I)")

    knapp = sorted([g for g in auswahl if g["speicher_kb"] < 200],
                   key=lambda g: g["speicher_kb"])
    if knapp:
        print(f"\nKnapper Speicher (App belegt im Leerlauf ~54 kB):")
        for g in knapp[:10]:
            print(f"  {g['id']:24} {g['speicher_kb']:>5} kB   {g['familie']}")

    if a.bloecke:
        zeige_bloecke(auswahl, {g["id"]: g["icon"] for g in auswahl})

    if a.liste:
        pathlib.Path(a.liste).write_text("\n".join(alle_vertreter) + "\n")
        print(f"\nVertreterliste geschrieben: {a.liste}")
    if a.alle_liste:
        pathlib.Path(a.alle_liste).write_text(
            "\n".join(sorted(g["id"] for g in auswahl)) + "\n")
        print(f"Auswahlliste geschrieben:   {a.alle_liste}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
