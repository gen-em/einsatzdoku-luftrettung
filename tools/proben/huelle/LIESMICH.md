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
Ableitung → `vektoren-inhalt.json`. Jede Gegenlage (vertauscht, zurückgelegt, Zeit
verschoben, Stück entfernt, ohne Geräteschlüssel) scheitert mit benanntem Grund — auch
die erste Fassung (Kennbyte 1 → `ohne-herkunft`, `edk1:`/`edka1:` → `kennung` bzw.
`altformat`): Seit KA-07 öffnet sie kein Leser mehr; gebaut wird sie nur als Gegenlage
(`vektoren.json` und die Übernahme sind entfallen). `probe.mjs` misst die Seite mit
`pruefungen2.js` (`crypto.js`, `inhalt2.js`, `huelle2.js`, `standbuch.js`);
`--bestand <Ordner>` misst einen älteren Stand. `probe_server.php`: die Gerätehilfe in
PHP gegen die Referenz, beide Richtungen. Die Uhr: `pruefstand.sh kryptoprobe`.
`geraet.php`, `*_cli.php`: Gerätehilfe.

## Was es braucht

Python mit `cryptography`, Node mit Playwright und einen Browser aus
`/opt/pw-browsers`. Keine Anlage: Die Seite beantwortet die Probe selbst. PHP mit
`openssl`; `probe_server.php` bindet `server/db.php` ein und braucht deshalb eine
`server/config.php` (für `kdf_haelfte()` aus `demo_lib.php`, keine Datenbankabfrage).

## Erwartete Zahl

`referenz.py`: Selbstprobe **45 Fälle, 0 Fehlschläge** · Fassung 2 **46 geöffnet,
52 Gegenlagen, 5 Hinweise**, 0 Abweichungen. `referenz_inhalt.py`: **29 Fälle,
0 Fehlschläge** · **24 geöffnet, 34 Gegenlagen**. PHP **18** · Browser **302 Prüfungen**,
0 Abweichungen, 0 ausgenommen · `ortsdaten_probe.mjs` **21 Prüfungen**, 0 Abweichungen
(19.11.2026, KA-26; bis dahin mit Fassung 1: Selbstprobe 48, Fassung 2 55/50, Inhalt
26/28, Server 22, Browser 606).

## Was es nicht kann

Die Vektoren belegen das Format, nicht den Entwurf. Ob Garmin und Android dieselben Bytes schreiben, messen die Krypto-Probe und die Android-Prüffälle, nicht diese Probe.
