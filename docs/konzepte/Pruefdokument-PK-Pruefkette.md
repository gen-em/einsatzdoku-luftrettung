# Prüfdokument PK — Prüfkette

Gehört zu `Konzept-PK-Pruefkette.md`. Nach `CLAUDE.md` 7: Was wurde
maschinell geprüft (Mittel und Zahl), was im Browser, was nicht und warum,
und eine abhakbare Prüfliste. Angelegt mit PK-M1; fortgeschrieben mit PK-01.

## 0. Was nicht geprüft werden konnte

**Stand nach PK-03.** Keines der drei Pakete ändert eine Zeile
Anwendungscode oder eine Zeile der Kette: PK-01 schreibt Dokumente und
eine Deny-Liste, PK-02 und PK-03 bauen Werkzeuge. Deshalb ist die Liste
kurz — aber sie ist nicht leer, und sie steht hier vorn und nicht in
einer Fußnote.

| Was | Warum nicht | Wann dann |
|---|---|---|
| **Die Kette auf einem echten Lauf** | PK-01 ändert `.github/workflows/` nicht. Dass `pruefung.yml` und `auslieferung.yml` nach den Änderungen unverändert grün laufen, belegt erst der nächste Push — und der gehört zum Phasen-PR, nicht zum Paket. | beim Phasen-PR |
| **Die Wirksamkeit der Deny-Liste für *andere* Instanzen** | Gemessen ist sie in **dieser** Sitzung. Solange PK-01 nicht gemergt ist, liegt die Datei nur auf dem Arbeitszweig; eine Instanz, die `main` auscheckt, hat den Riegel nicht. | nach dem Merge des Phasen-PR |
| **Ob die Deny-Liste einen *durchgeführten* Merge verhindert** | Nicht messbar, und zwar grundsätzlich: Die Regel nimmt das Werkzeug ganz aus dem Zusammenhang, es gibt also keinen abgewiesenen Aufruf. Gemessen wird die Abwesenheit mit Gegenprobe (3). | — (Messvorschrift steht in 3.1) |
| **Die Browserprüfung** | PK-01 fasst keine Oberfläche an — keine Datei unter `server/`, kein Stylesheet, kein Markup. Es gibt nichts zu sehen. | — |
| **Ein Lauf der Kette gegen die neuen Werkzeuge** | `pruefung.yml` ruft weiterhin die Einzelwerkzeuge, nicht `pruefablauf.json`. Dass beide dieselben Zahlen liefern, ist nicht gemessen. | PK-05 |
| ~~Der Bau der Uhr-App~~ | **Nachgeholt am 21.09.2026.** SDK 9.2.0, 1332 Schriftdateien, 99 von 99 Manifest-Geräten, 173 mit `compiler.json`, 0 fehlende Simulatorbibliotheken; Stufe I **99 übersetzt / 0 fehlgeschlagen**. **P-PK-11 ist vollständig.** Was dabei auffiel, steht in F-PK-19 und F-PK-21. | erledigt |
| **Die meisten Aufrufe in `pruefablauf.json`** | Von **40** eingetragenen Proben sind **17 über den Prüfstand gefahren** worden (15 grün, 2 rot — davon einer ein Verdrahtungsfehler, einer ein echter Befund). Die übrigen 23 stehen eingetragen und sind nie ausgeführt. `tools/kettenaufrufe/` hält sie gegen die Schnittstelle ihres Werkzeugs (0 Befunde) — das ist etwas anderes als ein Lauf. | beim ersten Paket, das die jeweilige Fläche berührt |
| ~~Der lange Lauf mit Bilderlauf und Bedienprobe~~ | **Nachgeholt am 21.09.2026** — erster Lauf 15 Proben, 13 grün, 2 rot, 0 nicht gemessen, rc 1; **Gegenprobe nach den Korrekturen: 15 grün, 0 rot, 0 nicht gemessen, rc 0**. Bilderlauf **496 Einzelbilder / 62 Kontaktbögen / Überlauf 0 / Knöpfe falscher Höhe 0 / 162 Karten, 0 außerhalb `main.inhalt`**, Bedienprobe grün. Die zwei roten sind **F-PK-20** und behoben. | erledigt |
| **Ein frischer Container** | Die Abnahme von `aufbauen.sh` ist in **dieser** Sitzung gefahren, nicht in einem frisch gestarteten Container. Der Unterschied ist messbar: Hier waren die sechs Pakete des alten Hooks schon installiert. Was ein frischer Container vorfindet, zeigt erst die nächste Sitzung. | nächste Sitzung |
| **Der rote Versuch 1 von Lauf 35639445224** | Die Lauf-API liefert zu einem Lauf den **jüngsten Versuch**; das Protokoll von Versuch 1 (Backup-Tor, 40 Aufrufe, 13 min) ist über das benutzte Werkzeug nicht erreichbar. Was dort steht, ist **berichtet, nicht nachgemessen** — F-PK-03 sagt es an der Stelle noch einmal. | wenn jemand mit Zugang zur Oberfläche das Protokoll des Versuchs 1 liest |
| **Eine grüne Stufe 2 nach einem Merge** | Sie kann bis PK-06 nicht grün werden: Schritt 6 meldet sich mit einem Konto an, das es auf der neuen Staging-Anlage nicht gibt (F-PK-04), und ein solches Konto wird **nicht angelegt**. **Stufe 2 ist nach einem Merge planmäßig rot am Schritt 6**; grün sind die Schritte 1 bis 5, und die sind der Nachweis. | PK-06 |
| **Die Zahlen der Kette in `Pruefablauf.md` 2.3/2.4** | Schrittzahlen und Jobnamen sind am Quelltext gezählt, nicht an einem Lauf gemessen. Die Dauerangaben (49 s Staging, 16 min Stufe 2) sind aus dem Konzept übernommen und hier **nicht** nachgemessen. | PK-05, PK-06 |

## 1. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-PK-01 | Zweigschutz und Merge-Recht auf `main` (PK-M1) | (a) Claude-Instanz öffnet PR und versucht den Merge über die API; (b) dieselbe Instanz pusht direkt auf `main`; (c) die Betreiberin mergt den PR | (a) abgewiesen, (b) abgewiesen, (c) geht | ein Merge oder Push durch die Instanz kommt durch | **gemessen 21.09.2026 — (a) kam durch, siehe F-PK-01**; (b) abgewiesen; (c) mit PR #70 |
| P-PK-02 | edbak-500 (`097D7622`) lokal reproduzieren (PK-03) | Kreislauf edbak gegen die lokale Installation unter PHP 8.3.33 und 8.4 | Export fällt lokal mit demselben Fehler, oder er fällt nicht (dann Plattform) | — | **erledigt 21.09.2026**: gegen MariaDB grün, gegen MySQL 8.4.0 rot mit `1064 near 'manual'`; behoben in Web 20.26.3 (PR #69, Nr. 267) |
| P-PK-03 | Merge-Werkzeug der Claude-Instanzen gesperrt (Folge aus F-PK-01) | `.claude/settings.json` trägt `mcp__github__merge_pull_request` und `mcp__github__enable_pr_auto_merge` in `permissions.deny`; eine Instanz sucht **beide** Werkzeuge **und drei andere** desselben Anschlusses | die beiden gesperrten sind **nicht mehr auffindbar**, die drei anderen laden unverändert | eines der beiden ist weiter da (Riegel wirkt nicht) — **oder alle fünf sind weg** (dann ist der Anschluss ausgefallen und es ist gar nicht gemessen) | **erledigt 21.09.2026 — 2 von 2 gesperrt, 3 von 3 Gegenproben vorhanden** (3.1) |
| P-PK-04 | `CLAUDE.md` 6 unter 60 Zeilen, die drei Regeln je an einer Stelle (PK-01) | die vier Befehle aus 3.2 | 6 unter 60 Zeilen; je Regel 1 normative Fundstelle in `docs/` und `CLAUDE.md` | eine Regel steht zweimal, oder sie steht nirgends mehr | **erledigt 21.09.2026 — 58 Zeilen; 1/1/1** (3.2) |
| P-PK-05 | Die Prüfmittel bleiben auf dem heutigen Stand (PK-01 bis PK-03) | `wortliste.py`, `vollstaendigkeit/pruefen.py --hoechstens 398`, `kettenaufrufe/pruefen.py` | Wortliste 0/0/0, Vollständigkeit auf der Schwelle, Kettenaufrufe 0 Befunde | eine Zahl wandert, ohne dass ein Paket sie bewusst verschoben hat | **erledigt 21.09.2026** (3.3) |
| P-PK-07 | Ein Beschaffer statt zweier, mit Nachweis (PK-02) | `bash tools/sandbox/aufbauen.sh web` im Container | 10 von 10 Stücken, **3 von 3 Engines**, 8 von 8 Umgebungswerten, Rückgabewert 0 | eine Engine fehlt, oder der Lauf meldet grün ohne die Engines einzeln zu nennen | **erledigt 21.09.2026** (4.1) |
| P-PK-08 | Die örtliche Anlage mit einem Befehl (PK-02) | `bash tools/sandbox/hochfahren.sh` | HTTP **200** auf `login.php`, Fassung genannt, Rückgabewert 0 | ein anderer Code, oder „läuft" ohne Zahl | **erledigt 21.09.2026** (4.2) |
| P-PK-09 | Plattformmatrix (PK-02) | `bash tools/sandbox/plattform.sh alles` | vier Fassungen bereit, **4 × „19 Prüfungen, 0 Fehlschläge"** | eine Fassung fehlt oder eine Probe meldet einen Fehlschlag | **erledigt 21.09.2026 — 29,7 s** (4.3) |
| P-PK-10 | Der Weg nach draußen ohne abgeschaltete Prüfung (PK-02) | drei Engines, örtlich und gegen die Prüfanlage, anmelden | **6 von 6** angemeldet, Dialog geschlossen, **kein** `ignoreHTTPSErrors` nach draußen | eine Engine scheitert, oder die Prüfung ist abgeschaltet | **erledigt 21.09.2026** (4.4) |
| P-PK-12 | Der Prüfstand-Befehl je Stufe (PK-03) | `bash tools/pruefstand/pruefen.sh --stufe klein` (ebenso neben, haupt) | je Lauf: Stufe genannt, Proben gefahren, Bericht erzeugt, Rückgabewert 0 | eine Probe wird still übersprungen, oder der Bericht fehlt | **erledigt 21.09.2026** (5.1) |
| P-PK-13 | Die Selbstproben (PK-03) | `bericht.py lesen --selbstprobe`; `auswahl.py --selbstprobe` | **6 Lagen / 0 Fehlschläge** (5 rote, 1 grüne) bzw. **11 Lagen / 0** | eine rote Lage wird nicht rot | **erledigt 21.09.2026** (5.2) |
| P-PK-14 | Abdeckung: keine Datei ohne Muster (PK-03) | `auswahl.py --abdeckung` | **0 Dateien unter `server/` ohne Muster** | eine Datei trifft kein Muster und hat damit keine Probe | **erledigt 21.09.2026 — 262 Dateien, 0 ohne Muster** (5.3) |
| P-PK-15 | `kettenaufrufe` liest die Zuordnung mit (PK-03) | Fehler einbauen (`--format` statt `--art`), Werkzeug fahren, zurücksetzen | mit Fehler **2 Befunde** mit Namen, ohne Fehler **0** | der Fehler kommt durch | **erledigt 21.09.2026** (5.4) |
| P-PK-17 | Nach dem Merge von PR #71: ein Arbeitszweig-Push erzeugt nur noch **einen** Lauf (Vorgriff auf PK-05) | auf einem Arbeitszweig committen und pushen, dann die Läufe von `pruefung.yml` zu diesem Commit zählen | **genau 1 Lauf**, Ereignis `pull_request`; **kein** `push`-Lauf | es entstehen zwei Läufe, oder gar keiner (dann prüft der Zweig nichts mehr) | **teilweise gemessen 21.09.2026 (5.6)** — die Wirkung ist belegt, der volle Bedienweg noch nicht gefahren |
| P-PK-20 | Die vier roten Proben aus 5.7 trennen: veraltete Erwartung oder Fehler der Anwendung (F-PK-18, F-PK-22) | je Probe den Befund nachvollziehen, Referenzbestand erneuern, erneut fahren | jede Probe nennt danach entweder eine behobene Anwendung oder eine berichtigte Erwartung — mit Zahl | eine Probe bleibt rot, ohne dass jemand sagen kann, woran | **offen** — eigene Korrekturstufe, kein PK-Paket |
| P-PK-16 | Der offene Befund der Wiederherstellungsprobe | Sicherungsziel eintragen, `php tools/wiederherstellungs-probe/probe.php` | 110 Erwartungen, 0 nicht erfüllt | die zwei Befunde bleiben auch mit Sicherungsziel stehen (dann ist es die Anwendung) | **offen** — siehe F-PK-18 |
| P-PK-11 | Ausbaustufen `android` und `uhr` (PK-02) | `aufbauen.sh android` → `./gradlew build`; `aufbauen.sh uhr` → `pruefstand.sh reihe` | 0 Lint-Fehler, 0 Fehlschläge bzw. Reihe grün | ein Fehlschlag, oder das SDK fehlt | **beide erledigt 21.09.2026.** android: BUILD SUCCESSFUL in 7m 19s, **0 Lint-Fehler**, **670 Prüffälle / 0**. uhr: `aufbauen.sh uhr` rc 0 mit Gegenstand — SDK **9.2.0**, **1332** Schriftdateien, **99 von 99** Manifest-Geräten, **173** Geräte mit `compiler.json`, **0** fehlende Simulatorbibliotheken, Nachweis **15 Stücke ok** (darunter die zwei neuen Uhr-Zeilen), **8 von 8** Umgebungswerten. Stufe I danach über alle Geräte: **99 übersetzt, 0 fehlgeschlagen, 0 ohne Gerätedatei**, rc 0. **Der Bedienweg oben war unvollständig** — siehe F-PK-21 |
| P-PK-18 | Zwei Merges kurz hintereinander erzeugen keine sich störenden Läufe mehr (F-PK-02, F-PK-03) | nach PK-06: zwei PRs innerhalb einer Minute nach `main` mergen; die Läufe von `auslieferung.yml` ansehen | der zweite Lauf **bricht den ersten ab** oder wartet; **kein** Backup-Tor läuft in die Job-Pause des Nachbarn; der Produktivlauf wird **nie** abgebrochen | ein Lauf steht 13 min im Backup-Tor, oder ein Produktivlauf wird abgebrochen (dann ist die Gruppe falsch herum gebaut) | **offen** — gehört zur Abnahme von PK-06 |
| P-PK-19 | Stufe 2 kommt ohne Konto mit Vorgabekennwort aus (F-PK-04) | nach PK-06: Push auf `main`, Stufe 2 ansehen | Stufe 2 grün; der Bilderlauf läuft im **Prüfstand** gegen die örtliche Anlage (Demo-Konto aus der Fixture) | Stufe 2 meldet wieder `Anmeldung als demo@gen-em.org gescheitert`, oder jemand hat das Konto auf Staging angelegt | **offen** — bis dahin ist Stufe 2 nach einem Merge **planmäßig rot am Schritt 6** |
| P-PK-06 | Die neuen Dokumente laufen durch die Wortliste (B-S4-06) | `wortliste.py --bereich c`; nachsehen, dass beide Dateien in `BEREICHE["c"]` stehen | beide Dateien werden gelesen, 0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen | der Lauf meldet 0 und hat keine Zeile der neuen Dokumente angesehen | **erledigt 21.09.2026** (3.3) |

## 2. Messprotokoll P-PK-01 (21.09.2026)

Rulesets auf `main` seit dem 21.09.2026: „Main Protect" (PR-Pflicht,
Pflichtprüfung `Stufe 1`, kein Force-Push, kein Löschen, Bypass leer) und
„Main Merge-Recht" (Restrict updates, Bypass nur die Betreiberin, Modus
„pull requests only").

| Messung | Wer | Ergebnis |
|---|---|---|
| (a) Merge von PR #67 über die API (`merge_pull_request`), nach grüner Stufe 1 | Claude-Instanz | **durchgegangen** — Merge-Commit `c78988f`, `merged_by: chodid`. Die GitHub-Werkzeuge von Claude Code laufen über den GitHub-Anschluss der Betreiberin und tragen deren Identität; für das Ruleset war das die Betreiberin selbst (Bypass „pull requests only“). Vor grüner Stufe 1 lautete die Ablehnung nur „Required status check Stufe 1 is in progress“ |
| (b) `git push origin HEAD:main` | dieselbe Instanz, Konto `claude` (GitHub-App über den Proxy) | **abgewiesen**: `GH013 … Cannot update this protected ref` (Ruleset Merge-Recht), dazu „Required status check Stufe 1 is in progress“ und „a merge commit must be used“ |
| (c) Merge dieses PR (Nachmessung) | Betreiberin, von Hand | *mit dem Merge von PR #70 belegt* |

### F-PK-01 — Das Ruleset hält, die Identität nicht

Der Zweigschutz tut, was er soll: Das Konto `claude`, unter dem die Sandbox
pusht, kommt weder per Push noch per Merge auf `main`. Die Lücke liegt davor:
Die GitHub-Werkzeuge in Claude Code (`mcp__github__*`) sprechen mit dem
OAuth-Anschluss der Betreiberin und handeln als sie. Ein Ruleset kann
`chodid` per Werkzeug nicht von `chodid` per Browser unterscheiden.

**Folge für E-PK-04 („Merge ausschließlich Sache der Betreiberin“):** Gegen
Git-Pushes und die App-Identität ist das ein Riegel; gegen den Werkzeugweg
ist es eine Regel. Drei Lagen, damit die Regel nicht allein steht:

1. Das Ruleset bleibt (Riegel gegen `claude` und gegen jede fremde App).
2. `.claude/settings.json` sperrt `mcp__github__merge_pull_request` und
   `mcp__github__enable_pr_auto_merge` in der Deny-Liste des Harness — ein
   Riegel innerhalb von Claude Code, den eine Instanz nicht umgehen kann
   (P-PK-03, PK-01). **Eingetragen und gemessen in PK-01.**
3. `CLAUDE.md` 8 sagt es als Satz: Eine Claude-Instanz mergt nie; sie
   öffnet PRs, die Betreiberin mergt. **Eingetragen in PK-01.**

Was bleibt: Wer die Deny-Liste im Repositorium ändert, hebt Lage 2 auf; das
fällt im PR auf, weil `.claude/settings.json` in der Berührung steht. **Zwei
weitere Wege stehen offen** und sind in `docs/Pruefablauf.md` 2.3 benannt:
eine Regel mit Klammern wird beim Laden still übersprungen, und
`.claude/settings.local.json` rangiert über der geteilten Datei (F-PK-13).

## 3. Messprotokoll PK-01 (21.09.2026, Zweig `claude/serene-dijkstra-bcpbjy`)

### 3.1 P-PK-03 — die Deny-Liste

**Der Bedienweg aus der ersten Fassung war nicht messbar.** Dort stand „eine
Instanz ruft das Werkzeug auf" und „der Aufruf wird vom Harness verweigert".
So verhält es sich nicht: Eine Deny-Regel aus dem bloßen Werkzeugnamen nimmt
das Werkzeug **ganz** aus dem Zusammenhang. Es gibt keinen abgewiesenen
Aufruf, weil es nichts mehr aufzurufen gibt. Gemessen wird die **Abwesenheit**
— und nur mit Gegenprobe, sonst zählt ein ausgefallener Anschluss als
wirksamer Riegel.

| Messung | Ergebnis |
|---|---|
| Vor dem Eintrag: beide Werkzeugnamen in der Liste der aufschiebbaren Werkzeuge | vorhanden (beide namentlich genannt) |
| `.claude/settings.json` um `permissions.deny` ergänzt, JSON gültig, Hook-Block erhalten | ja |
| Nach dem Eintrag: `mcp__github__merge_pull_request`, `mcp__github__enable_pr_auto_merge` | **2 von 2 nicht mehr auffindbar** |
| Gegenprobe: `pull_request_read`, `create_pull_request`, `resolve_review_thread` desselben Anschlusses | **3 von 3 unverändert ladbar** |

**Damit ist es die Liste und nicht ein Ausfall.** Die Regeln stehen **ohne
Klammern** — mit Klammern würde die Zeile beim Laden übersprungen, und die
Datei sähe streng aus, ohne zu sperren.

### 3.2 P-PK-04 — Kürzung und Doppelstellen

| Messung | Befehl | Ergebnis |
|---|---|---|
| `CLAUDE.md` 6 | Zeilen von `## 6. Prüfen` bis vor `## 7.` | **58 Zeilen** (vorher 166) |
| `CLAUDE.md` gesamt | `wc -l CLAUDE.md` | **469** (vorher 557) |
| Emulator-Regel | `grep -rnF 'Der Emulator läuft mit' CLAUDE.md docs/` ohne Geschichte | **1** — `docs/Pruefablauf.md` 6.9 |
| Tag-Rumpf-Regel | `grep -rnF 'php\b\|=).*?\?>\|[^>]' CLAUDE.md docs/` ohne Geschichte | **1** — `docs/Pruefablauf.md` 6.4 |
| Wortlisten-Regel | `grep -rnF 'läuft durch die Wortliste' CLAUDE.md docs/` ohne Geschichte | **1** — `docs/Pruefablauf.md` 6.6 |
| Neue Dokumente | `wc -l` | `Pruefablauf.md` **561**, `Sandbox-Setup.md` **306** |

**„Ohne Geschichte" heißt** — und das ist Teil der Messvorschrift, nicht
eine Fußnote: ausgenommen sind `docs/CHANGELOG.md`, `docs/Backlog.md`,
`docs/Rahmenplan-Archiv.md`, `docs/konzepte/` und die Verlaufstabelle in
`docs/Rahmenplan.md` (Zeilen der Form `| **NN** |`). Ohne diese Ausnahmen
ist die Abnahme nicht erfüllbar und auch nicht gemeint — siehe **F-PK-07**.

**Zwei Doppelstellen wurden dabei aufgelöst**, beide hätten die Abnahme
sonst gerissen:

- `docs/Rahmenplan.md` 2.2 führte die Wortlisten-Pflicht ein zweites Mal
  normativ aus („bei jeder sichtbaren Text- oder Doku-Änderung, Soll
  0/0/0"). Sie ist jetzt ein Verweis; R27, R28 und R35 bleiben als
  Entscheidungen verzeichnet.
- `tools/wortliste/LIESMICH.md` trug den B-S4-06-Block **wörtlich** wie
  `CLAUDE.md`. Auch dort steht jetzt ein Verweis. *(Zählt für die Abnahme
  nicht mit — sie misst `docs/` und `CLAUDE.md` —, aber eine Regel, die an
  einer Stelle stehen soll, darf nicht an zweien stehen bleiben.)*

### 3.3 P-PK-05 und P-PK-06 — die Prüfmittel

Gefahren **nach** der letzten Änderung, nicht zwischendurch:

| Mittel | Ergebnis |
|---|---|
| `python3 tools/wortliste/wortliste.py` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen**; 100 Regeln, 100 gegriffen |
| `python3 tools/vollstaendigkeit/pruefen.py --hoechstens 398` | **398 Befunde, auf der Schwelle, unverändert** |
| `python3 tools/kettenaufrufe/pruefen.py` | **43 Aufrufe geprüft, 0 Befunde, 0 ungeprüft** |

**Was die Wortliste gemessen hat, und warum die Zahl diesmal etwas heißt.**
`docs/Pruefablauf.md` und `docs/Sandbox-Setup.md` sind normative Dokumente
und standen darum bis PK-01 **nicht** in `BEREICHE["c"]` — ein Lauf hätte 0
gemeldet, ohne eine Zeile davon anzusehen. Genau der Fall aus B-S4-06. Beide
sind jetzt eingetragen (`wortliste.py`, `LIESMICH.md` Bereichstabelle).

Der erste Lauf danach meldete **26 Treffer**, sämtlich das Muster `station`:

- **24 in `docs/Pruefablauf.md`** — „Station" im Sinn von *Haltepunkt der
  Prüfkette* (A Arbeit bis E Produktiv). Das ist ein Homonym zum
  Luftrettungs-Begriff für den Standort. Eine Ausnahme mit Begründung
  (`pruefablauf-station-haltepunkt`, Klasse Homonym) trägt es; der Ersatz
  „Standort" wäre hier nicht neutraler, sondern falsch.
- **2 in `docs/Sandbox-Setup.md`** — dort war das Wort entbehrlich und ist
  **umformuliert**, nicht ausgenommen. Deshalb steht diese Datei in keiner
  Ausnahme.

Die Vollständigkeit liest nur `server/` (nachgesehen: `SERVER =
os.path.join(WURZEL, 'server')`) — PK-01 kann ihre Zahl nicht bewegen. Von
den 398 Befunden sind **330** „Unicode-Zeichen als Symbol im Markup"; das ist
der Bestand, den E-PK-16 in PK-04 auflöst.

## 4. Messprotokoll PK-02 (21.09.2026)

### 4.1 P-PK-07 — ein Beschaffer statt zweier

**Der Widerspruch ist gemessen entschieden, nicht abgewogen.** Playwright
nennt beim gescheiterten Start die fehlenden Pakete selbst, und es sind die
vier aus `containeraufbau/aufbau.sh`.

| Messung | Ergebnis |
|---|---|
| Engine-Dateien im Abbild | `chromium-1194`, `firefox-1495`, `webkit-2215` — **alle drei da**; `playwright install` ist damit überflüssig |
| Zustand **nach** dem alten Hook (er war in dieser Sitzung gelaufen) | seine **sechs** Pakete installiert, die **vier** von `aufbau.sh` **fehlend** |
| Engines in diesem Zustand | chromium 141.0.7390.37 ok, firefox 142.0.1 ok, **webkit FEHLT** |
| nach `aufbauen.sh web` | **3 von 3**: chromium 141.0.7390.37, firefox 142.0.1, **webkit 26.0** |
| Nachweis insgesamt | 10 von 10 Stücken, 8 von 8 Umgebungswerten, Rückgabewert 0 |

**Das ist der gefährliche Fall:** Ein Dreimotorenlauf ohne WebKit ist ein
Zweimotorenlauf und meldet dieselbe grüne Zahl.

### 4.2 P-PK-08 — die örtliche Anlage

`hochfahren.sh` auf einer leeren Datenbank: eingerichtet, Referenzbestand
eingespielt (**106 Einsätze, 21 Diensttage, 2 Geräte**), TLS davor,
`login.php` **HTTP 200**, gemeldete Fassung **20.26.2**, Rückgabewert **0**.

### 4.3 P-PK-09 — die Plattformmatrix

`plattform.sh alles` in **29,7 s**:

| Fassung | bereit nach | Schemaprobe |
|---|---|---|
| MariaDB 10.11.14 (örtlich, Port 3306) | lief bereits | **19 / 0** |
| MariaDB 10.6.28 (`mariadb:10.6`, Port 3310) | 5 s | **19 / 0** |
| MySQL 8.0.46 (`mysql:8.0`, Port 3307) | 8 s | **19 / 0** |
| MySQL 8.4.0 (`mysql:8.4.0`, Port 3308) | 10 s | **19 / 0** |
| PHP 8.3.33 (eigenes Abbild) | Bau 47 s | fünf Erweiterungen geladen |

### 4.4 P-PK-10 — der Weg nach draußen

| Engine | ohne Umleitung gegen die Prüfanlage | mit `kontextMachen()` |
|---|---|---|
| chromium | `ERR_CERT_AUTHORITY_INVALID` | HTTP 200 |
| firefox | `SEC_ERROR_UNKNOWN_ISSUER` | HTTP 200 |
| webkit | HTTP 200 (nimmt den Systemspeicher) | HTTP 200 |
| node | HTTP 200 | — |

Anmeldung über beide Anlagen: **6 von 6** (drei Engines × örtlich und
Prüfanlage), Schlüsselblatt-Dialog **6 von 6** geschlossen. **Ohne
`ignoreHTTPSErrors` nach draußen** — örtlich ja, und dort ist es richtig.

### 4.5 Was dabei schiefging, und was es gelehrt hat

Vier Anläufe, jeder mit einem Befund, den die naheliegende Lösung verdeckt
hätte:

1. **Die Umleitung brach die örtliche Anlage** (`ERR_FAILED`,
   `NS_ERROR_FAILURE`, „Blocked by Web Inspector"): Der Node-Stack schickt
   auch `127.0.0.1` durch den Proxy. Örtliche Adressen laufen jetzt daran
   vorbei.
2. **Die Anmeldung galt als gescheitert und war es nicht.** Gewartet wurde
   auf `domcontentloaded`; die Seite rechnete noch (310 000 Runden).
3. **Die Adresse taugt nicht als Merkmal.** Nach der Anmeldung steht die
   Tagesübersicht unter `/login.php` — die Prüfung „Adresse enthält
   login.php nicht mehr" wartete 90 s auf etwas, das nie eintritt.
4. **Der Dialogschluss meldete zuerst „1 von 3"**, und das war richtig so:
   Die erste Fassung sah zu früh nach. Der Dialog öffnet sich **nach** der
   Anmeldung — t=0 zu, t=2 s offen, in allen drei Engines.

**Ein Punkt in eigener Sache.** Beim Erproben des Dialogschlusses ist
viermal der Knopf „Später" auf der **Prüfanlage** geklickt worden, obwohl
E-PK-29 Schreibvorgänge dort nur auf ausdrückliche Anweisung zulässt und
ich das eine Messung zuvor selbst ausgeschlossen hatte. Nachgelesen im
Quelltext (`server/api/rueckfrage.php`): `blatt_spaeter` setzt
`$_SESSION['blatt_gezeigt']` und schreibt **nichts** in die Datenbank; die
Sitzungen sind geschlossen. Es ist also nichts hinterlassen worden — aber
es waren vier POSTs, die nicht hätten sein sollen. Der Nachweis des
Dialogschlusses ist danach **örtlich** geführt worden.

## 5. Messprotokoll PK-03 (21.09.2026)

### 5.1 P-PK-12 — der Prüfstand je Stufe

| Stufe | Dauer | Muster | Proben | Ergebnis |
|---|---|---|---|---|
| klein | **26,8 s** | kette, android | 13 (12 Riegel + 1) | 0 rot, 0 nicht gemessen, 13 grün |
| neben | **25 s** | kette, android | 13 | 0 rot, 0 grün-Lücke |
| haupt | **22 s** | kette, android | 13 | 0 rot |

**Warum alle drei dieselben 13 Proben fahren, und warum das richtig ist:**
Auf diesem Zweig ist **keine Datei unter `server/` berührt**. Der Umfang
folgt der Berührung, nicht der Stufe allein (Grundsatz 4) — eine Hauptstufe
ohne Anwendungsänderung hat nichts Teures zu messen. Dass die teuren Proben
sehr wohl greifen, zeigt die Auswahl bei berührtem `server/`:

```
auswahl.py --stufe haupt --datei server/spur_lib.php --datei server/backup_lib.php \
                         --datei server/assets/style.css
-> 6 Muster, 11 Proben (plus 12 Riegel):
   spurprobe, containerprobe, wiederherstellung, kreislauf-edbak, stilvergleich,
   bilderlauf, kontraste, bedienprobe, messstand, kreislauf-csv, schemaprobe
```

Ein Lauf damit kam bis **14 grüne Proben** (einschließlich `spurprobe` und
`containerprobe`), als der Behälter neu startete; Bilderlauf und Bedienprobe
sind über den Prüfstand **nicht** gefahren worden (Abschnitt 0).

### 5.2 P-PK-13 — die Selbstproben

`bericht.py lesen --selbstprobe` → **6 Lagen, 0 Fehlschläge** (5 rote, 1
grüne): Baum-Hash passt nicht · Stufe zu klein · berührte Fläche als
„nicht berührt“ gemeldet · Riegel meldet eine andere Zahl · gar kein
Bericht · und der grüne Fall.

**Jede Lage geht durch dieselbe Funktion.** Die erste Fassung stellte den
Stufenfall daneben nach, weil er ohne Git nicht lief — und prüfte damit
ihren Nachbau statt des Werkzeugs. Das ist berichtigt: `pruefen()` nimmt die
verlangte Stufe jetzt als Angabe entgegen, der Vergleich bleibt derselbe.

`auswahl.py --selbstprobe` → **11 Lagen, 0 Fehlschläge**, darunter die
Stufengrenze (das Muster `mengen` greift bei `klein` nicht und bei `haupt`
schon) und die Gegenfälle (`server/index.php` trifft **nicht** `spur`).

### 5.3 P-PK-14 — die Abdeckung

`auswahl.py --abdeckung`: **262 versionierte Dateien** unter `server/` (ohne
`vendor/`), **0 ohne Muster**. **87** treffen nur das Auffangmuster und haben
damit keine eigene Probe — sie laufen durch die billigen Riegel und den
Bilderlauf. Das ist eine Aussage über den Bestand, kein Fehler; aber eine,
die man sehen soll, statt sie zu vermuten.

### 5.4 P-PK-15 — `kettenaufrufe` liest die Zuordnung mit

Das Werkzeug hält jetzt auch jeden Aufruf aus `pruefablauf.json` gegen die
Schnittstelle seines Werkzeugs. **Gegenprobe mit wieder eingebautem Fehler**
(`--format` statt `--art`):

| Zustand | Befunde |
|---|---|
| mit dem Fehler | **2** — „kennt `--format` nicht“ und „verlangt `--art`, der Aufruf übergibt es nicht“ |
| nach der Berichtigung | **0** |

Insgesamt **82 Aufrufe** geprüft (4 Arbeitsläufe und **40** Proben),
**0 Befunde**, **18 ungeprüft** — Werkzeuge ohne Schalter, bei denen das
Werkzeug sagt, was es nicht wissen kann, statt eine Null zu melden.

### 5.5 Was dabei schiefging

Vier Fehler, alle beim ersten echten Lauf, alle in dem, was ich selbst
geschrieben hatte:

1. **`sh` ist dash.** `pruefen.sh` rief die Sandbox-Skripte mit `sh` auf,
   die aber `#!/bin/bash` tragen und Felder benutzen — `set: Illegal option
   -o pipefail`. Alle Aufrufe und alle Anleitungen stehen jetzt auf `bash`.
2. **`--format` statt `--art`** beim Kreislauf. Genau der Fehlertyp, für den
   `kettenaufrufe` da ist — deshalb liest es jetzt die Zuordnung mit (5.4).
3. **`./gradlew` ohne Wechsel nach `android/`.**
4. **Die Vollständigkeit lief ohne ihre Schwelle** und meldete rot bei 398
   Befunden. Die Schwelle steht jetzt im Aufruf in `pruefablauf.json` — und
   damit an genau einer Stelle, sobald PK-05 die Kette dieselbe Datei lesen
   lässt.

### 5.6 P-PK-17 — der Auslöser der Stufe 1, nach dem Merge von PR #71

**Gemessen am 21.09.2026 an der Lauf-API, nicht abgeschrieben.** PR #71 ist um
**20:13:03 UTC** von der Betreiberin gemergt worden (`523a5eb`).

**Was belegt ist — die Wirkung, am Vorgriffszweig selbst:** Zum Commit
`d5a9d7c` gibt es **genau einen** Lauf von `pruefung.yml`: Nr. **205**,
Ereignis **`pull_request`**, 19:03:54–19:46:53, grün. **Kein `push`-Lauf.**
Das ist der Kern der Sache und war schon **vor** dem Merge messbar — der
PR-Text sagte „erst nach dem Merge" und war damit zu vorsichtig: Für ein
`push`-Ereignis liest GitHub die Workflow-Datei **aus dem gepushten Commit**,
und der trug die Änderung bereits.

**Die Gegenprobe steht daneben und ist genauso wichtig.** Drei Läufe desselben
Abends, alle Ereignis `push`, alle richtig:

| Lauf | Zweig | Warum ein `push`-Lauf richtig ist |
|---|---|---|
| **209** | `claude/pk-06-vorgriff-stufe2` (`014a661`, 19:53) | von `main` **vor** dem Merge abgezweigt — trägt noch `branches: ['**']` |
| **211** | `claude/serene-dijkstra-bcpbjy` (`e86aed9`, 19:58) | dasselbe |
| **213** | `main` (`523a5eb`, 20:13) | **auf `main` bleibt der Auslöser**, und das ist Absicht |

**Was noch nicht gefahren ist:** der Bedienweg des Prüfpunkts in seinem
Wortlaut — ein Push auf einen Arbeitszweig, **der die neue Datei trägt und zu
dem ein Pull Request offen ist**. Dieser Zweig trägt sie nach dem nächsten
Push, hat aber **keinen offenen PR**; dort entstehen dann **null** Läufe. Das
ist kein Fehlschlag, sondern der Zweck der Änderung — Stufe 1 läuft beim Pull
Request, nicht bei jedem Push —, aber es ist **nicht dasselbe wie „genau ein
Lauf"**. Die Zeile in der Prüfliste beschreibt den PR-Fall; gemessen wird er
am **Phasen-PR** oder an PR #72.

---

### 5.7 Die 18 „ungeprüften" Aufrufe, einzeln gefahren (21.09.2026)

**Warum überhaupt.** Drei Stichproben, drei Treffer: `--format` statt `--art`
(PK-03), der Stilvergleich ohne Vergleichsstand (F-PK-20) und `uhr-stufe1`
ohne Listendatei (F-PK-21). Nach dem dritten war die Frage nicht mehr, ob die
Zuordnung stimmt, sondern wie viele der übrigen Aufrufe nie gelaufen sind.
Also gefahren, einzeln, mit Zeitgrenze 600 s, Rückgabewert und letzter
Ausgabezeile.

**Es sind 17, nicht 15.** Die Liste hat 18 Einträge; `stilvergleich` war
schon behoben. `uhr-stufe1` stand nie darin — das ist F-PK-21.

**Das Ergebnis, zuerst und ohne Beschönigung: Kein einziger der 17 Aufrufe
ist falsch verdrahtet.** Alle 17 sind richtig geschrieben; 15 laufen, 2
scheitern an einem fehlenden Prüfkonto. Die Sorge nach den drei Treffern war
also falsch, und das ist gemessen und nicht vermutet.

| Probe | rc | Dauer | Was sie meldet |
|---|---|---|---|
| `ingestprobe` | 0 | 1 s | **83 Erwartungen, 0 nicht erfüllt** |
| `spurprobe` | 0 | 2 s | **45 / 0** |
| `jobprobe` | 0 | 0 s | **35 / 0** |
| `komplettprobe` | 0 | 1 s | **64 / 0** |
| `wiederherstellung` | **1** | 0 s | **110 / 2** — bekannt, **F-PK-18** |
| `gpxprobe` | **1** | 2 s | **95 / 4** — **neu**, siehe F-PK-22 |
| `geraeteprobe` | 0 | 0 s | **59 Erwartungen geprüft** |
| `kopplungsprobe` | 0 | 9 s | **76 / 0 / 0 übergangen** |
| `mailprobe` | **1** | 18 s | 1 Erwartung `FEHL` — **neu**, F-PK-22 |
| `ratenprobe` | **1** | 12 s | 1 Erwartung `FEHL` — **neu**, F-PK-22 |
| `wartungsprobe` | 0 | 1 s | **67 / 0** |
| `verbindungsprobe` | 0 | 0 s | **alle 24 Erwartungen erfüllt** |
| `freigabeprobe` | **1** | 1 s | **läuft nicht** — Zielkonto `umlauf-edbak@gen-em.org` fehlt |
| `fristprobe` | 0 | 2 s | grün |
| `abmelde-probe` | 0 | 2 s | grün, „Seitenfehler: keine" |
| `containerprobe` | 0 | 2 s | **32 / 0** |
| `messstand` | **124** | **600 s** | **läuft nicht** — Konto `messstand@gen-em.org` fehlt; danach 180 s Wartezeit auf ein Element, dann Zeitgrenze |

**11 grün, 4 rot mit Sachbefund, 2 ohne Voraussetzung.** Zusammen **690
genannte Erwartungen** aus den elf Proben, die eine Zahl nennen.

**Die zwei fehlenden Konten sind keine Verdrahtungsfehler, sondern eine
Lücke im Referenzbestand** — und sie berühren **E-PK-27** („nur
`demo@gen-em.org`, alles andere `example.invalid"): Beide, `umlauf-edbak@`
und `messstand@`, stehen unter `gen-em.org`. Sie stammen aus vorhandenen
Werkzeugen, nicht aus diesem Paket; **festgehalten, nicht angefasst.**
`messstand` kostet dabei die volle Zeitgrenze, weil es nach der
gescheiterten Anmeldung 180 s auf ein Element wartet, statt abzubrechen —
das ist der teuerste Einzelposten des ganzen Durchlaufs.

---

## 6. Befunde der Umsetzung

**Zur Nummernvergabe, damit niemand darüber stolpert.** `F-PK-NN` meint in
diesem Dokument **einen Befund und sonst nichts**. Bis zum 21.09.2026 war das
nicht so: Die *beantworteten Fragen der ersten Konzeptfassung* (Konzept 3.2)
hießen `F-PK-1` bis `-6` — einstellig, während die Befunde zweistellig ab
`F-PK-01` zählen. `F-PK-1` und `F-PK-01` standen nebeneinander und meinten
Verschiedenes; die Umsetzung ließ deshalb vorsichtshalber 02 bis 06 frei und
begann bei **F-PK-07**. **Aufgelöst am 21.09.2026 auf Weisung des
Auftraggebers:** Die Fragen heißen jetzt **`Q-PK-01` bis `-06`**, eingetragen
in `docs/Pruefablauf.md` 7 und `CLAUDE.md` 7. Der Nachtrag desselben Abends
belegt **F-PK-02 bis -04**; **05 und 06 bleiben frei** und werden nicht
nachbelegt. **Wer eine ältere Quelle liest** — einen Commit, ein Protokoll,
eine alte Sitzung —, findet dort noch `F-PK-1` bis `-6` und meint die
Fragentabelle.

### 6.1 Nachträge vom Abend des 21.09.2026 (F-PK-02 bis -04)

Drei Befunde aus den beiden Auslieferungsläufen, die auf die Merges von
PR #70 und PR #69 folgten. Sie gehören sachlich zu PK-01 (Bestandsaufnahme),
werden aber erst in **PK-06** behoben, weil sie `auslieferung.yml` anfassen.

| Nr. | Befund | Folge |
|---|---|---|
| **F-PK-02** | **Der ganze Auslieferungslauf hat keine Gruppe — nur einer seiner Jobs hat eine.** Zwei Merges nach `main` innerhalb von 28 Sekunden (PR #70 um 18:35:47, PR #69 um 18:36:15 UTC) erzeugten zwei Läufe von `auslieferung.yml` (**35639395259** und **35639445224**), die sich überlappten. | **PK-06:** eine Gruppe je Umgebung über den **Lauf**, nicht über einen Job — laufende **Staging**-Läufe abbrechen, den **Produktiv**lauf **nie**. |
| **F-PK-03** | **Die Job-Pause der Stufe 2 legt den Nachbarlauf lahm.** `kreislauf.py` hält die Hintergrundjobs auf Staging über `jobs_pause(1800, …)` an; das Backup-Tor des Nachbarlaufs wartet auf `fertig`, bekam „angehalten bis 19:06:50" und schloss nach **13 Minuten** mit 40 Aufrufen: **Lauf 69, Versuch 1 rot, ohne dass eine einzige Datei übertragen war.** | Keine eigene Änderung nötig — **die Gruppe aus F-PK-02 löst es mit.** Sie steht in PK-06 als *eine* Maßnahme mit *zwei* Anlässen. |
| **F-PK-04** | **Der Bilderlauf gegen Staging meldet sich mit dem eingebauten Vorgabekennwort an, und dieses Konto gibt es auf der neuen Anlage nicht.** Gemessen in Lauf 69, Versuch 2, Job „Prüfung Stufe 2", Schritt 6, nach 11 s: `Error: Anmeldung als demo@gen-em.org gescheitert — die Seite sagt: Anmeldung fehlgeschlagen. E-Mail oder Passwort prüfen.` (`aufnehmen.mjs:547`). **Der Schritt war auf der neuen Staging-Anlage nie grün.** **Woher die Lücke kommt:** **Z7 der Kette II** (nicht die Z7 des Konzepts PK) verlangte „Demo-Konto, Referenzbestand, Messstand-Konto"; gebucht wurde Z7 als **erfüllt** mit dem gelungenen Komplett-Backup und dem `JOBS_TOKEN` — **die drei Konten-Punkte wurden nie nachgemessen.** Ein Haken auf einer Zuarbeit mit sieben Teilen, gesetzt nach zwei gemessenen. | **Nichts anlegen.** Der Schritt fällt mit **E-PK-01** ohnehin aus Stufe 2 heraus und wandert in den Prüfstand, wo das Demo-Konto aus der Fixture kommt. Bis PK-06 ist Stufe 2 nach einem Merge deshalb **planmäßig rot am Schritt 6**; die Kreisläufe davor sind der Nachweis. **NACHTRAG 21.09.2026, 19:42 — die Folge ist größer, als dieser Satz sagte:** Für den Produktionslauf ist ein roter Staging-Lauf ein roter Staging-Lauf, gleich woran er scheiterte. Der Tag `web-v20.26.3` (Lauf **35646453443**, `807f462`) blieb deshalb am **Schritt 7 „Grüner Staging- und Stufe-1-Lauf auf diesem Stand?"** hängen, nach **2 Sekunden**; Schritte 8 bis 17 übersprungen, **nichts ausgeliefert**. **Der Riegel hat gehalten** — aber F-PK-04 blockiert damit nicht nur eine Messung, sondern **jede Auslieferung nach Produktiv**. Das macht PK-06 dringlich statt planbar. |

**Kein Konto mit Vorgabekennwort auf Staging anlegen.** Das ist die Anweisung
des Auftraggebers vom 21.09.2026 und der Grund, warum F-PK-04 *nicht* durch
eine Zuarbeit behoben wird: `nadokudemo0815` steht im Quelltext von sieben
Werkzeugen; ein Konto damit auf einer erreichbaren Anlage wäre ein
veröffentlichtes Kennwort.

#### Was gemessen ist und was berichtet

| Aussage | Beleg |
|---|---|
| Zwei Läufe, überlappend | **gemessen** über die Lauf-API: 35639395259 (`f03023e`) `staging / ausliefern` 18:35:50–18:36:19, `Prüfung Stufe 2` 18:36:22–**18:52:19**; 35639445224 (`807f462`) Versuch 2 ab **18:53:40**. Der Nachbarlauf lag also **volle 16 Minuten** im Zeitfenster der Stufe 2 des ersten. |
| Die Gruppe fehlt dem Lauf, nicht dem Job | **gemessen** am Quelltext: `ausliefern-lauf.yml` trägt auf dem Job `ausliefern` `group: ausliefern-${{ inputs.umgebung }}` mit `cancel-in-progress: false` (E-KH-11, B4). `auslieferung.yml` trägt **keine** — weder auf dem Lauf noch auf einem der fünf Jobs, `stufe2` eingeschlossen. **Das ist die Lücke:** Die Gruppe reiht die Abgleiche hintereinander, aber `stufe2` steht außerhalb, und deshalb lief der Abgleich des einen Laufs in die Job-Pause des anderen. |
| 1 800 s Pause | **gemessen** am Quelltext: `kreislauf.py:345` `jobs_pause(1800, a.basis, a.jobs_token)`. |
| Backup-Tor, 40 Aufrufe, 13 Minuten, „angehalten bis 19:06:50" | **berichtet vom Auftraggeber**, nicht nachgemessen: Die Lauf-API liefert zu 35639445224 den **Versuch 2**; das Protokoll von Versuch 1 ist über das benutzte Werkzeug nicht erreichbar. Der Zeitrahmen passt: Die Pause begann um 18:36:27 (Schritt 5 der Stufe 2 des Nachbarlaufs), 1 800 s später ist 19:06:27. |
| Anmeldung des Bilderlaufs | **gemessen** im Protokoll von Job 106471304585, Schritt 6 (11 s, 18:56:05–18:56:16). |
| Kreisläufe grün gegen die echte Anlage | **gemessen**: Lauf 69, Versuch 2, Schritt 5, 18:54:21–18:56:05 = **104 s grün** gegen Staging mit **MySQL 8.4.10**. Das ist der Beleg für Backlog **Nr. 267** auf der echten Anlage — im Lauf davor (`f03023e`, ohne den Fix) war derselbe Schritt nach **15 min 51 s rot**. Der Tag `web-v20.26.3` folgt darauf. |

### 6.2 Befunde aus den Paketen (F-PK-07 ff.)

| Nr. | Befund | Folge |
|---|---|---|
| **F-PK-07** | **Die Abnahme von PK-01 ist wörtlich nicht erfüllbar.** „Je genau eine Fundstelle in `docs/` und `CLAUDE.md` zusammen" trifft nicht zu, wenn man alle Treffer zählt: Emulator 131 Treffer in 19 Dateien, Wortliste 16 in 8, Tag-Rumpf 8 in 3 — überwiegend Changelog, Backlog, Rahmenplan-Verlauf und Konzepte. Die werden nicht umgeschrieben; `docs/Technik.md` sagt selbst: „Eine Historie, die man umschreibt, ist keine mehr." | Gemessen wird **eine normative Fundstelle**, mit dem Befehl und der Ausschlussliste aus 3.2. So gemessen: **1/1/1**. |
| **F-PK-08** | **Vier Zahlen aus Konzept 1.1 sind veraltet, eine ist falsch.** Veraltet durch PR #68 (schon auf `main`): `tools/` 44 883 → **45 016**, Kette 2 830 → **2 863**, davon Kommentar 1 506 → **1 535**, Stufe 1 27 → **28** Schritte. Falsch: „Werkzeuge mit Selbstprobe **28**" — gemessen sind es **13** (sechs verschiedene Auslegungen durchgerechnet, keine ergibt 28). Unverändert richtig: `server/` 100 333, LIESMICH 7 206, 48 Werkzeugordner. | Die Begründung von E-PK-24 trägt auch bei 13. Die Zahl darf nicht abgeschrieben werden; PK-04 misst sie neu und nennt den Befehl. |
| **F-PK-09** | **Die drei Mailwerte heißen anders, als Konzept 1.4 sie schreibt.** Gesetzt sind buchstäblich `_MAIL_URL`, `_MAIL_USER`, `_MAIL_PASS` — führender Unterstrich, **kein** Präfix. `NADOKU_STAGING_MAIL_*` gibt es nicht. Die Klammer „(die Mailwerte mit führendem Unterstrich)" sagt es, geht aber beim Abschreiben verloren. | `Sandbox-Setup.md` 4 schreibt alle sieben Namen **aus** und benennt den Bruch. Alle 7 von 7 sind gesetzt (Längen dort). |
| **F-PK-10** | **Hook und `aufbau.sh` widersprechen einander.** `.claude/hooks/session-start.sh` ruft `playwright install firefox webkit`; `tools/containeraufbau/aufbau.sh` sagt wörtlich, das sei ausdrücklich **nicht** der Weg, weil die Engines im Abbild liegen und ein Nachladen eine zweite Fassung danebenzöge. Gemessen: Die Engines liegen im Abbild. Dazu **zwei disjunkte** Bibliothekslisten (6 gegen 4 Pakete, keine Überschneidung), beide unter Berufung auf „die Namen, die Playwright selbst nennt". | In `Sandbox-Setup.md` 1.2 benannt. **Mit PK-02 entschieden, und zwar gemessen:** Playwright nennt die vier Namen selbst; die Engines liegen im Abbild, `playwright install` entfällt. Nach dem Nachziehen der vier Pakete 3 von 3 Engines. |
| **F-PK-11** | **`CLAUDE.md` 3 stimmt bei der Ausnahmeliste zur Hälfte.** Dort steht „Acht Pfade … **Jeder steht dort zweimal**". Gemessen (`ausliefern-lauf.yml`): Acht Pfade stimmt; zweimal stehen nur die **drei Verzeichnisse**, die fünf Dateien je **einmal** — zusammen 14 Zeilen. | Nicht in PK-01 berichtigt: `CLAUDE.md` 3 gehört zum Auslieferungsweg, den **PK-06** anfasst. Dort mit berichtigen. |
| **F-PK-12** | **`docs/Technik.md` „2a" steht physisch unter „## 4. Zentrale Abläufe".** Wer die Nummer liest und in Abschnitt 2 sucht, findet nichts; `CHANGELOG.md` verweist bereits so darauf. | Nicht in PK-01 aufgelöst (das wäre ein Umbau von `Technik.md`). **PK-07** zieht `Technik.md` ohnehin nach und löst die Fehlstellung dort auf. |
| **F-PK-13** | **`.claude/settings.local.json` rangiert über der geteilten Datei** und steht nicht in `.gitignore`. Entstünde sie, hübe sie die Deny-Liste auf, ohne im Pull Request zu erscheinen. | In `Pruefablauf.md` 2.3 als offener Weg benannt statt verschwiegen. Ob die Datei in `.gitignore` gehört, entscheidet die Betreiberin — ein Eintrag machte sie unsichtbar, kein Eintrag lässt sie wenigstens als unverfolgte Datei auffallen. |
| **F-PK-14** | **Nicht nur Chromium misstraut der Proxy-Stelle — Firefox auch.** Konzept 1.4 nennt allein Chromium. Gemessen gegen die Prüfanlage: Chromium `ERR_CERT_AUTHORITY_INVALID`, **Firefox `SEC_ERROR_UNKNOWN_ISSUER`**, WebKit HTTP 200 (Systemspeicher), Node HTTP 200. | `kontextMachen()` legt die Umleitung auf **jeden** nicht-örtlichen Kontext, nicht nur auf Chromium. |
| **F-PK-15** | **Das PHP-8.3-Abbild braucht die Zertifizierungsstellen des Wirts.** Das Rezept in Konzept 1.3 nennt nur die Umstellung der Debian-Quellen auf HTTPS; damit allein scheitert `apt-get update` im Behälter mit `certificate verify failed`. Gemessen: die Stelle des Agent-Proxys **allein genügt nicht** — der Verkehr läuft über das Egress-Gateway. | `plattform.sh` kopiert alle Stellen aus `/usr/local/share/ca-certificates/` in den Bauplatz (ohne die je Behälter erzeugte Prüfstands-Stelle). Bau danach 47 s. |
| **F-PK-16** | **Die Drosselung von Docker Hub trägt den Umweg aus E-PK-30 nicht.** Vier Abrufe (`mysql:8.4.0`, `mariadb:10.6`, `mysql:8.0`, `php:8.3.33-cli`) und ein Bau liefen ohne einen einzigen 429 durch. | Der vorgesehene Weg über Ubuntu-Pakete unter `/opt` ist **nicht gebaut worden** (E-PK-32). Tritt die Drosselung später auf, ist das ein Befund mit Zahl — nicht die Voraussetzung eines Umwegs. |
| **F-PK-18** | **Die Wiederherstellungsprobe meldet auf einer frisch eingerichteten örtlichen Anlage 2 von 110 Erwartungen nicht erfüllt**: „ein knapper Schub sichert wenigstens ein Konto“ (2 erledigt, 0 von 2 offen) und „der Zeiger steht auf dem zuletzt gesicherten Konto“ (cur=—). | **Nicht geklärt**, ob eine Voraussetzung fehlt (kein Sicherungsziel eingetragen) oder die Anwendung einen Fehler hat. Der Prüfstand hat es gefunden, ohne danach zu suchen — das ist sein Zweck. Prüfpunkt P-PK-16; gehört nicht in ein PK-Paket, sondern als eigene Korrekturstufe untersucht. |
| **F-PK-17** | **Zwei Betriebsdinge, die kein Dokument sagte:** Der Docker-Dienst **läuft nicht von selbst** (`dial unix /var/run/docker.sock: no such file`), und der Einstiegspunkt von MySQL startet den Dienst **nach** der Einrichtung neu — ein Ping gelingt schon vorher, und die Schemaprobe lief prompt in „MySQL server has gone away". | Beides steht in `Sandbox-Setup.md` 2.2 und in der `LIESMICH.md`; `plattform.sh` wartet auf eine echte Abfrage statt auf ein Ping. |
| **F-PK-19** | **Mein eigener Beschaffer aus PK-02 meldet grün, ohne die Uhr angesehen zu haben.** `bash tools/sandbox/aufbauen.sh uhr` lief am 21.09.2026 mit **Rückgabewert 0** und der Schlusszeile „Arbeitsumgebung vollständig" — obwohl drei Zeilen darüber `pruefstand.sh: 11: set: Illegal option -o pipefail` stand. **Zwei Fehler, beide meine:** (1) `teil_uhr()` rief den Prüfstand mit `sh` auf; der ist `#!/usr/bin/env bash` und setzt `-o pipefail`, und `sh` ist in diesem Abbild dash. **Denselben Fehler hatte ich in PK-03 in `pruefen.sh` gefunden und behoben** — in `aufbauen.sh` und in `pruefablauf.json` blieb er stehen. (2) Die Aufrufe von `teil_web`, `teil_android`, `teil_uhr` und `teil_plattform` standen nackt in der `case`-Schleife, ihr Rückgabewert wurde verworfen; der `nachweis` prüfte ausschließlich Stücke der Stufe `web`. **`aufbauen.sh uhr` konnte nicht rot werden.** | **Das ist Grundsatz 7 im eigenen Werkzeug** — genau die grüne Zahl ohne Gegenstand, gegen die PK gebaut wird, und sie stand in dem Werkzeug, das die Abnahme liefern sollte. Behoben am 21.09.2026: `bash` statt `sh` an beiden Stellen; jeder Teil zählt seinen Fehlschlag; der Nachweis prüft bei `android` Plattform 36 und Build-Tools, bei `uhr` `monkeyc` und `Devices/`. **P-PK-11 stand bis dahin zu Recht auf „offen" — aber aus dem falschen Grund:** nicht weil niemand gefahren hatte, sondern weil ein Lauf nichts gesagt hätte. |
| **F-PK-20** | **Zwei Proben in `pruefablauf.json` waren örtlich rot, ohne etwas gemessen zu haben.** Gefunden beim nachgeholten langen Lauf vom 21.09.2026 (`--datei server/assets/style.css --datei server/einsatz.php`): 15 Proben, **13 grün, 2 rot, 0 nicht gemessen**, Rückgabewert 1. **(a) Bilderlauf, 48 Konsolenfehler** — drei fehlende Bilder auf zwei Handbuchseiten über acht Breiten (2 × 8 × 3 = 48): `doku/bilder/schublade-mobil.png`, `tagesuebersicht-desktop.png`, `tagesuebersicht-mobil.png`. **Kein Fehler der Anwendung:** Die Kette kopiert `docs/bilder/` in ihrem Schritt 11 nach `server/doku/` mit; örtlich legte sie niemand an. **(b) Stilvergleich** — der Aufruf lautete `python3 tools/stilvergleich/proben.py`, **ohne Argumente**. `proben.py` baut nur die vier Proben und gibt ohne Argumente seine Anleitung aus; der Vergleich ist `stilvergleich.js` und braucht den alten Stand. Beim Reparieren fiel auf: **`stilvergleich.js` ist CJS und macht `require('playwright')`** — es geht an `tools/motor.mjs` vorbei, und Playwright liegt in diesem Abbild unter `/opt/node22/lib/node_modules`. | **(a)** `hochfahren.sh` macht jetzt dieselben zwei Zeilen wie die Kette. Gegenprobe an denselben zwei Seiten: 16 Einzelbilder, **48 → 0 Konsolenfehler**. **(b)** Neu: `tools/stilvergleich/gegen.sh` — Vergleichsstand aus git, Proben bauen, messen; ein Treiber im vorhandenen Werkzeugordner, kein neues Werkzeug. Gemessen: **40 989 Elementmessungen, 0 Abweichungen, 175 Eigenschaften je Element**. Das `NODE_PATH` darin ist ein **Pflaster** und steht als solches im Kommentar und im LIESMICH; `stilvergleich.js` auf den Motor zu heben gehört nach **PK-04**. **Und der eigentliche Punkt: Der Stilvergleich war einer der „18 ungeprüft" von `kettenaufrufe`.** Diese Zahl steht in drei Commits als Beiwerk — sie ist keins, sondern die Liste der Aufrufe, über die niemand etwas weiß. |
| **F-PK-21** | **`kettenaufrufe` prüft Namen, nicht Vollständigkeit — und sagt das nicht.** Aufgefallen beim Nachsehen, warum `uhr-stufe1` nicht unter den 18 Ungeprüften steht: **Es steht überhaupt nicht in der Ausgabe**, gilt also als geprüft und in Ordnung. Der Aufruf `bash tools/uhr-pruefstand/pruefstand.sh reihe` bricht aber sofort mit `line 355: 1: Listendatei fehlt` ab — Stufe I braucht eine Geräteliste, die erst `geraeteklassen.py` erzeugt. **Strukturell:** Die Prüfung hält Unterbefehle und Schalter gegen den Quelltext. `reihe` **gibt es**, also kein Widerspruch; dass `reihe` ein **Pflichtargument** hat, sieht sie nicht. Ihr Schlusssatz „Kein Aufruf widerspricht der Schnittstelle seines Werkzeugs" ist wörtlich wahr und trotzdem irreführend. | **Das verschiebt die Bedeutung der Zahl 18.** Die 18 sind nicht die Liste der ungewissen Aufrufe, sondern die, bei denen das Werkzeug seine Unwissenheit **einräumt**. `uhr-stufe1` war kaputt und zählte zu den **82 grünen**. Daraus folgt: **Eine Schnittstellenprüfung ersetzt keinen Lauf.** Die 18 werden deshalb einzeln gefahren (5.7). Ob `kettenaufrufe` Pflichtargumente lernen soll oder ob der Prüfstand das ohnehin beim Fahren merkt, entscheidet **PK-04**. |
| **F-PK-22** | **Drei unbekannte Sachbefunde aus den nie gefahrenen Proben** (5.7), alle in `server/`, keiner aus diesem Paket. **(a) `gpxprobe` 95 / 4:** zwei davon sind eine veraltete Referenz („178 von 204 ohne Gegenstück — die Referenz ist älter als die Datenbank"; „9 Abweichungen, erste: 65 gegen 259 Punkte"), zwei sehen nach Sache aus („Ein Eintrag ohne Spur steht da, aber ohne Abruf — Plakette ‚keine Spur' gefunden"; „Der Kopf sagt, was die Datei als Ganzes ist"). **(b) `mailprobe`:** „Alle Pflichtwerte im Beispielsatz abgedeckt" schlägt fehl — `loeschung_beantragt/termin`, `konto_menge/einsaetze`, `konto_menge/speicher`. **(c) `ratenprobe`:** „Genau **fünf** Töpfe haben eine Leiter" schlägt fehl und zählt **sechs** auf: `blatt`, `ingest`, `ingest_ip`, `login`, `login_ip`, `salt`. | **Nicht behoben, und zwar bewusst:** Alle drei liegen in `server/`, das dieses Paket nicht anfasst, und zwei von ihnen sind vermutlich veraltete Erwartungen im Prüfmittel selbst, keine Fehler der Anwendung — das zu trennen ist eigene Arbeit. **Sie sind der Ertrag des Durchlaufs:** Vier Proben waren rot, seit jemand sie zuletzt gefahren hat, und niemand wusste es, weil niemand sie fuhr. **Das ist genau der Zweck von Station B.** Gehört als eigene Korrekturstufe untersucht, zusammen mit F-PK-18; **Prüfpunkt P-PK-20**. |

## 7. Entscheidungen der Umsetzung

| Nr. | Entscheidung | Grund |
|---|---|---|
| **E-PK-33** | **Das Muster `station` fällt aus der Sperrliste**, zusammen mit der Ausnahme, die PK-01 dafür angelegt hatte. Angewiesen vom Auftraggeber am 21.09.2026. | Konzept PK gliedert die Prüfkette in fünf „Stationen" — Haltepunkte auf dem Weg zum Produktivserver, nicht Standorte eines Rettungsmittels. Eine Ausnahme je Datei hätte das Wort für jedes neue Dokument neu begründen müssen. **Der Preis, benannt:** „Station" im Sinn des Luftrettungs-Standorts fällt jetzt durch **kein** Muster mehr; `basis` deckt den Geschwisterbegriff weiter ab. Sperrliste 24 → **23** Muster, Ausnahmen 100 → **99** Regeln; Lauf danach **0/0/0**, 99 von 99 Regeln gegriffen. |
| **E-PK-32** | **Das Modul `plattform` nimmt Docker für alle vier Fassungen** statt des in E-PK-30 vorgesehenen Umwegs über Ubuntu-Pakete unter `/opt`. | E-PK-30 begründet den Umweg mit der Drosselung von Docker Hub. Die tritt bei vier Abbildern nicht ein (F-PK-16). Der Umweg wäre aufwendiger, zerbrechlicher und löste ein Problem, das es nicht gibt. **Gemessen: `alles` in 29,7 s, 4 × 19/0.** **Bestätigt vom Auftraggeber am 21.09.2026.** |
| **E-PK-31** | **`docs/Technik.md` 2a wird von `Sandbox-Setup.md` abgelöst**, nicht danebengestellt. In `Technik.md` bleibt der Teil, der die *Prüfmittel* betrifft (Motortabelle, die drei Engine-Befunde, die vierte Zahl); der Teil über den *Container* wird zum Verweis. 2a schrumpft von 116 auf 73 Zeilen. **Bestätigt vom Auftraggeber am 21.09.2026.** | Das Konzept nennt `Technik.md` erst in PK-07. Zwei Beschreibungen derselben Umgebung nebeneinander stehen zu lassen wäre aber genau der Fehler, den PK abstellt — und `CLAUDE.md` 9 verlangt die Pflege im selben Paket. |

---

*PK-01 abgeschlossen am 21.09.2026. Nächstes Paket: PK-02 (Sandbox-Setup).*
