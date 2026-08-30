# Uhr-Prüfstand

Baut das Connect-IQ-SDK samt Simulator auf einem nackten Linux-Rechner auf,
übersetzt die Uhr-App und startet sie im Simulator — ohne Fensteroberfläche,
ohne installiertes SDK, ohne Garmin-Anmeldung.

Wozu: Die Uhr-App ließ sich bislang nur am Arbeitsplatz prüfen, an dem VS Code
und das SDK eingerichtet sind. Damit war jede Änderung am Monkey-C-Code für
eine Wegwerf-Umgebung (Claude Code on the web, ein CI-Läufer) blind — es gab
kein Kompilat und keinen Simulatorlauf, nur Lesen. Dieses Skript schließt die
Lücke. **Am Arbeitsplatz wird es nicht gebraucht**; dort tut es die
Monkey-C-Erweiterung besser.

## Was fehlt und woher es kommt

Der Aufbau hat vier Teile, und nur der letzte ist unangenehm:

| Teil | Herkunft | Anmeldung nötig |
|---|---|---|
| SDK (Compiler, Simulator) | `developer.garmin.com` | nein |
| Systembibliotheken | Ubuntu-Paketquellen | nein |
| Entwicklerschlüssel | `openssl`, selbst erzeugt | nein |
| **Geräteedateien und Schriften** | **eigene Bereitstellung** | — |

Die ersten drei löst das Skript allein. Der vierte ist der Haken: Gerätedateien
(`Devices/`) und Zeichensätze (`Fonts/`) liefert nur der **SDK-Manager** aus,
und der ist eine Fensteranwendung, die eine Garmin-Anmeldung verlangt. Auf
einem Rechner ohne Bildschirm ist er nicht zu bedienen.

### Quelle

Deshalb der Umweg: Wer am Arbeitsplatz ein eingerichtetes SDK hat, stellt den
Inhalt von `~/.Garmin/ConnectIQ` (unter Windows
`%USERPROFILE%\.Garmin\ConnectIQ`) über HTTPS bereit — es genügen die
Verzeichnisse `Devices/` und `Fonts/`, die Unterverzeichnisse `Sdks/` werden
nicht gebraucht. Die Adresse kommt als Umgebungsvariable herein:

```bash
export CIQ_GERAETE_URL=https://beispiel.invalid/ciq
tools/uhr-pruefstand/pruefstand.sh aufbau
```

**Die Adresse steht bewusst nicht in diesem Repositorium.** Es ist öffentlich,
die Dateien gehören Garmin, und eine Bereitstellung für den eigenen Gebrauch
ist etwas anderes als eine Veröffentlichung. Aus demselben Grund werden die
Dateien nicht eingecheckt.

## Bedienung

```bash
tools/uhr-pruefstand/pruefstand.sh aufbau        # einmal je Rechner
tools/uhr-pruefstand/pruefstand.sh pruefen       # Bestand auflisten
tools/uhr-pruefstand/pruefstand.sh bauen fenix6pro -l 3
tools/uhr-pruefstand/pruefstand.sh starten fenix6pro
tools/uhr-pruefstand/pruefstand.sh abbild /tmp/start.png
tools/uhr-pruefstand/pruefstand.sh beenden
```

`bauen` reicht alle weiteren Schalter an `monkeyc` durch — `-w` für Warnungen,
`-l 3` für die strenge Typprüfung, `--build-stats 0` für Speicherzahlen.
`alle` baut die drei Zielgeräte hintereinander.

Für die Eingabe-Probe genügt ein anderer Jungle-Pfad:

```bash
tools/uhr-pruefstand/pruefstand.sh bauen venu3s tools/eingabe-probe/monkey.jungle
```

## Bedienung simulieren

Tastendrücke und Touch gehen als X-Ereignisse an das Simulatorfenster:

```bash
pruefstand.sh tippen 290 410              # Touch-Tipp
pruefstand.sh halten 290 410 1.5          # Langdruck, 1,5 s
pruefstand.sh wischen 290 300 500         # Wischen nach unten
pruefstand.sh taste Return                # Tastendruck
```

Die Koordinaten sind **Fenster**-, nicht Gerätekoordinaten. Die Statusleiste
des Simulators zeigt unten rechts die umgerechnete Geräteposition — daran
lässt sich ablesen, ob man die Displayfläche getroffen hat.

Gemessen am 30.08.2026 mit der Eingabe-Probe auf der Venu 3S:

| Geste | Ergebnis |
|---|---|
| Tipp | `>> onSelect` |
| Wischen nach unten | `>> onPreviousPage` |
| Wischen nach oben | `>> onNextPage` |
| Halten 1,5 s | `HOLD x= y=` gefolgt von `RELEASE` |

## Grenzen

Was der Prüfstand **nicht** leistet, und woran das liegt:

- **Keine echte Hardware.** Alles, was `docs/Geraete-Eingabe.md` über den
  Unterschied zwischen Simulator und Gerät festhält, gilt unverändert — allen
  voran, dass der Simulator Systemgesten außerhalb der App nicht abbildet und
  ein Halten über 4,6 s dort nicht nachzustellen war.
- **Keine Kopplung, kein Server.** Die App meldet im Simulator „Server fehlt".
  Das ist richtiges Verhalten, kein Fehler.
- **Der Bildabzug zeigt das Fenster**, nicht das Gerät: Menüleiste und
  Uhrengehäuse sind mit drauf. Wer nur die Displayfläche will, schneidet nach.
- **Der Zustand ist flüchtig.** In einer Wegwerf-Umgebung ist nach dem
  Sitzungsende alles fort; der Aufbau läuft beim nächsten Mal erneut. Die rund
  1,2 GB Schriften sind dabei der langsame Teil.
- **Ein Lauf im Simulator ersetzt das Lesen nicht.** Er zeigt, dass es startet
  und wie es aussieht — nicht, dass es richtig ist.
- **`starten` kehrt in einer nicht-interaktiven Umgebung nicht immer zurück.**
  `monkeydo` hält die Verbindung zum Simulator, solange die App läuft; startet
  man mehrere Geräte in einer Schleife, wartet der Aufrufer unter Umständen auf
  den Kindprozess, obwohl er abgelöst gestartet wird. Interaktiv fällt das
  nicht auf. Für einen Reihendurchlauf über mehrere Geräte ist der direkte Weg
  zuverlässiger:

  ```bash
  monkeydo "$CIQ_BASIS/bin/fenix6pro.prg" fenix6pro >lauf.log 2>&1 &
  MD=$!; sleep 26; kill $MD
  ```

## Warum die Bibliotheken aus 22.04 kommen

Der Simulator ist gegen `webkit2gtk 4.0` gebunden. Ubuntu 24.04 führt nur noch
4.1, und die 4.0-Pakete sind dort ersatzlos fort. Das Skript holt die alten
Stände aus 22.04 und legt sie **neben** den Simulator statt sie ins System zu
installieren: So bleibt der Rechner für alles andere unberührt, das 4.1
erwartet. Betroffen sind `libwebkit2gtk-4.0`, `libjavascriptcoregtk-4.0`,
`libsoup2.4` und `libicu70`.
