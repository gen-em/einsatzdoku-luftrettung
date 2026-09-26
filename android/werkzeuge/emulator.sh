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
# UND DIE VIERTE: target=android-0. `avdmanager` aus cmdline-tools 12.0 kann
# Abbilder mit API "37.0" nicht lesen und schreibt das in die AVD; gfxstream
# wird dann falsch eingerichtet, und der Gast bricht mit "Assertion failed:
# !rcEnc->featureInfo()->hasReadColorBufferDma" ab -- auf der Uhr stirbt
# system_server im Minutentakt. Am 25.09.2026 neun Anlaeufe lang gesucht
# (Konzept AR, F-AR-18); dieselbe Ursache hatte der SurfaceFlinger-Abbruch
# des Handys (Nr. 337). `aufbauen` und `start` berichtigen den Eintrag;
# `tools/sandbox/aufbauen.sh android` holt seither cmdline-tools 23.0.
#
# AUFRUFE
#   emulator.sh aufbauen        Emulator, Abbilder, AVDs, Debug-Ramdisk anlegen
#                               (einmal je Container; ueblicher Weg:
#                               `tools/sandbox/aufbauen.sh emulator`)
#   emulator.sh start [AVD]     starten und auf sys.boot_completed warten
#                               (AVD: handy37 oder uhr37, Vorgabe handy37)
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
# WELCHES ABBILD. API 37, weil die Apps seit 0.16.0 dagegen bauen und
# `targetSdk` 37 setzen (Konzept AR, E-AR-08, E-AR-14). Bis dahin stand hier
# `android-34;default` -- `default` war schneller, weil der Erstboot von
# `google_apis` die Play-Dienste vorübersetzt; fuer API 37 gibt es aber nur
# `google_apis` (Boot 1 420 s gegen 502-715 s). Die Uhr gibt es ab API 36 nur
# als `android-wear-signed`, einen `user`-Build ohne `adb root` -- `start`
# setzt den Watchdog-Faktor dort ueber die Debug-Ramdisk (siehe unten).
# Wer eine andere Stufe braucht, setzt ABBILD / ABBILD_UHR von aussen.
ABBILD="${ABBILD:-system-images;android-37.0;google_apis;x86_64}"
ABBILD_UHR="${ABBILD_UHR:-system-images;android-37.0;android-wear-signed;x86_64}"
# Watchdog-Faktor. 10 genuegte auf den userdebug-Abbildern mit API 34; die Uhr
# auf API 37 lief mit 50, dem Wert, den Cuttlefish fuer Laeufe ohne KVM setzt.
FAKTOR="${FAKTOR:-50}"

sag() { printf '\033[1m%s\033[0m\n' "$*"; }

aufbauen() {
  # libpulse fehlt in schlanken Containern; ohne sie startet nicht einmal
  # `emulator -version` -- die QEMU-Binaerdatei bindet sie hart.
  if ! ldconfig -p | grep -q libpulse.so.0; then
    sag "libpulse0 fehlt, wird nachinstalliert"
    apt-get install -y libpulse0 >/dev/null
  fi
  local sdkm="$SDK/cmdline-tools/latest/bin/sdkmanager"
  yes | "$sdkm" --licenses >/dev/null 2>&1 || true
  sag "Emulator und Abbilder laden (mehrere GB, dauert)"
  # EINZELN. `sdkmanager` ist seit cmdline-tools 23.0 eine Huelle um die
  # "Android CLI" und scheitert, wenn mehrere Pakete in einem Aufruf stehen
  # ("Package path is not valid", gemessen am 25.09.2026).
  local paket
  for paket in emulator "$ABBILD" "$ABBILD_UHR"; do
    [ -f "$SDK/$(echo "$paket" | tr ';' '/')/source.properties" ] && continue
    yes | "$sdkm" "$paket" >/dev/null 2>&1 || true
    [ -f "$SDK/$(echo "$paket" | tr ';' '/')/source.properties" ] \
      || { sag "nicht geladen: $paket"; return 1; }
  done
  mkdir -p "$ANDROID_AVD_HOME"
  avd_anlegen handy37 "$ABBILD" pixel_5
  avd_anlegen uhr37 "$ABBILD_UHR" wearos_small_round
  sag "AVDs angelegt: $("$EMU" -list-avds | tr '\n' ' ')"
}

avd_anlegen() {  # avd_anlegen NAME ABBILD GERAET
  echo no | "$SDK"/cmdline-tools/latest/bin/avdmanager create avd \
      -n "$1" -k "$2" -d "$3" --force >/dev/null
  target_berichtigen "$1"
  if ist_user "$1"; then debug_ramdisk "$1"; fi
}

abbild_von() {   # Verzeichnis des Abbilds einer AVD, aus ihrer config.ini
  # `avdmanager` schreibt `image.sysdir.1=...`, der Emulator schreibt die Datei
  # beim ersten Start um zu `image.sysdir.1 = ...` -- beide Formen lesen. Die
  # erste Fassung las nur die eine; ein ZWEITER Start der Uhr haette den
  # user-Build nicht mehr erkannt und ohne Debug-Ramdisk gebootet (25.09.2026).
  local d
  d=$(sed -n 's/^image\.sysdir\.1[[:space:]]*=[[:space:]]*//p' "$ANDROID_AVD_HOME/$1.avd/config.ini" | tr -d ' \r')
  [ -n "$d" ] || { sag "$1: kein image.sysdir.1 in config.ini"; return 1; }
  echo "$SDK/$d"
}

ist_user() { grep -qx 'ro.build.type=user' "$(abbild_von "$1")/build.prop"; }

target_berichtigen() {
  # target=android-0 (siehe Kopf). Die richtige Stufe steht im Pfad des
  # Abbilds (`system-images/android-37.0/...`); cmdline-tools 23.0 schreibt
  # sie selbst -- die Berichtigung bleibt fuer AVDs aus einem aelteren Werkzeug.
  local ini="$ANDROID_AVD_HOME/$1.ini" stufe
  stufe=$(abbild_von "$1" | grep -oE 'system-images/android-[0-9.a-z-]+' | cut -d/ -f2)
  if [ -n "$stufe" ] && ! grep -qx "target=$stufe" "$ini"; then
    sag "$1: $(grep '^target=' "$ini") berichtigt zu target=$stufe"
    sed -i "s/^target=.*/target=$stufe/" "$ini"
  fi
}

# DIE DEBUG-RAMDISK (Konzept AR, F-AR-18). Ein `user`-Build laesst weder
# `adb root` noch `setprop ro.*` zu, also keinen Watchdog-Faktor -- und ohne
# den bootet unter TCG nichts. AOSP sieht fuer Pruefzwecke einen Weg vor: Liegt
# `/force_debuggable` in der Ramdisk, laedt init zuletzt `/adb_debug.prop` --
# aber NUR auf einem "entsperrten" Geraet, also mit
# androidboot.verifiedbootstate=orange (das `start` uebergibt; Emulator
# 37.1.11 tut es nicht von selbst). System- und Vendor-Abbild bleiben
# unberuehrt, keine Signatur wird veraendert. Darin stehen der Faktor und
# `persist.sys.usb.config=adb` -- sonst startet adbd im user-Build gar nicht.
#
# ZWEI FALLEN, BEIDE AM 25.09.2026 GEMESSEN:
# 1. Die ramdisk.img besteht aus MEHREREN aneinandergehaengten cpio-Archiven.
#    `cpio -i` liest nur das erste; wer auspackt und neu packt, verliert den
#    Vendor-Teil samt fstab -- Kernel-Panik, 34 Neustarts. Deshalb wird der
#    ganze entpackte Strom behalten und ein Archiv angehaengt.
# 2. Die Dateien muessen AUCH unter first_stage_ramdisk/ liegen: init wechselt
#    beim normalen Boot dorthin, bevor es nach force_debuggable sieht.
debug_ramdisk() {
  local rd; rd="$(abbild_von "$1")/ramdisk.img"
  local ziel="$ANDROID_AVD_HOME/$1.avd/ramdisk-debug.img" t auspacken packen
  case "$(head -c 4 "$rd" | od -An -tx1 | tr -d ' \n')" in
    02214c18) auspacken="lz4 -dc"; packen="lz4 -l -12 -c" ;;
    1f8b*)    auspacken="gzip -dc"; packen="gzip -9 -c" ;;
    *) sag "$1: Kompression von $rd unbekannt -- keine Debug-Ramdisk"; return 1 ;;
  esac
  t=$(mktemp -d)
  mkdir -p "$t/first_stage_ramdisk"
  : > "$t/force_debuggable"
  printf 'ro.hw_timeout_multiplier=%s\npersist.sys.usb.config=adb\n' "$FAKTOR" > "$t/adb_debug.prop"
  cp "$t/force_debuggable" "$t/adb_debug.prop" "$t/first_stage_ramdisk/"
  chmod 644 "$t/force_debuggable" "$t/adb_debug.prop" "$t"/first_stage_ramdisk/*
  { $auspacken "$rd"
    ( cd "$t" && printf '%s\n' force_debuggable adb_debug.prop \
        first_stage_ramdisk/force_debuggable first_stage_ramdisk/adb_debug.prop \
        | cpio -o -H newc -R 0:0 --quiet )
  } | $packen > "$ziel"
  rm -rf "$t"
  sag "$1: Debug-Ramdisk angelegt (Faktor $FAKTOR): $ziel"
}

start() {
  local avd="${1:-handy37}" beginn user="" frei; beginn=$(date +%s)
  local zusatz=()
  target_berichtigen "$avd"
  if ist_user "$avd"; then
    user=1
    local rd="$ANDROID_AVD_HOME/$avd.avd/ramdisk-debug.img"
    [ -s "$rd" ] || { sag "$avd ist ein user-Build, die Debug-Ramdisk fehlt -- erst: emulator.sh aufbauen"; return 1; }
    zusatz=(-ramdisk "$rd" -append-userspace-opt androidboot.verifiedbootstate=orange)
  fi
  # PLATZ (F-AR-18): Fuer die Datenpartition verlangt der Emulator auf den
  # Abbildern mit API 37 7 373 MB frei und bricht sonst SOFORT ab ("Not
  # enough space to create userdata partition") -- ein Warten auf adb liefe
  # dann ins Leere.
  frei=$(df -Pm "$ANDROID_AVD_HOME" | awk 'NR==2 {print $4}')
  if [ "$frei" -lt 7400 ]; then
    sag "WARNUNG: nur $frei MB frei -- API 37 verlangt 7 373 MB fuer die Datenpartition"
  fi
  # SPEICHER (Android 0.16.0, Konzept AR): Ein Gradle-Daemon belegt nach
  # einem Bau rund 5 GB, der Emulator 6 GB -- in 15 GB ohne Swap blieb am
  # 24.09.2026 der ganze Container stehen (Last 60, `ps` und `uptime` hingen).
  # Vorher `./gradlew --stop`.
  if pgrep -f org.gradle.launcher.daemon.bootstrap.GradleDaemon >/dev/null 2>&1; then
    sag "WARNUNG: Ein Gradle-Daemon laeuft -- erst ./gradlew --stop (Speicher)"
  fi
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
      -cores 4 "${zusatz[@]}" \
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
  # Im user-Build steht der Faktor schon ab init in der Debug-Ramdisk; dort
  # gibt es weder `adb root` noch einen Neustart des Frameworks.
  until [ "$("$ADB" get-state 2>/dev/null | tr -d '\r')" = "device" ]; do
    # Ueber die Befehlszeile der AVD, nicht ueber `qemu-system`: Das
    # Startprogramm legt den QEMU-Prozess erst nach Sekunden an, und die
    # erste Fassung dieser Pruefung meldete am 25.09.2026 nach 9 s
    # "beendet", waehrend der Emulator anlief. `pgrep` schliesst sich selbst
    # aus; die eigene Befehlszeile (`start handy37`) enthaelt kein `-avd`.
    pgrep -f -- "-avd $avd( |\$)" >/dev/null || { sag "Emulator beendet -- Protokoll lesen"; return 1; }
    sleep 10
  done
  if [ -z "$user" ]; then
    sag "adbd da nach $(( $(date +%s) - beginn )) s -- Watchdog-Faktor setzen, Framework neu starten"
    "$ADB" root >/dev/null 2>&1 || true; sleep 5
    "$ADB" shell setprop ro.hw_timeout_multiplier "$FAKTOR" >/dev/null 2>&1 || true
    "$ADB" shell "stop; sleep 3; start" >/dev/null 2>&1 || true
  else
    sag "adbd da nach $(( $(date +%s) - beginn )) s (user-Build, Faktor aus der Debug-Ramdisk)"
  fi
  until [ "$("$ADB" shell getprop sys.boot_completed 2>/dev/null | tr -d '\r')" = "1" ]; do
    sleep 15
  done
  # Nach einem Neustart der Oberflaeche sagt sys.boot_completed weiter 1,
  # der Nutzerspeicher ist aber noch gesperrt -- `am start` meldet dann
  # "Activity class ... does not exist" (Konzept AR, AR-05).
  until [ "$("$ADB" shell getprop sys.user.0.ce_available 2>/dev/null | tr -d '\r')" = "true" ]; do
    sleep 10
  done
  # ANR-Dialoge ("System UI isn't responding") legen sich unter TCG
  # zuverlaessig ueber die App und nehmen ihr den Fokus -- `bild` verweigert
  # dann zu Recht den Abzug. Ausblenden ist ehrlicher, als vor jedem Abzug
  # "Wait" zu tippen: Der ANR ist eine Eigenschaft der Emulation, nicht der App.
  "$ADB" shell settings put global hide_error_dialogs 1 >/dev/null 2>&1 || true
  # KEINE DREI-TASTEN-NAVIGATION MEHR (Backlog Nr. 337, erledigt 25.09.2026).
  # Vom 24. bis 25.09.2026 schaltete `start` hier auf Drei-Tasten um, weil
  # SurfaceFlinger auf API 37 mit `!hasReadColorBufferDma` abbrach. Die
  # Ursache war target=android-0 (siehe Kopf); mit richtigem target lief das
  # Handy 15 Minuten mit Gestennavigation ohne einen Eintrag im Absturzpuffer.
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
  # KEIN PNG? Dann ist das ein Befund, kein Anlass zum Ausweichen. Bis zum
  # 25.09.2026 zog `bild` hier von der Wirtsseite ab, weil `screencap` auf
  # API 37 an derselben Assertion scheiterte wie SurfaceFlinger (Nr. 337,
  # target=android-0). Ein stilles Ausweichen haette den naechsten solchen
  # Fehler verdeckt.
  if ! head -c 8 "$ZIEL/$1.png" | grep -q PNG; then
    sag "KEIN PNG fuer '$1': $(head -c 120 "$ZIEL/$1.png" | tr -d '\0')"
    rm -f "$ZIEL/$1.png"; return 1
  fi
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
  start)    start "${2:-handy37}" ;;
  legen)    legen "$2" ;;
  bild)     bild "$2" ;;
  aus)      aus ;;
  *) sed -n '2,/^set -eu/p' "$0" | sed '$d'; exit 1 ;;
esac
