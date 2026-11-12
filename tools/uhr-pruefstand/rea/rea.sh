#!/usr/bin/env bash
# Probe der Reanimation auf der Garmin-Uhr — Tasten, Sperre, Menue, Pause,
# Vibrationen und Erinnerungen des Countdowns.
#
# Anlass: Nr. 571, Nr. 574 — bis Uhr 4.4.0 lag die Rhythmuskontrolle auf lang DOWN,
# die Bestaetigung einer Buchung ging im Doppelpuls des Countdown-Neustarts
# unter, und vor dem Ende des Zyklus gab es keine Erinnerung.
#
# WAS SIE TUT. Eine Prueffassung (Kopie von watch/ unter $CIQ_BASIS/rea-app)
# startet direkt auf der Reanimationsseite, der Countdown dauert 40 s statt
# 120 s. ReaProbe.mc schreibt jeden Vibrationsaufruf, jedes gebuchte Ereignis,
# jeden Seitenwechsel und das Oeffnen von Menue und Uebersicht in die
# Konsole. Die Tasten gehen mit getrenntem keydown/keyup hinaus (kurz 0,15 s,
# lang 1,6 s); nach jedem Schritt vergleicht das Skript, was dazukam, mit dem
# Soll.
#
# WAS SIE NICHT ZEIGT: den Fehler, dass kurz UP/DOWN nach einem Seitenwechsel
# buchte (F-KG-01). Er haengt an der Reihenfolge von Tastenereignis und
# Ansichtswechsel auf dem Geraet; der Simulator erzeugt ihn nicht
# (Geraete-Eingabe.md 6). Belegt ist hier nur, dass die Seite waehrend einer
# Rea nicht mehr blaettert.
#
# GEGENPROBE: REA_WATCH=<verzeichnis> nimmt einen anderen Stand von watch/,
# etwa Uhr 4.4.0. Gegen ihn muss die Probe rot sein.
#
# AUFRUF   bash tools/uhr-pruefstand/rea/rea.sh [geraet]
#          (Vorgabe fenix6pro — ein Geraet mit UP/DOWN; braucht `pruefstand.sh aufbau`)
set -euo pipefail

WURZEL="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
P="$WURZEL/tools/uhr-pruefstand/pruefstand.sh"
BASIS="${CIQ_BASIS:-$HOME/.ciq-pruefstand}"
APP="$BASIS/rea-app"
QUELLE="${REA_WATCH:-$WURZEL/watch}"
GERAET="${1:-fenix6pro}"
export DISPLAY="${CIQ_DISPLAY:-:99}"

melde()  { printf '\033[1m==\033[0m %s\n' "$*"; }
fehler() { printf '\033[31mFEHLER:\033[0m %s\n' "$*" >&2; exit 1; }

melde "Prueffassung bauen ($GERAET, aus $QUELLE)"
rm -rf "$APP"; cp -r "$QUELLE" "$APP"
cp "$WURZEL/tools/uhr-pruefstand/rea/ReaProbe.mc" "$APP/source/ReaProbe.mc"
S="$APP/source"
sed -i 's|^    function _vibe(p as Lang.Array) as Void {$|&\n        ReaProbe.vib(p);|' "$S/Util.mc"
sed -i 's|new Attention.VibeProfile(|ReaProbe.profil(|g' "$S/Util.mc"
sed -i 's|^        (cur\["events"\] as Lang.Array).add(\[type, Util.isoNow(), Util.localHHMM()\]);$|&\n        ReaProbe.ereignis(type);|' "$S/Model.mc"
sed -i 's|^        index = (index + delta + PAGES.size()) % PAGES.size();$|&\n        ReaProbe.seite(index.toString());|' "$S/Nav.mc"
sed -i 's|^        if (index < 0) { index = 0; }$|&\n        ReaProbe.seite(index.toString());|' "$S/Nav.mc"
sed -i 's|^        var v = new CprMenuView();$|&\n        ReaProbe.menue(CprMenuView.ITEMS[v.index][0] as Lang.String);|' "$S/CprView.mc"
sed -i 's|^        var v = new ResusOverviewView();$|&\n        ReaProbe.uebersicht();|' "$S/CprView.mc"
# Schnellmenue der Hauptanzeige (KG-07): Oeffnen und jede Auswahl darin.
sed -i 's|^        var v = new QuickMenuView();$|&\n        ReaProbe.menue("SCHNELLMENUE");|' "$S/ClockView.mc"
sed -i 's|^    function onSelect() as Lang.Boolean {                 // START$|&\n        ReaProbe.menue("SCHNELL-AUSWAHL");|' "$S/ClockView.mc"
sed -i 's|        return \[new StartView(), new StartDelegate()\];|        Nav.index = 4;\n        return [new CprView(), new CprDelegate()];|' "$S/NAdokuApp.mc"
sed -i 's|^    const CPR_CYCLE_S = 120;|    const CPR_CYCLE_S = 40; |' "$S/Const.mc"
for muster in 'SCHNELLMENUE' 'SCHNELL-AUSWAHL' 'ReaProbe.vib' 'ReaProbe.profil(' 'ReaProbe.ereignis' 'ReaProbe.menue' 'ReaProbe.uebersicht' 'new CprView(), new CprDelegate()' 'CPR_CYCLE_S = 40;'; do
    grep -rqF "$muster" "$S" || fehler "Einhaengen gescheitert: $muster"
done
[ "$(grep -c 'ReaProbe.seite' "$S/Nav.mc")" = 2 ] || fehler "Einhaengen gescheitert: ReaProbe.seite (zweimal erwartet)"

BAU=$(bash "$P" bauen "$GERAET" "$APP/monkey.jungle" 2>&1) || true
case "$BAU" in *"BUILD SUCCESSFUL"*) ;; *) printf '%s\n' "$BAU" | tail -20; fehler "Uebersetzen gescheitert";; esac
bash "$P" beenden >/dev/null 2>&1 || true
bash "$P" einstellungen-leeren >/dev/null
bash "$P" speicher-leeren >/dev/null
melde "Starten"
timeout 90 bash "$P" starten "$GERAET" 25 >/dev/null 2>&1 || true
sleep 3

WID=$(xdotool search --onlyvisible --name "CIQ Simulator" | head -1)
[ -n "$WID" ] || fehler "Kein Simulatorfenster"
druck() {   # druck <taste> <sekunden>
    xdotool windowfocus "$WID"; sleep 0.3
    xdotool keydown "$1"; sleep "$2"; xdotool keyup "$1"; sleep 1.2
}
kurz() { druck "$1" 0.15; }
lang() { druck "$1" 1.6; }
konsole() { bash "$P" konsole | grep '^REA' || true; }

# Der erste Druck nach dem Laden geht regelmaessig verloren (pruefstand.sh,
# taste) -- deshalb vorweg zwei, die auf der Reanimationsseite ohne Rea
# nichts tun: lang START. Dazu eine Zeile, die die Konsole in Gang bringt.
sleep 3
lang Return
lang Return
sleep 3

pruef=0; fehl=0
STAND=$(konsole)
# schritt <name> <art> <soll>: Die neuen Konsolenzeilen seit dem letzten
# Schritt, in Reihenfolge, durch | getrennt. Art "kurz" laesst die langen
# Muster (2000 ms) weg -- sie gehoeren dem Countdown und laufen neben den
# Tastenschritten her --, Art "lang" laesst nur sie stehen.
schritt() {
    # Die Konsole des Simulators meldet mit Verzoegerung: bis zu 6 s auf das
    # Soll warten; ein leeres Soll wartet die ganze Zeit, damit nichts
    # Verspaetetes durchrutscht.
    local neu jetzt i
    for i in $(seq 1 12); do
        sleep 0.5
        jetzt=$(konsole)
        neu=$( { diff <(printf '%s\n' "$STAND") <(printf '%s\n' "$jetzt") || true; } | sed -n 's/^> //p')
        if [ "$2" = kurz ]; then neu=$(printf '%s\n' "$neu" | grep -v ' 2000' || true)
        else neu=$(printf '%s\n' "$neu" | grep 'REAVIB 2000' || true); fi
        neu=$(printf '%s\n' "$neu" | sed '/^$/d' | paste -sd'|' -)
        [ -n "$3" ] && [ "$neu" = "$3" ] && break
    done
    STAND="$jetzt"
    pruef=$((pruef+1))
    if [ "$neu" = "$3" ]; then
        echo "  ok    $1: ${neu:-nichts}"
    else
        echo "  FEHL  $1: erwartet \"${3:-nichts}\", gesehen \"${neu:-nichts}\""; fehl=$((fehl+1))
    fi
}

K1="REAVIB 200 @100"
K2="REAVIB 200,200,200 @100"
L1="REAVIB 2000 @100"
L2="REAVIB 2000,600,2000 @100"
L3="REAVIB 2000,600,2000,600,2000 @100"

# Ohne Rea blaettert die Seite, und zwar genau eine Seite (F-KG-09: in
# Uhr 4.4.0 im Simulator zwei).
kurz Up;     schritt "ohne Rea: kurz UP blaettert eine Seite" kurz "REANAV 3"
kurz Down;   schritt "ohne Rea: kurz DOWN zurueck, eine Seite" kurz "REANAV 4"

# Tasten
kurz Return; schritt "kurz START ohne Rea beginnt" kurz "$K2"
kurz Return; schritt "kurz START gleich danach: Sperre" kurz ""
sleep 6
kurz Return; schritt "kurz START nach 10 s: Rhythmuskontrolle" kurz "REAEVENT rhythmuskontrolle|$K2"
kurz Up;     schritt "kurz UP: Adrenalin, kein Blaettern" kurz "REAEVENT adrenalin|$K1"
kurz Down;   schritt "kurz DOWN: Defibrillation, kein Blaettern" kurz "REAEVENT defibrillation|$K2"
kurz Up;     schritt "kurz UP gleich danach: Sperre" kurz ""
lang Return; schritt "lang START: Menue auf Amiodaron, nichts gebucht" kurz "REAMENU Amiodaron"
kurz Return; schritt "Amiodaron aus dem Menue" kurz "REAEVENT amiodaron|$K1"
lang Up;     schritt "lang UP nach 10 s: genau ein Adrenalin" kurz "REAEVENT adrenalin|$K1"

# Countdown: 40 s ab der Defibrillation -> 0:30 bei +10 s, 0:10 bei +30 s,
# 0:00 bei +40 s. Vorher klingt aus, was die Schritte oben angestossen haben.
sleep 12
kurz Down;   schritt "Defibrillation startet den Countdown neu" kurz "REAEVENT defibrillation|$K2"
STAND=$(konsole)
T=$(date +%s)
while [ $(( $(date +%s) - T )) -lt 44 ]; do sleep 1; done
schritt "Countdown: 0:30, 0:10, 0:00 in dieser Folge" lang "$L1|$L2|$L3"

# Pause und Verlassen
lang Return
for _ in 1 2 3 4 5 6; do kurz Down; done
kurz Return; schritt "Menue: Rea BEENDEN haelt an, Uebersicht" kurz "REAMENU Amiodaron|REAUEBERSICHT"
kurz Escape
kurz Up;     schritt "pausiert: UP bucht nichts, blaettert nicht" kurz ""
kurz Return; schritt "pausiert: kurz START oeffnet die Uebersicht" kurz "REAUEBERSICHT"
kurz Escape
kurz Escape; schritt "BACK: Hauptanzeige" kurz "REANAV 0"
# Schnellmenue der Hauptanzeige (F-KG-08): oeffnet beim Loslassen, waehlt nichts.
lang Return; schritt "Hauptanzeige: lang START oeffnet das Schnellmenue, waehlt nichts" kurz "REAMENU SCHNELLMENUE"
kurz Escape
bash "$P" beenden >/dev/null 2>&1 || true

SOLL=18
[ "$pruef" = "$SOLL" ] || { echo "  FEHL  $pruef Pruefungen statt $SOLL"; fehl=$((fehl+1)); }
echo "Reanimation: $pruef Pruefungen, $fehl Fehlschlaege ($GERAET)"
[ "$fehl" = 0 ]
