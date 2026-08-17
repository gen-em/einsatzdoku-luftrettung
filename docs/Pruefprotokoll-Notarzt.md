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
mysqld_safe &

# Altbestand: Schema von VOR dem Umbau, Testdaten, dann migrieren
mariadb -e "CREATE DATABASE edoku_bestand CHARACTER SET utf8mb4"
mariadb edoku_bestand < docs/pruefgrundlage/schema-vor-6.0.0.sql
mariadb edoku_bestand < docs/pruefgrundlage/testbestand.sql
php server/update.php          # braucht server/config.php auf edoku_bestand

# Neuinstallation
mariadb -e "CREATE DATABASE edoku_neu CHARACTER SET utf8mb4"
mariadb edoku_neu < server/schema.sql
```

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
