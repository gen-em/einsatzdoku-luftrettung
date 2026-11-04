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
# DIE BREITE (Nr. 470). Seit BR6-06 schreibt die Kopie je Bild auch die
# groesste Ueberschreitung einer Zeile des unteren Blocks in die Konsole
# ("SYNCBREITE <Textbreite minus Sehne> <Zeile>"), mit derselben Sehne, die
# die App misst (Ui.kanteW). Gruen ist sie bei hoechstens 0. Bis dahin sah
# nur das Bild, dass auf der Venu 3s bei sechs und sieben Zeilen beide Enden
# von "Action halten: Geraet koppeln" fehlten.
# SYNCSEITE_OHNE_KURZFORM=1 schaltet in der Kopie die Kurzform ab — die
# Gegenprobe, dass die Messung den Fehler findet.
#
# DIE AUSNAHMEN (E-BR6-17, Q-BR6-10). Die Messung bleibt streng: Sehne MIT
# dem Sicherheitsrand der App (16 Bezugspixel, auf der Venu 3s rund 28 px).
# Wo eine Zeile bewusst ein Stueck in diesen Rand ragt, steht sie unten mit
# Geraet, Zeilenzahl, Text, hoechstem erlaubtem Ueberstand und Grund. Sie
# ist dann gruen, wird aber in JEDEM Lauf als Ausnahme genannt; ragt sie
# weiter, oder steht dort ein anderer Text, ist sie rot.
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

# geraet|zeilen|text|hoechstens px|grund
AUSNAHMEN=(
  "venu3s|6|Koppeln: Action halten|6|im Bild ganz lesbar; „… halten“ wie ueberall auf der Uhr (E-BR6-17)"
  "venu3s|7|Koppeln: Action halten|6|im Bild ganz lesbar; „… halten“ wie ueberall auf der Uhr (E-BR6-17)"
)
ausnahme() {   # ausnahme <geraet> <zeilen> <text> -> erlaubter Ueberstand und Grund, sonst nichts
    local e g z t m r
    for e in "${AUSNAHMEN[@]}"; do
        IFS='|' read -r g z t m r <<<"$e"
        [ "$g" = "$1" ] && [ "$z" = "$2" ] && [ "$t" = "$3" ] && { printf '%s|%s' "$m" "$r"; return 0; }
    done
    return 0   # kein Treffer ist kein Fehler — unter `set -e` beendete er das Skript
}

mkdir -p "$ABBILD"
pruef=0; fehl=0; ausn=0
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
    sed -i 's|^        for (var i = 0; i < lines.size(); i++) {$|        var probeUeber = -9999; var probeZeile = "";\n        for (var i = 0; i < lines.size(); i++) {|' "$APP/source/SyncView.mc"
    sed -i 's|            dc.drawText(cx, untenY, f, txt, Graphics.TEXT_JUSTIFY_CENTER);|            dc.drawText(cx, untenY, f, txt, Graphics.TEXT_JUSTIFY_CENTER);\n            var probeW = dc.getTextWidthInPixels(txt, f) - Ui.kanteW(dc, untenY, hZeile);\n            if (probeW > probeUeber) { probeUeber = probeW; probeZeile = txt; }\n            if (i == lines.size() - 1) { System.println("SYNCBREITE " + probeUeber.toString() + " " + probeZeile); }|' "$APP/source/SyncView.mc"
    if [ "${SYNCSEITE_OHNE_KURZFORM:-0}" = 1 ]; then
        sed -i 's|if (lines\[i\].size() > 2 \&\& !Ui.passt|if (false \&\& !Ui.passt|' "$APP/source/SyncView.mc"
        grep -q 'if (false && !Ui.passt' "$APP/source/SyncView.mc" || fehler "Kurzform nicht abgeschaltet"
    fi
    for muster in 'SyncProbe.fuellen' 'new SyncView(true)' 'using Toybox.System' 'SYNCPROBE' 'SYNCBREITE' 'var probeUeber'; do
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
        breite=$(bash "$P" konsole | grep '^SYNCBREITE' | tail -1 || true)
        pruef=$((pruef+1))
        if [ -z "$breite" ]; then
            echo "  FEHL  $g, $zeilen Zeilen: keine Breite in der Konsole"; fehl=$((fehl+1)); continue
        fi
        read -r _ ueber text <<<"$breite"
        a=$(ausnahme "$g" "$zeilen" "$text")
        if [ "$ueber" -le 0 ]; then
            echo "  ok    $g, $zeilen Zeilen: breiteste Zeile $((-ueber)) px innerhalb der Sehne (\"$text\")"
        elif [ -n "$a" ] && [ "$ueber" -le "${a%%|*}" ]; then
            echo "  ok    $g, $zeilen Zeilen: AUSNAHME — \"$text\" ragt $ueber px in den Sicherheitsrand (erlaubt ${a%%|*}: ${a#*|})"
            ausn=$((ausn+1))
        else
            echo "  FEHL  $g, $zeilen Zeilen: \"$text\" ragt $ueber px ueber die Sehne"; fehl=$((fehl+1))
        fi
    done
done
bash "$P" beenden >/dev/null 2>&1 || true
echo "Sync-Seite: $pruef Pruefungen, $fehl Fehlschlaege; $ausn gruen als Ausnahme (Bilder unter $ABBILD)"
[ "$fehl" = 0 ]
