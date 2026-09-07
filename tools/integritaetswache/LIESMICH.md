# Integritätswache — läuft die ausgelieferte Fassung noch der aus?

```
python3 tools/integritaetswache/wache.py [basisadresse]
python3 tools/integritaetswache/wache.py --selbstprobe
python3 tools/integritaetswache/wache.py https://127.0.0.1:8443 --unsicher
```

Vorgabe der Adresse: `$WACHE_BASIS`, sonst `https://nadoku.gen-em.org`.
`--unsicher` schaltet die Zertifikatsprüfung ab — **nur** für eine lokale
Installation mit selbst ausgestelltem Zertifikat.

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
(`.github/workflows/deploy.yml`). Was unter `assets/` liegt, ist auf dem Server
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
| Selbstprobe | Erkennt sie eine Abweichung überhaupt? Zwölf Erwartungen, ohne Netz — darunter fünf ausdrücklich „Abweichung erkannt" |
| 1 | Jede Datei unter `server/assets/` (ohne `.md`) — SHA-256 der Auslieferung gegen die des Repositoriums |
| 2 | Die **ganze Menge** der Skripte und Formulare der Anmeldeseite (`login.php`): jeder `<script src>`, jeder Inline-Block, jedes `<form>`-Tag — nichts darf fehlen, verändert sein **oder dazukommen** |

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

**Die Selbstprobe läuft in der Action zuerst**, und das ist kein Formalismus:
Ein grüner Lauf einer Wache, die *immer* grün meldet, sieht genauso aus wie
einer, der nichts gefunden hat.

Gemessen am 07.09.2026 gegen die lokale Installation: **112 Dateien, 112
gleich; 1 Inline-Block, 1 externes Skript, 1 Formular gleich, nichts zu
viel**. Gegenprobe mit **einer** veränderten Kennung in `crypto.js` (37 434 B,
Länge unverändert): **111 gleich, 1 abweichend**, Rückgabewert 1, mit
Dateiname und beiden Summen. Gegenprobe mit einem über `ui.php`
eingeschleusten Fremd-Skript: **1 zusätzliches Skript** gemeldet,
Rückgabewert 1.

## Wann sie läuft

`.github/workflows/integritaet.yml`: **täglich** um 04:17 UTC (krumme Minute mit
Absicht — zur vollen Stunde stehen bei GitHub Millionen Jobs an), **nach jedem
Deploy** (`workflow_run`) und von Hand über den Actions-Tab.

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
  außer dem, was davon auf der Anmeldeseite ankommt: Skripte und Formulare.
- **Sie vergleicht die Anmeldeseite, nicht jede Seite.** Ein Skript, das
  `ui.php` in jede Seite einschleust, fällt dort auf; eines, das nur auf einer
  angemeldeten Seite erscheint, nicht. Dort liegt der Inhaltsschlüssel schon
  im Browser — das ist die Grenze, an der die Wache endet und die CSP
  (Backlog Nr. 8, P5) anfängt.
- **Sie vergleicht keine Stylesheets und keine übrigen HTML-Änderungen.**
  Die Datei `assets/style.css` selbst ist über Teil 1 geprüft; ob die Seite
  ein *zusätzliches* Stylesheet lädt oder ihr Markup sonst geändert ist, sieht
  sie nicht. Ohne Skript und ohne Formular verlässt das Passwort die Seite
  nicht — deshalb reichen die beiden.
- **Sie sieht nicht, ob der Deploy vollständig war.** Eine Datei, die es im
  Repositorium gibt und auf dem Server nicht, meldet sie als „nicht
  erreichbar" — das ist derselbe rote Lauf, aber die andere Ursache.
