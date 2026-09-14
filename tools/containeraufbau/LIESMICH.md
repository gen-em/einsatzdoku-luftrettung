# Containeraufbau — was eine Wegwerf-Umgebung nicht mitbringt

```
sh tools/containeraufbau/aufbau.sh            # alles
sh tools/containeraufbau/aufbau.sh android    # nur ein Teil
```

Teile: `pakete`, `browser`, `datenbank`, `android`, `python`, `alles` (Vorgabe).

## Wozu

Der Container von Claude Code on the web bringt PHP 8.4, Node 22, Python 3.11,
JDK 21 und **drei Playwright-Engines** mit (`/opt/pw-browsers`: Chromium,
Firefox, WebKit). Er bringt **nicht** mit:

| fehlt | wer es braucht |
|---|---|
| MariaDB | jede Probe mit Installation: `ingestprobe`, `spurprobe`, `komplettprobe`, `jobprobe`, `screenshots`, `referenzdatensatz`, `anteilprobe`, `wartungsprobe`, `wiederherstellungs-probe`, `pruefkonten` |
| Android-SDK unter `/opt/android-sdk` | `./gradlew build` — und `CLAUDE.md` 6 wie `android/LIESMICH.md` setzen genau diesen Pfad voraus |
| `librsvg2-bin`, `imagemagick` | `tools/uhr-bilder/erzeugen.sh`, Bildvergleiche |
| `socat` | `tools/referenzdatensatz/einspielen/lokal_starten.sh` (TLS vor dem PHP-Server) |
| `libenchant-2-2`, `libsecret-1-0`, `libwayland-server0`, `libmanette-0.2-0` | **WebKit** — ohne sie startet die Engine nicht, siehe unten |
| ein brauchbares `cryptography` | `kreislauf.py` und `einspielen.py` |

Zwei Punkte davon sehen nicht wie ein fehlendes Paket aus, und beide sind
deshalb die unangenehmen.

**`python3-cryptography` liegt im Abbild, aber ohne `_cffi_backend`.** Der
Import endet dann nicht mit einer Fehlermeldung, sondern mit

```
pyo3_runtime.PanicException: Python API call failed
```

— und wer das liest, sucht den Fehler im Skript. `pip install cffi` behebt es.

**WebKit liegt im Abbild und startet trotzdem nicht.** Seit AP3b (Backlog
Nr. 183) fahren drei Prüfmittel wahlweise Chromium, Firefox und WebKit über
`tools/motor.mjs` — der Stilvergleich immer, der Bilderlauf bei
Gestaltungsrunden, die Klickprobe auf Anforderung. Gemessen am 14.09.2026 in
einem frischen Container:

```
chromium  OK  v141.0.7390.37
firefox   OK  v142.0.1
webkit    FEHLER: Host system is missing dependencies to run browsers
```

Nach `sh tools/containeraufbau/aufbau.sh browser`:

```
chromium  141.0.7390.37
firefox   142.0.1
webkit    26.0
3 von 3 Engines starten.
```

**Warum das die gefährlichere Lücke ist als ein fehlendes MariaDB:** Eine
fehlende Datenbank hält die Probe sofort an. Eine fehlende Engine tut das
nicht — `--motor webkit` bricht ab, nachdem der Bilderlauf zwei Engines lang
gerechnet hat, oder der Lauf wird gar nicht erst gefahren und niemand merkt
es. In beiden Fällen steht am Ende keine Null, sondern gar nichts
(`CLAUDE.md` 6: „Eine grüne Zahl ist erst dann ein Beleg, wenn sie das
Gemessene benennt").

Der Teil `browser` installiert deshalb nicht nur, er **misst nach**: Er
startet jede der drei Engines einmal, nennt ihre Fassung und bricht ab, wenn
eine fehlt. Die vier Paketnamen stammen aus der Meldung von Playwright selbst
und gelten für Fassung 1.56 — eine spätere kann andere nennen, und genau
dafür ist die Gegenprobe da.

## Was es nicht tut

**Den Uhr-Prüfstand aufbauen.** Der holt sein SDK selbst
(`tools/uhr-pruefstand/pruefstand.sh aufbau`, rund 500 MB von
`developer.garmin.com`) und braucht dafür die **Gerätedateien und
Schriften**, deren Adresse (`CIQ_GERAETE_URL`) bewusst nicht im
Repositorium steht. Ohne sie bricht `aufbau` mit einem Hinweis ab — richtig
so; siehe `tools/uhr-pruefstand/LIESMICH.md`, Abschnitt „Quelle".

**Die Anwendung einrichten.** Das macht
`tools/referenzdatensatz/einspielen/lokal_einrichten.sh`.

**Die Playwright-Engines beschaffen.** `playwright install` ist ausdrücklich
**nicht** der Weg: Die Engines liegen bereits unter `/opt/pw-browsers`, und ein
Nachladen zöge eine zweite, abweichende Fassung daneben — gemessen würde dann
etwas anderes als das, was die Prüfmittel fahren. Fehlt eine Engine wirklich,
ist das ein Befund über das Abbild und keine Aufgabe dieses Skripts.

## Reihenfolge

```bash
sh tools/containeraufbau/aufbau.sh                              # 1
sh tools/referenzdatensatz/einspielen/lokal_einrichten.sh       # 2
CIQ_ZIELE=alle tools/uhr-pruefstand/pruefstand.sh aufbau        # 3 (CIQ_GERAETE_URL)
```

Danach steht alles, was ohne echte Uhr und ohne echtes Android-Gerät zu
prüfen ist.

## Netz

Die PPA-Quellen des Abbilds (`deadsnakes`, `ondrej/php`) sind hinter dem
Egress-Filter nicht erreichbar; `apt-get update` meldet dafür 403 und macht
weiter. Das ist **kein** Fehler — die Ubuntu-Hauptquellen kommen durch, und
nur die werden gebraucht. Erreichbar sind ausserdem `dl.google.com`
(Android-SDK), `developer.garmin.com` (Connect-IQ-SDK), `pypi.org`,
`registry.npmjs.org` und `services.gradle.org`.
