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
| **Der Bau der Uhr-App** | Die Ausbaustufe `uhr` ist gebaut, aber **nicht abgenommen**: SDK und Gerätedateien sind nicht geholt, `pruefstand.sh reihe` ist nicht gefahren. Die Android-Seite ist inzwischen abgenommen (7m 19s, 0 Lint-Fehler, 670 Prüffälle / 0). **P-PK-11 bleibt zur Hälfte offen.** | vor dem Phasen-PR |
| **Die meisten Aufrufe in `pruefablauf.json`** | Von **40** eingetragenen Proben sind **17 über den Prüfstand gefahren** worden (15 grün, 2 rot — davon einer ein Verdrahtungsfehler, einer ein echter Befund). Die übrigen 23 stehen eingetragen und sind nie ausgeführt. `tools/kettenaufrufe/` hält sie gegen die Schnittstelle ihres Werkzeugs (0 Befunde) — das ist etwas anderes als ein Lauf. | beim ersten Paket, das die jeweilige Fläche berührt |
| **Der lange Lauf mit Bilderlauf und Bedienprobe** | Ein Lauf mit `--datei server/spur_lib.php --datei server/backup_lib.php` war bei **14 grünen Proben** (darunter `spurprobe` und `containerprobe`), als der Behälter neu startete. Bilderlauf und Bedienprobe sind damit **nicht** über den Prüfstand gefahren worden. | nächster Lauf |
| **Ein frischer Container** | Die Abnahme von `aufbauen.sh` ist in **dieser** Sitzung gefahren, nicht in einem frisch gestarteten Container. Der Unterschied ist messbar: Hier waren die sechs Pakete des alten Hooks schon installiert. Was ein frischer Container vorfindet, zeigt erst die nächste Sitzung. | nächste Sitzung |
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
| P-PK-16 | Der offene Befund der Wiederherstellungsprobe | Sicherungsziel eintragen, `php tools/wiederherstellungs-probe/probe.php` | 110 Erwartungen, 0 nicht erfüllt | die zwei Befunde bleiben auch mit Sicherungsziel stehen (dann ist es die Anwendung) | **offen** — siehe F-PK-18 |
| P-PK-11 | Ausbaustufen `android` und `uhr` (PK-02) | `aufbauen.sh android` → `./gradlew build`; `aufbauen.sh uhr` → `pruefstand.sh reihe` | 0 Lint-Fehler, 0 Fehlschläge bzw. Reihe grün | ein Fehlschlag, oder das SDK fehlt | **android erledigt 21.09.2026** (BUILD SUCCESSFUL in 7m 19s, **0 Lint-Fehler**, **670 Prüffälle, 0 Fehlschläge**); **uhr offen** |
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

## 6. Befunde der Umsetzung (F-PK-07 ff.)

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

## 7. Entscheidungen der Umsetzung

| Nr. | Entscheidung | Grund |
|---|---|---|
| **E-PK-32** | **Das Modul `plattform` nimmt Docker für alle vier Fassungen** statt des in E-PK-30 vorgesehenen Umwegs über Ubuntu-Pakete unter `/opt`. | E-PK-30 begründet den Umweg mit der Drosselung von Docker Hub. Die tritt bei vier Abbildern nicht ein (F-PK-16). Der Umweg wäre aufwendiger, zerbrechlicher und löste ein Problem, das es nicht gibt. **Gemessen: `alles` in 29,7 s, 4 × 19/0.** Zur Bestätigung vorgelegt. |
| **E-PK-31** | **`docs/Technik.md` 2a wird von `Sandbox-Setup.md` abgelöst**, nicht danebengestellt. In `Technik.md` bleibt der Teil, der die *Prüfmittel* betrifft (Motortabelle, die drei Engine-Befunde, die vierte Zahl); der Teil über den *Container* wird zum Verweis. 2a schrumpft von 116 auf 73 Zeilen. | Das Konzept nennt `Technik.md` erst in PK-07. Zwei Beschreibungen derselben Umgebung nebeneinander stehen zu lassen wäre aber genau der Fehler, den PK abstellt — und `CLAUDE.md` 9 verlangt die Pflege im selben Paket. **Zur Bestätigung vorgelegt.** |

---

*PK-01 abgeschlossen am 21.09.2026. Nächstes Paket: PK-02 (Sandbox-Setup).*
