#!/bin/bash
# Modul `plattform` — die Fassungen, auf denen die Anlagen wirklich laufen.
#
# Aufruf:  bash tools/sandbox/plattform.sh [php83|mariadb106|mysql80|mysql84|alles]
#          bash tools/sandbox/plattform.sh --aus      (Behälter wieder weg)
#
# Anlass: Nr. 267. Am 21.09.2026 scheiterte der Export auf Staging und lief
# auf Produktiv — Staging hat MySQL 8.4.10, Produktiv MariaDB 10.11.14, und
# keine der beiden Anlagen hatte je gesagt, dass sie verschieden sind.
set -uo pipefail

CA_QUELLE=${CA_QUELLE:-/usr/local/share/ca-certificates}
BAUPLATZ=${BAUPLATZ:-/tmp/nadoku-php83-bau}
melde() { printf '\033[1m==\033[0m %s\n' "$*" >&2; }
zeile() { printf '   %s\n' "$*" >&2; }
fehler=0

# Name  Abbild        Port  Klient   Umgebungsvorsatz
DBS=(
  "mariadb106|mariadb:10.6|3310|mariadb|MARIADB"
  "mysql80|mysql:8.0|3307|mysql|MYSQL"
  "mysql84|mysql:8.4.0|3308|mysql|MYSQL"
)

docker_da() {
    docker info >/dev/null 2>&1 && return 0
    zeile "Der Docker-Dienst läuft nicht. Er startet nicht von selbst:"
    zeile "   dockerd >/tmp/dockerd.log 2>&1 &"
    return 1
}

php83() {
    melde "PHP 8.3.33 als Abbild"
    if docker image inspect nadoku-php83 >/dev/null 2>&1; then
        zeile "ok    liegt bereits: PHP $(docker run --rm nadoku-php83 php -r 'echo PHP_VERSION;' 2>/dev/null)"
        return 0
    fi
    # DIE ZERTIFIZIERUNGSSTELLEN DES WIRTS MÜSSEN MIT HINEIN. Nur HTTPS kommt
    # hinaus, und im Behälter scheitert es ohne sie mit "certificate verify
    # failed". Gemessen: die Stelle des Agent-Proxys ALLEIN genügt nicht —
    # der Verkehr des Behälters läuft über das Egress-Gateway.
    rm -rf "$BAUPLATZ"; mkdir -p "$BAUPLATZ"
    # `nadoku-pruefstand.crt` bleibt draußen: Die erzeugt `lokal_starten.sh`
    # je Behälter neu, sie gehört nicht in ein Abbild.
    find "$CA_QUELLE" -maxdepth 1 -name '*.crt' ! -name 'nadoku-pruefstand.crt' \
         -exec cp {} "$BAUPLATZ/" \; 2>/dev/null
    cat > "$BAUPLATZ/Dockerfile" <<'DOCKER'
FROM php:8.3.33-cli
COPY *.crt /usr/local/share/ca-certificates/
RUN update-ca-certificates \
 && sed -i 's|http://|https://|g' /etc/apt/sources.list.d/debian.sources \
 && apt-get update -qq \
 && apt-get install -y -qq libzip-dev libicu-dev libpng-dev libjpeg-dev \
 && docker-php-ext-configure gd --with-jpeg \
 && docker-php-ext-install -j4 pdo_mysql mysqli zip intl gd \
 && rm -rf /var/lib/apt/lists/*
DOCKER
    if docker build -q -t nadoku-php83 "$BAUPLATZ" >/dev/null 2>&1; then
        zeile "ok    PHP $(docker run --rm nadoku-php83 php -r 'echo PHP_VERSION;') · $(docker run --rm nadoku-php83 php -r 'echo implode(" ", array_values(array_intersect(get_loaded_extensions(), ["pdo_mysql","mysqli","zip","intl","gd"])));')"
    else
        zeile "FEHLT PHP 8.3.33 — Abbild nicht gebaut."; fehler=$((fehler+1))
    fi
    rm -rf "$BAUPLATZ"
}

datenbank() {  # datenbank <name> <abbild> <port> <klient> <vorsatz>
    local name=$1 abbild=$2 port=$3 klient=$4 vorsatz=$5 behaelter="pk-$1"
    melde "$abbild auf Port $port"
    if ! docker ps --format '{{.Names}}' | grep -qx "$behaelter"; then
        docker rm -f "$behaelter" >/dev/null 2>&1
        docker run -d --name "$behaelter" -p "$port:3306" \
            -e "${vorsatz}_ROOT_PASSWORD=probe" -e "${vorsatz}_DATABASE=nadoku_probe" \
            "$abbild" >/dev/null 2>&1 \
            || { zeile "FEHLT $abbild — nicht gestartet (Docker Hub gedrosselt?)"; fehler=$((fehler+1)); return 1; }
    fi
    # AUF EINE ECHTE ABFRAGE WARTEN, NICHT AUF EIN PING. Der Einstiegspunkt
    # von MySQL richtet erst ein und startet den Dienst DANACH neu; ein Ping
    # gelingt schon vorher, und die Probe lief prompt in
    # "MySQL server has gone away" (gemessen 21.09.2026).
    local i fassung=
    for i in $(seq 1 90); do
        fassung=$(docker exec "$behaelter" "$klient" -uroot -pprobe -N -e "SELECT VERSION();" 2>/dev/null | tail -1)
        [ -n "$fassung" ] && break
        sleep 1
    done
    if [ -z "$fassung" ]; then
        zeile "FEHLT $abbild — kam in 90 s nicht zum Antworten."; fehler=$((fehler+1)); return 1
    fi
    zeile "ok    $fassung  (bereit nach ${i} s, Port $port)"
}

schemaproben() {
    melde "Schemaprobe je Fassung"
    local wurzel; wurzel=$(cd "$(dirname "$0")/../.." && pwd)
    # Die örtliche MariaDB ist die vierte Fassung — sie liegt schon da.
    mariadb -e "CREATE DATABASE IF NOT EXISTS nadoku_probe;
                GRANT ALL ON nadoku_probe.* TO 'nadoku'@'127.0.0.1';" >/dev/null 2>&1
    local liste=("3306|nadoku|nadokulokal|mariadb|örtlich")
    local e n a p k v
    for e in "${DBS[@]}"; do IFS='|' read -r n a p k v <<<"$e"; liste+=("$p|root|probe|$k|$a"); done
    for e in "${liste[@]}"; do
        IFS='|' read -r p benutzer pass k name <<<"$e"
        local aus; aus=$(php "$wurzel/tools/schemaprobe/probe.php" --datenbank nadoku_probe \
            --host 127.0.0.1 --port "$p" --benutzer "$benutzer" --passwort "$pass" \
            --klient "$k" 2>&1 | tail -1)
        case "$aus" in
            *"0 Fehlschlaege"*) zeile "ok    $aus" ;;
            *) zeile "FEHLT $name (Port $p): $aus"; fehler=$((fehler+1)) ;;
        esac
    done
}

aus() {
    melde "Behälter entfernen"
    local e n rest
    for e in "${DBS[@]}"; do IFS='|' read -r n rest <<<"$e"; docker rm -f "pk-$n" >/dev/null 2>&1 && zeile "weg: pk-$n"; done
    exit 0
}

[ $# -eq 0 ] && set -- alles
[ "${1:-}" = "--aus" ] && { docker_da || exit 1; aus; }
docker_da || exit 1

for t in "$@"; do
    case "$t" in
        php83)      php83 ;;
        mariadb106) datenbank mariadb106 mariadb:10.6 3310 mariadb MARIADB ;;
        mysql80)    datenbank mysql80    mysql:8.0    3307 mysql   MYSQL ;;
        mysql84)    datenbank mysql84    mysql:8.4.0  3308 mysql   MYSQL ;;
        alles)      php83
                    datenbank mariadb106 mariadb:10.6 3310 mariadb MARIADB
                    datenbank mysql80    mysql:8.0    3307 mysql   MYSQL
                    datenbank mysql84    mysql:8.4.0  3308 mysql   MYSQL
                    schemaproben ;;
        *) echo "Unbekannt: $t (php83, mariadb106, mysql80, mysql84, alles, --aus)" >&2; exit 2 ;;
    esac
done

[ "$fehler" -gt 0 ] && { melde "$fehler Stück fehlen — Befund mit Zahl."; exit 1; }
melde "Modul plattform steht."
exit 0
