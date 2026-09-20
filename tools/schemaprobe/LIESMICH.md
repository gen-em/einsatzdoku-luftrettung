# Schemaprobe — läuft das Schema auf der Datenbank, gegen die es laufen soll?

```
php tools/schemaprobe/probe.php --datenbank nadoku_probe \
    [--host 127.0.0.1] [--port 3306] [--benutzer probe] [--passwort probe] \
    [--klient mariadb]
php tools/schemaprobe/probe.php --selbstprobe
```

Rückgabewert `0` = alle Erwartungen erfüllt · `1` = mindestens eine nicht ·
`2` = die Probe selbst kam nicht zum Laufen (Verbindung, fehlende Datei,
unbekannter Schalter).

## Wofür

Am 20.09.2026 scheiterte die Einrichtung auf dem neuen Staging-Webspace:

```
SQLSTATE[42000] … 1064 … near 'manual TINYINT(1) NOT NULL DEFAULT 0, …' at line 23
```

MySQL führt `MANUAL` von **8.4.0 bis 8.4.10** als reserviertes Wort (ab 8.4.11
wieder nicht). `server/schema.sql` legte die Spalte ungequotet an.

**Dahinter lag ein zweiter Fehler, den erst das Beheben des ersten sichtbar
machte:** `DEFAULT UTC_TIMESTAMP()` ohne Klammern, von MySQL auf **jeder**
Fassung abgewiesen. Vier Stellen, zwei davon in `schema.sql`.

Beide hatten dieselbe Ursache, und die ist nicht SQL: **Entwickelt und geprüft
wird gegen MariaDB, ausgeliefert wird gegen MySQL.** MariaDB nimmt beide
Schreibweisen an und hält `MANUAL` nicht für reserviert. Die Anwendung ließ
sich dadurch seit Web 20.16.5 auf MySQL überhaupt nicht einrichten — und
gemerkt hat es niemand, weil es niemand probiert hat.

Diese Probe probiert es, bei jedem Push, in Sekunden.

## Was sie misst

Vier Fälle, 19 Erwartungen. Sie legt die genannte Datenbank je Fall neu an.

| Fall | Frage |
|---|---|
| 1 | Lässt sich aus `schema.sql` eine leere Anlage bauen — und bleibt danach jede Migration stehen (Vorabliste)? |
| 2 | Kommt eine **bestehende** Datenbank mit Bestand über die Migrationen, **ohne einen Wert zu verlieren**? |
| 3 | Was tut eine Datenbank, deren Migrationsregister fehlt? |
| 4 | Und eine, auf der die Umbenennung schon gelaufen ist? |

**Fall 2 ist der wichtigste.** Er legt sieben Einsätze an, vier davon mit
gesetzter Uhr-Sperre, migriert und vergleicht hinterher **Wert für Wert**.
Eine Migration, die Daten verliert, fällt hier auf und nirgends sonst.

Fall 3 und 4 prüfen die beiden Skip-Bedingungen, an denen still etwas
schiefgehen kann:

- Fragte `2026_07_18_manuelle_einsaetze` nur nach **einem** der beiden Namen,
  legte sie die andere Spalte **leer daneben** an. Ein Fehler wäre das nicht:
  `1060` steht in der Schluckliste von `migrationen_lauf()`, der Lauf ginge
  weiter, und gelesen würde ab da die leere Spalte.
- Fehlt der neuen Migration die zweite Bedingung („`manual` ist gar nicht
  da"), antwortet MySQL mit `1054 Unknown column` — und **1054 steht nicht in
  der Schluckliste**. Der ganze Lauf bräche ab, an einer Migration, die nichts
  zu tun hat.

## Voraussetzungen

- Eine erreichbare MySQL- oder MariaDB-Instanz und ein Konto, das Datenbanken
  **anlegen und löschen** darf.
- Das Kommandozeilenprogramm aus `--klient` (`mariadb` oder `mysql`).
  `schema.sql` enthält viele Anweisungen; PDO führt sie nicht in einem Rutsch
  aus.

**`--datenbank` ist Pflicht und hat mit Absicht keinen Vorgabewert.** Die Probe
löscht das genannte Schema zu Beginn jedes Falls. Ein Name, den man
versehentlich trifft, wäre hier teuer.

## Was sie mit `config.php` tut

`migration_lib.php` lädt `db.php`, und das lädt `config.php` — die es in einem
frisch ausgecheckten Repositorium nicht gibt. Die Probe legt eine hin und
räumt sie am Ende wieder weg, **auch wenn sie unterwegs abbricht**
(`register_shutdown_function`). Eine vorhandene `config.php` wird zur Seite
gelegt und zurückgeholt; ein Entwicklungsrechner behält seine Einstellungen.

## Ihre Grenzen, und sie sind scharf

- **Sie prüft das Schema und die Migrationen, nicht die Anwendung.** Ob
  `ingest.php` auf dieser Fassung durchläuft, sagt sie nicht. Dafür ist Stufe 2
  da.
- **Sie prüft nur, was im Katalog steht.** Eine Migration, die ihr DDL zur
  Laufzeit aus Variablen baut, sieht sie so wenig wie
  `tools/migrationsregister/`.
- **Sie misst eine Fassung je Lauf.** Der Sinn liegt darin, sie gegen
  *mehrere* zu fahren — in der Kette gegen MySQL 8.4.0 und MariaDB 10.6, das
  ist die dokumentierte Untergrenze (`docs/Technik.md` 7). Ein Lauf gegen eine
  Fassung belegt nichts über die andere; genau diese Verwechslung war die
  Ursache.
- **Die Selbstprobe braucht keine Datenbank** und beantwortet nur die Frage,
  ob das Zählwerk einen Fehlschlag überhaupt bemerkt. Ein grüner Lauf einer
  Prüfung, die immer grün meldet, sieht genauso aus wie einer, der nichts
  gefunden hat (dieselbe Begründung wie bei `tools/migrationsregister/`).

## In der Kette

Stufe 1 (`.github/workflows/pruefung.yml`), eigener Auftrag `schema` mit einer
Matrix über die beiden Fassungen. Er braucht keine Installation, keine
`config.php` und kein Netz außer dem Dienstbehälter.
