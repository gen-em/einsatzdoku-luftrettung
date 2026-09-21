#!/bin/bash
# Containerstart: die Arbeitsumgebung beschaffen.
#
# Er tut das NICHT SELBST, sondern ruft `tools/sandbox/aufbauen.sh web`.
# Bis PK-02 beschaffte er neben diesem Skript ein zweites (`containeraufbau/`)
# dasselbe mit einer anderen Paketliste — welche Arbeitsmittel eine Sitzung
# vorfand, hing davon ab, welches von beiden zuletzt jemand gepflegt hatte.
# Gemessen am 21.09.2026: Nach diesem Hook startete WebKit NICHT; die sechs
# Bibliotheken, die er zog, waren nicht die vier, die Playwright verlangt.
#
# ZWEI DINGE BLEIBEN ABSICHT: Er startet nichts — das macht
# `tools/sandbox/hochfahren.sh` —, und er schlägt nicht fehl. Eine Sitzung,
# die sich wegen eines fehlenden Bildwerkzeugs nicht öffnen lässt, ist
# schlimmer als eine, die den Mangel mit Zahl meldet.
set -uo pipefail

# Nur im Wegwerf-Container. Auf einer Entwicklungsmaschine hat die Person
# ihre Werkzeuge selbst installiert, und ein Hook, der dort apt anwirft,
# wäre eine Zumutung.
if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

WURZEL="${CLAUDE_PROJECT_DIR:-$(cd "$(dirname "$0")/../.." && pwd)}"

if sh "$WURZEL/tools/sandbox/aufbauen.sh" web; then
  printf '  Hochfahren: sh tools/sandbox/hochfahren.sh\n' >&2
else
  printf '  Die Arbeitsumgebung ist unvollständig — die Zahlen stehen oben.\n' >&2
  printf '  Das ist ein Befund mit Zahl, kein stiller Ausfall: im Prüfdokument nennen.\n' >&2
fi

exit 0
