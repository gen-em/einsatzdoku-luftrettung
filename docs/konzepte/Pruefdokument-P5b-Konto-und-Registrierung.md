# Prüfdokument P5b — Konto und Registrierung

**Zum Konzept** `docs/konzepte/Konzept-P5b-Konto-und-Registrierung.md`.
Dieses Dokument beantwortet **„was muss ich noch tun?"** — das Prüfprotokoll
im Konzept beantwortet „ist es belegt?". Es bleibt liegen, bis seine Prüfliste
abgehakt ist, und wird dann gelöscht (`CLAUDE.md` 7).

**Stand:** in Arbeit. Zweig `claude/magical-dirac-we2y1z`.

| Paket | Stand | Version |
|---|---|---|
| Vorarbeit | erledigt — P5a-Merge, Prüfstand, F4 | 20.15.3 |
| **AP1** Protokoll-Schreibweg und Einstellungen | **erledigt** | **20.16.0** |
| AP2 Lebenszyklus-Bibliothek | offen | — |
| AP3 Registrierung | **wartet auf M-P5b-02** | — |
| AP4 Einwilligungen | offen | — |
| AP5 Selbstlöschung, E-Mail-Wechsel | offen | — |
| AP6 Mengengrenze, SHA-256 | offen | — |
| AP7 Demo-Anmeldung | offen | — |
| AP8 Handbuch-Seiten | **wartet auf M-P5b-01** | — |
| AP9 Onboarding, Rückfragen | **wartet auf M-P5b-02** | — |
| AP10 Abschluss | offen | — |

**Die beiden Mockups werden dem Auftraggeber vor der Umsetzung vorgelegt**
(Weisung vom 16.09.2026). Sie entstehen mit **Opus statt Fable** — das
Konzept sieht Fable vor (Abschnitt 6), der Auftraggeber hat die Stopp-Punkte
freigegeben. Das steht hier, damit später nachvollziehbar ist, dass die
Gestaltung nicht aus dem vorgesehenen Modell kam.

---

## 0. Was NICHT geprüft werden konnte, und warum

Dieser Abschnitt steht vorn, nicht in einer Fußnote (`CLAUDE.md` 7).

| Was | Warum nicht | Was stattdessen |
|---|---|---|
| **Die Wartungsprobe** um den Protokoll-Fehlfall erweitert (Abnahme AP1) | Das Werkzeug `tools/wartungsprobe/` prüft den Wartungsmodus, nicht das Protokoll; eine Erweiterung wäre ein Umbau am Werkzeug, den AP1 nicht rechtfertigt | Der Fehlfall ist **von Hand gemessen** und in Abschnitt 3 mit Zahlen belegt: Tabelle umbenannt → `protokoll()` meldet `false`, Zähler 0 → 1, Handlung läuft weiter (`users` lesbar, 2 Konten), Tabelle zurück → Schreiben geht wieder, Zähler bleibt bis zum Quittieren stehen |
| Das Protokoll unter **echter Last** | Es gibt auf diesem Prüfstand keine | Die drei Indizes sind nach den drei Fragen gelegt, die 10c stellen wird; gemessen wird, wenn 10c die Abfragen hat |
| **`cmark-gfm`** (Markdown-Prüfung, Abnahme AP8) | Im Container nicht vorhanden | Wird in AP8 nachinstalliert |

---

## 1. Die Umgebung, in der geprüft wurde

Gemessen am 16.09.2026 im Wegwerf-Container der Sitzung.

| | |
|---|---|
| Ausgangsstand | Zweig auf `claude/butte-umsetzen-5opi9u` gehoben (P5a, 26 Commits), Web **20.15.1** |
| PHP | 8.4.19 |
| MariaDB | 10.11.14 — **nachinstalliert** über `tools/containeraufbau/aufbau.sh datenbank`, im Abbild nicht enthalten |
| Node | 22.22.2 · Python 3.11.15 |
| Browser-Engines | Chromium, Firefox, WebKit aus `/opt/pw-browsers` |
| Installation | `tools/referenzdatensatz/einspielen/lokal_einrichten.sh` — 2 Konten, **106 Einsätze**, 21 Diensttage, 40 Tabellen, erreichbar unter `https://127.0.0.1:8443/` |
| Android-SDK | **fehlt** (`/opt/android-sdk` nicht vorhanden) — für P5b ohne Belang, das Konzept sieht keine Client-Stufe vor (Abschnitt 0) |
| `CIQ_GERAETE_URL` | gesetzt — der Uhr-Prüfstand wäre aufbaubar, wird aber nur dort gebraucht, wo eine Abnahme den Simulator nennt |
| `cmark-gfm` | **fehlt** — wird für die Markdown-Prüfung in AP8 gebraucht und dort nachinstalliert |

**Die Installation aufzusetzen war selbst der erste Befund** — siehe Abschnitt 2.

---

## 2. Fehlerfunde der Umsetzung

Zählung fortlaufend ab F4; F1 bis F3 stehen im Konzept, Abschnitt 5.

### F4 — Die Anwendung ließ sich nicht mehr installieren (Backlog Nr. 215)

**Gefunden:** 16.09.2026, beim Aufbau des Prüfstands, noch vor AP1.
**Behoben:** Web 20.15.3.

`install.php` antwortete **HTTP 500 mit leerem Rumpf** — kein Formular, keine
Meldung. Gemessen: `curl http://127.0.0.1:8080/install.php` → `HTTP 500,
0 Byte`.

Die Kette, jedes Glied für sich richtig: `install.php` lädt `ui.php`, damit
ihr Formular aussieht wie die Anwendung → `ui_seite_start()` lädt seit
P5a/AP4 `kopfzeilen_lib.php`, damit die Kopfzeilen vor der ersten
Ausgabezeile stehen → `kopfzeilen_lib.php` lud `db.php` → `db.php` verlangt
`config.php` hart. Vor der Einrichtung gibt es keine `config.php`.

**Der Fehler lag auf `claude/butte-umsetzen-5opi9u` und wäre mit P5a auf
`main` gegangen.** Nachgemessen: `git diff origin/claude/butte-umsetzen-5opi9u
HEAD -- server/` war zum Zeitpunkt des Funds leer — der Merge hat ihn nicht
verursacht. Auf `main` trat er nicht auf, weil `ui.php` dort
`kopfzeilen_lib.php` nicht lädt.

**Warum kein Prüfmittel ihn gesehen hat** — und das ist die Lehre, die über
den Fall hinausgeht: Er trifft ausschließlich die Installation, die noch nicht
stattgefunden hat. Jede bestehende Anlage läuft weiter. Und jedes Prüfmittel
des Projekts setzt eine laufende Installation *voraus*, statt eine
einzurichten. Der Weg, den eine Betreiberin genau einmal geht, ist damit der
einzige, den niemand geht.

**Behoben** in `kopfzeilen_lib.php`, nicht in `db.php`: dort ist das harte
`require` richtig. `is_file()` vor dem `require`, `function_exists()` vor den
vier `app_state`-Aufrufen, Rückfall auf dieselben Vorgaben wie bei fehlender
Tabelle (Report-Only, 1 Tag HSTS). Nachgemessen, dass die Datei aus `db.php`
nur `app_state_lesen()` und `app_state_setzen()` braucht.

**Gegenprobe nach der Behebung:** `install.php` → **HTTP 200, 8 505 Byte**,
mit Formular-Token und angelegter Nachweisdatei; `lokal_einrichten.sh` läuft
bis „fertig" durch.

**Offen bleibt die Wache** (Backlog Nr. 214, Teil „Zu tun"): ein Prüfschritt,
der die Einrichtung selbst fährt. Ohne ihn fällt dieselbe Lücke beim nächsten
Umbau der Ladekette wieder auf.

---

## 3. Was maschinell geprüft wurde — Mittel und Zahl

| Mittel | Wann | Zahl | Befund |
|---|---|---|---|
| `php -l server/kopfzeilen_lib.php` | F4 | 0 Syntaxfehler | — |
| `curl install.php` vor/nach F4 | F4 | HTTP 500/0 Byte → **HTTP 200/8 505 Byte** | behoben |
| `lokal_einrichten.sh` | Prüfstand | 2 Konten, 106 Einsätze, 21 Diensttage, 40 Tabellen | steht |
| Wegwerfliste (E-P5b-23) | vor AP3 | **8 870** Zeilen; **8/8** bekannte Wegwerfanbieter enthalten; **0/10** echte Provider- und Klinikdomains fälschlich | Zahlen des Konzepts bestätigt |
| `php -l` über alle berührten Dateien | AP1 | 6 Dateien, **0 Syntaxfehler** | — |
| Migration `2026_09_16_protokoll_ereignisse` | AP1 | `php update.php` → **erfolgreich angewendet**, 15 Migrationen im Register | Tabelle mit 9 Spalten und 3 Indizes steht |
| **Bereinigung mit gestellten Fristen** (Abnahme AP1) | AP1 | 5 Prüffälle, **3 von 3 Fristfällen richtig**: 31 d E-Mail **weg**, 31 d Verwaltung **bleibt**, 366 d Verwaltung **weg**; Gegenproben 29 d E-Mail und 364 d Verwaltung bleiben. Erwartet `b,d,e` — gemessen `b,d,e` | **bestanden** |
| **Fehlfall V7** (Abnahme AP1) | AP1 | `protokoll()` → `false`, Zähler **0 → 1**, Handlung läuft weiter, Statuskarte rot; nach Rückbau Schreiben wieder `true`, Zähler bleibt bis zum Quittieren | alle **drei** Stufen belegt |
| Schreibweg allgemein | AP1 | 3 Einträge über 3 Reiter; unbekannter Reiter landet unter `system` **mit** `error_log`-Meldung; Zählkarte zählt 3 | — |
| **Bilderlauf, drei Engines** | AP1 | `betrieb_status.php` und `betrieb_server.php` in **8 Breiten** (360–1920): **Chromium** 16 Bilder 0/0/0 · **Firefox** 16 Bilder 0/0/0 · **WebKit** 16 Bilder 0/0/0. Gemessen wird Überlauf, Konsolenfehler und Knopfhöhe (44/36 px am Zeigergerät) | — |
| **Wortliste** | AP1 | **alle fünf Bereiche**: (a) 111 PHP-Dateien, (b) 36 JS, (c) 8 Dokumente, (d) 2 Android, (e) 35 Uhr — **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** bei 99 Regeln | — |

---

## 4. Was im Browser geprüft wurde

**AP1 — Betrieb → Servereinstellungen (1440 px, Chromium).** Die Karte
„Konten" steht zwischen *Sicherheitskopfzeilen* und *Ratenschutz*, trägt die
Plakette „nur auf Einladung" und erscheint **von selbst in der Sprungliste
der Seitenleiste**. Alle acht Felder sind belegt: Auswahl mit drei
Betriebsarten, Freischaltfrist 30, zwei Schalter, das mehrzeilige Feld für
eigene Domains, Grenzen 5000 / 250, Aufbewahrung leer, Protokollfrist 365.

**AP1 — Betrieb → Status (1440 px, Chromium).** Die Karte „Betriebsprotokoll"
steht zwischen *Plattform* und *Was hier gilt*, mit allen **sechs** Reitern,
je Reiter „0 heute · 0 gesamt" und der Frist in der Unterzeile
(Verwaltung 365, übrige 30).

**Was dabei auffiel und behoben wurde:** Im Hinweistext der Wegwerfadressen
standen Backticks um einen Dateinamen — sichtbarer Text geht durch `ui_e()`,
die Zeichen wären als Literal erschienen. Ersetzt durch eine Formulierung
ohne Dateinamen.

---

## 5. Prüfliste für die Betreiberin

Je Punkt: der Bedienweg, das erwartete Ergebnis und **woran ein Scheitern zu
erkennen ist**.

*(wird mit den Arbeitspaketen gefüllt)*

---

## 6. Grenzen der benutzten Prüfmittel

- **Der Bilderlauf misst statisches Markup**, keine Bedienzustände. Dass die
  Karte „Konten" *aussieht* wie vorgesehen, sagt nichts darüber, ob das
  Speichern die richtigen Werte schreibt — das ist von Hand geprüft
  (Abschnitt 3) und gehört in die Prüfliste (Abschnitt 5).
- **Die Wortliste liest sichtbaren Text ohne Kommentare.** Ein Luftbegriff in
  einem Kommentar fällt ihr nicht auf, und das ist richtig so — aber auch
  keine Aussage über Kommentare.
- **Kein Prüfmittel des Projekts richtet eine Installation ein.** Genau daran
  ist F4 dreizehn Auslieferungen lang vorbeigelaufen. Die Lücke bleibt
  bestehen (Backlog Nr. 215, Teil „Zu tun").
- **Die Fristen des Protokolls sind mit gestellten Zeitstempeln geprüft**, nicht
  über echte 365 Tage. Das ist die einzig mögliche Prüfung und gleichzeitig
  ihre Grenze: Sie belegt die SQL-Bedingung, nicht das Verhalten einer
  Datenbank, die ein Jahr lang gelaufen ist.
