# Quellformat des Referenzdatensatzes

**JSON ist die führende Quelle** (E-P1-04). GPX ist ein abgeleitetes
Sichtprüfformat und keine Quelle — es kann Phasen, Reanimation und
Einsatzfelder nicht tragen.

## Ein Dokument je Dienst

E-P1-04 nennt „ein Dokument je Einsatz/Dienst". Gewählt ist **je Dienst**:
`dienste/D01.json` … `dienste/D14.json`, jedes mit dem Diensttag, seinen
Ruhesegmenten und seinen Einsätzen. Der Grund ist die Sache selbst — ein
Einsatz ohne seinen Diensttag ist unvollständig (Besatzung, Fähigkeiten
und Rollensatz kommen von dort und sind am Diensttag eingefroren), und
39 Einzeldateien hätten diesen Zusammenhang über das Dateisystem
verstreut, ohne etwas zu gewinnen.

Daneben:

- `stammdaten.json` — Standorte, Rettungsmittel, Vorbelegungen (E-P1-05)
- `pruefschritte/` — Abläufe, die **kein** Dauerzustand sind
  (Sperrlisten-Fall E-P1-16)
- `schema/` — JSON-Schema, gegen das alle Dokumente validieren
- `matrix_abgleich.md` — welcher Einsatz welche Matrixzeile belegt

## Zeitangaben sind LOKAL

Jeder Zeitstempel in diesen Dateien steht in **Europa/Berlin**, im Format
`YYYY-MM-DD HH:MM`. Der Generator rechnet nach UTC um; die Anwendung
speichert UTC und zeigt lokal.

**Warum lokal und nicht UTC:** Zwei Dienste des Datensatzes liegen auf
einer Zeitumstellung (D05 in der Nacht auf den 29.03.2026, D12 in der
Nacht auf den 25.10.2026). Genau dort muss die Quelle sagen, welche
*Ortszeit* gemeint ist — in UTC notiert wäre die Umstellung nicht mehr
zu sehen und der Prüffall verlöre seinen Gegenstand. Das Datum steht
auch bei Phasen und Ereignissen ausgeschrieben, weil Dienste über
Mitternacht laufen.

## Felder eines Diensttags

| Feld | Bedeutung |
|---|---|
| `kennung` | `D01`…`D14`, stabil; Verweisziel des Matrix-Abgleichs |
| `abdeckung` | Marken der Abdeckungsmatrix, die dieser Dienst belegt |
| `dienst.day` | Kalenderdatum des **Dienstbeginns** (Sortierung/Anzeige) |
| `dienst.art` | `air` \| `ground` — wird beim Zuordnen eingefroren |
| `dienst.day_ref` | Uhr-Dienstkennung, Präfix `d-` (JSON-Vertrag 2.1) |
| `dienst.besatzung` | je Rolle des Rettungsmittels ein Eintrag, `null` erlaubt |
| `papierkorb` | `null` oder `"diensttag"` — Dauerzustand nach E-P1-21 |

## Felder eines Einsatzes

| Feld | Bedeutung |
|---|---|
| `client_ref` | Kennung nach JSON-Vertrag 8; Präfix `m-`, `man-`, `imp-` |
| `kanal` | `ingest` \| `formular` \| `import` (E-P1-01) |
| `nachtrag` | nur bei `ingest`: Felder und geschützte Angaben werden per Skript über `einsatz_form.php` nachgetragen (E-P1-01b). `false` lässt den Einsatz als reinen Uhr-Einsatz stehen (`origin=watch`, `edited=0`) |
| `phasen` | Liste `[nummer, "lokale Zeit"]`. Mehrfache Nummern sind **erlaubt** und bleiben erhalten (Korrektur, JSON-Vertrag 3) |
| `route` | Wegpunkt-Folge für den Track (siehe unten); `null` = kein Track |
| `spur` | Koordinaten **nur** für die Spurerzeugung, wo ein Wegpunkt nicht aus den gespeicherten Feldern folgt |
| `quell_kennung` | `IMP-01`…, `MAN-01`… — Ersatz für die `client_ref` bei Import und Formular |
| `rea` | Liste von Sitzungen, je `beginn` und `ereignisse` `[typ, zeit]` |
| `felder` | Spalten aus `mission_fields.php` (Klartext) |
| `geschuetzt` | Inhalt des `pat_blob` (E2E-verschlüsselt): `dx`, `dob`/`age`, `mission_no`, `loc`, `site_desc`, `start` |
| `papierkorb` | `null` oder `"einsatz"` — Dauerzustand nach E-P1-21 |

## Wegpunkte

`route` benennt Wegpunkte, keine Koordinaten — so kann die Quelle keine
Koordinate doppelt und widersprüchlich führen. Aufgelöst werden sie an
**einer** Stelle, `wegpunkte.py`, die Prüfskript und Generator gemeinsam
benutzen:

| Wegpunkt | Koordinate |
|---|---|
| `basis` | Standortkoordinate, ersatzweise `dienst.spur_ausgangspunkt` |
| `start` | manueller Abfahrtort `geschuetzt.start` (nur `start_src='manual'`) |
| `ort` | `spur.ort`, sonst `geschuetzt.loc` |
| `ziel` | `spur.ziel`, sonst `felder.dest_lat/dest_lon` |
| `ort_vorher` | Einsatzort des **vorigen** Einsatzes (`start_src='prev_site'`) |
| `ziel_vorher` | Transportziel des **vorigen** Einsatzes (`start_src='prev_dest'`) |

Die Phasenkoordinaten leitet der Generator daraus ab: 2 und 3 am
Abfahrtort, 4 bis 6 am Einsatzort, 7 und 8 am Ziel, 9 an der Basis.

### Warum es `spur` gibt

Trackpunkte und Phasenkoordinaten liegen in der Anwendung im
**Klartext** (`track_points`, `mission_phases`); verschlüsselt ist die
**Adresse** des Einsatzorts. Ein Einsatz ohne geschützte Angaben hat
deshalb sehr wohl eine Spur — sie kommt dann aus `spur`. Denselben Dienst
tut `spur.ziel`, wenn die Zielklinik am Einsatz bewusst keine Koordinate
führt: Geflogen wurde trotzdem dorthin.

`spur` wird **nicht gespeichert**. Sie ist Baumaterial für den Track und
verlässt den Generator nicht.

## Marken der Abdeckung

`abdeckung` trägt Marken wie `phasen-mehrfach` oder `start-manual`.
`pruefen.py` bildet sie auf die Zeilen der Abdeckungsmatrix ab und meldet
jede Zeile, die **kein** Dokument belegt. Damit ist P-01 eine Messung mit
Zahl und keine Behauptung.


## Was die Quelle NICHT festlegen kann

Zwei Kennungen vergibt die Anwendung selbst, und zwar nicht
vorhersagbar:

- **Import:** `import_commit.php` bildet `'imp-' . bin2hex(random_bytes(12))`
- **Formular:** `einsatz_form.php` bildet `'man-' . uniqid()`

Für diese Einsätze steht `client_ref` deshalb auf `null` und an seiner
Stelle eine `quell_kennung` (`IMP-01`, `MAN-01`, …), die nur in diesen
Quelldateien gilt. **Folge für die Regression:** Die `client_ref` dieser
sechs Einsätze ist in der Referenz-`edbak` von Lauf zu Lauf verschieden;
das Vergleichswerkzeug (B5) normalisiert sie wie die internen IDs. Im
CSV-Export steht sie ohnehin nicht.
