# Konzept PK — Prüfkette: jede Prüfung einmal, an ihrer Stelle

**Kürzel:** `PK`. Arbeitspakete `PK-01 …`, Meilensteine der Betreiberin
`PK-M1 …`, Entscheidungen `E-PK-01 …`, Befunde `F-PK-01 …`, Prüfpunkte
`P-PK-01 …`, Commit-Nachrichten beginnen mit dem Paket (`PK-03: …`).
**Rahmenplan:** R67 (Auslieferungskette), R84/E-KH-17 (kein Schritt
ungeprobt auf Produktiv — und kein Schritt nur für die Prüfung), E-KH-12
(Überspringen ist rot), R35 (Messstand), R62/K7 (Konzeptablage), R65
(Store-Verteilung, Signaturschlüssel), `CLAUDE.md` 6 und 7. **Backlog:**
Nr. 217, 219, 220, 227, 234, 263, 264 (verwiesen); neue Nummern vergibt die
einspielende Instanz. **Vorbereitung:** Konzeptauftrag vom 21.09.2026
(Umsetzungssitzung Kette II nach AP7/AP8a); Gespräch mit dem Auftraggeber am
selben Tag in drei Runden (Abschnitt 3.1); Durchsicht aller 48 Werkzeugordner
(65 Dateien, Abschnitt 3.3); Aufbauprobe der Sandbox mit zwei PHP-Fassungen,
vier Datenbanken und drei Browser-Engines (Abschnitt 1.3); Zugangsprobe
Staging und Webmail durch eine zweite Instanz (Abschnitt 1.4).
**Modell:** Konzept Fable, Umsetzung Opus. **Keine Fable-Schritte** in der
Umsetzung (keine Oberfläche, kein Mockup).
**Ablage:** `docs/konzepte/Konzept-PK-Pruefkette.md`; Prüfdokument daneben
(`Pruefdokument-PK-Pruefkette.md`, entsteht mit PK-01).
**Kein Versionssprung im Konzept (K3)** — Versionen vergibt die Umsetzung je
Paket nach `CLAUDE.md` 2. Pakete, die nur `tools/`, `docs/`, `.claude/` und
`.github/` anfassen, stufen nichts hoch.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **21.09.2026 — FREIGEGEBEN vom Auftraggeber (Z5), ohne Änderungen.** Z1 und Z2 erledigt; P-PK-02 vorgezogen und erledigt. Kein Paket begonnen — die Umsetzung startet mit PK-01 auf einem neuen Zweig. |
> | Entschieden | **E-PK-01 bis -30** — alle im Gespräch vom 21.09.2026 entschieden oder bestätigt (Abschnitt 3.1). Die offenen Fragen F-PK-1 bis -6 der ersten Fassung sind beantwortet (Abschnitt 3.2). |
> | Nächstes | **PK-01** (Umsetzungsinstanz, Opus); parallel bei der Betreiberin die Merges von PR #70 und #69, danach Tag `web-v20.26.3` = M1 der Kette II (Z2a). **Parallelität:** PK-01 bis PK-03 laufen neben Schritt 15; PK-04 bis PK-06 erst, wenn kein Schritt-15-Paket in einem offenen PR steht (Abschnitt 4.0). |
> | Kette II | wird nicht abgebrochen, sondern übergeben: Abschnitt 9 sagt, was bleibt, was PK übernimmt und was entfällt. |
> | Hakt | nichts mehr: Der HTTP 500 beim `.edbak`-Export auf Staging (`097D7622`) ist am 21.09.2026 **in der Sandbox reproduziert und behoben** (Web 20.26.3, PR #69, Nr. 267) — P-PK-02 vorgezogen, siehe 1.5. Offen ist die Bestätigung durch Stufe 2 nach dem Merge. |
>
> **Stand der Umsetzung**
>
> | Paket | Stand | Version | Commit | Abnahmezahlen |
> |---|---|---|---|---|
> | PK-M1 Zweigschutz und Merge-Recht | **gesetzt und gemessen 21.09.2026** (zwei Rulesets); Befund F-PK-01 | — | PR #67 (Messung), PR #70 (Nachmessung) | (b) abgewiesen; (a) durchgegangen unter der Identität der Betreiberin; (c) mit PR #70 |
> | PK-01 Regeldokumente | offen | | | |
> | PK-02 Sandbox-Setup | offen | | | |
> | PK-03 Prüfstand-Befehl | offen | | | |
> | PK-04 Werkzeuge zusammenlegen und bereinigen | offen | | | |
> | PK-05 Tor umbauen | offen | | | |
> | PK-06 Staging verschlanken | offen | | | |
> | PK-07 Abschluss | offen | | | |
> | PK-08 App-Auslieferung mit Signatur | offen (nach PK-07, eigene Freigabe) | | | |
> | PK-M2 Erster Durchlauf der neuen Kette | offen (Betreiberin) | | | |

---

## 0. Auftrag und Umfang

**Anlass.** Am 21.09.2026 brauchte Stufe 2 der Kette drei Läufe à 15
Minuten, um einen HTTP 500 beim `.edbak`-Export zu finden, dessen Ursache die
ganze Zeit im Zustandsfeld der Seite stand. Lokal wäre derselbe Export in
Sekunden gescheitert, mit demselben Text, und die Fehlerkennung läge im
Container statt in einem Webspace-Protokoll, an das niemand herankommt. Die
Frage des Auftraggebers dazu: Wäre es nicht schlauer, in der Sandbox solide
Prozesse und Schranken zu haben und nur getesteten Code einzuspielen, statt
die Prüfung GitHub zu überlassen?

**Die Frage ist größer als „lokal statt Kette".** Der Auftraggeber hat sie
im Gespräch erweitert: Ein Prüfkonzept über die ganze Strecke von der
Codeentwicklung bis Produktiv, in dem **jede sinnvolle Prüfung genau einmal
läuft**, die Stelle dokumentiert ist und jede Instanz weiß, wo, wann und wie.
Zweimal geprüft wird nur, was unbedingt noch einmal gecheckt werden muss.
Und: **Je einfacher, desto weniger fehleranfällig und bürokratisch.** Prüfung
und Prüfmittelpflege machen heute einen großen Teil aller Arbeit aus, die
Codeentwicklung steht dahinter zurück. Abschnitt 1.1 belegt das mit Zahlen.

**Die fünf Fragen des Auftrags** und wo sie beantwortet sind:

| Frage | Antwort |
|---|---|
| 1. Was gehört in eine lokale Prüfinstallation, wie wird sie reproduzierbar aufgesetzt? | Abschnitt 2.3, PK-02, PK-03 |
| 2. Was bleibt in Stufe 2, was misst sie noch, das lokal nicht messbar ist? | E-PK-01, E-PK-17, E-PK-29, PK-06 |
| 3. Wie wird aus „ich habe gemessen" ein Riegel? | E-PK-05, E-PK-06, Abschnitt 2.4 |
| 4. Welche Schranke verhindert ungetesteten Code? | E-PK-04, PK-M1 |
| 5. Was kostet es, was spart es? | Abschnitt 5 |

**Was dieses Konzept liefert:** Befund mit Zahlen, die Prüfkette in fünf
Stationen, dreißig Entscheidungen, eine Inventur aller 48 Werkzeuge mit
Urteil und Zielordner, acht Arbeitspakete und zwei Meilensteine, die zwei
neuen Regeldokumente `docs/Pruefablauf.md` und `docs/Sandbox-Setup.md`, die
Übergabe von Kette II.

**Was es nicht liefert:**

- **Keine Änderung unter `server/`** außer der Textbereinigung in PK-04
  (E-PK-26, -27). Alles andere liegt in `tools/`, `.github/`, `.claude/` und
  `docs/`. Der edbak-500 auf Staging ist ein Anwendungsfehler; wird er beim
  Reproduzieren gefunden, wird er als eigene Korrekturstufe behoben, nicht in
  einem PK-Paket versteckt.
- **Keinen Umbau der Auslieferung selbst** (`ausliefern-lauf.yml` bleibt in
  der Schrittfolge, wie Kette II sie gebaut hat). Verschlankt werden die
  Prüfschritte davor und danach, nicht der Transport.
- **Nicht die Aufteilung der Regeldokumente insgesamt** (`CLAUDE.md` als
  Überblick, `Technik.md` als Kompendium, weitere). Der Auftraggeber hat sie
  als eigenes Paket benannt; Abschnitt 8 hält den Rahmen fest, damit PK-01
  die beiden Prüfdokumente schon in dieser Form anlegt.
- **Nicht Nr. 234** (kein Prüfmittel fährt „Deploy, Anmeldung, `update.php`").
  Die Lücke bleibt benannt; PK-06 legt den Platz dafür in Stufe 2 an, füllt
  ihn aber nicht.

---

## 1. Befund (gemessen 21.09.2026, `main` `7123ced`)

### 1.1 Das Verhältnis in Zahlen

| Was | Zahl |
|---|---|
| Anwendungscode `server/` (PHP, JS, CSS ohne `vendor/`) | 100 333 Zeilen |
| Prüf- und Werkzeugcode `tools/` (py, php, mjs, sh) | 44 883 Zeilen |
| Anleitungen der Werkzeuge (`tools/*/LIESMICH.md`) | 7 206 Zeilen |
| Kette `.github/workflows/` | 2 830 Zeilen, davon **1 506 Kommentar** |
| Werkzeugordner | 48 |
| Werkzeuge mit Selbstprobe | 28 |
| Schritte Stufe 1 / Stufe 2 / Auslieferungslauf | 27 / 6 / 17 |
| Commits seit dem 14.09.2026 | 184, davon **118 nur `tools/`, `docs/`, `.github/`, `CLAUDE.md`** |

Zwei Drittel der Commits einer Woche haben keine Zeile Anwendung angefasst.
Das ist das Gefühl des Auftraggebers, nachgemessen.

### 1.2 Die Kette, aufgerollt

**Stufe 1 (`pruefung.yml`)**, jeder Push auf jedem Zweig, 177 Läufe.

| Gruppe | Schritte | Dauer | Läuft auch lokal? |
|---|---|---|---|
| Billige Riegel: PHP-Syntax, Python-Syntax, Wortliste, Vollständigkeit, Kontraste, Kettenaufrufe, vier Selbstproben, Backlog-Nummern, Handbuch rendert, Installweiche, Migrationsregister, CSP, Sitzungshärtung, Jobregister | 16 | rund 25 s | ja, jede Instanz fährt sie vor dem Commit |
| Nur in GitHub sinnvoll: Umgebungswert doppelt (braucht den Secrets-Kontext), Schemaprobe gegen MySQL 8.4.0 und MariaDB 10.6 (Dienstbehälter) | 3 | Sekunden bis 1 min | seit dem 21.09.2026 auch lokal (1.3) |
| Teuer: Android-Bau, Uhr Stufe I | 2 | 7 min plus 35 min | ja, mit nachgeladenen SDKs |

Gemessen an den letzten fünfzehn Läufen: auf dem Arbeitszweig rund 1 min
(Bereichserkennung überspringt Android und Uhr), **auf `main` und bei jedem
Pull Request 40 bis 51 min**, weil dort immer alles gemessen wird. Am
21.09.2026 noch einmal an PR #67 gemessen: **eine** geänderte Datei unter
`docs/`, und der Lauf des Pull-Request-Ereignisses baute Android (9 min) und
übersetzte die Uhr (35 min), während der Lauf des Push-Ereignisses auf
demselben Commit in 55 s fertig war. Der
Merge-Riegel wartet 40 min auf Code, der in 59 von 60 Commits nicht angefasst
wurde (Zahl aus `pruefung.yml` selbst).

**Staging-Deploy (`ausliefern-lauf.yml`)**, jeder Push auf `main`: 17
Schritte, gemessen 49 s. Bleibt.

**Stufe 2 (`auslieferung.yml`, Job `stufe2`)**, nach jedem Staging-Deploy:
6 Schritte, gemessen 16 min je Lauf, dazu je Lauf `pip install` und
`npm install playwright` samt Chromium-Download, weil der Läufer leer startet.

| Schritt | Misst | Braucht die echte Anlage? |
|---|---|---|
| Antwortet `login.php` als Anwendung | Zielpfad, Einrichtung | ja |
| Punktdateien 403, `.well-known` offen | Apache-`.htaccess` des Hosters | ja |
| Kreisläufe csv und edbak | Backup rein, Backup raus, Vergleich | nur als Plattformprobe (PHP 8.3, Hoster) |
| Bilderlauf, 62 Seiten in 8 Breiten | Überlauf, Konsolenfehler, Knopfhöhen, Karten | nein, das ist Markup und CSS |
| Messstand-Hinweis | nichts, ein Satz in der Zusammenfassung | — |

**Integritätswache (`integritaet.yml`)**: täglich und nach jeder
Auslieferung. Bleibt unverändert.

**Was in GitHub nicht existiert: der Zweigschutz auf `main`.** Gemessen am
16.09.2026 `protected: false`, Zuarbeit Z4 der Kette II ist offen. Damit ist
Stufe 1 bis heute eine Auskunft und kein Riegel (Rahmenplan 6b). **Das ist der
eine echte Riegel der ganzen Anordnung, und er ist nicht eingeschaltet** —
PK-M1 setzt ihn, vor der Freigabe dieses Konzepts.

### 1.3 Was die Sandbox kann — gemessen am 21.09.2026

Die erste Fassung dieses Abschnitts sagte „erreicht die Anlagen: nein". Das
war der Stand der Umsetzungssitzung von Kette II und ist falsch: Staging
und Webmail sind über HTTPS erreichbar. Was nicht erreichbar ist, steht
unten mit Namen.

| | Sandbox | Staging (lima-city) | Produktiv (Plesk) |
|---|---|---|---|
| PHP | **8.4.19** im Abbild; **8.3.33** nachbaubar, zwei Wege gemessen (unten) | 8.3.33 (fpm-fcgi) | 8.3.33 |
| Datenbank | **MariaDB 10.11.14** im Abbild; dazu nachgebaut **MariaDB 10.6.23**, **MySQL 8.0.46**, **MySQL 8.4.0** | **MySQL 8.4.10** (Statusseite, 21.09.2026) — eine andere Datenbank als Produktiv | MariaDB 10.11.14 |
| PHP-Grenzen | 512M, 300 s | 512M, 300 s, `post_max_size` 500M, OPcache „aus“ gemeldet (Nr. 266) | 512M, 240 s, 256M, OPcache aus |
| Node / Python | 22.22 / 3.11 | — | — |
| Browser | Chromium 141, Firefox 142, WebKit 26 (Playwright 1.56) — WebKit erst nach `aufbau.sh browser`, siehe 1.6 | — | — |
| Android-SDK | fehlt, nachladbar | — | — |
| Uhr-SDK | fehlt, nachladbar; `CIQ_GERAETE_URL` gesetzt | — | — |
| Docker | Daemon startet (29.3.1); Docker Hub seit dem 21.09.2026 freigegeben, **drosselt** (429 nach rund acht Abrufen) | — | — |
| Kerne / Speicher | 4 / 15 GB | — | — |
| Erreicht Staging, Webmail? | **ja**, über HTTPS (1.4) | — | — |
| Nicht erreichbar | `ppa.launchpadcontent.net`, `repo.mysql.com`, `php.net`; **nur Port 443** — kein IMAP, kein SMTP | | |

**Die Plattformmatrix lokal, mit Zahlen** (jede Datenbankzeile mit
`schemaprobe` gemessen: 19 Prüfungen, 0 Fehlschläge):

| Baustein | Weg | Dauer |
|---|---|---|
| PHP 8.3.33 als Docker-Abbild | `php:8.3.33-cli` plus Dockerfile mit fünf Erweiterungen (`pdo_mysql`, `mysqli`, `zip`, `intl`, `gd`); Debians Paketquellen auf `https://` umgestellt, weil der Proxy nur HTTPS durchlässt | Pull 10 s, Bau 43 s |
| PHP 8.3.33 aus dem Quelltext | `git clone --branch php-8.3.33` von GitHub, `make -j4` | 266 s |
| Anwendung unter PHP 8.3.33 | `lokal_einrichten.sh` mit `/opt/php83`; Anwendung im Docker-Container gegen die Host-Datenbank | 11 s; `login.php` 200, Fassung 20.26.2 |
| MariaDB 10.6.23 | Ubuntu-22.04-Pakete nach `/opt/mariadb106`, Port 3310 (Docker-Abbild am Drosseln gescheitert) | Laden und Start 30 s |
| MySQL 8.0.46 | Ubuntu-24.04-Pakete nach `/opt/mysql80`, Port 3307; ebenso als Docker-Abbild | Start 2 s bzw. 11 s |
| MySQL 8.4.0 | Docker-Abbild `mysql:8.4.0` — der einzige Weg zu dieser Fassung | Pull 10 s, bereit nach 6 s |
| drei Engines | `aufbau.sh browser` | 16 s |

Die Sandbox kann damit alles messen, was die GitHub-Matrix misst, und mehr:
MySQL 8.0 hat die Kette nie geprüft.

### 1.4 Zugang zu Staging und Webmail — belegt durch eine zweite Instanz

Eine zweite Instanz hat am 21.09.2026 in einer frischen Sitzung (mit den
berichtigten Umgebungswerten, unten) rein lesend geprüft: Anmeldung an
Staging mit Admin-Rechten, Startseite, Suche, Einstellungen, Statusseite
vollständig auslesbar, `jobs.php?aktion=zustand` antwortet, Roundcube-Webmail
anmeldbar, Postfach leer. Bilder liegen im Prüfbericht der Zugangsprobe.

Drei Dinge daraus gehören in `Sandbox-Setup.md`:

- **Sieben Umgebungswerte**, mit Namen: `NADOKU_STAGING_URL`, `_KONTO`,
  `_PASS`, `_JOBS_TOKEN`, `_MAIL_URL`, `_MAIL_USER`, `_MAIL_PASS` (die
  Mailwerte mit führendem Unterstrich). Am Vormittag des 21.09.2026 waren
  zwei davon falsch (Adresse der stillgelegten Anlage, Passwörter am `#`
  abgeschnitten), und kein Werkzeug hat es gemerkt. Die Passwörter stehen
  seither in Anführungszeichen; `aufbauen.sh` prüft künftig jeden Wert und
  nennt seine Länge, nie den Wert.
- **Chromium vertraut der Proxy-CA nicht.** Der saubere Weg (`certutil`) wird
  vom Auto-Modus als Sicherheitsschwächung abgelehnt. Der tragfähige Umweg
  leitet die Browser-Anfragen mit `page.route` durch den Node-Stack, der die
  CA kennt, ohne `ignoreHTTPSErrors`. Das wird eine Funktion in `motor.mjs`.
- **Der Dialog „Schlüsselblatt bestätigen"** erscheint alle drei Monate nach
  der Anmeldung. Jedes Werkzeug, das sich anmeldet, muss ihn mit „Später"
  schließen können.

Zwei Befunde der Anlage selbst, an die Betreiberin (Z4): Staging meldet den
Mailversand als fehlgeschlagen (eine Nachricht wartet, die Ursache steht im
Webspace-Protokoll), und drei Konten sind nie gesichert, kein Backup-Ziel
eingetragen.

### 1.5 Das Lehrstück

Der Kreislauf `edbak` gegen Staging kam am 21.09.2026 zum ersten Mal über
die Anmeldung hinaus (Lauf 35585472939): Konto angelegt, Backup eingespielt,
106 Einsätze. Dann sollte er das frische Konto sichern, und der Download kam
nicht. Nach 15 Minuten ein Satz: `Timeout 900000ms exceeded while waiting for
event "download"`. Drei Läufe, bis das Prüfmittel sprechend gemacht war und
den HTTP 500 mit Fehlerkennung `097D7622` nannte. Die Kennung liegt bis heute
nicht ausgelesen in einem Webspace-Protokoll.

**Was das Lehrstück belegt:** Nicht, dass die Kette falsch ist, sondern dass
sie der falsche Ort für die **Arbeit** ist. Fehler finden ist Arbeit; sie
gehört dorthin, wo ein Umlauf Sekunden kostet und das Protokoll auf der
Platte liegt. Die Kette ist der Ort für den **Riegel**: kurz, unabhängig,
und nur für das, was die Arbeit nicht selbst belegen kann. Der 500 selbst ist
ein Fehler der Anwendung und blockiert M1 der Kette II auch unter PK, weil
der edbak-Kreislauf als Plattformprobe bleibt. Ihn lokal zu reproduzieren war
als erster Prüfpunkt von PK-03 vorgesehen — und ist am 21.09.2026 in dieser
Konzeptsitzung vorgezogen worden, weil die Sandbox schon stand:

| Messung (PHP 8.3.33) | Ergebnis |
|---|---|
| Kreislauf edbak gegen MariaDB 10.11 | grün, 328 771 Vergleiche, 0 unerklärt — **reproduziert sich nicht** |
| dieselbe Anwendung auf einem MySQL-8.4.0-Container | **rot in Sekunden**, im lokalen Fehlerprotokoll `[F8F2D37A] backup: 1064 … near 'manual, origin, edited,'` |
| Ursache | `uhr_gesperrt AS manual` — Nr. 238 hatte die Spalte umbenannt, der bewahrte **Alias** ist auf MySQL 8.4.0–8.4.10 ebenso reserviert; Staging läuft auf MySQL 8.4.10 |
| Fix (Web 20.26.3, PR #69, Nr. 267) | zwei Backticks; danach MySQL 8.4.0 grün 328 771 / 0, MariaDB unverändert grün |

Drei Kreisläufe à vier Minuten in der Sandbox gegen drei Stufe-2-Läufe à
fünfzehn Minuten ohne Ursache. Das ist der Beleg für Abschnitt 2, bevor ein
Paket gebaut ist — und für die Plattformmatrix (E-PK-30): Der Fehler war auf
der Datenbank von Produktiv unsichtbar und auf der von Staging tödlich, und
keine der beiden Anlagen hatte je gesagt, dass sie verschieden sind.

### 1.6 Zwei Beschaffungswege mit zwei Listen — gemessen

`.claude/hooks/session-start.sh` und `tools/containeraufbau/aufbau.sh`
beschaffen beide, was der Container nicht mitbringt, mit **verschiedenen
Paketlisten**. Am 21.09.2026 nachgemessen: Im frischen Container starten nach
dem Hook Chromium und Firefox, **WebKit nicht**; erst `aufbau.sh browser` mit
seinen vier anderen Bibliotheken bringt 3 von 3. Welche Arbeitsmittel eine
Sitzung vorfindet, hängt davon ab, welches von beiden zuletzt jemand gepflegt
hat. Genau die Zufälligkeit, die der Auftraggeber abstellen will (2.3).

### 1.7 Woher die Doppelung kam, und was davon trägt

| Grund der Umsetzungsinstanz | Trägt er? |
|---|---|
| 1. Mehrere Instanzen parallel, die Betreiberin committet von Hand | Ja — aber das erfüllt ein Tor **beim Pull Request**, nicht ein Lauf bei jedem Push (E-PK-04). |
| 2. Ich messe den falschen Zeitpunkt (O9c) | Ja — genau das fängt ein Prüfbericht, der an den Baum-Hash gebunden ist (E-PK-05). |
| 3. Ich irre mich messbar oft | Ja — deshalb liest das Tor den Bericht gegen, statt ihm zu glauben; für die billigen Riegel kostet das 25 s. |
| 4. Meine Messung ist ein Bericht, die Kette ist ein Riegel | **Der tragende Grund**, und er gilt nur mit Zweigschutz — heute ist er nicht gesetzt (1.2). |

Keiner der vier Gründe verlangt, dass teure Messungen (Android, Uhr,
Bilderlauf, Kreisläufe) in der Kette laufen.

---

## 2. Die Prüfkette

### 2.1 Grundsatz

1. **Jede Prüfung hat genau eine Station.** Die Station steht in
   `docs/Pruefablauf.md`, nicht in einem Kommentar, nicht im Gedächtnis.
2. **Arbeit in der Sandbox, Riegel in der Kette.** Was Minuten kostet und
   Fehler *findet*, läuft lokal. Was Sekunden kostet und Fehler *aufhält*,
   läuft im Tor. Die Anlage misst nur, was nur die Anlage zeigen kann.
3. **Zweimal nur die Gegenlesung.** Das Tor wiederholt die billigen Riegel
   und hält sie gegen den Prüfbericht. Sonst wird nichts zweimal gemessen.
4. **Der Umfang folgt der Änderung, nicht dem Kalender.** Eine
   Korrekturstufe prüft, was sie berührt; eine Nebenstufe alles, was ohne
   Anlage geht; eine Hauptstufe auch Mengen und Plattformmatrix (2.2).
5. **Ein Prüfmittel braucht einen Fehler.** Jedes Werkzeug nennt in einer
   Zeile, welchen Fehler es gefangen hat oder hätte fangen müssen, als
   Verweis auf eine Nummer. Ohne diese Zeile steht es auf der Streichliste.
6. **Geschichte steht im Commit, nicht im Werkzeug** (E-PK-25). Eine
   Anleitung sagt, was das Werkzeug tut; was es einmal gefunden hat, sagt die
   Commit-Nachricht, das Backlog und der Changelog.
7. **Kein stilles Überspringen, keine grüne Zahl ohne Gegenstand** — das
   bleibt aus `CLAUDE.md` 6 und E-KH-12 unverändert.

### 2.2 Fünf Stationen

| Station | Wann | Wer | Was dort geprüft wird | Dauer (Ziel) |
|---|---|---|---|---|
| **A Arbeit** | während der Entwicklung | die Instanz im Container | Browser, Emulator, Simulator, die Proben, die zur Änderung gehören | nach Bedarf |
| **B Prüfstand** | vor dem PR, **ein Befehl** | dieselbe Instanz | lokale Installation hochfahren, Bestand, die Proben der Stufe, Android- und Uhr-Bau (unsigniert), die billigen Riegel; auf Anforderung die Plattformprobe **gegen Staging**. Ergebnis ist der **Prüfbericht** in der Commit-Nachricht | klein 5 min, neben 15 min, haupt 45 min |
| **C Tor** | beim Pull Request und auf `main` | GitHub | die billigen Riegel als Gegenlesung des Berichts, Schemaprobe gegen MySQL 8.4.0 und MariaDB 10.6, Bericht passt zum Baum und zur Berührung. **Rot heißt kein Merge**; der Merge ist Sache der Betreiberin | 1 bis 2 min |
| **D Staging** | nach dem Merge | GitHub | Auslieferung (unverändert), dann drei Schritte: Antwortprobe, Punktdateien, **ein** edbak-Kreislauf als Gegenlesung auf PHP 8.3 beim Hoster | unter 10 min |
| **E Produktiv** | beim Tag, nach Freigabe | GitHub | dieselbe Auslieferung, Backup-Tor, Versionsprüfung, Wache; **App-Tags** bauen und signieren einmal (E-PK-23) | wie heute |

**Die drei Stufen des Prüfstands** richten sich nach `server/version.php`
im Diff gegen `main` (Zählweise aus `CLAUDE.md` 2) und nach der Berührung:

| Stufe | Auslöser | Umfang |
|---|---|---|
| **klein** | Korrekturstufe `a.a.Y`, oder kein Versionssprung | billige Riegel; je berührter Datei die zugeordneten Proben (`pruefablauf.json`); Bilderlauf der berührten Seiten in drei Breiten, Chromium; Bedienprobe der berührten Seiten |
| **neben** | Nebenstufe `a.X.a` | alles ohne Anlage: alle Proben gegen die lokale Installation, beide Kreisläufe, Bilderlauf aller Seiten in acht Breiten, Chromium, Bedienprobe aller Seiten; Android und Uhr, wenn berührt |
| **haupt** | Hauptstufe `X.a.a`, oder `--stufe haupt` | wie neben, dazu **Plattformmatrix** (PHP 8.3.33 und 8.4; MariaDB 10.11 und 10.6, MySQL 8.0 und 8.4.0 — Schemaprobe und ein edbak-Kreislauf je Paar), Bilderlauf mit allen drei Engines über alle Seiten, Messstand, Anteilprobe, Verbindungsprobe, Uhr Stufe II |

Der Befehl bestimmt die Stufe selbst und sagt sie; `--stufe` überschreibt und
steht im Bericht. Eine Hauptstufe mit `--stufe klein` fällt im Tor auf
(E-PK-06).

### 2.3 Die Sandbox: vier Konfigurationen und ein Modul

`docs/Sandbox-Setup.md` beschreibt fest definierte Ausbaustufen; die
Beschaffung `tools/sandbox/aufbauen.sh <konfiguration>` stellt sie her und
misst nach, was steht. Der SessionStart-Hook ruft dieselbe Beschaffung mit
`web`; wer mehr braucht, ruft nach.

| Konfiguration | Enthält | Wofür |
|---|---|---|
| `web` | MariaDB 10.11, PHP 8.4, PHP-Server mit TLS, **drei** Browser-Engines (nachgemessen, nicht angenommen), Python-Pakete, Referenzbestand, die sieben Staging-Werte geprüft | jede Änderung unter `server/`, `docs/`, `tools/` |
| `android` | `web` plus Android-SDK 36, JDK 21, Emulator-Abbild | Änderungen unter `android/` |
| `uhr` | `web` plus Connect-IQ-SDK, Gerätedateien, Simulator-Bibliotheken | Änderungen unter `watch/` |
| `alles` | alle drei plus das Modul `plattform` | Hauptstufe, Abnahmen |
| Modul `plattform` | PHP 8.3.33 (Docker-Abbild, Ausweich Quelltextbau), MariaDB 10.6 und MySQL 8.0 (Ubuntu-Pakete unter `/opt`), MySQL 8.4.0 (Docker) | Stufe haupt; einzeln nachladbar |

Die Beschaffung ist **eine** Datei; `session-start.sh` und
`containeraufbau/aufbau.sh` gehen darin auf (1.6). Sie endet mit einer
Nachweistabelle (Fassung je Stück, drei Engines einzeln, sieben Werte mit
Länge), die der Prüfbericht mitführt.

**Benannte Grenzen der Sandbox**, die `Sandbox-Setup.md` vorn trägt: nur
Port 443 nach außen (kein IMAP, kein SMTP — Mail nur über Roundcube), Docker
Hub gedrosselt (deshalb Ubuntu-Pakete, wo es geht, und Docker nur für MySQL
8.4.0 und PHP 8.3), Debian-Quellen im PHP-Abbild müssen auf HTTPS stehen,
Chromium und die Proxy-CA (1.4), kein echter Data Layer, kein echtes Gerät,
kein Hoster-Apache (Station D misst `.htaccess`).

### 2.4 Der Prüfbericht — vom Bericht zum Riegel

Der Prüfstand-Befehl schreibt am Ende einen Block, der in die
Commit-Nachricht gehört und maschinell lesbar ist:

```
Prüfstand: neben · Baum a1b2c3d… · Konfiguration web
  syntax=php:511/0,py:48/0  textprobe=0  quelltext=6/0
  kreislauf=edbak:0,csv:0  bilderlauf=62/0/0/0  proben=ingest:10/10,spur:5/5
  android=nicht berührt  uhr=nicht berührt
```

Das Tor liest ihn und wird rot, wenn

- der **Baum-Hash** nicht der Baum des Commits ist (gemessen vor der letzten
  Änderung — der O9c-Fehler),
- die **Stufe** kleiner ist als die Versionsstufe im Diff verlangt,
- eine **berührte** Fläche als „nicht berührt" gemeldet ist,
- ein **billiger Riegel** im Tor eine andere Zahl liefert als der Bericht.

Was das Tor **nicht** kann: einen teuren Lauf nachrechnen. Für Kreisläufe,
Bilderlauf, Android und Uhr ist der Bericht ein Nachweis, kein Riegel. Die
Sandbox ist die geprüfte Partei, und ein Nachweis aus ihrer Hand bleibt einer
aus ihrer Hand. Der Auftraggeber hat das als ausreichend bestätigt
(E-PK-05). Zwei Dinge halten die Grenze klein: Der Block wird vom Befehl
**erzeugt**, nicht geschrieben; und der Merge bleibt ein Mensch, der den
Block liest.

`tools/pruefstand/pruefablauf.json` ist die eine Zuordnung
**Berührung → Probe** (Muster auf Dateipfade, je Muster die Werkzeuge und die
Stufe, ab der sie laufen). `docs/Pruefablauf.md` wird daraus **erzeugt**, wie
die Tabellen in `Design.md`.

---

## 3. Entscheidungen

### 3.1 Aus dem Gespräch vom 21.09.2026 — entschieden

**E-PK-01 — Stufe 2 wird auf das reduziert, was nur die echte Anlage zeigt.**
Antwortprobe, Punktdateien, ein edbak-Kreislauf. Bilderlauf und csv-Kreislauf
wandern in den Prüfstand. Ganz fallen darf Stufe 2 nicht: der Hoster-Apache
und Nr. 234 haben keinen anderen Ort, und die Gegenlesung nach dem Merge ist
der eine Fall, den Grundsatz 3 zulässt.

**E-PK-02 — Android und Uhr werden in der Sandbox gebaut**, unsigniert, in
den Konfigurationen `android` und `uhr` (Station B). Der Bericht hält den Bau
fest.

**E-PK-03 — Streichliste mit Begründung; gestrichen wird wirklich.** Die
Git-Historie behält, was war.

**E-PK-04 — Stufe 1 läuft beim Pull Request und auf `main`, sonst nirgends.
Pushes sind Pushes.** Kein Riegel vor dem Push, kein Hook. Der Merge nach
`main` ist ausschließlich Sache der Betreiberin: Zweigschutz mit
PR-Pflicht, Pflichtprüfung `Stufe 1` und Push- und Merge-Recht nur für sie
(PK-M1). **Gemessen am 21.09.2026 (F-PK-01):** Das Ruleset hält gegen das
Konto `claude` (Push abgewiesen), aber die GitHub-Werkzeuge von Claude Code
handeln unter der Identität der Betreiberin — ein Merge über
`merge_pull_request` ging durch. Deshalb drei Lagen: das Ruleset (Riegel
gegen Git und Apps), die **Deny-Liste in `.claude/settings.json`** für
`mcp__github__merge_pull_request` und `mcp__github__enable_pr_auto_merge`
(Riegel im Harness, PK-01, P-PK-03), und der Satz in `CLAUDE.md` 8, dass eine
Instanz nie mergt.

**E-PK-05 — Der Prüfbericht in der Commit-Nachricht reicht als Nachweis.**
Erzeugt vom Befehl, gebunden an den Baum-Hash, vom Tor gegengelesen (2.4).

**E-PK-06 — Das Tor ist die Gegenlesung, nicht die Wiederholung.**

**E-PK-07 — Die Kommentarprosa der Kette wird gelöscht.** 1 506 von 2 830
Zeilen. Sie wandert nicht nach `Technik.md`, sie fällt. Es bleibt **ein
Satz** an jeder Stelle, die eine Falle beschreibt, die sonst jemand wieder
einbaut (`pipefail`, `A && B || C`, Punktdatei, Jobname als Kupplung).

**E-PK-08 — Die Wortliste wird herabgestuft und zur Textprobe erweitert.**
Einmal, im Tor, in Sekunden; rot ist nur ein neuer Treffer, eine ungenutzte
Ausnahme eine Warnung. Aus `CLAUDE.md` fällt die Pflicht, sie bei jeder
Textänderung von Hand zu fahren. Fünf Regelklassen: Luftbegriffe (die 99
Ausnahmen wandern mit), **Hausform Binnen-I** (E-PK-26), **E-Mail-Adressen**
(E-PK-27), Adressen im Netz (Erlaubnisliste aus `Lizenzen.md`), Namen und
Rufnamen (Liste aus `referenzdatensatz/quelldaten/pruefen.py`). Werkzeug:
eine Regeldatei, ein Bericht, Teil von `tools/quelltext/` (E-PK-24).

**E-PK-09 — Benennung.** Kürzel je Konzept, `PK-NN Schlagwort` für Pakete,
`PK-MN` für Meilensteine, `E-/F-/P-PK-NN`; Commit-Nachrichten beginnen mit
dem Paket. Nummern zweistellig, nie wiederverwendet. `CLAUDE.md` 7 übernimmt
die Regel in PK-01.

**E-PK-10 — Zwei neue Regeldokumente:** `docs/Pruefablauf.md` (erzeugt aus
`pruefablauf.json`) und `docs/Sandbox-Setup.md`. `CLAUDE.md` 6 wird auf die
sieben Grundsätze und den Verweis gekürzt.

**E-PK-11 — Der Prüfumfang folgt der Versionsstufe** (2.2).

**E-PK-12 — Vier Konfigurationen und das Modul `plattform`** (2.3), eine
Beschaffung, ein Nachweis.

**E-PK-13 — Die Kette bei GitHub baut Android und Uhr im Pull Request
nicht.** Die Sandbox baut in Station B (E-PK-02) und der Bericht meldet es;
ist `android/` oder `watch/` berührt und der Bericht sagt „nicht berührt"
oder fehlt, ist das Tor rot, in Sekunden statt in 42 Minuten. Ein weiterer
Bau im PR wiederholte nur, was der Bericht schon sagt. Preis, benannt: Ein
Bericht, der einen Bau behauptet, den es nicht gab, kommt durch; der Merge
ist ein Mensch. *Die erste Fassung begründete das mit „die Betreiberin baut
und signiert auf ihrem Rechner" — das war falsch gelesen aus E-S4-16, dort
steht nur, dass der Schlüssel nicht im Repositorium liegt.* Die
**Auslieferung** einer App ist etwas anderes als der Bau im PR: E-PK-23.

**E-PK-14 — Der Bilderlauf bleibt, abgestuft, ohne Risikoliste.** klein =
berührte Seiten in drei Breiten (360, 1024, 1920), Chromium; neben = alle
Seiten, acht Breiten, Chromium; **haupt = alle Seiten, acht Breiten, alle
drei Engines**. Die Risikoliste (zehn Seiten mit Container-Abfragen, `:has()`,
`dvh`, `sticky`, `dialog`) und der Schalter `--risiko` fallen: eine von Hand
gepflegte Liste altert in eine Richtung, und der einzige WebKit-Fund (Nr. 185)
lag auf einer Seite, die nicht darauf stand. Dreißig Minuten bei einer
Hauptstufe sind kein Preis, der eine Liste rechtfertigt. Die Gegenprobe gegen
doppelte Bilder (`md5sum`) wird Teil des Laufs.

**E-PK-15 — Die Klickprobe bleibt und wird zur Bedienprobe umgebaut**, nicht
gestrichen. Sie ist das einzige Werkzeug, das Elemente bedient (Anlass PS-2:
bei gehaltener Maus kam der `click` nie). Umbau: die 48 Wege werden **nach
Seiten** geordnet statt nach Arbeitspaketen (heute zehn Dateien `ap1…`,
`p5a-ap8…`), damit `pruefablauf.json` „diese Datei berührt, also diese Wege"
sagen kann; Chromium, eine Breite je Eingabeart; Stufe klein nur berührte
Seiten; der Demo-Reset wird vor dem Lauf über `demo_kennzeichnen.php` in die
Zukunft gesetzt.

**E-PK-16 — Die Vollständigkeitsprüfung verliert die Symbolzählung.** Von 319
Befunden sind 299 Satzzeichen in Prosa (Nr. 227); die Schwelle „genau 398"
mit ihrem 70-Zeilen-Kommentar fällt. Die vier sauberen Prüfungen (Klasse ohne
Regel, Wert außerhalb `:root`, `style=`-Attribut, fremde Quelle) und 4b
laufen im Tor gegen **null**. Die 20 echten Symbole werden in PK-04 einmal
ersetzt oder als Rest ins Backlog geschrieben; Nr. 227 ist damit erledigt.

**E-PK-17 — Stufe 2 fährt genau drei Schritte:** Antwortprobe, Punktdateien,
edbak-Kreislauf (`--frisch`, mit Job-Pause). csv-Kreislauf und
Messstand-Hinweis fallen. Zeitgrenze des Jobs 20 min. Playwright und
`cryptography` aus dem Aktions-Cache, wo die Bauform es hergibt.

**E-PK-18 — Stufe 1 bekommt zwei Riegel dazu, die heute fehlen:** die
Linkprobe (Bruchteil einer Sekunde, fand Nr. 148 und 151, hängt nirgends) und
die Rechtstext-Angriffsprobe (reine Funktion, 81 Fälle, Sekunden).

**E-PK-19 — Das Tor liest den Bericht mit einem Werkzeug, nicht mit Bash**
(`bericht.py lesen`, mit Selbstprobe), aus dem Grund von E-P5a-12.

**E-PK-20 — `pruefung.yml` behält die Bereichserkennung nicht.** Mit E-PK-13
gibt es nichts mehr zu überspringen. „Fassungen nennen" bleibt.

**E-PK-21 — Stufe 2 bekommt einen benannten leeren Platz für Nr. 234.**

**E-PK-22 — Die Selbstprobe-Pflicht gilt nur für Werkzeuge, die einen Merge
oder eine Auslieferung verhindern** (Tor, Freigabe, Zielprobe, Zustand,
Bericht, Schemaprobe, `quelltext`). Proben in Station B brauchen keine.

**E-PK-23 — Die App-Auslieferung signiert bei GitHub, hinter der
Pflichtfreigabe, nicht in der Sandbox.** Ein Tag `android-vX.Y.Z` oder
`uhr-vX.Y.Z` löst auf Station E einen Lauf in der Umgebung `produktion` aus,
der einmal baut, signiert und ablegt (APK nach `server/apk/` über einen
eigenen Schritt, weil `apk/` in der Schutzliste des Abgleichs steht; das
Uhr-Paket als Artefakt). Die Schlüssel (Android-Upload-Schlüssel für Play App
Signing nach R65, Connect-IQ-Entwicklerschlüssel) liegen als Geheimnisse der
Umgebung `produktion`. **Warum nicht in der Sandbox:** Dort läuft bei jedem
`gradlew build` fremder Code mit Zugriff auf alle Umgebungswerte; ein
Schlüssel, dessen Verlust jede spätere Fassung zu einer anderen App macht,
gehört nicht dorthin, wo viel läuft — dieselbe Regel, nach der die Uhr keine
Zugangsdaten kennt (`CLAUDE.md` 4). Bei GitHub steht ein Mensch zwischen
Bau und Auslieferung, der Läufer ist frisch, das Protokoll nennt Lauf,
Commit und Freigebende. Einmal je Fassung, nicht je PR — deshalb kein
Widerspruch zu E-PK-13. Der Handgriff der Betreiberin schrumpft auf Tag und
Klick; Nr. 100 (Play-Upload per API) hängt sich später an denselben Lauf.
Paket: PK-08.

**E-PK-24 — Drei Zusammenlegungen statt 48 Ordner.** Der Wildwuchs steckt
weniger in den Messungen als in 48 Anleitungen, 48 Aufrufkonventionen und 28
handgebauten Selbstproben, die alle dasselbe Muster nachbauen.

| Neu | Geht auf in | Warum |
|---|---|---|
| `tools/quelltext/` | installweiche, sitzungshaertung, cspprobe (`pruefen.php`), jobregister, migrationsregister, linkprobe, vollstaendigkeit, textprobe (aus wortliste) | lesen Quelltext, brauchen nichts, laufen im Tor; ein Läufer, eine Selbstprobe, eine Regeltabelle |
| `tools/proben/` | ingest, spur, jobs, kopplung, wartung, raten, mail, versand, komplett, wiederherstellung, gpx, geraete, verbindung, anteil, freigabe, container, frist (mit abmelde), rechtstexte, cspprobe (`browserprobe.mjs`) | messen eine Bibliothek oder einen Endpunkt gegen die lokale Installation; gemeinsamer Rahmen, ein Aufruf `proben.php <name>`, `pruefablauf.json` zeigt auf Namen |
| `tools/erzeugen/` | design, logos, uhr-bilder, geraetemodelle, wegwerfdomains, pruefkonten | Erzeuger, keine Prüfmittel; ein Ordner mit Unterbefehlen |

Eigene Ordner bleiben: `referenzdatensatz`, `screenshots`, `bedienprobe`,
`stilvergleich`, `messstand`, `kette`, `kettenaufrufe`, `integritaetswache`,
`schemaprobe`, `uhr-pruefstand` (mit netzprobe und eingabe-probe), `sandbox`,
`pruefstand`. **15 Ordner statt 48**; die Messungen darunter bleiben
dieselben. Preis: PK-04 wird das größte Paket. Es ist die Investition, die
den Wildwuchs beendet; ohne sie bliebe es beim Streichen von zweien.

**E-PK-25 — Funde stehen in Commits, nicht in Werkzeugen.** Eine `LIESMICH.md`
je Werkzeug hat fünf feste Abschnitte und höchstens 40 Zeilen: Aufruf, was es
misst, was es braucht, erwartete Zahl, was es nicht kann; dazu eine Zeile
„Anlass: Nr. …" als Verweis. Funde, Fehlanläufe und Zahlengeschichte stehen in
der Commit-Nachricht des Pakets, im Backlog als Nummer und im Changelog. Gilt
ebenso für die Kettenkommentare (E-PK-07).

**E-PK-26 — Hausform ist das große Binnen-I:** `NutzerInnen`, `PolizistInnen`,
im Singular `NutzerIn`. Die Textprobe prüft gegen eine Liste von Rollenwörtern
in männlicher und generisch-weiblicher Form. Gemessen am 21.09.2026: Die
Texte mischen heute beide Formen (145 `NutzerIn`, 126 `BetreiberIn` neben
78 `Betreiberin`, 46 `Nutzerin`, 24 `Administratorin`) — ein Altbestand von
rund 190 Stellen, der in PK-04 **einmal** bereinigt wird; danach null, keine
Schwelle.

**E-PK-27 — Eine echte Adresse, sonst keine.** `demo@gen-em.org` ist die
einzige echte E-Mail-Adresse, die in Texten, Werkzeugen und Doku stehen darf;
sie wird im Handbuch festgeschrieben und ist im Adminbereich einstellbar.
`admin@gen-em.org` kommt nirgends mehr vor (gemessen: 39 Vorkommen in 16
Dateien); die Prüfkonten der Werkzeuge (`umlauf-csv@`, `umlauf-edbak@`,
`messstand@`, `ingestprobe@`, `kopplungsprobe@`, `verbindungsprobe@` und
weitere unter `gen-em.org`) wandern nach `example.invalid`. Umgebungswerte
(`staging@gen-em.org`) sind Konfiguration, kein Text, und fallen nicht
darunter. Regel der Textprobe, Bereinigung in PK-04.

**E-PK-28 — Der Stilvergleich bleibt**, gebunden an Änderungen von
`server/assets/style.css` in `pruefablauf.json`, nicht als Pflicht in
`CLAUDE.md` und nicht in der Kette. Er beantwortet die eine Frage, die der
Bilderlauf nicht beantwortet — hat sich ein berechneter Stil geändert, der
nicht sollte —, kostet 16 Sekunden und keine Pflege.

**E-PK-29 — Station B darf gegen Staging messen.** Die Sandbox erreicht
Staging, Webmail und `jobs.php` (1.4). Die Plattformprobe (edbak-Kreislauf
gegen Staging, Antwortprobe, Punktdateien) kann der Prüfstand mit
`--gegen staging` fahren, wenn eine Instanz sie braucht, etwa zur Fehlersuche
wie beim edbak-500. Stufe 2 bei GitHub bleibt als **Gegenlesung nach dem
Merge** mit denselben drei Schritten (E-PK-17): Sie ist der eine Riegel, der
nicht aus der Hand der geprüften Partei kommt. Mailversand und
Schreibvorgänge auf Staging aus der Sandbox nur auf ausdrückliche Anweisung.

**E-PK-30 — Plattformmatrix lokal, mit benannten Quellen.** MySQL 8.4.0 nur
als Docker-Abbild; MariaDB 10.6 und MySQL 8.0 als Ubuntu-Pakete unter `/opt`
(Docker Hub drosselt anonyme Abrufe, Ubuntu-Quellen nicht); PHP 8.3.33 als
Docker-Abbild mit Dockerfile (43 s), Ausweich Quelltextbau von GitHub
(266 s). Zahlen in 1.3. Ein Docker-Hub-Konto als Umgebungswert ist möglich,
wird aber nicht vorausgesetzt.

### 3.2 Beantwortete Fragen der ersten Fassung

| Nr. | Frage | Antwort des Auftraggebers (21.09.2026) |
|---|---|---|
| F-PK-1 | E-PK-13 bis -22 so entscheiden? | ja, wie besprochen (E-PK-13 berichtigt, -14 ohne Risikoliste, -15 als Umbau) |
| F-PK-2 | Android und Uhr im Tor? | ganz raus; Signatur bei GitHub hinter der Freigabe (E-PK-23) |
| F-PK-3 | Streichliste und Zusammenlegungen? | ja; dazu die drei Zusammenlegungen (E-PK-24) |
| F-PK-4 | Hausform? | Binnen-I (E-PK-26) |
| F-PK-5 | Prüfkonten unter `gen-em.org`? | nur `demo@gen-em.org`, alles andere `example.invalid` (E-PK-27) |
| F-PK-6 | Stilvergleich? | behalten (E-PK-28) |

### 3.3 Inventur — 48 Werkzeuge, ein Urteil je Werkzeug

Grundlage: die Durchsicht aller Anleitungen am 21.09.2026 (65 Dateien).
Urteile: **bleibt** (eigener Ordner), **→ quelltext / proben / erzeugen**
(geht in den Sammelordner, E-PK-24), **verschlanken**, **aufgehen in**,
**streichen**. Die Spalte „Anlass" ist die Zeile aus Grundsatz 5.

| Werkzeug | Urteil | Station | Anlass (Nummer oder Fund) |
|---|---|---|---|
| abmelde-probe | aufgehen in frist (→ proben) | B klein bei Sitzungs-JS | Nr. 22 |
| anteilprobe | → proben | B haupt; klein bei `kdf`/`unlock`/`serverkrypto` | S10-Kern, F-S10-AP3-03 |
| containeraufbau | aufgehen in `sandbox` | A | Wegwerf-Container ohne MariaDB (13.09.) |
| containerprobe | → proben | B klein bei `spur_lib`, `crypto.js`, Backup-Format | S2/AP5 |
| cspprobe | `pruefen.php` → quelltext; `browserprobe.mjs` → proben | C; B klein bei `kopf` | 15.09.: Meldeweg tot |
| design | → erzeugen | A | F-P3-BC |
| eingabe-probe | aufgehen in uhr-pruefstand (Handwerkzeug) | A | Geräte-Eingabe.md |
| freigabeprobe | → proben | B klein bei `freigabe`/`schluessel` | F-S2-F |
| fristprobe | → proben (mit abmelde) | B klein bei `keyguard` | R44 |
| geraetemodelle | → erzeugen | A | Teilenummer an zwei Geräten |
| geraeteprobe | → proben | B klein bei `pair`/`geraete` | Edge, das sich „uhr" nennt |
| gpxprobe | → proben | B klein bei `gpx`/`export` | Nr. 130 |
| ingestprobe | → proben | B klein bei `ingest`/`validate` | stiller Datenverlust bei „ok" |
| installweiche | → quelltext | C | PP-1 |
| integritaetswache | bleibt | E | B6 |
| jobprobe | → proben | B klein bei `jobs_lib` | Huckepack 18 s |
| jobregister | → quelltext | C | Nr. 208 |
| kette | bleibt | D, E | F3, Nr. 219 |
| kettenaufrufe | bleibt (liest auch `pruefablauf.json`) | C | Nr. 217 |
| klickprobe | verschlanken → `bedienprobe` (E-PK-15) | B neben; klein berührte Seiten | PS-2 |
| komplettprobe | → proben | B klein bei `komplett_lib` | `count(null)`, F-S10-AP4-02 |
| kopplungsprobe | → proben | B klein bei `pair` | Nr. 178, 180 |
| linkprobe | → quelltext (neu im Tor, E-PK-18) | C | Nr. 148, 151 |
| logos | → erzeugen | A | ENOENT nach NEF-Austausch |
| mailprobe | → proben | B klein bei `mail_lib`/`smtp` | `smtp_letzter_fehler()` |
| maskierungs-probe | **streichen** | — | F-20; Fall liegt im Referenzdatensatz (R20) |
| messstand | bleibt | B haupt | F-S2-E |
| migrationsregister | → quelltext | C | Hausregel dreimal vergessen |
| netzprobe | aufgehen in uhr-pruefstand (Anleitung) | A | −1001 über HTTP |
| pruefkonten | → erzeugen | A | O9-Abnahme |
| ratenprobe | → proben | B klein bei `ratelimit` | Stufe fiel nie zurück |
| rechtstexte | → proben (Sekunden, auch im Tor möglich) | C | P3/O10 |
| referenzdatensatz | bleibt (Kern) | B | F-P1-I, Nr. 174 |
| s5-anker | **streichen** | — | „hat seine Arbeit getan" |
| schemaprobe | bleibt | C und B haupt | Nr. 238 |
| screenshots | verschlanken (E-PK-14) | B abgestuft | Nr. 185, 225 |
| sitzungshaertung | → quelltext | C | Nr. 205 |
| spurprobe | → proben | B klein bei `spur_lib` | `int` gegen `float` |
| stilvergleich | bleibt (E-PK-28) | B bei `style.css` | P0/A3 |
| uhr-bilder | → erzeugen | A | 42 von 99 Geräten skaliert |
| uhr-pruefstand | bleibt (+ netzprobe, eingabe-probe) | A, B `uhr` | `Devices/Devices/` |
| verbindungsprobe | → proben | B haupt; klein bei `db.php` | Nr. 210 |
| versandprobe | → proben | B klein bei `sicherungsziel` | halb englische Meldungen |
| vollstaendigkeit | verschlanken (E-PK-16) → quelltext | C | F-P3-BA, Nr. 179 |
| wartungsprobe | → proben | B klein bei `wartung`/`auth_guard` | F-S8-P-04, Nr. 171 |
| wegwerfdomains | → erzeugen (bekommt seine Anleitung) | A | Nr. 230 |
| wiederherstellungs-probe | → proben | B klein bei `backup_lib` | Nr. 31, 33, 34, 35 |
| wortliste | verschlanken → textprobe (E-PK-08) → quelltext | C | B-S4-06, 14 Treffer Rechtstexte |
| `motor.mjs` | bleibt (bekommt die Proxy-Route, 1.4) | Baustein | Nr. 183 |

**Ergebnis:** 15 Ordner. Gestrichen: 2. Aufgegangen: 4. Verschlankt: 4. Neu:
`sandbox`, `pruefstand`, `quelltext`, `proben`, `erzeugen`, `bedienprobe`
(umbenannt). Die Messungen darunter bleiben bis auf die Symbolzählung und die
Risikoliste dieselben.

---

## 4. Arbeitspakete

### 4.0 Übersicht, Reihenfolge, Abhängigkeiten

| Paket | Titel | Entscheidungen | nach | berührt | Stopp |
|---|---|---|---|---|---|
| **PK-M1** | Zweigschutz und Merge-Recht (Betreiberin) | E-PK-04 | — (vor der Freigabe) | GitHub-Einstellungen | — |
| PK-01 | Regeldokumente | E-PK-09, -10, -25 | Freigabe | `docs/Pruefablauf.md`, `docs/Sandbox-Setup.md`, `CLAUDE.md` 6/7, Prüfdokument | — |
| PK-02 | Sandbox-Setup | E-PK-12, -30 | PK-01 | `tools/sandbox/`, `.claude/hooks/`, `tools/containeraufbau/` (weg), `motor.mjs` | — |
| PK-03 | Prüfstand-Befehl | E-PK-05, -06, -11, -19, -29 | PK-02 | `tools/pruefstand/` | erster Prüfpunkt: edbak-500 lokal reproduzieren |
| PK-04 | Werkzeuge zusammenlegen und bereinigen | E-PK-03, -08, -14, -15, -16, -22, -24, -25, -26, -27, -28 | PK-03 | `tools/` (drei Sammelordner, Streichliste, Textprobe, Bedienprobe, Bilderlauf); Binnen-I- und Adressbereinigung in `server/`-Texten und `docs/` | Binnen-I und Adressen berühren sichtbare Texte → **Korrekturstufe Web** |
| PK-05 | Tor umbauen | E-PK-04, -07, -13, -18, -20 | PK-04, PK-M1 | `pruefung.yml` | — |
| PK-06 | Staging verschlanken | E-PK-01, -07, -17, -21 | PK-05 | `auslieferung.yml` (`stufe2`), Kommentare in `ausliefern-lauf.yml`, `integritaet.yml` | — |
| PK-07 | Abschluss | — | PK-06 | `Technik.md` 6, `CLAUDE.md`, Rahmenplan, Backlog, Prüfdokument; Erledigt-Zeile Kette II | Freigabe |
| PK-08 | App-Auslieferung mit Signatur | E-PK-23 | PK-07, eigene Freigabe | `auslieferung.yml` (Jobs `android`, `uhr`), Umgebung `produktion` (zwei Geheimnisse), `Technik.md` 4.97g | Freigabe vor dem ersten Tag |
| **PK-M2** | Erster Durchlauf der neuen Kette (Betreiberin) | alle | PK-06 | PR, Merge, Staging | — |

Für jedes Paket gilt `CLAUDE.md` 7 und 8: eines nach dem anderen; Statusblock
und Prüfdokument fortschreiben, dann den Arbeitszweig pushen. **PK-04 ist das
einzige Paket mit Versionsstufe** (Web, Korrektur), weil die Bereinigung der
Hausform und der Adressen sichtbare Texte anfasst.

### PK-M1 — Zweigschutz und Merge-Recht (Betreiberin)

**Gesetzt am 21.09.2026 als zwei Rulesets:** „Main Protect" (PR-Pflicht,
Pflichtprüfung `Stufe 1`, kein Force-Push, kein Löschen, Bypass leer) und
„Main Merge-Recht" (Restrict updates, Bypass nur die Betreiberin, „pull
requests only"). **Gemessen (P-PK-01):** (b) `git push` als `claude` →
`GH013 Cannot update this protected ref`; (a) Merge über die API → **durch**,
`merged_by: chodid` — Befund F-PK-01, Folge in E-PK-04; (c) belegt die
Betreiberin mit PR #70. Der ursprüngliche Vorschlag steht darunter, weil der
klassische Weg dasselbe leistet und dieselbe Lücke hat.

Klassischer Weg (Settings → Branches → Rule für `main`): *Require a pull
request before merging* · *Require status checks to pass* mit `Stufe 1` ·
*Restrict who can push to matching branches* → nur die Betreiberin (das
schließt den Merge-Knopf ein) · *Do not allow bypassing the above settings*.
Rahmenplan 6b beschreibt die Maske; das Merge-Recht ist der eine neue Haken.
Der Ruleset-Weg tut dasselbe über *Restrict updates* mit der Betreiberin als
einzigem Bypass-Akteur; welcher gewählt wurde, steht in `Pruefablauf.md`.

**Abnahme (P-PK-01), drei Messungen:** Eine Claude-Instanz öffnet einen PR
mit einer Doku-Zeile und versucht den Merge — **abgewiesen**. Dieselbe
Instanz pusht direkt auf `main` — **abgewiesen**. Die Betreiberin mergt den
PR — geht. Ergebnis ins Prüfdokument. Ohne PK-M1 ist jedes Tor dieses
Konzepts eine Auskunft (1.2); deshalb vor der Freigabe.

### PK-01 — Regeldokumente

- `docs/Pruefablauf.md`: fünf Stationen, drei Stufen, sieben Grundsätze,
  Tabelle Berührung → Probe (ab PK-03 erzeugt), Benennungsregel, Form des
  Prüfberichts, LIESMICH-Regel (E-PK-25), was nicht geprüft wird und wo es
  steht (Nr. 234, echte Geräte, echte Ziele, Mailversand).
- `docs/Sandbox-Setup.md`: Konfigurationen und Modul, Beschaffungsbefehl,
  Nachweistabelle, die sieben Umgebungswerte mit Namen, die benannten Grenzen
  (2.3), die Proxy-Route für Chromium, die Netzregeln (offen: Ubuntu, GitHub,
  Docker Hub, Debian über HTTPS; gesperrt: PPA, MySQL-Quellen, php.net; nur
  Port 443).
- `CLAUDE.md` 6 auf Grundsätze und Verweis kürzen; `CLAUDE.md` 7 um E-PK-09
  ergänzen; `CLAUDE.md` 8 um den Satz „eine Instanz mergt nie" (F-PK-01).
- `.claude/settings.json`: Deny-Liste für `mcp__github__merge_pull_request`
  und `mcp__github__enable_pr_auto_merge` (P-PK-03); Abnahme: ein Aufruf
  wird vom Harness verweigert. Was in 6 an Werkzeugerzählung steht, wandert einmal nach
  `Pruefablauf.md` oder in die LIESMICH des Werkzeugs.
- Prüfdokument anlegen.
- **Abnahme:** beide Dokumente liegen; `CLAUDE.md` 6 unter 60 Zeilen (heute
  166); Emulator-Regel, Tag-Rumpf-Regel, Wortlisten-Regel je genau eine
  Fundstelle in `docs/` und `CLAUDE.md` zusammen.

### PK-02 — Sandbox-Setup

- `tools/sandbox/aufbauen.sh <web|android|uhr|alles|plattform>`: eine
  Beschaffung, idempotent, Nachweistabelle; `session-start.sh` ruft `web`;
  `containeraufbau/` fällt. Die sieben Umgebungswerte werden geprüft (Länge,
  Adresse antwortet), nie ausgegeben.
- `tools/sandbox/hochfahren.sh`: MariaDB, `lokal_einrichten.sh`,
  Referenzbestand, TLS als ein Befehl mit Rückgabewert; `--php 8.3` schaltet
  auf das Abbild um.
- `motor.mjs` bekommt die Proxy-Route (1.4) und die „Später"-Behandlung des
  Schlüsselblatt-Dialogs in der gemeinsamen Anmeldefunktion.
- **Abnahme:** frischer Container, `aufbauen.sh web && hochfahren.sh` →
  Anmeldeseite über TLS, Demo-Konto meldet sich an, 3 von 3 Engines, 7 von 7
  Werten geprüft; Dauer gemessen. `aufbauen.sh plattform` → vier
  Schemaproben 19/0 (10.11, 10.6, 8.0, 8.4.0) und PHP 8.3.33 mit allen
  Erweiterungen. `android` → `./gradlew build` grün; `uhr` → `pruefstand.sh
  reihe` grün.

### PK-03 — Prüfstand-Befehl

- `tools/pruefstand/pruefen.sh [--stufe …] [--gegen staging]`: Stufe
  ermitteln und sagen; hochfahren; Proben nach `pruefablauf.json`; billige
  Riegel; Android/Uhr bei Berührung; Plattformmatrix bei haupt; Bericht
  erzeugen; Rückgabewert.
- `pruefablauf.json` und `bericht.py` (`schreiben`, `lesen` mit Selbstprobe,
  `erzeugen-doku`); `kettenaufrufe` liest die JSON mit.
- **P-PK-02 (den edbak-500 lokal reproduzieren) ist am 21.09.2026 vorgezogen
  und erledigt** (1.5): reproduziert auf MySQL 8.4.0, behoben in Web 20.26.3.
  Für PK-03 bleibt daraus die Regel: Der Prüfstand fährt bei `haupt` und bei
  `--gegen staging` den edbak-Kreislauf **gegen MySQL 8.4.0**, nicht nur
  gegen MariaDB.
- **Abnahme:** je Stufe ein Lauf mit gemessener Dauer; Bericht in einer
  Commit-Nachricht; `bericht.py lesen --selbstprobe` fährt die vier roten
  Lagen und eine grüne; 0 Dateien unter `server/` ohne Muster in
  `pruefablauf.json`.

### PK-04 — Werkzeuge zusammenlegen und bereinigen

- Die drei Sammelordner nach E-PK-24, Streichliste und Aufgehen nach 3.3,
  jede LIESMICH auf die Fünf-Abschnitte-Form (E-PK-25).
- `quelltext/`: Textprobe mit fünf Regelklassen (E-PK-08), Vollständigkeit
  ohne Symbolzählung gegen null (E-PK-16), Linkprobe und die Tokenizer-Regeln.
- `bedienprobe/` nach Seiten (E-PK-15); Bilderlauf abgestuft ohne Risikoliste
  (E-PK-14).
- Bereinigung: Binnen-I in rund 190 Stellen (E-PK-26), `admin@gen-em.org`
  und die Prüfkonten nach `example.invalid` (E-PK-27), Handbuch nennt
  `demo@gen-em.org`; die 20 echten Symbole. **Das ist eine Korrekturstufe
  Web**, weil sichtbare Texte sich ändern; Changelog nennt sie.
- **Abnahme:** `ls -d tools/*/ | wc -l` = 15; Textprobe auf `main` 0 Treffer
  und je Regelklasse eine Gegenprobe (ein „Nutzer", ein `admin@gen-em.org`,
  ein Rufname, eine fremde Adresse → je 1 Treffer); Vollständigkeit 0;
  `grep -r 'admin@gen-em.org'` außerhalb von Changelog, Backlog und Konzepten:
  0; Summe der LIESMICH-Zeilen unter 1 000 (heute 7 206).

### PK-05 — Tor umbauen

- `pruefung.yml`: Auslöser `pull_request` und `push: main`; Android, Uhr und
  Bereichserkennung raus; Bericht-Gegenlesung rein; Aufruf von `quelltext/`
  statt sieben Einzelschritten; Vollständigkeit gegen null; Kommentare auf je
  einen Satz. Der Jobname `Stufe 1` bleibt (der Zweigschutz nennt ihn).
- **Abnahme:** PR ohne Bericht → rot mit Ansage; Bericht gegen falschen Baum →
  rot; passender Bericht → grün in **unter zwei Minuten** (gemessen);
  `pruefung.yml` unter 250 Zeilen (heute 834); `kettenaufrufe` 0/0.

### PK-06 — Staging verschlanken

- `stufe2`: drei Schritte plus der leere Platz für Nr. 234; Zeitgrenze
  20 min; Cache, wo tragfähig; Kommentare auf einen Satz. `ausliefern-lauf.yml`
  und `integritaet.yml`: nur Kommentare, kein Schritt ändert sich.
- **Abnahme:** Push auf `main` → Staging grün, Stufe 2 grün, zusammen unter
  zehn Minuten; Stufe 2 mit falschem `STAGING_PASS` → rot **innerhalb einer
  Minute** mit dem Grund; alle vier Arbeitsläufe zusammen unter 1 200 Zeilen
  (heute 2 830).

### PK-07 — Abschluss

Prüfdokument fertig; `Technik.md` 6.2/6.3 auf den neuen Stand; `CLAUDE.md` 3
und 6 widerspruchsfrei; Rahmenplan (R67-Zusatz, Fahrplanzeile, **Erledigt-
Zeile für Kette II** nach Abschnitt 9); Backlog (Nr. 227 nach Erledigt, neue
Nummern aus Abschnitt 8); Konzept wird nach der Freigabe gelöscht.

### PK-08 — App-Auslieferung mit Signatur (E-PK-23)

- Zwei Jobs in `auslieferung.yml`, ausgelöst durch `android-v*` und `uhr-v*`,
  in der Umgebung `produktion` (Pflichtfreigabe): bauen, signieren, ablegen.
  APK nach `server/apk/` über einen eigenen FTPS-Schritt (die Schutzliste des
  Abgleichs bleibt unangetastet); Uhr-Paket als Artefakt.
- Zuarbeit Z6: Upload-Schlüssel und Connect-IQ-Schlüssel als Geheimnisse der
  Umgebung `produktion`; `signatur.properties` wird im Lauf aus ihnen erzeugt.
- **Abnahme:** ein Tag `android-v…` → Lauf wartet auf Freigabe, baut,
  signiert (`apksigner verify` im Lauf), legt ab; die Fassung auf der
  Geräteseite stimmt. Vorher ein Probelauf ohne Ablage.

### PK-M2 — Erster Durchlauf der neuen Kette (Betreiberin)

Ein echtes Paket einer anderen Instanz geht A → B → C → D: Prüfstand,
Bericht, PR, Tor grün, Merge durch die Betreiberin, Staging grün. Die vier
Dauern werden notiert und mit Abschnitt 5 verglichen.

---

## 5. Was es kostet und was es spart

Spalte „heute" gemessen (1.2, 1.3); Spalte „Ziel" geschätzt, wird in PK-03,
PK-05, PK-06 durch Messwerte ersetzt.

| Weg | heute | Ziel |
|---|---|---|
| Stufe 1 auf dem Arbeitszweig | 1 min je Push | entfällt |
| Stufe 1 beim PR / auf `main` | 40 bis 51 min | unter 2 min |
| Lokale Prüfung vor dem Commit | unbestimmt | klein 5 / neben 15 / haupt 45 min, ein Befehl |
| Sandbox aufbauen, `web` | Hook ohne Nachweis, WebKit fehlt | unter 2 min mit Nachweis (gemessen: Einrichtung 11 s, Browser 16 s) |
| Modul `plattform` | gab es nicht | rund 6 min (PHP-Abbild 53 s, MySQL 8.4.0 16 s, MariaDB 10.6 30 s, MySQL 8.0 2 s; Ausweich PHP-Quelltext 266 s) |
| Staging + Stufe 2 | 1 min + 16 min, je Fehlerfund ein Umlauf | 1 min + unter 8 min |
| Ein Fehler wie der edbak-500 | 3 × 15 min, Ursache in fremdem Protokoll | Sekunden, Protokoll lokal |
| Zeilen Kette | 2 830 | unter 1 200 |
| Werkzeugordner / LIESMICH-Zeilen | 48 / 7 206 | 15 / unter 1 000 |
| Regeln zum Prüfen in `CLAUDE.md` 6 | 166 Zeilen | unter 60 |

**Was es kostet:** acht Pakete ohne Anwendungscode außer der Textbereinigung
in PK-04. Das ist noch einmal die Sorte Arbeit, die das Konzept verringern
soll, einmal bezahlt. Danach entfällt je Paket der 40-Minuten-Lauf, die
Stufe-2-Umläufe zur Fehlersuche, die Schwelle 398, die Wortlisten-Zeremonie,
die Selbstproben in Station B und die Frage, welche der zwanzig Proben dran
ist.

---

## 6. Offene Fragen

Keine. F-PK-1 bis -6 sind beantwortet (3.2). Was die Umsetzung selbst
aufwirft, trägt sie als F-PK-07 ff. hier ein.

---

## 7. Zuarbeiten der Betreiberin

| Nr. | Was | Wann | Stand |
|---|---|---|---|
| Z1 | PK-M1: Zweigschutz und Merge-Recht setzen; danach die drei Messungen aus P-PK-01 durch eine Claude-Instanz | **vor dem Push dieses Konzepts** | **erledigt 21.09.2026** — (c) mit dem Merge von PR #70 |
| Z2 | Den Kette-II-Zweig per PR mergen (PR #68, mit Übergabevermerk), damit PK-06 nicht kollidiert | vor der Freigabe | **erledigt 21.09.2026** (PR #68 gemergt) |
| Z2a | **PR #69 (Web 20.26.3) mergen** — der edbak-Fix; danach Stufe 2 auf `main` beobachten, dann Tag `web-v20.26.3` und Freigabe = **M1 der Kette II** | nach Stufe 1 grün | offen |
| Z3 | Umgebungswerte der Cloud-Umgebung: die sieben Namen aus 1.4 vollständig und in Anführungszeichen; Netzregel mit Docker Hub und `deb.debian.org` | — | **erledigt 21.09.2026** |
| Z4 | Staging: Mailversand reparieren (Webspace-Protokoll), Backup-Ziel eintragen | vor PK-M2 | offen (`097D7622` ist ohne das Protokoll geklärt, 1.5) |
| Z5 | Freigabe dieses Konzepts | nach Z1, Z2 | **erteilt 21.09.2026** |
| Z6 | PK-08: Upload-Schlüssel und Connect-IQ-Schlüssel als Geheimnisse der Umgebung `produktion` | vor PK-08 | offen |
| Z7 | PK-M2: einen echten PR durch die neue Kette mergen | nach PK-06 | offen |
| Z8 | Freigabe des Abschlusses | nach PK-07 | offen |

---

## 8. Einschübe (Nummern vergibt die einspielende Instanz)

**Rahmenplan:** R67-Zusatz „Prüfkette nach Konzept PK"; Abschnitt 6b um das
Merge-Recht ergänzen; Fahrplanzeile für PK; eine Zeile in Abschnitt 10;
**Erledigt-Zeile für Kette II** mit dem Übergabestand aus Abschnitt 9.

**Backlog:** Nr. 227 nach Erledigt (E-PK-16). Neu: „Nr. 234 hat seinen Platz
in Stufe 2, gebaut ist er nicht"; „edbak-Export auf Staging antwortet 500
(`097D7622`)" — **entfällt, ist Nr. 267 (Web 20.26.3)**; „Zwei Beschaffer mit zwei Listen" (erledigt mit PK-02);
„Regeldokumente aufteilen — `CLAUDE.md` Überblick, `Pruefablauf.md`,
`Sandbox-Setup.md`, `Technik.md` Kompendium, was fehlt" als eigenes Konzept
nach PK; „Mailversand auf Staging fehlgeschlagen" (Z4); „App-Signatur in der
Kette" (PK-08, ersetzt den Nachtrag zu Nr. 100).

**`CLAUDE.md` 7:** die Benennungsregel E-PK-09.

---

## 9. Übergabe von Kette II

Kette II wird nicht abgebrochen, sondern abgeschlossen. Was sie gebaut hat,
bleibt fast vollständig, weil PK die Auslieferung nicht anfasst:

| Aus Kette II | Unter PK |
|---|---|
| Zeiger `produktion`, Integritätswache (AP2) | bleibt, Station E |
| Zielprobe, Probelauf, Backup-Tor, Zustandsdatei, Schlussschritt (AP3, AP4, AP6) | bleibt, Station D und E |
| eine Schrittfolge für beide Umgebungen (AP5) | bleibt unverändert, Kommentare fallen (E-PK-07) |
| Hotfix-Weg mit Abstammungsprüfung (AP7) | bleibt; das Tor der grünen Läufe fragt weiter nach Stufe 1 und Staging, beide gibt es unter PK |
| Stufe 2 mit Kreisläufen und Bilderlauf | **PK-06 kürzt sie auf drei Schritte** |
| der offene Commit `download_lib` („fünfzehn Minuten messen") | nützlich, auch lokal; wird gemergt (Z2) |
| M1 erster grüner Produktivlauf | **bleibt nötig** und ist nach dem Merge von PR #69 (Web 20.26.3) erreichbar: Push-Lauf auf `main`, Stufe 2 grün, Tag, Freigabe (Z2a) |
| M2 Probe-Hotfix | entfällt als eigener Meilenstein; der Hotfix-Weg wird beim ersten echten Hotfix geprobt |
| AP8 Buchführung, Erledigt-Zeile, Konzeptlöschung | **übernimmt PK-07** |

Die Kette-II-Instanz erhält dafür eine kurze Anweisung: den offenen Commit
per PR abgeben, keine weiteren Pakete, keine Änderung an `stufe2` mehr, das
Konzept Kette II mit einem Übergabevermerk im Statusblock stehen lassen bis
PK-07.

---

## 10. Fable-Schritte der Umsetzung

Keine. Alle Pakete sind Werkzeug-, Ketten- und Dokumentationsarbeit; Opus
ohne Nachfrage (`CLAUDE.md` 7).
