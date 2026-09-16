# Migrationsregister — steht in `schema.sql` und `migration_lib.php` dasselbe?

```
php tools/migrationsregister/pruefen.php
php tools/migrationsregister/pruefen.php --ausfuehrlich   # alle Kennungen in Reihenfolge
php tools/migrationsregister/pruefen.php --selbstprobe    # findet die Prüfung überhaupt etwas?
```

Rückgabewert: `0` = sauber · `1` = Befund oder ungenutzte Ausnahme · `2` = die
Prüfung kam nicht zustande (Datei fehlt, kaputtes JSON).

**Keine Installation nötig.** Keine Datenbank, keine `config.php`, kein Netz —
die Prüfung liest zwei Dateien. Sie ist deshalb Stufe 1 des Prüftors
(`.github/workflows/pruefung.yml`, E-P5a-13) und läuft bei jedem Push.

## Wozu

`migration_lib.php` führt im Kopf eine Hausregel:

> NEUE MIGRATION? Ans ENDE von `migrationen_katalog()` anhängen und die Kennung
> zusätzlich am Ende von `schema.sql` eintragen, damit Neuinstallationen sie
> nicht unnötig ausführen.

Die Regel ist richtig und wurde trotzdem schon dreimal vergessen — das sagen
die Kommentare am Ende von `schema.sql` selbst („Nachgetragen (Web 5.9.0):
Beide Migrationen fehlten hier", „Nachgetragen (Web 9.10.1)"). Bemerkt wurde
es jedes Mal später und von Hand. Die Folge ist keine Fehlermeldung, sondern
eine **Neuinstallation**, die Migrationen ansetzt, die auf ihr nichts zu tun
haben — und im schlimmsten Fall an einer bereits vorhandenen Spalte
hängenbleibt. Auf der Betreiberinstallation fällt das nie auf; sie hat den
Bestand.

## Die sieben Prüfungen

| Nr. | Frage | Sollwert |
|---|---|---|
| 1 | Steht jede Kennung des Katalogs in der Vorabliste von `schema.sql`? | 0 Befunde |
| 2 | Steht jede Kennung der Vorabliste im Katalog? | 0 Befunde |
| 3 | Steht eine Kennung zweimal — hier oder dort? | 0 Befunde |
| 4 | Steigt das **Datum** der Kennungen? | 0 Befunde |
| 5 | Steht alles, was eine Migration **anlegt**, auch in `schema.sql`? | 0 Befunde |
| 6 | Ist alles, was eine Migration **löscht**, dort verschwunden? | 0 Befunde |
| 7 | Hat jeder Eintrag `label` und entweder `sql` oder `run`? | 0 Befunde |

Zu **Prüfung 4**: verglichen wird nur das Datum, nicht das Stichwort. Innerhalb
eines Tages ist die Reihenfolge eine Abhängigkeit und keine Sortierung —
`2026_07_20_kopplung` steht mit Absicht hinter `2026_07_20_stammdaten_defaults`.
Sechs solche Paare stehen heute im Katalog. Was nicht rutschen darf, ist der
Tag.

Zu **Prüfung 5 und 6**: Sie rechnen den Katalog durch — leer beginnen, jede
DDL-Anweisung anwenden, das Ergebnis gegen `schema.sql` halten. Das Ergebnis
ist eine **Teilmenge** des Schemas: Was nie eine Migration hatte, steht nur
dort. Geprüft wird deshalb in eine Richtung.

## Wie sie an den Katalog kommt, ohne ihn zu laden

`migration_lib.php` lädt `db.php`, und das lädt `config.php` — die es in einem
frisch ausgecheckten Repositorium nicht gibt. Statt eine hinzulegen, liest die
Prüfung die Datei mit `token_get_all()`: Jede Zeichenkette des Quelltextes
steht dann einzeln da, in der Reihenfolge des Quelltextes. Eine Kennung ist
eine Zeichenkette der Form `JJJJ_MM_TT_stichwort`; die DDL-Anweisungen dahinter
gehören zu ihr, bis die nächste Kennung kommt.

## Ihre Grenze, und sie ist scharf

```php
$pdo->exec("ALTER TABLE `$tab` DROP COLUMN `$spalte`");
```

Diese Zeile ist für einen Leser des Quelltextes **keine Zeichenkette** mehr,
sondern ein Stück Text mit Löchern (PHP: `T_ENCAPSED_AND_WHITESPACE`), und was
in den Löchern steht, weiß erst die Laufzeit. **Vier Spalten fallen heute genau
so** — im letzten Schritt von `2026_08_17_notarzt_erweiterung`. Sie stehen
deshalb in `ausnahmen.json`, mit dieser Begründung; die Prüfung meldet sie
nicht, und sie tut auch nicht so, als hätte sie sie gesehen.

Eine Migration, die ihre DDL vollständig aus eingesetzten Namen baut, ist für
dieses Werkzeug unsichtbar. Das ist der Preis dafür, dass es ohne Installation
läuft — und der Grund, warum die Zahlen des Berichts das Gemessene benennen
(„180 Spalten aus dem Katalog") und nicht bloß „in Ordnung".

## Ausnahmen

`ausnahmen.json`, je Eintrag `was` und `grund`. **Eine ungenutzte Ausnahme ist
selbst ein Befund** — sonst bleibt die Liste stehen, nachdem der Grund
weggefallen ist. Dieselbe Regel wie bei `tools/wortliste/`.

## Selbstprobe

`--selbstprobe` legt eine beschädigte Kopie beider Dateien an — eine Kennung
aus der Vorabliste gestrichen, eine Spalte aus `schema.sql` gestrichen, eine
Kennung im Katalog zurückdatiert — und verlangt, dass die Prüfungen 1, 2, 4 und
5 anschlagen. Die echten Dateien werden dabei nicht angefasst.

Sie steht vor der Prüfung und nicht daneben: Ein grüner Lauf einer Prüfung, die
immer grün meldet, sieht genauso aus wie einer, der nichts gefunden hat. Die
Integritätswache führt dieselbe Überlegung im Kopf ihrer Arbeitsdatei.

## Stand

Gemessen am 15.09.2026 auf `claude/butte-umsetzen-5opi9u`:

```
Kennungen im Katalog:            46
Kennungen in der Vorabliste:     46
Tabellen aus dem Katalog:        30
Spalten aus dem Katalog:         180
Loeschungen im Katalog:          29
Ausnahmen (erklaerte Befunde):   4
Befunde: 0
Ungenutzte Ausnahmen: 0
Selbstprobe: 4 von 4 erfuellt
```
