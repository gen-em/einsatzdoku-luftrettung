# Prüfdokument R4 — Backlog-Runde 4 (Schritt 17)

*Gehört zu `Konzept-R4-Backlog-Runde-4.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock des
Konzepts. Angelegt am 26.09.2026 mit dem Konzept (Fable); die Umsetzung
füllt es je Paket mit Mittel **und** Zahl. Stand: **Umsetzung, R4-01 und
R4-02 erledigt** (26.09.2026, `claude/schritt-17-konzept-mockups-q0yjcm`); die
Konzeptphase steht in 2 und 4 als erster Block. Dieses Dokument bleibt, bis seine Prüfliste abgehakt ist
(K9); das Konzept wird nach der Freigabe des Abschlusses gelöscht.*

---

## 1. Was nicht geprüft werden konnte — und warum

Steht vor allem anderen. Was dazukommt, gehört hierher — an den Anfang.

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Der Befund ist eine Lesung, keine Messung an der Anlage.** | Die Sichtung lief nur lesend (Konzept 2.1): kein Prüfmittel gefahren, keine Probe, kein Gradle-Lauf. Jede Trefferzahl ist ein `grep` am 26.09.2026; jede Aussage „die Probe fällt" (Nr. 259: 4 von 95) stammt aus dem Eintrag oder dem Code, nicht aus einem Lauf. | Die Umsetzung misst je Paket zuerst das Ausgangsmaß (wie AR-01) und trägt die Zahl hier ein; weicht sie von 2.2 des Konzepts ab, ist das ein Befund, kein Fehler des Konzepts. |
| **Der Baum ist nach der Sichtung weitergelaufen.** | Gesichtet wurde `8e29cf0` bis `5a2a69a`; danach kamen R4-00-Commits (nur `docs/`). Kein Code hat sich geändert; wer die Umsetzung beginnt, nimmt `main` nach dem Merge von PR #93 und misst neu. | `git log --stat 5a2a69a..HEAD -- server/ tools/ android/` → leer heißt: der Befund gilt. |
| **Die Mockups sind gerendert, nicht bedient.** | M-R4-22, -23, -24 liegen als HTML und PNG bei (`konzept-r4/mockups/`); gemessen ist nur der Überlauf je Breite. Ob die Datumsfelder, die Pillen und der Schwebe-Wert der Säulen sich so bedienen lassen, zeigt erst die Anwendung (Bedienprobe in R4-23/-24). Die Handy-Nachbildung ist HTML, kein Compose — Maße in dp bei 1:1 nachgebaut, kein Emulatorbild. | Prüfliste 4, P-R4-04; Emulatorbilder mit R4-22. |
| **Ob `stroeme.py` den Baum ändert** (F-R4-17) | Nicht gefahren; nur der Kopf der erzeugten Datei gelesen. | R4-04 misst den Baum-Hash vor und nach dem Lauf. |
| **Die tatsächliche Laufzeit je zusätzlicher Bilderlauf-Breite** (Q-R4-13) | Hochgerechnet (+12 % je Breite aus 745–845 s für acht), nicht gemessen. | R4-08 nennt die Zahl nach dem ersten Lauf `neben`. |
| **Die Nebenfrage aus Nr. 170** (Q-R4-15) | Entschieden: in 17 gebaut (E-R4-25); gemessen wird sie mit R4-06. | R4-06, Bilderlauf `einsatz.php` mit manuellem Abfahrtort. |
| **Das Löschen der zwei toten Zweige** (Q-R4-01) | Die Betreiberin löscht sie selbst (E-R4-15); die Umsetzung hat nichts gelöscht. | P-R4-06; `git ls-remote --heads origin` zeigt die zwei Zweige, solange es aussteht. |
| **Ortszeit-Abfragen auf der örtlichen Anlage** (F-R4-21) | Die örtliche MariaDB hat keine Zeitzonentabellen; `CONVERT_TZ` mit `Europe/Berlin` liefert NULL. Die Anwendung nutzt es nicht; Messungen in Ortszeit sind über UTC-Zeiten nachgerechnet. | R4-16: jede Ortszeit-Zahl mit Rechenweg. |

## 2. Maschinell geprüft — Konzeptphase (R4-00)

| Schritt | Mittel | Gegenstand | Zahl |
|---|---|---|---|
| 17-00, Abschlüsse SD/AR/BV, Konzept | `python3 tools/steuerung/decken.py` | 20 Decken der Steuerungsdokumente, nach jedem Commit | **20 Decken, 0 gerissen** (Rahmenplan 459 → 464 Zeilen; längste Erledigt-Zeile 399 Zeichen) |
| dito | `python3 tools/steuerung/uebersicht.py --pruefen` | Kopfzeilen des Backlogs | **101 offene Einträge, 0 ohne Grammatik, 0 ohne gültiges Ziel** (106 vor dem SD-Abschluss; 55 mit Ziel 17) |
| SD-Abschluss | `grep -n "Auswertung ist P5\|Bevor eine Auswertung entsteht" docs/Technik.md docs/Handbuch.md` | Nr. 193, die zwei Sätze | **0 Treffer** |
| 17-00 | `git show <zweig>:docs/Backlog.md` über alle Remote-Zweige, offene PRs | Nummernkollision vor der Spanne 340–349 | **0** (höchste 337 auf `main`, 336 auf den Zweigen; 0 offene PRs) |
| jeder Push | `bash tools/pruefstand/pruefen.sh` (Stufe klein, 20 Riegel + Probe `steuerung`) | der Baum des Kopf-Commits | **0 rot, 0 nicht gemessen, 20 grün**, 56–57 s je Lauf; Bericht in der Commit-Nachricht; nichts in den Baum geschrieben |
| Befund | zwei Workflows, 13 Agenten, nur lesend | 55 Einträge mit Ziel 17 | **55 von 55 zurück, 20 bestätigt, 35 korrigiert, 0 widerlegt** (Konzept 2.1) |
| Mockups | Chromium 141 über Playwright, 1280/390/1240 px, `scrollWidth > clientWidth` | M-R4-22, -23, -24 | **5 Bilder, Überlauf 0** |
| Mockups | `validate_palette.js` (dataviz), Fläche `#FFFCFA` | die zwei Diagrammtöne `#4280E5`, `#FF8F1F` | **alle Prüfungen bestanden**; Kontrastwarnung Orange 2,23:1 → Beschriftung in Asphalt (F-P3-J) |

### 2.1 Umsetzung — je Paket

| Paket | Mittel | Gegenstand | Zahl |
|---|---|---|---|
| R4-01 | `python3 tools/steuerung/uebersicht.py --ziel 17` / `--pruefen` | offene Punkte mit Ziel 17; Grammatik und Ziel aller Kopfzeilen | **43** mit Ziel 17 (vorher 55; −5 ausgetragen, −8 umgehängt, +1 Nr. 340 — F-R4-19: das Konzept erwartete 37); **97 Einträge, 0 ohne Grammatik, 0 ohne Ziel** |
| R4-01 | `python3 tools/steuerung/decken.py` | 20 Decken der Steuerungsdokumente (Rahmenplan Fassung 135) | **20 Decken, 0 gerissen** |
| R4-01 | `python3 tools/quelltext/bestand.py` | Form der Anleitungen, Nummern in beiden Backlog-Dateien | **0 Befunde** (die Anleitung `tools/steuerung/` bei 40 von 40 Zeilen) |
| R4-01 | Playwright 1.56, `launch()` je Motor, vor/nach `apt-get remove` der vier Pakete | Nr. 301: startet Firefox und WebKit ohne die vier Bibliotheken? | mit: **3 / 3**; ohne: Chromium und Firefox starten, **WebKit nicht**; wieder geholt: **3 / 3** (F-R4-20) |
| R4-01 | SQL über die Anlage, UTC-Zeiten mit Hand in Ortszeit umgerechnet | Nr. 275: aktive Diensttage des Demo-Kontos mit Einsätzen auf zwei Kalendertagen | **2 von 20** (`id` 7 und 19, beide `ground`, beide Zeitumstellung) — bestätigt F-R4-05 |
| R4-01 | `git show` der drei Commits, `grep` | Belege für 172 (`3258916`, Median aus 5 in `tools/proben/wartung/probe.php`), 295 (`d519fac`, `schritt_statistik()`), 216 (0 doppelte Trennlinien in `docs/`) | **3 von 3 Belegen** vorhanden |

| R4-02 | `python3 tools/quelltext/bestand.py` | die dreizehn Regeln, darunter `anlage` und `tor` neu | **0 Befunde**; 21 Proben ohne Anlage, 10 PHP-Einstiege, 18 Dateien in der Ladekette (7 unter `server/`); **20 von 20** Riegeln im Tor |
| R4-02 | dito, mit nachgebautem Fehler von Web 21.1.2 (`doku_lib.php` lädt `db.php`; `--riegel "anker=…"` aus `pruefung.yml` genommen), danach zurückgesetzt | fängt die neue Regel den Fall, der im Pull Request rot war? | **2 Befunde**: `anlage-db` (`anker.php → doku_lib.php → db.php`), `tor-fehlt` (`anker`); nach dem Zurücksetzen 0; `git status` für `server/` und `.github/` leer |
| R4-02 | `python3 tools/quelltext/bestand.py --selbstprobe` | je Befundstelle ein eingebauter Fehler | **155 Fälle, 0 Fehlschläge** (130 mit Fehler, 25 Gegenproben); **93 von 93** Befundstellen (vorher 141 / 85) |
| R4-02 | `bash tools/sandbox/hochfahren.sh`, einmal mit entfernter Registerzeile `2026_09_25_zentrale_stammdaten`, dann wieder eingesetzt | Schemafrage | heute **0 Migrationen offen**, rc 0; gestellt: **rot, rc 1**, Kennung `(skip)` und Weg; zurück: 0, rc 0 |
| R4-02 | `bash tools/pruefstand/pruefen.sh --stufe klein --datei android/handy/build.gradle.kts` mit beiseitegelegter `platforms/android-37.0` und einer leeren `android-36` | Erkennung der Ausbaustufe aus `compileSdk` | **„Ausbaustufe android fehlt (Plattform android-37 aus compileSdk)"**, `android-bau=nicht-gemessen`, rc 1; mit 37.0 erkannt (Nachbau der Zeile) |
| R4-02 | `bash tools/sandbox/aufbauen.sh android` nach der Änderung | holt es nur noch 37.0? | rc 0; `platforms/`: **nur `android-37.0`**; Nachweis 5 Stücke ok (Plattform 37.0, Build-Tools 36.0.0, JDK 21, Spiegel, `cmdline-tools` 23.0) |

## 3. Im Browser geprüft

Nichts — die Konzeptphase ändert keine Seite. Die Anlage lief (HTTP 200 auf
`login.php`, Fassung 21.1.3) nur für den Prüfstand.

## 4. Prüfliste für die Betreiberin

Je Punkt: Bedienweg, erwartetes Ergebnis, woran ein Scheitern zu erkennen
ist. Die Umsetzung hängt je Paket ihre Punkte an (P-R4-06 ff.).

| Nr. | Bedienweg | Erwartung | Scheitern |
|---|---|---|---|
| P-R4-01 | ☑ 26.09.2026 (E-R4-14 bis -26) — PR #93 lesen: Stufe 1 grün (Bericht im Kopf-Commit), Konzept Abschnitt 3.2 — die offenen Q-R4 mit „alles wie empfohlen" oder einzeln beantworten. | Antworten liegen vor der Umsetzung vor (K6). | Ein Paket beginnt mit einer offenen Q — dann hält die Umsetzung an (H-R4-05). |
| P-R4-02 | ☑ 26.09.2026 (E-R4-14, Merge PR #93) — **Freigabe des Konzepts** (ein Satz im PR oder im Chat), dann Merge von PR #93. | Rahmenplan auf `main`: Fahrplanzeile 17 „Konzept", Erledigt-Zeilen SD, AR, BV; `uebersicht.py --ziel 17` 55. | Stufe 1 rot am Kopf-Commit (Bericht fehlt oder falscher Baum) — dann kein Merge, Instanz beauftragen. |
| P-R4-03 | ☑ 26.09.2026 (`claude/schritt-17-konzept-mockups-q0yjcm`) — Nach dem Merge: Umsetzungsinstanz mit Opus auf eigenem Zweig starten (Konzept 4, Reihenfolge = Paketnummer). | Erster Commit `R4-01: …`; Zweig nach jedem Paket gepusht. | Ein Paket ohne Push, ein Statusblock ohne Fortschreibung. |
| P-R4-04 | ☑ 26.09.2026 (E-R4-26) — Die drei Mockups ansehen (`docs/konzepte/konzept-r4/mockups/*.png`, Regeln in der LIESMICH dort) und freigeben oder Änderungen nennen — Q-R4-16, spätestens vor R4-22. | Gebaut wird erst nach der Freigabe; Änderungen werden vorher ins Mockup eingearbeitet. | Ein Bild im Bilderlauf, das so in keinem freigegebenen Mockup steht. |
| P-R4-05 | Nach dem Deploy von R4-15 (Migration): als Administratorin `update.php` aufrufen, Betrieb → Updates ansehen. | Migration `days.created_at` gelaufen; Wartungsmodus geht aus. | Wartungsmodus bleibt an; Statuszeile nennt eine ausstehende Migration. |
| P-R4-06 | GitHub → Branches: `claude/nice-lovelace-snlo8m` und `claude/pk05-tor-umbauen` löschen (E-R4-15). Vorher: Beide tragen nichts Ungemergtes (F-R4-04). | Die zwei Zweige sind weg; `git ls-remote --heads origin` nennt sie nicht mehr. | Einer steht noch da — oder ein anderer fehlt. |

## 5. Grenzen der benutzten Prüfmittel

- `decken.py` und `uebersicht.py` messen Form (Decken, Grammatik, Ziel),
  nicht Inhalt: Ein Eintrag mit richtiger Kopfzeile und falscher Zuordnung
  ist für sie grün. Die Zuordnung hat die Sichtung geprüft (Konzept 2.2).
- Der Prüfstand der Stufe klein fährt bei einer reinen `docs/`-Berührung
  nur die 20 Riegel und die Probe `steuerung`; er belegt nicht, dass der
  Befund stimmt — nur, dass die Buchführung die Riegel hält.
- Die Sichter haben Trefferzahlen mit `grep` gemessen; ein Muster, das eine
  Schreibweise nicht kennt, zählt zu wenig (Nr. 36: erst 20, dann 16
  Dateien — zwei Zählweisen). Die Gegenprüfung hat jede Zahl ein zweites
  Mal gerechnet, mit eigenem Muster; wo beide abweichen, steht die zweite.

## 6. Was aus der Runde offen bleibt

*(wird von der Umsetzung gefüllt: Punkte, die nach 17 ein neues Ziel
tragen, und die Reste je Paket)*
