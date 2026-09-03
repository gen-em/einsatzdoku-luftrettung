# Prüfdokument S5 — Paket E (Android: Ortung und Dienstende)

**Zum Konzept `Konzept-S5-Zusatz-Android-Ortung-Dienstende.md`.** Das
Prüfprotokoll im Konzept beantwortet „ist es belegt?"; **dieses Dokument
beantwortet „was muss *ich* noch tun?"** — es ist für die Person am Gerät
geschrieben, nicht für die Instanz am Code.

> **Warum ein eigenes Dokument und nicht „Paket E" im Prüfdokument S5:** Ein
> `Pruefdokument-S5-…` gibt es noch nicht; es entsteht mit Paket A auf einem
> anderen Zweig. Ein Abschnitt darin hiesse, eine fremde Datei auf diesen
> Zweig zu holen und beim Merge gegen ihren lebenden Stand zu setzen. Beim
> Abschluss von S5 gehören beide zusammengeführt oder nebeneinander gelöscht
> (R62).

| | |
|---|---|
| Stand | 03.09.2026 — **E1 fertig** (Android 0.8.1, mit Bilderlauf), E2 und E3 offen |
| Zweig | `claude/s5-paket-e-android` |
| Erhoben an | Zweigstand von `main` 696449d (Web 12.9.4, Android 0.7.7, Uhr 2.0.0) |

---

## 1. Was **nicht** geprüft werden konnte — und warum

**Das steht an erster Stelle, nicht in einer Fußnote.** Paket E ist zu einem
grösseren Teil gerätegebunden, als das Konzept annahm (Abschnitt 9.3 dort
rechnete mit einem Emulator). Er fehlt.

### 1.1 Kein Emulator — beide Wege gemessen, beide zu

| Weg | Ergebnis | Wo gemessen |
|---|---|---|
| **x86_64-Abbild** | braucht KVM. `/dev/kvm` **fehlt**, `/proc/cpuinfo` nennt weder `vmx` noch `svm` | **In diesem Container, 03.09.2026** |
| **arm64-Abbild** | `FATAL | Avd's CPU Architecture 'arm64' is not supported by the QEMU2 emulator on x86_64 host.` (Emulator 37.1.11) | Übernommen aus S5-Vorbereitung 8.3, dort gemessen |

**Der Widerspruch zu `android/LIESMICH.md` ist echt und aufgelöst:** Dort steht
seit 0.7.2, „ein Emulator läuft", und das stimmte — in **jenem** Container, mit
einem älteren Emulator. Beide Sätze sind wahr; der Unterschied ist die
Wegwerf-Umgebung, nicht die App. Die Fassung in `LIESMICH.md` Abschnitt 7 sagt
das jetzt so.

**Was damit ausfällt:** die vier Emulator-Griffe aus Konzept 9.3 —
`adb emu geo fix`, das Abschalten des Standorts, `cmd jobscheduler run -f`,
`dumpsys notification` — **und das, wofür sie als Ersatz gedacht waren.**

### 1.2 Was nur ein Gerät beantwortet

| Offen | Warum nicht hier | Prüfliste unten |
|---|---|---|
| **Vibration** der Warnung, und ob „Nicht stören" sie unterdrückt | kein Gerät, kein Emulator | E1-4, E1-9 |
| **Die Fristen 120 s und 60 s** — beide sind **hergeleitet, nicht gemessen** | kein GPS | E1-3, E1-6 |
| Ob **`onProviderDisabled`** überhaupt eintrifft | Der Standort lässt sich hier nicht abschalten | E1-4 |
| Der **`AbstractMethodError`** auf Android 8–10 | kein solches Gerät; der Befund ist aus der Plattform-Schnittstelle **abgeleitet**, nicht beobachtet | E1-12 |
| Die **drei Meldungs-IDs** nebeneinander | `dumpsys notification` fällt aus | E1-10 |
| Ob der **Data Layer** die Standmeldung wirklich zustellt | keine Wear-OS-Uhr, keine Telefonseite (`android/LIESMICH.md` 7) | E1-11 |

### 1.3 Die Diagnose des Vorfalls — zur Hälfte beantwortet

**Beantwortet am 03.09.2026 vom Auftraggeber:**

| Frage | Antwort | Folge |
|---|---|---|
| Am Handy oder an der Uhr beendet? | **am Handy** | **H1 (Kette B3) ist ausgeschlossen** — die Uhr war es nicht |
| Fehlermeldung? | **keine** | passt auf jede der übrigen Ketten; 0.7.7 hat keinen Weg, eine zu zeigen |
| „Alles gesendet" oder „Rückstand N Pakete"? | **kein Rückstand** | passt auf **H3** (Kette B5): 400-Antwort, Paket als `fehlerhaft` markiert und aus Warteschlange **und** Anzeige genommen |

**Damit ist B-S5Z-06 der belegte Fehler**, nicht mehr nur ein Nebenfund: Die
App sagt „Alles gesendet", während beim Server ein Segment offen bleibt. Für
E2 heißt das, dass **E-S5Z-12** — die Zeile „N Pakete vom Server abgewiesen" —
nicht Beigabe ist, sondern die Abnahme.

**Der Vorbehalt gehört dazu und wird nicht weggelassen:** Der Blick in die App
erfolgte *später*, nicht im Augenblick des Beendens. Ein Rückstand, den ein
späterer Dienst inzwischen weggeräumt hat, sähe heute genauso aus — das wäre
H2 mit später Nachlieferung. Unterschieden wird das an **einer** Stelle:

> **Offen, und nur am Server zu beantworten:** Ist das Segment von damals
> **heute noch** offen (`final = 0`, `ended_at IS NULL`)? Dann war es H3.
> Ist es inzwischen geschlossen, war es H2.
>
> ```sql
> SELECT client_ref, started_at, ended_at, final FROM rest_segments
>  WHERE user_id = ? ORDER BY id DESC LIMIT 5;
> ```
>
> Ebenfalls offen: `adb logcat -s NAdoku` auf Zeilen „Sendelauf: … fertig"
> nach dem Zeitpunkt des Beendens. **Beides blockiert E2 nicht** — E2 behebt
> H2 und H3 gleichermaßen.

---

## 2. Was maschinell geprüft wurde — mit Mittel **und** Zahl

Alles am Stand von E1 (Android 0.8.1), **nach** der letzten Änderung gefahren,
nicht zwischendurch.

| Prüfmittel | Was es gemessen hat | Vorher (0.7.7) | Nachher (0.8.1) |
|---|---|---|---|
| `./gradlew build` | Übersetzen beider Module, Lint, alle Prüffälle in beiden Varianten | 0 Fehler, **14** Warnungen | 0 Fehler, **14** Warnungen |
| Prüffälle `handy` | JUnit/Robolectric, je Variante | **167** (12 übersprungen) | **196** (12 übersprungen) |
| Prüffälle `uhr` | dieselbe | **53** (0) | **64** (0) |
| `HandyBildTest` | 16 Bildschirme × 3 Breiten, gezeichnet und vermessen | — *(gab es nicht)* | **48 Bilder**, alle paarweise verschieden |
| `UhrBildTest` | Uhr-Ansichten, gezeichnet und vermessen | 4 Bilder | **6 Bilder**, alle paarweise verschieden |
| Fehlschläge | beide Module, beide Varianten | 0 | **0** |
| `werkzeuge/kontraste.py` | Farbpaare gegen WCAG-Zielwert, aus `farben.xml` gerechnet | 16 Paare, 0 darunter | **24 Paare, 0 darunter** |
| `werkzeuge/farbabgleich.py` | App-Token gegen Web-Token | 0 Abweichungen, 0 eigene Werte | **0 / 0** |
| `werkzeuge/stroeme.py` | Ausdünnung gegen die Referenzregel, 5 Ströme | 0 Abweichungen | **0** |
| `werkzeuge/bildmarken.sh pruefen` | 4 Bildmarken gegen ihre Quelle | 0 Abweichungen | **0** |
| `tools/wortliste/` Bereich d | sichtbare Texte **beider** Module, 2 Dateien | 3 Treffer, 0 außerhalb | **3 Treffer, 0 außerhalb** |

**Was diese Zahlen *nicht* messen** — die Regel aus `CLAUDE.md` 6, dass eine
grüne Zahl erst dann ein Beleg ist, wenn sie das Gemessene benennt:

- Die **260 Prüffälle** prüfen die *Entscheidungen*: die Zustandsmaschine mit
  eingespeister Zeit, die Genauigkeitsschwelle, den Rundlauf des
  Nachrichtenformats. Sie prüfen **nicht**, dass ein Rückruf des Systems
  eintrifft, dass ein Handy vibriert oder dass ein Text auf einem Bildschirm
  ankommt.
- Die **24 Kontrastpaare** rechnen Farbwerte aus `farben.xml` nach. Sie
  prüfen **nicht**, dass die Farbe im Code tatsächlich an dieser Stelle steht
  — das Werkzeug führt eine feste Liste, und genau diese Lücke hat zwei Paare
  jahrelang unbemerkt unter dem Zielwert stehen lassen (Backlog 92).
- Die **Wortliste** las 2 Dateien: `handy/…/strings.xml` und
  `uhr/…/strings.xml`. Sie hat die Kotlin-Quellen **nicht** angesehen; ein
  Text, der dort fest verdrahtet stünde, fiele nicht auf.
- Der **`SendeRundlaufTest` ist übersprungen** (12 von 196). Er ist die
  Abnahme von **E2**, nicht von E1; ohne laufende Installation überspringt er
  sich selbst.

---

## 3. Was am Bild geprüft wurde

Im **Browser** nichts — Paket E berührt `server/` nicht, es gibt keine
Webseite dazu.

**Am Bild seit 0.8.1 alles, was die App zeigt.** Die Lücke aus der ersten
Fassung dieses Dokuments („für das Handy gibt es keinen Bilderlauf") ist
geschlossen:

| Lauf | Bilder | Was gemessen wird | Ergebnis |
|---|---|---|---|
| `HandyBildTest` | **48** (16 Bildschirme × 360/411/600 dp) | Bedienhöhe, Knopf an der Bildkante, Knopf unter der Faltkante, Unterscheidbarkeit | 48 dp an 45 von 48; 0 an der Kante; **3 unter der Faltkante**; alle 48 verschieden |
| `UhrBildTest` | **6** | Bedienhöhe, Anteil außerhalb des runden Glases, Unterscheidbarkeit | 48 dp je Bild; **0 %** Knopffläche außerhalb; alle 6 verschieden |

Die 16 Bildschirme decken beide Sperren vor dem Dienst, **alle sechs**
Ortungszustände im Dienst, beide Betriebsarten, Kopplung und Einstellungen.
Die PNG liegen unter `handy/build/bilder/` und `uhr/build/bilder/`.

**Zwei Funde beim ersten Lauf** — beide standen vorher in keiner Zahl:

1. **B-S5Z-17 (behoben):** Auf der 192-dp-Uhr lag die unterste Zeile im
   Phasenmodus unter dem Rand. Betroffen war neben der neuen Ortungswarnung
   die **bestehende** „wartet aufs Handy · keine Aufzeichnung". Beide stehen
   jetzt oben in der Zustandszeile.
2. **B-S5Z-16 (offen, bewusst):** Bei laufendem Einsatz mit Phasenknöpfen sind
   vom Knopf „Dienst beenden" auf 800 dp nur **29 dp** sichtbar. Der
   Bildschirm rollt; eine Kürzung wäre eine Gestaltungsänderung und braucht
   eine Entscheidung.

**Was der Bilderlauf nicht sieht:** Bedienzustände. Kein Tippen, kein
Bildlauf, keine Tastatur, keine Schriftrasterung eines echten Geräts, keine
Systemleisten. Und ausdrücklich **keinen waagerechten Überlauf** im Sinn des
Web-Laufs — die Begründung steht in `android/LIESMICH.md` 2.2.

---

## 4. Prüfliste E1 — je Punkt Bedienweg, Erwartung, Scheitern

Voraussetzung: ein APK aus **demselben** Signaturschlüssel wie das installierte
(sonst verlangt Android eine Neuinstallation und der Puffer geht verloren).
`adb logcat -s NAdoku` mitlaufen lassen. Für die Empfangspunkte ein Ort ohne
GPS: Tiefgarage oder Handy in eine Metallbox.

| Nr. | Bedienweg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **E1-1** | Standort aus, App öffnen | Block **„Standort ausgeschaltet"** mit Knopf „Standort einschalten"; **kein** Knopf „Dienst beginnen" | „Dienst beginnen" ist trotzdem da |
| **E1-2** | „Standort einschalten" antippen, einschalten, zurück | Block verschwindet binnen 1 s, „Dienst beginnen" erscheint | Block bleibt; die App muss neu gestartet werden |
| **E1-3** | Dienst beginnen im Freien | erst „Dienst läuft seit … · GPS sucht …" (gedämpft), dann binnen 120 s **„Aufzeichnung läuft seit … · GPS empfängt"** (Asphalt); **keine** Vibration | Warnung trotz Empfang → **Z-S5Z-01 zu kurz. Die Zeit bis zum ersten Fix notieren** — sie ist die Zahl, die bisher fehlt |
| **E1-4** | Im laufenden Dienst den Standort abschalten (Schnelleinstellung) | **sofort** rote Zeile „… · Standort aus · keine Aufzeichnung", Dauermeldung gleichlautend, **eine** Vibration ohne Ton, Meldung in der Leiste; Antippen öffnet die Standort-Einstellungen | keine Vibration → Kanal prüfen (*Einstellungen → Apps → NAdoku → Benachrichtigungen → Warnungen*). **Absturz → das ist B-S5Z-01**, und dann bitte den Logcat sichern |
| **E1-5** | Standort wieder einschalten | „GPS sucht …", dann „GPS empfängt"; die Warnung verschwindet **von selbst** | Warnung bleibt; Zeile bleibt rot |
| **E1-6** | Handy 3 min in die Tiefgarage | nach 60 s „kein GPS-Signal seit 1 min · keine Aufzeichnung" (rot) + Vibration; der Punktzähler steht; nach der Rückkehr binnen 60 s wieder „GPS empfängt" | Zeile bleibt „GPS empfängt", **obwohl der Zähler steht** — dann misst der Wächter die Stille nicht. **Die Zeit bis zur Warnung notieren** |
| **E1-7** | 10 Minuten im Warnzustand bleiben | **genau eine** zweite Vibration nach 10 min, keine dazwischen | mehr als zwei, oder keine zweite |
| **E1-8** | Aus „kein Signal" heraus den Standort abschalten | **sofort** eine neue Vibration, Text wechselt auf „Standort aus" | keine Vibration — dann warnt der Wechsel zwischen zwei Warnzuständen nicht |
| **E1-9** | E1-4 einmal mit **„Nicht stören"** | Zeile und Leistenmeldung wie sonst; die Vibration **darf** ausbleiben | nichts — dieser Punkt misst, **ob** sie ausbleibt, und das gehört ins Handbuch (10.2 sagt es bereits als Möglichkeit) |
| **E1-10** | Während einer Warnung die Benachrichtigungsleiste ansehen | **zwei** Meldungen: die Dauermeldung (still, nicht wegwischbar) und die Warnung (wegwischbar) | nur eine — dann überschreiben sich die IDs |
| **E1-11** | *(nur mit Wear-OS-Uhr)* E1-4 mit Blick auf die Uhr | unten in Rosa „keine Ortung · keine Aufzeichnung", binnen Sekunden; nach E1-5 verschwindet sie | Uhr bleibt stumm → die Standmeldung kommt nicht an. **Ohne Uhr ist dieser Punkt nicht prüfbar und bleibt offen** |
| **E1-12** | *(nur mit einem Android-8/9/10-Gerät)* E1-4 dort | wie E1-4, **kein Absturz** | `AbstractMethodError` im Logcat — das belegte B-S5Z-01 rückwirkend für 0.7.7. **Ohne solches Gerät bleibt der Befund abgeleitet** |
| **E1-13** | Dienst an der **Uhr** beginnen, während der Standort des Handys aus ist | Der Dienst **beginnt** (er wird nicht abgelehnt), das Handy vibriert sofort, die Uhr zeigt „keine Ortung · keine Aufzeichnung" | Die Uhr zeigt „Dienst läuft" ohne Hinweis, oder das Handy schweigt |
| **E1-15** | Laufenden Einsatz mit Phasenknöpfen am S24 ansehen (B-S5Z-16) | „Dienst beenden" ist ohne Schieben sichtbar — **oder eben nicht**; dann notieren, wie weit gescrollt werden muss | Der Knopf ist erst nach Schieben da. Der Bilderlauf sagt: 29 von 48 dp sichtbar auf 800 dp. **Am Gerät gegenprüfen** — das S24 ist höher als 800 dp |
| **E1-16** | *(mit Wear-OS-Uhr)* Laufenden Dienst mit Phasenknöpfen ansehen, während das Handy nichts aufzeichnet | „keine Ortung · keine Aufzeichnung" steht **oben** in der Zustandszeile, dort wo sonst Phase und Zeit stehen | Die Zeile fehlt — dann greift B-S5Z-17 noch, oder die Standmeldung kommt nicht an |
| **E1-14** | Zwölfstundendienst mit E1 | Wächter und Sendetakt laufen durch; **Akkuverbrauch notieren** (Backlog 82) | Dienst abgeräumt („Apps im Tiefschlaf") |

### Was die Punkte messen sollen, das noch keine Zahl hat

Drei Zahlen dieses Pakets sind **Vorschläge mit Herleitung**, keine Messungen.
Sie stehen und fallen mit E1-3, E1-6 und E1-7:

| Zahl | Vorschlag | Woher zu bestätigen |
|---|---|---|
| Z-S5Z-01 Erstfix | 120 s | E1-3 — Zeit bis zum ersten brauchbaren Fund |
| Z-S5Z-02 Signalverlust | 60 s | E1-6 — Zeit, nach der die Warnung kam, gegen das Gefühl „jetzt fehlt zu viel" |
| Z-S5Z-07 Streuung | 100 m | E1-14 — wie oft sie über einen ganzen Dienst greift (steht bisher „blind gewählt" im Code) |

---

## 5. Grenzen der benutzten Prüfmittel

| Mittel | Was es **nicht** sieht |
|---|---|
| `./gradlew build` | jedes Verhalten auf einem Gerät. Es übersetzt, prüft statisch und lässt JVM-Fälle laufen |
| JUnit/Robolectric | den echten `LocationManager`, den echten `NotificationManager`, den echten Data Layer. Robolectric stellt sie nach; die Nachstellung ist nicht die Sache |
| `kontraste.py` | ob die Farbe im Code an dieser Stelle steht. **Feste Paarliste** — was nicht eingetragen ist, wird nicht gemessen und meldet folglich auch nichts (Backlog 92) |
| `wortliste.py` Bereich d | alles außerhalb der beiden `strings.xml` |
| Compose-Vorschauen | alles. Sie sind Quelltext, kein Bild — der Beleg kommt aus dem Bilderlauf |
| `HandyBildTest` / `UhrBildTest` | Bedienzustände. Und **keinen waagerechten Überlauf**: `fillMaxSize()` lässt die Einschränkung immer gewinnen, ein zu breites Kind wird beschnitten statt gemeldet |
| Lint | Laufzeitverhalten. Es hat `postDelayed` mit Token als `NewApi` gefunden — das war Glück in dem Sinn, dass der Fehler auf einem Android-9-Gerät sonst erst zur Laufzeit aufgefallen wäre |

---

## 6. Offene Punkte, die aus E1 mitgehen

1. **Die Diagnose 1.3** (Abschnitt 1.3 hier) — vor E2, beim Auftraggeber.
2. ~~Ein Bilderlauf für das Handy-Modul.~~ **Erledigt mit 0.8.1** —
   `HandyBildTest`, 48 Bilder. Offen bleibt daraus **B-S5Z-16**: der
   „Dienst beenden"-Knopf unter der Faltkante bei laufendem Einsatz.
3. **E1-11 und E1-12** brauchen Geräte, die es hier nicht gibt (Wear-OS-Uhr;
   Android 8–10).
