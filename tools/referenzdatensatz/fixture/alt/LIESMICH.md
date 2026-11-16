# Die letzte Fixture im alten Format

`fixture-format3.json.gz` ist `server/demo/fixture.json.gz` aus dem Stand `e2e4d8f`
(Web 24.19.0): Format 3, Gerätehüllen der Fassung 1, `edk1:`-Blöcke, 600 000 Runden,
die Spuren als Punkte. Seit Web 25.0.0 liefert die Anwendung Format 4 aus (KA-13).

Anlass: Nr. 563 — zwei Prüfmittel brauchen den alten Bestand, den es sonst nirgends
mehr gibt: die Bedienprobe des Anhebelaufs (`tools/bedienprobe/wege/ka06b4.mjs`, setzt
das Demo-Konto damit zurück) und die Spurprobe (`tools/proben/spur/probe.php`, misst die
Ausdünnung an den Punkten). Nicht neu erzeugen: Sie ist ein Stand, kein Erzeugnis.
