# Die letzte Fixture im alten Format

`fixture-format3.json.gz` ist `server/demo/fixture.json.gz` aus dem Stand `e2e4d8f`
(Web 24.19.0): Format 3, Gerätehüllen der Fassung 1, `edk1:`-Blöcke, 600 000 Runden,
die Spuren als Punkte. Seit Web 25.0.0 liefert die Anwendung Format 4 aus (KA-13).
Anlass: Nr. 563 — zwei Prüfmittel brauchen den alten Bestand, den es sonst nirgends mehr gibt.

## Aufruf

Kein eigener Aufruf. `demo_zuruecksetzen($pfad)` bzw. `demo_fixture_laden($pfad)` in
`server/demo_lib.php` nehmen den Pfad; so tun es `tools/bedienprobe/wege/ka06b4.mjs`
(Anhebelauf) und `tools/proben/spur/probe.php` (Ausdünnung).

## Was es misst

Nichts selbst. Die Datei liefert den Altbestand, an dem der Anhebelauf (104 Blöcke
`edk1:` → `edk2:`) und die Ausdünnung (Punkte im Klartext) gemessen werden.

## Was es braucht

`server/demo_lib.php`, das Format 1 bis 4 liest.

## Erwartete Zahl

903 114 Byte; Demo-Bestand 104 Blöcke `edk1:`, 223 Spuren, 67 016 Punkte.

## Was es nicht kann

Neu erzeugt werden: Sie ist ein Stand, kein Erzeugnis. Mit dem Rückbau des Altformats
(06e, E-KA-60) entfällt sie samt den beiden Verwendungen.
