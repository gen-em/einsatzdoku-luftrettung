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
| Dienste | 21 |
| Einsätze | 103 |
| Matrixzeilen | 97 |
| Zeitstempel auf Existenz und Eindeutigkeit geprüft | 1335 |
| Einzelprüfungen im Lauf | 7042 |

## Zuordnung

„Strukturell" heißt: Die Zeile wird nicht über eine Marke belegt,
sondern über den Bestand selbst geprüft — etwa ob wirklich alle zehn
Reanimationsarten vorkommen.

| Dimension | Anforderung | Belegt durch |
|---|---|---|
| Erfassungsart (R4) | luftgebunden mit Track (Ingest) | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+35) |
|  | bodengebunden mit Track (Ingest) | `D04/am-12-2458452183`, `D04/am-12-6467858312`, `D04/am-12-1466537302`, `D04/am-12-7129806272` … (+49) |
|  | nachträglich ohne Track | `D11/MAN-01`, `D13/IMP-03`, `D15/IMP-01`, `D15/IMP-02` … (+7) |
|  | ohne Track, mit Ort- und Zielkoordinate (Luftlinie) | `D11/MAN-01`, `D15/IMP-01`, `D17/MAN-03`, `D21/MAN-04` … (+1) |
| Herkunft | watch | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+88) |
|  | android | `D06/am-12-4386015927`, `D14/am-12-8845270913`, `D14/am-12-5106938274` |
|  | wear | `D06/wm-12-7092451863`, `D11/wm-12-9053871426`, `D11/wm-12-1687240539`, `D19/wm-12-4517809362` |
|  | manual | `D11/MAN-01`, `D16/MAN-02`, `D17/MAN-03`, `D21/MAN-04` … (+3) |
|  | import | `D13/IMP-03`, `D15/IMP-01`, `D15/IMP-02`, `D15/IMP-04` |
|  | schnitt | `D08/schnitt ar-12-1288367401`, `D18/schnitt ar-12-7539973209`, `D19/schnitt ar-12-1109381048` |
|  | Schnitte an mehr als einem Diensttag | *strukturell geprüft* |
| Geräte | beide Geräte mit Block (Momentaufnahme möglich) | *strukturell geprüft* |
|  | eine Uhr und ein Handy | *strukturell geprüft* |
| Diensttage | Luftdienst | `D01`, `D02`, `D03`, `D05` … (+4) |
|  | Bodendienst | `D04`, `D06`, `D08`, `D10` … (+4) |
|  | Kalendertag mit zwei Diensten | `D05`, `D06`, `D08`, `D20` … (+2) |
|  | Dienst über Mitternacht | `D06`, `D14` |
|  | Einsatzdatum ≠ Diensttag | `D06/wm-12-7092451863`, `D06/am-12-4386015927`, `D14/am-12-8845270913`, `D14/am-12-5106938274` |
|  | Diensttag ohne Einsatz | `D12` |
|  | Tagesnotizen | `D01`, `D02`, `D03`, `D04` … (+16) |
|  | Typ Bergwacht | `D17`, `D19` |
|  | Typ Veranstaltung | `D20`, `D21` |
|  | ohne Standort (base_id NULL) | `D20`, `D21` |
|  | ohne Rollensatz (Typ ohne Vorlagen) | `D17`, `D19`, `D20`, `D21` |
|  | zweiter Dienst am Abend, ohne Überschneidung | `D06`, `D20`, `D21` |
| Besatzung | alle Rollen des Katalogs belegt | *strukturell geprüft* |
|  | abweichende Besatzung (crew_override) | `D05/m-11-3391648207`, `D16/MAN-02` |
| Phasen | alle Phasen 2–9 im Datensatz | *strukturell geprüft* |
|  | Mehrfacheintrag derselben Phase | `D05/m-11-5027369184` |
|  | unvollständige Phasen | `D02/m-11-6640281937`, `D02/m-11-1287405639`, `D07/m-11-7148036592`, `D08/am-12-1301135081` … (+16) |
|  | nicht abgeschlossener Einsatz | `D09/m-11-8207364159` |
| Reanimation | Einsatz mit einer Sitzung | `D01/m-11-5192834077`, `D04/am-12-6604485875`, `D04/am-12-7265860386`, `D06/am-12-4386015927` … (+3) |
|  | Einsatz mit mehreren Sitzungen | `D09/m-11-7761204385` |
|  | alle speicherbaren Ereignisarten (neun) | *strukturell geprüft* |
| Transport | Transportart air | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-2418095733` … (+30) |
|  | Transportart ground | `D01/m-11-7734018625`, `D01/m-11-8624759753`, `D04/am-12-2458452183`, `D04/am-12-6467858312` … (+44) |
|  | Transportart ambulant | `D02/m-11-1287405639`, `D07/m-11-7148036592`, `D08/am-12-1301135081`, `D08/am-12-6487608678` … (+12) |
|  | Transportart leer | `D02/m-11-6640281937`, `D06/am-12-4386015927`, `D15/IMP-04` |
|  | NA-Begleitung | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+76) |
|  | Fehleinsatz / Storno | `D02/m-11-6640281937`, `D15/IMP-04` |
|  | Sekundärtransport | `D02/m-11-8336537404`, `D03/m-11-2275860419`, `D07/m-11-2236709481`, `D18/am-12-6190473582` … (+1) |
|  | Sekundärtransport bodengebunden | `D18/am-12-6190473582`, `D18/am-12-9503826147` |
|  | Transportziel ad hoc (Freitext mit Koordinate) | `D20/am-12-5062847193`, `D21/MAN-04`, `D21/MAN-06` |
|  | Schockraum | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-2418095733`, `D03/m-11-9013159356` … (+24) |
|  | Zielklinik mit Koordinate | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-2418095733` … (+74) |
|  | Zielklinik ohne Koordinate | `D01/m-11-7734018625`, `D09/m-11-4083572619`, `D13/IMP-03`, `D14/am-12-8845270913` |
| Abfahrtort | Regel base | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-8624759753` … (+69) |
|  | Regel prev_site | `D07/m-11-5573920184` |
|  | Regel prev_dest | `D01/m-11-7734018625`, `D14/am-12-5106938274`, `D18/am-12-9503826147` |
|  | Regel manual (verschlüsselter pat.start) | `D01/m-11-2418095733`, `D06/am-12-4386015927`, `D16/MAN-02` |
| Bergrettung | Winde mit Cycles | `D01/m-11-2418095733`, `D02/m-11-3845141782`, `D05/m-11-3391648207`, `D07/m-11-8804157236` … (+3) |
|  | Cycles mit Patient | `D01/m-11-2418095733`, `D02/m-11-3845141782`, `D05/m-11-3391648207`, `D07/m-11-8804157236` … (+2) |
|  | Luftverladung | `D01/m-11-2418095733`, `D07/m-11-8804157236`, `D19/am-12-4826137905` |
|  | Bergwacht mit Einheit und bw_info | `D01/m-11-3067419528`, `D01/m-11-2418095733`, `D02/m-11-1287405639`, `D05/m-11-3391648207` … (+6) |
|  | Winde am bodengebundenen Bergwacht-Dienst | `D19/am-12-4826137905` |
| Geschützte Angaben | Geburtsdatum (Alter gerechnet) | `D01/m-11-3067419528`, `D01/m-11-7734018625`, `D01/m-11-2418095733`, `D02/m-11-3845141782` … (+44) |
|  | Handalter (pat_alter) | `D01/m-11-1653279357`, `D01/m-11-5192834077`, `D01/m-11-8624759753`, `D02/m-11-8336537404` … (+47) |
|  | R20-Angriffswert im Altersfeld | `D15/m-11-6127408395` |
|  | Diagnose | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+95) |
|  | Einsatzort mit Adresse und Koordinate | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+97) |
|  | Ortsbeschreibung | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+95) |
|  | Einsatznummer | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+97) |
|  | Einsatz ohne jede geschützte Angabe | `D02/m-11-9518376204`, `D09/m-11-8207364159` |
| Sonderzeichen | Semikolon | `D01/m-11-3067419528`, `D01/m-11-7734018625`, `D01/m-11-2418095733`, `D02/m-11-1287405639` … (+25) |
|  | Anführungszeichen | `D02/m-11-1287405639`, `D05/m-11-5027369184`, `D14/am-12-5106938274`, `D15/m-11-6127408395` … (+4) |
|  | Zeilenumbruch | `D02/m-11-6640281937`, `D06/am-12-4386015927`, `D11/MAN-01`, `D16/MAN-02` … (+1) |
|  | Formel-Anfangszeichen = | `D16/MAN-02`, `D21/MAN-07` |
|  | Formel-Anfangszeichen + | `D15/m-11-4470962381` |
|  | Formel-Anfangszeichen - | `D16/MAN-02` |
|  | Formel-Anfangszeichen @ | `D15/m-11-6127408395` |
|  | Umlaute und ß | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-7734018625`, `D01/m-11-2418095733` … (+87) |
| Spur | Fußweg (Wegpunkt `zustieg`) | `D17/am-12-3071845926`, `D19/am-12-4826137905` |
| Ruhezeiten | Segmente mit Track | `D01`, `D02`, `D03`, `D04` … (+17) |
|  | mehrere Segmente je Dienst | `D01`, `D02`, `D03`, `D04` … (+16) |
|  | nicht abgeschlossenes Segment | `D16` |
| Papierkorb | gelöschter Einsatz (einzeln) | `D09/m-11-2914638507` |
|  | gelöschter Diensttag | `D03` |
|  | Einsätze mit deleted_with_day | `D03/m-11-4462903718`, `D03/m-11-2275860419`, `D03/m-11-9013159356`, `D03/m-11-3532475348` |
|  | Sperrlisten-Fall als Ablaufschritt | `PS-01` |
| Stammdaten | ≥ 3 Standorte, alle mit Koordinaten | *strukturell geprüft* |
|  | ≥ 1 Diensttag ohne Standort mit `spur_ausgangspunkt` | *strukturell geprüft* |
|  | ≥ 2 Luft-Rettungsmittel mit/ohne Fähigkeiten | *strukturell geprüft* |
|  | ≥ 1 Boden-Rettungsmittel | *strukturell geprüft* |
|  | Fähigkeiten am bodengebundenen Bergwacht-Rettungsmittel | *strukturell geprüft* |
|  | Rettungsmittel mit und ohne Standort | *strukturell geprüft* |
|  | Zielkliniken mit und ohne Koordinate | *strukturell geprüft* |
|  | Vorbelegungen aller Arten | *strukturell geprüft* |
|  | Standard-Markierungen | *strukturell geprüft* |
| Zeit | Einsätze in MEZ | `D01`, `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077` … (+51) |
|  | Einsätze in MESZ | `D07`, `D07/m-11-3639672828`, `D07/m-11-7148036592`, `D07/m-11-5573920184` … (+60) |
|  | Dienst um die Umstellung im Frühjahr | `D06`, `D06/am-12-4386015927` |
|  | Dienst um die Umstellung im Herbst | `D14`, `D14/am-12-5106938274` |
| Weitere Felder | mehrere weitere Rettungsmittel je Einsatz | `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D02/m-11-3845141782`, `D05/m-11-9670228276` … (+21) |
|  | weiterer Notarzt | `D01/m-11-5192834077`, `D07/m-11-2236709481`, `D11/MAN-01`, `D15/IMP-02` … (+2) |
|  | Notizen am Einsatz | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+83) |
|  | bearbeiteter Uhr-Einsatz (edited=1) | `D01/m-11-1653279357`, `D01/m-11-3067419528`, `D01/m-11-5192834077`, `D01/m-11-7734018625` … (+86) |
|  | unbearbeiteter Uhr-Einsatz (edited=0) | `D02/m-11-9518376204`, `D09/m-11-8207364159` |
