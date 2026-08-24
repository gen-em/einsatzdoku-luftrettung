# Prüfdokument P2 — was **du** noch prüfen musst

Das Prüfprotokoll im Konzept (`docs/Konzept-P2-Terminologie.md`, Abschnitt 7
und 11) beantwortet „ist es belegt?". Dieses Dokument beantwortet die andere
Frage: **was steht noch aus, und auf welchem Weg?**

Ausgeliefert wird **Web 8.0.1**. Eine Migration gibt es **nicht** —
`update.php` muss nach diesem Deploy **nicht** aufgerufen werden. Die Uhr-App
ist unverändert und wird **nicht** ausgeliefert.

---

## 1. Was NICHT geprüft werden konnte — und warum

Das steht hier oben und nicht in einer Fußnote.

### 1.1 Alles Gemessene stammt aus einer lokalen Installation

Kein Zugang zum Produktivserver. Lokal: Ubuntu 24.04, **PHP 8.4.19**,
**MariaDB 10.11.14**, Schema regulär über `install.php`, Browserschritte in
**Chromium 1194** (Playwright, headless). Der Referenzbestand wurde über die
regulären Wege eingespielt (`einspielen.py`, 366 Anfragen) und der
CSV-Anteil über die Oberfläche (`browser/csv_import.mjs`, 4 Einsätze).

Was das offenlässt:

- **Der FTPS-Deploy.** P2 ändert unter `server/` nur Texte und `version.php`;
  keine neue Datei, keine gelöschte. Dass der Deploy sie überträgt, ist
  wahrscheinlich, aber nicht gemessen.
- **Die PHP-Fassung des Hosters.** Lokal 8.4.
- **Wie GitHub das README darstellt.** Das README ist vollständig neu gefasst
  und hat eine Tabelle mit siebzehn Zeilen. Ob alle Links auf GitHub laufen,
  zeigt sich erst nach dem Push (Prüfliste 4.6).

### 1.2 Die Uhr ist nicht geprüft — es gibt keine

Der Kopplungstext im Web und Handbuch 12 sind **gerätefrei** neu formuliert;
der Garmin-Weg steht als Zusatz daneben und verweist für die Tastenwege auf
Handbuch 2.0 und 2.2. **Ob die Uhr-App die Sync-Seite und den Menüpunkt
tatsächlich so anbietet, wie der neue Text es beschreibt, ist am Gerät nicht
nachgesehen worden.** Der Text folgt dem, was die Uhr-Quelltexte und
Handbuch 2 sagen — mehr nicht. Das ist der wichtigste Punkt der Prüfliste
(4.1), und er braucht eine Uhr in der Hand.

### 1.3 Die Beispieldomain ist eine Behauptung

`nadoku.beispieldomain.de` steht jetzt an drei Stellen als Beispiel. Sie ist
als Platzhalter gedacht und wurde **nicht** darauf geprüft, ob sie
registriert ist oder jemandem gehört. Wer das für heikel hält, tauscht sie
gegen `example.com`-Form.

### 1.4 Der Demo-Reset ließ sich erst nach einem Eingriff prüfen

`browser/demo_pruefen.mjs` brach zunächst ab: Das Anlegen des Demo-Kontos
scheiterte mit `SQLSTATE[23000] … Duplicate entry 'manual-2' for key
'device_id'`. Die Spalte `devices.device_id` ist **global** eindeutig
(`schema.sql` 39), und die Demo-Fixture bringt die Gerätekennungen des
Referenzbestands mit — auf einer Installation, die den Referenzbestand
ohnehin führt, kollidieren sie. Das ist kein Befund von P2 (F-P2-S im
Konzept), aber es hat den Ablauf verändert: Die Zeile wurde für diesen Lauf
**per SQL entfernt** — die einzige Stelle dieser Umsetzung, an der die
Datenbank direkt angefasst wurde. Danach lief die Abnahme durch. Auf einer
Installation ohne Referenzbestand tritt der Fall nicht auf.

### 1.5 Kein SMTP, kein echtes Gerät

Unverändert gegenüber S1: E-Mail-Versand ist lokal nicht eingerichtet; der
Bestand ist über `ingest.php` eingespielt, nicht von einer echten Uhr.

### 1.6 Was das Wortlisten-Werkzeug **nicht** kann

Es findet Wörter, keine Perspektive. Drei Stellen im Handbuch waren nur von
der Luftrettung her gedacht, ohne ein Sperrwort zu enthalten — gefunden hat
sie eine Durchsicht über 1837 Zeilen, nicht das Werkzeug. **Es ist damit
möglich, dass weitere solche Stellen stehen geblieben sind**, in den nicht
gelesenen Teilen der Dokumentation und in der Oberfläche. Das Werkzeug sagt
nichts darüber; es sagt nur, dass kein Wort der Sperrliste mehr an einer
Stelle steht, an der es nicht hingehört.

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Was | Mittel | Zahl |
|---|---|---|
| **Wortliste gegen den Stand VOR der Phase** (`e29d593`) | `tools/wortliste/wortliste.py` | **286 Treffer**, 233 durch Ausnahmen erklärt, **53 außerhalb in 44 Zeilen** (= 30 zusammenhängende Stellen), 0 ungenutzte Ausnahmen, **Rückgabewert 1** |
| **Wortliste gegen den Endstand** | dasselbe | **0 Treffer außerhalb, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen**, Rückgabewert 0. 23 Muster, 9 Fallen, **44 Ausnahmeregeln, alle 44 gegriffen** |
| Teilstring-Fallen | dasselbe | **23 Vorkommen** von „dorthin", „earth", „maschinell", „maschinenlesbar", „Drop-down", „Countdown" — **keines** als Treffer gezählt |
| Selbstprobe des Kommentar-Zerlegers | `wortliste.py --probe` | **16/16** bestanden; jede Probe prüft zusätzlich, dass Zeilenzahl und Länge unverändert bleiben |
| **Kreislauf Sicherung** (R24) | `vergleich/kreislauf.py --art edbak --frisch` | **286 739 Einzelvergleiche, 0 unerklärt**, 16 erwartet — Sollstand nach S1 gehalten |
| **Kreislauf CSV** (R24) | `--art csv --frisch` | **8 797 Einzelvergleiche, 0 unerklärt**, 859 erwartet |
| Probe aufs Exempel, Sicherung | `vergleichen.py --testabweichung`, gleiche Datei beidseitig, ohne `--ausnahmen` | **12/12** bestanden |
| Probe aufs Exempel, CSV | dieselbe | **10/10** bestanden |
| Angriffswerte-Regression (R20) | `browser/angriffswerte.mjs` | **42 Einzelprüfungen, 0 Befunde, 0 Konsolenfehler** |
| Demo-Abnahme | `browser/demo_pruefen.mjs` | **24 Einzelprüfungen, 0 Befunde, 0 Konsolenfehler**; Papierkorb 5/1/5 vor und nach dem Reset |
| Warntext des Excel-Rückimports: Code gegen Dokumentation | zeichenweiser Vergleich der Zeichenkette aus `import_profiles.js` mit dem Blockzitat in `Export-Format.md` 5.2 | **identisch, 453 Zeichen** |
| Excel-Spaltentabelle gegen eine **erzeugte** Datei | Kopfzeile aus zwei Exporten des Referenzkontos gelesen | **31 Spalten** mit personenbezogenen Angaben, **15** ohne — Reihenfolge und Beschriftungen identisch zur berichtigten Tabelle |
| Abschnittsverweise | eigenes Skript über README, Handbuch, Export-Format, Technik, Backup-Format, JSON-Vertrag | **85 Verweise geprüft, 83 aufgelöst**; 2 Artefakte des Skripts; **1 echter Fehler** (Handbuch „Abschnitt 8.4" → 9.4) |
| Schwachwortliste vorher/nachher | beide Fassungen geladen, dieselben Passwörter durchgereicht | **768 Proben, 0 Abweichungen** |
| Syntax | `php -l` (4 Dateien), `new Function()` (2 Dateien) | fehlerfrei |
| `style.css` unverändert | `git diff e29d593 -- server/assets/style.css` | **0 Zeilen** — deshalb kein Stilvergleich |

**Zum Vergleich, was sich geändert hat:** Vor der Phase fiel das
Wortlisten-Werkzeug mit 53 Treffern durch, danach steht es auf 0. Kreisläufe,
Angriffswerte und Demo-Abnahme stehen **auf demselben Stand wie nach S1** —
P2 hat keinen Schreibweg berührt.

---

## 3. Was im Browser geprüft wurde

Alles über Playwright/Chromium gegen die lokale Installation, jeder Schritt
über die reguläre Oberfläche.

| Was | Ergebnis |
|---|---|
| Einstellungen → Geräte, ohne erzeugten Code | Gerätefreier Ablauf sichtbar („Sync-Seite → Gerät koppeln → Code eintippen"); Garmin-Zusatz als eigener Absatz; Verweis auf Handbuch 2.0; `luftrettung.net` verschwunden; Platzhalter „Bezeichnung, z. B. Dienstuhr" |
| dieselbe Seite mit erzeugtem Kopplungscode | Code und Gültigkeitszeit angezeigt, Ablauftext unverändert sichtbar |
| dieselbe Seite mit manuell angelegtem Gerät | „Beide Werte in den Einstellungen der Uhr-App eintragen (… `nadoku.beispieldomain.de`). Bei Garmin stehen diese Einstellungen in Garmin Connect."; „Connect-IQ-Einstellungen" verschwunden |
| Einstellungen → Rettungsmittel | Platzhalter „z. B. Christoph 17 oder NEF Kempten 1"; „(RTW, NEF, RTH …)"; „weitere Hubschrauber" verschwunden |
| Administration → Rettungsmittel (systemweit) | **derselbe** Platzhalter wie auf der NutzerInnenseite |
| Import / Export, Haken „Personenbezogene Angaben" aus, Profil CSV | GPX-Hinweis erscheint und lautet „… entfallen die GPX-Tracks — **ein Track** endet am Einsatzort"; „Flugspur" verschwunden |
| **Excel-Rückimport vor und nach der Änderung, dieselbe Datei** | Bilanz beide Male **14 Diensttage, 78 Einsätze, 20 Hinweise, 0 Fehler, 78 Dubletten, 7 mit abweichender Besatzung**; erkanntes Profil beide Male `export_excel_v1`; Warntext vorher mit „Abflug / Landung Krankenhaus / Flugkilometer", nachher mit „Ausrücken / Ankunft Klinik / Kilometer" |
| Gesamt | **21 Einzelprüfungen über 6 Seitenaufrufe, 0 Befunde, 0 Konsolenfehler**, 6 Bildschirmfotos |

Der Rückimport wurde bewusst **nicht** übernommen: Die Bilanz steht vor dem
Commit fest, und ein Commit hätte den Referenzbestand verändert, gegen den
die Kreisläufe laufen.

---

## 4. Prüfliste — was du selbst tun musst

Abhaken. Je Punkt: **Weg**, **Erwartung**, **woran ein Scheitern zu erkennen
ist**.

### 4.1 Die Kopplung, einmal mit der Uhr in der Hand

Der wichtigste Punkt. Der Kopplungstext ist gerätefrei umgeschrieben, und
niemand hat ihn gegen ein Gerät gehalten.

- [ ] **Weg:** Handbuch, Abschnitt 12 von oben nach unten durchgehen, mit
      einer Uhr, die noch nicht gekoppelt ist. Danach dasselbe im Web:
      **⚙ Einstellungen → Geräte** lesen und dem Text folgen.
- [ ] **Erwartung:** Jeder der fünf Schritte lässt sich ausführen, ohne dass
      man etwas dazuwissen muss. Die Bezeichnungen im Text („Sync-Seite",
      „Gerät koppeln") stimmen mit dem überein, was die Uhr anzeigt.
- [ ] **Scheitern erkennbar an:** Ein Menüpunkt heißt auf der Uhr anders als
      im Text, oder der Zusatz „Bei Garmin: die Sync-Seite und der Tastenweg
      zum Koppeln stehen in den Abschnitten 2.0 und 2.2" führt ins Leere,
      weil dort etwas anderes steht als das, was die Uhr tut. **Besonders auf
      der Venu 3s prüfen** — für sie war der alte Text falsch, und sie ist
      der Grund für die Umformulierung.

### 4.2 Der Warntext des Excel-Rückimports

- [ ] **Weg:** **⚙ Einstellungen → Import / Export**. Erst einen Export im
      Format **Excel (Standard)** erzeugen, dann dieselbe Datei oben unter
      „1. Datei wählen" wieder auswählen. **Nicht importieren** — nur lesen.
- [ ] **Erwartung:** Der gelbe Kasten nennt die Phasen **Ausrücken, Ankunft
      Einsatzort, Ankunft PatientIn, Transportbeginn, Ankunft Klinik und
      Übergabezeit** und den „Track (und damit auch die **Kilometer**)".
- [ ] **Scheitern erkennbar an:** Dort steht „Abflug", „Landung Krankenhaus"
      oder „Flugkilometer". Dann ist der Deploy nicht angekommen oder der
      Browser hält die alte `import_profiles.js` — Seite mit Strg+F5 neu
      laden und die Fußzeile auf **8.0.1** prüfen.

### 4.3 Die Excel-Spaltentabelle stimmt wieder

- [ ] **Weg:** Denselben Export aus 4.2 in einer Tabellenkalkulation öffnen,
      Zeile 3 lesen, und daneben `docs/Export-Format.md`, Abschnitt 2.
- [ ] **Erwartung:** **31 Spalten**, Spalte 16 heißt **„HEMS-TC"**, Spalte 30
      **„Kilometer"**, Spalten 14 bis 20 sind die sieben Rollen in der
      Reihenfolge Pilot 1, Pilot 2, HEMS-TC, Flugretter, Fahrer, Praktikant,
      Sonstige Besatzung. Ein zweiter Export **ohne** personenbezogene
      Angaben hat **15 Spalten**.
- [ ] **Scheitern erkennbar an:** Eine andere Spaltenzahl. Dann hat sich der
      Export seit dieser Messung geändert und die Tabelle stimmt wieder
      nicht — der Fehler, den P2 gerade behoben hat, ist genau so entstanden.

### 4.4 Der GPX-Hinweis

- [ ] **Weg:** **Import / Export**, Format auf **CSV (Standard)**, den Haken
      **„Personenbezogene Angaben einschließen"** wegnehmen.
- [ ] **Erwartung:** Anstelle der GPX-Wahl steht „Ohne personenbezogene
      Angaben entfallen die GPX-Tracks — **ein Track** endet am Einsatzort."
- [ ] **Scheitern erkennbar an:** Dort steht „eine Flugspur", oder es steht
      gar nichts (dann ist der Haken noch gesetzt oder das Profil ist Excel).

### 4.5 Die Platzhalter auf beiden Stammdatenseiten

- [ ] **Weg:** **⚙ Einstellungen → Rettungsmittel** und, als Admin,
      **Administration → Rettungsmittel systemweit**. Auf beiden Seiten das
      leere Namensfeld ansehen.
- [ ] **Erwartung:** Beide zeigen **„z. B. Christoph 17 oder NEF Kempten 1"**.
- [ ] **Scheitern erkennbar an:** Die Seiten zeigen Verschiedenes — genau der
      Zustand vor P2.

### 4.6 Das README auf GitHub

- [ ] **Weg:** Nach dem Push die Startseite des Repositoriums öffnen.
- [ ] **Erwartung:** Titel **„Einsatzdoku"**; der erste Absatz nennt
      **luftgebunden wie bodengebunden (RTH, NEF, NAW)** und die Uhr als
      „derzeit für Garmin-Uhren"; die Dokumentationstabelle hat **17 Zeilen**
      und **jeder** Link öffnet die genannte Datei.
- [ ] **Scheitern erkennbar an:** Ein Link führt auf eine 404-Seite. Am
      wahrscheinlichsten bei den vier Konzept- und Prüfdokumenten — sie sind
      neu in der Tabelle.

### 4.7 Die Fußzeile zeigt 8.0.1

- [ ] **Weg:** Irgendeine angemeldete Seite unten lesen.
- [ ] **Erwartung:** **8.0.1**.
- [ ] **Scheitern erkennbar an:** Dort steht 8.0.0. Dann ist der Deploy nicht
      durchgelaufen — und alle Punkte oben prüfen den alten Stand.

### 4.8 Das Demo-Passwort funktioniert noch

Kein Selbstzweck: Die Schwachwortliste hat in dieser Phase Wörter bekommen,
und ein sechstes hätte genau dieses Passwort unbrauchbar gemacht (F-P2-R).

- [ ] **Weg:** Mit `demo@gen-em.org` / `nadokudemo0815` anmelden, dann
      **Einstellungen → Backup**, Passwort **`nadokudemo0815`** zweimal
      eintragen und *Backup erstellen* klicken.
- [ ] **Erwartung:** Die Datei entsteht.
- [ ] **Scheitern erkennbar an:** „Dieses Passwort ist zu geläufig …" statt
      einer Datei. Dann ist doch ein Wort in der Liste, das im Demo-Passwort
      steckt.

### 4.9 Zwei Dinge, die P2 gefunden und **nicht** behoben hat

Beides braucht deine Entscheidung, beides ist eine Zeile Arbeit:

- [ ] **`.gitignore` und der Wiederherstellungsschlüssel** (F-P2-L). Die
      Anleitung `tools/referenzdatensatz/einspielen/LIESMICH.md` 39 sagt
      `node passwort_setzen.mjs … rc.json`; ignoriert ist aber nur
      `*_rc.json`. Wer der Anleitung folgt und `git add -A` sagt, hat den
      Wiederherstellungsschlüssel eines Kontos im Repositorium. **Vor dem
      nächsten Einspiellauf** entweder `*_rc.json` zu `*rc.json` erweitern
      oder im LIESMICH den Dateinamen ändern.
- [ ] **Der Rollencode `tc` in `docs/Backup-Format.md`** (F-P2-J). Das
      JSON-Schema der Sicherung führt `"roles": ["p1", "p2", "tc", "other"]`
      und `"crew": { …, "tc": … }`. Die Anwendung kennt `hems` und `fr`;
      `tc` kommt in keinem Quelltext vor. Wer ein Werkzeug gegen die
      Beschreibung baut, sucht einen Schlüssel, den keine Datei führt.

---

## 5. Grenzen der benutzten Prüfmittel

**Das Wortlisten-Werkzeug** prüft Wörter, keine Aussagen und keine
Perspektive (1.6). Sein Kommentar-Zerleger ist eine Heuristik, keine
Grammatik: Er unterscheidet Division und regulären Ausdruck am zuletzt
gesehenen Zeichen und behandelt `${…}` in Template-Literalen als Teil der
Zeichenkette. Er irrt im Zweifel **zugunsten des Textes** — lieber ein
Treffer zu viel, der eine Ausnahme kostet, als einer zu wenig, der die
Aussage kostet. Belegt durch sechzehn Proben mit Sollergebnis.

**Die Ausnahmeliste ist so gut wie ihre Begründungen.** 44 Regeln erklären
zusammen 286 Treffer. Eine zu weit gefasste Regel verdeckt echte Stellen —
in dieser Phase ist das einmal passiert und aufgefallen: Das Muster für
historische Nennungen fragte auch nach „seit Web …" und gab damit den Satz
„Eine Flugspur endet am Einsatzort (seit Web 5.8.0, A9)" frei. Es fragt jetzt
nur noch nach „bis Web …". Wer eine Regel hinzufügt, prüft, was sie sonst
noch erklärt.

**Die Kreisläufe** messen den Umlauf von Daten, nicht die Oberfläche. Dass
sie unverändert auf null stehen, belegt, dass P2 keinen Schreibweg berührt
hat — mehr nicht.

**Die Sichtprüfung im Browser** ist eine Stichprobe über sechs Seiten. Sie
misst statisches Markup und einen einzelnen Bedienzustand je Seite, keine
Übergänge, keine Fehlerfälle, keine Darstellung auf schmalen Bildschirmen.

**Die Verweisprüfung** löst Abschnittsnummern auf. Sie prüft nicht, ob der
verwiesene Abschnitt inhaltlich passt — nur, dass es ihn gibt.
