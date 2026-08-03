# Einsatzdoku — Eingabeverhalten der Zielgeräte

Gemessene Eigenschaften jedes Geräts, auf dem die Uhr-App laufen soll oder
geprüft wurde. **Referenzdokument**: Wer die Tastenbelegung oder die
Oberflächen ändert, prüft hier, was das jeweilige Gerät überhaupt hergibt.

Die Werte stammen nicht aus Datenblättern, sondern aus Messungen mit
`tools/eingabe-probe` im Connect-IQ-Simulator. Wie ein neues Gerät ergänzt
wird, steht in Abschnitt 5.

---

## 1. Überblick

| Gerät | Auflösung | Tasten | Touch | CIQ | Launcher-Icon | Stand |
|---|---|---|---|---|---|---|
| Fenix 6 Pro (`fenix6pro`) | 260 × 260 | 5 | nein | 3.4.5 | 40 × 40 | 03.08.2026 |
| Forerunner 945 (`fr945`) | 240 × 240 | 5 | nein | 3.3.1 | 40 × 40 | 03.08.2026 |
| Venu 3s (`venu3s`) | 390 × 390 | 3 (2 nutzbar) | ja | 5.2.0 | 70 × 70 | 03.08.2026 |

Die Icon-Größen stammen aus den Compiler-Warnungen: Für `fr945` und
`fenix6pro` erschien keine, für `venu3s` die Meldung, dass 40 × 40 nicht zur
Sollgröße 70 × 70 passt.

Alle drei sind rund (`screenShape = 1`). `TextPicker`, `Menu2` und
`Confirmation` sind auf allen dreien verfügbar — die Gerätekopplung über
`Pair.mc` braucht also nirgends einen Sonderweg.

Geprüft und **verworfen**: Epix Gen 1. Das Gerät bleibt auf CIQ 1.2.1 und
erfüllt `minApiLevel 3.1.0` nicht.

---

## 2. Fenix 6 Pro — `fenix6pro`

`partNumber 006-B3290-00` · 260 × 260 · kein Touch · CIQ 3.4.5

| Taste | Lage | Code | kurz | lang (~1,5 s) |
|---|---|---|---|---|
| LIGHT | oben links | — | wird nicht zugestellt | wird nicht zugestellt |
| UP | Mitte links | 13 | `onPreviousPage` | `onMenu` |
| DOWN | unten links | 8 | `onNextPage` | `onNextPage` |
| START | oben rechts | 4 | `onSelect` | `onSelect` |
| BACK | unten rechts | 5 | `onBack` | `onBack` |

- `KeyPressed` und `KeyReleased` kommen bei allen vier nutzbaren Tasten an.
  Die Langdruck-Erkennung der App funktioniert.
- Ein Halten über 3 s wird nicht abgefangen.
- **Lang UP erzeugt zusätzlich `onMenu`.** Dafür existiert die Absicherung in
  `CprDelegate.onMenu` — sonst würde eine Adrenalingabe doppelt gezählt.

## 3. Forerunner 945 — `fr945`

`partNumber 006-B3113-00` · 240 × 240 · kein Touch · CIQ 3.3.1

Verhält sich in allen gemessenen Punkten **identisch zur Fenix 6 Pro**,
einschließlich `onMenu` bei lang UP und der nicht zugestellten LIGHT-Taste.
Unterschiede nur bei Auflösung und CIQ-Fassung.

## 4. Venu 3s — `venu3s`

`partNumber 006-B4261-00` · 390 × 390 · Touch · CIQ 5.2.0

### 4.1 Tasten

Alle drei Tasten sitzen rechts.

| Taste | Lage | Code | kurz | lang (~1,5 s) |
|---|---|---|---|---|
| Action / Start-Stopp | oben | 4 | `onSelect` | `onSelect` (bei Druck) |
| Custom / Sprachassistent | Mitte | — | **liefert nichts** | **liefert nichts** |
| Zurück | unten | 5 | `onBack` (beim Loslassen) | `onMenu` nach 1000 ms, **kein** `onBack` |

- Die **Mitteltaste ist für Connect-IQ-Apps nicht verfügbar**, weder kurz noch
  lang. Sie ist systemseitig belegt. Folge: Ohne Touch ist die Venu 3s nicht
  bedienbar.
- `KeyPressed` kommt **im Moment des Drückens**. Gemessen: Halte-Timer feuerte
  1015 ms nach dem Druck und 1,7 s vor dem Loslassen. Die Langdruck-Erkennung
  der App funktioniert.
- Ein Halten der Action-Taste über **4,6 s wurde im Simulator nicht
  abgefangen**. ⚠️ Das Handbuch der Venu 3 nennt ein Steuerungsmenü nach 2 s
  Halten. Der Simulator bildet Systemgesten nicht zwingend ab — **auf echter
  Hardware ungeprüft**. Deshalb liegt SELECT_LONG zusätzlich auf dem langen
  Zurück-Druck (`onMenu`).
- `onSelect` der Action-Taste feuert **beim Drücken**, nicht beim Loslassen.
  In der App tritt es nicht auf: `ActionDelegate.onKeyPressed` gibt `true`
  zurück und schluckt das Ereignis. Genau darum bleibt eine
  Bildschirmberührung auf den Hauptseiten wirkungslos — sie erzeugt `onSelect`
  ohne Tastenereignis und läuft damit in einen Handler, den es nicht gibt.

### 4.2 Touch

| Geste | Ergebnis |
|---|---|
| Wischen hoch | `onNextPage` |
| Wischen runter | `onPreviousPage` |
| Wischen rechts | `onBack` |
| Wischen links | `SWIPE dir=3`, **kein Behavior** — frei |
| Tippen | `onSelect` bzw. `HOLD` + `RELEASE`, je nach Druckdauer |
| Halten auf dem Bildschirm | `HOLD` + `RELEASE` |

**Wichtigste Regel dieses Geräts:** Wo eine Behavior-Zuordnung existiert, wird
das Roh-Ereignis **gar nicht** zugestellt. Wischen lässt sich deshalb nicht
über `onSwipe` abfangen oder unterdrücken — auf der Venu blättert Wischen
immer. Nur der Linkswisch ist frei.

Eine Bildschirmberührung erzeugt **kein** Tastenereignis. Auf den Hauptseiten
läuft sie deshalb in Handler, die die App dort nicht implementiert, und bleibt
wirkungslos. In Menüs, die auf `onSelect` reagieren, kann ein Tippen den
gerade markierten Eintrag auswählen — unabhängig davon, wo getippt wurde.
Bewusst hingenommen.

---

## 5. Ein Gerät ergänzen

1. Gerät in `tools/eingabe-probe/manifest.xml` unter `<iq:products>` eintragen.
2. Messfolge aus `tools/eingabe-probe/LIESMICH.md` durchgehen.
3. Hier einen Abschnitt nach dem Muster von Abschnitt 4 anlegen und die
   Übersichtstabelle in Abschnitt 1 ergänzen.
4. Größe des Launcher-Icons aus der Compiler-Warnung übernehmen und ein
   passendes Icon unter `watch/resources-<gerät>/drawables/` ablegen.
5. Erst danach das Gerät in `watch/manifest.xml` aufnehmen.

Drei Punkte sind vor der Aufnahme zwingend zu klären, weil sie über die
Bedienbarkeit entscheiden:

- Kommen `KeyPressed` **und** `KeyReleased` an, und feuert der Halte-Timer vor
  dem Loslassen? Sonst sind keine Langdrücke möglich.
- Welche Tasten fängt das System ab?
- Bei Touchgeräten: Welche Wischgesten sind vom System belegt?

## 6. Was nicht gemessen werden kann

Der Simulator bildet Verhalten des Betriebssystems außerhalb der App nicht
zuverlässig ab — Steuerungsmenüs, Tastensperren, Displaydimmung. Diese Punkte
sind mit ⚠️ gekennzeichnet und brauchen eine Gegenprobe auf echter Hardware.
