# Bildprobe der Sync-Seite

Eine **Prüffassung** der Garmin-App (Kopie unter `$CIQ_BASIS/syncseite-app`)
startet direkt auf der Sync-Seite, mit zwei offenen Paketen und vier bis
sieben Meldungszeilen im unteren Block.
**Anlass: Nr. 402** — ab vier Zeilen lag „N Pakete offen" auf der obersten
Meldung (Venu 3s, im Simulator gesehen); der Kommentar behauptete das Gegenteil.

## Aufruf

```bash
bash tools/uhr-pruefstand/syncseite/syncseite.sh [geraet ...]   # Vorgabe fenix6pro fr945 venu3s
```

## Was es misst

Je Gerät und Zeilenzahl (4, 5, 6, 7) schreibt die Kopie von `SyncView.mc`
ihre Rechnung in die Konsole (`SYNCPROBE <Ende Mittelblock> <Beginn unterer
Block>`); grün ist eine Messung, wenn das Ende nicht über dem Beginn liegt.
Dazu ein Bild je Messung unter `$CIQ_BASIS/syncseite/`. Die Rechnung sagt,
ob sich die Blöcke berühren; das Bild, ob es so aussieht. Die Zeilen setzt
`SyncProbe.mc` (gesperrt, Upload-Fehler, Kopplungsmeldung, Hinweis); die
offenen Pakete legt `rundlauf/Rundlauf.mc` an.

## Was es braucht

Uhr-Prüfstand mit Simulator (`pruefstand.sh aufbau`, `Xvfb`). Keine örtliche
Anlage — die Prüffassung koppelt nicht und sendet nicht.

## Erwartete Zahl

`12 Pruefungen, 0 Fehlschlaege`; rund 9 min. Gegen die Sync-Seite von Uhr
4.3.0 (vor dem Ausweichen) gefahren: 10 Fehlschläge, auf der Venu 3s schon
bei vier Zeilen.

## Was es nicht kann

Abschneiden am Kreisrand messen: Die Rechnung kennt nur Höhen. Ob eine Zeile
breiter ist als die Sehne an ihrer Stelle, zeigt nur das Bild.
