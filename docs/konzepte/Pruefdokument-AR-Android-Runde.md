# Prüfdokument AR — Android-Runde

*Gehört zu `Konzept-AR-Android-Runde.md`. Beantwortet die Frage „was muss
**ich** noch tun?"; das Protokoll im Konzept beantwortet „ist es belegt?".
Stand: **25.09.2026, nach AR-05 — Endfassung für den Pull Request.**
Android 0.16.0.*

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es stattdessen hingehört |
|---|---|---|
| **Die Uhr auf Android 17** | Das einzige Wear-OS-Abbild mit API 37 (`android-wear-signed`) ist ein `user`-Build: `ro.adb.secure=1`, `ro.debuggable=0`. Ohne Root lässt sich der Watchdog-Faktor nicht setzen, ohne den unter reiner Software-Emulation kein Abbild bootet; `adb` blieb über zehn Minuten `offline`. Gefahren wurde Wear OS 5 (API 34) | Gerätetest (P-AR-12) |
| **„Handy nicht erreichbar" auf dem Emulator** | Wear OS 5 meldet ohne Telefon „Handy verbunden" — die Anzeige folgt einer zugestellten Nachricht (B-S4-09); woran sie dort zugestellt wurde, ist ungeklärt (F-AR-16). Den Zustand „nicht erreichbar" zeichnet deshalb der Bilderlauf | Bilderlauf belegt (P-AR-07); Gerätetest für F-AR-16 (P-AR-13) |
| **Die Netzweg-Punkte von Android 17** — Encrypted Client Hello, Certificate Transparency als Vorgabe | Der Emulator sprach nur mit der örtlichen Installation über Klartext-HTTP (Prüf-APK); beide Punkte wirken nur gegen einen echten Server mit Zertifikat | Gerätetest mit dem Release-APK gegen den Produktivserver (P-AR-03, -04) |
| **Die Datenschutzseite im Browser** | Der Knopf übergab an Chrome — der stand auf seiner Ersteinrichtung; `datenschutz.php` kam am Server nicht an (0 Abrufe) | Gerätetest (P-AR-14) |
| **Der Cursor in Orange tief** (AR-04) | Kein Bildschirm zeigt ein Eingabefeld: `Eingabefeld` hat seit R63 keinen Aufrufer (F-AR-15, Nr. 336) | entfällt, solange es keinen Aufrufer gibt |
| **Ein Bau ohne Spiegel** | Die Arbeitsumgebung holt Maven Central über Googles Spiegel (E-AR-13). Dass der Spiegel dieselben Dateien liefert, ist an einer Stichprobe belegt (SHA-1 des Robolectric-Abbilds dreifach gleich), nicht für jede Datei | Bau auf einer Maschine ohne Drosselung (P-AR-01) |
| **Die Rundlauffälle gegen die Installation** | Sie überspringen sich ohne `-Pnadoku.rundlauf` (15 je Bauart). Die Berichtigung in `KopplungRundlaufTest` (F-AR-11) ist übersetzt, nicht gelaufen | P-AR-15 |

## 2. Maschinell geprüft

| Paket | Mittel | Ergebnis |
|---|---|---|
| AR-01 | `tools/sandbox/aufbauen.sh android` | 22 s, rc 0, 18 von 18 Stücken, 8 von 8 Umgebungswerten |
| AR-01 | `./gradlew build` am unveränderten Baum, Zählung aus Lint-XML und JUnit-XML | Handy 0 Fehler / 14 Warnungen, Uhr 0 / 0; 264 (15 übersprungen) und 71 Fälle je Bauart — grün erst im **fünften** Anlauf (F-AR-01) |
| AR-01 | zweiter Vollbau `--offline --rerun-tasks` | dieselbe Zählung; Handy-APK und 78 Bilder byteweise gleich (5 min 18 s) |
| AR-02 | Prüfsumme Gradle 9.7.1 | offizielle Datei und eigene Rechnung gleich (`acd53f1e…`, 151 433 392 B); Wrapper-JAR byteweise die aus der Verteilung (`7a9ce74c…`) |
| AR-02 | `./gradlew help --warning-mode all` | 0 Veraltungen (vorher 12, alle umgestellt) |
| AR-02 | `./gradlew build --rerun-tasks` mit 0.15.1 | grün; 264 / 71 je Bauart wie vorher, `testReleaseUnitTest` läuft; 0 Kotlin-Warnungen nach Behebung von 4 |
| AR-02 | APK Eintrag für Eintrag gegen AR-01 | Handy 161 von 175 gleich, Uhr 176 von 190; verschieden nur DEX, Kotlin-Builtins, Baseline-Profil, `app-metadata.properties`; Manifest und Ressourcen gleich |
| AR-02 | `aapt2 dump badging` | Handy `versionCode 1600`, Uhr `1001600`, `versionName 0.16.0` |
| AR-03 | Durchsicht der 17 Verhaltensänderungen von Android 17 (`grep` je Punkt) | 14 ohne Wirkung, 3 betreffen den Netzweg |
| AR-03 | Bilderlauf Release gegen Release | 73 von 78 byteweise gleich; 5 nur in der Fassungszeile (Kästchen 29–31 × 17–19 px, angesehen) |
| AR-03 | `aufbauen.sh android` nach der Änderung | 3 s, rc 0, **21 von 21 Stücken** |
| AR-04 | `werkzeuge/kontraste.py --selbstprobe` | **5 Fälle, 0 Fehlschläge** (B-S5Z-13, B-S5Z-15, Farbe ohne Rolle, Paar unter Zielwert, Gegenprobe) |
| AR-04 | `werkzeuge/kontraste.py` | erster Lauf **4 Befunde** (2 echte Kontrastfehler); danach **30 Paare, 0 Befunde, 125 Farbstellen, 0 ohne Rolle** |
| AR-05 | Bildfall `uhr-handy-fehlt-192dp` | Statuszeile: **553 Bildpunkte Rosa, 0 Rot**; Rosa auf Asphalt 15,94 : 1 |
| AR-05 | **Endstand** `./gradlew build --rerun-tasks` | grün in 4 min 54 s; **Lint 0 Fehler / 0 Warnungen** in beiden Modulen; Handy **267** (15 übersprungen), Uhr **72**, je Bauart, 0 Fehlschläge; 0 Kotlin-Warnungen; Bilder 72 + 7 je Bauart; APK Release Handy 9 197 370 B, Uhr 22 743 506 B |
| AR-05 | Prüfstand (`tools/pruefstand/pruefen.sh`) | der Bericht steht in der Commit-Nachricht des Kopfs; Zahlen dort |

**Emulator** (AR-05, E-AR-11) — Handy auf **API 37** (`google_apis`), Uhr
auf Wear OS 5:

| Was | Ergebnis |
|---|---|
| Boot Handy | 1 420 s, zweiter Boot 526 s; drei SurfaceFlinger-Abstürze bis zur Drei-Tasten-Navigation, danach keiner mehr (F-AR-14, Nr. 337) |
| Kopplung | Code `EKG G89`, Konto 1, „Ja, koppeln", `devices`-Zeile am Server; über `127.0.0.1` ohne die neue Berechtigung für lokales Netz |
| Einstellungen → Rechtliches | „von der BetreiberIn des Servers", „Fassung 0.16.0-pruef" — P-PK-28 |
| Vordergrunddienst unter `targetSdk` 37 | `isForeground=true`, `types=0x00000008`; „GPS empfängt", 27 Punkte, 4,3 km |
| Dienstende | Rückfrage, `POST /ingest.php` 200, Diensttag `ended_at` gesetzt, Segment `final = 1` |
| Boot Uhr | 864 s (Wear OS 5); Startseite auf rundem Glas |

## 3. Prüfliste

| Nr. | Was | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-AR-01 | Der Bau läuft ohne Spiegel | `cd android && ./gradlew build` auf einem Rechner mit JDK 21 und Android-SDK (Plattform 37.0) | `BUILD SUCCESSFUL`, 0 Lint-Fehler, 267 / 72 Fälle | `Could not resolve` oder andere Zahlen | offen |
| P-AR-02 | Die Release-Prüffälle laufen weiter | Baulog nach `:handy:testReleaseUnitTest` | Aufgabe läuft, 267 Fälle | Aufgabe fehlt | **belegt** |
| P-AR-03 | Verbindung unter Android 17 (Certificate Transparency) | Release-APK auf einem Gerät mit Android 17 koppeln, einen Dienst senden | Kopplung und Senden wie vorher | Zertifikatsfehler oder „Keine Verbindung" nur auf 17 | offen — Gerätetest |
| P-AR-04 | Encrypted Client Hello stört nicht | wie P-AR-03 | wie P-AR-03 | wie P-AR-03 | offen — Gerätetest |
| P-AR-05 | Das Prüf-APK erreicht die örtliche Installation ohne Berechtigung für lokales Netz | Emulator, `adb reverse tcp:8080 tcp:8080`, Kopplung | Kopplung gelingt | Verbindungsfehler | **belegt** (AR-05) |
| P-AR-06 | Singular in den Zahlentexten | `MehrzahlTest`; am Gerät die letzte Minute der Kopplung abwarten | „Noch 1 Minute", „Noch 1 Sekunde" | „Noch 1 Minuten" | Prüffall **belegt**; Gerät offen |
| P-AR-07 | „Handy nicht erreichbar" in Rosa | `uhr/build/bilder/release/uhr-handy-fehlt-192dp.png` ansehen; am Gerät: Uhr ohne Handy | helle, gut lesbare Zeile | rote Zeile | Bilderlauf **belegt**; Gerät offen |
| P-AR-09 | Das Kontrastwerkzeug fährt | `python3 android/werkzeuge/kontraste.py` und `--selbstprobe` | 0 Befunde; 5 von 5 | `FEHLT`, `ROLLE?` oder `!` | **belegt** |
| P-AR-10 | Der Vordergrunddienst unter `targetSdk` 37 | Dienst beginnen und beenden | Dauermeldung, Aufzeichnung, Dienstende kommt am Server an | Absturz, keine Meldung, `ended_at` leer | Emulator **belegt**; Gerät offen (zwölf Stunden, Akku) |
| P-AR-11 | Die Uhr-APK ist 3,2 MB größer | Installation auf einer Galaxy Watch | installiert und startet | Speichermangel, langsamer Start | offen — Gerätetest |
| P-AR-12 | Die Uhr unter Wear OS mit API 37 | Uhr-APK auf einer Uhr mit Wear OS 7 | Startseite, Knöpfe, Sperre wie auf Wear OS 5 | Absturz, abgeschnittener Knopf | offen — Gerätetest |
| P-AR-13 | Woher meldet Wear OS 5 „Handy verbunden" ohne Telefon? (F-AR-16) | Uhr ohne gekoppeltes Telefon, Dienst beginnen | „Handy nicht erreichbar" | „Handy verbunden" | offen — Gerätetest |
| P-AR-14 | Die Rechtstexte öffnen im Browser | Einstellungen → Rechtliches → Datenschutzerklärung | die Seite des Servers lädt | leerer Browser, Fehlerseite | offen — Gerätetest |
| P-AR-15 | Die Rundlauffälle | `./gradlew :handy:testDebugUnitTest --rerun-tasks -Pnadoku.rundlauf=http://127.0.0.1:8080/` mit örtlicher Installation | 0 übersprungen, 0 Fehlschläge | Fehlschlag in `KopplungRundlaufTest` | offen |
| P-PK-28 | Die Android-Zeile aus PK-04/5b hat ihre Nummer (Nr. 284) | `version.properties`, Changelog, Emulator: Einstellungen → Rechtliches | 0.16.0, `[Android 0.16.0]`, Bild mit „BetreiberIn" | 0.15.1, kein Bild | **belegt** (AR-05). **In `Pruefdokument-PK-Pruefkette.md` noch abzuhaken** (E-AR-02) |

*(P-AR-08 — der Cursor im Eingabefeld — ist gestrichen: Den Cursor zeigt
kein Bildschirm, F-AR-15.)*

## 4. Grenzen der benutzten Prüfmittel

- **Der Bilderlauf** zeichnet das gerechnete Bild unter Robolectric
  (`sdk=34`), nicht das gelaufene. „73 von 78 gleich" heißt: Die neuen
  Bibliotheken verschieben nichts an dem, was Compose rechnet — nicht, dass
  die App auf einem Gerät gleich aussieht. Das zeigen die Emulator-Bilder.
- **Der Emulator** läuft ohne Beschleunigung und auf einem Abbild, dessen
  Grafik mit dem Emulator 37.1.11 nicht zusammenpasst (Nr. 337). Die
  Drei-Tasten-Navigation ist eine Umgehung; ein echtes Gerät nutzt meist
  Gestennavigation.
- **Die Lint-Zählung** zählt, was Lint meldet — Robolectric 4.17 meldet es
  nicht (F-AR-10). Und „0 Warnungen" hält nur, bis draußen eine neuere
  Fassung erscheint.
- **`kontraste.py`** kennt die Rolle, nicht den Grund: Eine bekannte Schrift
  auf einem neuen Grund meldet es nicht. Und es hängt an keinem Lauf
  (Nr. 334).
- **Der APK-Vergleich** sagt, welche Einträge sich geändert haben, nicht,
  ob der geänderte Code sich gleich verhält. Das sagen die Prüffälle.
