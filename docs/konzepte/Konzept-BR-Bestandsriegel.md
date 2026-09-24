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
> | Stand | **24.09.2026 — Konzept zur Bestätigung.** Kein Paket begonnen. |
> | Entschieden | **E-BR-01** (Riegel steht auf null, Altbestand wird im selben Konzept bereinigt) und **E-BR-02** (parallel zu P5c, Merge vor P5c AP2) — von der Betreiberin am 24.09.2026. Alle weiteren E-BR sind **Vorschläge** (Abschnitt 4) und gelten erst mit Antwort auf die Q-BR. |
> | Offen | Q-BR-01 bis Q-BR-06 (Abschnitt 5) |
> | Nächstes | Antworten auf die Q-BR, dann **BR-01** in einer Code-Sitzung |

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

## 3. Arbeitspakete

| Paket | Was | Berührt | Abnahme |
|---|---|---|---|
| **BR-01 Der Riegel** | `tools/quelltext/bestand.py` — die **neunte** Quelltextprüfung, mit Selbstprobe (6.3: sie hält einen Merge auf). Sie liest den Baum unter `tools/` und misst die Regeln aus Abschnitt 4 (E-BR-03 bis -06). Eintrag als Riegel in `pruefablauf.json` (das Tor übergibt ihn dann mit `--alle-riegel`, Lage 4), Aufruf in `pruefen.sh`, Schritt in `pruefung.yml` („Quelltext — neun Prüfungen"). **Bis BR-02 gemergt ist, ist der Riegel rot** — das ist gewollt, und der Prüfbericht des Pakets sagt es; die Selbstprobe belegt, dass jede der Regeln einen eingebauten Fehler findet. Beide Pakete gehen in **einem** PR (E-BR-01: kein Zwischenstand mit Decke). | `tools/quelltext/`, `tools/pruefstand/pruefablauf.json`, `.github/workflows/pruefung.yml`, `tools/kettenaufrufe/` (kennt den neuen Aufruf) | Selbstprobe: je Regel ein eingebauter Fehler → Befund mit Namen, danach 0; `kettenaufrufe` 0 Befunde; die Zahl des Riegels am Altbestand = die Zahl aus Abschnitt 2 (Gegenprobe: das Mittel findet, was von Hand gezählt wurde) |
| **BR-02 Der Altbestand** | F-BR-01 und F-BR-02 auf null: **19 Anlass-Zeilen** in den Probenköpfen (die Nummer aus Backlog, Changelog oder der Zuordnungstabelle in `Pruefablauf.md` 4, die je Probe schon einen Anlass nennt); `screenshots`, `zaehlung`, `uhr-pruefstand`, `kette` auf die Form — was Geschichte ist, geht in die Commit-Nachricht (Grundsatz 6), was Anleitung eines Unter-Skripts ist, in dessen Kopf; `erzeugen` bekommt seine Zeile (E-BR-05); `spaltenregister` eine Anleitung; die acht Unteranleitungen und die zwei losen Dateien nach Q-BR-02 und Q-BR-03. | die genannten Ordner unter `tools/` | `bestand.py` **0 Befunde**; jede gestrichene Zeile hat ihren Platz (Commit, Kopf) oder war Geschichte; `proben.sh alle` 20 von 20 unverändert grün |
| **BR-03 Die Reste aus PK, TB und RP** | F-BR-03: die drei Tor-Schritte bekommen je eine Zeile in `pruefablauf.json` (`riegel`) und einen Aufruf in Station B — der Backlog-Schritt als Teil von `bestand.py` (er zählt Bestand), die zwei anderen als `pruefen.sh`-Namen oder als Zeile in `pruefstand/pruefen.sh`; jeder mit Anlass. F-BR-04: der `true`-Zweig wird **rot** („nicht gemessen"), wie E-PK-46 es verlangt. F-BR-05 (b), (c), (d): je ein Satz an der richtigen Stelle. F-BR-05 (a) nach Q-BR-04. | `tools/proben/wiederherstellung/`, `tools/pruefstand/`, `docs/Pruefablauf.md` 1, 3, 4; je nach Q-BR-04 `tools/kette/` und beide Workflows | Stufe 1 auf dem PR grün mit **17** Riegeln im Bericht statt 14; die Wiederherstellungsprobe auf einer Anlage mit weniger als zwei offenen Konten **rot**, nicht grün |
| **BR-04 Das Runbook und der Abschluss** | `Pruefablauf.md` **6.12 „Ein neues Prüfmittel"**: fünf Zeilen, in der Reihenfolge, in der sie zu tun sind (Backlog-Nummer → Ordner oder Sammelordner → Anleitung in der Form → Zeile in `pruefablauf.json` → Tabelle 4 erzeugen → Riegel grün). Dazu in 6.1 der Satz **„Ein Anlass ist eine Backlog-Nummer"** (E-BR-07). `CLAUDE.md` 6 bekommt keinen neuen Absatz — nur der Verweis „Regeln für Prüfmittel: `Pruefablauf.md` 6" steht dort schon. Backlog Nr. 293 nach Erledigt, die Nummernregel im Kopf von `Backlog.md` auf die nächste freie Zahl, Changelog (Präfix `Web`, ohne Versionsstufe — wie RP), Prüfdokument, PR. Die zwei Fragen an P5c (Q-BR-05, -06) mit Antwort ins P5c-Konzept übertragen — das tut P5c selbst, BR schreibt nicht in ein fremdes Konzept. | `docs/Pruefablauf.md` 6, `docs/Backlog.md`, `docs/CHANGELOG.md`, `docs/Rahmenplan.md` 10 | Stufe 1 grün; `bestand.py` 0; Runbook von einer Instanz gegengelesen, die es nicht geschrieben hat: Kann sie daraus ein Prüfmittel anlegen, ohne eine zweite Stelle zu lesen? |

**Reihenfolge:** BR-01 → BR-02 → BR-03 → BR-04, je ein Commit mit
Paketpräfix, Push nach jedem Paket (`CLAUDE.md` 7, 8). BR-01 zuerst, weil erst
das Mittel sagt, ob die Zahl aus Abschnitt 2 vollständig war — und weil BR-02
gegen das Mittel misst, nicht gegen eine Liste im Konzept.

**Fächerung** (je Paket, `CLAUDE.md` 7, Vorschlag Q-BR-06):

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

Vorschläge, die mit den Antworten auf Abschnitt 5 gelten:

- **E-BR-03 — Was der Riegel misst, je Ordner direkt unter `tools/`:**
  (1) eine `LIESMICH.md` mit **genau** den fünf Abschnitten aus 6.2 in dieser
  Reihenfolge und **höchstens 40 Zeilen**; (2) **eine** Zeile, die mit
  `Anlass:` beginnt und mindestens eine Backlog-Nummer (`Nr. 123`) nennt —
  oder, nur bei Erzeugern, `Anlass: entfällt — Erzeuger (E-BR-05)`;
  (3) der Ordnername kommt in `pruefablauf.json`, in einem Workflow, in
  `tools/pruefstand/pruefen.sh` oder in `Sandbox-Setup.md` vor (die Inventur:
  ein Werkzeug, das niemand ruft, ist ein Kandidat für die Streichliste — der
  Riegel sagt das, mit Namen); (4) keine lose Datei direkt unter `tools/`
  (Q-BR-03). Was der Riegel **nicht** misst: den Inhalt einer Anleitung, ob
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
