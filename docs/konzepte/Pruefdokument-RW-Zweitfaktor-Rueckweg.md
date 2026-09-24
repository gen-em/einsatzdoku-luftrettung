# Prüfdokument RW — der Rückweg beim Zweitfaktor

Gehört zu `Konzept-RW-Zweitfaktor-Rueckweg.md` (im P5c-Konzept AP5b). Nach
`CLAUDE.md` 7: was maschinell geprüft wurde (Mittel **und** Zahl), was im
Browser, was nicht und warum, und eine abhakbare Prüfliste — je Punkt der
Bedienweg, das erwartete Ergebnis und woran ein Scheitern zu erkennen ist.
Angelegt mit RW-01; jedes Paket schreibt seinen Abschnitt fort, RW-04
schließt es ab.

## 0. Was nicht geprüft werden konnte

*Steht vorn, weil es das ist, was noch jemand tun muss.*

| Was | Warum nicht | Wann dann |
|---|---|---|
| **Die Rückweg-Prüfung beim Hoster** (RW-01) | Örtlich gemessen unter PHP 8.4.19 und — von Hand — 8.3.33, beide mit OpenSSL 3.x. Welchen Weg der Hoster nimmt (`openssl` oder reines PHP) und wie lange er braucht, sagt nur die Statuszeile auf der Anlage. | P-RW-01 (Produktiv), Staging nach dem Merge |
| **Die Plattformmatrix der Hauptstufe** (RW-01, Nr. 300) | Der Prüfstand fährt in `haupt` die Schemaprobe über vier Datenbanken unter PHP 8.4, aber kein PHP 8.3 und keinen Kreislauf je Datenbank. Von Hand gefahren: die Migration über Betrieb → Updates und die Rückwegprobe unter PHP 8.3.33 (1). **Nicht gefahren:** ein Kreislauf `edbak` gegen MariaDB 10.6, MySQL 8.0 und 8.4.0 — RW-01 legt drei leere Spalten an, die kein Konto-Backup trägt (E-RW-09). | Backlog Nr. 300 |
| **Das Paar in Firefox und WebKit** (RW-02) | Der Bedienweg läuft in Chromium 141. Dass Firefox und WebKit ein P-256-Paar erzeugen, PKCS8 ausführen und im IEEE-Format signieren, hat F-RW-02 in der Vorbereitung gemessen (drei Motoren, 12 / 0) — nicht mit `rueckweg.js` selbst. | RW-03 (`probe.mjs`), P-RW-03 auf Staging |
| **Ein Kreislauf `edbak` in ein Zielkonto mit Paar** (RW-02, Abnahme „Paar unverändert") | Gemessen ist die Konstruktion: Kontopaket und Freigabe haben **0** Zeichenketten mit `rw_` (Rückwegprobe A8, Gegenprobe rot), `edbak_restore()` schreibt `users` nur mit benannten Spalten. Der Kreislauf im Prüfstand legt sein Konto frisch an; ob dessen Paar vor dem Einspielen schon entstanden ist, sagt er nicht, und ihn dafür umzubauen gehört nicht in RW. | Kreislauf-Umbau, wenn er je ansteht |
| **Die Sperre nach zehn Fehlversuchen am Endpunkt** (RW-02) | Zehn falsche Token sperrten die Adresse der Sandbox für jede Anmeldung (F-RW-16). Gemessen: Ein Fehlversuch zählt unter dem Kontomerkmal im Topf `login`, eine gesetzte Sperre dort hält auch das richtige Token auf (429). Die Leiter misst die Ratenprobe. | — |
| **Der Weg am Code-Schritt** | Kommt mit RW-03. | dort |

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

## 2. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-RW-01 | **Die Rückweg-Prüfung auf Produktiv** (RW-01) | nach dem Tag und `update.php`: Betrieb → Status, Karte „Server" | Zeile „Rückweg-Prüfung", blau „prüft", mit Weg (openssl oder reines PHP) und Dauer | orange „abgeschaltet" (Runbook `Technik.md` 7); oder die Zeile fehlt (Fassung nicht ausgeliefert) | offen |
| P-RW-05 | **Das Demo-Konto bleibt ohne Paar** (RW-02, E-RW-15) | nach dem Merge auf Staging: Demo → „Auf Standard zurücksetzen"; danach im Demo anmelden, eine Seite mit Einsätzen öffnen; dann als BetreiberIn Verwaltung → Protokoll, Reiter Verwaltung, nach „Rückweg" suchen | der Reset läuft ohne Fehler; im Protokoll **kein** „Rückweg eingerichtet" für das Demo-Konto | eine Fehlermeldung beim Reset; oder ein Eintrag „Rückweg eingerichtet" mit dem Demo-Konto | offen |

## 3. Grenzen der benutzten Prüfmittel

**Aus RW-02:**

- **Der Bedienweg belegt Chromium.** Die Kette von `rueckweg.js` —
  `generateKey`, `exportKey('pkcs8')`, `encrypt` — läuft dort. Für Firefox
  und WebKit steht die Messung der Vorbereitung (F-RW-02), nicht dieser Weg.
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
  die durchginge. Die Konstruktion dazu steht im Konzept (5.2).
