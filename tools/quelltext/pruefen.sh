#!/usr/bin/env bash
# Quelltextprüfungen — ein Läufer für die dreizehn Prüfungen, die nur Quelltext
# lesen und im Tor laufen (E-PK-24, E-BR-08). `handbuch` braucht dazu
# `cmark-gfm` (Ausbaustufe web); fehlt es, endet sie mit rc 2, nicht grün.
#
# Aufruf:  bash tools/quelltext/pruefen.sh <name> [zusatz…]
#          bash tools/quelltext/pruefen.sh alle
#          bash tools/quelltext/pruefen.sh --selbstprobe
#          bash tools/quelltext/pruefen.sh --liste   # je Name der Befehl, dazu SELBST
#
# Namen:   installweiche sitzungshaertung csp jobregister migrationsregister
#          behandler linkprobe vollstaendigkeit textprobe bestand pysyntax
#          handbuch anker
#
# Vor PK-04 waren das acht Ordner mit acht Anleitungen und acht
# Aufrufkonventionen; die Messungen darunter sind unverändert (Abnahme von
# PK-04/1a: acht Ausgaben bytegleich vor und nach dem Umzug).
set -uo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$WURZEL" || exit 1

NAMEN=(installweiche sitzungshaertung csp jobregister migrationsregister
       behandler linkprobe vollstaendigkeit textprobe bestand pysyntax handbuch anker)
# Die elf mit eingebauter Selbstprobe (`behandler` seit P5c/AP3, `anker` seit
# P5c/AP9). `linkprobe`
# und `vollstaendigkeit` haben keine und hatten vor dem Umzug auch keine — das
# ist kein Rückschritt, sondern ein Rest, den E-PK-24 mit dem gemeinsamen
# Rahmen erst noch einlöst. `textprobe` hatte eine, hinter `--probe`, und sie
# lief bis BR-05 nirgends — `bestand` (Regel `selbst`) hält die Liste seither
# gegen den Code.
SELBST=(installweiche sitzungshaertung csp jobregister migrationsregister behandler
        textprobe bestand pysyntax handbuch anker)

starter() {   # starter <name> — womit die Datei gefahren wird
    case "$1" in
        linkprobe|vollstaendigkeit|textprobe|bestand|pysyntax|handbuch)
                                              echo "python3 tools/quelltext/$1.py" ;;
        *)                                    echo "php tools/quelltext/$1.php" ;;
    esac
}

# DIE VOLLSTAENDIGKEIT MISST SEIT PK-04/5e GEGEN NULL (E-PK-16). Es gibt
# keine Schwelle mehr: `pruefablauf.json` ruft sie ohne `--hoechstens` auf,
# und das Werkzeug meldet jeden Befund. Diese Funktion reicht eine Schwelle
# aus der Ablaufdatei nach, FALLS dort wieder eine steht -- sie tut heute
# nichts, und das ist der Zustand, den sie halten soll.
#
# WAS SIE BIS DAHIN WAR: die eine Stelle fuer eine Zahl, die vorher an zwei
# stand. PK-04/1c senkte den Bestand von 398 auf 18 und zog die Ablaufdatei
# nach; die Kette blieb auf 398 und war seither rot, ohne dass es auffiel
# (F-PK-23). Bis zum 22.09.2026 griff die Vorgabe ausserdem nur bei `alle`,
# und die Kette ruft EINZELN auf.
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
    # Gibt die Aufruferin selbst eine Schwelle, hat sie Vorrang — sonst
    # kommt sie aus der Ablaufdatei. Wortzerlegung ist hier gewollt:
    # `zusatz` gibt entweder nichts oder zwei Wörter, und beide sollen als
    # getrennte Argumente ankommen.
    local vorgabe=''
    case " $* " in *' --hoechstens '*) ;; *) vorgabe="$(zusatz "$n")" ;; esac
    printf '\033[1m==\033[0m %s\n' "$n" >&2
    # shellcheck disable=SC2086
    $ruf $vorgabe "$@"
}

fall="${1:-}"
[ -z "$fall" ] && { echo "Name fehlt. Namen: ${NAMEN[*]} · alle · --selbstprobe" >&2; exit 2; }

case "$fall" in
  --liste)
    # Maschinenlesbar, für den Bestandsriegel (BR-05): So sieht BASH die
    # Listen — mit Kommentaren, `+=` und Anführungszeichen, wie sie gelten.
    # Bis dahin las der Riegel die Listen mit eigenen Mustern nach, und jede
    # Gegenprüfrunde fand eine Schreibweise, die er anders las als bash.
    for n in "${NAMEN[@]}"; do printf 'NAME\t%s\t%s\n' "$n" "$(starter "$n")"; done
    for n in "${SELBST[@]}"; do printf 'SELBST\t%s\n' "$n"; done
    ;;
  --selbstprobe)
    fehl=0
    for n in "${SELBST[@]}"; do
        printf '\033[1m==\033[0m Selbstprobe %s\n' "$n" >&2
        # shellcheck disable=SC2046 — der Starter ist zwei Wörter
        $(starter "$n") --selbstprobe || fehl=$((fehl+1))
    done
    printf '\033[1m==\033[0m %s von %s Selbstproben grün\n' \
           "$(( ${#SELBST[@]} - fehl ))" "${#SELBST[@]}" >&2
    [ "$fehl" -eq 0 ]
    ;;
  alle)
    fehl=0
    for n in "${NAMEN[@]}"; do
        einzeln "$n" || fehl=$((fehl+1))
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
