# Prüfablauf — jede Prüfung einmal, an ihrer Stelle

*Stand: 23.09.2026 · Arbeitsumgebung: `Sandbox-Setup.md` · Architektur und
Betrieb: `Technik.md` · Steuerung: `Rahmenplan.md` · Arbeitsanweisung:
`CLAUDE.md`.*

Dieses Dokument sagt, **wo** eine Prüfung läuft, **wann** sie läuft und
**wer** sie fährt. Es ist die eine Stelle dafür. `CLAUDE.md` 6 nennt seit
PK-01 nur noch die Grundsätze und verweist hierher; wer eine Prüfregel in
einem Kettenkommentar, in einer alten Sitzung oder in einem Konzept findet,
liest sie hier nach, bevor er sie anwendet.

Aufgestellt mit **Konzept PK** (Prüfkette), freigegeben am 21.09.2026. Der
Anlass steht in einem Satz: Am selben Tag brauchte Stufe 2 der Kette drei
Läufe zu je fünfzehn Minuten, um einen HTTP 500 beim Export zu finden,
dessen Ursache in einem Protokoll lag, an das niemand herankam. Lokal wäre
derselbe Export in Sekunden gescheitert. Nicht die Kette war falsch — der
**Ort der Arbeit** war es.

---

## 0. Was schon gilt und was noch entsteht

Dieses Dokument beschreibt die Prüfkette vollständig. **Gebaut sind die
Stationen A, B und C**; Staging folgt mit PK-06. Die
Spalte **Stand** sagt bei jedem Stück, woran man ist; ein Stück ohne „gilt"
oder „gebaut" ist eine Vorgabe an das genannte Paket, keine Beschreibung der
Gegenwart. Wer das verwechselt, meldet eine Prüfung als gefahren, die es
nicht gibt.

| Stück | Stand |
|---|---|
| Die sieben Grundsätze (1) | **gilt** |
| Stationen A, D, E (2) | **gilt** — so läuft es heute |
| Zweigschutz und Merge-Recht auf `main` (2, Station C) | **gilt seit 21.09.2026** (zwei Rulesets, PK-M1) |
| Regeln für Prüfmittel (6) | **gilt** |
| Benennung (7) | **gilt** |
| Was nicht geprüft wird (8) | **gilt** |
| Arbeitsumgebung in fünf Ausbaustufen | **gebaut mit PK-02** (`Sandbox-Setup.md` 2), `emulator` mit Konzept AR (25.09.2026); `web`, `plattform`, `android` und `emulator` gemessen, **`uhr` noch nicht** |
| Station B, der Prüfstand-Befehl, die drei Stufen (3) | **gebaut und gemessen mit PK-03** |
| `pruefablauf.json`, die Tabelle Berührung → Probe (4) | **gebaut mit PK-03**, Tabelle erzeugt |
| Der Prüfbericht (5) | **gebaut mit PK-03**, seit PK-05 Selbstprobe 13 Lagen / 0 Fehlschläge |
| Seine Gegenlesung im Tor (5.1) | **gebaut mit PK-05** |
| Station C in der beschriebenen Form (2) | **gebaut mit PK-05** — die Schemaprobe wird Pflichtprüfung, sobald das Ruleset es trägt (E-PK-47) |
| Station D in der beschriebenen Form (2) | entsteht mit **PK-06** |

Bis dahin gilt für Station D, was in `.github/workflows/auslieferung.yml`
steht; Abschnitt 2.4 nennt den heutigen Umfang mit Zahl.

---

## 1. Die sieben Grundsätze

1. **Jede Prüfung hat genau eine Stelle.** Sie steht hier, nicht in einem
   Kommentar und nicht im Gedächtnis.
2. **Arbeit in der Arbeitsumgebung, Riegel in der Kette.** Was Minuten
   kostet und Fehler *findet*, läuft örtlich. Was Sekunden kostet und Fehler
   *aufhält*, läuft im Tor. Die Anlage misst nur, was nur die Anlage zeigen
   kann.
3. **Zweimal nur die Gegenlesung.** Das Tor wiederholt die billigen Riegel
   und hält sie gegen den Prüfbericht. Sonst wird nichts zweimal gemessen.
4. **Der Umfang folgt der Änderung, nicht dem Kalender.** Eine
   Korrekturstufe prüft, was sie berührt; eine Nebenstufe alles, was ohne
   Anlage geht; eine Hauptstufe auch Mengen und die Plattformmatrix (3).
5. **Ein Prüfmittel braucht einen Fehler.** Jedes Werkzeug nennt in einer
   Zeile, welchen Fehler es gefangen hat oder hätte fangen müssen, als
   Verweis auf eine Backlog-Nummer (6.1). Ohne diese Zeile ist Stufe 1 rot
   (seit Konzept BR; bis dahin stand es „auf der Streichliste").
6. **Geschichte steht im Commit, nicht im Werkzeug.** Eine Anleitung sagt,
   was das Werkzeug tut; was es einmal gefunden hat, sagen Commit-Nachricht,
   Backlog und Changelog.
7. **Kein stilles Überspringen, keine grüne Zahl ohne Gegenstand.** Ein
   Prüfschritt, der sich selbst überspringt, meldet grün, ohne gemessen zu
   haben — er bricht ab (E-KH-12). Und eine Zahl belegt erst dann etwas,
   wenn sie benennt, was sie gezählt hat (6.5). **Die eine Auslassung, die
   kein Überspringen ist,** steht in 2.3: Auf `main` misst Stufe 1 nicht
   noch einmal, wenn ein grüner PR-Lauf denselben Baum gemessen hat — und
   nennt dafür Lauf, Commit und Zeitpunkt. Ein Verweis mit Beleg, kein
   stilles Grün.

---

## 2. Die fünf Stationen

„Stelle" heißt hier **Station**: ein Haltepunkt auf dem Weg vom Quelltext
zum Produktivserver. Der Weg hat fünf davon, und jede hat eine Frage, die
nur sie beantworten kann.

| Station | Wann | Wer | Was dort geprüft wird | Dauer (Ziel) |
|---|---|---|---|---|
| **A Arbeit** | während der Entwicklung | die Instanz in der Arbeitsumgebung | Browser, Emulator, Uhr-Simulator, die Proben, die zur Änderung gehören | nach Bedarf |
| **B Prüfstand** | vor dem Pull Request, **ein Befehl** | dieselbe Instanz | örtliche Installation hochfahren, Referenzbestand, die Proben der Stufe, Bau der Apps (unsigniert), die billigen Riegel; auf Anforderung die Plattformprobe gegen Staging. Ergebnis ist der **Prüfbericht** (5) | klein 5 min, neben **rund 21 min** (E-RP-05), haupt 45 min |
| **C Tor** | beim Pull Request und auf `main` | GitHub | die billigen Riegel als Gegenlesung des Berichts, Schemaprobe gegen MySQL 8.4.0 und MariaDB 10.6, Bericht passt zum Baum und zur Berührung. **Rot in `Stufe 1` heißt kein Merge** — und in der Schemaprobe ebenso, sobald das Ruleset sie trägt (E-PK-47); der Merge ist Sache der BetreiberIn | 1 bis 2 min |
| **D Staging** | nach dem Merge | GitHub | Auslieferung (unverändert), dann drei Schritte: Antwortprobe, Punktdateien, **ein** Kreislauf `edbak` als Gegenlesung auf PHP 8.3 beim Hoster | unter 10 min |
| **E Produktiv** | beim Tag, nach Freigabe | GitHub | dieselbe Auslieferung, Backup-Tor, Fassungsprüfung, Integritätswache; App-Tags bauen und signieren einmal | wie heute |

### 2.1 Station A — Arbeit

Hier wird der Fehler **gefunden**. Alles, was einen Umlauf von Sekunden
braucht, gehört hierher: der Browser, der Emulator, der Uhr-Simulator, die
Probe des Endpunkts, den die Änderung anfasst. Die Arbeitsumgebung dafür
beschreibt `Sandbox-Setup.md`.

**Der Weg im Browser** (`/chrome`): Seite öffnen, **Konsole lesen**, den Weg
durchklicken, den die Änderung betrifft — und die Fassungen mitprüfen, die
dieselbe Regel benutzen. Denn **die Oberfläche teilt sich Bausteine**: Wer
`.btn-plain` ändert, ändert ein Dutzend Seiten. Mitprüfen heißt, die Seiten
mitzuprüfen, die dieselbe Regel benutzen — nicht nur die, an der die
Änderung entstand.

**Was nicht gemessen werden konnte, wird gesagt**, nicht als erledigt
gemeldet. Ein Punkt, der nicht gemessen werden konnte, gehört im Prüfdokument
an den **Anfang**, unter „was nicht geprüft werden konnte und warum", nicht
in eine Fußnote.

### 2.2 Station B — der Prüfstand

**Ein Befehl vor dem Pull Request**, `bash tools/pruefstand/pruefen.sh`. Er
ermittelt die Stufe selbst (3), fährt die örtliche Installation hoch, läuft
die Proben, die zur Berührung gehören (4), und **erzeugt** am Ende den
Prüfbericht (5).

**Was nicht laufen kann, wird gezählt und benannt, nicht übersprungen**
(E-KH-12): Fehlt das Android-SDK, `CIQ_GERAETE_URL` oder das Modul
`plattform` oder ein Umgebungswert, den der Aufruf nennt, meldet der Lauf
die Probe als *nicht gemessen* — mit Grund —, sagt am Ende „n rot, m nicht
gemessen, k grün" und endet mit rc 1. **Seit PK-05 steht sie auch im
Bericht**, als `name=nicht-gemessen`, und das Tor liest sie als rot
(E-PK-46). Eine übersprungene Probe, die grün meldet, gibt es nicht.

Station B ist die geprüfte Partei. Ihr Bericht ist ein **Nachweis**, kein
Riegel — was das heißt und wo die Grenze liegt, steht in 5.2.

### 2.3 Station C — das Tor

Beim Pull Request und auf `main` — dazu beim Handlauf, und der liest auf
einem Zweig ebenso gegen wie ein PR (E-PK-43). **Pushes sind Pushes**:
Ein Push auf einen Arbeitszweig löst keine Prüfung aus, und es gibt keinen
Riegel davor und keinen Hook.

**Der Riegel ist der Zweigschutz, und er gilt seit dem 21.09.2026.** Zwei
Rulesets auf `main`: „Main Protect" (Pull-Request-Pflicht, Pflichtprüfung
`Stufe 1` mit **„Require branches to be up to date before merging"** — gesetzt
am 23.09.2026, Konzept TB —, kein Force-Push, kein Löschen, Bypass leer) und „Main
Merge-Recht" (Restrict updates, Bypass nur die BetreiberIn, Modus „pull
requests only"). Die Maske und die Fallen dabei stehen in
`Rahmenplan-Archiv-2.md` 6b (`Rahmenplan.md` 6b verweist dorthin).

> **Ohne Zweigschutz ist jedes Tor eine Auskunft.** Der Lauf färbt sich rot,
> und der Stand liegt trotzdem auf `main` — und damit auf Staging. Das war
> der Zustand bis zum 21.09.2026; wer eine ältere Quelle liest, findet dort
> `protected: false`.

**Und der Zweigschutz allein reicht nicht.** Gemessen am 21.09.2026
(F-PK-01): Das Ruleset weist das Konto ab, unter dem die Arbeitsumgebung
pusht — aber die GitHub-Werkzeuge in Claude Code sprechen mit dem Anschluss
der BetreiberIn und handeln als sie; ein Merge über die Schnittstelle ging
durch. Deshalb drei Lagen statt einer:

1. **Das Ruleset** — Riegel gegen Git und gegen jede fremde Anwendung.
2. **Die Deny-Liste in `.claude/settings.json`** — sie sperrt
   `mcp__github__merge_pull_request` und
   `mcp__github__enable_pr_auto_merge`.
3. **Der Satz in `CLAUDE.md` 8** — eine Instanz mergt nie.

**Wie Lage 2 wirkt, und wie man sie deshalb misst.** Eine Deny-Regel aus dem
bloßen Werkzeugnamen nimmt das Werkzeug **ganz** aus dem Zusammenhang; es
gibt dann keinen abgewiesenen Aufruf, weil es nichts mehr aufzurufen gibt.
**Gemessen wird also die Abwesenheit, nicht eine Abweisung** — und immer mit
Gegenprobe, sonst verwechselt man einen ausgefallenen Anschluss mit einem
wirksamen Riegel. Gemessen am 21.09.2026: die beiden gesperrten Werkzeuge
nicht mehr auffindbar, **drei** andere Werkzeuge desselben Anschlusses
unverändert ladbar.

**Zwei Wege bleiben offen, und sie werden hier benannt statt verschwiegen:**

- **Klammern machen die Regel still wirkungslos.** Eine Zeile
  `mcp__github__merge_pull_request(…)` wird beim Laden übersprungen — die
  Datei sähe streng aus und sperrte nichts. Der Name steht ohne Klammern.
- **`.claude/settings.local.json` steht in der Rangfolge über der geteilten
  Datei** und ist heute nicht in `.gitignore`. Entstünde sie, wäre sie
  wenigstens als unverfolgte Datei sichtbar — aber sie hebt Lage 2 auf, ohne
  im Pull Request zu erscheinen.

Wer die versionierte Deny-Liste ändert, hebt Lage 2 ebenfalls auf; das fällt
im Pull Request auf, weil `.claude/settings.json` dann in der Berührung
steht.

**Heutiger Umfang** (gezählt am 23.09.2026 am Quelltext, nach PK-05): Der
Arbeitslauf `pruefung.yml` heißt „Prüfung" und führt drei Jobs —
**`Schon gemessen?`** mit **2** Schritten, `Stufe 1` mit **15** und
`Schema gegen …` mit **4**; bis PK-05 waren es in `Stufe 1` **24**, bis BR-03
**17** — drei Schritte sind in die Quelltextprüfungen gewandert (`bestand`,
`pysyntax`, `handbuch`), einer kam dazu (`cmark-gfm` bereitstellen). Den
ersten Job brachte Konzept TB am 23.09.2026:
Auf einem PR sagt er „messen"; beim Push auf `main` sucht er den grünen
PR-Lauf mit demselben Baum (`tools/kette/baumsuche.py`, dieselbe Suche wie
im Produktionstor, E-BR-09) und lässt `Stufe 1` und `Schema gegen …` dann
aus, mit einem Verweis auf Lauf, Commit und Zeitpunkt in der
Zusammenfassung. Das Produktionstor zählt Stufe 1 seither nach Baum und Job,
nicht nach Commit. **PK-05** hat dem Lauf den Bau der Apps und die
Bereichserkennung abgenommen und die Gegenlesung des Berichts an ihre Stelle
gesetzt (5.1). Der Jobname `Stufe 1` ist dabei unverändert geblieben — der
Zweigschutz nennt ihn beim Namen, und ein umbenannter Job hängt die
Pflichtprüfung still ab.

### 2.4 Station D — Staging

Nach dem Merge, auf `main`. Die Auslieferung selbst bleibt, wie Kette II sie
gebaut hat (`ausliefern-lauf.yml`, **17** Schritte, gemessen 49 s); PK fasst
den Transport nicht an. Verschlankt wird, was danach misst.

**Drei Schritte sollen bleiben**, weil nur die echte Anlage sie beantwortet:
antwortet `login.php` wie eine eingerichtete Installation, sind Punktdateien
gesperrt und ist `.well-known` offen (beides Apache des Hosters), und läuft
**ein** Kreislauf `edbak` durch — als Plattformprobe auf PHP 8.3 beim
Hoster. Dazu ein benannter leerer Platz für Backlog Nr. 234 (8).
**Seit Web 20.45.0 kommt die Rückwegprobe dazu** (Konzept RW, E-RW-11): Ob
die Anlage die Signatur des Rückwegs prüft — über `openssl` oder in reinem
PHP —, zeigt nur der Hoster. Sie läuft nach dem Kreislauf, im selben Job.

**Heutiger Umfang:** Der Job heißt „Prüfung Stufe 2", hat **6** Schritte und
eine Zeitgrenze von 45 Minuten; gemessen 16 min je Lauf. Bilderlauf und
Kreislauf `csv` laufen dort heute mit und wandern mit **PK-06** nach
Station B.

> **Staging belegt kein Plattformverhalten von Produktiv.** Seit dem
> 20.09.2026 liegt es bei einem anderen Hoster (E-KH-04) und auf einer
> anderen Datenbank: **MySQL 8.4.10** gegen **MariaDB 10.11.14** auf
> Produktiv. Eine Zahl, die Station D misst, gilt für Staging und sonst
> nirgends. Was das kostet, hat der Export gezeigt: Der Fehler war auf der
> Datenbank von Produktiv unsichtbar und auf der von Staging tödlich, und
> keine der beiden Anlagen hatte je gesagt, dass sie verschieden sind.

### 2.5 Station E — Produktiv

Beim Tag `web-vX.Y.Z`, nach der Pflichtfreigabe durch die BetreiberIn und
nach dem Backup-Tor. Der Ablauf steht in `Technik.md` 6; PK ändert ihn
nicht. Dazu gehören der Zeiger-Zweig `produktion` und die Integritätswache,
die gegen ihn vergleicht.

**Eine Auslieferung einer App** (Tag `android-vX.Y.Z` oder `uhr-vX.Y.Z`)
gehört ebenfalls hierher: Sie wird **einmal** je Fassung gebaut und
signiert, hinter derselben Pflichtfreigabe, mit Schlüsseln, die als
Geheimnisse der Umgebung `produktion` liegen. Der Grund ist derselbe, aus
dem die Uhr keine Zugangsdaten kennt (`CLAUDE.md` 4): Ein Schlüssel, dessen
Verlust jede spätere Fassung zu einer anderen Anwendung macht, gehört nicht
dorthin, wo bei jedem Bau fremder Code mitläuft. Das entsteht mit **PK-08**
und hat eine eigene Freigabe.

---

## 3. Die drei Stufen des Prüfstands

*Gebaut mit PK-03, berichtigt mit PK-05.* Die Zeiten aus PK-03 (klein/neben/haupt
= 26,8 / 25 / 22 s) sind **dreimal derselbe Umfang**: Bis PK-05 las der
Befehl die Fassung nicht (F-PK-30) und kam immer auf „klein", und „neben"
hatte kein eigenes Muster. **Die Nebenstufe, gemessen am 23.09.2026** auf
frischer Anlage mit einer berührten Datei unter `server/`: zuerst **9 von 36
rot** (Backlog Nr. 292) — acht Fehler in `tools/`, einer in einer Erwartung,
keiner in der Anwendung —, nach Konzept RP **36 grün, 0 rot, 0 nicht
gemessen, 1 239 s.** Davon trägt der Bilderlauf 769 s (acht Breiten, 62
Seiten) und die Bedienprobe 261 s; die übrigen 34 Proben zusammen 209 s.
Beide großen laufen absichtlich nacheinander — Wartungsseiten schalten die
ganze Anlage, die Bedienprobe ändert den Bestand —, und schneller ginge es
nur mit weniger Breiten. **Das Ziel ist deshalb rund 21 Minuten, nicht 15**
(E-RP-05). Die Zeit je Probe steht seit RP in jeder Zeile des Laufs.
Protokolle: Prüfdokument PK 5o, Prüfdokument RP.

Der Umfang richtet sich nach der Versionsstufe in `server/version.php` im
Unterschied gegen `main` (Zählweise in `CLAUDE.md` 2) und nach der
Berührung. Der Befehl bestimmt die Stufe selbst und **sagt sie**; `--stufe`
überschreibt und steht im Bericht. **Ist die Fassung in einem der beiden
Stände nicht lesbar, ist das rot** und nicht „klein" — eine Stufe, die aus
nichts gelesen wird, misst gegen nichts.

| Stufe | Auslöser | Umfang |
|---|---|---|
| **klein** | Korrekturstufe `a.a.Y`, oder kein Versionssprung | billige Riegel; je berührter Datei die zugeordneten Proben (4); Bilderlauf der berührten Seiten in drei Breiten, Chromium; Bedienprobe der berührten Seiten |
| **neben** | Nebenstufe `a.X.a` | alles ohne Anlage: alle Proben gegen die örtliche Installation, beide Kreisläufe, Bilderlauf aller Seiten in acht Breiten, Chromium, Bedienprobe aller Seiten; Handy- und Uhr-Bau, wenn berührt |
| **haupt** | Hauptstufe `X.a.a`, **eine neue Migration** (Stufenregel `migration`, 4; seit P5c/AP4) oder `--stufe haupt` | wie neben, dazu die **Plattformmatrix** (PHP 8.3.33 und 8.4; MariaDB 10.11 und 10.6, MySQL 8.0 und 8.4.0 — Schemaprobe und ein Kreislauf `edbak` je Paar), Bilderlauf mit allen drei Engines über alle Seiten, Messstand, Anteilprobe, Verbindungsprobe, Uhr-Prüfstand Stufe II |

**Warum die Matrix in die Hauptstufe gehört und nicht in die Kette:** Der
Export scheiterte am 21.09.2026 nicht an der Anwendung, sondern an einem
bewahrten Alias, der auf MySQL 8.4 reserviert ist und auf MariaDB nicht.
Drei Kreisläufe zu je vier Minuten örtlich fanden ihn; drei Läufe zu je
fünfzehn Minuten in der Kette hatten es nicht getan. Der Kreislauf `edbak`
läuft bei `haupt` und bei `--gegen staging` deshalb **gegen MySQL 8.4.0**,
nicht nur gegen MariaDB.

**Was davon heute läuft, und was nicht** (nachgesehen in P5c/AP4,
F-P5c-103). `pruefablauf.json` gibt `haupt` über `neben` hinaus: Messstand,
Anteil-, Verbindungs- und Schemaprobe (`plattform.sh schema`, vier
Datenbanken, PHP des Containers) und den Bilderlauf. **Nicht gebaut
sind:** PHP 8.3.33 als zweite Fassung, der Kreislauf `edbak` je Paar — auch
der gegen MySQL 8.4.0 aus dem Absatz darüber; `kreislauf.py` kennt keine
zweite Datenbank —, der Uhr-Prüfstand Stufe II und **Firefox und WebKit im
Bilderlauf**: `aufnehmen.mjs` fährt einen Motor aus `--motor` (Vorgabe
Chromium), und `--stufe haupt` ändert daran nichts. Die Zeile „haupt" oben ist damit **das Ziel, nicht der Stand**
(Backlog Nr. 300). Bis dahin fährt ein Paket in `haupt` die Teile, die es
berühren, von Hand (`hochfahren.sh --php 8.3`) und nennt im Prüfdokument,
was fehlt.

**Eine Hauptstufe, die mit `--stufe klein` gefahren wurde, fällt im Tor
auf** — das ist eine der fünf roten Lagen in 5.1.

---

## 4. Berührung → Probe

`tools/pruefstand/pruefablauf.json` ist die eine Zuordnung von der berührten
Datei zur Probe: je Muster auf Dateipfade die Werkzeuge und die Stufe, ab
der sie laufen. Die Tabelle unten wird **daraus erzeugt**
(`bericht.py erzeugen-doku`), wie die Tabellen in `Design.md` — wer sie von
Hand ändert, ändert sie an der falschen Stelle.

Zwei Dinge hängen mit daran, und beide sind gemessen:

- **`tools/kettenaufrufe/` liest dieselbe Datei mit.** Es hält jeden Aufruf
  gegen die Schnittstelle seines Werkzeugs. Gegenprobe am 21.09.2026: Ein von
  Hand eingetragenes `--format` statt `--art` ergibt **2 Befunde** mit Namen
  („kennt `--format` nicht", „verlangt `--art`"); nach der Berichtigung 0.
  Genau dieser Fehler war beim ersten Lauf drin.
- **Eine Probe kann eine andere voraussetzen** (`nach` in
  `pruefablauf.json`, seit RP-01): Der Prüfstand zieht die genannte Probe
  mit in den Lauf und stellt sie davor. So fährt die Wegprobe des
  Spaltenregisters erst, wenn der Kreislauf `edbak` ihr Umlaufkonto angelegt
  hat; `auswahl.py --selbstprobe` belegt die Reihenfolge.
- **Der Demo-Reset trifft keine Probe mehr** (`demo` in `pruefablauf.json`,
  seit R4-04, Nr. 322). Er spielt den Demo-Bestand alle 30 Minuten neu ein,
  und die Einsätze bekommen neue Kennungen; wen er traf, entschied bis dahin
  die Reihenfolge der Muster. Jetzt schiebt der Prüfstand vor jeder Probe
  mit `"demo": true` die Marke des letzten Resets auf „jetzt" und stellt sie
  am Ende zurück; die Zeile steht im Lauf. Das Feld trägt, wer das
  Demo-Konto anmeldet oder Demo-Daten über ihre Kennungen liest — heute
  sieben Proben (`auswahl.py --selbstprobe` hält die Form).
- **Der Prüfstand meldet, wenn eine Datei unter `server/` kein Muster
  trifft.** Gemessen am 23.09.2026: **267 versionierte Dateien, 0 ohne
  Muster**; 87 treffen nur das Auffangmuster und die beiden Stufenmuster
  (`nebenstufe`, `mengen`) und haben damit keine eigene Probe — das ist
  eine Aussage, kein Fehler.

<!-- ERZEUGT von tools/pruefstand/bericht.py erzeugen-doku — nicht von Hand ändern. -->

| Berührung | ab Stufe | Proben | Anlass |
|---|---|---|---|
| `server/**` | klein | — (nur die Riegel) | Auffangmuster: Jede Datei unter server/ laeuft durch die billigen Riegel. Sie traegt KEINE eigene Probe -- wer hier landet und sonst nirgends, hat keine zugeordnete Probe, und der Pruefstand sagt das. |
| `server/spur_lib.php`, `server/tag_spuren.php`, `server/api/backup_spuren*.php` | klein | `spurprobe`, `containerprobe` | int gegen float (S2) |
| `server/ingest.php`, `server/validate_lib.php` | klein | `ingestprobe` | stiller Datenverlust bei "ok" |
| `server/jobs_lib.php`, `server/jobs.php` | klein | `jobprobe`, `jobregister` | Huckepack 18 s; Nr. 208 |
| `server/backup_lib.php`, `server/adminbackup_*.php`, `server/import*.php` | klein | `wiederherstellung`, `containerprobe`, `kreislauf-edbak` | Nr. 31, 33, 34, 35 |
| `server/komplett_lib.php` | klein | `komplettprobe` | count(null), F-S10-AP4-02 |
| `server/gpx_lib.php`, `server/*export*.php`, `server/assets/export.js` | klein | `gpxprobe` | Nr. 130 |
| `server/geraete_lib.php`, `server/pair.php`, `server/geraete*.php` | klein | `geraeteprobe`, `kopplungsprobe` | Edge, das sich "uhr" nennt; Nr. 178, 180 |
| `server/mail_lib.php`, `server/email_lib.php`, `server/ankuendigung_lib.php` | klein | `mailprobe` | smtp_letzter_fehler(); Rundmail Nr. 296 |
| `server/auth_guard.php`, `server/db.php`, `server/admin_*.php`, `server/betrieb_*.php`, `server/api/rechtstext_vorschau.php`, `docs/Technik.md` | klein | `rollenprobe` | Nr. 286, Nr. 149 |
| `server/login.php`, `server/auth_guard.php`, `server/totp_lib.php`, `server/zweitfaktor.php`, `server/zweitfaktor_teile.php`, `server/codeblatt.php`, `server/status_lib.php`, `server/demo_lib.php`, `tools/zweitfaktor/**` | klein | `zweitfaktorprobe` | F-P5c-31 (Code vor der Sitzung), F-P5c-37 (kein Code gilt zweimal), E-P5c-54 (Demo-Reset) |
| `server/rueckweg_lib.php`, `server/status_lib.php`, `server/vendor/phpseclib3/**`, `server/api/rueckweg_anlegen.php`, `server/assets/rueckweg.js`, `server/login.php`, `server/zweitfaktor.php`, `tools/proben/rueckweg/**`, `tools/bedienprobe/wege/einstellungen_profil_rueckweg.mjs` | klein | `rueckwegprobe`, `rollenprobe`, `bedienprobe` | Nr. 319 (F-P5c-106: Rueckweg pruefte gegen einen Wert aus der Datenbank), Konzept RW E-RW-03, -11, -12 |
| `server/protokoll_lib.php`, `server/protokoll_archiv_lib.php`, `server/zip_lib.php`, `server/admin_protokoll.php`, `server/systemmeldung_lib.php` | klein | `protokollprobe`, `versandprobe` | F-P5c-18, F-P5c-19; versandprobe Teil 13 für die Archive auf dem Ziel (E-P5c-39); protokollprobe Teil 7 für das Fehlerprotokoll (Nr. 248) |
| `server/sicherungsziel_lib.php`, `server/admin_sicherungsziele.php` | klein | `versandprobe` | halb englische Meldungen |
| `server/ratelimit_lib.php` | klein | `ratenprobe` | Stufe fiel nie zurueck |
| `server/wartung_lib.php`, `server/auth_guard.php` | klein | `wartungsprobe` | F-S8-P-04, Nr. 171 |
| `server/api/health.php`, `server/speicher_lib.php` | klein | `ratenprobe`, `wartungsprobe` | E-P5c-17, -52 (Health, P5c/AP6): Token, Felder, Migration und Menge in der Ratenprobe, die Antwort aus dem Tor in der Wartungsprobe |
| `server/db.php` | klein | `verbindungsprobe` | Nr. 210 |
| `server/serverkrypto_lib.php`, `server/auth_salt.php`, `server/assets/unlock.js`, `server/assets/crypto.js` | klein | `anteilprobe`, `containerprobe` | S10-Kern, F-S10-AP3-03 |
| `server/*freigabe*.php`, `server/*schluessel*.php` | klein | `freigabeprobe` | F-S2-F |
| `server/sitzung_lib.php`, `server/session_lib.php`, `server/assets/keyguard.js` | klein | `fristprobe`, `abmelde-probe`, `sitzungshaertung` | R44; Nr. 22; Nr. 205 |
| `server/kopfzeilen_lib.php` | klein | `cspprobe`, `browserprobe-csp` | 15.09.: Meldeweg tot |
| `server/schema.sql`, `server/migration_lib.php`, `server/update.php` | klein | `migrationsregister`, `schemaprobe` | Nr. 238; Hausregel dreimal vergessen |
| `server/install.php`, `server/plattform_lib.php` | klein | `installweiche` | PP-1 |
| `docs/rechtstexte/*.md`, `server/nutzungsbedingungen.php`, `server/avv.php`, `server/datenschutz.php` | klein | `rechtstexte` | P3/O10 |
| `server/assets/style.css`, `tools/stilvergleich/**` | klein | `stilvergleich`, `bilderlauf`, `kontraste` | P0/A3 |
| `server/*.php`, `server/assets/*.js` | klein | `bilderlauf`, `bedienprobe` | Nr. 185, 225; PS-2 |
| `.github/workflows/*.yml`, `tools/**` | klein | `kettenaufrufe` | Nr. 217 |
| `android/**` | klein | `android-bau`, `android-kontraste`, `android-farbabgleich`, `android-bildmarken`, `android-stroeme` | E-PK-02 |
| `watch/**`, `tools/uhr-pruefstand/**` | klein | `uhr-stufe1` | E-PK-02 |
| `server/**` | neben | `ingestprobe`, `spurprobe`, `jobprobe`, `komplettprobe`, `wiederherstellung`, `gpxprobe`, `geraeteprobe`, `kopplungsprobe`, `mailprobe`, `versandprobe`, `ratenprobe`, `wartungsprobe`, `freigabeprobe`, `fristprobe`, `abmelde-probe`, `containerprobe`, `browserprobe-csp`, `bedienprobe`, `bilderlauf`, `kreislauf-csv`, `kreislauf-edbak`, `spaltenregister-wegprobe`, `protokollprobe`, `rollenprobe`, `zweitfaktorprobe`, `rueckwegprobe` | Pruefablauf.md 3, Zeile neben: alle Proben gegen die oertliche Installation, beide Kreislaeufe, Bilderlauf aller Seiten in acht Breiten, Bedienprobe. Bis PK-05 gab es dieses Muster nicht -- eine Nebenstufe mass dasselbe wie eine Korrekturstufe (F-P5c-49, E-P5c-32). protokollprobe und rollenprobe kamen mit P5c/AP2 und fehlten hier bis AP3 (F-P5c-90). zweitfaktorprobe kam mit P5c/AP5 und steht hier im selben Paket, rueckwegprobe ebenso mit RW-01. |
| `server/betrieb_statistik.php`, `server/statistik_lib.php` | klein | `messstand`, `bilderlauf` | Nr. 295 (Messstand-Schritt statistik: drei Reiter unter 1 s bei 5000 Einsätzen, EXPLAIN). Stand bis R4-04 am ENDE der Muster, weil die Reihenfolge der Proben entschied, wen der Demo-Reset traf (F-P5c-127); seit R4-04 schiebt der Pruefstand die Demo-Marke vor jeder Probe mit demo: true (Nr. 322), und die Stelle ist gleichgueltig. |
| `server/**` | haupt | `messstand`, `anteilprobe`, `verbindungsprobe`, `schemaprobe` | F-S2-E; Nr. 267 -- der Export scheiterte nur auf MySQL 8.4 -- dazu, was Pruefablauf.md 3 erst der Hauptstufe gibt: Messstand, Anteil- und Verbindungsprobe (PK-05). |
| `docs/Rahmenplan*.md`, `docs/Backlog*.md`, `CLAUDE.md`, `tools/steuerung/**` | klein | `steuerung`, `nummern` | Nr. 177, 196, 199 (Konzept SD): die Decken der Steuerungsdokumente und die Kopfzeilen des Backlogs. Der Riegel laeuft ohnehin in jeder Stufe; das Muster benennt die Beruehrung. Nr. 339 (R4-03): eine neue Nummer, die ein anderer Zweig schon traegt -- nur oertlich, deshalb nur hier. |
| `server/assets/style.css`, `server/assets/images/gen-em_logo_*.png` | klein | `android-farbabgleich`, `android-bildmarken` | Nr. 334 (R4-04): Die App uebernimmt Farbwerte und Bildmarken aus dem Web (E-S4-22a). Wer sie dort aendert, erfaehrt hier, dass die App nachzieht -- ohne den Android-Bau, der dafuer nichts misst. |

**Die billigen Riegel laufen in jeder Stufe, ohne Muster:** `syntax-php`, `wortliste`, `vollstaendigkeit`, `kontraste`, `linkprobe`, `anker`, `bestand`, `syntax-py`, `handbuch`, `installweiche`, `behandler`, `sitzungshaertung`, `cspprobe`, `jobregister`, `migrationsregister`, `rechtstexte`, `kettenaufrufe`, `zaehlung`, `spaltenregister`, `steuerung`.

**Stufenregel `migration`:** eine neue Kennung in `server/migration_lib.php` heißt mindestens **haupt** — E-P5c-36, E-P5c-88: Ein Paket mit Migration faehrt die Plattformmatrix, und die laeuft nur in haupt -- dort sind Nr. 238 und Nr. 267 gefunden worden. Ausgeloest von einer NEUEN Kennung im Katalog, nicht von einer Aenderung an der Datei: Die aendert sich auch ohne Migration (P5c/AP2, AP3).

---

## 5. Der Prüfbericht

*Erzeugung gebaut mit PK-03, Gegenlesung im Tor mit PK-05 (`bericht.py`,
Selbstprobe 13 Lagen / 0).*

Der Prüfstand schreibt am Ende einen Block, der in die Commit-Nachricht
gehört und maschinell lesbar ist:

```
Prüfstand: neben · Baum a1b2c3d… · Konfiguration web
  syntax=php:511/0,py:48/0  textprobe=0  quelltext=6/0
  kreislauf=edbak:0,csv:0  bilderlauf=62/0/0/0  proben=ingest:10/10
  handy=nicht berührt  uhr=nicht berührt
```

### 5.1 Fünf Lagen, in denen das Tor rot wird

- Der **Baum-Hash** ist nicht der Baum des Commits. Das fängt den Fehler,
  der in O9c passiert ist: gemessen wurde vor der letzten Änderung, gemeldet
  wurde die Zahl von davor. **Streng** (E-PK-42): Der Baum des PR-Kopfs muss
  es sein, nicht der eines Vorfahren — der Weg nach einem fremden Merge
  steht in 5.3.
- Die **Stufe** ist kleiner, als die Versionsstufe im Unterschied verlangt —
  oder die Versionsstufe ist nicht lesbar.
- Eine **berührte** Fläche steht nicht auf „gebaut": `handy` gegen
  `android/`, `uhr` gegen `watch/` und `tools/uhr-pruefstand/`. „gebaut"
  schreibt der Prüfstand nur nach einem grünen Bau, sonst „rot" oder
  „nicht-gemessen" (E-PK-44) — bis PK-05 genügte ihm die Berührung.
- Ein **billiger Riegel** liefert im Tor eine andere Zahl als der Bericht.
  Das Tor übergibt **jeden** Riegel aus `pruefablauf.json` (`--alle-riegel`);
  fehlt einer im Aufruf, ist das rot, damit ein neuer Riegel nicht still
  ungegengelesen bleibt. Jeder Wert hängt am Ausgang seines Schritts: Wer
  einen Riegelschritt streicht, bekommt „nicht-gelaufen" statt einer stehen
  gebliebenen 0.
- Eine **Probe** meldet im Bericht etwas anderes als 0 — rot oder
  `nicht-gemessen` (E-PK-44, -46). Der Prüfstand druckt den Bericht auch
  nach einem roten Lauf; ein roter Lauf ist kein Nachweis, ein übersprungener
  auch nicht.

Dazu rot, ohne eigene Lage: **kein Bericht** in der Nachricht des PR-Kopfs.
Die Meldung sagt dann, wie er hineinkommt.

Das Tor liest den Bericht mit einem Werkzeug, nicht mit einer Shell-Zeile
(`bericht.py lesen`, mit Selbstprobe) — aus dem Grund, der unter E-P5a-12
steht: Eine Shell-Zeile, die einen Wert aus Text zieht, ist die Stelle, an
der ein Riegel still durchlässt.

### 5.2 Was der Bericht **nicht** ist

Das Tor kann einen teuren Lauf nicht nachrechnen. Für Kreisläufe,
Bilderlauf und die Bauläufe der Apps ist der Bericht ein **Nachweis**, kein
Riegel: Station B ist die geprüfte Partei, und ein Nachweis aus ihrer Hand
bleibt einer aus ihrer Hand. Ein Bericht, der einen Bau behauptet, den es
nicht gab, kommt durch.

Zwei Dinge halten die Grenze klein, und sie sind der ganze Grund, warum das
tragbar ist: Der Block wird vom Befehl **erzeugt** und nicht geschrieben,
und der Merge bleibt ein Mensch, der den Block liest.

### 5.3 Nach einem fremden Merge — nicht „Update branch"

Das Ruleset verlangt, dass ein PR den Kopf von `main` enthält (2.3). Der Knopf
„Update branch" schreibt dafür einen Merge-Commit **ohne Bericht** und mit
einem Baum, den der Prüfstand nie gesehen hat — Stufe 1 ist danach **rot**,
und das ist gewollt (E-PK-42): Ein Stand aus zwei Zweigen ist ein neuer
Stand. Der Weg, der grün wird:

1. `git fetch origin main && git merge --no-commit origin/main` — Konflikte
   lösen, nichts committen.
2. `bash tools/pruefstand/pruefen.sh` — misst genau diesen Baum, samt allem,
   was der Lauf selbst schreibt. Bringt `main` Migrationen mit, ist er
   schon beim Hochfahren **rot** mit der Kennung und dem Weg: Die Anlage
   steht auf dem Schema davor (Nr. 332). Dann `hochfahren.sh --neu` und
   Schritt 2 noch einmal.
3. `git add -A`, den Merge-Commit mit dem Bericht als Nachricht schreiben,
   pushen.

So trägt der Merge-Commit selbst den Bericht, und es braucht keinen
Leer-Commit dafür. Berührt ist dabei nur, was diese Arbeit gegen `main`
ändert — nicht, was `main` mitbringt (F-PK-39). Zum ersten Mal gegangen
beim Merge von PR #80 in PK-05 (`5671d24`). Wer doch „Update branch" gedrückt hat, holt den Commit
herunter und fährt ab Schritt 2 mit einem eigenen Commit darüber.

---

## 6. Regeln für Prüfmittel

### 6.1 Ein Prüfmittel braucht einen Fehler

Jedes Werkzeug nennt in einer Zeile „Anlass: Nr. …", welchen Fehler es
gefangen hat oder hätte fangen müssen. Der Grund ist nicht Buchhaltung: Ein
Prüfmittel ohne gefundenen Fehler misst entweder etwas, das nicht
kaputtgeht, oder es misst daneben — und beides kostet bei jedem Lauf Zeit.

**Ein Anlass ist eine Backlog-Nummer** (E-BR-07). Eine Befund-Kennung
(`F-XX-NN`) und eine Entscheidung (`E-XX-NN`) stehen in einem Konzept, das
am Ende gelöscht wird; ein Kürzel wie `PP-1` oder `O9c` sagt der nächsten
Instanz nichts. Wer ein Prüfmittel anlegt, legt deshalb **zuerst** die
Nummer an — auch für einen Fehler, der im selben Paket behoben wird: Der
Eintrag wandert dann nach *Erledigt* und bleibt zitierbar. Ausgenommen ist
die Spalte „Anlass" der Zuordnung in `pruefablauf.json` (4): Sie beschreibt
die Berührung, nicht das Werkzeug. Und ein Erzeuger, der keinen Fehler
fangen kann, trägt genau `Anlass: entfällt — Erzeuger (E-BR-05)`.

**Seit Konzept BR misst `bestand` die Zeile** (6.2), je Ordner und je
Probe: Ohne sie ist Stufe 1 rot. Bis dahin kam ein Werkzeug ohne Zeile
„auf die Streichliste", und die führte niemand — vor BR trugen 0 von 20
Proben die Zeile. Eine Quelltextprüfung hat keinen eigenen Ordner: Ihr
Anlass steht in der Tabelle von `tools/quelltext/LIESMICH.md`. `bestand`
verlangt dort je Prüfung genau eine Zeile mit nicht leerer Anlass-Spalte
(Regel `zeile`, seit BR-05), liest die Spalte aber nicht als Backlog-Nummer
(E-BR-07).

### 6.2 Die Anleitung: fünf Abschnitte, höchstens 40 Zeilen

Eine `LIESMICH.md` je Werkzeug hat **fünf feste Abschnitte** und **höchstens
40 Zeilen**:

1. **Aufruf** — die Befehlszeile, mit den Schaltern.
2. **Was es misst** — der Gegenstand, in zwei Sätzen.
3. **Was es braucht** — Ausbaustufe, Umgebungswerte, laufende Installation.
4. **Erwartete Zahl** — woran man grün erkennt.
5. **Was es nicht kann** — die benannte Grenze.

Dazu **eine** Zeile „Anlass: Nr. …" als Verweis (6.1). Funde,
Fehlanläufe und Zahlengeschichte stehen **nicht** hier, sondern in der
Commit-Nachricht des Pakets, im Backlog als Nummer und im Changelog.
Dieselbe Regel gilt für die Kommentare in `.github/workflows/`: ein Satz an
jeder Stelle, die eine Falle beschreibt, die sonst jemand wieder einbaut.

**Seit Konzept BR ist die Form ein Riegel und keine Regel mehr** (E-BR-03,
-04): `tools/quelltext/` `bestand` hält **jede** `LIESMICH.md` unter
`tools/`, auch in Unterordnern, an die fünf Abschnitte in dieser
Reihenfolge und an 40 Zeilen; jeden Ordner direkt unter `tools/` an seine
eine Anlass-Zeile mit Backlog-Nummer; jede Probe an die Anlass-Zeile im
Kopfkommentar ihrer Einstiegsdatei. Dazu misst er, dass jeder Ordner
gerufen wird — `tools/<ordner>/` steht in `pruefablauf.json`, in einem
Workflow, in `tools/pruefstand/pruefen.sh` oder in `Sandbox-Setup.md` — und
dass außer `motor.mjs` keine Datei lose unter `tools/` liegt. Er misst gegen
null, **ohne Decke und ohne Ausnahmeliste** (E-BR-01). Die Anlass-Zeile
beginnt mit `Anlass:`; eine Zeile, in der das Wort mitten im Satz steht,
findet man nur, wenn man sie sucht, und zählt deshalb nicht.

**Seit BR-05 misst er auch das Einhängen** (Nr. 315): dass eine
Quelltextprüfung mit Selbstprobe in `SELBST` steht und ihre Zeile in der
Tabelle hat, dass jede Probe in `pruefablauf.json` an einem Muster, am
Riegel oder an einem `nach` hängt und jedes Muster vollständig ist, und dass
die Tabelle in 4 die Ausgabe ihres Erzeugers ist. Das waren die vier
Schritte, die die Gegenlesung von 6.12 auslassen konnte, ohne dass ein Mittel
es merkte. Dazu zwei, die erst die vierte Gegenprüfrunde fand: dass
`pruefen.sh --selbstprobe` in einem Workflow oder einem Aufruf überhaupt
steht, und dass jede Datei einer Fläche des Berichts (`android/`, `watch/`,
`tools/uhr-pruefstand/`) schon in der kleinsten Stufe ihre Bauprobe
auswählt — sonst verlangt das Tor „gebaut", und kein Lauf liefert es.
**Gelesen wird mit den echten Werkzeugen** (E-BR-22): Tabellen über
`cmark-gfm`, PHP über `token_get_all`, die Listen der Läufer über
`bash … --liste`, Pfadmuster und Auswahl über `auswahl.passt()` und
`auswahl.treffer()` — drei Gegenprüfrunden haben gezeigt, dass eigene
Nachbauten jede Runde eine andere Randschreibweise anders lesen. Jede
Befundstelle hat eine Kennung, und die Selbstprobe von `bestand` schlägt an,
wenn eine in keinem Fall fällt. Was er nicht sieht, steht im Kopf von
`bestand.py`.

**Seit R4-02 hält er den Prüfstand an das Tor** (Nr. 329), mit zwei Regeln:
`anlage` — keine Probe mit `braucht: nichts` lädt über ihre Ladekette auf
oberster Ebene `db.php` oder `config.php`, denn Stufe 1 hat keine Anlage;
gelesen über `token_get_all`, ein Pfad nur aus Zeichenketten, `__DIR__`,
`dirname()`, `realpath()` und so gebauten Variablen — und `tor` — jeder
Riegel steht als `--riegel NAME=` im Aufruf `bericht.py lesen
--alle-riegel` von `pruefung.yml`. Beides war örtlich grün und im Pull
Request rot (F-P5c-171, -172).

*Gemessen am 24.09.2026 mit `bestand`: 25 Anleitungen mit zusammen 972
Zeilen, alle in der Form, 0 Befunde. Vor Konzept BR waren es 24 Anleitungen
mit 2 144 Zeilen und 57 Befunde, am 21.09.2026 vor PK-04 47 mit 7 206.*

### 6.3 Selbstprobe nur, wo etwas aufgehalten wird

Ein Werkzeug braucht eine Selbstprobe, wenn es einen Merge oder eine
Auslieferung **verhindern** kann — Tor, Freigabe, Zielprobe, Zustand,
Bericht, Schemaprobe, Quelltextprüfungen. Proben, die in Station B nur
messen, brauchen keine: Dort sieht eine Instanz das Ergebnis und kann es
nachfahren; im Tor sieht es niemand mehr.

### 6.4 Wer Markup aus einer Quelldatei liest

Der Tag-Rumpf wird so gelesen — und zwar in **jedem** Tag-Muster:

```
(?:<\?(?:php\b|=).*?\?>|[^>])*
```

Ein `[^>]*` endet am ersten `>`, und in einer PHP-Quelle ist das oft das `>`
eines `?>` mitten im Tag: `<script<?= kopf_nonce_attr() ?>>`,
`<form data-sperre-rest="<?= (int)$rest ?>">`. Der Tag bricht dann mitten im
PHP-Ausdruck ab. **Für HTML beendet `?>` kein Tag; für ein Muster über den
Quelltext schon.**

Was das anrichtet, hängt am Muster: Die Integritätswache wurde bei jedem
Lauf grundlos rot (Fund 23), sie wurde für den Angriff blind, für den es sie
gibt (Fund 27), und in `SRC_RE` wäre ein fremdes Skript weder als Block noch
als Verweis gezählt worden — unsichtbar (Backlog Nr. 218). Drei Anläufe an
derselben Stelle, und beim zweiten wurde das Nachbarmuster zwanzig Zeilen
weiter übersehen. Deshalb gilt die Regel nicht für `<script>`, sondern für
jedes Tag, das ein Werkzeug aus dem Quelltext liest.

Wo die kurze Form richtig ist, weil die PHP-Inseln vorher ausgeräumt wurden,
**steht das als Kommentar daneben** — sonst wird sie beim nächsten Durchgang
„mitkorrigiert".

**Muster über *gelieferte* Antworten sind davon nicht betroffen**
(Serverausgabe, `tools/referenzdatensatz/`, `tools/messstand/`): Dort ist das
PHP bereits ausgeführt, und das lange Muster wäre dort falsch.

*Durchgesehen und auf der langen Form (Stand 21.09.2026, nachgemessen):
`tools/integritaetswache/`, `tools/quelltext/`,
`tools/stilvergleich/` und `tools/proben/wartung/` — **vier**.
`tools/quelltext/zerlegen.py` trägt die kurze Form mit dem Kommentar
daneben. Bis PK-01 nannte `CLAUDE.md` 6 an dieser Stelle vier Werkzeuge und
zählte dabei `wortliste` (kurze Form) mit und `wartungsprobe` (lange Form)
nicht — die Zahl stimmte, die Liste nicht.*

### 6.5 Eine grüne Zahl ist erst ein Beleg, wenn sie den Gegenstand nennt

Der Bilderlauf meldete nach O9c „248 Bilder, 0 Überlauf" — 176 davon zeigten
die Anmeldeseite (F-P3-AQ). Bei jedem Prüfmittel dazusagen, **was** es
gemessen hat, und im Zweifel eine unabhängige Gegenprobe fahren.

Das gilt auch für die Zahl, die eine Zusage belegen soll: „8 Schlösser
gezählt" sagt nichts darüber, ob eines fehlt — beide Lücken, die Web 19.1.1
geschlossen hat, standen neben einer richtigen Zahl (Backlog Nr. 170).

### 6.6 Jeder sichtbare Text läuft durch die Wortliste

Für jede Änderung an einem sichtbaren Text — der Weboberfläche, der
Handy- und Uhr-Anwendungen oder der normativen Dokumentation — läuft
`tools/quelltext/` (`textprobe`): Es zählt nach, ob Land und Luft neutral benannt sind.
Erwartet werden null Treffer außerhalb der Ausnahmeliste und null ungenutzte
Ausnahmen; ein Begriff, der bleiben soll, braucht einen Eintrag **mit
Begründung** — kein Ausblenden.

> **Ein Lauf, der einen Bereich übergeht, meldet keine Null — er meldet gar
> nichts.** Ein Bereich fehlt nicht, weil ein Verzeichnis jung ist; er fehlt,
> weil ihn niemand eingetragen hat. **Wer ein Verzeichnis oder ein normatives
> Dokument hinzufügt, trägt es im selben Paket in `BEREICHE` ein, in dem es
> entsteht.**
>
> Aufgestellt in S4 (B-S4-06), nachdem der Lauf nach dem letzten
> Android-Paket 0 Treffer meldete, ohne eine Zeile der Anwendung angesehen zu
> haben. Am 17.09.2026 wiederholte sich derselbe Fehler mit den
> Rechtstexten. Bereich `d` (Android) steht seit S4 in der Liste, Bereich `e`
> (`watch/` — Ressourcen **und** Quelltext) seit S5/C, die Rechtstexte seit
> P5b. **Damit läuft jeder Client durch die Liste.**

**Seit PK-04 ist die Wortliste die Textprobe** (E-PK-08): fünf Regelklassen
statt einer, und sie läuft **einmal im Tor** statt bei jeder Textänderung von
Hand.

| Klasse | Was sie misst | Ziel |
|---|---|---|
| `luft` | Land- und Luftbegriffe (das ursprüngliche Maß) | 0 |
| `hausform` | **18 Rollenwörter** im großen Binnen-I (E-PK-26, E-PK-38) | 0 |
| `adressen` | E-Mail-Adressen außerhalb der von RFC 2606 reservierten Formen; `demo@gen-em.org` ist die eine erlaubte (E-PK-27) | 0 |
| `netz` | Adressen im Netz, die nicht in `docs/Lizenzen.md` stehen | 0 |
| `namen` | reale Orts- und Rufnamen (E-P1-02) | 0 |

**Rot ist nur ein NEUER Treffer.** Der Altbestand steht mit Zahl in
`textprobe-altbestand.json`, je (Datei, Muster); wer eine Stelle bereinigt,
zieht ihn mit `--altbestand-schreiben` nach. So hält der Lauf auf, was
hinzukommt, ohne bei jeder Änderung den ganzen Altbestand zu verlangen.

> **Zwei Eigenschaften, ohne die eine Zahl aus dieser Probe nichts
> bedeutet** — beide in PK-04/5 gemessen und behoben:
>
> **Ein Muster ohne `"gross": true` liest ohne Rücksicht auf Groß- und
> Kleinschreibung.** Für `hausform` war das doppelt falsch: Es traf die
> **Bezeichner** (`betreiberin` ist der Rollenname im Code, `$nutzer` eine
> Variable — **1 233** kleingeschriebene Treffer gegen 913 großgeschriebene)
> und es traf **die Hausform selbst**, weil `BetreiberIn` als
> `Betreiber` + `In` durchgeht. Die Regel zählte damit hoch, je mehr man
> reparierte: `AVV.md` ging von 22 auf 47, während 44 Stellen richtig
> umgestellt wurden. Wer ein Muster für sichtbaren Text schreibt, setzt den
> Schalter.
>
> **Eine Ausnahme ohne `muster`-Feld gilt nur für die Klasse `luft`.** Sie
> meldet trotzdem „gegriffen" und sieht damit aus wie eine wirksame Ausnahme.
> PK-04/1c hat das an 89 von 100 Regeln behoben; zwei sind durchgerutscht,
> weil sie damals keinen Treffer der neuen Klassen hatten, und sind in 5c
> aufgefallen. Wer eine Ausnahme für eine andere Klasse schreibt, nennt ihr
> Muster.

**Was die Zahl `hausform = 0` bedeutet — und was nicht.** Sie misst 18
Wörter in fünf Bereichen: `server/*.php`, `server/api/*.php` (sichtbarer
Text **ohne Kommentare**), `server/assets/*.js`, die Android- und
Uhr-Ressourcen und 14 normative Dokumente. **Nicht** gemessen werden
`Rahmenplan.md`, `Backlog.md`, `Backlog-Erledigt.md`, `CHANGELOG.md`,
`docs/konzepte/**` und **alle Kommentare**. Wer die Zahl zitiert, zitiert diesen Absatz mit.

> **Für die Klasse `namen` ist „ohne Kommentare" zu wenig** (Backlog
> Nr. 283). E-P1-02 richtet sich gegen das **öffentliche Repositorium**, das
> die reale Station benennt — und ein Kommentar steht genauso darin. Am
> 23.09.2026 mit `grep` gemessen: **acht Stellen** in `server/` trugen einen
> realen Orts- oder Rufnamen, alle in Kommentaren, alle für die Textprobe
> unsichtbar. Die schärfste stand **sechs Zeilen unter einem Platzhalter,
> den E-S3-13 schon berichtigt hatte**.
>
> **`namen = 0` heißt deshalb „null im sichtbaren Text", nicht „null im
> Repositorium".** Die acht sind bereinigt; die Lücke im Prüfmittel ist es
> nicht.

### 6.7 Die Prüfmittel laufen zuletzt, nicht zwischendurch

Erst der Code, dann die Dokumentation, **dann** die Prüfmittel. Ein
Werkzeug, das vor der letzten Änderung lief, misst einen Stand, den es nicht
mehr gibt — in O9c stand die Wortliste dadurch auf fünf Treffern, gemeldet
worden waren null (Web 9.10.1).

### 6.8 Was die Kette selbst prüft

`tools/kettenaufrufe/` hält jeden Werkzeugaufruf in `.github/workflows/`
gegen die tatsächliche Schnittstelle des aufgerufenen Werkzeugs, ohne es
auszuführen. Grund: Am 16./17.09.2026 sind drei Kettenschritte beim jeweils
**ersten** echten Lauf gescheitert, alle drei mit gültigem YAML und sauberer
Shell-Syntax (Backlog Nr. 217). Wer einen Aufruf in der Kette ändert, fährt
das Werkzeug davor; wer ein Werkzeug umbenennt oder seine Schalter ändert,
ebenfalls.

### 6.9 Die Bauläufe der beiden Anwendungen

- **Uhr** (`watch/`): `tools/uhr-pruefstand/` — Stufe I übersetzt für alle
  Zielgeräte, Stufe II startet im Simulator. Der Aufbau braucht
  `CIQ_GERAETE_URL` (`Sandbox-Setup.md` 4).
- **Handy und Uhr-Gegenstück** (`android/`): `./gradlew build` im Ordner
  `android/`, mit `ANDROID_HOME=/opt/android-sdk`. Erwartet werden **0
  Lint-Fehler** und **0 Fehlschläge**; Warnungen werden gezählt und nicht
  stummgeschaltet. Was nur auf einem Android-System geht, steht in
  `src/androidTest/` und geht an Gradle vorbei — Befehlsfolge und Begründung
  in `android/LIESMICH.md`.
  **Und dort sind Namen in schrägen Anführungszeichen mit Leerzeichen
  verboten:** D8 lehnt sie unterhalb von DEX 040 ab, und das Modul steht auf
  `minSdk = 26`. Ein Prüffall, der so heißt, bricht den Bau — nicht den
  Prüflauf, den Bau.
- **Der Emulator läuft mit, wie der Simulator bei der Uhr.** Bei jeder
  Änderung an einem der beiden Android-Module wird der Emulator gestartet,
  die Änderung darin **angesehen und bedient**, und beides mit **Bildern
  belegt** (`android/werkzeuge/emulator.sh`). Der Bilderlauf ersetzt ihn
  nicht: Robolectric zeichnet das *gerechnete* Bild, der Emulator zeigt das
  *gelaufene* — Systemleisten, echte Schriftrasterung, rundes Glas,
  Bedienzustände, und was nach einem Druck auf einen Knopf passiert.
  **Pflicht ist der Versuch, nicht der Erfolg:** Läuft er nicht, ist das ein
  Befund mit Zahl — welches Abbild, welche Fassung, welche Meldung —, kein
  stillschweigend übersprungener Punkt. Was vorher versucht wird und was er
  kostet, steht in `android/LIESMICH.md`.

### 6.10 Der Stilvergleich — wann er zu fahren ist

**Auslöser:** jede Änderung an `server/assets/style.css`, die Regeln
**verschiebt, zusammenführt, entfernt** oder deren **Reihenfolge** berührt.
Dann läuft `tools/stilvergleich/`: Kaskadenvergleich plus berechnete Stile in
Chromium über mehrere Fensterbreiten, Vergleichsstand aus Git.

**Bei einer beabsichtigten Gestaltungsänderung ist das Ergebnis keine Null,
sondern eine Liste.** Sie wird gegen die Liste der geplanten Änderungen
gehalten; **jede Abweichung darüber hinaus ist unbeabsichtigt und wird
geklärt, bevor committet wird.** Wer eine Null erwartet, wo eine Liste
richtig ist, schaltet das Werkzeug beim ersten beabsichtigten Umbau ab.

**Die Liste steht seit P5c/AP1 in einer Datei** (F-P5c-72):
`tools/stilvergleich/geplant.txt`, je Zeile eine Signatur — Probe, Element
mit Elternteil, die Namen der Eigenschaften, die sich ändern (ohne Werte,
vereinigt über alle Breiten). Grün ist der Lauf, wenn Messung und Liste
**gleich** sind: Eine ungeplante Abweichung ist rot, und eine geplante, die
nicht gemessen wird, auch — sonst verdeckte eine veraltete Liste beim
nächsten Mal eine ungewollte Änderung. Geschrieben wird sie mit
`bash tools/stilvergleich/gegen.sh --schreiben`, **gelesen im Pull
Request**: Jede Zeile ist die Aussage „das soll sich ändern". Fehlt die
Datei oder ist sie leer, gilt die Null. **Nach dem Merge bleibt sie
stehen** (seit dem 26.09.2026, Nr. 330): `gegen.sh` übergibt die Liste des
Vergleichsstands als `--geerbt`, und eine Zeile, die dort wortgleich steht
und **nicht gemessen** wird, zählt nicht — sie steht mit Zahl als „geerbt,
nicht gemessen" im Protokoll. Nur diese eine Kategorie: Eine gemessene
Abweichung ohne Zeile bleibt rot, eine eigene Zeile ohne Messung auch.
Wortgleich allein streicht nichts, weil dieselbe Signatur in zwei Pull
Requests hintereinander gewollt sein kann. Bis dahin musste die Liste nach
jedem Merge von Hand geleert werden. Bis P5c/AP1 war gebaut, was hier
steht, nur als Null: Jede gewollte Gestaltungsänderung machte seit PK-05
den Prüfbericht rot (Lage 5).

**Er ersetzt die Browserprüfung nicht:** Er misst statisches Markup, keine
Bedienzustände. Er beantwortet die eine Frage, die der Bilderlauf nicht
beantwortet — hat sich ein berechneter Stil geändert, der nicht sollte —,
kostet rund 16 Sekunden und braucht keine Pflege.

Mit **PK-04** hängt dieser Auslöser in `pruefablauf.json` statt hier.

### 6.11 Die Sollwerte, die heute gelten

Die Zahlen stehen in der `LIESMICH.md` des jeweiligen Werkzeugs und nicht
hier — eine Prüfzahl an zwei Stellen altert an einer davon unbemerkt. Was
hier steht, ist nur, **was grün heißt**:

| Mittel | grün heißt |
|---|---|
| `tools/quelltext/` `textprobe` | 0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen |
| `tools/quelltext/` `vollstaendigkeit` | 0 Befunde in sechs Prüfungen — ohne Schwelle seit PK-04/5e (E-PK-16); die sechste, Selektoren, seit R4-05 |
| `tools/screenshots/` | 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe falscher Höhe, 0 Karten außerhalb von `main.inhalt`; mit `--etikett NAME` zusätzlich 0 Abweichungen bei Titel und Kopfleiste (P5c/AP1) |
| `tools/kettenaufrufe/` | 0 Befunde; jeder ungeprüfte Aufruf ist benannt |
| `tools/quelltext/` `bestand` | 0 Befunde in allen dreizehn Regeln — ohne Decke, ohne Ausnahmeliste (E-BR-01) |
| `tools/quelltext/` `anker` | 0 Verweise `hilfe.php#…` ohne Ziel im gerenderten Handbuch; Selbstprobe 6 von 6 (falscher Anker rot, Kommentar zählt nicht, `-2` bei gleichem Titel) |
| `tools/quelltext/` `pysyntax`, `handbuch` | 0 Syntaxfehler bei mindestens einer Datei; beide Dokumente rendern, gültiges UTF-8, 0 Bilder aus fremder Quelle |
| `./gradlew build` | 0 Lint-Fehler, 0 Fehlschläge |
| `tools/stilvergleich/` | die gemessenen Abweichungen sind genau `geplant.txt` — ohne Datei: 0 (6.10) |
| `tools/steuerung/` | 0 Decken gerissen (welche es sind, sagt `decken.py`), 0 Kopfzeilen ohne Grammatik, 0 ohne gültiges Ziel, 0 Nummern in beiden Backlog-Dateien, 0 Einträge als Codeblock; Selbstproben alle Fälle grün |

PK-04 hat zwei davon geändert: E-PK-16 hat der Vollständigkeit die
Symbolzählung und ihre Schwelle genommen, E-PK-08 hat die Wortliste zur
Textprobe erweitert.

*Warum hier keine Zahl steht:* `docs/Technik.md` 6.2 trug die Zahl des
Bilderlaufs ein zweites Mal und stand bis Web 20.21.1 auf 366, während die
Kette längst mit 377 lief. Eine Schwelle hat genau einen Ort, und das ist
der Aufruf, der sie anwendet — seit PK-03 `tools/pruefstand/pruefablauf.json`.
In `pruefung.yml` steht seit PK-05 keine Schwelle mehr; die Zahlen dort misst
das Tor selbst und hält sie dem Bericht entgegen.

### 6.12 Ein neues Prüfmittel

In dieser Reihenfolge. In Klammern steht, welches Mittel einen
ausgelassenen Schritt meldet. **Seit BR-05 meldet jeden Schritt ein Mittel**
(Backlog Nr. 315); was keines sehen kann, steht in Schritt 7. Die Zuordnung
ist gemessen: Eine Instanz, die diesen Abschnitt nicht geschrieben hat, hat
danach zwei Attrappen eingehängt und je einen Schritt weggelassen (BR-04,
F-BR-19) — vier davon blieben damals grün.

1. **Die Backlog-Nummer anlegen** (6.1). Am **Ende von `docs/Backlog.md`**
   — die Datei hält nur *Offen*; Erledigtes steht in
   `docs/Backlog-Erledigt.md` (Konzept SD, seit 26.09.2026) — mit der
   nächsten freien Nummer aus der Reservierungstabelle im Kopf der Datei.
   Vorher auf `origin/main` **und** auf den offenen Arbeitszweigen
   nachsehen; wer Nummern vergibt, trägt seine Spanne dort ein, bevor er
   pusht. Die Form — die Kopfzeile nach dem Muster im Kopf der Datei
   (Ziel und Stand aus dem dort genannten Vokabular), jede Folgezeile
   **fünf** Leerzeichen eingerückt, höchstens 20 Zeilen:

   ```
   NNN. **Ein Satz: der Fehler oder das Risiko.** · gehört zu: ZIEL · Stand: offen · seit TT.MM.JJJJ
        *Aufgenommen TT.MM.JJJJ mit <Paket>.* Was, wo (Funktionsname, nicht
        Zeilennummer), wie gefunden.
        *Weg:* … *Abnahme:* …
   ```

   Behebt das Paket den Fehler gleich mit, wandert der Eintrag mit seinem
   Text ans Ende von `docs/Backlog-Erledigt.md` und bleibt zitierbar.
   `bestand` meldet eine Nummer, die **zweimal** steht — auch einmal je
   Datei —, und eine Anlass-Zeile, deren Nummer es in keiner der beiden
   Dateien gibt. Achtung: Er liest **jede** Zeile, die mit Zahl und Punkt
   beginnt, als Nummer — auch ein umbrochenes Datum. Den Umbruch davor
   setzen.
2. **Den Ort wählen.** Keine Datei lose unter `tools/` (`bestand`, `lose`).
   - **Liest es nur Quelltext** → `tools/quelltext/<name>.py` oder `.php`.
     Den Namen in `NAMEN` von `tools/quelltext/pruefen.sh` eintragen
     (fehlt er: `kettenaufrufe`), bei `.py` auch in `starter()` dort
     (fehlt er: der Läufer meldet eine grüne Prüfung weniger). Dazu eine
     Zeile in der Tabelle von `tools/quelltext/LIESMICH.md` mit Name,
     Gegenstand und Anlass (fehlt sie oder ihr Anlass: `bestand`, `zeile`),
     und die Nummer in die Anlass-Zeile derselben Datei.
   - **Braucht es die laufende Anlage** → `tools/proben/<name>/`, eine
     Zeile in `RUF` von `tools/proben/proben.sh` (fehlt sie: `bestand`,
     `probe`). Der Kopfkommentar der Datei, die `RUF` startet, trägt eine
     Zeile, die mit `Anlass: Nr. …` beginnt, etwa
     ` * Anlass: Nr. NNN — …` (fehlt sie: `bestand`, `probe`).
   - **Sonst** ein eigener Ordner `tools/<ordner>/`.
3. **Die Anleitung in der Form** (6.2) — für einen eigenen Ordner neu:
   `LIESMICH.md` mit genau `## Aufruf`, `## Was es misst`, `## Was es
   braucht`, `## Erwartete Zahl`, `## Was es nicht kann` in dieser
   Reihenfolge, **höchstens 40 Zeilen**, genau eine Zeile, die mit
   `Anlass: Nr. …` beginnt; `**Anlass: Nr. …**` zählt auch (`bestand`:
   `form`, `anleitung`, `anlass`). Für einen Sammelordner die Zähler
   mitziehen: die Zahl im ersten Satz („Elf Prüfungen", „Zwanzig Proben"),
   die Zahl unter *Erwartete Zahl* und die im Kopfkommentar von
   `pruefen.sh` bzw. `proben.sh`. **Beide Sammelanleitungen stehen bei 40
   von 40 Zeilen** (gemessen 24.09.2026): Wer eine Zeile ergänzt, streicht
   eine. Geschichte gehört in die Commit-Nachricht (Grundsatz 6).
4. **Eine Selbstprobe, wenn es aufhält** (6.3). `--selbstprobe` baut je
   Fehlerart einen Fall mit eingebautem Fehler und eine Gegenprobe, die
   grün bleiben muss; Ausgabe je Fall eine Zeile, am Ende „N Fälle, M
   Fehlschläge". Rückgabewert **0** grün, **1** Befund, **2** nicht
   gelaufen (etwas fehlt — nie still grün). Vorlage:
   `tools/quelltext/pysyntax.py`. Eine Quelltextprüfung trägt ihren Namen
   dann auch in `SELBST` von `tools/quelltext/pruefen.sh` (fehlt er:
   `bestand`, `selbst` — sonst liefe die Selbstprobe nirgends; ebenso,
   wenn `pruefen.sh --selbstprobe` aus `pruefung.yml` verschwindet).
5. **Die Zeile in `tools/pruefstand/pruefablauf.json`.** Unter `proben` ein
   Eintrag (fehlt er für eine Probe aus `RUF` oder einen Namen aus `NAMEN`:
   `bestand`, `ablauf`); der Schlüssel ist der Name im Bericht und darf vom
   Namen im Läufer abweichen (`cspprobe` ruft `pruefen.sh csp`):

   ```json
   "attrappe": {"aufruf": "bash tools/proben/proben.sh attrappe",
                "braucht": "installation", "nach": ["kreislauf-edbak"]}
   ```

   `braucht` ist `nichts`, `installation`, `plattform`, `android` oder
   `uhr` (ein anderer Wert: `bestand`, `ablauf` — die Vorabprüfung entfiele
   still); `"demo": true`, wenn die Probe das Demo-Konto anmeldet oder
   Demo-Daten über ihre Kennungen liest (4, Nr. 322); `nach` nur, wenn es eine andere Probe voraussetzt (4), nie im
   Kreis und nie an einem Riegel. Der
   `aufruf` darf eine Kette sein (`a && b`, wie bei `uhr-stufe1`);
   `kettenaufrufe` prüft seit BR-04 jeden Teil. Ein **neuer Schlüssel**,
   den ein Werkzeug liest, gehört auch in `SCHLUESSEL` von
   `tools/quelltext/bestand.py` (sonst: `bestand`, `ablauf` — „das liest
   niemand"). Dann
   **eines** von beiden (fehlt beides: `bestand`, `ablauf` — sonst liefe es
   nie):
   - ein Eintrag unter `muster`, wenn es nur bei einer Berührung laufen
     soll. Alle fünf Felder sind Pflicht, `ab` ist eine Stufe aus
     `auswahl.py`, jeder Pfad steht relativ zur Wurzel ohne `/` vorn und
     hinten (sonst: `bestand`, `ablauf` — bis BR-05 stürzte erst der
     Prüfstand daran ab oder das Muster griff nie):

     ```json
     {"id": "attrappe", "ab": "klein", "pfade": ["server/attrappe*.php"],
      "proben": ["attrappe"], "anlass": "Nr. NNN"}
     ```
   - oder der Name in `riegel.proben`, wenn es in jeder Stufe laufen und
     im Tor gegengelesen werden soll. Dann braucht
     `.github/workflows/pruefung.yml` einen Schritt mit `id:`, der es
     fährt, und „Prüfbericht gegenlesen" ein `--riegel`: für eine
     Quelltextprüfung `--riegel "<name>=$q"` (der Schritt „Quelltext"
     fährt sie schon mit — die Zahl in seinem Namen nachziehen), sonst
     `--riegel "<name>=$(r "$X")"` mit `X: ${{ steps.<id>.outcome }}`
     unter `env:`. Fehlt das `--riegel`, ist Stufe 1 rot (`--alle-riegel`)
     — und seit R4-02 schon örtlich (`bestand`, `tor`).

   Ein Ordner, der hier nicht steht, muss in einem Arbeitslauf, in
   `tools/pruefstand/pruefen.sh` oder in `docs/Sandbox-Setup.md` genannt
   sein (`bestand`, `inventur`).
6. **Die Tabelle in 4 erzeugen:** `python3 tools/pruefstand/bericht.py
   erzeugen-doku`. Die Ausgabe ersetzt in Abschnitt 4 den Block von der
   Zeile `<!-- ERZEUGT … -->` bis zur **letzten Zeile, die der Erzeuger
   schreibt** — heute die Stufenregel `migration` unter der Zeile „**Die
   billigen Riegel laufen in jeder Stufe …**". *Bis R4-03 stand hier „bis
   einschließlich der Riegelzeile"; wörtlich befolgt, stand die Stufenregel
   danach zweimal da (P-BR-09, F-R4-23).* Nie von Hand (fehlt der Schritt,
   ist die Tabelle von Hand geändert oder eine Zeile doppelt: `bestand`,
   `tabelle`).
7. **Die Riegel grün:** `bash tools/quelltext/pruefen.sh bestand`,
   `python3 tools/kettenaufrufe/pruefen.py` und
   `bash tools/quelltext/pruefen.sh --selbstprobe`, alle **0 Befunde** bzw.
   alle grün. Die Sollzahl des neuen Mittels steht in seiner Anleitung,
   nicht hier (6.11); ein neuer Riegel bekommt in 6.11 eine Zeile dazu, was
   grün heißt. Dann den `aufruf` **einmal von Hand fahren**: `kettenaufrufe`
   prüft Namen und Schalter, `bestand` die Einträge — keines von beiden
   Positionsargumente. `uhr-stufe1` stand so von PK-03 bis
   BR-04 in der Datei und brach nach 0 s ab (Nr. 316).

Danach läuft es wie jedes andere: Der Prüfstand wählt es nach der Berührung
aus, der Bericht nennt seine Zahl, und das Tor liest den Bericht gegen (5).
Endet `bericht.py` mit einem Fehler, ist der Lauf rot (seit BR-04, Nr. 314).

---

## 7. Benennung

Jedes Konzept führt ein Kürzel; daraus leiten sich alle Nummern ab:

| Form | Was | Beispiel |
|---|---|---|
| `XX-NN Schlagwort` | Arbeitspaket | `PK-03 Prüfstand-Befehl` |
| `XX-MN` | Meilenstein der BetreiberIn | `PK-M1` |
| `E-XX-NN` | Entscheidung | `E-PK-04` |
| `F-XX-NN` | **Befund** — etwas, das gemessen anders ist als angenommen | `F-PK-01` |
| `Q-XX-NN` | **Frage an die BetreiberIn**, mit ihrer Antwort im Konzept | `Q-PK-01` |
| `P-XX-NN` | Prüfpunkt im Prüfdokument | `P-PK-03` |

Nummern sind **zweistellig** und werden **nie wiederverwendet**.

**`F` und `Q` waren bis zum 21.09.2026 dasselbe Kürzel** („Befund oder
offene Frage"), und das ging schief: Konzept PK führte seine Fragen
einstellig (`F-PK-1`) und seine Befunde zweistellig (`F-PK-01`), sodass
beide nebeneinanderstanden und die Befunde vorsichtshalber erst bei 07
begannen. Ein Kürzel, das zwei Dinge meint, wird von der Stelligkeit
auseinandergehalten — und die Stelligkeit ist keine Bedeutung, sondern ein
Tippfehler, der noch keiner war. Seither zwei Kürzel, beide zweistellig.

Commit-Nachrichten beginnen mit dem Paket: `PK-03: …`.

---

## 8. Was nicht geprüft wird — und wo es steht

Diese Liste ist Teil des Ablaufs, nicht sein Kleingedrucktes. Wer eine
Prüfung sucht, die es nicht gibt, findet sie hier zusammen mit dem Grund.

| Was | Stand |
|---|---|
| **Deploy, Anmeldung, `update.php` in einem Zug** | Kein Prüfmittel fährt diesen Weg. Backlog **Nr. 234**. PK-06 legt den benannten Platz dafür in Station D an und **füllt ihn nicht** — ein leerer Platz mit Namen ist ehrlicher als ein Schritt, der sich selbst überspringt. |
| **Echte Geräte** | Der Uhr-Simulator und der Android-Emulator sind Rechenmodelle. Der echte Data Layer, der echte Schlüsselspeicher und das Eingabeverhalten eines gekoppelten Geräts sind darin nicht enthalten; was das im Einzelnen heißt, steht in `android/LIESMICH.md` und in `Geraete-Eingabe.md`. |
| **Echte Sicherungsziele** | Ein Ziel, das wirklich außer Haus schreibt, wird nicht angefahren. Geprüft wird die Bibliothek, nicht die Gegenstelle. |
| **Mailversand** | Die Arbeitsumgebung erreicht nur Port 443 — kein SMTP, kein IMAP. Geprüft wird die Warteschlange und der Katalog; ob eine Nachricht ankommt, sieht nur die BetreiberIn. |
| **Apache des Hosters** | Punktdateien und `.well-known` hängen an einer `.htaccess`, die es örtlich nicht gibt. Das misst **allein** Station D. |
| **Plattformverhalten von Produktiv** | Staging liegt seit dem 20.09.2026 bei einem anderen Hoster und auf einer anderen Datenbank (2.4). Die Plattformmatrix der Hauptstufe (3) ist der Ersatz, nicht Station D. |
| **Der Bericht aus Station B** | Kein Riegel, ein Nachweis (5.2). |

---

## 9. Herkunft

Konzept PK, freigegeben am 21.09.2026 (E-PK-01 bis -30). Die Zahlen dieses
Dokuments sind am 21.09.2026 an `main` `08e032e` gemessen; wo eine Zahl aus
dem Konzept abweicht, gilt die hier genannte, weil sie jünger ist.
Fortgeschrieben mit Konzept RP (23.09.2026) und Konzept BR (24.09.2026:
Riegel `bestand`, `pysyntax`, `handbuch`, 6.1 und 6.12); deren Zahlen sind
an ihrem Tag gemessen und stehen, wo sie gelten.

Der Werdegang — welches Paket was gebaut hat, welche Fehlanläufe es gab —
steht nicht hier, sondern in den Commit-Nachrichten der PK-Pakete, im
Changelog und im Backlog. Das ist Grundsatz 6, angewandt auf dieses
Dokument selbst.
