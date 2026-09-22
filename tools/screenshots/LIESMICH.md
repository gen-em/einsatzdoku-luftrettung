# Bilderlauf

Nimmt jede Seite in mehreren Breiten auf und misst, was ein Bild allein
nicht zeigt. **Anlass: Nr. 185, Nr. 225.**

## Aufruf

```bash
node tools/screenshots/aufnehmen.mjs --stufe klein|neben|haupt
python3 tools/screenshots/kontrast.py        # Kontrast gegen die Fläche
```

`--nur <name>` filtert Seiten, `--finger` misst gegen 44 px.

## Was es misst

Überlauf, **Konsolenfehler**, Knopfhöhen gegen die Sollwerte aus
`CLAUDE.md` 5, Karten außerhalb von `main.inhalt` — und seit PK-04 die
**Bildgleichheit**: Acht identische Dateien wären acht Bilder, bei denen
alles grün meldet, ohne dass die Breite je umgestellt wurde.

**Drei Stufen** (E-PK-14): klein = berührte Seiten, drei Breiten, Chromium ·
neben = alle Seiten, acht Breiten · haupt = alle drei Engines. Die
Risikoliste ist entfallen — der einzige WebKit-Fund lag auf einer Seite,
die nicht darauf stand.

## Was es braucht

Eine Installation, Chromium (haupt: alle drei Engines) und für die
Wartungsseiten ein `--jobs-token`. **Ohne Token bricht der Lauf ab.**

## Erwartete Zahl

Voller Lauf: **496 Einzelbilder, 62 Kontaktbögen, Überlauf 0, Knöpfe
falscher Höhe 0, 162 Karten / 0 außerhalb**.

## Was es nicht kann

Es bedient nichts (dafür `tools/bedienprobe/`) und vergleicht nicht mit
einem früheren Stand (dafür der Stilvergleich).
