# Prüfdokument S7 — was **du** noch prüfen musst

Zum Schritt 4 des Rahmenplans („S7 — Backup-Begriff", R50). Das Konzept
`docs/konzepte/Umstellung-Backup.md` ist nach R62 **gelöscht** (zuletzt
unter Commit `7057e7b`); sein Prüfprotokoll beantwortete *„ist es belegt?"*
und ist in `docs/Rahmenplan.md` Abschnitt 8 zusammengefasst. **Dieses**
Dokument beantwortet die andere Frage: *„was muss ich noch tun?"* — es
bleibt liegen, bis seine Prüfliste abgehakt ist.

> **Stand:** Web **12.9.3** (Begriffsumstellung) und **12.9.4**
> (Fehlerbehebung, Backlog Nr. 89). **Keine Migration** — `update.php` ist
> nach diesem Deploy **nicht** fällig. Die drei Migrationen aus S4 und S6
> warten weiterhin auf ihre Bestätigung; daran ändert dieses Paket nichts.
>
> **Der wichtigste Punkt dieses Dokuments ist Nummer 4:** ein
> Komplett-Backup aus der Zeit **vor** diesem Deploy einspielen. Dort liegt
> das einzige echte Schadenspotenzial dieses Pakets — alles Übrige ist hier
> gemessen, einschließlich des vollständigen Umlaufs einer Konto-Sicherung
> durch den Browser (252 882 Einzelvergleiche, 0 unerklärte Abweichungen).

---

## 1. Was NICHT geprüft werden konnte — und warum

Das steht an erster Stelle und nicht in einer Fußnote.

### 1.1 ~~Der Umlauf einer Konto-Sicherung~~ — **doch gefahren**

*Hier stand, der volle Umlauf sei nicht gefahren. Das ist überholt.* Der
Kreislaufvergleich (unten, 1.2) fährt genau ihn: Referenz-Backup in ein
**frisches** Konto einspielen, dort erneut sichern, beide Dateien Feld für
Feld vergleichen — alles durch den Browser, mit echtem Passwort und echter
WebCrypto. Die Meldungen, die S7 angefasst hat, sind dabei gelesen worden:

> „**Backup eingespielt** — Import fertig: 87 Einsätze übernommen, 100
> Ruhesegmente, 16 Diensttage … 181 Spuren übernommen."
> „Herkunft: **Backup** vom 31.8.2026, 17:42 Uhr aus dem Konto
> demo@gen-em.org."

**Was weiterhin fehlt:** der Weg, den eine einzelne NutzerIn auf **ihrer**
Backup-Seite geht — Passwort selbst vergeben, Datei herunterladen, in
dasselbe Konto zurückspielen. Der Kreislauf benutzt ein frisches Konto,
weil ein Rückspielen in dasselbe Konto von der Dublettenerkennung
übersprungen würde. **Prüfliste Punkt 1.**

### 1.2 ~~Der Kreislaufvergleich (R24) ist nicht gefahren~~ — **doch gefahren**

*Auch das ist überholt.* Beide Kreisläufe sind gelaufen, nach der letzten
Änderung:

| Lauf | Einzelvergleiche | unerklärt | erwartet |
|---|---|---|---|
| `--art edbak --frisch` | **252 882** | **0** | 16 |
| `--art csv --frisch` | **8 797** | **0** | 859 |

Beide mit **0** Konsolenfehlern und **0** Befunden im Browserteil. Damit ist
die Regressionspflicht aus `CLAUDE.md` 2.2 erfüllt.

### 1.3 Bedienzustände sind nicht im Bild

Der Bilderlauf fotografiert Seiten, keine geöffneten Dialoge. Die
Bestätigungstexte der Löschdialoge („Backup löschen", „Backups ohne
Konto"), das Aktionsblatt der Kontoseite und die Freigabe an ein Zielkonto
sind im Markup umgestellt und im Quelltext gelesen, aber nicht im geöffneten
Zustand fotografiert. **Prüfliste Punkte 2 und 3.**

### 1.4 Der Wiederanlaufweg ist nur bis zum Torwächter geprüft

`wiederherstellen.php` arbeitet ausschließlich in einer **leeren**
Datenbank. Die lokale Installation hat Konten, also zeigt die Seite ihren
Sperrvermerk — den habe ich gelesen und er ist umgestellt. Der eigentliche
Weg (Datei nach `sicherungen/eingang/`, einspielen, Nachweisdatei) ist
**nicht** gegangen worden.

**Was stattdessen belegt ist**, und das ist der gefährlichste Punkt des
ganzen Pakets: Die Erkennung „stammt dieser Dump aus dieser Anwendung?"
ist **einzeln** geprüft — mit einem echten Dump in neuer Schreibweise, mit
demselben Dump auf die alte Schreibweise zurückgesetzt, und mit einer
fremden `mysqldump`-Kopfzeile. Alle drei antworten richtig.
**Prüfliste Punkt 4.**

### 1.5 SMTP ist nicht eingerichtet

Die beiden Warnmails („Backups: N % der Speichergrenze erreicht",
„Backups fällig — Gen-EM Einsatzdokumentation Notarzt") sind im Quelltext
umgestellt und gelesen, aber nicht versandt worden. **Prüfliste Punkt 5.**

### 1.6 Nur Chromium

WebKit und Gecko stehen in dieser Umgebung nicht zur Verfügung. Was nur dort
auffiele, fällt hier nicht auf. Das ist keine Neuigkeit dieses Pakets,
sondern die dokumentierte Grenze von `tools/screenshots/`.

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

Eine Prüfung ohne Zahl ist keine.

| Was | Mittel | Zahl |
|---|---|---|
| Land/Luft-Neutralität aller sichtbaren Texte | `tools/wortliste/` | **0** Treffer außerhalb der Ausnahmen, **0** ungenutzte Ausnahmen (77 Regeln, 77 gegriffen), **0** durchgerutschte Fallen — über alle vier Bereiche |
| Klassen und Werte des Stylesheets | `tools/vollstaendigkeit/` | **272** Befunde — genau der dokumentierte Stand aus Rahmenplan Fassung 19, also unverändert |
| Kontraste der Token | `tools/screenshots/kontrast.py` | **21** Paare gerechnet, **0** verfehlt |
| Seiten in acht Breiten | `tools/screenshots/` | siehe Abschnitt 2a |
| PHP-Syntax | `php -l` | **41** geänderte Dateien, **0** Fehler |
| JavaScript-Syntax | `node --check` | **9** Dateien, **0** Fehler |
| Python und JSON der Prüfmittel | `py_compile`, `json.load` | **0** Fehler |
| Falsch gebeugte Artikel, Adjektive, Relativpronomen | eigene Nachprüfung (9 Muster) | **0** echte Verdachtsstellen |
| Wortgruppen über Zeichenketten-Grenzen | eigene Prüfung | **3** gefunden, **3** behoben, danach **0** |
| Pronomenbezug über Satzgrenzen | eigene Prüfung | **78** Stellen gelesen (50 Code, 28 Doku), **17** echte Fehlbezüge behoben |
| Verwaiste weibliche Nominalisierungen | eigene Prüfung | **7** Stellen gelesen, **4** behoben |
| Überschriften in Versalien | eigene Prüfung | **30** Stellen umgestellt, danach **0** |
| Fehlerklasse „Vorgabewert aus lazy geladener Datei" | eigene Prüfung über alle Signaturen | **1** Stelle in `server/`, behoben |
| Backlog-Nummern | `grep … \| uniq -d` | **0** doppelt |
| Umlauf einer Konto-Sicherung | `kreislauf.py --art edbak --frisch` | **252 882** Einzelvergleiche, **0** unerklärt |
| Umlauf des CSV-Exports | `kreislauf.py --art csv --frisch` | **8 797** Einzelvergleiche, **0** unerklärt |
| Verbliebene Fundstellen, einzeln zugeordnet | eigene Zählung | siehe Abschnitt 3 |

### 2a. Bilderlauf

`node tools/screenshots/aufnehmen.mjs`, 38 Seiten in acht Breiten
(360 · 390 · 420 · 768 · 1024 · 1280 · 1440 · 1920):

| | |
|---|---|
| Einzelbilder | **304** |
| Kontaktbögen | **38** |
| waagerechter Überlauf | **0** |
| Konsolenfehler | **0** |
| Knöpfe ≠ 44 px | **0** |

**Die Gegenprobe aus der LIESMICH ist gefahren** — und sie ist nicht glatt
aufgegangen, deshalb steht sie hier: 304 Bilder, **301 verschiedene
Prüfsummen**. Die drei Doppel sind `10-tagesuebersicht` gegen
`11-tagesuebersicht-schublade` bei 1024, 1280 und 1440 px. Nachgesehen: Ab
`@media (min-width:1024px)` ist die Schublade eine feste Seitenleiste
(`.nur-schublade{display:none}`, `style.css` Zeile 1663) — der
Bedienschritt „Schublade öffnen" hat dort **keine Wirkung**, und dass beide
Bilder gleich sind, ist richtig. Bei 360, 390, 420 und 768 px unterscheiden
sie sich, bei 1920 px ebenfalls (Kartenausschnitt). Kein einziges Bild zeigt
eine **andere** Seite als die gemeinte — das ist die Frage, die diese
Gegenprobe stellt (F-P3-AQ).

### 2b. Was eine NutzerIn wirklich sieht

Die Zählungen in Abschnitt 3 messen den **Quelltext**. Diese hier misst den
**sichtbaren Text**: Jede Seite der Bilderlauf-Liste wird im Browser
aufgerufen und ihr `innerText` durchsucht — mit den Rollen abgemeldet, Demo
und Admin.

| | |
|---|---|
| Seiten im Browser gelesen | **29** (9 mit Laufzeit-Platzhalter übersprungen) |
| „Backup" im sichtbaren Text | **83** |
| „Sicherung" im sichtbaren Text | **2** |

**Und beide sind genau die zwei dokumentierten Grenzen:**

1. `admin_sicherungen.php` zeigt den **Ablagepfad**
   `…/server/sicherungen` — er steht in der Ausnahmeliste des Deploys und
   bleibt (Grenze 3.1).
2. `update.php` zeigt die **Migrationskennung**
   `2026_09_01_sicherungsziele` — ein gespeicherter Name (R5).

Kein dritter Fall. Die neun übersprungenen Seiten tragen Platzhalter, die
das Bilderlauf-Werkzeug zur Laufzeit auflöst; die für dieses Paket
wichtigste davon — die **Kontoseite** — ist einzeln geprüft (Karte
„Backups", „Für dieses Konto gibt es noch kein Backup.", Meldung „Backup
erzeugt.", Karte danach „Backups 1 · aktuell", 0 Konsolenfehler).

---

## 3. Die Gegenprobe: was bleibt, und warum

`server/` ohne `vendor/`: **642 → 167**. Jeder verbliebene Treffer ist
zugeordnet:

| Anzahl | Klasse | Grund |
|---|---|---|
| 51 | Versionsgeschichte in `version.php` | Beleg, nicht Oberfläche (E-S7-2) |
| 26 | Ablagepfad `sicherungen/` | Ausnahmeliste des Deploys (Grenze 3.1) |
| 26 | Dateinamen | R5, R56 |
| 25 | Funktions- und Feldnamen | R5 |
| 13 | Symbolname `sicherung` | R56 |
| 13 | falsche Freunde (Absicherung, Zusicherung) | anderes Wort |
| 9 | Formatkennungen | gespeicherte Namen bleiben (R5) |
| 4 | Menüschlüssel und Dateibasisnamen | R5 |

Normative Dokumentation **272 → 48** (Handbuch **78 → 0**), offene
Backlog-Punkte **45 → 7**, `tools/` **188 → 40**, Historie **734 → 734**.

---

## 4. Die Prüfliste

Je Punkt: Bedienweg, erwartetes Ergebnis, und woran ein Scheitern zu
erkennen ist.

### ☐ 1. Ein Backup erstellen und in DASSELBE Konto einspielen

**Weg:** Anmelden · *Einstellungen → Backup* · Passwort vergeben ·
**Backup erstellen** · Datei herunterladen · auf derselben Seite unter
*Backup einspielen* dieselbe Datei und dasselbe Passwort · **Backup
einspielen**.

**Erwartet:** Die Karten heißen „Backup erstellen" und „Backup einspielen",
die Knöpfe **genauso** — das war der Anlass der ganzen Umstellung. Die
Feldbeschriftungen lauten „Passwort für das Backup" und „Passwort des
Backups". Nach dem Öffnen erscheint eine Zeile „Backup vom … aus dem Konto
…". Das Einspielen meldet, was ergänzt wurde.

**Scheitern erkennt man daran:** dass irgendwo noch „Sicherung" steht, dass
ein Artikel nicht passt („die Backup"), oder dass das Einspielen mit einer
Meldung abbricht, die vorher nicht kam. Die Meldungstexte sind angefasst
worden — eine, die nicht mehr passt, ist ein Fehler dieses Pakets.

### ☐ 2. Die Dialoge des Adminbereichs öffnen

**Weg:** Als Admin *Einstellungen → Backups* · ein Paket unter „Backups ohne
Konto" (falls vorhanden) · **Löschen** · den Dialog **ansehen, nicht
bestätigen**. Dann auf einer Kontoseite: **Löschen** an einem Paket und
**Für Zielkonto freigeben**.

**Erwartet:** Überschriften „Backup löschen", „Backup ohne Konto
einspielen"; der Haken lautet „Ich entferne ein Backup, **das** sich keinem
Konto mehr zuordnen lässt".

**Scheitern:** „eine Sicherung, die …" oder „ein Backup, **die** …" — der
Relativsatz ist die Stelle, an der eine Umstellung am ehesten hängen bleibt.

### ☐ 3. Die Kontenliste und ihre Kacheln

**Weg:** *Administration → NutzerInnen*.

**Erwartet:** Spaltenkopf **Backup**, Kacheln **Backup überfällig** und
**Nie gesichert** (das Verb bleibt, R56), Sammelknopf **Auswahl sichern**.

**Scheitern:** eine Kachel, die noch „Sicherung überfällig" heißt, oder ein
Filterknopf, der in zwei Zeilen bricht, weil der Text länger geworden ist.
Der Bilderlauf misst Überlauf, aber nicht den Umbruch **innerhalb** einer
Leiste (das ist Backlog Nr. 73).

### ☐ 4. Ein Komplett-Backup aus der Zeit **vor** diesem Deploy einspielen

**Der Punkt mit dem größten Schadenspotenzial.** Wenn auf dem Server oder
einem Backup-Ziel noch ein `.edk` oder `.sql.gz` von vor heute liegt: in
eine **leere** Testdatenbank über `wiederherstellen.php` einspielen.

**Erwartet:** Die Seite erkennt ihn als **eigenen** Dump und verlangt die
Endmarke.

**Scheitern erkennt man daran**, dass die Seite ihn als *fremden* Dump
behandelt (dann fehlt der Satz zur Endmarke) — das hieße, die
Vollständigkeitsprüfung ist für alte Dateien ausgefallen, und ein
abgebrochener Stand ginge klaglos durch. Genau dagegen kennt der Leser jetzt
**beide** Schreibweisen der Kopfzeile; hier wird nachgesehen, ob das am
echten Bestand trägt.

### ☐ 5. Die beiden Warnmails auslösen

**Weg:** Auf einer Installation mit eingerichtetem SMTP die Speichergrenze
unter *Backups* so weit senken, dass eine Warnschwelle überschritten ist,
und den Wartungsjob laufen lassen. Dazu die Erinnerungsfrist auf 1 Tag
setzen.

**Erwartet:** Betreff „**Backups**: N % der Speichergrenze erreicht" und
„**Backups** fällig — Gen-EM Einsatzdokumentation Notarzt"; im Text
„letztes Backup vor N Tagen" und der Hinweis „Abschalten lässt sie sich
unter Einstellungen → **Backups**".

**Scheitern:** ein Betreff mit „Sicherungen" — dann ist eine Zeichenkette
übersehen worden, die kein Werkzeug sieht, weil sie nur im Versand entsteht.

### ☑ 6. Der Kreislaufvergleich (R24) — **erledigt**

Gefahren am 02.09.2026 nach der letzten Änderung: `--art edbak --frisch`
**252 882** Einzelvergleiche, **0** unerklärt (16 erwartet); `--art csv
--frisch` **8 797** Einzelvergleiche, **0** unerklärt (859 erwartet). Kein
Handlungsbedarf; der Punkt steht hier, damit die Zahl auffindbar bleibt.

### ☐ 7. Das geplante Komplett-Backup im Betrieb

**Weg:** Auf der Produktivinstallation *Einstellungen → Komplett-Backup* ·
Plan auf „täglich" · einen Tag warten (oder den Cron einmal von Hand
auslösen).

**Erwartet:** Ein Stand entsteht **ohne** Zutun. Auf der Wartungsseite steht
beim Job „Komplett-Backup der Installation" ein Zeitpunkt und **keine**
Fehlerzeile.

**Scheitern:** die Fehlerzeile ist noch da. Dann trägt der Fix aus Backlog
Nr. 89 nicht — dieser Job hat von Web 12.2.0 bis 12.9.2 nie gelaufen, und
das ist der erste Betriebsnachweis, dass er es jetzt tut.

---

## 5. Grenzen der benutzten Prüfmittel

- **Die Wortliste misst diese Umstellung nicht.** Sie fragt nach Land- und
  Luft-Neutralität, nicht nach „Backup" gegen „Sicherung". Ihre Null
  beantwortet die andere Frage: ob beim Umschreiben von rund 900 Textstellen
  ein Luftwort neu hineingeraten ist. Der Vollständigkeitsbeleg ist die
  Gegenprobe in Abschnitt 3, nicht die Wortliste.
- **Die eigenen Prüfungen dieses Pakets sind Muster, keine Grammatik.** Sie
  finden, was ihnen beigebracht wurde — und jede der vier Fehlerklassen ist
  erst aufgefallen, **nachdem** sie einmal durchgerutscht war. Wer daraus
  ableitet, dass es keine fünfte gibt, irrt.
- **Der Bilderlauf sagt nicht, ob ein Bild richtig ist**, nur wie es
  aussieht. Und er zeigt keine Bedienzustände (Abschnitt 1.3).
- **Der Stilvergleich ruht** (P3-Regel, `CLAUDE.md` 6). An
  `server/assets/style.css` sind in diesem Paket nur zwei Kommentarzeilen
  geändert worden; Regeln, Reihenfolge und Werte sind unberührt.
