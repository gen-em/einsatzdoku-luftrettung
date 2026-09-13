# Bildaufnahme aller Seiten in acht Breiten

Entstanden in P3 (Konzept, Anlage F). Zusammen mit
`tools/vollstaendigkeit/` ersetzt sie den Stilvergleich für die Dauer der
Phase.

## Warum es sie gibt

Ein Redesign, das „voll mobiltauglich auf allen Seiten" verspricht, muss das
auf allen Seiten belegen — und zwar bei jeder Breite, nicht bei der einen,
die gerade offen war. 44 Seiten mal acht Breiten sind 352 Bilder; von Hand
macht das niemand zweimal. (Die Zahl stand hier lange bei „30 Seiten … 240
Bilder" und war schon vor S9/AP5b falsch — `seiten.json` führte 46 Seiten,
ein voller Lauf machte 368 Bilder. Sie ist mit dem Streichen der beiden
Admin-Stammdatenseiten nachgezogen worden; maßgeblich ist immer
`seiten.json`, nicht dieser Satz.)

**Eine Seite braucht ihre Parameter.** Steht in `seiten.json` ein Pfad, den
die Anwendung ohne Abfrageteil ablehnt, fotografiert das Werkzeug die Seite,
auf die sie umleitet — und meldet für sie brav „kein Überlauf". Genau das ist
mit `zeitraum.php` passiert: Ohne `?y=` leitet sie auf die Startseite um, und
der Kontaktbogen „14-zeitraum" zeigte acht Bilder der Tagesübersicht (F-P3-AH,
P3/O7). Wer eine Seite aufnimmt, prüft **einmal am Bild**, dass es die
gemeinte ist.

**Und es prüft, ob es die richtige Seite vor sich hat.** Bis Web 9.10.1 tat es
das nicht, und der Preis war hoch: Der Lauf meldete „31 Seiten, 0 Überlauf,
0 Konsolenfehler" — 22 dieser 31 Seiten waren Bilder der **Anmeldeseite**.
176 von 248 Einzelbildern, byteweise identisch. Zwei Ursachen, beide behoben
(F-P3-AQ):

- **Die Sitzung stirbt mitten im Lauf.** Das Demo-Konto setzt sich alle 30
  Minuten zurück und erhöht dabei die Sitzungs-Epoche; `auth_guard.php`
  beendet daraufhin jede offene Sitzung — und der Lauf löst den fälligen Reset
  durch seine **eigenen** Anfragen aus. Die Prüfung stand einmal, direkt nach
  dem Anmelden; danach hat nichts mehr hingesehen. Jetzt wird nach **jedem**
  Seitenaufruf geprüft, bei Bedarf neu angemeldet und einmal wiederholt.
- **Ein Platzhalter, der sich nicht auflösen lässt**, ergibt kein Bild mehr.
  Vorher fiel er auf `index.php` zurück oder fehlte ganz — dann wurde
  `__FORMULAR__` als Adresse aufgerufen, und der Server antwortete mit **200**
  und der Startseite.
- **Ein abweichender Statuscode** ergibt kein Bild mehr (seit O11). Erwartet
  werden 200; eine Seite, die es anders meint, sagt das in `seiten.json` mit
  `"status": 404`. Der Fund dahinter: `diensttag_zusammenfuehren.php` stand
  ohne Parameter in der Liste, lieferte 404 mit der Abbruchseite — und acht
  Bilder davon galten als „kein Überlauf" (F-P3-AV).

In beiden Fällen entsteht jetzt **kein Bild**, sondern ein Fehler, und der
Rückgabewert ist ≠ 0. Ein fehlendes Bild ist eine Auskunft; ein falsches ist
eine Lüge, die durch jede weitere Prüfung durchmarschiert.

**Die einfachste Gegenprobe** — sie hätte den Fehler jederzeit gefunden:

```
cd tools/screenshots/ausgabe/einzeln
ls *.png | wc -l                                  # 248
md5sum *.png | cut -d' ' -f1 | sort -u | wc -l    # muss dieselbe Zahl sein
```

Stehen dort zwei verschiedene Zahlen, zeigen mehrere Seiten dasselbe Bild.

Das Werkzeug **misst** dabei mit, statt nur zu fotografieren. Drei Zahlen,
die sonst niemand nachhält:

- **waagerechter Überlauf** (`scrollWidth > innerWidth`) je Seite und Breite —
  der Prüfpunkt P-P3-06;
- **Konsolenfehler** je Seite und Breite — gezählt werden Konsolenmeldungen
  vom Typ `error`, die `istRauschen()` durchlässt, **und jede** `pageerror`
  (eine nicht abgefangene JavaScript-Ausnahme). Die zweite Sorte läuft
  absichtlich **nicht** durch den Rauschfilter: Eine Ausnahme im eigenen Code
  ist nie Rauschen. Wer die Zahl liest, liest also zwei Dinge in einer;
- **Knopfhöhen**: jedes `.knopf` muss so hoch sein, wie es die **emulierte
  Eingabeart** verlangt (P-P3-04, seit Web 15.5.0 zwei Sollwerte):
  **44 px** am Fingergerät und unter 1024 px, **36 px** am Zeigergerät ab
  1024 px (E-S8-09/R76). Benannte Ausnahme bleibt der Filterknopf neben dem
  48-px-Suchfeld der Suche.

Die Kontraste der Token rechnet `kontrast.py` daneben (P-P3-05).

## Voraussetzung

Eine laufende lokale Installation mit dem Referenzdatensatz und dem
Demo-Konto:

```
sh tools/referenzdatensatz/einspielen/lokal_starten.sh
```

Wie sie entsteht, steht in `tools/referenzdatensatz/LIESMICH.md`.

## Aufruf

```
node tools/screenshots/aufnehmen.mjs                  # alles
node tools/screenshots/aufnehmen.mjs --nur 10-,12-    # nur diese Seiten
node tools/screenshots/aufnehmen.mjs --klein          # 1× statt 2×
node tools/screenshots/aufnehmen.mjs --finger         # als Fingergerät
node tools/screenshots/aufnehmen.mjs --selbstprobe    # nur die Rauschprobe
python3 tools/screenshots/kontrast.py                 # Kontraste der Token
```

`--selbstprobe` prüft nur `istRauschen()` gegen **fünfzehn** gebaute Fälle und
endet mit ≠ 0, wenn einer davon anders eingestuft wird als erwartet. Sie
braucht **keinen laufenden Browser und keinen Server** — das Playwright-Modul
muss aber vorhanden sein, weil die Datei es an ihrem Kopf lädt; ohne
Installation bricht sie mit `ERR_MODULE_NOT_FOUND` ab. Sie **löscht die
Ausgabe nicht** — alle anderen Aufrufe tun das (siehe Grenzen).

### Trägt jede Klasse einen Fall?

Die Probe selbst braucht eine Gegenprobe, und zwar aus einem gemessenen Grund:
Ihr **erster** Entwurf hatte zehn Fälle und meldete **10 von 10 auch dann, wenn
man Klasse 1 oder Klasse 3 aus `istRauschen()` löschte** — alle verwerfenden
Fälle trugen einen Kachelgastgeber in der URL, also fing sie Klasse 1, und fiel
die weg, fing sie Klasse 3. Die Probe belegte damit die Unterscheidung nicht,
um die es ging; sie bestätigte sich selbst. Die Fälle 11 bis 13 lösen das auf.

Nachgemessen wird es, indem man jede Klasse einzeln herausnimmt — die Probe
muss **jedes Mal rot** werden:

```
S=/tmp/mut; mkdir -p $S; cp tools/screenshots/seiten.json $S/
lauf() { cp tools/screenshots/aufnehmen.mjs $S/a.mjs; eval "$2"
         printf '%-34s' "$1"; node $S/a.mjs --selbstprobe | tail -1; }
lauf "unverändert"           "true"
lauf "Klasse 1 gelöscht"     "sed -i '/FREMDE_QUELLEN.test(text)/d' \$S/a.mjs"
lauf "Klasse 2 gelöscht"     "sed -i '/nur ein Statuscode/,+2d' \$S/a.mjs"
lauf "Klasse-2-Schranke weg"  "sed -i 's/&& !VERBINDUNGSCODES.test(text))/)/' \$S/a.mjs"
lauf "Klasse 3 gelöscht"     "sed -i \"/herkunft(ort) === 'fremd'/d\" \$S/a.mjs"
lauf "herkunft: keine→fremd" "sed -i \"s/if (!o || o === 'null') return 'keine';/if (false) return 'keine';/\" \$S/a.mjs"
```

Gemessen am 13.09.2026: **15 von 15** unverändert, und **14 von 15** in allen
fünf Mutationen; dazu die sechste von Hand — den `catch`-Zweig von
`herkunft()` auf `'fremd'` gestellt, ebenfalls **14 von 15** (das ist der Fall
`<anonymous>`). Bleibt eine grün, ist der zugehörige Fall verlorengegangen.

Rückgabewert ≠ 0, sobald Überlauf, Konsolenfehler oder ein Knopf mit falscher
Höhe gefunden wird.

### Zeiger oder Finger

Ohne `--finger` läuft der Browser als **Zeigergerät** — das ist der Regelfall
an einem Bildschirm ab 1024 px, und die Bilder sollen den Regelfall zeigen.
Mit `--finger` läuft derselbe Lauf als **Fingergerät**; dort gelten überall
44 px. Beide Läufe messen alles, nur der Sollwert der Knopfhöhe unterscheidet
sich. Das Konzept S8 beschrieb es andersherum (Finger als Regel, Zeiger als
Zugabe); gedreht wurde es aus dem genannten Grund.

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
Zeitfenster `tools/kopplungsprobe/rundlauf.mjs` fährt, kann den Topf trotzdem
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
  `tools/vollstaendigkeit/`; **das ist falsch**, nachgesehen am 13.09.2026:
  Dessen Gruppe 5 kennt genau zwei Zusagen (native Dialoge, Seite ohne
  Gerüst), und **kein** Werkzeug des Repositoriums zählt „keine fremde Quelle
  zur Laufzeit" nach — eine CSP schickt die Anwendung auch nicht
  (0 Fundstellen für `Content-Security-Policy` unter `server/`). Die Lücke ist
  **Backlog Nr. 179**. Bis dahin sieht sie niemand.
- **Jeder Lauf löscht den vorigen.** `ausgabe/` wird beim Start geräumt
  (`rmSync`). Zwei Läufe zu vergleichen geht nur, wenn der erste Bericht
  vorher weggesichert wurde — sonst ist seine Zahl hinterher unbelegbar. In
  Backlog-Runde 3 hat genau das eine Zahl gekostet: Ein früherer Lauf hatte
  15 Konsolenfehler gemeldet, der Abschlusslauf 0, und der alte Bericht war
  nicht mehr da.
