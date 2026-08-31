#!/usr/bin/env python3
"""Soll-Zahlen der Ausdünnung nachrechnen — unabhängig von der App.

WOZU. Die Abnahme von B3 verlangt: „synthetischer Positionsstrom → Punktfolge
nach der 15 m/10 s-Regel, Soll-Zahlen je Strom im Prüfprotokoll". Eine Zahl,
die aus derselben Umsetzung stammt, die sie belegen soll, belegt nichts. Dieses
Skript rechnet sie deshalb **zweimal unabhängig** nach:

  1. **analytisch** — aus Geschwindigkeit und Dauer, mit dem Kopf;
  2. **mit der Referenzregel** — einer Portierung von
     `tools/referenzdatensatz/generator/spur.py::ausduennen`, also der Regel,
     mit der der Referenzdatensatz erzeugt wurde und die ihrerseits aus
     `watch/source/Track.mc` stammt.

Die Kotlin-Prüffälle (`AusduennerTest`) erzeugen **denselben Strom noch einmal
selbst** und vergleichen gegen die Zahlen aus `stroeme.txt`. Stimmen drei
unabhängige Wege überein, ist die Zahl belastbar; weicht einer ab, fällt es auf.

WARUM DIE STRÖME KEINEN ZUFALL ENTHALTEN. Zwei Umsetzungen in zwei Sprachen
müssen denselben Strom erzeugen. Ein Zufallsgenerator wäre die eine Stelle, an
der das nicht gelingt. Stattdessen: gleichförmige Bewegung, ganze
Geschwindigkeiten und — wichtig — Geschwindigkeiten **fern der 15-m-Schwelle**,
damit keine Entscheidung an der letzten Nachkommastelle hängt. Wie fern, misst
das Skript selbst und meldet es als „Abstand zur Schwelle".

WARUM KEINE TRIGONOMETRIE IM ERZEUGER. Ein Grad Breite sind 111 320 m; für die
Länge kommt der Faktor cos(47,7°) als **feste Dezimalzahl** dazu (dieselbe in
beiden Umsetzungen). Damit stehen im Erzeuger nur +, −, × und ÷ — Operationen,
die in Python und Kotlin bitgleich rechnen. Die Trigonometrie steckt allein im
Haversine, und der ist auf beiden Seiten dieselbe Formel.

Aufruf:  werkzeuge/stroeme.py            # rechnen und Datei schreiben
         werkzeuge/stroeme.py pruefen    # nur rechnen und vergleichen
"""
import math
import sys
from pathlib import Path

ZIEL = Path(__file__).resolve().parents[1] / "handy/src/test/resources/stroeme.txt"

# --- Erdmaße, wortgleich in Kotlin -----------------------------------------
R_ERDE = 6_371_000.0
GRAD_BREITE_M = 111_320.0
COS_47_7 = 0.672367  # cos(47,7°) — der Breitengrad des Referenzdatensatzes

# Regel der Uhr (Const.THIN_*)
MIN_STRECKE_M = 15.0
MAX_ABSTAND_S = 10
MIN_ABSTAND_S = 1

START_BREITE, START_LAENGE, START_HOEHE = 47.7261, 10.3186, 712.0
START_ZEIT = 1_784_279_400          # fester Zeitpunkt, kein "jetzt"


def abstand_m(a_lat, a_lon, b_lat, b_lon):
    """Haversine — dieselbe Formel wie Track.mc und wegpunkte.abstand_m."""
    p1, p2 = math.radians(a_lat), math.radians(b_lat)
    dp = p2 - p1
    dl = math.radians(b_lon - a_lon)
    h = math.sin(dp / 2) ** 2 + math.cos(p1) * math.cos(p2) * math.sin(dl / 2) ** 2
    return 2 * R_ERDE * math.asin(math.sqrt(h))


def ausduennen(roh):
    """Regel der Uhr: >= 15 m ODER >= 10 s, nie öfter als 1/s."""
    behalten = []
    for p in roh:
        if not behalten:
            behalten.append(p)
            continue
        letzte = behalten[-1]
        dt = p[3] - letzte[3]
        if dt < MIN_ABSTAND_S:
            continue
        if abstand_m(letzte[0], letzte[1], p[0], p[1]) < MIN_STRECKE_M and dt < MAX_ABSTAND_S:
            continue
        behalten.append(p)
    return behalten


def kennzahlen(punkte):
    """(strecke_m, anstieg_m) wie die Uhr sie bildet — über die BEHALTENEN."""
    strecke = anstieg = 0.0
    for i in range(1, len(punkte)):
        a, b = punkte[i - 1], punkte[i]
        strecke += abstand_m(a[0], a[1], b[0], b[1])
        if a[2] is not None and b[2] is not None and b[2] > a[2]:
            anstieg += b[2] - a[2]
    return strecke, anstieg


def strom(abschnitte):
    """Ein Positionsstrom mit 1-Hz-Abtastung.

    abschnitte: Liste aus (dauer_s, tempo_m_s, steigen_m_s).
    Die Richtung ist fest 45 Grad (gleiche Anteile nach Norden und Osten) —
    die Ausdünnung kennt nur Abstände, keine Richtungen.
    """
    lat, lon, hoehe, t = START_BREITE, START_LAENGE, START_HOEHE, START_ZEIT
    punkte = [(lat, lon, hoehe, t)]
    for dauer, tempo, steigen in abschnitte:
        # Gleiche Anteile: Der Weg je Achse ist tempo/wurzel(2). Die Wurzel
        # steht als feste Dezimalzahl, damit beide Umsetzungen dieselbe Zahl
        # benutzen und nicht zwei Näherungen derselben.
        je_achse = tempo * 0.7071067811865476
        d_lat = je_achse / GRAD_BREITE_M
        d_lon = je_achse / (GRAD_BREITE_M * COS_47_7)
        for _ in range(dauer):
            t += 1
            lat += d_lat
            lon += d_lon
            hoehe += steigen
            punkte.append((lat, lon, hoehe, t))
    return punkte


# (Name, Beschreibung, Abschnitte, analytisch erwartete Punktzahl)
STROEME = [
    ("reiseflug", "900 s Reiseflug mit 60 m/s, davon 100 s im Steigflug",
     [(100, 60.0, 2.0), (800, 60.0, 0.0)],
     901),                       # 60 m je Schritt >= 15 m -> jeder Punkt
    ("anfahrt_boden", "600 s Anfahrt mit 12 m/s",
     [(600, 12.0, 0.0)],
     301),                       # 12 m < 15, 24 m >= 15 -> jeder zweite
    ("stand_einsatzort", "900 s Stillstand am Einsatzort",
     [(900, 0.0, 0.0)],
     91),                        # 0 m -> nur die 10-s-Bedingung
    ("stadtfahrt", "10x (60 s mit 8 m/s, 30 s Halt)",
     [(60, 8.0, 0.0), (30, 0.0, 0.0)] * 10,
     None),                      # gemischt, nur die Referenzregel
    # 12 h = 43 200 s, genau. Drei Einsaetze zu je 62 min, der Rest
    # Bereitschaft — das Verhaeltnis eines Diensttages des Referenzdatensatzes.
    ("dienst12h", "12 h Dienst: Bereitschaft, drei Einsätze",
     ([(7200, 0.0, 0.0)]                            # 2 h Bereitschaft
      + [(120, 60.0, 3.0), (900, 60.0, 0.0), (1200, 0.0, 0.0),
         (900, 60.0, 0.0), (600, 0.0, 0.0)] * 3     # 3 x 62 min Einsatz
      + [(7200, 0.0, 0.0)]                          # 2 h Bereitschaft
      + [(17640, 0.0, 0.0)]),                       # Rest bis genau 12 h
     None),
]


def schwellenabstand(roh):
    """Wie nah kommt eine Entscheidung der 15-m-Schwelle? (Je größer, je robuster.)"""
    naechste = float("inf")
    behalten = []
    for p in roh:
        if not behalten:
            behalten.append(p)
            continue
        letzte = behalten[-1]
        dt = p[3] - letzte[3]
        if dt < MIN_ABSTAND_S:
            continue
        d = abstand_m(letzte[0], letzte[1], p[0], p[1])
        if dt < MAX_ABSTAND_S:          # nur dann entscheidet die Strecke
            naechste = min(naechste, abs(d - MIN_STRECKE_M))
        if d < MIN_STRECKE_M and dt < MAX_ABSTAND_S:
            continue
        behalten.append(p)
    return naechste


def main() -> int:
    nur_pruefen = len(sys.argv) > 1 and sys.argv[1] == "pruefen"
    zeilen = [
        "# Soll-Zahlen der Ausdünnung — erzeugt von werkzeuge/stroeme.py.",
        "# NICHT von Hand ändern: Die Zahlen sind nachgerechnet, nicht gesetzt.",
        "# Format: name;rohpunkte;behalten;strecke_m;anstieg_m;schwellenabstand_m",
        "",
    ]
    abweichungen = 0
    print(f"{'Strom':<18} {'roh':>7} {'behalten':>9} {'Strecke m':>11} "
          f"{'Anstieg m':>10} {'Abstand Schwelle':>17}")
    for name, beschreibung, abschnitte, analytisch in STROEME:
        roh = strom(abschnitte)
        behalten = ausduennen(roh)
        strecke, anstieg = kennzahlen(behalten)
        abstand = schwellenabstand(roh)
        marke = ""
        if analytisch is not None and analytisch != len(behalten):
            marke = f"  ! analytisch {analytisch}"
            abweichungen += 1
        print(f"{name:<18} {len(roh):>7} {len(behalten):>9} {strecke:>11.1f} "
              f"{anstieg:>10.1f} {abstand:>17.2f}{marke}")
        zeilen.append(f"{name};{len(roh)};{len(behalten)};{strecke:.3f};"
                      f"{anstieg:.3f};{abstand:.3f}")

    zeilen.append("")
    text = "\n".join(zeilen)
    if nur_pruefen:
        alt = ZIEL.read_text(encoding="utf-8") if ZIEL.exists() else ""
        if alt != text:
            print("\n! Die Datei stroeme.txt weicht ab.")
            abweichungen += 1
    else:
        ZIEL.parent.mkdir(parents=True, exist_ok=True)
        ZIEL.write_text(text, encoding="utf-8")
        print(f"\nGeschrieben: {ZIEL.relative_to(ZIEL.parents[4])}")

    print(f"Abweichungen analytisch/Referenzregel: {abweichungen}")
    return 1 if abweichungen else 0


if __name__ == "__main__":
    sys.exit(main())
