# Prüfablauf — jede Prüfung einmal, an ihrer Stelle

*Stand: 21.09.2026 · Arbeitsumgebung: `Sandbox-Setup.md` · Architektur und
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

Dieses Dokument beschreibt die Prüfkette vollständig. Gebaut ist sie noch
nicht. Die Spalte **Stand** sagt bei jedem Stück, woran man ist; ein Stück
ohne „gilt" ist eine Vorgabe an das genannte Paket, keine Beschreibung der
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
| Arbeitsumgebung in vier Ausbaustufen | entsteht mit **PK-02** (`Sandbox-Setup.md` 2) |
| Station B, der Prüfstand-Befehl, die drei Stufen (3) | entsteht mit **PK-03** |
| `pruefablauf.json`, die Tabelle Berührung → Probe (4) | entsteht mit **PK-03** |
| Der Prüfbericht und seine Gegenlesung (5) | entsteht mit **PK-03** (Bericht) und **PK-05** (Gegenlesung) |
| Station C in der beschriebenen Form (2) | entsteht mit **PK-05** |
| Station D in der beschriebenen Form (2) | entsteht mit **PK-06** |

Bis dahin gilt für Station C, was in `.github/workflows/pruefung.yml` steht,
und für Station D, was in `.github/workflows/auslieferung.yml` steht.
Abschnitt 2 nennt bei beiden den heutigen Umfang mit Zahl.

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
   Verweis auf eine Nummer. Ohne diese Zeile steht es auf der Streichliste.
6. **Geschichte steht im Commit, nicht im Werkzeug.** Eine Anleitung sagt,
   was das Werkzeug tut; was es einmal gefunden hat, sagen Commit-Nachricht,
   Backlog und Changelog.
7. **Kein stilles Überspringen, keine grüne Zahl ohne Gegenstand.** Ein
   Prüfschritt, der sich selbst überspringt, meldet grün, ohne gemessen zu
   haben — er bricht ab (E-KH-12). Und eine Zahl belegt erst dann etwas,
   wenn sie benennt, was sie gezählt hat (6.5).

---

## 2. Die fünf Stationen

„Stelle" heißt hier **Station**: ein Haltepunkt auf dem Weg vom Quelltext
zum Produktivserver. Der Weg hat fünf davon, und jede hat eine Frage, die
nur sie beantworten kann.

| Station | Wann | Wer | Was dort geprüft wird | Dauer (Ziel) |
|---|---|---|---|---|
| **A Arbeit** | während der Entwicklung | die Instanz in der Arbeitsumgebung | Browser, Emulator, Uhr-Simulator, die Proben, die zur Änderung gehören | nach Bedarf |
| **B Prüfstand** | vor dem Pull Request, **ein Befehl** | dieselbe Instanz | örtliche Installation hochfahren, Referenzbestand, die Proben der Stufe, Bau der Apps (unsigniert), die billigen Riegel; auf Anforderung die Plattformprobe gegen Staging. Ergebnis ist der **Prüfbericht** (5) | klein 5 min, neben 15 min, haupt 45 min |
| **C Tor** | beim Pull Request und auf `main` | GitHub | die billigen Riegel als Gegenlesung des Berichts, Schemaprobe gegen MySQL 8.4.0 und MariaDB 10.6, Bericht passt zum Baum und zur Berührung. **Rot heißt kein Merge**; der Merge ist Sache der Betreiberin | 1 bis 2 min |
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

**Ein Befehl vor dem Pull Request**, `tools/pruefstand/pruefen.sh`. Er
ermittelt die Stufe selbst (3), fährt die örtliche Installation hoch, läuft
die Proben, die zur Berührung gehören (4), und **erzeugt** am Ende den
Prüfbericht (5). Er kommt mit **PK-03**.

Station B ist die geprüfte Partei. Ihr Bericht ist ein **Nachweis**, kein
Riegel — was das heißt und wo die Grenze liegt, steht in 5.2.

### 2.3 Station C — das Tor

Beim Pull Request und auf `main`, nirgends sonst. **Pushes sind Pushes**:
Ein Push auf einen Arbeitszweig löst keine Prüfung aus, und es gibt keinen
Riegel davor und keinen Hook.

**Der Riegel ist der Zweigschutz, und er gilt seit dem 21.09.2026.** Zwei
Rulesets auf `main`: „Main Protect" (Pull-Request-Pflicht, Pflichtprüfung
`Stufe 1`, kein Force-Push, kein Löschen, Bypass leer) und „Main
Merge-Recht" (Restrict updates, Bypass nur die Betreiberin, Modus „pull
requests only"). Die Maske und die drei Fallen dabei stehen in
`Rahmenplan.md` 6b.

> **Ohne Zweigschutz ist jedes Tor eine Auskunft.** Der Lauf färbt sich rot,
> und der Stand liegt trotzdem auf `main` — und damit auf Staging. Das war
> der Zustand bis zum 21.09.2026; wer eine ältere Quelle liest, findet dort
> `protected: false`.

**Und der Zweigschutz allein reicht nicht.** Gemessen am 21.09.2026
(F-PK-01): Das Ruleset weist das Konto ab, unter dem die Arbeitsumgebung
pusht — aber die GitHub-Werkzeuge in Claude Code sprechen mit dem Anschluss
der Betreiberin und handeln als sie; ein Merge über die Schnittstelle ging
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

**Heutiger Umfang** (gemessen am 21.09.2026 an `main` `08e032e`): Der
Arbeitslauf `pruefung.yml` heißt „Prüfung" und führt zwei Jobs — `Stufe 1`
mit **24** Schritten und `Schema gegen …` mit **4**, zusammen **28**. Er
läuft heute noch bei **jedem** Push auf **jedem** Zweig; **PK-05** nimmt ihm
das ab, dazu den Bau der Apps und die Bereichserkennung, und setzt die
Gegenlesung des Berichts an ihre Stelle. Der Jobname `Stufe 1` bleibt dabei
unverändert — der Zweigschutz nennt ihn beim Namen, und ein umbenannter Job
hängt die Pflichtprüfung still ab.

### 2.4 Station D — Staging

Nach dem Merge, auf `main`. Die Auslieferung selbst bleibt, wie Kette II sie
gebaut hat (`ausliefern-lauf.yml`, **17** Schritte, gemessen 49 s); PK fasst
den Transport nicht an. Verschlankt wird, was danach misst.

**Drei Schritte sollen bleiben**, weil nur die echte Anlage sie beantwortet:
antwortet `login.php` wie eine eingerichtete Installation, sind Punktdateien
gesperrt und ist `.well-known` offen (beides Apache des Hosters), und läuft
**ein** Kreislauf `edbak` durch — als Plattformprobe auf PHP 8.3 beim
Hoster. Dazu ein benannter leerer Platz für Backlog Nr. 234 (8).

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

Beim Tag `web-vX.Y.Z`, nach der Pflichtfreigabe durch die Betreiberin und
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

*Entsteht mit PK-03.*

Der Umfang richtet sich nach der Versionsstufe in `server/version.php` im
Unterschied gegen `main` (Zählweise in `CLAUDE.md` 2) und nach der
Berührung. Der Befehl bestimmt die Stufe selbst und **sagt sie**; `--stufe`
überschreibt und steht im Bericht.

| Stufe | Auslöser | Umfang |
|---|---|---|
| **klein** | Korrekturstufe `a.a.Y`, oder kein Versionssprung | billige Riegel; je berührter Datei die zugeordneten Proben (4); Bilderlauf der berührten Seiten in drei Breiten, Chromium; Bedienprobe der berührten Seiten |
| **neben** | Nebenstufe `a.X.a` | alles ohne Anlage: alle Proben gegen die örtliche Installation, beide Kreisläufe, Bilderlauf aller Seiten in acht Breiten, Chromium, Bedienprobe aller Seiten; Handy- und Uhr-Bau, wenn berührt |
| **haupt** | Hauptstufe `X.a.a`, oder `--stufe haupt` | wie neben, dazu die **Plattformmatrix** (PHP 8.3.33 und 8.4; MariaDB 10.11 und 10.6, MySQL 8.0 und 8.4.0 — Schemaprobe und ein Kreislauf `edbak` je Paar), Bilderlauf mit allen drei Engines über alle Seiten, Messstand, Anteilprobe, Verbindungsprobe, Uhr-Prüfstand Stufe II |

**Warum die Matrix in die Hauptstufe gehört und nicht in die Kette:** Der
Export scheiterte am 21.09.2026 nicht an der Anwendung, sondern an einem
bewahrten Alias, der auf MySQL 8.4 reserviert ist und auf MariaDB nicht.
Drei Kreisläufe zu je vier Minuten örtlich fanden ihn; drei Läufe zu je
fünfzehn Minuten in der Kette hatten es nicht getan. Der Kreislauf `edbak`
läuft bei `haupt` und bei `--gegen staging` deshalb **gegen MySQL 8.4.0**,
nicht nur gegen MariaDB.

**Eine Hauptstufe, die mit `--stufe klein` gefahren wurde, fällt im Tor
auf** — das ist eine der vier roten Lagen in 5.1.

---

## 4. Berührung → Probe

*Entsteht mit PK-03.*

`tools/pruefstand/pruefablauf.json` ist die eine Zuordnung von der berührten
Datei zur Probe: je Muster auf Dateipfade die Werkzeuge und die Stufe, ab
der sie laufen. Die Tabelle an dieser Stelle wird **daraus erzeugt**, wie
die Tabellen in `Design.md` — wer sie von Hand ändert, ändert sie an der
falschen Stelle.

Zwei Dinge hängen mit daran: `tools/kettenaufrufe/` liest dieselbe Datei
mit, damit ein Aufruf in der Kette und eine Zuordnung nicht
auseinanderlaufen; und der Prüfstand meldet, wenn eine Datei unter `server/`
**kein** Muster trifft — eine Datei ohne Zuordnung ist eine Datei ohne
Probe, und das soll man sehen, statt es zu vermuten.

---

## 5. Der Prüfbericht

*Entsteht mit PK-03 (Erzeugung) und PK-05 (Gegenlesung).*

Der Prüfstand schreibt am Ende einen Block, der in die Commit-Nachricht
gehört und maschinell lesbar ist:

```
Prüfstand: neben · Baum a1b2c3d… · Konfiguration web
  syntax=php:511/0,py:48/0  textprobe=0  quelltext=6/0
  kreislauf=edbak:0,csv:0  bilderlauf=62/0/0/0  proben=ingest:10/10
  handy=nicht berührt  uhr=nicht berührt
```

### 5.1 Vier Lagen, in denen das Tor rot wird

- Der **Baum-Hash** ist nicht der Baum des Commits. Das fängt den Fehler,
  der in O9c passiert ist: gemessen wurde vor der letzten Änderung, gemeldet
  wurde die Zahl von davor.
- Die **Stufe** ist kleiner, als die Versionsstufe im Unterschied verlangt.
- Eine **berührte** Fläche ist als „nicht berührt" gemeldet.
- Ein **billiger Riegel** liefert im Tor eine andere Zahl als der Bericht.

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

---

## 6. Regeln für Prüfmittel

### 6.1 Ein Prüfmittel braucht einen Fehler

Jedes Werkzeug nennt in einer Zeile „Anlass: Nr. …", welchen Fehler es
gefangen hat oder hätte fangen müssen. Ohne diese Zeile steht es auf der
Streichliste. Der Grund ist nicht Buchhaltung: Ein Prüfmittel ohne
gefundenen Fehler misst entweder etwas, das nicht kaputtgeht, oder es misst
daneben — und beides kostet bei jedem Lauf Zeit.

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

*Gemessen am 21.09.2026: 47 Anleitungen mit zusammen 7 206 Zeilen, dazu ein
Werkzeug ohne Anleitung (`tools/wegwerfdomains/`). Die Form wird mit PK-04
hergestellt.*

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
`tools/integritaetswache/`, `tools/vollstaendigkeit/`,
`tools/stilvergleich/` und `tools/wartungsprobe/` — **vier**.
`tools/wortliste/zerlegen.py` trägt die kurze Form mit dem Kommentar
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
`tools/wortliste/`: Es zählt nach, ob Land und Luft neutral benannt sind.
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

Mit **PK-04** wird die Wortliste zur **Textprobe** erweitert (fünf
Regelklassen: Luftbegriffe, Hausform, E-Mail-Adressen, Adressen im Netz,
Namen und Rufnamen) und läuft dann einmal im Tor statt bei jeder
Textänderung von Hand. Bis dahin gilt dieser Abschnitt unverändert.

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
| `tools/wortliste/` | 0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen |
| `tools/vollstaendigkeit/` | auf der Schwelle oder darunter — **die Schwelle steht in `.github/workflows/pruefung.yml`** (`--hoechstens`) und wird hier nicht wiederholt |
| `tools/screenshots/` | 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe falscher Höhe, 0 Karten außerhalb von `main.inhalt` |
| `tools/kettenaufrufe/` | 0/0 |
| `./gradlew build` | 0 Lint-Fehler, 0 Fehlschläge |
| `tools/stilvergleich/` | die Liste deckt sich mit der Liste der geplanten Änderungen (6.10) |

**PK-04 ändert zwei davon** (E-PK-16 nimmt der Vollständigkeit die
Symbolzählung und ihre Schwelle; E-PK-08 erweitert die Wortliste zur
Textprobe). **Bis dahin gilt diese Tabelle unverändert** — insbesondere läuft
die Vollständigkeit weiter gegen die Schwelle, die in `pruefung.yml` steht.

*Warum hier keine Zahl steht:* `docs/Technik.md` 6.2 trug die Zahl des
Bilderlaufs ein zweites Mal und stand bis Web 20.21.1 auf 366, während die
Kette längst mit 377 lief. Eine Schwelle hat genau einen Ort, und das ist
der Aufruf, der sie anwendet.

---

## 7. Benennung

Jedes Konzept führt ein Kürzel; daraus leiten sich alle Nummern ab:

| Form | Was | Beispiel |
|---|---|---|
| `XX-NN Schlagwort` | Arbeitspaket | `PK-03 Prüfstand-Befehl` |
| `XX-MN` | Meilenstein der Betreiberin | `PK-M1` |
| `E-XX-NN` | Entscheidung | `E-PK-04` |
| `F-XX-NN` | Befund oder offene Frage | `F-PK-01` |
| `P-XX-NN` | Prüfpunkt im Prüfdokument | `P-PK-03` |

Nummern sind **zweistellig** und werden **nie wiederverwendet**.
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
| **Mailversand** | Die Arbeitsumgebung erreicht nur Port 443 — kein SMTP, kein IMAP. Geprüft wird die Warteschlange und der Katalog; ob eine Nachricht ankommt, sieht nur die Betreiberin. |
| **Apache des Hosters** | Punktdateien und `.well-known` hängen an einer `.htaccess`, die es örtlich nicht gibt. Das misst **allein** Station D. |
| **Plattformverhalten von Produktiv** | Staging liegt seit dem 20.09.2026 bei einem anderen Hoster und auf einer anderen Datenbank (2.4). Die Plattformmatrix der Hauptstufe (3) ist der Ersatz, nicht Station D. |
| **Der Bericht aus Station B** | Kein Riegel, ein Nachweis (5.2). |

---

## 9. Herkunft

Konzept PK, freigegeben am 21.09.2026 (E-PK-01 bis -30). Die Zahlen dieses
Dokuments sind am 21.09.2026 an `main` `08e032e` gemessen; wo eine Zahl aus
dem Konzept abweicht, gilt die hier genannte, weil sie jünger ist.

Der Werdegang — welches Paket was gebaut hat, welche Fehlanläufe es gab —
steht nicht hier, sondern in den Commit-Nachrichten der PK-Pakete, im
Changelog und im Backlog. Das ist Grundsatz 6, angewandt auf dieses
Dokument selbst.
