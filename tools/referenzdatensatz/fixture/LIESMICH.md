# Demo-Fixture

Erzeugt die Demo-Fixture (`server/demo/fixture.json.gz`, Format 3) aus dem
Referenzkonto und hält sie gegen die Referenz.
Anlass: Nr. 388 — nicht mehr erzeugbar, und Fixture und Referenz liefen still auseinander.

## Aufruf

```bash
php erzeugen.php [demo@gen-em.org] [ziel.json.gz]   # Vorgabe: server/demo/fixture.json.gz
php riegelprobe.php [umlauf-csv@example.invalid]    # die Riegel des Erzeugers und des Einspielers
python3 abgleich.py [--tief | --schreiben] [--fixture PFAD] [--selbstprobe]
```

## Was es misst

`erzeugen.php` liest Konto, Geräte und Bestand (`edbak_build()`) und öffnet
Stücke und Einsatzhüllen mit dem Schlüssel des Demo-Kontos; Abbruch, wenn
eine Hülle nicht aufgeht, eine Punktzahl nicht zum Umriss passt oder der Kern
Klartext trägt. `riegelprobe.php`: die zwei Riegel (Rundenzahl, `edk1:`).
`abgleich.py` (Stufe 1): Fixture gegen `../referenz/umriss.json` und dessen
SHA-256 der `.edbak`; `--tief` rechnet den Umriss aus der `.edbak` nach.

## Was es braucht

Die Anlage mit dem Referenzkonto als Demo-Konto (`../einspielen/LIESMICH.md`)
und für die Riegelprobe ein `edka1:`-Konto mit Rolle `user`
(`../vergleich/pruefkonto.py`). `abgleich.py` nur Python; `--tief` und
`--schreiben` dazu `cryptography`.

## Erwartete Zahl

`erzeugen.php` Rückgabe 0, die Datenbank unverändert (05.11.2026: 67 016
Punkte in 223 Spuren, 1 244 auf Stufe 3). `riegelprobe.php` **11 von 11**.
`abgleich.py` „Gleich.", Selbstprobe **8 von 8**.

## Was es nicht kann

Den Bestand herstellen (`einspielen/`, `browser/`); laufende Jobs landen still in der
Datei. Der Abgleich vergleicht Zahlen, keine Punkte; `n_original` nur bei Stufe 3.
