# Eingabe-Probe

Kleines Connect-IQ-Wegwerfprojekt, mit dem sich das Eingabeverhalten eines
Garmin-Geräts im Simulator ausmessen lässt. **Kein Bestandteil der App** — es
wird nie ausgeliefert, hat eine eigene UUID und keine Berechtigungen.

Wozu: Bevor ein neues Zielgerät in `watch/manifest.xml` aufgenommen wird, muss
geklärt sein, welche Tasten überhaupt bei der App ankommen, welche Behaviors
das System daraus ableitet und ob die Langdruck-Erkennung der App dort
funktioniert. Die Ergebnisse werden in `docs/Geraete-Eingabe.md` festgehalten.

## Was die Probe protokolliert

Jede Zeile trägt einen Millisekunden-Stempel seit App-Start. Ausgegeben wird
auf der Simulator-Konsole **und** auf dem Display (die letzten acht Zeilen).

| Präfix | Bedeutung |
|---|---|
| `KeyPressed` / `KeyReleased` | Roh-Tastenereignis mit Tastencode |
| `onKey` | Tastenereignis der Behavior-Ebene |
| `TAP` / `HOLD` / `RELEASE` | Roh-Touchereignis mit Koordinaten |
| `SWIPE dir=` | Wischen: 0 hoch, 1 rechts, 2 runter, 3 links |
| `>> …` | vom System abgeleitetes Behavior |
| `*** HALTE-TIMER` | der 1000-ms-Timer hat gefeuert |

Alle Roh-Ereignisse geben `false` zurück, damit die System-Umsetzung in
Behaviors danach weiterläuft und **beide** Ebenen sichtbar werden. Die echte
App gibt aus `onKeyPressed` dagegen `true` zurück und schluckt das Ereignis —
deshalb erzeugt sie kein zusätzliches `onSelect`, die Probe aber schon.

## Der Halte-Timer

`onKeyPressed` startet einen 1000-ms-Timer, exakt den Mechanismus aus
`Const.LONG_PRESS_MS`. Die Ablesung entscheidet, ob Langdrücke auf dem Gerät
überhaupt möglich sind:

| Beobachtung | Bedeutung |
|---|---|
| `HALTE-TIMER` steht **vor** `KeyReleased` | Langdrücke funktionieren |
| `HALTE-TIMER` steht **nach** `KeyReleased` | `KeyPressed` kommt erst beim Loslassen — kein Langdruck möglich |
| kein `HALTE-TIMER` | die Taste wird vom System abgefangen |

## Anwendung

1. Zielgerät in `manifest.xml` unter `<iq:products>` eintragen.
2. Ordner in VS Code öffnen (er muss die Wurzel des Fensters sein).
3. Debug-Konsole öffnen (Strg+Shift+Y) und leeren.
4. F5, Gerät auswählen. Warten, bis der Block `=====` steht.
5. Messfolge durchgehen, zwischen den Schritten **drei Sekunden Pause** —
   die Zeitstempel sind dann eindeutig zuzuordnen:
   1. jede Taste einmal kurz
   2. jede Taste einmal ~1,5 s halten
   3. die Bestätigungstaste ~3 s halten (fängt das System ab?)
   4. bei Touchgeräten: tippen, ~1,5 s gedrückt halten, in alle vier
      Richtungen wischen
6. Konsolentext sichern und `docs/Geraete-Eingabe.md` um das Gerät ergänzen.
7. Gerät wechseln: Strg+Shift+P → „Monkey C: Build Current Project" → Gerät
   wählen; das wird zur Vorgabe für das nächste F5.

Nebenbei fällt die benötigte Größe des Launcher-Icons an: Weicht das
mitgelieferte 40×40 ab, nennt der Compiler den Sollwert in der Warnung
(Ausgabe-Fenster, Kanal „Monkey C"). Auch diese Größe gehört nach
`docs/Geraete-Eingabe.md`.

**Fünfmal Zurück** beendet die Probe.
