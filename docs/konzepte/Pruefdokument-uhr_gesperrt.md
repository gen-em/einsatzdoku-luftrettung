# Prüfdokument — Hotfix `missions.manual` → `uhr_gesperrt`

**Backlog Nr. 238 · Web 20.25.0 · Zweig `claude/festive-fermi-el0avv` · 20.09.2026**

Dieses Dokument beantwortet nicht „ist es belegt?", sondern **„was muss ich
noch tun?"**. Es bleibt liegen, bis seine Prüfliste abgehakt ist, und wird
dann gelöscht (CLAUDE.md 7).

---

## 1. Was NICHT geprüft werden konnte, und warum

Das steht bewusst zuerst.

### 1.1 Die Weboberfläche — vollständig ungeprüft

**Kein einziger Klick im Browser.** Die Sitzung lief in einem
Wegwerf-Container ohne laufende Anwendung; es gab keine eingerichtete
Installation, an der man das Einsatzformular hätte öffnen können. Nach
CLAUDE.md 6 ist das zu sagen und nicht zu verschweigen: **Die Änderung ist
gelesen und maschinell geprüft, nicht bedient.**

Konkret ungeprüft sind damit alle Wege der Prüfliste in Abschnitt 4 — das
Speichern eines Einsatzes, der Uhr-Upload danach, GPX- und Datei-Import, der
Schnitt, Sicherung und Export, der Demo-Reset. Das sind zugleich die Wege,
auf denen die geänderten `INSERT`- und `UPDATE`-Anweisungen tatsächlich
laufen. Maschinell belegt ist, dass sie **syntaktisch** stimmen und dass die
Spalte da ist, gegen die sie schreiben; **nicht** belegt ist, dass der
jeweilige Weg als Ganzes durchläuft.

### 1.2 Der Bilderlauf, der Stilvergleich und die Vollständigkeitsprüfung

Nicht gefahren, und hier ist das vertretbar: Die Änderung fasst **keine**
Zeile CSS, kein `ui.php`-Baustein und keine Beschriftung an. Was sie an
sichtbarem Text ändert, ist der Anzeigetext **einer** Migration
(„Herkunft … getrennt von der Uhr-Sperre", sichtbar in `update.php` und
`betrieb_updates.php`). Die Wortliste, die genau für sichtbaren Text zuständig
ist, lief (Abschnitt 2).

### 1.3 MySQL 8.4.11 und aufwärts

Ab 8.4.11 ist `MANUAL` wieder frei. Dass die Umbenennung dort ebenfalls
funktioniert, ist nicht gemessen, sondern erschlossen: `uhr_gesperrt` ist auf
keiner Fassung reserviert. Ein Abbild `mysql:8.4.11` stand im Container nicht
zur Verfügung.

### 1.4 Der Produktivbestand selbst

Die Migration ist gegen **künstlichen** Bestand gemessen (sieben Einsätze,
vier mit gesetzter Sperre) — nicht gegen eine Kopie des echten. Das ist
Prüfpunkt 4.2 und gehört dir.

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Prüfmittel | Ergebnis |
|---|---|
| `php -l` über `server/` und `tools/schemaprobe/` | **0 Fehler** |
| `tools/wortliste/wortliste.py` | **0 Treffer** außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Teilstring-Fallen (99 Regeln, 99 gegriffen, 35 Dateien) |
| `tools/migrationsregister/pruefen.php` | **60/60 Kennungen, 257 Spalten, 30 Löschungen, 0 Befunde** (vorher 59/59, 29, 0) |
| `tools/kettenaufrufe/pruefen.py` | **30 Aufrufe geprüft, 0 Befunde, 0 ungeprüft**; Selbstprobe 10/10 |
| Backlog-Nummern (`grep … uniq -d`) | **leer** |
| `tools/schemaprobe/probe.php` gegen **MySQL 8.4.0** | **19 Erwartungen, 0 Fehlschläge** |
| … gegen **MariaDB 10.6** (dokumentierte Untergrenze) | **19 Erwartungen, 0 Fehlschläge** |
| … gegen **MariaDB 10.11** (örtlich) | **19 Erwartungen, 0 Fehlschläge** |
| `tools/spurprobe/probe.php` gegen eine Wegwerf-Installation | Teil 6 (Schnitt und Nachlieferung — der Teil mit der geänderten Anweisung): **21 von 21**. Von 40 Erwartungen insgesamt 4 nicht erfüllt, **alle vier „0 Punkte im Bestand"** — die Wegwerf-Installation hat keine Spuren, das ist kein Befund am Code |
| `yaml.safe_load` über `pruefung.yml` | gültig; Aufträge `pruefung`, `schema`; Matrix `MySQL 8.4.0`, `MariaDB 10.6` |

### 2.1 Der Originalfehler ist reproduziert, nicht nur zitiert

Das **alte** `schema.sql` (Commit `7150793`) gegen **MySQL 8.4.0**:

```
ERROR 1064 (42000) at line 364: … near 'manual     TINYINT(1) NOT NULL DEFAULT 0,
           -- ausschliesslich: Uhr uebe' at line 23
```

Wortgleich mit der Meldung vom Staging-Webspace. Das **neue** `schema.sql`
legt auf derselben Datenbank **42 Tabellen** fehlerfrei an.

### 2.2 Die Gegenprobe: Die Prüfung kann rot werden

| Lauf | Ergebnis |
|---|---|
| Altstand gegen MySQL 8.4.0 | **1 Prüfung, 1 Fehlschlag**, Rückgabe 1, Detail = Originalmeldung |
| Altstand gegen MariaDB 10.6 | **6 Prüfungen, 4 Fehlschläge**, Rückgabe 1 |
| Neuer Stand, beide Fassungen | 19 / 0, Rückgabe 0 |

**Und das ist der Beleg dafür, dass die Matrix nötig ist:** Derselbe Altstand
legt auf MariaDB 10.6 klaglos **42 Tabellen** an. Ein Prüflauf gegen eine
Fassung hätte den Fehler nie gefunden.

### 2.3 Kein Datenverlust — gemessen, nicht behauptet

Fall 2 der Schemaprobe legt sieben Einsätze an (IDs 1–7, vier davon mit
gesetzter Sperre), fährt den Migrationslauf und vergleicht hinterher:

```
vorher   {1:1, 2:1, 3:1, 4:1, 5:0, 6:0, 7:0}
nachher  {1:1, 2:1, 3:1, 4:1, 5:0, 6:0, 7:0}
Definition: tinyint(1) / NOT NULL / DEFAULT 0
```

Identisch auf MySQL 8.4.0, MariaDB 10.6 und MariaDB 10.11.

---

## 3. Was geändert wurde, in einem Satz je Gruppe

- **Schema und Migration:** `schema.sql` führt `uhr_gesperrt`; eine 60.
  Migration `2026_09_20_uhr_gesperrt` benennt per `CHANGE` um; die
  Skip-Prüfung von `2026_07_18` kennt jetzt **beide** Namen.
- **Anwendungscode:** 14 Spaltenbezüge umgestellt, zwei davon **per Alias**
  (`uhr_gesperrt AS manual`), damit sich am Dateiformat nichts ändert.
- **Zweiter Blocker mitbehoben:** `DEFAULT UTC_TIMESTAMP()` → `DEFAULT
  (UTC_TIMESTAMP())` an vier Stellen. MySQL wies die ungeklammerte Form auf
  **jeder** Fassung ab; die Anwendung war seit Web 20.16.5 auf MySQL gar nicht
  installierbar.
- **Kette:** `tools/schemaprobe/` in Stufe 1, Matrix über zwei Fassungen.

**Unverändert und mit Absicht:** der `origin`-Wert `'manual'`, das
Geräte-Präfix `manual-<konto>`, der `start_src`-Wert `'manual'` — und der
**Dateischlüssel `manual`** in Sicherung, Export und JSON-Vertrag.

---

## 4. Prüfliste

Je Punkt: Bedienweg, erwartetes Ergebnis, **und woran ein Scheitern zu
erkennen ist**. Die Reihenfolge ist nicht beliebig — 4.1 und 4.2 sind die
Tore.

### ☐ 4.1 Frische Installation auf Staging (8.4.x), in LEERER Datenbank

**Vorher:** Die Tabellen aus dem Fehlversuch müssen weg. Der Abbruch bei
Zeile 386 hat die Datenbank **halb angelegt** zurückgelassen — `install.php`
läuft dagegen erneut in einen Fehler, und zwar in einen anderen.

```sql
DROP DATABASE <name>;
CREATE DATABASE <name> CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Dazu auf dem Server löschen: `server/config.php`, `server/install.lock`,
`server/install-nachweis-*.txt`.

**`server/install.php` liegt nicht auf dem Server** — sie steht seit Web
20.15.2 in der Ausnahmeliste beider FTPS-Schritte (Backlog Nr. 214). Für eine
leere Anlage muss sie **einmal von Hand hinauf** und danach wieder gelöscht
werden.

**Weg:** `install.php` aufrufen, Formular ausfüllen, absenden.

**Erwartet:** Die Einrichtung läuft durch und legt **42 Tabellen** an.

**Scheitern erkennt man an:** einem `SQLSTATE[42000] … 1064`. Steht dort noch
`near 'manual …'`, ist das alte `schema.sql` auf dem Server — dann hat der
Deploy nicht gegriffen. Steht dort `near 'UTC_TIMESTAMP()'`, fehlt die
Klammerung, also ebenfalls ein alter Stand. Jede andere `1064`-Meldung ist ein
**neuer** Befund und gehört gemeldet.

**Gegenprobe danach:**
```sql
SELECT COLUMN_NAME FROM information_schema.columns
 WHERE table_schema = DATABASE() AND table_name = 'missions'
   AND COLUMN_NAME IN ('manual','uhr_gesperrt');
```
Erwartet: **genau eine Zeile**, `uhr_gesperrt`. Zwei Zeilen wären der stille
Doppelanlage-Fall und ein sofortiger Stopp.

### ☐ 4.2 Migration einer Kopie des Produktivbestands

**Nicht gegen Produktiv selbst.** Eine Kopie einspielen, dann `update.php`
aufrufen.

**Vorher zählen:**
```sql
SELECT COUNT(*) AS gesamt, SUM(`manual`) AS gesperrt FROM missions;
```

**Weg:** Deploy, dann `update.php` als Administratorin aufrufen und den
Migrationslauf starten.

**Erwartet:** Genau **eine** Migration läuft (`2026_09_20_uhr_gesperrt`), alle
übrigen stehen auf „Bereits angewendet". Danach:
```sql
SELECT COUNT(*) AS gesamt, SUM(uhr_gesperrt) AS gesperrt FROM missions;
```
**Beide Zahlen müssen exakt die von vorher sein.**

**Scheitern erkennt man an:** einer abweichenden Summe (Datenverlust — sofort
stoppen, Sicherung zurück), oder daran, dass der Lauf mit `1054` oder `1060`
abbricht. Besonders heimtückisch: Wenn `gesperrt` auf **0** fällt, während
`gesamt` stimmt, wurde eine leere Spalte daneben angelegt statt umbenannt.
Dann prüfen, ob `missions` **beide** Spalten führt.

### ☐ 4.3 Einsatzformular speichern, danach Uhr-Upload

Der eigentliche Zweck der Spalte.

**Weg:** (1) Einen Einsatz öffnen, der von der Uhr stammt (Herkunft „Uhr",
Hinweis „Dieser Einsatz stammt von der Uhr" steht oben). (2) Etwas ändern,
speichern. (3) Die Uhr denselben Einsatz noch einmal hochladen lassen — oder
`ingest.php` mit demselben `client_ref` und geänderten Zeiten füttern.

**Erwartet:** Nach (2) steht `uhr_gesperrt = 1`. Nach (3) sind Zeiten, Phasen
und Reanimation **unverändert**; GPS-Punkte werden weiterhin angehängt. Die
Antwort von `ingest.php` nennt `kept_phases` / `kept_resus`.

**Scheitern erkennt man an:** überschriebenen Zeiten nach (3). Das wäre der
gefährlichste Fehlerfall dieses Hotfix — er wirft **keine** Fehlermeldung, weil
`(int)null === 1` schlicht `false` ist. Wenn die Uhr überschreibt, sind
`ingest.php:505` und `:636` nicht als Paar geändert.

**Zweite Gegenprobe:** Nach (2) darf der Hinweis „Dieser Einsatz stammt von der
Uhr" beim erneuten Öffnen **nicht mehr** erscheinen. Erscheint er bei
**jedem** Einsatz, auch bei von Hand angelegten, liest `einsatz_form.php:782`
ins Leere.

### ☐ 4.4 GPX-Import, Datei-Import, Schnitt

Drei Wege, drei geänderte `INSERT`-Anweisungen.

**Weg:** (a) Eine GPX-Datei als Einsatz importieren. (b) Eine CSV-Datei
importieren (Import/Export → Import). (c) Aus einem Ruhesegment einen Einsatz
schneiden.

**Erwartet:** Alle drei legen einen Einsatz an. In der Datenbank:
```sql
SELECT id, origin, uhr_gesperrt FROM missions ORDER BY id DESC LIMIT 3;
```
`uhr_gesperrt = 1` bei allen dreien; `origin` = `import`, `import`, `schnitt`.

**Scheitern erkennt man an:** einer Fehlerseite mit `1054 Unknown column
'uhr_gesperrt'` (Deploy unvollständig) oder `1064` (alter Stand auf dem
Server). Ein Import, der **ohne** Meldung nichts anlegt, ist ebenfalls ein
Befund.

### ☐ 4.5 Sicherung erstellen und einspielen — und ein ALTES Backup

Der Punkt, an dem der Alias hängt.

**Weg:** (a) Eine neue Sicherung erstellen, herunterladen, entpacken.
(b) Sie wieder einspielen. (c) **Ein Backup von vor diesem Hotfix**
einspielen.

**Erwartet bei (a):** In der Datei heißt das Feld **`manual`**, nicht
`uhr_gesperrt`. Zu prüfen ohne Werkzeug:
```
zcat <datei> | grep -o '"manual"' | head -1     # muss etwas finden
zcat <datei> | grep -o '"uhr_gesperrt"' | head -1   # muss LEER bleiben
```
**Erwartet bei (b) und (c):** Beide spielen durch, und die Einsätze tragen
hinterher ihre Sperre.

**Scheitern erkennt man an:** `"uhr_gesperrt"` in der Datei — dann fehlt der
Alias in `backup_lib.php:233`, und **alte wie neue** Sicherungen werden
unbrauchbar, ohne dass irgendwo ein Fehler erschiene. Nach (b) und (c)
zusätzlich zählen:
```sql
SELECT COUNT(*) FROM missions WHERE uhr_gesperrt = 1;
```
Steht dort **0**, obwohl die Datei gesetzte Werte trug, ist die Abbildung beim
Einspielen kaputt (`backup_lib.php:1876/1884`).

### ☐ 4.6 Export: Schlüssel `manual` in der Datei vorhanden und korrekt

**Weg:** CSV-Export mit Personenbezug erzeugen, `einsaetze.csv` öffnen.

**Erwartet:** Die Kopfzeile führt **`manual`** als 8. Spalte (zwischen `final`
und `edited`), und die Werte stimmen mit
`SELECT id, uhr_gesperrt FROM missions` überein.

**Scheitern erkennt man an:** einer Spalte `uhr_gesperrt` in der Kopfzeile
(Alias fehlt in `export_data.php:293`) oder an einer Spalte `manual`, die
**überall leer oder 0** ist (der Alias fehlt, und `$r['manual']` liest ins
Leere — `export.js` schriebe dann stumm nichts).

**Zusatz:** Auch `felder.csv` öffnen. Die Zeile zu `manual` muss noch da sein.

### ☐ 4.7 Demo-Reset

**Weg:** Betrieb → Demo-Konto zurücksetzen.

**Erwartet:** Der Reset läuft durch und legt **106 Einsätze** an, davon
**104** mit gesetzter Sperre:
```sql
SELECT COUNT(*) AS gesamt, SUM(uhr_gesperrt) AS gesperrt
  FROM missions WHERE user_id = <demo-konto>;
```

**Scheitern erkennt man an:** `gesperrt = 0` bei `gesamt = 106`. Dann wurde
der Dateischlüssel der Fixture (`server/demo/fixture.json.gz`, die selbst eine
Sicherungsdatei ist) nicht auf die Spalte abgebildet. Die Fixture wurde
**nicht** neu erzeugt und muss es auch nicht — genau deshalb bleibt der
Dateischlüssel `manual`.

### ☐ 4.8 Stufe 1 der Kette ist grün

**Weg:** Den Zweig pushen und den Lauf „Prüfung" ansehen.

**Erwartet:** Beide Aufträge grün. Der neue Auftrag heißt **„Schema gegen
MySQL 8.4.0"** und **„Schema gegen MariaDB 10.6"**; die Zusammenfassung nennt
je `19 Pruefungen, 0 Fehlschlaege`.

**Scheitern erkennt man an:** einem Auftrag, der **gar nicht erscheint** (dann
ist die Matrix falsch geschrieben), oder an einem Dienstbehälter, der nicht
gesund wird (dann stimmt das `--health-cmd` nicht — es unterscheidet sich
zwischen den beiden Abbildern und steht deshalb in der Matrix).

---

## 5. Grenzen der benutzten Prüfmittel

- **`tools/schemaprobe/` prüft das Schema und die Migrationen, nicht die
  Anwendung.** Ob `ingest.php` auf MySQL 8.4 durchläuft, sagt sie nicht — das
  ist Prüfpunkt 4.3. Sie sieht außerdem nur DDL, das als Zeichenkette im
  Katalog steht; eine Migration, die ihr SQL zur Laufzeit aus Variablen baut,
  ist für sie unsichtbar (dieselbe Grenze wie bei
  `tools/migrationsregister/`).
- **`tools/migrationsregister/` führt vier erklärte Ausnahmen** (`ausnahmen.json`)
  — vier Spalten, die eine Migration über eine Schleife mit eingesetzten Namen
  löscht. Sie sind unverändert; die Prüfung tut nicht so, als hätte sie sie
  gesehen.
- **`tools/wortliste/` prüft Begriffe, nicht Bezeichner.** Dass eine
  Datenbankspalte umbenannt wurde, ist ihr gleichgültig; sie hat hier nur
  belegt, dass der geänderte **Anzeigetext** der Migration nichts
  Luftgebundenes einführt.
- **`tools/kettenaufrufe/` prüft Schalternamen, nicht Semantik.** Dass
  `--datenbank nadoku_schemaprobe` ein sinnvoller Wert ist, sagt sie nicht.
- **`tools/spurprobe/` lief gegen eine leere Installation.** Die vier nicht
  erfüllten Erwartungen betreffen sämtlich einen Bestand, den es dort nicht
  gab. Gegen die Staging-Installation mit Bestand ist er **nicht** gelaufen;
  das wäre eine sinnvolle Ergänzung zu Prüfpunkt 4.2.

---

## 6. Wenn etwas schiefgeht

**Der Rückweg ist die Sicherung, nicht eine Gegen-Migration.** Es gibt
absichtlich keine Migration, die `uhr_gesperrt` wieder zu `manual` macht —
sie liefe auf MySQL 8.4.0–8.4.10 selbst in die `1064`, die dieser Hotfix
behebt.

Muss zurückgerollt werden: Sicherung einspielen (alte wie neue sind lesbar —
der Dateischlüssel hat sich nicht geändert), alten Stand ausliefern, fertig.
Die Datenbank trägt dann wieder `manual`, und die Anwendung sucht sie auch so.

**Das Backup-Tor der Kette macht vor dem Produktiv-Deploy ohnehin eine
Sicherung** (CLAUDE.md 3). Prüfen, dass sie da ist, bevor der Tag gesetzt wird.
