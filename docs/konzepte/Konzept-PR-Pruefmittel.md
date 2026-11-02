# Konzept PR — Prüfmittel (Fahrplanschritt „PR")

**Kürzel:** PR. Pakete `PR-NN`, Entscheidungen `E-PR-NN`, Befunde
`F-PR-NN`, Fragen `Q-PR-NN`, Prüfpunkte `P-PR-NN` (`docs/Pruefablauf.md` 7).
Das Kürzel ist die Kennung aus dem Fahrplan; mit „PR #NNN" (Pull Request)
ist es nicht verwandt.

**Zweig:** `claude/kind-cray-lgaz7d` · **Herkunft:**
`docs/konzepte/Vorbereitung-Backlog-Einordnung.md` 4 und E-BE-04
(Betreiberin, 02.11.2026) · **Prüfdokument:** `Pruefdokument-PR-Pruefmittel.md`
(entsteht mit PR-04).

---

## Statusblock

| Paket | Inhalt | Stand |
|---|---|---|
| PR-01 | Nr. 385 — Zweitfaktorprobe räumt ihre Sperre ab | **erledigt** 02.11.2026 |
| PR-02 | Nr. 352 — `nummern.py` kennt den offenen Merge | **erledigt** 02.11.2026 |
| PR-03 | Nr. 403 — Freigabeprobe im Gesamtlauf | offen — als Nächstes |
| PR-04 | Abschluss — Prüfstand, Prüfdokument, Pull Request | offen |

**In Arbeit:** PR-03. **Es hakt:** nirgends.

---

## 1. Auftrag

Drei Werkzeugfehler, die heute Läufe rot färben oder Messungen verfälschen,
vor den Schritten 21 und 22 beheben (E-BE-04). Nur `tools/` und die
Dokumente, die diese Werkzeuge beschreiben — **keine Fassung** wird
hochgestuft, also auch kein Eintrag im Changelog (CLAUDE.md 2 (1)).

Nicht Teil dieses Schritts: die übrigen Werkzeug-Punkte (232, 234, 237, 240,
290, 300, 343, 347, 353, 384, 388, 391, 410, 411). Sie tragen `gehört zu:
14`; die Inventur nach R68 entscheidet über sie (E-BE-04).

## 2. Entscheidungen

**E-PR-01 — Ein schlankes Konzept mit Statusblock und Prüfdokument
(02.11.2026, Betreiberin).** Die Fahrplanzeile sagte „kein Konzept — drei
Backlog-Punkte mit Weg und Abnahme"; K5 und K9 verlangen aber Statusblock
und Prüfdokument. Gewählt ist das Muster von RP: dieses Dokument, ein
Prüfdokument daneben, beide gehen beim Abschluss nach CLAUDE.md 7.

**E-PR-02 — Reihenfolge 385, 352, 403 (02.11.2026, Betreiberin).** Die
zwei kleinen, sicheren zuerst; 403 braucht lange Gesamtläufe (je rund
95 Minuten) und hat eine unbekannte Ursache.

**Modellwahl:** Opus für alle Pakete. **Fächerung:** keine — PR-01 und
PR-03 sind Prüfarbeit an der einen örtlichen Anlage, PR-02 ist eine Datei
(CLAUDE.md 7).

## 3. Arbeitspakete

### PR-01 Zweitfaktorprobe räumt ihre Sperre ab (Nr. 385) — erledigt

*Weg:* Der Aufräumteil der Probe (`register_shutdown_function` nach dem
Anlegen des Probenkontos) löscht Konto, Protokollzeilen und Zähler, aber
nicht das Ereignis in `sicherheit_ereignisse`, das `rate_ereignis()` beim
Sperren schreibt. Neu: `DELETE FROM sicherheit_ereignisse WHERE merkmal = ?`
mit dem Merkmal des Probenkontos (`id:zweitfaktor-probe@probe.invalid`),
alle Töpfe.

*Abnahme:* nach einem Lauf keine Zeile mehr für das Probenkonto.

*Ergebnis:* siehe Prüfprotokoll 4, PR-01.

### PR-02 `nummern.py` kennt den offenen Merge (Nr. 352) — erledigt

*Weg:* Liegt `MERGE_HEAD` vor, werden die Nummern des Arbeitsbaums
zusätzlich um die von `MERGE_HEAD` bereinigt — was der aufgenommene Stand
schon trägt, ist nicht neu. Die Selbstprobe bekommt den Fall und eine
Gegenprobe (eine eigene neue Nummer während des Merges bleibt sichtbar).
`docs/Pruefablauf.md` 5.3 verliert den Satz zur Umgehung über einen
Folge-Commit.

*Abnahme:* ein offener Merge mit einer Nummer aus `main` → 0
Überschneidungen.

*Ergebnis:* siehe Prüfprotokoll 4, PR-02, und F-PR-01.

### PR-03 Freigabeprobe im Gesamtlauf (Nr. 403)

*Weg:* Ursache unbekannt. Gesamtlauf `neben` fahren; trifft der Abbruch,
an der Seitenkennung (seit Web 23.5.1 in der Meldung) die Ursache ablesen
und beheben — nicht die Probe wiederholen lassen. Bleibt der Abbruch aus,
wird das als Befund mit Zahl gemeldet, nicht als Behebung.

*Abnahme:* zwei Gesamtläufe `neben` ohne diesen Abbruch.

### PR-04 Abschluss

Prüfstand über den Endstand, Prüfdokument, Backlog-Punkte nach
`Backlog-Erledigt.md`, Pull Request. Erledigt-Zeile, Löschung von Konzept
und Prüfdokument erst nach der Freigabe (CLAUDE.md 7).

## 4. Prüfprotokoll

**PR-01** (02.11.2026, örtliche Anlage Fassung v23.6.0 nach
`hochfahren.sh`, MariaDB des Containers):

| Messung | vorher | nachher |
|---|---|---|
| `php tools/proben/zweitfaktor/probe.php` | 139 ok, 0 fehlen | 139 ok, 0 fehlen |
| Zeilen in `sicherheit_ereignisse` mit Merkmal `%zweitfaktor%` nach dem Lauf | 1 (`totp`, `sperre`, `id:zweitfaktor-probe@probe.invalid`) | 0 |

Der Fehler ist damit vor der Änderung nachgestellt und danach nicht mehr
messbar; Laufzeit der Probe je rund 4 min.

**PR-02** (02.11.2026):

| Messung | alte Fassung | neue Fassung |
|---|---|---|
| `nummern.py --selbstprobe` | — (10 Fälle; die zwei neuen schlagen fehl: 7 und 8 als Überschneidung mit `origin/main`) | 12 Fälle, 0 Fehlschläge |
| Echtes Repositorium: Arbeitsbaum auf `118bc6c~1` (vor Nr. 440), `git merge --no-commit origin/main`, `nummern.py --ohne-holen` | 2 Überschneidungen (Nr. 433, 440) | 0 Überschneidungen |
| `nummern.py --ohne-holen` auf dem Arbeitszweig | 0 | 0 |

## 5. Befunde

**F-PR-01 — Der erste Fall der Selbstprobe war grün, ohne zu messen.** Der
Klon der Selbstprobe holt `origin` erst im gemessenen Lauf; ein Merge davor
nahm deshalb ein `origin/main` ohne die Nummern 7 und 8 auf, und der Fall
war grün, bevor das Werkzeug geändert war. Behoben in der Anlage des Falls
(`fetch` vor dem Merge); erst danach schlug er fehl, wie er sollte, und
die Behebung färbte ihn grün.
