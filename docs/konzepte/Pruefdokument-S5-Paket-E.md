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
| Stand | 03.09.2026 — **E1, E2 und E3 fertig** (Android 0.10.1). Was bleibt, ist der **Gerätetest** |
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
| Ob der **Data Layer** die Standmeldung wirklich zustellt | keine Wear-OS-Uhr, keine Telefonseite (`android/LIESMICH.md` 7) | E1-11, **E3-1** |
| **Wann der Nachsende-Job anläuft** (Doze, Samsungs Akkusteuerung) | `cmd jobscheduler run -f` bräuchte einen Emulator | E2-2, E2-3 |
| Ob der Job einen **Neustart** übersteht | nachstellen geht nur mit einem Neustart | E2-4 |
| Ob ein **Dienstende von der Uhr** ankommt | keine Uhr | E2-6 |
| Ob der Sendelauf beim **Wegwischen** der App abbricht | Prozessverwaltung des Herstellers | E2-1 |

### 1.3 Die Diagnose des Vorfalls — beantwortet, und anders als vermutet

**Beantwortet am 03.09.2026 vom Auftraggeber:**

| Frage | Antwort | Folge |
|---|---|---|
| Am Handy oder an der Uhr beendet? | **am Handy** | **H1 (Kette B3) ist ausgeschlossen** — die Uhr war es nicht |
| Fehlermeldung? | **keine** | passt auf jede der übrigen Ketten; 0.7.7 hat keinen Weg, eine zu zeigen |
| „Alles gesendet" oder „Rückstand N Pakete"? | **kein Rückstand** | sah zunächst nach **H3** aus — der Blick auf den Server hat das widerlegt, siehe Nachtrag |

**Nachtrag 03.09.2026 — der Server hat entschieden, und er widerspricht der
ersten Lesart.** Abgefragt wurden alle Ruhesegmente ohne `final`:

```sql
SELECT u.email, s.client_ref, s.started_at, s.ended_at, s.final
  FROM rest_segments s JOIN users u ON u.id = s.user_id
 WHERE s.final = 0 AND s.deleted_at IS NULL
 ORDER BY s.id DESC LIMIT 20;
```

**Das Segment vom 02.09. steht nicht darin.** Es ist also inzwischen
geschlossen — ein späterer Dienst hat es nachgeliefert. Damit ist **H3
ausgeschlossen** und **H2 (Kette B1/B2) der belegte Fehler**: Der
Abschluss-Upload kam im Augenblick des Beendens nicht durch (kein Netz, oder
der Prozess starb mit der weggewischten App), und es gab **keinen zweiten
Versuch** — bis der nächste Dienst lief.

Der Vorbehalt aus der ersten Fassung dieses Abschnitts hat also gegriffen:
„Alles gesendet" in der App war eine Beobachtung von **später**, nach der
Nachlieferung, und nicht von damals. Wer nur die App gefragt hätte, wäre bei
H3 gelandet.

**Was das für E2 heißt:** Die Abnahme sind die Punkte 1 und 2 des Ablaufs 5.1
— den Vordergrunddienst halten, bis der Lauf zurück ist, und einen
Nachsende-Job planen, wenn etwas liegen bleibt. `E-S5Z-12` (die Zeile
„N Pakete vom Server abgewiesen") bleibt richtig und wird gebaut, ist aber
**nicht** der belegte Fehler, sondern Vorsorge gegen den Fall, der hier
nicht eingetreten ist.

**Nebenbefund aus derselben Abfrage, nicht Paket E:** Zwei Konten
(`philipp@gen-em.org` und `philipp@chadid.net`) tragen je ein offenes Segment
mit **derselben** `client_ref` `r-1785863592` und demselben `started_at`
(04.08.2026 17:13:12). Die Eindeutigkeit im Schema ist
`UNIQUE (device_id, client_ref)`, nicht `(user_id, client_ref)` — zwei Geräte
dürfen dieselbe Kennung tragen. Ob das hier ein doppelt gekoppeltes Gerät ist
oder zwei Installationen mit gleicher Zufallszahl, ist von außen nicht zu
sagen. **Gemeldet, nicht bewertet** — es gehört zu `server/`, nicht zu Paket E.

---

## 2. Was maschinell geprüft wurde — mit Mittel **und** Zahl

Alles am Stand von E3 (Android 0.10.1), **nach** der letzten Änderung gefahren,
nicht zwischendurch.

| Prüfmittel | Was es gemessen hat | Vorher (0.7.7) | Nachher (0.10.1) |
|---|---|---|---|
| `./gradlew build` | Übersetzen beider Module, Lint, alle Prüffälle in beiden Varianten | 0 Fehler, **14** Warnungen | 0 Fehler, **14** Warnungen |
| Prüffälle `handy` | JUnit/Robolectric, je Variante | **167** (12 übersprungen) | **224** (12 übersprungen) |
| Prüffälle `uhr` | dieselbe | **53** (0) | **71** (0) |
| `HandyBildTest` | 22 Bildschirme × 3 Breiten, gezeichnet und vermessen | — *(gab es nicht)* | **66 Bilder**, alle paarweise verschieden |
| `UhrBildTest` | Uhr-Ansichten, gezeichnet und vermessen | 4 Bilder | **6 Bilder**, alle paarweise verschieden |
| Fehlschläge | beide Module, beide Varianten | 0 | **0** |
| `werkzeuge/kontraste.py` | Farbpaare gegen WCAG-Zielwert, aus `farben.xml` gerechnet | 16 Paare, 0 darunter | **24 Paare, 0 darunter** |
| `werkzeuge/farbabgleich.py` | App-Token gegen Web-Token | 0 Abweichungen, 0 eigene Werte | **0 / 0** |
| `werkzeuge/stroeme.py` | Ausdünnung gegen die Referenzregel, 5 Ströme | 0 Abweichungen | **0** |
| `werkzeuge/bildmarken.sh pruefen` | 4 Bildmarken gegen ihre Quelle | 0 Abweichungen | **0** |
| `tools/wortliste/` Bereich d | sichtbare Texte **beider** Module, 2 Dateien | 3 Treffer, 0 außerhalb | **3 Treffer, 0 außerhalb** |
| **`SendeRundlaufTest`** gegen echtes `ingest.php` | ob ein Dienstende beim Server **ankommt** | — | **224 von 224, 0 übersprungen**; am Server `rest_segments.final = 1` mit `ended_at`, `days.ended_at` gesetzt |

### Der Rundlauf im Einzelnen — die Abnahme von E2

```bash
sh tools/referenzdatensatz/einspielen/lokal_einrichten.sh   # WURZEL setzen, s. u.
mariadb nadoku -e "DELETE FROM pair_codes; INSERT INTO pair_codes …"
cd android && ANDROID_HOME=/opt/android-sdk ./gradlew :handy:testDebugUnitTest \
  --rerun-tasks -Pnadoku.rundlauf=http://127.0.0.1:8080/
```

**Was er hinterlässt** — vor und nach dem Lauf gezählt, weil ein zweiter Lauf
sonst den Bestand des ersten misst (Backlog 91):

| Konto 1 (admin) | vor dem Lauf | nach dem Lauf |
|---|---|---|
| Diensttage | 0 | **9** |
| Einsätze | 0 | **5** |
| Ruhesegmente | 0 | **14** |
| Spurpunkte | 55 861 | **70 300** (+14 439) |

> **Die letzte Zeile ist eine Warnung an die nächste Person.** Zuerst hatte
> ich `SELECT COUNT(*) FROM track_points` gezählt und 55 861 → **30 610**
> bekommen — was nach Datenverlust aussah. Es war keiner: Seit Web 10.0.0
> liegen ältere Punkte als Blob in `track_blobs`, und die Summe über **beide**
> Ablagen stimmt. Genau davor warnt `CLAUDE.md` 4 zu `spur_lib.php` („Wer eine
> der beiden Tabellen unmittelbar per SQL liest, zeigt früher oder später eine
> halbe Spur"), und ich bin trotzdem hineingelaufen. Wer den Bestand zählt,
> zählt `track_points` **plus** `SUM(n_original)` aus `track_blobs`.

**Zwei Stolperstellen beim Aufbau**, beide nicht in der Anleitung:

1. `lokal_einrichten.sh` leitet `WURZEL` aus dem eigenen Pfad ab. Wer das
   Skript woanders hinkopiert (etwa aus einem anderen Zweig), muss
   `WURZEL=<Repo>` mitgeben — sonst startet es einen PHP-Server auf ein
   Verzeichnis, das es nicht gibt, und bricht wortlos ab.
2. Der PHP-Server läuft im Hintergrund der Shell. Endet sie, ist er weg;
   `setsid` hilft.

**Was diese Zahlen *nicht* messen** — die Regel aus `CLAUDE.md` 6, dass eine
grüne Zahl erst dann ein Beleg ist, wenn sie das Gemessene benennt:

- Die **295 Prüffälle** prüfen die *Entscheidungen*: die Zustandsmaschine mit
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
- Der **`SendeRundlaufTest` ist im normalen Lauf übersprungen** (12 von 221).
  **Mit laufender Installation ist er gefahren** — siehe die eigene Zeile
  unten; er ist die Abnahme von E2.

---

## 3. Was am Bild geprüft wurde

Im **Browser** nichts — Paket E berührt `server/` nicht, es gibt keine
Webseite dazu.

**Am Bild seit 0.8.1 alles, was die App zeigt.** Die Lücke aus der ersten
Fassung dieses Dokuments („für das Handy gibt es keinen Bilderlauf") ist
geschlossen:

| Lauf | Bilder | Was gemessen wird | Ergebnis |
|---|---|---|---|
| `HandyBildTest` | **66** (22 Bildschirme × 360/411/600 dp) | Bedienhöhe, Knopf an der Bildkante, Knöpfe im sichtbaren Bereich gegen den ganzen Inhalt, Unterscheidbarkeit | 48 dp an **66 von 66**; 0 an der Kante; **3 Bilder mit einem Knopf unter dem Rand** (`laufend-einsatz-spaet`, 1 von 2, Inhalt 955 dp); alle 66 verschieden |
| `UhrBildTest` | **6** | Bedienhöhe, Anteil außerhalb des runden Glases, Unterscheidbarkeit | 48 dp je Bild; **0 %** Knopffläche außerhalb; alle 6 verschieden |

Die 16 Bildschirme decken beide Sperren vor dem Dienst, **alle sechs**
Ortungszustände im Dienst, beide Betriebsarten, Kopplung und Einstellungen.
Die PNG liegen unter `handy/build/bilder/` und `uhr/build/bilder/`.

**Zwei Funde beim ersten Lauf** — beide standen vorher in keiner Zahl:

1. **B-S5Z-17 (behoben):** Auf der 192-dp-Uhr lag die unterste Zeile im
   Phasenmodus unter dem Rand. Betroffen war neben der neuen Ortungswarnung
   die **bestehende** „wartet aufs Handy · keine Aufzeichnung". Beide stehen
   jetzt oben in der Zustandszeile.
2. **B-S5Z-16 (behoben mit 0.10.1):** Bei laufendem Einsatz lag „Einsatz
   abschließen" bei 771–819 dp und damit halb unter dem Rand. Die Phasenliste
   zeigt jetzt gesetzte und nächste Phase (E-S5Z-33); der Abschluss endet bei
   515 bis 759 dp. **Rest, bewusst stehen gelassen:** Bei acht gesetzten
   Phasen liegt „Dienst beenden" weiter darunter (879 dp).
3. **B-S5Z-18 (behoben mit 0.10.1):** Das Prüfmittel selbst fand nur
   *angeschnittene* Knöpfe und schwieg über solche, die vollständig unter dem
   Rand liegen. Es misst jetzt zusätzlich den ganzen Inhalt.

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
| **E1-15** | Laufenden Einsatz mit **allen acht** gesetzten Phasen am S24 ansehen (B-S5Z-16) | **„Einsatz abschließen" ist ohne Schieben sichtbar** (gerechnet: endet bei 759 dp). „Dienst beenden" darf darunter liegen | „Einsatz abschließen" ist erst nach Schieben da → die Kürzung reicht auf diesem Gerät nicht. **Zahl notieren**, das S24 hat eine andere nutzbare Höhe als die gerechneten 800 dp |
| **E1-17** | Im Einsatz „Alle Phasen zeigen" antippen | Die vollständige Liste erscheint und bleibt, bis der Einsatz abgeschlossen ist; eine gesetzte Phase erneut tippen setzt sie ein zweites Mal, **beide Zeiten bleiben stehen** | Die Liste klappt von selbst wieder zu; oder die zweite Zeit ersetzt die erste (das wäre ein Verstoß gegen E-R45-12) |
| **E1-16** | *(mit Wear-OS-Uhr)* Laufenden Dienst mit Phasenknöpfen ansehen, während das Handy nichts aufzeichnet | „keine Ortung · keine Aufzeichnung" steht **oben** in der Zustandszeile, dort wo sonst Phase und Zeit stehen | Die Zeile fehlt — dann greift B-S5Z-17 noch, oder die Standmeldung kommt nicht an |
| **E1-14** | Zwölfstundendienst mit E1 | Wächter und Sendetakt laufen durch; **Akkuverbrauch notieren** (Backlog 82) | Dienst abgeräumt („Apps im Tiefschlaf") |

## 4a. Prüfliste E2 — Dienstende und Nachsenden

Dies ist die Liste zum **belegten** Fehler. E2-1 und E2-2 sind die beiden
Punkte, die den Vorfall vom 02.09.2026 abdecken.

| Nr. | Bedienweg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **E2-1** | Dienst beenden **mit Netz**, die App sofort aus der Übersicht wischen | Dauermeldung „Dienst beendet · sende …" für Sekunden, dann **keine** Meldung mehr. App öffnen: „Alles gesendet". **Web: Diensttag mit Dienstende, letztes Segment mit Endzeit** | Im Web steht „–offen" oder der Diensttag hat kein Ende. Oder: eine Meldung „Kein Dienst" bleibt hängen (das wäre B-S5Z-03) |
| **E2-2** | **Flugmodus an**, Dienst beenden, Flugmodus 5 min später aus, App **nicht** öffnen | Hinweis „Dienst beendet · N Pakete warten auf Netz"; nach dem Flugmodus verschwindet er binnen weniger Minuten von selbst; `logcat` zeigt „Nachsende-Job läuft an" und „fertig". Web zeigt das Ende | Der Hinweis bleibt über 15 min bei Netz stehen → der Job läuft nicht. **Dann Akkueinstellung prüfen** und die Zeit notieren |
| **E2-3** | Wie E2-2, aber die Zeit bis zum Anlaufen **messen** | eine Zahl | keine — dieser Punkt hat kein Soll. Er liefert die Zahl, die bisher fehlt: wie lange der Job unter Doze braucht |
| **E2-4** | Wie E2-2, aber das Handy **im Flugmodus neu starten**, entsperren, dann Flugmodus aus | wie E2-2 | nichts gesendet → der persistierte Job fehlt. **Achtung:** Ohne Entsperren läuft er absichtlich nicht — das ist kein Fehler |
| **E2-5** | Wie E2-2, dann App öffnen und **„Jetzt senden"** antippen | Ergebniszeile „Gesendet · HH:MM", danach „Alles gesendet"; der Knopf verschwindet | „Keine Verbindung" trotz Netz; oder der Knopf bleibt stehen |
| **E2-6** | *(mit Wear-OS-Uhr)* Dienst am Handy beginnen, **an der Uhr beenden**, Handy in der Tasche | Dauermeldung verschwindet binnen Sekunden, das GPS-Symbol der Statusleiste erlischt, Web zeigt das Ende binnen einer Minute | Meldung und GPS-Symbol bleiben (das wäre B-S5Z-04); oder das Ende kommt erst nach 15 min |
| **E2-7** | Im Dienst **Flugmodus 20 min** an, dann aus | binnen 60 s ein Sendelauf im `logcat` („Sendelauf (WIEDERVERBINDUNG)"), nicht erst beim Takt | Lauf erst nach bis zu 15 min → der Netzrückruf greift nicht |
| **E2-8** | Netz mehrfach kurz hintereinander an- und ausschalten | **höchstens ein** Lauf je Minute im `logcat` | ein Lauf je Umschaltung → die Bremse greift nicht |
| **E2-9** | Während einer Warnung des Wächters den Dienst beenden | Die Warnung (ID 3) verschwindet sofort; nur der Hinweis (ID 2) kann bleiben | Die Warnung vibriert nach dem Dienstende weiter |
| **E2-10** | Zweimal schnell auf „Beenden" tippen | Es geschieht einmal etwas | zwei Sendeläufe im `logcat`, oder die App hängt |
| **E2-11** | Nach einem Dienst mit abgewiesenem Paket in die App sehen | rote Zeile „1 Paket vom Server abgewiesen" | „Alles gesendet" bei offenem Segment im Web — das wäre B-S5Z-06 unverändert. *(Ein 400 lässt sich nicht bestellen; dieser Punkt fällt an, wenn er anfällt)* |

## 4b. Prüfliste E3 — der aktive Uhr-Spiegel

**Beide Punkte brauchen eine Wear-OS-Uhr.** Ohne sie ist E3 nicht prüfbar,
und das steht hier so statt als „erledigt".

| Nr. | Bedienweg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **E3-1** | Dienst läuft, Uhr am Handgelenk, **Uhr nicht anfassen**. Standort des Handys ausschalten | Binnen Sekunden steht **oben in der Zustandszeile** der Uhr „keine Ortung · keine Aufzeichnung" in Rosa — ohne jeden Knopfdruck. Nach dem Wiedereinschalten „GPS sucht", dann wieder Phase und Zeit | Die Uhr ändert sich erst beim nächsten Knopfdruck → die aktive Meldung kommt nicht an (`logcat`: „Ortungsstand an die Uhr … zugestellt=false"). Gar keine Änderung → das Feld fehlt, oder die Zeile liegt wieder unter dem Rand (B-S5Z-17) |
| **E3-2** | Im Dienst zwischen zwei Warnzuständen wechseln lassen: Standort aus, dann zusätzlich in die Tiefgarage | Die Uhr zeigt durchgehend dasselbe, und im `logcat` steht **kein zweiter** „Ortungsstand an die Uhr" | Eine Meldung je **Zustands**wechsel statt je **Anzeige**wechsel — das kostet Akku und Funk für eine Anzeige, die sich nicht ändert (E-S5Z-31) |

### Was die Punkte messen sollen, das noch keine Zahl hat

Drei Zahlen dieses Pakets sind **Vorschläge mit Herleitung**, keine Messungen.
Sie stehen und fallen mit E1-3, E1-6 und E1-7:

| Zahl | Vorschlag | Woher zu bestätigen |
|---|---|---|
| Z-S5Z-01 Erstfix | 120 s | E1-3 — Zeit bis zum ersten brauchbaren Fund |
| Z-S5Z-02 Signalverlust | 60 s | E1-6 — Zeit, nach der die Warnung kam, gegen das Gefühl „jetzt fehlt zu viel" |
| Z-S5Z-07 Streuung | 100 m | E1-14 — wie oft sie über einen ganzen Dienst greift (steht bisher „blind gewählt" im Code) |
| Z-S5Z-06 Backoff | 30 s | E2-3 — wie lange der Job unter Doze **tatsächlich** braucht |

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
3. **E1-11, E1-12, E2-6, E3-1 und E3-2** brauchen Geräte, die es hier nicht
   gibt (Wear-OS-Uhr; Android 8–10).
4. **Der Rest von B-S5Z-16** — bei acht gesetzten Phasen liegt „Dienst
   beenden" weiter unter dem Rand (879 dp von 800 sichtbaren). Bewusst so
   gelassen; am Gerät gegenzuprüfen (E1-15), weil die Zahl von Statusleiste
   und Navigationsart abhängt.
