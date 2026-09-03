#!/bin/sh
# Lokale Installation hochfahren (MariaDB + PHP-Server).
#
# WOFUER. Der Einspiellauf braucht eine laufende Installation. Auf einer
# Entwicklungsmaschine ist sie in zwei Minuten aufgesetzt; in einem Container,
# der zwischendurch neu startet, muss man sie wieder hochfahren -- und dann
# will man nicht raten, welche Schritte es waren.
#
# EINRICHTEN passiert damit NICHT. Das macht install.php ueber den Browser
# (siehe LIESMICH.md, Abschnitt "Lokale Installation").
set -e

MYSQLD=${MYSQLD:-mysqld_safe}
WURZEL=${WURZEL:-/home/user/einsatzdoku-luftrettung/server}
ADRESSE=${ADRESSE:-127.0.0.1:8080}
TLS_PORT=${TLS_PORT:-8443}
TLS_DIR=${TLS_DIR:-/tmp/lokal-tls}

mkdir -p /var/run/mysqld /var/log/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql /var/log/mysql 2>/dev/null || true

if ! mysqladmin ping >/dev/null 2>&1; then
  echo "MariaDB starten ..."
  $MYSQLD >/var/log/mysql/safe.log 2>&1 &
  # --wait laesst mysqladmin selbst warten und erneut versuchen. Eine
  # eigene Warteschleife war hier zuerst drin und lief leer durch, weil sie
  # ohne Pause zaehlte -- sechzig Versuche in einer Zehntelsekunde.
  mysqladmin ping --wait=30 >/dev/null 2>&1 \
    || { echo "MariaDB kam nicht hoch."; exit 1; }
fi
echo "MariaDB laeuft."

if ! curl -s --noproxy '*' -o /dev/null "http://$ADRESSE/login.php"; then
  echo "PHP-Server starten ..."
  php -S "$ADRESSE" -t "$WURZEL" >/tmp/php-server.log 2>&1 &
  curl -s --noproxy '*' --retry 20 --retry-delay 1 --retry-all-errors \
       -o /dev/null "http://$ADRESSE/login.php" || true
fi
curl -s --noproxy '*' -o /dev/null -w "PHP-Server: HTTP %{http_code}\n" "http://$ADRESSE/login.php"

# ---- TLS davor ------------------------------------------------------------
#
# WARUM. Die Anwendung setzt ihr Sitzungs-Cookie mit `secure` (login.php,
# auth_guard.php) -- richtig so, sie gehoert hinter HTTPS. Ueber blankes HTTP
# schickt aber kein Client das Cookie zurueck, und jede angemeldete Seite
# leitet zur Anmeldung um. Der eingebaute PHP-Server kann kein TLS; socat
# terminiert es davor.
#
# DAS ZERTIFIKAT IST NICHT MEHR SELBSTSIGNIERT, sondern von einer eigenen,
# hier erzeugten CA unterschrieben, und die CA liegt im Systemspeicher.
#
# WARUM DER UMWEG. Fuer curl war selbstsigniert genug (die Skripte pruefen mit
# -k nicht nach). Der Connect-IQ-SIMULATOR prueft sehr wohl, und er nimmt
# keinen unbekannten Aussteller: Der Handschlag endet in
# "tlsv1 alert unknown ca", und beim Uhr-Rundlauf kommt nichts an. Ueber
# blankes HTTP geht es auch nicht -- da laesst der Simulator die Anfrage zwar
# hinaus (der Server sieht sie), gibt der App die Antwort aber nicht: sie
# bekommt -1001 SECURE_CONNECTION_REQUIRED. Gemessen am 03.09.2026 mit
# tools/netzprobe/ (F-S5-11); mit CA im Systemspeicher kam 405 von pair.php
# durch, also die echte Antwort.
#
# Fuer alles andere aendert sich nichts: curl -k gilt weiter, und ein
# CA-signiertes Zertifikat ist fuer 127.0.0.1 nicht unsicherer als ein
# selbstsigniertes -- die CA entsteht auf dieser Maschine und verlaesst sie
# nicht.
if ! curl -sk --noproxy '*' -o /dev/null "https://127.0.0.1:$TLS_PORT/login.php"; then
  mkdir -p "$TLS_DIR"
  if [ ! -f "$TLS_DIR/beides.pem" ]; then
    echo "Eigene CA und Serverzertifikat erzeugen ..."
    openssl req -x509 -newkey rsa:2048 -nodes -days 3650 \
      -keyout "$TLS_DIR/ca.key" -out "$TLS_DIR/ca.crt" \
      -subj "/CN=NAdoku Pruefstand CA" \
      -addext "basicConstraints=critical,CA:TRUE" 2>/dev/null
    openssl req -newkey rsa:2048 -nodes -keyout "$TLS_DIR/key.pem" \
      -out "$TLS_DIR/srv.csr" -subj "/CN=127.0.0.1" 2>/dev/null
    printf 'subjectAltName=IP:127.0.0.1,DNS:localhost\nextendedKeyUsage=serverAuth\n' \
      > "$TLS_DIR/ext.cnf"
    openssl x509 -req -in "$TLS_DIR/srv.csr" -CA "$TLS_DIR/ca.crt" \
      -CAkey "$TLS_DIR/ca.key" -CAcreateserial -days 3650 \
      -extfile "$TLS_DIR/ext.cnf" -out "$TLS_DIR/cert.pem" 2>/dev/null
    cat "$TLS_DIR/cert.pem" "$TLS_DIR/key.pem" > "$TLS_DIR/beides.pem"
    # Die CA in den Systemspeicher -- daran prueft der Simulator.
    if [ -d /usr/local/share/ca-certificates ]; then
      cp "$TLS_DIR/ca.crt" /usr/local/share/ca-certificates/nadoku-pruefstand.crt
      update-ca-certificates >/dev/null 2>&1 || true
    fi
  fi
  echo "TLS-Terminierung starten ..."
  socat "OPENSSL-LISTEN:$TLS_PORT,cert=$TLS_DIR/beides.pem,verify=0,reuseaddr,fork" \
        "TCP:$ADRESSE" >/tmp/socat.log 2>&1 &
  curl -sk --noproxy '*' --retry 20 --retry-delay 1 --retry-all-errors \
       -o /dev/null "https://127.0.0.1:$TLS_PORT/login.php" || true
fi
curl -sk --noproxy '*' -o /dev/null -w "HTTPS:       HTTP %{http_code}  (https://127.0.0.1:$TLS_PORT)\n" \
     "https://127.0.0.1:$TLS_PORT/login.php"
