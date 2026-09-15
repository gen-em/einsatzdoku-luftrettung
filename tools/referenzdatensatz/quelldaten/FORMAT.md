# Quellformat des Referenzdatensatzes

**JSON ist die führende Quelle** (E-P1-04). GPX ist ein abgeleitetes
Sichtprüfformat und keine Quelle — es kann Phasen, Reanimation und
Einsatzfelder nicht tragen.

## Was hier liegt

| Datei / Ordner | Rolle |
|---|---|
| `dienste/D01.json` … `D21.json` | **die Quelle**: je Dienst ein Dokument mit Diensttag, Ruhesegmenten und Einsätzen |
| `stammdaten.json` | Standorte, Rettungsmittel, Vorbelegungen (E-P1-05) |
| `geraete.json` | die **zwei Geräte** mit dem Block, den sie beim Koppeln an `pair.php` schicken (JSON-Vertrag 1a.4) — und den Präfixen, die daraus folgen |
| `pruefschritte/` | Abläufe, die **kein** Dauerzustand sind (Sperrlisten-Fall E-P1-16) |
| `schema/` | JSON-Schema, gegen das alle Dokumente validieren |
| `katalog.py` | Inhaltsvorrat für den erzeugten Betriebsalltag |
| `aufbauen.py` | füllt die Dienste auf ihre Zielzahl auf und baut die Ruhesegmente |
| `wegpunkte.py` | löst Routen-Wegpunkte auf — von Prüfskript **und** Generator benutzt |
| `pruefen.py` | Schema, Sachprüfungen, Matrixabgleich |
| `matrix_abgleich.md` | **erzeugt** aus `pruefen.py --matrix`, nicht von Hand gepflegt |

## Ein Dokument je Dienst

E-P1-04 nennt „ein Dokument je Einsatz/Dienst". Gewählt ist **je Dienst**.
Der Grund ist die Sache selbst: Ein Einsatz ohne seinen Diensttag ist
unvollständig — Besatzung, Fähigkeiten und Rollensatz kommen von dort und
sind am Diensttag eingefroren. Über hundert Einzeldateien hätten diesen
Zusammenhang über das Dateisystem verstreut, ohne etwas zu gewinnen.

## Handgeschrieben und erzeugt

Der Datensatz umfasst **21 Dienste (8 Luft, 13 Boden) mit 103 Einsätzen**. Im
Bestand sind es danach **106**: Die drei geschnittenen Einsätze stehen nicht
in den Quelldaten, sondern entstehen auf dem Server aus der Liste `schnitte`
(unten). Deshalb zählt `pruefen.py` weiter 103.
Er besteht aus zweierlei:

- **Prüffälle**, von Hand geschrieben. Jeder belegt mindestens eine Zeile
  der Abdeckungsmatrix und trägt eine `$warum`-Begründung. Sie tragen
  **kein** `erzeugt`-Kennzeichen und werden von `aufbauen.py` nie angefasst.
- **Betriebsalltag**, von `aufbauen.py` aus `katalog.py` erzeugt und mit
  `"erzeugt": true` gekennzeichnet. Er bringt den Bestand auf eine
  realistische Dichte — ohne ihn sähe ein Dienst mit vier Einsätzen aus wie
  ein Datensatz und nicht wie ein Dienst.

`aufbauen.py` ist beliebig oft ausführbar und liefert bei gleichem Samen
dasselbe Ergebnis (fester Samen `20260101`; zwei Läufe sind byteweise
gleich). Die **Ruhesegmente entstehen immer neu**: Sie sind die
Zwischenräume zwischen den Einsätzen und wären von Hand schon nach dem
nächsten hinzugefügten Einsatz falsch.

## Zeitangaben sind LOKAL

Jeder Zeitstempel steht in **Europa/Berlin**, Format `YYYY-MM-DD HH:MM`.
Der Generator rechnet nach UTC um; die Anwendung speichert UTC und zeigt
lokal.

**Warum lokal und nicht UTC:** Zwei Dienste liegen auf einer
Zeitumstellung — D06 in der Nacht auf den 29.03.2026 und D14 in der Nacht
auf den 25.10.2026. Genau dort muss die Quelle sagen, welche *Ortszeit*
gemeint ist; in UTC notiert wäre die Umstellung nicht mehr zu sehen und der
Prüffall verlöre seinen Gegenstand.

`pruefen.py` prüft **jeden** Zeitstempel darauf, ob es ihn an dem Tag
überhaupt gibt (Frühjahrsumstellung) und ob er eindeutig ist
(Herbstumstellung). Das Datum steht auch bei Phasen und Ereignissen
ausgeschrieben, weil Dienste über Mitternacht laufen.

## Felder eines Diensttags

| Feld | Bedeutung |
|---|---|
| `kennung` | `D01`…`D21`, stabil; Verweisziel des Matrix-Abgleichs |
| `abdeckung` | Marken der Abdeckungsmatrix, die dieser Dienst belegt |
| `dienst.day` | Kalenderdatum des **Dienstbeginns** (Sortierung/Anzeige) |
| `dienst.art` | `air` \| `ground` — wird beim Zuordnen eingefroren |
| `dienst.day_ref` | Uhr-Dienstkennung, Präfix `d-` (JSON-Vertrag 2.1) |
| `dienst.standort` | Name eines Standorts — oder **`null`**: der Diensttag **ohne Standort**, den die Anwendung seit Web 16.0.0 kennt. Zulässig nur, wenn das Rettungsmittel `ohne_standort` trägt. Dann gibt es keine Vorschlagslisten (Zielklinik, Bereitschaft, weitere Rettungsmittel sind Freitext), keine Besatzung und keinen Rollensatz |
| `dienst.spur_ausgangspunkt` | der reale Ausgangspunkt für die Spurerzeugung. **Pflicht**, wo der Wegpunkt `basis` sonst auf nichts aufliefe — an einem Standort ohne Koordinaten und an einem Tag ohne Standort. **Verboten**, wo der Standort selbst Koordinaten führt: zwei Wahrheiten übereinander (E-DA-09). Wird **nicht** als Stammdatum gespeichert |
| `dienst.besatzung` | je Rolle des Rettungsmittels ein Eintrag, `null` erlaubt; **leer** an einem Tag ohne Standort und an einem Typ ohne Rollen-Vorlagen |
| `papierkorb` | `null` oder `"diensttag"` — Dauerzustand nach E-P1-21 |
| `schnitte` | **optional**, an beliebig vielen Diensten und je Dienst beliebig viele: die Schnitt-Aufträge der Stufe `schneiden` (unten) |
| `ziel_einsaetze` | Zielzahl; `aufbauen.py` füllt bis dahin auf |

## Die zwei Geräte und ihre Präfixe

`geraete.json` beschreibt, **womit** der Bestand aufgezeichnet wurde: Gerät
**11** ist eine Garmin-Uhr (Luftdienste), Gerät **12** ein Android-Handy
(Bodendienste). Beide werden beim Einspielen **echt gekoppelt** — über
`pair.php`, nicht über die Geräteseite. Nur so tragen sie `geraet_art` und
`geraet_modell`, und nur dann kopiert `ingest.php` diese **Momentaufnahme**
an jeden Einsatz und jedes Ruhesegment.

Aus der Geräteart folgt das Kennungspräfix, und aus dem Präfix leitet der
Server die Herkunft ab (`herkunft_ableiten()`, JSON-Vertrag 8):

| Gerät | Einsatz | Ruhesegment | Diensttag | Herkunft |
|---|---|---|---|---|
| 11 — Uhr | `m-11-…` | `r-11-…` | `d-11-…` | `watch` |
| 12 — Handy | `am-12-…` | `ar-12-…` | `ad-12-…` | `android` |
| 12 — Handy, an der Uhr begonnen | `wm-12-…` | — | — | `wear` |

Die `wm-`-Einsätze stehen nur an **handgeschriebenen** Prüffällen:
`aufbauen.py` vergibt sie nicht, weil „an der Uhr begonnen" eine Aussage
darüber ist, wo jemand einen Knopf gedrückt hat — keine Eigenschaft, die
sich erzeugen ließe.

`pruefen.py` hält Präfix und Geräteart gegeneinander. Eine Kennung, die
nicht zur Art ihres Geräts passt, ist ein Befund: Der Server leitete daraus
eine andere Herkunft ab, als dieses Dokument behauptet, und niemand merkte
es.

## Die Schnitte

Ein Dienst trägt eine Liste `schnitte` — die Aufträge für die Einspielstufe
`schneiden`, die über `api/schneiden.php` aus einem Ruhesegment einen
Einsatz macht. Bis zum Demo-Ausbau war **genau einer** vorgeschrieben
(`schnittzahl == 1`, E-R64-16); seither sind es beliebig viele an beliebig
vielen Diensten (E-DA-11), und `pruefen.py` prüft die Summe gegen die Liste.
Was die alte Zeile schützen sollte, schützt die neue weiter: dass der
Bestand seinen Schnitt nicht **still** verliert — und mit ihm den
Sperrvermerk, den Backlog Nr. 63 im Bestand haben will.

| Feld | Bedeutung |
|---|---|
| `abdeckung` | Marken der Abdeckungsmatrix (hier `herkunft-schnitt`) |
| `segment` | `client_ref` des Ruhesegments **desselben Dienstes** |
| `beginn`, `ende` | lokale Zeiten, **innerhalb** der Spur des Segments |
| `phasen` | genau `3`, `4`, `7` — mehr nimmt der Endpunkt nicht |

**Der geschnittene Einsatz steht NICHT in den Quelldaten.** Er entsteht
serverseitig und bekommt dort eine Kennung mit Präfix `cut-`; deshalb zählt
`pruefen.py` weiter 103 Einsätze, der Bestand aber 106.

`pruefen.py` prüft vier Randbedingungen, jede davon aus einem Fehlschlag
gelernt: das Fenster liegt in der Spur des Segments (sonst wandert kein
Punkt und der Server antwortet `409 leer`), es läuft nicht über Mitternacht
(die Phasen rechnen mit dem Tagesversatz des **Beginns**), seine
Beginnminute kommt am selben Diensttag kein zweites Mal vor (drei
Einspielstufen suchen Einsätze über `start_hhmm`), und es liegt nicht am
**neuesten** Diensttag (den öffnet `index.php` von selbst, und
`sichtpruefung.mjs` wie vier Seiten des Bilderlaufs greifen auf dessen erste
Einsatzzeile — die ein geschnittener Einsatz nicht bedienen kann, weil er
keine geschützten Angaben hat).

## Felder eines Einsatzes

| Feld | Bedeutung |
|---|---|
| `client_ref` | Kennung nach JSON-Vertrag 8, Präfix `m-`. **`null`** bei `kanal` `import` und `formular`: Diese Wege vergeben sie selbst und nicht vorhersagbar (`imp-` + Zufall bzw. `man-` + `uniqid()`). Zur Nachverfolgung dient dann `quell_kennung` |
| `kanal` | `ingest` \| `formular` \| `import` (E-P1-01) |
| `nachtrag` | nur bei `ingest`: Felder und geschützte Angaben werden per Skript über `einsatz_form.php` nachgetragen. `false` lässt den Einsatz als reinen Uhr-Einsatz stehen (`origin=watch`, `edited=0`) |
| `erzeugt` | `true` = Betriebsalltag aus `aufbauen.py`; fehlt bei Prüffällen |
| `phasen` | Liste `[nummer, "lokale Zeit"]`. Mehrfache Nummern sind **erlaubt** und bleiben erhalten (Korrektur, JSON-Vertrag 3) |
| `route` | Wegpunkt-Folge für die Spur (siehe unten); `null` = kein Track |
| `spur` | Koordinaten **nur** für die Spurerzeugung (siehe unten) |
| `rea` | Sitzungen, je `beginn` und `ereignisse` `[typ, zeit]` |
| `felder` | Spalten aus `mission_fields.php` (Klartext) |
| `geschuetzt` | Inhalt des `pat_blob` (E2E-verschlüsselt) |
| `papierkorb` | `null` oder `"einsatz"` — Dauerzustand nach E-P1-21 |

## Wegpunkte und die Trennung von Spur und Adresse

`route` benennt Wegpunkte, keine Koordinaten:

| Wegpunkt | löst auf zu |
|---|---|
| `basis` | Standortkoordinate, sonst `dienst.spur_ausgangspunkt` |
| `start` | `geschuetzt.start` (nur bei `start_src = "manual"`) |
| `ort` | `spur.ort`, sonst `geschuetzt.loc` |
| `ziel` | `spur.ziel`, sonst `felder.dest_lat/dest_lon` |
| `zustieg` | `spur.zustieg` — der Punkt, bis zu dem **gefahren** wird: Parkplatz, Talstation, Hüttenzufahrt |
| `ort_vorher` | Einsatzort des **vorigen** Einsatzes (`start_src = "prev_site"`) |
| `ziel_vorher` | Transportziel des **vorigen** Einsatzes (`start_src = "prev_dest"`) |

**Der Fußweg ist ein Paar von Wegpunkten, kein Schalter am Einsatz.** Ein
Teilstück wird **zu Fuß** gezeichnet, wenn seine beiden Enden `zustieg` und
`ort` heißen — in der einen oder der anderen Richtung (`wegpunkte.ist_fussweg`).
Alles andere fährt oder fliegt. Drei lesen diese eine Frage: der Generator
(er zeichnet mit `spur.fussweg`), der Routenabruf (er holt dafür **keine**
Straße) und die Prüfung (sie misst die Gehgeschwindigkeit gegen 1,5–6,0 km/h).

**Die Gehgeschwindigkeit wird nicht gesetzt, sie ergibt sich** aus dem
Zeitfenster der Phasen und der Strecke. Mit einem `zustieg` hat ein Einsatz
mehr Teilstücke als es Phasenfenster gibt; welches Teilstück welches Fenster
bekommt, rechnet `wegpunkte.fenster()` — Phase 4 („Ankunft Einsatzort") ist
dann der Punkt, an dem das Fahrzeug hält, Phase 5 („Ankunft Patient*in") der
Patient. Wo zwischen zwei Phasen mehrere Teilstücke liegen, wird die Spanne
im Verhältnis ihrer geschätzten Dauer geteilt.

Die Phasenkoordinaten leitet der Generator daraus ab (2/3 am Abfahrtort,
4/5/6 am Einsatzort, 7/8 am Ziel, 9 an der Basis) — so kann die Quelle
keine Koordinate doppelt und widersprüchlich führen. `pruefen.py` prüft für
**jeden** Wegpunkt, dass er auf eine Koordinate auflöst.

**`spur` ist kein Notbehelf, sondern die Trennung, die die Anwendung selbst
macht.** Trackpunkte und Phasenkoordinaten liegen im Klartext (`track_points`,
`mission_phases`); verschlüsselt ist die **Adresse** (`pat_blob.loc.addr`).
Deshalb hat ein Einsatz ohne geschützte Angaben sehr wohl eine Spur — und
deshalb kann eine Zielklinik ohne gespeicherte Koordinate trotzdem
angeflogen worden sein. In beiden Fällen steht die Koordinate in `spur` und
wird **nicht** am Einsatz gespeichert.

## Marken der Abdeckung

`abdeckung` trägt Marken wie `phasen-mehrfach` oder `start-manual`.
`pruefen.py` bildet sie auf die Zeilen der Abdeckungsmatrix ab und meldet
jede Zeile, die **kein** Dokument belegt. Damit ist P-01 eine Messung mit
Zahl und keine Behauptung. Viele Marken vergibt das Skript zusätzlich aus
dem Inhalt (Sonderzeichen, Transportart, Abfahrtortregel) — eine Marke,
die nur behauptet wäre, gäbe es nicht.

## Läufe

```
python3 aufbauen.py           # Dienste auffüllen, Ruhesegmente bauen
python3 pruefen.py            # Schema, Sache, Matrix
python3 pruefen.py --matrix   # zusätzlich matrix_abgleich.md schreiben
```
