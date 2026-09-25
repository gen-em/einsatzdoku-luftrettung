#!/usr/bin/env bash
# Rückwegprobe — beide Teile unter einem Namen (Konzept RW, E-RW-12).
# Anlass: Nr. 319 — der Rückweg nach E-P5c-42 prüfte gegen einen Wert, den jeder Datenbankabzug enthält (F-P5c-106).
#
# `probe.php` misst Bibliothek und Server ohne Browser, `probe.mjs` den echten
# Weg im Browser. Beide laufen, auch wenn der erste rot ist — ein Teil soll den
# anderen nicht verdecken. Zusatzangaben gehen an den Browser-Teil (Basis und
# Zugang des Prüfkontos, für Staging); der Server-Teil misst örtlich.
#
# EINE DATEI STATT EINER FUNKTION IN `proben.sh` (seit dem Aufnehmen von
# Konzept BR): Der Bestandsriegel liest den Anlass im Kopf der Einstiegsdatei,
# und aus einer Funktion mit zwei Aufrufen im Vordergrund kann er keine
# ermitteln (`probe-einstieg-unklar`).
#
# Aufruf: bash tools/proben/rueckweg/probe.sh [--basis … --admin … --admin-pw … --admin-totp …]
set -uo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/../../.." || exit 2
rc=0
php tools/proben/rueckweg/probe.php || rc=1
node tools/proben/rueckweg/probe.mjs "$@" || rc=1
exit "$rc"
