#!/bin/bash
# Die örtliche Installation hochfahren — ein Befehl, ein Rückgabewert.
#
# Aufruf:  sh tools/sandbox/hochfahren.sh [--neu] [--php 8.3]
#            --neu      richtet neu ein — LÖSCHT die Datenbank und config.php
#            --php 8.3  fährt die Anwendung im Abbild nadoku-php83 statt
#                       unter dem PHP des Containers (8.4)
#
# Anlass: Nr. 267 — der Export scheiterte auf der Datenbank von Staging und
# lief auf der von Produktiv. Wer eine Plattformfrage untersucht, will die
# Anwendung in Sekunden auf einer anderen Fassung stehen haben.
#
# Ohne --neu wird NICHT neu eingerichtet: Steht eine Installation, wird sie
# nur gestartet. `lokal_einrichten.sh` löscht die Datenbank, und das soll
# niemand aus Versehen auslösen.
set -uo pipefail

WURZEL=${WURZEL:-$(cd "$(dirname "$0")/../.." && pwd)}
EIN="$WURZEL/tools/referenzdatensatz/einspielen"
ADRESSE=${ADRESSE:-127.0.0.1:8080}
TLS_PORT=${TLS_PORT:-8443}
NEU=0; PHPFASSUNG=

melde() { printf '\033[1m==\033[0m %s\n' "$*" >&2; }
zeile() { printf '   %s\n' "$*" >&2; }

while [ $# -gt 0 ]; do
    case "$1" in
        --neu) NEU=1; shift ;;
        --php) PHPFASSUNG=${2:-}; shift 2 ;;
        *) echo "Unbekannt: $1 (--neu, --php 8.3)" >&2; exit 2 ;;
    esac
done

# ---- 1. Datenbank ---------------------------------------------------------
melde "MariaDB"
mkdir -p /var/run/mysqld /var/log/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql /var/log/mysql 2>/dev/null || true
if ! mariadb-admin ping >/dev/null 2>&1; then
    mariadbd-safe >/var/log/mysql/safe.log 2>&1 &
    mariadb-admin ping --wait=30 >/dev/null 2>&1 \
        || { zeile "MariaDB kam nicht hoch (siehe /var/log/mysql/safe.log)"; exit 1; }
fi
zeile "$(mariadb -N -e 'SELECT VERSION();' 2>/dev/null)"

# ---- 2. Anwendung ---------------------------------------------------------
if [ "$NEU" = 1 ] || [ ! -f "$WURZEL/server/config.php" ]; then
    [ "$NEU" = 1 ] && melde "Neu einrichten (löscht Datenbank und config.php)" \
                   || melde "Keine Installation gefunden — einrichten"
    sh "$EIN/lokal_einrichten.sh" >&2 || { zeile "Einrichten gescheitert."; exit 1; }
else
    melde "Installation steht — starten"
    sh "$EIN/lokal_starten.sh" >&2 || { zeile "Starten gescheitert."; exit 1; }
fi

# ---- 3. Andere PHP-Fassung ------------------------------------------------
if [ -n "$PHPFASSUNG" ]; then
    [ "$PHPFASSUNG" = "8.3" ] || { zeile "Nur --php 8.3 ist vorgesehen."; exit 2; }
    melde "Anwendung unter PHP 8.3 (Abbild nadoku-php83)"
    docker image inspect nadoku-php83 >/dev/null 2>&1 \
        || { zeile "Abbild fehlt — erst: sh tools/sandbox/plattform.sh php83"; exit 1; }
    # Netzwerk des Wirts, damit die Anwendung dieselbe Datenbank und denselben
    # TLS-Vorbau erreicht wie unter 8.4. Der alte Server wird vorher beendet.
    pkill -f "php -S $ADRESSE" 2>/dev/null || true
    sleep 1
    docker rm -f nadoku-php83-lauf >/dev/null 2>&1 || true
    docker run -d --rm --name nadoku-php83-lauf --network host \
        -v "$WURZEL/server:/app" -w /app nadoku-php83 \
        php -S "$ADRESSE" -t /app >/dev/null \
        || { zeile "Behälter startete nicht."; exit 1; }
    sleep 3
    zeile "PHP im Behälter: $(docker exec nadoku-php83-lauf php -r 'echo PHP_VERSION;' 2>/dev/null)"
fi

# ---- 4. Nachweis ----------------------------------------------------------
# EINE ZAHL, DIE DEN GEGENSTAND NENNT: nicht „läuft", sondern welcher Code
# von welcher Adresse und welche Fassung die Anlage meldet.
melde "Nachweis"
code=$(curl -s --noproxy '*' -o /tmp/hochfahren-login.html -w '%{http_code}' \
       "https://127.0.0.1:$TLS_PORT/login.php" 2>/dev/null)
fassung=$(grep -oE 'v?[0-9]+\.[0-9]+\.[0-9]+' /tmp/hochfahren-login.html 2>/dev/null | tail -1)
zeile "https://127.0.0.1:$TLS_PORT/login.php  HTTP $code   Fassung ${fassung:-unbekannt}"
rm -f /tmp/hochfahren-login.html

if [ "$code" != "200" ]; then
    zeile "Die Anmeldeseite antwortet nicht mit 200 — das ist ein Befund mit Zahl."
    exit 1
fi
melde "Die örtliche Installation steht."
exit 0
