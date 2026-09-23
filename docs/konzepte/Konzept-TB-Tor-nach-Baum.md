# Konzept TB — Ein Baum, eine Messung: das Produktionstor vergleicht Inhalt, nicht Commit

**Kürzel:** `TB`. Arbeitspakete `TB-01 …`, Meilensteine der Betreiberin
`TB-M1 …`, Entscheidungen `E-TB-01 …`, Befunde `F-TB-01 …`, Fragen
`Q-TB-01 …`, Prüfpunkte `P-TB-01 …`; Commit-Nachrichten beginnen mit dem
Paket (`TB-01: …`).
**Rahmenplan:** R67 (Auslieferungskette), E-KH-15 (Abstammung statt
Zweigschutz im Tor), E-KH-12 (Überspringen ist rot), E-P5a-12 (eine
Bedingung, die eine Auslieferung verhindert, steht in einem Werkzeug mit
`--selbstprobe`), E-PK-04 bis -06 und E-PK-13 (Konzept PK), `CLAUDE.md` 3,
6 und 7. **Backlog:** Nr. 285 (Anlass, neu mit diesem Konzept); Nr. 278
(Vorgriff auf PK-05, verwiesen).
**Verhältnis zu Konzept PK:** eigenständig und **vor PK-05** umsetzbar. PK-05
(Android und Uhr aus `pruefung.yml`, Gegenlesung des Prüfberichts) bleibt
unverändert offen; TB nimmt ihm nichts weg und legt ihm nichts in den Weg.
Was TB entscheidet, wendet **dieselbe Idee wie E-PK-05** — der Baum-Hash
ist die Identität eines Standes — auf das Produktionstor und den Push-Lauf
auf `main` an.
**Modell:** Konzept Fable, Umsetzung Opus. **Keine Fable-Schritte** in der
Umsetzung (keine Oberfläche, kein Mockup).
**Fächerung (E-PK-35, `CLAUDE.md` 7):** keine. Alle Pakete seriell; drei
davon fassen dieselben zwei Dateien an, und der erzählende Text (Changelog,
Kommentare der Kette) hat einen Ton.
**Ablage:** `docs/konzepte/Konzept-TB-Tor-nach-Baum.md`; Prüfdokument
daneben (`Pruefdokument-TB-Tor-nach-Baum.md`).
**Keine Versionsnummer im Konzept.** Die Pakete fassen `tools/`, `.github/`
und `docs/` an, nach `CLAUDE.md` 2 also keine der drei Zählungen; ob eine
Zeile in `server/` doch nötig wird, entscheidet die Umsetzung und stuft dann
hoch. Der Changelog bekommt einen Eintrag in der Form `[Werkzeug: …]`, wie
die Werkzeug-Einträge vom 22.09.2026.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **23.09.2026 — Konzept geschrieben, nichts umgesetzt.** TB-M1 ist erledigt (die Betreiberin hat „Require branches to be up to date before merging" am 23.09.2026 gesetzt; vorher war es nicht gesetzt, Q-TB-03). Kein PR offen. |
> | Entschieden | E-TB-01 bis E-TB-09 (Abschnitt 3), Q-TB-01 bis -04 beantwortet. |
> | Nächstes | **TB-01** — `freigabe.py` lernt den Baum. |
> | Hakt | nichts. **Zur Kenntnis:** Der gescheiterte Lauf 35834341392 (Tag `web-v20.37.1`) lässt sich **schon vor** dieser Umsetzung wiederholen, sobald der Push-Lauf 35834129884 auf `main` grün ist — er ist mit dem alten Tor zufrieden, weil dann ein Lauf mit derselben SHA existiert. TB verhindert das **nächste** Warten, nicht dieses. |
>
> **Stand der Umsetzung**
>
> | Paket | Stand | Commit | Abnahmezahlen |
> |---|---|---|---|
> | TB-M1 „Up to date" im Ruleset | **gesetzt 23.09.2026** (Betreiberin) | — | war nicht gesetzt → gesetzt; kein PR offen |
> | TB-01 `freigabe.py` lernt den Baum | offen | | |
> | TB-02 Das Tor holt Bäume | offen | | |
> | TB-03 Der Push-Lauf auf `main` sagt, wo schon gemessen ist | offen | | |
> | TB-04 Dokumente und Kommentare | offen | | |
> | TB-05 Abschluss | offen | | |

---

## 0. Auftrag und Anlass

**Anlass (gemessen 23.09.2026):** Der Tag `web-v20.37.1` auf dem
Merge-Commit `10a942c` ist am Produktionstor gescheitert — Lauf
**35834341392**, Job `produktion / ausliefern`, Schritt 7 „Grüner Staging-
und Stufe-1-Lauf auf diesem Stand?":

```
Grüne Stufe-1-Läufe auf diesem Commit: 0
Abstammung vom Zeiger `produktion`:    ja (Vergleich: ahead)
  [JA  ] Lauf 35834130161: Push auf `main`, Job `staging` grün
Anerkannte Staging-Läufe: 1
##[error]Kein grüner Stufe-1-Lauf auf diesem Commit — dieser Stand ist nicht freigabefähig.
```

Nichts war falsch am Stand. Stufe 1 war auf dem Zweig-Commit `f356a44`
grün (PR-Lauf **35830260215**, 42 min, weil der PR `pruefung.yml` selbst
änderte), und der Push-Lauf auf `main` für `10a942c` (**35834129884**) lief
seit 07:53:45 noch, als das Tor um 07:57:06 fragte. **Beide Commits haben
denselben Baum** — `git rev-parse f356a44^{tree} 10a942c^{tree}` liefert
zweimal `447793e41c1ce317c8c4e1daa2ba94ff04bffa55`. Der zweite Lauf misst
Byte für Byte, was der erste gemessen hat, und das Tor wartet auf ihn, weil
es nach der **SHA** fragt (`ausliefern-lauf.yml`, Schritt 7:
`…/runs?head_sha=$sha&status=success`).

**Auftrag:** Das Produktionstor soll einen grünen Stufe-1-Lauf anerkennen,
wenn dessen geprüfter **Inhalt** der Inhalt des Tag-Commits ist — nicht erst,
wenn seine SHA übereinstimmt. Der Push-Lauf auf `main` soll bei gleichem
Inhalt sagen, wo gemessen wurde, statt noch einmal zu messen. Und der
Zweigschutz soll sicherstellen, dass ein Merge-Commit auf `main` inhaltlich
das ist, was der PR-Lauf geprüft hat.

**Was TB nicht ist:** kein Umbau von Stufe 1 (das ist PK-05), keine Änderung
am Transport, am Backup-Tor oder an der Abstammungsprüfung (E-KH-15), keine
Änderung an Stufe 2.

---

## 1. Befund (gemessen 23.09.2026, `main` `10a942c`)

| Nr. | Befund | Folge |
|---|---|---|
| F-TB-01 | **Das Tor zählt Läufe nach SHA.** `ausliefern-lauf.yml` Schritt 7 zählt `pruefung.yml`-Läufe mit `head_sha=$GITHUB_SHA&status=success` und gibt die Zahl als `--stufe1` an `freigabe.py`; `urteil()` schließt bei `stufe1 < 1`. Ein PR-Lauf trägt als `head_sha` den Zweig-Commit, nie den Merge-Commit. | TB-01, TB-02 |
| F-TB-02 | **Der PR-Lauf misst `server/` vollständig.** In `pruefung.yml` hängen genau **3** Schritte an der Bereichserkennung (`if: steps.bereiche.outputs.…`): Java 21, Android-Bau, Uhr-Übersetzung. Jeder Schritt für `server/` läuft bedingungslos; der Kommentar am Filter verbietet ausdrücklich, das zu ändern. **Ein grüner PR-Lauf belegt für die Web-Auslieferung dasselbe wie der Push-Lauf auf `main`.** | Das Tor darf einen PR-Lauf anerkennen, ohne an Umfang zu verlieren (E-TB-02). |
| F-TB-03 | **Der Push-Lauf auf `main` misst immer alles** (Bereichserkennung, Punkt (1)), und die Begründung dafür ist ausdrücklich das Tor: „`main` ist der Stand, den ein Tag ausliefert — Tor 3 des Produktionslaufs verlangt einen grünen Stufe-1-Lauf auf genau diesem Commit." Bis PK-05 heißt „alles" 42–56 Minuten; danach ein bis zwei. | Der Satz fällt mit TB weg und wird ersetzt (TB-03, TB-04). |
| F-TB-04 | **Ein `pull_request`-Lauf prüft nicht den Zweig-Commit, sondern den Probe-Merge** (`refs/pull/N/merge`) mit dem `main` zum Zeitpunkt des Laufs; die Bereichserkennung sagt das selbst („Bei einem PR checkt die Aktion den MERGE-Commit aus"). Die Lauf-API nennt nur `head_sha` (den Zweig-Commit). Ohne Zusatzannahme ist Baum(`head_sha`) **nicht** beweisbar der geprüfte Baum. | Mit TB-M1 wird es beweisbar (Abschnitt 2.2, E-TB-03). |
| F-TB-05 | **„Require branches to be up to date before merging" war nicht gesetzt.** Die Maske in `Rahmenplan.md` 6b führt das Unterfeld nicht; `Pruefablauf.md` 2.3 nennt es nicht. Die Betreiberin hat es am 23.09.2026 nachgesehen und gesetzt (TB-M1, Q-TB-03). | Maske und 2.3 nachziehen (TB-04). |
| F-TB-06 | **Die Lehre aus F-KH-U-35 gilt auch hier:** Ein Lauf ist „success", wenn kein Job rot ist — ein **übersprungener** Job `Stufe 1` macht den Lauf nicht rot. Wer nach TB-03 Läufe auf `main` zählt, bei denen `Stufe 1` planmäßig übersprungen wurde, zählt einen Verweis als Messung. | Das Tor **und** der Verweis prüfen die Job-Conclusion, nicht die Lauf-Conclusion (E-TB-05). |
| F-TB-07 | **Zwei Namen für ein Ruleset.** `Rahmenplan.md` 6b nennt es „main geschützt", `Pruefablauf.md` 2.3 und Konzept PK „Main Protect". Bei GitHub heißt es **„Main Protect"** (Q-TB-04, Betreiberin 23.09.2026). | TB-04 berichtigt `Rahmenplan.md` 6b auf „Main Protect". |

---

## 2. Das Verfahren

### 2.1 Zwei Vergleiche, unabhängig voneinander

Es gibt nach TB **zwei** Stellen, die „schon gemessen" entscheiden, und sie
teilen keinen Code und kein Ergebnis:

1. **Das Produktionstor** (`ausliefern-lauf.yml` Schritt 7 → `freigabe.py`).
   Es entscheidet über ausgelieferten Code; die Entscheidung liegt deshalb in
   einem Werkzeug mit `--selbstprobe` (E-P5a-12, wie heute). Es holt sich
   seine Tatsachen **selbst** — Bäume, Läufe, Job-Ergebnisse — und verlässt
   sich nicht auf das, was der Push-Lauf auf `main` gemeldet hat.
2. **Der Push-Lauf auf `main`** (`pruefung.yml`, TB-03). Er entscheidet nur,
   ob er **selbst** noch einmal misst. Irrt er in Richtung „schon gemessen",
   obwohl der Baum abweicht, dann fehlt dem Tor später schlicht ein Lauf mit
   passendem Baum, und das Tor bleibt zu. **Ein Fehler in (2) kann (1) nicht
   öffnen.** Das ist der Grund, warum (2) als Schritt in der Kette stehen darf
   und nicht in ein Werkzeug mit Selbstprobe muss — die Grenze, die der
   Filterkommentar zieht („eine Bedingung, die über ausgelieferten Code
   entscheidet, gehört in ein Werkzeug mit `--selbstprobe`"), bleibt gewahrt.

### 2.2 Warum der Baum von `head_sha` reicht — mit TB-M1

Mit „Require branches to be up to date" gilt beim Merge: Der Zweig-Commit H
enthält den Kopf von `main`, M. Dann ist der Probe-Merge von H und M
inhaltlich H (nichts in M fehlt in H), und der Merge-Commit T auf `main` —
ob echter Merge, Squash oder Rebase — hat ebenfalls den Baum von H. Also
**Baum(T) = Baum(H) = das Geprüfte**, und das Tor vergleicht den Baum des
Tag-Commits mit dem Baum von `head_sha` jedes grünen Laufs. Beides ist ohne
Klon zu haben: den Tag-Commit aus dem ausgecheckten Arbeitsverzeichnis
(`git rev-parse HEAD^{tree}` — der Auscheckschritt liegt vor dem Tor), den
Zweig-Commit über `GET /repos/{o}/{r}/git/commits/{sha}` → `.tree.sha`.

Ohne TB-M1 stimmte die Gleichung „meistens" (ein Drei-Wege-Merge identischer
Änderungen ergibt H), aber nicht beweisbar. Und auch dann bliebe TB sicher,
nur unbequem: Weicht der Baum ab, findet das Tor keinen passenden Lauf und
verlangt — wie heute — den Push-Lauf auf `main`, der bei abweichendem Baum
voll misst (2.4). **TB fällt nie unter die heutige Strenge, nur auf sie
zurück.**

### 2.3 Das Tor (TB-01, TB-02)

Schritt 7 holt statt einer Zahl eine Liste:

1. `baum=$(git rev-parse HEAD^{tree})` — der Baum des Tag-Commits. Leer oder
   Fehler → das Tor bleibt zu (im Zweifel nein, wie `abstammt()`).
2. Die jüngsten **50** erfolgreichen Läufe von `pruefung.yml`, alle
   Ereignisse (`…/workflows/pruefung.yml/runs?status=success&per_page=50`),
   je Lauf `id`, `event`, `head_branch`, `head_sha`.
3. Je Lauf der Baum von `head_sha` (`git/commits/{sha}` → `.tree.sha`;
   Fehler → leer). Nur für Läufe mit **passendem Baum** dann die Jobs
   (`…/runs/{id}/jobs`): zählt der Job `Stufe 1` mit `conclusion ==
   "success"`? — genau wie der Staging-Job heute (F-TB-06).
4. `freigabe.py urteil --laeufe laeufe.json --vergleich … --baum "$baum"
   --stufe1-laeufe stufe1.json`. Das Werkzeug zählt die Läufe, deren Baum
   gleich `--baum` ist **und** deren `stufe1_ok` wahr ist; `< 1` → zu. Das
   Protokoll nennt jeden Lauf mit Baum, Ereignis und Urteil, wie heute die
   Staging-Läufe.

Der Schalter `--stufe1 N` fällt ersatzlos; `kettenaufrufe` (Stufe 1, „passt
jeder Aufruf zu seinem Werkzeug?") muss den neuen Aufruf kennen.

Das Fenster von 50 Läufen ist kein Sicherheitswert, sondern eine Kostengrenze
(höchstens 50 `git/commits`-Abrufe, die Jobs nur bei Treffern). Ein Tag auf
einen Stand, dessen grüner Lauf älter als 50 Läufe ist, findet ihn nicht —
das Tor bleibt zu, die Meldung sagt „kein Lauf mit Baum … unter den letzten
50", und der Weg ist ein Handlauf von `pruefung.yml` auf dem Stand (heute
schon vorgesehen für `hotfix/*`).

**Hotfix-Weg (E-KH-15):** unverändert. Ein Handlauf auf `hotfix/*` erzeugt
einen Lauf mit derselben SHA und damit demselben Baum; die Abstammung wird
weiter geprüft, TB ändert daran nichts.

### 2.4 Der Push-Lauf auf `main` (TB-03)

Ein neuer, kleiner Job **vor** `Stufe 1` und `Schema gegen …` — Arbeitsname
`schon-gemessen`, Anzeigename „Schon gemessen?" — mit einer Ausgabe
`messen=ja|nein`:

- **Nicht Push auf `main`** (also `pull_request`, `workflow_dispatch`):
  `messen=ja`, ohne Netzzugriff. Ein PR misst immer.
- **Push auf `main`:** eigener Baum (`git rev-parse HEAD^{tree}`), dann die
  jüngsten erfolgreichen `pull_request`-Läufe (Fenster 30), je Lauf der Baum
  von `head_sha` per API, beim ersten Treffer die Jobs — `Stufe 1` muss
  `success` sein (F-TB-06; sonst zählte ein Lauf, der selbst nur verwiesen
  hat). Treffer → `messen=nein` und **eine Zeile in die Zusammenfassung**:
  „Baum `447793e…` bereits gemessen: Lauf 35830260215 (`pull_request`,
  `f356a44`), Job `Stufe 1` grün am 23.09.2026 07:51 UTC — nicht noch einmal
  gemessen." Kein Treffer, API-Fehler, leerer Baum → `messen=ja` (**im Zweifel
  messen**, wie die Bereichserkennung).

`Stufe 1` und `Schema gegen …` bekommen `needs: schon-gemessen` und
`if: needs.schon-gemessen.outputs.messen == 'ja'`. Der Jobname `Stufe 1`
bleibt (der Zweigschutz nennt ihn). Auf einem PR ändert sich nichts — der
Job läuft immer, und das ist der Fall, den das Ruleset prüft.

**Was auf `main` dann zu sehen ist:** ein grüner Lauf mit einem Job, der
den Verweis trägt, und zwei übersprungenen Jobs. Das ist **kein stilles
Überspringen** im Sinne von Grundsatz 7: Die Auslassung nennt ihren
Gegenstand (Lauf, Commit, Baum, Zeitpunkt), und wer nachprüfen will, hat
die Nummer.

### 2.5 Was bleibt, wie es ist

- Stufe 2 auf Staging, das Backup-Tor, die Abstammung, der Zeiger
  `produktion`, die Ausnahmeliste des FTPS-Schritts: unberührt.
- Die Bereichserkennung: unberührt in ihrer Logik; nur der Kommentar (1)
  wird berichtigt (F-TB-03). Ihr Satz „auf `main` wird immer alles gemessen"
  bleibt wahr **für den Fall, dass gemessen wird** — und der Job davor
  entscheidet, ob.

---

## 3. Entscheidungen

**E-TB-01 — A und B, nicht A oder B.** A (das Tor vergleicht Bäume) allein
ist sicher, aber der Vergleich über `head_sha` nicht beweisbar (F-TB-04); B
(„up to date") allein lässt das Tor weiter warten, weil die SHA verschieden
bleibt. Zusammen: B macht die einfache Form von A beweisbar (2.2), A macht
das Warten überflüssig. Der Preis von B — nach einem fremden Merge `main`
nachziehen und Stufe 1 neu bestehen — trifft nur den Fall zweier
gleichzeitig offener PRs, und das ist genau der Fall, in dem eine zweite
Messung etwas Neues misst. **Vom Auftraggeber entschieden am 23.09.2026.**

**E-TB-02 — Ein grüner PR-Lauf gilt dem Tor so viel wie ein Push-Lauf auf
`main`.** Begründet durch F-TB-02: Für `server/` messen beide dasselbe. Was
der PR-Lauf auslässt (Android, Uhr), liefert die Kette nicht aus.

**E-TB-03 — Der Baum wird verglichen, nicht die SHA; der Baum des Tag-Commits
kommt aus dem Arbeitsverzeichnis, der des Lauf-Commits aus der API.** Kein
`fetch-depth: 0`, kein Klon der Historie im Auslieferungslauf. Ein
annotierter Tag stört nicht: `git rev-parse HEAD^{tree}` löst ihn auf.

**E-TB-04 — Im Zweifel nein (Tor) und im Zweifel messen (Push-Lauf).** Ein
leerer Baum, eine gescheiterte API-Antwort, ein unbekanntes Feld: Am Tor
heißt das „zählt nicht", im Push-Lauf „messen". Beide Richtungen sind die
sichere.

**E-TB-05 — Job-Conclusion, nicht Lauf-Conclusion.** Sowohl das Tor als auch
der Verweis auf `main` zählen einen Lauf nur, wenn der Job `Stufe 1` selbst
`success` ist. Ein Lauf, in dem `Stufe 1` planmäßig übersprungen wurde
(2.4), ist ein Verweis und keine Messung (F-TB-06, Lehre aus F-KH-U-35).

**E-TB-06 — Der Push-Lauf auf `main` misst bei gleichem Baum nicht und sagt,
wo gemessen wurde** (Variante (i) aus dem Gespräch). Alternative (ii) — er
misst weiter voll, nur das Tor wartet nicht — war die Gegenlesung auf
frischer Umgebung; sie kostete nach PK-05 ein bis zwei Minuten je Merge, bis
dahin 42 bis 56. Entschieden für (i): Grundsatz 3 wörtlich, E-PK-06
sinngemäß, und der Verweis mit Nummer ist nachprüfbar. **Vom Auftraggeber
entschieden am 23.09.2026.** Wer (ii) später will, streicht das `if:` an
den beiden Jobs — der Verweis-Job bleibt als Auskunft stehen.

**E-TB-07 — Zwei Vergleiche, kein geteilter Zustand** (2.1). Das Tor holt
seine Tatsachen selbst. Ein Fehler im Verweis auf `main` kann das Tor nicht
öffnen, nur schließen.

**E-TB-08 — Die Entscheidung des Tors bleibt in `freigabe.py`, der Verweis
auf `main` darf Bash sein.** Das Tor entscheidet über ausgelieferten Code
(E-P5a-12); der Verweis entscheidet nur, ob ein Lauf sich selbst wiederholt,
und sein schlimmster Fehler ist ein geschlossenes Tor (E-TB-07).

**E-TB-09 — Fenster 50 am Tor, 30 auf `main`; beides Kostengrenzen.** Wer
darüber hinaus will, fährt einen Handlauf von `pruefung.yml` auf dem Stand.
Die Zahlen stehen als Konstanten mit Begründung im Arbeitslauf, nicht als
Prüfwert in einem Dokument.

### 3.1 Fragen an den Auftraggeber — beantwortet

| Nr. | Frage | Antwort (23.09.2026) |
|---|---|---|
| Q-TB-01 | A + B, mit Variante (i) für den Push-Lauf auf `main`? | **Ja.** |
| Q-TB-02 | Als Paket im PK-Konzept oder eigenes Konzept? | **Eigenes Konzept**, wird sofort von einer anderen Instanz umgesetzt. |
| Q-TB-03 | Ist „Require branches to be up to date before merging" gesetzt? | **War nicht gesetzt; jetzt gesetzt** (TB-M1). Kein PR offen. |
| Q-TB-04 | Wie heißt das Ruleset mit der PR-Pflicht bei GitHub wirklich — „main geschützt" oder „Main Protect"? (F-TB-07) | **„Main Protect".** `Rahmenplan.md` 6b ist zu berichtigen (TB-04). |

---

## 4. Arbeitspakete

Reihenfolge fest; jedes Paket ein Commit, danach Konzept fortschreiben und
Zweig pushen (`CLAUDE.md` 7 und 8). **Modell je Paket: Opus. Fächerung je
Paket: keine.**

### TB-M1 — „Require branches to be up to date" (Betreiberin) — erledigt

Gesetzt am 23.09.2026 unter *Settings → Rules → Rulesets → [Ruleset mit
PR-Pflicht] → Require status checks to pass → Require branches to be up to
date before merging*. Vorher nicht gesetzt (F-TB-05). **Wirkung ab sofort für
jeden PR:** Ein PR, der den Kopf von `main` nicht enthält, bekommt „Update
branch" und muss Stufe 1 danach neu bestehen — **das gilt auch für den PR
dieser Umsetzung.**

### TB-01 — `freigabe.py` lernt den Baum

- **Dateien:** `tools/kette/freigabe.py`, `tools/kette/LIESMICH.md`.
- `urteil()` bekommt statt `stufe1: int` eine Liste `stufe1_laeufe`
  (`[{id, event, head_branch, head_sha, baum, stufe1_ok}]`) und den
  Zielbaum `baum`. Ein Lauf zählt, wenn `baum` gleich dem Zielbaum ist
  (Zeichenkette, 40 Hexziffern, kleingeschrieben) **und** `stufe1_ok`
  wahr ist. Leerer oder fehlender Zielbaum → Urteil 1 mit eigener
  Meldung. Jede Zeile des Protokolls nennt Lauf, Ereignis, Zweig, Baum
  (kurz) und den Grund — wie `lauf_zaehlt()` für Staging.
- Schnittstelle: `--baum <sha>` und `--stufe1-laeufe <datei|->`;
  `--stufe1` fällt ersatzlos.
- **Selbstprobe erweitern** — mindestens: gleicher Baum + Job grün → zählt;
  gleicher Baum + Job übersprungen/rot → nein; anderer Baum + Job grün →
  nein; Baum leer im Lauf → nein; Zielbaum leer → Tor zu; Zielbaum in
  Großschreibung → zählt trotzdem (Normalisierung); Liste keine Liste →
  Tor zu; ein Lauf zählt, obwohl ein anderer nicht → Tor auf. Die
  Staging-Lagen bleiben unverändert erhalten.
- **Abnahme:** `python3 tools/kette/freigabe.py --selbstprobe` — Zahl
  „erfüllt / offen" in der `LIESMICH.md` nachziehen (heute 32/0); die neuen
  Lagen sind gezählt und benannt.

### TB-02 — Das Tor holt Bäume

- **Dateien:** `.github/workflows/ausliefern-lauf.yml` (Schritt 7),
  `tools/kettenaufrufe/` (Erwartung an den Aufruf).
- Umsetzung nach 2.3. Die Zahl der Läufe im Fenster, die Zahl der Treffer
  und die Zahl der API-Fehler stehen im Protokoll („50 Läufe geholt, 2 mit
  Baum `447793e…`, 0 Abrufe gescheitert").
- Der Kommentar des Schritts wird auf das Nötige gekürzt und sagt in einem
  Satz, warum Baum statt SHA (F-TB-01) und warum Job statt Lauf (F-TB-06).
- **Abnahme:** `python3 tools/kettenaufrufe/pruefen.py` 0 Befunde;
  `grep -c 'stufe1 ' .github/workflows/ausliefern-lauf.yml` → 0 (der alte
  Schalter ist weg); Probelauf der Kette (`probelauf: true`) grün — er
  überspringt das Tor, belegt aber die Syntax; die Selbstprobe aus TB-01
  läuft in Stufe 1 („Das Tor der grünen Läufe") grün.

### TB-03 — Der Push-Lauf auf `main` sagt, wo schon gemessen ist

- **Dateien:** `.github/workflows/pruefung.yml`.
- Job `schon-gemessen` nach 2.4, `needs`/`if` an `Stufe 1` und
  `Schema gegen …`. Der Kopfkommentar (Zeilen „WANN SIE LAUFEN" bis „Auf
  `main` bleibt der Push-Auslöser …") und Punkt (1) der Bereichserkennung
  werden berichtigt (F-TB-03): „Auf `main` misst der Lauf alles — **wenn**
  er misst; ob er misst, entscheidet der Job davor am Baum (Konzept TB)."
- Jede fremde `uses:`-Zeile trägt weiter eine 40-stellige SHA; die Zählung
  aus `CLAUDE.md` 3 nach dem Umbau wiederholen (ein weiterer
  `actions/checkout` kommt hinzu).
- **Abnahme:** Auf dem Arbeitszweig ist im PR-Lauf `schon-gemessen`
  „ja" und `Stufe 1` läuft — gemessen an der Lauf-Nummer. Der Fall
  „Push auf `main` mit gleichem Baum" ist **im Container nicht messbar**
  (Prüfdokument 0) und wird nach dem Merge an der Lauf-Nummer belegt
  (P-TB-05).

### TB-04 — Dokumente und Kommentare

- `CLAUDE.md` 3: „Der Produktionslauf verlangt einen grünen Stufe-1-Lauf
  auf demselben Commit" → „auf demselben **Baum** — ein grüner PR-Lauf
  zählt, ein Lauf mit übersprungener Stufe 1 nicht"; der Absatz „Stufe 1
  läuft … auf `main` bei jedem Push" bekommt den Satz, dass der Lauf auf
  `main` bei gleichem Baum verweist statt misst. Dazu der Hinweis, dass
  „up to date" im Ruleset gesetzt ist und ein PR nach fremdem Merge
  nachziehen muss.
- `docs/Pruefablauf.md` 2.3: „up to date" in der Beschreibung des Rulesets;
  der Absatz „Heutiger Umfang" trägt den neuen Job.
- `docs/Rahmenplan.md` 6b: Maske um die Zeile „— Require branches to be up
  to date before merging | ☑" ergänzen, dazu eine vierte Falle: „Der PR
  muss `main` enthalten — nach einem fremden Merge ‚Update branch' und
  Stufe 1 neu." Name der Maske auf **„Main Protect"** berichtigen (F-TB-07, Q-TB-04).
- `docs/Technik.md` 6 (Kette): der Satz zum Tor.
- `docs/CHANGELOG.md`: ein Eintrag `[Werkzeug: …]`, erklärende Prosa mit
  dem Anlass (Lauf 35834341392) und dem Preis (das Fenster, der PR nach
  fremdem Merge).
- `docs/Backlog.md`: Nr. 285 nach *Erledigt* — erst mit TB-05.
- **Abnahme:** Linkprobe 0 Abweichungen; Textprobe 0 neue Treffer;
  `grep -rn "demselben Commit" CLAUDE.md docs/` → 0 Treffer außerhalb der
  Geschichte (Changelog, Konzepte).

### TB-05 — Abschluss

- Konzept-Statusblock final, Prüfdokument mit allen Zahlen, Backlog Nr. 285
  nach *Erledigt*, PR öffnen (die Betreiberin mergt). **Nach** dem Merge:
  P-TB-05 (Verweis auf `main`) und P-TB-06 (ein Tag ohne Warten) an
  Lauf-Nummern belegen; dann Erledigt-Zeile in `Rahmenplan.md` 8, Konzept
  löschen, Prüfdokument bleibt, bis P-TB-05/06 abgehakt sind (`CLAUDE.md` 7).

---

## 5. Kosten und Ertrag

- **Spart:** je Auslieferung das Warten auf den Push-Lauf (heute 42–56 min,
  nach PK-05 1–2 min) und je Merge einen Lauf, der Gemessenes noch einmal
  misst.
- **Kostet:** bis zu 50 + n API-Abrufe am Tor und bis zu 30 + 1 im Push-Lauf
  (Sekunden); je fremdem Merge bei offenem PR ein Nachziehen und einen
  PR-Lauf (TB-M1).
- **Verschiebt nichts nach Produktion:** Das Tor akzeptiert nie weniger als
  heute — nur früher.

---

## 6. Offene Fragen und Befunde der Umsetzung

Neue Befunde `F-TB-08 …`, neue Fragen `Q-TB-05 …`, neue Entscheidungen
`E-TB-10 …`. Ausführlich mit Messwerten im Prüfdokument.

| Nr. | Aus | Befund / Frage | Folge |
|---|---|---|---|
| — | | *noch keine* | |
