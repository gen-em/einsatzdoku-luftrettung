# Hüllenprobe

Gerätehülle (Fassung 1 und 2) und Chiffretexte unter dem Inhaltsschlüssel (`edk2:`) gegen Prüfvektoren.
**Anlass: Nr. 43 und 563** — Garmin, Android und Browser müssen dieselben Bytes schreiben.

## Aufruf

```bash
bash tools/proben/proben.sh huelle [--motor chromium] [--drossel 6]
python3 tools/proben/huelle/referenz.py erzeugen          # nach einer Formatänderung
python3 tools/proben/huelle/referenz_inhalt.py erzeugen   # ebenso, für edk2
```

## Was es misst

`referenz.py` ist die Referenz der Gerätehülle (Format im Kopf): Fassung 1 →
`vektoren.json`, `Vektoren.mc`; Fassung 2 (Herkunft, Zeit, Zähler, Stückfolge,
Vergleichswert der Kopplung) → `vektoren-2.json`, `Vektoren2.mc`, `HuelleVektor2.mc`. `referenz_inhalt.py`: `edk2:`,
Standbuch, Gerätebuch, Ableitung → `vektoren-inhalt.json`. Jede Gegenlage
(vertauscht, zurückgelegt, Zeit verschoben, Stück entfernt, ohne Geräteschlüssel)
scheitert mit benanntem Grund. `probe_server.php` hält Fassung 1 dagegen, `probe.mjs`
beide: Fassung 2 mit `pruefungen2.js` (`inhalt2.js`, `huelle2.js`, `standbuch.js`), einmal
mit der Weiche wie ausgeliefert, einmal umgelegt; `--bestand <Ordner>` misst einen älteren
Stand. Die Uhr: `pruefstand.sh kryptoprobe`. `geraet.php`, `*_cli.php`: Gerätehilfe.

## Was es braucht

Python mit `cryptography`, Node mit Playwright und einen Browser aus
`/opt/pw-browsers`. Keine Anlage: Die Seite beantwortet die Probe selbst.

## Erwartete Zahl

`referenz.py`: Selbstprobe **19 Fälle, 0 Fehlschläge** · Fassung 1 **6 geöffnet,
8 Störungen** · Fassung 2 **26 geöffnet, 39 Gegenlagen**, je 0 Abweichungen.
`referenz_inhalt.py`: **18 Fälle, 0 Fehlschläge** · **23 geöffnet, 28 Gegenlagen**.
Server **22** (bis KA-06c stand hier 18 — überholt seit KA-06b-3) · Browser **415 Prüfungen** (39 + 177 Weiche aus + 199 an), 0 Abweichungen (14.11.2026, mit den vier Wechselfällen aus KA-06c).

## Was es nicht kann

Fassung 2: Referenz und Browser, Geräte und PHP folgen. Die Vektoren belegen das Format, nicht den Entwurf.
