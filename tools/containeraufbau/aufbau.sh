#!/bin/sh
# Systemvoraussetzungen einer Wegwerf-Umgebung nachziehen.
#
# WOZU. Der Container von Claude Code on the web bringt PHP, Node, Python,
# Java und Chromium mit — aber weder einen Datenbankserver noch das
# Android-SDK, obwohl CLAUDE.md 6 und android/LIESMICH.md beides
# voraussetzen ("./gradlew build im Ordner android/, mit
# ANDROID_HOME=/opt/android-sdk"). Wer das nicht weiss, sucht den Fehler in
# der Anwendung.
#
# Was hier steht, ist zurueckgerechnet aus dem, was die Pruefmittel des
# Repositoriums tatsaechlich brauchen — nicht aus einer Wunschliste:
#
#   mariadb-server       jede Probe, die eine Installation braucht
#   librsvg2-bin         tools/uhr-bilder/erzeugen.sh
#   imagemagick          dasselbe, und tools/uhr-pruefstand (Bildvergleich)
#   socat                tools/referenzdatensatz/einspielen/lokal_starten.sh
#   Android-SDK 36       ./gradlew build
#   python3-cffi         cryptography -> tools/referenzdatensatz/vergleich
#
# WAS ES NICHT TUT: den Uhr-Pruefstand aufbauen. Der holt sein SDK selbst
# (tools/uhr-pruefstand/pruefstand.sh aufbau) und braucht dafuer die
# Geraetedateien aus CIQ_GERAETE_URL, die nicht im Repositorium steht.
#
# Aufruf:  sh tools/containeraufbau/aufbau.sh [teil …]
#          Teile: pakete, datenbank, android, python, alles (Vorgabe)
set -eu

ANDROID_SDK="${ANDROID_HOME:-/opt/android-sdk}"
CMDTOOLS_URL="https://dl.google.com/android/repository/commandlinetools-linux-11076708_latest.zip"

melde() { printf '\033[1m==\033[0m %s\n' "$*"; }

pakete() {
    melde "Systempakete"
    export DEBIAN_FRONTEND=noninteractive
    # Die PPA-Quellen des Abbilds sind hinter dem Egress-Filter nicht
    # erreichbar und melden 403. Das ist kein Fehler dieses Skripts —
    # die Ubuntu-Hauptquellen kommen durch, und nur die werden gebraucht.
    apt-get update -qq >/dev/null 2>&1 || true
    apt-get install -y -qq mariadb-server librsvg2-bin imagemagick socat \
                           unzip wget curl >/dev/null 2>&1 \
        || { echo "apt-get fehlgeschlagen"; exit 1; }
    # Ein abgebrochener Lauf laesst Pakete im Zustand "entpackt, nicht
    # eingerichtet" stehen; dpkg meldet das erst beim naechsten Mal.
    dpkg --configure -a >/dev/null 2>&1 || true
    printf '   mariadb %s · rsvg %s · socat vorhanden\n' \
        "$(mariadbd --version 2>/dev/null | grep -o '1[0-9.]*' | head -1)" \
        "$(rsvg-convert --version 2>/dev/null | grep -o '[0-9.]*$')"
}

datenbank() {
    melde "MariaDB starten"
    mkdir -p /var/run/mysqld /var/log/mysql
    chown -R mysql:mysql /var/run/mysqld /var/lib/mysql /var/log/mysql 2>/dev/null || true
    if ! mariadb-admin ping >/dev/null 2>&1; then
        mariadbd-safe >/var/log/mysql/safe.log 2>&1 &
        mariadb-admin ping --wait=30 >/dev/null 2>&1 \
            || { echo "MariaDB kam nicht hoch (siehe /var/log/mysql/safe.log)"; exit 1; }
    fi
    printf '   %s\n' "$(mariadb -N -e 'SELECT VERSION();')"
}

android() {
    if [ -d "$ANDROID_SDK/platforms/android-36" ]; then
        melde "Android-SDK liegt bereits"
        return
    fi
    melde "Android-SDK beschaffen (Plattform 36, Build-Tools 36.0.0)"
    mkdir -p "$ANDROID_SDK/cmdline-tools"
    curl -sS -L -o /tmp/cmdtools.zip "$CMDTOOLS_URL"
    unzip -q -o /tmp/cmdtools.zip -d "$ANDROID_SDK/cmdline-tools"
    rm -f /tmp/cmdtools.zip
    # Das Archiv entpackt nach cmdline-tools/; der sdkmanager verlangt eine
    # benannte Fassung darunter ("latest"), sonst findet er das SDK nicht.
    if [ -d "$ANDROID_SDK/cmdline-tools/cmdline-tools" ]; then
        mv "$ANDROID_SDK/cmdline-tools/cmdline-tools" "$ANDROID_SDK/cmdline-tools/latest"
    fi
    sdkm="$ANDROID_SDK/cmdline-tools/latest/bin/sdkmanager"
    yes | "$sdkm" --licenses >/dev/null 2>&1 || true
    "$sdkm" "platform-tools" "platforms;android-36" "build-tools;36.0.0" >/dev/null 2>&1
    printf '   %s\n' "$(ls "$ANDROID_SDK")"
}

python_teile() {
    melde "Python: cryptography brauchbar machen"
    # Das Abbild bringt python3-cryptography ohne _cffi_backend mit; der
    # Import bricht dann in einer Rust-Panik ab statt mit einer Fehlermeldung.
    # Betroffen: tools/referenzdatensatz/vergleich/kreislauf.py und
    # tools/referenzdatensatz/einspielen/einspielen.py.
    python3 -c 'from cryptography.hazmat.primitives.ciphers.aead import AESGCM' 2>/dev/null \
        || pip3 install --quiet --break-system-packages cffi
    python3 -c 'from cryptography.hazmat.primitives.ciphers.aead import AESGCM; print("   cryptography brauchbar")'
}

alles() { pakete; datenbank; android; python_teile;
    melde "fertig"
    cat <<ENDE
   Weiter mit:
     sh tools/referenzdatensatz/einspielen/lokal_einrichten.sh
     CIQ_ZIELE=alle tools/uhr-pruefstand/pruefstand.sh aufbau   (braucht CIQ_GERAETE_URL)
ENDE
}

# Ohne Argument alles. Als AND-Liste geschrieben wuerde `set -e` bei
# vorhandenem Argument aussteigen — der Rueckgabewert der Liste ist dann 1.
if [ $# -eq 0 ]; then set -- alles; fi
for teil in "$@"; do
    case "$teil" in
        pakete)    pakete ;;
        datenbank) datenbank ;;
        android)   android ;;
        python)    python_teile ;;
        alles)     alles ;;
        *) echo "Unbekannter Teil: $teil (pakete, datenbank, android, python, alles)"; exit 1 ;;
    esac
done
