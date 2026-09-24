# Ausnahmelisten

Eine Ausnahme sagt: **Diese Abweichung ist Bauart, kein Fehler.** Kein
Filter — jede getroffene Regel steht mit Anzahl und Grund im Bericht.

## Aufruf

Keiner von Hand: `kreislauf.py --art <art>` reicht `<art>_umlauf.json` an
`vergleichen.py` weiter (`--ausnahmen`). Drei Listen: `csv`, `edbak`,
`edbak-alt`.

## Was es misst

Die Form einer Liste:

```jsonc
{ "name": "csv-umlauf", "beschreibung": "…",
  "regeln": [ { "bereich": "einsaetze",   // oder * für alle
                "feld": "herkunft",       // oder * für die ganze Zeile
                "art": "wert",            // wert | fehlt | zusaetzlich (weglassbar)
                "von": "uhr", "nach": "import",   // bekannter Übergang (weglassbar)
                "begruendung": "…" } ] }          // PFLICHT
```

## Was es braucht

Eine `begruendung` je Regel — ohne sie weist `vergleichen.py` die Regel beim
Laden zurück: Eine Ausnahme ohne Grund ist ein Filter.

## Erwartete Zahl

Jede Regel greift. Eine, die nicht gegriffen hat, steht am Ende des
Berichts: Entweder gibt es ihren Fall nicht mehr, oder der Umlauf hat ihn
nicht berührt — dann prüft der Lauf weniger als gedacht.

## Was es nicht kann

Einen Fehler festschreiben: Was sich an der Anwendung beseitigen ließe,
gehört ins Backlog, nicht hierher. Und `{"bereich": "*", "feld": "*"}` wäre
gültig und machte den Vergleich stumm — je enger die Regel, desto besser.
