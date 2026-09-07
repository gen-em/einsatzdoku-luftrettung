# Prüfdokument — Sofortpaket Sicherheit (Rahmenplan Schritt 9a, R78)

**Stand:** 07.09.2026 · **Web 15.6.0** und **Android 0.14.0** · Zweig
`claude/sofortpaket-sicherheit-1t70p1` · beide Teile gebaut und geprüft,
Merge auf `main` **offen**.

Dieses Dokument beantwortet die Frage „**was muss ich noch tun?**" — nicht die
Frage „ist es belegt?"; die beantworten die Commit-Nachrichten und der
Changelog. Es steht neben der Spezifikation
`docs/konzepte/Vorbereitung-Sicherheitspaket.md`.

---

## 0. Was **nicht** geprüft werden konnte — und warum

Das steht am Anfang, nicht in einer Fußnote.

| Was | Warum nicht | Wer es prüfen muss |
|---|---|---|
| **Der Wortlaut der Hinweismail** beim E-Mail-Wechsel (Nr. 128) | Im Prüfcontainer ist **kein SMTP eingerichtet**. Belegt ist nur, dass der Versand **versucht** wird und sein Fehlschlag den Wechsel nicht zurückrollt — die Protokollzeile `Adresswechsel: Hinweismail an die alte Adresse ging nicht weg` steht im Serverprotokoll | Auftraggeber, Prüfliste P-7 |
| **Die Integritätswache gegen die Produktivinstallation** (Nr. 140) | Es gibt hier keine. Gemessen ist sie gegen die **lokale** Installation (112 Dateien) und gegen eine künstliche Manipulation | Auftraggeber, P-9 — beim ersten Lauf der Action |
| **Ein GPX aus einem echten Gerät** (Nr. 130) | Im Container liegt kein Gerätemitschnitt. Geprüft ist gegen erzeugte Dateien und den Referenzexport | Auftraggeber, P-4 |
| **Die Uhr selbst** am Ersetzfenster (Nr. 134) | Kein Gerät. Geprüft ist `ingest.php` über echtes HTTP, nicht das Verhalten der Uhr auf `kept_points` | Auftraggeber, P-6 |
| **`/apk/` und `/demo/` auf dem Produktivserver** (Nr. 129) | Gemessen unter einem **im Container aufgesetzten Apache** mit derselben `.htaccess`. Ob der Produktiv-Webspace `AllowOverride` erlaubt und mod_rewrite lädt, sagt nur der Produktivserver | Auftraggeber, P-3 |
| **Die Absenderprüfung mit einer echten Uhr** (Nr. 144) | Kein Data Layer mit Telefonseite im Container (`android/LIESMICH.md` 7). Geprüft ist die Entscheidung in `Uhrannahme` gegen echtes SQLite mit einer Attrappe der Knotenliste — nicht, was `connectedNodes` auf Hardware liefert | Auftraggeber, P-11 — **vor** der Verteilung der 0.14.0 an eine Uhr im Dienst |
| **Das Klartextverbot auf Android 8.0/8.1** (Nr. 142) | Kein Gerät mit API 26/27; der Emulator läuft mit API 34, wo Android Klartext ohnehin verbietet. Belegt sind die zusammengeführte Release-Manifestdatei und der Prüffall im Release-Buildtyp | Auftraggeber, P-12 — nur, falls ein altes Gerät greifbar ist |
| **Der Räumlauf nach 30 Tagen im Feld** (Nr. 114) | Die Frist ist nur im Prüfstand stellbar (`jetzt`); im Emulator vergehen keine 30 Tage | — (Robolectric belegt die Regel; am Gerät bleibt das Trennen, das dieselbe Funktion ohne Frist ruft) |
| **Das Uhr-Modul im Wear-Emulator** | Die einzige Änderung am Uhr-Modul ist die neue Methode in der gemeinsamen Datei `WearNachrichtenweg.kt`, die die Uhr nicht ruft; das Uhr-APK ist byteweise gleich groß geblieben. Ein zweiter TCG-Boot (nach vier Fehlversuchen und 715 s beim Handy) stand dazu in keinem Verhältnis | — bei der nächsten Änderung am Uhr-Modul mit |
| **Ein signiertes Release-APK** | Kein Signaturschlüssel im Container (E-S4-16); geprüft ist das unsignierte Release-APK aus `./gradlew build` | Auftraggeber beim Release |

**Eine Bemerkung zur Umgebung, weil sie für jede Zahl hier gilt:** Der
Container brachte weder Datenbank noch Android-SDK mit. Beides holt
`tools/containeraufbau/aufbau.sh` nach (MariaDB 10.11.14, Android-SDK 36);
die Anwendung selbst steht über
`tools/referenzdatensatz/einspielen/lokal_einrichten.sh` — **88 Einsätze,
16 Diensttage, 2 Geräte**. Für Nr. 129 kam ein **Apache 2.4 mit mod_ssl,
mod_rewrite und einer Durchreichung der PHP-Anfragen an den eingebauten
Server** dazu: Der PHP-Entwicklungsserver liest **keine `.htaccess`** — ohne
diesen Umweg hätte der Punkt keine Zahl.

---

## 1. Je Punkt: Mittel, Soll, Ist

| Nr. | Mittel | Soll | Ist (gemessen 07.09.2026) |
|---|---|---|---|
| **136** SP-1 | Browserlauf gegen die lokale Installation, DB-Abfrage | Anmeldung vor/nach der Anhebung, Rundenzahl steigt still | Anmeldung mit Konto auf 320 000 → `api/kdf_upgrade.php` antwortet `{"ok":true,"iter":600000}`, `users.kdf_iter` **320000 → 600000**; Inhaltsschlüssel danach weiterhin entpackbar (Prüfsumme `66303b53…`). Ableitung **298 ms** (320 000) gegen **551 ms** (600 000), Übergang **849 ms** — Median aus je 5 Läufen im Browser |
| **136** SP-2 | Browserlauf, Regelprobe, **breiter Vergleich alt gegen neu** | Mindestlänge 12, Passphrasen möglich, **nirgends schwächer als vorher** | `minlength` der Passwortfelder **12**, `EdPwQuality.MIN_LAENGE` 12, `MIN_REST` 8. „Anker-Winter-Regen-Glas" **angenommen**, „Winterurlaub2026" **abgewiesen**, „Rettung2026Notarzt" **abgewiesen**, „elfzeichen1" **abgewiesen**. Beide Fassungen über **1552 erzeugte Passwörter** gegeneinander gefahren: **454 neu durchgelassen, davon 0 mit einem Füllwort wie „abcdefgh", „aaaaaaaa", „12345678"; 0 neu abgewiesen.** 10 bekannte gute Passwörter (alle Prüfmittel) unverändert gültig |
| **136** Fund F-9a-01 | Browserlauf mit Netzverfolgung | stille Anhebung läuft beim nächsten Anmelden | **Vorher: lief nicht** — Konto auf 320 000, Anmeldung, `suche.php`, Rundenzahl unverändert, Vormerkfach verworfen. Nachher: siehe oben |
| **136** Fund F-9a-02 | Browserlauf auf `betrieb_status.php` | Wartungsseite sagt, wann der Altwert weg darf | Zeile „Schlüsselableitung" nennt jetzt „**1 Konto/Konten stehen noch unter dem Zielwert 600000**", Plakette „Übergang läuft" |
| **127** | Browserlauf **und** HTTP | ohne Token abgewiesen, mit Token angemeldet (2 von 2) | **2 von 2**: mit Token → `/index.php`; Feld aus dem Formular entfernt → abgewiesen mit „Das Formular ist abgelaufen." Dazu HTTP: ohne Feld und mit falschem Feld je 200 + dieselbe Meldung, mit richtigem Feld der gewohnte Fehlerzweig. Token vor der Anmeldung `69018804ce9d…`, danach `bf304fc953d5…` — **gewechselt** |
| **128** | Browserlauf | falsches Passwort → abgewiesen (1 von 1) | **4 von 4**: nur Name ändern, Feld leer → gespeichert · Adresse mit falschem Passwort → abgewiesen, Adresse unverändert · Adresse ohne Passwort → Seite hält an · Adresse mit richtigem Passwort → gespeichert, Mailversuch im Protokoll |
| **129** | Apache mit der echten `.htaccess` | zwei Aufrufe → 403 | **vier Aufrufe → 403** (`apk/`, `apk/<datei>.apk`, `demo/`, `demo/fixture.json.gz`); `login.php` 200, `assets/style.css` 200, `index.php` 302. Mitgemessen: `schema.sql` **403** (`FilesMatch`); `config.php` und `db.php` antworten im Rig **200**, weil dessen `ProxyPassMatch` jede `.php`-Anfrage am `FilesMatch` vorbei an den PHP-Server reicht — ein Rig-Artefakt, kein Befund an der `.htaccess`; auf dem Produktivserver mit P-3 mitprüfen (berichtigt nach Review-Fund 25 — hier stand vorher „je 403", das war für die beiden PHP-Dateien nicht gemessen, sondern angenommen) |
| **130** | `tools/gpxprobe/` Teil 8 | n Proben, 0 durch | **8 Proben, 0 durch**, saubere Datei geht durch. Am Stand davor: UTF-16LE mit DOCTYPE **ging durch, 2 Punkte**. Probe insgesamt **88 Erwartungen, 2 nicht erfüllt** (beide vorbestehend, siehe Abschnitt 2) |
| **131** | HTTP, unangemeldet, mit absichtlich falschem DB-Namen | keine Zahl, kein Fehlertext | Vorher: `Access denied for user 'nadoku'@'localhost' to database 'gibtesnicht'` und „stehen **2** Konten". Nachher: Kennung `798FF2B9`, **keiner** der Begriffe `nadoku`, `SQLSTATE`, `gibtesnicht`, `127.0.0.1`, `Unknown database` in der Antwort; volle Zeile im Protokoll |
| **133** | PHP-Probe gegen die Installation | Bauordner nach Fehlschlag weg | **1 auf 0** in beiden Fällen: Aufräumlauf ohne Fälligkeit, und ein Lauf, der wirft (Zustand `abgebrochen`, `geraeumt=true`, Protokollzeile) |
| **134** | `tools/ingestprobe/` Teil 9 | 1 angenommen, 1 abgewiesen | **1 angenommen** (Phasen ersetzt, 5 Punkte angehängt, keine `kept_*`-Felder), **1 abgewiesen** (HTTP 200 `ok`, `kept_phases` 2, `kept_points` 5, Phasen unverändert bei lat 40.0 statt gesendeter 10.0, Zeilen 10 vorher / 10 nachher, `next_seq` trotzdem 15). Neuer Einsatz entsteht weiterhin. Probe **47 Erwartungen, 0 nicht erfüllt** |
| **135** | maschinelle Einteilung + Browserlauf | Stellen vorher/nachher | **79** `json_encode()`-Aufrufe unter `server/`, davon **44 in einem `<script>`** (alle umgestellt) und **35 außerhalb** (unverändert). Gegenprobe: 0 verbliebene in einem Skriptblock, 44 `json_js()`. Wirkung mit Profilnamen `<!--<script>` auf `import.php`: vorher fehlten `KONTO_NAME`, `APP_TZ` **und** `WEB_VERSION`; nachher stehen alle drei |
| **138** | Lesen | vier Dokumente sagen dasselbe | `CLAUDE.md` 4, `README.md`, `Technik.md` 4.98, `Handbuch.md` 5 — dazu der Textbaustein in Handbuch 11.5. Keine Codeänderung, keine Versionsstufe für sich |
| **140** | Werkzeuglauf + zwei Manipulationen am laufenden System | Wartungsprobe um eine Erwartung (Abweichung erkannt) | Selbstprobe **12 Erwartungen, 0 nicht erfüllt**, davon fünfmal ausdrücklich „Abweichung erkannt" (veränderte Datei, veränderter Block, zusätzliches `<script src>`, zusätzlicher Inline-Block, fremdes `action`). Lauf gegen die Installation: **112 Dateien, 112 gleich; 1 Inline-Block, 1 externes Skript, 1 Formular gleich, nichts zu viel** → „Kein Unterschied". Gegenprobe 1, **eine** veränderte Kennung in `crypto.js`: **111 gleich, 1 abweichend**. Gegenprobe 2, Fremd-Skript über `ui_seite_ende()` eingeschleust: **1 zusätzliches Skript** gemeldet. Je Rückgabewert 1. `tools/wartungsprobe/` **12a** neu → **51 statt 50 Erwartungen, 0 nicht erfüllt** |
| **142** | Prüffälle in beiden Bauarten, zusammengeführte Manifestdatei | Ausnahme nur im Debug, Release verbietet Klartext | `ServeradresseTest` **11 Fälle je Bauart, 0 Fehlschläge, 1 übersprungen** — im Debug der Release-Fall, im Release der Debug-Fall. `build/intermediates/merged_manifest/release/…/AndroidManifest.xml` trägt `networkSecurityConfig="@xml/netzsicherheit"`, das Debug-Manifest weiterhin `netzwerk_pruefstand` |
| **114** Räumteil | Robolectric gegen echtes SQLite | nach 30 Tagen und beim Trennen weg, `dienst`-Zeilen mit | `AbgewieseneTest` **5 → 10 Fälle** (alt geräumt, jung bleibt — samt Punkt und Phase; ohne Frist alles Abgewiesene, der Rückstand nicht; laufendes bleibt; Dienstzeilen 4 → 2, die laufende bleibt; die Frist schont eine junge leere Dienstzeile), `SenderTest` **16 → 17** (36 Tage weg, 26 Tage bleibt, `geraeumt = 1`, keine Anfrage an den Server), `KopplungTest` **25** mit Zähler am Räumen: Getrennt 1, NurLokal 1, Rückstand 0 |
| **143** | Lesen | eine Zeile, die es dann gibt | Abschnitt „Warum kein Certificate Pinning" in `android/LIESMICH.md`, Verweis im Kopf von `HttpNetzweg`; keine Codeänderung |
| **144** | Robolectric gegen echtes SQLite, Attrappe der Knotenliste | Absender gegen Knoten, Zeit plausibel | `UhrannahmeTest` **12 → 19 Fälle**: bekannter Knoten ja, fremder nein, Liste `null` nein; 6 min Zukunft quittiert und nicht gewirkt, 4:59 min gewirkt; Phase 10 min vor Dienstbeginn quittiert, kein Einsatz; Dienstende vor dem Beginn beendet nichts. Dazu `:uhr:compileDebugKotlin` mit der ergänzten Klasse |
| **145** | zwei Quellen, Wrapper-Lauf | Prüfsumme eingetragen, Wrapper läuft weiter | `…bin.zip.sha256` von `services.gradle.org` = `sha256sum` des frisch geladenen Archivs (137 393 837 B) = `bd711022…f3531`; `./gradlew --version` mit der Zeile: Gradle 8.14.3 |
| **alle Android** | `./gradlew build` | 0 Lint-Fehler, 0 Fehlschläge | `./gradlew build` grün — Handy **261 Prüffälle je Bauart** (Debug und Release; vorher 247), **0 Fehlschläge**, 15 übersprungen (14 Rundlauf ohne Installation und der jeweils bauartfremde Fall aus Nr. 142); Uhr **71 Prüffälle**, 0 übersprungen; Lint **0 Fehler** (Handy 13 Warnungen, unverändert die `libs.versions.toml`-Hinweise; Uhr 0); Release-APK Handy **7 867 394 B** (+332 B gegen 0.13.0), Uhr **19 574 406 B** (unverändert); Bilderlauf 72 Bilder wie zuvor |
| **alle Android** | Emulator (Stufe II) | Änderung angesehen und bedient, mit Bildern | **erreicht, im fünften Anlauf** (Emulator 37.1.11, `android-34;default;x86_64`, `-accel off`): adbd nach 120 s, `ro.hw_timeout_multiplier=10` als Root gesetzt und Framework neu gestartet, Boot **715 s**, Prüf-APK gegen die lokale Installation **128 s**; acht Bilder — Kopplungsansicht, Code `S4Y ZPF`, im Web als Demo-Konto eingetragen und bestätigt, „Zu diesem Konto koppeln? de***@gen-em.org", „Ja, koppeln" → Dienstansicht „Gekoppelt · 127.0.0.1:8080", `devices`-Zeile 79 am Server; per `sqlite3` ein abgewiesenes Paket samt Punkt, Phase und beendeter Dienstzeile eingespielt → rote Zeile „1 Paket vom Server abgewiesen"; Einstellungen; „Gerät trennen" mit Rückfrage; „Getrennt". **Der Räumlauf am echten Android-SQLite:** nach dem Trennen `paket 0, fehlerhaft 0, punkt 0, phase 0, dienst 0` (vorher je 1), Gerät am Server gelöscht (`POST /pair.php` 200), kein Absturz im `logcat`. Davor **vier Anläufe ohne Boot** (14, 38, 22 und 12 min) — Ursache der Android-Watchdog unter TCG, Gegenmittel jetzt in `emulator.sh start` (F-SP-P-07); der Wear-Emulator für das Uhr-Modul wurde nicht gefahren (Abschnitt 0) |

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Mittel | Zahl | Bemerkung |
|---|---|---|
| `php -l` | **114 Dateien, 0 Syntaxfehler** | `server/`, `server/api/`, `tools/*/` |
| `node --check` | **32 Dateien, 0 Syntaxfehler** | `server/assets/*.js` |
| `tools/wortliste/` | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** | 87 Regeln (eine kam dazu, siehe F-SP-P-01) |
| `tools/vollstaendigkeit/` | **300 → 301 Befunde** | Der eine neue ist eine **Ellipse in einem PHP-Kommentar** (`gpx_lib.php:328`) — dieselbe Rauschklasse wie die 227 vorhandenen „Unicode-Zeichen als Symbol im Markup". Gegen den Abzweigpunkt `4b442de` gemessen, nicht gegen den lokalen `main` (der stand auf PR #27) |
| `tools/linkprobe/` | **99 Zielseiten, 132 Verweise, 0 unbekannte Abweichungen, 1 bekannt mit Nummer (151), 0 tote Zeilen** | unverändert gegenüber Web 15.5.2 |
| `tools/gpxprobe/` | **88 Erwartungen, 2 nicht erfüllt** | Beide vorbestehend: Der Referenzexport im Repositorium ist älter als die frisch eingespielte Datenbank. **Gegengeprüft am Stand vor der Änderung: 77 Erwartungen, dieselben 2** |
| `tools/ingestprobe/` | **47 Erwartungen, 0 nicht erfüllt** | vorher 39; Teil 9 neu, Zeitstempel auf `time()` umgestellt |
| `tools/wartungsprobe/` | **51 Erwartungen, 0 nicht erfüllt** | vorher 50; 12a neu |
| `tools/integritaetswache/` | **7 Erwartungen Selbstprobe, 0 nicht erfüllt** · Lauf **112/112**, Gegenprobe **1 abweichend** | neu |
| `tools/screenshots/` (Zeiger) | **112 Einzelbilder, 14 Kontaktbögen, 0 Überlauf, 0 Konsolenfehler, 0 Knöpfe falscher Höhe** | 14 berührte Seiten × 8 Breiten |
| `tools/screenshots/ --finger` | **dieselben Zahlen bei 44 px** | beide Bedienhöhen |
| Gegenprobe des Bilderlaufs | **112 Bilder, 112 verschiedene Prüfsummen, 0 Doppelte** | Die Falle aus F-P3-AQ (176 Bilder zeigten die Anmeldeseite) ist damit ausgeschlossen |
| `tools/screenshots/kontrast.py` | **21 Paare, 0 verfehlt** | `style.css` unverändert |
| `./gradlew build` (Android) | `./gradlew build` grün — Handy **261 Prüffälle je Bauart** (Debug und Release; vorher 247), **0 Fehlschläge**, 15 übersprungen (14 Rundlauf ohne Installation und der jeweils bauartfremde Fall aus Nr. 142); Uhr **71 Prüffälle**, 0 übersprungen; Lint **0 Fehler** (Handy 13 Warnungen, unverändert die `libs.versions.toml`-Hinweise; Uhr 0); Release-APK Handy **7 867 394 B** (+332 B gegen 0.13.0), Uhr **19 574 406 B** (unverändert); Bilderlauf 72 Bilder wie zuvor | beide Module, beide Bauarten, Lint mit `abortOnError` |
| `android/werkzeuge/emulator.sh` | **erreicht, im fünften Anlauf** (Emulator 37.1.11, `android-34;default;x86_64`, `-accel off`): adbd nach 120 s, `ro.hw_timeout_multiplier=10` als Root gesetzt und Framework neu gestartet, Boot **715 s**, Prüf-APK gegen die lokale Installation **128 s**; acht Bilder — Kopplungsansicht, Code `S4Y ZPF`, im Web als Demo-Konto eingetragen und bestätigt, „Zu diesem Konto koppeln? de***@gen-em.org", „Ja, koppeln" → Dienstansicht „Gekoppelt · 127.0.0.1:8080", `devices`-Zeile 79 am Server; per `sqlite3` ein abgewiesenes Paket samt Punkt, Phase und beendeter Dienstzeile eingespielt → rote Zeile „1 Paket vom Server abgewiesen"; Einstellungen; „Gerät trennen" mit Rückfrage; „Getrennt". **Der Räumlauf am echten Android-SQLite:** nach dem Trennen `paket 0, fehlerhaft 0, punkt 0, phase 0, dienst 0` (vorher je 1), Gerät am Server gelöscht (`POST /pair.php` 200), kein Absturz im `logcat`. Davor **vier Anläufe ohne Boot** (14, 38, 22 und 12 min) — Ursache der Android-Watchdog unter TCG, Gegenmittel jetzt in `emulator.sh start` (F-SP-P-07); der Wear-Emulator für das Uhr-Modul wurde nicht gefahren (Abschnitt 0) | ohne KVM, `-accel off` |
| `tools/wortliste/` nach dem Android-Teil | **0 Treffer außerhalb der Ausnahmen, 0 ungenutzte Ausnahmen, 0 durchgerutschte Fallen** (87 Regeln, alle fünf Bereiche einschließlich d = Android) | Bereich d (Android) eingeschlossen |

**Was der Bilderlauf gemessen hat**, damit die Zahl etwas bedeutet: die 14
Seiten `01-anmeldung`, `06-wiederherstellen`, `10-tagesuebersicht`,
`12-einsatzansicht`, `13-einsatzformular`, `14-zeitraum`, `15-suche`,
`30-einstellungen-profil`, `34-einstellungen-backup`, `35-import-export`,
`41-kontoseite`, `42-stammdaten-systemweit`, `43c-komplettsicherung`,
`45-betrieb-status` — je acht Breiten von 360 bis 1920 px.

---

## 3. Was im Browser geprüft wurde

Gegen die lokale Installation (`https://127.0.0.1:8443`, Apache mit TLS,
PHP-Anfragen durchgereicht), angemeldet als Admin und als Demo-Konto:

- **Nr. 136:** Anmeldung, stille Anhebung, `minlength`, die vier
  Passwortfälle, Wartungsseite.
- **Nr. 127:** Anmeldung mit und ohne Formularfeld, Tokenwechsel.
- **Nr. 128:** die vier Fälle des Profilformulars.
- **Nr. 135:** `import.php` und `einstellungen.php` mit einem bösartigen
  Profilnamen.
- **Alle Krypto-Seiten** nach der CSRF-Änderung: `index.php`, `suche.php`,
  `zeitraum.php`, `einsatz_form.php`, `import.php`,
  `einstellungen.php?t=konto`, `?t=backup` — je **CSRF vorhanden**,
  **0 Seitenfehler**.

> **Die Konsolenfehler im Bilderlauf sind Kachelabrufe.** Der Prüf-Browser
> kommt in dieser Umgebung nicht an `tile.openstreetmap.org`
> (`ERR_CONNECTION_RESET`); der Bilderlauf des Repositoriums fängt genau diese
> Abrufe ab und zählt sie nicht — deshalb steht dort 0. In meinen eigenen
> Wegwerfläufen erscheinen sie und sind **keine** Befunde.

---

## 4. Funde beim Bauen

| Kennung | Fund | Erledigt |
|---|---|---|
| **F-9a-01** | Die stille Anhebung der Rundenzahl lief **nicht beim nächsten Anmelden**. Sie ruft `api/kdf_upgrade.php` und braucht `CSRF`; `ui_krypto_bootstrap()` gab das nur auf Anfrage aus, und drei von sieben Seiten fragten. Die erste Seite ohne CSRF **verwarf das Vormerkfach** und nahm der Anhebung damit für die ganze Sitzung die Grundlage — beim nächsten Anmelden dasselbe | ja, in Nr. 136: CSRF steht immer |
| **F-9a-02** | Die Wartungsseite meldete nur **verwaiste** Rundenzahlen — den Fall, dass jemand den Altwert zu früh gestrichen hat. Die Frage davor, wann er gestrichen werden **darf**, beantwortete sie nicht; SP-1 nimmt an, sie täte es | ja, in Nr. 136 |
| **F-SP-P-01** | Die Wortliste schlug beim neuen Kasten „Uhr verloren? Sofort trennen" auf **„Garmin"** an. Hier ist die Plattform aber die Sache selbst: Für die **Wear-OS**-Uhr gilt der Satz gerade **nicht**, sie kennt weder Serveradresse noch Schlüssel. Ein gerätefreies „Uhr" wäre keine Neutralität, sondern eine falsche Warnung | ja: Ausnahme `handbuch-geraete-verlust-garmin` mit Begründung |
| **F-SP-P-02** | Die **Ingestprobe** stand auf festen März-Daten. Mit dem Ersetzfenster prüften zehn ihrer Erwartungen zweite Pakete an Datensätzen außerhalb des Fensters — ein Fall, den es im Betrieb nicht gibt | ja, in Nr. 134: Zeitstempel an `time()` |
| **F-SP-P-05** | Die erste Fassung der Integritätswache prüfte auf der Anmeldeseite nur, ob der bekannte Inline-Block **vorhanden** ist — ein **zusätzliches** Skript oder ein Formular mit fremdem `action` fiel ihr nicht auf. Gefunden beim Nachprüfen der Frage des Auftraggebers, welche Lücke „Wartungsprobe … Abweichung erkannt" schließen sollte; am laufenden System belegt (Fremd-Skript über `ui.php`: „Kein Unterschied") | ja: die ganze Menge der Skripte und Formulare wird verglichen; Selbstprobe 7 → 12 |
| **F-SP-P-06** | Das `src`-Muster der Wache brach am Anführungszeichen innerhalb von `asset('…')` ab und hielt das eine externe Skript der Quelle für unbestimmbar — jeder Ersatz für `crypto.js` wäre durchgegangen. Gefunden von der **Selbstprobe**, bevor es eingecheckt war | ja |
| **F-SP-P-04** | Die neue Anteilsregel der Passwortprüfung war an einer Stelle **schwächer** als die alte: Ein Listenwort plus Tastaturreihe („Passwortabcdefgh", „passwort2026aaaaaaaa") füllte die geforderten acht Zeichen. Gefunden beim breiten Vergleich über 1552 erzeugte Passwörter, **nicht** in den 22 handverlesenen Fällen davor | ja: `ohneReihen()` streicht Folgen mit gleichbleibendem Abstand; danach 0 solcher Fälle |
| **F-SP-P-07** | **Der Emulator bootete nicht — vier Anläufe, 14/38/22/12 min.** `adb devices` sagte `device`, `sys.boot_completed` kam nie. Ursache (`logcat`): Der Android-**Watchdog** erschießt den `system_server` nach 60 s Blockade in `systemReady` („*** GOODBYE!", SIG 9), Zygote geht mit, alles startet neu — unter TCG eine Schleife. Gegenmittel: `ro.hw_timeout_multiplier=10` als Root setzen, sobald `adbd` da ist (das Abbild ist `userdebug`; `-prop` kann nur `qemu.*`), und das Framework neu starten. Danach Boot in 715 s | ja: `emulator.sh start` tut das jetzt selbst; LIESMICH Abschnitt 7 |
| **F-SP-P-08** | `emulator.sh bild` prüfte den Fokus mit `dumpsys window windows` — auf API 34 druckt das kein `mCurrentFocus`, also **jeder** Abzug „KEIN ABZUG" | ja: `dumpsys window` |
| **F-SP-P-09** | Ein Emulator, der als Kind einer Shell läuft, stirbt mit ihr — ein gestoppter Hintergrundauftrag riss den ersten 14-Minuten-Boot mit | ja: `setsid nohup … < /dev/null &` in `emulator.sh start` |
| **F-SP-P-03** | Der Seitenbruch aus K-15 entsteht **nicht** über `</script>` — `json_encode()` schreibt `<\/script>`, ein schließendes Tag kann aus einem Wert gar nicht entstehen. Der Weg ist `<!--<script>` | in Nr. 135 gemessen und behoben |

---

### 4a. Die adversarische Gegenprüfung des Web-Teils (07.09.2026)

Ein Workflow aus 93 Agenten hat den fertigen Web-Diff aus sechs Blickwinkeln
angegriffen (29 Funde) und jeden Fund von drei unabhängigen Agenten widerlegen
lassen; fünf weitere Agenten haben die Ergebnisse gegen den heutigen Stand
reproduziert. **22 Funde halten, 7 sind widerlegt.** Die sechs zur Dokumentation
sind behoben (`bf5a506`, siehe Abschnitt 1 zu Nr. 129 und P-3). Die sechzehn
zum Code stehen **offen** — Entscheidung des Auftraggebers, ob sie vor dem Merge
als Nachbesserung je Punkt behoben werden oder als Backlog-Nummern eingetragen:

| Punkt | Fund | Schwere |
|---|---|---|
| 134 | Bei geschlossenem Fenster schreibt `dt_zeitraum_fortschreiben()` weiterhin Beginn/Ende des Diensttags um | hoch |
| 134 | Der Anker ist das vom Gerät gesendete `started_at`: eine falsch gestellte Uhr schließt das Fenster sofort, verliert Punkte und Phasen, `next_seq` wandert trotzdem (die Uhr löscht als quittiert) | hoch |
| 134 | Abschlusspaket außerhalb des Fensters: Metadaten still verworfen, kein `kept_*`; Diensttag im Papierkorb → leerer neuer Tag; `started_at` in der Zukunft → Fenster schließt nie; zweite Fensterprüfung unerreichbar | mittel / niedrig |
| 130 | **UTF-7** über die Kodierungsdeklaration umgeht die DOCTYPE-Sperre; die Probe Teil 8 prüft die Deklaration nicht; der Kommentar zur Latin-1-Abweisung gilt nur für den JSON-Direktweg | hoch / mittel / niedrig |
| 136 | Sonderzeichen zählen nicht zum Rest: Zufallspasswörter mit Sonderzeichen werden abgewiesen (12 Zeichen: 2–15 %; vorher 0 %), mit der Begründung „geläufige Wörter"; Listenwörter der Reihe nach statt längste zuerst, kurze gar nicht, Wiederentstehen nach dem Streichen | hoch / mittel |
| 136 | Statuszeile: Das Demo-Konto (alle 30 min mit 320 000 Runden eingespielt) hält „Übergang läuft" für immer; ein Satz bricht ab | mittel / niedrig |
| 140 | Wache: Selbstprobe hängt an Code-Literalen; Dateiname mit Leerzeichen bricht den Lauf ab; `<base href>`/`formaction` unbeobachtet; `data-src`; Zusatzdatei auf dem Server unsichtbar (Grenze benennen) | mittel / niedrig |

Die vollständigen Belege (Reproduktionsskripte, Zahlen) liegen im Protokoll des
Workflows, nicht im Repositorium; die Kurzfassung oben genügt, um die Behebung
zu beauftragen.

## 5. Entscheidungen, die beim Bauen gefallen sind

1. **Die Sperrliste der Passwortprüfung rechnet den Anteil statt des
   Vorkommens.** SP-2 empfiehlt Passphrasen **und** will die Liste erweitern;
   beides zusammen ging nicht, weil „Anker-Winter-Regen-Glas" an „winter"
   scheiterte. **Diese Entscheidung braucht Ihre Bestätigung** — sie ist eine
   Regeländerung, keine Zahl. Rückgängig ist sie mit einem Commit.
2. **Die Ortsnamen des eigenen Standorts kommen nicht in die Liste.** Sie
   stehen in den Stammdaten; sie an die Passwortseite auszugeben hieße, sie
   auch der **unangemeldeten** Seite auszugeben. Der Satz steht stattdessen im
   Handbuch.
3. **`kept_points` ist ein neues Feld im JSON-Vertrag.** Der Auftrag sah dort
   „nur die Nennung des Ersetzfensters" vor. Ohne das Feld wäre ein
   abgewiesenes Paket von einem übernommenen nicht zu unterscheiden — genau
   der Fehler, gegen den die übrigen `kept_*` gebaut sind.
4. **Der Bauordner wird auch bei Fehlschlag geräumt, und „Fortsetzen" fällt
   damit weg.** Der Preis ist Rechenzeit, nicht Daten.
5. **Handbuch 10 statt 12** für „Uhr verloren → sofort trennen": Das
   Gerätekapitel ist seit S8 Abschnitt 10; 12 ist der Betrieb.
6. **Nr. 153 neu**, damit der `querySelector`-Teil von Nr. 135 nicht
   unsichtbar wird.
7. **Die Knotenliste `null` gilt als fremd** (Nr. 144). Fail closed: Ohne
   Quittung liefert die Uhr nach; das kostet Zeit, keine Daten. Andersherum
   wäre die Prüfung genau dann außer Kraft, wenn etwas nicht stimmt.
8. **Unplausible Zeit: quittiert, nicht gewirkt** (Nr. 144) — dieselbe Regel
   wie für eine Phase ohne Dienst. Die Alternative (keine Quittung) hieße
   ewige Nachlieferung eines Ereignisses, das immer unplausibel bliebe.
9. **Fünf Minuten Spiel und 30 Tage Frist sind gewählt, nicht gemessen**
   (Nr. 144, 114); die Begründung steht an beiden Konstanten.
10. **`Nachrichtenweg` bleibt, wie es ist.** Die Knotenliste steht als
    Methode an `WearNachrichtenweg`, nicht in der Schnittstelle: Ihr einziger
    Aufrufer ist `HandyHorcher`, der als `WearableListenerService` den Data
    Layer ohnehin kennt. Eine Erweiterung der Schnittstelle hätte alle
    Attrappen berührt — für eine Methode, die oberhalb niemand braucht.
11. **Geräumt wird nur Abgeschlossenes** (`final = 1`, Nr. 114): Ein
    laufendes Paket, dessen Teil-Upload eine 400 bekam, wird noch
    beschrieben.
12. **Der Räumlauf hängt am Sendelauf**, nicht an einem eigenen Zeitgeber:
    Jeder Lauf ist der Augenblick, in dem die App auf den Puffer sieht, und
    es gibt mindestens einen je Dienst (den Takt).
13. **Die Release-Netzregel liegt in `src/release/`**, spiegelbildlich zur
    Debug-Regel in `src/debug/`, statt im Hauptmanifest: Je Bauart gilt genau
    eine Datei, und keine wird aus zwei zusammengeführt (Nr. 142).

---

## 6. Prüfliste für den Auftraggeber

Was nur am Gerät, am Produktivserver oder mit echtem Postfach geht. Je Punkt:
Weg, erwartetes Ergebnis, **woran ein Scheitern zu erkennen ist**.

### P-1 · Anmeldung nach der Rundenanhebung (Nr. 136) — **zuerst**
1. Nach dem Deploy an **Ihrem** Konto anmelden.
2. Die Anmeldung dauert einmalig spürbar länger (etwa doppelt).
3. Danach **Betrieb → Status**, Zeile „Schlüsselableitung".

**Erwartet:** Die Anmeldung gelingt. Nach der ersten Seite, die geschützte
Angaben braucht, steht dort „Alle Konten rechnen mit einer Rundenzahl, die
diese Fassung anbietet (600000, 320000)" — die Zahl der Konten unter dem
Zielwert sinkt mit jedem Konto, das sich anmeldet.
**Scheitern erkennt man an:** „Anmeldung fehlgeschlagen" trotz richtigem
Passwort (dann fehlt **320000** in `KDF_ITER_LISTE` — sofort melden, nichts
weiter tun), oder die Zahl unter dem Zielwert bleibt nach mehreren Anmeldungen
stehen (dann greift die stille Anhebung nicht).

### P-2 · Passwortregel im Alltag (Nr. 136)
Ein neues Passwort setzen (Profil → Passwort ändern) und dabei **eine
Passphrase aus vier Wörtern** probieren.
**Erwartet:** Sie wird angenommen, der Balken zeigt „gut" oder „stark".
**Scheitern:** Die Seite weist eine vernünftige Passphrase ab — dann ist die
Anteilsrechnung zu streng, und Entscheidung 1 aus Abschnitt 5 gehört
zurückgenommen. **Bitte auch dann melden, wenn es funktioniert** — die Regel
steht unter Ihrer Bestätigung.

### P-3 · `/apk/` und `/demo/` auf `nadoku.gen-em.org` (Nr. 129)
Im Browser aufrufen: `https://nadoku.gen-em.org/apk/` und
`https://nadoku.gen-em.org/demo/fixture.json.gz`.
Dazu `https://nadoku.gen-em.org/config.php` und `…/db.php` — die schützt die
`FilesMatch`-Regel derselben `.htaccess`, und im Prüfcontainer war sie nicht
messbar (Rig-Artefakt, Abschnitt 1).
**Erwartet:** alle **403 Forbidden**. Danach **Einstellungen → Geräte**
öffnen und eine APK herunterladen — sie muss weiterhin kommen.
**Scheitern:** 200 mit Inhalt oder Verzeichnisliste (dann wertet der Webspace
die `.htaccess` nicht aus — melden, die Sperre muss dann anders gesetzt
werden); oder der APK-Download bricht (dann greift die Regel zu weit).

### P-4 · GPX-Import aus dem echten Gerät (Nr. 130)
Eine GPX-Datei aus Ihrer Uhr oder einer anderen Software importieren
(Tagesübersicht → „···" → GPX importieren).
**Erwartet:** Sie geht durch wie bisher.
**Scheitern:** „Die Datei ist nicht in UTF-8 kodiert" oder „enthält ein
Nullbyte". Dann bitte **die Datei aufheben und melden** — dann schreibt Ihr
Gerät eine andere Kodierung, und die Grenze gehört überdacht.

### P-5 · Profil: E-Mail-Wechsel (Nr. 128)
Im Profil den **Namen** ändern (Passwortfeld leer) → speichern. Danach die
**E-Mail-Adresse** ändern, Passwortfeld erst leer, dann falsch, dann richtig.
**Erwartet:** Name geht ohne Passwort; Adresse ohne/mit falschem Passwort wird
abgewiesen und **bleibt unverändert**; mit richtigem Passwort wird sie
gespeichert.
**Scheitern:** Die Adresse ändert sich ohne Passwort — dann greift die Prüfung
nicht.

### P-6 · Uhr-Kopplung und Ersetzfenster (Nr. 134) — **nach dem Deploy**
1. Einen Dienst mit der Uhr fahren und normal hochladen lassen.
2. Danach einen **älteren, unbearbeiteten** Einsatz (> 72 h) von der Uhr
   erneut senden lassen, falls das ohne Aufwand geht.

**Erwartet:** Der laufende Dienst kommt vollständig an. Der alte Einsatz
bleibt unverändert, und die Uhr meldet **keinen Fehler** und wiederholt nicht.
**Scheitern:** Die Uhr hängt in einer Wiederholschleife (dann antwortet der
Server nicht mit `ok`), oder ein **aktueller** Nachtrag kommt nicht an (dann
ist das Fenster falsch gerechnet — bitte mit Uhrzeit melden).

### P-7 · Die Hinweismail (Nr. 128) — braucht ein echtes Postfach
Nach P-5 in das Postfach der **alten** Adresse sehen.
**Erwartet:** Eine Mail „Anmeldeadresse geändert" mit alter und neuer Adresse
und dem Satz „Warst du das nicht, handle bitte sofort".
**Scheitern:** keine Mail (dann im Serverprotokoll nach `Adresswechsel:
Hinweismail` sehen — steht sie dort, klemmt der Mailweg, nicht der Code); oder
die Mail geht an die **neue** Adresse (das wäre ein Fehler und gehört
gemeldet).

### P-8 · Nach dem Deploy: die Wartungsseite (alle Punkte)
**Betrieb → Status** durchsehen.
**Erwartet:** keine rote Zeile. „Schlüsselableitung" darf „Übergang läuft"
zeigen, solange Konten unter dem Zielwert stehen.
**Scheitern:** „Anmeldung blockiert" in der Zeile Schlüsselableitung — dann
sofort melden.

### P-9 · Die Integritätswache im Postfach (Nr. 140)
1. Nach dem Merge auf `main` im **Actions**-Tab den Lauf „Integritätswache"
   ansehen (er startet nach dem Deploy von selbst).
2. Sicherstellen, dass GitHub Ihnen Fehlschläge schickt: Profil →
   Notifications → **Actions** → „Only notify for failed workflows" reicht.
3. Steht die Installation nicht unter `https://nadoku.gen-em.org`, die
   Repository-Variable **`WACHE_BASIS`** setzen (Settings → Secrets and
   variables → Actions → Variables).

**Erwartet:** grüner Lauf mit „Kein Unterschied" und der Zeile
„112 Dateien … 112 gleich".
**Scheitern:** rot. Dann **zuerst** prüfen, ob gerade deployt wurde und ob
`main` weiter ist als die Auslieferung — die Reihenfolge steht in
`tools/integritaetswache/LIESMICH.md`. Erst wenn beides nicht passt, ist es
eine Manipulation: **nichts überschreiben**, die abweichende Datei per FTPS
herunterladen und beiseitelegen, FTPS-Zugangsdaten wechseln, und jedes
Passwort als möglicherweise mitgelesen behandeln.

### P-10 · Zuarbeit, die nicht im Repositorium erledigt werden kann (Nr. 140)
- **Branch-Schutz** auf `main` mit Review-Pflicht.
- **2FA-Zwang** in der Organisation.

Beides steht seit R78 aus und ist der Teil von Nr. 140, den die Wache
ausdrücklich **nicht** ersetzt: Sie erkennt einen Angreifer mit Push-Recht
nicht.

### P-11 · Uhr-Kopplung nach dem Android-Release (Nr. 144) — **am Gerät, vor dem Dienst**
1. Handy-App und Uhr-App 0.14.0 aufspielen (beide aus einem Baulauf,
   gleiche Signatur).
2. An der Uhr „Dienst beginnen". Am Handy muss der Dienst laufen, und die
   Uhr muss „Dienst läuft" zeigen — das ist die angekommene Quittung.
3. Eine Phase an der Uhr setzen; im Web erscheint der Einsatz mit dieser
   Zeit. Dienst an der Uhr beenden.

**Erwartet:** wie bisher — die Prüfung ist unsichtbar, wenn sie durchlässt.
**Scheitern:** Die Uhr bleibt bei „wartet aufs Handy" oder liefert dasselbe
Ereignis immer wieder nach; `adb logcat -s NAdoku` zeigt „Ereignis von
unbekanntem Knoten … verworfen". Dann nennt `connectedNodes` auf dem Gerät
nicht den Knoten, den der Data Layer als Absender nennt — sofort melden, mit
der Protokollzeile; bis dahin 0.13.0 auf dem Handy lassen.

### P-12 · Klartextverbot auf einem alten Gerät (Nr. 142) — nur bei Gelegenheit
Auf einem Android-8-Gerät (API 26/27) ein Release-APK mit
`-Pnadoku.serverBasis=http://<IP-Adresse>/` bauen und die Kopplung starten.
**Erwartet:** „Keine Verbindung" — die App spricht `https`, und das System
verböte Klartext ohnehin. **Scheitern:** Eine Kopplungssitzung kommt
zustande.

---

## 7. Grenzen der benutzten Prüfmittel

- **Der Bilderlauf klickt keinen Knopf.** Er fotografiert und misst Überlauf,
  Konsolenfehler und Knopfhöhen. Die Bedienwege dieser Stufe (Anmeldung,
  Profilformular) sind deshalb **einzeln** im Browser gefahren worden, nicht
  vom Bilderlauf.
- **Die Vollständigkeitsprüfung zählt auch Kommentare.** Ihre Kategorie
  „Unicode-Zeichen als Symbol im Markup" trifft Ellipsen in PHP-Kommentaren;
  daher 301 statt 300.
- **Die Integritätswache misst, was HTTP ausliefert**, nicht, was auf der
  Platte liegt. Von PHP sieht sie nur, was davon auf der **Anmeldeseite**
  ankommt — Skripte und Formulare. Ein Skript, das erst auf einer angemeldeten
  Seite erscheint, sieht sie nicht; dort beginnt die CSP (Nr. 8, P5).
- **`tools/gpxprobe/` Teil 8 ruft `gpx_lesen()` unmittelbar auf**, nicht über
  HTTP. Die Funktion ist die Abwehr und hat genau einen Aufrufer
  (`api/gpx_import.php:115`).
- **Die Ingestprobe fährt Grenzfälle, nicht die Menge.** Der vollständige
  Sendeplan ist `tools/referenzdatensatz/einspielen/`.
- **Kein Nebenläufigkeitsprüfstand.** Ein Upload genau während eines
  Verdichtungslaufs lässt sich hier nicht herstellen.
- **Der Emulator zeigt die Handy-App, nicht den Data Layer.** Im Abbild
  ohne Telefonseite liefert `connectedNodes` eine leere Liste; die
  Absenderprüfung (Nr. 144) ist dort nur als Code lesbar, nicht als
  Verhalten. Was der Emulator belegt, ist, dass Kopplung, Einstellungen und
  Trennen nach dem Umbau laufen — und das Trennen **ist** der Räumlauf.
- **Robolectric misst die Regel, nicht die Uhr des Geräts.** Die 30 Tage und
  die fünf Minuten sind mit gestellter Zeit geprüft.

---

## 8. Wenn die Prüfliste abgehakt ist

Dieses Dokument wird gelöscht (CLAUDE.md 7). Die Erledigt-Zeile für Schritt
9a steht seit Fassung 36 in `docs/Rahmenplan.md` Abschnitt 8; was dort noch
fehlt, ist der Merge auf `main` — der deployt den Web-Teil sofort und
verlangt danach P-8.
