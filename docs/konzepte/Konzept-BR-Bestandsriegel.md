# Konzept BR — Der Bestandsriegel: was PK als Regel ließ, wird gemessen

**Kürzel:** `BR`. Arbeitspakete `BR-01 …`, Entscheidungen `E-BR-01 …`,
Befunde `F-BR-01 …`, Fragen an die Betreiberin `Q-BR-01 …`, Prüfpunkte
`P-BR-01 …`. Commit-Nachrichten beginnen mit dem Paket (`BR-02: …`).
**Herkunft:** Beurteilung der Prüfkette am 24.09.2026, einen Tag nach dem
Abschluss von PK-05 und RP — Auftrag der Betreiberin: „greifen die Regeln,
oder entsteht wieder Wildwuchs?" Die Messung steht in Abschnitt 2.
**Modell:** Opus, in jedem Paket. **Kein Versionssprung im Konzept** — BR
berührt `server/` voraussichtlich nicht (`CLAUDE.md` 2: eine Änderung nur an
`tools/`, `docs/` und `.github/` stuft keine der drei Zählungen hoch).
**Ablage:** dieses Konzept; Prüfdokument `Pruefdokument-BR-Bestandsriegel.md`
entsteht mit BR-01. **Zweig der Umsetzung:** `claude/br-bestandsriegel`, von
`main`; das Konzept wird dorthin übernommen. **Backlog:** BR vergibt
**Nr. 293** (der Bestand hat keinen Riegel) — nachgesehen am 24.09.2026 auf
`origin/main` (höchste 292) und auf dem P5c-Zweig (keine über 292).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **24.09.2026 — Umsetzung läuft** auf `claude/br-bestandsriegel` (von `main` `f4fe4a0`, Konzept per Vorspulen übernommen; der Konzeptzweig ist gelöscht). **BR-01 bis BR-03 erledigt** — der Riegel steht, der Altbestand ist bereinigt (**57 → 0 Befunde**, `proben.sh alle` 20 von 20), und die Reste aus PK, TB und RP sind aufgeräumt: 17 Riegel statt 14, eine Baumsuche statt zweier (Protokoll in Abschnitt 9). |
> | Entschieden | **E-BR-01 bis E-BR-10**, alle von der Betreiberin am 24.09.2026 (Abschnitt 4); Q-BR-01 bis -07 beantwortet, alle wie empfohlen (Abschnitt 5). **Aus der Umsetzung: E-BR-11 bis -16** (4.1, zur Kenntnis — keine ändert eine Entscheidung der Betreiberin). **E-BR-17 und -18** von der Betreiberin am 24.09.2026 auf Q-BR-08 bis -12 (5.1). |
> | Befunde der Umsetzung | **F-BR-07 bis -17** (2.1). Zwei davon verschieben den Rahmen: Die Handzählung aus Abschnitt 2 war zu grob (F-BR-07: 57 Befunde statt rund 35, **20** Anlass-Zeilen statt 19) — und **P5c ist weiter als angenommen** (F-BR-11: AP2 ist gebaut, E-BR-02 „Merge vor P5c AP2" ist damit überholt; was P5c nach dem Aufnehmen tun muss, ist gemessen). |
> | Offen | nichts, was BR aufhält. **Zwei Zuarbeiten von P5c** (Q-BR-05, -06) — von P5c als Nachtrag zur Fassung 2 übernommen (E-P5c-86 bis -90, Backlog 295–298 auf dem P5c-Zweig). |
> | Nächstes | **BR-04** — Runbook `Pruefablauf.md` 6.12, der Satz in 6.1, Backlog Nr. 293 nach *Erledigt*, Prüfstand mit Uhr-Bau, Gegenlesung des Runbooks, Pull Request |

---

## 1. Auftrag

Konzept PK hat den **Durchlauf** einer Änderung als Riegel gebaut — Zweigschutz,
Prüfbericht am Baum, fünf rote Lagen, „nicht gemessen ist rot" — und den
**Werkzeugbestand** als Regel belassen: Grundsatz 5 (Anlass-Zeile), die
Fünf-Abschnitte-Form mit höchstens 40 Zeilen (6.2), die Streichliste
(E-PK-03), die Ordnerzahl (E-PK-24). Kein Mittel misst eine davon. Drei Tage
nach PK-04 stehen die Zahlen unter den Regeln (Abschnitt 2).

**Ziel:** Ein Riegel in Stufe 1, der den Bestand unter `tools/` gegen die
Regeln aus `Pruefablauf.md` 6 hält — und der **auf null steht**, weil der
Altbestand im selben Konzept bereinigt wird (E-BR-01). Dazu die eine Stelle,
die PK nicht geschrieben hat: ein Runbook „Neues Prüfmittel" in
`Pruefablauf.md` 6, und die Regel, dass ein Anlass eine Backlog-Nummer ist.

**Nicht Ziel:** neue Proben; ein Umbau der Kette jenseits der drei Riegel aus
F-BR-03; die Steuerdokumente (`Backlog.md`, `Rahmenplan.md`, `CLAUDE.md`) —
dort wächst dieselbe Sorte Bestand, aber das ist ein anderes Konzept mit
einem anderen Maß.

**Warum jetzt und parallel** (E-BR-02): P5c AP1 läuft seit dem 23.09.2026 in
`server/`; erst **AP2** (Rollenprobe) fasst `tools/proben/` und
`pruefablauf.json` an. P5c mergt einmal, am Ende von elf Paketen — bis dahin
baut es einen QR-Decoder, Code-Rechner „je Sprache", eine Rollenprobe und
zwei Tor-Riegel ohne den Bestandsriegel, und alles wird hinterher
nachgetragen. Das ist der Fehler, den PK abschaffen wollte. Der Preis steht in
Abschnitt 6.

## 2. Befund (gemessen am 24.09.2026 an `main` `f4fe4a0`)

| Nr. | Was | Zahl | Regel |
|---|---|---|---|
| F-BR-01 | **Die Anlass-Zeile ist ein Versprechen ohne Deckung.** `tools/proben/LIESMICH.md` sagt: „Den Anlass je Probe (Grundsatz 5) trägt der Kopfkommentar ihrer Datei." Gemessen an den 20 Einstiegsdateien der Proben: **1 trägt sie** (`raten/probe.php`), 19 nicht. RP hat sieben davon angefasst und die Zeile nirgends nachgetragen. Nach 6.1 stünden 19 Proben auf der Streichliste — und niemand hat es gemerkt, weil es niemand zählt. | 1 von 20 | 6.1, Grundsatz 5 |
| F-BR-02 | **Die Anleitungen wachsen wieder.** PK-04 hat alle 15 auf die Form gebracht, 574 Zeilen zusammen. Heute: `tools/screenshots/` **317** Zeilen und 10 Abschnitte, `tools/zaehlung/` **154** Zeilen, 6 Abschnitte, keine Anlass-Zeile, `tools/uhr-pruefstand/` 50, `tools/kette/` 44 (37 vor TB), `tools/erzeugen/` ohne Anlass-Zeile, `tools/spaltenregister/` **ohne Anleitung**. Dazu **acht Unteranleitungen** (`referenzdatensatz/` sechs, `uhr-pruefstand/` zwei) mit zusammen **1 120** Zeilen, die PK-04 nie gezählt hat. Ordner **17** statt 15 (F-PK-28, „entschieden wird es in Teilstück 5" — dort nicht entschieden). Zwei lose Dateien direkt unter `tools/` (`motor.mjs`, `konfig_stellen.php`), die zu keinem Werkzeug gehören. | 4 von 17 Anleitungen über 40 Zeilen, 1 fehlt, 8 ungezählt | 6.2, E-PK-24 |
| F-BR-03 | **Drei Riegel leben nur im Tor.** „Backlog — keine Nummer zweimal", „Handbuch und Was-ist-NAdoku rendern" und „Python-Werkzeuge übersetzen" stehen in `pruefung.yml`, aber nicht in `pruefablauf.json` und nicht in `Pruefablauf.md`; Station B fährt sie nicht, und das Tor liest sie nicht gegen den Bericht. Dasselbe Muster wie F-PK-27, das PK schon einmal berichtigt hat. | 3 von 17 Schritten | Grundsatz 1, 2 |
| F-BR-04 | **Ein Selbstüberspringer meldet grün.** `tools/proben/wiederherstellung/probe.php` Teil 11: „WIEDERAUFNAHME: nicht messbar — zu wenige Konten offen" mit `true`. RP-03 hat nur dafür gesorgt, dass der Zweig auf frischer Anlage nicht greift; der Zweig steht, und weder Konzept noch Prüfdokument RP führen ihn. | 1 | Grundsatz 7, E-KH-12 |
| F-BR-05 | **Kleine Doppelungen aus TB und RP.** (a) Die Suche „grüner PR-Lauf mit demselben Baum" steht **zweimal** als Bash mit zwei Fenstern (30 in `pruefung.yml`, 50 in `ausliefern-lauf.yml`), eine ohne Selbstprobe. (b) Der Mechanismus `nach` (Voraussetzung einer Probe, RP-01) steht in der JSON-Bemerkung, im Docstring, im Changelog und im Backlog — nicht in `Pruefablauf.md` 3 oder 4. (c) Die Bemerkung zu `spaltenregister-wegprobe` in `pruefablauf.json` zitiert E-PK-27 als „ein eingecheckter Zugang ist ein Zugang", während 6.6 E-PK-27 als Adressregel führt — und die Wegprobe liest Konto und Passwort seit RP aus `kreislauf.py`. (d) Grundsatz 7 sagt „er bricht ab", 2.3 beschreibt den TB-Sonderfall (Auslassung mit Verweis auf `main`) — der Grundsatz nennt seine Ausnahme nicht. | 4 Stellen | Grundsatz 1, 3, 7 |
| F-BR-06 | **Der Weg für ein neues Prüfmittel steht nirgends.** Wer eines anlegt, muss ihn aus 6.1, 6.2 und dem Kopf von `pruefablauf.json` zusammenlesen; `Pruefablauf.md` 6 hat kein Runbook dafür. Und Grundsatz 5 sagt „Nummer", ohne zu sagen, welche: P5c erfüllt ihn an vier Stellen mit Befund-Kennungen (`F-P5c-40`, `-43`, `-29`, `-41`), die mit dem Konzept gelöscht werden — die Anlass-Zeile zeigt dann ins Leere. | — | 6 |

**Was hält, und deshalb nicht angefasst wird:** Ausnahmelisten seit PK-05
unverändert (+0 Zeilen in vier Dateien); keine neuen Ordner, keine neuen
Workflow-Dateien durch TB oder RP; das Tor selbst (P-PK-29 bis -32).

### 2.1 Befunde der Umsetzung

| Nr. | Was | Folge |
|---|---|---|
| F-BR-07 | **Die Handzählung war zu grob — das Mittel findet mehr.** Abschnitt 7 hat es vorhergesagt. Der erste Lauf von `bestand.py` an `f4fe4a0`: **57 Befunde** in sechs Regeln (form 22, anleitung 1, anlass 12, inventur 1, lose 1, probe 20). Alles, was Abschnitt 2 zählt, ist darunter — die vier Anleitungen über 40, die acht Unteranleitungen mit **genau 1 120** Zeilen, `spaltenregister`, `konfig_stellen.php`. Mehr sind es an drei Stellen: (a) **0 von 20** Proben tragen die Anlass-Zeile, nicht 1 — der Treffer in `raten/probe.php` war `pruef('Der erste Anlass reiht ein', …)`, ein Prüffall; (b) **12** Anleitungen ohne gültige Anlass-Zeile, nicht 2 — `bedienprobe` (`PS-2`), `integritaetswache` (`B6`), `stilvergleich` (`P0/A3`) nennen ein Kürzel statt einer Nummer (E-BR-07), `kette`, `messstand`, `referenzdatensatz`, `screenshots`, `uhr-pruefstand` stellen sie mitten in einen Satz, `proben` und `quelltext` haben keine; (c) `tools/erzeugen/` wird aus keiner der vier Quellen gerufen (Inventur). | BR-02 setzt **20** Anlass-Zeilen statt 19; E-BR-10 gilt sinngemäß (je Datei ein Agent). Die zehn zusätzlichen Anleitungen gehören zum seriellen Teil von BR-02. |
| F-BR-08 | **Die Selbstprobe fand einen Fehler im Riegel selbst.** Bei einer Probe, die über eine Funktion startet (die Versandprobe startet erst ihre Gegenstellen), nahm die erste Fassung den ersten Werkzeugaufruf im Funktionsrumpf — und hielt damit eine Gegenstelle für die Probe, sobald sie oben stand. Im echten `proben.sh` steht die Probe zufällig zuerst; die Selbstprobe stellt die Gegenstelle absichtlich davor. | Behoben: gezählt wird der **eine Aufruf im Vordergrund**; ein Hintergrundauftrag (`… &`) ist eine Gegenstelle. Ist es nicht genau einer, ist das ein Befund. |
| F-BR-09 | **`kettenaufrufe` sah die Namen der Sammelläufer nicht.** Es las Unterbefehle eines Shell-Werkzeugs nur aus `case "$befehl"`; `quelltext/pruefen.sh` und `proben/proben.sh` verteilen über `case "$fall"` und eine Namensliste. Von **30** Aufrufen mit Namen (28 in `pruefablauf.json`, 2 in den Workflows) prüfte es nur die Schalter — ein vertipptes `pruefen.sh bestnd` wäre erst im Lauf gescheitert. | Behoben in BR-01 („kennt den neuen Aufruf"): liest `NAMEN=(…)`, die Schlüssel von `RUF` und die Zweige von `case "$fall"`. Selbstprobe 13 → **16** Fälle. |
| F-BR-10 | **`Sandbox-Setup.md` 1 nennt einen Pfad, den es seit PK-04 nicht gibt:** `tools/uhr-bilder/erzeugen.sh` (heute `tools/erzeugen/uhr-bilder.sh`, der Erzeuger, für den ImageMagick und `rsvg-convert` gebraucht werden). Gefunden beim Nachsehen, warum `erzeugen` in der Inventur fehlt. | BR-02 berichtigt den Pfad. Damit steht `tools/erzeugen/` in `Sandbox-Setup.md` — mit Grund, nicht um den Riegel zu bedienen: Die Anlage braucht die zwei Pakete für genau diesen Erzeuger. |
| F-BR-11 | **P5c ist weiter als in Abschnitt 1 und 6 angenommen.** Auf `claude/p5c-mockups-konzept-4yeomf` ist **AP2 erledigt** (Web 20.39.0, 24.09.2026) — mit der Rollenprobe und einer neuen Protokollprobe unter `tools/proben/`. E-BR-02 („Merge vor P5c AP2") ist damit überholt; der Preis aus Abschnitt 6 fällt einmal an, egal wann BR mergt. **Gemessen** (Riegel gegen den P5c-Stand, 24.09.2026): P5c hat denselben Altbestand wie `main` und darüber **genau zwei eigene Punkte** — `tools/proben/protokoll/probe.php` nennt als Anlass „F-P5c-18 und F-P5c-19" (Befund-Kennungen, E-BR-07), und `tools/screenshots/LIESMICH.md` ist dort auf 321 Zeilen gewachsen, dieselbe Datei, die BR-02 auf die Form bringt (Konflikt, zeilenweise). `tools/proben/rollen/probe.php` trägt ihre Zeile schon in der Form, die BR misst (` * Anlass: Nr. 286 …`). | Keine Änderung an BR. Die zwei Punkte gehen mit dem PR an P5c (Abschnitt 8, Nachtrag). |
| F-BR-12 | **Zehn Werkzeuge haben keine Backlog-Nummer** (BR-02). Abschnitt 3 setzte voraus, die Nummer stehe „schon" in Backlog, Changelog oder Zuordnungstabelle. Für zwölf Proben stimmt das; sieben haben einen belegten Fehler, der nur als Befund-Kennung, im Changelog oder als R44 überliefert ist, und drei haben keinen verbuchten Fund. In der Gegenlesung verworfen: Nr. 178 für die Kopplungsprobe — ein Fehler der Probe selbst, und zwar in `rundlauf.mjs`, nicht in der Einstiegsdatei. | Q-BR-08 bis -12 an die Betreiberin → E-BR-17, -18; Backlog Nr. 304 bis 313 unter *Erledigt*. |
| F-BR-13 | **26 Verweise auf Werkzeugpfade, die es seit PK-04 nicht mehr gibt**, in zehn Dokumenten (`README`, `android/LIESMICH`, `Backup-Format`, `Design`, `Geraete-Eingabe`, `JSON-Vertrag`, `Lizenzen`, `Technik`, `Uhr-Layout_Regeln`, `Sandbox-Setup`) und zwei Werkzeugen (`zaehlen.php`, `proben/gpx/probe.php`); dazu drei Verweise auf Abschnitte, die es nicht mehr gibt (`Technik.md` „Die drei Läufe", `pruefstand.sh`, `Technik.md` → Kette). **Eine davon war eine Zusage ohne das genannte Mittel:** `Backup-Format.md` sagte, die Containerprobe sehe nach, ob `_spur_index` vor dem Versiegeln entfernt wird. Sie tut es nicht. | Alle berichtigt. Die Zusage trägt trotzdem — vom Kreislauf `edbak`, nachgemessen: eingesetztes `_spur_index` → 1 unerklärte Meldung, ohne 0. Den Riegel auf tote Pfade in Dokumenten auszudehnen, ist eine neue Regel und keine Entscheidung dieses Konzepts (Abschnitt 7). |
| F-BR-14 | **Der Umzug von `konfig_stellen.php` wäre still falsch geworden.** Die Datei rechnete ihren Pfad als `dirname(__DIR__) . '/server/config.php'`; unter `tools/sandbox/` zeigt das auf `tools/server/`, und der Rückweg kehrt bei fehlendem Verzeichnis **ohne Meldung** zurück — dieselbe Art Fehler wie F-PK-24. | `dirname(__DIR__, 2)`, mit Kommentar. Die vier Proben, die sie laden, laufen grün. |
| F-BR-15 | **Die Anleitungen trugen Zahlen und Schalter, die nicht mehr stimmten:** `vergleichen.py --selbstprobe` 14 Fälle statt „sieben", die Zählung 34 statt 29, `tor.py` ohne das genannte `--jobs-token`; `zaehlen.php` verwies auf `tools/wortliste/zerlegen.py`. Genau das, was E-BR-03 nicht misst — der Inhalt einer Anleitung — und was beim Kürzen ohnehin gelesen wird. | Berichtigt beim Kürzen. |
| F-BR-16 | **`cmark-gfm --validate-utf8` prüft die Kodierung nicht** (BR-03). Es ersetzt kaputte Bytes still durch U+FFFD und endet mit 0 — belegt mit einer Datei aus `\xff\xfe`: Ausgabe zweimal `357 277 275`, rc 0. Der Tor-Schritt „rendern sie überhaupt?" hätte ein Handbuch mit kaputter Kodierung grün gemeldet. Gefunden von der Selbstprobe der neuen Quelltextprüfung `handbuch` beim Umzug. | `handbuch.py` liest die Datei zusätzlich streng als UTF-8; Selbstprobe 5 von 5, darunter genau dieser Fall. |
| F-BR-17 | **`kettenaufrufe` sah Befehlsersetzungen am Zeilenende nicht** (BR-03). Eine Zeile, die mit `)` endet, galt als `case`-Muster und wurde übersprungen; eine Zeile wie `x=$(werkzeug --schalter)` war damit unsichtbar. Drei Aufrufe von `tor.py` in `ausliefern-lauf.yml` und `auslieferung.yml` gingen so nie durch die Prüfung. Dazu las es `--erster)` als Schalternamen. Gefunden, als der neue Aufruf von `baumsuche.py` einen falschen Befund bekam. | Ein `case`-Muster hat vor seinem `)` keine öffnende Klammer; die Klammer am letzten Wort wird abgelöst. 84 statt 81 Aufrufe, 0 Befunde; Selbstprobe 16 → 18. |

## 3. Arbeitspakete

| Paket | Was | Berührt | Abnahme |
|---|---|---|---|
| **BR-01 Der Riegel** | `tools/quelltext/bestand.py` — die **neunte** Quelltextprüfung, mit Selbstprobe (6.3: sie hält einen Merge auf). Sie liest den Baum unter `tools/` und misst die Regeln aus Abschnitt 4 (E-BR-03 bis -06). Eintrag als Riegel in `pruefablauf.json` (das Tor übergibt ihn dann mit `--alle-riegel`, Lage 4), Aufruf in `pruefen.sh`, Schritt in `pruefung.yml` („Quelltext — neun Prüfungen"). **Bis BR-02 gemergt ist, ist der Riegel rot** — das ist gewollt, und der Prüfbericht des Pakets sagt es; die Selbstprobe belegt, dass jede der Regeln einen eingebauten Fehler findet. Beide Pakete gehen in **einem** PR (E-BR-01: kein Zwischenstand mit Decke). | `tools/quelltext/`, `tools/pruefstand/pruefablauf.json`, `.github/workflows/pruefung.yml`, `tools/kettenaufrufe/` (kennt den neuen Aufruf) | Selbstprobe: je Regel ein eingebauter Fehler → Befund mit Namen, danach 0; `kettenaufrufe` 0 Befunde; die Zahl des Riegels am Altbestand = die Zahl aus Abschnitt 2 (Gegenprobe: das Mittel findet, was von Hand gezählt wurde) |
| **BR-02 Der Altbestand** | F-BR-01 und F-BR-02 auf null: **19 Anlass-Zeilen** in den Probenköpfen (die Nummer aus Backlog, Changelog oder der Zuordnungstabelle in `Pruefablauf.md` 4, die je Probe schon einen Anlass nennt); `screenshots`, `zaehlung`, `uhr-pruefstand`, `kette` auf die Form — was Geschichte ist, geht in die Commit-Nachricht (Grundsatz 6), was Anleitung eines Unter-Skripts ist, in dessen Kopf; `erzeugen` bekommt seine Zeile (E-BR-05); `spaltenregister` eine Anleitung; die acht Unteranleitungen (E-BR-04) und die zwei losen Dateien (E-BR-03 (4)). | die genannten Ordner unter `tools/` | `bestand.py` **0 Befunde**; jede gestrichene Zeile hat ihren Platz (Commit, Kopf) oder war Geschichte; `proben.sh alle` 20 von 20 unverändert grün |
| **BR-03 Die Reste aus PK, TB und RP** | F-BR-03: die drei Tor-Schritte bekommen je eine Zeile in `pruefablauf.json` (`riegel`) und einen Aufruf in Station B — der Backlog-Schritt als Teil von `bestand.py` (er zählt Bestand), die zwei anderen als `pruefen.sh`-Namen oder als Zeile in `pruefstand/pruefen.sh`; jeder mit Anlass. F-BR-04: der `true`-Zweig wird **rot** („nicht gemessen"), wie E-PK-46 es verlangt. F-BR-05 (b), (c), (d): je ein Satz an der richtigen Stelle. F-BR-05 (a) nach E-BR-09. | `tools/proben/wiederherstellung/`, `tools/pruefstand/`, `docs/Pruefablauf.md` 1, 3, 4; `tools/kette/` und beide Workflows (E-BR-09) | Stufe 1 auf dem PR grün mit **17** Riegeln im Bericht statt 14; die Wiederherstellungsprobe auf einer Anlage mit weniger als zwei offenen Konten **rot**, nicht grün |
| **BR-04 Das Runbook und der Abschluss** | `Pruefablauf.md` **6.12 „Ein neues Prüfmittel"**: fünf Zeilen, in der Reihenfolge, in der sie zu tun sind (Backlog-Nummer → Ordner oder Sammelordner → Anleitung in der Form → Zeile in `pruefablauf.json` → Tabelle 4 erzeugen → Riegel grün). Dazu in 6.1 der Satz **„Ein Anlass ist eine Backlog-Nummer"** (E-BR-07). `CLAUDE.md` 6 bekommt keinen neuen Absatz — nur der Verweis „Regeln für Prüfmittel: `Pruefablauf.md` 6" steht dort schon. Backlog Nr. 293 nach Erledigt, die Nummernregel im Kopf von `Backlog.md` auf die nächste freie Zahl, Changelog (Präfix `Web`, ohne Versionsstufe — wie RP), Prüfdokument, PR. Die zwei Fragen an P5c (Q-BR-05, -06) mit Antwort ins P5c-Konzept übertragen — das tut P5c selbst, BR schreibt nicht in ein fremdes Konzept. | `docs/Pruefablauf.md` 6, `docs/Backlog.md`, `docs/CHANGELOG.md`, `docs/Rahmenplan.md` 10 | Stufe 1 grün; `bestand.py` 0; Runbook von einer Instanz gegengelesen, die es nicht geschrieben hat: Kann sie daraus ein Prüfmittel anlegen, ohne eine zweite Stelle zu lesen? |

**Reihenfolge:** BR-01 → BR-02 → BR-03 → BR-04, je ein Commit mit
Paketpräfix, Push nach jedem Paket (`CLAUDE.md` 7, 8). BR-01 zuerst, weil erst
das Mittel sagt, ob die Zahl aus Abschnitt 2 vollständig war — und weil BR-02
gegen das Mittel misst, nicht gegen eine Liste im Konzept.

**Fächerung** (je Paket, `CLAUDE.md` 7, E-BR-10):

| Paket | gefächert | seriell |
|---|---|---|
| BR-01 | — | alles: ein Werkzeug, eine Datei |
| BR-02 | **die 19 Anlass-Zeilen**, je Datei ein Agent, der die Nummer aus Backlog, Changelog und `git log -- <datei>` liest und die eine Zeile setzt; danach Gegenlesung durch die Instanz (Muster PK-04/5b) | die Anleitungen — erzählender Text, ein Ton (`CLAUDE.md` 7); die Unteranleitungen und losen Dateien |
| BR-03 | — | alles: kleine Änderungen an je einer Stelle |
| BR-04 | — | alles: Text |

**Modell:** Opus in jedem Paket; kein Schritt sieht Fable vor.

## 4. Entscheidungen

Getroffen von der Betreiberin am 24.09.2026:

- **E-BR-01 — Der Riegel steht auf null; der Altbestand wird im selben
  Konzept bereinigt.** Keine Decke, kein Übergang. Gefragt war: Decke für den
  Altbestand, die senkt, wer die Dateien ohnehin anfasst — abgelehnt. Folge:
  BR-01 und BR-02 gehen in einem PR, und der P5c-Zweig nimmt beim
  Aufnehmen von `main` einen Riegel auf, der auf `main` grün ist.
- **E-BR-02 — Parallel zu P5c, auf eigenem Zweig, Merge vor P5c AP2.**
  Begründung in Abschnitt 1, Preis in Abschnitt 6.

Getroffen von der Betreiberin am 24.09.2026 mit den Antworten auf
Abschnitt 5, alle wie empfohlen:

- **E-BR-03 — Was der Riegel misst, je Ordner direkt unter `tools/`:**
  (1) eine `LIESMICH.md` mit **genau** den fünf Abschnitten aus 6.2 in dieser
  Reihenfolge und **höchstens 40 Zeilen**; (2) **eine** Zeile, die mit
  `Anlass:` beginnt und mindestens eine Backlog-Nummer (`Nr. 123`) nennt —
  oder, nur bei Erzeugern, `Anlass: entfällt — Erzeuger (E-BR-05)`;
  (3) der Ordnername kommt in `pruefablauf.json`, in einem Workflow, in
  `tools/pruefstand/pruefen.sh` oder in `Sandbox-Setup.md` vor (die Inventur:
  ein Werkzeug, das niemand ruft, ist ein Kandidat für die Streichliste — der
  Riegel sagt das, mit Namen); (4) keine lose Datei direkt unter `tools/`
  **außer `motor.mjs`** — der eine geteilte Browser-Motor von fünf
  Werkzeugen, beim Namen erlaubt; jede zweite lose Datei ist rot
  (Q-BR-03; `konfig_stellen.php` geht nach `tools/sandbox/`).
  Was der Riegel **nicht** misst: den Inhalt einer Anleitung, ob
  der Anlass zur Probe passt, ob ein Werkzeug überflüssig ist — das bleibt
  Lesearbeit, und das Runbook sagt es.
- **E-BR-04 — Unteranleitungen zählen** (Q-BR-02). Der Riegel liest jede
  `LIESMICH.md` unter `tools/` rekursiv und hält sie an dieselbe Form. Ein
  Werkzeug mit acht Unterordnern hat acht Anleitungen à 40 Zeilen oder
  weniger — nicht eine à 40 und sieben à 200.
- **E-BR-05 — Erzeuger sind Werkzeuge, keine Prüfmittel.** `tools/erzeugen/`
  fängt keinen Fehler und hat deshalb keine Nummer; seine Zeile heißt
  `Anlass: entfällt — Erzeuger (E-BR-05)`, und der Riegel kennt genau diese
  eine Form. Das ist keine Ausnahmeliste, sondern eine Aussage in der Datei,
  die man liest, wenn man sie sucht.
- **E-BR-06 — Ordnerzahl ist kein Maß mehr.** E-PK-24 sagte „15 Ordner".
  Gemessen sind 17, und F-PK-28 hat es bewusst so gelassen (`zaehlung` und
  `spaltenregister` aus Schritt 15 sind eigene Werkzeuge). Der Riegel zählt
  keine Ordner — er zählt, ob jeder Ordner die Form hat und gerufen wird. Eine
  Zahl, die man beim nächsten Werkzeug mit Grund erhöht, ist kein Riegel.
- **E-BR-07 — Ein Anlass ist eine Backlog-Nummer.** Grundsatz 5 sagt
  „Nummer"; gemeint war die dauerhafte. Eine Befund-Kennung (`F-XX-NN`) steht
  in einem Konzept, das am Ende gelöscht wird (`CLAUDE.md` 7); eine
  Entscheidung (`E-XX-NN`) ebenso, es sei denn, sie steht im Register des
  Rahmenplans. Wer ein Prüfmittel anlegt, legt zuerst die Backlog-Nummer an —
  auch für einen Befund, der im selben Paket behoben wird; die Nummer wandert
  dann nach Erledigt und bleibt zitierbar. `Pruefablauf.md` 6.1 bekommt den
  Satz. Was heute mit Kürzeln wie `PP-1`, `S2`, `O9c`, `F-S10-AP4-02` in
  `pruefablauf.json` steht, wird in BR-02 **nicht** umgeschrieben — das ist
  die Spalte „Anlass" der Zuordnung, nicht die Zeile des Werkzeugs; der
  Riegel misst die Zeile.
- **E-BR-08 — Der Riegel ist die neunte Quelltextprüfung** (Q-BR-01):
  `tools/quelltext/bestand.py`, im Läufer `pruefen.sh`, mit Selbstprobe, im
  Tor unter „Quelltext — neun Prüfungen". Kein eigener Ordner.
- **E-BR-09 — Die Baumsuche bekommt eine Stelle** (Q-BR-04): Python in
  `tools/kette/` mit Selbstprobe, das Fenster als Parameter (30 im Tor, 50
  in der Auslieferung, E-TB-09 bleibt); beide Workflows rufen sie, und
  `kettenaufrufe` prüft den Aufruf mit. Umgesetzt in BR-03.
- **E-BR-10 — Fächerung nur für die 19 Anlass-Zeilen in BR-02** (Q-BR-07),
  je Datei ein Agent, danach Gegenlesung durch die Instanz. Alle anderen
  Pakete und Teile seriell.

### 4.1 In der Umsetzung getroffen

Zur Kenntnis. Keine ändert eine Entscheidung der Betreiberin; jede legt fest,
was eine Entscheidung offen ließ, damit ein Mittel es messen kann.

- **E-BR-11 — Die Anlass-Zeile gilt je Werkzeug, nicht je Unteranleitung**
  (BR-01). E-BR-03 (2) sagt „je Ordner direkt unter `tools/`", E-BR-04 hält
  die Unteranleitungen an die **Form**. Ein Unterteil wie
  `referenzdatensatz/browser/` hat keinen eigenen Fehler, sondern teilt den
  des Werkzeugs. Preis: Eine Unteranleitung darf eine Anlass-Zeile tragen,
  die niemand misst.
- **E-BR-12 — „Beginnt mit `Anlass:`" heißt: nach Kommentarzeichen und
  Hervorhebung** (`*`, `//`, `#`, `**`), außerhalb von Codeblöcken. Und die
  Nummer muss es im Backlog **geben** — schärfer als „nennt": Eine
  vertippte Nummer ist so wenig ein Anlass wie keine.
- **E-BR-13 — Der Kopfkommentar einer Probe ist der erste Kommentarblock**
  nach dem, was jede Datei ihrer Art trägt (`<?php`, `declare(…)`, `#!`,
  Leerzeilen). Eine Anlass-Zeile weiter unten im Code zählt nicht.
- **E-BR-14 — Die Einstiegsdatei einer Probe steht im Verteiler `RUF` von
  `proben.sh`**; bei einer Funktion ist es der eine Aufruf im Vordergrund
  (F-BR-08). Ein Probenordner ohne Eintrag ist ein Befund — sonst ginge der
  Riegel still an ihm vorbei (Grundsatz 7).
- **E-BR-15 — Gelesen wird, was `git add -A` in den Baum legte**
  (`git ls-files -co --exclude-standard`), wie beim Baum-Hash des
  Prüfberichts. Eine Ausgabe wie `tools/screenshots/ausgabe/` zählt nicht;
  sonst hinge die Zahl davon ab, wer zuletzt wo gemessen hat.
- **E-BR-16 — Buchführung.** Backlog Nr. 293 entsteht in BR-01 unter
  *Offen*, weil die Anleitung der Quelltextprüfungen sie als Anlass nennt,
  und wandert in BR-04 nach *Erledigt*. Der Nummernkopf von `Backlog.md`
  zieht schon in BR-01 nach, auf **304**: P5c hat 294–303 am 24.09.2026 auf
  seinem Zweig reserviert, und eine Zahl auf einem ungemergten Zweig ist
  vergeben. Die Changelog-Überschrift beginnt mit `Werkzeug:` — Abschnitt 3
  sagt „Präfix `Web` … wie RP", und RP hat `Werkzeug:` geschrieben; „wie RP"
  ist die genauere Angabe.

- **E-BR-17 — Ein Fehler ohne Nummer bekommt eine, nachträglich und unter
  *Erledigt*** (Q-BR-08, von der Betreiberin am 24.09.2026). Für die sieben
  Werkzeuge mit belegtem Fehler entsteht je ein Eintrag ab Nr. 304 auf dem
  BR-Zweig, mit Fundstelle — Changelog, Konzept, Commit. Das ist E-BR-07,
  rückwärts angewandt: Wer ein Prüfmittel anlegt, legt die Nummer zuerst an;
  wer es geerbt hat, trägt sie nach.
- **E-BR-18 — Drei Werkzeuge ohne verbuchten Fund bleiben, mit einer Nummer
  für das Risiko, das sie hüten** (Q-BR-10 bis -12, von der Betreiberin am
  24.09.2026, je einzeln): Rechtstexte-Probe (Nr. 311), Stilvergleich
  (Nr. 312), Kopplungsprobe (Nr. 313). `Pruefablauf.md` 6.1 trägt das mit
  „oder hätte fangen müssen".

## 5. Fragen an die Betreiberin

| Nr. | Frage | Empfehlung |
|---|---|---|
| **Q-BR-01** | Der Riegel als **neunte Quelltextprüfung** in `tools/quelltext/` (ein Läufer, eine Selbstprobe, im Tor unter „Quelltext — neun Prüfungen") — oder als eigenes Werkzeug `tools/bestand/` (Ordner 18)? | **Quelltextprüfung.** Er liest nur Dateien, braucht nichts und läuft im Tor — das ist die Definition von `tools/quelltext/` (E-PK-24). Ein eigener Ordner wäre der erste, den BR selbst anlegt. |
| **Q-BR-02** | Die **acht Unteranleitungen** (1 120 Zeilen, `referenzdatensatz/` sechs, `uhr-pruefstand/` zwei): (a) auf die Form bringen — je höchstens 40 Zeilen, Geschichte in den Commit, Skriptanleitung in den Skriptkopf; oder (b) vom Riegel ausnehmen, weil PK-04 sie nie gezählt hat? | **(a), E-BR-04.** Das ist der größte Posten von BR-02 — voraussichtlich die Hälfte des Pakets —, und genau deshalb: Die 1 120 Zeilen sind der Bestand, der PK-04 durchgerutscht ist. (b) wäre die erste Ausnahmeliste des Riegels am Tag seiner Einführung. Preis: Die Anleitungen des Referenzdatensatzes tragen heute Beschreibung des **Formats** (was der Generator erzeugt, wie der Vergleich normalisiert) — das ist keine Geschichte, sondern Dokumentation, und gehört nach `docs/Technik.md` oder in einen Dateikopf, nicht in den Papierkorb. Die Umsetzung sortiert je Absatz: Anleitung (bleibt, ≤ 40), Format (nach `Technik.md` oder Kopf), Geschichte (Commit). |
| **Q-BR-03** | Die zwei **losen Dateien** direkt unter `tools/`: `motor.mjs` (der Browser-Motor für Bilderlauf, Bedienprobe, Stilvergleich, zwei Anteilproben) und `konfig_stellen.php` (schreibt `config.php` für Sandbox und drei Proben). Wohin? | **`konfig_stellen.php` nach `tools/sandbox/`** — es ist ein Stück Anlage, und `Sandbox-Setup.md` ist seine Stelle. **`motor.mjs` bleibt, wo es ist, als benannte Ausnahme in E-BR-03 (4)**: Es ist die eine geteilte Bibliothek von fünf Werkzeugen, und jeder Ordner, in den man sie legt, macht vier Aufrufer zu Fremdlesern. Der Riegel erlaubt **genau diesen Namen** und keinen zweiten — steht die zweite lose Datei da, ist er rot. Alternative: `tools/motor/` mit eigener Anleitung; das ist ein Ordner für eine Datei. |
| **Q-BR-04** | F-BR-05 (a), die **doppelte Baumsuche** (Fenster 30 im Tor, 50 in der Auslieferung, eine ohne Selbstprobe): (a) eine Stelle — `tools/kette/` bekommt die Suche als Python mit Selbstprobe, beide Workflows rufen sie; oder (b) das Nebeneinander bleibt und wird in `Pruefablauf.md` 2.3 als Entscheidung mit Grund geführt? | **(a).** Der Grund für zwei Fenster (TB, E-TB-09: Kostengrenze je Aufrufer) bleibt als Parameter; die Suche selbst ist eine Sache. Es ist die Bauform, die `freigabe.py` schon hat, und `kettenaufrufe` prüft den Aufruf dann mit. Preis: eine dritte Datei in `tools/kette/`, und die Anleitung dort muss von 44 auf 40 — was BR-02 ohnehin tut. |
| **Q-BR-05** | **An P5c:** Die vier Anlässe mit Befund-Kennung (`statistik` F-P5c-40, Ankerprüfung F-P5c-43, Mailprobe F-P5c-29, Motor-Messungen F-P5c-41) und der QR-Decoder ohne Anlass und Ort — legt P5c dafür Backlog-Nummern an und nennt für den Decoder den Sammelordner, bevor AP5 beginnt? | **Ja, in P5c selbst**, als Nachtrag zur Fassung 2 — BR schreibt nicht in ein fremdes Konzept. Mit E-BR-07 wäre der Riegel sonst beim Merge von P5c rot, und zwar zu Recht. |
| **Q-BR-06** | **An P5c:** E-P5c-36 („jedes Paket mit Migration fährt `--stufe haupt`") ist eine Auslöseregel, die nur im Konzept steht — soll sie als Muster in `pruefablauf.json` (`server/migrationen/**` → `haupt`) oder als Satz in `Pruefablauf.md` 3 stehen, bevor das Konzept gelöscht wird? | **Als Muster in `pruefablauf.json`.** Dann bestimmt der Prüfstand die Stufe selbst und sagt sie, wie bei der Versionsstufe; und die Tabelle in 4 wird erzeugt. Ein Satz in 3 wäre eine Regel im Gedächtnis (Grundsatz 1). Das kann P5c in dem Paket tun, das die erste Migration bringt; BR fasst `pruefablauf.json` nur für den Riegel an. |
| **Q-BR-07** | Fächerung wie in Abschnitt 3 vorgeschlagen (nur die 19 Anlass-Zeilen in BR-02)? | **Ja.** Die Zeilen sind Nachschlagearbeit in getrennten Dateien; alles andere ist Text oder eine Datei. |

**Beantwortet am 24.09.2026, alle wie empfohlen:**

- **E-BR-08** (Q-BR-01): neunte Quelltextprüfung, kein eigener Ordner.
- **E-BR-04** (Q-BR-02): die acht Unteranleitungen kommen auf die Form;
  Format-Beschreibung nach `Technik.md` oder in den Dateikopf, Geschichte in
  den Commit.
- **E-BR-03 (4)** (Q-BR-03): `konfig_stellen.php` nach `tools/sandbox/`;
  `motor.mjs` bleibt als die eine beim Namen erlaubte lose Datei.
- **E-BR-09** (Q-BR-04): eine Baumsuche in `tools/kette/`, Fenster als
  Parameter.
- **Q-BR-05 und Q-BR-06** gehen als Zuarbeit an die P5c-Instanz — Wortlaut
  in Abschnitt 8; P5c trägt den Nachtrag selbst in sein Konzept ein.
- **E-BR-10** (Q-BR-07): Fächerung nur für die Anlass-Zeilen.

### 5.1 Fragen aus der Umsetzung (BR-02)

Die Nachschlagearbeit für die Anlass-Zeilen (E-BR-10, 20 Agenten, Gegenlesung
durch die Instanz) hat ergeben: **12 Proben** haben eine Backlog-Nummer, die
ihren Fehler nennt; **10 Werkzeuge** haben keine. Abschnitt 3 hatte
vorausgesetzt, dass die Nummer „schon da" ist.

| Nr. | Frage | Antwort (24.09.2026) |
|---|---|---|
| **Q-BR-08** | Sieben Werkzeuge haben einen **belegten** Fehler der Anwendung, aber keine Backlog-Nummer — `spur`, `raten`, `komplett`, `anteil`, `freigabe`, `container`, `frist`; der Fehler steht nur als Befund-Kennung, im Changelog oder als R44. Neue Einträge, eine Sammelnummer oder Kennungen zulassen? | **Neue Erledigt-Einträge** (wie empfohlen) → E-BR-17 |
| **Q-BR-09** | Drei Werkzeuge haben **keinen** belegten Fehler der Anwendung — Rechtstexte-Probe, Stilvergleich, Kopplungsprobe. Nummer für das Risiko, Streichliste oder einzeln? | **Einzeln vorlegen**, entschieden vor dem Abschluss von BR-02 → Q-BR-10 bis -12 |
| **Q-BR-10** | **Rechtstexte-Probe** (`tools/proben/rechtstexte/pruefen.php`). Vorsorglich mit `rt_html()` gebaut (P3/O10, R32): 81 Angriffsproben und eine Positivliste der Tags. Ein Fund ist nicht verbucht. Sie ist ein **Riegel** — sie läuft in jeder Stufe und im Tor. | **Behalten, neue Nummer** → Nr. 311, E-BR-18. Vorschlag war ein Erledigt-Eintrag für das Risiko, das sie hütet: „`rt_html()` ist der eine Weg, auf dem aus einer Eingabe HTML wird — eine Lücke dort wäre ein eingeschleustes Skript auf den öffentlichen Rechtstextseiten". Streichen hieße, den einzigen Nachweis dieses Wegs aus dem Tor zu nehmen. |
| **Q-BR-11** | **Stilvergleich** (`tools/stilvergleich/`). Anlass „P0/A3 — ein Umbau des Stylesheets ohne Netz und doppelten Boden". In Gebrauch (P5c AP2: 54 geplant, 54 gemessen, 0 ungeplant; `Pruefablauf.md` 6.10 regelt ihn), aber kein verbuchter Fund einer **ungeplanten** Änderung. | **Behalten, neue Nummer** → Nr. 312, E-BR-18. Vorschlag war ein Erledigt-Eintrag „Ein Umbau des Stylesheets ändert einen berechneten Stil, den niemand ändern wollte". Er ist das einzige Mittel für diese Frage; der Bilderlauf beantwortet sie nicht (6.10). |
| **Q-BR-12** | **Kopplungsprobe** (`tools/proben/kopplung/probe.php`). Prüft `pair.php` gegen den JSON-Vertrag, dazu die **Antwortgleichheit** der Fehlerzweige (beide 401 in 0,351 s, Rümpfe byteweise gleich). Die einzige Nummer im Umfeld, Nr. 178, beschreibt einen Fehler der Probe selbst, und zwar in `rundlauf.mjs`. | **Behalten, neue Nummer** → Nr. 313, E-BR-18. Vorschlag war ein Erledigt-Eintrag „Die Fehlerzweige der Kopplung dürfen nicht verraten, welche Kennungen es gibt". Die Kopplung ist der eine Weg, auf dem ein Gerät ohne Anmeldung zu Zugangsdaten kommt. |

## 6. Der Preis der Parallelität — und was P5c davon merkt

- **Ein Merge, ein Lauf.** Nach dem Merge von BR nimmt der P5c-Zweig `main`
  auf, auf dem Weg aus `Pruefablauf.md` 5.3: örtlich mergen, Prüfstand fahren,
  Merge-Commit mit Bericht. P5c AP1 ist Web 20.38.0, also eine Nebenstufe —
  rund 21 Minuten (E-RP-05). Einmal, wenn BR vor AP2 liegt; jedes Paket
  später kostet denselben Lauf noch einmal mit mehr Konflikten.
- **Gemeinsame Dateien.** Sicher: `Backlog.md`, `CHANGELOG.md`,
  `Rahmenplan.md` 10, `Pruefablauf.md` — Buchführung, mechanisch lösbar.
  Wahrscheinlich: `pruefablauf.json` (BR: eine Riegelzeile; P5c AP2: die
  Rollenprobe und `health.php`), `tools/bedienprobe/LIESMICH.md` (P5c AP1 hat
  sie schon angefasst; BR-02 bringt sie nicht unter 40, sie ist es — aber
  jede Zeile, die P5c dort ergänzt, zählt der Riegel). Beides ist eine
  Zeile gegen eine Zeile, kein Umbau.
- **Der Riegel gilt für P5c ab dem Aufnehmen.** Jedes neue Prüfmittel von
  P5c muss ab dann die Form haben — das ist der Zweck. Was heute im
  P5c-Konzept steht und nicht die Form hätte, steht in Q-BR-05.
- **Backlog-Nummern.** BR nimmt **293**; P5c weiß es aus diesem Konzept und
  beginnt bei 294. Die Nummernregel im Kopf von `Backlog.md` sagt heute noch
  „beginnt bei 292" — 292 ist seit RP vergeben; BR-04 zieht sie nach, und wer
  vorher eine Nummer vergibt, sieht auf `origin/main` **und** auf den
  P5c-Zweig.

## 7. Was dieses Konzept nicht klärt

- **Ob der Riegel auch tote Werkzeugpfade in Dokumenten zählen soll**
  (F-BR-13). BR hat 26 davon von Hand gefunden und berichtigt; kein Mittel
  hält neue auf. Das wäre eine siebte Regel mit eigener Quelle — den
  normativen Dokumenten statt `tools/` — und damit eine Frage an die
  Betreiberin, keine Entscheidung eines Pakets.

- **Ob `bestand.py` die Zahl aus Abschnitt 2 wiederfindet.** Die Zahl ist von
  Hand gezählt (`wc`, `grep -c '^## '`, `grep Anlass`). Findet das Mittel mehr,
  war die Hand zu grob; findet es weniger, misst das Mittel daneben — beides
  ist ein Befund in BR-01, kein Grund, die Zahl anzupassen.
- **Was aus den 1 120 Zeilen Unteranleitung Format-Dokumentation ist** und
  nach `Technik.md` gehört. Das entscheidet sich beim Lesen, in BR-02, je
  Absatz.
- **Die Steuerdokumente.** `Backlog.md` ist seit PK-Beginn um rund 1 000
  Zeilen und 30 Nummern gewachsen, `Rahmenplan.md` hat 3 669 Zeilen,
  `CLAUDE.md` vier Absätze „wer eine ältere Quelle liest". Das ist dieselbe
  Sorte Bestand wie unter `tools/` vor PK, und es wird das nächste Konzept.
  BR misst es nicht — ein Riegel über erzählenden Text ist ein anderes
  Werkzeug, und ob es eines braucht, ist eine Frage an die Betreiberin, nicht
  an ein Paket.

## 8. Zuarbeit an P5c — Wortlaut der Übergabe (24.09.2026)

Konzept BR (`docs/konzepte/Konzept-BR-Bestandsriegel.md`, Zweig
`claude/epic-mccarthy-zq08zw`, danach `claude/br-bestandsriegel`) baut einen
Riegel in Stufe 1 über den Werkzeugbestand unter `tools/` und mergt **vor
P5c AP2**. Ab dem Aufnehmen von `main` gilt er für P5c. Zwei Dinge im
P5c-Konzept hätten dann keine Form, und beide gehören P5c, nicht BR:

1. **Anlass ist eine Backlog-Nummer (E-BR-07).** Vier geplante Prüfmittel
   nennen als Anlass eine Befund-Kennung, die mit dem Konzept gelöscht wird:
   Messstand-Schritt `statistik` (F-P5c-40), Ankerprüfung in `linkprobe`
   (F-P5c-43), Rundmail in der Mailprobe (F-P5c-29), Motor-Messungen
   (F-P5c-41). Der QR-Decoder (`jsqr`) hat weder Anlass noch Ordner. Bitte
   je eine Backlog-Nummer anlegen (**ab 294** — BR hat 293; nachsehen auf
   `origin/main` und auf dem BR-Zweig) und für den Decoder den Sammelordner
   nennen, bevor AP5 beginnt. Für F-P5c-43 passt der offene Punkt Nr. 188.
2. **E-P5c-36 als Muster in `pruefablauf.json` (Q-BR-06).** „Jedes Paket
   mit Migration fährt `--stufe haupt`" steht nur im Konzept. Bitte als
   Muster `server/migrationen/**` → Stufe `haupt` eintragen, in dem Paket,
   das die erste Migration bringt; die Tabelle in `Pruefablauf.md` 4 wird
   daraus erzeugt.

Dazu, weil es beim Merge sonst rot wird: Jede neue `LIESMICH.md` (etwa
`tools/proben/rollen/`) hat die fünf Abschnitte aus `Pruefablauf.md` 6.2,
höchstens 40 Zeilen und eine Zeile `Anlass: Nr. …`; die schon angefasste
`tools/bedienprobe/LIESMICH.md` bleibt unter 40.

## 9. Umsetzung — Protokoll

### BR-01 Der Riegel — erledigt 24.09.2026

**Gebaut.** `tools/quelltext/bestand.py` (neu): sechs Regeln mit Namen —
`form`, `anleitung`, `anlass`, `inventur`, `lose`, `probe` —, eine
Selbstprobe mit 22 Fällen und `--wurzel` für einen anderen Auscheck. Dazu:
`tools/quelltext/pruefen.sh` (`bestand` in beiden Listen; die
Selbstprobenschleife startet jetzt über `starter()`, bis dahin fest `php`),
`tools/quelltext/LIESMICH.md` (neunte Zeile, 9 von 9, 6 von 6),
`tools/pruefstand/pruefablauf.json` (Probe und Riegel `bestand`),
`.github/workflows/pruefung.yml` („Quelltext — neun Prüfungen",
`--riegel "bestand=$q"`), `tools/kettenaufrufe/` (F-BR-09),
`docs/Pruefablauf.md` 4 (erzeugt), 6.2 und 6.11, `docs/Technik.md`
(Verzeichnisbaum, Schritte von Stufe 1), `docs/Backlog.md` Nr. 293 und der
Nummernkopf, `docs/CHANGELOG.md`.

**Gemessen.**

| Was | Mittel | Zahl |
|---|---|---|
| Selbstprobe des Riegels | `bestand.py --selbstprobe` | **22 Fälle, 0 Fehlschläge** — 19 eingebaute Fehler, jeder als Befund genau seiner Regel; 3 Gegenproben grün (genau 40 Zeilen, ohne `motor.mjs`, ignorierte Ausgabe mit 100 Zeilen); alle sechs Regeln mit mindestens einem Fehler |
| Selbstproben des Läufers | `pruefen.sh --selbstprobe` | **6 von 6** |
| Der Altbestand | `bestand.py` an diesem Stand | **57 Befunde, rc 1** — gewollt bis BR-02 |
| Gegenprobe gegen die Hand | Abschnitt 2 gegen den Lauf | jede Handzahl wiedergefunden; 22 Befunde mehr, jeder einzeln nachgesehen (F-BR-07) |
| Kettenaufrufe | `pruefen.py --probe`, dann ohne Schalter | **16 von 16**; **76** Aufrufe, 0 Befunde, 2 ungeprüft (vorher 75 / 0 / 2) |
| Die neun zusammen | `pruefen.sh alle` | 8 von 9 grün — `bestand` rot, gewollt |
| Doppelte Backlog-Nummern | der `grep` aus Stufe 1 | leer |
| Der P5c-Zweig | `bestand.py --wurzel` auf einem Arbeitsbaum von `origin/claude/p5c-mockups-konzept-4yeomf` | 58 Befunde: dieselben 57 (eine Zeilenzahl anders) und einer mehr — F-BR-11 |

**Probleme und wie sie gelöst wurden.** F-BR-08 (die Selbstprobe fand den
Fehler im Riegel, bevor er je lief), F-BR-09 (`kettenaufrufe` sah die Namen
nicht — das ist, was „kennt den neuen Aufruf" in Abschnitt 3 verlangt). Der
Konzeptzweig ließ sich aus der Sitzung nicht löschen (die Gegenstelle wies
das Löschen ab); die Betreiberin hat ihn selbst gelöscht.

**Fächerung.** Die Anlass-Zeilen von BR-02 sind während BR-01
nachgeschlagen worden — 20 Agenten, je Probe einer, **nur lesend**: Sie
schlagen Nummer und Beleg vor und ändern keine Datei. Gesetzt wird in BR-02
nach der Gegenlesung. So stand früh fest, ob eine Probe ohne Backlog-Nummer
bleibt und eine Frage an die Betreiberin nötig wird — sie wurde es (F-BR-12).

### BR-02 Der Altbestand — erledigt 24.09.2026

**Gebaut.** Alle sechs Regeln auf null:

- **probe** (20 → 0): je Einstiegsdatei eine Zeile `Anlass: Nr. …` als
  eigener Absatz im Kopfkommentar. Zwölf Nummern waren da (ingest 134, jobs
  37, wartung 171, mail 204, versand 139/49, wiederherstellung 31/33/34/35,
  gpx 130, verbindung 210, geraete 80, abmelden 22, csp-browser 181); zehn
  sind nachgetragen (304 bis 313, E-BR-17, -18). `abmelden/pruefe.mjs` war der
  Sonderfall: Der Kommentar endete in der Einfügezeile mit `*/`; er schließt
  jetzt in einer eigenen Zeile.
- **anlass** (12 → 0): Kürzel durch Nummern ersetzt (`PS-2` → Nr. 102,
  `B6` → Nr. 140, `F-S2-E` → Nr. 37, `P0/A3` → Nr. 312), Zeilen aus der
  Satzmitte an den Anfang (`kette` Nr. 219, `referenzdatensatz` Nr. 174 und
  267, `screenshots` Nr. 185 und 225, `uhr-pruefstand` Nr. 13), die zwei
  Sammelordner mit Nummern aus ihrem Bestand, `zaehlung` Nr. 202 und 257,
  `erzeugen` die Erzeugerform.
- **form** (22 → 0): `screenshots` 317 → 40, `zaehlung` 154 → 37,
  `uhr-pruefstand` 50 → 40, `kette` 44 → 39, die acht Unteranleitungen
  1 120 → 318. Je Absatz sortiert (Q-BR-02): Anleitung blieb; Format ging
  nach `Technik.md` (5.2b: Gerätearchive, Simulator und TLS; Runbook:
  Wiederaufbau des Referenzbestands) oder in den Skriptkopf
  (`aufnehmen.mjs` Seitenliste, `vergleichen.py` Versionsstufe,
  `register.php` Felder, `ProbeApp.mc` Präfixe, `erzeugen.py` drei
  Entscheidungen, `kreislauf.py` drei Läufe und HTTPS, `csv_import.mjs` und
  `angriffswerte.mjs` die Klickstrecken, `demo_pruefen.mjs` „nicht
  gemessen"); Geschichte steht in der Commit-Nachricht. **Die meisten
  Absätze standen schon ein zweites Mal im Skriptkopf** — die Anleitungen
  hatten sie wiederholt.
- **anleitung** (1 → 0): `tools/spaltenregister/LIESMICH.md` neu.
- **inventur** (1 → 0): `Sandbox-Setup.md` nennt den Erzeuger
  `tools/erzeugen/uhr-bilder.sh` jetzt mit richtigem Pfad (F-BR-10).
- **lose** (1 → 0): `konfig_stellen.php` nach `tools/sandbox/` (F-BR-14).

Dazu F-BR-13 (26 tote Werkzeugpfade, 3 tote Abschnittsverweise, eine
Zusage mit falschem Mittel) und F-BR-15 (veraltete Zahlen und Schalter).

**Gemessen.**

| Was | Mittel | Zahl |
|---|---|---|
| Der Riegel | `bestand.py` | **0 Befunde** (vorher 57) — 17 Ordner, 25 Anleitungen mit 972 Zeilen (vorher 24 mit 2 144), 20 Proben, 1 lose Datei (`motor.mjs`) |
| Die Proben nach dem Umzug und den Kopfzeilen | `proben.sh alle` auf frischer Anlage | **20 von 20 grün, 0 ausgelassen** — darunter die vier mit `konfig_stellen.php`: anteil, komplett 64/0, wiederherstellung 111/0, versand 135/0 |
| Syntax der 20 geänderten Probendateien | `php -l`, `node --check` | 0 Fehler |
| Die Zusage in `Backup-Format.md` | `vergleiche_edbak()` mit der Referenz, einmal mit eingesetztem `_spur_index` | 1 unerklärte Meldung (`kopf._spur_index zusaetzlich`); ohne Eingriff 0 |
| Tote Werkzeugpfade in normativen Dokumenten | Pfadprüfung über `docs/*.md`, `README`, `CLAUDE.md`, `android/LIESMICH.md` | 26 → 0 (ausgenommen: SDK-Pfade und der absichtlich geschichtliche `containeraufbau`-Absatz) |
| Selbstproben, deren Zahl die Anleitungen nennen | `aufnehmen.mjs`, `vergleichen.py`, `zaehlen.php`, `pruefen.php` (Spaltenregister), `download_lib.mjs` je `--selbstprobe` | 15/15, 14/14, 34/34, 16/16, 10/0 |
| Doppelte Backlog-Nummern | der `grep` aus Stufe 1 | leer (neu: 304 bis 313) |

**Fächerung** (E-BR-10). 20 Agenten, je Probe einer, **nur lesend**; sie
haben Nummer, Beleg, Zeile und Einfügestelle vorgeschlagen, die Instanz hat
gegengelesen und gesetzt. Abweichung von E-BR-10 in einem Punkt: Gesetzt hat
die Instanz, nicht der Agent — so lag jede Zeile vor dem Schreiben bei der
Gegenlesung. Verworfen wurde ein Vorschlag (Kopplungsprobe → Nr. 178,
F-BR-12); fünf mit „mittlerer" Sicherheit sind einzeln nachgelesen.
Nebenbefunde der Agenten, die in F-BR-13 eingegangen sind: die Zusage in
`Backup-Format.md`, die alten Pfade dort und ein verdrehter Satz in
`tools/proben/container/lesen_pruefen.py` (ein Pfad statt des alten; behoben).

**Probleme und wie sie gelöst wurden.** F-BR-12 war eine echte Lücke im
Konzept und ging als zwei Fragen an die Betreiberin (Q-BR-08, -09) und drei
Einzelfragen (Q-BR-10 bis -12). Beim Wiederaufbau-Weg im Runbook habe ich
zuerst `lokal_starten.sh` durch `lokal_einrichten.sh` ersetzt — ungeprüft;
zurückgenommen, weil `lokal_einrichten.sh` selbst schon ein Demo-Konto
anlegt.

### BR-03 Die Reste aus PK, TB und RP — erledigt 24.09.2026

**Gebaut.**

- **F-BR-03, drei Tor-Riegel.** Der Backlog-Schritt ist die siebte Regel von
  `bestand.py` (`backlog`, dieselbe Lesart wie der `grep` vorher, zwei Fälle
  in der Selbstprobe); „Python-Werkzeuge übersetzen" ist
  `tools/quelltext/pysyntax.py`, „Handbuch rendern" `tools/quelltext/handbuch.py`
  — die zehnte und elfte Quelltextprüfung, je mit Selbstprobe. In
  `pruefablauf.json` die Riegel `syntax-py` und `handbuch`; in `pruefung.yml`
  entfallen drei Schritte, einer kommt dazu (`cmark-gfm` bereitstellen), und
  die Gegenlesung übergibt `bestand`, `syntax-py`, `handbuch`. `cmark-gfm`
  steht in der Ausbaustufe `web` samt Nachweis. **Der Anlass der zwei neuen
  Namen steht in der Tabelle der Quelltextprüfungen** („Kette II/AP4",
  „P5b/AP8") wie bei den acht anderen — E-BR-07 nimmt diese Spalte
  ausdrücklich aus; die Anlass-Zeile des Ordners bleibt eine.
- **F-BR-04.** Teil 11 der Wiederherstellungsprobe schreibt „NICHT
  GEMESSEN" mit `false`.
- **F-BR-05 (a), E-BR-09.** `tools/kette/baumsuche.py` mit Selbstprobe;
  `pruefung.yml` ruft es mit `--fenster 30 --ereignis pull_request --erster`,
  `ausliefern-lauf.yml` mit `--fenster "$FENSTER" --ausgabe stufe1.json`
  (50). Die Selbstprobe fährt Stufe 1 neben der von `freigabe.py`.
  `kettenaufrufe` prüft beide Aufrufe (F-BR-17).
- **F-BR-05 (b)** `nach` steht in `Pruefablauf.md` 4; **(c)** die Bemerkung
  der Wegprobe nennt RP-01 statt E-PK-27; **(d)** Grundsatz 7 nennt seine
  eine Auslassung mit Beleg.

**Gemessen.**

| Was | Mittel | Zahl |
|---|---|---|
| Selbstproben der Quelltextprüfungen | `pruefen.sh --selbstprobe` | **8 von 8** (neu: `pysyntax` 3/0, `handbuch` 5/0; `bestand` 24/0 mit 7 Regeln) |
| Alle Quelltextprüfungen | `pruefen.sh alle` | **11 von 11** — 56 Python-Werkzeuge ohne Syntaxfehler, 2 Dokumente rendern |
| Die Baumsuche | `baumsuche.py --selbstprobe` | **11 von 11** |
| Kettenaufrufe | `--probe`, dann ohne Schalter | **18 von 18**; **84** Aufrufe (vorher 81), 0 Befunde, 2 ungeprüft |
| Die Wiederherstellungsprobe | `proben.sh wiederherstellung` auf der örtlichen Anlage | normal **111 / 0**, rc 0; Gegenprobe ohne die Zusatzkonten: **rc 1**, „NICHT GEMESSEN" rot (vorher grün) |
| Ausbaustufe `web` | `aufbauen.sh web` | **11 von 11** Stücken (neu `cmark-gfm`), 3 von 3 Engines, 8 von 8 Werten, rc 0 |
| Prüfstand-Selbstproben | `auswahl.py`, `bericht.py lesen` | 27 / 0, 13 / 0 |

**Nicht gemessen:** die Baumsuche gegen die echte GitHub-Schnittstelle — in
dieser Umgebung gibt es kein `gh`. Sie zeigt sich beim ersten Push auf
`main` nach dem Merge („Schon gemessen?") und beim nächsten Tag
(Produktionstor). Beide Wege sind so gebaut, dass ein Fehlschlag misst bzw.
das Tor schließt, nicht öffnet.

**Probleme und wie sie gelöst wurden.** F-BR-16 und F-BR-17 — beide von
einer Selbstprobe bzw. einem Befund gefunden, die es ohne BR-03 nicht
gegeben hätte. Die neue Anleitung der Quelltextprüfungen hatte zuerst 43
Zeilen; der Riegel hat sie gemeldet.

