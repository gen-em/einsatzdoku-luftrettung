# Register der Browserwerkzeuge — welche Engine, auf welchem Weg

**Jede Browserprobe läuft in Chromium, Firefox und WebKit** (R87,
`docs/Pruefablauf.md` 1, Grundsatz 8). Diese Datei sagt je Werkzeug unter
`tools/`, das einen Browser startet, **wie** es dahin kommt. Gelesen wird sie
vom Bestandsriegel, Regel `motoren` (`tools/quelltext/bestand.py`, Stufe 1):
Ein Browserwerkzeug ohne Zeile ist rot, eine Ausnahme ohne Grund auch.

Die Wege:

- **Probe: NAME** — der Prüfstand fährt die Probe `NAME` aus
  `pruefablauf.json` dreimal (`"motoren": true`, `MOTOR=chromium|firefox|webkit`).
- **Helfer** — kein eigener Lauf; es läuft in der Engine dessen, der es ruft
  (`MOTOR` aus der Umgebung).
- **von Hand** — kann alle drei (`--motor`), aber kein Prüfstand ruft es.
- **nur Chromium** — misst etwas, das es nur in Chromium gibt; der Grund steht daneben.
- **kein Test** — erzeugt etwas, prüft nichts.

Gestartet wird über `starten()` aus `tools/motor.mjs`, das auch die Engine und
ihre Fassung ins Protokoll schreibt (`Motor: firefox 157.0`). Ein eigenes
`.launch(` ist nur bei „nur Chromium" und „kein Test" erlaubt.

| Werkzeug | Weg | Grund |
|---|---|---|
| `tools/bedienprobe/probe.mjs` | Probe: bedienprobe | Zwei Stücke nur in Chromium (E-DM-05), in Firefox und WebKit benannt: der Weg `einstellungen-profil-passkey` (virtueller Authenticator über CDP) und im Weg `betrieb-server-schluesselwechsel` die Seitenzahl des Schlüsselblatts (`page.pdf()`) |
| `tools/screenshots/aufnehmen.mjs` | Probe: bilderlauf | |
| `tools/stilvergleich/stilvergleich.js` | Probe: stilvergleich | |
| `tools/proben/abmelden/pruefe.mjs` | Probe: abmelde-probe | |
| `tools/proben/container/probe.mjs` | Probe: containerprobe | |
| `tools/proben/csp-browser/browserprobe.mjs` | Probe: browserprobe-csp | |
| `tools/proben/freigabe/probe.mjs` | Probe: freigabeprobe | |
| `tools/proben/frist/pruefe.mjs` | Probe: fristprobe | |
| `tools/proben/gpx/probe.mjs` | Probe: gpxprobe | |
| `tools/proben/huelle/probe.mjs` | Probe: huellenprobe | `--drossel` geht nur in Chromium (CDP, E-DM-05); der Prüfstand fährt ohne |
| `tools/proben/kontopaar/probe.mjs` | Probe: kontopaarprobe | |
| `tools/proben/ortsdaten/probe.mjs` | Probe: ortsdatenprobe | |
| `tools/proben/rueckweg/probe.mjs` | Probe: rueckwegprobe | |
| `tools/proben/wartungslauf/probe.mjs` | Probe: wartungslaufprobe | |
| `tools/referenzdatensatz/browser/kreislauf_csv.mjs` | Probe: kreislauf-csv | |
| `tools/referenzdatensatz/browser/kreislauf_edbak.mjs` | Probe: kreislauf-edbak | |
| `tools/referenzdatensatz/einspielen/passwort_setzen.mjs` | Helfer | gerufen von `kreislauf.py`, `pruefkonto.py`, `einspielen.py` und `messen.py`; läuft in deren Engine |
| `tools/messstand/browserprobe.mjs` | nur Chromium | Die Grundlinie ist gedrosseltes Chromium, und die CPU-Drossel gibt es nur über CDP (E-DM-06) |
| `tools/messstand/einspielen.mjs` | nur Chromium | wie `browserprobe.mjs` (E-DM-06) |
| `tools/erzeugen/logos.mjs` | kein Test | erzeugt die Logo-Bilder aus SVG |
| `tools/proben/kopplung/rundlauf.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
| `tools/proben/anteil/betriebslauf.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
| `tools/proben/anteil/umstellungslauf.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
| `tools/proben/komplett/klickweg.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
| `tools/referenzdatensatz/browser/angriffswerte.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
| `tools/referenzdatensatz/browser/csv_import.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
| `tools/referenzdatensatz/browser/demo_bremse.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
| `tools/referenzdatensatz/browser/demo_pruefen.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
| `tools/referenzdatensatz/browser/papierkorb_misch.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
| `tools/referenzdatensatz/browser/referenz_export.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
| `tools/referenzdatensatz/einspielen/sichtpruefung.mjs` | von Hand | kein Prüfstand ruft ihn (Nr. 391) |
