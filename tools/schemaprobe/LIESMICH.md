# Schemaprobe

Läuft `schema.sql` auf der Datenbank, gegen die es laufen soll?
**Anlass: Nr. 238** — eine Migration, die nur auf MariaDB durchging; Nr. 158 — eine TIMESTAMP-Spalte, die je Fassung anders entsteht (Fall 6); Nr. 43 — die Aufräummigration nach S11, die ganze Tabellen wirft und nur mit Freigabe über einen Rest geht (Fall 7); Nr. 458 — eine Abfrage, die nur MySQL ablehnt (Fall 8); Nr. 510 — die Probe setzte die Anlage in Wartung (Wegwerf-Wurzel).

## Aufruf

```bash
php tools/schemaprobe/probe.php --datenbank nadoku_probe   # --selbstprobe
bash tools/sandbox/plattform.sh schema                     # alle vier Fassungen
```

Der Prüfstand ruft die zweite Zeile; sie kennt die Zugangsdaten je Fassung.
Sie fasst die Anlage nicht an (seit Nr. 510; bis dahin schloss der Torwächter sie währenddessen).

## Was es misst

Es legt das Schema in einer **Wegwerf-Datenbank** an, fährt die Migrationen
darüber und vergleicht das Ergebnis mit dem, was `schema.sql` verspricht:
Tabellen, Spalten, Typen, Schlüssel, Vorgabewerte. Dazu die Plattformweiche
— MariaDB und MySQL vertragen nicht dasselbe. Fall 8 fragt über `db()`, mit echt vorbereiteten Anweisungen wie die Anwendung — nur so lehnt MySQL ab.

## Was es braucht

Eine erreichbare Datenbank und eine Kennung, die `CREATE DATABASE` darf.
Die vier Fassungen der Matrix stellt `bash tools/sandbox/plattform.sh` her.
Sie arbeitet auf einer **Kopie von `server/`** mit eigener `config.php`, die am
Ende geht; die der Anlage bleibt unberührt (06.11.2026: 0 statt 76 Abweichungen).

## Erwartete Zahl

**52 Prüfungen, 0 Fehlschläge** je Fassung, die Matrix **4 × 52/0**
(04.11.2026, mit Fall 8 seit BR6-02; davor 50 seit S11-12, 40 seit R4-15, 30 seit P5c/AP8, 19).

## Was es nicht kann

Keine Daten prüfen — nur die Form. Ob eine Migration den **Bestand**
richtig umschreibt, misst der Kreislauf im Referenzdatensatz.
