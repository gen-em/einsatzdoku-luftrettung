#!/usr/bin/env bash
# Proben gegen die örtliche Installation — ein Läufer für die zwanzig
# Prüfungen, die eine Bibliothek oder einen Endpunkt messen (E-PK-24).
#
# Aufruf:  bash tools/proben/proben.sh <name> [zusatz…]
#          bash tools/proben/proben.sh alle
#          bash tools/proben/proben.sh --liste
#
# Vor PK-04 waren das zwanzig Ordner mit zwanzig Anleitungen und zwanzig
# Aufrufkonventionen — `probe.php`, `pruefe.mjs`, `probe.mjs`, `pruefen.php`,
# je nachdem, wer sie geschrieben hatte. Die Messungen darunter sind
# unverändert; die Abnahme steht im Prüfdokument.
set -uo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$WURZEL" || exit 1

# Name -> Aufruf. DIE EINE STELLE, an der steht, womit eine Probe gefahren
# wird; `tools/pruefstand/pruefablauf.json` zeigt nur noch auf den Namen.
declare -A RUF=(
  [ingest]="php tools/proben/ingest/probe.php"
  [spur]="php tools/proben/spur/probe.php"
  [jobs]="php tools/proben/jobs/probe.php"
  [kopplung]="php tools/proben/kopplung/probe.php"
  [wartung]="php tools/proben/wartung/probe.php"
  [raten]="php tools/proben/raten/probe.php"
  [mail]="php tools/proben/mail/probe.php"
  [versand]="php tools/proben/versand/probe.php"
  [komplett]="php tools/proben/komplett/probe.php"
  [wiederherstellung]="php tools/proben/wiederherstellung/probe.php"
  [gpx]="php tools/proben/gpx/probe.php"
  [geraete]="php tools/proben/geraete/probe.php"
  [verbindung]="php tools/proben/verbindung/probe.php"
  [anteil]="php tools/proben/anteil/probe.php"
  [rechtstexte]="php tools/proben/rechtstexte/pruefen.php"
  [freigabe]="node tools/proben/freigabe/probe.mjs"
  [container]="node tools/proben/container/probe.mjs"
  [frist]="node tools/proben/frist/pruefe.mjs"
  [abmelden]="node tools/proben/abmelden/pruefe.mjs"
  [csp-browser]="node tools/proben/csp-browser/browserprobe.mjs"
)

# Proben, die `alle` NICHT fährt, mit dem Grund daneben. Kein stilles
# Auslassen (Grundsatz 7): Wer `alle` fährt, liest hier, was fehlt.
declare -A NICHT_IN_ALLE=(
  [versand]="braucht den Wurzelpfad der Gegenstellen als Argument"
)

melde() { printf '\033[1m==\033[0m %s\n' "$*" >&2; }

fall="${1:-}"
[ -z "$fall" ] && { echo "Name fehlt. Namen: bash tools/proben/proben.sh --liste" >&2; exit 2; }

case "$fall" in
  --liste)
    for n in $(printf '%s\n' "${!RUF[@]}" | sort); do
        printf '  %-20s %s%s\n' "$n" "${RUF[$n]}" \
               "${NICHT_IN_ALLE[$n]:+   (nicht in »alle«: ${NICHT_IN_ALLE[$n]})}"
    done
    ;;
  alle)
    fehl=0; gefahren=0
    for n in $(printf '%s\n' "${!RUF[@]}" | sort); do
        if [ -n "${NICHT_IN_ALLE[$n]:-}" ]; then
            melde "$n — ausgelassen: ${NICHT_IN_ALLE[$n]}"
            continue
        fi
        melde "$n"
        gefahren=$((gefahren+1))
        ${RUF[$n]} || fehl=$((fehl+1))
    done
    melde "$(( gefahren - fehl )) von $gefahren Proben grün · $(( ${#RUF[@]} - gefahren )) ausgelassen"
    [ "$fehl" -eq 0 ]
    ;;
  *)
    if [ -z "${RUF[$fall]:-}" ]; then
        echo "Unbekannt: $fall. Namen: bash tools/proben/proben.sh --liste" >&2
        exit 2
    fi
    shift
    ${RUF[$fall]} "$@"
    ;;
esac
