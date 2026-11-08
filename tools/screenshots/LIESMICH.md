# Bilderlauf

Nimmt jede Seite in mehreren Breiten auf und misst, was ein Bild nicht zeigt.
**Anlass: Nr. 185, 225, 297, 116, 530** — ein Überlauf nur in WebKit, eine Seite außerhalb ihres Gerüsts, eine Rolle unter falschem Namen, ein Farbpaar ohne Listeneintrag, drei Seitenregeln, die nur beim Ansehen auffielen.

## Aufruf

```bash
node tools/screenshots/aufnehmen.mjs --breiten drei|fuenf|zehn  # --stufe · --nur · --finger · --etikett · --jobs-token · --rolle-admin · --rolle-support · --selbstprobe
python3 tools/screenshots/kontrast.py                 # Kontrast der Token gegen die Fläche · --selbstprobe
python3 tools/screenshots/vergleichen.py <vorher>     # --nur-text · --erwartet <seite> · --selbstprobe
```

## Was es misst

Überlauf, **Konsolenfehler**, Knopfhöhen gegen 44 und 36 px (`CLAUDE.md` 5), Karten außerhalb von `main.inhalt`,
die **drei Seitenregeln** (seit Konzept OF, Nr. 530: kein Knopf zwischen zwei Abschnitten, eine rechte Kante in den Einstellungen ab 1024 px, höchstens ein Satz je Karte und je Seitenerklärung, Leerzustände („Noch keine …“) ausgenommen — bekannte Verstöße mit ihrem Paket unter `seitenregeln` in `ausnahmen.json`, eine Ausnahme ohne Befund ist selbst einer),
das **Umgebungsetikett** (P5c/AP1: `--etikett Staging` misst Titelvorsatz und Kopfleiste mit Etikett (seit Schritt 26 orange 800), ohne den Schalter die Gegenrichtung)
und die **Bildgleichheit** über die Breiten; **rollende Behälter** nennt der Bericht, sie halten nicht auf. Fünf Rollen: aus, demo, betreiberin, admin, support (Prüfkonten: `pruefkonten.sh`). Breiten und Seiten wählt der Prüfstand (`auswahl.py --bilderlauf`, E-PU-03, -04): drei Breiten in klein, fünf im schmalen
Lauf ab neben, zehn beim vollen Lauf und bei Stylesheet/`ui.php`; Seiten über ihre eigene Datei (`--nur`), alle bei einer geteilten. Engines: Grundsatz 8 (R89, `MOTOR=`). `kontrast.py` leitet die Farbpaare aus dem Stylesheet ab; ein Paar ohne Listeneintrag ist rot. `vergleichen.py` hält zwei Läufe gegeneinander — Bild, Zeile und **Form**;
nur die Form ist ein Befund. Seiten und Bedienschritte: `seiten.json`.

## Was es braucht

Eine Installation, die drei Engines (`aufbauen.sh web`) und für die
Wartungsseiten `--jobs-token`. **Ohne Token bricht der Lauf ab.**

## Erwartete Zahl

Voller Lauf: **860 Einzelbilder, 86 Kontaktbögen, Überlauf 0, Knöpfe
falscher Höhe 0, 208 Karten / 0 außerhalb, 80 Bilder mit rollendem Behälter**, 1 474 s (04.10.2026, Prüfstand zu SR-04; am 28.09.2026 205 Karten, 66 rollend — die neue Seite rollt nicht; seit R4-08 zehn Breiten, sechs Seiten für Admin und Support). `kontrast.py` **58 Paare je Modus (hell und dunkel), 0 verfehlt, 36 abgeleitet, 0 ohne Eintrag** (07.11.2026, Schritt 26: Paare nach Rollen statt Farben, zwei Modi; bis dahin 36 Paare nur hell). Seitenregeln: **0 Befunde**, die Zahl der Ausnahmen sinkt je Paket von OF bis auf 0 (OF-09). Selbstproben:
`aufnehmen.mjs` **33 von 33** (08.11.2026, Nr. 530: Fälle 25 bis 33 an einer nachgebauten Seite im Browser — seither braucht die Selbstprobe Chromium), davor **24 von 24** (21.10.2026, Nr. 393: Fälle 18 bis 24 für den abgebrochenen Abruf in WebKit; hier stand bis dahin 15, seit Konzept DM waren es 17), `vergleichen.py` **14 von 14**, `kontrast.py` **4 von 4**.

## Was es nicht kann

Es bedient nichts (dafür `tools/bedienprobe/`), sieht nur die Bedienzustände
aus `seiten.json`, und ein Bild sagt nicht, ob es **richtig** ist. **Jeder
Lauf löscht den vorigen** — wer vergleichen will, sichert `ausgabe/` vor der
Änderung weg. Über eine Versionsstufe hinweg wird der Bildvergleich nie
null (die Fassung steht in jeder Fußzeile); dann gilt `--nur-text`.
