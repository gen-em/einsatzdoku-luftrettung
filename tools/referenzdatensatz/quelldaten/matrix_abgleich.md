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
| Dienste | 16 |
| Einsätze | 87 |
| Matrixzeilen | 78 |
| Zeitstempel auf Existenz und Eindeutigkeit geprüft | 1122 |
| Einzelprüfungen im Lauf | 5528 |

## Zuordnung

„Strukturell" heißt: Die Zeile wird nicht über eine Marke belegt,
sondern über den Bestand selbst geprüft — etwa ob wirklich alle zehn
Reanimationsarten vorkommen.

| Dimension | Anforderung | Belegt durch |
|---|---|---|
| Erfassungsart (R4) | luftgebunden mit Track (Ingest) | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+35) |
|  | bodengebunden mit Track (Ingest) | `D04/m-12-2458452183`, `D04/m-12-7793367508`, `D04/m-12-7691667355`, `D04/m-12-1194337097` … (+38) |
|  | nachträglich ohne Track | `D11/MAN-01`, `D13/IMP-03`, `D15/IMP-01`, `D15/IMP-02` … (+2) |
| Herkunft | watch | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+77) |
|  | manual | `D11/MAN-01`, `D16/MAN-02` |
|  | import | `D13/IMP-03`, `D15/IMP-01`, `D15/IMP-02`, `D15/IMP-04` |
| Diensttage | Luftdienst | `D01`, `D02`, `D03`, `D05` … (+4) |
|  | Bodendienst | `D04`, `D06`, `D08`, `D10` … (+4) |
|  | Kalendertag mit zwei Diensten | `D05`, `D06` |
|  | Dienst über Mitternacht | `D06`, `D14` |
|  | Einsatzdatum ≠ Diensttag | `D06/m-12-7092451863`, `D06/m-12-4386015927`, `D14/m-12-8845270913`, `D14/m-12-5106938274` … (+1) |
|  | Diensttag ohne Einsatz | `D12` |
|  | Tagesnotizen | `D01`, `D02`, `D03`, `D04` … (+11) |
| Besatzung | alle Rollen des Katalogs belegt | *strukturell geprüft* |
|  | abweichende Besatzung (crew_override) | `D05/m-11-3391648207`, `D16/MAN-02` |
| Phasen | alle Phasen 2–9 im Datensatz | *strukturell geprüft* |
|  | Mehrfacheintrag derselben Phase | `D05/m-11-5027369184` |
|  | unvollständige Phasen | `D02/m-11-6640281937`, `D02/m-11-1287405639`, `D04/m-12-3360383339`, `D04/m-12-7024419717` … (+12) |
|  | nicht abgeschlossener Einsatz | `D09/m-11-8207364159` |
| Reanimation | Einsatz mit einer Sitzung | `D01/m-11-5192834077`, `D04/m-12-7691667355`, `D06/m-12-4386015927`, `D08/m-12-3842923791` … (+3) |
|  | Einsatz mit mehreren Sitzungen | `D09/m-11-7761204385` |
|  | alle zehn Ereignisarten | *strukturell geprüft* |
| Transport | Transportart air | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-2418095733` … (+29) |
|  | Transportart ground | `D01/m-11-7734018625`, `D04/m-12-2458452183`, `D04/m-12-7793367508`, `D04/m-12-7691667355` … (+32) |
|  | Transportart ambulant | `D02/m-11-1287405639`, `D04/m-12-3360383339`, `D04/m-12-7024419717`, `D07/m-11-7148036592` … (+9) |
|  | Transportart leer | `D02/m-11-6640281937`, `D06/m-12-4386015927`, `D15/IMP-04` |
|  | NA-Begleitung | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+64) |
|  | Fehleinsatz / Storno | `D02/m-11-6640281937`, `D15/IMP-04` |
|  | Sekundärtransport | `D02/m-11-8336537404`, `D03/m-11-2275860419`, `D07/m-11-2236709481` |
|  | Schockraum | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-2418095733`, `D02/m-11-4546535431` … (+22) |
|  | Zielklinik mit Koordinate | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-2418095733` … (+61) |
|  | Zielklinik ohne Koordinate | `D01/m-11-7734018625`, `D09/m-11-4083572619`, `D13/IMP-03`, `D14/m-12-8845270913` |
| Abfahrtort | Regel base | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-8624759753` … (+33) |
|  | Regel prev_site | `D07/m-11-5573920184` |
|  | Regel prev_dest | `D01/m-11-7734018625`, `D14/m-12-5106938274` |
|  | Regel manual (verschlüsselter pat.start) | `D01/m-11-2418095733`, `D06/m-12-4386015927`, `D16/MAN-02` |
| Luftspezifik | Winde mit Cycles | `D01/m-11-2418095733`, `D02/m-11-3845141782`, `D05/m-11-3391648207`, `D07/m-11-8804157236` … (+2) |
|  | Cycles mit Patient | `D01/m-11-2418095733`, `D02/m-11-3845141782`, `D05/m-11-3391648207`, `D07/m-11-8804157236` |
|  | Luftverladung | `D01/m-11-2418095733`, `D07/m-11-8804157236` |
|  | Bergwacht mit Einheit und bw_info | `D01/m-11-3067419528`, `D01/m-11-2418095733`, `D02/m-11-1287405639`, `D05/m-11-3391648207` … (+3) |
| Geschützte Angaben | Geburtsdatum (Alter gerechnet) | `D01/m-11-3067419528`, `D01/m-11-7734018625`, `D01/m-11-2418095733`, `D02/m-11-3845141782` … (+39) |
|  | Handalter (pat_alter) | `D01/m-11-1456345856`, `D01/m-11-5192834077`, `D01/m-11-8624759753`, `D02/m-11-8336537404` … (+36) |
|  | R20-Angriffswert im Altersfeld | `D15/m-11-6127408395` |
|  | Diagnose | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+79) |
|  | Einsatzort mit Adresse und Koordinate | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+81) |
|  | Ortsbeschreibung | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+79) |
|  | Einsatznummer | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+81) |
|  | Einsatz ohne jede geschützte Angabe | `D02/m-11-9518376204`, `D09/m-11-8207364159` |
| Sonderzeichen | Semikolon | `D01/m-11-3067419528`, `D01/m-11-7734018625`, `D01/m-11-2418095733`, `D02/m-11-1287405639` … (+18) |
|  | Anführungszeichen | `D02/m-11-1287405639`, `D05/m-11-5027369184`, `D14/m-12-5106938274`, `D15/m-11-6127408395` … (+3) |
|  | Zeilenumbruch | `D02/m-11-6640281937`, `D06/m-12-4386015927`, `D11/MAN-01`, `D16/MAN-02` |
|  | Formel-Anfangszeichen = | `D15/IMP-01`, `D16/MAN-02` |
|  | Formel-Anfangszeichen + | `D15/IMP-02`, `D15/IMP-04` |
|  | Formel-Anfangszeichen - | `D16/MAN-02` |
|  | Formel-Anfangszeichen @ | `D15/IMP-02` |
|  | Umlaute und ß | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+72) |
| Ruhezeiten | Segmente mit Track | `D01`, `D02`, `D03`, `D04` … (+12) |
|  | mehrere Segmente je Dienst | `D01`, `D02`, `D03`, `D04` … (+11) |
|  | nicht abgeschlossenes Segment | `D16` |
| Papierkorb | gelöschter Einsatz (einzeln) | `D09/m-11-2914638507` |
|  | gelöschter Diensttag | `D03` |
|  | Einsätze mit deleted_with_day | `D03/m-11-4462903718`, `D03/m-11-2275860419`, `D03/m-11-9013159356`, `D03/m-11-1451009129` |
|  | Sperrlisten-Fall als Ablaufschritt | `PS-01` |
| Stammdaten | ≥ 2 Standorte, einer ohne Koordinaten | *strukturell geprüft* |
|  | ≥ 2 Luft-Rettungsmittel mit/ohne Fähigkeiten | *strukturell geprüft* |
|  | ≥ 1 Boden-Rettungsmittel | *strukturell geprüft* |
|  | Zielkliniken mit und ohne Koordinate | *strukturell geprüft* |
|  | Vorbelegungen aller Arten | *strukturell geprüft* |
|  | Standard-Markierungen | *strukturell geprüft* |
| Zeit | Einsätze in MEZ | `D01`, `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077` … (+49) |
|  | Einsätze in MESZ | `D07`, `D07/m-11-3639672828`, `D07/m-11-7148036592`, `D07/m-11-5573920184` … (+46) |
|  | Dienst um die Umstellung im Frühjahr | `D06`, `D06/m-12-4386015927` |
|  | Dienst um die Umstellung im Herbst | `D14`, `D14/m-12-5106938274` |
| Weitere Felder | mehrere weitere Rettungsmittel je Einsatz | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-8624759753` … (+29) |
|  | weiterer Notarzt | `D01/m-11-5192834077`, `D07/m-11-2236709481`, `D11/MAN-01`, `D15/IMP-02` … (+1) |
|  | Notizen am Einsatz | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+64) |
|  | bearbeiteter Uhr-Einsatz (edited=1) | `D01/m-11-1456345856`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+75) |
|  | unbearbeiteter Uhr-Einsatz (edited=0) | `D02/m-11-9518376204`, `D09/m-11-8207364159` |
