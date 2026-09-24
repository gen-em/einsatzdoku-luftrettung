# Browserschritte des Referenzdatensatzes

Was es **nur** im Browser gibt — der Import, die Exporte, die Umläufe, die
Demo-Funktion. Die Klickstrecke von Hand steht jeweils im Kopf des Skripts.

## Aufruf

```bash
node csv_import.mjs [basis] [email] [passwort] [csv] [ausgabe]   # Aufbau: vier Einsätze per CSV
node referenz_export.mjs            # die eingecheckten Referenzdateien ziehen
node angriffswerte.mjs              # P-07: Angriffswerte stehen inert
node papierkorb_misch.mjs           # E-S1-04, Nr. 33: gemischter Papierkorb im Umlauf
node demo_pruefen.mjs [basis] [schritte]   # Abnahme der Demo-Funktion (E-P1-08)
node demo_bremse.mjs · node download_lib.mjs --selbstprobe
```

Die zwei `kreislauf_*.mjs` ruft `vergleich/kreislauf.py`.

## Was es misst

Den Weg, den eine Nutzerin geht — der Import verschlüsselt im Browser. Die
Umläufe warten auf **`.meldung`** und ihren Ton, nicht auf Wörter; jeder
Download geht über `download_lib.mjs`, die beim Ausbleiben Zustand, Verlauf
und Konsolenfehler meldet.

## Was es braucht

Eine eingespielte Anlage (`../einspielen/`). `demo_pruefen.mjs` und
`papierkorb_misch.mjs` fassen Daten an und brechen **hart** ab (rc 2), wenn
das Konto nicht das Demo- bzw. ein `umlauf-`-Konto ist.

## Erwartete Zahl

`demo_pruefen.mjs` **24 Prüfungen, 0 Befunde** („nicht gemessen" mit Grund
ist regulär); `download_lib.mjs --selbstprobe` **10 / 0** (in Stufe 1);
`angriffswerte.mjs` 0 Befunde; `csv_import.mjs` 4 angelegt, danach 4 Dubletten.

## Was es nicht kann

Kartenkacheln laden; `demo_pruefen.mjs` **verändert** das Konto, gegen das es läuft.
