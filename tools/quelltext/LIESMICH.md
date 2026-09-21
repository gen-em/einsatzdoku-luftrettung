# Quelltextprüfungen

Acht Prüfungen, die nur Quelltext lesen und im Tor laufen (E-PK-24).

## Aufruf

```bash
bash tools/quelltext/pruefen.sh <name> [zusatz…]
bash tools/quelltext/pruefen.sh alle
bash tools/quelltext/pruefen.sh --selbstprobe
```

## Was es misst

| Name | Prüft | Anlass |
|---|---|---|
| `installweiche` | die Weiche in `install.php` | PP-1 |
| `sitzungshaertung` | Sitzungsflaggen und ihre Setzstellen | Nr. 205 |
| `csp` | die Kopfzeilen gegen `kopfzeilen_lib.php` | 15.09.: Meldeweg tot |
| `jobregister` | Registerzeilen gegen `jobs_lib.php` | Nr. 208 |
| `migrationsregister` | Migrationen gegen `schema.sql` | Nr. 238 |
| `linkprobe` | jeder Verweis nennt einen Parameter, den sein Ziel liest | Nr. 148, 151 |
| `vollstaendigkeit` | Klasse ohne Regel, Wert außerhalb `:root`, `style=`, fremde Quelle | Nr. 179, 227 |
| `textprobe` | Luftbegriffe in sichtbarem Text | B-S4-06 |

## Was es braucht

Nichts — kein Netz, keine Datenbank, keine Installation. `php` und `python3`.

## Erwartete Zahl

`alle` → **8 von 8 Prüfungen grün**, `--selbstprobe` → **5 von 5**.
Die Schwelle der Vollständigkeit steht in `tools/pruefstand/pruefablauf.json`
und wird von dort **gelesen**, nicht hier geführt (E-PK-16 nimmt sie weg).

## Was es nicht kann

Nichts, was einen Browser oder eine laufende Anlage braucht — dafür ist
`tools/proben/`. `linkprobe`, `vollstaendigkeit` und `textprobe` haben keine
eigene Selbstprobe; vor dem Umzug hatten sie auch keine.
