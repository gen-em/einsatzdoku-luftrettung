#!/usr/bin/env bash
# Quelltextprüfungen — ein Läufer für die acht Prüfungen, die nur Quelltext
# lesen, nichts brauchen und im Tor laufen (E-PK-24).
#
# Aufruf:  bash tools/quelltext/pruefen.sh <name> [zusatz…]
#          bash tools/quelltext/pruefen.sh alle
#          bash tools/quelltext/pruefen.sh --selbstprobe
#
# Namen:   installweiche sitzungshaertung csp jobregister migrationsregister
#          linkprobe vollstaendigkeit textprobe
#
# Vor PK-04 waren das acht Ordner mit acht Anleitungen und acht
# Aufrufkonventionen; die Messungen darunter sind unverändert (Abnahme von
# PK-04/1a: acht Ausgaben bytegleich vor und nach dem Umzug).
set -uo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$WURZEL" || exit 1

NAMEN=(installweiche sitzungshaertung csp jobregister migrationsregister
       linkprobe vollstaendigkeit textprobe)
# Die fünf mit eingebauter Selbstprobe. Die zwei Python-Prüfungen haben keine
# und hatten vor dem Umzug auch keine — das ist kein Rückschritt, sondern ein
# Rest, den E-PK-24 mit dem gemeinsamen Rahmen erst noch einlöst.
SELBST=(installweiche sitzungshaertung csp jobregister migrationsregister)

starter() {   # starter <name> — womit die Datei gefahren wird
    case "$1" in
        linkprobe|vollstaendigkeit|textprobe) echo "python3 tools/quelltext/$1.py" ;;
        *)                                    echo "php tools/quelltext/$1.php" ;;
    esac
}

# DIE SCHWELLE STEHT IN pruefablauf.json UND NIRGENDS SONST (CLAUDE.md 6).
# `alle` liest sie von dort, statt sie ein zweites Mal zu führen — eine Zahl
# an zwei Stellen altert an einer davon unbemerkt. **PK-04/1b hat sie von
# 398 auf 18 gebracht, nicht auf null** — die verbliebenen 18 sind Klassen im
# Markup ohne Regel (Backlog Nr. 269) und lassen sich nur in `server/`
# auflösen. Teilstück 5 nimmt sie dann ganz weg, und diese Funktion fällt mit.
zusatz() {
    [ "$1" = vollstaendigkeit ] || return 0
    python3 - <<'PY'
import json, re, sys
a = json.load(open('tools/pruefstand/pruefablauf.json'))
ruf = a['proben']['vollstaendigkeit']['aufruf']
m = re.search(r'--hoechstens\s+(\d+)', ruf)
sys.stdout.write(f'--hoechstens {m.group(1)}' if m else '')
PY
}

einzeln() {   # einzeln <name> [zusatz…]
    local n="$1"; shift
    local ruf; ruf="$(starter "$n")"
    printf '\033[1m==\033[0m %s\n' "$n" >&2
    $ruf "$@"
}

fall="${1:-}"
[ -z "$fall" ] && { echo "Name fehlt. Namen: ${NAMEN[*]} · alle · --selbstprobe" >&2; exit 2; }

case "$fall" in
  --selbstprobe)
    fehl=0
    for n in "${SELBST[@]}"; do
        printf '\033[1m==\033[0m Selbstprobe %s\n' "$n" >&2
        php "tools/quelltext/$n.php" --selbstprobe || fehl=$((fehl+1))
    done
    printf '\033[1m==\033[0m %s von %s Selbstproben grün\n' \
           "$(( ${#SELBST[@]} - fehl ))" "${#SELBST[@]}" >&2
    [ "$fehl" -eq 0 ]
    ;;
  alle)
    fehl=0
    for n in "${NAMEN[@]}"; do
        # Wortzerlegung ist hier gewollt: `zusatz` gibt entweder nichts oder
        # zwei Wörter zurück, und beide sollen als getrennte Argumente ankommen.
        # shellcheck disable=SC2046
        einzeln "$n" $(zusatz "$n") || fehl=$((fehl+1))
    done
    printf '\033[1m==\033[0m %s von %s Prüfungen grün\n' \
           "$(( ${#NAMEN[@]} - fehl ))" "${#NAMEN[@]}" >&2
    [ "$fehl" -eq 0 ]
    ;;
  *)
    for n in "${NAMEN[@]}"; do
        [ "$n" = "$fall" ] && { shift; einzeln "$fall" "$@"; exit $?; }
    done
    echo "Unbekannt: $fall. Namen: ${NAMEN[*]} · alle · --selbstprobe" >&2
    exit 2
    ;;
esac
