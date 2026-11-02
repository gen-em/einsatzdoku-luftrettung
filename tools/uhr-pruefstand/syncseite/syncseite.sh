#!/usr/bin/env bash
# Bildprobe der Sync-Seite — vier bis sieben Zeilen im unteren Block.
#
# Anlass: Nr. 402 — der Mittelblock ("N Pakete offen") rutschte ab vier
# Zeilen im unteren Block auf dessen oberste Meldung. Gesehen nur im
# Simulator, und nur auf der Venu 3s; deshalb misst die Probe alle drei
# Zielgeraete.
#
# WAS SIE TUT. Je Zeilenzahl eine Prueffassung (Kopie von watch/ unter
# $CIQ_BASIS/syncseite-app): Rundlauf.mc legt zwei offene Pakete an,
# SyncProbe.mc setzt die Meldungen, die App startet direkt auf der
# Sync-Seite, und die Kopie von SyncView.mc schreibt ihre Rechnung in die
# Konsole ("SYNCPROBE <Ende Mittelblock> <Beginn unterer Block>"). Gruen ist
# eine Zeile, wenn das Ende nicht ueber dem Beginn liegt. Dazu ein Bild je
# Geraet und Zahl unter $CIQ_BASIS/syncseite/ — die Rechnung sagt, ob die
# Bloecke sich beruehren, das Bild, ob es so aussieht.
#
# AUFRUF   bash tools/uhr-pruefstand/syncseite/syncseite.sh [geraet ...]
#          (Vorgabe fenix6pro fr945 venu3s; braucht `pruefstand.sh aufbau`)
set -euo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
P="$WURZEL/tools/uhr-pruefstand/pruefstand.sh"
BASIS="${CIQ_BASIS:-$HOME/.ciq-pruefstand}"
APP="$BASIS/syncseite-app"
ABBILD="${SYNCSEITE_ABBILDER:-$BASIS/syncseite}"
GERAETE=("$@")
[ ${#GERAETE[@]} -eq 0 ] && GERAETE=(fenix6pro fr945 venu3s)
export DISPLAY="${CIQ_DISPLAY:-:99}"

melde()  { printf '\033[1m==\033[0m %s\n' "$*"; }
fehler() { printf '\033[31mFEHLER:\033[0m %s\n' "$*" >&2; exit 1; }

mkdir -p "$ABBILD"
pruef=0; fehl=0
for zeilen in 4 5 6 7; do
    rm -rf "$APP"; cp -r "$WURZEL/watch" "$APP"
    cp "$WURZEL/tools/uhr-pruefstand/rundlauf/Rundlauf.mc" "$APP/source/Rundlauf.mc"
    sed "s/const ZEILEN = 4;/const ZEILEN = $zeilen;/" \
        "$WURZEL/tools/uhr-pruefstand/syncseite/SyncProbe.mc" > "$APP/source/SyncProbe.mc"
    sed -i 's|        Track.restore();|        Track.restore();\n        Rundlauf.fuellen();\n        SyncProbe.fuellen();|' "$APP/source/NAdokuApp.mc"
    sed -i 's|        return \[new StartView(), new StartDelegate()\];|        return [new SyncView(true), new SyncDelegate(true)];|' "$APP/source/NAdokuApp.mc"
    sed -i 's|^using Toybox.Lang;|using Toybox.Lang;\nusing Toybox.System;|' "$APP/source/SyncView.mc"
    sed -i 's|        if (y < oben) { y = oben; }|        if (y < oben) { y = oben; }\n        System.println("SYNCPROBE " + (y + blockH).toString() + " " + untenY.toString());|' "$APP/source/SyncView.mc"
    # Die Fassung vor Uhr 4.3.1 hatte die Grenze noch ausgeschrieben — so laesst
    # sich die Probe auch gegen den alten Stand fahren (Gegenprobe).
    sed -i 's|        if (y < Ui.s(dc, 34)) { y = Ui.s(dc, 34); }|        if (y < Ui.s(dc, 34)) { y = Ui.s(dc, 34); }\n        System.println("SYNCPROBE " + (y + blockH).toString() + " " + untenY.toString());|' "$APP/source/SyncView.mc"
    for muster in 'SyncProbe.fuellen' 'new SyncView(true)' 'using Toybox.System' 'SYNCPROBE'; do
        grep -rq "$muster" "$APP/source" || fehler "Einhaengen gescheitert: $muster"
    done
    for g in "${GERAETE[@]}"; do
        BAU=$(bash "$P" bauen "$g" "$APP/monkey.jungle" 2>&1) || true
        case "$BAU" in *"BUILD SUCCESSFUL"*) ;; *) printf '%s\n' "$BAU" | tail -20; fehler "Uebersetzen gescheitert ($g)";; esac
        bash "$P" beenden >/dev/null 2>&1 || true
        bash "$P" einstellungen-leeren >/dev/null
        bash "$P" speicher-leeren >/dev/null
        timeout 90 bash "$P" starten "$g" 25 >/dev/null 2>&1 || true
        sleep 3
        bash "$P" abbild "$ABBILD/$g-$zeilen.png" >/dev/null
        zeile=$(bash "$P" konsole | grep '^SYNCPROBE' | tail -1 || true)
        pruef=$((pruef+1))
        if [ -z "$zeile" ]; then
            echo "  FEHL  $g, $zeilen Zeilen: keine Rechnung in der Konsole"; fehl=$((fehl+1)); continue
        fi
        read -r _ ende unten <<<"$zeile"
        if [ "$ende" -le "$unten" ]; then
            echo "  ok    $g, $zeilen Zeilen: Mittelblock endet bei $ende, unterer Block beginnt bei $unten"
        else
            echo "  FEHL  $g, $zeilen Zeilen: Mittelblock endet bei $ende, unterer Block beginnt bei $unten"; fehl=$((fehl+1))
        fi
    done
done
bash "$P" beenden >/dev/null 2>&1 || true
echo "Sync-Seite: $pruef Pruefungen, $fehl Fehlschlaege (Bilder unter $ABBILD)"
[ "$fehl" = 0 ]
