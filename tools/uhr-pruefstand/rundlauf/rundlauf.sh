#!/usr/bin/env bash
# Rundlauf der Uhr — koppeln, verschlossen senden, auf dem Server oeffnen.
#
# Anlass: Nr. 43 — seit Uhr 4.0.0 verschliesst die Garmin-Uhr Spur,
# Phasenkoordinaten und Reanimation. Uebersetzen und Selbsttest zeigen, dass
# sie rechnen kann; ob ihre Huellen nach dem Weg ueber Kopplung, ingest.php
# und Datenbank aufgehen, zeigt nur dieser Lauf.
#
# WAS ER TUT
#   1. eine PRUEFFASSUNG bauen: watch/ nach $CIQ_BASIS/rundlauf-app kopieren,
#      Server-Adresse auf die oertliche Anlage, Rundlauf.mc dazu (legt beim
#      Start einen Einsatz und ein Ruhesegment an). Die App selbst bleibt
#      unberuehrt.
#   2. im Simulator starten, zur Sync-Seite, START halten -> Kopplungscode
#   3. die Sitzung in der Datenbank dem Demo-Konto zuordnen — das ersetzt den
#      Klick im Web, den die Kopplungsprobe schon misst —, auf der Uhr mit
#      START bestaetigen
#   4. warten, bis beide Pakete angekommen sind, dann pruefen.php
#
# ZWEITE ART: --nachholen. Das Demo-Konto steht beim
# Koppeln OHNE oeffentlichen Schluessel da (die Spalte wird fuer diesen
# Augenblick geleert und danach — auch bei Abbruch — zurueckgeschrieben). Die
# Uhr koppelt, sagt „Gekoppelt, kein Schlüssel", sendet nicht; dann steht der
# Schluessel wieder, START halten holt ihn, und erst danach gehen die Pakete.
#
# DRITTE ART: --schrumpfen. Zwei geparkte Pakete
# vor dem Einsatz; waehrend seiner letzten Anfrage verwirft die Prueffassung
# alle geparkten (Haken in der Kopie von Uploader.mc, Rundlauf.mc). Danach
# muss die Konsole "liste=0 spuren=0" melden und keinen Absturz. Bis 4.0.0
# entfernte die Antwort den Eintrag ueber einen veralteten Listenplatz und
# brach ab.
#
# AUFRUF   bash tools/uhr-pruefstand/rundlauf/rundlauf.sh [--nachholen|--schrumpfen] [geraet]
#          (Vorgabe fenix6pro; braucht `pruefstand.sh aufbau` und die oertliche
#          Anlage mit eigener CA, docs/Technik.md 5.6)
#
# DER DEMO-RESET wird fuer die Dauer des Laufs aufgeschoben (Marke auf jetzt,
# danach zurueck) — am 17.10.2026 hat er mitten im ersten Versuch das eben
# gekoppelte Geraet geloescht: ingest 500, danach 401.
set -euo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
P="$WURZEL/tools/uhr-pruefstand/pruefstand.sh"
NACHHOLEN=0
SCHRUMPFEN=0
[ "${1:-}" = "--nachholen" ] && { NACHHOLEN=1; shift; }
[ "${1:-}" = "--schrumpfen" ] && { SCHRUMPFEN=1; shift; }
GERAET="${1:-fenix6pro}"
BASIS="${CIQ_BASIS:-$HOME/.ciq-pruefstand}"
APP="$BASIS/rundlauf-app"
ABBILD="${RUNDLAUF_ABBILDER:-$BASIS/rundlauf}"
LAUF="$(date +%s)"
EINSATZ="m-rl-$LAUF"
RUHE="r-rl-$LAUF"
DB="${RUNDLAUF_DB:-nadoku}"
export DISPLAY="${CIQ_DISPLAY:-:99}"

melde()  { printf '\033[1m==\033[0m %s\n' "$*"; }
fehler() { printf '\033[31mFEHLER:\033[0m %s\n' "$*" >&2; exit 1; }
sql()    { mysql -N "$DB" -e "$1"; }
demo_marke() {
    (cd "$WURZEL/server" && php -r 'require "db.php"; require_once "demo_lib.php";
        if (($argv[1] ?? "") === "setzen") { demo_reset_marke_setzen((int)$argv[2]); }
        echo demo_letzter_reset();' -- "$@" 2>/dev/null)
}
halten() {   # Langdruck: keydown, warten, keyup — `taste` kann nur kurz
    local wid; wid=$(xdotool search --onlyvisible --name "CIQ Simulator" | head -1)
    xdotool windowfocus "$wid"; sleep 0.5
    xdotool keydown "$1"; sleep 1.6; xdotool keyup "$1"
}

curl -sf -o /dev/null https://127.0.0.1:8443/login.php \
    || fehler "Die oertliche Anlage antwortet nicht ueber TLS mit eigener CA (https://127.0.0.1:8443)."
DEMO=$(cd "$WURZEL/server" && php -r 'require "db.php"; require_once "demo_lib.php"; echo demo_id() ?? "";')
[ -n "$DEMO" ] || fehler "Kein Demo-Konto auf der Anlage."
ALT=$(demo_marke lesen)
KP_ALT=
GERAET_VOR=$(sql "SELECT COALESCE(MAX(id),0) FROM devices")
aufraeumen() {
    demo_marke setzen "$ALT" >/dev/null
    # Das gekoppelte Geraet wieder abraeumen: Das Demo-Konto hat MAX_GERAETE
    # Plaetze, und am 17.10.2026 lief der vierte Lauf genau hinein
    # (`bestaetigen` 409 device_limit, kein Paket kam an). Die Daten bleiben
    # (device_id wird NULL), der Platz wird frei.
    sql "DELETE FROM devices WHERE user_id = $DEMO AND id > $GERAET_VOR" || true
    # Der Schluessel des Demo-Kontos MUSS zurueck — ohne ihn geht kein
    # Demo-Einsatz mehr auf, und jede folgende Probe misst Unsinn.
    [ -n "$KP_ALT" ] && sql "UPDATE users SET kp_oeffentlich = $KP_ALT WHERE id = $DEMO" || true
}
trap aufraeumen EXIT
demo_marke setzen "$(date +%s)" >/dev/null
melde "Demo-Reset aufgeschoben (Konto $DEMO)"

melde "Prueffassung bauen ($GERAET, Lauf $LAUF)"
rm -rf "$APP"; cp -r "$WURZEL/watch" "$APP"
sed "s/m-rundlauf-0001/$EINSATZ/; s/r-rundlauf-0001/$RUHE/" \
    "$WURZEL/tools/uhr-pruefstand/rundlauf/Rundlauf.mc" > "$APP/source/Rundlauf.mc"
sed -i 's|<property id="serverUrl" type="string">[^<]*</property>|<property id="serverUrl" type="string">127.0.0.1:8443</property>|' \
    "$APP/resources/settings/properties.xml"
sed -i 's|        Track.restore();|        Track.restore();\n        Rundlauf.fuellen();|' "$APP/source/NAdokuApp.mc"
grep -q 'Rundlauf.fuellen' "$APP/source/NAdokuApp.mc" || fehler "Einhaengen in NAdokuApp.onStart gescheitert"
if [ "$SCHRUMPFEN" = 1 ]; then
    sed -i 's|const SCHRUMPFEN = false;|const SCHRUMPFEN = true;|' "$APP/source/Rundlauf.mc"
    # Haken 1 nach dem Absenden, Haken 2 nach jeder angenommenen Antwort.
    sed -i 's|            opts, cb.method(:onResponse));|            opts, cb.method(:onResponse));\n        Rundlauf.waehrendAnfrage(ref);|' "$APP/source/Uploader.mc"
    sed -i 's|            _next();   // weitere offene Chunks/Jobs|            _next();   // weitere offene Chunks/Jobs\n            Rundlauf.bericht();|' "$APP/source/Uploader.mc"
    [ "$(grep -c 'Rundlauf\.' "$APP/source/Uploader.mc")" = 2 ] \
        || fehler "Haken in Uploader.mc nicht eingesetzt (zwei erwartet)"
    grep -q 'const SCHRUMPFEN = true;' "$APP/source/Rundlauf.mc" || fehler "Schalter in Rundlauf.mc nicht gesetzt"
fi
# Ausgabe erst sammeln: `| grep -q` schloesse die Pipe beim ersten Treffer,
# und unter pipefail zaehlte das als Scheitern des Uebersetzens.
BAU=$(bash "$P" bauen "$GERAET" "$APP/monkey.jungle" 2>&1) || true
case "$BAU" in *"BUILD SUCCESSFUL"*) ;; *) printf '%s\n' "$BAU" | tail -20; fehler "Uebersetzen gescheitert";; esac

# ERST BEENDEN, DANN LEEREN. Ein laufender Simulator haelt den Speicher der
# App im Prozess und schreibt ihn zurueck: Am 17.10.2026 zeigte ein Lauf nach
# `speicher-leeren` "6 Pakete offen" — die 2 dieses Laufs und 4 aus zwei
# gescheiterten davor, die dann mitgesendet wurden.
bash "$P" beenden >/dev/null 2>&1 || true
bash "$P" einstellungen-leeren >/dev/null
bash "$P" speicher-leeren >/dev/null
mkdir -p "$ABBILD"
VORHER=$(sql "SELECT COALESCE(MAX(id),0) FROM pair_sessions")
melde "Starten"
timeout 90 bash "$P" starten "$GERAET" 25 >/dev/null 2>&1 || true
bash "$P" taste Down; sleep 2; bash "$P" taste Down; sleep 3     # der erste Druck geht verloren
bash "$P" abbild "$ABBILD/1-sync.png" >/dev/null
halten Return
SITZUNG=
for _ in $(seq 1 30); do
    SITZUNG=$(sql "SELECT id FROM pair_sessions WHERE id > $VORHER AND user_id IS NULL ORDER BY id DESC LIMIT 1")
    [ -n "$SITZUNG" ] && break; sleep 1
done
[ -n "$SITZUNG" ] || fehler "Keine Kopplungssitzung — kam der Langdruck an? ($ABBILD/1-sync.png)"
bash "$P" abbild "$ABBILD/2-code.png" >/dev/null
if [ "$NACHHOLEN" = 1 ]; then
    KP_ALT="0x$(sql "SELECT HEX(kp_oeffentlich) FROM users WHERE id = $DEMO")"
    [ "$KP_ALT" != "0x" ] && [ "$KP_ALT" != "0xNULL" ] || fehler "Demo-Konto hat keinen Schluessel, den man wegnehmen koennte"
    sql "UPDATE users SET kp_oeffentlich = NULL WHERE id = $DEMO"
    melde "Schluessel des Demo-Kontos voruebergehend geleert"
fi
sql "UPDATE pair_sessions SET user_id = $DEMO WHERE id = $SITZUNG"
melde "Sitzung $SITZUNG dem Demo-Konto zugeordnet"
sleep 9
bash "$P" abbild "$ABBILD/3-rueckfrage.png" >/dev/null
bash "$P" taste Return
if [ "$NACHHOLEN" = 1 ]; then
    sleep 12
    bash "$P" abbild "$ABBILD/3a-kein-schluessel.png" >/dev/null
    VOR=$(sql "SELECT COUNT(*) FROM missions WHERE user_id=$DEMO AND client_ref='$EINSATZ'")
    [ "$VOR" = 0 ] || fehler "Ohne Schluessel ist trotzdem ein Einsatz angekommen"
    melde "Ohne Schluessel nichts gesendet (0 Einsaetze) — Schluessel zurueck, START halten"
    sql "UPDATE users SET kp_oeffentlich = $KP_ALT WHERE id = $DEMO"; KP_ALT=
    # Die Bestaetigung hat die Sync-Seite verlassen? Erst ansehen, dann halten.
    halten Return
    sleep 4
    bash "$P" abbild "$ABBILD/3b-geholt.png" >/dev/null
fi
melde "Bestaetigt — warte auf die Pakete"
for _ in $(seq 1 60); do
    N=$(sql "SELECT (SELECT COUNT(*) FROM missions WHERE user_id=$DEMO AND client_ref='$EINSATZ' AND final=1)
               + (SELECT COUNT(*) FROM rest_segments WHERE user_id=$DEMO AND client_ref='$RUHE' AND final=1)")
    [ "$N" = 2 ] && break; sleep 2
done
sleep 4
bash "$P" abbild "$ABBILD/4-gesendet.png" >/dev/null
melde "Gegenpruefung"
php "$WURZEL/tools/uhr-pruefstand/rundlauf/pruefen.php" "$EINSATZ" "$RUHE"
if [ "$SCHRUMPFEN" = 1 ]; then
    melde "Schrumpfen (F-R17-52): was meldet die Uhr?"
    KONSOLE=$(bash "$P" konsole)
    printf '%s\n' "$KONSOLE" | grep 'RL-SCHRUMPF' || true
    s_fehl=0
    if printf '%s\n' "$KONSOLE" | grep -q 'RL-SCHRUMPF verworfen=2 offen=1'; then
        echo "  ok    waehrend der letzten Anfrage 2 geparkte verworfen, 1 Eintrag uebrig"
    else echo "  FEHL  das Verwerfen waehrend der Anfrage kam nicht an"; s_fehl=$((s_fehl+1)); fi
    if [ "$(printf '%s\n' "$KONSOLE" | grep 'RL-SCHRUMPF liste=' | tail -1)" = "RL-SCHRUMPF liste=0 spuren=0" ]; then
        echo "  ok    nach der Antwort: Liste leer, kein Spurstueck uebrig"
    else echo "  FEHL  nach der Antwort nicht leer (oder keine Antwort verarbeitet)"; s_fehl=$((s_fehl+1)); fi
    if printf '%s\n' "$KONSOLE" | grep -qiE 'Error:|Exception|Out Of Bounds'; then
        echo "  FEHL  Absturz in der Konsole"; s_fehl=$((s_fehl+1))
    else echo "  ok    kein Absturz in der Konsole"; fi
    echo "Schrumpfen: 3 Pruefungen, $s_fehl Fehlschlaege"
    [ "$s_fehl" = 0 ] || exit 1
fi
