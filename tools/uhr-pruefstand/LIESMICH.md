# Uhr-Prüfstand

SDK und Simulator auf einem nackten Linux aufbauen, die Uhr-App übersetzen und starten.
**Anlass: Nr. 13** — ohne Arbeitsplatz war der Uhr-Code blind; 29 Warnungen wurden erst hier eine Zahl.

## Aufruf

```bash
bash tools/uhr-pruefstand/pruefstand.sh aufbau      # SDK, Geräte, Schriften
python3 tools/uhr-pruefstand/geraeteklassen.py ~/.Garmin/ConnectIQ/Devices \
        --vertreter 5 --liste vertreter.txt --alle-liste auswahl.txt
bash tools/uhr-pruefstand/pruefstand.sh reihe     auswahl.txt    # Stufe I
bash tools/uhr-pruefstand/pruefstand.sh bildreihe vertreter.txt bilder "Down,Down,wait:3"
```

**`reihe` verlangt die Listendatei** (F-PK-21). Tastenfolge (Nr. 92): Kopf
von `bildreihe()`; nicht koppeln lassen — die App zeigt auf Produktiv.

## Was es misst

**Stufe I** übersetzt für **alle** Zielgeräte und fängt fehlende
API-Funktionen, fehlende Ressourcen und Speicherbedarf. **Stufe II** startet
den Simulator für die Vertreter je Geräteklasse und nimmt Bilder auf.
Daneben, je mit Anleitung: `netzprobe/`, `eingabe-probe/`, `krypto-probe/`
(S11-02) und `rundlauf/` (koppeln, verschlossen senden, öffnen; S11-08).

## Was es braucht

`CIQ_GERAETE_URL` mit `devices.tar` und `fonts.tar` (`docs/Technik.md` 5.2b);
SDK und Simulator-Bibliotheken holt es selbst, seit R17-19 nur mit passender
SHA-256 (`SDK_SHA256`, F-R17-20); Stufe II `Xvfb`.

## Erwartete Zahl

Aufbau: **SDK 9.2.0 (Summe stimmt), 1332 Schriften, 99/99 Geräte, 0 fehlende Bibliotheken**. Stufe I: **99 übersetzt, 0 rot**.

## Was es nicht kann

Kein echtes Gerät ersetzen (`docs/Geraete-Eingabe.md`), kein Bluetooth;
`speicher-leeren` wirkt nur bei beendetem Simulator; Gerätedateien ohne Summe.
