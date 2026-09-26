# Bilderlauf

Nimmt jede Seite in mehreren Breiten auf und misst, was ein Bild nicht zeigt.
**Anlass: Nr. 185, 225, 297, 116** — ein Überlauf nur in WebKit, eine Seite außerhalb ihres Gerüsts, eine Rolle unter falschem Namen, ein Farbpaar ohne Listeneintrag.

## Aufruf

```bash
node tools/screenshots/aufnehmen.mjs --stufe klein|neben|haupt  # --nur · --finger · --etikett · --jobs-token · --rolle-admin · --rolle-support · --selbstprobe
python3 tools/screenshots/kontrast.py                 # Kontrast der Token gegen die Fläche · --selbstprobe
python3 tools/screenshots/vergleichen.py <vorher>     # --nur-text · --erwartet <seite> · --selbstprobe
```

## Was es misst

Überlauf, **Konsolenfehler**, Knopfhöhen gegen 44 und 36 px (`CLAUDE.md` 5), Karten außerhalb von `main.inhalt`,
das **Umgebungsetikett** (P5c/AP1: `--etikett Staging` misst Titelvorsatz und rote Kopfleiste, ohne den Schalter die Gegenrichtung)
und die **Bildgleichheit** über die Breiten; **rollende Behälter** nennt der Bericht, sie halten nicht auf. Fünf Rollen: aus, demo, betreiberin, admin, support (Prüfkonten: `pruefkonten.sh`). Drei Stufen (E-PK-14): klein = berührte Seiten, drei Breiten, Chromium · neben = alle
Seiten, zehn Breiten · haupt = alle drei Engines. `kontrast.py` leitet die Farbpaare aus dem Stylesheet ab; ein Paar ohne Listeneintrag ist rot. `vergleichen.py` hält zwei Läufe gegeneinander — Bild, Zeile und **Form**;
nur die Form ist ein Befund. Seiten und Bedienschritte: `seiten.json`.

## Was es braucht

Eine Installation, Chromium (haupt: alle drei Engines) und für die
Wartungsseiten `--jobs-token`. **Ohne Token bricht der Lauf ab.**

## Erwartete Zahl

Voller Lauf: **780 Einzelbilder, 78 Kontaktbögen, Überlauf 0, Knöpfe
falscher Höhe 0, 188 Karten / 0 außerhalb, 51 Bilder mit rollendem Behälter**, 1 170 s (26.09.2026, R4-08: zehn Breiten, sechs Seiten für Admin und Support). `kontrast.py` **33 Paare, 0 verfehlt, 32 abgeleitet, 0 ohne Eintrag**. Selbstproben offline:
`aufnehmen.mjs` **15 von 15**, `vergleichen.py` **14 von 14**, `kontrast.py` **4 von 4**.

## Was es nicht kann

Es bedient nichts (dafür `tools/bedienprobe/`), sieht nur die Bedienzustände
aus `seiten.json`, und ein Bild sagt nicht, ob es **richtig** ist. **Jeder
Lauf löscht den vorigen** — wer vergleichen will, sichert `ausgabe/` vor der
Änderung weg. Über eine Versionsstufe hinweg wird der Bildvergleich nie
null (die Fassung steht in jeder Fußzeile); dann gilt `--nur-text`.
