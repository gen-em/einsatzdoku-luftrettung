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

## Wiederholen

```
cd tools/messstand
python3 messen.py --frisch                 # alles, rund zehn Minuten
python3 messen.py --schritte server --optimieren   # nur die Serverzahlen
```

Die Rohdaten liegen unter `/tmp/messstand/messprotokoll.json`. Bedienweg,
Riegel und die Grenzen des Prüfmittels: `LIESMICH.md`.
