#!/bin/bash
# Station B — ein Befehl vor dem Pull Request.
#
# Aufruf:  bash tools/pruefstand/pruefen.sh [--stufe klein|neben|haupt]
#                                          [--basis origin/main]
#                                          [--gegen staging]
#                                          [--datei PFAD]   (mehrfach)
#                                          [--ohne-hochfahren] [--trocken]
#
# Er ermittelt die Stufe und SAGT sie, fährt die örtliche Anlage hoch, läuft
# die Proben, die zur Berührung gehören (tools/pruefstand/pruefablauf.json),
# und erzeugt am Ende den Prüfbericht für die Commit-Nachricht.
#
# DER BERICHT WIRD ERZEUGT, NICHT GESCHRIEBEN. Das ist der ganze Unterschied
# zwischen einem Nachweis und einer Behauptung (docs/Pruefablauf.md 5.2).
#
# Anlass: O9c — gemessen wurde vor der letzten Änderung.
set -uo pipefail

WURZEL=${WURZEL:-$(cd "$(dirname "$0")/../.." && pwd)}
HIER="$WURZEL/tools/pruefstand"
STUFE=; BASIS=${BASIS:-origin/main}; GEGEN=oertlich; HOCHFAHREN=1; TROCKEN=0
DATEIEN=()

melde() { printf '\033[1m==\033[0m %s\n' "$*" >&2; }
zeile() { printf '   %s\n' "$*" >&2; }

while [ $# -gt 0 ]; do
    case "$1" in
        --stufe) STUFE=${2:-}; shift 2 ;;
        --basis) BASIS=${2:-}; shift 2 ;;
        --gegen) GEGEN=${2:-}; shift 2 ;;
        --datei) DATEIEN+=(--datei "${2:-}"); shift 2 ;;
        --ohne-hochfahren) HOCHFAHREN=0; shift ;;
        --trocken) TROCKEN=1; HOCHFAHREN=0; shift ;;
        *) echo "Unbekannt: $1" >&2; exit 2 ;;
    esac
done

# ---- 1. Stufe ermitteln und SAGEN ----------------------------------------
melde "Stufe"
if [ -n "$STUFE" ]; then
    zeile "$STUFE  (mit --stufe gesetzt; das steht so im Bericht und fällt im Tor auf)"
else
    erm=$(python3 "$HIER/auswahl.py" --stufe-ermitteln --basis "$BASIS") || exit 2
    STUFE=$(printf '%s' "$erm" | cut -f1)
    zeile "$STUFE  ($(printf '%s' "$erm" | cut -f2))"
fi

# ---- 2. Was ist berührt, was läuft daraus --------------------------------
melde "Berührung"
python3 "$HIER/auswahl.py" --stufe "$STUFE" --basis "$BASIS" "${DATEIEN[@]}" >&2 || exit 2
mapfile -t PROBEN < <(python3 "$HIER/auswahl.py" --stufe "$STUFE" --basis "$BASIS" "${DATEIEN[@]}" --nur-proben)

if [ "$TROCKEN" = 1 ]; then
    melde "Trockenlauf — es wird nichts gefahren."
    exit 0
fi

# ---- 3. Anlage ------------------------------------------------------------
if [ "$HOCHFAHREN" = 1 ] && [ "$GEGEN" = oertlich ]; then
    melde "Örtliche Anlage"
    bash "$WURZEL/tools/sandbox/hochfahren.sh" >&2 || { zeile "Anlage kam nicht hoch."; exit 1; }
fi
[ "$GEGEN" = staging ] && melde "Gegen die Prüfanlage — NUR LESEND (E-PK-29)"

# ---- 4. Proben ------------------------------------------------------------
melde "Proben (${#PROBEN[@]})"
declare -A ZAHL; fehl=0; nicht=0
for name in "${PROBEN[@]}"; do
    aufruf=$(python3 -c "
import json,sys
a=json.load(open('$HIER/pruefablauf.json'))
p=a['proben'].get('$name')
print(p['aufruf'] if p else '', p['braucht'] if p else '', sep='\t')")
    befehl=$(printf '%s' "$aufruf" | cut -f1); braucht=$(printf '%s' "$aufruf" | cut -f2)
    if [ -z "$befehl" ]; then
        zeile "?     $name — steht nicht unter \"proben\" in pruefablauf.json"; fehl=$((fehl+1)); continue
    fi
    # KEIN STILLES ÜBERSPRINGEN (E-KH-12). Was nicht laufen kann, wird
    # GEZÄHLT und benannt — nicht weggelassen.
    case "$braucht" in
      android) [ -d "${ANDROID_HOME:-/opt/android-sdk}/platforms/android-36" ] || {
                 zeile "--    $name — Ausbaustufe android fehlt"; nicht=$((nicht+1)); continue; } ;;
      uhr)     [ -n "${CIQ_GERAETE_URL:-}" ] || {
                 zeile "--    $name — CIQ_GERAETE_URL fehlt"; nicht=$((nicht+1)); continue; } ;;
      plattform) docker image inspect mysql:8.4.0 >/dev/null 2>&1 || {
                 zeile "--    $name — Modul plattform steht nicht"; nicht=$((nicht+1)); continue; } ;;
    esac
    if [ "$name" = syntax-php ]; then
        n=0; s=0
        while IFS= read -r d; do n=$((n+1)); php -l "$d" >/dev/null 2>&1 || s=$((s+1)); done \
            < <(git -C "$WURZEL" ls-files 'server/*.php' 'server/**/*.php')
        ZAHL[$name]="php:$n/$s"; [ "$s" -eq 0 ] && zeile "ok    $name  $n/$s" \
            || { zeile "ROT   $name  $n/$s"; fehl=$((fehl+1)); }
        continue
    fi
    if (cd "$WURZEL" && eval "$befehl" >/tmp/pruefstand-$name.log 2>&1); then
        zeile "ok    $name"; ZAHL[$name]=0
    else
        zeile "ROT   $name  (siehe /tmp/pruefstand-$name.log)"; ZAHL[$name]=1; fehl=$((fehl+1))
    fi
done

# ---- 5. Bericht -----------------------------------------------------------
melde "Bericht"
args=(schreiben --stufe "$STUFE" --konfiguration "$([ "$STUFE" = haupt ] && echo alles || echo web)")
for k in "${!ZAHL[@]}"; do args+=(--zahl "$k=${ZAHL[$k]}"); done
for f in handy:android uhr:watch; do
    name=${f%%:*}; ordner=${f##*:}
    if git -C "$WURZEL" diff --name-only "$BASIS...HEAD" -- "$ordner" | grep -q .; then
        args+=(--flaeche "$name=gebaut")
    else
        args+=(--flaeche "$name=nicht-beruehrt")
    fi
done
python3 "$HIER/bericht.py" "${args[@]}"

melde "$fehl rot, $nicht nicht gemessen, $(( ${#PROBEN[@]} - fehl - nicht )) grün"
[ "$nicht" -gt 0 ] && zeile "Was nicht gemessen werden konnte, gehört in das Prüfdokument — an den Anfang."
exit $([ "$fehl" -gt 0 ] && echo 1 || echo 0)
