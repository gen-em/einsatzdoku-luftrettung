#!/bin/sh
# Lokale Installation EINRICHTEN -- Datenbank, install.php, Admin-Passwort,
# Demo-Konto. Danach laeuft eine vollstaendige Anwendung mit Bestand.
#
# WOZU. `lokal_starten.sh` faehrt hoch, was schon da ist, und sagt in seinem
# Kopf ausdruecklich: "EINRICHTEN passiert damit NICHT. Das macht install.php
# ueber den Browser." In einer Wegwerf-Umgebung ist der Browserweg genau der,
# den man nicht gehen kann -- und dann steht man vor einer leeren Datenbank
# und raet, welche Felder das Formular haben wollte. Dieses Skript geht
# denselben Weg ueber HTTP: dieselbe Seite, dasselbe Formular, dieselben
# Pruefungen (Formular-Token, Nachweisdatei, Schema, Admin-Anlage).
#
# WAS ES NICHT NACHBAUT: den Browserschritt. Passwort, Salz, Inhalts-
# schluessel, beide Schluesselhuellen und der Wiederherstellungsschluessel
# entstehen ausschliesslich mit der WebCrypto des Browsers (E-P1-10). Dieses
# Skript ruft dafuer `passwort_setzen.mjs` -- den vorhandenen, geprueften
# Weg -- und baut nichts nach.
#
# DIE VORGABEN SIND NICHT BELIEBIG. Sie sind die, die die Pruefmittel ohne
# Schalter erwarten:
#   admin@gen-em.org / adminlokal2026   kreislauf.py, aufnehmen.mjs
#   demo@gen-em.org  / nadokudemo0815   Handbuch, aufnehmen.mjs, spurprobe
# Wer sie aendert, gibt sie jedem Werkzeug einzeln mit.
#
# Aufruf:  sh tools/referenzdatensatz/einspielen/lokal_einrichten.sh
#          DB=… DBUSER=… DBPASS=… ADMIN=… ADMINPW=… ADRESSE=… TLS_PORT=…
#
# ACHTUNG: Schritt 1 LOESCHT die Datenbank $DB und server/config.php.
# Gegen eine Testinstallation fahren, nicht gegen den Produktivserver.
set -e

WURZEL=${WURZEL:-$(cd "$(dirname "$0")/../../.." && pwd)}
SERVER="$WURZEL/server"
DB=${DB:-nadoku}
DBUSER=${DBUSER:-nadoku}
DBPASS=${DBPASS:-nadokulokal}
ADMIN=${ADMIN:-admin@gen-em.org}
ADMINPW=${ADMINPW:-adminlokal2026}
ADRESSE=${ADRESSE:-127.0.0.1:8080}
TLS_PORT=${TLS_PORT:-8443}
JAR=$(mktemp)

echo "== 1. Alte Installation abraeumen ($DB)"
pkill -f "php -S $ADRESSE" 2>/dev/null || true
sleep 1
rm -f "$SERVER/config.php" "$SERVER/install.lock" "$SERVER"/install-nachweis-*.txt
mariadb -e "DROP DATABASE IF EXISTS \`$DB\`;
  CREATE DATABASE \`$DB\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS '$DBUSER'@'localhost' IDENTIFIED BY '$DBPASS';
  CREATE USER IF NOT EXISTS '$DBUSER'@'127.0.0.1' IDENTIFIED BY '$DBPASS';
  GRANT ALL PRIVILEGES ON \`$DB\`.* TO '$DBUSER'@'localhost';
  GRANT ALL PRIVILEGES ON \`$DB\`.* TO '$DBUSER'@'127.0.0.1';
  FLUSH PRIVILEGES;"

echo "== 2. PHP-Server starten"
php -S "$ADRESSE" -t "$SERVER" >/tmp/php-server.log 2>&1 &
curl -s --noproxy '*' --retry 20 --retry-delay 1 --retry-all-errors \
     -o /dev/null "http://$ADRESSE/install.php"

echo "== 3. Formular-Token und Nachweis holen"
# Der Nachweis steht im DATEINAMEN einer Datei, die install.php beim ersten
# Aufruf in server/ anlegt (M1-11): Wer sie lesen kann, hat Zugriff auf das
# Verzeichnis. Deshalb erst aufrufen, dann den Namen lesen.
CSRF=$(curl -s --noproxy '*' -c "$JAR" "http://$ADRESSE/install.php" \
       | grep -o 'name="csrf" value="[0-9a-f]*"' | head -1 | sed 's/.*value="//;s/"//')
NACHWEIS=$(ls "$SERVER"/install-nachweis-*.txt 2>/dev/null | head -1 \
       | sed 's/.*install-nachweis-//;s/\.txt//')
[ -n "$CSRF" ] && [ -n "$NACHWEIS" ] || { echo "Formular-Token oder Nachweis fehlt."; exit 1; }

echo "== 4. Einrichtungsformular abschicken"
ANTWORT=$(curl -s --noproxy '*' -b "$JAR" -c "$JAR" -X POST "http://$ADRESSE/install.php" \
  --data-urlencode "csrf=$CSRF"        --data-urlencode "nachweis=$NACHWEIS" \
  --data-urlencode "db_host=127.0.0.1" --data-urlencode "db_name=$DB" \
  --data-urlencode "db_user=$DBUSER"   --data-urlencode "db_pass=$DBPASS" \
  --data-urlencode "admin_email=$ADMIN" \
  --data-urlencode "base_url=https://127.0.0.1:$TLS_PORT" \
  --data-urlencode "timezone=Europe/Berlin" \
  --data-urlencode "logo_path=assets/images/gen-em_logo_helicopter.svg" \
  --data-urlencode "smtp_host=127.0.0.1" --data-urlencode "smtp_port=2525" \
  --data-urlencode "smtp_user=noreply@example.invalid" --data-urlencode "smtp_pass=x" \
  --data-urlencode "smtp_from=noreply@example.invalid" \
  --data-urlencode "smtp_from_name=Einsatzdoku")
LINK=$(printf '%s' "$ANTWORT" \
       | grep -o 'https\{0,1\}://[^"<[:space:]]*pw_handling.php[^"<[:space:]]*' | head -1)
if [ -z "$LINK" ]; then
  echo "Einrichtungslink nicht gefunden. Meldungen der Seite:"
  printf '%s' "$ANTWORT" | grep -o '<li>[^<]*</li>' | sed 's/<[^>]*>//g;s/^/   /'
  exit 1
fi
rm -f "$JAR"

echo "== 5. TLS davor (socat) -- das Sitzungs-Cookie ist secure"
sh "$WURZEL/tools/referenzdatensatz/einspielen/lokal_starten.sh" >/dev/null

echo "== 6. Admin-Passwort im Browser setzen"
cd "$WURZEL/tools/referenzdatensatz/einspielen"
NODE_PATH=${NODE_PATH:-/opt/node22/lib/node_modules} \
  node passwort_setzen.mjs "$LINK" "$ADMINPW" /tmp/admin-rc.json | sed 's/^/   /'
cd "$WURZEL"

echo "== 7. Demo-Konto aus der Fixture anlegen"
# demo_anlegen() ist derselbe Weg wie der Knopf im Adminbereich
# (admin_demo.php, Aktion demo_anlegen) -- kein zweiter Weg, den niemand pflegt.
php -r 'require_once "server/db.php"; require_once "server/demo_lib.php";
        $b = demo_anlegen();
        printf("   %d Einsaetze, %d Diensttage, %d Geraete\n",
               $b["missions"], $b["days"], $b["geraete"]);'

echo "== fertig"
echo "   https://127.0.0.1:$TLS_PORT/"
echo "   $ADMIN / $ADMINPW   ·   demo@gen-em.org / nadokudemo0815"
