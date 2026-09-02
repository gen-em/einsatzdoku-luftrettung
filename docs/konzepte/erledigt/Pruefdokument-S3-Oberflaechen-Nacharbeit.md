# Prüfdokument S3 — was **du** noch prüfen musst

Zur Phase S3 („Oberflächen-Nacharbeit und vertikaler Rhythmus"). Das
Prüfprotokoll im Konzept beantwortet *„ist es belegt?"*; dieses Dokument
beantwortet *„was muss ich noch tun?"*.

> **Stand: AP1 bis AP12 sind gebaut.** Web 12.2.2 bis 12.4.2, elf
> Versionsschritte, **keine Migration** — `update.php` ist nach dem Deploy
> **nicht** nötig.
>
> **Der wichtigste Punkt dieses Dokuments ist Nummer 1 der Prüfliste:** die
> vier Funktionsänderungen einmal selbst bedienen. Alles Übrige ist hier
> gemessen; Bedienzustände sind es nicht.

---

## 1. Was NICHT geprüft werden konnte — und warum

Das steht an erster Stelle und nicht in einer Fußnote.

### 1.1 Der Bilderlauf ist nur für **eine** Logo-Wahl gefahren

Das Konzept verlangt ihn „für **beide** Logo-Wahlen" (Abschnitt 6). Gefahren
ist er mit der Standardwahl (Hubschrauber). Die Umstellung im Profil ließ sich
in der Probe nicht auslösen: Die Wahlliste arbeitet mit ausgeblendeten
Radioknöpfen, und der Klick auf die sichtbare Zeile kam im Skript nicht durch.

**Was stattdessen belegt ist:** die **Geometrie beider Logos** direkt gemessen,
bei beiden vorkommenden Höhen (34 px Kopfleiste, 56 px Anmeldung), und ein
Bild beider Logos nebeneinander in der Kopfleiste. Was fehlt, ist der Nachweis
über die echte Einstellung — also ob die Wahl korrekt greift und ob die Seiten
mit dem Bodenlogo irgendwo überlaufen. **Prüfliste Punkt 5.**

### 1.2 Die Uhr-Kacheln sind nicht übersetzt worden

`tools/uhr-bilder/erzeugen.sh` ist gelaufen, die vier geänderten
`logo_boden.png` liegen im Repositorium. **Nicht** gelaufen ist
`tools/uhr-pruefstand/pruefstand.sh reihe` — es fehlt das Garmin-SDK im
Umsetzungscontainer.

Die Kacheln sind Ableitungen mit **unveränderten Maßen** (nur der Bildinhalt
verschiebt sich um ein bis zwei Bildpunkte); ein Übersetzungsfehler ist nicht
zu erwarten, belegt ist er nicht. **Ausgeliefert werden sie ohnehin erst mit
S5** (E-S3-04) — dort gehört der Übersetzungslauf hin. **Prüfliste Punkt 10.**

### 1.3 Die Autosuche ist gegen einen **abgefangenen** Photon geprüft

Die Anfragen wurden im Browser abgefangen und gezählt. Das prüft Entprellung
(400 ms), Mindestlänge (3 Zeichen) und „höchstens eine offene Anfrage"
**genau** — und es prüft **nicht**, ob die Antwort des echten Dienstes noch
richtig gelesen wird. Am Antwortformat hat sich nichts geändert; ein Beleg ist
das nicht. **Prüfliste Punkt 2.**

### 1.4 Bedienzustände fehlen durchgehend

Stilvergleich und Bilderlauf messen **ruhendes** Markup. Nicht gemessen sind:
aufgeklappte Aktionsmenüs, geöffnete Dialoge, Hover- und Fokuszustände der
Leisteneinträge, die Schublade unter 1024 px im geöffneten Zustand, der
**gesperrte** Zustand der Einsatzansicht (Balken mit Entsperren-Knopf) und der
Doppelring eines Markers, an dem eine Spur beginnt **und** endet.
**Prüfliste Punkte 1, 6, 7.**

### 1.5 Einzelnes, das im Bestand nicht vorkam

- **Das Schloss an „Name"** (AP6) steht im Code auf demselben Weg wie das an
  „Geboren", war aber in keinem geprüften Einsatz zu sehen: Im Demo-Bestand
  trägt keiner der beiden einen Patientennamen. Belegt ist der Codeweg.
- **Ein Bestand, in dem eine Auswahlspalte nur den Wert 0 trägt** (AP9) —
  „0 Cycles" ohne jeden anderen Windeneintrag. Der Code behandelt das
  ausdrücklich; ein solcher Bestand ließ sich nicht herstellen.
- **Vier der sieben gesperrten Demo-Aktionen** (AP10) sind nicht einzeln mit
  einem POST beschossen worden (`einspielen`, `freigeben`, `widerrufen`,
  `paket_loeschen`). Sie stehen in derselben Liste und werden an derselben
  Stelle abgewiesen wie die drei geprüften.

### 1.6 Die Messungen liefen in einem geteilten Behälter

Die Zeitmessungen (0,06 ms je Sichtbarkeitsdurchgang, Entprellzeiten) sind
Größenordnungen, keine Bestwerte. Für die Aussagen, die daraus gezogen werden
(„das fällt nicht ins Gewicht"), reicht das; für einen Vergleich mit anderen
Maschinen nicht.

### 1.7 Die Gegenprobe zum Markerversatz ist eine **Nachstellung**

Sie setzt Namensschild und unbestimmte Größe im laufenden Browser wieder ein
und misst dann 51,7 px Versatz. Das zeigt, dass die beschriebene Kette den
Versatz erzeugt — nicht, dass der alte Code genau diese Kette hatte. Dafür ist
der Codeweg der Beleg: `iconSize: null` und `iconAnchor: [22, 22]` stehen im
Git-Stand vor diesem Paket.

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Was | Mittel | Zahl |
|---|---|---|
| Kaskade unverändert außer den geplanten Regeln | `tools/stilvergleich/kaskade.py`, je Paket gegen den Vorstand | AP2 **14** geänderte Endwerte · AP3 2 neu + 1 geändert · AP4 4 entfallen + 1 neu + 3 geändert · AP5 8 neu · AP6 1 geändert · AP7 8 entfallen + 1 neu + 3 geändert · AP8 4 entfallen + 1 geändert. **In allen Paketen: 0 Reihenfolgetausche** |
| Berechnete Stile im Browser | `tools/stilvergleich/stilvergleich.js`, 4 Proben × 13 Breiten (AP2) | 38 857 Elementmessungen |
| **Vollständige Gegenprobe dazu** | eigener Lauf, der **jede** (Element, Eigenschaft)-Abweichung nennt statt acht Beispielen | **8 703 942 Einzelmessungen**, 6 340 abweichend, **147** verschiedene Paare — 68 beabsichtigt (`margin`), 79 geometrische Folge. **Keine darüber hinaus** |
| Nichts verlorengegangen | `tools/vollstaendigkeit/pruefen.py`, nach jedem Paket | **260 Befunde**, Zeile für Zeile gleich der Basis vor S3 |
| Alle Seiten in acht Breiten | `tools/screenshots/aufnehmen.mjs`, voller Lauf nach jedem Paket | **304 Einzelbilder**, 38 Seiten: **0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| Gegenprobe gegen F-P3-AQ | `md5sum` über alle 304 Bilder | **302 verschieden**; die zwei Dubletten sind Tagesübersicht mit und ohne Schublade bei 1280 und 1440 px — oberhalb der Schwelle gibt es keine Schublade |
| Kontraste | `tools/screenshots/kontrast.py` | 21 Paare, **0 verfehlt**; `--asphalt` auf der Leistenfläche **19,29 : 1** |
| Begriffe | `tools/wortliste/wortliste.py` | **0 / 0 / 0** (Treffer außerhalb der Ausnahmen / ungenutzte Ausnahmen / durchgerutschte Fallen), 66 statt 67 Ausnahmeregeln |
| Markerversatz | eigene DOM-Probe, 6 Zoomstufen, mit Nachstellung des Altzustands | **51,7 px → 0,0 px** |
| Autosuche | Netzwerkmitschnitt mit abgefangenen Anfragen, 3 Ortsfelder | flüssiges Tippen → **1** Anfrage · 2 Zeichen → **0** · Lupe → 1 nach **< 50 ms** |
| Filterausblendung | Browserprobe an zwei echten Konten + Gegenprobe über die Oberfläche | volles Konto **0** ausgeblendet · leeres Konto **12** · geteilter Link holt **genau 1** zurück · nach dem Anlegen eines Fehleinsatzes erscheint **genau** dieser Filter |
| Demo-Sperre | direkte POSTs mit gültigem CSRF | **3 von 3** abgewiesen, Gegenprobe am normalen Konto durchgelaufen |
| Logogeometrie | gezeichnete Maße bei 34 px und 56 px | 54,5 × 34 gegen **42,6 × 34** · Flächenverhältnis **2,01 → 1,28** |
| Uhr-Kacheln | Bildvergleich aller 17 PNG | **4 geändert**, 13 bildgleich |
| Syntax | `php -l` (alle geänderten PHP), `node --check` (alle geänderten JS) | keine Fehler |

---

## 3. Was im Browser geprüft wurde

- **Sammelleiste** an allen vier Stellen (Spurenseite, NutzerInnen-Liste,
  Einsatzformular, Rechtstexte) in bis zu acht Breiten.
- **Einsatzansicht** mit je einem Luft- und einem Bodeneinsatz.
- **Kontentabelle** und **Tagesübersicht** in vier bzw. fünf Breiten.
- **Kennzahl-Kacheln** in der schmalen Monatsansicht (der Fall, in dem sie
  unterschiedlich hoch werden).
- **Demo-Kontoseite** gegen ein normales Konto.
- **Spurenseite mobil** (412 px) vor und nach der Gerüst-Behebung.
- **Anlegen eines Diensttags und eines Einsatzes** über die Oberfläche
  (als Gegenprobe zur Filterausblendung).

---

## 4. Prüfliste — was du tun musst

Je Punkt: der Bedienweg, das erwartete Ergebnis und **woran ein Scheitern zu
erkennen ist**.

### 1. Die vier Funktionsänderungen bedienen  *(wichtigster Punkt)*

| | Weg | erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **a** | Einen **luftgebundenen** Einsatz mit Einsatzort öffnen | unter der Adresse steht **„Höhe 1917 m"** — mit dem Wort | „1917 m" ohne Wort, oder die Zeile fehlt |
| **b** | Einen **bodengebundenen** Einsatz öffnen | **keine** Höhenzeile, auch keine leere | eine Zeile „Höhe" ohne Wert, oder eine Höhe steht da |
| **c** | Einstellungen → Standorte, im Ortsfeld einen Ort tippen | nach ~0,4 s erscheint die Vorschlagsliste, **ohne** Klick auf die Lupe | nichts erscheint (dann sucht die Autosuche nicht) · die Liste flackert bei jedem Buchstaben (dann greift die Entprellung nicht) |
| **d** | Dasselbe Feld: Lupe klicken | Liste erscheint **sofort** | Verzögerung von einer halben Sekunde |
| **e** | Suche öffnen | die Filterblöcke **Transport** und **Bergrettung** stehen da, wenn dein Bestand sie füllt | ein Block fehlt, obwohl du Windeneinsätze dokumentiert hast → melden |
| **f** | Administration → NutzerInnen → **Demo NutzerIn** | Formular grau, kein Block „Sicherungen", nur „Zum Demo-Konto" oben | Felder sind bedienbar → melden |
| **g** | Administration → Demo-Konto → **Zurücksetzen** | funktioniert wie bisher, danach steht der Name weiter auf **„Demo NutzerIn"** | der Name springt auf etwas anderes zurück |

### 2. Die Ortssuche gegen den **echten** Photon

Ortsfeld auf **Einsatzort**, **Zielklinik** und **Standort** je einen echten
Ort tippen. **Erwartet:** Vorschläge kommen, ein Klick übernimmt Adresse bzw.
Koordinaten wie bisher. **Scheitern:** Die Liste bleibt leer, obwohl das Netz
steht — dann liest der Code die Antwort nicht mehr richtig (siehe 1.3). Ein
Blick in die Entwicklerkonsole zeigt es sofort.

### 3. Der Rhythmus auf deinen echten Daten

Einmal durch: Tagesübersicht, Einsatzansicht, Einsatzformular, Einstellungen,
Suche, NutzerInnen. **Erwartet:** Karten stehen **weiter** auseinander als die
Felder darin; Überschriften kleben an ihrem Inhalt. **Scheitern:** eine Stelle,
an der zwei Karten enger stehen als zwei Felder — dann ist dort eine Regel
übersehen worden. **Bitte mit Seitennamen melden**, nicht selbst nachbessern:
Der Rhythmus gehört an den Baustein.

### 4. Die Sammelleiste

NutzerInnen-Liste, ein Kästchen ankreuzen. **Erwartet:** Die Leiste erscheint
unten, **so breit wie die Karte darüber**, mit demselben abgerundeten Rand;
der Knopf steht **rechts**, die Zahl links daneben. Dasselbe auf der
Spurenseite eines Diensttags. **Scheitern:** Die Leiste ist breiter als die
Karte oder hat scharfe Ecken.

### 5. Beide Logo-Wahlen  *(die Lücke aus 1.1)*

Profil → Logo → **Fahrzeug (NEF)** wählen und speichern. Dann durch die
Anwendung gehen. **Erwartet:** Das Bodenlogo steht in der Kopfleiste **gleich
hoch** wie vorher das Luftlogo und wirkt nicht mehr kleiner; beim Laden
**springt nichts**. **Scheitern:** Das Logo bleibt klein (dann hat der
Browser eine alte Datei — Strg+F5) · der Text daneben rutscht beim Laden
(dann greift `ui_logo_masse()` nicht).

Danach auf **„Wechselnd"** stellen und zweimal ab- und anmelden: Das Logo
soll je Anmeldung wechseln, **innerhalb** einer Sitzung aber stehen bleiben.

### 6. Die Karte

Einen Einsatz mit Zielklinik öffnen und **weit herauszoomen**. **Erwartet:**
Haus- und Klinik-Symbol bleiben auf ihrem Punkt; sie tragen **keinen Namen**
mehr (der erscheint als Kurzinfo beim Zeigen). Der Einsatzort ist ein oranger
Kreis **ohne weißen Rand**. **Scheitern:** Ein Symbol wandert beim Zoomen nach
rechts — dann ist der Anker wieder falsch, und zwar an einer Stelle, die hier
nicht geprüft wurde.

Auf der **Tagesübersicht**: Es stehen **keine** Klinik-Symbole mehr — nur der
Standort und die Einsatzorte.

**Und der Fall, der hier fehlte:** ein Diensttag, dessen Spur am **Standort
beginnt und endet** — das Haus-Symbol soll dann einen **Doppelring** tragen
(außen rot, innen blau).

### 7. Die Leiste und die Menüpunkte

**Erwartet:** Die Menüpunkte stehen in normaler Schrift, **nur der
ausgewählte** ist fett und orange hinterlegt. Die Überschrift („Diensttage",
„Einstellungen", „Administration", „Filter") ist **größer und dunkler** als
vorher und steht **nicht mehr in Großbuchstaben**. **Scheitern:** Alle Punkte
sind fett (alte Datei im Browser) · die Überschrift ist kaum zu lesen.

Auf dem Handy zusätzlich: **Schublade öffnen**, mit dem Finger durch die
Liste — der aktive Punkt muss auch dort der einzige fette sein.

### 8. Die Einsatzansicht

**Erwartet:** Die Blöcke **Einsatz** und **PatientIn** tragen beide oben die
blaue Plakette **„verschlüsselt"**; Einsatzort, Beschreibung, Diagnose, Name
und Geburtsdatum tragen daneben ein kleines **Schloss**, das **auf Höhe des
Wortes** sitzt. Nach dem Entsperren steht **kein** blauer Balken mehr da.
**Scheitern:** Der Balken „Geschützte Angaben sind entsperrt" ist noch da ·
das Schloss hängt sichtbar zu hoch oder zu tief.

**Der Fall, der hier fehlte:** ein Einsatz **mit Patientennamen** — dort muss
das Schloss neben „Name" stehen (siehe 1.5).

Und der **gesperrte** Zustand: abmelden, neu anmelden, ohne zu entsperren
einen Einsatz öffnen. **Erwartet:** blauer Balken mit **Entsperren**-Knopf.

### 9. Die Spurenseite mobil

Diensttag → Spuren, auf dem **Telefon**. **Erwartet:** Titel, Karte und
Kartenbaustein haben links und rechts Luft; oben steht die Kopfleiste mit dem
Menüknopf. Der Datenschutzhinweis über der Liste ist ein **roter Kasten mit
Schloss**. **Scheitern:** Der Titel klebt am Bildrand (dann fehlt das Gerüst
wieder) · der Hinweis ist weiß oder blau.

### 10. Vor der nächsten Uhr-Auslieferung (S5)

`tools/uhr-pruefstand/pruefstand.sh reihe` laufen lassen — die vier neuen
`logo_boden.png` sind nie übersetzt worden (siehe 1.2). **Erwartet:** Alle
Geräte übersetzen. Das gehört **in S5**, nicht hierher.

### 11. Nach dem Deploy

- **Keine Migration** — `update.php` ist **nicht** nötig.
- Einmal **Strg+F5**: Die Versionsnummer in der Fußzeile muss **12.4.2**
  zeigen. Steht dort etwas Älteres, hat der Browser alte Dateien.

---

## 5. Grenzen der benutzten Prüfmittel

- **Der Bilderlauf misst waagerechten Überlauf**, nicht Randlosigkeit. Eine
  Seite ohne Innenabstand läuft nicht über — sie ist nur randlos. Genau so ist
  `tag_spuren.php` zwei Jahre durchgerutscht (F-S3-C, Backlog Nr. 58).
- **Die Vollständigkeitsprüfung sucht Klassen als Literale.** Eine Klasse, die
  zur Laufzeit zusammengesetzt wird (`'meldung-' . $ton`), sieht sie nicht —
  deshalb blieb ein ungestalteter Meldungskasten zwei Jahre unbemerkt
  (F-S3-B).
- **Der Stilvergleich misst statisches Markup**, keine Bedienzustände, und
  seine Proben sind zerlegtes Markup: Ein Baustein, der sein Markup über
  mehrere Zeichenketten zusammensetzt, erscheint darin in Stücken.
- **Die Wortliste zählt Wörter**, nicht Bedeutung. Sie hat in diesem Paket
  eine Ausnahme überflüssig gemacht (der neue Platzhalter ist von sich aus
  neutral) — aber sie hätte auch einen neutral formulierten Unsinn
  durchgelassen.
- **`kontrast.py` rechnet Tokenpaare**, nicht die tatsächlich übereinander
  liegenden Flächen einer Seite. Wer eine Farbe auf eine Fläche setzt, die im
  Paarkatalog nicht steht, bekommt keine Warnung.
- **Der Favicon-Generator schrieb bis zu diesem Paket auch dann eine Datei,
  wenn das Bild gar nicht geladen hatte.** Das ist behoben; die Lehre gilt
  weiter: Ein grüner Lauf ist kein Beleg, solange nicht dasteht, **was**
  gemessen wurde.
