#!/usr/bin/env bash
# Bildmarken der App aus den Vektorvorlagen holen -- das Rezept dazu.
#
# WOZU. Die vier PNG unter gemeinsam/res/drawable-nodpi/ sind ABLEITUNGEN. Ohne
# ein Rezept muesste jemand, der eine ersetzen will, raten, woher sie kamen und
# mit welcher Breite -- genau der Zustand, den tools/uhr-bilder/ fuer die
# Garmin-Uhr und tools/logos/ fuer das Favicon beseitigt haben.
#
# WARUM NICHT AUS DEN SVG GERASTERT. tools/uhr-bilder/erzeugen.sh rastert mit
# rsvg-convert und ImageMagick aus den beiden SVG. Beide Werkzeuge liegen im
# Bau-Container NICHT, und eine App-Ressource, die sich nur auf einem
# Arbeitsplatz erzeugen laesst, ist keine gute Ressource.
#
# Sie sind hier auch nicht noetig: server/assets/images/ fuehrt dieselben vier
# Motive bereits als PNG in 500 px Breite -- gerastert aus denselben Vorlagen,
# fuer die Weboberflaeche. Android skaliert eine Bitmap ohnehin auf die
# angeforderte Groesse; 500 px reichen fuer den Kopf der Handy-App (rund 20 dp)
# und fuer ein Sechstel der Displayhoehe einer Uhr (rund 75 px) um ein
# Vielfaches. Deshalb -nodpi: EINE Datei fuer alle Dichten, statt fuenf
# Ableitungen derselben Ableitung.
#
# WELCHE FASSUNG WOFUER (Design.md 2.3, E-S4-22b):
#   *_farbig   auf hellem Grund -- Startseite und Erklaerflaechen des Handys
#   *_weiss    auf Dunkel -- der dunkelblaue Kopf des Handys UND die Uhr
#              (dort heisst dieselbe Fassung "Dunkelgrund-Fassung": nur die
#              farbtragenden Elemente, der dunkle Korpus wird weiss). Es sind
#              dieselben Vorlagen, aus denen die Garmin-Uhr seit 1.10.3 ihre
#              logo_luft.png / logo_boden.png rastert.
#
# ACHTUNG, offener Fund B-S4-01: Die SVG tragen teilweise noch die ALTEN
# Farbwerte (#587abc, #e3322b, #f7941d, Korpus #1d0e0a). Am 31.08.2026 wurde
# entschieden, das bewusst liegen zu lassen (Backlog Nr. 62); die App erbt den
# jeweils aktuellen Stand der Dateien. Wer die Vorlagen berichtigt, laesst
# dieses Skript danach laufen -- sonst zeigt die App weiter die alten Werte.
#
# Aufruf:  werkzeuge/bildmarken.sh          # holen und Pruefsummen nennen
#          werkzeuge/bildmarken.sh pruefen  # nur pruefen, nichts schreiben

set -euo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
QUELLE="$WURZEL/server/assets/images"
ZIEL="$WURZEL/android/gemeinsam/res/drawable-nodpi"

# <Quelldatei>:<Zieldatei>
PAARE="
gen-em_logo_helicopter.png:marke_luft_farbig.png
gen-em_logo_helicopter_weiss.png:marke_luft_weiss.png
gen-em_logo_nef.png:marke_boden_farbig.png
gen-em_logo_nef_weiss.png:marke_boden_weiss.png
"

nur_pruefen=0
[ "${1:-}" = "pruefen" ] && nur_pruefen=1

abweichungen=0
for paar in $PAARE; do
    q="$QUELLE/${paar%%:*}"
    z="$ZIEL/${paar##*:}"
    if [ ! -f "$q" ]; then echo "FEHLT: $q"; exit 1; fi
    if [ "$nur_pruefen" = 0 ]; then cp "$q" "$z"; fi
    if [ -f "$z" ] && cmp -s "$q" "$z"; then
        printf '  %-26s <- %-34s %s\n' "$(basename "$z")" "$(basename "$q")" \
               "$(sha256sum "$z" | cut -c1-16)"
    else
        printf '  %-26s ABWEICHUNG gegen %s\n' "$(basename "$z")" "$(basename "$q")"
        abweichungen=$((abweichungen + 1))
    fi
done

echo "Abweichungen: $abweichungen"
exit $(( abweichungen > 0 ? 1 : 0 ))
