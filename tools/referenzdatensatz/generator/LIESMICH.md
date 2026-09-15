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
| `fusswege.json` | je **Fußweg** Strecke, Dauer und Gehgeschwindigkeit — `pruefen.py` misst gegen diese Datei und nicht gegen die Spur |
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
| `spur.py` | Spurerzeugung: `flug` geometrisch, `fahrt` auf Straßengeometrie, `fussweg` am Boden entlang; Ausdünnung wie auf der Uhr |
| `gelaende.py` | Höhenmodell aus rund fünfzig Stützpunkten |
| `krypto.py` | PBKDF2, HKDF und AES-256-GCM nach `server/assets/crypto.js` — beide Hüllenfassungen (`edk1:` und `edka1:<kennung>:`, S10) |
| `routen/` | Straßengeometrie und Fahrzeiten-Tafel (einmaliger Abruf, eingecheckt) |
| `pruefen.py` | prüft die Erzeugnisse |

## Drei Fortbewegungsarten, nicht zwei

Bis zum Demo-Ausbau kannte der Generator **fliegen** (geometrisch, mit
Reiseflughöhe) und **fahren** (auf einer Straße aus OSRM). Seit E-DA-13 gibt
es die dritte: **gehen**.

Ein Bergwachtnotarzt fährt bis zum Parkplatz, zur Talstation oder zur
Hüttenzufahrt — in den Quelldaten der Wegpunkt **`zustieg`** — und geht von
dort zum Patienten. Als Flug gezeichnet ergäbe das eine schnurgerade Linie
über den Hang mit Reiseflughöhe darüber; als Fahrt gezeichnet bräuchte es
eine Straße, die dort nicht liegt.

`spur.fussweg()` unterscheidet sich in drei Punkten von `spur.flug()`, und
jeder hat einen Grund:

1. **Keine Reiseflughöhe** — die Höhe ist die des Geländes plus anderthalb
   Meter. Daran hängt `site_ele_m`: Die Anwendung rechnet die Höhe des
   Einsatzorts aus der Spur, und ein Fußweg, der 200 m über dem Hang
   schwebt, machte aus einer Almwiese einen Gipfel.
2. **Mehr Streuung** — sechs Meter statt drei. Unter Fels und Baumkronen ist
   der Empfang schlechter als in der Luft oder auf der Straße.
3. **Ein Weg, der sich windet** — der Bogen ist stärker als beim Flug. Ein
   Steig geht Serpentinen, keine Gerade.

**Welches Teilstück gegangen wird, steht nicht hier**, sondern in
`quelldaten/wegpunkte.py` (`ist_fussweg`): die Paare `zustieg → ort` und
`ort → zustieg`. Drei lesen diese eine Frage — der Generator zeichnet
danach, der Routenabruf holt für einen Fußweg **keine** Straße, und die
Prüfung misst die Gehgeschwindigkeit. Zwei Fassungen davon wären eine
Straße, die niemand benutzt, oder ein Fußweg mit Straßengeometrie.

**Die Gehgeschwindigkeit wird nicht gesetzt, sie ergibt sich.** Das
Zeitfenster kommt aus den Phasen, die Strecke aus den Koordinaten; ob dabei
etwas Plausibles herauskommt, misst `pruefen.py` gegen `fusswege.json` und
meldet es als Befund, nicht als Nebensatz.

## Wer welches Zeitfenster bekommt

Mit dem `zustieg` hat ein Einsatz **vier** Teilstücke — hinfahren, hingehen,
zurückgehen, wegfahren — und dafür gibt es keine vier Phasen: Zwischen
Transportbeginn (6) und Ankunft Klinik (7) liegt **eine** Spanne, in der
zweierlei passiert.

Die Zuteilung steht in `quelldaten/wegpunkte.py` (`fenster()`), und zwar
**einmal**. Bis zum Demo-Ausbau rechnete der Generator seine Fenster selbst
und das Prüfskript dieselbe Ableitung noch einmal; der Kommentar dort
begründete das mit „die Regel selbst ist kurz". Sie war es. Mit vier
Teilstücken ist sie es nicht mehr, und zwei Fassungen hießen, dass das
Prüfskript die Erreichbarkeit eines **anderen** Ablaufs misst als den, den
der Generator zeichnet — und dass beide dabei Erfolg melden.

Solange eine Route nicht mehr Teilstücke hat als es Phasenfenster gibt (alle
Routen bis zum Demo-Ausbau), gilt der alte Weg unverändert. Das ist keine
Höflichkeit gegenüber dem Bestand, sondern die Bedingung dafür, dass die 16
alten Diensttage byteweise dieselben Spuren behalten — gemessen mit
`diff -r` über den ganzen Ausgabeordner.

## Der Server-Anteil in `krypto.py` (S10)

Seit S10 ist die PBKDF2-Hälfte nicht mehr selbst der Datenschlüssel. Trägt
eine Schlüsselhülle das Präfix `edka1:<kennung>:`, kommt er aus
`HKDF-SHA256(Hälfte, Konto-Anteil)`; trägt sie `edk1:`, bleibt es die Hälfte.
Entschieden wird **am Präfix der Hülle**, nicht am Zustand der Installation —
während einer Rotation gilt beides gleichzeitig, aber je für einen Teil der
Konten.

`hkdf_sha256()` ist ausgeschrieben und nicht aus einer Bibliothek geholt: vier
Zeilen gegen einen weiteren Fremdbestandteil (E-S10-15). Der Preis ist, dass
niemand sie für uns prüft — **`pruefen.py` rechnet deshalb bei jedem Lauf den
Prüffall 1 aus RFC 5869 nach**, dazu einen Hüllenrundlauf in beide Fassungen
und den Fall, der laut sein muss: eine Kennung, zu der kein Anteil vorliegt.
Ein Rückfall auf die Hälfte ergäbe dort einen Schlüssel, der nicht passt, und
der Fehlschlag sähe aus wie ein falsch getipptes Passwort.

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
JSON-Vertrags. Die Zahl steht im Prüfdokument des jeweiligen Pakets; sie
wächst mit dem Bestand. Dazu:

- **Folge der Teilstücke** — `seq_from` lückenlos und ohne Überlappung
- **Krypto-Rundlauf** — jeder Chiffretext entschlüsselt zum Quell-Klartext
- **Tempo und Höhe** je Spur, für Einsätze **und** Ruhe-Segmente
- **Gehgeschwindigkeit** je Fußweg gegen 1,5–6,0 km/h, gemessen an
  `fusswege.json` — der fertigen Spur sieht niemand mehr an, welches
  Teilstück gegangen wurde: Die Punkte liegen dicht, und ein Fußweg sieht
  dort aus wie ein Stau auf der Landstraße

Die letzte Prüfung gäbe es ohne einen Befund nicht: Der erste Generator
erzeugte Flüge mit 380 km/h und ein NEF auf 2 100 m Höhe. Auffällig war das
in keiner Einzelprüfung — jeder Punkt lag im gültigen Bereich, jede Anfrage
hielt den Vertrag ein. Sichtbar wurde es erst, als jemand die Strecke durch
die Zeit teilte.
