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
tools/uhr-pruefstand/pruefstand.sh einstellungen-leeren
tools/uhr-pruefstand/pruefstand.sh beenden
```

`bauen` reicht alle weiteren Schalter an `monkeyc` durch — `-w` für Warnungen,
`-l 3` für die strenge Typprüfung, `--build-stats 0` für Speicherzahlen.
`alle` baut die drei Zielgeräte hintereinander.

Für die Eingabe-Probe genügt ein anderer Jungle-Pfad:

```bash
tools/uhr-pruefstand/pruefstand.sh bauen venu3s tools/eingabe-probe/monkey.jungle
```

## Viele Geräte prüfen — zwei Stufen

Über 170 Geräte einzeln durchzuklicken ist weder nötig noch bezahlbar. Der
Hebel: **Übersetzen ist billig, Simulieren ist teuer.**

| | Aufwand je Gerät | Läuft über | Fängt |
|---|---|---|---|
| **Stufe I** `reihe` | ~3 s | **alle** Zielgeräte | fehlende API-Funktionen, fehlende Ressourcen, Speicherbedarf |
| **Stufe II** `bildreihe` | ~50 s | nur **Vertreter** je Klasse | Layout, Bedienhinweise, Abstürze beim Zeichnen |

```bash
python3 geraeteklassen.py ~/.Garmin/ConnectIQ/Devices \
        --vertreter 5 --liste vertreter.txt --alle-liste auswahl.txt
pruefstand.sh reihe     auswahl.txt          # Stufe I
pruefstand.sh bildreihe vertreter.txt bilder # Stufe II
```

### Wonach `geraeteklassen.py` gruppiert

Nicht nach Garmins Katalog, sondern nach den vier Achsen, die **diese App**
tatsächlich unterscheidet — Herleitung im Kopf des Skripts:

| Achse | Wo im Code |
|---|---|
| Displayform | `Ui.chordW()` rechnet eine Kreissehne, nimmt also ein rundes Display an |
| Schwelle 320 px | `Ui.fontHint()` springt dort von `FONT_TINY` auf `FONT_XTINY` |
| Eingabe (Touch, Tastenzahl) | `DeviceProfile.HAS_UP_DOWN`, Zuordnung im Jungle |
| Speicher | `appTypes[watchApp].memoryLimit`; die App belegt im Leerlauf rund 54 kB |

Die Displayhöhe **innerhalb** einer Klasse bildet keine eigene Klasse:
`Ui.s()` und `Ui.pct()` skalieren stetig. Deshalb zieht das Skript je Klasse
nicht ein Gerät, sondern eine Spanne — die Höhen-Extreme zuerst, dann
gleichmäßig aufgefüllt.

### Was der Compiler nicht fängt

Stufe I ist blind für den gefährlichsten Fehler: `base.sourcePath` im Jungle
steht auf dem **Fünf-Tasten-Profil**. Ein neu eingetragenes Gerät erbt es
stillschweigend. Für ein Touch-Gerät mit zwei nutzbaren Tasten übersetzt das
sauber und ist auf dem Gerät **unbedienbar** — genau der Fall, den
`docs/Geraete-Eingabe.md` für die Venu 3s beschreibt. Die Eingabe-Zuordnung
bleibt Handarbeit je Klasse, mit `tools/eingabe-probe`.

## App-Einstellungen: der Simulator merkt sie sich

Wer eine Vorgabe in `watch/resources/settings/properties.xml` ändert und neu
übersetzt, sieht im Simulator trotzdem den **alten** Wert. Der Grund:

```
/tmp/com.garmin.connectiq/GARMIN/APPS/SETTINGS/<GERAET>.SET
```

Diese Datei wird beim **ersten** Laden aus den Vorgaben des Kompilats gefüllt
und danach nicht mehr angefasst — sie gewinnt über jedes weitere Kompilat. Am
31.08.2026 hat das zwei Läufe lang dieselbe Bildmarke gezeigt, obwohl das
zweite Kompilat die andere trug; erst `einstellungen-leeren` brachte sie zum
Vorschein. Vor jedem Lauf, der eine geänderte Vorgabe prüfen soll:

```bash
pruefstand.sh einstellungen-leeren
```

**Das Verzeichnis muss stehen bleiben.** Wird es ganz entfernt, wirft
`Properties.getValue()` einen Fehler, den ein `catch (e)` **nicht** fängt — die
App stirbt beim ersten Zeichnen, das Display bleibt schwarz. Das ist ein
Artefakt des Simulators: Auf einem Gerät legt die Installation den Speicher an.
Ein fehlender **Schlüssel** dagegen wird sauber gefangen (geprüft mit einer
Probe auf `"gibtsNicht"`, die brav auf die Vorgabe zurückfällt) — der Fall
„App-Aktualisierung bringt eine neue Einstellung mit" ist also abgedeckt.

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
- **Anzeige und Simulator brauchen `setsid`, nicht nur `nohup`.** Eine
  Werkzeugumgebung, die jeden Befehl in einer eigenen Shell ausführt, räumt
  beim Verlassen die ganze Prozessgruppe ab — die mit `nohup ... &` gestartete
  Anzeige war dann schon fort, bevor der nächste Befehl sie brauchte
  (`unable to open display ":99"`, und der Simulator bricht mit
  „Can't create a GtkStyleContext without a display connection" ab). Das
  Skript startet beides deshalb mit `setsid`.
- **`pkill -f monkeydo` erwischt die eigene Shell**, wenn das Muster im
  Kommandozeilentext dieser Shell vorkommt — bei einem Aufruf über ein
  Werkzeug, das den ganzen Befehl in `bash -c` einbettet, ist das die Regel.
  Der Aufruf endet mit Rückgabewert 144, und nichts läuft. Deshalb steht der
  Reihendurchlauf in einer **Skriptdatei**: Deren Kommandozeile lautet nur
  `bash lauf.sh`, das Muster kommt darin nicht vor.
- **`starten` kehrt nicht zurück, wenn seine Ausgabe in eine Pipe geht.**
  `monkeydo` erbt die Standardausgabe und hält die Verbindung zum Simulator,
  solange die App läuft; der Leser am anderen Ende der Pipe wartet folglich auf
  einen Prozess, der nicht endet — auch wenn `monkeydo` abgelöst gestartet
  wurde. In eine **Datei** umgeleitet kehrt derselbe Aufruf sofort zurück:

  ```bash
  pruefstand.sh starten fenix6pro 5 >lauf.log 2>&1 </dev/null   # kehrt zurueck
  pruefstand.sh starten fenix6pro 5 | tail                      # blockiert
  ```

  Für einen Reihendurchlauf über mehrere Geräte ist der direkte Weg
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
