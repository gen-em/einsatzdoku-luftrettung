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

## Der NEF-Platzhalter ist abgelöst

`gen-em_logo_fahrzeug.svg` und `_weiss.svg` waren **Platzhalter** (E-P3-19):
Sie standen dort, damit die Logo-Wahl aus E-P3-20 vollständig gebaut, bedient
und geprüft werden konnte, bevor die echte Datei vorlag. Maße und Fassungen
waren die des Hubschrauber-Logos.

Sie sind ersetzt — durch `gen-em_logo_nef.svg` und `_weiss.svg`, mit einem
**anderen** Namen und einem **anderen** viewBox (420 × 420 statt
400,16 × 249,81). Der Austausch war damit nicht das angekündigte 1:1, und das
hat Spuren hinterlassen:

- Dieses Werkzeug zeigte danach auf Dateien, die es nicht mehr gibt, und brach
  bei jedem Aufruf mit `ENOENT` ab. Berichtigt mit Uhr 1.10.2 — auch die
  Zielnamen, die inzwischen `favicon_helicopter.png` und `favicon_nef.png`
  heißen.
- Der Server las die alten Namen weiter; das betraf unter anderem das Favicon
  **jeder** Seite. Eigener Zweig, s. Changelog Web 9.14.1.

**Wann laufen lassen:** immer, wenn eine Logodatei sich ändert. Das Ergebnis
ist von den Dateien im Repositorium nicht zu unterscheiden — geprüft am
31.08.2026: gleiche Zeichnung, andere Kantenglättung, 2 035 statt 1 487 Byte.

## Die Uhr geht einen anderen Weg

Die Bilder der Uhr-App (`watch/resources*/drawables/`) stammen aus denselben
SVG, entstehen aber mit `tools/uhr-bilder/erzeugen.sh` über `rsvg-convert`.
Grund: Dort werden **21 Größen** gebraucht, jede mit einer eigenen Regel für
Breite und Kachel — das ist Rechnung, nicht Bildschirmfoto. Hier genügt der
Browser, der für den Bilderlauf ohnehin bereitsteht.
