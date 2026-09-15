"""Wegpunkte einer Route auf Koordinaten aufloesen.

EINE STELLE FUER ZWEI LESER. Das Pruefskript (pruefen.py) und der Generator
(../generator/) muessen dieselbe Antwort geben, sonst prueft das eine etwas
anderes, als das andere erzeugt. Deshalb steht die Aufloesung hier und nicht
zweimal.

DIE TRENNUNG, DIE DAHINTER STECKT. Trackpunkte und Phasenkoordinaten liegen
in der Anwendung im KLARTEXT (Tabellen `track_points` und `mission_phases`);
verschluesselt ist die ADRESSE des Einsatzorts (`pat_blob.loc.addr`). Ein
Einsatz ohne geschuetzte Angaben hat deshalb sehr wohl eine Spur — sie kommt
dann aus `spur`, nicht aus `geschuetzt`.

Wegpunkte:
  basis        Standortkoordinate; hat der Standort keine -- oder hat der Tag
               gar keinen Standort --, der `spur_ausgangspunkt` des Dienstes
  start        manueller Abfahrtort, `geschuetzt.start` (nur start_src='manual')
  ort          Einsatzort: `spur.ort`, sonst `geschuetzt.loc`
  ziel         Transportziel: `spur.ziel`, sonst `felder.dest_lat/dest_lon`
  zustieg      der Punkt, bis zu dem GEFAHREN wird: Parkplatz, Talstation,
               Huettenzufahrt (`spur.zustieg`, Demo-Ausbau E-DA-13)
  ort_vorher   Einsatzort des VORIGEN Einsatzes (start_src='prev_site')
  ziel_vorher  Transportziel des VORIGEN Einsatzes (start_src='prev_dest')

DER FUSSWEG IST EIN PAAR VON WEGPUNKTEN, KEIN SCHALTER AM EINSATZ. Ein
Teilstueck wird zu Fuss gezeichnet, wenn seine beiden Enden `zustieg` und
`ort` heissen -- in der einen oder der anderen Richtung. Das steht hier und
nicht im Generator, weil auch der Routenabruf es wissen muss: Fuer einen
Fussweg wird keine Strasse geholt (`routen_holen.py`). Zwei Fassungen dieser
Frage waeren eine Strasse, die der Generator nie benutzt -- oder ein Fussweg
mit Strassengeometrie.
"""
from __future__ import annotations

import math
from datetime import datetime
from zoneinfo import ZoneInfo

R_ERDE = 6371000.0
TZ = ZoneInfo("Europe/Berlin")


def epoche(lokal: str) -> int:
    """Ortszeit (FORMAT.md) als Sekunden seit der Epoche.

    Steht hier, weil die Fensterrechnung darunter sie braucht und beide Leser
    dieselbe Umrechnung benutzen muessen. `generator/erzeugen.py` reicht seine
    gleichnamige Funktion hierher durch.
    """
    return int(datetime.strptime(lokal, "%Y-%m-%d %H:%M").replace(tzinfo=TZ).timestamp())


def abstand_m(a_lat: float, a_lon: float, b_lat: float, b_lon: float) -> float:
    """Haversine — dieselbe Formel, mit der die Uhr `distance_m` bildet.

    Sie steht HIER und nicht in gelaende.py, weil auch die Quelldaten sie
    brauchen (Einsatzort in erreichbarer Entfernung). Zwei Formeln fuer
    denselben Abstand liefen frueher oder spaeter auseinander.
    """
    p1, p2 = math.radians(a_lat), math.radians(b_lat)
    dp = p2 - p1
    dl = math.radians(b_lon - a_lon)
    h = math.sin(dp / 2) ** 2 + math.cos(p1) * math.cos(p2) * math.sin(dl / 2) ** 2
    return 2 * R_ERDE * math.asin(math.sqrt(h))


def _koord(x):
    if not x:
        return None
    lat, lon = x.get("lat"), x.get("lon")
    return None if lat is None or lon is None else (float(lat), float(lon))


#: Teilstuecke, die zu Fuss zurueckgelegt werden (E-DA-13).
FUSS_PAARE = {("zustieg", "ort"), ("ort", "zustieg")}


def ist_fussweg(von_name: str, nach_name: str) -> bool:
    """Wird dieses Teilstueck GEGANGEN?

    Die eine Stelle fuer drei Leser: Generator (zeichnet), Routenabruf (holt
    dafuer keine Strasse) und Pruefung (misst die Gehgeschwindigkeit).
    """
    return (von_name, nach_name) in FUSS_PAARE


def basis_von(dienst: dict, standorte: dict) -> tuple[float, float] | None:
    """Wo der Dienst beginnt und endet.

    `spur_ausgangspunkt` GEWINNT, wenn er dasteht. Er steht seit dem
    Demo-Ausbau nur noch an Diensten OHNE Standort (E-DA-09) -- an einem
    Standort mit Koordinaten waere er eine zweite Wahrheit, und `pruefen.py`
    laesst ihn dort nicht mehr zu. Ein Tag ohne Standort traegt
    `dienst['standort'] = None`; `standorte.get(None)` liefert dann nichts,
    und der Ausgangspunkt ist die einzige Quelle.
    """
    return _koord(dienst.get("spur_ausgangspunkt")) or _koord(standorte.get(dienst["standort"]))


def zustieg_von(einsatz: dict) -> tuple[float, float] | None:
    return _koord((einsatz.get("spur") or {}).get("zustieg"))


def ort_von(einsatz: dict) -> tuple[float, float] | None:
    spur = einsatz.get("spur") or {}
    return _koord(spur.get("ort")) or _koord((einsatz.get("geschuetzt") or {}).get("loc"))


def ziel_von(einsatz: dict) -> tuple[float, float] | None:
    spur = einsatz.get("spur") or {}
    if _koord(spur.get("ziel")):
        return _koord(spur["ziel"])
    f = einsatz.get("felder") or {}
    return _koord({"lat": f.get("dest_lat"), "lon": f.get("dest_lon")})


def aufloesen(dienst: dict, einsatz: dict, vorheriger: dict | None,
              standorte: dict) -> list[tuple[str, tuple[float, float] | None]]:
    """Liste (Wegpunktname, Koordinate) — Koordinate None heisst: loest nicht auf."""
    ergebnis = []
    for w in (einsatz.get("route") or []):
        if w == "basis":
            k = basis_von(dienst, standorte)
        elif w == "start":
            k = _koord((einsatz.get("geschuetzt") or {}).get("start"))
        elif w == "ort":
            k = ort_von(einsatz)
        elif w == "ziel":
            k = ziel_von(einsatz)
        elif w == "zustieg":
            k = zustieg_von(einsatz)
        elif w == "ort_vorher":
            k = ort_von(vorheriger) if vorheriger else None
        elif w == "ziel_vorher":
            k = ziel_von(vorheriger) if vorheriger else None
        else:
            k = None
        ergebnis.append((w, k))
    return ergebnis



# ======================================================================
# ZEITFENSTER DER TEILSTUECKE — die zweite Frage, die beide Leser teilen
# ======================================================================
#
# WARUM SIE SEIT DEM DEMO-AUSBAU HIER STEHT UND NICHT ZWEIMAL. Bis dahin
# rechnete der Generator seine Fenster in `erzeugen._fenster()` und das
# Pruefskript dieselbe Ableitung noch einmal in `pruefen.bewegungsfenster()`.
# Der Kommentar dort begruendete das mit: „Sie steht zweimal, weil Quelldaten
# und Generator sonst voneinander abhingen; die Regel selbst ist kurz."
#
# Kurz war sie. Mit dem Wegpunkt `zustieg` (E-DA-13) ist sie es nicht mehr:
# Vier Teilstuecke und drei Phasenfenster verlangen eine Zuteilung, und die
# passt in keine sechs Zeilen. Zwei Fassungen davon hiessen, dass das
# Pruefskript die Erreichbarkeit eines ANDEREN Ablaufs misst als den, den der
# Generator zeichnet -- und beide meldeten dabei Erfolg.
#
# Die Abhaengigkeit, die der alte Kommentar vermeiden wollte, entsteht dabei
# nicht: Dieses Modul liegt in `quelldaten/`, der Generator importiert es
# ohnehin schon, und es kennt seinerseits nichts aus `generator/`.

def phasenkandidaten(einsatz: dict, dienst: dict) -> list[tuple[int, int]]:
    """Die Zeitfenster, die sich aus den Phasen ERGEBEN — hoechstens drei.

    Die Phasen sind die Wahrheit ueber den Ablauf: 3 -> 4 ist der Weg zum
    Einsatzort, 6 -> 7 der Transport, danach der Rueckweg bis Phase 9. Der
    Track wird an sie GEBUNDEN und nicht daneben erfunden -- sonst zeigte die
    Karte den Hubschrauber am Einsatzort, waehrend die Phasentabelle ihn schon
    in der Klinik fuehrt.

    OHNE NOTNAGEL, und das ist der Grund, warum es diese Funktion gibt:
    `fenster()` muss wissen, wie viele Fenster die Phasen WIRKLICH hergeben.
    Solange das Auffuellen mit drinsteckte, meldete `phasenfenster(…, legs=4)`
    vier Fenster -- und `fenster()` schloss daraus, es gebe genug, und nahm
    den alten Weg. Der Fussweg zum Patienten bekam dadurch ein Viertel der
    Einsatzdauer statt der Spanne zwischen Phase 4 und Phase 5 und lief mit
    1,2 km/h. Gemessen an `fusswege.json`, nicht geraten.
    """
    p = {}
    for nr, zeit in einsatz["phasen"]:
        p.setdefault(nr, epoche(zeit))
    start = epoche(einsatz["beginn"])
    kandidaten = []
    if 3 in p and 4 in p:
        kandidaten.append((p[3], p[4]))
    elif 4 in p:
        kandidaten.append((start, p[4]))
    if 6 in p and 7 in p:
        kandidaten.append((p[6], p[7]))
    if 9 in p:
        ab = p.get(8) or p.get(7) or p.get(5) or p.get(4) or start
        if p[9] > ab:
            kandidaten.append((ab, p[9]))
    return kandidaten


def phasenfenster(einsatz: dict, dienst: dict, legs: int) -> list[tuple[int, int]]:
    """Ein Fenster je Teilstueck — die Phasen, notfalls gleichmaessig geteilt.

    Der Notnagel greift, wenn die Phasen weniger Fenster hergeben als es
    Teilstuecke gibt UND die Wegpunktnamen nichts Besseres wissen (siehe
    `fenster()`). Er ist kein guter Weg, nur ein ehrlicher: gleichmaessig
    teilen ist falsch, aber sichtbar falsch.
    """
    start = epoche(einsatz["beginn"])
    ende = epoche(einsatz["ende"]) if einsatz["ende"] else epoche(dienst["ende"])
    kandidaten = phasenkandidaten(einsatz, dienst)
    if len(kandidaten) < legs:                     # Notnagel: gleichmaessig teilen
        spanne = (ende - start) / max(legs, 1)
        kandidaten = [(int(start + i * spanne), int(start + (i + 1) * spanne))
                      for i in range(legs)]
    return kandidaten[:legs]


# ---- Zeitfenster, wenn es MEHR Teilstuecke als Phasenfenster gibt ---------
#
# WOFUER. Bis zum Demo-Ausbau hatte jede Route hoechstens zwei Teilstuecke
# (`basis -> ort -> ziel`), und `_fenster` gab je Teilstueck eines der drei
# Phasenfenster zurueck. Mit dem Wegpunkt `zustieg` (E-DA-13) sind es vier:
# hinfahren, hingehen, zurueckgehen, wegfahren. Fuer vier Teilstuecke gibt es
# aber keine vier Phasen — der Ablauf zwischen Transportbeginn (6) und Ankunft
# Klinik (7) ist EINE Spanne, in der zweierlei passiert.
#
# DIE PHASEN BLEIBEN DIE WAHRHEIT. Jeder Wegpunkt bekommt die Phase, an der er
# liegt — dieselbe Zuordnung wie in FORMAT.md, nur feiner: Mit einem `zustieg`
# zerfaellt die Ankunft in zwei Schritte. Phase 4 („Ankunft Einsatzort") ist
# der Punkt, an dem das Fahrzeug haelt, Phase 5 („Ankunft Patientin") der
# Patient am Hang. Wo zwischen zwei Phasen mehrere Teilstuecke liegen, wird
# die Spanne im Verhaeltnis ihrer geschaetzten Dauer geteilt.
#
# DIE NOMINALEN TEMPI BESTIMMEN NICHT DAS TEMPO DER SPUR, nur das VERHAELTNIS.
# Wie schnell am Ende gegangen wird, ergibt sich aus dem zugeteilten Fenster
# und der Strecke; `generator/pruefen.py` misst es nach.
TEMPO_NOMINAL_KMH = {"fuss": 3.5, "fahrt": 45.0}

# Welche Phase statt der gewuenschten gilt, wenn es sie an diesem Einsatz
# nicht gibt. Die Reihenfolge ist die des Ablaufs und nicht die der Zahlen:
# Fehlt „Ankunft Patientin" (5), ist „Ankunft Einsatzort" (4) der naechste
# wahre Zeitpunkt, nicht „Transportbeginn" (6).
PHASE_ERSATZ = {2: [2, 3], 3: [3, 2], 4: [4, 5, 3], 5: [5, 4, 6],
                6: [6, 5, 4], 7: [7, 8, 9], 8: [8, 7, 9], 9: [9, 8, 7]}


def _wegpunkt_phasen(namen: list[str]) -> list[tuple[int | None, int | None]]:
    """Je Wegpunkt (Ankunftsphase, Abfahrtsphase) — None heisst „ergibt sich".

    Der erste Wegpunkt hat keine Ankunft (dort beginnt der Einsatz), der
    letzte keine Abfahrt. Ein `zustieg` auf dem RUECKWEG bleibt bewusst ohne
    Phase: Zwischen Transportbeginn und Ankunft Klinik gibt es keine, und eine
    erfundene waere eine Phasenzeit, die im Datensatz nicht steht.
    """
    n = len(namen)
    paare: list[list[int | None]] = [[None, None] for _ in range(n)]
    paare[0][1] = 3
    i_ort = namen.index("ort") if "ort" in namen else None
    if i_ort is not None:
        if i_ort > 0 and namen[i_ort - 1] == "zustieg":
            paare[i_ort - 1] = [4, 4]
            paare[i_ort] = [5, 6]
        else:
            paare[i_ort] = [4, 6]
    for i in range(n - 1, 0, -1):
        if namen[i] == "ziel":
            paare[i][0] = 7
            break
    if paare[-1][0] is None:
        paare[-1][0] = 9
    return [(a, b) for a, b in paare]


def fenster_je_teilstueck(einsatz: dict, dienst: dict, namen: list[str],
                          koords: list) -> list[tuple[int, int]]:
    """Zeitfenster je Teilstueck, aus den Phasen und den Wegpunktnamen.

    JEDER WEGPUNKT HAT ZWEI ZEITEN, nicht eine: eine ANKUNFT und eine
    ABFAHRT. Am Einsatzort sind das Phase 5 und Phase 6, und dazwischen liegt
    die Versorgung -- der Unterschied ist der ganze Punkt. Ein Teilstueck
    laeuft deshalb von der ABFAHRT seines Anfangs bis zur ANKUNFT seines
    Endes. (Der erste Entwurf nahm je Wegpunkt nur einen Zeitpunkt; der
    Fussweg zum Patienten bekam dadurch die Spanne bis zum Transportbeginn
    und lief mit 1,1 km/h -- ein Wert, der wie ein Datenfehler aussieht und
    keiner war.)

    Wo eine der beiden Zeiten fehlt -- etwa am `zustieg` auf dem Rueckweg, wo
    es zwischen Transportbeginn und Ankunft Klinik keine Phase gibt --, wird
    die Spanne ueber mehrere Teilstuecke im Verhaeltnis ihrer geschaetzten
    Dauer geteilt.
    """
    p: dict[int, int] = {}
    for nr, zeit in einsatz["phasen"]:
        p.setdefault(nr, epoche(zeit))
    start = epoche(einsatz["beginn"])
    ende = epoche(einsatz["ende"]) if einsatz["ende"] else epoche(dienst["ende"])

    def zeit(wunsch: int | None) -> int | None:
        if wunsch is None:
            return None
        for nr in PHASE_ERSATZ.get(wunsch, [wunsch]):
            if nr in p:
                return p[nr]
        return None

    n = len(namen)
    paare = _wegpunkt_phasen(namen)
    an = [zeit(a) for a, _ in paare]
    ab = [zeit(b) for _, b in paare]
    if ab[0] is None:
        ab[0] = start
    if an[-1] is None:
        an[-1] = ende

    fenster: list[tuple[int, int]] = []
    zeiger = start
    i = 0
    while i < n - 1:
        j = i
        while j < n - 1 and an[j + 1] is None:
            j += 1
        t0 = ab[i] if ab[i] is not None else zeiger
        t1 = an[j + 1] if an[j + 1] is not None else ende
        # Monoton halten: Eine rueckwaerts laufende Uhr waere eine Spur, die
        # springt, und die Pruefung meldete sie als Ueberschall.
        t0 = max(t0, zeiger)
        t1 = max(t1, t0 + (j - i + 1))
        gewichte = []
        for k in range(i, j + 1):
            art = "fuss" if ist_fussweg(namen[k], namen[k + 1]) else "fahrt"
            strecke = abstand_m(*koords[k], *koords[k + 1]) / 1000.0
            gewichte.append(max(strecke / TEMPO_NOMINAL_KMH[art], 1e-6))
        summe = sum(gewichte)
        lauf = t0
        for k, g in enumerate(gewichte):
            bis = t1 if k == len(gewichte) - 1 else lauf + int((t1 - t0) * g / summe)
            fenster.append((lauf, max(bis, lauf + 1)))
            lauf = fenster[-1][1]
        zeiger = lauf
        i = j + 1
    return fenster


def fenster(einsatz: dict, dienst: dict, namen: list[str] | None,
            koords: list) -> list[tuple[int, int]]:
    """DER Einstieg: je Teilstueck ein Zeitfenster.

    Sie waehlt zwischen den beiden Wegen darueber und ist die einzige Stelle,
    an der diese Wahl getroffen wird -- Generator und Pruefskript rufen sie
    beide. Solange eine Route nicht mehr Teilstuecke hat als es Phasenfenster
    gibt (alle Routen bis zum Demo-Ausbau), gilt der alte Weg unveraendert;
    das ist keine Hoeflichkeit gegenueber dem Bestand, sondern die Bedingung
    dafuer, dass die 16 alten Diensttage byteweise dieselben Spuren behalten.
    """
    legs = max(len(koords) - 1, 0)
    if legs == 0:
        return []
    if namen and len(namen) == len(koords) and legs > len(phasenkandidaten(einsatz, dienst)):
        return fenster_je_teilstueck(einsatz, dienst, namen, koords)
    return phasenfenster(einsatz, dienst, legs)


def tagesablauf(dienst: dict, einsaetze: list, ruhesegmente: list,
                standorte: dict) -> list[dict]:
    """Der Dienst als zeitliche Folge — mit der Position, an der jedes Stueck
    beginnt und endet.

    WOFUER. Nach einem Einsatz beginnt die Uhr sofort ein Ruhe-Segment
    (Model.mc, `_endMission` -> `_startRestSegment`). Steht das Fahrzeug dann
    nicht an seinem Standort, sondern an der Zielklinik, gehoert der RUECKWEG
    in dieses Ruhe-Segment und nicht mehr zum Einsatz. Wer das anders
    modelliert, muss den Rueckweg in die Spanne zwischen Uebergabe und Endzeit
    pressen -- und erhaelt Rueckfluege mit 666 km/h.

    Diese Ableitung brauchen zwei: der Generator (Spuren) und der Routenabruf
    (welche Fahrstrecke ueberhaupt gebraucht wird). Deshalb steht sie hier und
    nicht zweimal.

    Rueckgabe je Stueck: {art, ref, beginn, ende, von, nach, wegpunkte,
    wegpunkt_namen}. `von` und `nach` sind Koordinaten oder None;
    `wegpunkt_namen` steht Zeichen fuer Zeichen neben `wegpunkte` und sagt,
    WIE der Punkt heisst -- daran erkennen Generator und Routenabruf den
    Fussweg (`ist_fussweg`). Ein Ruhe-Segment fuehrt die zwei Namen
    'ruhe_von' und 'ruhe_nach'; es wird nie gegangen.
    """
    basis = basis_von(dienst, standorte)
    stuecke = []
    for e in einsaetze:
        stuecke.append({"art": "einsatz", "obj": e, "beginn": e["beginn"],
                        "ende": e["ende"] or dienst["ende"]})
    for r in ruhesegmente:
        stuecke.append({"art": "ruhe", "obj": r, "beginn": r["beginn"],
                        "ende": r["ende"] or dienst["ende"]})
    stuecke.sort(key=lambda s: s["beginn"])

    # --- 1. Einsaetze aufloesen: wo faengt jeder an, wo hoert er auf ------
    vorheriger_einsatz = None
    for s in stuecke:
        if s["art"] != "einsatz":
            continue
        e = s["obj"]
        aufgeloest = [(n, k) for n, k in aufloesen(dienst, e, vorheriger_einsatz, standorte) if k]
        koords = [k for _, k in aufgeloest]
        s["wegpunkte"] = koords
        s["wegpunkt_namen"] = [n for n, _ in aufgeloest]
        s["von"] = koords[0] if koords else basis
        s["nach"] = koords[-1] if koords else basis
        vorheriger_einsatz = e

    # --- 2. Ruhe-Segmente sind die Brücken dazwischen --------------------
    #
    # Ein Ruhe-Segment fuehrt von dort, wo das Fahrzeug steht, dorthin, wo der
    # NAECHSTE Einsatz beginnt. Meistens ist das der Standort -- aber nicht
    # immer: Bei `start_src = prev_dest` wird die Besatzung alarmiert, waehrend
    # sie noch an der Klinik steht, und faehrt gar nicht erst heim. Wer das
    # Ruhe-Segment blind zum Standort fuehren laesst, schickt sie in sechs
    # Minuten zwanzig Kilometer weit und wieder zurueck.
    position = basis
    for i, s in enumerate(stuecke):
        if s["art"] == "einsatz":
            position = s["nach"]
            continue
        naechster = next((x for x in stuecke[i + 1:] if x["art"] == "einsatz"), None)
        s["von"] = position
        s["nach"] = naechster["von"] if naechster else basis
        s["wegpunkte"] = ([s["von"], s["nach"]] if s["von"] != s["nach"] else [s["von"]])
        s["wegpunkt_namen"] = (["ruhe_von", "ruhe_nach"] if s["von"] != s["nach"]
                               else ["ruhe_von"])
        position = s["nach"]

    for s in stuecke:
        s["ref"] = s["obj"]["client_ref"] or s["obj"].get("quell_kennung")
    return stuecke
