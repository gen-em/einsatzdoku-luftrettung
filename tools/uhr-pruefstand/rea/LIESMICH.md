# Reanimation der Uhr

Eine **Prüffassung** der Garmin-App (Kopie unter `$CIQ_BASIS/rea-app`)
startet auf der Reanimationsseite, der Countdown dauert 40 s; jede
Vibration, jede Buchung, jeder Seitenwechsel steht in der Konsole.
**Anlass: Nr. 571, Nr. 574** — Belegung, Sperre, Vibrationen; Menü beim Loslassen.

## Aufruf

```bash
bash tools/uhr-pruefstand/rea/rea.sh [geraet]   # Vorgabe fenix6pro
REA_WATCH=<verzeichnis> bash tools/uhr-pruefstand/rea/rea.sh   # Gegenprobe
```

## Was es misst

18 Schritte mit getrenntem Drücken und Loslassen: ohne Rea genau eine Seite je Druck, Beginn, Sperre,
Rhythmuskontrolle, Adrenalin, Defibrillation ohne Blättern, Menü auf
Amiodaron ohne Buchung beim Loslassen, lang UP genau einmal, die
Erinnerungen 0:30, 0:10, 0:00 in Folge, Pause, BACK, Schnellmenü der Hauptanzeige ohne Auswahl. Je Schritt die neuen
Konsolenzeilen gegen das Soll, Muster samt Dauer und Stärke.

## Was es braucht

Uhr-Prüfstand mit Simulator (`pruefstand.sh aufbau`, `Xvfb`). Keinen Server.

## Erwartete Zahl

`Reanimation: 18 Pruefungen, 0 Fehlschlaege`, rund 4 min. Gegen Uhr 4.5.1:
1 Fehlschlag (Schnellmenü wählt beim Loslassen). Gegen 4.4.0: fast alle rot.

## Was es nicht kann

Den Fehler, dass kurz UP/DOWN nach einem Seitenwechsel buchte — der
Simulator erzeugt ihn nicht (`docs/Geraete-Eingabe.md` 6). Kein Gefühl der
Vibration, keine Venu (kein UP/DOWN), keine echte Uhr.
