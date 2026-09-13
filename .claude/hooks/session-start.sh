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

if [ "$fehler" -gt 0 ]; then
  meldung "$fehler Stueck fehlen — der Pruefstand ist unvollstaendig."
  meldung "Das ist ein Befund mit Zahl, kein stiller Ausfall: im Pruefdokument nennen."
else
  meldung "Pruefstand vollstaendig beschafft. Hochfahren:"
  meldung "sh tools/referenzdatensatz/einspielen/lokal_starten.sh"
fi

# Der Hook schlaegt NICHT fehl, wenn etwas fehlt. Eine Sitzung, die sich
# wegen eines fehlenden Bildwerkzeugs nicht starten laesst, ist schlimmer als
# eine, die es weiss und sagt.
exit 0
