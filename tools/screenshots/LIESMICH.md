# Bilderlauf

Nimmt jede Seite in mehreren Breiten auf und misst, was ein Bild allein
nicht zeigt. **Anlass: Nr. 185, Nr. 225.**

## Aufruf

```bash
node tools/screenshots/aufnehmen.mjs --stufe klein|neben|haupt
python3 tools/screenshots/kontrast.py        # Kontrast gegen die Fläche
python3 tools/screenshots/vergleichen.py <vorher>   # hat sich ein Pixel bewegt?
```

`--nur <name>` filtert Seiten, `--finger` misst gegen 44 px.

## Was es misst

Überlauf, **Konsolenfehler**, Knopfhöhen gegen die Sollwerte aus
`CLAUDE.md` 5, Karten außerhalb von `main.inhalt` — und seit PK-04 die
**Bildgleichheit**: Acht identische Dateien wären acht Bilder, bei denen
alles grün meldet, ohne dass die Breite je umgestellt wurde.

**Drei Stufen** (E-PK-14): klein = berührte Seiten, drei Breiten, Chromium ·
neben = alle Seiten, acht Breiten · haupt = alle drei Engines. Die
Risikoliste ist entfallen — der einzige WebKit-Fund lag auf einer Seite,
die nicht darauf stand.

## Was es braucht

Eine Installation, Chromium (haupt: alle drei Engines) und für die
Wartungsseiten ein `--jobs-token`. **Ohne Token bricht der Lauf ab.**

## Erwartete Zahl

Voller Lauf: **496 Einzelbilder, 62 Kontaktbögen, Überlauf 0, Knöpfe
falscher Höhe 0, 162 Karten / 0 außerhalb**.

## Was es nicht kann

Es bedient nichts (dafür `tools/bedienprobe/`) und vergleicht nicht mit
einem früheren Stand (dafür der Stilvergleich).

> **Ein Fund, den man kennen muss, wenn man hier etwas ändert.** Die
> Eingabeart hält nicht von selbst: `hasTouch` am Playwright-Kontext setzt
> sie richtig, aber der erste **Vollseiten-Screenshot** verliert sie.
> Gemessen im ersten `--finger`-Lauf: bei 360 px `hover:false pointer:coarse`,
> ab 390 px wieder `hover:true pointer:fine` — und damit ab 1024 px 36 statt
> 44 px. Der Lauf meldete daraufhin **28 „falsche" Knopfhöhen, die keine
> waren**. Behoben mit `Emulation.setTouchEmulationEnabled` über CDP, vor
> jeder Breite erneut gesendet.
>
> Zwei Sackgassen auf dem Weg dorthin, beide gemessen: `Emulation.
> setEmulatedMedia` kennt `prefers-*` und `forced-colors`, **nicht** `hover`
> und `pointer` — der Aufruf läuft durch und ändert nichts. Und
> `setTouchEmulationEnabled {enabled:false}` ist **nicht** das Gegenteil von
> `{enabled:true}`: An einem Kontext, der ohnehin Zeigergerät ist, kippt der
> Aufruf die Merkmale auf `none`/`coarse`. Im Zeigerlauf wird deshalb gar
> nichts gesendet.

## Ausgabe

Unter `tools/screenshots/ausgabe/` — steht in `.gitignore`, denn 232 Bilder
zu 2× gehören nicht in ein Repositorium.

| | |
|---|---|
| `einzeln/<seite>-<breite>.png` | die Einzelbilder, ganze Seite |
| `bogen/<seite>.png` | der Kontaktbogen: acht Breiten nebeneinander, jede beschriftet |
| `bericht.md` | Zahlen und Befunde zum Mitnehmen ins Prüfdokument |
| `bericht.json` | dasselbe für Werkzeuge |

Der Kontaktbogen entsteht **im Browser selbst**: Die acht Einzelbilder gehen
als `data:`-Adressen in eine Seite, die anschließend fotografiert wird. Ein
Bildbearbeitungswerkzeug wäre für diese eine Aufgabe eine weitere
Abhängigkeit.

## Die Seitenliste

`seiten.json`. Jede Zeile nennt Name, Gruppe, Rolle (`aus` = abgemeldet,
`demo`, `admin`) und Pfad; `status` nennt einen erwarteten Code, wenn es nicht
200 ist. Platzhalter in `__GROSSBUCHSTABEN__` werden zur
Laufzeit aus dem Bestand aufgelöst — Kennungen gehören zu **einer**
Installation und dürfen nicht in einer eingecheckten Datei stehen.

`karte: true` wartet zusätzlich auf Leaflet. `vorher` führt Bedienschritte
vor der Aufnahme aus. Bekannt sind drei:

| Schritt | Was er tut |
|---|---|
| `schublade` | öffnet das Menü (nur unter 1024 px sichtbar — sonst geschieht nichts) |
| `kopplung-rueckfrage` | holt sich über `pair.php` eine **echte** Kopplungssitzung, tippt den Code ins Feld und klickt „Weiter" — Zustand 2 der Karte „Gerät koppeln" |
| `kopplung-warten` | dasselbe, dann noch „Mit meinem Konto verbinden" — Zustand 3, die Karte wartet auf das Gerät |

Die beiden Kopplungsschritte sind **das Gerät**, nicht eine Attrappe davon:
Sie sprechen mit `pair.php`, wie eine Uhr es täte. Der Code wird je Schritt
**einmal** geholt und über alle acht Breiten wiederverwendet — der
Ratenschutz-Topf `pair_start` lässt zwanzig Aufrufe je zehn Minuten und Adresse
zu, ein Lauf mit einer Sitzung je Breite bräuchte sechzehn davon. Wer im selben
Zeitfenster `tools/proben/kopplung/rundlauf.mjs` fährt, kann den Topf trotzdem
füllen; dann meldet der Schritt es ausdrücklich, statt ein Bild des falschen
Zustands aufzunehmen. Zurück bleibt eine Sitzung, die nach zehn Minuten
verfällt — eine Gerätezeile entsteht nie, denn das Gerät sagt in diesem Lauf
kein Ja.

## Sechs Fallen, die hier schon zugeschnappt sind

**Der Inhaltsschlüssel hängt an der Registerkarte.** Der erste Entwurf
öffnete je Aufnahme eine neue Seite. Jede davon startete mit leerem
`sessionStorage` — und auf allen 232 Bildern stand der Entsperrdialog statt
des Inhalts. Genau die Angaben, um die es geht (Einsatzort, Diagnose, Alter),
waren auf keinem zu sehen. Jetzt gibt es **eine** Seite je Rolle, und für
jede Breite ändert sich nur die Fenstergröße.

**Nicht jede rote Zeile ist ein Fehler.** Kartenkacheln und Ortssuche sind
bewusste Laufzeitquellen; und die Abbruchseite antwortet mit 404 — das ist
ihre Aufgabe, nicht ihr Fehler. Ein Bericht, der jede rote Zeile meldet, wird
nach zwei Läufen weggeklickt, und dann geht der echte Fehler mit unter.
`istRauschen()` wirft deshalb drei Klassen weg: **fremde Quellen** am Namen
(Gastgeber im Wortlaut oder in der Fundstelle), **den Statuscode der Seite
selbst** (Fundstelle = Seitenadresse) und **Verbindungsfehler auf einer
fremden Fundstelle**.

**Eine Dokumentation, die das Richtige sagt, belegt nicht, dass der Code es
tut** (Backlog Nr. 176, behoben am 13.09.2026). Der Absatz darüber behauptete
bis dahin, gefiltert werde „über die **Fundstelle** der Meldung, nicht über
ihren Wortlaut" — der Code tat für die dritte Klasse das Gegenteil:
`ERR_CONNECTION_RESET`, `ERR_CONNECTION_CLOSED` und `ERR_ABORTED` standen in
**demselben** Muster wie die Kachelhosts und wurden gegen den Wortlaut geprüft.
Damit fiel jeder Abruf auf dem **eigenen** Server unter das Kartenrauschen,
sobald er mit einem dieser drei scheiterte. Am alten Muster nachgerechnet: von
zehn gebauten Fällen waren **vier** falsch eingestuft, drei davon lokale
Abbrüche. Am laufenden Browser gemessen (Seite geladen, PHP-Server angehalten,
Symbol und API-Aufruf nachgeladen): zwei Konsolenfehler auf der eigenen Basis,
davon verwarf der **alte** Filter **einen** (`api/day.php` mit
`ERR_CONNECTION_RESET`), der neue **keinen** — dieselbe Lehre wie bei F-P3-AQ,
eine Ebene tiefer. Nachzählbar ist die Unterscheidung seither mit
`--selbstprobe`: fünfzehn Fälle mit Sollwert, erwartet **15 von 15**, ohne
laufenden Browser und ohne Server, und ohne die Ausgabe des letzten Laufs zu
löschen.
Drei Entscheidungen darin sind bewusst zur lauten Seite hin getroffen: Eine
Meldung **ohne Fundstelle** wird gezählt, nicht verworfen; der Fehlercode wird
nur noch im Wortlaut gesucht, nicht auch in der Fundstelle (in einer URL kommt
er nicht vor); und **Klasse 2 verwirft nur einen Statuscode** — verliert die
Seite selbst die Verbindung, wird das gezählt, denn ein Verbindungsabbruch ist
kein Statuscode. Die letzte der drei ist erst im zweiten Anlauf dazugekommen
(Abschnitt „Trägt jede Klasse einen Fall?").

**Der Prüf-Browser kommt nicht überall hin, wo `curl` hinkommt.** In der
Claude-Arbeitsumgebung setzt die Egress-Sperre Chromiums TLS-Handschlag zu
den Kachelservern zurück — direkt wie über den Umgebungsproxy, unabhängig
von TLS-Version und Post-Quantum-Merkmalen (per NetLog belegt, F-P3-AC).
Jede Karte war grau. Deshalb fängt eine Playwright-Route die Kachelabrufe ab
und beantwortet sie aus einem **Node-Abruf** (Lager je URL, damit 232
Aufnahmen die Server nicht 232-fach fragen). Nodes eingebautes `fetch`
liest den Proxy wiederum nur, wenn `NODE_USE_ENV_PROXY` **beim
Prozessstart** gesetzt ist — das Skript startet sich dafür einmal selbst
neu. Ohne Proxy (lokaler Rechner) läuft derselbe Weg unverändert direkt;
Nebeneffekt überall: deterministische Kartenbilder.

**Eine Kennung läuft dem Bestand davon.** Die Seiten mit `__TAG_…__` und
`__EINSATZ__` bekommen ihre Kennung einmal, zu Beginn des Laufs. Das
Demo-Konto setzt sich **alle 30 Minuten** zurück; ein voller Lauf dauert
länger. Gemessen am 06.09.2026: Die Einsatzseiten (früh im Lauf) standen, die
sechs Tag- und Aktionsseiten dahinter antworteten mit **404** — 48 von 368
Aufnahmen fielen aus. Der Lauf hat das laut gemeldet und keine Null behauptet,
das war richtig; brauchbar war er trotzdem nicht. Jetzt löst er die
Kennungen **einmal je Seite** neu auf, wenn ein 404 kommt, und wiederholt den
Aufruf. Bleibt der 404 auch mit frischen Kennungen, ist er echt.

**Gemessen, bevor das Stylesheet greift.** `domcontentloaded` heißt nicht,
dass `style.css` angewendet ist. Gemessen an der Abbruchseite bei 1024 px:
`getComputedStyle` lieferte für den Knopf `height: auto`, `font-family:
Times New Roman`, `border-width: 0` — die ungestaltete Seite, Höhe **35 px**
statt 36. Sechs solcher Meldungen standen im Bericht als „Knopf mit falscher
Höhe", und keine war eine. Die Gegenrichtung ist die gefährlichere: Eine
ungestaltete Seite läuft nicht über und wirft keinen Konsolenfehler — sie
meldet **zweimal Null**. Vor jeder Messung wird jetzt gewartet, bis
`--knopf` in `:root` steht; ist es nach fünf Sekunden nicht da, steht das als
Fehler im Bericht statt als grüne Zahl.

## Bildvergleich — hat sich ein Pixel bewegt?

`vergleichen.py` daneben beantwortet die Frage, die der Bilderlauf selbst
**nicht** beantwortet. Überlauf, Konsolenfehler und Knopfhöhen sagen nichts
darüber, ob eine Seite anders aussieht als gestern: 0/0/0 meldet auch eine
Seite, die ein anderes Datum zeigt.

```
python3 tools/screenshots/vergleichen.py <vorher> [<nachher>]
python3 tools/screenshots/vergleichen.py <vorher> --erwartet 46-betrieb-updates
```

**Zwei Vergleiche, und der wichtigere ist der Text.**

| | |
|---|---|
| **Bild** | SHA-256 je Einzelbild. Streng, aber laut — er meldet jede Schriftrasterung mit |
| **Text** | `document.body.innerText` je Seite und Breite, zeilenweise. Das ist die Frage, die ein Formatierungsumbau stellt: *Steht ein Buchstabe anders da als vorher?* |

**Warum beide — mit der Zahl, die es entschieden hat.** Gemessen am 22.09.2026
auf **unverändertem** Code: **303 von 496 Bildern** wichen ab. Ursache war
nicht die Anwendung, sondern der **Countdown im Demo-Banner** („in etwa
43 188 Minuten"), der auf jeder Seite des Demo-Kontos steht und in Echtzeit
herunterzählt — ein Kasten von 43 × 19 Pixeln, der 303 Bilder unbrauchbar
machte. Ein Bildvergleich kann so eine Zeile nicht benennen; er sieht nur
Pixel, und wer die Seite ganz ausnimmt, nimmt 1 200 andere Zeilen mit aus.
Der Textvergleich sieht die Zeile.

Der Bildvergleich bleibt daneben stehen: Er findet, was **kein** Text ist —
eine verrutschte Spalte, ein anderer Abstand, eine Farbe.

> **Über eine Versionsstufe hinweg kann der Bildvergleich nicht null werden**,
> und das ist keine Schwäche, sondern Arithmetik: Die Versionsnummer steht in
> der Fußzeile **jeder** Seite, und jedes Arbeitspaket stuft sie hoch
> (`CLAUDE.md` 2.1). Damit ändert sich jedes einzelne Bild. Wer ein Paket mit
> Versionsstufe belegen will, fährt `--nur-text`; der Bildvergleich ist dann
> für den **nächsten** Lauf ohne Stufe wieder brauchbar.

### Drei Vergleiche, und nur einer ist der Befund

| | misst | ist ein |
|---|---|---|
| **Bild** | SHA-256 je Einzelbild | Zahl mit Rauschen (Fußzeile, Uhrzeiten) |
| **Zeile** | `innerText` Zeile für Zeile | **Zahl, kein Befund** — zwischen zwei Läufen ändern sich Werte zwangsläufig: eine Datenbank wächst, ein Alter läuft weiter |
| **Form** | dieselbe Zeile, jede Ziffernfolge durch `#` ersetzt | **der Befund** |

**Warum die Form das richtige Maß ist.** Ein Formatierungsumbau darf die
*Schreibweise* nicht ändern; die *Werte* ändern sich ohnehin. Aus „1,0 MB" und
„1,3 MB" wird beide Male `#,# MB` — aus „2,00 GB" und „2 GB" dagegen
`#,## GB` und `# GB`, und genau das ist der Unterschied, den ein solches
Paket ausschließen muss. Auch „gerade eben" gegen „vor 1 Minuten" bleibt
sichtbar, weil die Wörter verschieden sind.

**Was die Form nicht sieht:** eine Änderung, die *nur* Ziffern betrifft — etwa
eine andere Rundung bei gleicher Stellenzahl. Dafür stehen die Rechnungen je
Funktion.

**Deshalb braucht der Zeilenvergleich keine Ausnahmeliste**, der Formvergleich
dagegen schon: In Schritt 15 AP7 waren es nach dem Umstieg auf die Form noch
**zwei** Einträge (ein Zufallstoken, eine Gerätekennung) statt der sieben, die
der Zeilenvergleich gebraucht hätte. Eine kurze Ausnahmeliste ist keine
Bequemlichkeit — sie ist der Beleg, dass das Maß zur Frage passt.

Voreinstellung für `<nachher>` ist `ausgabe/`. `--nur-text` lässt den
Bildvergleich außer Wertung, `--selbstprobe` hält das Werkzeug gegen sieben
Fälle mit Sollwert.

**Die Reihenfolge ist nicht wahlfrei, und sie ist der ganze Trick:** Jeder Lauf
löscht den vorigen (`rmSync` auf `ausgabe/`, siehe *Grenzen*). Wer vergleichen
will, sichert den ersten Lauf weg, **bevor** er die Änderung baut. Danach ist es
zu spät.

```
cp -r tools/screenshots/ausgabe /tmp/bilder_vor    # VOR der Änderung
…  bauen …
node tools/screenshots/aufnehmen.mjs
python3 tools/screenshots/vergleichen.py /tmp/bilder_vor
```

**Zwei Arten, eine Abweichung zu erklären, und sie sind nicht dasselbe:**

| | |
|---|---|
| `ausnahmen.json`, Abschnitt `zeitabhaengig` | **dauerhaft, ganze Seite, nur Bild.** Die Seite ändert sich zwischen zwei Läufen, ohne dass jemand etwas geändert hätte |
| `ausnahmen.json`, Abschnitt `zeilenmuster` | **dauerhaft, einzelne Zeile, nur Text.** Ein regulärer Ausdruck auf die Textzeile — so bleibt der Rest der Seite in der Messung. Hier steht heute genau ein Eintrag: der Demo-Countdown |
| `--erwartet <seite>` | **für diesen einen Lauf.** Die Abweichung ist die beabsichtigte Folge der Änderung, die gerade gebaut wurde |

Für beide gilt die Regel des Hauses: **Eine Ausnahme ohne Treffer ist selbst ein
Befund.** Sonst wächst die Liste zu, und der Vergleich meldet eine Null, die
nichts mehr gemessen hat.

**Was ein Bitvergleich nicht kann:** Er sagt, **dass** sich etwas geändert hat,
nicht **was**. Eine gemeldete Datei wird angesehen — das Werkzeug ersetzt den
Blick nicht, es sagt nur, wohin er gehört. Und er ist **streng**: Eine
Abweichung von einem Pixel in einer Schriftrasterung zählt wie eine
verschobene Spalte.

**Der Textvergleich ist kein echter Diff**, und das mit Absicht: Er stellt die
Zeilen stumpf nebeneinander. Eine eingefügte Zeile verschiebt alles dahinter
und erzeugt lauter Abweichungen — das ist die richtige Lautstärke für einen
Umbau, der keinen Buchstaben ändern darf, denn der fügt auch keine Zeile ein.

**`texte/` entsteht erst seit Schritt 15 AP7.** Ein älterer weggesicherter Lauf
hat den Ordner nicht; der Vergleich sagt dann ausdrücklich **„NICHT
GEMESSEN"** statt eine Null zu melden.

## Grenzen

- **Nur Chromium.** WebKit (Safari, iOS) und Gecko (Firefox) stehen in der
  Umsetzungsumgebung nicht zur Verfügung. Was nur dort auffiele, fällt hier
  nicht auf.
- **Bedienzustände** sind nur so weit erfasst, wie `seiten.json` sie als
  `vorher`-Schritte führt. Ein geöffnetes Aktionsblatt, ein aufgeklappter
  Kartenkopf, ein Dialog: Was nicht in der Liste steht, ist nicht im Bild.
- **Das Bild sagt nicht, ob es richtig ist.** Es sagt, wie es aussieht. Der
  Abgleich gegen die Mockups bleibt Sichtprüfung.
- **Ein Verbindungsfehler auf einer fremden Adresse bleibt stumm.** Die dritte
  Rauschklasse verwirft ihn — sie kann nicht wissen, ob die Adresse überhaupt
  abgerufen werden durfte. Hier stand zuerst, das messe
  `tools/quelltext/vollstaendigkeit.py`; **als es hier stand, war das falsch** — dessen
  Gruppe 5 kannte zwei Zusagen, und kein Werkzeug zählte „keine fremde Quelle
  zur Laufzeit" nach. **Seit dem 13.09.2026 tut es das** (Backlog Nr. 179,
  Prüfung `fremde Quelle`, 15 Ausnahmen mit Grund) — aber **am Quelltext**,
  nicht zur Laufzeit. Was erst zur Laufzeit dazukommt, sieht weiterhin
  niemand: Eine Content-Security-Policy schickt die Anwendung nicht
  (**Nr. 181**).
- **Der Bilderlauf misst nicht, ob der Text stimmt.** Überlauf, Konsolenfehler
  und Knopfhöhen melden **0/0/0**, auch wenn aus „2,00 GB" ein „2 GB"
  geworden ist. Dafür gibt es seit Schritt 15 AP7 den Textvergleich
  (`vergleichen.py`) — die Zahl 0/0/0 allein ist **kein** Beleg dafür, dass
  sich nichts geändert hat.
- **Jeder Lauf löscht den vorigen.** `ausgabe/` wird beim Start geräumt
  (`rmSync`). Zwei Läufe zu vergleichen geht nur, wenn der erste Bericht
  vorher weggesichert wurde — sonst ist seine Zahl hinterher unbelegbar. In
  Backlog-Runde 3 hat genau das eine Zahl gekostet: Ein früherer Lauf hatte
  15 Konsolenfehler gemeldet, der Abschlusslauf 0, und der alte Bericht war
  nicht mehr da.
