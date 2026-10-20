# Prüfdokument R17 — Review (Schritt 12: Bedrohungsmodell; Schritt 12b: Stücke 2 bis 12)

*Gehört zu `Review-R17.md`. Beantwortet „was muss **ich** noch tun?" — das
Protokoll „ist es belegt?" steht im Statusblock und in Abschnitt 5 des
Konzepts. Angelegt am 11.10.2026 mit R17-00 (Fable); jedes Paket füllt es
mit Mittel **und** Zahl. Stand: **Schritt 12b läuft seit dem 20.10.2026**
(R17-13 erledigt — Stücke 2 bis 9 gelesen); Schritt 12 ist abgeschlossen — Freigabe des Modells
durch die Betreiberin am 11.10.2026 (P-R17-01), Erledigt-Zeile in
Rahmenplan 8 (Fassung 156), PR #103 gemergt. **12b schreibt dieses
Dokument fort** (E-R17-15), mit denselben Zählern: Die Punkte P-R17-03 bis
-05 aus Schritt 12 bleiben offen neben dem, was die Stücke bringen. Es
bleibt, bis seine Prüfliste abgehakt ist (K9).*

---

## 1. Was nicht geprüft werden konnte — und warum

Steht vor allem anderen. Was dazukommt, gehört hierher — an den Anfang.

| Was | Warum nicht | Wo es sich zeigt |
|---|---|---|
| **Ob das Modell vollständig ist, misst kein Werkzeug.** | Ein Bedrohungsmodell ist eine Lesung: Es zählt auf, wogegen die Anlage hält und wogegen nicht. Dass keine Angreiferlage fehlt, lässt sich nicht nachzählen, nur gegenlesen — gegen die Liste im Fahrplan (Schritt 12), gegen den Krypto-Review vom 05.09.2026 (Git-Historie; Stand in Konzept 3.4) und gegen den Code. Die Inventartabelle (Konzept 3.2) macht sichtbar, **welche** Quellen gelesen wurden; sie belegt nicht, dass es alle sind. | P-R17-01: die Betreiberin liest das Modell gegen ihr eigenes Bild der Anlage. |
| **Was die drei Leser von R17-01 nicht sicher lesen konnten** — je Punkt, wo es sich zeigt: (1) `Toybox.Cryptography` auf der Garmin (Kurven, AES-CBC, HMAC, Verfügbarkeit auf allen 99 Geräten) — kein Connect-IQ-SDK im Container; SP-9 behauptet es. (2) Ob Garmin den App-Speicher verschlüsselt und ob er per USB lesbar ist. (3) Der Weg einer Connect-IQ-Anfrage (direkt oder über Garmin-Server, Zertifikatsprüfung, `http://`). (4) Ob die App-Einstellungen der Garmin (Alt-Weg mit `apiKey`) in Garmins Cloud liegen. (5) Ob der Wear-Data-Layer ohne Bluetooth über Google-Server vermittelt. (6) Hardware-Anker des Keystore (TEE/StrongBox) — gerätabhängig, nicht gefordert. (7) Schutzregeln der GitHub-Umgebungen und Zweitfaktor-Zwang der Organisation — API 403; wer freigeben darf, ist nirgends belegt. (8) Ob `kdf_anteil` auf Produktiv gesetzt ist (Rahmenplan 6.1: offen). (9) `display_errors`, PHP-Fassung, bcrypt-Kosten, `csp_scharf`, `hsts_tage` auf Produktiv. (10) Zugriffsprotokolle beider Hoster (Dauer, Inhalt). (11) Ein vollständiger IDOR- und XSS-Durchgang (Stichproben nur; 43 `innerHTML`-Zuweisungen ungeprüft) — **Gegenstand von 12b, nicht von 12**. (12) Welche `ftp-deploy`-Fassung in der gepinnten SHA steckt. | Für (1): Probe-App im Uhr-Prüfstand, Stufe I über alle Geräte (vor S11). (2)–(5): Herstellerdoku, vor S11. (7), (8), (9): die Betreiberin liest es in GitHub bzw. auf Betrieb → Status ab — P-R17-04. (11): 12b. |
| **Stück 2 (R17-06): drei Dinge, die der Container nicht messen konnte.** (1) Die Signaturprüfung der Passkeys und des Rückwegs (`passkey_lib.php`, `rueckweg_lib.php`) läuft über phpseclib unter `vendor/`; PHPStan sieht die Klassen nicht (44 `class.notFound`), die Prüfung ist **gelesen**, nicht statisch geprüft — die Zweitfaktorprobe misst sie gegen die Anlage (Stufe 2). (2) F-R17-33: ob ein Dump von vor dem 03.09.2026 wirklich den Kopf „Komplettsicherung der Installation" trägt, lässt sich hier nicht nachsehen — die Git-Historie dieses Repositoriums beginnt am 21.09.2026, eine Fixture mit altem Kopf gibt es nicht; belegt ist die Wortgleichheit der beiden Vergleiche (PHPStan) und die Umstellung (Rahmenplan-Archiv-2, Schritt 4). (3) F-R17-32: der fehlende Ratenschutz ist am Code gelesen (kein `rate_*`-Aufruf), nicht gegen die Anlage gemessen — der Review behebt und misst nichts (K4); das Paket, das ihn behebt, erweitert die Zweitfaktorprobe um den Fall. | (1): Zweitfaktorprobe in Stufe 2 nach jedem Paket, das die beiden Dateien anfasst. (2): P-R17-06 — die Betreiberin sagt, ob noch ein Dump von vor S7 liegt. (3): das Paket zu F-R17-01 (b) und F-R17-32. |
| **Stück 6 (R17-10): drei Dinge, die hier nicht gemessen sind.** (1) F-R17-47 ist am Code gelesen (die `FilesMatch`-Liste gegen die zwei Schreibziele, der Aufrufer von `wartung_einschalten()`), aber nicht gegen einen Apache gefahren: Der Prüfstand `klein` liest keine `.htaccess`, und ob Produktivs Apache die Datei genau so auswertet, zeigt nur der Abruf dort (P-R17-10). (2) F-R17-46 ist am Code gelesen (`AUTH LOGIN` ohne Bedingung, `$cfg['user']` ohne `??`); ein Relais ohne Anmeldung ist nicht zur Hand, die Mailprobe (`tools/proben/mail/`) fährt mit Anmeldung. (3) F-R17-49 ist am Code gelesen (`ui_e($text)` in `ui_meldung_markup()`, die zwei Aufrufer mit Tags); im Browser ist keine der zwei Meldungen gesehen — die Bremse steht im Prüfstand auf Stufe 0, und der Bilderlauf fotografiert die Hilfe mit vorhandenem `docs/`. | (1): P-R17-10; das Paket zu F-R17-47 bringt den Abruf in die Probe `wartung` (404 oder 403 statt 200). (2): das Paket zu F-R17-46 bringt den Fall „ohne `user`" in die Mailprobe. (3): das Paket zu F-R17-49 bringt den Riegel in Stufe 1, der eine dritte Stelle vor dem Merge findet. |
| **Stück 7 (R17-11): drei Dinge, die hier nicht gemessen sind.** (1) Der `innerHTML`-Durchgang ist Lesung: Jede der 50 Zuweisungen in den Skripten und 27 in den Seiten ist auf ihre Quelle zurückverfolgt (`EdHtml.escape()`, `textContent`, Bauart `[a-z0-9]`, festes Markup); nachgespielt ist keine — es gibt keine Probe, die eine Sicherung mit `<img onerror>` in `pat.age` oder `pat.site` einspielt und die drei Einsatztabellen fotografiert. (2) F-R17-50 ist gegen das npm-Original gerechnet (`curl registry.npmjs.org/leaflet/-/leaflet-1.9.4.tgz`, SHA-256 der entpackten `dist/leaflet.css` = Kopfwert); ob die Datei auf Produktiv LF oder CRLF trägt, ist nicht gemessen — die Kette lädt den Klon, also LF. (3) Nr. 382 (erste Antwort der Startseite) ist mit keinem Mittel dieses Stücks messbar; `vorladen.js` ist gelesen, nicht gestoppt. | (1): Das Paket zu Nr. 22/F-R17-49 bringt eine Injektionsprobe in den Prüfstand — eine Sicherung mit Markup in drei `pat`-Feldern, Bilderlauf über Tag, Suche, Zeitraum, Soll: Text, kein Bild. (2): Das Paket zu F-R17-50 schreibt `-text` und rechnet in Stufe 1 nach. (3): bleibt bei Nr. 382. |
| **Stück 8 (R17-12): drei Dinge, die hier nicht gemessen sind.** (1) F-R17-52 ist am Code gelesen (der Index aus `_findJob()`, die drei Stellen, die die Liste währenddessen kürzen) und nicht nachgespielt: Der Rundlauf (`tools/uhr-pruefstand/rundlauf/`) hat keinen Fall mit geparkten Paketen vor einem laufenden, und Stufe II ist für dieses Stück nicht gefahren. (2) F-R17-53 ist am Code gelesen (`catch` → `"kaputt"` → `Storage.setValue`, kein `deleteValue` auf `kpruef` im ganzen Quelltext); ob der Selbsttest auf einer echten Uhr je eine Ausnahme geworfen hat, weiß nur die Uhr — und sie zeigt es nur als „Verschlüsseln geht nicht". (3) Die Punkte (1) bis (4) aus der R17-01-Zeile oben bleiben: `Toybox.Cryptography` ist jetzt **übersetzt** für 99 Geräte, aber nicht auf ihnen gelaufen; ob Garmin den App-Speicher verschlüsselt, ob die Anfrage über Garmin-Server läuft und ob die Einstellungen in Garmins Cloud liegen, misst kein Werkzeug dieses Repositoriums. | (1): Das Paket zu F-R17-52 bringt den Fall in den Rundlauf (zwei geparkte, ein offenes finales Paket, Verwerfen während der Anfrage; Soll: das offene Paket kommt an, die Chunks der geparkten sind weg, die App läuft). (2): Das Paket zu F-R17-53 misst im Simulator, dass ein einmal geworfener Selbsttest beim nächsten Start neu misst. (3): bleiben bei der Freigaberunde als Grenzen des Modells (Lage H, R13, R14). |
| **Stück 9 (R17-13): vier Dinge, die hier nicht gemessen sind.** (1) F-R17-54 ist am Code gelesen (die zwei `PendingIntent` mit `BEENDEN`, der Zweig in `onStartCommand()`, das private `beenden()` ohne `klammer.beenden()`, die zwei anderen Wege zum Vergleich) und nicht nachgespielt: Kein Prüffall startet den `AufzeichnungsDienst` unter Robolectric, und weder Emulator noch Gerät liefen. (2) Die drei instrumentierten Klassen (`src/androidTest/`, 11 Fälle — Data Layer erreichbar, Gerätehülle auf dem Gerät, Keystore-Härte des Tresors) sind nicht gelaufen; sie brauchen ein Android-System. (3) Die 15 übersprungenen Fälle je Bauart (drei Rundlauf-Klassen, der andere Fall von `ServeradresseTest`) brauchen die örtliche Anlage mit `-Pnadoku.rundlauf`; der Review startet sie nicht. (4) `lint test` baut kein APK: Größe, Signatur und `aapt2 dump permissions` sind nicht gemessen; was die Kette daraus macht, ist Stück 10/11. | (1): Das Paket zu F-R17-54 bringt den Robolectric-Fall (Dienst beginnen, `BEENDEN` an `onStartCommand()`, danach `klammer.laeuft()` falsch) und die Betreiberin P-R17-11 am Gerät. (2): bleibt der Gerätetest (E-R45-7; `LIESMICH.md` 2.3). (3): der nächste Android-Baulauf mit Anlage (`LIESMICH.md`, „Was der Baulauf heute meldet", 279 Fälle). (4): Stück 10/11 liest `appbau.yml`; P-R17-05 misst die Berechtigungen der Wear-APK. |
| **Stück 5 (R17-09): drei Dinge, die hier nicht gemessen sind.** (1) F-R17-43 ist am Code gelesen (`zf_codes_neu` ohne `zweitfaktor_frisch_verlangen()`, `ZF_FRISCH_HANDLUNGEN` ohne die Handlung) und gegen die Begründung von E-SR-31 gehalten, aber nicht gefahren: Der Prüfstand `klein` hat kein Konto mit eingeschaltetem Zweitfaktor, und die Zweitfaktor-Probe kennt die Codes nur beim Einrichten. Ob auf Produktiv schon jemand Codes erzeugt hat, den die Person nicht kennt, sieht nur das Protokoll (P-R17-09). (2) F-R17-45 ist am Code gelesen (`fputcsv` ohne Entschärfung, Modellname aus der Selbstauskunft); die Datei ist nicht in Excel geöffnet — die Wirkung einer Formelzelle ist Sache der Tabellenkalkulation, nicht der Anlage. (3) F-R17-42 ist am Code gelesen (die Liste gegen die Handlungen); ein POST `konto_status` gegen das Demo-Konto ist nicht gefahren — die Rollenprobe misst das Demo-Konto nicht. | (1): P-R17-09; das Paket zu F-R17-43 bringt den Fall in die Zweitfaktor-Probe. (2): das Paket zu F-R17-45 bringt eine Probe über die `fputcsv`-Ausgabe (Zelle beginnt nicht mit `=`). (3): das Paket zu F-R17-42 bringt den Fall in die Rollenprobe. |
| **Stück 4 (R17-08): zwei Dinge, die hier nicht gemessen sind.** (1) F-R17-39 ist am Code gelesen (`spur_stand()` liefert kein `n_gespeichert`, der zweite Zweig rechnet 0) und mit `git log -S` datiert, aber nicht an einer gepackten Spur im Browser gefahren — die GPX-Probe kennt nur eine frische Spur, und der Prüfstand `klein` fährt den Wartungslauf nicht; ob Produktiv schon gepackte Spuren hat und der Menüeintrag dort fehlt, sieht nur die Betreiberin (P-R17-08). (2) F-R17-40 ist am Code gelesen (die Sperre fragt das Datum); ein Import mit einem gelöschten und einem aktiven Tag am selben Datum ist nicht gefahren — die Importprobe hat den Fall nicht. | (1): P-R17-08; das Paket, das F-R17-39 behebt, bringt den Probenfall mit gepackter Spur. (2): das Paket zu F-R17-40 bringt den Fall in die Importprobe. |
| **Stück 3 (R17-07): zwei Dinge, die hier nicht gemessen sind.** (1) F-R17-36 ist am Code gelesen (`rate_merkmale()` → zwei Merkmale, ein `max`) und gegen Probe und Doku gehalten, aber nicht gegen die Anlage gefahren — der Review behebt und misst nichts (K4), und die Probe `raten` klopft den Topf `login_ip` nur isoliert; ob eine Klinik auf Produktiv schon getroffen wurde, zeigt nur die Sicherheitsseite (P-R17-07). (2) `wegwerfdomains.txt` ist nur auf Form gelesen (eine Domain je Zeile); ob die Liste aktuell ist, misst kein Werkzeug dieses Repositoriums. | (1): P-R17-07 — die Betreiberin liest Betrieb → Sicherheit; das Paket, das F-R17-36 behebt, bringt den Probenfall. (2): Stück 10 sieht nach, ob ein Werkzeug die Liste pflegt. |
| **Die Behauptungen des Modells über den Code sind gelesen, nicht gemessen.** | R17-01 liest den Code bei Web 21.14.0, Uhr 3.1.0, Android 0.17.0 und nennt Funktionsnamen. Dass eine genannte Funktion tut, was das Modell sagt, belegt die Lesung; eine Probe, die es misst, gibt es nur dort, wo ein Prüfwerkzeug schon steht (`tools/proben/`). Das Modell nennt je Baustein, ob eine Probe ihn misst. | Abschnitt 2 je Paket; Konzept 3.2. |

## 2. Maschinell geprüft

Je Paket die Mittel mit Zahl. Die Zahl eines Pakets steht hier mit dem
nächsten Paket — eine Zeile hier ändert den Baum, den der Prüfstand gemessen
hat (`Pruefablauf.md` 5.1); der Bericht eines Pakets steht in **seiner**
Commit-Nachricht.

### R17-13 Stück 9 — Android (20.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| Lesung Fable (E-R17-14), jede Datei vollständig | die 98 Kotlin-Dateien beider Module und `gemeinsam/`, Manifeste, Netz- und Sicherungsregeln, Ressourcen, Bauskripte, Prüffälle, Werkzeuge (Konzept 7.3.9) — je Datei: Herkunft des Schlüssels, Speicher und Schutz, Netz und Antworten, wer von außen zuhört, was beim Beenden geschieht | 135 Dateien, 23 824 Zeilen (18 809 Kotlin), 1 075 Kommentarverweise; 1 Eintrag in 2.3a (F-R17-54 mittel) |
| Nachlesen der Altbefunde des Stücks | F-R17-03, -27, -31; AN-5 | F-R17-27 und -31 bestätigt; F-R17-03 ohne zweiten Leser in `android/` (0 Treffer `System.getenv`/`environmentVariable`), bleibt für Stück 10/11; AN-5 beantwortet (Konzept 7.3.9) |
| `bash tools/sandbox/aufbauen.sh android` | ob der Android-Bau im Container steht | Rückgabe 0; SDK unter `/opt/android-sdk`, Gradle 9.7.1 über den Wrapper (Prüfsumme) |
| `./gradlew --no-daemon lint test` (beide Module, beide Bauarten) | ob Lint und alle JVM-Prüffälle grün sind | BUILD SUCCESSFUL in 6 min 5 s; 144 Aufgaben (131 ausgeführt, 13 aus dem Cache); Rückgabe 0 |
| `build/test-results/*/TEST-*.xml` je Modul und Bauart, ausgezählt | Fälle, übersprungene, Fehlschläge, Fehler | Handy `testDebugUnitTest` 30 Klassen, **280 Fälle, 15 übersprungen, 0 rot**; `testReleaseUnitTest` ebenso; Uhr je Bauart 6 Klassen, **72 Fälle, 0 übersprungen, 0 rot**; übersprungen in `KopplungRundlaufTest`, `MissionRundlaufTest`, `SendeRundlaufTest`, `ServeradresseTest` |
| `build/reports/lint-results-debug.xml` je Modul | Lint-Fehler und -Warnungen | Handy **0 Fehler, 5 Warnungen** (2 × `GradleDependency`: wear-compose 1.7.1; 3 × `NewerVersionAvailable`: `kotlin.android` 2.4.21, `kotlin.plugin.compose` 2.4.21, Robolectric 4.17), alle in `libs.versions.toml`; Uhr **0 und 0** |
| Bilderlauf (`HandyBildTest`, `UhrBildTest`, Teil von `test`) | gezeichnete Bilder | Handy 78 Bilder (je 800 dp), Uhr 7 Bilder, paarweise verschieden; Robolectric-Abbild offline unter `build/robolectric-abbild/` beider Module |
| Zähler (`zaehlung-r17-13.py`) über die 54 `main`-Kotlin-Dateien | Protokoll-, Fehler-, SQL-, Zufalls-, Netz-, Krypto- und Nebenläufigkeitsstellen | `Log.` 23 · `try` 27 · `catch (Exception/Throwable)` 13 · `rawQuery`/`execSQL` 25 (0 mit `$`) · `SecureRandom` 3 · `Random` 2 · `Tasks.await` 3 · `HttpURLConnection` 6 · `openConnection` 1 · `instanceFollowRedirects` 2 · `Cipher.getInstance` 3 · `KeyGenParameterSpec` 2 · `setUserAuthenticationRequired` 2 · `PendingIntent.get` 7 · `FLAG_IMMUTABLE` 7 · `startForeground` 1 · `@Volatile` 5 · `Thread(` 2 · `Executors` 3 · `System.getenv` 0 · `BuildConfig.` 7 · `import com.google.android.gms` 6 (3 Dateien) · `sourceNodeId` 3 · `TODO` 0 |
| `grep` über `klammer.beenden`, `BEENDEN`, `addAction`, `fun beenden`, `AufzeichnungsDienst.steht`, `uses-permission`, `android:exported`, `@Test` | die Fundstellen des Befunds und die Oberfläche nach außen | `klammer.beenden` 2 Aufrufer (`HauptActivity`, `Uhrannahme`); `BEENDEN` an 2 `PendingIntent` (Akkuwarnung, Erinnerung), 1 Zweig in `onStartCommand()`; `addAction` 2; `uses-permission` Handy 8, Uhr 0; `exported="true"` je 2; 46 Prüffall-Dateien, 39 mit `@Test`, 363 `@Test` (352 JVM + 11 instrumentiert) |
| `decken.py`, `nummern.py`, `uebersicht.py --ziel 12b`, `textprobe.py --bereich c`, `linkprobe.py`, Prüfstand `klein` | Steuerung und Tor | siehe Commit-Nachricht von R17-13 |

### R17-12 Stück 8 — Uhr (20.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| Lesung Fable (E-R17-14), jede Datei vollständig | die 20 Monkey-C-Dateien, zwei Geräteprofile, Manifest, Jungle, Einstellungen, Strings, zwölf `drawables.xml` (Konzept 7.3.8) — je Datei: Herkunft des Schlüssels, was im Speicher liegt und wie lange, was über das Netz wohin geht, was bei jeder Antwort geschieht | 39 Dateien, 5 650 Zeilen (5 253 Monkey C), 131 Kommentarverweise; 2 Einträge in 2.3a (F-R17-52 mittel, F-R17-53 niedrig) |
| Nachlesen der Altbefunde des Stücks | F-R17-11, -12, -13, -28; AN-2, I-31, K-14 | F-R17-11, -12, -13 bestätigt; F-R17-28 zur Hälfte erledigt (geparkte: `PARK_FRIST_S` = 30 Tage; „abgemeldet": ohne Frist); AN-2 für Geparkte erledigt (Konzept 7.3.8) |
| `bash tools/uhr-pruefstand/pruefstand.sh aufbau` (SDK von `developer.garmin.com`, Geräte und Schriften von `CIQ_GERAETE_URL`) | ob der Prüfstand im Container steht | SDK 9.2.0 (Connect IQ Compiler 9.2.0), Schlüssel vorhanden, 1 332 Schriftdateien, 99 von 99 Manifest-Geräten, 3 Zielgeräte vorhanden, 0 fehlende Simulatorbibliotheken; `devices.tar` 326 MB, `fonts.tar` 1 207 MB |
| `python3 tools/uhr-pruefstand/geraeteklassen.py … --vertreter 5`, dann `pruefstand.sh reihe auswahl.txt` (Stufe I, `monkeyc -w` je Gerät) | ob die App für jedes Manifest-Gerät übersetzt, und mit wie vielen Warnungen | Auswahlliste 99 Geräte, Vertreterliste 20; **99 übersetzt, 0 fehlgeschlagen, 0 ohne Gerätedatei**; Spalte „Warn" 0 bei allen 99; Kompilate 206 bis 218 kB |
| `cat ~/.ciq-pruefstand/reihe_*.txt \| grep -c WARNING` | Gegenprobe zur Warn-Spalte | 99 Logs, 0 `WARNING`-Zeilen |
| Zähler (`zaehlung-r17-12.py`) über `Communications.makeWebRequest(`, `Storage.setValue(`, `.getValue(`, `.deleteValue(`, `Properties.getValue(`, `.setValue(`, `println`, `try`, `new WatchUi.Confirmation(`, `Cryptography.`, `randomBytes`, `Math.rand(` in `watch/source/*.mc` | Netz-, Speicher- und Kryptostellen | 6 · 13 · 18 · 10 · 5 · 2 · 0 · 8 · 8 · 22 · 1 · 3 |
| Verwenderprobe: jeder `function`-Name gegen den ganzen Monkey-C-Korpus, Lebenszyklus-Rückrufe ausgenommen | Funktionen ohne Verwender | 306 Funktionen, 2 ohne zweite Nennung (`Input.hasUpDown()`, `Input.touchActive()`) |
| `grep` über `find("http")`, `PARK_FRIST`, `parkFristPruefen`, `kpruef`/`ABLAGE_PRUEFUNG`, `pendingIdx`, `.remove(`, `alleGeparktenVerwerfen()`, `backlogCount()`, `uses-permission`, `iq:product` | die Fundstellen der Befunde und Altbefunde | `http` 1 Stelle (`_serverUrl()`); `PARK_FRIST_S` 1 Definition, 1 Verwender; `kpruef` 1 `getValue`, 1 `setValue`, 0 `deleteValue`; `pendingIdx` 4 Setzer, 1 Leser; `.remove(` 7; `alleGeparktenVerwerfen()` 2 Aufrufer, `backlogCount()` 2; 2 Berechtigungen, 99 Geräte |
| `decken.py`, `nummern.py`, `uebersicht.py --ziel 12b`, `textprobe.py --bereich c`, `linkprobe.py`, Prüfstand `klein` | Steuerung und Tor | siehe Commit-Nachricht von R17-12 |

### R17-11 Stück 7 — Browser-Skripte und Stylesheet (20.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| Lesung Fable (E-R17-14), jede Datei vollständig | die 57 Skripte und `style.css` (Konzept 7.3.7) — je Skript: Herkunft jedes Werts, der in die Seite geht; was über das Netz wohin geht; was im Browser liegen bleibt; je Stylesheet-Abschnitt: fremde Quellen, Werte außerhalb der Token | 58 Dateien, 22 597 Zeilen (17 783 JS, 4 814 CSS), 929 Kommentarverweise; 2 Einträge in 2.3a (F-R17-50 Hinweis, F-R17-51 niedrig); 30 Skripte zum zweiten Mal (Stücke 2 bis 5), 0 neue Befunde daraus |
| Nachlesen der Altbefunde und Posten des Stücks | F-R17-24; Nr. 341, 380, 382 | F-R17-24 bestätigt; Nr. 341 gemessen (13 Regeln, 6 ohne Grund, alle 6 nur in Kommentaren); Nr. 380 gezählt; Nr. 382 nur gelesen (Konzept 7.3.7) |
| `node --check` | Syntax jedes Skripts, auch unter `vendor/` | 61 Dateien, 0 Fehler |
| `sha256sum` über den Rumpf hinter dem Kopfkommentar, gegen den Kopfwert; für `leaflet.css` zusätzlich gegen `dist/leaflet.css` aus `leaflet-1.9.4.tgz` (npm) und `git ls-files --eol` | ob die fünf Kopf-Prüfsummen stimmen (F-R17-50) | 4 von 5 stimmen; `leaflet.css`: Rumpf LF `337bfca5…`, Kopf `a7837102…` = SHA-256 des CRLF-Originals, `diff -w` leer, `.gitattributes` `* text=auto eol=lf`, 1 `-text`-Ausnahme (die XSD) |
| `grep` über Herkunft, Fassung, SHA in den Köpfen unter `assets/` und `vendor/`; `grep -ri openlocationcode docs/`; `grep OpenLocationCode\.` über `server/` | Fremdbestandteile ohne Nachweis (F-R17-51) | 5 vendor-Textdateien mit Herkunft und SHA; 1 Fremddatei außerhalb `vendor/` (`openlocationcode.js`, 720 Zeilen) ohne beides; in `docs/Lizenzen.md` 0 Treffer, in `Technik.md` und `CHANGELOG.md` je 1; 4 Verwender in `locparse.js` |
| `python3 tools/quelltext/vollstaendigkeit.py` (und `--ausfuehrlich`); `grep -rl` über die sechs Klassen in `server/` | Nr. 341; Werte außerhalb der Token | 13 Regeln „im Markup nicht gefunden" (7 mit Grund, 6 ohne); die 6: 0 Treffer im Markup, 4 nur in Kommentaren (`ui.php`, `einstellungen.php`, `version.php`), 2 nirgends; Hexwerte 0, Schriftgrößen 0, Pixelmaße 0 (nach Eigenschaftsausnahme — 3 Literale darunter: 34, 56, 14 px), `style="` in PHP/JS 0 (nach Ausnahmen) |
| `grep -c` über `\.innerHTML\s*[+]?=`, `insertAdjacentHTML`, `\.textContent\s*=`, `\beval\(\|new Function\(`, `document\.write`, `\bfetch\(`, `style="`, `localStorage\.`, `sessionStorage\.` in `assets/*.js` und `server/*.php` | der `innerHTML`-Durchgang (Prüfdokument 1 (11)) und die Netz- und Speicherstellen | `innerHTML` 50 (JS) und 27 (PHP); `insertAdjacentHTML` 1 und 3; `textContent` 41; `eval`/`new Function`/`document.write` 0; `fetch(` 7 Aufrufe (`api.js` 1, `geocoder.js` 2, `kopplung.js` 1, `unlock.js` 1, `vorladen.js` 2); `style="` 10 (geo 2, missiontable 2, schneiden 6); `localStorage` 2, `sessionStorage` 24 |
| Exportprobe: `window.EdX = {…}` und `const EdX = (…)` gegen `\bEdX\b` und `EdX.name` in `server/` und `tools/` | Exporte und Einträge ohne Verwender | 36 Namen, 0 ohne Verwender (einer ist ein Wächterwert, `EdAnkuendigung`); 15 Einträge ohne Verwender außerhalb ihrer Datei, 1 mit Probe, 14 ohne (Konzept 7.3.7); 1 Fehltreffer des Musters von Hand entfernt |
| `grep -c '<script'` und `asset('assets/…\.js')` je `server/*.php` | Nr. 380 | `einstellungen.php` 27/20, `index.php` 20/19, `einsatz_form.php` 14/13, `import.php` 11/9, `einsatz.php` 10/9, `zeitraum.php` 8/6, `tag_spuren.php` 7/6, `suche.php` 6/5; `ui.php` 18/7 (Gerüst) |
| `grep` über `style-src`, `img-src`, `KOPF_KACHELN` in `kopfzeilen_lib.php` gegen die `style="`-Stellen und die Hosts in `map_layers.js` | ob die CSP deckt, was die Skripte tun | `style-src 'self'` + `style-src-attr 'unsafe-inline'` deckt 10 Stellen; 4 Kachelhosts = 4 Einträge; `connect-src` kennt den Adressdienst |
| `grep` über `url(`, `@import`, `https?://` in `style.css` | fremde Quellen im Stylesheet | 10 `url()`, alle `fonts/`; 0 `@import`; 0 Adressen außerhalb von Kommentaren |
| `decken.py`, `nummern.py`, `uebersicht.py --ziel 12b`, `textprobe.py --bereich c`, `linkprobe.py`, Prüfstand `klein` | Steuerung und Tor | siehe Commit-Nachricht von R17-11 |

### R17-10 Stück 6 — Betrieb (20.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| Lesung Fable (E-R17-14), jede Datei vollständig | die 48 Dateien des Stücks (Konzept 7.3.6) nach der Checkliste 7.2 an jedem HTTP-Einstieg: Rolle, CSRF, Eingabe, Ausgabe, Fehlerweg, Rate, gebundene Parameter, eine Stelle; dazu der Migrationslauf mit seinen Sperren und der Freigabe, der Torwächter, die Wartung und die Überlast, die Jobs, die Sicherungsziele, der Mailversand, der Hilfe-Renderer, die Bausteine in `ui.php` mit ihrem Vertrag über rohes Markup, `schema.sql` gegen den Katalog | 48 Dateien, 38 118 Zeilen (43 PHP, 4 SQL, `.htaccess`); 16 HTTP-Einstiege (11 Seiten, 4 Rechtstext-Hüllen, 1 `api/`) und `doku_seite.php` hinter 2 Hüllen; 4 Einträge in 2.3a (F-R17-46, -47, -49 niedrig, -48 Hinweis); 0 kritisch, 0 hoch |
| Nachlesen der Altbefunde und Posten des Stücks | F-R17-05, -19, -21; Nr. 77 | 3 nachgelesen, 3 bestehen (F-R17-19 und -21 bestätigt, -05 beim Stand der Betreiberin); Nr. 77 gelesen (Konzept 7.3.6) |
| PHPStan 2.3.1 (dieselbe Konfiguration wie R17-06) | Typfehler, tote Zweige, fehlende Schlüssel | 66 Meldungen in Stück 6: 20 `variable.undefined` (Rauschen aus `auth_guard.php`), 25 `class.notFound`/`class.noParent`/`method.notFound` (phpseclib 17, Parsedown 8 — unter `vendor/`, Rauschen), 21 gelesen — 0 Befunde, 3 veraltete Annotationen, 9 Absicht, 1 Fehlalarm, 8 kosmetisch |
| `grep -rE` über die `function`-Definitionen der 27 PHP-Dateien des Stücks mit Definitionen gegen `server/`, `tools/`, `docs/` | Funktionen ohne Aufruf oder Nennung | 412 geprüft, 3 ohne (`protokoll_frist_verwaltung_setzen()`, `protokoll_fehler_quittieren()`, `doku_bestand()`); 61 nur in der eigenen Datei, 5 davon JavaScript |
| `grep -rn -A4 'ui_meldung_markup('` über `server/`, gefiltert auf `<(strong\|code\|em\|a \|br)` | Aufrufer, die Tags in den maskierten Text geben (F-R17-49) | 2 Aufrufer (`betrieb_sicherheit.php`, `doku_seite.php`), 0 weitere; `ui_meldung_markup()`: 1 `ui_e($text)`, 1 `ui_e($auftakt)`, `$knopf` roh |
| `grep -rn -B6 "'unter' =>"` über `server/` | Aufrufer von `ui_titelzeile()`, deren `unter` roh ausgegeben wird | 18 Nennungen; 10 mit Laufzeitwert, 10 maskiert (`e()`, `ui_e()`) oder aus Datumswerten (`stat_achsensatz()`) — 0 Befunde |
| `grep` über `FilesMatch` in `.htaccess`; `grep "__DIR__ . '/"` in `wartung_lib.php`; `grep wartung_einschalten` über `server/` | welche Laufzeitdateien im Webroot die Sperrliste nicht kennt und was darin steht (F-R17-47) | 7 Dateinamen gesperrt; 2 Schreibziele im Webroot (`wartung.lock`, `ueberlast.json`) nicht darunter; 1 Aufrufer von `wartung_einschalten()`, übergibt `$userName ?: $userEmail` |
| `grep` über `AUTH LOGIN`, `$cfg['user']`, „ohne Authentifizierung" in `smtp.php`; Lesung der Betreffe in `mail_katalog()` | ob die Zusage im Code steht und ob Nutzertext in eine Kopfzeile geht (F-R17-46) | 2 Sätze „ohne Authentifizierung", 0 Bedingung am `AUTH LOGIN`, 2 Zugriffe ohne `??`; Betreffe: 0 mit Nutzertext (feste Texte, Zahlen, `mail.betreff_praefix` aus `config.php`) |
| `grep` über `ueberlast_erkannt()`, `UEBERLAST_CODES` in `wartung_lib.php` und das Muster in `tools/proben/verbindung/probe.php` | ob Liste, Muster und Probe dieselben Nummern kennen (F-R17-48) | Liste 3 (1040, 1203, 1226), Muster 2 (1040, 1203), Probenmuster 3 |
| `ls server/api/` gegen `WARTUNG_AUSNAHMEN` | ob ein `api/`-Skript denselben Dateinamen trägt wie eine Ausnahme | 26 Dateinamen, 0 Gleichheit |
| `grep` über die Aufrufer von `mail_konto_entfernen()` und `edbak_ablage_bereit()` | F-R17-21, F-R17-19 | 0 Aufrufer; 5 Dateien, `wiederherstellen.php` 0 |
| Verweiszähler (Muster aus Konzept 7.4) | Fundstellen des Kommentardurchgangs je Datei | 2 494 in 48 Dateien; Tabelle in Konzept 7.3.6 |
| `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` | Form der Steuerung; Nummern; neue Treffer; Verweise | siehe Commit-Nachricht von R17-10 |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel, Bericht für die Commit-Nachricht | siehe Commit-Nachricht von R17-10 |

### R17-09 Stück 5 — Verwaltung und Admin (20.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| Lesung Fable (E-R17-14), jede Datei vollständig | die 31 Dateien des Stücks (Konzept 7.3.5) nach der Checkliste 7.2 an jedem HTTP-Einstieg: Rolle, CSRF, Eingabe, Ausgabe, Fehlerweg, Rate, gebundene Parameter, eine Stelle; dazu die Rollengrenzen (Support, Admin, BetreiberIn, Demo), die Dateinamen der Sicherungen, der Renderer der Rechtstexte, die Fragmente von Suche und Zeitraum | 31 Dateien, 18 322 Zeilen; 17 HTTP-Einstiege (15 Seiten, 2 `api/`) und `rechtstext_seite.php` hinter 4 Hüllen; 4 Einträge in 2.3a (F-R17-43, -44, -45 niedrig, -42 Hinweis); 0 kritisch, 0 hoch |
| Nachlesen der Altbefunde und Posten des Stücks | F-R17-02, -06, -07, -08; Nr. 371, 381 | 4 nachgelesen, 4 bestehen (F-R17-07 und -08 bestätigt, -02 und -06 unverändert); Nr. 371 und 381 bestätigt (Konzept 7.3.5) |
| PHPStan 2.3.1 (dieselbe Konfiguration wie R17-06) | Typfehler, tote Zweige, fehlende Schlüssel | 201 Meldungen in Stück 5: 181 `variable.undefined` (Rauschen aus `auth_guard.php`), 0 `class.notFound`, 20 gelesen — 0 Befunde, 7 veraltete `@return`-Annotationen, 10 Absicht, 2 Fehlalarme, 1 toter Zweig |
| `grep -rE` über die `function`-Definitionen der 15 PHP-Dateien des Stücks mit Definitionen gegen `server/`, `tools/`, `docs/` | Funktionen ohne Aufruf oder Nennung | 140 geprüft, 0 ohne; 18 nur in der eigenen Datei (Seitenhelfer), 27 davon JavaScript |
| `php -r` | `$MONATSNAMEN[(int)'13']` (F-R17-44) | 1 Warnung („Undefined array key 13"), Lauf geht weiter |
| `grep` über `ZF_FRISCH_HANDLUNGEN` (`db.php`) gegen die `zf_`-Zweige in `einstellungen.php` | welche Handlung des Zweitfaktors ohne frischen Code läuft (F-R17-43) | 8 Handlungen in der Liste; `zf_codes_neu` fehlt, `zf_ausschalten` und `passkey_entfernen` stehen; `totp_codes_erneuern()`: 1 `protokoll()`, 0 Mail |
| `grep` über `DEMO_GESPERRT` gegen die `$action`-Zweige in `admin_user.php`; `grep status` in `demo_lib.php` | welche Handlung das Demo-Konto trifft (F-R17-42) | 7 Einträge gegen 10 Schreibwege; 3 fehlen (`konto_status`, `konto_grenzen`, `totp_zuruecksetzen`); `demo_lib.php`: 0 Nennungen von `status` |
| `grep` über `fputcsv` in `server/` und `_geraet_kuerzen()` in `geraete_lib.php` | ob eine CSV-Zelle entschärft wird und woher sie kommt (F-R17-45) | 1 `fputcsv`-Schreiber (`betrieb_statistik.php`), 0 Entschärfung; `_geraet_kuerzen()`: Steuerzeichen und Länge, kein Zeichen am Anfang |
| `grep` über die Monatsprüfung in `api/range.php` und `zeitraum.php` | ob beide dasselbe annehmen (F-R17-44) | `range.php` `^(0[1-9]\|1[0-2])$`, `zeitraum.php` `^\d{2}$` |
| Verweiszähler (Muster aus Konzept 7.4) | Fundstellen des Kommentardurchgangs je Datei | 1 012 in 31 Dateien; Tabelle in Konzept 7.3.5 |
| `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` | Form der Steuerung; Nummern; neue Treffer; Verweise | siehe Commit-Nachricht von R17-09 |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel, Bericht für die Commit-Nachricht | siehe Commit-Nachricht von R17-09 |

### R17-08 Stück 4 — Einsatz, Diensttag, Spur, Ingest (20.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| Lesung Fable (E-R17-14), jede Datei vollständig | die 36 Dateien des Stücks (Konzept 7.3.4) nach der Checkliste 7.2 an jedem HTTP-Einstieg: Rolle, CSRF, Eingabe, Ausgabe, Fehlerweg, Rate, gebundene Parameter, eine Stelle; dazu die Zusagen aus `CLAUDE.md` 4 (Klartext nie an den Server, Hülle an jedem Schreibweg, Spuren nur über `spur_lib.php`, Feldkatalog) | 36 Dateien, 20 019 Zeilen; 20 HTTP-Einstiege; 4 Einträge in 2.3a (F-R17-39 mittel, -38 und -40 niedrig, -41 Hinweis); 0 kritisch, 0 hoch |
| Nachlesen der Altbefunde und Posten des Stücks | F-R17-09, -13, -28; K-10; Nr. 23 | 3 nachgelesen, 3 bestehen (soweit das Stück sie sieht); K-10 gegenstandslos seit S11-05d; Nr. 23 gelesen (Konzept 7.3.4) |
| PHPStan 2.3.1 (dieselbe Konfiguration wie R17-06) | Typfehler, tote Zweige, fehlende Schlüssel | 101 Meldungen in Stück 4: 87 `variable.undefined` (Rauschen aus `auth_guard.php`), 0 `class.notFound`, 14 gelesen — 5 davon die zwei Befunde F-R17-38 (4 Meldungen) und -39 (1), 9 Absicht oder harmlos |
| `grep -rE` über die `function`-Definitionen der 14 PHP-Dateien des Stücks mit Definitionen gegen `server/`, `tools/`, `docs/` | Funktionen ohne Aufruf oder Nennung | 193 geprüft, 0 ohne |
| `php -r` | Verhalten von PHP 8 beim `use` einer undefinierten Variablen (F-R17-38) | 1 Warnung („Undefined variable"), Lauf geht weiter; `system_fehler_behandeln()` wirft nicht |
| `git log -S n_gespeichert` über `einsatz.php` und `spur_lib.php` | seit wann `spur_stand()` das Feld nicht mehr liefert (F-R17-39) | `0a6b926` (S11-12, Web 23.0.0, 19.10.2026) |
| `grep` über `tools/proben/gpx/probe.mjs`, `server/demo_lib.php`, `server/assets/wartungslauf.js`, `server/index.php` | misst die Probe eine gepackte Spur? wer stößt den Wartungslauf an? | Probe: 1 Abruf über `[data-gpx-laden]` an einer Spur ohne Wartung; `demo_lib.php`: 0 Nennungen einer Stufe; Wartungslauf: aus `index.php` über `wartungslauf.js` |
| Verweiszähler (Muster aus Konzept 7.4) | Fundstellen des Kommentardurchgangs je Datei | 1 242 in 36 Dateien; Tabelle in Konzept 7.3.4 |
| `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` | Form der Steuerung; Nummern; neue Treffer; Verweise | siehe Commit-Nachricht von R17-08 |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel, Bericht für die Commit-Nachricht | siehe Commit-Nachricht von R17-08 |

### R17-07 Stück 3 — Zugang und Sitzung (20.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| Lesung Fable (E-R17-14), jede Datei vollständig | die 31 Dateien des Stücks und der API-Teil von `db.php` (Konzept 7.3.3) nach der Checkliste 7.2 an jedem HTTP-Einstieg: Rolle, CSRF, Eingabe, Ausgabe, Fehlerweg, Rate, gebundene Parameter, eine Stelle; `wegwerfdomains.txt` nur auf Form | 31 Dateien, 11 238 Zeilen (+ `db.php` 480–830, + 8 883 Zeilen Domänenliste); 19 HTTP-Einstiege; 2 Einträge in 2.3a (F-R17-36 mittel, -37 niedrig), 1 Altbefund verschärft (F-R17-32: drei Stellen in `einstellungen.php`); 0 kritisch, 0 hoch |
| Nachlesen der Altbefunde und Posten des Stücks | F-R17-22, -23, -31; K-7, K-15 | 3 nachgelesen, 3 bestehen; K-7 und K-15 beantwortet (Konzept 7.3.3) |
| PHPStan 2.3.1 (dieselbe Konfiguration wie R17-06) | Typfehler, tote Zweige, immer wahre Vergleiche | 31 Meldungen in Stück 3: 22 `variable.undefined` (Rauschen aus `auth_guard.php`), 0 `class.notFound`, 9 gelesen — 0 Befunde, 1 Fehlalarm, 8 Absichtsvergleiche |
| `grep -rE` über die `function`-Definitionen der 15 PHP-Dateien des Stücks mit Definitionen gegen `server/`, `tools/`, `docs/` | Funktionen ohne Aufruf oder Nennung | 169 geprüft, 2 ohne (`sitzung_bindung_loeschen()`, `adresse_vormerkung_loeschen()`) |
| `grep` über `tools/proben/raten/probe.php`, `docs/Technik.md`, `server/einstellungen.php` | F-R17-36 gegen Probe und Doku; F-R17-32 gegen die übrigen `password_verify()`-Stellen | Probe: 4 Nennungen von `login_ip`, alle isoliert; Technik 5e.2: „10 je Konto, 50 je Anschluss"; `einstellungen.php`: 4 `password_verify()`, 4 `rate_*` — alle vier zum Kopplungscode, keiner zum Passwort |
| Verweiszähler (Muster aus Konzept 7.4) | Fundstellen des Kommentardurchgangs je Datei | 608 in 31 Dateien; Tabelle in Konzept 7.3.3 |
| `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` | Form der Steuerung; Nummern; neue Treffer; Verweise | siehe Commit-Nachricht von R17-07 |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel, Bericht für die Commit-Nachricht | siehe Commit-Nachricht von R17-07 |

### R17-06 Stück 2 — Kryptographie und Schlüssel (20.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| Lesung Fable (E-R17-14), jede Datei vollständig | die 38 Dateien des Stücks (Konzept 7.3.2) nach der Checkliste 7.2: Rolle, CSRF, Eingabe, Ausgabe, Fehlerweg, Rate, gebundene Parameter, eine Stelle; dazu die Hüllenformate gegen `Technik.md` 4.98/4.99 und das Modell | 38 Dateien, 20 053 Zeilen; 4 Einträge in 2.3a (F-R17-32 mittel, -33 und -34 niedrig, -35 Hinweis); 0 kritisch, 0 hoch |
| Nachlesen der Altbefunde des Stücks am Stand Web 23.0.2 (`grep` an der Fundstelle, Lesung des Blocks) | F-R17-01, -02, -06, -10, -19, -24, -26 | 7 nachgelesen, 7 bestehen (Konzept 7.3.2, Tabelle) |
| PHPStan 2.3.1 (Phar, örtlich, E-R17-16), Stufe 4, `server/` ohne `vendor/`, `assets/`, `demo/`, `doku/` | Typfehler, tote Zweige, immer wahre Vergleiche | 506 Meldungen gesamt; 88 in Stück 2: 29 `variable.undefined` (Rauschen: `$userId` aus `auth_guard.php`), 44 `class.notFound` (Rauschen: phpseclib, ConstantTime unter `vendor/`), 15 gelesen — 1 Befund (F-R17-33 `function.impossibleType`), 6 tote Zweige, 8 Absichtsvergleiche |
| `grep -rE` über die Namen aller `function`-Definitionen der 16 Bibliotheken des Stücks gegen `server/`, `tools/`, `docs/` | Funktionen ohne einen einzigen Aufruf oder eine Nennung | 334 geprüft, 1 ohne (`serverschluessel_zeile()`); Grenze: ein Aufruf über eine Zeichenkette (`call_user_func`) bliebe unsichtbar |
| `grep -rhoE '\.<Methode>\('` über `server/` für die Methoden der Objekte in `crypto.js`, `keyguard.js`, `pwquality.js`, `kontopaar.js`, `schluessel.js` | Methoden ohne Verwender | 0 (die Treffer `for`, `if`, `switch` sind Schlüsselwörter, die das Muster mitnahm; `sealTeil`, `pruefeRunden`, `ablegen`, `verwerfeInhalt` haben Verwender in derselben Datei) |
| Verweiszähler (`grep -oE` mit dem Muster aus Konzept 7.4) | Fundstellen des Kommentardurchgangs je Datei | 987 in 38 Dateien; Tabelle in Konzept 7.3.2 |
| `git log -G`, `-S`, `--follow` auf `wiederherstellen.php` und `komplett_lib.php`; `grep` in `Rahmenplan-Archiv-2.md`, `CHANGELOG.md` | F-R17-33: seit wann die beiden Vergleiche wortgleich sind und wie die alte Schreibweise hieß | wortgleich seit dem ältesten Commit der Historie (21.09.2026); die Umstellung war S7 (Web 12.9.3/12.9.4, 03.09.2026); alter Begriff „Komplettsicherung" an 5 Stellen in Changelog und Archiv belegt |
| `grep -c getContentKey` | Verwender für Nr. 370 | 4 Dateien, 11 Nennungen |
| `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `linkprobe.py` | Form der Steuerung; Nummern; neue Treffer; Verweise | siehe Konzept 5 (Zeile R17-06) |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel, Bericht für die Commit-Nachricht | siehe Commit-Nachricht von R17-06 |

### R17-05 Buchführung 12b (20.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| `python3 tools/steuerung/decken.py` | Decken der Steuerungsdokumente nach Rahmenplan Fassung 167, Verlaufszeile 167 (276 Zeichen) und Backlog-Kopf | 21 Decken, 0 gerissen — zwei rissen beim ersten Lauf: `backlog-kopf` mit 61 Zeilen (Decke 60) nach der neuen Spannenzeile, behoben durch Streichen der gemergten Spannen 12a und 19; `fahrplan-vokabular`, weil „läuft" kein Statuswort ist (Vokabular: offen, Konzept, freigegeben, Umsetzung, gebaut, gemergt, blockiert) — auf „Umsetzung" gesetzt |
| `python3 tools/steuerung/uebersicht.py --ziel 12b` | Kopfzeilen; Zuordnung zu 12b | 69 offene Einträge, 0 ohne Grammatik, 0 ohne gültiges Ziel; 10 zu Ziel 12b (Nr. 21, 77, 188, 263, 341, 370, 371, 380, 381, 382) |
| `python3 tools/steuerung/nummern.py` | neue Nummern gegen `origin/main` und die Remote-Zweige | 0 neue Nummern, 15 Zweige verglichen (sie legen zusammen 7 Nummern neu an), 0 Überschneidungen |
| `python3 tools/quelltext/textprobe.py --bereich c` | neue Treffer in der normativen Doku | 0 Treffer außerhalb der Ausnahmen, 0 durchgerutschte Fallen (3 ungenutzte Ausnahmen gehören zu Bereich a, nicht gefahren) |
| `bestand.py` · `linkprobe.py` · `pysyntax.py` | Form des Werkzeugbestands; Verweise; Python-Werkzeuge | 0 Befunde · 150 Verweise, 0 Abweichungen · 66 Werkzeuge, 0 Fehler, 0 Warnungen |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel, Bericht für die Commit-Nachricht | siehe Commit-Nachricht von R17-05 |

### R17-04 Antworten, Backlog, Abschluss (11.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| `grep -rn Review-Krypto-Sicherheit` über `docs/`, `tools/`, `server/`, `.github/`, `CLAUDE.md`, `README.md` | kein Verweis mehr auf die gelöschte Datei außer als Geschichte („gelöscht", „Git-Historie") | 7 Nennungen, alle in `Review-R17.md` als Fundstelle eines Befunds, in Q-R17-08 oder in 3.4 — keine in einem anderen Dokument |
| `uebersicht.py --ziel 12` und `--ziel 12b` | Nr. 146 weg, Nr. 263 nach 12b, Nr. 370 und 371 neu | Ziel 12: 0 Einträge; Ziel 12b: 7 (darunter 263, 370, 371); 63 offene, 0 ohne Grammatik, 0 ohne Ziel |
| `decken.py` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` · Prüfstand `klein` | wie zuvor — zwei Decken rissen beim ersten Lauf (Zuarbeitszeile 454 Zeichen; Trennzeile vor Nr. 154 mit Nr. 146 entfernt), behoben | 21/0 · 2 neue Nummern, 0 Überschneidungen · 0 neue Treffer · 0 Befunde · 150/0 · Prüfstand R17-04: 23 Riegel grün, 68 s, Baum `ea29dca…` = Baum von `30d7226`; Abschluss: Bericht in seiner Commit-Nachricht |

### R17-03 Fragen (11.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| `decken.py` · `textprobe.py --bereich c` · `linkprobe.py` | Form | 21/0 · 0 neue Treffer · 150/0 |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel | 23 Riegel grün, 0 rot, 0 nicht gemessen, 58 s; Baum `5955872…` = Baum von `bc08eda` |

### R17-02 Das Modell (11.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| `python3 tools/quelltext/textprobe.py --bereich c` | das neue Dokument im Bereich `c` — Land und Luft, Hausform, Adressen, Netz, Namen | erster Lauf 52 Treffer in 29 Zeilen (43 `luft`, 9 `hausform`); nach Korrektur der Hausform (9 × BetreiberIn) und zwei Ausnahmen mit Begründung (`spur-fachdokument-bedrohungsmodell`, `bedrohungsmodell-plattformen`): **0 außerhalb der Ausnahmen, 0 durchgerutschte Fallen**; ein Rest (`fenix5`) umformuliert |
| `textprobe.py --selbstprobe` | die Probe prüft sich selbst | 4/4 |
| `decken.py` · `bestand.py` · `linkprobe.py` · `pysyntax.py` | Form der Steuerung, des Werkzeugbestands, der Verweise, der Python-Werkzeuge | 21/0 · 0 Befunde · 150/0 · 63/0 |
| Lesung gegen die Gliederung (Konzept 3.1) | jeder der sechs Abschnitte vorhanden, jede I-Nummer des Inventars einem Abschnitt zugeordnet, jeder F-Befund an mindestens einer Stelle genannt | 6 von 6 Abschnitte; **28 von 31** Befunde genannt (gezählt mit `grep -o`) — die drei Sammelbefunde F-R17-29 bis -31 (Doku-Altstände, veraltete Krypto-Review, Hinweise) nennt das Modell nicht einzeln. *Bis R17-03 stand hier „31 von 31" — die Zahl war geschätzt, nicht gezählt (berichtigt).* |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel | 23 Riegel grün (die Ausnahmenliste bringt den Riegel `wortliste` dazu), 0 rot, 0 nicht gemessen, 63 s; Baum `60bfe49…` = Baum von `8a4c163` |

### R17-01 Bestandsaufnahme (11.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| drei lesende Leser (Opus), ohne Nebenwirkung | Code gegen Doku: Server und Browser (14 Punkte), Uhr und Android (6 Punkte), Kette und Betrieb (12 Punkte) | 129 + 71 + 105 Werkzeugaufrufe; 36 + 25 + 32 Bausteinzeilen; 16 + 16 + 23 Befunde; verdichtet auf 55 Bausteine und 31 Befunde |
| Nachprüfung Fable am Quelltext | die Hoch- und die bestätigbaren Mittel-Befunde | 11 nachgeprüft, 11 bestätigt (F-R17-01 bis -06, -11, -14, -15, -19, -21); 20 als Lesung gekennzeichnet |
| `gh api` (lesend) | `rules/branches/produktion`; Rulesets | `[]` — keine Regel auf dem Zeiger; Umgebungen und Organisation 403 |
| Zählung `uses:` | der Handbefehl aus `CLAUDE.md` 3 (beide Zählungen) | 14 von 14 |
| `decken.py` · `uebersicht.py --ziel 12` · `nummern.py` · `textprobe.py --bereich c` · `bestand.py` · `linkprobe.py` | wie R17-00 | 21/0 · 62/0/0, 2 zu Ziel 12 · 0 Überschneidungen · 0 neue Treffer · 0 Befunde · 150/0 |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel | 22 Riegel grün, 0 rot, 0 nicht gemessen, 53 s; Baum `63fef5e…` = Baum von `3b6f330` |

### R17-00 Buchführung (11.10.2026)

| Mittel | Was gemessen | Zahl |
|---|---|---|
| `python3 tools/steuerung/decken.py` | Decken der Steuerungsdokumente (Kopf des Rahmenplans, Backlog-Einträge, Verlaufszeile) | 21 Decken, 0 gerissen — die Verlaufszeile 154 riss beim ersten Lauf mit 333 Zeichen (Decke 300), gekürzt auf 283 |
| `python3 tools/steuerung/uebersicht.py --ziel 12` | Kopfzeilen; Zuordnung zu 12 | 62 offene Einträge, 0 ohne Grammatik, 0 ohne gültiges Ziel; 2 zu Ziel 12 (Nr. 146, 263) |
| `python3 tools/steuerung/nummern.py` | neue Nummern gegen `origin/main` und die Remote-Zweige | 0 neue Nummern, 8 Zweige verglichen, 0 Überschneidungen; kein offener PR (GitHub, 11.10.2026) |
| `python3 tools/quelltext/textprobe.py --bereich c` | neue Treffer in der normativen Doku | 0 Treffer außerhalb der Ausnahmen, 0 durchgerutschte Fallen (3 ungenutzte Ausnahmen gehören zu Bereich a, nicht gefahren) |
| `python3 tools/quelltext/bestand.py` · `linkprobe.py` | Form des Werkzeugbestands; Verweise | 0 Befunde · 150 Verweise, 0 Abweichungen |
| `bash tools/pruefstand/pruefen.sh --stufe klein` | die billigen Riegel, Bericht für die Commit-Nachricht | 22 Riegel grün, 0 rot, 0 nicht gemessen, 86 s; Baum `5fa9b53…` = Baum von `fca8549` |

## 3. Im Browser geprüft

Entfällt für diesen Schritt: kein Paket ändert die Oberfläche. Steht hier,
damit die Leerstelle sichtbar ist und nicht als Vergessen gelesen wird.

## 4. Prüfliste für die Betreiberin

Je Punkt: Bedienweg, erwartetes Ergebnis, woran ein Scheitern zu erkennen
ist. Abhaken, wenn geprüft.

| Nr. | Bedienweg | Erwartung | Scheitern erkennbar an | ☐ |
|---|---|---|---|---|
| P-R17-01 | `docs/Bedrohungsmodell.md` von vorn bis hinten lesen, mit dem eigenen Bild der Anlage daneben: Welche Lage fehlt? Welche Zusage steht dort anders, als Sie sie geben würden? | Keine Lage fehlt, keine Zusage ist zu weit; jede Grenze, die Sie kennen, steht benannt. | Eine Angreiferlage oder ein Klartextfeld, das Ihnen einfällt und im Modell nicht steht. | ☑ 11.10.2026 — freigegeben |
| P-R17-02 | Die acht Fragen Q-R17-01 bis -08 (Konzept 2.2) beantworten — je Zeile die Empfehlung annehmen oder anders entscheiden. | Jede Frage hat eine Antwort, die als E-R17-NN eingetragen ist und im Modell 6 steht. | Eine Frage ohne E-Nummer nach dem Abschluss; eine Antwort, die im Modell fehlt. | ☑ 11.10.2026 (E-R17-06 bis -13) |
| P-R17-04 | In GitHub: Settings → Environments → `produktion` und `staging`: Stehen Pflichtprüfer und eine Zweigbeschränkung (Deployment branches and tags)? Organisation `gen-em` → Authentication security: Ist der Zweitfaktor erzwungen? Auf Produktiv: Betrieb → Status, Zeile *Server-Anteil*; Betrieb → Servereinstellungen, CSP und HSTS. | Pflichtprüfer gesetzt, Zweige auf `main` und die drei Tag-Muster beschränkt; 2FA erzwungen; Anteil angelegt; CSP scharf, HSTS mindestens 180 Tage. | Eine fehlende Beschränkung bestätigt F-R17-04; ein fehlender Anteil F-R17-05. | ☐ |
| P-R17-05 | Die Wear-Release-APK aus der Kette (oder `appbau.sh`) mit `aapt2 dump permissions nadoku-uhr-<Fassung>.apk` ansehen. | Keine Zeile — die Wear-OS-Uhr hat keine Berechtigung (Modell G7). | Irgendeine `uses-permission`, vor allem `INTERNET`. | ☐ |
| P-R17-03 | Nach dem Merge: `docs/Bedrohungsmodell.md` ist aus `README.md` und `docs/Technik.md` erreichbar; die Links führen hin. | Beide Verweise öffnen das Modell. | 404 oder ein Verweis auf eine andere Datei. | ☐ |

| P-R17-06 | Auf dem Backup-Ziel (Betrieb → Backup-Ziele) und in `sicherungen/` nachsehen, ob noch ein Komplett-Backup (`.edk`, `.sql.gz`) liegt, das **vor dem 03.09.2026** erzeugt wurde (Dateiname trägt den Zeitpunkt). | Keines — dann ist F-R17-33 ein Aufräumposten (eine Zeile und ein Kommentar), kein Risiko. | Ein Dump von vor S7: Der Wiederanlauf verlangt für ihn keine Endmarke; vor dem Einspielen die letzte Zeile der Datei auf `-- EDKOMP-ENDE` prüfen (`zcat … \| tail -1`). | ☐ |

| P-R17-07 | Auf Produktiv: Betrieb → Sicherheit, Karte der Sperren und die Ereignisse der letzten 30 Tage; nach Zeilen mit Topf `login` und Art **Anschluss** (`ip:…`) sehen, besonders mit Stufe 2 bis 4. | Keine — oder nur solche, zu denen eine Konto-Sperre derselben Zeit gehört (ein echter Angriff auf ein Konto). | Anschlusssperren im Topf `login` ohne Konto-Sperre daneben: Dann hat F-R17-36 schon einen Anschluss mit mehreren NutzerInnen getroffen; die Sperre lässt sich auf derselben Seite aufheben, bis das Paket kommt. | ☐ |

| P-R17-08 | Auf Produktiv einen Einsatz öffnen, dessen Spur älter als 31 Tage ist (der Wartungslauf hat sie dann gepackt oder ausgedünnt; die Spurenseite des Tages zeigt „ausgedünnt · n von m Punkten"); im Aktionsmenü nachsehen; danach Betrieb → System, die Einträge der Art PHP, nach „Undefined array key" suchen. | Der Eintrag „GPS-Daten als GPX" steht im Menü, die Metazeile trägt die Plakette „GPS-Daten" oder „GPS-Daten ausgedünnt"; im Reiter System keine Zeile mit `n_gespeichert`. | Menüeintrag und Plakette fehlen, obwohl die Karte die Spur zeichnet; im Reiter System Zeilen „Undefined array key n_gespeichert" aus `einsatz.php` (eine je Aufruf) und „Undefined variable $platzhalter" aus `tageszuordnung_lib.php` (zwei je Umdatierung): Dann sind F-R17-39 und -38 auf Produktiv; der Abruf geht bis zum Paket über die Spurenseite des Tages. | ☐ |

| P-R17-09 | Auf Produktiv Betrieb → Protokoll, Reiter Verwaltung, Art „Wiederherstellungscodes erneuert" (`totp_codes_erneuert`) und „Passkey angelegt" über den ganzen Zeitraum durchsehen; jede Zeile dem Konto nennen, das sie betrifft, und fragen, ob die Person den Handgriff selbst getan hat. | Jede Zeile gehört zu einem eigenen Handgriff der Person (neue Codes nach dem Einrichten, ein verlegtes Codeblatt). | Eine Zeile, die die Person nicht kennt: Dann hat jemand mit ihrer Sitzung Codes erzeugt (F-R17-43) — Zweitfaktor über die Kontoseite zurücksetzen, Passwort wechseln lassen (das beendet alle Sitzungen), und das Paket zu F-R17-43 vorziehen. | ☐ |

| P-R17-10 | Auf Produktiv ohne Anmeldung `https://<host>/ueberlast.json` abrufen (`curl -sI`), und während der nächsten Wartung ebenso `https://<host>/wartung.lock`. | Beide Abrufe antworten 403 oder 404 — nach dem Paket zu F-R17-47; bis dahin ist 200 mit JSON der erwartete Befund, und er bestätigt F-R17-47 auf Produktiv. | 200 mit JSON nach dem Paket: Dann greift die `FilesMatch`-Liste auf Produktiv nicht, und die `.htaccess` ist dort anders als im Repositorium (oder `AllowOverride` fehlt) — die Datei auf dem Server mit der im Repositorium vergleichen. | ☐ |
| P-R17-11 | Auf dem Prüf-Handy, **nicht im Dienst:** Dienst beginnen, dann den Akkustand unter 15 % bringen (`adb shell dumpsys battery unplug` und `adb shell dumpsys battery set level 12`; ohne adb warten, bis er dort steht), in der Meldung „Akku bei 15 %" auf „Dienst beenden" tippen, danach die App öffnen und im Web den Diensttag ansehen. Zum Schluss `adb shell dumpsys battery reset`. | Nach dem Paket zu F-R17-54: Die App zeigt „Dienst beginnen", der Diensttag im Web hat ein Ende. **Bis dahin ist das Scheitern der erwartete Befund** — dann den Diensttag im Web schließen. | Die App zeigt „Dienst läuft" ohne Dauermeldung und ohne GPS; der Diensttag im Web steht ohne Ende; ein weiteres „Dienst beginnen" setzt ihn fort. | ☐ |

*Stück 7 (R17-11) bringt keinen Prüfpunkt für die Betreiberin: F-R17-50 und -51 sind am Klon messbar und dort gemessen. Stück 8 (R17-12) ebenso keinen: F-R17-52 ist ein Fall für den Rundlauf im Simulator, nicht für eine Uhr im Dienst — wer ihn auf der echten Uhr nachspielt, riskiert genau das verlorene Paket, das er sucht; F-R17-53 zeigt sich nur, wenn er schon eingetreten ist (Zeile „Verschlüsseln geht nicht" auf der Sync-Seite bei funktionierendem Gerät — dann bitte melden, statt neu zu koppeln). Stück 9 (R17-13) bringt **P-R17-11** (F-R17-54 am Gerät — nach dem Paket, bis dahin ist das Scheitern der erwartete Befund). Weitere Punkte kommen mit den Stücken 10 bis 12 und der Freigaberunde (R17-14 bis -17).*

## 5. Grenzen der benutzten Prüfmittel

- **`decken.py`, `uebersicht.py`, `nummern.py`** messen Form, nicht Inhalt:
  Kopfzeilen, Zeilenzahlen, Nummernkollisionen. Eine falsche Zuordnung mit
  richtiger Grammatik ist für sie grün.
- **Die Textprobe** (`textprobe.py`, Bereich `c`) meldet nur **neue**
  Treffer gegen den Altbestand; sie liest `docs/Bedrohungsmodell.md` erst,
  wenn R17-02 die Datei im Bereich eingetragen hat — und das ist der Zweck
  des Eintrags im selben Paket (`Pruefablauf.md` 6.6).
- **Der Prüfstand** (`pruefen.sh`, Stufe `klein`) läuft ohne `server/`-
  Berührung über die 20 billigen Riegel; er misst nicht, ob ein Satz im
  Modell wahr ist.
- **PHPStan** (Stück 2 ff., örtlich, E-R17-16): Stufe 4 ohne Bootstrap-Datei
  und ohne `vendor/`. Es kennt die Variablen nicht, die eine eingebundene
  Datei setzt (`$userId` aus `auth_guard.php`, `$pdo`), und nicht die
  Klassen unter `vendor/` (phpseclib, ConstantTime) — beides Rauschen, das
  je Stück ausgezählt und abgezogen wird. Es findet Typwidersprüche und
  tote Zweige; es findet keinen fehlenden Aufruf (F-R17-32 kam aus der
  Lesung) und keinen Fehler, der nur zur Laufzeit sichtbar ist.
- **Die Verweis- und Aufruferzähler** (`grep`, Konzept 7.4) sind Listen,
  kein Spürsinn: Der Verweiszähler trifft auch Fachbegriffe, die wie ein
  Verweis aussehen, und zählt Zeilen, nicht Absätze; der Aufruferzähler
  sieht keinen Aufruf über eine Zeichenkette und keine Methode, deren Name
  anderswo ein Wort ist. Beide Zahlen sind Orientierung; was als
  Karteileiche gilt, steht mit Gegenprobe in 7.3.

## 6. Was aus dem Schritt offen bleibt

**Schritt 12b (seit 20.10.2026):** die Stücke 2 bis 12 (Konzept 7.3, je
Paket R17-06 bis -16; **Stück 2 gelesen am 20.10.2026**, F-R17-32 bis -35,
P-R17-06; **Stück 3 gelesen am 20.10.2026**, F-R17-36 und -37, F-R17-32
verschärft, P-R17-07; **Stück 4 gelesen am 20.10.2026**, F-R17-38 bis -41,
K-10 gegenstandslos, P-R17-08; **Stück 5 gelesen am 20.10.2026**, F-R17-42 bis -45,
F-R17-07 und -08 bestätigt, P-R17-09; **Stück 6 gelesen am 20.10.2026**, F-R17-46 bis -49,
F-R17-19 und -21 bestätigt, P-R17-10; **Stück 7 gelesen am 20.10.2026**, F-R17-50 und -51,
F-R17-24 bestätigt, Nr. 341 gemessen; **Stück 8 gelesen am 20.10.2026**, F-R17-52 und -53,
F-R17-11, -12, -13 bestätigt, F-R17-28 zur Hälfte erledigt, Uhr-Prüfstand 99 von 99; **Stück 9 gelesen am 20.10.2026**, F-R17-54,
F-R17-27 und -31 bestätigt, AN-5 beantwortet, Gradle 280 und 72 Fälle grün, P-R17-11), dann die Freigaberunde R17-17; was die Stücke nicht
prüfen können, kommt in Abschnitt 1, die Vorsortierung mit ihren Grenzen
in Abschnitt 5, Prüfpunkte für die Betreiberin ab P-R17-06 in Abschnitt 4.
Für Stück 3 bleibt P-R17-04 (GitHub-Umgebungen, Org-Zweitfaktor, Produktiv-
Einstellungen) die Lücke, die nur die Betreiberin schließt.

**Aus Schritt 12:**

- **31 Befunde** (Konzept 2.3) für die Freigaberunde von 12b — ohne Sofortpaket (E-R17-06); die fünf hohen (F-R17-01 bis -05) stehen bis dahin offen, F-R17-04 und -05 als Zuarbeiten der Betreiberin in Rahmenplan 6.1.
- **Zwei Entscheidungen mit Backlog-Nummer** für 12b: Nr. 370 (`CryptoKey`, E-R17-08) und Nr. 371 (Admin-Riegel, E-R17-11); **Nr. 263** offen für 12b (E-R17-12).
- **Für S11** (12a): E-R17-09 (Zweck Server) und E-R17-10 (Klartextliste) als Eingang; Modell 6.2 bis 6.4 nennen den Schlüsselweg (drei Antworten) und die unbelegte Garmin-Krypto (Probe-App im Uhr-Prüfstand zuerst).
- **Nicht prüfbar hier** (Abschnitt 1): die zwölf Punkte, voran die Garmin-Krypto und die GitHub-Umgebungen; Prüfwege stehen daneben.
- **Die Prüfliste** (Abschnitt 4): P-R17-01 (Freigabe), -02 (erledigt mit den Antworten vom 11.10.2026), -03 (Verweise nach dem Merge), -04 (GitHub und Produktiv), -05 (Wear-APK).
