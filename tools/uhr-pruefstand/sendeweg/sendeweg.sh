#!/usr/bin/env bash
# Probe des Sendewegs der Garmin-Uhr — Kopplung im Dienst, Bindung der
# laufenden Pakete, Deckel fuer next_seq, gleiche Stueckgroesse bei
# Wiederholung.
#
# Anlass: Nr. 570, Nr. 577 — bis Uhr 4.3.3 liess die Uhr ein Neukoppeln im Dienst zu,
# schickte den laufenden Einsatz ohne Pruefung der Bindung, glaubte jeder
# Quittung und liess ein unquittiertes Stueck wachsen.
#
# WAS SIE TUT. Eine Prueffassung (Kopie von watch/ unter
# $CIQ_BASIS/sendeweg-app) mit SendewegProbe.mc; zwei Haken in der Kopie von
# Uploader.mc halten jede Anfrage vor dem Absenden an und melden das
# gelesene Stueck. Die Serveradresse zeigt ins Leere (127.0.0.1:1) — die
# Probe spricht mit keinem Server, auch nicht ueber die Kopplung. Die
# Antworten spielt SendewegProbe.mc vor; das Ergebnis steht in der Konsole.
#
# GEGENPROBE: SENDEWEG_WATCH=<verzeichnis> nimmt einen anderen Stand von
# watch/, etwa den von Uhr 4.3.3 (`git archive 0be2092 watch | tar -x -C …`).
# Gegen ihn muss die Probe rot sein.
#
# AUFRUF   bash tools/uhr-pruefstand/sendeweg/sendeweg.sh [geraet]
#          (Vorgabe fenix6pro; braucht `pruefstand.sh aufbau`)
set -euo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
P="$WURZEL/tools/uhr-pruefstand/pruefstand.sh"
BASIS="${CIQ_BASIS:-$HOME/.ciq-pruefstand}"
APP="$BASIS/sendeweg-app"
QUELLE="${SENDEWEG_WATCH:-$WURZEL/watch}"
GERAET="${1:-fenix6pro}"
export DISPLAY="${CIQ_DISPLAY:-:99}"

melde()  { printf '\033[1m==\033[0m %s\n' "$*"; }
fehler() { printf '\033[31mFEHLER:\033[0m %s\n' "$*" >&2; exit 1; }

melde "Prueffassung bauen ($GERAET, aus $QUELLE)"
rm -rf "$APP"; cp -r "$QUELLE" "$APP"
cp "$WURZEL/tools/uhr-pruefstand/sendeweg/SendewegProbe.mc" "$APP/source/SendewegProbe.mc"
sed -i 's|<property id="serverUrl" type="string">[^<]*</property>|<property id="serverUrl" type="string">127.0.0.1:1</property>|' \
    "$APP/resources/settings/properties.xml"
sed -i 's|        Track.restore();|        Track.restore();\n        SendewegProbe.starten();|' "$APP/source/NAdokuApp.mc"
sed -i 's|^\(        \)var flat = Track.readPoints(ref, seqFrom, n);$|&\n\1SendewegProbe.stueck(seqFrom, flat.size() / 4);|' "$APP/source/Uploader.mc"
sed -i 's|^        Communications.makeWebRequest(url,$|        if (SendewegProbe.abfangen(body)) { return; }\n&|' "$APP/source/Uploader.mc"
grep -q '127.0.0.1:1<' "$APP/resources/settings/properties.xml" || fehler "Serveradresse nicht umgestellt"
for muster in 'SendewegProbe.starten' 'SendewegProbe.stueck' 'SendewegProbe.abfangen'; do
    [ "$(grep -rc "$muster" "$APP/source" | awk -F: '{s+=$2} END {print s}')" = 1 ] \
        || fehler "Einhaengen gescheitert: $muster (genau einmal erwartet)"
done

BAU=$(bash "$P" bauen "$GERAET" "$APP/monkey.jungle" 2>&1) || true
case "$BAU" in *"BUILD SUCCESSFUL"*) ;; *) printf '%s\n' "$BAU" | tail -20; fehler "Uebersetzen gescheitert";; esac
bash "$P" beenden >/dev/null 2>&1 || true
bash "$P" einstellungen-leeren >/dev/null
bash "$P" speicher-leeren >/dev/null
melde "Starten"
timeout 90 bash "$P" starten "$GERAET" 25 >/dev/null 2>&1 || true
AUSGABE=""
for _ in $(seq 1 30); do
    AUSGABE=$(bash "$P" konsole | grep '^SENDEWEG' || true)
    case "$AUSGABE" in *"SENDEWEG ENDE"*) break;; esac
    sleep 1
done
bash "$P" beenden >/dev/null 2>&1 || true

printf '%s\n' "$AUSGABE" | grep -v '^SENDEWEG ENDE' | sed 's/^SENDEWEG /  /'
pruef=$(printf '%s\n' "$AUSGABE" | grep -c '^SENDEWEG \(ok\|FEHL\)' || true)
fehl=$(printf '%s\n' "$AUSGABE" | grep -c '^SENDEWEG FEHL' || true)
SOLL=22
if ! printf '%s\n' "$AUSGABE" | grep -q '^SENDEWEG ENDE'; then
    echo "  FEHL  keine ENDE-Zeile — die Probe brach nach $pruef Pruefungen ab"
    fehl=$((fehl+1))
elif [ "$pruef" != "$SOLL" ]; then
    echo "  FEHL  $pruef Pruefungen statt $SOLL"
    fehl=$((fehl+1))
fi
echo "Sendeweg: $pruef Pruefungen, $fehl Fehlschlaege ($GERAET)"
[ "$fehl" = 0 ]
