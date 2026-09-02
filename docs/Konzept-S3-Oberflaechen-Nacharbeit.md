# Konzept S3 — Oberflächen-Nacharbeit und vertikaler Rhythmus

> ## Stand: umgesetzt (02.09.2026)
>
> **Alle zwölf Arbeitspakete sind gebaut**, Web **12.2.2 bis 12.4.2**, ein
> Commit je Paket. **Keine Migration** — `update.php` ist nach dem Deploy
> nicht nötig. **Die Uhr-Version steigt nicht**; die neu gerasterten Kacheln
> reisen mit S5 (E-S3-04). **Die Deploy-Freigabe steht aus** — ein Push auf
> `main` deployt sofort.
>
> Der Umsetzungsstand je Paket steht in **Abschnitt 10**, die Fehlerfunde in
> **Abschnitt 8** (F-S3-A bis F-S3-E), die während der Umsetzung
> entschiedenen Fragen in **Abschnitt 4** (F-S3-01 bis F-S3-03). Was noch
> **von Hand** zu prüfen ist, steht nicht hier, sondern in
> `docs/Pruefdokument-S3-Oberflaechen-Nacharbeit.md`.
>
> **Drei Zahlen, die die Phase zusammenfassen:** Von 269
> Abstandsdeklarationen trug **keine** einen Rohwert — es fehlte nicht die
> Skala, sondern die Regel. Der Markerversatz ist von **51,7 px auf 0,0 px**
> gemessen. Und **fünf Funde** kamen dazu, die auf keiner Liste standen;
> **drei davon konnte kein Prüfmittel finden**.
>
> Der Text unterhalb dieses Kastens ist die **Übergabefassung vom
> 01.09.2026** und bleibt, wie sie war — sie sagt, was geplant war. Wo die
> Umsetzung etwas anders vorgefunden hat, sagt das der jeweilige Abschnitt
> in Kapitel 10, nicht eine stille Berichtigung oben.

Zwischenpaket nach R43, **nach S2, vor S5** (Rahmenplan Fassung 15).
Konzeptstand: 01.09.2026, Übergabefassung an die Umsetzung. Format nach K1;
keine Versionsnummern (K3). **Kein Fable-Schritt** (K2/K8): Die
Gestaltungslinie steht in `docs/Design.md`; dieses Paket schreibt sie fort,
es erfindet sie nicht. Umsetzung durchgehend mit dem Standardmodell.

Grundlage ist die Rückmeldungsliste vom 31.08.2026 (`ToDo_Layout.pdf`, acht
Seiten, 19 Punkte am ausgelieferten Stand Web 9.14.1). **Die PDF liegt nicht
im Repositorium**; maßgeblich ist ihre Wiedergabe im Rahmenplan (Eintrag R43
und Abschnitt 5, Blöcke A–K), die dieses Konzept übernimmt und am Code
prüft. Die drei offenen Gestaltungsfragen der Liste sind am 31.08.2026
entschieden (E-R43-1 bis E-R43-3) und gehen ohne weitere Rückfrage in die
Umsetzung. Dazu kommen **zwei nachgetragene Punkte** der zweiten
Rückmeldungsrunde vom 01.09.2026, die **bereits umgesetzt sind**
(Abschnitt 1.13) — sie stehen hier, damit die Umsetzung sie als erledigt
vorfindet und nicht ein zweites Mal beschließt.

Ablage: `docs/Konzept-S3-Oberflaechen-Nacharbeit.md` (Muster E-S1-18); das
Prüfdokument nach K9 entsteht in der Umsetzung daneben als
`docs/Pruefdokument-S3-Oberflaechen-Nacharbeit.md`. Die umsetzende Instanz
schreibt dieses Dokument fort — Abschnitt 4 (Fragen der Umsetzung,
F-S3-01 ff.), Abschnitt 8 (Fehlerfunde, F-S3-A ff.) und Abschnitt 10
(Umsetzungsstand).

---

## 0. Auftrag und Rahmen

Die 19 Rückmeldungen sind zum größten Teil **nicht eine Sammlung von
Schönheitsfehlern, sondern die sichtbare Folge einer fehlenden Regel**: Die
Abstandsskala existiert und wird eingehalten, aber es gibt keine
Anwendungsregel, die sagt, welche Stufe wo gilt (Befund in 1.1). Diese Regel
wird in diesem Paket geschrieben — der Rest der Liste ist überwiegend ihre
Anwendung, dazu vier Funktionsänderungen und ein echter Fehler mit bereits
geklärter Ursache (Markerversatz, 1.9).

Die Arbeitsweise ist durch R43 verbindlich vorgegeben (ausdrücklich: **kein
CSS-Flickwerk**):

1. Der vertikale Rhythmus wird als **Regelwerk in `docs/Design.md`**
   festgeschrieben — je Beziehung genau eine Stufe der bestehenden Skala,
   mit Begründung; neue Token nur, wenn die fünf Stufen nachweislich nicht
   reichen.
2. Umgesetzt wird **an Klassen und Bausteinen** (`ui.php`,
   `stammdaten_ui.php`, die Kartenbausteine), nie als Einzelregel an einer
   Seite; wo ein Baustein fehlt, entsteht er — unter der Freigaberegel aus
   `Design.md` 1.2 (E-S3-11).
3. Eine **Einzelkorrektur an einer Seite ist begründungspflichtig** und wird
   in diesem Konzept benannt (bisher benannt: keine; die beiden Punkte der
   zweiten Rückmeldungsrunde wurden am Baustein behoben, 1.13).
4. Die Prüfmittel laufen mit: Stilvergleich mit Soll-Ist-Liste,
   `tools/screenshots/` über acht Breiten, Vollständigkeitsprüfung des
   Stylesheets, Wortliste nach R28 (Abschnitt 6).

Als **Funktionsänderungen** gekennzeichnet — sie ändern Verhalten, nicht nur
Aussehen, und werden einzeln im Browser geprüft:

- Filterausblendung in der Suche (1.11, AP9),
- Autosuche im Ortsfeld (1.6, AP8),
- Sperrung des Demo-Kontos (1.5, AP10),
- Höhenanzeige nur bei Luftrettung (1.8, AP6).

**Nicht Umfang von S3:**

- Der **Geräteabschnitt von `einstellungen.php`** — benannte Ausnahme von
  der Formular-Durchsicht: S5 ersetzt ihn vollständig (umgekehrte Kopplung,
  R49); die Bausteinänderungen aus S3 wirken dort von selbst, sobald S5 mit
  den Bausteinen baut (E-S3-03, Rahmenplan Fassung 15).
- Die **Terminologie-Umstellung „Sicherung" → „Backup"** (R50) — sie folgt
  nach S5, in einem Zug; S3 schreibt keine der betroffenen Zeichenketten
  vorab um.
- Die **Auslieferung der neu gerasterten Uhr-Kacheln** — sie reisen mit der
  nächsten Uhr-Auslieferung, das ist nach R48 die von **S5** (E-S3-04). In
  S3 werden Beschnitt und Generatorlauf gemacht; die Dateien liegen bis
  dahin im Repositorium.
- Die **Logofarben** — Backlog Nr. 49 (alte Farbwerte in den Vektordateien)
  ist am 31.08.2026 bewusst liegen gelassen worden; S4/B1 übernimmt den dann
  aktuellen Stand. S3 ändert am Bildinhalt nichts, nur am Rahmen (E-S3-05).
- **P4 Nr. 21** (A4-Restfunde) und die übrigen Backlog-Punkte aus P3.

---

## 1. Befund

### 1.1 Der Kern: die Skala steht, die Anwendungsregel fehlt

Nachgemessen am Stylesheet (R43), nicht vermutet: Die Frage der Liste
(„Warum passen die Abstände häufiger nicht? Gibt es dafür keine
Definition?") hat eine genaue Antwort, und sie lautet nicht „jemand hat
krumme Werte benutzt".

- Die **Werteskala wird eingehalten**: `--abstand-1` bis `--abstand-5`
  (4/8/12/16/24 px), 222 Deklarationen ziehen ihre Abstände aus diesen
  Token, ganze **zwei** Stellen tragen einen Rohwert (davon einer eine
  1-px-Linie).
- Was fehlt, ist die Stufe darüber: eine **Anwendungsregel**, die sagt,
  *welche* Stufe *wo* gilt — nach einer Überschrift, zwischen Formular und
  nächstem Abschnitt, unter einer Knopfreihe. Ohne sie wird die Wahl an
  jeder der 222 Stellen einzeln getroffen: 29-mal fällt sie auf
  `--abstand-3`, 15-mal auf `--abstand-4`, 15-mal auf `--abstand-1`, ohne
  dass ein Muster dahinterstünde.
- Der von der Liste bemängelte Fall belegt es: In `einstellungen.php` steht
  „Profil speichern" als **nackter `ui_knopf()` zwischen `ui_karte_ende()`
  und `</form>`** — obwohl es mit `.listen-form-fuss`
  (`margin-top: var(--abstand-4)`) längst einen Baustein für den
  Formularfuß gibt. Der Abstand fehlt nicht, weil eine Zahl falsch wäre,
  sondern weil an dieser Stelle **kein Baustein benutzt wird**.

Das Regelwerk steht als Beschluss in 2.2 (E-S3-01/E-S3-02); AP1 misst den
Bestand dagegen und legt jede Abweichung offen, bevor umgebaut wird.

### 1.2 A — Vertikaler Rhythmus und Formularfuß

„Profil speichern" bekommt den vorhandenen Formularfuß-Baustein statt einer
eigenen Regel. Danach eine **Durchsicht der übrigen Formularseiten** auf
dasselbe Muster (freistehender Absendeknopf ohne Fuß) — mit der benannten
Ausnahme Geräteabschnitt (E-S3-03). Die Durchsicht zählt ihre Funde; das
Ergebnis (welche Seiten, welche Stellen) gehört in den Paketabschluss von
AP2, nicht in eine Fußnote.

### 1.3 B — NutzerInnen-Liste (`admin_users.php`)

Die Spalten Rolle, Seit, Zuletzt angemeldet, Geräte und Sicherung werden
**zentriert** — Überschrift und Inhalt gemeinsam; heute stehen die Inhalte
links und die Überschrift steht über nichts. Die Spalte Konto bleibt
linksbündig. Der **Zeilentrenner reicht heute nicht über die
„Öffnen"-Spalte** und wird auf die volle Tabellenbreite gezogen. Beides
gehört an die Tabellenklassen, **nicht an `:nth-child`** (Grundregel
`CLAUDE.md` 5 und `Design.md` 6).

### 1.4 C — Sammelleiste (`ui_speichern_leiste`, `.speichern`)

Zwei Dinge:

- **Reihenfolge umkehren**: Knopf rechts, Zählung („XX ausgewählt") links
  daneben — heute steht der Knopf im Markup zuerst und damit links.
- **Form ist entschieden (E-R43-1): Die Leiste übernimmt die Kartenform** —
  Radius und Breite wie die Karte darüber, keine begründete Abweichung. Der
  negative Randausbruch (`margin: … calc(var(--abstand-3) * -1)`) entfällt;
  er war die Ursache dafür, dass die Leiste „eckig und breiter" wirkte.
  Sticky Sitz und Trennlinie nach oben bleiben — sie tragen die Funktion
  (die Leiste klebt), nicht die Form.

Der Baustein hat **zwei Verwendungen** (`Design.md` 9.9): die
Speichern-Leiste schmutziger Formulare und die Sammelleiste einer Auswahl
(„Auswahl sichern" in der NutzerInnen-Liste, „Auswahl als GPX" auf der
Spurenseite). Die Änderung trifft beide; beide werden geprüft.

### 1.5 D — Demo-Konto auf der Kontoseite (`admin_user.php`)

Für das Demo-Konto entfallen Sicherungsanzeige und Sicherungsaktionen (es
wird zentral über den Reiter „Demo-Konto" angelegt und zurückgesetzt,
gesichert wird es nicht); die Bearbeitung wird **gesperrt und sichtbar
ausgegraut**, die Seite bleibt aufrufbar. Anzeigename des Kontos: **„Demo
NutzerIn"**. Das berührt R25 (das Demo-Konto ist dauerhafter Bestandteil) —
die Sperre wird beim Paketabschluss im R25-Eintrag des Rahmenplans
mitgeführt. **Funktionsänderung**; Umsetzungsweg in E-S3-07.

### 1.6 E — Formularbausteine

- Die **Logo-Wahl** wird von umrandeten Einzelzeilen auf eine **schlichte
  Liste** umgestellt — kompakter; die Änderung betrifft den
  Radiolisten-Baustein, nicht nur diese eine Stelle.
- **Platzhalter tragen ausschließlich Phantasienamen.** „z. B. Standort
  Kempten" bevorzugt einen realen Ort; die Regel gilt für **alle** Formulare
  und wird als Pflegeregel in `docs/Design.md` festgeschrieben (E-S3-13).
  AP8 erhebt den Bestand vollständig und tauscht jeden realen Orts- oder
  Namenswert.
- Das **Ortsfeld sucht beim Tippen** (entprellt), ohne Klick auf die Lupe —
  bei Standort und Zielklinik; die Lupe bleibt als Auslöser bestehen.
  **Funktionsänderung** mit einer Folge, die benannt werden muss: Die
  Adresssuche geht an Photon (`photon.komoot.io`, `Lizenzen.md` 6.2), und
  dort steht heute als Zusage, dass die Suche „nicht bei jedem Tastendruck
  läuft" und nur auf ausdrückliches Auslösen hin. Mit der Autosuche löst
  das **Tippen selbst** die Anfrage aus. Die Leitplanken stehen in E-S3-06;
  `Lizenzen.md` 6.2 wird im selben Paket nachgezogen.

### 1.7 F — Navigation und Leistenüberschrift

- Die Menüpunkte der Seitenleiste sind durchgehend fett; künftig **normal,
  fett nur der ausgewählte Punkt**.
- Die Überschrift „Diensttage" wirkt verloren. **Entschieden (E-R43-2):
  linksbündig bleibt sie, größer wird sie** — „wirkt verloren" ist ein
  Problem von Größe und Kontrast, nicht von Ausrichtung. Umgesetzt am
  geteilten Baustein `.leiste-kopfzeile`: **eine Stufe höher in der
  Schriftskala, Farbe von `--gedaempft` auf `--asphalt`**. Der Baustein
  trägt **vier** Zeilen (Diensttage, Einstellungen, Administration, Filter
  der Suche); alle vier ziehen mit und stehen als vier beabsichtigte
  Abweichungen in der Soll-Ist-Liste des Stilvergleichs. Ob Versalien und
  Sperrung bei der größeren Stufe bleiben, **entscheidet die Umsetzung am
  Bild** (E-S3-12) — gesperrte Versalien lesen sich ab einer gewissen Größe
  als Etikett, nicht als Überschrift.

### 1.8 G — Einsatzansicht (`einsatz.php`)

- Die **Höhe erscheint nur bei Luftrettung** (`kind = 'air'`), dann aber
  beschriftet: „Höhe 1917 m" statt „1917 m". **Funktionsänderung.**
- **Schlosssymbole vertikal zum Text zentrieren.**
- Am Block „Einsatz" fehlt die blaue Plakette „verschlüsselt", bei
  „Name/Geboren" fehlt das Schlosssymbol — beides ergänzen.
- Der blaue Hinweisbalken „Geschützte Angaben sind entsperrt, bis du dich
  abmeldest" **entfällt**; die Information steht im Handbuch (das Handbuch
  wird an der Stelle gegengelesen und, falls nötig, nachgezogen — entfernte
  Oberfläche wird ausgetragen, nicht nur neue eingetragen).

### 1.9 H — Karte (`geo.js`, `.geo-*`), mit dem echten Fehler der Liste

**Der Markerversatz — Ursache gefunden (R43), kein Ratespiel:** Die Marker
von Standort und Zielklinik sitzen umso weiter östlich, je weiter
herausgezoomt wird. `.geo-schild` ist eine Flex-**Spalte** mit
`align-items: center`; das Icon-Wurzelelement wird damit so breit wie sein
breitestes Kind — das **Namensschild** (`white-space: nowrap`), nicht der
44-px-Kasten. `geo.js` verankert aber mit `iconAnchor: [22, 22]` bei
`iconSize: null`, also auf der Mitte des breiten Wurzelelements. Bei
„Klinikum Immenstadt" liegt der Kasten dadurch rund 50 px zu weit rechts —
ein **konstanter Pixelversatz**: herausgezoomt sind dieselben 50 px
Kilometer, hereingezoomt Meter. Der Versatz wächst mit der Länge des
Namens. Er verschwindet mit der ohnehin gewünschten Streichung der
Beschriftung von selbst — **die Behebung setzt `iconSize` trotzdem
ausdrücklich**, damit ein künftiges Beiwerk am Marker den Fehler nicht
erneut einträgt.

Dazu die Gestaltungsänderungen:

- **Keine Beschriftung** an Standort und Zielklinik — nur das Symbol; der
  Kasten wird kleiner, vor allem durch **weniger Weißraum zwischen Symbol
  und Rand** (das Symbol selbst bleibt weitgehend gleich groß).
- Auf `index.php` erscheint **nur der Standort des Rettungsmittels**, in
  derselben Größe wie in der Einsatzansicht, ohne Beschriftung;
  **Zielkliniken werden dort nicht angezeigt**.
- Das orange Einsatzort-Symbol verliert seine **weiße Umrandung** und wird
  etwas verkleinert.

### 1.10 I — Kacheln und Tabellen

- Kennzahl-Kacheln der Zeitraumübersicht auf **größeren Kacheln vertikal
  zentrieren**.
- Tagesübersicht (`index.php`): die **Dauer steht auch in schmaler Spalte
  einzeilig** („1h 06min" bricht heute um); **Nr., Beginn und Alter werden
  in der Zelle zentriert**; die Spaltenüberschrift „Sekundär Transport"
  wird zu **„Sekundär-" / „transport"** (Trennstrich mit Umbruch).
- Spaltenbreiten und -ausrichtungen über Klassen, nie über `:nth-child`.

### 1.11 J — Suche (`suche.php`)

Filter erscheinen **nur, wenn im Bestand etwas dahintersteht** — enthält
kein Datensatz einen Praktikanten, entfällt das Feld. **Funktionsänderung.**
Die Regel gehört an die **Filtererzeugung** aus dem Feldkatalog
(`mission_fields.php`), nicht als Sonderfall je Rolle — sonst entsteht
genau der Einzelfall-Wildwuchs, den der Feldkatalog abschaffen sollte
(E-S3-08).

### 1.12 K — Logogrößen Luft und Boden

Beobachtet am 31.08.2026, **im Browser nachgemessen** (Chromium,
`svg.getBBox()` über die Wurzel — eine erste, grobe Zählung der Koordinaten
im Dateitext hatte das Gegenteil nahegelegt und war falsch). Zwei Ursachen,
die sich addieren:

1. **Das Bodenlogo ist gar nicht quadratisch — es sitzt nur auf einer
   quadratischen Fläche.** `viewBox="0 0 420 420"`, die tatsächliche
   Zeichnung misst **420 × 335** und beginnt bei `y = 42,5`: oben und unten
   je 42,5 Einheiten leer, links und rechts null. Das Motiv hat das
   Verhältnis **1,254 : 1** und ist symmetrisch auf ein Quadrat gepolstert —
   ein Artefakt des Exports, keine Gestaltungsentscheidung. Das Luftlogo
   ist randlos (`viewBox="0 0 400,16 249,81"`, Verhältnis **1,602 : 1**).
   Farb- und Weiß-Fassung sind jeweils identisch aufgebaut.
2. **Skaliert wird allein über die Höhe** (`.kopf-marke img { height: 34px;
   width: auto }`, `.anmeldung-logo { height: 56px; width: auto }`). In der
   Kopfleiste füllt das Luftlogo seine 34 px ganz (54,5 × 34 px); das
   Bodenlogo bekommt eine 34 × 34-Schachtel, in der das Motiv nur
   34 × 27,1 px groß ist — es erscheint **schmaler und niedriger
   zugleich**. Sichtbare Fläche: **1 853 gegen 921 px²**, das Luftlogo
   bedeckt rund das Doppelte. Auf der Anmeldeseite dieselbe Relation.

**Behebung — zuerst am Bild, dann erst an der Regel** (Einzelheiten und
Nebenpunkte in 3.6): Nur der leere Rand wird aus dem Rahmen genommen
(`viewBox="0 42.5 420 335"` bzw. ein enger Neuexport), die Zeichnung selbst
bleibt unangetastet. Danach liefert die vorhandene Höhenregel von selbst
ein weitgehend ausgewogenes Bild (42,6 × 34 gegen 54,5 × 34 px; die
verbleibende Differenz ist der ehrliche Unterschied zweier Motive, kein
Fehler). Ob danach noch eine Feinkorrektur nötig ist, wird **am
korrigierten Bild entschieden** (E-S3-12).

**Geltung:** alle vier Darstellungsorte — Kopfleiste sowie Anmeldung,
Passwortvergabe und Zurücksetzen (`.anmeldung-logo`). Die **Favicons**
(`favicon_helicopter.png`, `favicon_nef.png`) sind auf denselben Rand hin zu
prüfen — sie stammen aus denselben Vorlagen.

**Der Beschnitt greift bis auf die Uhr durch** (nachgemessen am
31.08.2026): `tools/uhr-bilder/erzeugen.sh` rastert die Uhr-Bildmarken aus
denselben SVG; der NEF-Faktor 78 % ist genau das Verhältnis der beiden
Seitenverhältnisse und **bleibt nach dem Beschnitt richtig**. Die erzeugten
PNG ändern sich trotzdem (1–2 px, Rundung der halben Einheit — das Ergebnis
ist *genauer*, aber anders). **Wer die SVG beschneidet, muss den Generator
neu laufen lassen** — sonst passen die eingecheckten Kacheln nicht mehr zu
ihrer Quelle, und das fällt niemandem auf. Auslieferung: E-S3-04.

### 1.13 Nachgetragen: die zweite Rückmeldungsrunde (bereits umgesetzt)

Zwei Punkte vom 01.09.2026, **nicht** in `ToDo_Layout.pdf` (die Liste hat
19 Punkte am Stand 9.14.1, diese sind neuer). Beide sind vorab umgesetzt,
mit Web 12.2.1 auf `main` zusammengeführt und am 01.09.2026 bedienerisch
geprüft (Einzelheiten im Changelog zu 12.2.1); sie stehen hier, weil R43
verlangt, dass Einzelkorrekturen im Konzept benannt werden — und damit die
Umsetzung sie nicht ein zweites Mal beschließt:

1. **Das Dateifeld der Sicherungsseite saß am oberen Rand** (gemessen:
   0 px Luft darüber, 19 px darunter). Behoben **am Baustein**
   `input[type=file].feld-eingabe`, nicht an der Seite — also im Sinne des
   „kein Flickwerk" von R43.
2. **Der Hinweis fehlte, dass die erzeugte Datei heruntergeladen wurde** —
   bei Sicherung und Datenexport; die Abschlussmeldung nennt jetzt jeweils
   den Dateinamen.

Damit ist der Konzeptteil von **Backlog Nr. 56 erledigt**; der Punkt ist
nach *Erledigt* verschoben. Für S3 heißt das: Die Stilvergleichs-Basis
enthält diese beiden Änderungen bereits (13 beabsichtigte Abweichungen der
Runde, 35 763 Elementmessungen über 13 Breiten, keine darüber hinaus).

### 1.14 Wechselwirkungen mit Nachbarpaketen

- **S5 (Kopplung umgekehrt):** ersetzt den Geräteabschnitt von
  `einstellungen.php`. S3 lässt ihn deshalb bei der Formular-Durchsicht aus
  (E-S3-03) und nimmt ihn nicht in die Soll-Ist-Liste auf.
- **R50 (Terminologie):** folgt nach S5. S3 formuliert sichtbare Texte, die
  es ohnehin anfasst, **nicht** vorauseilend auf „Backup" um.
- **R48/S5 (Uhr-Auslieferung):** Der R43-Wortlaut „die geänderten Kacheln
  reisen mit der P6-Uhr-Auslieferung (R29) mit" stammt aus Fassung 9 und
  ist von R48 (Fassung 12) überholt — **P6 trägt keine Uhr-Auslieferung
  mehr**; die nächste ist die von S5. E-S3-04 legt das fest.
- **Backlog Nr. 49 (Logofarben):** bewusst liegen gelassen, S4/B1 übernimmt.
  S3 ändert nur den Rahmen (viewBox), keine Farbwerte; der Generatorlauf
  rastert also mit den heutigen — teils falschen — Farben. Das ist bekannt
  und bleibt so bis S4/B1 (E-S3-05).
- **R42-Kleinstpaket:** noch offen; keine gemeinsame Fachdatei mit S3
  (`pair.php`, `devices`, `keyguard.js` gegen Oberfläche und Stylesheet,
  S3 bringt keine Migration mit). Kann neben S3 laufen; gemeinsam ist nur
  die Buchführung (`version.php`, `CHANGELOG.md`).

---

## 2. Entscheidungen

### 2.1 Übernommen aus dem Rahmenplan (entschieden am 31.08.2026)

- **E-R43-1 — Die Sammelleiste übernimmt die Kartenform.** Radius und
  Breite wie die Karte darüber; der negative Randausbruch entfällt; sticky
  Sitz und Trennlinie bleiben. (1.4)
- **E-R43-2 — Die Leistenüberschrift bleibt linksbündig und wird größer.**
  Eine Stufe höher, `--asphalt` statt `--gedaempft`, am geteilten Baustein
  an allen vier Stellen; Versalien-Frage am Bild. (1.7)
- **E-R43-3 — „Winden-Cycles / Flugtag" bleibt.** Die Kachel steht in der
  Luftrettungsansicht, dort ist Flugsprache zulässig (E-P2-04). Kein
  Eingriff; sollte die Wortliste den Fall melden, bekommt er eine Ausnahme
  mit dieser Begründung, keine Umformulierung.

### 2.2 Entscheidungen dieses Konzepts

**E-S3-01 — Verortung des Regelwerks.** Der vertikale Rhythmus wird als
neuer Abschnitt in `docs/Design.md`, Kapitel 6 (Grundregeln)
festgeschrieben — er gilt wie die übrigen Grundregeln für jede Seite,
unabhängig vom Baustein. Kapitel 9 (Bausteine) verweist darauf. Die
Regeltabelle ist **handgeschrieben** und normativ; die vier erzeugten
Tabellen (`tools/design/tabellen.py`) bleiben davon unberührt.

**E-S3-02 — Das Regelwerk selbst.** Je Beziehung genau eine Stufe der
bestehenden Skala; Leitgedanke: **Bindung ist kleiner als Trennung** — was
zusammengehört, steht enger als das, was sich voneinander absetzt. Die
Tabelle ist der Beschluss dieses Konzepts:

| Beziehung | Stufe | Begründung |
|---|---|---|
| Beschriftung → ihr Feld | `--abstand-1` (4 px) | klebt am Feld; alles Größere ließe die Beschriftung zwischen zwei Feldern schweben |
| Überschrift → ihr Inhalt | `--abstand-2` (8 px) | bindet; die Überschrift gehört zum Inhalt darunter, nicht in die Mitte zwischen zwei Blöcke |
| Element → Element derselben Gruppe (Feld → Feld, Zeile → Zeile) | `--abstand-3` (12 px) | der Arbeitsabstand; zugleich die heute häufigste Wahl (29 von 222) |
| Gruppe → nächste Gruppe innerhalb einer Karte; Formular → Formularfuß | `--abstand-4` (16 px) | setzt ab, ohne zu trennen; deckungsgleich mit dem bestehenden `.listen-form-fuss` (`margin-top: var(--abstand-4)`) |
| Karte → Karte; Inhalt → nächste Abschnittsüberschrift | `--abstand-5` (24 px) | trennt; der Wechsel zwischen Sinneinheiten muss größer sein als jeder Abstand innerhalb |

**Keine neuen Token** — die fünf Stufen decken die fünf Beziehungen; sollte
die Umsetzung eine Beziehung finden, die in keiner Zeile aufgeht, ist das
eine F-Frage (K6), keine stille sechste Stufe. **Prüfvorbehalt:** AP1 misst
den Bestand gegen diese Tabelle. Wo eine bestehende, sichtbar richtige
Gestaltung einer Zeile widerspricht, wird die Abweichung offengelegt und
entschieden — Tabelle anpassen oder Bestand umbauen —, bevor AP2 umbaut.
Still angepasst wird keines von beiden.

**E-S3-03 — Benannte Ausnahme: der Geräteabschnitt von
`einstellungen.php`.** S5 ersetzt ihn (R49); S3 lässt ihn bei Durchsicht
und Soll-Ist-Liste aus. Bausteinänderungen wirken dort ohnehin über die
gemeinsamen Klassen.

**E-S3-04 — Die neu gerasterten Uhr-Kacheln reisen mit der
S5-Uhr-Auslieferung.** Der R43-Wortlaut nennt P6; das ist seit R48 überholt
(P6 trägt keine Uhr-Auslieferung mehr, die nächste ist die von S5 —
umgekehrte Kopplung und Vorgabeadresse). S3 macht Beschnitt und
Generatorlauf und checkt die PNG ein; ausgeliefert werden sie mit dem
S5-Uhr-Build.

**E-S3-05 — Logofarben bleiben unangetastet.** Backlog Nr. 49 ist bewusst
liegen gelassen (Beschluss 31.08.2026, S4/B1 übernimmt). S3 nimmt nur den
leeren Rand aus dem Rahmen und räumt den Überstand des Luftlogos auf; kein
Farbwert wird berührt. Dass der Generatorlauf mit den heutigen Farbwerten
rastert, ist benannt und in Kauf genommen.

**E-S3-06 — Leitplanken der Autosuche im Ortsfeld.** Die Suche beim Tippen
läuft **entprellt mit 400 ms** Ruhe nach dem letzten Tastendruck, **ab drei
Zeichen**, mit **höchstens einer offenen Anfrage** (eine laufende wird
abgebrochen, bevor die nächste startet); die Lupe löst weiterhin sofort
aus. Die Umsetzung darf die Entprellzeit am Gerät zwischen 300 und 600 ms
nachstellen und dokumentiert den Endwert hier. Begründung: Photon ist ein
frei betriebener Gemeinschaftsdienst; eine Anfrage je Tastendruck wäre
Missbrauch seiner Gutmütigkeit — und jede Anfrage überträgt die
eingetippten Buchstaben an einen Dritten. **Genau deshalb wird
`docs/Lizenzen.md` 6.2 im selben Paket nachgezogen:** Dort steht heute als
Zusage, die Suche laufe „nicht bei jedem Tastendruck" und nur auf
ausdrückliches Auslösen; der zweite Teil stimmt künftig nicht mehr. Die
neue Formulierung sagt ehrlich, dass das Tippen im Ortsfeld — und nur dort —
entprellte Suchanfragen auslöst, und dass das Feld freiwillig bleibt. Die
E2E-Zusage ist nicht berührt: Gesucht wird, bevor aus der Eingabe ein
gespeicherter (verschlüsselter) Wert wird — wie heute beim Lupen-Klick.

**E-S3-07 — Die Demo-Sperre sitzt auf dem Server, nicht im Aussehen.** Die
Kontoseite des Demo-Kontos zeigt die Bearbeitung ausgegraut und ohne
Sicherungsteil; **abgewiesen wird aber im Schreibweg** — ein direkt
abgesetzter POST auf die Speicher- und Sicherungsaktionen des Demo-Kontos
wird serverseitig verweigert, mit verständlicher Meldung. Ein `disabled`
im Markup allein wäre Kulisse. Erkannt wird das Demo-Konto über den
vorhandenen zentralen Weg (die Anwendung kennt ihr Demo-Konto seit S1);
ein zweites Erkennungsmerkmal wird nicht eingeführt.

**E-S3-08 — Filterausblendung an der Filtererzeugung.** Die Suche fragt
den Bestand einmal je Seitenaufruf ab — **eine** Abfrage über alle
katalogisierten Filterfelder, kein Feld-für-Feld-Scan — und die
Filtererzeugung aus `mission_fields.php` lässt Felder ohne Bestand weg.
Kein Sonderfall je Rolle, keine Liste im Code, welche Felder „optional"
sind: Was der Katalog kennt und der Bestand füllt, erscheint. Die
Z3-Grenzen aus S2 gelten (die Abfrage bleibt unter den Serverbudgets, auch
beim 5 000-Einsätze-Konto).

**E-S3-09 — Höhenanzeige.** Die Höhe erscheint nur bei `kind = 'air'`,
beschriftet („Höhe 1917 m"). Bei Bodeneinsätzen entfällt die Zeile ersatzlos
— eine leere beschriftete Zeile wäre derselbe Fehler in neu.

**E-S3-10 — Karte auf der Tagesübersicht.** `index.php` zeigt nur den
Standort des Rettungsmittels, in derselben Größe wie die Einsatzansicht,
ohne Beschriftung; Zielkliniken erscheinen dort nicht. In allen
Kartenansichten: `iconSize` wird ausdrücklich gesetzt (1.9), das orange
Einsatzort-Symbol verliert die weiße Umrandung und wird etwas verkleinert.

**E-S3-11 — Kein neuer Baustein ohne Freigabe.** Nach heutigem Stand
braucht S3 keinen neuen Baustein — alle Punkte arbeiten an vorhandenen.
Stellt die Umsetzung fest, dass doch einer fehlt, gilt die Freigaberegel
aus `Design.md` 1.2 (Mockup, ausdrückliche Freigabe). Das **Zusammenfassen
verstreuter Einzelregeln unter einen vorhandenen Baustein** ist kein neuer
Baustein, sondern Aufräumen und braucht keine Freigabe.

**E-S3-12 — Zwei Bildentscheide liegen bewusst in der Umsetzung.** (a)
Versalien und Sperrung der größeren Leistenüberschrift (E-R43-2); (b) eine
etwaige Feinkorrektur der Logogrößen nach dem Beschnitt — nur falls nötig,
dann als hergeleitete Regel in `Design.md`, mit der Kopfleistenhöhe von
56 px als Schranke. Beides sind keine offenen F-Fragen: Die Entscheidung
ist delegiert, ihr Ergebnis wird im Konzept-Nachtrag dokumentiert.

**E-S3-13 — Platzhalter-Pflegeregel.** Platzhaltertexte in Formularen
tragen ausschließlich Phantasienamen (Orte, Personen, Kliniken). Die Regel
wird in `docs/Design.md` festgeschrieben (bei den Formular-Bausteinen,
Kapitel 9.7/9.13) und gilt ab S3 für jede neue Stelle.

---

## 3. Ausarbeitung

### 3.1 Regelwerk und Ausmessung (zu AP1)

Die Ausmessung wiederholt die R43-Zählung auf dem heutigen Stand (Web
12.2.1) und schlüsselt sie **je Beziehung** auf: Für jede der fünf Zeilen
aus E-S3-02 wird gezählt, wie viele Stellen heute schon die Soll-Stufe
tragen und wie viele abweichen. Das Ergebnis ist die **Arbeitsliste von
AP2** — und zugleich die Soll-Ist-Liste des Stilvergleichs: Jede geplante
Änderung steht vorher fest, jede Abweichung darüber hinaus ist
unbeabsichtigt und wird geklärt, bevor committet wird.

In `Design.md` kommt neben der Tabelle ein kurzer Anwendungsabsatz: Woran
erkenne ich die Beziehung? (Die Frage ist immer: Was ist das Nächste, das
folgt — gehört es noch zu mir oder ist es das Nächste?) Dazu das
Anti-Muster: ein Abstand, der an der Seite statt am Baustein hängt.

### 3.2 Sammelleiste (zu AP3)

`ui_speichern_leiste()` stellt die Markup-Reihenfolge um (Zählung vor dem
Knopf); `.speichern` verliert den negativen Randausbruch und erhält Radius
und Breite der Karte (dieselben Token, keine eigenen Werte). Sticky Sitz
und obere Trennlinie bleiben unverändert. Zu prüfen sind **beide**
Verwendungen (NutzerInnen-Liste, Spurenseite) und die Speichern-Form
schmutziger Formulare — der Baustein ist geteilt, eine Änderung trifft
alle Stellen (`Design.md` 9.9).

### 3.3 Navigation (zu AP4)

Menüpunkte: Grundschnitt normal, der ausgewählte Punkt fett — die
Auszeichnung wandert von „alle" zu „einer", die Erkennbarkeit des aktiven
Punkts steigt dadurch. `.leiste-kopfzeile`: Schriftstufe eine Stufe höher
(aus der geschlossenen Skala, `Design.md` 5), Farbe `--asphalt`. Nach der
Änderung `python3 tools/screenshots/kontrast.py` — die Kombination
`--asphalt` auf der Leistenfläche muss AA erreichen, gegen die
tatsächliche Fläche gerechnet, nicht gegen Weiß.

### 3.4 Einsatzansicht (zu AP6)

Die Höhenbedingung sitzt an der Ausgabestelle der Einsatzansicht, nicht im
Feldkatalog — die Höhe bleibt gespeichert und exportiert wie bisher, nur
die Anzeige ist bedingt (R5: gespeicherte Namen und Formate unangetastet).
Schloss-Plakette und -Symbole benutzen die vorhandenen Bausteine
(`Design.md` 9.6, 9.15); die vertikale Zentrierung wird am Baustein
gerichtet, damit alle Schloss-Stellen mitziehen. Beim Entfernen des
Hinweisbalkens wird das Handbuch an der Stelle gegengelesen: Die Aussage
(„entsperrt bis zur Abmeldung") muss dort stehen bleiben bzw. hinkommen —
und der Balken wird aus allen Dokument-Erwähnungen ausgetragen.

### 3.5 Karte (zu AP7)

`geo.js` setzt beim Erzeugen der DivIcons `iconSize` ausdrücklich auf die
Kastenmaße, sodass `iconAnchor` wieder auf der Kastenmitte liegt —
unabhängig davon, was künftig neben dem Kasten steht. Die Beschriftungen
von Standort und Zielklinik entfallen im Markup (nicht per `display:none` —
was nicht angezeigt wird, wird nicht erzeugt); der Innenabstand des
`.geo-schild`-Kastens wird verkleinert, das Symbol bleibt weitgehend gleich
groß. Die Probe für den Versatz: derselbe Marker („Klinikum Immenstadt"-Fall
aus dem Referenzdatensatz) in mehreren Zoomstufen vor und nach der
Änderung — der Anker darf sich beim Zoomen nicht gegenüber der Koordinate
verschieben (0 px Versatz, gemessen, nicht gesehen).

### 3.6 Logos und Ableitungen (zu AP11)

Reihenfolge im Paket, weil eines am anderen hängt:

1. **Beschnitt** der Boden-SVG (Farbe und Weiß gleichermaßen):
   `viewBox="0 42.5 420 335"` oder ein enger Neuexport — nur der Rahmen,
   nicht die Zeichnung.
2. **Aufräumen** des Luftlogos: rund 156 Einheiten Zeichnung rechts
   außerhalb des Rahmens, heute folgenlos weggeschnitten, aber eine Falle
   für jeden, der den Rahmen später anfasst.
3. **`ui.php` gibt die Bildmaße je Logo aus** statt fest
   `width="54" height="34"` für beide — das feste Paar ist das Verhältnis
   des Luftlogos; für das Bodenlogo reserviert der Browser den falschen
   Kasten, und beim Laden springt das Layout.
4. **Favicons prüfen** (`favicon_helicopter.png`, `favicon_nef.png`): aus
   denselben Vorlagen, also auf denselben leeren Rand hin ansehen und ggf.
   neu ableiten.
5. **`tools/uhr-bilder/erzeugen.sh` neu laufen lassen** und die geänderten
   PNG einchecken (Erwartung: 1–2 px Verschiebung je Kachelstufe, 3.6 der
   Rahmenplan-Messung). Auslieferung mit S5 (E-S3-04).
6. **Nachmessen** mit `tools/screenshots/` für **beide** Logo-Wahlen an
   allen vier Darstellungsorten; `svg.getBBox()`-Gegenprobe, dass Zeichnung
   und Rahmen jetzt deckungsgleich sind.

Erst danach, am korrigierten Bild: die Feinkorrektur-Frage (E-S3-12 b).

### 3.7 Suche (zu AP9)

Die Bestandsabfrage liefert je katalogisiertem Filterfeld, ob der Bestand
des Kontos einen Wert führt (eine Abfrage, gruppiert; kein N+1). Die
Filtererzeugung überspringt Felder ohne Bestand. Grenzfälle, die die
Umsetzung prüft: leeres Konto (keine Filter außer den immer sinnvollen wie
Zeitraum), Bestand entsteht neu (Filter erscheint beim nächsten Aufruf),
Suche per URL-Parameter auf ein ausgeblendetes Feld (wird ignoriert, kein
Fehler). Die Leistenüberschrift „Filter" der Suche ist eine der vier
`.leiste-kopfzeile`-Stellen (E-R43-2) — die beiden Pakete AP4 und AP9
berühren sich dort nur im Aussehen, nicht im Verhalten.

### 3.8 Demo-Konto (zu AP10)

Anzeige: Kontoseite ohne Sicherungsblock und -aktionen, Bearbeitung
sichtbar ausgegraut, Seite bleibt aufrufbar; Anzeigename „Demo NutzerIn".
Sperre: in den Schreibwegen der Kontoseite (Speichern, Sicherungsaktionen)
wird das Demo-Konto abgewiesen — geprüft mit direktem POST, nicht nur durch
Ansehen des Formulars. Der Reiter „Demo-Konto" (Anlegen, Zurücksetzen)
bleibt der eine Verwaltungsweg. Beim Paketabschluss: R25 im Rahmenplan um
die Sperre ergänzen.

---

## 4. Offene Fragen

Bei Übergabe **keine.** Die drei Gestaltungsfragen der Liste sind
entschieden (E-R43-1 bis E-R43-3), die Verortungs- und Mechanikfragen dieses
Konzepts in E-S3-01 bis E-S3-13; zwei Bildentscheide liegen ausdrücklich
delegiert in der Umsetzung (E-S3-12). Fragen, die während der Umsetzung
entstehen, werden hier als F-S3-01 ff. eingetragen und vor Umsetzungsbeginn
des betroffenen Pakets entschieden (K6) — das gilt insbesondere für den
Prüfvorbehalt aus E-S3-02 (Bestand widerspricht einer Regelzeile).

### Aus der Umsetzung

Der Prüfvorbehalt hat gegriffen: Die Ausmessung in AP1 hat **16
Abweichungen** zwischen Bestand und Regeltabelle gefunden, gebündelt in vier
Ursachen (Einzelheiten im Umsetzungsstand zu AP1). Drei davon sind nicht
mechanisch zu entscheiden, sondern verlangen eine Wahl zwischen „Bestand
umbauen" und „Tabelle anpassen".

**F-S3-01 — Der Kern: Kartenabstand und Feldabstand.**
*Befund:* `.karte` und `.feld` tragen beide `--abstand-4` (16 px). Der
Abstand zwischen zwei Karten ist damit genauso groß wie der zwischen zwei
Feldern **innerhalb** einer Karte. Die Regel verlangt 24 px zwischen Karten
und 12 px zwischen Feldern. Die Grundform `label` trägt die 12 px schon
heute — die Anwendung widerspricht sich also bereits selbst.
*Betroffen:* 5 Regeln (`.karte`, `.meldung`, `.geo`, `.demo-hinweis`,
`.feld`), sichtbar auf **jeder** Seite.
*Entschieden am 01.09.2026:* **Bestand umbauen, Tabelle bleibt.** Das ist
der Befund, wegen dessen die Rückmeldungsliste diese Frage überhaupt
gestellt hat; ihn stehen zu lassen hieße, das Paket ohne seinen Kern
auszuliefern. → AP2.

**F-S3-02 — Überschrift zu ihrem Inhalt, insbesondere die Titelzeile.**
*Befund:* Sechs Überschriftenregeln stehen auf 12 oder 16 px, die Regel
sagt 8 px. Fünf davon sind unstrittig (`.text h1`, `.text h2`,
`.blatt-titel`, `.listen-form-titel`, `.vorschau > h4`) — `.text h3` trägt
die 8 px bereits, die Skala ist also nur auf den höheren Ebenen nicht
durchgehalten. **Strittig ist `.titelzeile`:** Sie ist keine bloße
Überschrift, sondern trägt Rückweg, Titel, Unterzeile **und** Aktionsknöpfe
von 44 px Höhe. Bei 8 px stünde ein Knopf fast auf der ersten Karte.
*Entschieden am 01.09.2026:* **Die fünf unstrittigen Stellen gehen auf 8 px;
`.titelzeile` bleibt bei 16 px** und wird als **begründete Ausnahme** in
`Design.md` vermerkt: Wo eine Überschrift Bedienelemente trägt, muss der
Abstand darunter den Knopf freistellen — die Beziehung ist dann nicht
„Überschrift → Inhalt", sondern „Gruppe → nächste Gruppe" (`--abstand-4`).
Das ist keine sechste Stufe, sondern die Zuordnung derselben Zeile 4 auf
einen Fall, den die Tabelle nicht benannt hatte. → AP2, mit Nachtrag in
`Design.md`.

**F-S3-03 — Zeilen in einem Textblock.**
*Befund:* `.text li` und `.suchsyntax li` stehen auf 4 px, die Regelzeile 3
(Element → Element derselben Gruppe) verlangt 12 px. Für Aufzählungen im
Fließtext wären 12 px so viel wie zwischen zwei Absätzen — die Liste verlöre
genau die Bindung, die sie zur Liste macht.
*Entschieden am 01.09.2026:* **Tabelle präzisieren.** Regelzeile 3 gilt für
**Bausteine**, nicht für Zeilen innerhalb eines zusammenhängenden
Textblocks; ein `<li>` im Fließtext ist eine Zeile, kein Element. Der Satz
kommt in `Design.md` zur Regelzeile 3 dazu. Die beiden `li`-Regeln bleiben
damit unverändert; die verbleibenden drei Fälle des Bündels D
(`label:has(> input[type=checkbox])`, `.suchzeile`, `.phasen-eingabe`) sind
echte Abweichungen und gehen auf 12 px. → AP2.

---

## 5. Arbeitspakete

Reihenfolge wie nummeriert; AP1 muss vor AP2 liegen (die Regel vor ihrer
Anwendung), AP3–AP11 sind untereinander weitgehend unabhängig, werden aber
**eines nach dem anderen** bearbeitet (CLAUDE.md 7). Je Paket ein Commit
(K7), Konzeptfortschreibung und Prüfmittel im Paketabschluss (Abschnitt 6);
`server/version.php` und `docs/CHANGELOG.md` je Paket nach CLAUDE.md 2.
**Gepusht wird einmal am Ende der Phase, nach ausdrücklicher Bestätigung** —
ein Push auf `main` deployt sofort. S3 bringt **keine Migration** mit;
`update.php` ist nach dem Deploy nicht nötig.

**AP1 — Regelwerk und Ausmessung.** Rhythmus-Regel (E-S3-02) und
Platzhalter-Pflegeregel (E-S3-13) in `docs/Design.md`; Ausmessung des
Bestands je Beziehung (3.1); daraus die Arbeits- und Soll-Ist-Liste für
AP2.
*Abnahme:* Regeltabelle mit Begründung je Zeile steht in `Design.md`
Kapitel 6; die Ausmessung liegt mit Zahlen vor (je Beziehung: konform /
abweichend); Widersprüche zwischen Bestand und Regel sind als Liste
offengelegt und entschieden (ggf. F-S3-…), bevor AP2 beginnt. Noch keine
Stylesheet-Änderung in diesem Paket.

**AP2 — Rhythmus an den Bausteinen, Formularfuß.** Umsetzung der
AP1-Arbeitsliste an Klassen und Bausteinen; „Profil speichern" in den
Formularfuß-Baustein; Durchsicht aller Formularseiten auf freistehende
Absendeknöpfe (ohne Geräteabschnitt, E-S3-03).
*Abnahme:* Stilvergleich: Abweichungen = Soll-Ist-Liste aus AP1, keine
darüber hinaus; Durchsicht dokumentiert mit Fundzahl und Stellen; kein
freistehender Absendeknopf mehr (Zahl: 0, ausgenommen E-S3-03);
`tools/screenshots/` der berührten Seiten ohne Überlauf und mit Knopfhöhe
44 px.

**AP3 — Sammelleiste (E-R43-1).** Markup-Reihenfolge, Kartenform,
Randausbruch weg (3.2).
*Abnahme:* Beide Verwendungen und die Speichern-Form geprüft (drei
Bedienwege); in acht Breiten deckt sich die Leistenbreite mit der Karte
darüber (gemessen im Bilderlauf, nicht nach Augenmaß); sticky Verhalten
unverändert.

**AP4 — Navigation und Leistenüberschrift (E-R43-2).** Menüpunkte normal /
aktiv fett; `.leiste-kopfzeile` eine Stufe höher und `--asphalt`, an allen
vier Stellen; Versalien-Bildentscheid fällen und dokumentieren.
*Abnahme:* Vier beabsichtigte Abweichungen im Stilvergleich, keine fünfte;
`kontrast.py` für die geänderte Farbverwendung: AA erreicht, gegen die
tatsächliche Fläche; Bildentscheid mit Begründung im Konzept-Nachtrag.

**AP5 — Tabellen und Kacheln (B, I).** NutzerInnen-Liste (Zentrierung über
Spaltenklassen, Zeilentrenner volle Breite); Tagesübersicht (Dauer
einzeilig, Nr./Beginn/Alter zentriert, „Sekundär-/transport");
Kennzahl-Kacheln vertikal zentriert.
*Abnahme:* Kein `:nth-child` für Spalten (Zählung: 0 neue, keine
bestehenden an diesen Tabellen); Bilderlauf: Dauer bei 360 px einzeilig;
Zeilentrenner reicht über die volle Breite (Bildbeleg); Kacheln in den
Zeitraum-Ansichten zentriert.

**AP6 — Einsatzansicht (G).** Höhe nur Luft, beschriftet
(Funktionsänderung); Schlosssymbole zentriert; Plakette am Block
„Einsatz"; Schloss bei „Name/Geboren"; Hinweisbalken entfällt,
Handbuch-Abgleich (3.4).
*Abnahme:* Je ein Luft- und ein Bodeneinsatz des Referenzdatensatzes im
Browser geprüft (Höhe da/beschriftet bzw. Zeile fehlt); alle
verschlüsselten Blöcke tragen Plakette bzw. Schloss (Zählung im
Prüfprotokoll); der Balken ist aus Oberfläche **und** Doku ausgetragen;
Wortliste für die geänderten Texte.

**AP7 — Karte (H).** `iconSize` ausdrücklich; Beschriftungen weg, Kasten
enger; `index.php` nur Rettungsmittel-Standort; orange Symbol ohne weiße
Umrandung, etwas kleiner (3.5).
*Abnahme:* Versatzprobe „Klinikum Immenstadt" über mehrere Zoomstufen:
0 px Ankerwanderung, gemessen; `index.php` zeigt keine Zielkliniken mehr;
Einsatzansicht und Tagesübersicht zeigen dasselbe Standortsymbol in
derselben Größe; Konsolenfehler 0.

**AP8 — Formularbausteine und Ortssuche (E).** Radioliste der Logo-Wahl
schlicht; Platzhalter-Bestand vollständig auf Phantasienamen; Autosuche
nach E-S3-06 (Funktionsänderung); `docs/Lizenzen.md` 6.2 nachgezogen.
*Abnahme:* Platzhalter-Erhebung: 0 reale Orte/Namen verbleibend (Liste der
getauschten Stellen im Paketabschluss); Autosuche: Entprellzeit und
Mindestlänge nachgemessen (Netzwerkmitschnitt: beim flüssigen Tippen eines
Ortsnamens höchstens eine Anfrage), Lupe löst weiterhin sofort aus, beide
Ortsfelder (Standort, Zielklinik) geprüft; Radioliste an allen Stellen des
Bausteins angesehen, nicht nur an der Logo-Wahl.

**AP9 — Suche (J).** Filterausblendung an der Filtererzeugung nach E-S3-08
(Funktionsänderung).
*Abnahme:* Referenzbestand: Feld mit Bestand erscheint, Feld ohne Bestand
fehlt (Gegenprobe: Bestand anlegen → Feld erscheint); URL-Parameter auf
ausgeblendetes Feld wird ignoriert; die Bestandsabfrage bleibt eine
Abfrage (kein N+1, im Log nachgesehen); Messung am 5 000-Einsätze-Konto
innerhalb der Z3-Grenzen.

**AP10 — Demo-Konto (D).** Sperre nach E-S3-07 (Funktionsänderung),
Anzeigename „Demo NutzerIn"; R25-Nachtrag im Rahmenplan.
*Abnahme:* Demo-Kontoseite aufrufbar, ausgegraut, ohne Sicherungsteil;
direkter POST auf Speichern/Sicherungsaktionen wird serverseitig
abgewiesen (Meldung geprüft); ein normales Konto verhält sich unverändert
(Gegenprobe); Reiter „Demo-Konto" funktioniert wie bisher.

**AP11 — Logos, Favicons, Uhr-Kacheln (K).** Nach 3.6, Schritte 1–6.
*Abnahme:* `getBBox`-Gegenprobe: Zeichnung = Rahmen bei allen vier
SVG-Fassungen; Bilderlauf beider Logo-Wahlen an den vier
Darstellungsorten; kein Layoutsprung mehr beim Laden (Maße je Logo im
Bild-Tag); Flächenverhältnis der beiden Logos in der Kopfleiste
nachgerechnet (Erwartung ≈ 42,6 × 34 gegen 54,5 × 34 px);
`erzeugen.sh`-Lauf eingecheckt, Kacheln passen zur Quelle; Favicon-Befund
dokumentiert (geändert oder begründet unverändert); kein Farbwert
angefasst (Diff zeigt nur Rahmen/Struktur).

**AP12 — Abschluss.** Doku-Konsolidierung (Abschnitt 7), Backlog-Pflege,
Prüfmittel-Endlauf in der richtigen Reihenfolge (erst Code, dann Doku,
**dann** Wortliste, Vollständigkeit, Kontraste, Bilderlauf — CLAUDE.md 6),
Prüfdokument nach K9, Statuszeile im Rahmenplan.
*Abnahme:* Endzahlen benannt und mit Gegenstand („was wurde gemessen"):
Bilderlauf über die Seitenliste in acht Breiten mit 0 Überlauf / 0
Konsolenfehler / 0 Knopfhöhenabweichungen — und der Gegenprobe aus der
LIESMICH gegen den F-P3-AQ-Fall (nicht 176-mal die Anmeldeseite);
Wortliste 0/0/0 (ggf. mit der E-R43-3-Ausnahme); Vollständigkeitsprüfung
gelesen, nicht nur gezählt; Stilvergleich-Endstand = konsolidierte
Soll-Ist-Liste; Prüfliste des Prüfdokuments vollständig.

---

## 6. Prüf- und Regressionspflichten

- **Stilvergleich** (`tools/stilvergleich/`) bei jedem Paket, das
  `style.css` berührt: Ergebnis ist keine Null, sondern eine Liste, die
  gegen die geplanten Änderungen gehalten wird; jede Abweichung darüber
  hinaus wird geklärt, **bevor** committet wird. Die Basis enthält bereits
  die 13 Abweichungen der zweiten Rückmeldungsrunde (1.13).
- **`tools/screenshots/`** (acht Breiten) je Paket für die berührten
  Seiten; am Ende der volle Lauf, **für beide Logo-Wahlen**. Bei jeder
  Zahl dazusagen, was gemessen wurde; die unabhängige Gegenprobe der
  LIESMICH fahren (Lehre aus F-P3-AQ).
- **`kontrast.py`** nach jeder Farbänderung (hier: `--asphalt` auf der
  Leistenfläche), gegen die tatsächliche Fläche, Zielwert AA.
- **`tools/vollstaendigkeit/`** je Paket: keine Klasse ohne Regel, keine
  Regel ohne Klasse, kein Wert außerhalb `:root`; die Liste wird gelesen,
  nicht nur gezählt (F-P3-BA).
- **`tools/wortliste/`** bei jeder Textänderung; erwartet 0 Treffer
  außerhalb der Ausnahmen und 0 ungenutzte Ausnahmen; der
  E-R43-3-Fall bekommt, falls gemeldet, eine begründete Ausnahme.
- **Browserprüfung der vier Funktionsänderungen** einzeln, mit Gegenprobe
  (Suche mit/ohne Bestand, Luft-/Bodeneinsatz, Demo-/Normalkonto,
  Tippen/Lupe) — der Stilvergleich misst statisches Markup, keine
  Bedienzustände.
- **Reihenfolge:** Die Prüfmittel laufen **zuletzt**, nach Code und Doku
  des jeweiligen Pakets; ein Lauf vor der letzten Änderung misst einen
  Stand, den es nicht mehr gibt (Lehre aus O9c, Web 9.10.1).
- Der **Messstand** aus S2 (`tools/messstand/`) steht bereit, falls AP9
  eine Messung am 5 000-Einsätze-Konto braucht; die Z3-Budgets gelten
  unverändert.

---

## 7. Dokumentations- und Backlog-Pflege

- **`docs/Design.md`**: Rhythmus-Regel (Kapitel 6), Platzhalter-Pflegeregel
  (Kapitel 9), Ergebnis der beiden Bildentscheide (E-S3-12), etwaige
  Baustein-Nachträge; die erzeugten Tabellen nur über
  `python3 tools/design/tabellen.py alle`. Der Änderungsverlauf (Kapitel
  12) wird je Paket fortgeschrieben.
- **`docs/Handbuch.md`**: Höhenanzeige (nur Luft), Wegfall des
  Hinweisbalkens (Aussage bleibt im Handbuch), Autosuche im Ortsfeld,
  Demo-Konto-Sperre. Entferntes wird ausgetragen.
- **`docs/Lizenzen.md`** 6.2: neue, ehrliche Beschreibung der Autosuche
  (E-S3-06).
- **`docs/CHANGELOG.md`** je Version, Präfix `Web`, erklärende Prosa mit
  Begründung; **`server/version.php`** je Paket (Zählweise nach
  CLAUDE.md 2 — überwiegend Neben- und Korrekturstufen; die Versionszahlen
  legt die Umsetzung fest, K3).
- **Rahmenplan**: R25-Nachtrag (Demo-Sperre, AP10); Statuszeile S3 am
  Phasenende (K5); Prüfdokument nach K9.
- **Backlog**: Nr. 56 ist mit diesem Konzept nach *Erledigt* verschoben
  (1.13). Neue Funde während S3 werden gesammelt, nicht sofort behoben
  (K4), und hinten angehängt; die Nummern 4, 6 und 7 bleiben frei.

---

## 8. Fehlerfunde

Während der Umsetzung gefundene Fehler werden hier gesammelt (F-S3-A ff.)
und nicht sofort behoben, außer der Fund blockiert die laufende Arbeit (K4).

**F-S3-A — Die Tagesübersicht baut ihre Einsatztabelle ein zweites Mal.**
`assets/missiontable.js` führt die Spaltendefinitionen der drei
Einsatztabellen an einer Stelle; `index.php` baut seine Zeilen daneben noch
einmal selbst zusammen (`tr.innerHTML = …`). Die beiden liefen auseinander:
Die Dauerspalte trug in `missiontable.js` seit F-N1-G die Klasse
`zeit-spalte`, in `index.php` nicht — deshalb brach dort „1h 06min" um.
**Behoben ist die Folge** (die Klasse steht jetzt in beiden), **nicht die
Ursache**. Solange es zwei Aufbauten gibt, wird die nächste Änderung wieder
nur an einem ankommen. Nicht in S3 zu machen: Die Vereinheitlichung berührt
Sortierung, Sortierblatt und die Kachelform. **Gehört in den Backlog.**

**F-S3-B — Ein Meldungston, den es nicht gibt, ergab einen ungestalteten
Kasten.** `ui_meldung_markup()` setzte die Klasse aus dem übergebenen Wort
zusammen (`'meldung-' . $ton`); ein unbekannter Ton führte zu einer Klasse
ohne Regel im Stylesheet — weiß, ohne Fläche, ohne Fehlermeldung.
`tag_spuren.php` benutzte an zwei Stellen den Ton `hinweis`, den es nie gab.
Die Vollständigkeitsprüfung findet das **nicht**, weil sie Klassen als
Literale sucht und diese zur Laufzeit entsteht. **Behoben:** Die Funktion
prüft den Ton selbst und wirft bei einem unbekannten; die beiden Stellen
tragen jetzt `info` bzw. den neuen Ton `schutz`. Behoben statt gesammelt,
weil derselbe Fund die Arbeit an derselben Seite blockierte (K4).

**F-S3-C — Der Spurenseite fehlte das Seitengerüst.** `tag_spuren.php` rief
`ui_seite_start()` und schrieb ihren Inhalt danach unmittelbar in den
`<body>` — ohne `ui_geruest_start()`. Als einzige angemeldete Seite hatte sie
damit weder die Diensttag-Leiste noch `.rahmen`/`.inhalt` und deren
seitlichen Innenabstand; gemessen bei 412 px saß die linke Kante von Titel,
Karte und Kartenbaustein bei **0 statt 12 px**. **Behoben.**

> **Warum kein Prüfmittel das gefunden hat.** Der Bilderlauf misst
> waagerechten Überlauf (`scrollWidth > innerWidth`) — eine Seite ohne
> Innenabstand läuft nicht über, sie ist nur randlos. Die
> Vollständigkeitsprüfung fragt nach Klassen ohne Regel, nicht nach Seiten
> ohne Gerüst. Der Stilvergleich misst Markup-Proben, kein
> zusammengesetztes Seitengerüst. **Gefunden hat es ein Mensch auf einem
> Telefon.** Das ist die Lücke, die im Prüfdokument benannt gehört, und der
> Kandidat für ein neues Prüfmittel: „Jede angemeldete Seite ruft
> `ui_geruest_start()`" ist eine Frage, die sich am Quelltext beantworten
> lässt. **Gehört in den Backlog.**

---

## 9. Zuarbeiten

Für die Umsetzung wird keine Zuarbeit gebraucht — keine Migration, kein
Gerät, kein externer Dienst. Es bleibt die **Freigabe dieses Konzepts**
(Rahmenplan Abschnitt 7, „Freigabe je Phasenkonzept") und am Phasenende
die **Deploy-Freigabe** (K7; ein Push auf `main` deployt sofort, S3 braucht
kein `update.php`).

---

## 10. Umsetzungsstand

Fortlaufend geführt, ein Abschnitt je Arbeitspaket: was erledigt ist,
welche Probleme auftraten und wie sie gelöst wurden, welche Entscheidungen
dabei gefallen sind — dazu je Paket der Prüfstand (was wurde womit geprüft,
mit Zahl; was steht aus und auf welchem Weg).

---

### AP1 — Regelwerk und Ausmessung

**Stand:** Regelwerk geschrieben, Ausmessung gefahren, Soll-Ist-Liste steht.
**Offen:** drei Widersprüche zwischen Bestand und Regeltabelle (F-S3-01 bis
F-S3-03) — sie sind nach E-S3-02 vor AP2 zu entscheiden.

#### Was erledigt ist

- **`docs/Design.md`, Kapitel 6:** neuer Abschnitt „Der vertikale Rhythmus" —
  Leitgedanke, die Regeltabelle aus E-S3-02 mit Begründung je Zeile, der
  Anwendungsabsatz („Was ist das Nächste, das folgt — gehört es noch zu mir
  oder ist es das Nächste?"), die Abgrenzung Zwischenraum gegen Polsterung
  und das Anti-Muster (Abstand an der Seite statt am Baustein).
- **`docs/Design.md`, Kapitel 9.7:** Platzhalter-Pflegeregel (E-S3-13), mit
  Querverweis in 9.13 (Ortsfeld).
- **Ausmessung** des Stylesheets auf dem Stand Web 12.2.1.
- **Keine Stylesheet-Änderung** — wie in AP1 vorgesehen.

#### Die Ausmessung

Gemessen wurde `server/assets/style.css` (2 828 Zeilen) mit einem eigenen
Leser, der Kommentare entfernt, Regelblöcke zerlegt und bei den
Kurzschreibweisen den **senkrechten** Anteil herauszieht (`margin: a b` → a;
`margin: a b c d` → a und c). Ohne diesen Schritt fehlen genau die
Überschriftenregeln `.text h2` und `.text h3`, die in der Kurzform stehen.

| Größe | Zahl |
|---|---|
| Abstandsdeklarationen gesamt (`margin*`, `padding*`, `gap*`) | 269 |
| davon mit senkrechter Wirkung und `--abstand-N` | 182 |
| davon **Zwischenraum** (senkrechte `margin`, `row-gap` einer Spalte/eines Rasters) — der Gegenstand der Regel | **74** |
| Abstandsdeklarationen mit einem **Rohwert** | **0** |

**Eine Zahl aus dem Konzept stimmt nicht mehr.** 1.1 nennt „222
Deklarationen aus Token, zwei Stellen mit Rohwert". Heute sind es 226
token-gestützte Deklarationen und **null** Rohwerte. Der Unterschied ist
erklärbar und kein Fehler der R43-Messung: Sie lag auf Web 9.14.1; die
zweite Rückmeldungsrunde (1.13, Web 12.2.1) hat eine der beiden Stellen
mitgenommen, und die zweite war die 1-px-Linie — eine Rahmenstärke, keine
Abstandsdeklaration. **Der Befund von 1.1 bleibt damit richtig, und zwar
schärfer als dort formuliert:** Die Skala wird nicht bloß „überwiegend"
eingehalten, sie wird **ausnahmslos** eingehalten. Es liegt an keiner
einzigen krummen Zahl.

Nennungen je Stufe über alle 269 Deklarationen: `--abstand-1` 47,
`--abstand-2` 71, `--abstand-3` 93, `--abstand-4` 50, `--abstand-5` 12.

#### Soll-Ist je Beziehung

Grundlage sind die 74 Zwischenraum-Deklarationen. `padding` ist bewusst
nicht dabei — es ist Polsterung, nicht Verhältnis (so jetzt auch in
`Design.md` festgehalten).

| Beziehung (E-S3-02) | Soll | konform | abweichend |
|---|---|---|---|
| Beschriftung → ihr Feld | `--abstand-1` | 12 | 0 |
| Überschrift → ihr Inhalt | `--abstand-2` | 5 | **6** |
| Element → Element derselben Gruppe | `--abstand-3` | 24 | **6** |
| Gruppe → Gruppe; Formular → Formularfuß | `--abstand-4` | 15 | 0 |
| Karte → Karte; Inhalt → Abschnittsüberschrift | `--abstand-5` | 5 | **4** |
| **Summe** | | **61** | **16** |

(61 + 16 = 77 statt 74: Drei Deklarationen in Kurzschreibweise tragen zwei
senkrechte Werte — `.text h2`, `.text h3`, `.sd-rolle` — und werden je Ende
einmal gezählt.)

#### Die 16 Abweichungen, nach Ursache gebündelt

**Bündel A — der Kartenabstand ist zu klein** (4 Stellen, heute
`--abstand-4`, Soll `--abstand-5`):

| Zeile | Regel | ist | soll |
|---|---|---|---|
| 862 | `.karte { margin-bottom }` | 16 px | 24 px |
| 1039 | `.meldung { margin-bottom }` | 16 px | 24 px |
| 1438 | `.geo { margin-bottom }` | 16 px | 24 px |
| 1354 | `.demo-hinweis { margin-bottom }` | 16 px | 24 px |

**Bündel B — der Feldabstand ist zu groß** (1 Stelle, heute `--abstand-4`,
Soll `--abstand-3`):

| Zeile | Regel | ist | soll |
|---|---|---|---|
| 1124 | `.feld { margin-bottom }` | 16 px | 12 px |

**A und B sind derselbe Befund von zwei Seiten.** Heute gilt
`.karte` = `.feld` = 16 px: Der Abstand zwischen zwei Karten ist **genauso
groß** wie der zwischen zwei Feldern innerhalb einer Karte. Damit sagt die
Fläche nichts mehr darüber, was wozu gehört — „Bindung ist kleiner als
Trennung" ist an der wichtigsten Stelle der Anwendung verletzt. Das ist die
Antwort auf die Frage der Rückmeldungsliste, und sie ist genauer als „die
Abstände passen nicht": Sie passen zueinander, aber sie unterscheiden
nichts.

**Die Anwendung widerspricht sich dabei selbst.** Die Grundform `label`
(Zeile 1514) trägt für dieselbe Beziehung — Feld → nächstes Feld — schon
heute `--abstand-3` (12 px). Ein Formular aus `<label>`-Grundformen steht
also enger als eines aus `.feld`-Bausteinen, und beide sehen aus wie ein
Formular. Die Regel entscheidet diesen Widerspruch zugunsten der 12 px.

**Bündel C — Überschriften stehen zu weit von ihrem Inhalt** (6 Stellen,
Soll `--abstand-2`):

| Zeile | Regel | ist | soll |
|---|---|---|---|
| 934 | `.titelzeile { margin-bottom }` | 16 px | 8 px |
| 1311 | `.text h1 { margin-bottom }` | 16 px | 8 px |
| 1312 | `.text h2 { margin-bottom }` | 12 px | 8 px |
| 1082 | `.blatt-titel { margin-bottom }` | 12 px | 8 px |
| 2528 | `.listen-form-titel { margin-bottom }` | 12 px | 8 px |
| 2819 | `.vorschau > h4 { margin-bottom }` | 12 px | 8 px |

`.text h3` (Zeile 1313) trägt die 8 px bereits — die Skala steht also schon
richtig da, sie wird nur auf den höheren Ebenen nicht durchgehalten.

**Bündel D — Kleinteiliges, fünf Einzelfälle:**

| Zeile | Regel | ist | soll | Beziehung |
|---|---|---|---|---|
| 1317 | `.text li { margin-bottom }` | 4 px | 12 px | Zeile → Zeile |
| 2310 | `.suchsyntax li { margin-bottom }` | 4 px | 12 px | Zeile → Zeile |
| 1521 | `label:has(> input[type=checkbox]) { margin-bottom }` | 4 px | 12 px | Element → Element |
| 2276 | `.suchzeile { margin-bottom }` | 8 px | 12 px | Element → Element |
| 2171 | `.phasen-eingabe { margin-bottom }` | 8 px | 12 px | Element → Element |

#### Was die Ausmessung sonst zutage gefördert hat

- **`.feld-label` steht zweimal im Stylesheet** (Zeile 1125 im
  Formularabschnitt, Zeile 2253 im Suchabschnitt) — mit demselben Wert.
  Eine der beiden ist überflüssig. Kein Fehler, aber eine Stelle, an der
  eine künftige Änderung nur halb ankommt. Wird in AP2 zusammengeführt
  (das ist Aufräumen, kein neuer Baustein — E-S3-11).
- **Zwei negative Ränder** außer dem der Sammelleiste:
  `.kennzahl-mehr-knopf` und `.seiten-erklaerung` ziehen sich mit
  `calc(-1 * var(--abstand-2))` nach oben. Beide sind gewollt (sie holen
  einen Block an die Überschrift heran) und beide sind, mit der Regel im
  Rücken, ein Symptom: Der Abstand darüber ist zu groß, deshalb wird er
  wieder abgezogen. Nach Bündel C stünde dort der richtige Wert und der
  negative Rand wäre entbehrlich. **In AP2 nachsehen**, nicht ungeprüft
  streichen.
- **`padding` bleibt außen vor**, und das ist eine Entscheidung, keine
  Auslassung: 87 der 269 Deklarationen sind Polsterung. Sie nach der
  Rhythmustabelle zu wählen hieße, die Frage „gehört das noch zu mir?" an
  ein Element zu stellen, das gar keinen Nachbarn hat.

#### Buchführung

**Keine Versionserhöhung in diesem Paket, und das ist eine Abweichung vom
Konzept** (Abschnitt 5 sagt „`server/version.php` und `docs/CHANGELOG.md`
je Paket"). AP1 ändert ausschließlich `docs/` — kein Byte unter `server/`.
`WEB_VERSION` steht in der Fußzeile der Anwendung und dient dem Browser als
Rückfall beim Nachladen von Dateien; sie zu erhöhen, ohne dass sich eine
ausgelieferte Datei geändert hat, wäre eine Auskunft, die nichts bedeutet.
Die Erhöhung und der Changelog-Eintrag laufen mit **AP2** und nennen dann
beide Pakete.

#### Prüfstand AP1

| Was | Womit | Ergebnis |
|---|---|---|
| Abstandsbestand vollständig erhoben | eigener CSS-Leser über `style.css` | 269 Deklarationen, 74 davon Zwischenraum |
| Rohwerte in Abstandsdeklarationen | derselbe Leser, Gegenprobe mit `grep -E` ohne `var(` | **0** (zwei unabhängige Wege, gleiches Ergebnis) |
| Soll-Ist je Beziehung | Handzuordnung der 74 Stellen an die fünf Regelzeilen | 61 konform, 16 abweichend |
| Wortliste (`Design.md` ist normative Dokumentation) | `python3 tools/wortliste/wortliste.py` | 0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen |

**Nicht geprüft und warum:** Stilvergleich, Bilderlauf, Vollständigkeit und
Kontraste sind in AP1 **nicht** gelaufen — es gibt keine Codeänderung, die
sie messen könnten. Ihr Ausgangsstand ist festgehalten (Vollständigkeit:
260 Befunde auf dem Stand Web 12.2.1, alle vorbestehend), damit AP2 gegen
eine Zahl und nicht gegen ein Gefühl vergleicht.

**Die Handzuordnung ist die eine Stelle mit Ermessen.** Ob `.titelzeile`
eine „Überschrift" im Sinne der Regelzeile 2 ist oder eine Gruppe für sich,
lässt sich nicht messen — es wird entschieden. Genau diese Fälle stehen
deshalb als F-S3-01 bis F-S3-03 in Abschnitt 4 und nicht als stille
Zuordnung in der Tabelle.

#### Prüfumgebung (Vorarbeit, gehört zu keinem Paket)

Der Umsetzungscontainer brachte **keine** lauffähige Installation mit; ohne
sie wäre keine der Browserprüfungen dieses Konzepts fahrbar gewesen. Neu
aufgesetzt und einmalig belegt:

| Schritt | Beleg |
|---|---|
| MariaDB, PHP-Server, TLS davor | `lokal_starten.sh`, HTTPS 302 auf `login.php` |
| Einrichtung über `install.php` | `config.php` und `install.lock` angelegt |
| Admin-Passwort im Browser gesetzt | `passwort_setzen.mjs`, 0 Konsolenfehler |
| Demo-Konto aus `server/demo/fixture.json.gz` | 15 Diensttage, 82 Einsätze, 95 Ruhesegmente; Papierkorb 5 / 1 / 5 |
| Bilderlauf lauffähig | Probelauf über zwei Seiten: 16 Einzelbilder, 0 Überlauf, 0 Konsolenfehler, 0 Knopfhöhenabweichungen |
| `kontrast.py` lauffähig | 21 Paare gerechnet, 0 verfehlt |

Die Papierkorbzahlen sind die Kontrolle aus `admin_demo.php`: Stünden sie
auf null, wäre beim Einspielen etwas übersprungen worden.

---

### AP2 — Rhythmus an den Bausteinen, Formularfuß

**Stand:** erledigt. Web 12.2.2.

#### Was geändert wurde

**13 Stylesheet-Regeln** nach der Arbeitsliste aus AP1 und den Entscheidungen
F-S3-01 bis F-S3-03: `.karte`, `.meldung`, `.geo`, `.demo-hinweis` auf
`--abstand-5`; `.feld` auf `--abstand-3`; `.text h1`, `.text h2`,
`.blatt-titel`, `.listen-form-titel`, `.vorschau > h4` auf `--abstand-2`;
`label:has(> input[type=checkbox])`/`[type=radio]`, `.suchzeile`,
`.phasen-eingabe` auf `--abstand-3`. Nicht geändert: `.titelzeile` (F-S3-02)
und die beiden `li`-Regeln (F-S3-03).

**Eine Regel entfernt:** `.feld-label` stand zweimal, im Formular- und im
Suchabschnitt. Die zweite setzte nichts, was die erste nicht schon setzte —
aber eine Abstandsänderung wäre an einer der beiden Stellen hängengeblieben.
An ihrer Stelle steht jetzt der Hinweis, warum es sie gab.

**Zwölf freistehende Absendeknöpfe** haben den Formularfuß-Baustein bekommen.
Die Durchsicht lief über alle 60 PHP-Dateien unter `server/` mit einem
eigenen Sucher: jeder `ui_knopf()`-Aufruf innerhalb eines `<form>`, dessen
umgebende Hüllen keinen bekannten Fußbereich nennen. 16 Kandidaten, davon
**12 echte Funde** und **4 richtige Fälle**, die so bleiben:

| Datei | Knopf |
|---|---|
| `einstellungen.php` | Profil speichern *(der benannte Fall)* |
| `einstellungen.php` | Passwort ändern |
| `install.php` | Einrichten |
| `admin_sicherungen.php` | Speichern (Regeln) |
| `admin_sicherungsziele.php` | Serverschlüssel erzeugen und eintragen |
| `admin_sicherungsziele.php` | Speichern (Versand) |
| `admin_user.php` | Speichern (Konto) |
| `admin_user.php` | Konto endgültig löschen |
| `login.php` | Anmelden |
| `reset_request.php` | Link anfordern |
| `pw_handling.php` | Passwort festlegen |
| `pw_handling.php` | Passwort speichern |

Kein Fund: `index.php` („Verstanden, das war ich"), `diensttag_neu.php`
(„Zu den Standorten") und `einsatz_form.php` („Entsperren") — alle drei
sitzen in `.meldung-aktion`, dem Aktionsplatz des Meldungs-Bausteins, und
sind dort richtig. Dazu `ui.php` (Abbruchseite): ein Verweisknopf in einem
Absatz, kein Formular.

**Der Geräteabschnitt von `einstellungen.php` ist ausgenommen** (E-S3-03) und
war in der Durchsicht auch kein Kandidat — seine Knöpfe stehen bereits in
`.listen-form-fuss`.

#### Entscheidungen und Beobachtungen aus der Umsetzung

**E-S3-14 — „Profil speichern" bekommt `.listen-form-fuss`, nicht
`ui_speichern_leiste()`.** Naheliegend wäre die klebende Speichern-Leiste
gewesen: `einsatz_form.php` und `admin_rechtstexte.php` benutzen sie für
genau diese Lage — ein Formular, das über mehrere Karten läuft. Sie erscheint
aber erst, wenn `forms.js` das Formular als schmutzig meldet; der Knopf wäre
danach beim Aufruf der Seite **nicht mehr da**. Das ist eine
Verhaltensänderung, und S3 führt vier davon, diese nicht. Das Konzept nennt
zudem ausdrücklich `.listen-form-fuss` als den Formularfuß-Baustein (1.1).

**Der Fuß steht bei zweiteiligen Formularen außerhalb der Karten.** Bei
„Profil speichern" umfasst das Formular zwei Karten (Angaben, Logo); der
Knopf gehört zum ganzen Formular, nicht zur Logo-Karte. Er steht deshalb
nach `ui_karte_ende()`. **Die Ränder fallen dort zusammen:** `.karte` bringt
24 px nach unten mit, `.listen-form-fuss` 16 px nach oben — benachbarte
Geschwister-Ränder kollabieren auf das Maximum, also 24 px. Im Bild
nachgesehen und richtig so: Was dem Knopf vorangeht, ist eine abgeschlossene
Karte.

**Die beiden negativen Ränder bleiben** (`.seiten-erklaerung`,
`.kennzahl-mehr-knopf`, je `calc(-1 * var(--abstand-2))`). AP1 hatte sie als
Verdachtsfälle notiert. Nachgesehen: Beide ziehen einen Block an die
Titelzeile heran, und die Titelzeile behält nach F-S3-02 ihre 16 px. Sie
sind damit **kein Symptom mehr, sondern die Umsetzung der Regel**: 16 − 8 =
8 px, also genau „Überschrift → ihr Inhalt". Ungeprüft gestrichen wären sie
ein Fehler gewesen.

#### Prüfstand AP2

| Was | Womit | Ergebnis |
|---|---|---|
| Kaskade unverändert außer der Soll-Liste | `kaskade.py`, Vergleichsstand aus Git | 640 → 639 Regeln; **14 geänderte Endwerte, 0 entfallen, 0 neu, 0 Reihenfolgetausch** — deckungsgleich mit der Soll-Ist-Liste (13 Regeln, `label:has()` zählt mit zwei Selektoren) |
| Berechnete Stile im Browser | `stilvergleich.js`, 4 Proben × 13 Breiten | 38 857 Elementmessungen, 2 624 abweichende Elemente |
| **Vollständige Gegenprobe** dazu | eigener Lauf, der **jede** (Element, Eigenschaft)-Abweichung nennt statt acht Beispielen je Probe | **8 703 942 Einzelmessungen, 6 340 abweichend, 147 verschiedene Paare** — 68 davon `margin`/`margin-bottom` (die beabsichtigten Regeln), 79 `height`/`top`/`inset`/`bottom`/`grid-template-rows` als geometrische Folge. **Keine einzige darüber hinaus.** |
| Nichts verlorengegangen | `tools/vollstaendigkeit/pruefen.py` | 260 Befunde, **Zeile für Zeile identisch** zur Basis vor AP2 bis auf zwei verschobene Zeilennummern in `admin_user.php` (zwei eingefügte Zeilen) |
| Alle Seiten, alle Breiten | `tools/screenshots/aufnehmen.mjs` (voller Lauf) | 38 Seiten, **304 Einzelbilder: 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| Gegenprobe gegen F-P3-AQ | `md5sum` über alle 304 Bilder | 301 verschiedene. Die drei Dubletten sind **erklärt und richtig**: `10-tagesuebersicht` und `11-tagesuebersicht-schublade` bei 1024, 1280 und 1440 px — oberhalb der Schwelle steht die Leiste fest, es gibt dort keine Schublade. Kein Fall der Art „176-mal die Anmeldeseite". |
| Texte | `tools/wortliste/wortliste.py` | 0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen |
| Kontraste | `tools/screenshots/kontrast.py` | 21 Paare, 0 verfehlt (AP2 ändert keine Farbe — der Lauf belegt, dass es dabei geblieben ist) |
| Syntax | `php -l` über alle 9 geänderten PHP-Dateien | keine Fehler |

**Ein Messfehler im eigenen Prüfmittel, und er ist der Erwähnung wert:** Die
erste Fassung der Gegenprobe meldete zwei zusätzliche Paare — `outline` und
`outline-offset` an einem `<input>`. Ursache war nicht die Änderung, sondern
die Gegenprobe selbst: Sie hatte die Fokus-Rücknahme des Originalwerkzeugs
nicht übernommen, und ein Feld mit `autofocus` trug in einem der beiden
Durchläufe den Fokusring. Mit `blur()` verschwinden beide. Die Zahlen oben
sind die des korrigierten Laufs.

**Nicht geprüft:** Bedienzustände, die erst durch Klicken entstehen
(aufgeklappte Aktionsmenüs, geöffnete Dialoge, die schmutzige
Speichern-Leiste). Der Stilvergleich misst statisches Markup, der Bilderlauf
den Ruhezustand der Seite. Für AP2 ist das vertretbar — geändert wurden
ausschließlich Abstände an ruhenden Bausteinen —, es steht aber im
Prüfdokument als Bedienweg.

---

### AP3 — Sammelleiste (E-R43-1)

**Stand:** erledigt. Web 12.2.3.

#### Was geändert wurde

- `.speichern`: negativer Randausbruch entfällt (`margin: var(--abstand-4) 0 0`),
  `border-radius: var(--radius-gross)` — Radius und Breite der Karte. Sticky
  Sitz, obere Trennlinie und Schatten bleiben unverändert.
- `.speichern-innen`: `justify-content: flex-end`.
- `ui_speichern_leiste()`: Hinweis vor dem Knopf im Markup.

**Ausgerichtet wird über `justify-content`, nicht über `order`.** Ein `order`
hätte den Knopf optisch nach rechts geschoben und ihn im Vorlesefluss vorn
gelassen — die Zahl käme nach der Handlung, auf die sie sich bezieht. Die
Markup-Reihenfolge ist jetzt zugleich die Vorlesereihenfolge.

#### Prüfstand AP3

| Was | Womit | Ergebnis |
|---|---|---|
| Kaskade | `kaskade.py` | 639 → 639 Regeln; **2 neue Eigenschaften** (`border-radius`, `justify-content`), **1 geänderter Wert** (`margin`), 0 entfallen, 0 Reihenfolgetausch — genau die drei geplanten Änderungen |
| **Deckt sich die Leiste mit der Karte?** | eigene Bedienprobe in Chromium, gemessen statt gesehen | Spurenseite in **acht** Breiten (360/390/420/720/1024/1280/1440/1920): Breite und linke Kante **auf den Pixel gleich**, `border-radius` 12 px = Karte, `position: sticky`, `border-top` 1 px |
| Zweite Verwendung: NutzerInnen-Liste | dieselbe Probe, Kästchen angehakt | 720/1024/1920 px: 688@16, 772@236, 1388@396 — deckt sich jeweils mit der Karte |
| Dritte Verwendung: schmutziges Formular | Einsatzformular (Einsatz 88), Feld geändert | 360/720/1024/1920 px: Leiste deckt sich mit dem Inhaltsrahmen; Radius 12 px; sticky |
| Vierte Stelle: Rechtstexte | dieselbe Probe | 360/720/1024 deckt sich mit der Karte. Bei **1920 px steht die Leiste mit 1388 px über zwei 686-px-Karten** — kein Fehler: Dort ist der Inhalt zweispaltig, und die Leiste gehört dem Formular, das beide Spalten umfasst. Linke Kante identisch (396 px) |
| Knopf rechts? | Lage von Knopf und Hinweis verglichen | ab 720 px an allen vier Stellen `Knopf rechts vom Hinweis`; unter 720 px ist der Hinweis ausgeblendet (unverändertes Verhalten seit P3) und der Knopf 100 % breit |
| Alle Seiten | `tools/screenshots/aufnehmen.mjs`, voller Lauf | 304 Bilder, **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| Nichts verlorengegangen | `tools/vollstaendigkeit/pruefen.py` | 260 Befunde, gleich der Basis |
| Texte | `tools/wortliste/wortliste.py` | 0 / 0 / 0 |

**Zwei Messartefakte, beide aufgeklärt und keines ein Fehler der Anwendung:**

1. Der erste Probelauf meldete vier `HTTP 400`. Ursache war die Probe selbst —
   sie hatte keine Einsatzkennung gefunden und `einsatz_form.php?id=undefined`
   aufgerufen. Mit einer echten Kennung (88) verschwinden sie.
2. Der zweite Lauf meldete `ERR_CONNECTION_RESET`. Das ist die lokale
   TLS-Terminierung (`socat` vor dem eingebauten PHP-Server), nicht die
   Anwendung: Derselbe Aufruf über den vollen Bilderlauf ergibt **0**
   Konsolenfehler auf 304 Bildern.

**Ein Fund an der eigenen Buchführung:** Die Vollständigkeitsprüfung stieg
kurzzeitig auf 261 Befunde. Gelesen statt gezählt (F-P3-BA) war es ein
Auslassungszeichen in einem **Kommentar** von `version.php`, den die Prüfung
als „Unicode-Zeichen als Symbol im Markup" zählt. Der Kommentar ist
umgeschrieben; 260 stehen wieder. Der Befundtyp ist mit 195 Altfällen
bekanntes Rauschen — aber eine Zahl, die sich ohne Erklärung bewegt, ist
keine Prüfung.

**Nicht geprüft:** das Zusammenspiel mit `forms.js` beim Verlassen der Seite
(Verlassen-Warnung) — unberührt von dieser Änderung, aber nicht nachgefahren.
Steht im Prüfdokument als Bedienweg.

---

### AP4 — Navigation und Leistenüberschrift (E-R43-2)

**Stand:** erledigt. Web 12.2.4. Der Bildentscheid E-S3-12 (a) ist gefallen.

#### Was geändert wurde

- `.eintrag-text`: `font-weight` 600 → 400; `.eintrag.aktiv .eintrag-text`
  bekommt 600. Die Auszeichnung wandert von „alle" zu „einer".
- `.eintrag-leise`: `font-weight: 400` entfernt — seit der Grundschnitt 400
  ist, wäre es eine Dublette (Anti-Muster in `Design.md` 9.16).
- `.leiste-kopfzeile`: `--groesse-2` → `--groesse-3`, `--gedaempft` →
  `--asphalt`; `text-transform: uppercase` und `letter-spacing: .08em`
  entfallen. Ausrichtung unverändert linksbündig.
- `docs/Design.md`, Kapitel 5: Die Schriftskala führte die Leistenüberschrift
  noch bei `--groesse-1` (12 px), während das Stylesheet seit P3 13 px setzt.
  Beim Nachziehen aufgefallen und mitkorrigiert — jetzt bei `--groesse-3`.

#### E-S3-12 (a): Versalien und Sperrung — entschieden am Bild

Drei Fassungen bei 1440 px in doppelter Auflösung aufgenommen, alle mit
15 px, Bricolage 600 und `--asphalt`:

| Fassung | Befund |
|---|---|
| **a** versal + gesperrt (bisheriger Satz) | „DIENSTTAGE" liest sich als **Etikett**. Es steht dem Jahreseintrag „2026" darunter an Lautstärke kaum nach — zwei Überschriften statt einer Ordnung |
| **b** gemischt, ohne Sperrung | bleibt eine **Überschrift**: präsent, aber ersichtlich eine Ebene über dem, was sie ordnet |
| **c** versal, ohne Sperrung | wie a, nur enger — dasselbe Etikett |

**Entschieden: Fassung b.** Die Begründung ist die, die E-S3-12 vorweggenommen
hat, und sie hat eine Kehrseite, die im Bild sichtbar wird: Bei 13 px **trug**
die Sperrung, weil die Größe allein nicht reichte — sie war der **Ersatz für
die Größe**. Mit der Größe ist der Ersatz überflüssig und wird zur Last.

#### Prüfstand AP4

| Was | Womit | Ergebnis |
|---|---|---|
| Kaskade | `kaskade.py` | **4 entfallen, 1 neu, 3 geänderte Werte, 0 Reihenfolgetausch** — genau die acht geplanten Änderungen, keine neunte |
| Kontrast der neuen Farbverwendung | `tools/screenshots/kontrast.py`, gegen die tatsächliche Fläche | `--asphalt` auf `--schnee` (Fläche der Leiste): **19,29:1**, Schwelle 4,5 — AA weit übertroffen. 21 Paare, 0 verfehlt |
| Alle vier Stellen des Bausteins | `grep` über `server/*.php` | Diensttage, Einstellungen, Administration, Filter — alle vier in `ui.php`, alle vier ziehen über dieselbe Klasse mit |
| Alle Seiten | `tools/screenshots/aufnehmen.mjs`, voller Lauf | 304 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px |
| Nichts verlorengegangen | `tools/vollstaendigkeit/pruefen.py` | 260 Befunde, gleich der Basis |
| Texte | `tools/wortliste/wortliste.py` | 0 / 0 / 0 |

**Nicht geprüft:** die Leiste als **Schublade** unter 1024 px im geöffneten
Zustand — der Bilderlauf nimmt sie über `11-tagesuebersicht-schublade` auf,
aber die Menüpunkte im Hover- und Fokuszustand sind nicht gemessen. Steht im
Prüfdokument als Bedienweg.

---

### AP5 — Tabellen und Kacheln (B, I)

**Stand:** erledigt. Web 12.3.0 — zusammen mit zwei Funden aus der
Rückmeldung vom 01.09.2026 (siehe unten und Abschnitt 8).

#### Was geändert wurde

- **Neue Spaltenklasse `.tabelle td.mitte-spalte`** — die dritte
  Zellenausrichtung neben links (Text) und rechts (Zahl). Kein neuer
  Baustein, sondern ein Geschwister von `zahl-spalte` und `haken-spalte`.
- **NutzerInnen-Liste:** Rolle, Seit, Zuletzt angemeldet, Geräte und
  Sicherung stehen mittig; Konto bleibt links.
- **Tagesübersicht:** Nr., Beginn und Alter mittig — in der Kopfzeile **und**
  in der Zelle. Die Dauer trägt jetzt `zeit-spalte`.
- **`.kennzahl`** wird zur Flex-Spalte mit `justify-content: center`.

#### Zwei Punkte aus Block B und I, die sich anders darstellten als im Konzept

**Der Zeilentrenner reicht bereits über die volle Breite.** Das Konzept (1.3)
nennt ihn als Fund am Stand Web 9.14.1. Nachgemessen auf 12.2.4, nicht
angesehen: Die Trennlinien der Kontentabelle laufen bei 1440 px von **x = 0
bis x = 2227 von 2228 Bildpunkten** (Bild in doppelter Auflösung, Farbe der
Linie im Bild gesucht). Sie decken also die volle Tabellenbreite
einschließlich der „Öffnen"-Spalte. `.tabelle td` trägt den Rand an **jeder**
Zelle; es gibt keine Ausnahme für die letzte. **Nichts zu tun** — der Punkt
hat sich zwischen 9.14.1 und 12.2.4 erledigt.

**„Sekundär Transport" trägt schon das weiche Trennzeichen.** Das Konzept
(1.10) verlangt „Sekundär-" / „transport" mit Trennstrich. `missiontable.js`
setzt seit F-N1-G `Sekundär&shy;transport` — der Browser trennt mit
Bindestrich, wenn er muss, und lässt es sonst in einer Zeile. **Nichts zu
tun.**

**Die Kopfzeile der NutzerInnen-Liste bleibt durchgehend zentriert.** Auch
über der linksbündigen Konto-Spalte. Das ist F-N1-G aus P3 („der Kopf ist
keine Zeile der Daten, sondern ihre Beschriftung") und wird hier nicht
angetastet — das Konzept verlangt für Konto nur, dass der **Inhalt** links
bleibt. Genannt, damit es nicht als Übersehen gelesen wird.

#### Prüfstand AP5

| Was | Womit | Ergebnis |
|---|---|---|
| Kaskade | `kaskade.py` | **8 neue Eigenschaften, 0 entfallen, 0 geänderte Werte, 0 Reihenfolgetausch** — die drei `.kennzahl`-Angaben, die vier `.meldung-schutz`-Angaben und `.mitte-spalte` |
| Zeilentrenner | Bildpunktmessung über den Bilderlauf-Screenshot der Kontentabelle | Linien von x = 0 bis 2227 von 2228 — volle Breite, kein Befund |
| Spaltenausrichtung Konten | Bedienprobe, `getComputedStyle` je Zelle, vier Breiten (720/1024/1440/1920) | Kopf/Zelle: Rolle `center/center`, Seit `center/center`, Zuletzt `center/center`, Geräte `center/center`, Sicherung `center/center`, Konto `center/start` (gewollt) |
| Tagesübersicht | dieselbe Probe, fünf Breiten (720…1920) | Nr. und Beginn `mitte-spalte`/`center`, Dauer `zahl-spalte zeit-spalte` mit `white-space: nowrap` |
| Kacheln senkrecht mittig | Monatsansicht bei 360 px, Luft über/unter dem Inhalt je Kachel gemessen | Kacheln 77 px und 97 px nebeneinander; die niedrigeren zeigen **23/23** statt vorher 13/33. Bei gleich hohen Kacheln unverändert 13/13 |
| Alle Seiten | `tools/screenshots/aufnehmen.mjs`, voller Lauf | 304 Bilder, **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| Nichts verlorengegangen | `tools/vollstaendigkeit/pruefen.py` | 260 Befunde, gleich der Basis |
| Texte / Kontraste | `wortliste.py`, `kontrast.py` | 0 / 0 / 0 · 21 Paare, 0 verfehlt |

**Ein Fehlalarm des Bilderlaufs, und woran er lag:** Ein Lauf meldete 47
fehlende Bilder, 47 Konsolenfehler und 13 Knöpfe mit 35 px. Alle 13 hießen
„Zur Startseite" und standen auf der **Abbruchseite** — die Seiten hatten
404 geliefert. Ursache war der **30-Minuten-Reset des Demo-Kontos mitten im
Lauf**: Er vergibt neue Kennungen, und die vom Lauf zuvor aufgelösten
Diensttag- und Einsatzkennungen zeigten ins Leere. Nach einem Reset
unmittelbar vor dem Lauf: 304 Bilder, 0/0/0. Die Zahl 13 war echt gemessen
und trotzdem keine Aussage über die Anwendung — ein Beispiel dafür, warum zu
jeder Zahl gehört, **was** sie gemessen hat.

#### Zwei Funde aus der Rückmeldung vom 01.09.2026

Beide betreffen `tag_spuren.php` und sind in Abschnitt 8 als F-S3-B und
F-S3-C geführt; behoben, weil sie die laufende Arbeit an derselben Seite
betrafen (K4).

---

### AP6 — Einsatzansicht (G)

**Stand:** erledigt. Web 12.3.1.

#### Was geändert wurde

- **Höhe beschriftet:** `Höhe 706 m` statt `706 m` in der Kleinzeile des
  Einsatzortes. Die Bedingung `kind === 'air'` stand bereits im Code
  (E-S3-09 war insoweit schon erfüllt) — gefehlt hat nur das Wort.
- **Plakette „verschlüsselt"** am Block Einsatz; **Schloss** an Name und
  Geburtsdatum/Alter.
- **`.symbol-schutz`**: `vertical-align` von `baseline` auf `-0.1em`.
- **Der Freibanner entfällt** samt der Zeile, die ihn einblendete.
- **Handbuch** an der betroffenen Stelle umgeschrieben — die Aussage
  „entsperrt bis zur Abmeldung" bleibt dort, der Satz „Schloss-Symbole an
  den einzelnen Zeilen gibt es nicht mehr" ist **ausgetragen**, weil er seit
  diesem Paket falsch wäre.

#### E-S3-16 — Plakette und Schloss schließen einander nicht mehr aus

**Das ist die Umkehr einer P3-Entscheidung und gehört benannt.** F-N1-B hatte
festgelegt: entweder die Plakette am Kartenkopf **oder** das Schloss an der
Zeile, nie beides — „in der PatientIn-Karte wäre das Zeichen an jeder Zeile
Lärm". Die Rückmeldung verlangt beides.

Umgesetzt, weil die Begründung trägt: **Es sind zwei verschiedene
Auskünfte.** Die Plakette beantwortet „stehen auf dieser Karte geschützte
Daten?", das Schloss beantwortet „ist *diese* Zeile geschützt?". F-N1-B hat
die zweite Frage der ersten geopfert. Bei einer Schutzauskunft ist Redundanz
kein Lärm — dieselbe Überlegung, aus der in derselben Runde der
Datenschutzhinweis der Spurenseite rot geworden ist.

Vermerkt in `docs/Design.md` 9.6 als ausdrückliche Ablösung von F-N1-B.

#### Prüfstand AP6

| Was | Womit | Ergebnis |
|---|---|---|
| Kaskade | `kaskade.py` | 643 → 643 Regeln, **1 geänderter Wert** (`.symbol-schutz vertical-align`), 0 entfallen, 0 neu, 0 Reihenfolgetausch |
| Höhe: Luft gegen Boden | Browserprobe, je ein Einsatz des Demo-Bestands (349 Luft, 365 Boden) | Luft: `Höhe 706 m` gefunden. Boden: **keine** Höhenzeile, und auch kein nacktes „nnn m" (Gegenprobe mit eigenem Muster) |
| Plaketten | dieselbe Probe, alle sichtbaren Karten | Einsatz **verschlüsselt**, PatientIn **verschlüsselt**, Transport/Besatzung/Phasen ohne — wie vorgesehen |
| Schlösser | dieselbe Probe | Einsatzort, Beschreibung Einsatzort, Diagnose, Geboren bzw. Alter — je mit `<title>` „Ende-zu-Ende-verschlüsselt" |
| Schlosslage | Symbolmitte gegen Mitte des Zeilenkastens, sieben Werte durchgemessen | `baseline` −2,0 px · `middle`/`text-bottom`/`sub` +2,0 px · **`-0.1em` −0,5 px** (gegen die Versalmitte +0,5) — gewählt |
| Freibanner weg | DOM-Abfrage nach `#freibanner` | nicht vorhanden; der Gesperrt-Balken und der Fehlerbalken stehen unverändert im Markup |
| Alle Seiten | `tools/screenshots/aufnehmen.mjs` | 304 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px |
| Nichts verlorengegangen / Texte | `pruefen.py`, `wortliste.py` | 260 Befunde wie Basis · 0 / 0 / 0 |

**Nicht geprüft und warum:** Das **Schloss an „Name"** ist im Code auf
demselben Weg gesetzt wie das an „Geboren" (dieselbe Funktion
`dtGeschuetzt`), stand aber in keinem der beiden geprüften Einsätze im Bild —
im Demo-Bestand trägt keiner der beiden einen Patientennamen. Beleg ist damit
der Codeweg, nicht das Bild. Gehört als Bedienweg ins Prüfdokument.

Ebenfalls nicht geprüft: der **gesperrte** Zustand (Balken mit
Entsperren-Knopf) und der Fehlerzustand. Beide sind unberührt, aber die Probe
lief angemeldet und entsperrt.

---

### AP7 — Karte (H)

**Stand:** erledigt. Web 12.3.2.

#### Was geändert wurde

- **`geo.js`, `schild()`:** kein Namensschild mehr im Markup (nicht per
  `display:none` — was nicht angezeigt wird, wird nicht erzeugt);
  `iconSize: [36, 36]`, `iconAnchor: [18, 18]`. Der Name wandert ins
  `title`-Attribut des Markers.
- **`iconSize` ausdrücklich an allen fünf Markerarten** — Schild,
  Einsatzort, Ring, Punkt, Richtungspfeil.
- **`.geo-schild-kasten`:** 36 statt 44 px (neues Token `--geo-schild`).
- **`.geo-kreis`:** ohne weiße Umrandung, `--geo-kreis` 36 → 32 px.
- **`.geo-schild-text`** ist aus dem Stylesheet **ausgetragen**.
- **`index.php`:** kein Zielklinik-Marker mehr.
- Handbuch an **beiden** Kartenstellen nachgezogen; Tokentabelle in
  `Design.md` neu erzeugt (89 statt 88 Token).

#### Die Versatzprobe — mit Gegenprobe

Die Probe braucht das Kartenobjekt nicht: Leaflet setzt die Bildschirmlage
einer Koordinate als `transform` an das Markerelement, und der **Anker ist
der Nullpunkt dieser Verschiebung**. Der Versatz ist damit die Strecke
zwischen diesem Nullpunkt und der Mitte des gezeichneten Kastens — allein am
DOM messbar, über die Transformationen von Kartenebene und Marker.

Gemessen an der Einsatzansicht (Haus- und Klinik-Schild), sechs Zoomstufen
über die Zoom-Schaltfläche:

| | waagerecht | senkrecht |
|---|---|---|
| **vorher** (nachgestellt: Namensschild „Klinikum Immenstadt", `iconSize` unbestimmt) | **51,7 px** | −4,0 px |
| **nachher** | **0,0 px** | **0,0 px** |

Beide Werte über **alle sechs Zoomstufen unverändert** — das belegt zugleich
die Diagnose aus dem Konzept: Es ist ein **konstanter Pixelversatz**, kein
mit dem Zoom wachsender Fehler. Die 51,7 px bestätigen die Schätzung „rund
50 px" aus 1.9.

**Die Gegenprobe ist eine Nachstellung, keine Messung am alten Code.** Sie
setzt Namensschild und unbestimmte Größe im laufenden Browser wieder ein.
Das ist schwächer als ein Lauf gegen den alten Stand — es zeigt, dass die
beschriebene Kette den Versatz erzeugt, nicht dass der alte Code genau diese
Kette hatte. Der Codeweg ist dafür der Beleg: `iconSize: null` und
`iconAnchor: [22, 22]` stehen im Git-Stand vor diesem Paket.

#### Prüfstand AP7

| Was | Womit | Ergebnis |
|---|---|---|
| Kaskade | `kaskade.py` | **8 entfallen** (`.geo-schild-text` mit sieben Eigenschaften, `.geo-kreis border`), **1 neu** (`--geo-schild`), **3 geänderte Werte**, 0 Reihenfolgetausch — genau die geplanten |
| Versatz | eigene DOM-Probe, sechs Zoomstufen, mit Nachstellung des alten Zustands | 51,7 → **0,0 px** |
| Tagesübersicht ohne Zielkliniken | Marker nach Symbol gezählt | `index.php`: **1 Haus, 0 Klinik**, 6 Einsatzort-Kreise, **0 Namensschilder** |
| Einsatzansicht unverändert vollständig | dieselbe Zählung | **1 Haus, 1 Klinik**, 1 Kreis, 0 Namensschilder |
| Maße | `getBoundingClientRect` an den gezeichneten Elementen | Schildkasten **36 × 36**, Einsatzort-Kreis **32 × 32**, `border-width` **0px** |
| Alle Seiten | `tools/screenshots/aufnehmen.mjs` | 304 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px |
| Nichts verlorengegangen | `tools/vollstaendigkeit/pruefen.py` | 260 Befunde wie Basis — `.geo-schild-text` ist aus **Stylesheet und Markup** verschwunden, taucht also in keiner der beiden Listen als Rest auf |
| Texte | `tools/wortliste/wortliste.py` | 0 / 0 / 0 |

**Nicht geprüft:** Die Ringe (Start/Ende der Spur) liegen als `box-shadow`
außerhalb des Kastens und ändern seine Größe nicht — nachgelesen, nicht
gemessen. Ein Einsatz mit Doppelring stand in den geprüften Beständen nicht
zur Verfügung. Gehört als Bedienweg ins Prüfdokument.

---

### AP8 — Formularbausteine und Ortssuche (E)

**Stand:** erledigt. Web 12.3.3.

#### Was geändert wurde

- **`ortsfeld.js`:** Das `input`-Ereignis stößt die Suche jetzt in **beiden**
  Bedienformen an (vorher `if (nurKoordinaten) { zustand(); }`). Drei
  benannte Konstanten oben in der Datei: `ENTPRELL_MS = 400`,
  `MINDESTZEICHEN = 3`, dazu ein `AbortController` für „höchstens eine offene
  Anfrage". Ein `AbortError` leert die Liste **nicht** — sonst flackerte sie
  beim flüssigen Tippen.
- **Elf Platzhalter** auf erfundene Namen getauscht.
- **`.wahlliste`/`.wahl-zeile`:** ohne Rahmen, ohne eigene Fläche, ohne
  Zwischenraum; Radius eine Stufe kleiner.
- **`docs/Lizenzen.md` 6.2** neu geschrieben.
- **`tools/wortliste/ausnahmen.json`:** eine Regel entfallen.

#### E-S3-06 in Zahlen — die Entprellzeit bleibt bei 400 ms

Das Konzept erlaubt 300–600 ms und verlangt, den Endwert hier festzuhalten:
**400 ms.** Gemessen wurde mit **abgefangenen** Photon-Anfragen (Antwortzeit
künstlich auf 120 ms gesetzt), damit der Nachweis nicht am fremden Dienst
hängt:

| Ortsfeld | flüssig „Talwang" (7 Zeichen à 80 ms) | „Ta" (2 Zeichen) | Lupe |
|---|---|---|---|
| Standort (Einstellungen) | **1 Anfrage** | 0 | 1 nach **37 ms** |
| Zielklinik (Einsatzformular) | **1 Anfrage** | 0 | 1 nach **47 ms** |
| Einsatzort (Einsatzformular) | **1 Anfrage** | 0 | 1 nach **39 ms** |

Die einzelne Anfrage trug jeweils den **vollständigen** Suchtext („Talwang"),
nicht ein Bruchstück — das belegt, dass die Entprellung bis zum Ende des
Tippens hält.

**Ein Zwischenbefund, der Zeit gekostet hat:** Die erste Probe lief auf einem
**bestehenden** Einsatz und zählte null Anfragen. Das ist kein Fehler,
sondern die dokumentierte Regel „stehen Koordinaten, ruht die Adresssuche" —
der Einsatz hatte welche. Die Probe läuft jetzt auf einem **neuen**
Einsatzformular.

#### Der Platzhalter-Bestand, vollständig erhoben

Erhoben über `grep` auf `'platzhalter' =>` und `placeholder="` in allen
PHP- und JS-Dateien unter `server/`. **39 Stellen**, davon **11 mit realen
Orts- oder Organisationsnamen** — alle getauscht:

| Datei | Stellen |
|---|---|
| `admin_stammdaten.php` | 5 |
| `einstellungen.php` | 5 |
| `mission_fields.php` | 1 |

**Verbleibend und geprüft in Ordnung:** `sicherung.example.de` (reservierte
Beispieldomäne), `neue@adresse.de`, `z. B. Vorname Nachname`, `z. B.
Nachname`, `Name der Person`, `z. B. Dienstuhr`, `z. B. Leitstellen-Nr.`,
`z. B. 07:00`, `hh:mm`, `XXXX-XXXX-XXXX-XXXX`, `ABCD-EFGH-JKMN-PQRS-TVWX`,
`Tippen zum Suchen, Enter zum Übernehmen`, `Name oder E-Mail`, `Suchen`,
`wird in der Kopfleiste angezeigt`, `tippen für Vorschläge — auch Koordinaten
oder Plus Code` — keiner davon nennt einen realen Ort oder eine reale Person.

**Drei Kommentare im Code zitierten den alten Platzhalter** („Standort
Kempten") und sind mitgezogen — sie hätten sonst einen Text zitiert, den es
nicht mehr gibt. **Nicht** angefasst: `import_profiles.js` und `update.php`
nennen „Christoph 17" als reale Fremdbezeichnung (Titel einer fremden
Exportdatei, Beispiel eines selbstvergebenen Gerätenamens) — das sind keine
Platzhalter, und ein erfundener Name wäre dort falsch. `geo.js` nennt
„Klinikum Immenstadt" als Fallbeispiel des behobenen Markerversatzes; das ist
die Wiedergabe eines Fehlerberichts.

**Ein Nebeneffekt, der die Regel bestätigt:** Die Wortliste führte für den
Rettungsmittel-Platzhalter eine Ausnahme, weil „Christoph 17" ein Luftbegriff
ist. „Alpenfalke 1 oder NEF Talwang 76/1" ist von sich aus neutral — die
Ausnahme ist **ersatzlos entfallen** (66 statt 67 Regeln). Ein Platzhalter,
der keine Ausnahme braucht, ist der bessere Platzhalter.

#### Prüfstand AP8

| Was | Womit | Ergebnis |
|---|---|---|
| Kaskade | `kaskade.py` | **4 entfallen** (Rahmen, Fläche, Zwischenraum, Rahmenfarbe der Wahl), **1 geänderter Wert** (Radius), 0 neu, 0 Reihenfolgetausch |
| Autosuche | Netzwerkmitschnitt mit abgefangenen Anfragen, drei Ortsfelder | 1 / 0 / 1 je Feld — Tabelle oben |
| Wahlliste | Höhen und Rahmen gemessen, vorher gegen nachher am selben Bild | **248 → 224 px**, Zeilenhöhe unverändert 4 × 44 px, `border-width` **0px** |
| Wahlliste an allen Stellen | Baustein-Suche | drei Verwendungen: Logo-Wahl (Profil) und zweimal Diensttag-Zusammenführen — die Änderung sitzt am Baustein, alle drei ziehen mit |
| Texte | `tools/wortliste/wortliste.py` | **0 / 0 / 0** — nach dem Streichen der überflüssigen Ausnahme |
| Alle Seiten | `tools/screenshots/aufnehmen.mjs` | 304 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px |
| Nichts verlorengegangen | `tools/vollstaendigkeit/pruefen.py` | 260 Befunde wie Basis |
| Syntax | `php -l`, `node --check` | keine Fehler |

**Nicht geprüft:** Das Zusammenspiel mit dem **echten** Photon-Dienst. Die
Probe fängt die Anfragen ab — das prüft Entprellung, Mindestlänge und Abbruch
genau, aber nicht, ob die Antwort des echten Dienstes noch richtig gelesen
wird. Am Antwortformat hat sich nichts geändert; belegt ist es damit nicht.
Gehört als Bedienweg ins Prüfdokument.

**Ebenfalls nicht geprüft:** die Wahllisten des Diensttag-Zusammenführens im
Bild — sie hängen an einem Bestand mit zwei zusammenführbaren Diensttagen,
der im Demo-Konto nicht ohne Vorarbeit herzustellen ist. Belegt ist der
gemeinsame Baustein.

---

### AP9 — Suche (J)

**Stand:** erledigt. Web 12.4.0. **Funktionsänderung.**

#### Was geändert wurde

- **`KATALOG_ART`** entsteht in `suche.php` **aus `mission_fields.php`**
  (samt Unterfeldern, gefiltert über `mf_ist_spalte()`) und nennt je Spalte
  ihre Art.
- **14 Filter** tragen jetzt ihre Katalogspalte in der Definition.
- **`GRUPPE_NUR_WENN` und `FELD_NUR_WENN` sind ersatzlos entfallen**, dazu
  die alte `gruppenSichtbarkeit()`. An ihre Stelle treten drei kleine
  Funktionen: `wertGefuellt()` (was heißt „leer" bei dieser Art),
  `spaltenMitBestand()` (ein Durchgang über den Bestand) und
  `filterKasten()` (welches Element versteckt wird).
- Handbuch nachgezogen.

#### E-S3-08, wörtlich und sinngemäß — eine benannte Abweichung

E-S3-08 sagt: „Die Suche fragt den Bestand **einmal je Seitenaufruf ab** —
**eine** Abfrage über alle katalogisierten Filterfelder, kein
Feld-für-Feld-Scan."

**Diese Abfrage gibt es bereits, und eine zweite wäre dieselbe Auskunft
doppelt geholt.** Seit Web 5.10.0 lädt `api/suchindex.php` den **gesamten**
aktiven Bestand einmal je Seitenaufruf in den Browser — fünf SQL-Abfragen,
unabhängig von der Zahl der Einsätze, und der Endpunkt nimmt bewusst keine
Parameter entgegen (die geschützten Felder gehen als Chiffretext mit, der
Server filtert nicht). Die Suche filtert danach vollständig lokal.

Umgesetzt ist deshalb der **Sinn** des Beschlusses und nicht sein Wortlaut:

| E-S3-08 verlangt | umgesetzt |
|---|---|
| einmal je Seitenaufruf abfragen | ja — `api/suchindex.php`, **1** Abruf je Aufruf, im Netzwerkmitschnitt gezählt |
| eine Abfrage, kein Feld-für-Feld-Scan | ja — **ein** Durchgang über den Bestand für **alle** Spalten, nicht einer je Filter |
| Regel an der Filtererzeugung aus dem Feldkatalog | ja — `KATALOG_ART` wird aus `mission_fields.php` erzeugt |
| keine Liste im Code, welche Felder „optional" sind | ja — beide Listen entfallen |
| Z3-Grenzen halten | ja — **keine** neue Serverabfrage; der Zusatzaufwand ist ein JS-Durchgang von **0,06 ms** bei 82 Einsätzen |

**Der Weg über eine zusätzliche SQL-Abfrage wäre schlechter gewesen:** Er
holte Auskünfte, die schon im Browser liegen, und die Sichtbarkeit hinge
danach an zwei Quellen, die auseinanderlaufen können.

#### Ein Punkt aus 3.7, den die Umsetzung anders beantwortet

3.7 nennt als Grenzfall: „Suche per URL-Parameter auf ein ausgeblendetes Feld
(**wird ignoriert**, kein Fehler)." Der Bestand macht seit Web 5.10.0 das
Gegenteil, und das ist besser: Der Filter **erscheint**. Ein gesetzter, aber
unsichtbarer Filter hielte die Trefferliste leer, ohne dass sich finden ließe,
warum — und ohne dass er sich zurücknehmen ließe. Ihn zu ignorieren hieße,
einen geteilten Link stillschweigend anders auszuführen, als er lautet.
**Verhalten beibehalten, im Handbuch beschrieben.**

#### Prüfstand AP9

| Was | Womit | Ergebnis |
|---|---|---|
| Konto **mit** Bestand | Browserprobe, Sichtbarkeit je Block und je Filterkasten gelesen | fünf Blöcke sichtbar, **0** ausgeblendete Filter |
| Konto **ohne** Bestand (frisches Admin-Konto) | dieselbe Probe | Blöcke **Transport** und **Bergrettung** aus, **12** Filter ausgeblendet; die elf immer sinnvollen bleiben |
| geteilter Link auf ein ausgeblendetes Feld | `suche.php#wi=j` auf dem leeren Konto | Block Bergrettung kommt zurück, **genau** `f-wi` erscheint, die übrigen elf bleiben aus |
| **Gegenprobe: Bestand anlegen** | über die Oberfläche: Diensttag anlegen, Einsatz mit Fehleinsatz anlegen, Suche erneut öffnen | vorher `f-fe` **aus**, nachher **an** — und `f-se`, `f-ta`, `f-wi` sowie beide Blöcke bleiben aus. Es erscheint genau das Feld, dessen Spalte Bestand bekommen hat |
| kein N+1 | Netzwerkmitschnitt je Seitenaufruf | `api/suchindex.php`: **1** Abruf, in allen vier Läufen |
| Aufwand | `performance.now()` über 200 Durchgänge | **0,06 ms** je Durchgang bei 82 Einsätzen |
| Konsolenfehler | dieselben Läufe | 0 |
| Alle Seiten | `tools/screenshots/aufnehmen.mjs` | 304 Bilder, 0 / 0 / 0 |
| Nichts verlorengegangen / Texte | `pruefen.py`, `wortliste.py` | 260 wie Basis · 0 / 0 / 0 |

**Nicht geprüft und warum:** Die Messung am **5 000-Einsätze-Konto** (S2,
`tools/messstand/`) ist **nicht** gefahren. Sie prüft Serverbudgets, und
dieses Paket fügt **keine** Serverabfrage hinzu — `api/suchindex.php` ist
unverändert, die Sichtbarkeit entsteht im Browser. Was wächst, ist ein
JS-Durchgang, der linear mit dem Bestand geht: aus 0,06 ms bei 82 Einsätzen
folgen rund **4 ms** bei 5 000. Gemessen ist die kleine Zahl, die große ist
gerechnet — das steht so im Prüfdokument.

**Ebenfalls nicht geprüft:** ein Bestand, in dem eine Auswahlspalte
ausschließlich den Wert **0** trägt (etwa „0 Cycles" ohne jeden anderen
Windeneintrag). Der Code behandelt das ausdrücklich — bei einer Auswahl ist
die Null eine Angabe —, ein solcher Bestand ließ sich aber nicht ohne
Weiteres herstellen. Bedienweg fürs Prüfdokument.

---

### AP10 — Demo-Konto (D)

**Stand:** erledigt. Web 12.4.1. **Funktionsänderung.**

#### Was geändert wurde

- **`demo_lib.php`:** neue Konstante `DEMO_NAME = 'Demo NutzerIn'`, gesetzt
  beim Anlegen **und** beim Zurücksetzen. Der Name kam vorher aus der
  Fixture — also aus dem Referenzkonto — und hätte sonst beim nächsten Reset
  wiederkommen müssen.
- **`admin_user.php`:** `DEMO_GESPERRT` mit sieben Aktionen, abgewiesen
  **vor** der Verarbeitung; `demo_lib.php` wird jetzt oben eingebunden statt
  mitten im Aktionsblock.
- **Anzeige:** Meldung oben mit dem Weg zum Reiter, Kontoformular in einem
  `<fieldset disabled>`, Karte „Sicherungen" entfällt, Löschkarte erklärt den
  richtigen Weg, Aktionsmenü auf „Zum Demo-Konto" reduziert.
- **CSS:** `.feldsatz-gesperrt` — Rücknahme der Browservorgaben für
  `fieldset` (die Elementregeln sind mit O11 gefallen) plus Deckkraft im
  gesperrten Zustand.
- **Rahmenplan R25** und **Handbuch 3.2** nachgetragen.

#### Warum ein `<fieldset>` und nicht `disabled` an jedem Feld

Ein `fieldset[disabled]` sperrt alles darin in einem Zug — auch Felder, die
später dazukommen. Die Alternative wäre ein Attribut je `ui_feld()`-Aufruf
gewesen: vier Stellen heute, und die fünfte vergisst jemand. **Der Preis:**
Die Elementregeln für `fieldset` sind in P3/O11 gefallen, ein blankes
`<fieldset>` brächte also Rahmen und Polsterung des Browsers mit. Dafür gibt
es jetzt `.feldsatz-gesperrt` — einschließlich `min-width: 0`, ohne das ein
`fieldset` seine eingebaute Mindestbreite nach dem Inhalt behält und jedes
schmale Raster sprengt.

#### Die Geräte-Aktionen bleiben offen

E-S3-07 spricht von „Speicher- und Sicherungsaktionen". `device_toggle` und
`device_delete` sind bewusst **nicht** gesperrt: Das Demo-Konto lädt
ausdrücklich zum Koppeln einer Uhr ein („Ausprobieren ist erwünscht — ändern,
anlegen, löschen, Uhr koppeln"), und was dabei entsteht, räumt der Reset
selbst wieder ab. Eine Sperre dort nähme dem Konto seinen Zweck.

#### Prüfstand AP10

| Was | Womit | Ergebnis |
|---|---|---|
| Anzeigename | NutzerInnen-Liste, Seitentitel und Überschrift gelesen | dreimal **„Demo NutzerIn"** statt der E-Mail-Adresse |
| Demo-Kontoseite | Browserprobe, sichtbare Karten und Formularzustand | Karten **Konto · Geräte · Abonnement · Konto löschen** — **„Sicherungen" fehlt**; Formular `disabled`, Deckkraft **0,55**; Aktionsmenü nur **„Zum Demo-Konto"** |
| Seite bleibt aufrufbar | dieselbe Probe | HTTP 200, Titel „Demo NutzerIn — Konto — Einsatzdoku" |
| **Sperre im Schreibweg** | **direkte POSTs** an der Oberfläche vorbei, mit gültigem CSRF | `konto`, `sichern`, `user_delete` je **abgewiesen** mit der Meldung (Ton `fehler`); Name des Demo-Kontos danach unverändert |
| Gegenprobe normales Konto | derselbe POST auf Konto 1 | **durchgelaufen** („Name gespeichert"), Karten und Aktionsmenü unverändert vollständig |
| Reiter „Demo-Konto" | Anlegen und Zurücksetzen gefahren | funktioniert wie bisher: 15 Diensttage, 82 Einsätze, Papierkorb 5 / 1 / 5 |
| Alle Seiten | `tools/screenshots/aufnehmen.mjs` | 304 Bilder, 0 / 0 / 0 |
| Nichts verlorengegangen / Texte | `pruefen.py`, `wortliste.py` | 260 wie Basis · 0 / 0 / 0 |
| Konsolenfehler | Browserprobe | 0 |

**Ein Fund an der eigenen Arbeit:** Die erste Fassung der Sperrmeldung trug
ein gerades `"` als schließendes Anführungszeichen; im Browser stand
sichtbar `&quot;`. Aufgefallen ist es erst am **Text der Antwort** im
POST-Protokoll, nicht im Bild — drei Stellen richtiggestellt.

**Nicht geprüft:** die Aktionen `einspielen`, `freigeben`, `widerrufen` und
`paket_loeschen` mit einem direkten POST. Sie stehen in derselben Liste und
werden an derselben Stelle abgewiesen wie die drei geprüften; belegt ist
damit der Codeweg, nicht der Einzelfall. Gehört ins Prüfdokument.

---

### AP11 — Logos, Favicons, Uhr-Kacheln (K)

**Stand:** erledigt. Web 12.4.2. Der Bildentscheid E-S3-12 (b) ist gefallen.

#### Die sechs Schritte aus 3.6, der Reihe nach

**1 — Beschnitt der Boden-SVG.** `viewBox="0 0 420 420"` → `"0 42.5 420 335"`,
in beiden Fassungen (farbig und weiß). An der Zeichnung nichts geändert.
`getBBox()` bestätigt: Rahmen und Zeichnung sind jetzt deckungsgleich
(x = 0, y = 42,5, 420 × 335).

**2 — Aufräumen des Luftlogos.** Nachgemessen: Ein blauer Pfad läuft bis
x = 556,7 und damit **156,5 Einheiten** über den Rahmen (400,16) hinaus —
die Zahl aus dem Konzept, bestätigt. Sichtbar ist er nicht; ein Clip im
Inneren schneidet ihn weg. **Nicht** getan: die Pfaddaten beschneiden — das
wäre ein Eingriff in die Zeichnung mit ungewissem Ausgang. **Getan:** ein
zusätzlicher Rahmen-Clip auf der Wurzel, der ihn unabhängig von der viewBox
drinnen hält. Beleg, dass am Bild nichts hängt: die aus alter und neuer SVG
abgeleiteten Favicons sind **bitgleich**.

**3 — Bildmaße je Logo.** `ui_logo_masse()` in `ui.php`; die Kopfleiste gibt
`width` je nach Wahl aus (54 für Luft, 43 für Boden, bei 34 px Höhe).
**Nicht** geändert: die Anmeldeseiten. Sie binden über `logo_src()` ein, und
das darf auf eine **eigene** Datei der Installation zeigen (`app.logo_path`)
— deren Verhältnis kennt niemand, und ein falsch reservierter Kasten wäre
schlimmer als gar keiner. Dort steht `width:auto` im Stylesheet.

**4 — Favicons.** Neu abgeleitet mit `tools/logos/erzeugen.mjs`.

**5 — Uhr-Kacheln.** `tools/uhr-bilder/erzeugen.sh` gelaufen.

**6 — Nachmessen.** Siehe Prüfstand.

#### E-S3-12 (b): keine Feinkorrektur

| bei 34 px Höhe | vorher | nachher |
|---|---|---|
| Luftlogo | 54,5 × 34 px · 1 853 px² | 54,5 × 34 px · 1 853 px² |
| Bodenlogo | 34 × 27,1 px · **921 px²** | **42,6 × 34 px · 1 449 px²** |
| Verhältnis | **2,01** | **1,28** |

Die Zahlen treffen die Erwartung des Konzepts (1.12: „42,6 × 34 gegen
54,5 × 34 px") auf die Nachkommastelle. **Entschieden: keine Feinkorrektur.**
Am Bild — beide Logos nebeneinander in der Kopfleiste bei dreifacher
Auflösung — stehen sie gleich hoch und wiegen gleich schwer; das Bodenlogo
wirkt durch seine geschlossene Form eher etwas kräftiger. Was das Auge in
einer Zeile vergleicht, ist die Höhe, und die ist gleich. Eine Regel mit der
56-px-Schranke wird damit nicht gebraucht.

#### Zwei Funde, beide an der eigenen Arbeit

**F-S3-D — XML verbietet `--` im Kommentar, und das Werkzeug schrieb trotzdem
eine Datei.** Der erste Anlauf setzte einen Herkunftsvermerk in die SVG, mit
Gedankenstrichen als `--`. Damit waren alle vier Dateien **ungültige XML**.
Der Browser zeigte sein Platzhalterbild, `tools/logos/erzeugen.mjs`
fotografierte es, schrieb es als Favicon und meldete „64 × 64, 865 Bytes" —
Rückgabewert 0. Aufgefallen ist es erst beim Bildvergleich.
**Behoben zweifach:** die Kommentare enthalten keine doppelten Bindestriche
mehr, **und** das Werkzeug sieht jetzt nach, ob das Bild geladen hat, und
bricht ab, ohne etwas zu schreiben. Ein Werkzeug, das bei einem Fehlschlag
eine Datei schreibt, ist schlimmer als eines, das nichts tut.

**F-S3-E — Die Logotabelle in `Design.md` 2.3 war veraltet.** Sie führte
`gen-em_logo_fahrzeug.svg`, `favicon.png` und `favicon-fahrzeug.png` —
Namen, die es seit dem Ersatz des NEF-Platzhalters nicht mehr gibt. Beim
Nachziehen aufgefallen und mitkorrigiert, samt Rahmenmaßen.

#### Prüfstand AP11

| Was | Womit | Ergebnis |
|---|---|---|
| Rahmen = Zeichnung | `getBBox()` im Browser über die Wurzel, alle vier SVG | Boden `viewBox="0 42.5 420 335"`, bbox `x=0 y=42,5 w=420 h=335` — **deckungsgleich**. Luft unverändert 400,16 × 249,81 |
| Der Überstand des Luftlogos | dieselbe Messung, Element für Element | ein Pfad `.cls-3` bis **x = 556,7**, also 156,5 Einheiten über dem Rahmen |
| Ändert der Clip das Bild? | Favicon aus **alter** und aus **neuer** SVG mit demselben Chromium erzeugt und verglichen | Luftlogo **bitgleich** · Bodenlogo 593 abweichende Punkte (der Beschnitt) |
| Logogrößen | gezeichnete Maße bei 34 px und 56 px Höhe | 54,5 × 34 gegen **42,6 × 34** · Flächenverhältnis **1,28** (vorher 2,01) |
| Bildmaße im Markup | `ui_logo_masse()` gegen die gemessenen Maße | Luft `width=54` (gemessen 54,5) · Boden `width=43` (gemessen 42,6) — Rundung auf die ganze Zahl, wie ein `width`-Attribut es verlangt |
| Uhr-Kacheln | alle 17 PNG unter `watch/resources*/drawables/` vorher/nachher verglichen | **4 geändert** (genau die `logo_boden.png`), **13 bildgleich**. Die 13 sind zurückgenommen: Ihre Bytes unterschieden sich nur im `tIME`-Block des PNG |
| Bestätigt das den 78-%-Faktor? | Rechnung gegen die neuen Seitenverhältnisse | ja — der Faktor war schon vorher das Verhältnis der Verhältnisse (0,798 / 0,624 ≈ 0,78); der Beschnitt ändert die Höhe je Breite nicht |
| Favicon-Bauform | Alphakanal-Kasten der erzeugten PNG | Luft `(0, 12, 64, 52)` unverändert · Boden `(0, 6, 64, 58)` statt `(0, 5, 64, 58)` — eine Zeile, die Rundung des Beschnitts |
| Alle Seiten | `tools/screenshots/aufnehmen.mjs` | 304 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px |
| Nichts verlorengegangen / Texte | `pruefen.py`, `wortliste.py` | 260 wie Basis · 0 / 0 / 0 |

**Nicht geprüft und warum:**

- **Der Bilderlauf für beide Logo-Wahlen** ist **nicht** gefahren. Die
  Umstellung der Wahl im Profil ließ sich in der Probe nicht auslösen
  (die Wahlliste arbeitet mit ausgeblendeten Radios; der Klick auf die Zeile
  kam nicht durch). Gemessen ist stattdessen die **Geometrie beider SVG**
  direkt, bei beiden vorkommenden Höhen, und das Bild der Kopfleiste mit
  beiden Logos nebeneinander. Was fehlt, ist der Nachweis über die echte
  Einstellung — Bedienweg fürs Prüfdokument.
- **Die Uhr-Kacheln auf einem Gerät.** `tools/uhr-pruefstand/pruefstand.sh
  reihe` (übersetzt die App für alle Geräte) ist nicht gelaufen — es fehlt
  das Garmin-SDK. Die Kacheln sind Ableitungen mit unveränderten Maßen; ein
  Übersetzungsfehler ist nicht zu erwarten, belegt ist er nicht. **Gehört ins
  Prüfdokument und in die S5-Uhr-Auslieferung** (E-S3-04).
- **Backlog Nr. 49 (Logofarben)** ist wie beschlossen **nicht** angefasst
  (E-S3-05). Der Generatorlauf rastert also mit den heutigen — teils
  abweichenden — Farbwerten. Das ist bekannt und bleibt so bis S4/B1.

---

### AP12 — Abschluss

**Stand:** erledigt. Keine Versionserhöhung — AP12 ändert nur `docs/`
(dieselbe Begründung wie bei AP1).

#### Was erledigt ist

- **Backlog:** Nr. 56 nach *Erledigt* verschoben (Konzeptteil mit AP1
  erfüllt); **Nr. 57** (zwei Aufbauten derselben Einsatztabelle, aus F-S3-A)
  und **Nr. 58** (kein Prüfmittel fragt, ob eine Seite ihr Gerüst hat, aus
  F-S3-C) neu angehängt. Nummern 4, 6, 7 bleiben frei.
- **Rahmenplan:** Statuszeile S3 gesetzt.
- **Prüfdokument:** `docs/Pruefdokument-S3-Oberflaechen-Nacharbeit.md` —
  eigene Datei neben dem Konzept, mit dem, **was nicht geprüft werden
  konnte**, an erster Stelle.
- **Dokumentation gegengelesen** (Abschnitt 7 des Konzepts): `Design.md`
  (Kapitel 2.3, 5, 6, 9.5–9.9, 9.13, 9.16, 12), `Handbuch.md` (vier
  Stellen), `Lizenzen.md` 6.2, `CHANGELOG.md`, `Rahmenplan.md`.

#### Prüfmittel-Endlauf, in der Reihenfolge aus CLAUDE.md 6

Erst Code, dann Dokumentation, **dann** die Prüfmittel — und jede Zahl mit
dem, was sie gemessen hat:

| Mittel | Was es gemessen hat | Ergebnis |
|---|---|---|
| `tools/wortliste/wortliste.py` | alle sichtbaren Texte unter `server/` und die normative Dokumentation | **0** Treffer außerhalb der Ausnahmen, **0** ungenutzte Ausnahmen, **0** durchgerutschte Fallen — bei 66 statt 67 Regeln |
| `tools/vollstaendigkeit/pruefen.py` | 220 Klassen der Sollmenge, 328 Klassen im Markup, Symbolverweise, Knopfhöhen | **260** Befunde — Zeile für Zeile gleich dem Stand vor S3 |
| `tools/screenshots/kontrast.py` | 21 Farbpaare aus `:root` gegen ihre tatsächliche Fläche | **0** verfehlt |
| `tools/screenshots/aufnehmen.mjs` | **38 Seiten** in acht Breiten, angemeldet als Demo- und als Admin-Konto | **304 Bilder**: 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px |
| Gegenprobe dazu (LIESMICH, F-P3-AQ) | `md5sum` über alle 304 Bilder | **302 verschieden**; die zwei Dubletten sind erklärt: `10-tagesuebersicht` und `11-tagesuebersicht-schublade` bei 1280 und 1440 px — oberhalb der Schwelle steht die Leiste fest, es gibt dort keine Schublade. Ein früherer Lauf hatte drei, weil auch 1024 px zusammenfiel: dieselbe Ursache. Kein Fall der Art „176-mal die Anmeldeseite“ |
| `tools/stilvergleich/kaskade.py` | Endstand gegen den Stand vor S3 | siehe Soll-Ist-Bilanz unten |

#### Soll-Ist-Bilanz des Stilvergleichs über die ganze Phase

Je Paket gegen den jeweiligen Vorstand gerechnet, hier zusammengezogen:

| Paket | entfallen | neu | anderer Wert | Reihenfolgetausch |
|---|--:|--:|--:|--:|
| AP2 | 0 | 0 | 14 | **0** |
| AP3 | 0 | 2 | 1 | **0** |
| AP4 | 4 | 1 | 3 | **0** |
| AP5 | 0 | 8 | 0 | **0** |
| AP6 | 0 | 0 | 1 | **0** |
| AP7 | 8 | 1 | 3 | **0** |
| AP8 | 4 | 0 | 1 | **0** |
| AP10 | 0 | 5 | 0 | **0** |
| **Summe** | **16** | **17** | **23** | **0** |

**Gegengerechnet in einem Zug**, Endstand gegen den Stand vor AP1:
`entfallen 16 · neu 17 · anderer Endwert 23 · Reihenfolgetausch 0` — die
Summe der Pakete deckt sich mit der Gesamtrechnung. (Die fünf neuen
Eigenschaften von AP10 sind die vier Rücknahmen der `fieldset`-Vorgaben plus
die Deckkraft; im Paketstand oben standen sie als „2", weil dort gegen den
Stand vor AP8 gerechnet war und zwei davon dieselbe Regel betreffen. Die
Gesamtrechnung ist die maßgebliche.)

Jede dieser 56 Abweichungen stand vor ihrem Paket als Soll fest und ist im
jeweiligen Prüfstand benannt. **Keine darüber hinaus** — und in keinem Paket
hat sich die Reihenfolge zweier gleich starker Regeln gedreht.

#### Was S3 über die Liste hinaus gefunden hat

Die Rückmeldungsliste hatte 19 Punkte. Zwei davon waren bereits erledigt und
sind nachgemessen worden, statt sie erneut zu „beheben" (Zeilentrenner,
weiches Trennzeichen). Dafür sind **fünf Funde** dazugekommen, von denen
keiner auf der Liste stand:

| | Fund | wie gefunden |
|---|---|---|
| F-S3-A | Die Tagesübersicht baut ihre Einsatztabelle ein zweites Mal | beim Suchen der Ursache für den Dauer-Umbruch |
| F-S3-B | Ein unbekannter Meldungston ergab einen ungestalteten Kasten | Rückmeldung 01.09.2026, dann im Code nachgesehen |
| F-S3-C | `tag_spuren.php` lief ohne Seitengerüst | **von einem Menschen auf einem Telefon** |
| F-S3-D | XML verbietet `--` im Kommentar — und das Favicon-Werkzeug schrieb trotzdem eine Datei | eigener Fehler, beim Bildvergleich aufgefallen |
| F-S3-E | Die Logotabelle in `Design.md` 2.3 war veraltet | beim Nachziehen |

**Drei davon konnte kein Prüfmittel finden**, und das ist der eigentliche
Ertrag: F-S3-C nicht, weil der Bilderlauf Überlauf misst und nicht
Randlosigkeit; F-S3-B nicht, weil die Vollständigkeitsprüfung Klassen als
Literale sucht und diese zur Laufzeit entsteht; F-S3-D nicht, weil das
Werkzeug bei einem Fehlschlag klaglos eine Datei schrieb. Zwei sind als
Backlog Nr. 57 und 58 vermerkt, der dritte ist im Werkzeug behoben.

#### Der Rahmenplan ist an zwei Stellen fortgeschrieben

- **Abschnitt 6, Statuszeile S3** — Ergebnis, Zahlen, Funde.
- **Abschnitt 5, der S3-Block selbst** — ein Umsetzungsblock am Ende, der
  benennt, was sich **anders dargestellt hat als geplant**: Der
  Zeilentrenner reichte bereits über die volle Breite, „Sekundärtransport"
  trug schon das weiche Trennzeichen, die Höhenbedingung stand schon im
  Code, und die Favicons trugen den leeren Rand nicht. Dazu die drei
  gefallenen Bildentscheide und das, was offen bleibt.
- Dabei **eine überholte Angabe berichtigt**: Der Block K nannte für die
  Uhr-Auslieferung noch „P6 (R29)". Das ist seit R48 nicht mehr richtig —
  P6 trägt keine Uhr-Auslieferung mehr, die nächste ist die von S5
  (E-S3-04). Der Satz stand so auch im Konzept (1.14) und war dort bereits
  als überholt gekennzeichnet; im Rahmenplan nicht.

#### Versionen der Phase

| Version | Paket | Sache |
|---|---|---|
| 12.2.2 | AP1 + AP2 | vertikaler Rhythmus, Formularfuß an zwölf Knöpfen |
| 12.2.3 | AP3 | Sammelleiste in Kartenform |
| 12.2.4 | AP4 | Navigation, Leistenüberschrift |
| 12.3.0 | AP5 | Spaltenausrichtung, Kacheln, fünfter Meldungston, Gerüst der Spurenseite |
| 12.3.1 | AP6 | Einsatzansicht |
| 12.3.2 | AP7 | Markerversatz |
| 12.3.3 | AP8 | Autosuche, Platzhalter, Wahlliste |
| 12.4.0 | AP9 | Filterausblendung aus dem Feldkatalog |
| 12.4.1 | AP10 | Demo-Konto gesperrt |
| 12.4.2 | AP11 | Logos, Favicons, Uhr-Kacheln |

**Keine Migration.** `update.php` ist nach dem Deploy nicht nötig.
**Die Uhr-Version steigt nicht** — die neu gerasterten Kacheln reisen mit der
S5-Auslieferung (E-S3-04).
