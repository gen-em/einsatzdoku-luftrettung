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
    befehl=${befehl//\{stufe\}/$STUFE}   # der Bilderlauf misst je Stufe anders (PK-05)
    if [ -z "$befehl" ]; then
        zeile "?     $name — steht nicht unter \"proben\" in pruefablauf.json"; fehl=$((fehl+1)); continue
    fi
    # KEIN STILLES ÜBERSPRINGEN (E-KH-12). Was nicht laufen kann, wird
    # GEZÄHLT und benannt — und steht seit E-PK-46 als „nicht-gemessen" im
    # Bericht, wo das Tor es als rot liest. Bis dahin fehlte es dort ganz.
    ohne() { zeile "--    $name — $1"; nicht=$((nicht+1)); ZAHL[$name]=nicht-gemessen; }
    case "$braucht" in
      # Die Plattform, gegen die gebaut wird — aus `compileSdk` gelesen, nicht
      # fest geschrieben (Nr. 335): Bis R4-02 stand hier `android-36`, und die
      # Zeile war grün, wenn 36 lag und die gebrauchte 37.0 fehlte.
      android) sdk=$(grep -oE '^\s*compileSdk\s*=\s*[0-9]+' "$WURZEL/android/handy/build.gradle.kts" | grep -oE '[0-9]+$')
               [ -n "$sdk" ] || { ohne "compileSdk in android/handy/build.gradle.kts nicht lesbar"; continue; }
               ls -d "${ANDROID_HOME:-/opt/android-sdk}/platforms/android-$sdk"* >/dev/null 2>&1 || {
                 ohne "Ausbaustufe android fehlt (Plattform android-$sdk aus compileSdk)"; continue; } ;;
      uhr)     [ -n "${CIQ_GERAETE_URL:-}" ] || { ohne "CIQ_GERAETE_URL fehlt"; continue; } ;;
      plattform) docker image inspect mysql:8.4.0 >/dev/null 2>&1 || {
                 ohne "Modul plattform steht nicht"; continue; } ;;
    esac
    # Ein Umgebungswert, den der Aufruf nennt und der fehlt, ist eine fehlende
    # Voraussetzung, kein Absturz: `set -u` machte daraus „unbound variable"
    # und eine rote Probe (spaltenregister-wegprobe, 23.09.2026).
    # Nur was OHNE Vorgabe steht: `${ANDROID_HOME:-/opt/android-sdk}` bringt
    # seine mit, und bis R4-02 galt der Android-Bau deshalb als „nicht
    # gemessen", sobald ANDROID_HOME nicht exportiert war (F-R4-22). Der
    # Name wird besitzergreifend gelesen (`*+`), sonst träfe `ANDROID_HOM`.
    leer=""
    for v in $(printf '%s' "$befehl" | grep -oP '\$(?:\{[A-Z_][A-Z0-9_]*+(?!:?[-=])|[A-Z_][A-Z0-9_]*+)' | tr -d '${' | sort -u); do
        [ -n "${!v:-}" ] || leer="$leer $v"
    done
    [ -z "$leer" ] || { ohne "Umgebungswert fehlt:$leer"; continue; }
    if [ "$name" = syntax-php ]; then
        n=0; s=0
        # Gezählt wird, was `add -A` in den Baum legt (bericht.py baum_hash): auch
        # neue, noch nicht vorgemerkte Dateien, keine gelöschten (F-PK-37).
        while IFS= read -r d; do
            [ -f "$WURZEL/$d" ] || continue
            n=$((n+1)); php -l "$WURZEL/$d" >/dev/null 2>&1 || s=$((s+1))
        done < <(git -C "$WURZEL" ls-files -co --exclude-standard 'server/*.php' 'server/**/*.php')
        ZAHL[$name]="php:$n/$s"; [ "$s" -eq 0 ] && zeile "ok    $name  $n/$s" \
            || { zeile "ROT   $name  $n/$s"; fehl=$((fehl+1)); }
        continue
    fi
    # Die Zeit je Probe steht in der Zeile (RP-01): Die Nebenstufe brauchte
    # 1 266 s, und niemand konnte sagen, wofür.
    t0=$SECONDS
    if (cd "$WURZEL" && eval "$befehl" >/tmp/pruefstand-$name.log 2>&1); then
        zeile "ok    $name  $((SECONDS - t0)) s"; ZAHL[$name]=0
    else
        zeile "ROT   $name  $((SECONDS - t0)) s  (siehe /tmp/pruefstand-$name.log)"; ZAHL[$name]=1; fehl=$((fehl+1))
    fi
done

# ---- 5. Bericht -----------------------------------------------------------
melde "Bericht"
# Die Flächen leitet bericht.py aus Berührung UND Bau ab — „gebaut" nur nach
# einem grünen Bau (F-PK-34, E-PK-44).
args=(schreiben --stufe "$STUFE" --basis "$BASIS"
      --konfiguration "$([ "$STUFE" = haupt ] && echo alles || echo web)")
for k in "${!ZAHL[@]}"; do args+=(--zahl "$k=${ZAHL[$k]}"); done
# Ohne Bericht ist der Lauf rot, wie grün die Proben auch waren (BR-04,
# Nr. 314): Bis dahin endete er hier mit rc 0 und „0 rot", auch wenn
# bericht.py abgestürzt war — ein Lauf ohne Beleg, der sich als Beleg meldete.
rcb=0; python3 "$HIER/bericht.py" "${args[@]}" || rcb=$?
if [ "$rcb" -ne 0 ]; then
    melde "KEIN BERICHT — bericht.py endete mit rc $rcb. Der Lauf ist rot."
    exit 1
fi

melde "$fehl rot, $nicht nicht gemessen, $(( ${#PROBEN[@]} - fehl - nicht )) grün · $SECONDS s"
[ "$nicht" -gt 0 ] && zeile "Was nicht gemessen werden konnte, gehört in das Prüfdokument — an den Anfang."
# Nicht gemessen ist nicht grün (E-KH-12, E-PK-46): Der Bericht trägt es, und
# das Tor liest es als rot — dann soll es der Lauf auch sein.
exit $([ "$fehl" -gt 0 ] || [ "$nicht" -gt 0 ] && echo 1 || echo 0)
