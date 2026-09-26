#!/bin/sh
# Pruefkonten der Rollen admin und support anlegen -- fuer den Bilderlauf.
#
# Anlass: Nr. 297 (F-P5c-41). Der Bilderlauf kannte zwei angemeldete Rollen,
# `demo` und `admin`, und `admin` meldete sich als admin@gen-em.org an --
# eine BetreiberIn. Die reine Admin-Sicht und die des Supports (drei Kacheln
# ueber der Liste, eine einspaltige Kontoseite ohne die Knoepfe, die der
# Support nicht darf, zwei Protokollreiter) nahm er nie auf; belegt waren
# sie nur als Statuscode in der Rollenprobe.
#
# DERSELBE WEG WIE JEDES ANDERE KONTO. `konto_anlegen()` legt das Konto mit
# einem Setz-Token an (wie die Verwaltung), `passwort_setzen.mjs` setzt das
# Passwort im Browser (Salz, Inhaltsschluessel und Huellen entstehen nur
# dort, E-P1-10), `pruefkonto.php` gibt ihm den Zweitfaktor der Sandbox --
# beide Rollen sind Pflichtrollen (`rolle_braucht_zweitfaktor()`). Nichts
# davon ist nachgebaut.
#
# WIEDERHOLBAR. Ein Konto mit Passwort bleibt, wie es ist; eines ohne (ein
# abgebrochener frueherer Lauf) bekommt einen neuen Setz-Token. Deshalb ruft
# `tools/sandbox/hochfahren.sh` das Skript bei jedem Start: Eine Anlage, die
# vor R4-08 eingerichtet wurde, bekommt die Konten nachgereicht, ohne dass
# jemand `--neu` fahren und die Datenbank loeschen muss.
#
# Aufruf:  sh tools/referenzdatensatz/einspielen/pruefkonten.sh
#          (Adressen und Passwoerter wie in aufnehmen.mjs; anders nur mit
#          ROLLE_ADMIN=… ROLLE_ADMIN_PW=… ROLLE_SUPPORT=… ROLLE_SUPPORT_PW=…)
#
# NUR IN DER SANDBOX -- `pruefkonto.php` bricht ab, wenn die Anlage nicht auf
# 127.0.0.1 oder localhost zeigt.
set -e

WURZEL=${WURZEL:-$(cd "$(dirname "$0")/../../.." && pwd)}
ROLLE_ADMIN=${ROLLE_ADMIN:-bilderlauf-admin@probe.invalid}
ROLLE_ADMIN_PW=${ROLLE_ADMIN_PW:-pruefstandadminsicht2026}
ROLLE_SUPPORT=${ROLLE_SUPPORT:-bilderlauf-support@probe.invalid}
ROLLE_SUPPORT_PW=${ROLLE_SUPPORT_PW:-pruefstandsupportsicht2026}

konto() {   # konto <rolle> <adresse> <passwort>
  LINK=$(cd "$WURZEL/server" && php -r '
    require "db.php"; require_once "konto_lib.php";
    [, $rolle, $adresse] = $argv;
    $st = db()->prepare("SELECT id, role, password_hash, totp_seit FROM users WHERE email = ?");
    $st->execute([$adresse]);
    $k = $st->fetch(PDO::FETCH_ASSOC);
    if ($k && $k["role"] !== $rolle) {
        fwrite(STDERR, "Konto $adresse hat die Rolle {$k["role"]}, erwartet $rolle\n");
        exit(1);
    }
    if ($k && $k["password_hash"] !== null) { echo $k["totp_seit"] === null ? "zweitfaktor" : ""; exit(0); }
    $token = $k ? reset_token_ausstellen((int)$k["id"], TOKEN_EINLADUNG_S)
                : konto_anlegen($adresse, "Prüfstand " . ucfirst($rolle), $rolle, "einladung")["token"];
    echo app_url("/pw_handling.php?token=" . $token);' -- "$1" "$2")
  if [ -z "$LINK" ]; then
    echo "   $2 ($1): steht, mit Zweitfaktor"
    return 0
  elif [ "$LINK" = zweitfaktor ]; then
    echo "   $2 ($1): steht, ohne Zweitfaktor"
  else
    LOG=$(mktemp)
    if (cd "$WURZEL/tools/referenzdatensatz/einspielen" \
        && NODE_PATH=${NODE_PATH:-/opt/node22/lib/node_modules} \
           node passwort_setzen.mjs "$LINK" "$3" >"$LOG" 2>&1)
    then
      rm -f "$LOG"; echo "   $2 ($1): angelegt, Passwort gesetzt"
    else
      sed 's/^/   /' "$LOG"; rm -f "$LOG"
      echo "   $2 ($1): Passwort NICHT gesetzt"
      exit 1
    fi
  fi
  php "$WURZEL/tools/zweitfaktor/pruefkonto.php" "$2"
}

konto admin "$ROLLE_ADMIN" "$ROLLE_ADMIN_PW"
konto support "$ROLLE_SUPPORT" "$ROLLE_SUPPORT_PW"
