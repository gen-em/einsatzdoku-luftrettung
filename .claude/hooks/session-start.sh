#!/bin/bash
# Containerstart: nachinstallieren, was der Pruefstand braucht.
#
# WOZU. Die Wegwerf-Container von Claude Code im Web bringen PHP, Node,
# Chromium, socat, zip und git mit — aber KEINEN Datenbankserver und kein
# ImageMagick. Ohne die beiden steht die Haelfte der Pruefmittel still:
#
#   MariaDB          jede Browserprobe, jeder Kreislauf, die Klickprobe,
#                    die Wartungsprobe, `lokal_einrichten.sh`
#   ImageMagick      `tools/uhr-bilder/erzeugen.sh` (convert) und jeder
#                    Bildvergleich (`compare -metric AE`)
#   rsvg-convert     dieselbe Kette: SVG -> PNG fuer die Uhr-Bilder
#   jsonschema       `tools/referenzdatensatz/` prueft seine Quelldaten damit
#   Firefox/WebKit   jede Browserprobe lief bis zum 14.09.2026 NUR in
#                    Chromium — und seit Web 19.4.1 haengt eine Darstellung an
#                    einer Container-Abfrage, seit P3 an `:has()` und `dvh`.
#                    Eine Engine, die eines davon nicht kann, fiele lautlos
#                    durch jede Pruefung (Backlog Nr. 183)
#
# Aufgestellt am 13.09.2026 in Backlog-Runde 3: AP2 konnte `erzeugen.sh`
# nicht laufen lassen (kein ImageMagick), AP4 musste MariaDB erst von Hand
# nachinstallieren. Zweimal dieselbe Viertelstunde — deshalb dieser Hook.
#
# WAS ER NICHT TUT: Er STARTET nichts. Das macht
# `tools/referenzdatensatz/einspielen/lokal_starten.sh`, und das Einrichten
# macht `lokal_einrichten.sh` daneben. Die Arbeitsteilung bleibt, wo sie war;
# hier wird nur beschafft.
set -euo pipefail

# Nur im Wegwerf-Container. Auf einer Entwicklungsmaschine hat die Person
# ihre Werkzeuge selbst installiert, und ein Hook, der dort apt anwirft,
# waere eine Zumutung.
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

export DEBIAN_FRONTEND=noninteractive

meldung() { printf '  %s\n' "$1" >&2; }

# ---- 1. Systempakete ------------------------------------------------------
#
# IDEMPOTENT UEBER DIE BEFEHLE, NICHT UEBER APT: `command -v` ist schneller
# als jede apt-Abfrage, und der Containerzustand wird nach dem Hook
# zwischengespeichert — beim zweiten Start ist hier nichts mehr zu tun.
fehlend=()
command -v mariadbd      >/dev/null || fehlend+=(mariadb-server)
command -v convert       >/dev/null || fehlend+=(imagemagick)
command -v rsvg-convert  >/dev/null || fehlend+=(librsvg2-bin)

# Systembibliotheken fuer Firefox und WebKit. Das Abbild bringt sie fuer
# Chromium mit, fuer die anderen beiden nicht — `playwright install` laedt
# dann zwar die Binaerdateien, bricht aber beim Abschlusstest ab und meldet
# genau diese Namen. Sie stehen hier, damit der naechste Container nicht
# wieder danach sucht.
for lib in libgtk-4-1 libwoff1 libevent-2.1-7t64 \
           libgstreamer-plugins-bad1.0-0 libflite1 gstreamer1.0-libav; do
  dpkg -s "$lib" >/dev/null 2>&1 || fehlend+=("$lib")
done

if [ ${#fehlend[@]} -gt 0 ]; then
  meldung "nachinstallieren: ${fehlend[*]}"
  # ERST OHNE `apt-get update`, DANN MIT. Die Paketlisten des Abbilds sind
  # meist frisch genug; sind sie es nicht, scheitert der erste Lauf mit 404
  # auf einzelne .deb (so gesehen am 13.09.2026), und erst dann lohnt der
  # Listenabgleich. Andersherum kostet jeder Start eine Minute umsonst.
  if ! apt-get install -y -qq "${fehlend[@]}" >/dev/null 2>&1; then
    meldung "erster Versuch fehlgeschlagen — Paketlisten abgleichen"
    apt-get update -qq >/dev/null 2>&1 || true
    apt-get install -y -qq "${fehlend[@]}" >/dev/null 2>&1 \
      || meldung "ACHTUNG: apt-get install fehlgeschlagen — Pruefmittel unvollstaendig"
  fi
fi

# ---- 1b. Die beiden anderen Browser-Engines -------------------------------
#
# WOFUER. Bis zum 14.09.2026 hatte der Pruefstand nur Chromium, und jede
# Aussage ueber die Oberflaeche galt genau fuer ihn. Seither liegen alle drei
# hier; Nr. 182 und Nr. 42 sind die ersten Punkte, die dreifach gemessen
# wurden (und in allen dreien uebereinstimmten).
#
# DIE ADRESSEN SIND FREIGEGEBEN, ABER ERST SEIT DEM 14.09.2026:
# `cdn.playwright.dev` und `playwright.download.prss.microsoft.com` haben bis
# dahin mit 403 geantwortet. Faellt eine Egress-Regel weg, scheitert dieser
# Block wieder — und das ist dann ein Befund mit Zahl (Abschnitt 3), kein
# stiller Ausfall.
#
# PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD ist in dieser Umgebung gesetzt, damit ein
# `npm install` nicht jedes Mal Chromium nachlaedt. Fuer diesen bewussten
# Aufruf wird es ausgehaengt.
PW=${PLAYWRIGHT_BROWSERS_PATH:-/opt/pw-browsers}
PW_CLI=/opt/node22/lib/node_modules/playwright/cli.js
if [ -f "$PW_CLI" ]; then
  for engine in firefox webkit; do
    if ! ls -d "$PW"/"$engine"-* >/dev/null 2>&1; then
      meldung "nachinstallieren: $engine (Playwright)"
      PLAYWRIGHT_BROWSERS_PATH="$PW" PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD= \
        node "$PW_CLI" install "$engine" >/dev/null 2>&1 \
        || meldung "ACHTUNG: $engine nicht beschafft — Egress-Regel pruefen"
    fi
  done
fi

# ---- 2. Python-Pakete -----------------------------------------------------
#
# `--break-system-packages`, weil es kein venv gibt und die Werkzeuge mit dem
# System-Python laufen. WICHTIG: `python3` ist hier 3.11 (deadsnakes),
# waehrend apt seine Pakete nach 3.12 legt — ein `apt-get install
# python3-jsonschema` landete also im falschen Verzeichnis. Deshalb pip.
python3 -c 'import jsonschema' >/dev/null 2>&1 \
  || { meldung "nachinstallieren: jsonschema (pip)"
       python3 -m pip install -q --break-system-packages jsonschema >/dev/null 2>&1 \
         || meldung "ACHTUNG: jsonschema fehlt weiter"; }

# `cryptography` liegt als apt-Paket vor und ist fuer 3.12 gebaut (abi3). Das
# TRAEGT normalerweise, aber am 13.09.2026 ist der Import einmal mit
# pyo3_runtime.PanicException abgebrochen. Deshalb wird er hier GEPRUEFT und
# nur im Fehlerfall ersetzt — ein blindes pip-Install ueber ein
# funktionierendes Debian-Paket macht mehr kaputt, als es heilt.
python3 -c 'from cryptography.hazmat.primitives.ciphers.aead import AESGCM' >/dev/null 2>&1 \
  || { meldung "cryptography traegt nicht — passende Fassung holen"
       python3 -m pip install -q --break-system-packages --ignore-installed \
         'cryptography>=42' >/dev/null 2>&1 \
         || meldung "ACHTUNG: cryptography traegt weiter nicht"; }

# ---- 3. Nachweis ----------------------------------------------------------
#
# Eine gruene Zahl ist erst dann ein Beleg, wenn sie das Gemessene benennt
# (CLAUDE.md 6). Deshalb steht hier je Stueck, was gefunden wurde.
fehler=0
pruefe() {
  if eval "$2" >/dev/null 2>&1; then
    meldung "ok   $1"
  else
    meldung "FEHLT $1"
    fehler=$((fehler + 1))
  fi
}
pruefe "mariadbd"      "command -v mariadbd"
pruefe "convert"       "command -v convert"
pruefe "compare"       "command -v compare"
pruefe "rsvg-convert"  "command -v rsvg-convert"
pruefe "php"           "command -v php"
pruefe "socat"         "command -v socat"
pruefe "python-cryptography" \
       "python3 -c 'from cryptography.hazmat.primitives.ciphers.aead import AESGCM'"
pruefe "python-jsonschema" "python3 -c 'import jsonschema'"
pruefe "python-requests"   "python3 -c 'import requests'"

# Die drei Engines EINZELN, nicht als eine Zahl: „3 Browser da" sagt nicht,
# welcher fehlt — und es fehlt immer nur einer.
for engine in chromium firefox webkit; do
  pruefe "$engine" "PLAYWRIGHT_BROWSERS_PATH=${PLAYWRIGHT_BROWSERS_PATH:-/opt/pw-browsers} \
    node -e \"import('/opt/node22/lib/node_modules/playwright/index.mjs')
      .then(p => p.$engine.launch()).then(b => b.close())\""
done

if [ "$fehler" -gt 0 ]; then
  meldung "$fehler Stueck fehlen — der Pruefstand ist unvollstaendig."
  meldung "Das ist ein Befund mit Zahl, kein stiller Ausfall: im Pruefdokument nennen."
else
  meldung "Pruefstand vollstaendig beschafft. Hochfahren:"
  meldung "sh tools/referenzdatensatz/einspielen/lokal_starten.sh"
  meldung "Drei Engines: PLAYWRIGHT_BROWSERS_PATH=${PLAYWRIGHT_BROWSERS_PATH:-/opt/pw-browsers}"
fi

# Der Hook schlaegt NICHT fehl, wenn etwas fehlt. Eine Sitzung, die sich
# wegen eines fehlenden Bildwerkzeugs nicht starten laesst, ist schlimmer als
# eine, die es weiss und sagt.
exit 0
