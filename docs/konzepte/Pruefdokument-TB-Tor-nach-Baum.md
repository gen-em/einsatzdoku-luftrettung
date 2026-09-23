# Prüfdokument TB — Tor nach Baum

Gehört zu `Konzept-TB-Tor-nach-Baum.md`. Nach `CLAUDE.md` 7: Was wurde
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
| P-TB-01 | Selbstprobe des Tors kennt den Baum (TB-01) | `python3 tools/kette/freigabe.py --selbstprobe` | alle Lagen erfüllt, 0 offen; die Zahl steht in `tools/kette/LIESMICH.md` und ist größer als 32 | „offen" > 0, oder die Zahl ist noch 32 (dann wurde nichts ergänzt) | offen |
| P-TB-02 | Der Aufruf im Auslieferungslauf passt zum Werkzeug (TB-02) | `python3 tools/kettenaufrufe/pruefen.py` | 0 Befunde; `grep -c -- '--stufe1 ' .github/workflows/ausliefern-lauf.yml` → 0 | ein Befund zu `freigabe.py`, oder der alte Schalter steht noch | offen |
| P-TB-03 | Stufe 1 auf dem Arbeitszweig ist grün, und `schon-gemessen` sagt „ja" (TB-03) | PR öffnen; im Lauf den Job „Schon gemessen?" und `Stufe 1` ansehen | `messen=ja`, `Stufe 1` gelaufen und grün, Laufdauer unverändert | `Stufe 1` übersprungen auf einem PR — dann greift das `if:` falsch, und der Zweigschutz hätte keine Pflichtprüfung mehr | offen |
| P-TB-04 | Jede fremde `uses:`-Zeile trägt eine SHA (TB-03) | `grep -rhoE 'uses: [^ ]+@[0-9a-f]{40}' .github/workflows/ \| wc -l` gegen `grep -rh 'uses:' .github/workflows/ \| grep -vc 'uses: \./'` | beide Zahlen gleich (heute 11; nach TB-03 eine mehr) | Zahlen ungleich | offen |
| P-TB-05 | **Nach dem Merge:** der Push-Lauf auf `main` verweist statt zu messen (TB-03, E-TB-06) | Lauf von `pruefung.yml` zum Merge-Commit öffnen | Job „Schon gemessen?" grün mit Zeile „Baum … bereits gemessen: Lauf …"; `Stufe 1` und `Schema gegen …` übersprungen; Laufdauer unter einer Minute | `Stufe 1` läuft voll (dann hat der Vergleich keinen Treffer gefunden — Baum der PR-Läufe gegen Baum des Merge-Commits von Hand vergleichen), oder der Verweis nennt keinen Lauf | offen — nach dem Merge |
| P-TB-06 | **Beim nächsten Tag:** das Tor geht ohne Warten auf (TB-02) | Tag `web-v*` unmittelbar nach dem Merge setzen, Produktionslauf freigeben | Schritt 7 nennt ≥ 1 Lauf mit passendem Baum und Job `Stufe 1` grün; kein „Kein grüner Stufe-1-Lauf" | Schritt 7 rot mit derselben Meldung wie Lauf 35834341392 | offen — beim nächsten Tag |
| P-TB-07 | Das Ruleset hält einen PR ohne aktuellen `main` an (TB-M1) | Bei zwei offenen PRs den ersten mergen, dann den zweiten ansehen | „Update branch" erscheint, Merge gesperrt, bis Stufe 1 auf dem nachgezogenen Stand grün ist | Merge möglich ohne Nachziehen | offen — beim nächsten Fall |
| P-TB-08 | Die Dokumente sagen „Baum", nicht „Commit" (TB-04) | `grep -rn "demselben Commit" CLAUDE.md docs/*.md`; Linkprobe; Textprobe | 0 Treffer außerhalb der Geschichte; Linkprobe 0 Abweichungen; Textprobe 0 neue Treffer | ein Treffer in `CLAUDE.md`, `Technik.md` oder `Pruefablauf.md` | offen |
| P-TB-09 | Das Tor bleibt zu, wenn der Baum abweicht (E-TB-04) | Selbstprobe-Lage „anderer Baum + Job grün → nein" (Teil von P-TB-01) | Lage erfüllt | Lage fehlt oder offen | offen |
| P-TB-10 | Das Tor bleibt zu, wenn `Stufe 1` im Lauf übersprungen war (E-TB-05) | Selbstprobe-Lage „gleicher Baum + Job übersprungen → nein" (Teil von P-TB-01) | Lage erfüllt | Lage fehlt oder offen | offen |

## 2. Maschinell geprüft — mit Mittel und Zahl

*Wird je Paket gefüllt.*

| Paket | Mittel | Zahl | Datum |
|---|---|---|---|
| Konzept | `git rev-parse f356a44^{tree} 10a942c^{tree}` | beide `447793e41c1ce317c8c4e1daa2ba94ff04bffa55` | 23.09.2026 |
| Konzept | `grep -c "if: steps.bereiche" .github/workflows/pruefung.yml` | **3** (Java, Android, Uhr); kein `server/`-Schritt bedingt | 23.09.2026 |
| Konzept | Lauf-API, `pruefung.yml`: Läufe zu `10a942c` | 1 (`push`, `in_progress` seit 07:53:45 UTC); zu `f356a44`: 1 (`pull_request`, `success`, 07:09–07:51 UTC) | 23.09.2026 |

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
