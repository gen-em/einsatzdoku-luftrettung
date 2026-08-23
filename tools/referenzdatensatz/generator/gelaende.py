"""Hoehenmodell fuer die Spurerzeugung.

WOFUER. Die Anwendung rechnet `site_ele_m` (Hoehe des Einsatzorts) aus dem
Track, nicht aus einer Eingabe (`server/site_elevation_lib.php`). Ein Track
ohne Hoehen liesse das Feld leer und damit einen Teil der Anzeige ungeprueft.

WAS DAS HIER IST UND WAS NICHT. Es ist KEIN Gelaendemodell. Es sind rund
vierzig Stuetzpunkte mit ihrer tatsaechlichen Hoehe im Allgaeu und am
Alpenrand, zwischen denen invers-distanzgewichtet interpoliert wird. Das
liefert plausible Hoehen und plausible Steigprofile -- mehr braucht ein
Referenzdatensatz nicht, und mehr wird hier auch nicht behauptet. Zwischen
zwei Stuetzpunkten kann die Hoehe um einige hundert Meter danebenliegen; wer
dieses Modul fuer etwas anderes als eine Demo benutzt, benutzt es falsch.
"""
from __future__ import annotations

import math
import pathlib
import sys

sys.path.insert(0, str(pathlib.Path(__file__).resolve().parent.parent / "quelldaten"))
from wegpunkte import abstand_m  # noqa: E402,F401  -- EINE Definition, siehe dort

# (lat, lon, Hoehe in m) -- reale Hoehen realer Orte; die NAMEN des Datensatzes
# sind erfunden, die Geographie ist es nicht (E-P1-02).
STUETZPUNKTE = [
    (47.7255, 10.3140,  700), (47.7010, 10.3390,  730), (47.7300, 10.3980,  750),
    (47.7530, 10.2280,  858), (47.7240, 10.2010,  894), (47.8020, 10.2200,  720),
    (47.8080, 10.2900,  700), (47.6660, 10.3120,  720), (47.6480, 10.1250,  800),
    (47.5960, 10.1330,  900), (47.5590, 10.2170,  730), (47.5450, 10.2500,  750),
    (47.5320, 10.2870,  780), (47.5150, 10.2810,  740), (47.4570, 10.2810,  760),
    (47.4380, 10.2210,  875), (47.4290, 10.2610,  850), (47.4210, 10.3430, 2100),
    (47.4100, 10.2790,  815), (47.5080, 10.3710,  825), (47.5170, 10.4090, 1140),
    (47.5820, 10.3300,  850), (47.5960, 10.4110,  915), (47.6350, 10.4300,  960),
    (47.6220, 10.5050,  865), (47.5820, 10.5560,  850), (47.5710, 10.7000,  800),
    (47.7790, 10.6170,  758), (47.8800, 10.6220,  680), (47.9830, 10.1810,  600),
    (47.6920, 10.0400,  705), (47.5080, 10.0230, 1750), (47.4430, 10.1090, 1044),
    (47.6280, 10.3310, 1500), (48.4011,  9.9876,  478), (47.6810, 11.2020,  700),
    (47.7180, 10.3220,  700),
    # Talstuetzpunkte RUND UM DIE GIPFEL. Ohne sie zog die Interpolation
    # Strassen in Gipfelnaehe auf ueber 1300 m hoch -- der Grunten liegt auf
    # 1500 m, und drei Kilometer entfernt fuehrt eine Talstrasse auf 800 m.
    # Ein Modell aus wenigen Stuetzpunkten kann das nur wissen, wenn man es
    # ihm sagt.
    (47.6100, 10.3200,  790), (47.6450, 10.3450,  820), (47.6150, 10.3550,  830),
    (47.5250, 10.0600,  830), (47.4900, 10.0700,  860), (47.5350, 10.0100,  900),
    (47.4350, 10.3200,  900), (47.4050, 10.3300,  850), (47.4450, 10.3700, 1000),
]

def hoehe(lat: float, lon: float, nachbarn: int = 5) -> float:
    """Invers-distanzgewichtete Hoehe aus den naechsten Stuetzpunkten."""
    mit_abstand = sorted(
        ((abstand_m(lat, lon, s_lat, s_lon), h) for s_lat, s_lon, h in STUETZPUNKTE))[:nachbarn]
    if mit_abstand[0][0] < 1.0:
        return float(mit_abstand[0][1])
    summe = gewicht = 0.0
    for d, h in mit_abstand:
        g = 1.0 / (d ** 2)
        summe += g * h
        gewicht += g
    return summe / gewicht
