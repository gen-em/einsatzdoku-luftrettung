# Prüfdokument BR — Der Bestandsriegel

*Gehörte zu `Konzept-BR-Bestandsriegel.md` — **mit dem Abschluss am
24.09.2026 gelöscht**, letzter Stand in der Historie unter `d4e96e6`; die
Erledigt-Zeile steht in `Rahmenplan.md` 8, die offene Prüfliste in 6.
Beantwortet „was muss **ich** noch tun?" — das Protokoll „ist es belegt?"
stand im Konzept, Abschnitt 9. Dieses Dokument bleibt, bis seine Prüfliste
abgehakt ist. Stand: gemergt als PR #85 (`ce42213`), abgeschlossen
24.09.2026; belegt sind P-BR-01, -02, -04, -07 und -13.*

---

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| ~~**Der Riegel in Stufe 1 auf GitHub**~~ | **Belegt nach dem Merge** (PR-Lauf 36037964804, P-BR-01, -13). Bis dahin liefen derselbe Läufer, dieselbe Selbstprobe und dieselbe Gegenlesung nur örtlich; ob der Schritt im Tor mit dem Bericht übereinstimmt, konnte erst der PR zeigen. | P-BR-01 |
| **Ob jede Anlass-Zeile den richtigen Fehler nennt** | Der Riegel misst die Zeile, nicht ihren Inhalt (E-BR-03). Zwölf Nummern sind von Agenten mit Beleg vorgeschlagen und von der Instanz gegengelesen, fünf davon einzeln nachgelesen; zehn Einträge (304–313) sind neu geschrieben. Eine zweite, unabhängige Lesung hat es nicht gegeben. | P-BR-05 |
| **Uhr-Prüfstand nach der Kürzung seiner Anleitung** | Die Befehle sind unverändert übernommen; gefahren wird `uhr-stufe1` mit dem Prüfstand vor dem PR (BR-04), weil `tools/uhr-pruefstand/` berührt ist. | P-BR-02 |
| **Die zweite Fassung des Runbooks 6.12** (BR-04) | Die Gegenlesung durch Befolgen hat die **erste** Fassung geprüft und fünf Lücken und drei falsche Aussagen gefunden (F-BR-19). Die zweite Fassung beantwortet jede davon, ist aber nicht noch einmal von einer unabhängigen Instanz befolgt worden. | P-BR-09 |
| **Die Baumsuche gegen die echte GitHub-Schnittstelle** (BR-03) | In dieser Umgebung gibt es kein `gh`. Belegt ist die Logik über die Selbstprobe mit nachgebauten Antworten (11 / 0) und die Aufrufe über `kettenaufrufe` (0 Befunde); nicht belegt ist, dass die echte Antwort die Form hat, die die Selbstprobe annimmt. Die Felder sind dieselben, die die Bash-Schleife vorher las. **Seit dem Merge zur Hälfte belegt:** „Schon gemessen?" hat den PR-Lauf gefunden (P-BR-07); das Produktionstor zeigt sich erst beim nächsten Tag. | P-BR-08 |
| **Ein roter Riegel hält wirklich einen Merge auf** | Das hieße, einen PR mit einem eingebauten Fehler zu öffnen. Belegt ist es an zwei Stellen, die zusammen tragen: Die Selbstprobe baut je Befundstelle einen Fehler ein (seit BR-05 140 / 0), und das Tor liest jeden Riegel aus `pruefablauf.json` gegen den Bericht (`--alle-riegel`, P-PK-29 bis -32). | P-BR-03, P-BR-11, P-BR-12 (freiwillig) |
| **Eine fünfte Gegenprüfrunde** (BR-05) | Nicht gefahren, nach dem Abbruchkriterium aus E-BR-22: Runde 4 fand keine Randschreibweise mehr, sondern Lücken im Umfang; was realistisch und mindestens „mittel" war, ist behoben. Ob eine fünfte Runde gegen die Fassung mit diesen Behebungen etwas fände, ist **nicht gemessen** — vier Runden haben 29, 30, 27 und 21 echte Mängel gefunden, und die Zahl fällt nicht auf null, weil jede Runde den neuen Umfang mitprüft. | — |
| **Die Grenzen aus Runde 4** (BR-05) | Benannt, nicht gebaut (Konzept 7, Kopf von `bestand.py`): `getopt()` mit Optionen in einer Konstanten, Unterordner von `tools/quelltext/`, ein Hilfsmodul mit dem Schalter als Datum (rot zu Unrecht), Konstanten in `auswahl.py` mit einer Muster-id, `Nr. 12a` und „bis", eine Endmarke unter der Tabelle (rot). Keine davon ist heute im Bestand. | — |

---

## 2. Maschinell geprüft

| Paket | Mittel | Gegenstand | Zahl |
|---|---|---|---|
| BR-01 | `python3 tools/quelltext/bestand.py --selbstprobe` | je Regel ein eingebauter Fehler, dazu Gegenproben | **22 Fälle, 0 Fehlschläge** (19 Fehler, 3 Gegenproben, 6 von 6 Regeln abgedeckt) |
| BR-01 | `bash tools/quelltext/pruefen.sh --selbstprobe` | die sechs Selbstproben des Läufers | **6 von 6** |
| BR-01 | `python3 tools/quelltext/bestand.py` | 17 Ordner, 24 Anleitungen, 20 Proben, 2 lose Dateien | **57 Befunde** — rot, gewollt bis BR-02 |
| BR-01 | `python3 tools/kettenaufrufe/pruefen.py --probe`, dann ohne Schalter | Aufrufe in 4 Workflows und 44 Proben | **16 von 16**; 76 Aufrufe, 0 Befunde, 2 ungeprüft |
| BR-02 | `python3 tools/quelltext/bestand.py` | derselbe Bestand nach der Bereinigung; 25 Anleitungen mit 972 Zeilen | **0 Befunde** |
| BR-02 | `bash tools/proben/proben.sh alle` auf frischer Anlage | alle 20 Proben, darunter die vier mit `konfig_stellen.php` aus `tools/sandbox/` | **20 von 20 grün, 0 ausgelassen** |
| BR-02 | `php -l`, `node --check` | die 20 Probendateien mit neuer Kopfzeile | 0 Fehler |
| BR-02 | `vergleiche_edbak()` gegen die Referenz, mit und ohne eingesetztes `_spur_index` | die Zusage in `Backup-Format.md` | 1 Meldung / 0 |
| BR-02 | Pfadprüfung über die normativen Dokumente | Verweise `tools/…` auf Dateien und Ordner | 26 tote → 0 |
| BR-03 | `bestand.py --selbstprobe` | sieben Regeln, dazu `backlog` | **24 / 0** |
| BR-03 | `pruefen.sh --selbstprobe` | die Selbstproben des Läufers, neu `pysyntax` (3/0) und `handbuch` (5/0, darunter kaputte Kodierung, F-BR-16) | **8 von 8** |
| BR-03 | `pruefen.sh alle` | elf Quelltextprüfungen; 56 Python-Werkzeuge (gemessen vor `baumsuche.py`; der Commit von BR-03 trägt 57, nachgezählt in BR-05), 2 Dokumente | **11 von 11** |
| BR-03 | `baumsuche.py --selbstprobe` | nachgebaute Antworten: Treffer, fremder Baum, roter Job, Fenster, Ereignisfilter, Fehler der Schnittstelle | **11 / 0** |
| BR-03 | `kettenaufrufe/pruefen.py --probe`, dann ohne Schalter | nach F-BR-17 auch Befehlsersetzungen am Zeilenende | **18 von 18**; **84** Aufrufe, 0 Befunde, 2 ungeprüft |
| BR-03 | `proben.sh wiederherstellung` normal / ohne Zusatzkonten | Teil 11 meldet „nicht gemessen" rot (F-BR-04) | **111 / 0**, rc 0 / **rc 1** |
| BR-03 | `aufbauen.sh web` | `cmark-gfm` in der Ausbaustufe | **11 von 11** Stücken |
| BR-04 | Gegenlesung von 6.12 durch Befolgen, eigene Instanz im Worktree | zwei Attrappen (Quelltextprüfung als Riegel, Probe am Muster), je ein Schritt weggelassen | erste Fassung: **5** Sprünge, **4** weitere Dateien, **3** falsche Aussagen; eingehängt grün: `bestand` 0, `kettenaufrufe` 86/0, Selbstproben 9/9, `alle` 12/12; **4** Auslassungen meldet niemand |
| BR-04 | `pruefstand/pruefen.sh --stufe klein` in einem Git-Worktree, vor und nach der Behebung, dazu `bericht.py` durch `sys.exit(3)` ersetzt | ob ein Lauf ohne Bericht grün sein kann (F-BR-18) | vorher **rc 0** ohne Bericht; nachher Bericht mit Baum, rc 0; Gegenprobe **rc 1** |
| BR-04 | erster voller Prüfstand, dann der neue Aufruf von `uhr-stufe1` von Hand | ob die Uhr-Probe überhaupt läuft (F-BR-20, seit PK-03 als F-PK-21 bekannt) | vorher **rot nach 0 s**; von Hand **99 übersetzt, 0 fehlgeschlagen, 0 ohne Gerätedatei**, rc 0, 9 min 56 s |
| BR-04 | `kettenaufrufe` alt gegen neu, `--probe` | Ketten mit `&&` (F-BR-21) | alt 1 Aufruf / 0 Befunde, neu 2 / 1; **20 von 20**; **85** Aufrufe, 0 Befunde, 2 ungeprüft |
| BR-04 | `git merge-tree` gegen den P5c-Zweig (`f7729c2`), dann `bestand.py --wurzel` über den zusammengeführten Baum | was P5c nach dem Aufnehmen tun muss (Konzept 8.1) | **4** Dateien mit Konflikt; danach **1** Befund (Anlass der Protokollprobe); Zählung 40 Zeilen / 0 über der Decke; `kettenaufrufe` 86 / 0 |
| BR-05 | adversariale Gegenprobe in vier Runden, je fünf Angreifer in eigener Kopie, jeder Fund unabhängig nachgestellt | die vier neuen Regeln in vier Fassungen | echt: **29, 30, 27, 21**; Runde 4 davon 7 realistisch und ≥ „mittel" (5 Ursachen) — behoben; dazu 4 von 5 Resten früherer Runden; der Rest benannt (1) |
| BR-05 | `bestand.py --selbstprobe` | je Befundstelle ein eingebauter Fehler, dazu Gegenproben | **140 Fälle, 0 Fehlschläge** (117 / 23), **85 von 85** Befundstellen, 22 s |
| BR-05 | Mutationsmessung: jede Befundstelle einzeln stumm, je die ganze Selbstprobe | ob die Selbstprobe jede Stelle bemerkt | **83 von 83** Mutanten rot, 0 überlebt; Kontrolle 140 / 0 (Runde 4: 6 von 81 überlebten) |
| BR-05 | `bestand.py` am Bestand und an einer Kopie mit fünf eingebauten Fehlern | elf Regeln; Fläche, Aufruf der Selbstproben, Code-Spanne mit `\|`, alte Riegelzeile, `&shy;` | **0 Befunde**; je genau der erwartete Befund, unverändert 0 |
| BR-05 | `pruefen.sh --selbstprobe`, `pruefen.sh alle` | nach F-BR-23 mit der Textprobe | **9 von 9**; **11 von 11** |
| BR-05 | `kettenaufrufe --probe`, dann ohne Schalter; `auswahl.py --selbstprobe`, `--abdeckung` | `NAMEN` zeilenweise; der tote Pfad (F-BR-24) | **21 von 21**, 85 / 0 / 2 ungeprüft; **27 / 0**, Abdeckung vorher wie nachher (0 / 87) |
| Abschluss | Q-BR-14: `auswahl.py --selbstprobe`, dazu gegen die Zuordnung ohne den neuen Pfad | `server/api/backup_spuren*.php` im Muster `spur` | **28 / 0**; ohne den Pfad **1 rot** (der neue Fall) |
| Abschluss | `bestand.py` vor und nach `erzeugen-doku` | die Tabelle in 4 nach der Änderung der Zuordnung | vorher **1 Befund** `tabelle-abweichung`, danach **0** |
| Abschluss | PR-Lauf 36037964804, Job `Stufe 1` (Log gelesen über die GitHub-Schnittstelle; der Direktabruf des Log-Archivs ist in dieser Umgebung gesperrt) | P-BR-01, -13 | 15 Schritte; „9 von 9", „11 von 11", `FEHL` 0-mal; „Prüfbericht in Ordnung … 21 Zahlen" |
| Abschluss | Push-Lauf 36038550112 auf `main` | P-BR-07 | Verweis auf Lauf 36037964804, `Stufe 1` übersprungen, **17 s** |
| Abschluss | `python3 -W error` über alle versionierten Python-Dateien | ungültige Escape-Folgen (Nr. 318) | **2 von 60**, beide in `tools/`, beide älter als BR |

Im Browser ist nichts zu prüfen: BR berührt weder `server/` noch eine
Android- oder Uhr-Oberfläche.

---

## 3. Prüfliste

- [x] **P-BR-01 — Stufe 1 fährt die drei neuen Riegel und liest sie gegen.**
  *Weg:* im PR von BR den Lauf „Prüfung" → Job `Stufe 1` öffnen.
  *Erwartet:* 15 Schritte; „cmark-gfm bereitstellen" grün; „Quelltext —
  elf Prüfungen, ein Läufer" zeigt „9 von 9 Selbstproben grün" und
  „11 von 11 Prüfungen grün"; die Schritte „Python-Werkzeuge", „Backlog"
  und „Handbuch rendern" gibt es nicht mehr; „Prüfbericht gegenlesen"
  endet mit „Prüfbericht in Ordnung" und rc 0.
  *Scheitern erkennbar an:* „10 von 11" (etwa `handbuch` rc 2, weil
  `cmark-gfm` fehlt), oder „Riegel „syntax-py" steht in pruefablauf.json,
  läuft aber nicht im Tor", oder „Riegel „bestand": Bericht …, im Tor
  gemessen …".
  **Erfüllt 24.09.2026, PR-Lauf 36037964804** (Kopf `7f4106a`): Job
  `Stufe 1` grün mit 15 Schritten, darunter „cmark-gfm bereitstellen — für
  die Quelltextprüfungen `handbuch` und `bestand`" (10 s); „Python-Werkzeuge",
  „Backlog" und „Handbuch rendern" gibt es nicht mehr. Im Log „9 von 9
  Selbstproben grün", „11 von 11 Prüfungen grün", `FEHL` 0-mal, und
  „Prüfbericht in Ordnung: Stufe klein, Baum 73ff930…, Konfiguration web,
  21 Zahlen." **Nebenfund:** zwei `SyntaxWarning` über der Zeile von
  `pysyntax` — Nr. 318.
- [x] **P-BR-02 — Der Bericht im Kopf-Commit nennt `bestand=0`.**
  *Weg:* `git log -1` auf dem PR-Kopf.
  *Erwartet:* ein Block `Prüfstand: …` mit `bestand=0`, `syntax-py=0` und
  `handbuch=0` unter den Riegeln, `uhr-stufe1=0` und `uhr=gebaut` (BR-02
  berührt `tools/uhr-pruefstand/`), `android-bau=0` und `handy=gebaut`
  (BR-02 berührt `android/LIESMICH.md`).
  *Scheitern erkennbar an:* eine der drei mit 1 oder 2, eine fehlt im
  Block, oder `uhr=nicht-gemessen`.
  **Erfüllt 24.09.2026:** Der Kopf `7f4106a` trägt `bestand=0`,
  `syntax-py=0`, `handbuch=0`, `uhr-stufe1=0`, `android-bau=0`,
  `handy=gebaut` und `uhr=gebaut` (Baum `73ff930`).
- [ ] **P-BR-03 (freiwillig) — Ein eingebauter Fehler macht Stufe 1 rot.**
  *Weg:* auf einem Wegwerfzweig eine Zeile an `tools/kette/LIESMICH.md`
  hängen, bis sie 41 Zeilen hat, Prüfstand fahren, PR öffnen.
  *Erwartet:* Stufe 1 rot, Befund `form · tools/kette/LIESMICH.md: 41
  Zeilen, erlaubt sind 40`. *Scheitern erkennbar an:* Stufe 1 grün.
- [x] **P-BR-04 — `kettenaufrufe` meldet einen vertippten Namen.**
  *Weg:* in `tools/pruefstand/pruefablauf.json` örtlich
  `pruefen.sh bestand` in `pruefen.sh bestnd` ändern,
  `python3 tools/kettenaufrufe/pruefen.py` fahren, zurücksetzen.
  *Erwartet:* 1 Befund „kennt den Befehl "bestnd" nicht".
  *Scheitern erkennbar an:* 0 Befunde.
  **Erfüllt 24.09.2026** (örtlich, auf `ce42213`): 85 Aufrufe, **1 Befund**
  „tools/quelltext/pruefen.sh kennt den Befehl "bestnd" nicht", rc 1;
  zurückgesetzt 0 Befunde.

- [ ] **P-BR-05 — Stichprobe: Passt die Anlass-Zeile zur Probe?**
  *Weg:* drei Proben wählen (Vorschlag: `ingest`, `geraete`, `anteil`), die
  Zeile `Anlass: Nr. …` im Kopf lesen, den Backlog-Eintrag dazu öffnen.
  *Erwartet:* Der Eintrag beschreibt den Fehler, den die Probe misst oder
  messen müsste (bei 311–313 das Risiko). *Scheitern erkennbar an:* Ein
  Eintrag, der eine andere Stelle oder einen Fehler der Probe selbst
  beschreibt — dann Nummer berichtigen.
- [ ] **P-BR-06 — Die zehn nachgetragenen Einträge lesen.**
  *Weg:* `docs/Backlog.md`, *Erledigt*, Nr. 304 bis 313.
  *Erwartet:* Je Eintrag Fehler oder Risiko, Fundstelle, Fassung und
  Nachweis. *Scheitern erkennbar an:* eine Fundstelle, die es nicht gibt,
  oder ein Eintrag, den die Betreiberin anders zugeordnet hätte.
- [x] **P-BR-07 — „Schon gemessen?" verweist nach dem Merge.**
  *Weg:* nach dem Merge von BR den Lauf „Prüfung" auf `main` öffnen, Job
  „Schon gemessen?".
  *Erwartet:* Er findet den grünen PR-Lauf mit demselben Baum („gemessen
  in Lauf …") und Stufe 1 wird übersprungen — das Verhalten wie vor BR,
  jetzt über `baumsuche.py`.
  *Scheitern erkennbar an:* „Schon gemessen? Nein — kein grüner PR-Lauf mit
  Baum … unter den letzten 30" oder „— Liste der PR-Läufe nicht abrufbar",
  obwohl der PR grün war. Dann misst Stufe 1 selbst (sicher, aber rund eine
  Stunde), und die Antwort der Schnittstelle hat eine andere Form, als die
  Selbstprobe annimmt.
  **Erfüllt 24.09.2026, Push-Lauf 36038550112** (Merge `ce42213`): „Stufe 1:
  1 Läufe geholt, 1 mit Baum 73ff930, 0 Baum-Abrufe gescheitert (Fenster
  30)", dann „Messen: nein — Baum `73ff930` bereits gemessen: Lauf
  36037964804 (`pull_request`, `7f4106a`), Job `Stufe 1` grün"; `Stufe 1`
  und `Schema gegen …` übersprungen, Lauf **17 s**.
- [ ] **P-BR-08 — Das Produktionstor findet Stufe 1 beim nächsten Tag.**
  *Weg:* beim nächsten Tag `web-v…` den Lauf „Auslieferung" → Job
  `produktion`, Schritt der Freigabe.
  *Erwartet:* `freigabe.py urteil` nennt einen grünen Stufe-1-Lauf mit
  demselben Baum; die Datei `stufe1.json` ist keine leere Liste.
  *Scheitern erkennbar an:* „Kein grüner Stufe-1-Lauf auf diesem Baum" bei
  einem Stand, dessen PR grün war — das Tor schließt dann (sicher), aber
  die Suche liest falsch.
- [ ] **P-BR-09 — Die zweite Fassung des Runbooks trägt.** *Weg:* beim
  nächsten neuen Prüfmittel (etwa auf P5c) nur `Pruefablauf.md` 6.12
  befolgen.
  *Erwartet:* `bestand`, `kettenaufrufe` und `pruefen.sh --selbstprobe`
  beim ersten Lauf grün, und der Prüfstand wählt das Mittel aus.
  *Scheitern erkennbar an:* ein Schritt, den 6.12 nicht nennt und den erst
  Stufe 1 oder der Prüfstand meldet (etwa `KeyError` in der Auswahl) —
  dann gehört er in 6.12.
- [ ] **P-BR-10 — Der Prüfstand ohne Bericht ist rot.** *Weg:* freiwillig;
  `git worktree add` eines beliebigen Stands **außerhalb** des
  Repositoriums, dort `bash tools/pruefstand/pruefen.sh --stufe klein
  --datei docs/Backlog.md`.
  *Erwartet:* ein Block `Prüfstand: klein · Baum …` und rc 0.
  *Scheitern erkennbar an:* `CalledProcessError` im Abschnitt „Bericht"
  und trotzdem rc 0 — dann ist F-BR-18 zurück.
- [ ] **P-BR-11 (freiwillig) — Ein vergessener `SELBST`-Eintrag macht Stufe 1
  rot** (BR-05). *Weg:* auf einem Wegwerfzweig in
  `tools/quelltext/pruefen.sh` einen Namen aus `SELBST=(…)` streichen,
  Prüfstand fahren, PR öffnen.
  *Erwartet:* Stufe 1 rot mit „`selbst` · tools/quelltext/<name> wertet
  --selbstprobe aus, aber <name> steht nicht in SELBST".
  *Scheitern erkennbar an:* Stufe 1 grün — dann läuft die Regel im Tor
  nicht mit.
- [ ] **P-BR-12 (freiwillig) — Ein Muster, das seine Fläche verliert, macht
  Stufe 1 rot** (BR-05). *Weg:* auf einem Wegwerfzweig in
  `tools/pruefstand/pruefablauf.json` aus dem Muster `uhr` den Pfad
  `tools/uhr-pruefstand/**` streichen, die Tabelle in `Pruefablauf.md` 4
  neu erzeugen, Prüfstand fahren, PR öffnen.
  *Erwartet:* Stufe 1 rot mit „`ablauf` · … tools/uhr-pruefstand/… gehört
  zur Fläche uhr, aber kein Muster ab Stufe klein wählt dafür uhr-stufe1
  aus". *Scheitern erkennbar an:* Stufe 1 grün — dann wäre jede spätere
  Berührung von `tools/uhr-pruefstand/` eine Sackgasse (`uhr=nicht-gemessen`).
- [x] **P-BR-13 — Die Laufzeit der Selbstprobe im Tor.** *Weg:* im PR von
  BR, Job `Stufe 1`, Schritt „Quelltext — elf Prüfungen", die Zeile der
  Selbstprobe von `bestand`.
  *Erwartet:* „140 Fälle, 0 Fehlschläge … 85 von 85 Befundstellen
  gefallen", örtlich rund 20 s. *Scheitern erkennbar an:* ein Fall
  `[FEHL]`, der örtlich grün war — dann liest ein Werkzeug im Tor anders
  (`cmark-gfm`- oder PHP-Fassung), und das gehört als Befund ins Konzept.
  **Erfüllt 24.09.2026, PR-Lauf 36037964804:** „140 Fälle, 0 Fehlschläge
  (117 mit eingebautem Fehler, 23 Gegenproben; 85 von 85 Befundstellen
  gefallen)", im Tor rund **16 s** (örtlich 22 s); kein `[FEHL]`. Der ganze
  Quelltext-Schritt 28 s.
---

## 4. Grenzen der Prüfmittel

- **`bestand` misst Form, nicht Inhalt.** Ob eine Anleitung stimmt, ob der
  Anlass zur Probe passt und ob ein Werkzeug überflüssig ist, sieht er
  nicht (E-BR-03). Eine Anlass-Zeile mit einer existierenden, aber falschen
  Nummer ist für ihn grün.
- **„Gerufen" heißt „genannt".** Die Inventur sucht `tools/<ordner>/` als
  Zeichenkette in vier Quellen; ein Kommentar dort zählt so viel wie ein
  Aufruf.
- **Die Pfadprüfung über die Dokumente ist kein Werkzeug**, sondern eine
  Schleife in der Sitzung (Protokoll BR-02). Sie ist nicht im Tor; ob sie
  eines werden soll, steht in Abschnitt 7 des Konzepts.
- **Die Einstiegsdatei einer Probe kommt aus `proben.sh`.** Wer eine Probe
  an `RUF` vorbei startet, entzieht sie dem Riegel — der Ordner ohne Eintrag
  ist dann aber ein Befund.
- **`bestand` sieht, was die echten Werkzeuge sehen** (E-BR-22) — die
  Tabelle wie `cmark-gfm` sie rendert, PHP wie `token_get_all` es zerlegt,
  die Listen wie bash sie ausgibt. Rendert GitHub anders als die örtliche
  `cmark-gfm`-Fassung, misst er die örtliche.
- **„Die Selbstproben laufen" heißt „der Aufruf steht da"** (`selbst-tor`).
  Gesucht wird `pruefen.sh --selbstprobe` in einem Workflow (außer in
  Kommentarzeilen) oder in einem Aufruf der Ablaufdatei; ob der Schritt
  hinter einer Bedingung nie läuft, sieht er nicht.
- **„Die Nummer gibt es" heißt „eine Zeile beginnt mit ihr".** `bestand`
  liest jede Zeile in `Backlog.md`, die mit Zahl und Punkt beginnt, als
  Nummer — auch ein umbrochenes Datum oder eine Aufzählung. Eine
  Anlass-Nummer, die es nur als solche Zeile gibt, ist für ihn belegt
  (gemessen von der Gegenlesung, BR-04).
