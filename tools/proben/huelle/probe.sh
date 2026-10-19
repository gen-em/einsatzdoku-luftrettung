#!/usr/bin/env bash
# Hüllenprobe — die Referenz gegen ihre Vektoren, dann Server und Browser dagegen.
# Anlass: Nr. 43 — drei Umsetzungen der Gerätehülle müssen byte-gleich sein.
# Anleitung: LIESMICH.md daneben. Rückgabe 0 grün · 1 Befund · 2 nicht gelaufen.
set -uo pipefail
HIER="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
python3 "$HIER/referenz.py" --selbstprobe || exit $?
python3 "$HIER/referenz.py" pruefen || exit $?
php "$HIER/probe_server.php" || exit $?
node "$HIER/probe.mjs" "$@"
