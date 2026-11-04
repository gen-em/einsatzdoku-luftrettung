#!/bin/bash
# Station B — ein Befehl vor dem Pull Request.
#
# Aufruf:  bash tools/pruefstand/pruefen.sh [--stufe klein|neben|haupt]
#                                          [--basis origin/main]
#                                          [--gegen staging]
#                                          [--datei PFAD]   (mehrfach)
#                                          [--voll]         (voller Lauf, E-PU-01)
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
STUFE=; BASIS=${BASIS:-origin/main}; GEGEN=oertlich; HOCHFAHREN=1; TROCKEN=0; VOLL=
DATEIEN=()

melde() { printf '\033[1m==\033[0m %s\n' "$*" >&2; }
zeile() { printf '   %s\n' "$*" >&2; }

while [ $# -gt 0 ]; do
    case "$1" in
        --stufe) STUFE=${2:-}; shift 2 ;;
        --basis) BASIS=${2:-}; shift 2 ;;
        --gegen) GEGEN=${2:-}; shift 2 ;;
        --datei) DATEIEN+=(--datei "${2:-}"); shift 2 ;;
        --voll) VOLL=--voll; shift ;;
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

# ---- 1a. Umfang ermitteln und SAGEN (E-PU-01) ----------------------------
# schmal oder voll — voll in haupt, an einer Zehnergrenze der Nebennummer und
# mit --voll. Was davon abhängt (Engines, Breiten, Seiten), steht in den
# Abschnitten darunter; der Bericht trägt `umfang=…`, das Tor liest es gegen.
melde "Umfang"
erm=$(python3 "$HIER/auswahl.py" --umfang-ermitteln --stufe "$STUFE" --basis "$BASIS" $VOLL) || exit 2
UMFANG=$(printf '%s' "$erm" | cut -f1)
zeile "$UMFANG  ($(printf '%s' "$erm" | cut -f2))"

# ---- 2. Was ist berührt, was läuft daraus --------------------------------
melde "Berührung"
python3 "$HIER/auswahl.py" --stufe "$STUFE" --umfang "$UMFANG" --basis "$BASIS" "${DATEIEN[@]}" >&2 || exit 2
mapfile -t PROBEN < <(python3 "$HIER/auswahl.py" --stufe "$STUFE" --umfang "$UMFANG" --basis "$BASIS" "${DATEIEN[@]}" --nur-proben)
# Breiten und Seiten des Bilderlaufs (E-PU-03, -04): ein Satz für --breiten,
# die Seiten für --nur oder „alle". Steht im Bericht, das Tor rechnet nach.
erm=$(python3 "$HIER/auswahl.py" --bilderlauf --stufe "$STUFE" --umfang "$UMFANG" --basis "$BASIS" "${DATEIEN[@]}") || exit 2
BREITEN=$(printf '%s' "$erm" | cut -f1); SEITEN=$(printf '%s' "$erm" | cut -f2)
# DER ERERBTE BERICHT (E-PU-13): In `neben`, im schmalen Lauf, übernimmt der
# Prüfstand das Ergebnis einer Probe vom Bericht in HEAD, wenn sie dort grün
# war und der Unterschied seither keines ihrer Muster trifft. Welche, sagt
# auswahl.py --ererbt; das Tor rechnet nach (bericht.py, Lage 8).
declare -A ERBE
while IFS=$'\t' read -r n b; do [ -n "$n" ] && ERBE[$n]=$b; done \
    < <(python3 "$HIER/auswahl.py" --ererbt --stufe "$STUFE" --umfang "$UMFANG" --basis "$BASIS" "${DATEIEN[@]}" 2> >(sed 's/^/   /' >&2))

if [ "$TROCKEN" = 1 ]; then
    melde "Trockenlauf — es wird nichts gefahren."
    exit 0
fi

# ---- 3. Anlage ------------------------------------------------------------
# DEN SERVER ZURÜCKGEBEN, DEN MAN VORGEFUNDEN HAT (Nr. 490). Läuft dieser
# Prüfstand aus einem anderen Baum als der Server — typisch: aus einem
# `git worktree` —, startet `hochfahren.sh` den Server auf diesen Baum um.
# Am Ende, auch nach einem Abbruch, bekommt der vorige Baum ihn zurück,
# solange es ihn noch gibt. Sonst zeigt die Anlage nach dem Löschen des
# Arbeitsbaums ins Leere, und jede Probe im Hauptbaum scheitert mit 404.
WURZEL_VORHER=; DEMO_ALT=
wurzel_zurueck() {
    [ -n "$WURZEL_VORHER" ] || return 0
    [ "$WURZEL_VORHER" != "$(readlink -f "$WURZEL/server")" ] || return 0
    [ -d "$WURZEL_VORHER" ] || return 0
    local start="$WURZEL_VORHER/../tools/referenzdatensatz/einspielen/lokal_starten.sh"
    [ -f "$start" ] || return 0
    pkill -f "php -S 127.0.0.1:8080" 2>/dev/null || true
    sleep 1
    WURZEL="$WURZEL_VORHER" sh "$start" >/dev/null 2>&1 \
        && zeile "Server wieder auf $WURZEL_VORHER (Nr. 490)" \
        || zeile "Server NICHT auf $WURZEL_VORHER zurückgestellt — dort hochfahren.sh fahren"
    WURZEL_VORHER=
}
zum_schluss() {
    [ -n "$DEMO_ALT" ] && demo_marke setzen "$DEMO_ALT" >/dev/null
    wurzel_zurueck
}
trap zum_schluss EXIT

if [ "$HOCHFAHREN" = 1 ] && [ "$GEGEN" = oertlich ]; then
    melde "Örtliche Anlage"
    WURZEL_VORHER=$(bash "$WURZEL/tools/sandbox/hochfahren.sh" --welche-wurzel)
    bash "$WURZEL/tools/sandbox/hochfahren.sh" >&2 || { zeile "Anlage kam nicht hoch."; exit 1; }
fi
[ "$GEGEN" = staging ] && melde "Gegen die Prüfanlage — NUR LESEND (E-PK-29)"

# ---- 3a. Demo-Marke -------------------------------------------------------
# Der Demo-Reset (30 Minuten nach der ersten Änderung, `demo_lib.php`) spielt den Demo-Bestand neu
# ein, und seine Einsätze bekommen neue Kennungen. Welche Probe er traf,
# entschied ohne Marke die Reihenfolge der Muster — eine Voraussetzung, die
# nirgends stand. Jetzt schiebt der Prüfstand die Marke des letzten
# Resets vor jeder Probe mit `"demo": true` auf „jetzt": dreißig Minuten ohne
# Reset je Probe. Am Ende stellt er sie zurück, auch nach einem Abbruch.
# NUR ÖRTLICH: Gegen Staging schreibt er nichts.
demo_marke() {   # demo_marke lesen | demo_marke setzen <unix-sekunden>
    (cd "$WURZEL/server" && php -r 'require "db.php"; require_once "demo_lib.php";
        if (($argv[1] ?? "") === "setzen") { demo_reset_marke_setzen((int)$argv[2]); }
        echo demo_letzter_reset();' -- "$@" 2>/dev/null)
}
if [ "$GEGEN" = oertlich ]; then
    DEMO_ALT=$(demo_marke lesen) || DEMO_ALT=
fi

# ---- 4. Proben ------------------------------------------------------------
melde "Proben (${#PROBEN[@]})"
declare -A ZAHL; fehl=0; nicht=0; hinw=0; erbt=0; DREIFACH=0; WIEDERHOLT=0; HINWEISE=()
# DIE ERGEBNISART „hinweis" (E-PU-05): gemessen, gemeldet, hält nicht auf.
# Zwei Wege führen dahin: Eine Probe mit "hinweis": true in pruefablauf.json
# endet mit rc != 0 — dann ist das ein Hinweis statt rot —, oder eine Probe
# endet grün und schreibt Zeilen, die mit „HINWEIS: " beginnen. Beides steht
# als `name=hinweis` im Bericht, und das Tor liest es als nicht rot
# (bericht.py, Lage 5). Nichts wird übersprungen (Grundsatz 7).
hinweise_aus() { grep -E '^HINWEIS: ' "$1" 2>/dev/null | sed -E 's/^HINWEIS: //' || true; }
# ergebnis NAME RC SEKUNDEN LOG — schreibt die Zeile des Laufs. rc 0 der
# Funktion heißt „grün oder rot, wie gemessen", rc 1 heißt „Hinweis".
# Liest $nurhinweis der laufenden Probe.
ergebnis() {
    local n=$1 rc=$2 s=$3 log=$4 h
    h=$(hinweise_aus "$log")
    if [ "$rc" = 0 ] && [ -z "$h" ]; then zeile "ok    $n  $s s"; return 0; fi
    if [ "$rc" = 0 ]; then
        zeile "HIN   $n  $s s  (Hinweis — hält nicht auf, siehe $log)"
        printf '%s\n' "$h" | while IFS= read -r z; do zeile "      $n: $z"; done
        return 1
    fi
    if [ -n "$nurhinweis" ]; then
        zeile "HIN   $n  $s s  (rot gemessen, als Hinweis gemeldet: \"hinweis\": true in pruefablauf.json; siehe $log)"
        tail -n 5 "$log" | while IFS= read -r z; do zeile "      $n: $z"; done
        return 1
    fi
    zeile "ROT   $n  $s s  (siehe $log)"; return 0
}
# DIE ENGINES JE BERÜHRUNG (E-PU-02, R89): Chromium allein, alle drei beim
# vollen Lauf und ab `neben`, wenn Stylesheet oder ui.php berührt sind. Die
# Regel steht in auswahl.py (motoren_aus), bericht.py rechnet mit derselben
# nach (Lage 6). Bis PU-03 hieß es: `klein` Chromium, `neben` und `haupt`
# drei (R87) — der Bilderlauf allein brauchte dann rund 70 Minuten.
erm=$(python3 "$HIER/auswahl.py" --motoren --stufe "$STUFE" --umfang "$UMFANG" --basis "$BASIS" "${DATEIEN[@]}") || exit 2
read -r -a MOTOREN <<< "$(printf '%s' "$erm" | cut -f1)"
zeile "Engines der Browserproben: ${MOTOREN[*]}  ($(printf '%s' "$erm" | cut -f2))"
for name in "${PROBEN[@]}"; do
    aufruf=$(python3 -c "
import json,sys
a=json.load(open('$HIER/pruefablauf.json'))
p=a['proben'].get('$name')
print(p['aufruf'] if p else '', p['braucht'] if p else '', 'demo' if p and p.get('demo') is True else '',
      'drei' if p and p.get('motoren') is True else '', 'hinweis' if p and p.get('hinweis') is True else '',
      sep='\t')")
    befehl=$(printf '%s' "$aufruf" | cut -f1); braucht=$(printf '%s' "$aufruf" | cut -f2)
    demo=$(printf '%s' "$aufruf" | cut -f3); drei=$(printf '%s' "$aufruf" | cut -f4)
    nurhinweis=$(printf '%s' "$aufruf" | cut -f5)
    befehl=${befehl//\{stufe\}/$STUFE}
    # Der Bilderlauf: Breitensatz und Seiten (E-PU-03, -04).
    befehl=${befehl//\{breiten\}/$BREITEN}
    if [ "$SEITEN" = alle ] || [ "$SEITEN" = - ]; then befehl=${befehl//\{seiten\}/}
    else befehl=${befehl//\{seiten\}/--nur $SEITEN}; fi
    if [ -z "$befehl" ]; then
        zeile "?     $name — steht nicht unter \"proben\" in pruefablauf.json"; fehl=$((fehl+1)); continue
    fi
    # Geerbt ist nicht übersprungen: Der Bericht nennt den Baum, von dem das
    # Ergebnis stammt, und das Tor liest dessen Bericht nach (Grundsatz 7).
    if [ -n "${ERBE[$name]:-}" ]; then
        zeile "erbt  $name  — grün im Bericht von HEAD, Baum ${ERBE[$name]} (E-PU-13)"
        ZAHL[$name]="ererbt:${ERBE[$name]}"; erbt=$((erbt+1))
        [ -n "$drei" ] && DREIFACH=$((DREIFACH+1))
        continue
    fi
    # KEIN STILLES ÜBERSPRINGEN. Was nicht laufen kann, wird
    # GEZÄHLT und benannt — und steht als „nicht-gemessen" im
    # Bericht, wo das Tor es als rot liest.
    ohne() { zeile "--    $name — $1"; nicht=$((nicht+1)); ZAHL[$name]=nicht-gemessen; }
    case "$braucht" in
      # Die Plattform, gegen die gebaut wird — aus `compileSdk` gelesen, nicht
      # fest geschrieben: Stünde hier `android-36`, wäre die
      # Zeile grün, wenn 36 lag und die gebrauchte 37.0 fehlte.
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
    # seine mit, und ohne diese Regel galt der Android-Bau als „nicht
    # gemessen", sobald ANDROID_HOME nicht exportiert war. Der
    # Name wird besitzergreifend gelesen (`*+`), sonst träfe `ANDROID_HOM`.
    leer=""
    for v in $(printf '%s' "$befehl" | grep -oP '\$(?:\{[A-Z_][A-Z0-9_]*+(?!:?[-=])|[A-Z_][A-Z0-9_]*+)' | tr -d '${' | sort -u); do
        [ -n "${!v:-}" ] || leer="$leer $v"
    done
    [ -z "$leer" ] || { ohne "Umgebungswert fehlt:$leer"; continue; }
    if [ "$name" = syntax-php ]; then
        n=0; s=0
        # Gezählt wird, was `add -A` in den Baum legt (bericht.py baum_hash): auch
        # neue, noch nicht vorgemerkte Dateien, keine gelöschten.
        while IFS= read -r d; do
            [ -f "$WURZEL/$d" ] || continue
            n=$((n+1)); php -l "$WURZEL/$d" >/dev/null 2>&1 || s=$((s+1))
        done < <(git -C "$WURZEL" ls-files -co --exclude-standard 'server/*.php' 'server/**/*.php')
        ZAHL[$name]="php:$n/$s"; [ "$s" -eq 0 ] && zeile "ok    $name  $n/$s" \
            || { zeile "ROT   $name  $n/$s"; fehl=$((fehl+1)); }
        continue
    fi
    # Die Marke steht im Protokoll: Wer einen Reset mitten in einer Probe
    # vermutet, sieht hier, ob sie geschoben war.
    # JE LAUF, NICHT JE PROBE. Eine Probe läuft in
    # `neben` dreimal hintereinander, und stand die Marke vor
    # dieser Schleife, hieß das dreißig Minuten für drei Läufe. Der Bilderlauf
    # braucht in Chromium allein 26 davon; am 20.10.2026 begann der
    # WebKit-Lauf bei Minute 47, der Reset kam dazwischen, und 80 Aufnahmen
    # fielen aus, weil die Kennungen fehlten.
    marke_schieben() {
        [ -n "$demo" ] && [ "$GEGEN" = oertlich ] || return 0
        local jetzt; jetzt=$(date +%s)
        [ "$(demo_marke setzen "$jetzt")" = "$jetzt" ] \
            && zeile "      $name${1:+ [$1]}: Demo-Marke auf $(date -u -d "@$jetzt" +%H:%M:%S) UTC — kein Reset in den nächsten 30 min" \
            || zeile "      $name${1:+ [$1]}: Demo-Marke NICHT gesetzt — ein Demo-Reset kann diese Probe treffen"
    }
    # Die Zeit je Probe steht in der Zeile: Die Nebenstufe brauchte
    # 1 266 s, und niemand konnte sagen, wofür.
    # DREI ENGINES, in `klein` eine. Eine Probe mit
    # "motoren": true läuft einmal je Engine aus MOTOREN, mit MOTOR=… in der Umgebung — `tools/motor.mjs` liest
    # es, und jeder Zwischenschritt (php, python, bash) reicht es weiter. Grün
    # ist sie nur, wenn alle drei grün sind; rot nennt sie, welche nicht.
    # Fehlt eine Engine, startet sie nicht, und die Probe ist rot — nicht
    # übersprungen (Grundsatz 7). Bis zum 19.10.2026 lief jede Probe einmal,
    # in Chromium, auch die, die --motor kannten.
    if [ -n "$drei" ]; then
        rot=(); hin=0
        for mo in "${MOTOREN[@]}"; do
            marke_schieben "$mo"
            t1=$SECONDS
            log="/tmp/pruefstand-$name-$mo.log"
            if (cd "$WURZEL" && export MOTOR=$mo && eval "$befehl" >"$log" 2>&1); then
                ergebnis "$name [$mo]" 0 "$((SECONDS - t1))" "$log" || hin=1
            elif ergebnis "$name [$mo]" 1 "$((SECONDS - t1))" "$log"; then
                rot+=("$mo")
            else
                hin=1
            fi
            # Wiederholte Seitenwechsel in WebKit (motor.mjs) und neu
            # angemeldete Rollen, deren Seite nicht mehr antwortete (Bedienprobe):
            # genannt, nicht verschwiegen — grün bleibt grün, aber man
            # sieht, wie oft.
            w=$(grep -cE '^WebKit-(Wiederholung|Neuanmeldung):' "/tmp/pruefstand-$name-$mo.log" 2>/dev/null || true)
            [ "${w:-0}" -gt 0 ] && zeile "      $name [$mo]: $w Mal wiederholt (WebKit-Stolpern, E-DM-10: Seitenwechsel oder Neuanmeldung)"
            WIEDERHOLT=$((WIEDERHOLT + ${w:-0}))
            # Was motor.mjs in WebKit zurückhält: CSP-Berichte nach
            # Screenshots, Abrufe, die ein Seitenwechsel abbrach. Genannt, nicht
            # verschwiegen — die Zeile steht am Ende des Probenprotokolls.
            grep -hE '^WebKit-(Screenshot|Seitenwechsel): ' "/tmp/pruefstand-$name-$mo.log" 2>/dev/null \
                | sed -E 's/ \(motor\.mjs, Nr\. 393\)$//' | while IFS= read -r z; do zeile "      $name [$mo]: $z"; done
        done
        if [ ${#rot[@]} -gt 0 ]; then ZAHL[$name]="rot:$(IFS=,; echo "${rot[*]}")"; fehl=$((fehl+1))
        elif [ "$hin" = 1 ]; then ZAHL[$name]=hinweis; hinw=$((hinw+1)); HINWEISE+=("$name")
        else ZAHL[$name]=0; fi
        DREIFACH=$((DREIFACH+1))
        continue
    fi
    marke_schieben
    t0=$SECONDS
    log="/tmp/pruefstand-$name.log"
    rc=0; (cd "$WURZEL" && eval "$befehl" >"$log" 2>&1) || rc=1
    if ergebnis "$name" "$rc" "$((SECONDS - t0))" "$log"; then
        if [ "$rc" = 0 ]; then ZAHL[$name]=0; else ZAHL[$name]=1; fehl=$((fehl+1)); fi
    else
        ZAHL[$name]=hinweis; hinw=$((hinw+1)); HINWEISE+=("$name")
    fi
done

# ---- 5. Bericht -----------------------------------------------------------
if [ -n "$DEMO_ALT" ]; then
    d=$DEMO_ALT; DEMO_ALT=      # der Abschluss braucht es nicht noch einmal
    [ "$(demo_marke setzen "$d")" = "$d" ] \
        && zeile "Demo-Marke zurückgestellt ($(date -u -d "@$d" '+%d.%m. %H:%M:%S') UTC)" \
        || zeile "Demo-Marke NICHT zurückgestellt — sie steht auf dem Stand der letzten Probe"
fi
melde "Bericht"
# Die Flächen leitet bericht.py aus Berührung UND Bau ab — „gebaut" nur nach
# einem grünen Bau.
args=(schreiben --stufe "$STUFE" --basis "$BASIS"
      --konfiguration "$([ "$STUFE" = haupt ] && echo alles || echo web)")
for k in "${!ZAHL[@]}"; do args+=(--zahl "$k=${ZAHL[$k]}"); done
args+=(--zahl "umfang=$UMFANG")
# Breiten und Seiten des Bilderlaufs, wenn er lief — das Tor rechnet nach (Lage 6).
if [ -n "${ZAHL[bilderlauf]:-}" ]; then
    if [ "$SEITEN" = alle ]; then n=alle; else n=$(printf '%s' "$SEITEN" | tr ',' '\n' | grep -c .); fi
    args+=(--zahl "breiten=$BREITEN" --zahl "seiten=$n")
fi
# Welche Engines die dreifachen Proben gefahren haben — Stufe 1 liest es
# gegen (bericht.py, Lage 6): Ein Bericht mit Browserproben ohne diese Zeile
# ist rot.
[ "$DREIFACH" -gt 0 ] && args+=(--zahl "motoren=$(IFS=+; echo "${MOTOREN[*]}")")
# Ohne Bericht ist der Lauf rot, wie grün die Proben auch waren: Bis dahin endete er hier mit rc 0 und „0 rot", auch wenn
# bericht.py abgestürzt war — ein Lauf ohne Beleg, der sich als Beleg meldete.
rcb=0; python3 "$HIER/bericht.py" "${args[@]}" || rcb=$?
if [ "$rcb" -ne 0 ]; then
    melde "KEIN BERICHT — bericht.py endete mit rc $rcb. Der Lauf ist rot."
    exit 1
fi

melde "$fehl rot, $nicht nicht gemessen, $hinw Hinweis, $erbt ererbt, $(( ${#PROBEN[@]} - fehl - nicht - hinw - erbt )) grün · $SECONDS s"
[ "$hinw" -gt 0 ] && zeile "Hinweise (halten nicht auf, stehen im Bericht): ${HINWEISE[*]} — im Prüfprotokoll des Konzepts nennen."
[ "$WIEDERHOLT" -gt 0 ] && zeile "$WIEDERHOLT Wiederholungen in WebKit (Seitenwechsel oder Neuanmeldung) — im Prüfdokument nennen (E-DM-10)"
[ "$nicht" -gt 0 ] && zeile "Was nicht gemessen werden konnte, gehört in das Prüfdokument — an den Anfang."
# Nicht gemessen ist nicht grün: Der Bericht trägt es, und
# das Tor liest es als rot — dann soll es der Lauf auch sein.
exit $([ "$fehl" -gt 0 ] || [ "$nicht" -gt 0 ] && echo 1 || echo 0)
