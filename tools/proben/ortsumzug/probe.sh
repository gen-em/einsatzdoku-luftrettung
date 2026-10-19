#!/usr/bin/env bash
# Probe des Einmalwerkzeugs (S11-10): Altbestand im Klartext → Hüllen, im Browser.
# Anlass: Nr. 43 — nur der Browser kann verschließen; die Probe vergleicht
# vorher und nachher.
set -u
WURZEL="$(cd "$(dirname "$0")/../../.." && pwd)"
node "$WURZEL/tools/proben/ortsumzug/probe.mjs"
