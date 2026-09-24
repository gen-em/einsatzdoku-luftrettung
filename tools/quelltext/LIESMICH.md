# Quelltextprüfungen

Neun Prüfungen, die Quelltext lesen und im Tor laufen (E-PK-24).
**Anlass: Nr. 148, 205, 208, 238, 293** — je Prüfung in der Tabelle.

## Aufruf

```bash
bash tools/quelltext/pruefen.sh <name> [zusatz…]   # alle · --selbstprobe
```

## Was es misst

| Name | Prüft | Anlass |
|---|---|---|
| `installweiche` | die Weiche in `install.php` | PP-1 |
| `sitzungshaertung` | Sitzungsflaggen und ihre Setzstellen | Nr. 205 |
| `csp` | Kopfzeilen gegen `kopfzeilen_lib.php` | 15.09.: Meldeweg tot |
| `jobregister` | Registerzeilen gegen `jobs_lib.php` | Nr. 208 |
| `migrationsregister` | Migrationen gegen `schema.sql` | Nr. 238 |
| `linkprobe` | jeder Verweis nennt einen Parameter, den sein Ziel liest | Nr. 148, 151 |
| `vollstaendigkeit` | Klasse ohne Regel, Wert außerhalb `:root`, `style=` | Nr. 179, 227 |
| `textprobe` | **fünf Regelklassen** in sichtbarem Text: Luftbegriffe, Binnen-I, E-Mail-Adressen, Netzadressen, reale Namen | B-S4-06, E-PK-08 |
| `bestand` | jedes Werkzeug unter `tools/` gegen `Pruefablauf.md` 6: Form, Anlass, gerufen, keine lose Datei | Nr. 293 |

## Was es braucht

Nichts — kein Netz, keine Datenbank. `php` und `python3`.

## Erwartete Zahl

`alle` → **9 von 9 Prüfungen grün**, `--selbstprobe` → **6 von 6**.
Vollständigkeit und Bestand messen gegen **0**, ohne Schwelle. Die Textprobe
meldet nur **neue** Treffer gegen `textprobe-altbestand.json` — neu
schreiben mit `--altbestand-schreiben`, nie von Hand.

## Was es nicht kann

Nichts, was einen Browser oder eine Anlage braucht — dafür `tools/proben/`.
`linkprobe` und `vollstaendigkeit` haben keine Selbstprobe.
