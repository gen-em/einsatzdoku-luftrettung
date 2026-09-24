# Uhr-Prüfstand

SDK und Simulator auf einem nackten Linux aufbauen, die Uhr-App übersetzen und starten.
**Anlass: Nr. 13** — ohne Arbeitsplatz war der Uhr-Code blind; 29 Warnungen wurden erst hier eine Zahl.

## Aufruf

```bash
bash tools/uhr-pruefstand/pruefstand.sh aufbau      # SDK, Geräte, Schriften
python3 tools/uhr-pruefstand/geraeteklassen.py ~/.Garmin/ConnectIQ/Devices \
        --vertreter 5 --liste vertreter.txt --alle-liste auswahl.txt
bash tools/uhr-pruefstand/pruefstand.sh reihe     auswahl.txt    # Stufe I
bash tools/uhr-pruefstand/pruefstand.sh bildreihe vertreter.txt bilder
```

**`reihe` verlangt die Listendatei** — ohne sie bricht es ab (F-PK-21).

## Was es misst

**Stufe I** übersetzt für **alle** Zielgeräte und fängt fehlende
API-Funktionen, fehlende Ressourcen und Speicherbedarf. **Stufe II** startet
den Simulator für die Vertreter je Geräteklasse und nimmt Bilder auf.
Daneben: `netzprobe/` (erreicht der Simulator 127.0.0.1?) und
`eingabe-probe/` (welche Tasten kommen an?), je mit eigener Anleitung.

## Was es braucht

`CIQ_GERAETE_URL` mit den Archiven `devices.tar` und `fonts.tar` — wie sie
gepackt werden und was bei einem fehlenden gilt: `docs/Technik.md` 5.2b.
Das SDK holt es selbst; Stufe II zusätzlich `Xvfb`.

## Erwartete Zahl

Aufbau: **SDK 9.2.0, 1332 Schriftdateien, 99 von 99 Manifest-Geräten, 0
fehlende Bibliotheken**. Stufe I: **99 übersetzt, 0 fehlgeschlagen**.

## Was es nicht kann

Kein echtes Gerät ersetzen: Was eine Uhr am Handgelenk sendet, sagt erst
`docs/Geraete-Eingabe.md`. Der Simulator kennt kein Bluetooth.
