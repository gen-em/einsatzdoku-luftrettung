# Rundlauf der Uhr

Eine **Prüffassung** der Garmin-App koppelt im Simulator an das Demo-Konto
der örtlichen Anlage, sendet verschlossen, `pruefen.php` öffnet. Kein
Bestandteil der App: Kopie unter `$CIQ_BASIS/rundlauf-app`, Adresse
`127.0.0.1:8443`, dazu `Rundlauf.mc` (legt einen Einsatz und ein Ruhesegment an).
**Anlass: Nr. 43** — ob die Hüllen der Uhr nach Kopplung, `ingest.php` und
Datenbank aufgehen, zeigt nur ein Paket, das wirklich gesendet wurde.

## Aufruf

```bash
bash tools/uhr-pruefstand/rundlauf/rundlauf.sh [--nachholen] [geraet]   # Vorgabe fenix6pro
```

Im Prüfstand als `uhr-rundlauf`: `--nachholen fenix6pro`, dann `fr945`.

## Was es misst

Koppeln (die Sitzung wird per SQL dem Demo-Konto zugeordnet — den Klick im
Web misst die Kopplungsprobe), Senden, Öffnen mit dem Demo-Schlüssel: 600
Punkte an den Rändern (negativ, 4500,5 m, ohne Höhe) in zwei Stücken, drei
Phasen (eine ohne Ortung), Reanimation, Ruhestück. Mit `--nachholen` ist der
öffentliche Schlüssel des Kontos beim Koppeln geleert: „Kein Schlüssel“, 0
Einsätze; dann START halten (E-S11-79), erst danach gehen die Pakete.

## Was es braucht

Uhr-Prüfstand mit Simulator, örtliche Anlage mit eigener CA (`docs/Technik.md`
5.2b), Demo-Konto mit Schlüsselpaar. Demo-Marke, Demo-Schlüssel und das
gekoppelte Gerät stellt das Skript am Ende zurück, auch nach Abbruch.

## Erwartete Zahl

`12 Pruefungen, 0 Fehlschlaege` je Lauf; Abbilder unter `$CIQ_BASIS/rundlauf/`.

## Was es nicht kann

Venu 3s (Wischweg nicht ausgemessen), echte GPS-Aufzeichnung, Zeit und Speicher einer echten Uhr.
