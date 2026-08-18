# Prüfprotokoll — Erweiterung auf bodengebundene Notarzteinsätze

Fortgeschrieben mit **jeder** Etappe. Zu jedem Schritt steht hier, was geprüft
wurde, **wie** geprüft wurde, was dabei herauskam — und was für die Betreiberin
zu prüfen bleibt.

## Wortwahl in der Spalte „Wie geprüft"

Die Begriffe sind festgelegt, damit die Aussagekraft nicht verwischt:

| Begriff | Bedeutung |
|---|---|
| `php -l` / `node --check` | Reine Syntaxprüfung. Sagt **nichts** über Verhalten. |
| **Datenbank** | Gegen eine echte MariaDB 10.11.14 tatsächlich ausgeführt; der verwendete Datenbestand ist benannt. |
| **Browser** | Im Chromium der Arbeitsumgebung tatsächlich aufgerufen. **Bis Etappe 2** hieß das: die ausgelieferte Seite geholt und ihr HTML gelesen. **Ab Etappe 3** heißt es: die Seite in einem gesteuerten Chromium geöffnet, angeklickt und ihre Konsole mitgeschrieben. Was dabei nicht geht, steht je Etappe unter „nicht prüfbar". |
| **Gegengelesen** | Nur gelesen, nicht ausgeführt. Wird ausdrücklich so benannt und **nie** als „geprüft" ausgegeben. |

## Prüfumgebung

| | |
|---|---|
| PHP | 8.4.19 (CLI) — das Abnahmekriterium A15 nennt 8.3; geprüft wurde auf 8.4 |
| Node | 22.22.2 |
| Datenbank | MariaDB 10.11.14 (Ubuntu noble), lokal installiert |
| Browser | Chromium über Playwright 1.56 — **ab Etappe 3**, siehe unten |
| Connect-IQ-SDK | **nicht vorhanden** — Uhr-Code kann nicht kompiliert werden |
| Webserver | eingebauter PHP-Server (`php -S`) mit einem Testrouter, der eine Sitzung setzt — siehe unten |

Zwei Datenbanken stehen nebeneinander:

- **`edoku_bestand`** — aufgebaut aus dem `schema.sql` des Standes *vor* dem
  Umbau (`git show HEAD:server/schema.sql`), befüllt mit einem nachgebauten
  Altbestand, danach migriert. Bildet den Weg einer bestehenden Installation ab.
- **`edoku_neu`** — aufgebaut aus dem neuen `schema.sql`. Bildet eine
  Neuinstallation ab.

> **Ab Etappe 2 nur noch `edoku_neu`.** Die Etappen 2 bis 4 ändern das Schema
> nicht (Berichtigung B5) — der Migrationsweg ist mit Etappe 1b abgeschlossen
> geprüft. Er wäre zudem nicht wiederholbar: `docs/pruefgrundlage/` fehlt im
> Repository (Befund P19).

Der nachgebaute Altbestand deckt bewusst die Fälle ab, die die Migration
unterscheiden muss: Diensttage mit und ohne Rettungsmittel, ein Tag nur mit
Besatzung, ein völlig leerer Tag, ein Tag im Papierkorb, ein Einsatz **über
Mitternacht**, ein Einsatz mit `crew_override = 1`, ein Einsatz mit belegten
Besatzungsspalten **ohne** Haken, ein Einsatz **ohne zugehörigen Diensttag**,
Konten mit einem, mit zwei und mit keinem Standort sowie zentrale Stammdaten.

---

## Prüfumgebung wiederherstellen

Für den nächsten Chat, damit die Prüfung nicht neu erfunden wird:

Beides liegt als Datei im Repository, damit die Anleitung ohne
Commit-Kenntnis funktioniert:

- `docs/pruefgrundlage/schema-vor-6.0.0.sql` — das Schema **vor** dem Umbau
- `docs/pruefgrundlage/testbestand.sql` — der nachgebaute Altbestand

Bewusst als Dateien und nicht als `git show <hash>`: Ein Commit-Hash aus der
Arbeitsumgebung überlebt den Weg über ein ZIP nicht, und ohne die
Prüfgrundlage ließe sich der Migrationslauf nicht wiederholen.

```
apt-get update && apt-get install -y mariadb-server     # Paketlisten des Images sind alt
mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld
mysqld_safe --user=mysql &

# Ein eigener Datenbankbenutzer: root@localhost laeuft im Image ueber
# unix_socket und ist fuer PDO ueber 127.0.0.1 nicht erreichbar.
mariadb -e "CREATE USER IF NOT EXISTS 'edoku'@'%' IDENTIFIED BY 'edoku'"

# Altbestand: Schema von VOR dem Umbau, Testdaten, dann migrieren
mariadb -e "CREATE DATABASE edoku_bestand CHARACTER SET utf8mb4"
mariadb -e "GRANT ALL ON edoku_bestand.* TO 'edoku'@'%'"
mariadb edoku_bestand < docs/pruefgrundlage/schema-vor-6.0.0.sql
mariadb edoku_bestand < docs/pruefgrundlage/testbestand.sql
php server/update.php          # braucht server/config.php auf edoku_bestand

# Neuinstallation
mariadb -e "CREATE DATABASE edoku_neu CHARACTER SET utf8mb4"
mariadb -e "GRANT ALL ON edoku_neu.* TO 'edoku'@'%'"
mariadb edoku_neu < server/schema.sql
```

### Oberfläche prüfen, ohne sich anzumelden

Ab Etappe 1b gibt es etwas zu sehen. Ein Anmeldevorgang ist in der
Arbeitsumgebung nicht durchführbar — das Passwort wird im Browser abgeleitet.
Stattdessen ein Testrouter, der eine Sitzung setzt und die Anfrage an die
Seitendatei weitergibt:

```php
<?php   // router.php — NUR fuer die Pruefung, nie ausliefern
session_start();
$_SESSION['user_id'] = 1;                 // Testkonto
$_SESSION['last_seen'] = time();
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
session_write_close();
$pfad = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$datei = __DIR__ . '/server' . ($pfad === '/' ? '/index.php' : $pfad);
if (is_file($datei)) {
    if (substr($datei, -4) === '.php') { chdir(dirname($datei)); require $datei; return true; }
    return false;                          // statische Datei ausliefern lassen
}
http_response_code(404);
```

```
php -S 127.0.0.1:8099 -t server router.php
curl -s -c cj -b cj http://127.0.0.1:8099/index.php
```

Für die Admin-Seiten muss das Testkonto Admin sein
(`UPDATE users SET role='admin' WHERE id=1`).

**Eingebettetes JavaScript** (A15) wird aus den AUSGELIEFERTEN Seiten geholt —
dann hat PHP es schon erzeugt — und einzeln durch `node --check` geschickt. Eine
Prüfung am PHP-Quelltext ginge nicht: Dort stehen `<?= … ?>`-Einsetzungen im
Skript. Beim Abholen die Abfrageparameter nicht vergessen: `zeitraum.php` ohne
`?y=` leitet auf `index.php` um und liefert dann fremdes Markup.

### Die Oberfläche bedienen (ab Etappe 3)

Chromium liegt im Bild bereit, Playwright ist global installiert — ein
Anmeldevorgang ist damit zwar weiterhin nicht durchführbar (der Testrouter setzt
die Sitzung), aber **Klicken** ist es. Damit lassen sich Tabs, Filter,
Kachel-Hervorhebungen und Karten-Pins tatsächlich prüfen statt gegenlesen.

```
NODE_PATH=/opt/node22/lib/node_modules node pruefskript.js
```

Zwei Dinge im Skript nicht vergessen:

- **Konsolenfehler mitschreiben** (`page.on('pageerror')` und
  `page.on('console')`) — sonst sieht ein kaputtes Skript aus wie eine leere
  Seite. Die Kartenkacheln von OpenStreetMap sind dabei **kein** Befund: Das
  Netz der Arbeitsumgebung lässt sie nicht durch (`ERR_TUNNEL_CONNECTION_FAILED`).
- **Für einen geteilten Link eine frische Seite öffnen.** Ein `goto()` auf
  dieselbe Adresse mit anderem `#`-Teil ist für den Browser keine neue Seite —
  das Skript prüft dann den alten Zustand und meldet fälschlich einen Fehler.

Die Filter der Suche stehen in zugeklappten `<details>`. Vor dem Setzen
aufklappen, sonst wartet Playwright auf ein unsichtbares Element:

```js
await p.evaluate(() => document.querySelectorAll('.filtergruppe').forEach(d => d.open = true));
```

Der Export hängt an zwei Dingen, die ein Skript sonst nicht überwindet: Der
Passwortschutz ist **vorangehakt** (abwählen oder ein Passwort setzen), und vor
dem Erzeugen steht eine Rückfrage — der Knopf dafür ist
`dialog[open] button[data-act="yes"]`.

`server/config.php` zeigt auf die jeweilige Datenbank; sie steht in
`.gitignore` und gehört **nicht** ins ZIP.

Der Schemavergleich, der Befund B7 gefunden hat, muss `is_nullable` und die
Indizes mitnehmen — `column_type` allein meldet fälschlich „identisch":

```
SELECT table_name,column_name,column_type,is_nullable FROM information_schema.columns
  WHERE table_schema='<db>' ORDER BY table_name,column_name;
SELECT table_name,index_name,GROUP_CONCAT(column_name ORDER BY seq_in_index),non_unique
  FROM information_schema.statistics WHERE table_schema='<db>'
  GROUP BY table_name,index_name,non_unique ORDER BY table_name,index_name;
```

---

## Etappe 1a — Schema, Migration, Rollenkatalog

> **Die Anwendung läuft nach dieser Etappe nicht.** Das Schema ist umgestellt,
> der Code liest noch das alte — 19 PHP- und 3 JavaScript-Dateien folgen in
> Etappe 1b. Die Migration deshalb **nicht** auf der Produktivinstallation
> ausführen, bevor 1b vorliegt. Geprüft ist hier ausschließlich der
> Datenmodellumbau; eine Oberflächenprüfung im Browser gab es bewusst nicht,
> weil es dafür noch nichts zu sehen gibt.

### Schema und Migration

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| `schema.sql` ist gültiges SQL und baut eine Neuinstallation auf | Datenbank (`edoku_neu`) | Läuft fehlerfrei durch, 30 Tabellen | Auf der echten MySQL-Fassung des Webspace gegenprüfen — geprüft wurde MariaDB 10.11 |
| Tabellenreihenfolge trägt die neuen Fremdschlüssel | Datenbank | `days` steht vor `missions`; alle Fremdschlüssel legen sich an | — |
| Migration läuft auf dem Altbestand durch (**A11**) | Datenbank (`edoku_bestand`) | „Erfolgreich angewendet" | **Auf dem echten Bestand ausführen.** Der Testbestand ist nachgebaut, nicht deiner. Vorher Sicherung. |
| Kein verwaister Einsatz, kein verwaistes Ruhe-Segment (**A11**) | Datenbank, Abfrage auf `day_id IS NULL` | 0 und 0 | — |
| Einsatz ohne `days`-Zeile bekommt einen Diensttag | Datenbank | Für den Einsatz vom 01.02. wurde Diensttag 7 angelegt, neutral | — |
| Diensttag über Mitternacht führt seine Zeiten richtig | Datenbank | Tag 1: `started_at` 10.01. 08:00, `ended_at` **11.01. 00:40** — das Ende des Einsatzes nach Mitternacht ist übernommen | Fachlich im Alltag gegenprüfen (**A2**) |
| Dreistufige Ermittlung von `kind` und `day_crew` | Datenbank | a) Tag mit Rettungsmittel → `air`, **alle** Rollen des Rettungsmittels als Zeilen, auch leere. b) Tag ohne Rettungsmittel, aber mit Besatzung → `air`, nur belegte Rollen. c) Tag ohne beides → `kind` NULL, keine Zeilen | — |
| Belegte Rolle, die das Rettungsmittel nicht vorsieht, geht nicht verloren | Datenbank | Bekommt trotzdem ihre Zeile | — |
| `mission_crew` nur bei `crew_override = 1` | Datenbank | Nur Einsatz 3 übernommen; die Werte des Einsatzes ohne Haken sind entfallen (siehe Problem P2) | — |
| Standortbezug zweistufig (**E15**) | Datenbank | Konto mit genau einem Standort → alle Stammdaten zugeordnet. Konto mit zwei, Konto mit keinem, zentrale Stammdaten → bleibt offen für die Nachbearbeitung | — |
| `user_defaults.kind` von `aircraft` auf `vehicle` | Datenbank | Umgestellt, Zeile erhalten | — |
| Fähigkeiten für den Bestand (Konzept 2a) | Datenbank | 3 Rettungsmittel × 2 und 7 Diensttage × 2 Einträge | — |
| Altspalten entfernt | Datenbank | Nur `missions.crew_override` und `days.day` verbleiben — beide absichtlich | — |
| Migration ist wiederaufnehmbar | Datenbank | Nach zwei absichtlich herbeigeführten Abbrüchen (Fehler 1553) setzte der nächste Lauf korrekt auf | — |
| Migration wird auf einer Neuinstallation nicht ausgeführt | Datenbank (`edoku_neu`) | „Bereits angewendet" — die Kennung steht in `schema.sql` | — |
| Migrierte Datenbank und Neuinstallation stimmen überein | Datenbank, Vergleich über `information_schema` (Spalten, Typen, NULL-Zulässigkeit, alle Indizes) | Indizes **identisch**. Spalten identisch bis auf die fünf `base_id` — nullbar nach Migration, `NOT NULL` bei Neuinstallation. Beabsichtigt, siehe Problem P6 | Nach dem Durchlauf der Nachbearbeitungsseite erneut vergleichen (**A12**) |
| `db.php`, `update.php`, `schema.sql` syntaktisch | `php -l` | Fehlerfrei | — |

### Gefundene und behobene Fehler

Vier Befunde aus dem Prüflauf, alle in der Umsetzung behoben. Sie stehen
ausführlich in `Konzept-Notarzt-Erweiterung.md`, Abschnitt 0.3.

| Nr. | Befund | Wie gefunden |
|---|---|---|
| P4 | MySQL-Fehler 1553 an zwei Stellen: Index wird vom Fremdschlüssel gebraucht | Datenbank — die Migration brach ab |
| P5 | `skip`-Prüfung verbuchte eine halbfertige Migration als erledigt | Datenbank — der zweite Lauf meldete fälschlich „nicht nötig" |
| B7 | Eindeutige Schlüssel von `bw_units`, `resources`, `transport_dests` ohne Standort — dieselbe Zielklinik wäre an zwei Standorten nicht anlegbar | Datenbank — Schemavergleich gegen die Neuinstallation |
| — | Erster Schemavergleich war unvollständig (`column_type` enthält die NULL-Zulässigkeit nicht) und meldete fälschlich „identisch" | Beim Gegenlesen des eigenen Prüfbefehls aufgefallen |

---

## Etappe 1b — Codeanpassung, Nachbearbeitung, Umbenennung

> **Die Anwendung läuft wieder.** Geprüft wurde diesmal auf drei Ebenen: die
> Bibliotheken direkt gegen die Datenbank, jede Seite über HTTP, und die
> Formatwege (Backup, Export, Import) über ihre Endpunkte. Was in der
> Arbeitsumgebung grundsätzlich nicht prüfbar ist, steht weiter unten.

### Datenmodell und Bibliotheken

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Zwei Diensttage am selben Kalendertag, einer luft-, einer bodengebunden (**A1**) | Datenbank (`edoku_bestand`) **und** Browser auf einer Neuinstallation (`diensttag_neu.php`, zweimal 01.04.) | Beide angelegt, kein Schlüsselkonflikt; Art, Bezeichnungen und Rollensatz je Tag getrennt eingefroren | — |
| Rollensatz folgt der Art (**A3**) | Datenbank | Bodengebundener Tag: Fahrer, Praktikant, Sonstige. Luftgebundener: die Rollen des Rettungsmittels | — |
| Rollen-Filter im Einsatzformular (**A3**, erste Hälfte) | Browser (HTML der ausgelieferten Seite) | An einem Tag mit p1/hems/fr sind genau diese drei Felder sichtbar, p2 und die Bodenrollen tragen `hidden` | — |
| Windenfelder an einem bodengebundenen Dienst (**A3**, zweite Hälfte) | Browser | **Noch sichtbar** — sie hängen an `cap_gate`, und das ist Etappe 2 (Konzept 4.3). Die Felder sind leer; kein Datenverlust | Mit Etappe 2 erneut prüfen |
| Höhenangaben nur luftgebunden (**A13**) | Browser (`einsatz.php`) | „Höhe Einsatzort" und „Steigung" erscheinen nur bei `day_kind === 'air'`; gerechnet werden sie unverändert für jeden Einsatz mit Track und gehen weiterhin in Export und Backup | — |
| Nachträgliche Zuordnung verliert keine Besatzung (**A7b**) | Datenbank | Belegte Rolle `p1` blieb erhalten, obwohl das neue Rettungsmittel sie nicht vorsieht; leere Rollen wurden ersetzt | — |
| Diensttag über Mitternacht (**A2**) | Datenbank | `started_at` 10.01. 18:00, `ended_at` 11.01. 02:30; der Zeitraum wandert nur nach vorne bzw. hinten | Fachlich im Alltag gegenprüfen |
| Statistik nach Diensttag, Suche nach Einsatzdatum (**A2**, E14) | Browser (`api/suchindex.php`) | Einsatz 2: `day` = 11.01. (echtes Einsatzdatum), `dienst_day` = 10.01. Beide Werte stehen in der Antwort | — |
| Stammdatenänderung wirkt nicht rückwärts (**A4**) | Datenbank | Anzeige kommt durchgehend aus `vehicle_name`/`base_name` des Diensttags; Umbenennen und Löschen lassen dokumentierte Tage unberührt | — |
| Winde/Bergwacht abwählen (**A13e**) | Datenbank + gegengelesen | `vehicle_capabilities` wird ersetzt, `day_capabilities` nicht angefasst — vorhandene Diensttage behalten ihren Satz | Fachlich gegenprüfen |
| Art und Fähigkeit schließen sich richtig aus (E3/E29) | Browser (POST `veh_save`) | Beim bodengebundenen Rettungsmittel wurden die Flugrolle `p1` und die Fähigkeit `winch` serverseitig verworfen, obwohl gesendet | — |
| Umdatieren auf ein belegtes Datum ist zulässig (**A1**) | Datenbank | Läuft durch; Datum, Dienstzeitraum und alle Zeitstempel wandern gemeinsam | — |
| Einsatz verschieben, Zieltag wird nicht angelegt | Datenbank | Verschieben setzt `day_id` und zieht den Zeitraum des Zieltags nach; ein zweiter Aufruf wird mit Begründung abgewiesen | — |
| Uhr-Kennung ist idempotent (**A8**) | Datenbank | Zweiter Aufruf mit derselben `day_ref` liefert denselben Diensttag | Auf echter Hardware gegenprüfen (**A14**) |
| Rückfallebene ohne `day_ref` bei **mehreren** Diensttagen (P14) | Browser (`ingest.php`) auf der Neuinstallation | Ein Upload um 08:00 landet im Dienst 05:00–13:00, einer um 20:00 im offenen Abenddienst ab 17:00, einer um 03:00 (vor beiden) im frühesten. Ohne Diensttag des Datums wird ein neutraler angelegt | Auf echter Hardware gegenprüfen (**A14**) |
| Kein verwaister Einsatz nach allen Eingriffen (**A11**) | Datenbank, Abfrage auf `day_id IS NULL` | 0 und 0 | — |

### Nachbearbeitung (A12) und Schemagleichheit (P6)

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Die Seite listet genau die offenen Punkte (**A12**) | Browser | Drei Diensttage ohne Zuordnung, fünf Stammdatenarten mit je zwei offenen Zeilen — über alle Konten gezählt, wie es die Bedingung verlangt | — |
| Zuordnen eines Diensttags friert alles ein (**A12**, E8) | Browser + Datenbank | `kind`, `vehicle_name`, `base_name`, `day_crew` und `day_capabilities` gesetzt — über dieselbe Funktion wie das Formular | — |
| Zweite Stufe lehnt bei offenen Einträgen ab | Browser | Meldung nennt die offenen Arten mit Anzahl; nichts geändert | — |
| Zweite Stufe setzt `NOT NULL` (**A12**) | Browser + Datenbank | Fünf Tabellen umgestellt, Meldung bestätigt es | **Auf dem echten Bestand durchführen**, nachdem alle Konten ihre Zuordnungen nachgetragen haben |
| Migrierte Datenbank und Neuinstallation stimmen danach überein (**Problem P6**) | Datenbank, Vergleich über `information_schema` (Spalten, Typen, NULL-Zulässigkeit, Standardwerte, alle Indizes) | **Vollständig identisch**, 30 Tabellen beidseitig. Der letzte Unterschied aus Etappe 1a ist damit aufgelöst | — |
| Die Seite verschwindet, wenn nichts offen ist | Browser | Nach dem Setzen der Bedingung meldet sie „Es ist nichts nachzutragen"; der Eintrag in der Leiste erscheint nicht mehr | — |

### Formatwege

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Backup enthält alle neuen Strukturen (**A9**) | Datenbank (`edbak_build`) | Nutzlast 6, `app` = `einsatzdoku-notarzt`; Diensttage mit Kennung, Zeitraum, Art, Snapshot-Spalten, `crew`, `capabilities` und `refs`; Rettungsmittel mit Art, Rollen, Fähigkeiten und Standort als Name; `user_bases`; Standortbezug aller Stammdaten | Mit einer echten, im Browser verschlüsselten Datei gegenprüfen |
| Wiedereinspielen in ein **fremdes Konto** (**A9**) | Datenbank (`edbak_restore`) | 7 Diensttage, 6 Einsätze, 2 Ruhesegmente, 13 `day_crew`-, 10 `day_capabilities`-, 1 `day_refs`- und 3 `mission_crew`-Zeilen; kein verwaister Einsatz. Beide Diensttage des 10.01. bleiben getrennt | — |
| Zweites Einspielen derselben Datei verdoppelt nichts | Datenbank | 0 Diensttage, 0 Einsätze, 0 Ruhesegmente neu | — |
| Ältere Nutzlastversion wird abgelehnt (**A10**) | Browser (`api/backup_restore.php`) | HTTP 409 mit benannter Begründung, die die fehlenden Angaben aufzählt; nichts geändert | Mit einer echten alten Datei gegenprüfen |
| Export liefert das neue Modell | Browser (`api/export_data.php`) | Diensttage mit Kennung und Snapshot-Spalten, Einsätze mit `day_id` und `crew` als Objekt; Bezeichnungen aus dem eingefrorenen Stand | Dateien in Excel/LibreOffice gegenprüfen |
| Import-Abgleich findet den richtigen Diensttag | Browser (`api/import_commit.php`, `check`) | Je Kalendertag der erste offene Diensttag samt Kennung, Art, Bezeichnungen und Besatzung; dazu die Einsätze aller Diensttage dieses Datums | Vollständigen Importlauf mit einer echten Datei gegenprüfen |

### Syntax und Auslieferung

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Alle PHP-Dateien (**A15**) | `php -l` (PHP 8.4.19) | Fehlerfrei | Auf PHP 8.3 des Webspace gegenprüfen |
| Alle JavaScript-Dateien (**A15**) | `node --check` (Node 22) | Fehlerfrei | — |
| Eingebettetes JavaScript (**A15**) | `node --check` je Block, aus 14 ausgelieferten Seiten extrahiert | 11 Blöcke, fehlerfrei | — |
| Jede Seite lädt ohne Fehler, Warnung oder Hinweis | Browser (HTTP), 28 Adressen — **auf beiden Datenbanken**: dem migrierten Bestand und einer Neuinstallation | Alle HTTP 200, kein `Fatal error`, `Warning`, `Notice` oder `Deprecated` — auch nicht im Serverprotokoll (dafür war ein Altfehler in `update.php` zu beheben, P15) | Auf dem echten Webspace gegenprüfen |

### Gefundene und behobene Fehler

Sechs Befunde aus dem Prüflauf dieser Etappe, alle behoben. Sie stehen
ausführlich in `Konzept-Notarzt-Erweiterung.md`, Abschnitt 0.3. Zwei davon sind
**älter als diese Etappe** und ausdrücklich so benannt.

| Nr. | Befund | Wie gefunden |
|---|---|---|
| P10 | Der Export bündelte Diensttage nach DATUM — zwei Dienste eines Kalendertags hätten sich überschrieben, der zweite hätte die Besatzung des ersten getragen | Beim Umstellen aufgefallen: `daysByDate[m.day]` konnte nach E9 nicht mehr stimmen |
| P11 | Das Wiedereinspielen erkannte einen vorhandenen Diensttag an Datum **und** Dienstbeginn — und verschmolz zwei Diensttage zu einem, sobald ein verschobener Einsatz beide Beginne gleich gezogen hatte | Datenbank: Der Rundlauf lieferte 6 statt 7 Diensttage |
| P13 | Die zweite Stufe (`NOT NULL`) war nur durch einen verborgenen Knopf geschützt, nicht im Handler — bei einer Schemaänderung für alle Konten die falsche Richtung | Beim Prüfen der Seite aufgefallen |
| P15 | `update.php` schrieb bei jedem Aufruf zwei PHP-Warnungen je verbuchter Migration ins Fehlerprotokoll — eine Ergebniszeile mit vier statt sechs Elementen. Älter als diese Etappe | Serverprotokoll beim Prüfen der Seiten |
| — | `adminbackup_lib.php` zählte die Ruhesegmente unter `rests`, `edbak_build()` liefert sie als `rest_segments`. Die Zahl stand in jeder Adminsicherung auf 0 — schon vor dieser Etappe | Beim Umstellen der Zählung aufgefallen |

---

## Etappe 2 — Einsatzfelder, Ortsfeld, Abfahrtort

> **Geprüft gegen eine laufende Installation.** MariaDB 10.11, PHP 8.4,
> eingebauter PHP-Server mit dem Testrouter von oben. Die Datenbank ist eine
> **Neuinstallation** aus `server/schema.sql` — Etappe 2 braucht keine
> Migration (B5), der Weg über den migrierten Altbestand entfällt hier also.
> Er wäre auch nicht wiederholbar: `docs/pruefgrundlage/` fehlt im Repository
> (Befund P19).
>
> **Testbestand:** drei Diensttage eines Kontos — ein luftgebundener mit Winde
> und Bergwacht (Christoph 17), ein bodengebundener (NEF 1) am **selben**
> Kalendertag, ein neutraler ohne Zuordnung. Dazu zwei Standorte (einer mit,
> einer ohne Koordinaten) und zwei Zielkliniken (dito).

### Sichtbarkeit der Felder

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Windenfelder nur bei Windenfähigkeit (**A3**, zweite Hälfte) | Browser, HTML der ausgelieferten Seite, alle drei Diensttage | Luftgebunden mit Winde: `winch` und `bergwacht` sichtbar. Bodengebunden: beide `hidden`. Neutral: beide `hidden`. **A3 ist damit vollständig erfüllt** | — |
| Rollenfilter unverändert (**A3**, erste Hälfte) | dieselbe Prüfung | Luftgebunden: nur die Rollen des Rettungsmittels (p1, hems, fr). Bodengebunden: driver und other. Neutral: keine | — |
| Ein belegtes Feld bleibt sichtbar (**A13e**) | Code gegengelesen + Renderpfad | `$belegt` wird **typabhängig** bestimmt: Textfeld = Inhalt, Haken = gesetzt. Ohne diese Unterscheidung hätte kein `cap_gate` je gegriffen, weil jede Checkbox eines bearbeiteten Einsatzes den Wert „0" trägt | Fachlich gegenprüfen: Winde am Rettungsmittel abwählen, alten Einsatz öffnen |
| Neutraler Diensttag zeigt nichts Artabhängiges (**A7a**) | Browser | Alle gefilterten Felder `hidden`, Transportart und Fehleinsatz sichtbar (sie sind artneutral) | — |

### Transportart und ihre Unterfelder

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Speichern mit Transport „Boden" | Browser (POST), dann Datenbank | `transport_mode='ground'`, `na_escort=1`, `transport_dest`, `dest_lat/dest_lon`, `schockraum=1`, `false_alarm=1`, `start_src='base'` — alles wie gesendet | — |
| **„Ambulant" leert die Unterfelder (A5)** | Browser (POST), der Zielklinik, Koordinaten, Schockraum und NA-Begleitung **mitschickt** | Alle vier serverseitig geleert: `transport_dest`, `dest_lat`, `dest_lon` NULL, `na_escort` und `schockraum` 0. Der Browser blendet sie zusätzlich vor dem Absenden aus, die Änderung ist also sichtbar | — |
| Vorbelegung beim Bearbeiten | Browser | Bezeichnung, beide Koordinaten, gewählte Transportart und gewählte Abfahrtortregel stehen im Formular; `#startfields` ist bei `manual` offen und sonst zu | — |
| Anzeige zeigt die Beschriftung, nicht den Wert | `api/mission.php` | „Transport: Boden", nicht „ground" (`mf_optionen`) | — |
| Fehleinsatz als Spalte der Tagestabelle | `api/day.php` + `index.php` | Spalte erscheint ohne weitere Codeänderung aus dem Katalog (`day_col`), Kopf mit weichem Trennstrich | — |

### Abfahrtort und Luftlinie

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Regel `base` wird serverseitig aufgelöst | `api/mission.php` | `start_base` = eingefrorene Standortkoordinate des Diensttags | — |
| Regel `prev_dest` (**A13q**) | `api/mission.php`, zwei Einsätze desselben Diensttags | `start_prev_dest` = `dest_lat/dest_lon` des **zeitlich vorherigen** Einsatzes; Papierkorbeinträge sind durch `deleted_at IS NULL` ausgeschlossen | Fachlich mit mehreren Einsätzen gegenprüfen |
| Nur die gewählte Quelle wird geliefert | `api/mission.php`, alle vier Regeln | Bei `manual` sind `start_base`, `start_prev_dest` und `start_prev_blob` alle `null` — der Blob eines anderen Einsatzes geht nur bei `prev_site` mit | — |
| Standortkoordinate für die Tageskarte | `api/day.php` | `meta.base_lat/base_lon` aus `days`, nicht aus den Stammdaten (**A13p**) | — |
| Keine Linie ohne Einsatzort (**A13n**), kein Ausweichen (**A13i**), Track hat Vorrang (**A13h**) | Code gegengelesen, `assets/luftlinie.js` | Alle drei Bedingungen stehen in `punkte()` als frühe Rückgabe einer leeren Liste; keine Ersatzquelle im Code | **Im Browser gegenprüfen** — Kartendarstellung ist hier nicht prüfbar |
| Länge als Summe beider Abschnitte (**A13n**) | Code gegengelesen | Großkreisdistanz je Abschnitt, Summe, kein Umwegfaktor; Beschriftung nennt „Luftlinie" ausdrücklich | Im Browser gegenprüfen |
| Luftlinie in keiner Kachel, kein Filter (**A13k**) | Code durchsucht | `EdLuftlinie` wird ausschließlich in `einsatz.php` und `index.php` aufgerufen; `api/range.php` und der Suchindex kennen sie nicht. `site_ele_m` bleibt ohne Track leer (unverändert) | — |
| Zielklinik-Pin ohne Freischalten (**A13o**) | Code gegengelesen | Er wird in `init()` bzw. `loadDay()` gesetzt, also **vor** dem Entschlüsseln; Linie und Einsatzort-Pin liegen in `zeigePat()` bzw. hinter dem Schlüssel | Im Browser gegenprüfen |

### Koordinaten — überall freiwillig, nie halb

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Koordinaten ohne Bezeichnung werden abgewiesen (**A13j**) | Browser (POST), Zielklinik leer, beide Koordinaten gesetzt | Benannte Fehlermeldung, **nichts gespeichert** — der vorherige Stand blieb unverändert. Der Browser fängt den Fall zusätzlich vor dem Absenden ab | — |
| Nur zusammen gültig | Browser (POST) auf `base_save`, Breite ohne Länge | Beide NULL statt einer halben Angabe | — |
| Außerhalb des Bereichs ist leer, nicht gekappt | Browser (POST), Breite 95.0 | Beide NULL | — |
| Komma als Dezimaltrennzeichen | Browser (POST), „47,8001" | `47.800100` in der Spalte | — |
| Dieselben Regeln im Import | `api/import_commit.php`, ungültige Werte | `transport_mode='Rettungswagen'` und `start_src='irgendwas'` werden zu NULL statt die Zeile scheitern zu lassen; halbes Koordinatenpaar wird ganz verworfen (Befund P18) | — |
| Zielklinik ohne Koordinaten bleibt gültig (**A13m**) | Datenbank + Browser | Freitext und Vorschlagsliste unverändert; die zweite Testklinik hat keine Koordinaten und erscheint in der `<datalist>` | — |

### Ortsfeld-Komponente (V8)

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Sechs Verwendungen, ein Code | Browser, HTML aller betroffenen Seiten | `loc`, `start`, `f_transport_dest_`, `sdbase`, `sdtd<id>`, `adbase`, `adtd<id>` — je vollständiger Elementsatz | — |
| Kein Kennungskonflikt bei mehreren Standorten | Browser, Stammdatenseite mit zwei Standorten | `sdtd1…` und `sdtd2…` getrennt; die Belebungsliste entsteht beim Rendern, nicht als zweite Aufzählung im Skript | — |
| Einsatzort-Kennungen unverändert | Browser | `locaddr`, `loclat`, `loclon`, `locsuggest`, `locstate`, `locchips` wie vor der Herauslösung — die 25 Fundstellen aus V8 hätten sonst einzeln nachgezogen werden müssen | Bedienung im Browser gegenprüfen (Photon-Abfrage, Plus Code) |
| Zielklinik-Vorschläge tragen Koordinaten (**A13l**) | Browser | Die Abfrage liefert `name`, `lat`, `lon`; die `<datalist>` zeigt Namen, das Skript übernimmt die Koordinaten bei genauem Treffer und lässt sie überschreibbar | Im Browser gegenprüfen |

### Formatwege

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Export liefert die neuen Felder | `api/export_data.php` (POST) | `transport_mode`, `na_escort`, `false_alarm`, `start_src`, `dest_lat`, `dest_lon` in der Antwort | Dateien in Excel/LibreOffice gegenprüfen |
| **Sicherungs-Rundlauf** (**A9**) | `edbak_build()` → `edbak_restore()` in ein **zweites Konto** | Nutzlast 6; nach dem Einspielen stehen `transport_mode`, `na_escort`, `false_alarm`, `start_src`, `dest_lat`, `dest_lon` vollständig in der Zielzeile. **Dabei gefunden: Befund P16** — vorher fehlten `start_src` und beide Koordinaten | Mit einer echten, im Browser verschlüsselten Datei gegenprüfen |
| Import schreibt die neuen Felder | `api/import_commit.php` (POST), Einfügen **und** Überschreiben | Beide Wege übernehmen alle sechs Spalten; Platzhalterzahl von INSERT (29) und UPDATE (28) gegen die Werteliste (23) geprüft | Vollständigen Importlauf mit einer echten Datei gegenprüfen |
| Der eigene CSV-Export bleibt der verlustfreie Rückweg | `export.js` gegen `import_profiles.js` gelesen | Jede neue Exportspalte hat einen Eintrag im Profil `export_csv_v1` | Rundlauf mit einer echten Datei gegenprüfen |

### Syntax und Auslieferung

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Alle PHP-Dateien (**A15**) | `php -l` (PHP 8.4.19), 60 Dateien | Fehlerfrei | Auf PHP 8.3 des Webspace gegenprüfen |
| Alle JavaScript-Dateien (**A15**) | `node --check` (Node 22), 22 Dateien ohne `vendor/` | Fehlerfrei | — |
| Eingebettetes JavaScript (**A15**) | `node --check` je Block, aus den ausgelieferten Seiten extrahiert | 20 Blöcke, fehlerfrei | — |
| Jede Seite lädt ohne Fehler, Warnung oder Hinweis | Browser (HTTP), 29 Adressen | Alle HTTP 200, kein `Fatal error`, `Warning`, `Notice` oder `Deprecated` — auch nicht im Serverprotokoll | Auf dem echten Webspace gegenprüfen |

### Gefundene und behobene Fehler

Vier Befunde aus dem Prüflauf dieser Etappe, alle behoben. Sie stehen
ausführlich in `Konzept-Notarzt-Erweiterung.md`, Abschnitt 0.3. **Zwei davon
wären ohne den Prüflauf nicht aufgefallen** — sie sind beim Lesen des Codes
unsichtbar.

| Nr. | Befund | Wie gefunden |
|---|---|---|
| P16 | Das Wiedereinspielen einer Sicherung übernahm nur Spalten, die so heißen wie ihr Katalogfeld — `dest_lat`, `dest_lon` und `start_src` wären beim Rückweg verschwunden. Zusätzlich stand die normalisierte Besatzung noch in dieser Liste; eine Datei mit `crew_p1` hätte die **ganze** Wiederherstellung scheitern lassen. Älter als diese Etappe | Sicherungs-Rundlauf in ein zweites Konto: Die Zielzeile trug NULL, wo Werte stehen mussten |
| P17 | Der Feldkatalog kannte nur Auswahlfelder, deren Wert die Beschriftung ist. Die Transportart schrieb „Boden" in ein `ENUM('air','ground','ambulant')` — MariaDB-Fehler 1265, das Formular meldete „Speichern fehlgeschlagen" | Erstes Speichern über die Oberfläche |
| P18 | Die Zielklinik-Koordinate wurde beim Import je Achse einzeln geprüft; ein halbes Paar kam durch | Importlauf mit absichtlich ungültigen Werten |
| P19 | `docs/pruefgrundlage/` fehlt im Repository — der Migrationslauf der Etappe 1a ist nicht wiederholbar | Beim Wiederherstellen der Prüfumgebung |

### Was in dieser Etappe nicht prüfbar war

Die **Kartendarstellung** selbst. Der Prüfstand liefert HTML und JSON; ob eine
gestrichelte Linie mit drei Stützpunkten und benannter Länge tatsächlich
erscheint, sagt erst ein Browser. Geprüft ist, dass die Daten dafür vollständig
und richtig ankommen (`start_src`, alle vier Quellen, `dest_lat/dest_lon`,
`base_lat/base_lon`) und dass die Regeln im Code stehen — nicht, wie es
aussieht. Dasselbe gilt für die Bedienung des Ortsfelds: Photon-Abfrage,
Plus-Code-Erkennung und Chip sind unverändert übernommen, aber nur im Browser
erlebbar.

---

## Etappe 3 — Auswertung, Suche, Export/Import

> **Geprüft gegen eine laufende Installation und in einem echten Browser.**
> MariaDB 10.11.14, PHP 8.4.19, eingebauter PHP-Server mit dem Testrouter von
> oben. Die Datenbank ist eine **Neuinstallation** aus `server/schema.sql` —
> Etappe 3 braucht keine Migration (B5). Der Weg über den migrierten Altbestand
> entfällt und wäre nicht wiederholbar (Befund P19).
>
> **Neu in dieser Etappe: die Oberfläche wurde bedient, nicht nur ausgeliefert.**
> Chromium liegt im Bild bereit (`/opt/pw-browsers`), Playwright ist global
> installiert. Damit lassen sich Tabs anklicken, Filter setzen und
> Konsolenfehler mitschreiben. Wo unten **Browser** steht, heißt das ab hier
> tatsächlich *bedient* — nicht „HTML gelesen".
>
> ```
> NODE_PATH=/opt/node22/lib/node_modules node pruefskript.js
> ```
>
> **Testbestand:** ein Konto, neun Diensttage über vier Monate, bewusst so
> geschnitten, dass jeder Fall der Tableiste vorkommt:
>
> | Monat | Diensttage | Wozu |
> |---|---|---|
> | März | 2 luft, 2 boden, 1 neutral | beide Arten → Tableiste; zwei Dienste am **selben** Kalendertag (A1); ein Einsatz **über Mitternacht** (A2); ein Windeneinsatz, ein Bergwachteinsatz, ein Fehleinsatz |
> | April | 2 luft, 1 neutral | nur eine Art **plus** neutral, **kein** Windeneinsatz |
> | Mai | 1 neutral | ausschließlich neutrale Diensttage |
> | Juni | 1 boden | nur eine Art, ohne neutrale |
>
> Dazu ein Monat ohne jeden Diensttag (Dezember) als Leerfall.

### Tableiste und Kachelsätze

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Beide Arten → drei Tabs, „Gemischt" aktiv (**A13a**) | Browser, März | Leiste sichtbar, `Gemischt [aktiv]`, `Luftrettung`, `Bodengebundener Rettungsdienst` | — |
| Nur eine Art → keine Tableiste (**A13a**) | Browser, April / Mai / Juni | Leiste in allen drei Fällen `hidden` | — |
| Luftrettungs-Tab: zehn Kacheln, heutige Beschriftungen (**A13f**) | Browser, März | Einsätze, Flugtage, Ø Einsätze / Flugtag, Sekundärtransporte, Flugkilometer gesamt, Längste Flugstrecke, Längste Einsatzdauer, Höchster Einsatzort, Anzahl Winden-Cycles, Ø Winden-Cycles / Flugtag. **Keine** Fehleinsatz-Kachel | — |
| „Gemischt" und bodengebunden: dieselben acht, neutral (**A13c**) | Browser, März | Einsätze, Diensttage, Ø Einsätze / Diensttag, Sekundärtransporte, **Fehleinsätze**, Einsatzkilometer gesamt, Längste Einsatzstrecke, Längste Einsatzdauer — in beiden Tabs identisch | — |
| Nur neutrale Diensttage → Kachelmenge „Gemischt" | Browser, Mai | acht neutrale Kacheln, Hinweis auf die fehlende Zuordnung sichtbar | — |
| Nur eine Art → deren Beschriftung, aber **alle** Diensttage | Browser, April | „Flugtage = 3" bei 2 luftgebundenen + 1 neutralen, Hinweis erklärt die dritte | Fachlich gegenlesen, ob das die gewünschte Lesart ist |
| Zahlen je Tab | Browser, März, gegen die Datenbank nachgerechnet | Gemischt: 7 Einsätze / 5 Diensttage / 149,0 km. Luft: 4 / 2 / 132,0 km. Boden: 2 / 2 / 12,0 km. Die Summe der Artentabs ist kleiner — die Differenz ist der neutrale Diensttag | — |
| Divisor je Tab | dieselbe Prüfung | Ø Einsätze / Flugtag = 2,0 im Luftrettungs-Tab (4 ÷ 2), nicht 4 ÷ 5 | — |
| Leerer Zeitraum | Browser, Dezember | acht Kacheln mit 0 bzw. „–", Leermeldung sichtbar, keine Konsolenfehler | — |

### Datengetriebene Sichtbarkeit (E30, A13d)

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Windenkacheln nur bei Windeneinsätzen | Browser, März (Luftrettungs-Tab, mit Windeneinsatz) vs. April (windenfähiger Hubschrauber, **kein** Windeneinsatz) | März: 10 Kacheln. April: 8 — beide Windenkacheln fehlen, obwohl das Rettungsmittel windenfähig ist | — |
| Für Bergwacht keine Kachel | Browser, alle Tabs | keine Bergwachtkachel in keinem Tab | — |
| Spalte Winde / Bergwacht nur bei Bestand | Browser, März | Gemischt und Luftrettung: beide Spalten. Bodengebunden: keine von beiden | — |
| Spalte Fehleinsatz nur bei Bestand | Browser, März | Gemischt und Bodengebunden: Spalte da (ein Fehleinsatz liegt bodengebunden). Luftrettung: keine | — |
| Spalte Art nur bei mehr als einer Art | Browser, März vs. Juni | März/Gemischt: Spalte „Art" ganz links. Luftrettungs-Tab: keine (nur eine Art). Juni: keine | — |
| Maßgeblich ist der **Bestand**, nicht die Trefferliste | Browser, Suche: Filter setzen und tippen | Die Spalten bleiben stehen, während die Trefferliste schrumpft — `setSpaltenBestand()` wird einmal beim Laden gesetzt | — |

### Tab filtert alles gemeinsam (A13b)

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Kacheln, Tabelle und Karte | Browser, März, Tabwechsel | Zeilen 7 / 4 / 2, Kacheln entsprechend, Karten-Pins 7 / 4 / 2 (gezählt über `pinLayer`) | — |
| Keine Karteileichen beim Tabwechsel | Browser, mehrfacher Wechsel hin und zurück | Zahl der Pins auf der Karte gleich der Zahl der Einsätze mit `_marker`; nach Rückkehr zu „Gemischt" wieder 7 | — |
| Hervorhebung aus einer Extremwert-Kachel funktioniert auf frisch gesetzten Pins | Browser, Kachel „Längste Einsatzstrecke" überfahren | Zeile hervorgehoben, kein `this._point is undefined` in der Konsole | — |
| Festsetzung wird beim Tabwechsel gelöst | Browser | Nach Klick auf die Kachel: 1 aktiv, 1 Zeile markiert. Nach Tabwechsel: 0 / 0 | — |
| Tab im **Fragment**, nicht als Abfrageparameter | Browser | `#t=mix` / `#t=air` / `#t=ground`; `history.replaceState`, die Chronik wächst nicht | — |
| Geteilter Link | Browser, frischer Aufruf mit `#t=air` | Luftrettungs-Tab aktiv, zehn Kacheln | — |
| Fragment von Hand geändert, Seite schon offen | Browser, `location.hash = '#t=ground'` | Ansicht wechselt mit (`hashchange`). **Ohne diese Behandlung** stand in der Adresszeile ein anderer Tab als auf dem Bildschirm — beim ersten Durchlauf genau so beobachtet | — |
| Tab, den es im Zeitraum nicht gibt | Browser, `#t=air` auf einem Monat ohne Tableiste | still verworfen, keine Fehlermeldung | — |
| Pfeiltasten in der Tableiste | Browser, `ArrowRight` auf dem aktiven Tab | wechselt zum nächsten und setzt den Fokus mit | Auf einem schmalen Gerät gegenprüfen |

### Hinweis auf neutrale Diensttage (E31)

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Steht in „Gemischt" | Browser, März | „Ein Diensttag dieses Zeitraums ist mitgezählt, aber noch keiner Art zugeordnet … Zuordnung nachtragen" | — |
| Steht **nicht** in den Artentabs | Browser, März | in Luftrettung und Bodengebunden `hidden` — dort zählen sie nicht mit | — |
| Steht in einer Ansicht ohne Tableiste | Browser, April und Mai | sichtbar; in Juni (keine neutralen) `hidden` | — |
| Verlinkt auf die Zuordnung | Browser | `nachbearbeitung.php` — die Seite besteht, solange offene Zuordnungen vorliegen, und das ist genau dann der Fall | — |

### Suche

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Filter **Art** | Browser, Auswahl durchprobiert | luftgebunden 6, bodengebunden 3, ohne Zuordnung 3, (egal) 12 — Summe stimmt | — |
| Werte statt Beschriftungen | Browser, Optionen ausgelesen | `air` = „🚁 luftgebunden", `ground` = „🚑 bodengebunden", `neutral` = „◌ ohne Zuordnung"; die Liste kommt aus `dt_art_symbole()` | — |
| Filter **Transportart** | Browser | `air` 4, `ambulant` 1, (egal) 12; Optionen `air`/`ground`/`ambulant` mit „Luft"/„Boden"/„Ambulant" aus dem Feldkatalog (`mf_optionen`) | — |
| Filter **NA-Begleitung** und **Fehleinsatz** | Browser | ja 2 / ja 2, nein 10 — Dreiwertlogik wie bei den übrigen Haken | — |
| Neuer Block „Einsatz" nur bei Bestand | Browser | sichtbar, solange ein Fehleinsatz im Bestand liegt; Bedingung in `GRUPPE_NUR_WENN` | Ohne Fehleinsatz im Bestand gegenprüfen |
| Fragment-Kurznamen | Browser, jeder Filter einzeln | `art`, `ta`, `nb`, `fe` — keiner kollidiert mit einem bestehenden oder einem zurückgezogenen Namen | — |
| Geteilter Link | Browser, frischer Aufruf `#art=ground&fe=n` | 2 Treffer, beide Auswahlfelder gesetzt, die Blöcke `einsatz` und `wer` aufgeklappt | — |
| Bestehende Filter unverändert | Browser, Standort und Rettungsmittel | Standort Nord 4 Treffer; die Kurznamen `st` und `ac` unverändert | — |
| Artsymbol in der Trefferliste | Browser | erste Zeile `aria-label="bodengebunden"` — die Textalternative hängt an jeder Zelle (**A7c**, sinngemäß für die Tabelle) | — |

### Formatwege

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Export erzeugt die neuen Spaltennamen | Browser, vollständiger CSV-Export erstellt und entpackt | `phase_03_ausruecken`, `phase_07_ankunft_klinik`; die übrigen sechs Phasen unverändert | — |
| Export führt die Felder der Etappe 2 | dieselbe Datei | `art`, `transport_art`, `na_begleitung`, `fehleinsatz`, `ziel_lat`, `ziel_lon`, `abfahrt_regel`, `pat_start_adresse/_lat/_lon` | — |
| `felder.csv` neutral | dieselbe Datei | `strecke_m;int;m;nein;Einsatzstrecke (distance_m)` | — |
| Dateiname | dieselbe Datei | `einsatzdokumentation_export_18-08-2026_csv_ohne-pers_unverschl_testkonto.zip` | — |
| Alte Spaltennamen bleiben lesbar | Importprofil ausgewertet (Node, `ImportProfile.profiles`) | `phase_03_abflug` und `phase_03_ausruecken` zeigen beide auf `phase:3`, `phase_07_landung_krankenhaus` und `phase_07_ankunft_klinik` beide auf `phase:7` | **Vollständiger Rundlauf bleibt offen** — siehe unten |
| Profilerkennung leidet nicht | Code gegengelesen, `findeKopfzeile()` | Gezählt werden erwartete Namen, die in der Datei **vorkommen**; zusätzliche Zweitnamen in `expectedHeaders` senken die Trefferzahl nicht | — |

### Syntax und Auslieferung

| Was | Wie geprüft | Ergebnis | Bleibt für dich |
|---|---|---|---|
| Alle PHP-Dateien (**A15**) | `php -l` über `server/**/*.php`, PHP 8.4.19 | fehlerfrei | Auf PHP 8.3 gegenprüfen |
| Alle JavaScript-Dateien (**A15**) | `node --check`, Node 22.22.2, ohne `vendor/` | fehlerfrei | — |
| Eingebettetes JavaScript (**A15**) | aus den **ausgelieferten** Seiten geholt, je Block `node --check` | `zeitraum.php` (2 Blöcke), `suche.php` (2), `index.php`, `einsatz.php`, `einsatz_form.php`, `einstellungen.php` (2), `nachbearbeitung.php`, `import.php` (2) — alle fehlerfrei | — |
| Keine Konsolenfehler | Browser, 18 Seiten aufgerufen | keine — mit Ausnahme der Kartenkacheln, die im abgeschotteten Netz nicht geladen werden können (`ERR_TUNNEL_CONNECTION_FAILED`) | — |

### Gefundene und behobene Fehler

| Nr. | Befund | Wie gefunden |
|---|---|---|
| — | Ein von Hand geändertes Fragment (`#t=air` → `#t=ground`) wirkte nicht: Für den Browser ist das keine neue Seite, die Ansicht blieb stehen, während die Adresszeile den anderen Tab zeigte | Browsertest mit geteiltem Link auf der bereits offenen Seite |
| P20 | Zwei Exportspalten hießen noch nach der Luftrettung (`phase_03_abflug`, `phase_07_landung_krankenhaus`), dazu die Erläuterung zu `strecke_m` und der Dateinamenspräfix | Beim Abarbeiten von Abschnitt 4.8 („Spaltenköpfe neutralisieren") gegen den tatsächlichen Export |
| P21 | Widerspruch im Konzept: 3.7 verlangt „keine eigene Spalte" für die Art, 4.6 verlangt sie ausdrücklich. Die Einsatztabelle führt keinen Rettungsmittelnamen, an den ein Symbol passte | Beim Umsetzen der Spalte |
| — | Die Windenspalte der Einsatztabelle folgte der datengetriebenen Regel **noch nicht**, obwohl A13d sie als Vorbild für die Bergwachtspalte nennt | Beim Lesen von A13d gegen `missiontable.js` |
| — | `api/suchindex.php` behauptete im Kopf „sechs Abfragen"; es sind fünf, seit die Stammdatenabfrage mit den Snapshot-Spalten entfallen ist. Ebenso beschrieb `Technik.md` einen Rückfall auf `days.base`/`days.aircraft`, den es seit 6.0.0 nicht mehr gibt | Beim Nachziehen der Dokumentation |
| P22 | Die drei umbenannten Seiten `flugtag_neu.php`, `flugtag_loeschen.php` und `flugtag_datum.php` lagen noch im Repository und wurden mitgeliefert. Sie arbeiten auf dem alten Datenmodell — `flugtag_loeschen.php` übergibt `trash_scope_day()` einen Datumstext, wo seit 6.0.0 eine `int`-Kennung steht. Verlinkt ist keine, über die Adresszeile erreichbar alle drei | Beim Packen des Auslieferungs-ZIP: Die Dateiliste enthielt `server/flugtag_datum.php` |

### Was in dieser Etappe nicht prüfbar war

**Der vollständige Export-Import-Rundlauf.** Der Import setzt einen
entsperrten Inhaltsschlüssel voraus — er verschlüsselt die geschützten Angaben
im Browser, bevor er sie sendet. Das Testkonto der Arbeitsumgebung hat keinen:
Er entsteht nur bei der Passwortvergabe, und die läuft über einen Einmal-Link
aus einer E-Mail. Geprüft ist deshalb, dass der Export die neuen Spalten
schreibt und dass das Importprofil alte **wie** neue Namen auf dasselbe Ziel
führt — nicht, dass ein kompletter Durchlauf zeilengleich zurückkommt.

**Die Kartendarstellung selbst** — unverändert gegenüber Etappe 2. Neu geprüft
ist immerhin, dass beim Tabwechsel die richtige **Zahl** an Pins auf der Karte
liegt und keine verwaisten Marker zurückbleiben; wie der Ausschnitt aussieht,
sagt weiterhin erst ein Blick.

**Anzeige auf schmalen Geräten.** Die Tableiste bricht um statt zu scrollen, und
die beiden neuen Spalten sind schmal gehalten — geprüft ist das Verhalten der
CSS-Regeln, nicht ihr Ergebnis auf einem echten Telefon.

---

## Was insgesamt für dich zu prüfen bleibt

Diese Punkte sind in der Arbeitsumgebung **grundsätzlich** nicht prüfbar. Sie
sammeln sich hier über alle Etappen.

| Was | Warum hier nicht prüfbar | Abnahmekriterium |
|---|---|---|
| Migration auf dem **echten** Bestand | Der Testbestand ist nachgebaut. Reale Daten enthalten Fälle, die niemand vorhersieht — insbesondere `days.crew`, das die Migration absichtlich blockieren lässt | A11 |
| Uhr auf echter Hardware: Kopplung, Upload, `day_ref` | Kein Connect-IQ-SDK, kein Gerät | A14 |
| FTP-Deploy und das Verhalten der Ausnahmeliste bei **umbenannten und gelöschten** Dateien | Kein Zugriff auf den Webspace. `flugtag_*.php` → `diensttag_*.php` heißt: Die alten Dateien müssen auf dem Server verschwinden — im Repository lagen sie bis Etappe 3 noch (Befund P22), auf einer bereits aktualisierten Installation liegen sie also ebenfalls | — |
| Backup und Wiederherstellung mit einer echten, im Browser verschlüsselten Datei | Die Verschlüsselung geschieht ausschließlich im Browser mit dem Kontoschlüssel | A9, A10 |
| Anzeige auf schmalen Geräten | Mehrere Entscheidungen nehmen ausdrücklich darauf Rücksicht (Tableiste, Symbol statt Spalte). Geprüft ist das Verhalten der CSS-Regeln, nicht ihr Ergebnis auf einem echten Telefon | A7c, A13a |
| **Vollständiger Export-Import-Rundlauf** | Der Import verschlüsselt im Browser und braucht dafür einen entsperrten Inhaltsschlüssel; den bekommt ein Konto nur über die Passwortvergabe per Einmal-Link. Geprüft ist, dass der Export die neuen Spalten schreibt und das Importprofil alte **wie** neue Namen auf dasselbe Ziel führt | — |
| MySQL statt MariaDB | Geprüft wurde MariaDB 10.11.14 | A11 |
| PHP 8.3 | Geprüft wurde 8.4.19 | A15 |
| **Kartendarstellung**: gestrichelte Luftlinie, Pins, ausgewiesene Länge | Der Prüfstand liefert HTML und JSON, keinen gerenderten Kartenausschnitt. Geprüft ist, dass die Daten dafür vollständig ankommen und die Regeln im Code stehen | A13g, A13h, A13n, A13o |
| **Bedienung des Ortsfelds**: Photon-Abfrage, Plus-Code-Erkennung, Chip | Erfordert einen Browser mit Netzzugang zu photon.komoot.io | A13l, A13m |
| Fachliche Abnahme im Alltagsgebrauch | — | A1–A16 |
| Verhalten mit **mehreren Konten** an denselben zentralen Stammdaten | Der Testbestand hat drei Konten, aber niemand arbeitet gleichzeitig darin. Besonders die zweite Stufe aus A12 betrifft alle: Solange EIN Konto eine Zuordnung offen hat, lässt sich die Bedingung nicht setzen | A12, A4a |
