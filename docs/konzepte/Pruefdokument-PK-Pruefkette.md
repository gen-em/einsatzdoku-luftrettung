# Prüfdokument PK — Prüfkette

Gehört zu `Konzept-PK-Pruefkette.md`. Nach `CLAUDE.md` 7: Was wurde
maschinell geprüft (Mittel und Zahl), was im Browser, was nicht und warum,
und eine abhakbare Prüfliste. Angelegt mit PK-M1; fortgeschrieben mit PK-01.

## 0. Was nicht geprüft werden konnte

**Stand nach PK-01.** PK-01 ändert keine Zeile Anwendungscode und keine
Zeile der Kette; es schreibt Dokumente, eine Deny-Liste und zwei
Werkzeugzeilen. Deshalb ist die Liste kurz — aber sie ist nicht leer:

| Was | Warum nicht | Wann dann |
|---|---|---|
| **Die Kette auf einem echten Lauf** | PK-01 ändert `.github/workflows/` nicht. Dass `pruefung.yml` und `auslieferung.yml` nach den Änderungen unverändert grün laufen, belegt erst der nächste Push — und der gehört zum Phasen-PR, nicht zum Paket. | beim Phasen-PR |
| **Die Wirksamkeit der Deny-Liste für *andere* Instanzen** | Gemessen ist sie in **dieser** Sitzung. Solange PK-01 nicht gemergt ist, liegt die Datei nur auf dem Arbeitszweig; eine Instanz, die `main` auscheckt, hat den Riegel nicht. | nach dem Merge des Phasen-PR |
| **Ob die Deny-Liste einen *durchgeführten* Merge verhindert** | Nicht messbar, und zwar grundsätzlich: Die Regel nimmt das Werkzeug ganz aus dem Zusammenhang, es gibt also keinen abgewiesenen Aufruf. Gemessen wird die Abwesenheit mit Gegenprobe (3). | — (Messvorschrift steht in 3.1) |
| **Die Browserprüfung** | PK-01 fasst keine Oberfläche an — keine Datei unter `server/`, kein Stylesheet, kein Markup. Es gibt nichts zu sehen. | — |
| **Die Ausbaustufen aus `Sandbox-Setup.md` 2** | Sie sind in PK-01 **beschrieben**, nicht gebaut. `tools/sandbox/aufbauen.sh` gibt es noch nicht; die Zahlen in 2.2 stammen aus einem Nachbau von Hand am 21.09.2026 und nicht aus einem Lauf des Werkzeugs. | PK-02 |
| **Die Zahlen der Kette in `Pruefablauf.md` 2.3/2.4** | Schrittzahlen und Jobnamen sind am Quelltext gezählt, nicht an einem Lauf gemessen. Die Dauerangaben (49 s Staging, 16 min Stufe 2) sind aus dem Konzept übernommen und hier **nicht** nachgemessen. | PK-05, PK-06 |

## 1. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-PK-01 | Zweigschutz und Merge-Recht auf `main` (PK-M1) | (a) Claude-Instanz öffnet PR und versucht den Merge über die API; (b) dieselbe Instanz pusht direkt auf `main`; (c) die Betreiberin mergt den PR | (a) abgewiesen, (b) abgewiesen, (c) geht | ein Merge oder Push durch die Instanz kommt durch | **gemessen 21.09.2026 — (a) kam durch, siehe F-PK-01**; (b) abgewiesen; (c) mit PR #70 |
| P-PK-02 | edbak-500 (`097D7622`) lokal reproduzieren (PK-03) | Kreislauf edbak gegen die lokale Installation unter PHP 8.3.33 und 8.4 | Export fällt lokal mit demselben Fehler, oder er fällt nicht (dann Plattform) | — | **erledigt 21.09.2026**: gegen MariaDB grün, gegen MySQL 8.4.0 rot mit `1064 near 'manual'`; behoben in Web 20.26.3 (PR #69, Nr. 267) |
| P-PK-03 | Merge-Werkzeug der Claude-Instanzen gesperrt (Folge aus F-PK-01) | `.claude/settings.json` trägt `mcp__github__merge_pull_request` und `mcp__github__enable_pr_auto_merge` in `permissions.deny`; eine Instanz sucht **beide** Werkzeuge **und drei andere** desselben Anschlusses | die beiden gesperrten sind **nicht mehr auffindbar**, die drei anderen laden unverändert | eines der beiden ist weiter da (Riegel wirkt nicht) — **oder alle fünf sind weg** (dann ist der Anschluss ausgefallen und es ist gar nicht gemessen) | **erledigt 21.09.2026 — 2 von 2 gesperrt, 3 von 3 Gegenproben vorhanden** (3.1) |
| P-PK-04 | `CLAUDE.md` 6 unter 60 Zeilen, die drei Regeln je an einer Stelle (PK-01) | die vier Befehle aus 3.2 | 6 unter 60 Zeilen; je Regel 1 normative Fundstelle in `docs/` und `CLAUDE.md` | eine Regel steht zweimal, oder sie steht nirgends mehr | **erledigt 21.09.2026 — 58 Zeilen; 1/1/1** (3.2) |
| P-PK-05 | Die Prüfmittel bleiben auf dem heutigen Stand (PK-01 bis PK-03) | `wortliste.py`, `vollstaendigkeit/pruefen.py --hoechstens 398`, `kettenaufrufe/pruefen.py` | Wortliste 0/0/0, Vollständigkeit auf der Schwelle, Kettenaufrufe 0 Befunde | eine Zahl wandert, ohne dass ein Paket sie bewusst verschoben hat | **erledigt 21.09.2026** (3.3) |
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

## 4. Befunde der Umsetzung (F-PK-07 ff.)

| Nr. | Befund | Folge |
|---|---|---|
| **F-PK-07** | **Die Abnahme von PK-01 ist wörtlich nicht erfüllbar.** „Je genau eine Fundstelle in `docs/` und `CLAUDE.md` zusammen" trifft nicht zu, wenn man alle Treffer zählt: Emulator 131 Treffer in 19 Dateien, Wortliste 16 in 8, Tag-Rumpf 8 in 3 — überwiegend Changelog, Backlog, Rahmenplan-Verlauf und Konzepte. Die werden nicht umgeschrieben; `docs/Technik.md` sagt selbst: „Eine Historie, die man umschreibt, ist keine mehr." | Gemessen wird **eine normative Fundstelle**, mit dem Befehl und der Ausschlussliste aus 3.2. So gemessen: **1/1/1**. |
| **F-PK-08** | **Vier Zahlen aus Konzept 1.1 sind veraltet, eine ist falsch.** Veraltet durch PR #68 (schon auf `main`): `tools/` 44 883 → **45 016**, Kette 2 830 → **2 863**, davon Kommentar 1 506 → **1 535**, Stufe 1 27 → **28** Schritte. Falsch: „Werkzeuge mit Selbstprobe **28**" — gemessen sind es **13** (sechs verschiedene Auslegungen durchgerechnet, keine ergibt 28). Unverändert richtig: `server/` 100 333, LIESMICH 7 206, 48 Werkzeugordner. | Die Begründung von E-PK-24 trägt auch bei 13. Die Zahl darf nicht abgeschrieben werden; PK-04 misst sie neu und nennt den Befehl. |
| **F-PK-09** | **Die drei Mailwerte heißen anders, als Konzept 1.4 sie schreibt.** Gesetzt sind buchstäblich `_MAIL_URL`, `_MAIL_USER`, `_MAIL_PASS` — führender Unterstrich, **kein** Präfix. `NADOKU_STAGING_MAIL_*` gibt es nicht. Die Klammer „(die Mailwerte mit führendem Unterstrich)" sagt es, geht aber beim Abschreiben verloren. | `Sandbox-Setup.md` 4 schreibt alle sieben Namen **aus** und benennt den Bruch. Alle 7 von 7 sind gesetzt (Längen dort). |
| **F-PK-10** | **Hook und `aufbau.sh` widersprechen einander.** `.claude/hooks/session-start.sh` ruft `playwright install firefox webkit`; `tools/containeraufbau/aufbau.sh` sagt wörtlich, das sei ausdrücklich **nicht** der Weg, weil die Engines im Abbild liegen und ein Nachladen eine zweite Fassung danebenzöge. Gemessen: Die Engines liegen im Abbild. Dazu **zwei disjunkte** Bibliothekslisten (6 gegen 4 Pakete, keine Überschneidung), beide unter Berufung auf „die Namen, die Playwright selbst nennt". | In `Sandbox-Setup.md` 1.2 benannt. **PK-02 muss entscheiden, welche der beiden Fassungen gilt** — die Zusammenlegung darf den Widerspruch nicht erben. |
| **F-PK-11** | **`CLAUDE.md` 3 stimmt bei der Ausnahmeliste zur Hälfte.** Dort steht „Acht Pfade … **Jeder steht dort zweimal**". Gemessen (`ausliefern-lauf.yml`): Acht Pfade stimmt; zweimal stehen nur die **drei Verzeichnisse**, die fünf Dateien je **einmal** — zusammen 14 Zeilen. | Nicht in PK-01 berichtigt: `CLAUDE.md` 3 gehört zum Auslieferungsweg, den **PK-06** anfasst. Dort mit berichtigen. |
| **F-PK-12** | **`docs/Technik.md` „2a" steht physisch unter „## 4. Zentrale Abläufe".** Wer die Nummer liest und in Abschnitt 2 sucht, findet nichts; `CHANGELOG.md` verweist bereits so darauf. | Nicht in PK-01 aufgelöst (das wäre ein Umbau von `Technik.md`). **PK-07** zieht `Technik.md` ohnehin nach und löst die Fehlstellung dort auf. |
| **F-PK-13** | **`.claude/settings.local.json` rangiert über der geteilten Datei** und steht nicht in `.gitignore`. Entstünde sie, hübe sie die Deny-Liste auf, ohne im Pull Request zu erscheinen. | In `Pruefablauf.md` 2.3 als offener Weg benannt statt verschwiegen. Ob die Datei in `.gitignore` gehört, entscheidet die Betreiberin — ein Eintrag machte sie unsichtbar, kein Eintrag lässt sie wenigstens als unverfolgte Datei auffallen. |

## 5. Entscheidungen der Umsetzung

| Nr. | Entscheidung | Grund |
|---|---|---|
| **E-PK-31** | **`docs/Technik.md` 2a wird von `Sandbox-Setup.md` abgelöst**, nicht danebengestellt. In `Technik.md` bleibt der Teil, der die *Prüfmittel* betrifft (Motortabelle, die drei Engine-Befunde, die vierte Zahl); der Teil über den *Container* wird zum Verweis. 2a schrumpft von 116 auf 73 Zeilen. | Das Konzept nennt `Technik.md` erst in PK-07. Zwei Beschreibungen derselben Umgebung nebeneinander stehen zu lassen wäre aber genau der Fehler, den PK abstellt — und `CLAUDE.md` 9 verlangt die Pflege im selben Paket. **Zur Bestätigung vorgelegt.** |

---

*PK-01 abgeschlossen am 21.09.2026. Nächstes Paket: PK-02 (Sandbox-Setup).*
