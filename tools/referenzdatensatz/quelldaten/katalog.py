"""Inhaltskatalog des Referenzdatensatzes.

WOFUER. Der Datensatz umfasst rund 90 Einsaetze. Die Faelle, die eine Zeile
der Abdeckungsmatrix belegen, sind einzeln von Hand geschrieben und stehen im
Drehbuch (aufbauen.py) -- jeder mit einer Begruendung, warum es ihn gibt. Die
uebrigen sind Betriebsalltag: Sie sollen plausibel sein und den Bestand auf
eine realistische Dichte bringen, ohne dass jemand neunzig Mal dasselbe von
Hand schreibt.

NAMEN ERFUNDEN, GEOGRAPHIE ECHT (E-P1-02). Die Koordinaten liegen im Allgaeu
und am Alpenrand; ohne sie waeren Tracks, Hoehenprofile und Kartendarstellung
nicht pruefbar. Ortsnamen, Strassen, Kliniken und Personen sind frei erfunden.
"""

# --- Einsatzorte ----------------------------------------------------------
#
# EINE Liste, kein Paar. Zwei getrennte Listen fuehrten denselben Ortsnamen mit
# zwei verschiedenen Koordinaten -- "Moosachtal" lag fuer die Luft auf 1750 m
# und fuer den Boden auf 900 m. Ein Ort hat eine Lage.
#
# `boden` sagt, ob der Ort mit einem Fahrzeug erreichbar ist. Die drei Orte
# ohne diesen Haken liegen im Steilgelaende oberhalb der Strassen; ein NEF
# faehrt dort nicht hin, und ein Einsatzort auf 2100 m im Bodendienst war
# genau der Fehler, den diese Spalte verhindert.
#
# (Name, lat, lon, mit dem Fahrzeug erreichbar)
ORTE = [
    ("Sonnenau",            47.4100, 10.2790, True),
    ("Auwiesen",            47.5450, 10.2500, True),
    ("Talwang",             47.5590, 10.2170, True),
    ("Burgstall",           47.5320, 10.2870, True),
    ("Steinach im Tal",     47.5150, 10.2810, True),
    ("Neusiedl am Steig",   47.4570, 10.2810, True),
    ("Oberkarwang",         47.4380, 10.2210, True),
    ("Gschwend",            47.4290, 10.2610, True),
    ("Grünegg",             47.5820, 10.3300, True),
    ("Moosachtal",          47.5960, 10.1330, True),
    ("Lohberg",             47.6480, 10.1250, True),
    ("Waltenau",            47.6660, 10.3120, True),
    ("Ödwang",              47.5960, 10.4110, True),
    ("Rauhenbach",          47.6350, 10.4300, True),
    ("Schattwald am Berg",  47.5080, 10.3710, True),
    ("Hinterried",          47.4430, 10.1090, True),
    ("Jochwang",            47.5170, 10.4090, True),
    ("Kaltbrunn am Hang",   47.7300, 10.3980, True),
    ("Wangried",            47.7530, 10.2280, True),
    ("Ellrain",             47.7240, 10.2010, True),
    ("Bärenmoos",           47.8020, 10.2200, True),
    ("Rossbrunn",           47.8080, 10.2900, True),
    ("Birkenau am See",     47.7010, 10.3390, True),
    ("Kirchbichl",          47.6220, 10.5050, True),
    ("Ahornau",             47.5820, 10.5560, True),
    ("Lindfeld",            47.5710, 10.7000, True),
    ("Marbach im Moos",     47.7790, 10.6170, True),
    ("Weißenstein",         47.8800, 10.6220, True),
    ("Traunwang",           47.6920, 10.0400, True),
    # Steilgelaende -- nur aus der Luft erreichbar
    ("Nebelkopf",           47.4210, 10.3430, False),
    ("Rauhenberg",          47.6280, 10.3310, False),
    ("Silberkar",           47.5080, 10.0230, False),
]

ORTE_LUFT = [(n, a, b) for n, a, b, _ in ORTE]
ORTE_BODEN = [(n, a, b) for n, a, b, boden in ORTE if boden]

STRASSEN = [
    "Sennereiweg", "Am Kreuzbichl", "Talstraße", "Lärchenweg", "Bergbahnstraße",
    "Wiesenweg", "Am Anger", "Kirchsteig", "Mühlbachweg", "Föhrenring",
    "Schmiedgasse", "Enzianweg", "Hirtenweg", "Brunnengasse", "Buchenallee",
    "Almweg", "Rossgasse", "Zur Sennalpe", "Hochstraße", "Weiherweg",
    "Ahornstraße", "Salzstraße", "Kreuzwiese", "Am Bühl", "Lindenplatz",
]

PLZ = ["87561", "87534", "87509", "87545", "87538", "87477", "87452",
       "87441", "87466", "87411", "87427", "87490", "87519", "87572"]

# --- Einsatzbilder --------------------------------------------------------
# (Diagnose, Ortsbeschreibung, Transportart, Schockraum, Bergwacht-tauglich)
BILDER_LUFT = [
    ("Schädel-Hirn-Trauma nach Sturz",              "Wohnhaus, Treppenaufgang",             "air", 1, 0),
    ("Polytrauma nach Verkehrsunfall",              "Bundesstraße, Fahrzeug im Graben",     "air", 1, 0),
    ("Thoraxtrauma nach Sturz aus Höhe",            "Baustelle, Gerüst im 2. OG",           "air", 1, 0),
    ("Unterschenkelfraktur",                        "Skipiste, Talabfahrt",                 "air", 0, 1),
    ("Beckenfraktur nach Absturz",                  "Steilgelände unterhalb des Steigs",    "air", 1, 1),
    ("Wirbelsäulentrauma nach Sturz",               "Wanderweg oberhalb der Alpe",          "air", 0, 1),
    ("Akutes Koronarsyndrom mit ST-Hebung",         "Einfamilienhaus, Wohnzimmer",          "air", 0, 0),
    ("Schlaganfall mit Halbseitensymptomatik",      "Seniorenwohnanlage, Erdgeschoss",      "air", 0, 0),
    ("Status asthmaticus",                          "Ferienwohnung im Dachgeschoss",        "air", 0, 0),
    ("Anaphylaxie nach Insektenstich",              "Gartengrundstück am Hang",             "air", 0, 0),
    ("Krampfanfall, postiktal orientiert",          "Straßenrand vor der Bushaltestelle",   "ground", 0, 0),
    ("Hypoglykämie",                                "Feldweg am Ortsrand",                  "ambulant", 0, 0),
    ("Verbrennung Grad IIb",                        "Hofstelle, Grillunfall im Innenhof",   "air", 1, 0),
    ("Intoxikation mit Mischkonsum",                "Badesee, Uferbereich",                 "air", 0, 0),
    ("Oberschenkelfraktur nach Sturz",              "Klettersteig, Einstieg Süd",           "air", 0, 1),
    ("Beinahe-Ertrinken mit Aspiration",            "Badesee, Steg am Ostufer",             "air", 1, 0),
    ("Akzidentelle Hypothermie",                    "Feldweg, Person im Straßengraben",     "air", 0, 1),
    ("Obere gastrointestinale Blutung",             "Gasthof, Gästezimmer im 1. OG",        "ground", 0, 0),
    ("Distorsion des oberen Sprunggelenks",         "Wanderweg, Wiesenhang",                "ambulant", 0, 1),
    ("Schulterluxation, geschlossen reponiert",     "Rodelbahn, Auslauf",                   "ambulant", 0, 1),
    ("Milzruptur nach Skikollision",                "Skipiste, Einmündung der Talabfahrt",  "air", 1, 1),
    ("Sepsis bei Pyelonephritis",                   "Innerklinische Übernahme",             "air", 0, 0),
    ("Intrazerebrale Blutung",                      "Innerklinische Übernahme, Intensiv",   "air", 0, 0),
    ("Rippenserienfraktur nach Sturz",              "Landwirtschaftlicher Betrieb, Stall",  "air", 0, 0),
    ("Amputationsverletzung der Hand",              "Sägewerk, Halle 2",                    "air", 1, 0),
    ("Lungenembolie mit Schock",                    "Wohnhaus, Schlafzimmer",               "air", 0, 0),
]

BILDER_BODEN = [
    ("Akutes Koronarsyndrom mit ST-Hebung",         "Mehrfamilienhaus, 3. OG ohne Aufzug",  "ground", 0, 0),
    ("Hypertensive Entgleisung",                    "Seniorenwohnanlage, Erdgeschoss",      "ambulant", 0, 0),
    ("Krampfanfall bei bekannter Epilepsie",        "Gaststätte, Nebenraum",                "ground", 0, 0),
    ("Exazerbierte COPD",                           "Reihenhaus, Wohnküche",                "ground", 0, 0),
    ("Schlaganfall, Lyse-Fenster offen",            "Wohnhaus, Bad",                        "ground", 0, 0),
    ("Synkope unklarer Genese",                     "Supermarkt, Kassenbereich",            "ground", 0, 0),
    ("Schädel-Hirn-Trauma bei Motorradunfall",      "Fahrbahnrand, Fahrzeug im Graben",     "ground", 1, 0),
    ("Thoraxtrauma nach Verkehrsunfall",            "Kreisstraße, Fahrzeug gegen Baum",     "ground", 1, 0),
    ("Hypoglykämie",                                "Werkstatt, hinter der Hebebühne",      "ambulant", 0, 0),
    ("Intoxikation mit Alkohol",                    "Festzelt, Sanitätsbereich",            "ground", 0, 0),
    ("Akutes Abdomen",                              "Wohnhaus, Küche",                      "ground", 0, 0),
    ("Fieberkrampf beim Kleinkind",                 "Ferienwohnung, Kinderzimmer",          "ground", 0, 0),
    ("Sturz aus dem Stand mit Platzwunde",          "Vorplatz der Kapelle",                 "ambulant", 0, 0),
    ("Anaphylaxie nach Wespenstich",                "Gartengrundstück",                     "ground", 0, 0),
    ("Kreislaufstillstand bei Vorderwandinfarkt",   "Werkstatt eines Landmaschinenbetriebs","ground", 1, 0),
    ("Obere gastrointestinale Blutung",             "Gasthof, Gästezimmer",                 "ground", 0, 0),
    ("Pneumonie mit respiratorischer Insuffizienz", "Pflegeheim, Zimmer 14",                "ground", 0, 0),
    ("Femurfraktur nach häuslichem Sturz",          "Wohnhaus, Flur",                       "ground", 0, 0),
    ("Verbrühung beim Kleinkind",                   "Wohnung, Küche",                       "ground", 1, 0),
    ("Akzidentelle Hypothermie",                    "Bushaltestelle am Ortsrand",           "ground", 0, 0),
    ("Rippenprellung nach Sturz",                   "Rodelbahn, Kurve 4",                   "ambulant", 0, 0),
    ("Nierenkolik",                                 "Wohnhaus, Schlafzimmer",               "ground", 0, 0),
]

# --- Notizen (Betriebsalltag, keine Prüffälle) -----------------------------
NOTIZEN_LUFT = [
    "Landeplatz durch die Feuerwehr abgesichert.",
    "Übergabe an der Notaufnahme, Voranmeldung über die Leitstelle erfolgt.",
    "Anflug wegen tiefer Bewölkung über das Tal geführt.",
    "Patientin war bei Eintreffen bereits durch den RTW versorgt.",
    "Landung auf dem Sportplatz, Zufahrt für den RTW frei gehalten.",
    "Nachforderung des Hubschraubers durch den bodengebundenen Notarzt.",
    None,
    "Rückflug mit Zwischenlandung zur Betankung.",
    "Sichtflug am Grat grenzwertig, Rückflug über das Haupttal.",
    None,
]

NOTIZEN_BODEN = [
    "Zufahrt eng, RTW musste am Ortsrand halten.",
    "Übergabe an der Notaufnahme ohne Voranmeldung.",
    "First Responder war vor Ort und hatte bereits Sauerstoff gegeben.",
    "Patient wurde nach Aufklärung ambulant belassen.",
    None,
    "Nachforderung des Rettungshubschraubers geprüft, wegen Sichtflug verworfen.",
    None,
    "Angehörige vor Ort, Betreuung durch die Besatzung.",
]

# Notizen, die nur in der kalten Jahreszeit passen. Sie stehen getrennt, weil
# eine Strassenglaette im Juni das Erste ist, was einem Betrachter des
# Demo-Kontos auffaellt -- und dann glaubt er dem Rest auch nicht mehr.
NOTIZEN_WINTER = [
    "Straßenglätte auf der Anfahrt, verlängerte Ausrückzeit.",
    "Zufahrt erst nach Räumung durch den Bauhof befahrbar.",
    "Landeplatz durch die Feuerwehr von Schnee freigeräumt.",
]

# --- Reanimationsverläufe (fuer die Fuellung; die Pruefkfaelle stehen im Drehbuch)
REA_VERLAEUFE = [
    [("zugang", 1), ("rhythmuskontrolle", 2), ("adrenalin", 4), ("defibrillation", 6),
     ("intubation", 10), ("rosc", 18)],
    [("zugang", 1), ("rhythmuskontrolle", 2), ("adrenalin", 4), ("intubation", 8),
     ("amiodaron", 12), ("rhythmuskontrolle", 15), ("rosc", 21)],
    [("zugang", 2), ("rhythmuskontrolle", 3), ("adrenalin", 5), ("defibrillation", 7),
     ("sonographie", 11), ("tod", 24)],
]
