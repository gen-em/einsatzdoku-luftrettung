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
| **Das Paar, der Weg am Code-Schritt, der echte Browser** | Kommen mit RW-02 und RW-03. | dort |

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

## 2. Prüfliste

| Nr. | Punkt | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-RW-01 | **Die Rückweg-Prüfung auf Produktiv** (RW-01) | nach dem Tag und `update.php`: Betrieb → Status, Karte „Server" | Zeile „Rückweg-Prüfung", blau „prüft", mit Weg (openssl oder reines PHP) und Dauer | orange „abgeschaltet" (Runbook `Technik.md` 7); oder die Zeile fehlt (Fassung nicht ausgeliefert) | offen |

## 3. Grenzen der benutzten Prüfmittel

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
