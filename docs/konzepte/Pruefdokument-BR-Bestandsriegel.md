# Prüfdokument BR — Der Bestandsriegel

*Zum Konzept `Konzept-BR-Bestandsriegel.md`. Beantwortet „was muss **ich**
noch tun?" — das Protokoll „ist es belegt?" steht im Konzept, Abschnitt 9.
Stand: BR-01 bis BR-03 erledigt, 24.09.2026.*

---

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Der Riegel in Stufe 1 auf GitHub** | Stufe 1 läuft erst beim Pull Request. Örtlich gefahren sind derselbe Läufer, dieselbe Selbstprobe und dieselbe Gegenlesung (`bericht.py lesen`); ob der Schritt im Tor mit dem Bericht übereinstimmt, zeigt erst der PR. | P-BR-01 |
| **Ob jede Anlass-Zeile den richtigen Fehler nennt** | Der Riegel misst die Zeile, nicht ihren Inhalt (E-BR-03). Zwölf Nummern sind von Agenten mit Beleg vorgeschlagen und von der Instanz gegengelesen, fünf davon einzeln nachgelesen; zehn Einträge (304–313) sind neu geschrieben. Eine zweite, unabhängige Lesung hat es nicht gegeben. | P-BR-05 |
| **Uhr-Prüfstand nach der Kürzung seiner Anleitung** | Die Befehle sind unverändert übernommen; gefahren wird `uhr-stufe1` mit dem Prüfstand vor dem PR (BR-04), weil `tools/uhr-pruefstand/` berührt ist. | P-BR-02 |
| **Die Baumsuche gegen die echte GitHub-Schnittstelle** (BR-03) | In dieser Umgebung gibt es kein `gh`. Belegt ist die Logik über die Selbstprobe mit nachgebauten Antworten (11 / 0) und die Aufrufe über `kettenaufrufe` (0 Befunde); nicht belegt ist, dass die echte Antwort die Form hat, die die Selbstprobe annimmt. Die Felder sind dieselben, die die Bash-Schleife vorher las. | P-BR-07, P-BR-08 |
| **Ein roter Riegel hält wirklich einen Merge auf** | Das hieße, einen PR mit einem eingebauten Fehler zu öffnen. Belegt ist es an zwei Stellen, die zusammen tragen: Die Selbstprobe baut je Regel einen Fehler ein (24 / 0), und das Tor liest jeden Riegel aus `pruefablauf.json` gegen den Bericht (`--alle-riegel`, P-PK-29 bis -32). | P-BR-03 (freiwillig) |

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
| BR-03 | `pruefen.sh alle` | elf Quelltextprüfungen; 56 Python-Werkzeuge, 2 Dokumente | **11 von 11** |
| BR-03 | `baumsuche.py --selbstprobe` | nachgebaute Antworten: Treffer, fremder Baum, roter Job, Fenster, Ereignisfilter, Fehler der Schnittstelle | **11 / 0** |
| BR-03 | `kettenaufrufe/pruefen.py --probe`, dann ohne Schalter | nach F-BR-17 auch Befehlsersetzungen am Zeilenende | **18 von 18**; **84** Aufrufe, 0 Befunde, 2 ungeprüft |
| BR-03 | `proben.sh wiederherstellung` normal / ohne Zusatzkonten | Teil 11 meldet „nicht gemessen" rot (F-BR-04) | **111 / 0**, rc 0 / **rc 1** |
| BR-03 | `aufbauen.sh web` | `cmark-gfm` in der Ausbaustufe | **11 von 11** Stücken |

Im Browser ist nichts zu prüfen: BR berührt weder `server/` noch eine
Android- oder Uhr-Oberfläche.

---

## 3. Prüfliste

- [ ] **P-BR-01 — Stufe 1 fährt die drei neuen Riegel und liest sie gegen.**
  *Weg:* im PR von BR den Lauf „Prüfung" → Job `Stufe 1` öffnen.
  *Erwartet:* 15 Schritte; „cmark-gfm bereitstellen" grün; „Quelltext —
  elf Prüfungen, ein Läufer" zeigt „8 von 8 Selbstproben grün" und
  „11 von 11 Prüfungen grün"; die Schritte „Python-Werkzeuge", „Backlog"
  und „Handbuch rendern" gibt es nicht mehr; „Prüfbericht gegenlesen"
  endet mit „Prüfbericht in Ordnung" und rc 0.
  *Scheitern erkennbar an:* „10 von 11" (etwa `handbuch` rc 2, weil
  `cmark-gfm` fehlt), oder „Riegel „syntax-py" steht in pruefablauf.json,
  läuft aber nicht im Tor", oder „Riegel „bestand": Bericht …, im Tor
  gemessen …".
- [ ] **P-BR-02 — Der Bericht im Kopf-Commit nennt `bestand=0`.**
  *Weg:* `git log -1` auf dem PR-Kopf.
  *Erwartet:* ein Block `Prüfstand: …` mit `bestand=0`, `syntax-py=0` und
  `handbuch=0` unter den Riegeln und `uhr=gebaut` (BR-02 berührt
  `tools/uhr-pruefstand/`).
  *Scheitern erkennbar an:* eine der drei mit 1 oder 2, eine fehlt im
  Block, oder `uhr=nicht-gemessen`.
- [ ] **P-BR-03 (freiwillig) — Ein eingebauter Fehler macht Stufe 1 rot.**
  *Weg:* auf einem Wegwerfzweig eine Zeile an `tools/kette/LIESMICH.md`
  hängen, bis sie 41 Zeilen hat, Prüfstand fahren, PR öffnen.
  *Erwartet:* Stufe 1 rot, Befund `form · tools/kette/LIESMICH.md: 41
  Zeilen, erlaubt sind 40`. *Scheitern erkennbar an:* Stufe 1 grün.
- [ ] **P-BR-04 — `kettenaufrufe` meldet einen vertippten Namen.**
  *Weg:* in `tools/pruefstand/pruefablauf.json` örtlich
  `pruefen.sh bestand` in `pruefen.sh bestnd` ändern,
  `python3 tools/kettenaufrufe/pruefen.py` fahren, zurücksetzen.
  *Erwartet:* 1 Befund „kennt den Befehl "bestnd" nicht".
  *Scheitern erkennbar an:* 0 Befunde.

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
- [ ] **P-BR-07 — „Schon gemessen?" verweist nach dem Merge.**
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
- [ ] **P-BR-08 — Das Produktionstor findet Stufe 1 beim nächsten Tag.**
  *Weg:* beim nächsten Tag `web-v…` den Lauf „Auslieferung" → Job
  `produktion`, Schritt der Freigabe.
  *Erwartet:* `freigabe.py urteil` nennt einen grünen Stufe-1-Lauf mit
  demselben Baum; die Datei `stufe1.json` ist keine leere Liste.
  *Scheitern erkennbar an:* „Kein grüner Stufe-1-Lauf auf diesem Baum" bei
  einem Stand, dessen PR grün war — das Tor schließt dann (sicher), aber
  die Suche liest falsch.
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
