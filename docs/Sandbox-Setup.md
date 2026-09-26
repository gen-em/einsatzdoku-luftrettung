# Arbeitsumgebung — was sie mitbringt, was nachgeholt wird, was sie nicht kann

*Stand: 21.09.2026 · Prüfablauf: `Pruefablauf.md` · Architektur und Betrieb:
`Technik.md` · Arbeitsanweisung: `CLAUDE.md`.*

Die Arbeit an diesem Repositorium findet in einem **Wegwerf-Container**
statt: Er entsteht mit der Sitzung, wird beim Ende weggeworfen, und alles,
was nicht im Repositorium liegt, muss er sich jedes Mal neu holen. Dieses
Dokument sagt, **was er mitbringt**, **wie das Fehlende beschafft wird** und
**was auch danach nicht geht**. Es ist die eine Stelle dafür.

Aufgestellt mit **Konzept PK** (PK-01). Es löst `Technik.md` 2a ab; dort
steht seither ein Verweis hierher.

> **Der letzte Abschnitt ist der wichtigste.** Eine Umgebung, von der man
> annimmt, sie könne etwas, das sie nicht kann, erzeugt Messwerte, die nach
> einer Prüfung aussehen. Abschnitt 6 nennt die Grenzen beim Namen.

---

## 0. Was schon gilt und was noch entsteht

| Stück | Stand |
|---|---|
| Was das Abbild mitbringt (1) | **gemessen 21.09.2026** |
| Die acht Umgebungswerte (4) | **8 von 8 gemessen** |
| Netzregeln und Grenzen (5, 6) | **gemessen 21.09.2026** |
| `tools/sandbox/aufbauen.sh`, `hochfahren.sh`, `plattform.sh` | **gebaut und gemessen mit PK-02** |
| Die Ausbaustufen `web` und `plattform` (2) | **gebaut und gemessen** |
| Die Ausbaustufe `android` (2) | **gemessen 24.09.2026** (Konzept AR): 22 s, rc 0, 18 von 18 Stücken; seit AR-03 mit Plattform 37.0, JDK-Prüfung und Gradle-Spiegel |
| Die Ausbaustufe `emulator` (2) | **gemessen 25.09.2026** (Konzept AR, F-AR-18) — Zahlen in 2 |
| Die Ausbaustufe `uhr` (2) | gebaut, **noch nicht gemessen** — siehe Prüfdokument PK |
| Die Route nach draußen (5.2) | **gebaut und gemessen** in `tools/motor.mjs` |
| Der Schlüsselblatt-Dialog (5.3) | **gebaut und gemessen**, örtlich und gegen die Prüfanlage |
| Zwei Beschaffer mit zwei Listen (1.2) | **behoben mit PK-02** — es gibt nur noch einen |

---

## 1. Was der Container heute mitbringt

Gemessen am 21.09.2026.

**Im Abbild vorhanden:** PHP **8.4.19** (mit `pdo_mysql`, `mysqli`,
`openssl`, `gd`, `zip`, `mbstring`), Node **22.22** samt Playwright **1.56**
und den drei Engine-Dateien unter `/opt/pw-browsers/`, Python **3.11**
(deadsnakes) mit `requests` und `cryptography`, Java, `socat`, `zip`,
`unzip`, `git`, `curl`, `openssl`, Docker **29.3.1**. Vier Kerne, 15 GB
Speicher.

**Nicht im Abbild, und ohne diese steht die Hälfte der Prüfmittel:**
*(Am 26.09.2026 brachte das Abbild MariaDB, ImageMagick und `rsvg-convert`
schon mit — der apt-Verlauf zeigt sie am 22.09.2026, vom Sitzungshook vor
PK-02; cmark-gfm und die vier WebKit-Bibliotheken fehlten. `aufbauen.sh`
prüft je Paket und holt nur, was fehlt.)*

| fehlt | wer es braucht |
|---|---|
| **MariaDB** | jede Browserprobe, beide Kreisläufe, Bedienprobe, Wartungsprobe, `lokal_einrichten.sh` |
| **ImageMagick** (`convert`, `compare`) | der Erzeuger `tools/erzeugen/uhr-bilder.sh` und jeder Bildvergleich |
| **rsvg-convert** (`librsvg2-bin`) | dieselbe Kette: SVG nach PNG |
| **cmark-gfm** | die Quelltextprüfungen `handbuch` — rendern Handbuch und „Was ist NAdoku"? (seit BR-03) — und `bestand`, die die Tabelle der Quelltextprüfungen so liest, wie GitHub sie zeigt (seit BR-05) |
| **Python `jsonschema`** | `tools/referenzdatensatz/quelldaten/pruefen.py` |
| **Python `pyftpdlib`, `paramiko`, `pyopenssl`** | die Gegenstellen der Versandprobe (`tools/proben/versand/gegenstellen.py`); ohne `pyopenssl` fehlt FTPS, und alle drei Nachbauten brechen ab (RP-01) |
| **Vier Systembibliotheken für WebKit** (`libenchant-2-2`, `libsecret-1-0`, `libwayland-server0`, `libmanette-0.2-0`) — Firefox startet ohne sie, WebKit nicht (gemessen 26.09.2026: entfernt, Start versucht, wieder geholt; Backlog Nr. 301) | jede Aussage über die Oberfläche, die auch für WebKit gelten soll (Backlog Nr. 183) |
| **Android-SDK** (Plattformen 37.0 und 36, Build-Tools 36.0.0, `cmdline-tools` 23.0) | `./gradlew build` im Ordner `android/`; `cmdline-tools` unter 23.0 legt AVDs mit API 37 falsch an (F-AR-18) |
| **Emulator, Abbilder API 37, `lz4`, `cpio`, `libpulse0`** | `android/werkzeuge/emulator.sh` — der Emulatorlauf nach `Pruefablauf.md` 6.9 |
| **Uhr-SDK und Gerätedateien** | `tools/uhr-pruefstand/` |

### 1.1 Warum alle drei Engines dazugehören

Bis zum 14.09.2026 lief jede Browserprobe in Chromium — tragbar, solange
die Oberfläche sich auf Breitentricks beschränkte. Seit Web 19.4.1 hängt
eine Darstellung an einer Container-Abfrage, seit P3 an `:has()` und `dvh`.
Eine Engine, die eines davon nicht kann, fiele **lautlos** durch jede
Prüfung.

Der dreifache Lauf hat am ersten Tag zwei Befunde geliefert, die sonst nicht
aufgefallen wären: `import.php` lief bei 360 px **nur in WebKit** um 6 px
über, weil WebKit den längsten Eintrag eines Auswahlfelds in den Überlauf
rechnet (Backlog Nr. 185); und ein Prüfmittel maß eine Drehung mit
`getScreenCTM()`, das in WebKit die Transformation eines Vorfahren nicht
enthält — ein Fehler im Prüfmittel, der wie einer der Anwendung aussah
(Nr. 186). Die Motorwahl steht an einer Stelle, `tools/motor.mjs`.

### 1.2 Zwei Beschaffungswege mit zwei Listen — behoben mit PK-02

**Bis PK-02 beschafften zwei Stellen dasselbe, und zwar verschieden.**
Gemessen am 21.09.2026, kurz bevor sie zusammengelegt wurden:

| | `.claude/hooks/session-start.sh` | `tools/containeraufbau/aufbau.sh` |
|---|---|---|
| Läuft | beim Sitzungsstart, nur bei `CLAUDE_CODE_REMOTE=true` | von Hand, mit Unterbefehlen |
| Systempakete | `mariadb-server`, `imagemagick`, `librsvg2-bin` | dieselben **plus** `socat`, `unzip`, `wget`, `curl` |
| Engine-Bibliotheken | `libgtk-4-1`, `libwoff1`, `libevent-2.1-7t64`, `libgstreamer-plugins-bad1.0-0`, `libflite1`, `gstreamer1.0-libav` — **sechs** | `libenchant-2-2`, `libsecret-1-0`, `libwayland-server0`, `libmanette-0.2-0` — **vier, keine davon dieselbe** |
| Engines selbst | ruft `playwright install firefox webkit` | ruft es **ausdrücklich nicht** („ein Nachladen zöge eine zweite, abweichende Fassung daneben") |
| Python | `jsonschema`, `cryptography` geprüft und nur im Fehlerfall ersetzt | `cffi`, `jsonschema` |
| Datenbank starten | **nein**, ausdrücklich nicht | ja (`datenbank`) |
| Android-SDK | nein | ja (`android`) |
| Bei Fehlschlag | meldet und gibt **0** zurück | bricht mit **1** ab |

**Die beiden Bibliothekslisten haben keinen einzigen Eintrag gemeinsam**,
und in der Frage, ob Engines nachgeladen werden, widersprechen sich die
Dateien. Welche Arbeitsmittel eine Sitzung vorfand, hing also davon ab,
welche der beiden Dateien zuletzt jemand gepflegt hatte.

**PK-02 hat beide zu einer zusammengelegt.** `tools/sandbox/aufbauen.sh` ist
die eine Beschaffung, `session-start.sh` ruft sie mit `web`,
`tools/containeraufbau/` ist entfernt.

**Der Widerspruch ist durch Messung entschieden, nicht durch Abwägung.**
Playwright nennt die Paketnamen selbst, wenn WebKit nicht startet — und es
sind die vier aus `aufbau.sh`:

```
Alternatively, use apt:
    apt-get install libenchant-2-2 libsecret-1-0 libwayland-server0 libmanette-0.2-0
```

Gemessen am 21.09.2026 in dieser Sitzung, **nachdem der Hook gelaufen war**:
seine sechs Pakete installiert, die vier von `aufbau.sh` nicht — und WebKit
startete nicht. Nach dem Nachziehen der vier: **3 von 3 Engines, WebKit 26.0**.
Die Engine-Dateien selbst liegen im Abbild (`chromium-1194`, `firefox-1495`,
`webkit-2215`); `playwright install` ist deshalb nicht der Weg und zöge nur
eine zweite Fassung daneben. `aufbauen.sh` installiert die vier
Bibliotheken, lädt keine Engine nach und gibt bei einem Fehlschlag die
Meldung von Playwright aus — sie nennt die Namen und altert nicht mit.

---

## 2. Die fünf Ausbaustufen und das Modul

`tools/sandbox/aufbauen.sh <web|android|emulator|uhr|plattform|alles>` stellt eine
fest beschriebene Ausbaustufe her, idempotent, und **misst nach, was
steht**.

| Stufe | Enthält | Wofür |
|---|---|---|
| `web` | MariaDB 10.11, PHP 8.4, **drei** Engines (nachgemessen, nicht angenommen), Python-Pakete, die acht Umgebungswerte geprüft | jede Änderung unter `server/`, `docs/`, `tools/` |
| `android` | `web` plus Android-SDK (Plattform 37.0 für den Bau, 36 für die Erkennung im Prüfstand), JDK 21 geprüft, Gradle über Googles Spiegel mit mehr Wiederholungen (5.1). **Kein Emulator-Abbild** — das holt `android/werkzeuge/emulator.sh aufbauen`, mehrere GB, nur für den Emulatorlauf. *Bis zum 24.09.2026 stand hier „Emulator-Abbild"; das Skript hat es nie geholt (Konzept AR, F-AR-02).* | Änderungen unter `android/` |
| `emulator` | `android` plus Emulator, die Abbilder mit API 37 (Handy `google_apis`, Uhr `android-wear-signed`), die AVDs `handy37` und `uhr37` mit berichtigtem `target` und die Debug-Ramdisk der Uhr; `lz4`, `cpio`, `libpulse0`. Rund 9 GB, und der Start einer AVD verlangt **weitere 7,4 GB frei** für die Datenpartition — der Emulator bricht sonst sofort ab. Nicht in `alles` | den Emulatorlauf (`android/LIESMICH.md`, „Wear OS 7 ohne Root“) |
| `uhr` | `web` plus Uhr-SDK, Gerätedateien, Simulator-Bibliotheken | Änderungen unter `watch/` |
| `alles` | `web`, `android`, `uhr` plus das Modul `plattform` — **ohne** `emulator` | Hauptstufe, Abnahmen |
| Modul `plattform` | PHP 8.3.33, MariaDB 10.6, MySQL 8.0, MySQL 8.4.0 | Hauptstufe; einzeln nachladbar |

Der Hook ruft `web`. Wer mehr braucht, ruft nach.

### 2.1 Die Nachweistabelle

Jeder Lauf endet mit einer Tabelle, die **je Stück die Fassung nennt** — die
drei Engines **einzeln**, die acht Umgebungswerte mit ihrer Länge, nie mit
ihrem Wert. Der Grund steht in `Pruefablauf.md` 6.5: „3 Browser da" sagt
nicht, welcher fehlt, und es fehlt immer nur einer. Der Prüfbericht führt
diese Tabelle mit (`Pruefablauf.md` 5).

### 2.2 Das Modul `plattform` — Fassungen und Wege

```
bash tools/sandbox/plattform.sh [php83|mariadb106|mysql80|mysql84|alles|schema|--aus]
```

**Alle vier über Docker, gemessen am 21.09.2026 — `alles` in 29,7 s:**

| Stück | Weg | gemessen |
|---|---|---|
| PHP 8.3.33 | Abbild aus `php:8.3.33-cli` plus fünf Erweiterungen (`pdo_mysql`, `mysqli`, `zip`, `intl`, `gd`) | Bau **47 s**; alle fünf geladen |
| MariaDB 10.6 | `mariadb:10.6`, Port 3310 | bereit nach **5 s**, 10.6.28 |
| MySQL 8.0 | `mysql:8.0`, Port 3307 | bereit nach **8 s**, 8.0.46 |
| MySQL 8.4.0 | `mysql:8.4.0`, Port 3308 | bereit nach **10 s**, 8.4.0 |
| MariaDB 10.11 | die örtliche, Port 3306 | 10.11.14 |
| Schemaprobe je Fassung | `tools/schemaprobe/` | **4 × „30 Prüfungen, 0 Fehlschläge"** (seit P5c/AP8; bis dahin 19) |

**Drei Dinge, die erst die Messung ergeben hat** — und die alle drei gegen die
erste Planung stehen:

- **Der Docker-Dienst läuft nicht von selbst.** Er muss gestartet werden
  (`dockerd >/tmp/dockerd.log 2>&1 &`), sonst meldet jeder Aufruf „dial unix
  /var/run/docker.sock: no such file". Das Werkzeug sagt es und rät nicht.
- **Die Drosselung von Docker Hub trägt den Umweg nicht.** Vier Abrufe und
  ein Bau liefen durch, ohne einen 429 zu sehen. Der vorgesehene Umweg über
  Ubuntu-Pakete unter `/opt` ist damit **nicht gebaut worden**: Er wäre
  aufwendiger, zerbrechlicher und löst ein Problem, das bei vier Abbildern
  nicht auftritt. Tritt es später auf, ist es ein Befund mit Zahl.
- **Das PHP-Abbild braucht die Zertifizierungsstellen des Wirts.** Nur HTTPS
  kommt hinaus, und im Behälter scheitert es ohne sie mit „certificate
  verify failed". Gemessen: die Stelle des Agent-Proxys **allein genügt
  nicht** — der Verkehr des Behälters läuft über das Egress-Gateway. Das
  Werkzeug kopiert deshalb alle Stellen aus
  `/usr/local/share/ca-certificates/` in den Bauplatz, bis auf die, die
  `lokal_starten.sh` je Behälter neu erzeugt.

**Und eine Falle beim Warten:** Der Einstiegspunkt von MySQL richtet erst ein
und startet den Dienst **danach neu**. Ein Ping gelingt schon vorher — die
Schemaprobe lief prompt in „MySQL server has gone away". Gewartet wird
deshalb auf eine echte Abfrage (`SELECT VERSION()`), nicht auf ein Ping.

**Warum die Matrix überhaupt:** Staging läuft auf **MySQL 8.4.10**,
Produktiv auf **MariaDB 10.11.14**. Am 21.09.2026 scheiterte ein Export auf
der einen und lief auf der anderen, und keine der beiden Anlagen hatte je
gesagt, dass sie verschieden sind. Die Matrix ist die Stelle, an der das
auffällt, bevor es ausgeliefert ist.

---

## 3. Hochfahren

```
bash tools/sandbox/hochfahren.sh [--neu] [--php 8.3]
```

Er fasst zusammen, was vorher drei Aufrufe waren: MariaDB starten, die
Anwendung einrichten oder starten, TLS davor. **Ein Rückgabewert**, und am
Ende eine Zeile, die den Gegenstand nennt — Adresse, HTTP-Code, gemeldete
Fassung, nicht bloß „läuft".

**Der PHP-Server läuft mit vier Arbeitern** (`PHP_CLI_SERVER_WORKERS`, seit
dem Abschluss von P5c; `PHP_ARBEITER` stellt die Zahl, in
`lokal_starten.sh` und `lokal_einrichten.sh`). Mit einem Arbeiter bediente
er eine Anfrage zur Zeit, hinter socat, für einen Browser, der sechs
Verbindungen gleichzeitig und weitere auf Vorrat öffnet. Das hat dreimal
einen Prüfstand rot gefärbt, ohne dass die Anwendung etwas falsch machte:
WebKit hing beim zweiten Anmelden (F-RW-23, Backlog Nr. 301), und zweimal
kam die Bedienprobe nach der Anmeldung nicht an ihre erste Seite
(`net::ERR_TOO_MANY_RETRIES`, RW-04 und der Abschluss von P5c) — die
Anfrage erreichte den Server nie. Die Anwendung zählt nichts im
Prozessspeicher; Sitzungen und Ratenbremsen liegen in Dateien und in der
Datenbank, und Produktiv bedient ohnehin viele Anfragen zugleich.

**Ohne `--neu` wird nicht neu eingerichtet.** `lokal_einrichten.sh` löscht die
Datenbank und `config.php`; das soll niemand aus Versehen auslösen. Steht
eine Installation, wird sie nur gestartet.

**`--php 8.3`** beendet den PHP-Server des Containers und fährt die Anwendung
im Abbild `nadoku-php83` weiter, im Netzwerk des Wirts, gegen dieselbe
Datenbank und denselben TLS-Vorbau. Dafür muss das Abbild stehen
(`plattform.sh php83`).

*Gemessen am 21.09.2026: frische Einrichtung auf einer leeren Datenbank,
106 Einsätze und 21 Diensttage eingespielt, `login.php` HTTP **200**,
Fassung 20.26.2, Rückgabewert 0.*

**Eine Lage in `config.php` herstellen** — einen anderen Serverschlüssel,
keinen Anteil — und wieder zurücknehmen: `tools/sandbox/konfig_stellen.php`.
Vier Proben binden es ein; es schreibt die Datei und ruft
`konfig_verwerfen()`, denselben Weg wie die Anwendung, und legt am Ende den
vorigen Stand zurück, auch nach einem Abbruch.

---

## 4. Die acht Umgebungswerte

Sie stehen **nicht** im Repositorium, sondern in den Umgebungsvariablen der
Arbeitsumgebung. Hier stehen nur die Namen, und das bleibt so: Ein Wert, der
in der Dokumentation steht, ist keiner mehr.

| Name | Wofür | gemessen 21.09.2026 |
|---|---|---|
| `NADOKU_STAGING_URL` | Adresse der Prüfanlage | gesetzt, 33 Zeichen |
| `NADOKU_STAGING_KONTO` | Prüfkonto dort | gesetzt, 18 Zeichen |
| `NADOKU_STAGING_PASS` | dessen Passwort | gesetzt, 25 Zeichen |
| `NADOKU_STAGING_JOBS_TOKEN` | Aufruf von `jobs.php` | gesetzt, 64 Zeichen |
| `_MAIL_URL` | Webmail der Prüfanlage | gesetzt, 17 Zeichen |
| `_MAIL_USER` | Postfach dort | gesetzt, 18 Zeichen |
| `_MAIL_PASS` | dessen Passwort | gesetzt, 20 Zeichen |
| `CIQ_GERAETE_URL` | Gerätedateien des Uhr-SDK | gesetzt, 33 Zeichen |

> **Der Zweitfaktor des Prüfkontos (seit Web 20.42.0) ist kein Wert dieser
> Tabelle.** In der Sandbox rechnen die Werkzeuge mit dem Geheimnis der
> Sandbox, das `tools/zweitfaktor/pruefkonto.php` beim Einrichten einträgt
> (`lokal_einrichten.sh`, Schritt 6b). **`NADOKU_TOTP` muss hier leer
> bleiben** — ist es gesetzt, rechnen die Rechner mit diesem Geheimnis, und
> die Anmeldung an der Sandbox scheitert am Code-Schritt; die Meldung nennt
> die Quelle. Gegen Staging reicht die Kette das Secret `STAGING_TOTP`
> durch (`tools/zweitfaktor/LIESMICH.md`); aus der Arbeitsumgebung heraus
> gibt es dafür keinen Wert, und die Staging-Werkzeuge nehmen es als
> Schalter (`--admin-totp`).

> **Die drei Mailwerte tragen einen führenden Unterstrich und kein Präfix.**
> Sie heißen `_MAIL_URL`, `_MAIL_USER`, `_MAIL_PASS` — nicht
> `NADOKU_STAGING_MAIL_URL`. Wer die Kurzschreibweise `NADOKU_STAGING_URL,
> _KONTO, _PASS, _MAIL_URL` als gemeinsames Präfix liest, sucht drei Werte,
> die es nicht gibt. Deshalb stehen sie hier ausgeschrieben.

**Zwei davon waren am Vormittag des 21.09.2026 falsch** — die Adresse zeigte
auf die stillgelegte Anlage, und zwei Passwörter waren am `#` abgeschnitten,
weil sie ohne Anführungszeichen gesetzt waren. **Kein Werkzeug hat es
gemerkt**; die Prüfläufe meldeten Anmeldefehler und niemand sah die Ursache.
Seither stehen die Passwörter in Anführungszeichen, und `aufbauen.sh` prüft
ab PK-02 jeden der Werte: ist er gesetzt, wie lang ist er, antwortet die
Adresse — **nie wird der Wert ausgegeben**.

**`CIQ_GERAETE_URL`** liegt seit dem 03.09.2026 ebenfalls in den
Umgebungswerten. Prüfen mit `[ -n "$CIQ_GERAETE_URL" ]`, und nur erfragen,
wenn sie fehlt.

### 4.1 Zwei weitere, die der Container NICHT mitbringt

`WEGWERFKONTO` und `WEGWERFPASSWORT` gehören nicht zu den acht. Sie stehen
in keiner Ausbaustufe, `aufbauen.sh` misst sie nicht, und eine Arbeitsumgebung
ohne sie ist vollständig. Gebraucht werden sie von **genau einer** Probe:
`spaltenregister-wegprobe` (Schritt 15/AP6).

**Warum sie eigene Werte sind und nicht die Prüfkonto-Werte darüber.** Die
Wegprobe **schreibt**: Sie schneidet einen Einsatz und überschreibt einen
zweiten. Auf der Demo wäre das ein zerstörter Vorführbestand, auf dem
Referenzbestand ein zerstörter Vergleichsmaßstab — beides fällt nicht sofort
auf, sondern erst beim nächsten Kreislauf, der dann eine Abweichung meldet,
die keine ist. Ein Wegwerfkonto ist eines, dessen Verlust niemanden stört.

Wer die Probe fahren will, legt sich eines an und setzt die beiden Werte in
der Sitzung. Sie gehören aus demselben Grund nicht ins Repositorium wie die
acht darüber: **Ein eingecheckter Zugang ist ein Zugang.**

---

## 5. Das Netz

### 5.1 Was durchkommt und was nicht

**Nur Port 443.** Kein IMAP, kein SMTP, kein anderer Port nach außen.

| offen | gesperrt |
|---|---|
| Ubuntu-Hauptquellen | `ppa.launchpadcontent.net` (meldet 403) |
| GitHub | `repo.mysql.com` |
| Docker Hub (drosselt, 429 nach rund acht Abrufen) | `php.net` |
| `deb.debian.org` **über HTTPS** | jeder Port außer 443 |
| `cdn.playwright.dev`, `playwright.download.prss.microsoft.com` | |
| Maven Central — **aber gedrosselt**: `repo.maven.apache.org` 13 von 20 Abrufen `429` (24.09.2026) | |
| Googles Spiegel `maven-central.storage-download.googleapis.com` (10 von 10) | |
| Staging und dessen Webmail | |

**Die Drosselung von Maven Central trifft den Android-Bau** (Konzept AR,
F-AR-01): Der unveränderte Stand baute am 24.09.2026 erst im fünften Anlauf,
jedes Mal mit `Received status code 429`. Seither schreibt `aufbauen.sh
android` zwei Dinge — **nur in die Arbeitsumgebung, nicht ins
Repositorium**: `~/.gradle/init.d/spiegel.gradle` setzt Googles Spiegel vor
die Quellen des Projekts (Maven Central bleibt als Rückfall dahinter), und
`~/.gradle/gradle.properties` erhöht die Wiederholungen je Abruf auf zehn.
Der Spiegel liefert dieselben Dateien — die SHA-1 des Robolectric-Abbilds
stimmt auf Spiegel, Central und im Cache überein.

Fällt eine dieser Freigaben weg, scheitert die Beschaffung — und das ist
**ein Befund mit Zahl**, kein stiller Ausfall: welcher Abruf, welche
Antwort, welche Adresse.

### 5.2 Chromium und die Proxy-Zertifizierungsstelle

Der ausgehende Verkehr läuft über einen Proxy mit eigener
Zertifizierungsstelle. Node kennt sie; **Chromium nicht**. Der saubere Weg
(`certutil`, die Stelle in den Zertifikatsspeicher des Browsers legen) wird
vom Auto-Modus als Sicherheitsschwächung abgelehnt.

**Der tragfähige Umweg** leitet die Anfragen des Browsers mit `page.route`
durch den Node-Stack, der die Stelle kennt — **ohne
`ignoreHTTPSErrors`**. Das ist der Unterschied, auf den es ankommt: Der
Umweg prüft das Zertifikat weiterhin, er prüft es nur an einer anderen
Stelle. `ignoreHTTPSErrors` prüfte gar nicht mehr, und eine Prüfanlage, die
man nicht mehr von einer untergeschobenen unterscheiden kann, misst nichts.

**Gebaut mit PK-02**, in `tools/motor.mjs`: `proxyRoute()` legt die Umleitung
auf einen Kontext, `kontextMachen()` trifft die Entscheidung gleich mit.

**Zwei Dinge, die dabei erst die Messung zeigte:**

- **Es sind zwei Engines, nicht eine.** Gemessen gegen die Prüfanlage:
  Chromium `ERR_CERT_AUTHORITY_INVALID`, **Firefox
  `SEC_ERROR_UNKNOWN_ISSUER`**, WebKit HTTP 200 (es nimmt den
  Systemspeicher), Node HTTP 200.
- **Örtliche Adressen dürfen NICHT über die Umleitung laufen.** Der
  Node-Stack schickt auch `127.0.0.1` durch den Proxy, und der kennt den
  Wirt nicht: Mit Umleitung scheiterte `https://127.0.0.1:8443` in allen
  drei Engines. Für die örtliche Anlage ist `ignoreHTTPSErrors` richtig und
  harmlos — die Stelle hat dieser Behälter vor Minuten selbst angelegt, und
  auf 127.0.0.1 sitzt niemand dazwischen. **Nach draußen wäre dasselbe
  falsch.** `kontextMachen()` hält beide Fälle auseinander, damit die Wahl
  nicht in jedem Werkzeug neu getroffen wird.

*Gemessen: 6 von 6 — drei Engines, örtlich und gegen die Prüfanlage,
angemeldet.*

### 5.3 Der Dialog „Schlüsselblatt bestätigen"

Er erscheint alle drei Monate nach der Anmeldung. **Jedes Werkzeug, das sich
anmeldet, muss ihn mit „Später" schließen können** — sonst bleibt es alle
drei Monate an einer Stelle hängen, die mit seiner Messung nichts zu tun
hat, und meldet einen Fehlschlag, den niemand zuordnet.
`blattDialogSchliessen()` in `tools/motor.mjs` übernimmt das seit PK-02,
`anmelden()` ruft es mit.

**Er öffnet sich NACH der Anmeldung, nicht mit ihr.** Gemessen am 21.09.2026
in allen drei Engines: unmittelbar nachdem das Passwortfeld verschwunden ist,
steht `dialog.open` auf `false`, zwei Sekunden später auf `true`. Wer sofort
nachsieht, findet nichts und meldet „kein Dialog" — und stolpert eine Messung
später über ihn. Deshalb wird gewartet, und zwar nur dann, wenn das Element
überhaupt im Markup liegt (der Server bindet es nur ein, wenn er fragen will).

**Und die Adresse taugt nicht als Merkmal:** Nach der Anmeldung steht die
Tagesübersicht **unter `/login.php`** — es gibt keine Umleitung. Eine Prüfung
auf „Adresse enthält login.php nicht mehr" wartet auf etwas, das nie
eintritt. Das verlässliche Merkmal ist das Verschwinden des Passwortfeldes;
gemessene Dauer danach 1,1 s. Dass die Anmeldung überhaupt Zeit braucht,
liegt an der Ableitung im Browser (310 000 Runden, während einer Anhebung
zweimal).

---

## 6. Was die Arbeitsumgebung nicht kann

Der wichtigste Abschnitt. Wer hier etwas annimmt, das nicht stimmt, misst
etwas anderes, als er glaubt.

| Nicht möglich | Folge |
|---|---|
| **Mailversand und -empfang** | Nur Port 443. Geprüft werden Warteschlange und Katalog; ob eine Nachricht ankommt, zeigt nur das Webmail der Prüfanlage. |
| **Der Apache des Hosters** | `.htaccess` gilt hier nicht. Punktdateien und `.well-known` misst **allein** die Prüfung auf Staging (`Pruefablauf.md` 2.4). |
| **Plattformverhalten von Produktiv** | Es gibt keine Anlage, die Produktiv nachstellt. Die Plattformmatrix (2.2) misst dieselben Fassungen, nicht dieselbe Anlage. |
| **Ein echtes Gerät** | Kein echter Data Layer, kein echter Schlüsselspeicher, kein gekoppeltes Gerät. Was der Emulator und der Uhr-Simulator dafür können und was nicht, steht in `android/LIESMICH.md` und `Geraete-Eingabe.md`. |
| **Ein echtes Sicherungsziel** | Es wird keine Gegenstelle angefahren, die wirklich außer Haus schreibt. |
| **Docker ohne Maß** | Docker Hub drosselt anonyme Abrufe. Ein Lauf, der acht Abbilder holt, scheitert beim neunten mit 429 — und das sieht aus wie ein Netzfehler. |

### 6.1 Zwei Fallen, die Zeit kosten

**`python3` ist 3.11, apt legt nach 3.12.** Ein
`apt-get install python3-jsonschema` landet in einem Verzeichnis, das
`python3` nicht liest. Deshalb `pip` mit `--break-system-packages`. Dasselbe
erklärt, warum das apt-`cryptography` (für 3.12 gebaut, abi3) hier nur
meistens trägt — der Import wird geprüft und das Paket nur im Fehlerfall
ersetzt.

**Der eingebaute PHP-Server liefert nach einer Dateiänderung für einige
Sekunden den alten Stand.** Gemessen am 13.09.2026: dieselbe Anfrage 0 s
nach der Änderung mit dem alten Verhalten, 4 s danach mit dem neuen. Es ist
**nicht** OPcache (`opcache.enable_cli` steht auf Off). Wer eine
Serveränderung prüft, startet den Server vorher neu — sonst misst er den
Stand davor und hält ihn für den danach.

### 6.2 Der Emulator ist eine Eigenschaft des Containers

Er läuft ohne KVM: QEMU übersetzt dann selbst, auf **einem** Kern, und Start
und Aufspielen liegen in Minuten statt Sekunden. **Bevor man ihn
abschreibt, wird `-accel off` versucht** — am 03.09.2026 stand in
`android/LIESMICH.md`, das x86_64-Abbild brauche KVM; es braucht es nicht.
Der Satz verwechselte „startet nicht ohne Weiteres" mit „geht nicht" und
kostete ein ganzes Arbeitspaket ohne Simulatorprüfung.

**Und bevor man ein Abbild abschreibt, wird die AVD angesehen.** Am
25.09.2026 galt das Wear-Abbild mit API 37 als unbrauchbar, weil es ein
`user`-Build ist; tatsächlich lag es an `target=android-0`, das ein
veraltetes `cmdline-tools` in die AVD schrieb (F-AR-18). Die Stufe
`emulator` holt deshalb `cmdline-tools` 23.0, `emulator.sh` berichtigt den
Eintrag, und für `user`-Builds setzt es den Watchdog-Faktor über die
Debug-Ramdisk von AOSP statt über `adb root`.

---

## 7. Herkunft

Konzept PK, freigegeben am 21.09.2026 (E-PK-12, E-PK-29, E-PK-30). Die
Zahlen sind am 21.09.2026 gemessen, die des Moduls `plattform` in einem
Nachbau von Hand. Der Werdegang steht in den Commit-Nachrichten der
PK-Pakete, im Changelog und im Backlog — nicht hier (`Pruefablauf.md` 1,
Grundsatz 6).
