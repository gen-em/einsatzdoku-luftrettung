# Schemaprobe

Läuft `schema.sql` auf der Datenbank, gegen die es laufen soll?
**Anlass: Nr. 238** — eine Migration, die nur auf MariaDB durchging; Nr. 158 — eine TIMESTAMP-Spalte, die je Fassung anders entsteht (Fall 6); Nr. 43 — die Aufräummigration nach S11, die ganze Tabellen wirft und nur mit Freigabe über einen Rest geht (Fall 7).

## Aufruf

```bash
php tools/schemaprobe/probe.php --datenbank nadoku_probe   # --selbstprobe
bash tools/sandbox/plattform.sh schema                     # alle vier Fassungen
```

Der Prüfstand ruft die zweite Zeile; sie kennt die Zugangsdaten je Fassung.
**Nie neben anderen Proben fahren:** Während sie läuft, sieht die Anlage
eine Datenbank mit offenen Migrationen, und der Torwächter schließt sie.

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

**50 Prüfungen, 0 Fehlschläge** je Fassung, die Matrix **4 × 50/0**
(19.10.2026, mit Fall 7 seit S11-12; davor 40 seit R4-15, 30 seit P5c/AP8, 19).

## Was es nicht kann

Keine Daten prüfen — nur die Form. Ob eine Migration den **Bestand**
richtig umschreibt, misst der Kreislauf im Referenzdatensatz.
