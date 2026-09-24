# Prüfdokument AR — Android-Runde

*Gehört zu `Konzept-AR-Android-Runde.md`. Beantwortet die Frage „was muss
**ich** noch tun?"; das Protokoll im Konzept beantwortet „ist es belegt?".
Stand: **24.09.2026, nach AR-02.** Wird je Paket fortgeschrieben.*

## 1. Was nicht geprüft werden konnte — und warum

| Was | Warum nicht | Wo es stattdessen hingehört |
|---|---|---|
| **Der Emulator zu AR-02** | Absichtlich ausgelassen (E-AR-11): Er läuft einmal, in AR-05, am auszuliefernden Stand. AR-02 ist ein Zwischenstand, der nie einzeln ausgeliefert wird | Stattdessen belegt: 78 von 78 Bildern des Bilderlaufs byteweise gleich, Manifest und Ressourcen beider APKs byteweise gleich (2.) |
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

## 3. Prüfliste — was die Betreiberin noch tun kann

| Nr. | Was | Bedienweg | Erwartet | Scheitern erkennbar an | Stand |
|---|---|---|---|---|---|
| P-AR-01 | Der Bau läuft auf einer Maschine ohne Spiegel | `cd android && ./gradlew build` auf einem Rechner mit JDK 21 und Android-SDK | `BUILD SUCCESSFUL`, 0 Lint-Fehler, Prüffallzahlen wie in 2. | Auflösefehler (`Could not resolve`) oder andere Prüffallzahlen | offen |
| P-AR-02 | Die Release-Prüffälle laufen weiter | im Baulog nach `:handy:testReleaseUnitTest` suchen | Aufgabe läuft, 264 Fälle | Aufgabe fehlt im Log | **belegt** (AR-02, 2.) |
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
