#!/usr/bin/env bash
# Stilvergleich gegen einen Vergleichsstand — der eine Aufruf fuer den
# Pruefstand. Drei Schritte hintereinander, damit die Zuordnung in
# `tools/pruefstand/pruefablauf.json` EINE Zeile bleibt.
#
# Aufruf:  bash tools/stilvergleich/gegen.sh [<ref>]
#          <ref> ist der Vergleichsstand (Vorgabe: origin/main)
#
# ANLASS (21.09.2026, F-PK-20): In `pruefablauf.json` stand
# `python3 tools/stilvergleich/proben.py` — ohne Argumente. `proben.py` baut
# aber nur die vier Proben und gibt ohne Argumente seine Anleitung aus; der
# Vergleich ist `stilvergleich.js`, und der braucht den ALTEN Stand. Jeder
# Lauf, der `style.css` beruehrte, war damit rot, ohne etwas gemessen zu
# haben — eine rote Zahl ohne Gegenstand ist so schaedlich wie eine gruene.
#
# UND: `stilvergleich.js` ist CJS und macht `require('playwright')`. Es geht
# damit an `tools/motor.mjs` vorbei, das genau diese Stelle loest. Playwright
# liegt in diesem Abbild unter /opt/node22/lib/node_modules, nicht im
# Projekt — deshalb die Zeile NODE_PATH unten. Das ist ein Pflaster;
# `stilvergleich.js` auf den Motor zu heben, gehoert nach PK-04.
set -euo pipefail

REF="${1:-origin/main}"
WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
NEU="$WURZEL/server/assets/style.css"
ARBEIT="$(mktemp -d)"
trap 'rm -rf "$ARBEIT"' EXIT

ALT="$ARBEIT/alt.css"
git -C "$WURZEL" show "$REF:server/assets/style.css" > "$ALT" \
    || { echo "Vergleichsstand $REF nicht lesbar." >&2; exit 1; }

python3 "$WURZEL/tools/stilvergleich/proben.py" "$ALT" "$NEU" "$ARBEIT/proben" >/dev/null

echo "Vergleichsstand: $REF"
NODE_PATH="${NODE_PATH:-/opt/node22/lib/node_modules}" \
    node "$WURZEL/tools/stilvergleich/stilvergleich.js" "$ARBEIT/proben" "$ALT" "$NEU"
