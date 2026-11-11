# Krypto-Probe

Kann die Garmin-Uhr die Gerätehülle bauen? Eigene UUID, **nie ausgeliefert**.
**Anlass: Nr. 43, Nr. 563** — die Uhr verschließt vor dem Upload; Fassung 2.

## Aufruf

```bash
bash tools/uhr-pruefstand/pruefstand.sh kryptoprobe [geraet…]   # Simulator
bash tools/uhr-pruefstand/pruefstand.sh kryptoreihe auswahl.txt # Stufe I
python3 tools/uhr-pruefstand/krypto-probe/extern.py ~/.ciq-pruefstand/krypto_<geraet>.txt
```

Echte Uhr: „Build for Device", `.prg` nach `GARMIN/APPS/`; Update: `FASSUNG` heben.

## Was es misst

Schritte im Kopf von `source/KryptoApp.mc`: Fassung 1 (Paar, ECDH samt
Byteordnung, HKDF, AES, HMAC, Hülle) gegen `Vektoren.mc`; `KURVE`, `SIGNATUR`,
`GERAETEPAAR`, `T-STATISCH` (KG-02). Seit KG-03a übersetzt sie den Kern der
App mit (`Huelle.mc`, `Huelle2.mc`) und misst Fassung 2 byte-gleich gegen
`Vektoren2.mc`: Kennung, Z_s, Hüllen „kurz" und „spur-3", Kopf, Zusage,
Vergleichswert, Kopplungsablauf. Was mit Zufall entsteht, steht als
`EXTERN`-Zeilen in der Konsole; `extern.py` öffnet es mit der Referenz.

## Was es braucht

Den Uhr-Prüfstand (`aufbau`); für die echte Uhr eine Arbeitsstation mit SDK.

## Erwartete Zahl

`kryptoprobe` → je Gerät **ERGEBNIS 23 gut, 0 Fehler**, **3 von 3**.
`extern.py` → **18 Hüllen und Kopplungen, 0 Fehler**. `kryptoreihe` → **99/0**.

## Was es nicht kann

Zeiten und Speicher sind die des Rechners. Ob die übrigen 96 Geräte die Krypto
ausführen, zeigt Übersetzen nicht. Die Einsatzhülle der Fassung 2 ist JSON und
nicht byte-gleich prüfbar; `extern.py` vergleicht den Inhalt. Der Simulator
teilt den Speicher zwischen den Geräten (der Zähler steigt über die Läufe).
