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
| **Browser** | Im Chromium der Arbeitsumgebung tatsächlich aufgerufen und angesehen. |
| **Gegengelesen** | Nur gelesen, nicht ausgeführt. Wird ausdrücklich so benannt und **nie** als „geprüft" ausgegeben. |

## Prüfumgebung

| | |
|---|---|
| PHP | 8.4.19 (CLI) — das Abnahmekriterium A15 nennt 8.3; geprüft wurde auf 8.4 |
| Node | 22.22.2 |
| Datenbank | MariaDB 10.11.14 (Ubuntu noble), lokal installiert |
| Connect-IQ-SDK | **nicht vorhanden** — Uhr-Code kann nicht kompiliert werden |
| Webserver | eingebauter PHP-Server (`php -S`) mit einem Testrouter, der eine Sitzung setzt — siehe unten |

Zwei Datenbanken stehen nebeneinander:

- **`edoku_bestand`** — aufgebaut aus dem `schema.sql` des Standes *vor* dem
  Umbau (`git show HEAD:server/schema.sql`), befüllt mit einem nachgebauten
  Altbestand, danach migriert. Bildet den Weg einer bestehenden Installation ab.
- **`edoku_neu`** — aufgebaut aus dem neuen `schema.sql`. Bildet eine
  Neuinstallation ab.

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
Skript.

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

## Was insgesamt für dich zu prüfen bleibt

Diese Punkte sind in der Arbeitsumgebung **grundsätzlich** nicht prüfbar. Sie
sammeln sich hier über alle Etappen.

| Was | Warum hier nicht prüfbar | Abnahmekriterium |
|---|---|---|
| Migration auf dem **echten** Bestand | Der Testbestand ist nachgebaut. Reale Daten enthalten Fälle, die niemand vorhersieht — insbesondere `days.crew`, das die Migration absichtlich blockieren lässt | A11 |
| Uhr auf echter Hardware: Kopplung, Upload, `day_ref` | Kein Connect-IQ-SDK, kein Gerät | A14 |
| FTP-Deploy und das Verhalten der Ausnahmeliste bei **umbenannten und gelöschten** Dateien | Kein Zugriff auf den Webspace. `flugtag_*.php` → `diensttag_*.php` heißt: Die alten Dateien müssen auf dem Server verschwinden | — |
| Backup und Wiederherstellung mit einer echten, im Browser verschlüsselten Datei | Die Verschlüsselung geschieht ausschließlich im Browser mit dem Kontoschlüssel | A9, A10 |
| Anzeige auf schmalen Geräten | Mehrere Entscheidungen nehmen ausdrücklich darauf Rücksicht (Tableiste, Symbol statt Spalte) | A7c, A13a |
| MySQL statt MariaDB | Geprüft wurde MariaDB 10.11.14 | A11 |
| PHP 8.3 | Geprüft wurde 8.4.19 | A15 |
| Fachliche Abnahme im Alltagsgebrauch | — | A1–A16 |
| Verhalten mit **mehreren Konten** an denselben zentralen Stammdaten | Der Testbestand hat drei Konten, aber niemand arbeitet gleichzeitig darin. Besonders die zweite Stufe aus A12 betrifft alle: Solange EIN Konto eine Zuordnung offen hat, lässt sich die Bedingung nicht setzen | A12, A4a |
