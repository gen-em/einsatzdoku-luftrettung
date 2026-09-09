# Bestandsaufnahme R39 — zentrale Stammdaten

**Was ist das hier.** Eine Aufstellung aller Stellen im Repositorium, die das
Modell der **zentralen Stammdaten** (`user_id IS NULL`) tragen. Sie ist die
Vorarbeit für den Rückbau nach **Rahmenplan R39** und ersetzt kein Konzept:
Sie sagt, **wo** etwas steht und **was daran hängt**, nicht, in welcher
Reihenfolge es fällt.

**Anlass.** Am 09.09.2026 kam auf die Rückfrage nach dem Umgang mit
systemweiten Standorten die Auskunft: *„es gibt keine systemweiten Standorte
mehr, alle gelöscht"* und *„die wollten wir doch ganz abschaffen"*. Der
Beschluss dazu steht seit dem 30.08.2026 als **R39** im Rahmenplan
(`docs/Rahmenplan.md`, Abschnitt 7; Volltext in `docs/Rahmenplan-Archiv.md`)
und ist dort P5 zugeordnet. **Das S9-Konzept hat ihn nie aufgenommen** —
`grep -c "R39" docs/konzepte/` ergab am 09.09.2026 **0**. In S9/AP5-4 ist
deshalb `server/admin_stammdaten.php` neu gebaut worden: eine Seite für ein
Modell, das abgeschafft werden soll.

---

## 1. Verfahren und Grenzen

Sechs Flächen wurden parallel abgesucht (Schema und Migrationen, PHP der
Weboberfläche, Sicherung/Import/Export/API, Dokumentation, Rahmenplan und
Konzepte, Prüfmittel und Referenzbestand). Ergebnis: **208 Befunde**.

**Diese Liste ist eine Fundliste, kein Prüfbericht.** Die geplante
Gegenprüfung jedes einzelnen Befundes wurde abgebrochen — sie hätte Stunden
gelaufen. **Von Hand nachgeprüft ist allein Abschnitt 3.** Alles in
Abschnitt 5 ist ein *Hinweis auf eine Fundstelle*, der beim Rückbau am Code
zu bestätigen ist. Wer eine Zeilennummer von dort übernimmt, sieht vorher
nach: Sie stammt vom Stand des Zweigs `claude/go-bwucrx` am 09.09.2026 und
wandert mit jeder Änderung.

### Verteilung der 208 Befunde

| Fläche | Befunde | davon Schema | Code | Doku | Prüfmittel | Daten |
|---|---:|---:|---:|---:|---:|---:|
| A Datenbankschema und Migrationen | 29 | 20 | 5 | 2 | 1 | 1 |
| B PHP der Weboberfläche | 59 | — | 49 | 9 | — | 1 |
| C Sicherung, Import, Export, API | 30 | 3 | 17 | 6 | 1 | 3 |
| D Dokumentation | 21 | — | — | 21 | — | — |
| E Rahmenplan, Backlog und Konzepte | 44 | — | — | 42 | 1 | 1 |
| F Prüfmittel und Referenzbestand | 25 | — | — | 3 | 19 | 3 |
| **Summe** | **208** | **23** | **71** | **83** | **22** | **9** |

Die Spaltensummen sind aus der Fundliste gezählt, nicht geschätzt.

---

## 2. Woraus das Modell besteht

Sieben Tabellen tragen es (`server/schema.sql`):

| Tabelle | Zeile | Träger |
|---|---:|---|
| `bases` | 73 | `user_id INT UNSIGNED NULL` — *NULL = zentral* |
| `vehicles` | 101 | dieselbe Spalte |
| `crew_presets` | 146 | dieselbe Spalte |
| `resources` | 170 | dieselbe Spalte |
| `bw_units` | 182 | dieselbe Spalte |
| `transport_dests` | 160 | dieselbe Spalte |
| `user_bases` | 89 | die **Auswahltabelle** (E16) — existiert nur für zentrale Standorte |

Dazu die Migration `2026_07_26_zentrale_stammdaten`
(`server/migration_lib.php:589`), die diese Nullbarkeit hergestellt hat, und
die Nutzlast der Kontosicherung, die `user_bases` **als Namensliste** führt
(`server/backup_lib.php:618-624`, dokumentiert in `docs/Backup-Format.md`).

---

## 3. Von Hand nachgeprüfte Kernbefunde

Diese fünf Punkte sind am Code und an der laufenden lokalen Installation
gemessen, nicht aus der Fundliste übernommen.

### 3.1 Das Löschen eines Standorts nimmt alles mit — bis auf die Diensttage

`bases` hängt an sechs Fremdschlüsseln mit **`ON DELETE CASCADE`**
(`schema.sql:94, 119, 154, 165, 175, 193`): `user_bases`, `vehicles`,
`crew_presets`, `resources`, `bw_units`, `transport_dests`. Über `vehicles`
läuft die Kaskade weiter auf `vehicle_roles` und `vehicle_capabilities`.
**`days` dagegen trägt `ON DELETE SET NULL`** für `vehicle_id` und `base_id`
(`schema.sql:252-253`). Ein Diensttag verliert also seine *Kennungen*, nicht
seine Angaben: `days.base_name` und `days.vehicle_name` sind beim Anlegen
**eingefroren** und stehen weiter da. Anzeige, Ausdruck und Ausfuhr bleiben
vollständig; verloren gehen die Filter und Auswertungen, die über die
Kennung gruppieren.

### 3.2 Der Zähler „Zuordnung offen" springt **nicht** an — Korrektur

Ich hatte das Gegenteil angenommen. `nb_offene_tage()`
(`server/nachbearbeitung_lib.php:98-121`) findet tatsächlich jeden Diensttag
mit `vehicle_id IS NULL` oder `base_id IS NULL` — aber die Seite und der
Zähler hängen an `nb_moeglich()` (`:196-204`), und das fragt seit Web 16.0.0
**nur noch die vier Tabellen aus `NB_NOTNULL`** (`:77`), deren `base_id` in
einer fertig migrierten Anlage `NOT NULL` trägt. Dann ist `nb_moeglich()`
falsch, und `nb_offen_gesamt()` gibt vor jeder Abfrage `0` zurück (`:166`).

**Die Folge ist also bedingt:** Steht in einer Anlage auch nur **eine** der
vier Tabellen noch auf `base_id NULL`-fähig — weil `nb_notnull_ziehen()` dort
nie gelaufen ist —, dann erscheint die Nachbearbeitung wieder, und jeder
Diensttag, dessen zentraler Standort gelöscht wurde, steht darin als offener
Punkt. Prüfen lässt sich das mit einer Abfrage:

```sql
SELECT table_name, is_nullable FROM information_schema.columns
 WHERE table_schema = DATABASE() AND column_name = 'base_id'
   AND table_name IN ('crew_presets','transport_dests','resources','bw_units');
```

Viermal `NO` heißt: nichts zu befürchten. Ein `YES` heißt: nachsehen.

### 3.3 Verwaiste Vorbelegungen — ein Fehler, unabhängig von R39

`user_defaults` hat **keinen Fremdschlüssel auf `item_id`**
(`schema.sql:201-208`, Begründung im Kommentar ab `:196` — zwei Zieltabellen). Beide Löschwege
räumen die Vorbelegung des **Standorts** ab
(`admin_stammdaten.php:164`, `einstellungen.php:718`) — aber **nicht** die
Vorbelegungen der Rettungsmittel, die mit dem Standort kaskadieren. Gemessen
an der lokalen Anlage am 09.09.2026, in einer zurückgerollten Transaktion:

```
vorbelegung_vorher    1
rettungsmittel_weg    0   (die Zeile in vehicles ist fort)
vorbelegung_verwaist  1   (die Zeile in user_defaults steht noch)
```

Die Wirkung ist klein und still: `dt_standardwerte()`
(`diensttag_lib.php:349`) liefert eine tote Kennung, das Auswahlfeld findet
dazu keinen Eintrag und belegt nichts vor. Niemand sieht einen Fehler,
niemand kann die Zeile loswerden. **Aufgenommen als Backlog Nr. 167.**

### 3.4 Der Referenzbestand kennt das Modell überhaupt nicht

Gezählt an der lokalen Anlage am 09.09.2026:

| Tabelle | Zeilen gesamt | davon zentral |
|---|---:|---:|
| `bases` | 8 | **0** |
| `vehicles` | 21 | **0** |
| `crew_presets` | 60 | **0** |
| `transport_dests` | 32 | **0** |
| `resources` | 32 | **0** |
| `bw_units` | 12 | **0** |
| `user_bases` | 0 | — |

Damit ist auch **F-S9-U-33** an der Wurzel erklärt: Der Platzhalter
`__ADMIN_STANDORT__` des Bilderlaufs (`tools/screenshots/aufnehmen.mjs:343`)
lässt sich nicht auflösen, weil es keinen zentralen Standort zu verlinken
gibt. Die acht ausgefallenen Aufnahmen sind kein Sitzungsproblem. Die
Klickprobe misst die Adminseite deshalb an einem Fall, den sie **selbst
anlegt und hinterher wieder abräumt** (`tools/klickprobe/wege/ap5.mjs:561`) —
das ist belastbar, aber es ist auch der einzige Weg, auf dem die Seite
überhaupt Inhalt hat.

### 3.5 Was heute noch zentrale Stammdaten anlegen kann

Zwei Stellen, beide für Administratorinnen:

- `server/admin_stammdaten.php` — seit S9/AP5-4 neu gebaut, mit zwölf
  Öffnern für sechs Dialoge. Sie legt Standorte, Rettungsmittel,
  Besatzungen, Zielkliniken, weitere Rettungsmittel und
  Bergwacht-Bereitschaften **systemweit** an.
- `server/einstellungen.php:1774-1788` — die Karte **„Vordefinierte
  Standorte"** in den Einstellungen jedes Kontos. Sie wird **immer**
  gezeichnet, auch bei null zentralen Standorten, und zeigt dann
  „0 · 0 ausgewählt" und den Satz „Keine vordefinierten Standorte
  hinterlegt."

---

## 4. Offene Entscheidung

**Frage 10 — wird der Rückbau nach R39 vorgezogen, oder bleibt er in P5?**
Drei Wege stehen im Prüfdokument zu S9; die Empfehlung nach dieser
Bestandsaufnahme lautet **Weg (c)**: jetzt nur die **Tür schließen** — kein
Anlegen neuer zentraler Stammdaten mehr, die leere Karte in den Einstellungen
weg —, den **Schemarückbau aber in P5** lassen, wo R39 ihn hinstellt. Die
Begründung steht im Prüfdokument zu S9 bei Frage 10.

Solange die Entscheidung offen ist, wird an dieser Liste nichts abgearbeitet.

---

## 5. Fundliste

Sortiert nach Fläche, innerhalb der Fläche nach Fundstelle. **Ungeprüft** —
siehe Abschnitt 1.

### A — Datenbankschema und Migrationen

29 Befunde (code 5, daten 1, doku 2, pruefmittel 1, schema 20).

**`docs/Backup-Format.md:541-545`** — Das dokumentierte Format nennt user_bases als Namensliste mit Begründung (Zentrale Standorte selbst gehören dem Konto nicht und werden nicht exportiert — die Auswahl schon).
*Rückbau:* Feld austragen und eine neue Nutzlastversion beschreiben; die Versionsliste steht ab Zeile 373.
*Risiko:* Format und Code müssen zusammen wandern. Ein Dokument, das ein Feld nennt, das der Export nicht mehr schreibt, macht jede spätere Fehlersuche am Einspielweg falsch.

**`docs/Technik.md:554, :556, :557, :559, :560, :561`** — Datenmodell-Tabelle des Technikdokuments. Sechs Zeilen erklären user_id NULL = zentral und die Zeile 556 beschreibt user_bases samt E16.
*Rückbau:* Zeile 556 (user_bases) ganz streichen; in den Zeilen 554, 557, 559, 560, 561 den Zusatz user_id NULL = zentral austragen. Die Zeile zu user_defaults (561) nennt persönlich oder zentral und ist mitzuziehen.
*Risiko:* Abschnitt 2 der Arbeitsanweisung verlangt ausdrücklich das Austragen entfernter Funktionen. Bleibt die Zeile stehen, beschreibt das einzige Architekturdokument eine Tabelle, die es nicht mehr gibt.

**`server/backup_lib.php:619-624, :731, :1057-1066`** — Nutzlast des Kontobackups: user_bases wird als Namensliste exportiert (Zeile 621-624, 731) und beim Einspielen wieder in die Tabelle geschrieben (Zeile 1061-1066). Das Feld ist Teil des dokumentierten Formats.
*Rückbau:* Feld aus Export und Import entfernen; der Import muss ein vorhandenes user_bases in älteren Paketen still übergehen, nicht daran scheitern. Nutzlastversion und Format sind mitzuziehen.
*Risiko:* Ein Bestandspaket (Nutzlastversion 10) enthält das Feld. Ein Einspielweg, der es nach dem Rückbau noch schreibt, greift auf eine gelöschte Tabelle und bricht mitten in der Transaktion ab.

**`server/db.php:58-95`** — stammdaten_dup_global() und stammdaten_dup_personal_count(). Der Kommentar Zeile 58-63 sagt es wörtlich: Die UNIQUE-Keys (user_id, name) greifen bei NULL nicht, daher muss die Duplikatprüfung in der Anwendung erfolgen.
*Rückbau:* Beide Funktionen entfallen mit dem zentralen Modell — die Datenbank übernimmt die Prüfung wieder. Aufrufer prüfen: admin_stammdaten.php, backup_lib.php:1037, backup_lib.php:1436-Umfeld.
*Risiko:* stammdaten_dup_global() wird auch auf dem Einspielweg des Kontobackups benutzt (backup_lib.php:1037). Wird sie ersatzlos gestrichen, ohne den Aufruf umzubauen, läuft der Import in einen Fehler statt in ein sauberes Überspringen.

**`server/demo_lib.php:407-411`** — Liste der Tabellen, aus denen die Kontolöschung DELETE ... WHERE user_id = ? fährt; user_bases steht darin.
*Rückbau:* Eintrag user_bases aus der Liste nehmen, sonst DELETE auf eine nicht mehr vorhandene Tabelle.
*Risiko:* Diese Schleife ist der Löschweg des Demokontos und wird laut Kommentar auch von der Kontolöschung gegangen. Ein Fehler hier bricht die Kontolöschung ab.

**`server/einstellungen.php:660-676 und :1485-1535`** — Kontoseite schreibt und löscht Zeilen in user_bases (INSERT IGNORE / DELETE) und listet die zentralen Standorte zur Auswahl.
*Rückbau:* Beide Schreibwege und die Auswahlliste entfallen. Gehört zur Fläche Oberfläche, steht hier, weil es die einzigen Schreibzugriffe auf user_bases außerhalb von Migration und Backup sind.
*Risiko:* Bleibt ein Schreibweg stehen, während die Tabelle fällt, wirft die Einstellungsseite einen Datenbankfehler an einer Stelle, an der sonst nichts passiert wäre.

**`server/install.php:39 und :213`** — Neuinstallation spielt server/schema.sql ein; es gibt keinen zweiten Schemaweg. install.php legt keine zentralen Stammdaten an (nur users und password_resets, Zeile 279/284).
*Rückbau:* Nichts zu ändern — aber der Beleg dafür, dass schema.sql die einzige Quelle des Sollzustands ist. Migrierte Datenbank und Neuinstallation müssen nach dem Rückbau in denselben Spalten übereinstimmen.
*Risiko:* Wenn nur schema.sql geändert wird und keine Migration folgt, laufen frische und gewachsene Installationen auseinander — genau die Lage, die nachbearbeitung_lib.php:267-270 zu schließen versucht.

**`server/komplett_lib.php:286-292 und :759-771`** — Komplettsicherung ermittelt die Tabellen dynamisch (SHOW FULL TABLES) und schreibt SHOW CREATE TABLE je Tabelle in den Dump.
*Rückbau:* Kein Codeeingriff nötig. Aber: Ein VOR dem Rückbau erzeugter Komplettdump enthält CREATE TABLE user_bases und die nullbaren user_id-Spalten. Ein Rückspielen nach dem Rückbau stellt das alte Schema wieder her, ohne dass ein Migrationslauf das bemerkt (schema_migrations kommt aus demselben Dump).
*Risiko:* Stiller Rückfall auf das alte Modell. Die Kopfzeilen des Dumps nennen den Migrationsstand (komplett_lib.php:784-800) — nach dem Rückbau muss das Runbook sagen, dass ein älterer Dump nach dem Einspielen einen Migrationslauf braucht.

**`server/migration_lib.php:1181-1188`** — Innerhalb von 2026_08_17_notarzt_erweiterung: CREATE TABLE IF NOT EXISTS user_bases, Abschnitt 4. Auswahl zentraler Standorte.
*Rückbau:* Ebenfalls stehen lassen; die neue Migration löscht die Tabelle am Ende. Die Kennung selbst bleibt im Register.
*Risiko:* Reihenfolge: DROP TABLE user_bases muss NACH dem NOT NULL auf bases stehen, sonst hängt der FK base_id noch an einer Tabelle, deren Zeilen gerade geprüft werden.

**`server/migration_lib.php:1468-1491`** — Umbau der eindeutigen Schlüssel auf (user_id, base_id, name) für bw_units, resources, transport_dests und auf (user_id, name) für vehicles, mit ausführlicher Begründung, warum der Standort im Schlüssel stehen muss.
*Rückbau:* Die Schlüsselform bleibt richtig und wird durch NOT NULL erst wirksam. Der Begleittext im Schema und in Technik.md ist um den Satz zu ergänzen, dass der Schlüssel jetzt tatsächlich greift.
*Risiko:* Kein technisches. Aber die Aussage der Schlüssel verhindert Dubletten ist heute falsch und wäre nach dem Rückbau wahr — wer sie vorher zitiert, zitiert eine Zusage, die es nicht gibt.

**`server/migration_lib.php:1492-1514`** — Zuordnung der base_id über COALESCE(user_id, 0) — der Zuständigkeitsbereich, wobei 0 für zentral steht. Danach der endgültige Schlüssel uq_user_base_role_name.
*Rückbau:* Historie, bleibt. Zeigt aber, dass das zentrale Modell bis in die Zuordnungslogik der Migrationen reicht; ein späterer Nachlauf dieser Migration auf einer alten Datenbank würde den zentralen Bereich wieder bedienen.
*Risiko:* Eine Installation, die von einem sehr alten Stand einspielt, läuft erst durch diese Migration und danach durch den Rückbau. Die Reihenfolge muss stimmen, sonst legt Schritt A an, was Schritt B gerade weggeräumt hat.

**`server/migration_lib.php:1528-1534`** — INSERT IGNORE INTO user_bases (user_id, base_id) SELECT DISTINCT d.user_id, d.base_id FROM days d JOIN bases b ... WHERE b.user_id IS NULL — füllt die Auswahl aus den bestehenden Diensttagen.
*Rückbau:* Entfällt mit der Tabelle. Zeigt aber den einzigen Weg, auf dem user_bases jemals automatisch gefüllt wurde.
*Risiko:* Keines beim Rückbau. Beim Lesen wichtig: Wenn user_bases heute leer ist, heißt das nicht, dass es nie zentrale Standorte gab — die FK ON DELETE CASCADE hat die Zeilen mit den Standorten mitgenommen.

**`server/migration_lib.php:2306-2425`** — Migration 2026_09_07_rettungsmittel_typ (Web 16.0.0): ALTER TABLE vehicles MODIFY base_id INT UNSIGNED NULL (Zeile 2390) plus typ/kurz. Sie ist der Grund, warum zentrale Rettungsmittel ohne Standort existieren können.
*Rückbau:* Bleibt. Aber sie ist die Ursache dafür, dass die Löschung aller zentralen Standorte den zentralen Bestand NICHT vollständig geräumt hat: ohne base_id greift kein CASCADE. Der Rückbau muss diese Zeilen ausdrücklich behandeln.
*Risiko:* Wird das übersehen, meldet die neue Migration einen Fehler beim NOT NULL — oder, falls sie ohne Prüfung löscht, verschwinden Rettungsmittel, die in Diensttagen als vehicle_id verwiesen sind.

**`server/migration_lib.php:2432-2460`** — migrationen_inhalt_zaehlen(): zählt je Spalte, wie viele Zeilen NICHT NULL und nicht leer sind. Das ist der einzige Inhaltsschutz vor zerstörenden Migrationen; er hängt an der Liste inhalt => [[Tabelle, Spalte, Beschreibung]].
*Rückbau:* Für den Rückbau UNBRAUCHBAR und deshalb hier vermerkt: Die zentralen Zeilen sind genau die mit user_id IS NULL — die Funktion zählt das Gegenteil. Eine ganze Tabelle (user_bases) kann sie ohnehin nicht prüfen. Der Rückbau braucht eine eigene Prüfung im skip- bzw. run-Zweig.
*Risiko:* Wer inhalt naiv mit ['bases','user_id',...] befüllt, bekommt einen Zähler, der bei vollem zentralem Bestand Null meldet und die Migration durchwinkt. Genau der Fall, gegen den die Mechanik gebaut wurde.

**`server/migration_lib.php:589-624`** — Migration 2026_07_26_zentrale_stammdaten (Web 2.4.0) — die Migration, die das zentrale Modell eingeführt hat. Zeilen 599-603 sind die fünf ALTER TABLE ... MODIFY user_id INT UNSIGNED NULL für bases, aircraft, crew_presets, resources, bw_units; Zeile 604-610 legt transport_dests gleich mit nullbarer user_id an.
*Rückbau:* NICHT ändern. Der Katalog ist Historie und wird nur hinten ergänzt (Dateikopf Zeile 21-24). Der Rückbau ist eine NEUE Migration am Ende von migrationen_katalog(), die genau diese fünf MODIFY umkehrt.
*Risiko:* Wer die alte Migration umschreibt, macht eine Installation, die noch dazwischen steht, unmigrierbar — und der Katalog wird beim Merge gegengezählt (Rahmenplan 2.2).

**`server/nachbearbeitung_lib.php:59-77, :207-216, :263-303`** — NB_STAMMDATEN (fünf Tabellen) und NB_NOTNULL (vier: crew_presets, transport_dests, resources, bw_units) plus nb_notnull_ziehen(), das per Knopfdruck ALTER TABLE ... MODIFY base_id NOT NULL ausführt. Eine Schemaänderung außerhalb des Migrationskatalogs.
*Rückbau:* Muss beim Rückbau mitgedacht werden: Der Ist-Zustand des Produktivsystems (base_id nullbar oder nicht) entscheidet, ob noch zentrale Restzeilen ohne Standort existieren können. nb_stammdaten_offen_gesamt() zählt ausdrücklich über alle Konten hinweg, also auch zentrale Zeilen mit.
*Risiko:* Zwei Wege ändern dasselbe Schema — Migrationskatalog und diese Seite. Eine Rückbau-Migration, die base_id nicht mitbedenkt, kann auf einer Installation laufen, auf der die Spalte noch nullbar ist, und dort Zeilen stehen lassen, die sie auf einer anderen nicht findet.

**`server/schema.sql:101 und :117`** — vehicles.user_id NULL = zentral, dazu UNIQUE KEY uq_user_name (user_id, name), der bei NULL nicht greift.
*Rückbau:* user_id auf NOT NULL. Zusätzlich muss vorher der Bestand geklärt werden: zentrale Rettungsmittel mit base_id IS NULL sind von der Löschung der Standorte NICHT erfasst worden (kein CASCADE-Pfad).
*Risiko:* Werden diese Zeilen übersehen, bricht das ALTER TABLE ab. Werden sie blind gelöscht, verschwinden Rettungsmittel, die bereits in Diensttagen benutzt wurden — die Tage selbst bleiben durch E8 lesbar, verlieren aber ihren FK.

**`server/schema.sql:146 und :152`** — crew_presets.user_id NULL = zentral; UNIQUE KEY uq_user_base_role_name (user_id, base_id, role_code, name).
*Rückbau:* user_id auf NOT NULL; der Schlüssel bleibt in der Form, wird aber erst dadurch wirksam.
*Risiko:* base_id ist hier NOT NULL mit FK ON DELETE CASCADE auf bases — die zentralen Vorbelegungen sind mit den Standorten bereits mitgelöscht worden. Nur wenn nb_notnull_ziehen() nie lief, können Zeilen mit base_id IS NULL übrig sein; die sind in keiner Oberfläche mehr sichtbar.

**`server/schema.sql:160 und :163`** — resources.user_id NULL = zentral; UNIQUE KEY uq_user_base_res (user_id, base_id, name).
*Rückbau:* user_id auf NOT NULL, Kommentar streichen.
*Risiko:* Wie crew_presets: Restzeilen nur bei noch nullbarer base_id möglich, dann aber unsichtbar und unlöschbar über die Oberfläche.

**`server/schema.sql:170 und :173`** — bw_units.user_id NULL = zentral; UNIQUE KEY uq_user_base_name (user_id, base_id, name).
*Rückbau:* user_id auf NOT NULL, Kommentar streichen.
*Risiko:* Wie crew_presets.

**`server/schema.sql:182 und :191`** — transport_dests.user_id NULL = zentral; UNIQUE KEY uq_user_base_name (user_id, base_id, name).
*Rückbau:* user_id auf NOT NULL, Kommentar streichen.
*Risiko:* Wie crew_presets. Zusätzlich hängen an dieser Tabelle Koordinaten (lat/lon), die am Einsatz eingefroren werden — verlorene Vorschläge bedeuten also keinen Datenverlust an bestehenden Einsätzen.

**`server/schema.sql:196-208`** — user_defaults: Kommentar sagt ausdrücklich funktioniert für persönliche UND zentrale Einträge; item_id verweist ohne Fremdschlüssel auf bases.id bzw. vehicles.id (zwei Zieltabellen).
*Rückbau:* Der Kommentar ist auszutragen. Wichtiger: weil kein FK besteht, hat die Löschung der zentralen Standorte hier WAISEN hinterlassen — Zeilen mit kind='base' und item_id auf einen nicht mehr existierenden Standort. Der Rückbau muss sie aufräumen (DELETE ... WHERE NOT EXISTS).
*Risiko:* Ohne Aufräumen bleiben die Waisen dauerhaft stehen. Sie tun heute nichts Sichtbares, weil dt_base_erlaubt() (diensttag_lib.php:371-379) sie verwirft — aber sie belegen den UNIQUE-Schlüssel uq_user_kind (user_id, kind) und verhindern damit still, dass die betroffene NutzerIn einen neuen Standard-Standort setzt.

**`server/schema.sql:227-256`** — days mit FK base_id und vehicle_id auf ON DELETE SET NULL sowie den Snapshot-Spalten (E8). Der Kommentar :218-225 sagt, die FK dienten nur noch dem Filtern und Auswerten, niemals der Anzeige.
*Rückbau:* Am Schema selbst nichts. Aber der Kommentar ist nachzuschärfen: Die FK werden sehr wohl noch benutzt — für die Vorschlagslisten am Einsatzformular (einsatz_form.php:69 ff.), für die Offen-Zählung (nachbearbeitung_lib.php:113) und als portabler Verweis im Backup (backup_lib.php:525-526).
*Risiko:* Wer dem Kommentar glaubt, hält das Nullen dieser FK für folgenlos. Es ist es nicht — die drei genannten Wege sind auf dem Produktivsystem seit der Löschung betroffen.

**`server/schema.sql:60-68`** — Kopfkommentar STAMMDATEN. Erklärt E15 (Standort als Anker), nennt aber die zentrale Ebene nicht — die Erklärung dazu steht in den Spaltenkommentaren.
*Rückbau:* Absatz nach dem Rückbau gegenlesen: Wenn die Zeilen NULL = zentral verschwinden, muss der Kopf sagen, dass es genau eine Ebene gibt (persönlich), sonst steht dort eine halbe Erklärung.
*Risiko:* Ein Schema, dessen Kommentare die entfernte Ebene weiter voraussetzen, führt die nächste Instanz in die Irre.

**`server/schema.sql:668-755`** — Register schema_migrations mit INSERT IGNORE aller Kennungen als skipped; letzter Eintrag 2026_09_07_rest_segments_created_at (Zeile 755).
*Rückbau:* Die neue Rückbau-Migration muss hier hinten angehängt werden, mit Begründungskommentar in derselben Machart wie die vorhandenen.
*Risiko:* Fehlt der Eintrag, setzt betrieb_updates.php die Migration auf jeder Neuinstallation erneut an — und läuft dann gegen eine Spalte, die dort schon NOT NULL ist. Der Kopf von migration_lib.php (Zeile 21-24) verlangt den Eintrag ausdrücklich; er ist in der Vergangenheit zweimal vergessen worden (schema.sql:673-676, :686-691).

**`server/schema.sql:73`** — bases.user_id INT UNSIGNED NULL mit Kommentar NULL = zentral (Admin-Eintrag). Die Nullbarkeit IST das zentrale Modell.
*Rückbau:* Spalte auf NOT NULL ziehen, Kommentar streichen. Voraussetzung: keine Zeile mit user_id IS NULL mehr. Ursprung der Nullbarkeit ist migration_lib.php:599.
*Risiko:* ALTER TABLE bricht mit Fehler ab, wenn noch eine zentrale Zeile steht; MySQL kennt kein Zurückrollen von Schemaänderungen. Ohne vorherige Zählung bleibt die Installation auf halbem Weg stehen.

**`server/schema.sql:82`** — UNIQUE KEY uq_user_name (user_id, name) auf bases. Greift bei user_id IS NULL nicht, weil MySQL mehrere NULLs erlaubt.
*Rückbau:* Der Schlüssel bleibt, wird aber mit NOT NULL erstmals wirksam. Die Ersatzprüfung in der Anwendung (db.php:69) entfällt.
*Risiko:* Zwei gleichnamige zentrale Standorte, die der Schlüssel bisher durchließ, lassen sich nach dem NOT NULL nicht demselben Konto zuordnen. Vor der Migration auf Namensdubletten prüfen.

**`server/schema.sql:86-95`** — CREATE TABLE user_bases (user_id, base_id) mit PK (user_id, base_id) und zwei FK ON DELETE CASCADE. Existiert ausschließlich für die Auswahl zentraler Standorte (E16).
*Rückbau:* Tabelle ersatzlos löschen (DROP TABLE user_bases). Kein Ersatz nötig: eigene Standorte gelten immer als ausgewählt.
*Risiko:* Ein DROP TABLE ist über migrationen_inhalt_zaehlen() nicht abzusichern, weil diese Funktion nur Spalten zählt. Der Zeileninhalt muss durch eine eigene skip/run-Prüfung nachgewiesen werden, sonst fällt die Auswahl unbemerkt weg.

**`tools/referenzdatensatz/quelldaten/stammdaten.json`** — Der Referenzdatensatz baut ausschließlich persönliche Stammdaten auf (zwei Standorte, Rettungsmittel je Standort) und legt sie über die regulären Einstellungs-Endpunkte an. Kein zentraler Eintrag, kein user_bases.
*Rückbau:* Nichts zu ändern — der Datensatz ist vom Rückbau nicht betroffen und bleibt danach gültig.
*Risiko:* Umgekehrtes Risiko: Weil das Prüfmittel den zentralen Weg nie berührt hat, ist er auch nie geprüft worden. Der Rückbau kann sich nicht auf eine bestehende Prüfung stützen; die Belege müssen aus Abfragen gegen die Datenbank kommen.

### B — PHP der Weboberfläche

59 Befunde (code 44, daten 1, doku 13, schema 1).

**`server/admin_sicherungsziele.php:182-186`** — Unterzeile der Titelzeile: „Nicht zu verwechseln mit den Transportzielen unter <a href="admin_stammdaten.php">Stammdaten</a> — das sind Zielkliniken.“ — der einzige HTML-Verweis auf die Seite im ganzen PHP-Bestand.
*Rückbau:* Verweis auf einstellungen.php?t=standorte umhängen oder den Halbsatz streichen. Da Zielkliniken künftig nur noch je Konto gepflegt werden, ist der Verweis auf die eigene Standortseite die passende Fassung.
*Risiko:* Sichtbarer Text — Wortliste. Ein toter Verweis in einer Verwaltungsseite fällt erst auf, wenn jemand ihn anklickt.

**`server/admin_stammdaten.php:1-1205 (ganze Datei)`** — Pflegeseite der zentralen Stammdaten. require_admin(); Reiter t=standorte und t=standort&s=<id>; zwölf Schreibwege (base_save/base_del/veh_save/veh_del/crew_save/crew_del/res_save/res_del/bw_save/bw_del/td_save/td_del), alle mit `user_id IS NULL` bzw. `INSERT ... VALUES (NULL,...)`. Bestand: Z.452, 462, 474. Zahl der auswählenden Konten aus user_bases: Z.508-514. Dublettenhinweise über stammdaten_dup_personal_count(): Z.634, 845, 892, 935, 978, 1021. Dialoge/Ortsfelder/Kartenfilter: Z.1100-1205.
*Rückbau:* Datei ersatzlos löschen. Kein Ersatz — Stammdaten werden nur noch je Konto in einstellungen.php gepflegt. Mit ihr entfallen admin_base_id() (Z.41-45), $adBaseNamen, $adZahlen, $anzahlJeBase, $rollenAmStandort, $vehKette.
*Risiko:* Nur den Menüweg zu kappen ist kein Rückbau — die Seite hat seit Web 15.3.0 (E-S8-14) ohnehin keinen Menüpunkt und ist allein über ihre Adresse erreichbar. Bleiben Verweise stehen (admin_sicherungsziele.php:185, sd_seite() in stammdaten_ui.php:92), zeigen sie ins Leere.

**`server/admin_stammdaten.php:41-45 (admin_base_id)`** — `SELECT id FROM bases WHERE id = ? AND user_id IS NULL` — prüft, dass eine Kennung existiert UND zentral ist. Aufgerufen Z.76, 92, 153.
*Rückbau:* Ersatzlos mit der Datei. Das Gegenstück dt_base_erlaubt() bleibt (ohne den user_bases-Zweig).
*Risiko:* Wird die Funktion vor der Seite entfernt, fallen drei Schreibwege auf `null` zurück und löschen/ändern nichts, ohne es zu melden.

**`server/api/day.php:220-228`** — `SELECT DISTINCT role_code, name FROM crew_presets WHERE base_id = ? AND (user_id = ? OR user_id IS NULL) ORDER BY name` — die Besatzungs-Vorbelegungen in der Tagesantwort.
*Rückbau:* Auf `user_id = ?` verengen.
*Risiko:* Diese Antwort ist eine Schnittstelle. Der Inhalt ändert sich nach dem Rückbau nicht (die zentralen Zeilen sind fort), die Form auch nicht — docs/JSON-Vertrag.md braucht daher voraussichtlich keine Änderung, ist aber zu prüfen.

**`server/api/day.php:99-107 (Kommentar) und server/api/import_commit.php:124-129 (Kommentar)`** — Beide erklären, warum die Prüfung in dt_zuordnen() steckt: „sonst wird ein zentraler Eintrag beim Speichern stillschweigend auf NULL zurückgesetzt“ bzw. „weil ein zentrales Rettungsmittel erst zur Verfügung steht, wenn die NutzerIn seinen Standort ausgewählt hat (E16)“.
*Rückbau:* Begründung umschreiben: Die Regel „eine Prüfung, deckungsgleich mit der Liste“ bleibt richtig und wichtig — nur ihr Grund ist nicht mehr die Zentralität, sondern die Fremdzugriffssperre.
*Risiko:* Ersatzloses Streichen nähme die Warnung mit, die die Deckungsgleichheit von dt_bases() und dt_base_erlaubt() sichert. Der Kommentar wird gekürzt, nicht gelöscht.

**`server/api/mission.php:20 (Kommentar)`** — „Zusatzfelder generisch aus der zentralen Definition (mission_fields.php).“
*Rückbau:* Nichts. Falscher Treffer wie mission_fields_lib.php:4.
*Risiko:* Keines.

**`server/backup_lib.php:1035-1038 und 1103-1106`** — `if (stammdaten_dup_global('bases','name',$name)) { $stats['stammdaten_skipped']++; continue; }` und dasselbe für vehicles — beim Einspielen wird übersprungen, was zentral schon existiert.
*Rückbau:* Beide Zeilen streichen; die Einträge werden dann regulär über die UNIQUE-Schlüssel abgefangen (INSERT IGNORE, Z.1039 und 1108).
*Risiko:* stammdaten_skipped verliert damit einen seiner Gründe. Der Berichtstext in einstellungen.php:3238 nennt genau diesen Grund und muss mit — sonst behauptet der Bericht „bereits systemweit vorhanden“ für Einträge, die aus einem anderen Grund übersprungen wurden.

**`server/backup_lib.php:1047-1056 (baseIdByName)`** — `SELECT id FROM bases WHERE name = ? AND (user_id = ? OR user_id IS NULL) ORDER BY user_id IS NULL LIMIT 1` — löst den portablen Schlüssel Name auf und bevorzugt bei Gleichstand den EIGENEN Standort.
*Rückbau:* Auf `WHERE name = ? AND user_id = ?` verengen; die Sortierung entfällt damit ersatzlos.
*Risiko:* Der Kommentar Z.1044-1047 begründet die Namensauflösung mit „ein zentraler heisst in beiden Installationen gleich“ — nach dem Rückbau ist der Name nur noch INNERHALB eines Kontos eindeutig. Das ist weiterhin genug, aber die Begründung stimmt nicht mehr und muss neu geschrieben werden.

**`server/backup_lib.php:1058-1066`** — Einspielen: `foreach (($sd['user_bases'] ?? []) as $bn) { … INSERT IGNORE INTO user_bases (user_id, base_id) VALUES (?,?) }` mit Kommentar „Ohne das verschwaenden sie nach dem Einspielen aus den Auswahllisten“.
*Rückbau:* Block ersatzlos streichen. Ein alter Schlüssel `user_bases` in einer Datei wird damit stillschweigend übergangen — das ist richtig so, gehört aber in docs/Backup-Format.md als „wird ignoriert“ vermerkt.
*Risiko:* Bleibt der Block stehen, während die Tabelle fällt, scheitert jedes Einspielen einer alten Datei mit einem Datenbankfehler mitten in der Transaktion.

**`server/backup_lib.php:1434-1440`** — `SELECT id FROM vehicles WHERE name = ? AND (user_id = ? OR user_id IS NULL) ORDER BY user_id IS NULL LIMIT 1` — dieselbe Auflösung für vehicle_ref beim Wiederherstellen der Diensttage.
*Rückbau:* Auf `user_id = ?` verengen, Sortierung streichen.
*Risiko:* Vierte Stelle in derselben Datei. Wird sie übersehen, sucht das Einspielen weiter nach Rettungsmitteln, die niemandem gehören — nach dem Rückbau eine leere Menge, also stillschweigend `null` und ein Diensttag ohne Rettungsmittel.

**`server/backup_lib.php:617-624 und 731`** — Export: `SELECT b.name FROM user_bases ub JOIN bases b ON b.id = ub.base_id WHERE ub.user_id = ? AND b.user_id IS NULL ORDER BY b.name` und der Nutzlast-Schlüssel `'user_bases' => [...]` im Block `stammdaten`.
*Rückbau:* Abfrage und Schlüssel aus der Nutzlast entfernen. Das ist eine SCHNITTSTELLENÄNDERUNG: docs/Backup-Format.md muss im selben Paket nachgezogen werden, und die Nutzlastversion ist zu prüfen.
*Risiko:* Der Schlüssel steckt auch in server/demo/fixture.json.gz (dort als leere Liste, geprüft). Alte Sicherungsdateien führen ihn weiter — das Einspielen muss ihn IGNORIEREN können, nicht daran scheitern.

**`server/db.php:110-130 und 155-182 (stammdaten_ohne_standortpflicht, stammdaten_standort_loesen)`** — Beide nehmen `?int $userId`; `$userId === null` heißt ausdrücklich „der systemweite Bestand“ (`user_id IS NULL` statt `= ?`). Der Kopfkommentar Z.117-120 benennt die zwei Aufrufstellen einstellungen.php und admin_stammdaten.php.
*Rückbau:* Parameter auf `int $userId` verengen, den NULL-Zweig im SQL streichen, Kommentar Z.117-120 auf eine Aufrufstelle kürzen.
*Risiko:* Bleibt der nullbare Parameter stehen, ist ein Aufruf mit null weiter möglich und liefert dann Rettungsmittel, die niemandem gehören — nach dem Rückbau eine leere Menge, aber eine, die keine Fehlermeldung erzeugt.

**`server/db.php:184-230 (stammdaten_loeschfrage)`** — Parameter `bool $systemweit` steuert vier ausgeschriebene Beugungsformen („Ein systemweiter Stammdatensatz“, „ systemweite Stammdatensätze“, „systemweiten“) und das Wort „systemweit“ im Einleitungssatz (Z.207-210). Aufrufer: einstellungen.php:1962 (false), admin_stammdaten.php (true).
*Rückbau:* Parameter `$systemweit` und `$zusatz` entfernen, die drei systemweiten Textvarianten streichen, Satz auf die eigene Fassung verengen.
*Risiko:* Der Kommentar warnt ausdrücklich davor, deutsche Adjektivendungen zu rechnen — beim Kürzen nicht wieder zusammensetzen, sondern die verbleibenden Formen ausgeschrieben lassen.

**`server/db.php:58-79 (stammdaten_dup_global)`** — `SELECT COUNT(*) FROM $table WHERE user_id IS NULL AND LOWER($col)=LOWER(?)`. Aufrufe: admin_stammdaten.php:117,212,280,309,334,360; einstellungen.php sperrend 622,782,843,886,911,936; einstellungen.php als Anzeigehinweis 1689,2169,2216,2266,2313; backup_lib.php:1037,1105.
*Rückbau:* Funktion ersatzlos entfernen. In einstellungen.php entfallen sechs `elseif`-Zweige samt Meldung „… ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.“ — danach greift der jeweilige UNIQUE-Schlüssel (uq_user_base_name usw.), der bei eigenen Einträgen ohnehin schon zuständig ist.
*Risiko:* Ohne die Sperre kann eine NutzerIn Namen anlegen, die vorher abgelehnt wurden — das ist erwünscht, muss aber im CHANGELOG stehen. Bleibt die Funktion stehen, während die Tabellen keine NULL-Zeilen mehr führen, liefert sie dauerhaft false und ist toter Code.

**`server/db.php:81-92 (stammdaten_dup_personal_count)`** — `SELECT COUNT(*) ... WHERE user_id IS NOT NULL ...` — zählt persönliche Einträge gleichen Namens, ausschließlich für den Admin-Hinweis „N Konten führen einen gleichnamigen eigenen Eintrag“ und die Plakette „Namensdublette“ in admin_stammdaten.php.
*Rückbau:* Ersatzlos entfernen — sie hat nach dem Rückbau keinen einzigen Aufrufer mehr.
*Risiko:* Wird sie übersehen, bleibt eine Funktion mit der Aufschrift „Nutzer-Ansicht bzw. Admin-Hinweis“ ohne Aufrufer stehen und suggeriert beim nächsten Umbau, es gebe noch eine Verwaltungsansicht.

**`server/demo_lib.php:407-412`** — Die Löschliste des Demo-Resets führt `'user_bases'` zwischen `'vehicles'` und `'user_defaults'`.
*Rückbau:* Eintrag streichen, sobald die Tabelle fällt.
*Risiko:* Bleibt er stehen, scheitert jeder Demo-Reset (alle 30 Minuten, unbeaufsichtigt) mit einem SQL-Fehler auf einer nicht vorhandenen Tabelle. Die Demo-Fixture selbst führt `stammdaten.user_bases` als leere Liste — geprüft; nach dem Rückbau des Backup-Formats ist der Schlüssel dort ebenfalls auszutragen.

**`server/diensttag_lib.php:362-381 (dt_base_erlaubt)`** — `LEFT JOIN user_bases ub ... WHERE b.id = ? AND (b.user_id = ? OR (b.user_id IS NULL AND ub.base_id IS NOT NULL))`. Die eine Prüfschicht für Standortkennungen; Aufrufer: dt_zuordnen (Z.530), einstellungen.php:61,561,642, nachbearbeitung.php:86.
*Rückbau:* Auf `WHERE b.id = ? AND b.user_id = ?` verengen, JOIN und zweiten Zweig streichen. Die Funktion selbst bleibt — sie ist weiterhin die Stelle, an der Fremdzugriff abgewehrt wird.
*Risiko:* Diese Prüfung MUSS deckungsgleich mit dt_bases() bleiben (Kommentar Z.363-366). Wird nur eine der beiden verengt, wird eine Zuordnung beim Speichern still auf NULL zurückgesetzt — der Diensttag sieht danach neutral aus, ohne Meldung.

**`server/diensttag_lib.php:383-409 (dt_vehicle_erlaubt)`** — `LEFT JOIN user_bases ub ON ub.base_id = v.base_id ... (v.user_id = ? OR (v.user_id IS NULL AND (ub.base_id IS NOT NULL OR v.base_id IS NULL)))`.
*Rückbau:* Auf `WHERE v.id = ? AND v.user_id = ?` verengen. Achtung: der Zweig `v.base_id IS NULL` gehört NICHT zum zentralen Modell, sondern zu E-S9-09 (Rettungsmittel ohne Standortpflicht) — er entfällt hier nur, weil er im verbliebenen `v.user_id = ?`-Fall gar keine Bedingung mehr ist.
*Risiko:* Wer beim Kürzen auch die Deckungsgleichheit mit dt_vehicles() verliert, erzeugt genau den stillen Datenverlust, den version.php:3117-3119 beschreibt.

**`server/diensttag_lib.php:410-430 (dt_bases)`** — `SELECT b.id, b.name, b.lat, b.lon, b.user_id IS NULL AS zentral ... LEFT JOIN user_bases ... WHERE b.user_id = ? OR (b.user_id IS NULL AND ub.base_id IS NOT NULL)`. Liefert die Spalte `zentral`, die fünf Seiten auswerten.
*Rückbau:* Auf `WHERE b.user_id = ?` verengen; JOIN und die berechnete Spalte `zentral` streichen. Damit fällt der Rückgabeschlüssel `zentral` weg — alle Verbraucher müssen im selben Paket mit.
*Risiko:* Bleibt `zentral` im SELECT stehen (immer 0), sehen die Verbraucher weiterhin ein Feld, das nichts mehr bedeutet; wird es entfernt, ohne die Verbraucher zu ändern, greifen index.php:269, diensttag_neu.php:84, import.php:93, einstellungen.php:1933/2038/2047 auf einen fehlenden Schlüssel zu (empty() fängt es, `!empty()` still — kein Fehler, nur ein verschwundener Hinweis).

**`server/diensttag_lib.php:431-452 (dt_vehicles)`** — `LEFT JOIN user_bases ub ... WHERE v.user_id = ? OR (v.user_id IS NULL AND (ub.base_id IS NOT NULL OR v.base_id IS NULL))`.
*Rückbau:* Auf `WHERE v.user_id = ?` verengen; JOIN streichen. Der Kommentar Z.433-438 zur Aufnahme standortloser Rettungsmittel bleibt gültig und darf nicht mit weggekürzt werden.
*Risiko:* Muss im selben Schnitt wie dt_vehicle_erlaubt() geschehen (siehe dort).

**`server/diensttag_neu.php:30 und 83-85`** — $SD_BASES = dt_bases($userId); `$baseOpt[…] = (string)$b['name'] . (!empty($b['zentral']) ? ' (zentral)' : '');`
*Rückbau:* Suffix streichen.
*Risiko:* Sichtbarer Text — Wortliste. Zweite von drei gleichartigen Stellen (index.php, diensttag_neu.php, import.php); alle drei im selben Paket.

**`server/einsatz_form.php:66-72`** — `SELECT DISTINCT name FROM resources WHERE base_id = ? AND (user_id = ? OR user_id IS NULL) ORDER BY name` — Vorschlagsliste „Andere Rettungsmittel“.
*Rückbau:* Auf `user_id = ?` verengen; das zweite `?`-Argument bleibt.
*Risiko:* Eine von fünf gleichartigen Stellen in dieser Datei. Der Rückbau ist inhaltlich folgenlos (die zentralen Zeilen sind gelöscht), aber jede übersehene Stelle behält eine Bedingung, die es nicht mehr gibt.

**`server/einsatz_form.php:766-770 und 777-781 ($optSrc)`** — `bw_units` und `crew:<rolle>` als OPTIONSQUELLEN: je `WHERE base_id = ? AND (user_id = ? OR user_id IS NULL)`.
*Rückbau:* Beide auf `user_id = ?` verengen. Kommentar Z.759-761 („persoenliche UND zentrale“) mit.
*Risiko:* Bei options_src IST die Liste die Auswahl (Kommentar Z.783-786) — hier ist eine übersehene Änderung folgenreicher als bei suggest_src, weil ein Wert außerhalb der Liste nicht speicherbar ist.

**`server/einsatz_form.php:824-828 und 836-840 ($suggestSrc)`** — `transport_dests` und `crew:<rolle>` als VORSCHLAGSQUELLEN: je `WHERE base_id = ? AND (user_id = ? OR user_id IS NULL)`; bei transport_dests zusätzlich `GROUP BY name` mit `MAX(lat)/MAX(lon)`.
*Rückbau:* Auf `user_id = ?` verengen. Das GROUP BY/MAX war unter anderem dafür da, gleichnamige eigene und zentrale Zielkliniken zu einer Zeile zu verschmelzen — nach dem Rückbau prüfen, ob es noch gebraucht wird (uq_user_base_name lässt denselben Namen an zwei Standorten zu, und dayBaseId ist genau einer).
*Risiko:* Wer das MAX() gedankenlos mit entfernt, ändert das Verhalten bei mehreren Zeilen gleichen Namens — das ist eine eigene Entscheidung, keine Nebenwirkung.

**`server/einstellungen.php:1486-1494`** — `SELECT b.id, b.name, b.lat, b.lon, ub.base_id IS NOT NULL AS gewaehlt FROM bases b LEFT JOIN user_bases ub ... WHERE b.user_id IS NULL ORDER BY b.name` — füllt $zentral, die Datengrundlage der Karte „Vordefinierte Standorte“.
*Rückbau:* Abfrage und Variable $zentral ersatzlos entfernen.
*Risiko:* $zentral wird an drei Stellen weiterverwendet (1779, 1786, 1789); ein Rückbau nur der Abfrage bricht die Seite mit einem undefinierten Wert.

**`server/einstellungen.php:1512-1516 ($sdLade) und 1529-1532 ($sdVehOhne)`** — `WHERE (user_id = ? OR user_id IS NULL) AND base_id IN ({IDS})` bzw. `WHERE base_id IS NULL AND (user_id = <uid> OR user_id IS NULL)` — laden persönliche UND zentrale Einträge der sechs Listen.
*Rückbau:* Auf `user_id = ?` bzw. `user_id = <uid>` verengen; die Spalte `user_id` im SELECT wird damit überflüssig (sie dient nur $istZentral).
*Risiko:* Wird nur $istZentral entfernt und die Abfrage nicht verengt, stehen zentrale Einträge weiter in den Listen — dann aber ohne Kennzeichnung und mit Bearbeiten- und Löschen-Knöpfen, die an `AND user_id = ?` scheitern und nichts tun.

**`server/einstellungen.php:1633-1634 ($istZentral) mit Aufrufern 1872, 2110, 2176, 2225, 2273, 2320`** — `$istZentral = static fn(array $z): bool => $z['user_id'] === null;` — wird als Option `'zentral' => $istZentral($v)` an sd_zeile() gereicht und entscheidet dort, ob eine Zeile Knöpfe oder die Plakette „systemweit“ bekommt.
*Rückbau:* Schließung und alle sechs `'zentral' => …`-Optionen streichen.
*Risiko:* Bleibt eine der sechs Übergaben stehen, während sd_zeile() den Schlüssel nicht mehr kennt, wird sie stillschweigend ignoriert — kein Fehler, aber ein Rest, der beim nächsten Lesen erneut Fragen aufwirft.

**`server/einstellungen.php:1689, 1705, 1719`** — Dublettenhinweis in der Karte „Eigene Standorte“: `$dup = stammdaten_dup_global('bases','name',$b['name'])`, Kleinzeile „identisch mit einem systemweiten Eintrag“ (zweimal ausgegeben, Z.1705 wird von Z.1719 überschrieben).
*Rückbau:* $dup, beide Textstellen und die tote Zuweisung in Z.1705 streichen.
*Risiko:* Der Hinweis ist eine Anzeige ohne Sperre; er verschwindet folgenlos. Z.1705 fällt beim Lesen als toter Code auf — beim Rückbau mitnehmen, statt ihn als Fund für später stehenzulassen.

**`server/einstellungen.php:1774-1826 (Karte „Vordefinierte Standorte“)`** — ui_karte_start(['titel' => 'Vordefinierte Standorte', 'id' => 'zentrale', 'zu' => true, 'zahl' => count($zentral).' · '.$gewaehlt.' ausgewählt']) samt Hinweistext „Vordefinierte Standorte legt eine Administratorin an…“, den Formularen f-zsel-<id> (ub_toggle) und f-zdef-<id> (base_default für zentrale Standorte), den Aktionen „Auswählen“/„Abwählen“ und der Plakette ui_plakette('systemweit') je Zeile.
*Rückbau:* Karte ersatzlos entfernen (Z.1774 bis ui_karte_ende(true) in Z.1827). Es tritt nichts an ihre Stelle; die Karte „Eigene Standorte“ darüber bleibt die einzige Standortliste. Der Sonderfall „★ auch für systemweite Standorte“ (Kommentar Z.1792-1795) entfällt mit — die Vorbelegung wird dann nur noch auf der Standortseite gesetzt.
*Risiko:* Diese Karte ist der einzige Ort, an dem eine NutzerIn heute ohne eigenen Standort überhaupt arbeitsfähig wird. Ihr Wegfall ist eine spürbar veränderte Wegführung — nach Abschnitt 2 der Arbeitsanweisung eine HAUPT-Nummer, keine Neben-Nummer.

**`server/einstellungen.php:1832`** — Meldungstext „Noch kein Standort verfügbar. Lege oben einen eigenen an oder wähle einen vordefinierten aus — ohne Standort gibt es keine Rettungsmittel…“
*Rückbau:* Halbsatz „oder wähle einen vordefinierten aus“ streichen.
*Risiko:* Bleibt er stehen, verweist die einzige Hilfestellung des leeren Zustands auf eine Karte, die es nicht mehr gibt. Textänderung — die Wortliste (tools/wortliste/) muss danach laufen.

**`server/einstellungen.php:1929-1970 (Kopf der Standortseite)`** — `$sZentral = (bool)($seiteB['zentral'] ?? false);` (Z.1933) und `'aktionen' => $sZentral ? ui_plakette('systemweit') : ui_zeilenaktionen([...])` (Z.1969) — ein zentraler Standort zeigt statt des Aktionsmenüs eine Plakette.
*Rückbau:* $sZentral streichen, das Aktionsmenü bedingungslos setzen. Der Erklärtext Z.1974-1978 („die Plakette ‚systemweit‘ sagt, warum eine Zeile keine Knöpfe hat“) wird damit falsch und muss mit.
*Risiko:* Bleibt der Zweig stehen, während dt_bases() `zentral` nicht mehr liefert, greift `?? false` — die Seite funktioniert, aber die Verzweigung ist tot.

**`server/einstellungen.php:2036-2049 (Karte „Standort“)`** — `<?php if (!empty($b['zentral'])): ?><p class="feld-hinweis">[systemweit] Dieser Standort wird von der Verwaltung gepflegt.</p>` und `'aktionen' => empty($b['zentral']) ? ui_knopf(['text' => 'Bearbeiten', …]) : ''`.
*Rückbau:* Hinweisabsatz streichen, Bearbeiten-Knopf bedingungslos setzen.
*Risiko:* Textänderung — Wortliste läuft danach. Der Absatz ist zugleich der einzige Ort im Konto, der den Sonderfall erklärt; wird nur er entfernt und die Bedingung am Knopf bleibt, gibt es eine Zeile ohne Knopf ohne Begründung.

**`server/einstellungen.php:2169-2172, 2216-2219, 2266-2269, 2313-2316`** — Dieselbe Anzeige für Besatzung, Zielkliniken, weitere Rettungsmittel und Bergwacht: `$dup = !$xz && stammdaten_dup_global(...)`, Kleinzeile „identisch mit einem systemweiten Eintrag“.
*Rückbau:* Vier Blöcke ersatzlos streichen; die Kleinzeilen fallen auf ihren übrigen Inhalt zurück.
*Risiko:* Gering, aber vier gleichartige Stellen — wer drei erwischt, hinterlässt einen Hinweis, der nie mehr wahr werden kann.

**`server/einstellungen.php:3238`** — JavaScript-Text des Einspielberichts: `(s.stammdaten_skipped ? ` (${s.stammdaten_skipped} übersprungen, bereits systemweit vorhanden)` : '')`.
*Rückbau:* Entfällt zusammen mit dem Zähler stammdaten_skipped in backup_lib.php, soweit er dort nur wegen stammdaten_dup_global() hochgezählt wurde. Achtung: derselbe Zähler wird auch von pruef_rettungsmittel() bedient (backup_lib.php:1102) — der Text braucht dann eine Fassung ohne „systemweit“, nicht die Streichung.
*Risiko:* Blindes Streichen nimmt dem Bericht die einzige Auskunft über übersprungene Rettungsmittel aus fehlgeschlagener Prüfung. Sichtbarer Text — Wortliste.

**`server/einstellungen.php:553-558, 1451-1453, 1465-1468, 1481-1483, 1929-1931 (Kommentare)`** — Fünf Kommentarblöcke erklären das Modell: „Zulässig sind die eigenen und die AUSGEWAEHLTEN zentralen (E16)“, „die Auswahl der zentralen“, „ZENTRALE EINTRÄGE bleiben sichtbar und unveränderlich … tragen hier das Kennzeichen ‚systemweit‘“.
*Rückbau:* Im selben Paket nachziehen, nicht später. Die Aussage „dieselbe Menge, die dt_base_erlaubt() beim Speichern durchlässt“ bleibt richtig und soll erhalten bleiben — nur die Zentralität fällt heraus.
*Risiko:* Kommentare, die ein abgeschafftes Modell erklären, sind schlimmer als keine: Die nächste Instanz baut danach.

**`server/einstellungen.php:659-679 (action ub_toggle)`** — Der Schreibweg der Auswahl: prüft `SELECT COUNT(*) FROM bases WHERE id = ? AND user_id IS NULL` (Z.664) und schreibt/löscht `user_bases` (Z.668, 672) mit den Meldungen „Zentraler Standort ausgewählt/abgewählt.“
*Rückbau:* Ganzen Block ersatzlos streichen. Damit entfällt die einzige Schreibstelle auf user_bases in der laufenden Anwendung.
*Risiko:* Bleibt der Block stehen, während die Karte verschwindet, ist ein POST mit action=ub_toggle weiter gültig und schreibt in eine Tabelle, die niemand mehr liest.

**`server/einstellungen.php:989 (Umleitungstabelle $abschnitt)`** — `'ub_toggle' => 'zentrale'` — der Anker, auf den nach dem Aus-/Abwählen zurückgesprungen wird.
*Rückbau:* Zeile streichen; der Anker `#zentrale` verschwindet mit der Karte.
*Risiko:* Bleibt der Eintrag stehen, leitet ein verwaister POST auf einen Anker, den es nicht gibt — ohne Fehler, ohne sichtbare Wirkung.

**`server/import.php:25-30 und 93`** — Kommentar „eigene und ausgewaehlte zentrale Standorte samt ihren Rettungsmitteln (E16)“; im Auswahlfeld `echo !empty($b['zentral']) ? ' (systemweit)' : '';` — dieselbe Sache wie index.php, aber mit ANDEREM Wort.
*Rückbau:* Suffix und Kommentar streichen.
*Risiko:* Die drei Stellen sagen heute schon zweierlei („(zentral)“ vs. „(systemweit)“). Wer nur zwei davon findet, hinterlässt genau die Uneinheitlichkeit, die den Rest übersehen ließ. Ferner: Kommentarzeile 9 verweist auf admin_stammdaten.php als Muster — mit weg.

**`server/index.php:13-19 und 265-269`** — $SD_BASES = dt_bases($userId) mit Kommentar über die „AUSGEWAEHLTEN zentralen Standorte (E16)“; im Auswahlfeld `echo !empty($b['zentral']) ? ' (zentral)' : '';` samt Erklärkommentar Z.265-268.
*Rückbau:* Kennzeichnung und Kommentar streichen; dt_bases() bleibt der Lieferant, nur ohne zweite Herkunft.
*Risiko:* Sichtbarer Text „(zentral)“ — Wortliste. Bleibt er, während dt_bases() `zentral` nicht mehr liefert, verschwindet er still; das ist kein Fehler, aber auch kein Rückbau.

**`server/kopplung_lib.php:18, 141, 210, 220`** — `UPDATE ... WHERE code = ? AND user_id IS NULL` auf pair_sessions — der Kopplungscode ist unbeansprucht, solange user_id NULL ist.
*Rückbau:* Nichts. Falscher Treffer: gleiche SQL-Wendung, anderes Modell (Gerätekopplung, nicht Stammdaten).
*Risiko:* Diese vier Stellen sind der Schiedsrichter der Kopplung (Z.210: „`user_id IS NULL` steht in der BEDINGUNG, nicht in einer vorherigen Abfrage“). Eine Änderung hier gäbe einen Kopplungscode zweimal aus.

**`server/migration_lib.php:1181-1189`** — `CREATE TABLE IF NOT EXISTS user_bases (…)` in der Migration `2026_07_26_zentrale_stammdaten` (Kennung Z.589, Beschriftung Z.591).
*Rückbau:* NICHT ändern. Migrationen sind Geschichte; eine rückwirkend geänderte Migration führt bei jeder Installation, die sie schon gelaufen ist, zu einem anderen Zustand als bei einer neuen. Der Rückbau braucht eine NEUE Migration, die user_bases wirft und die Zeilen mit `user_id IS NULL` in den sechs Tabellen behandelt.
*Risiko:* Die neue Migration muss ausdrücklich entscheiden, was mit verbliebenen zentralen Zeilen geschieht (löschen — der Auftraggeber hat die Standorte am 09.09.2026 bereits gelöscht, die vier anderen Tabellen hängen per ON DELETE CASCADE daran, standortlose zentrale Rettungsmittel jedoch NICHT). Und: Nach einem Deploy mit Schemaänderung muss eine Administratorin update.php aufrufen — das ist beim Vorschlagen mit anzusagen.

**`server/migration_lib.php:1435-1440 und 1494 (Kommentare)`** — „fuer zentrale (user_id IS NULL) die zentralen“ und `$einzel = []; // user_id (oder 0 fuer zentral) => base_id`.
*Rückbau:* Stehen lassen — sie beschreiben, was die Migration damals getan hat, und das bleibt wahr.
*Risiko:* Wer beim Austragen der Dokumentation die Migrationskommentare mitnimmt, macht die Geschichte unlesbar. Die Grenze verläuft zwischen laufendem Code (wird zurückgebaut) und Migration (bleibt).

**`server/migration_lib.php:1528-1535`** — `INSERT IGNORE INTO user_bases (user_id, base_id) SELECT DISTINCT d.user_id, d.base_id FROM days d JOIN bases b ON b.id = d.base_id WHERE d.base_id IS NOT NULL AND b.user_id IS NULL` — wählt beim Umstieg benutzte zentrale Standorte aus.
*Rückbau:* NICHT ändern (siehe vorigen Punkt). Die neue Migration setzt danach.
*Risiko:* Reihenfolge: Fällt user_bases, bevor diese Migration bei einer alten Installation gelaufen ist, bricht sie ab. `CREATE TABLE IF NOT EXISTS` bleibt deshalb stehen und die neue Migration räumt danach auf — nicht umgekehrt.

**`server/mission_fields.php:35-38 und 195-197 (Kommentare zum Feldkatalog)`** — Zweimal „Beide liefern persoenliche UND zentrale Stammdaten DES STANDORTS, der am Diensttag hinterlegt ist (E15) — es gibt keine standortuebergreifenden Stammdaten mehr.“
*Rückbau:* „persoenliche UND zentrale“ auf „persoenliche“ kürzen. Der Rest des Satzes (E15, keine standortübergreifende Ebene) bleibt und ist weiterhin die tragende Regel.
*Risiko:* mission_fields.php ist die eine Beschreibung, aus der Formular, Speichern, API und Anzeige nachziehen (CLAUDE.md 4). Ein Kommentar dort, der eine abgeschaffte Quelle nennt, wird beim nächsten Feld als Vorlage gelesen.

**`server/mission_fields_lib.php:4 (Kommentar)`** — „Abgeleitete Sichten auf den zentralen Feldkatalog (mission_fields.php).“
*Rückbau:* Nichts. Falscher Treffer: „zentral“ meint hier den EINEN Feldkatalog, nicht das Stammdatenmodell.
*Risiko:* Wer die Wortsuche ohne Lesen abarbeitet, ändert hier eine Stelle, die richtig ist.

**`server/nachbearbeitung.php:139-152`** — $zentraleBases (`SELECT id, name FROM bases WHERE user_id IS NULL`, nur für Admins, Z.141-146), $offeneSdZ (Z.137, nb_offene_stammdaten(..., true)), $sdOffenZentral (Z.152) und $nichtsOffen (Z.147), das $offeneSdZ mitprüft.
*Rückbau:* Alle vier Variablen auf die eigene Fassung verengen bzw. streichen; $nichtsOffen = !$offeneTage && !$offeneSd.
*Risiko:* Bleibt $offeneSdZ gefüllt, steht die Seite dauerhaft auf „es ist etwas offen“, obwohl nichts mehr zuzuordnen ist — genau der Hinweis, den man nicht loswird, vor dem nachbearbeitung_lib.php:160-163 warnt.

**`server/nachbearbeitung.php:206-210`** — Meldungstext „Es stehen keine Standorte und Rettungsmittel zur Verfügung. Bitte zuerst welche anlegen oder einen vordefinierten Standort auswählen.“
*Rückbau:* Halbsatz „oder einen vordefinierten Standort auswählen“ streichen.
*Risiko:* Sichtbarer Text — Wortliste.

**`server/nachbearbeitung.php:285-296 und 308-312, 328`** — Der zweite Kartenblock: `$blocks[] = ['Zentrale Einträge ohne Standort', $offeneSdZ, true, $zentraleBases, $sdOffenZentral];` für Admins, samt Kommentar Z.286-289, dem Warnhinweis „bitte zuerst unter ‚Standorte systemweit‘ einen anlegen“ (Z.310-311) und dem versteckten Feld `<input type="hidden" name="zentral" …>` (Z.328).
*Rückbau:* $blocks auf den einen eigenen Block reduzieren; die Schleife über $blocks kann damit einer geraden Ausgabe weichen. Warnhinweis auf die eigene Fassung kürzen, verstecktes Feld entfernen.
*Risiko:* Sichtbarer Text mit dem Namen einer gelöschten Seite („Standorte systemweit“) — Wortliste und Handbuch. Wer nur den Block entfernt, aber $istZentral im Anker (Z.317) stehen lässt, erzeugt Anker mit dem Präfix ‚e-‘ ohne Gegenstück, was folgenlos, aber verwirrend ist.

**`server/nachbearbeitung.php:62-101 (action sd_zuordnen)`** — `$zentral = ($_POST['zentral'] ?? '') === '1'` (Z.66); Admin-Sperre mit Meldung „Systemweite Standorte lassen sich nur von einer Administratorin zuordnen.“ (Z.71-74); Standortprüfung im zentralen Zweig über `SELECT id FROM bases WHERE id = ? AND user_id IS NULL` (Z.82); Schreibbedingung `$wo = $zentral ? 'user_id IS NULL' : 'user_id = ?'` (Z.93-96).
*Rückbau:* Feld `zentral` aus POST und Formular entfernen, Admin-Zweig und Meldung streichen, Prüfung fest auf dt_base_erlaubt() (Z.86), Schreibbedingung fest auf `user_id = ?`.
*Risiko:* Wird das versteckte Feld im Markup (Z.328) stehengelassen, während der Handler es nicht mehr liest, ist das folgenlos; umgekehrt akzeptiert der Handler weiter `zentral=1` aus einem manipulierten POST und schreibt an Zeilen, die niemandem gehören.

**`server/nachbearbeitung_lib.php:120-155 (nb_offene_stammdaten)`** — Parameter `bool $zentral = false`; `$wo = $zentral ? 'user_id IS NULL' : 'user_id = ?'` (Z.138), `$q->execute($zentral ? [] : [$userId])` (Z.149); Kopfkommentar Z.124-128 („Solange EIN zentraler Eintrag offen ist, kann die NOT-NULL-Bedingung nicht gezogen werden“).
*Rückbau:* Parameter entfernen, Bedingung fest auf `user_id = ?`, Kommentar kürzen.
*Risiko:* Der zitierte Satz ist der Grund, warum die zweite Stufe (NOT NULL) hängen bleiben kann. Nach dem Rückbau bleiben zentrale Waisen-Zeilen mit `user_id IS NULL` in den fünf Tabellen möglicherweise stehen — sie werden dann von NICHTS mehr gefunden, blockieren aber nb_stammdaten_offen_gesamt() (Z.235-245, das ohne user_id-Bedingung zählt) und damit nb_notnull_ziehen(). Das ist der gefährlichste Punkt dieser Bestandsaufnahme.

**`server/nachbearbeitung_lib.php:157-175 (nb_offen_gesamt)`** — `if (ist_admin()) { foreach (nb_offene_stammdaten($userId, true) as $zeilen) { $n += count($zeilen); } }` (Z.170-172) samt Begründung Z.160-163. Das Ergebnis steuert den Leisteneintrag „Zuordnung offen“ (ui.php:715, 722-729).
*Rückbau:* Admin-Zweig ersatzlos streichen; die Zahl zählt danach nur noch eigene Punkte.
*Risiko:* Bleibt er, zeigt die Leiste jeder Administratorin dauerhaft eine Zahl für eine Aufgabe, deren Bearbeitungsseite gelöscht ist.

**`server/nachbearbeitung_lib.php:235-245 (nb_stammdaten_offen_gesamt)`** — `SELECT COUNT(*) FROM $tabelle WHERE base_id IS NULL` — OHNE user_id-Bedingung, zählt also eigene und zentrale Waisen zusammen. Grundlage für den Knopf der zweiten Stufe (action=notnull).
*Rückbau:* Bleibt inhaltlich richtig, ist aber der Ort, an dem ein zurückgelassener zentraler Datenrest sichtbar wird. Im Rückbaupaket ist zu entscheiden, ob verbliebene Zeilen mit `user_id IS NULL` per Migration gelöscht oder einem Konto zugeschlagen werden.
*Risiko:* Wenn nach dem Rückbau noch eine einzige Zeile mit `user_id IS NULL` und `base_id IS NULL` in crew_presets/bw_units/resources/transport_dests steht, meldet diese Funktion dauerhaft offene Punkte, die über keine Oberfläche mehr erreichbar sind. Die NOT-NULL-Stufe ist dann nicht mehr zu ziehen.

**`server/papierkorb.php:108-112 (Kommentar)`** — „Meldung aus der Sitzung abholen — dieselbe Mechanik wie in admin_stammdaten.php und einstellungen.php.“
*Rückbau:* Verweis auf admin_stammdaten.php streichen; einstellungen.php bleibt als Beleg.
*Risiko:* Reiner Kommentar, folgenlos — aber ein Verweis auf eine gelöschte Datei ist genau die Sorte Rest, die die Suche beim nächsten Mal übersieht.

**`server/stammdaten_ui.php:31-32, 110, 112-114, 126, 148`** — Der Baustein der Stammdatenzeile: Option `zentral` (dokumentiert Z.31), `$zentral = !empty($o['zentral'])` (Z.110), `if (!$zentral) { … Bearbeiten/Löschen … }` (Z.126) und `if ($zentral) { $plaketten .= ui_plakette('systemweit'); }` (Z.148).
*Rückbau:* Option und beide Verzweigungen entfernen; jede Zeile bekommt danach Bearbeiten und Löschen. Der Kopfkommentar Z.20-33 („DER UNTERSCHIED ZWISCHEN DEN BEIDEN SEITEN steckt in genau drei Dingen“) schrumpft von drei auf zwei — und da die zweite Seite entfällt, ist zu prüfen, ob die Datei überhaupt noch zwei Aufrufer hat.
*Risiko:* Nach Wegfall von admin_stammdaten.php hat stammdaten_ui.php nur noch EINEN Aufrufer. Die Datei existiert ausdrücklich, weil das Muster an zwei Stellen stand (Kopf Z.5-19). Sie deshalb aufzulösen wäre ein zweiter Umbau — er gehört angesprochen, nicht nebenbei gemacht.

**`server/stammdaten_ui.php:85-97 (sd_seite)`** — `if ($basis === 'admin_stammdaten.php') { return 'admin_stammdaten.php?t=standort&s=' . $baseId; }` samt Kommentar Z.86-89, dass die Linkprobe beide Zweige ausgeschrieben prüft.
*Rückbau:* Zweig streichen; der Parameter `$basis` wird damit einwertig und kann selbst entfallen (Signatur `sd_seite(int $baseId): string`).
*Risiko:* Wer den Parameter behält, behält die Möglichkeit, auf eine gelöschte Datei zu verlinken — mit dem Vorgabezweig Z.96, der jeden beliebigen Dateinamen durchreicht.

**`server/ui.php:2228-2236 (Kommentar zu ui_ortsfeld_bootstrap)`** — „…nur ruft `admin_stammdaten.php` `ui_krypto_bootstrap()` gar nicht auf: Die systemweite Stammdatenpflege braucht keine Verschlüsselung, aber sie trägt zwei der fünf Ortsfelder.“ — die Begründung dafür, dass die Adresssuche-Einstellungen aus ui_ortsfeld() selbst kommen.
*Rückbau:* Begründung umschreiben, NICHT die Bauform ändern. Die Regel „wo ein Ortsfeld steht, stehen seine Einstellungen“ bleibt richtig; nur ihr Anlass entfällt.
*Risiko:* Wer aus dem Wegfall des Anlasses schließt, der Bootstrap könne zurück in ui_krypto_bootstrap(), bricht die Adresssuche auf jeder Seite ohne Verschlüsselung. Genau davor warnt der Text.

**`server/ui.php:741-768 (Kommentarblock zum Einstellungsmenü)`** — „Bis Web 15.3.0 stand ‚Stammdaten systemweit‘ in beiden…“ und der Absatz „‚STAMMDATEN SYSTEMWEIT‘ HAT KEINEN EINTRAG MEHR (E-S8-14). Die Seite bleibt und ist über ihre Adresse erreichbar; … Der Weg dorthin steht im Handbuch.“
*Rückbau:* Zweiten Absatz ersatzlos streichen — die Seite bleibt nicht mehr. Das erste Beispiel (Web 15.3.0) beschreibt einen historischen Befund und kann bleiben oder durch ein anderes ersetzt werden.
*Risiko:* Der Absatz verweist ausdrücklich auf docs/Handbuch.md als Ort der Wegbeschreibung. Wer ihn hier streicht, aber das Handbuch nicht, hinterlässt eine Anleitung zu einer 404-Seite.

**`server/validate_lib.php:744-748, 754-757, 885-895 (Kommentare)`** — Die Tabelle der drei Schreibwege nennt „admin_stammdaten.php veh_save Verwaltung dieselben drei, kopiert“ (Z.746); Z.755-757 „hängt am Aufrufer (Konto oder systemweit)“; Z.888-895 begründet die vier Stammdatenprüfungen mit „ACHTMAL ausgeschrieben — viermal in einstellungen.php, viermal in admin_stammdaten.php“.
*Rückbau:* Zeilenweise auf die verbliebenen zwei Schreibwege (Formular und Sicherung) kürzen; die Begründung für die gemeinsame Prüfschicht bleibt und wird nicht geschwächt.
*Risiko:* Die Zusage „Gemeinsame Prüfschicht — alle Schreibwege, ohne Ausnahme“ (CLAUDE.md 4) hängt an diesen Kommentaren. Sie sind zu kürzen, nicht zu entfernen: Wer den Grund streicht, warum die Funktion dort steht, lädt die nächste Instanz ein, sie wieder aufzuteilen.

**`server/version.php:492, 722, 753, 1861, 3117-3119, 3232, 3363, 3414, 3461, 3497, 3529`** — Elf Stellen der Versionserzählung beschreiben das zentrale Modell (u. a. „die vordefinierten Eintraege als zweite, zugeklappte Karte“, „Standorte systemweit und Rettungsmittel systemweit zeigten auf dieselbe Datei“, „DREI FUNKTIONEN IN db.php, neben stammdaten_dup_global()“).
*Rückbau:* Bestehende Einträge NICHT umschreiben — sie erzählen, was damals war. Der Rückbau bekommt einen NEUEN Eintrag mit erhöhter HAUPT-Nummer: Datenmodell und spürbar veränderte Wege durch die Anwendung (Karte „Vordefinierte Standorte“, Verwaltungsseite, Nachbearbeitungsblock) — nach CLAUDE.md 2 eindeutig Haupt, nicht Neben.
*Risiko:* Ohne erhöhte WEB_VERSION sieht der Browser alte Dateien. Und: Die drei Zählungen sind getrennt — Uhr (watch/source/Const.mc) und Android (android/version.properties) bleiben unberührt, weil sie das Modell nicht kennen.

### C — Sicherung, Import, Export, API

30 Befunde (code 19, daten 1, doku 7, pruefmittel 2, schema 1).

**`docs/Backup-Format.md:112 und :1291`** — NEBENBEFUND, schon heute falsch: Beide Manifest-Beispiele nennen `"nutzlast": 9`, während der Code seit Web 16.0.0 eine 10 schreibt (adminbackup_lib.php:600, backup_lib.php:712). :1312 sagt zusätzlich „Nutzlast 9 (bis Web 14.1.0: 8)".
*Rückbau:* Nicht Teil des Rückbaus — aber die Zahlen liegen in genau den Blöcken, die für `user_bases` ohnehin angefasst werden. Bei der Gelegenheit auf 10 ziehen.
*Risiko:* Wer beim Rückbau die Nutzlastfrage anhand des Dokuments prüft, prüft gegen eine Zahl, die zwei Fassungen alt ist.

**`docs/Backup-Format.md:542-545`** — Das Feld im dokumentierten JSON-Beispiel, mit Kommentar: „Auswahl ZENTRALER Standorte dieser NutzerIn, als Namensliste. Zentrale Standorte selbst gehören dem Konto nicht und werden nicht exportiert — die Auswahl schon, sonst stünden nach dem Einspielen leere Listen da." Beispielwert `"Zentrale Wache Süd"`.
*Rückbau:* Feld und Kommentar aus dem Beispiel austragen. Ein Satz gehört DAZU, nicht nur weg: dass ältere Dateien das Feld noch führen und was mit ihm geschieht.
*Risiko:* Ein stillschweigend verschwundenes Feld ist für jemanden, der eine alte Datei in der Hand hält, nicht von einem Fehler zu unterscheiden.

**`docs/Backup-Format.md:862-865`** — Der Aufzählungspunkt unter „Feldkonventionen": „**Zentrale (globale) Stammdaten** (vom Admin gepflegt, seit Version 3) gehören nicht dem Konto und werden **nicht** exportiert. Beim Import werden Einträge, die zentral bereits (case-insensitiv) vorhanden sind, still übersprungen und in der Ergebnismeldung gezählt." Beschreibt genau backup_lib.php:1037/:1105 und die Meldung einstellungen.php:3238.
*Rückbau:* Punkt ersatzlos austragen — er beschreibt nach dem Rückbau ein Verhalten, das es nicht mehr gibt.
*Risiko:* Dieser Punkt und der bei :542-545 sind die zwei Stellen, an denen die Formatdokumentation das Modell trägt. Beide übersehen heißt: Das Dokument beschreibt weiter ein Format, das die Anwendung nicht mehr schreibt.

**`docs/Export-Format.md (ganze Datei), docs/JSON-Vertrag.md (ganze Datei, insb. :511)`** — POSITIVBEFUND, ausdrücklich: Keines der beiden Dokumente führt die Unterscheidung. Volltextsuche nach `zentral`, `systemweit`, `user_bases`, `admin_stammdaten` ergibt NULL Treffer. JSON-Vertrag.md:511 sagt sogar das Gegenteil ausdrücklich zu: „Standort und Rettungsmittel werden in der Weboberfläche nachgetragen" — die Uhr erfährt nichts davon.
*Rückbau:* Nichts auszutragen.
*Risiko:* Keines. Das ist die verlässliche Aussage: Das Gerätevertragsformat ist vom Rückbau nicht berührt.

**`docs/Technik.md:554, :556, :557, :559, :560, :561`** — Die Tabelle der Datenbanktabellen. Sechs Zeilen tragen die Formulierung „`user_id` NULL = **zentral** (vom Admin gepflegt), sonst persönlich" (bases/vehicles :554, resources :557, bw_units :559, transport_dests :560, user_defaults :561); :556 ist die vollständige Zeile zu `user_bases` („Auswahl **zentraler** Standorte je NutzerIn (E16)").
*Rückbau:* Zeile :556 streichen, die fünf Zusätze in den übrigen Zeilen austragen.
*Risiko:* Technik.md ist das Dokument, aus dem eine spätere Instanz das Datenmodell liest. Ein stehengebliebenes „NULL = zentral" an einer Spalte, die NOT NULL trägt, ist ein Widerspruch, der beim nächsten Umbau als Tatsache gelesen wird.

**`server/adminbackup_lib.php:584 (`'version' => 2`), :600 (`'nutzlast' => 10`), :2101 (`edbak_restore($zielUserId, $kopf)`), :2127`** — Das Konto-Backup führt KEINE eigene Fassung der Unterscheidung. Es baut und spielt über `edbak_build()`/`edbak_restore()` ein — es erbt `user_bases` und die Überspringregeln vollständig aus backup_lib.php. In der ganzen Datei kommt weder `user_id IS NULL` noch `zentral` vor.
*Rückbau:* Nichts eigenes. Aber: Jeder Befund an backup_lib.php trifft diesen Weg mit, und hier läuft er über ein SERVERSEITIG abgelegtes Paket, das Monate alt sein kann.
*Risiko:* Die Konto-Backups auf dem Server (`server/sicherungen/`) sind der größte Bestand alter Dateien mit gefüllter `user_bases`-Liste. Sie sind genau die Dateien, die nach dem Rückbau an :1058-1066 scheitern würden.

**`server/api/backup_restore.php:32-108 (Schranken), :101 (`NUTZLAST_HOECHSTENS = 10`)`** — ANTWORT AUF DIE NUTZLASTFRAGE, Teil 2 — ob sie sich ändern MÜSSTE. Nein, und der Grund steht in der Datei selbst: Die obere Schranke wehrt NEUERE Dateien in ÄLTEREN Installationen ab. `user_bases` verschwinden zu lassen heißt, ein optionales Feld WEGZUNEHMEN — der Fall, für den die Nummer nie gedacht war. Eine 11 bewirkte genau eines: Eine Installation, in der das Modell noch steht, wiese eine Datei nach dem Rückbau ab, obwohl sie dort vollständig lesbar wäre. Die untere Schranke (`< 6`) ist nicht betroffen; `$sd['user_bases'] ?? []` liest eine Datei ohne das Feld seit jeher fehlerfrei.
*Rückbau:* Keine Änderung an den Schranken. Die Richtung, die tatsächlich weh tut — ALTE Datei mit gefüllter Liste in eine zurückgebaute Installation —, lässt sich mit keiner Versionsnummer abfangen, weil die Zahl der alten Datei feststeht. Das ist ein Fall für die Schleife :1058-1066, nicht für die Nummer.
*Risiko:* Wer die Zahl trotzdem hebt, sperrt den Weg zurück in eine noch nicht aktualisierte Installation aus, ohne den einzigen echten Fehlerfall zu beheben.

**`server/api/day.php:222-226`** — Die Besatzungs-Vorschläge der Tagesantwort: `SELECT DISTINCT role_code, name FROM crew_presets WHERE base_id = ? AND (user_id = ? OR user_id IS NULL)`. Die einzige Stelle in `server/api/`, an der die Unterscheidung tatsächlich SQL ist.
*Rückbau:* Auf `AND user_id = ?` zusammenstreichen.
*Risiko:* Wird die Spalte `crew_presets.user_id` entfernt statt nur NOT NULL gesetzt, bricht die Tagesantwort — und damit die gesamte Tagesansicht, nicht nur die Vorschlagsliste.

**`server/api/export_data.php:206-224, :307-333, :445`** — POSITIVBEFUND mit Begründung: Der Export liest AUSSCHLIESSLICH die eingefrorenen Snapshot-Spalten (`d.vehicle_name`, `d.vehicle_typ`, `d.vehicle_kurz`, `d.base_name`); der Kommentar :207-210 hält fest, dass der Join auf `aircraft`/`bases` seit E8 entfallen ist. `resources` an :307/:330/:445 ist `mission_resources` (Freitext je Einsatz) und NICHT die Stammdatentabelle `resources` — gleiche Bezeichnung, verschiedene Sache.
*Rückbau:* Nichts. Das Exportformat trägt die Unterscheidung nicht und kann sie gar nicht tragen.
*Risiko:* Die Namensgleichheit `resources` (Stammdaten) / `resources` (Exportfeld aus `mission_resources`) ist die einzige Stolperstelle: Eine Suche nach `resources` über die Fläche liefert überwiegend Treffer, die mit dem Rückbau nichts zu tun haben.

**`server/api/import_commit.php:124-129`** — Ein Kommentarblock, der den Verzicht auf eine zweite Prüfung damit begründet, dass „ein zentrales Rettungsmittel erst zur Verfuegung steht, wenn die NutzerIn seinen Standort ausgewaehlt hat (E16)". Code selbst ist hier nicht betroffen — der Import prüft über `dt_anlegen()`/`dt_zuordnen()` (:202-204).
*Rückbau:* Begründung umschreiben. Die Aussage „eine zweite Fassung hier wäre die Stelle, an der beide auseinanderlaufen" bleibt richtig und soll stehen bleiben.
*Risiko:* Ein Kommentar, der eine abgeschaffte Regel als Begründung nennt, lädt beim nächsten Umbau dazu ein, die Prüfung für überflüssig zu halten.

**`server/assets/export.js:596`** — Ein Kommentar zur CSV-Formelneutralisierung: „Fremder Text gelangt über zentrale Stammdaten und über eingespielte Daten in die Textspalten". Die Begründung für M5-04 selbst bleibt gültig — über eingespielte Daten kommt fremder Text weiterhin herein.
*Rückbau:* Halbsatz „über zentrale Stammdaten und" streichen. Die Schutzmaßnahme selbst NICHT anfassen.
*Risiko:* Wer die Begründung als Ganzes streicht statt nur die halbe Quelle, nimmt einer Sicherheitsmaßnahme ihre einzige Erklärung — und die nächste Instanz hält sie für überflüssig.

**`server/backup_lib.php:1037`** — `if (stammdaten_dup_global('bases','name',$name)) { $stats['stammdaten_skipped']++; continue; }` — ein eigener Standort aus der Datei wird beim Einspielen VERWORFEN, wenn ein gleichnamiger zentraler existiert.
*Rückbau:* Zeile streichen; der `INSERT IGNORE` darunter genügt dann allein, denn der UNIQUE-Schlüssel `uq_user_name (user_id, name)` greift bei einem konkreten `user_id` sehr wohl (bei `user_id IS NULL` greift er nicht — das ist der Grund, warum es diesen Helfer überhaupt gibt, s. admin_stammdaten.php:98-99).
*Risiko:* Nach dem Rückbau liefert der Helfer immer false — die Zeile ist dann tote Last, nicht falsch. Wird der Helfer aus db.php entfernt, bricht diese Zeile mit einem Fatal Error.

**`server/backup_lib.php:1046-1056`** — `$baseIdByName()` — Name → Kennung, `WHERE name = ? AND (user_id = ? OR user_id IS NULL) ORDER BY user_id IS NULL LIMIT 1`. Das `ORDER BY` ist die Vorrangregel: eigen (0) vor zentral (1). Diese Funktion hängt an ALLEM — crew_presets, bw_units, resources, transport_dests, Diensttage.
*Rückbau:* Auf `WHERE name = ? AND user_id = ?` zusammenstreichen, `ORDER BY` entfällt.
*Risiko:* Wird nur die Spalte `bases.user_id` auf NOT NULL gesetzt, läuft die Abfrage weiter (der OR-Zweig findet nur nichts mehr). Wird die Spalte ENTFERNT, bricht sie — und mit ihr die Zuordnung sämtlicher Stammdaten einer eingespielten Sicherung.

**`server/backup_lib.php:1058-1066`** — Der Rückweg: `foreach (($sd['user_bases'] ?? []) as $bn)` löst den Namen auf und schreibt `INSERT IGNORE INTO user_bases (user_id, base_id)`. Das ist die schärfste Stelle der ganzen Fläche.
*Rückbau:* Schleife ersatzlos streichen — und zwar zusammen mit dem Streichen des Schreibers, nicht danach.
*Risiko:* WENN DIE TABELLE `user_bases` FÄLLT UND DIESE SCHLEIFE STEHEN BLEIBT: Eine alte Sicherung mit gefüllter Namensliste läuft in SQLSTATE 42S02, die Ausnahme fliegt aus dem try-Block bei :1019, und die Transaktion (:1027, bzw. die ÄUSSERE bei einem verschachtelten Aufruf) setzt ALLES zurück. Ergebnis: Die Datei ist nicht mehr einspielbar, und zwar mit einem rohen Datenbankfehler statt mit einer benannten Ablehnung wie `version_alt`. Trifft drei Aufrufer: api/backup_restore.php, adminbackup_lib.php:2101 (Konto-Backup) und demo_lib.php:464 (Demo-Reset, alle 30 Minuten unbeaufsichtigt). Bleibt die Tabelle stehen und sind nur die zentralen Standorte weg, ist die Schleife harmlos: `$baseIdByName` liefert null, `continue`.

**`server/backup_lib.php:1103, :1105`** — Dasselbe für Rettungsmittel: :1103 zählt einen von `pruef_rettungsmittel()` abgelehnten Satz, :1105 verwirft ein Rettungsmittel, das zentral schon existiert.
*Rückbau:* Nur :1105 streichen. :1103 bleibt — es hat mit dem zentralen Modell nichts zu tun.
*Risiko:* Die beiden Zeilen erhöhen denselben Zähler aus zwei völlig verschiedenen Gründen; wer beim Rückbau nicht hinsieht, streicht die falsche.

**`server/backup_lib.php:1435-1440`** — Dieselbe Auflösung für das Rettungsmittel eines Diensttags: `SELECT id FROM vehicles WHERE name = ? AND (user_id = ? OR user_id IS NULL) ORDER BY user_id IS NULL LIMIT 1`. Zweite, getrennte Fassung derselben Regel — sie steht nicht in `$baseIdByName`.
*Rückbau:* Ebenso auf `user_id = ?` zusammenstreichen.
*Risiko:* Zwei Fassungen einer Regel an zwei Stellen: Wer :1051 anfasst und :1436 übersieht, hinterlässt eine halb zurückgebaute Auflösung, die sich nur an alten Dateien zeigt.

**`server/backup_lib.php:618-624, :731`** — Das EINZIGE Feld, in dem das Backup-Format die Unterscheidung eigen/zentral führt: `stammdaten.user_bases` — eine Liste der NAMEN der zentralen Standorte, die dieses Konto ausgewählt hat. Die Abfrage steht auf `ub.user_id = ? AND b.user_id IS NULL`, das Feld wird in :731 in den `stammdaten`-Block geschrieben. Zentrale Einträge selbst werden nie exportiert (jede Stammdatenabfrage in :572-:655 steht auf `user_id = ?`).
*Rückbau:* Abfrage und Feld ersatzlos streichen. Damit hat das Nutzerformat keine Spur des Modells mehr.
*Risiko:* Wer nur das Feld streicht und den Rückweg (:1058-1066) stehen lässt, hat den Schreiber entfernt und den Leser behalten — der Fehler fällt erst beim Einspielen einer ALTEN Datei auf, also genau dann, wenn niemand einen zweiten Versuch hat.

**`server/backup_lib.php:712; server/api/backup_eintraege_restore.php:57; server/adminbackup_lib.php:600, :2127`** — ANTWORT AUF DIE NUTZLASTFRAGE, Teil 1 — wo die Zahl steht. Geschrieben wird sie an VIER Stellen, alle hart: `'version' => $ohneSpuren ? 10 : 7` (Nutzerformat), `$eintraege['version'] = 10` (Eintragsfenster), `'nutzlast' => 10` (Manifest des Konto-Backups), `$f['version'] = 10` (Eintragsteile des Konto-Backups).
*Rückbau:* Wenn überhaupt gehoben wird, dann an allen vier Stellen. Es gibt keine gemeinsame Konstante.
*Risiko:* Eine Erhöhung an drei von vier Stellen bleibt still: Der Rückweg entscheidet an `>= 8` (backup_lib.php:1000), und der ist von einer 10 oder 11 nicht zu unterscheiden.

**`server/backup_lib.php:879, :1020-1021`** — Der Zähler `stammdaten_skipped` und der Kommentar darüber („zentral vorhandene Eintraege werden uebersprungen und gezaehlt, s. 6.3/8"). Der Zähler hat aber SECHS Erhöhungsstellen mit zwei ganz verschiedenen Bedeutungen: :1037 und :1105 = „zentral schon da"; :1103, :1158, :1166, :1173, :1180 = „ungültig bzw. Standort nicht auflösbar".
*Rückbau:* Zähler behalten (die vier Standort-Gründe bleiben), Kommentar :1020-1021 austragen.
*Risiko:* Der Zähler bleibt nach dem Rückbau mit unveränderter Bedeutung stehen — nur seine Beschriftung in der Oberfläche wird falsch (s. nächster Befund).

**`server/db.php:60, :69-79 (`stammdaten_dup_global`), :84-92 (`stammdaten_dup_personal_count`), :118-120`** — Die beiden Helfer, auf denen die ganze Duplikatlogik steht. `stammdaten_dup_global()` hat 13 Aufrufer (6 in admin_stammdaten.php, 5+6 in einstellungen.php, 2 in backup_lib.php), `stammdaten_dup_personal_count()` sechs — alle in admin_stammdaten.php. Der Kommentar :118-120 erklärt zusätzlich, dass `$userId === null` in der Löschregel für Rettungsmittel „den systemweiten Bestand" meint.
*Rückbau:* `stammdaten_dup_personal_count()` fällt ersatzlos (nur die Verwaltungsseite ruft sie). `stammdaten_dup_global()` fällt ebenfalls — aber erst, wenn alle 13 Aufrufer weg sind, davon zwei in meiner Fläche (:1037, :1105).
*Risiko:* Der Helfer ist der gemeinsame Nenner zwischen Verwaltungsseite, Kontoeinstellungen und Sicherungs-Rückweg. Wer ihn zuerst entfernt, bricht drei Bereiche gleichzeitig; wer ihn zuletzt entfernt, hat 13 Stellen zu zählen.

**`server/demo_lib.php:409 und server/demo/fixture.json.gz`** — Der Demo-Reset löscht je Konto aus einer festen Tabellenliste, in der `user_bases` steht (:409). Die Fixture führt `daten.stammdaten.user_bases` — geprüft: die Liste ist LEER (`[]`), Nutzlast 7, zwei eigene Standorte.
*Rückbau:* `'user_bases'` aus der Liste :409 streichen, sobald die Tabelle fällt. Die Fixture braucht nicht neu erzeugt zu werden — ein leeres Feld liest der Rückweg entweder weiter oder nach dem Streichen der Schleife gar nicht mehr.
*Risiko:* Bleibt `'user_bases'` in der Liste stehen, nachdem die Tabelle gefallen ist, scheitert `demo_konto_leeren()` bei JEDEM Reset — alle 30 Minuten, unbeaufsichtigt, und der Demo-Bestand bleibt halb gelöscht stehen. Dass die Fixture-Liste leer ist, rettet nur die andere Hälfte (die Schleife :1058-1066), nicht diese.

**`server/diensttag_lib.php:372-380 (`dt_base_erlaubt`), :399-408 (`dt_vehicle_erlaubt`)`** — DAS TOR, DURCH DAS JEDER SCHREIBWEG DER API GEHT. Beide Funktionen joinen `user_bases`. Aufgerufen von `dt_zuordnen()` (:529-530), das wiederum von api/day.php:105 (Diensttag speichern), api/import_commit.php:202-204 über `dt_anlegen()` und vom Formular benutzt wird. Der Kommentar :385-397 erklärt ausdrücklich, dass diese Fassung mit `dt_vehicles()` übereinstimmen MUSS, sonst setzt `dt_zuordnen()` beim Speichern still auf NULL zurück.
*Rückbau:* `LEFT JOIN user_bases` und den `user_id IS NULL`-Zweig entfernen; übrig bleibt `WHERE b.id = ? AND b.user_id = ?` bzw. für Rettungsmittel `v.user_id = ?` (der Zweig `v.base_id IS NULL` bleibt — er gehört zu E-S9-09, nicht zum zentralen Modell).
*Risiko:* Der Kommentar benennt die Falle selbst: Laufen diese beiden Fassungen und die Auswahllisten (`dt_bases`/`dt_vehicles`) beim Rückbau auseinander, steht ein Eintrag im Auswahlfeld und wird beim Speichern WORTLOS verworfen. Beide Paare gehören in dasselbe Paket.

**`server/diensttag_lib.php:417-426 (`dt_bases`), :439-451 (`dt_vehicles`)`** — Die Auswahllisten. `dt_bases()` liefert eine berechnete Spalte `b.user_id IS NULL AS zentral` — das Kennzeichen, aus dem import.php, index.php:269 und diensttag_neu.php:84 ihre Beschriftung bauen. Beide Funktionen joinen `user_bases`.
*Rückbau:* Join und Spalte `zentral` entfernen; `WHERE b.user_id = ?` bzw. `v.user_id = ?`.
*Risiko:* Die Spalte `zentral` ist ein Vertrag nach außen: Wer sie entfernt, ohne die drei Verbraucher mitzunehmen, bekommt kein PHP-Fehler, sondern still eine Beschriftung, die nie mehr erscheint — oder, bei `$b['zentral']` auf einem fehlenden Schlüssel, eine Notice.

**`server/einstellungen.php:3237-3238`** — Die Rückmeldung nach dem Einspielen: `` (${s.stammdaten_skipped} übersprungen, bereits systemweit vorhanden)``. Nach dem Rückbau ist das die EINZIGE Erklärung, die die Anwendung für einen Zähler gibt, dessen verbliebene Ursachen ausschließlich „Standort nicht auflösbar" bzw. „Rettungsmittel ungültig" lauten.
*Rückbau:* Text auf die verbleibenden Ursachen umschreiben („übersprungen, Standort nicht auflösbar" o. ä.). Wortliste danach fahren — sichtbarer Text.
*Risiko:* Wird der Text vergessen, meldet die Anwendung nach jedem Einspielen mit fehlendem Standort einen Grund, den es nicht mehr gibt. Das ist keine Kosmetik: Es schickt die Nutzerin auf die Suche nach einer Verwaltungsseite, die abgeschaltet ist.

**`server/import.php:25-27 (Kommentar), :87-95 (Auswahlfeld)`** — Die Importseite baut ihre beiden Auswahlfelder aus `dt_bases()`/`dt_vehicles()` und hängt an den Standort ` (systemweit)` (:93). Der Kommentar :25-27 begründet das ausdrücklich mit E16 („eigene und ausgewaehlte zentrale Standorte").
*Rückbau:* Beschriftungszusatz und Kommentar streichen; die Feldbefüllung selbst bleibt.
*Risiko:* Sichtbarer Text — Wortliste ist Pflicht. Nebenbefund: Dieselbe Sache heißt hier ` (systemweit)`, in index.php:269 und diensttag_neu.php:84 aber ` (zentral)`. Zwei Wörter für eine Sache, beide fallen weg; wer nur eines sucht, findet die Hälfte.

**`server/ingest.php (ganze Datei)`** — POSITIVBEFUND: Der Geräte-Eingang kennt weder `base_id` noch `vehicle_id`. Er ruft aus diensttag_lib.php nur `dt_zu_dayref()` (:427), `dt_rueckfall()` (:431) und `dt_zeitraum_fortschreiben()` (:897) — keine davon berührt `user_bases` oder `user_id IS NULL`. Volltextsuche nach `zentral`/`user_bases`/`user_id IS NULL`: null Treffer.
*Rückbau:* Nichts.
*Risiko:* Keines. Ein von der Uhr angelegter Diensttag ist neutral (E26) und wird es bleiben.

**`server/komplett_lib.php:286-292 (`komp_tabellen`), :759-771 (`komp_tabellenkopf`)`** — Das Komplett-Backup (.edk) kennt keine Unterscheidung und braucht keine: `SHOW FULL TABLES` nimmt jede Tabelle mit, also `user_bases` samt Inhalt und jede Zeile mit `user_id IS NULL`. Der Rückweg schreibt je Tabelle `DROP TABLE IF EXISTS` + das originale `CREATE TABLE` aus dem Dump.
*Rückbau:* Nichts zu ändern — der Dump zieht von selbst nach.
*Risiko:* DAS EINSPIELEN EINER .edk VON VOR DEM RÜCKBAU HOLT DAS GANZE MODELL ZURÜCK: Tabelle `user_bases`, alle zentralen Zeilen, dazu über `schema_migrations` den alten Migrationsstand (:789-791, Kopfzeile :800). Danach steht die Installation wieder vor dem Rückbau und `update.php` muss die Rückbau-Migration erneut laufen lassen. Das ist kein Fehler, aber es gehört ausdrücklich ins Runbook — das Komplett-Backup ist eine Zeitmaschine, und niemand rechnet damit, dass sie ein gelöschtes Datenmodell zurückbringt.

**`server/schema.sql:72 und :101 (`user_id … NULL -- NULL = zentral (Admin-Eintrag)`), :86-96 (Tabelle `user_bases` samt Kommentarblock), sowie die gleichlautenden Spalten in crew_presets/resources/bw_units/transport_dests`** — Das Modell im Schema: sechs `user_id`-Spalten NULL-fähig mit dem Kommentar „NULL = zentral", dazu die Tabelle `user_bases` mit ihrem eigenen Erklärblock. Wichtig für den Rückweg: `UNIQUE KEY uq_user_name (user_id, name)` greift bei `user_id IS NULL` NICHT wie erwartet (MySQL vergleicht NULL nie gleich) — genau deshalb existiert `stammdaten_dup_global()`.
*Rückbau:* `user_bases` löschen; die sechs `user_id`-Spalten auf NOT NULL. Beides braucht eine Migration UND — nach Abschnitt 3 der Arbeitsanweisung — den ausdrücklichen Hinweis, dass eine Administratorin danach `update.php` aufrufen muss.
*Risiko:* Ein `ALTER … NOT NULL` scheitert, solange auch nur eine Zeile mit `user_id IS NULL` übrig ist. Der Auftraggeber hat am 09.09.2026 gemeldet, dass alle systemweiten STANDORTE gelöscht sind — das sagt nichts über zentrale Zeilen in `vehicles`, `crew_presets`, `resources`, `bw_units`, `transport_dests` OHNE Standortbezug. admin_stammdaten.php:469-474 zeigt, dass es zentrale Rettungsmittel ohne `base_id` überhaupt geben kann; die hängen an keinem Standort und sind mit den Standorten NICHT mitgelöscht worden. Vor der Migration zählen, nicht annehmen.

**`tools/referenzdatensatz/einspielen/einspielen.py:186-193, :222-223`** — Das Einspielwerkzeug liest Standort- und Rettungsmittelkennungen aus den Auswahllisten von `diensttag_neu.php` und streift dabei ` (zentral)` vom Optionstext ab (:223). Der Docstring :190 begründet den Weg über die Seite ausdrücklich mit „einschliesslich zentraler Eintraege und der Standortbindung".
*Rückbau:* Abstreif-Lambda auf `x.strip()` vereinfachen, Docstring nachziehen.
*Risiko:* Nach dem Rückbau ist das Abstreifen ein wirkungsloser No-op — es bricht nichts. Wichtiger ist der Nachbar: Der Leser hängt am Markup einer Seite und ist genau dort schon einmal still leergelaufen (F-S2-A, im Kommentar :195-205 dokumentiert). Wer die Optionsbeschriftung anfasst, prüft dieses Werkzeug mit.

**`tools/wiederherstellungs-probe/probe.php:531-578`** — DECKUNGSLÜCKE, ausdrücklich benannt: Die Wiederherstellungs-Probe baut ihre Nutzlasten von Hand (`$nutzlast8` ab :531) und ruft `edbak_restore()` dreimal (:552, :566, und für Nutzlast 7 ab :578). Volltextsuche nach `stammdaten`, `user_bases`, `bases`, `zentral` in der Datei: NULL Treffer. Die Probe fasst den Stammdatenteil des Rückwegs überhaupt nicht an.
*Rückbau:* Wenn die Schleife :1058-1066 fällt, gibt es kein vorhandenes Prüfmittel, das den Fall „alte Datei mit gefüllter `user_bases`-Liste" abdeckt. Der Fall muss entweder in die Probe aufgenommen oder im Prüfdokument ausdrücklich als „nicht maschinell geprüft" geführt werden.
*Risiko:* Die Probe liefe nach dem Rückbau grün durch und bewiese nichts über den einzigen Fall, an dem der Rückbau tatsächlich brechen kann. Genau das Muster, vor dem Abschnitt 6 der Arbeitsanweisung warnt: eine grüne Zahl, die nicht benennt, was sie gemessen hat.

### D — Dokumentation

21 Befunde (doku 21).

**`docs/Design.md:1962-1964`** — Kapitel 9.29 (Kartendialog): „**Ein** Dialog für **fünf** Einbauorte: Einsatzort, manueller Abfahrtort, Transportziel und die Lagefelder der Standorte in Konto- **und Systemverwaltung**."
*Rückbau:* „und Systemverwaltung" streichen, „fünf" wird zu „vier". Fließtext, keine erzeugte Tabelle — von Hand zu ändern (die erzeugten Tabellen in Design.md stammen aus tools/design/tabellen.py, dieser Absatz nicht).
*Risiko:* Einziger Fund in Design.md und der einzige mit dem Wort „Systemverwaltung" — keine Suche nach „systemweit", „zentral" oder „admin_stammdaten" findet ihn. Dazu die dritte Zählung derselben Einbauorte (nach Technik 4004 und 4088): Drei Zahlen an drei Stellen müssen gemeinsam fallen.

**`docs/Handbuch.md:1211-1213`** — Abschnitt 4 (Abweichende Besatzung): „… schlägt das Feld unter der Überschrift „Vorlagen des Standorts" deine Besatzungs-Vorbelegungen **und die zentralen Stammdaten der jeweiligen Rolle** vor (Abschnitt 9.1 bzw. 9.4)".
*Rückbau:* „und die zentralen Stammdaten der jeweiligen Rolle" streichen, Querverweis auf „(Abschnitt 9.1)" kürzen. Entspricht dem Code-Rückbau der Abfrage `(user_id = ? OR user_id IS NULL)` in crew_presets (siehe Technik.md:1300-1302).
*Risiko:* Steht weit weg von Kapitel 9 und enthält das Wort „systemweit" nicht — der wahrscheinlichste vergessene Fund der ganzen Fläche.

**`docs/Handbuch.md:2369`** — Tabellenzeile im Kopf von Kapitel 9: „**Einstellungen → Standorte** (die Liste) | Eigene Standorte anlegen und bearbeiten, **vordefinierte** Standorte auswählen — und die Karte **„Ohne Standort"** …"
*Rückbau:* Den Teil „**vordefinierte** Standorte auswählen" aus der Zelle entfernen. Der Rest der Zeile (eigene Standorte, Karte „Ohne Standort") bleibt unberührt.
*Risiko:* Diese Tabelle ist der Einstieg ins Kapitel; wer nur sie liest, hält die Auswahlfunktion für vorhanden.

**`docs/Handbuch.md:2519-2521`** — „**Vordefinierte Standorte** stehen in einer eigenen, zugeklappten Karte darunter; ihr Kopf nennt, wie viele es gibt und wie viele davon ausgewählt sind." — beschreibt die Auswahlkarte auf der Standortliste.
*Rückbau:* Absatz ersatzlos streichen. Achtung auf den Zusammenhang: Der Absatz davor beschreibt die zugeklappte Karte je Standort, der danach beginnt mit „Ein **Rettungsmittel** ist entweder …" — beide bleiben, der Übergang ist nach der Streichung zu lesen.
*Risiko:* Verwechslungsgefahr beim Streichen: Direkt darüber steht die (bleibende) Beschreibung der zugeklappten Standort-Karten. Wer zu großzügig löscht, nimmt die falsche Karte mit.

**`docs/Handbuch.md:2559-2568`** — Der Absatz zur Stern-Vorbelegung: „… werden bei neuen Diensttagen vorbelegt — das gilt auch für vom Admin zentral hinterlegte Einträge (s. 9.4). Bei **Standorten** ließ sich das bis Web 6.3.0 nur für eigene Einträge setzen; die Schaltfläche fehlte bei den vordefinierten … Ein Konto, das ausschliesslich mit vordefinierten Standorten arbeitet — der Regelfall überall dort, wo die Standorte zentral gepflegt werden —, konnte damit gar keine Vorbelegung setzen. Jetzt steht sie bei jedem **ausgewählten** vordefinierten Standort. (Nicht ausgewählte bleiben aussen vor: Was nicht in den Auswahllisten steht, kann auch keine Vorbelegung sein.)"
*Rückbau:* Auf die schlichte Regel eindampfen: Stern an eigenem Standort/Rettungsmittel belegt neue Diensttage vor. Die gesamte Web-6.3.0-Historie, der Verweis „(s. 9.4)" und der Klammersatz zu nicht ausgewählten Einträgen entfallen mit dem Modell.
*Risiko:* Der Absatz enthält mit „der Regelfall überall dort, wo die Standorte zentral gepflegt werden" eine Betriebsannahme, die R39 gerade verwirft. Bleibt sie stehen, widerspricht das Handbuch der Programmentscheidung an einer Stelle, die niemand mit „systemweit" findet.

**`docs/Handbuch.md:2604-2607`** — Abschnitt 9.3 (Transportziele): Koordinaten lassen sich hinterlegen „… und auf drei Ebenen: **zentral durch die Verwaltung**, hier im eigenen Konto und einmalig am einzelnen Einsatz."
*Rückbau:* „drei Ebenen" wird zu zwei Ebenen; „zentral durch die Verwaltung" streichen. Reine Zahlenkorrektur im Fließtext — leicht zu übersehen, weil das Wort „systemweit" hier nicht fällt.
*Risiko:* Eine Zahl im Fließtext („drei Ebenen"), die nach dem Rückbau still falsch ist. Kein Prüfmittel schlägt darauf an.

**`docs/Handbuch.md:2615-2661`** — Abschnitt 9.4 „Vordefinierte (systemweite) Stammdaten" — der vollständige Lehrtext des zentralen Modells: „Der Admin kann alle sechs Bereiche zusätzlich **systemweit** hinterlegen", die Auswahlmechanik über „Standorte → Vordefinierte Standorte" (E16/user_bases), das Kennzeichen „zentral", die Namenskollisionsregeln in beide Richtungen (persönlicher Eintrag wird abgelehnt / bekommt Warnhinweis „identisch mit systemweitem Eintrag"), der Absatz „So kommt die Verwaltung dorthin" mit der Zeile „wie viele Konten diesen Standort ausgewählt haben", dazu zwei Kästen: der Bugkasten zu Web 16.3.0/Backlog Nr. 163 und der Kasten „Die Seite hat seit Web 15.4.0 keinen Menüpunkt mehr (E-S8-14)" mit den „zwei Wegen" zu admin_stammdaten.php.
*Rückbau:* Abschnitt 9.4 ersatzlos streichen, samt beider Kästen. Da 9.4 der letzte Abschnitt von Kapitel 9 ist, verschiebt das keine Nummern — aber jeder Verweis auf „9.4" muss mit (Handbuch:293, 1212, 2560). Prüfen, ob statt der Streichung ein Satz „Stammdaten gibt es nur noch im eigenen Konto" nötig ist, damit BestandsnutzerInnen den Wegfall verstehen.
*Risiko:* Der Abschnitt ist die einzige Stelle, an der der Auswahlmechanismus erklärt wird. Bleibt er stehen, suchen NutzerInnen nach einer Karte „Vordefinierte Standorte", die es nicht mehr gibt; wird er gestrichen, ohne die drei Querverweise mitzuziehen, zeigen drei Stellen im Handbuch auf einen leeren Abschnitt.

**`docs/Handbuch.md:292-294`** — „**Stammdaten systemweit** hat seit Web 15.4.0 keinen Menüpunkt mehr — die Seite bleibt und ist über ihre Adresse erreichbar (Abschnitt 9.4)." Steht im Kapitel zum Zahnrad-Menü, direkt unter der Tabelle der drei Menüblöcke.
*Rückbau:* Satz vollständig streichen. Er sagt ausdrücklich das Gegenteil von R39 („die Seite bleibt") und ist die einzige Stelle im Menükapitel, die die Seite überhaupt erwähnt — die Blocktabelle darüber (Handbuch:286-287) nennt sie zu Recht nicht.
*Risiko:* Bleibt der Satz, sucht eine Admin nach dem Rückbau die Adresse admin_stammdaten.php und bekommt einen 404 — mit einem Handbuch in der Hand, das ihr sagt, die Seite sei noch da.

**`docs/Handbuch.md:3772-3773`** — Kapitel 12.7 (Backup-Ziele): „Nicht zu verwechseln mit dem **Transportziel** eines Einsatzes — das ist die Zielklinik und steht unter Stammdaten." Das ist die Handbuchfassung des Hinweistextes, der in der Anwendung als Link auf admin_stammdaten.php ausgegeben wird (server/admin_sicherungsziele.php:185) — und laut Handbuch:2657-2660 einer der „zwei Wege" zur systemweiten Seite.
*Rückbau:* Der Satz selbst kann bleiben, muss aber auf „Einstellungen → Standorte" zeigen, sobald der Link im Code sein Ziel verliert. Im selben Paket ist der Verweis in `admin_sicherungsziele.php` mit zu ändern, sonst führt eine Betriebsseite ins Leere.
*Risiko:* Der einzige verbliebene In-App-Weg zur Seite. Wird nur die Seite gelöscht und dieser Link vergessen, steht auf Betrieb → Backup-Ziele ein toter Link — und das Handbuch beschreibt ihn weiter.

**`docs/Technik.md:1298-1302`** — Abschnitt zu den Besatzungsfeldern: „ein `<datalist>` mit den Vorbelegungen der Rolle aus `crew_presets`, **wie überall mit `(user_id = ? OR user_id IS NULL)`**, aber ohne Schranke." — das „wie überall" erklärt die Abfrageform des zentralen Modells zum Hausstandard.
*Rückbau:* Die Bedingung auf `user_id = ?` zurückführen und das „wie überall" ersatzlos streichen — es beschreibt nach P5 nichts mehr. Der fachliche Grund im Folgesatz (wer aushilft, steht nicht in den Stammdaten) bleibt gültig und bleibt stehen.
*Risiko:* Das „wie überall" ist die Stelle, die das Muster verallgemeinert. Bleibt sie stehen, ist der Rückbau in der Doku widerlegt, obwohl der Code sauber ist.

**`docs/Technik.md:4004-4012`** — Abschnitt 4 (Ortsfeld), Tabelle der Verwendungen: Einleitung „Die **sechs**:" und zwei Zeilen mit dem Admin-Zweig — „Standort im Konto / zentral | `sdbase` / `adbase`" und „Zielklinik im Konto / zentral | `sdtd<id>` / `adtd<id>`".
*Rückbau:* Die Präfixe `adbase` und `adtd<id>` samt der Wörter „/ zentral" aus beiden Zeilen entfernen; die Einleitung „Die sechs:" wird zu „Die vier:". Die Zeilen selbst bleiben (die Kontofassung `sdbase`/`sdtd<id>` besteht fort).
*Risiko:* Die Zahl „sechs" und die Präfixliste sind der Vertrag zwischen `ui_ortsfeld()` und `ortsfeld.js`. Bleibt ein `ad…`-Präfix dokumentiert, sucht die nächste Instanz nach einem Aufrufer, den es nicht mehr gibt.

**`docs/Technik.md:4067-4070`** — Begründung, warum `ui_geocoder_bootstrap()` in `ui_ortsfeld()` sitzt: „… und keine Seite kann sie vergessen (**`admin_stammdaten.php` ruft `ui_krypto_bootstrap()` nie auf und hätte sie sonst nicht**)." Die systemweite Seite ist hier der tragende Beleg der Architekturentscheidung.
*Rückbau:* Den Klammerbeleg streichen und die Begründung ohne ihn tragfähig neu formulieren — die Entscheidung bleibt richtig, ihr einziges dokumentiertes Beispiel fällt weg. Ersatzbeispiel suchen oder die Regel allgemein begründen.
*Risiko:* Wird nur die Klammer gelöscht, steht dort eine Behauptung ohne Beleg — und die nächste Umbauidee („der Bootstrap kann doch in die Seite") hat kein Argument mehr gegen sich. Das ist der Fund, bei dem Streichen allein nicht genügt.

**`docs/Technik.md:4088-4093`** — Ortswahl/Pin-Knopf: „Seit Web 15.8.0 tragen **fünf** Felder den Knopf statt zweier: Einsatzort, manueller Abfahrtort, Transportziel … und die Lagefelder der Standorte in `einstellungen.php` **und `admin_stammdaten.php`**."
*Rückbau:* `admin_stammdaten.php` aus der Aufzählung nehmen, „fünf" wird zu „vier". Der Folgesatz zu Backlog Nr. 70 (Nur-Lage-Fassung hatte keine Karte) bleibt — er betrifft die Kontofassung.
*Risiko:* Zweite gezählte Aufzählung derselben Sache (nach 4004). Wer nur eine der beiden Zahlen korrigiert, hinterlässt zwei Stellen, die sich widersprechen.

**`docs/Technik.md:554`** — Schematabelle, Zeile `bases` / `vehicles` / `crew_presets`, Satzende: „`user_id` NULL = **zentral** (vom Admin gepflegt), sonst persönlich".
*Rückbau:* Satzende streichen; nach dem Rückbau ist `user_id` immer gesetzt (Schema: NOT NULL). Die Doku muss die neue Zusicherung ausdrücklich benennen, sonst bleibt offen, ob NULL noch vorkommen kann.
*Risiko:* Diese Zeile ist die Referenz für jede neue Abfrage. Bleibt sie, schreibt die nächste Instanz weiterhin `(user_id = ? OR user_id IS NULL)` — der Rückbau wäre im Code rückgängig, ohne dass jemand es merkt.

**`docs/Technik.md:556`** — Schematabelle, ganze Zeile `user_bases`: „Auswahl **zentraler** Standorte je NutzerIn (E16). Nur ausgewählte erscheinen in den Auswahllisten; eigene Standorte brauchen hier keine Zeile".
*Rückbau:* Zeile ersatzlos entfernen, wenn die Tabelle mit der Migration fällt. Mit ihr geht die Entscheidungsnummer E16 aus der Technikdoku — prüfen, ob E16 anderswo referenziert wird, bevor sie verschwindet.
*Risiko:* Die einzige Beschreibung der Tabelle. Eine gelöschte Tabelle, die in der Schematabelle weiterlebt, führt beim nächsten Backup-/Export-Umbau zu Code für eine Tabelle, die es nicht gibt.

**`docs/Technik.md:557`** — Schematabelle, Zeile `resources`: „Vorbelegung „Andere Rettungsmittel" ; `user_id` NULL = zentral, sonst persönlich".
*Rückbau:* Den Halbsatz „`user_id` NULL = zentral, sonst persönlich" streichen.
*Risiko:* Vier Zeilen (557, 559, 560, 561) tragen dieselbe Formel in leicht verschiedener Schreibweise — wer nur nach einer Schreibweise sucht, lässt zwei stehen.

**`docs/Technik.md:559`** — Schematabelle, Zeile `bw_units`: „Bergwacht-Bereitschaften; `user_id` NULL = zentral, sonst persönlich".
*Rückbau:* Den Halbsatz streichen.
*Risiko:* siehe 557 — gleiche Formel, andere Zeile.

**`docs/Technik.md:560`** — Schematabelle, Zeile `transport_dests`: „… `base_id` = Standort; `user_id` NULL = zentral, sonst persönlich".
*Rückbau:* Den Halbsatz streichen; der Rest der Zeile (Freitext ohne FK, Koordinaten seit Web 6.1.0, base_id) bleibt.
*Risiko:* siehe 557.

**`docs/Technik.md:561`** — Schematabelle, Zeile `user_defaults`: „… (`kind` in `base`/`vehicle`, `item_id` verweist auf `bases.id` bzw. `vehicles.id`, **persönlich oder zentral**)".
*Rückbau:* „persönlich oder zentral" streichen. Achtung: Hier steht die Formel anders formuliert als in 557/559/560 — eine Suche nach „user_id NULL" findet sie nicht.
*Risiko:* Formel ohne die Zeichenkette „user_id IS NULL" — der Fund, den eine reine Stichwortsuche über die Schematabelle verfehlt.

**`docs/Technik.md:82-88`** — Verzeichnisstruktur: „admin_stammdaten.php  Systemweite Stammdaten aller sechs Typen" mit sechs Folgezeilen zur Gliederung seit Web 17.0.0 (`?t=standorte` = Liste, `?t=standort&s=<id>` = Seite eines Standorts, entfallene Segmentwahl, `?t=rettungsmittel` als Weiche).
*Rückbau:* Kompletten Baumeintrag samt der sechs Erläuterungszeilen entfernen, wenn die Datei gelöscht wird.
*Risiko:* Die Verzeichnisstruktur ist die Landkarte für jede neue Instanz. Ein Eintrag für eine gelöschte Datei schickt die nächste Sitzung auf die Suche nach Code, den es nicht mehr gibt.

**`docs/Technik.md:89-95`** — Verzeichnisstruktur: „stammdaten_ui.php  Zeile, Dialoge und Adresse der Stammdatenlisten — eine Fassung fuer die Kontoansicht (einstellungen.php) **und die Adminansicht (admin_stammdaten.php)**, seit Web 9.10.0. `sd_zeile()`, `sd_seite()`, `sd_oeffner()` und die drei Dialogfunktionen …"
*Rückbau:* Die Hälfte „und die Adminansicht (admin_stammdaten.php)" streichen; die Datei selbst bleibt. Damit entfällt auch der Daseinsgrund des zweiten Parameters von `sd_seite($id, $seite)` — die Beschreibung ist an den tatsächlichen Code-Rückbau anzupassen, nicht nur zu kürzen.
*Risiko:* Die Formulierung „eine Fassung für zwei Ansichten" ist die dokumentierte Begründung des Bausteins. Bleibt sie stehen, wirkt der verbliebene Seiten-Parameter wie eine bewusste Erweiterungsstelle.

### E — Rahmenplan, Backlog und Konzepte

44 Befunde (code 1, doku 37, pruefmittel 5, schema 1).

**`docs/Backlog.md:1476-1502 (Nr. 152)`** — „Standortseiten: Standort zuerst, ein Menüpunkt, Kennzahlen, Dialoge, Landung." Aufgenommen 07.09.2026 vom Auftraggeber bei der Mockup-Freigabe. Zeile 1498: „**Verwaltung → Stammdaten ebenso.**" Das ist die Beauftragung, die zum Neubau der zurückzubauenden Seite geführt hat — acht Tage nach R39.
*Rückbau:* Der Satz „Verwaltung → Stammdaten ebenso" wird mit dem Rückbau gegenstandslos. Beim Verschieben nach *Erledigt* (AP8) ist zu vermerken, welcher Teil des Punktes nur bis zum Rückbau lebt.
*Risiko:* **Der zentrale Widerspruch auf der Backlog-Seite.** Ein Backlog-Punkt vom 07.09.2026 beauftragt ausdrücklich den Ausbau einer Seite, die eine Programmentscheidung vom 30.08.2026 abschafft. Weder der Punkt noch die Freigabe nennen R39.

**`docs/Backlog.md:1665-1683 (Nr. 166, Erledigt)`** — „~~Der Referenzbestand kennt keinen systemweiten Standort~~ — ZURÜCKGEZOGEN am 09.09.2026, am Tag der Aufnahme." Begründung: R39 baut das Modell in P5 zurück, auf dem Produktivsystem ist es bereits gelöscht. Nennt als Folge: der Eintrag `42a-stammdaten-standortseite` in `tools/screenshots/seiten.json` fällt mit dem Rückbau weg; die Klickprobe `ap5-verwaltung-besatzung-anlegen` deckt die Seite bis dahin ab.
*Rückbau:* Bleibt als Beleg stehen (so ausdrücklich vermerkt). Der genannte Wegfall des Bilderlauf-Eintrags ist beim Rückbau abzuarbeiten — der Eintrag existiert (tools/screenshots/seiten.json:219-222), ebenso `42-stammdaten-systemweit` (dort:213-216).
*Risiko:* Beide Einträge zeigen nach dem Rückbau ins Leere. Ein Bilderlauf, der eine Seite nicht auflösen kann, meldet heute „8× Platzhalter nicht auflösbar" — nach dem Rückbau wäre das keine Meldung mehr, sondern ein toter Eintrag.

**`docs/Backlog.md:1685-1703 (Nr. 163, Erledigt)`** — „Systemweite Besatzungs-Vorbelegungen ließen sich nicht anlegen" — seit Web 9.10.0 unmöglich, behoben am 09.09.2026 in S9/AP5-4 (Web 17.0.0). Der Nachweisweg `ap5-verwaltung-besatzung-anlegen` „legt einen systemweiten Standort samt Rettungsmittel an, legt die Vorbelegung an, prüft die Landung auf `#crew-<id>` und räumt alles wieder ab".
*Rückbau:* Der Klickprobe-Weg (tools/klickprobe/wege/ap5.mjs) fällt mit dem Rückbau ersatzlos weg; die Sollzahl der Klickprobe sinkt entsprechend (heute 35 Wege je Breite).
*Risiko:* Ein Fehler, der zwei Jahre unbemerkt blieb, wurde am Tag vor der Rückbau-Bestandsaufnahme in einer Funktion behoben, die abgeschafft wird. Das ist kein Schaden, aber es zeigt, wie teuer die fehlende R39-Kenntnis war.

**`docs/Backlog.md:1914-1934 (Nr. 70, Erledigt)`** — „Auf der Karte setzen" für Standorte, erledigt Web 15.8.0 (AP2). Der Nachweis lautet: „Der Dialog öffnet aus **5 von 5** Einbauorten … Standort im Konto (`einstellungen.php`) und **Standort systemweit (`admin_stammdaten.php`)**".
*Rückbau:* Die Sollzahl „5 von 5" sinkt auf 4 von 4. Dieselbe Zahl steht im S9-Konzept (Zeile 29 des Statusblocks) und im S9-Prüfdokument (P-05, Zeile 163).
*Risiko:* Eine Prüfzahl, die nach dem Rückbau falsch ist, ohne dass ein Prüfmittel meckert — die Klickprobe würde schlicht einen Weg weniger fahren. CLAUDE.md 6: „Eine grüne Zahl ist erst dann ein Beleg, wenn sie das Gemessene benennt."

**`docs/Backlog.md:436-446 (Nr. 44)`** — „Sprungliste bei Standorten mit vielen Rettungsmitteln", umgesetzt in S9/AP5 nach E-S9-14/M-S9-05. Der Konzepttext dazu (Konzept-S9:709-710) verortet sie ausdrücklich „*Ort:* die Karte „Rettungsmittel" der Standortseite (E-S9-18), Konto **und Admin**".
*Rückbau:* Die Admin-Hälfte fällt mit `admin_stammdaten.php` weg. Beim Erledigt-Vermerk (AP8) so schreiben, dass die spätere Halbierung nicht als Regression gelesen wird.
*Risiko:* Gering, aber es ist einer von mehreren Punkten, deren Nachweiszahlen den systemweiten Fall mitzählen.

**`docs/Backlog.md:823-833 (Nr. 71)`** — „Regionen mit Unteradmins — verworfen, festgehalten." Beschreibt das Alternativmodell (Regionen am zentralen Standort, Vererbung über E15, `user_regions` n:m, Unteradmin ohne Kontoeinblick, null Regionen = heutiges Verhalten) und begründet die Verwerfung mit R39. Zuordnung: nach v1.0. Steht im **offenen** Teil des Backlogs.
*Rückbau:* Bleibt stehen (Nummern sind dauerhaft). Zu ergänzen wäre: Nach dem Rückbau existiert die Grundlage des Modells nicht mehr; eine Wiederaufnahme wäre ein Neuentwurf, kein Aufgreifen.
*Risiko:* Der Punkt liest sich heute so, als läge das Modell fertig bereit. Nach dem Rückbau ist er das einzige Dokument, das die Zentraleinträge noch als Baustein voraussetzt.

**`docs/Rahmenplan-Archiv.md:1168-1207, insb. 1201-1202`** — Phasentext P5 „Mehrbenutzer und Administration" mit der Erweiterung aus dem Dienstbetriebs-Gespräch; Zeile 1201-1202: „**Rückbau der zentralen Stammdaten** nach **R39**".
*Rückbau:* Bleibt (eingefroren).
*Risiko:* —

**`docs/Rahmenplan-Archiv.md:1441`** — Statusübersicht, Zeile P5: „**Erweitert um R37–R39** (… Rückbau zentrale Stammdaten)".
*Rückbau:* Bleibt (eingefroren).
*Risiko:* —

**`docs/Rahmenplan-Archiv.md:207-215`** — Fassungsvermerk 8 (30.08.2026): R36–R41 aus dem Dienstbetriebs-Konzeptgespräch eingearbeitet, darunter „Wegfall der zentralen Stammdaten (R39)". Belegt das Beschlussdatum.
*Rückbau:* Bleibt (eingefroren).
*Risiko:* Widerlegt die Datumsangabe im S9-Prüfdokument (siehe dort, Zeile 1143): R39 steht seit Fassung 8 / 30.08.2026 im Rahmenplan, nicht seit Fassung 14 / 01.09.2026.

**`docs/Rahmenplan-Archiv.md:424`** — R39 im Volltext — die einzige Stelle im Repositorium mit vollständigem Wortlaut und Begründung. Nennt namentlich: E15-Zentraleinträge mit `user_id IS NULL`, E16-Auswahl, `admin_stammdaten.php`; Rückbau in P5 „einschließlich Doku-Austragung"; eingefrorene Diensttage (E8) ausdrücklich unberührt; Regionen-Modell verworfen, festgehalten als Backlog Nr. 71.
*Rückbau:* Bleibt wörtlich stehen — das Archiv ist nach Rahmenplan.md:3 „wörtlich und eingefroren". Der Erledigt-Vermerk gehört in den aktiven Rahmenplan (Register R39, Abschnitt 8), nicht hierher.
*Risiko:* Wer R39 nur aus dem Register (Rahmenplan.md:1391, eine Zeile) kennt, kennt weder den Umfang noch die Ausnahme E8. Jede Rückbauplanung muss von dieser Zeile ausgehen, nicht von der Kurzfassung.

**`docs/Rahmenplan.md:1191 und 1273`** — Zuordnungszeilen Nr. 44 (Sprungliste, S9/AP5) und Nr. 152 (Standortseiten, S9/AP5, PS-12, E-S9-18/-19). Beide sind noch als offen geführt, obwohl AP5 vollständig ist; beide betreffen ausdrücklich auch die Verwaltungsseite.
*Rückbau:* Nr. 152 wandert mit AP8 nach Erledigt; sein Umfang („Verwaltung → Stammdaten ebenso") wird mit dem Rückbau teilweise wieder abgeräumt. Das gehört in den Erledigt-Vermerk, sonst liest sich die Historie widersprüchlich.
*Risiko:* Ein Punkt, der als erledigt verbucht wird und dessen halbe Wirkung kurz darauf entfernt wird, ohne dass irgendwo steht, dass das beabsichtigt war.

**`docs/Rahmenplan.md:1216 und 1217`** — Backlog-Zuordnung Nr. 69 „Kurzname je Rettungsmittel" steht zweimal mit unterschiedlicher Bemerkung (AP4/AP4a gegen AP4).
*Rückbau:* Entdoppeln. Der Kopf des Abschnitts behauptet: „Jeder offene Punkt steht genau einmal" (Zeile 1153).
*Risiko:* Die Zusage des Abschnitts trifft nicht zu; eine Zählung der offenen Punkte wird falsch.

**`docs/Rahmenplan.md:1219`** — Backlog-Zuordnung: „| 71 | Regionen mit Unteradmins | nach v1.0 | verworfen, festgehalten (R39) |".
*Rückbau:* Bleibt — Nr. 71 ist die konservierte Alternative und **setzt zentrale Stammdaten voraus** (Regionen hängen laut R39-Text „am zentralen Standort und vererben über E15"). Nach dem Rückbau ist Nr. 71 nicht mehr auf dem heutigen Modell umsetzbar; der Punkt müsste bei Wiederaufnahme neu gedacht werden.
*Risiko:* Nr. 71 ist der einzige Backlog-Punkt, der das zurückgebaute Modell fachlich voraussetzt. Wer ihn nach v1.0 aufgreift, findet die Grundlage nicht mehr vor — das sollte im Punkt stehen.

**`docs/Rahmenplan.md:1275-1343 (Abschnitt 6, Offene Abnahmen und Zuarbeiten)`** — Enthält für P5 nur „Hosting-Entscheidung … vor Schritt 10", „Staging-Installation … vor Schritt 10", „GitHub-Umgebung produktion … in P5", „SPF/DKIM/DMARC … vor der P5-Abnahme". Keine Zeile zum Rückbau, keine Zeile zur Mitteilung vom 09.09.2026.
*Rückbau:* Wenn der Rückbau vorgezogen wird, braucht er hier eine Zeile (Freigabe/Entscheidung); bleibt er in P5, gehört die Mitteilung des Auftraggebers als Tatsachenvermerk in den Stand.
*Risiko:* Die beiden P5-Vorbedingungen (Hosting, Staging) sind offen. Solange sie offen sind, ist P5 nicht terminiert — und mit ihm der Rückbau nicht.

**`docs/Rahmenplan.md:1391`** — Register-Kurzzeile: „| R39 | Zentrale Stammdaten entfallen; Regionen-Modell verworfen | gilt, P5; Regionen als Nr. 71 festgehalten |". Der Status lautet **gilt, P5**.
*Rückbau:* Status auf „erledigt" mit Version und Datum umstellen, sobald der Rückbau gebaut ist. Wird der Rückbau vorgezogen, muss hier zusätzlich der neue Ort (S9-Zusatzpaket bzw. eigener Schritt) vermerkt werden — sonst zeigt das Register weiter auf P5.
*Risiko:* Das Register ist der Ort, an dem eine Instanz nach Programmentscheidungen sucht. Steht dort „P5", baut die nächste Instanz weiter an der zentralen Verwaltung — genau so ist S9/AP5-4 entstanden.

**`docs/Rahmenplan.md:1855-1895 (Abschnitt 10, Änderungsverlauf)`** — Doppelt vergebene Fassungsnummern: 35, 36 und 37 kommen je zweimal vor, mit verschiedenen Inhalten (S9/AP1–AP3 gegen Schritt 9a); die letzte Zeile ist Fassung 39 (08.09.2026). Für den 09.09.2026 (AP5 Teile 4–6, Web 17.0.0–17.1.1, Backlog 163–166) gibt es keine Zeile.
*Rückbau:* Nummernvergabe bereinigen und den 09.09. nachtragen, bevor eine Fassung „Rückbau beschlossen" geschrieben wird.
*Risiko:* Die Fassungsnummer ist der Anker, mit dem andere Dokumente auf den Rahmenplan verweisen (z. B. „Rahmenplan Fassung 34"). Doppelte Nummern machen diese Verweise mehrdeutig.

**`docs/Rahmenplan.md:213-215 gegen 216-218`** — Die Fahrplanzeilen für Schritt 8 (S9), 9 (Backlog-Runde) und 9a (Sofortpaket Sicherheit) stehen **doppelt**, mit widersprüchlichem Status: 213 „Umsetzung läuft … AP1 bis AP3 erledigt; AP4 bis AP8 offen" gegen 216 „Umsetzung beauftragt 07.09.2026"; 215 „offen" gegen 218 „gebaut, geprüft und gegengeprüft".
*Rückbau:* Entdoppeln, bevor eine Rückbauzeile in dieselbe Tabelle geschrieben wird.
*Risiko:* Eine Steuerungstabelle mit zwei Wahrheiten je Schritt. Ein Eintrag zum Rückbau würde dieselbe Doppelung erben.

**`docs/Rahmenplan.md:220`** — Fahrplan-Zeile Schritt 10 „P5 — Dienstbetrieb". Inhalt: „Registrierung, Rollen, Administration, Betrieb; Zweitfaktor (Nr. 141) und CSP nach SP-5 (Nr. 8)" — **der Rückbau der zentralen Stammdaten wird hier nicht genannt**. Voraussetzung: „Schritte 2, 5, 7 und 9b; Hosting-Entscheidung; Staging". Status: offen.
*Rückbau:* Der Rückbau gehört in diese Zeile, solange er in P5 bleibt — die Fahrplantabelle ist die Kurzübersicht, in die man zuerst sieht.
*Risiko:* Wer nur den Fahrplan liest (die naheliegende Leseweise), erfährt vom Rückbau nichts. Zusammen mit dem einzeiligen Register erklärt das, warum S9 R39 nicht gefunden hat. Zweitens: **S9 (Schritt 8) steht nicht unter den Voraussetzungen von P5.**

**`docs/Rahmenplan.md:3 und 12-56 (Kopf und Standabsätze)`** — Kopf: „Fassung 37 (07.09.2026)", Stand: „Stand am 06.09.2026, abends: `main` trägt Web 15.5.1 … AP4 wartet auf die Freigabe des Auftraggebers". Tatsächlicher Stand am 09.09.2026: AP1–AP5 gebaut, Web 17.1.1.
*Rückbau:* Vor jeder Rückbauentscheidung den Kopfstand nachziehen — sonst wird gegen einen Stand entschieden, den es nicht mehr gibt (CLAUDE.md 6: „Ein Werkzeug, das vor der letzten Änderung lief, misst einen Stand, den es nicht mehr gibt").
*Risiko:* Der Rahmenplan ist zwei Tage und elf Versionsstufen hinter der Umsetzung. Die Mitteilung des Auftraggebers vom 09.09.2026 (alle systemweiten Standorte gelöscht) steht im Rahmenplan **nirgends** — weder im Stand, noch in Abschnitt 6 (Offene Abnahmen), noch im Änderungsverlauf.

**`docs/Rahmenplan.md:536 (dazu 1427, R73)`** — „P5 setzt S9 nicht voraus, **P6 schon**" — wörtlich auch in R73 (Zeile 1427) und in docs/konzepte/Konzept-Planung-v1.0.md:1198 und :2194.
*Rückbau:* Bleibt sachlich richtig, ist aber die Stelle, die die Reihenfolgefrage beantwortet: P5 ist Schritt 10, S9 Schritt 8 — P5 kommt **nach** S9/AP6–AP8, hängt aber formal nicht daran.
*Risiko:* Die Aussage gilt nur in einer Richtung. Umgekehrt gilt sie nicht: S9/AP5 hat mit `admin_stammdaten.php` gebaut, was P5 abreißt. Diese Rückrichtung ist nirgends geregelt.

**`docs/Rahmenplan.md:575-578`** — Im S9-Absatz steht derselbe Satz zweimal: „Nr. 152 — Fassung 34 nannte hier irrtümlich Nr. 150 …): Der Menüpunkt „Rettungsmittel" entfällt, „Standorte" wird Liste" / „Nr. 152): Der Menüpunkt „Rettungsmittel" entfällt, „Standorte" wird Liste".
*Rückbau:* Entdoppeln.
*Risiko:* Kosmetisch, aber es ist genau der Absatz, der PS-12 beschreibt — die Beauftragung, die zur Verwaltungsseite geführt hat.

**`docs/Rahmenplan.md:785`** — „**AP5 bis AP8 — offen.** AP5 beginnt nach dem Wort des Auftraggebers (K7)." — veraltet. AP5 ist am 09.09.2026 in sechs Teilen vollständig gebaut (Web 16.2.0 bis **17.1.1**), einschließlich Teil 4 „die Dialoge **und die Verwaltungsseite**".
*Rückbau:* Nachziehen. Der Rahmenplan weiß bis heute nicht, dass die zurückzubauende Seite gerade neu gebaut wurde.
*Risiko:* Der Steuerungsstand kennt den Umbau der Seite nicht, die er selbst zum Rückbau vorgesehen hat. Wer die Rückbauentscheidung auf Grundlage des Rahmenplans trifft, unterschätzt den Umfang: `admin_stammdaten.php` ist seit Web 17.0.0 auf Liste, Standortseite, sechs Karten und fünf Dialoge umgebaut.

**`docs/Rahmenplan.md:966`** — Schritt 10 (P5 Dienstbetrieb), Inhaltsaufzählung: „· Rückbau der zentralen Stammdaten (R39) · Servicemodell (R33) ·". Dies ist die **einzige** Stelle im aktiven Rahmenplan, die den Rückbau terminiert.
*Rückbau:* Austragen, sobald der Rückbau anderswo gebaut ist; sonst steht er zweimal im Plan.
*Risiko:* Eine einzige Aufzählungsposition in einem 40-Zeilen-Absatz — leicht zu übersehen. Der Fahrplan-Eintrag zu P5 (Zeile 220) nennt sie nicht (siehe nächster Befund).

**`docs/konzepte/Konzept-Planung-v1.0.md:1198 und 2194`** — Die Reihenfolgeaussage steht auch hier zweimal: „**P5 setzt S9 nicht voraus; P6 schon**", dazu die Schrittnummer-Verschiebung „P5 9 → 10" (1203, 2199). Das Dokument nennt R39 an keiner Stelle, obwohl es die P5-Vorbereitung mitführt (Zeilen 35-37, 57, 64, 184, 818-829).
*Rückbau:* Kein Eingriff nötig; das Dokument ist Festlegungsprotokoll. Beim P5-Konzept ist es aber der Eingang — dort muss R39 auftauchen.
*Risiko:* Die drei Dokumente, die auf P5 vorbereiten (Rahmenplan Schritt 10, Archiv-Phasentext, Planung v1.0), nennen den Rückbau **nur** an je einer Stelle bzw. gar nicht. Die Wahrscheinlichkeit, dass ihn auch das P5-Konzept übersieht, ist damit real.

**`docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md:1002-1062 (AP5), insb. 1015-1017`** — Arbeitspaket AP5: „Verwaltung → Stammdaten dieselbe Liste und Seite"; „Standortliste: Zeilen als Verweise mit Artzeichen, drei Zahlen, Stern, **Plakette „systemweit"**, Winkel". Dazu Zeile 868 (Übersichtstabelle „Wo es hinkommt"): „Einstellungen → Standorte; **Verwaltung → Stammdaten**".
*Rückbau:* Plakette „systemweit", Stern der Vorbelegung an systemweiten Zeilen und der ganze Admin-Zweig entfallen. Die Karte `zentrale` (Auswahl vordefinierter Standorte, E16) entfällt aus der Kontoliste.
*Risiko:* Die Kontoliste zählt heute „3 Karten / 3 Unterpunkte (`standorte`, `zentrale`, `sd-ohne`)" (Konzept Zeile 1420, Prüfdokument Zeile 346). Nach dem Rückbau sind es zwei. Wer diese Zahl nicht mitzieht, hat einen Prüfstand, der gegen ein falsches Soll misst.

**`docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md:158-163 („Nicht Umfang")`** — Die Abgrenzungsliste des Konzepts nennt ausdrücklich, was P5 gehört: „Selbstbetrieb eines Geocoders (R36, vor P5)", „die Support-Rolle und alles Weitere aus **R38** (P5)". **R39 fehlt in dieser Liste.**
*Rückbau:* Wäre R39 hier gestanden, hätte AP5 die Verwaltungsseite nicht angefasst. Die Liste ist der Ort, an dem die Kollision hätte auffallen müssen.
*Risiko:* Das Konzept hat die P5-Abgrenzung bewusst geführt und dabei zwei von drei einschlägigen R-Nummern erfasst. Die dritte, die genau seinen Gegenstand betrifft, fehlt — das ist kein Zufall, sondern eine Folge des einzeiligen Registereintrags (Rahmenplan.md:1391).

**`docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md:34 (Statusblock, Nachtrag 09.09.2026)`** — „**Nachtrag 09.09.2026:** Backlog Nr. 166 schlug vor, dem Referenzbestand einen systemweiten Standort hinzuzufügen — **zurückgezogen**, weil **R39** (30.08.2026) die zentralen Stammdaten abschafft und in P5 zurückbaut. **Weder dieses Konzept noch das Prüfdokument nannten R39**; siehe Prüfdokument, Frage 10." Dies ist die **einzige** R39-Nennung im gesamten S9-Konzept, und sie ist ein Nachtrag der Umsetzung, nicht des Konzepts.
*Rückbau:* Bleibt als Beleg. Beim Abschluss (AP8, Erledigt-Zeile Rahmenplan Abschnitt 8) muss dieser Befund in den Rahmenplan wandern — sonst geht er mit dem Konzept unter, das nach R62 gelöscht wird.
*Risiko:* **Antwort auf die Frage, ob S9 R39 zur Kenntnis genommen hat: nein.** Das Konzept ist vom 06./07.09.2026, R39 vom 30.08.2026 — acht Tage älter. R39 taucht in Konzept und Prüfdokument erst am 09.09.2026 auf, nachdem AP5 Teil 4 die Verwaltungsseite bereits umgebaut hatte (Web 17.0.0).

**`docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md:543-566 (E-S9-06 c), insb. 556-557 und 563-566`** — Der gemeinsame Kartendialog: „damit Zielklinik und Standort in den Stammdaten (`einstellungen.php`, **`admin_stammdaten.php`**) … dieselbe Karte bekommen"; „*Ort:* … an fünf Stellen: Einsatzort, manueller Abfahrtort, Transportziel, Zielklinik-Stammdaten (Konto **und Admin**), Standort-Stammdaten (Konto **und Admin**)".
*Rückbau:* Die Admin-Einbauorte entfallen; die Zahl „fünf Einbauorte" wird vier.
*Risiko:* Prüfpunkt P-05 des S9-Prüfdokuments misst genau diese fünf (siehe dort, Zeile 163). Nach dem Rückbau ist das Soll falsch, ohne dass es auffällt.

**`docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md:590-637 (E-S9-09), insb. 611 und 634`** — „Rettungsmittel bekommen einen Typ und einen Kurznamen." Zeile 611: „ein Rettungsmittel ohne Standort hat keine Vorschlagslisten (**E15** — die hängen am Standort)". Zeile 633-634: „*Ort:* Einstellungen → Standorte → Standortseite (Konto) **und Verwaltung → Stammdaten (Admin)**".
*Rückbau:* Datenmodellseitig unberührt (`vehicles.typ`, `vehicles.kurz`, `base_id` NULL-fähig bleiben). Nur der Admin-Ort entfällt. Wichtig: Der Rahmenplan-Text zu AP4 (Rahmenplan.md:739) begründet die Hauptnummer 16.0.0 unter anderem mit „eine feste Zusage (E15)" — E15 bleibt gültig, R39 trifft nur E15s Zentralteil.
*Risiko:* E15 wird in S9 zweimal als tragende Begründung zitiert (611, 748). Wer beim Rückbau E15 mit „zentrale Stammdaten" verwechselt, reißt die Standortbindung mit ein — die bleibt.

**`docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md:712-756 (E-S9-18)`** — „Standort zuerst: eine Liste, eine Seite je Standort." Zeile 717: die Standortliste zeigt „**„systemweit" als Plakette**". Zeile 746: „**Verwaltung → Stammdaten** (Admin) bekommt dieselbe Liste und Seite (`sd_zeile()`)." Zeile 748: Begründung „Alles hängt am Standort (`crew_presets.base_id`, `transport_dests.base_id`, **E15**)". Zeile 754-756: „*Ort:* Einstellungen → Standorte (Konto), **Verwaltung → Stammdaten (Admin)**".
*Rückbau:* Der gesamte Admin-Zweig dieser Entscheidung entfällt: Plakette „systemweit", die zweite Ausprägung von Liste und Standortseite, die Verwendung von `sd_zeile()` in `admin_stammdaten.php`. E15 selbst (Standortbezug der Untertypen) bleibt — R39 hebt nur den **Zentraleintrag** auf, nicht die Standortbindung.
*Risiko:* **Die Kernstelle des Widerspruchs.** Eine am 07.09.2026 freigegebene Entscheidung baut die Seite aus, die R39 (30.08.2026) abschafft. E-S9-18 nennt R39 nicht, prüft es nicht und wägt es nicht ab.

**`docs/konzepte/Konzept-S9-Einsatzbearbeitung-Rettungsmittel.md:757-793 (E-S9-19), insb. 775`** — „Anlegen und Bearbeiten im Dialog, Landung auf der neuen Zeile." Zeile 775: „**Gilt für Verwaltung → Stammdaten ebenso.**" Umgesetzt in AP5 Teil 4 als fünf Dialoge (Web 17.0.0).
*Rückbau:* Die Dialoge der Verwaltungsseite entfallen mit ihr. Die Konto-Dialoge bleiben.
*Risiko:* Der Statusblock (Zeile 21) begründet ausdrücklich, warum die Verwaltung in Teil 4 gerückt wurde: „Sie benutzte `sd_form()` an acht Stellen … wer sie früher umgebaut hätte, hätte sie zweimal gebaut." Die Überlegung ist richtig — sie ist nur an einer Seite angestellt worden, die abgerissen wird.

**`docs/konzepte/Pruefdokument-S8-Einstellungen-Administration-Wartung.md:241`** — Wartungsmodus-Prüfung: „**Fünf Seiten 200** …, **neun Seiten 503** (Komplett-Backup, Backup-Ziele, NutzerInnen, Konto-Backups, Installation, Demo-Konto, **Stammdaten systemweit**, Startseite, Profil)".
*Rückbau:* Die Zahl neun wird acht. Das Prüfdokument S8 bleibt, bis seine Prüfliste abgehakt ist (R62) — die Zahl ist also noch in Gebrauch.
*Risiko:* Eine belegte Zahl in einem noch gültigen Prüfdokument, die der Rückbau still falsch macht.

**`docs/konzepte/Pruefdokument-S9-Einsatzbearbeitung-Rettungsmittel.md:1030-1046 (Prüfliste Punkt 31)`** — „~~Die Standortseite der Verwaltung an einem echten Bestand~~ — **ENTFÄLLT (09.09.2026)**", abgehakt, mit R39-Begründung. Ersatz: die Entscheidung zu Frage 10.
*Rückbau:* Bleibt. Der einzige Prüflistenpunkt, der bereits nach R39 entschieden wurde.
*Risiko:* —

**`docs/konzepte/Pruefdokument-S9-Einsatzbearbeitung-Rettungsmittel.md:1133-1147`** — Die ausdrückliche Selbstfeststellung: „Weder das S9-Konzept noch dieses Prüfdokument nennen R39 an einer einzigen Stelle (`grep -c "R39" docs/konzepte/` = **0**), und ich habe den Rahmenplan vor AP5-4 nicht gelesen, obwohl `CLAUDE.md` ihn als Ort der Programmentscheidungen benennt." **Enthält einen Datumsfehler:** „im Rahmenplan seit Fassung 14 (01.09.2026)" (Zeile 1143).
*Rückbau:* Datum berichtigen: R39 kam mit **Fassung 8, 30.08.2026** (Rahmenplan.md:1861 „| 8 | 30.08.2026 | Dienstbetriebs-Gespräch: R36–R41 …"; Rahmenplan-Archiv.md:207-215). Der Befund selbst bleibt und gehört beim S9-Abschluss in den Rahmenplan.
*Risiko:* Ein Befund über eine übersehene Entscheidung, der das Datum dieser Entscheidung falsch angibt, schwächt genau die Lehre, die er ziehen will. Die drei fehlenden Tage sind nicht egal: Fassung 8 liegt **vor** der Problemsammlung (03.09.2026), aus der S9 entstanden ist.

**`docs/konzepte/Pruefdokument-S9-Einsatzbearbeitung-Rettungsmittel.md:1149-1162 (Fragen 8 und 9)`** — Frage 8 („Geht ein fremdes Rettungsmittel mit, wenn die Verwaltung einen systemweiten Standort löscht?") ist „erledigt, nicht zu entscheiden" — der Fall kann nicht mehr eintreten. Frage 9 („Bekommt der Referenzbestand einen systemweiten Standort?") — Empfehlung war „ja", richtig ist „nein"; Backlog Nr. 166 entsprechend zurückgezogen.
*Rückbau:* Beide bleiben als Beleg stehen. Der in AP5-5 gebaute Zuschnitt (Rettungsmittel ohne Standortpflicht überleben das Löschen ihres Standorts) bleibt für den Kontofall gültig und „verschwindet mit dem Rückbau" nur in seiner Admin-Hälfte.
*Risiko:* —

**`docs/konzepte/Pruefdokument-S9-Einsatzbearbeitung-Rettungsmittel.md:1167-1183 (Frage 10)`** — „**Wird der Rückbau nach R39 vorgezogen, oder bleibt er in P5?**" Drei Wege: **(a)** wie geplant in P5; **(b)** jetzt als eigenes Arbeitspaket in S9 (AP5b oder nach AP8) mit eigenem Konzept — „der Rückbau berührt Schema, sechs Tabellen, `user_bases`, die Sicherungsformate und die Dokumentation und ist kein Nebenbei"; **(c)** nur die Anzeige stilllegen (Karte „Vordefinierte Standorte" und den Verweis auf `admin_stammdaten.php` ausblenden, solange kein zentraler Eintrag existiert), Schema erst in P5. Eine Empfehlung ist ausdrücklich der Bestandsaufnahme vorbehalten.
*Rückbau:* Das ist die offene Entscheidung, auf die diese Bestandsaufnahme zuarbeitet. Die Aufzählung des Umfangs in (b) ist die bislang einzige Umfangsschätzung im Repositorium.
*Risiko:* Solange Frage 10 offen ist, baut jedes weitere S9-Paket (AP6, AP7, AP8) potenziell an derselben Stelle weiter — AP6 (Tageszuordnung) und AP7 (Verschlüsselung) fassen die Stammdatenwege mit an.

**`docs/konzepte/Pruefdokument-S9-Einsatzbearbeitung-Rettungsmittel.md:163 (P-05), 346, 381, 391, 403, 684`** — Prüfzahlen und Leseorte, die den systemweiten Fall mitzählen: P-05 „5 von 5 Einbauorten … Standort systemweit (Rolle `admin`)"; Zeile 346 „Liste 3 Karten / 3 Unterpunkte (`standorte`, **`zentrale`**, `sd-ohne`)"; Zeilen 381/403 „Lesen (`server/einstellungen.php`, **`server/admin_stammdaten.php`**)"; Zeile 391 Backlog Nr. 163; Zeile 684 „Vier Aufrufe setzen `'such' => true` (**`admin_stammdaten.php:504, 724`**)".
*Rückbau:* Jede dieser Zahlen sinkt. Sie stehen in einem Dokument, das nach R62 bestehen bleibt, bis die Prüfliste abgehakt ist — der Rückbau muss sie mitziehen oder das Dokument als historisch kennzeichnen.
*Risiko:* Prüfzahlen, die den zurückgebauten Fall mitzählen, sind nach dem Rückbau keine Belege mehr, sehen aber wie welche aus.

**`docs/konzepte/Pruefdokument-S9-Einsatzbearbeitung-Rettungsmittel.md:17 und 19 (Statusblock)`** — Statusblock steht auf dem 08.09.2026: „AP5 in Arbeit … Teile 4 bis 6 offen", „Offen: P-17 bis P-22, P-26, P-28 bis P-31, P-33". Der Rumpf des Dokuments führt die Teile 4, 5 und 6 als erledigt (Web 17.0.0 / 17.1.0 / 17.1.1) und Punkt 31 als entfallen.
*Rückbau:* Statusblock nachziehen, bevor Frage 10 entschieden wird — sonst entscheidet man gegen den Kopf statt gegen den Inhalt.
*Risiko:* Dasselbe Muster wie beim Rahmenplan: Der Kopf, in den zuerst gesehen wird, ist älter als der Inhalt.

**`docs/konzepte/Pruefdokument-S9-Einsatzbearbeitung-Rettungsmittel.md:37-56 (Abschnitt 0)`** — „Die Standortseite der Verwaltung ist nicht fotografiert — und bleibt es." Der Referenzbestand hat `bases` mit `user_id IS NULL` **leer**; der Bilderlauf meldet „OHNE BILD, 8× Platzhalter `__ADMIN_STANDORT__` nicht auflösbar". Ausdrücklich: „Der Eintrag in `tools/screenshots/seiten.json` fällt mit dem Rückbau weg."
*Rückbau:* Beim Rückbau: `42a-stammdaten-standortseite` (seiten.json:219-222) und `42-stammdaten-systemweit` (seiten.json:213-216) austragen; Sollzahl der fotografierten Seiten anpassen.
*Risiko:* Zwei tote Prüfmitteleinträge, die nach dem Rückbau eine Meldung erzeugen, die niemand mehr deuten kann.

**`docs/konzepte/Pruefdokument-S9-Einsatzbearbeitung-Rettungsmittel.md:415-427`** — Offen gebliebener Zuschnitt: „Ein Rettungsmittel, das einer **NutzerIn** gehört und an einem **systemweiten** Standort hängt, geht weiterhin mit, wenn die Verwaltung diesen Standort löscht — auch wenn sein Typ keinen Standort braucht." Begründet mit dem Zählumfang der Admin-Rückfrage.
*Rückbau:* Wird mit dem Rückbau gegenstandslos (Frage 8 ist deshalb bereits als hinfällig verbucht). Umgekehrt: Solange das Modell steht, ist das ein realer Datenverlustpfad.
*Risiko:* Falls der Rückbau lange dauert und doch noch jemand einen systemweiten Standort anlegt, kann dessen Löschung fremde Rettungsmittel mitnehmen. Auf dem Produktivsystem ist das derzeit ausgeschlossen (keine systemweiten Standorte).

**`docs/konzepte/Pruefdokument-S9-Einsatzbearbeitung-Rettungsmittel.md:989-999 (Prüfliste Punkt 28)`** — „**28 — Die Verwaltungsseite ist noch die alte (AP5, bekannter Rest).** *Erwartet:* Sie sieht aus wie bisher — zwei Reiter „Standorte" und „Rettungsmittel" …" — nicht abgehakt und seit Web 17.0.0 sachlich überholt (die Seite ist umgebaut).
*Rückbau:* Berichtigen oder streichen. Wenn der Rückbau kommt, entfällt der Punkt ganz.
*Risiko:* Ein offener Prüflistenpunkt, der den Auftraggeber auffordert, einen Zustand zu bestätigen, den es seit zwei Versionen nicht mehr gibt.

**`docs/konzepte/Pruefdokument-Sofortpaket-Sicherheit.md:112`** — Bilderlauf-Seitenliste des Sofortpakets nennt `42-stammdaten-systemweit` unter den geprüften Seiten.
*Rückbau:* Mit dem Eintrag in `tools/screenshots/seiten.json` austragen.
*Risiko:* Gering; gehört zur selben Aufräumliste wie Nr. 166.

**`docs/konzepte/erledigt/Konzept-P2-Terminologie.md:385, 395, 856; docs/konzepte/erledigt/Pruefdokument-P2-Terminologie.md:142, 214`** — P2 hat die Begriffe der systemweiten Stammdaten vereinheitlicht (Platzhalter W6, „Administration → Rettungsmittel systemweit").
*Rückbau:* Nichts zu tun (eingefroren).
*Risiko:* —

**`docs/konzepte/erledigt/Konzept-P3-Oberflaeche.md:2725-2741, 688, 499, 1398`** — P3 hat die systemweiten Stammdaten gestaltet: „Stammdaten systemweit: ein Punkt, zwei Reiter" (2725ff), Plakette „systemweit" (499, 688), Dublettenhinweis für systemweite Standorte (F-P3-AO, 1398).
*Rückbau:* Nichts zu tun — `docs/konzepte/erledigt/` wird nach R62 nicht mehr fortgeschrieben. Nur als Herkunftsnachweis relevant: Die Gestaltung der Seite ist P3-Erbe, nicht S9-Erfindung.
*Risiko:* Keines für den Rückbau; wichtig ist nur, das erledigt-Verzeichnis **nicht** anzufassen, sonst wird eine eingefrorene Historie verändert.

### F — Prüfmittel und Referenzbestand

25 Befunde (code 2, daten 6, doku 3, pruefmittel 14).

**`docs/Backlog.md:1665-1682 gegen tools/screenshots/seiten.json:218-223`** — BEURTEILUNG DES VORSCHLAGS Nr. 166: Er ist im Repositorium bereits am 09.09.2026 — am Tag seiner Aufnahme — ZURÜCKGEZOGEN worden, mit genau der hier verlangten Begründung (R39, Rückbau in P5, auf dem Produktivsystem bereits gelöscht). Die Bestandsaufnahme bestätigt das: Dem Generator einen systemweiten Standort hinzuzufügen hieße, in `quelldaten/stammdaten.json` ein Feld zu erfinden, das es nicht gibt, und in `einspielen.py` einen zweiten Schreibweg über `admin_stammdaten.php` zu bauen — beides in ein Modell hinein, das im selben Zug zurückgebaut wird.
*Rückbau:* Nichts hinzufügen. Was Nr. 166 als bleibende Folge festhält, ist umzusetzen: Der Eintrag `42a-stammdaten-standortseite` fällt mit dem Rückbau weg — und, was dort NICHT steht: `42-stammdaten-systemweit` fällt mit ihm, denn die Übersichtsseite verschwindet ebenso. Die Klickprobe deckt die Seite bis dahin ab (`ap5-verwaltung-besatzung-anlegen`), und auch diese Abdeckung endet mit dem Rückbau.
*Risiko:* Der Vorschlag umzusetzen wäre Arbeit in die falsche Richtung — und schlimmer als nutzlos: Ein Referenzbestand MIT systemweitem Standort würde den Rückbau selbst verdecken, weil die Prüfmittel dann grün auf einer Bauform stünden, die verschwinden soll. Umgekehrt ist Nr. 166 unvollständig, solange sie nur `42a` nennt und `42` verschweigt.

**`tools/ — übrige Proben (wartungsprobe, freigabeprobe, integritaetswache, komplettprobe, pruefkonten, messstand, stilvergleich, containerprobe, jobprobe, versandprobe, wiederherstellungs-probe, spurprobe, gpxprobe, ingestprobe, geraeteprobe, kopplungsprobe, netzprobe, fristprobe, maskierungs-probe, abmelde-probe, design, s5-anker, uhr-*)`** — GEMESSEN, kein Befund: Ein Suchlauf über `tools/` nach `admin_stammdaten`, `systemweit`, `user_bases`, `zentral`, `bases`, `crew_presets`, `transport_dests`, `bw_units` trifft außerhalb der oben genannten Stellen nur Fehlalarme (`watch/resources`, `global $var`, „zentrales Verzeichnis" im Containerformat). `wartungsprobe/probe.php` und `integritaetswache/wache.py` führen feste Seitenlisten, in denen `admin_stammdaten.php` nicht vorkommt; `stilvergleich/proben.py:65` globt `server/*.php` und zieht von selbst nach.
*Rückbau:* Nichts zu streichen. Diese Werkzeuge sind vom Rückbau nicht berührt.
*Risiko:* Keines. Die Vollständigkeit dieser Aussage hängt daran, dass die Suche die Tabellennamen und nicht nur die Seitennamen abgedeckt hat — beides ist gelaufen.

**`tools/klickprobe/ausgabe/bericht.md:41 und :76 (gitignored)`** — MESSBELEG zur Gegenrichtung: `ap5-verwaltung-besatzung-anlegen` ist im letzten Lauf bei 390 px und bei 1280 px ERFÜLLT — er hat den systemweiten Standort (s=64 bzw. s=66) selbst angelegt, die Vorbelegung geschrieben (#crew-301/#crew-302) und wieder abgeräumt. Der Weg funktioniert also; er verliert mit P5 nicht seine Richtigkeit, sondern seinen Gegenstand.
*Rückbau:* Nichts am Ausgabeverzeichnis (.gitignore:84). Der Weg selbst ist zu streichen (siehe Befund zu ap5.mjs:560-637).
*Risiko:* Keines. Wichtig für die Entscheidung: Der Weg wird nicht gestrichen, weil er kaputt ist, sondern weil die Sache verschwindet — das gehört so in die CHANGELOG-Begründung, sonst liest es sich später wie das Wegräumen eines lästigen roten Prüffalls.

**`tools/klickprobe/probe.mjs:302-312`** — Die Handreichung `k.rolle(name)` im Werkzeugkasten. Ihr Docblock begründet ihre Existenz ausschließlich mit den zentralen Stammdaten: „AP2 braucht das, weil DERSELBE Kartendialog an fuenf Stellen sitzt und die fuenfte — die systemweiten Standorte — nur der Verwaltung offensteht." Einziger Aufrufer ist ap2.mjs:143.
*Rückbau:* Nach dem Streichen des fünften Einbauorts hat `k.rolle()` keinen Aufrufer mehr. Entweder mit Begründung stehen lassen (sie ist billig und ein späteres Paket kann sie brauchen) — dann muss der Docblock umgeschrieben werden — oder mitstreichen. Die Anmeldung als `admin` bleibt in beiden Fällen nötig: `k.schalterInstallation()` (probe.mjs:359-378) holt sich die Rolle selbst über `rolleHolen('admin')`.
*Risiko:* Ein Docblock, der eine Bauform mit einer Seite begründet, die es nicht mehr gibt, ist eine falsche Erklärung an einer Stelle, an der die nächste Instanz Vertrauen schöpft. Wer ihn liest, sucht die fünfte Stelle.

**`tools/klickprobe/wege/ap2.mjs:135-138`** — Der Weg heißt `ap2-dialog-fuenf-einbauorte`, sein Klartext lautet „Der Kartendialog öffnet aus allen fünf Einbauorten", und `soll: '5 von 5'` ist als Zeichenkette fest eingetragen (Zeile 138) — anders als das Ist, das aus EINBAUORTE.length gerechnet wird.
*Rückbau:* Name auf `ap2-dialog-vier-einbauorte`, Text auf „vier Einbauorten", `soll` auf '4 von 4'. Ebenso der Kopfkommentar der Datei (Zeile 4: „Dialog aus fuenf Einbauorten  Soll 5 von 5").
*Risiko:* Ein fest verdrahteter Sollwert, der nicht mitgezogen wird, macht jeden künftigen Lauf rot; ein Name, der weiter „fuenf" sagt, führt beim nächsten Lesen in die Irre und lädt dazu ein, den fünften Ort „wiederherzustellen".

**`tools/klickprobe/wege/ap2.mjs:78-86`** — Die Liste EINBAUORTE hat fünf Einträge; der fünfte ist „Standort (systemweit)" mit rolle: 'admin' und Präfix 'adbase' und öffnet k.basis + '/admin_stammdaten.php' (Zeile 83-85). Das ist die einzige Stelle der Klickprobe, an der der Kartendialog aus der zentralen Stammdatenpflege geöffnet wird.
*Rückbau:* Den fünften Eintrag (Zeilen 82-85) ersatzlos streichen. Die Zählung im Weg selbst (`ist: auf von EINBAUORTE.length`) zieht dann von allein auf 4 nach.
*Risiko:* Bleibt der Eintrag stehen, meldet der Weg nach dem Rückbau dauerhaft „4 von 5" mit der Bemerkung „Standort (systemweit): kein Pin-Knopf" — ein roter Lauf, der wie ein Fehler der Anwendung aussieht und keiner ist. Wird er dagegen gestrichen, ohne den Sollwert unten anzupassen, meldet der Weg „4 von 4" gegen ein Soll „5 von 5".

**`tools/klickprobe/wege/ap5.mjs:560-637`** — Der ganze Weg `ap5-verwaltung-besatzung-anlegen` (Paket AP5, Prüfpunkt Backlog Nr. 163, rolle: 'admin'). Er stellt den Fall selbst her: Er legt über `admin_stammdaten.php?t=standorte` (Zeile 571) einen systemweiten Standort „KP Verwaltungsstandort" an, hängt Rettungsmittel und Besatzungs-Vorbelegung daran, prüft Landung auf `#crew-<id>` und die orange-helle Zeile und räumt im `finally` über `form[id^="f-adbdel-"]` (Zeile 630) wieder ab.
*Rückbau:* Der Weg entfällt vollständig (Zeilen 560-637) samt dem erklärenden Block darüber. Mit ihm entfällt der einzige maschinelle Nachweis für Backlog Nr. 163 — die dort behobene Sache (`role_code` gegen `role`) verschwindet mit der Seite, der Nachweis also mit dem Gegenstand.
*Risiko:* Bleibt er stehen, scheitert er nach dem Rückbau schon am ersten `k.gehZu` (404) und meldet „nicht gefahren" — was die Klickprobe ausdrücklich als VERFEHLT wertet (probe.mjs:49-52). Der Lauf gäbe dann dauerhaft 1 zurück, und ein rotes Prüfmittel, das aus Gewohnheit rot ist, hat aufgehört zu messen.

**`tools/linkprobe/ausnahmen.md:24-26 und :34-36`** — GEPRÜFT UND NICHTS GEFUNDEN: Weder die Tabelle „Ausnahmen" (leer) noch „Bekannte Abweichungen" (eine Zeile, `index.php?day=`, Nr. 151) enthält einen Verweis auf `admin_stammdaten.php`. Die Linkprobe selbst liest `server/` per os.walk und führt keine Seitenliste.
*Rückbau:* Nichts zu streichen. Die Probe zieht von selbst nach; die Zahlen in LIESMICH.md:97-99 („99 Zielseiten und 132 Verweise") sind nach dem Rückbau neu zu messen und dort einzutragen.
*Risiko:* Keines am Werkzeug. Nur: Bringt der Rückbau neue Verweise mit falschem Parameternamen, findet die Probe sie — sie gehört deshalb in den Prüflauf des Pakets, nicht danach.

**`tools/linkprobe/probe.py:32-36 und tools/linkprobe/LIESMICH.md:79-86`** — Beide nennen als EINZIGES Beispiel für die Grenze „ein Verweis, dessen Ziel erst zur Laufzeit entsteht" den Fall `admin_stammdaten.php:571` baut `$seite . '&ev=' . $vid` — mit dem Vermerk, dass er von Hand nachgesehen und richtig ist.
*Rückbau:* Die Grenze bleibt bestehen (sie ist eine Eigenschaft des statischen Abgleichs), das Beispiel entfällt. Entweder durch einen dann noch vorhandenen Fall ersetzen oder ausdrücklich schreiben, dass es aktuell keinen gibt — nicht stillschweigend löschen, sonst liest sich die Grenze wie eine theoretische.
*Risiko:* Ein Prüfmittel, das seine Grenze mit einer Datei belegt, die es nicht mehr gibt, macht die Grenze unglaubwürdig. Wer sie prüfen will, findet nichts und hält sie für erledigt.

**`tools/referenzdatensatz/einspielen/einspielen.py:120-176`** — Beleg dazu: `stufe_stammdaten()` schickt JEDEN Stammdatensatz (base_save, veh_save, crew_save, td_save, bw_save, res_save) über `s.post("einstellungen.php?t=standorte", …)` in der Sitzung des Demo-Kontos (Zeile 128-129). Es gibt im gesamten Verzeichnis `tools/referenzdatensatz/` keinen einzigen POST an `admin_stammdaten.php`.
*Rückbau:* Nichts zu streichen. Alles, was der Bestand anlegt, trägt zwangsläufig `user_id = <Demo-Konto>`; ein Eintrag mit `user_id IS NULL` kann auf diesem Weg gar nicht entstehen.
*Risiko:* Keines.

**`tools/referenzdatensatz/einspielen/einspielen.py:185-191 und :222-223`** — Die einzige Stelle des Referenzbestands, die zentrale Einträge überhaupt vorsieht — als Toleranz beim Lesen: Der Docstring von `kennungen()` begründet den Weg über `diensttag_neu.php` damit, dass die Seite „genau das listet, was die Anwendung dem Konto anbietet — einschliesslich zentraler Eintraege und der Standortbindung", und Zeile 223 schneidet den Zusatz „ (zentral)" vom Optionstext ab (`x.replace(" (zentral)", "")`). Der Zusatz entsteht in `server/diensttag_neu.php:84`.
*Rückbau:* Zeile 222-223 auf `standorte = lesen("base_id", lambda x: x.strip())` zurückbauen und den Halbsatz „einschliesslich zentraler Eintraege" aus dem Docstring nehmen — im selben Paket, in dem `diensttag_neu.php:84` den Zusatz nicht mehr schreibt.
*Risiko:* Kein Bruch: Das `replace` läuft nach dem Rückbau ins Leere und tut nichts. Es bleibt aber toter Code, der behauptet, es gebe zentrale Einträge — und die nächste Instanz, die den Referenzbestand liest, glaubt es. Genau das ist die Sorte Rest, die R39 austragen will.

**`tools/referenzdatensatz/fixture/erzeugen.php:40-50`** — Die Demo-Fixture entsteht aus `edbak_build()` für GENAU EIN Konto (`SELECT … FROM users WHERE email = ?`, Rolle muss 'user' sein). Sie kann definitionsgemäß keine Sätze mit `user_id IS NULL` enthalten. Sie führt aber die Nutzlast des Backups mit — und damit den Schlüssel `stammdaten.user_bases` (server/backup_lib.php:731).
*Rückbau:* Am Werkzeug nichts. Beim Rückbau der Nutzlast ist die Fixture jedoch NEU ZU ERZEUGEN, sonst trägt `server/demo/fixture.json.gz` einen Schlüssel, den `edbak_restore()` nicht mehr kennt.
*Risiko:* Eine nicht neu erzeugte Fixture ist der stillste denkbare Fehler: Das Demo-Konto wird bei jeder Anfrage aus ihr zurückgesetzt (demo_lib.php). Bricht das Einspielen an einem unbekannten Schlüssel, ist das Demo-Konto kaputt, und kein Prüfmittel dieser Fläche zeigt darauf.

**`tools/referenzdatensatz/quelldaten/matrix_abgleich.md:15-40`** — Die erzeugte Abdeckungsmatrix (16 Dienste, 87 Einsätze, 83 Matrixzeilen, 5913 Einzelprüfungen) kennt KEINE Dimension und keine Zeile für zentrale oder systemweite Stammdaten. Auch `quelldaten/katalog.py` und `quelldaten/FORMAT.md` erwähnen sie nicht.
*Rückbau:* Nichts zu streichen. Die Abdeckung des Referenzbestands sinkt durch den Rückbau um Null — es gab nie eine Matrixzeile, die zentrale Einträge verlangt hätte.
*Risiko:* Keines. Der Befund entkräftet zugleich das naheliegende Gegenargument gegen R39 („dann fehlt dem Bestand etwas").

**`tools/referenzdatensatz/quelldaten/stammdaten.json:17-32`** — KERNANTWORT ZUR FRAGE NACH DEM GENERATOR: Der Referenzbestand kennt genau zwei Standorte („Luftrettungsstation Hochkreuth" mit Koordinaten, „Notarztstandort Talwang" ohne), beide dem Demo-Konto zugeordnet. Es gibt in der ganzen Quelldatei kein Feld, keinen Schalter und keinen Kommentar für einen systemweiten Eintrag; die Begleitkommentare (Zeilen 13-15) verankern jeden Satz an genau einem Standort des Kontos.
*Rückbau:* Nichts zu streichen. Der Referenzbestand enthält KEINE zentralen Einträge — weder in den Quelldaten noch im Ergebnis.
*Risiko:* Keines. Die Gegenprobe ist wichtig, weil eine Behauptung „die Datenbank hat keine" allein nichts wert wäre: Hier ist es der Generator, der keine anlegen KANN.

**`tools/referenzdatensatz/referenz/einsatzdoku-backup-2026-09-07.edbak und referenz/altformat/einsatzdoku-backup-2026-08-24.edbak`** — Die beiden eingefrorenen Referenzdateien tragen die Nutzlast MIT dem Schlüssel `stammdaten.user_bases` (server/backup_lib.php:618-624 und :731 — „Auswahl zentraler Standorte (E16). Als NAME, nicht als Kennung"). Für das Demo-Konto ist die Liste leer, der Schlüssel steht aber da. Der Umlaufvergleich `vergleichen.py` prüft alles außerhalb der drei Tabellen mit `baum()` (Zeile 275-278), also Schlüssel für Schlüssel.
*Rückbau:* Entfällt `user_bases` mit P5 aus der Nutzlast, meldet der Kreislauf `--art edbak` eine Abweichung „kopf.stammdaten.user_bases fehlt". Zwei Wege: entweder eine Regel in `vergleich/ausnahmen/edbak_umlauf.json` mit gemessener Zahl und Begründung nachtragen — oder die Referenzdateien neu erzeugen (`tools/referenzdatensatz/browser/referenz_export.mjs`, Protokoll in `referenz/.export_lauf.json`). Für `referenz/altformat/` gilt nur der erste Weg: Sie ist ausdrücklich eingefroren.
*Risiko:* Wird beides vergessen, gibt `kreislauf.py --art edbak` 1 zurück, und der Rückbau sieht aus, als hätte er den Backup-Kreislauf zerstört. Umgekehrt gefährlicher: Wer die Abweichung mit einer Sammelregel wegdrückt, statt sie zu benennen, macht den Vergleich für den nächsten Formatschritt blind.

**`tools/referenzdatensatz/vergleich/ausnahmen/edbak_umlauf.json (eine einzige Regel) und edbak-alt_umlauf.json:5-11`** — `edbak_umlauf.json` führt genau EINE erwartete Abweichung (days[].refs[].device_id) — jede weitere gilt als unerklärt. `edbak-alt_umlauf.json` trägt zusätzlich die Regel `kopf.version: von 7 nach 10` mit der Begründung, die Altformat-Referenz bleibe eingefroren.
*Rückbau:* Steigt die Nutzlastfassung mit dem Rückbau von 10 auf 11, muss die Regel in `edbak-alt_umlauf.json` auf „nach 11" nachgezogen werden; die Beschreibungstexte beider Listen nennen gemessene Zahlen (287 771 bzw. 287 781 Einzelvergleiche, 653 Abweichungen), die neu zu messen und einzutragen sind.
*Risiko:* Eine nicht nachgezogene Versionsregel macht den formatübergreifenden Lauf rot an der einen Stelle, an der er grün sein soll — und die Zahl in der Beschreibung wird zur Behauptung ohne Messung, also genau zu dem, was CLAUDE.md 6 („Eine Prüfung ohne Zahl ist keine Prüfung") ausschließt.

**`tools/screenshots/aufnehmen.mjs:335-343`** — Die Auflösung des Platzhalters `__ADMIN_STANDORT__`: Der Lauf geht als admin auf `admin_stammdaten.php?t=standorte` (Zeile 339-340), liest den ersten `a.zeile[href*="t=standort&s="]` und setzt `p['__ADMIN_STANDORT__'] = aHref || null`.
*Rückbau:* Block 335-343 ersatzlos streichen, zusammen mit dem Eintrag in seiten.json. Die Variable `const a = rollen.admin.seite` (Zeile 338) wird zwei Zeilen weiter (346) noch gebraucht und muss dabei erhalten bleiben — sie ist beim Streichen leicht mitzunehmen.
*Risiko:* Bleibt der Block, ruft jeder Bilderlauf eine gelöschte Seite auf und druckt bei jedem Lauf „NICHT AUFGELÖST: __ADMIN_STANDORT__" — Rauschen, das die Meldung für einen echten nicht auflösbaren Platzhalter (Bestand leer, Sitzung verloren) entwertet.

**`tools/screenshots/aufnehmen.mjs:536-541`** — Der Kommentar über `const ausgefallen = []` begründet die getrennte Führung von „Sitzung verloren" und „Platzhalter nicht auflösbar" mit genau diesem Fall: „Beim Lauf zu AP5-4 meldete der Bericht acht verlorene Sitzungen, wo in Wahrheit acht Bilder einer Seite fehlten, die es ohne systemweiten Standort gar nicht gibt."
*Rückbau:* Der Kommentar darf bleiben — er begründet eine Bauform, nicht eine Seite —, sollte aber ein Wort bekommen, dass der Anlassfall mit P5 entfallen ist. Die Unterscheidung selbst ist unabhängig von den zentralen Stammdaten wertvoll.
*Risiko:* Gering. Ein Kommentar, dessen Beispiel es nicht mehr gibt, verleitet aber dazu, die Unterscheidung für überflüssig zu halten und zurückzubauen — dann meldet der Bilderlauf wieder den falschen Grund.

**`tools/screenshots/ausgabe/bericht.md:25-34 (gitignored, Lauf vom 09.09.2026 02:29)`** — MESSBELEG für den heutigen Zustand: „8 Aufnahmen sind AUSGEFALLEN" — achtmal `42a-stammdaten-standortseite @ <Breite> — Platzhalter __ADMIN_STANDORT__ nicht auflösbar". Der Lauf umfasste nur 3 gefilterte Seiten in 8 Breiten (24 Einzelbilder, 0 Überlauf, 0 Konsolenfehler); `42-stammdaten-systemweit` wurde dabei fotografiert und zeigt die leere Verwaltungsliste.
*Rückbau:* Nichts — `tools/screenshots/ausgabe/` steht in .gitignore (Zeile 80) und ist Laufwerk, kein Repositoriumsinhalt.
*Risiko:* Keines. Der Beleg ist hier aufgeführt, weil er die Aussage „der Referenzbestand hat keinen systemweiten Standort" mit einer Zahl unterlegt statt mit einer Behauptung.

**`tools/screenshots/seiten.json:212-217`** — Der Eintrag `42-stammdaten-systemweit`, Gruppe „Administration", Rolle admin, Pfad `admin_stammdaten.php` — die Übersichtsseite der zentralen Stammdatenpflege in acht Breiten.
*Rückbau:* Eintrag ersatzlos streichen. Damit fallen 8 Einzelbilder und eine Zeile aus dem Kontaktbogen weg; die Zahlen im Prüfdokument („N Seiten, N×8 Bilder") sind entsprechend neu zu nennen.
*Risiko:* Bleibt er stehen, fotografiert der Bilderlauf nach dem Rückbau acht Bilder einer 404- oder Weiterleitungsseite unter dem Namen „42-stammdaten-systemweit" — und meldet dazu „0 Überlauf, 0 Konsolenfehler". Genau das Muster, das im Repositorium als F-P3-AH/AQ dokumentiert ist: eine grüne Zahl über acht Bildern der falschen Seite.

**`tools/screenshots/seiten.json:218-223`** — Der Eintrag `42a-stammdaten-standortseite` mit Platzhalter `__ADMIN_STANDORT__` — die Standortseite der Verwaltung. Er ist seit dem 09.09.2026 (S9/AP5-4) neu und hat noch nie ein Bild erzeugt: Der Referenzbestand hat keinen systemweiten Standort, der Platzhalter bleibt `null`, und der Bilderlauf lässt die Seite ausdrücklich aus.
*Rückbau:* Eintrag ersatzlos streichen — so steht es auch in Backlog Nr. 166 („der Eintrag `42a-stammdaten-standortseite` in `tools/screenshots/seiten.json` fällt mit dem Rückbau weg").
*Risiko:* Bleibt er stehen, meldet der Bilderlauf auf Dauer acht ausgefallene Aufnahmen mit Grund. Das ist heute richtig (ein Zustand, kein Mangel), nach dem Rückbau aber eine Zeile, die auf eine Seite zeigt, die es gar nicht mehr geben kann — und die den Blick auf echte Ausfälle verstellt.

**`tools/vollstaendigkeit/pruefen.py:220-274 (Messung)`** — GEMESSEN, kein Befund: Keine einzige Klasse des Markups steht ausschließlich in `admin_stammdaten.php` (0 von allen `class="…"`-Literalen unter server/ ohne vendor/fonts/demo), und kein Eintrag aus `ohne-regel.md` oder aus den `[bleibt]`-Zeilen der Streichliste hat dort seinen einzigen Fundort. Die Seite teilt sich ihr Markup mit `einstellungen.php`.
*Rückbau:* Nichts vorzubereiten. Die Vollständigkeit läuft nach dem Rückbau unverändert durch — kein neuer Befund „ohne-regel.md: Eintrag ungenutzt", keine neue verwaiste Klasse.
*Risiko:* Keines. Der Befund ist trotzdem zu nennen, weil ohne die Messung die naheliegende Annahme wäre, dass mit einer Seite auch Klassen verschwinden — sie tun es hier nicht, und das ist eine Zahl, keine Vermutung.

**`tools/vollstaendigkeit/streichliste.md:110`** — Die Begründung zu `ac-form` nennt `admin_stammdaten.php` als zweiten Fundort: „Steht seit O8a in `einstellungen.php` und seit O9c auch in `admin_stammdaten.php`, beide aus derselben Vorlage."
*Rückbau:* Nur den Halbsatz zum zweiten Fundort streichen. Der Eintrag selbst bleibt ([bleibt], Skriptanker) — die Klasse steht weiterhin in `einstellungen.php`.
*Risiko:* Gering, aber die Streichliste ist das Dokument, an dem die Vollständigkeitsprüfung ihre Erklärungen hat. Eine Erklärung, die auf eine gelöschte Datei zeigt, ist der Anfang der Verwahrlosung, gegen die die Liste gebaut ist.

**`tools/vollstaendigkeit/streichliste.md:97 und tools/vollstaendigkeit/vorher-klassen.txt:24`** — Die Klasse `badge-central` („Kennzeichen ‚systemweit' an Stammdatenzeilen") steht in der Sollmenge des ALTEN Stylesheets (vorher-klassen.txt) und ist in der Streichliste mit Begründung und Paket O8b ausgetragen — sie wurde schon in P3 durch `ui_plakette('systemweit')` ersetzt.
*Rückbau:* NICHT anfassen. `vorher-klassen.txt` ist ein eingefrorener historischer Stand („Klassen des alten Stylesheets"), und jeder Eintrag darin braucht dauerhaft entweder eine Regel oder eine Streichzeile. Wer die Streichzeile löscht, weil die Sache verschwindet, erzeugt einen Befund „ohne Gegenstueck".
*Risiko:* Löschen der Streichzeile macht den Lauf rot, und zwar mit einem Befund, dessen Ursache (eine gelöschte Erklärungszeile) nirgends steht. Das ist der teuerste denkbare Fehlgriff an diesem Werkzeug.

**`tools/wortliste/ausnahmen.json (673 Zeilen, 0 Treffer)`** — GEMESSEN, kein Befund: Kein einziger Eintrag der Ausnahmeliste ist auf `server/admin_stammdaten.php` verankert (`grep -c admin_stammdaten` = 0); die einzige dateigebundene Admin-Ausnahme betrifft `server/admin_installation.php` (Zeile 581). Die Wortliste arbeitet ohnehin über Globs (`server/*.php`, wortliste.py:81-90) und nicht über eine Dateiliste.
*Rückbau:* Nichts zu streichen. Die Wortliste ist nach der Textänderung (Handbuch, Oberfläche) erneut zu fahren — sie zieht die Dateimenge von selbst nach.
*Risiko:* Keines am Werkzeug. Wohl aber am Verfahren: Werden beim Rückbau Texte in `einstellungen.php` und im Handbuch umgeschrieben, muss die Wortliste ZULETZT laufen (CLAUDE.md 6) — ein Lauf vor der letzten Änderung misst einen Stand, den es nicht mehr gibt.

