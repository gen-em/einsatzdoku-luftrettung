# Bildaufnahme aller Seiten in acht Breiten

Entstanden in P3 (Konzept, Anlage F). Zusammen mit
`tools/vollstaendigkeit/` ersetzt sie den Stilvergleich für die Dauer der
Phase.

## Warum es sie gibt

Ein Redesign, das „voll mobiltauglich auf allen Seiten" verspricht, muss das
auf allen Seiten belegen — und zwar bei jeder Breite, nicht bei der einen,
die gerade offen war. 30 Seiten mal acht Breiten sind 240 Bilder; von Hand
macht das niemand zweimal.

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
- **Konsolenfehler** je Seite und Breite;
- **Knopfhöhen**: jedes `.knopf` muss 44 px hoch sein, mobil wie Desktop
  (P-P3-04).

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
python3 tools/screenshots/kontrast.py                 # Kontraste der Token
```

Rückgabewert ≠ 0, sobald Überlauf, Konsolenfehler oder ein Knopf ≠ 44 px
gefunden wird.

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
`demo`, `admin`) und Pfad. Platzhalter in `__GROSSBUCHSTABEN__` werden zur
Laufzeit aus dem Bestand aufgelöst — Kennungen gehören zu **einer**
Installation und dürfen nicht in einer eingecheckten Datei stehen.

`karte: true` wartet zusätzlich auf Leaflet. `vorher` führt Bedienschritte
vor der Aufnahme aus (bisher: `schublade`).

## Drei Fallen, die hier schon zugeschnappt sind

**Der Inhaltsschlüssel hängt an der Registerkarte.** Der erste Entwurf
öffnete je Aufnahme eine neue Seite. Jede davon startete mit leerem
`sessionStorage` — und auf allen 232 Bildern stand der Entsperrdialog statt
des Inhalts. Genau die Angaben, um die es geht (Einsatzort, Diagnose, Alter),
waren auf keinem zu sehen. Jetzt gibt es **eine** Seite je Rolle, und für
jede Breite ändert sich nur die Fenstergröße.

**Nicht jede rote Zeile ist ein Fehler.** Kartenkacheln und Ortssuche sind
bewusste Laufzeitquellen; und die Abbruchseite antwortet mit 404 — das ist
ihre Aufgabe, nicht ihr Fehler. Beides wird ausgefiltert, und zwar über die
**Fundstelle** der Meldung, nicht über ihren Wortlaut. Ein Bericht, der
jede rote Zeile meldet, wird nach zwei Läufen weggeklickt, und dann geht der
echte Fehler mit unter.

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

## Grenzen

- **Nur Chromium.** WebKit (Safari, iOS) und Gecko (Firefox) stehen in der
  Umsetzungsumgebung nicht zur Verfügung. Was nur dort auffiele, fällt hier
  nicht auf.
- **Bedienzustände** sind nur so weit erfasst, wie `seiten.json` sie als
  `vorher`-Schritte führt. Ein geöffnetes Aktionsblatt, ein aufgeklappter
  Kartenkopf, ein Dialog: Was nicht in der Liste steht, ist nicht im Bild.
- **Das Bild sagt nicht, ob es richtig ist.** Es sagt, wie es aussieht. Der
  Abgleich gegen die Mockups bleibt Sichtprüfung.
