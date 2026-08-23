# Ausnahmelisten

Eine Ausnahme sagt: **Diese Abweichung ist Bauart, kein Fehler.** Sie ist kein
Filter — das Werkzeug zählt jede getroffene Regel und führt sie im Bericht
unter *Erwartete Abweichungen* mit Anzahl und Begründung auf.

Drei Regeln für diese Dateien:

1. **Ohne Begründung keine Regel.** `vergleichen.py` weist eine Regel ohne
   `begruendung` beim Laden zurück. Das ist nicht Pedanterie: Eine Ausnahme
   ohne Grund ist ein Filter, und ein Filter verdeckt genau das, wofür der
   Vergleich da ist.
2. **Vermeidbares ist keine Ausnahme.** Wenn sich eine Abweichung durch eine
   Änderung an der Anwendung beseitigen ließe, gehört sie als Fehlerfund ins
   Konzeptdokument und in den Backlog — nicht hierher. Sonst schreibt die
   Ausnahmeliste einen Fehler auf Dauer fest.
3. **Jede Regel hat eine Zahl.** Der Bericht nennt am Ende die Regeln, die
   nicht gegriffen haben. Sie sind nicht harmlos: Entweder beschreiben sie
   etwas, das es nicht mehr gibt, oder der Umlauf hat den Fall gar nicht
   berührt — dann prüft der Lauf weniger als gedacht.

## Aufbau

```jsonc
{
  "name": "csv-umlauf",
  "beschreibung": "…",
  "regeln": [
    { "bereich": "einsaetze",   // oder * für alle
      "feld": "herkunft",       // oder * für die ganze Zeile
      "art": "wert",            // wert | fehlt | zusaetzlich (weglassbar)
      "von": "uhr",             // erwarteter Wert davor (weglassbar)
      "nach": "import",         // erwarteter Wert danach (weglassbar)
      "begruendung": "…" }      // PFLICHT
  ]
}
```

Je enger eine Regel gefasst ist, desto besser: `von`/`nach` angeben, wo der
Übergang bekannt ist. Eine Regel `{"bereich": "*", "feld": "*"}` wäre
gültiges JSON und trotzdem falsch — sie machte den ganzen Vergleich stumm.
