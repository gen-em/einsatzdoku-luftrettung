"""Die äußere Adresse des Läufers — für das Protokoll von Wache und Zielprobe.

Wozu: Produktiv war für einen Teil der GitHub-Läufer zeitweise per HTTPS
nicht erreichbar, FTPS ging. Ohne die Adresse im Protokoll bleibt ein
Muster — dieselben Netze, dieselben Stunden — unsichtbar.

Der Läufer kennt seine äußere Adresse nicht; sie kommt von einem fremden
Dienst (`api.ipify.org`). Ein Abruf, 5 s Grenze, ohne Daten der Anwendung.
Scheitert er, steht „unbekannt" da, und der Aufrufer misst trotzdem weiter:
Die Adresse ist eine Beobachtung, kein Teil der Messung.
"""
from __future__ import annotations

import ipaddress
import os
import urllib.request

DIENST = "https://api.ipify.org"
ZEITGRENZE_S = 5


def adresse(dienst: str = DIENST, zeitgrenze: float = ZEITGRENZE_S) -> str:
    """Die äußere Adresse oder „unbekannt (Grund)". Wirft nie.

    `LAEUFER_ADRESSE_AUS=1` schaltet den Abruf ab (Selbstproben ohne Netz);
    das steht dann so im Protokoll, nicht als „unbekannt"."""
    if os.environ.get("LAEUFER_ADRESSE_AUS") == "1":
        return "nicht erfragt (LAEUFER_ADRESSE_AUS=1)"
    try:
        with urllib.request.urlopen(dienst, timeout=zeitgrenze) as antwort:
            text = antwort.read(64).decode("ascii", "replace").strip()
        return str(ipaddress.ip_address(text))
    except Exception as ex:                       # noqa: BLE001 -- nie abbrechen
        return f"unbekannt ({type(ex).__name__})"


def zeile(dienst: str = DIENST, zeitgrenze: float = ZEITGRENZE_S) -> str:
    """Die Protokollzeile, wie Wache und Zielprobe sie schreiben."""
    return f"Läuferadresse:    {adresse(dienst, zeitgrenze)}"
