# Bildprobe der Sync-Seite

Eine **Prüffassung** der Garmin-App (Kopie unter `$CIQ_BASIS/syncseite-app`)
startet direkt auf der Sync-Seite, mit zwei offenen Paketen und vier bis
sieben Meldungszeilen im unteren Block.
**Anlass: Nr. 402** — ab vier Zeilen lag „N Pakete offen" auf der obersten
Meldung (Venu 3s, im Simulator gesehen); der Kommentar behauptete das Gegenteil.
Nr. 470 — eine Zeile, breiter als der Kreis an ihrer Stelle (Venu 3s, 6 und 7 Zeilen).

## Aufruf

```bash
bash tools/uhr-pruefstand/syncseite/syncseite.sh [geraet ...]   # Vorgabe fenix6pro fr945 venu3s
```

## Was es misst

Je Gerät und Zeilenzahl (4, 5, 6, 7) schreibt die Kopie von `SyncView.mc`
ihre Rechnung in die Konsole (`SYNCPROBE <Ende Mittelblock> <Beginn unterer
Block>`); grün ist eine Messung, wenn das Ende nicht über dem Beginn liegt. Dazu je Bild die Breite (`SYNCBREITE <Überstand> <Zeile>`, breiteste Zeile gegen `Ui.kanteW`; grün bei höchstens 0).
Dazu ein Bild je Messung unter `$CIQ_BASIS/syncseite/`. Die Rechnung sagt,
ob sich die Blöcke berühren; das Bild, ob es so aussieht. Die Zeilen setzt
`SyncProbe.mc` (gesperrt, Upload-Fehler, Kopplungsmeldung, Hinweis); die
offenen Pakete legt `rundlauf/Rundlauf.mc` an.

## Was es braucht

Uhr-Prüfstand mit Simulator (`pruefstand.sh aufbau`, `Xvfb`). Keine örtliche
Anlage — die Prüffassung koppelt nicht und sendet nicht.

## Erwartete Zahl

`24 Pruefungen, 0 Fehlschlaege` (12 Höhen, 12 Breiten; seit BR6-06); rund 9 min.
Gegenprobe `SYNCSEITE_OHNE_KURZFORM=1` (Kurzform aus): Venu 3s bei 6 und 7 Zeilen rot.

## Was es nicht kann

Den Mittelblock auf Breite messen — nur den unteren Block. Und die Sehne ist
die Rechnung der App: Liegt die falsch, liegt die Probe mit; das Bild bleibt.
