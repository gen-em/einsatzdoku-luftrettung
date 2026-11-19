# Hüllenprobe

Gerätehülle (Fassung 2) und Chiffretexte unter dem Inhaltsschlüssel (`edk2:`) gegen Prüfvektoren.
**Anlass: Nr. 43 und 563** — Garmin, Android und Browser müssen dieselben Bytes schreiben.

## Aufruf

```bash
bash tools/proben/proben.sh huelle [--motor chromium] [--drossel 6]
python3 tools/proben/huelle/referenz.py erzeugen          # nach einer Formatänderung
python3 tools/proben/huelle/referenz_inhalt.py erzeugen   # ebenso, für edk2
```

## Was es misst

`referenz.py` ist die Referenz der Gerätehülle (Format im Kopf): Fassung 2 (Herkunft, Zeit,
Zähler samt Rückweg, Stückfolge, Vergleichswert der Kopplung) → `vektoren-2.json`,
`Vektoren2.mc`, `HuelleVektor2.mc`. `referenz_inhalt.py`: `edk2:`, Standbuch, Gerätebuch,
Ableitung → `vektoren-inhalt.json`. Jede Gegenlage scheitert mit benanntem Grund, auch die
erste Fassung (`ohne-herkunft`, `kennung`, `altformat`) — seit KA-07 nur noch als Gegenlage
gebaut. `probe.mjs` misst die Seite mit `pruefungen2.js`; `--bestand <Ordner>` einen älteren
Stand. `probe_server.php`: die Gerätehilfe in PHP gegen die Referenz. `geraet.php`,
`*_cli.php`: Gerätehilfe der Proben. Die Uhr: `pruefstand.sh kryptoprobe`.

## Was es braucht

Python mit `cryptography`, Node mit Playwright und einen Browser aus
`/opt/pw-browsers`. Keine Anlage: Die Seite beantwortet die Probe selbst. PHP mit
`openssl`; `probe_server.php` bindet `server/db.php` ein und braucht deshalb eine
`server/config.php` (für `kdf_haelfte()` aus `demo_lib.php`, keine Datenbankabfrage).

## Erwartete Zahl

`referenz.py`: Selbstprobe **45/0** · Fassung 2 **46 geöffnet, 52 Gegenlagen, 5 Hinweise**.
`referenz_inhalt.py`: **29/0** · **24 geöffnet, 34 Gegenlagen**. PHP **18** · Browser **302**,
0 ausgenommen · `ortsdaten_probe.mjs` **21** — je 0 Abweichungen (19.11.2026, KA-26).

## Was es nicht kann

Die Vektoren belegen das Format, nicht den Entwurf. Ob Garmin und Android dieselben Bytes schreiben, messen die Krypto-Probe und die Android-Prüffälle, nicht diese Probe.
