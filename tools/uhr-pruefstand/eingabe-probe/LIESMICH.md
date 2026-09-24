# Eingabe-Probe

Welche Tasten und Gesten eines Garmin-Geräts kommen bei einer App an?
Wegwerfprojekt mit eigener UUID und ohne Berechtigungen — **kein Bestandteil
der App**, nie ausgeliefert.

## Aufruf

In VS Code mit der Monkey-C-Erweiterung, dieser Ordner als Wurzel des
Fensters: das Zielgerät in `manifest.xml` unter `<iq:products>` eintragen,
Debug-Konsole öffnen (Strg+Shift+Y), **F5**, Gerät wählen. Dann, mit je drei
Sekunden Pause: jede Taste kurz · jede Taste ~1,5 s halten · die
Bestätigungstaste ~3 s halten · am Touchgerät tippen, ~1,5 s halten, in alle
vier Richtungen wischen. **Fünfmal Zurück** beendet die Probe.

## Was es misst

Jedes Roh-Ereignis und das Behavior, das das System daraus ableitet, mit
Millisekunden seit dem Start — auf der Konsole und auf dem Display (die
letzten acht Zeilen). Die Zeilenpräfixe stehen im Kopf von
`source/ProbeApp.mc`.

## Was es braucht

Einen Simulator — den des Uhr-Prüfstands oder den eines Arbeitsplatzes —
und die Gerätedatei des Zielgeräts.

## Erwartete Zahl

Die Ablesung am Halte-Timer (1000 ms, wie `Const.LONG_PRESS_MS`):
`HALTE-TIMER` **vor** `KeyReleased` = Langdruck geht · **danach** = kein
Langdruck möglich · **gar nicht** = das System fängt die Taste ab. Ergebnis
und Größe des Launcher-Icons (Warnung des Compilers) gehören nach
`docs/Geraete-Eingabe.md`.

## Was es nicht kann

Ein echtes Gerät ersetzen. Und ein zusätzliches `onSelect` ist kein Befund
der App: Die Probe gibt jedes Roh-Ereignis weiter, die App schluckt es.
