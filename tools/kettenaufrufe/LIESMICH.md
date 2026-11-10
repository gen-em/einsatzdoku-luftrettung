# Kettenaufrufe

Passt jeder Werkzeugaufruf zu seinem Werkzeug — und hält die Kette ihre Riegel?
**Anlass: Nr. 217** — ein Aufruf mit einem Schalter, den es nicht gab.

## Aufruf

```bash
python3 tools/kettenaufrufe/pruefen.py    # --liste · --probe
```

## Was es misst

Jede `run:`-Zeile in `.github/workflows/` und jeden `aufruf` in
`tools/pruefstand/pruefablauf.json` gegen die Schnittstelle **aus dem
Quelltext des Werkzeugs**. Dazu sechs Riegel: (1) jede fremde `uses:`-Zeile an
einer 40-stelligen SHA mit Fassung als Kommentar; (2) die Ausnahmeliste der
Auslieferung gleich ihrer Sollliste (sieben Pfade), `.gitignore` dazu, keine
`config.php*` eingecheckt (beide seit R17-19, F-R17-16); seit KA-01 (3) kein
`${{ inputs.* }}`, `github.event.*`, `github.head_ref` im Text eines `run:`
(F-KA-14); (4) jede `image:`-Zeile am Digest (F-KA-39); (5) kein
Geheimnisschalter mit Variable auf der Befehlszeile (F-KA-37); (6) der
FTPS-Schritt mit `security: strict` (F-KA-02).

## Was es braucht

Nur `git`. Kein Netz, keine Datenbank, keine Installation.

## Erwartete Zahl

**0 Befunde** („ungeprüft" ist kein Beiwerk, F-PK-20). 10.11.2026: **147
Aufrufe, 0 Befunde, 5 ungeprüft; 19 Aktionszeilen gepinnt; 7 Serverpfade;
59 run-Blöcke ohne Eingang; 2 Abbilder am Digest; 0 Geheimnisschalter;
1 FTPS-Schritt strikt**; `--probe` → **67 von 67**.

## Was es nicht kann

Positions- und Pflichtargumente eines Shell-Unterbefehls sieht es nicht
(F-PK-21). SHA und Digest hält es nicht gegen die Fassung; Riegel 5 kennt nur
seine Schalterliste; ob `strict` gegen den Hoster trägt, zeigt erst ein Lauf.
