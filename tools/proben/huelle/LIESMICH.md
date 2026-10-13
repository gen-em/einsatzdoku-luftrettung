# Hüllenprobe

Die Gerätehülle (S11, Fassung 1) in vier Umsetzungen gegen Prüfvektoren.
**Anlass: Nr. 43** — Garmin, Android und Browser müssen dieselben Bytes schreiben.

## Aufruf

```bash
bash tools/proben/proben.sh huelle [--motor chromium] [--drossel 6]
python3 tools/proben/huelle/referenz.py erzeugen   # nach einer Formatänderung
```

## Was es misst

`referenz.py` ist die Referenz (Format im Kopf, erzeugt `vektoren.json` und
`Vektoren.mc`). `probe_server.php` hält `server/geraetehuelle_lib.php`
byte-gleich dagegen, samt Rundlauf und Innenform ohne Höhe (-32768).
`probe.mjs` prüft `server/assets/crypto.js`: Z, HKDF, Bindung, Hülle, Öffnen,
Störungen, Rundlauf, Zeiten ohne Abnahme. Die Uhr: `pruefstand.sh kryptoprobe`.
`geraet.php` (für Python `geraet_cli.php`) ist die **Gerätehilfe der Proben**:
Paket im alten Format → wie ein Gerät ab Web 22; öffnet Hüllen, nie im Server.

## Was es braucht

Python mit `cryptography`, Node mit Playwright und einen Browser aus
`/opt/pw-browsers`. Keine Anlage: Die Seite beantwortet die Probe selbst.

## Erwartete Zahl

Selbstprobe **5 Fälle, 0 Fehlschläge** · `pruefen` **6 Fälle geöffnet,
8 Störungen abgewiesen, 0 Abweichungen** · Server **14 Prüfungen,
0 Abweichungen** (13.10.2026) · Browser **39 Prüfungen, 0 Abweichungen**
(Chromium, 11.10.2026).

## Was es nicht kann

Zwei Umsetzungen auf derselben Maschine belegen das Format, nicht die
Sicherheit des Entwurfs — die liest 12b (R86). Die Zeiten gelten für diese
Maschine; Firefox und WebKit fehlten im Abbild vom 11.10.2026.
