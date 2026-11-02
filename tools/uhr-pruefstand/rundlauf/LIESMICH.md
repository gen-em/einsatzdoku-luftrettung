# Rundlauf der Uhr

Eine **Prüffassung** der Garmin-App (Kopie unter `$CIQ_BASIS/rundlauf-app`,
Adresse `127.0.0.1:8443`, dazu `Rundlauf.mc` mit Einsatz und Ruhesegment)
koppelt im Simulator an das Demo-Konto, sendet verschlossen, `pruefen.php` öffnet.
**Anlass: Nr. 43** — ob die Hüllen der Uhr nach Kopplung, `ingest.php` und
Datenbank aufgehen, zeigt nur ein Paket, das wirklich gesendet wurde.
**Anlass: Nr. 431** für `--reaktivieren` — eine im Web wieder aktivierte Uhr
sendete erst nach einem Neustart der App.

## Aufruf

```bash
bash tools/uhr-pruefstand/rundlauf/rundlauf.sh [--nachholen|--schrumpfen|--reaktivieren] [geraet]   # Vorgabe fenix6pro
```

Im Prüfstand als `uhr-rundlauf`: `--nachholen fenix6pro`, dann `--schrumpfen fr945`,
dann `--reaktivieren fenix6pro`.

## Was es misst

Koppeln (Sitzung per SQL dem Demo-Konto zugeordnet; den Klick misst die Kopplungsprobe), Senden, Öffnen mit dem Demo-Schlüssel: 600
Punkte an den Rändern (negativ, 4500,5 m, ohne Höhe) in zwei Stücken, drei
Phasen (eine ohne Ortung; `mission_phases` ohne Koordinatenspalte, F-R17-63),
Reanimation, Ruhestück. `--nachholen`: Schlüssel des Kontos beim Koppeln
geleert, „Kein Schlüssel“, 0 Einsätze; START halten (E-S11-79), dann gehen
die Pakete. `--schrumpfen` (F-R17-52): zwei geparkte Pakete vor dem Einsatz,
verworfen während seiner letzten Anfrage (Haken in der Kopie von
`Uploader.mc`); danach `liste=0 spuren=0` in der Konsole, kein Absturz.
`--reaktivieren`: wie `--nachholen`, aber das Gerät wird vor dem Holen
deaktiviert — 403, 0 Einsätze, Bild `3c-deaktiviert.png` mit der Sperrzeile;
wieder aktiviert, 12 s ohne Anlass weiter 0; Sync-Seite verlassen und neu
öffnen, dann gehen die Pakete.

## Was es braucht

Uhr-Prüfstand mit Simulator, örtliche Anlage mit eigener CA (`Technik.md` 5.6),
Demo-Konto mit Schlüsselpaar; Marke, Schlüssel und Gerät stellt es zurück, auch nach Abbruch.

## Erwartete Zahl

`12 Pruefungen, 0 Fehlschlaege` je Lauf, mit `--schrumpfen` dazu `3 Pruefungen, 0 Fehlschlaege`,
mit `--reaktivieren` dazu `2 Pruefungen, 0 Fehlschlaege`;
Abbilder unter `$CIQ_BASIS/rundlauf/`.

## Was es nicht kann

Venu 3s (Wischweg nicht ausgemessen), echte GPS-Aufzeichnung, Zeit und Speicher einer echten Uhr.
