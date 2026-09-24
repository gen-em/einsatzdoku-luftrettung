#!/bin/bash
# Die Arbeitsumgebung herstellen — eine Beschaffung, ein Nachweis.
#
# Aufruf:  bash tools/sandbox/aufbauen.sh [web|android|uhr|plattform|alles]
#          ohne Argument: web
#
# Anlass: Nr. 183 (WebKit fehlte still), 13.09.2026 (Container ohne MariaDB).
# Ersetzt `.claude/hooks/session-start.sh` und `tools/containeraufbau/`, die
# dasselbe mit zwei verschiedenen Listen taten (PK-02, F-PK-10).
#
# Die Ausbaustufen und die benannten Grenzen stehen in docs/Sandbox-Setup.md.
set -uo pipefail

WURZEL=${WURZEL:-$(cd "$(dirname "$0")/../.." && pwd)}
ANDROID_SDK="${ANDROID_HOME:-/opt/android-sdk}"
CMDTOOLS_URL="https://dl.google.com/android/repository/commandlinetools-linux-11076708_latest.zip"
PW=${PLAYWRIGHT_BROWSERS_PATH:-/opt/pw-browsers}
PW_MODUL=${PLAYWRIGHT_MODUL:-/opt/node22/lib/node_modules/playwright/index.mjs}
export DEBIAN_FRONTEND=noninteractive

melde() { printf '\033[1m==\033[0m %s\n' "$*" >&2; }
zeile() { printf '   %s\n' "$*" >&2; }

# ---------------------------------------------------------------- Bausteine

apt_holen() {   # apt_holen <paket…> — nur was fehlt, erst ohne, dann mit update
    local fehlend=() p
    for p in "$@"; do dpkg -s "$p" >/dev/null 2>&1 || fehlend+=("$p"); done
    [ ${#fehlend[@]} -eq 0 ] && return 0
    zeile "nachinstallieren: ${fehlend[*]}"
    if ! apt-get install -y -qq "${fehlend[@]}" >/dev/null 2>&1; then
        apt-get update -qq >/dev/null 2>&1 || true
        apt-get install -y -qq "${fehlend[@]}" >/dev/null 2>&1 || return 1
    fi
    dpkg --configure -a >/dev/null 2>&1 || true
    return 0
}

teil_web() {
    melde "Systempakete"
    # cmark-gfm seit BR-03: die Quelltextprüfung `handbuch` (F-BR-03), seit
    # BR-05 auch `bestand` — er liest Tabellen so, wie GitHub sie zeigt.
    apt_holen mariadb-server librsvg2-bin imagemagick socat unzip wget curl cmark-gfm \
        || zeile "ACHTUNG: apt-get fehlgeschlagen — Prüfmittel unvollständig"

    melde "Engine-Bibliotheken"
    # DIE ENGINES WERDEN NICHT NACHGELADEN. Sie liegen im Abbild
    # (/opt/pw-browsers: chromium-1194, firefox-1495, webkit-2215); ein
    # `playwright install` zöge eine zweite, abweichende Fassung daneben.
    # Was fehlt, sind Systembibliotheken — und zwar genau die vier, die
    # Playwright selbst nennt, wenn WebKit nicht startet. Gemessen am
    # 21.09.2026: ohne sie startet WebKit nicht, mit ihnen 3 von 3.
    apt_holen libenchant-2-2 libsecret-1-0 libwayland-server0 libmanette-0.2-0 \
        || zeile "ACHTUNG: Engine-Bibliotheken fehlgeschlagen"

    melde "Python-Pakete"
    # `python3` ist 3.11 (deadsnakes), apt legt nach 3.12 — deshalb pip.
    python3 -c 'import jsonschema' >/dev/null 2>&1 \
        || python3 -m pip install -q --break-system-packages jsonschema >/dev/null 2>&1 \
        || zeile "ACHTUNG: jsonschema fehlt weiter"
    # cryptography ist meist brauchbar; blind ersetzen macht mehr kaputt, als
    # es heilt. Deshalb Import prüfen und nur im Fehlerfall nachhelfen.
    python3 -c 'from cryptography.hazmat.primitives.ciphers.aead import AESGCM' >/dev/null 2>&1 \
        || python3 -m pip install -q --break-system-packages cffi >/dev/null 2>&1 \
        || zeile "ACHTUNG: cryptography trägt weiter nicht"
    # Die Gegenstellen der Versandprobe (FTP, FTPS, SFTP als Nachbau, RP-01).
    # pyOpenSSL ist KEIN Beiwerk: Ohne es fehlt pyftpdlib der TLS_FTPHandler,
    # FTPS startet nicht, und die Gegenstellen brechen alle drei ab.
    python3 -c 'import paramiko; from pyftpdlib.handlers import TLS_FTPHandler' >/dev/null 2>&1 \
        || python3 -m pip install -q --break-system-packages pyftpdlib paramiko pyopenssl >/dev/null 2>&1 \
        || zeile "ACHTUNG: Gegenstellen der Versandprobe (pyftpdlib, paramiko, pyopenssl) fehlen"
}

teil_android() {
    if [ -d "$ANDROID_SDK/platforms/android-36" ]; then
        melde "Android-SDK liegt bereits"; return 0
    fi
    melde "Android-SDK beschaffen (Plattform 36, Build-Tools 36.0.0)"
    mkdir -p "$ANDROID_SDK/cmdline-tools"
    curl -sS -L -o /tmp/cmdtools.zip "$CMDTOOLS_URL" || { zeile "Download fehlgeschlagen"; return 1; }
    unzip -q -o /tmp/cmdtools.zip -d "$ANDROID_SDK/cmdline-tools"
    rm -f /tmp/cmdtools.zip
    # Das Archiv entpackt nach cmdline-tools/; der sdkmanager verlangt eine
    # benannte Fassung darunter, sonst findet er das SDK nicht.
    [ -d "$ANDROID_SDK/cmdline-tools/cmdline-tools" ] \
        && mv "$ANDROID_SDK/cmdline-tools/cmdline-tools" "$ANDROID_SDK/cmdline-tools/latest"
    local sdkm="$ANDROID_SDK/cmdline-tools/latest/bin/sdkmanager"
    yes | "$sdkm" --licenses >/dev/null 2>&1 || true
    "$sdkm" "platform-tools" "platforms;android-36" "build-tools;36.0.0" >/dev/null 2>&1
}

teil_uhr() {
    melde "Uhr-Prüfstand"
    if [ -z "${CIQ_GERAETE_URL:-}" ]; then
        zeile "CIQ_GERAETE_URL fehlt — ohne sie gibt es keine Gerätedateien."; return 1
    fi
    # Der Prüfstand holt sein SDK selbst; hier wird er nur angestoßen.
    #
    # BASH, NICHT SH (21.09.2026). `pruefstand.sh` ist `#!/usr/bin/env bash`
    # und setzt `-o pipefail`; `sh` ist in diesem Abbild dash und bricht in
    # Zeile 11 mit „Illegal option -o pipefail" ab. Derselbe Fehler stand in
    # `tools/pruefstand/pruefen.sh` und ist dort in PK-03 behoben worden —
    # hier blieb er stehen und fiel nicht auf, weil der Rückgabewert dieser
    # Funktion verworfen wurde (siehe unten, „Lauf").
    CIQ_ZIELE=${CIQ_ZIELE:-alle} bash "$WURZEL/tools/uhr-pruefstand/pruefstand.sh" aufbau
}

teil_plattform() {
    melde "Modul plattform (PHP 8.3, MariaDB 10.6, MySQL 8.0, MySQL 8.4.0)"
    # Docker Hub drosselt anonyme Abrufe (429 nach rund acht Abrufen), die
    # Ubuntu-Quellen nicht — deshalb Docker nur dort, wo es keinen anderen
    # Weg gibt: MySQL 8.4.0 und PHP 8.3.
    bash "$(dirname "$0")/plattform.sh" || return 1
}

# ------------------------------------------------------------- Umgebung

WERTE=(NADOKU_STAGING_URL NADOKU_STAGING_KONTO NADOKU_STAGING_PASS
       NADOKU_STAGING_JOBS_TOKEN _MAIL_URL _MAIL_USER _MAIL_PASS
       CIQ_GERAETE_URL)

umgebungswerte() {
    # NUR NAME UND LÄNGE, NIE DER WERT. Am 21.09.2026 waren zwei Werte falsch
    # (stillgelegte Adresse, am `#` abgeschnittenes Passwort) und kein
    # Werkzeug hat es gemerkt — eine Länge hätte es gezeigt.
    local n v da=0
    melde "Umgebungswerte"
    for n in "${WERTE[@]}"; do
        v="${!n:-}"
        if [ -n "$v" ]; then zeile "ok    $(printf '%-26s' "$n") ${#v} Zeichen"; da=$((da+1))
        else zeile "FEHLT $n"; fi
    done
    zeile "$da von ${#WERTE[@]} gesetzt"
    [ "$da" -eq "${#WERTE[@]}" ]
}

# --------------------------------------------------------------- Nachweis

fehler=0
pruefe() {  # pruefe <name> <befehl> — meldet, was gefunden wurde
    if eval "$2" >/dev/null 2>&1; then zeile "ok    $1"
    else zeile "FEHLT $1"; fehler=$((fehler+1)); fi
}

engines() {
    # EINZELN, nicht als eine Zahl: „3 Engines da" sagt nicht, welche fehlt —
    # und es fehlt immer nur eine. Scheitert eine, wird Playwrights eigene
    # Meldung ausgegeben; sie nennt die Paketnamen und altert nicht.
    local aus
    aus=$(PLAYWRIGHT_BROWSERS_PATH="$PW" PW_MODUL="$PW_MODUL" node --input-type=module -e '
      const M = await import(process.env.PW_MODUL.startsWith("/")
        ? "file://" + process.env.PW_MODUL : process.env.PW_MODUL);
      const pw = M.default ?? M;
      let fehlt = 0;
      for (const n of ["chromium", "firefox", "webkit"]) {
        try { const b = await pw[n].launch();
              console.log("ok    " + n.padEnd(9) + " " + b.version()); await b.close(); }
        catch (e) { fehlt++;
              console.log("FEHLT " + n.padEnd(9) + " " + String(e).replace(/\s+/g, " ").slice(0, 160)); }
      }
      process.exit(fehlt ? 1 : 0);
    ' 2>&1) || fehler=$((fehler+1))
    printf '%s\n' "$aus" | while IFS= read -r z; do zeile "$z"; done
}

nachweis() {
    melde "Nachweis"
    pruefe "mariadbd       $(mariadbd --version 2>/dev/null | grep -oE '1[0-9.]+' | head -1)" "command -v mariadbd"
    pruefe "convert"       "command -v convert"
    pruefe "compare"       "command -v compare"
    pruefe "rsvg-convert"  "command -v rsvg-convert"
    pruefe "php            $(php -r 'echo PHP_VERSION;' 2>/dev/null)" "command -v php"
    pruefe "node           $(node -v 2>/dev/null)" "command -v node"
    pruefe "socat"         "command -v socat"
    pruefe "cmark-gfm"     "command -v cmark-gfm"
    pruefe "python-cryptography" \
      "python3 -c 'from cryptography.hazmat.primitives.ciphers.aead import AESGCM'"
    pruefe "python-jsonschema" "python3 -c 'import jsonschema'"
    pruefe "python-requests"   "python3 -c 'import requests'"
    engines
    # STUFENABHÄNGIG, damit der Nachweis einen Gegenstand hat. Bis zum
    # 21.09.2026 prüfte er ausschließlich die Stücke der Stufe `web` — ein
    # Lauf `uhr` meldete deshalb „Arbeitsumgebung vollständig", ohne ein
    # einziges Uhr-Stück angesehen zu haben (Grundsatz 7).
    if [ -n "${STUFE_ANDROID:-}" ]; then
        pruefe "android-sdk     Plattform 36" "[ -d \"$ANDROID_SDK/platforms/android-36\" ]"
        pruefe "android-sdk     Build-Tools 36.0.0" "[ -d \"$ANDROID_SDK/build-tools/36.0.0\" ]"
    fi
    if [ -n "${STUFE_UHR:-}" ]; then
        pruefe "ciq-sdk         monkeyc" "[ -x \"$CIQ_BASIS_PFAD/sdk/bin/monkeyc\" ]"
        pruefe "ciq-geraete     Devices" "[ -d \"$HOME/.Garmin/ConnectIQ/Devices\" ]"
    fi
    umgebungswerte || fehler=$((fehler+1))
}

# ------------------------------------------------------------------ Lauf

# JEDER TEIL ZÄHLT SEINEN FEHLSCHLAG (21.09.2026). Bis dahin standen die
# Aufrufe nackt da, und die Schale verwarf ihren Rückgabewert: Der
# Uhr-Prüfstand konnte abbrechen, und der Lauf meldete trotzdem 0. Gemessen
# am Lauf `aufbauen.sh uhr` vom 21.09.2026 — „Illegal option -o pipefail",
# danach „Arbeitsumgebung vollständig", Rückgabewert 0.
CIQ_BASIS_PFAD="${CIQ_BASIS:-$HOME/.ciq-pruefstand}"
[ $# -eq 0 ] && set -- web
for stufe in "$@"; do
    case "$stufe" in
        web)       teil_web || fehler=$((fehler+1)) ;;
        android)   STUFE_ANDROID=1; teil_web || fehler=$((fehler+1)); teil_android || fehler=$((fehler+1)) ;;
        uhr)       STUFE_UHR=1; teil_web || fehler=$((fehler+1)); teil_uhr || fehler=$((fehler+1)) ;;
        plattform) teil_plattform || fehler=$((fehler+1)) ;;
        alles)     STUFE_ANDROID=1; STUFE_UHR=1
                   teil_web || fehler=$((fehler+1)); teil_android || fehler=$((fehler+1))
                   teil_uhr || fehler=$((fehler+1)); teil_plattform || fehler=$((fehler+1)) ;;
        *) echo "Unbekannte Stufe: $stufe (web, android, uhr, plattform, alles)" >&2; exit 2 ;;
    esac
done

nachweis

if [ "$fehler" -gt 0 ]; then
    melde "$fehler Stück fehlen — die Arbeitsumgebung ist unvollständig."
    zeile "Das ist ein Befund mit Zahl, kein stiller Ausfall: im Prüfdokument nennen."
    exit 1
fi
melde "Arbeitsumgebung vollständig. Hochfahren: bash tools/sandbox/hochfahren.sh"
exit 0
