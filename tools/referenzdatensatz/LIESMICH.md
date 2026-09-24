# Referenzdatensatz

Ein **erfundener** Beispielbestand, der zwei Aufgaben hat: Demo-Konto auf
dem Produktivserver und Regressionsreferenz.
**Anlass: Nr. 174, 267** — ohne festen Bestand misst jeder Lauf etwas anderes.

## Aufruf

```bash
python3 tools/referenzdatensatz/quelldaten/pruefen.py    # die Quelle prüfen
python3 tools/referenzdatensatz/einspielen/einspielen.py # Bestand herstellen
python3 tools/referenzdatensatz/vergleich/kreislauf.py --art csv|edbak
```

## Was es misst

`pruefen.py` hält die Quelldaten gegen ihre Matrix: Deckt der Bestand jeden
Fall ab, den er abdecken soll? Der **Kreislauf** exportiert, importiert und
vergleicht Feld für Feld — er beantwortet „kommt heraus, was hineinging?".

## Was es braucht

`pruefen.py` nichts. Einspielen und Kreislauf brauchen eine Installation;
der Kreislauf gegen Staging zusätzlich `STAGING_*` und `JOBS_TOKEN`.

## Erwartete Zahl

**0 Befunde, keine offene Matrixzeile.** Der Kreislauf: **0 Abweichungen**
bei 21 Diensttagen und 106 Einsätzen. Gegen Staging mit MySQL 8.4.10
zuletzt **104 s grün** (21.09.2026, Nr. 267).

## Was es nicht kann

**Die Geographie ist echt, die Namen sind erfunden** — `VERBOTENE_NAMEN`
(E-P1-02) hält reale Rufnamen und Orte heraus, und die Textprobe liest
dieselbe Liste. Der Bestand misst keine Mengen; dafür ist der Messstand da.
