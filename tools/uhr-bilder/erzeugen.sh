#!/usr/bin/env bash
# Launcher-Symbole und Bildmarken der Uhr-App aus den Vektorvorlagen rastern.
#
# WOZU. Die PNG unter watch/resources*/drawables/ sind ABLEITUNGEN der beiden
# SVG in server/assets/images/. Bis hierher lagen sie ohne Rezept im
# Repositorium: Wer eine Groesse ergaenzen wollte, musste raten, mit welcher
# Breite und welcher Ausrichtung die vorhandenen entstanden waren. Dasselbe
# Problem beschreibt tools/logos/LIESMICH.md fuer das Favicon — „das PNG ist
# eine Ableitung und soll keine sein, die jemand in einem Bildprogramm
# nachbaut".
#
# Das Rezept ist aus den vorhandenen Dateien zurueckgerechnet und reproduziert
# sie BITGLEICH (geprueft mit `compare -metric AE`, Ergebnis 0 fuer alle vier
# Dateien, die es vorher schon gab).
#
# Anleitung: LIESMICH.md im selben Verzeichnis.

set -euo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
HELI="$WURZEL/server/assets/images/gen-em_logo_helicopter_weiss.svg"
NEF="$WURZEL/server/assets/images/gen-em_logo_nef_weiss.svg"
UHR="$WURZEL/watch"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

melde() { printf '\033[1m==\033[0m %s\n' "$*"; }

command -v rsvg-convert >/dev/null || { echo "rsvg-convert fehlt (Paket librsvg2-bin)"; exit 1; }
command -v convert      >/dev/null || { echo "convert fehlt (Paket imagemagick)";     exit 1; }

# Groessen der Launcher-Symbole. Welches Geraet welche verlangt, steht in
# seiner compiler.json unter launcherIcon.width; die Liste hier ist die
# Vereinigung ueber die 99 Zielgeraete. 40 liegt im Grundordner, weil ein neu
# eingetragenes Geraet ohne eigene Zuweisung damit am wenigsten falsch liegt
# (51 der 99 Geraete verlangen genau 40).
SYMBOLE="35 36 54 56 60 61 65 70"

# Kachelgroessen der Bildmarke — VIER STUFEN ueber die zehn vorkommenden
# Displayhoehen. Zielwert ist 27 % der Displayhoehe (genauer 70/260, das
# Verhaeltnis des Bezugsgeraets fenix6pro, dem `Ui.s()` ohnehin jede Laenge
# der Oberflaeche folgt). Die Bildmarke konnte ihm als Bitmap nicht folgen —
# `dc.drawBitmap` zeichnet 1:1; vorgerasterte Stufen holen das nach.
#
# Die Stufengrenzen sind nicht geschaetzt, sondern gerechnet: Fuer jede
# Stufenzahl wurde die Aufteilung gesucht, die die groesste Abweichung vom
# Zielwert klein haelt. Vier Stufen ergeben 25,0-28,8 %; heute reicht die
# Spanne von 15 % bis 34 %.
#
#   Stufen  Spanne         warum nicht
#   3       23,6-30,4 %    oben und unten noch deutlich daneben
#   4       25,0-28,8 %    <- gewaehlt
#   5       25,3-28,4 %    die fuenfte Stufe traegt EIN Geraet (FR 55)
#   10      26,8-27,1 %    eine Kachel je Hoehe, 8 Ordner statt 3
#
# 73 liegt im Grundordner: Es ist die Stufe des Bezugsgeraets, und ein neu
# eingetragenes Geraet ohne eigene Zeile liegt damit am wenigsten falsch.
# Format: <Displayhoehe>:<Kachel>
KACHELN="208:60 218:60 240:60 260:73 280:73 360:101 390:101 416:118 454:118 466:118"
KACHEL_GRUND=73

# Hubschrauber auf die volle Kachelbreite, senkrecht mittig in eine
# durchsichtige quadratische Kachel. Das Seitenverhaeltnis 400,16:249,81
# ergibt bei Breite b die Hoehe b/1,602.
luft() {   # luft <kante> <zieldatei>
    local k="$1" ziel="$2"
    rsvg-convert -w "$k" "$HELI" -o "$TMP/x.png"
    convert "$TMP/x.png" -background none -gravity center -extent "${k}x${k}" "$ziel"
}

# Das NEF steht auf 78 % der Kachelbreite. Grund: Seine Vorlage ist
# quadratisch (420x420), die des Hubschraubers liegt quer (400x250). Blind in
# dieselbe Kachel gesetzt, waere das NEF deutlich schwerer erschienen; auf
# 78 % sind beide Motive praktisch gleich hoch.
# ABGERUNDET, nicht gerundet: 70*0,78 = 54,6 -> 54. Mit kaufmaennischer
# Rundung entstuende 55, und die vorhandene Datei waere nicht mehr
# reproduzierbar.
boden() {  # boden <kante> <zieldatei>
    local k="$1" ziel="$2" b
    b=$(python3 -c "print(int($k * 0.78))")
    rsvg-convert -w "$b" "$NEF" -o "$TMP/x.png"
    convert "$TMP/x.png" -background none -gravity center -extent "${k}x${k}" "$ziel"
}

symbole() {
    melde "Launcher-Symbole"
    # Das Symbol ist IMMER der Hubschrauber. Es wird beim Uebersetzen in die
    # App gebacken und kann der Einstellung "logoWahl" deshalb nicht folgen —
    # eine App hat im Geraetemenue genau ein Symbol.
    luft 40 "$UHR/resources/drawables/launcher_icon.png"
    printf '  %-28s %s\n' "resources" "40 px"
    for s in $SYMBOLE; do
        local z="$UHR/resources-icon$s/drawables"
        mkdir -p "$z"
        luft "$s" "$z/launcher_icon.png"
        cat > "$z/drawables.xml" <<XML
<drawables>
    <bitmap id="LauncherIcon" filename="launcher_icon.png"/>
</drawables>
XML
        printf '  %-28s %s\n' "resources-icon$s" "$s px"
    done
}

# Ohne Argument alle Kachelgroessen, sonst nur die genannten — waehrend der
# Umstellung stand nur ein Teil der Ordner im Jungle, und eine Datei ohne
# Abnehmer im Repositorium ist eine Frage, die sich niemand mehr beantworten
# kann.
marken() {
    melde "Bildmarken"
    local nur="$*"
    for paar in $KACHELN; do
        if [ -n "$nur" ]; then
            case " $nur " in *" ${paar##*:} "*) ;; *) continue ;; esac
        fi
        local h="${paar%%:*}" k="${paar##*:}" z
        if [ "$k" = "$KACHEL_GRUND" ]; then
            z="$UHR/resources/drawables"          # Grundordner
        else
            z="$UHR/resources-marke$k/drawables"
            mkdir -p "$z"
            cat > "$z/drawables.xml" <<XML
<drawables>
    <bitmap id="LogoLuft"  filename="logo_luft.png"/>
    <bitmap id="LogoBoden" filename="logo_boden.png"/>
</drawables>
XML
        fi
        luft  "$k" "$z/logo_luft.png"
        boden "$k" "$z/logo_boden.png"
        printf '  %-28s %s px  (Display %s px, %s %%)\n' \
            "$(basename "$(dirname "$z")")" "$k" "$h" \
            "$(python3 -c "print(round(100*$k/$h))")"
    done
}

case "${1:-alle}" in
    symbole) symbole ;;
    marken)  shift; marken "$@" ;;
    alle)    symbole; marken ;;
    *) echo "Aufruf: $(basename "$0") [alle|symbole|marken [<kachel> ...]]"; exit 1 ;;
esac
