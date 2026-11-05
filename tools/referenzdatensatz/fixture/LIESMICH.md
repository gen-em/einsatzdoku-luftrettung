# Demo-Fixture

Macht aus dem Referenzkonto die Datei, mit der der Demo-Reset das
Demo-Konto zurücksetzt (`server/demo/fixture.json.gz`, Format 3).
Anlass: Nr. 388 — seit Web 23 ließ sie sich nicht mehr erzeugen.

## Aufruf

```bash
php erzeugen.php [demo@gen-em.org] [ziel.json.gz]   # Vorgabe: server/demo/fixture.json.gz
php riegelprobe.php [umlauf-csv@example.invalid]    # die Riegel des Erzeugers und des Einspielers
```

## Was es misst

`erzeugen.php` liest Konto, Geräte und Bestand (`edbak_build()`, Kopf und
Fenster) und öffnet Spurstücke und Einsatzhüllen mit dem Schlüssel des
Demo-Kontos; es bricht ab, wenn eine Hülle nicht aufgeht, eine Punktzahl
nicht zum Umriss passt oder der Kern Klartext-Ortsdaten trägt.
`riegelprobe.php` misst die zwei Riegel (Rundenzahl, Hülle `edk1:`) beidseitig.

## Was es braucht

Die örtliche Anlage mit dem Referenzkonto als Demo-Konto
(`../einspielen/LIESMICH.md`, samt `../browser/csv_import.mjs`) und für die
Riegelprobe ein Konto mit `edka1:`-Hülle und Rolle `user`
(`../vergleich/pruefkonto.py anlegen umlauf-csv@example.invalid`).

## Erwartete Zahl

`erzeugen.php` Rückgabe 0, die Datenbank unverändert; Punktzahl gleich der
Summe der Umrisse (05.11.2026: 67 478 in 221 Spuren, alle Stufe 1).
`riegelprobe.php` **11 von 11**, Rückgabe 0.

## Was es nicht kann

Den Bestand herstellen — das tun `einspielen/` und `browser/`. Laufen
Hintergrundjobs zwischen Herstellen und Erzeugen, landet ihre Arbeit still
in der Datei (Kopf von `erzeugen.php`). Teil 3 der Riegelprobe legt die
echte Fixture kurz beiseite und prüft danach die SHA-256.
