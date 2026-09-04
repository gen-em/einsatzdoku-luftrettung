# Prüfdokument S4 — was **du** noch prüfen musst

Zur Phase S4 („Handy- und Uhr-Client, Schneidewerkzeug und GPX-Import"). Das
Prüfprotokoll im Konzept beantwortet *„ist es belegt?"*; dieses Dokument
beantwortet *„was muss ich noch tun?"*.

> **Stand: Blöcke B, C, A (teilweise) und D sind gebaut.**
> Android 0.1.0 bis 0.7.5 · Web 12.5.0 bis 12.8.0.
>
> **Eine Migration ist zwingend** (`2026_09_02_schnitte`, Web 12.5.0): Nach
> dem Deploy muss eine Administratorin **`update.php`** aufrufen. Ohne sie
> gibt es die Tabelle `track_cuts` nicht, und der erste Schnitt scheitert.
>
> **Die zwei wichtigsten Punkte dieses Dokuments:**
> **Prüfliste 1** — das Schneidewerkzeug einmal an einem echten Diensttag
> bedienen. **Prüfliste 6** — der Gerätetest, für den es hier gar keinen
> Ersatz gibt.

---

## 1. Was NICHT geprüft werden konnte — und warum

Das steht an erster Stelle und nicht in einer Fußnote.

> **Nachtrag vom 02.09.2026 (Android 0.7.6):** Ein Emulatorlauf hat drei
> Punkte dieser Liste bewegt. **Geschlossen** ist der `AndroidKeyStore`
> (Punkt 4 der alten Prüfliste, jetzt sechs grüne instrumentierte Fälle).
> **Bestätigt** ist die Behebung des runden Beschnitts auf echter Maske.
> **Verschoben** ist die Grenze zum Data Layer: Sie liegt nicht bei den
> Play-Diensten, sondern bei der Companion-App auf der Telefonseite. Die
> Einzelheiten stehen in 1.1a. Alles Übrige gilt unverändert.

### 1.1 Es gab kein Telefon und keine Uhr

Das ist die größte Lücke der ganzen Phase, und sie betrifft **die Hälfte des
Gebauten**. Kein einziger Teil der Android-Apps ist je auf Hardware gelaufen.

**Nicht belegt ist damit vor allem:**

- **Ob der Wear Data Layer überhaupt zustellt.** Ob die beiden
  `WearableListenerService` gerufen werden, ob die Play-Dienste auf der Uhr
  vorhanden sind, ob Paket- und Signaturgleichheit im Feld greift (E-S4-01).
  Der wahrscheinlichste Fehler beim ersten Versuch ist eine **unterschiedliche
  Signatur** der beiden Module — dann passiert schlicht nichts, ohne
  Fehlermeldung.
- **Wie lange `Tasks.await` bei abgeschalteter Uhr wirklich hängt.** Fünf
  Sekunden sind gewählt, nicht gemessen.
- **Die Aufzeichnung über zwölf Stunden.** Ob das System die App beendet, ob
  der Akku hält, ob die Spur durchläuft.
- **Ob 44 dp mit Handschuhen treffbar sind** (siehe Backlog Nr. 64).
- **Always-on-Display**, Systemgesten, Tastensperre.

**Was stattdessen belegt ist:** 214 Prüffälle über beide Module, 0
Fehlschläge — darunter Funkabriss mit Nachlieferung (0 Bytes auf der Leitung,
zwei gepufferte Ereignisse, nach Rückkehr beide zugestellt in der Reihenfolge
1, 2), Doppelzustellung nach verlorener Quittung (zweimal dieselbe Meldung →
**1 Einsatz, 1 Phase**), Uhr-Neustart mit gefülltem Puffer (**2 Nachrichten
unverändert da**, nächste Nummer **3** und nicht 1) und ein Rundlauf gegen
`ingest.php` gegen eine echte lokale Installation (**3 Phasenzeilen**,
`verworfen={}`). Das ist die Mechanik. Es ist nicht das Gerät.

### 1.1a Was ein Emulatorlauf davon abgedeckt hat (02.09.2026)

Ein Emulator ist **kein** Gerät. Er hat keine echte Funkstrecke, keinen
Akku, kein GPS und keinen Hardware-Sicherheitsanker. Was er hat, ist ein
echtes Android und ein echtes rundes Glas — und damit ließ sich Folgendes
belegen:

| | Ergebnis |
|---|---|
| **Der `AndroidKeyStore`** | **geschlossen.** `GeraetTresorTest`, **6 Fälle, alle grün, 13,7 s**: Der Schlüssel entsteht unter seinem Namen im Keystore, `getEncoded()` ist **null**, der Rundlauf trägt, der Schlüssel überlebt einen neuen Griff, jeder Schreibvorgang bekommt einen frischen Zufallswert, ein verfälschtes Paket wird abgelehnt |
| **Der runde Beschnitt (B-S4-08b)** | **bestätigt.** Auf echter Maske: Knopfhöhe **35,5 → 48,0 dp**, Luft zum Glasrand **0,4 → 14,7 dp**. Dazu „Handy nicht erreichbar" statt des früheren falschen „Handy verbunden" (B-S4-09) |
| **Die Wearable-API** | **erreichbar.** Play-Dienste 22.48.14 vorhanden, `NodeClient.localNode` liefert einen Knoten mit Kennung, `connectedNodes` = 0 (richtig ohne Telefon), `HandyHorcher` löst für alle drei Pfade auf |
| **Die Zustellung über den Data Layer** | **weiter offen** — siehe unten |

**Der `AndroidKeyStore` bleibt trotzdem halb offen**, und das gehört gesagt:
Ein Emulator bildet ihn in Software nach. `getEncoded() == null` gilt; die
Aussage „auch mit Root-Rechten nicht auslesbar" hängt am Trusted Execution
Environment eines echten Geräts.

**Die Grenze zum Data Layer liegt woanders, als dieses Dokument annahm.**
Nicht bei den Play-Diensten — die sind da. Sondern bei der
**Wear-OS-Companion-App auf dem Telefon**: Zwei Emulatoren zu koppeln
verlangt sie, sie kommt aus dem Play Store, und der verlangt eine Anmeldung
mit einem Google-Konto. Ein Seitenladen aus einer APK-Sammelseite scheidet
aus (CLAUDE.md 4).

**In meiner Umgebung ist das ein Nein, kein „noch nicht"** — nachgemessen und
nicht vermutet: Keine der 16 APKs im Android-SDK ist die Companion-App;
`android.clients.google.com` (Play-Bezug) antwortet mit **403**
(`x-deny-reason: host_not_allowed`), `dl.google.com` daneben mit **302** — die
Sperre ist also gezielt und nicht „kein Netz"; und Googles eigener Notausgang
`cmd package query-receivers -a com.google.android.gms.wearable.EMULATOR`
meldet **`No receivers found`**. Eine Verwechslung, die naheliegt: `pm path
com.google.android.wearable.app` *antwortet* auf der Uhr — aber mit
`ClockworkWcs.apk`, der Uhrseite unter demselben Paketnamen, nicht der
Telefon-App.

*Auf deinem Rechner ist der Weg dagegen offen, wenn du ihn gehen willst:* ein
Google-Konto (ein Wegwerfkonto genügt) auf einem
`google_apis_playstore`-Telefonabbild, dort die Wear-OS-App installieren, dann
`adb -s <uhr> forward tcp:5601 tcp:5601` und in der App „Mit Emulator
koppeln". Rechne mit einer Stunde, das meiste davon Bootzeit. **Ob sich das
lohnt, ist eine echte Frage:** Es belegt den Data Layer zwischen zwei
Emulatoren — der Gerätetest auf dem S24 und einer echten Uhr belegt ihn
ohnehin, und zwar besser.

### 1.2 Der Signaturschlüssel ist erzeugt — und lag vier Tage nur im Ablagefach

**Diese Überschrift hieß bis zum 02.09.2026 „ist nicht erzeugt", und das war
falsch.** Erzeugt wurde er am 31.08.2026 in B1 (E-S4-27): RSA 4096, PKCS#12,
Alias `nadoku`, gültig bis 23.08.2056, Zertifikat SHA-256 `078c…ad64` —
derselbe Fingerabdruck, mit dem beide Module probeweise signiert wurden.

**Nicht übergeben war er.** Die Datei lag im Ablagefach der Arbeitssitzung,
und das wird mit dem Container eingezogen. Zwei Dokumente sagten „nicht
erzeugt", eines sagte „erzeugt und übergeben"; keines sagte, wo er liegt. Wäre
der Widerspruch nicht aufgefallen, wäre der Schlüssel verlorengegangen — und
mit ihm die Möglichkeit, je eine zweite Fassung derselben App auszuliefern.

**Übergeben am 02.09.2026**, mit Übergabevermerk und Passwort. **Sichere ihn
an einem Ort, der ein Notebook überlebt:** Android erkennt eine App an
Paketname *und* Signatur. Eine spätere Fassung mit einem anderen Schlüssel ist
für jedes Gerät eine **andere App** — Installation schlägt fehl, und der
einzige Weg wäre Deinstallation samt Kopplung, Geräteschlüssel und
ungesendetem Puffer. Solange noch nichts ausgeliefert ist, kostet ein Tausch
nichts; danach nie mehr.

Ungeprüft bleibt trotzdem: Die Download-Seite ist gegen eine **Attrappe aus
7 MB Füllbytes** gelaufen, nicht gegen ein echtes signiertes APK.

### 1.3 Die Deploy-Ausnahme für `server/apk/` ist abgeleitet, nicht gefahren

Ein Trockenlauf der Action bräuchte FTP-Zugangsdaten. Das Muster (`apk/**`
und `apk/`) ist **zeichengenau** das von `sicherungen/`, das im Betrieb steht.
Das ist das stärkste Argument, das hier zu haben war — es ist kein Beleg.
**Nach dem ersten Deploy nachsehen, ob die Dateien noch da sind** (Prüfliste
5).

### 1.4 Kein Gerät hat nach einem Schnitt nachgeliefert

Der Fall ist **zweimal** in Zahlen belegt (Spurprobe Teil 6, Endpunktprobe),
aber beide bauen die Punktschleife aus `ingest.php` nach, statt den Endpunkt
über HTTP aufzurufen. Ein echter Upload mit gültigem Gerätetoken **nach**
einem Schnitt steht aus. Er ist der eigentliche Zweck des Sperrvermerks.

### 1.5 Die Kreisläufe R24 sind nicht gefahren

`tools/wiederherstellungs-probe/` und `papierkorb_misch.mjs` sind für
geschnittene und importierte Einsätze **nicht** gelaufen. Beide tragen
`origin` = `manual` bzw. `import`, `manual = 1` und eine `cut-`/`imp-`-Kennung
— dieselben drei Merkmale wie ein CSV-importierter Einsatz, der die Kreisläufe
besteht. **Ein Befund steht aber schon fest:** Die Konto-Sicherung trägt
`track_cuts` **nicht** mit (Fund B-S4-10, Backlog Nr. 63).

### 1.6 Kein Messstand R35, kein Dienst über Mitternacht mit echten Daten

Die Antwortzeit des Schneide-Endpunkts auf dem 5 000-Einsätze-Konto ist nicht
gemessen. Der Tagesversatz über Mitternacht ist gebaut und im Code begründet,
aber der Prüfbestand hat keinen Dienst über Mitternacht **mit Ruhesegment**.

### 1.7 Nur Chromium, und keine fremde GPX-Datei

Alle Browserprüfungen liefen in Chromium. Alle GPX-Prüfdateien sind entweder
vom eigenen Schreiber erzeugt oder von Hand geschrieben — eine Datei aus
Garmin Connect, Komoot oder einer Leitstellensoftware ist **nicht** durch, und
das ist der Fall, für den der Import gebaut ist.

### 1.8 Zwei Dinge aus Block A fehlen ganz

QR-Kopplungscode (E-S4-15) und der Nachtrag im JSON-Vertrag hängen an **S5**
und **R42** und sind nicht gebaut. Der Geräte-Reiter zeigt den Kopplungscode
weiterhin nur als Text.

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Was | Mittel | Zahl |
|---|---|---|
| Android, beide Module | `./gradlew build` | **BUILD SUCCESSFUL** |
| Prüffälle `uhr` | `testDebugUnitTest` | **47**, 0 Fehlschläge, 0 übersprungen |
| Prüffälle `handy` | `testDebugUnitTest` mit lokaler Installation | **167**, 0 Fehlschläge, 0 übersprungen |
| Prüffälle gesamt | beide Module | **214** |
| **Instrumentiert** (auf dem Emulator, seit 0.7.6) | `am instrument` | `GeraetTresorTest` **6/6**, `DataLayerErreichbarTest` **3/3** |
| Runder Beschnitt auf echter Maske | Emulatorabzug, ausgemessen | Knopf **48,0 dp**, Luft zum Glasrand **14,7 dp** (vorher 35,5 dp / 0,4 dp) |
| Lint `uhr` | `lintDebug` | **0 Fehler, 0 Warnungen** |
| Lint `handy` | `lintDebug` | **0 Fehler, 14 Warnungen** — alle Fassungshinweise, alle an *einer* Entscheidung (Backlog Nr. 65) |
| APK-Größe | `assembleRelease`, unsigniert | Handy **9 598 911 B**, Uhr **19 491 794 B** |
| Spuren: Rundlauf, Verdichtung, Ausdünnung, **Schnitt** | `php tools/spurprobe/probe.php` | **45 Erwartungen, 1 nicht erfüllt** — die eine steht auf `main` genauso (Fund B-S4-11) |
| … davon Teil 6 (Schnitt und Nachlieferung) | dasselbe | **20 Erwartungen, alle erfüllt** |
| Endpunktlogik des Schnitts, ohne HTTP | eigenes Skript | **17 Erwartungen, alle erfüllt** |
| GPX-Leser | eigenes Skript | **17 Erwartungen, alle erfüllt**; 9 000 Punkte in **0,13 s** |
| GPX-Rundlauf (Import → Abruf → erneut gelesen) | eigenes Skript | **12 Erwartungen, alle erfüllt**, 54 Punkte, **0 Abweichungen** |
| Wortliste | `python3 tools/wortliste/wortliste.py` | **0 / 0 / 0** über **vier** Bereiche, **123 Dateien** |
| … Selbstprobe des Zerlegers | `--probe` | **19/19** |
| Vollständigkeit | `python3 tools/vollstaendigkeit/pruefen.py` | **266** Befunde gegen **260** vor S4 — die sechs sind benannt (Laufzeit-Prozentwerte der Zeitleiste) |
| Schema gegen Migration | `schema.sql` in eine leere Datenbank, `SHOW CREATE TABLE` verglichen | **identisch** bis auf den AUTO_INCREMENT-Zähler |
| Farben, Kontraste, Bildmarken, Ströme (Android) | `android/werkzeuge/*` | **0 / 0 / 0 / 0** Abweichungen (16 Farbpaare, 4 Bildmarken, 5 Ströme) |

**Der Stilvergleich ist nicht gefahren und war nicht fällig:** Die Änderung an
`style.css` fügt einen Block hinzu und verschiebt, führt zusammen oder
entfernt nichts (CLAUDE.md 6).

---

## 3. Was im Browser geprüft wurde

Chromium gegen die lokale Installation, 1280 px und 390 px.

| Was | Zahl |
|---|---|
| Schneidewerkzeug: Bedienung, Grenzfälle, Rückgängig | **28 Erwartungen, alle erfüllt** |
| GPX-Import: beide Ziele, Ablehnungen | **17 Erwartungen, alle erfüllt** |
| APK-Karte und Download | **10 Erwartungen, alle erfüllt** |
| Konsolenfehler auf allen drei Wegen | **0** unerwartete |
| Waagerechter Überlauf bei 390 px | **0 px** |
| Bedienhöhen im Schneide-Bereich bei 390 px | **44 / 44 / 44 / 44 / 44 px** |

Die Kernzahlen des Schneide-Rundlaufs: Segment **61 → 48** Punkte,
Einsatzliste **3 → 4**, Sperrvermerk 07:01–07:03 sichtbar; nach dem
Rückgängig **48 → 61** und wieder **3**, Vermerk weg.

---

## 4. Prüfliste — was du tun musst

Je Punkt: der Bedienweg, das erwartete Ergebnis und **woran ein Scheitern zu
erkennen ist**.

### 1. Das Schneidewerkzeug an einem echten Diensttag  *(wichtigster Punkt)*

**Vorher: `update.php` aufrufen.** Ohne die Migration gibt es `track_cuts`
nicht.

| | Weg | erwartet | Scheitern erkennbar an |
|---|---|---|---|
| **a** | Tagesübersicht öffnen, einen Tag mit Ruhesegmenten wählen | unter den Einsätzen steht die Karte **„Ruhesegmente"** mit Zeit, Dauer und Punktzahl je Segment | die Karte fehlt (dann liefert `api/day.php` die Segmente nicht) · sie steht da, aber alle Punktzahlen sind 0 |
| **b** | Bei einem Segment **„Schneiden"** | der Bereich klappt auf: Zeitleiste, Beginn/Ende mit den Segmentgrenzen vorbelegt, drei Phasenfelder | die Zeiten in den Feldern weichen von der Zeile darüber ab → **sofort melden**, das war der Zeitzonenfehler aus 12.6.0 |
| **c** | Beginn und Ende auf einen Bereich **innerhalb** des Segments setzen | der Balken wird orange, der Text nennt Dauer und die verbleibenden Reste | der Text nennt Reste, die es nicht gibt |
| **d** | **„Einsatz erzeugen"** | Meldung mit **zwei Zahlen** („n Punkte gewandert, m bleiben"), der Einsatz erscheint in der Liste, das Segment hat weniger Punkte | „0 Punkte gewandert" → der Zeitbereich trifft nichts |
| **e** | Den neuen Einsatz öffnen | die **Spur liegt auf der Karte**, Zeiten stimmen, Einsatzort/Alter/Diagnose sind **leer** | keine Spur → die Punkte sind nicht mitgewandert |
| **f** | Zurück zur Tagesübersicht, **„Schnitt zurücknehmen"** | Meldung mit der Zahl, Segment wieder vollständig, Einsatz weg | das Segment hat danach **weniger** Punkte als vor dem Schnitt → **sofort melden** |
| **g** | Denselben Bereich ein **zweites** Mal schneiden | Ablehnung mit Begründung, **nichts** entsteht | ein leerer Einsatz erscheint in der Liste |
| **h** | Einen Einsatz schneiden, dann darin **Diagnose eintragen**, dann „Schnitt zurücknehmen" | Ablehnung: „Am Einsatz hängt inzwischen …" | der Einsatz verschwindet samt Diagnose → **sofort melden**, das ist Datenverlust |

### 2. Der Sperrvermerk gegen ein echtes Gerät

Das ist der Fall, für den der ganze zweite Boden gebaut ist, und **er ist
nirgends über HTTP belegt** (1.4).

**Weg:** Einen Dienst mit der Garmin-Uhr aufzeichnen und dabei das Handy oder
den Empfang so unterbrechen, dass die Uhr puffert. Während die Uhr noch
puffert, aus dem entstandenen Ruhesegment einen Einsatz schneiden. Dann die
Uhr wieder senden lassen.

**Erwartet:** Die Punkte des geschnittenen Zeitraums kommen **nicht** zurück
ins Segment; alles davor und danach schon. Der Einsatz behält seine Punkte.

**Scheitern:** Das Segment hat danach wieder Punkte im geschnittenen
Zeitraum, und die Fahrt liegt zweimal da — im Einsatz *und* im Segment.

### 3. Der GPX-Import mit einer **fremden** Datei

**Weg:** Eine Datei aus Garmin Connect, Komoot oder einer anderen Quelle
exportieren. Tagesübersicht → „···" → **„GPX importieren"**, als
**Ruhesegment** übernehmen.

**Erwartet:** Sie kommt an, die Meldung nennt Punktzahl und Zeitraum, das neue
Segment steht in der Karte und trägt „Schneiden".

**Scheitern:** eine Ablehnung, die du nicht erwartet hast. Dann **die Meldung
notieren** — sie nennt den Grund, und der ist der Fund. Wahrscheinlichste
Fälle: die Datei hat keine `<time>`-Elemente (dann liegt es an der Quelle),
oder ein Namensraum, den der Leser nicht kennt (dann liegt es an uns).

Danach: die importierte Spur über **„Spuren als GPX"** wieder herunterladen
und die beiden Dateien vergleichen. Erwartet: dieselben Punkte.

### 4. Die Android-App auf einem echten Telefon  *(braucht den Signaturschlüssel)*

*Der Punkt „AndroidKeyStore" ist hier gestrichen — er ist seit 0.7.6 auf dem
Emulator belegt (1.1a). Was am Gerät bleibt, ist die Härte des Ankers: dass
der Schlüssel auch mit Root-Rechten nicht auszulesen ist.*

Erst nach 1.2 möglich. Der vollständige Ablauf steht in
`docs/Geraete-Eingabe.md`, Abschnitt 7.3 — dort mit Erwartung und
Scheiterns-Merkmal je Punkt. Die Reihenfolge, die ich empfehle:

1. **Beide Module aus demselben Baulauf** installieren (Handy und Uhr).
   Ungleiche Signaturen sind der wahrscheinlichste Fehler und äußern sich als
   „es passiert nichts".
2. Kopplung am Handy, Dienst beginnen, **eine Phase** auslösen.
3. Auf dem Server nachsehen: Ist der Dienst da, ist die Phase da?
4. Erst dann die Uhr: Dienst **von der Uhr aus** beginnen.
5. Funkloch (Handy aus, drei Ereignisse, Handy an).
6. Zwölf Stunden Dauerlauf.

### 5. Nach dem ersten Deploy: liegt das APK noch da?

**Weg:** Ein APK per FTPS nach `server/apk/` legen, auf dem Geräte-Reiter
prüfen, dass die Karte es zeigt. **Dann einen Deploy auslösen** (irgendeine
Änderung unter `server/` nach `main`). Danach den Geräte-Reiter erneut
aufrufen.

**Erwartet:** Die Karte zeigt die Datei weiterhin.

**Scheitern:** Die Karte ist verschwunden → die Deploy-Ausnahme greift nicht
(1.3), und die Datei ist gelöscht. Dann `.github/workflows/deploy.yml` prüfen
und **vor dem nächsten Deploy** melden.

### 6. Die Kreisläufe R24 auf geschnittenen und importierten Einsätzen

**Weg:** Einen geschnittenen und einen importierten Einsatz anlegen, dann eine
Konto-Sicherung erstellen, in ein leeres Konto einspielen und vergleichen.

**Erwartet:** Beide Einsätze kommen vollständig durch, mit ihrer Spur.

**Bekannt und erwartet:** Der **Sperrvermerk** kommt *nicht* mit (Backlog
Nr. 63). Das ist kein neuer Fund, sondern der bereits notierte — er wird hier
nur sichtbar.

---

## 5. Grenzen der benutzten Prüfmittel

- **Robolectric ist kein Android.** Es simuliert die Plattform in der JVM.
  Was es nicht abbildet: echtes Rendering (die Bilder entstehen im
  NATIVE-Modus, aber ohne Compositor), Systemdienste, Lebenszyklus unter
  Speicherdruck, den Data Layer. Ein grüner Lauf sagt: *Die Logik stimmt.* Er
  sagt nicht: *Es funktioniert auf dem Gerät.*
- **Die Spurprobe misst am Bestand.** Der lokale Testbestand ist **eine**
  Spur, 77-fach dupliziert, erzeugt und nicht geflogen (Fund B-S4-11). Was sie
  über Formate und Rundläufe sagt, gilt; was sie über Ausdünnungsquoten sagt,
  gilt für diese Daten.
- **Die Vollständigkeitsprüfung sieht keine zusammengesetzten Klassennamen.**
  Ein `'meldung-' + ton` im Skript ist für sie unsichtbar — sie hat es in
  diesem Paket gemeldet, und die Stelle ist ausgeschrieben worden. Andere
  dieser Art findet sie nicht.
- **Die Wortliste liest Text, nicht Bedeutung.** Sie zählt Begriffe; ob ein
  Satz die Sache trifft, entscheidet sie nicht. Und sie erreicht `watch/`
  weiterhin nicht (Backlog Nr. 66).
- **Playwright klickt, es bedient nicht.** Ein Skript trifft ein Element mit
  dem Selektor; ob ein Mensch es findet, sagt das nicht. Es hat in diesem
  Paket zweimal *falsch* gemeldet — einmal, weil es das versteckte Radio des
  `.wahlliste`-Bausteins anklicken wollte, einmal, weil es den letzten
  Listeneintrag für den neuen hielt. Beide Male lag der Fehler im Prüfmittel.
- **Ein Emulator misst den runden Beschnitt NICHT so, wie man denkt.** Er hat
  bereits beschnitten: Was außerhalb des Glases lag, steht im Abzug gar nicht,
  und „Anteil außerhalb des Kreises" ist dort **immer 0 %**. Der erste
  Messversuch am 02.09.2026 lieferte deshalb für die *fehlerhafte* Fassung
  0.7.0 dieselben 0,00 % wie für die behobene — er hätte den Fehler als
  behoben ausgewiesen. Messbar ist auf einem Emulatorabzug nur, ob der Knopf
  den Glasrand **berührt**. Der Prüfstand rechnet dagegen gegen den
  einbeschriebenen Kreis und sieht den Beschnitt; die beiden Mittel messen
  Verschiedenes und sind nicht austauschbar.
- **Der erste Lauf eines GMS-Prüffalls sagt wenig.** `NodeClient.localNode`
  lief nach dem Boot in eine 30-Sekunden-Zeitgrenze; GMS meldete dabei einen
  **60 Sekunden** blockierten Verbindungspool auf seiner `phenotype.db`.
  Beim zweiten Lauf, mit warmem GMS: **10 Sekunden für drei Fälle**. Wer aus
  dem ersten Lauf „die API antwortet nicht" schließt, schließt falsch.
- **`connectedAndroidTest` trägt auf einem Emulator ohne KVM nicht.** Gradle
  meldet „Skipping device: Unknown API Level", weil ddmlib beim Lesen von
  `ro.build.version.sdk` nach 5 s aufgibt — das Gerät antwortet in 4,3 s und
  liegt damit auf der Kippe. Der Weg über `adb shell am instrument` hat keine
  solche Grenze.
- **Eine abgelehnte Anfrage ist ein Konsolenfehler.** Jeder Browser
  protokolliert eine 404 oder 409, auch die beabsichtigte. Die Zahlen oben
  nennen deshalb „unerwartete" Fehler und sagen dazu, wie viele erwartete
  danebenstehen.
