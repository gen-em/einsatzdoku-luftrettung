# NAdoku für Android — Handy-App und Wear-OS-Uhr

Dieses Verzeichnis ist der **zweite Client** des JSON-Vertrags. Die
Garmin-Uhr (`watch/`) war der erste; hier entsteht das Gegenstück für
Menschen ohne Garmin: Das **Handy** zeichnet die GPS-Spur über den ganzen
Dienst auf und sendet sie, die **Wear-OS-Uhr** ist Fernbedienung für die
Phasenknöpfe.

Grundlage ist `docs/Konzept-S4-Handy-Uhr-Client.md`; der Vertrag, gegen den
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
# Kopplungscodes als Vorbedingung anlegen (sie sind einmal einlösbar):
mariadb -e "DELETE FROM pair_codes; DELETE FROM devices;
  INSERT INTO pair_codes (user_id, code) VALUES
   (1,'AB3K7Q'),(1,'CD4M8R'),(1,'EF5N9S'),(1,'GH6P2T'),
   (1,'LA2B3C'),(1,'LD4E5F'),(1,'LG6H7J'),(1,'LK8L9M'),
   (1,'LN2P3Q'),(1,'LR4S5T'),(1,'LU6V7W'),(1,'LX8Y9Z'),
   (1,'RA2B3C'),(1,'RD4E5F'),(1,'RG6H7J'),(1,'MA2B3C'),(1,'MD4E5F'),
   (1,'UA2B3C');" nadoku

cd android
./gradlew :handy:testDebugUnitTest --rerun-tasks \
          -Pnadoku.rundlauf=http://127.0.0.1:8080/
```

**Warum Klartext-HTTP und nicht HTTPS:** Die lokale Installation trägt ein
selbstsigniertes Zertifikat. Es in den Vertrauensspeicher des Prüflaufs zu
legen hieße, dem Prüfstand etwas beizubringen, was die App nie tun darf. Dass
die App **nur** HTTPS spricht (E-S4-14), ist an der Stelle belegt, an der diese
Regel wohnt — `ServeradresseTest`.

**Warum die Codes von Hand in die Tabelle kommen:** Geprüft wird der Weg
*App → `pair.php` → `devices`-Zeile*. Wie ein Kopplungscode im Browser
entsteht, ist nicht Gegenstand dieses Falls, sondern seine Vorbedingung.

Die Fälle **räumen hinter sich auf**: Was sie koppeln, trennen sie wieder.
Das ist Voraussetzung und nicht Ordnungsliebe — `MAX_GERAETE` ist 5, und JUnit
sichert keine Reihenfolge zu. Beim ersten Lauf füllte der Grenzfall das Konto,
und alles danach scheiterte an `device_limit`.

Die APK liegen danach unter
`handy/build/outputs/apk/release/handy-release-unsigned.apk` und
`uhr/build/outputs/apk/release/uhr-release-unsigned.apk`.

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
- **Kein echtes GPS**, kein Akkuverhalten (namentlich Samsungs „Apps im
  Tiefschlaf"), kein Mobilfunk-Upload, kein Bluetooth, kein Data Layer auf
  Hardware.
- **Der Wear Data Layer.** Er braucht zwei gekoppelte Geräte und die
  Play-Dienste; im Container gibt es beides nicht. Ungeprüft sind damit genau
  drei Klassen: `WearNachrichtenweg` (rund fünfzig Zeilen, keine Entscheidung)
  und die beiden `WearableListenerService`. **Geprüft ist alles darüber** —
  Puffer, Nummernvergabe, Quittung, Nachlieferung, Doppelzustellung, die
  Buchführung am Handy: gegen eine Transport-Attrappe, in 26 Fällen. Genau
  dafür ist die Naht dort, wo sie ist.
- **Die Uhr-App ist blind gebaut.** Rundung, Schriftgrößen, Berührziele,
  Haltedauer und Sperrfrist sind gewählt und am Gerät nachzumessen; sie
  gehören danach in den Wear-Teil von `docs/Geraete-Eingabe.md`. Geprüft ist
  seit C1 die Schicht darunter: `Uhrbedienung` ist eine reine
  Zustandsmaschine ohne Compose-Bezug, und ihre 21 Fälle schreiben das
  Bedienbild fest, bevor es jemand gesehen hat. Ungeprüft bleibt alles
  Sichtbare — und die freie Taste: `WearableButtons` meldet im Container
  keine, der Weg über `onKeyDown` ist ungeprüft.
- **Der `AndroidKeyStore`.** Robolectric bringt ihn nicht mit
  (`KeyStoreException: AndroidKeyStore not found`). Geprüft ist deshalb der
  ganze Umschlag — AES-256-GCM, frischer Zufallswert je Schreibvorgang, der
  Rundlauf, die Abwesenheit von Klartext im Speicherabbild. **Ungeprüft ist
  genau eine überschriebene Methode:** dass der Schlüssel im Keystore entsteht
  und ihn nicht verlässt (`KeystoreTresorschluessel.schluessel()`). Das gehört
  auf die Prüfliste des Gerätetests.
- **Die Kamera.** `QrKamera` ist eine Hülle um CameraX; ohne Emulator gibt es
  keine. Geprüft ist, was dahinterliegt: die Erkennung (`QrLeser`) und die
  Auswertung des Inhalts (`QrInhalt`).
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
