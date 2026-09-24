# Konzept AR — Android-Runde: AGP 9 und die Kette dahinter

**Kürzel:** `AR`. Arbeitspakete `AR-01 …`, Entscheidungen `E-AR-01 …`,
Befunde `F-AR-01 …`, Fragen an die Betreiberin `Q-AR-01 …`, Prüfpunkte
`P-AR-01 …`. Commit-Nachrichten beginnen mit dem Paket (`AR-02: …`).
**Herkunft:** Freigabe der Betreiberin vom 24.09.2026 — Backlog **Nr. 65**
(AGP 9 und die Kette), **Nr. 116** (nur die Android-Hälfte) und **Nr. 284**
(Versionsstufe, Changelog-Zeile, Emulatorlauf; Abnahme **P-PK-28**).
**Modell:** Opus. **Keine Versionsnummer im Konzept** — die Umsetzung setzt
sie nach `CLAUDE.md` 2 und `android/version.properties`.
**Ablage:** dieses Konzept; Prüfdokument `Pruefdokument-AR-Android-Runde.md`
entsteht mit AR-02. Zweig `claude/affectionate-newton-6pzfkc`, von `main`
`ba2ec57`. **Backlog-Spanne 334 bis 338** (E-AR-05).

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | **24.09.2026 — AR-01 und AR-02 erledigt, AR-03 in Arbeit.** Android **0.16.0** gesetzt (E-AR-04). AGP 9.4.1, Gradle 9.7.1, Kotlin 2.4.20: Baulauf grün, Prüffälle wie vorher, 78 von 78 Bildern byteweise gleich (Protokoll 7) |
> | Entschieden | **E-AR-01 bis -07** aus der Freigabe, **E-AR-08 bis -13** aus den Antworten auf Q-AR-01 bis -06, beide vom 24.09.2026 (Abschnitt 5) |
> | Offen | nichts |
> | Hakt | Maven Central drosselt diesen Container (F-AR-01); die Runde baut über Googles Spiegel (E-AR-13) |
> | Nächstes | **AR-03** — `compileSdk`/`targetSdk` 37 und die Bibliotheken |

---

## 1. Auftrag

**Nr. 65.** Die Android-Module bauen mit AGP 8.13.2 und Kotlin 2.1.21; der
Rest der Kette (Compose-BOM, `wear-compose`, `core-ktx`, `lifecycle`,
`activity-compose`) hängt daran fest, und Lint meldet es als Fassungshinweise.
Ziel: **AGP 9 und die Kette dahinter auf den Stand vom 24.09.2026**, Baulauf
grün, **0 Lint-Fehler, 0 Fehlschläge**, jede verbleibende Warnung mit Namen
gezählt. **Stummgeschaltet wird nichts** (E-AR-07).

**Nr. 116, Android-Hälfte.** `android/werkzeuge/kontraste.py` misst nur, was
in seiner Paarliste steht. Ziel: Ein Farbpaar, das im Quelltext vorkommt und
in der Liste fehlt, wird gemeldet — auf dem Weg, den Q-AR-02 entscheidet.

**Nr. 284.** Die Hausform-Zeile in `strings.xml` (PK-04/5b) ist ausgelieferter
Code ohne eigene Nummer. Sie bekommt **im selben Zug wie Nr. 65** ihre
Versionsstufe, den Kopfabsatz in `version.properties`, eine Changelog-Zeile
mit dem Präfix `Android` und einen Emulatorlauf mit Bildern der Seite
Einstellungen → Rechtliches (E-AR-04, P-PK-28).

**Nicht Ziel:** die Web-Hälfte von Nr. 116 (`tools/screenshots/kontrast.py`
— P5c ändert die Datei), die Web-Version, der Rahmenplan, neue Funktionen der
App, R8, das Einhängen der Android-Werkzeuge in den Prüfstand (Q-AR-05).

**Grenzen (E-AR-02).** Nicht angefasst werden `server/`, `tools/proben/`,
`tools/pruefstand/`, `tools/screenshots/`, `tools/bedienprobe/`, `.github/`
und unter `docs/konzepte/` alles außer diesem Konzept und seinem
Prüfdokument. Buchführung (`CHANGELOG.md`, `Backlog.md`, `Lizenzen.md` 6a,
`android/LIESMICH.md`, `Technik.md` 5a) ist erlaubt und gibt nur mechanische
Konflikte.

## 2. Ausgangsmaß — AR-01 (gemessen 24.09.2026, vor jeder Änderung)

### 2.1 Arbeitsumgebung

`bash tools/sandbox/aufbauen.sh android` auf frischem Container: **22 s,
Rückgabewert 0**, Nachweis **18 von 18 Stücken** (MariaDB 10.11.14, PHP
8.4.19, Chromium 141 / Firefox 142 / WebKit 26.0 einzeln, Plattform 36,
Build-Tools 36.0.0), **8 von 8 Umgebungswerten**. SDK **462 MB**. Kein KVM
(`/dev/kvm` fehlt, 0 CPU-Kennzeichen `vmx`/`svm`), vier Kerne, 15 GB.
Damit ist die Ausbaustufe `android` **gemessen** — sie stand in
`Sandbox-Setup.md` 0 auf „gebaut, noch nicht gemessen". Was sie nicht
liefert, steht in F-AR-02.

### 2.2 Der Baulauf auf dem unveränderten Baum

`./gradlew build` in `android/`, Android 0.15.1, `main` `ba2ec57`. Grün
erst im **fünften** Anlauf — die vier davor scheiterten an Maven Central,
nicht am Code (F-AR-01). Gezählt aus den Lint-XML und den JUnit-XML je
Modul, nicht aus der Konsole:

| | `handy` | `uhr` |
|---|---|---|
| Lint-Fehler | **0** | **0** |
| Lint-Warnungen | **14** | **0** |
| Prüffälle `testDebugUnitTest` | **264**, davon 15 übersprungen | **71**, davon 0 übersprungen |
| Prüffälle `testReleaseUnitTest` | **264**, davon 15 übersprungen | **71**, davon 0 übersprungen |
| Fehlschläge | **0** | **0** |
| APK Release (unsigniert) | **7 868 394 B** | **19 574 402 B** |
| APK Debug | 11 410 851 B | 26 165 101 B |
| Kotlin-Warnungen im Baulog | 0 | 0 |
| Bilderlauf | **72** Bilder, gesichert mit SHA-256 | **6** Bilder, ebenso |
| Bauzeit, voll (`--offline --rerun-tasks`) | **5 min 18 s**, beide Module | |

**Die 14 Warnungen, einzeln** — alle im Handy-Bericht; der Uhr-Bericht ist
leer, obwohl beide Module denselben Katalog lesen:

| Kennung | Zahl | Gegenstand |
|---|---|---|
| `AndroidGradlePluginVersion` | 1 | AGP 8.13.2 → 9.4.1 |
| `NewerVersionAvailable` | 2 | `kotlin.android` und `kotlin.plugin.compose` 2.1.21 → 2.4.20 |
| `GradleDependency` | 7 | `core-ktx` → 1.19.1, `lifecycle-runtime-ktx` und `lifecycle-service` → 2.11.0, `activity-compose` → 1.13.0, Compose-BOM → 2026.09.00, `wear.compose` `foundation` und `material` → 1.7.0 |
| `PluralsCandidate` | 3 | „%d Minuten", „%d Sekunden", „%d Stunden" — seit 0.15.0 bewusst stehen gelassen (`LIESMICH.md` 2) |
| `UnusedResources` | 1 | `R.string.sync_fehlt_kopplung` — F-AR-09 |

**Zehn der vierzehn sind Nr. 65**, vier sind es nicht. Robolectric 4.17 ist
erschienen und wird **nicht** gemeldet (F-AR-10).

**Der Stand ist reproduzierbar, und das ist der Maßstab für AR-02.** Ein
zweiter Vollbau (`--offline --rerun-tasks`) ergibt dieselbe Zählung, das
Handy-APK **byteweise gleich** und alle 78 Bilder **byteweise gleich** (SHA-256).
Jede Abweichung nach der Umstellung ist also eine Folge der Umstellung, nicht
des Zufalls. Bilder, Lint-Berichte und die Release-APKs liegen als Satz
„vorher" in der Arbeitsumgebung.

### 2.3 Die Kette — heute und verfügbar

Gelesen aus den Maven-Verzeichnissen (`maven-metadata.xml`) und den
AAR-Metadaten (`META-INF/com/android/build/gradle/aar-metadata.properties`)
am 24.09.2026. „verlangt" ist, was die Bibliothek selbst vom Bau fordert.

| Stück | heute | neueste stabile | verlangt | letzte mit `compileSdk` 36 |
|---|---|---|---|---|
| Gradle | 8.14.3 | 9.8.0 (vom **24.09.**), davor 9.7.1 (19.08.) | — | — |
| AGP | 8.13.2 | **9.4.1** | Gradle ≥ 9.1, JDK 17; bringt KGP 2.2.10 mit | — |
| Kotlin (KGP, Compose-Compiler) | 2.1.21 | **2.4.20** | — | — |
| Compose-BOM | 2025.06.01 (Compose 1.8.3) | **2026.09.00** (Compose 1.12.1) | **`minCompileSdk` 37**, AGP ≥ 9.1.0 | 2026.06.01 (Compose 1.11.4) |
| `wear-compose` | 1.4.1 | **1.7.0** | **`minCompileSdk` 37**, AGP ≥ 9.1.0 | 1.6.2 |
| `core-ktx` | 1.16.0 | **1.19.1** | **`minCompileSdk` 37**, AGP ≥ 9.1.0 | 1.18.0 |
| `lifecycle` | 2.9.1 | **2.11.0** | `minCompileSdk` 34 | 2.11.0 |
| `activity-compose` | 1.10.1 | **1.13.0** | `minCompileSdk` 36 | 1.13.0 |
| `wear-input` | 1.2.0 | 1.2.0 | — | — |
| `play-services-wearable` | 20.0.1 | 20.0.1 | — | — |
| Robolectric | 4.16.1 | **4.17** | — | — |
| `androidx.test` core / ext-junit / runner | 1.7.0 / 1.3.0 / 1.7.0 | dieselben | — | — |

**Die SDK-Plattform 37 gibt es nur mit Nebenversion** (`android-37.0`,
`-37.1`, `-37.2`); ein Emulator-Abbild für 37 nur als `google_apis`, nicht
als `default`.

### 2.4 Die Prüfmittel der App

| Mittel | Ergebnis |
|---|---|
| `werkzeuge/kontraste.py` | **24 Paare, 0 unter dem Zielwert** |
| `werkzeuge/farbabgleich.py` | 0 eigene Farbwerte, 1 Web-Token nicht übernommen (`--spur-8`) |

## 3. Befunde

| Nr. | Befund | Folge |
|---|---|---|
| **F-AR-01** | **Maven Central drosselt diesen Container.** Nachgemessen mit `curl`: `repo.maven.apache.org` **13 von 20** Abrufen `429 Too Many Requests`, `repo1.maven.org` 4 von 5; Googles Spiegel `maven-central.storage-download.googleapis.com` **10 von 10** mit 200. Der Ausgangsbau brauchte **fünf Anläufe**: (1) Vorgabe (3 Versuche je Abruf) — rot nach 5 min 20 s, **42** Bausteine nicht aufgelöst; (2) 12 Versuche ab 3 s — schlief sich fest, weil die Wartezeit sich je Versuch verdoppelt, abgebrochen; (3) 8 Versuche ab 0,5 s — rot nach 11 min 7 s, **7** Bausteine; (4) 10 Versuche — rot nach 4 min 30 s, **2** JARs; (5) grün nach 3 min 40 s. Gradle behält jeden Erfolg, darum kommt man hin — aber nur mit Anlauf. **AR-02 und AR-03 holen Hunderte neuer Bausteine** (AGP 9.4.1, KGP 2.4.20, Compose 1.12), und der Teil von Maven Central läuft in dieselbe Drosselung. Der Spiegel führt alle fünf Stichproben, auch das 145-MB-Abbild von Robolectric; dessen SHA-1 stimmt auf Spiegel, Central und im geladenen JAR überein (`dd5e1d35…`) | Q-AR-06 |
| **F-AR-02** | **Die Ausbaustufe `android` lädt kein Emulator-Abbild**, obwohl `Sandbox-Setup.md` 2 es zusagt („Android-SDK 36, JDK 21, Emulator-Abbild"). `aufbauen.sh` holt Plattform und Build-Tools; Emulator und Abbilder holt erst `android/werkzeuge/emulator.sh aufbauen`. Der Nachweis prüft weder JDK noch Emulator | AR-03 zieht die Zusage oder das Skript gerade (Q-AR-01 bestimmt, welche Plattform) |
| **F-AR-03** | **Die Kette verlangt `compileSdk` 37.** Compose-BOM 2026.09.00, `wear-compose` 1.7.0 und `core-ktx` 1.19.1 tragen `minCompileSdk=37` und `minAndroidGradlePluginVersion=9.1.0`. Der Satz in `LIESMICH.md` 4 („API-Stufe, die die App nicht braucht") stimmt für die App, aber nicht mehr für ihre Bibliotheken | Q-AR-01 |
| **F-AR-04** | **`camera` gehört nicht mehr zur Kette.** Nr. 65 nennt die vier CameraX-Bausteine; sie sind mit R63 (Android 0.11.0) entfallen. Und die Zahl 14 stimmt heute zwar wieder, aber mit anderem Inhalt: **zehn** davon sind Nr. 65, vier nicht (2.2) | Backlog-Text wird beim Erledigen berichtigt |
| **F-AR-05** | **Die vier Android-Werkzeuge hängen an keinem Lauf.** `kontraste.py`, `farbabgleich.py`, `bildmarken.sh`, `stroeme.py` werden von keinem Workflow, keinem Prüfstand-Aufruf und keiner `pruefablauf.json`-Zeile gerufen (0 Treffer außerhalb von `android/werkzeuge/`). Der Riegel `kontraste` des Tors ist das **Web**-Werkzeug. Was Nr. 116 an `kontraste.py` verbessert, läuft also nur, wenn es jemand von Hand fährt | Q-AR-05 |
| **F-AR-06** | **Die Paarliste ist schon heute lückenhaft**, ohne dass ein Paar durchfällt: Der blaue Zustandspunkt steht auf der Schneekarte (`punktfarbe = Farbe.blau`, 3,77 : 1 gegen 3,0 — besteht, aber ungemessen), und der Rahmen der Auswahlknöpfe ist `Farbe.linie` auf Schnee (**1,36 : 1**). Ob dieser Rahmen eine Komponentengrenze nach WCAG 1.4.11 ist oder Zierde, hat niemand entschieden | AR-04 (je nach Q-AR-02) |
| **F-AR-07** | **Der Prüfstand erkennt die Ausbaustufe an `platforms/android-36`** (`tools/pruefstand/pruefen.sh`, P5c-Gebiet). Mit `compileSdk` 37 misst diese Zeile das Falsche: Sie ist grün, wenn 36 liegt und 37 fehlt | Backlog-Nummer, nach dem P5c-Merge (E-AR-02) |
| **F-AR-08** | **AGP 9 lässt zwei Dinge still wegfallen, an denen dieses Projekt hängt** (aus den Versionshinweisen, in AR-02 nachzumessen): (1) `android.onlyEnableUnitTestForTheTestedBuildType` steht auf `true` — `testReleaseUnitTest` entfiele, und mit ihm der Release-Fall von `ServeradresseTest` (Nr. 142, „nur im Release"). (2) Eingebautes Kotlin **unterstützt `sourceSets["main"].java.srcDir(…)` mit Kotlin-Dateien nicht** — genau so bindet das Projekt `gemeinsam/quelle` in beide Module ein (E-S4-02) | AR-02 stellt (1) ausdrücklich auf `false` und zieht (2) auf `kotlin.directories` um; beides mit Zahl belegt |
| **F-AR-09** | **Eine unbenutzte Zeichenkette im Handy-Modul.** `sync_fehlt_kopplung` („Koppel die App mit deinem Konto.") steht in `strings.xml` zwischen zwei benutzten Nachbarn der Sync-Anzeige (Nr. 11); keine Kotlin-Zeile ruft sie. `LIESMICH.md` nennt sie in keiner Zählung. Wann ihr Verbraucher wegfiel, gibt die Historie nicht her — die Android-Dateien tragen seit PK-M1 (`5e9333f`) einen umgeschriebenen Werdegang | Q-AR-03 |
| **F-AR-10** | **Die Vermutung in `LIESMICH.md` 2 ist widerlegt.** Dort steht als Kandidat für die Warnung, die zwischen dem 08. und dem 20.09.2026 dazukam, Robolectric 4.16.1 → 4.17. Robolectric 4.17 ist erschienen, und Lint meldet es **nicht** — der Kandidat war es nicht. Ob es die unbenutzte Zeichenkette aus F-AR-09 war, lässt sich nicht belegen: Der Bericht vom 20.09. starb mit dem Läufer | `LIESMICH.md` 2 wird berichtigt (AR-05) |
| **F-AR-11** | **Ein Prüffall übergab seinen Rückstand nie** (gefunden in AR-02 durch Kotlin 2.4, „Expression is unused"). `KopplungRundlaufTest.dienst()` reichte `{ rueckstand }` als nachgestellte Lambda; seit Nr. 114 ist der letzte Parameter von `Kopplungsdienst` `raeumen`. Ohne Wirkung — kein Fall setzt einen Rückstand, die App übergibt benannt | **behoben in AR-02** (benanntes Argument) |

## 4. Fragen an die Betreiberin

*Alle sechs am 24.09.2026 beantwortet; die Antworten stehen als E-AR-08 bis
-13 in Abschnitt 5. Die Fragen bleiben mit ihren Empfehlungen stehen, damit
nachvollziehbar ist, wo die Antwort von der Empfehlung abweicht (Q-AR-01,
Q-AR-03).*

### Q-AR-01 — Kommt API 37 mit?

**Zwei Zahlen, zwei Antworten.**

- **`compileSdk` 37: Empfehlung ja.** Ohne sie endet die Kette bei den
  Fassungen der letzten Spalte in 2.3 (BOM 2026.06.01, `wear-compose` 1.6.2,
  `core-ktx` 1.18.0), und genau die drei Fassungshinweise, um die es in
  Nr. 65 geht, blieben stehen. `compileSdk` ändert nicht, wie die App auf
  einem Gerät läuft, sondern nur, gegen welche Schnittstellen übersetzt wird.
  Preis: Plattform `android-37.0` im Container (AR-03), AGP-Schreibweise mit
  Nebenversion.
- **`targetSdk` 37: Empfehlung nein, bleibt 36.** `targetSdk` schaltet die
  Verhaltensänderungen von Android 17 für die App ein; die hat hier niemand
  durchgesehen, und der Gerätetest (E-R45-7) steht aus. Der Emulator hätte
  dafür nur ein `google_apis`-Abbild. Google Play verlangt seit dem
  31.08.2026 `targetSdk` 36 für neue Apps und Updates (Wear OS: 35) — das
  ist erfüllt; eine Frist für 37 ist noch nicht veröffentlicht (nachgelesen
  24.09.2026, `developer.android.com/google/play/requirements/target-sdk`;
  nach dem jährlichen Muster läge sie im Spätsommer 2027). **Preis:** Neuere Lint-
  Fassungen melden dafür vermutlich `OldTargetApi` — **eine** Warnung mehr,
  mit Namen gezählt, nicht stummgeschaltet. Gemessen wird das in AR-03.

*Gegenoption:* `compileSdk` 36 und AGP 9 mit den Fassungen der letzten
Spalte. Dann bleiben drei Fassungshinweise stehen, und die nächste Runde
fängt beim selben Schritt wieder an.

### Q-AR-02 — Welcher Weg für Nr. 116?

Drei Wege, durchgerechnet am heutigen Quelltext:

| Weg | Was | gemessen / erwartet |
|---|---|---|
| **(a) Paare ableiten** (so steht es im Rahmenplan, Zeile 116) | aus dem Compose-Baum lesen, welche Schrift auf welchem Grund liegt | Der Grund kommt fast nie aus derselben Funktion: `Karte`, `Knopfflaeche`, `Hinweiskasten` setzen die Fläche, die Schrift kommt vom Aufrufer, oft über `if (…) Farbe.a else Farbe.b`. Das verlangt einen Kotlin-Zerleger über Funktionsgrenzen — ein eigenes Projekt, das bei jeder neuen Schreibweise daneben liest |
| **(b) Kreuzprodukt** | je Modul jede Vordergrund-Farbe gegen jede Grund-Farbe; was nicht in der Liste steht, braucht eine Ausnahme | Handy **53** Kombinationen, davon **43** nicht in der Liste; Uhr 6, davon 2. Eine Ausnahmeliste von rund 45 Zeilen „kommt nicht zusammen vor" — das ist die Liste, die niemand pflegt |
| **(c) Vollständigkeit je Modul und Rolle** | Jede Farbe, die im Quelltext eines Moduls als **Schrift** oder **Zeichen** (Punkt, Symbol) vorkommt, muss in der Paarliste **dieses Moduls** in dieser Rolle stehen; jede, die als **Fläche** vorkommt, ebenso. Eine Farbe, deren Rolle das Werkzeug nicht erkennt, ist ein Befund, kein Schweigen | fängt **beide** historischen Fehler: den orangen Punkt auf der Karte (B-S5Z-13 — `orange` als Zeichen im Handy, nicht gelistet) und Rot als Schrift auf der Uhr (B-S5Z-15 — `rot` nur als Fläche gelistet). Fängt **nicht** eine bekannte Schrift auf einem neuen Grund |

**Empfehlung: (c)**, mit einer Selbstprobe, die genau die zwei
historischen Fehler einbaut und rot werden muss, und einer Gegenprobe, die
grün bleibt. Die Paarliste bekommt dafür eine Spalte „Modul". Was (c) nicht
sieht, steht im Kopf des Werkzeugs. Beim ersten Lauf ist mit Befunden zu
rechnen (F-AR-06); jeder wird entweder als Paar eingetragen und gerechnet
oder als Zierde begründet — nicht ausgeblendet.

### Q-AR-03 — Die vier Warnungen, die nicht Nr. 65 sind

Nach AR-03 blieben von den 14 (2.2) vier stehen, dazu vermutlich
`OldTargetApi` (Q-AR-01):

- **`UnusedResources` (1), F-AR-09: Empfehlung — die Zeichenkette in AR-03
  austragen.** Sie ist toter Text; die Runde stuft ohnehin hoch, und eine
  Warnung, deren Beseitigung eine Zeile kostet, gehört nicht ins
  Preisschild einer aufgeschobenen Entscheidung.
- **`PluralsCandidate` (3): Empfehlung — stehen lassen.** Das ist die
  Entscheidung von 0.15.0 (die Texte erscheinen nur bei Werten, für die der
  Plural immer stimmt). Sie in `<plurals>` umzubauen wäre die richtige
  Behebung, aber eine eigene Sache, keine der Kette.
- **Robolectric 4.16.1 → 4.17: Empfehlung — nicht mitnehmen.** Lint meldet
  es nicht (F-AR-10), es gehört nicht zur AGP-Kette, und Robolectric muss mit
  seiner Abbildkennung und `sdk=34` zusammen wandern (`LIESMICH.md` 2.3);
  ein Stufenwechsel änderte jedes der 78 Bilder, ohne dass sich an der App
  etwas geändert hätte. **Ausnahme:** Verlangt AGP 9 oder Kotlin 2.4 eine
  neuere Fassung, wird sie in AR-02 gezogen und dort begründet.

### Q-AR-04 — Emulator: einmal am Ende oder nach jedem Paket?

`Pruefablauf.md` 6.9 verlangt ihn bei jeder Änderung an einem Modul; unter
TCG kostet ein Lauf 10 bis 20 Minuten je Modul. **Empfehlung: einmal, in
AR-05, am Stand, der ausgeliefert wird — mit beiden Modulen.** AR-02 und
AR-03 sind Zwischenstände, die nie ausgeliefert werden; sie belegen sich mit
Baulauf und **Bild-für-Bild-Vergleich des Bilderlaufs** gegen den Stand von
heute (der Satz „vorher" ist gesichert). Handy: Kopplung, Einstellungen →
Rechtliches (P-PK-28), laufender Dienst. Uhr: Startseite auf rundem Glas —
`wear-compose` springt von 1.4 auf 1.7, das ist die größte sichtbare Stelle
der Runde.

### Q-AR-05 — Die Android-Werkzeuge in den Prüfstand?

F-AR-05. Das Einhängen hieße eine Zeile in `tools/pruefstand/pruefablauf.json`
— P5c-Gebiet. **Empfehlung: nicht in AR**, sondern eine Backlog-Nummer aus
der Spanne (334), umzusetzen von der Instanz, die nach dem P5c-Merge `main`
aufnimmt, zusammen mit F-AR-07 (eine zweite Nummer, 335).

### Q-AR-06 — Die Drosselung von Maven Central (F-AR-01)

Drei Wege; **keiner ändert die Bauskripte im Repositorium**:

| Weg | Was | Preis |
|---|---|---|
| **(a) nur Wiederholungen** | `aufbauen.sh android` schreibt `org.gradle.internal.repository.max.tentatives=10` und `…initial.backoff=500` nach `~/.gradle/gradle.properties` — gilt für jeden Bau in diesem Container, auch den `android-bau` des Prüfstands, der `./gradlew build` ohne Schalter ruft | keine neue Quelle; aber **fünf Anläufe, rund 25 Minuten** für einen Bau, dessen Bausteine schon bekannt waren (F-AR-01). Nicht vorhersagbar — ein Lauf, der an einer Drosselung scheitert, sieht aus wie ein roter Bau |
| **(b) Spiegel zuerst, Central dahinter** | dazu ein Init-Skript unter `~/.gradle/init.d/`, das Googles Maven-Central-Spiegel **vor** Maven Central setzt. Nur in diesem Container, geschrieben von `aufbauen.sh android` | eine zweite Bezugsquelle für die Arbeitsumgebung — mit denselben Dateien (SHA-1 dreifach gleich, F-AR-01), betrieben von Google, dem der Bau über `google()` ohnehin traut (AGP, AndroidX). Gradle prüft in diesem Projekt keine Signaturen, gegen Central nicht und gegen den Spiegel nicht |
| **(c) Spiegel in `settings.gradle.kts`** | für jeden Bau überall | eine neue Bezugsquelle für **jeden** Bau, auch den signierten (PK-08), wegen einer Eigenschaft dieses Containers — **nicht empfohlen** |

**Empfehlung: (b), mit (a) als Rückfall darin.** Die Runde holt mehr neue
Bausteine als jede Runde davor, und ein Bau, der nur beim fünften Mal grün
wird, misst die Drosselung und nicht den Code. `Sandbox-Setup.md` 5.1 und
`LIESMICH.md` („Netzfreigaben") nennen Weg und Zahl. Wer (b) nicht will,
nimmt (a); dann wird jeder Anlauf gezählt und im Prüfdokument genannt.

## 5. Entscheidungen

Aus der Freigabe vom 24.09.2026:

| Nr. | Entscheidung |
|---|---|
| **E-AR-01** | Die Runde läuft parallel zu P5c (Rahmenplan 4: Ein Paket, das nur `android/` anfasst, kann immer laufen). **Der PR wird erst nach dem Merge von P5c gemergt**; vorher nimmt der Zweig `main` nach `Pruefablauf.md` 5.3 auf, nicht über „Update branch" |
| **E-AR-02** | Grenzen wie in Abschnitt 1. **P-PK-28 steht in `Pruefdokument-PK-Pruefkette.md`**, das hier nicht angefasst wird: Die Abnahme belegt das AR-Prüfdokument; das Abhaken dort ist Sache der Instanz, die nach dem P5c-Merge aufnimmt |
| **E-AR-03** | Nr. 116 nur in der Android-Hälfte; **Nr. 116 bleibt offen** mit Vermerk, weil `tools/screenshots/kontrast.py` P5c gehört |
| **E-AR-04** | Nr. 284 im selben Zug wie Nr. 65 — **eine** Nummer für die Runde, keine eigene Korrekturnummer für die eine Zeile (E-PK-40). Sie wird mit dem ersten Paket gesetzt, das das APK ändert (AR-02), und gilt bis AR-05 |
| **E-AR-05** | Backlog-Spanne **334 bis 338**, vor dem ersten Push im Kopf von `Backlog.md` eingetragen; 319–328 P5c, 329–333 Konzept BV. Der Absatz ist **wortgleich** aus dem BV-Zweig (`claude/intelligent-carson-q8f7ag`, `4da0c42`) übernommen, der AR dort schon führt — gleiche Änderungen führt Git ohne Konflikt zusammen. Vor jeder Nummer gegen `origin/main` und alle offenen Zweige geprüft |
| **E-AR-06** | Web-Version und Rahmenplan werden nicht angefasst; die Fahrplanzeile schreibt, wer nach dem P5c-Merge `main` aufnimmt |
| **E-AR-07** | Stummgeschaltet wird nichts. Das Ausgangsmaß wird vor jeder Änderung mit Zahl festgehalten (2.2) |

Aus den Antworten auf Abschnitt 4, 24.09.2026:

| Nr. | Frage | Entscheidung |
|---|---|---|
| **E-AR-08** | Q-AR-01 | **`compileSdk` UND `targetSdk` auf 37** — gegen die Empfehlung (nur `compileSdk`). Folge: AR-03 geht die Verhaltensänderungen von Android 17 für Apps mit `targetSdk` 37 durch, Punkt für Punkt gegen den Quelltext beider Module, und hält fest, welche die App trifft und wie sie belegt sind. Der Emulator braucht dafür ein Abbild mit API 37 (nur `google_apis`, Handy; für die Uhr, was das SDK für Wear OS 7 führt) — mit Messung, ob es unter TCG bootet. `minSdk` bleibt (26 / 30) |
| **E-AR-09** | Q-AR-02 | Nr. 116 über **Weg (c)**: Vollständigkeit je Modul und Rolle, Selbstprobe mit beiden historischen Fehlern, Spalte „Modul" in der Paarliste |
| **E-AR-10** | Q-AR-03 | **Die unbenutzte Zeichenkette wird ausgetragen UND die drei Texte werden auf `<plurals>` umgebaut** — über die Empfehlung hinaus. Damit bleibt nach AR-03 keine der 14 Warnungen stehen. Robolectric bleibt auf 4.16.1 (`sdk=34`) |
| **E-AR-11** | Q-AR-04 | **Emulator einmal, in AR-05, beide Module**, am auszuliefernden Stand. AR-02 und AR-03 belegen sich mit Baulauf und Bild-für-Bild-Vergleich gegen den Satz „vorher" |
| **E-AR-12** | Q-AR-05 | **Zwei Backlog-Nummern**: **334** die Android-Werkzeuge in den Prüfstand (F-AR-05), **335** die Erkennung der Ausbaustufe an Plattform 37 (F-AR-07); umzusetzen nach dem P5c-Merge |
| **E-AR-13** | Q-AR-06 | **Googles Maven-Central-Spiegel zuerst, Central dahinter**, dazu die erhöhten Wiederholungen — nur in der Arbeitsumgebung, eingerichtet von `aufbauen.sh android`; die Bauskripte im Repositorium bleiben unberührt |

## 6. Arbeitspakete

| Paket | Was | Berührt | Abnahme |
|---|---|---|---|
| **AR-01 Ausgangsmaß** | Arbeitsumgebung, Baulauf, Kette, Prüfmittel am unveränderten Baum (Abschnitt 2); Bilderlauf „vorher" gesichert | nichts | **erledigt** mit diesem Konzept |
| **AR-02 Bau-Sprache** | Gradle 9 (Prüfsumme auf zwei Wegen, wie `LIESMICH.md` 2.1), AGP 9.4.1, eingebautes Kotlin (`kotlin-android` entfällt), KGP und Compose-Compiler 2.4.20, `gemeinsam/` über `kotlin.directories` (F-AR-08 (2)), `onlyEnableUnitTestForTheTestedBuildType=false` (F-AR-08 (1)), Schalter in `gradle.properties`, die AGP 9 abgeschafft hat. **Bibliotheken und `compileSdk` unverändert** — damit ein Fehler dieses Pakets der Bau-Sprache gehört und nicht einer Bibliothek. Versionsstufe, Kopfabsatz, Changelog-Zeile (E-AR-04). Prüfdokument angelegt | `android/` (Bauskripte, Wrapper, Katalog, `version.properties`), `docs/CHANGELOG.md` | Baulauf grün, **dieselben Prüffallzahlen wie in 2.2 je Bauart**, 0 Lint-Fehler, Warnungen mit Namen; APK Eintrag für Eintrag gegen 2.2 verglichen |
| **AR-03 Die Kette** | `compileSdk` und `targetSdk` 37 (E-AR-08) samt Durchsicht der Android-17-Verhaltensänderungen, BOM, `wear-compose`, `core-ktx`, `lifecycle`, `activity-compose`; unbenutzte Zeichenkette aus, drei Texte auf `<plurals>` (E-AR-10); was dabei veraltet gemeldet wird, wird **umgestellt statt unterdrückt**. Plattform 37 und Build-Tools in `aufbauen.sh`, F-AR-02, Spiegel und Wiederholungen (E-AR-13) | `android/`, `tools/sandbox/aufbauen.sh`, `docs/Sandbox-Setup.md` | Baulauf grün, 0 Lint-Fehler, **0 Warnungen** (oder jede verbleibende mit Namen und Grund); Durchsicht Android 17 mit Ergebnis je Punkt; Bilderlauf Bild für Bild gegen „vorher", jede Abweichung angesehen und benannt |
| **AR-04 Kontraste** | `werkzeuge/kontraste.py` nach Q-AR-02, mit Selbstprobe und Anlass-Zeile (Nr. 116); die Befunde des ersten Laufs aufgelöst (F-AR-06) | `android/werkzeuge/`, `android/LIESMICH.md` 6 | Selbstprobe: beide historischen Fehler rot, Gegenprobe grün; Lauf 0 Befunde, 0 Paare unter Zielwert |
| **AR-05 Abschluss** | Emulatorlauf nach E-AR-11 auf API 37 (P-PK-28), Dokumente (`LIESMICH.md` 2 und 4, `Technik.md` 5a falls nötig, `Lizenzen.md` 6a, `Sandbox-Setup.md`), Backlog (65 und 284 nach *Erledigt*, 116 offen mit Vermerk, 334 und 335 neu nach E-AR-12), Prüfstand-Lauf mit `handy=gebaut`, Prüfdokument mit Prüfliste, PR | `android/`, `docs/` | Bericht über genau diesen Baum mit `handy=gebaut`; Prüfliste abhakbar; PR offen, nicht gemergt |

**Fächerung (Ultracode) — je Paket keine.** AR-02 bis AR-05 teilen ein
Build-Verzeichnis, einen Gradle-Daemon, ein SDK und einen Emulator; zwei
Agenten, die gleichzeitig bauen, messen einander (`CLAUDE.md` 7). Der Text
(Changelog, `version.properties`, Konzept) hat einen Ton. Die einzige lesende
Arbeit, die sich fächern ließe — die Messung in AR-01 —, ist getan.

**Prüfmittel je Paket:** Baulauf mit Zählung (`./gradlew build`, Lint-XML
und JUnit-XML je Modul ausgezählt), `kontraste.py`, `farbabgleich.py`,
Textprobe über `tools/quelltext/` (Bereich `d`), und am Ende der Prüfstand.
Sie laufen **zuletzt** in jedem Paket (`Pruefablauf.md` 6.7).

## 7. Protokoll

### AR-01 — Ausgangsmaß (erledigt 24.09.2026)

Abschnitt 2. Problem: Maven Central drosselte den Container; der
unveränderte Stand wurde erst im fünften Anlauf grün (F-AR-01). Gelöst für
die Runde durch E-AR-13.

### AR-02 — Bau-Sprache (erledigt 24.09.2026)

**Was:** Gradle 8.14.3 → 9.7.1 (Prüfsumme zweifach, Wrapper-JAR aus der
geprüften Verteilung), AGP 8.13.2 → 9.4.1, Kotlin und Compose-Compiler
2.1.21 → 2.4.20; `kotlin-android` aus beiden Modulen, in der Wurzel nur noch
zur Festlegung der Fassung; `gemeinsam/` über `kotlin.directories`;
`android.onlyEnableUnitTestForTheTestedBuildType=false`;
`kotlin { compilerOptions }` entfällt (`jvmTarget` folgt
`targetCompatibility`). Android **0.16.0**, Kopfabsatz, Changelog-Eintrag,
`LIESMICH.md` 2, 2.1, 3 und 4.

**Probleme und Lösungen:**

1. **Zwölf Veraltungen in den Bauskripten** — die drei Berichtsschalter von
   Lint und `by configurations.creating`, je Modul. Umgestellt statt
   unterdrückt; `--warning-mode all` meldet 0.
2. **Vier neue Kotlin-Warnungen** (Ausgangsmaß 0). Drei Kleinigkeiten
   (`else` in `Uhrbedienung`, zweimal `.toInt()` in `HandyBildTest`) — und
   **ein Fehler im Prüffall (F-AR-11):** `KopplungRundlaufTest` übergab den
   Rückstand als nachgestellte Lambda; die ging seit Nr. 114 an `raeumen`.
   Ohne Wirkung, weil kein Fall einen Rückstand setzt; die App übergibt
   benannt. Behoben mit benanntem Argument; 0 Kotlin-Warnungen.
3. **Die Wahl der Nummer.** 0.16.0 und nicht 0.15.2, weil `targetSdk` 37
   (E-AR-08) der App neue Plattformregeln gibt — gesetzt jetzt, weil AR-02
   das erste Paket ist, das das APK ändert (E-AR-04).

**Gemessen:** Prüfdokument 2. Kern: 264 (15 übersprungen) und 71 Fälle je
Bauart wie vorher, **78 von 78 Bildern byteweise gleich**, Manifest und
Ressourcen beider APKs gleich; Lint danach Handy 13 (8× `GradleDependency`
einschließlich `compileSdk` 37, 1× `OldTargetApi`, 3× `PluralsCandidate`,
1× `UnusedResources`), Uhr 1 (`compileSdk` 37). Die drei AGP-/Kotlin-Hinweise
sind weg; dazugekommen sind die drei, die AR-03 auflöst.

**Neuer Befund F-AR-11** (oben beschrieben) — trägt keine eigene
Backlog-Nummer, weil er im selben Paket behoben ist und im Changelog steht.
