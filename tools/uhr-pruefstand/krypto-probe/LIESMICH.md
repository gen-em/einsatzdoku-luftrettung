# Krypto-Probe

Kann eine Garmin-Uhr die Gerätehülle aus S11 bauen, und was kostet es?
Wegwerfprojekt mit eigener UUID und ohne Berechtigungen — **kein Bestandteil
der App**, nie ausgeliefert.
**Anlass: Nr. 43** — die Uhr verschließt Spur und Einsatzhülle vor dem Upload.

## Aufruf

```bash
bash tools/uhr-pruefstand/pruefstand.sh kryptoprobe [geraet…]   # Simulator
bash tools/uhr-pruefstand/pruefstand.sh kryptoreihe auswahl.txt # Stufe I
```

Auf der echten Uhr: in VS Code mit diesem Ordner als Wurzel bauen
(„Build for Device"), die `.prg` nach `GARMIN/APPS/` kopieren, starten,
mit Hoch/Runter blättern.

## Was es misst

Schritte und Zeilen im Kopf von `source/KryptoApp.mc`: Schlüsselpaar, ECDH samt
Byteordnung, HKDF, AES-256-CBC, HMAC und die ganze Hülle je gegen die Vektoren
aus `tools/proben/huelle/` (`source/Vektoren.mc` ist erzeugt); ob es `deflate`
gibt; Zeit für ein Schlüsselpaar; Spurstück zu 200 und 500 Punkten, binär und
JSON, mit Speicherzuwachs; ob ein Punkt neben der Kurve abfällt (`KURVE`, F-S11-85).

## Was es braucht

Den Uhr-Prüfstand (`aufbau`); für die echte Uhr eine Arbeitsstation mit SDK.

## Erwartete Zahl

`kryptoprobe` → je Gerät **ERGEBNIS 7 gut, 0 Fehler**, am Ende **3 von 3**
(Simulator, 11.10.2026). `kryptoreihe` → **99 übersetzt, 0 fehlgeschlagen**.

## Was es nicht kann

Zeiten und Speicher des Simulators sind die des Rechners, nicht der Uhr —
gültig sind nur die von der echten Uhr abgelesenen. Ob die übrigen 96
Geräte die Krypto ausführen, zeigt Übersetzen nicht (E-S11-16).
