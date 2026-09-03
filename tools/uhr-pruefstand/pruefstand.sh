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
# SCHRAEGSTRICH AM ENDE WEG — sonst entsteht ein leeres Pfadsegment.
#
# Die Adresse wird unten als "$GERAETE_URL/Devices/" zusammengesetzt. Endet sie
# selbst auf einem Schraegstrich, steht dort "https://host//Devices/", und das
# leere Segment zaehlt fuer wget als Verzeichnisebene. pfadtiefe() sieht es
# nicht (es misst die Adresse, nicht das Ergebnis der Zusammensetzung), also
# ist --cut-dirs um eins zu klein: Der Baum landet teils als
# Devices/Devices/<geraet>/ und teils richtig — je nachdem, ob wget eine Datei
# ueber die Startadresse oder ueber einen Verweis aus der Verzeichnisauflistung
# erreicht. Der halb richtige Baum ist die unangenehme Form: `pruefen` findet
# die drei Zielgeraete und meldet Vollzug, waehrend `reihe` fuer die anderen
# 170 "Geraetedatei fehlt" sagt.
#
# Gemessen am 03.09.2026: 173 Geraete unter Devices/Devices, 732 MB Schriften
# unter Fonts/Fonts, daneben je ein korrekt abgelegter Teil. Am 02.09. war
# dasselbe Bild schon einmal da, mit anderer Ursache (fest verdrahtete 1) —
# deshalb steht die Normalisierung jetzt hier, an der Quelle, und nicht in der
# Rechnung.
while [ "${GERAETE_URL%/}" != "$GERAETE_URL" ]; do GERAETE_URL="${GERAETE_URL%/}"; done
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
        wget unzip imagemagick x11-apps x11-utils xdotool xvfb >/dev/null 2>&1 \
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

# Wieviele Pfadabschnitte hat die Adresse? wget legt den Baum unter dem
# aktuellen Verzeichnis ab und zaehlt dabei JEDEN Abschnitt der Adresse mit;
# --cut-dirs muss sie alle abschneiden, den Abschnitt "Devices" eingeschlossen.
# Fest verdrahtete 1 traegt nur, wenn die Quelle in der Wurzel des Servers
# liegt — bei https://beispiel.invalid/ciq landet der Baum sonst als
# Devices/Devices/<geraet>/, und monkeyc findet kein Geraet. Gemessen am
# 02.09.2026 gegen einen oertlichen Testserver, beide Formen.
pfadtiefe() {
    local p="${1#*://}"
    p="${p#"${p%%/*}"}"          # Host weg
    p="${p#/}"; p="${p%/}"
    [ -z "$p" ] && { echo 0; return; }
    printf '%s\n' "$p" | tr '/' '\n' | grep -c .
}

# Geraetedateien und Schriften gehoeren Garmin und werden vom SDK-Manager
# ausgeliefert, der eine Anmeldung verlangt und nur als Fensteranwendung
# existiert. In einer Wegwerf-Umgebung ist beides nicht zu haben — deshalb der
# Umweg ueber eine selbst bereitgestellte Quelle. Deren Adresse steht in
# CIQ_GERAETE_URL und bewusst nicht in diesem Repositorium: sie ist privat, und
# die Dateien duerfen nicht oeffentlich weiterverbreitet werden.
geraetedateien() {
    local fehlt=0
    if [ "$ZIEL_GERAETE" = "alle" ]; then
        # Bei "alle" ist die Frage nicht, ob ein bestimmtes Geraet daliegt,
        # sondern ob ueberhaupt etwas dasteht — die Liste kennt man ja erst
        # danach.
        [ -n "$(ls "$GARMIN_HOME/Devices" 2>/dev/null)" ] || fehlt=1
    else
        for g in $ZIEL_GERAETE; do
            [ -f "$GARMIN_HOME/Devices/$g/compiler.json" ] || fehlt=1
        done
    fi
    [ -d "$GARMIN_HOME/Fonts" ] || fehlt=1
    [ "$fehlt" -eq 0 ] && { melde "Geraetedateien und Schriften liegen bereits"; return; }

    [ -n "$GERAETE_URL" ] || fehler \
"Geraetedateien fehlen und CIQ_GERAETE_URL ist nicht gesetzt.
   Erwartet wird eine Adresse mit Verzeichnisauflistung, unter der Devices/
   und Fonts/ aus ~/.Garmin/ConnectIQ abrufbar sind. Die Adresse steht nicht
   im Repositorium — sie kommt von der Projektleitung. Siehe LIESMICH.md,
   Abschnitt Quelle."

    local schnitt=$(( $(pfadtiefe "$GERAETE_URL") + 1 ))
    mkdir -p "$GARMIN_HOME/Devices" && cd "$GARMIN_HOME/Devices"
    if [ "$ZIEL_GERAETE" = "alle" ]; then
        # Fuer Stufe I und geraeteklassen.py wird der GANZE Bestand gebraucht:
        # Welche Geraete es gibt, steht nirgends sonst — die Liste ist das
        # Verzeichnis selbst.
        melde "Geraetedateien holen (alle)"
        wget -q -r -np -nH --cut-dirs="$schnitt" -R "index.html*" "$GERAETE_URL/Devices/" \
            || fehler "Geraeteverzeichnis nicht abrufbar"
    else
        melde "Geraetedateien holen ($ZIEL_GERAETE)"
        for g in $ZIEL_GERAETE; do
            wget -q -r -np -nH --cut-dirs="$schnitt" -R "index.html*" "$GERAETE_URL/Devices/$g/" \
                || fehler "Geraet $g nicht abrufbar"
        done
    fi

    # Die Schriften sind rund 1,2 GB. Welche Datei zu welchem Geraet gehoert,
    # steht nur im Geraeteabbild (.bin) — deshalb wird der ganze Bestand
    # geholt statt geraten. Ein fehlender Zeichensatz aeussert sich als
    # "Invalid Font Specified" und beendet die App beim ersten Zeichnen.
    melde "Schriften holen (rund 1,2 GB, dauert)"
    mkdir -p "$GARMIN_HOME/Fonts" && cd "$GARMIN_HOME/Fonts"
    wget -q -r -np -nH --cut-dirs="$schnitt" -R "index.html*" "$GERAETE_URL/Fonts/" \
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

# DER JUNGLE IST OPTIONAL, DIE SCHALTER SIND ES AUCH — und beides an
# derselben Stelle. Bis zum 03.09.2026 nahm diese Funktion das zweite Argument
# unbesehen als Jungle-Pfad. Der in der LIESMICH dokumentierte Aufruf
#
#     pruefstand.sh bauen fenix6pro -l 3
#
# uebergab damit "-l" als Jungle und "3" als Schalter; monkeyc brach ab mit
# "Missing argument for option: f" und druckte seine Hilfe. Der Aufruf mit
# eigenem Jungle (tools/eingabe-probe) funktionierte, der dokumentierte nicht —
# und die strenge Typpruefung ist gerade der Schalter, den die Abnahme
# verlangt. Ein zweites Argument, das mit "-" beginnt, ist deshalb ein
# Schalter und kein Pfad.
bauen() {
    local geraet="${1:?Geraet fehlt}"; shift
    local jungle="$WURZEL/watch/monkey.jungle"
    case "${1:-}" in
        -*|"") ;;                       # Schalter oder nichts: Vorgabe behalten
        *) jungle="$1"; shift ;;
    esac
    umgebung; mkdir -p "$AUSGABE"
    # DIESELBE GRAFIKFALLE WIE IN `reihe` (Begruendung dort): monkeyc skaliert
    # das Launcher-Icon ueber java.awt.BufferedImage und braucht dafuer eine
    # Grafikumgebung. Ohne sie endet der Lauf in einem AWTError statt in einer
    # ERROR-Zeile. `reihe` setzt headless, `bauen` tat es bis zum 03.09.2026
    # nicht — und `umgebung` leert JAVA_TOOL_OPTIONS sogar ausdruecklich.
    # Aufgefallen an `bauen venu3s tools/eingabe-probe/monkey.jungle`; Geraete,
    # deren Icon exakt passt (fenix6pro, fr945), bauen ohne die Zeile durch,
    # und deshalb sah der Ausfall nach einem Geraeteproblem aus.
    export JAVA_TOOL_OPTIONS="-Djava.awt.headless=true"
    melde "Uebersetzen fuer $geraet"
    monkeyc -f "$jungle" -d "$geraet" -o "$AUSGABE/$geraet.prg" \
            -y "$SCHLUESSEL" "$@"
    printf 'Kompilat: %s\n' "$AUSGABE/$geraet.prg"
}

anzeige_starten() {
    umgebung
    pgrep -f "Xvfb $ANZEIGE" >/dev/null && return
    # Ein abgebrochener Lauf laesst die Sperrdatei stehen; Xvfb weigert sich
    # dann mit "Server is already active" und der Simulator startet ins Leere.
    local n="${ANZEIGE#:}"
    if [ -e "/tmp/.X${n}-lock" ]; then
        melde "verwaiste Anzeige-Sperre entfernen"
        rm -f "/tmp/.X${n}-lock" "/tmp/.X11-unix/X${n}"
    fi
    # setsid, nicht nur nohup: In einer Werkzeugumgebung, die jeden Befehl in
    # einer eigenen Shell ausfuehrt, wird beim Verlassen die ganze Prozessgruppe
    # abgeraeumt — die Anzeige war dann schon fort, bevor der naechste Befehl
    # sie brauchte ("unable to open display"). setsid loest sie heraus.
    setsid nohup Xvfb "$ANZEIGE" -screen 0 1400x1000x24 >/dev/null 2>&1 </dev/null &
    sleep 3
    xdpyinfo -display "$ANZEIGE" >/dev/null 2>&1 \
        || fehler "Anzeige $ANZEIGE liess sich nicht starten"
}

simulator_starten() {
    umgebung; anzeige_starten
    pgrep -f "$SDK_DIR/bin/simulator" >/dev/null && { melde "Simulator laeuft bereits"; return; }
    melde "Simulator starten"
    (cd "$SDK_DIR/bin" && setsid nohup ./simulator >"$BASIS/simulator.log" 2>&1 </dev/null &)
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
    # s. einstellungen_leeren(): eine stehende .SET-Datei ueberstimmt die
    # Vorgaben aus properties.xml — das sieht man dem Kompilat nicht an.
    # WELCHE Datei zu diesem Lauf gehoert, laesst sich NICHT am Namen ablesen
    # (s. Kopf von einstellungen_leeren), deshalb wird jede gemeldet.
    # Das || true ist noetig: Ohne .SET-Datei scheitert das ls, und unter
    # "set -e -o pipefail" nimmt es den ganzen Aufruf mit.
    local vorhanden
    vorhanden=$(ls "$EINSTELL_ABLAGE"/*.SET 2>/dev/null | xargs -r -n1 basename | tr '\n' ' ' || true)
    [ -n "$vorhanden" ] && melde "Hinweis: gespeicherte App-Einstellungen aktiv ($vorhanden) — 'einstellungen-leeren' setzt sie zurueck"
    # Eine vorherige Sitzung zuerst beenden: monkeydo laeuft weiter, solange die
    # App laeuft, und zwei gleichzeitige Verbindungen blockieren einander.
    pkill -f "monkeydo" 2>/dev/null || true
    melde "App laden: $geraet"
    : >"$BASIS/konsole.log"
    setsid nohup monkeydo "$AUSGABE/$geraet.prg" "$geraet" \
        >"$BASIS/konsole.log" 2>&1 </dev/null &
    sleep "$wartezeit"
    konsole
}

# Was der Simulator ausgibt: System.println der App, Absturzmeldungen samt
# Aufrufliste. Nach jeder Bedienung erneut lesen — die Ausgabe waechst.
konsole() { grep -v 'JAVA_TOOL_OPTIONS' "$BASIS/konsole.log" 2>/dev/null || true; }

# ---- Stufe I: uebersetzen, fuer viele Geraete --------------------------------
#
# Uebersetzen ist billig (wenige Sekunden je Geraet) und faengt schon eine
# Menge: fehlende API-Funktionen, nicht vorhandene Ressourcen, Speicherbedarf.
# Deshalb laeuft es ueber ALLE Zielgeraete, nicht nur ueber Vertreter.
reihe() {
    local liste="${1:?Listendatei fehlt}"; shift || true
    [ -f "$liste" ] || fehler "Liste nicht gefunden: $liste"
    umgebung; mkdir -p "$AUSGABE"
    # monkeyc braucht eine Grafikumgebung, sobald es das Launcher-Icon auf die
    # Groesse des Geraets skalieren muss (java.awt.BufferedImage). Fehlt sie,
    # bricht es mit einem AWTError ab — und zwar OHNE ERROR-Zeile: Das Kompilat
    # fehlt dann einfach. Geraete, deren Icon exakt passt, bauen durch; der
    # Ausfall sieht deshalb nach einem Geraeteproblem aus und ist keins.
    export JAVA_TOOL_OPTIONS="-Djava.awt.headless=true"
    local ok=0 mangel=0 fehlend=0
    printf '%-26s %5s %6s %10s  %s\n' "Gerät" "Warn" "Fehler" "Größe" "Anmerkung"
    printf '%s\n' "----------------------------------------------------------------------"
    while read -r g; do
        [ -z "$g" ] && continue
        if [ ! -f "$GARMIN_HOME/Devices/$g/compiler.json" ]; then
            printf '%-26s %5s %6s %10s  %s\n' "$g" "-" "-" "-" "Gerätedatei fehlt"
            fehlend=$((fehlend+1)); continue
        fi
        local log="$BASIS/reihe_$g.txt"
        monkeyc -f "$WURZEL/watch/monkey.jungle" -d "$g" -o "$AUSGABE/$g.prg" \
                -y "$SCHLUESSEL" -w "$@" >"$log" 2>&1 || true
        sed -i '/JAVA_TOOL_OPTIONS/d' "$log" 2>/dev/null || true
        local w e sz
        w=$(grep -c 'WARNING' "$log" || true)
        # Ausnahmen der Java-Ebene tragen keine ERROR-Zeile — mitzaehlen,
        # sonst steht da "0 Fehler" neben einem fehlenden Kompilat.
        e=$(grep -cE 'ERROR|Exception in thread' "$log" || true)
        sz=$(stat -c%s "$AUSGABE/$g.prg" 2>/dev/null || echo 0)
        if [ "$e" -gt 0 ] || [ "$sz" -eq 0 ]; then
            printf '%-26s %5s %6s %10s  %s\n' "$g" "$w" "$e" "$sz" "FEHLGESCHLAGEN"
            mangel=$((mangel+1))
        else
            printf '%-26s %5s %6s %10s\n' "$g" "$w" "$e" "$sz"
            ok=$((ok+1))
        fi
    done < "$liste"
    printf '%s\n' "----------------------------------------------------------------------"
    printf 'übersetzt: %s   fehlgeschlagen: %s   ohne Gerätedatei: %s\n' \
           "$ok" "$mangel" "$fehlend"
    [ "$mangel" -eq 0 ] && [ "$fehlend" -eq 0 ]
}

# ---- Stufe II: im Simulator starten, je Geraet ein Abbild --------------------
#
# Teuer (rund eine Minute je Geraet), deshalb nur fuer die Vertreter der
# Klassen. monkeydo wird bewusst direkt gestartet und wieder beendet statt
# ueber starten() — in einer Schleife blockiert das sonst (s. LIESMICH).
bildreihe() {
    local liste="${1:?Listendatei fehlt}" ziel="${2:-$BASIS/abbilder}"
    [ -f "$liste" ] || fehler "Liste nicht gefunden: $liste"
    umgebung; simulator_starten; mkdir -p "$ziel"
    local wartezeit="${CIQ_WARTEZEIT:-26}"
    while read -r g; do
        [ -z "$g" ] && continue
        if [ ! -f "$AUSGABE/$g.prg" ]; then
            printf '%-26s %s\n' "$g" "kein Kompilat — erst 'reihe'"; continue
        fi
        : >"$BASIS/konsole.log"
        nohup monkeydo "$AUSGABE/$g.prg" "$g" >"$BASIS/konsole.log" 2>&1 &
        local md=$!
        sleep "$wartezeit"
        xwd -root -display "$ANZEIGE" 2>/dev/null | convert xwd:- "$ziel/$g.png" 2>/dev/null
        # Absturzmeldungen des Simulators sind die eigentliche Ausbeute
        local stoerung
        stoerung=$(grep -c -iE 'error|crash|exception' "$BASIS/konsole.log" 2>/dev/null || true)
        if [ "${stoerung:-0}" -gt 0 ]; then
            printf '%-26s %s\n' "$g" "MELDUNG:"
            konsole | head -6 | sed 's/^/    /'
        else
            printf '%-26s %s\n' "$g" "ok"
        fi
        kill "$md" 2>/dev/null || true
        sleep 2
    done < "$liste"
    printf 'Abbilder unter %s\n' "$ziel"
}

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
# Tastendruck. Anders als Mausereignisse (die an das Fenster unter dem Zeiger
# gehen) wertet der Simulator Tasten nur am FOKUSFENSTER — ohne Fenstermanager
# hat aber keines den Fokus, und der Druck verpufft spurlos. windowfocus setzt
# ihn ueber XSetInputFocus; windowactivate scheitert hier, weil Xvfb ohne
# Fenstermanager kein _NET_ACTIVE_WINDOW kennt.
#
# Der ERSTE Druck nach dem Laden geht regelmaessig verloren — die App
# initialisiert noch. Wer sicher gehen will, drueckt zweimal und sieht nach.
taste()   {
    umgebung
    local wid
    wid=$(xdotool search --onlyvisible --name "CIQ Simulator" 2>/dev/null | head -1)
    [ -n "$wid" ] && { xdotool windowfocus "$wid" 2>/dev/null; sleep 0.5; }
    xdotool key "${1:?Taste}"
}

# Der Simulator fuehrt ZWEI Ablagen, die jeden Neustart und jedes neue Kompilat
# ueberleben: die App-Einstellungen (SETTINGS/*.SET, das Gegenstueck zu den
# Einstellungen in Connect IQ) und den Anwendungsspeicher (DATA/*.DAT samt
# *.IDX, das Gegenstueck zu Application.Storage).
#
# Beide werden NUR EINMAL angelegt. Wer eine Vorgabe in properties.xml aendert
# und neu uebersetzt, sieht deshalb weiter den alten Wert — die Datei gewinnt.
# Am 31.08.2026 hat das zwei Laeufe lang dieselbe Bildmarke gezeigt, obwohl das
# Kompilat die andere trug.
#
# WONACH DIE DATEIEN HEISSEN, WEISS MAN NICHT. Nicht nach dem Geraet und nicht
# verlaesslich nach dem Kompilat: Am 02.09.2026 ergaben zzprobe.prg und qq7.prg
# — beide aus demselben Quelltext uebersetzt — jeweils V2.SET und V2.DAT, den
# Namen eines frueher geladenen v2.prg; uuid_alt.prg dagegen legte UUID_ALT.SET
# an. Der Name klebt offenbar an der App, nicht an der Datei, und ueberlebt das
# Loeschen der Ablage. Praktische Folge: Wer eine Ablage leeren will, leert sie
# GANZ, und liest aus einem Dateinamen NICHT ab, zu welcher App er gehoert.
#
# Das VERZEICHNIS muss stehen bleiben. Fehlt SETTINGS ganz, wirft
# Properties.getValue() einen Fehler, den ein "catch (e)" NICHT faengt: Die App
# stirbt beim ersten Zeichnen. Ein fehlender SCHLUESSEL dagegen wird sauber
# gefangen — geprueft mit einer Probe auf "gibtsNicht".
EINSTELL_ABLAGE=/tmp/com.garmin.connectiq/GARMIN/APPS/SETTINGS
SPEICHER_ABLAGE=/tmp/com.garmin.connectiq/GARMIN/APPS/DATA

einstellungen_leeren() {
    mkdir -p "$EINSTELL_ABLAGE"
    rm -f "$EINSTELL_ABLAGE"/*.SET
    melde "App-Einstellungen geleert (Verzeichnis bleibt)"
}

# Setzt die App auf den Zustand "frisch installiert" zurueck: keine Kopplung,
# kein Dienst, keine Warteschlange. Ohne das misst jeder zweite Lauf den
# Zustand des ersten.
speicher_leeren() {
    mkdir -p "$SPEICHER_ABLAGE"
    rm -f "$SPEICHER_ABLAGE"/*.DAT "$SPEICHER_ABLAGE"/*.IDX
    melde "Anwendungsspeicher geleert (Verzeichnis bleibt)"
}

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
    if [ "$ZIEL_GERAETE" = "alle" ]; then
        printf '%-28s %s\n' "Geraete" \
            "$(find "$GARMIN_HOME/Devices" -name compiler.json 2>/dev/null | wc -l) mit compiler.json"
    else
        for g in $ZIEL_GERAETE; do
            printf '%-28s %s\n' "Geraet $g" \
                "$([ -f "$GARMIN_HOME/Devices/$g/compiler.json" ] && echo vorhanden || { echo FEHLT; mangel=1; })"
        done
    fi
    # grep -c meldet bei null Treffern Rueckgabewert 1 — das ist hier der
    # Gutfall, deshalb der Ausgleich mit true.
    printf '%-28s %s\n' "Simulatorbibliotheken" \
        "$(ldd "$SDK_DIR/bin/simulator" 2>/dev/null | grep -c 'not found' || true) fehlend"
    return $mangel
}

aufbau() { sdk_holen; bibliotheken; schluessel; geraetedateien; pruefen; }

alle() {
    aufbau
    # Bei CIQ_ZIELE=alle geht es um die Beschaffung, nicht ums Uebersetzen —
    # dafuer ist "reihe" da, das eine Geraeteliste bekommt.
    [ "$ZIEL_GERAETE" = "alle" ] && { melde "CIQ_ZIELE=alle: zum Uebersetzen 'reihe <liste>' benutzen"; return; }
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
  einstellungen-leeren       gespeicherte App-Einstellungen des Simulators
                             verwerfen (sonst gewinnen sie ueber
                             properties.xml)
  speicher-leeren            Application.Storage des Simulators verwerfen —
                             die App ist danach "frisch installiert"
  beenden                    Simulator und Anzeige beenden

Umgebungsvariablen:
  CIQ_SDK_VERSION   SDK-Fassung (Vorgabe 9.2.0)
  CIQ_GERAETE_URL   Quelle fuer Devices/ und Fonts/ — ohne sie kein Aufbau
  CIQ_ZIELE         Zielgeraete (Vorgabe: fenix6pro fr945 venu3s);
                    "alle" holt beim Aufbau den ganzen Geraetebestand —
                    noetig fuer reihe und geraeteklassen.py
  CIQ_WARTEZEIT     Sekunden je Geraet in bildreihe (Vorgabe 26)
  CIQ_BASIS         Ablage (Vorgabe ~/.ciq-pruefstand)
ENDE
}

befehl="${1:-hilfe}"; shift || true
case "$befehl" in
    aufbau|pruefen|bauen|alle|reihe|bildreihe|starten|konsole|abbild|tippen|halten|wischen|taste|beenden|hilfe)
        "$befehl" "$@" ;;
    einstellungen-leeren)
        einstellungen_leeren ;;
    speicher-leeren)
        speicher_leeren ;;
    *) hilfe; exit 1 ;;
esac
