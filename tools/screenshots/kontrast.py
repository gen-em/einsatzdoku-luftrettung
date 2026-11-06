# -*- coding: utf-8 -*-
"""
Kontraste der Token nachrechnen.

WOFUER. Die Gestaltungsrichtlinie fuehrt eine Kontrasttabelle. Eine abgeschriebene
Tabelle veraltet in dem Augenblick, in dem jemand einen Token aendert — und
merkt es nicht. Dieses Skript liest die Werte aus dem Stylesheet und rechnet
sie nach; docs/Design.md wird daraus erzeugt, nicht gepflegt.

Gerechnet wird nach WCAG 2.1 (relative Luminanz, sRGB). Zwei Schwellen:
  4.5:1  Schrift in normaler Groesse
  3.0:1  grosse Schrift (ab 18 px oder 14 px fett) und Raender von
         Bedienelementen (WCAG 1.4.11)

Aufruf:  python3 tools/screenshots/kontrast.py [--json]
         python3 tools/screenshots/kontrast.py --selbstprobe
Rueckgabewert != 0, wenn ein Paar seine Schwelle verfehlt oder ein Paar des
Stylesheets in keiner der beiden Listen steht.

DIE LISTE IST NICHT MEHR DAS MASS. Frueher rechnete
das Werkzeug nur, was in PAARE stand, und ein Paar, das dort fehlte, meldete
keinen Fehler — so standen in der App der orange Rueckstandspunkt (2,23:1)
und Rot als Schrift auf der Uhr unter dem Zielwert, waehrend jeder Lauf
gruen war. Jetzt leitet
`ableiten()` die Paare aus den Regeln des Stylesheets ab:

  - Steht in EINER Regel eine Vordergrundfarbe (`color`, `border*`,
    `outline*`, `fill`, `stroke`, `text-decoration*`, `caret-color`) neben
    einer Flaeche (`background`, `background-color`), ist das ein Paar.
  - Eine Vordergrundfarbe ohne Flaeche in ihrer Regel muss als Vordergrund
    in einer Liste stehen; eine Flaeche ohne Vordergrund irgendwo.

Jedes abgeleitete Paar steht in PAARE (dann wird es gerechnet) oder in
AUSNAHMEN (mit Grund) — sonst ist es ein Befund. WAS ES NICHT SIEHT: eine
bekannte Vordergrundfarbe auf einer NEUEN Flaeche, die eine Elternregel
setzt. Die Flaeche steht fast nie in derselben Regel wie die Schrift; dafuer
gibt es den Bilderlauf. Dieselbe Grenze nennt das Android-Werkzeug.
"""
import io
import json
import os
import re
import sys

HIER = os.path.dirname(os.path.abspath(__file__))
CSS = os.path.join(HIER, '..', '..', 'server', 'assets', 'style.css')


def _bloecke(t, kopf):
    """Inhalte aller Bloecke, deren Selektor auf `kopf` passt (Regex)."""
    aus = []
    for m in re.finditer(kopf, t):
        tiefe, i = 1, m.end()
        while tiefe and i < len(t):
            tiefe += {'{': 1, '}': -1}.get(t[i], 0)
            i += 1
        aus.append(t[m.end():i - 1])
    return aus


def _aufloesen(roh):
    aufgeloest = {}
    for name, wert in roh.items():
        for _ in range(6):
            v = re.match(r'var\(--([\w-]+)\)', wert)
            if not v:
                break
            wert = roh.get(v.group(1), wert)
        if re.fullmatch(r'#[0-9A-Fa-f]{6}', wert):
            aufgeloest[name] = wert.upper()
    return aufgeloest


def token_lesen(modus='hell'):
    """Alle --name:#RRGGBB aus den :root-Bloecken, var()-Ketten aufgeloest.

    SEIT SCHRITT 26 ZWEI BLOECKE UND ZWEI MODI. Die Farben stehen im Block
    des Branding-Repos (`:root {`, `--genem-*`), die Rollen der Anwendung im
    zweiten (`:root{`) und zeigen darauf. `modus='dunkel'` legt die
    Zuweisungen aus `:root[data-theme="dark"]` darueber — dieselben, die
    `prefers-color-scheme: dark` setzt; das Stylesheet fuehrt sie doppelt,
    weil CSS keinen Selektor fuer „System oder Schalter" kennt."""
    t = io.open(CSS, encoding='utf-8').read()
    t = re.sub(r'/\*.*?\*/', ' ', t, flags=re.S)
    roh = {}
    for inhalt in _bloecke(t, r':root\s*\{'):
        for name, wert in re.findall(r'--([\w-]+)\s*:\s*([^;]+);', inhalt):
            roh[name] = wert.strip()
    if modus == 'dunkel':
        for inhalt in _bloecke(t, r':root\[data-theme="dark"\]\s*\{'):
            for name, wert in re.findall(r'--([\w-]+)\s*:\s*([^;]+);', inhalt):
                roh[name] = wert.strip()
    return _aufloesen(roh)


def luminanz(hexwert):
    h = hexwert.lstrip('#')
    werte = []
    for i in (0, 2, 4):
        c = int(h[i:i + 2], 16) / 255
        werte.append(c / 12.92 if c <= 0.03928 else ((c + 0.055) / 1.055) ** 2.4)
    return 0.2126 * werte[0] + 0.7152 * werte[1] + 0.0722 * werte[2]


def kontrast(a, b):
    la, lb = luminanz(a), luminanz(b)
    hoch, tief = max(la, lb), min(la, lb)
    return (hoch + 0.05) / (tief + 0.05)


# Die Paare, die in der Oberflaeche tatsaechlich vorkommen — mit ihrer Rolle,
# denn die Rolle bestimmt die Schwelle. SEIT SCHRITT 26 (Konzept MA) stehen
# hier Rollen statt Leitwerte (`text` statt `asphalt`), und jedes Paar wird
# zweimal gerechnet: hell und dunkel. Bis dahin hiessen die Paare nach den
# Farben („Asphalt auf Schnee"); die Rollen schalten im Dark Mode um, die
# Namen der Farben taeten es nicht.
PAARE = [
    ('Text auf Karte',                    'text',          'flaeche-karte',     4.5, 'Fliesstext'),
    ('Text auf Seite',                    'text',          'flaeche-seite',     4.5, 'Fliesstext ausserhalb von Karten'),
    ('Text auf abgesetzt',                'text',          'flaeche-abgesetzt', 4.5, 'Fliesstext unter dem Zeiger, Tabellenkopf'),
    ('Text im Feld',                      'text',          'flaeche-feld',      4.5, 'Eingabefeld'),
    ('Titel auf Karte',                   'text-titel',    'flaeche-karte',     4.5, 'Titel, Symbole'),
    ('Titel auf Seite',                   'text-titel',    'flaeche-seite',     4.5, 'Seitentitel'),
    ('Titel auf abgesetzt',               'text-titel',    'flaeche-abgesetzt', 4.5, 'Titel, Symbole auf Rauch'),
    ('Titel im Feld',                     'text-titel',    'flaeche-feld',      4.5, 'Segmenttaste'),
    ('Gedaempft auf Karte',               'gedaempft',     'flaeche-karte',     4.5, 'Kleinzeile'),
    ('Gedaempft auf abgesetzt',           'gedaempft',     'flaeche-abgesetzt', 4.5, 'Kleinzeile, gesperrtes Feld'),
    ('Link auf Karte',                    'text-link',     'flaeche-karte',     4.5, 'Textlink, Anlegen-Weg'),
    ('Link auf abgesetzt',                'text-link',     'flaeche-abgesetzt', 4.5, 'Link unter dem Zeiger'),
    ('Link auf Seite',                    'text-link',     'flaeche-seite',     4.5, 'Link in der Fusszeile'),
    ('Fehlertext auf Karte',              'fehler-text',   'flaeche-karte',     4.5, 'Pflichtzeichen, Fehler am Feld'),
    ('Linie stark auf Karte',             'linie-stark',   'flaeche-karte',     3.0, 'Rand von Bedienelementen'),
    ('Linie stark im Feld',               'linie-stark',   'flaeche-feld',      3.0, 'Rand des Eingabefelds'),
    ('Linie stark auf abgesetzt',         'linie-stark',   'flaeche-abgesetzt', 3.0, 'Rand des gesperrten Felds'),
    ('Fokus auf Seite',                   'fokus',         'flaeche-seite',     3.0, 'Fokusring'),
    ('Fokus auf Karte',                   'fokus',         'flaeche-karte',     3.0, 'Fokusring'),
    # Aktion und Auswahl
    ('Auf Aktion (Primaerknopf)',         'auf-aktion',    'aktion',            4.5, 'Primaerknopf, gewaehlte Segmenttaste, Zahl des aktiven Filters'),
    ('Auf Aktion unter dem Zeiger',       'auf-aktion',    'aktion-hover',      4.5, 'Primaerknopf unter dem Zeiger'),
    ('Aktion auf Karte',                  'aktion',        'flaeche-karte',     3.0, 'Schalter an, Radiopunkt, Fortschritt, Stern'),
    ('Auswahltext auf Auswahl',           'auswahl-text',  'auswahl-flaeche',   4.5, 'aktiver Menuepunkt, aktive Kennzahl, Chip'),
    ('Auswahllinie auf Auswahl',          'auswahl-linie', 'auswahl-flaeche',   3.0, 'Strich am aktiven Eintrag'),
    ('Auswahllinie auf Karte',            'auswahl-linie', 'flaeche-karte',     3.0, 'Strich des aktiven Reiters'),
    ('Auswahllinie auf abgesetzt',        'auswahl-linie', 'flaeche-abgesetzt', 3.0, 'Strich am Vorschlag unter dem Zeiger'),
    ('Text auf Auswahl',                  'text',          'auswahl-flaeche',   4.5, 'Textauswahl'),
    # Status: je Fläche 100, Schrift 700
    ('Info auf Info',                     'info-text',     'info-flaeche',      4.5, 'Hinweis, Plakette blau'),
    ('Erfolg auf Erfolg',                 'erfolg-text',   'erfolg-flaeche',    4.5, 'Vollzug, Plakette gruen'),
    ('Warnung auf Warnung',               'warnung-text',  'warnung-flaeche',   4.5, 'Warnung, Plakette orange'),
    ('Fehler auf Fehler',                 'fehler-text',   'fehler-flaeche',    4.5, 'Fehler, Schutz, Plakette rot'),
    ('Infolinie auf Info',                'info-linie',    'info-flaeche',      3.0, 'Strich am Zitat im Handbuch'),
    ('Warnlinie auf Warnung',             'warnung-linie', 'warnung-flaeche',   3.0, 'Rahmen der orangen Kennzahl'),
    ('Fehlerlinie auf Fehler',            'fehler-linie',  'fehler-flaeche',    3.0, 'Rahmen der roten Kennzahl'),
    ('Fehlerlinie auf Karte',             'fehler-linie',  'flaeche-karte',     3.0, 'Rahmen der Gefahrkarte'),
    ('Warntext auf Karte',                'warnung-text',  'flaeche-karte',     4.5, 'Tag des Extremwerts'),
    ('Erfolgslinie auf Karte',            'erfolg-linie',  'flaeche-karte',     3.0, 'erledigter Schritt, Passwortbalken'),
    ('Auf Gefahr (Loeschknopf)',          'auf-gefahr',    'gefahr',            4.5, 'Gefahrknopf, roter Zaehler'),
    ('Auf Gefahr unter dem Zeiger',       'auf-gefahr',    'gefahr-hover',      4.5, 'Gefahrknopf unter dem Zeiger'),
    ('Titel auf abgesetzt (Zaehler)',     'text-titel',    'flaeche-abgesetzt', 4.5, 'neutraler Zaehler'),
    # Kopfleiste und Markenflaechen: Leitwerte, schalten nicht um
    ('Schnee auf Dunkelblau (Kopfleiste)', 'auf-dunkel',   'dunkelblau',        4.5, 'Kopfleiste, dunkler Fuss, Bereichszeichen'),
    ('Kopfaktiv auf Dunkelblau',          'kopf-aktiv',    'dunkelblau',        3.0, 'Strich des aktiven Kopfpunkts'),
    ('Sand auf Dunkelblau (Name im Kopf)', 'sand',         'dunkelblau',        4.5, 'Nutzername in der Kopfleiste'),
    ('Schnee auf Umgebung (Kopfleiste Staging)', 'auf-dunkel', 'umgebung-flaeche', 4.5, 'Kopfleiste mit Etikett'),
    ('Umgebungstext auf Umgebung',        'umgebung-text', 'umgebung-flaeche',  4.5, 'Name und aktiver Punkt in der Kopfleiste mit Etikett'),
    ('Schnee auf Dunkelblau (Einsatzort)', 'schnee',       'dunkelblau',        3.0, 'Pin im Einsatzort-Kreis der Karte'),
    # Diagramme: Grafik nach WCAG 1.4.11
    ('Saeule auf Karte',                  'diagramm-wert', 'flaeche-karte',     3.0, 'Saeule und Balken der Diagramme'),
    ('Hoechstwert auf Karte',             'diagramm-hoechst', 'flaeche-karte',  3.0, 'hervorgehobene Saeule'),
    ('Saeule unter dem Zeiger',           'diagramm-wert-hover', 'flaeche-karte', 3.0, 'Saeule unter dem Zeiger'),
    # Papier: die Druckblaetter, immer hell
    ('Papiertext auf Papier',             'papier-text',   'papier',            4.5, 'Druckblatt'),
    ('Papiertitel auf abgesetzt',         'papier-titel',  'papier-abgesetzt',  4.5, 'Kachel im Druckblatt'),
    ('Papier leise auf Papier',           'papier-leise',  'papier',            4.5, 'Kleinzeile im Druckblatt'),
    ('Papierwarnung auf Papier',          'papier-warnung', 'papier',           4.5, 'Umgebungszeile im Druckblatt'),
    ('QR-Modul auf Schnee',               'asphalt',       'schnee',            4.5, 'QR-Code — immer dunkel auf hell, sonst liest ihn keine Kamera'),
    ('Schnee auf Markenflaeche',          'auf-dunkel',    'flaeche-marke',     4.5, 'Fuss unter der Anmeldekarte'),
    # Die Karte (Leaflet) bleibt im Dark Mode hell (E-MA-14): Leitwerte.
    ('Dunkelblau auf Schnee (Kartenschild)', 'dunkelblau', 'schnee',            4.5, 'Schild, Phasenpunkt, Pfeil, Kartenknopf'),
    ('Blau auf Schnee (Startring)',       'blau',          'schnee',            3.0, 'Ring am Ort, an dem die Spur beginnt'),
    ('Rot auf Schnee (Endring)',          'rot',           'schnee',            3.0, 'Ring am Ort, an dem die Spur endet'),
]

# Diese Paare sind ausdruecklich AUSGENOMMEN, und zwar mit Grund. Ohne die
# Liste wuerde jeder Lauf sie melden, jemand wuerde sie wegdruecken, und beim
# naechsten Mal draengte sich ein echter Befund dazwischen.
AUSNAHMEN = [
    ('Linie auf Karte', 'linie', 'flaeche-karte',
     'Trennlinie zwischen Zeilen und Rand einer Karte — Zierrat, kein '
     'Bedienelement. WCAG 1.4.11 nimmt rein dekorative Begrenzungen aus. Wo eine '
     'Linie ein Bedienelement begrenzt, steht --linie-stark.'),
    ('Linie auf abgesetzt', 'linie', 'flaeche-abgesetzt',
     'Rahmen von Codeblock, <pre> im Handbuch, Vorschlagsgruppe und neutraler '
     'Plakette — Zierrat, kein Bedienelement; dieselbe Lage wie „Linie auf Karte".'),
    ('Mechanik auf Karte', 'mechanik', 'flaeche-karte',
     'Der Winkel des Akkordeons ist Mechanik, keine Botschaft: Er sagt nichts, '
     'was die aufklappbare Zeile daneben nicht auch sagt, und die ist in '
     'Dunkelblau beschriftet. Dasselbe gilt fuer den Winkel der '
     'Einstellungs-Uebersicht und fuer den abgeschalteten Blaetterknopf '
     '(.seitenknopf.aus) — ein deaktiviertes Bedienelement nimmt WCAG 1.4.3 '
     'ausdruecklich aus. Als FLAECHE (Blattgriff, ausgeschalteter Schalter, '
     'Bahn der Zeitleiste) faellt Mechanik ohnehin nicht darunter.'),
    ('Spurfarbe als Farbschluessel', 'spur-1', 'flaeche-karte',
     'Die Linie der Legende (.legende-linie) zeigt die Farbe der Spur auf der '
     'Karte; was sie bedeutet, sagt die Beschriftung daneben.'),
    ('Saeule als Balken auf Linie', 'diagramm-wert', 'linie',
     'Die Fuellung eines Anteilsbalkens auf seiner Spur (.balken-fuellung, '
     'R4-24): Zahl und Anteil stehen in derselben Zeile, der Balken traegt '
     'nichts allein. Die Spur in Linie ist die freigegebene Form (M-R4-24).'),
    ('Radiopunkt gewaehlt', 'auswahl-linie', 'aktion',
     'Rand und Fuellung des gewaehlten Radiopunkts (.wahl-punkt) sind dieselbe '
     'Farbe; der Ring in --flaeche-feld dazwischen macht den Punkt.'),
    ('Linie im Feld', 'linie', 'flaeche-feld',
     'Trenner zwischen zwei Tasten einer Segmentwahl — der Rand der ganzen '
     'Wahl steht in --linie-stark.'),
    ('Linie auf Schnee (Karte laedt)', 'linie', 'schnee',
     'Ring der Ladeanzeige ueber der Karte (.geo .ladeplatz) — Zierrat, '
     'die Bewegung traegt die Aussage.'),
    ('Rauch unter der Karte', 'rauch', 'schnee',
     'Flaeche des Kartenrahmens, bevor die Kacheln da sind (.geo); darauf '
     'steht nichts.'),
    ('Speicherbalken', 'diagramm-4', 'flaeche-karte',
     'Die vier Teile des Speicherbalkens (.sb-*, --diagramm-1 bis -4) sind '
     'Kategorien mit Legende und Zahl daneben; der Balken traegt nichts allein. '
     'Sand (Dateien) erreicht als Flaeche 1,5:1 und war so freigegeben.'),
    ('Speicherbalken 1', 'diagramm-1', 'flaeche-karte', 'wie „Speicherbalken".'),
    ('Speicherbalken 2', 'diagramm-2', 'flaeche-karte', 'wie „Speicherbalken".'),
    ('Speicherbalken 3', 'diagramm-3', 'flaeche-karte', 'wie „Speicherbalken".'),
    ('Kartenzeichen auf heller Karte', 'genem-dunkelblau', 'genem-blau-100',
     'Rand des hervorgehobenen Phasenpunkts und Flaeche des aktiven '
     'Kartenknopfs (.pm-chip.hl, .map-ctrl-*): Sie liegen auf der Karte, und '
     'die bleibt im Dark Mode hell (E-MA-14). Dunkelblau auf blau 100 11,26:1.'),
]


VORN = re.compile(r'(?:^|;)\s*(color|border(?:-[a-z]+)*|outline(?:-[a-z]+)*|fill|stroke|'
                  r'text-decoration(?:-color)?|caret-color)\s*:\s*([^;]*)')
HINTEN = re.compile(r'(?:^|;)\s*(background(?:-color)?)\s*:\s*([^;]*)')


def regeln_lesen(text):
    """[(Selektor, Deklarationen)] — Kommentare weg, @media-Bloecke aufgeloest."""
    text = re.sub(r'/\*.*?\*/', ' ', text, flags=re.S)
    aus = []

    def zerlege(s):
        i = 0
        while i < len(s):
            j = s.find('{', i)
            if j < 0:
                return
            sel, tiefe, k = s[i:j].strip(), 1, j + 1
            while tiefe and k < len(s):
                tiefe += {'{': 1, '}': -1}.get(s[k], 0)
                k += 1
            inhalt = s[j + 1:k - 1]
            if sel.startswith('@'):
                if '{' in inhalt:
                    zerlege(inhalt)
            elif sel != ':root':
                aus.append((' '.join(sel.split()), inhalt))
            i = k
    zerlege(text)
    return aus


def ableiten(text, tok):
    """(paare, nur_vorn, nur_hinten) — je Token bzw. Paar die Selektoren."""
    paare, nur_vorn, nur_hinten = {}, {}, {}
    for sel, d in regeln_lesen(text):
        def token(wert):
            return [x for x in re.findall(r'var\(--([\w-]+)\)', wert) if x in tok]
        vorn = [x for _, w in VORN.findall(d) for x in token(w)]
        hinten = [x for _, w in HINTEN.findall(d) for x in token(w)]
        if vorn and hinten:
            for a in vorn:
                for b in hinten:
                    if a != b:
                        paare.setdefault((a, b), set()).add(sel)
        elif vorn:
            for a in vorn:
                nur_vorn.setdefault(a, set()).add(sel)
        elif hinten:
            for b in hinten:
                nur_hinten.setdefault(b, set()).add(sel)
    return paare, nur_vorn, nur_hinten


def ohne_eintrag(text, tok, paare_liste=None, ausnahmen=None):
    """(Zahl der Paare, [(Schluessel, Befund)]) — Paare und Farben des
    Stylesheets, die in keiner Liste stehen. Der Schluessel ist das Paar oder
    die Farbe, damit die Selbstprobe nach DEM Befund fragen kann, den sie
    eingebaut hat, statt nur zu zaehlen."""
    paare_liste = PAARE if paare_liste is None else paare_liste
    ausnahmen = AUSNAHMEN if ausnahmen is None else ausnahmen
    bekannt = {(a, b) for _, a, b, *_ in paare_liste} | {(a, b) for _, a, b, _ in ausnahmen}
    vorn_bekannt = {a for a, _ in bekannt}
    alle_bekannt = vorn_bekannt | {b for _, b in bekannt}
    paare, nur_vorn, nur_hinten = ableiten(text, tok)
    befunde = []
    for (a, b), sels in sorted(paare.items()):
        if (a, b) not in bekannt:
            befunde.append(((a, b), '%s auf %s (%.2f:1) in %s' % (
                a, b, kontrast(tok[a], tok[b]), ', '.join(sorted(sels)[:3]))))
    for a, sels in sorted(nur_vorn.items()):
        if a not in vorn_bekannt:
            befunde.append((a, '%s als Vordergrund ohne Paar in %s' % (a, ', '.join(sorted(sels)[:3]))))
    for b, sels in sorted(nur_hinten.items()):
        if b not in alle_bekannt:
            befunde.append((b, '%s als Flaeche ohne Paar in %s' % (b, ', '.join(sorted(sels)[:3]))))
    return len(paare), befunde


def selbstprobe():
    """Die zwei Fehler, um die es geht, im Web nachgebaut."""
    text = io.open(CSS, encoding='utf-8').read()
    tok = token_lesen()
    faelle = [
        ('GEGENPROBE: das Stylesheet, wie es ist', '', None),
        ('ein oranger Punkt auf Rauch, den keine Liste kennt (wie B-S5Z-13)',
         '\n.rueckstand-punkt{background:var(--rauch);color:var(--orange)}\n', ('orange', 'rauch')),
        ('Rot als Schrift auf einer neuen Flaeche (wie B-S5Z-15)',
         '\n.wartet{color:var(--rot);background:var(--info-flaeche)}\n', ('rot', 'info-flaeche')),
        ('eine Flaeche in einer Farbe, die in keiner Liste steht',
         '\n.neu-kasten{background:var(--spur-3)}\n', 'spur-3'),
    ]
    fehl = 0
    for name, zusatz, erwartet in faelle:
        _, befunde = ohne_eintrag(text + zusatz, tok)
        schluessel = [s for s, _ in befunde]
        ok = (not befunde) if erwartet is None else (erwartet in schluessel)
        fehl += 0 if ok else 1
        print('  [%s] %s (%d Befunde)' % ('ok  ' if ok else 'FEHL', name, len(befunde)))
    print('%d Faelle, %d Fehlschlaege' % (len(faelle), fehl))
    return 1 if fehl else 0


def main():
    if '--selbstprobe' in sys.argv:
        return selbstprobe()
    tok = token_lesen()
    abgeleitet, befunde = ohne_eintrag(io.open(CSS, encoding='utf-8').read(), tok)
    zeilen, schlecht = [], 0
    for name, a, b, soll, rolle in PAARE:
        if a not in tok or b not in tok:
            zeilen.append({'paar': name, 'fehler': 'Token fehlt: %s / %s' % (a, b)})
            schlecht += 1
            continue
        v = kontrast(tok[a], tok[b])
        ok = v + 1e-9 >= soll
        if not ok:
            schlecht += 1
        zeilen.append({'paar': name, 'vorn': tok[a], 'hinten': tok[b],
                       'wert': round(v, 2), 'soll': soll, 'rolle': rolle, 'ok': ok})

    if '--json' in sys.argv:
        print(json.dumps({'token': tok, 'paare': zeilen,
                          'ausnahmen': [{'paar': n, 'wert': round(kontrast(tok[a], tok[b]), 2),
                                         'grund': g} for n, a, b, g in AUSNAHMEN
                                        if a in tok and b in tok],
                          'verfehlt': schlecht, 'abgeleitet': abgeleitet,
                          'ohne_eintrag': [b for _, b in befunde]}, indent=2, ensure_ascii=False))
        return 1 if schlecht or befunde else 0

    print('Kontraste der Token (WCAG 2.1, gerechnet aus server/assets/style.css)\n')
    print('%-40s %9s %6s  %s' % ('Paar', 'Ist', 'Soll', 'Rolle'))
    print('-' * 92)
    for z in zeilen:
        if 'fehler' in z:
            print('%-40s  %s' % (z['paar'], z['fehler']))
            continue
        print('%-40s %7.2f:1 %5.1f  %s%s' % (z['paar'], z['wert'], z['soll'], z['rolle'],
                                             '' if z['ok'] else '   << VERFEHLT'))
    print('\nAusgenommen, mit Grund:')
    for n, a, b, g in AUSNAHMEN:
        if a in tok and b in tok:
            print('  %-38s %7.2f:1' % (n, kontrast(tok[a], tok[b])))
            for zeile in [g[i:i + 74] for i in range(0, len(g), 74)]:
                print('      %s' % zeile)
    print('\n%d Paare gerechnet, %d verfehlt.' % (len(PAARE), schlecht))
    print('%d Paare aus den Regeln des Stylesheets abgeleitet, %d ohne Listeneintrag.'
          % (abgeleitet, len(befunde)))
    for _, b in befunde:
        print('  ! %s' % b)
    return 1 if schlecht or befunde else 0


if __name__ == '__main__':
    sys.exit(main())
