# NAdoku für Android — Handy-App und Wear-OS-Uhr

Dieses Verzeichnis ist der **zweite Client** des JSON-Vertrags. Die
Garmin-Uhr (`watch/`) war der erste; hier entsteht das Gegenstück für
Menschen ohne Garmin: Das **Handy** zeichnet die GPS-Spur über den ganzen
Dienst auf und sendet sie, die **Wear-OS-Uhr** ist Fernbedienung für die
Phasenknöpfe.

Grundlage ist `docs/konzepte/Konzept-S4-Handy-Uhr-Client.md`; der Vertrag, gegen den
gebaut wird, steht in `docs/JSON-Vertrag.md` und ist die führende Quelle.

> **Stand: Arbeitspaket C2 — die Blöcke B und C sind damit fertig.** Das
> Handy koppelt sich, zeichnet auf, dokumentiert Phasen und Einsätze und
> sendet alles nach dem JSON-Vertrag. Die Uhr hat ihr Bedienbild und den
> Nachrichtenweg dorthin: Sie puffert jedes Ereignis, bis das Handy quittiert,
> und liefert wortgleich nach. Was fehlt, ist der Browserteil (Block A) und
> die Übergabe (Block D).

---

## 1. Was hier liegt

```
android/
├── LIESMICH.md              diese Datei
├── version.properties       DIE Versionsnummer, für beide Module
├── settings.gradle.kts      die zwei Module und die Bezugsquellen
├── build.gradle.kts         liest die Version, rechnet den Versionscode
├── gradle/libs.versions.toml   alle Fremdbestandteile, eine Nummer je Stück
├── gemeinsam/               Quelltext und Ressourcen, die BEIDE Module
│   ├── quelle/              tragen — kein eigenes Modul, siehe 3.
│   └── res/
├── handy/                   die Android-App (Block B)
├── uhr/                     die Wear-OS-App (Block C)
└── werkzeuge/               Prüf- und Ableitungsskripte
```

Ein Push auf `main` mit Änderungen **unter `android/`** löst **kein Deploy**
aus — die GitHub-Action lädt nur `server/` hoch (CLAUDE.md 3). Das ist der
Grund, warum die Blöcke B und C parallel zu S2/S3 laufen können.

## 2. Bauen

### Voraussetzungen

| | Stand | Woher |
|---|---|---|
| JDK | 21 (im Container), mindestens 17 | `JAVA_HOME` |
| Android SDK | Plattform 36, Build-Tools 36.0.0 | `ANDROID_HOME` |
| Gradle | 8.14.3 | der Wrapper holt sie selbst |

```bash
export JAVA_HOME=/usr/lib/jvm/java-21-openjdk-amd64
export ANDROID_HOME=/opt/android-sdk
cd android
./gradlew build          # übersetzt, prüft, baut beide APK
./gradlew test           # nur die Prüffälle
./gradlew lint           # nur Lint (Textbericht unter <modul>/build/reports/)
```

### Der Server-Rundlauf

Ein Teil der Prüffälle spricht mit einer **echten Installation** statt mit
einem nachgestellten Server. Sie laufen nur, wenn man es sagt — ohne
Installation überspringen sie sich, statt fehlzuschlagen:

```bash
sh tools/referenzdatensatz/einspielen/lokal_starten.sh
cd android
./gradlew :handy:testDebugUnitTest --rerun-tasks \
          -Pnadoku.rundlauf=http://127.0.0.1:8080/
```

**Vorher ist eine Kopplung anzulegen, und dafür gibt es seit S5 zwei
Wege.** Welcher gilt, hängt nicht von der App ab, sondern vom Stand des
Servers, gegen den sie läuft — und die drei Rundlaufklassen der App stehen
heute noch auf dem alten. Deshalb beides, in dieser Reihenfolge.

#### Heute: die drei Rundlaufklassen brauchen einen Server vom Stand vor S5

`KopplungRundlaufTest`, `SendeRundlaufTest` und `MissionRundlaufTest`
koppeln über `Kopplungsdienst.koppeln(basis, code, geraet)` — die App
**sendet** also einen Code, den vorher jemand im Web erzeugt hat. Seit
Web 13.0.0 gibt es diesen Weg nicht mehr (S5, R49): Das Gerät **zeigt**
den Code, ein Mensch tippt ihn im Browser ein, und das Gerät bestätigt das
Konto. `pair.php` antwortet einem Rumpf ohne `aktion` mit
`400 {"error":"aktion","meldung":"Uhr-App aktualisieren"}`.

**Woran ein falscher Serverstand zu erkennen ist:** Der Gutfall
`kopplungGegenPairPhp` meldet `Abgewiesen(CODE_UNBRAUCHBAR)` statt
`Gekoppelt` — die Fehlerabbildung in `Kopplungsdienst` macht aus jedem 400
einen unbrauchbaren Code. Es scheitert also sichtbar, sagt aber nicht, was
los ist.

**„Stand vor S5" heißt: Quelltext *und* Datenbank.** Die Migration
`2026_09_03_kopplungssitzungen` legt `pair_sessions` an und lässt
`pair_codes` fallen. Ein `pair.php` von vorher greift dann auf eine
Tabelle zu, die es nicht mehr gibt, und endet im Serverfehler;
`/tmp/php-server.log` nennt `pair_codes` beim Namen. Der Rundlauf braucht
deshalb eine eigene Installation:

```bash
# Den letzten Stand vor Web 13.0.0 finden und daneben auschecken:
git log --oneline -- server/version.php | head     # „web v13.0.0 …" suchen
git worktree add /tmp/vor-s5 <der Commit DAVOR>

# Server aus diesem Stand fahren; die Datenbank frisch einrichten
# (install.php im Browser) und update.php NICHT laufen lassen:
WURZEL=/tmp/vor-s5/server sh tools/referenzdatensatz/einspielen/lokal_starten.sh

# Kopplungscodes als Vorbedingung anlegen (sie sind einmal einlösbar):
mariadb -e "DELETE FROM pair_codes; DELETE FROM devices;
  INSERT INTO pair_codes (user_id, code) VALUES
   (1,'AB3K7Q'),(1,'CD4M8R'),(1,'EF5N9S'),(1,'GH6P2T'),
   (1,'LA2B3C'),(1,'LD4E5F'),(1,'LG6H7J'),(1,'LK8L9M'),
   (1,'LN2P3Q'),(1,'LR4S5T'),(1,'LU6V7W'),(1,'LX8Y9Z'),
   (1,'RA2B3C'),(1,'RD4E5F'),(1,'RG6H7J'),(1,'MA2B3C'),(1,'MD4E5F'),
   (1,'UA2B3C');" nadoku
```

**Bis wann das so bleibt:** bis der S4-Rest das Kopplungsmodul der
Handy-App auf den neuen Weg umbaut (Konzept S5, Abschnitt 7 beschreibt
ihn). Danach koppeln die drei Klassen wie im nächsten Abschnitt, laufen
wieder gegen den aktuellen Stand — und dieser Abschnitt hier fällt weg.

#### So bekommt ein Prüfling heute eine Kopplung

Die Codes stehen in keiner Tabelle mehr, in die man sie legen könnte: Sie
entstehen bei `pair.php` selbst, zusammen mit den Zugangsdaten. Ein
Prüfling holt sich seine Kopplung deshalb genauso, wie ein Gerät es tut —
drei Schritte, von denen der mittlere im Betrieb ein Mensch im Browser
ist:

```bash
# 1. Sitzung holen. Das tut sonst die App, wenn jemand „Kopplung starten"
#    drückt: OHNE Kopfzeilen, mit der Handy-Form des Blocks `geraet`.
antwort=$(curl -s --noproxy '*' -X POST http://127.0.0.1:8080/pair.php \
  -H 'Content-Type: application/json' \
  -d '{"aktion":"start","geraet":{"art":"handy","teil":null,
        "hersteller":"samsung","modell":"SM-S921B","br":1080,"ho":2340,
        "touch":true,"fw":"16","sdk":36,"app":"0.7.7"}}')
#  -> {"code":"TKH9XS","device_id":"dev-…","api_key":"…","frist_s":600}
code=$(printf '%s' "$antwort" | jq -r .code)
dev=$( printf '%s' "$antwort" | jq -r .device_id)
key=$( printf '%s' "$antwort" | jq -r .api_key)

# 2. Den Code einem Konto zuordnen. Das tut sonst ein Mensch im Browser
#    unter Einstellungen → Geräte („Code vom Gerät" → Weiter → Mit meinem
#    Konto verbinden). Konto 1 ist das, welches die lokale Installation
#    beim Einrichten anlegt.
mariadb -e "UPDATE pair_sessions SET user_id = 1 WHERE code = '$code';" nadoku

# 3. Ja sagen. Das tut sonst die Person am Gerät, nachdem es ihr die
#    maskierte Adresse des Kontos gezeigt hat. MIT Kopfzeilen.
curl -s --noproxy '*' -X POST http://127.0.0.1:8080/pair.php \
  -H 'Content-Type: application/json' \
  -H "X-Device-Id: $dev" -H "X-Api-Key: $key" \
  -d '{"aktion":"bestaetigen","antwort":"ja"}'
#  -> {"ok":true}. Ab jetzt steht die Zeile in `devices`, und $dev/$key
#     sind gültige Zugangsdaten für ingest.php.
```

`--noproxy '*'` steht dort, weil im Container ein Proxy gesetzt ist und
`127.0.0.1` nicht durch ihn gehört; `jq` liest nur die Antwort und ist
keine Voraussetzung des Wegs.

Drei Dinge, damit der Weg nicht an einer Kleinigkeit scheitert:

- **Zehn Minuten für alles** (`frist_s`), gerechnet ab Schritt 1. Die
  Zuordnung im Web verlängert nichts. Wer dazwischen etwas anderes tut,
  fängt von vorn an; Schritt 3 antwortet dann `410 abgelaufen`.
- **Schritt 1 zählt, auch wenn nichts daraus wird.** Der Topf `pair_start`
  lässt zwanzig `start`-Aufrufe je zehn Minuten und Adresse zu (E-S5-33).
  Wer eine Schleife darüber schreibt, sperrt sich selbst für zehn Minuten
  aus (`429 zu_viele_versuche`) — und nimmt dabei dem Bilderlauf und der
  Kopplungsprobe ihre Aufrufe weg, die aus demselben Topf kommen.
- **Der saubere Rückweg** ist `{"aktion":"bestaetigen","antwort":"nein"}`
  mit denselben Kopfzeilen: Er löscht die Sitzung. Für eine bereits
  angelegte Gerätezeile ist es `{"aktion":"trennen"}` — dasselbe, was die
  Fälle in ihrem `@After` tun.

Wer den ganzen Endpunkt prüfen will statt nur eine Kopplung zu bekommen,
nimmt `php tools/kopplungsprobe/probe.php`; sie fährt alle vier Anliegen,
Fristen, Fehlerzweige und Bremsen über echtes HTTP durch.

**Warum Klartext-HTTP und nicht HTTPS:** *(Zeile 83–87 unverändert)*

**Warum die Kopplung als Vorbedingung von Hand entsteht:** Geprüft wird
der Weg *App → `pair.php` → `devices`-Zeile*. Wie ein Code auf das Gerät
kommt und wie er von dort in ein Konto gelangt, ist Sache des Geräts und
der Weboberfläche — es ist die Vorbedingung dieser Fälle, nicht ihr
Prüfling.

**Seit Web 12.9.0 trägt diese `devices`-Zeile drei Spalten mehr** —
*(Zeile 93–100 unverändert)*

**Die Fassung der Apps steigt dafür nicht.** Weder S6 noch S5 ändern eine
Zeile Quelltext unter `android/`; S5 schreibt nur diese Anleitung fort.
Die drei Zählungen (Web, Uhr, Android) bleiben getrennt.

Die Fälle **räumen hinter sich auf**: *(Zeile 105–108 unverändert)*

### Was der Baulauf heute meldet

Stand C2 (Android 0.7.0), `./gradlew build` im Container:

| | `handy` | `uhr` |
|---|---|---|
| Lint-Fehler | **0** | **0** |
| Lint-Warnungen | **14** | **0** |
| Prüffälle | **167**, davon 12 übersprungen | **53**, davon 0 übersprungen |
| APK (unsigniert, Release) | **9 598 911 B** | **19 491 794 B** |

Zusammen **220 Prüffälle**. Dass die Uhr-APK doppelt so groß ist wie die
Handy-APK, ist kein Fehler: Compose für Wear OS bringt seine eigene
Bausteinsammlung mit, und beide Module übersetzen `gemeinsam/` mit. Der Fund
**B-S4-03** im Konzept hält fest, dass das für eine Uhr viel ist und worauf
beim Gerätetest zu achten ist; mit C2 sind rund 1,4 MB Data Layer
dazugekommen (Handy: 0,5 MB).

Alle 14 Warnungen sind derselbe Befund („A newer version of … is available")
auf `gradle/libs.versions.toml` — und sie hängen **alle** an einer einzigen
Entscheidung: AGP 9 und Kotlin 2.4 (Abschnitt 4). Was ohne diesen Schritt
hochzuziehen war, ist mit 0.7.5 hochgezogen: `wear-input` 1.2.0,
`play-services-wearable` 20.0.1, `zxing` 3.5.4, `test.ext:junit` 1.3.0 —
**18 → 14 Warnungen**, Baulauf grün.

**Ein Kopplungshinweis zur Uhr-App:** `androidx.wear.compose` 1.6.2 und die
Compose-BOM 2026.08.00 verlangen einen neueren Compose-Compiler, der an
Kotlin hängt; Kotlin 2.4 wiederum verlangt AGP 9. Die vier AndroidX-Bausteine
(`core-ktx`, `lifecycle`, `activity-compose`, `camera`) ziehen dieselbe Kette.
Es sind also nicht 14 Entscheidungen, sondern **eine**.

Bis 0.7.3 war eine 19. dabei: `BatteryLife`. Sie ist mit **E-S4-52**
verschwunden — die App fordert die Akku-Freistellung nicht mehr über den
gezielten Dialog an, sondern führt zur allgemeinen Liste. Ein Bedienschritt
mehr, dafür verträgt sie sich mit der Inhaltsrichtlinie des Play Store.

Keine der Warnungen wird stummgeschaltet: Eine unterdrückte Warnung ist eine
Warnung weniger, die später auffällt.

Die **11 übersprungenen** Fälle sind der Server-Rundlauf; mit laufender
Installation sind es 167 von 167 (siehe unten). Die 53 Fälle der Uhr laufen
immer — sie brauchen weder Server noch Gerät, weil geprüft wird, was die
Bedienung *entscheidet* und was der Funk *zusichert*, nicht was die Uhr
*zeichnet* (E-S4-40).

### 2.2 Bilder der Oberfläche, ohne Emulator und ohne Gerät

`UhrBildTest` (Modul `uhr`) baut die Ansicht in einer Robolectric-Activity
auf, misst und zeichnet sie selbst auf eine Bitmap und legt PNG unter
`uhr/build/bilder/` ab:

```bash
./gradlew :uhr:testDebugUnitTest --tests '*UhrBildTest*'
```

**Null neue Abhängigkeiten** (E-S4-49): `ComposeView` steckt in
`androidx.compose.ui`, das die App ohnehin einbindet. Der naheliegende Weg
über `captureToImage()` aus `compose-ui-test` funktioniert unter Robolectric
**nicht** — er wartet in einer `Thread.sleep`-Schleife auf genau den Faden,
der zeichnen müsste.

Der Fall **misst mit**, statt nur zu malen — zwei Zusicherungen:

| Gemessen | Zusage | gefunden |
|---|---|---|
| Höhe der Knopffläche | 48 dp (E-S4-41) | B-S4-08: 35,5 dp auf 192 dp |
| Knopffläche außerhalb des einbeschriebenen Kreises | 0 % | B-S4-08b: 13,55 % Gesamtinhalt, alles im Knopf |

Die runde Maske wird dabei **gerechnet, nicht gemalt**: Der Direktweg zeichnet
ein Quadrat, aber wo das Glas liegt, ist bekannt. Was außerhalb Farbe trägt,
sieht auf der Uhr niemand.

> **Am 02.09.2026 auf dem Emulator gegengeprüft** — die Rechnung stimmt.
> Der Abzug der Startseite auf echter runder Maske (`wear30`, Wear OS 3,
> 384 × 384, `hw.lcd.circular = true`), gegen den Abzug von 0.7.0 gehalten:
>
> | | Knopfhöhe | Luft zum Glasrand |
> |---|---|---|
> | 0.7.0 vorher | **35,5 dp** | **0,4 dp** — der Knopf klebt am Rand |
> | 0.7.5 nachher | **48,0 dp** | **14,7 dp** |
>
> Dazu bestätigt: „Handy nicht erreichbar" statt des früheren, falschen
> „Handy verbunden" (B-S4-09).
>
> **Eine Falle dabei, die man kennen muss:** Auf einem Emulator-Abzug lässt
> sich „Anteil außerhalb des Kreises" **nicht** messen — der Emulator hat
> bereits beschnitten, die Pixel außerhalb sind gar nicht da, und die Zahl
> ist immer 0 %. Der erste Messversuch lieferte deshalb für beide Fassungen
> 0,00 % und hätte den Fehler von 0.7.0 als behoben ausgewiesen. Messbar ist
> auf dem Abzug nur, ob der Knopf den Glasrand **berührt** — dafür der größte
> Abstand seiner Pixel zur Mitte gegen den Radius. Und die orange Bildmarke
> muss vorher weg: über zusammenhängende Flächen, die größte ist der Knopf.

Ein Mockup mit Vorher/Nachher-Bildern liegt unter `mockups/`.

**Was diese Bilder nicht zeigen:** Robolectrics Schriften statt der von Wear OS, keine
Hardwarebeschleunigung, und **einen** Android-Stand (sdk=34) statt der Spanne
30 bis 36. Wo das runde Glas zur Frage steht, hilft nur der Emulator.

### Das SDK ist im Container nicht vorinstalliert

Es wird einmalig geholt; danach steht es:

```bash
mkdir -p /opt/android-sdk/cmdline-tools
curl -L -o /tmp/cmdline-tools.zip \
  https://dl.google.com/android/repository/commandlinetools-linux-11076708_latest.zip
unzip -q /tmp/cmdline-tools.zip -d /opt/android-sdk/cmdline-tools
mv /opt/android-sdk/cmdline-tools/cmdline-tools /opt/android-sdk/cmdline-tools/latest
yes | /opt/android-sdk/cmdline-tools/latest/bin/sdkmanager --licenses
/opt/android-sdk/cmdline-tools/latest/bin/sdkmanager \
  "platform-tools" "platforms;android-36" "build-tools;36.0.0"
```

Rund 460 MB. Die Zuarbeitenliste des Konzepts (Abschnitt 9) nennt die
Kommandozeilenwerkzeuge als „im Container"; sie waren es nicht — der Schritt
oben ist der Nachtrag dazu.

### Netzfreigaben

Der Baulauf spricht mit fünf Bezugsquellen; alle fünf standen in der
Zuarbeitenliste und antworten:

`dl.google.com` · `maven.google.com` · `repo1.maven.org` ·
`plugins.gradle.org` · `services.gradle.org`

**Eine sechste kommt hinzu, und sie stand nicht auf der Liste:** Der
Gradle-Wrapper lädt seine Verteilung von `services.gradle.org`, und diese
Adresse leitet auf **`github.com`** weiter (Freigabe
`gradle/gradle-distributions`). Ohne die Weiterleitung gibt es keinen
Wrapper-Lauf. Eine siebte, `downloads.gradle.org`, trägt nur die
Prüfsummen-Datei und ist gesperrt — siehe 2.1.

### 2.1 Warum keine Prüfsumme der Gradle-Verteilung eingetragen ist

`gradle-wrapper.properties` führt üblicherweise ein
`distributionSha256Sum`. Hier steht keines, und das ist eine bewusste Lücke
mit Begründung:

Die offizielle Prüfsumme liegt unter
`services.gradle.org/distributions/gradle-8.14.3-bin.zip.sha256`. Diese
Adresse leitet auf `downloads.gradle.org` weiter, und die ist im Container
**gesperrt** (403 der Egress-Regel). Die Prüfsumme aus der Datei zu rechnen,
die man gerade geladen hat, ist keine Prüfung, sondern eine Tautologie — sie
bestätigte jede Datei, auch eine falsche.

Stattdessen wurde **gegenseitig belegt**: Die vom Wrapper geladene
Verteilung und das im Container vorinstallierte Gradle 8.14.3 stammen aus
zwei verschiedenen Quellen. Ein Vergleich ihrer Programmbibliotheken ergab
**188 von 188 JAR bitgleich, 0 Abweichungen**. Zwei unabhängige Wege zur
selben Datei sind der Beleg, den die Prüfsummen-Datei geliefert hätte.

Wer an einem Arbeitsplatz **ohne** diese Sperre sitzt, trägt die Prüfsumme
von <https://gradle.org/release-checksums/> nach — sie gehört dorthin.

Die Wrapper-JAR selbst liegt im Repositorium (das ist bei Gradle so
vorgesehen). Ihre Prüfsumme, für den Fall, dass jemand sie nachrechnen will:

```
SHA-256  7d3a4ac4de1c32b59bc6a4eb8ecb8e612ccd0cf1ae1e99f66902da64df296172
         gradle/wrapper/gradle-wrapper.jar   (aus Gradle 8.14.3)
```

## 3. Warum es zwei Module gibt und trotzdem gemeinsamen Quelltext

`E-S4-02` legt **zwei** Module fest: `handy/` und `uhr/`. Beide brauchen
dieselben Dinge — die Farb-Token, die beiden Bildmarken, die Phasenliste,
später das Nachrichtenformat des Data Layer.

Der übliche Gradle-Weg dafür wäre ein drittes Modul. Das wäre aber eine
Entscheidung, die das Konzept nicht getroffen hat. Stattdessen wird
`gemeinsam/` in **beide** Module **eingebunden**:

```kotlin
sourceSets["main"].java.srcDir("../gemeinsam/quelle")
sourceSets["main"].res.srcDir("../gemeinsam/res")
```

Der gemeinsame Teil wird damit zweimal übersetzt. Das kostet Bauzeit und
sonst nichts — und es spart eine Modulgrenze mit eigenem Manifest, eigener
Versionierung und eigener Lint-Auswertung für rund tausend Zeilen.

**Beide Module tragen dieselbe Anwendungs-ID** (`org.genem.nadoku`) und
denselben Namensraum. Das ist keine Bequemlichkeit, sondern Bedingung: Der
Wear Data Layer stellt Nachrichten nur zwischen Apps **gleichen Pakets und
gleicher Signatur** zu (E-S4-01). Wären sie verschieden, käme keine einzige
Nachricht von der Uhr an — und zwar ohne Fehlermeldung.

## 4. Werkzeugstände und warum sie so gewählt sind

| | gewählt | warum |
|---|---|---|
| `compileSdk` / `targetSdk` | **36** (Android 16) | siehe unten |
| `minSdk` Handy | **26** (Android 8.0) | E-S4-03, mit F-S4-A am 31.08.2026 bestätigt |
| `minSdk` Uhr | **30** (Wear OS 3) | Galaxy Watch4 aufwärts; ältere Tizen-Modelle führen gar keine Android-Apps aus |
| Sprachstand | **17** | E-S4-02 |
| AGP | **8.13.2** | siehe unten |
| Kotlin | **2.1.21** | Compose-Compiler ist seit 2.0 Teil von Kotlin |

**Warum `targetSdk` 36 und nicht 37.** Das SDK-Verzeichnis führt inzwischen
`android-37.0`, `-37.1` und `-37.2` — API 37 gibt es nur mit
**Nebenversionen**, eine schlichte `platforms;android-37` existiert nicht.
Diese Schreibweise (`compileSdk` + `compileSdkMinor`) beherrscht erst AGP 9.
API **36** ist die letzte Stufe mit ganzer Nummer, und sie erfüllt genau
das, wofür E-S4-03 „`targetSdk` aktuell" verlangt: Ab API 34 gelten die
strengen Regeln für Vordergrunddienste, und die App deklariert
`FOREGROUND_SERVICE_LOCATION` (ab B3).

**Warum AGP 8.13.2 und nicht 9.x.** AGP 9 ist ein Umbau der Bau-Sprache
(eingebautes Kotlin-Plugin, verschobene DSL). Ihn blind einzuführen, um eine
API-Stufe zu gewinnen, die die App nicht braucht, wäre der teure Weg zum
kleinen Gewinn. 8.13.2 ist die letzte Fassung der 8er-Reihe. Der Wechsel auf
AGP 9 samt API 37 ist eine eigene, absichtliche Runde — nicht ein Nebeneffekt
von B1.

**JDK 21 baut, JDK 17 ist der Sprachstand.** Das ist kein Widerspruch:
`sourceCompatibility`/`jvmTarget` legen fest, welchen Bytecode die App
enthält; womit übersetzt wird, ist davon unabhängig. Im Container liegt JDK
21, E-S4-02 nennt 17 — beides gilt gleichzeitig.

## 5. Der Signaturschlüssel

Er liegt **nicht** im Repositorium (E-S4-16, `.gitignore`). Der Bauweg findet
ihn über eine Datei `signatur.properties` neben dieser hier:

```properties
speicherDatei=nadoku-auslieferung.jks
speicherPasswort=…
schluesselName=nadoku
schluesselPasswort=…
```

Fehlt sie, entsteht ein **unsigniertes** Release — genau so läuft es im
Container und später im CI-Prüftor (E-R45-9: signiert wird außerhalb der CI,
weil der Schlüssel dort nichts verloren hat).

**Jede spätere Fassung muss mit demselben Schlüssel signiert sein.** Android
erkennt eine App an Paketname **und** Signatur; ein Wechsel bedeutet für
jedes Gerät Deinstallation samt Datenverlust. Deshalb wird der Schlüssel
einmal erzeugt und dem Auftraggeber zur Verwahrung übergeben.

## 6. Die Werkzeuge unter `werkzeuge/`

| Skript | Was es prüft | Sollstand |
|---|---|---|
| `farbabgleich.py` | App-Token gegen `:root` des Web | 0 Abweichungen, 0 eigene Farbwerte |
| `kontraste.py` | Kontrast jedes Farbpaars der App | 0 Paare unter dem Zielwert |
| `bildmarken.sh` | Bildmarken gegen ihre Vorlagen | 0 Abweichungen |
| `stroeme.py` | Soll-Zahlen der Ausdünnung, mit der Referenzregel aus `tools/referenzdatensatz/` nachgerechnet | 0 Abweichungen gegen die analytischen Werte |

Sie sind das Gegenstück zu `tools/vollstaendigkeit/` und
`tools/screenshots/kontrast.py` der Weboberfläche — und sie mussten eigene
sein, weil die App andere Paare zusammenstellt (weiße Schrift auf
vollflächig Rot, alles auf Asphaltgrund) und ihre Werte in einer
XML-Ressource führt statt in einem Stylesheet.

## 7. Was hier nicht geprüft werden kann

Das steht vorn und nicht in einer Fußnote (E-R45-7, E-R45-8):

- ~~**Kein Emulator.**~~ **Das stimmte nicht** und ist seit 0.7.2 berichtigt.
  Der Emulator läuft ohne KVM in reiner Software-Emulation; beide Module
  wurden darin gebootet, bedient und abgezogen. Bilder entstehen im
  Prüflauf ohne ihn (siehe 2.2). Was er kostet: Boot 197–345 s, Installation
  eines 26-MB-APK 207 s. `-no-window` ist **Pflicht** — die GUI-Binärdatei
  scheitert an fehlendem `libpulse.so.0`.

  *Zweiter Lauf am 02.09.2026 (0.7.6), Zahlen bei geteilten Kernen:* Boot
  **366 s** (Gradle lief parallel), Uhr-APK **165 s**, Handy-APK **466 s**.
  Und: `./gradlew connectedAndroidTest` **scheitert** auf diesem Emulator mit
  „Skipping device 'wear30(AVD)': Unknown API Level" — ddmlib gibt beim Lesen
  von `ro.build.version.sdk` nach 5 s auf, das Gerät braucht 4,3 s und liegt
  damit auf der Kippe. Der Weg, der trägt, geht an Gradle vorbei:

  ```bash
  adb install -r -t handy/build/outputs/apk/debug/handy-debug.apk
  adb install -r -t handy/build/outputs/apk/androidTest/debug/handy-debug-androidTest.apk
  adb shell am instrument -w -e class <Klasse> \
    org.genem.nadoku.pruef.test/androidx.test.runner.AndroidJUnitRunner
  ```
- **Kein echtes GPS**, kein Akkuverhalten (namentlich Samsungs „Apps im
  Tiefschlaf"), kein Mobilfunk-Upload, kein Bluetooth, kein Data Layer auf
  Hardware.
- **Der Wear Data Layer — die Grenze liegt woanders, als hier stand.**
  Der Satz lautete: „Er braucht zwei gekoppelte Geräte **und** die
  Play-Dienste; im Container gibt es beides nicht." **Beide Hälften sind
  widerlegt** (gemessen am 02.09.2026 auf dem `wear30`-Emulator):

  | behauptet | gemessen |
  |---|---|
  | keine Play-Dienste | `com.google.android.gms` **22.48.14** liegt im Abbild; `isGooglePlayServicesAvailable` meldet `SUCCESS` |
  | Wearable-API nicht erreichbar | `NodeClient.localNode` liefert einen **lokalen Knoten mit Kennung**; `connectedNodes` liefert **0** — ohne Telefon die richtige Antwort |
  | Empfangsdienst ungeprüft | `HandyHorcher` ist beim System registriert (`wear:`, `PREFIX /nadoku`) und löst für **alle drei** Pfade auf |

  Was tatsächlich fehlt, ist **nur die Telefonseite** — und sie ist in diesem
  Container nicht zu beschaffen. Der Knoten entsteht nicht durch die
  Play-Dienste, sondern durch die **Wear-OS-Companion-App auf dem Telefon**;
  ohne Knoten liefert `connectedNodes` eine leere Liste und `sendMessage`
  scheitert mit `TARGET_NODE_NOT_CONNECTED`, gleichgültig wie die
  Portweiterleitungen stehen. Drei Messungen schließen die Kette
  (02.09.2026, jede einzeln nachgefahren):

  | Weg | gemessen |
  |---|---|
  | Companion-APK liegt in einem Systemabbild | **0** von 16 APKs unter `/opt/android-sdk` tragen `wear`, `clockwork` oder `companion` im Namen |
  | Bezug über den Play Store | `android.clients.google.com` antwortet **403**, `x-deny-reason: host_not_allowed` (ebenso `play.google.com`); `dl.google.com` dagegen **302** — die Sperre ist gezielt nach Hostname, nicht „kein Netz" |
  | Googles eigener Notausgang an der Companion vorbei | `cmd package query-receivers -a com.google.android.gms.wearable.EMULATOR` → **`No receivers found`** |

  Ein Seitenladen aus einer APK-Sammelseite scheidet ohnehin aus
  (CLAUDE.md 4) — und `www.apkmirror.com` antwortet hier ebenfalls mit 403.
  **Damit ist das kein „noch nicht", sondern ein Nein**, und wer es doch
  versucht, zahlt eine Viertelstunde Bootzeit für eine Sperre, die ein
  `curl` in zwölf Sekunden zeigt.

  **Zwei Begründungen, die NICHT tragen** und deshalb hier nicht stehen: Es
  liegt *nicht* an der fehlenden Hardwarebeschleunigung — zwei Emulatoren sind
  mindestens einmal gleichzeitig gebootet worden. Und es liegt *nicht* daran,
  dass die Uhr kein Netz hätte — das ist widersprüchlich gemessen und
  ungeklärt. Wer sich auf eine der beiden beruft, ist widerlegt, sobald es
  jemand ausprobiert.

  Zur Vorsicht gegen eine naheliegende Verwechslung: `pm path
  com.google.android.wearable.app` *antwortet* auf der Uhr — aber mit
  `/system/priv-app/ClockworkWcs/ClockworkWcs.apk`, also der **Uhrseite** von
  WCS unter demselben Paketnamen. Das ist nicht die Telefon-App.

  Ungeprüft bleibt damit **die Zustellung selbst**: `WearNachrichtenweg`
  (rund fünfzig Zeilen, keine Entscheidung) und was die beiden
  `WearableListenerService` *mit* einer echten Nachricht tun. **Geprüft ist
  alles darüber** — Puffer, Nummernvergabe, Quittung, Nachlieferung,
  Doppelzustellung, die Buchführung am Handy: gegen eine Transport-Attrappe,
  in 26 Fällen. Genau dafür ist die Naht dort, wo sie ist.

  *Prüffall dazu:* `DataLayerErreichbarTest` (instrumentiert). Er schreibt die
  Grenze fest: Fällt er durch, liegt es an den Play-Diensten; läuft er durch
  und die Zustellung klemmt, liegt es an der Kopplung.
- **Die Uhr-App ist blind gebaut.** Rundung, Schriftgrößen, Berührziele,
  Haltedauer und Sperrfrist sind gewählt und am Gerät nachzumessen; sie
  gehören danach in den Wear-Teil von `docs/Geraete-Eingabe.md`. Geprüft ist
  seit C1 die Schicht darunter: `Uhrbedienung` ist eine reine
  Zustandsmaschine ohne Compose-Bezug, und ihre 21 Fälle schreiben das
  Bedienbild fest, bevor es jemand gesehen hat. Ungeprüft bleibt alles
  Sichtbare — und die freie Taste: `WearableButtons` meldet im Container
  keine, der Weg über `onKeyDown` ist ungeprüft.
- ~~**Der `AndroidKeyStore`.**~~ **Seit 0.7.6 geprüft** — auf dem Emulator,
  nicht mehr nur zugesagt. Robolectric bringt ihn nach wie vor nicht mit
  (`KeyStoreException: AndroidKeyStore not found`); der instrumentierte
  `GeraetTresorTest` läuft dafür auf einem echten Android-System und belegt in
  **sechs Fällen** (13,7 s): Der Schlüssel entsteht unter seinem Namen im
  Keystore, `getEncoded()` ist **null** (also nicht exportierbar), der
  Rundlauf trägt, der Schlüssel überlebt einen neuen Griff, jeder
  Schreibvorgang bekommt einen frischen Zufallswert, und ein verfälschtes
  Paket wird abgelehnt.

  **Was auch das nicht belegt:** Ein Emulator hat keinen
  Hardware-Sicherheitsanker. `getEncoded() == null` gilt, aber „auch mit
  Root-Rechten nicht auslesbar" hängt am Trusted Execution Environment eines
  echten Geräts. Das bleibt auf der Prüfliste des Gerätetests.
- **Die Kamera.** `QrKamera` ist eine Hülle um CameraX. Die frühere
  Begründung — „ohne Emulator gibt es keine" — ist **überholt**: Den Emulator
  gibt es, und sein AVD führt `hw.camera.back = emulated`. Ungeprüft ist die
  Hülle trotzdem, nur aus einem anderen Grund: Die emulierte Kamera zeigt eine
  Kunstszene, und ob ein QR-Code darin erkennbar bei ZXing ankommt, ist eine
  eigene Frage. Geprüft ist, was dahinterliegt: die Erkennung (`QrLeser`) und
  die Auswertung des Inhalts (`QrInhalt`).
- **Der Vordergrunddienst.** `AufzeichnungsDienst` bekommt im Container weder
  GPS noch einen `LocationManager`. Geprüft ist die Logik dahinter —
  `Ausduenner` gegen fünf synthetische Ströme, `Dienstklammer` gegen echtes
  SQLite, samt Wiederaufnahme nach Absturz und Neustart. Ob der Dienst zwölf
  Stunden durchhält, sagt nur der S24-Dienst.
- **Die Akku-Freistellung.** Ob sie hält, zeigt allein das Gerät; Samsungs
  „Apps im Tiefschlaf" ist ein zweiter, herstellereigener Schalter, den keine
  App erreicht.

Geprüft wird deshalb: der Baulauf, Lint, Prüffälle auf der JVM (teils mit
Robolectric) und — ab B4 — der Rundlauf gegen `ingest.php` in der lokalen
Container-Installation.
