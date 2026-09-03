# Prüfdokument S5 — was **du** noch prüfen musst

Zur Phase S5 („Kopplung umgekehrt“). Das Prüfprotokoll im Konzept
(`Konzept-S5-Kopplung-umgekehrt.md`, Abschnitte 9 und 10) beantwortet *„ist es
belegt?“*; dieses Dokument beantwortet *„was muss ich noch tun?“*. Es wird je
Paket ergänzt und bleibt nach dem Abschluss der Phase stehen, bis seine
Prüfliste abgehakt ist (K9, R62).

> **Stand: Paket A (Server, Web 13.0.0), die Korrekturen 13.0.1 und 13.1.1,
> Paket B (Weboberfläche, Web 13.1.0), die erste Hälfte von Paket D
> (Dokumentation, Web 13.1.2) und der Zusatz **Paket W — Wartungsmodus**
> (Web 13.2.0) sind gebaut** — auf
> `claude/s7-umsetzung-vorbereiten-s8kax0`. Offen: C (Uhr), E
> (Android-Zusatz) — beide in eigenen Instanzen — und **D Hälfte 2**, die auf C
> wartet (E-S5-58).
>
> **Paket W hat ein eigenes Konzept**
> (`Konzept-S5-Zusatz-Wartungsmodus.md`) mit eigenem Nummernkreis; seine
> Prüfpunkte stehen hier, weil es nur ein Prüfdokument je Phase gibt (K9).
> Was du dafür tun musst, steht in Prüfliste **10 und 11** — und Punkt 10 ist
> zugleich der einzige Nachweis, den der Container nicht führen kann (1.11).
>
> **Bis Paket C beschreibt `docs/Handbuch.md` 12 den alten Weg.** Das ist
> gewollt und benannt: Die sieben Stellen nennen Wortlaute der Uhr, die Paket C
> erst festlegt. Wer vor C nach der Bedienanleitung koppelt, kommt nicht ans
> Ziel — für einen Zweig, der nicht auf `main` geht, ist das folgenlos.
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
> neuen Weg; den Knopf „Kopplungscode erzeugen“ gibt es nicht mehr. Auf `main`
> kommt die Phase trotzdem erst am Ende, nach Paket C und D — eine Uhr, die
> den alten Weg spricht, kann sich nach dem Deploy nicht mehr koppeln.
>
> **Der Server-Rundlauf der Android-Instanz ist seit Paket A stillgelegt.** Er
> legte Kopplungscodes per SQL in `pair_codes`, und die Tabelle gibt es nicht
> mehr. `android/LIESMICH.md` trägt seit D Hälfte 1 ein `git worktree`-Rezept
> auf einen Stand vor S5 (Quelltext **und** Datenbank) — das ist ein Hinweis
> für die andere Instanz, keine Prüfung, die hier abzuhaken wäre.

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

### 1.1d Uhr: die Restzeile war auf der Venu 3s randvoll — der Wortlaut wurde gekürzt

**Behoben, hier nur zur Nachvollziehbarkeit.** Der erste Entwurf der Restzeit
hieß „noch 10 min gültig" und maß auf der Venu 3s **194 px** gegen eine Sehne
von **193 px** — einen Pixel über der Linie, die sich das Projekt selbst
zieht, und zwar **nachdem** `Ui.fitFont` bereits auf die kleinste verfügbare
Schrift zurückgefallen war. Gezeichnet wurde er trotzdem vollständig, weil
`Ui.chordW` zusätzlich `Ui.s(dc,16)` = 24 px Rand abzieht; am Simulatorbild
nachgesehen und die Tinte darin nachgemessen. Auf Fenix 6 Pro (120/128 px)
und FR945 (111/118 px) blieben 8 bzw. 7 px.

Auf Ansage vom 03.09.2026 heißt die Zeile jetzt **„10 min gültig"** — rund
140 px gegen 193, die Reserve ist zurück. Am Bild belegt.

**Was daran zu prüfen bleibt:** Der Simulator zeichnet mit denselben
Schriftdateien wie das Gerät, aber der Gehäuserand einer echten Venu 3s ist
nicht derselbe wie die gezeichnete Lünette. Prüfliste 10.

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

### 1.8 Paket D: die Trennen-Mail geht nicht raus, und das Rezept ist ungefahren

**Zwei Dinge aus D Hälfte 1 sind nur gelesen, nicht gefahren.**

**Die Trennen-Mail.** Sie ist umgeschrieben — sie beschrieb den Rückweg über
einen Knopf, den es seit Web 13.0.0 nicht mehr gibt. Geprüft ist ihr
**Wortlaut im Quelltext** (`server/pair.php` 439–455) und der **Versandweg**
(Kopplungsprobe Fall 26: `trennen` antwortet, danach wird verschickt). Nicht
geprüft ist, was im Postfach ankommt: Der Prüfstand hat keinen Mailserver.
Das gilt seit Paket A schon für die Kopplungsmail (1.1) — jetzt für beide.
**Prüfliste Punkt 3** deckt beide ab.

**Das `git worktree`-Rezept in `android/LIESMICH.md`.** Es beschreibt, wie
sich ein Server vor S5 aufsetzen lässt, damit die Android-Instanz ihren
Server-Rundlauf weiterfahren kann. **Es ist nicht gefahren worden** — dafür
bräuchte es eine zweite Datenbank und einen zweiten PHP-Server neben der
laufenden Installation, und der Prüfstand hat einen Port und ein Schema.
Geprüft ist nur, dass die genannten Commits existieren und die genannten
Dateien darin liegen. **Woran ein Scheitern zu erkennen wäre:** Die
Android-Instanz meldet, dass `pair_codes` auch im Worktree fehlt (dann ist
der Commit falsch gewählt) oder dass `install.lock` den zweiten Server
blockiert (dann fehlt ein Schritt im Rezept).

### 1.9 Die Handbuch-Stellen sind gezählt, nicht behoben

Die Zahl **K3** aus der Konsistenzlesung („Kopplungscode erzeugen“ /
„Code eintippen“ als Handlungsanweisung, außerhalb von Changelog, Archiv und
erledigten Konzepten) steht nach D Hälfte 1 auf **sieben**, nicht auf null.
Alle sieben stehen in `docs/Handbuch.md`: **2274, 2674, 2675, 2682, 2698,
2700, 2718**. Sie gehören zu D Hälfte 2 und warten auf Paket C (E-S5-58).
Wer K3 vor Hälfte 2 als Null meldet, meldet etwas, das er nicht gemessen hat.

Dieselbe Teilung trifft **K4** (`beispieldomain` in `watch/` und im Handbuch):
vier Zeilen, davon drei in `watch/` bei der Uhr-Instanz und eine im Handbuch
(2668) bei Hälfte 2. Nach D Hälfte 1 steht K4 unverändert auf **vier**.

### 1.10 Die Vollständigkeit steht jetzt auf 278 — das sechste Zeichen

Ein Zeichen mehr als nach Paket B, und es ist benannt: **`server/pair.php`
Zeile 449**, der Pfeil in „Sync-Seite → Gerät koppeln" in der neu
geschriebenen Trennen-Mail. Prüfung 3 zählt jedes `→` im Repositorium, auch
in Zeichenketten, die nie in Markup landen.

**Warum er bleibt und nicht ausgeschrieben wird:** Die Kopplungsmail drei
Funktionen weiter oben (`pair.php` 337, aus Paket A) schreibt den Menüpfad
genauso — „Einstellungen → Geräte". Zwei Mails derselben Datei, die denselben
Weg verschieden schreiben, wären die schlechtere Wahl. Gemessen per
`git worktree` gegen `dcaede6`: **Prüfung 1 (Klassen), 2 (Werte) und 4
(Knopfhöhe) Zeile für Zeile unverändert**, ein einziger neuer Eintrag in
Prüfung 3.

### 1.11 Paket W: der Deploy ist die eine Sache, die der Container nicht kann

**Ob der FTPS-Sync die `server/wartung.lock` stehen lässt, ist NICHT
geprüft.** Die Ausnahme in `.github/workflows/deploy.yml` ist eine Zusage —
`wartung.lock` steht dort neben `config.php`, `install.lock`, `sicherungen/`
und `apk/`. Bewiesen wird sie erst beim ersten Deploy im Wartungsmodus, und
das ist der Merge dieser Phase selbst (Prüfliste Punkt 10).

**Woran ein Scheitern zu erkennen wäre:** Nach dem Push antwortet die
Installation wieder normal, obwohl niemand ausgeschaltet hat — dann hat der
Sync die Datei gelöscht. **Was dann zu tun ist:** sofort wieder einschalten
(die Datei ist der ganze Schalter) und den Eintrag in `deploy.yml` prüfen;
die Aktion `SamKirkland/FTP-Deploy-Action` prüft Datei- und Verzeichnismuster
getrennt, deshalb steht `sicherungen/` dort zweimal — `wartung.lock` ist eine
Datei und braucht nur einen Eintrag.

**Ebenfalls nicht geprüft: die Wartung mit einem echten Gerät in der Hand.**
Dass Uhr und Handy ein 503 puffern und danach nachliefern, steht im
JSON-Vertrag und ist im S4-Prüfprotokoll für die Android-App gemessen — hier
ist es **gelesen, nicht gefahren**. Prüfliste Punkt 10 fährt es mit.

**Und nicht geprüft: die Anmeldung im Wartungsmodus über HTTP.** `login.php`
ist ohne im Browser abgeleitetes Token nicht zu erreichen. Fall 18 der
Wartungsprobe liest die drei Regeln aus E-S5W-09 stattdessen **am Code** nach
— dass `role` in der Abfrage steht, dass die Sitzung verworfen wird, und dass
das **erst nach** `rate_erfolg` geschieht. Eine am Code gelesene Regel ist
keine gefahrene; Prüfliste Punkt 11 fährt sie.

### 1.12 Das Handbuch ist gelesen, nicht bedient

`docs/Handbuch.md` 10, 12 und 12.1 beschreiben jetzt den neuen Weg, und die
Wortlaute sind **aus dem Quelltext abgeschrieben** — `PairView.mc` (Codeblock,
Restzeit, Verbindungshinweis) und `Pair.mc` (Rückfrage, neun Fehlerpaare,
Trennen). Nicht aus dem Konzept: Dort standen Entwürfe, gebaut wurde teils
anders („Einstellungen, Geräte" statt „Einstellungen → Geräte", E-S5-63).

**Was das nicht beweist:** dass jemand mit dem Handbuch in der einen und der
Uhr in der anderen Hand durchkommt. Ein abgeschriebener Wortlaut kann an der
richtigen Stelle stehen und trotzdem im falschen Schritt. **Das ist der
P2-Prüfpunkt 4.1** (R55), und er steht als Prüflistenpunkt 12.

**Zwei Zahlen dazu, beide auf null:** **K3** — Handlungsanweisungen „Code
erzeugen"/„Code eintippen" außerhalb von Changelog, Archiv und erledigten
Konzepten: nach D Hälfte 1 sieben, jetzt **0**. Die eine verbleibende
Fundstelle (`Handbuch.md` 2153, „Plus Code eintippen") ist die Adresseingabe
und war nie gemeint. **K4** — `beispieldomain` in `watch/` und Handbuch: vorher
vier, jetzt **0**; drei Zeilen hat Paket C geschlossen, die vierte diese Hälfte.
Was unter `beispieldomain` noch findbar ist, steht im Changelog, im
Rahmenplan-Archiv und in `docs/mockups/S4-app.html` — Historie und ein
Android-Mockup, beides kein Handlungstext.

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Mittel | Was es misst | Zahl (03.09.2026) |
|---|---|---|
| `php tools/kopplungsprobe/probe.php` | `pair.php` über echtes HTTP: vier Anliegen, Zustände, Frist, Gerätelimit, Antwortgleichheit, drei Töpfe, Obergrenze, Bibliothek, Aufräumjob, Migrationsregister, Kaskade (Konzept 10.2, Fälle 1–34 plus E-S5-48 und E-S5-49) | **76 Erwartungen, 0 nicht erfüllt, 0 übergangen** (75 in A, dazu E51 in 13.1.1) — gefahren nach A, nach B und nach D Hälfte 1 |
| `php tools/ingestprobe/probe.php` | `ingest.php` mit dem neuen Schlüsselverfahren (SHA-256, E-S5-42); seit 13.0.1 dazu Teil 7, der Upsert gegen ein spät eintreffendes Teilstück | **30 / 30** |
| `php tools/wartungsprobe/probe.php` (neu) | Den Wartungsmodus über echtes HTTP: was gesperrt wird (Seiten, `ingest.php` mit **gültigem** Schlüssel, `pair.php`, `/api/`), was offen bleibt (die sechs Ausnahmen), Schalten per POST mit CSRF, kaputte Schalterdatei, Antwortzeit, CLI-Notausgang, Ausnahmeliste gegen E-S5W-04, die drei Regeln aus E-S5W-09 am Code, der Münzwurf des Logos | **40 Erwartungen, 0 nicht erfüllt.** Darunter: keine Zeile in `missions` trotz gültigem Geräteschlüssel · kein `Set-Cookie` auf dem 503 · das 503 kommt in **0,3 ms** statt 1,4 ms — das Tor greift vor Datenbank und Ratenschutz |
| `php tools/geraeteprobe/probe.php` | Blocklesen `geraet` unverändert | **39 / 39** |
| `php server/update.php` (CLI) auf dem Bestand mit `pair_codes` und einem Code darin | Migration `2026_09_03_kopplungssitzungen` | **applied**; Register 40 → **41**; `pair_codes` weg, `pair_sessions` mit UNIQUE auf `code` und `device_id`, FK CASCADE |
| `lokal_einrichten.sh` (frische Installation aus `schema.sql`) | Register nach `install.php` | **41 Kennungen, alle `skipped`**; Kopplungsprobe danach 75 / 75 |
| `python3 tools/s5-anker/anker.py --paket B/C/D/E` | Anker der übrigen Pakete nach A | B 11 / 11 unverändert · C 27 / 27 · E 32 / 32 · D 16 (7 verschoben durch die Technik-Änderungen, 0 nicht gefunden) — Stand nach Paket A |
| `python3 tools/wortliste/wortliste.py` | Bereiche a bis d (Bereich e kommt mit C) | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen (77 / 77), 0 durchgerutschte Fallen** über **128 Dateien** (a 88 · b 30 · c 8 · d 2) — gefahren zuletzt, nach allen Textänderungen. In D Hälfte 1 fand sie **einen** Treffer, und zwar in neu geschriebenem Text dieses Pakets („der Ablauf hat drei Stationen“, Technik-Runbook); ersetzt und erneut gefahren |
| `node tools/kopplungsprobe/rundlauf.mjs` (neu) | Der ganze Weg im Browser: anmelden, drei Zustände, beide Fehlerwege, Umleitung, Neuladen, das Ja am Gerät, das Nachladen, Vollzugsmeldung, Geräteliste, Abmelden | **25 Erwartungen, 0 nicht erfüllt, 0 Konsolenfehler**; das Nachladen griff **3,2 s** nach dem Ja (B) und **3,1 s** (D Hälfte 1) |
| `node tools/screenshots/aufnehmen.mjs --nur 33` | Die drei Zustände der Karte in acht Breiten | **24 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px**. Gegenprobe nach `LIESMICH.md`: **27 Dateien, 27 verschiedene Prüfsummen** — kein Bild zeigt dasselbe wie ein anderes |
| `node tools/screenshots/aufnehmen.mjs --nur 07-wartungsseite,45a` | Die Wartungsseite und die Adminseite **mit** stehendem Wartungsmodus, je acht Breiten | **16 Bilder, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe ≠ 44 px** |
| `python3 tools/vollstaendigkeit/pruefen.py` | Stylesheet, Werte, Symbole, Knopfhöhen | **278** (Basis 272; die fünf aus Paket B sind in 1.7 benannt, das sechste in 1.10; Prüfung 1/2/4 unverändert) |
| `python3 tools/s5-anker/anker.py` | Fundstellen der Pakete C, D, E | nach D Hälfte 1: **0 nicht gefunden, 0 mehrdeutig**, 64 unverändert, 2 verschoben. Die Liste ist von 83 auf **66** geschrumpft — A und B (11, in Paket B) und neun von D sind ausgetragen, weil ihre Stellen umgeschrieben sind. **Zwei davon meldeten vor dem Austragen `NICHT GEFUNDEN`** (`vertrag.1b-429`, `backup.pair-codes`): die Gegenprobe, dass D Hälfte 1 sie wirklich angefasst hat |
| `php -l` | alle geänderten oder neuen PHP-Dateien (A: 16, B: 4, D Hälfte 1: 5, W: 6) | 0 Syntaxfehler |

**Was die 76 Erwartungen NICHT sind:** kein Beweis für Nebenläufigkeit. Die
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
| Bildstrecke aller Zustände | 10 Zustände × 3 Zielgeräte, dazu 4 Sonderfälle auf der fenix6pro | **34 Bilder**: Sync frisch · Kopplungsansicht · Rückfrage · gekoppelt · Trennen-Rückfrage · nicht gekoppelt · abgebrochen · abgelaufen · Gerätelimit · Serverfehler · Restzeit unter einer Minute · „Server antwortet nicht" über der Restzeit |
| Textbreiten aus den Gerätedateien | die vier Zeilen der Kopplungsansicht, drei Zielgeräte, mit unabhängiger Gegenmessung (FreeType) | Code, „Kopplungscode" und „Im Web eingeben" passen mit Reserve; **Restzeile auf der Venu 3s 194 px gegen 193 px Sehne** — ein Pixel über der Sicherheitslinie, gezeichnet wird sie vollständig (1.1d) |
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

**Paket D Hälfte 1** ändert zwei sichtbare Texte, und beide sind im Browser
gegengelesen — der eine automatisiert, der andere nicht prüfbar:

- **Der Demo-Hinweis** („Ausprobieren ist ausdrücklich erwünscht — ändern,
  anlegen, löschen, **Gerät koppeln**"). Gemessen mit Chromium auf zwei Seiten
  des Demo-Kontos (Startseite und Geräte-Reiter): „Gerät koppeln" **1× bzw.
  3×**, „Uhr koppeln" **0×**. Er steht auf **jeder** Seite des Demo-Kontos
  (`ui_leiste_ende()`), also auch auf allen, die der Rundlauf durchläuft.
- **Die Trennen-Mail** — nicht im Browser prüfbar, siehe 1.8.

Der Rundlauf selbst ist nach D Hälfte 1 noch einmal gefahren: **25 / 25, 0
Konsolenfehler**, das Nachladen griff 3,1 s nach dem Ja. **Beim ersten
Versuch scheiterte er**, und zwar nicht am Code: Der Demo-Reset fiel genau in
die Anmeldung, `session_epoch` wurde hochgezählt, und die eine Sekunde alte
Sitzung war beendet. Das ist eine Überschneidung des Prüfstands, keine
Auskunft über die Anwendung; sie ist jetzt in
`tools/kopplungsprobe/LIESMICH.md` beschrieben, mit dem Erkennungsmerkmal im
Serverprotokoll (`[302]: GET /einstellungen.php` unmittelbar gefolgt von
`[200]: GET /login.php`) und der SQL-Abfrage für die Restzeit.

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

### 3a. Den Text der Trennen-Mail sichten  *(neu mit D Hälfte 1)*

- **Weg:** ein gekoppeltes Gerät an ein **anderes** Konto koppeln (oder am
  Gerät „trennen" auslösen) und die Mail „Gerät getrennt" im Postfach des
  vorherigen Kontos lesen.
- **Erwartet:** Geräte-ID, Zeitpunkt — und der **Rückweg in der neuen
  Richtung**: „Starte die Kopplung auf dem Gerät (Sync-Seite → Gerät koppeln)
  und gib den Code, den es zeigt, hier ein", darunter die Adresse
  `…/einstellungen.php?t=geraete`.
- **Scheitern:** Die Mail nennt „Kopplungscode erzeugen" oder schickt auf
  einen Knopf, den die Geräteseite nicht mehr hat — dann ist eine ältere
  Fassung von `server/pair.php` ausgeliefert. Ebenfalls Scheitern: Die Mail
  kommt gar nicht (Fehlerprotokoll: „Hinweis auf getrenntes Geraet konnte
  nicht verschickt werden").
- **Warum du das prüfen musst:** Der Prüfstand hat keinen Mailserver — geprüft
  ist nur der Wortlaut im Quelltext und der Versandweg (1.8).
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
### 10. Das erste Update mit Wartungsmodus  *(der Merge dieser Phase, Paket W)*

Der erste Einsatz des Schalters ist der Merge, der ihn bringt. Das ist kein
Zufall, sondern der Plan (Konzept W 6.2): Es gibt keinen zweiten Anlass, bei
dem sich das Zusammenspiel aus Schalter, Deploy und Gerät so vollständig
zeigt.

- **Weg:** Wartung → Karte **„Serverbetrieb"** → *Wartungsmodus einschalten*.
  Dann den Merge auf `main` auslösen. Danach `update.php` neu laden und die
  Migrationen ausführen (bei diesem Merge steht keine an — die letzte war
  13.0.0). Startseite in einem zweiten Reiter aufrufen. Dann *Wartungsmodus
  ausschalten*, Startseite erneut.
- **Und während der Wartungsmodus steht:** an der Uhr einen Einsatz
  abschließen, damit sie sendet.
- **Erwartet:**
  1. Nach dem Einschalten steht auf `update.php` oben ein **oranger Balken**
     mit Zeitpunkt und deinem Namen.
  2. Der zweite Reiter zeigt die **Wartungsseite** (Logo, „Wartung", der
     orange Kasten) — **nicht** die Startseite und **nicht** eine
     Fehlermeldung.
  3. **Nach dem Push steht der Balken immer noch.** Das ist der eigentliche
     Nachweis: Der FTPS-Sync hat `wartung.lock` nicht gelöscht.
  4. Die Uhr sagt „später erneut" und **behält ihren Puffer**.
  5. Nach dem Ausschalten antwortet die Startseite, und in der Fußzeile steht
     die neue Fassung.
  6. Der Einsatz von der Uhr kommt beim nächsten Sync an — **vollständig**,
     ohne dass du etwas tust.
- **Scheitern, und was es heißt:**
  - Nach dem Push ist der Balken **weg** und die Startseite antwortet → der
    Deploy hat die Datei gelöscht. Sofort wieder einschalten und den Eintrag
    `wartung.lock` in `.github/workflows/deploy.yml` prüfen (1.11).
  - Der zweite Reiter zeigt die **Startseite** statt der Wartungsseite → der
    Schalter greift nicht. `server/wartung.lock` muss neben `db.php` liegen.
  - Die Uhr meldet einen **Fehler** statt „später erneut" → sie hat kein 503
    bekommen, sondern etwas anderes. Im Fehlerprotokoll des Webspace nachsehen.
  - Der Einsatz kommt nach dem Ausschalten **nicht** an → nicht der
    Wartungsmodus, sondern der Sync. Der Puffer der Uhr leert sich erst nach
    bestätigtem `next_seq`.
- **Zeitpunkte und Ergebnis hier notieren:** eingeschaltet ______,
  gepusht ______, ausgeschaltet ______, Einsatz angekommen ______.
- [ ] erledigt am ______

### 11. Anmelden während der Wartung  *(zwei Konten, Paket W)*

- **Weg:** Wartungsmodus einschalten. In einem privaten Fenster mit einem
  **Nicht-Admin-Konto** anmelden. Danach im selben Fenster mit einem
  **Admin-Konto** anmelden.
- **Erwartet:** Das Nicht-Admin-Konto sieht nach dem Absenden die
  **Wartungsseite** — nicht das Anmeldeformular und keine Fehlermeldung; es
  ist danach **nicht** angemeldet. Das Admin-Konto landet normal in der
  Anwendung (die Startseite ist getort, also auf `update.php` gehen).
- **Scheitern:** Erscheint wieder das **Anmeldeformular**, liest sich das wie
  „Passwort falsch" — dann greift E-S5W-09 nicht richtig, und die Person tippt
  weiter, bis der Ratenschutz zuschlägt. Ebenfalls Scheitern: Das
  Nicht-Admin-Konto ist danach angemeldet und sieht überall 503.
- **Und danach:** Mit demselben Nicht-Admin-Konto nach dem **Ausschalten**
  anmelden. Es muss **sofort** gehen — der Ratenschutz darf die richtigen
  Versuche von vorhin nicht gezählt haben (E-S5W-09 b).
- [ ] erledigt am ______

### 12. Eine Kopplung mit dem Handbuch in der Hand  *(P2-Prüfpunkt 4.1, R55)*

Der Punkt, den der Rahmenplan seit P2 offen führt, und der einzige, der das
Handbuch selbst prüft.

- **Weg:** `docs/Handbuch.md` Abschnitt 12 **von oben nach unten**, mit einer
  frisch aufgesetzten Uhr und ohne Vorwissen. Danach 12.1 mit einem zweiten
  Konto.
- **Erwartet:** Jeder Schritt geht ohne Zusatzwissen. Jede Bezeichnung im
  Handbuch steht so auch auf der Uhr oder im Web — „Code für das Web",
  „Einstellungen, Geräte", „noch 9 min", „Mit ph\*\*\*@… koppeln?",
  „Gekoppelt", „Code vom Gerät".
- **Scheitern, und woran du es merkst:** Ein Text auf der Uhr weicht vom
  Handbuch ab, **besonders auf der Venu 3s** — sie hat andere Tasten, und der
  Tastenname steht in der Hinweiszeile, nicht im Handbuch. Ebenfalls
  Scheitern: Ein Schritt setzt etwas voraus, das erst weiter unten steht.
- **Notiere, welche Uhr** du benutzt hast; die Tastenwege unterscheiden sich,
  und ein Durchlauf auf einer Fenix beweist nichts für die Venu.
- [ ] erledigt am ______

### 13. Die Restzeile auf einer echten Venu 3s  *(Sichtprüfung)*

**Warum:** Der erste Wortlaut lag rechnerisch einen Pixel über der
Sicherheitslinie (1.1d); der gekürzte hat wieder Reserve. Am Simulator
belegt — ob es auf echter Hardware mit ihrem Gehäuserand ebenso aussieht,
ist damit nicht beantwortet.

**Bedienweg:** Auf einer Venu 3s koppeln, bis der Code steht.

**Erwartet:** „10 min gültig" steht vollständig da, mit sichtbarem
schwarzem Rand zu beiden Seiten.

**Scheitern erkennt man daran:** Die „1" am Anfang oder das „g" am Ende
berührt den Gehäuserand oder fehlt teilweise. Dann bleibt nur, die Zeile
weiter zu kürzen — eine kleinere Schrift gibt es dort nicht mehr.

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
  der Pakete A und B sind ausgetragen, seit D Hälfte 1 auch neun von D — ihre
  Stellen sind umgeschrieben.
- **Der Browserrundlauf:** ein Browser (Chromium), eine Breite, ein Konto,
  eine Sitzung. Er beweist, dass der Weg trägt — nicht, dass er unter zwei
  gleichzeitigen Bedienungen trägt (dafür steht die Regel in E-S5-13 und
  Kopplungsprobe Fall 9), und nicht, dass er ohne JavaScript trägt (Prüfliste
  6). Er läuft im Demo-Konto und meldet sein Prüfgerät am Ende ab; bricht er
  mittendrin ab, bleibt eines stehen. **Und er kann am Demo-Reset scheitern:**
  Fällt der in die Anmeldung, beendet `session_epoch + 1` seine gerade
  entstandene Sitzung, und er läuft in die erste Erwartung danach. Ein
  Fehlschlag an dieser Stelle ist zuerst gegen den Reset zu prüfen (Merkmal
  und SQL in `tools/kopplungsprobe/LIESMICH.md`), nicht gegen den Code.
- **Die Anker sind kein Prüfmittel** im Sinn von `CLAUDE.md` 6 (sagt ihre
  eigene `LIESMICH.md`). Ihre Zahl schrumpft mit jedem Paket, weil erledigte
  Stellen ausgetragen werden — **83 → 66**. Eine sinkende Zahl ist hier
  Fortschritt und nicht Verlust; wer sie als Deckungsgrad liest, liest sie
  falsch.
- **Die Wartungsprobe:** ein Aufrufer, nacheinander; sie legt den Schalter
  selbst um und ist deshalb auf einer Installation mit Betrieb nicht zu
  fahren. Drei ihrer Erwartungen (Fall 18) sind **am Code gelesen**, nicht
  gefahren — `login.php` ist ohne im Browser abgeleitetes Token nicht zu
  erreichen. Und den Deploy sieht sie gar nicht (1.11).
- **Der Bilderlauf:** misst Überlauf, Konsolenfehler und Knopfhöhen — nicht,
  ob die Seite richtig aussieht. Seine beiden neuen Bedienschritte holen sich
  eine echte Kopplungssitzung; sie kosten zwei der zwanzig `start`-Aufrufe,
  die der Ratenschutz je zehn Minuten zulässt.
