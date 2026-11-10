# Sendeweg der Uhr

Eine **Prüffassung** der Garmin-App (Kopie unter `$CIQ_BASIS/sendeweg-app`)
hält jede Anfrage vor dem Absenden an und spielt die Antworten des Servers
selbst vor. **Anlass: Nr. 570** — Kopplung im Dienst, ungebundene laufende
Pakete, ungedeckeltes `next_seq`, wachsendes Wiederholungsstück.

## Aufruf

```bash
bash tools/uhr-pruefstand/sendeweg/sendeweg.sh [geraet]   # Vorgabe fenix6pro
SENDEWEG_WATCH=<verzeichnis> bash tools/uhr-pruefstand/sendeweg/sendeweg.sh   # Gegenprobe
```

## Was es misst

Acht Prüfungen in sieben Fällen, je Fall mit leerem Speicher: `Pair.start()` im Dienst
(ungekoppelt, gekoppelt; Gegenprobe: Schlüssel holen bleibt erlaubt), ein
laufender Einsatz nach Verlust der Zugangsdaten und Kopplung mit anderem
Schlüssel, ein Stück nach verlorener Antwort, eine Quittung über das
Gesendete hinaus, eine Antwort ohne `next_seq`.

## Was es braucht

Uhr-Prüfstand mit Simulator (`pruefstand.sh aufbau`, `Xvfb`). Keinen
Server — die Adresse zeigt auf `127.0.0.1:1`.

## Erwartete Zahl

`Sendeweg: 8 Pruefungen, 0 Fehlschlaege`, rund 1 min. Gegen Uhr 4.3.3
(`git archive 0be2092 watch`): 5 Fehlschläge, die drei Gegenproben grün.

## Was es nicht kann

Den echten Server: Wie `ingest.php` ein wiederholtes Stück ablegt, misst
`uhr-rundlauf`. Und keine echte Uhr, kein echter Funkabriss.
