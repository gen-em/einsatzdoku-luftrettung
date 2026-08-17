# Konzept: Erweiterung auf bodengebundene Notarzteinsätze

**Status:** in Umsetzung
**Art:** Umbau am Datenmodell — Haupt-Versionssprung Web, Neben-Versionssprung Uhr
**Repo:** `gen-em/einsatzdoku-luftrettung`

> **Dieses Dokument wird fortgeschrieben.** Es beantwortet die Frage „ist das
> schon erledigt?", ohne dass jemand den Changelog rückwärts lesen muss — nach
> demselben Muster wie `Review-Umsetzung.md`. Der Konzepttext ab Abschnitt 1
> bleibt als abgestimmte Grundlage stehen; was sich beim Umsetzen als überholt
> erwiesen hat, steht in Abschnitt 0.2 und ist im Text mit **[überholt]**
> gekennzeichnet. Der ursprüngliche Wortlaut wird **nicht** überschrieben —
> sonst ließe sich später nicht mehr nachvollziehen, wogegen entschieden wurde.

---

## 0. Umsetzungsstand

**Die Umsetzung läuft in vier Etappen, jede in einem eigenen Chat**, um das
Kontextfenster klein zu halten. Ein neuer Chat beginnt ohne Vorgeschichte und
liest **dieses Dokument** als Einstieg. Was hier nicht steht, ist verloren.

### 0.1 Fortschritt

| Etappe | Inhalt | Version | Stand |
|---|---|---|---|
| 1a | Schema, Migration, Rollenkatalog | — | **erledigt** |
| 1b | Codeanpassung an das neue Schema, Nachbearbeitungsseite, Umbenennung | Web 6.0.0 | **erledigt** |
| 2 | Einsatzfelder, Ortsfeld-Komponente, Abfahrtort und Luftlinie | Web 6.1.0 | **als Nächstes** |
| 3 | Auswertung, Suche, Export/Import/Backup | Web 6.2.0 | offen |
| 4 | Zusammenführen, Uhr, Dokumentation | Web 6.3.0 / Uhr 1.8.0 | offen |

> **Warum Etappe 1 geteilt wurde.** Der Umbau am Datenmodell und die Anpassung
> des Codes daran sind zwei sehr verschiedene Arbeiten: Die erste ist konzeptionell
> und musste gegen eine echte Datenbank geprüft werden, die zweite ist breit und
> mechanisch — 19 PHP- und 3 JavaScript-Dateien. Beides in einem Zug hätte den
> Kontext des Chats gesprengt, und eine **halb** umgestellte Anwendung wäre der
> schlechteste aller Zustände gewesen: Sie läuft nicht und man sieht ihr nicht an,
> wie weit sie ist. Etappe 1a steht deshalb für sich, vollständig geprüft.
>
> **Nach Etappe 1a läuft die Anwendung nicht.** Das Schema ist umgestellt, der
> Code liest noch das alte. Das ist ein bewusster Zwischenstand, kein Fehler —
> Etappe 1b stellt ihn her. Die Migration **nicht** auf der Produktivinstallation
> ausführen, bevor 1b vorliegt.
>
> **Mit Etappe 1b ist dieser Zwischenstand aufgehoben.** Die Anwendung läuft auf
> dem neuen Schema; Migration und Update gehören ab jetzt zusammen ausgeliefert.

**Versionsnummern** (aus dem tatsächlichen Stand abgeleitet, nicht frei gewählt):

| | von | nach |
|---|---|---|
| Web | 5.10.0 | 6.0.0, Etappen 2–4 als 6.1.0 / 6.2.0 / 6.3.0 |
| Uhr | 1.7.0 | 1.8.0 (erst Etappe 4) |
| JSON-Vertrag | 1.2 | **1.3** — nicht 1.2 wie in Abschnitt 4.4, die Nummer ist vergeben |
| Backup-Nutzlast | 5 | 6 (Container bleibt 3, Signatur `EDBAK2` unverändert) |

**Abnahmekriterien** (Abschnitt 6). Geprüft wird in
`docs/Pruefprotokoll-Notarzt.md`; dort steht auch, **wie** geprüft wurde.

| | Stand | | Stand | | Stand |
|---|---|---|---|---|---|
| A1 | **erfüllt** | A7a | **erfüllt** | A13d | offen (Etappe 3) |
| A2 | **erfüllt** | A7b | **erfüllt** | A13e | **erfüllt** |
| A3 | teilweise | A7c | **erfüllt** | A13f | offen (Etappe 3) |
| A4 | **erfüllt** | A8 | **erfüllt** | A13g–A13q | offen (Etappe 2) |
| A4a | **erfüllt** | A9 | **erfüllt** | A14 | offen (Etappe 4) |
| A5 | offen (Etappe 2) | A10 | **erfüllt** | A15 | **erfüllt** |
| A6 | offen (Etappe 4) | A11 | **erfüllt** | A16 | teilweise |
| A7 | offen (Etappe 4) | A12 | **erfüllt** | | |
| A13 | **erfüllt** | A13a–A13c | offen (Etappe 3) | | |

Wie jeder Punkt geprüft wurde, steht in `docs/Pruefprotokoll-Notarzt.md`.
„offen (Etappe N)" heißt: Das Kriterium betrifft Funktionen, die diese Etappe
noch nicht bringt — nicht, dass etwas fehlschlug.

Die beiden „teilweise" sind es aus benennbaren Gründen:

- **A3** verlangt zwei Dinge. Die eine Hälfte ist erfüllt: Ein bodengebundener
  Diensttag zeigt Fahrer, Praktikant und Sonstige und sonst keine Rolle
  (`role_gate` liest aus `day_crew`). Die andere Hälfte — „und keine
  Windenfelder" — hängt an `cap_gate`, und das ist ausdrücklich Etappe 2
  (Abschnitt 4.3). Bis dahin erscheinen Windenfelder auch an einem
  bodengebundenen Dienst. Sie sind leer und lassen sich ignorieren; **kein
  Datenverlust**, nur eine Zeile zu viel im Formular.
- **A16**: Die Dokumentation ist auf dem Stand von 1b — Changelog, Technik,
  Konzept und Prüfprotokoll sind durchgezogen. `Handbuch.md`, `JSON-Vertrag.md`,
  `Backup-Format.md` und `Export-Format.md` folgen mit Etappe 4, so vorgesehen
  (Abschnitt 4.12): Der JSON-Vertrag steigt erst dort auf 1.3, und das Handbuch
  soll den fertigen Stand beschreiben, nicht einen von vier.

### 0.2 Berichtigungen am Konzept

Der Konzepttext entstand auf dem Auditstand **Web 3.6.0 / Uhr 1.6.6 /
JSON-Vertrag 1.1**. Tatsächlich stand das Repository bei Beginn der Umsetzung
auf **Web 5.10.0 / Uhr 1.7.0 / JSON-Vertrag 1.2**. Daraus folgen Berichtigungen,
die **keine** der Entscheidungen E1–E40 berühren:

| Nr. | Betrifft | Berichtigung |
|---|---|---|
| B1 | 4.4 | JSON-Vertrag steigt auf **1.3**, nicht auf 1.2 — 1.2 ist bereits vergeben, und zwar für genau die dort geforderte Phase-10-Berichtigung. |
| B2 | 4.7 | **Entfällt ersatzlos.** Die Phase-10-Bereinigung ist erledigt: `db.php` führt nur noch 1–9, `JSON-Vertrag.md` ist auf 1.2 und berichtigt sie in Abschnitt 3 und 7, `Handbuch.md` sagt bereits „beim Abschluss des Einsatzes". Geblieben war ein Kommentar `-- 2..10` an `mission_phases.phase` in `schema.sql`; er ist berichtigt. |
| B3 | 5, V6 | **Entfällt.** Die Spalten der Tagestabelle sind seit Web 5.4.0 generisch: `mf_tagesspalten()` in `mission_fields_lib.php` ist die einzige auswertende Stelle (Backlog Nr. 10). Der im Konzept zitierte Kommentar zu `crew_override` dokumentiert inzwischen das Gegenteil. Ein neues Feld mit `day_col` wirkt ohne Codeänderung. |
| B4 | 4.12 | Die Dateiliste ist unvollständig. Ergänzend betroffen: `tageszuordnung_lib.php`, `flugtag_datum.php`, `einsatz_verschieben.php`, `mission_fields_lib.php`, `validate_lib.php`, `admin_user.php`, `adminbackup_lib.php`, `admin_sicherungen.php`, `assets/import.js`, `assets/aktionsmenu.js`, `assets/pwquality.js`, `assets/style.css`, `docs/Backlog.md`. |
| B5 | 4.9 | Die **gesamte** Schemaänderung läuft in **einer** Migration `2026_08_17_notarzt_erweiterung`, einschließlich der Einsatzspalten aus Abschnitt 4.3, die erst in Etappe 2 benutzt werden. Eine Schemaänderung in einem Zug ist dreien vorzuziehen: Wer einmal migriert hat, muss es für die späteren Etappen nicht erneut. Spalten ohne Katalogeintrag stören nicht. |
| B6 | 4.9 Schritt 11 | `days.aircraft` und `days.base` werden **nicht** ersatzlos entfernt, sondern vorher nach `vehicle_name` / `base_name` gerettet (`COALESCE`). Das ist zugleich der Ersatz für den Rückfall, den `api/suchindex.php` bisher auf diese Altspalten hatte — Diensttage von vor der Stammdaten-Umstellung bleiben nach Standort und Rettungsmittel auffindbar. |
| B7 | 4.2 | Der eindeutige Schlüssel von `bw_units`, `resources` und `transport_dests` muss den Standort enthalten (`user_id, base_id, name`). Das Konzept nennt das nur für `crew_presets`. Ohne die Erweiterung ließe sich dieselbe Zielklinik nicht an zwei Standorten anlegen — also genau die Doppelpflege nicht, die E15 ausdrücklich in Kauf nimmt. |
| B8 | 3.6 | Die neutralen Phasenbeschriftungen (E20) sind **serverseitig** bereits in Etappe 1 gesetzt (`PHASE_LABELS` in `db.php`), die Uhr folgt in Etappe 4. Bis dahin zeigt die Uhr „Abflug", das Web „Ausrücken". Rein kosmetisch: Der JSON-Vertrag überträgt Nummern, keine Beschriftungen. |

### 0.3 Probleme und Abweichungen aus der Umsetzung

| Nr. | Fundstelle | Sachverhalt |
|---|---|---|
| P1 | `update.php`, Migration | `days.crew` (Freitext-Besatzung aus der Zeit vor den Rollenspalten) hat **kein Ziel** in der neuen Struktur. Es auf eine Rolle abzubilden wäre geraten — „Sonstige" trüge dann eine Aufzählung statt eines Namens. Die Spalte steht deshalb in `'inhalt'`: Steht dort noch etwas, **verweigert** die Migration und meldet es, statt zu entscheiden. Auf einer leeren Spalte läuft sie durch. |
| P2 | `update.php`, Migration | `missions.crew_*` mit `crew_override = 0` wird **nicht** übernommen (Konzept 4.9 Schritt 6) und auch nicht geprüft. Diese Werte sind schon heute unerreichbar: Die COALESCE-Regel liest sie ausschließlich bei `crew_override = 1`. Was nie gelesen wird, geht beim Entfernen nicht verloren. |
| P3 | `update.php`, Migration | Es gibt Einsätze und Ruhe-Segmente, zu deren Datum **keine** `days`-Zeile existiert — bisher folgenlos, weil die Verknüpfung gerechnet und nicht gespeichert wurde. Die Migration legt für sie einen neutralen Diensttag an, sonst wären sie nach A11 verwaist. |
| P4 | `update.php`, Migration | Zweimal MySQL-Fehler 1553 („Cannot drop index … needed in a foreign key constraint") bei `days.uq_user_day` und `crew_presets.uq_user_role_name`: Beide führen `user_id` an und bedienen damit den Fremdschlüssel. Auflösung: Ersatzindex **vor** dem Entfernen anlegen. Dieselbe Falle ist in der Migration `2026_07_16_mehrere_reanimationen` dokumentiert. |
| P5 | `update.php`, Migration | Die `skip`-Prüfung darf **nicht** auf den ersten Schritt (`vehicles` vorhanden) prüfen. Ein in der Mitte abgebrochener Lauf wurde dadurch als erledigt verbucht und der Rest nie nachgeholt. Sie prüft jetzt auf den **letzten** Schritt (`days.aircraft` entfernt). |
| P6 | Migration vs. `schema.sql` | Nach der Migration sind `base_id` in `vehicles`, `crew_presets`, `bw_units`, `resources` und `transport_dests` **nullbar**, in einer Neuinstallation dagegen `NOT NULL`. Das ist die zweistufige Regel aus A12 und beabsichtigt: Die Nachbearbeitungsseite zieht die Bedingung an, sobald keine Zuordnung mehr offen ist. Bis dahin unterscheiden sich die beiden Wege in genau diesen fünf Spalten — sonst in nichts. **Erledigt in Etappe 1b:** Nach dem Durchlauf der Nachbearbeitung stimmen Spalten, Typen, NULL-Zulässigkeit und alle Indizes vollständig überein (geprüft, siehe Prüfprotokoll). |
| P7 | `einsatz_form.php`, Aufruf | Der Diensttag ist beim Nachtragen eines Einsatzes **Pflicht** (`?d=<Kennung>`), nicht mehr ein Datum. Ohne ihn gäbe es keinen Standort, aus dem sich die Vorschlagslisten ableiten, und keinen Rollensatz, aus dem sich die Besatzungsfelder ergeben. Die Seite weist einen Aufruf ohne Diensttag mit einer Meldung ab, statt einen zu erfinden. |
| P8 | `mission_fields.php` | Die Besatzungsfelder sind die **einzige Ausnahme** von „alle Felder sind Spalten in `missions`". Sie liegen in `mission_crew` und tragen dafür den Schlüssel `'store' => 'crew'`. Wer den Katalog auswertet, muss ihn beachten — `mf_ist_spalte()` und ein Wächter in `mf_tagesspalten()` fangen den Fehler ab, statt ihn als SQL-Fehler ohne erkennbaren Bezug auftauchen zu lassen. |
| P9 | `assets/*.js` | Der Rollenkatalog muss den Browser erreichen: `import.php` setzt `CREW_ROLLEN` und `CREW_LABELS` aus `CREW_ROLES`, **vor** `import_profiles.js` und `import.js` — beide leiten ihre Spaltenlisten beim Laden daraus ab. Jede der Dateien führt zusätzlich einen Rückfall auf die fünf Flugrollen, damit sie auch ohne die Vorgabe läuft. |
| P10 | Export, Schlüssel je Diensttag | Der Export bündelte Diensttage nach DATUM (`daysByDate`). Mit mehreren Diensttagen je Kalendertag hätten sie sich gegenseitig überschrieben, und der zweite hätte die Besatzung des ersten getragen. Jetzt nach Kennung (`day_id`). Dieselbe Falle steckte im Import-Abgleich; dort ist sie benannt und bewusst anders gelöst (siehe P12). |
| P11 | `backup_lib.php`, Wiedereinspielen | Die Wiedererkennung eines vorhandenen Diensttags über **Datum und Dienstbeginn** genügt nicht: Wird ein Einsatz zwischen zwei Diensttagen eines Kalendertags verschoben, zieht `dt_zeitraum_fortschreiben()` den Beginn nach vorne, und beide tragen denselben. Beim Prüfen verschmolzen dadurch zwei Diensttage zu einem. Erkannt wird jetzt zweistufig: zuerst über eine bereits vorhandene `client_ref` eines seiner Einsätze — die ist eindeutig —, ersatzweise über einen Fingerabdruck aus Datum, Beginn, Ende, Art und den eingefrorenen Bezeichnungen. |
| P12 | `api/import_commit.php` | Ein Import legt **je Kalendertag höchstens einen** Diensttag an. Aus einer Tabelle lässt sich nicht ableiten, ob zwei Einsätze desselben Datums zu einem oder zu zwei Diensten gehören; eine geratene Aufteilung wäre schlechter als eine, die jemand bewusst vornimmt. Das `ON DUPLICATE KEY UPDATE` ist mit dem Tagesschlüssel entfallen — ein blindes INSERT hätte bei jedem Lauf neue Diensttage angelegt. |
| P15 | `update.php`, Ergebnisliste | **Fehler, der älter ist als diese Etappe.** Die Zeile für eine bereits angewendete Migration trug vier Elemente, die Auswertung zerlegt sie in sechs — zwei PHP-Warnungen je verbuchter Migration, bei jedem Aufruf der Wartungsseite. Auf einer Installation mit 30 Migrationen sechzig Zeilen Fehlerprotokoll für nichts, ausgerechnet auf der Seite, die den Zustand der Datenbank berichten soll. Angezeigt wurde trotzdem das Richtige (die fehlenden Werte kamen als NULL an), deshalb war es nie aufgefallen. Berichtigt — die Ausnahme von der Regel „Etappe 1b fasst `update.php` nicht an" ist damit begründet. |
| P14 | `diensttag_lib.php`, Rückfallebene | Welcher Diensttag ist gemeint, wenn ein Upload OHNE `day_ref` auf ein Datum mit **mehreren** Diensttagen trifft (Konzept 4.4 lässt das offen)? Entschieden über die **Zeit** des Datensatzes, nicht über die Reihenfolge: erst der Diensttag, dessen Zeitraum ihn umschließt, dann der letzte, der vor ihm begonnen hat, dann der früheste des Datums. Die erste Fassung nahm schlicht den jüngsten — ein Früheinsatz landete dadurch am Abenddienst und zog dessen Beginn um Stunden nach vorne. Die Uhr sagt nicht, welcher Dienst gemeint ist; ihre Zeitstempel sagen es sehr wohl. |
| P13 | `nachbearbeitung.php` | Der letzte Schritt (`base_id` auf `NOT NULL`) ändert das **Schema** und gilt für alle Konten. Er ist deshalb auf Admins beschränkt — und zwar im Handler, nicht nur durch einen verborgenen Knopf. Zusätzlich prüft er über **alle Konten** hinweg auf offene Einträge: Die Bedingung gilt für die Tabelle, ein einziger offener Eintrag eines anderen Kontos ließe das `ALTER TABLE` mit einem Datenbankfehler scheitern statt mit einer lesbaren Meldung. |

### 0.4 Was Etappe 1b umgesetzt hat

**Die Anwendung läuft auf dem neuen Schema.** Neue Dateien, umbenannte Dateien
und die Umstellungsregeln stehen unten; geprüft wurde gegen dieselbe MariaDB, mit
der Etappe 1a gearbeitet hat (Ablauf in `Pruefprotokoll-Notarzt.md`).

**Neu:**

| Datei | Wozu |
|---|---|
| `server/diensttag_lib.php` | Anlegen, Zuordnen, Einfrieren und Auflisten von Diensttagen. Die eine Stelle, an der E8 und E9 umgesetzt sind — Formular, Uhr, Import und Nachbearbeitung benutzen sie alle. |
| `server/nachbearbeitung_lib.php` | Die beiden offenen Listen und die zweite Stufe aus A12 (`base_id` auf `NOT NULL`). Ohne eigene Buchführung: Ob die Bedingung steht, sagt das Schema selbst. |
| `server/nachbearbeitung.php` | Die einmalige Seite aus E24. |

**Umbenannt** (die alten Dateien müssen auf dem Webspace verschwinden):
`flugtag_neu.php` → `diensttag_neu.php`, `flugtag_loeschen.php` →
`diensttag_loeschen.php`, `flugtag_datum.php` → `diensttag_datum.php`.

**Adressen:** `index.php?day=YYYY-MM-DD` ist zu `index.php?d=<Kennung>`
geworden, ebenso bei `einsatz_form.php`, den drei `diensttag_*.php` und im
Papierkorb. `api/day.php` nimmt `?d=<Kennung>` und im POST `day_id`.

**Entfallen, weil der Tagesschlüssel weg ist:**

- die Kollisionsprüfung beim Umdatieren (`tz_tag_datum_aendern`) samt der Liste
  belegter Daten in `diensttag_datum.php`,
- `tz_zieltag_sichern()` — der Zieltag wird gewählt, nicht angelegt,
- `tz_tag_zustand()`,
- das `INSERT IGNORE`/`ON DUPLICATE KEY UPDATE` auf `days` an vier Stellen,
- der Rückfall von `api/suchindex.php` auf `days.aircraft`/`days.base`: Die
  Migration hat den Altfreitext in die Snapshot-Spalten gerettet (B6).

#### Was sich am Datenmodell geändert hat — die Umstellungsregeln

| Bisher | Künftig |
|---|---|
| `aircraft`, Spalte `registration` | `vehicles`, Spalte `name`, dazu `kind` und `base_id` |
| `aircraft.p1 … other` | Zeilen in `vehicle_roles (vehicle_id, role_code)` |
| `days.aircraft_id` | `days.vehicle_id` |
| `days.crew_p1 … crew_other` | Zeilen in `day_crew (day_id, role_code, name)` |
| `missions.crew_p1 … crew_other` | Zeilen in `mission_crew`, nur bei `crew_override = 1` |
| `missions.day`, `rest_segments.day` | `day_id` mit Fremdschlüssel auf `days` |
| Verknüpfung über `(user_id, day)` | Verknüpfung über `day_id` |
| `days.aircraft` / `days.base` (Altfreitext) | `days.vehicle_name` / `days.base_name` (eingefroren) |
| `crew_presets.role` (ENUM) | `crew_presets.role_code` (VARCHAR), dazu `base_id` |
| `user_defaults.kind = 'aircraft'` | `'vehicle'` |
| `bases.is_default`, `aircraft.is_default` | entfallen |
| Anzeige aus den Stammdaten | Anzeige **immer** aus den Snapshot-Spalten des Diensttags (E8) |

Die Architekturregel „`days` und `missions` dürfen nie gejoint werden" ist
**aufgehoben** (Konzept 4.11): Sie entstand allein aus den gleichnamigen
`crew_*`-Spalten. `missions.day_id = days.id` ist ab jetzt der vorgesehene Weg
und wird in `api/range.php`, `api/export_data.php`, `api/import_commit.php` und
`trash_lib.php` benutzt. In `Technik.md` ist sie als aufgehoben gekennzeichnet.

#### Bewusst noch nicht neutral: die Kacheln der Zeitraumübersicht

`zeitraum.php` beschriftet weiterhin „Flugtage", „Ø Einsätze / Flugtag" und
„Flugkilometer gesamt". Das ist **kein Übersehen**: Die Kacheln werden in
Etappe 3 nach Art in Tabs geteilt, und der Luftrettungs-Tab behält genau diese
Beschriftungen (E32, A13f). Sie jetzt zu neutralisieren und in Etappe 3
zurückzudrehen wäre Arbeit für nichts. Einsatztabelle, Suche und Export sprechen
dagegen schon jetzt neutral (Abschnitt 3.7.3) — dort bleiben sie es dauerhaft.

Bis Etappe 3 heißt das: Wer bodengebunden dokumentiert, liest in der
Zeitraumübersicht „Flugtage", wo Diensttage gemeint sind. Hinzunehmen, weil die
Zahl stimmt und der Umbau der Ansicht ohnehin ansteht.

### 0.5 Nächste Etappe: 2 — Einsatzfelder, Ortsfeld, Abfahrtort

**Für den Chat, der Etappe 2 übernimmt.** Voraussetzung: Das ZIP der Etappe 1b
ist committet und gepusht; ein neuer Chat klont frisch.

**Etappe 2 braucht keine Migration.** `transport_mode`, `na_escort`,
`false_alarm`, `start_src`, `dest_lat`, `dest_lon` und die Koordinaten in
`bases`/`transport_dests` **existieren bereits** (Berichtigung B5) und werden von
Backup und Export schon mitgeführt.

**Prüfumgebung wiederherstellen** (Anleitung in `Pruefprotokoll-Notarzt.md`):
MariaDB installieren, `edoku_bestand` aus
`docs/pruefgrundlage/schema-vor-6.0.0.sql` plus Testbestand aufbauen und
migrieren, `edoku_neu` aus dem neuen `schema.sql`. Zum Prüfen der Oberfläche
genügt der eingebaute PHP-Server mit einem Testrouter, der eine Sitzung setzt;
das Vorgehen steht im Prüfprotokoll.

**Zu tun:**

- Grundlage sind die Vorprüfungen **V4** und **V8** in Abschnitt 5a — bereits
  durchgeführt, nicht erneut erheben.
- Feldkatalog um `transport_mode` samt Kindern, `false_alarm`, `kind_gate`,
  `cap_gate` und `show_if`. `role_gate` ist in 1b umgesetzt und liest aus
  `day_crew`; `kind_gate` und `cap_gate` folgen demselben Muster und finden
  `days.kind` sowie `dt_faehigkeiten()` vor.
- `show_if` im Lese- und Renderpfad von `einsatz_form.php` (Vorgehen in V4). Der
  Lesepfad hat sich in 1b geändert: `$readField` verteilt jetzt auf **zwei**
  Ziele (Spalten und `mission_crew`, Befund P8). Die `show_if`-Auswertung gehört
  weiterhin allein in den Durchfall-Zweig.
- Ortsfeld-Komponente `assets/ortsfeld.js` herauslösen (Vorgehen in V8). Sie
  ersetzt dann auch die einfachen Koordinatenfelder, die 1b in der Standort- und
  Zielklinikpflege eingebaut hat — dort steht bereits ein Kommentar darauf.
- Abfahrtort und Luftlinie zeichnen.

---

## 1. Aufgabenstellung

Die Anwendung dokumentiert bisher ausschließlich Einsätze der Luftrettung. Sie
soll künftig ebenso bodengebundene Notarzteinsätze (NEF, NAW) abbilden. Beides
in einer Installation, ein Konto kann beides mischen — auch am selben
Kalendertag.

Damit verbunden sind vier strukturelle Änderungen, die über ein bloßes
Ergänzen von Feldern hinausgehen:

1. Der **Standort** wird zum Anker der Stammdaten. An ihm hängen Rettungsmittel,
   Zielkliniken, weitere Rettungsmittel, Bergwacht-Bereitschaften und
   Besatzungs-Vorbelegungen.
2. Das **Rettungsmittel** entscheidet, ob ein Dienst luft- oder bodengebunden
   ist, welche Besatzungsrollen es gibt und welche Einsatzfelder erscheinen.
3. Der **Diensttag** löst sich vom Kalendertag. Jeder Start auf der Uhr erzeugt
   einen eigenen Diensttag; mehrere pro Kalendertag sind zulässig.
4. Die **Besatzung** wird normalisiert, weil feste Rollenspalten mit zwei
   Rettungsmittelarten nicht mehr tragen.

Das Produkt heißt künftig **Einsatzdokumentation Notarzt**.

---

## 2. Verbindliche Entscheidungen

Diese Punkte sind abgestimmt und stehen nicht mehr zur Diskussion. Abweichungen
sind vor der Umsetzung als nummerierte Rückfrage zu melden.

| Nr. | Entscheidung |
|---|---|
| E1 | Umfang: Notarztdienst. Kein Rettungsdienst ohne Notarzt, kein KTW/ITW. |
| E2 | Eine Installation, ein Konto, gemischte Dienste erlaubt. |
| E3 | Rettungsmittel sind binär: luftgebunden oder bodengebunden. |
| E4 | Rollen stammen aus einem festen Katalog im Code, nicht aus der Datenbank. |
| E5 | Luftrollen unverändert: Pilot 1, Pilot 2, HEMS-TC, Flugretter, Sonstige. |
| E6 | Bodenrollen: Fahrer, Praktikant, Sonstige. „Sonstige" ist dieselbe Rolle wie bei Luft. |
| E7 | Besatzung wird normalisiert; die Spalten `crew_p1…crew_other` entfallen in `days` und `missions`. |
| E8 | Alles, was der Diensttag aus Standort und Rettungsmittel ableitet — Art, Rollensatz, Fähigkeiten, Bezeichnungen, Standortkoordinaten —, wird beim Anlegen eingefroren. Stammdatenänderungen wirken nur in die Zukunft. |
| E9 | Jeder Klick auf „Einsatztag starten" erzeugt einen eigenen Diensttag. |
| E10 | Zusammenführen von Diensttagen für Uhr- **und** manuelle Tage, ein Codepfad. |
| E11 | Zusammenführen nur bei vereinbarer Art: luftgebunden und bodengebunden schließen sich aus, ein noch nicht zugeordneter Diensttag passt zu beidem. |
| E12 | Aufteilen eines Diensttags gibt es nicht. |
| E13 | Zusammenführen ist nicht umkehrbar und läuft nicht über den Papierkorb. |
| E14 | Einsatzsuche filtert nach tatsächlichem Einsatzdatum, Statistik rechnet nach Diensttag. |
| E15 | Standortbezug ist verbindlich. Es gibt keine standortübergreifenden Stammdaten; Doppelpflege wird zugunsten eines einfachen Modells hingenommen. |
| E16 | Nutzer wählen zentrale Standorte aus; nur ausgewählte erscheinen in Auswahllisten. |
| E17 | Neue Einsatzfelder: Transportart (Luft / Boden / Ambulant), Haken NA-Begleitung, Haken Fehleinsatz. |
| E18 | Kein Einsatzstichwort, keine Todesfeststellung, keine Hilfsfrist-Kennzahl. |
| E19 | Nachforderungen laufen weiterhin über „Weitere Rettungsmittel". |
| E20 | Phasenbeschriftungen werden neutral: Phase 3 „Ausrücken", Phase 7 „Ankunft Klinik". Sonst unverändert. |
| E21 | Die Uhr kennt die Einsatzart nicht. Die Einordnung geschieht ausschließlich im Web. |
| E22 | Die Connect-IQ-App-ID in `watch/manifest.xml` bleibt unverändert. |
| E23 | Backups älterer Formatversionen werden nach der Umstellung nicht mehr eingelesen. |
| E24 | Die Migration läuft in `update.php`; nicht ableitbare Zuordnungen erledigt eine einmalige Nachbearbeitungsseite. |
| E25 | Das Zusammenführen wird aus dem Zieldiensttag heraus gestartet. Kein Auswahlmodus in der Tagesliste. |
| E26 | Ein Diensttag ohne Rettungsmittel bleibt neutral: keine Art, keine Rollen, keine artabhängigen Felder — bis die Zuordnung nachgetragen ist. |
| E27 | Die Art erscheint als Symbol am Rettungsmittelnamen, nicht als eigene Spalte. |
| E28 | Die Zeitraumübersicht wird in Tabs nach Art geteilt. Nur eine Art im Zeitraum vorhanden → keine Tableiste. Beide vorhanden → drei Tabs mit „Gemischt" als aktivem. |
| E29 | Winde und Bergwacht sind zwei getrennte Fähigkeiten, die je Rettungsmittel angehakt werden und beim Anlegen des Diensttags eingefroren werden. |
| E30 | Artspezifische und fähigkeitsabhängige Kacheln erscheinen nur, wenn im Zeitraum tatsächlich entsprechende Einsätze vorliegen — nicht schon, wenn das Rettungsmittel es könnte. |
| E31 | Neutrale Diensttage zählen im Tab „Gemischt" mit und werden dort ausdrücklich ausgewiesen. |
| E32 | Kachelbeschriftungen sind tababhängig: Luftrettung behält die Flugterminologie, die übrigen Tabs sprechen neutral. Der Luftrettungs-Tab entspricht damit exakt dem heutigen Bestand — ohne Fehleinsatz-Kachel. |
| E33 | Der Tab „Gemischt" führt dieselben acht Kacheln wie der bodengebundene, neutral beschriftet und über alle Diensttage gerechnet. |
| E34 | Einsätze ohne Track erhalten einen wählbaren Abfahrtort: Standort, letzter Einsatzort, letzte Zielklinik oder manueller Ort. Gespeichert wird die Regel, nicht die Koordinate. |
| E35 | Aus Abfahrtort, Einsatzort und Zielklinik wird eine gestrichelte Luftlinie gezeichnet. Ein aufgezeichneter Track hat immer Vorrang. |
| E36 | Die Luftlinienlänge fließt in keine Kachel und in keinen Filter ein. |
| E37 | Standorte **und** Zielkliniken erhalten optionale Koordinaten. |
| E38 | Zielklinik-Koordinaten sind auf drei Ebenen pflegbar: zentral durch Admins, persönlich im Konto, ad hoc am einzelnen Einsatz. |
| E39 | Koordinaten sind überall freiwillig. Reine Textangabe bleibt für Einsatzort, Abfahrtort und Zielklinik uneingeschränkt möglich; sie erzeugt lediglich keinen Pin und keine Linie. |
| E40 | Die Zielklinik-Koordinate bleibt Klartext, wie der Zielklinikname heute schon. Ihr Pin ist damit ohne Freischalten sichtbar; die Linie nicht, weil der Einsatzort verschlüsselt ist. |

---

## 3. Fachliches Konzept

Dieser Abschnitt beschreibt das Verhalten aus Sicht der Anwendung und ist ohne
Kenntnis der Datenbankstruktur lesbar. Die technische Umsetzung folgt in
Abschnitt 4.

### 3.1 Standort als Anker

Ein Standort ist der Ort, an dem Dienst geleistet wird. Standorte gibt es in
zwei Ausprägungen, die sich nicht ausschließen:

- **Zentrale Standorte** legt eine Administratorin an. Sie stehen allen zur
  Verfügung, erscheinen aber erst dann in den Auswahllisten einer Nutzerin,
  wenn sie sie in den Voreinstellungen ausgewählt hat.
- **Eigene Standorte** legt jede Nutzerin selbst an. Sie sind automatisch
  ausgewählt und für niemanden sonst sichtbar.

An einem Standort hängen: Rettungsmittel, Zielkliniken, weitere Rettungsmittel,
Bergwacht-Bereitschaften und Besatzungs-Vorbelegungen.

Der Standortbezug ist dabei **verbindlich**. Jede Zielklinik, jedes
Rettungsmittel, jede Besatzungs-Vorbelegung, jedes weitere Rettungsmittel und
jede Bergwacht-Bereitschaft gehört genau einem Standort. Es gibt keine
standortübergreifenden Einträge.

Der Preis dafür ist Doppelpflege: Eine Zielklinik, die von zwei Standorten
angefahren wird, muss zweimal angelegt werden. Das ist bewusst in Kauf
genommen, weil eine zweite, überall gültige Ebene jede Auswahlliste, jede
Pflegemaske und jede Migrationsregel um einen Sonderfall erweitern würde. Ein
Modell mit einer Regel ist einem mit zwei vorzuziehen.

In den Auswahllisten erscheinen damit genau die Einträge des Standorts, der am
Diensttag hinterlegt ist.

### 3.2 Rettungsmittel und Rollen

Ein Rettungsmittel ist entweder luft- oder bodengebunden. Diese Eigenschaft
steuert alles Weitere: die verfügbaren Besatzungsrollen und die im
Einsatzformular sichtbaren Felder.

Beim Anlegen eines Rettungsmittels werden die Rollen angehakt, die tatsächlich
besetzt werden — so wie bisher beim Hubschrauber. Zur Wahl stehen je nach Art:

| Art | Verfügbare Rollen |
|---|---|
| Luftgebunden | Pilot 1, Pilot 2, HEMS-TC, Flugretter, Sonstige |
| Bodengebunden | Fahrer, Praktikant, Sonstige |

Die Notärztin selbst ist keine Rolle — sie ist die Nutzerin.

### 3.3 Diensttag

Ein Diensttag beginnt mit „Einsatztag starten" auf der Uhr und endet mit
„Einsatztag beenden". Er trägt echte Start- und Endzeiten. Jeder Start erzeugt
einen eigenen Diensttag; zwei Dienste am selben Kalendertag sind dadurch
möglich, etwa ein Hubschrauberdienst am Tag und ein NEF-Nachtdienst am Abend.

Das Datum dient nur noch der Sortierung und Anzeige — es ist das Datum des
Dienstbeginns. Einsätze hängen am Diensttag, nicht mehr am Datum.

Diensttage lassen sich auch von Hand anlegen, ohne Uhr. Für sie gilt dasselbe.

Zu jedem Diensttag werden Standort und Rettungsmittel gewählt. Daraus ergeben
sich Art, Rollen, Fähigkeiten, Bezeichnungen und die Standortkoordinaten.
**Alles davon wird beim Anlegen des Diensttags eingefroren.** Wird ein
Rettungsmittel oder ein Standort später bearbeitet, umbenannt oder gelöscht,
bleiben bereits dokumentierte Diensttage unverändert. Änderungen an den
Stammdaten wirken ausschließlich in die Zukunft.

Das gilt ausnahmslos, auch für einen Tippfehler im Namen: Ein Diensttag ist ein
abgeschlossener Dienstnachweis, kein Blick auf den heutigen Stammdatenbestand.
Wer eine alte Bezeichnung korrigieren will, tut das am Diensttag selbst.

Der Verweis auf den Stammdatensatz bleibt daneben bestehen, aber nur zum
Filtern und Auswerten — nicht für die Anzeige.

Solange Standort und Rettungsmittel fehlen — etwa bei einem Diensttag, den die
Uhr soeben selbst angelegt hat —, bleibt der Diensttag **neutral**: ohne Art,
ohne Rollen, ohne artabhängige Einsatzfelder. Zeiten, Phasen, Track und
Reanimationsdokumentation werden trotzdem vollständig erfasst; alles Weitere
erscheint, sobald die Zuordnung nachgetragen ist. Ein bereits belegtes Feld
bleibt dabei immer sichtbar, es geht also auch dann nichts verloren, wenn die
Zuordnung später eine andere Art ergibt. Neutrale Diensttage sind in der
Übersicht als solche erkennbar.

### 3.4 Zusammenführen von Diensttagen

Wurde die App während eines Dienstes versehentlich mehrfach gestartet,
entstehen mehrere Diensttage für einen tatsächlichen Dienst. Sie lassen sich
zusammenführen.

Der Einstieg liegt **im Diensttag selbst**, nicht in der Tagesliste: Der
geöffnete Diensttag ist immer der Zieltag, aufgenommen wird ein anderer. Damit
ist die Richtung eindeutig — wichtig, weil der Vorgang nicht umkehrbar ist.

Ablauf: Im Zieltag die Aktion „Anderen Diensttag aufnehmen" wählen, aus einer
kurzen Liste zeitlich benachbarter Diensttage den aufzunehmenden auswählen,
Vorschau bestätigen. Die Vorschau zeigt den resultierenden Zeitraum und die
Anzahl der Einsätze und Ruhe-Segmente. Nach Bestätigung wandern Einsätze und
Ruhe-Segmente zum Zieltag, Start- und Endzeit werden auf den umschließenden
Zeitraum gesetzt, der aufgenommene Tag verschwindet.

Regeln:

- Beide Tage müssen derselben Nutzerin gehören und eine **vereinbare Art**
  haben. Luftgebunden und bodengebunden schließen sich aus und werden mit
  Hinweis abgewiesen, weil sonst Einsätze mit Windendokumentation an einem
  bodengebundenen Tag landen und ihre Felder verlieren würden. Ein neutraler,
  noch nicht zugeordneter Diensttag passt dagegen zu beidem; das Ergebnis
  übernimmt die zugeordnete Art. Sind beide neutral, bleibt es auch das
  Ergebnis.
- Unterscheiden sich die Rettungsmittel innerhalb derselben Art, wird beim
  Zusammenführen ausgewählt, welches gilt. Dasselbe gilt für Standort und
  Besatzung. Notizen werden aneinandergehängt.
- Der Vorgang ist **nicht umkehrbar** und läuft nicht über den Papierkorb: dort
  läge ein leerer Tag, dessen Wiederherstellung die Einsätze nicht zurückholen
  könnte.
- Ein Aufteilen gibt es nicht.

### 3.5 Einsatzfelder

**Neu für beide Arten:**

- **Transportart** — Auswahl aus Luft, Boden, Ambulant. „Ambulant" bedeutet,
  dass die Patientin nicht transportiert wurde.
- **NA-Begleitung** — Haken unterhalb der Transportart, nur bei Luft und Boden.
- **Fehleinsatz / Storno / Abbruch** — ein Haken, keine Unterauswahl.

Zielklinik und Schockraum hängen künftig ebenfalls an der Transportart und
entfallen bei „Ambulant".

**Nur bei entsprechend ausgestattetem Rettungsmittel sichtbar:** Windeneinsatz
samt Cycles und Luftverladung, Bergwacht samt Bereitschaft und Infos. Beim
Anlegen eines luftgebundenen Rettungsmittels werden dazu zwei getrennte Haken
gesetzt — Winde und Bergwacht. Ein Hubschrauber kann eine Winde führen, ohne in
einer Bergwachtkooperation zu stehen, und umgekehrt. Die beiden Fähigkeiten
werden wie Art und Rollen beim Anlegen des Diensttags eingefroren: Wird der
Windenhaken Jahre später entfernt, verlieren alte Einsätze ihre
Windendokumentation nicht.

Da Fähigkeiten ausschließlich an luftgebundenen Rettungsmitteln vorkommen,
steuern sie diese Felder allein — eine zusätzliche Prüfung auf die Art ist
weder nötig noch vorgesehen.

**Unverändert für beide:** Sekundärtransport, Anderer Notarzt, Weitere
Rettungsmittel, Abweichende Besatzung, Notizen, sowie sämtliche
Ende-zu-Ende-verschlüsselten Patientendaten.

Felder, die durch die Art ausgeblendet werden, behalten ihren Inhalt. Sie
werden weiterhin gerendert und lediglich versteckt — genau nach dem
bestehenden Muster der Rollensteuerung. Ein bereits belegtes Feld bleibt immer
sichtbar, damit nichts stillschweigend verloren geht.

#### 3.5.1 Abfahrtort und Streckenlinie ohne GPS-Aufzeichnung

**Ausgangslage.** Fällt die Uhr aus oder wird ohne Uhr gearbeitet, fehlt der
Track. Die Karte bleibt leer, obwohl der Einsatzort bekannt ist: Ein manuell
erfassbarer Einsatzort mit Adresssuche und Koordinaten existiert bereits und
wird als Pin dargestellt. Was fehlt, ist der Gegenpunkt.

**Lösung.** Zu jedem Einsatz lässt sich ein Abfahrtort bestimmen. Sind
Abfahrtort und Einsatzort bekannt und liegt kein Track vor, zeichnet die Karte
eine gerade Verbindung zwischen beiden. Hat zusätzlich die Zielklinik
Koordinaten, wird die Linie um diesen dritten Punkt verlängert:
**Abfahrtort → Einsatzort → Zielklinik**.

Der Abfahrtort wird nicht als Koordinate erfasst, sondern als **Regel**, aus
der die Koordinate abgeleitet wird:

| Auswahl | Herkunft der Koordinate |
|---|---|
| Standort | Koordinaten des Standorts des Diensttags |
| Letzter Einsatzort | Einsatzort des zeitlich vorherigen Einsatzes desselben Diensttags |
| Letzte Zielklinik | Zielklinik des zeitlich vorherigen Einsatzes desselben Diensttags |
| Manueller Ort | eigene Adresssuche, analog zum Einsatzort |
| *(nichts gewählt)* | keine Linie |

Damit genügt in der Regel eine einzige Auswahl statt einer Adresseingabe je
Einsatz.

**Zu den beiden Vorgänger-Auswahlen.** Sie bilden zwei verschiedene reale
Abläufe ab. Nach einem Transport steht das Rettungsmittel an der **Zielklinik**
des Vorgängers und rückt von dort zum nächsten Einsatz aus — dafür ist „Letzte
Zielklinik" gedacht. Wurde nicht transportiert, etwa bei ambulanter Versorgung
oder Fehleinsatz, steht es noch am **Einsatzort** des Vorgängers; dann greift
„Letzter Einsatzort". Beide bleiben zur Wahl, weil sich der Fall nicht
zuverlässig automatisch bestimmen lässt: Ein Einsatz kann abgebrochen worden
sein, nachdem bereits ein Transportziel eingetragen war.

Fehlt die jeweilige Koordinate — kein vorheriger Einsatz im Diensttag, kein
Einsatzort, keine Zielklinik oder eine Zielklinik ohne Koordinaten —, entsteht
keine Linie. Es wird **nicht** stillschweigend auf eine andere Quelle
ausgewichen, weil eine falsche Linie schlechter ist als keine.

**Zielklinik als dritter Punkt.** Die Zielklinik ist heute ein Textfeld mit
Vorschlagsliste; Freitext bleibt uneingeschränkt möglich. Neu lassen sich dazu
Koordinaten hinterlegen — auf drei Ebenen:

| Ebene | Wer pflegt | Wirkung |
|---|---|---|
| Zentral | Administratorin | gilt für alle, die den Eintrag sehen |
| Konto | Nutzerin selbst | eigene Zielkliniken |
| Einsatz | Nutzerin am Einsatz | einmalig, für diesen Einsatz |

Wird eine Zielklinik aus der Vorschlagsliste übernommen, füllen sich ihre
Koordinaten mit; sie lassen sich am Einsatz überschreiben. Wird ein Name frei
eingetippt, bleiben sie leer, bis sie von Hand erfasst werden.

**Koordinaten sind überall freiwillig.** Einsatzort, Abfahrtort und Zielklinik
funktionieren unverändert als reine Textangabe. Ohne Koordinaten entstehen
lediglich kein Pin und keine Linie — kein Feld wird dadurch unbrauchbar. Für
alle drei gilt dieselbe Regel wie heute beim Einsatzort: Koordinaten ohne
Bezeichnung werden abgewiesen, eine Bezeichnung ohne Koordinaten ist zulässig.

**Vorrang des echten Tracks.** Liegt ein aufgezeichneter Track vor, wird er
gezeichnet und die Linie unterbleibt. Das gilt auch, wenn der Track erst später
eintrifft: Die Abfahrtortangabe bleibt gespeichert und wird lediglich nicht
mehr dargestellt. Fällt der Track später weg, erscheint die Linie wieder.

**Darstellung.** Die Linie wird **gestrichelt** und in einer eigenen Farbe
gezeichnet, damit sie nicht mit einem aufgezeichneten Track verwechselt wird.
Jeder Punkt erhält einen Pin. Die Luftlinienlänge wird an der Linie ausgewiesen
und ausdrücklich als Luftlinie benannt; bei drei Punkten ist es die Summe
beider Abschnitte.

Fehlt der Einsatzort, entsteht **keine** Linie — auch dann nicht, wenn
Abfahrtort und Zielklinik beide Koordinaten haben. Eine direkte Verbindung
zwischen beiden hat nie stattgefunden und wäre eine Falschaussage.

**Keine Statistikwirkung.** Die Luftlinienlänge fließt in **keine** Kachel und
in keinen Filter ein. Zwei Gründe:

- Die Kacheln werden serverseitig aggregiert, der Einsatzort liegt verschlüsselt
  im `pat_blob` und ist serverseitig nicht lesbar. Das ist dieselbe Grenze, an
  der auch die serverseitige Suche in verschlüsselten Feldern endet.
- Eine Luftlinie und eine gemessene Fahrt- oder Flugstrecke sind nicht dieselbe
  Größe. Bodengebunden liegt die Fahrstrecke deutlich über der Luftlinie; beides
  in einer Summe machte „Einsatzkilometer gesamt" unbrauchbar.

Wer ohne Uhr dokumentiert, hat damit keine Kilometerzahlen in der Statistik.
Das ist bewusst in Kauf genommen.

**Sichtbarkeit.** Da der Einsatzort verschlüsselt ist, erscheinen Linie und
Einsatzort-Pin erst nach Freischalten des Patientendatenschlüssels — wie der
Einsatzort-Pin heute schon. Der Zielklinik-Pin ist davon ausgenommen: Der
Zielklinikname steht bereits heute unverschlüsselt am Einsatz, seine Koordinate
folgt derselben Einstufung und ist ohne Freischalten sichtbar.

Damit sind Einsatzort und Zielklinik unterschiedlich streng geschützt. Das ist
kein neuer Bruch, sondern der bestehende Zuschnitt: Der Einsatzort verrät, wo
die Patientin war, die Zielklinik nur, wohin transportiert wurde — und ihr Name
benennt diesen Ort ohnehin schon.

### 3.6 Phasen

Die neun Phasen bleiben inhaltlich und in ihrer Nummerierung unverändert. Zwei
Beschriftungen werden neutral, damit sie für beide Arten passen und die Uhr die
Art nicht kennen muss:

| Phase | Bisher | Künftig |
|---|---|---|
| 1 | Frei | Frei |
| 2 | Alarmierung | Alarmierung |
| 3 | Abflug | **Ausrücken** |
| 4 | Ankunft Einsatzort | Ankunft Einsatzort |
| 5 | Ankunft PatientIn | Ankunft PatientIn |
| 6 | Transportbeginn | Transportbeginn |
| 7 | Landung Krankenhaus / Landung KKH | **Ankunft Klinik** |
| 8 | Übergabezeit | Übergabezeit |
| 9 | Endzeit des Einsatzes | Endzeit des Einsatzes |

Bei dieser Gelegenheit wird die bekannte Phase-10-Inkonsistenz aufgelöst
(Abschnitt 4.10).

### 3.7 Statistik und Suche

- **Statistik rechnet nach Diensttag.** Ein Einsatz um 01:30 Uhr eines Dienstes,
  der am Vortag begonnen hat, zählt zum Vortag.
- **Die Einsatzsuche filtert nach tatsächlichem Einsatzdatum.** Derselbe Einsatz
  ist dort unter seinem echten Datum zu finden.
- Dieser Unterschied ist beabsichtigt und wird im Handbuch erklärt.
- Die Einsatzsuche bekommt **Standort** und **Art** als gewöhnliche Filter.
- Die Art erscheint als **Symbol am Rettungsmittelnamen**, nicht als eigene
  Spalte — die Übersichten sind auf kleinen Bildschirmen ohnehin eng, und der
  Name verrät die Art meist schon. Neutrale Diensttage tragen ein eigenes, klar
  unterscheidbares Zeichen. Jedes Symbol braucht eine Textalternative, damit die
  Information nicht allein an der Grafik hängt.
- Eine Hilfsfrist-Kennzahl wird **nicht** eingeführt.

#### 3.7.1 Tabs in der Zeitraumübersicht

Die Monats- und Jahresübersicht wird nach Art geteilt. Die Tableiste richtet
sich danach, was im gewählten Zeitraum tatsächlich vorliegt:

| Im Zeitraum vorhanden | Anzeige |
|---|---|
| nur luftgebundene Diensttage | keine Tableiste, Ansicht wie bisher |
| nur bodengebundene Diensttage | keine Tableiste |
| nur neutrale Diensttage | keine Tableiste, Hinweis auf fehlende Zuordnung |
| beide Arten | drei Tabs: **Gemischt** (aktiv), Luftrettung, Bodengebundener Rettungsdienst |

Der Tab filtert die **gesamte** Ansicht: Kacheln, Einsatztabelle und Karte.

„Gemischt" enthält alle Diensttage des Zeitraums, auch die neutralen. Die
Summe der beiden Artentabs ist deshalb kleiner als „Gemischt", sobald neutrale
Diensttage vorliegen — genau deshalb weist „Gemischt" deren Anzahl mit einem
Hinweis aus und verlinkt auf die Zuordnung. Ohne diesen Hinweis wäre die
Abweichung nicht erklärbar.

Der gewählte Tab wird wie alle übrigen Filterzustände im URL-Fragment gehalten,
nicht als Abfrageparameter.

#### 3.7.2 Kacheln je Tab

Die Beschriftungen sind **tababhängig**: Der Luftrettungs-Tab behält die
gewohnte Flugterminologie, die übrigen Tabs sprechen neutral. Dadurch
entspricht der Luftrettungs-Tab exakt der heutigen Ansicht — dieselben zehn
Kacheln mit denselben Beschriftungen. Für eine rein luftgebundene Nutzung
ändert sich an der Auswertung nichts.

**Tab „Luftrettung" — 10 Kacheln**

| # | Kachel | Bedingung |
|---|---|---|
| 1 | Einsätze | — |
| 2 | Flugtage | — |
| 3 | Ø Einsätze / Flugtag | — |
| 4 | Sekundärtransporte | — |
| 5 | Flugkilometer gesamt | — |
| 6 | Längste Flugstrecke | — |
| 7 | Längste Einsatzdauer | — |
| 8 | Höchster Einsatzort | — |
| 9 | Anzahl Winden-Cycles | mindestens ein Windeneinsatz im Zeitraum |
| 10 | Ø Winden-Cycles / Flugtag | mindestens ein Windeneinsatz im Zeitraum |

Entspricht dem heutigen Bestand unverändert. Insbesondere gibt es hier
**keine** Fehleinsatz-Kachel, obwohl der Fehleinsatz-Haken luftgebunden sehr
wohl zur Verfügung steht (Entscheidung E17).

**Tab „Bodengebundener Rettungsdienst" — 8 Kacheln**

| # | Kachel | Herkunft |
|---|---|---|
| 1 | Einsätze | bestehend |
| 2 | Diensttage | bestehend, neutral beschriftet |
| 3 | Ø Einsätze / Diensttag | bestehend, neutral beschriftet |
| 4 | Sekundärtransporte | bestehend |
| 5 | Fehleinsätze | **neu** |
| 6 | Einsatzkilometer gesamt | bestehend, neutral beschriftet |
| 7 | Längste Einsatzstrecke | bestehend, neutral beschriftet |
| 8 | Längste Einsatzdauer | bestehend |

**Tab „Gemischt" — dieselben 8 Kacheln wie bodengebunden**

Neutral beschriftet, gerechnet über alle Diensttage des Zeitraums
einschließlich der neutralen. Die Fehleinsatz-Kachel zählt hier auch
luftgebundene Fehleinsätze mit, obwohl es im Luftrettungs-Tab keine
entsprechende Kachel gibt — die Zahl bleibt dadurch vollständig.

Höchster Einsatzort und Windenzahlen fehlen in „Gemischt", weil sie sich über
beide Arten nicht sinnvoll addieren lassen.

Liegt im Zeitraum nur eine Art vor, entfällt die Tableiste und es gilt die
Kachelmenge der jeweiligen Art. Liegen ausschließlich neutrale Diensttage vor,
gilt die Kachelmenge von „Gemischt".

Für die Windenkacheln gilt zusätzlich: Sie erscheinen nur, wenn im Zeitraum
**tatsächlich** Windeneinsätze dokumentiert sind. Ein windenfähiger
Hubschrauber ohne einen einzigen Windeneinsatz erzeugt keine Kachel. Damit
lässt sich „null Windeneinsätze" nicht mehr von „Winde nicht konfiguriert"
unterscheiden — das ist beabsichtigt, weil eine Dauerkachel mit dem Wert null
nur Platz kostet.

**Für Bergwacht gibt es bewusst keine Kachel.** Die Fähigkeit steuert weiterhin
die Felder im Einsatzformular (`cap_gate`), und die Bergwachtspalte der
Einsatztabelle folgt derselben datengetriebenen Regel wie die Windenspalte —
sie erscheint nur, wenn im Zeitraum entsprechende Einsätze vorliegen.

#### 3.7.3 Reichweite der Flugterminologie

Die tababhängige Beschriftung gilt **ausschließlich für die Kacheln**. Die
Einsatztabelle und der Export bleiben durchgehend neutral. Grund ist nicht
Geschmack, sondern Technik:

- Die Einsatztabelle wird von `missiontable.js` gemeinsam für `zeitraum.php`
  und `suche.php` erzeugt. In der Suche stehen beide Arten nebeneinander in
  **einer** Tabelle — artabhängige Spaltenköpfe sind dort gar nicht darstellbar.
- Artabhängige Exportspalten würden das Exportformat verdoppeln und die
  Importprofile zwingen, beide Varianten zu kennen. Der Rücklauf über
  Export und Import wäre nicht mehr verlustfrei.

Kacheln sind davon frei, weil sie je Tab neu aufgebaut werden und in keinen
Datenaustausch eingehen.

### 3.8 Menüstruktur der Stammdaten

Die Stammdatenverwaltung wird nach Standort gegliedert:

```
Einstellungen › Stammdaten
├── Standorte
│   ├── eigene Standorte (anlegen, bearbeiten, löschen, Koordinaten)
│   └── zentrale Standorte (auswählen/abwählen)
└── Je ausgewähltem Standort (aufklappbar)
    ├── Rettungsmittel      (Art, Rollen, Fähigkeiten)
    ├── Besatzung           (je Rolle)
    ├── Zielkliniken        (Name, optional Koordinaten)
    ├── Weitere Rettungsmittel
    └── Bergwacht           (nur bei luftgebundenen Rettungsmitteln)
```

Jeder Stammdatensatz hängt an genau einem Standort; eine standortübergreifende
Ebene gibt es nicht (Entscheidung E15).

Die Admin-Stammdatenverwaltung folgt derselben Gliederung, ohne den Block
„zentrale Standorte auswählen".

### 3.9 Umbenennung

Das Produkt heißt **Einsatzdokumentation Notarzt**. Durchgängig zu ersetzen:

| Bisher | Künftig |
|---|---|
| Flugtag | Diensttag |
| Hubschrauber / Maschine | Rettungsmittel |
| Einsatzdoku Luftrettung | Einsatzdokumentation Notarzt |

Nicht anzufassen: die Connect-IQ-App-ID, die Backup-Signatur `EDBAK2` und die
bestehenden Geräteschlüssel.

**Eine Ausnahme:** Die Kacheln des Luftrettungs-Tabs behalten die
Flugterminologie (Entscheidung E32, Abschnitt 3.7.3). Überall sonst — auch in
der Einsatztabelle und im Export — gilt die neutrale Sprache.

### 3.10 Migration

Der mechanische Teil läuft automatisch beim Update: Diensttage entstehen aus
den bestehenden Flugtagen, Start- und Endzeit werden aus erstem und letztem
Einsatz abgeleitet, die Besatzung wandert in die neue Struktur, die Art wird
auf „luftgebunden" gesetzt. Bestehende Stammdaten werden dem einzigen
vorhandenen Standort zugeordnet, sofern es genau einen gibt.

Was sich nicht ableiten lässt — Standort und Rettungsmittel je Diensttag sowie
die Standortzuordnung der Stammdaten, wo mehrere Standorte in Frage kommen —,
erledigt eine einmalige Nachbearbeitungsseite.

Nach der Umstellung ist ein frisches Backup nötig, weil ältere Backupdateien
nicht mehr eingelesen werden.

---

## 4. Technische Umsetzung

### 4.1 Rollenkatalog

Neue Konstante, analog zu `PHASE_LABELS` in `server/db.php`. Fest im Code, nicht
in der Datenbank.

```
CREW_ROLES = [
  'p1'      => ['label' => 'Pilot 1',    'kind' => 'air'],
  'p2'      => ['label' => 'Pilot 2',    'kind' => 'air'],
  'hems'    => ['label' => 'HEMS-TC',    'kind' => 'air'],
  'fr'      => ['label' => 'Flugretter', 'kind' => 'air'],
  'driver'  => ['label' => 'Fahrer',     'kind' => 'ground'],
  'trainee' => ['label' => 'Praktikant', 'kind' => 'ground'],
  'other'   => ['label' => 'Sonstige',   'kind' => 'both'],
]
```

Reihenfolge im Array = Anzeigereihenfolge.

### 4.2 Schemaänderungen

#### Neu: `vehicles` (ersetzt `aircraft`)

```
id           INT UNSIGNED PK
user_id      INT UNSIGNED NULL      -- NULL = zentral
base_id      INT UNSIGNED NOT NULL  -- jedes Rettungsmittel gehört einem Standort
name         VARCHAR(64) NOT NULL   -- bisher 'registration'
kind         ENUM('air','ground') NOT NULL
UNIQUE (user_id, name)
FK user_id -> users(id) ON DELETE CASCADE
FK base_id -> bases(id) ON DELETE CASCADE
```

Die Rollenspalten `p1, p2, hems, fr, other` und `is_default` entfallen.

#### Neu: `vehicle_roles`

```
vehicle_id   INT UNSIGNED NOT NULL
role_code    VARCHAR(16) NOT NULL
PRIMARY KEY (vehicle_id, role_code)
FK vehicle_id -> vehicles(id) ON DELETE CASCADE
```

#### Neu: `user_bases` (Auswahl zentraler Standorte)

```
user_id      INT UNSIGNED NOT NULL
base_id      INT UNSIGNED NOT NULL
PRIMARY KEY (user_id, base_id)
FK beide ON DELETE CASCADE
```

Eigene Standorte brauchen keinen Eintrag; sie gelten immer als ausgewählt.

#### Neu: `day_refs` (Uhr-Kennungen eines Diensttags)

```
id           INT UNSIGNED PK
day_id       INT UNSIGNED NOT NULL
device_id    INT UNSIGNED NULL      -- NULL = Gerät gelöscht
day_ref      VARCHAR(64) NOT NULL   -- von der Uhr erzeugt
UNIQUE (device_id, day_ref)
FK day_id    -> days(id) ON DELETE CASCADE
FK device_id -> devices(id) ON DELETE SET NULL
```

Bewusst eine eigene Tabelle statt einer Spalte in `days`: Nach dem
Zusammenführen trägt ein Diensttag legitim **mehrere** Kennungen. Damit
entfällt jede Umleitungslogik — `ingest.php` schlägt `(device_id, day_ref)`
nach und findet den richtigen Tag, auch wenn dieser inzwischen aufgenommen
wurde. Von Hand angelegte Diensttage haben keine Zeile hier.

#### Neu: `vehicle_capabilities` und `day_capabilities`

```
vehicle_capabilities:
  vehicle_id  INT UNSIGNED NOT NULL
  capability  VARCHAR(16) NOT NULL      -- 'winch' | 'bergwacht'
  PRIMARY KEY (vehicle_id, capability)
  FK vehicle_id -> vehicles(id) ON DELETE CASCADE

day_capabilities:
  day_id      INT UNSIGNED NOT NULL
  capability  VARCHAR(16) NOT NULL
  PRIMARY KEY (day_id, capability)
  FK day_id -> days(id) ON DELETE CASCADE
```

`day_capabilities` ist der eingefrorene Fähigkeitssatz. Anders als beim
Rollensatz lässt er sich nicht in eine bestehende Tabelle falten, weil zu einer
Fähigkeit kein Wert gehört — nur ihr Vorhandensein. Beim Anlegen eines
Diensttags werden die Fähigkeiten des gewählten Rettungsmittels kopiert;
spätere Änderungen am Rettungsmittel wirken nur auf neue Diensttage.

Fähigkeiten existieren ausschließlich an Rettungsmitteln mit `kind = 'air'`.
Beim Speichern eines bodengebundenen Rettungsmittels sind eventuell vorhandene
Fähigkeitszeilen zu entfernen.

#### Neu: `day_crew` und `mission_crew`

```
day_crew:
  day_id     INT UNSIGNED NOT NULL
  role_code  VARCHAR(16) NOT NULL
  name       VARCHAR(120) NULL
  PRIMARY KEY (day_id, role_code)
  FK day_id -> days(id) ON DELETE CASCADE

mission_crew:
  mission_id INT UNSIGNED NOT NULL
  role_code  VARCHAR(16) NOT NULL
  name       VARCHAR(120) NULL
  PRIMARY KEY (mission_id, role_code)
  FK mission_id -> missions(id) ON DELETE CASCADE
```

**Der Snapshot des Rollensatzes ist die Zeilenmenge in `day_crew`.** Beim
Anlegen eines Diensttags wird für jede Rolle des gewählten Rettungsmittels eine
Zeile mit `name = NULL` erzeugt. Welche Rollen ein Diensttag anbietet, ergibt
sich damit aus `day_crew` und nicht aus dem Rettungsmittel — spätere Änderungen
am Rettungsmittel wirken nur auf neue Diensttage. Eine zusätzliche
Snapshot-Tabelle ist dadurch nicht nötig.

`mission_crew` wird nur befüllt, wenn `missions.crew_override = 1`. Die
effektive Besatzung bleibt die bestehende COALESCE-Regel, jetzt über zwei
Tabellen statt über zwei Spaltensätze; `api/mission.php` liefert sie weiterhin
als `crew_effektiv`.

#### Geändert: `days`

| Änderung | Detail |
|---|---|
| entfernen | `UNIQUE KEY uq_user_day (user_id, day)` |
| entfernen | `crew_p1, crew_p2, crew_hems, crew_fr, crew_other` |
| entfernen | Altfelder `aircraft`, `base`, `crew` |
| umbenennen | `aircraft_id` → `vehicle_id`, FK auf `vehicles` |
| ergänzen | `started_at DATETIME NULL` (UTC) |
| ergänzen | `ended_at DATETIME NULL` (UTC) |
| ergänzen | `kind ENUM('air','ground') NULL` — Snapshot; **NULL = neutral, noch nicht zugeordnet** |
| ergänzen | `base_name VARCHAR(120) NULL` — eingefrorene Standortbezeichnung |
| ergänzen | `base_lat DECIMAL(9,6) NULL`, `base_lon DECIMAL(9,6) NULL` — eingefrorene Standortkoordinate |
| ergänzen | `vehicle_name VARCHAR(64) NULL` — eingefrorene Rettungsmittelbezeichnung |
| ergänzen | `INDEX (user_id, day)` |
| bleibt | `day DATE` als Sortier- und Anzeigedatum, **nicht mehr Schlüssel** |

Die vier Snapshot-Spalten setzen Entscheidung E8 um. Zusammen mit `kind`,
`day_crew` und `day_capabilities` ergibt sich **eine einzige Regel**: Alles, was
der Diensttag aus Standort und Rettungsmittel ableitet, wird beim Anlegen
kopiert. `base_id` und `vehicle_id` bleiben als Fremdschlüssel erhalten, dienen
aber nur noch dem Filtern und Auswerten — **niemals der Anzeige**.

Nebeneffekt: Das Löschen eines Standorts oder Rettungsmittels beschädigt keine
Historie mehr. Die Fremdschlüssel dürfen daher gefahrlos `ON DELETE SET NULL`
tragen; der Diensttag behält Bezeichnung, Koordinate, Art, Rollen und
Fähigkeiten.

#### Geändert: `missions`

| Änderung | Detail |
|---|---|
| ergänzen | `day_id INT UNSIGNED NULL`, FK auf `days(id)` ON DELETE SET NULL |
| entfernen | `day DATE` und `INDEX (user_id, day)` |
| entfernen | `crew_p1 … crew_other` |
| ergänzen | `INDEX (user_id, started_at)` |
| ergänzen | `transport_mode ENUM('air','ground','ambulant') NULL` |
| ergänzen | `na_escort TINYINT(1) NOT NULL DEFAULT 0` |
| ergänzen | `false_alarm TINYINT(1) NOT NULL DEFAULT 0` |
| ergänzen | `start_src ENUM('base','prev_site','prev_dest','manual') NULL` — Abfahrtortregel, siehe 3.5.1 |
| ergänzen | `dest_lat DECIMAL(9,6) NULL`, `dest_lon DECIMAL(9,6) NULL` — Zielklinik-Koordinate, Klartext wie `transport_dest` |
| bleibt | `transport_dest`, `schockraum` — künftig Unterfelder von `transport_mode` |

`ON DELETE SET NULL` ist bewusst gewählt: Der Papierkorb arbeitet mit
`deleted_at` und `deleted_with_day`, nicht mit echtem Löschen. Ein Kaskadieren
würde beim endgültigen Entfernen eines Diensttags die bestehende Logik in
`trash_lib.php` umgehen.

#### Geändert: `rest_segments`

Analog zu `missions`: `day_id` ergänzen, `day` und `INDEX (user_id, day)`
entfernen.

#### Geändert: Stammdatentabellen

`bases` erhält zwei Koordinatenspalten für den Abfahrtort aus Abschnitt 3.5.1:

```
lat  DECIMAL(9,6) NULL
lon  DECIMAL(9,6) NULL
```

Beide dürfen NULL bleiben; ein Standort ohne Koordinaten steht als Abfahrtort
schlicht nicht zur Auswahl. Die Erfassung nutzt dasselbe Adresssuch-Widget wie
der Einsatzort (Photon, `einsatz_form.php` ab Zeile 770) und wird in die
Standortpflege übernommen. Die Koordinaten werden beim Anlegen des Diensttags
nach `days.base_lat/base_lon` **eingefroren** (Entscheidung E8): Eine spätere
Korrektur wirkt nur auf neue Diensttage, ein tatsächlicher Wachenumzug ist als
eigener Standort abzubilden.

`transport_dests` erhält dieselben zwei Koordinatenspalten (Entscheidung E37):

```
lat  DECIMAL(9,6) NULL
lon  DECIMAL(9,6) NULL
```

**Auch diese Koordinaten werden am Einsatz eingefroren.** Das folgt derselben
Regel wie beim Standort (Entscheidung E8) und wird hier zusätzlich durch die
bestehende Verknüpfung erzwungen: `missions.transport_dest` ist ein
**Freitextfeld mit `<datalist>`-Vorschlägen** (`einsatz_form.php`, Zeile 325),
kein Fremdschlüssel. Eine Auflösung über Namensgleichheit wäre brüchig — ein
umbenannter Eintrag verlöre seine Koordinate, gleichnamige Einträge an
verschiedenen Standorten kollidierten. Hinzu kommt, dass die Ad-hoc-Erfassung
am Einsatz überhaupt keinen Stammdatensatz hat und ohnehin einsatzeigene
Spalten braucht.

Damit gilt für Standort, Rettungsmittel und Zielklinik dasselbe: Eine Korrektur
in den Stammdaten wirkt **nur auf neu erfasste Daten**.

Freitext bleibt uneingeschränkt möglich; ein Transportziel ohne Koordinaten ist
weiterhin ein gültiger Wert.

`crew_presets`, `bw_units`, `resources`, `transport_dests` erhalten je
`base_id INT UNSIGNED NOT NULL` mit FK auf `bases(id) ON DELETE CASCADE`
(Entscheidung E15). Das Löschen eines Standorts entfernt seine Stammdaten mit —
Diensttage bleiben davon unberührt, weil sie ihre Angaben eingefroren haben.

Vor dem Löschen eines Standorts ist die Anzahl der betroffenen
Stammdatensätze anzuzeigen und bestätigen zu lassen.

`crew_presets.role` wechselt von `ENUM('p1','p2','hems','fr','other')` auf
`role_code VARCHAR(16)`, damit neue Rollen ohne Schemaänderung möglich sind.
Der eindeutige Schlüssel wird zu `(user_id, base_id, role_code, name)`.

#### Geändert: `user_defaults`

`kind ENUM('base','aircraft')` → `ENUM('base','vehicle')`. Beim Speichern ist zu
prüfen, dass das Standard-Rettungsmittel zum Standard-Standort gehört, also
dessen `base_id` auf den Standard-Standort zeigt.

### 4.3 Feldkatalog (`server/mission_fields.php`)

Zwei neue Schlüssel:

- **`kind_gate` => 'air' | 'ground'** — Feld nur zeigen, wenn der Diensttag diese
  Art hat. Verhalten exakt wie `role_gate`: rendern und verstecken, nie
  weglassen; ein bereits belegtes Feld bleibt sichtbar. Ist `days.kind` NULL
  (neutraler Diensttag), greift kein `kind_gate`-Feld — beide Artenblöcke
  bleiben verborgen, belegte Felder aber weiterhin sichtbar.
- **`cap_gate` => 'winch' | 'bergwacht'** — Feld nur zeigen, wenn der Diensttag
  diese Fähigkeit in `day_capabilities` trägt. Gleiches Verhalten wie
  `role_gate` und `kind_gate`. Da Fähigkeiten nur an luftgebundenen
  Rettungsmitteln vorkommen, ersetzt `cap_gate` bei den betroffenen Feldern die
  Artprüfung vollständig — `winch` und `bergwacht` erhalten **kein**
  zusätzliches `kind_gate`.
- **`show_if` => ['field' => '<spalte>', 'not_in' => ['<wert>', …]]** — Unterfeld
  nur zeigen, wenn das übergeordnete Auswahlfeld nicht einen der genannten Werte
  hat.

**Wichtiger Befund aus dem Audit:** Bedingte Unterfelder funktionieren derzeit
ausschließlich unter Checkboxen. Unter einem `select` werden Kinder immer
gerendert und immer gespeichert — siehe `server/einsatz_form.php`, Lesepfad
Zeilen 98–124 (`$readField`, Sonderbehandlung nur für `type === 'checkbox'`) und
Renderpfad Zeilen 381–401. Die Umsetzung von `show_if` erfordert daher:

1. **Lesepfad:** `$readField` muss `show_if` auswerten und den `$parentOn`-Wert
   entsprechend setzen, damit ausgeblendete Unterfelder geleert werden statt
   Geisterinhalte zu behalten.
2. **Renderpfad:** Der Select braucht dieselbe `data-target`-Mechanik wie die
   `parentcheck`-Klasse, plus ein `change`-Ereignis, das den Kindercontainer
   ein- und ausblendet.
3. `role_gate` liest die Rollen künftig aus `day_crew` des Diensttags statt aus
   den Rollenspalten des Hubschraubers. Hat ein neutraler Diensttag keine
   `day_crew`-Zeilen, hat der Haken „Abweichende Besatzung" keine Unterfelder;
   er wird dann samt Kindern verborgen, sofern nicht bereits belegt.

Neue Einträge im Katalog:

```
'transport_mode' => [
    'label' => 'Transport', 'type' => 'select',
    'options' => ['Luft', 'Boden', 'Ambulant'],
    'children' => [
        'na_escort'      => ['label' => 'NA-Begleitung', 'type' => 'checkbox',
                             'show_if' => ['field' => 'transport_mode', 'not_in' => ['Ambulant']]],
        'transport_dest' => [ … bestehender Eintrag, verschoben,
                             'show_if' => ['field' => 'transport_mode', 'not_in' => ['Ambulant']]],
    ],
],
'false_alarm' => [
    'label' => 'Fehleinsatz / Storno / Abbruch', 'type' => 'checkbox',
    'day_col' => 'check', 'day_label' => 'Fehl&shy;einsatz',
],
```

`schockraum` bleibt Unterfeld von `transport_dest`. `winch` erhält
`'cap_gate' => 'winch'`, `bergwacht` erhält `'cap_gate' => 'bergwacht'`.

`transport_dest` wechselt vom reinen Textfeld auf das Ortswidget: Freitext und
`<datalist>`-Vorschläge bleiben unverändert erhalten, dazu kommt der optionale
Koordinaten-Chip. Übernimmt die Nutzerin einen Vorschlag, dessen
Stammdatensatz Koordinaten führt, werden diese vorbelegt und bleiben
überschreibbar. Die Vorschlagsabfrage in `einsatz_form.php` (Zeile 325) muss
dafür `lat` und `lon` mitliefern statt nur `name`.

### 4.4 Diensttag-Kennung und JSON-Vertrag

Der Vertrag steigt auf **Version 1.2**. Änderungen:

1. Neues Feld **`day_ref`** in `mission` und `rest_segment`: eine von der Uhr bei
   „Einsatztag starten" erzeugte, gerätweit eindeutige Kennung, die für alle
   Uploads dieses Dienstes unverändert mitgeschickt wird. Gleiches Muster wie
   `client_ref`, gleiche Idempotenz-Eigenschaft.
2. Das Feld `day` bleibt erhalten und behält seine Bedeutung (Datum des
   Dienstbeginns), ist aber **nicht mehr der Zuordnungsschlüssel**, sondern nur
   noch Sortier- und Anzeigedatum eines neu angelegten Diensttags.
3. Abschnitt 7 des Vertrags: Phase 10 entfällt, Beschriftungen von Phase 3 und 7
   angepasst. Abschnitt 3 der Einleitungssatz („Gesendet bei Phase 10") wird auf
   „Gesendet beim Abschluss des Einsatzes (`final: true`)" korrigiert.

Serverseitig in `server/ingest.php`:

- `day_ref` in `day_refs` nachschlagen. Treffer → zugehörigen Diensttag
  verwenden. Kein Treffer → neuen Diensttag anlegen, `day_refs`-Zeile schreiben,
  `started_at` auf den frühesten bekannten Zeitpunkt setzen.
- Fehlt `day_ref` (ältere Uhr-Version), gilt der bisherige Weg über
  `(user_id, day)`: vorhandenen Diensttag dieses Datums verwenden, sonst neu
  anlegen. Diese Rückfallebene bleibt dauerhaft bestehen.
- `ended_at` des Diensttags wird beim Abschluss eines Einsatzes oder
  Ruhe-Segments fortgeschrieben, wenn der neue Wert später liegt.

Auf der Uhr (`watch/source/`): `day_ref` wird zusammen mit dem Dienstzustand in
`K_STATE` abgelegt und von `Uploader.mc` an beiden Nachrichtentypen mitgesendet.
Die Uhr erfährt nichts über die Einsatzart.

### 4.5 Zusammenführen — Ablauf

Neue Serverfunktion, ein Codepfad für Uhr- und manuelle Tage:

Einstieg ausschließlich aus dem geöffneten Diensttag (Entscheidung E25); dieser
ist stets der Zieltag. Die Auswahlliste zeigt Diensttage derselben Nutzerin in
zeitlicher Nähe, ohne Papierkorbeinträge und ohne den Zieltag selbst.

1. Prüfen: beide Diensttage gehören derselben Nutzerin, keiner liegt im
   Papierkorb, die Arten sind vereinbar. Vereinbar heißt: gleiche `kind`, oder
   mindestens eine der beiden ist NULL. `air` gegen `ground` wird abgewiesen.
   Ergebnisart ist der nicht-NULL-Wert, sonst NULL.
2. Vorschau erzeugen: resultierender Zeitraum, Anzahl Einsätze, Anzahl
   Ruhe-Segmente, abweichende Metadaten (Standort, Rettungsmittel, Besatzung).
3. Nach Bestätigung in einer Transaktion:
   - `missions.day_id` und `rest_segments.day_id` des aufgenommenen Tags auf den
     Zieltag umsetzen.
   - `day_refs`-Zeilen des aufgenommenen Tags auf den Zieltag umhängen.
   - `started_at` = Minimum beider Werte, `ended_at` = Maximum, `day` = Datum des
     neuen `started_at`.
   - Bei Abweichungen die in Schritt 2 gewählten Metadaten setzen; `day_crew`
     entsprechend neu schreiben.
   - Notizen aneinanderhängen.
   - Aufgenommenen Diensttag endgültig entfernen (nicht in den Papierkorb).
4. Die `deleted_refs`-Sperrliste wird **nicht** bedient — die Kennungen leben in
   `day_refs` weiter und zeigen jetzt auf den Zieltag. Genau deshalb ist die
   Umleitung über eine eigene Tabelle gelöst.

### 4.6 Statistik, Suche, Anzeige

- `server/index.php`: Aggregation über `day_id` statt über `missions.day`. Die
  Seite ist die Tagesübersicht und zeigt den gewählten beziehungsweise zuletzt
  dokumentierten Diensttag; sie enthält **keine** Statistikkacheln und bekommt
  daher auch keine Tabs.
- `server/zeitraum.php` und `server/api/range.php`: Aggregation über `day_id`.
  Neu die Tableiste aus Abschnitt 3.7.1 samt Kachellogik aus 3.7.2. Der
  Endpunkt liefert dazu je Einsatz die Art des zugehörigen Diensttags sowie die
  Kennzeichen für Winden- und Bergwachteinsatz, damit die Auswertung ohne
  weitere Abfrage im Browser erfolgen kann.
- Sichtbarkeit der Kacheln wird **datengetrieben** entschieden: Eine
  fähigkeitsabhängige Kachel erscheint nur, wenn im Zeitraum mindestens ein
  entsprechender Einsatz vorliegt — nicht schon, wenn `day_capabilities` die
  Fähigkeit führt. Die Extremwert-Kacheln behalten ihr bestehendes Verhalten
  (`setzeExtremKachel` in `zeitraum.php`, Zeile 171): ohne Kandidat bleiben sie
  stumm statt zu verschwinden.
- `server/api/suchindex.php`: Der Join über den natürlichen Schlüssel
  `(user_id, day)` (dort kommentiert um Zeile 47) wird durch `day_id` ersetzt.
  Das Suchdatum eines Einsatzes wird aus `started_at` in Ortszeit abgeleitet,
  nicht aus dem Diensttagsdatum.
- `server/site_elevation_lib.php` bleibt unverändert (Referenz Phase 5, Fallback
  Phase 6). Nur die Anzeige von `site_ele_m` und `ascent_m` wird an
  `days.kind = 'air'` gebunden.
- `server/assets/missiontable.js`: neue Spalten Art und Fehleinsatz;
  `pfeilInitial:false` für `zeitraum.php` bleibt unverändert erhalten.

#### 4.6.1 Abfahrtort und Zielklinik: Speicherung und Auflösung

Die Regel steht in `missions.start_src` im Klartext, die Koordinate je nach
Regel an unterschiedlicher Stelle. Das ist keine Unsauberkeit, sondern folgt
dem Vertraulichkeitsgrad:

| `start_src` | Koordinate kommt aus | Sichtbarkeit |
|---|---|---|
| `base` | `days.base_lat/base_lon` — eingefroren beim Anlegen des Diensttags | Klartext |
| `prev_site` | Einsatzort des vorherigen Einsatzes desselben Diensttags | verschlüsselt |
| `prev_dest` | `dest_lat/dest_lon` des vorherigen Einsatzes desselben Diensttags | Klartext |
| `manual` | neuer Schlüssel `start` im `pat_blob` | verschlüsselt |
| NULL | — | keine Linie |

Die beiden anderen Punkte der Linie:

| Punkt | Koordinate kommt aus | Sichtbarkeit |
|---|---|---|
| Einsatzort | `pat_blob.loc.lat/lon` | verschlüsselt |
| Zielklinik | `missions.dest_lat/dest_lon` | Klartext |

Der Klartextwert verrät nur die **Regel**, keinen Ort. Ein Standort ist ohnehin
kein Geheimnis; ein Einsatzort ist es sehr wohl, weshalb `prev_site` und
`manual` niemals eine Koordinate in eine Klartextspalte schreiben. `prev_dest`
ist davon ausgenommen, weil es auf `dest_lat/dest_lon` des Vorgängers zeigt und
diese Spalten ohnehin im Klartext stehen (Entscheidung E40).

**Neuer Blob-Schlüssel.** Analog zum bestehenden `loc`:

```
o.start = { addr: "…", lat: <float>, lon: <float> }
```

Nur bei `start_src = 'manual'` belegt. Es gilt dieselbe Regel wie beim
Einsatzort (`einsatz_form.php`, Zeile 566): Koordinaten ohne Bezeichnung werden
abgewiesen, sonst stünde in den Listen wieder ein Zahlenfragment.

**Auflösung von `prev_site` und `prev_dest`.** Der vorherige Einsatz ist in
beiden Fällen der zeitlich unmittelbar vorangehende Einsatz desselben
Diensttags — Papierkorbeinträge zählen nicht mit. Bei `prev_site` stammt die
Koordinate aus `o.loc` seines Blobs oder, falls die Uhr lief, aus der
Phasenkoordinate; bei `prev_dest` aus seinen Spalten `dest_lat/dest_lon`.

Beide Wege verlangen, dass die Einsatzansicht den Vorgängereintrag mitlädt:
`api/day.php` liefert die Einsätze eines Diensttags ohnehin gemeinsam, die
Auflösung geschieht also im Browser ohne Zusatzabfrage. Findet sich keine
Koordinate, entsteht keine Linie.

`prev_dest` hat dabei eine praktische Eigenschaft: Der Abfahrtort ist ohne
Freischalten auflösbar. Die Linie bleibt trotzdem verschlüsselt, weil ihr
mittlerer Stützpunkt der Einsatzort ist.

**Zeichnen.** In `einsatz.php` und `index.php` nach dem Entschlüsseln, direkt
neben dem bestehenden Einsatzort-Pin (`einsatz.php`, Zeile 357). Als
`L.polyline` mit `dashArray`, in einer Farbe, die sich vom Track-Orange
`#FF8F1F` unterscheidet. Die Stützpunkte sind in dieser Reihenfolge Abfahrtort,
Einsatzort, Zielklinik; ein Punkt ohne Koordinate entfällt. Bedingungen:

- `m.track` ist leer, **und**
- der Einsatzort hat Koordinaten, **und**
- mindestens ein weiterer Punkt hat Koordinaten.

Ohne Einsatzort entsteht keine Linie, auch wenn Abfahrtort und Zielklinik
belegt sind. Die Luftlinie wird als Summe der Großkreisdistanzen berechnet und
ausdrücklich als Luftlinie benannt — kein Umwegfaktor, weil eine gerechnete
Fahrstrecke Genauigkeit vortäuschte, die es nicht gibt.

Der **Zielklinik-Pin** wird unabhängig davon gesetzt, sobald `dest_lat` belegt
ist: Er stammt aus einer Klartextspalte und braucht kein Freischalten. Linie
und Einsatzort-Pin dagegen schon.

**Keine Höhenermittlung.** `site_ele_m` stammt aus der Höhe des zeitlich
nächstgelegenen **Trackpunkts** (`site_elevation_lib.php`). Ohne Track gibt es
keine Höhe. Manuelle Orte liefern deshalb keine Einsatzorthöhe, und die Kachel
„Höchster Einsatzort" bleibt davon unberührt. Ein externer Höhendienst wird
nicht angebunden.

**Export und Import.** `start_src`, `dest_lat` und `dest_lon` werden als
gewöhnliche Spalten geführt — sie liegen im Klartext, brauchen also keine
`sensitive`-Markierung. Der Blob-Schlüssel bekommt drei Felder analog zu
`pat_ort_*`, diese ebenfalls als `sensitive` markiert:

```
pat_start_adresse   -> pat.start.addr     (sensitive)
pat_start_lat       -> pat.start.lat      (sensitive)
pat_start_lon       -> pat.start.lon      (sensitive)
ziel_lat            -> missions.dest_lat
ziel_lon            -> missions.dest_lon
abfahrt_regel       -> missions.start_src
```

Zu ergänzen in `assets/export.js` (Feldkatalog ab Zeile 582) und
`assets/import_profiles.js` (ab Zeile 210). Das Backup führt den `pat_blob` roh
und braucht dafür keine Anpassung; die neuen Klartextspalten und die
Koordinaten in `transport_dests` und `bases` müssen dagegen in die
Backup-Tabellenliste aufgenommen werden.

### 4.7 Phase-10-Bereinigung
Der Auditstand ist eindeutig: `server/ingest.php` Zeile 91 verwirft Phasen
außerhalb 2–9 stillschweigend, `watch/source/Const.mc` kennt nur 1–9. Nicht
angepasst sind `server/db.php` Zeile 160 (`PHASE_LABELS` enthält noch
`10 => 'Beendigung Einsatz'`), `docs/JSON-Vertrag.md` Abschnitte 3 und 7 sowie
`docs/Handbuch.md` Zeile 172 („Die Uhr lädt selbstständig hoch: Einsätze bei
Phase 10").

Zur Einordnung: Phase 10 „Beendigung Einsatz" war ursprünglich ein zehnter
Zeitstempel, der den Einsatz abschloss, die Uhr auf Phase 1 zurückschaltete,
den Upload anstieß und das `ended_at` lieferte. Sie wurde mit der Migration
`2026_07_19_phase10_entfernen` abgeschafft; seither stammt `ended_at` aus
Phase 9 und der Abschluss ist eine bestätigte Aktion statt eines Zeitstempels.

Auflösung: Phase 10 aus `PHASE_LABELS` und aus dem JSON-Vertrag entfernen. Kein
Verhaltenswechsel, reine Bereinigung. Vor dem Entfernen ist zu prüfen, ob
`PHASE_LABELS[10]` noch irgendwo gelesen wird.

### 4.8 Backup und Export

- Backup-Formatversion **5 → 6**. Signatur `EDBAK2` und Verschlüsselung
  unverändert (`server/assets/crypto.js`, Zeilen 109–116).
- Feld `app` wechselt von `einsatzdoku-luftrettung` auf `einsatzdoku-notarzt`.
- Neu im Backup: `days.started_at`, `days.ended_at`, `days.kind`, `day_refs`,
  `day_crew`, `mission_crew`, `day_capabilities`, `vehicles` samt
  `vehicle_roles` und `vehicle_capabilities`, `user_bases`, `base_id` aller
  Stammdatentabellen, die drei neuen Einsatzfelder.
- **`day_refs` muss ins Backup**, sonst legt ein später eintreffender Upload nach
  einer Wiederherstellung den Diensttag erneut an.
- Wiederherstellung von Formatversion < 6 wird mit klarer Meldung abgelehnt
  (Entscheidung E23). Die Ableitungsregel `edbak_origin_edited()` in
  `server/backup_lib.php` bleibt bestehen, weil sie auch für Importe gilt.
- Export (`server/api/export_data.php`, `server/assets/export.js`): Spaltenköpfe
  neutralisieren, Spalten Art, Transportart, NA-Begleitung, Fehleinsatz
  ergänzen, Besatzungsspalten aus dem Rollenkatalog erzeugen statt fest zu
  verdrahten.
- **Bekannte Altlast, hier nicht behoben:** Das Feld `herkunft` in
  `api/export_data.php` nutzt weiterhin die alte `client_ref`-Präfix-Heuristik
  statt der Spalte `origin`. Bleibt bewusst außen vor und braucht ein eigenes
  Konzept.

### 4.9 Migration

Eine Migration in `server/update.php`, Kennung `2026_XX_XX_notarzt_erweiterung`.
Die Kennung ist zusätzlich in `server/schema.sql` unter `schema_migrations`
einzutragen, sonst läuft sie bei jeder Neuinstallation unnötig — siehe den
Hinweis im Kopf jener Tabelle.

Reihenfolge:

1. Neue Tabellen anlegen: `vehicle_roles`, `user_bases`, `day_refs`, `day_crew`,
   `mission_crew`.
2. `aircraft` → `vehicles` umbenennen, `kind = 'air'` setzen, `registration` →
   `name`, `base_id` ergänzen, Rollenspalten nach `vehicle_roles` überführen,
   danach entfernen. Für `base_id` gilt dieselbe zweistufige Regel wie in
   Schritt 8.
2a. **Fähigkeiten für Bestandsdaten:** Alle bestehenden Rettungsmittel erhalten
   in `vehicle_capabilities` beide Einträge `winch` und `bergwacht`, und jeder
   bestehende Diensttag in `day_capabilities` ebenfalls beide. Bisher waren die
   Felder bei jedem Hubschrauber verfügbar; ohne diesen Schritt verschwände
   vorhandene Winden- und Bergwachtdokumentation aus der Anzeige. Das Ausdünnen
   auf die tatsächlich zutreffenden Fähigkeiten bleibt eine bewusste
   Nachpflege, die nur neue Diensttage betrifft.
3. `days`: neue Spalten ergänzen, `aircraft_id` → `vehicle_id`.
   `started_at` = frühestes `started_at` der Einsätze und Ruhe-Segmente des
   Tages, ersatzweise `day` 00:00 UTC. `ended_at` = spätestes `ended_at`,
   ersatzweise NULL. Anschließend `uq_user_day` entfernen.
3a. **Snapshot-Spalten befüllen (E8):** `base_name`, `base_lat`, `base_lon` aus
   dem verknüpften Standort, `vehicle_name` aus dem verknüpften Rettungsmittel.
   Fehlt die Verknüpfung, bleiben die Spalten leer. Da die Koordinaten in
   Schritt 7a leer angelegt werden, bleiben `base_lat/base_lon` für Bestandsdaten
   zunächst NULL; sie lassen sich am Diensttag nachtragen.
4. `days.kind` und `day_crew` setzen. **Der neutrale Zustand aus E26 gilt für
   neue Diensttage, nicht rückwirkend** — Bestandsdaten dürfen keine Besatzung
   verlieren. Deshalb dreistufig:
   - Diensttag hat ein Rettungsmittel → `kind = 'air'`, `day_crew`-Zeilen für
     alle Rollen des Rettungsmittels, auch leere mit `name = NULL`.
   - Kein Rettungsmittel, aber mindestens eine `crew_*`-Spalte belegt →
     `kind = 'air'`, `day_crew`-Zeilen nur für die belegten Rollen. Damit bleibt
     die vorhandene Besatzung erhalten.
   - Kein Rettungsmittel und keine Besatzung → `kind = NULL`, keine
     `day_crew`-Zeilen. Der Diensttag ist neutral und erscheint in der
     Nachbearbeitung.

   Danach `days.crew_*` entfernen.
5. `missions.day_id` und `rest_segments.day_id` über `(user_id, day)` füllen,
   danach `day` und die zugehörigen Indizes entfernen.
6. `missions.crew_*` nach `mission_crew` überführen, aber **nur für Einsätze mit
   `crew_override = 1`**. Danach Spalten entfernen.
7. Neue Einsatzspalten ergänzen. `transport_mode` bleibt NULL — es wird bewusst
   nicht aus `transport_dest` erraten. `start_src`, `dest_lat` und `dest_lon`
   bleiben ebenfalls NULL; Koordinaten lassen sich für Altdaten nicht ableiten
   und werden nicht über eine Adressanfrage nachgeschlagen.
7a. `bases` und `transport_dests` erhalten je `lat` und `lon` (NULL). Keine
   automatische Befüllung: Ein zentral geführter Standort ist nicht eindeutig
   geokodierbar, und eine falsche Koordinate zöge stillschweigend falsche Linien
   nach sich.
8. `base_id` in `crew_presets`, `bw_units`, `resources`, `transport_dests`
   ergänzen. **Der Standortbezug ist verbindlich (E15), Bestandsdaten haben
   aber keinen.** Deshalb zweistufig:
   - Hat die Nutzerin genau einen Standort, werden alle ihre Stammdatensätze
     diesem zugeordnet. Das ist der Regelfall und läuft ohne Nachfrage.
   - Hat sie mehrere oder keinen, wird die Spalte zunächst nullbar angelegt und
     bleibt leer; die Zuordnung erledigt die Nachbearbeitungsseite. Erst danach
     wird sie auf `NOT NULL` gesetzt.

   Zentrale Stammdaten (`user_id IS NULL`) folgen derselben Regel bezogen auf
   die zentralen Standorte. Gibt es keinen einzigen Standort, legt die Migration
   **keinen** an — ein erfundener Sammelstandort wäre genau die zweite Ebene,
   die E15 vermeiden soll. `crew_presets.role` → `role_code`.
9. `user_defaults.kind` von `aircraft` auf `vehicle` umstellen.
10. `user_bases`: für jeden zentralen Standort, der in einem bestehenden
    Diensttag verwendet wurde, eine Zeile anlegen — sonst verschwindet er aus
    den Auswahllisten.
11. Aufräumen: Altspalten `days.aircraft`, `days.base`, `days.crew`,
    `bases.is_default`, `vehicles.is_default` entfernen.

**Nicht rekonstruierbar:** `day_refs` bleibt für Bestandsdaten leer, weil die
Uhr bisher keine Dienstkennung vergeben hat. Ein nach dem Update noch
gepufferter Upload einer alten Uhr-Version fällt auf die Rückfallebene aus
Abschnitt 4.4 zurück. Das ist hinzunehmen und im Changelog zu vermerken.

**Nachbearbeitungsseite:** Eine einmalige, nur für angemeldete Nutzer
erreichbare Seite mit zwei Listen. Die erste zeigt alle Diensttage ohne
Standort oder ohne Rettungsmittel mit Datum, Zeitraum und Einsatzzahl und lässt
beides nachtragen; die Zuordnung schreibt `vehicle_id`, `base_id`, `kind`, die
Snapshot-Spalten `base_name`, `base_lat`, `base_lon`, `vehicle_name` sowie die
zugehörigen `day_crew`- und `day_capabilities`-Zeilen fort. Die zweite listet
Stammdatensätze ohne Standortzuordnung — Rettungsmittel, Zielkliniken,
Besatzungs-Vorbelegungen, weitere Rettungsmittel, Bergwacht-Bereitschaften —
und lässt sie einem Standort zuweisen.

Die Seite verschwindet, sobald beide Listen leer sind. Erst danach wird
`base_id` in den Stammdatentabellen auf `NOT NULL` gesetzt (Schritt 8).

Der `pat_blob` wird an keiner Stelle berührt. Die gesamte Migration läuft
serverseitig ohne Entschlüsselung im Browser.

### 4.10 Umbenennungen im Code

| Bisher | Künftig |
|---|---|
| `server/flugtag_neu.php` | `server/diensttag_neu.php` |
| `server/flugtag_loeschen.php` | `server/diensttag_loeschen.php` |
| `trash_scope_day`, `trash_delete_day`, … | Signatur `string $day` → `int $dayId` |

Die Papierkorb-Funktionen in `server/trash_lib.php` arbeiten durchgängig mit dem
Datum als Schlüssel (Zeilen 42, 89, 117, 161). Sie müssen auf `day_id`
umgestellt werden; die Logik mit `deleted_with_day` und der Sperrliste bleibt
inhaltlich unverändert.

### 4.11 Aufgehobene Architekturregel

Die bisherige Regel „`days` und `missions` dürfen nie gejoint werden" entstand
allein daraus, dass beide Tabellen gleichnamige `crew_*`-Spalten trugen. Mit
deren Wegfall entfällt der Grund. **Die Regel wird ausdrücklich aufgehoben und
in `docs/Technik.md` als aufgehoben dokumentiert**, damit sie nicht als
Halbwissen zurückkehrt. Der Join `missions.day_id = days.id` ist ab sofort der
vorgesehene Weg.

Unverändert bestehen bleiben dagegen:

- Der Null-Schutz für `pat_blob` in `server/einsatz_form.php` darf nicht
  entfernt werden.
- Filterzustände werden weiterhin über URL-Fragmente statt Query-Parameter
  transportiert, damit keine sensiblen Suchbegriffe in Serverprotokolle geraten.
- Eine serverseitige Suche in verschlüsselten Feldern ist und bleibt
  architektonisch ausgeschlossen.
- `pfeilInitial:false` in `missiontable.js` für `zeitraum.php`.

### 4.12 Betroffene Dateien

**Schema und Migration:** `server/schema.sql`, `server/update.php`

**Kern:** `server/db.php`, `server/mission_fields.php`, `server/ui.php`,
`server/trash_lib.php`, `server/backup_lib.php`, `server/ingest.php`,
`server/site_elevation_lib.php` (nur Anzeigebindung)

**Oberfläche:** `server/index.php`, `server/einsatz.php`,
`server/einsatz_form.php`, `server/einsatz_loeschen.php`,
`server/flugtag_neu.php`, `server/flugtag_loeschen.php`,
`server/einstellungen.php`, `server/admin_stammdaten.php`,
`server/papierkorb.php`, `server/suche.php`, `server/zeitraum.php`,
`server/import.php`, plus neue Seiten für Zusammenführen und Nachbearbeitung

**Schnittstellen:** `server/api/day.php`, `server/api/mission.php`,
`server/api/range.php`, `server/api/suchindex.php`,
`server/api/export_data.php`, `server/api/import_commit.php`,
`server/api/backup_data.php`, `server/api/backup_restore.php`

**Skripte:** `server/assets/daylist.js`, `server/assets/missiontable.js`,
`server/assets/export.js`, `server/assets/import_profiles.js`,
`server/assets/import_ui.js`, `server/assets/forms.js`

**Uhr:** `watch/source/Const.mc`, `watch/source/Model.mc`,
`watch/source/Uploader.mc`, `watch/source/StartView.mc`,
`watch/resources/strings/strings.xml`

**Dokumentation:** `docs/CHANGELOG.md`, `docs/Handbuch.md`, `docs/Technik.md`,
`docs/JSON-Vertrag.md`, `docs/Backup-Format.md`, `docs/Export-Format.md`,
`docs/Uhr-Layout.md`, `docs/Geraete-Eingabe.md`, `README.md`

---

## 5. Vorprüfungen — vor der Umsetzung durchführen und melden

Bei jedem dieser Punkte gilt: **prüfen, Ergebnis berichten, erst nach
Bestätigung weiterarbeiten.**

- **V1** Vollständige Fundstellenliste für `crew_p1`, `crew_p2`, `crew_hems`,
  `crew_fr`, `crew_other` erstellen. Das Audit fand elf Dateien; vor dem
  Entfernen der Spalten ist die Liste zu verifizieren, insbesondere in
  `assets/export.js` und `assets/import_profiles.js`.
- **V2** Vollständige Fundstellenliste für `missions.day` und den natürlichen
  Schlüssel `(user_id, day)` erstellen. Das Audit fand fünfzehn Dateien.
- **V3** Prüfen, ob `PHASE_LABELS[10]` noch irgendwo gelesen wird, bevor der
  Eintrag entfernt wird.
- **V4** Die Erweiterung von `einsatz_form.php` auf wertabhängige Unterfelder am
  bestehenden Windeneinsatz-Block gegenprüfen: Checkbox-Kinder müssen sich
  unverändert verhalten. Vorgehen vor der Umsetzung beschreiben.
- **V5** Prüfen, welche Importprofile in `assets/import_profiles.js` auf
  Besatzungsspalten oder das Flugtagsdatum abbilden, und wie sie nach der
  Umstellung aussehen müssen.
- **V6** Prüfen, ob `api/day.php` und `index.php` die Tagestabellenspalten
  weiterhin fest verdrahten (Kommentar in `mission_fields.php` zum
  `day_col`-Eintrag von `crew_override` legt das nahe). Falls ja: unverändert
  lassen, nicht im Rahmen dieses Umbaus generisch machen.
- **V7** Prüfen, ob `api/range.php` die Kennzeichen für Winden- und
  Bergwachteinsatz bereits mitliefert oder ob die Antwort erweitert werden muss.
  Die Kachelsichtbarkeit soll ohne Zusatzabfrage im Browser entscheidbar sein.

- **V8** Prüfen, ob sich das Einsatzort-Widget aus `einsatz_form.php`
  (Adresssuche, `locparse.js`, Plus-Code-Erkennung, Chip-Darstellung) mehrfach
  einsetzen lässt: zweimal auf der Einsatzseite (Abfahrtort, Zielklinik) und je
  einmal in der Standort- und Zielklinikpflege, dort jeweils für Konto- und
  Adminebene. Die vorhandene Umsetzung arbeitet mit festen Element-Kennungen
  (`loclat`, `loclon`, `locaddr`, `locstate`); vor der Umsetzung ist zu
  beschreiben, wie daraus eine mehrfach verwendbare Komponente wird, ohne das
  bestehende Verhalten zu verändern. Besonders zu klären: Die Zielklinik trägt
  zusätzlich eine `<datalist>`, die es beim Einsatzort nicht gibt.
---

## 5a. Ergebnis der Vorprüfungen

Durchgeführt vor Beginn der Etappe 1, auf dem Stand Web 5.10.0. **Nicht erneut
erheben** — die Etappen 2 und 3 setzen darauf auf. Zeilennummern beziehen sich
auf den Stand vor der Umsetzung.

### V1 — Fundstellen `crew_p1 … crew_other`

**16 Dateien, 159 Fundstellen** (das Audit nannte elf Dateien). Nach Dichte:
`api/import_commit.php` (32), `assets/export.js` (16), `api/export_data.php` (16),
`assets/import_profiles.js` (11), `schema.sql` (10), `assets/import_ui.js` (10),
`update.php` (10), `api/day.php` (8), `backup_lib.php` (7), `mission_fields.php` (5),
`api/suchindex.php` (3), `api/mission.php` (3), dazu vier Dokumentationsdateien.

### V2 — Fundstellen `missions.day` und Schlüssel `(user_id, day)`

**20 Dateien, 80 Fundstellen.** Als Schlüssel tatsächlich benutzt in:
`tageszuordnung_lib.php:40,146,228`, `api/import_commit.php:121`, `api/day.php:93`,
`api/suchindex.php:50`, `flugtag_neu.php:27`, `trash_lib.php:95,222`,
`backup_lib.php:435`, `update.php:116`, `schema.sql:81,125,231`. Als dokumentierte
Annahme zusätzlich in `flugtag_datum.php:45` und `einsatz_verschieben.php:61`.

`tageszuordnung_lib.php` (398 Zeilen) ist im Konzept nirgends erwähnt, ist aber
die zentrale Stelle des Anlegens und Zuordnens von Diensttagen.

### V3 — `PHASE_LABELS[10]`: bereits erledigt

Siehe Berichtigung B2. Alle Leser sind sicher: `einsatz_form.php:716` zählt fest
`p = 2..9`, `api/mission.php:104` indiziert mit `??`-Rückfall, `export.js` und
`import_profiles.js` führen eigene Kopien mit 2–9.

### V4 — Wertabhängige Unterfelder unter einem `select`

Bestätigt, mit gegenüber dem Konzept verschobenen Zeilennummern.

- **Lesepfad** `$readField`, `einsatz_form.php:200–225`: Der Zweig für
  `type === 'checkbox'` endet mit `return` in Zeile 209 und reicht
  `$parentOn = ($v === 1)` an die Kinder. Alle anderen Typen fallen bis Zeile
  224 durch und reichen `$parentOn` **unverändert** weiter — Kinder eines
  `select` werden deshalb immer gelesen und gespeichert.
- **Renderpfad** `einsatz_form.php:604–617` (Checkbox) gegen `618–640` (Select):
  Die Checkbox erzeugt `class="parentcheck" data-target="ch_<col>"` und einen
  Kindercontainer `<div class="childfields" id="ch_<col>" hidden>`; der Select
  erzeugt einen Kindercontainer **ohne Kennung und ohne Umschaltung**. Das
  Umschalten macht `einsatz_form.php:960` über `.parentcheck`.

**Vorgehen für Etappe 2, das den Windenblock nicht anfasst:** `show_if`
ausschließlich im Durchfall-Zweig auswerten — Zeile 224 wird zu
`$readField($cc, $cf, $parentOn && mf_show_if($cf, $v))`. Der Checkbox-Zweig
kehrt vorher zurück und bleibt unverändert; `winch` ist eine Checkbox und damit
beweisbar nicht betroffen. Im Renderpfad bekommt der Select dieselbe
`data-target`-Mechanik und ein `change`-Ereignis.

### V5 — Importprofile

Drei Profile in `assets/import_profiles.js`, **alle drei** bilden auf das
Tagesdatum ab, **zwei** zusätzlich auf Besatzungsspalten:

| Profil | Datum | Besatzung |
|---|---|---|
| `ch17_jahresliste` (Z. 64) | `'Datum'` → `day` (Z. 92) | — |
| `export_csv_v1` (Z. 231) | `'flugtag'` → `day` (Z. 148) | `tag_crew_*` → `dayCrew.*` (Z. 163–167), `crew_*` → `crew.*` (Z. 170–174) |
| `export_excel_v1` (Z. 255) | `'Einsatzdatum'` → `day` (Z. 288) | Spaltenköpfe „Pilot 1" … (Z. 262) |

`export_csv_v1` ist der verlustfreie Rückweg des eigenen Exports und muss mit
`assets/export.js` (Feldkatalog ab Z. 608) synchron bleiben.

### V6 — Spalten der Tagestabelle: bereits generisch

Siehe Berichtigung B3. Nichts zu tun.

### V7 — `api/range.php`: Winde und Bergwacht liegen bereits vor

`api/range.php:75–76` liefert je Einsatz `winch` und `bergwacht` als
Wahrheitswerte, dazu `winch_cycles`, `secondary`, `site_ele_m`, `distance_m`.
Die datengetriebene Kachelsichtbarkeit ist damit ohne Zusatzabfrage im Browser
entscheidbar.

**Für Etappe 3 zu ergänzen:** `kind` des zugehörigen Diensttags und
`false_alarm` je Einsatz sowie die Aufteilung der Kennzahl `tage` (Z. 88) nach
Art einschließlich der Zahl der neutralen Diensttage.

### V8 — Das Einsatzort-Widget ist keine Komponente

Es ist über **feste Element-Kennungen** verdrahtet: `locaddr`, `loclat`,
`loclon`, `locstate` erscheinen in rund 25 `getElementById`-Aufrufen in
eingebettetem JavaScript zwischen `einsatz_form.php:859` und `:1132` —
Photon-Abfrage, Plus-Code-Erkennung, Chip-Darstellung, Zustandszeile und die
Prüfung „Koordinaten ohne Bezeichnung" (Z. 918–923) hängen alle daran.

Sechs Verwendungen sind gefordert (Abfahrtort, Zielklinik am Einsatz, Standort-
und Zielklinikpflege je auf Konto- und Adminebene). Eine Mehrfachverwendung
setzt deshalb die **Herauslösung in ein Modul** `assets/ortsfeld.js` voraus, das
je Verwendung ein Präfix bekommt und die Element-Kennungen daraus bildet. Die
`<datalist>` der Zielklinik wird eine Option der Komponente, kein Sonderfall.
Das Einsatzortfeld wird als erste Verwendung umgestellt, damit es nur eine
Umsetzung gibt.

---

## 6. Abnahmekriterien

- **A1** Zwei Diensttage am selben Kalendertag lassen sich anlegen, einer
  luftgebunden, einer bodengebunden, ohne Schlüsselkonflikt.
- **A2** Ein Diensttag über Mitternacht führt seine Einsätze korrekt: Statistik
  ordnet sie dem Diensttag zu, die Suche findet sie unter ihrem echten Datum.
- **A3** Ein bodengebundener Diensttag zeigt Fahrer, Praktikant und Sonstige und
  keine Windenfelder.
- **A4** Wird ein Rettungsmittel oder Standort nachträglich bearbeitet,
  umbenannt oder gelöscht, ändert sich an bereits dokumentierten Diensttagen
  nichts — weder Art, Rollensatz und Fähigkeiten noch Bezeichnung und
  Standortkoordinate. Die Änderung wirkt ausschließlich auf neue Diensttage.
- **A4a** Es gibt keine standortübergreifenden Stammdaten. Jede Zielklinik,
  jedes Rettungsmittel, jede Besatzungs-Vorbelegung, jedes weitere
  Rettungsmittel und jede Bergwacht-Bereitschaft ist genau einem Standort
  zugeordnet, und die Auswahllisten zeigen genau dessen Einträge.
- **A5** Transport „Ambulant" blendet Zielklinik, Schockraum und NA-Begleitung
  aus; ein zuvor eingetragenes Transportziel geht dabei nicht verloren, sondern
  wird geleert und die Änderung ist erkennbar.
- **A6** Zwei Diensttage gleicher Art lassen sich zusammenführen; Einsätze,
  Ruhe-Segmente und Uhr-Kennungen hängen danach am Zieltag, der Zeitraum
  umschließt beide.
- **A7** Ein luftgebundener und ein bodengebundener Diensttag lassen sich nicht
  zusammenführen und erzeugen eine verständliche Meldung. Ein neutraler
  Diensttag lässt sich mit beiden zusammenführen und übernimmt deren Art.
- **A7a** Ein von der Uhr angelegter, noch nicht zugeordneter Diensttag zeigt
  keine Rollen und keine artabhängigen Felder. Zeiten, Phasen, Track und
  Reanimationsdokumentation sind dennoch vollständig vorhanden.
- **A7b** Nach dem Nachtragen von Standort und Rettungsmittel erscheinen Rollen
  und artabhängige Felder am selben Diensttag, ohne dass zuvor erfasste Daten
  verloren gehen.
- **A7c** Die Art ist in der Tagesübersicht als Symbol am Rettungsmittelnamen
  erkennbar, neutrale Diensttage tragen ein eigenes Zeichen, und jedes Symbol
  hat eine Textalternative.
- **A8** Nach einem Zusammenführen legt ein Upload mit einer Kennung des
  aufgenommenen Tags keinen neuen Diensttag an, sondern landet im Zieltag.
- **A9** Backup und Wiederherstellung erhalten alle neuen Strukturen
  einschließlich `day_refs`; nach der Wiederherstellung gilt A8 unverändert.
- **A10** Ein Backup älterer Formatversion wird mit klarer Meldung abgelehnt.
- **A11** Die Migration läuft auf dem Bestand fehlerfrei durch; alle Einsätze
  hängen danach an einem Diensttag, kein Einsatz ist verwaist.
- **A12** Die Nachbearbeitungsseite listet genau die Diensttage ohne Standort
  oder Rettungsmittel sowie die Stammdatensätze ohne Standortzuordnung und
  verschwindet, sobald keine offen sind. Erst danach trägt `base_id` die
  `NOT NULL`-Bedingung.
- **A13** Steigung und Einsatzorthöhe erscheinen nur bei luftgebundenen
  Diensttagen.
- **A13a** Ein Zeitraum mit nur einer Art zeigt keine Tableiste. Ein Zeitraum mit
  beiden Arten zeigt drei Tabs, „Gemischt" ist aktiv.
- **A13b** Der Tab filtert Kacheln, Einsatztabelle und Karte gemeinsam. Der
  gewählte Tab steht im URL-Fragment, nicht als Abfrageparameter.
- **A13c** „Gemischt" zeigt dieselben acht Kacheln wie der bodengebundene Tab,
  neutral beschriftet, und weist die Anzahl neutraler Diensttage aus, sobald
  welche im Zeitraum liegen.
- **A13f** Der Luftrettungs-Tab zeigt genau die zehn heutigen Kacheln mit den
  heutigen Beschriftungen und keine Fehleinsatz-Kachel. Einsatztabelle und
  Export sind in allen Tabs neutral beschriftet.
- **A13d** Windenkacheln erscheinen ausschließlich, wenn im Zeitraum
  Windeneinsätze dokumentiert sind — ein windenfähiger Hubschrauber ohne
  Windeneinsatz erzeugt keine Kachel. Für Bergwacht gibt es keine Kachel; die
  Bergwachtspalte der Einsatztabelle folgt derselben datengetriebenen Regel.
- **A13e** Werden Winde oder Bergwacht an einem Rettungsmittel abgewählt,
  verlieren bereits dokumentierte Einsätze früherer Diensttage weder Daten noch
  Anzeige. Neue Diensttage desselben Rettungsmittels zeigen die Felder nicht
  mehr.
- **A13g** Ein Einsatz ohne Track mit gewähltem Abfahrtort und erfasstem
  Einsatzort zeigt eine gestrichelte Luftlinie mit Pins an beiden Enden und
  benannter Luftlinienlänge. Linie und Pins erscheinen erst nach Freischalten
  des Patientendatenschlüssels.
- **A13h** Liegt ein Track vor, wird er gezeichnet und die Luftlinie unterbleibt.
  Trifft ein Track nachträglich ein, bleibt die Abfahrtortangabe gespeichert und
  wird lediglich nicht mehr dargestellt.
- **A13i** Fehlt eine der beiden Koordinaten — Standort ohne Koordinaten, kein
  vorheriger Einsatz, Einsatzort nicht erfasst —, entsteht keine Linie und es
  wird nicht auf einen anderen Abfahrtort ausgewichen.
- **A13j** Ein manueller Abfahrtort ohne Bezeichnung wird abgewiesen, wie beim
  Einsatzort. Die Koordinaten stehen im `pat_blob`, nicht in einer
  Klartextspalte.
- **A13k** Die Luftlinienlänge erscheint in keiner Kachel und in keinem Filter.
  `site_ele_m` bleibt bei Einsätzen ohne Track leer.
- **A13l** Eine Zielklinik lässt sich auf allen drei Ebenen mit Koordinaten
  pflegen: zentral, im Konto und einmalig am Einsatz. Wird ein Vorschlag mit
  hinterlegten Koordinaten übernommen, sind sie vorbelegt und überschreibbar.
- **A13m** Eine Zielklinik ohne Koordinaten bleibt ein gültiger Wert; Freitext
  und Vorschlagsliste funktionieren unverändert. Dasselbe gilt für Einsatzort
  und Abfahrtort als reine Textangabe.
- **A13n** Bei belegtem Einsatzort und belegter Zielklinik verläuft die Linie
  über drei Punkte, und die ausgewiesene Luftlinie ist die Summe beider
  Abschnitte. Fehlt der Einsatzort, entsteht keine Linie.
- **A13o** Der Zielklinik-Pin ist ohne Freischalten des
  Patientendatenschlüssels sichtbar, Linie und Einsatzort-Pin nicht.
- **A13p** Eine nachträgliche Korrektur der Koordinaten einer Zielklinik oder
  eines Standorts verändert bereits erfasste Einsätze und Diensttage nicht.
- **A13q** „Letzte Zielklinik" löst auf die Zielklinik-Koordinate des zeitlich
  unmittelbar vorangehenden Einsatzes desselben Diensttags auf; Papierkorb-
  einträge bleiben unberücksichtigt. Hat der Vorgänger keine Zielklinik oder
  keine Koordinaten, entsteht keine Linie und es wird nicht auf eine andere
  Quelle ausgewichen.
- **A14** Die Uhr sendet `day_ref` und funktioniert unverändert ohne Kenntnis der
  Einsatzart. Die Connect-IQ-App-ID ist unverändert.
- **A15** Alle PHP-Dateien bestehen `php -l` unter PHP 8.3, alle
  JavaScript-Dateien `node --check`, eingebettetes JavaScript separat geprüft.
- **A16** `CHANGELOG.md`, `Handbuch.md`, `Technik.md`, `JSON-Vertrag.md`,
  `Backup-Format.md` und `Export-Format.md` sind auf dem neuen Stand, inklusive
  entfernter Funktionen. Keine verwaisten Querverweise auf „Flugtag",
  „Hubschrauber" oder Phase 10.

---

## 7. Bewusst nicht enthalten

- Rettungsdienst ohne Notarzt, KTW, ITW, Verlegungsdienste
- Einsatzstichwort, Todesfeststellung, Hilfsfrist als Kennzahl
- Aufteilen eines Diensttags
- Unterauswahl beim Fehleinsatz
- Lesbarkeit älterer Backupdateien
- Behebung des `herkunft`-Drifts in `api/export_data.php` (eigenes Konzept)
- Umbenennung des GitHub-Repositorys (separater Vorgang, betrifft
  `.github/workflows/deploy.yml` und die spätere F-Droid-Metadatenpflege)
- Anpassungen an der geplanten Android-Begleit-App

---

## 8. Offene Punkte für die Umsetzung

Fachlich sind alle Punkte entschieden. Frei bleibt allein die konkrete
gestalterische Ausformung, die keiner gesonderten Freigabe bedarf:

- Wortlaut der Aktion zum Zusammenführen und Umfang der Kandidatenliste
  („zeitlich benachbart" ist bewusst nicht in Tagen festgelegt).
- Wahl der Symbole für luftgebunden, bodengebunden und neutral.
- Anordnung der neuen Felder innerhalb des Einsatzformulars.

Ergeben sich bei der Umsetzung Abweichungen von den Entscheidungen aus
Abschnitt 2, sind sie als nummerierte Punkte zu melden und vor der Umsetzung
bestätigen zu lassen.
