# Browserschritte des Referenzdatensatzes

Was es **nur** im Browser gibt — Import, Exporte, Umläufe, Demo-Funktion; die Klickstrecke steht im Kopf des Skripts.

## Aufruf

```bash
node csv_import.mjs [basis] [email] [passwort] [csv] [ausgabe]   # Aufbau: vier Einsätze per CSV
node referenz_export.mjs            # die eingecheckten Referenzdateien ziehen
node angriffswerte.mjs              # P-07: Angriffswerte stehen inert
node papierkorb_misch.mjs           # E-S1-04, Nr. 33: gemischter Papierkorb im Umlauf
node demo_pruefen.mjs [basis] [schritte]   # Abnahme der Demo-Funktion (E-P1-08), mit den Spurfällen (Nr. 500)
node demo_fassung2.mjs [basis] [--zweimal]  # Demo-Bestand im neuen Format, Weiche an (KA-13, Nr. 563)
node demo_bremse.mjs · node download_lib.mjs --selbstprobe
```

Die zwei `kreislauf_*.mjs` ruft `vergleich/kreislauf.py`.

## Was es misst

Den Weg, den eine Nutzerin geht — der Import verschlüsselt im Browser. Die Umläufe warten auf
**`.meldung`** und ihren Ton; jeder Download geht über `download_lib.mjs`. `demo_fassung2.mjs` öffnet
nach einem Reset jeden Einsatz und jedes Segment mit umgelegter Weiche; `--zweimal` kommt nach
Änderung und Reset im selben Browser wieder (`demo_zustand.php` setzt zurück und liest).

## Was es braucht

Eine eingespielte Anlage (`../einspielen/`). `demo_pruefen.mjs` und
`papierkorb_misch.mjs` fassen Daten an und brechen **hart** ab (rc 2), wenn
das Konto nicht das Demo- bzw. ein `umlauf-`-Konto ist.

## Erwartete Zahl

`demo_pruefen.mjs` **41 Prüfungen, 0 Befunde** (05.11.2026; am 15.11.2026 ohne `spurfaelle` 27 / 0 — der
Abschnitt hängt an einer verborgenen Ruhezeile, auch vor KA-13); `demo_fassung2.mjs --zweimal` **„Ohne Befund“**,
106 Einsätze, 118 Segmente, 63 366 Punkte; `download_lib.mjs --selbstprobe` **10 / 0**; `csv_import.mjs` 4 angelegt.

## Was es nicht kann

Kartenkacheln laden; `demo_pruefen.mjs` und `demo_fassung2.mjs` **verändern** das Konto (Reset); das zweite misst nur Format 4.
