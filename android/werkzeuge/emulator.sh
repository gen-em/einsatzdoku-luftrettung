#!/usr/bin/env bash
# Emulator aufsetzen, starten, bedienen, abziehen -- Stufe II fuer Android.
#
# WOZU. Die Garmin-Uhr hat seit langem zwei Stufen: uebersetzen
# (tools/uhr-pruefstand/ Stufe I) und im Simulator starten (Stufe II). Die
# Android-Module hatten nur Stufe I -- `./gradlew build`. Der Bilderlauf
# (HandyBildTest, UhrBildTest) fuellte die Luecke nur halb: Er zeichnet das
# GERECHNETE Bild, deterministisch und in Sekunden, aber ohne laufendes
# Programm. Systemleisten, echte Schriftrasterung, das runde Glas, ein Druck
# auf einen Knopf und was danach kommt -- all das sieht er nicht.
#
# Dieses Skript ist Stufe II. Aufgestellt am 03.09.2026 auf Anweisung, dass
# bei Android-Arbeit wie bei der Uhr-Arbeit immer der Emulator mitlaeuft und
# Aussehen wie Funktion mit Bildern belegt werden.
#
# DIE EINE ZAHL, DIE MAN KENNEN MUSS: Ohne KVM laeuft der Emulator in reiner
# Software-Emulation (QEMU TCG). Er laeuft -- aber er braucht Minuten, wo ein
# beschleunigter Emulator Sekunden braucht. Deshalb gehoert er ans ENDE eines
# Arbeitspakets, zu den uebrigen Pruefmitteln, nicht zwischen zwei Dateien.
#
# BERICHTIGUNG EINER FRUEHEREN MESSUNG. android/LIESMICH.md sagte bis 03.09.
# "x86_64-Abbild braucht KVM". Das stimmt fuer den Standardaufruf, aber nicht
# fuer `-accel off`: damit uebersetzt QEMU die x86_64-Befehle selbst und
# braucht die Verschachtelung nicht. Der Satz war eine Verwechslung von
# "startet nicht ohne Weiteres" mit "geht nicht".
#
# AUFRUFE
#   emulator.sh aufbauen        SDK-Teile und AVDs anlegen (einmal je Container)
#   emulator.sh start [handy]   starten und auf sys.boot_completed warten
#   emulator.sh legen APK       APK aufspielen
#   emulator.sh bild NAME       Bildschirm abziehen nach $ZIEL/NAME.png
#   emulator.sh aus             beenden
#
# WAS DAS SKRIPT NICHT TUT: Es bedient die App nicht von selbst. Welche Wege
# durchzuklicken sind, weiss nur, wer die Aenderung gemacht hat; das Skript
# stellt die Uhr, nicht den Zeiger. Fuer die Bedienung stehen `adb shell input
# tap X Y` und `adb shell am start` bereit, und jeder Schritt endet mit einem
# `bild`.
set -eu

SDK="${ANDROID_HOME:-/opt/android-sdk}"
ADB="$SDK/platform-tools/adb"
EMU="$SDK/emulator/emulator"
ZIEL="${ZIEL:-$(pwd)/emulator-bilder}"
# Einmal fuer alle Unterbefehle: sonst legt `aufbauen` die AVDs woanders ab,
# als `start` sie sucht, und der Fehler lautet "Unknown AVD name".
export ANDROID_AVD_HOME="${ANDROID_AVD_HOME:-$HOME/.android/avd}"
ABBILD="system-images;android-34;google_apis;x86_64"
ABBILD_UHR="system-images;android-30;android-wear;x86"

sag() { printf '\033[1m%s\033[0m\n' "$*"; }

aufbauen() {
  # libpulse fehlt in schlanken Containern; ohne sie startet nicht einmal
  # `emulator -version` -- die QEMU-Binaerdatei bindet sie hart.
  if ! ldconfig -p | grep -q libpulse.so.0; then
    sag "libpulse0 fehlt, wird nachinstalliert"
    apt-get install -y libpulse0 >/dev/null
  fi
  local sdkm; sdkm=$(ls "$SDK"/cmdline-tools/*/bin/sdkmanager | head -1)
  yes | "$sdkm" --licenses >/dev/null 2>&1 || true
  sag "Emulator und Abbilder laden (mehrere GB, dauert)"
  "$sdkm" emulator "$ABBILD" "$ABBILD_UHR" 2>&1 | tail -1
  mkdir -p "$ANDROID_AVD_HOME"
  echo no | "$SDK"/cmdline-tools/*/bin/avdmanager create avd \
      -n handy34 -k "$ABBILD" -d pixel_5 --force >/dev/null
  echo no | "$SDK"/cmdline-tools/*/bin/avdmanager create avd \
      -n uhr30 -k "$ABBILD_UHR" -d wearos_small_round --force >/dev/null
  sag "AVDs angelegt: $("$EMU" -list-avds | tr '\n' ' ')"
}

start() {
  local avd="${1:-handy34}" beginn; beginn=$(date +%s)
  # -no-window ist Pflicht: die GUI-Binaerdatei braucht ein X11 und Ton.
  # -accel off ist der Kern -- ohne /dev/kvm gibt es keinen anderen Weg.
  # -gpu swiftshader_indirect: die Grafik rechnet ebenfalls die CPU.
  nohup "$EMU" -avd "$avd" -no-window -no-audio -no-boot-anim -no-snapshot \
      -accel off -gpu swiftshader_indirect -memory 3072 -cores 4 \
      >"${TMPDIR:-/tmp}/emu-$avd.log" 2>&1 &
  sag "gestartet: $avd (Protokoll ${TMPDIR:-/tmp}/emu-$avd.log)"
  "$ADB" start-server >/dev/null 2>&1 || true
  until [ "$("$ADB" shell getprop sys.boot_completed 2>/dev/null | tr -d '\r')" = "1" ]; do
    sleep 15
  done
  sag "Boot fertig nach $(( $(date +%s) - beginn )) s"
}

legen() {
  local apk="$1" beginn; beginn=$(date +%s)
  "$ADB" install -r -t "$apk" >/dev/null
  sag "aufgespielt: $(basename "$apk") in $(( $(date +%s) - beginn )) s"
}

bild() {
  mkdir -p "$ZIEL"
  "$ADB" exec-out screencap -p > "$ZIEL/$1.png"
  sag "abgezogen: $ZIEL/$1.png ($(stat -c%s "$ZIEL/$1.png") Bytes)"
}

aus() { "$ADB" emu kill >/dev/null 2>&1 || true; sag "beendet"; }

case "${1:-}" in
  aufbauen) aufbauen ;;
  start)    start "${2:-handy34}" ;;
  legen)    legen "$2" ;;
  bild)     bild "$2" ;;
  aus)      aus ;;
  *) sed -n '2,40p' "$0"; exit 1 ;;
esac
