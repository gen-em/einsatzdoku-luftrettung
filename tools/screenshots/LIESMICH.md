# Bilderlauf

Nimmt jede Seite in mehreren Breiten auf und misst, was ein Bild nicht zeigt.
**Anlass: Nr. 185, 225** — ein Überlauf nur in WebKit, eine Seite außerhalb ihres Gerüsts.

## Aufruf

```bash
node tools/screenshots/aufnehmen.mjs --stufe klein|neben|haupt  # --nur · --finger · --jobs-token · --selbstprobe
python3 tools/screenshots/kontrast.py                 # Kontrast der Token gegen die Fläche
python3 tools/screenshots/vergleichen.py <vorher>     # --nur-text · --erwartet <seite> · --selbstprobe
```

## Was es misst

Überlauf, **Konsolenfehler**, Knopfhöhen gegen 44 und 36 px (`CLAUDE.md` 5),
Karten außerhalb von `main.inhalt` und die **Bildgleichheit** über die
Breiten. Drei Stufen (E-PK-14): klein = berührte Seiten, drei Breiten,
Chromium · neben = alle Seiten, acht Breiten · haupt = alle drei Engines.
`vergleichen.py` hält zwei Läufe gegeneinander — Bild, Zeile und **Form**;
nur die Form ist ein Befund. Seiten und Bedienschritte: `seiten.json`.

## Was es braucht

Eine Installation, Chromium (haupt: alle drei Engines) und für die
Wartungsseiten `--jobs-token`. **Ohne Token bricht der Lauf ab.**

## Erwartete Zahl

Voller Lauf: **496 Einzelbilder, 62 Kontaktbögen, Überlauf 0, Knöpfe
falscher Höhe 0, 162 Karten / 0 außerhalb**. Selbstproben offline:
`aufnehmen.mjs` **15 von 15**, `vergleichen.py` **14 von 14**.

## Was es nicht kann

Es bedient nichts (dafür `tools/bedienprobe/`), sieht nur die Bedienzustände
aus `seiten.json`, und ein Bild sagt nicht, ob es **richtig** ist. **Jeder
Lauf löscht den vorigen** — wer vergleichen will, sichert `ausgabe/` vor der
Änderung weg. Über eine Versionsstufe hinweg wird der Bildvergleich nie
null (die Fassung steht in jeder Fußzeile); dann gilt `--nur-text`.
