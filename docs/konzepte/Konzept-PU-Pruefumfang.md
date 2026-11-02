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
Entscheidungen fielen im selben Gespräch (E-PU-01 bis -13); dieses Dokument
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
Umsetzung läuft auf **`claude/compassionate-ride-1a0uw1`**, aufgesetzt auf
`origin/main` bei `c32dd2c` (PR #125, 12b gemergt; Web 23.6.0, Uhr 4.1.1,
Android 0.18.2).
**Backlog-Spanne:** **420 bis 429** — umgebucht von 410 bis 419 beim Merge mit 12b am 01.11.2026, weil 12b dieselbe Spanne reserviert und 410, 411 vergeben hatte (E-SD-21, eingetragen mit PU-00 vor
jeder Vergabe).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **01.11.2026 — Umsetzung läuft. PU-02, -01, -03, -04, -10, -05, -06, -07, -08 erledigt; als Nächstes PU-09 (Abschluss).** |
> | Entschieden | E-PU-01 bis E-PU-19 (Abschnitt 2); E-PU-14 bis -19 während der Umsetzung (01.11.2026) |
> | Offen | keine Frage (Q-PU-03 beantwortet 01.11.2026) |
> | Umsetzung | Reihenfolge PU-02, -01, -03, -04, -10, -05, -06, -07, -08, -09 (Abschnitt 3). Erledigt: PU-00, PU-02, PU-01, PU-03, PU-04, PU-10, PU-05, PU-06, PU-07, PU-08 (07 und 08 in einem Commit, E-PU-19). |
> | Halt | keiner — 12b ist gemergt (PR #125, 01.11.2026). |
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
| E-PU-14 | **Alle dreizehn Prüfdokumente gehen in die Prüfliste** (F-PU-06): E-PU-09 und PU-08 sagten „zwölf", in `docs/konzepte/` liegen dreizehn, und F-PU-03 zählt selbst dreizehn Kürzel auf. | Betreiberin, 01.11.2026 |
| E-PU-15 | **PU-08 läuft im selben PR wie der Rest**, nicht als eigener; die Arbeit läuft ohne Halt durch, gehalten wird nur bei einem Problem oder einer offenen Frage. Ausgegeben wird über den gepushten Zweig, keine ZIP-Datei. | Betreiberin, 01.11.2026 |
| E-PU-16 | **`tools/motor.mjs` hebt ab `neben` auf drei Engines**, nicht auf zehn Breiten (`umfang.engine_wahl`). E-PU-02 nennt nur Stylesheet und `ui.php`; wer aber den Start der Engines ändert, misst ihn in einer Engine nicht. Das Muster `motoren` sagte bis dahin „fährt alle in drei Engines (R87)" — mit R89 wäre das still auf Chromium gefallen. | Umsetzung (PU-04), 01.11.2026 — zur Bestätigung im PR |
| E-PU-17 | **Q-PU-03: Seiten über ihre eigene Datei.** Eine Seite aus `seiten.json` ist berührt, wenn ihre Datei berührt ist (Platzhalter über `umfang.platzhalter`); jede andere auslösende Datei gilt als geteilt und bringt alle Seiten; `server/version.php` löst nichts aus (`umfang.neutral`). Bilderlauf und Bedienprobe verlassen das Muster `nebenstufe` und laufen wie in `klein` über ihre Muster, beim vollen Lauf immer. Die Bedienprobe fährt alle Wege. Gilt auch für `klein`. E-PU-04 sprach von einer Liste geteilter Dateien; sie ist durch „alles, was keine Seite ist" ersetzt. | Betreiberin, 01.11.2026 |
| E-PU-18 | **Zwei Riegel mehr gegen ein falsches Erbe:** Ist `tools/pruefstand/` berührt oder eine Datei, die der Aufruf der Probe nennt (bei `proben.sh NAME` auch `tools/proben/NAME/`), erbt die Probe nicht. E-PU-13 nannte als Preis „ein unvollständiges Muster erbt fälschlich grün"; die meisten Probenwerkzeuge unter `tools/proben/` hängen an keinem Muster, ihre Änderung hätte also geerbt. | Umsetzung (PU-10), 01.11.2026 — zur Bestätigung im PR |
| E-PU-19 | **PU-07 und PU-08 in einem Commit, mit einem Prüfstandlauf.** Die neuen Sätze in `CLAUDE.md` 7 und `Pruefablauf.md` 2.1 verweisen auf `docs/konzepte/Pruefliste.md`, die erst PU-08 anlegt; ein eigener Lauf für PU-07 wäre an der Dokumentenprobe rot gewesen, und ein Platzhalter-Dokument nur dafür wäre eine Datei ohne Inhalt. | Umsetzung (PU-07), 02.11.2026 |
| E-PU-13 | **Ererbter Bericht.** Der Prüfstand darf in `neben` das Ergebnis einer Probe vom **direkten Vorgänger auf demselben Zweig** übernehmen, wenn dessen Bericht im Commit steht, die Probe dort **grün** war und der Unterschied zwischen beiden Bäumen **kein Muster dieser Probe** trifft. Der Bericht schreibt dann `NAME=ererbt:<Baum des Vorgängers>` statt einer Zahl; das Tor liest den Vorgänger-Bericht nach und prüft beides (achte Lage in `Pruefablauf.md` 5.1). **Nie geerbt werden:** was rot oder nicht gemessen war; die billigen Riegel (25 s, das Tor misst sie selbst); der Kreislauf `edbak` (Nr. 267, F-S11-48); Proben, die eine berührte Datei nur über das Auffangmuster `server/**` erreicht; alles beim vollen Lauf (E-PU-01) und in `haupt`. **Nur über eine Stufe:** Ein Bericht, der selbst erbt, vererbt nicht weiter — spätestens jeder zweite Lauf im Zweig ist wieder voll. Grund: Der letzte Nebenlauf eines Zwischen-PRs dauerte 12 205 s, und der zweite Lauf nach einer Berichtigung misst heute alles noch einmal, auch das, was die Berichtigung nicht berührt. Preis: Die Zuordnung in `pruefablauf.json` ist von Hand gepflegt; ein unvollständiges Muster erbt fälschlich grün — in `klein` gilt das heute schon. | Betreiberin, 01.11.2026 (Nachtrag) |

**Abgelehnt:** Werkzeugarbeit ohne eigenes Konzept zu führen (Vorschlag B4
vom 01.11.2026) — Werkzeugschritte bleiben, wo sie sinnvoll sind, eigene
Schritte mit Konzept.

## 3. Arbeitspakete

Seriell; jedes Paket endet mit Prüfstand, Statusblock und Push. **Gefahren
wird PU-02, -01, -03, -04, -10, -05, -06, -07, -08, -09** — PU-02 zuerst,
weil PU-03 bis -06 darauf aufbauen, PU-10 nach PU-04 (so stand es schon in
den Zeilen; die Tabelle bleibt nach Nummern geordnet).

| Paket | Inhalt | Dateien |
|---|---|---|
| PU-00 | Buchführung: dieses Dokument, Fahrplanzeile Schritt 20, R89, Verlaufszeile, Spanne 410–419. **Erledigt 01.11.2026.** | `docs/konzepte/`, `docs/Rahmenplan*.md`, `docs/Backlog.md` |
| PU-01 | **Zehnerregel und `--voll`** (E-PU-01): `auswahl.py` liest die Spanne der Nebennummern, gibt `umfang` mit Grund aus; `pruefen.sh` nimmt `--voll`; `bericht.py` schreibt `umfang=…` und liest es im Tor gegen (siebte Lage in `Pruefablauf.md` 5.1); Selbstproben je Lage. **Erledigt 01.11.2026** — die Lage heißt „Lage 7", die Werte `schmal`, `voll:haupt`, `voll:zehner`, `voll:hand`. | `tools/pruefstand/`, `docs/Pruefablauf.md` |
| PU-02 | **Ergebnisart `hinweis`** (E-PU-05): im Lauf, im Bericht, im Tor; Selbstprobe von `bericht.py lesen`. Zuerst, weil PU-03 bis -06 darauf aufbauen. **Erledigt 01.11.2026.** | `tools/pruefstand/`, `.github/workflows/pruefung.yml` |
| PU-03 | **Engines je Berührung** (E-PU-02): `MOTOREN` in `pruefen.sh` aus Berührung, `umfang` und Stufe; `motoren=`-Zeile im Bericht und ihre Gegenlesung angepasst; Grundsatz 8 neu gefasst, `CLAUDE.md` 6, `motoren.md`. **Erledigt 01.11.2026** — die Regel steht einmal, `motoren_aus()` in `auswahl.py`, die Liste in `pruefablauf.json` unter `umfang.stil`; Prüfstand fragt `auswahl.py --motoren`, das Tor rechnet mit derselben Funktion nach; alle drei passen immer. `klein` bleibt bei Chromium, auch mit berührtem Stylesheet — E-PU-02 sagt „ab `neben`" —, außer mit `--voll`. | `tools/pruefstand/`, `docs/Pruefablauf.md`, `CLAUDE.md` |
| PU-04 | **Breiten und Seiten je Berührung** (E-PU-03, -04): `aufnehmen.mjs` mit Breitensatz je `umfang`; Liste der geteilten Dateien in `pruefablauf.json`; Bilderlauf und Bedienprobe in `neben` über `--nur` wie in `klein`, sonst alle. **Erledigt 01.11.2026** nach E-PU-17: `aufnehmen.mjs --breiten drei|fuenf|zehn`; `auswahl.py` `breiten_aus()`, `seitenwahl()`, `--bilderlauf`; Aufruf `--breiten {breiten} {seiten}`; Bericht `breiten=`, `seiten=`, Tor in Lage 6; zwei neue Muster für die Werkzeuge (F-PU-08). | `tools/screenshots/`, `tools/bedienprobe/`, `tools/pruefstand/` |
| PU-05 | **Steuerung aus dem Tor** (E-PU-06): `steuerung` aus `riegel` in `pruefablauf.json` und aus `pruefung.yml`; im Prüfstand als `hinweis`; `tools/kettenaufrufe/` und `bestand` Regel `tor` folgen von selbst. **Erledigt 02.11.2026:** `steuerung` trägt `"hinweis": true` und läuft über ihr Muster; Schritt, Umgebungswert und `--riegel` in `pruefung.yml` gestrichen (Stufe 1: 16 Schritte); `CLAUDE.md` 2, `Rahmenplan.md` 9, `Backlog.md`-Kopf, `decken.py`, `Pruefablauf.md` 2.3 und 6.11 nachgezogen. `nummern` bleibt, wie sie war (nicht Gegenstand von E-PU-06). | `tools/pruefstand/`, `.github/workflows/`, `tools/steuerung/LIESMICH.md` |
| PU-06 | **Bestandsriegel** (E-PU-07): Erzeuger vor dem Riegel im Prüfstand, `hinweis` bei Änderung; `form`-Decke als `hinweis`; Selbstproben nachgezogen. **Erledigt 02.11.2026:** neue Probe `erzeugen` (`bericht.py erzeugen`: `design.py schreiben` und die Tabelle in `Pruefablauf.md` 4, jede Änderung als `HINWEIS:`), Muster `erzeugte-tabellen` (`server/**`, die beiden Dokumente, `design.py`, `tools/pruefstand/**`), `auswahl.py` stellt sie vor die Riegel; `bestand.py` übergeht `form-zeilen` im Rückgabewert und druckt `HINWEIS: form · …`. Kein Riegel ist gestrichen, keine Regel. | `tools/pruefstand/pruefen.sh`, `tools/quelltext/bestand.py`, `tools/erzeugen/` |
| PU-07 | **Prüfdokument-Form und Changelog-Regel** (E-PU-08, -10): `CLAUDE.md` 2 und 7, `Pruefablauf.md`; ohne Riegel (E-PU-12). **Erledigt 02.11.2026** (mit PU-08, E-PU-19): `CLAUDE.md` 2 Punkt 2 (fünf Sätze, keine Prüfzahlen, der Preis ohne Riegel), 7 (Prüfdokument = drei Teile, höchstens 20 Punkte; nach dem Merge in die Prüfliste; Prüfzahlen im Prüfprotokoll des Konzepts), `Pruefablauf.md` 2.1. | `CLAUDE.md`, `docs/Pruefablauf.md` |
| PU-08 | **Prüfliste** (E-PU-09): `docs/konzepte/Pruefliste.md` aus den zwölf Prüfdokumenten (Stand 01.11.2026: rund 300 offene Punkte), Doppelungen gestrichen; Rahmenplan 6.1 nachgezogen; Einzeldokumente gelöscht. Reine Doku-Arbeit, kann nach PU-07 auch als eigener PR laufen. **Erledigt 02.11.2026** (im selben PR, E-PU-15): 179 Punkte in zwölf Bereichen — 174 mit P-Nummer, 5 aus Kette II —, Zellen wörtlich, ausgezogen von einem Einmalwerkzeug gegen Rahmenplan 6.1 als Maßstab; dreizehn Dokumente gelöscht (letzter Stand `8a8144f`); Rahmenplan 6.1 und 6.2 (17 Verweise), `README.md`, `Bedrohungsmodell.md`, `android/LIESMICH.md` nachgezogen; eine Ausnahmezeile „Früher" für die Erledigt-Tabelle. | `docs/konzepte/`, `docs/Rahmenplan.md` |
| PU-09 | **Doku und Abschluss:** `Pruefablauf.md` 2 bis 5 und 6.11, `Sandbox-Setup.md`, `tools/pruefstand/LIESMICH.md`; Prüfdokument in der neuen Form; Prüfstand; PR. | `docs/`, `tools/pruefstand/LIESMICH.md` |
| PU-10 | **Ererbter Bericht** (E-PU-13): `auswahl.py` findet den Vorgänger-Bericht im Zweig und rechnet den Unterschied gegen dessen Baum; `pruefen.sh` lässt erbfähige Proben aus und meldet je Probe „ererbt von <Baum>"; `bericht.py` schreibt `ererbt:` und liest es im Tor gegen (achte Lage, Selbstproben: Vorgänger fehlt, war rot, Muster berührt, Vorgänger erbt selbst, Kreislauf, Riegel); `Pruefablauf.md` 5 und 5.1. Läuft nach PU-04, weil es `umfang` und die Seitenwahl voraussetzt; PU-09 nimmt die Doku mit. **Erledigt 01.11.2026:** `erbfehler()` und `erbschaft()` in `auswahl.py` sind die eine Regel, `--ererbt` fragt sie im Prüfstand (Vorgänger HEAD, Unterschied zum Arbeitsbestand), `bericht.py` Lage 8 im Tor (Vorgänger erster Elter); `zerlegen()` liegt jetzt in `auswahl.py`. Doku gleich mit: `Pruefablauf.md` 5.1 und neuer Abschnitt 5.4. | `tools/pruefstand/`, `docs/Pruefablauf.md` |

**Ersparnis, wenn alles steht** (geschätzt aus den Zahlen in 1.1): je
Nebenfassung ohne Stylesheet-Berührung rund 45 Minuten (Engines) plus mehr
als die Hälfte des verbleibenden Bilder- und Bedienlaufs; je Phase 9 von 15
roten Bestandsläufen weniger; je Prüfdokument die Hälfte des Textes; je Berichtigung nach einem
Nebenlauf der zweite Volllauf (zuletzt 12 205 s) auf die berührten Proben.
Gemessen wird das in PU-09 am ersten vollen, am ersten schmalen und am
ersten erbenden Lauf.

## 4. Befunde

| Nr. | Gewicht | Fundort | Befund | Folge |
|---|---|---|---|---|
| F-PU-01 | mittel | `tools/quelltext/bestand.py`; Prüfdokumente seit 24.09.2026 | **Der Bestandsriegel hat in fünf Wochen 15-mal rot gemeldet, 14-mal wegen einer Formalie** (1.2). Neun davon waren eine nicht neu erzeugte Tabelle. | E-PU-07 |
| F-PU-02 | mittel | `Pruefablauf.md` 3, Tabelle „Stufe" | **Die Nebenstufe fährt den vollen Umfang bei jeder Nebenfassung**, obwohl die Zuordnung Datei → Probe (Abschnitt 4) für alles andere längst je Berührung wählt. Bilder- und Bedienlauf waren die Ausnahme. | E-PU-02 bis -04 |
| F-PU-03 | niedrig | `docs/konzepte/` | **Zwölf Prüfdokumente mit rund 300 offenen Prüfpunkten liegen nebeneinander** (01.11.2026: AR 15, BV 8, DM 6, P5c 44, PK 42, R17 44, R4 23, RW 5, S11 44, SR 15; BR, Kette II und SD ohne nummerierte Punkte). Viele betreffen dieselben Wege (Anmeldung, Sicherung, Zweitfaktor). | E-PU-09 |
| F-PU-04 | niedrig | `docs/CHANGELOG.md` | **144 000 geänderte Zeilen im Changelog seit September** — dort landen Prüfzahlen und Werkzeugbefunde, die ins Konzept gehören. | E-PU-10 |
| F-PU-05 | mittel | `tools/pruefstand/auswahl.py`, Prüfdokumente | **Der Umfang wird gegen `main` bestimmt, nicht gegen den letzten grünen Bericht im Zweig.** Nach einer Berichtigung fährt ein Zweig mit Nebenfassung die ganze Nebenstufe noch einmal, auch für einen Satz Doku; die Prüfdokumente zeigen das Muster „erster Lauf n rot, behoben, zweiter Lauf" bei S11, R17 und P5c. Der Nebenlauf des letzten Zwischen-PRs dauerte 12 205 s. | E-PU-13 |
| F-PU-07 | mittel | `tools/pruefstand/pruefen.sh`, `Pruefablauf.md` 3 | **`klein` hat nie „berührte Seiten" gemessen.** Die Tabelle sagte es seit PK-03, `aufnehmen.mjs` nannte `--nur` — aber der Prüfstand übergab den Schalter nie, und der Bilderlauf fuhr alle 86 Seiten (gemessen im Lauf zu PU-03: 522 s in Chromium, drei Breiten). E-PU-04 setzte auf etwas, das es nicht gab. | Q-PU-03, E-PU-17 |
| F-PU-08 | niedrig | `tools/pruefstand/pruefablauf.json` | **Keine Berührung von `tools/screenshots/` oder `tools/bedienprobe/` führte zu ihrer Probe** (außer `wege/registrieren.mjs`). Wer den Bilderlauf änderte, fuhr ihn nur, wenn zufällig eine Seite berührt war. Für PU-10 wiegt das schwerer: Ein fehlendes Muster erbt grün. | zwei Muster `bilderlauf-werkzeug`, `bedienprobe-werkzeug`. Das zweite stand zuerst auf `tools/bedienprobe/**` und traf damit den eingecheckten Bericht der Bedienprobe (`ausgabe/bericht.*`) — jeder Lauf hätte den nächsten ausgelöst; im Trockenlauf gesehen, der Lauf abgebrochen, jetzt `*.mjs` und `wege/**`. |
| F-PU-09 | niedrig | `docs/Pruefablauf.md` 2.3 | **Der Zählbefehl für die Schritte von Stufe 1 zählte über feste Zeilennummern** (`NR>=75 && NR<231`) und brach seit einer Verlängerung des Jobs vor dem letzten Schritt ab: Er ergab 16, es waren 17. Gefunden beim Nachzählen nach PU-05. | Befehl zählt jetzt an den Jobgrenzen (`/^  pruefung:/` bis `/^  schema:/`); 16 nach PU-05 |
| F-PU-10 | niedrig | F-PU-03 | **„Rund 300 offene Punkte" waren 179.** F-PU-03 zählte die nummerierten Punkte der Prüflisten samt den erledigten; offen laut Rahmenplan 6.1 waren 174 mit P-Nummer und 5 aus Kette II. | Zahl in PU-08 berichtigt |
| F-PU-11 | niedrig | P-BR-03 | **Ein offener Prüfpunkt prüfte, was PU-06 abgeschafft hat:** „eine 41. Zeile macht Stufe 1 rot". | nicht in die Prüfliste übernommen, dort begründet |
| F-PU-06 | niedrig | `docs/konzepte/` | **Dreizehn Prüfdokumente, nicht zwölf.** E-PU-09 und PU-08 zählten zwölf; die Liste in F-PU-03 hat dreizehn Kürzel, und dreizehn Dateien liegen da. | E-PU-14 |

## 4a. Fragen an die Betreiberin

| Nr. | Frage | Stand |
|---|---|---|
| Q-PU-01 | **Beantwortet 01.11.2026: fünf, E-PU-11.** **Vier oder fünf Breiten im schmalen Bilderlauf?** `Design.md` 7 hat vier Schwellen (720, 1024, 1200, 1600). Vier Breiten knapp darüber (768, 1024, 1200, 1600) messen die Schwellen; eine Handybreite darunter (390) fehlt dann, und auf dem Handy liegt die meiste Bedienung. **Empfehlung: fünf** — 390, 768, 1024, 1200, 1600. | beantwortet |
| Q-PU-03 | **Beantwortet 01.11.2026: Seiten über Seitendatei, E-PU-17.** **Was heißt „berührte Seiten", wenn `klein` es nicht kennt (F-PU-07)?** Drei Wege: (1) Seiten über ihre eigene Datei, alles andere geteilt → alle (Empfehlung); (2) nur Breiten, Seiten später; (3) eine Liste geteilter Dateien wie im Konzept — eine Bibliothek ohne Listeneintrag brächte dann null Seiten. | beantwortet |
| Q-PU-02 | **Beantwortet 01.11.2026: ohne, E-PU-12.** **Changelog-Regel mit oder ohne Riegel?** Ein Riegel bräuchte ein neues Prüfmittel mit Anlass (Grundsatz 5) und misst Sätze, nicht Sinn. **Empfehlung: ohne** — die Regel steht in `CLAUDE.md` 2, der Preis (sie kann unbemerkt reißen) steht dabei; der Changelog wird in Schritt 13 ohnehin neu gefasst (R15). | beantwortet |

## 5. Prüfprotokoll

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
| PU-02 | `bericht.py lesen --selbstprobe`; `auswahl.py --selbstprobe`; `ergebnis()` aus `pruefen.sh` in einer Probeschale | 22 Lagen, 0 Fehlschläge (16 rot, 6 grün; neu: 5c, 4b, 4c, 5d) · 41 Lagen, 0 (neu: `hinweis` nur `true`, kein Riegel trägt es) · 4 Fälle: grün mit `HINWEIS:`-Zeile → Hinweis, grün ohne → ok, rot → ROT, rot mit `"hinweis": true` → Hinweis | 01.11.2026 |
| PU-02 | `bestand.py` (Gegenprobe, ungewollt) | 1 Befund `form-zeilen`, 41 Zeilen in `tools/pruefstand/LIESMICH.md` — F-PU-01 noch einmal, am eigenen Paket; gekürzt, dann 0 | 01.11.2026 |
| PU-02 | Prüfstand `klein` | 24 grün, 0 rot, 0 nicht gemessen, 0 Hinweis, 99 s; Bericht in `01c4e9b7`, örtlich gegengelesen (`bericht.py lesen`: in Ordnung, 26 Zahlen) | 01.11.2026 |
| PU-01 | `auswahl.py --selbstprobe`; `bericht.py lesen --selbstprobe`; `kettenaufrufe`; `bestand` | 53 Lagen, 0 (neu 12: Zehnergrenze erreicht, überschritten, nicht erreicht, `main` jünger, haupt, Migrationsregel, klein an der Grenze, `--voll`) · 28 Lagen, 0 (neu 7, 7b bis 7f) · 0 Befunde · 0 Befunde | 01.11.2026 |
| PU-01 | Prüfstand `klein` | 24 grün, 0 rot, 0 nicht gemessen, 0 Hinweis, 63 s, `umfang=schmal`; Bericht in `bccf0041`, örtlich gegengelesen (in Ordnung, 27 Zahlen) | 01.11.2026 |
| PU-03 | `auswahl.py --selbstprobe`; `bericht.py lesen --selbstprobe`; `bestand.py` samt Selbstprobe; `kettenaufrufe` | 62 Lagen, 0 (neu 9: `stil` in der echten Datei, neben mit und ohne Stil, `ui.php`, voll, haupt, klein mit Stil, klein mit `--voll`, fremdes Stylesheet) · 33 Lagen, 0 (6d neu gefasst, neu 6e bis 6i) · 0 Befunde, Selbstprobe 177 Fälle / 0 · 0 Befunde | 01.11.2026 |
| PU-03 | Prüfstand `klein` | 41 grün, 0 rot, 0 nicht gemessen, 0 Hinweis, 1 844 s (`motoren.md` berührt → alle Browserproben, Chromium; Bilderlauf 522 s über alle Seiten, F-PU-07); Bericht in `7084a5a1`, örtlich gegengelesen (in Ordnung, 45 Zahlen) | 01.11.2026 |
| PU-04 | `auswahl.py --selbstprobe`; `bericht.py lesen --selbstprobe`; `aufnehmen.mjs --selbstprobe` und `--breiten acht`; `bestand`; `kettenaufrufe`; `pruefen.sh --trocken --stufe neben --datei server/index.php --datei server/version.php` | 81 Lagen, 0 (neu 19: jeder Platzhalter hat eine Datei, jede Datei liegt da, index.php → 11 Seiten, Platzhalter, Bibliothek/Skript/Endpunkt/Werkzeug geteilt, version.php und Doku lösen nichts aus, voll, `nebenstufe` ohne die beiden, Breitensätze, `motor.mjs`) · 39 Lagen, 0 (neu 6j bis 6o) · 24 von 24, unbekannter Satz rc 2 · 1 Befund `tabelle` (Tabelle 4 nicht neu erzeugt — F-PU-01 zum zweiten Mal), neu erzeugt, dann 0 · 0 · „bilderlauf: 11 Seiten, bedienprobe: alle" | 01.11.2026 |
| PU-04 | Prüfstand `klein` | 41 grün, 0 rot, 0 nicht gemessen, 0 Hinweis, 1 856 s; Bilderlauf `breiten=drei seiten=alle` (Werkzeug berührt, also geteilt); Bericht in `97210547`, gegengelesen (in Ordnung, 47 Zahlen). Ein erster Lauf ist nach Sekunden abgebrochen (Muster `bedienprobe-werkzeug` zu breit, F-PU-08) | 01.11.2026 |
| PU-04 | Handlauf `aufnehmen.mjs --breiten fuenf --nur 10-tagesuebersicht,13-einsatzformular` (Chromium, örtliche Anlage) | 2 Seiten, Breiten 390/768/1024/1200/1600, 10 Einzelbilder, Überlauf 0, rc 0, 98 s — der Weg über `--nur` und den Satz `fuenf`, den der Prüfstandslauf selbst nicht fuhr | 02.11.2026 |
| PU-10 | `auswahl.py --selbstprobe`; `bericht.py lesen --selbstprobe`; `bestand`; `kettenaufrufe`; `pysyntax`; `dokumente` | 103 Lagen, 0 (neu 22: grün geerbt, Doku-Satz, Vorgänger fehlt/rot/nicht genannt, eigenes Muster, `nebenstufe`, Vorgänger erbt selbst, `edbak`, Riegel, `klein`, voll, Vorgänger voll, anderer Baum, Prüfstand berührt, Werkzeug aus dem Aufruf, Engines, Bilderlauf-Seiten, `nach`) · 48 Lagen, 0 (neu 8 bis 8i) · 0 · 0 · 69 Werkzeuge, 0 · 0 (erst 1: ein erfundener Pfad `tools/proben/NAME/` in 5.4) | 02.11.2026 |
| PU-10 | Durchstich in einer Wegwerfkopie mit echtem Git: Vorgänger mit `neben`-Bericht (Web 23.7.0), danach ein Satz in `Handbuch.md` | Prüfstand: 4 von 37 Proben erben (`spurprobe`, `ingestprobe`, `jobprobe`, `gpxprobe`), `kreislauf-edbak` nicht; mit berührter `spur_lib.php` 0. Tor auf dem Folgecommit: in Ordnung; derselbe Bericht mit `kreislauf-edbak=ererbt:…` rot mit Grund | 02.11.2026 |
| PU-10 | Prüfstand `klein` | 41 grün, 0 rot, 0 nicht gemessen, 0 Hinweis, 0 ererbt (`klein` erbt nicht), 1 831 s; Bericht in `6de7fe0f`, gegengelesen (in Ordnung, 47 Zahlen) | 02.11.2026 |
| PU-05 | `decken.py --selbstprobe`; `uebersicht.py --selbstprobe`; `auswahl.py --selbstprobe`; `bestand`; `kettenaufrufe`; Schritte von Stufe 1 am Quelltext | 23 / 0 · 7 / 0 · 103 / 0 · erst 2 Befunde (`form-zeilen` 41 in `tools/steuerung/LIESMICH.md`, `tabelle` nicht neu erzeugt — F-PU-01 zum dritten und vierten Mal), dann 0; Regel `tor`: 22 von 22 Riegeln übergeben · 0 · 16 (F-PU-09) | 02.11.2026 |
| PU-05 | Prüfstand `klein` | 41 grün, 0 rot, 0 nicht gemessen, 0 Hinweis, 1 833 s; `steuerung=0` jetzt als Probe, nicht als Riegel; Bericht in `921e66ce`, gegengelesen (in Ordnung, 47 Zahlen) | 02.11.2026 |
| PU-06 | `bericht.py erzeugen` zweimal; `bestand.py --selbstprobe`; Gegenversuch mit einer 41. Zeile in `tools/steuerung/LIESMICH.md`; `auswahl.py`/`bericht.py --selbstprobe`; `kettenaufrufe`; `dokumente` | erster Lauf: 1 geändert (die neue Musterzeile in `Pruefablauf.md` 4 — der Erzeuger trug seine eigene Zeile ein), zweiter: 0 · 177 Fälle, 0, dazu 2 Gegenproben (Decke allein rc 0, neben einem anderen Befund rc 1) · rc 0, eine Zeile `HINWEIS: form · … 41 Zeilen` · 103 / 0, 48 / 0 · 0 · 0 | 02.11.2026 |
| PU-06 | Prüfstand `klein` | 42 grün, 0 rot, 0 nicht gemessen, 0 Hinweis, 1 841 s; `erzeugen=0` (vor den Riegeln, 2 s), `bestand=0`; Bericht in `8a8144f5`, gegengelesen (in Ordnung, 48 Zahlen) | 02.11.2026 |
| PU-07/08 | Auszug der offenen Punkte (Einmalwerkzeug, Scratchpad) gegen Rahmenplan 6.1; `decken.py`; `uebersicht.py --pruefen`; `dokumente`; `linkprobe`; `textprobe`; `bestand` | 175 Punkte erkannt, 0 nicht gefunden (nach zwei Berichtigungen des Lesers: R17 setzt seine Tabelle nach einer Leerzeile fort; dieselbe Kennung steht auch in Messprotokollen — es gewinnt die Zeile mit Bedienweg), davon 1 erledigt (P-S11-28) → 174 + 5 · 21 Decken, 0 gerissen · 63 Einträge, 0 · 0 Befunde (3 719 Verweise, 36 Ausnahmen) · 152 Verweise, 0 · erst 1 Treffer (`hausform`: „Betreiberin" in der neuen README-Zeile), dann 0 · 0 | 02.11.2026 |
| PU-00 | Prüfstand `klein` | 24 grün, 0 rot, 0 nicht gemessen, 69 s; Bericht am PR-Kopf (`0e7faf8`) | 01.11.2026 |
| PU-00 | `decken.py`, `uebersicht.py --pruefen`, `nummern.py`, `bestand.py`, `pruefen.sh textprobe`, `dokumente`, `linkprobe` | 21 Decken, 0 gerissen (erst 1: R89-Kern 196 Zeichen über 160, gekürzt) · 68 Einträge, 0 ohne Grammatik, 0 ohne Ziel · 0 neue Nummern, 0 Überschneidungen gegen 18 Zweige · 0 Befunde · 0 Treffer, 143 Regeln gegriffen · 50 Dokumente, 3 688 Pfadverweise, 0 Befunde · 154 Verweise, 0 Abweichungen | 01.11.2026 |

## 6. Was dieser Schritt nicht tut

- Er streicht keine Probe, keinen Riegel und keine Regel; er verschiebt
  Umfang und Farbe (rot → hinweis). Was heute rot ist und einen Fehler in der
  Anwendung meinte, bleibt rot.
- Er fasst Stufe 2 und Produktiv nicht an. Staging misst weiter dreifach.
- Er baut die Hauptstufe nicht aus (Backlog Nr. 300 bleibt).
- Er ändert nichts an der Gerätehülle, der Verschlüsselung oder der
  Anwendung; es gibt keine Versionsstufe.
