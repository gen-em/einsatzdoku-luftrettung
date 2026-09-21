# Konzept PK — Prüfkette: jede Prüfung einmal, an ihrer Stelle

**Kürzel:** `PK`. Arbeitspakete `PK-01 …`, Meilensteine der Betreiberin
`PK-M1 …`, Entscheidungen `E-PK-01 …`, Befunde `F-PK-01 …`, Prüfpunkte
`P-PK-01 …`, Commit-Nachrichten beginnen mit dem Paket (`PK-03: …`).
**Rahmenplan:** R67 (Auslieferungskette), R84/E-KH-17 (kein Schritt
ungeprobt auf Produktiv — und kein Schritt nur für die Prüfung), E-KH-12
(Überspringen ist rot), R35 (Messstand), R62/K7 (Konzeptablage), `CLAUDE.md`
6 und 7. **Backlog:** Nr. 217, 219, 220, 227, 234, 263, 264 (verwiesen); neue
Nummern vergibt die einspielende Instanz. **Vorbereitung:** Konzeptauftrag
vom 21.09.2026 (Umsetzungssitzung Kette II nach AP7/AP8a), Gespräch mit dem
Auftraggeber am selben Tag (Abschnitt 3.1), Durchsicht aller 48
Werkzeugordner (Abschnitt 3.3).
**Modell:** Konzept Fable, Umsetzung Opus. **Keine Fable-Schritte** in der
Umsetzung (keine Oberfläche, kein Mockup).
**Ablage:** `docs/konzepte/Konzept-PK-Pruefkette.md`; Prüfdokument daneben
(`Pruefdokument-PK-Pruefkette.md`, entsteht mit PK-01).
**Kein Versionssprung im Konzept (K3)** — Versionen vergibt die Umsetzung je
Paket nach `CLAUDE.md` 2. Pakete, die nur `tools/`, `docs/` und `.github/`
anfassen, stufen nichts hoch.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **21.09.2026 — Entwurf, zur Freigabe vorgelegt.** Kein Paket begonnen. |
> | Entschieden | **E-PK-01 bis -12** im Gespräch vom 21.09.2026 (Abschnitt 3.1) — gelten. |
> | Vorgeschlagen | **E-PK-13 bis -22** (Abschnitt 3.2) und die Inventur (Abschnitt 3.3) — zur Bestätigung. Offene Fragen: **F-PK-1 bis -6** (Abschnitt 6). |
> | Nächstes | Freigabe, dann **PK-M1** (Zweigschutz, Betreiberin, unabhängig von allem) und **PK-01**. |
> | Hakt | nichts; M1 der Kette II (erster grüner Produktivlauf) bleibt davon unberührt und läuft parallel. |
>
> **Stand der Umsetzung**
>
> | Paket | Stand | Version | Commit | Abnahmezahlen |
> |---|---|---|---|---|
> | PK-M1 Zweigschutz und Merge-Recht | offen (Betreiberin) | — | — | — |
> | PK-01 Regeldokumente | offen | | | |
> | PK-02 Sandbox-Setup | offen | | | |
> | PK-03 Prüfstand-Befehl | offen | | | |
> | PK-04 Inventur umsetzen | offen | | | |
> | PK-05 Tor umbauen | offen | | | |
> | PK-06 Staging verschlanken | offen | | | |
> | PK-M2 Erster Durchlauf der neuen Kette | offen (Betreiberin) | | | |
> | PK-07 Abschluss | offen | | | |

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
| 2. Was bleibt in Stufe 2, was misst sie noch, das lokal nicht messbar ist? | E-PK-01, E-PK-17, PK-06 |
| 3. Wie wird aus „ich habe gemessen" ein Riegel? | E-PK-05, E-PK-06, Abschnitt 2.4 |
| 4. Welche Schranke verhindert ungetesteten Code? | E-PK-04, E-PK-06, PK-M1 |
| 5. Was kostet es, was spart es? | Abschnitt 5 |

**Was dieses Konzept liefert:** Befund mit Zahlen, die Prüfkette in fünf
Stationen, die Entscheidungen, eine Inventur aller 48 Werkzeuge mit Urteil,
sieben Arbeitspakete und zwei Meilensteine, die zwei neuen Regeldokumente
`docs/Pruefablauf.md` und `docs/Sandbox-Setup.md`.

**Was es nicht liefert:**

- **Keine Änderung unter `server/`.** Alles liegt in `tools/`, `.github/`,
  `.claude/` und `docs/`. Eine Ausnahme wäre begründungspflichtig.
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
| Nur in GitHub sinnvoll: Umgebungswert doppelt (braucht den Secrets-Kontext), Schemaprobe gegen MySQL 8.4.0 und MariaDB 10.6 (Dienstbehälter) | 3 | Sekunden bis 1 min | nein |
| Teuer: Android-Bau, Uhr Stufe I | 2 | 7 min plus 35 min | ja, mit nachgeladenen SDKs |

Gemessen an den letzten fünfzehn Läufen: auf dem Arbeitszweig rund 1 min
(Bereichserkennung überspringt Android und Uhr), **auf `main` und bei jedem
Pull Request 40 bis 51 min**, weil dort immer alles gemessen wird. Der
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
| Kreisläufe csv und edbak | Backup rein, Backup raus, Vergleich | nur als Plattformprobe (PHP 8.3) |
| Bilderlauf, 62 Seiten in 8 Breiten | Überlauf, Konsolenfehler, Knopfhöhen, Karten | nein, das ist Markup und CSS |
| Messstand-Hinweis | nichts, ein Satz in der Zusammenfassung | — |

**Integritätswache (`integritaet.yml`)**: täglich und nach jeder
Auslieferung. Bleibt unverändert.

**Was in GitHub nicht existiert: der Zweigschutz auf `main`.** Gemessen am
16.09.2026 `protected: false`, Zuarbeit Z4 der Kette II ist offen. Damit ist
Stufe 1 bis heute eine Auskunft und kein Riegel (Rahmenplan 6b). **Das ist der
eine echte Riegel der ganzen Anordnung, und er ist nicht eingeschaltet.**

### 1.3 Was der Container kann — gemessen an diesem

| | Container (21.09.2026) | Staging (lima-city) | Produktiv (Plesk) |
|---|---|---|---|
| PHP | **8.4.19** | 8.3.33 | 8.3.33 |
| Datenbank | MariaDB 10.11 (installiert, nicht gestartet) | ⬚ | MariaDB 10.11.14 |
| Node / Python | 22.22 / 3.11 | — | — |
| Browser | Chromium, Firefox, WebKit (Playwright 1.56) | — | — |
| Android-SDK | fehlt, nachladbar (`aufbau.sh android`) | — | — |
| Uhr-SDK | fehlt, nachladbar (`CIQ_GERAETE_URL` ist gesetzt) | — | — |
| Kerne / Speicher | 4 / 15 GB | — | — |
| Erreicht die Anlagen? | **nein** (Egress-Proxy 403) | — | — |

Der Container erreicht die entfernten Anlagen nicht; eine **lokale**
Installation ist etwas anderes, und die ist möglich. Alle PHP-Erweiterungen
der Anwendung sind da. **Der eine Unterschied, der zählt, ist PHP 8.4 gegen
8.3.** Er ist der Grund, warum Stufe 2 nicht ganz fällt (E-PK-17).

### 1.4 Das Lehrstück

Der Kreislauf `edbak` gegen Staging kam am 21.09.2026 zum ersten Mal über
die Anmeldung hinaus (Lauf 35585472939): Konto angelegt, Backup eingespielt,
106 Einsätze. Dann sollte er das frische Konto sichern, und der Download kam
nicht. Nach 15 Minuten ein Satz: `Timeout 900000ms exceeded while waiting for
event "download"`. Nicht, ob der Export angelaufen war, nicht, wie weit, nicht,
ob der Browser einen Fehler geworfen hatte. Drei Läufe, bis das Prüfmittel
sprechend gemacht war und den HTTP 500 mit Fehlerkennung `097D7622` nannte.
Die Kennung liegt bis heute nicht ausgelesen in einem Webspace-Protokoll.

**Was das Lehrstück belegt:** Nicht, dass die Kette falsch ist, sondern dass
sie der falsche Ort für die **Arbeit** ist. Fehler finden ist Arbeit; sie
gehört dorthin, wo ein Umlauf Sekunden kostet und das Protokoll auf der
Platte liegt. Die Kette ist der Ort für den **Riegel**: kurz, unabhängig,
und nur für das, was die Arbeit nicht selbst belegen kann.

### 1.5 Woher die Doppelung kam, und was davon trägt

Die Umsetzungsinstanz hatte vier Gründe genannt, warum Stufe 1 wiederholt,
was lokal ohnehin läuft:

| Grund | Trägt er? |
|---|---|
| 1. Mehrere Instanzen parallel, die Betreiberin committet von Hand | Ja — aber das erfüllt ein Tor **beim Pull Request**, nicht ein Lauf bei jedem Push (E-PK-04). |
| 2. Ich messe den falschen Zeitpunkt (O9c: Wortliste vor der letzten Änderung) | Ja — und genau das fängt ein Prüfbericht, der an den Baum-Hash gebunden ist (E-PK-05). |
| 3. Ich irre mich messbar oft | Ja — deshalb liest das Tor den Bericht gegen, statt ihm zu glauben. Für die billigen Riegel ist die Gegenlesung ein Lauf von 25 s. |
| 4. Meine Messung ist ein Bericht, die Kette ist ein Riegel | **Das ist der tragende Grund**, und er gilt nur, wenn der Riegel geschlossen ist — heute ist er es nicht (1.2, letzter Absatz). |

Keiner der vier Gründe verlangt, dass **teure** Messungen (Android, Uhr,
Bilderlauf, Kreisläufe) in der Kette laufen. Alle vier sind mit einem
schnellen Tor beim PR und einem gegengelesenen Bericht erfüllt.

### 1.6 Zwei Beschaffungswege mit zwei Listen

`.claude/hooks/session-start.sh` und `tools/containeraufbau/aufbau.sh`
beschaffen beide, was der Container nicht mitbringt — mit **verschiedenen
Paketlisten** (der Hook nennt sechs WebKit-Bibliotheken, das Skript vier
andere; das Skript kennt das Android-SDK, der Hook nicht). Welche
Arbeitsmittel eine Sitzung vorfindet, hängt davon ab, welches von beiden
zuletzt jemand gepflegt hat. Genau die Zufälligkeit, die der Auftraggeber
abstellen will (Abschnitt 2.3).

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
   Anlage geht; eine Hauptstufe auch die Mengen (2.2).
5. **Ein Prüfmittel braucht einen Fehler.** Jedes Werkzeug nennt in einer
   Zeile, welchen Fehler es gefangen hat oder hätte fangen müssen. Ohne diese
   Zeile steht es auf der Streichliste (3.3).
6. **Kein stilles Überspringen, keine grüne Zahl ohne Gegenstand** — das
   bleibt aus `CLAUDE.md` 6 und E-KH-12 unverändert. Neu ist nur, dass es
   weniger Stellen gibt, an denen es gelten muss.

### 2.2 Fünf Stationen

| Station | Wann | Wer | Was dort geprüft wird | Dauer (Ziel) |
|---|---|---|---|---|
| **A Arbeit** | während der Entwicklung | die Instanz im Container | Browser, Emulator, Simulator, die Proben, die zur Änderung gehören | nach Bedarf |
| **B Prüfstand** | vor dem PR, **ein Befehl** | dieselbe Instanz | lokale Installation hochfahren, Bestand, die Proben der Stufe (klein/neben/haupt), Android- und Uhr-Bau, die billigen Riegel; Ergebnis ist der **Prüfbericht** in der Commit-Nachricht | klein 5 min, neben 15 min, haupt 45 min |
| **C Tor** | beim Pull Request und auf `main` | GitHub | die billigen Riegel als Gegenlesung des Berichts, Schemaprobe gegen MySQL, Bericht passt zum Baum und zur Berührung. **Rot heißt kein Merge**; der Merge ist Sache der Betreiberin | 1 bis 2 min |
| **D Staging** | nach dem Merge | GitHub | Auslieferung (unverändert), Antwortprobe, Punktdateien, **ein** edbak-Kreislauf als Plattformprobe auf PHP 8.3 | unter 10 min |
| **E Produktiv** | beim Tag, nach Freigabe | GitHub | dieselbe Auslieferung, Backup-Tor, Versionsprüfung, Wache | wie heute |

**Die drei Stufen des Prüfstands** richten sich nach `server/version.php`
im Diff gegen `main` (die Zählweise aus `CLAUDE.md` 2) und nach der
Berührung:

| Stufe | Auslöser | Umfang |
|---|---|---|
| **klein** | Korrekturstufe `a.a.Y`, oder kein Versionssprung | billige Riegel; dazu je berührter Datei die zugeordneten Proben (`pruefablauf.json`, 2.4); Bilderlauf nur der berührten Seiten in drei Breiten, Chromium |
| **neben** | Nebenstufe `a.X.a` | alles ohne Anlage: alle Proben gegen die lokale Installation, beide Kreisläufe, Bilderlauf aller Seiten in acht Breiten, Chromium; Android und Uhr, wenn berührt |
| **haupt** | Hauptstufe `X.a.a`, oder auf Anforderung `--stufe haupt` | wie neben, dazu Messstand, Bilderlauf mit drei Motoren über die Risikoliste, Anteilprobe, Verbindungsprobe, Uhr Stufe II |

Der Befehl bestimmt die Stufe selbst und sagt sie; `--stufe` überschreibt.
Eine Instanz, die eine Hauptstufe mit `--stufe klein` fährt, bekommt das im
Bericht vermerkt, und das Tor liest es (E-PK-06).

### 2.3 Die Sandbox: vier Konfigurationen

`docs/Sandbox-Setup.md` beschreibt **vier** fest definierte Ausbaustufen; die
Beschaffung (`tools/sandbox/aufbauen.sh <konfiguration>`) stellt sie her
und misst nach, was steht. Der SessionStart-Hook ruft dieselbe Beschaffung
mit der Vorgabe `web`; wer Android oder Uhr anfasst, ruft sie mit der
passenden Konfiguration nach.

| Konfiguration | Enthält | Wofür |
|---|---|---|
| `web` | MariaDB, PHP-Server mit TLS, drei Browser-Engines, Python-Pakete, Referenzbestand | jede Änderung unter `server/`, `docs/`, `tools/` |
| `android` | `web` plus Android-SDK 36, JDK 21, Emulator-Abbild | Änderungen unter `android/` |
| `uhr` | `web` plus Connect-IQ-SDK, Gerätedateien, Simulator-Bibliotheken | Änderungen unter `watch/` |
| `alles` | alle drei | Hauptstufe, Abnahmen |

Die Beschaffung ist **eine** Datei; `session-start.sh` und
`containeraufbau/aufbau.sh` gehen darin auf (F-PK-01 in 1.6). Sie endet mit
einer Nachweistabelle (Fassung je Stück), die der Prüfbericht mitführt.

**Benannte Grenzen der Sandbox**, die `Sandbox-Setup.md` vorn trägt:
PHP 8.4 statt 8.3 (Station D misst das), MariaDB 10.11 statt der
MySQL-Matrix (Station C misst das), kein echter Data Layer, kein echtes
Gerät, kein Hoster-Apache (Station D misst `.htaccess`), kein Netz zu den
Anlagen.

### 2.4 Der Prüfbericht — vom Bericht zum Riegel

Der Prüfstand-Befehl schreibt am Ende einen Block, der in die
Commit-Nachricht gehört und maschinell lesbar ist:

```
Prüfstand: neben · Baum a1b2c3d… · Konfiguration web
  syntax=php:412/0,py:48/0  textprobe=0/0  vollstaendigkeit=ok
  kreislauf=edbak:0,csv:0  bilderlauf=62/0/0/0  proben=ingest:10/10,spur:5/5
  android=nicht berührt  uhr=nicht berührt
```

Das Tor liest ihn und wird rot, wenn

- der **Baum-Hash** nicht der Baum des Commits ist (gemessen vor der letzten
  Änderung — der O9c-Fehler),
- die **Stufe** kleiner ist als die Versionsstufe im Diff verlangt,
- eine **berührte** Fläche als „nicht berührt" gemeldet ist (`android/`
  geändert, `android=nicht berührt`),
- ein **billiger Riegel** im Tor eine andere Zahl liefert als der Bericht.

Was das Tor **nicht** kann: einen teuren Lauf nachrechnen. Für Kreisläufe,
Bilderlauf, Android und Uhr ist der Bericht ein Nachweis, kein Riegel. Das ist
die ehrliche Grenze: Die Sandbox ist die geprüfte Partei, und ein Nachweis
aus ihrer Hand bleibt einer aus ihrer Hand. Der Auftraggeber hat das als
ausreichend bestätigt (E-PK-05). Zwei Dinge halten die Grenze klein: Der
Block wird vom Befehl **erzeugt**, nicht geschrieben; und der Merge bleibt ein
Mensch, der den Block liest.

`tools/pruefstand/pruefablauf.json` ist die eine Zuordnung
**Berührung → Probe** (Muster auf Dateipfade, je Muster die Werkzeuge und die
Stufe, ab der sie laufen). `docs/Pruefablauf.md` wird daraus **erzeugt**, wie
die Tabellen in `Design.md` — eine Liste, die von Hand gepflegt wird, altert
in eine Richtung (Wortliste Bereich c, 17.09.2026).

---

## 3. Entscheidungen

### 3.1 Aus dem Gespräch vom 21.09.2026 — entschieden

**E-PK-01 — Stufe 2 wird auf das reduziert, was nur die echte Anlage zeigt.**
Antwortprobe, Punktdateien, ein edbak-Kreislauf. Bilderlauf und csv-Kreislauf
wandern in den Prüfstand. Ganz fallen darf Stufe 2 nicht: PHP 8.3, der
Hoster-Apache und Nr. 234 haben keinen anderen Ort.

**E-PK-02 — Android und Uhr werden lokal gebaut.** Dort wird entwickelt, die
SDKs sind Teil der Konfigurationen `android` und `uhr`. Die Frage, ob die
Kette sie zusätzlich bauen soll, ist E-PK-13.

**E-PK-03 — Streichliste mit Begründung; gestrichen wird wirklich.** Kein
Archiv, kein „ruhend". Die Git-Historie behält, was war.

**E-PK-04 — Stufe 1 läuft beim Pull Request und auf `main`, sonst nirgends.
Pushes sind Pushes.** Kein Riegel vor dem Push, kein Hook, der `git push`
aufhält. Ein Push auf einen Arbeitszweig löst nichts aus. Der Merge nach
`main` ist ausschließlich Sache der Betreiberin (PK-M1).

**E-PK-05 — Der Prüfbericht in der Commit-Nachricht reicht als Nachweis.**
Erzeugt vom Befehl, gebunden an den Baum-Hash, vom Tor gegengelesen (2.4).
Kein signiertes Artefakt, keine Prüfsummendatei im Repositorium.

**E-PK-06 — Das Tor ist die Gegenlesung, nicht die Wiederholung.** Es fährt
die billigen Riegel (25 s) und hält sie gegen den Bericht; teure Messungen
wiederholt es nicht.

**E-PK-07 — Die Kommentarprosa der Kette wird gelöscht.** 1 506 von 2 830
Zeilen sind Erzählung. Sie wandert nicht nach `Technik.md`, sie fällt. Was
bleibt: **ein Satz** an jeder Stelle, die eine Falle beschreibt, die sonst
jemand wieder einbaut (`pipefail`, `A && B || C`, die Punktdatei-Falle, der
Jobname als Kupplung). Die Historie hat Git.

**E-PK-08 — Die Wortliste wird herabgestuft und zur Textprobe erweitert.**
Sie läuft einmal, im Tor, in Sekunden. Rot ist nur ein **neuer Treffer**;
eine ungenutzte Ausnahme ist eine Warnung, kein Fehlschlag. Aus `CLAUDE.md`
fällt die Pflicht, sie bei jeder Textänderung von Hand zu fahren. Dazu kommen
Regeln, die nicht nur den Luftrettungssinn verfolgen, denn die Anwendung
ist auf alle Rettungsmittel migriert: geschlechtsneutrale Hausform, keine
echten E-Mail-Adressen außerhalb einer Erlaubnisliste, keine echten Adressen
(URLs) außerhalb der in `Lizenzen.md` genannten, keine echten Namen und
Rufnamen (die Liste aus `referenzdatensatz/quelldaten/pruefen.py` zieht um).
Werkzeug: `tools/textprobe/`, eine `regeln.json`, ein Bericht.

**E-PK-09 — Benennung.** Kürzel je Konzept, `PK-NN Schlagwort` für Pakete,
`PK-MN` für Meilensteine, `E-/F-/P-PK-NN`; Commit-Nachrichten beginnen mit
dem Paket. Nummern sind zweistellig und werden nicht wiederverwendet. Ein
Paket heißt überall gleich. Gilt ab diesem Konzept; `CLAUDE.md` 7 übernimmt
die Regel in PK-01.

**E-PK-10 — Zwei neue Regeldokumente:** `docs/Pruefablauf.md` (wo läuft
was, wann, wie — erzeugt aus `pruefablauf.json`) und `docs/Sandbox-Setup.md`
(die vier Konfigurationen, die Grenzen, der Nachweis). `CLAUDE.md` 6 wird auf
einen Verweis und die sechs Grundsätze aus 2.1 gekürzt.

**E-PK-11 — Der Prüfumfang folgt der Versionsstufe** (2.2). Der Befehl liest
die Stufe aus dem Diff; `--stufe` überschreibt und wird im Bericht genannt.

**E-PK-12 — Vier fest definierte Sandbox-Konfigurationen** (2.3), eine
Beschaffung, ein Nachweis.

### 3.2 Vorschläge — zur Bestätigung

**E-PK-13 — Die Kette baut Android und Uhr nicht mehr.** Begründung: Beide
werden von der Kette **nicht ausgeliefert**; die Betreiberin baut und
signiert sie auf ihrem Rechner (E-S4-16) — dort läuft der Bau ohnehin ein
zweites Mal, mit dem Schlüssel, den die Kette nie sieht. Ein dritter Bau im
Tor bringt nur die Gegenlesung „wurde gebaut", und die leistet der Bericht:
Ist `android/` berührt und der Bericht sagt `android=nicht berührt` oder
fehlt, ist das Tor rot — in Sekunden statt in 42 Minuten. Preis: Ein Bericht,
der einen grünen Bau behauptet, den es nicht gab, kommt durch. Der Merge ist
ein Mensch, und der nächste Bau ist die Signatur. **Verworfen:** Bau im PR
nur bei Berührung — technisch sauber (die Bereichserkennung leistet es), aber
es ist die dritte Messung derselben Sache, und der Auftraggeber fragt zu
Recht, welchen Mehrwert sie hat. Falls die Betreiberin den Bau im Tor
behalten will (F-PK-2), dann so.

**E-PK-14 — Der Bilderlauf bleibt, abgestuft.** Er ist das Werkzeug, das die
Karten am `body` (Nr. 225) und den WebKit-Überlauf (Nr. 185) gefunden hat,
und er ist der Nachweis der Bedienhöhen. Aber 62 Seiten × 8 Breiten × 3
Motoren nach jedem Paket ist der Preis, den der Auftraggeber meint. Neu:
klein = berührte Seiten (Zuordnung Seite → PHP-Datei in `seiten.json`) in
drei Breiten (360, 1024, 1920), Chromium; neben = alle Seiten, acht Breiten,
Chromium; haupt = dazu Firefox und WebKit über die Risikoliste. Aus Stufe 2
fällt er ganz (E-PK-01). Die Gegenprobe gegen doppelte Bilder (`md5sum`)
wird Teil des Laufs statt ein Handgriff in der Anleitung.

**E-PK-15 — Die Klickprobe wird zur Bedienprobe verschlankt.** Befund aus der
Durchsicht: Sie ist das einzige Werkzeug, das Elemente bedient — und sie hat
die beiden Fehler, derentwegen es sie gibt (PS-2, Nr. 148), nicht selbst
gefunden; ihre Funde waren Fehler in ihr selbst und Demo-Reset-Kollisionen.
Sie wächst je Arbeitspaket um eine Datei unter `wege/` (heute 10 Dateien, 48
Wege) und braucht länger als das 30-Minuten-Fenster des Demo-Resets. Neu: die
48 Wege werden zu **einem** festen Satz je Seite zusammengelegt (keine
AP-Dateien mehr), gefahren nur bei neben/haupt, Chromium, eine Breite je
Eingabeart; die Zuordnung Seite → Weg steht in `pruefablauf.json`. Der
Demo-Reset wird vor dem Lauf über `demo_kennzeichnen.php` in die Zukunft
gesetzt, wie es der Referenzdatensatz tut. **Verworfen:** streichen — der
Fehler „`click` kommt bei gehaltener Maus nie" (PS-2) ist mit Bildern nicht
zu sehen, und ein zweites solches Loch hätte keinen Wächter.

**E-PK-16 — Die Vollständigkeitsprüfung verliert die Symbolzählung.** Von
319 Befunden sind 299 Satzzeichen in Prosa (Nr. 227, ausgezählt in
`pruefung.yml`); die Schwelle „genau 398" ist eine Zahl, die bei jedem Paket
nachgezogen wird und nichts über Symbole sagt. Die Prüfungen „Klasse ohne
Regel", „Wert außerhalb `:root`", „`style=`-Attribut", „fremde Quelle" und 4b
(Tonfehler) bleiben und laufen im Tor gegen **null**, nicht gegen eine
Schwelle. Nr. 227 wird damit erledigt, nicht vertagt.

**E-PK-17 — Stufe 2 fährt genau drei Schritte:** Antwortprobe, Punktdateien,
edbak-Kreislauf (`--frisch`, mit Job-Pause). Der csv-Kreislauf misst
dasselbe Plattformverhalten ein zweites Mal und fällt. Der Messstand-Hinweis
fällt (der Satz steht in `Pruefablauf.md`). Zeitgrenze des Jobs 20 min statt
45. Playwright und `cryptography` werden über den Aktions-Cache gehalten
statt je Lauf geladen — oder, wenn das die Bauform der Aktion nicht hergibt,
bleibt es beim Laden und die Zeit wird im Prüfdokument genannt.

**E-PK-18 — Stufe 1 bekommt zwei Schritte dazu, die heute fehlen:** die
Linkprobe (Bruchteil einer Sekunde, nur Quelltext, fand Nr. 148 und 151 —
und hängt nirgends) und die Rechtstext-Angriffsprobe (reine Funktion, 81
Fälle, Sekunden). Beides sind billige Riegel im Sinn von 2.1.

**E-PK-19 — Das Tor liest den Bericht mit einem Werkzeug, nicht mit Bash**
(`tools/pruefstand/bericht.py lesen`, mit Selbstprobe), aus demselben Grund
wie Backup-Tor und Freigabe (E-P5a-12): Was einen Merge verhindern soll, muss
ohne Netz nachweisbar sein.

**E-PK-20 — `pruefung.yml` behält die Bereichserkennung nicht.** Sie war die
Antwort auf 42 Minuten Android und Uhr; mit E-PK-13 gibt es nichts mehr zu
überspringen. Der Schritt „Fassungen nennen" bleibt (drei Zahlen, eine
Auskunft). Erwartete Laufzeit des Tors: unter zwei Minuten, gemessen wird sie
in PK-05.

**E-PK-21 — Stufe 2 bekommt einen benannten leeren Platz für Nr. 234**
(„Deploy, Anmeldung, `update.php`"): einen Schritt, der heute nur sagt, dass
er nicht gebaut ist, damit die Lücke im Lauf sichtbar bleibt statt in einer
Backlog-Nummer. Gebaut wird er nicht in diesem Konzept.

**E-PK-22 — Die Selbstprobe-Pflicht gilt nur für Werkzeuge, die einen Merge
oder eine Auslieferung verhindern** (Tor, Freigabe, Zielprobe, Zustand,
Bericht, Schemaprobe, die Tokenizer-Prüfungen in Stufe 1). Proben in Station
B brauchen keine; ihre Zahl steht im Bericht, und ein Werkzeug, das immer
grün meldet, fällt bei der nächsten echten Änderung auf. Heute tragen 28 von
48 eine Selbstprobe, und die Pflege der Selbstproben ist ein Teil der
Bürokratie, um die es geht.

### 3.3 Inventur — 48 Werkzeuge, ein Urteil je Werkzeug

Grundlage: die Durchsicht aller Anleitungen am 21.09.2026 (65 Dateien, drei
Durchgänge). Urteile: **bleibt** (unverändert, bekommt eine Station),
**verschlanken**, **aufgehen in** (wird Teil eines anderen), **streichen**.
Die Spalte „Fehler" ist die Zeile aus Grundsatz 5.

| Werkzeug | Urteil | Station | Fehler, den es gefangen hat | Grund / Bemerkung |
|---|---|---|---|---|
| abmelde-probe | **aufgehen in** fristprobe | B klein bei `assets/*.js` (Sitzung) | Nr. 22: `edk_neu` blieb nach dem Abmelden liegen (7.2.0) | Einmalbeleg; die eine Erwartung wird ein Fall der Fristprobe |
| anteilprobe | bleibt | B haupt, klein bei `kdf`/`unlock`/`serverkrypto` | dreizehn Fallen, zehn davon im Prüfmittel; S10-Kern | Teile E bis G brauchen Browser; nur haupt |
| containeraufbau | **aufgehen in** `tools/sandbox/aufbauen.sh` | A | Wegwerf-Container ohne MariaDB (13.09.2026) | zwei Beschaffer mit zwei Listen (1.6) |
| containerprobe | bleibt | B klein bei `spur_lib`, `crypto.js`, `Backup-Format` | Fassung 4 der Teile (S2/AP5) | drei Umsetzungen gegen eine Wahrheit |
| cspprobe | bleibt | `pruefen.php` C; `browserprobe.mjs` B klein bei `kopf`/CSP | 15.09.2026: Meldeweg tot, „0 Berichte bei zwei Verstößen" | |
| design | bleibt | A (Erzeuger) | F-P3-BC: zwei ungenutzte Token, eine zu schmale Leiste | |
| eingabe-probe | bleibt, als Handwerkzeug | A (Uhr) | Eingabezuordnung je Gerät | kein Prüfmittel, sagt es selbst; wandert nach `tools/uhr-pruefstand/eingabe/` |
| freigabeprobe | bleibt | B klein bei `freigabe`/`schluessel` | F-S2-F: der Schlüsselkasten erschien nie — „hat auch nie funktioniert" | |
| fristprobe | bleibt (+ abmelde) | B klein bei `keyguard`/Sitzung | R44-Abnahme belegte nichts; 17 → 1 Neu-Entpackung | |
| geraetemodelle | bleibt | A (Erzeuger) | Teilenummer an zwei Geräten | |
| geraeteprobe | bleibt | B klein bei `pair`/`geraete` | Edge, das sich „uhr" nennt | |
| gpxprobe | bleibt | B klein bei `gpx`/`export` | UTF-16-DOCTYPE ging durch (Nr. 130) | |
| ingestprobe | bleibt | B klein bei `ingest`/`validate` | stiller Datenverlust bei „ok" | |
| installweiche | bleibt | C | — (Vorsorge PP-1) | Sekunden, Tokenizer |
| integritaetswache | bleibt | E | B6: täglich rot gegen `main` | unverändert |
| jobprobe | bleibt | B klein bei `jobs_lib` | Huckepack 18 s je Anfrage | |
| jobregister | bleibt | C | Register hinkte dreimal (Nr. 208) | |
| kette | bleibt | D, E | F3 (acht Trennversuche), Nr. 219 | Selbstproben bleiben (E-PK-22) |
| kettenaufrufe | bleibt | C | drei Aufrufe beim ersten Lauf gescheitert (Nr. 217) | wird um `pruefablauf.json` erweitert |
| klickprobe | **verschlanken** → Bedienprobe (E-PK-15) | B neben | keinen der beiden Anlassfehler; eigene Fehler | |
| komplettprobe | bleibt | B klein bei `komplett_lib`/`adminbackup` | Neuanlauf lief in `count(null)`; Teil 8 stürzte (255) | Teil 10 hängt an versandprobe |
| kopplungsprobe | bleibt | B klein bei `pair` | Nr. 178: Rauschregel verschluckte 4 Fehler; Nr. 180 seit 06.09. rot | |
| linkprobe | bleibt, **neu in C** (E-PK-18) | C | Nr. 148, Nr. 151 | hing nirgends |
| logos | bleibt | A (Erzeuger) | ENOENT nach dem NEF-Austausch | |
| mailprobe | bleibt | B klein bei `mail_lib`/`smtp` | `smtp_letzter_fehler()` nannte den vorigen Versuch | |
| maskierungs-probe | **streichen** | — | F-20 (Alter unmaskiert, 7.2.0) | Einmalbeleg; R20 hat den Fall in den Referenzdatensatz übernommen |
| messstand | bleibt | B haupt | F-S2-E: 91 208 Punkte verloren bei „164 übernommen" | R35; Zahlen vom 04.09.2026, seither nicht nachgerechnet |
| migrationsregister | bleibt | C | Hausregel dreimal vergessen | |
| netzprobe | **aufgehen in** uhr-pruefstand (Anleitung) | A (Uhr) | −1001 über HTTP, 405 über TLS mit CA | das Rezept steckt seit 03.09. in `lokal_starten.sh` |
| pruefkonten | bleibt | A (Erzeuger) | — | Handabnahme der NutzerInnen-Liste |
| ratenprobe | bleibt | B klein bei `ratelimit` | die Stufe fiel nie zurück | |
| rechtstexte | bleibt, **neu in C** (E-PK-18) | C | — (81 Angriffsfälle) | reine Funktion, Sekunden |
| referenzdatensatz | bleibt | B (Kern) | F-P1-I (echtes XSS), Nr. 174 | Kreislauf = Herz des Prüfstands |
| s5-anker | **streichen** | — | — | Anleitung: „Die Liste hat ihre Arbeit getan"; Löschung war für S5-Abschluss vorgesehen |
| schemaprobe | bleibt | C (Matrix) | `MANUAL` reserviert in MySQL 8.4.0–8.4.10 (Nr. 238) | |
| screenshots | **verschlanken** (E-PK-14) | B abgestuft | Nr. 185, Nr. 225, F-P3-AQ | fällt aus D |
| sitzungshaertung | bleibt | C | `use_strict_mode` an den zwei falschen Stellen (Nr. 205) | |
| spurprobe | bleibt | B klein bei `spur_lib` | 175 von 181 Spuren: `int` gegen `float` | |
| stilvergleich | bleibt, ruhend | B nur bei `style.css`, Chromium | `.keybox`/`.paircode` (P0/A3) | 16 s; läuft nur bei CSS-Umbau |
| uhr-bilder | bleibt | A (Erzeuger) | 42 von 99 Geräten skaliert | |
| uhr-pruefstand | bleibt (+ netzprobe, + eingabe-probe) | A, B Konfiguration `uhr` | `Devices/Devices/` (03.09.) | Stufe I bei Berührung, Stufe II bei haupt |
| verbindungsprobe | bleibt | B haupt, klein bei `db.php` | 12 × HTTP 500 durch Deadlock (Nr. 210) | |
| versandprobe | bleibt | B klein bei `sicherungsziel` | zwei halb englische Meldungen; `ext/ftp` prüft kein Zertifikat | |
| vollstaendigkeit | **verschlanken** (E-PK-16) | C | F-P3-BA (Export-Knopf 23 px), Nr. 179 | ohne Symbolzählung, gegen null |
| wartungsprobe | bleibt | B klein bei `wartung`/`auth_guard` | F-S8-P-04, Nr. 171 (`auth_salt.php` fehlte jahrelang) | |
| wegwerfdomains | bleibt | A (Erzeuger, Runbook) | — | einzige Datei ohne Anleitung; Kopfkommentar wird LIESMICH |
| wiederherstellungs-probe | bleibt | B klein bei `backup_lib` | Nr. 31, 33, 34, 35 | |
| wortliste | **verschlanken** → textprobe (E-PK-08) | C | B-S4-06, 14 Treffer in den Rechtstexten | |
| `motor.mjs` | bleibt | Baustein | Firefox ohne Voreinstellung: 36-px-Block unsichtbar (Nr. 183) | |

**Streichliste:** maskierungs-probe, s5-anker. **Aufgehen:** abmelde-probe,
containeraufbau, netzprobe, eingabe-probe (verschoben, nicht gelöscht).
**Verschlankt:** klickprobe, screenshots, vollstaendigkeit, wortliste. Von 48
Ordnern bleiben **42**, davon 8 Erzeuger und 34 Prüfmittel. Dazu kommen zwei
neue Ordner: `tools/sandbox/` (Beschaffung) und `tools/pruefstand/` (Befehl,
Bericht, `pruefablauf.json`), und `tools/textprobe/` ersetzt `tools/wortliste/`.

Was **nicht** verschlankt wird, obwohl es viel ist: die zwanzig Handproben
gegen eine Installation (ingest, spur, jobs, kopplung, wartung, …). Jede hat
einen echten Fehler gefangen, jede misst etwas, das kein Kreislauf sieht, und
jede läuft in Sekunden. Ihr Preis war nie die Laufzeit, sondern dass niemand
wusste, wann welche dran ist. Das löst `pruefablauf.json`, nicht das Löschen.

---

## 4. Arbeitspakete

### 4.0 Übersicht, Reihenfolge, Abhängigkeiten

| Paket | Titel | Entscheidungen | nach | berührt | Stopp |
|---|---|---|---|---|---|
| **PK-M1** | Zweigschutz und Merge-Recht (Betreiberin) | E-PK-04 | — (sofort) | GitHub-Einstellungen | — |
| PK-01 | Regeldokumente | E-PK-09, -10 | Freigabe | `docs/Pruefablauf.md`, `docs/Sandbox-Setup.md`, `CLAUDE.md` 6/7, Prüfdokument | — |
| PK-02 | Sandbox-Setup | E-PK-12 | PK-01 | `tools/sandbox/`, `.claude/hooks/`, `tools/containeraufbau/` (weg) | — |
| PK-03 | Prüfstand-Befehl | E-PK-05, -06, -11, -19 | PK-02 | `tools/pruefstand/` | — |
| PK-04 | Inventur umsetzen | E-PK-03, -08, -14, -15, -16, -22 | PK-03 | `tools/` (Streichliste, textprobe, klickprobe, screenshots, vollstaendigkeit) | — |
| PK-05 | Tor umbauen | E-PK-04, -07, -13, -18, -20 | PK-03, PK-M1 | `pruefung.yml` | **ja — F-PK-2 (Android/Uhr im Tor)** |
| PK-06 | Staging verschlanken | E-PK-01, -07, -17, -21 | PK-05 | `auslieferung.yml` (`stufe2`), `ausliefern-lauf.yml` (nur Kommentare) | — |
| **PK-M2** | Erster Durchlauf der neuen Kette (Betreiberin) | alle | PK-06 | PR, Merge, Staging | — |
| PK-07 | Abschluss | — | PK-M2 | `Technik.md` 6, `CLAUDE.md`, Rahmenplan, Backlog, Prüfdokument | Freigabe |

Für jedes Paket gilt `CLAUDE.md` 7 und 8: eines nach dem anderen; Statusblock
und Prüfdokument fortschreiben, dann den Arbeitszweig pushen. **Kein Paket
stuft eine Version hoch** — keines fasst `server/`, `android/` oder `watch/`
an.

### PK-M1 — Zweigschutz und Merge-Recht (Betreiberin)

Die Antwort auf die Frage „kann man den PR auf `main` so setzen, dass nur
ich mergen kann": ja. Zwei Wege, einer davon wird gewählt und in
`Pruefablauf.md` festgehalten:

| Weg | Einstellung | Wirkung |
|---|---|---|
| **klassisch** (Settings → Branches → Rule für `main`) | *Require a pull request* · *Require status checks: `Stufe 1`* · *Restrict who can push to matching branches* → nur die Betreiberin · *Do not allow bypassing* | Ein Merge ist ein Push auf `main`; eingeschränkt ist damit auch der Merge-Knopf. Eine Claude-Instanz (GitHub-App) kann PRs öffnen und wird beim Merge abgewiesen. |
| **Ruleset** (Rahmenplan 6b) | wie dort, dazu *Restrict updates* mit der Betreiberin als einzigem Bypass-Akteur im Modus *pull requests only* | gleich; die Bypass-Semantik ist die Falle aus 6b (3) und muss gemessen werden |

**Abnahme (P-PK-01):** Eine Claude-Instanz öffnet einen PR mit einer
Doku-Zeile und versucht den Merge — **abgewiesen**. Dieselbe Instanz pusht
direkt auf `main` — **abgewiesen**. Die Betreiberin mergt den PR — geht.
Drei Messungen, drei Antworten, im Prüfdokument.

**Warum sofort und vor allem anderen:** Ohne PK-M1 ist jedes Tor dieses
Konzepts eine Auskunft (1.2). Das Paket kostet fünf Minuten in den
Einstellungen und hängt an nichts.

### PK-01 — Regeldokumente

- `docs/Pruefablauf.md` anlegen: die fünf Stationen, die drei Stufen, die
  sechs Grundsätze, die Tabelle Berührung → Probe (vorerst von Hand, ab PK-03
  erzeugt), die Benennungsregel (E-PK-09), die Form des Prüfberichts, was
  nicht geprüft wird und wo es steht (Nr. 234, echte Geräte, echte Ziele).
- `docs/Sandbox-Setup.md` anlegen: vier Konfigurationen, Beschaffungsbefehl,
  Nachweistabelle, die benannten Grenzen (2.3), die Netzregeln des
  Containers (was erreichbar ist, was nicht).
- `CLAUDE.md` 6 auf die sechs Grundsätze und den Verweis kürzen; `CLAUDE.md`
  7 um E-PK-09 ergänzen. Was in 6 heute an Werkzeugerzählung steht (Wortliste
  bei jeder Textänderung, Emulator-Regel, Tag-Rumpf-Muster, Kettenaufrufe),
  wandert nach `Pruefablauf.md` oder in die LIESMICH des Werkzeugs — **einmal**.
- Prüfdokument `Pruefdokument-PK-Pruefkette.md` anlegen (Form nach
  `CLAUDE.md` 7).
- **Abnahme:** Beide Dokumente liegen; `CLAUDE.md` 6 ist um mindestens die
  Hälfte kürzer (heute 166 Zeilen, gemessen); kein Satz steht doppelt
  (Stichprobe: die Emulator-Regel, die Tag-Rumpf-Regel, die Wortlisten-Regel
  — je genau eine Fundstelle in `docs/` und `CLAUDE.md` zusammen).

### PK-02 — Sandbox-Setup

- `tools/sandbox/aufbauen.sh <web|android|uhr|alles>`: eine Beschaffung,
  idempotent, endet mit der Nachweistabelle (Fassung je Stück, drei Engines
  einzeln). `session-start.sh` ruft sie mit `web`; `containeraufbau/` fällt.
- `tools/sandbox/hochfahren.sh`: MariaDB starten, `lokal_einrichten.sh`,
  Referenzbestand, TLS — das, was heute in drei Anleitungen als Reihenfolge
  steht, als ein Befehl mit Rückgabewert.
- Die Grenzen-Tabelle in `Sandbox-Setup.md` gegen den Container **messen**
  (PHP, MariaDB, Playwright-Fassungen) und eintragen.
- **Abnahme:** frischer Container, `aufbauen.sh web && hochfahren.sh` →
  Anmeldeseite antwortet über TLS, Demo-Konto meldet sich an; Dauer gemessen
  und in `Sandbox-Setup.md` eingetragen. `aufbauen.sh android` → `./gradlew
  build` grün; `aufbauen.sh uhr` → `pruefstand.sh reihe` grün. Drei Zahlen.

### PK-03 — Prüfstand-Befehl

- `tools/pruefstand/pruefen.sh [--stufe klein|neben|haupt]`: Stufe aus
  `version.php`-Diff und Berührung ermitteln und sagen; `hochfahren.sh`;
  Proben nach `pruefablauf.json`; billige Riegel; Android/Uhr bei Berührung;
  Bericht erzeugen (`bericht.py schreiben`), Rückgabewert.
- `tools/pruefstand/pruefablauf.json`: Muster → Werkzeuge → Stufe. Dazu
  `bericht.py erzeugen-doku`, das die Tabelle in `Pruefablauf.md` schreibt
  (wie `tools/design/tabellen.py`).
- `bericht.py lesen` mit Selbstprobe (E-PK-19): Baum-Hash, Stufe gegen
  Versionsstufe, Berührung gegen „nicht berührt", Zahlen der billigen Riegel.
- `kettenaufrufe` liest zusätzlich `pruefablauf.json` (jeder genannte Aufruf
  gegen die Schnittstelle des Werkzeugs).
- **Abnahme:** je Stufe ein Lauf mit gemessener Dauer (Ziel 5/15/45 min —
  der Messwert ersetzt das Ziel); der Bericht steht in einer Commit-Nachricht;
  `bericht.py lesen --selbstprobe` fährt die vier roten Lagen aus 2.4 und
  eine grüne; `pruefablauf.json` deckt jede Datei unter `server/` mit
  mindestens einem Muster (gemessen: 0 Dateien ohne Muster).

### PK-04 — Inventur umsetzen

- Streichliste ausführen (maskierungs-probe, s5-anker), Aufgehen ausführen
  (abmelde → frist, netzprobe und eingabe-probe → uhr-pruefstand,
  containeraufbau → sandbox), je mit einem Satz im Changelog-Abschnitt der
  Werkzeuge (`Technik.md` 4.x, wo die Werkzeuge stehen).
- `tools/textprobe/` aus `tools/wortliste/`: Regelklassen Luftbegriffe (die
  99 Ausnahmen wandern mit), Hausform (Liste männlicher Generika; die
  Hausform selbst legt `Design.md` oder `Pruefablauf.md` fest — F-PK-4),
  E-Mail-Adressen (Erlaubnisliste: `example.invalid`, die festen Prüfkonten
  unter `gen-em.org`), Adressen (Erlaubnisliste aus `Lizenzen.md`), Namen
  (Liste aus `quelldaten/pruefen.py`). Ungenutzte Ausnahme = Warnung.
- Klickprobe → Bedienprobe (E-PK-15), Bilderlauf abgestuft (E-PK-14),
  Vollständigkeit ohne Symbolzählung gegen null (E-PK-16); jede LIESMICH
  bekommt als erste Zeile nach dem Aufruf die Fehler-Zeile aus 3.3.
- Selbstproben, die E-PK-22 nicht mehr verlangt, **bleiben, wo sie sind**,
  werden aber nicht mehr in `CLAUDE.md` oder im Tor genannt; neue Werkzeuge
  in Station B brauchen keine.
- **Abnahme:** `ls -d tools/*/ | wc -l` = 43 (42 plus `pruefstand/`, minus
  `wortliste/`, plus `textprobe/`, plus `sandbox/` — die Zahl wird beim Bauen
  nachgezählt und hier ersetzt); Textprobe: 0 neue Treffer auf `main`, und
  eine Gegenprobe je neuer Regelklasse (ein eingebautes „Nutzer", eine echte
  Adresse, ein Rufname → je 1 Treffer); Vollständigkeit auf `main`: 0.

### PK-05 — Tor umbauen

- `pruefung.yml`: Auslöser `pull_request` und `push: main` (E-PK-04);
  Android/Uhr und Bereichserkennung raus (E-PK-13, -20); Bericht-Gegenlesung
  rein (`bericht.py lesen`); Linkprobe und Rechtstextprobe rein (E-PK-18);
  Wortliste → Textprobe; Vollständigkeit ohne Schwelle; Kommentare auf je
  einen Satz (E-PK-07).
- Der Zweigschutz aus PK-M1 nennt weiterhin den Job `Stufe 1`; der Name
  bleibt.
- **Stopp vor dem Bauen:** F-PK-2 (Android/Uhr im Tor) muss entschieden
  sein.
- **Abnahme:** ein PR ohne Bericht → rot mit Ansage; ein PR mit Bericht
  gegen den falschen Baum → rot; ein PR mit passendem Bericht → grün in
  **unter zwei Minuten** (gemessen); `pruefung.yml` unter 300 Zeilen
  (heute 834); `kettenaufrufe` 0 Befunde, 0 ungeprüft.

### PK-06 — Staging verschlanken

- `stufe2`: drei Schritte (E-PK-17) plus der leere Platz für Nr. 234
  (E-PK-21); Zeitgrenze 20 min; Playwright/`cryptography` aus dem Cache,
  falls tragfähig; Kommentare auf einen Satz.
- `ausliefern-lauf.yml`: **nur** Kommentare kürzen (E-PK-07); kein Schritt
  ändert sich. `integritaet.yml`: nur Kommentare.
- **Abnahme:** ein Push auf `main` → Staging grün, Stufe 2 grün, Gesamtdauer
  unter zehn Minuten (gemessen); ein absichtlich kaputter edbak-Export auf
  Staging ist **nicht** herstellbar ohne Servercode — stattdessen: Stufe 2
  mit falschem `STAGING_PASS` → rot **innerhalb einer Minute** mit dem Grund
  (nicht nach 15). Die Zeilenzahl aller vier Arbeitsläufe zusammen unter
  1 200 (heute 2 830).

### PK-M2 — Erster Durchlauf der neuen Kette (Betreiberin)

Ein echtes Paket einer anderen Instanz geht den Weg A → B → C → D: Prüfstand
gefahren, Bericht im Commit, PR, Tor grün, Merge durch die Betreiberin,
Staging grün. Die vier Dauern werden notiert und mit Abschnitt 5 verglichen.

### PK-07 — Abschluss

Prüfdokument fertig (`CLAUDE.md` 7); `Technik.md` 6.2 und 6.3 auf den neuen
Stand (Stufe 1 = Tor, Stufe 2 = drei Schritte), `CLAUDE.md` 3 und 6
widerspruchsfrei; Rahmenplan (R67-Zusatz, Erledigt-Zeile nach Freigabe),
Backlog (Nr. 227 nach Erledigt; neue Nummern aus Abschnitt 7); Konzept wird
nach der Freigabe gelöscht.

---

## 5. Was es kostet und was es spart

Alle Zahlen der Spalte „heute" sind gemessen (1.2); die Spalte „Ziel" ist
**geschätzt** und wird in PK-03, PK-05, PK-06 durch Messwerte ersetzt.

| Weg | heute | Ziel |
|---|---|---|
| Stufe 1 auf dem Arbeitszweig | 1 min je Push, läuft bei jedem Push | entfällt |
| Stufe 1 beim PR / auf `main` | 40 bis 51 min | unter 2 min |
| Lokale Prüfung vor dem Commit | unbestimmt; „was mir einfällt" | klein 5 / neben 15 / haupt 45 min, ein Befehl |
| Staging + Stufe 2 | 1 min + 16 min, je Fehlerfund ein weiterer Umlauf | 1 min + unter 8 min |
| Ein Fehler wie der edbak-500 | 3 × 15 min, Ursache in einem fremden Protokoll | Sekunden, Protokoll lokal |
| Zeilen Kette | 2 830 | unter 1 200 |
| Werkzeugordner | 48 | 43 |
| Regeln zum Prüfen in `CLAUDE.md` 6 | 166 Zeilen | unter 60 |

**Was es kostet:** sieben Pakete ohne eine Zeile Anwendungscode — noch
einmal die Sorte Arbeit, die das Konzept verringern soll. Das ist der Preis,
und er wird einmal bezahlt. Danach entfällt je Paket: der 40-Minuten-Lauf
beim PR, die Stufe-2-Umläufe zur Fehlersuche, die Pflege der Schwelle 398,
die Wortlisten-Zeremonie, die Selbstproben in Station B, und die Frage,
welche der zwanzig Proben jetzt dran ist.

---

## 6. Offene Fragen an den Auftraggeber

| Nr. | Frage | Vorschlag |
|---|---|---|
| **F-PK-1** | E-PK-13 bis -22 so entscheiden? | ja |
| **F-PK-2** | Android und Uhr im Tor: ganz raus (E-PK-13) oder im PR bei Berührung? | ganz raus; die Signatur auf dem Rechner der Betreiberin ist der zweite Bau |
| **F-PK-3** | Streichliste und Aufgehen aus 3.3 so ausführen? | ja |
| **F-PK-4** | Hausform für die Textprobe: generisches Femininum („Betreiberin", „Nutzerin"), Binnen-I („NutzerInnen") oder Doppelnennung? `CLAUDE.md` und Handbuch benutzen heute beides (Femininum und Binnen-I). | eine Form festlegen, in `Design.md` 1 eintragen; die Textprobe prüft dann gegen die andere |
| **F-PK-5** | Prüfkonten `demo@gen-em.org` und `admin@gen-em.org` sind echte Domain. Erlaubnisliste oder Umzug nach `example.invalid`? | Erlaubnisliste — die Fixture reist auf Produktiv, und das Demo-Konto ist dort öffentlich |
| **F-PK-6** | Stilvergleich: ruhend behalten (B nur bei `style.css`) oder streichen? | behalten; 16 s, ein echter Fund, kein Pflegeaufwand |

---

## 7. Zuarbeiten der Betreiberin

| Nr. | Was | Wann |
|---|---|---|
| Z1 | PK-M1: Zweigschutz und Merge-Recht setzen, drei Messungen aus P-PK-01 | sofort |
| Z2 | Freigabe dieses Konzepts mit den Antworten auf F-PK-1 bis -6 | vor PK-01 |
| Z3 | PK-M2: einen echten PR durch die neue Kette mergen | nach PK-06 |
| Z4 | Freigabe des Abschlusses | nach PK-07 |

---

## 8. Einschübe (Nummern vergibt die einspielende Instanz)

**Rahmenplan:** R67-Zusatz „Prüfkette nach Konzept PK: Stufe 1 nur beim PR
und auf `main`, Stufe 2 drei Schritte, Prüfstand-Befehl mit Bericht,
Zweigschutz mit Merge-Recht der Betreiberin"; Abschnitt 6b um das
Merge-Recht ergänzen; eine Zeile in Abschnitt 10; Fahrplanzeile für PK.

**Backlog:** Nr. 227 nach Erledigt (E-PK-16). Neu: „Nr. 234 hat seinen
Platz in Stufe 2, gebaut ist er nicht" (E-PK-21); „Zwei Beschaffer mit zwei
Listen" (1.6, erledigt mit PK-02); „Regeldokumente aufteilen — `CLAUDE.md`
Überblick, `Pruefablauf.md`, `Sandbox-Setup.md`, `Technik.md` Kompendium,
was fehlt" als eigenes Konzept nach PK (Wunsch des Auftraggebers, 21.09.2026;
PK-01 legt die zwei Prüfdokumente schon in dieser Aufteilung an).

**`CLAUDE.md` 7:** die Benennungsregel E-PK-09.

---

## 9. Fable-Schritte der Umsetzung

Keine. Alle Pakete sind Werkzeug-, Ketten- und Dokumentationsarbeit; Opus
ohne Nachfrage (`CLAUDE.md` 7).
