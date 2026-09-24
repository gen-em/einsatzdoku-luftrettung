# Prüfdokument AR — Android-Runde

*Gehört zu `Konzept-AR-Android-Runde.md`. Beantwortet die Frage „was muss
**ich** noch tun?"; das Protokoll im Konzept beantwortet „ist es belegt?".
Stand: **24.09.2026, nach AR-03.** Wird je Paket fortgeschrieben.*

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es stattdessen hingehört |
|---|---|---|
| **Der Emulator zu AR-02** | Absichtlich ausgelassen (E-AR-11): Er läuft einmal, in AR-05, am auszuliefernden Stand. AR-02 ist ein Zwischenstand, der nie einzeln ausgeliefert wird | Stattdessen belegt: 78 von 78 Bildern des Bilderlaufs byteweise gleich, Manifest und Ressourcen beider APKs byteweise gleich (2.) |
| **Die drei Netzweg-Punkte von Android 17** (P-AR-03 bis -05) | Der Emulator hat keinen Weg zum Produktivserver und kein Gerät mit echtem Zertifikatsweg; ECH und Certificate Transparency wirken nur gegen einen echten Server | Gerätetest; P-AR-05 im Emulatorlauf (AR-05) |
| **Ein Bau ohne Spiegel** | Maven Central drosselt den Container (F-AR-01); AR-02 baut über Googles Spiegel (E-AR-13). Dass ein Bau ohne Spiegel dieselben Dateien bekommt, ist an **einer** Stichprobe belegt (SHA-1 des Robolectric-Abbilds dreifach gleich), nicht für jede Datei | ein Bau auf einer Maschine ohne Drosselung, z. B. beim Signieren (PK-08) |
| **Die Rundlauffälle gegen eine echte Installation** | Nicht Gegenstand von AR-02; sie überspringen sich ohne `-Pnadoku.rundlauf` (15 je Bauart). Der eine behobene Fehler in `KopplungRundlaufTest` (3.) ist nur übersetzt, nicht gelaufen | AR-05, mit örtlicher Installation |

## 2. Maschinell geprüft

| Paket | Mittel | Ergebnis |
|---|---|---|
| AR-01 | `tools/sandbox/aufbauen.sh android` | 22 s, rc 0, 18 von 18 Stücken, 8 von 8 Umgebungswerten |
| AR-01 | `./gradlew build` am unveränderten Baum, Zählung aus Lint-XML und JUnit-XML | Handy 0 Fehler / 14 Warnungen, Uhr 0 / 0; 264 (15 übersprungen) und 71 Fälle je Bauart, 0 Fehlschläge — grün erst im **fünften** Anlauf (F-AR-01) |
| AR-01 | zweiter Vollbau `--offline --rerun-tasks` | dieselbe Zählung; Handy-APK und 78 Bilder byteweise gleich (5 min 18 s) |
| AR-02 | Prüfsumme Gradle 9.7.1 | offizielle Datei und eigene Rechnung gleich (`acd53f1e…`, 151 433 392 B); Wrapper-JAR byteweise die aus der Verteilung (`7a9ce74c…`) |
| AR-02 | `./gradlew help --warning-mode all` | 0 Veraltungen (vorher 12, alle umgestellt) |
| AR-02 | `./gradlew build --rerun-tasks` (mit 0.15.1 gebaut) | grün in 4 min 25 s; Handy 0 Fehler / 13 Warnungen, Uhr 0 / 1; **264 (15 übersprungen) und 71 Fälle je Bauart — wie vorher, `testReleaseUnitTest` läuft**; 0 Kotlin-Warnungen (nach Behebung von 4) |
| AR-02 | APK Eintrag für Eintrag gegen AR-01 | Handy 161 von 175 gleich, Uhr 176 von 190; verschieden nur DEX, Kotlin-Builtins, Baseline-Profil, `app-metadata.properties`; weg `kotlin-tooling-metadata.json`; **Manifest und Ressourcen gleich** |
| AR-02 | Bilderlauf SHA-256 gegen AR-01 | **78 von 78 byteweise gleich** |
| AR-02 | `aapt2 dump badging` mit 0.16.0 | Handy `versionCode 1600`, Uhr `1001600`, `versionName 0.16.0` |
| AR-03 | `./gradlew build --rerun-tasks` | grün in 4 min 57 s; **Lint 0 Fehler / 0 Warnungen in beiden Modulen** (Ausgangsmaß 14); Prüffälle Handy **267** (15 übersprungen), Uhr 71, je Bauart, 0 Fehlschläge — die drei neuen sind `MehrzahlTest`, grün in Debug und Release; 0 Kotlin-Warnungen |
| AR-03 | Bilderlauf, Release gegen Release (Ausgangsmaß), SHA-256 und `compare -metric AE` | **73 von 78 byteweise gleich**; 5 weichen ab, jeweils in einem Kästchen von 29–31 × 17–19 Pixeln an der Fassungszeile (253 bzw. 296 Pixel) — angesehen |
| AR-03 | APK-Größen | Handy 7 868 394 → **9 197 370 B**, Uhr 19 574 402 → **22 743 506 B** |
| AR-03 | Durchsicht der 17 Verhaltensänderungen von Android 17 gegen den Quelltext (`grep` je Punkt) | 14 ohne Treffer; 3 betreffen den Netzweg (P-AR-03 bis -05) |
| AR-03 | `werkzeuge/kontraste.py`, `farbabgleich.py` | 24 Paare, 0 unter Zielwert; 0 Abweichungen, 0 eigene Farbwerte |
| AR-03 | `bash tools/sandbox/aufbauen.sh android` (nach der Änderung) | 3 s, rc 0, **21 von 21 Stücken**, darunter Plattform 37.0, JDK 21, Gradle-Spiegel |

## 3. Prüfliste — was die Betreiberin noch tun kann

| Nr. | Was | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-AR-01 | Der Bau läuft auf einer Maschine ohne Spiegel | `cd android && ./gradlew build` auf einem Rechner mit JDK 21 und Android-SDK | `BUILD SUCCESSFUL`, 0 Lint-Fehler, Prüffallzahlen wie in 2. | Auflösefehler (`Could not resolve`) oder andere Prüffallzahlen | offen |
| P-AR-02 | Die Release-Prüffälle laufen weiter | im Baulog nach `:handy:testReleaseUnitTest` suchen | Aufgabe läuft, 264 Fälle | Aufgabe fehlt im Log | **belegt** (AR-02, 2.) |
| P-AR-03 | Verbindung zum Server unter Android 17 (Certificate Transparency als Vorgabe) | App auf einem Gerät mit Android 17 koppeln und einen Dienst senden | Kopplung und Senden gelingen wie vorher | „Keine Verbindung" oder Zertifikatsfehler nur auf Android 17 | offen — Gerätetest |
| P-AR-04 | Encrypted Client Hello stört nicht | wie P-AR-03 | wie P-AR-03 | wie P-AR-03 | offen — Gerätetest |
| P-AR-05 | Das Prüf-APK erreicht die örtliche Installation ohne die neue Berechtigung für das lokale Netz | Emulator (AR-05): `adb reverse tcp:8080 tcp:8080`, Kopplung gegen `127.0.0.1:8080` | Kopplung gelingt — 127.0.0.1 ist Loopback, nicht lokales Netz | Verbindungsfehler nur mit `targetSdk` 37 | offen — AR-05 |
| P-AR-06 | Singular in den Zahlentexten | `MehrzahlTest`; am Gerät: Kopplung starten und die letzte Minute abwarten | „Noch 1 Minute", „Noch 1 Sekunde" | „Noch 1 Minuten" | Prüffall **belegt** (AR-03); Gerät offen |
| P-PK-28 | Die Android-Zeile aus PK-04/5b hat ihre Nummer (Nr. 284) | `android/version.properties`, `docs/CHANGELOG.md`; Emulator: Einstellungen → Rechtliches | Nummer > 0.15.1, Changelog-Zeile `[Android …]`, ein Bild zeigt „von der BetreiberIn des Servers" | Nummer 0.15.1, kein Bild | Nummer und Changelog **erledigt** (0.16.0); Bild folgt in AR-05 |

## 4. Grenzen der benutzten Prüfmittel

- **Der Bilderlauf** zeichnet das gerechnete Bild unter Robolectric (`sdk=34`),
  nicht das gelaufene. „78 von 78 gleich" heißt: AGP 9 und Kotlin 2.4 haben
  an dem, was Compose rechnet, nichts verschoben — nicht, dass die App auf
  einem Gerät gleich aussieht.
- **Die Lint-Zählung** zählt, was Lint meldet. Lint meldet eine neuere Fassung
  nicht bei jedem Baustein (Robolectric 4.17 fehlt, F-AR-10).
- **Der APK-Vergleich** sagt, welche Einträge sich geändert haben, nicht, ob
  der geänderte DEX-Code sich gleich verhält. Das sagen die Prüffälle.
