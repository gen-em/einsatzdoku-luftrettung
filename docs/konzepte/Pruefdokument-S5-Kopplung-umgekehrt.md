# Prüfdokument S5 — was **du** noch prüfen musst

Zur Phase S5 („Kopplung umgekehrt“). Das Prüfprotokoll im Konzept
(`Konzept-S5-Kopplung-umgekehrt.md`, Abschnitte 9 und 10) beantwortet *„ist es
belegt?“*; dieses Dokument beantwortet *„was muss ich noch tun?“*. Es wird je
Paket ergänzt und bleibt nach dem Abschluss der Phase stehen, bis seine
Prüfliste abgehakt ist (K9, R62).

> **Stand: Paket A (Server, Web 13.0.0), die Korrektur 13.0.1 und Paket B
> (Weboberfläche, Web 13.1.0) sind gebaut** — auf
> `claude/s7-umsetzung-vorbereiten-s8kax0`. C (Uhr), D (Doku) und E
> (Android-Zusatz) folgen; C und E laufen in eigenen Instanzen.
>
> **Eine Migration ist zwingend** (`2026_09_03_kopplungssitzungen`): Nach dem
> Deploy muss eine Administratorin **`update.php`** aufrufen. Sie legt
> `pair_sessions` an und **löscht `pair_codes`**. Ohne sie antwortet jedes
> `start` mit `500`.
>
> **Die Bestandsuhr koppelt nach dem Deploy einmal neu** (E-S5-42): Ihr
> Schlüssel liegt als bcrypt-Hash, der Server vergleicht ab 13.0.0 gegen
> SHA-256. Vorher den Sync vollständig laufen lassen — sonst gehen gepufferte
> Ereignisse mit dem Trennen verloren.
>
> **Der Zwischenzustand ist vorbei:** Mit Paket B ist die Geräteseite auf dem
> neuen Weg; der Knopf „Kopplungscode erzeugen“ gibt es nicht mehr. Auf `main`
> kommt die Phase trotzdem erst am Ende, nach Paket C und D — eine Uhr, die
> den alten Weg spricht, kann sich nach dem Deploy nicht mehr koppeln.

---

## 1. Was NICHT geprüft werden konnte — und warum

Das steht an erster Stelle und nicht in einer Fußnote.

### 1.1 Der Text der Kopplungsmail

Es gibt keinen Mailserver im Prüfstand. `smtp_send()` scheitert lokal an
`ssl://127.0.0.1` und schreibt eine Protokollzeile — **belegt ist nur, dass
der Versandweg nach der Antwort betreten wird** (Kopplungsprobe Fall 27,
Protokollzeile `SMTP connect`). Ob die Mail Art, Modell, Kennung und den neuen
Satz „Das Gerät hat den Code gezeigt, du hast ihn im Web eingegeben und am
Gerät mit Ja bestätigt“ trägt, ist Sichtprüfung — Prüfliste 3.

### 1.1a Uhr: der Verbindungsabriss ist nur zur Hälfte belegt

**Der Simulator kann keine tote Verbindung nachstellen, die aussieht wie eine
tote Verbindung.** Tötet man den lokalen Server, sieht die App **HTTP 404** —
nicht den negativen Code, den ein Gerät ohne Telefon in Reichweite liefert.
Dieselbe Eigenschaft hat `tools/netzprobe/` am 03.09.2026 schon für den
CA-Fehler gemessen („https, selbstsigniert → die App sieht 404").

**Belegt ist damit:** Die Uhr übersteht einen Server, der nicht mehr antwortet,
wirft die Sitzung nicht weg und meldet ihn mit seiner Zahl statt mit einer
erfundenen Ursache.

**Nicht belegt ist:** der Zweig `code < 0` — „Keine Verbindung (n)" unter dem
stehenbleibenden Code, Abfrage läuft weiter bis zur Frist. Er ist gelesen, aber
nicht gelaufen. **Das braucht die Uhr in der Hand** (Prüfliste 7).

### 1.1b Uhr: zwei von zwanzig Vertretern zeigen die `PairView` nicht

`bildreihe` lief über alle **20** Vertreterklassen, **0 Abstürze**, 18 mit
sichtbarer `PairView` und echtem Code. Die beiden übrigen, jeweils mit Grund:

- **`fenix8solar47mm`** — die App läuft und zeichnet (der Startbildschirm ist
  fotografiert), aber die Tastenautomatik brachte sie nicht auf die Sync-Seite.
  Grenze des Prüfmittels, nicht der App.
- **`fenix9prosolar51mm`** — der Simulator öffnet ein Fenster (446 × 700) und
  zeichnet **das Gerät nicht**: nur Menüleiste und leere Fläche, 0 Meldungen in
  der Konsole. Stufe I hat dasselbe Gerät fehlerfrei übersetzt.

Beide sind **keine** Aussage über die Oberfläche auf diesen Geräten. Wer sie
haben will, braucht einen Lauf am Arbeitsplatz oder das Gerät.

### 1.1c Uhr: die Tastensperre ist im Simulator nicht nachstellbar

`docs/Geraete-Eingabe.md` 6 sagt es ausdrücklich: Der Simulator bildet
Systemgesten außerhalb der App — Steuerungsmenüs, **Tastensperren** — nicht ab.
Die Behebung von B-S5-12 ist deshalb **übersetzt und gelesen, nicht erlebt**.
Belegt ist nur, dass sie die 99 Zielgeräte ohne Warnung übersetzt und die
übrigen Bedienwege im Simulator unverändert funktionieren. **Prüfliste 8.**

### 1.2 Die Antwortzeit auf dem Produktivserver

Die Gleichheit der beiden 401-Zweige ist im Container gemessen (0,351 s und
0,351 s, Rümpfe byteweise gleich). Die Mindestdauer 0,35 s aus
`rate_gleiche_dauer()` deckt dort jeden Unterschied. Auf dem Produktivserver
ist die Datenbank langsamer und der Blindvergleich derselbe — der Fall, in dem
das kippt, wäre eine Datenbankabfrage über 0,35 s, und die hätte andere
Folgen zuerst. Nachmessen kostet zwei `curl`-Aufrufe — Prüfliste 4.

### 1.3 Kein Gerät — das Web ist jetzt da

Mit Paket B gibt es die Geräteseite, und sie ist im Browser gefahren
(Abschnitt 3). Was weiterhin fehlt, ist die **andere Seite**: eine Uhr (C) und
ein Handy, die den neuen Weg wirklich sprechen.

In beiden Proben ist die Probe selbst das Gerät: Sie holt sich über `pair.php`
mit `aktion=start` eine echte Kopplungssitzung und antwortet später mit
`bestaetigen`. Das ist kein Ersatz für eine Uhr — es prüft den Vertrag, nicht
das Display. Was eine Uhr daraus macht (der Code lesbar in zwei Dreiergruppen,
die maskierte Adresse im Dialog, BACK als Abbruch, der Takt der Abfrage),
zeigt erst der Simulator-Rundlauf in Paket C und danach der Gerätetest.

### 1.4 Die Migration in der Produktion

Gefahren auf dem lokalen Bestand (41 Kennungen im Register, `pair_codes` weg,
`pair_sessions` da) und auf einer frischen Installation (als übersprungen
verbucht). Nicht gefahren: auf der Produktionsdatenbank. Die Migration löscht
eine Tabelle — die Vorschau von `update.php` zeigt das rot an („Löscht Daten:
Die Tabelle pair_codes …“). Prüfliste 1.

### 1.5 Die alte Uhr am neuen Server

Dass Uhr 2.0.0 auf `400 {"error":"aktion","meldung":"Uhr-App aktualisieren"}`
die Meldung als zweite Zeile zeigt, folgt aus `Pair.mc` 330–333 (gelesen,
nicht gesehen). Der Server liefert die Meldung mit 21 Zeichen (Fall 5). Ob sie
auf der Uhr steht, zeigt Paket C im Simulator mit der **alten** App gegen den
neuen Server — oder der Gerätetest.

### 1.6 Paket B: was der Browser nicht beantwortet

Der Rundlauf (`tools/kopplungsprobe/rundlauf.mjs`) fährt den ganzen Weg in
**einem** Browser, in **einer** Breite, mit **einem** Konto. Nicht belegt ist
damit:

- **Ein zweiter Browser mit demselben Code.** Dass genau einer gewinnt, hängt
  am `UPDATE … WHERE user_id IS NULL` und seinem `rowCount()` (E-S5-13); die
  Kopplungsprobe prüft die Regel (Fall 9), nicht zwei gleichzeitige Klicks.
- **Ein anderer Browser als Chromium.** Das Nachladen benutzt `fetch`,
  `document.hidden` und `location.assign` — nichts Ausgefallenes, aber
  gemessen ist es nur in Chromium.
- **Ein Reiter, der stundenlang offen liegt.** Dass die Abfrage im Hintergrund
  ruht und beim Zurückkommen sofort nachholt, ist gelesen, nicht gemessen.
- **Der Weg ohne JavaScript.** Die Karte ist so gebaut, dass sie ohne das
  Skript vollständig bleibt (die Auskunft steht im Text, ein Neuladen von Hand
  führt zum selben Ergebnis) — gefahren wurde er nicht. Prüfliste 6.

### 1.7 Die Vollständigkeit steht auf 277 statt 272

Fünf Zeichen mehr, und sie sind einzeln benannt: **drei** Unicode-Zeichen in
den Kommentaren der beiden neuen Dateien (`assets/kopplung.js`,
`api/kopplung_stand.php`) und **zwei** Pfeile mehr in sichtbarem Text, weil
der Weg „Sync-Seite → Gerät koppeln" jetzt an drei Stellen steht statt an
einer (zwei Fehlermeldungen und der Erklärtext).

Das ist kein neues Element und keine Klasse ohne Regel: **Prüfung 1 (Klassen),
2 (Werte) und 4 (Knopfhöhe) sind Zeile für Zeile unverändert** — verglichen
gegen den Stand vor Paket B mit `git stash -u`. Die betroffene Prüfung 3 zählt
jedes `→` und `…` im Repositorium, auch in Kommentaren; 201 solche Zeichen
gehören zum Bestand.

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Mittel | Was es misst | Zahl (03.09.2026) |
|---|---|---|
| `php tools/kopplungsprobe/probe.php` | `pair.php` über echtes HTTP: vier Anliegen, Zustände, Frist, Gerätelimit, Antwortgleichheit, drei Töpfe, Obergrenze, Bibliothek, Aufräumjob, Migrationsregister, Kaskade (Konzept 10.2, Fälle 1–34 plus E-S5-48 und E-S5-49) | **75 Erwartungen, 0 nicht erfüllt, 0 übergangen** — zweimal: gegen den Bestand nach gefahrener Migration und gegen die frische Installation |
| `php tools/ingestprobe/probe.php` | `ingest.php` mit dem neuen Schlüsselverfahren (SHA-256, E-S5-42) | **24 / 24** |
| `php tools/geraeteprobe/probe.php` | Blocklesen `geraet` unverändert | **39 / 39** |
| `php server/update.php` (CLI) auf dem Bestand mit `pair_codes` und einem Code darin | Migration `2026_09_03_kopplungssitzungen` | **applied**; Register 40 → **41**; `pair_codes` weg, `pair_sessions` mit UNIQUE auf `code` und `device_id`, FK CASCADE |
| `lokal_einrichten.sh` (frische Installation aus `schema.sql`) | Register nach `install.php` | **41 Kennungen, alle `skipped`**; Kopplungsprobe danach 75 / 75 |
| `python3 tools/s5-anker/anker.py --paket B/C/D/E` | Anker der übrigen Pakete nach A | B 11 / 11 unverändert · C 27 / 27 · E 32 / 32 · D 16 (7 verschoben durch die Technik-Änderungen, 0 nicht gefunden) |
| `python3 tools/wortliste/wortliste.py` | Bereiche a bis d (Bereich e kommt mit C) | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen (77 / 77), 0 durchgerutschte Fallen** — gefahren zuletzt, nach allen Textänderungen |
| `node tools/kopplungsprobe/rundlauf.mjs` (neu) | Der ganze Weg im Browser: anmelden, drei Zustände, beide Fehlerwege, Umleitung, Neuladen, das Ja am Gerät, das Nachladen, Vollzugsmeldung, Geräteliste, Abmelden | **25 Erwartungen, 0 nicht erfüllt, 0 Konsolenfehler**; das Nachladen griff **3,2 s** nach dem Ja |
| `node tools/screenshots/aufnehmen.mjs --nur 33` | Die drei Zustände der Karte in acht Breiten | **24 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px**. Gegenprobe nach `LIESMICH.md`: **27 Dateien, 27 verschiedene Prüfsummen** — kein Bild zeigt dasselbe wie ein anderes |
| `python3 tools/vollstaendigkeit/pruefen.py` | Stylesheet, Werte, Symbole, Knopfhöhen | **277** (Basis 272; die fünf sind in 1.7 benannt, Prüfung 1/2/4 unverändert) |
| `python3 tools/s5-anker/anker.py` | Fundstellen der Pakete C, D, E | **0 nicht gefunden, 0 mehrdeutig**, 68 unverändert, 7 verschoben (A und B ausgetragen — ihre Stellen sind umgeschrieben) |
| `php -l` | alle geänderten oder neuen PHP-Dateien (A: 16, B: 4) | 0 Syntaxfehler |

**Was die 75 Erwartungen NICHT sind:** kein Beweis für Nebenläufigkeit. Die
Probe schickt Anfragen nacheinander; „zwei Browser mit demselben Code“ ist
über `rowCount()` belegt (Fall 9), „zwei Ja gleichzeitig“ über `FOR UPDATE`
und den Rückfall in den Geräte-Zweig — gelesen, nicht unter Last gefahren.
Der R17-Review in P6 prüft die Transaktion (Konzept 8.3).

### Paket C — Uhr (03.09.2026)

| Mittel | Was es gemessen hat | Zahl |
|---|---|---|
| `pruefstand.sh reihe … -l 3 -w` **vorher** | Ausgangsstand an `origin/main`, in einem eigenen Arbeitsbaum | **99 übersetzt · 0 fehlgeschlagen · 0 Warnungen · 0 Fehler** |
| `pruefstand.sh reihe … -l 3 -w` **nachher** | derselbe Lauf mit Uhr 3.0.0 | **99 · 0 · 0 · 0** — unverändert; App wächst um Ø 9,4 kB (max. d2delta +9824 Byte) |
| `pruefstand.sh bildreihe` | 20 Vertreterklassen aus `geraeteklassen.py`, Konsole auf `error\|crash\|exception` durchsucht | **20 Vertreter · 0 Abstürze · 18 Bilder der `PairView`** (die zwei übrigen: 1.1b) |
| Simulator-Rundlauf | 6 Fälle × 3 Zielgeräte gegen die **echte** lokale Installation (Web 13.1.1) | **5 von 6 Fällen belegt**: Ja, Nein, BACK, Fristablauf, Gerätelimit. Der sechste nur zur Hälfte (1.1a) |
| Gegenlesung des Uhr-Codes | fünf Dimensionen (Vertrag, Zustandsmaschine, Layout, Typprüfung, Texte), jeder Befund einzeln zu widerlegen versucht | **32 Befunde · 16 widerlegt · 16 bestätigt · 16 behoben** |
| `tools/wortliste/` | fünf Bereiche, davon `e` neu (`watch/`, XML **und** Monkey C) | **0 Treffer außerhalb der Ausnahmen · 0 ungenutzte Ausnahmen · 0 durchgerutschte Fallen**; Bereich `e`: **35 Dateien**, 2 Treffer, beide erklärt |
| `wortliste.py --probe` | Selbstprobe des Zerlegers, inkl. zwei neuer Monkey-C-Fälle | **21 / 21** |
| Vertragsrundlauf über `curl` | `start` → `status offen` → `beansprucht` (maskiert) → `bestaetigen ja` → `gekoppelt`; dazu die alte Uhr ohne `aktion` | alle Antworten wie Vertrag 1a; `api_key_hash` **64 Zeichen** (SHA-256) |

**Was diese Zahlen NICHT sagen:** Der Bilderlauf misst, dass etwas gezeichnet
wurde und die Konsole schweigt — nicht, dass es richtig aussieht. Dafür sind
die Bilder da, und die drei Zielgeräte sind einzeln angesehen worden.

## 3. Was im Browser geprüft wurde

**Paket A** hat keine Oberfläche — dort war nichts zu klicken.

**Paket B** ist im Browser gefahren, und zwar automatisiert: Der Rundlauf oben
ist eine echte Bedienung mit Chromium, kein Abruf von Markup. Er klickt sich
durch alle drei Zustände, tippt den Code so ein, wie ein Mensch ihn abliest
(mit Leerzeichen, klein geschrieben), lädt im Wartezustand von Hand neu, lässt
das Gerät Ja sagen und sieht zu, ob die Seite von selbst nachlädt. Dazu die
Bilder in acht Breiten, von 360 bis 1920 px.

**Der eine echte Fehler dieses Pakets ist dabei gefunden worden**, und er wäre
beim Lesen nicht aufgefallen: Das Nachladen sprang auf
`…?t=geraete#geraeteliste`, während die Seite auf `…?t=geraete#koppeln` stand.
Eine Navigation, die nur das Fragment ändert, ist keine — der Browser scrollt
und fragt den Server nicht. Die Karte wartete weiter auf ein Gerät, das längst
in der Liste stand. Nachgemessen, behoben, und die Messung steht als
Begründung im Code (E-S5-57).

## 4. Prüfliste — was du tun musst

Je Punkt: der Bedienweg, das erwartete Ergebnis, **woran ein Scheitern zu
erkennen ist**.

### 1. Nach dem Deploy: `update.php` aufrufen  *(zwingend)*

- **Weg:** als Administratorin `update.php` öffnen. Die Vorschau zeigt
  `2026_09_03_kopplungssitzungen` als „STEHT AN“, mit rotem Hinweis „Löscht
  Daten: Die Tabelle pair_codes mit allen Kopplungscodes wird gelöscht.“
  Ausführen.
- **Erwartet:** Zeile „Erfolgreich angewendet.“; das Register zählt 41.
- **Scheitern:** „Fehler: …“ in der Zeile — dann ist `pair_codes` noch da
  und `pair_sessions` fehlt; ein `start` vom Gerät antwortet `500`. Die
  Migration ist wiederholbar (`CREATE TABLE IF NOT EXISTS`, `DROP TABLE IF
  EXISTS`). **Fehlt der rote Hinweis in der Vorschau**, ist das ein Fund für
  sich: Dann zeigt `update.php` den Schlüssel `zerstoert` nicht mehr an.
- [ ] erledigt am ______

### 2. Die Bestandsuhr neu koppeln  *(nach C, mit der neuen Uhr-Fassung)*

- **Weg:** Sync-Seite der Uhr, bis „Sync vollständig“; dann START halten →
  Kopplung trennen → neuer Code → im Web eingeben → Ja.
- **Erwartet:** „Gekoppelt“ auf der Uhr; ein Upload danach `200`.
- **Scheitern:** Die Uhr meldet vor der Neukopplung `401` beim Sync — das ist
  der bcrypt-Hash (erwartet) und kein Fehler; **ein 401 nach der Neukopplung**
  wäre einer.
- [ ] erledigt am ______

### 3. Den Text der Kopplungsmail sichten

- **Weg:** eine Kopplung auf der Produktionsinstallation abschließen (Punkt 2)
  und die Mail „Neues Gerät gekoppelt“ lesen.
- **Erwartet:** Art und Modell („Uhr · …“), Geräte-ID, Zeitpunkt, der Satz
  „Das Gerät hat den Code gezeigt, du hast ihn im Web eingegeben und am Gerät
  mit Ja bestätigt“.
- **Scheitern:** Keine Mail innerhalb einer Minute (Fehlerprotokoll: „Hinweis
  auf neues Geraet konnte nicht verschickt werden“), oder „Gerät unbekannt“,
  obwohl die Uhr einen Block gesendet hat.
- [ ] erledigt am ______

### 4. Antwortgleichheit auf dem Produktivserver nachmessen

- **Weg:** zweimal `curl -s -o /dev/null -w '%{http_code} %{time_total}\n'
  -X POST -H 'Content-Type: application/json' -H 'X-Device-Id: dev-gibt-es-nicht'
  -H 'X-Api-Key: x' -d '{"aktion":"status"}' https://…/pair.php`, einmal mit
  einer **echten** Kennung aus der Geräteliste und falschem Schlüssel.
- **Erwartet:** beide `401`, beide ≥ 0,35 s, Rümpfe `{"error":"auth"}`.
- **Scheitern:** eine Dauer deutlich über der anderen (mehr als 50 ms) — dann
  ist die Datenbankabfrage auf dem Server langsamer als die Mindestdauer und
  `rate_gleiche_dauer()` gehört an dieser Stelle angehoben.
- [ ] erledigt am ______

### 5. Die alte Uhr gegen den neuen Server  *(Paket C oder Gerätetest)*

- **Weg:** Uhr 2.0.0 (alte Fassung) START halten, Code eingeben (irgendeinen).
- **Erwartet:** „Kopplung fehlgeschlagen (400)“ und darunter „Uhr-App
  aktualisieren“.
- **Scheitern:** nur die erste Zeile — dann zeigt die alte Uhr die Meldung
  nicht, und das Handbuch 12 muss den Fall ausdrücklich beschreiben.
- [ ] erledigt am ______

### 6. Die Geräteseite **ohne JavaScript**

- **Weg:** In den Browsereinstellungen JavaScript für die Seite abschalten
  (Chromium: Einstellungen → Datenschutz und Sicherheit → Website-Einstellungen
  → JavaScript → Blockieren), dann eine Kopplung von Anfang bis Ende fahren.
- **Erwartet:** Alle drei Zustände arbeiten. Im Wartezustand steht dieselbe
  Auskunft, und nachdem am Gerät Ja gesagt wurde, zeigt ein Neuladen von Hand
  das Gerät in der Liste samt Vollzugsmeldung.
- **Scheitern:** Ein Zustand, in dem nichts weitergeht, oder eine Seite, die
  ohne das Skript etwas Falsches behauptet — etwa eine Restzeit, die stehen
  bleibt und nicht als Stand beim Seitenaufbau zu erkennen ist.
- [ ] erledigt am ______

### 7. Der Verbindungsabriss mit der Uhr in der Hand  *(nur am Gerät)*

**Warum:** Der Simulator liefert für eine tote Verbindung HTTP 404 statt eines
negativen Codes (1.1a). Der Zweig, der den Code stehenlässt und weiterfragt,
ist damit ungeprüft.

**Bedienweg:** Kopplung starten, bis der Code steht. Dann am Telefon Bluetooth
ausschalten (oder sich außer Reichweite bewegen) und **mindestens zehn
Sekunden** warten.

**Erwartet:** Der Code bleibt stehen. Unter ihm erscheint rot
„Keine Verbindung (−104)" oder eine andere negative Zahl, darunter unverändert
die Restzeit. Bluetooth wieder an → die Zeile verschwindet beim nächsten
Nachfragen, die Kopplung lässt sich zu Ende führen.

**Scheitern erkennt man daran:** Die Uhr springt auf die Sync-Seite zurück und
meldet „Code abgelaufen" oder „Kopplung fehlgeschlagen (n)" — dann wirft sie
eine lebende Sitzung weg. Oder der Code bleibt zwar stehen, aber es erscheint
keine Zeile — dann sieht die Trägerin nicht, warum nichts geschieht.

---

### 8. Die Tastensperre  *(nur am Gerät — der Grund, warum es sie gibt)*

**Warum:** Der Simulator bildet Tastensperren nicht ab (1.1c). Die Behebung ist
übersetzt und gelesen, nicht erlebt.

**Bedienweg, alle vier Kombinationen einzeln:**
1. Auf dem Startbildschirm **UP zuerst** halten, dann START dazu.
2. Dasselbe mit **START zuerst**, dann UP.
3. Auf der **Sync-Seite** beides noch einmal.
4. Auf der **Reanimationsseite** beides noch einmal.

**Erwartet:** Die Uhr sperrt, und die App tut **nichts** — kein Schnellmenü,
keine Kopplung, kein Rea-Ereignis. Nach dem Entsperren ist die App unverändert
bedienbar; ein einzelner langer Druck wirkt wieder normal.

**Scheitern erkennt man daran:** Das Schnellmenü öffnet sich (der alte Fehler,
Reihenfolge 1). Auf der Sync-Seite erscheint die Frage „Kopplung trennen und
neu koppeln?" oder ein Code. Auf der Rea-Seite wird ein **Adrenalin** oder eine
**Rhythmuskontrolle** eingetragen — das ist der schlimmste Fall, weil er stumm
in die Dokumentation läuft. Oder umgekehrt: Nach dem Entsperren reagiert der
erste lange Druck nicht mehr (dann bleibt `_fremdKey` hängen; der zweite muss
wieder wirken, sonst ist die Behebung falsch).

---

### 9. Die Oberfläche auf zwei Geräteklassen  *(am Arbeitsplatz oder am Gerät)*

**Warum:** `fenix8solar47mm` und `fenix9prosolar51mm` haben die `PairView` im
Container nicht gezeigt (1.1b) — einmal die Automatik, einmal der Simulator
selbst.

**Bedienweg:** Am eingerichteten Arbeitsplatz mit der Monkey-C-Erweiterung für
beide Geräte bauen, starten, DOWN → Sync-Seite, START halten.

**Erwartet:** Derselbe Aufbau wie auf den drei Zielgeräten — „Code für das
Web", der Code in zwei Dreiergruppen, „Einstellungen, Geräte", die Restzeit.

**Scheitern erkennt man daran:** Der Code wird abgeschnitten oder überlappt
eine Nachbarzeile; die untere Zeile läuft über den Rand. Beides wären
Layoutfehler, die `Ui.fitFont` auf diesen Größen nicht auffängt.

---

## 5. Grenzen der benutzten Prüfmittel

- **Kopplungsprobe:** ein Aufrufer, nacheinander; kein Mailserver; die
  Code-Eingabe im Web simuliert über die Bibliothek (die Aktionen im Formular
  sind Paket B); die Migration als Zustand gelesen, nicht als Lauf gefahren
  (der Lauf steht in Abschnitt 2). Sie leert die Ratenschutz-Töpfe für
  `127.0.0.1` — auf einem Server mit Betrieb wäre das ein Eingriff; sie ist
  für den Prüfstand gedacht.
- **Ingestprobe:** prüft den Weg, nicht den Bestand — sie legt ihr eigenes
  Konto an.
- **`update.php` auf der Kommandozeile:** kennt die Freigabe für blockierte
  Migrationen nicht (die gibt es hier nicht) und zeigt keine Vorschau — die
  rote Zeile „Löscht Daten“ ist **nicht gesehen worden**, weder im Browser
  noch auf der Kommandozeile; sie folgt aus dem Schlüssel `zerstoert` und dem
  Muster der Phase-10-Migration. Prüfliste 1 sieht sie.
- **Anker:** finden Zeilen nach Inhalt; sie sagen, dass C, D und E ihre
  Fundstellen noch haben, nicht, dass die Fundstellen noch stimmen. Die Anker
  der Pakete A und B sind ausgetragen — ihre Stellen sind umgeschrieben.
- **Der Browserrundlauf:** ein Browser (Chromium), eine Breite, ein Konto,
  eine Sitzung. Er beweist, dass der Weg trägt — nicht, dass er unter zwei
  gleichzeitigen Bedienungen trägt (dafür steht die Regel in E-S5-13 und
  Kopplungsprobe Fall 9), und nicht, dass er ohne JavaScript trägt (Prüfliste
  6). Er läuft im Demo-Konto und meldet sein Prüfgerät am Ende ab; bricht er
  mittendrin ab, bleibt eines stehen.
- **Der Bilderlauf:** misst Überlauf, Konsolenfehler und Knopfhöhen — nicht,
  ob die Seite richtig aussieht. Seine beiden neuen Bedienschritte holen sich
  eine echte Kopplungssitzung; sie kosten zwei der zwanzig `start`-Aufrufe,
  die der Ratenschutz je zehn Minuten zulässt.
