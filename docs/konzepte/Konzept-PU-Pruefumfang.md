# Konzept PU — Der Prüfumfang folgt der Berührung, nicht dem Kalender

**Kürzel:** `PU` („Prüfumfang"). Arbeitspakete `PU-NN`, Entscheidungen
`E-PU-NN`, Befunde `F-PU-NN`, Fragen an die Betreiberin `Q-PU-NN`,
Prüfpunkte `P-PU-NN` (im Prüfdokument). Commit-Nachrichten beginnen mit dem
Paket (`PU-02: …`). Benennung nach `docs/Pruefablauf.md` 7.
**Rahmenplan:** Schritt **20** — „PU — Prüfumfang" (R89). Voraussetzung für
die Umsetzung: **12b gemergt** (Abschnitt 1.3); das Konzept selbst läuft
daneben.
**Herkunft:** Frage der Betreiberin am 01.11.2026, ob die Entwicklung „zu
90 % aus Testläufen" bestehe. Die Zählung (Abschnitt 1.1) sagt: ein Drittel
der Zeilen, aber der größere Teil der Wartezeit. Vorschläge, Rückfragen und
Entscheidungen fielen im selben Gespräch (E-PU-01 bis -12); dieses Dokument
hält sie fest. Die Umsetzung findet in einer anderen Sitzung statt.
**Modell:** **Opus** für jedes Paket (`CLAUDE.md` 7, Standard).
**Fächerung (`CLAUDE.md` 7):** keine. Die Pakete fassen dieselben Läufer an
(`pruefen.sh`, `auswahl.py`, `bericht.py`, `pruefablauf.json`), und
Prüfarbeit lässt sich nicht fächern.
**Versionsstufe:** keine. Der Schritt fasst `tools/`, `docs/`,
`.github/` und `CLAUDE.md` an, nichts unter `server/`, `watch/` oder
`android/` (`CLAUDE.md` 2, Punkt 1). Der Prüfstand fährt `klein`.
**Ablage:** dieses Dokument; `Pruefdokument-PU-Pruefumfang.md` daneben, in
der neuen Form aus E-PU-08.
**Zweig:** `claude/zealous-albattani-2xk21u` für das Konzept, aufgesetzt auf
`origin/main` bei `9dd9790` (Web 23.5.1, Uhr 4.1.0, Android 0.18.1). Die
Umsetzung setzt einen **neuen** Zweig auf `main` auf, sobald 12b gemergt ist
(1.3).
**Backlog-Spanne:** **420 bis 429** — umgebucht von 410 bis 419 beim Merge mit 12b am 01.11.2026, weil 12b dieselbe Spanne reserviert und 410, 411 vergeben hatte (E-SD-21, eingetragen mit PU-00 vor
jeder Vergabe).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **01.11.2026 — Konzept geschrieben (PU-00). Umsetzung wartet auf den Merge von 12b.** |
> | Entschieden | E-PU-01 bis E-PU-12 (Abschnitt 2) |
> | Offen | keine Frage (Q-PU-01, -02 beantwortet am 01.11.2026) |
> | Umsetzung | PU-00 erledigt 01.11.2026 (dieses Dokument, Fahrplanzeile 20, R89, Verlaufszeile, Spanne). PU-01 bis PU-10 offen. |
> | Halt | **Umsetzung blockiert bis 12b gemergt ist** (1.3). |
> | Fable-Schritte | keine |
> | Fächerung | keine (Kopf) |
> | Nummern | 420 bis 429 reserviert (bis 01.11.2026 410 bis 419, umgebucht beim Merge mit 12b); vergeben: keine. |

---

## 1. Auftrag und Ausgangslage

**Ziel:** Der Prüfstand misst so viel, wie die Änderung verlangt, und nicht
mehr — bei gleicher Sicherheit an den Stellen, die Fehler aufgehalten haben.
Was die Kette vor `main` und vor Produktiv hält, bleibt. Was wegfällt, ist
Wartezeit und Nacharbeit an Dingen, die noch nie einen Fehler gezeigt haben.

### 1.1 Gemessen am 01.11.2026

**Zeilen je Bereich**, 100 Commits ohne Merges vom 14.10. bis 01.11.2026,
ohne `vendor/`, Sperrdateien und `wegwerfdomains.txt`
(`git log --numstat`):

| Bereich | Anteil |
|---|---|
| Anwendung (`server/`, `android/`, `watch/`) | 34 % |
| Prüfwerkzeuge und Kette (`tools/`, `.github/`) | 32 % |
| übrige Dokumentation (Changelog, Technik, Backlog, Rahmenplan, Versionskopf) | 25 % |
| Konzepte und Prüfdokumente | 9 % |

Im Bestand stehen 157 000 Zeilen unter `tools/` gegen 209 000 unter
`server/`. Acht der 34 abgeschlossenen Schritte im Rahmenplan waren reine
Werkzeugschritte (P0, TB, RP, BR, BV, SD, Kette II, PK), DM fast ganz.

**Zeit:** Die Nebenstufe braucht rund 21 Minuten (E-RP-05), der Bilderlauf
in drei Engines allein rund 70 Minuten (`Pruefablauf.md` 4). Eine Sitzung
wartet davon den größten Teil. Zeilen sind nicht Zeit — darum fühlt sich
ein Drittel wie neun Zehntel an.

**Was die Prüfung der Prüfung gekostet hat:** RP fand 9 von 36 Proben rot,
keine wegen der Anwendung. BR fand 57 Befunde, alle im Werkzeugbestand.

**Hauptversionssprünge:** vier seit Mitte September (20.0.0 am 14.09.,
21.0.0 am 25.09., 22.0.0 und 23.0.0 im Oktober), also alle zwei bis drei
Wochen — mit 5 bis **45** Nebenfassungen dazwischen (Web 20). Ein voller
Lauf nur in `haupt` wäre bei Web 20 für 45 Nebenfassungen ausgeblieben.

### 1.2 Der Bestandsriegel, gezählt (F-PU-01)

Grundlage: alle Prüfdokumente, Changelog und Backlog seit dem 24.09.2026,
dem Tag, an dem BR-02 den Riegel auf 0 Befunde brachte. Gezählt ist jeder
dokumentierte rote Lauf je Regel von `tools/quelltext/bestand.py`.

| Regel | Rote Läufe | Was es war |
|---|---|---|
| `design` | 5 (R4-25, SR-02, SR-03, SR-07, S11) | eine erzeugte Tabelle in `Design.md` nicht neu erzeugt |
| `tabelle` | 4 (BR, R4-03, P5c, SR-07) | die erzeugte Tabelle in `Pruefablauf.md` 4 nicht neu erzeugt, einmal eine Kopie |
| `form` | 3 (R4-16, R4-25 zweimal) | 41, 42 und 43 Zeilen in einer `LIESMICH.md`, Decke 40 |
| `anlass` | 2 (BR-04, R17) | Anlass-Zeile fehlte oder stand doppelt |
| `selbst` | 1 (bei Einführung) | **echt:** die Selbstprobe der Textprobe lief nirgends |
| `anlage`, `tor`, `ablauf`, `backlog`, `motoren`, `anleitung`, `inventur`, `lose`, `probe`, `zeile` | 0 | — |

**15 rote Läufe, 14 davon Formalien, einer echt — und der lag im Werkzeug.**
Einen Fehler in der Anwendung kann der Riegel nicht finden: Er liest nur
`tools/` und die Dokumentation. Seine Laufzeit beträgt 5 Sekunden; was er
kostet, ist Nacharbeit (9 der 15 Läufe: Erzeuger starten, committen,
Prüfstand noch einmal) und Pflege (2 657 Zeilen, Selbstprobe mit 177
Fällen). **Grenze der Zählung:** Rote Läufe, die eine Sitzung still
berichtigt hat, stehen in keinem Prüfdokument; die Zahl ist eher zu niedrig.
Das Muster — fast nur erzeugte Tabellen und Zeilenzahlen — trägt trotzdem.

### 1.3 Warum die Umsetzung auf 12b wartet

Der Zweig von 12b (`claude/new-session-lvb0x0`, R17-28, noch nicht gemergt)
ändert gegen `main` genau die Dateien, die dieser Schritt umbaut:
`tools/quelltext/bestand.py` (147 Zeilen), `tools/pruefstand/pruefen.sh`
(57), `auswahl.py` (48), `bericht.py` (31), `tools/screenshots/aufnehmen.mjs`
(256), dazu `tools/steuerung/` und `.github/workflows/`. Zwei Zweige an
denselben Zeilen heißen einen Merge-Konflikt in der Werkzeugschicht, und die
lässt sich nicht fächern (`CLAUDE.md` 7). Darum: Konzept jetzt, Umsetzung
auf einem frischen Zweig nach dem Merge von 12b. Das Konzept selbst fasst
nur `docs/` an und kann neben 12b gemergt werden (Rahmenplan 4:
„Konzeptarbeit zu allem").

### 1.4 Was bleibt, wie es ist

Nicht angefasst, weil es Fehler aufgehalten hat und je ein bis zwei Minuten
kostet: das Tor vor `main` mit Pflichtbericht (Stufe 1), die Schemaprobe
gegen MySQL 8.4 und MariaDB 10.6 (Nr. 267), das Backup-Tor und die
Pflichtfreigabe vor Produktiv, die Integritätswache. Ebenso die 33 Proben
unter `tools/proben/`: Sie laufen in Sekunden und prüfen Verschlüsselung,
Sitzung und Sicherung. Und keine Regel des Bestandsriegels wird gestrichen
(E-PU-07): Laufzeit spart das nicht, und `anlage`/`tor` sind aus sechs roten
PR-Läufen entstanden.

## 2. Entscheidungen

| Nr. | Entscheidung | Wer, wann |
|---|---|---|
| E-PU-01 | **Zehnerregel.** Der volle Lauf — drei Engines, alle Breiten, alle Seiten — fährt in `neben`, wenn die Spanne der Nebennummern zwischen `main` und dem Zweig eine durch 10 teilbare Zahl enthält oder überschreitet (23.9 → 23.11 ist voll), und in jeder Hauptstufe. Die Regel liest aus `server/version.php`, hält keinen Zustand, steht im Bericht (`umfang=voll:zehner`) und wird im Tor gegengelesen. Dazu `--voll` am Prüfstand, der den vollen Lauf jederzeit erzwingt und im Bericht steht. | Betreiberin, 01.11.2026 |
| E-PU-02 | **Engines je Berührung.** Ab `neben` laufen Browserproben in Chromium allein; Firefox und WebKit kommen dazu, wenn `server/assets/style.css` oder `server/ui.php` berührt ist, beim vollen Lauf (E-PU-01) und in `haupt`. Stufe 2 bleibt dreifach. Grundsatz 8 wird entsprechend neu gefasst (R89 ersetzt R87). | Betreiberin, 01.11.2026 |
| E-PU-03 | **Breiten je Berührung.** Der Bilderlauf nimmt zehn Breiten nur beim vollen Lauf, in `haupt` und bei Berührung von `style.css`/`ui.php`; sonst die Schwellenbreiten aus `Design.md` 7 — fünf, E-PU-11. | Betreiberin, 01.11.2026 |
| E-PU-04 | **Seiten je Berührung.** In `neben` nehmen Bilderlauf und Bedienprobe die berührten Seiten wie in `klein`; **alle** Seiten, wenn eine geteilte Datei berührt ist (`ui.php`, `style.css`, ein Skript unter `server/assets/`, das mehr als eine Seite lädt), beim vollen Lauf und in `haupt`. Eine feinere Zuordnung Baustein → Seite wird nicht gebaut; die Liste der geteilten Dateien steht in `pruefablauf.json`. | Betreiberin, 01.11.2026 |
| E-PU-05 | **Ergebnisart `hinweis`.** Neben grün, rot und nicht-gemessen kennt der Bericht `hinweis`: gemessen, gemeldet, hält nicht auf. Das Tor liest `hinweis` als nicht rot. Grundsatz 7 bleibt gewahrt — nichts wird übersprungen, alles steht im Bericht. | Betreiberin, 01.11.2026 |
| E-PU-06 | **Steuerungsriegel nur noch im Prüfstand.** `steuerung` (Decken, Kopfzeilen) verlässt die Riegelliste des Tors und läuft im Prüfstand als `hinweis`. Ein roter PR wegen einer 21. Backlog-Zeile kostet sonst einen Prüfstandlauf für eine Textkorrektur. | Betreiberin, 01.11.2026 |
| E-PU-07 | **Bestandsriegel: erzeugen statt rot.** Der Prüfstand fährt vor dem Riegel `erzeugen.sh design` und `bericht.py erzeugen-doku` und meldet als `hinweis`, was sich geändert hat; die Änderung gehört in den Commit. Im Tor bleibt `bestand` rot, wenn jemand ohne Prüfstand committet. Die Zeilendecke von `form` wird `hinweis`. Keine Regel wird gestrichen (1.2). | Betreiberin, 01.11.2026 |
| E-PU-08 | **Prüfdokument = Prüfliste.** Ein Prüfdokument hält nur noch: was nicht geprüft werden konnte (zuerst), die Grenzen der Mittel, und die Handprüfliste — höchstens 20 Punkte, je Bedienweg, Erwartung, Scheitern. Die maschinellen Zahlen stehen im Prüfprotokoll des Konzepts und im Prüfbericht des Commits. `CLAUDE.md` 7 wird entsprechend gefasst. | Betreiberin, 01.11.2026 |
| E-PU-09 | **Eine Prüfliste statt zwölf Prüfdokumente.** Die offenen Punkte der zwölf Prüfdokumente in `docs/konzepte/` wandern, Doppelungen gestrichen, in **eine** Datei `docs/konzepte/Pruefliste.md`, nach Bereich gegliedert (Anmeldung, Sicherung, Zweitfaktor, Geräte, Kette, Oberfläche …); die Einzeldokumente werden gelöscht, die Zeilen in Rahmenplan 6.1 verweisen dorthin. Künftig geht ein Prüfdokument nach dem Merge ebenso auf: Offenes in die Prüfliste, Datei weg. | Betreiberin, 01.11.2026 |
| E-PU-10 | **Changelog kürzer.** Ein Eintrag sagt in höchstens fünf Sätzen, was das Problem war und warum diese Lösung; Prüfzahlen, Werkzeugbefunde und Paketnummern gehören nicht hinein — die stehen im Konzept und in der Git-Historie. Ohne Riegel, E-PU-12. | Betreiberin, 01.11.2026 |
| E-PU-11 | **Q-PU-01 (Empfehlung):** fünf Breiten im schmalen Bilderlauf — 390 (Handy, unter der ersten Schwelle), 768, 1024, 1200, 1600 (je knapp über einer Schwelle aus `Design.md` 7). | Betreiberin, 01.11.2026 |
| E-PU-12 | **Q-PU-02 (Empfehlung):** die Changelog-Regel steht in `CLAUDE.md` 2 ohne Riegel; der Preis — sie kann unbemerkt reißen — steht dabei. Schritt 13 fasst den Changelog ohnehin neu (R15). | Betreiberin, 01.11.2026 |

**Abgelehnt:** Werkzeugarbeit ohne eigenes Konzept zu führen (Vorschlag B4
vom 01.11.2026) — Werkzeugschritte bleiben, wo sie sinnvoll sind, eigene
Schritte mit Konzept.

## 3. Arbeitspakete

Seriell, in dieser Reihenfolge; jedes Paket endet mit Prüfstand, Statusblock
und Push.

| Paket | Inhalt | Dateien |
|---|---|---|
| PU-00 | Buchführung: dieses Dokument, Fahrplanzeile Schritt 20, R89, Verlaufszeile, Spanne 410–419. **Erledigt 01.11.2026.** | `docs/konzepte/`, `docs/Rahmenplan*.md`, `docs/Backlog.md` |
| PU-01 | **Zehnerregel und `--voll`** (E-PU-01): `auswahl.py` liest die Spanne der Nebennummern, gibt `umfang` mit Grund aus; `pruefen.sh` nimmt `--voll`; `bericht.py` schreibt `umfang=…` und liest es im Tor gegen (siebte Lage in `Pruefablauf.md` 5.1); Selbstproben je Lage. | `tools/pruefstand/`, `docs/Pruefablauf.md` |
| PU-02 | **Ergebnisart `hinweis`** (E-PU-05): im Lauf, im Bericht, im Tor; Selbstprobe von `bericht.py lesen`. Zuerst, weil PU-03 bis -06 darauf aufbauen. | `tools/pruefstand/`, `.github/workflows/pruefung.yml` |
| PU-03 | **Engines je Berührung** (E-PU-02): `MOTOREN` in `pruefen.sh` aus Berührung, `umfang` und Stufe; `motoren=`-Zeile im Bericht und ihre Gegenlesung angepasst; Grundsatz 8 neu gefasst, `CLAUDE.md` 6, `motoren.md`. | `tools/pruefstand/`, `docs/Pruefablauf.md`, `CLAUDE.md` |
| PU-04 | **Breiten und Seiten je Berührung** (E-PU-03, -04): `aufnehmen.mjs` mit Breitensatz je `umfang`; Liste der geteilten Dateien in `pruefablauf.json`; Bilderlauf und Bedienprobe in `neben` über `--nur` wie in `klein`, sonst alle. | `tools/screenshots/`, `tools/bedienprobe/`, `tools/pruefstand/` |
| PU-05 | **Steuerung aus dem Tor** (E-PU-06): `steuerung` aus `riegel` in `pruefablauf.json` und aus `pruefung.yml`; im Prüfstand als `hinweis`; `tools/kettenaufrufe/` und `bestand` Regel `tor` folgen von selbst. | `tools/pruefstand/`, `.github/workflows/`, `tools/steuerung/LIESMICH.md` |
| PU-06 | **Bestandsriegel** (E-PU-07): Erzeuger vor dem Riegel im Prüfstand, `hinweis` bei Änderung; `form`-Decke als `hinweis`; Selbstproben nachgezogen. | `tools/pruefstand/pruefen.sh`, `tools/quelltext/bestand.py`, `tools/erzeugen/` |
| PU-07 | **Prüfdokument-Form und Changelog-Regel** (E-PU-08, -10): `CLAUDE.md` 2 und 7, `Pruefablauf.md`; ohne Riegel (E-PU-12). | `CLAUDE.md`, `docs/Pruefablauf.md` |
| PU-08 | **Prüfliste** (E-PU-09): `docs/konzepte/Pruefliste.md` aus den zwölf Prüfdokumenten (Stand 01.11.2026: rund 300 offene Punkte), Doppelungen gestrichen; Rahmenplan 6.1 nachgezogen; Einzeldokumente gelöscht. Reine Doku-Arbeit, kann nach PU-07 auch als eigener PR laufen. | `docs/konzepte/`, `docs/Rahmenplan.md` |
| PU-09 | **Doku und Abschluss:** `Pruefablauf.md` 2 bis 5 und 6.11, `Sandbox-Setup.md`, `tools/pruefstand/LIESMICH.md`; Prüfdokument in der neuen Form; Prüfstand; PR. | `docs/`, `tools/pruefstand/LIESMICH.md` |

**Ersparnis, wenn alles steht** (geschätzt aus den Zahlen in 1.1): je
Nebenfassung ohne Stylesheet-Berührung rund 45 Minuten (Engines) plus mehr
als die Hälfte des verbleibenden Bilder- und Bedienlaufs; je Phase 9 von 15
roten Bestandsläufen weniger; je Prüfdokument die Hälfte des Textes.
Gemessen wird das in PU-09 am ersten vollen und am ersten schmalen Lauf.

## 4. Befunde

| Nr. | Gewicht | Fundort | Befund | Folge |
|---|---|---|---|---|
| F-PU-01 | mittel | `tools/quelltext/bestand.py`; Prüfdokumente seit 24.09.2026 | **Der Bestandsriegel hat in fünf Wochen 15-mal rot gemeldet, 14-mal wegen einer Formalie** (1.2). Neun davon waren eine nicht neu erzeugte Tabelle. | E-PU-07 |
| F-PU-02 | mittel | `Pruefablauf.md` 3, Tabelle „Stufe" | **Die Nebenstufe fährt den vollen Umfang bei jeder Nebenfassung**, obwohl die Zuordnung Datei → Probe (Abschnitt 4) für alles andere längst je Berührung wählt. Bilder- und Bedienlauf waren die Ausnahme. | E-PU-02 bis -04 |
| F-PU-03 | niedrig | `docs/konzepte/` | **Zwölf Prüfdokumente mit rund 300 offenen Prüfpunkten liegen nebeneinander** (01.11.2026: AR 15, BV 8, DM 6, P5c 44, PK 42, R17 44, R4 23, RW 5, S11 44, SR 15; BR, Kette II und SD ohne nummerierte Punkte). Viele betreffen dieselben Wege (Anmeldung, Sicherung, Zweitfaktor). | E-PU-09 |
| F-PU-04 | niedrig | `docs/CHANGELOG.md` | **144 000 geänderte Zeilen im Changelog seit September** — dort landen Prüfzahlen und Werkzeugbefunde, die ins Konzept gehören. | E-PU-10 |

## 4a. Fragen an die Betreiberin

| Nr. | Frage | Stand |
|---|---|---|
| Q-PU-01 | **Beantwortet 01.11.2026: fünf, E-PU-11.** **Vier oder fünf Breiten im schmalen Bilderlauf?** `Design.md` 7 hat vier Schwellen (720, 1024, 1200, 1600). Vier Breiten knapp darüber (768, 1024, 1200, 1600) messen die Schwellen; eine Handybreite darunter (390) fehlt dann, und auf dem Handy liegt die meiste Bedienung. **Empfehlung: fünf** — 390, 768, 1024, 1200, 1600. | beantwortet |
| Q-PU-02 | **Beantwortet 01.11.2026: ohne, E-PU-12.** **Changelog-Regel mit oder ohne Riegel?** Ein Riegel bräuchte ein neues Prüfmittel mit Anlass (Grundsatz 5) und misst Sätze, nicht Sinn. **Empfehlung: ohne** — die Regel steht in `CLAUDE.md` 2, der Preis (sie kann unbemerkt reißen) steht dabei; der Changelog wird in Schritt 13 ohnehin neu gefasst (R15). | beantwortet |

## 5. Prüfprotokoll

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
| PU-00 | `decken.py`, `uebersicht.py --pruefen`, `nummern.py`, `bestand.py`, `pruefen.sh textprobe`, `dokumente`, `linkprobe` | 21 Decken, 0 gerissen (erst 1: R89-Kern 196 Zeichen über 160, gekürzt) · 68 Einträge, 0 ohne Grammatik, 0 ohne Ziel · 0 neue Nummern, 0 Überschneidungen gegen 18 Zweige · 0 Befunde · 0 Treffer, 143 Regeln gegriffen · 50 Dokumente, 3 688 Pfadverweise, 0 Befunde · 154 Verweise, 0 Abweichungen | 01.11.2026 |

## 6. Was dieser Schritt nicht tut

- Er streicht keine Probe, keinen Riegel und keine Regel; er verschiebt
  Umfang und Farbe (rot → hinweis). Was heute rot ist und einen Fehler in der
  Anwendung meinte, bleibt rot.
- Er fasst Stufe 2 und Produktiv nicht an. Staging misst weiter dreifach.
- Er baut die Hauptstufe nicht aus (Backlog Nr. 300 bleibt).
- Er ändert nichts an der Gerätehülle, der Verschlüsselung oder der
  Anwendung; es gibt keine Versionsstufe.
