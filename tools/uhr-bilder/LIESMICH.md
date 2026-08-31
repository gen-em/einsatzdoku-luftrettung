# Bilder der Uhr-App erzeugen

Rastert Launcher-Symbole und Bildmarken aus den beiden Vektorvorlagen unter
`server/assets/images/` in die Ressourcenordner der Uhr-App.

```bash
tools/uhr-bilder/erzeugen.sh            # alles
tools/uhr-bilder/erzeugen.sh symbole    # nur Launcher-Symbole
tools/uhr-bilder/erzeugen.sh marken     # nur Bildmarken
tools/uhr-bilder/erzeugen.sh marken 105 # nur eine Kachelgröße
```

**Wann laufen lassen:** immer, wenn eine der beiden SVG sich ändert, und wenn
eine Größe dazukommt. Danach `tools/uhr-pruefstand/pruefstand.sh reihe`, um zu
sehen, dass alle Geräte weiter übersetzen.

## Warum es dieses Werkzeug gibt

Die PNG unter `watch/resources*/drawables/` sind **Ableitungen**. Bis Uhr
1.10.2 lagen sie ohne Rezept im Repositorium: Wer eine Größe ergänzen wollte,
musste raten, mit welcher Breite und welcher Ausrichtung die vorhandenen
entstanden waren. Genau davor warnt `tools/logos/LIESMICH.md` für das Favicon —
„das PNG ist eine Ableitung und soll keine sein, die jemand in einem
Bildprogramm nachbaut".

Das Rezept unten ist aus den vorhandenen Dateien **zurückgerechnet**. Es
reproduziert die vier, die es vor diesem Werkzeug schon gab, bitgleich
(`compare -metric AE` liefert 0). Das ist der Beleg, dass es das richtige ist
und nicht bloß ein ähnliches.

Eine Ausnahme: Die beiden **Launcher-Symbole** (40 und 70 px) stammten aus der
Zeit vor der Vektorumstellung und waren aus einem 40-px-Bitmap hochskaliert.
Sie kommen jetzt aus derselben Vorlage wie alles andere — der einzige
beabsichtigte Unterschied zum Altstand (RMSE 5,9 % auf der 70er Fassung).

## Das Rezept

| | Vorlage | Breite | Kachel |
|---|---|---|---|
| Bildmarke *luft* | `gen-em_logo_helicopter_weiss.svg` | volle Kachelbreite | quadratisch, mittig, durchsichtig |
| Bildmarke *boden* | `gen-em_logo_nef_weiss.svg` | **78 %** der Kachelbreite, **abgerundet** | dito |
| Launcher-Symbol | `gen-em_logo_helicopter_weiss.svg` | volle Kachelbreite | dito |

**Warum 78 % für das NEF:** Seine Vorlage ist quadratisch (420 × 420), die des
Hubschraubers liegt quer (400 × 250). Blind in dieselbe Kachel gesetzt, wäre
das NEF deutlich schwerer erschienen; auf 78 % sind beide Motive praktisch
gleich hoch.

**Warum abgerundet und nicht gerundet:** 70 × 0,78 = 54,6. Abgerundet 54 — das
ist die vorhandene Datei. Kaufmännisch gerundet entstünde 55, und der Altstand
wäre nicht mehr reproduzierbar. Ein Pixel, an dem die ganze Beweisführung
hängt.

**Warum das Symbol immer der Hubschrauber ist:** Es wird beim Übersetzen in die
App gebacken. Der Einstellung `logoWahl` kann es deshalb nicht folgen — eine
App hat im Gerätemenü genau ein Symbol.

## Welche Größen und warum

**Launcher-Symbole** (35, 36, 40, 54, 56, 60, 61, 65, 70 px): Das ist keine
Wahl, sondern eine Vorgabe. Jedes Gerät nennt seine Größe in der
`compiler.json` unter `launcherIcon.width`; die neun sind die Vereinigung über
die 99 Zielgeräte. Fehlt die passende, skaliert `monkeyc` und meldet es als
Warnung — vor Uhr 1.10.2 traf das **42 der 99 Geräte**.

**Bildmarken:** eine Kachel je vorkommender Displayhöhe, nach der Regel

```
Kachelhöhe = 70/260 × Displayhöhe   (rund 27 %)
```

70/260 ist das Verhältnis des Bezugsgeräts fenix6pro, dem `Ui.s()` ohnehin jede
Länge der Uhr-Oberfläche folgt (`docs/Uhr-Layout_Regeln.md`, Abschnitt 2). Die
Bildmarke konnte ihm als Bitmap nicht folgen: `dc.drawBitmap` zeichnet 1:1.
Vorgerasterte Stufen holen das nach.

Die Regel ist nicht erfunden, sondern die vorhandene: Die beiden Größen, die es
vor der Staffelung gab, liegen exakt auf dieser Geraden — 260 → 70 und
390 → 105.

## Wo die Bilder landen

| Ordner | Inhalt |
|---|---|
| `watch/resources/` | Grundordner: Symbol 40 px, Kachel 70 px |
| `watch/resources-icon<N>/` | **nur** das Launcher-Symbol in N Pixeln |
| `watch/resources-marke<K>/` | **nur** die Bildmarke in einer Kachel von K Pixeln |

Getrennt, weil die beiden Größen nicht miteinander laufen: Ein Gerät mit
60-px-Symbol gibt es bei 360, 390, 416 und 454 Pixeln Displayhöhe. Wären
Symbol und Marke in einem Ordner, bräuchte es eine Kombination je Paar — 18
Ordner statt 14.

Welches Gerät welche Ordner bekommt, erzeugt
`tools/uhr-pruefstand/geraeteklassen.py --bloecke`; die Zeilen gehören
unverändert in `watch/monkey.jungle`.

## Voraussetzungen

`rsvg-convert` (Paket `librsvg2-bin`) und `convert` (Paket `imagemagick`).
Bewusst **nicht** der Weg über Chromium, den `tools/logos/erzeugen.mjs` geht:
Dort ist der Browser ohnehin für den Bilderlauf vorhanden; hier wäre er eine
Abhängigkeit für eine Aufgabe, die zwei kleine Kommandozeilenwerkzeuge
erledigen.
