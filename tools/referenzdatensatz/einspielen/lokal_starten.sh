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
# Das Zertifikat ist selbstsigniert und gilt nur fuer diese Maschine. Die
# Skripte pruefen es deshalb nicht -- das ist fuer 127.0.0.1 vertretbar und
# steht ausdruecklich so im LIESMICH.
if ! curl -sk --noproxy '*' -o /dev/null "https://127.0.0.1:$TLS_PORT/login.php"; then
  mkdir -p "$TLS_DIR"
  if [ ! -f "$TLS_DIR/beides.pem" ]; then
    echo "Selbstsigniertes Zertifikat erzeugen ..."
    openssl req -x509 -newkey rsa:2048 -nodes -days 365 \
      -keyout "$TLS_DIR/key.pem" -out "$TLS_DIR/cert.pem" \
      -subj "/CN=localhost" -addext "subjectAltName=DNS:localhost,IP:127.0.0.1" 2>/dev/null
    cat "$TLS_DIR/cert.pem" "$TLS_DIR/key.pem" > "$TLS_DIR/beides.pem"
  fi
  echo "TLS-Terminierung starten ..."
  socat "OPENSSL-LISTEN:$TLS_PORT,cert=$TLS_DIR/beides.pem,verify=0,reuseaddr,fork" \
        "TCP:$ADRESSE" >/tmp/socat.log 2>&1 &
  curl -sk --noproxy '*' --retry 20 --retry-delay 1 --retry-all-errors \
       -o /dev/null "https://127.0.0.1:$TLS_PORT/login.php" || true
fi
curl -sk --noproxy '*' -o /dev/null -w "HTTPS:       HTTP %{http_code}  (https://127.0.0.1:$TLS_PORT)\n" \
     "https://127.0.0.1:$TLS_PORT/login.php"
