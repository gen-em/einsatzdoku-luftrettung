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

#### Die drei Rundlaufklassen laufen wieder gegen den aktuellen Stand

*Bis Android 0.10.1 stand hier eine Anleitung, wie man neben der Installation
eine zweite „vom Stand vor S5" aufsetzt.* Sie wurde gebraucht, weil
`KopplungRundlaufTest`, `SendeRundlaufTest` und `MissionRundlaufTest` über
`Kopplungsdienst.koppeln(basis, code, geraet)` koppelten — den alten Weg, den
`pair.php` seit Web 13.0.0 mit `400 {"error":"aktion"}` beantwortet.

**Mit Android 0.11.0 ist das erledigt.** Die drei Klassen koppeln über die
drei Anliegen aus Vertrag 1a, und die Vorbereitung ist eine Zeile weniger:

```bash
sh tools/referenzdatensatz/einspielen/lokal_starten.sh
cd android
./gradlew :handy:testDebugUnitTest --rerun-tasks \
          -Pnadoku.rundlauf=http://127.0.0.1:8080/
```

**Kopplungscodes werden nicht mehr vorbereitet** — es gibt keine Tabelle mehr,
in die man sie legen könnte. Den Schritt, den sonst ein Mensch im Browser tut,
übernimmt `kopplung/Kopplungshilfe.kt`: Sie ordnet den Code über `mariadb`
dem Konto 1 zu. Ihr Dateikopf begründet, warum nicht über die Weboberfläche.

**Sie leert auch den Ratenschutz**, und das ist keine Bequemlichkeit: `pair.php`
lässt zwanzig `start`-Aufrufe je zehn Minuten und Absenderadresse zu
(E-S5-33). Die drei Rundlaufklassen machen in einem Lauf mehr als zwanzig,
und alle kommen von `127.0.0.1`. Ohne das Leeren läuft der erste Durchgang
grün und der zweite meldet für jeden Fall `429` — wer das sieht, sucht den
Fehler in der App. Genau das ist beim ersten Lauf dieses Pakets passiert.

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

**Stand 0.13.1, 05.09.2026** — `./gradlew build` im Container, 5 min 19 s
bei warmem Zwischenspeicher:

| | `handy` | `uhr` |
|---|---|---|
| Lint-Fehler | **0** | **0** |
| Lint-Warnungen | **14** — unverändert, alle auf `libs.versions.toml` (siehe unten) | **0** |
| Prüffälle | **249**, davon 14 übersprungen (der Server-Rundlauf) | **71**, davon 0 übersprungen |
| APK (unsigniert, Release) | **7 869 238 B** | **19 574 402 B** |

Neu sind drei Prüffälle, alle Bildmessungen — Antworten auf Befunde vom S24,
die der Bilderlauf nicht gesehen hatte, obwohl sie in seinen Bildern standen:

- `ZweierwahlBildTest`: Die Wahl „Mit Phasenknöpfen / Nur aufzeichnen" füllt
  ihre 48-dp-Zeile — gemessen in einer **rollenden** Spalte, weil der Fehler
  nur dort entsteht (`fillMaxHeight()` gegen unendliche Höhe). Gemessen:
  Rahmen 48,0 dp, Hellblau 46,0 dp, Trennstrich 2 px, Schriftversatz
  0,00 dp. Der Bilderlauf misst die Bedienhöhe an den **farbigen** Knöpfen
  und hatte darum nichts zu sagen.
- `SymbolBildTest`: Der Vordergrund des adaptiven Symbols nimmt bei 40 und
  108 dp denselben Anteil ein (72 % der Kachel) und bleibt im sicheren
  Kreis — Backlog Nr. 81, mit festen dp wäre er bei 40 dp größer als die
  Kachel.
- Zwei Bildschirme mehr im `HandyBildTest` (**78 Bilder statt 72**): der
  Hinweis „Benachrichtigungen ausgeschaltet" vor dem Dienst und darin.

Der Stand davor, zur Einordnung der Zahlen:

Stand E3 (Android 0.10.1), `./gradlew build` im Container, 03.09.2026:

| | `handy` | `uhr` |
|---|---|---|
| Lint-Fehler | **0** | **0** |
| Lint-Warnungen | **14** | **0** |
| Prüffälle | **224**, davon 12 übersprungen | **71**, davon 0 übersprungen |
| APK (unsigniert, Release) | **9 657 735 B** | **19 574 446 B** |

Zusammen **295 Prüffälle** — 75 mehr als der Stand davor (0.7.7: 167 / 53 =
220). Woher sie kommen:

| Paket | Prüffälle |
|---|---|
| E1 | `OrtungswaechterTest` 24, `OrtungszuhoererTest` 2, `AusduennerTest` +5 (`brauchbar()`), `NachrichtenformatTest` +5, `UhrsteuerungTest` +3 |
| Bilderlauf | `HandyBildTest` 1 Fall für **72 Bilder** (24 Bildschirme × 3 Breiten), `UhrBildTest` +3 Fälle (4 → 6 Bilder) |
| E2 | `NachsendenTest` 9, `DienstfolgeTest` 6, `AbgewieseneTest` 5, `SendetaktTest` +5 |
| E3 | `OrtungsanzeigeTest` 7 (die drei davon in `OrtungswaechterTest` sind oben schon gezählt) |

Dass es so viele sind, ist kein Übereifer: Paket E ist zu einem großen Teil
gerätegebunden, und diese Fälle sind das Einzige, was im Container überhaupt
läuft (Abschnitt 7).

**Mit laufender Installation sind es 224 von 224, 0 übersprungen** — dann
laufen auch die drei Rundlaufklassen. `SendeRundlaufTest` ist die Abnahme von
E2: Er belegt am echten `ingest.php`, dass ein Dienstende beim Server ankommt
(`rest_segments.final = 1` mit `ended_at`, `days.ended_at` gesetzt).

**Die Warnungszahl blieb bei 14, und einmal war sie 15.** Der neue Text
„· GPS ok" ergab `Typos: "ok" is usually capitalized as "OK"`. Stummgeschaltet
wurde nichts — der Wortlaut heißt jetzt „· GPS empfängt", und das ist die
bessere Aussage: Er nennt das Gemessene (es kommen brauchbare Funde) statt
eine Güte zu behaupten, die die App gar nicht abstuft. Ebenso gefunden und
behoben: `postDelayed(r, token, ms)` gibt es erst ab **API 28**, `minSdk` ist
26 — vier Lint-Fehler `NewApi`. Der Weg, der ab API 1 trägt, ist
`postAtTime(r, token, uptimeMillis() + ms)`.

Die APKs sind gegenüber 0.7.7 um 58 824 B (Handy) und 82 652 B (Uhr) gewachsen. Dass die Uhr-APK doppelt so groß ist wie die
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

Die **12 übersprungenen** Fälle sind der Server-Rundlauf; mit laufender
Installation sind es 224 von 224 (siehe unten). *(Hier stand „11" — die Zahl
war seit 0.7.0 nicht nachgezogen worden; gezählt sind es zwölf.)* Die 71 Fälle der Uhr laufen
immer — sie brauchen weder Server noch Gerät, weil geprüft wird, was die
Bedienung *entscheidet* und was der Funk *zusichert*, nicht was die Uhr
*zeichnet* (E-S4-40).

### 2.2 Bilder der Oberfläche, ohne Emulator und ohne Gerät

**Zwei Bilderläufe, einer je Modul.** Beide bauen die Ansicht in einer
Robolectric-Activity auf, messen und zeichnen sie selbst auf eine Bitmap und
legen PNG unter `<modul>/build/bilder/` ab:

```bash
./gradlew :uhr:testDebugUnitTest   --tests '*UhrBildTest*'      #  6 Bilder
./gradlew :handy:testDebugUnitTest --tests '*HandyBildTest*'    # 72 Bilder
```

| | `UhrBildTest` (seit C1) | `HandyBildTest` (seit E1) |
|---|---|---|
| Bilder | 6 — zwei Marken, laufende Ansicht, zwei Ortungszustände, 227-dp-Uhr | **78** — 26 Bildschirme × 3 Breiten (360, 411, 600 dp); bis 0.13.0 waren es 24 × 3 |
| Bedienhöhe | 48 dp je Bild | 48 dp an **75 von 78** — die drei `kopplung-code` tragen keinen farbigen Knopf und werden nicht daran gemessen (benannte Ausnahme im Prüffall) |
| Beschnitt | Anteil außerhalb des **runden Glases**, gerechnet | Knopffarbe an der **Bildkante**; dazu der **ganze** Inhalt gegen den sichtbaren Bereich |
| Unterscheidbarkeit | alle 6 paarweise verschieden | alle 78 paarweise verschieden, **und je Breite** |

**Was der Bilderlauf nicht misst, obwohl es im Bild steht (0.13.1):** alles,
was keine Primär- oder Beenden-Farbe trägt. Die Zweierwahl füllte ihre Zeile
in 72 Bildern nicht, und keine Zahl sagte es — die Bedienhöhe wird an den
farbigen Knöpfen gemessen, und die Wahl ist hellblau auf Schnee. Seither
stehen zwei Messungen daneben, beide nach demselben Muster (Robolectric,
NATIVE, selbst gezeichnet): `ZweierwahlBildTest` (Höhe der blauen Fläche,
Trennstrich, Schriftmitte gegen Rahmenmitte — in einer **rollenden** Spalte,
weil der Fehler nur dort entsteht) und `SymbolBildTest` (das adaptive Symbol
bei 40 und 108 dp; der Vordergrund nimmt bei beiden denselben Anteil ein).
Eine Regel, die nur Farbflächen zählt, misst nur Farbflächen; wer einen
Baustein ohne solche ändert, schreibt ihm seine Messung dazu.

**Warum die letzte Zeile die wichtigste ist (F-P3-AQ).** Der Bilderlauf des
Web meldete nach O9c „248 Bilder, 0 Überlauf" — 176 davon zeigten die
Anmeldeseite. Beide Läufe vergleichen deshalb die SHA-256 aller erzeugten PNG
und bestehen darauf, dass keine zweimal vorkommt. Das hat sich sofort
ausgezahlt: Die zwei neuen Uhr-Bilder für „keine Ortung" und „GPS sucht"
kamen **byteweise gleich** heraus, weil die Zeile auf der 192-dp-Uhr im
Phasenmodus unter dem Rand lag und in keinem der beiden zu sehen war
(B-S5Z-17). Ohne den Vergleich wären zwei Dateien entstanden, die nichts
belegen.

**Zwei Funde im Prüfmittel selbst, beide behoben.** Die erste Fassung
maß den waagerechten Überlauf (siehe unten) — eine Zahl, die nichts messen
konnte. Die zweite fand Knöpfe, die von der Faltkante **angeschnitten**
werden, aber keinen, der **vollständig** darunter liegt: Sie meldete „kein
Knopf unter der Faltkante", während einer 80 dp tiefer lag (B-S5Z-18). Der
Lauf misst deshalb **zweimal** — einmal auf der Gerätehöhe für das Bild,
einmal ohne Faltkante für die Zählung — und nennt in der Ausgabe „N von M
Knöpfen, Inhalt X dp".

**Was `HandyBildTest` ausdrücklich NICHT misst:** waagerechten Überlauf im
Sinn des Web-Laufs. Die erste Fassung meldete ihn, und die Zahl war wertlos —
jeder Bildschirm der App ruft `fillMaxSize()`, also gewinnt die Einschränkung
immer, und ein zu breites Kind wird von Compose **beschnitten statt
gemeldet**. „Verlangte Breite = Gerätebreite" stand in jeder Zeile,
gleich was darin stand. An ihre Stelle sind die zwei Messungen getreten, die
die **Folge** des Beschnitts dort fassen, wo sie jemanden trifft: Knopffarbe
an der Bildkante, und Knöpfe unter der Faltkante.

**Was beide nicht können:** Bedienzustände. Kein Tippen, kein Bildlauf, keine
Tastatur, keine Schriftrasterung eines echten Geräts, keine Systemleisten.

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

### HTTP zum Prüfstand: nur das Prüf-APK

Die App spricht **nur HTTPS** (E-S4-14), und Android setzt dasselbe seit
Fassung 9 von sich aus durch. Die örtliche Prüfinstallation spricht aber HTTP
(Port 8080); ihr HTTPS-Port trägt ein selbstsigniertes Zertifikat.

Wer den Kopplungsweg **am Emulator** gegen sie ansehen will, braucht deshalb
zweierlei — beides seit Android 0.11.0 im Baum:

```bash
adb reverse tcp:8080 tcp:8080          # 127.0.0.1 im Gerät -> Host
./gradlew :handy:assembleDebug -Pnadoku.serverBasis=http://127.0.0.1:8080/
```

Die Klartext-Ausnahme liegt in `handy/src/debug/` und geht damit **nur** in
das Prüf-APK ein (`org.genem.nadoku.pruef`). Das Release-APK kennt sie nicht;
dort fiele ein `http://` durch, selbst wenn jemand es einbaute.

**Ohne die Rückleitung sieht man nichts:** Der PHP-Server der
Prüfinstallation hört auf `127.0.0.1:8080`, nicht auf `0.0.0.0` — aus dem
Emulator ist er über `10.0.2.2` also nicht zu erreichen. Der erste Versuch
dieses Pakets endete deshalb dreimal bei „Keine Verbindung", und die Ursache
lag nicht dort, wo man sie suchte.

### Der Versionscode: zwei Zahlen, eine Fassung

Seit Android 0.11.1 (Backlog Nr. 98) tragen die beiden Module **verschiedene**
Versionscodes bei **gleichem** Versionsnamen:

| Modul | Versionscode | Versionsname |
|---|---|---|
| `handy` | `Haupt·10000 + Neben·100 + Korrektur` | aus `version.properties` |
| `uhr` | derselbe **+ 1 000 000** (`UHR_VERSATZ`) | derselbe |

**Wozu:** Die Play Console verlangt unter einer Anwendungs-ID je hochgeladenem
APK einen eindeutigen Code. Beide Module tragen dieselbe ID (E-S4-01) — ohne
Versatz lässt sich das zweite nicht hochladen, und ohne das gibt es kein
Wear-OS-Release.

**Die Zählung bleibt trotzdem eine** (E-S4-02): `version.properties` führt
genau eine Nummer, und beide Module tragen sie als Versionsnamen. Was sich
unterscheidet, ist eine Zahl, die nur Play liest.

Der Baulauf nennt beide, damit eine falsche auffällt:

```
NAdoku Android 0.11.0 (Versionscode Handy 1100, Uhr 1001100)
```

**Nachmessen am fertigen APK**, nicht an dieser Ausgabe:

```bash
$ANDROID_HOME/build-tools/*/aapt2 dump badging <apk> | head -1
```

### Was der Bilderlauf nicht kann: Dialoge

Gefunden am 04.09.2026 beim Versuch, den Verbrauchshinweis (Nr. 82) als
Bildfall aufzunehmen: **1 dp Inhalt, 0 Knöpfe** bei allen drei Breiten. Ein
Compose-`AlertDialog` rendert in einem **eigenen Fenster**, und die
Bildaufnahme über die Wurzel-Composable sieht davon nichts.

Das trifft jeden Dialog der App — Akkufrage, Verbrauchshinweis, Rückfrage,
Trennfrage. Keiner ist im Bilderlauf abgebildet, und das ist eine Lücke des
**Mittels**, nicht des Fallkatalogs: Ein Fall dafür ergäbe ein leeres Bild,
das nichts belegt und trotzdem in der Gesamtzahl mitzählt.

**Wo Dialoge stattdessen belegt werden:** am Emulator (Aussehen) und über
Prüffälle auf den Merkern (Verhalten, `VerbrauchhinweisTest`).

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

### Was Paket E1 dazugelegt hat (Android 0.8.0)

Der Ortungswächter ist **fast vollständig gerätegebunden**. Was in diesem
Container läuft, ist seine Regel — die Zustandsmaschine mit eingespeister
Zeit; was sie auslöst und was daraus folgt, läuft nicht:

| Nicht prüfbar | Warum | Wo es geprüft wird |
|---|---|---|
| **Vibration** der Warnung | Kein Gerät, kein Emulator (siehe unten) | Gerätetest, einmal auch mit „Nicht stören" — die Einstellung kann sie unterdrücken, und das bleibt so |
| **Die Fristen** 120 s (Erstfix) und 60 s (Signalverlust) | Kein GPS. Beide Zahlen sind **hergeleitet, nicht gemessen** | Gerätetest: Kaltstart im Freien mitstoppen, Handy drei Minuten in die Tiefgarage |
| **Ob `onProviderDisabled` ankommt** | Der Standort lässt sich hier nicht abschalten, weil es nichts gibt, an dem er anginge | Gerätetest: Schnelleinstellung im laufenden Dienst |
| **Der `AbstractMethodError` auf Android 8–10** | Kein solches Gerät. Der Befund ist aus der Plattform-Schnittstelle **abgeleitet** | Gerätetest, falls ein Gerät greifbar ist. Ersatzweise der Reflexionsfall — er belegt die Bauform, nicht ihre Wirkung |
| **Die drei Meldungs-IDs nebeneinander** | `adb shell dumpsys notification` braucht ein Gerät | Gerätetest, mit dem Auge |
| **Job-Zeiten unter Doze** und Samsungs Akkusteuerung | dasselbe | Gerätetest |

Und was **E2** dazugelegt hat:

| Nicht prüfbar | Warum | Wo es geprüft wird |
|---|---|---|
| **Wann der Nachsende-Job wirklich anläuft** | `JobScheduler` unter Doze verhält sich auf einem Gerät anders als in der JVM; `adb shell cmd jobscheduler run -f` bräuchte einen Emulator, und den gibt es hier nicht | Gerätetest: Flugmodus an, Dienst beenden, Flugmodus aus, Zeit messen |
| **Ob er einen Neustart übersteht** | `setPersisted` wird vom System über den Neustart getragen; nachstellen lässt sich das nur mit einem Neustart | Gerätetest, ausdrücklich **ohne** die App zu öffnen |
| **Ob ein Dienstende von der Uhr ankommt** | keine Wear-OS-Uhr, keine Telefonseite des Data Layer | Gerätetest mit Uhr |
| **Ob die aktive Standmeldung** (E3) auf Hardware ankommt | dasselbe — der Data Layer braucht eine Telefonseite | Gerätetest mit Uhr (E3-1) |
| **Ob der Sendelauf beim Wegwischen der App abbricht** | Prozessverwaltung des Herstellers | Gerätetest: beenden, sofort wegwischen, Web ansehen |

Was **stattdessen** belegt ist: `SendeRundlaufTest` fährt den ganzen Weg gegen
ein echtes `ingest.php` und prüft am Server nach, dass Segment und Diensttag
geschlossen sind. Er sagt nichts über Zeitpunkte und nichts über
Prozesstode — aber alles über die Nachricht selbst.

### Der Emulator — er läuft, und er ist ab 03.09.2026 Pflicht

**Die Regel zuerst** (CLAUDE.md 6, angewiesen am 03.09.2026): Bei jeder
Änderung an einem der beiden Android-Module läuft der Emulator mit, Aussehen
**und** Funktion werden darin geprüft, und beides wird mit Bildern belegt —
so, wie `tools/uhr-pruefstand/` Stufe II für die Garmin-Uhr ist. Werkzeug:
`android/werkzeuge/emulator.sh`.

- ~~**Kein Emulator.**~~ ~~**Nachtrag 03.09.2026: In diesem Container läuft
  keiner — das x86_64-Abbild braucht KVM.**~~ **Auch das war falsch, und zwar
  am selben Tag berichtigt.**

  **Was stimmt:** KVM fehlt wirklich. `/dev/kvm` ist nicht da, `/proc/cpuinfo`
  nennt weder `vmx` noch `svm`, und `emulator -accel-check` antwortet
  „KVM requires a CPU that supports vmx or svm" — die CPU ist selbst
  virtualisiert, Verschachtelung ist nicht freigegeben.

  **Was nicht stimmt:** dass daraus „kein Emulator" folgt. Mit **`-accel off`**
  übersetzt QEMU die x86_64-Befehle selbst (TCG) und braucht die
  Verschachtelung nicht. Der Fehlschluss war, „startet nicht ohne Weiteres"
  für „geht nicht" zu nehmen und deshalb gar nicht erst bis zum Startversuch
  zu gehen. Er kostete Paket E die ganze Stufe II.

  **Zwei Stolpersteine davor**, beide am 03.09.2026 gemessen:

  | Stolperstein | Was zu tun ist |
  |---|---|
  | `emulator -version` scheitert an `libpulse.so.0` | `apt-get install -y libpulse0`. Die QEMU-Binärdatei bindet sie hart — auch mit `-no-audio`, auch mit `-no-window`. |
  | `-accel auto` (die Vorgabe) bricht ab | `-accel off` setzen. Dazu `-gpu swiftshader_indirect`, denn die Grafik rechnet ebenfalls die CPU. |

  | Abbild | Ergebnis |
  |---|---|
  | **x86_64** (`system-images;android-34;google_apis;x86_64`) | **läuft** mit `-accel off`. |
  | **arm64** | `FATAL | Avd's CPU Architecture 'arm64' is not supported by the QEMU2 emulator on x86_64 host.` (Emulator 37.1.11) — der heutige Emulator übersetzt keine fremde Architektur mehr. *Übernommen aus der S5-Vorbereitung 8.3, dort gemessen.* |

  **Was er kostet, und warum er trotzdem ans Ende gehört.** TCG rechnet
  **einkernig**: Der QEMU-Prozess steht bei rund 100 % *eines* Kerns, `-cores 4`
  ändert daran nichts. Boot und Aufspielen liegen darum in Minuten. Wer den
  Emulator nach jeder Datei anwirft, verbraucht die Zeit, die die Änderung
  selbst gebraucht hätte — er läuft **am Ende eines Arbeitspakets**, mit
  Wortliste, Kontrasten und Bilderlauf.

  Was das nicht ändert: **Der Bilderlauf bleibt** (Abschnitt 2.2, Robolectric
  im NATIVE-Modus). Er und der Emulator messen Verschiedenes — der eine das
  gerechnete Bild, deterministisch und in Sekunden, der andere das gelaufene
  mit Systemleisten, echter Schriftrasterung, rundem Glas und Bedienzuständen.
  Keiner ersetzt den anderen. Die instrumentierten Prüffälle aus 0.7.6 bleiben
  ebenfalls, was sie sind — sie brauchen nur ein Android-System, gleich
  welches.

  Was der Lauf von 0.7.2 kostete, zur Erinnerung:
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

  **Dritter Lauf am 04.09.2026 (0.13.0): eine Ansicht hinter der Kopplung
  ansehen.** Die Einstellungen sind aus `KopplungAnsicht` **nicht**
  erreichbar — der Weg dorthin geht nur über `DienstAnsicht`, und die zeigt
  sich erst, wenn `Schluesseltresor.gekoppelt()` wahr ist. Der Tresor ist mit
  dem `AndroidKeyStore` verschlüsselt; von außen lässt sich nichts
  hineinlegen. Der Weg, der trägt, ist die **echte Kopplung gegen die
  örtliche Installation**:

  ```bash
  sh tools/referenzdatensatz/einspielen/lokal_starten.sh
  ./gradlew --offline -Pnadoku.serverBasis=http://127.0.0.1:8080/ \
      :handy:assembleDebug
  adb reverse tcp:8080 tcp:8080          # 127.0.0.1 im Emulator = Host
  adb install -r handy/build/outputs/apk/debug/handy-debug.apk
  adb shell pm clear org.genem.nadoku.pruef   # alten Tresor loswerden
  ```

  Dann in der App „Kopplung starten", den Code aus
  `SELECT code FROM pair_sessions ORDER BY id DESC LIMIT 1` nehmen und im
  Browser bestätigen (`action=koppeln_bestaetigen`, ein POST mit `csrf`,
  in **einer** Sitzung), zuletzt am Gerät „Ja, koppeln". Klartext-HTTP geht
  nur im Prüf-APK — `src/debug/res/xml/netzwerk_pruefstand.xml`.

  Zahlen des Laufs: Boot **621 s**, Handy-APK **60 s** (schon installiert,
  daher schnell). Kopplung, Einstellungen, beide Rechtstext-Knöpfe und die
  Trennung sind darin bedient worden.

  **Drei Dinge, die dabei Zeit gekostet haben:**

  - `input swipe` mit 300 ms Dauer **scrollt nicht** — Compose nimmt es als
    Tipp. 900 ms über eine lange Strecke wirkt zuverlässig.
  - Der Abzug kommt gelegentlich als **0-Byte-Datei** zurück (`screencap`
    trifft einen Augenblick, in dem die Oberfläche neu zeichnet). Einfach
    wiederholen; `file` sagt, ob ein PNG darin steht.
  - **Der einzige Browser des Abbilds ist `org.chromium.webview_shell`**
    (WebView Browser Tester 113.0.5672.136). Er nimmt einen `ACTION_VIEW`
    an und zeigt die Adresse — aber er **rendert nicht**: „WebView Shell
    isn't responding", auch nach „Wait" und 45 s. Chromium auf einem
    emulierten Kern. Dass die Seite ankam, misst man deshalb **am Server**
    (`tail /tmp/php-server.log` → `[200]: GET /datenschutz.php`), nicht am
    Bild.

  **Vierter Lauf am 05./06.09.2026 (0.13.1): die vier Befunde vom S24.**
  Drei Anläufe, und der dritte trug: Boot **509 s** (Kaltstart mit
  `-wipe-data`, `handy34`, **ein** Gast-Kern), Handy-APK **65 s**. Der Weg
  wie beim dritten Lauf: örtliche Installation (`lokal_einrichten.sh`, dafür
  MariaDB und socat nachinstalliert — beides fehlt im Container), Prüf-APK
  gegen `http://127.0.0.1:8080/`, `adb reverse`, Kopplung über
  `UPDATE pair_sessions SET user_id = 1` (der Weg der `Kopplungshilfe`).
  Bedient und mit Bildern belegt (`docs/bilder/android-0.13.1/`):

  | Bild | Was es zeigt |
  |---|---|
  | `02-startbild-symbol` | das adaptive Symbol auf dem Startbild von Android 12+, Marke mittig mit Luft |
  | `03-kopplung-frage` | Kopplung gegen die örtliche Installation, `devices` mit `handy` und Modell |
  | `04-dienst-hinweise-zweierwahl` | die Zweierwahl füllt ihre Zeile (Blau bis zur Rahmenlinie, Trennstrich, Schrift mittig); dazu der neue Hinweis „Benachrichtigungen ausgeschaltet" neben der Ortungssperre |
  | `05-zweierwahl-rechts` | dieselbe Zeile nach dem Tipp auf „Nur aufzeichnen" |
  | `06-meldungseinstellung-system` | wohin der Knopf führt: die Benachrichtigungsseite der App im System — und das App-Symbol dort, in Kachelgröße, ohne Beschnitt (Backlog 81) |
  | `07-dienst-laeuft-meldungen-aus` | Dienst läuft, Freigabe abgelehnt: kein Symbol in der Leiste, und die App sagt es |
  | `08-dienst-mit-leistensymbol` | nach dem Einschalten: der Hinweis ist fort, der Punkt steht links oben in der Leiste |
  | `09-benachrichtigung` | die Dauermeldung aufgeklappt, mit Symbol |
  | `10-startprogramm-symbol` | das Symbol im Startprogramm, ohne Beschnitt |

  Dazu ohne Bild: die Zurück-Taste aus den Einstellungen führt zur
  Dienstansicht (Fokus bleibt `HauptActivity`; vorher wäre die App zu),
  „Code kopieren" ist auf dem Code-Bildschirm zu sehen (`t03` im Lauf), der
  Dienst endet mit `[200] POST /ingest.php` und einem geschlossenen
  Diensttag, die Trennung mit `[200] POST /pair.php` und 0 Geräten am Konto.
  **Nicht belegt:** die Einblendung des Systems nach „Code kopieren" — der
  Abzug traf zweimal einen ANR-Dialog und einmal den Bildschirm ohne
  Einblendung; der Code selbst stand danach nicht nachweisbar in der Ablage,
  weil `dumpsys clipboard` auf diesem Abbild nichts ausgibt. Der Weg ist
  eine Zeile `ClipboardManager.setPrimaryClip`; am Gerät steht er auf der
  Prüfliste.

  **Was diesmal Zeit gekostet hat — und was seither im Werkzeug steht:**

  - **`-cores 1` statt 4.** TCG rechnet einkernig; vier Gast-Prozessoren auf
    einem Faden bekommen je ein Viertel der Zeit, und die Wachhunde des
    Systems messen Wanduhr. Mit vier Kernen liefen `systemui` und `phone` in
    eine **Dauerschleife aus ANRs**, in der der Eingabeverteiler keine
    Berührung mehr zustellte (`InputDispatcher: no touchable window`) —
    `system_server` bei 150 %, und jeder Tipp auf „Wait" stellte den
    nächsten Dialog, weil der Dialog selbst ein Systemfenster im selben Stau
    ist. Mit einem Kern: `system_server` bei 20 %, keine Schleife.
  - **Nie einen Gradle-Lauf neben dem Emulator.** Der neue Prüffall lief
    zweimal parallel zur Stufe II; beide Male begann die Schleife genau dann.
  - **Fehlerdialoge aus, Bildschirm an, bevor die App läuft:**
    `hide_error_dialogs 1`, die drei Animationsmaßstäbe auf 0,
    `svc power stayon true` — `emulator.sh start` setzt das jetzt selbst.
  - **`screencap` liefert nach einigen Minuten nur noch Schwarz** (15 197
    Bytes, bei `mWakefulness=Awake`, auch nach Schlafen/Wecken). Die
    Emulator-Konsole (`adb emu screenrecord screenshot`) liest den
    Bildspeicher selbst und zeigt das richtige Bild; `emulator.sh bild`
    nimmt seither diesen Weg.
  - **`adb shell stop` / `start` hilft nicht** (Rahmenwerk nach 210 s zurück,
    Anzeige schwarz), und ein harter `kill` hinterlässt ein `/data`, das beim
    nächsten Start in einer Schleife hängt (`netd` findet
    `libnetd_resolv.so` nicht, Zygote startet endlos neu). Der Weg, der
    trägt: `kill` auf die QEMU-PID und Neustart mit **`-wipe-data`** — APK
    und Einstellungen sind danach fort und in zwei Minuten wieder da.
  - `mCurrentFocus` steht zweimal in `dumpsys window` (ein Eintrag je
    Anzeige); `emulator.sh bild` las nur die erste Zeile und verweigerte den
    Abzug, obwohl die App den Fokus hatte. Es prüft jetzt beide.

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
