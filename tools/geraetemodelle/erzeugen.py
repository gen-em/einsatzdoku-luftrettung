#!/usr/bin/env python3
"""Erzeugt `server/geraetemodelle.php` — Teilenummer auf Modellname und Art.

Die Garmin-Uhr kennt ihren Modellnamen nicht. `System.getDeviceSettings()`
führt ihn nicht, und eine Modelltabelle auf einem Gerät mit 128 kB wäre der
falsche Platz. Sie sendet deshalb beim Koppeln ihre **Teilenummer**
(`006-B4261-00`), und der Server löst sie auf — so steht es im JSON-Vertrag,
Abschnitt 1a, und so hat R42 es entschieden.

Diese Zuordnung steht nirgends als Liste. Sie steckt in den **Gerätedateien
der Uhr-Plattform**, je Gerät eine `compiler.json` mit drei Angaben, die hier
gebraucht werden:

    displayName          "Venu 3S"
    webDocDeviceGroup    "Watches/Wearables"
    partNumbers[].number "006-B4261-00"   (mehrere je Gerät möglich)

Aufruf:

    python3 tools/geraetemodelle/erzeugen.py ~/.Garmin/ConnectIQ/Devices
    python3 tools/geraetemodelle/erzeugen.py <verz> --ziel server/geraetemodelle.php
    python3 tools/geraetemodelle/erzeugen.py --leer      # gültige, leere Tabelle

Rückgabewert: 0 = Datei geschrieben · 1 = keine lesbaren Gerätedateien.

## Woher die Gerätedateien kommen

Aus demselben Ort wie beim Uhr-Prüfstand, und mit derselben Einschränkung:
Sie liefert nur der **SDK-Manager** aus, eine Fensteranwendung mit
Garmin-Anmeldung. Auf einem Rechner ohne Bildschirm ist er nicht zu bedienen.
Wer am Arbeitsplatz ein eingerichtetes SDK hat, stellt `~/.Garmin/ConnectIQ`
über HTTPS bereit; die Adresse kommt als `CIQ_GERAETE_URL` herein und steht
**bewusst nicht im Repositorium** (Begründung in
`tools/uhr-pruefstand/LIESMICH.md`). Sie muss erfragt werden.

    export CIQ_GERAETE_URL=https://beispiel.invalid/ciq
    tools/uhr-pruefstand/pruefstand.sh aufbau     # holt Devices/ und Fonts/
    python3 tools/geraetemodelle/erzeugen.py ~/.Garmin/ConnectIQ/Devices

## Was NICHT in die erzeugte Datei geht

Die Gerätedateien gehören Garmin und werden nicht eingecheckt. Was hier
entsteht, ist etwas anderes: eine Zuordnung öffentlicher Teilenummern zu
öffentlichen Produktnamen — Sachangaben, keine Übernahme der Dateien. Alles
Übrige (Auflösungen, Speichergrenzen, Schriften, Bilder) bleibt draußen.
"""
import argparse
import json
import pathlib
import re
import sys
import textwrap

# --- Geräteart (R42: Uhr / Handy / Sonstiges) --------------------------------
#
# `webDocDeviceGroup` ist die Einteilung, die Garmin selbst für seine
# Dokumentation benutzt. "Watches/Wearables" ist die Uhr; alles andere, was
# Connect IQ trägt -- Radcomputer, Handgeräte -- ist für R42 "Sonstiges".
# "Handy" kann hier nie herauskommen: Eine Connect-IQ-App läuft nicht auf
# einem Handy. Die Handy-Angabe kommt aus der Android-App und geht an dieser
# Tabelle vorbei.
UHR_GRUPPE = "Watches/Wearables"

# Ein Muster, das eine Teilenummer sein könnte. Zweck ist nicht die Abwehr --
# die Dateien sind vertrauenswürdig --, sondern der Befund: Weicht eine Zeile
# ab, soll das auffallen und nicht still in der Tabelle landen.
TEIL_MUSTER = re.compile(r"^[0-9A-Za-z][0-9A-Za-z.\-]{3,31}$")


def lies_geraete(wurzel):
    """Liest je Gerätverzeichnis die compiler.json ein."""
    geraete = []
    for d in sorted(wurzel.iterdir()):
        if not d.is_dir():
            continue
        cj = d / "compiler.json"
        if not cj.exists():
            continue
        try:
            c = json.loads(cj.read_text())
        except (json.JSONDecodeError, OSError):
            continue
        geraete.append({
            "id": d.name,
            "name": (c.get("displayName") or "").strip(),
            "gruppe": (c.get("webDocDeviceGroup") or "").strip(),
            "teile": [str(p.get("number") or "").strip()
                      for p in (c.get("partNumbers") or [])
                      if isinstance(p, dict)],
        })
    return geraete


def baue_tabelle(geraete):
    """Teilenummer -> (Modellname, Art). Meldet Auffälligkeiten zurück."""
    tabelle, hinweise = {}, []
    gruppen = {}
    for g in geraete:
        gruppen.setdefault(g["gruppe"] or "(ohne)", 0)
        gruppen[g["gruppe"] or "(ohne)"] += 1

        if not g["name"]:
            hinweise.append(f"{g['id']}: kein displayName — übersprungen")
            continue
        if not g["teile"]:
            hinweise.append(f"{g['id']}: keine partNumbers — übersprungen")
            continue

        art = "uhr" if g["gruppe"] == UHR_GRUPPE else "sonstiges"
        for t in g["teile"]:
            if not TEIL_MUSTER.match(t):
                hinweise.append(f"{g['id']}: Teilenummer {t!r} passt nicht ins Muster")
                continue
            schluessel = t.upper()
            # Eine Teilenummer gehört genau einem Gerät. Trifft sie doch
            # zweimal, ist das ein Befund und keine Kleinigkeit: Die spätere
            # Statistik zählte das Gerät dann unter zwei Namen.
            if schluessel in tabelle and tabelle[schluessel][0] != g["name"]:
                hinweise.append(
                    f"{schluessel}: doppelt — {tabelle[schluessel][0]!r} und {g['name']!r}; "
                    f"der erste bleibt stehen")
                continue
            tabelle.setdefault(schluessel, (g["name"], art))
    return tabelle, hinweise, gruppen


def schreibe_php(tabelle, ziel, quelle_beschreibung):
    zeilen = []
    for schluessel in sorted(tabelle):
        name, art = tabelle[schluessel]
        zeilen.append("    '%s' => ['%s', '%s']," % (
            schluessel.replace("\\", "\\\\").replace("'", "\\'"),
            name.replace("\\", "\\\\").replace("'", "\\'"),
            art))

    n_uhr = sum(1 for v in tabelle.values() if v[1] == "uhr")
    modelle = len({v[0] for v in tabelle.values()})

    # Der Kopfkommentar wird gelesen; eine 300 Zeichen lange Zeile wird es
    # nicht. Umbruch auf die Breite, die der Rest der Datei hat.
    herkunft = textwrap.fill(quelle_beschreibung, width = 66,
                             initial_indent = " * Herkunft: ",
                             subsequent_indent = " *           ")

    inhalt = f"""<?php
declare(strict_types=1);
/**
 * Teilenummer -> [Modellname, Geraeteart]. ERZEUGT — NICHT VON HAND AENDERN.
 *
 *     python3 tools/geraetemodelle/erzeugen.py ~/.Garmin/ConnectIQ/Devices
 *
 * Wozu: Die Garmin-Uhr kennt ihren Modellnamen nicht und sendet beim Koppeln
 * ihre Teilenummer (JSON-Vertrag 1a, R42). `geraete_lib.php` loest sie hier
 * auf. Wer diese Datei von Hand ergaenzt, ergaenzt sie an der falschen Stelle
 * — der naechste Lauf des Erzeugers wirft es weg.
 *
{herkunft}
 * Bestand:  {len(tabelle)} Teilenummern auf {modelle} Modelle, davon {n_uhr} als Uhr eingestuft.
 *
 * Die Gerätedateien selbst liegen NICHT im Repositorium (sie gehoeren Garmin);
 * was hier steht, ist die Zuordnung oeffentlicher Teilenummern zu
 * oeffentlichen Produktnamen. Einordnung: docs/Lizenzen.md.
 */
const GERAETE_MODELLE = [
{chr(10).join(zeilen) if zeilen else '    // leer'}
];
"""
    ziel.write_text(inhalt, encoding="utf-8")


def main():
    p = argparse.ArgumentParser(description=__doc__,
                                formatter_class=argparse.RawDescriptionHelpFormatter)
    p.add_argument("verzeichnis", nargs="?",
                   help="Devices-Verzeichnis des Connect-IQ-SDK")
    p.add_argument("--ziel", default="server/geraetemodelle.php",
                   help="Zieldatei (Vorgabe: server/geraetemodelle.php)")
    p.add_argument("--leer", action="store_true",
                   help="Gültige, leere Tabelle schreiben — für den Fall, dass "
                        "die Gerätedateien noch nicht vorliegen")
    a = p.parse_args()
    ziel = pathlib.Path(a.ziel)

    if a.leer:
        schreibe_php({}, ziel,
                     "noch keine — mit --leer erzeugt, weil die Geraetedateien "
                     "nicht vorlagen. Jede Teilenummer bleibt bis zum naechsten "
                     "Lauf unaufgeloest und steht dann in devices.geraet_teil.")
        print(f"{ziel}: leere Tabelle geschrieben (0 Teilenummern).")
        return 0

    if not a.verzeichnis:
        p.error("entweder ein Devices-Verzeichnis oder --leer")

    wurzel = pathlib.Path(a.verzeichnis).expanduser()
    if not wurzel.is_dir():
        print(f"Kein Verzeichnis: {wurzel}", file=sys.stderr)
        return 1

    geraete = lies_geraete(wurzel)
    if not geraete:
        print(f"Keine lesbaren Gerätedateien unter {wurzel}", file=sys.stderr)
        return 1

    tabelle, hinweise, gruppen = baue_tabelle(geraete)
    if not tabelle:
        print("Gerätedateien gelesen, aber keine Teilenummer gefunden.", file=sys.stderr)
        return 1

    schreibe_php(tabelle, ziel, f"Connect-IQ-Gerätedateien, {len(geraete)} Verzeichnisse")

    modelle = len({v[0] for v in tabelle.values()})
    print(f"{ziel}: {len(tabelle)} Teilenummern auf {modelle} Modelle.")
    print("Gerätegruppen: " + ", ".join(f"{k} {v}" for k, v in sorted(gruppen.items())))
    if hinweise:
        print(f"\n{len(hinweise)} Hinweise:")
        for h in hinweise:
            print("  " + h)
    return 0


if __name__ == "__main__":
    sys.exit(main())
