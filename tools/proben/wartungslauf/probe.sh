#!/usr/bin/env bash
# Wartungslaufprobe: Serverteil, dann der Weg im Browser.
# Anlass: Nr. 43 — Packen und Ausdünnen wandern aus den Jobs in den Browser.
set -u
WURZEL="$(cd "$(dirname "$0")/../../.." && pwd)"
rc=0
php "$WURZEL/tools/proben/wartungslauf/probe.php" "$@" || rc=1
echo
node "$WURZEL/tools/proben/wartungslauf/probe.mjs" || rc=1
exit $rc
