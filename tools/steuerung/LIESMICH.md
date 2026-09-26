# Steuerung

Halten Rahmenplan und Backlog ihre Decken, trägt jeder offene Punkt ein Ziel — und legt kein Zweig eine Nummer an, die ein anderer schon trägt?
**Anlass: Nr. 177, 196, 199, 339** — Konzept SD 1.1 (121 Berichtigungssätze, 96 Zeilen gegen 113 offene Punkte); F-BV-14 und -18 (zwei Nummernkollisionen an einem Tag).

## Aufruf

```bash
python3 tools/steuerung/decken.py        # --stellen · --selbstprobe
python3 tools/steuerung/uebersicht.py    # --ziel 17 · --pruefen · --selbstprobe
python3 tools/steuerung/nummern.py       # --ohne-holen · --selbstprobe
python3 tools/steuerung/verschieben.py NR "Erledigt TT.MM.JJJJ mit …: Beleg."  # --trocken
```

## Was es misst

`decken.py` hält Rahmenplan, Verlauf und beide Backlog-Dateien an **20 Decken**
(Konzept SD 5): Zeilen, Zeichen, Vokabular, Form, Nummern, Rendering (`cmark-gfm`).
`uebersicht.py` hält jede Kopfzeile an Grammatik und Fahrplan-Ziel und gibt
die offenen Punkte nach Ziel aus. `nummern.py` holt die Remote-Zweige und ist rot,
wenn der Arbeitsbaum eine Nummer neu anlegt, die `origin/main` oder ein anderer
Zweig auch neu anlegt (je gegen den Vorfahren mit `main`, beide Dateien).
`verschieben.py` misst nichts: Es verschiebt einen erledigten Punkt (E-R4-06).

## Was es braucht

`python3`, `cmark-gfm` (Ausbaustufe web), für `nummern.py` Git und Netz zu `origin`.
Rückgabe 0 = gehalten, 1 = gerissen, 2 = nicht gelaufen — nie still grün.

## Erwartete Zahl

`decken.py`: **20 Decken, 0 gerissen**, Selbstprobe **22 / 0**. `uebersicht.py
--pruefen`: **0 ohne Grammatik, 0 ohne Ziel**, Selbstprobe **7 / 0**.
`nummern.py`: **0 Überschneidungen**, Selbstprobe **8 / 0**. Reißt eine Decke:
kürzen — oder sie in `decken.py` ändern und begründen (E-SD-27).

## Was es nicht kann

Sinn lesen: Ob ein Statussatz stimmt, sieht nur ein Mensch. `nummern.py` sieht nur
gepushte Zweige, keine Forks; die Nummer, die GitHub zeigt (Nr. 340), keiner.
