# Prüfdokument TB — Tor nach Baum

Gehörte zu `Konzept-TB-Tor-nach-Baum.md` — **mit dem Abschluss am 23.09.2026
gelöscht**, letzter Stand in der Historie unter `a72c7df`; die
Erledigt-Zeile steht in `Rahmenplan.md` 8. Dieses Dokument bleibt, bis
P-TB-06 und P-TB-07 abgehakt sind. Nach `CLAUDE.md` 7: Was wurde
maschinell geprüft (Mittel und Zahl), was im Browser, was nicht und warum,
und eine abhakbare Prüfliste. Angelegt mit dem Konzept am 23.09.2026;
fortgeschrieben je Paket.

## 0. Was nicht geprüft werden konnte — und wann dann

Vorn, nicht in einer Fußnote. Drei Dinge misst nur die echte Anlage, und
eines davon erst nach dem Merge:

| Was | Warum nicht im Container | Wann dann |
|---|---|---|
| **Der Verweis auf `main` bei gleichem Baum** (TB-03) | Ein Push auf `main` gibt es nur durch den Merge der Betreiberin; im PR ist `schon-gemessen` immer „ja". | P-TB-05, nach dem Merge, an der Lauf-Nummer |
| **Ein Tag, der ohne Warten durchgeht** (TB-02) | Ein Produktionslauf ist eine Auslieferung; er wird nie zur Probe gefahren. Der Probelauf überspringt das Tor (E-KH-08). | P-TB-06, beim nächsten `web-v*`-Tag |
| **Das Ruleset** (TB-M1) | Einstellung bei GitHub, nur die Betreiberin sieht sie. | **Gemessen von ihr am 23.09.2026:** war nicht gesetzt, ist gesetzt. Gegenprobe P-TB-07 beim nächsten PR nach fremdem Merge. |
| **Die Browserprüfung** | Kein Byte unter `server/`. Es gibt nichts zu sehen. | — |

## 1. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-TB-01 | Selbstprobe des Tors kennt den Baum (TB-01) | `python3 tools/kette/freigabe.py --selbstprobe` | alle Lagen erfüllt, 0 offen; die Zahl steht in `tools/kette/LIESMICH.md` und ist größer als 32 | „offen" > 0, oder die Zahl ist noch 32 (dann wurde nichts ergänzt) | **erfüllt 23.09.2026: 52/0** |
| P-TB-02 | Der Aufruf im Auslieferungslauf passt zum Werkzeug (TB-02) | `python3 tools/kettenaufrufe/pruefen.py` | 0 Befunde; `grep -c -- '--stufe1 ' .github/workflows/ausliefern-lauf.yml` → 0 | ein Befund zu `freigabe.py`, oder der alte Schalter steht noch | **erfüllt 23.09.2026:** 88 Aufrufe, 0 Befunde; `--stufe1 ` 0-mal |
| P-TB-03 | Stufe 1 auf dem Arbeitszweig ist grün, und `schon-gemessen` sagt „ja" (TB-03) | PR öffnen; im Lauf den Job „Schon gemessen?" und `Stufe 1` ansehen | `messen=ja`, `Stufe 1` gelaufen und grün, Laufdauer unverändert | `Stufe 1` übersprungen auf einem PR — dann greift das `if:` falsch, und der Zweigschutz hätte keine Pflichtprüfung mehr | **erfüllt 23.09.2026, PR #77, Lauf 35840201595:** „Messen: ja — kein Push auf `main` (pull_request)"; `Stufe 1` 20 min 36 s grün, beide `Schema gegen …` grün |
| P-TB-04 | Jede fremde `uses:`-Zeile trägt eine SHA (TB-03) | `grep -rhoE 'uses: [^ ]+@[0-9a-f]{40}' .github/workflows/ \| wc -l` gegen `grep -rh 'uses:' .github/workflows/ \| grep -vc 'uses: \./'` | beide Zahlen gleich (heute 11; nach TB-03 eine mehr) | Zahlen ungleich | **erfüllt 23.09.2026: 12 / 12** |
| P-TB-05 | **Nach dem Merge:** der Push-Lauf auf `main` verweist statt zu messen (TB-03, E-TB-06) | Lauf von `pruefung.yml` zum Merge-Commit öffnen | Job „Schon gemessen?" grün mit Zeile „Baum … bereits gemessen: Lauf …"; `Stufe 1` und `Schema gegen …` übersprungen; Laufdauer unter einer Minute | `Stufe 1` läuft voll (dann hat der Vergleich keinen Treffer gefunden — Baum der PR-Läufe gegen Baum des Merge-Commits von Hand vergleichen), oder der Verweis nennt keinen Lauf | **erfüllt 23.09.2026, Lauf 35844072753** (Merge von PR #77, `eefffca`): „Baum `c440740` bereits gemessen: Lauf 35842387963 (`pull_request`, `a72c7df`), Job `Stufe 1` grün am 2026-09-23 09:35:21Z"; `Stufe 1` und `Schema gegen …` übersprungen; Lauf **18 s** |
| P-TB-06 | **Beim nächsten Tag:** das Tor geht ohne Warten auf (TB-02) | Tag `web-v*` unmittelbar nach dem Merge setzen, Produktionslauf freigeben | Schritt 7 nennt ≥ 1 Lauf mit passendem Baum und Job `Stufe 1` grün; kein „Kein grüner Stufe-1-Lauf" | Schritt 7 rot mit derselben Meldung wie Lauf 35834341392 | **offen — mit Web 20.37.2**, die eigens dafür ausgeliefert wird (Tag `web-v20.37.2` auf dem Merge-Commit dieses Abschlusses). Zwei Tags ohne passende Fassung belegen es nicht: `web-v20.37.1-test` (Lauf 35844281706) und `web-v20.37.2` auf `eefffca` (Lauf 35845324185) endeten beide an Schritt 6 „Tag gegen WEB_VERSION halten", Schritt 7 übersprungen |

| P-TB-07 | Das Ruleset hält einen PR ohne aktuellen `main` an (TB-M1) | Bei zwei offenen PRs den ersten mergen, dann den zweiten ansehen | „Update branch" erscheint, Merge gesperrt, bis Stufe 1 auf dem nachgezogenen Stand grün ist | Merge möglich ohne Nachziehen | offen — beim nächsten Fall |
| P-TB-08 | Die Dokumente sagen „Baum", nicht „Commit" (TB-04) | `grep -rn "demselben Commit" CLAUDE.md docs/*.md`; Linkprobe; Textprobe | 0 Treffer außerhalb der Geschichte; Linkprobe 0 Abweichungen; Textprobe 0 neue Treffer | ein Treffer in `CLAUDE.md`, `Technik.md` oder `Pruefablauf.md` | **erfüllt 23.09.2026:** `demselben Commit` 0 Treffer; Linkprobe 122/0; Textprobe 0/0 |
| P-TB-09 | Das Tor bleibt zu, wenn der Baum abweicht (E-TB-04) | Selbstprobe-Lage „anderer Baum + Job grün → nein" (Teil von P-TB-01) | Lage erfüllt | Lage fehlt oder offen | **erfüllt 23.09.2026** — Lage vorhanden; mit ausgeschaltetem Baumvergleich 2 offen |
| P-TB-10 | Das Tor bleibt zu, wenn `Stufe 1` im Lauf übersprungen war (E-TB-05) | Selbstprobe-Lage „gleicher Baum + Job übersprungen → nein" (Teil von P-TB-01) | Lage erfüllt | Lage fehlt oder offen | **erfüllt 23.09.2026** — Lage vorhanden; mit ausgeschalteter Job-Prüfung 3 offen |

## 2. Maschinell geprüft — mit Mittel und Zahl

*Wird je Paket gefüllt.*

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
| Konzept | `git rev-parse f356a44^{tree} 10a942c^{tree}` | beide `447793e41c1ce317c8c4e1daa2ba94ff04bffa55` | 23.09.2026 |
| Konzept | `grep -c "if: steps.bereiche" .github/workflows/pruefung.yml` | **3** (Java, Android, Uhr); kein `server/`-Schritt bedingt | 23.09.2026 |
| Konzept | Lauf-API, `pruefung.yml`: Läufe zu `10a942c` | 1 (`push`, `in_progress` seit 07:53:45 UTC); zu `f356a44`: 1 (`pull_request`, `success`, 07:09–07:51 UTC) | 23.09.2026 |
| TB-01 | `python3 tools/kette/freigabe.py --selbstprobe` | **52 erfüllt, 0 offen** (vorher 32); zwanzig neue Lagen | 23.09.2026 |
| TB-01 | Gegenprobe: drei Fehler in Kopien von `freigabe.py` eingebaut — Job-Prüfung aus, Baumvergleich aus, Zielbaum-Prüfung aus | **3, 2 und 1 offen** — jeder fällt auf | 23.09.2026 |
| Abschluss | Lauf 35844072753 (`pruefung.yml`, Push `main`, `eefffca`) | `Schon gemessen?` → `messen=nein`, Verweis auf Lauf 35842387963; `Stufe 1` und `Schema gegen …` übersprungen; Lauf **18 s** (Merge davor, Lauf #258: rund 41 min). `git rev-parse eefffca^{tree} a72c7df^{tree}` → zweimal `c4407403…` | 23.09.2026 |
| Abschluss | Lauf 35845324185 (`auslieferung.yml`, Tag `web-v20.37.2` auf `eefffca`, dort noch `WEB_VERSION` 20.37.1) | wie der Lauf darunter: rot an Schritt 6, Tor übersprungen, nichts ausgeliefert | 23.09.2026 |
| Abschluss | Lauf 35844281706 (`auslieferung.yml`, Tag `web-v20.37.1-test`) | `produktion / ausliefern` rot an Schritt 6 „Tag gegen WEB_VERSION halten"; Schritt 7 (das Tor) und alles danach **übersprungen**, Wartung nicht eingeschaltet, Zeiger nicht bewegt — belegt P-TB-06 nicht | 23.09.2026 |
| TB-05 | PR #77, Lauf 35840201595 (`015acc8`) | `Schon gemessen?` 4 s, `messen=ja`; `Stufe 1` **20 min 36 s** grün (Lauf #253 auf `main`: 46 min), davon Uhr-Schritt **10 min 54 s** (vorher 38 min 27 s); `Schema gegen MySQL 8.4.0` 35 s grün mit TCP-Gesundheitscheck (F-TB-12) | 23.09.2026 |
| TB-05 | PR #77, Lauf 35840034467 (`7312019`) | `Schema gegen MySQL 8.4.0` **rot**: „MySQL server has gone away" — der Wettlauf aus F-TB-12 | 23.09.2026 |
| TB-05 | `bash tools/quelltext/pruefen.sh alle` | **8 von 8 grün** | 23.09.2026 |
| TB-05 | Backlog „keine Nummer zweimal" (derselbe `grep` wie in `pruefung.yml`) | **0 doppelte** | 23.09.2026 |
| TB-04 | `bash tools/quelltext/pruefen.sh linkprobe` | **122 Verweise, 0 Abweichungen** | 23.09.2026 |
| TB-04 | `bash tools/quelltext/pruefen.sh textprobe` | **0 Treffer, 108 Regeln, 0 ungenutzt** | 23.09.2026 |
| TB-04 | `grep -rn "demselben Commit" CLAUDE.md docs/*.md` ohne Changelog | **0** (der eine Satz in `CLAUDE.md` 3, der den alten Stand nennt, steht über einen Zeilenumbruch und ist Geschichte) | 23.09.2026 |
| TB-03 | Verweis-Job (`schon-gemessen`, Schritt `vergleich`) als Bash aus der YAML, unter `bash -eo pipefail` gegen eine `gh`-Attrappe mit vier PR-Läufen (anderer Baum; gleicher Baum mit übersprungenem Job; gescheiterter Baum-Abruf; gleicher Baum mit grünem Job) | 7 Lagen, alle richtig: PR → `ja`; Push `main` mit grünem Treffer → `nein` und die Verweiszeile mit Lauf, Commit, Zeitpunkt; der übersprungene Job wird übergangen und genannt; kein grüner Job → `ja`; Liste nicht abrufbar → `ja`; kein Git → `ja`; `workflow_dispatch` auf `main` → `ja`. Jede Lage rc 0 | 23.09.2026 |
| TB-03 | `grep`-Zählung aus `CLAUDE.md` 3 | **12 SHA-Zeilen / 12 fremde `uses:`** (vorher 11) | 23.09.2026 |
| TB-02 | `python3 tools/kettenaufrufe/pruefen.py` | **88 Aufrufe, 0 Befunde**, 2 ungeprüft (wie auf `main`: `gegen.sh`, `browserprobe.mjs`) | 23.09.2026 |
| TB-02 | `yaml.safe_load` über `ausliefern-lauf.yml` | gültig | 23.09.2026 |
| TB-02 | Schritt 7 als Bash aus der YAML gezogen, unter `bash -eo pipefail` gegen eine `gh`-Attrappe (5 Läufe: grüner PR-Lauf mit gleichem Baum, anderer Baum, gescheiterter Baum-Abruf, Verweis-Lauf auf `main` mit übersprungenem `Stufe 1`, zweiter PR-Lauf auf demselben Commit mit rotem Job) | (1) wie geliefert → **rc 0**, genau Lauf 101 anerkannt; (2) Job in 101 übersprungen → **rc 1**, der Verweis-Lauf zählt nicht; (3) Baum von 101 anders → **rc 1**; (4) kein Git im Arbeitsverzeichnis → **rc 1** „Baum des Tag-Commits ist nicht ermittelt" | 23.09.2026 |
| TB-01 | `python3 tools/kettenaufrufe/pruefen.py` | **1 Befund** — `ausliefern-lauf.yml` ruft noch `--stufe1` (F-TB-08, gewollt bis TB-02); 2 ungeprüft wie auf `main` | 23.09.2026 |

## 3. Grenzen der benutzten Prüfmittel

- **Die Selbstprobe von `freigabe.py` prüft das Urteil, nicht den Abruf.**
  Ob `gh api` die richtigen Felder liefert (`.tree.sha`, `.jobs[].name`,
  `.jobs[].conclusion`), belegt erst ein echter Produktionslauf (P-TB-06).
  Das ist dieselbe Grenze wie bei AP7 (E-KH-15) und bewusst so — Abrufen
  lässt sich ohne Netz nicht proben, Entscheiden schon.
- **`kettenaufrufe` prüft Namen und Schalter, nicht Vollständigkeit**
  (F-PK-21). Dass der Aufruf im Arbeitslauf auch die Datei liefert, die das
  Werkzeug erwartet, zeigt nur ein Lauf.
- **Der Probelauf der Kette überspringt das Tor** (E-KH-08). Er belegt die
  YAML-Syntax des Schritts, nicht sein Urteil.
