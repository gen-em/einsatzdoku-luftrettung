"""Spurerzeugung — Lufttracks geometrisch, Bodentracks aus Strassengeometrie.

DIE AUSDUENNUNG IST DIE DER UHR. `watch/source/Track.mc` nimmt einen Punkt
auf, wenn seit dem letzten >= 15 m zurueckgelegt wurden ODER >= 10 s vergangen
sind, und nie oefter als einmal je Sekunde (`Const.THIN_*`). Genau diese Regel
laeuft hier -- ein Track, der anders ausgeduennt ist, sieht in der Anzeige
anders aus und ist als Referenz wertlos.

WO DER GENERATOR BEWUSST GROEBER IST. Die Uhr wertet die Regel jede Sekunde
aus; hier wird der Weg alle 3 s (Luft) beziehungsweise 5 s (Boden) abgetastet,
ein Halt alle 30 s und ein Ruhe-Segment alle 60 s. Der Grund ist die Groesse:
Bei sekundengenauer Abtastung traegt der Datensatz rund 160 000 Spurpunkte,
und die Fixture unter `server/demo/` wird bei JEDEM Deploy per FTP
hochgeladen. Mit der groeberen Abtastung sind es rund ein Drittel davon.

Was das NICHT kostet: Die Teilstueckbildung des Uploads bleibt dieselbe
(500 Punkte je Anfrage, JSON-Vertrag 6) -- die laengeren Lufteinsaetze
ueberschreiten die Grenze weiterhin und werden in mehreren Anfragen gesendet.
Was es kostet: Ein Track ist gleichmaessiger als einer aus dem Feld. Fuer die
Anzeige und fuer `site_ele_m` ist das ohne Belang, und mehr soll er nicht
belegen.

`distance_m` und `ascent_m` entstehen wie auf der Uhr: Summe der Haversine-
Abstaende zwischen den AUFGEZEICHNETEN Punkten und Summe der positiven
Hoehendifferenzen zwischen ihnen -- nicht aus der Weglaenge des Modells.
"""
from __future__ import annotations

import json
import math
import pathlib
import random

import gelaende

TAKT_LUFT = 3         # s zwischen zwei Abtastungen in der Luft
TAKT_BODEN = 5        # s zwischen zwei Abtastungen auf der Strasse
TAKT_HALT = 30        # s im Stand (Einsatzort, Klinik)
TAKT_RUHE = 60        # s im Ruhe-Segment

THIN_MIN_DIST_M = 15.0   # wie Const.THIN_MIN_DIST_M
THIN_MAX_GAP_S = 10      # wie Const.THIN_MAX_GAP_S

AGL_SPITZE = 350.0    # Hoehe ueber Grund im Reiseflug
JITTER_M = 3.0        # GPS-Streuung im Stand


def _mitte(a, b, f):
    """Punkt auf der Verbindung a->b beim Anteil f (Grosskreis, kurze Wege)."""
    return (a[0] + (b[0] - a[0]) * f, a[1] + (b[1] - a[1]) * f)


def _quer(a, b):
    """Einheitsvektor quer zur Strecke a->b, in Grad."""
    dlat, dlon = b[0] - a[0], (b[1] - a[1]) * math.cos(math.radians(a[0]))
    laenge = math.hypot(dlat, dlon) or 1.0
    return (-dlon / laenge, dlat / laenge / max(math.cos(math.radians(a[0])), 1e-6))


RAMPE = 0.15          # Anteil der Fahrzeit fuer Beschleunigen bzw. Bremsen


def _profil(f: float) -> float:
    """Weganteil bei Zeitanteil f — beschleunigen, halten, bremsen (Trapez).

    Ohne ein Profil faehrt und fliegt alles vom ersten bis zum letzten Punkt
    mit derselben Geschwindigkeit; die Spur sieht am Start und am Ziel falsch
    aus, denn ein Hubschrauber steht, hebt ab und beschleunigt.

    WARUM TRAPEZ UND NICHT KOSINUS. Der erste Entwurf nahm eine
    Kosinus-Glaettung. Die ist an den Enden richtig, ueberhoeht aber die MITTE
    um 57 Prozent gegenueber dem Mittelwert -- ein Abschnitt mit 90 km/h im
    Schnitt hatte in der Mitte 141 km/h, und die Pruefung meldete es zu Recht.
    Beim Trapez liegt die Reisegeschwindigkeit nur rund 18 Prozent ueber dem
    Mittel, und das ist auch die Wirklichkeit: Man beschleunigt einmal, faehrt
    und bremst einmal.
    """
    f = min(max(f, 0.0), 1.0)
    r = RAMPE
    v = 1.0 / (1.0 - r)                      # Reisegeschwindigkeit, auf 1 normiert
    if f <= r:
        return v * f * f / (2 * r)
    if f <= 1 - r:
        return v * (r / 2 + (f - r))
    return 1.0 - v * (1 - f) ** 2 / (2 * r)


def flug(von, nach, t0: int, t1: int, saat: str) -> list[tuple]:
    """Punkte eines Flugabschnitts, roh (noch nicht ausgeduennt)."""
    z = random.Random(f"flug-{saat}-{von}-{nach}")
    strecke = gelaende.abstand_m(von[0], von[1], nach[0], nach[1])
    # Leichter Bogen statt Lineal: eine reale Spur ist nie exakt gerade.
    bogen = z.uniform(-1, 1) * min(strecke * 0.012, 900.0)
    qlat, qlon = _quer(von, nach)
    dauer = max(t1 - t0, 1)

    # --- 1. Weg abtasten, Gelaende darunter merken ----------------------
    bahn = []
    for t in range(t0, t1 + 1, TAKT_LUFT):
        w = _profil((t - t0) / dauer)
        lat, lon = _mitte(von, nach, w)
        s = math.sin(math.pi * w) * bogen / 111320.0   # leichter Bogen
        lat += qlat * s
        lon += qlon * s
        bahn.append((lat, lon, gelaende.hoehe(lat, lon), t))

    # --- 2. Hoehenprofil: EINMAL steigen, EINMAL sinken -------------------
    #
    # Der erste Entwurf legte die Hoehe als fester Abstand ueber das Gelaende.
    # Das ergab auf 34 km ueber 2200 Hoehenmeter -- der Hubschrauber waere
    # jeder Gelaendewelle gefolgt. So fliegt niemand: Man steigt nach dem
    # Start auf eine Reiseflughoehe, die den hoechsten Punkt der Strecke mit
    # Abstand ueberquert, bleibt dort und sinkt erst am Ziel.
    hoch_start, hoch_ziel = bahn[0][2], bahn[-1][2]
    # Reiseflughoehe waechst mit der Strecke. Ohne das steigt der Hubschrauber
    # fuer einen Kilometer Verlegungsflug 350 m hoch und sinkt sofort wieder --
    # sichtbarer Unsinn im Hoehenprofil des Einsatzes.
    agl = min(max(strecke / 1000.0 * 60.0, 80.0), AGL_SPITZE)
    reise = max(max(p[2] for p in bahn) + agl * 0.85,
                hoch_start + agl, hoch_ziel + agl)
    steigen_bis, sinken_ab = 0.16, 0.82
    punkte = []
    for i, (lat, lon, grund, t) in enumerate(bahn):
        f = i / max(len(bahn) - 1, 1)
        if f <= steigen_bis:
            h = hoch_start + (reise - hoch_start) * _profil(f / steigen_bis)
        elif f >= sinken_ab:
            h = reise + (hoch_ziel - reise) * _profil((f - sinken_ab) / (1 - sinken_ab))
        else:
            h = reise
        h = max(h, grund + 80.0)          # nie ins Gelaende fliegen
        punkte.append((round(lat, 6), round(lon, 6), round(h, 1), t))
    return punkte


def _bogenlaengen(koordinaten: list) -> tuple[list, float]:
    laengen, summe = [0.0], 0.0
    for i in range(1, len(koordinaten)):
        a, b = koordinaten[i - 1], koordinaten[i]
        summe += gelaende.abstand_m(a[1], a[0], b[1], b[0])
        laengen.append(summe)
    return laengen, summe


def fahrt(geometrie: list, t0: int, t1: int) -> list[tuple]:
    """Punkte eines Fahrabschnitts entlang einer Strassengeometrie.

    `geometrie` ist die GeoJSON-Koordinatenliste [[lon, lat], ...] aus dem
    Routing. Abgetastet wird ueber die BOGENLAENGE, nicht ueber den Index:
    OSRM setzt in Kurven dicht und auf der Geraden duenn: Ueber den Index
    abgetastet fuehre der Wagen in Kurven langsam und auf der Geraden schnell.
    """
    laengen, gesamt = _bogenlaengen(geometrie)
    if gesamt <= 0:
        lon, lat = geometrie[0]
        return [(round(lat, 6), round(lon, 6), round(gelaende.hoehe(lat, lon), 1), t0)]
    punkte, j = [], 0
    dauer = max(t1 - t0, 1)
    for t in range(t0, t1 + 1, TAKT_BODEN):
        ziel = _profil((t - t0) / dauer) * gesamt
        while j < len(laengen) - 2 and laengen[j + 1] < ziel:
            j += 1
        spanne = laengen[j + 1] - laengen[j]
        f = (ziel - laengen[j]) / spanne if spanne > 0 else 0.0
        a, b = geometrie[j], geometrie[j + 1]
        lon = a[0] + (b[0] - a[0]) * f
        lat = a[1] + (b[1] - a[1]) * f
        punkte.append((round(lat, 6), round(lon, 6),
                       round(gelaende.hoehe(lat, lon), 1), t))
    return punkte


def halt(punkt, t0: int, t1: int, takt: int, saat: str) -> list[tuple]:
    """Punkte im Stand — GPS streut, das Fahrzeug bewegt sich nicht."""
    z = random.Random(f"halt-{saat}-{punkt}-{t0}")
    grund = gelaende.hoehe(punkt[0], punkt[1])
    punkte = []
    for t in range(t0, t1 + 1, takt):
        punkte.append((
            round(punkt[0] + z.gauss(0, JITTER_M) / 111320.0, 6),
            round(punkt[1] + z.gauss(0, JITTER_M) / (111320.0 * math.cos(math.radians(punkt[0]))), 6),
            round(grund + z.gauss(0, 2.0), 1),
            t))
    return punkte


def ausduennen(roh: list[tuple]) -> list[tuple]:
    """Regel der Uhr: >= 15 m ODER >= 10 s, nie oefter als 1/s."""
    behalten: list[tuple] = []
    for p in roh:
        if not behalten:
            behalten.append(p)
            continue
        letzte = behalten[-1]
        dt = p[3] - letzte[3]
        if dt < 1:
            continue
        d = gelaende.abstand_m(letzte[0], letzte[1], p[0], p[1])
        if d < THIN_MIN_DIST_M and dt < THIN_MAX_GAP_S:
            continue
        behalten.append(p)
    return behalten


def kennzahlen(punkte: list[tuple]) -> tuple[int, int]:
    """(distance_m, ascent_m) wie die Uhr sie bildet."""
    strecke = steigung = 0.0
    for i in range(1, len(punkte)):
        a, b = punkte[i - 1], punkte[i]
        strecke += gelaende.abstand_m(a[0], a[1], b[0], b[1])
        if a[2] is not None and b[2] is not None and b[2] > a[2]:
            steigung += b[2] - a[2]
    return round(strecke), round(steigung)
