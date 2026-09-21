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
| Die beiden heutigen Beschaffungswege (1.2) | **gilt** — und ist das Problem, das PK-02 abstellt |
| Die sieben Umgebungswerte (4) | **gilt, 7 von 7 gemessen** |
| Netzregeln und Grenzen (5, 6) | **gemessen 21.09.2026** |
| Die vier Ausbaustufen und das Modul (2) | entsteht mit **PK-02** |
| `tools/sandbox/aufbauen.sh`, `hochfahren.sh` (2, 3) | entsteht mit **PK-02** |
| Die Proxy-Route für Chromium (5.2) | entsteht mit **PK-02** |
| Die Plattformmatrix (2.2) | **einmal von Hand nachgebaut und gemessen** (21.09.2026); als Ausbaustufe entsteht sie mit PK-02 |

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

| fehlt | wer es braucht |
|---|---|
| **MariaDB** | jede Browserprobe, beide Kreisläufe, Bedienprobe, Wartungsprobe, `lokal_einrichten.sh` |
| **ImageMagick** (`convert`, `compare`) | `tools/uhr-bilder/erzeugen.sh` und jeder Bildvergleich |
| **rsvg-convert** (`librsvg2-bin`) | dieselbe Kette: SVG nach PNG |
| **Python `jsonschema`** | `tools/referenzdatensatz/quelldaten/pruefen.py` |
| **Systembibliotheken für Firefox und WebKit** | jede Aussage über die Oberfläche, die für mehr als Chromium gelten soll (Backlog Nr. 183) |
| **Android-SDK** (Plattform 36, Build-Tools 36.0.0) | `./gradlew build` im Ordner `android/` |
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

### 1.2 Zwei Beschaffungswege mit zwei Listen — der Grund für PK-02

**Heute beschaffen zwei Stellen dasselbe, und zwar verschieden.** Gemessen
am 21.09.2026:

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
Dateien. Gemessen: Im frischen Container starten nach dem Hook Chromium und
Firefox, **WebKit nicht**; erst `aufbau.sh browser` bringt 3 von 3. Welche
Arbeitsmittel eine Sitzung vorfindet, hängt also davon ab, welche der beiden
Dateien zuletzt jemand gepflegt hat.

**PK-02 legt beide zu einer zusammen** (`tools/sandbox/aufbauen.sh`);
`session-start.sh` ruft sie dann mit `web`, `tools/containeraufbau/` fällt
weg. Bis dahin gilt: Wer drei Engines braucht, fährt `aufbau.sh browser`
nach.

---

## 2. Die vier Ausbaustufen und das Modul

*Entsteht mit PK-02.*

`tools/sandbox/aufbauen.sh <web|android|uhr|alles|plattform>` stellt eine
fest beschriebene Ausbaustufe her, idempotent, und **misst nach, was
steht**.

| Stufe | Enthält | Wofür |
|---|---|---|
| `web` | MariaDB 10.11, PHP 8.4, PHP-Server mit TLS, **drei** Engines (nachgemessen, nicht angenommen), Python-Pakete, Referenzbestand, die sieben Umgebungswerte geprüft | jede Änderung unter `server/`, `docs/`, `tools/` |
| `android` | `web` plus Android-SDK 36, JDK 21, Emulator-Abbild | Änderungen unter `android/` |
| `uhr` | `web` plus Uhr-SDK, Gerätedateien, Simulator-Bibliotheken | Änderungen unter `watch/` |
| `alles` | alle drei plus das Modul `plattform` | Hauptstufe, Abnahmen |
| Modul `plattform` | PHP 8.3.33, MariaDB 10.6, MySQL 8.0, MySQL 8.4.0 | Hauptstufe; einzeln nachladbar |

Der Hook ruft `web`. Wer mehr braucht, ruft nach.

### 2.1 Die Nachweistabelle

Jeder Lauf endet mit einer Tabelle, die **je Stück die Fassung nennt** — die
drei Engines **einzeln**, die sieben Umgebungswerte mit ihrer Länge, nie mit
ihrem Wert. Der Grund steht in `Pruefablauf.md` 6.5: „3 Browser da" sagt
nicht, welcher fehlt, und es fehlt immer nur einer. Der Prüfbericht führt
diese Tabelle mit (`Pruefablauf.md` 5).

### 2.2 Das Modul `plattform` — Fassungen und Wege

Am 21.09.2026 einmal von Hand nachgebaut und gemessen; jede Datenbankzeile
mit `tools/schemaprobe/` geprüft (**19 Prüfungen, 0 Fehlschläge**).

| Stück | Weg | Dauer |
|---|---|---|
| PHP 8.3.33 | Abbild `php:8.3.33-cli` plus Dockerfile mit fünf Erweiterungen (`pdo_mysql`, `mysqli`, `zip`, `intl`, `gd`); die Debian-Quellen müssen im Abbild auf `https://` stehen, weil der Proxy nur HTTPS durchlässt | Holen 10 s, Bau 43 s |
| PHP 8.3.33, Ausweich | `git clone --branch php-8.3.33`, `make -j4` | 266 s |
| Anwendung unter PHP 8.3.33 | `lokal_einrichten.sh` mit `/opt/php83`; Anwendung im Behälter gegen die Datenbank des Wirts | 11 s; `login.php` antwortet 200 |
| MariaDB 10.6.23 | Ubuntu-22.04-Pakete nach `/opt/mariadb106`, Port 3310 | Laden und Start 30 s |
| MySQL 8.0.46 | Ubuntu-24.04-Pakete nach `/opt/mysql80`, Port 3307 | Start 2 s |
| MySQL 8.4.0 | Abbild `mysql:8.4.0` — **der einzige Weg** zu dieser Fassung | Holen 10 s, bereit nach 6 s |
| drei Engines | `aufbau.sh browser` | 16 s |

**Warum Ubuntu-Pakete, wo es geht:** Docker Hub drosselt anonyme Abrufe
(gemessen: 429 nach rund acht Abrufen), die Ubuntu-Quellen nicht. Deshalb
wird der Behälter nur dort genommen, wo es keinen anderen Weg gibt — MySQL
8.4.0 und PHP 8.3.

**Warum die Matrix überhaupt:** Staging läuft auf **MySQL 8.4.10**,
Produktiv auf **MariaDB 10.11.14**. Am 21.09.2026 scheiterte ein Export auf
der einen und lief auf der anderen, und keine der beiden Anlagen hatte je
gesagt, dass sie verschieden sind. Die Matrix ist die Stelle, an der das
auffällt, bevor es ausgeliefert ist.

---

## 3. Hochfahren

*`tools/sandbox/hochfahren.sh` entsteht mit PK-02.* Er fasst zusammen, was
heute drei Aufrufe sind, gibt einen Rückgabewert und schaltet mit
`--php 8.3` auf das Abbild um.

**Heute, bis PK-02:**

```
sh tools/containeraufbau/aufbau.sh alles
sh tools/referenzdatensatz/einspielen/lokal_einrichten.sh
sh tools/referenzdatensatz/einspielen/lokal_starten.sh
```

---

## 4. Die sieben Umgebungswerte

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
| Staging und dessen Webmail | |

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

Das wird mit **PK-02** eine Funktion in `tools/motor.mjs` (heute exportiert
die Datei `MOTOREN`, `motorWahl()` und `starten()` und kennt keine
Anmeldung).

### 5.3 Der Dialog „Schlüsselblatt bestätigen"

Er erscheint alle drei Monate nach der Anmeldung. **Jedes Werkzeug, das sich
anmeldet, muss ihn mit „Später" schließen können** — sonst bleibt es alle
drei Monate an einer Stelle hängen, die mit seiner Messung nichts zu tun
hat, und meldet einen Fehlschlag, den niemand zuordnet. Die gemeinsame
Anmeldefunktion in `tools/motor.mjs` übernimmt das mit PK-02.

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

---

## 7. Herkunft

Konzept PK, freigegeben am 21.09.2026 (E-PK-12, E-PK-29, E-PK-30). Die
Zahlen sind am 21.09.2026 gemessen, die des Moduls `plattform` in einem
Nachbau von Hand. Der Werdegang steht in den Commit-Nachrichten der
PK-Pakete, im Changelog und im Backlog — nicht hier (`Pruefablauf.md` 1,
Grundsatz 6).
