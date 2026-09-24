# Schemaprobe

Läuft `schema.sql` auf der Datenbank, gegen die es laufen soll?
**Anlass: Nr. 238** — eine Migration, die nur auf MariaDB durchging.

## Aufruf

```bash
php tools/schemaprobe/probe.php --datenbank nadoku_probe   # --selbstprobe
bash tools/sandbox/plattform.sh schema                     # alle vier Fassungen
```

**Der Prüfstand ruft die zweite Zeile** (seit P5c/AP2, F-P5c-88). Bis dahin
stand dort die erste — ohne Zugangsdaten, also als `root` ohne Passwort, und
das lehnt die örtliche MariaDB ab. Weil bis AP2 kein Paket
`migration_lib.php` berührt hatte, lief der Eintrag nie; in keinem
Prüfbericht war die Schemaprobe gemessen. `plattform.sh` kennt die
Zugangsdaten je Fassung.

**Nicht neben anderen Proben fahren.** Weil sie `config.php` für ihre
Laufzeit auf die Probe-Datenbank stellt, sieht jede Anfrage an die Anlage in
diesem Fenster eine Datenbank mit ausstehenden Migrationen — und der
Torwächter schließt die Anlage (`wartung.lock`). Gemessen im Vorlauf zu
P5c/AP2: danach 503 auf jeder Seite (F-P5c-86).

## Was es misst

Es legt das Schema in einer **Wegwerf-Datenbank** an, fährt die Migrationen
darüber und vergleicht das Ergebnis mit dem, was `schema.sql` verspricht:
Tabellen, Spalten, Typen, Schlüssel, Vorgabewerte. Dazu die Plattformweiche
— MariaDB und MySQL vertragen nicht dasselbe.

## Was es braucht

Eine erreichbare Datenbank und eine Kennung, die `CREATE DATABASE` darf.
Die vier Fassungen der Matrix stellt `bash tools/sandbox/plattform.sh` her.
**Es fasst `config.php` an** — es schreibt die Probe-Datenbank hinein und
legt den Urstand bytegleich zurück, auch bei Abbruch.

## Erwartete Zahl

**19 Prüfungen, 0 Fehlschläge** je Fassung. Die Matrix aus vier Fassungen
meldet folglich **4 × 19/0** (gemessen 21.09.2026, 29,7 s).

## Was es nicht kann

Keine Daten prüfen — nur die Form. Ob eine Migration den **Bestand**
richtig umschreibt, misst der Kreislauf im Referenzdatensatz.
