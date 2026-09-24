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
> | Stand | **24.09.2026 — gebaut, gemergt als PR #85 (`ce42213`), Abschluss von der Betreiberin freigegeben.** BR-01 bis BR-05 erledigt — der Riegel steht, der Altbestand ist bereinigt (**57 → 0 Befunde**, `proben.sh alle` 20 von 20), die Reste aus PK, TB und RP sind aufgeräumt (17 Riegel statt 14, eine Baumsuche statt zweier), der Weg für ein neues Prüfmittel steht in `Pruefablauf.md` 6.12, und seit BR-05 meldet jeden Schritt dort ein Mittel: `bestand` misst **elf** Regeln, mit den echten Werkzeugen, nach **vier** adversarialen Gegenprüfrunden (Protokoll in Abschnitt 9). Der Abschluss läuft auf `claude/br-bestandsriegel`, neu von `main`. |
> | Entschieden | **E-BR-01 bis E-BR-10**, alle von der Betreiberin am 24.09.2026 (Abschnitt 4); Q-BR-01 bis -07 beantwortet, alle wie empfohlen (Abschnitt 5). **Aus der Umsetzung: E-BR-11 bis -16** (4.1, zur Kenntnis — keine ändert eine Entscheidung der Betreiberin). **E-BR-17 und -18** von der Betreiberin am 24.09.2026 auf Q-BR-08 bis -12 (5.1). **E-BR-19, -20** aus BR-04 (4.1). **E-BR-21** von der Betreiberin am 24.09.2026 auf Q-BR-13: die vier stillen Schritte in BR, als BR-05. **E-BR-22** aus BR-05 (4.1): echte Werkzeuge statt Nachbau, und nach Runde 4 ist Schluss. **E-BR-23** von der Betreiberin am 24.09.2026 auf Q-BR-14: die Spur-Endpunkte lösen die Spurprobe aus. |
> | Befunde der Umsetzung | **F-BR-07 bis -26** (2.1). Zwei davon verschieben den Rahmen: Die Handzählung aus Abschnitt 2 war zu grob (F-BR-07: 57 Befunde statt rund 35, **20** Anlass-Zeilen statt 19) — und **P5c ist weiter als angenommen** (F-BR-11: AP2 ist gebaut, E-BR-02 „Merge vor P5c AP2" ist damit überholt; was P5c nach dem Aufnehmen tun muss, ist gemessen). |
> | Offen | nichts. **Q-BR-13** und **Q-BR-14** sind beantwortet und gebaut (E-BR-21, -23). Die Zuarbeiten an P5c (Q-BR-05, -06) hat P5c übernommen; was P5c nach dem Aufnehmen tun muss, ist **gemessen** (8.1). |
> | Nächstes | **Abschluss** (freigegeben 24.09.2026): Erledigt-Zeile in `Rahmenplan.md` 8, Reste nach 6, Zeile in 10 (Fassung **114** — 113 hat der P5c-Zweig), Konzept löschen. Das Prüfdokument bleibt, bis seine Prüfliste abgehakt ist. |

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
| F-BR-18 | **Der Prüfstand meldete grün, wenn sein Bericht nicht entstand** (BR-04). `tools/pruefstand/pruefen.sh` wertete den Rückgabewert von `bericht.py schreiben` nicht aus (kein `set -e`); `bericht.py` legte seinen Index fest unter `WURZEL/.git/` an. In einem Git-Worktree ist `.git` eine Datei: `add -A` brach ab (`CalledProcessError`), und der Lauf endete mit **rc 0** und „0 rot, 17 grün", ohne Bericht. Gefunden von der Gegenlesung des Runbooks, nachgestellt in einem eigenen Worktree. Im Tor wäre es aufgefallen (kein Bericht → rot); örtlich hält sich eine Instanz für fertig. Dazu stand in der Anleitung des Prüfstands noch „klein ohne `server/` **14**" — seit BR-03 sind es 17. | Behoben in BR-04, **Backlog Nr. 314** (unter *Erledigt*): ohne Bericht rc 1 mit „KEIN BERICHT", den Index-Ort nennt Git (`rev-parse --git-path`). Gegenprobe mit einem absichtlich scheiternden `bericht.py`: rc 1. Anleitung auf 17. |
| F-BR-19 | **Das Runbook trug in der ersten Fassung nicht** (BR-04, Abnahme laut Abschnitt 3). Die unabhängige Instanz brauchte **5** Sprünge in andere Abschnitte und **4** weitere Dateien (`Backlog.md`, `pysyntax.py`, `pruefablauf.json`, `pruefung.yml`). Drei Aussagen waren falsch: Die Klammer zur Regel `backlog` (sie misst Dopplungen, nicht „keine Zeile mit Zahl und Punkt"); das Muster mit nur `pfade`, `ab`, `anlass` (`auswahl.py` liest `id` und `proben` — `KeyError`, während `bestand` und `kettenaufrufe` grün bleiben); `nach` beim Muster statt beim Proben-Eintrag. Und **vier Schritte meldet kein Mittel**: Name fehlt in `SELBST`, Tabellenzeile der Quelltextprüfung fehlt, Probe an keinem Muster und keinem Riegel, Tabelle in 4 nicht neu erzeugt. | 6.12 neu geschrieben: Form eines Backlog-Eintrags, beide JSON-Einträge vollständig, die 40-Zeilen-Falle, Rückgabewerte, das Ende des erzeugten Blocks, und je Schritt das Mittel, das sein Fehlen meldet — oder „niemand". Die vier Lücken als **Backlog Nr. 315** (offen) und **Q-BR-13**. |
| F-BR-20 | **Die Uhr-Probe des Prüfstands lief seit PK-03 nie** (BR-04). Der erste volle Prüfstand vor dem PR: `uhr-stufe1` **rot nach 0 s**, „Listendatei fehlt". `pruefablauf.json` rief `pruefstand.sh reihe` ohne die Geräteliste auf, die erst `geraeteklassen.py` schreibt. Bekannt seit PK-03 als **F-PK-21** („ob `kettenaufrufe` Pflichtargumente lernen soll: PK-04"), behoben nie. Unsichtbar, weil seit PK-05 kein PR `tools/uhr-pruefstand/` berührt hatte — BR-02 tut es, und das Tor verlangt dann `uhr=gebaut`. **Ohne Behebung wäre der PR von BR rot.** | Behoben in BR-04, **Backlog Nr. 316**: der Aufruf ist die Kette aus der Anleitung des Werkzeugs. Von Hand gemessen: 99 übersetzt, 0 fehlgeschlagen, 0 ohne Gerätedatei, rc 0, 9 min 56 s. Runbook 6.12, Schritt 7: den Aufruf einmal von Hand fahren. |
| F-BR-21 | **`kettenaufrufe` prüfte in einer Kette nur den ersten Befehl** (BR-04). Gegenprobe am neuen Aufruf von `uhr-stufe1`: `geraeteklassen.py --alle-listee` → 1 Befund, `pruefstand.sh reihee` hinter `&&` → **0**. Je Zeile ein Aufruf, jedes Wort dahinter ihm zugerechnet — die Schalter des zweiten Befehls wären dem ersten angelastet worden. | Die Zeile wird an `&&`, `\|\|`, `\|`, `;` zerlegt. Selbstprobe 18 → 20 (ein roter Fall, eine Gegenprobe); gegen die alte Fassung gefahren: sie zählte je Kette 1 Aufruf und übersah beide Tippfehler. 85 statt 84 Aufrufe, 0 Befunde. |
| F-BR-22 | **Die erste Fassung der vier Regeln hielt der Gegenprobe nicht stand** (BR-05). Fünf Angreifer (je Regel einer, dazu Querwirkungen), jeder Fund von einem weiteren Agenten in eigener Kopie nachgestellt: **30 Funde, 29 reproduziert und echt** (3 hoch, 22 mittel, 4 niedrig; der eine nicht echte: Der Erzeuger maskiert kein `\|` in einer Zelle — ein Mangel des Erzeugers, nicht des Riegels). Die tragenden: `zeile` las die Anlass-Spalte nach Position (vierte Spalte, fehlende Zelle, `\|\|`); `selbst` hielt einen Kommentar mit `'--selbstprobe'` für eine Auswertung und sah weder `getopt()` noch Dateien außerhalb von `NAMEN`; `bash_liste` zerbrach an einem Kommentar mit Klammer und übersah `NAMEN+=`; `ablauf` prüfte „irgendwo genannt" statt „erreichbar" (Waisen, die einander per `nach` halten; `nach` an einem Riegel), sah keinen Kreis in `nach`, keinen doppelten Schlüssel, `pfade` als Zeichenkette nicht und glaubte `stufen` aus der JSON; `tabelle` verglich nur den ersten Block, fand die Kopfzeile auch in einem Codeblock, hing an einer festen Endzeile und verdoppelte einen relativen `--wurzel`. | Zweite Fassung: Zeichenketten über den Tokenizer bzw. einen PHP-Zustandsautomaten, Tabellen nach GFM mit Spalte über die Überschrift, Bash-Listen zeilenweise, `pruefablauf.json` über Erreichbarkeit mit Kreis-, Typ- und Dublettenprüfung, Stufen aus `auswahl.py`, Tabellenblock nach Anfang und Länge der Erzeugerausgabe, außerhalb von Codeblöcken, genau einmal. Je Fund ein Fall in der Selbstprobe (65). Zweite Runde siehe Protokoll BR-05. |
| F-BR-23 | **Die Selbstprobe der Textprobe lief nirgends — im heutigen Bestand** (BR-05, der eine „hoch"-Fund, der kein konstruierter Fall war). `textprobe.py` hat eine Selbstprobe des Zerlegers mit 21 Fällen hinter `--probe`; `pruefen.sh --selbstprobe` ruft `--selbstprobe`, nur für `SELBST`, und `textprobe` stand nicht darin. Die erste Fassung von `selbst` suchte nur `--selbstprobe` und sah es nicht. | `textprobe.py` nimmt `--selbstprobe` (und weiter `--probe`), `textprobe` steht in `SELBST`: **9 von 9** Selbstproben, darunter 21/21 des Zerlegers. `selbst` meldet eine Selbstprobe hinter `--probe` seither als Befund. |
| F-BR-24 | **Ein Pfad in `pruefablauf.json` traf seit PK-03 keine Datei** (BR-05, Runde 3, der zweite Fund am heutigen Bestand). Muster `spur` nannte `server/api/spur*.php`; `git log --all` kennt keine solche Datei, die Spur-Endpunkte heißen `server/api/backup_spuren*.php`. Von 55 Pfaden in 27 Mustern traf genau dieser keine der versionierten Dateien. Die Selbstprobe von `auswahl.py` hielt ihn gegen eine erfundene Datei (`server/api/spurteil.php`) und war darum grün. | Den toten Pfad gestrichen — die Auswahl ändert sich nicht (`auswahl.py --abdeckung` vorher wie nachher 0 ohne Muster, 87 nur Auffang). Der Fall in `auswahl.py` prüft jetzt einen Glob gegen eine versionierte Datei. **Backlog Nr. 317.** Ob `server/api/backup_spuren*.php` die Spurprobe auslösen soll: **Q-BR-14**. `bestand` hält jeden Pfad gegen den Baum (`ablauf-pfad-trifft-nie`). |
| F-BR-25 | **Drei Runden, und die Nachbauten konvergierten nicht** (BR-05). Runde 2 fand 30 neue echte Mängel, Runde 3 weitere 27 (18 „mittel"; hier stand bis zum Abschluss von BR-05 „16", nachgezählt an den Urteilen der Nachstellung) — immer eine neue Randschreibweise, die der eigene Markdown-, PHP- oder Bash-Leser anders las als das Original (Heredoc, `?>` im Kommentar, Tabelle unter einem zweizeiligen Listenpunkt, HTML-Kommentar in der Tabelle, `NAMEN` in Anführungszeichen, `RUF+=`). Dazu zeigte eine Mutationsmessung: **23 von 67 Befundstellen** ließen sich streichen, ohne dass die Selbstprobe es merkte. | **E-BR-22**: echte Werkzeuge statt Nachbau, Befundstellen mit Kennung, die Selbstprobe verlangt jede. Vierte Fassung: 117 Fälle, **72 von 72 Befundstellen** gefallen; nach Runde 4 (F-BR-26): 140 Fälle, **85 von 85**. |
| F-BR-26 | **Die vierte Runde fand Lücken im Umfang, keine Randschreibweise mehr** (BR-05, gegen die vierte Fassung). 24 Funde, jeder in eigener Kopie nachgestellt: **21 echt**, davon **7 realistisch und mindestens „mittel"** — fünf Ursachen, zwei doppelt gemeldet: (1) `SELBST` wird geprüft, aber nicht, ob `pruefen.sh --selbstprobe` überhaupt jemand ruft — ohne die eine Zeile in `pruefung.yml` läuft keine Selbstprobe, und alles bleibt grün; (2) eine Fläche des Berichts (`FLAECHEN` in `bericht.py`), deren Bauprobe umbenannt oder deren Ordner aus dem Muster gefallen ist — das Tor verlangt `uhr=gebaut`, kein Lauf liefert es; (3) `auswahl.py` mit einem Geschwistermodul galt als „fehlt" (`modul_aus()` ohne `sys.path`), die Selbstprobe fiel in 111 von 117 Fällen; (4) ein PHP-Literal mit kaputtem UTF-8 (`"\xC3\x28"`, die Hausform solcher Prüffälle) ließ `json_encode` scheitern und leerte den Leser für alle Dateien; (5) `tabelle-kopie` fing nur eine WORTGLEICHE alte Zeile — die echte alte Riegelzeile unterscheidet sich immer. Dazu **fünf Mängel früherer Runden**, als nicht behoben nachgemessen: ein `\|` ohne `\` in einer Code-Spanne der Quelltexttabelle (GitHub wirft die Zellen über der Kopfzeile weg, die Anlass-Spalte zeigte fremden Text); eine regelgerecht geänderte Kopfzeile des Erzeugers galt als „kein Block"; drei Befundstellen ließen sich streichen, weil der Absturzfänger und je zwei Stellen dieselbe Kennung trugen; der Erzeuger, am Grundbestand gescheitert, brach die Selbstprobe mit Traceback ab; eine Endmarke unter der Tabelle zählt als zweiter Block. 14 echte „niedrig" oder nicht realistische Funde. | Die sieben und vier der fünf behoben, je mit Fall in der Selbstprobe: Befund `selbst-tor`; `ablauf-flaeche` und `-flaeche-probe` über `auswahl.treffer()` für **jede** Datei der Fläche, ab der kleinsten Stufe; `modul_aus()` mit Ordner in `sys.path`; `JSON_INVALID_UTF8_SUBSTITUTE`; Kopie am Anfang einer Zeile erkannt (die ersten zwei Zellen, der fette Vorsatz); `zeile-zellen` über die rohe Zeile; Block auch an der genauen Kopfzeile der Ausgabe; eine Kennung je Stelle, eigene für den Fänger, und ein Haken, der ihn auslöst; Erzeugerfehler rc 2 mit Meldung. Von den 14 kleinen sind sechs mitgenommen (`--` und `&shy;` als leer, Kreissuche ohne Rekursion, `tabelle` als eigene Gruppe — ein Komma zu viel in der Ablaufdatei ließ sie still ausfallen —, veraltete Tabellen der Selbstprobe mit dem echten Erzeuger, der Hinweis auf `SCHLUESSEL`, drei Fälle gegen Mutanten). Der Rest und die Endmarke sind **Grenzen** (Abschnitt 7, Kopf von `bestand.py`). **Keine fünfte Runde** (E-BR-22). |

## 3. Arbeitspakete

| Paket | Was | Berührt | Abnahme |
|---|---|---|---|
| **BR-01 Der Riegel** | `tools/quelltext/bestand.py` — die **neunte** Quelltextprüfung, mit Selbstprobe (6.3: sie hält einen Merge auf). Sie liest den Baum unter `tools/` und misst die Regeln aus Abschnitt 4 (E-BR-03 bis -06). Eintrag als Riegel in `pruefablauf.json` (das Tor übergibt ihn dann mit `--alle-riegel`, Lage 4), Aufruf in `pruefen.sh`, Schritt in `pruefung.yml` („Quelltext — neun Prüfungen"). **Bis BR-02 gemergt ist, ist der Riegel rot** — das ist gewollt, und der Prüfbericht des Pakets sagt es; die Selbstprobe belegt, dass jede der Regeln einen eingebauten Fehler findet. Beide Pakete gehen in **einem** PR (E-BR-01: kein Zwischenstand mit Decke). | `tools/quelltext/`, `tools/pruefstand/pruefablauf.json`, `.github/workflows/pruefung.yml`, `tools/kettenaufrufe/` (kennt den neuen Aufruf) | Selbstprobe: je Regel ein eingebauter Fehler → Befund mit Namen, danach 0; `kettenaufrufe` 0 Befunde; die Zahl des Riegels am Altbestand = die Zahl aus Abschnitt 2 (Gegenprobe: das Mittel findet, was von Hand gezählt wurde) |
| **BR-02 Der Altbestand** | F-BR-01 und F-BR-02 auf null: **19 Anlass-Zeilen** in den Probenköpfen (die Nummer aus Backlog, Changelog oder der Zuordnungstabelle in `Pruefablauf.md` 4, die je Probe schon einen Anlass nennt); `screenshots`, `zaehlung`, `uhr-pruefstand`, `kette` auf die Form — was Geschichte ist, geht in die Commit-Nachricht (Grundsatz 6), was Anleitung eines Unter-Skripts ist, in dessen Kopf; `erzeugen` bekommt seine Zeile (E-BR-05); `spaltenregister` eine Anleitung; die acht Unteranleitungen (E-BR-04) und die zwei losen Dateien (E-BR-03 (4)). | die genannten Ordner unter `tools/` | `bestand.py` **0 Befunde**; jede gestrichene Zeile hat ihren Platz (Commit, Kopf) oder war Geschichte; `proben.sh alle` 20 von 20 unverändert grün |
| **BR-03 Die Reste aus PK, TB und RP** | F-BR-03: die drei Tor-Schritte bekommen je eine Zeile in `pruefablauf.json` (`riegel`) und einen Aufruf in Station B — der Backlog-Schritt als Teil von `bestand.py` (er zählt Bestand), die zwei anderen als `pruefen.sh`-Namen oder als Zeile in `pruefstand/pruefen.sh`; jeder mit Anlass. F-BR-04: der `true`-Zweig wird **rot** („nicht gemessen"), wie E-PK-46 es verlangt. F-BR-05 (b), (c), (d): je ein Satz an der richtigen Stelle. F-BR-05 (a) nach E-BR-09. | `tools/proben/wiederherstellung/`, `tools/pruefstand/`, `docs/Pruefablauf.md` 1, 3, 4; `tools/kette/` und beide Workflows (E-BR-09) | Stufe 1 auf dem PR grün mit **17** Riegeln im Bericht statt 14; die Wiederherstellungsprobe auf einer Anlage mit weniger als zwei offenen Konten **rot**, nicht grün |
| **BR-04 Das Runbook und der Abschluss** | `Pruefablauf.md` **6.12 „Ein neues Prüfmittel"**: fünf Zeilen, in der Reihenfolge, in der sie zu tun sind (Backlog-Nummer → Ordner oder Sammelordner → Anleitung in der Form → Zeile in `pruefablauf.json` → Tabelle 4 erzeugen → Riegel grün). Dazu in 6.1 der Satz **„Ein Anlass ist eine Backlog-Nummer"** (E-BR-07). `CLAUDE.md` 6 bekommt keinen neuen Absatz — nur der Verweis „Regeln für Prüfmittel: `Pruefablauf.md` 6" steht dort schon. Backlog Nr. 293 nach Erledigt, die Nummernregel im Kopf von `Backlog.md` auf die nächste freie Zahl, Changelog (Präfix `Web`, ohne Versionsstufe — wie RP), Prüfdokument, PR. Die zwei Fragen an P5c (Q-BR-05, -06) mit Antwort ins P5c-Konzept übertragen — das tut P5c selbst, BR schreibt nicht in ein fremdes Konzept. | `docs/Pruefablauf.md` 6, `docs/Backlog.md`, `docs/CHANGELOG.md`, `docs/Rahmenplan.md` 10 | Stufe 1 grün; `bestand.py` 0; Runbook von einer Instanz gegengelesen, die es nicht geschrieben hat: Kann sie daraus ein Prüfmittel anlegen, ohne eine zweite Stelle zu lesen? |
| **BR-05 Die vier stillen Schritte** *(nachgetragen am 24.09.2026 auf Anweisung der Betreiberin, Q-BR-13)* | Nr. 315 als vier Regeln in `bestand.py`: `selbst` (Selbstprobe im Code ⇔ Name in `SELBST`, die Datei, die `starter()` startet, gibt es), `zeile` (je Name in `NAMEN` genau eine Tabellenzeile mit nicht leerer Anlass-Spalte, keine Zeile ohne Namen), `ablauf` (jede Probe hängt an Muster, Riegel oder `nach`; jeder genannte Name existiert; jedes Muster hat seine fünf Felder und eine Stufe als `ab`), `tabelle` (Abschnitt 4 = Ausgabe von `erzeugen-doku`, der Riegel ruft den Erzeuger). Selbstprobe je Regel; Runbook 6.12 ohne „niemand". | `tools/quelltext/bestand.py`, `docs/Pruefablauf.md` 6, `tools/quelltext/LIESMICH.md`, Buchführung | `bestand` 0 Befunde in **elf** Regeln; Selbstprobe mit je mindestens einem roten Fall je neuer Regel; eine unabhängige, adversariale Gegenprobe findet keinen Weg, eine der vier Lücken still offen zu lassen — oder was sie findet, ist behoben oder als Grenze benannt |

**Reihenfolge:** BR-01 → BR-02 → BR-03 → BR-04 (→ BR-05), je ein Commit mit
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
| Abschluss | **die Gegenlesung nach dem Löschen** — lesend, ohne Nebenwirkung: je ein Agent für die Belege der Prüfpunkte an den Läufen von PR #85 und `main`, für die Verweise auf das gelöschte Konzept über das ganze Repositorium, und für die Zahlen und Nummern über Rahmenplan, Backlog, Changelog und Prüfdokument; danach je Fund ein Agent, der ihn unabhängig nachprüft. Eingetragen von der Instanz am 24.09.2026 bei eingeschaltetem Ultracode, weil `CLAUDE.md` 7 ohne diese Zeile keine Fächerung erlaubt | alles Schreiben — Rahmenplan, Backlog, Changelog, Prüfdokument sind erzählender Text; der Prüfstand — Prüfarbeit an der einen Anlage |
| BR-05 | **die Gegenprobe der vier neuen Regeln** — lesend und in je einer eigenen Kopie des Baums, ohne die örtliche Anlage: je Regel ein Agent, der versucht, sie zu täuschen (ein Fehler, den sie still durchlässt; ein Bestand, den sie zu Unrecht rot meldet), danach ein Agent, der jeden Fund unabhängig nachstellt. Eingetragen von der Instanz am 24.09.2026 bei eingeschaltetem Ultracode, weil `CLAUDE.md` 7 ohne diese Zeile keine Fächerung erlaubt | der Umbau — alle vier Regeln stehen in **einer** Datei (`bestand.py`); Dokumente, Changelog, Konzept — erzählender Text; der Prüfstand — Prüfarbeit an der einen Anlage |

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
- **E-BR-19 — Grundsatz 5 in `CLAUDE.md` 6 bekommt einen Satz, keinen
  Absatz** (BR-04). Abschnitt 3 sagt „`CLAUDE.md` 6 bekommt keinen neuen
  Absatz". Dort stand aber derselbe Satz wie in `Pruefablauf.md` 1: „Ohne
  sie steht es auf der Streichliste." Nach BR ist das falsch — ohne die
  Zeile ist Stufe 1 rot. Zwei gleichlautende Sätze, von denen einer
  berichtigt wird, widersprechen einander; berichtigt sind beide, mit
  „Backlog-Nummer" und „ist Stufe 1 rot". Preis: `CLAUDE.md` steht in der
  Berührung des PR.
- **E-BR-21 — Die vier stillen Schritte werden in BR gemessen** (Q-BR-13,
  von der Betreiberin am 24.09.2026): als Paket BR-05 im offenen PR, nicht
  als Folgepaket. Die vier Regeln stehen in `bestand.py` — derselbe Riegel,
  dieselbe Selbstprobe, keine zwölfte Quelltextprüfung. `tabelle` ruft
  `bericht.py erzeugen-doku`, statt die Tabelle nachzubauen: Der Erzeuger ist
  die eine Stelle, der Riegel vergleicht nur. Die Anlass-Spalte der
  Quelltexttabelle bleibt von E-BR-07 ausgenommen; `zeile` verlangt nur,
  dass sie nicht leer ist.
- **E-BR-22 — Der Riegel liest mit den echten Werkzeugen, nicht mit
  Nachbauten** (BR-05, aus der Gegenprobe, F-BR-25). Tabellen über
  `cmark-gfm` (wie GitHub), PHP über `token_get_all` (wie PHP), die Listen
  der Läufer über `bash … --liste` (wie bash; `pruefen.sh` bekommt dafür den
  Modus `--liste`), Pfadmuster über `auswahl.passt()` (wie der Prüfstand).
  Eine Quelltextprüfung in einer anderen Sprache als Python oder PHP ist ein
  Befund („nicht messbar"), keine Schätzung — heute gibt es keine. **Preis:**
  `bestand` braucht `cmark-gfm` und `php`; beide stehen im Tor vor dem
  Quelltext-Schritt und in der Ausbaustufe `web`; fehlt eines, ist das ein
  Befund der Regel, nicht grün. Die Selbstprobe braucht rund 20 s statt 2 s.
  Jede Befundstelle trägt eine Kennung, und die Selbstprobe schlägt an, wenn
  eine in keinem Fall fällt. **Abbruchkriterium der Gegenprobe:** nach
  diesem Umbau eine letzte Runde; was sie noch findet und nicht realistisch
  und mindestens „mittel" ist, wird als Grenze benannt, nicht gejagt.
- **E-BR-23 — Die Spur-Endpunkte der Schnittstelle lösen die Spurprobe
  aus** (Q-BR-14, von der Betreiberin am 24.09.2026). Das Muster `spur`
  nennt `server/api/backup_spuren*.php`; eine Berührung von
  `backup_spuren.php` oder `backup_spuren_restore.php` fährt `spurprobe` und
  `containerprobe` (Backlog Nr. 317). Gebaut mit dem Abschluss, nach dem
  Merge von PR #85: ein Fall in der Selbstprobe von `auswahl.py`, der ohne
  den Pfad fehlschlägt, und die Tabelle in `Pruefablauf.md` 4 neu erzeugt —
  `bestand` hatte sie vorher als `tabelle-abweichung` gemeldet.
- **E-BR-20 — Die Fassung des Rahmenplans ist 112, nicht 108** (BR-04).
  `main` steht auf 107; der P5c-Zweig hat 108 bis 111 vergeben (gemessen an
  `f7729c2`). Dieselbe Regel wie bei den Backlog-Nummern: Eine Zahl auf
  einem ungemergten Zweig ist vergeben. Beim Zusammenführen kollidiert nur
  die Kopfzeile „Fassung …", und dort gilt die höhere.

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

### 5.1 Fragen aus der Umsetzung (BR-02, BR-04, BR-05)

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
| **Q-BR-13** | *(aus BR-04)* Vier Schritte beim Einhängen eines Prüfmittels meldet **kein** Mittel (F-BR-19, Nr. 315): Name nicht in `SELBST`, keine Tabellenzeile der Quelltextprüfung, Probe an keinem Muster und keinem Riegel, Tabelle in `Pruefablauf.md` 4 nicht neu erzeugt. (a) als Regeln in `bestand` bzw. als Vergleich mit `erzeugen-doku` bauen — in einem eigenen kleinen Paket nach BR; oder (b) gegenlesen lassen, wie 6.12 es jetzt sagt? | **(a), in BR — von der Betreiberin am 24.09.2026** („setz 315 direkt mit um"). → **E-BR-21**, Paket **BR-05**. Die Empfehlung war (a), aber in einem Folgepaket; die Betreiberin hat den kürzeren Weg gewählt — der PR war noch offen, und ein zweiter Durchlauf von Prüfstand und Tor hätte dasselbe gekostet. |
| **Q-BR-14** | *(aus BR-05)* Der Pfad `server/api/spur*.php` im Muster `spur` traf nie eine Datei (F-BR-24) und ist gestrichen. Die Spur-Endpunkte der Schnittstelle heißen `server/api/backup_spuren.php` und `backup_spuren_restore.php`; heute lösen sie nur das Auffangmuster und `oberflaeche` aus, keine Spurprobe. Soll (a) eine Berührung dieser beiden Dateien `spurprobe` und `containerprobe` auslösen (Pfad `server/api/backup_spuren*.php` ins Muster `spur`), oder (b) bleibt es so? | **(a) — von der Betreiberin am 24.09.2026** („trag backup_spuren in spur ein“). → **E-BR-23**. Empfohlen war (a): PK-03 wollte die Schnittstelle der Spuren an die Spurprobe hängen und hat sich im Dateinamen geirrt. |

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

- **Ein `|` in einer Zelle der erzeugten Tabelle.** `bericht.py
  erzeugen-doku` maskiert es nicht; eine Probe, ein Pfad oder ein Anlass mit
  `|` ergäbe eine kaputte Tabellenzeile, und `tabelle` beglaubigte sie, weil
  sie Byte für Byte der Ausgabe entspricht (Gegenprobe BR-05, als Mangel des
  Erzeugers eingestuft, nicht des Riegels). Heute steht in keiner Zelle ein
  `|`. Das gehört in den Erzeuger, wenn es gebraucht wird.
- **Positionsargumente eines Aufrufs.** Weder `kettenaufrufe` noch `bestand`
  sieht, ob ein Aufruf die Argumente bekommt, die sein Werkzeug verlangt —
  `uhr-stufe1` stand so von PK-03 bis BR-04 in der Datei (F-BR-20, F-PK-21).
  6.12 verlangt deshalb, den Aufruf einmal von Hand zu fahren. Ob
  `kettenaufrufe` Pflicht-Positionsargumente lernen soll, ist offen, seit
  PK-03 („PK-04") — eine Frage an die Betreiberin, kein Paket von BR.
- **Was die vierte Gegenprüfrunde fand und nicht gebaut ist** (F-BR-26,
  E-BR-22: nicht realistisch oder unter „mittel"). Benannt im Kopf von
  `bestand.py`, damit niemand sie für gemessen hält: Den Schalter
  `--selbstprobe` sieht `selbst` nur als Zeichenkette in der Datei selbst —
  in einer Konstanten für `getopt()` nicht; Unterordner von
  `tools/quelltext/` durchsucht es nicht nach fremden Selbstproben; ein
  Hilfsmodul, das den Schalter als Datum trägt oder selbst einen hat, gilt
  als Datei mit Selbstprobe (rot zu Unrecht, nicht still). Konstanten in
  `auswahl.py`, die eine Muster-id nennen (`AUFFANG = 'grundlage'`), gleicht
  `ablauf` nicht ab. `Nr. 12a` liest `anlass` als Nr. 12, und was nach „bis"
  oder „/" steht, nicht mehr. Eine Endmarke unter der erzeugten Tabelle zählt
  als zweiter Block (rot). Zwei Gegenproben der Selbstprobe ändern den
  Erzeuger selbst und greifen dafür in seinen Text; ändert er sich, melden
  sie „Anker fehlt" — rot und benannt, kein Absturz. Und die Gegenlesung im
  Tor (`--alle-riegel`) prüft, ob eine `--riegel`-Zeile da ist, nicht, ob ein
  Schritt die Probe fährt — das ist Stufe 1, nicht `bestand`.
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

### 8.1 Nachtrag zum Abschluss (BR-04, 24.09.2026) — gemessen, nicht vermutet

P5c hat die Übergabe als Nachtrag zur Fassung 2 übernommen (E-P5c-86 bis
-90, Backlog 295 bis 298 auf seinem Zweig). Was nach dem Aufnehmen von BR
bleibt, ist **gemessen**: Probemerge mit `git merge-tree` des BR-Stands
gegen `claude/p5c-mockups-konzept-4yeomf` an `f7729c2`, dann der Riegel über
den zusammengeführten Baum (`bestand.py --wurzel`).

1. **Vier Dateien kollidieren, alle zeilenweise.** `docs/Backlog.md` (Kopf
   der Nummernvergabe und die Einträge), `docs/CHANGELOG.md` (zwei Einträge
   oben), `docs/Rahmenplan.md` (Kopfzeile „Fassung 111" gegen „112" und die
   Verlaufszeilen, E-BR-20) — Buchführung, beide Seiten behalten. Und
   `tools/screenshots/LIESMICH.md`: BR-02 hat sie auf die Form gebracht, P5c
   hat darin Zahlen geändert. **Die BR-Fassung nehmen und die Zahlen von P5c
   einsetzen**; 40 Zeilen bleiben die Grenze.
2. **Danach genau ein Befund:** `tools/proben/protokoll/probe.php` Zeile 7,
   „Anlass: F-P5c-18 und F-P5c-19" — eine Befund-Kennung statt einer
   Backlog-Nummer (E-BR-07). Abhilfe: eine Nummer aus der Spanne des
   P5c-Zweigs (299 bis 303 sind frei) anlegen und in die Zeile schreiben.
   Alles andere hält die Form: `rollen` trägt `Anlass: Nr. 286`, die vier
   Anleitungen, die P5c angefasst hat, sind unter 40.
3. **`tools/konfig_stellen.php` liegt jetzt in `tools/sandbox/`.** Git führt
   den Umzug mit den Änderungen von P5c an den vier Proben ohne Konflikt
   zusammen; die neuen Proben von P5c binden die Datei nicht ein. Wer ab
   jetzt eine Probe schreibt, die sie braucht, schreibt
   `require_once __DIR__ . '/../../sandbox/konfig_stellen.php';`.
4. **Nummern.** BR hat Backlog 293 und 304 bis 313, Rahmenplan-Fassung 112.
   Die nächste freie Backlog-Nummer außerhalb beider Spannen ist **314**.
5. **Stufe 1 hat 15 Schritte und liest 17 Riegel gegen.** Wer auf P5c einen
   Riegel ergänzt, folgt `Pruefablauf.md` 6.12, Schritt 5. Auf dem
   zusammengeführten Baum sind die zwei anderen Bestandsmittel grün:
   Zählung 40 Zeilen, 0 über der Decke; `kettenaufrufe` 86 Aufrufe,
   0 Befunde.

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
| Alle Quelltextprüfungen | `pruefen.sh alle` | **11 von 11** — 56 Python-Werkzeuge ohne Syntaxfehler (gemessen, bevor `baumsuche.py` entstand; der Commit trägt 57 — nachgezählt in BR-05), 2 Dokumente rendern |
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

### BR-04 Das Runbook und der Abschluss — erledigt 24.09.2026

**Gebaut.**

- `Pruefablauf.md` **6.12 „Ein neues Prüfmittel"**, sieben Schritte. In
  **6.1** der Satz „Ein Anlass ist eine Backlog-Nummer" (E-BR-07), dazu,
  dass die Zeile je Ordner und je Probe gemessen wird und die Tabelle der
  Quelltextprüfungen nicht. **Grundsatz 5** in `Pruefablauf.md` 1 und in
  `CLAUDE.md` 6: „ist Stufe 1 rot" statt „steht auf der Streichliste"
  (E-BR-19). 9 „Herkunft" nennt RP und BR.
- **Backlog:** Nr. 293 nach *Erledigt*; **Nr. 314** (F-BR-18) und
  **Nr. 316** (F-BR-20) behoben unter *Erledigt*, **Nr. 315** (F-BR-19)
  offen; der Nummernkopf auf **317**. Nachgesehen auf allen sechs Zweigen
  der Gegenstelle: keiner vergibt 314 bis 316.
- **Rahmenplan** Fassung **112** (E-BR-20), eine Zeile in Abschnitt 10.
  Die Erledigt-Zeile in Abschnitt 8 folgt nach der Freigabe.
- **Prüfstand:** Ohne Bericht ist der Lauf rot; den Ort des eigenen Index
  nennt Git (F-BR-18). Die Anleitung des Prüfstands nennt 17 statt 14.
  `uhr-stufe1` ist verdrahtet (F-BR-20, Nr. 316); `kettenaufrufe` prüft
  jeden Teil einer Kette (F-BR-21).
- **Übergabe an P5c gemessen** (8.1).
- **Veraltete Zahlen**, die BR-03 übersehen hatte: „neun" Prüfungen in
  `Technik.md` und `README.md`, „sechs Regeln" im Kopf von `bestand.py`,
  „14" in der Anleitung des Prüfstands.

**Die Gegenlesung — die Abnahme aus Abschnitt 3.** Eine Instanz, die 6.12
nicht geschrieben hat, hat in einem eigenen Worktree nur danach zwei
Attrappen eingehängt — eine Quelltextprüfung als Riegel, eine Probe an
einem Muster — und je einen Schritt weggelassen. **Ergebnis der ersten
Fassung: Nein**, sie trug nicht allein (F-BR-19). Mit dem, was sie
nachschlagen musste, waren beide Attrappen vollständig grün: `bestand`
0 Befunde bei 21 Proben, `kettenaufrufe` 86 Aufrufe / 0, `pruefen.sh
--selbstprobe` 9 von 9, `alle` 12 von 12, der Prüfstand wählte die
Attrappe über ihr Muster. Die Gegenproben:

| Weggelassen | gemeldet von |
|---|---|
| Anlass-Zeile der Probe, `RUF`-Zeile, Backlog-Eintrag, Kürzel statt Nummer | `bestand` rc 1 |
| Name in `NAMEN` | `kettenaufrufe` rc 1 |
| Name in `starter()` | Läufer: eine Prüfung weniger grün |
| `--riegel` im Tor | Gegenlesung: „steht in pruefablauf.json, läuft aber nicht im Tor" |
| Name in `SELBST`, Tabellenzeile, Muster/Riegel, Tabelle in 4 | **niemand** (Nr. 315) |

Die zweite Fassung von 6.12 beantwortet jede Lücke an Ort und Stelle und
sagt bei jedem Schritt, wer ihn meldet. **Nicht noch einmal gegengelesen**
— eine zweite unabhängige Lesung hätte eine zweite Instanz gebraucht, die
die erste Fassung nicht kennt; der Prüfpunkt P-BR-09 holt das beim
nächsten echten Prüfmittel nach.

**Gemessen** (örtlich, vor dem Prüfstand):

| Was | Mittel | Zahl |
|---|---|---|
| F-BR-18 vorher | `pruefstand/pruefen.sh --stufe klein --datei docs/Backlog.md` in einem Worktree | `CalledProcessError`, **rc 0**, „0 rot, 17 grün" |
| F-BR-18 nachher | derselbe Lauf | Bericht mit Baum, rc 0 |
| F-BR-18 Gegenprobe | `bericht.py` ersetzt durch `sys.exit(3)` | „KEIN BERICHT", **rc 1** |
| Selbstproben | `bericht.py lesen`, `auswahl.py` | 13 / 0, 27 / 0 |
| Probemerge gegen P5c | `git merge-tree`, `bestand.py --wurzel` | 4 Konflikte, 1 Befund (8.1) |
| F-BR-20 | erster voller Prüfstand; dann der neue Aufruf von Hand | `uhr-stufe1` **rot nach 0 s**; von Hand **99 / 0 / 0**, rc 0, 9 min 56 s |
| F-BR-21 | `kettenaufrufe` alt gegen neu, je zwei Ketten mit Tippfehler hinter `&&` | alt 1 Aufruf, 0 Befunde; neu 2 Aufrufe, 1 Befund; Selbstprobe **20 von 20**, **85** Aufrufe / 0 |

**Der Prüfstand mit Uhr-Bau läuft zuletzt**, nach dieser Zeile; sein
Bericht steht in der Nachricht des Kopf-Commits, nicht hier — ein Protokoll,
das ihn nennt, hätte den Baum verändert, den er belegt.

**Probleme und wie sie gelöst wurden.** F-BR-18 und F-BR-19, beide von der
Gegenlesung gefunden; F-BR-20 und F-BR-21 vom ersten vollen Prüfstand.
Der erste Anlauf des Prüfstands kam gar nicht bis zu den Proben: Die
Anlage antwortete mit 404, weil der PHP-Server noch aus dem Wegwerf-Worktree
der F-BR-18-Messung lief, dessen Verzeichnis gelöscht war. `hochfahren.sh`
fand den Port belegt und meldete den 404 als Befund mit Zahl, rot — nicht
still. Server beendet, neu gefahren. Der zweite Anlauf: `android-bau`
„nicht gemessen", weil `ANDROID_HOME` nicht gesetzt war (die Probe prüft
ihre Umgebungswerte vorab, der Vorgabewert im Aufruf greift darum nie;
`android/LIESMICH.md` verlangt das `export`), und `uhr-stufe1` rot
(F-BR-20). Der Worktree des Agenten lag unter
`.claude/worktrees/` im Repositorium und ist dort nicht ignoriert; er hätte
im Baum-Hash des Prüfstands gestanden. Örtlich über `.git/info/exclude`
ausgeschlossen und nach der Gegenlesung entfernt. Der Hook der Sitzung
verlangte vor dem Ende der Gegenlesung einen Commit — daher der
Zwischenstand `c5fc807`.

### BR-05 Die vier stillen Schritte — erledigt 24.09.2026

**Gebaut.** Nr. 315 als vier Regeln in `tools/quelltext/bestand.py`, elf
statt sieben — derselbe Riegel, dieselbe Selbstprobe (E-BR-21):

- **`selbst`** — eine Quelltextprüfung, deren Code `--selbstprobe`
  auswertet, steht in `SELBST`, und umgekehrt; eine Selbstprobe hinter
  `--probe` oder in einer Datei außerhalb von `NAMEN` ist ein Befund; die
  Datei, die `starter()` startet, gibt es und ist Python oder PHP; und
  `pruefen.sh --selbstprobe` steht in einem Workflow oder einem Aufruf
  (Runde 4).
- **`zeile`** — je Name genau eine Zeile in der Tabelle von
  `tools/quelltext/LIESMICH.md`, so wie GitHub sie zeigt, mit Anlass; keine
  Zeile ohne Namen, mit fremdem Namen oder mit mehr Zellen als die
  Kopfzeile.
- **`ablauf`** — `pruefablauf.json` so, wie `auswahl.py` und das Tor es
  lesen: jede Probe erreichbar, jeder Name definiert, kein Kreis in `nach`,
  keine Schlüssel, die niemand liest, jedes Muster vollständig mit Pfaden,
  die eine Datei treffen, jede Probe der Läufer aufgerufen, und jede Datei
  einer Fläche des Berichts wählt ab der kleinsten Stufe ihre Bauprobe aus.
- **`tabelle`** — Abschnitt 4 von `Pruefablauf.md` ist genau einmal die
  Ausgabe von `erzeugen-doku`, in Abschnitt 4, und nirgends steht eine
  veraltete Kopie; eine eigene Gruppe, unabhängig von `ablauf`.

Gelesen wird mit den echten Werkzeugen (E-BR-22): `cmark-gfm`,
`token_get_all`, `bash … --liste` (neuer Modus in `pruefen.sh`),
`auswahl.passt()` und `auswahl.treffer()`, `FLAECHEN` aus dem geladenen
`bericht.py`. Jede Befundstelle hat eine Kennung (85), die Selbstprobe
verlangt jede; das Auffangnetz je Gruppe hat eine eigene und einen Haken,
der es auslöst.

**Am heutigen Bestand gefunden und behoben** — die zwei Funde, die kein
konstruierter Fall waren: Die Selbstprobe der Textprobe lief nirgends
(F-BR-23: `--selbstprobe`, in `SELBST`, 9 von 9), und ein Pfad der
Zuordnung traf seit PK-03 keine Datei (F-BR-24, Nr. 317; die Frage, was
dort stehen soll, ist Q-BR-14). Dazu drei Lesefehler anderer Werkzeuge, die
dieselbe Gegenprobe zeigte: `pysyntax` sah Pfade mit Umlaut nicht (`git
ls-files` ohne `-z`), `kettenaufrufe` las `NAMEN=(…)` bis zur ersten
Klammer und übersah `NAMEN+=` (jetzt zeilenweise, 21 von 21), und die
Selbstprobe von `auswahl.py` prüfte den toten Pfad gegen eine erfundene
Datei.

**Die Gegenprobe — die Abnahme aus Abschnitt 3.** Gefächert nach der
Zeile in Abschnitt 3: je Runde fünf Angreifer in je einer eigenen Kopie
des Baums (je Regel einer, dazu Querwirkungen), jeder Fund von einem
weiteren Agenten unabhängig nachgestellt; ab Runde 2 dazu je Gruppe die
Nachmessung der offenen Funde. Umbau, Dokumente und Prüfarbeit seriell.

| Runde | gegen | gemeldet | echt | Folge |
|---|---|---|---|---|
| 1 | erste Fassung (eigene Leser) | 30 | **29** (3 hoch, 22 mittel) | zweite Fassung, F-BR-22 |
| 2 | zweite Fassung | 30 neue | **30** (13 mittel) | dritte Fassung |
| 3 | dritte Fassung | 29 neue | **27** (18 mittel) | Nachbauten konvergieren nicht: E-BR-22, vierte Fassung mit den echten Werkzeugen (F-BR-25) |
| 4 | vierte Fassung | 24 neue, dazu 9 Nachmessungen | **21** (1 hoch, 6 mittel), alle 7 davon realistisch (5 Ursachen); 5 frühere als nicht behoben | alle 7 und 4 der 5 behoben, 6 der kleinen mitgenommen, der Rest Grenze (F-BR-26, Abschnitt 7); **keine fünfte Runde** |

**Gemessen** (örtlich, vor dem Prüfstand):

| Was | Mittel | Zahl |
|---|---|---|
| Selbstprobe | `bestand.py --selbstprobe` | **140 Fälle, 0 Fehlschläge** (117 mit eingebautem Fehler, 23 Gegenproben); **85 von 85** Befundstellen gefallen; 22 s |
| Mutationsmessung | jede der 80 Befundstellen und die 3 Rückgaben in `anlass_pruefen()` einzeln stumm, je die ganze Selbstprobe; Kontrolle unverändert im selben Aufbau | **83 von 83** Mutanten rot (rc 1, je mindestens ein `[FEHL]`), 0 überlebt; Kontrolle 140 / 0. In Runde 4 überlebten 6 von 81 |
| Riegel am Bestand | `bestand.py` | **0 Befunde in elf Regeln**: 17 Ordner, 25 Anleitungen, 20 Proben, 11 Quelltextprüfungen (9 mit Selbstprobe), `pruefablauf.json` 46 Proben / 27 Muster / 17 Riegel; 0,4 s |
| Gegenprobe am echten Bestand | Kopie des Baums, je ein eingebauter Fehler | Ordner `tools/uhr-pruefstand/**` aus dem Muster `uhr` → `ablauf-flaeche` + `tabelle-abweichung`; `--selbstprobe` aus `pruefung.yml` → `selbst-tor`; `a\|b` in einer Code-Spanne → `zeile-zellen`; alte Riegelzeile ohne `bestand` → `tabelle-kopie`; Anlass `&shy;` → `zeile-anlass-leer`; unverändert **0** |
| Rest aus Runde 3 (Kopfzeile) | Kopfzeile des Erzeugers geändert, Tabelle neu erzeugt | Riegel **0**, Selbstprobe **140 / 0**, kein „Anker fehlt" |
| F-BR-23 | `pruefen.sh --selbstprobe` | **9 von 9**, darunter die Textprobe mit 21 / 21 |
| F-BR-24 | `auswahl.py --abdeckung` vorher / nachher; `--selbstprobe` | je 0 ohne Muster, 87 nur Auffang; **27 / 0** |
| Quelltextprüfungen | `pruefen.sh alle` | **11 von 11**; 57 Python-Werkzeuge ohne Syntaxfehler |
| `kettenaufrufe` | `--probe`, dann ohne Schalter | **21 von 21**; 85 Aufrufe, 0 Befunde, 2 benannt ungeprüft |
| Übrige | Zählung; `bericht.py lesen --selbstprobe`; `baumsuche`; `freigabe`; `git diff --check` | 38 Zeilen / 0 über der Decke; 13 / 0; 11 / 0; 52 / 0; sauber |

**Der Prüfstand läuft zuletzt**, nach dieser Zeile; sein Bericht steht in
der Nachricht des Paket-Commits.

**Probleme und wie sie gelöst wurden.** Die ersten drei Fassungen bauten
Leser nach, und jede Runde fand die nächste Randschreibweise — das war
kein Fleiß-, sondern ein Bauartproblem, und E-BR-22 hat es so gelöst: Wer
liest, wie das Original liest, kann sich nicht anders verlesen. Preis ist
die Laufzeit der Selbstprobe (2 s → rund 20 s) und zwei Werkzeuge mehr im
Tor. Die Mutationsmessungen der Runden 3 und 4 zeigten, dass die
Selbstprobe Kennungen zählte, nicht Stellen: Drei Stellen teilten sich
eine Kennung mit dem Auffangnetz. Jetzt hat jede Stelle ihre eigene, und
die Messung ist wiederholt (oben). Nach dem Umbau der Runde 4 fielen neun
Fälle der Selbstprobe — jeder geprüft, keiner ein Fehler des Riegels:
Ohne `auswahl.py` lädt `bericht.py` nicht, also ist `FLAECHEN` wirklich
unlesbar; die Muster-id zeigt die Tabelle gar nicht; und seit `tabelle`
eine eigene Gruppe ist, meldet sie ihr Teil auch dann, wenn `ablauf` früh
aufhört. Die Erwartungen sind nachgezogen, mit dem Grund im Namen des
Falls. Die Selbstprobe baute veraltete Tabellen durch Textersatz im
Ausgabeformat (`| neben |`); eine regelgerechte Formatänderung des
Erzeugers hätte sie still der aktuellen gleich gemacht. Jetzt erzeugt sie
sie mit dem echten Erzeuger aus einer geänderten Ablaufdatei — belegt mit
einer geänderten Kopfzeile des Erzeugers: 140 / 0 ohne einen „Anker
fehlt". Der Hook der Sitzung verlangte mehrmals Commit und Push; beides
wartet bis zum Paket-Commit mit Bericht, weil ein Zwischenstand ohne
Bericht in Stufe 1 rot wäre.
