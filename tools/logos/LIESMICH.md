# Favicons aus den Logodateien erzeugen

Die Anwendung liefert je Logo drei Fassungen aus: farbig (SVG), weiß (SVG,
für die dunkle Kopfleiste) und Favicon (PNG, 64 × 64). Die beiden SVG werden
von Hand gepflegt; das PNG ist eine **Ableitung** und soll keine sein, die
jemand in einem Bildprogramm nachbaut.

Genau das war der Fall: Die Logodateien trugen bis P3 Näherungen der
Markenfarben (Rot `#E3322B` statt `#D63338`, Blau `#587ABC` statt `#4280E5`,
Orange `#F7941D` statt `#FF8F1F`), und das Favicon trug sie mit. Beides ist
in P3/O1 berichtigt; die verbindlichen Markenwerte stehen in `docs/Design.md`,
Abschnitt 2.

```
node tools/logos/erzeugen.mjs
```

Erzeugt aus jeder Quelldatei ein PNG mit durchsichtigem Grund, in das die
Zeichnung vollständig hineinpasst — dieselbe Bauform wie das bisherige
`favicon.png`. Chromium kommt aus Playwright; ein eigener Bildkonverter wäre
eine weitere Abhängigkeit für eine Aufgabe, die der Browser ohnehin kann.

**Wann laufen lassen:** immer, wenn eine Logodatei sich ändert — insbesondere,
wenn das echte NEF-Logo den Platzhalter ersetzt.

## Der NEF-Platzhalter

`gen-em_logo_fahrzeug.svg` und `_weiss.svg` sind **Platzhalter** (E-P3-19).
Sie stehen dort, damit die Logo-Wahl aus E-P3-20 vollständig gebaut, bedient
und geprüft werden kann, bevor die echte Datei vorliegt. Maße und Fassungen
sind die des Hubschrauber-Logos (`viewBox 400.16 × 249.81`); die echte Datei
ersetzt sie **1:1** — gleicher Name, gleicher viewBox, kein Eingriff im Code.
Danach `erzeugen.mjs` laufen lassen.

Als Platzhalter erkennbar sind sie am gestrichelten Rahmen in Sand. Solange
er liegt, weist außerdem die Wartungsseite darauf hin.
