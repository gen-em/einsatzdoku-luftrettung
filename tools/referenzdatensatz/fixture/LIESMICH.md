# Demo-Fixture

Erzeugt die Demo-Fixture (`server/demo/fixture.json.gz`, Format 4) aus dem Referenzkonto und hält sie gegen die Referenz.
Anlass: Nr. 388, 563 — nicht mehr erzeugbar; Fixture und Referenz liefen still auseinander; Fassung 1 ohne Herkunft.

## Aufruf

```bash
php erzeugen.php [demo@gen-em.org] [ziel.json.gz]   # Vorgabe: server/demo/fixture.json.gz
python3 verschliessen.py EIN.json.gz [AUS.json.gz] [--runden N] [--selbstprobe]   # der zweite Schritt; `erzeugen.php` ruft ihn
php riegelprobe.php [umlauf-csv@example.invalid]    # die Riegel des Erzeugers und des Einspielers
python3 abgleich.py [--tief | --schreiben] [--fixture PFAD] [--selbstprobe]
```

## Was es misst

`erzeugen.php` liest Konto, Geräte, Bestand (`edbak_build()`), Stücke und
Standbuch, wie sie liegen, und öffnet nichts. `verschliessen.py` öffnet den
Rohbestand (beide Fassungen) über die Referenz in `tools/proben/huelle/` und
verschließt neu: Gerätehülle Fassung 2 von zwei Demo-Geräten mit festem
Schlüssel, `edk2:`, 16 geschlossene Blätter, Gerätebuch, Kontopaar — wer was
verschließt, steht in seinem Kopf. Danach liest es alles gegen; bei einem
Befund entsteht keine Datei. `riegelprobe.php`: Hülle ohne Anteil, Reset mit
verbogener Fixture. `abgleich.py` (Stufe 1): Umriss der Fixture gegen
`../referenz/umriss.json`; `--tief` rechnet ihn aus der `.edbak` nach.

## Was es braucht

Die Anlage mit dem Referenzkonto als Demo-Konto (`../einspielen/LIESMICH.md`), Python mit `cryptography` und `bcrypt`;
für die Riegelprobe ein Konto mit Anteil und Rolle `user` (`../vergleich/pruefkonto.py`). `abgleich.py` nur Python.

## Erwartete Zahl

`erzeugen.php` Rückgabe 0, „Gegenlesung ohne Befund“ (15.11.2026: 67 016 Punkte in 223 Spuren und 269 Stücken, 197 vom Gerät).
`verschliessen.py --selbstprobe` **9 / 0**, `riegelprobe.php` **11 von 11**, `abgleich.py` „Gleich.", Selbstprobe **10 / 0**.

## Was es nicht kann

Den Bestand herstellen (`einspielen/`, `browser/`); laufende Jobs landen still in der Datei.
Der Abgleich zählt den Umriss, er öffnet nichts. Die Fixture gilt nur mit umgelegter Weiche.
