#!/bin/sh
# Systemvoraussetzungen einer Wegwerf-Umgebung nachziehen.
#
# WOZU. Der Container von Claude Code on the web bringt PHP, Node, Python,
# Java und drei Playwright-Engines mit — aber weder einen Datenbankserver
# noch das Android-SDK, obwohl CLAUDE.md 6 und android/LIESMICH.md beides
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
#   WebKit-Bibliotheken  tools/motor.mjs -> Bilderlauf, Klickprobe,
#                        Stilvergleich mit `--motor webkit`
#   Android-SDK 36       ./gradlew build
#   python3-cffi         cryptography -> tools/referenzdatensatz/vergleich
#   jsonschema          tools/referenzdatensatz/quelldaten/pruefen.py
#
# DIE WEBKIT-BIBLIOTHEKEN SIND AM 14.09.2026 DAZUGEKOMMEN, und zwar nicht,
# weil eine Engine fehlte, sondern weil eine vorhandene nicht STARTETE. Seit
# AP3b (Backlog Nr. 183) fahren drei Pruefmittel wahlweise Chromium, Firefox
# und WebKit ueber `tools/motor.mjs`. Alle drei Engines liegen im Abbild
# unter /opt/pw-browsers; gemessen am 14.09.2026: Chromium 141.0.7390.37 und
# Firefox 142.0.1 starten sofort, WebKit 26.0 bricht ab mit "Host system is
# missing dependencies to run browsers" und nennt vier Pakete.
#
# DAS IST DIE GEFAEHRLICHE SORTE LUECKE. Ein Dreimotorenlauf ohne sie ist ein
# Zweimotorenlauf — oder er bricht in der Mitte ab, nachdem der Bilderlauf
# zwei Engines lang gerechnet hat. Beides meldet am Ende keine Null, sondern
# gar nichts (CLAUDE.md 6: "Eine gruene Zahl ist erst dann ein Beleg, wenn
# sie das Gemessene benennt").
#
# WAS ES NICHT TUT: den Uhr-Pruefstand aufbauen. Der holt sein SDK selbst
# (tools/uhr-pruefstand/pruefstand.sh aufbau) und braucht dafuer die
# Geraetedateien aus CIQ_GERAETE_URL, die nicht im Repositorium steht.
#
# EBENSO WENIG holt es die Engines selbst. `playwright install` ist
# ausdruecklich NICHT der Weg: Die Engines liegen bereits im Abbild, und ein
# Nachladen zoege eine zweite, abweichende Fassung daneben. Fehlt eine
# Engine wirklich, ist das ein Befund ueber das Abbild und keine Aufgabe
# dieses Skripts.
#
# Aufruf:  sh tools/containeraufbau/aufbau.sh [teil …]
#          Teile: pakete, browser, datenbank, android, python, alles (Vorgabe)
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

# Die Laufzeitabhaengigkeiten der Playwright-Engines — und die Gegenprobe,
# dass alle drei WIRKLICH starten.
#
# WARUM DIE GEGENPROBE UND NICHT NUR DAS INSTALLIEREN. Ein `apt-get`, das
# durchlaeuft, belegt nicht, dass WebKit startet: Die Paketliste unten ist
# die, die Playwright 1.56 nennt, und die naechste Fassung kann eine andere
# nennen. Der Lauf sagt deshalb, was er GEMESSEN hat — drei Namen mit drei
# Fassungsnummern —, und bricht ab, wenn eine Engine fehlt. Eine Engine, die
# hier stillschweigend fehlt, meldet spaeter im Bilderlauf keine Null,
# sondern gar nichts.
browser() {
    melde "Playwright-Engines"
    export DEBIAN_FRONTEND=noninteractive
    # Chromium und Firefox starten im Abbild ohne Zutun; WebKit nicht. Die
    # vier Namen stammen aus der Meldung von Playwright selbst
    # ("Alternatively, use apt:"), gemessen am 14.09.2026.
    apt-get install -y -qq libenchant-2-2 libsecret-1-0 libwayland-server0 \
                           libmanette-0.2-0 >/dev/null 2>&1 \
        || { echo "apt-get fehlgeschlagen (WebKit-Bibliotheken)"; exit 1; }
    dpkg --configure -a >/dev/null 2>&1 || true

    # Gegenprobe: jede der drei Engines einmal starten und ihre Fassung
    # nennen. Derselbe Modulpfad wie in den Pruefmitteln
    # (tools/screenshots/aufnehmen.mjs, tools/klickprobe/probe.mjs).
    node --input-type=module -e '
      const MODUL = process.env.PLAYWRIGHT_MODUL
        || "/opt/node22/lib/node_modules/playwright/index.mjs";
      const M = await import(MODUL.startsWith("/") ? "file://" + MODUL : MODUL);
      const pw = M.default ?? M;
      let fehlt = 0;
      for (const name of ["chromium", "firefox", "webkit"]) {
        try {
          const b = await pw[name].launch();
          console.log("   " + name.padEnd(9) + " " + b.version());
          await b.close();
        } catch (e) {
          fehlt++;
          console.log("   " + name.padEnd(9) + " FEHLT: "
            + String(e).split("\n")[0].slice(0, 100));
        }
      }
      if (fehlt) {
        console.log("   " + fehlt + " von 3 Engines starten nicht — "
          + "tools/motor.mjs kann sie nicht fahren.");
        process.exit(1);
      }
      console.log("   3 von 3 Engines starten.");
    ' || { echo "Engine-Gegenprobe fehlgeschlagen"; exit 1; }
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

    # jsonschema fuer quelldaten/pruefen.py des Referenzdatensatzes. Es fehlt im
    # Abbild, und der Lauf bricht dann mit ModuleNotFoundError ab -- am
    # 07.09.2026 (S9/AP4) beim ersten Aufbau nach der Erweiterung der
    # Referenz-Stammdaten aufgefallen. Anders als bei cryptography sagt der
    # Fehler hier immerhin, was fehlt.
    python3 -c 'import jsonschema' 2>/dev/null \
        || pip3 install --quiet --break-system-packages jsonschema
    python3 -c 'import jsonschema; print("   jsonschema", jsonschema.__version__)'
}

alles() { pakete; browser; datenbank; android; python_teile;
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
        browser)   browser ;;
        datenbank) datenbank ;;
        android)   android ;;
        python)    python_teile ;;
        alles)     alles ;;
        *) echo "Unbekannter Teil: $teil (pakete, browser, datenbank, android, python, alles)"; exit 1 ;;
    esac
done
