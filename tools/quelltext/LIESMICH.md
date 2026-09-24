# Quelltextprüfungen

Zwölf Prüfungen, die Quelltext lesen und im Tor laufen (E-PK-24).
**Anlass: Nr. 148, 205, 208, 238, 248, 293, 315** — je Prüfung in der Tabelle.

## Aufruf

```bash
bash tools/quelltext/pruefen.sh <name> [zusatz…]   # alle · --selbstprobe · --liste
```

## Was es misst

| Name | Prüft | Anlass |
|---|---|---|
| `installweiche` | die Weiche in `install.php` | PP-1 |
| `sitzungshaertung` | Sitzungsflaggen und ihre Setzstellen | Nr. 205 |
| `csp` | Kopfzeilen gegen `kopfzeilen_lib.php` | 15.09.: Meldeweg tot |
| `jobregister` | Registerzeilen gegen `jobs_lib.php` | Nr. 208 |
| `migrationsregister` | Migrationen gegen `schema.sql` | Nr. 238 |
| `behandler` | die drei Behandler des Fehlerprotokolls in `db.php` | Nr. 248 |
| `linkprobe` | jeder Verweis nennt einen Parameter, den sein Ziel liest | Nr. 148, 151 |
| `vollstaendigkeit` | Klasse ohne Regel, Wert außerhalb `:root`, `style=` | Nr. 179, 227 |
| `textprobe` | **fünf Regelklassen** in sichtbarem Text: Luftbegriffe, Binnen-I, E-Mail-Adressen, Netzadressen, reale Namen | B-S4-06, E-PK-08 |
| `bestand` | jedes Werkzeug unter `tools/` gegen `Pruefablauf.md` 6, elf Regeln: Form, Anlass, gerufen, keine lose Datei, keine Backlog-Nummer zweimal; eingehängt in `SELBST`, Tabelle, `pruefablauf.json` und die Tabelle in 4 | Nr. 293, 315 |
| `pysyntax` | jedes Python-Werkzeug unter `tools/` übersetzt | Kette II/AP4: `zustand.py` |
| `handbuch` | Handbuch und „Was ist NAdoku" rendern, UTF-8 streng, kein fremdes Bild | P5b/AP8 |

## Was es braucht

`php`, `python3`, `bash`; für `handbuch` und `bestand` `cmark-gfm` (Ausbaustufe `web`) — fehlt es, ist `handbuch` rc 2 und `bestand` rot.

## Erwartete Zahl

`alle` → **12 von 12 grün**, `--selbstprobe` → **10 von 10**; ohne Schwelle.
Die Textprobe meldet nur **neue** Treffer gegen `textprobe-altbestand.json` (neu schreiben mit `--altbestand-schreiben`, nie von Hand).

## Was es nicht kann

Browser und Anlage (dafür `tools/proben/`); `linkprobe` und `vollstaendigkeit` ohne Selbstprobe.
