# Ausgangsmessung — der Stand vor S2

Gemessen am **31.08.2026** gegen `main` = **Web 9.14.0**, an einem Konto mit
**5002 Einsätzen** und **3 201 524 Spurpunkten**. Das ist der Vergleichsmaßstab
für R35: Jede Zahl, die S2 später meldet, gehört gegen die Zahl in derselben
Zeile gehalten.

**Zuerst das, was nicht gemessen werden konnte** — nach K9, an den Anfang und
nicht in eine Fußnote:

- **Echte Hardware.** Das Referenzgerät nach Z3 (fünf Jahre altes Handy) ist
  durch `Emulation.setCPUThrottlingRate` mit Faktor 6 **nachgestellt**. Das
  drosselt die Rechenzeit — nicht den Speicher, nicht die Grafik, nicht die
  Leitung und nicht den langsameren Flash. Wo eine Zahl knapp unter dem
  Zielwert liegt (Suche: 4,53 s gegen 5 s), ist sie **kein Beleg**.
  *(Nachtrag S2/AP9: Diese 4,53 s waren zudem zu einem grossen Teil ein
  Zeitlimit im Pruefmittel — siehe die Berichtigung weiter unten.)*
- **Die Wiederherstellung wurde ohne Drossel gemessen.** Der Einspiellauf
  fährt den Browser ungedrosselt; die 245 s für 5002 Einsätze sind deshalb
  eine **Untergrenze**.
- **Z2 (500 Konten × 600 Einsätze) wurde nicht gemessen.** Dafür bräuchte es
  300 000 Einsätze. Was zu Z2 gesagt wird, ist aus den Zeilenkosten
  hochgerechnet und als solches gekennzeichnet.
- **Die Kartenkacheln sind ausgesperrt.** Sie kommen von einer fremden Quelle
  und sind in dieser Umgebung nicht erreichbar. Die Zeit, die das Zeichnen der
  Karte selbst kostet, steht hier deshalb nicht.
- **Die Haldenspitze wird alle 500 ms abgetastet.** Eine kürzere Spitze
  dazwischen entgeht der Messung; die genannten Werte sind **Untergrenzen**.

---

## 1. Der Bestand

| | |
|---|---|
| Einsätze | **5002** (61 Runden × 82 aus der Referenz) |
| Ruhesegmente | 5795 |
| Diensttage | 915 |
| Spurpunkte | **3 201 524** |
| `.edbak`-Dateien | 21 (20 × 246 Einsätze, eine × 82); je 2,00 MB versiegelt, ~6,9 MB Nutzlast |
| Einspieldauer | **245,1 s** für alle 21 Dateien, 0 Fehler |
| eingespielt / erwartet | **5002 / 5002** |

Hergestellt mit `python3 messen.py --frisch`, ausschließlich über den
regulären Wiederherstellungsweg im Browser. Kein SQL.

**Wie viele Einsätze in eine Datei passen, entscheidet `post_max_size`.** Die
Nutzlast wiegt **28 KB je Einsatz** (2,42 MB für 87 Einsätze mit 55 861
Punkten). Bei den hier eingestellten 8 MB sind das rund 280 Einsätze — die im
Konzept genannten **400–500 gehen sich dort nicht aus** (E-S2-23). Das ist
keine Bremse des Messstands, sondern eine Eigenschaft des heutigen
Einspielwegs: Er schickt die ganze Datei als **einen** POST.

## 2. Browser (Drossel 6×)

| Schritt | gemessen | Zielwert | |
|---|---|---|---|
| Anmelden | 2,36 s | — | |
| Startseite, 500 Tagesverweise | 1,36 s | — | ✓ |
| Tagesansicht bis zur gezeichneten Spur | ~~**4,81 s**~~ **1,17 s** | ≤ 3 s (E-S2-24) | ~~62 % darüber~~ **✓** (Berichtigung S2/AP9) |
| Suche bis zur ersten Trefferanzeige | ~~**4,53 s**~~ **3,81 s** | ≤ 5 s (E-S2-24) | ~~✓ ohne Reserve~~ **✓** (Berichtigung S2/AP9) |
| Backup erstellen | **109,8 s** | ≤ 5 min (E-S2-24) | ✓ |

Und die Gerätebudgets nach Z3, gemessen beim Sichern:

| Größe | gemessen | Z3 | |
|---|---|---|---|
| größte JSON-Zeichenkette | **138,25 MB** | ≤ 10 MB | **13,8×** darüber |
| Haldenspitze | **508 MB** | ≤ 100 MB | **5,1×** darüber |
| PBKDF2-Ableitungen je Vorgang | **1** | 1 | ✓ |
| Backup-Datei | **40,5 MB** | ≤ 25 MB (E-S2-24) | **1,6×** darüber |

Konsolenfehler: **0**.

> **BERICHTIGUNG (S2/AP9, 01.09.2026).** Zwei Aussagen dieses Abschnitts
> stimmen nicht, und beide sind hier stehengeblieben, weil sie plausibel
> klangen.
>
> **Erstens: Die Zeiten 4,53 s und 4,81 s sind zu hoch.** `entsperren()` in
> `browserprobe.mjs` wartete vier Sekunden auf einen Entsperr-Dialog, der bei
> bereits entsperrter Sitzung nie kommt — mitten im gemessenen Abschnitt.
> Gemessen wurde `max(4 s, tatsächliche Dauer)`. Deshalb liegen ausgerechnet
> die beiden auffälligen Werte dicht über vier Sekunden. Der ganze Lauf mit
> der berichtigten Wartelogik, Drossel 6×:
>
> | Schritt | AP0 (mit dem Messfehler) | jetzt | Ziel (E-S2-24) |
> |---|---|---|---|
> | Startseite, 500 Tagesverweise | 1,36 s | 1,39 s | — |
> | **Tagesansicht bis zur gezeichneten Spur** | **4,81 s** | **1,17 s** | ≤ 3 s — **gehalten**, nicht 62 % darüber |
> | Suche bis zur ersten Trefferanzeige | 4,53 s | **3,81 s** | ≤ 5 s |
> | Backup erstellen | 109,8 s | **42,21 s** | ≤ 5 min |
> 
> **Die Tagesansicht war nie über dem Ziel.** Der Befund „62 % darüber"
> löst sich vollständig auf — er war der Timeout. Die Suche liegt mit Reserve
> unter ihrem Ziel statt „ohne Reserve" darauf.
>
> Ein Teil der Verbesserung beim Backup geht auf AP5b und AP6 zurück und
> nicht auf den Messfehler; bei Tagesansicht und Suche ist es der Timeout.
>
> **Zweitens: Es werden sehr wohl alle 5 002 entschlüsselt.** Gezählt:
> `entschluessleListe` bekommt 5 002 Einträge, `crypto.subtle.decrypt` läuft
> **4 880-mal** (die übrigen 122 haben keinen Block). Gedeckelt ist die
> **Anzeige** auf 200, nicht die Entschlüsselung. Der Grund dafür ist
> sachlich — die Freitextsuche sucht in Diagnose, Alter und Einsatzort, und
> die liegen im verschlüsselten Block —, ohne Filter aber unnötig
> (Backlog Nr. 51).
>
> Was stimmt: **PBKDF2 = 0** auf der Suchseite. Der Schlüssel kam aus der
> Sitzung.

**Die Suche ist nicht das Problem, für das sie gehalten wurde** — dieser Satz
gilt weiterhin, nur mit anderer Begründung als der unten stehenden. Die
Entschlüsselung der angezeigten Zeilen kostet **0,1 s**; die übrige Zeit geht
für das drauf, was vor der Tabelle passiert. Der ursprüngliche Wortlaut:
Sie zeigt `5002 · 132.171 km · 200 angezeigt` — die Trefferliste zeigt **200**
Zeilen, nicht 5002, und nur die werden entschlüsselt. Die 4,53 s sind damit
nicht die Kosten von 5000 Entschlüsselungen. AP9 (E-S2-16) sollte das prüfen,
bevor es gegen ein Problem antritt, das anderswo liegt: Der Schlüssel wurde in
diesem Lauf **kein einziges Mal** neu abgeleitet (PBKDF2 = 0 auf der
Suchseite), weil er aus der Sitzung kam.

**Die Tagesansicht liegt über dem Ziel, und zwar ohne Spurmenge.** Der
gemessene Tag hat 13 Spurlinien, die Antwort wiegt 0,5 MB — die 4,81 s
entstehen nicht an der Datenmenge. Auch das gehört vor AP9 nachgesehen.

## 3. Server

| Größe | gemessen | Einordnung |
|---|---|---|
| `track_points` | **193,77 MB**, 3 257 385 Zeilen | Tabelle vorher neu aufgebaut |
| Kosten je Zeile | **62,4 B** | Befund B-S2-02: 62 B — **bestätigt** |
| Spuren je 1000 Einsätze | **38,07 MB** | Befund B-S2-02: 40 MB — bestätigt |
| `edbak_build()` Laufzeit | **14,15 s** | Z3: ≤ 30 s je Anfrage |
| `edbak_build()` Paketgröße | **138,82 MB** | |
| `edbak_build()` Speicherspitze | **1784 MB** | Z3: ≤ 64 MB — **27,9× darüber** |
| Waisen-Vollscan, 3,26 Mio. Zeilen | 0,552 s | |
| Wartungsjob, 6,2 Mio. Waisen | **15,18 s** | läuft in der Anfrage einer Nutzerin |

**Die Speicherspitze ist die härteste Zahl dieser Messung.** `edbak_build()`
hält gleichzeitig das vollständige PHP-Array und die daraus erzeugte
JSON-Zeichenkette; bei 5002 Einsätzen sind das 1,78 GB. Auf einem geteilten
Webspace mit den üblichen 128 oder 256 MB `memory_limit` bricht der Vorgang
mit einem Fatal Error ab — **ohne JSON-Antwort**, so dass der Browser „HTTP
500" oder einen Parserfehler meldet und der Nutzerin nicht gesagt wird, dass
es an der Menge lag. Das Konzept schätzte hier „~740 MB Heap" (B-S2-03); die
Messung liegt **um mehr als das Doppelte darüber**.

**Wo genau heute Schluss ist**, lässt sich daraus ausrechnen: Bei 64 MB
Budget trägt der Weg rund **180 Einsätze**, bei 128 MB rund **360**, bei
256 MB rund **720**. Der Befund B-S2-03 nannte „~400–500 Einsätze mit
Spuren"; das passt zu einem `memory_limit` von 128–192 MB.

## 4. Was der Messstand nebenbei über sich selbst gelernt hat

**6 202 931 verwaiste Spurpunkte.** Beim Aufbau wurde das Messstandkonto
zweimal gelöscht und neu angelegt. Danach standen 9 460 316 Zeilen in
`track_points` statt 3 257 385: Zwei Kontolöschungen hatten **380 MB
Positionsdaten** liegen lassen, weil `track_points` keinen Fremdschlüssel
trägt und die Kaskade sie nicht mitnimmt (F-S2-B). Der Wartungsjob hat sie
danach in **15,18 s** entfernt — beim nächsten Aufruf der Anwendung durch
irgendjemanden.

**Und dreimal dieselbe Falle im eigenen Werkzeug.** Der Messstand hat in
seinem ersten Lauf drei Zahlen gemeldet, die etwas anderes maßen, als sie
behaupteten:

1. **„5046 Einsätze eingespielt"** — angelegt waren 4744. Addiert worden war
   die *erwartete* Zahl, nicht die *gemeldete*; die Anwendung hatte korrekt
   „254 übernommen, 7 übersprungen" berichtet.
2. **„167 MB Spuren je 1000 Einsätze"** — die Tabelle aller Konten geteilt
   durch die Einsätze eines Kontos. Viermal zu viel.
3. **„Startseite 25,6 s, Tagesansicht 30,7 s"** — gemessen war die Wartezeit
   auf gesperrte Kartenkacheln. Dieselben Seiten sind in 1,4 s und 4,8 s da.

Alle drei sind behoben, und alle drei stehen hier, weil sie dieselbe Sorte
Fehler sind, vor der `CLAUDE.md` 6 nach O9c warnt: **Eine Zahl ist erst dann
ein Beleg, wenn sie benennt, was sie gemessen hat.** Ein Prüfmittel ist davor
nicht sicherer als das, was es prüft.

## 5. Was S2 daran zu ändern hat

| Zahl | heute | Ziel (E-S2-24 / Z3) | Faktor |
|---|---|---|---|
| Speicherspitze `edbak_build()` | 1784 MB | 64 MB | **28** |
| größte JSON-Zeichenkette | 138,25 MB | 10 MB | **14** |
| Haldenspitze Browser | 508 MB | 100 MB | **5** |
| Spuren je 1000 Einsätze | 38,07 MB | 3 MB | **13** |
| Backup-Datei | 40,5 MB | 25 MB | **1,6** |
| Tagesansicht | ~~4,81 s~~ **1,17 s** | 3 s | ~~1,6~~ **✓** |
| Suche | ~~4,53 s~~ **3,81 s** | 5 s | ✓ |
| Backup erstellen | 109,8 s | 300 s | ✓ |
| Wiederherstellung | 245 s | 900 s | ✓ (ohne Drossel) |

Die vier großen Faktoren hängen alle an derselben Sache: **die Spurpunkte
liegen als Zeilen und wandern als JSON**. Genau das baut S2 um.

---

## Nachmessung vom 16.09.2026 (Web 20.13.0, P5a/AP9)

**Was gemessen wurde:** ein frischer Messstandlauf gegen `main` +
Arbeitszweig, **5050 Einsätze**, 1000 Diensttage, **2 813 201 Spurpunkte**,
17 `.edbak`-Dateien, CPU sechsfach gedrosselt, lokale Installation
(PHP 8.4.19, MariaDB 10.11). Anlass waren die **drei offenen Messungen aus
Backlog Nr. 37**; die Browserprobe hat dafür zwei neue Schritte bekommen
(Zeitraumübersicht, Nachbearbeitung).

**Zuerst, was nicht gemessen werden konnte:**

- **`post_max_size` des Zielservers.** Die Zahl gehört der Anlage, nicht
  dieser Maschine. Staging stand am 16.09.2026 noch nicht. Der Weg ist seit
  P5a/AP2 die Plattformkarte auf Betrieb → Status, die `post_max_size` und
  `upload_max_filesize` mit Soll- und Ist-Wert nennt — sobald Staging
  antwortet, ist es ein Seitenaufruf und keine Messung mehr.
- **Die Nachbearbeitung mit gefüllter Liste.** Der Messstandbestand kommt aus
  einem Backup, in dem jeder Diensttag zugeordnet ist; gemessen ist die
  Abfrage, nicht das Aufbauen der Liste. Steht dabei.
- **Z2 und echte Hardware** — unverändert wie oben.

| Messung | 16.09.2026 | Zielwert | |
|---|---|---|---|
| Anmelden | 2,09 s | — | |
| Startseite (Tagesliste, 500 Verweise) | 1,20 s | — | |
| Tagesansicht (Spur gezeichnet) | **1,11 s** | 3 s | ✔ |
| Suche — erste Trefferanzeige (5050 Treffer, 200 angezeigt) | **3,18 s** | 5 s | ✔ |
| **Zeitraumübersicht, Jahr 2026 (3983 Einsätze, 3983 `<tr>`)** | **42,61 s** | — | **Befund** |
| **Nachbearbeitung („Zuordnung nachtragen", 0 offene)** | **2,83 s** | — | |
| Backup erstellen | **49,84 s** | 300 s | ✔ |
| Backup-Datei | **11,8 MB** | 25 MB | ✔ |
| Wiederherstellung (17 Dateien, ohne Drossel) | **231,9 s** | 900 s | ✔ |
| Spuren je 1000 Einsätze (nach Verdichtung, mit `OPTIMIZE`) | **3,66 MB** | 3 MB | ✘ knapp |
| Speicherspitze `edbak_build()` in Fenstern | **12 MB** | 64 MB | ✔ |
| Speicherspitze `edbak_build()` am Stück (Admin-Backup) | 1030,8 MB | 64 MB | **16×** |
| Haldenspitze Browser (höchster Schritt) | **55 MB** | 100 MB | ✔ |
| größte JSON-Zeichenkette | **2,2 MB** | 10 MB | ✔ |
| Waisen-Vollscan | 0,033 s | — | 0 Waisen |
| Konsolenfehler | **0** | 0 | ✔ |

**Was sich gegenüber der Ausgangsmessung geändert hat.** Vier der fünf großen
Faktoren sind weg: Die Speicherspitze des Backups liegt im Fensterweg bei
12 statt 1784 MB, die größte JSON-Zeichenkette bei 2,2 statt 138 MB, die
Haldenspitze bei 55 statt 508 MB, die Backup-Datei bei 11,8 statt 40,5 MB.
Übrig sind zwei:

- **Die Spuren je 1000 Einsätze — 3,66 MB gegen 3 MB.** Gemessen **nach**
  Verdichtung und Ausdünnung und **mit** `OPTIMIZE TABLE`; ohne den kamen
  19,30 MB heraus, weil dann freigegebene Seiten mitzählen. Der
  Verdichtungsjob lässt **434 von 5050** Einsätzen liegen — ihr letzter Punkt
  ist keine zwei Wochen alt, und er fasst nur abgeschlossene Aufzeichnungen
  an. Das ist richtig so und heißt: 3,66 MB ist der Wert eines **frisch
  eingespielten** Bestands, nicht der eines gewachsenen.
- **Der Weg „am Stück" des Admin-Backups**, unverändert bei rund einem
  Gigabyte. Er ist seit Web 11.1.0 nicht mehr der Weg der NutzerIn; die
  Zeile steht hier, damit die 12 MB daneben nicht als Zahl für beide Wege
  gelesen werden.

**Und ein neuer Befund: die Zeitraumübersicht.** 42,61 s für ein Jahr mit
3983 Einsätzen, gegen 3,18 s für die Suche über alle 5050. Der Unterschied
ist keine Zufälligkeit, sondern eine Bauentscheidung: `zeitraum.php` ruft
`EdMissionTable.erzeuge` **ohne `seite`** und baut deshalb so viele
Tabellenzeilen, wie der Zeitraum Einsätze hat — sie ist die einzige Ansicht
ohne Seitengrenze. Auch durch die sechsfache Drossel geteilt bleiben gut
sieben Sekunden. Der Umbau ist ein eigenes Paket (Backlog Nr. 37).

## Nachmessung 27.09.2026 — die Zeitraumübersicht mit Seitengrenze (R4-17)

Web 21.4.0 gibt `zeitraum.php` dieselbe Grenze wie der Suche, 200 Zeilen.
Gemessen am Messstandbestand dieses Tages (**4071** Einsätze im Jahr 2026,
**1029** Diensttage), derselbe Rechner vorher und nachher, Drossel 6×,
`messen.py --schritte browser`:

| Messung | vorher | nachher | |
|---|---|---|---|
| Zeitraumübersicht, Dauer bis Playwright die erste Zeile sieht | **88,11 s** (Prüfstand), **109,47 s** (Gegenprobe ohne Grenze) | **8,97 s** / 9,16 s | Faktor 9,6 bis 12 |
| dieselbe, erste Zeile im DOM (von der Seite gemessen, neu) | — | **2,51 s** | |
| Tabellenzeilen | 4071 | **200** | Riegel: mehr als 200 ist rot |
| Startseite, Tagesverweise der Leiste | 500, ohne Hinweis | 500, **mit** Hinweis | Riegel: 500 ohne Hinweis ist rot |
| Suche — erste Trefferanzeige (5096 Treffer) | 5,78 s | 5,95 s | unverändert, Bezug |

**Was die zwei Zahlen der Zeitraumübersicht unterscheidet.** Die Dauer ist
die Zeit, bis Playwright die Zeile sieht, und das kann es erst, wenn der
gedrosselte Hauptfaden frei wird. Der Profiler von Chromium zeigt, womit er
bis dahin beschäftigt ist: rund 1,2 s Entschlüsseln (`fromB64`, `decrypt`,
für alle 4071 Einsätze — Karte und Kacheln brauchen sie alle), 0,8 s die
erste Layoutberechnung der Seite (ausgelöst von Leaflet, `clientWidth`),
0,4 s `innerHTML`. Die Tabelle steht nach 2,5 s; entschlüsselt ist sie nach
rund 4 s.

**Die Abnahme des Konzepts sagte „unter 5 s bei 3983 Einsätzen (heute
42,61 s)"** — gemessen auf einem anderen Rechner als dieser, auf dem
dieselbe Seite 88 s brauchte. Auf diesem Rechner sind es 8,97 s: absolut
darüber, als Faktor (9,6 gegen verlangte 8,5) darunter, und die erste Zeile
steht nach 2,5 s. Wie das zu werten ist, steht im Konzept R4 (F-R4-58,
Q-R4-24). Weiter hinunter führen die zwei Wege, die Nr. 37 nach v1.0 hält:
Vorschneiden und Monatsvorwahl.

## Nachmessung 28.09.2026 — die Zeitraumübersicht in Stücken (R4-27)

Die Betreiberin hat Q-R4-24 mit „nachbessern" beantwortet (E-R4-63). Web
21.6.1 zeichnet die Seite in Stücken und entschlüsselt schneller
(`fromB64()` als Schleife); der Schritt misst seither ab `zeitraum.php` und
nennt eine zweite Zahl (E-R4-64, F-R4-70). Gemessen **je fünf Läufe** mit
genau diesem Schritt (ohne den Backup-Schritt), derselbe Bestand (4071
Einsätze im Jahr 2026), derselbe Rechner, Drossel 6×, warme Anlage:

| Messung | Web 21.6.0 | Web 21.6.1 | |
|---|---|---|---|
| Zeitraumübersicht, **sichtbar** (ab `zeitraum.php`, bis Playwright die erste Zeile sieht) | 5,68–6,19 s, Median **5,83 s** | 3,42–4,72 s, Median **3,71 s** | Ziel 5 s ✔ |
| dieselbe, erste Zeile im DOM | 1,68–2,07 s | 1,36–1,58 s | |
| dieselbe, **fertig** (Ende der letzten Langaufgabe) | 6,12–6,57 s, Median **6,32 s** | 6,42–7,27 s, Median **7,01 s** | **+0,7 s, bewusst** |
| längste Aufgabe des Hauptfadens (Langaufgaben der Seite, je zwei Läufe) | 2,79 / 2,97 s | 0,61 / 0,64 s | |
| `fromB64()` im Profil | 1027 ms | 123 ms | |
| der alte Schritt (Startseite + Zeitraum + zwei Abfragen), zum Vergleich | 7,58 / 7,76 s | — | |

**Was die Zahlen sagen.** Die Tabelle ist rund zwei Sekunden früher zu
sehen, und die Seite nimmt schon während des Entschlüsselns Eingaben an —
vorher stand sie in einer Aufgabe von fast drei Sekunden. **Fertig** ist sie
rund 0,7 s später: Die Bilder, die der Browser zwischendurch zeichnen darf,
kosten Zeit. Die Betreiberin hat das mit diesen Zahlen entschieden
(E-R4-64). Eine Probe ohne die Pause nach dem Entschlüsseln brachte
„fertig" nicht zurück (6,2–7,1 s) und schob „sichtbar" in drei von fünf
Läufen über 5 s.

**Die alte Messung hätte die Abnahme auch am alten Stand verfehlt.** Der
berichtigte Schritt misst Web 21.6.0 mit 5,7 bis 6,2 s: Die Messung war
nicht der Grund, sondern die Seite.

**Die Streuung ist groß** (3,4 bis 4,7 s bei gleichem Stand): „Sichtbar"
ist die Zeit, bis Playwright nachfragt und eine Antwort bekommt, und es
fragt in Abständen bis 500 ms, jedes Mal auf einen freien Moment des
gedrosselten Hauptfadens angewiesen. Deshalb steht hier der Median, und
die Spanne daneben.

## Grundlinie 11.10.2026 — vor S11 (S11-01)

**Wofür.** S11 verschließt Spur, Phasenkoordinaten und Reanimation in
Gerätehüllen, die nur der Browser öffnet; Arbeit wandert vom Server in den
Browser. E-S11-15 setzt die Grenze: keine Einzelansicht mehr als 1,5-mal
langsamer als vorher und keine über 1 s; die Massenwege werden gemessen und
benannt. Diese Zahlen sind das **Vorher**. S11-11 misst mit denselben
Schritten auf derselben Maschine das Nachher.

**Was gemessen wurde:** Web **21.14.0** (Zweig `ccr-dd33b6e8-ismfm0`, Code
gleich `dbca560`), frische Anlage, `messen.py --frisch`; Bestand **5096
Einsätze**, **2 874 650** Spurpunkte im Messstandkonto, Median **423** Punkte
je Spur. Maschine: Container mit 4 Kernen (Intel Xeon 2,10 GHz), 15 GB, PHP
8.4.19, MariaDB 10.11, Chromium aus Playwright 1.56. Zwei Läufe: Drossel
**6×** (Referenzgerät Z3, die Vorgabe des Messstands) mit allen Schritten,
dann **ungedrosselt** (`--drossel 1`) nur der Schritt `browser`.

**Zuerst, was nicht gemessen werden konnte:**

- **Ein echtes Telefon.** Wie oben: Die Drossel stellt die Rechenzeit nach,
  nicht Speicher, Grafik und Leitung. Der Vergleich vorher/nachher gilt nur
  auf dieser Maschine (E-S11-15).
- **Wartungslauf und Einmalwerkzeug** gibt es noch nicht; sie haben kein
  Vorher. Gemessen werden sie mit S11-07 und S11-10.
- **Die Kartenkacheln** — gesperrt wie in jeder Messung hier.

| Messung | Gegenstand | Drossel 6× | ungedrosselt |
|---|---|---|---|
| **Einsatz mit Spur öffnen** (Karte und Phasen stehen) | Einsatz 141, 1356 Punkte (der größte des Bestands), 8 Phasen | Median **1,28 s** (1,18–1,44 s, 5 Läufe), Halde ≤ 13 MB | Median **0,45 s** (0,41–0,48 s) |
| **Diensttag mit Ruhe-Spuren öffnen** (Spur auf der Karte) | Diensttag 30: 8 Ruhe-Segmente mit 1471 Punkten (die meisten des Bestands), 7 Einsätze, 15 Linien | Median **1,71 s** (1,69–2,03 s), Halde ≤ 25 MB | Median **0,47 s** (0,44–0,49 s) |
| **Backup erzeugen** (Schritt „Backup erstellen", seit S2) | ganzes Konto, `.edbak` | **58,54 s**, Datei 12,01 MB, Halde 52 MB, 1 PBKDF2 | **44,05 s**, 11,95 MB, Halde 68 MB |
| dazu Serverprobe `edbak_build()` | in Fenstern zu 250 / am Stück | 3,8 s, Spitze 12 MB / 13,41 s, 133,24 MB Paket, Spitze 2079 MB | — |
| **GPX über einen Zeitraum** (Export Profil B mit GPX, Passwort an) | Jahr 2026: 4071 Einsätze, **8174** GPX-Spuren | **329,6 s**, Datei 31,95 MB, **Halde 389 MB** | **74,62 s**, 31,61 MB, Halde 392 MB |

Daneben, aus demselben Lauf mit Drossel 6× und zum Bezug: Tagesansicht
1,19 s, Suche 3,55 s, Zeitraumübersicht sichtbar 3,43 s (fertig 4,93 s),
Statistik je Reiter 0,07–0,08 s, 0 Konsolenfehler. Rückgabewert beider
Läufe 0.

**Was die Zahlen sagen.**

- **Unter Drossel 6× liegt schon das Vorher über 1 s** — beide
  Einzelansichten, 1,28 s und 1,71 s. Ungedrosselt liegen sie bei 0,45 und
  0,47 s. E-S11-15 sagt nicht, unter welcher Drossel die Sekunde gilt; das
  ist die Frage Q-S11-16 im Konzept.
- **Der GPX-Export über ein Jahr braucht 389 MB Halde**, viermal das
  Gerätebudget aus Z3 (100 MB) — heute schon, ohne eine Hülle zu öffnen.
  Es ist ein Massenweg; E-S11-15 verlangt dort Messen und Benennen, keine
  Grenze. Steht als F-S11-16 im Konzept.
- **Der Einsatz ist der größte, nicht der typische.** 1356 Punkte gegen
  einen Median von 423: Die Grenze gilt für jede Einzelansicht, also misst
  der Schritt die teuerste.

**Zweiter Lauf, am selben Tag und auf derselben Maschine** — mit zwei
Messungen, die die Betreiberin dazu haben wollte: die **Zeitraumübersicht
für einen Monat** und der **GPX-Export, zerlegt in seine Abschnitte**, je
einmal mit und ohne Passwort. Seither schreibt die Probe den Prozessor in
jedes Protokoll (E-S11-28). Prozessor: Intel Xeon 2,10 GHz, 4 Kerne.

| Messung | Drossel 6× | ungedrosselt |
|---|---|---|
| Einsatz mit Spur öffnen | Median **1,33 s** (1,27–1,86) | Median **0,45 s** (0,42–0,52) |
| Diensttag mit Ruhe-Spuren öffnen | Median **2,35 s** (1,78–2,38) | Median **0,51 s** (0,45–0,74) |
| Zeitraumübersicht, **ein Monat** (03/2026, 588 Einsätze) | sichtbar Median **2,38 s**, fertig **4,04 s** | sichtbar **0,45 s**, fertig 0,53 s¹ |
| Zeitraumübersicht, ganzes Jahr (4071 Einsätze) | sichtbar 2,85 s, fertig 5,75 s | sichtbar 0,77 s, fertig 0,87 s |
| Backup erzeugen | 56,49 s | 40,01 s |
| **GPX-Export Jahr, Passwort an** | **329,6 s**, Halde 372 MB | **69,8 s**, Halde 364 MB |
| — Daten laden · Patientendaten entschlüsseln | 1,8 s · 0,4 s | 1,1 s · 0,05 s |
| — Spuren laden (328 Anfragen zu je 25) und 8174 GPX bauen | 115,8 s | 19,3 s |
| — Archiv packen mit AES (32 MB) | **208,5 s** | **47,9 s** |
| **GPX-Export Jahr, Passwort aus** | **270,2 s**, Halde 329 MB | **55,7 s**, Halde 323 MB |
| — Spuren laden und GPX bauen · Archiv packen | 85,5 s · 178,8 s | 18,0 s · 35,7 s |

¹ Ungedrosselt meldete der Monat in vier von fünf Läufen keine Aufgabe über
50 ms; „fertig" ist dort nicht messbar und liegt bei „sichtbar". Die Zahl
stammt aus dem einen Lauf, der eine meldete.

**Was die Zahlen zusätzlich sagen.**

- **Zwei Drittel des GPX-Exports sind das Packen des Archivs im Browser**,
  nicht die Spuren; die AES-Verschlüsselung des Archivs kostet bei 6× rund
  30 s davon. S11 fügt beim Laden der Spuren das Öffnen der Hüllen hinzu
  (im Browser 0,2 bis 3 ms je Stück, `tools/proben/huelle/`).
- **Die Zeitraumübersicht lädt keine Spur.** `api/range.php` liefert keine
  Spurpunkte, keine Phasenkoordinaten und kein Transportziel, nur
  `site_ele_m` und die Standorte der Dienste. Nach S11 liegt die Ortshöhe im
  `pat_blob`, der hier ohnehin für jeden Einsatz entschlüsselt wird; eine
  Gerätehülle öffnet die Seite nicht.
- **Die Streuung zwischen zwei Läufen ist groß.** Derselbe Code, dieselbe
  Maschine, derselbe Tag: Diensttag 1,71 s im ersten Lauf, 2,35 s im
  zweiten (je Median aus fünf). Ein Vergleich gegen den Faktor 2 aus
  E-S11-26 braucht deshalb mehrere Läufe vorher und nachher (F-S11-22).

**Am Prüfmittel berichtigt** (F-S11-14): Die Zähler der Browserprobe leben
je Dokument. Ein Schritt, der navigiert, rechnete „nachher − vorher" über
zwei Seiten und meldete im ersten Lauf **PBKDF2 = −1**. Seit S11-01 zählt
nach einem Seitenwechsel allein die neue Seite.

---

## Wiederholen

```
cd tools/messstand
python3 messen.py --frisch                 # alles, rund zehn Minuten
python3 messen.py --schritte server --optimieren   # nur die Serverzahlen
```

Die Rohdaten liegen unter `/tmp/messstand/messprotokoll.json`. Bedienweg,
Riegel und die Grenzen des Prüfmittels: `LIESMICH.md`.
