# Sendeweg der Uhr

Eine **Prüffassung** der Garmin-App (Kopie unter `$CIQ_BASIS/sendeweg-app`)
hält jede Anfrage vor dem Absenden an und spielt die Antworten des Servers
selbst vor. **Anlass: Nr. 570, 573, 575, 577** — Kopplung im Dienst, laufende
Pakete, `next_seq`, Wiederholungsstück; 401/403 ohne Fehlerfeld, Frist;
Dienst ohne Kopplung, Trennen; Kopplung mit Vergleichswert, Fassung 2.

## Aufruf

```bash
bash tools/uhr-pruefstand/sendeweg/sendeweg.sh [geraet]   # Vorgabe fenix6pro
SENDEWEG_WATCH=<verzeichnis> bash tools/uhr-pruefstand/sendeweg/sendeweg.sh   # Gegenprobe
```

## Was es misst

22 Prüfungen in 19 Fällen, je Fall mit leerem Speicher: `Pair.start()` im Dienst
(ungekoppelt, gekoppelt; Gegenprobe: Schlüssel holen bleibt erlaubt), ein
laufender Einsatz nach Verlust der Zugangsdaten und Kopplung mit anderem
Schlüssel, ein Stück nach verlorener Antwort, eine Quittung über das
Gesendete hinaus, eine Antwort ohne `next_seq`; seit KG-06 401 und 403 ohne
Fehlerfeld, 401 `auth` (Gegenprobe), die abgelaufene Frist mit letztem Versuch;
seit KG-08 kurz START ungekoppelt und `Pair.onTrennen(200)` ohne neue Sitzung;
seit KG-03b Wert erst nach `offenlegen`, anderes Paar, 202, abgelehnt,
`huelle: 2`, leeres Schlussstück in Fassung 2, Gegenprobe Fassung 1.

## Was es braucht

Uhr-Prüfstand mit Simulator (`pruefstand.sh aufbau`, `Xvfb`). Keinen Server (`127.0.0.1:1`).

## Erwartete Zahl

`Sendeweg: 15 Pruefungen, 0 Fehlschlaege`, rund 1 min; gegen Uhr 4.5.2 zwei rot (KG-08).
Für 13 Prüfungen, nicht nachgemessen: gegen 4.5.0 drei rot, gegen 4.3.3 fünf.

## Was es nicht kann

Den echten Server (das misst `uhr-rundlauf`), keine echte Uhr, kein echter Funkabriss.
