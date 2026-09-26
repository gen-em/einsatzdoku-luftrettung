# Prüfdokument R4 — Backlog-Runde 4 (Schritt 17)

*Gehört zu `Konzept-R4-Backlog-Runde-4.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Statusblock des
Konzepts. Angelegt am 26.09.2026 mit dem Konzept (Fable); die Umsetzung
füllt es je Paket mit Mittel **und** Zahl. Stand: **Umsetzung, R4-01 bis
R4-06 erledigt**, Web 21.1.5 (26.09.2026, `claude/schritt-17-konzept-mockups-q0yjcm`); die
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
| **Die Abfahrtort-Zeile auf einem echten Bestand** (R4-06, E-R4-25) | Gemessen nur am Demo-Konto der örtlichen Anlage (drei Einsätze mit „Manueller Ort", einer ohne). Ob ein Einsatz mit der Regel „Manueller Ort", aber **ohne Adresse** (nur Koordinaten, etwa aus einem Import) vorkommt, ist nicht gemessen — er bekäme keine Zeile, wie der Einsatzort ohne Adresse auch. | P-R4-08. |
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
| R4-02 | `bash tools/pruefstand/pruefen.sh` über den Baum des Commits `519ded8` (steht erst nach dem Commit fest und deshalb hier, eine Zeile später) | 20 Riegel, `kettenaufrufe`, `steuerung`, **`android-bau`** (berührt: `android/LIESMICH.md`) | **0 rot, 0 nicht gemessen, 21 grün**, 504 s, davon Android-Bau **466 s**, `handy=gebaut`; der erste Lauf davor war `android-bau=nicht-gemessen` (F-R4-22) |
| R4-03 | `python3 tools/steuerung/nummern.py --selbstprobe` | gestellte Doppelungen in einem Wegwerf-Ursprung (offener, gemergter, fortgeschriebener Zweig; Nummer in Erledigt; eigener Zweig gepusht; kein `origin/main`) | **8 Fälle, 0 Fehlschläge** |
| R4-03 | `python3 tools/steuerung/nummern.py` (holt mit `git fetch --prune`) | neue Nummern dieses Arbeitsbaums gegen `origin/main` und fünf weitere Remote-Zweige | **1 neue (340), 6 Zweige, 0 Überschneidungen** |
| R4-03 | `auswahl.py --stufe klein --datei docs/Backlog.md --nur-proben`; `--selbstprobe`; `--abdeckung` | wird `nummern` bei einer Backlog-Berührung gewählt? | gewählt; **36 / 0**; **0 ohne Muster** |
| R4-03 | `Pruefablauf.md` 6.12 ausschließlich befolgt (P-BR-09) | trägt die zweite Fassung des Runbooks? | **ein Fund**: Schritt 6 ließ die Stufenregel doppelt stehen, `bestand` rot (`tabelle-kopie`); berichtigt, danach 0; `kettenaufrufe` 0; `pruefen.sh --selbstprobe` 11 / 11 |
| R4-03 | `bash tools/pruefstand/pruefen.sh` über den Baum des Commits `ab007e9` | 20 Riegel, `kettenaufrufe`, `android-bau`, `steuerung`, **`nummern`** | **0 rot, 0 nicht gemessen, 22 grün**, 42 s; `nummern` 5 s |
| R4-04 | `auswahl.py --selbstprobe`, `--abdeckung` | das Feld `demo` (nur `true`; Bedienprobe und Bilderlauf tragen es; jede Probe damit braucht die Anlage) | **39 Lagen, 0 Fehlschläge** (vorher 36); **0 ohne Muster** |
| R4-04 | die vier Aufrufe aus `pruefablauf.json`, einmal von Hand | `android-kontraste`, `-farbabgleich`, `-bildmarken`, `-stroeme` | `kontraste.py` **30 Paare, 0 Befunde**, Selbstprobe **5 / 5**; `farbabgleich.py` **18 / 17 Token, 0 Abweichungen, 0 eigene**; `bildmarken.sh pruefen` **0 Abweichungen**; `stroeme.py pruefen` **5 Ströme, 0 Abweichungen**; danach `git status android/` **leer** (F-R4-17) |
| R4-04 | `bestand`, `bestand --selbstprobe`, `kettenaufrufe` | Schlüssel `demo`, neue Proben und Muster, Tabelle in `Pruefablauf.md` 4 neu erzeugt | **0 Befunde**; **155 / 0**; **0** |
| R4-04 | `bash tools/pruefstand/pruefen.sh --stufe neben` über den Baum des Commits `403f819` (Bericht im Commit) | 20 Riegel, `kettenaufrufe`, `steuerung`, `nummern`, `android-bau` und die **vier Android-Proben** | **0 rot, 0 nicht gemessen, 26 grün**, 45 s — ohne Demo-Probe, weil `nebenstufe` nur `server/**` trifft |
| R4-04 | **Abnahme:** `pruefen.sh --stufe neben --datei server/demo_lib.php`, derselbe Baum; vorher Demo-Marke seit 2 780 s fällig | trifft ein Demo-Reset eine Probe? | **46 grün, 0 rot, 0 nicht gemessen**, 1 632 s; Marke vor `zweitfaktorprobe`, `bilderlauf`, `bedienprobe`, `spurprobe`, `gpxprobe`, `kopplungsprobe` im Lauf; Demo-Einsätze vorher und nachher **1..106** (kein Reset), GPX-Probe grün; Marke danach wieder `1790448460` |
| R4-05 | `python3 tools/quelltext/vollstaendigkeit.py --ausfuehrlich` | sechs Prüfungen, davon neu: Selektoren, Tonübergaben mit Bedingung, Klassen-Stellen | **0 Befunde**; Selektoren **59 / 27 Dateien, 2 mit Grund**; Töne **383 geprüft, 12 ohne Literal**; Hinweise **73 → 13** |
| R4-05 | dieselbe Prüfung mit drei eingebauten Fehlern, einzeln, danach zurück (`git status server/` leer) | erfundener Selektor; Regel `pwq-2` weg; Regel `kennzahl-orange` weg | **1**, **2**, erst **0** → F-R4-26 berichtigt → **2** Befunde; zurück **0** |
| R4-05 | Playwright, `einstellungen.php?t=standorte#standorte`, Admin-Prüfkonto, `document.activeElement` | Fokus nach dem Anker (F-R4-25) | vorher **`BODY`**; nach der ersten Änderung nur über `hashchange` im Feld; nach der zweiten **`INPUT#sdbase-name`** in **Chromium, Firefox, WebKit** |
| R4-05 | `bash tools/pruefstand/pruefen.sh` über den Baum des Commits `6703455` (Bericht im Commit) | 20 Riegel, `bilderlauf`, `bedienprobe`, `kettenaufrufe`, `android-bau`, die vier Android-Proben, `steuerung`, `nummern` | **0 rot, 0 nicht gemessen, 28 grün**, 740 s (Bilderlauf 379 s, Bedienprobe 313 s); Baum `24c65ccf`; Demo-Marke vor beiden Demo-Proben geschoben, danach zurück |
| R4-06 | `php tools/quelltext/kennzeichnung.php` vor der Änderung an `einsatz.php` | Sollliste (zehn Zeilen — Name hat im Formular zwei Felder) gegen Formular und Leseansicht; Katalog | **19 von 20** Kennzeichen, **1 Befund**: „Manueller Abfahrtort … (Leseansicht) — kein Schloss an `dt:Abfahrtort`" (H-R4-04 → Web 21.1.5) |
| R4-06 | dito, nach der Änderung | dito; 9 Klartext-Freitextfelder des Katalogs | **20 von 20**, **0 Befunde**, 9 Kleinzeilen |
| R4-06 | `kennzeichnung.php --selbstprobe` | Gegenprobe; die beiden Fehler aus 19.1.0; Kommentar mit dem alten Aufruf; Diagnose und Einsatzort im Formular; Katalog ohne Kleinzeile; Blobfeld ohne Sollzeile | **8 Fälle, 0 Fehlschläge** (vor der Änderung: 1 — die Gegenprobe, wie erwartet) |
| R4-06 | Gegenproben von Hand, je einzeln, danach zurück (`git diff` nur die gewollten Zeilen) | `dtGeschuetzt('Abfahrtort')` → nackter String; `'geschuetzt' => true` am Ortsfeld `start` entfernt | je **1 Befund** mit Feld, Ansicht und Datei; zurück **0** |
| R4-06 | `kennzeichnung.php` und `--selbstprobe` mit beiseitegelegter `server/config.php` (wie Stufe 1) | läuft sie ohne Anlage? `CREW_ROLES` aus den Tokens von `db.php` | **0 Befunde**, **8 / 0**; `bestand` Regel `anlage`: 27 Proben ohne Anlage, 19 Dateien in der Ladekette, 0 Befunde |
| R4-06 | `bestand`, `kettenaufrufe`, `pruefen.sh --selbstprobe` nach 6.12 | Eintrag in `NAMEN`, `SELBST`, LIESMICH (40 von 40 Zeilen), `pruefablauf.json`, `pruefung.yml`, Tabelle in 4 neu erzeugt | **0 Befunde**, **21 von 21** Riegeln im Tor; **0**; **12 von 12** Selbstproben |

## 3. Im Browser geprüft

Die Konzeptphase änderte keine Seite; die Anlage lief (HTTP 200 auf
`login.php`, Fassung 21.1.3) nur für den Prüfstand. In der Umsetzung:

| Paket | Seite, Konto, Motor | Ergebnis |
|---|---|---|
| R4-05 | `einstellungen.php?t=standorte#standorte`, Admin-Prüfkonto; Chromium, Firefox, WebKit | Fokus vorher `BODY`, nachher **`INPUT#sdbase-name`** in allen drei |
| R4-06 | `einsatz.php` nach dem Entsperren, Demo-Konto, Chromium 1280 px; drei Einsätze mit „Manueller Ort" (398, 428, 499), einer ohne (394); Demo-Marke vorher geschoben, danach zurück | Karte Einsatz: **„Abfahrtort" mit Schloss** zwischen „Beschreibung Einsatzort" und „Diagnose" bei allen drei (Einsatz 499: „Zwischenhalt Parkplatz Talstation"); bei 394 **keine** Zeile. Konsolenfehler nur die Kartenkacheln ohne Netz (`ERR_TOO_MANY_RETRIES`) |

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
| P-R4-07 | Nach dem Deploy von Web 21.1.4 auf Staging: Einstellungen → Standorte, einen Standort anlegen und speichern (das Formular sendet an `…?t=standorte#standorte`); danach einmal „Bearbeiten". | Nach dem Speichern und nach „Bearbeiten" steht der Cursor im Feld „Name" des Standortformulars, ohne Klick. | Die Seite springt zur Karte, aber kein Feld ist aktiv — dann ist der Fokus wieder am Seitenkörper (F-R4-25). |
| P-R4-08 | Nach dem Deploy von Web 21.1.5 auf Staging: einen Einsatz **ohne GPS-Aufzeichnung** bearbeiten, Abfahrtort „Manueller Ort", eine Adresse wählen, speichern; in der Einsatzansicht entsperren. Danach denselben Einsatz auf „Standort" umstellen. | Karte Einsatz: Zeile **„Abfahrtort"** mit Schloss zwischen Beschreibung und Diagnose, darin die Adresse. Nach dem Umstellen auf „Standort" ist die Zeile weg. | Keine Zeile, eine Zeile ohne Schloss, oder die Zeile bleibt bei „Standort" stehen. |

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
