#!/usr/bin/env bash
# Erzeuger — was Dateien HERSTELLT, nicht was prüft (E-PK-24).
#
# Aufruf:  bash tools/erzeugen/erzeugen.sh <name> [zusatz…]
#          bash tools/erzeugen/erzeugen.sh --liste
#
# KEIN `alle`, und das ist Absicht: Diese Befehle schreiben in das
# Repositorium — Tabellen in `docs/Design.md`, Favicons, Gerätebilder, eine
# Domänenliste, einen Kontenbestand. Wer sie gesammelt laufen ließe, hätte
# einen Commit mit sechs unzusammenhängenden Änderungen und keine Ahnung,
# welche davon er wollte. Jeder Erzeuger wird einzeln und auf Zuruf gefahren.
set -uo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$WURZEL" || exit 1

declare -A RUF=(
  [design]="python3 tools/erzeugen/design.py"
  [logos]="node tools/erzeugen/logos.mjs"
  [uhr-bilder]="bash tools/erzeugen/uhr-bilder.sh"
  [geraetemodelle]="python3 tools/erzeugen/geraetemodelle.py"
  [nachaufloesen]="php tools/erzeugen/geraetemodelle-nachaufloesen.php"
  [wegwerfdomains]="python3 tools/erzeugen/wegwerfdomains.py"
  [pruefkonten]="php tools/erzeugen/pruefkonten.php"
)
declare -A WAS=(
  [design]="die Tabellen in docs/Design.md (Token, Schwellen, Symbole, Bausteine)"
  [logos]="die Favicons AUS den Logodateien"
  [uhr-bilder]="die vorgerasterten Bildmarken der Uhr-App"
  [geraetemodelle]="server/geraetemodelle.php aus der Garmin-Geräteliste"
  [nachaufloesen]="Teilenummern nachschlagen, die erzeugen.py offen ließ"
  [wegwerfdomains]="die Liste der Wegwerf-Mailanbieter (schreibt erst auf Zuruf)"
  [pruefkonten]="einen Bestand von 300+ Konten in der örtlichen Anlage"
)

fall="${1:-}"
[ -z "$fall" ] && { echo "Name fehlt: bash tools/erzeugen/erzeugen.sh --liste" >&2; exit 2; }

if [ "$fall" = "--liste" ]; then
    for n in $(printf '%s\n' "${!RUF[@]}" | sort); do
        printf '  %-16s %s\n' "$n" "${WAS[$n]}"
        printf '  %-16s   %s\n' "" "${RUF[$n]}"
    done
    exit 0
fi
if [ -z "${RUF[$fall]:-}" ]; then
    echo "Unbekannt: $fall. Namen: bash tools/erzeugen/erzeugen.sh --liste" >&2
    exit 2
fi
shift
exec ${RUF[$fall]} "$@"
