# GPX-Probe — liefert der Abruf die richtige Spur, richtig ausgezeichnet?

`php tools/gpxprobe/probe.php [basisadresse]`
(Vorgabe `http://127.0.0.1:8080`)

Rückgabewert 0 = alle Erwartungen erfüllt, 1 = mindestens eine nicht.

## Wozu

Der GPX-Abruf (S2/AP4, E-S2-09, Backlog Nr. 3) beantwortet drei Fragen, die
alle drei schiefgehen können, ohne dass es jemandem auffällt:

1. **Ist die Datei gültiges GPX 1.1?** Die Reihenfolge der Kindelemente ist im
   Schema eine `xsd:sequence`, keine `xsd:choice` — wer `<desc>` hinten
   anhängt, schreibt eine Datei, die manche Programme klaglos lesen und andere
   ablehnen.
2. **Steht die richtige Spur drin?** Nach sechs Monaten ist es die
   ausgedünnte, davor das Original.
3. **Sieht man ihr an, welche von beiden es ist?** — und zwar *vor* dem
   Herunterladen, nicht erst in der Datei.

Seit der Mehrfachauswahl kommt eine vierte dazu, die genauso still schiefgeht:
**Stehen mehrere Spuren als mehrere `<trk>` nebeneinander?** Aneinandergehängt
bleibt die Datei gültig — und jedes Kartenprogramm zieht eine gerade Linie vom
Ende der einen Spur zum Anfang der nächsten.

## Die Teile

| Teil | Frage |
|---|---|
| 0 | Gültig gegen das **amtliche GPX-1.1-XSD** (`DOMDocument::schemaValidate`). Vor jedem Lauf wird die SHA-256-Summe der Schemadatei geprüft |
| 1 | Anmeldung und Datentrennung: unangemeldet nichts, ein fremder Einsatz ergibt **404 und nicht 403** |
| 2 | **Punkt für Punkt gegen den Referenzexport** — die dort liegenden GPX-Dateien stammen von `assets/export.js`, einer ganz anderen Umsetzung, im Browser |
| 3 | Struktur, Punktzahl je Stufe und Kennzeichnung über den **echten Abrufweg** (HTTP), für Stufe 2 und Stufe 3 |
| 4 | Grenzfälle: keine Spur, unbekannte Art, Kennung 0, Kennung ohne Eintrag, Papierkorb — dazu: der Abruf liegt **nicht** unter `api/`, und Fehlgriffe laufen in den Ratenschutz |
| 5 | Die Kennzeichnung ist **auf der Seite** sichtbar, und ohne Spur gibt es den Menüeintrag nicht |
| 6 | Die Spurenseite des Diensttages — auch **Ruhesegmente** haben dort einen Abruf |
| 7 | Die **Mehrfachauswahl**: mehrere `<trk>` statt einer zusammengeklebten Spur, Punkt für Punkt gegen die Einzelabrufe, Reihenfolge, Grenzfälle, Speicherspitze |

**Teil 2 belegt mehr als Teil 0.** Ein Schema sagt, dass die Datei richtig
*aufgebaut* ist; es sagt nichts darüber, ob die richtigen Punkte darin stehen.
Zwei unabhängige Umsetzungen, die auf denselben Bestand dieselbe Datei
schreiben, sagen genau das.

**Die eigene Strukturprüfung bleibt neben dem Schemalauf.** Sie stellt andere
Fragen: Der Schemalauf sagt „ungültig", sie sagt *woran es liegt* — und sie
fängt zwei Dinge, die das Schema durchlässt: ein Komma als Dezimaltrenner in
`lat`/`lon` (das Schema sieht dann nur einen ungültigen Dezimalwert, nicht die
Ursache) und eine Datei ganz ohne `<trkpt>`, die schemagültig ist und trotzdem
keine Spur.

## Das vendorierte Schema

`tools/gpxprobe/gpx11.xsd` — **byteweise unverändert** wie bezogen.

| | |
|---|---|
| Herkunft | `https://topografix.com/GPX/1/1/gpx.xsd`, bezogen am 31.08.2026 |
| Größe | 26 665 Byte |
| SHA-256 | `9e4d1988b862edbe556305b130f8f6f1b29864fefd0dc02d5dab04ccdd1f34d6` |
| Lizenz | siehe `docs/Lizenzen.md` |

Die Herkunft steht **hier und in `docs/Lizenzen.md`**, nicht im Dateikopf: Ein
Kommentar in der Datei änderte ihre Prüfsumme, und die Prüfsumme ist der Punkt.
Die Probe rechnet sie bei **jedem** Lauf nach — ein Schemalauf gegen ein
verändertes Schema belegt nichts, und ein Schema, das jemand „passend gemacht"
hat, fiele sonst niemandem auf.

**Und Git darf sie nicht anfassen.** Die `.gitattributes` des Projekts setzen
`* text=auto eol=lf`; das Schema hat 788 CRLF, die dabei auf LF umgeschrieben
worden wären. Aufgefallen beim Einchecken (`CRLF will be replaced by LF`), und
zwar an der schlimmstmöglichen Stelle: Die **Arbeitskopie** bleibt, wie sie
kam, die Summe stimmte hier also weiter — auf jedem frisch geklonten
Arbeitsplatz wäre die Datei 25 877 statt 26 665 Byte groß gewesen und die
Probe an ihrer ersten Erwartung gescheitert. Die Zeile
`tools/gpxprobe/gpx11.xsd -text` hält sie unangetastet; nachgerechnet mit
`git checkout-index` in ein leeres Verzeichnis (26 665 Byte, Summe stimmt).

**Nicht zur Laufzeit.** Die Datei liegt unter `tools/`, wird von der Anwendung
nie geladen und ist im Deploy nicht enthalten (`server/` allein wird
ausgeliefert). Die Zusage „keine fremde Quelle zur Laufzeit" (CLAUDE.md 4)
bleibt unberührt.

## Was sie am Bestand ändert

Sie legt ihr **eigenes Konto** (`gpxprobe@gen-em.org`) samt Diensttag,
Einsätzen und einem Ruhesegment an und räumt alles am Ende ab — auch bei einem
Abbruch (`finally`), einschließlich der Spuren, die an keinem Fremdschlüssel
hängen (F-S2-B).

Für Teil 7 legt sie zusätzlich ein Ruhesegment **vor** dem ersten Einsatz an.
Ohne das säßen alle Ruhezeiten hinter allen Einsätzen, und eine nach Art
gruppierte Liste sähe genauso aus wie eine chronologische — die Prüfung der
Reihenfolge belegte dann nichts.

**Die Hintergrundjobs hält sie an**, solange sie läuft (`jobs_pause()`). Sonst
dünnte der Ausdünnungsjob mitten in der Messung eine Spur aus, und die Stufe
wäre nicht mehr die, die die Probe hergestellt hat.

**Das Konto entsteht per SQL, die Anmeldung läuft echt** über `login.php`. Die
Ableitung des Anmeldetokens gehört in den Browser (PBKDF2 über das Passwort);
sie hier nachzubauen prüfte nichts, was diese Probe angeht. Der Hash wird
deshalb über ein selbst gewähltes Token gesetzt — Sitzung und `auth_guard`
laufen danach unverändert.

## Was sie nicht prüft

- **Das Zusammenspiel mit der Karte.** Ob das Hervorheben beim Zeigen und das
  Zurücktreten der nicht ausgewählten Linien funktioniert, ist Bedienzustand;
  die Probe prüft nur, dass jede Zeile die Verknüpfung (`data-spur`) und ihr
  Kästchen trägt. Der Rest gehört in den Browser und ist dort mit gemessenen
  Deckkräften geprüft (S2/AP4).
- **Fremde Kartenprogramme.** Dass eine schemagültige Datei in QGIS, Garmin
  BaseCamp oder Komoot so aussieht wie gemeint, sagt kein Schema.
- **Sehr große Spuren.** Der Bestand hat keine Spur über 50 000 Punkten.
- **Nebenläufigkeit** zwischen Abruf und Ausdünnungsjob. Die Probe hält die
  Jobs an, statt den Fall herzustellen.

## Voraussetzungen

Eine laufende Installation (der Entwicklungsserver genügt), die Migrationen bis
`2026_09_01_letzter_punkt_am`, der eingecheckte Referenzexport unter
`tools/referenzdatensatz/referenz/` und PHP mit `dom` und `libxml`.
