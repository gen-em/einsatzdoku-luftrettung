# Konzept S5 — Zusatz: Android-Ortung und Dienstende (Paket E)

**Ergänzung zu `Konzept-S5-Kopplung-umgekehrt.md` · Rahmenplan Schritt 5 ·
Auftrag des Auftraggebers vom 02.09.2026 nach der Geräteprüfung · Ablage
`docs/konzepte/` neben dem S5-Konzept (K1), Lebenszyklus R62.**

> **Statusblock**
>
> | | |
> |---|---|
> | Stand | 03.09.2026 — **freigegeben, Umsetzung läuft** auf Zweig `claude/s5-paket-e-android` |
> | Paket in Arbeit | **E2** (Dienstende und Nachsenden) |
> | Erledigt | Freigabe und alle sechs Fragen (E-S5Z-15 bis -21); Prüfstand aufgebaut, Ausgangszahlen gemessen; **E1 (Android 0.8.0)**; **Bilderlauf beider Module (0.8.1)** |
> | Wo es hakt | **Die Diagnose 1.3 ist zur Hälfte beantwortet** (siehe Abschnitt 8) — H1 ist ausgeschlossen, H3 ist die wahrscheinlichste. Was fehlt, ist ein Blick auf den Server: Ist das Segment heute noch offen? Ursprünglich stand hier: Die Diagnose kann die Umsetzung nicht fahren — sie braucht das S24 und den Produktivserver. Sie liegt beim Auftraggeber und ist Voraussetzung für den *Beleg* in E2, nicht für den *Bau* (E2 behebt alle drei Ketten unabhängig davon). Dazu: kein Emulator im Container (Vorbereitung 8.3), also ist Paket E gerätegebundener als Abschnitt 9.3 annimmt |
> | Fable-Schritt | **keiner** |
> | Erhoben an | `main`, Commit `c2ac707` (02.09.2026): Android 0.7.7, Web 12.9.2 |
> | Erhoben aus | dem Repositorium und der Beobachtung des Auftraggebers am Gerät (02.09.2026: Diensttage in der App beendet, im Web noch als laufend gezeigt). Kein Telefon, keine Uhr, kein Server in der Konzeptsitzung — was sich so nicht ermitteln ließ, steht in Abschnitt 10 |

Dieses Dokument wird während der Umsetzung nach **jedem** Arbeitspaket
fortgeschrieben (Statusblock, Abschnitt 8 Umsetzungsstand, Abschnitt 11
Fehlerfunde); das Prüfdokument S5 bekommt einen eigenen Abschnitt „Paket E".
Nach der Freigabe des S5-Abschlusses geht dieses Dokument denselben Weg wie
das Hauptkonzept (R62): Erledigt-Zeile, Reste, Backlog, löschen.

**Was dieses Dokument nicht festlegt (K2, K3):** keine Versionsnummern, keine
Modellempfehlung je Paket, keine Backlog-Nummern — Kandidaten heißen
„Backlog-Kandidat". **Nummernkreise:** E-S5Z, F-S5Z, Z-S5Z und B-S5Z sind
von E-S5/F-S5/B-S5 getrennt, damit die Fortschreibung des Hauptkonzepts
(E-S5-32 ff. für die F-Entscheidungen) nicht kollidiert.

**Drei Vorgaben des Auftraggebers vom 02.09.2026**, die hier als
Entscheidungen eingehen: Dienstbeginn am Handy ist **gesperrt**, solange der
Standort aus ist (E-S5Z-03) · Warnen **sichtbar und per Vibration, ohne Ton**
(E-S5Z-04) · die Wear-OS-Uhr spiegelt den Ortungszustand als **optionales
Paket E3** (E-S5Z-12).

---

## 0. Auftrag an die Umsetzungsinstanz (Zusatzprompt)

> Konzept S5 wird um **Paket E — Android: Ortung und Dienstende** ergänzt,
> in drei Teilpaketen E1 (Ortungswächter), E2 (Dienstende und Nachsenden),
> E3 (Uhr-Spiegel, optional). Grundlage ist dieses Dokument; es liegt neben
> dem S5-Konzept unter `docs/konzepte/`.
>
> 1. F-S5Z-01 bis F-S5Z-06 entscheiden lassen und als E-S5Z-14 ff. hier
>    eintragen (K6).
> 2. Im S5-Konzept: Statusblock um die Zeile „Paket E (Zusatz): siehe
>    `Konzept-S5-Zusatz-Android-Ortung-Dienstende.md`" ergänzen; in
>    Abschnitt 9 („Umsetzungsstand") je eine Zeile E1, E2, E3.
> 3. **Vor E2** den Vorfall vom 02.09.2026 nach Abschnitt 1.3 am Gerät
>    diagnostizieren und das Ergebnis in Abschnitt 8 eintragen — die
>    Umsetzung soll wissen, welche der drei Ketten sie tatsächlich
>    getroffen hat.
> 4. Reihenfolge E1 → E2 → E3. Paket E berührt nur `android/` und Doku und
>    läuft unabhängig von A–D (Rahmenplan 4); es wird **vor dem S4-Rest**
>    (Schritt 6) gemergt, weil beide `HauptActivity.kt`, `strings.xml` und
>    das Manifest anfassen.
> 5. Je Paket: `android/version.properties` hochstufen (Zählweise im
>    Kopf der Datei), Changelog nach F-S5Z-05, `android/LIESMICH.md`
>    („Was der Baulauf heute meldet", Abschnitt 7), `docs/Technik.md` 5a,
>    Prüfdokument S5 Abschnitt „Paket E", `tools/wortliste/` Bereich d,
>    Statusblock, Push (K7). `./gradlew build`: 0 Lint-Fehler, 0
>    Fehlschläge, Warnungen gezählt.
> 6. Prüfmittel zuletzt. Gerätetest nach 9.2 mit einem APK, das mit **dem**
>    Signaturschlüssel signiert ist (sonst verlangt Android eine
>    Neuinstallation und der Puffer des S24 geht verloren).
> 7. Kein Fable-Schritt. Kein Push auf `main` vor der Bestätigung (K7).

---

## 1. Befund

### 1.1 Der heutige Weg, am Code gelesen (Android 0.7.7)

| Stelle | Was heute geschieht | Fundstelle |
|---|---|---|
| Ortung anfordern | `requestLocationUpdates(GPS_PROVIDER, 1000 ms, 0 m)` — geprüft wird nur die **Freigabe** (`ACCESS_FINE_LOCATION`), nicht, ob der Standort eingeschaltet ist. Ist er aus, wirft der Aufruf nichts; es kommt nur nie etwas | `AufzeichnungsDienst.kt` 154–179 |
| Der Zuhörer | ein SAM-Lambda `LocationListener { ort -> aufnehmen(ort) }` — es setzt nur `onLocationChanged` um; `onProviderEnabled`, `onProviderDisabled` und `onStatusChanged` sind nicht überschrieben | `AufzeichnungsDienst.kt` 65 |
| „Kommt etwas?" | wird nirgends gemessen. Der einzige Hinweis ist der Punktzähler der Ansicht, der im Stand ohnehin nur alle 10 s steigt | `DienstAnsicht.kt` 188–193 |
| „GPS an" | die Zustandszeile sagt „Aufzeichnung läuft seit … · GPS an", sobald die **Freigabe** erteilt ist — unabhängig davon, ob der Standort an ist oder Positionen kommen | `DienstAnsicht.kt` 110–118; `strings.xml` 112–113 |
| Dauermeldung | zwei Texte: „Aufzeichnung läuft seit …" und „Kein Dienst". Der Kommentar in `ortungAnfordern()` („Die Benachrichtigung sagt dann, dass die Ortung fehlt") ist **nicht** umgesetzt | `AufzeichnungsDienst.kt` 158–160, 222–252 |
| Genauigkeit | ein Fund über 100 m Streuung wird von der Ausdünnung verworfen — still; der Punktzähler bleibt stehen, die Zeile sagt weiter „GPS an" | `Ausduenner.kt` 76–77, 137 (E-S4-32) |
| Dienstende aus der Oberfläche | `klammer.beenden()` schließt Einsatz, Ruhesegment und Dienst im Puffer (`final = 1`, `beendet_at`); dann `AufzeichnungsDienst.beenden()`: Ortung ab, Zeitgeber ab, `sendeWenn(DIENSTENDE)` startet einen **Faden** — und **unmittelbar danach** `stopForeground` + `stopSelf` | `HauptActivity.kt` 254–259; `AufzeichnungsDienst.kt` 194–202; `Dienstklammer.kt` 85–102; `Puffer.kt` 183–188, 228–239 |
| Nach dem Sendelauf | der Faden postet `meldungAuffrischen()` auf den Hauptfaden — auch dann, wenn der Dienst inzwischen gestoppt ist | `AufzeichnungsDienst.kt` 141 |
| Dienstende von der Uhr | `HandyHorcher` wirkt das Ereignis über `Uhrannahme` und **startet** den Vordergrunddienst, wenn ein Dienst läuft. Beenden tut er ihn **nie** | `HandyHorcher.kt` 41 |
| Gescheiterter Sendelauf | `spaeterErneut` — und dann nichts. Außerhalb eines laufenden Dienstes gibt es keinen Zeitgeber; der Auslöser `WIEDERVERBINDUNG` ist deklariert und nirgends benutzt; `ACCESS_NETWORK_STATE` ist deklariert und unbenutzt | `Sendetakt.kt` 43–44; `Sender.kt` 162–163; `AndroidManifest.xml` 22 |
| Sendeläufe | ein roher `Thread` je Auslöser, aus dem Dienst (Takt, Dienstende) **und** aus der Oberfläche (Phase, Einsatzabschluss). Der Kommentar „die Läufe überlappen nicht (der nächste kommt frühestens in 15 Minuten)" gilt nur für den Takt | `AufzeichnungsDienst.kt` 127–142; `HauptActivity.kt` 341–343 |
| Was die Ansicht vom Rückstand zeigt | „Rückstand N Pakete" (orange) ohne Handlung; Pakete mit 400 (`fehlerhaft = 1`) zählen nicht und erscheinen **nirgends** — E-S4-36 hatte die Anzeige „D1" zugewiesen, dort ist sie nicht entstanden | `DienstAnsicht.kt` 127–135; `Puffer.kt` 454–459, 508–514 |
| Serverseite | `rest_segment`/`mission` mit `final: true` und `ended_at` schließen das Segment (`final = GREATEST(final, …)`) und schreiben `days.ended_at` nur nach hinten fort. **Ohne diesen Upload bleibt das Segment offen und der Diensttag ohne Dienstende.** Die Spurenseite zeigt dann „Ruhezeit · 07:02–offen Uhr" | `ingest.php` 364–370, 545; `diensttag_lib.php` 461–474; `tag_spuren.php` 179–182; Vertrag 4 (426–428) |

### 1.2 Was daraus folgt — zwei Ketten

**Kette A — Ortung.** Die App unterscheidet heute nicht zwischen „Standort
aus", „kein Empfang", „Signal zu ungenau" und „Ortung läuft". In allen vier
Fällen steht „GPS an", die Dauermeldung sagt „Aufzeichnung läuft", der rote
Aufnahmepunkt leuchtet — und im Puffer landet nichts. Das ist genau die Art
Aussage, gegen die E-S4-10 und B-S4-09 gebaut sind: eine Behauptung über
etwas, das die App nicht geprüft hat. Dazu ein abgeleiteter Absturz auf
älteren Geräten (B-S5Z-01): Auf Android 8 bis 10 (API 26–29) hat
`LocationListener` vier abstrakte Methoden; ein gegen API 36 übersetztes
Lambda setzt nur eine davon um. Ruft das System `onProviderDisabled` oder
`onStatusChanged`, endet das in `AbstractMethodError` — der Dienst stirbt in
dem Augenblick, in dem jemand den Standort ausschaltet oder das Signal
wegbleibt. `minSdk` ist 26. Nicht beobachtet, aus der Plattform-API abgeleitet
(Abschnitt 10).

**Kette B — Dienstende.** Drei Wege, auf denen der Abschluss-Upload
verlorengeht, ohne dass es jemand sieht:

1. **Der Sendelauf beginnt nach dem Stopp des Dienstes.** `beenden()` startet
   den Faden und stoppt sofort den Vordergrunddienst. Solange die Ansicht
   offen ist, lebt der Prozess; wird die App danach weggewischt, ist er ein
   Hintergrundprozess ohne Dienst — Android (und Samsung besonders) darf ihn
   jederzeit abräumen. Ohne Netz hängt der Lauf bis zu 15 s im
   Verbindungslimit (`Netzweg.kt` 44–45), im Zweifel länger, als die Person
   die App offen hält.
2. **Kein Netz oder 5xx beim Dienstende.** Der Lauf endet mit `spaeterErneut`
   — und es gibt keinen nächsten. Erst der **nächste Dienst** sendet wieder:
   sein erster Takt kommt 15 Minuten nach dem Start, ein Phasenwechsel
   früher. Bis dahin steht der Diensttag im Web ohne Ende, das letzte
   Ruhesegment „–offen".
3. **Dienstende von der Uhr.** `HandyHorcher` schließt den Dienst im Puffer,
   der Vordergrunddienst läuft aber weiter: Ortung an (Akku), Dauermeldung
   nach dem nächsten Auffrischen „Kein Dienst", **kein** DIENSTENDE-Lauf.
   Gesendet wird erst beim nächsten 15-Minuten-Takt — sofern der Dienst bis
   dahin nicht vom System beendet wird. Wer innerhalb dieser Viertelstunde
   ins Web sieht, sieht den Dienst laufend.

Dazu ein vierter Weg, der nicht verliert, aber falsch anzeigt: Nach dem
Stopp postet der Sendefaden die Dauermeldung neu (`meldungAuffrischen()`),
diesmal als gewöhnliche, „andauernde" Benachrichtigung „Kein Dienst" ohne
Dienst dahinter (B-S5Z-03). Und ein fünfter, der still bleibt: Antwortet der
Server auf das Abschlusspaket mit 400, wird es als `fehlerhaft` markiert und
aus Warteschlange **und** Anzeige genommen — die App sagt „Alles gesendet",
das Segment bleibt auf dem Server offen (B-S5Z-06).

### 1.3 Der Vorfall vom 02.09.2026 — Hypothesen und Diagnoseweg

**Beobachtung des Auftraggebers:** Diensttage wurden in der App (teilweise)
beendet; im Diensttag selbst stand danach noch, die Aufzeichnung laufe.

**Was im Repositorium dazu steht:** Den Wortlaut „Aufzeichnung laufend" gibt
es in `server/` nicht. Was der Server bei fehlendem Abschluss zeigt, ist ein
Ruhesegment „HH:MM–offen" auf der Spurenseite (`tag_spuren.php` 182) und ein
Diensttag ohne Dienstende (`days.ended_at` bleibt NULL). Steht der Text an
einer anderen Stelle, muss die Umsetzung sie kennen — **F-S5Z-06**.

**Drei Hypothesen, in der Reihenfolge ihrer Wahrscheinlichkeit:**

| | Hypothese | Passt zur Beobachtung, wenn … | Kette |
|---|---|---|---|
| H1 | Dienstende **von der Uhr** ausgelöst | „teilweise in der App beendet" schließt die Uhr ein; das Web wurde binnen 15 min angesehen, oder der Vordergrunddienst wurde vorher vom System beendet | B3 |
| H2 | Dienstende am Handy **ohne Netz** oder mit sofort weggewischter App | im Web fehlt das Ende dauerhaft, die App zeigt beim nächsten Öffnen „Rückstand N Pakete" | B1, B2 |
| H3 | Server antwortete **400** auf das Abschlusspaket | die App zeigt „Alles gesendet", das Segment bleibt trotzdem offen; im Serverprotokoll steht ein 400 mit `error: payload` | B5 |

**Diagnoseweg vor E2** (Ergebnis nach Abschnitt 8):

1. App öffnen: steht unter „Gekoppelt" **„Alles gesendet"** oder **„Rückstand
   N Pakete"**? Rückstand > 0 → H2. „Alles gesendet" bei offenem Segment im
   Web → H1 (bereits nachgesendet) oder H3.
2. Am Handy `adb logcat -s NAdoku`: Zeilen „Sendelauf: … Pakete fertig" nach
   dem Zeitpunkt des Beendens? Keine → der Lauf ist nicht gelaufen (H2) oder
   der Prozess war weg.
3. Auf dem Server (SQL): `SELECT client_ref, started_at, ended_at, final FROM
   rest_segments WHERE user_id = ? ORDER BY id DESC LIMIT 5;` und `SELECT
   day, started_at, ended_at FROM days WHERE user_id = ? ORDER BY id DESC
   LIMIT 3;` — `final = 0` und `ended_at IS NULL` beim letzten Segment
   bestätigen die fehlende Übertragung; ein `ar-`-Präfix sagt, dass es das
   Handy war.
4. Mit dem Auftraggeber klären: Wurde der Dienst am Handy oder an der Uhr
   beendet, und wie lange nach dem Beenden wurde ins Web gesehen?

Die Umsetzung von E2 behebt alle drei Ketten unabhängig vom Ergebnis; die
Diagnose entscheidet nur, was das Prüfdokument als **belegten** Fehler führt
und ob H3 einen Blick in das Serverprotokoll verlangt.

### 1.4 Die Garmin-Uhr als Verhaltensreferenz

Die Android-App ist „wortgleich zur Garmin-Uhr" gebaut (E-S4-05, E-S4-06,
E-S4-08). Für beide Ketten gibt es dort ein Vorbild:

- **GPS-Güte auf der Sync-Seite:** „GPS gut / GPS ausreichend / GPS zu schwach
  / GPS aus (kein Dienst)" aus `Position.getInfo().accuracy` — und zwar
  bewusst mit **derselben Schwelle, ab der `Track.mc` Punkte speichert**
  („sonst wäre die Anzeige irreführend"). `SyncView.mc` 56–72, `Track.mc`
  88–89. E1 übernimmt das Prinzip: Angezeigt wird, ob **brauchbare** Funde
  kommen, nicht ob irgendein Sensor läuft.
- **Dienstende:** `Model.endService()` schließt Segment und Einsatz, hält die
  Ortung an und ruft `Uploader.syncAll()`; die Warteschlange bleibt bis zur
  Bestätigung („sonst gingen noch nicht bestätigte Einsätze verloren; sie
  wird beim nächsten Start weiter gesendet"). `Model.mc` 155–178. Die Uhr
  hat allerdings dieselbe Lücke wie das Handy — nach dem Dienst sendet sie
  erst wieder, wenn die Sync-Seite oder der nächste Dienst läuft. Das Handy
  kann mehr, weil es einen Job-Planer hat; E2 nutzt ihn (E-S5Z-09).

---

## 2. Entscheidungen (E-S5Z)

| Nr. | Entscheidung | Herkunft |
|---|---|---|
| **E-S5Z-01** | **Der Ortungszustand ist ein Wert mit sechs Stufen** (`Ortungsstand`): `FREIGABE_FEHLT`, `STANDORT_AUS`, `SUCHT`, `KEIN_SIGNAL`, `UNGENAU`, `OK` — Definitionen in 4.1. Es gibt **eine** Quelle im Prozess (`NAdokuApp.ortung`, Arbeitsspeicher, geschrieben vom Vordergrunddienst); vor dem Dienst leitet die Oberfläche `FREIGABE_FEHLT`/`STANDORT_AUS` selbst aus Freigabe und `isProviderEnabled(GPS_PROVIDER)` ab. Das widerspricht E-S4-31 nicht: Dort geht es um den Zustand des **Dienstes**, der Neustarts überleben muss. Der Ortungszustand ist ein Augenblickswert; nach einem Neustart wird er neu gemessen, nicht wiederhergestellt | dieses Konzept |
| **E-S5Z-02** | **„Brauchbar" heißt: die Ausdünnung verwirft den Fund nicht wegen Streuung** (≤ 100 m, E-S4-32). Der Wächter zählt brauchbare Funde, nicht Sensorereignisse — dieselbe Regel, nach der `Track.mc` speichert und `SyncView.mc` anzeigt (1.4). Die Regel wird zu **einer** öffentlichen Funktion `Ausduenner.brauchbar(punkt)`, die `nimm()` und der Wächter beide benutzen | `Ausduenner.kt` 76–77; `SyncView.mc` 56–58 |
| **E-S5Z-03** | **Dienstbeginn am Handy ist gesperrt, solange der Standort ausgeschaltet ist.** Reihenfolge der Prüfung: erst Freigabe (bestehender Block „Ortung nicht freigegeben" mit Knopf „Ortung freigeben"), dann Standort: Block „Standort ausgeschaltet" mit `KnopfPrimaer` **„Standort einschalten"** (`Settings.ACTION_LOCATION_SOURCE_SETTINGS`). Solange einer der beiden Blöcke steht, gibt es keinen Knopf „Dienst beginnen". Nach der Rückkehr liest der Sekundentakt der Ansicht den Zustand neu, der Block verschwindet von selbst. Bausteine: `Meldungsblock`, `KnopfPrimaer` — beide vorhanden (`KopplungAnsicht.kt` 249–268; `DienstAnsicht.kt` 81–88) | Auftraggeber 02.09.2026 |
| **E-S5Z-04** | **Warnen sichtbar und spürbar, ohne Ton.** Sichtbar: Zustandszeile der Dienstansicht (Rot bzw. Orange, Wortlaute 4.3) und der Text der Dauermeldung. Spürbar: eine **eigene Benachrichtigung** (ID 3) auf einem **zweiten Kanal `warnungen`** (`IMPORTANCE_DEFAULT`, `enableVibration(true)`, `setSound(null, null)`) — beim Übergang in `STANDORT_AUS`, `KEIN_SIGNAL` oder `UNGENAU`, beim Wechsel zwischen diesen dreien, und als Erinnerung alle Z-S5Z-04 (10 min), solange der Zustand anhält; sie verschwindet von selbst (`cancel`) bei `SUCHT` oder `OK`. Ein zweiter Kanal, weil Android die Einstellungen eines Kanals nach dem Anlegen der NutzerIn überlässt und der Kanal „Aufzeichnung" bewusst `LOW` und stumm ist (`AufzeichnungsDienst.kt` 207–220). Antippen führt bei `STANDORT_AUS` in die Standort-Einstellungen, sonst in die App. Grenze: „Nicht stören" kann die Vibration unterdrücken (Abschnitt 10) | Auftraggeber 02.09.2026 |
| **E-S5Z-05** | **Der Zuhörer ist eine ausgeschriebene Klasse mit allen vier Methoden** (`object : LocationListener`), kein SAM-Lambda (B-S5Z-01). `onProviderEnabled`/`onProviderDisabled` setzen den Zustand sofort; `onStatusChanged` bleibt leer (seit API 29 wirkungslos), muss aber vorhanden sein. Ein JVM-Prüffall stellt per Reflexion sicher, dass die Klasse des Zuhörers alle vier Methoden **selbst deklariert** — der billigste Schutz gegen ein späteres „Vereinfachen" zurück zum Lambda | Plattform-API; B-S5Z-01 |
| **E-S5Z-06** | **Der Wächter läuft im Vordergrunddienst** im Takt Z-S5Z-03 (10 s) am vorhandenen `Handler`, misst mit `SystemClock.elapsedRealtime()` (monoton; die GPS-Zeit `ort.time` und die Wanduhr können springen) und kennt drei Zeitpunkte: Dienststart bzw. Anbieter wieder an, letzter roher Fund, letzter brauchbarer Fund. Übergänge in 4.2, Fristen Z-S5Z-01/-02. Das Ergebnis schreibt er nach `NAdokuApp.ortung`, frischt die Dauermeldung auf und postet bzw. löscht die Warnung (E-S5Z-04) | dieses Konzept |
| **E-S5Z-07** | **Das Dienstende hält den Vordergrunddienst, bis der Sendelauf zu Ende ist.** Ablauf 5.1: Ortung ab → Wächter ab → Dauermeldung „Dienst beendet · sende …" → DIENSTENDE-Lauf auf dem Sendeausführer (E-S5Z-11) → Rückstand 0: Meldung weg, `stopSelf`; Rückstand > 0: Nachsende-Job planen, Hinweismeldung (ID 2, still, wegwischbar) „Dienst beendet · N Pakete warten auf Netz", `stopSelf`. Die Dauermeldung wird nach dem Stopp **nicht** mehr gepostet (B-S5Z-03). Kein Typwechsel des Dienstes für die paar Sekunden (`dataSync` brauchte eine weitere Berechtigung); die Zeitlimits des Netzwegs (15 s + 30 s je Anfrage) begrenzen den Lauf | B1, B-S5Z-03 |
| **E-S5Z-08** | **Ein Dienstende von der Uhr beendet den Vordergrunddienst.** `HandyHorcher` entscheidet nach `uebernimm()` über eine reine Funktion `Dienstfolge(liefVorher, laeuftNachher, dienstSteht)` → `STARTEN`, `BEENDEN` oder `NICHTS`; `BEENDEN` ruft `AufzeichnungsDienst.beenden()`, das denselben Weg wie E-S5Z-07 geht. `dienstSteht` ist eine Laufmarke des Dienstes (gesetzt in `onCreate`, gelöscht in `onDestroy`); ohne sie darf `startService()` aus dem Hintergrund nicht gerufen werden (`IllegalStateException` ab Android 8) — der Aufruf fängt sie zusätzlich ab | B3, B-S5Z-04 |
| **E-S5Z-09** | **Nachsenden über `JobScheduler`** — Bordmittel, keine neue Abhängigkeit (WorkManager wäre eine und gehörte nach `docs/Lizenzen.md`; er setzt intern ohnehin auf denselben Planer). Ein `JobService` `NachsendeDienst`, feste Job-ID, Bedingung `NETWORK_TYPE_ANY`, `setPersisted(true)` (F-S5Z-04: braucht `RECEIVE_BOOT_COMPLETED`, eine normale Berechtigung ohne Dialog), Backoff exponentiell ab Z-S5Z-06 (30 s). **Geplant wird** nach jedem Sendelauf, der mit `spaeterErneut` endet und Rückstand > 0 hinterlässt, und beim App-Start, wenn Rückstand > 0 ist und kein Dienst läuft (der Prozess kann gestorben sein, bevor geplant wurde). **Nicht geplant** bei 401 (`pausiert` — Wiederholen hilft nicht, angezeigt wird es) und bei Rückstand 0. Der Job führt `sendeAlles()` auf dem Sendeausführer aus und endet mit `jobFinished(reschedule = Rückstand > 0 && spaeterErneut)`; bei Erfolg löscht er die Hinweismeldung (ID 2). Läuft ein Dienst, plant der Job nichts nach — dort sendet der Takt | B2, E-S4-07 |
| **E-S5Z-10** | **Wiederverbindung im Dienst.** Der Vordergrunddienst registriert `ConnectivityManager.registerDefaultNetworkCallback`; `onAvailable` löst `sendeWenn(WIEDERVERBINDUNG)` aus. `Sendetakt.faellig()` bekommt eine Regel: `WIEDERVERBINDUNG` ist fällig nur, wenn seit dem letzten Versuch mindestens Z-S5Z-05 (60 s) vergangen sind — ein flatterndes Mobilfunknetz meldet `onAvailable` im Sekundentakt. Damit ist E-S4-07 vollständig gebaut und `ACCESS_NETWORK_STATE` hat seinen Nutzer (B-S5Z-05) | E-S4-07; B-S5Z-05 |
| **E-S5Z-11** | **Ein Sendeausführer, nie zwei Läufe zugleich.** `NAdokuApp` hält einen `Executors.newSingleThreadExecutor()`; Dienst (Takt, Dienstende, Wiederverbindung), Oberfläche (Phase, Einsatzabschluss, „Jetzt senden") und Nachsende-Job reichen ihre Läufe dort ein. Zwei parallele Läufe auf demselben Puffer sind heute möglich (B-S5Z-11) und harmlos nur, weil der Server idempotent ist — `chunkPunkte` im `Sender` ist ein ungeschütztes `var`. Der Ausführer macht die Zusicherung „Läufe überlappen nicht" wahr, statt sie zu kommentieren | B-S5Z-11 |
| **E-S5Z-12** | **Die Ansicht zeigt den Sendezustand vollständig** (5.5): „Alles gesendet" / „Rückstand N Pakete" (bestehend), neu „N Pakete vom Server abgewiesen" (Rot, aus `fehlerhaft = 1`; B-S5Z-06) und ein Knopf **„Jetzt senden"** (`KnopfNeutral`, nur bei Rückstand > 0 und nicht während ein Lauf läuft) mit einer Ergebniszeile aus dem letzten Lauf. Alles mit vorhandenen Bausteinen (`Zustandszeile`, `KnopfNeutral`); kein neuer Baustein, kein Mockup nötig | B-S5Z-06; `CLAUDE.md` 5 |
| **E-S5Z-13** | **Uhr-Spiegel als optionales Paket E3.** Die Standmeldung Handy → Uhr bekommt ein Feld `ortung` (Kurzcode der sechs Stufen); die Uhr zeigt in der laufenden Ansicht in Rot „keine Ortung" (`STANDORT_AUS`, `KEIN_SIGNAL`, `UNGENAU`, `FREIGABE_FEHLT`), in Sand „GPS sucht" (`SUCHT`), nichts bei `OK` und nichts, wenn das Feld fehlt (alte Handy-Fassung). Das Handy schickt die Standmeldung zusätzlich bei jedem Ortungswechsel, nicht nur als Antwort auf ein Ereignis. Begründung wie E-S4-10: Eine Uhr, die „Dienst läuft" zeigt, während nichts aufgezeichnet wird, verschweigt genau die Lücke, die hinterher niemand erklären kann. **Kein Zugangsdatum** reist mit (CLAUDE.md 4); der Weg bleibt `Nachrichtenweg`. Details in 6 | Auftraggeber 02.09.2026 (Ja, optional) |
| **E-S5Z-14** | **Reihenfolge und Einordnung:** E1 → E2 → E3. E läuft parallel zu A–D (nur `android/` und Doku; Rahmenplan 4 „ein Paket, das nur `android/` anfasst, kann immer laufen") und wird **vor dem S4-Rest** gemergt (gemeinsame Dateien). Eine Android-Fassung je Paket (Nebennummer: neue Funktion; die Nummer vergibt die Umsetzung, K3). Backlog 82 (Akku-Warnung) bleibt beim S4-Rest, aber sein Kandidat (c) nennt den Text „… · GPS an", den E1 ersetzt — der S4-Rest baut auf der neuen Zeile auf (B-S5Z-10) | Rahmenplan 3 und 4 |
| **E-S5Z-15** | **F-S5Z-01 ist mit (c) beantwortet** (Auftraggeber, 03.09.2026): Ein Dienststart von der Uhr bei ausgeschaltetem Standort wird **durchgelassen**; das Handy warnt sofort (Warnung ID 3, Vibration, rote Zustandszeile) **und** schickt der Uhr den Ortungszustand in der Standmeldung, mit der es das Ereignis ohnehin quittiert. Damit ist die Warnung nicht nur in der Hosentasche. Gegenargument, das dabei bewusst in Kauf genommen wird: Es entsteht ein Dienst, der nachweislich nichts aufzeichnet — die Klammer bleibt offen, Phasen werden dokumentiert, Koordinaten sind `null` (Vertrag 3 lässt das zu). Ein abgelehnter Start vom Handgelenk wäre der schlechtere Fall: Die Person steht mit Handschuhen am Fahrzeug, hat gedrückt, und nichts erklärt ihr, warum nichts läuft | Auftraggeber 03.09.2026 |
| **E-S5Z-16** | **Der Paketschnitt ändert sich, weil E-S5Z-15 es verlangt.** Für die Quittung mit Ortungszustand braucht es das Feld `ortung` in der `Standmeldung`, seine Füllung in `Uhrannahme.stand()` und die Anzeige auf der Uhr — genau den Dateisatz, den Abschnitt 8 dem optionalen Paket E3 zuwies. Er wandert nach **E1**. E3 behält, was übrig bleibt: den **aktiven** Spiegel, also die Standmeldung bei **jedem** Wächterwechsel statt nur als Antwort auf ein Ereignis. E3 ist damit klein und nicht mehr „optional bei Zeitnot" — es ist der Unterschied zwischen „die Uhr erfährt es beim nächsten Knopfdruck" und „die Uhr erfährt es sofort". **Folge für Abschnitt 6 und für die Prüffallzahlen:** Die sechs E3-Fälle verteilen sich mit; gezählt wird am Ende die Summe, nicht die Aufteilung | Folge aus E-S5Z-15 |
| **E-S5Z-17** | **F-S5Z-02 bestätigt:** Z-S5Z-01 = 120 s (Erstfix), Z-S5Z-02 = 60 s (Signalverlust). Beide sind hergeleitete Vorschläge, keine Messungen — die Ist-Zeiten liefert der Gerätetest (9.2 Punkte 3 und 6) und gehört ins Prüfdokument | Auftraggeber 03.09.2026 |
| **E-S5Z-18** | **F-S5Z-03 bestätigt:** Erinnerung alle Z-S5Z-04 = 600 s, solange ein Warnzustand anhält — nicht nur beim Übergang. Das Handy steckt in der Tasche; eine einzige Vibration in einer Anfahrt ist überhörbar | Auftraggeber 03.09.2026 |
| **E-S5Z-19** | **F-S5Z-04 bestätigt:** `RECEIVE_BOOT_COMPLETED` wird deklariert, damit der Nachsende-Job `setPersisted(true)` einen Neustart übersteht. Normale Berechtigung ohne Dialog, im Manifest mit Begründung kommentiert wie die übrigen | Auftraggeber 03.09.2026 |
| **E-S5Z-20** | **F-S5Z-05 bestätigt:** Changelog-Präfix `Android` ab sofort, ein Eintrag je E-Paket, erklärende Prosa mit Begründung. Die erste verteilte Fassung ist faktisch da — der Auftraggeber betreibt ein APK auf dem S24 und prüft damit | Auftraggeber 03.09.2026 |
| **E-S5Z-21** | **F-S5Z-06 ist gegenstandslos.** Der Wortlaut „Aufzeichnung läuft" steht in der App (`strings.xml` 103/112/113) **und** das Web zeigt für ein Ruhesegment ohne `final` „· läuft noch" (`schneiden.js` 346, Vorbereitung 8.1 Fassung 5). Beide Oberflächen entstehen aus demselben Zustand — ein Segment, dessen Abschluss den Server nie erreicht hat — und beide führen auf **H1, Kette B3**. Die Rückfrage „App oder Web?" entfällt; die Diagnose 1.3 am Gerät bleibt zu fahren, weil sie entscheidet, was das Prüfdokument als **belegten** Fehler führt | Vorbereitung 8.1 |
| **E-S5Z-22** | **`UNGENAU` wird rot, nicht orange** — und das ist eine berichtigte Zusage, keine Geschmacksfrage. Abschnitt 4.3 sah Orange vor; nachgerechnet nach WCAG trägt **kein** vorhandenes Orange Text auf Schnee: `marke_orange` (#FF8F1F) kommt auf **2,23 : 1**, `marke_orange_tief` (#C25A00) auf **4,32 : 1** — beide unter AA (4,5). Damit wäre das E1-Abnahmekriterium „`kontraste.py`: 0 Paare unter Zielwert" mit orangem Text unerreichbar gewesen (B6.1 der Gegenlesung). Gewählt ist `marke_rot_tief` (**7,58 : 1**) wie bei `KEIN_SIGNAL`: Alle vier Zustände, in denen nichts aufgezeichnet wird, sind rot und unterscheiden sich am **Wortlaut**. Der Verlust der farblichen Abstufung „warnt" gegen „fehlt ganz" ist in Kauf genommen — aufgezeichnet wird in beiden Fällen nichts, und das ist die Aussage, auf die es ankommt. Kein neues Token, keine Mockup-Freigabe nötig | Auftraggeber 03.09.2026; B6.1 |
| **E-S5Z-23** | **Der Wächter bekommt ein eigenes Handler-Token** (B1.1 der Gegenlesung, am Code bestätigt). `taktStarten()` begann mit `taktgeber.removeCallbacksAndMessages(null)` — **ohne Token, also die ganze Warteschlange des einzigen Handlers**; `onStartCommand` ruft es bei jedem Start, und `HandyHorcher` startet den Dienst bei **jeder** Uhrnachricht. Ein 10-s-Wächter „am vorhandenen Handler" wäre dort still gestorben. Beide Takte laufen deshalb mit eigenem Token (`postDelayed(r, TOKEN, ms)` / `removeCallbacksAndMessages(TOKEN)`) | B1.1; `AufzeichnungsDienst.kt` 63, 113–121 |
| **E-S5Z-24** | **Die Standmeldung an die Uhr geht nie vom Hauptfaden** (B4.6). `WearNachrichtenweg.sende()` blockiert bis 5 s je `Tasks.await`, und ihr eigener Klassenkommentar sagt, sie gehöre nicht auf den Hauptthread. Der Wächtertakt läuft auf dem Main-Looper — die Meldung wird deshalb auf dem Sendeausführer eingereiht (E-S5Z-11, kommt mit E2; bis dahin auf einem eigenen Faden wie der Sendelauf heute) | B4.6; `WearNachrichtenweg.kt` 25–27, 41–50 |
| **E-S5Z-25** | ~~**Der Uhr-Spiegel steht am Rand, nicht über den Knöpfen** (B4.3).~~ **Aufgehoben durch E-S5Z-28** — die Messung hat B4.3 hier widerlegt. Der Gedanke war richtig (E-S4-51: Statusanzeigen an den Rand) und die Folge falsch: Auf der 192-dp-Uhr liegt der Rand im Phasenmodus **unter der Faltkante**, und die Warnung erschien dort nie. Begründung und Messung in E-S5Z-28 | B4.3, widerlegt durch `UhrBildTest` |
| **E-S5Z-26** | **Beim Nachsenden wird die Reihenfolge gesichert** (B5.3). `ingest.php` schrieb beim Upsert `ended_at = VALUES(ended_at)` **bedingungslos**, `final` dagegen mit `GREATEST` — ein nicht-finales Paket, das **nach** dem finalen ankam, setzte `ended_at` auf NULL zurück, während `final = 1` stehen blieb. **Nachtrag 03.09.2026:** S5 Paket A hat den Fund nachgestellt statt geglaubt und dabei festgestellt, dass er **breiter** ist als gemeldet — es traf auch `distance_m` und `ascent_m`, also einen abgeschlossenen Einsatz ohne Ende, ohne Strecke und ohne Anstieg, quittiert mit „ok". Behoben mit `COALESCE(VALUES(x), x)` an allen vier Stellen und mit **Web 13.0.1 gepusht**; die Ingestprobe prüft es seither als Teil 7 (30 Erwartungen statt 24, 0 nicht erfüllt). Der serverseitige Backlog-Kandidat ist damit **erledigt, bevor er eine Nummer bekam**. **Für Paket E ändert das nichts an der Zusage:** Der Nachsende-Job schickt je Paket in Anlagereihenfolge und nie ein älteres nach einem finalen. Der Server verzeiht die falsche Reihenfolge jetzt — sie herzustellen bleibt Aufgabe des Absenders, denn eine zweite Wahrheit über die Reihenfolge wäre genau die Art doppelter Boden, die der Vertrag ohnehin führt (Idempotenz über `client_ref`), und keine Entschuldigung dafür, sie wegzulassen | B5.3; S5 Paket A, Web 13.0.1 |
| **E-S5Z-27** | **Der Wortlaut bei `OK` heisst „· GPS empfängt", nicht „· GPS ok".** Abschnitt 4.3 sah „ok" vor; Lint meldet dazu `Typos: "ok" is usually capitalized as "OK"` und die Warnungszahl stieg von 14 auf 15. Stummschalten verbietet `CLAUDE.md` 6, und „OK" mitten im Satz ist ein Schreien. „Empfängt" ist ohnehin die bessere Aussage: Es nennt das **Gemessene** — es kommen brauchbare Funde —, statt eine Güte zu behaupten, die diese App gar nicht abstuft. Die Garmin-Uhr hat drei Stufen („gut / ausreichend / zu schwach"), die App hat eine; „gut" zu schreiben hiesse, eine Abstufung zu versprechen, die es nicht gibt | Lint `Typos`; `SyncView.mc` 56–72 |
| **E-S5Z-28** | **Die Warnung „keine Aufzeichnung" steht auf der Uhr in der Zustandszeile oben**, nicht in der Verbindungszeile unten — und sie **verdrängt** dort Phase und Zeit, statt eine Reihe hinzuzufügen. Gemessen mit `UhrBildTest` (B-S5Z-17): Auf der 192-dp-Uhr ist die laufende Ansicht mit Phasenknöpfen 221 dp hoch; die letzte Reihe liegt unter dem Rand. Drei Bilder mit drei verschiedenen Ortungszuständen kamen **byteweise gleich** heraus, weil keines die Zeile zeigte. Eine Warnung, die genau auf der engsten Uhr ausfällt, ist keine. **Die Reihe wächst dabei nicht** — es ist dieselbe eine Zeile, sie sagt nur etwas Anderes, solange es etwas Wichtigeres zu sagen gibt; der Glasüberlauf aus B-S4-08b kehrt also nicht zurück. Der Preis ist, dass „Phase 3 seit 09:12" währenddessen nicht dasteht, und das ist die richtige Reihenfolge: „Es entsteht gerade keine Spur" schlägt die Phasenzeit, und die Phasenliste ist einen Druck entfernt. **Dieselbe Verlegung gilt für die bestehende Zeile `dienst_schwebt`** — sie stand in derselben Reihe und war auf der kleinen Uhr ebenso unsichtbar | `UhrBildTest`; B-S5Z-17 |

---

## 3. Offene Fragen (F-S5Z) — mit Empfehlung

**Alle sechs sind am 03.09.2026 entschieden** und stehen als E-S5Z-15 bis
E-S5Z-21 in Abschnitt 2. Die Tabelle bleibt stehen, weil sie die Empfehlung
und das Gegenargument festhält, gegen die entschieden wurde — die Entscheidung
selbst steht oben, nicht hier.

| Nr. | Entschieden | Als |
|---|---|---|
| F-S5Z-01 | **(c)** durchlassen, warnen **und** der Uhr quittieren | E-S5Z-15 |
| F-S5Z-02 | Fristen 120 s / 60 s bestätigt | E-S5Z-17 |
| F-S5Z-03 | Erinnerung alle 10 Minuten | E-S5Z-18 |
| F-S5Z-04 | `RECEIVE_BOOT_COMPLETED` ja | E-S5Z-19 |
| F-S5Z-05 | Changelog-Präfix `Android` ab sofort | E-S5Z-20 |
| F-S5Z-06 | gegenstandslos — der Wortlaut steht in **beiden** Oberflächen | E-S5Z-21 |

Die ursprüngliche Vorlage:

| Nr. | Frage | Empfehlung | Paket |
|---|---|---|---|
| **F-S5Z-01** | **Dienststart von der Uhr bei ausgeschaltetem Standort.** Am Handy ist der Start gesperrt (E-S5Z-03); die Uhr kann aber niemanden fragen. (a) Der Dienst beginnt trotzdem, das Handy warnt sofort (Vibration, E-S5Z-04), die Uhr zeigt „keine Ortung" (E3). (b) Das Handy lehnt ab: Quittung ja, Stand „kein Dienst" — die Uhr springt ohne Erklärung auf die Startseite zurück | **(a).** Ein abgelehnter Start vom Handgelenk ist der schlechtere Fall: Die Person steht mit Handschuhen am Fahrzeug, hat gedrückt, und nichts erklärt ihr, warum nichts läuft. (a) hält die Klammer offen (Phasen werden dokumentiert, Koordinaten dann `null` — Vertrag 3 lässt das zu) und sagt laut, was fehlt. Ohne E3 ist die Vibration des Handys das einzige Signal — ein Grund, E3 zu bauen | E1, E3 |
| **F-S5Z-02** | **Fristen des Wächters:** Erstfix Z-S5Z-01 (120 s) und Signalverlust Z-S5Z-02 (60 s) — bestätigen oder ändern? | **Übernehmen und am Gerät nachmessen.** Beide sind Vorschläge mit Herleitung (Abschnitt 7); der Gerätetest (9.2, Punkte 4 und 5) liefert die Ist-Zeiten. Zu kurz warnt im Kaltstart falsch, zu lang lässt eine Minute Spur fehlen, bevor jemand etwas merkt | E1 |
| **F-S5Z-03** | **Erinnerung alle 10 Minuten**, solange ein Warnzustand anhält — oder nur beim Übergang? | **Erinnern.** Das Handy steckt in der Tasche; eine einzige Vibration in einer Anfahrt ist überhörbar. Zehn Minuten sind lang genug, um nicht zu nerven (Backlog 82: „nicht als Dauerwarnung"), und kurz genug, dass ein Dienst nicht stundenlang leer läuft. Der Wert steht als Konstante an einer Stelle | E1 |
| **F-S5Z-04** | **`RECEIVE_BOOT_COMPLETED`** für den persistierten Nachsende-Job — ja, oder Job nur bis zum nächsten Neustart? | **Ja.** Normale Berechtigung ohne Dialog, einziger Zweck ist die Planung des Jobs über den Neustart hinweg; ohne sie verlöre ein Neustart nach einem Dienst ohne Netz die Nachlieferung, bis jemand die App öffnet. Im Manifest mit Begründung kommentieren, wie die übrigen | E2 |
| **F-S5Z-05** | **Changelog jetzt oder später?** Rahmenplan Schritt 6 sieht das Präfix `Android` „mit der ersten verteilten Fassung" vor; `CLAUDE.md` 2 verlangt einen Eintrag je Änderung; `version.properties` sagt „Im Changelog trägt sie das Präfix Android" | **Jetzt.** Die erste verteilte Fassung ist faktisch da — der Auftraggeber betreibt ein APK auf dem S24 und prüft damit. Ein Eintrag je E-Paket, Prosa mit Begründung, Präfix `Android`; der S4-Rest setzt die Reihe fort | E1 |
| **F-S5Z-06** | **Wo genau stand „Aufzeichnung laufend"?** Im Repositorium gibt es den Wortlaut nicht; die Spurenseite sagt „–offen", der Diensttag hat kein Dienstende. Seite und Stelle nennen | **Klären vor der Diagnose (1.3).** Steht der Text in der App (Dauermeldung, Zustandszeile), ist es Kette B3 (Dienst lief weiter); steht er im Web, ist die Stelle nachzutragen, und die Prüfliste bekommt sie als Erkennungsmerkmal | E2 |

---

## 4. Der Ortungszustand — Zustände, Übergänge, Wortlaute

### 4.1 Zustände

| Zustand | Bedingung | Es wird aufgezeichnet? |
|---|---|---|
| `FREIGABE_FEHLT` | `ACCESS_FINE_LOCATION` nicht erteilt | nein |
| `STANDORT_AUS` | Freigabe da, `isProviderEnabled(GPS_PROVIDER)` falsch oder `onProviderDisabled` empfangen. Absichtlich der **GPS-Anbieter**, nicht `isLocationEnabled()` (API 28+): Im Modus „Stromsparen" ist der Standort an, GPS aber aus — und aufgezeichnet wird nur mit GPS | nein |
| `SUCHT` | Anbieter an, seit Dienststart bzw. seit `onProviderEnabled` noch kein roher Fund, und Z-S5Z-01 nicht verstrichen | noch nicht |
| `KEIN_SIGNAL` | Anbieter an, kein roher Fund seit Z-S5Z-01 (nach Start) bzw. Z-S5Z-02 (nach einem Fund) | nein |
| `UNGENAU` | rohe Funde kommen, aber kein **brauchbarer** (E-S5Z-02) seit Z-S5Z-02 | nein — die Ausdünnung verwirft |
| `OK` | ein brauchbarer Fund innerhalb der letzten Z-S5Z-02 | ja |

### 4.2 Übergänge (der Wächter, E-S5Z-06)

| Auslöser | Von | Nach | Warnung (ID 3) |
|---|---|---|---|
| Dienststart, Freigabe fehlt | — | `FREIGABE_FEHLT` | posten |
| Dienststart, Anbieter aus | — | `STANDORT_AUS` | posten |
| Dienststart, Anbieter an | — | `SUCHT` | — |
| `onProviderDisabled` | jeder | `STANDORT_AUS` | posten (sofort) |
| `onProviderEnabled` | `STANDORT_AUS` | `SUCHT` (Frist neu) | löschen |
| Z-S5Z-01 verstrichen ohne rohen Fund | `SUCHT` | `KEIN_SIGNAL` | posten |
| Z-S5Z-02 ohne rohen Fund | `OK`, `UNGENAU` | `KEIN_SIGNAL` | posten |
| Z-S5Z-02 mit rohen, ohne brauchbare Funde | `OK`, `SUCHT` | `UNGENAU` | posten |
| brauchbarer Fund | jeder außer `FREIGABE_FEHLT`, `STANDORT_AUS` | `OK` | löschen |
| Z-S5Z-04 verstrichen im selben Warnzustand | `STANDORT_AUS`, `KEIN_SIGNAL`, `UNGENAU` | gleich | erneut posten (Erinnerung) |
| Dienstende | jeder | — (Wächter aus) | löschen |

Die Zustandsmaschine ist eine reine Klasse `Ortungswaechter(jetztMs)` ohne
Android-Bezug: Sie bekommt Ereignisse (`anbieterAn`, `anbieterAus`,
`roherFund`, `brauchbarerFund`, `tick`) und liefert Zustand und
Warnentscheidung — deshalb ist sie auf der JVM prüfbar, wie `Sendetakt`
und `Uhrbedienung`.

### 4.3 Wortlaute Handy

Alle Texte laufen durch `tools/wortliste/` Bereich d (R28; Soll 0/0/0).
Zweizeilig, wo es eine Handlung gibt: erst **was los ist**, dann **was
hilft** (Muster `Pair.mc`, übernommen in `strings.xml` 59–64).

**Vor dem Dienst (Dienstansicht, Karte):**

| Zustand | Block (`Meldungsblock`, warnend) | Knopf |
|---|---|---|
| `FREIGABE_FEHLT` | „Ortung nicht freigegeben" / „Ohne Ortungsfreigabe zeichnet die App keine Spur auf. Tippe hier, um sie zu erteilen." *(bestehend)* | „Ortung freigeben" *(bestehend)* |
| `STANDORT_AUS` | „Standort ausgeschaltet" / „Ohne eingeschalteten Standort beginnt kein Dienst. Tippe hier, um ihn einzuschalten." | „Standort einschalten" |
| sonst | — | „Dienst beginnen" *(bestehend)* |

**Im Dienst (Zustandszeile neben dem Aufnahmepunkt, ersetzt
`dienst_laeuft_seit` / `…_ohne_gps`):**

| Zustand | Text | Farbe |
|---|---|---|
| `OK` | „Aufzeichnung läuft seit 07:02 · GPS ok" | Asphalt (wie heute) |
| `SUCHT` | „Dienst läuft seit 07:02 · GPS sucht …" | gedämpft |
| `KEIN_SIGNAL` | „Dienst läuft seit 07:02 · kein GPS-Signal seit 3 min · keine Aufzeichnung" | Rot (`rotTief`) |
| `UNGENAU` | „Dienst läuft seit 07:02 · GPS zu ungenau · keine Aufzeichnung" | Orange |
| `STANDORT_AUS` | „Dienst läuft seit 07:02 · Standort aus · keine Aufzeichnung" | Rot |
| `FREIGABE_FEHLT` | „Dienst läuft seit 07:02 · Ortung nicht freigegeben · keine Aufzeichnung" | Rot |

„Aufzeichnung läuft" steht nur bei `OK` — in allen anderen Zuständen
„Dienst läuft", weil es wahr ist und das andere nicht. Der Aufnahmepunkt
bleibt in jedem Zustand (E-S4-22a: er zeigt den Dienst, nicht das Signal);
die Zeile daneben sagt, was der Punkt nicht sagen kann.

**Dauermeldung (ID 1, Kanal „Aufzeichnung", still):** derselbe Text wie die
Zustandszeile, ohne Farbe. Beim Dienstende „Dienst beendet · sende …".

**Warnung (ID 3, Kanal „Warnungen", Vibration):**

| Zustand | Titel | Text |
|---|---|---|
| `STANDORT_AUS` | „Keine Aufzeichnung" | „Der Standort ist ausgeschaltet. Einschalten, sonst bleibt die Spur dieses Dienstes leer." *(Antippen: Standort-Einstellungen)* |
| `KEIN_SIGNAL` | „Keine Aufzeichnung" | „Seit 3 min kommt kein GPS-Signal. Handy aus der Tasche oder ans Fenster." |
| `UNGENAU` | „Keine Aufzeichnung" | „Das GPS-Signal ist zu ungenau (über 100 m). Handy aus der Tasche oder ans Fenster." |
| `FREIGABE_FEHLT` | „Keine Aufzeichnung" | „Die Ortungsfreigabe fehlt. In der App erteilen." |

**Hinweis nach dem Dienstende (ID 2, Kanal „Aufzeichnung", still,
wegwischbar):** „Dienst beendet · 2 Pakete warten auf Netz" — wird vom
Nachsende-Job gelöscht, sobald der Rückstand 0 ist.

**Rückfrage „Dienst beenden?"** (`dienst_beenden_text`): heute „Die
Aufzeichnung hört auf, und alles Offene wird abgeschlossen und gesendet."
Neu: „Die Aufzeichnung hört auf. Alles Offene wird abgeschlossen und
gesendet — ohne Netz, sobald es wieder da ist." (B-S5Z-09)

### 4.4 Wo der Zustand lebt

`NAdokuApp.ortung` (Klasse `Ortungslage`: `stand`, `seitMs`, `letzterFundMs`;
Felder `@Volatile`). Schreiber: der Vordergrunddienst (Wächter,
Zuhörer). Leser: die Dienstansicht im Sekundentakt (`HauptActivity.kt`
185–218, das `stand`-Objekt bekommt `ortung: Ortungsstand`), die
Dauermeldung, die Warnung, in E3 die Standmeldung an die Uhr. Ohne
laufenden Dienst gilt, was die Oberfläche selbst misst (E-S5Z-01).

---

## 5. Dienstende und Nachsenden

### 5.1 Ablauf am Handy

1. Rückfrage → Ja: `klammer.beenden()` (unverändert: Einsatz und Segment
   `final`, Dienst `beendet_at`), dann `AufzeichnungsDienst.beenden()`.
2. Im Dienst: `removeUpdates`, Wächter aus, Netzrückruf abmelden, Warnung
   (ID 3) löschen, Dauermeldung „Dienst beendet · sende …".
3. DIENSTENDE-Lauf auf dem Sendeausführer (E-S5Z-11). **Der Dienst bleibt
   im Vordergrund**, bis der Lauf zurück ist.
4. Rückkehr auf dem Hauptfaden:
   - Rückstand 0 → `stopForeground(REMOVE)`, `stopSelf`. Keine Meldung
     bleibt.
   - Rückstand > 0 und `spaeterErneut` → Nachsende-Job planen (E-S5Z-09),
     Hinweismeldung ID 2, `stopForeground(REMOVE)`, `stopSelf`.
   - Rückstand > 0 und `pausiert` (401) → kein Job; Hinweismeldung „Dienst
     beendet · Schlüssel abgewiesen – Gerät neu koppeln"; stoppen.
   - Pakete `fehlerhaft` (400) → nichts zu senden; die Ansicht zeigt sie
     (E-S5Z-12).
5. Die Ansicht liest den Sendezustand im Sekundentakt: „Alles gesendet" bzw.
   „Rückstand N Pakete" und die Ergebniszeile des letzten Laufs.

### 5.2 Ablauf von der Uhr

`HandyHorcher.onMessageReceived` → `uebernimm(meldung)` → `Dienstfolge`:

| lief vorher | läuft nachher | Dienst steht | Folge |
|---|---|---|---|
| nein | ja | egal | `starten()` (wie heute) |
| ja | nein | ja | `beenden()` → Weg 5.1 ab Schritt 2 |
| ja | nein | nein | nichts (Dienst war schon weg; Nachsende-Job über den App-Start-Pfad) |
| sonst | | | nichts |

„lief vorher" wird **vor** `uebernimm()` gelesen (`klammer.laeuft()`), „läuft
nachher" danach. Die Standmeldung an die Uhr geht wie heute zurück.

### 5.3 Der Nachsende-Job (E-S5Z-09)

`NachsendeDienst : JobService`, im Manifest mit
`android:permission="android.permission.BIND_JOB_SERVICE"`. Planung in
**einer** Funktion `Nachsenden.planen(kontext)` (idempotent: `schedule()` mit
derselben ID ersetzt den vorhandenen Job), gerufen aus dem Dienstende (5.1),
aus dem Job selbst (`reschedule`) und aus `NAdokuApp` beim Start, wenn
Rückstand > 0 und kein Dienst läuft. `onStartJob` reicht den Lauf beim
Sendeausführer ein und gibt `true` zurück; der Lauf ruft am Ende
`jobFinished(params, wantsReschedule)`. `onStopJob` gibt `true` zurück (der
Lauf ist idempotent, der Server auch — Vertrag 2).

Was der Job **nicht** tut: einen laufenden Dienst anfassen, eine Rückfrage
stellen, etwas löschen. Was er braucht: entsperrtes Gerät nach einem
Neustart — die Ablage der Zugangsdaten liegt in der
anmeldungsgeschützten Speicherung, und der Planer startet Jobs für Apps
ohne Direct-Boot-Kennzeichnung erst nach dem ersten Entsperren. Das ist
richtig so und wird nicht umgangen.

### 5.4 Wiederverbindung im Dienst (E-S5Z-10)

`registerDefaultNetworkCallback` in `onCreate` des Vordergrunddienstes,
`unregisterNetworkCallback` in `onDestroy`. `onAvailable` → `sendeWenn
(WIEDERVERBINDUNG)`; `onLost` → nichts (der nächste Lauf stellt es fest).
Die Mindestabstandsregel liegt in `Sendetakt`, nicht im Rückruf — dort ist
sie prüfbar.

### 5.5 Was die Ansicht zeigt (E-S5Z-12)

Unter „Gekoppelt · …" (bestehende `Zustandszeile`n):

| Zeile | Wann | Farbe |
|---|---|---|
| „Alles gesendet" | Rückstand 0 und keine abgewiesenen | Blau *(bestehend)* |
| „Rückstand 2 Pakete" | Rückstand > 0 | Orange *(bestehend)* |
| „1 Paket vom Server abgewiesen" | `fehlerhaft = 1` vorhanden | Rot |
| Ergebniszeile des letzten Laufs: „Gesendet · 12:41" / „Keine Verbindung · wird nachgeholt" / „Schlüssel abgewiesen · Gerät neu koppeln" / „Server hat 1 Paket abgewiesen" | nach jedem Lauf, solange die Ansicht offen ist; aus `NAdokuApp.letzterSendebericht` | gedämpft / Rot |
| Knopf „Jetzt senden" (`KnopfNeutral`) | Rückstand > 0, kein Lauf aktiv | — |

Der Puffer bekommt dafür `abgewiesen(): Int` (`COUNT(*) WHERE fehlerhaft =
1`). Ein Weg, ein abgewiesenes Paket loszuwerden (ansehen, exportieren,
verwerfen), ist **nicht** Umfang — Backlog-Kandidat „abgewiesene Pakete
sichtbar machen und ausräumen".

### 5.6 Ausfallmatrix — was bei welchem Ausfall geschieht

| Ausfall | Heute | Nach E2 |
|---|---|---|
| App direkt nach „Beenden" weggewischt | Sendefaden ohne Dienst; Prozess kann sterben, nichts wiederholt | Dienst steht bis zum Ende des Laufs im Vordergrund; danach Job, falls Rest |
| Kein Netz beim Beenden | nichts bis zum nächsten Dienst | Job sendet, sobald Netz da ist; Hinweismeldung sagt es |
| 5xx beim Beenden | wie kein Netz | wie kein Netz (Backoff) |
| Prozess stirbt, bevor der Job geplant ist | — | App-Start plant nach (Rückstand > 0, kein Dienst) |
| Neustart des Handys mit Rückstand | nichts bis zum Öffnen der App und dem nächsten Dienst | persistierter Job läuft nach dem Entsperren (F-S5Z-04) |
| Dienstende von der Uhr | Vordergrunddienst läuft weiter, GPS an, Senden beim nächsten Takt | Dienst wird beendet, Lauf wie 5.1 |
| 401 | pausiert, „Rückstand N" ohne Grund | „Schlüssel abgewiesen · Gerät neu koppeln"; kein Job |
| 400 auf das Abschlusspaket | unsichtbar, „Alles gesendet" | „1 Paket vom Server abgewiesen" |
| Netz kommt im Dienst zurück | nächster Takt (≤ 15 min) | sofort, höchstens einmal je 60 s |

---

## 6. Uhr-Spiegel (E3, optional)

**Nachrichtenformat** (`gemeinsam/Uhrnachricht.kt`): `Standmeldung` bekommt
`ortung: String?`; `schreibe()` setzt den Schlüssel `ortung` nur, wenn der
Wert nicht `null` ist; `liesStand()` liest ihn mit `optString` und liefert
`null`, wenn er fehlt. Codes: `frei_fehlt`, `aus`, `sucht`, `kein`, `ungenau`,
`ok`. Die Schlüsselmenge der Standmeldung wächst damit um einen; die
Schlüsselmenge der **Uhrmeldung** (Uhr → Handy, `NachrichtenformatTest` 61)
bleibt unverändert — dort steht die Sicherheitsaussage.

**Handy:** `Uhrannahme.stand()` füllt `ortung` aus `NAdokuApp.ortung`; der
Vordergrunddienst schickt bei jedem Zustandswechsel des Wächters eine
Standmeldung über `WearNachrichtenweg` (`PFAD_STAND`). Kommt sie nicht an
(`false`), bleibt es dabei — die nächste Antwort auf ein Ereignis trägt den
Stand ohnehin.

**Uhr:** `Uhrzustand` bekommt `ortung: String?`; `Uhrsteuerung.standEingegangen`
übernimmt es. In `LaufendeAnsicht` steht unter der Zustandszeile, **vor** den
Knöpfen (Statusanzeigen an den Rand, E-S4-51 — aber diese eine über die
Knöpfe, aus demselben Grund wie `dienst_schwebt`: sie sagt, dass **nichts
aufgezeichnet wird**):

| `ortung` | Text | Farbe |
|---|---|---|
| `aus`, `kein`, `ungenau`, `frei_fehlt` | „keine Ortung · keine Aufzeichnung" | Rot |
| `sucht` | „GPS sucht" | Sand |
| `ok`, `null` | — | — |

Ein Wortlaut für vier Ursachen, weil die Uhr die Ursache nicht beheben kann
— das tut das Handy, und das vibriert. Prüffälle: `NachrichtenformatTest`
(Rundlauf mit und ohne Feld), `UhrsteuerungTest` (Übernahme),
`UhrbedienungTest` (Zustand bleibt beim Bedienen erhalten), `UhrBildTest`
(ein Bild der laufenden Ansicht mit der roten Zeile, Knopfhöhe 48 dp
unverändert). Wortliste Bereich d.

---

## 7. Zahlen — mit Fundstelle und Begründung

**Code** = steht so im Repositorium; **abgeleitet** = daraus gerechnet;
**Vorschlag** = neu, zur Entscheidung (Abschnitt 3), am Gerät nachzumessen.

| Nr. | Was | Wert | Herkunft | Fundstelle und Begründung |
|---|---|---|---|---|
| Z-S5Z-01 | Frist bis zum ersten Fund (`SUCHT` → `KEIN_SIGNAL`) | 120 s | **Vorschlag** (F-S5Z-02) | Ein GPS-Kaltstart ohne Netzhilfe braucht typisch 30–60 s, in Gebäuden länger; 120 s lassen dem Empfänger Zeit, ohne dass eine Anfahrt ganz ohne Warnung bleibt. Ist der Standort erst während des Dienstes eingeschaltet worden, gilt dieselbe Frist ab `onProviderEnabled` |
| Z-S5Z-02 | Frist ohne (brauchbaren) Fund im laufenden Betrieb (`OK` → `KEIN_SIGNAL`/`UNGENAU`) | 60 s | **Vorschlag** (F-S5Z-02) | 1-Hz-Abtastung (`TAKT_MS` 1000, `AufzeichnungsDienst.kt` 266): 60 ausgefallene Messungen. Die Ausdünnung hält spätestens alle 10 s einen Punkt (`HOECHSTABSTAND_S`, `Ausduenner.kt` 131) — nach 60 s fehlen sechs Pflichtpunkte. Kürzer warnte in jeder Unterführung; länger ließe eine Minute Spur unbemerkt fehlen |
| Z-S5Z-03 | Wächtertakt | 10 s | abgeleitet | gleich `HOECHSTABSTAND_S`: feiner als der langsamste erwartete Punkt braucht der Wächter nicht zu schauen; der vorhandene `Handler` trägt ihn |
| Z-S5Z-04 | Erinnerungsabstand der Warnung | 600 s | **Vorschlag** (F-S5Z-03) | zehn Minuten: nicht als Dauerwarnung (Backlog 82), aber kein Dienst läuft eine Stunde leer |
| Z-S5Z-05 | Mindestabstand `WIEDERVERBINDUNG` | 60 s | **Vorschlag** | flatterndes Mobilfunknetz; jeder Lauf kostet mindestens eine Anfrage mit bcrypt-Prüfung am Server (`db.php` 476–481, aus S5 Z-11) |
| Z-S5Z-06 | Backoff des Nachsende-Jobs | 30 s, exponentiell | Code (Plattform) | `JobInfo.DEFAULT_INITIAL_BACKOFF_MILLIS`; die Plattform deckelt bei 5 h |
| Z-S5Z-07 | Streuung, ab der ein Fund unbrauchbar ist | 100 m | Code | `Ausduenner.kt` 137 (E-S4-32, „blind gewählt, am Gerät nachzumessen") — E1 misst zum ersten Mal mit, wie oft sie greift (Zähler im Protokoll) |
| Z-S5Z-08 | Zeitlimits des Netzwegs | 15 s Verbindung, 30 s Lesen | Code | `Netzweg.kt` 44–45; sie begrenzen den DIENSTENDE-Lauf je Anfrage |
| Z-S5Z-09 | Sendetakt im Dienst | 900 s | Code | `Sendetakt.kt` 65 (E-S4-07) — unverändert |
| Z-S5Z-10 | Meldungs-IDs | 1 Dauermeldung · 2 Hinweis Dienstende · 3 Warnung | abgeleitet | `MELDUNG_ID = 1` besteht (`AufzeichnungsDienst.kt` 262); zwei weitere, damit sich die drei nicht überschreiben |
| Z-S5Z-11 | Höchste Anfragen je Lauf | 500 | Code | `Sender.kt` 191 — unverändert; ein Dienstende braucht rund zwanzig |

---

## 8. Arbeitspakete E1–E3, Reihenfolge, Abnahme

**Reihenfolge:** E1 → E2 → E3 (E3 optional, kann bei Zeitnot entfallen —
dann Backlog-Kandidat). Kein Fable-Schritt. Nach jedem Paket: Statusblock,
Abschnitt 8 und 11 fortschreiben, Prüfdokument S5 ergänzen, Zweig pushen.
Prüfmittel **zuletzt** (`CLAUDE.md` 6). Vor E2 die Diagnose aus 1.3.

### Paket E1 — Ortungswächter

| | |
|---|---|
| **Dateien** | `handy/…/aufzeichnung/Ortungswaechter.kt` (neu, reine Zustandsmaschine 4.2), `handy/…/aufzeichnung/AufzeichnungsDienst.kt` (Zuhörer als Klasse E-S5Z-05, `isProviderEnabled` beim Start, Wächtertakt, Warnkanal und Warnung E-S5Z-04, Dauermeldung nach 4.3), `handy/…/NAdokuApp.kt` (`ortung`), `handy/…/HauptActivity.kt` (Sperre E-S5Z-03, `stand.ortung`), `handy/…/DienstAnsicht.kt` (Block „Standort ausgeschaltet", Zustandszeile 4.3), `handy/…/aufzeichnung/Ausduenner.kt` (`brauchbar()` herausgezogen, Verhalten unverändert), `handy/src/main/res/values/strings.xml` (Texte 4.3), `android/version.properties`, Prüffälle |
| **Prüffälle (JVM)** | `OrtungswaechterTest`: Start ohne Anbieter → `STANDORT_AUS` + Warnung; Start mit Anbieter → `SUCHT` ohne Warnung; 119 s ohne Fund → `SUCHT`, 120 s → `KEIN_SIGNAL` + Warnung; brauchbarer Fund → `OK`, Warnung gelöscht; 60 s ohne Fund aus `OK` → `KEIN_SIGNAL`; rohe Funde mit 150 m Streuung 60 s lang → `UNGENAU`; `onProviderDisabled` aus `OK` → `STANDORT_AUS` sofort; `onProviderEnabled` → `SUCHT`, Frist neu; Erinnerung nach 600 s im selben Zustand, keine nach 599 s; Wechsel `KEIN_SIGNAL` → `STANDORT_AUS` warnt sofort; Dienstende löscht — **mindestens 12 Fälle**, Zeit eingespeist, kein Warten. `AusduennerTest`: `brauchbar()` bei 99 m, 100 m, 101 m, `null` — und die fünf Ströme unverändert (0 Abweichungen). Reflexionsfall: der Zuhörer deklariert `onLocationChanged`, `onStatusChanged`, `onProviderEnabled`, `onProviderDisabled` selbst |
| **Abnahme** | `./gradlew build` 0 Lint-Fehler, 0 Fehlschläge, Warnungszahl genannt · Wortliste Bereich d **0/0/0** mit Dateizahl · `werkzeuge/kontraste.py` 0 Paare unter Zielwert (neue Farbe-auf-Fläche-Paare der Zustandszeile: `rotTief`/Orange auf Schnee) · Gerätetest 9.2 Punkte 1–6 · Robolectric-Bild der Dienstansicht in `STANDORT_AUS` (vor dem Dienst) und `KEIN_SIGNAL` (im Dienst), Knopfhöhe 48 dp |

### Paket E2 — Dienstende und Nachsenden

| | |
|---|---|
| **Dateien** | `AufzeichnungsDienst.kt` (Dienstende 5.1, kein Nachposten, Netzrückruf 5.4, Laufmarke), `handy/…/uhr/HandyHorcher.kt` und neu `handy/…/uhr/Dienstfolge.kt` (5.2), neu `handy/…/senden/NachsendeDienst.kt` und `Nachsenden.kt` (5.3), `NAdokuApp.kt` (Sendeausführer E-S5Z-11, `letzterSendebericht`, Planung beim Start), `senden/Sendetakt.kt` (Mindestabstand Wiederverbindung), `senden/Sender.kt` (nur, falls `chunkPunkte` für den Ausführer abgesichert werden muss), `puffer/Puffer.kt` (`abgewiesen()`), `HauptActivity.kt` und `DienstAnsicht.kt` (5.5, „Jetzt senden", Ergebniszeile), `AndroidManifest.xml` (`RECEIVE_BOOT_COMPLETED` nach F-S5Z-04, `NachsendeDienst` mit `BIND_JOB_SERVICE`, Begründungskommentare), `strings.xml` (4.3: Hinweis, Ergebniszeilen, `dienst_beenden_text`), `android/version.properties`, Prüffälle |
| **Prüffälle (JVM)** | `DienstfolgeTest`: die vier Zeilen aus 5.2 (4 Fälle). `SendetaktTest`: Wiederverbindung nach 59 s nicht fällig, nach 60 s fällig, Ereignisse weiterhin sofort (3 Fälle). `NachsendenTest` (reine Entscheidung „planen?"): `spaeterErneut` + Rückstand → ja; Rückstand 0 → nein; `pausiert` → nein; laufender Dienst → nein (4 Fälle). `PufferTest`/`SenderTest`: `abgewiesen()` zählt 400-Pakete, `rueckstand()` nicht (2 Fälle). `SendeRundlaufTest` (gegen `ingest.php`, wo eine Installation läuft): Dienstende ohne Gegenstelle → Rückstand 2, Bericht `spaeterErneut`; Gegenstelle zurück → ein Lauf, Rückstand 0, Segment am Server `final = 1`, `days.ended_at` gesetzt (**das** ist der Beleg für Kette B2) |
| **Abnahme** | Baulauf wie E1 · Wortliste d 0/0/0 · Gerätetest 9.2 Punkte 7–14, **darunter 12 (Dienstende von der Uhr) und 9 (Flugmodus)** als Belege für H1/H2 · Diagnoseergebnis aus 1.3 in Abschnitt 8 eingetragen · nach einem vollständigen Dienst am S24: keine Benachrichtigung bleibt, Web zeigt Dienstende und geschlossenes Segment |

### Paket E3 — Uhr-Spiegel (optional)

| | |
|---|---|
| **Dateien** | `gemeinsam/…/Uhrnachricht.kt` (`ortung`), `handy/…/uhr/Uhrannahme.kt` (`stand()`), `AufzeichnungsDienst.kt` (Standmeldung bei Wechsel), `uhr/…/Uhrzustand.kt`, `uhr/…/funk/Uhrsteuerung.kt`, `uhr/…/UhrActivity.kt` (Zeile 6), `uhr/src/main/res/values/strings.xml`, `android/version.properties`, Prüffälle (6), `docs/Geraete-Eingabe.md` 7.3 (eine Zeile: „keine Ortung" am Handgelenk sichtbar) |
| **Abnahme** | Baulauf · `NachrichtenformatTest`: Rundlauf mit Feld, Rundlauf **ohne** Feld (alte Fassung) → `null`; Schlüsselmenge der Uhrmeldung unverändert · `UhrBildTest` +1 Bild, 48 dp · Wortliste d 0/0/0 · Gerätetest 9.2 Punkt 15 (nur mit Wear-OS-Uhr; sonst als nicht prüfbar an erster Stelle des Prüfdokuments) |

### Umsetzungsstand (wird je Paket fortgeschrieben)

| Paket | Stand | Probleme / Lösungen | Entscheidungen |
|---|---|---|---|
| Vorlauf | **erledigt** 03.09.2026 | Zwei Werkzeuge, die der Prüfstand braucht (`tools/containeraufbau/`, `…/lokal_einrichten.sh`), liegen nicht auf `main`, sondern nur auf dem S7-Zweig — ohne sie gibt es kein Android-SDK und damit keine Zahl. **Gelöst:** von dort geholt und benutzt, aber **nicht** auf diesen Zweig committet; sie gehören dem S7-Stand und kollidierten sonst beim Merge. Ausgangszahlen gemessen und mit Vorbereitung 8.2 verglichen: **alle acht decken sich** | E-S5Z-15 bis -26 eingetragen |
| Diagnose 1.3 | **teilweise beantwortet** 03.09.2026 durch den Auftraggeber | Zwei von vier Schritten: **beendet am Handy** (nicht an der Uhr) und **keine Fehlermeldung**; die App zeigte **keinen Rückstand**, also „Alles gesendet". Damit ist **H1 (Kette B3) ausgeschlossen** — die Uhr war es nicht. Übrig bleiben H2 und H3, und die Beobachtung „Alles gesendet bei offenem Segment" **passt auf H3** (Kette B5): Der Server antwortete mit 400, die App markierte das Paket `fehlerhaft = 1` und nahm es aus Warteschlange **und** Anzeige (B-S5Z-06). **Vorbehalt, der dazugehört:** Der Blick in die App erfolgte *später*, nicht im Augenblick des Beendens — ein Rückstand, den ein späterer Dienst inzwischen weggeräumt hat, sähe heute genauso aus. Unterschieden wird das nur am Server: Ist das Segment **heute noch** offen, war es H3; ist es geschlossen, war es H2 mit später Nachlieferung. Schritte 2 und 3 der Diagnose (Logcat, SQL) stehen weiter offen | Der belegte Fehler ist **B-S5Z-06** (400-Pakete sind unsichtbar) — nicht mehr nur ein Nebenfund. E2 zeigt sie (E-S5Z-12); das ist damit **die** Abnahme von E2, nicht eine Beigabe |
| E1 | **erledigt** 03.09.2026, Android **0.8.0**, Bilderlauf **0.8.1** | sechs Fundstellen, alle unten | E-S5Z-22 (rot statt orange), E-S5Z-23 (Handler-Token), E-S5Z-27 (Wortlaut „GPS empfängt") |
| E2 | offen | — | — |
| E3 | offen | — | — |

#### Was in E1 entstanden ist

| Datei | Was |
|---|---|
| `aufzeichnung/Ortungswaechter.kt` *(neu)* | Die Zustandsmaschine (6 Stufen, 3 Warnbefehle) plus `Ortungslage` — ohne Android-Bezug, Zeit eingespeist |
| `aufzeichnung/Ortungszuhoerer.kt` *(neu)* | Der `LocationListener` als ausgeschriebene Klasse mit allen vier Methoden (B-S5Z-01) |
| `aufzeichnung/AufzeichnungsDienst.kt` | Wächter, Wächtertakt, zweiter Meldungskanal, Warnung (ID 3), Dauermeldung nach Zustand, `isProviderEnabled` beim Start, getrennte Handler-Token |
| `aufzeichnung/Ausduenner.kt` | `brauchbar()` herausgezogen — **eine** Schwelle für Aufzeichnung und Anzeige |
| `NAdokuApp.kt` | `ortung: Ortungslage?`, `@Volatile` |
| `HauptActivity.kt`, `DienstAnsicht.kt` | Sperre vor dem Dienst, Block „Standort ausgeschaltet", Zustandszeile in drei Farbstufen, zwei neue Vorschauen |
| `gemeinsam/Uhrnachricht.kt` | `Standmeldung.ortung` und `object Ortungscode` (E-S5Z-15) |
| `uhr/…` (4 Dateien) | Zustand, Übernahme, Zeile am unteren Rand, Texte |
| `werkzeuge/kontraste.py` | **8 neue Paare** — 16 → 24 |

**Prüffälle: 220 → 260** (+40), dazu **54 Bilder** (48 Handy, 6 Uhr). `OrtungswaechterTest` 21, `OrtungszuhoererTest`
2, `AusduennerTest` +5, `NachrichtenformatTest` +5, `UhrsteuerungTest` +3.

#### Probleme in E1 und wie sie gelöst wurden

| Was | Wie es auffiel | Lösung |
|---|---|---|
| **`postDelayed(r, token, ms)` gibt es erst ab API 28**, `minSdk` ist 26 | Vier Lint-Fehler `NewApi` im Baulauf — nicht beim Übersetzen, das ging durch | `postAtTime(r, token, uptimeMillis() + ms)`, ab API 1 vorhanden, in einer Hilfsfunktion `spaeter()` gekapselt |
| **Der Wortlaut „· GPS ok" ergab eine 15. Lint-Warnung** (`Typos`, „ok" → „OK") | Warnungszahl wich von 14 ab | Nicht stummgeschaltet, sondern umformuliert: **„· GPS empfängt"** (E-S5Z-27). Das ist auch die bessere Aussage — es nennt das Gemessene statt eine Güte zu behaupten, die die App nicht abstuft |
| **Die Trailing-Lambda-Falle:** `Uhrannahme(puffer, klammer) { Modus.X }` band nach dem neuen vierten Parameter an `ortung` statt an `modus` | Zwei Übersetzungsfehler in Prüffällen | Beide Aufrufstellen auf benannte Argumente umgestellt. Der Fehler wäre ohne Typprüfung still gewesen — beide Parameter sind Funktionen |
| **`marke_rot` als Schrift auf Asphalt trägt 4,12 : 1** und damit unter AA — es trifft die **bestehende** Uhr-Zeile „wartet aufs Handy" | Beim Nachrechnen aller Token für E-S5Z-22 | `marke_rosa` (15,94 : 1), der helle Vertreter derselben Familie. Als **Fläche** mit weisser Schrift trägt dasselbe Rot 4,78 : 1 und bleibt richtig. Neuer Fund **B-S5Z-15** |

#### Was E1 bewusst stehen lässt

Der Sendelauf beim Dienstende läuft weiter auf einem eigenen Faden, während
der Dienst schon stoppt (B-S5Z-03, Kette B1). Halb behoben wäre er schwerer zu
prüfen als gar nicht; der Umbau gehört als Ganzes zu E2 und ist im Code an
der Stelle vermerkt.

---

## 9. Prüfprotokoll-Soll

### 9.1 Maschinell — die Zahlen, die am Ende stehen müssen

| Prüfmittel | Misst | Soll |
|---|---|---|
| `./gradlew build` (`handy`, `uhr`) | Übersetzen, Lint, Prüffälle | 0 Lint-Fehler, 0 Fehlschläge; Warnungen gezählt (heute 14, alle die AGP-9-Kette — keine neue) |
| `testDebugUnitTest` **und** `testReleaseUnitTest` | Prüffälle | alle grün; Zahl je Modul genannt; neu mindestens: E1 ≥ 16, E2 ≥ 13, E3 ≥ 6 |
| `SendeRundlaufTest` mit `-Pnadoku.rundlauf` | Dienstende gegen echtes `ingest.php` | Segment `final = 1`, `days.ended_at` gesetzt, Rückstand 0 |
| `tools/wortliste/` Bereich d | sichtbare Texte beider Module | 0 / 0 / 0, Dateizahl genannt |
| `android/werkzeuge/kontraste.py` | Farbpaare | 0 unter dem Zielwert (neue Paare mitgezählt) |
| `android/werkzeuge/farbabgleich.py`, `bildmarken.sh`, `stroeme.py` | unverändert | 0 / 0 / 0 Abweichungen |
| `UhrBildTest` | Bilder der Uhr | +1 Bild (E3), Knopfhöhe 48 dp, 0 % außerhalb des Kreises |
| Reflexionsfall Zuhörer | vier Methoden deklariert | 4 / 4 |

### 9.2 Gerätetest — Prüfliste (je Punkt Bedienweg, Erwartung, Scheitern)

Voraussetzung: signiertes APK aus demselben Schlüssel wie das installierte;
`adb logcat -s NAdoku` mitlaufen lassen. Ort ohne GPS-Empfang: Tiefgarage
oder das Handy in eine Metallbox.

| Nr. | Bedienweg | Erwartet | Scheitern erkennbar an |
|---|---|---|---|
| 1 | Standort aus, App öffnen | Block „Standort ausgeschaltet" mit Knopf; kein „Dienst beginnen" | Knopf „Dienst beginnen" sichtbar; oder der Block bleibt nach dem Einschalten stehen |
| 2 | „Standort einschalten" antippen, einschalten, zurück | Block verschwindet binnen 1 s, „Dienst beginnen" erscheint | Block bleibt; App muss neu gestartet werden |
| 3 | Dienst beginnen im Freien | „GPS sucht …", dann binnen 120 s „Aufzeichnung läuft seit … · GPS ok"; keine Vibration | Warnung trotz Empfang → Z-S5Z-01 zu kurz (Zeit notieren) |
| 4 | Im Dienst Standort ausschalten (Schnelleinstellung) | sofort Zustandszeile Rot „Standort aus · keine Aufzeichnung", Dauermeldung gleichlautend, **eine Vibration** ohne Ton, Warnung in der Leiste; Antippen öffnet die Standort-Einstellungen | keine Vibration (Kanal prüfen: Einstellungen → Apps → NAdoku → Benachrichtigungen → Warnungen); App stürzt ab (auf Android 8–10: B-S5Z-01) |
| 5 | Standort wieder einschalten | „GPS sucht …" → „GPS ok"; Warnung verschwindet von selbst | Warnung bleibt; Zeile bleibt rot |
| 6 | Handy 3 min in die Tiefgarage/Metallbox | nach 60 s „kein GPS-Signal seit 1 min · keine Aufzeichnung", Vibration; Punktzähler steht; nach der Rückkehr binnen 60 s „GPS ok", Warnung weg | Zeile bleibt „GPS ok" bei stehendem Zähler; oder keine Vibration |
| 7 | 10 min im Warnzustand bleiben | zweite Vibration nach 10 min, keine dazwischen | mehr als zwei, oder keine zweite |
| 8 | Dienst beenden mit Netz, App sofort wegwischen | Dauermeldung „Dienst beendet · sende …" für Sekunden, dann **keine** Meldung mehr; App öffnen: „Alles gesendet"; Web: Diensttag mit Dienstende, letztes Segment mit Endzeit | Meldung „Kein Dienst" bleibt (B-S5Z-03); Web „–offen" |
| 9 | Flugmodus an, Dienst beenden, Flugmodus 5 min später aus, App **nicht** öffnen | Hinweis „Dienst beendet · N Pakete warten auf Netz"; nach dem Flugmodus binnen weniger Minuten verschwindet er; Logcat „Sendelauf: … fertig"; Web zeigt das Ende | Hinweis bleibt > 15 min bei Netz; Logcat ohne Lauf → Job läuft nicht (Akkueinstellung prüfen) |
| 10 | Wie 9, aber Handy im Flugmodus neu starten, entsperren, Flugmodus aus | wie 9 | nichts gesendet → persistierter Job fehlt (F-S5Z-04) |
| 11 | Wie 9, dann App öffnen und „Jetzt senden" | Ergebniszeile „Gesendet · HH:MM", „Alles gesendet" | „Keine Verbindung" trotz Netz |
| 12 | Dienst am Handy beginnen, an der Uhr beenden, Handy in der Tasche | Dauermeldung verschwindet binnen Sekunden; GPS-Symbol der Statusleiste erlischt; Web zeigt das Ende binnen einer Minute | Meldung bleibt, GPS-Symbol bleibt (B-S5Z-04); Ende erst nach 15 min |
| 13 | Standort aus, Dienst **an der Uhr** beginnen (nach F-S5Z-01) | Empfehlung (a): Dienst läuft, Handy vibriert, Zeile Rot; Uhr (E3) „keine Ortung" | Uhr zeigt „Dienst läuft" ohne Hinweis; oder Handy schweigt |
| 14 | Im Dienst Flugmodus 20 min an, dann aus | binnen 60 s ein Sendelauf (Logcat), nicht erst beim Takt | Lauf erst nach bis zu 15 min |
| 15 | *(E3, Wear-OS-Uhr)* Punkt 4 mit Blick auf die Uhr | „keine Ortung · keine Aufzeichnung" in Rot binnen Sekunden; nach Punkt 5 verschwindet sie | Uhr bleibt stumm (Standmeldung kommt nicht an — Zustellung prüfen) |
| 16 | *(wenn ein Android-8/9/10-Gerät greifbar ist)* Punkt 4 dort | wie 4, kein Absturz | `AbstractMethodError` im Logcat — belegt B-S5Z-01 rückwirkend für 0.7.7 |
| 17 | Zwölf-Stunden-Dienst (Prüfliste S4, Punkt 4.6) mit E1/E2 | Wächter und Takt laufen durch; Akkuverbrauch notieren (Backlog 82) | Dienst abgeräumt (Samsung „Apps im Tiefschlaf") |

### 9.3 Nicht prüfbar aus dem Container — steht im Prüfdokument an erster Stelle

Alles in 9.2. Der Emulator hat kein GPS (er kann Positionen **einspielen**,
aber weder Kaltstart noch Signalverlust noch Streuung nachstellen), keine
Vibration, kein Wear-Telefon, und `JobScheduler`-Zeiten unter Doze sind dort
nicht die des S24. Was der Emulator **kann** und die Umsetzung nutzen soll:
`adb emu geo fix` für einen einzelnen Fund (belegt `SUCHT` → `OK`), das
Abschalten des Standorts über die Einstellungen (belegt `STANDORT_AUS` und den
Rückruf), `adb shell cmd jobscheduler run -f org.genem.nadoku <id>` für einen
erzwungenen Joblauf, und `adb shell dumpsys notification` für die drei
Meldungs-IDs.

---

## 10. Was sich aus dem Repositorium nicht ermitteln ließ — und wie es die Umsetzung belegt

| Offen | Warum nicht ermittelbar | Beleg in der Umsetzung |
|---|---|---|
| Ob B-S5Z-01 auf einem echten Android 8–10 auftritt | abgeleitet aus der Plattform-API (`LocationListener` hat dort vier abstrakte Methoden; das SAM-Lambda setzt eine um); kein solches Gerät | 9.2 Punkt 16, sonst der Reflexionsfall als Schutz |
| Ob `requestLocationUpdates` bei ausgeschaltetem Anbieter sofort `onProviderDisabled` liefert | Plattformverhalten je Fassung verschieden dokumentiert | E1 verlässt sich nicht darauf: `isProviderEnabled` wird beim Start selbst gelesen; der Rückruf ist Zusatz |
| Die Ist-Zeiten für Erstfix und Signalverlust auf dem S24 | kein GPS im Container | 9.2 Punkte 3 und 6, Zeiten ins Prüfdokument (F-S5Z-02) |
| Ob „Nicht stören" die Vibration des Kanals `warnungen` unterdrückt | Nutzereinstellung, nicht nachstellbar | 9.2 Punkt 4 einmal mit „Nicht stören"; Grenze ins Handbuch/LIESMICH |
| Wie schnell der Nachsende-Job unter Doze und Samsungs Akkusteuerung läuft | nur das Gerät | 9.2 Punkte 9 und 10, Zeiten notieren |
| Ob ein Dienststart von der Uhr bei **geschlossener** App den Vordergrunddienst starten darf (Android 12+ untersagt `startForegroundService` aus dem Hintergrund außer in Ausnahmen; `ForegroundServiceStartNotAllowedException`) | seit C2 „nicht belegt" (`HandyHorcher.kt` 25); Data Layer nicht im Container | Gerätetest mit Uhr; ist es untersagt, ist das ein eigener Fund (B-S5Z-08) und kein Teil von E |
| Der exakte Wortlaut „Aufzeichnung laufend", den der Auftraggeber sah | nicht im Repositorium | F-S5Z-06 |
| Ob der Sendelauf beim Wegwischen der App heute tatsächlich abgebrochen wird | Prozessverwaltung des Herstellers | 9.2 Punkt 8 auf 0.7.7 **vor** E2 fahren, wenn die Diagnose es hergibt — sonst bleibt Kette B1 abgeleitet |

---

## 11. Fehlerfunde am Bestand (B-S5Z, K4 — sammeln, hier beheben, wo E sie ohnehin anfasst)

| Nr. | Fund | Fundstelle | Behandlung |
|---|---|---|---|
| **B-S5Z-01** | Der `LocationListener` ist ein SAM-Lambda; auf API 26–29 fehlen drei Methoden zur Laufzeit → `AbstractMethodError`, sobald das System `onProviderDisabled`/`onStatusChanged` ruft. `minSdk = 26`. Abgeleitet, nicht beobachtet | `AufzeichnungsDienst.kt` 65 | E1 (E-S5Z-05) |
| **B-S5Z-02** | Der Kommentar in `ortungAnfordern()` verspricht eine Meldung „Ortung fehlt", die `meldung()` nicht kennt | `AufzeichnungsDienst.kt` 158–160, 230–240 | E1 |
| **B-S5Z-03** | Nach dem Dienstende postet der Sendefaden die Dauermeldung neu — als andauernde Meldung „Kein Dienst" ohne Dienst dahinter | `AufzeichnungsDienst.kt` 141, 194–202 | E2 (E-S5Z-07) |
| **B-S5Z-04** | Ein Dienstende von der Uhr beendet den Vordergrunddienst nicht: Ortung läuft weiter, kein DIENSTENDE-Lauf | `HandyHorcher.kt` 41 | E2 (E-S5Z-08) |
| **B-S5Z-05** | Auslöser `WIEDERVERBINDUNG` (E-S4-07) ist deklariert und nie gebaut; `ACCESS_NETWORK_STATE` deklariert und unbenutzt | `Sendetakt.kt` 43–44; `AndroidManifest.xml` 22 | E2 (E-S5Z-10) |
| **B-S5Z-06** | 400-Pakete sind unsichtbar: nicht im Rückstand, nicht in der Ansicht. E-S4-36 sagte „gehört zu D1"; D1 hat es nicht gebracht | `Puffer.kt` 454–459, 508–514; `DienstAnsicht.kt` 127–135 | E2 zeigt die Zahl (E-S5Z-12); Ausräumen ist Backlog-Kandidat |
| **B-S5Z-07** | „· GPS an" wird aus der **Freigabe** abgeleitet, nicht aus dem Zustand der Ortung | `DienstAnsicht.kt` 110–118; `strings.xml` 112 | E1 |
| **B-S5Z-08** | Dienststart von der Uhr bei geschlossener App: `startForegroundService` aus dem Hintergrund ist ab Android 12 beschränkt; ob der Weg über den `WearableListenerService` unter eine Ausnahme fällt, ist nicht belegt | `HandyHorcher.kt` 41 | Gerätetest; nicht Umfang von E |
| **B-S5Z-09** | Die Rückfrage „Dienst beenden?" verspricht „… und gesendet" ohne Einschränkung | `strings.xml` 110 | E2 (4.3) |
| **B-S5Z-10** | Backlog 82, Kandidat (c), nennt den Text „Aufzeichnung läuft seit … · GPS an" — E1 ersetzt ihn; der S4-Rest muss die neue Zeile kennen | `docs/Backlog.md` 979–998 | Vermerk beim Merge von E (eine Zeile im Backlog-Punkt) |
| **B-S5Z-11** | Sendeläufe können überlappen: Oberfläche (Phase, Abschluss) und Dienst (Takt, Dienstende) starten je einen eigenen Faden auf demselben Puffer; `Sender.chunkPunkte` ist ungeschützt. Harmlos nur dank Idempotenz des Servers | `AufzeichnungsDienst.kt` 131; `HauptActivity.kt` 341–343; `Sender.kt` 74–76 | E2 (E-S5Z-11) |
| **B-S5Z-12** | `TAKT_MS = 1000` wird als `minTimeMs` an `requestLocationUpdates` gegeben — das ist ein **Mindest**abstand, kein Takt. Der Kommentar („1-s-Abtastung wie die Uhr") ist deshalb halb richtig: Mehr als 1 Hz kommt nie, weniger jederzeit. Für den Wächter ist das der Grund, mit Fristen statt mit Zählungen zu arbeiten | `AufzeichnungsDienst.kt` 167–173, 265–266 | Kommentar in E1 berichtigen; kein Verhaltensfehler |
| **B-S5Z-13** | **Der orange Punkt der Zeile „Rückstand N Pakete" ist zu blass.** `marke_orange` (#FF8F1F) auf `marke_schnee` (#FFFCFA) erreicht **2,23 : 1**; für ein grafisches Objekt verlangt WCAG 1.4.11 **3,0**. Der Fehler stand nie in einer Zahl, weil `werkzeuge/kontraste.py` eine **feste Paarliste** führt und dieses Paar nicht enthält (B6.2 der Gegenlesung). Gefunden beim Nachrechnen aller Token gegen Schnee für E-S5Z-22 | `DienstAnsicht.kt` 132; `werkzeuge/kontraste.py` | E1 setzt den Punkt auf `marke_orange_tief` (4,32 : 1) und trägt das Paar in `kontraste.py` ein |
| **B-S5Z-14** | **Ein von der Uhr geführter Dienst sendet bis zum Dienstende gar nicht.** `taktStarten()` postet den 15-Minuten-Takt bei **jedem** `onStartCommand` neu, und `HandyHorcher` ruft `AufzeichnungsDienst.starten()` bei **jeder** Uhrnachricht — kommen Ereignisse dichter als 15 Minuten, läuft der Takt nie ab. Nebenbefund aus B1.1; er trifft die Aussage von Kette B Punkt 3 („gesendet wird beim nächsten 15-Minuten-Takt"), die dann **nicht** zutrifft | `AufzeichnungsDienst.kt` 83, 113–121; `HandyHorcher.kt` 41 | E1 (zusammen mit E-S5Z-23): Der Takt wird nur gestartet, wenn er nicht schon läuft |
| **B-S5Z-15** | **`marke_rot` als Schrift auf `marke_asphalt` trägt 4,12 : 1** und bleibt damit unter AA (4,5). Betroffen ist die bestehende Uhr-Zeile „wartet aufs Handy · keine Aufzeichnung" (`dienst_schwebt`), seit C1. Als **Fläche** mit weisser Schrift trägt dasselbe Rot 4,78 : 1 und ist richtig — der Unterschied stand nie in einer Zahl, weil das Paar in `kontraste.py` fehlte (dieselbe Lücke wie B-S5Z-13). Gefunden beim Nachrechnen aller Token gegen Asphalt | `UhrActivity.kt` 394–401; `werkzeuge/kontraste.py` | E1: `marke_rosa` (15,94 : 1) für beide Warnzeilen der Uhr; beide Paare in der Liste |
| **B-S5Z-16** | **Bei laufendem Einsatz mit Phasenknöpfen liegt der Knopf „Dienst beenden" unter der Faltkante.** Auf 800 dp Bildschirmhöhe sind von ihm **29 dp** sichtbar, in allen drei geprüften Breiten (360, 411, 600 dp) — der Rest ist nur mit Bildlauf erreichbar. Der Bildschirm rollt, es geht also nichts verloren; aber mit Handschuhen ist Schieben ein Bedienschritt mehr, und die beendende Handlung ist die, die unter Zeitdruck gesucht wird. Gefunden vom neuen `HandyBildTest` beim ersten ehrlichen Lauf | `DienstAnsicht.kt`, `Phasenteil.kt` | **Nicht in E1 behoben** — eine Kürzung der laufenden Ansicht ist eine Gestaltungsänderung und braucht eine Entscheidung. Der Bilderlauf benennt sie bei jedem Lauf; Kandidat für den S4-Rest |
| **B-S5Z-17** | **Auf der 192-dp-Uhr erscheint die unterste Zeile im Phasenmodus nie.** Die laufende Ansicht ist dort 221 dp hoch (im Fall selbst seit C1 vermerkt), die Verbindungszeile liegt darunter. Betroffen war damit **auch die bestehende Warnung `dienst_schwebt`** („wartet aufs Handy · keine Aufzeichnung") — die Aussage, für die E-S4-10 an der Oberfläche überhaupt gebaut wurde, fiel auf der engsten Uhr aus. Nachgewiesen, nicht vermutet: Drei Bilder mit drei verschiedenen Ortungszuständen waren byteweise gleich | `UhrActivity.kt` (Verbindungszeile) | E1 (E-S5Z-28): beide Aussagen wandern in die Zustandszeile oben |

---

## 12. Nicht Umfang

- Keine Änderung an `server/`, am JSON-Vertrag, an `schema.sql` — der
  Server sieht dieselben Nachrichten wie bisher, nur verlässlicher.
- Keine Änderung am Kopplungsmodul (`handy/…/kopplung/`), das der S4-Rest
  nach Abschnitt 1a neu schneidet; keine Änderung an der Garmin-Uhr.
- Keine Akku-Warnung (Backlog 82, S4-Rest) — aber die neue Zustandszeile
  ist die Stelle, an der Kandidat (c) später ansetzt (B-S5Z-10).
- Kein WorkManager, keine Hintergrund-Ortungsfreigabe
  (`ACCESS_BACKGROUND_LOCATION`), kein zweiter Dienststyp.
- Keine Änderung an der Ausdünnung (die Zahlen aus B3 bleiben; `brauchbar()`
  ist dieselbe Regel an einer Stelle).
- Kein Weg, abgewiesene Pakete auszuräumen (Backlog-Kandidat).
- Kein Erzwingen der Akku-Freistellung (E-S4-52 bleibt).

---

## 13. Übergabe an die Umsetzung

1. F-S5Z-01 bis F-S5Z-06 entscheiden lassen (Freigabe dieses Zusatzes),
   Ergebnisse als E-S5Z-15 ff. eintragen.
2. Dokument neben das S5-Konzept legen
   (`docs/konzepte/Konzept-S5-Zusatz-Android-Ortung-Dienstende.md`); im
   S5-Konzept Statusblock und Abschnitt 9 ergänzen (Abschnitt 0, Punkt 2);
   im Prüfdokument S5 den Abschnitt „Paket E" mit der Liste aus 9.2 anlegen.
3. Diagnose nach 1.3 am S24, Ergebnis in Abschnitt 8 („Diagnose 1.3").
4. E1, dann E2, dann E3 — je Paket Fassung hochstufen, Changelog
   (F-S5Z-05), `android/LIESMICH.md` (Baulaufzahlen, Abschnitt 7 „Was hier
   nicht geprüft werden kann": Vibration, Wächterfristen, Job-Zeiten),
   `docs/Technik.md` 5a (Absatz „Ortungswächter und Nachsenden"),
   `docs/Geraete-Eingabe.md` 7.3 (E3), Backlog-Kandidaten notieren, Wortliste
   d, Statusblock, Push.
5. Prüfmittel zuletzt; Gerätetest nach 9.2 mit signiertem APK; Zahlen und
   Zeiten ins Prüfdokument, das Nicht-Prüfbare an erste Stelle.
6. Beim Merge von E: `docs/Rahmenplan.md` Schritt 5 um „Paket E (Android:
   Ortung und Dienstende)" ergänzen, Abschnitt 4 um die Zeile „E zu S4-Rest
   (`HauptActivity.kt`, `strings.xml`, Manifest) — E zuerst", Backlog 82 um
   den Vermerk B-S5Z-10; Backlog-Nummern per `uniq -d` prüfen.
7. Nach der Freigabe des S5-Abschlusses: R62 wie für das Hauptkonzept —
   die Erledigt-Zeile nennt die Android-Fassungen von E und die Zahlen aus
   9.1; dieses Dokument wird mit dem Hauptkonzept gelöscht.
