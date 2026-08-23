# Maskierungsprobe Altersfeld

Vorher/Nachher-Beleg zu **Backlog Nr. 22** (Befund F-20): Das Alter lief in
`zelleGeschuetzt()` bis Web 7.2.0 unmaskiert in die Zelle, die per `innerHTML`
gesetzt wird. Über das Formular war der Weg zu (`parseInt()`), über den Import
nicht — `assets/import.js` übernimmt `pat.age` als rohen Zellenwert.

Die Probe baut das Markup der Einsatztabelle nach. Sie lädt die **echten**
Dateien `server/assets/html.js` und `server/assets/missiontable.js` für den
Nachher-Stand und hält den Stand 7.2.0 daneben als wörtliche Kopie der alten
Funktion. Beide Stände zeichnen dieselben sechs Fälle.

## Aufruf

    cd tools/maskierungs-probe
    node pruefe.mjs

Erwartet: `AK-S22-2 ERFUELLT` und `AK-S22-3 ERFUELLT`. `probe.html` lässt sich
auch von Hand im Browser öffnen — dort ist der Unterschied unmittelbar zu sehen.

## Was gemessen wird

- **Ausgelöstes Markup.** Die Nutzlast ist ein `<img src=x onerror="…">`; der
  Zähler steigt nur, wenn der Browser sie als Markup verarbeitet. Die Nutzlast
  trägt den Lauf fest eingebaut — `onerror` läuft asynchron, erst wenn beide
  Tabellen schon stehen, und eine Zählung über eine globale „aktueller Lauf"-
  Variable schlüge den Treffer dem falschen Lauf zu.
- **Eingehängte `<img>`-Elemente** je Tabelle — dasselbe von der anderen Seite,
  ohne Zeitverhalten.
- **Zellen-HTML zeichenweise**, vorher gegen nachher. Für die legitimen Fälle
  (47, leer, 0, nicht lesbar) muss es *gleich* sein; genau das ist der Nachweis,
  dass sich für gültige Eingaben nichts ändert.

`<script>` ist bewusst **nicht** die Nutzlast: `innerHTML` führt eingefügte
`<script>`-Elemente nicht aus. Das verharmlost die Lücke nicht — `onerror` und
verwandte Attribute laufen sehr wohl.

## Verhältnis zu P1

Phase P1 nimmt einen Angriffswert im Altersfeld als ständigen Regressionsfall in
den Referenzdatensatz auf (R20). Diese Probe darf dafür als Vorlage liegen
bleiben. Sie liegt unter `tools/` und wird deshalb **nicht** ausgeliefert: Der
Deploy lädt ausschließlich `server/` hoch.
