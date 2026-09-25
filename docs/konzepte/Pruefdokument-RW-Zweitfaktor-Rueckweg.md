# Prüfdokument RW — der Rückweg beim Zweitfaktor

Gehört zu `Konzept-RW-Zweitfaktor-Rueckweg.md` (im P5c-Konzept AP5b). Nach
`CLAUDE.md` 7: was maschinell geprüft wurde (Mittel **und** Zahl), was im
Browser, was nicht und warum, und eine abhakbare Prüfliste — je Punkt der
Bedienweg, das erwartete Ergebnis und woran ein Scheitern zu erkennen ist.
Angelegt mit RW-01; jedes Paket schreibt seinen Abschnitt fort, RW-04
schließt es ab (24.09.2026). **Stand: AP5b gebaut; offen sind die fünf
Prüfpunkte in 2 und das, was 0 nennt.**

## 0. Was nicht geprüft werden konnte

*Steht vorn, weil es das ist, was noch jemand tun muss.*

| Was | Warum nicht | Wann dann |
|---|---|---|
| **Die Rückweg-Prüfung beim Hoster** (RW-01) | Örtlich gemessen unter PHP 8.4.19 und — von Hand — 8.3.33, beide mit OpenSSL 3.x. Welchen Weg der Hoster nimmt (`openssl` oder reines PHP) und wie lange er braucht, sagt nur die Statuszeile auf der Anlage. | P-RW-01 (Produktiv), Staging nach dem Merge |
| **Die Plattformmatrix der Hauptstufe** (RW-01, Nr. 300) | Der Prüfstand fährt in `haupt` die Schemaprobe über vier Datenbanken unter PHP 8.4, aber kein PHP 8.3 und keinen Kreislauf je Datenbank. Von Hand gefahren: die Migration über Betrieb → Updates und die Rückwegprobe unter PHP 8.3.33 (1); in RW-04 dazu Rückweg (beide Teile), Wartungs-, Zweitfaktor- und Rollenprobe unter 8.3.33 (1c). **Nicht gefahren:** ein Kreislauf `edbak` gegen MariaDB 10.6, MySQL 8.0 und 8.4.0 — RW-01 legt drei leere Spalten an, die kein Konto-Backup trägt (E-RW-09). | Backlog Nr. 300 |
| ~~**Das Paar in Firefox und WebKit** (RW-02)~~ | **Seit RW-04 gemessen:** `probe.mjs --motor firefox` und `--motor webkit` legen das Paar mit `rueckweg.js` an und signieren damit, je **23 / 0** (1c). Es bleiben die Motoren von Playwright (Firefox 142, WebKit 26) — nicht das Safari eines iPhones | P-RW-03 mit dem Browser der Betreiberin |
| **Ein Kreislauf `edbak` in ein Zielkonto mit Paar** (RW-02, Abnahme „Paar unverändert") | Gemessen ist die Konstruktion: Kontopaket und Freigabe haben **0** Zeichenketten mit `rw_` (Rückwegprobe A8, Gegenprobe rot), `edbak_restore()` schreibt `users` nur mit benannten Spalten. Der Kreislauf im Prüfstand legt sein Konto frisch an; ob dessen Paar vor dem Einspielen schon entstanden ist, sagt er nicht, und ihn dafür umzubauen gehört nicht in RW. | Kreislauf-Umbau, wenn er je ansteht |
| **Die Sperre nach zehn Fehlversuchen am Endpunkt** (RW-02) | Zehn falsche Token sperrten die Adresse der Sandbox für jede Anmeldung (F-RW-16). Gemessen: Ein Fehlversuch zählt unter dem Kontomerkmal im Topf `login`, eine gesetzte Sperre dort hält auch das richtige Token auf (429). Die Leiter misst die Ratenprobe. | — |
| **Die Rückwegprobe gegen Staging** (RW-03, Stufe 2) | Der Schritt steht in `auslieferung.yml`, `kettenaufrufe` hält den Aufruf gegen die Schnittstelle (0 ungeprüft) — gelaufen ist er nicht: Stufe 2 fährt erst nach dem Merge, und `STAGING_TOTP` muss dann stehen. Welchen Prüfweg phpseclib beim Hoster nimmt, sagt erst dieser Lauf. | P-RW-02 |
| **Der Rückweg in Firefox und WebKit — im Tor** (RW-03, RW-04) | Von Hand in RW-04 gemessen (1c: Firefox 23 / 0, WebKit 23 / 0); die Kette und der Prüfstand fahren weiter nur Chromium. **WebKit braucht dafür einen `php -S` mit mehreren Arbeitern** — mit einem blieb es in 2 von 4 Läufen beim zweiten Anmelden hängen (F-RW-23) | Nr. 300, Nr. 301; P-RW-03 |
| **Zwei Verbindungsabbrüche der Bedienprobe im ersten Prüfstand von RW-04** | `admin-protokoll-reiter` und `-zeilen` kamen nicht an die Seite (`ERR_TOO_MANY_RETRIES`), obwohl der Server mit 200 antwortete; einzeln 3 von 3 grün. Die Verbindungsschicht der Sandbox (TLS-Vorschaltung vor einem einfädigen `php -S`) ist die naheliegende Stelle — belegt ist das nicht, anders als bei WebKit (F-RW-23) | tritt es wieder auf: `php -S` mit mehreren Arbeitern, wie bei F-RW-23 (Backlog Nr. 301) |
| **Ein einmaliger Fehlschlag des Bedienwegs `einstellungen-profil-rueckweg`** (RW-03) | Einmal rot in sieben Läufen, im selben Lauf mit den beiden Zweitfaktor-Wegen; die Stelle ist verloren. Danach nicht wieder aufgetreten: sechs Läufe von Hand und **drei Prüfstände** (RW-03 zweimal, das Aufnehmen von BR; Bedienprobe je **55 / 55**). Kein Befund mit Ursache, aber auch kein Grund, ihn wegzulassen. | — (tritt er wieder auf, steht der Schritt im Bericht) |
| **Die Sperre von „10 falschen Signaturen" über HTTP** (RW-03) | Gemessen an der Bibliothek (`probe.php` B4): nach **5** falschen die Sperre im Topf `totp` — der Topf erlaubt fünf, nicht zehn; das Konzept nannte zehn aus dem Topf `login`. Über HTTP geht der Browser nie mit einer falschen Signatur, weil er vorher im Browser scheitert; handgebaute POSTs misst B8 für „nicht angeboten" (400), die echte Signatur und das gesperrte Konto — die Sperre nicht. | — |

## 1. Messprotokoll RW-01 (24.09.2026, Web 20.43.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| **Rückwegprobe, Teil A** (neu) | `bash tools/proben/proben.sh rueckweg` | **27 / 0.** A1 Selbsttest mit erzwungener Engine: OpenSSL angenommen (rund 20 ms), reines PHP angenommen **185 ms** (Sollbereich 150–400), ohne Zwang derselbe Weg wie `rw_pruefen()` (openssl). A2 sechs Signaturfälle: **1 angenommen, 5 abgewiesen** (fremd, verändert, zweckfremd `passwort-reset`, fremdes Konto, 63 Byte); Signatur 64 Byte. A3 P-384 und Ed25519: `rw_oeffentlich_pruefen()` weist ab, ihre gültige Signatur nimmt `rw_pruefen()` nicht an, `rw_oeffentlich_laden()` lädt sie nicht (**2 / 2** + **2 / 2**); Unsinn in der Form abgewiesen. A4 `RW_OEFFENTLICH_RE` nimmt den 124-Zeichen-SPKI, `RW_PRIVAT_RE` nimmt `edk1:` und weist `edka1:` ab. A5 Statuszeile **3 / 3** Lagen (openssl blau, reines PHP blau, Fehlschlag orange „abgeschaltet"). A6 Marke: zweimal gefragt, **1** Lauf; fremde Marke → einmal neu; gemerkter Fehlschlag schaltet ab. Die Marke steht danach wie vorher |
| **Gegenprobe** | Kurvenprüfung in `rw_oeffentlich_laden()` herausgenommen, danach zurück | **3 rot** — P-384 angenommen, beide Kurvenfälle geladen. Zurück: 27 / 0 |
| **Der Browser-Vektor** | Chromium 141 über WebCrypto (`generateKey` P-256, `exportKey('spki')`, `sign` SHA-256) gegen `https://127.0.0.1:8443` | SPKI 124 Zeichen, Signatur 64 Byte; von phpseclib über openssl **und** in reinem PHP angenommen — derselbe Befund wie F-RW-02, jetzt als Konstante im Selbsttest |
| **Wo die Zeit steckt** (F-RW-10) | zehnmal laden, zehnmal prüfen | Laden des SPKI **18,8 ms**, `verify()` über openssl **0,97 ms** |
| **Vor `update.php`** (Abnahme) | Spalten `rw_*` entfernt, Registereintrag und `migration_tor_hash` gelöscht; Anmeldung der BetreiberIn über `sitzung.py` | erste angemeldete Anfrage schaltet die Wartung ein; `login.php` ohne Sitzung mit „Wartungsmodus seit … — automatisch geschaltet"; `betrieb_updates.php` **200**, Migration genannt; `betrieb_status.php` **200** mit „Rückweg-Prüfung … prüft" (die Zeile braucht die Spalten nicht); `betrieb_statistik.php` **200**; `index.php` **503**. `php server/update.php` → „Erfolgreich angewendet", drei Spalten da, **Wartung bleibt an** |
| **Unter PHP 8.3.33** (Nr. 300, von Hand) | `hochfahren.sh --php 8.3`; dieselbe Rücknahme, dann `betrieb_updates.php` mit `action=migrate`; die Probe im Abbild `nadoku-php83` mit eingehängtem Repositorium | GET **200**, Migration genannt; POST → Registereintrag `applied`, drei Spalten, **Wartung an**. Probe **27 / 0** unter 8.3.33 mit OpenSSL 3.5.7, reines PHP **168 ms**; Status **200** mit der Zeile; im Protokoll des Behälters **0** Zeilen mit `Fatal`, `Warning`, `Deprecated` oder `Notice`. Zurück: Behälter entfernt |
| Quelltext | `bash tools/quelltext/pruefen.sh alle` | **9 von 9**, Textprobe 0 Treffer außerhalb der Ausnahmen |
| Register | `php tools/zaehlung/zaehlen.php` | **40 Zeilen, 0 über der Decke** |
| Stufenregel | `python3 tools/pruefstand/auswahl.py --selbstprobe`, `--abdeckung` | **35 Lagen, 0 Fehlschläge**; 0 Dateien ohne Muster |
| **Prüfstand** | `hochfahren.sh --neu`, `pruefen.sh` | *steht in der Commit-Nachricht von `RW-01`* |

## 1a. Messprotokoll RW-02 (24.09.2026, Web 20.44.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| **Bedienweg** `einstellungen-profil-rueckweg` (neu) | `node tools/bedienprobe/probe.mjs --nur einstellungen-profil-rueckweg` | **1 / 1.** Konto über Einladung und `pw_handling.php` im Browser; Anmeldung → Paar **0 → 1** (`RW_STAND` auf der Seite „fehlt"), Protokoll `rueckweg_angelegt` **+1**, Mails **+0**, privater Teil `edk1:`. Zweite Anmeldung: Wert **unverändert**, `RW_STAND` „da". Endpunkt aus der Seite über `EdApi`: ohne Token **403**, falsches Token mit `ersetzen` **403**, richtiges Token bei vorhandenem Paar **409**, P-384 **400** — Paar unverändert. Topf `login`: **2** Fehlversuche unter dem Kontomerkmal, gestellte Sperre → **429**. Zweitfaktor eingeschaltet (`tools/zweitfaktor/pruefkonto.php`): Karte „eingerichtet", Knopf **1**. Dialog mit falschem Passwort: „Das Passwort ist nicht korrekt.", **nichts gesendet**; mit richtigem: neuer Wert, `rueckweg_erneuert` **+1**, Mail **+1**, Meldung „Rückweg erneuert" auf der Seite. Demo-Konto: `RW_STAND` „demo", `rueckweg.js` **0** ausgeliefert, **gültiges** Token ohne und mit `ersetzen` **403 / 403**, **0** Paare |
| **Gegenprobe F-RW-14** | derselbe Weg vor dem stillen Auflösen in `unlock.js` | **rot:** Paar **0 → 0**, `vorhanden` **200** (der Weg legte selbst an). Einzeln nachgestellt: Fach auf `index.php` belegt, erst `suche.php` legte an |
| **Gegenprobe Demo-Sperre** | `demo_ist_demo()` im Endpunkt ausgeschaltet, danach zurück | **rot:** Demo **200 / 200**, **1** Paar — danach geräumt (`demo_zweitfaktor_leeren()`), Endpunkt zurück, Weg wieder grün |
| **Rückwegprobe** | `bash tools/proben/proben.sh rueckweg` | **33 / 0.** Neu A7: `rw_zustand()` **4 / 4** Lagen — „fehlt", „da" mit Datum, „spalten" gegen eine SQLite-Datenbank ohne die Spalten, „demo". A8: **0** Zeichenketten mit `rw_` in `adminbackup_lib.php` und `backup_lib.php`; Gegenprobe (eine eingefügte) **1 rot** |
| **Zweitfaktorprobe** | `bash tools/proben/proben.sh zweitfaktor` | **45 / 0.** Teil 6 neu: Demo-Reset leert das Paar, **3 / 3** Spalten NULL; Gegenprobe (Block ausgeschaltet) **0 / 3, rot** |
| **Rollenprobe** | `bash tools/proben/proben.sh rollen` | **296 / 0.** Matrixzeile `POST api/rueckweg_anlegen.php`: **4 × durch** (die JSON-Ablehnung `{"error":"csrf"}` zählt jetzt als Token-Ablehnung); Wirkung: jede Rolle legt mit gültigem Formular- und Anmelde-Token ab, **4 / 4** mit 200 und Paar |
| **Karte und Dialog im Bild** | Playwright, Chromium 141, Konto mit Zweitfaktor | 1440 px: Überlauf **0**, Knöpfe **36 / 36 / 36** px · 376 px: Überlauf **0**, Knöpfe **44 / 44 / 44** px; **0** Seitenfehler; gegen M-RW-01 Bild 4 (a) abgeglichen: Zeile, Plakette, Knopfreihe |
| Quelltext | `bash tools/quelltext/pruefen.sh alle` | **9 von 9**, Textprobe 0 Treffer außerhalb der Ausnahmen |
| Register | `php tools/zaehlung/zaehlen.php` | **40 Zeilen, 0 über der Decke** |
| Stufenregel | `auswahl.py --selbstprobe`, `--abdeckung` | **35 Lagen, 0 Fehlschläge**; 0 Dateien ohne Muster |
| **Prüfstand** | `hochfahren.sh --neu`, `pruefen.sh` | *steht in der Commit-Nachricht von `RW-02`* |

## 1b. Messprotokoll RW-03 (24.09.2026, Web 20.45.0)

| Mittel | Aufruf | Zahl |
|---|---|---|
| **Rückwegprobe, Browser** (neu, `probe.mjs`) | `node tools/proben/rueckweg/probe.mjs` (örtlich) | **23 / 0.** NutzerIn: Schlüssel von der Seite gelesen (24 Zeichen), Paar entsteht beim Anmelden (`RW_STAND` da), Zweitfaktor über die Profilkarte, Code-Schritt mit Verweis, Tippfehler („O") benannt, **fremder Zettel 0 Anfragen**, richtiger Zettel → Erfolgskarte (200), Startseite 200, `suche.php` löst das Vormerkfach und hat den Datenschlüssel (F-RW-20), Profil „Einrichten"; Datenbank: Codes 0, Protokoll `totp_zurueckgesetzt` mit `weg=schluessel` +1, Mail +1. BetreiberIn: nach dem Anmelden ins Tor, nach dem Tor Paar da, Rückweg → **302** auf `zweitfaktor.php` mit „Zweitfaktor zurückgesetzt." oben; Datenbank wie oben (das Tor legt sofort ein neues Geheimnis an, ohne `totp_seit`). 0 Skriptfehler |
| **Rückwegprobe, Server** | `php tools/proben/rueckweg/probe.php` | **49 / 0** (Teil A 33, Teil B 16). B1 echt → angenommen, Herausforderung verbraucht — und der Zweitfaktor **noch an**, Protokoll 0: Die Bibliothek schaltet nichts ab (F-RW-21). B2 dieselbe Signatur → abgewiesen. B3 abgelaufen → abgewiesen, **nicht gezählt**, nächste Herausforderung neu. B4 fremd, verändert, zweckfremd → je **+1** im Topf `totp`; weiter bis zur Sperre (**5** falsche), danach **auch die echte** abgewiesen, Zweitfaktor bleibt. B5 `pat_key_check` (roh, Base64, Hex→Base64) und leer → **4 / 4** abgewiesen. B6 ohne Paar, Selbsttest gescheitert → **2 / 2** nicht angeboten. **B7 Abzug-Gegenprobe: 73 Versuche, 0 Erfolge**; der Inhaltsschlüssel selbst öffnet `rw_privat` (Gegenprobe des Öffners). B8 über HTTP: Verweis mit Paar **1**, ohne **0**; POST mit Signatur an ein Konto ohne Paar **400**; **echte Signatur → 200, Erfolgskarte, Zweitfaktor aus, Codes 0, Protokoll +1, Mail +1**; **dieselbe an einem gesperrten Konto → die Seite des Tors, Zweitfaktor an, Codes 2, Protokoll 0, Mail 0** (F-RW-21) |
| **Gegenprobe alte Fassung** | Prüfzweig nimmt `pat_key_check` an | **rot:** B5 FEHLT (48 / 1); zurück 49 / 0 |
| **Gegenprobe Verbrauch** | Herausforderung nicht verbraucht | **rot:** B1 (verbraucht) und B2 FEHLEN (47 / 2); zurück 49 / 0 |
| **Gegenprobe F-RW-21** | `totp_abschalten()` in `login.php` wieder VOR das Tor gestellt | **rot:** B8 gesperrtes Konto FEHLT — an=0, 0 Codes, Protokoll **1**, Mail **0**: der stille Rücksetzer (48 / 1); zurück 49 / 0 |
| **Wartungsprobe** | `bash tools/proben/proben.sh wartung` | **67 / 0.** Fall 18: „Aufruf Z. 406 hinter totp; Aufruf Z. 487 hinter totp; Aufruf Z. 730 hinter login/salt/login_ip". Im ersten Prüfstand dieses Pakets **rot** — „Aufruf Z. 478 hinter nichts" (F-RW-21) |
| **Handlauf mit Bildern** | Chromium, Konto NutzerIn und BetreiberIn | Schlüsselschritt, „passt nicht", Erfolgskarte und Tor mit Meldung **gegen M-RW-01 Bild 1–3 abgeglichen**; zuerst rot (F-RW-19), danach wie im Bild |
| **Integritätswache** | `wache.py --selbstprobe`; `wache.py http://127.0.0.1:8080` | **43 / 0**; gegen die Sandbox: 138 Dateien gleich, **4** Inline-Blöcke und **4** Skripte gleich, 1 Formular gleich — kein Unterschied |
| **Mail der Verwaltung** | Text aus `totp_zurueckgesetzt` ohne `weg`, gegen die Fassung aus `f82e277` (Arbeitsbaum daneben) | **byteweise gleich** (`cmp`) |
| Kettenaufrufe | `python3 tools/kettenaufrufe/pruefen.py`, `--probe` | **0 Befunde, 0 ungeprüft**; 13 / 13 |
| Quelltext, Register | `pruefen.sh alle`, `zaehlen.php` | **9 von 9** (die Textprobe fand „basis" in einer neuen Technik-Zeile — umformuliert), **40 / 0 über der Decke** |
| Berührte Proben | `proben.sh zweitfaktor`, `rollen`, `mail`, `raten` | **45 / 0**, **296 / 0**, **51 / 0**, **50 / 0** (Zweitfaktor und Raten nach F-RW-21 noch einmal; `probe.mjs` danach wieder **23 / 0**) |
| Berührte Bedienwege | `probe.mjs --nur zweitfaktor-einrichten,einstellungen-profil-zweitfaktor,einstellungen-profil-rueckweg` | **6 Läufe grün**; **ein** Lauf davor war im Weg `einstellungen-profil-rueckweg` rot — welcher Schritt, ist nicht festgehalten (die Ausgabe war beim Kürzen abgeschnitten). Siehe 0 |
| **Prüfstand** | `hochfahren.sh --neu`, `pruefen.sh` | *steht in der Commit-Nachricht von `RW-03`* |

## 1c. Messprotokoll RW-04 (24.09.2026, ohne Versionsstufe)

*Nach dem Aufnehmen von `main` (Konzept BR, `3576a97`). Was `haupt` nicht selbst fährt (Nr. 300), von Hand — vorn in 0 genannt.*

| Mittel | Aufruf | Zahl |
|---|---|---|
| **Rückweg in Firefox** (neu: `--motor`) | `node tools/proben/rueckweg/probe.mjs --motor firefox` | zuerst **rot** — „RW_STAND fehlt" (F-RW-22, ein Fehler der Probe); danach **23 / 0** |
| **Rückweg in WebKit** | `… --motor webkit` | gegen `php -S` mit einem Arbeiter **2 von 4** Läufen grün, zwei mit 90 s ohne Navigation beim zweiten Anmelden; mit `PHP_CLI_SERVER_WORKERS=4` **3 von 3**, je **23 / 0** (F-RW-23) |
| Rückweg in Chromium, nach F-RW-22 | `… --motor chromium` | **23 / 0** |
| **Gegenprobe F-RW-22** | dasselbe Konto in Firefox angemeldet, fünf Sekunden gewartet, ohne Probe | `api/rueckweg_anlegen.php` **200**, `RW_STAND` „da" — die Anwendung legt an, die alte Probe lud zu früh neu |
| **Unter PHP 8.3.33** (Nr. 300) | `hochfahren.sh --php 8.3`; die Proben im Abbild `nadoku-php83` mit eingehängtem Repositorium | Kopfzeile `X-Powered-By: PHP/8.3.33`; Rückweg `probe.php` **49 / 0** (reines PHP **168 ms**, openssl 12 ms), `probe.mjs` gegen die 8.3-Anlage **23 / 0**, Wartung **67 / 0**, Zweitfaktor **45 / 0**, Rollen **296 / 0**; im Protokoll des Behälters **0** Zeilen mit Fatal, Warning, Deprecated oder Notice. Zurück: Behälter entfernt |
| Einschübe | Skript über die Anker aus Konzept RW 8 und 9 | P5c-Konzept 15 Stellen, Rahmenplan 4, Backlog 4 — jeder Anker genau einmal gefunden; Backlog **0** doppelte Nummern |
| Nummer 301 | gegen `origin/main` `ba2ec57` (höchste 318) und die Spannen im Kopf von `Backlog.md` | frei; aus der Spanne des P5c-Zweigs (294–303) |
| **Erster Prüfstand RW-04** | `hochfahren.sh --neu`, `pruefen.sh` | **rot, 48 / 1:** die Bedienprobe 53 / 55 — `admin-protokoll-reiter` und `admin-protokoll-zeilen` „nicht gefahren": `net::ERR_TOO_MANY_RETRIES` bzw. eine Navigation, die in `chrome-error://` endete. Im Protokoll des PHP-Servers steht dieselbe Anfrage mit **200**; die Anwendung hat geantwortet, die Verbindung davor ist gerissen. RW-04 ändert nur `docs/` und `tools/`, und derselbe Anwendungsstand lief im Prüfstand des Merge **55 / 55**. Danach einzeln **3 von 3** grün (je 2 / 2). **Ursache nicht gefunden** — siehe 0 |
| **Prüfstand** | `hochfahren.sh --neu`, `pruefen.sh`, zweiter Lauf | *steht in der Commit-Nachricht von `RW-04`* |

## 2. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-RW-01 | **Die Rückweg-Prüfung auf Produktiv** (RW-01) | nach dem Tag und `update.php`: Betrieb → Status, Karte „Server" | Zeile „Rückweg-Prüfung", blau „prüft", mit Weg (openssl oder reines PHP) und Dauer | orange „abgeschaltet" (Runbook `Technik.md` 7); oder die Zeile fehlt (Fassung nicht ausgeliefert) | offen |
| P-RW-02 | **Die Rückwegprobe in Stufe 2** (RW-03) | nach dem Merge: Actions → „Auslieferung" → Job „Prüfung Stufe 2", Schritt „Rückwegprobe gegen Staging" | grün, am Ende „23 ok, 0 fehlen" (auf Staging ohne die Datenbankzeilen: **21**) | Schritt rot; oder „NICHT GEMESSEN" (fehlt `STAGING_TOTP` oder das Prüfkonto) | offen |
| P-RW-03 | **Der Rückweg mit dem eigenen Konto auf Staging** (RW-03) | eigenes Konto auf Staging, einmal angemeldet (das Paar entsteht), Zweitfaktor einschalten, die Codes **nicht** benutzen; abmelden, anmelden, „Gerät und Codes verloren? Wiederherstellungsschlüssel verwenden", den Schlüssel vom Notfallblatt eingeben, „Zweitfaktor zurücksetzen" | Erfolgskarte „Zweitfaktor zurückgesetzt" (Rolle user) bzw. das Tor mit der Meldung oben (Pflichtrolle); Mail „Zweitfaktor zurückgesetzt" mit dem Satz vom Wiederherstellungsschlüssel; im Protokoll „Zweitfaktor zurückgesetzt" | keine Erfolgskarte; der Verweis fehlt (dann Betrieb → Status, „Rückweg-Prüfung", und Profil → Karte „Zweitfaktor"); keine Mail | offen |
| P-RW-04 | **Dasselbe mit einem falschen Zettel** (RW-03) | wie P-RW-03, aber den Wiederherstellungsschlüssel eines ANDEREN Kontos eingeben | „passt aber nicht zu diesem Konto", der Zweitfaktor bleibt an | eine andere Meldung; oder der Zweitfaktor ist danach aus | offen |
| P-RW-05 | **Das Demo-Konto bleibt ohne Paar** (RW-02, E-RW-15) | nach dem Merge auf Staging: Verwaltung → Demo-Konto → „Zurücksetzen" (hier stand bis P5c/AP11 „Demo → Auf Standard zurücksetzen"); danach im Demo anmelden, eine Seite mit Einsätzen öffnen; dann als BetreiberIn Verwaltung → Protokoll, Reiter Verwaltung, nach „Rückweg" suchen | der Reset läuft ohne Fehler; im Protokoll **kein** „Rückweg eingerichtet" für das Demo-Konto | eine Fehlermeldung beim Reset; oder ein Eintrag „Rückweg eingerichtet" mit dem Demo-Konto | offen |

## 3. Grenzen der benutzten Prüfmittel

**Aus RW-03:**

- **„Ein Abzug genügt nicht" ist Konstruktion plus Messung** (Konzept RW
  5.2, seit dem Abschluss von P5c in `Technik.md` 4.99q). B7 zeigt, dass kein Wert des Abzugs `rw_privat` öffnet — 73 Versuche,
  als Hex und als Rohbytes. Dass es keine ANDERE Art gibt, aus dem Abzug zu
  signieren, sagt die Konstruktion: Signieren braucht den privaten Teil, und
  der liegt nur unter dem Inhaltsschlüssel. Die Tabelle dazu steht in `Technik.md` 4.99q („Was ein Abzug enthält“).
- **Die Probe baut ihr Konto für Teil B selbst**, mit derselben Verpackung
  wie `crypto.js`. Dass die Verpackung der Anwendung dieselbe ist, zeigt der
  Browser-Teil (`probe.mjs`), der mit der echten Krypto der Seite durchgeht.
- **`probe.mjs` gegen Staging hat keine Datenbank** — dort stehen nur die
  Aussagen der Seiten (Profilkarte „Einrichten", Erfolgskarte, 302).

**Aus RW-02:**

- **Der Bedienweg belegt Chromium.** Die Kette von `rueckweg.js` —
  `generateKey`, `exportKey('pkcs8')`, `encrypt` — läuft dort. Firefox und
  WebKit hat RW-04 nachgeholt: `probe.mjs --motor` legt das Paar dort mit
  `rueckweg.js` an, je 23 / 0 (Abschnitt 1). Der Bedienweg selbst läuft
  weiter nur in Chromium (Nr. 300).
- **„Kein Paar im Demo" ist Konstruktion plus Messung.** Gemessen sind der
  Endpunkt (403 mit gültigem Token) und die Seite (`RW_STAND` „demo", kein
  `rueckweg.js`); dass keine DRITTE Stelle je ein Paar für das Demo-Konto
  schreibt, sagt der Quelltext: Ein Paar SCHREIBT nur der Endpunkt; die
  zweite Stelle mit `SET rw_oeffentlich`, `demo_zweitfaktor_leeren()`, leert.
- **Die Sperre am Endpunkt ist gestellt** (F-RW-16) — gemessen ist, dass der
  Endpunkt fragt und zählt, nicht dass die Leiter nach zehn greift.

**Aus RW-01:**

- **Der Selbsttest prüft einen Vektor, keinen Browser.** Er belegt, dass
  phpseclib auf dieser Anlage eine Signatur aus Chromium annimmt — nicht,
  dass Firefox oder WebKit dasselbe Format liefern. Das hat F-RW-02 gemessen
  (drei Motoren, 12 / 0); nachgemessen wird es mit dem echten Weg in RW-03.
- **Die Dauer ist die dieser Maschine.** Der Sollbereich 150–400 ms für
  reines PHP ist eine Auskunft; gefordert ist nur „unter einer Sekunde". Ein
  langsamer Hoster zeigt seine Zahl in der Statuszeile.
- **„Abgewiesen" ist Konstruktion plus Messung.** Die Probe zeigt, dass fünf
  Arten falscher Signaturen scheitern — nicht, dass es keine sechste gibt,
  die durchginge. Die Konstruktion dazu steht in `Technik.md` 4.99q.

## 4. Soll gegen Ist (Konzept RW 5.1)

| Prüfung | Soll | Ist | Mittel, Paket |
|---|---|---|---|
| Selbsttest beide Engines | 2 / 2, Dauer je Weg | **2 / 2** — openssl rund 20 ms, reines PHP **185 ms** (8.4) und **168 ms** (8.3) | `probe.php` A1, RW-01, RW-04 |
| Sechs Signaturfälle | 1 angenommen, 5 abgewiesen | **1 / 5** | A2, RW-01 |
| Fremde Kurve, fremdes Verfahren | 2 / 2 abgewiesen | **2 / 2** (P-384, Ed25519) | A3, RW-01 |
| Statuszeile drei Lagen | 3 / 3 | **3 / 3** | A5, RW-01 |
| Schemaprobe, Migrationsregister | 4 × 0, 0 | **4 × 19 / 0**, **0** | Prüfstand, zuletzt nach dem Aufnehmen von BR |
| Anlegeweg | 8 / 8 | **8 / 8** in einem Weg: richtiges Token, ohne 403, falsches 403, 409, 400, Demo 403 / 403, Erneuern, Mail +1 | Bedienweg, RW-02 |
| `RW_STAND` vier Lagen | 4 / 4 (Rollenprobe, Markup) | **4 / 4** — gemessen in `probe.php` A7 (`rw_zustand()`) und im Markup des Bedienwegs, **nicht** in der Rollenprobe: Die liest Zugänge, keine Seitenwerte | A7, Bedienweg |
| Kontopaket ohne die Spalten; Kreislauf lässt Paar stehen | 0-mal, unverändert | **0** Zeichenketten mit `rw_` (Gegenprobe rot); der Kreislauf ist **nicht** gemessen — siehe 0 | A8, RW-02 |
| Demo: kein Paar, Reset leert | 3 / 3 NULL, Endpunkt 403 | **3 / 3**, **403 / 403** mit gültigem Token | Zweitfaktorprobe Teil 6, Bedienweg |
| Der echte Rückweg im Browser | 4 / 4 | **4 / 4** (NutzerIn, BetreiberIn, fremder Zettel mit 0 Anfragen, Tippfehler benannt) — `probe.mjs` **23 / 0** in Chromium, Firefox und WebKit | RW-03, RW-04 |
| Teil B gegen den Login-Weg | 1 / 5, N / 0, rot | **1 / 5**; **73 / 0**; alte Fassung **rot** (48 / 1) | `probe.php` B, RW-03 |
| Ratenschutz, Ablauf, Wiederholung | 1 / 1, 1 / 1, 1 / 1 | **1 / 1** (Sperre nach **5**, nicht 10 — der Topf `totp` erlaubt fünf; siehe 0), **1 / 1**, **1 / 1** | B2–B4 |
| Weg nicht angeboten | 2 / 2 | **2 / 2**, dazu über HTTP: Verweis 0, POST **400** | B6, B8 |
| Bilderlauf, Bedienprobe, Textprobe, Register, Kontraste | 0 Code-Schritt-Bilder, grün, 0 neue, 0 über der Decke, 0 verfehlt | **544** Bilder, **0** mit Code- oder Schlüsselschritt; **55 / 55**; **0** neue; **40 / 0**; **25 / 0** | Prüfstand |
| Rückweg gegen Staging | grün | **nicht gemessen** — Stufe 2 läuft erst nach dem Merge und braucht `STAGING_TOTP` | P-RW-02 |

Dazu, **ohne Soll in 5.1**, gemessen: das Abschalten erst hinter dem Tor — ein gesperrtes Konto behält seinen Zweitfaktor, 0 Protokoll, 0 Mail; die alte Reihenfolge rot (F-RW-21, B8).
