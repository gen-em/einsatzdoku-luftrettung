# Komplettprobe — der volle Zyklus der Komplettsicherung

Entstanden in S2/AP8. Sie prüft `server/komplett_lib.php` und die Stellen, an
denen die Komplettsicherung die übrige Anwendung berührt — den Versand
(`sicherungsziel_lib.php`) und die Speicherbuchführung
(`adminbackup_lib.php`).

## Zwei Werkzeuge

| | |
|---|---|
| `probe.php` | die Bibliothek darunter — 76 Erwartungen, ohne Browser |
| `klickweg.mjs` | die Adminseite im Browser — 17 Prüfungen, mit Playwright |

## Was hier NICHT geprüft werden kann

**`wiederherstellen.php` nicht.** `klickweg.mjs` deckt die Adminseite ab; der
Rückweg ist damit in der *Sache* belegt (Teil 7 spielt denselben Dump Zeile
für Zeile ein, wie es jene Seite tut), aber nicht über seine Seite — die
Schranken „Datenbank leer" und „Nachweis" und die Wiederaufnahme über mehrere
Durchgänge sind Handarbeit. Der Grund ist schlicht: Diese Seite verlangt eine
**leere** Datenbank, und eine leere Datenbank hat kein Werkzeug hier zur Hand,
ohne die laufende Installation anzufassen.

**Keine volle Platte.** Geprüft ist die Speichergrenze als *Rechnung*
(zählt die Komplettsicherung mit?), nicht als erlebter Zustand.

**Kein wirklicher Absturz mitten in der Anfrage.** Teil 8 stellt ihn nach:
Der Zustand wird auf den Stand vor dem Häppchen zurückgedreht und die Datei
behält, was das Häppchen schon geschrieben hatte — genau die Lage, die ein
Zeitlimit hinterlässt. Erlebt ist er nicht.

**Kein Migrationslauf.** Er gehört einer angemeldeten Administration
(`update.php`, zweistufig seit M6-01) und läuft in keiner Probe mit.

**Keine fremde Datenbank.** Gemessen wird gegen MariaDB 10.11. Ob ein
anderer Server dieselben `SHOW CREATE TABLE` zurückgibt, ist damit nicht
gesagt — die Probe misst den Rundlauf auf *einem* Server.

## Sie arbeitet in einer Kopie

`edbak_wurzel()` zeigt fest auf `server/sicherungen`. Eine Probe, die dort
Stände ablegt, verdrängt womöglich einen echten. Deshalb legt sie unter
`/tmp` eine Kopie des Serververzeichnisses an, mit eigener `config.php` und
**eigenem Serverschlüssel**, und räumt sie am Ende weg.

**Gelesen wird aus der echten Datenbank.** Der Dump liest nur, und ein Dump
gegen einen Spielbestand prüfte nichts — die Zahlen, auf die es ankommt
(Speicherspitze, Dauer, Zeilenzahl), entstehen erst an einem Bestand dieser
Grösse.

Geschrieben wird ausserhalb der Kopie an genau zwei Stellen, beide
vorübergehend und beide wieder zurückgenommen:

* `app_state.komplett_aufbewahrung` (Teil 9, für die Verdrängungsprobe),
* ein Wegwerf-Eintrag in `backup_targets` (Teil 10, danach gelöscht).

Die Zeile `jobs.komplett` fasst sie **nicht** an: Der Zustand des Laufs
bleibt in einer Variablen der Probe. Deshalb stimmen in Teil 7 auch alle 34
Prüfsummen — im echten Betrieb weicht `jobs` ab, weil die Sicherung ihren
eigenen Fortschritt mitschreibt.

## Voraussetzung

Eine `server/config.php` mit erreichbarer Datenbank. Für Teil 7 zusätzlich
ein Zugang, der `CREATE DATABASE` darf — der Zugang der Anwendung darf das in
aller Regel nicht, deshalb `--nutzer`. Für Teil 10 die Gegenstellen der
Versandprobe.

## Aufruf

```sh
# Die Adminseite im Browser (die lokale Installation muss laufen):
node tools/komplettprobe/klickweg.mjs

# Nur die Bibliothek (Teil 1–6, 8, 9):
php tools/komplettprobe/probe.php

# Mit Rückspielung:
php tools/komplettprobe/probe.php --pruefdb=edoku_probe --nutzer=root --passwort=

# Mit Versand (Gegenstellen in einer zweiten Schale):
python3 tools/versandprobe/gegenstellen.py /tmp/versandprobe
php tools/komplettprobe/probe.php --pruefdb=edoku_probe --nutzer=root \
    --passwort= --ziel=/tmp/versandprobe
```

**Die Prüfdatenbank wird ohne Rückfrage gelöscht und neu angelegt.** Nur
einen Namen angeben, an dem nichts hängt.

**`klickweg.mjs` sichert wirklich** — er drückt „Jetzt sichern" und wartet den
Lauf ab. Auf dem Messbestand sind das rund zehn Sekunden und ein zusätzlicher
Stand in der Ablage der laufenden Installation. Nichts für nebenbei.

Erwartet: **17 Prüfungen, 0 Befunde** (`klickweg.mjs`) und mit allen
Schaltern **76 Erwartungen, 0 nicht erfüllt** (`probe.php`). Ohne
`--pruefdb` und ohne `--ziel` fallen die betroffenen Teile mit `[ -- ]` aus,
statt zu schweigen; die Zahl ist dann kleiner.

Der Rückgabewert ist 0, wenn alles hält, sonst 1.

## Was geprüft wird

| Teil | Worum es geht |
|---|---|
| 1 | Tabellen, einspielbare Reihenfolge, führende ENUM-Spalten im Cursor |
| 2 | SQL-Literale: kein echter Zeilenumbruch, Hex für Binärspalten, Zahlen |
| 3 | Der Dump entsteht in **mehreren** Häppchen, unter 64 MB Speicherspitze |
| 4 | Das Siegel EDKOMP1: öffnen, fremder Schlüssel, abgeschnitten (auch **an einer Blockgrenze**), veränderter Kopf |
| 5 | Die Passphrase-Fassung: umsiegeln, öffnen, falsche Passphrase, Serverschlüssel |
| 6 | Die Form: ein Statement je Zeile, Stapel ≤ 1 MB, Endmarke, Kopfzeilen |
| 7 | **Rückspielung in eine leere Datenbank** und Vergleich Tabelle für Tabelle |
| 8 | Wiederanlauf: abgeschnittener Rest, verschwundener Baustand |
| 9 | Aufbewahrung, Verdrängung, Speicherbuchführung, Zeitplan |
| 10 | Versand aufs Sicherungsziel — auch der Fall „halbe Datei liegt dort" |

Und im Browser (`klickweg.mjs`): Bestätigungsdialog, Lauf mit Rückmeldung,
beide Downloads (Inhalt geprüft: gzip-Magie bzw. `EDKOMP1` und `"pbkdf2"` im
Kopf), Abweisung einer zu kurzen Passphrase, Zeitplan setzen und
zurückstellen, Konsolenfehler.

## Was sie gefunden hat

**Der Neuanlauf lief in ein `count(null)`.** Die Erstbelegung des Zustands
(`folge`, `i`, `nach`) stand *vor* dem Zweig, der bei verschwundenem
Baustand ebendiese Marken löscht. Der Zweig ist der, der nach einer
Wiederherstellung greift — die Sicherung schreibt ihren eigenen Fortschritt
mit, die eingespielte Datenbank trägt also den Stand „Dump läuft" samt einem
Bauordner, den es auf dem neuen Server nie gab. Ohne die Probe wäre das
genau einmal aufgefallen: beim ersten Wartungslauf nach dem ersten
Wiederanlauf.

**Zwei Fehler in der Probe selbst**, beide der Erwähnung wert, weil sie
wiederkehren: `$a['k'] ?? 'x'` behandelt ein vorhandenes `null` wie einen
fehlenden Schlüssel — die Erwartung „der Kopf nennt `kdf: null`" war damit
nie erfüllbar. Und der Zustand nach einem fertigen Lauf trägt kein `bau`
mehr; wer den Bauordner prüfen will, merkt sich seinen Namen vorher.

### Zwei Stolperstellen, die Zeit gekostet haben

**Über TLS anmelden, nicht über Port 8080.** Die Sitzung setzt `secure`; über
`http` bleibt man auf der Anmeldeseite stehen — ohne Fehlermeldung.

**`waitForNavigation`, nicht `waitForLoadState`.** Die Anmeldung leitet den
Schlüssel im Browser ab (PBKDF2, 320 000 Runden) und geht erst danach weiter.
`waitForLoadState` fällt sofort durch, weil es auf die *aktuelle* Seite
wartet. Dasselbe Muster steht in `tools/screenshots/aufnehmen.mjs`.

## Grenzen der Zahlen

Die Zeitangaben stammen vom Messbestand (5000 Einsätze, 1,12 Mio. Zeilen) auf
der Entwicklungsmaschine, **ohne Drosselung** — sie sagen etwas über die
Grössenordnung und nichts über einen geteilten Webspace. Die Speicherspitze
dagegen ist eine Aussage: Sie hängt an der Häppchengrösse und nicht an der
Maschine.
