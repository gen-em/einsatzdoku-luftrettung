# V1 — Ortsdaten: was verschlüsselt ist, was nicht, und was es kostet

**Anlass:** Die Frage aus der Durchsicht vom 30.08.2026 — *„Transportziel inkl.
Koordinaten und Einsatznummer müssen eigentlich auch verschlüsselt werden. Sind
Einsatzort & Koordinaten auch verschlüsselt?"*

**Auftrag:** Erst Bestandsaufnahme, dann entscheiden. Dieses Dokument ändert
nichts am Code. Es beantwortet drei Fragen: Was liegt wo? Was verrät was? Was
kostet es, das zu ändern?

---

## 0. Die kurze Antwort

**Zwei der drei genannten Punkte sind bereits erfüllt.** Die Einsatznummer ist
verschlüsselt, der Einsatzort ist es mit Adresse *und* Koordinaten. Das
Transportziel ist es nicht — und das war eine bewusste Entscheidung, die im
Feldkatalog steht.

**Der eigentliche Befund liegt daneben:** Die GPS-Spur und die
Phasen-Koordinaten liegen im Klartext. Der Einsatzort ist damit **nominell**
geschützt und **faktisch** aus der Spur rekonstruierbar, die dort endet. Das
ist kein neuer Fehler; es ist ein Widerspruch zur Zusage in `CLAUDE.md`, der
benannt gehört.

---

## 1. Bestandsaufnahme: was liegt wo

### 1.1 Verschlüsselt (im `pat_blob`, Ende-zu-Ende)

Erhoben aus `server/einsatz_form.php`, dem einen Ort, an dem der Block gebaut
wird. Der Browser verschlüsselt, der Server sieht nur Chiffretext.

| Schlüssel | Feld |
|---|---|
| `mission_no` | **Einsatznummer** |
| `last`, `first` | Nachname, Vorname |
| `dob`, `age` | Geburtsdatum, Alter |
| `dx` | Diagnose |
| `loc.addr`, `loc.lat`, `loc.lon` | **Einsatzort: Bezeichnung UND Koordinaten** |
| `site_desc` | Beschreibung Einsatzort |
| `start.addr`, `start.lat`, `start.lon` | Manueller Abfahrtort mit Koordinaten |

In `missions` gibt es **keine** Spalten `site_lat`/`site_lon` — nachgezählt in
`schema.sql`. Die Koordinate des Einsatzorts existiert nur im Chiffretext.

### 1.2 Klartext

| Ort | Was | Wie es dahin kommt |
|---|---|---|
| `missions.transport_dest` | Name der Zielklinik | Formular |
| `missions.dest_lat/dest_lon` | Koordinate der Zielklinik | Formular |
| `missions.site_ele_m` | Höhe des Einsatzorts | vom Server aus der Spur gerechnet |
| `mission_phases.lat/lon` | Koordinate **jeder Phase** | Uhr-Upload |
| `track_points.lat/lon` | die **vollständige GPS-Spur** | Uhr-Upload |
| `days.base_lat/base_lon` | Standort des Diensttags | Stammdaten |

Der Klartext des Transportziels ist ausdrücklich entschieden und in
`mission_fields.php` begründet: *„Sie liegt im KLARTEXT wie der Name selbst —
ihr Pin ist damit ohne Freischalten sichtbar, anders als der Einsatzort."*

---

## 2. Was verrät was

Das ist der Teil, der die Entscheidung trägt.

**Die Spur verrät den Einsatzort.** Sie beginnt am Standort und endet dort, wo
der Einsatz war. Wer die Datenbank liest, braucht den verschlüsselten
`loc`-Schlüssel nicht — der letzte Punkt vor der Phase „Eintreffen" steht im
Klartext daneben.

**Die Phasen verraten das Ziel.** `mission_phases` trägt zu jeder Phase eine
Koordinate, darunter „Transportbeginn" und „Ankunft Klinik". Das Transportziel
zu verschlüsseln und diese Spalten stehen zu lassen, verschöbe das Problem um
eine Tabelle.

**Der Standort verrät den Träger.** `days.base_lat/base_lon` ist Stammdatum und
kein Patientendatum — hier ist Klartext richtig.

**Die Höhe verrät wenig, aber nicht nichts.** `site_ele_m` grenzt in einem
Bergland eine Menge möglicher Orte ein. Für sich genommen harmlos, zusammen mit
Zeit und Standort weniger.

---

## 3. Was es kostet

### 3.1 Die gute Nachricht: der Server rechnet nicht damit

Nachgesehen in allen dreizehn Dateien, die `track_points` anfassen:

| Was man vermuten würde | Woher es tatsächlich kommt |
|---|---|
| Strecke (`distance_m`) | **von der Uhr** — `ingest.php:129` liest sie aus dem Upload |
| Höhenmeter (`ascent_m`) | ebenso, `ingest.php:130` |
| Höhe des Einsatzorts | aus `ele` und `ts` — **nicht** aus lat/lon |
| Phasenzuordnung (`track_idx`) | rein über **Zeitstempel**, `api/mission.php:155` |
| Luftlinie | im **Browser**, `assets/luftlinie.js` |

**Der Server liest `lat`/`lon` nirgends rechnend.** Er zählt Punkte, löscht sie
und gibt sie weiter. Die Verschlüsselung der Koordinaten bräche also **keine**
Serverfunktion — anders als zunächst vermutet und in der Besprechung so
gesagt; diese Annahme war falsch.

### 3.2 Die schlechte Nachricht: die Uhr hat keinen Schlüssel

Ende-zu-Ende heißt hier: Der Inhaltsschlüssel wird im Browser aus dem Passwort
abgeleitet. Die Garmin-App kennt ihn nicht und kann ihn nicht kennen — sie hat
kein Passwort, sondern ein Gerätetoken. Die Koordinaten kommen deshalb
**zwangsläufig im Klartext** beim Server an.

Das ist der eigentliche Kostenpunkt, und er lässt drei Wege offen.

---

## 4. Die drei Wege

### Weg A — nachträglich im Browser verschlüsseln

Die Uhr lädt weiter Klartext. Beim ersten Öffnen des Einsatzes verschlüsselt
der Browser die Spur und schreibt sie zurück; der Server löscht die
Klartextzeilen.

- **Aufwand:** mittel. Ein Migrationsweg je Einsatz, ein neues Blob-Feld, der
  Kartenaufbau liest künftig aus dem Blob.
- **Was es nicht löst — und das gehört ausgesprochen:** Zwischen Upload und
  erstem Öffnen liegt die Spur im Klartext, **unbestimmt lange**. Ein Einsatz,
  den niemand öffnet, bleibt für immer im Klartext. Das ist eine Zusage, die
  „meistens" gilt, und solche Zusagen sind schlechter als keine.

### Weg B — Schlüssel auf die Uhr

Die Kopplung übergibt der Uhr einen Schlüssel; sie verschlüsselt vor dem
Upload.

- **Aufwand:** groß. Kopplung, Uhr-App, JSON-Vertrag, Schlüsselwechsel bei
  Passwortänderung, und die Frage, was ein verlorenes Gerät bedeutet.
- **Was es löst:** alles. Der Klartext entsteht nie.
- **Was es kostet, das nirgends steht:** Eine Uhr, die verschlüsselt, kann
  ihre Daten nicht mehr selbst anzeigen, und ein Schlüsselwechsel macht
  Uploads unlesbar, die noch nicht synchronisiert sind.

### Weg C — nichts verschlüsseln, aber es sagen

Der heutige Zustand bleibt. Dafür wird die Zusage auf das eingegrenzt, was sie
hält: **Personendaten und die Einsatzort-Angabe** sind Ende-zu-Ende
verschlüsselt; die GPS-Spur ist es nicht. Nachzutragen in `CLAUDE.md`
(Abschnitt 4), `docs/Technik.md` und im Datenschutztext der Installation.

- **Aufwand:** gering.
- **Was es löst:** den Widerspruch zwischen Zusage und Wirklichkeit — nicht
  die Sache selbst.

---

## 5. Empfehlung

**C jetzt, B als Ziel, A nicht.**

Weg A sieht nach dem pragmatischen Mittelweg aus und ist der schlechteste: Er
kostet echten Aufwand und liefert eine Zusage mit Loch. Wer sie liest, glaubt
mehr, als sie hält.

Weg C ist heute in einem Nachmittag zu haben und stellt die Ehrlichkeit her.
Er ist keine Lösung, aber er ist auch keine Behauptung.

Weg B ist die Lösung und gehört in eine eigene Phase, zusammen mit der
Uhr-Arbeit, die ohnehin ansteht (R29, Logo-Wahl auf der Uhr). Vorher zu klären:
Was passiert bei Passwortwechsel mit noch nicht hochgeladenen Aufzeichnungen?

**Unabhängig von der Wahl** ist das Transportziel die kleinere Frage. Es zu
verschlüsseln, solange die Phasen-Koordinate „Ankunft Klinik" daneben im
Klartext steht, ist Symbolik. Es gehört zu B — oder gar nicht.

---

## 6. Was noch zu klären ist

| Frage | Wer |
|---|---|
| Ist die Spur überhaupt schützenswert, oder reicht der Personenbezug? | Philipp, ggf. mit Datenschutzberatung |
| Was soll bei einem Passwortwechsel mit nicht synchronisierten Uhr-Daten geschehen? | vor Weg B zu entscheiden |
| Genügt für den Bestand ein Stichtag („ab hier verschlüsselt"), oder muss rückwirkend? | vor Weg B zu entscheiden |
