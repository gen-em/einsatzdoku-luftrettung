# Steuerung

Halten Rahmenplan und Backlog ihre Decken — und trägt jeder offene Punkt ein Ziel?
**Anlass: Nr. 177, 196, 199** — Konzept SD 1.1: 121 Berichtigungssätze im Rahmenplan, Abschnitt 5 mit 96 Zeilen gegen 113 offene Punkte.

## Aufruf

```bash
python3 tools/steuerung/decken.py        # --stellen · --selbstprobe
python3 tools/steuerung/uebersicht.py    # --ziel 17 · --pruefen · --selbstprobe
python3 tools/steuerung/verschieben.py NR "Erledigt TT.MM.JJJJ mit …: Beleg."  # --trocken
```

## Was es misst

`decken.py` hält `docs/Rahmenplan.md`, `Rahmenplan-Verlauf.md`, `Backlog.md`
und `Backlog-Erledigt.md` an **20 Decken** (Konzept SD 5): Zeilen, Zeichen je
Tabellenzeile, Statusvokabular, Berichtigungsmuster, Blockquotes, Form der
Backlog-Einträge, Nummern in beiden Dateien, Rendering mit `cmark-gfm`.
`uebersicht.py` hält jede Kopfzeile an die Grammatik (Konzept SD 4.6) und ihr
Ziel an die Fahrplan-Tabelle und gibt die offenen Punkte nach Ziel aus.
`verschieben.py` misst nichts: Es trägt einen erledigten Punkt in der Form
von E-R4-06 nach `Backlog-Erledigt.md` (Kopfzeile bleibt, `Stand: erledigt`,
Schlusssatz als letzte Folgezeile).

## Was es braucht

`python3` und `cmark-gfm` (Ausbaustufe web). Rückgabe 0 = gehalten,
1 = gerissen, 2 = nicht gelaufen (Datei oder `cmark-gfm` fehlt) — nie still grün.

## Erwartete Zahl

`decken.py`: **20 Decken, 0 gerissen**; `--selbstprobe` **22 Fälle, 0 Fehlschläge**.
`uebersicht.py --pruefen`: **0 ohne Grammatik, 0 ohne Ziel**; `--selbstprobe` **7 / 0**.
Reißt eine Decke: kürzen — oder sie in `decken.py` ändern und begründen (E-SD-27).

## Was es nicht kann

Sinn lesen: Ob ein Statussatz stimmt, sieht nur ein Mensch. Doppelte Nummern
misst `bestand` (`backlog`); die Nummer, die GitHub zeigt (Nr. 340), keiner.
