# Prüfdokument PK — Prüfkette

Gehört zu `Konzept-PK-Pruefkette.md`. Nach `CLAUDE.md` 7: Was wurde
maschinell geprüft (Mittel und Zahl), was im Browser, was nicht und warum,
und eine abhakbare Prüfliste. Angelegt mit PK-M1; vollständig mit PK-01.

## 0. Was nicht geprüft werden konnte

*Noch nichts — wird mit PK-01 gefüllt.*

## 1. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-PK-01 | Zweigschutz und Merge-Recht auf `main` (PK-M1) | (a) Claude-Instanz öffnet PR und versucht den Merge über die API; (b) dieselbe Instanz pusht direkt auf `main`; (c) die Betreiberin mergt den PR | (a) abgewiesen, (b) abgewiesen, (c) geht | ein Merge oder Push durch die Instanz kommt durch | **gemessen 21.09.2026 — (a) kam durch, siehe F-PK-01**; (b) abgewiesen; (c) mit diesem PR |
| P-PK-02 | edbak-500 (`097D7622`) lokal reproduzieren (PK-03) | Kreislauf edbak gegen die lokale Installation unter PHP 8.3.33 und 8.4 | Export fällt lokal mit demselben Fehler, oder er fällt nicht (dann Plattform) | — | **erledigt 21.09.2026**: gegen MariaDB grün, gegen MySQL 8.4.0 rot mit `1064 near 'manual'`; behoben in Web 20.26.3 (PR #69, Nr. 267) |
| P-PK-03 | Merge-Werkzeug der Claude-Instanzen gesperrt (Folge aus F-PK-01) | `.claude/settings.json` trägt `mcp__github__merge_pull_request` und `mcp__github__enable_pr_auto_merge` in der Deny-Liste; eine Instanz ruft das Werkzeug auf | der Aufruf wird vom Harness verweigert | der Merge geht durch | offen (PK-01) |

## 2. Messprotokoll P-PK-01 (21.09.2026)

Rulesets auf `main` seit dem 21.09.2026: „Main Protect" (PR-Pflicht,
Pflichtprüfung `Stufe 1`, kein Force-Push, kein Löschen, Bypass leer) und
„Main Merge-Recht" (Restrict updates, Bypass nur die Betreiberin, Modus
„pull requests only").

| Messung | Wer | Ergebnis |
|---|---|---|
| (a) Merge von PR #67 über die API (`merge_pull_request`), nach grüner Stufe 1 | Claude-Instanz | **durchgegangen** — Merge-Commit `c78988f`, `merged_by: chodid`. Die GitHub-Werkzeuge von Claude Code laufen über den GitHub-Anschluss der Betreiberin und tragen deren Identität; für das Ruleset war das die Betreiberin selbst (Bypass „pull requests only“). Vor grüner Stufe 1 lautete die Ablehnung nur „Required status check Stufe 1 is in progress“ |
| (b) `git push origin HEAD:main` | dieselbe Instanz, Konto `claude` (GitHub-App über den Proxy) | **abgewiesen**: `GH013 … Cannot update this protected ref` (Ruleset Merge-Recht), dazu „Required status check Stufe 1 is in progress“ und „a merge commit must be used“ |
| (c) Merge dieses PR (Nachmessung) | Betreiberin, von Hand | *mit dem Merge dieses PR belegt* |

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
   (P-PK-03, PK-01).
3. `CLAUDE.md` 8 sagt es als Satz: Eine Claude-Instanz mergt nie; sie
   öffnet PRs, die Betreiberin mergt.

Was bleibt: Wer die Deny-Liste im Repositorium ändert, hebt Lage 2 auf; das
fällt im PR auf, weil `.claude/settings.json` in der Berührung steht.
