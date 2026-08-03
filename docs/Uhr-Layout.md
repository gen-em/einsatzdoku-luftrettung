# Einsatzdoku — Layoutregeln der Uhr-Oberflächen

Was beim Zeichnen auf Garmin-Uhren zu beachten ist. **Referenzdokument**: Wer
eine Oberfläche anfasst oder ein neues Gerät aufnimmt, liest zuerst hier.

Jede Regel steht hier, weil sie einmal verletzt wurde und der Fehler erst am
Simulator aufgefallen ist. Die Ursachen sind unauffällig — der Code sah in
allen Fällen richtig aus.

Zum Eingabeverhalten der Geräte siehe `Geraete-Eingabe.md`, zu Build und
Geräteprofilen `Technik.md` Abschnitt 5.

---

## 1. Der Werkzeugkasten in `Ui.mc`

| Funktion | Zweck |
|---|---|
| `Ui.s(dc, v)` | Länge `v` in **Bezugspixeln** auf das Gerät umrechnen |
| `Ui.pct(dc, p)` | Anteil der Displayhöhe in Prozent |
| `Ui.numH(dc, font)` | **sichtbare** Höhe einer Schrift (ohne leere Unterlänge) |
| `Ui.chordW(dc, y)` | nutzbare Breite des runden Displays in Höhe `y` |
| `Ui.fitFont(dc, txt, top, lineH, fonts)` | größte Schrift, die in dieser Zeile passt |
| `Ui.fontHint(dc)` | Schrift für Hinweiszeilen, auf großen Displays eine Stufe größer |
| `Ui.drawResusMarker(dc)` | Rea-Hinweis am unteren Rand |

Markenfarben ebenfalls dort: `Ui.ORANGE`, `Ui.BLAU`, `Ui.ROT`.

---

## 2. Längen sind relativ, Schriften nicht

`Ui.s(dc, v)` rechnet `v × Displayhöhe ÷ 260`. Die 260 ist die Fenix 6 Pro als
Bezugsgerät: Bei 260 kommt **exakt** `v` heraus. Das ist Absicht und
Abnahmekriterium — auf der Fenix darf sich durch eine Umstellung nichts
verschieben.

`Ui.s(dc, 8)` ist also **kein** absoluter Pixelwert, auch wenn es so aussieht.
Es sind 8 Bezugspixel: 7 auf der FR945, 8 auf der Fenix, 12 auf der Venu 3s.

**Schriftgrößen skalieren dagegen nicht.** `FONT_TINY` und die anderen sind
Gerätekonstanten, die Garmin je Display festlegt — nicht unbedingt im selben
Verhältnis wie die Auflösung. Auf der Venu 3s wirkt `FONT_XTINY` im Verhältnis
zum Display kleiner als auf der Fenix. Dafür gibt es `Ui.fontHint()`, das ab
320 Pixeln Displayhöhe eine Stufe höher greift. Eine stufenlose Anpassung ist
nicht möglich: Es gibt genau fünf Textgrößen.

> **Nicht versuchen**, Schriften über eine eigene Skalierung zu vergrößern.
> Wer mehr als `FONT_LARGE` braucht, braucht eine eigene Schriftressource.

---

## 3. Ziffernschriften

### 3.1 Sie enthalten keine Buchstaben

`FONT_NUMBER_MILD`, `FONT_NUMBER_HOT` und `FONT_NUMBER_THAI_HOT` kennen nur
Ziffern, Doppelpunkt und Punkt. Buchstaben erscheinen als **leere Kästchen**.

Passiert in 1.6.0: „PAUSE" wurde in `FONT_NUMBER_MILD` gezeichnet und erschien
als fünf leere Rechtecke. Es gab keine Warnung, keinen Fehler — nur ein Bild,
das niemand deuten konnte.

### 3.2 Ihre Schriftbox ist höher als das, was man sieht

`dc.getFontHeight()` liefert die volle Box **samt Unterlänge**. Bei Ziffern
bleibt die leer. Rechnet man Blöcke damit, entsteht unter jeder Zahl eine
Lücke, und der ganze Block wirkt zu weit oben.

Deshalb `Ui.numH()` für alles, was direkt unter einer Zahl steht. Der Faktor
`NUM_VIS_PCT = 78` ist **empirisch**, nicht gemessen — `Toybox.Graphics.Dc`
kennt weder `getFontAscent` noch `getFontDescent`. Wer die Abstände unter
Zahlen ändern will, dreht dort, an einer Stelle für alle Oberflächen.

> **Achtung bei `has`:** `if (dc has :getFontDescent)` übersteht den Compiler,
> verengt aber den Typ von `dc` auf genau dieses eine Symbol — danach findet
> der Typprüfer `getFontHeight` nicht mehr. Objektmethoden nicht mit `has`
> abfragen. Bei Modulsymbolen (`Attention has :vibrate`) tritt das nicht auf.

---

## 4. Das Display ist rund

Der wiederkehrendste Fehler dieser Serie. Oben und unten läuft der Kreis zu:
Eine Zeile, die in der Bildschirmmitte passt, wird am Rand abgeschnitten.

### 4.1 Breite in der Höhe messen, nicht in der Mitte

`Ui.chordW(dc, y)` liefert die tatsächlich nutzbare Breite in Höhe `y`, mit
Sicherheitsrand. `Ui.fitFont()` wählt darauf aufbauend die größte Schrift, die
noch hineingeht.

### 4.2 An der äußeren Kante der Zeile messen

Der Kreis läuft **innerhalb einer einzigen Textzeile** schon spürbar zu.
`Ui.fitFont()` bekommt deshalb Oberkante und Höhe der Zeile und misst an der
Kante, die weiter von der Displaymitte entfernt liegt — unterhalb der Mitte
also unten, oberhalb oben.

In 1.6.4 wurde in der Zeilen**mitte** gemessen. „Ankunft Einsatzort" passte
danach, „Ankunft PatientIn" nicht — obwohl es kürzer ist. Zeichen zu zählen
hilft nicht, Glyphenbreiten entscheiden.

### 4.3 Ganz unten und ganz oben passt gar nichts

Unterhalb von etwa 85 % der Höhe trägt der Kreis keine sinnvolle Textzeile
mehr. Dort hilft auch die kleinste Schrift nicht — `fitFont` hat dann keine
Option mehr, die passt, und liefert notgedrungen die kleinste.

Konsequenz für den Entwurf: **Text nicht in der unteren Zone zentrieren**,
sondern an den darüberliegenden Block hängen. Und Warnzeilen dort kurz halten;
zwei Zeilen passen unterhalb eines Hauptblocks auf keinem der drei Geräte
zuverlässig.

---

## 5. Blöcke vertikal setzen

### 5.1 Feste Blöcke zuerst, dann den Rest zentrieren

Wer zwei Blöcke unabhängig voneinander positioniert — einen mittig, einen vom
Rand aus — lässt sie einander entgegenwachsen. Auf der Sync-Seite überlappten
sie sich, sobald drei Meldungen zusammenkamen.

Richtig: den Block mit schwankender Zeilenzahl **zuerst** setzen, dann den
anderen im verbleibenden Raum zentrieren.

### 5.2 Rechnerisch mittig wirkt zu hoch

Weil die Ziffernschriften oben mehr Luft lassen als unten und der Kreis unten
zuläuft, sitzt ein exakt mittiger Block optisch zu hoch. Auf der Hauptanzeige
wird der Block deshalb um `Ui.s(dc, 8)` nach unten versetzt.

Dasselbe bei Feldern im oberen Viertel: Die Gesamtdauer auf der
Reanimationsseite steht auf 62 % ihres Feldes, nicht auf 50 %.

### 5.3 Ungleiche Abstände können gleich aussehen

Steht eine Zahl zwischen zwei Textzeilen, wirkt oberhalb zusätzlich die
Unterlänge der Textzeile mit, unterhalb der Zahl dagegen nichts. Für optisch
gleiche Abstände muss der Wert oben **kleiner** sein als unten (Statistikseite:
8 gegen 12).

---

## 6. Menüs

`WatchUi.Menu2` ist eine Systemliste: Garmin zeichnet sie, die App reicht nur
Einträge hinein. Zeilenhöhe, Farben, Hintergrund und Trennelemente sind nicht
ansprechbar.

Alle Menüs der App sind deshalb **selbst gezeichnet**, nach demselben Muster:

- schwarzer Grund, fünf sichtbare Zeilen, Endlos-Umlauf per Modulo
- Zeilenhöhe `Ui.s(dc, 38)`, Auswahl als gefülltes Feld mit schwarzem Text
- Nachbarzeilen als reiner Text in der Eintragsfarbe
- Trennbalken (`:sep`) in halber Zeilenhöhe, nicht anwählbar — das Blättern
  überspringt sie
- Bedienung über `onNextPage`/`onPreviousPage` (Tasten **und** Wischen) und
  `onSelect`

Betroffen: `QuickMenuView`, `CprMenuView`, `ResusOverviewView`.

Bei umlaufenden Listen daran denken, dass **Anfang und Ende aneinanderstoßen**.
Zwei gleichfarbige Einträge an dieser Nahtstelle sind nicht auseinanderzuhalten
— deshalb ist „Übersicht" im Rea-Untermenü blau und nicht weiß, und deshalb hat
die Rea-Übersicht zwei Trennbalken statt einem.

---

## 7. Farblogik

| Farbe | Bedeutung |
|---|---|
| Rot (`Ui.ROT`) | laufende Reanimation, Warnung, Fehler |
| Blau (`Ui.BLAU`) | pausierte Reanimation, Bedienhinweis |
| Grün | erledigt, erfolgreich |
| Orange (`Ui.ORANGE`) | Kennzahlen und Marke |
| Hellgrau | läuft gerade, noch keine Aussage |

Ein Zustand bekommt **überall dieselbe Farbe**. Die pausierte Reanimation ist
auf fünf Oberflächen sichtbar; als sie dort drei verschiedene Farben hatte, war
sie nicht als ein Zustand erkennbar.

Farben nie aus Anzeigetexten ableiten. Auf der Sync-Seite wurde die Farbe der
Kopplungsmeldung aus den ersten drei Zeichen bestimmt — „Kopple…" (läuft noch)
und „Kopplung fehlgeschlagen" unterscheiden sich erst im sechsten. `Pair.mc`
führt seitdem eine eigene Statusart.

---

## 8. Vor der Abgabe

- [ ] Bei 260 Pixeln ergeben alle `Ui.s()`-Werte exakt die Ausgangszahlen?
- [ ] Steht Text in einer Ziffernschrift? (→ leere Kästchen)
- [ ] Blockhöhen mit `Ui.numH()` gerechnet, wo Zahlen beteiligt sind?
- [ ] Jede Textzeile durch `Ui.fitFont()` geführt, die nahe an den Rand kommt?
- [ ] Blöcke mit schwankender Zeilenzahl zuerst gesetzt?
- [ ] Umlaufende Listen an der Nahtstelle geprüft?
- [ ] Auf **allen drei Geräten** gebaut und angesehen?

Der letzte Punkt ist der wichtigste. Keiner der Fehler in diesem Dokument war
im Code sichtbar — jeder einzelne wurde erst im Simulator gefunden.
