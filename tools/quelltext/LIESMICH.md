# Quelltextprüfungen

**Anlass: Nr. 148, 170, 188, 205, 208, 238, 248, 283, 293, 315, 318** — vierzehn Prüfungen, die Quelltext lesen und im Tor laufen (E-PK-24), je Prüfung mit Anlass in der Tabelle.

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
| `vollstaendigkeit` | Klasse ohne Regel, Wert außerhalb `:root`, `style=`; Ton ohne Regel (auch über eine Bedingung); Selektor, dessen Klasse niemand setzt | Nr. 36, 179, 227, 331 |
| `textprobe` | **fünf Regelklassen** in sichtbarem Text: Luftbegriffe, Binnen-I, E-Mail-Adressen, Netzadressen, reale Namen — diese auch in Kommentaren und im Stylesheet | B-S4-06, E-PK-08, Nr. 283 |
| `bestand` | jedes Werkzeug unter `tools/` gegen `Pruefablauf.md` 6, dreizehn Regeln: Form, Anlass, gerufen, keine lose Datei, keine Backlog-Nummer zweimal; eingehängt in `SELBST`, Tabelle, `pruefablauf.json` und die Tabelle in 4; keine Probe ohne Anlage lädt `db.php`, jeder Riegel steht im Tor | Nr. 293, 315, 329 |
| `pysyntax` | jedes Python-Werkzeug unter `tools/` übersetzt, auch mit Warnungen als Fehler | Kette II/AP4: `zustand.py`; Nr. 318 |
| `handbuch` | Handbuch und „Was ist NAdoku" rendern, UTF-8 streng, kein fremdes Bild | P5b/AP8 |
| `anker` | jeder Verweis `hilfe.php#…` trifft eine Überschrift des gerenderten Handbuchs | Nr. 188 |
| `kennzeichnung` | Schloss an jedem verschlüsselten Feld in Formular und Leseansicht, gegen `kennzeichnung-soll.md`; Kleinzeile an jedem Klartext-Freitextfeld des Katalogs | Nr. 170 |

## Was es braucht

`php`, `python3`, `bash`; für `handbuch` und `bestand` `cmark-gfm` (Ausbaustufe `web`) — fehlt es, ist `handbuch` rc 2 und `bestand` rot.

## Erwartete Zahl

`alle` → **14 von 14 grün**, `--selbstprobe` → **12 von 12**; ohne Schwelle. Die Textprobe meldet nur **neue** Treffer gegen `textprobe-altbestand.json` (neu schreiben mit `--altbestand-schreiben`, nie von Hand).

## Was es nicht kann

Browser und Anlage (dafür `tools/proben/`); `linkprobe` und `vollstaendigkeit` ohne Selbstprobe.
