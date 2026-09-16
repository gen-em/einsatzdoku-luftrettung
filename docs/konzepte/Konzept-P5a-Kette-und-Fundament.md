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
> | Entschieden | E-P5a-01 bis E-P5a-21 (Abschnitt 2), dazu die in der Umsetzung gefallenen E-P5a-22 bis **-49**; E-PP-01 bis -09 übernommen; **F-P5a-1 entschieden** (2.4) |
> | Offen | — |
> | Umsetzung | **läuft.** AP1–AP7 und AP4a erledigt, **AP8 als Nächstes** (Status → Sicherheit, nach Mockup M-P5a-01); Abhängigkeiten in 3.0 |
> | Fable-Schritte der Umsetzung | **keiner mehr** — M-P5a-01 ist nach Auftrag vom 15.09.2026 ohne Pause umgesetzt worden (2.5) |

> **Stand der Umsetzung**
>
> | Paket | Stand | Stufe | Abnahmezahlen |
> |---|---|---|---|
> | AP0 Aufnahme | **erledigt** | — | Rahmenplan Fassung 72, Vorbereitung und Konzept im Repositorium |
> | **AP1 Auslieferungskette** | **erledigt** | **Web 20.4.0** | Register 46/46, 0 Befunde, Selbstprobe 4/4 · Backup-Tor 5/5 · Wortliste 0/0/0 · Kontraste 22/0 · Vollständigkeit 340 = unverändert · 3 Arbeitsläufe gültiges YAML |
> | **AP2 Plattformprüfung** | **erledigt** | **Web 20.5.0** | 21 Befunde (15 ohne DB/config) · Muss offen 0 · Installweiche 8/8, 0 Befunde auf 658 Zeilen · Bilderlauf 16 Bilder, 0/0/0 · Wortliste 0/0/0 |
> | **AP3 Torwächter** | **erledigt** | **Web 20.6.0** | Wartungsprobe **67 Erwartungen, 0 nicht erfüllt** (Teil 7 neu, 10 Erwartungen) · Browserprobe 12/12 · Bilderlauf 8 Bilder, 0/0/0 · Wortliste 0/0/0 |
> | **AP4 Kopfzeilen und HTTPS** | **erledigt** | **Web 20.7.0** | CSP-Probe **0 Befunde** (106 Dateien, 108 Skript-Stellen), Selbstprobe 8/8 · Browserprobe **33/33, 0 Seitenfehler** · Bilderlauf **392 Bilder, 0/0/0** und **0 CSP-Berichte** (dritter Lauf — die zwei davor fanden F6 und F7) · Wartungsprobe 67/0 · Integritätswache 30/30 und **kein Unterschied** · Wortliste 0/0/0 · Kontraste 22/0 · Vollständigkeit 365 gegen 351 (`style=` **13→10**, Unicode +17 — alle in Kommentaren) |
> | **AP5 Mail-Warteschlange** | **erledigt** | **Web 20.8.0 · 20.9.0** | Teil 1 (Name der Installation, E-P5a-35): 38 Stellen auf zwei Werte, 6/6 Einschleusversuche abgewiesen · Teil 2 (Warteschlange): Katalog mit **10** Nachrichten, **alle zehn** Versandstellen umgezogen — `smtp_send()` hat ausserhalb von `mail_lib.php` **0** Aufrufer · `tools/mailprobe/` **41 Prüfungen, 0 Befunde** gegen eine eigene SMTPS-Gegenstelle · `tools/jobprobe/` **35/35** (Teil 10 neu) · Frist statt Dauer: schweigender Server **5,01 s** bei 5 s Budget, 12 Fortsetzungszeilen je 1 s **5,00 s** (ohne Frist wären es über 13 s) · Leiter 300 s gemessen, `unzustellbar` nach 5, `zu_spaet` nach **3** bei 1 h Frist · Job am Huckepack-Weg 4 Nachrichten in 0,37 s, hängender Server **3,01 s** bei 3,0 s Vorgabe · Adressen (E-P5a-40) 7 Fälle, **5 abgewiesen** · `grep gen-em.org server/` = **0**, `grep base_url server/` = nur Bibliothek und Quelle · Wortliste **0/0/0** (96 Ausnahmen, 96 gegriffen) · Vollständigkeit **367** (371 → 367, der Doppelbestand der Mailtexte ist weg; die im Commit genannten 366 waren **mitten im Paket gemessen** und um 1 zu niedrig — nachgezogen in AP4a) · Migrationsregister **0 Befunde**, 49/49 Kennungen · CSP-Probe **0** auf 108 Stellen · Browser: Status und Installation, **0 Konsolenfehler** |
> | **AP4a Sicherheitszeilen** (Nachtrag 16.09.2026) | **erledigt** | **Web 20.9.1** | Nr. 205: `use_strict_mode` vor **7 von 7** `session_start()`-Aufrufen (vorher 2, und zwar die beiden ohne Anmeldesitzung) · Gegenprobe gemessen: **ohne** die Zeile kein `Set-Cookie` (Kennung übernommen), **mit** ihr eine neue — `php.ini` des Prüfstands steht auf `Off` · neu `tools/sitzungshaertung/` in Stufe 1, Selbstprobe **8/8**, Lauf **108 Dateien, 7 Aufrufe, 0 ohne Härtung** · Nr. 203: `json_kopf()` / `json_roh_out()` / `json_out()`, **7 Stellen** umgestellt (nicht 3 — `auth_salt.php`, `jobs.php`, `pair.php` hatten denselben Mangel) · gemessen im Browser: `export_data.php` **200, 68 820 Byte Spurpunkte** mit `no-store`+`nosniff`, `backup_data.php` **200, 19 603 Byte** · `grep Content-Type: application/json server/` = **2 Codezeilen** (`db.php`, `wartung_lib.php` — jene darf `db.php` nicht laden) · Wortliste **0/0/0** (erster Lauf: 2 Treffer, der eigene Satz über die verschwundene Domain) · Vollständigkeit **367 = unverändert** (AP4a fügt netto 0 hinzu; die Schwelle der Kette von 366 auf 367 richtiggestellt) · Kontraste 22/0 · CSP-Probe **0** · Migrationsregister **0** |
> | **AP6 Ratenschutz neu** | **erledigt** | **Web 20.10.0** | `tools/ratenprobe/` neu: **49 Prüfungen, 0 Befunde** · Leiter 15/20/30/60 min gemessen, Stufe 1→2→3→4 und Deckel bei 4 · Verfall nach 24 h gemessen, Gegenprobe (laufende Frist wird verlängert) ebenso · **zwei Fehler gefunden, die ohne Meldung durchgegangen wären**: Klopfen hätte die laufende Sperre gelöscht, und die Stufe wäre nie zurückgefallen · zwei Schwellen 10/50 gemessen · Verlangsamung 200/400/800/1600 → 1/2/4/8 s, Stufe 1 **1,00 s**, Stufe 4 **8,0 s** (gedeckelt), abgelaufenes Fenster → 0 · gleiche Antwortzeit **0,3 ms Differenz über 100 Messungen** · Sammelmail 1 je Stunde gemessen, abschaltbar · Einstellungen 6 Fälle im Browser, 4 abgewiesen, **nichts halb gespeichert** (Gegenprobe) · Anmeldeseite: Sperrmeldung **wortgleich für echtes und erfundenes Konto**, Countdown tickt (43→38 s), Formular entsperrt sich nach Ablauf, 0 Konsolenfehler · Migrationsregister **51/51, 34 Tabellen, 0 Befunde** · kopplungsprobe **76/0** (ein Prüffall hing an einer festen Migrationszahl und war schon vorher rot), wartungsprobe **67/0**, mailprobe **41/0**, jobprobe **35/0** · Wortliste **0/0/0** · Vollständigkeit **372** (367 + 5 Pfeile in neuen sichtbaren Texten; 5 Auslassungszeichen wieder entfernt) · CSP **0**, Sitzungshärtung **0**, Kontraste **22/0** |
> | **AP7 Mengenbremse `ingest.php`** | **erledigt** | **Web 20.11.0** | Ingestprobe um Teil 10 erweitert: **83 Erwartungen, 0 nicht erfüllt** (21 davon neu) · gemessen: **14 Fehlversuche ohne Sperre**, Versuche 15–30 weiterhin `401`, **Versuch 31 = `429`** mit `Retry-After: 900` und Rumpf `zu_viele_versuche` · die Antwort nennt den Topf **nicht** · `403 device_disabled`, `400 payload`, `413 too_large` zählen **nicht** (drei Gegenproben) · zweites Gerät an derselben Adresse **`200`** — Adresstopf leer · gelungener Upload leert Topf und Vermerk (`anzahl 30 → 0`) · **30 erfundene Kennungen erreichen dieselbe Schwelle** wie eine bekannte (E-P5a-47) · genau **ein** Sperrereignis je Sperre · Laufzeit über den erzeugten Sendeplan (612 Anfragen, 64 478 Punkte, **je zwei Läufe**): Median **14,43 ms ohne / 15,14 ms mit** (+4,9 %), Mittel **19,67 / 20,33 ms** (+3,4 %), **0 Fehlversuche in allen vier Läufen** — bei 3 bis 4 % Streuung zwischen zwei *gleichen* Läufen · Ratenprobe **50/0** (zwei Prüfungen neu) · Migrationsregister **52/52, 219 Spalten, 0 Befunde** · Bilderlauf der zwei berührten Seiten **16 Bilder, 0 Überlauf / 0 Konsole / 0 Knopfhöhen**, mit gesetztem Vermerk aufgenommen · Wortliste **0/0/0** (98 Regeln, 98 gegriffen) · Vollständigkeit **372 = unverändert** |
> | AP8 bis AP12 | offen | — | — |

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

**E-P5a-35 (neu, 16.09.2026) — der Name dieser Installation steht an einer
Stelle.** Aufgekommen als Rückfrage während AP5: „Sollen wir den Namen der
Instanz und den Kurznamen in den Einstellungen als Variable festlegen?"

**Gemessen, bevor entschieden wurde:** 38 sichtbare Stellen, **drei**
Schreibweisen — „Gen-EM NAdoku" (Browsertab, Kopfleiste, Anmeldeseite,
Wartungsseite, Schlüsselblatt, Installer, GPX-Datei, `from_name`), „Gen-EM
Einsatzdokumentation Notarzt" (alle acht Mailtexte) und „Einsatzdokumentation
Notarzt" in der Testmail, **ohne „Gen-EM"**. Die dritte ist der Beweis: Eine
abweichende Schreibweise fällt niemandem auf, solange man acht Dateien
nebeneinanderlegen müsste.

**Zwei Werte in `app_state`** (`instanz_name` lang, `instanz_kurz` kurz),
gepflegt unter Verwaltung → Installation. Vorgaben sind die heutigen
Zeichenketten — wer nichts einstellt, merkt nichts.

**Warum es in AP5 gehört und nicht in ein eigenes Paket:** AP5 schreibt alle
zehn Mail-Aufrufstellen ohnehin um. Den Namen dabei hartkodiert in zehn frische
Katalogeinträge zu schreiben, hieße, das Problem in neuen Code einzubauen.

**Zwei Stellen bleiben fest**, beide begründet: die Fußzeile „© Gen-EM · Open
Source" (Urheberschaft der Software, nicht Name des Betriebs) und
`GPX_CREATOR` (gehört zum Exportformat; einstellbar verglichen die
eingecheckten Referenzausführungen Äpfel mit Birnen).

**E-P5a-37 (neu, 16.09.2026, Auftraggeber; Backlog Nr. 204) — `smtp.php`
protokolliert keine Empfängeradresse.** Die Zeile
`error_log('SMTP: Versand an ' . $toEmail . ' fehlgeschlagen')` war die
**einzige Stelle mit Personenbezug** im Fehlerprotokoll und widersprach der
Zusage im Kopf derselben Datei. **Entscheidung: Die Zusage gilt.** Die Meldung
nennt Kennung und Grund; der Empfänger steht in `mail_warteschlange`, wo er
nach 30 Tagen verfällt (E-P5a-09).

**Damit die Kennung nicht ins Leere zeigt** (Zusatz dieser Instanz): Die
Warteschlange schreibt **dieselbe** Kennung in ihre Fehlerspalte. Sonst wäre
die Änderung keine Verbesserung, sondern ein Verlust — ein Protokoll ohne
Adressat und eine Liste ohne Ursache. Die übrigen zehn `error_log()`-Aufrufe
in `smtp.php`, `email_lib.php`, `pair.php` und `reset_request.php` sind
mitgeprüft: keiner nennt Adresse, Kennung oder Token.

**E-P5a-39 (neu, 16.09.2026) — was beim Endzustand geleert wird, hängt vom
Endzustand ab.** Der Auftraggeber hat „Weg B" freigegeben (Zeile bleibt 30
Tage, Rumpf fällt) und dabei zu Recht nachgefasst: *„Fehler sollten mit
Mailadresse protokolliert bleiben, damit sie nachvollziehbar sind."*

| Zustand | `empfaenger` | `betreff` | `text` |
|---|---|---|---|
| offen | bleibt | bleibt | bleibt — sonst kann der Job nicht senden |
| zugestellt | **fällt** | **fällt** | **fällt** |
| unzustellbar | **bleibt** | bleibt | **fällt** |
| ueberholt / zu_spaet | fällt | fällt | fällt |

Der **Rumpf fällt immer** (Token). Bei **unzustellbar bleibt die Adresse** —
„die Einladung an X kam nie an" ist ohne X wertlos, und E-P5a-14 verlangt die
Liste ausdrücklich „mit Empfänger und Grund". Das ist eine benannte Ausnahme
von der Zusage in `smtp.php`, und sie steht **dort neben der Zusage**, nicht
davon getrennt: Eine Liste *gescheiterter* Zustellungen ist kein Protokoll
darüber, wer Post *bekommen* hat, sondern eine Mängelliste.

**E-P5a-36 (neu, 16.09.2026) — ein Mailrahmen und eine Basisadresse**
(Nachtrag des Auftraggebers, Herkunft Backlog Nr. 202 Paket 1, R83).
`mail_rahmen($anrede, $kern, $schluss = null)` und `app_url($pfad = '')`
liegen in `instanz_lib.php`, also dort, wo E-P5a-35 den Namen hält. Alle zehn
Katalogeinträge benutzen den Rahmen — die Testmail eingeschlossen, die bis
Web 20.7.0 die einzige ohne Anrede, ohne Kontaktzeile und ohne „Gen-EM" im
Betreff war.

`app_url()` ersetzt sieben Handverkettungen, **fünf davon ohne `rtrim()`**.
Die zwei mit sind der Beweis, dass es aufgefallen ist — nur eben nicht
überall. *Nicht* angefasst: die Token-Ausstellung (`reset_token_ausstellen()`,
vier Stellen) — das bleibt Schritt 15.

**E-P5a-40 (neu, 16.09.2026) — Kontaktadresse und Betreiberadresse sind
Einstellungen.** Aufgekommen als Anweisung des Auftraggebers während AP5:
*„Kontaktadresse muss in die Servereinstellungen … es sollte idealerweise
nichts von gen-em.org und keine gen-em E-Mail-Adressen im Code hartkodiert
stehen."*

Dieselbe Fehlerklasse wie der Name, eine Fassung später gefunden: In **sieben**
Mailtexten stand dieselbe **persönliche** Adresse des Entwicklers.

- `instanz_kontakt()` — die Zeile „Bei Fragen wende dich an …" in **jeder**
  Mail. **Leer heißt: die Zeile fällt weg.** Nicht `smtp.from` — das ist der
  Absender und auf einer gut eingerichteten Anlage ein `noreply@`; eine Mail,
  die im Fehlerfall auf ein ungelesenes Postfach verweist, ist schlimmer als
  eine ohne Verweis.
- `betrieb_mail()` — wohin Betriebspost geht. **Leer heißt: weiterhin an alle
  mit Verwaltungsrecht.** Die vorsichtige Richtung; eine leere Einstellung darf
  keine Warnung verschlucken.
- `mail_betriebsziele()` hält diese Auswahl an **einer** Stelle. Drei Stellen
  bauten dieselbe Liste, und die dritte hatte bereits eine abweichende
  Sortierung — genau der Fall, für den R83 das Zentralisieren verlangt. Der
  Punkt stand **nicht** in der Zentralisierungsanalyse; er ist beim Umzug
  aufgefallen und in Nr. 202 nachgetragen.

> **Die E-Nummer ist die zweite.** Der erste Entwurf trug E-P5a-38 — die ist
> im Nachtrag des Auftraggebers für AP4a vergeben. Umgezogen auf 40;
> **E-P5a-34 bleibt unvergeben** (eine Lücke, kein Fehler).

**E-P5a-41 (neu, 16.09.2026) — die Warteschlange bekommt eine Zeile, keine
Seite.** E-P5a-14 verlangt eine „Unzustellbar-Liste" auf Betrieb → Status.
Eine Liste ist eine **neue Darstellung** und bräuchte nach `CLAUDE.md` 5 eine
Freigabe mit Mockup. Was eine BetreiberIn hier braucht, ist zudem keine Liste,
sondern eine Antwort auf eine Frage — *ist etwas liegengeblieben, und für
wen?* —, und die passt in eine `status_z()`-Zeile mit vorhandenem Baustein.

Drei Zustände, **zwei Töne für drei Fälle**: blau „leer", **orange** „N
wartet" (der Normalfall eines kurz gestörten Mailservers — er heilt von
selbst), **rot** „N unzustellbar" **mit Adresse** und letztem Grund. Rot ist
erst, was nicht mehr heilt.

Der volle Bereich mit Reitern kommt in **P5c** (Protokollierung). Bis dahin
ist diese Zeile die Auskunft, und sie ist vollständig: Was sie nicht zeigt,
zeigt auch keine Liste — die Adressen der *zugestellten* Nachrichten sind
gelöscht, und zwar mit Absicht.

**E-P5a-38 (neu, 16.09.2026) — zwei Sicherheitszeilen, ein eigenes Paket**
(Nachtrag des Auftraggebers; Backlog Nr. 203 und 205). Klein, eigene Version,
eigener Commit — „damit es nicht in AP5 untergeht", und das war richtig: Der
Umzug der zehn Versandstellen hätte die beiden Zeilen im Diff verschluckt.

- **`session.use_strict_mode`** vor jedem `session_start()`. Stand an zwei
  Stellen, und zwar den beiden **ohne** Anmeldesitzung. **Kein
  `sitzung_starten()`-Helfer** — der ist Schritt 15; hier nur die Zeile.
- **`json_kopf()` / `json_roh_out()`** in `db.php`. Sieben Stellen gaben JSON
  ohne den zentralen Kopfzeilensatz aus; zwei davon ohne `no-store`, und die
  liefern **GPS-Spurpunkte**.

**Zwei Abweichungen vom Auftrag, beide nach oben:**

1. Der Auftrag nannte **drei** Dateien für Nr. 203; es waren **sieben**.
   `auth_salt.php`, `jobs.php` und `pair.php` hatten denselben Mangel, und
   bei zweien wiegt er schwerer als beim Ausgangspunkt — `auth_salt.php`
   liefert das Salt der Schlüsselableitung je Konto und ist unangemeldet
   erreichbar, `pair.php` nennt die maskierte Adresse des Kontos. Sie
   auszulassen hieße, nach einer Durchsicht wissentlich eine Lücke
   stehenzulassen.
2. Der Auftrag verlangte für Nr. 205 die Zeile; dazugekommen ist
   **`tools/sitzungshaertung/`** in Stufe 1. Eine Zeile, die neben dem Aufruf
   steht, den sie schützt, ist genau die Art Zusage, die beim nächsten neuen
   Weg still wegfällt — und niemand merkt es, weil nichts passiert.

**Die Abnahme des Auftrags war zu optimistisch formuliert**, und das steht
hier, weil es sich wiederholen wird: „`grep -rn "Content-Type:
application/json" server/` zeigt nur `db.php`" — es zeigt **zwei**
Codezeilen. `wartung_lib.php` darf `db.php` nicht laden (die Wartungsseite
antwortet, während die Datenbank umgebaut wird) und setzt seinen Satz
weiterhin selbst, `no-store` eingeschlossen. Eine Abnahmezahl, die eine
benannte Ausnahme nicht kennt, macht aus ihr einen Befund.

**E-P5a-42 (neu, 16.09.2026, Auftraggeber) — in den mobilen Clients bleibt
`gen-em.org` stehen.** Vorgelegt im Anschluss an E-P5a-40 mit vier
Fundstellen (Uhr-`properties.xml` und -`settings.xml`, Android
`build.gradle.kts`, `Serveradresse.kt`) und der Rechnung dazu: je eine eigene
Auslieferung von Uhr und Android. Antwort: *„In den Apps passt es."*

**Der Unterschied zu E-P5a-40 ist nicht Bequemlichkeit, sondern die Sache.**
Im Server war die Adresse eine **Festverdrahtung** — eine fremde Betreiberin
konnte sie nicht ändern und verschickte Post, die auf einen Unbekannten
verweist. In den Clients ist sie eine **Vorgabe**: Die Uhr zeigt das Feld in
Garmin Connect, das APK nimmt `-Pnadoku.serverBasis=…`. Wer die Anwendung
aufsetzt, baut seine Apps ohnehin selbst und setzt die Vorgabe dabei.

Ausgeschrieben in `docs/Technik.md` 5d.8, weil das Konzept am Phasenende
gelöscht wird und die Entscheidung sonst mit ihm verschwände — und weil P2
dieselbe Frage schon einmal andersherum entschieden hatte (R29/R48). Ein
Fund, der zweimal gemacht und zweimal anders entschieden wurde, braucht seine
Begründung an einer bleibenden Stelle.

**E-P5a-43 (neu, 16.09.2026) — die Sperrleiter zählt ab 1, und ihre erste
Sprosse ist 15 Minuten.** Zwei Festlegungen, beide gegen den Wortlaut des
Konzepts, beide aus demselben Grund: Er ist an dieser Stelle nicht
widerspruchsfrei.

1. **Die Zählung.** E-P5a-04 sagt „eine Stufe 0–3", E-P5a-07 sagt „Sammelmail
   bei Stufe 4". Beides zusammen geht nicht auf. Gewählt ist die Lesart, die
   jemand ausspricht: **0 = nie gesperrt, 1–4 = die vier Sprossen.** „Stufe 4"
   heißt damit wörtlich die 60-Minuten-Sperre, und die Mail hängt an einer
   Zahl, die man nachzählen kann.
2. **Die erste Sprosse.** Das Konzept nennt 10/20/30/60. Der Topf `login`
   sperrt heute fest **900 s = 15 min**. Mit 10 wäre der **erste** Verstoß
   nach dem Update *milder* als davor — ein Sicherheitspaket, das eine
   Schranke senkt, ohne es zu sagen, ist genau die Art Fehler, die niemandem
   auffällt. Vorgabe deshalb **15/20/30/60**; die Leiter ist eine Einstellung,
   wer 10 will, trägt 10 ein.

**Nicht jeder Topf bekommt eine Leiter**, obwohl das Konzept `salt` und
`reset` „nach demselben Muster" nennt. `reset` bekommt **keine**: Er sperrt
heute 3600 s, jede Sprosse unterhalb der vierten wäre *schwächer*. Und sein
Scheitern ist absichtlich still — `reset_request.php` antwortet im gesperrten
Fall wortgleich wie im erlaubten; eine Leiter dort streckt ein Fenster, in dem
jemand fünfmal klickt, fünfmal dieselbe Zusage liest und keine Mail bekommt.
Die Kopplungstöpfe und die vier Mengenzähler ebenfalls nicht (Begründung je
Topf in `ratelimit_lib.php`).

**E-P5a-44 (neu, 16.09.2026) — zwei Schwellen brauchen zwei Töpfe.** E-P5a-04
verlangt 10 je Konto und 50 je Adresse. `RATE_GRENZEN` hängt aber am **Topf**,
nicht am Merkmal, und `rate_misserfolg()` bindet für alle Merkmale eines
Aufrufs dieselben Parameter — zwei Grenzen in einem Topf hieße, die Schleife
umzubauen und damit die Bauart aufzugeben, die in der Datei zweimal
ausgeschrieben begründet ist. Der hauseigene Weg ist ein **zweiter Topf**
(`login_ip`) mit ausdrücklicher Merkmalsliste; Muster ist `RATE_DEMO_GLOBAL`.

> **Eine Folge, die man leicht übersieht** und die im Betrieb erst nach Wochen
> auffiele: `login.php` muss bei einer gelungenen Anmeldung **beide** Töpfe
> leeren. Sonst läuft eine Praxis über den Tag in ihre 50 hinein, ohne dass
> irgendjemand etwas falsch gemacht hat.

**E-P5a-45 (neu, 16.09.2026) — drei Stellen, an denen der Konzepttext nicht
umsetzbar war.**

1. **Der Countdown steht nicht in `forms.js`.** Das Konzept nennt
   „login.php, forms.js". `login.php` **lädt forms.js gar nicht**, und jene
   Datei ist Änderungsverfolgung, Strg-Enter und Abbrechen-Rückfrage. Sie dort
   nachzutragen schaltete nebenbei eine `beforeunload`-Warnung auf einer Seite
   frei, auf der jemand ein Passwort tippt. Der Countdown steht im vorhandenen
   genonceten Block — zehn Zeilen rechtfertigen keine eigene Datei.
2. **Die Sammelmail geht an `mail_betriebsziele()`**, nicht an eine eigene
   Liste „alle Konten mit Rolle BetreiberIn". Seit E-P5a-40 ist das die *eine*
   Stelle, an der der Empfängerkreis der Betriebspost steht (R83); eine zweite
   Liste wäre der Rückfall in den Zustand, den R83 abgeschafft hat. Das
   Konzept ist hier älter als die Entscheidung.
3. **Ein gesetztes Passwort räumt die Anmeldesperre.** Das Konzept sagt „der
   Passwort-Reset (eigener Topf, per Mail) bleibt offen". Offen ist er nur,
   wenn er auch **hilft** — `pw_handling.php` rief bis dahin keine einzige
   `rate_*`-Funktion. Wer sein Passwort über den Link setzt, hat gerade
   nachgewiesen, dass ihm das Postfach gehört, und blieb trotzdem gesperrt:
   bisher 15 Minuten, mit der Leiter eine Stunde, und die Stufe stünde danach
   noch 24 h. Geräumt werden `login` und `salt` — **nur das Konto, nicht die
   Adresse**: Wer ein Postfach übernommen hat, soll damit nicht die Sperre
   einer ganzen Klinik aufheben.

**E-P5a-46 (neu, 16.09.2026) — `sicherheit_ereignisse` führt Merkmale im
Klartext, und das wandert in jede Komplettsicherung.**

Die Tabelle hält, was **war** — `rate_limits` verliert seinen Inhalt, sobald
die Sperre abläuft und der Aufräumjob die Zeile wegnimmt. Dieselbe Lücke, die
`job_laeufe` für die Hintergrundjobs geschlossen hat.

`merkmal` enthält IP-Adressen und — an der Anmeldung — E-Mail-Adressen. Ohne
sie wäre die Liste „irgendwo war irgendwer gesperrt" und damit wertlos;
dieselbe Abwägung wie bei der Unzustellbar-Liste (E-P5a-39).

**Die Folge wird benannt, nicht übergangen:** `komp_tabellen()` zählt seine
Tabellen über `SHOW FULL TABLES` und hat **keine Ausnahmeliste** — diese
Tabelle liegt damit in *jeder* Komplettsicherung, und die 30-Tage-Frist gilt
in der laufenden Datenbank, nicht im versiegelten Abzug. Wer das ändern will,
braucht eine Ausnahmeliste in `komplett_lib.php`, und die gehört nicht in ein
Paket, das den Ratenschutz baut.

**Ein Eintrag je Sperre, nicht je Fehlversuch.** `rate_misserfolg()` führt bei
einer Anmeldung vier Statements aus; ein Ereignis je Merkmal machte sechs und
schriebe jeden Tippfehler mit. Ein Protokoll, das jeden Tippfehler verbucht,
wird nicht gelesen.


**E-P5a-47 (neu, 16.09.2026) — das Existenzorakel der Mengenbremse: benannt,
verkleinert, nicht geschlossen.**

E-P5a-01 (3) legt fest: Merkmal ist die Gerätekennung, die IP **nur** für
unbekannte Kennungen. Genau daraus entsteht eine Auskunft, die `ingest.php`
an anderer Stelle mit Aufwand vermeidet: M4-07 hat den Zeitunterschied
zwischen bekannter und unbekannter Kennung beseitigt (Blindvergleich gegen
`GERAET_VERGLEICHSWERT`), weil er ohne jede Zugangsdaten messbar war. Zwei
Töpfe mit **verschiedenen Schwellen** stellen ihn als Zählunterschied wieder
her: Wer dieselbe geratene Kennung von einer Adresse aus hämmert, bekommt bei
existierender Kennung ab dem 31. Versuch `429`, bei nicht existierender erst
ab dem 51.

**Was getan wird:** Beide Töpfe bekommen **dieselbe Zahl — 30 je 15 min**.
Damit endet der einfache Sonderfall: Ein Hämmern auf *eine* Kennung
unterscheidet die beiden Fälle nicht mehr, die Antwort kommt beide Male beim
31. Versuch und mit demselben Rumpf (`zu_viele_versuche`, nie ein Hinweis,
welcher Topf gegriffen hat). Die Absenkung von 50 auf 30 kostet **keinen
legitimen Verkehr**: In den Adresstopf zählen ausschließlich *unbekannte*
Kennungen, und ein gekoppeltes Gerät sendet nie eine unbekannte.

**Was bleibt, und das wird nicht beschönigt:** Eine zweistufige Probe
unterscheidet weiterhin — 31 Versuche mit der fraglichen Kennung, danach
einer mit einer offensichtlich erfundenen. Kommt darauf `401`, war die erste
bekannt; kommt `429`, war sie es nicht. Das zu schließen hieße, auch
Fehlversuche **bekannter** Kennungen in den Adresstopf zu zählen — und dann
sperrt ein einziges Gerät mit veraltetem Schlüssel seine ganze Adresse,
einschließlich des soeben neu gekoppelten Geräts, das die Abhilfe ist. Die
Heilung wäre schlimmer als der Schaden.

**Warum der Rest hinnehmbar ist:** Die Kennung ist `dev-` + 16 Zufallsbytes
(128 Bit) und ausdrücklich **kein Geheimnis** — `pair.php` schreibt das so
hin, die Berechtigung hängt am Schlüssel (192 Bit). Das Orakel beantwortet
also nur die Frage „ist diese Kennung, die ich ohnehin schon habe, noch
eingetragen?" Raten kann man sie nicht.

**E-P5a-48 (neu, 16.09.2026) — `ingest` und `ingest_ip` bekommen eine Leiter,
und die Trennlinie im Kopfkommentar wird umgeschrieben.**

`ratelimit_lib.php` begründete bisher, die Kopplungstöpfe bekämen keine
Leiter, weil „dahinter ein Gerät steht, das nicht lesen kann, was auf der
Seite steht". Auf `ingest.php` trifft das wörtlich genauso zu — die Regel
hätte also gegen die Leiter entschieden. Sie war falsch formuliert.

**Die tragfähige Trennlinie ist eine andere:** Bei den Kopplungstöpfen
unterbricht eine längere Sperre einen Vorgang, der **gerade läuft** — jemand
steht am Gerät mit einem sechsstelligen Code, der in zehn Minuten verfällt.
Eine Stunde Sperre schreckt dort keinen Automaten ab, sie beendet die
Kopplung für den Menschen. Bei `ingest.php` läuft nichts: Die Daten liegen in
der Warteschlange des Geräts (1.7) und kommen später an. Die Sperre kostet den
legitimen Fall **nichts als Zeit** — und Zeit ist genau das, was sie den
illegitimen kosten soll. Deshalb: Leiter ja.

**Und sie sperrt kein repariertes Gerät aus:** Die Abhilfe bei veraltetem
Schlüssel ist Neukopplung, und die erzeugt eine **neue** Kennung
(`pair.php`); der alte Topf bleibt zurück und läuft ab.

**E-P5a-49 (neu, 16.09.2026) — zwei Abnahmezeilen aus AP7 waren so nicht
erfüllbar.**

1. „30 → Sperre **10 min**" (auch im Text von E-P5a-02). Die erste Sprosse
   ist seit E-P5a-43 **15 Minuten**, und zwar mit Begründung: Mit 10 wäre der
   erste Verstoß nach dem Update milder als davor. Die Abnahme liest sich
   jetzt „Sperre 15 min (erste Sprosse)".
2. „**Messstand-Zahlen für `ingest.php`** unverändert (± 5 %)". Der Messstand
   misst keine Ingest-Zahl — er hat nie eine erhoben. An ihre Stelle tritt die
   Zahl, die es wirklich gibt: `dauer_ms` aus der `lauf.json` des
   Referenzlaufs, vorher gegen nachher.

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

- Topf `ingest` (30/900 s, Leiter) je Gerätekennung, Topf `ingest_ip`
  je IP für unbekannte Kennungen — **ebenfalls 30/900 s, nicht 50**
  (E-P5a-47); Leiter für beide (E-P5a-48); `429` mit `Retry-After`;
  Vermerk am Gerät (`devices.abgewiesen_seit`, `abgewiesen_anzahl` —
  Migration) für Kontoseite und Status; Begründung der (nun beendeten)
  Asymmetrie in `docs/Technik.md`.
- **Abnahme:** Referenzlauf (`tools/referenzdatensatz/einspielen/`)
  unverändert 0 Fehlversuche; Ingestprobe mit veraltetem Schlüssel: 14
  Fehlversuche in einem Stoß **ohne** Sperre, 30 → Sperre **15 min**
  (erste Sprosse, E-P5a-43/-49), Uhr-Simulator sendet danach den Rückstand
  vollständig (Punktzahl vorher = nachher); `dauer_ms` des Referenzlaufs
  vorher gegen nachher (± 5 %, E-P5a-49 — der Messstand erhebt für
  `ingest.php` keine Zahl).

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
| Ingestprobe, Referenzlauf | AP7 | 0 Fehlversuche Referenz; 14 ohne Sperre, 30 mit (15 min); `dauer_ms` ± 5 % |
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
| F6 | **Der Meldeweg der CSP schwieg — und sah dabei aus wie Erfolg.** Die Richtlinie trug `report-to csp`; die Gruppe `csp` war nie auflösbar definiert (die `Reporting-Endpoints`-Zeile enthielt eine *relative* Adresse). Chromium **bevorzugt `report-to` und verwirft den Bericht dann ersatzlos** — der erste Bilderlauf meldete „0 CSP-Berichte“ bei zwei Verstößen, die in der Konsole standen | `kopfzeilen_lib.php` | **In AP4 behoben:** `report-to` steht nur noch da, wenn `kopf_melde_url()` eine vollständige HTTPS-Adresse liefert; sonst trägt `report-uri` allein. Gefunden hat es die Gegenprobe in `tools/cspprobe/browserprobe.mjs`, die einen Verstoß **absichtlich** auslöst |
| F7 | **Der Nonce machte die Integritätswache rot — täglich und grundlos.** Ihr Muster las `<script…[^>]*>`; in der Quelle steht `<script<?= kopf_nonce_attr() ?>>`, und das `[^>]*>` endet am `?>`. Der Block begann danach mit einem überzähligen `>` | `tools/integritaetswache/wache.py`, `tools/wartungsprobe/probe.php` | **In AP4 behoben** (Fund 23): Tag-Muster, das PHP kennt. Gefunden hat es Erwartung 12a der Wartungsprobe, die seit S10 genau dafür da ist |

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

#### Nachtrag 16.09.2026 — ein Riegel vor die drei FTP-Geheimnisse

Beim Anlegen der Umgebung `staging` fiel eine Lücke in AP1 auf, die kein
Prüfmittel hätte finden können, weil sie erst beim ersten echten Lauf
zuschlägt: **GitHub setzt ein Geheimnis, das es nicht gibt, auf leer und
bricht nicht ab.** Die FTPS-Aktion wäre mit leerem Benutzernamen losgelaufen
und erst an der Gegenstelle gescheitert — mit einer Meldung über die
*Anmeldung*, nicht über den *fehlenden Eintrag*. Wer den Namen eines
Geheimnisses vertippt, sucht den Fehler dann beim Hoster.

Beide Deploy-Jobs tragen deshalb jetzt einen Riegel, der (a) die drei Werte
auf Vorhandensein prüft und (b) nachsieht, ob der Host wirklich ein Hostname
ist und nicht `ftps://…/staging` oder `ftp.example.de:21`. Im
**Produktions-Job steht er ganz oben**, vor dem Backup-Tor: weiter unten
hätte der Lauf schon die Wartung eingeschaltet, und ein vertippter Name ließe
die Anwendung zu.

**Zwei Fallen dabei, beide gemessen statt vermutet.** Erstens ist
`[ -z "$X" ] && y=1` unter `set -e` — und GitHub setzt es — als *letzte* Zeile
eines Skripts ein Abbruch mit Code 1, sobald die Bedingung **nicht** zutrifft,
also genau dann, wenn alles in Ordnung ist. Der Riegel benutzt deshalb
ausgeschriebene `if`-Blöcke. Zweitens ist der Wert ein **Hostname**; ein
Geheimnis namens `…_FTP_URL` lädt dazu ein, eine Adresse einzutragen.
Nachgewiesen an **zehn Fällen** (je Job: alles gesetzt, einer fehlt, alle drei
fehlen, Host mit Schema, Host mit Port) gegen `bash -e`.

**Die Namen bleiben generisch** (`FTP_SERVER`, `FTP_USERNAME`,
`FTP_PASSWORD`) und stehen in **beiden** Umgebungen gleich. Zwischendurch
waren sie auf `NADOKU_STAGING_*` / `NADOKU_PRODUKTION_*` umgestellt und sind
zurückgedreht worden: Die Umgebung soll entscheiden, welcher Wert ankommt —
dann kann ein kopierter Job die Zugangsdaten der falschen Seite gar nicht
erwischen, weil es die anderen dort nicht gibt.

**Keine Versionserhöhung.** Die Änderung fasst `.github/` und `docs/` an und
liefert an keine der drei Zählungen etwas aus. Muster: `e9d59c4` vom
16.09.2026, ebenfalls eine Korrektur an der Kette ohne Version.

**Ein zweiter Riegel vor Stufe 2, aus demselben Anlass.** Die Geheimnisse
`STAGING_KONTO`/`STAGING_PASS` werden eingetragen, **bevor** die Anlage steht
— und damit greift der „ÜBERSPRUNGEN"-Zweig der beiden Prüfschritte nicht
mehr. Die Kreisläufe liefen dann gegen eine Adresse, die noch nichts
ausliefert, und scheiterten mit einem Rückverfolg über eine fehlende
Anmeldeseite. Ein Griff auf `login.php` unterscheidet jetzt vier Lagen und
nennt zu jeder den Schritt aus Rahmenplan 6a, der fehlt.

**Er fragt `login.php` und nicht `/`, und das ist gemessen, nicht überlegt:**
`staging.nadoku.gen-em.org` lieferte am 16.09.2026 eine **Plesk-„Domain
Default page" mit HTTP 200** aus. Ein Griff auf `/` hätte eine leere Subdomain
für eine laufende Anwendung gehalten. Derselbe Griff fängt außerdem den
teuersten Einrichtungsfehler mit — einen falschen `FTP_ZIELPFAD`: Liegen die
Dateien im falschen Verzeichnis, gibt es dort keine `login.php`.

Nachgewiesen an **fünf Lagen** gegen die laufende Entwicklungsinstallation:
URL leer (übersprungen, Exit 0) · Plesk-Standardseite (404, Exit 1, nennt
`FTP_ZIELPFAD`) · fremder Host (Exit 1) · `config.php` beiseite geschoben, also
noch nicht eingerichtet (Weiterleitung auf `install.php`, Exit 1, nennt die
Schritte 6–8) · laufende Anwendung (Exit 0). Die vierte Lage ist mit einer
echten, wieder zurückgelegten `config.php` hergestellt worden — nicht
nachgestellt.

Der Einrichtungsweg steht jetzt als abhakbare Liste in **Rahmenplan
Abschnitt 6a** — angelegt, weil in der Umsetzung die Annahme aufkam, die Kette
richte die Instanz selbst ein. Sie tut es nicht: Sie überträgt `server/` in ein
Verzeichnis, das es schon geben muss, und legt `config.php` ausdrücklich
**nicht** an.

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

### AP3 — Torwächter und Wartung · Web 20.6.0 · 15.09.2026

**Entstanden.** `migrationen_ausstehend()` samt Hash-Zwischenspeicher und
`migrationen_tor_zuruecksetzen()` in `migration_lib.php`; der Aufruf in
`auth_guard.php`; der Grund auf der Wartungsseite und im Balken
(`wartung_lib.php`); Meldung und zweiter „Wartung beenden"-Knopf auf
Betrieb → Updates; das Zurücksetzen in `wiederherstellen.php` (Nr. 54);
**Teil 7 der Wartungsprobe** (10 Erwartungen); `docs/Technik.md` 4.99c und
Runbook, Handbuch 12.4, Changelog.

**Problem 1 — `serialize(migrationen_katalog())` geht nicht.** Das Konzept
schreibt „gecacht in `app_state` unter dem Hash des Katalogs". Der Katalog
enthält Closures (`skip`, `run`), und Closures lassen sich nicht
serialisieren — der Aufruf hätte eine Ausnahme geworfen, und zwar bei **jeder
angemeldeten Anfrage**. **Gelöst** über die Kennungen:
`sha256(implode("\n", array_column($katalog, 'id')))`. Das beantwortet die
Frage ohnehin genauer — was ein Deploy hinzufügt, sind Kennungen.

**Problem 2 — der Zwischenspeicher überlebt einen ausgeführten Lauf.** Nach
`migrationen_lauf(…, true)` ändert sich der Hash **nicht** (es kommt ja keine
Migration hinzu, es wird eine ausgeführt). Ohne Fortschreibung schlösse der
Torwächter die Installation unmittelbar wieder zu — die Betreiberin klickte
„Ausstehende ausführen" und säße danach vor derselben Wartungsseite.
**Gelöst** durch eine Zeile am Ende von `migrationen_lauf()`, und zwar nur im
Ausführungszweig: Schriebe auch die Vorschau ihn, gäbe es zwei Schreibwege
für dieselbe Zeile, und einer davon (die Statusseite, die nur vorschaut) wäre
in einer Rolle, die er nicht hat.

**Problem 3 — die Anfrage, die schaltet, bekäme ihre Seite noch.**
`wartung_tor()` läuft in `db.php`, also **bevor** der Torwächter die Datei
anlegt. Ohne einen zweiten Aufruf lieferte genau die Anfrage, die den
Wartungsmodus auslöst, ihre Seite noch aus — aus einer Anwendung, die sich
gerade für geschlossen erklärt hat. **Gelöst** durch `wartung_tor()` direkt
hinter `wartung_einschalten()`; für die Ausnahmeseiten kehrt der Aufruf sofort
zurück.

**Entscheidung E-P5a-29 (neu) — der Knopf steht auf Betrieb → Updates, nicht
in `update.php`.** Das Konzept schreibt in E-P5a-20 „bietet `update.php` den
Knopf „Wartung beenden" direkt an". `update.php` ist im Web seit S8/AP3 aber
**nur noch eine Weiterleitung** (302 auf `betrieb_updates.php`, Backlog
Nr. 77); ein Knopf dort wäre unerreichbar. Abschnitt 2.3 desselben Konzepts
nennt als Ort ausdrücklich „Betrieb → **Updates**" — dort steht er, und die
Tabelle löst den Widerspruch in ihrem eigenen Sinn auf.

**Entscheidung E-P5a-30 (neu) — das offene Fenster wird benannt, nicht
geschlossen.** `ingest.php` und `pair.php` laden `auth_guard.php` nicht; bis
zur ersten angemeldeten Anfrage bekommen die Geräte weiter 500 statt 503. Die
Prüfung dorthin zu ziehen hieße, sie in `db.php` zu stellen — und damit
`wartung_tor()` genau die Eigenschaft zu nehmen, um derentwillen es dort
steht (es antwortet **ohne** Datenbank). Verloren geht nichts: 5xx ist 5xx,
beide Clients puffern und liefern nach. Für die Auslieferungskette ist das
Fenster ohnehin null. Der Satz steht im Code, in `Technik.md` 4.99c, im
Handbuch und im Changelog — an allen vier Stellen, an denen jemand ihn
suchen würde.

**Was die Prüfung besonders macht:** Teil 7 misst Nr. 54 in der Richtung, die
weh tut. Erwartung 32 verlangt, dass der Zwischenspeicher nach einer
nachgestellten Wiederherstellung **lügt** — sonst hätte niemand gemerkt, dass
`wiederherstellen.php` ihn zurücksetzen muss. Erwartung 33 verlangt, dass das
Zurücksetzen hilft. Eine Prüfung, die nur das Richtige bestätigt, hätte diese
Lücke nie gefunden.


### AP4 — Kopfzeilen und HTTPS · Web 20.7.0 · 15.09.2026

**Entstanden.** `kopfzeilen_lib.php` (CSP mit Nonce je Anfrage, HSTS als
Einstellung, `https_tor()`, die vier übrigen Kopfzeilen) und `netz_lib.php`
(Client-Adresse hinter vertrauenswürdigen Proxys); die Aufrufe in
`ui_seite_start()` und `json_out()`; `api/csp_bericht.php` samt Tabelle
`csp_berichte`, Migration `2026_09_15_csp_berichte`, Ratentopf `csp` und
Aufräumschritt; `app_state_lesen()`/`app_state_setzen()` in `db.php`; die
Karte „Sicherheitskopfzeilen" auf Betrieb → Servereinstellungen; 23
Inline-Skripte auf `kopf_nonce_attr()` umgestellt; drei statische `style=`
aufgelöst; zwölf API-Dateien auf `csrf_check()` (Nr. 67) und `csrf_ok()` auf
`X-CSRF`; der `netz`-Block in `config.example.php`; `.htaccess` umgebaut;
**`tools/cspprobe/`** neu; `docs/Technik.md` 5c samt Runbook und
Verzeichnisstruktur, Handbuch 12.5, Changelog, Backlog Nr. 8 und Nr. 181 nach
*Erledigt*.

**Problem 1 — `.htaccess` hätte die neue Einstellung überschrieben.** Das
Konzept sagt „`.htaccess`-Zusatz" und behandelt die Datei als Ergänzung. Sie
ist das Gegenteil: `Header always set` **überschreibt** auf Apache, was PHP
schickt. Die Einstellung `hsts_tage` hätte dort nichts bewirkt — die
Oberfläche hätte „1 Tag" angezeigt und der Server ein Jahr geschickt. Zwei
Wahrheiten über dieselbe Kopfzeile sind schlimmer als eine. **Gelöst** in
E-P5a-31.

**Problem 2 — dreizehn `style=`, nicht eines.** Das Konzept erbt aus der
Krypto-Bestandsaufnahme vom 06.09.2026 die Zahl „**ein** `style`-Attribut"
(Backlog Nr. 8) und plant „Auflösung der 3 `style=`". Gemessen am 15.09.2026
waren es **dreizehn**: drei statische in PHP und **zehn zur Laufzeit in
JavaScript**. Die zehn sind nicht dieselbe Art Arbeit wie die drei. **Gelöst**
in E-P5a-32.

**Problem 3 — die erste Fassung der CSP-Probe meldete null von 108.** Sie
sammelte die `T_INLINE_HTML`-Stücke einzeln ein und suchte in jedem nach
einem vollständigen `<script…>`-Tag. Die Schreibweise dieses Projekts ist
aber `<script src="<?= asset('assets/html.js') ?>"></script>`, und die
zerfällt in drei Stücke, von denen keines ein Tag ist. Der Lauf meldete „0
Skript-Stellen, 0 Befunde" — und sah genau aus wie ein sauberer Lauf.
**Gelöst** durch ein **Markup-Bild** je Datei: zeichengenau so lang wie die
Quelle (damit Zeilennummern stimmen), Markup und Zeichenketten verbatim,
Kommentare geleert, `<?php`/`<?=`/`?>` zu Leerzeichen, und im übrigen
PHP-Code `<` und `>` zu `_`, damit ein `=>` kein Tag vorzeitig schließt. Der
Fehler steht jetzt im Kopf der Probe und in ihrer `LIESMICH.md` — er ist das
Lehrstück zu CLAUDE.md 6 („eine grüne Zahl ist erst dann ein Beleg, wenn sie
das Gemessene benennt").

**Problem 5 — der Meldeweg schwieg, und die Null sah aus wie ein Erfolg
(F6).** Der erste Bilderlauf über 49 Seiten und 392 Bilder meldete **0
CSP-Berichte**. Das war das Abnahmekriterium — und es war wertlos: Die
Gegenprobe der Browserprobe löste zwei Verstöße **absichtlich** aus, sah sie
in der Konsole stehen und fand die Tabelle trotzdem leer. Ursache: Die
Richtlinie trug `report-to csp`, die Gruppe `csp` war aber nie auflösbar
definiert (`Reporting-Endpoints` enthielt eine *relative* Adresse, und die
nimmt der Browser dort nicht an). **Chromium bevorzugt `report-to` gegenüber
`report-uri` und verwirft den Bericht dann ersatzlos** — gemessen: ohne die
Zeile `report-to csp` kam derselbe Verstoß sofort als Zeile in
`csp_berichte`. **Gelöst** durch `kopf_melde_url()`: `report-to` und
`Reporting-Endpoints` erscheinen nur, wenn eine vollständige HTTPS-Adresse
gebaut werden kann (aus der laufenden Anfrage, nicht aus `app.base_url` —
ein abweichender Name wäre fremder Herkunft); sonst trägt `report-uri`
allein. Danach: 4 provozierte Verstöße, 4 Zeilen, richtig zusammengefasst
(`inline` mit Zähler 2).

Der eigentliche Fund ist nicht der Tippfehler, sondern **dass die Prüfung ihn
fast nicht gefunden hätte**. Die zweiwöchige Report-Only-Phase wäre eine
Wartezeit ohne Erkenntnis geblieben, und der leere Kasten auf der
Servereinstellungsseite hätte wie ein gutes Zeichen ausgesehen — bis jemand
scharf schaltet und Seiten still brechen. Seither steht die Gegenprobe als
fester Punkt in `browserprobe.mjs`.

**Problem 6 — `Technik.md` behauptete eine CSP auf JSON-Antworten.** Der
erste Entwurf von 5c.1 schrieb „dieselben ohne Nonce“. `kopfzeilen_json()`
setzt aber bewusst **keine** CSP — so steht es in E-P5a-15 („kein CSP
nötig“), und so ist es richtig: Eine JSON-Antwort ist kein Dokument, der
Browser führt darin nichts aus; die tragende Zeile ist `nosniff`. Gefunden
von der Browserprobe, die zuerst das Falsche erwartete. **Gelöst** in der
Dokumentation, nicht im Code — und die Erwartung steht jetzt umgekehrt in
der Probe, damit ein späteres versehentliches Hinzufügen auffällt.

**Problem 7 — `img-src data:` war gestrichen, und das war falsch.** SP-5
führt `data:` mit. Eine Zählung im eigenen Quelltext (`server/`,
`assets/style.css`) ergab **0 Treffer**, also wurde es gestrichen — mit dem
Satz „der Report-Only-Lauf sagt, wenn das ein Irrtum war". **Er hat es
gesagt:** Der zweite Bilderlauf, der erste mit funktionierendem Meldeweg,
brachte **140 Verstöße** auf genau vier Seiten — `index.php` 65,
`zeitraum.php` 31, `einsatz.php` 28, `tag_spuren.php` 16 —, alle mit der
Quelle `data`. Das sind die vier Kartenseiten.

**Ursache:** `leaflet.js` trägt eine eingebaute Konstante, ein 1×1 Pixel
großes durchsichtiges GIF als `data:image/gif;base64,…`
(`L.Util.emptyImageUrl`); Leaflet setzt sie als `src`, wenn es eine Kachel
wegräumt. Sie steht in einer **minifizierten Bibliothek** und nicht in
unserem Quelltext — und genau deshalb hat die Zählung sie nicht gesehen.
**Gelöst** durch `data:` in `img-src`; der Preis steht ausgesprochen im Kopf
von `kopfzeilen_lib.php`, in `Technik.md` 5c.2 und im Changelog: Es ist die
schwächste Zeile der Richtlinie, sie bleibt, weil die Alternative das Patchen
einer vendorierten Bibliothek wäre und weil ein Bild kein Skript ausführt.

Zusammen mit Problem 5 ist das der eigentliche Ertrag dieses Pakets: **Zwei
Fehler, die beide nur ein laufender Browser finden konnte** — und der zweite
war nur zu finden, weil der erste behoben war. Hätte der Meldeweg
geschwiegen, wäre die Installation mit einer Richtlinie in Betrieb gegangen,
die nach dem Scharfschalten alle vier Kartenseiten beschädigt hätte, und die
zwei Wochen Report-Only hätten dazu **null** gesagt.

**Problem 8 — der Nonce machte die Integritätswache rot. Jeden Tag.** Die
Wache vergleicht täglich den Inline-Block der Anmeldeseite mit dem
Repositorium (Backlog Nr. 140, SP-6). Ihr Muster liest das Tag als
`<script…[^>]*>` — und in der **Quelle** steht seit diesem Paket

    <script<?= kopf_nonce_attr() ?>>

Das `[^>]*>` endet am `>` des PHP-Schlusses, und der Block begann danach mit
einem überzähligen `>`. In der **Auslieferung** steht `<script nonce="…">`,
also ohne dieses Zeichen. Prüfsumme verschieden, Wache rot — bei jedem Lauf,
aus einem vollkommen harmlosen Grund. Gemessen: „login.php: ein Inline-Block
der Quelle steht nicht so in der Auslieferung".

**Der Schaden wäre nicht die rote Zeile gewesen, sondern ihre Folge.** Der
Kopf der Wartungsprobe sagt es selbst: „Eine Wache, die regelmäßig aus einem
harmlosen Grund rot wird, ist nach dem dritten Mal abgeschaltet." Danach fällt
eine echte Manipulation nicht mehr auf.

**Gelöst** durch ein Tag-Muster, das PHP kennt:
`(?:<\?(?:php\b|=).*?\?>|[^>])*` — erst ein PHP-Stück am Stück, sonst ein
einzelnes Zeichen, das kein `>` ist. Dieselbe Änderung in
`tools/integritaetswache/wache.py` (Fund 23) und in Erwartung 12a der
Wartungsprobe, die genau dies nachhält. Danach: Selbstprobe **30 von 30**,
Lauf gegen die lokale Installation **122 Dateien gleich, 1 Inline-Block
gleich, kein Unterschied**; Wartungsprobe **67 Erwartungen, 0 nicht erfüllt**.

**Warum es überhaupt auffiel:** weil die Wartungsprobe eine Erwartung dafür
hat. Sie steht dort seit S10 mit der Begründung, die oben zitiert ist — jemand
hat vorausgedacht, und drei Pakete später hat es sich ausgezahlt.

**Problem 4 — `vendor/` verfälschte die Stilzahl.** Die Probe zählte vier
`style="` in PHP und meldete sie als Befund-Nachbarn; alle vier standen in
`vendor/phpseclib3/File/ANSI.php`, das ein Terminal malt und von dieser
Anwendung nie aufgerufen wird. **Gelöst** durch Ausschluss von `vendor/` —
eine Zahl, die beim nächsten Bibliotheks-Update grundlos springt, sagt nichts
über die Oberfläche. Die Laufzeitstellen zählt die Probe seither dort, wo sie
stehen: in `assets/*.js` (**10**).

**Entscheidung E-P5a-31 (neu) — HSTS hat genau eine Quelle, und das ist
PHP.** `.htaccess` verliert die HSTS-Zeile ganz; die drei übrigen Kopfzeilen
bleiben dort, aber als **`setifempty`** statt `set`. So führt PHP, wo PHP
läuft, und `.htaccess` deckt weiterhin die statischen Dateien, die nie durch
PHP gehen. **Der Preis wird benannt, nicht verschwiegen:** Auf einer
bestehenden Installation fällt die Bindung von einem Jahr auf einen Tag
(Vorgabe), bis jemand sie wieder hochstellt. Das ist der richtige Weg herum —
eine zu kurze Bindung kostet einen Klick, eine zu lange kostet ein Jahr. Der
Satz steht im Runbook, im Handbuch 12.5 und im Changelog.

**Entscheidung E-P5a-32 (neu) — `style-src 'self'` plus
`style-src-attr 'unsafe-inline'`.** Die drei statischen Stellen sind
aufgelöst (`betrieb_server.php` über `data-breite` und ein genonctes Skript,
`index.php` über `.style.background` nach dem `innerHTML`). Die zehn
Laufzeitstellen bleiben. Drei Gründe, und der dritte ist der eigentliche:

1. **Der Umbau wäre unverhältnismäßig.** Leaflet-divIcons (`geo.js`) bekommen
   ihr Markup als Zeichenkette; jeder Pfeil einer Spur ist ein eigenes Icon.
   Ein Konto mit 600 Einsätzen zeigt Tausende davon.
2. **Es änderte an der Angriffsfläche nichts.** `el.style.transform = …` ist
   CSSOM und wird von CSP **nicht** erfasst. Wer die Attribute in
   JavaScript-Zuweisungen umschreibt, hat dieselbe Fähigkeit mit anderer
   Schreibweise.
3. **`style-src-attr` ist die schmalere Ausnahme, nicht die bequemere.**
   `style-src 'self'` bleibt scharf: Ein eingeschleuster `<style>`-Block und
   ein fremdes Stylesheet werden weiterhin blockiert. Erlaubt ist nur das
   Attribut — und was ein Stilattribut anrichten kann, begrenzen
   `default-src 'none'` und das enge `img-src`.

Nachgehalten wird die Zahl: `tools/cspprobe/` meldet sie bei jedem Lauf, und
ein Wachsen fällt auf.

**Entscheidung E-P5a-33 (neu) — `connect-src` liest den Geocoder zur
Laufzeit.** SP-5 nennt `https://photon.komoot.io` als feste Zeichenkette.
Die Anschrift ist seit S9/AP2 aber eine Einstellung je Installation
(`geocoder_dienst()`), und genau darauf weist Backlog Nr. 181 hin. Die
Richtlinie liest sie deshalb bei jeder Anfrage — und **lässt sie weg**, wenn
die Adresssuche ausgeschaltet ist. Wer einen eigenen Photon-Dienst einträgt,
braucht keine Code-Änderung; wer die Suche abschaltet, hat auch keine
`connect-src`-Ausnahme mehr stehen.

**Was die Prüfung besonders macht:** Die CSP-Probe prüft **vier Fälle, die
nicht anschlagen dürfen** — darunter einen Kommentar, der `<script>` nennt,
und `data-onload="1" name="onlineform"`. Beide hätten ein `grep` zum Fehlalarm
gebracht, und eine Prüfung mit Fehlalarm wird nach dem zweiten Lauf
abgeschaltet.

---

### AP4a, AP5, AP6 — wo ihr Protokoll steht

Diese drei Pakete haben **keinen eigenen Abschnitt hier**, und das ist ein
Bruch in diesem Dokument, kein Versehen der Ablage: Ihre Zahlen stehen im
**Statusblock** (Tabelle „Stand der Umsetzung"), ihre Entscheidungen als
**E-P5a-35 bis -46** in Abschnitt 2, ihre Fehlerfunde in Abschnitt 5 und die
ausführliche Begründung jeweils im Kopfkommentar der geänderten Datei
(`mail_lib.php`, `ratelimit_lib.php`) und in `docs/CHANGELOG.md`. Wer das
Protokoll dieser drei Pakete sucht, findet es dort vollständig — aber an vier
Stellen statt an einer.

### AP7 — Mengenbremse `ingest.php` · Web 20.11.0 · 16.09.2026

**Was entstanden ist.** Zwei Töpfe (`ingest` je Gerätekennung, `ingest_ip` je
Adresse), je 30 Fehlversuche pro 15 Minuten, mit der Sperrleiter aus AP6;
`429` mit `Retry-After`; der Vermerk am Gerät (`devices.abgewiesen_seit`,
`abgewiesen_anzahl`, Migration `2026_09_16_geraet_abgewiesen`) auf Kontoseite
und Betrieb → Status. Dazu `rate_sperre_paare()`, `rate_merkmal_ip()`,
`rate_merkmal_kennung()` und ein `$merkmale`-Parameter an `rate_erfolg()`.

#### Drei Fragen, die vor der ersten Codezeile zu klären waren

Eine Vorprüfung des Pakets hat drei Widersprüche gefunden, die im Code nicht
mehr lösbar gewesen wären. Sie sind als **E-P5a-47, -48 und -49** entschieden
und in Abschnitt 2.2 ausgeschrieben:

1. **Das Existenzorakel** (E-P5a-47). Die im Konzept vorgesehenen
   verschiedenen Schwellen (30 je Kennung, 50 je Adresse) hätten genau die
   Auskunft wiederhergestellt, die M4-07 in `ingest.php` mit einem
   Blindvergleich beseitigt hat. **Gelöst:** 30 gegen 30 — die einfache Probe
   unterscheidet nicht mehr. **Nicht gelöst und benannt:** die zweistufige
   Probe; die Heilung wäre schlimmer als der Schaden.
2. **Die Leiter** (E-P5a-48). Die Begründung in `ratelimit_lib.php`, warum die
   Kopplungstöpfe keine bekommen, hätte auch gegen die Leiter für `ingest`
   entschieden. **Gelöst:** Die Trennlinie war falsch formuliert und ist
   umgeschrieben — sie fragt jetzt, ob die längere Sperre einen Vorgang
   unterbricht, der *gerade läuft*.
3. **Zwei Abnahmezeilen** (E-P5a-49): „Sperre 10 min" (die erste Sprosse ist
   seit E-P5a-43 fünfzehn) und „Messstand-Zahlen für `ingest.php`" (der
   Messstand erhebt keine).

#### Probleme, die in der Umsetzung aufgetreten sind

- **Der Rollback verschluckte die Ursache.** `$pdo->rollBack()` lief im
  Fehlerzweig unbedingt, obwohl `commit()` mitten im `try` steht. Behoben mit
  `if ($pdo->inTransaction())`; Begründung im Code und im Changelog. Gefunden
  beim Lesen, nicht von einem Prüfmittel.
- **Die Messgrundlage der 30 war falsch zitiert.** „Spitze 14 Anfragen an
  einem Auslöser" steht so seit P1 im Backlog — `messprotokoll.json` führt
  unter `spitze_je_dienst` die **3**; die 14 ist `teilstuecke_je_paket.max`.
  Die **Schlussfolgerung** (30, nicht 10) bleibt, die Begründung ist
  berichtigt — in `ratelimit_lib.php`, in `docs/Technik.md` 5e.7 und im
  Backlog-Eintrag selbst.
- **Fünf Unicode-Zeichen in eigenen Kommentaren** hätten die
  Vollständigkeitszahl von 372 auf 377 getrieben. Sie sind durch Worte
  ersetzt; die Prüfung „Unicode-Zeichen als Symbol im Markup" meint Markup,
  nicht Kommentare, und eine Zahl, die an Rauschen wächst, verliert ihren
  Sinn. Gemessen wurde gegen einen **ausgecheckten** Stand
  (`git worktree add --detach HEAD`), nicht gegen die Arbeitskopie.
- **Die Wortliste fand zwei eigene Sätze** („Rückruf von Connect IQ"). Sie
  bleiben mit einer begründeten Ausnahme (Klasse G): Die Signatur
  `(code, data)` ohne Kopfzeilen ist eine Eigenschaft der Garmin-Plattform und
  nicht der Uhr im Allgemeinen — ein Wear-OS-Client könnte `Retry-After`
  lesen.

#### Was der vollständige Referenzlauf nicht hergab

Die Abnahme nennt den Einspiellauf (`tools/referenzdatensatz/einspielen/`).
Der Generator lief (21 Dienste, 612 Ingest-Anfragen, 64 478 Punkte), die Stufe
`geraet` brach ab: Sie koppelt über die **Weboberfläche** und braucht dafür
eine angemeldete Sitzung des Demo-Kontos, deren Einladungslink in diesem
Container nicht mehr zu haben war (`Konto demo@gen-em.org besteht bereits`).

**Gemessen wurde stattdessen der erzeugte Sendeplan selbst** — dieselben 612
Anfragen, dieselben Körper, über echtes HTTP, mit per SQL angelegten Geräten.
Das prüft `ingest.php`, nicht die Geräteverwaltung, und ist für die Frage
dieses Pakets die richtige Messung. **Was dabei nicht geprüft wurde**, steht
so auch im Prüfdokument: der Kopplungsweg über die Oberfläche und die beiden
Kreisläufe (csv, edbak). Beide gehören zu AP12.

**Zahlen, je zwei Läufe mit und ohne Bremse:**

| | ohne Bremse | mit Bremse | Δ |
|---|---|---|---|
| Median je Anfrage | 14,43 ms | 15,14 ms | +4,9 % |
| Mittel je Anfrage | 19,67 ms | 20,33 ms | +3,4 % |
| Fehlversuche | 0 | 0 | — |

Die Streuung **zwischen zwei gleichen Läufen** liegt bei 3 bis 4 %. Der
Aufschlag ist damit die **obere Schranke**, nicht der Messwert — die Bremse
kostet eine zusätzliche, indizierte Abfrage je Upload.

#### Zwei Funde neben der Sache

Beide sind als Backlog **Nr. 206** und **Nr. 207** aufgenommen und
ausdrücklich *nicht* nebenbei geändert worden:

- `.github/workflows/auslieferung.yml:144` ruft `serverprobe.py --basis …` —
  ein Argument, das es nicht gibt. Der Schritt läuft nur bei Tag-Läufen und
  bricht dort **jedes Mal** ab. Zuständig ist AP9, das den Messstand ohnehin
  anfasst.
- `gen-em.org` steht 96× in `tools/` und `.github/` (in `server/`: 0).
