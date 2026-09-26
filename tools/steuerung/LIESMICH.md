# Steuerung

Halten Rahmenplan und Backlog ihre Decken — und trägt jeder offene Punkt ein Ziel?
**Anlass: Nr. 177, 196, 199** — Konzept SD 1.1: 121 Berichtigungssätze im Rahmenplan, Abschnitt 5 mit 96 Zeilen gegen 113 offene Punkte.

## Aufruf

```bash
python3 tools/steuerung/decken.py        # --stellen · --selbstprobe
python3 tools/steuerung/uebersicht.py    # --ziel 17 · --pruefen · --selbstprobe
```

## Was es misst

`decken.py` hält `docs/Rahmenplan.md`, `Rahmenplan-Verlauf.md`, `Backlog.md`
und `Backlog-Erledigt.md` an **20 Decken** (Konzept SD 5): Zeilen je Datei und
Kopf, Zeichen je Tabellenzeile, Statusvokabular, Berichtigungsmuster,
Blockquotes, 20 Zeilen und fünf Leerzeichen je Backlog-Eintrag, Nummern in
beiden Dateien, Rendering mit `cmark-gfm`. `uebersicht.py` hält jede Kopfzeile
des Backlogs an die Grammatik (Konzept SD 4.6) und ihr Ziel an die Fahrplan-
Tabelle und gibt die offenen Punkte nach Ziel aus — der Ersatz für den alten
Rahmenplan-Abschnitt 5, erzeugt statt gepflegt.

## Was es braucht

`python3` und `cmark-gfm` (Ausbaustufe web). Rückgabe 0 = gehalten,
1 = gerissen, 2 = nicht gelaufen (Datei oder `cmark-gfm` fehlt) — nie still grün.

## Erwartete Zahl

`decken.py`: **20 Decken, 0 gerissen**; `--selbstprobe` **22 Fälle, 0 Fehlschläge**.
`uebersicht.py --pruefen`: **0 ohne Grammatik, 0 ohne Ziel** (105 Einträge,
26.09.2026); `--selbstprobe` **7 Fälle, 0 Fehlschläge**. Reißt eine Decke: kürzen —
oder sie in `decken.py` ändern und es in der Verlaufszeile begründen (E-SD-27).

## Was es nicht kann

Sinn lesen: Ob ein Statussatz stimmt oder eine Kürzung etwas verschweigt,
sieht nur ein Mensch. Eine Nummer zweimal in EINER Datei misst `bestand`
(Regel `backlog`); was GitHub aus der Liste macht (Nr. 196), `cmark-gfm` nicht.
