# Konzept — Zentralisierung: eine Stelle je Sache

**Rahmenplan:** Schritt 15 (R83). **Backlog:** 202 (Sammelnummer), 57
(Tagesübersicht baut ihre Tabelle zweimal). **Reihenfolge:** Kette II → 16 →
**15** → 10c. **Messbasis:** `origin/main` `862ca7f`, Web 20.25.0, gemessen am
20.09.2026 — nach dem Merge von 10a (PR #50) **und** 10b (PR #57), beide
gemeinsam nachgemessen; **nachgeprüft** an `fd99989` (Web 20.26.2, nach dem
Merge von Schritt 16) — Schlüsselzahlen unverändert, siehe 1.0. **Modell:** Konzept Fable (R14), Umsetzung Opus, **kein
Fable-Schritt, kein Mockup**. **Ablage:** dieses Dokument
(`docs/konzepte/Konzept-Zentralisierung.md`), Prüfdokument daneben
(`Pruefdokument-Zentralisierung.md`, entsteht mit AP1 und wächst je Paket).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 20.09.2026 — **Freigegeben (Auftraggeber, 20.09.2026).** Umfang am 20.09.2026 bestätigt; vier Entscheidungen nach der Messung vom Auftraggeber getroffen (E-ZE-01 bis -04, „alles wie empfohlen"); F-ZE-1 bis F-ZE-6 gelten mit der Freigabe. |
> | Entschieden | E-ZE-01 bis E-ZE-08 (Gespräch), E-ZE-10 bis E-ZE-24 (Nachmessen); E-ZE-09 ist nicht vergeben |
> | Offen | nichts im Konzept. **F-ZE-1 bis F-ZE-6** (Abschnitt 2.3) sind mit der Freigabe vom 20.09.2026 entschieden. Außerhalb des Konzepts: die Einschübe (Abschnitt 8, 9) sind noch nicht eingespielt |
> | Umsetzung | zehn Arbeitspakete, eines nach dem anderen, eigener Zweig von `main`. **Voraussetzungen:** Kette II bis M1 und auf `main` — **offen** (Kette II steht bei AP4; M1 folgt auf AP4, AP5 und AP6 und ist ein Schritt der Betreiberin: Tag und Freigabe); Schritt 16 gemergt — **erfüllt** (PR #61/#62, `main` `fd99989`, Web 20.26.2; `sitzung_lib.php` besteht). **Merge nach `main` nur auf Ansage** (löst einen Staging-Deploy aus) |
> | Nummern | Dieses Konzept vergibt **keine** Rahmenplan-Fassung, **keine** Backlog-Nummer und **keine** Version. Einschübe in Abschnitt 8 und 9 übernimmt die einspielende Instanz |
> | Steuerungsdokumente | liegen bis zum Merge von Kette II auf `claude/fervent-dirac-xirsqw` (Stand `52eb540`: Fassung 95, Backlog 241–249 reserviert, nächste freie 250); `main` trägt Fassung 80. Dieses Konzept liegt dort **noch nicht** — Abschnitt 8.0 |

> **Stand der Umsetzung**
>
> | Paket | Stand | Stufe | Abnahmezahlen |
> |---|---|---|---|
> | AP1 Zählmittel, Register, Gegenprobe Paket 1 | offen | — | — |
> | AP2 Konfiguration und Sitzung | offen | — | — |
> | AP3 API-Eingang und Flash | offen | — | — |
> | AP4 Datenzugriff klein | offen | — | — |
> | AP5 Transaktion und Kindtabellen | offen | — | — |
> | AP6 Spaltenregister `missions` | offen | — | — |
> | AP7 Zeit und Zahl (PHP) | offen | — | — |
> | AP8 JavaScript | offen | — | — |
> | AP9 Nr. 57 — eine Einsatztabelle | offen | — | — |
> | AP10 Abschluss und Übergabe an 10c | offen | — | — |

---

## 0. Auftrag und Umfang

Im Web-Teil (`server/`, ohne `assets/vendor/` und `vendor/`) bekommt jede Sache
**eine** Stelle — nach dem Vorbild von `mission_fields.php`. Android und Uhr
sind ausdrücklich nicht Gegenstand. Grundlage ist der Befund aus Backlog
Nr. 202 (16.09.2026, sechs Pakete); seine Zahlen gelten hier nur als
Hypothese und sind in Abschnitt 1 **neu gemessen**.

**Warum vor 10c:** 10c baut Banner, Protokollseite, Fehlerbehandler,
Support-Rolle, Zweitfaktor, Health-Endpunkt, Betriebslage und Bounce-Postfach.
Jedes davon trifft nach diesem Schritt je Sache eine Stelle statt vier —
Abschnitt 6 nennt sie je 10c-Arbeitspaket.

**Grundregel (E-ZE-10):** Dieser Schritt ändert **kein Verhalten**. Wo Kopien
auseinandergelaufen sind, nennt das Konzept die Fassung, die gilt, und jede
sichtbare Folge steht hier als Entscheidung. Es gibt genau **fünf** benannte
Ausnahmen: Nr. 57 (E-ZE-01, entschieden), „heute" in der App-Zeitzone
(F-ZE-1), lesende Seiten starten keine Sitzung ohne Cookie (F-ZE-2), die
Fehlerschlüssel des API-Eingangs (F-ZE-5) und die Altersangabe auf Betrieb →
Updates (E-ZE-23). Nichts sonst.

**Nicht Gegenstand:**

- **Der Log-Helfer** — die `error_log()`-Aufrufe stellt **10c AP3** um
  (E-P5c-12). Schritt 15 schreibt nur ihre **Zahl** fort (E-ZE-05).
- Android, Uhr, `docs/JSON-Vertrag.md`: Die Geräte-Endpunkte (`ingest.php`,
  `pair.php`, `auth_salt.php`, `jobs.php`, `gpx.php`) behalten Fehlerschlüssel
  und Antwortformen **zeichengleich**.
- Sitzungsbindung per Cookie-Token (Schritt 18, E-SA-09).
- Erklärtexte, Menü, Druckseiten (10c AP9).
- Die bewussten PHP-JS-Spiegelungen (`PHASE_LABELS`, `RESUS_LABELS`,
  `dt_art_symbole()`) — sie bleiben, wie im Rahmenplan festgehalten.
- **Gelaufene Migrationen** in `migration_lib.php` (E-ZE-04).
- **Paket 6 (Beifang)** aus Nr. 202 bekommt **kein** Arbeitspaket (E-ZE-07).
- `post_ende()` / Umleiten nach POST auf den Admin-Seiten (F-ZE-4).
- Die Deadlock-Behandlung in `ingest.php` (Nr. 210, Schritt 18).

## 1. Befund — gemessen an `origin/main` `862ca7f`, 20.09.2026

### 1.0 Messverfahren

Gezählt wurde mit einem Zerleger, der PHP-Dateien in drei Sichten teilt:
**Code ohne Kommentare** (Zeichenketten erhalten — für SQL-Literale), **Code
ohne Kommentare und ohne Zeichenketteninhalt** (für Funktionsaufrufe) und den
**HTML-Anteil** außerhalb von `<?php … ?>` (für Inline-JavaScript). JS-Dateien
ohne Kommentare. Bestand: **132 PHP-Dateien, 40 JS-Dateien**. **Geeicht** an
zwei bekannten Zahlen: `error_log(` **77 Aufrufe in 32 Dateien**,
`session_start(` **9 in 9 Dateien** — beide getroffen.

Das Messwerkzeug dieser Sitzung war Python und liegt **nicht** im
Repositorium. AP1 baut das bleibende Zählmittel mit dem PHP-Tokenizer (Bauform
wie `tools/sitzungshaertung/pruefen.php`) und **misst alle Startwerte nach**.
**Weicht eine Zahl ab, gilt die des Werkzeugs**; die Abweichung wird in diesem
Dokument mit Grund nachgetragen (Rahmenplan Abschnitt 9: gemessen, nicht
fortgeschrieben).

**Nachprüfung an `main` `fd99989` (Web 20.26.2, Schritt 16 gemergt), 20.09.2026:**
133 PHP-Dateien (neu: `sitzung_lib.php`), 40 JS-Dateien. Unverändert:
`error_log(` 77 in 32 Dateien · `session_start(` 9 in 9 · `config.php`-Lesestellen
7 in 5 · `$CFG`-Zugriffe 44 in 11 · `app_state`-SQL 34 in 15 ·
`beginTransaction(` 33 in 22 · `edbak_groesse_text(` 43 in 10. Neu und wie
erwartet: `sitzung_ablage()` mit **zwei** Aufrufstellen (`db.php` Z. 586,
`install.php` Z. 144). Die drei lesenden Seiten tragen weiter **keine**
Cookie-Bedingung (FF-2 besteht fort).

### 1.1 Paket 1 — Marke, Mail, Link, Token: **durch** (Gegenprobe)

| Muster | 16.09. | 20.09. | Lage |
|---|---|---|---|
| `INSERT INTO password_resets` | 4 Stellen | **2, nur `konto_lib.php`** | erledigt (P5b AP2) |
| `base_url`-Verkettung von Hand | 5 | **0** — `'base_url'` nur in `install.php` (4, schreibt), `instanz_lib.php` (2, `app_url()`), `config.example.php` (1) | erledigt (P5a AP5) |
| Mailrahmen von Hand | 7 Versandstellen | **0** — `mail_rahmen(` 20 Aufrufe, 19 in `mail_lib.php`, 1 in `instanz_lib.php`; Versand über `mail_einreihen(` (19 Aufrufe, 14 Dateien), Texte aus `mail_katalog()` | erledigt (P5a AP5) |
| Token-Laufzeiten als SQL-Literal | 4 | **0** — `TOKEN_EINLADUNG_S`, `TOKEN_RESET_S` in `konto_lib.php` | erledigt (P5b AP2) |

10b hat hier **nichts neu verstreut**: Selbstregistrierung, Adresswechsel und
Freischaltung reihen über den Katalog ein.

### 1.2 Paket 2 — Datenzugriff: **alles gewachsen**

| Muster | 16.09. | 20.09. | Einzelheiten |
|---|---|---|---|
| `app_state` direkt per SQL | 24 Stellen | **34 in 15 Dateien** | obwohl `app_state_lesen()` (22 Aufrufe, 10 Dateien) und `app_state_setzen()` (28, 12 Dateien) in `db.php` bestehen. Nach Operation: SELECT 15, INSERT 12, `INSERT IGNORE` 2, DELETE 5. Dateien: `jobs_lib.php` 6, `demo_lib.php` 5, `db.php` 4, `migration_lib.php` 3, je 2 `adminbackup_lib.php`, `auth_salt.php`, `geocoder_lib.php`, `registrieren.php`, `serverkrypto_lib.php`, je 1 `admin_installation.php`, `jobs.php`, `konten_einstellungen_lib.php`, `konto_lib.php`, `session_lib.php`, `smtp.php` |
| Wrapper-Paare um `app_state` | 5 | **4 Paare + Einzelne** | `edbak_marke_lesen/-setzen`, `geocoder_state/-setzen`, `schluessel_marke_lesen/-setzen`, `geraete_hinweis_stand/-bestaetigen`; dazu `demo_*`, `jobs_*`, `logo_*`, `smtp_versand_vermerken()` |
| „Geheimnis einmalig anlegen" | — | **2× baugleich** | `auth_salt.php` (`salt_secret`), `registrieren.php` `reg_geheimnis()` — SELECT, sonst `INSERT IGNORE`, dann erneut lesen |
| Virtuelles Gerät `manual-<userId>` | 4× zeichengleich | **4 baugleiche Blöcke „Gerät sicherstellen"** | `api/import_commit.php`, `api/schneiden.php` `schnitt_geraet()`, `api/gpx_import.php` `gpx_import_geraet()`, `einsatz_form.php`. Dazu **3 `NOT LIKE 'manual-%'`-Literale** (`admin_users.php`, `admin_user.php`, `betrieb_statistik.php`), obwohl `GERAETE_ECHT_SQL` und `geraet_virtuell()` in `db.php` bestehen. Literal `'manual-` außerhalb `db.php`: **7** |
| Einsatz per ID mit Besitzprüfung | 11 in 9 Dateien | **13 in 9 Dateien** | `trash_lib.php` 3, `api/schneiden.php` 2, `einsatz_form.php` 2, je 1 `api/import_commit.php`, `api/mission.php`, `einsatz.php`, `einsatz_verschieben.php`, `papierkorb.php`, `tageszuordnung_lib.php`. Spalten: `*`, `day_id`, `final`, `id, day_id`, …; Papierkorb-Bedingung in drei Fassungen (`IS NULL`, `IS NOT NULL`, keine). Gegenstück `dt_laden()`: 19 Aufrufe, 13 Dateien |
| Handlisten der `missions`-Spalten | 8 | **11 in 6 Dateien** | `api/export_data.php` 2 (SELECT 25 Spalten, Ausgabefeld 28), `api/import_commit.php` 3 (INSERT 27, UPDATE 26, Werteliste 19), `api/suchindex.php` 2 (19, 17), `backup_lib.php` 2 (`$missionSpalten` 30 mit Alias `uhr_gesperrt AS manual`, `$cols` im Einspielen), `api/range.php` 1, `api/mission.php` 1. Nicht gezählt: `migration_lib.php` (Historie), `mission_fields.php` (der Katalog) |
| Transaktionsrahmen | 21 Dateien | **`beginTransaction(` 33 in 22 Dateien**, `inTransaction(` 50, `rollBack(` 40 | verschachtelungsfeste Fassung (`$eigene = !$pdo->inTransaction()`) nur in `spur_lib.php` und `backup_lib.php`; **kein** `db_transaktion()` |
| Kindtabellen eines Einsatzes | 4 Schreibwege | **5 Schreibwege, 30 Anweisungen** | neu: `api/schneiden.php`. `mission_phases` INSERT 5/DELETE 4, `resus_sessions` 4/3, `resus_events` 4/0, `mission_resources` 3/2, `mission_crew` 3/2 (ohne `migration_lib.php`) — in `einsatz_form.php`, `api/import_commit.php`, `ingest.php`, `api/schneiden.php`, `backup_lib.php` |
| Schema-Abfrage „gibt es Tabelle/Spalte?" außerhalb `migration_lib.php` | — | **9 `information_schema` in 6 Dateien** | `komplett_lib.php` 2, `nachbearbeitung_lib.php` 2, `sicherungsziel_lib.php` 2, `ingest.php` 1, `speicher_lib.php` 1, `wiederherstellen.php` 1; die Helfer `_hat_tabelle/_hat_spalte/_hat_index/_fk_name` sind privat in `migration_lib.php` |
| Rollenvergleich von Hand (`=== 'admin'` …) | — | **7 in 3 Dateien**, davon **4 außerhalb `db.php`** | `admin_user.php` 3, `admin_users.php` 1; `rolle_darf_verwalten()`/`rolle_ist_betreiberin()` bestehen (12 Aufrufe, 7 Dateien) |

### 1.3 Paket 3 — API-Eingang, Sitzung, Flash

| Muster | 16.09. | 20.09. | Einzelheiten |
|---|---|---|---|
| Dateien unter `api/` | 12 | **21** | alle prüfen die Methode selbst: `REQUEST_METHOD` 24× in 21 Dateien; `'error' => 'method'` **17× in 17 Dateien** unter `api/` (19 in `server/` gesamt) |
| JSON-Rumpf lesen (`php://input`) | 12 | **15** | 12 unter `api/` (darunter `api/csp_bericht.php`, Sonderfall), dazu `auth_salt.php`, `ingest.php`, `pair.php` (Gerätevertrag, bleiben) |
| Fehlerschlüssel „Rumpf ist kein JSON-Objekt" | uneinheitlich | **`payload` 14 (10 Dateien), `format` 4 (3)** — für dieselbe Sache | `leer` 4 (davon 3× leerer Rumpf mit `post_max_size`-Hinweis in drei Fassungen: `api/backup_restore.php`, `api/backup_eintraege_restore.php`, `api/backup_spuren_restore.php`), `zu_gross` 2, `too_large` 1. **Kein JS wertet diese Schlüssel aus** (gemessen: 0 Verbraucher; die Aufrufer lesen `data.meldung` und `data.error` nur als Wahrheitswert) |
| CSRF-Gatter | erledigt P5a | **erledigt** — `csrf_check(` 50 Aufrufe, 40 Dateien, 0 inline | bleibt eine Zeile je Datei |
| Sitzungsstart | 7 in 3 Varianten | **9 in 9 Dateien, 4 Varianten** | Tabelle unten |
| Flash-Meldung über die Sitzung | 3 Dateien | **22 Zugriffe in 3 Dateien**, kein Helfer | `einstellungen.php` 10, `nachbearbeitung.php` 8, `papierkorb.php` 4 |
| Verzögerte Antwort der unangemeldeten Endpunkte | 7 inline | **erledigt (P5a)** | `rate_gleiche_dauer()` einmal definiert, 16 Aufrufe in 7 Dateien; `json_out()`/`json_roh_out()` zentral |
| **Neu:** `config.php` lesen | — | **7 Lesestellen in 5 Dateien + 44 `$CFG`-Zugriffe in 11 Dateien**, kein Leser | `require … config.php`: `smtp.php` 3, `db.php` 1, `jobs.php` 1, `mail_lib.php` 1, `instanz_lib.php` 1 (Rückfall). `$CFG`/`global $CFG`/`$GLOBALS['CFG']`: `serverkrypto_lib.php` 17, `db.php` 10, `betrieb_schluesselblatt.php` 4, `plattform_lib.php` 3, je 2 `api/schluesselblatt_pruefen.php`, `migration_lib.php`, `tageszuordnung_lib.php`, je 1 `import.php`, `ingest.php`, `instanz_lib.php`, `netz_lib.php` |

**Die neun Sitzungsstarts und ihre vier Varianten:**

| Art | Dateien | Cookie-Parameter | Besonderheit |
|---|---|---|---|
| `app` | `auth_guard.php`, `login.php`, `session_lib.php` (`session_beenden()`) | `secure => true`, `samesite => 'Strict'` | — |
| `lesend` | `doku_seite.php`, `rechtstext_seite.php`, `notfallblatt.php` | `secure => !empty($_SERVER['HTTPS'])`, `Strict` | `@session_start()`, nur wenn `session_status() === PHP_SESSION_NONE`; **keine Cookie-Bedingung** (FF-2) |
| `einrichtung` | `install.php`, `wiederherstellen.php` | `secure => !empty($_SERVER['HTTPS'])`, `samesite => 'Lax'` | `install.php` lädt **kein** `db.php` |
| `passwort` | `pw_handling.php` (`pw_session_start()`) | `secure => true`, `Lax` | eigener Sitzungsname `PW_SESSION_NAME` (`EDPWSESS`) |

Alle neun setzen `session.use_strict_mode` davor; der Stufe-1-Schritt
„Sitzungshärtung" (`tools/sitzungshaertung/pruefen.php`) prüft genau das.

### 1.4 Paket 4 — JavaScript

| Muster | 16.09. | 20.09. | Einzelheiten |
|---|---|---|---|
| JSON-POST mit Kopf `X-CSRF` | 15 in 6 Dateien | **15 in 6 Dateien** (unverändert) | `einstellungen.php` 6, je 2 `assets/export.js`, `assets/import_ui.js`, `assets/schneiden.js`, `assets/unlock.js`, `index.php` 1; zwei Schreibweisen des Tokens (`CSRF` und `typeof CSRF === 'string' ? CSRF : ''`) |
| **Neu aus 10b:** Formular-POST mit Feld `csrf` | — | **5 in 3 Dateien** | `assets/rueckfrage.js` 2, `assets/schluesselblatt.js` 2, `assets/schluessel.js` 1 — ein **zweiter** Transportweg neben dem Kopf; `csrf_check()` nimmt beide |
| `fetch(` gesamt | — | 33 in 16 Dateien | vier Fehlerschemata (`data.meldung ‖ data.error`, fester Satz, `e.message`, Status) |
| Formatierer-Definitionen (Tag, Dauer, km) | Datum 6×, Dauer 3 Schreibweisen, km 7× | **12 Definitionen in 4 Dateien** + 2 Dauer in `export.js` | `missiontable.js`: `fmtTag`, `fmtDur`, `fmtKm`, `fmtKmZahl`; `index.php`: `fmtDay`; `einsatz.php`: `fmtDay`, `fmtKm`, `fmtDauer`; `zeitraum.php`: `fmtKmDe`, `wertKm`, `wertKmSumme`, `fmtTagKurz` |
| Karten-Präambel (`L.map(`) | 4 Seiten | **4 Seiten + `ortswahl.js`** | `einsatz.php`, `index.php`, `tag_spuren.php`, `zeitraum.php`; `L.tileLayer(` ist schon zentral (`assets/map_layers.js`) |
| Rahmen um `EdPat.entschluessleListe()` | 3 | **6 Aufrufer in 6 Dateien** | Seiten: `index.php`, `suche.php`, `zeitraum.php`, `einstellungen.php`; Module: `assets/export.js`, `assets/import_ui.js` |
| Meldungs-Markup im JS nachgebaut | 6 | **nicht belastbar gemessen** | eine breite Suche nach `class="meldung` im HTML-Anteil zählt PHP-Markup mit (35 Erwähnungen, 15 Dateien). AP1 misst mit der Regel „Zeichenkette im JS-Kontext" |

### 1.5 Paket 5 — Zeit, Zahl, Migration

| Muster | 16.09. | 20.09. | Einzelheiten |
|---|---|---|---|
| Bytes lesbar | 2 Funktionen | **`edbak_groesse_text()` in `adminbackup_lib.php`: 43 Aufrufe in 10 Dateien** | der Beleg aus R83, unverändert — neun fremde Dateien laden die Backup-Bibliothek dafür (`admin_sicherungen.php` 7, `betrieb_server.php` 6, `status_lib.php` 6, `admin_komplettsicherung.php` 5, `admin_sicherungsziele.php` 4, `wiederherstellen.php` 3, `speicher_lib.php` 2, `betrieb_sicherheit.php` 1, `betrieb_updates.php` 1) |
| Relative Zeit („vor … Minuten") | 3 | **2 Bauten** | `status_alter()` in `status_lib.php` (8 Aufrufe, nur dort) und eine Inline-Kopie in `betrieb_updates.php` mit **abweichender Rundung** (`max(1, …)`) |
| `number_format(` | 22 | **41 in 19 Dateien**, davon **26 in deutscher Form** (`, ',', '.'`) in 11 Dateien | — |
| Umrechnung Bytes → Anzeige von Hand | GB 7× | **18 Divisionen in 8 Dateien** | `betrieb_server.php` 4, `adminbackup_lib.php` 3, je 2 `admin_user.php`, `apk_lib.php`, `einstellungen.php`, `ingest.php`, `jobs_lib.php`, `gpx_lib.php` 1 (Byte-Literale insgesamt 33 in 12 Dateien; der Rest sind Grenzwerte, keine Anzeige) |
| Prozent von Hand | 5 Dateien | **10 in 5 Dateien** | `betrieb_statistik.php` 3, `speicher_lib.php` 3, `betrieb_server.php` 2, `adminbackup_lib.php` 1, `plattform_lib.php` 1 |
| ISO-UTC-Marke schreiben | 21 | **20 in 7 Dateien** (`gmdate('Y-m-d\TH:i:s\Z'`) | `komplett_lib.php` 9, `adminbackup_lib.php` 4, je 2 `gpx_lib.php`, `wiederherstellen.php`, je 1 `smtp.php`, `speicher_lib.php`, `wartung_lib.php`; `gmdate(` gesamt 50 in 24 Dateien |
| ISO-UTC-Marke lesen | 9 | **9 in 5 Dateien** (`str_replace(['T','Z'], …) . ' UTC'`) | `status_lib.php` 5, je 1 `admin_sicherungen.php`, `admin_user.php`, `adminbackup_lib.php`, `wartung_lib.php` |
| Datumsformat-Literale mit Tag.Monat | 45 in 4 Trennervarianten | **67 in 26 Dateien, 5 Varianten** | `'d.m.Y'` 29, `'d.m.Y H:i'` 25, `'d.m.Y · H:i'` 11, `'d.m.Y, H:i'` 1, `'d.m.Y H'` 1 |
| `date(` (Zeitzone des Servers) | 3 | **14 in 9 Dateien**; **`date_default_timezone_set` kommt nicht vor** | 6 davon ohne Zeitstempel („heute"): `diensttag_neu.php` 2, `einsatz_form.php` 1, `betrieb_statistik.php` 1, `install.php` 2 (FF-1) |
| `migration_lib.php` gegen `information_schema` | 40 inline | **57 Erwähnungen** bei 42 Helferaufrufen (`_hat_spalte` 27, `_hat_index` 10, `_hat_tabelle` 3, `_fk_name` 2) | gelaufene Migrationen — **bleiben** (E-ZE-04) |

### 1.6 Paket 6 — Beifang (kein Arbeitspaket)

Gemessen nur, was 10c berührt: **Kopien von `asset()`/`e()` in
`betrieb_schluesselblatt.php`: 0** — erledigt, beide stehen nur noch in
`db.php`. **Verwaltungsseiten-Auftakt:** 17 Seiten, je eine Zeile
`require_admin()` oder `require_betreiberin()` (`auth_guard.php`) — das
Rollengatter **ist** eine Stelle; der Rest des Auftakts bleibt Beifang.

### 1.7 Nr. 57 — gilt unverändert

`index.php` baut seine Zeilen weiter selbst (`tr.innerHTML = …`) und holt vom
Modul nur Zellbausteine (3 Verwendungen von `EdMissionTable.`); `suche.php` und
`zeitraum.php` nutzen das Modul ganz. **Spaltensatz:** Modul `col, art, day,
start, dur, site, age, dx, winch, bw, sec, fehl, km`; `index.php` führt
zusätzlich **`no` (Nr.)** und die Tagesspalten aus `mf_tagesspalten()`, es
fehlen ihm `art`, `day`, `fehl`. `mf_tagesspalten()` hat genau zwei
Verbraucher (`index.php`, `api/day.php`); `api/range.php` und
`api/suchindex.php` führen `winch`, `bergwacht`, `secondary` hart im SELECT.
Das Modul kennt `setSpaltenBestand()` — der Haken für `cap_gate` besteht also.

---

## 2. Entscheidungen

### 2.1 Aus dem Gespräch (E-ZE-01 bis -08)

**E-ZE-01 — Nr. 57: Das Modul gilt auch für Gleichstände und `sortable`**
(Auftraggeber, 20.09.2026). Die Entscheidung vom 12.09.2026 („`missiontable.js`
ist die Vorlage") deckte Beschriftung, Ausrichtung und Hakenreihenfolge. Sie
gilt jetzt ausdrücklich auch für (a) die **Sortierung bei Gleichständen** — das
Modul verlässt sich auf die stabile Sortierung; ein zweiter Klick auf eine
Spalte mit lauter gleichen Werten dreht die Zeilen **nicht mehr** — und (b)
`sortable` auf **jedem** Spaltenkopf samt `cursor:pointer` und Hover. **Kein
Mockup.**

**E-ZE-02 — Ein Konfigurationsleser `konfig()` kommt in den Umfang**
(Auftraggeber, 20.09.2026). Nicht in Nr. 202, aber gemessen (1.3) — und 10c
bringt drei neue Schlüssel (`app.umgebung`, `betrieb.health_token`,
`mail.postfach`), jeder wäre eine achte bis zehnte Lesestelle. Gebaut in AP2.

**E-ZE-03 — Das Zählmittel entsteht hier, nicht in 10c AP3** (Auftraggeber,
20.09.2026). `tools/zaehlung/` entsteht in AP1, der Stufe-1-Schritt in AP10.
10c AP3 baut **kein** Zählmittel mehr, sondern setzt die Zeile `error_log` des
Registers auf Soll ≤ 2 (Nr. 248 wird damit eine Registerzeile). Das ändert
**einen Satz** im freigegebenen P5c-Konzept — Wortlaut in Abschnitt 9.

**E-ZE-04 — Gelaufene Migrationen werden nicht umgebaut** (Auftraggeber,
20.09.2026; weicht bewusst von „alles wird angegangen" ab). P8 setzt das
Migrationsregister neu auf (R66); 57 Abfragen in Migrationen umzuschreiben,
die auf jeder Anlage schon gelaufen sind, ist Risiko ohne Ertrag. Stattdessen:
die Schema-Helfer werden **öffentlich** (`db_hat_tabelle()`, `db_hat_spalte()`,
`db_hat_index()` in `db.php`, AP4), die privaten `_hat_*` rufen sie nur noch
auf, und die **Regel** „neue Migrationen fragen das Schema nur über die
Helfer" steht in `CLAUDE.md` und als Registerzeile (Stand 57 als Decke: die
Zahl der `information_schema`-Erwähnungen in `migration_lib.php` darf nicht
steigen).

**E-ZE-05 — Log-Helfer nicht hier, seine Zahl schon** (Auftrag). Kein
Arbeitspaket stellt einen `error_log()`-Aufruf um. Wer Code verschiebt,
verschiebt die Zeile **unverändert** mit. Jedes AP nennt in seiner
Abnahme die Zahl der `error_log(`-Aufrufe; Start **77 in 32 Dateien**, die
Übergabezahl an 10c AP3 steht im Statusblock von AP10. Eine Abweichung von 77
ist zu erklären (welche Zeile, warum).

**E-ZE-06 — `sitzung_ablage()` wandert in `sitzung_starten()`** (Auftrag;
Konzept Sitzungsablage E-SA-02). Schritt 16 ruft sie an zwei Stellen
(`db.php`, `install.php`); mit AP2 ruft sie allein `sitzung_starten()`, die
beiden Aufrufe entfallen.

**E-ZE-07 — Paket 6 (Beifang) bleibt Liste mit Regel, kein Arbeitspaket.**
Es gilt, was der Rahmenplan sagt: nur zusammen mit Arbeit an der jeweiligen
Datei, kein Termin. Die Frage aus der Umfangsbestätigung, ob der
Verwaltungsseiten-Auftakt wegen der Support-Rolle (10c AP4) vorzuziehen sei,
ist mit der Messung beantwortet: **nein** — das Rollengatter ist schon eine
Stelle (1.6).

**E-ZE-08 — Nr. 57 gehört zu Schritt 15 und ist ein eigenes Arbeitspaket**
(Auftrag). AP9, nach AP6 und AP8.

### 2.2 Aus dem Nachmessen (E-ZE-10 bis -24)

**E-ZE-10 — Kein Verhalten ändert sich.** Wortlaut in Abschnitt 0. Prüfbar
gemacht durch: Kreisläufe csv und edbak **0 unerklärt**, Byte-Vergleich von
Export und Backup (AP6), Bilderlauf **0/0/0** außerhalb der in AP9 genannten
Abweichungen, alle Proben unter `tools/` grün wie zuvor.

**E-ZE-11 — Ort je Helfer (R83: Bibliotheksdatei statt Seite).**

| Helfer | Datei | Grund |
|---|---|---|
| `konfig()`, `konfig_verwerfen()` | **`konfig_lib.php`** (neu, **ohne Abhängigkeiten**) | `install.php` und `sitzung_lib.php` müssen sie ohne `db.php` laden können |
| `sitzung_starten()` | **`sitzung_lib.php`** (besteht seit Schritt 16; lädt nie `db.php` und zieht nur für die stündliche Schreibprobe `plattform_lib.php` nach) | `install.php` lädt kein `db.php`; `session_lib.php` lädt `db.php` und scheidet deshalb aus |
| `api_eingang()` | **`db.php`**, neben `json_out()` | 10c AP6 (`api/health.php`) braucht ihn **ohne** `auth_guard.php`; CSRF bleibt deshalb draußen (E-ZE-15) |
| `flash_setzen()`, `flash_holen()` | `session_lib.php` | hängt an der Sitzung |
| `app_state_*`, `db_transaktion()`, `db_hat_*()`, `geraet_virtuell_sicherstellen()`, `geraete_echt_sql()` | `db.php` | dort liegen die Nachbarn schon |
| `einsatz_laden()`, `einsatz_*_ersetzen()` | **`einsatz_lib.php`** (neu) | Gegenstück zu `diensttag_lib.php` |
| `mf_missions_register()`, `mf_spalten()` | `mission_fields_lib.php` | der Katalog ist das Vorbild |
| `groesse_text()`, `zeit_relativ()`, `zahl_text()`, `prozent_text()`, `iso_utc()`, `iso_utc_lesen()`, `heute_lokal()`, `datum_text()`, `datum_zeit_text()` | **`format_lib.php`** (neu; lädt nur `konfig_lib.php`) | kein Verbraucher soll dafür eine Fachbibliothek laden müssen — der R83-Beleg |
| `EdApi` | **`assets/api.js`** (neu) | in der Immer-Liste von `ui.php` (`ui_seite_*`) |
| `EdFormat` | **`assets/format.js`** (neu) | `missiontable.js` wird erster Verbraucher |
| `EdHtml.meldung()` | `assets/html.js` (besteht) | — |
| `EdKarte.anlegen()` | `assets/map_layers.js` (besteht) | dort liegt `L.tileLayer` schon |

Namen sind Vorschläge und am 20.09.2026 **auf Kollision geprüft: 0 Treffer**.
Ändert die Umsetzung einen Namen, trägt sie ihn hier und in Abschnitt 6 nach.

**E-ZE-12 — `sitzung_starten(string $art): bool` mit vier Arten.** `app`,
`lesend`, `einrichtung`, `passwort` — mit **genau** den Parametern der Tabelle
in 1.3. Ablauf: läuft schon eine Sitzung → `true`, nichts tun; sonst
`sitzung_ablage()`, `session_name()` (nur `passwort`),
`session_set_cookie_params()`, `ini_set('session.use_strict_mode', '1')`,
`session_start()` (bei `lesend` mit `@`). `PW_SESSION_NAME` zieht als Konstante
nach `sitzung_lib.php`. Die Drift bei `secure` (fest `true` gegen
HTTPS-abhängig) **bleibt**, wie sie ist — sie zu schließen ist eine
Sicherheitsentscheidung für Schritt 18, keine Zentralisierung; sie steht als
Kommentar an der Tabelle der Arten.

**E-ZE-13 — Der Stufe-1-Schritt „Sitzungshärtung" wird umgestellt.** Nach AP2
gibt es **einen** `session_start(`-Aufruf in `server/`. Die Prüfung verlangt
dann: (a) genau ein Aufruf, (b) er steht in `sitzung_lib.php`, (c) davor die
Härtungszeile, (d) **jeder weitere Aufruf irgendwo ist ein Befund**. Die
Selbstprobe wird entsprechend neu geschrieben (Fallzahl im Prüfdokument).

**E-ZE-14 — `konfig(string $pfad, mixed $vorgabe = null): mixed`.** Punktpfad
(`'app.timezone'`), Ergebnis in einer `static` gemerkt, fehlende `config.php`
→ `$vorgabe` (der Fall `install.php`). `konfig_verwerfen()` leert den Merker;
`config_gemerktes_verwerfen()` in `serverkrypto_lib.php` ruft sie mit auf
(derselbe Fehler wie S2/AP7 wäre sonst wieder da: die Seite, die gerade
geschrieben hat, zeigt den alten Stand). Die globale `$CFG` in `db.php`
entfällt, sobald ihre Zugriffszahl 0 ist — in AP2, nicht später.

**E-ZE-15 — `api_eingang(array $o = []): array`.** Optionen: `methode`
(`'POST'` Vorgabe, `'GET'` oder Liste), `rumpf` (`'json'` Vorgabe, `'keiner'`),
`max_bytes`. Er prüft die Methode, liest den Rumpf, prüft „leer" und „ist ein
JSON-Objekt" und gibt den Rumpf zurück. **CSRF gehört nicht hinein** — die
Zeile `csrf_check();` bleibt je Datei stehen: Sie ist schon eine Stelle, und
`api/health.php` (10c AP6) braucht den Eingang ohne Sitzung. **Inhaltliche**
Prüfungen nach dem Eingang (fehlender Schlüssel `eintraege`, falsches
`format`-Feld eines Backups, `leer` in `api/schneiden.php`) bleiben, wo sie
sind, mit ihren Schlüsseln. **Ausnahme, namentlich:** `api/csp_bericht.php`
(anderer Inhaltstyp, kein eigener Aufrufer). Fehlerschlüssel: F-ZE-5.

**E-ZE-16 — Flash:** `flash_setzen(string $ton, string $text): void`,
`flash_holen(): ?array` (liest **und** löscht). Drei Seiten ziehen um; der
Sitzungsschlüssel heißt danach einheitlich `flash`. `post_ende()` aus Nr. 202
wird **nicht** gebaut (F-ZE-4).

**E-ZE-17 — `app_state`: sechs Funktionen, die Wrapper bleiben.** Zu
`app_state_lesen()` und `app_state_setzen()` kommen `app_state_mehrere(array
$k): array`, `app_state_setzen_mehrere(array $kv): bool`,
`app_state_loeschen(string ...$k): void` und `app_state_einmalig(string $k,
callable $erzeuger): string` (`INSERT IGNORE`, dann lesen — atomar, für die
beiden Geheimnisse). Die Wrapper (`edbak_marke_*`, `geocoder_state*`,
`schluessel_marke_*`, `geraete_hinweis_*`, `demo_*`, `jobs_*`, `logo_*`)
**behalten Namen und Signatur** — sie tragen Bedeutung und teils einen eigenen
Merker (`$frisch`) —, ihr Rumpf ruft die Helfer. **Ausnahmen, namentlich:**
`migration_lib.php` (`_tor_lesen()`, `migrationen_tor_merken()`,
`migrationen_tor_zuruecksetzen()` — E-ZE-04) und `job_aufraeumen()` in
`jobs_lib.php` (`DELETE a FROM app_state a …`, ein Verbund, kein Schlüsselzugriff).
**Falle:** **12 Stellen in 5 Dateien** arbeiten mit einem **übergebenen** `$pdo`
statt mit `db()` — `jobs_lib.php` 5, je 2 `auth_salt.php`, `demo_lib.php`,
`registrieren.php`, `konto_lib.php` 1 —, teils in einer Transaktion
(`demo_anlegen()`, `konto_loeschen()`, `jobs_pause()`). Vor
dem Umbau ist je Stelle zu belegen, dass `$pdo` dieselbe Verbindung ist wie
`db()`; wo nicht, bekommt der Helfer einen optionalen Parameter `?PDO $pdo`.

**E-ZE-18 — Virtuelles Gerät:** `geraet_virtuell_kennung(int $userId): string`
und `geraet_virtuell_sicherstellen(PDO $pdo, int $userId): int` neben
`geraet_virtuell()` in `db.php`; die vier Blöcke rufen sie, `schnitt_geraet()`
und `gpx_import_geraet()` entfallen. Für die drei `LIKE`-Literale
`geraete_echt_sql(string $alias = ''): string` (zwei der drei Stellen brauchen
den Tabellenalias `d.`, den die Konstante nicht trägt) und eine Konstante
`GERAET_VIRTUELL_MUSTER` für die Stelle, die das Muster als Parameter bindet.

**E-ZE-19 — `einsatz_laden(int $id, int $userId, array $o = []): ?array`.**
Optionen `spalten` (Vorgabe `'*'`) und `papierkorb` (`'nein'` Vorgabe ·
`'ja'` · `'egal'`). 12 der 13 Stellen ziehen um; **Ausnahme, namentlich:**
`trash_restore_mission()` (Verbund mit `days`).

**E-ZE-20 — `db_transaktion(PDO $pdo, callable $fn): mixed`.**
Verschachtelungsfest (`$eigene = !$pdo->inTransaction()`), `rollBack()` bei
jedem `Throwable` nur für die eigene Transaktion, danach weiterwerfen,
Rückgabewert der Funktion durchgereicht. AP5 zählt **zuerst** die Bauformen
der 33 Stellen aus und trägt das Ergebnis hier nach; umgestellt wird, was die
Bauform „beginnen · versuchen · bestätigen · bei Fehler zurückrollen und
werfen" hat. **Ausnahmen werden namentlich geführt**; gesetzt ist vorab
`ingest.php` (Deadlocks, Nr. 210, Schritt 18 — dort fasst Schritt 15 den
Rahmen nicht an). **Haltepunkt H-ZE-4**, wenn mehr als acht Ausnahmen bleiben.

**E-ZE-21 — Kindtabellen: vier Datenzugriffsfunktionen ohne Prüfpolitik** in
`einsatz_lib.php`: `einsatz_phasen_ersetzen()`, `einsatz_reas_ersetzen()`
(Sitzungen und Ereignisse), `einsatz_rettungsmittel_ersetzen()`,
`einsatz_besatzung_ersetzen()`. Sie nehmen geprüfte Werte und schreiben;
**was gültig ist, entscheidet weiter der Aufrufer**. `mission_crew` ist gegenüber
Nr. 202 dazugekommen (dieselben Schreibwege). `ingest.php` ist der heiße
Pfad und Gerätevertrag: dieselben Anweisungen in derselben Reihenfolge,
belegt durch Ingestprobe und Messstand.

**E-ZE-22 — Spaltenregister für `missions`.** `mf_missions_register()` führt
**jede** Spalte aus `schema.sql` genau einmal und sagt je **Zweck** —
`export`, `backup`, `import_neu`, `import_aendern`, `suchindex`, `range`,
`api_mission` —: dabei · nicht dabei **mit Grund** · dabei unter Alias
(`uhr_gesperrt AS manual`). `mf_spalten(string $zweck): array` liefert die
Liste. Die sieben **SQL-Listen** werden daraus erzeugt; die vier
**Abbildungen** (Ausgabefelder mit Umrechnung) dürfen von Hand bleiben, wenn
eine **Vollständigkeitsprobe** belegt, dass sie genau die Registerspalten
ihres Zwecks führen. Zwei Prüfungen tragen das Paket: (1) die erzeugten Listen
sind den heutigen Handlisten in **Menge und Reihenfolge gleich** (eingefrorene
Abbilder im Werkzeug), (2) **`schema.sql` minus Register = leer** — als
Stufe-1-Schritt neben dem Migrationsregister. Die bewussten Auslassungen aus
`backup_lib.php` (`id`, `user_id`, `device_id`, die tote `other_resources`)
wandern als Grund ins Register. `Export-Format.md` und `Backup-Format.md`
ändern sich **nicht**.

**E-ZE-23 — `format_lib.php`.** `edbak_groesse_text()` heißt dort
`groesse_text()`; der alte Name **entfällt** (43 Aufrufe). `status_alter()`
heißt `zeit_relativ()` und **ist die Fassung, die gilt**; die Kopie in
`betrieb_updates.php` entfällt. Die beiden sind auseinandergelaufen, und das
ist die **einzige sichtbare Folge dieses Pakets**: Auf Betrieb → Updates
steht beim Alter des jüngsten Komplett-Backups unter 90 Sekunden künftig
„gerade eben" statt „vor 1 Minuten", und zwischen 60 und 90 Minuten „vor 60 …
90 Minuten" statt „vor 1 Stunden" (die Kopie wechselt bei 3 600 s auf Stunden,
`status_alter()` bei 5 400 s). `zahl_text()` ersetzt
`number_format(…, ',', '.')` (26 Stellen); die 15 übrigen `number_format`
(andere Form, etwa Koordinaten und GPX) bleiben. `iso_utc()` schreibt,
`iso_utc_lesen()` liest die Marke. Byte-**Anzeigen** laufen über
`groesse_text()`; Byte-**Grenzwerte** bleiben, wo sie sind. Datumsformate:
F-ZE-3.

**E-ZE-24 — Ein Register, das bleibt.** `tools/zaehlung/` führt je Muster eine
Zeile: Kennung, Beschreibung, Regel, Sicht, **Decke**, Ausnahmen. Der
Stufe-1-Schritt (AP10) schlägt fehl, wenn ein Ist-Wert **über** der Decke
liegt. So kommt eine zweite Stelle nicht unbemerkt zurück — der Fall, den R83
mit `edbak_groesse_text()` selbst belegt. Die Startdecken stehen in
Abschnitt 4.

### 2.3 Zum Gegenlesen — mit der Freigabe vom 20.09.2026 entschieden

| # | Vorschlag | Folge | Die andere Wahl |
|---|---|---|---|
| **F-ZE-1** | `heute_lokal()` rechnet „heute" in `app.timezone` statt in der Zeitzone der `php.ini` (FF-1). Vier Stellen: `diensttag_neu.php` 2, `einsatz_form.php` 1, `betrieb_statistik.php` 1; `install.php` bleibt (keine `config.php`) | sichtbar nur, wo Server- und App-Zeitzone auseinanderliegen — dort ist es die Berichtigung | `date()` stehen lassen und den Fund ins Backlog — dann hängt „heute" weiter am Hoster (R81) |
| **F-ZE-2** | Art `lesend` startet die Sitzung **nur, wenn das Sitzungs-Cookie da ist** (FF-2) | anonyme Besucher von Handbuch, „Was ist NAdoku", Rechtstexten bekommen kein Cookie und keine Sitzungsdatei mehr; Angemeldete sehen den angemeldeten Kopf wie bisher | unverändert lassen — dann legt jeder Bot eine Datei in `.sitzungen/` an |
| **F-ZE-3** | Datum-Zeit-Trenner: Schritt 15 **benennt** die Varianten und ändert keinen Pixel — `datum_text()` (`d.m.Y`), `datum_zeit_text($utc, $trenner)` mit den Trennern Leerzeichen (25) und ` · ` (11); `'d.m.Y, H:i'` (1, `betrieb_schluesselblatt.php`) und `'d.m.Y H'` (1, `status_lib.php`) bleiben benannte Einzelfälle. **Die Vereinheitlichung geht an 10c AP9**, das alle elf ` · `-Seiten ohnehin mit Bilderlauf überarbeitet | 67 Literale → 0, Bilderlauf 0/0/0 | jetzt vereinheitlichen — das wäre eine Gestaltungsentscheidung ohne Mockup in einem Schritt, der keins hat |
| **F-ZE-4** | `post_ende()` (Umleiten nach POST auf den Admin-Seiten, die heute nicht umleiten) aus Nr. 202 wird **nicht** gebaut | bleibt als Backlog-Kandidat (Abschnitt 9) | bauen — ändert aber Wege durch die Anwendung und ist damit keine Zentralisierung |
| **F-ZE-5** | Fehlerschlüssel des API-Eingangs unter `api/`: `method` (405), `leer` (400, **ein** `post_max_size`-Hinweis statt drei Fassungen), `format` (400, Rumpf ist kein JSON-Objekt), `zu_gross` (413). `payload` für „kein JSON-Objekt" verschwindet aus `api/` | kein JS wertet die Schlüssel aus (gemessen 0); die Geräte-Endpunkte behalten `payload` und `too_large` (JSON-Vertrag, Android-Tests) | beide Schlüssel im Eingang weiterführen — dann bleibt die Drift, nur an einer Stelle |
| **F-ZE-6** | Nr. 57, **Spaltensatz bleibt je Seite, wie er ist**: Die Tagesübersicht behält „Nr." und bekommt weder „Tag" noch „Art" dazu; das Modul erhält die Spalte `no` und nimmt den Spaltensatz als Seitenparameter. Die Entscheidung vom 12.09. nennt den Spaltensatz nicht | 0 neue, 0 entfallende Spalten auf allen drei Seiten | das Modul bestimmt auch den Spaltensatz — dann verlöre die Tagesübersicht „Nr." und bekäme eine Spalte „Tag" mit lauter gleichen Werten |

---

## 3. Arbeitspakete

### 3.0 Reihenfolge und Regeln

AP1 zuerst (ohne Zählmittel keine Abnahme in Zahlen). AP2 vor allem anderen
in `server/` — daran docken Schritt 16 und 10c an. AP4 vor AP5 und AP6
(`einsatz_lib.php` entsteht dort). AP8 vor AP9 (`assets/format.js`), AP6 vor
AP9 (Spalten für `api/range.php` und `api/suchindex.php`). AP10 zuletzt.

Für **jedes** Paket gilt zusätzlich zu `CLAUDE.md`: Startwerte mit dem
Zählmittel nachmessen und hier eintragen · `error_log(`-Zahl nennen (E-ZE-05)
· Registerzeilen auf die neue Decke setzen · `docs/Technik.md` nachziehen
(neue Bibliothek, entfallene Funktion) · Statusblock fortschreiben · pushen.

**Haltepunkte:** **H-ZE-1** eine Migration wird nötig → anhalten (erwartet:
keine). **H-ZE-2** Byte-Vergleich in AP6 scheitert und die Ursache ist nicht
die Reihenfolge → anhalten. **H-ZE-3** Schritt 16 ist nicht gemergt, oder es
findet sich ein zehnter Sitzungsstart → anhalten vor AP2. **H-ZE-4** mehr als
acht Transaktions-Ausnahmen → melden vor dem Umbau.

### AP1 — Zählmittel, Register, Gegenprobe Paket 1 (E-ZE-03, -24)

`tools/zaehlung/zaehlen.php` (PHP-Tokenizer für `.php`; für `.js` und den
Inline-Anteil ein Kommentar-Entferner), `register.php` (die Zeilen aus
Abschnitt 4), `--selbstprobe`, `LIESMICH.md`. **Kein** Eingriff in `server/`.
Prüfdokument anlegen.
**Abnahme:** Selbstprobe mit benannter Fallzahl, darunter „Aufruf im
Kommentar zählt nicht", „Aufruf in Zeichenkette zählt nicht", „Methodenaufruf
`->date(` zählt nicht"; **Eichung:** `error_log(` = **77 in 32 Dateien**,
`session_start(` = **9 in 9** an `862ca7f` (oder der dann aktuelle Stand, mit
Differenz erklärt); alle Startwerte aus Abschnitt 4 nachgemessen, Abweichungen
eingetragen; die vier Zeilen der Gegenprobe Paket 1 (Z30–Z33) stehen auf ihrer
Decke; die Regel für das Meldungs-Markup im JS (1.4) ist gefunden und
gemessen.

### AP2 — Konfiguration und Sitzung (E-ZE-02, -06, -12, -13, -14; F-ZE-2)

`konfig_lib.php` neu; 7 Lesestellen und 44 `$CFG`-Zugriffe ziehen um, `$CFG`
entfällt. `sitzung_starten()` in `sitzung_lib.php`; neun Starts ziehen um;
die zwei `sitzung_ablage()`-Aufrufe aus Schritt 16 entfallen;
`pw_session_start()` wird ein Einzeiler oder entfällt. Prüfwerkzeug
„Sitzungshärtung" umgestellt.
**Falle — Ladezyklus:** `db.php` ruft `sitzung_ablage()` **während des eigenen
Ladens**, und `sitzung_lib.php` zieht dabei `plattform_lib.php` nach (Kommentar
dort, Z. 262–274 an `fd99989`). `plattform_lib.php` liest heute
`$GLOBALS['CFG']` und bekommt mit diesem Paket `konfig()`. Deshalb gilt:
`konfig_lib.php` lädt **nichts**, und kein Weg von `sitzung_lib.php` oder
`plattform_lib.php` darf `db.php` erreichen — `require_once` verdeckte den
Zyklus, statt ihn zu melden.
**Abnahme:** Z01 `session_start(` **9 → 1**; Z02 `sitzung_ablage(` Aufrufe
**2 → 1**; Z03 `config.php`-Lesestellen **7 → 1**; Z04 `$CFG`-Zugriffe
**44 → 0**; Sitzungshärtung 0 Befunde mit neuer Selbstprobe; **alle Wege
einmal gegangen** (Liste aus Konzept Sitzungsablage, Abschnitt 3): Anmeldung
bis Tagesübersicht ohne Schleife, Passwort-Reset, Abmelden, Handbuch ·
Rechtstext · Notfallblatt angemeldet mit angemeldetem Kopf, `install.php` und
`wiederherstellen.php` im Prüfstand; **F-ZE-2:** anonymer Abruf von
`hilfe.php` → **0** `Set-Cookie`, **0** neue Dateien in `.sitzungen/`;
angemeldet → Kopf wie zuvor; Cookie-Parameter je Art gegen die Tabelle in 1.3
gemessen (4 Arten × 3 Attribute = **12 Zellen, 0 Abweichungen**);
Abmelde-Probe, Ratenprobe, Kopplungsprobe grün; `konfig()` ohne `config.php`
liefert die Vorgabe (Prüfstand `install.php`); nach `config_eintrag_schreiben()`
zeigt dieselbe Anfrage den neuen Wert.

### AP3 — API-Eingang und Flash (E-ZE-15, -16; F-ZE-5)

`api_eingang()` in `db.php`; 11 der 12 Rumpf-Lesestellen unter `api/` und alle
Methodenprüfungen dort ziehen um. `flash_*()` in `session_lib.php`.
**Abnahme:** Z05 `php://input` unter `api/` **12 → 1** (`csp_bericht.php`);
Z06 `'error' => 'method'` unter `api/` **→ 0** außerhalb des Helfers; Z07
„kein JSON-Objekt" mit `payload`/`format` von Hand unter `api/` **→ 0**; Z08
`post_max_size`-Hinweis **3 → 1**; Z09 `$_SESSION['flash…']` außerhalb
`session_lib.php` **22 → 0**; je Endpunkt unter `api/` eine Anfrage mit
falscher Methode (405), leerem Rumpf (400 `leer`), Nicht-JSON (400 `format`) —
**21 Dateien, Zahl der Zellen im Prüfdokument**; Geräte-Endpunkte
**zeichengleich** (Ingestprobe, Kopplungsprobe, Android-`SenderTest`
unverändert grün); Kreisläufe 0 unerklärt; die drei Flash-Seiten im Browser
je einmal (Meldung erscheint einmal, nach Neuladen nicht mehr).

### AP4 — Datenzugriff klein (E-ZE-04, -17, -18, -19)

`app_state_*` vervollständigt, Wrapper-Rümpfe umgestellt; virtuelles Gerät;
`einsatz_lib.php` mit `einsatz_laden()`; vier Rollenvergleiche auf `rolle_*()`;
`db_hat_*()` öffentlich, sechs Dateien ziehen um, `_hat_*` rufen sie.
**Abnahme:** Z10 `app_state`-SQL außerhalb `db.php` und `migration_lib.php`
**27 → 1** (`job_aufraeumen()`); Z11 `'manual-` außerhalb `db.php` **7 → 0**;
Z12 Einsatz-laden-SQL außerhalb `einsatz_lib.php` **13 → 1**
(`trash_restore_mission()`); Z13 Rollenvergleich von Hand außerhalb `db.php`
**4 → 0**; Z14 `information_schema` außerhalb `migration_lib.php` und `db.php`
**9 → Zahl der Nicht-Existenz-Abfragen** (erwartet 3 bis 4: `komplett_lib.php`
2 und `speicher_lib.php` 1 fragen Spaltenlisten und Größen, nicht Existenz;
`nachbearbeitung_lib.php` fragt einmal `is_nullable` — beim Start nachmessen
und entscheiden, ob dafür ein vierter Helfer lohnt, R83); Z15 `information_schema` in `migration_lib.php`
**Decke 57**; der Beleg zu `$pdo` je Stelle (E-ZE-17) steht im Konzept;
Demo-Reset, Jobpause, Kontolöschung im Prüfstand je einmal; Kreisläufe 0
unerklärt; Komplettprobe und Migrationsregister grün.

### AP5 — Transaktion und Kindtabellen (E-ZE-20, -21)

Zuerst die Bauformen der 33 Stellen auszählen und hier eintragen; dann
`db_transaktion()` und der Umbau; dann die vier `einsatz_*_ersetzen()`.
**Abnahme:** Z16 `beginTransaction(` außerhalb `db.php` **33 → Zahl der
namentlichen Ausnahmen** (≤ 8, H-ZE-4); Z17 Kindtabellen-Anweisungen
außerhalb `einsatz_lib.php` und `migration_lib.php` **30 → 0**; Probe für
`db_transaktion()`: Fehler in der inneren Funktion rollt die **äußere** nicht
zurück, wenn sie fremd ist, und wirft weiter (Fallzahl im Prüfdokument);
Ingestprobe, Spurprobe, GPX-Probe grün; Kreisläufe csv und edbak 0 unerklärt;
**Messstand:** Laufzeit von `ingest.php` innerhalb der Streuung der letzten
drei Läufe (Zahlen im Prüfdokument); Schneiden und Rückgängig im Browser je
einmal.

### AP6 — Spaltenregister `missions` (E-ZE-22)

Register und `mf_spalten()`; sieben SQL-Listen erzeugt; vier Abbildungen mit
Vollständigkeitsprobe; Stufe-1-Schritt „Spaltenregister — `schema.sql` gegen
`mf_missions_register()`".
**Abnahme:** Z18 Handlisten **11 → ≤ 4**, jede verbleibende mit Probe;
erzeugte gegen eingefrorene Listen: **7 von 7 gleich in Menge und
Reihenfolge**; `schema.sql` minus Register **= 0 Spalten**; **Byte-Vergleich
am Referenzdatensatz:** Export (csv und JSON) und Konto-Backup vor und nach
dem Paket — **gleiche Prüfsumme**, abgesehen von Zeitstempeln der Erzeugung
(die Felder sind im Prüfdokument benannt und werden vor dem Vergleich
ausgeblendet); Kreisläufe 0 unerklärt; Einspielen eines **alten** Backups (mit
Schlüssel `manual`) gelingt; `Export-Format.md`, `Backup-Format.md`: **0
geänderte Zeilen**.

### AP7 — Zeit und Zahl in PHP (E-ZE-23; F-ZE-1, F-ZE-3)

`format_lib.php`; Umzug der Aufrufer.
**Abnahme:** Z19 `edbak_groesse_text(` **43 → 0**, `function groesse_text`
**= 1**; Z20 relative Zeit von Hand **2 → 1**; Z21 `number_format(` in
deutscher Form außerhalb `format_lib.php` **26 → 0**; Z22 Byte-Division für
die Anzeige **18 → namentliche Ausnahmen** (beim Start auszählen: Anzeige
gegen Rechnung); Z23 Prozent von Hand **10 → 0**; Z24 ISO-UTC schreiben
**20 → 1**; Z25 ISO-UTC lesen **9 → 1**; Z26 Datumsformat-Literale
**67 → 0** außerhalb `format_lib.php`; Z27 `date(` ohne Zeitstempel außerhalb
`install.php` **4 → 0**; **Bilderlauf 8 Breiten 0/0/0** (kein Pixel bewegt
sich — der Beleg für F-ZE-3; ausgenommen ist allein die zeitabhängige
Altersangabe auf Betrieb → Updates, E-ZE-23 — im Bilderlauf maskiert); `heute_lokal()` mit gestellter Serverzeitzone
(UTC gegen `Europe/Berlin` um 23:30 UTC): **2 Fälle, 2 richtig**;
`zeit_relativ()` gegen `status_alter()` an den Grenzen 0 · 89 · 90 · 5 399 ·
5 400 · 172 799 · 172 800 s: **7 von 7 gleich**; gegen die entfallene Kopie
aus `betrieb_updates.php` weichen **genau** die zwei in E-ZE-23 benannten
Bereiche ab.

### AP8 — JavaScript

`assets/api.js` (`EdApi.postJson(url, daten)`, `EdApi.postForm(url, felder)`,
beide liefern `{ ok, status, daten, meldung }` und hängen das CSRF-Token
selbst an), in der Immer-Liste von `ui.php`; `assets/format.js` (`EdFormat`);
`EdHtml.meldung(ton, text)`; `EdKarte.anlegen(el, o)`; ein Rahmen
`EdPat.listeLaden()` für die vier Seiten.
**Abnahme:** Z28 Literal `'X-CSRF'` im JS **15 → 1**; Z29 Feld `csrf` von Hand
**5 → 0**; Z34 Formatierer-Definitionen außerhalb `assets/format.js`
**12 (+2) → 0**; Z35 Seiten mit eigener `L.map(`-Präambel **4 → 0**; Z36
`EdPat.entschluessleListe(` in Seiten **4 → 0**; Z37 Meldungs-Markup im JS
**Startwert aus AP1 → 0**; **CSP-Schritt 0** (kein Inline-Skript ohne Nonce);
Klickprobe und Eingabe-Probe grün; Bilderlauf 0/0/0; im Browser je einmal:
Export, Import-Vorschau, Schneiden, Schlüssel erneuern, Rückfrage,
Schlüsselblatt-Prüfung, Entsperren mit KDF-Anhebung; ein provozierter
Serverfehler zeigt auf allen sechs JSON-POST-Seiten **denselben** Satzbau.

### AP9 — Nr. 57: eine Einsatztabelle (E-ZE-01, -08; F-ZE-6)

`index.php` bezieht Kopf, Zeilen, Sortierung und Sortierblatt aus
`EdMissionTable`; die eigene Zeilenerzeugung, die drei Spaltenlisten und das
eigene Sortierblatt entfallen. `api/range.php` und `api/suchindex.php`
beziehen die bedingten Spalten aus dem Register (AP6).
**Erster Prüffall — `cap_gate`:** NEF-Tag ohne Fähigkeit → **keine** Winden-
und Bergwachtspalte; Tag mit `winch` → Spalte da; dasselbe in Suche und
Zeitraum gegen den Referenzdatensatz (Fälle und Zahlen im Prüfdokument).
**Abnahme:** `tr.innerHTML`-Zeilenerzeugung in `index.php` **1 → 0**;
Spaltenlisten für Einsatztabellen **4 (6 mit den beiden API-Dateien) → 1**;
Sortierblatt-Erzeuger **3 → 1**; **Bildvergleich Tagesübersicht** vorher und
nachher, 8 Breiten — erwartet sind **genau** die Abweichungen aus Nr. 57:
Kopf „Sekundärtransport" (64 → 42 px), Ausrichtung von Alter und Beginn,
Hakenreihenfolge; **jede weitere Abweichung ist ein Befund**; Suche und
Zeitraum **0/0/0**; Spaltensatz je Seite unverändert (F-ZE-6: Zählung der
Köpfe vorher = nachher); Gleichstände: sechs gleichwertige Zeilen, zweiter
Klick → Reihenfolge bleibt 1–6 (E-ZE-01); Kacheln unverändert.

### AP10 — Abschluss und Übergabe an 10c

Stufe-1-Schritt „Zentralisierung — hält jede Sache ihre eine Stelle?"
(`tools/zaehlung/`, eingetragen in `tools/kettenaufrufe`); `CLAUDE.md`:
Regeln „neue Migrationen nur über `db_hat_*()`", „Konfiguration nur über
`konfig()`", „Sitzung nur über `sitzung_starten()`", R83 mit Verweis auf das
Register; `docs/Technik.md`: Abschnitt „Eine Stelle je Sache" mit der Tabelle
aus 2.2/E-ZE-11; Abschnitt 6 dieses Konzepts gegen den gebauten Stand
nachgezogen; Prüfdokument fertig; Einschübe (Abschnitt 8, 9) an die
einspielende Instanz.
**Abnahme:** Register **alle Zeilen ≤ Decke**, Schritt in Stufe 1 grün und
einmal **absichtlich rot** gesehen (eine zweite Stelle eingebaut, Schritt
schlägt an — Selbstprobe der Kette); Kettenaufrufe 0/0; **Übergabezahl
`error_log(`** genannt und gegen 77 erklärt; Wortliste, Vollständigkeit,
Kontraste, CSP, Migrationsregister, `php -l` wie immer mit Zahl.

---

## 4. Register — Startwerte und Decken

Startwerte aus der Messung vom 20.09.2026 (Verfahren 1.0); AP1 misst nach.
„Decke" ist der Wert nach dem genannten Paket und danach die Grenze in Stufe 1.

```yaml
# kennung: [beschreibung, sicht, start, decke, paket]
Z01: ["session_start( Aufrufe", php_ohne_zeichenketten, 9, 1, AP2]
Z02: ["sitzung_ablage( Aufrufe (nach Schritt 16)", php_ohne_zeichenketten, 2, 1, AP2]
Z03: ["config.php lesend einbinden", php_mit_zeichenketten, 7, 1, AP2]
Z04: ["$CFG / global $CFG / $GLOBALS['CFG']", php_mit_zeichenketten, 44, 0, AP2]
Z05: ["php://input unter api/", php_mit_zeichenketten, 12, 1, AP3]
Z06: ["'error' => 'method' unter api/ ausserhalb api_eingang()", php_mit_zeichenketten, 17, 0, AP3]
Z07: ["kein-JSON-Objekt von Hand (payload|format) unter api/", php_mit_zeichenketten, "AP1 misst", 0, AP3]
Z08: ["post_max_size-Hinweis unter api/", php_mit_zeichenketten, 3, 1, AP3]
Z09: ["$_SESSION['flash…'] ausserhalb session_lib.php", php_mit_zeichenketten, 22, 0, AP3]
Z10: ["app_state-SQL ausserhalb db.php und migration_lib.php", php_mit_zeichenketten, 27, 1, AP4]
Z11: ["Literal 'manual- ausserhalb db.php", php_mit_zeichenketten, 7, 0, AP4]
Z12: ["Einsatz laden mit Besitz per SQL ausserhalb einsatz_lib.php", php_mit_zeichenketten, 13, 1, AP4]
Z13: ["Rollenvergleich von Hand ausserhalb db.php", php_mit_zeichenketten, 4, 0, AP4]
Z14: ["information_schema ausserhalb migration_lib.php und db.php", php_mit_zeichenketten, 9, "AP4 nennt (erwartet 3 bis 4)", AP4]
Z15: ["information_schema in migration_lib.php", php_mit_zeichenketten, 57, 57, AP4]
Z16: ["beginTransaction( ausserhalb db.php", php_ohne_zeichenketten, 33, "AP5 nennt (<= 8)", AP5]
Z17: ["INSERT/DELETE auf Kindtabellen ausserhalb einsatz_lib.php, migration_lib.php", php_mit_zeichenketten, 30, 0, AP5]
Z18: ["Handlisten missions (Anweisung mit >= 10 Spaltennamen)", php_mit_zeichenketten, 11, 4, AP6]
Z19: ["edbak_groesse_text( Aufrufe", php_ohne_zeichenketten, 43, 0, AP7]
Z20: ["relative Zeit von Hand ('vor ' . …)", php_mit_zeichenketten, 2, 1, AP7]
Z21: ["number_format( deutsche Form ausserhalb format_lib.php", php_mit_zeichenketten, 26, 0, AP7]
Z22: ["Byte-Division fuer die Anzeige", php_ohne_zeichenketten, 18, "AP7 nennt", AP7]
Z23: ["Prozent von Hand", php_ohne_zeichenketten, 10, 0, AP7]
Z24: ["gmdate('Y-m-d\\TH:i:s\\Z' …", php_mit_zeichenketten, 20, 1, AP7]
Z25: ["str_replace(['T','Z'] … (ISO lesen)", php_mit_zeichenketten, 9, 1, AP7]
Z26: ["Datumsformat-Literale mit d.m. ausserhalb format_lib.php", php_mit_zeichenketten, 67, 0, AP7]
Z27: ["date('…') ohne Zeitstempel ausserhalb install.php", php_mit_zeichenketten, 4, 0, AP7]
Z28: ["JS: Literal 'X-CSRF'", js_und_inline, 15, 1, AP8]
Z29: ["JS: Feld csrf von Hand", js_und_inline, 5, 0, AP8]
Z30: ["Gegenprobe P1: INSERT INTO password_resets ausserhalb konto_lib.php", php_mit_zeichenketten, 0, 0, AP1]
Z31: ["Gegenprobe P1: 'base_url' lesend ausserhalb konfig_lib/instanz_lib/install/config.example", php_mit_zeichenketten, 0, 0, AP1]
Z32: ["Gegenprobe P1: mail_rahmen( ausserhalb mail_lib.php, instanz_lib.php", php_ohne_zeichenketten, 0, 0, AP1]
Z33: ["Gegenprobe P3: usleep( ausserhalb ratelimit_lib.php", php_ohne_zeichenketten, 0, 0, AP1]
Z34: ["JS: Formatierer-Definitionen ausserhalb assets/format.js", js_und_inline, 14, 0, AP8]
Z35: ["JS: Seiten mit eigener L.map(-Praeambel", js_und_inline, 4, 0, AP8]
Z36: ["JS: EdPat.entschluessleListe( in Seiten", js_und_inline, 4, 0, AP8]
Z37: ["JS: Meldungs-Markup von Hand", js_und_inline, "AP1 misst", 0, AP8]
Z38: ["Uebergabe 10c: error_log( Aufrufe", php_ohne_zeichenketten, 77, 77, "alle; 10c AP3 setzt <= 2"]
```

## 5. Prüfprotokoll-Soll

| Mittel | Wann | Soll |
|---|---|---|
| `tools/zaehlung/` Register | jedes AP | alle Zeilen ≤ Decke; Selbstprobe grün |
| `php -l` über `server/` | jedes AP | 0 |
| Wortliste, Vollständigkeit, Kontraste, Kettenaufrufe, CSP, Migrationsregister | jedes AP | wie vor dem Paket, mit Zahl |
| Sitzungshärtung (umgestellt) | ab AP2 | 0 Befunde; genau 1 Aufruf |
| Kreisläufe csv und edbak | AP3–AP6, AP9 | 0 unerklärt |
| Byte-Vergleich Export und Backup am Referenzdatensatz | AP6 | gleiche Prüfsumme |
| Ingest-, Kopplungs-, Raten-, Abmelde-, Mail-, Spur-, GPX-Probe | AP2–AP5 | grün wie zuvor |
| Messstand | AP5 | `ingest.php` innerhalb der Streuung |
| Bilderlauf 8 Breiten | AP7, AP8 | 0/0/0 |
| Bildvergleich Tagesübersicht | AP9 | nur die drei benannten Abweichungen |
| Android `SenderTest`, `SendeantwortTest` | AP3 | unverändert grün (Gerätevertrag) |

**Nicht prüfbar, und so gesagt:** `heute_lokal()` auf einer echten Anlage mit
abweichender Serverzeitzone (nachgestellt mit gesetzter Zeitzone im
Prüfstand); F-ZE-2 gegen echte Bots (nachgestellt mit einem Abruf ohne
Cookie).

## 6. Andockstellen für 10c — was P5c nach Schritt 15 vorfindet

Bezug: `Konzept-P5c-Rollen-Sicherheit-Betriebslage.md`, AP1–AP11. **Zusage**
heißt: Name, Datei und Signatur gelten; ändert die Umsetzung von 15 etwas
daran, steht es nach AP10 hier.

| 10c-Paket | braucht | findet vor | aus |
|---|---|---|---|
| **AP1 Banner** | Umgebungsetikett aus `config.php` | **`konfig('app.umgebung')`**; der Mail-Präfix kommt aus demselben Leser (`konfig('mail.betreff_praefix')`) — die Statuswarnung „Präfix ohne Etikett" vergleicht zwei `konfig()`-Werte | AP2 |
| | Banner auf jeder Seite | `ui_seite_start()` ist **eine** Stelle (46 Aufrufe, 43 Dateien). **Nicht darüber laufen** und einzeln zu entscheiden: `install.php`, die Störungs- und Wartungsseiten in `wartung_lib.php`, `kopfzeilen_lib.php`, die Druckseiten `betrieb_schluesselblatt.php` und `notfallblatt.php`, `gpx_lib.php` — Schritt 15 fasst diese Hüllen nicht an | Bestand |
| **AP2 Protokollseite, Archiv** | Schreibweg | `protokoll()` in `protokoll_lib.php` (13 Aufrufe, 8 Dateien) — von 15 **unberührt** | Bestand |
| | Rollengatter je Reiter | `require_admin()`/`require_betreiberin()` (`auth_guard.php`), `rolle_*()` (`db.php`); **0 Rollenvergleiche von Hand** außerhalb `db.php` | AP4 |
| | Zeile, Filter, Archivliste | `datum_zeit_text()`, `zeit_relativ()`, `zahl_text()`, `groesse_text()` aus `format_lib.php` | AP7 |
| | Archivtakt, Fristen | `app_state_lesen/-setzen/-mehrere()` | AP4 |
| | Archivlauf | `db_transaktion()` | AP5 |
| | ZIP schreiben | **R83: 10c AP2 ist der zweite Verbraucher und löst heraus.** Heute 4× `new ZipArchive` allein in `adminbackup_lib.php`; Schritt 15 baut **nichts**, nennt aber den Ort: **`zip_lib.php`** (neu, dort), `adminbackup_lib.php` zieht im selben Paket um. Registerzeile dafür legt 10c an | Bestand |
| **AP3 Fehlerprotokoll** | Zahl der umzustellenden Aufrufe | **Übergabezahl** aus AP10 (Start 77 in 32 Dateien; Schritt 15 stellt keinen um) | E-ZE-05 |
| | Zählmittel und Stufe-1-Schritt | `tools/zaehlung/`, Zeile **Z38**; 10c AP3 setzt die Decke auf **2** — Nr. 248 ist damit eine Registerzeile, kein neues Werkzeug | AP1, AP10 |
| | Behandler „früh in `db.php`" | der frühe Teil von `db.php` trägt nach AP2: `konfig_lib.php` laden, `wartung_tor()`. **Der `sitzung_ablage()`-Aufruf aus Schritt 16 steht dort nicht mehr** (E-ZE-06) | AP2 |
| | JSON-Fehler mit Kennung | `json_fehler()` (`db.php`, 18 Aufrufe, 14 Dateien) — unberührt; `api_eingang()` fängt **keine** Ausnahmen | AP3 |
| **AP4 Support-Rolle** | eine Andockstelle | `ROLLEN`, `rolle_darf_verwalten()` in `db.php` — `rolle_darf_support()` kommt daneben; **kein** Handvergleich mehr in `admin_user.php`/`admin_users.php` | AP4 |
| | „je Handlung, nicht je Seite" | Der POST-Verteiler der Verwaltungsseiten (`$_POST['action']`) ist von 15 **nicht** zentralisiert (Beifang, E-ZE-07) — 10c AP4 trifft ihn je Seite | — |
| | Meldungen nach Handlungen | `flash_setzen()`/`flash_holen()` | AP3 |
| **AP5 Zweitfaktor** | Sitzung und Tor | **`sitzung_starten('app')`** ist der einzige Weg in die Anmeldesitzung (`login.php`, `auth_guard.php`, `session_beenden()`); das Tor für Pflichtrollen dockt in `auth_guard.php` **nach** dem Sitzungsstart an, neben dem Einwilligungstor (E-P5b-15) | AP2 |
| | Geheimnis einmalig, Einstellungen | `app_state_einmalig()`, `konfig()` | AP4, AP2 |
| **AP6 Health** | Eingang ohne Sitzung | **`api_eingang(['methode' => 'GET', 'rumpf' => 'keiner'])`** aus `db.php` — lädt **kein** `auth_guard.php` | AP3 |
| | Token, Dauer, Zustand | `konfig('betrieb.health_token')`, `rate_gleiche_dauer()` (Bestand), `migrationen_ausstehend()` (Bestand), `wartung_tor()` antwortet vorher mit 503 | AP2 |
| **AP7 Betriebslage** | Zahlen und Anteile | `zahl_text()`, `prozent_text()`, `zeit_relativ()` | AP7 |
| | „je echtem Gerät" | `geraete_echt_sql($alias)`, `GERAET_VIRTUELL_MUSTER` | AP4 |
| | Migration für den Index | Regel „nur über `db_hat_index()`"; Registerzeile Z15 (Decke 57) schlägt sonst an | AP4, AP10 |
| **AP8 R39-Rest** | Migration mit Vorzählung | `db_hat_spalte()`; `migrationen_inhalt_zaehlen()` (Bestand) | AP4 |
| | Besatzung am Diensttag | `einsatz_besatzung_ersetzen()` besteht für **Einsätze**; die Diensttag-Besatzung (`day_crew`) ist von 15 nicht berührt | AP5 |
| **AP9 Aufräumen** | Datum-Zeit-Trenner | **übernimmt die Vereinheitlichung aus F-ZE-3**: nach 15 steht der Trenner an **einer** Stelle (`datum_zeit_text()`), 11 Aufrufer übergeben ` · ` — die Entscheidung ist danach eine Zeile | AP7 |
| | JS von `einstellungen.php` | sechs JSON-POSTs laufen über `EdApi`; Textarbeit dort trifft kein Fehlerschema mehr je Knopf | AP8 |
| **AP10 Bounce** | Betreff-Schlüssel, Postfach | `mail_einreihen()` ist **eine** Stelle (`mail_lib.php`; 19 Aufrufe, 14 Dateien) — der Schlüssel `[NAdoku #<id>]` entsteht dort; `konfig('mail.postfach')` | Bestand, AP2 |
| **AP11 Abschluss** | — | Das Register läuft in Stufe 1: Neuer 10c-Code, der eine zweite Stelle baut, macht die Kette rot. **Decken werden nicht angehoben, ohne dass es im Konzept steht** | AP10 |

**Andockstellen zu Schritt 16** (gemergt, `main` `fd99989`): `sitzung_lib.php`
besteht mit `sitzung_ablage()`; die zwei Aufrufstellen (`db.php` Z. 586,
`install.php` Z. 144) entfallen in AP2. FF-2 besteht an diesem Stand fort: Bis
AP2 legt jeder anonyme Abruf einer lesenden Seite eine Datei in `.sitzungen/`
an — auch die Messschritte und der Bilderlauf der Kette. Wer dort Dateien
zählt, rechnet das ein.

## 7. Gesammelte Fehlerfunde (K4)

| # | Fund | Wohin |
|---|---|---|
| FF-1 | **„Heute" hängt an der `php.ini`.** `date_default_timezone_set` kommt in `server/` nicht vor; `date('Y-m-d')` bestimmt in `diensttag_neu.php` die Vorgabe des neuen Diensttags und das `max` des Datumsfelds, in `einsatz_form.php` das `max` des Geburtsdatums, in `betrieb_statistik.php` einen Dateinamen. Auf einem Server in UTC ist zwischen 0 und 2 Uhr Ortszeit „heute" gestern | AP7, F-ZE-1 |
| FF-2 | **Drei öffentliche Seiten starten für jeden Besucher eine Sitzung** (`doku_seite.php`, `rechtstext_seite.php`, `notfallblatt.php`: `@session_start()` ohne Cookie-Bedingung). Das Konzept Sitzungsablage, Abschnitt 1, sagt „nur wenn ein Cookie da ist" — gemessen ist das nicht so. Mit Schritt 16 landet jede dieser Sitzungen als Datei in `.sitzungen/` | AP2, F-ZE-2; Hinweis an Schritt 16 (Abschnitt 6) |
| FF-3 | **R83 ist in P5a AP8–AP10 nicht angekommen:** `edbak_groesse_text()` liegt weiter in `adminbackup_lib.php` (43 Aufrufe, 10 Dateien), die relative Zeit steht zweimal — mit abweichender Rundung | AP7 |
| FF-4 | **10b hat einen zweiten CSRF-Transport im JS gebaut** (Formularfeld, 5× in 3 Skripten) neben den 15 Kopf-Stellen | AP8 |
| FF-5 | **Drei Trenner zwischen Datum und Zeit** (Leerzeichen 25, ` · ` 11, Komma 1) | AP7 benennt, 10c AP9 entscheidet (F-ZE-3) |
| FF-6 | Nr. 202 nennt für den Log-Helfer 39 Aufrufe in 19 Dateien, für den Sitzungsstart 7 in drei Varianten — gemessen 77 in 32 und 9 in vier. Der Eintrag wird mit den Zahlen aus Abschnitt 1 berichtigt | Abschnitt 9 |

## 8. Einschub Rahmenplan (Fassung und Wortlaut vergibt die einspielende Instanz)

### 8.0 Lage der Steuerungsdokumente — gemessen am 20.09.2026

**Bei der Messung** stand `claude/fervent-dirac-xirsqw` bei `46f727c`; der
Einschub vom 20.09.2026 war dort noch nicht eingespielt, und die Fassung 84,
die sein Auftrag nannte, hatte Kette II/AP2 schon vergeben.
**Stand `52eb540` (20.09.2026, später am Tag):** Der Einschub ist eingespielt
— als **Fassung 85** —, Backlog **241–249 reserviert und angelegt**, nächste
freie **250**, die drei Konzeptdateien eingecheckt; der Rahmenplan steht bei
**Fassung 95**, Fahrplanzeile 16 bei „erledigt und gemergt". **Dieses Konzept
liegt noch auf keinem Zweig**; Zeile 15 nennt es noch nicht.

### 8.1 Fahrplanzeile 15

- Konzept: „liegt vor: `docs/konzepte/Konzept-Zentralisierung.md` (Fable,
  20.09.2026, E-ZE-01 bis -24, F-ZE-1 bis -6, AP1–AP10, kein Fable-Schritt)".
- Inhalt: sechs Pakete → „zehn Arbeitspakete; Paket 1 aus Nr. 202 ist durch
  (Gegenprobe), Paket 6 bleibt Beifang, **dazu Nr. 57** und der
  Konfigurationsleser; **ohne** Log-Helfer (10c AP3) und **ohne** Umbau
  gelaufener Migrationen (E-ZE-04)".
- Voraussetzung: „Kette II bis M1; Schritt 16 gemergt — **erfüllt** (PR #61/#62)".
- Status: „Konzept freigegeben (20.09.2026), Umsetzung offen".

### 8.2 Schritt-15-Block (Abschnitt 3 des Rahmenplans)

Die Zahlen „Stand `main` 16.09.2026" durch den Verweis auf Abschnitt 1 dieses
Konzepts ersetzen; den Satz „Reihenfolge = Reihenfolge" der sechs Pakete durch
die AP-Folge aus 3.0. R83 bleibt unverändert; **kein neues R** — E-ZE-04 ist
eine Ausnahme von der Weisung „alles wird angegangen" und steht als Satz im
Block, nicht als Programmentscheidung.

### 8.3 Fahrplanzeile 10c

Voraussetzung bleibt „Schritte 16 und 15"; ergänzen: „Andockstellen in
`Konzept-Zentralisierung.md` Abschnitt 6; **AP3 baut kein Zählmittel**
(E-ZE-03)".

## 9. Einschub Backlog und Nachbarkonzepte (Nummern vergibt die einspielende Instanz)

**Bestehende Einträge:**

- **Nr. 202:** Zahlen nach Abschnitt 1 berichtigen (FF-6); Zuordnung je Paket
  „Schritt 15 AP…"; Paket 1 „durch — Gegenprobe in AP1"; Paket 3, Zeile
  Log-Helfer: „77 Aufrufe in 32 Dateien, 10c AP3".
- **Nr. 57:** Zuordnung „Schritt 15 AP9"; ergänzen: E-ZE-01 (Gleichstände,
  `sortable`) und F-ZE-6 (Spaltensatz je Seite).
- **Nr. 248** (P5c, Prüfmittel): Text „das Zählmittel entsteht mit AP3" →
  „das Zählmittel besteht seit Schritt 15 AP1 (`tools/zaehlung/`); 10c AP3
  setzt die Registerzeile Z38 auf Decke 2".
- **Nr. 210** (Deadlocks in `ingest.php`): Vermerk „`db_transaktion()` aus
  Schritt 15 AP5 fasst `ingest.php` nicht an — die Wiederholung bei Deadlock
  gehört hierher".

**Neu (ohne Nummer; die einspielende Instanz trägt im Backlog-Kopf eine
Spanne für Schritt 15 ein — diese vier Einträge plus Reserve für Funde der
Umsetzung —, wie bei 241–249):**

- *Umleiten nach POST auf den Admin-Seiten, die heute nicht umleiten*
  (`post_ende()` aus Nr. 202, F-ZE-4) — ändert Wege, deshalb nicht in
  Schritt 15. Zuordnung: Schritt 17.
- *Cookie-Attribut `secure` der Sitzung ist in zwei Arten HTTPS-abhängig, in
  zwei fest* (E-ZE-12) — nach AP2 eine Tabelle in `sitzung_lib.php`.
  Zuordnung: Schritt 18, zusammen mit der Sitzungsbindung.
- *Gelaufene Migrationen fragen das Schema 57× von Hand* (E-ZE-04) —
  erledigt sich mit dem neuen Migrationsregister in P8 (R66); bis dahin
  Decke 57 im Register.
- *Datum-Zeit-Trenner vereinheitlichen* (F-ZE-3, FF-5). Zuordnung: 10c AP9.

**Ein Satz im freigegebenen P5c-Konzept** (E-ZE-03), AP3, Abnahme: „das
Zählmittel entsteht hier und geht in Stufe 1 (Nr. 248)" → „das Zählmittel
besteht seit Schritt 15 (`tools/zaehlung/`, Zeile Z38); AP3 setzt dessen Decke
auf 2 (Nr. 248)". Dazu in E-P5c-12 der Halbsatz „(Paket 3 aus Nr. 202, hier
erledigt)" → unverändert richtig.

**Ein Satz im Konzept Sitzungsablage**, Abschnitt 1: „die drei letzten mit
`@session_start()`, nur wenn ein Cookie da ist" → „… mit `@session_start()`,
**ohne** Cookie-Bedingung (nachgemessen 20.09.2026; die Bedingung kommt mit
Schritt 15 AP2, F-ZE-2)". Ist das Konzept Sitzungsablage nach `CLAUDE.md` 7
schon gelöscht, gehört der Satz in die Erledigt-Zeile von Schritt 16.
