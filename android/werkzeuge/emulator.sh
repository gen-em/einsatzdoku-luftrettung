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
# UND DIE ZWEITE ZAHL: -memory. Der erste Versuch am 03.09.2026 lief mit
# 3072 MB und kam nach 23 Minuten nicht ueber die halbe SystemServer-Folge
# hinaus -- `window` und `input` waren nicht registriert. Die Ursache war
# NICHT die Rechenleistung: Die Gast-CPU stand zu 276 von 400 Prozent
# untaetig, waehrend der Speicher bei 2,82 von 3,05 GB stand. Wer einen
# haengenden Boot sieht, misst zuerst `adb shell top -n 1 -b | head -4` --
# untaetige CPU bei vollem Speicher heisst: mehr -memory, nicht mehr Geduld.
#
# UND DIE DRITTE ZAHL: 60 Sekunden. So lange darf der system_server unter
# Android in systemReady haengen, bevor der Watchdog ihn erschiesst -- und
# unter TCG haengt er laenger. Ohne `ro.hw_timeout_multiplier` (siehe start)
# bootet das Abbild NIE, und zwar ohne jede Meldung ausser einem "GOODBYE!"
# im logcat. Am 07.09.2026 vier Anlaeufe lang (14, 38, 22 und 12 Minuten)
# nicht erkannt, weil `adb devices` "device" sagte und nur boot_completed
# fehlte. Wer einen Boot sieht, der bei "device" stehen bleibt, liest zuerst
# `adb logcat -d | grep -a GOODBYE`.
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
# WELCHES ABBILD. `default` statt `google_apis`: Der Erstboot des
# google_apis-Abbilds entpackt die Chrome-, WebView- und Trichrome-Stubs und
# uebersetzt anschliessend die Play-Dienste mit dex2oat vor -- unter TCG sind
# das viele Minuten fuer etwas, das dieses Projekt nicht braucht. Der Data
# Layer laesst sich ohnehin nicht pruefen, solange keine Companion-App da ist
# (LIESMICH.md, Abschnitt 7); Ortung, JobScheduler und Meldungen brauchen die
# Play-Dienste nicht. Wer sie doch braucht, setzt ABBILD von aussen.
ABBILD="${ABBILD:-system-images;android-34;default;x86_64}"
ABBILD_UHR="${ABBILD_UHR:-system-images;android-30;android-wear;x86}"

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
  # KEIN -no-snapshot: Der Erstboot kostet unter TCG Minuten, der zweite Start
  # aus dem Abzug Sekunden. `aus` (adb emu kill) legt den Abzug an.
  # setsid: Der Emulator darf KEIN KIND DER AUFRUFENDEN SHELL sein. Wird die
  # abgebrochen (Strg-C, ein gestoppter Hintergrundauftrag), stirbt er sonst
  # mit -- am 07.09.2026 kostete das einen Boot von 14 Minuten (0.14.0).
  setsid nohup "$EMU" -avd "$avd" -no-window -no-audio -no-boot-anim \
      -accel off -gpu swiftshader_indirect -memory 6144 -partition-size 4096 \
      -cores 4 \
      >"${TMPDIR:-/tmp}/emu-$avd.log" 2>&1 < /dev/null &
  sag "gestartet: $avd (Protokoll ${TMPDIR:-/tmp}/emu-$avd.log)"
  "$ADB" start-server >/dev/null 2>&1 || true

  # DER WATCHDOG (gefunden am 07.09.2026, nach vier Anlaeufen ohne Boot).
  # Unter TCG erschiesst der Android-Watchdog den system_server nach 60 s
  # Blockade in systemReady ("*** GOODBYE!", SIG 9 -- `adb logcat -d | grep
  # GOODBYE`); Zygote geht mit, alles startet neu, sys.boot_completed kommt
  # NIE. Die Frist skaliert mit `ro.hw_timeout_multiplier` (Cuttlefish setzt
  # sie fuer langsame Geraete). `-prop` kann das nicht (nur qemu.*) -- aber
  # das Abbild ist userdebug: Sobald adbd da ist, als Root setzen (eine
  # ro-Eigenschaft laesst sich setzen, solange sie noch nicht gesetzt ist)
  # und das Framework neu starten, damit der naechste system_server sie
  # liest. Gemessen: adbd nach 120 s, Boot 553 s danach, 715 s gesamt.
  until [ "$("$ADB" get-state 2>/dev/null | tr -d '\r')" = "device" ]; do sleep 10; done
  sag "adbd da nach $(( $(date +%s) - beginn )) s -- Watchdog-Faktor setzen, Framework neu starten"
  "$ADB" root >/dev/null 2>&1 || true; sleep 5
  "$ADB" shell setprop ro.hw_timeout_multiplier 10 >/dev/null 2>&1 || true
  "$ADB" shell "stop; sleep 3; start" >/dev/null 2>&1 || true
  until [ "$("$ADB" shell getprop sys.boot_completed 2>/dev/null | tr -d '\r')" = "1" ]; do
    sleep 15
  done
  # ANR-Dialoge ("System UI isn't responding") legen sich unter TCG
  # zuverlaessig ueber die App und nehmen ihr den Fokus -- `bild` verweigert
  # dann zu Recht den Abzug. Ausblenden ist ehrlicher, als vor jedem Abzug
  # "Wait" zu tippen: Der ANR ist eine Eigenschaft der Emulation, nicht der App.
  "$ADB" shell settings put global hide_error_dialogs 1 >/dev/null 2>&1 || true
  sag "Boot fertig nach $(( $(date +%s) - beginn )) s (Watchdog-Faktor $("$ADB" shell getprop ro.hw_timeout_multiplier 2>/dev/null | tr -d '\r'))"
}

legen() {
  local apk="$1" beginn; beginn=$(date +%s)
  "$ADB" install -r -t "$apk" >/dev/null
  sag "aufgespielt: $(basename "$apk") in $(( $(date +%s) - beginn )) s"
}

# EIN screencap PRUEFT NICHT, WAS ER FOTOGRAFIERT. Am 03.09.2026 lieferte der
# erste Lauf zwei byteweise gleich grosse Abzuege (52153 Bytes) -- beide
# zeigten den Dialog "System UI isn't responding". Unter TCG ist der Emulator
# so langsam, dass die SYSTEMOBERFLAECHE selbst in den ANR laeuft; die eigene
# App startete dahinter und war nie zu sehen. Ohne Nachsehen waeren daraus
# "zwei Bilder, kein Absturz" geworden -- dieselbe hohle Zahl wie die 176
# Anmeldeseiten nach O9c (F-P3-AQ).
#
# Deshalb: vor jedem Abzug nachfragen, WER den Fokus hat. `mFocusedApp` genuegt
# nicht -- es nannte im Fehlerfall bereits die richtige Activity, waehrend
# `mCurrentFocus` den ANR-Dialog nannte. Sichtbar ist, was in mCurrentFocus
# steht. Den Dialog raeumt ein Tipp auf "Wait" weg; danach steht die App.
PAKET="${PAKET:-org.genem.nadoku.pruef}"
bild() {
  mkdir -p "$ZIEL"
  local fokus
  fokus=$("$ADB" shell 'dumpsys window 2>/dev/null | grep mCurrentFocus' | tr -d '\r')
  case "$fokus" in
    *"$PAKET"*) : ;;
    *) sag "KEIN ABZUG fuer '$1' -- im Vordergrund steht:${fokus#*mCurrentFocus=}"
       return 1 ;;
  esac
  "$ADB" exec-out screencap -p > "$ZIEL/$1.png"
  sag "abgezogen: $ZIEL/$1.png ($(stat -c%s "$ZIEL/$1.png") Bytes)"
}

# NICHT `pkill -f qemu` BENUTZEN, um einen haengenden Emulator loszuwerden:
# Das Muster steht in der eigenen Befehlszeile der aufrufenden Shell, und die
# bringt sich damit selbst um -- der Emulator laeuft weiter. Am 03.09.2026
# zweimal passiert. Der Weg ist `adb emu kill`; hilft der nicht, die PID aus
# `ps -eo pid,args | grep -- "-avd"` heraussuchen und einzeln toeten.
aus() { "$ADB" emu kill >/dev/null 2>&1 || true; sag "beendet"; }

case "${1:-}" in
  aufbauen) aufbauen ;;
  start)    start "${2:-handy34}" ;;
  legen)    legen "$2" ;;
  bild)     bild "$2" ;;
  aus)      aus ;;
  *) sed -n '2,40p' "$0"; exit 1 ;;
esac
