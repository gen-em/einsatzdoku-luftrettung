# Prüfkonten — ein Testbestand für die NutzerInnen-Liste

## Wofür

Das Konzept verlangt die Abnahme der NutzerInnen-Liste **„mit einem Testbestand
von 300 Konten"** (E-P3-41, Abnahme O9). Der Referenzdatensatz hat vier Konten
— damit lässt sich keine der Fragen beantworten, um derentwillen die Liste
gebaut wurde:

- Trägt der Seitenwechsel, und rechnet er an den Rändern richtig?
- Bleibt die Auswahl über Seiten hinweg stehen?
- Wie lange braucht ein Aufruf, wenn der Sicherungsstand jedes Kontos aus dem
  Dateisystem kommt?
- Sortiert die Namensspalte einen Umlaut an die richtige Stelle?

## Aufruf

Voraussetzung: eine laufende lokale Instanz
(`sh tools/referenzdatensatz/einspielen/lokal_starten.sh`).

```
php tools/pruefkonten/pruefkonten.php anlegen [anzahl]   # Vorgabe 300
php tools/pruefkonten/pruefkonten.php zeigen
php tools/pruefkonten/pruefkonten.php entfernen
```

Optional als weiteres Argument der Pfad zu `server/` (alles mit einem `/`
darin wird als Pfad gelesen).

## Was es anfasst

Konten unterhalb von `@example.invalid` mit dem Präfix `pruefkonto-` — sonst
nichts. `entfernen` löscht genau diese wieder, samt Geräten (Fremdschlüssel mit
`ON DELETE CASCADE`) und samt ihrer Sicherungsordner unter
`server/sicherungen/`.

**Trotzdem: gegen eine Testinstallation fahren, nicht gegen den
Produktivserver.** Das Werkzeug fragt nicht nach.

## Warum die Sicherungen echte Dateien sind

Der Stand eines Kontos — *aktuell*, *überfällig · n Tage*, *nie gesichert* —
steht nicht in der Datenbank, sondern in
`server/sicherungen/<kennung>/konto.json`. Ein Testbestand, der nur
Datenbankzeilen anlegt, füllte die Liste mit lauter „nie gesichert" und prüfte
damit genau die Verzweigung nicht, um die es geht.

Die Pakete sind deshalb echt, aber winzig: ein formal gültiges Paket mit leerem
Datenteil, rund 300 Byte. Es geht um die **Zahl** der Ordner, nicht um ihren
Inhalt. Wer Datenmengen prüfen will, ist bei Backlog Nr. 37 richtig.

## Reproduzierbar

`mt_srand()` mit festem Startwert: Zweimal `anlegen 300` ergibt zweimal
denselben Bestand — Rollen, Gerätezahlen, Sicherungsstände und
Anmeldezeitpunkte inbegriffen. Nur so lässt sich eine gemessene Zahl beim
nächsten Lauf wiederfinden.

Die Mischung bei 300 Konten (gemessen, Stand Web 9.9.0):

| | |
|---|---|
| aktuell gesichert | 180 |
| Sicherung überfällig | 28 |
| nie gesichert | 86 |
| ohne Kontokennung | 6 |
| Admins | 6 |
| ohne Gerät | 55 |
| nie angemeldet | 44 |

Die sechs Konten **ohne Kontokennung** sind Absicht: Sie bilden den Altbestand
vor der Migration `2026_08_16_kontokennung` nach. Die Liste muss sie zeigen
können, ohne sie als „nie gesichert" auszugeben — sie sind ein anderer Befund.

## Grenzen

- Die Konten haben **keine Einsätze und keine Diensttage**. Die Sicherungen
  sind leer. Für Datenmengen ist das der falsche Bestand (Backlog Nr. 37).
- Kein Konto hat ein Passwort — anmelden lässt sich mit keinem davon.
- Die Geräte tragen einen Zufallshash als Schlüssel; es gibt keinen Klartext
  dazu, und keines kann sich am Endpunkt melden.
