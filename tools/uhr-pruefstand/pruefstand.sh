#!/usr/bin/env bash
# Uhr-Pruefstand — Connect-IQ-SDK und Simulator auf einem nackten Linux-Rechner
# aufbauen, die Uhr-App uebersetzen und im Simulator starten.
#
# Gedacht fuer Wegwerf-Umgebungen (Claude Code on the web, CI-Laeufer), in denen
# nach jedem Sitzungsende alles fort ist. Auf einem Arbeitsplatz mit installiertem
# SDK wird das Skript nicht gebraucht — dort tut es die Monkey-C-Erweiterung.
#
# Anleitung: LIESMICH.md im selben Verzeichnis.

set -euo pipefail

SDK_VERSION="${CIQ_SDK_VERSION:-9.2.0}"
GERAETE_URL="${CIQ_GERAETE_URL:-}"      # Quelle fuer Devices/ und Fonts/
BASIS="${CIQ_BASIS:-$HOME/.ciq-pruefstand}"
GARMIN_HOME="$HOME/.Garmin/ConnectIQ"
ZIEL_GERAETE="${CIQ_ZIELE:-fenix6pro fr945 venu3s}"

SDK_DIR="$BASIS/sdk"
LIB_DIR="$BASIS/libs/root/usr/lib/x86_64-linux-gnu"
SCHLUESSEL="$BASIS/entwickler.der"
AUSGABE="$BASIS/bin"
ANZEIGE="${CIQ_DISPLAY:-:99}"

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

melde()  { printf '\033[1m==\033[0m %s\n' "$*"; }
fehler() { printf '\033[31mFEHLER:\033[0m %s\n' "$*" >&2; exit 1; }

# ---------------------------------------------------------------- Aufbau ----

sdk_holen() {
    [ -x "$SDK_DIR/bin/monkeyc" ] && { melde "SDK $SDK_VERSION liegt bereits"; return; }

    melde "SDK $SDK_VERSION beschaffen"
    local liste datei
    liste=$(curl -sS --max-time 60 https://developer.garmin.com/downloads/connect-iq/sdks/sdks.json) \
        || fehler "SDK-Verzeichnis nicht erreichbar — developer.garmin.com freigegeben?"
    datei=$(printf '%s' "$liste" | python3 -c "
import json, sys
for e in json.load(sys.stdin):
    if e['version'] == '$SDK_VERSION':
        print(e['linux']); break
")
    [ -n "$datei" ] || fehler "SDK-Fassung $SDK_VERSION nicht im Verzeichnis"

    mkdir -p "$BASIS"
    curl -sS -L --max-time 1800 -o "$BASIS/sdk.zip" \
        "https://developer.garmin.com/downloads/connect-iq/sdks/$datei"
    unzip -q -o "$BASIS/sdk.zip" -d "$SDK_DIR"
    rm -f "$BASIS/sdk.zip"
    chmod +x "$SDK_DIR/bin/"* 2>/dev/null || true
}

# Der Simulator ist gegen webkit2gtk 4.0 gebunden. Ubuntu 24.04 fuehrt nur noch
# 4.1 — die alten Staende kommen deshalb aus 22.04 und werden NEBEN den
# Simulator gelegt, nicht ins System installiert. Das haelt den Rechner sauber
# und vermeidet Konflikte mit allem, was 4.1 erwartet.
bibliotheken() {
    [ -f "$LIB_DIR/libwebkit2gtk-4.0.so.37" ] && { melde "Bibliotheken liegen bereits"; return; }

    melde "Systembibliotheken aufloesen"
    apt-get update -qq >/dev/null 2>&1 || true
    apt-get install -y -qq \
        libsecret-1-0 libusb-1.0-0 libenchant-2-2 libmanette-0.2-0 \
        libwayland-server0 libwebpdemux2 libwebpmux3 libwoff1 \
        libgstreamer-plugins-base1.0-0 libgstreamer-gl1.0-0 \
        wget unzip imagemagick x11-apps xdotool xvfb >/dev/null 2>&1 \
        || fehler "apt-get fehlgeschlagen"

    mkdir -p "$BASIS/libs" && cd "$BASIS/libs"
    local pool=http://archive.ubuntu.com/ubuntu/pool/main
    local wk=2.50.4-0ubuntu0.22.04.1
    curl -sS -O --max-time 300 "$pool/libs/libsoup2.4/libsoup2.4-1_2.74.2-3_amd64.deb"
    curl -sS -O --max-time 600 "$pool/w/webkit2gtk/libwebkit2gtk-4.0-37_${wk}_amd64.deb"
    curl -sS -O --max-time 600 "$pool/w/webkit2gtk/libjavascriptcoregtk-4.0-18_${wk}_amd64.deb"
    curl -sS -O --max-time 300 "$pool/i/icu/libicu70_70.1-2_amd64.deb"
    mkdir -p root
    for f in *.deb; do dpkg-deb -x "$f" root; done
    rm -f ./*.deb
    cd - >/dev/null
}

# Der Entwicklerschluessel wird selbst erzeugt. Er beweist nur, dass alle
# Kompilate derselben Quelle entstammen — fuer den Simulator genuegt jeder
# gueltige Schluessel. Der Schluessel des Arbeitsplatzes gehoert NICHT hierher.
schluessel() {
    [ -f "$SCHLUESSEL" ] && { melde "Entwicklerschluessel liegt bereits"; return; }
    melde "Entwicklerschluessel erzeugen"
    mkdir -p "$BASIS"
    openssl genrsa -out "$BASIS/entwickler.pem" 4096 2>/dev/null
    openssl pkcs8 -topk8 -inform PEM -outform DER \
        -in "$BASIS/entwickler.pem" -out "$SCHLUESSEL" -nocrypt 2>/dev/null
}

# Geraetedateien und Schriften gehoeren Garmin und werden vom SDK-Manager
# ausgeliefert, der eine Anmeldung verlangt und nur als Fensteranwendung
# existiert. In einer Wegwerf-Umgebung ist beides nicht zu haben — deshalb der
# Umweg ueber eine selbst bereitgestellte Quelle. Deren Adresse steht in
# CIQ_GERAETE_URL und bewusst nicht in diesem Repositorium: sie ist privat, und
# die Dateien duerfen nicht oeffentlich weiterverbreitet werden.
geraetedateien() {
    local fehlt=0
    for g in $ZIEL_GERAETE; do
        [ -f "$GARMIN_HOME/Devices/$g/compiler.json" ] || fehlt=1
    done
    [ -d "$GARMIN_HOME/Fonts" ] || fehlt=1
    [ "$fehlt" -eq 0 ] && { melde "Geraetedateien und Schriften liegen bereits"; return; }

    [ -n "$GERAETE_URL" ] || fehler \
"Geraetedateien fehlen und CIQ_GERAETE_URL ist nicht gesetzt.
   Erwartet wird eine Adresse, unter der Devices/ und Fonts/ aus
   ~/.Garmin/ConnectIQ abrufbar sind. Siehe LIESMICH.md, Abschnitt Quelle."

    melde "Geraetedateien holen ($ZIEL_GERAETE)"
    mkdir -p "$GARMIN_HOME/Devices" && cd "$GARMIN_HOME/Devices"
    for g in $ZIEL_GERAETE; do
        wget -q -r -np -nH --cut-dirs=1 -R "index.html*" "$GERAETE_URL/Devices/$g/" \
            || fehler "Geraet $g nicht abrufbar"
    done

    # Die Schriften sind rund 1,2 GB. Welche Datei zu welchem Geraet gehoert,
    # steht nur im Geraeteabbild (.bin) — deshalb wird der ganze Bestand
    # geholt statt geraten. Ein fehlender Zeichensatz aeussert sich als
    # "Invalid Font Specified" und beendet die App beim ersten Zeichnen.
    melde "Schriften holen (rund 1,2 GB, dauert)"
    mkdir -p "$GARMIN_HOME/Fonts" && cd "$GARMIN_HOME/Fonts"
    wget -q -r -np -nH --cut-dirs=1 -R "index.html*" "$GERAETE_URL/Fonts/" \
        || fehler "Schriften nicht abrufbar"
    cd - >/dev/null
}

# ----------------------------------------------------------------- Nutzen ----

umgebung() {
    export PATH="$SDK_DIR/bin:$PATH"
    export LD_LIBRARY_PATH="$LIB_DIR${LD_LIBRARY_PATH:+:$LD_LIBRARY_PATH}"
    export DISPLAY="$ANZEIGE"
    export JAVA_TOOL_OPTIONS=""      # sonst verrauscht der Proxy-Hinweis jede Ausgabe
}

bauen() {
    local geraet="${1:?Geraet fehlt}" jungle="${2:-$WURZEL/watch/monkey.jungle}"
    umgebung; mkdir -p "$AUSGABE"
    melde "Uebersetzen fuer $geraet"
    monkeyc -f "$jungle" -d "$geraet" -o "$AUSGABE/$geraet.prg" \
            -y "$SCHLUESSEL" "${@:3}"
    printf 'Kompilat: %s\n' "$AUSGABE/$geraet.prg"
}

anzeige_starten() {
    umgebung
    pgrep -f "Xvfb $ANZEIGE" >/dev/null && return
    nohup Xvfb "$ANZEIGE" -screen 0 1400x1000x24 >/dev/null 2>&1 &
    sleep 3
}

simulator_starten() {
    umgebung; anzeige_starten
    pgrep -f "$SDK_DIR/bin/simulator" >/dev/null && { melde "Simulator laeuft bereits"; return; }
    melde "Simulator starten"
    (cd "$SDK_DIR/bin" && nohup ./simulator >"$BASIS/simulator.log" 2>&1 &)
    sleep 18
}

# monkeydo haelt die Verbindung zum Simulator, solange die App laeuft, und
# kehrt darum nicht zurueck. Es gehoert deshalb in den Hintergrund — sonst
# blockiert es jeden folgenden Befehl. Seine Ausgabe ist trotzdem wichtig:
# dort landen Abstuerze und alles aus System.println.
starten() {
    local geraet="${1:?Geraet fehlt}" wartezeit="${2:-25}"
    [ -f "$AUSGABE/$geraet.prg" ] || fehler \
"Kein Kompilat fuer $geraet. Erst uebersetzen:
   $(basename "${BASH_SOURCE[0]}") bauen $geraet"
    umgebung; simulator_starten
    melde "App laden: $geraet"
    : >"$BASIS/konsole.log"
    nohup monkeydo "$AUSGABE/$geraet.prg" "$geraet" >"$BASIS/konsole.log" 2>&1 &
    sleep "$wartezeit"
    konsole
}

# Was der Simulator ausgibt: System.println der App, Absturzmeldungen samt
# Aufrufliste. Nach jeder Bedienung erneut lesen — die Ausgabe waechst.
konsole() { grep -v 'JAVA_TOOL_OPTIONS' "$BASIS/konsole.log" 2>/dev/null || true; }

# Der Simulator kennt keinen Bildschirmabzug ueber die Kommandozeile — deshalb
# der Umweg ueber den X-Server. Was hier herauskommt, ist das Fenster, nicht
# das Geraet: Rahmen und Menueleiste sind mit drauf.
abbild() {
    local ziel="${1:-$BASIS/abbild.png}"
    umgebung
    xwd -root -display "$ANZEIGE" | convert xwd:- "$ziel"
    printf 'Abbild: %s\n' "$ziel"
}

# Tastendruecke und Touch gehen als X-Ereignisse an das Simulatorfenster. Die
# Koordinaten sind Fenster-, nicht Geraetekoordinaten — die Statusleiste des
# Simulators zeigt die umgerechnete Geraeteposition an.
tippen()  { umgebung; xdotool mousemove "${1:?x}" "${2:?y}" click 1; }
halten()  { umgebung; xdotool mousemove "${1:?x}" "${2:?y}" mousedown 1
            sleep "${3:-1.5}"; xdotool mouseup 1; }
wischen() {                                  # wischen <x> <vonY> <nachY>
    umgebung
    local x="${1:?x}" von="${2:?vonY}" nach="${3:?nachY}" schritt
    schritt=$(( von < nach ? 40 : -40 ))
    xdotool mousemove "$x" "$von" mousedown 1
    for ((y=von; (schritt>0 ? y<nach : y>nach); y+=schritt)); do
        xdotool mousemove "$x" "$y"; sleep 0.05
    done
    xdotool mousemove "$x" "$nach"; xdotool mouseup 1
}
taste()   { umgebung; xdotool key "${1:?Taste}"; }

beenden() {
    pkill -f "$SDK_DIR/bin/simulator" 2>/dev/null || true
    pkill -f "Xvfb $ANZEIGE" 2>/dev/null || true
    melde "Simulator und Anzeige beendet"
}

pruefen() {
    umgebung
    local mangel=0
    printf '%-28s %s\n' "SDK"        "$(monkeyc --version 2>/dev/null || { echo 'FEHLT'; mangel=1; })"
    printf '%-28s %s\n' "Schluessel" "$([ -f "$SCHLUESSEL" ] && echo vorhanden || { echo FEHLT; mangel=1; })"
    printf '%-28s %s\n' "Schriften"  "$(find "$GARMIN_HOME/Fonts" -name '*.cft' 2>/dev/null | wc -l) Dateien"
    for g in $ZIEL_GERAETE; do
        printf '%-28s %s\n' "Geraet $g" \
            "$([ -f "$GARMIN_HOME/Devices/$g/compiler.json" ] && echo vorhanden || { echo FEHLT; mangel=1; })"
    done
    # grep -c meldet bei null Treffern Rueckgabewert 1 — das ist hier der
    # Gutfall, deshalb der Ausgleich mit true.
    printf '%-28s %s\n' "Simulatorbibliotheken" \
        "$(ldd "$SDK_DIR/bin/simulator" 2>/dev/null | grep -c 'not found' || true) fehlend"
    return $mangel
}

aufbau() { sdk_holen; bibliotheken; schluessel; geraetedateien; pruefen; }

alle() {
    aufbau
    for g in $ZIEL_GERAETE; do bauen "$g" "$@"; done
}

hilfe() {
    sed -n '2,10p' "${BASH_SOURCE[0]}" | sed 's/^# \?//'
    cat <<'ENDE'

Befehle:
  aufbau                     SDK, Bibliotheken, Schluessel, Geraetedateien
  pruefen                    Bestand auflisten
  bauen <geraet> [jungle]    uebersetzen (weitere Schalter werden durchgereicht)
  alle [schalter]            aufbauen und alle Zielgeraete uebersetzen
  starten <geraet> [sek]     Simulator starten und App laden (Vorgabe 25 s)
  konsole                    Ausgabe des Simulators nachlesen
  abbild [datei]             Bildschirmabzug des Simulatorfensters
  tippen <x> <y>             Touch-Tipp
  halten <x> <y> [sek]       Touch-Langdruck
  wischen <x> <vonY> <nachY> Wischgeste
  taste <name>               Tastendruck (xdotool-Name, z. B. Return)
  beenden                    Simulator und Anzeige beenden

Umgebungsvariablen:
  CIQ_SDK_VERSION   SDK-Fassung (Vorgabe 9.2.0)
  CIQ_GERAETE_URL   Quelle fuer Devices/ und Fonts/ — ohne sie kein Aufbau
  CIQ_ZIELE         Zielgeraete (Vorgabe: fenix6pro fr945 venu3s)
  CIQ_BASIS         Ablage (Vorgabe ~/.ciq-pruefstand)
ENDE
}

befehl="${1:-hilfe}"; shift || true
case "$befehl" in
    aufbau|pruefen|bauen|alle|starten|konsole|abbild|tippen|halten|wischen|taste|beenden|hilfe)
        "$befehl" "$@" ;;
    *) hilfe; exit 1 ;;
esac
