# Krypto-Probe

Kann die Garmin-Uhr die Gerätehülle bauen? Eigene UUID, **nie ausgeliefert**.
**Anlass: Nr. 43, Nr. 563, Nr. 577, Nr. 580** — die Uhr verschließt vor dem Upload; Fassung 2, Zähler am Inhalt.

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
Vergleichswert, Kopplungsablauf. Seit KG-09 der Zähler `z` am Inhalt (E-KA-69,
`ZAEHLER-…`): Regel, Speicher, Altbestand, Trennen, Vektor `zaehler`, Inhalt,
Hashform, Ablage, 2038. Was mit Zufall entsteht, steht als `EXTERN`-Zeilen in der
Konsole; `extern.py` öffnet es mit der Referenz und prüft die Zählerfolge.

## Was es braucht

Den Uhr-Prüfstand (`aufbau`); für die echte Uhr eine Arbeitsstation mit SDK.

## Erwartete Zahl

`kryptoprobe` → je Gerät **ERGEBNIS 32 gut, 0 Fehler**, **3 von 3**.
`extern.py` → **27 Hüllen und Kopplungen, 0 Fehler**. `kryptoreihe` → **99/0**.

## Was es nicht kann

Zeiten, Speicher (geteilt zwischen den Geräten) und Uhrzeit sind die des Rechners;
Rücksprung, fehlende Zeit und 2038 messen die `ZAEHLER`-Schritte mit vorgegebenem „jetzt". Übersetzen
zeigt nicht, ob die übrigen 96 Geräte rechnen; die Einsatzhülle 2 prüft `extern.py`.
