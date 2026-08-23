# Matrix-Abgleich — welcher Einsatz belegt welche Zeile

**Diese Datei wird erzeugt, nicht gepflegt.** Sie entsteht aus
`pruefen.py --matrix` und damit aus denselben Marken, gegen die das
Prüfskript prüft. Wer sie von Hand ändert, verliert die Änderung beim
nächsten Lauf — und das ist der Zweck: Ein handgeführtes
Abgleichsdokument ist nach der zweiten Änderung an den Quelldaten
falsch und behauptet trotzdem weiter eine Abdeckung, die es nicht
mehr gibt.

Grundlage ist die Abdeckungsmatrix aus Abschnitt 5 des Konzepts
*P1 — Referenzdatensatz und Demo-Account*.

## Umfang

| Größe | Wert |
|---|---|
| Dienste | 14 |
| Einsätze | 39 |
| Matrixzeilen | 78 |
| Zeitstempel auf Existenz und Eindeutigkeit geprüft | 523 |
| Einzelprüfungen im Lauf | 2698 |

## Zuordnung

„Strukturell" heißt: Die Zeile wird nicht über eine Marke belegt,
sondern über den Bestand selbst geprüft — etwa ob wirklich alle zehn
Reanimationsarten vorkommen.

| Dimension | Anforderung | Belegt durch |
|---|---|---|
| Erfassungsart (R4) | luftgebunden mit Track (Ingest) | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+23) |
|  | bodengebunden mit Track (Ingest) | `D05/m-12-7092451863`, `D05/m-12-4386015927`, `D10/m-12-9053871426`, `D10/m-12-1687240539` … (+2) |
|  | nachträglich ohne Track | `D07/IMP-01`, `D07/IMP-02`, `D09/IMP-03`, `D10/MAN-01` … (+2) |
| Herkunft | watch | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+29) |
|  | manual | `D10/MAN-01`, `D14/MAN-02` |
|  | import | `D07/IMP-01`, `D07/IMP-02`, `D09/IMP-03`, `D13/IMP-04` |
| Diensttage | Luftdienst | `D01`, `D02`, `D03`, `D04` … (+7) |
|  | Bodendienst | `D05`, `D10`, `D12` |
|  | Kalendertag mit zwei Diensten | `D04`, `D05` |
|  | Dienst über Mitternacht | `D05`, `D12` |
|  | Einsatzdatum ≠ Diensttag | `D05/m-12-7092451863`, `D05/m-12-4386015927`, `D12/m-12-8845270913`, `D12/m-12-5106938274` |
|  | Diensttag ohne Einsatz | `D11` |
|  | Tagesnotizen | `D01`, `D02`, `D03`, `D05` … (+8) |
| Besatzung | alle Rollen des Katalogs belegt | *strukturell geprüft* |
|  | abweichende Besatzung (crew_override) | `D04/m-11-3391648207`, `D14/MAN-02` |
| Phasen | alle Phasen 2–9 im Datensatz | *strukturell geprüft* |
|  | Mehrfacheintrag derselben Phase | `D04/m-11-5027369184` |
|  | unvollständige Phasen | `D02/m-11-6640281937`, `D02/m-11-1287405639`, `D06/m-11-7148036592`, `D07/IMP-02` … (+4) |
|  | nicht abgeschlossener Einsatz | `D08/m-11-8207364159` |
| Reanimation | Einsatz mit einer Sitzung | `D01/m-11-5192834077`, `D05/m-12-4386015927`, `D10/MAN-01` |
|  | Einsatz mit mehreren Sitzungen | `D08/m-11-7761204385` |
|  | alle zehn Ereignisarten | *strukturell geprüft* |
| Transport | Transportart air | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-2418095733`, `D03/m-11-4462903718` … (+18) |
|  | Transportart ground | `D01/m-11-7734018625`, `D05/m-12-7092451863`, `D09/IMP-03`, `D10/m-12-9053871426` … (+3) |
|  | Transportart ambulant | `D02/m-11-1287405639`, `D06/m-11-7148036592`, `D07/IMP-02`, `D10/m-12-1687240539` … (+1) |
|  | Transportart leer | `D02/m-11-6640281937`, `D05/m-12-4386015927`, `D13/IMP-04` |
|  | NA-Begleitung | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+24) |
|  | Fehleinsatz / Storno | `D02/m-11-6640281937`, `D13/IMP-04` |
|  | Sekundärtransport | `D03/m-11-2275860419`, `D06/m-11-2236709481` |
|  | Schockraum | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-2418095733`, `D04/m-11-8836271059` … (+9) |
|  | Zielklinik mit Koordinate | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-2418095733`, `D03/m-11-4462903718` … (+21) |
|  | Zielklinik ohne Koordinate | `D01/m-11-7734018625`, `D08/m-11-4083572619`, `D09/IMP-03`, `D12/m-12-8845270913` |
| Abfahrtort | Regel base | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D02/m-11-6640281937`, `D02/m-11-1287405639` … (+22) |
|  | Regel prev_site | `D06/m-11-5573920184` |
|  | Regel prev_dest | `D01/m-11-7734018625`, `D12/m-12-5106938274` |
|  | Regel manual (verschlüsselter pat.start) | `D01/m-11-2418095733`, `D05/m-12-4386015927`, `D14/MAN-02` |
| Luftspezifik | Winde mit Cycles | `D01/m-11-2418095733`, `D04/m-11-3391648207`, `D06/m-11-8804157236`, `D08/m-11-5648017293` … (+1) |
|  | Cycles mit Patient | `D01/m-11-2418095733`, `D04/m-11-3391648207`, `D06/m-11-8804157236`, `D13/m-11-4470962381` |
|  | Luftverladung | `D01/m-11-2418095733`, `D06/m-11-8804157236` |
|  | Bergwacht mit Einheit und bw_info | `D01/m-11-3067419528`, `D01/m-11-2418095733`, `D02/m-11-1287405639`, `D04/m-11-3391648207` … (+7) |
| Geschützte Angaben | Geburtsdatum (Alter gerechnet) | `D01/m-11-3067419528`, `D01/m-11-7734018625`, `D01/m-11-2418095733`, `D03/m-11-4462903718` … (+14) |
|  | Handalter (pat_alter) | `D01/m-11-5192834077`, `D02/m-11-1287405639`, `D03/m-11-2275860419`, `D04/m-11-5027369184` … (+13) |
|  | R20-Angriffswert im Altersfeld | `D13/m-11-6127408395` |
|  | Diagnose | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+31) |
|  | Einsatzort mit Adresse und Koordinate | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+33) |
|  | Ortsbeschreibung | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+31) |
|  | Einsatznummer | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+33) |
|  | Einsatz ohne jede geschützte Angabe | `D02/m-11-9518376204`, `D08/m-11-8207364159` |
| Sonderzeichen | Semikolon | `D01/m-11-3067419528`, `D01/m-11-7734018625`, `D01/m-11-2418095733`, `D02/m-11-1287405639` … (+22) |
|  | Anführungszeichen | `D02/m-11-1287405639`, `D04/m-11-5027369184`, `D07/IMP-01`, `D07/IMP-02` … (+3) |
|  | Zeilenumbruch | `D02/m-11-6640281937`, `D05/m-12-4386015927`, `D10/MAN-01` |
|  | Formel-Anfangszeichen = | `D07/IMP-01`, `D14/MAN-02` |
|  | Formel-Anfangszeichen + | `D07/IMP-02`, `D13/IMP-04` |
|  | Formel-Anfangszeichen - | `D07/m-11-9925471083`, `D14/m-11-6693481027` |
|  | Formel-Anfangszeichen @ | `D07/IMP-02` |
|  | Umlaute und ß | `D01/m-11-3067419528`, `D01/m-11-7734018625`, `D01/m-11-2418095733`, `D02/m-11-6640281937` … (+30) |
| Ruhezeiten | Segmente mit Track | `D01`, `D02`, `D03`, `D04` … (+10) |
|  | mehrere Segmente je Dienst | `D01`, `D02`, `D03`, `D04` … (+10) |
|  | nicht abgeschlossenes Segment | `D14` |
| Papierkorb | gelöschter Einsatz (einzeln) | `D08/m-11-2914638507` |
|  | gelöschter Diensttag | `D03` |
|  | Einsätze mit deleted_with_day | `D03/m-11-4462903718`, `D03/m-11-2275860419` |
|  | Sperrlisten-Fall als Ablaufschritt | `PS-01` |
| Stammdaten | ≥ 2 Standorte, einer ohne Koordinaten | *strukturell geprüft* |
|  | ≥ 2 Luft-Rettungsmittel mit/ohne Fähigkeiten | *strukturell geprüft* |
|  | ≥ 1 Boden-Rettungsmittel | *strukturell geprüft* |
|  | Zielkliniken mit und ohne Koordinate | *strukturell geprüft* |
|  | Vorbelegungen aller Arten | *strukturell geprüft* |
|  | Standard-Markierungen | *strukturell geprüft* |
| Zeit | Einsätze in MEZ | `D01`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+23) |
|  | Einsätze in MESZ | `D06`, `D06/m-11-7148036592`, `D06/m-11-5573920184`, `D06/m-11-8804157236` … (+22) |
|  | Dienst um die Umstellung im Frühjahr | `D05`, `D05/m-12-4386015927` |
|  | Dienst um die Umstellung im Herbst | `D12`, `D12/m-12-5106938274` |
| Weitere Felder | mehrere weitere Rettungsmittel je Einsatz | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D04/m-11-8836271059`, `D05/m-12-4386015927` … (+9) |
|  | weiterer Notarzt | `D01/m-11-5192834077`, `D06/m-11-2236709481`, `D07/IMP-02`, `D10/MAN-01` |
|  | Notizen am Einsatz | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+33) |
|  | bearbeiteter Uhr-Einsatz (edited=1) | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+27) |
|  | unbearbeiteter Uhr-Einsatz (edited=0) | `D02/m-11-9518376204`, `D08/m-11-8207364159` |
