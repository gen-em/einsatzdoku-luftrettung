# Dokumentenprobe — Ausnahmen

Pfade (und Zahlen), die in einem Dokument stehen dürfen, obwohl es sie im
Repositorium nicht gibt. Jede Zeile ist ein regulärer Ausdruck über den
ganzen Pfad (bei `Zahl` über den Satzteil), dazu Klasse und Grund. Eine
Zeile, die nichts mehr trifft, ist ein Befund — die Liste schrumpft mit dem
Bestand. Gelesen von `dokumente.py` (R17-27, Nr. 188).

**Die Klassen.** `Server`: entsteht nur auf der Anlage, steht in
`.gitignore` oder in der Ausnahmeliste der Kette. `Kette`: Zustand der
Auslieferung auf dem FTP-Server, außerhalb des Webroots. `Adresse`: ein Pfad
der Anwendung als URL, keine Datei. `Format`: Bestandteil einer Datei, die
die Anwendung oder ein Werkzeug erzeugt. `Fremd`: außerhalb des
Repositoriums — System, Gerät, SDK, fremdes Paket. `Früher`: ausdrücklich als
früherer Stand genannt, im Satz erkennbar. `Beispiel`: ein Platzhalter.

| Muster | Klasse | Grund |
|---|---|---|
| `(server/)?config(\.neu)?\.php` | Server | Konfiguration der Anlage, nie im Repositorium (`CLAUDE.md` 3); `config.neu.php` ist der Zwischenschritt des Schlüsselwechsels im Runbook |
| `/?(server/)?\.sitzungen/` | Server | PHP-Sitzungsdateien (Schritt 16, E-SA-05) |
| `(server/)?\.zustand/(ueberlast\.json)?\|ueberlast\.json` | Server | Schalter und Zähler der Anlage (R17-22, F-R17-47) |
| `(server/)?sicherungen/((eingang\|komplett\|protokoll\|\.schluesselwechsel)/)?` | Server | Ablage der Sicherungen auf der Anlage |
| `eingang/(\.arbeit/dump\.sql)?` | Server | Eingang des Wiederherstellens unter `sicherungen/` |
| `(server/)?apk/` | Server | die ausgelieferten APKs, nur auf Produktiv (`CLAUDE.md` 3) |
| `(server/)?bilder/(x\.png)?\|/bilder/x\.png` | Server | Bildablage der Anlage; `x.png` ist das Beispiel einer Sperrregel |
| `\.user\.ini\|php\.ini` | Server | Einstellungsdateien des Hosters, nicht der Anwendung |
| `\.well-known/(acme-challenge/)?` | Server | Ordner des Hosters für Zertifikate |
| `(\.\./\|/)?\.(deploy-state-(staging\|produktion)\|ftp-deploy-sync-state)\.json` | Kette | Zustandsdateien der Auslieferungsaktion auf dem FTP-Server (`Technik.md` 12.9) |
| `\.\./\|\./(staging\|httpdocs)/` | Kette | Verzeichnisse oberhalb und neben dem Webroot des Hosters |
| `\.zielprobe-gespraech/` | Kette | Ausgabe der Zielprobe im Lauf, nicht im Repositorium |
| `hotfix/` | Kette | Vorsilbe eines Zweigs (`hotfix/*`), kein Ordner |
| `/api/(health\.php)?\|/login\.php` | Adresse | Pfade der Anwendung als URL |
| `konto\.json\|manifest\.json\|kopf\.json\|(eintraege\|spuren)/0001\.json\|tracks/\|LIESMICH\.txt` | Format | Bestandteile der Sicherungs- und Exportarchive (`Backup-Format.md`, `Export-Format.md`) |
| `lauf\.json\|rc\.json\|sendeplan\.json\|generator/ausgabe/` | Format | Ausgaben der Werkzeuge des Referenzdatensatzes beim Lauf |
| `\.bau/\|emulator-bilder/\|build/.*\|watch/bin/\|signatur\.properties` | Format | Bau- und Prüfausgaben (`.gitignore`); `signatur.properties` liegt nur örtlich neben `android/LIESMICH.md` |
| `\.git/\|\.claude/settings\.local\.json` | Fremd | Verwaltung von Git und Claude Code, nicht im Baum |
| `/(opt\|usr\|etc)/.*` | Fremd | Pfade im Container (`Sandbox-Setup.md`) |
| `GARMIN/(Apps\|APPS)/\|Devices/\|Fonts/\|compiler\.json\|first_stage_ramdisk/` | Fremd | Ordner auf der Uhr, im Connect-IQ-SDK und im Emulatorabbild |
| `HashDiff\.js\|deploy\.js\|syncProvider\.js` | Fremd | Quelltext der Aktion `@samkirkland/ftp-deploy`, nachgelesen in `Technik.md` 12.7 |
| `composer\.json\|org\.json\|dist/jsQR\.js\|LICENSE\.txt\|disposable_email_blocklist\.conf` | Fremd | Dateien in fremden Paketen, deren Herkunft `Lizenzen.md` nennt |
| `styles\.xml` | Fremd | Teil einer Excel-Datei |
| `gpx\.php\|(server/)?gpx_lib\.php\|site_elevation_lib\.php` | Früher | bis Web 22.2.0 bzw. 22.7.0, im Satz als „bis" genannt |
| `(api/\|assets/)?ortsumzug(_lib)?\.(php\|js)\|tools/proben/ortsumzug/\|tools/proben/gpx/probe\.php` | Früher | der Ortsumzug bis Web 23 (S11-12) und die alte GPX-Probe |
| `assets/openlocationcode\.js` | Früher | bis Web 23.5.0 dort, seither unter `vendor/` (F-R17-51) |
| `admin_stammdaten\.php\|info\.php` | Früher | entfernte Seiten, im Satz als frühere genannt |
| `docs/Branding\.md\|Umstellung-Backup\.md\|docs/konzepte/konzept-(p5c\|rw)/` | Früher | gelöschte Dokumente, mit Commit des letzten Stands |
| `(\.github/workflows/)?deploy\.yml` | Früher | mit Web 20.4.0 gelöscht |
| `tools/containeraufbau/(aufbau\.sh)?\|aufbau\.sh` | Früher | bis PK-02, seither `tools/sandbox/aufbauen.sh` (`Sandbox-Setup.md` 1.2) |
| `referenz/altformat/` | Früher | mit S11-12 entfernt (Backlog Nr. 46) |
| `HemsApp\.mc\|MapPage\.mc\|ClockView/SpeedView/StatsView/SyncView/CprView\.mc` | Früher | Dateinamen der Uhr-App vor der Umbenennung, im Satz als frühere genannt |
| `x/\|Ziel` | Beispiel | Platzhalter in einer Regel (`x/**`) und im Linkmuster `[Text](Ziel)` |
| `Einundzwanzig Dateien unter .server/api/.` | Früher | Zahl der Endpunkte vor Schritt 15 AP3, im Satz als „fingen … an" genannt (`Technik.md` 4.5) |
