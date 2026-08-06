# Exportformate

Der Export erzeugt Dateien **zum Weiterverarbeiten in anderen Programmen** —
Tabellenkalkulation, Auswertung, Weitergabe an Dritte. Er ist **kein Backup**:
Ein Backup (`.edbak`, siehe `Backup-Format.md`) sichert alles und lässt sich
vollständig zurückspielen; ein Export ist auf Lesbarkeit und Anschlussfähigkeit
ausgelegt.

Der gesamte Dateiaufbau passiert **im Browser**. Der Server (`api/export_data.php`)
liefert ausschließlich Rohdaten und sieht zu keinem Zeitpunkt Klartext der
geschützten Angaben. Ist „Patientendaten einschließen" nicht gesetzt, sendet der
Server den `pat_blob` gar nicht erst mit.

Es gibt drei Profile:

| Profil | Datei | Zielgruppe | Verlustfrei |
|---|---|---|---|
| CSV (Standard) | `…_csv.zip` | Maschinen | ja |
| Excel (Standard) | `…_standard.xlsx` | Menschen | nein |
| Excel (GuteSeele) | `…_guteseele.xlsx` | Dritte | nein |

Benennung und Reihenfolge sind dieselben wie im Auswahlfeld des Imports.

Dateiname durchgängig:

```
luftrettungsdokumentation_export_TT-MM-JJJJ_<profil>.<endung>
```

Das Datum ist der **Tag der Erstellung**, nicht der ausgewählte Zeitraum — der
steht in der Datei selbst (Titelzeile bzw. `LIESMICH.txt`). `<profil>` ist
`standard`, `guteseele` oder `csv`. Mit Passwortschutz entsteht in allen Fällen
ein `.zip`.

---

## 1. Passwortschutz

Optional für alle drei Profile. Verfahren: **AES-256 nach WinZip-Standard**
(`encryptionStrength: 3`), erzeugt mit zip.js 2.8.34. Das ältere ZipCrypto wird
bewusst **nicht** angeboten — es ist bei bekanntem Klartext in Minuten zu
brechen, und bei einem dokumentierten Format ist der Klartext immer bekannt.

Solche Archive öffnen **weder der Windows-Explorer noch das Archivprogramm von
macOS**. Gebraucht wird 7-Zip (Windows) oder Keka bzw. The Unarchiver (macOS),
beide kostenlos. Ohne Passwort entsteht ein normales Archiv, das überall aufgeht.

Das Passwort wird nirgends gespeichert, nicht protokolliert und nicht an den
Server gesendet. Es lässt sich nicht wiederherstellen.

Dateinamen enthalten **nie** einen Patientenbezug — weder der Archivname noch
die GPX-Dateinamen. Erlaubt sind Datum, Uhrzeit und die interne Einsatz-ID.

---

## 2. Excel (Standard)

Ein Blatt namens `Einsätze`.

- Zeile 1: Titel `Einsatzdokumentation <von> – <bis>`
- Zeile 2: leer
- Zeile 3: Kopfzeile mit deutschen Beschriftungen, Autofilter
- ab Zeile 4: eine Zeile je Einsatz, sortiert nach Datum, dann Alarmzeit

Alle Zeiten stehen in **Ortszeit** ohne Zonenangabe. Leere Werte stehen als `-`
(Bindestrich), nicht als leere Zelle — das unterscheidet „nicht erhoben"
sichtbar von „übersehen". Ein **Flugtag ohne Einsatz** erscheint als eine Zeile,
in der nur Hubschrauber, Standort und Einsatzdatum gefüllt sind.

Die mit `*` markierten Spalten entfallen **ersatzlos**, wenn „Patientendaten
einschließen" nicht gesetzt ist; die übrigen rücken auf.

| # | Beschriftung |
|---|---|
| 1 | Hubschrauber |
| 2 | Standort |
| 3 | Einsatzdatum |
| 4 | Alarmzeit |
| 5 | Endzeit |
| 6 | Dauer |
| 7 | Einsatznummer * |
| 8 | Nachname * |
| 9 | Vorname * |
| 10 | Geburtsdatum * |
| 11 | Alter * |
| 12 | Einsatzort * |
| 13 | Diagnose * |
| 14 | Pilot 1 |
| 15 | Pilot 2 |
| 16 | HEMS |
| 17 | Flugretter |
| 18 | Sonstige Besatzung |
| 19 | Sekundärtransport |
| 20 | Transportziel |
| 21 | Schockraum |
| 22 | Windeneinsatz |
| 23 | Windenzyklen gesamt |
| 24 | Bergwacht |
| 25 | Bergwacht-Einheit |
| 26 | Weitere Rettungsmittel |
| 27 | Höhe Einsatzort (m) |
| 28 | Flugkilometer |
| 29 | Notizen |

29 Spalten, davon 7 geschützte. Die Beschriftungen folgen dem Wortlaut aus
`server/mission_fields.php` — die Tabelle soll dieselben Begriffe verwenden wie
das Eingabeformular.

**Alter** ist der angezeigte Wert: bei bekanntem Geburtsdatum daraus gerechnet
(bezogen auf den Einsatztag, nicht auf heute), sonst der von Hand eingetragene.
Die Spalte bleibt damit auch bei unbekannten Personen gefüllt. Das CSV
unterscheidet die beiden Fälle, siehe 3.7.

**Bewusst nicht in Excel (Standard)** (nur im CSV): Anderer Notarzt, Beschreibung
Einsatzort, Höhenmeter, alle Phasen außer Alarmierung und Endzeit, sämtliche
Koordinaten, Reanimationsdokumentation, Tracks, Ruhezeiten und die Herkunft des
Datensatzes. Ebenfalls nicht enthalten, weil in einer Übersichtstabelle
entbehrlich: Windenzyklen mit PatientIn, Luftverladung und die
Bergwacht-Zusatzangabe. Alle drei stehen weiterhin vollständig im CSV.

**Effektive Besatzung:** Für jede Rolle gilt — bei abweichender Besatzung und
belegtem Einsatzfeld der Wert vom Einsatz, sonst der Wert vom Flugtag. Woher der
Wert stammt, wird nicht ausgewiesen; in dieser Tabelle zählt nur, wer geflogen
ist.

**Fette Schrift und Fensterfixierung** sind nicht gesetzt. Die mitgelieferte
freie Ausgabe von SheetJS kann Zellformatierung und fixierte Fenster nicht
schreiben; eine Datei, die es vorgibt, wäre schlechter als eine ohne.
Spaltenbreiten, Autofilter und echte Datumszellen funktionieren.

---

## 3. CSV (Standard)

Ein ZIP-Archiv:

```
LIESMICH.txt         Aufbau, Formate, Erzeugungsdatum, App-Version, Zeitzone
felder.csv           jedes Feld jeder Tabelle: datei;feld;typ;einheit;beschreibung
einsaetze.csv        eine Zeile je Einsatz — vollständig
flugtage.csv         eine Zeile je Flugtag, auch ohne Einsatz
ruhezeiten.csv       eine Zeile je Ruhesegment
tracks/              nur bei aktiviertem Haken
  mission_000042_2026-03-14_1150.gpx
  rest_000007_2026-03-14_1330.gpx
```

### 3.1 CSV-Konventionen

Gelten für alle drei Tabellen:

- Trennzeichen **Semikolon**, Zeichensatz **UTF-8 mit BOM**, Zeilenende `CRLF`.
  So öffnet die Datei in Excel per Doppelklick spaltenrichtig und wird von
  `pandas.read_csv(sep=';')` ohne weitere Angaben gelesen.
- Quoting nach RFC 4180: Felder mit `;`, `"` oder Zeilenumbruch stehen in
  Anführungszeichen, enthaltene Anführungszeichen sind verdoppelt.
- Genau **eine** Kopfzeile mit stabilen technischen Namen. Keine Titelzeile,
  keine Leerzeile — die Datei ist maschinenlesbar, nicht hübsch.
- **Zeitstempel:** ISO 8601 mit Zonenversatz, z. B. `2026-03-14T11:50:00+01:00`.
  Zusätzlich gibt es je Tabelle die Bequemlichkeitsspalten `datum` und
  `uhrzeit_ortszeit`.
- **Datum ohne Zeit:** `JJJJ-MM-TT`.
- **Wahrheitswerte:** `1` / `0`, nie `Ja`/`Nein`.
- **Leere Werte:** leeres Feld. (Anders als in Excel (Standard), wo `-` steht:
  Dort liest ein Mensch, hier würde `-` von jedem Importer als Text
  eingelesen.)
- **Dezimaltrennzeichen: Punkt.** Das Semikolon als Feldtrenner würde zwar auch
  das deutsche Komma erlauben, aber dann liest jedes nicht-deutsche Werkzeug
  falsch. Der Punkt ist eindeutig.
- Mehrfachwerte in einer Zelle mit `|` getrennt, ohne Leerzeichen.

### 3.2 Stabiler Spaltensatz

Der Spaltensatz hängt **nicht** am Haken „Patientendaten einschließen". Ohne
Haken bleiben die `pat_`-Spalten vorhanden und leer.

Mit Web 3.3.0 hat sich der Satz um eine Spalte verschoben — nicht in der Zahl,
aber in der Zuordnung: `site_desc` ist aus dem ungeschützten Bereich
verschwunden und als `pat_ort_beschreibung` in den geschützten gewandert. Ohne
Haken ist die Beschreibung also nicht mehr enthalten. Beim **Zurücklesen** wird
die alte Kopfzeile `site_desc` weiterhin angenommen und auf dasselbe Ziel
abgebildet, damit Exportdateien früherer Versionen lesbar bleiben; der Wert
landet dann ebenfalls im verschlüsselten Block. Ein Programm, das diese
Dateien einliest, muss deshalb nicht zwei Fälle unterscheiden. `felder.csv`
beschreibt immer den vollen Formatumfang.

(Excel (Standard) verhält sich bewusst anders: Dort entfallen die geschützten
Spalten, weil eine dauerhaft leere Spalte für eine lesende Person nur Ballast
ist.)

### 3.3 Gewollte Redundanz

`hubschrauber`, `standort` und die Tagesbesatzung stehen in `einsaetze.csv`
**und** in `flugtage.csv`. Das ist Absicht: Wer nur die Einsatztabelle einliest,
soll ein vollständiges Bild haben, ohne nachschlagen zu müssen.

**Bei Abweichungen gilt `einsaetze.csv`.** `flugtage.csv` wird nur für Tage ohne
Einsatz und für Tagesnotizen gebraucht.

**Effektive Besatzung:** `crew_p1` bis `crew_other` in `einsaetze.csv` sind die
Namen, die für diesen Einsatz tatsächlich gelten — bei gesetztem
`crew_abweichend` und belegtem Einsatzfeld der Wert vom Einsatz, sonst der Wert
vom Flugtag. Die `tag_crew_`-Spalten daneben führen den unveränderten Wert des
Flugtags, sodass sich beides gegeneinander halten lässt.

### 3.4 Struktur von `rea_json`

Ein JSON-Array, auch bei nur einer Sitzung; leer, wenn keine Reanimation
dokumentiert ist. Zeitstempel im selben Format wie die übrigen Spalten.

```json
[{"beginn":"2026-03-14T11:02:00+01:00",
  "ereignisse":[{"typ":"adrenalin","bezeichnung":"Adrenalingabe",
                 "zeit":"2026-03-14T11:05:00+01:00"}]}]
```

`typ` ist der gespeicherte Schlüssel, `bezeichnung` der Klartext. Beide sind
enthalten: der Schlüssel für den Rückimport, der Klartext für Menschen, die die
Datei ohne diese Dokumentation öffnen. Beim Rückimport wird `bezeichnung`
verworfen — maßgeblich ist `typ`.

### 3.5 GPX-Dateien

- GPX 1.1, ein `<trk>` mit einem `<trkseg>` je Datei.
- Je Punkt `<trkpt lat lon>` mit `<ele>` (falls vorhanden) und `<time>` in UTC
  (`...Z`) — so schreibt es der Standard, und alle Programme erwarten es so.
- `<name>` = `Einsatz <id> — <Datum> <Uhrzeit>` bzw. `Ruhezeit <id> — …`.
  Kein Patientenbezug.
- `<metadata><time>` = Erzeugungszeit des Exports.
- Einsätze ohne Punkte bekommen **keine** Datei; `track_datei` bleibt leer.

### 3.6 `herkunft`, `edited` und `manual`

Drei Spalten, die nach einer Angabe aussehen und drei verschiedene Dinge sagen:

| Spalte | Antwortet auf | Ändert sich später |
|---|---|---|
| `herkunft` | Wie ist der Einsatz entstanden? | nein, wird beim Anlegen vergeben |
| `edited` | Wurde er danach verändert? | ja, sobald jemand ihn bearbeitet |
| `manual` | Darf die Uhr ihn noch überschreiben? | ja, als Nebenwirkung einer Bearbeitung |

Der Fall, an dem der Unterschied hängt: Ein von der Uhr aufgezeichneter Einsatz,
den jemand im Formular korrigiert, behält `herkunft = uhr` und bekommt
`edited = 1`. Er bekommt zusätzlich `manual = 1` — nicht, weil er von Hand
angelegt worden wäre, sondern damit ein späterer Upload derselben Uhr die
Korrektur nicht wieder überschreibt. Wer die Herkunft auswerten will, nimmt
`herkunft`; `manual` ist ein Schutzschalter und taugt dafür nicht.

Die Einsatzansicht der Anwendung zeigt dieselben beiden Angaben als Kennzeichen
„Uhr / manuell / importiert" und zusätzlich „editiert".

**Einschränkung für Altbestand:** Für Einsätze, die vor dem 30.07.2026 angelegt
wurden, ließ sich `edited` nur dort zuverlässig herleiten, wo der Einsatz von
der Uhr stammt. Von Hand angelegte und importierte Einsätze starten mit
`edited = 0`, auch wenn sie tatsächlich bearbeitet worden sind — eine frühere
Bearbeitung ist rückwirkend nicht mehr feststellbar. Für diesen Altbestand ist
`edited` also als „mindestens" zu lesen, nicht als „genau". `herkunft` ist davon
nicht betroffen und für den gesamten Bestand belastbar.

### 3.7 `pat_alter` und `pat_geburtsdatum`

Die Anwendung führt das Alter auf zwei Wegen: Ist ein Geburtsdatum bekannt,
rechnet sie es bezogen auf den Einsatztag aus und speichert es **nicht**. Ist
keines bekannt — bei unbekannten Personen der Regelfall — lässt es sich von Hand
eintragen und liegt dann als `age` im `pat_blob`.

`pat_alter` führt genau diesen gespeicherten Wert und ist deshalb bei einem
Einsatz mit Geburtsdatum leer. Das ist Absicht: Ein zusätzlich hineingerechnetes
Alter wäre eine zweite Quelle für dieselbe Angabe und liefe auseinander, sobald
jemand das Geburtsdatum korrigiert. Wer das Alter für eine Auswertung braucht,
rechnet es aus `pat_geburtsdatum` und `flugtag` und greift auf `pat_alter`
zurück, wenn das Geburtsdatum fehlt — dieselbe Reihenfolge, die die Anwendung
selbst anwendet.

Excel (Standard) verhält sich umgekehrt und zeigt in der Spalte `Alter` immer
den angezeigten Wert: Dort liest ein Mensch, und eine leere Zelle neben einem
leeren Geburtsdatum wäre für ihn nur eine fehlende Angabe.

### 3.8 Feldlisten

### `einsaetze.csv`

| Feld | Typ | Einheit | Beschreibung |
|---|---|---|---|
| `einsatz_id` | int | — | interne ID, Bezugsschlüssel für tracks/ |
| `flugtag` | date | — | missions.day |
| `datum` | date | — | identisch zu flugtag, für Tabellenprogramme |
| `uhrzeit_ortszeit` | time | — | Alarmzeit HH:MM, für Tabellenprogramme |
| `herkunft` | text | — | wie der Einsatz entstanden ist (`missions.origin`): `uhr` \| `manuell` \| `import` |
| `final` | 0/1 | — | abgeschlossen |
| `manual` | 0/1 | — | Schutz: Uhr überschreibt Metadaten/Phasen/Rea nicht mehr (Herkunft siehe `herkunft`) |
| `edited` | 0/1 | — | nach dem Anlegen verändert (`missions.edited`) — unabhängig von der Herkunft, nicht zu verwechseln mit `manual` |
| `hubschrauber` | text | — | Kennzeichen (Flugtag) |
| `standort` | text | — | Basis (Flugtag) |
| `tag_crew_p1` | text | — | Besatzung des Flugtags: Pilot 1 |
| `tag_crew_p2` | text | — | Besatzung des Flugtags: Pilot 2 |
| `tag_crew_hems` | text | — | Besatzung des Flugtags: HEMS |
| `tag_crew_fr` | text | — | Besatzung des Flugtags: Flugretter |
| `tag_crew_other` | text | — | Besatzung des Flugtags: Sonstige |
| `crew_abweichend` | 0/1 | — | missions.crew_override |
| `crew_p1` | text | — | tatsächliche Besatzung: Pilot 1 (effektiv, siehe 3.3) |
| `crew_p2` | text | — | tatsächliche Besatzung: Pilot 2 |
| `crew_hems` | text | — | tatsächliche Besatzung: HEMS |
| `crew_fr` | text | — | tatsächliche Besatzung: Flugretter |
| `crew_other` | text | — | tatsächliche Besatzung: Sonstige |
| `beginn` | ts | — | started_at |
| `ende` | ts | — | ended_at |
| `dauer_min` | int | min | Phase 2 → Phase 9, leer wenn unvollständig |
| `phase_02_alarmierung` | ts | — | Zeitpunkt Phase 2 (alarmierung) |
| `phase_03_abflug` | ts | — | Zeitpunkt Phase 3 (abflug) |
| `phase_04_ankunft_einsatzort` | ts | — | Zeitpunkt Phase 4 (ankunft_einsatzort) |
| `phase_05_ankunft_patientin` | ts | — | Zeitpunkt Phase 5 (ankunft_patientin) |
| `phase_06_transportbeginn` | ts | — | Zeitpunkt Phase 6 (transportbeginn) |
| `phase_07_landung_krankenhaus` | ts | — | Zeitpunkt Phase 7 (landung_krankenhaus) |
| `phase_08_uebergabezeit` | ts | — | Zeitpunkt Phase 8 (uebergabezeit) |
| `phase_09_endzeit` | ts | — | Zeitpunkt Phase 9 (endzeit) |
| `phase_02_lat` | dec | — | Breitengrad Phase 2 |
| `phase_02_lon` | dec | — | Längengrad Phase 2 |
| `phase_03_lat` | dec | — | Breitengrad Phase 3 |
| `phase_03_lon` | dec | — | Längengrad Phase 3 |
| `phase_04_lat` | dec | — | Breitengrad Phase 4 |
| `phase_04_lon` | dec | — | Längengrad Phase 4 |
| `phase_05_lat` | dec | — | Breitengrad Phase 5 |
| `phase_05_lon` | dec | — | Längengrad Phase 5 |
| `phase_06_lat` | dec | — | Breitengrad Phase 6 |
| `phase_06_lon` | dec | — | Längengrad Phase 6 |
| `phase_07_lat` | dec | — | Breitengrad Phase 7 |
| `phase_07_lon` | dec | — | Längengrad Phase 7 |
| `phase_08_lat` | dec | — | Breitengrad Phase 8 |
| `phase_08_lon` | dec | — | Längengrad Phase 8 |
| `phase_09_lat` | dec | — | Breitengrad Phase 9 |
| `phase_09_lon` | dec | — | Längengrad Phase 9 |
| `strecke_m` | int | m | Flugstrecke (distance_m) |
| `hoehenmeter_m` | int | m | Höhenmeter (ascent_m) |
| `hoehe_einsatzort_m` | int | m | Höhe des Einsatzorts |
| `transport_dest` | text | — | Transportziel |
| `schockraum` | 0/1 | — | Schockraum alarmiert |
| `secondary` | 0/1 | — | Sekundärtransport |
| `winch` | 0/1 | — | Windeneinsatz |
| `winch_cycles` | int | — | Windenzyklen gesamt (Formular: „Cycles") |
| `winch_cycles_pat` | int | — | Windenzyklen mit PatientIn (Formular: „Cycles mit Patient") |
| `winch_airload` | 0/1 | — | Luftverladung |
| `bergwacht` | 0/1 | — | Bergwacht beteiligt |
| `bw_unit` | text | — | Bergwacht-Einheit |
| `bw_info` | text | — | Bergwacht: Namen / Infos |
| `other_ema` | text | — | Anderer Notarzt |
| `weitere_rettungsmittel` | text | — | mission_resources.name, mit `\|` verkettet |
| `notizen` | text | — | missions.notes |
| `pat_mission_no` | text | — | Einsatznummer (pat_blob.mission_no) |
| `pat_nachname` | text | — | pat_blob.last |
| `pat_vorname` | text | — | pat_blob.first |
| `pat_geburtsdatum` | date | — | pat_blob.dob |
| `pat_alter` | int | Jahre | pat_blob.age — nur belegt, wenn kein Geburtsdatum vorliegt; sonst folgt das Alter aus `pat_geburtsdatum` und `flugtag` (siehe 3.7) |
| `pat_diagnose` | text | — | pat_blob.dx |
| `pat_ort_adresse` | text | — | pat_blob.loc.addr |
| `pat_ort_lat` | dec | — | pat_blob.loc.lat |
| `pat_ort_lon` | dec | — | pat_blob.loc.lon |
| `pat_ort_beschreibung` | text | — | pat_blob.site_desc (bis Web 3.2.0: ungeschützte Spalte `site_desc`) |
| `rea_json` | json | — | Reanimationssitzungen mit Ereignissen, siehe 3.4; leer wenn keine Reanimation |
| `track_datei` | text | — | relativer Pfad unter tracks/, oder leer |
| `track_punkte` | int | — | Anzahl Trackpunkte |

### `flugtage.csv`

| Feld | Typ | Einheit | Beschreibung |
|---|---|---|---|
| `flugtag` | date | — | days.day |
| `hubschrauber` | text | — | Kennzeichen |
| `standort` | text | — | Basis |
| `crew_p1` | text | — | Pilot 1 |
| `crew_p2` | text | — | Pilot 2 |
| `crew_hems` | text | — | HEMS |
| `crew_fr` | text | — | Flugretter |
| `crew_other` | text | — | Sonstige |
| `notizen` | text | — | days.notes |
| `anzahl_einsaetze` | int | — | Anzahl Einsätze an diesem Flugtag im Export |

### `ruhezeiten.csv`

| Feld | Typ | Einheit | Beschreibung |
|---|---|---|---|
| `ruhezeit_id` | int | — | interne ID, Bezugsschlüssel für tracks/ |
| `flugtag` | date | — | rest_segments.day |
| `beginn` | ts | — | started_at |
| `ende` | ts | — | ended_at |
| `dauer_min` | int | min | ende − beginn |
| `final` | 0/1 | — | abgeschlossen |
| `track_datei` | text | — | relativer Pfad unter tracks/, oder leer |
| `track_punkte` | int | — | Anzahl Trackpunkte |

---

## 4. Excel (GuteSeele)

Erhält das bisherige Listenlayout für die Weitergabe an Dritte. Aufbau exakt wie
das Importprofil `ch17_jahresliste`:

- Titelzeile in F1: `Einsatzdokumentation Christoph 17 - <Jahr>`
- Zeile 2 leer, Kopfzeile in Zeile 3, Daten ab Zeile 4
- Spalten: Datum, Zeit, Name, Geb.dat, Vers., Einsatzort, RTW, Diagnose,
  Transport, Winde, HEMS, Pilot, Einsatz-Nr

Rückabbildung: `Datum` als `T.M.` ohne Jahr, `Zeit` als `HH:MM` Ortszeit, `Name`
als `Nachname, Vorname`, `Winde` als `j` bzw. leer, `RTW` aus den weiteren
Rettungsmitteln mit `, ` verkettet, `HEMS` und `Pilot` als effektive Besatzung.

**`Vers.` bleibt leer** — das Feld wird im System nicht geführt und wurde schon
beim Import bewusst verworfen. Es wird insbesondere *nicht* aus
„Sekundärtransport" hergeleitet.

Umfasst der Zeitraum mehrere Kalenderjahre, entsteht **je Jahr ein Blatt**,
benannt nach dem Jahr. Ohne Patientendaten bleiben Name, Geb.dat, Diagnose und
Einsatzort leer — die Datei behält ihr Layout, ist inhaltlich aber weitgehend
leer.

---

## 5. Rückimport

Zwei Formate auf derselben Seite unter „Import".

### 5.1 `export_csv_v1` — verlustfrei

Liest `einsaetze.csv`, wahlweise als einzelne Datei oder direkt aus dem `.zip`
(bei einem passwortgeschützten Archiv wird nach dem Passwort gefragt).
Übernommen werden alle Einsatzfelder, die Phasen 2–9 samt Koordinaten, die
weiteren Rettungsmittel und die Reanimationsdokumentation.

Drei bewusste Ausnahmen:

- **`einsatz_id` wird nicht übernommen.** IDs sind kontospezifisch; ein Import
  in ein anderes Konto vergibt neue.
- **GPX-Dateien werden nicht eingelesen.** Tracks stammen von der Uhr und sind
  der Rohbestand; ein Rückspielen über den Export wäre ein zweiter, schlechterer
  Weg neben dem Backup. Wer Tracks braucht, nutzt das Backup.
- **Hubschrauber und Standort** werden wie bei jedem Import oben auf der Seite
  ausgewählt. Ein Kennzeichen aus der Datei würde sonst stillschweigend neue
  Stammdaten anlegen.

Ebenfalls nicht übernommen werden **`herkunft` und `edited`**. Beide beschreiben,
wie ein Datensatz *in der Installation entstanden ist, aus der die Datei stammt*.
Beim Einlesen entsteht er neu: Die Herkunft wird auf „importiert" gesetzt, der
Bearbeitungsstatus beim Aktualisieren eines bestehenden Einsatzes. Ein Wert aus
der Datei wäre an dieser Stelle eine Aussage über ein fremdes Konto.

Eine Exportdatei ohne die Spalte `edited` (Auslieferungen bis Web 3.3.2) oder
ohne `pat_alter` (bis Web 3.4.0) lässt sich unverändert einlesen — die
Formaterkennung zählt Treffer gegen die erwarteten Spaltennamen bei einem
Schwellwert von 20, und ein oder zwei fehlende von 78 ändern daran nichts.

Dubletten werden zuerst über die Einsatznummer erkannt (clientseitig gegen die
entschlüsselten Bestandsdaten), hilfsweise über Tag und Alarmzeit.

**Eigenheit beim Rundlauf:** Der Export schreibt die *effektive* Besatzung. Ein
Einsatz, bei dem nur eine Rolle abwich und die übrigen vom Flugtag geerbt waren,
hat nach dem Rückimport alle Rollen ausdrücklich gespeichert. Die effektive
Besatzung und damit auch eine erneut exportierte Datei sind identisch — nur die
Speicherung ist expliziter.

### 5.2 `export_excel_v1` — verdichtet

Liest den Standard-Excel-Export (Kopfzeile in Zeile 3). Vor dem Import wird
angezeigt:

> Diese Datei enthält nicht alle Felder, die das System kennt. Nach dem Import
> bleiben leer: die Phasen Abflug, Ankunft Einsatzort, Ankunft PatientIn,
> Transportbeginn, Landung Krankenhaus und Übergabezeit, sämtliche Koordinaten,
> die Reanimationsdokumentation, der Track (und damit auch die Flugkilometer)
> sowie ein von Hand eingetragenes Alter ohne Geburtsdatum. Für einen
> vollständigen Rückweg nutze den CSV-Export, für eine echte Wiederherstellung
> das Backup.

Die Aussagerichtung ist bewusst „bleibt leer" und nicht „geht verloren": Die
Angaben stehen in dieser Datei nie drin, es werden lediglich Felder nicht
befüllt.

**Ignorierte Spalten:** `Dauer` ist gerechnet, nicht gespeichert; sie zu
übernehmen würde zu Widersprüchen führen, sobald jemand eine Zeit korrigiert.
`Flugkilometer` wird ebenfalls verworfen, weil der Wert aus dem Track stammt und
ohne Track nicht nachvollziehbar wäre.

`Alter` wird ebenfalls verworfen, aber aus einem anderen Grund: Die Spalte führt
mal einen gerechneten, mal einen gespeicherten Wert (siehe 2), und beim Einlesen
lassen sich die beiden Fälle nur über das Geburtsdatum derselben Zeile
auseinanderhalten. Ein gerechnetes Alter, das dabei versehentlich im `pat_blob`
landet, liefe bei der nächsten Korrektur des Geburtsdatums auseinander. Der
verlustfreie Weg ist `pat_alter` im CSV.

`Alarmzeit` setzt Phase 2, `Endzeit` setzt Phase 9. Eine Zeile für einen
**Flugtag ohne Einsatz** (nur Hubschrauber, Standort und Datum gefüllt) legt den
Flugtag an, aber keinen Einsatz.

---

## 6. Grenzen

- Höchstens **5000 Einsätze** je Export. Darüber meldet der Server einen Fehler
  und rät zu kleineren Zeiträumen.
- Gelöschte Einsätze und Flugtage (Papierkorb) tauchen in keinem Profil auf.
- Tracks werden blockweise geladen (höchstens 25 auf einmal); bei vielen
  Einsätzen mit Track dauert der Aufbau entsprechend, der Fortschritt wird
  angezeigt.
