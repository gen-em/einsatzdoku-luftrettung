# Generator des Referenzdatensatzes

Macht aus den Quelldaten (`../quelldaten/`) alles, was der Einspiellauf
braucht. **Deterministisch:** Zweimal ausgeführt entsteht dasselbe, Byte für
Byte.

```
python3 erzeugen.py     # erzeugen
python3 pruefen.py      # prüfen (Vertrag, Folge, Krypto, Spuren)
```

## Was entsteht — `ausgabe/`

| Ordner / Datei | Inhalt |
|---|---|
| `payloads/D01/0001.json` … | je eine Ingest-Anfrage, in der Reihenfolge, in der die Uhr sie senden würde |
| `sendeplan.json` | wann welche Anfrage fällig ist — Grundlage des Messprotokolls (E-P1-14 / R19) |
| `formular/` | Daten für das Nachtragen über `einsatz_form.php`, im **Klartext** |
| `import/einsaetze.csv` | Importdatei im Format `export_csv_v1` |
| `gpx/` | Sichtprüfformat, abgeleitet (E-P1-04) |
| `kennzahlen.json` | Umfang in Zahlen |

**`ausgabe/` steht in `.gitignore`.** Der Ordner ist rund 25 MB groß und
vollständig aus den Quelldaten ableitbar; ein Lauf dauert zwei Sekunden.
Eingecheckt ist stattdessen, was **nicht** ableitbar ist: die
Straßengeometrie unter `routen/` (sie kommt von einem fremden Dienst) und
die Quelldaten selbst.

**Die Chiffretexte entstehen hier nicht.** `formular/` führt Klartext; das
Verschlüsseln übernimmt das Einspielskript mit dem Schlüssel des Kontos
(`krypto.py`). Anders ginge es nicht — den Inhaltsschlüssel gibt es erst,
wenn das Konto existiert.

## Die Bausteine

| Datei | Aufgabe |
|---|---|
| `erzeugen.py` | Hauptlauf: Spuren, Payloads, Sendeplan, Formulardaten, CSV, GPX |
| `spur.py` | Spurerzeugung; Ausdünnung wie auf der Uhr |
| `gelaende.py` | Höhenmodell aus rund fünfzig Stützpunkten |
| `krypto.py` | PBKDF2 und AES-256-GCM nach `server/assets/crypto.js` |
| `routen/` | Straßengeometrie und Fahrzeiten-Tafel (einmaliger Abruf, eingecheckt) |
| `pruefen.py` | prüft die Erzeugnisse |

## Drei Entscheidungen, die man sehen muss

**Der Rückweg gehört nicht zum Einsatz.** Die Uhr beendet den Einsatz und
beginnt sofort ein Ruhe-Segment (`Model.mc`, `_endMission` →
`_startRestSegment`). Der Weg von der Klinik zurück wird deshalb *dort*
aufgezeichnet. Solange der Generator ihn zum Einsatz zählte, musste er in
die Spanne zwischen Übergabe und Endzeit passen — und dabei entstanden
Rückflüge mit 666 km/h.

**Die Phasen sind die Wahrheit über den Ablauf, nicht die Spur.** Der Track
wird an sie gebunden: Phase 3 → 4 ist der Weg zum Einsatzort, 6 → 7 der
Transport. Umgekehrt richtet sich in den Quelldaten der *Ort* nach der Zeit,
die die Phasen dafür vorsehen — auf der Straße nach der echten Fahrzeit aus
`routen/fahrzeiten.json`, nicht nach der Luftlinie. Im Voralpenland liegt
ein Ort 15 km Luftlinie und 40 km Fahrstrecke entfernt, weil das Tal in die
andere Richtung geht.

**Gröber als die Uhr, und zwar absichtlich.** Die Ausdünnungsregel ist die
der Uhr (≥ 15 m **oder** ≥ 10 s, nie öfter als 1/s, `Const.THIN_*`), aber
abgetastet wird alle 3 s (Luft) beziehungsweise 5 s (Boden), ein Halt alle
30 s, ein Ruhe-Segment alle 60 s. Sekundengenau trüge der Datensatz rund
160 000 Spurpunkte; die Fixture unter `server/demo/` wird bei **jedem**
Deploy per FTP hochgeladen. So sind es rund 57 000. Was das nicht kostet:
Die Teilstückbildung des Uploads bleibt dieselbe — 166 Pakete gehen in
mehreren Anfragen hinaus, 18 davon an der 500-Punkte-Grenze.

## Was `pruefen.py` misst

Kein Stichprobenverfahren: **jede** Anfrage gegen jede Grenze des
JSON-Vertrags. Zuletzt 283 990 Einzelprüfungen ohne Befund über 526
Anfragen und 56 587 Trackpunkte. Dazu:

- **Folge der Teilstücke** — `seq_from` lückenlos und ohne Überlappung
- **Krypto-Rundlauf** — 81 Chiffretexte entschlüsseln zum Quell-Klartext
- **Tempo und Höhe** je Spur, für Einsätze **und** Ruhe-Segmente

Die letzte Prüfung gäbe es ohne einen Befund nicht: Der erste Generator
erzeugte Flüge mit 380 km/h und ein NEF auf 2 100 m Höhe. Auffällig war das
in keiner Einzelprüfung — jeder Punkt lag im gültigen Bereich, jede Anfrage
hielt den Vertrag ein. Sichtbar wurde es erst, als jemand die Strecke durch
die Zeit teilte.
