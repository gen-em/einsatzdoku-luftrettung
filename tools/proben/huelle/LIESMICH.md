# Hüllenprobe

Die Gerätehülle (S11, Fassung 1) in drei Umsetzungen gegen Prüfvektoren.
**Anlass: Nr. 43** — Garmin, Android und Browser müssen dieselben Bytes schreiben.

## Aufruf

```bash
bash tools/proben/proben.sh huelle [--motor chromium] [--drossel 6]
python3 tools/proben/huelle/referenz.py erzeugen   # nach einer Formatänderung
```

## Was es misst

`referenz.py` (Python, `cryptography`) ist die Referenz: Format im Kopf,
`vektoren.json` und `Vektoren.mc` für die Uhr erzeugt sie, `pruefen` rechnet
jeden Fall nach. `probe.mjs` lädt `huelle.js` (WebCrypto; ab S11-03 in
`crypto.js`) und prüft je Fall Z, HKDF, Bindung, die Hülle byte-gleich und das
Öffnen, je Störung den Grund der Abweisung, dazu einen Rundlauf; Zeiten für
200 und 500 Punkte stehen dabei, ohne Abnahme. Die Uhr prüft
`pruefstand.sh kryptoprobe` (`tools/uhr-pruefstand/krypto-probe/`).

## Was es braucht

Python mit `cryptography`, Node mit Playwright und einen Browser aus
`/opt/pw-browsers`. Keine Anlage: Die Seite beantwortet die Probe selbst.

## Erwartete Zahl

Selbstprobe **5 Fälle, 0 Fehlschläge** · `pruefen` **6 Fälle geöffnet,
8 Störungen abgewiesen, 0 Abweichungen** · Browser **39 Prüfungen,
0 Abweichungen** (Chromium, 11.10.2026).

## Was es nicht kann

Zwei Umsetzungen auf derselben Maschine belegen das Format, nicht die
Sicherheit des Entwurfs — die liest 12b (R86). Die Zeiten gelten für diese
Maschine; Firefox und WebKit fehlten im Abbild vom 11.10.2026.
