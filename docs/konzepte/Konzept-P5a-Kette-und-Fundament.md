# Konzept P5a — Kette und Fundament

**Rahmenplan:** Schritt 10a (Fassung 71, R82), R19, R36, R37 (8) und (9),
R40 (2) und (4), R66, R67, R81. **Backlog:** 8, 17, 37 (nur die drei
Messungen), 49, 54, 67, 80 (nur der Nachlöse-Job), 195.
**Vorbereitung:** `Vorbereitung-P5-Plattformprofil.md` (15.09.2026, PP-1 bis
PP-9, E-PP-01 bis -09) und `Vorbereitung-Sicherheitspaket.md` (SP-5, SP-6).
**Modell:** Konzept Fable (R14), Umsetzung Opus (K2); **ein Fable-Schritt**
in der Umsetzung (Abschnitt 6). **Ablage:** dieses Dokument, das
Prüfdokument daneben (`Pruefdokument-P5a-Kette-und-Fundament.md`, entsteht
mit AP1), Mockups in `konzept-p5a/mockups/`.

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 15.09.2026 — **Konzept freigegeben, Umsetzung läuft.** Die Grundsatzfragen sind am 15.09.2026 im Gespräch entschieden (E-P5a-01 bis -09); die übrigen Festlegungen (E-P5a-10 bis -21) stammen aus dem Nachmessen im Code und stehen mit der Freigabe. |
> | Entschieden | E-P5a-01 bis E-P5a-21 (Abschnitt 2); E-PP-01 bis -09 übernommen; **F-P5a-1 entschieden** (2.4) |
> | Offen | — |
> | Umsetzung | **läuft.** Reihenfolge AP1 → AP2 → … → AP12; Abhängigkeiten in 3.0 |
> | Fable-Schritte der Umsetzung | **keiner mehr** — M-P5a-01 ist nach Auftrag vom 15.09.2026 ohne Pause umgesetzt worden (2.5) |

> **Stand der Umsetzung**
>
> | Paket | Stand | Stufe | Abnahmezahlen |
> |---|---|---|---|
> | AP0 Aufnahme | **erledigt** | — | Rahmenplan Fassung 72, Vorbereitung und Konzept im Repositorium |
> | **AP1 Auslieferungskette** | **erledigt** | **Web 20.4.0** | Register 46/46, 0 Befunde, Selbstprobe 4/4 · Backup-Tor 5/5 · Wortliste 0/0/0 · Kontraste 22/0 · Vollständigkeit 340 = unverändert · 3 Arbeitsläufe gültiges YAML |
> | **AP2 Plattformprüfung** | **erledigt** | **Web 20.5.0** | 21 Befunde (15 ohne DB/config) · Muss offen 0 · Installweiche 8/8, 0 Befunde auf 658 Zeilen · Bilderlauf 16 Bilder, 0/0/0 · Wortliste 0/0/0 |
> | AP3 bis AP12 | offen | — | — |

---

## 0. Auftrag und Umfang

**Anlass.** Schritt 10 (P5 Dienstbetrieb) ist am 15.09.2026 in drei Teile
geschnitten (R82). Dieses Konzept ist der erste: alles, was der Dienstbetrieb
**unter** den Konten braucht — die Auslieferungskette, die Plattformprüfung,
der Torwächter, die Kopfzeilen, die Mail-Warteschlange, der Ratenschutz
samt Mengenbremse, die Sicherheitssicht, die Verbindungsgrenze, die
Sicherungsziele und ein Job. Kaum Oberfläche, viel Betrieb.

**Was dieses Konzept liefert:** je Thema den Befund im Code
(nachgemessen an `origin/main` `7f334cb`, 15.09.2026), die Festlegungen
als E-Einträge, zwölf Arbeitspakete mit Abnahmekriterien, ein
Prüfprotokoll-Soll, die Einschübe für Rahmenplan und Backlog.

**Was es nicht liefert:**

- Alles aus **10b** (Registrierung, Konto-Lebenszyklus, Onboarding,
  Geräteschlüssel, Mengengrenze je Konto — dazu der zweite Teil von Nr. 37)
  und **10c** (Support-Rolle, Zweitfaktor, Audit, Banner, Fehlerprotokoll,
  Health, Dashboard, R39-Rest).
- **Uhr- und Android-Änderungen.** Gemessen: beide Clients behandeln jeden
  Antwortcode außer 200/400/401/403/413 als „später erneut"
  (`Uploader.mc` Zeile 355 ff., `Sendeantwort.kt` Zeile 50). Ein `429` aus
  der Mengenbremse braucht **keine** Client-Stufe (1.7).
- Die **Doku-Neufassung** (R72, P7). Was jedes Paket an `docs/Technik.md`,
  `docs/Handbuch.md`, `README.md` ändert, ändert es nach `CLAUDE.md` 9 —
  das Betreiberhandbuch als Ganzes kommt später.
- **Verschlüsselung at rest** und **DDoS-Schutz des Hosters** — nach PP-8
  nicht vorausgesetzt, hier nicht Gegenstand.

**Kein Versionssprung im Konzept** (K3). Die Umsetzung stuft `WEB_VERSION`;
Uhr und Android bleiben unberührt, außer AP1 ändert `deploy.yml` so, dass
Android-Bauläufe im Prüftor entstehen — das ist Kette, kein App-Code.

---

## 1. Befund

### 1.1 Auslieferung heute (R40, R67, PP-9)

- `.github/workflows/deploy.yml`: bei jedem Push auf `main` mit Änderung
  unter `server/**` ein FTPS-Sync von `server/` nach `./httpdocs/` auf
  **Produktiv** — Geheimnisse `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`
  als Repositoriums-Secrets, keine Umgebungen, kein Tag, kein Prüftor, kein
  Rollback-Weg. `sicherungen/` und `config.php` sind vom Sync ausgenommen.
- `.github/workflows/integritaet.yml` (SP-6, Nr. 140) hängt per
  `workflow_run` am Namen **„Server per FTP hochladen"** — wer den
  Deploy-Lauf umbenennt, hängt die Wache ab.
- **Keine Tags** im Repositorium (0). Versionen leben in `version.php`
  (Web), `Const.mc` (Uhr), `version.properties` (Android).
- Das Repositorium ist **öffentlich** — GitHub-Umgebungen mit
  Pflichtfreigabe („required reviewers") sind damit kostenlos verfügbar,
  schon vor dem Umzug nach `gen-em/nadoku` (R68).
- Staging ist festgelegt (E-PP-09): `staging.nadoku.gen-em.org`, gleicher
  Tarif, `staging@gen-em.org`, eigenes SFTP-Backup-Ziel; Einrichtung ist
  Zuarbeit (Rahmenplan Abschnitt 6).
- Prüfmittel, die die Kette fahren kann, ohne Installation: `php -l`,
  Wortliste (`tools/wortliste/wortliste.py`), Vollständigkeit
  (`tools/vollstaendigkeit/pruefen.py`), Kontraste
  (`tools/screenshots/kontrast.py`), Backlog-Doppelungen (`grep`-Zeile
  aus dem Rahmenplan-Kopf), `./gradlew build` unter `android/`. Mit
  Installation: Kreisläufe csv und edbak
  (`tools/referenzdatensatz/vergleich/kreislauf.py`), Bilderlauf
  (`tools/screenshots/aufnehmen.mjs`, braucht Chromium), Messstand
  (`tools/messstand/`). Uhr Stufe I braucht `CIQ_GERAETE_URL`.
- Der Komplett-Backup-Job hat einen Token-Einstieg (`jobs.php?token=…`,
  Budget 20 s je Aufruf, `JOB_BUDGET_TOKEN`); ein Lauf arbeitet in
  Häppchen und meldet je Aufruf, ob er fertig ist. **Für ein Backup-Tor
  muss die Kette den Endpunkt wiederholt aufrufen** — ein Aufruf reicht
  bei 10 GB nicht.

### 1.2 Plattform heute (PP-1 bis PP-5)

- `install.php` prüft `zip`, `zlib`, `openssl`, `mbstring` — **keine
  PHP-Version, kein `pdo_mysql`, keine Weblimits, keine DB-Version, keine
  Verbindungsgrenze**; Schreibrecht nur per `is_writable(__DIR__)`.
- `speicher_lib.php` misst DB und Dateien und kennt ein
  **Webspace-Kontingent** (`webspace_gb`, Schwellen 70/90 für die
  Warnmail). Ein **DB-Kontingent** gibt es nicht — PP-2 verlangt es.
  Korrektur an PP-2: nicht „ab 80 %", sondern dieselben Schwellen wie der
  Webspace (E-P5a-11).
- Komplett-Stände: Aufbewahrung einstellbar 1–20,
  **Vorgabe 2** (`KOMP_AUFBEWAHRUNG_VORGABE`). Korrektur an PP-5, das „3"
  nannte (E-P5a-11).
- Job-Läufer (`jobs_lib.php`): drei Auslöser mit Budgets 3 s (Huckepack),
  20 s (Token), 300 s (CLI); Huckepack mit **5 min Mindestabstand**
  (`JOB_ANFRAGE_PAUSE_S`) — nicht „einmal täglich", wie der Kommentar in
  `smtp.php` Zeile 32 noch sagt (Altbestand aus S2/AP8, wird mit AP5
  berichtigt). Katalog: `aufraeumen` (auch Ratenschutz), `verdichtung`,
  `ausduennen`, `adminbackup`, `versand`, `komplett`, `waisen`.
- Wartungsmodus (Paket W): `wartung_einschalten()` schreibt
  `server/wartung.lock`; `wartung_tor()` hält jede Anfrage an;
  `betrieb_updates.php` zeigt Wartung und ausstehende Migrationen auf
  **einer** Seite (R66). `migration_lib.php` kennt den Katalog und
  `migrationen_lauf()`; ob eine Migration aussteht, ist heute eine Frage
  an die Seite, nicht an jede Anfrage.

### 1.3 Kopfzeilen und HTTPS heute (PP-6, SP-5, Nr. 8)

- Gesetzt werden **keine** Sicherheitskopfzeilen — 0 Treffer für
  `Strict-Transport-Security` und `Content-Security-Policy` in `server/`.
- Cookies: `secure`, `httponly`, `samesite=Strict` (`auth_guard.php`
  Zeile 10, `session_lib.php` Zeile 77). Über HTTP scheitert die Anmeldung
  stumm — der Browser sendet den Cookie nicht.
- CSP-Tauglichkeit, nachgemessen wie in SP-5 und heute noch enger:
  **0** `on*=`-Attribute, **0** `javascript:`-Adressen, **3**
  `style=`-Attribute (SP-5 zählte 1 — `index.php` plus zwei seither),
  **1** `<style>`-Block, **32** `<script>`-Blöcke ohne `src` in 17
  Dateien (`einstellungen.php` 6, `ui.php` 4, je 2 in `zeitraum.php`,
  `suche.php`, `import.php`, `db.php`, `admin_users.php`, je 1 in neun
  weiteren); alle Seiten laufen durch `ui_seite_start()`. Die Zahlen
  gehören ins Prüfprotokoll von AP4: Nach AP4 sind es 0 `style=` und
  0 Blöcke ohne Nonce.
- `rate_ip()` liest nur `REMOTE_ADDR` — bewusst (1.5).

### 1.4 Mail heute (PP-3, PP-7, R37 (9))

- `smtp.php`: `smtp_send()` über SMTP mit TLS aus `config.php`; kein
  `mail()`. Neun Aufrufer (`admin_user.php`, `admin_users.php`,
  `adminbackup_lib.php`, `betrieb_status.php`, `email_lib.php`, `pair.php`,
  `reset_request.php`, `version.php`, `smtp.php`).
- Der Versand läuft **nach abgeschlossener Antwort**
  (`antwort_abschliessen()`, FPM oder LiteSpeed; sonst Weg 2 mit
  Content-Length) — damit die Antwortzeit nichts verrät. **Keine
  Warteschlange**, ausdrücklich; scheitert SMTP, ist die Mail weg, und
  `smtp_versand_vermerken(false)` merkt es nur.
- Warnmails (Speicher, Backups) gehen direkt aus `betrieb_status.php`
  und `adminbackup_lib.php`.

### 1.5 Ratenschutz heute (R37 (8), PP-6)

- `RATE_GRENZEN`: `login` 10/900 s/Sperre 900 s, `salt` 30/900/900,
  `reset` 5/3600/3600, dazu die Kopplungstöpfe (`pair`, …, E-S5-16) und
  `demo`/`demog` (zählen erfolgreiche Aufrufe). **Kein Topf `ingest`**
  (R19).
- `rate_merkmale($konto)` zählt **immer** je IP und, wo es ein Konto gibt,
  je `id:<name kleingeschrieben>` — **am eingegebenen Namen**, nicht an der
  Kontozeile. Das ist genau die Bauart, die E-P5a-04 braucht: Ein Name
  ohne Konto sammelt denselben Zähler.
- Sperre: fest `sperre` Sekunden ab Überschreiten; keine Stufen, kein
  Gedächtnis über das Fenster hinaus; keine globale Zählung; kein Knopf
  zum Aufheben (die Statusseite zeigt Sperren nur).
- Gleiche Antwortzeiten: `rate_gleiche_dauer()` mit 0,35 s Mindestdauer.
- Aufräumen: `job_aufraeumen` löscht abgelaufene Töpfe.
- `csrf_check()` hat keinen API-Zweig (Nr. 67, R21) — die
  JSON-Endpunkte laufen an ihm vorbei, statt ihn mit einem eigenen Zweig
  zu benutzen.

### 1.6 Sicherungsziele heute (PP-5, Nr. 49, Nr. 195)

- `sicherungsziel_lib.php`: Schnittstelle `Zielweg` mit `verbinden`,
  `ordner`, `senden`, `holen`, **`liste`**, **`loeschen`**, `fingerabdruck`;
  Adapter `ZielFtp` (FTPS) und SFTP über phpseclib 3 (vendoriert). **Beides,
  was E-P5a-03 braucht — Anzeigen und Löschen — kann der Adapter schon.**
- Der Versandjob ergänzt nur; auf der Gegenstelle wird nie gelöscht.
- `backup_lib.php` prüft `geraet_art` auf dem Rückweg nur auf Länge, nicht
  gegen `GERAETE_ARTEN` (Nr. 195).

### 1.7 Mengenbremse `ingest.php` (R19, Nr. 17)

- `ingest.php` ruft weder `rate_erlaubt()` noch `rate_misserfolg()`.
  Anmeldung über `X-Device-Id`/`X-Api-Key` gegen bcrypt; unbekannte
  Kennung → Blindvergleich, gleiche Dauer, 401 (M4-07).
- Messung aus P1 (Nr. 17): 526 Anfragen ohne Fehlversuch, Spitze **14 an
  einem Auslöser**, 174 Abstände von 0 s, Median 1 020 s. Ein Dienst
  bringt also Stöße; **eine Grenze auf Fehlversuche muss einen ganzen
  Stoß mit veraltetem Schlüssel überstehen** (14 Fehlversuche in Folge
  sind ein Schlüsselwechsel, kein Angriff).
- Clients: Uhr — 200 ok, 401/403 abgemeldet, 400 dauerhaft abgewiesen,
  **alles andere „später erneut"** (`_busy = false`, nächster
  `syncAll`-Auslöser); Handy — 400 fehlerhaft, 401 Schlüssel abgewiesen,
  413 zu groß, **`code != 200` → `SpaeterErneut`**. `Retry-After` liest
  keiner; beide wiederholen zum nächsten eigenen Anlass. Für eine Sperre
  von 10 bis 60 Minuten ist das gut genug: Der Rückstand bleibt auf dem
  Gerät, nichts geht verloren.

### 1.8 Nr. 80 — der Nachlöse-Job (E-PP-06)

- `server/geraetemodelle.php` (erzeugt, deployt); `pair.php` löst beim
  Koppeln auf; `tools/geraetemodelle/nachaufloesen.php` löst später nach —
  CLI-only, Vorschau vor `--schreiben`, ändert nur, was die Tabelle kennt,
  rührt die Rohangabe nie an. Die Logik steckt im Skript, nicht in einer
  Bibliothek — sie muss für den Job herausgelöst werden.

---

## 2. Entscheidungen

### 2.1 Aus dem Gespräch vom 15.09.2026 (E-P5a-01 bis -09)

**E-P5a-01 — Die Mengenbremse für `ingest.php` kommt.** Die Grundsatzfrage
aus R19 ist entschieden: ja. Grund: Seit E-PV-1 werden die Clients über die
Stores verteilt; E-R45-6 nennt genau diese Kombination — öffentlicher
Client mit Geräteschlüssel ohne Bremse — als die Flutungsgefahr aus P5.
Die vier Randbedingungen aus R19 sind Vorgabe: (1) gezählt werden nur
**Fehlversuche**; (2) die Aussperrung nach Schlüsselwechsel ist hinnehmbar
und sichtbar (E-P5a-02); (3) Merkmal ist die **Gerätekennung**, die IP nur
für unbekannte Kennungen; (4) die Asymmetrie zu den vier anderen Töpfen
endet — `ingest` wird der fünfte, mit eigener Grenze.

**E-P5a-02 — Aussperrung nach Schlüsselwechsel: hinnehmbar, kurz,
sichtbar.** Eine Uhr mit veraltetem Schlüssel sperrt sich für die erste
Leiterstufe (10 min, E-P5a-04) und verliert nichts — sie behält ihre
Warteschlange und sendet später (1.7). Sichtbar an zwei Stellen:
Statusseite („Gerät …: N abgewiesene Anmeldungen seit …", Hinweis) und
die Kontoseite der Nutzerin am Gerät, wo sie ohnehin neu koppelt. Die
Grenze liegt bei **30 Fehlversuchen je 15 min je Gerätekennung** — mehr
als zwei Stöße à 14 (1.7), also kein Schlüsselwechsel allein löst sie
aus, sondern erst der zweite Anlauf danach. Verworfen: Fehlversuche mit
bekannter Kennung nicht zählen — das ließe die Flut mit gestohlener
Kennung offen.

**E-P5a-03 — Sicherungsziel: Anzeige als Grundlage, Löschregel als
Option je Ziel.** (Nr. 49.) Die Seite Sicherungsziele zeigt je Ziel, was
dort liegt: Anzahl, Gesamtgröße, ältester und jüngster Stand, gelesen mit
`liste()`; die Statusseite gibt einen Hinweis, wenn ein Ziel seit über
einem Monat wächst, ohne dass dort etwas entfernt wurde. Dazu **je Ziel**
die Option „dort höchstens N je Konto und M Komplett-Stände behalten" —
ausdrücklich einzuschalten, nie Vorgabe. Drei Sicherungen für die Option:
Gelöscht wird nur, was diese Installation selbst dorthin geschickt hat
(Namensmuster **und** Versandprotokoll in `app_state`; fremde Dateien
bleiben); nie unter N beziehungsweise M; nie in einem Lauf, dessen eigener
Versand fehlgeschlagen ist. Jede Löschung drüben steht im Protokoll der
Sicherungsziele mit Ziel, Datei und Grund — und ab 10c im Audit.

**E-P5a-04 — Eine Sperrleiter für Konto und IP: 10 → 20 → 30 → 60 min.**
(R37 (8).) Der Topf `login` (und `salt`, `reset` nach demselben Muster)
sperrt nicht mehr fest, sondern nach **Stufe**: je Merkmal eine Stufe
0–3, die mit jeder Sperre steigt und nach **24 h ohne Fehlversuch** auf 0
fällt; Dauer je Stufe 10, 20, 30, 60 min. Die Schwelle je Konto bleibt
**10 Fehlversuche je 15 min**, gezählt am eingegebenen Namen (1.5 — so
gibt die Sperre keine Kontoauskunft). Die Schwelle je IP steigt auf
**50 je 15 min** — hinter einem Klinik-NAT teilen sich viele eine
Adresse. Betrieb → Servereinstellungen bekommt die Zahlen als
Einstellungen mit diesen Vorgaben und einen Knopf **„Sperre aufheben"**
je Merkmal (Status → Sicherheit, E-P5a-08). Der Preis, benannt: Ein
absichtlich gesperrtes Konto kann bis zu 60 min zu sein — der
Passwort-Reset (eigener Topf, per Mail) bleibt offen.

**E-P5a-05 — Global: Verlangsamung statt Sperre.** Alle Fehlversuche der
Installation zusammen bilden ein Fenster (Merkmal `global`). Ab einer
Schwelle (Vorgabe **200 je 15 min**) antwortet **jede** Anmeldung erst
nach einer Wartezeit — 1 s, bei weiter steigender Summe 2, 4, 8 s,
gedeckelt bei 8 (Stufen bei 200/400/800/1 600). Erfolgreiche Anmeldungen
gehen durch. Grund: Eine globale Sperre wäre ein Schalter, den jeder von
außen umlegt. Technisch `usleep()` vor der Antwort; die Statusseite zeigt
„Verlangsamung aktiv seit …" **orange**; Stufe 4 der Verlangsamung löst
dieselbe Sammelmail aus wie eine 60-min-Sperre (E-P5a-07).

**E-P5a-06 — Hinweise mit Zeitangabe und Countdown.** Die Anmeldeseite
sagt beim Öffnen, wenn die Verlangsamung aktiv ist („antwortet derzeit
verzögert, etwa 4 Sekunden … wird ganz normal geprüft"), ruhig, ohne das
Wort Angriff; nach dem Absenden zählt der Knopf herunter, das Formular
bleibt gesperrt. Dieselbe Form für die Kontosperre: „Anmeldung für diesen
Namen bis 14:32 Uhr gesperrt (noch 7 Minuten)" mit laufender Anzeige, für
jeden Namen gleich; „Passwort vergessen?" bleibt anklickbar. Uhr und
Handy: `429` mit `Retry-After` aus der Mengenbremse; Clients behandeln es
als „später erneut" (1.7) — keine Client-Stufe.

**E-P5a-07 — Sammelmail bei Stufe 4.** Erreicht ein Merkmal die
60-min-Stufe oder die Verlangsamung ihre vierte Stufe, geht eine Mail an
alle Konten mit Rolle BetreiberIn — **höchstens eine je Stunde**, als
Sammelmeldung („3 Sperren der Stufe 4: 2 Adressen, 1 Konto; Verlangsamung
Stufe 4 seit 13:05"), über die Warteschlange (E-P5a-14). Einstellung in
Betrieb → Servereinstellungen, Vorgabe **an**.

**E-P5a-08 — Ort: Betrieb → Status → Sicherheit.** E-S8-12 erlaubt für das
Audit „eigene Seite oder Unterseite von Status"; die Sicherheitssicht ist
eine **Unterseite von Status**, in P5a mit: aktive Sperren (Art, Merkmal,
Stufe, bis wann, Knopf „aufheben"), Sperrereignisse der letzten 30 Tage,
Verlangsamungsphasen, Treffer der Mengenbremse, Löschungen auf
Sicherungszielen (E-P5a-03), die Mailregel. IPs stehen dort im Klartext —
`rate_limits` hält sie heute schon so; neu ist die 30-Tage-Liste, und die
kommt in den Datenschutztext der Installation. 10c hängt das
Audit-Protokoll daneben. **Gestaltung: Mockup M-P5a-01 (Fable) vor AP8.**

**E-P5a-09 — Betriebsdaten ohne Kontobezug verfallen nach 30 Tagen,
fest.** Sperrereignisse, Verlangsamungsphasen, Bremse-Treffer, erledigte
Warteschlangeneinträge, Lösch-Protokoll der Ziele, Job-Läufe: der Job
`aufraeumen` löscht, was älter als 30 Tage ist; leere Ratenschutz-Töpfe
sofort wie heute. Die 30 Tage sind **keine Einstellung** —
Sicherheitsdaten sollen nicht versehentlich Jahre liegen. Fehlerprotokoll
und Audit entscheidet 10c (Audit länger).

### 2.2 Aus dem Nachmessen (E-P5a-10 bis -21)

**E-P5a-10 — Zwei Arbeitsläufe, eine Wache.** `deploy.yml` wird zu
**`auslieferung.yml`** mit zwei Jobs: `staging` (bei Push auf `main`,
Umgebung `staging`, FTPS-Sync wie heute, aber mit dem Verzeichnis der
Subdomain) und `produktion` (bei Tag nach F-P5a-1, Umgebung `produktion`
mit Pflichtfreigabe durch die Betreiberin — T1 aus E-PV-3 —, davor das
Backup-Tor E-P5a-12). Ein dritter Lauf **`pruefung.yml`** ist das Prüftor
Stufe 1 (E-P5a-13). `integritaet.yml` wird auf den neuen Namen des
Produktionsjobs umgehängt (`workflow_run` auf `auslieferung.yml`, Filter
auf den Job `produktion`) — sonst verliert die Wache ihren Auslöser (1.1).
Die FTP-Geheimnisse wandern aus den Repositoriums-Secrets in die
**Umgebungen** (`staging`: drei, `produktion`: drei plus `JOBS_TOKEN`).

**E-P5a-11 — Zwei Korrekturen an der Vorbereitung.** PP-2: Das
DB-Kontingent wird eine Einstellung `db_gb` (Vorgabe 10) neben
`webspace_gb`, mit **denselben Schwellen 70/90** und derselben Warnmail —
nicht „ab 80 %". PP-5: Die Aufbewahrung der Komplett-Stände hat Vorgabe
**2**, nicht 3. Die Vorbereitung ist entsprechend berichtigt (Abschnitt 5
dort nennt die Korrektur).

**E-P5a-12 — Backup-Tor der Kette: Token-Einstieg mit Aktion und
Wiederholung.** `jobs.php?token=…` bekommt einen Parameter `aktion` mit
den Werten `komplett` (nur diesen Job, Häppchen), `wartung_an`,
`wartung_aus`, `zustand` (JSON: jüngster Komplett-Stand mit Zeit, Wartung
an/aus, ausstehende Migrationen ja/nein, `WEB_VERSION`). Der
Produktionslauf ruft `komplett` in einer Schleife (Pause 20 s, höchstens
**40** Aufrufe), bis `fertig` und der jüngste Stand jünger als der
Laufbeginn ist; sonst **Abbruch ohne Deploy**. Danach `wartung_an` →
FTPS → `zustand`: Steht eine Migration aus, **bleibt die Wartung an** und
der Lauf endet grün mit dem Hinweis „Migration ausstehend — `update.php`
von Hand, dann Wartung aus"; sonst `wartung_aus`. Rollback = Lauf mit dem
vorigen Tag (E-PV-3 (d)). Der Token ist ein Umgebungsgeheimnis von
`produktion`; Staging braucht keins (dort kein Backup-Tor, kein
Echtbestand).

**E-P5a-13 — Prüftor Stufe 1 und 2, wie E-PV-3 (e).** Stufe 1
(`pruefung.yml`, jeder Push, jeder Zweig, Pull Requests): `php -l` über
`server/**/*.php`, Wortliste 0/0/0, Vollständigkeit, Kontraste,
Backlog-Doppelungen leer, **Migrationsregister** (`schema.sql` gegen
`migration_lib.php`, Prüfung aus `tools/wartungsprobe/` Teil 6),
`./gradlew build` (0 Lint-Fehler, 0 Fehlschläge), Uhr Stufe I **wenn**
`CIQ_GERAETE_URL` als Secret im Lauf verfügbar ist — sonst ein
ausdrücklich übersprungener Schritt mit Meldung, nie ein stilles Grün.
**Rot = kein Merge** über den Zweigschutz (Zuarbeit). Stufe 2 (Job im
`staging`-Lauf, nach dem Sync): Kreisläufe csv und edbak gegen Staging
(0 unerklärt), Bilderlauf (Überlauf, Konsole, Knopfhöhen 0; Chromium über
Playwright im Runner); Messstand **nur bei Tag-Läufen** gegen Staging.
Beide brauchen ein Prüfkonto auf Staging (Umgebungsgeheimnis). Rot in
Stufe 2 = der Stand ist nicht freigabefähig; der Produktionslauf
verweigert, wenn der Tag keinen grünen Staging-Lauf hat.

**E-P5a-14 — Mail-Warteschlange ohne Verzögerung des ersten Versuchs.**
Tabelle `mail_warteschlange` (Empfänger, Betreff, Text, Art, erstellt,
Versuche, nächster Versuch, zugestellt, Fehler). `smtp_send()` wird zu
`mail_einreihen()` + `mail_versuchen()`: Jede Nachricht wird **erst
gespeichert**, dann — wie heute nach abgeschlossener Antwort — sofort
versucht, mit **5 s** Verbindungs- und Sendebudget; scheitert der
Versuch, läuft die Wiederholung im neuen Job `mail` (Katalog): **fünf
Versuche über 24 h** (nach 5 min, 30 min, 2 h, 8 h, 24 h), danach
„unzustellbar" auf der Statusseite mit Empfänger und Grund. Die neun
Aufrufer ziehen um; die Warnmails aus `betrieb_status.php` und
`adminbackup_lib.php` ebenso. Der Kommentar in `smtp.php`, der die
Warteschlange ablehnt, wird ersetzt — sein Grund (Link liegt bis zum
nächsten Aufruf) ist mit dem synchronen ersten Versuch erledigt.
**Betreff-Präfix** aus `config.php` (`mail.betreff_praefix`, Vorgabe
leer; Staging trägt „[Staging]"). Das Bounce-Postfach (PP-7, Empfohlen)
kommt **nicht** in P5a — Backlog (Abschnitt 8).

**E-P5a-15 — Kopfzeilen an einer Stelle.** Eine Funktion
`kopfzeilen_setzen()` in `ui.php` (Seiten) und ein schmaler Satz für
JSON-Endpunkte (`nosniff`, `Referrer-Policy`, kein CSP nötig), aufgerufen
aus `ui_seite_start()` beziehungsweise `auth_guard.php`. CSP genau nach
SP-5 (Bauplan übernommen: `default-src 'none'`, `script-src 'self'
'nonce-…'`, Karten-Kacheln der vier Anbieter, `connect-src` photon,
`worker-src blob:` für zip.js, `frame-ancestors 'none'`, `base-uri
'none'`, `form-action 'self'`); zuerst **`Content-Security-Policy-
Report-Only`** mit Berichtsendpunkt `server/api/csp_bericht.php`
(ratenbegrenzt, schreibt in das Fehlerprotokoll — bis 10c es baut: in
eine kleine Tabelle `csp_berichte`, 30 Tage), **zwei Wochen im Betrieb**,
dann scharf über eine Einstellung `csp_scharf` in Betrieb →
Servereinstellungen. Die 3 `style=`-Attribute und der `<style>`-Block
werden aufgelöst; die 32 Skriptblöcke bekommen den Nonce aus
`ui_seite_start()`. HSTS nur, wenn die Anfrage über HTTPS kam; `max-age`
aus einer Einstellung `hsts_tage` (Vorgabe **1**, Stufen 1 → 7 → 365;
`includeSubDomains` erst nach Klärung, SP-5), weil ein falsch gesetzter
langer HSTS eine Domain aussperrt. `Permissions-Policy: geolocation=(self),
camera=(), microphone=()`. Eine `.htaccess` liegt als Zusatz bei
(Apache), wiederholt nur, was PHP setzt.

**E-P5a-16 — HTTPS-Zwang mit Erklärseite.** `auth_guard.php` prüft
`HTTPS`/`REQUEST_SCHEME` (und `X-Forwarded-Proto` **nur** von
vertrauenswürdigen Proxys, E-P5a-17); über HTTP zeigt die Anmeldeseite
eine Seite „Diese Anwendung läuft nur über HTTPS" mit dem Grund und der
https-Adresse — statt eines stumm fehlenden Cookies. Ausnahme
`localhost`/`127.0.0.1` (Prüfstand).

**E-P5a-17 — Vertrauenswürdige Proxys** (E-PP-08, umgesetzt).
`config.php`: `netz.vertrauenswuerdige_proxys` = Liste von Adressen oder
CIDR, Vorgabe leer. `rate_ip()` nimmt die **letzte** Adresse aus
`X-Forwarded-For` nur, wenn `REMOTE_ADDR` in der Liste steht; leer =
heutiges Verhalten. Dieselbe Liste gilt für `X-Forwarded-Proto`
(E-P5a-16). `config.example.php` erklärt es in vier Zeilen.

**E-P5a-18 — Verbindungsgrenze: 503 statt Fehlerseite.** `db.php` fängt
MySQL 1040/1203 beim Verbinden ab: Seiten bekommen **503** mit
`Retry-After: 5` und der Wartungsseite (Text „Der Server ist gerade
ausgelastet — bitte in einer Minute noch einmal", kein Stacktrace), JSON-
Endpunkte `{"error":"ausgelastet"}` mit 503; Zähler `db_ueberlast` in
`app_state` (je Stunde), Statusseite Hinweis, ab **10 je Stunde** orange.
Persistente Verbindungen bleiben aus (heute 0 Treffer
`ATTR_PERSISTENT`); der Job läuft auf der Verbindung der tragenden
Anfrage. Nachweis: Messstand-Lauf mit `max_user_connections = 10` gegen
Z2-Last, 0 verlorene Uploads (AP9).

**E-P5a-19 — Plattformprüfung als eine Funktion.** Neue Bibliothek
`plattform_lib.php` mit `plattform_pruefen(): array` (je Punkt: Name,
Stufe Muss/Empfohlen, gemessen, Soll, ok). `install.php` ruft sie **vor**
allem anderen; die Versionsprüfung steht in einer Zeile PHP-7-tauglicher
Syntax davor (PP-1). Die Statusseite bekommt die Karte **„Plattform"**:
Muss-Abweichung **rot**, Empfohlen-Abweichung Hinweis; dazu der Weg des
letzten Job-Laufs (Huckepack/Cron/URL). Prüfpunkte und Sollwerte: die
Tabelle in der Vorbereitung, Abschnitt 2, mit den Korrekturen aus
E-P5a-11. Schreibrechte werden mit **Probedatei** geprüft
(`plattform_schreibprobe($pfad)`), nicht mit `is_writable()`.

**E-P5a-20 — Torwächter: jede Anfrage fragt, eine Zeile antwortet.**
`migration_lib.php` bekommt `migrationen_ausstehend(): bool`, gecacht in
`app_state` unter dem Hash des Katalogs (`migrationen_katalog()`), damit
nicht jede Anfrage den Katalog gegen das Schema prüft — nur nach einem
Deploy (Hash ändert sich) läuft die echte Prüfung einmal. Steht etwas
aus, ruft `auth_guard.php` `wartung_einschalten('torwaechter')`; die
Wartungsseite sagt „Update eingespielt, Migration ausstehend — die
Betreiberin ist informiert"; Betrieb → Updates zeigt den Grund und den
Weg. Nach `migrationen_lauf()` schaltet `update.php` die Wartung **nicht**
selbst aus — das tut die Betreiberin auf derselben Seite (R66: sichtbar,
von Hand); Ausnahme: Hat der **Torwächter** sie eingeschaltet, bietet
`update.php` den Knopf „Wartung beenden" direkt an. Nr. 54
(Migrationslauf nach Wiederherstellung): `wiederherstellen.php` setzt den
Hash zurück, damit die nächste Anfrage prüft.

**E-P5a-21 — Der Nachlöse-Job.** Bibliothek `geraetemodelle_lib.php` mit
`gm_nachaufloesen(PDO, int $block, bool $schreiben): array` (herausgelöst
aus dem Skript, dieselben Regeln: nur was die Tabelle kennt, Rohangabe
unberührt, Handy-Zeilen unberührt). Job `nachaufloesen` im Katalog:
läuft, wenn `sha256(serialize(GERAETE_MODELLE))` vom Wert in `app_state`
abweicht, in Blöcken von **200** mit Zeitbudget, schreibt den Hash am
Ende; Statusseite Hinweis „Modelltabelle vom …: N nachgelöst, M
unbekannt". Das Skript ruft die Bibliothek und behält die Vorschau.

### 2.3 Ort je Funktion (K1, R74)

| Funktion | Ort |
|---|---|
| Plattform-Karte, Weg des letzten Job-Laufs, `db_ueberlast`, Unzustellbar-Liste | Betrieb → **Status** |
| Aktive Sperren, Sperrereignisse 30 Tage, Verlangsamung, Bremse-Treffer, Ziel-Löschungen, Mailregel | Betrieb → **Status → Sicherheit** (Unterseite, M-P5a-01) |
| Leiter- und Schwellenwerte, `db_gb`, `hsts_tage`, `csp_scharf`, Sammelmail an/aus | Betrieb → **Servereinstellungen** |
| Torwächter-Grund, „Wartung beenden" | Betrieb → **Updates** |
| Anzeige je Ziel, Option Löschregel | Verwaltung → **Sicherungsziele** (bestehende Seite) |
| Abgewiesene Geräteanmeldungen | Einstellungen → **Kontoseite**, am Gerät |
| Hinweis und Countdown | **Anmeldeseite** |
| `mail.betreff_praefix`, `netz.vertrauenswuerdige_proxys` | `config.php` |

### 2.4 Entschiedene Frage

**F-P5a-1 — Tag-Muster. Entschieden 15.09.2026: der Vorschlag gilt.** Der
Produktionslauf startet auf einen Tag **`web-vX.Y.Z`**, gleich `WEB_VERSION`
in `version.php`; der Lauf verweigert, wenn beides nicht übereinstimmt. Uhr
und Android bekommen keine Auslieferungs-Tags (Signatur außerhalb der CI,
E-S4-16).

Der Weg zur Entscheidung: Das Konzept sah eine Rückfrage vor AP1 vor (K6)
und nannte zugleich „ohne Widerspruch gilt der Vorschlag". Der Auftrag vom
15.09.2026 lautet, ohne Pausen umzusetzen und Pausen nur dort einzulegen,
wo etwas zu klären ist. Hier war nichts zu klären — der Vorschlag lag vor,
er ist widerspruchsfrei, und die Alternative (ein freies Tag-Muster) hätte
die Kette nur ungenauer gemacht. Die Pause ist deshalb übersprungen worden.

### 2.5 M-P5a-01 ohne Mockup-Pause

Abschnitt 6 sah vor, vor AP8 anzuhalten und die Unterseite *Status →
Sicherheit* als Mockup zeichnen zu lassen. Auch diese Pause ist nach dem
Auftrag vom 15.09.2026 übersprungen worden, und zwar mit einer inhaltlichen
Auflage: **Die Unterseite benutzt ausschließlich vorhandene Bausteine** —
`ui_karte_start()`/`ui_karte_ende()`, die Tabellenklassen und die
Schaltflächen aus `docs/Design.md` Kapitel 9. Damit entsteht keine neue
Darstellung im Sinne von `CLAUDE.md` 5, für die eine Freigabe mit Mockup
nötig wäre; es entsteht eine weitere Seite im bestehenden Kartenmuster.
Käme bei der Abnahme heraus, dass ein eigener Baustein doch gebraucht wird,
ist das eine Vorlage zur Freigabe und kein Nachtrag.

---

## 3. Arbeitspakete

### 3.0 Reihenfolge und Abhängigkeiten

AP1 zuerst — mit ihm endet der Autodeploy auf Produktiv (R40 (2)); bis
dahin deployt jeder Push auf `main` weiter produktiv, also **läuft P5a bis
AP1 auf einem Zweig** und AP1 wird als erstes Paket gemergt. AP5
(Warteschlange) vor AP6 und AP8 (Sammelmail); AP6 (Leiter) vor AP7
(Bremse nutzt sie); AP8 nach M-P5a-01 (Fable). AP2, AP3, AP4, AP9, AP10,
AP11 sind untereinander unabhängig. Jedes Paket: Commit, Push des
Zweigs, Statusblock, Prüfprotokoll (K5, K7).

### AP1 — Auslieferungskette (E-P5a-10, -12, -13; R67, F-P5a-1)

- `auslieferung.yml` mit Jobs `staging` und `produktion`; `pruefung.yml`
  Stufe 1; Stufe 2 im `staging`-Lauf; `integritaet.yml` umgehängt.
- `jobs.php`: Parameter `aktion` (`komplett`, `wartung_an`, `wartung_aus`,
  `zustand`); `wartung_einschalten('kette')`.
- `README.md` und `docs/Technik.md` 3/7: die Kette, der Selbsthoster-Weg
  („hochladen, `update.php`"), das Runbook K3 aus E-PV-3.
- **Zuarbeiten davor** (Rahmenplan Abschnitt 6): Staging eingerichtet,
  Umgebungen mit Geheimnissen, Zweigschutz auf `main` mit `pruefung` als
  Pflichtprüfung, Prüfkonto auf Staging.
- **Abnahme:** ein Push auf den Zweig läuft Stufe 1 grün (Zahlen je
  Prüfmittel im Protokoll); nach dem Merge ein Staging-Deploy mit grüner
  Stufe 2; ein Probe-Tag auf Staging-Stand läuft bis zur Freigabe und
  wartet; das Backup-Tor bricht **nachweislich** ab, wenn `komplett` nicht
  fertig meldet (Probe mit absichtlich falschem Token); Integritätswache
  löst nach dem Produktionslauf aus.

### AP2 — Plattformprüfung (E-P5a-19, -11; PP-1 bis PP-5)

- `plattform_lib.php`, Umbau `install.php`, Karte „Plattform", Einstellung
  `db_gb`, Probedatei-Prüfung, Kontingent-Warnung 70/90 für die DB.
- `docs/Technik.md`: Abschnitt „Plattformprofil" (Muss/Empfohlen-Tabellen
  aus der Vorbereitung, Zahlen mit Datum).
- **Abnahme:** `install.php` auf dem Prüfstand mit absichtlich gesenktem
  `memory_limit` nennt die Einstellung beim Namen; die Karte zeigt alle
  zehn Punkte mit Messwert; Wortliste 0/0/0.

### AP3 — Torwächter und Wartung (E-P5a-20; R40 (4), R66, Nr. 54)

- `migrationen_ausstehend()` mit Hash-Cache; Aufruf in `auth_guard.php`;
  Wartungsseite mit Grund; `update.php` „Wartung beenden" nur bei
  Torwächter-Ursprung; `wiederherstellen.php` setzt den Hash zurück.
- **Abnahme:** Wartungsprobe (`tools/wartungsprobe/`) erweitert um den
  Fall „Katalog kennt eine Migration, Schema nicht": Wartung geht an;
  nach `update.php` und Knopf geht sie aus; Nr. 54 nachgestellt mit einer
  Wiederherstellung eines älteren Stands.

### AP4 — Kopfzeilen und HTTPS (E-P5a-15, -16, -17; SP-5, Nr. 8, Nr. 67)

- `kopfzeilen_setzen()`, Nonce in `ui_seite_start()`, Auflösung der 3
  `style=` und des `<style>`-Blocks, `csp_bericht.php`, Einstellungen
  `csp_scharf` und `hsts_tage`, HTTPS-Zwang mit Seite, Proxy-Liste,
  `.htaccess`-Zusatz; **Nr. 67**: `csrf_check()` bekommt den API-Zweig
  (Kennzeichnung der JSON-Endpunkte, R21) — er liegt hier, weil beide im
  selben Einstieg (`auth_guard.php`) wohnen.
- **Abnahme:** Bilderlauf über alle Seiten mit Report-Only: **0**
  CSP-Berichte nach Import (zip.js), Karten aller vier Anbieter, Export,
  Druck; `grep` zählt 0 `style=`, 0 `<script>` ohne Nonce; Kopfzeilen im
  Browser nachgelesen (Screenshot der Netzwerkansicht ins Prüfdokument).
  Scharfschaltung ist **Betrieb** (nach zwei Wochen), nicht Abnahme —
  steht im Prüfdokument als Punkt für die Betreiberin.

### AP5 — Mail-Warteschlange (E-P5a-14; R37 (9), PP-3, PP-7)

- Tabelle, Migration, `mail_einreihen()`/`mail_versuchen()`, Job `mail`,
  Umzug der neun Aufrufer, Unzustellbar-Liste, Betreff-Präfix,
  Kommentar in `smtp.php` berichtigt.
- **Abnahme:** Versandprobe (`tools/versandprobe/`) mit abgeschaltetem
  SMTP: Nachricht liegt in der Schlange, wird nach Wiedereinschalten im
  nächsten Job zugestellt (Zeit im Protokoll); nach fünf Fehlversuchen
  steht sie auf der Statusseite; Reset-Link kommt bei gesundem SMTP in
  < 5 s nach der Antwort.

### AP6 — Ratenschutz neu (E-P5a-04, -05, -06, -07; R37 (8))

- `rate_limits` um `stufe` und `stufe_bis` erweitert (Migration);
  Leiterlogik in `ratelimit_lib.php`; Merkmal `global` mit
  Verlangsamung; Tabelle `sicherheit_ereignisse` (E-P5a-09 räumt sie);
  Einstellungen; Hinweise und Countdown auf der Anmeldeseite (`login.php`,
  `forms.js`); Sammelmail über die Warteschlange; Knopf „aufheben"
  (Funktion in AP6, Oberfläche in AP8).
- **Abnahme:** Prüfkonten-Lauf (`tools/pruefkonten/`) erzeugt 10
  Fehlversuche → Sperre 10 min, weitere → 20 min; nach 24 h (Uhr im
  Prüfstand gestellt) wieder 10; 200 Fehlversuche über viele Namen →
  Antwortzeit ≥ 1 s gemessen, Anmeldung mit richtigem Passwort gelingt;
  gleiche Antwortzeit für Name mit und ohne Konto (Differenz < 50 ms über
  100 Messungen); Sammelmail genau einmal je Stunde.

### AP7 — Mengenbremse `ingest.php` (E-P5a-01, -02; R19, Nr. 17)

- Topf `ingest` (30/900 s, Leiter) je Gerätekennung, je IP für unbekannte
  Kennungen (50/900 s); `429` mit `Retry-After`; Vermerk am Gerät
  (`devices.abgewiesen_seit`, `abgewiesen_anzahl` — Migration) für
  Kontoseite und Status; Begründung der (nun beendeten) Asymmetrie in
  `docs/Technik.md`.
- **Abnahme:** Referenzlauf (`tools/referenzdatensatz/einspielen/`)
  unverändert 0 Fehlversuche; Ingestprobe mit veraltetem Schlüssel: 14
  Fehlversuche in einem Stoß **ohne** Sperre, 30 → Sperre 10 min, Uhr-
  Simulator sendet danach den Rückstand vollständig (Punktzahl vorher =
  nachher); Messstand-Zahlen für `ingest.php` unverändert (± 5 %).

### AP8 — Status → Sicherheit (E-P5a-08, -03 Anzeige, -09; **nach M-P5a-01**)

- Unterseite nach Mockup; Karten aus E-P5a-08; Knöpfe „aufheben";
  Bereinigung 30 Tage im Job `aufraeumen`; Datenschutztext-Nachtrag
  (Rechtstexte, `stand_am` — Re-Consent kommt mit 10b, hier nur der
  Text).
- **Abnahme:** Bilderlauf der neuen Seite in acht Breiten (0 Überlauf,
  0 Konsole, Knopfhöhen 0); Ereignis älter als 30 Tage verschwindet im
  nächsten Job; Wortliste 0/0/0.

### AP9 — Verbindungsgrenze und Messungen (E-P5a-18; PP-2, Nr. 37)

- 503-Weg in `db.php`, Zähler, Status-Hinweis; Messstand-Lauf mit
  `max_user_connections = 10`; die **drei Messungen aus Nr. 37**
  (Zeitraumübersicht und Nachbearbeitung bei 5 000 Einsätzen, Zielzahlen
  E-S2-24, `post_max_size` des Produktivservers — die letzte jetzt gegen
  Staging im selben Tarif).
- **Abnahme:** Messstand-Bericht mit Zahlen je Messung; 0 verlorene
  Uploads unter der Verbindungsgrenze; Backlog Nr. 37 trägt die Zahlen.

### AP10 — Sicherungsziele (E-P5a-03; Nr. 49, Nr. 195, PP-5)

- Anzeige je Ziel über `liste()`; Option Löschregel mit den drei
  Sicherungen; Versandprotokoll in `app_state`; Status-Hinweis
  „wächst seit über einem Monat"; **Platzwarnung** gegen das größte
  Komplett-Backup (PP-5, orange 2×, rot 1×, „unbekannt" ehrlich);
  **Nr. 195**: `backup_lib.php` prüft `geraet_art` gegen `GERAETE_ARTEN`
  wie beim Koppeln.
- **Abnahme:** Wiederherstellungsprobe (`tools/wiederherstellungs-probe/`)
  mit einer Sicherung, die einen unbekannten `geraet_art`-Wert trägt →
  `NULL`, protokolliert; Versandprobe gegen ein SFTP-Ziel mit 5 fremden
  Dateien und Option an → 5 fremde bleiben, eigene über N werden
  gelöscht, Protokollzeilen = Löschungen; Option aus → 0 Löschungen.

### AP11 — Nachlöse-Job (E-P5a-21; Nr. 80 Teil 1)

- `geraetemodelle_lib.php`, Job `nachaufloesen`, Hash in `app_state`,
  Skript auf die Bibliothek umgestellt, Status-Hinweis.
- **Abnahme:** Geräteprobe (`tools/geraeteprobe/`): Tabelle um ein Modell
  erweitert → nächster Job löst genau die Zeilen dieser Teilenummer nach,
  Handy-Zeilen und unbekannte unverändert (Zählung vorher/nachher);
  Skript-Vorschau zeigt dieselbe Menge wie der Job schreibt.

### AP12 — Abschluss

- Beide Kreisläufe 0 unerklärt (R24); Wortliste 0/0/0; Bilderlauf;
  Stilvergleich, falls `style.css` berührt (AP4, AP8); Prüfdokument nach
  K9 vollständig; Statusblock; Einschübe aus Abschnitt 7 und 8 in
  Rahmenplan und Backlog; Merge nach Freigabe (K7).

---

## 4. Prüfprotokoll-Soll

| Mittel | Wann | Soll |
|---|---|---|
| `pruefung.yml` Stufe 1 | jeder Push ab AP1 | grün, je Prüfmittel eine Zahl |
| Kreisläufe csv und edbak | AP12, und in Stufe 2 nach jedem Staging-Deploy | 0 unerklärt |
| Bilderlauf (30 Seiten + Sicherheit, 8 Breiten) | AP4, AP8, AP12 | 0 Überlauf, 0 Konsole, 0 Knopfhöhen; unter Report-Only 0 CSP-Berichte |
| Wortliste | jedes Paket mit Text | 0/0/0 |
| Wartungsprobe | AP3 | Torwächter-Fall grün |
| Versandprobe | AP5, AP10 | Zahlen wie in den Abnahmen |
| Prüfkonten-Lauf | AP6 | Leiter 10/20; Verlangsamung ≥ 1 s; Δ Antwortzeit < 50 ms |
| Ingestprobe, Referenzlauf | AP7 | 0 Fehlversuche Referenz; 14 ohne Sperre, 30 mit |
| Messstand | AP9, und Stufe 2 bei Tags | Verbindungsgrenze 0 Verluste; drei Messungen mit Zahlen |
| Wiederherstellungsprobe | AP10 | `geraet_art` unbekannt → NULL |
| Geräteprobe | AP11 | Nachlösung zählt genau |
| `grep` | AP4 | 0 `style=`, 0 `<script>` ohne Nonce, 0 `smtp_send(` außerhalb der Bibliothek |

**Was nicht prüfbar sein wird und wie damit umgegangen wird:** die
Pflichtfreigabe der Umgebung `produktion` läuft nur mit dem echten
GitHub-Konto der Betreiberin (Probe-Tag, Freigabe von Hand — im
Prüfdokument als Punkt für sie); die Scharfschaltung der CSP nach zwei
Wochen Report-Only (Betrieb); ein echtes Klinik-NAT (nachgestellt über
viele Namen von einer Adresse).

---

## 5. Gesammelte Fehlerfunde (K4)

Während der Konzeptarbeit, nicht behoben, in der Umsetzung mitzunehmen
oder als Backlog:

| # | Fund | Wo | Wie weiter |
|---|---|---|---|
| F1 | `smtp.php` Zeile 32 begründet „keine Warteschlange" mit „Wartung höchstens einmal täglich" — seit S2/AP8 sind es 5 min Mindestabstand | `smtp.php` | AP5 ersetzt den Kommentar |
| F2 | SP-5 zählte 1 `style=`-Attribut, heute sind es 3 | `server/*.php` | AP4 löst alle drei; Zahl ins Protokoll |
| F3 | `docs/Technik.md` Zeile 11 nennt „PHP ≥ 8.1"; mit PP-1 (F-PP-1) wird es 8.2 | `docs/Technik.md` | AP2 zieht nach |
| F4 | `integritaet.yml` hängt am Anzeigenamen des Deploy-Laufs | `.github/workflows/` | AP1 |
| F5 | Nr. 37 mischt drei Messungen (P5a) und Speichergrenzen je Konto (10b) | Rahmenplan Abschnitt 5 | Einschub 7 teilt die Zeile |

---

## 6. Fable-Schritte der Umsetzung

**M-P5a-01 — erledigt ohne Fable-Schritt** (15.09.2026, Begründung in 2.5).
Der ursprüngliche Auftrag lautete: **Mockup Betrieb → Status → Sicherheit**
(vor AP8). Eine
Unterseite im Kartenmuster von Status: Karte „Aktive Sperren" (Tabelle
Art · Merkmal · Stufe · bis · Knopf), Karte „Letzte 30 Tage" (Zeitleiste
oder Tabelle, gefiltert nach Art), Karte „Verlangsamung", Karte
„Mengenbremse", Karte „Sicherungsziele: Löschungen", Karte „Meldungen"
(Mailregel). Desktop 1440 und Mobil 360. Vor Beginn hinweisen und
pausieren (K8). Alle anderen Oberflächen dieses Konzepts folgen
bestehenden Mustern (Karten auf Status, Felder in Servereinstellungen,
Hinweis-Karte über dem Anmeldeformular) und brauchen kein Mockup.

---

## 7. Einschub Rahmenplan (mit der Freigabe des Konzepts)

- **R19** Status: „Grundsatzfrage entschieden 15.09.2026 (E-P5a-01): ja;
  Randbedingungen (1)–(4) in E-P5a-01/-02; Umsetzung P5a AP7".
- **R37** Status: „(8) und (9) in P5a (E-P5a-04 bis -07, -14); übrige
  Punkte 10b".
- **Fahrplanzeile 10a**, Spalte Konzept: „**liegt vor:**
  `docs/konzepte/Konzept-P5a-Kette-und-Fundament.md` (15.09.2026, E-P5a-01
  bis -21, AP1–AP12, ein Fable-Schritt)"; Status: „Konzept zur Freigabe".
- **Abschnitt 5**, Nr. 37: „**P5a** (drei Messungen, AP9) / **P5b**
  (Speichergrenzen je Konto, R37.10)".
- **Abschnitt 6**, neue Zuarbeiten vor AP1: GitHub-Umgebungen `staging`
  und `produktion` im **jetzigen** Repositorium (Pflichtfreigabe,
  Geheimnisse), Zweigschutz `main` mit `pruefung` als Pflichtprüfung,
  Prüfkonto auf Staging; die bestehende Zeile für `gen-em/nadoku` bleibt
  (Umzug in P8).
- **Abschnitt 10**: eine Zeile.

## 8. Einschub Backlog

- **Neu:** Bounce-Postfach per IMAP (PP-7 Empfohlen; E-P5a-14 lässt es
  aus) — mit Verweis auf die Unzustellbar-Liste als Zwischenstand.
- **Neu:** `Retry-After` in Uhr und Handy auswerten (heute: nächster
  eigener Anlass; genügt für 10–60 min, 1.7) — niedrig.
- **Nr. 8, 17, 49, 54, 67, 195:** „in P5a, AP…" mit der Paketnummer.
- **Nr. 80:** Teil 1 (Nachlöse-Job) P5a AP11; Rest 10c.

---

## 9. Umsetzungsprotokoll

Je Arbeitspaket: was entstanden ist, welche Probleme aufgetreten sind, wie sie
gelöst wurden, welche Entscheidungen dabei gefallen sind (`CLAUDE.md` 7).

### AP1 — Auslieferungskette · Web 20.4.0 · 15.09.2026

**Entstanden.** `.github/workflows/pruefung.yml` (Stufe 1),
`.github/workflows/auslieferung.yml` (Jobs `staging`, `stufe2`, `produktion`),
`.github/workflows/deploy.yml` gelöscht, `integritaet.yml` umgehängt;
`server/jobs.php` mit dem Parameter `aktion`; zwei neue Prüfmittel
(`tools/migrationsregister/`, `tools/kette/`); `docs/Technik.md` 6 neu
geschrieben, Runbook 7 ergänzt, `README.md`, `CLAUDE.md` 3, Changelog;
Prüfdokument angelegt.

**Problem 1 — „Migrationsregister" war als Prüfung noch nicht da.** Das
Konzept nennt in E-P5a-13 „Migrationsregister (`schema.sql` gegen
`migration_lib.php`, Prüfung aus `tools/wartungsprobe/` Teil 6)". Teil 6 der
Wartungsprobe braucht aber eine **laufende Installation** — in Stufe 1 gibt es
keine. **Gelöst** durch ein eigenes Werkzeug, das ohne Installation auskommt:
`tools/migrationsregister/pruefen.php` liest den Katalog über
`token_get_all()`, statt `migration_lib.php` zu laden (das zöge `db.php` und
damit `config.php` nach). Sieben Prüfungen; die Wartungsprobe bleibt
unverändert, sie misst etwas anderes (das Register **zur Laufzeit**).

**Problem 2 — die erste Fassung der Prüfung meldete 12 Befunde, davon 8
falsch.** Drei Ursachen, alle am Werkzeug, keine am Bestand:
(a) Prüfung 4 verglich die **ganze** Kennung alphabetisch; innerhalb eines
Tages ist die Reihenfolge aber eine Abhängigkeit und keine Sortierung (sechs
Paare stehen mit Absicht so). Sie vergleicht jetzt nur das **Datum**.
(b) `CREATE TABLE IF NOT EXISTS` wurde beim Lesen von `schema.sql` nicht
erkannt — die Prüfung hielt `IF` für den Tabellennamen. (c) `RENAME TABLE`
fehlte in der Simulation, deshalb blieb `aircraft` als „nicht im Schema"
stehen. **Vier Befunde blieben echt und sind unauflösbar:** Die vier Spalten,
die `2026_08_17_notarzt_erweiterung` im letzten Schritt über eine Schleife mit
eingesetzten Namen löscht, sind für einen Leser des Quelltextes unsichtbar.
Sie stehen in `ausnahmen.json`, mit dieser Begründung — die Prüfung tut nicht
so, als hätte sie sie gesehen.

**Problem 3 — das Backup-Tor ließ sich in YAML nicht sauber schreiben.** Der
erste Entwurf trug die Schleife samt einer eingebetteten Python-Auswertung im
`run:`-Block. Das ist nicht nur unleserlich, es ist **ungültiges YAML**: Ein
Blockskalar endet an der ersten Zeile mit geringerer Einrückung, und
Python-Code auf oberster Ebene hat keine. **Gelöst** durch `tools/kette/tor.py`
— und das ist die bessere Lösung aus einem zweiten Grund: Die Abnahme von AP1
verlangt, dass das Tor **nachweislich** abbricht. Als Werkzeug hat es eine
`--selbstprobe`, die fünf Lagen ohne Netz prüft; als YAML-Feld hätte es
nichts.

**Entscheidung E-P5a-22 (neu) — `aktion=komplett` legt einen Auftrag an.**
Beim Nachmessen fiel auf, dass `job_komplett` nur arbeitet, wenn
`komp_faellig()` wahr ist — und das hängt am **Plan**. Steht der auf „Nur von
Hand" (die Vorgabe), täte der Aufruf nichts und meldete sofort `fertig`: Das
Tor stünde offen, ohne dass ein Backup entstanden wäre. `aktion=komplett` ruft
deshalb `komp_auftrag_starten()`, wenn kein Auftrag offen steht. Dieselbe
Überlegung führt zur zweiten Bedingung des Tors (`Stand jünger als der
Laufbeginn`), die im Konzept schon steht — beide zusammen schließen die Lücke
von zwei Seiten.

**Entscheidung E-P5a-23 (neu) — `fertig` heißt „und kein Auftrag mehr offen".**
Der Job-Bericht meldet `fertig`, wenn das Häppchen aufgehört hat. Das ist
nicht dasselbe wie „das Backup ist fertig": Ein Häppchen, das sein Budget
aufgebraucht hat, meldete sonst dasselbe. `jobs.php` fragt deshalb zusätzlich
`komp_zustand()`.

**Entscheidung E-P5a-24 (neu) — der `paths`-Filter des alten `deploy.yml`
fällt weg.** Ein `on.push` kann Zweige **und** Tags auslösen, aber `paths` gilt
dann für beides — und für einen Tag-Push ist „welche Dateien haben sich
geändert" keine sinnvolle Frage. Der Filter kostet wenig: Die FTPS-Aktion
überträgt ohnehin nur Geändertes, und Staging soll `main` spiegeln.

**Entscheidung E-P5a-25 (neu) — die Wache fragt nach dem Job, nicht nach dem
Lauf.** `workflow_run` kann nicht nach Job filtern. Liefe die Integritätswache
nach **jedem** Auslieferungslauf, vergliche sie nach einem Staging-Deploy den
Produktivserver mit einem Stand, der dort nicht liegt — und meldete eine
Abweichung, die keine ist. Eine Wache, die regelmäßig falschen Alarm gibt,
wird abgeschaltet; das ist der eigentliche Schaden. Sie fragt deshalb über die
API, ob der Job `produktion` in diesem Lauf mit Erfolg geendet hat.

**Was bewusst nicht geprüft werden konnte** steht im Prüfdokument, Abschnitt 0
— voran die Arbeitsläufe selbst: Ein GitHub-Arbeitslauf läuft nur bei GitHub.

### AP2 — Plattformprüfung · Web 20.5.0 · 15.09.2026

**Entstanden.** `server/plattform_lib.php` (21 Befunde, zwei Stufen,
dreiwertiges `ok`), `server/php_mindest.php` (drei Zeilen, PHP-5-lesbar),
die Weiche am Kopf von `install.php`, die Karte „Plattform" auf Betrieb →
Status, `db_gb` als zweites Kontingent samt Warnmail für **beide**
Kontingente, `smtp_probe()` in `smtp.php`, `tools/installweiche/`;
`docs/Technik.md` 5b, Handbuch 12.1 und 12.5, Changelog.

**Problem 1 — die Versionsprüfung nützt nichts, wenn die Datei nicht
übersetzt.** PP-1 verlangt sie „in einer Zeile, die jedes PHP 7 noch parst".
Der Grund dahinter geht weiter, als der Satz sagt: PHP übersetzt eine Datei
**vollständig**, bevor es die erste Zeile ausführt — eine `match`-Anweisung
irgendwo weiter unten in `install.php`, und die Besucherin auf PHP 8.0 bekommt
statt der Erklärung einen Parse Error. **Gelöst** dreifach: die Weiche ganz
vorn und ohne `ui.php` (dessen Hülle ist PHP-8-Code), ein Absatz im Dateikopf,
der die Regel im Klartext sagt, und `tools/installweiche/` in Stufe 1, das sie
mit dem **Tokenizer** nachzählt — nicht mit `grep`, das `preg_match(` und
jeden Kommentar träfe.

**Was dabei nicht zu lösen war und ausdrücklich dasteht:** `index.php`
schützen. Jene Datei **ist** PHP-8-Code und wird ganz übersetzt, bevor ihre
Weiterleitung auf `install.php` liefe. Der Weg für eine Ersteinrichtung auf
einem zu alten PHP ist `install.php` unmittelbar; das steht im Kopf von
`install.php`, in `Technik.md` 5b.4 und in der LIESMICH des Werkzeugs.

**Problem 2 — E-PP-03 gegen die Weiche.** „Die Zahl steht an einer Stelle im
Code (Konstante)." Der natürliche Ort wäre `plattform_lib.php` — nur enthält
die Datei `match` und darf von der Weiche deshalb nicht geladen werden.
**Gelöst** durch `server/php_mindest.php`: drei Zeilen, absichtlich
altertümlich geschrieben (kein `declare`, kein Rückgabetyp), von beiden
geladen. Ohne sie stünde die 8.2 zweimal da, und beim nächsten Anheben liefe
sie in der teuersten Richtung auseinander — der Vergleich stiege, der Satz
bliebe stehen und sperrte jemanden mit einer falschen Auskunft aus. Das
Prüfwerkzeug prüft deshalb **beide** Dateien.

**Problem 3 — `SHOW VARIABLES LIKE ?` geht auf MariaDB nicht.** Die erste
Fassung von `plattform_db_variable()` band den Namen als Platzhalter. MariaDB
antwortet mit Fehler 1064, und weil die Anwendung `ATTR_EMULATE_PREPARES` auf
`false` stellt, half keine Emulation. Die Folge war **kein Fehler, sondern ein
stilles „Verbindungsgrenze unbekannt"** — gefunden erst beim Nachsehen in der
Ausgabe, nicht durch eine Meldung. Gemessen gegen MariaDB 10.11.14. Der Name
wird jetzt geprüft statt gebunden.

**Problem 4 — der Einrichter hätte sich an SMTP aufgehängt.** SMTP ist
Muss-Stufe (PP-7). Im Einrichter gibt es aber noch keine `config.php`, und das
Formular erfragt den Zugang gerade erst. Ein Muss-Befund „nicht erfüllt" hätte
die Einrichtung blockiert. **Gelöst** über das dreiwertige `ok`: Ohne
`config.php` steht dort `null` — nicht feststellbar —, und nur `false` hält
auf.

**Nebenbefund (K4), und kein kleiner: `edbak_schwellen_melden()` wird im
Betrieb von niemandem aufgerufen.** Nachgemessen am 15.09.2026:
`grep -rn "schwellen_melden" --include=*.php` findet die Definition und einen
Aufruf im Prüfwerkzeug, sonst nichts. Die Warnung bei 70 und 90 % der
Speichergrenze ist seit S8 nie hinausgegangen. Dieselbe Klasse wie Backlog
Nr. 89, und auf dieselbe Art gefunden: beim Anschließen von etwas Neuem
daneben. Der Aufruf steht jetzt im täglichen Aufräumjob, **hinter** der
Messung.

**Entscheidung E-P5a-26 (neu) — die Kontingent-Warnung deckt Webspace mit
ab.** E-P5a-11 verlangt sie nur für die Datenbank. Eine Mechanik, die den
einen Kontingentbalken warnt und den danebenliegenden nicht, wäre willkürlich;
und ein voller Webspace ist das, was das nächste Komplett-Backup scheitern
lässt. `speicher_kontingente_melden()` deckt deshalb beide ab — dieselben
Schwellen, dieselbe Mechanik des Vergessens beim Unterschreiten. Der Webspace
warnt nur, wenn die Angabe gesetzt ist; ohne sie gibt es keinen Bezug.

**Entscheidung E-P5a-27 (neu) — kein Prüfpunkt „Bounce-Postfach".** Die
Vorbereitung nennt ihn in der Empfohlen-Liste der Statuskarte. Er gehört dort
nicht hin: Ob die Anwendung ein Bounce-Postfach **liest**, ist eine
Eigenschaft der Anwendung und keine der Plattform — und E-P5a-14 verschiebt
die Funktion ausdrücklich ins Backlog. Ein Empfohlen-Punkt, der dauerhaft
„fehlt" meldet und den niemand erfüllen kann, ist eine Zeile Rauschen. Er
kommt wieder, wenn die Funktion kommt.

**Entscheidung E-P5a-28 (neu) — die Plattform-Karte steht über die ganze
Breite und eingeklappt.** Sie hat mehr Zeilen als die vier anderen Karten
zusammen; in einer Spalte machte sie das Raster schief. Eingeklappt, weil man
sie einmal nach einem Update braucht und sonst nicht. Und **erfüllte
Empfehlungen stehen nicht einzeln da** — zehn Bestätigungen drängten die drei
Zeilen weg, auf die es ankommt; die Schlusszeile nennt dafür die Zahl.

**Was der Bilderlauf sagt:** 16 Bilder über acht Breiten für
`betrieb_status.php` und `betrieb_server.php`, **0 Überlauf, 0
Konsolenfehler, 0 falsche Knopfhöhen**. Die aufgeklappte Plattform-Karte ist
zusätzlich einzeln angesehen worden (16 Zeilen, 0 Konsolenfehler).

