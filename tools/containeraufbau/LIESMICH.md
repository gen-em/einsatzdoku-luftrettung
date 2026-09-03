# Containeraufbau — was eine Wegwerf-Umgebung nicht mitbringt

```
sh tools/containeraufbau/aufbau.sh            # alles
sh tools/containeraufbau/aufbau.sh android    # nur ein Teil
```

Teile: `pakete`, `datenbank`, `android`, `python`, `alles` (Vorgabe).

## Wozu

Der Container von Claude Code on the web bringt PHP 8.4, Node 22, Python 3.11,
JDK 21 und Chromium mit. Er bringt **nicht** mit:

| fehlt | wer es braucht |
|---|---|
| MariaDB | jede Probe mit Installation: `ingestprobe`, `spurprobe`, `komplettprobe`, `jobprobe`, `screenshots`, `referenzdatensatz` |
| Android-SDK unter `/opt/android-sdk` | `./gradlew build` — und `CLAUDE.md` 6 wie `android/LIESMICH.md` setzen genau diesen Pfad voraus |
| `librsvg2-bin`, `imagemagick` | `tools/uhr-bilder/erzeugen.sh`, Bildvergleiche |
| `socat` | `tools/referenzdatensatz/einspielen/lokal_starten.sh` (TLS vor dem PHP-Server) |
| ein brauchbares `cryptography` | `kreislauf.py` und `einspielen.py` |

Der letzte Punkt ist der unangenehmste, weil er nicht wie ein fehlendes Paket
aussieht: `python3-cryptography` liegt im Abbild, aber ohne `_cffi_backend`.
Der Import endet dann nicht mit einer Fehlermeldung, sondern mit

```
pyo3_runtime.PanicException: Python API call failed
```

— und wer das liest, sucht den Fehler im Skript. `pip install cffi` behebt es.

## Was es nicht tut

**Den Uhr-Prüfstand aufbauen.** Der holt sein SDK selbst
(`tools/uhr-pruefstand/pruefstand.sh aufbau`, rund 500 MB von
`developer.garmin.com`) und braucht dafür die **Gerätedateien und
Schriften**, deren Adresse (`CIQ_GERAETE_URL`) bewusst nicht im
Repositorium steht. Ohne sie bricht `aufbau` mit einem Hinweis ab — richtig
so; siehe `tools/uhr-pruefstand/LIESMICH.md`, Abschnitt „Quelle".

**Die Anwendung einrichten.** Das macht
`tools/referenzdatensatz/einspielen/lokal_einrichten.sh`.

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
