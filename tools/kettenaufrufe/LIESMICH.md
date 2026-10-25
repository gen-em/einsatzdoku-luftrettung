# Kettenaufrufe

Passt jeder Werkzeugaufruf zu seinem Werkzeug — und seit R17-19: hängt jede
fremde Aktion an einer SHA, hält die Ausnahmeliste der Kette ihre Sollliste?
**Anlass: Nr. 217** — ein Aufruf mit einem Schalter, den es nicht gab.

## Aufruf

```bash
python3 tools/kettenaufrufe/pruefen.py    # --liste · --probe
```

## Was es misst

Jede `run:`-Zeile in `.github/workflows/` und jeden `aufruf` in
`tools/pruefstand/pruefablauf.json`, gegen die Schnittstelle **aus dem
Quelltext des Werkzeugs** — `argparse`, `case`-Zweige, `BEKANNT`-Mengen,
Namenslisten der Sammelläufer. Dazu seit R17-19 zwei Riegel (F-R17-16):
(1) jede fremde `uses:`-Zeile an einer 40-stelligen SHA, Fassung als
Kommentar; (2) die Ausnahmeliste in `ausliefern-lauf.yml` gleich ihrer
Sollliste (acht Pfade, Verzeichnisse als `x/**` und `x/`), jeder Serverpfad
außer `install.php` in `.gitignore`, keine `server/config.php*` eingecheckt.

## Was es braucht

Nur `git` (für `.gitignore` und die eingecheckten Pfade). Kein Netz, keine
Datenbank, keine Installation.

## Erwartete Zahl

**0 Befunde** („ungeprüft" ist kein Beiwerk, F-PK-20). 25.10.2026: **135
Aufrufe, 0 Befunde, 3 ungeprüft; 19 Aktionszeilen gepinnt; 8 Serverpfade,
0 eingecheckte Kopien**; `--probe` → **37 von 37**.

## Was es nicht kann

Positionsargumente und Pflichtargumente eines Shell-Unterbefehls sieht es
nicht (F-PK-21); es misst die Form, nicht das Tun. Die Riegel prüfen, **dass**
eine SHA dasteht, nicht, ob sie zur Fassung gehört; eine neue Ausnahme heißt
Sollliste in `pruefen.py`, `CLAUDE.md` 3 und Ausnahmeliste zugleich ändern.
