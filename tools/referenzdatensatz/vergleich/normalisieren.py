"""Fluechtige Anteile aus einem eingelesenen Export entfernen (E-P1-13).

WAS FLUECHTIG IST UND WARUM. Zwei Exporte desselben Bestands sind nicht
byteweise gleich, und das ist kein Fehler:

  interne IDs        gelten nur in der Datenbank, aus der der Export stammt
  Erzeugungszeit     steht im LIESMICH, im Dateinamen und in jeder GPX-Datei
  App-Version        steht im LIESMICH
  Trackdateinamen    tragen die interne Einsatz-ID
  deleted_at         Zeitpunkt des Loeschens — der Zustand bleibt vergleichbar

`created_at` STAND HIER BIS WEB 7.3.1 und steht nicht mehr hier: Seit Web
8.0.0 wird der Anlegezeitpunkt eines Einsatzes wieder eingespielt (E-S1-06),
also ist er kein fluechtiger Anteil mehr, sondern eine Angabe wie jede andere.
Genau diese Normalisierung hatte den Verlust jahrelang verdeckt — der
Kreislauf sah ihn nicht, weil das Werkzeug wegsah. Die Kopfangabe `created_at`
der DATEI (Zeitpunkt des Exports) bleibt normalisiert; sie ist tatsaechlich
fluechtig.

`deleted_at` DAGEGEN WIRD NORMALISIERT, ABER NICHT WEGGENOMMEN. Beim
Einspielen entsteht der Papierkorbeintrag neu und bekommt den
Einspielzeitpunkt (E-S1-03) — der Zeitwert kann also gar nicht ueberleben. Was
ueberleben MUSS, ist die Unterscheidung leer/gesetzt: „Papierkorbeintrag kommt
als Papierkorbeintrag zurueck" ist die Aussage, die der Kreislauf belegen soll.
Ein gesetzter Wert wird deshalb durch die Zeitmarke ersetzt, ein leerer bleibt
leer. `deleted_with_day` wird gar nicht angefasst — es ist ein Zustand ohne
Zeitbezug.

Alles davon wird durch eine Marke ersetzt, nicht geloescht: Ein Feld, das
verschwindet, faellt beim Vergleich nicht auf; eine Marke, die an der falschen
Stelle steht, schon.

WAS NICHT NORMALISIERT WIRD: Chiffretext. Er kommt hier gar nicht vor — das
CSV-Archiv traegt Klartext, und das innere JSON des Backups ebenfalls
(Backup-Format.md 2). Nur wo ein Backup einen Einsatz NICHT lesen konnte,
fuehrt sie `pat_blob` unveraendert mit; dieser Fall wird eigens gemeldet,
statt ihn stillschweigend zu vergleichen (der IV ist zufaellig, ein Vergleich
verglich also nichts).
"""
from __future__ import annotations

import json

import re

MARKE_ID = "<ID>"
MARKE_ZEIT = "<ZEIT>"
MARKE_VERSION = "<VERSION>"
MARKE_KONTO = "<KONTO>"

# tracks/mission_000042_2026-03-14_1150.gpx  ->  tracks/mission_<ID>_2026-03-14_1150.gpx
RE_TRACKNAME = re.compile(r"^(tracks/(?:mission|rest)_)(\d+)(_.*\.gpx)$")
RE_GPX_ZEIT = re.compile(r"(<metadata><time>)[^<]*(</time></metadata>)")
RE_GPX_NAME = re.compile(r"(<name>(?:Einsatz|Ruhezeit|Ruhe) )(\d+)( )")

ID_SPALTEN = {
    "einsaetze": ["einsatz_id", "diensttag_id"],
    "diensttage": ["diensttag_id"],
    "ruhezeiten": ["ruhezeit_id", "diensttag_id"],
}


def _ordnung(x):
    """Ein stabiler Sortierschluessel fuer beliebige Eintraege.

    Ueber den JSON-Text und nicht ueber ein benanntes Feld: Welches Feld die
    Reihenfolge entscheidet, ist je Liste ein anderes, und eine Liste, die
    morgen ein Feld dazubekommt, sortierte sonst weiter nach dem alten.
    """
    return json.dumps(x, ensure_ascii=False, sort_keys=True)


def _trackname(n: str) -> str:
    m = RE_TRACKNAME.match(n)
    return f"{m.group(1)}{MARKE_ID}{m.group(3)}" if m else n


def _gpx(text: str) -> str:
    text = RE_GPX_ZEIT.sub(rf"\1{MARKE_ZEIT}\2", text)
    return RE_GPX_NAME.sub(rf"\1{MARKE_ID}\3", text)


def _liesmich(text: str) -> str:
    zeilen = []
    for z in text.split("\r\n"):
        if z.startswith("Erzeugt am:"):
            z = f"Erzeugt am: {MARKE_ZEIT}"
        elif z.startswith("App-Version:"):
            z = f"App-Version: {MARKE_VERSION}"
        zeilen.append(z)
    return "\r\n".join(zeilen)


def _rea_json(text: str) -> str:
    """Reanimationsverlauf mit fester Reihenfolge der Ereignisse.

    DIESELBE URSACHE WIE BEI DEN STAMMDATEN WEITER UNTEN (siehe `edbak`):
    Zwei Ereignisse in DERSELBEN Minute haben keine zugesagte Reihenfolge, und
    der Bestand fuehrt genau so einen Fall — an D06, weil der Dienst ueber die
    Fruehjahrsumstellung laeuft und die Stunde danach nicht existiert. Im
    CSV-Weg steht der Verlauf als eine Zeichenkette in der Spalte `rea_json`;
    ohne diese Zeile meldete der Vergleich die ganze Spalte als geaendert und
    zeigte zwei Texte, die sich in zwei vertauschten Woertern unterscheiden.

    NUR DIE REIHENFOLGE. Ein Ereignis, das FEHLT oder eine andere Art traegt,
    faellt weiter auf -- die Gegenprobe in `vergleichen.py` belegt beides.
    Ist die Spalte kein brauchbares JSON, bleibt sie, wie sie ist: Ein
    Vergleich, der unlesbare Werte stillschweigend gleichmacht, prueft nichts.
    """
    try:
        daten = json.loads(text)
    except (ValueError, TypeError):
        return text
    if not isinstance(daten, list):
        return text
    for sitzung in daten:
        ev = isinstance(sitzung, dict) and sitzung.get("ereignisse")
        if isinstance(ev, list):
            sitzung["ereignisse"] = sorted(ev, key=_ordnung)
    daten.sort(key=_ordnung)
    return json.dumps(daten, ensure_ascii=False, separators=(",", ":"))


def archiv(a: dict) -> dict:
    """Normalisiert ein mit lesen.lesen_archiv() eingelesenes Archiv."""
    aus: dict = {}
    for tabelle, ids in ID_SPALTEN.items():
        zeilen = []
        for z in a.get(tabelle, []):
            n = dict(z)
            for sp in ids:
                if sp in n:
                    n[sp] = MARKE_ID
            if n.get("track_datei"):
                n["track_datei"] = _trackname(n["track_datei"])
            if n.get("rea_json"):
                n["rea_json"] = _rea_json(n["rea_json"])
            zeilen.append(n)
        aus[tabelle] = zeilen
    aus["felder"] = [dict(z) for z in a.get("felder", [])]
    aus["liesmich"] = _liesmich(a.get("liesmich", ""))
    aus["tracks"] = {_trackname(k): _gpx(v) for k, v in a.get("tracks", {}).items()}
    aus["dateiliste"] = sorted(_trackname(n) for n in a.get("dateiliste", []))
    return aus


def _papierkorb(n: dict) -> None:
    """`deleted_at` auf die Zeitmarke setzen — leer bleibt leer.

    Die Unterscheidung ist der Punkt: Der Zeitpunkt entsteht beim Einspielen
    neu, der ZUSTAND muss den Umlauf ueberstehen.
    """
    if "deleted_at" in n:
        n["deleted_at"] = MARKE_ZEIT if n["deleted_at"] else None


def edbak(b: dict) -> dict:
    """Normalisiert ein mit lesen.lesen_edbak() geoeffnetes inneres JSON.

    Die Diensttag-Kennung wird nicht verworfen, sondern durch ihre STELLE in
    der Liste ersetzt (`tag#3`). Sonst ginge die Zuordnung Einsatz -> Diensttag
    verloren — und genau die soll ein Umlauf ja belegen.
    """
    aus = {k: v for k, v in b.items() if k not in ("created_at", "user", "$container")}
    aus["created_at"] = MARKE_ZEIT
    aus["user"] = MARKE_KONTO

    tage = []
    stelle: dict = {}
    for i, d in enumerate(b.get("days", [])):
        n = dict(d)
        if "id" in n:
            stelle[n["id"]] = f"tag#{i}"
            n["id"] = f"tag#{i}"
        _papierkorb(n)
        tage.append(n)
    aus["days"] = tage

    def zeilen(name: str) -> list:
        out = []
        for z in b.get(name, []):
            n = dict(z)
            if "day_id" in n:
                n["day_id"] = stelle.get(n["day_id"], f"unbekannt:{n['day_id']}")
            _papierkorb(n)
            out.append(n)
        return out

    aus["missions"] = zeilen("missions")
    aus["rest_segments"] = zeilen("rest_segments")

    # ---- Reihenfolgen, die niemand zugesagt hat -------------------------
    #
    # ZWEI STELLEN, EINE URSACHE: Die Sicherung schreibt Stammdaten und
    # Reanimationsereignisse in der Reihenfolge, die die Datenbank liefert.
    # Bei GLEICHEM Sortierwert ist die nicht festgelegt -- MySQL darf zwei
    # Zeilen mit demselben `name` in jeder Reihenfolge zurueckgeben, und nach
    # einem Umlauf in ein frisches Konto tut es das auch.
    #
    # BEIDE FAELLE STEHEN SEIT DEM DEMO-AUSBAU IM BESTAND:
    #   - `Bergwacht Sonnenau` gibt es an ZWEI Standorten. Stammdaten sind je
    #     Standort (E15), Dubletten ueber Standorte hinweg sind zulaessig --
    #     und im Bestand gewollt (E-DA-10).
    #   - An D06 liegen zwei Reanimationsereignisse in DERSELBEN Minute. Das
    #     ist kein Versehen: Der Dienst laeuft ueber die Fruehjahrsumstellung,
    #     und die Stunde danach gibt es nicht.
    #
    # Der Vergleich meldete daraufhin zwoelf Abweichungen, die keine waren:
    # paarweise vertauschte `base_ref`-Werte und zwei vertauschte
    # Ereignisarten. Eine Ausnahmeregel waere hier falsch -- sie erklaerte
    # etwas, das gar nicht abweicht. Normalisiert wird deshalb die
    # REIHENFOLGE, und nur sie: Was in den Zeilen steht, bleibt unangetastet,
    # und ein geaenderter Wert faellt weiter auf (Probe aufs Exempel in
    # vergleichen.py).
    if isinstance(aus.get("stammdaten"), dict):
        sd = {}
        for name, liste in aus["stammdaten"].items():
            sd[name] = (sorted(liste, key=_ordnung) if isinstance(liste, list) else liste)
        aus["stammdaten"] = sd
    for m in aus["missions"]:
        for sitzung in (m.get("resus") or []):
            if isinstance(sitzung, dict) and isinstance(sitzung.get("events"), list):
                sitzung["events"] = sorted(sitzung["events"], key=_ordnung)
        if isinstance(m.get("resus"), list):
            m["resus"] = sorted(m["resus"], key=_ordnung)
    # Der Pruefwert des Inhaltsschluessels haengt am KONTO, nicht am Bestand.
    # Nach einem Umlauf in ein frisches Konto ist er zwangslaeufig ein anderer.
    if "pat_key_check" in aus:
        aus["pat_key_check"] = "<SCHLUESSELPRUEFWERT>"
    return aus
