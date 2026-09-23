# Uhr-Prüfstand

Connect-IQ-SDK und Simulator auf einem nackten Linux aufbauen, die Uhr-App
übersetzen und starten. **Anlass:** Wegwerf-Umgebungen, in denen nach jeder
Sitzung alles fort ist.

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

**Stufe I** übersetzt für **alle** Zielgeräte (Sekunden je Gerät) und fängt
fehlende API-Funktionen, fehlende Ressourcen und Speicherbedarf. **Stufe II**
startet den Simulator für die Vertreter je Geräteklasse und nimmt Bilder auf
— Layout, Bedienhinweise, Abstürze beim Zeichnen. Daneben liegen
`netzprobe/` (kommt der Simulator an 127.0.0.1 heran?) und `eingabe-probe/`.

## Was es braucht

`CIQ_GERAETE_URL` für die Gerätedateien; das SDK holt es selbst. Stufe II
zusätzlich eine Grafikumgebung (`Xvfb`).

**Unter der Adresse liegen zwei Archive** — `devices.tar` und `fonts.tar`,
gepackt im Ordner `~/.Garmin/ConnectIQ` mit `tar cf devices.tar Devices` und
`tar cf fonts.tar Fonts`, **immer der ganze Bestand** (sonst sieht
`geraeteklassen.py` kein neues Gerät). Je eine Anfrage: 47 s statt rund
31 min über Einzeldateien (Lauf #253). Fehlt ein Archiv, holt das Skript die
Einzeldateien per `wget -r` — langsam, mit Warnung, dafür braucht die Quelle
eine Verzeichnisauflistung. Lässt sich ein Archiv nicht entpacken oder liegt
es eine Ebene zu tief, ist der Lauf rot. **Wer Geräte nachlädt, packt beide
Archive neu** — sonst holt die Kette ohne Warnung den alten Stand.

## Erwartete Zahl

Aufbau: **SDK 9.2.0, 1332 Schriftdateien, 99 von 99 Manifest-Geräten, 0
fehlende Bibliotheken**. Stufe I: **99 übersetzt, 0 fehlgeschlagen**.

## Was es nicht kann

Kein echtes Gerät ersetzen: Was eine Uhr am Handgelenk sendet, sagt erst
`docs/Geraete-Eingabe.md`. Der Simulator kennt kein Bluetooth.
