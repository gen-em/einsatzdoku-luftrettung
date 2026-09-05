# Gen-EM NAdoku — Eingabeverhalten der Zielgeräte

Gemessene Eigenschaften jedes Geräts, auf dem die Uhr-App laufen soll oder
geprüft wurde. **Referenzdokument**: Wer die Tastenbelegung oder die
Oberflächen ändert, prüft hier, was das jeweilige Gerät überhaupt hergibt.

Die Werte stammen nicht aus Datenblättern, sondern aus Messungen mit
`tools/eingabe-probe` im Connect-IQ-Simulator. Wie ein neues Gerät ergänzt
wird, steht in Abschnitt 5.

Zur **Darstellung** auf den Geräten — Schriften, runde Displays, Aufbau der
Oberflächen — siehe `Uhr-Layout_Regeln.md`.

**Die Kopplung bringt seit Uhr 3.0.0 keinen neuen Tastenweg mit.** Sie beginnt
wie bisher mit dem langen Auswahl-Druck auf der Sync-Seite (`SELECT_LONG`,
Abschnitt 1) und braucht in der neuen Kopplungsansicht nur noch `BACK` zum
Abbrechen — beides steht in der Tabelle. Was **entfallen** ist, ist der
`WatchUi.TextPicker`: die einzige Texteingabe der App und der einzige Weg, der
auf der Venu 3s eine Bildschirmtastatur brauchte.

Die Abschnitte 1 bis 6 gelten der **Garmin-Uhr** (Connect IQ, Monkey C).
Abschnitt 7 kam mit S4 dazu und gilt der **Wear-OS-App** — dort ist die Lage
grundlegend anders, und der Abschnitt beginnt damit.

---

## 1. Überblick

| Gerät | Teilenummer | Auflösung | Tasten | Touch | CIQ | Launcher-Icon | Stand |
|---|---|---|---|---|---|---|---|
| Fenix 6 Pro (`fenix6pro`) | `006-B3290-00` | 260 × 260 | 5 | nein | 3.4.5 | 40 × 40 | 03.08.2026 |
| Forerunner 945 (`fr945`) | `006-B3113-00` | 240 × 240 | 5 | nein | 3.3.1 | 40 × 40 | 03.08.2026 |
| Venu 3s (`venu3s`) | `006-B4261-00` | 390 × 390 | 3 (2 nutzbar) | ja | 5.2.0 | 70 × 70 | 03.08.2026 |

**Die Teilenummer steht seit S6 in dieser Tabelle**, weil sie außerhalb der
Uhr gebraucht wird: Die Uhr kennt ihren Modellnamen nicht und sendet beim
Koppeln stattdessen diese Nummer (`docs/JSON-Vertrag.md`, Abschnitt 1a). Der
Server löst sie über `server/geraetemodelle.php` auf. Diese drei Nummern sind
damit prüfbare Eingangswerte — und die einzigen, die im Repositorium belegt
sind; die vollständige Zuordnung entsteht aus den Gerätedateien
(`tools/geraetemodelle/`).

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
6. **Prüfen, dass die Teilenummer aufgelöst wird**: Steht sie in
   `server/geraetemodelle.php`? Wenn nicht, `tools/geraetemodelle/erzeugen.py`
   mit aktuellen Gerätedateien neu laufen lassen. Sonst koppelt das Gerät
   zwar, erscheint in der Geräteliste aber dauerhaft als Nummer statt als
   Name — und das fällt niemandem auf, weil nichts fehlschlägt.

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

---

## 7. Wear OS — `android/uhr/` (seit S4)

> **⚠️ Dieser Abschnitt ist blind gebaut und am Gerät nachzumessen.**
> Es lag keine Wear-OS-Uhr vor. Was hier steht, stammt aus der
> Android-Dokumentation, aus den Maßen der Compose-for-Wear-Bausteine und aus
> gerenderten Bildern (Robolectric, NATIVE-Grafikmodus) — **nicht** aus einer
> Messung an Hardware. Die Abschnitte 2 bis 4 sind gemessen; dieser ist es
> nicht, und der Unterschied gehört an den Anfang und nicht in eine Fußnote.

### 7.1 Warum hier keine Tastentabelle steht

Die Garmin-Abschnitte messen, **welche Taste welches Ereignis auslöst** und ob
`KeyPressed`/`KeyReleased` durchkommen. Bei Wear OS stellt sich diese Frage
nicht in derselben Form:

- **Es gibt keine feste Tastenzahl.** Wear-OS-Uhren haben eine, zwei oder drei
  physische Tasten; ihre Belegung liegt beim System, nicht bei der App. Eine
  App, die auf „Taste 2" baut, ist auf der Hälfte der Geräte unbedienbar.
- **Die Bedienung ist Touch.** Das ist die Vorgabe der Plattform, nicht eine
  Entscheidung dieses Projekts. Die App bedient sich mit Tippen und
  senkrechtem Rollen; sie fängt **keine** Systemgesten ab.
- **Zurückwischen gehört dem System.** Ein Wisch von links ist „zurück" und
  wird nicht abgefangen.

Was stattdessen zu messen ist, steht deshalb in 7.3.

### 7.2 Was aus dem Bild bekannt ist

Gerendert wurde in den Größen, die Wear OS führt — **192 dp** (kleine runde
Uhr) und **227 dp** (große). Die Zahlen aus dem Prüfstand C1/C2:

| Eigenschaft | Wert | Herkunft |
|---|---|---|
| Höhe der Bedienknöpfe | **48 dp** | gemessen im gerenderten Bild (Android-Vorgabe; das Web hält 44 px, siehe Fund B-S4-02) |
| Anteil der Knopfflächen außerhalb des runden Glases | **0 %** | gemessen, beide Größen |
| Inhalt der laufenden Ansicht | **221 dp auf 192 dp** | gemessen — sie ist **rollbar**, und das ist der Grund |

**Die Zusicherung gilt den Knopfflächen, nicht dem ganzen Inhalt.** Eine
frühere Fassung dieser Zeile behauptete „0 % außerhalb" für die ganze Ansicht;
gemessen sind es 221 dp Inhalt auf 192 dp Glas. Der Inhalt rollt, die Knöpfe
liegen vollständig im Glas — das ist die Aussage, die trägt.

### 7.3 Was am Gerät nachzumessen ist

Je Punkt: was zu tun ist, was erwartet wird, und **woran ein Scheitern zu
erkennen ist**.

| Prüfen | Erwartet | Scheitern erkennbar an |
|---|---|---|
| **Zustellung überhaupt** — Dienst auf der Uhr beginnen, Handy in Reichweite | Das Handy zeigt den Dienst binnen Sekunden | Die Uhr bleibt bei „wartet aufs Handy · keine Aufzeichnung"; der Zähler „gepuffert" steigt |
| **Paket- und Signaturgleichheit** (E-S4-01) — beide Module aus demselben Baulauf installieren | wie oben | Zustellung schlägt dauerhaft fehl, auch bei nebeneinander liegenden Geräten. **Der wahrscheinlichste Fehler überhaupt**: unterschiedliche Signaturen |
| **Funkloch** — Handy ausschalten, drei Ereignisse auslösen, Handy einschalten | alle drei kommen an, in der Reihenfolge 1, 2, 3 | eines fehlt (Puffer verloren) oder eines liegt doppelt (Quittung greift nicht) |
| **Wie lange `Tasks.await` hängt** bei abgeschalteter Uhr | die Bedienung friert nicht ein | Die Uhr reagiert nach dem Auslösen mehrere Sekunden nicht |
| **Uhr-Neustart mit gefülltem Puffer** | Nach dem Neustart sind die Nachrichten noch da, die nächste Nummer ist **nicht** 1 | Das Handy legt einen zweiten Dienst an |
| **Rollen** in der laufenden Ansicht | Der Abschlussknopf ist erreichbar | Er liegt unter dem Rand und lässt sich nicht erreichen |
| **Knopfhöhe am Handgelenk** | 48 dp sind mit Handschuh treffbar | Fehlgriffe; dann ist 48 dp die falsche Zahl für dieses Gerät |
| **Always-on-Display** | Die Ansicht überlebt den Wechsel in den Ambient-Modus | Die App startet neu oder verliert den Dienst |
| **Dauerlauf** — zwölf Stunden Dienst | Der Akku hält, der Dienst läuft durch | Das System beendet die App; der Dienst bricht ohne Meldung ab |
| **Ortungszustand am Handgelenk** (seit Android 0.10.0) — im laufenden Dienst den Standort des Handys ausschalten und die Uhr **nicht anfassen** | Binnen Sekunden steht **oben in der Zustandszeile** „keine Ortung · keine Aufzeichnung" in Rosa, ohne dass jemand einen Knopf drückt; nach dem Wiedereinschalten „GPS sucht" und dann wieder Phase und Zeit | Die Uhr ändert sich erst, wenn man einen Knopf drückt → die aktive Standmeldung kommt nicht an. Die Uhr ändert sich gar nicht → das Feld `ortung` fehlt, oder die Zeile liegt wieder unter dem Rand (B-S5Z-17) |
| **Kein Funkfeuer bei jedem Wechsel** — im Dienst zwischen „Standort aus" und „kein Signal" wechseln lassen (Standort aus, dann in die Tiefgarage) | Die Uhr zeigt durchgehend dasselbe, und im `logcat` steht **kein** zweiter „Ortungsstand an die Uhr" | Eine Meldung je Zustandswechsel statt je Anzeigewechsel — das kostet Akku für nichts |
| **Dienststart bei ausgeschaltetem Standort** — an der Uhr beginnen, während der Standort des Handys aus ist | Der Dienst beginnt trotzdem (er wird nicht abgelehnt), das Handy vibriert, und die Uhr zeigt sofort „keine Ortung · keine Aufzeichnung" | Die Uhr zeigt „Dienst läuft" ohne Hinweis — dann verschweigt sie genau die Lücke, die hinterher niemand erklären kann |

### 7.4 Ein Gerät ergänzen

Anders als bei Garmin gibt es **keine Geräteliste zu pflegen**: Wear OS
liefert Größe und Form zur Laufzeit, und die App rechnet damit. Zu ergänzen
ist hier nur, was eine Messung ergeben hat, die von 7.2 abweicht — und das
ist dann ein Fund, kein Eintrag.
