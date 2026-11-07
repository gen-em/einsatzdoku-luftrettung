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
# Motive bereits als PNG in 500 px Breite -- gerastert aus denselben Vorlagen
# (tools/erzeugen/logos.mjs). Android skaliert eine Bitmap ohnehin auf die
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
# SEIT SCHRITT 26 (Konzept MA) sind die Quellen die Produktmarke NAdoku aus
# dem Branding-Repo (Grundform Dunkelblau, Markenfarben exakt; Backlog
# Nr. 62). Die Fassung, die die App "weiss" nennt, heisst dort
# "farbig-negativ" (Grundform Schnee, Federfarben bleiben) -- dieselbe
# Aussage wie vorher. Die PNG erzeugt tools/erzeugen/logos.mjs. Bis dahin
# stand hier der offene Fund B-S4-01 (alte Farbwerte in den Vorlagen).
#
# Aufruf:  werkzeuge/bildmarken.sh          # holen und Pruefsummen nennen
#          werkzeuge/bildmarken.sh pruefen  # nur pruefen, nichts schreiben

set -euo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
QUELLE="$WURZEL/server/assets/images"
ZIEL="$WURZEL/android/gemeinsam/res/drawable-nodpi"

# <Quelldatei>:<Zieldatei>
PAARE="
nadoku-hubschrauber-farbig.png:marke_luft_farbig.png
nadoku-hubschrauber-farbig-negativ.png:marke_luft_weiss.png
nadoku-nef-farbig.png:marke_boden_farbig.png
nadoku-nef-farbig-negativ.png:marke_boden_weiss.png
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
