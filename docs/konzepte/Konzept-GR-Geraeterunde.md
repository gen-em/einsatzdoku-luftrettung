# Konzept GR — Geräterunde (Fahrplanschritt 21)

**Kürzel:** GR. Pakete `GR-NN`, Entscheidungen `E-GR-NN`, Befunde
`F-GR-NN`, Fragen `Q-GR-NN`, Prüfpunkte `P-GR-NN` (`docs/Pruefablauf.md` 7).

**Zweig:** `claude/trusting-wozniak-33j782` · **Herkunft:**
`docs/konzepte/Vorbereitung-Backlog-Einordnung.md` 4, E-BE-03, -07, -08
(Betreiberin, 02.11.2026) · **Backlog-Spanne:** 470 bis 479 ·
**Prüfdokument:** `Pruefdokument-GR-Geraeterunde.md` (entsteht mit GR-07).

---

## Statusblock

| Paket | Inhalt | Stand |
|---|---|---|
| GR-01 | Vertrag: Fehlertabelle `ingest.php` (403, 429, 503, 507), Abschnitt 0 berichtigt | **erledigt** 02.11.2026 |
| GR-02 | Handy, Antwortauswertung: Nr. 154, 229 (Handy), 201 (Handy), 507 | **erledigt** 02.11.2026 (Android 0.19.0) |
| GR-03 | Handy, Kopplung: Nr. 157, Neukoppeln mit Schlüsselbindung (E-BE-08) | offen |
| GR-04 | Uhr, Antwortauswertung: Nr. 229, 201 (Uhr), 507, 431 Teil 1 | offen |
| GR-05 | Uhr, Kopplung: Nr. 431 Teil 2, Neukoppeln mit Schlüsselbindung (E-BE-08) | offen |
| GR-06 | Uhr, Sync-Seite: Nr. 402 mit Bildprobe | offen |
| GR-07 | Abschluss: Prüfstand, Prüfdokument, Backlog, Pull Request | offen |

**In Arbeit:** GR-03. **Es hakt:** nirgends. Der volle Android-Bau mit Lint läuft nach GR-03 (F-GR-08).

---

## 1. Auftrag

Sechs Client-Punkte aus dem Backlog (E-BE-03): 154, 157, 201, 229, 402,
431. Berührt werden `watch/` (Garmin) und `android/handy/`, dazu
`docs/JSON-Vertrag.md` für die Fehlertabelle von `ingest.php` (E-BE-07).
**Kein `server/`**: Schritt 22 (BR5) arbeitet dort nebenher (Rahmenplan 4).
Die Wear-OS-App (`android/uhr/`) wird nicht berührt; sie sendet über das
Handy und erbt dessen Verhalten.

Ausgeliefert wird je ein Tag `uhr-v…` und `android-v…` nach der Freigabe
(nicht Teil dieses Schritts; das Setzen eines Tags ist die Auslieferung,
CLAUDE.md 3).

**Nicht Teil:** die Warnung im Web beim Löschen eines Geräts mit Rückstand
(Nr. 440, Schritt 22, E-BE-08); jede Änderung am Server.

## 2. Befund

**F-GR-01 — Die Begründung von E-BE-08 ist falsch, der Entscheid nicht.**
E-BE-08 sagt, ein Rückstand für ein anderes Konto wäre dort „unlesbar".
Das stimmt nicht: Uhr und Handy halten den Puffer im **Klartext** und
verschließen erst beim Senden (`Uploader._send()` → `Huelle.spurstueck()`,
`Huelle.einsatz()`; `Sendekoerper.baue()`; Bedrohungsmodell G3 (3), Lage H).
Nach einer Kopplung an ein anderes Konto würde der Rückstand mit **dessen**
Schlüssel verschlossen — und wäre dort lesbar. Der Schlüsselvergleich ist
also nicht Komfort, sondern die Schranke gegen den Abfluss fremder
Patientendaten in ein fremdes Konto. Dasselbe sagt schon der Kommentar in
`Pair.start()` („fremde Einsätze in einem fremden Konto").

**F-GR-02 — Der Kontoschlüssel ist die Identität des Kontos.** Ein Konto hat
höchstens ein Schlüsselpaar, und es wird nie ersetzt: `kp_ablegen()` schreibt
nur bei `kp_oeffentlich IS NULL`, `api/kontopaar.php` antwortet sonst 409.
Der Schlüsselwechsel (`schluesselwechsel_lib.php`, Zweck `kontopaar`) hüllt
nur das **Siegel** um, nicht den Schlüssel. Gleicher Schlüssel heißt damit
gleiches Konto; ein Fehlalarm beim richtigen Konto ist ausgeschlossen. Die
Grenze C1 (wer den Server übernimmt, tauscht den Schlüssel) bleibt, wie sie
im Bedrohungsmodell steht.

**F-GR-03 — Die Uhr kann `Retry-After` nicht lesen.** Der Rückruf von
Connect IQ bekommt `(code, data)`, keine Kopfzeilen (`JSON-Vertrag.md` 5,
`ingest.php`-Kommentar zur Mengenbremse). Nr. 201 ist für die Uhr in der
Form des Backlogs nicht umsetzbar; → E-GR-02.

**F-GR-04 — Die Sync-Seite stößt alle zwei Sekunden an.** `SyncView.refresh()`
ruft im Zwei-Sekunden-Takt `Uploader.syncAll()`. Nach einer Störung (5xx,
429, 507) ist `_busy` falsch, und solange die Seite offen ist, klopft die
Uhr alle zwei Sekunden an — gerade bei der Mengenbremse das Gegenteil des
Gewollten. Nr. 201 schließt das mit (E-GR-02).

**F-GR-05 — Abschnitt 0 des Vertrags behauptet zu viel.** Die Zeile „401/403
halten den Upload an" sagt, das Trennen sei dann „nicht mehr gesperrt
(Backlog Nr. 159)". Nr. 159 hat nur **geparkte** Pakete aus dem Rückstand
genommen; ein gewöhnlicher Rückstand sperrt das Trennen weiter
(`Pair.start()`, `Kopplungsdienst.trennen()`). Das ist die Sackgasse aus
Nr. 157, 229 und 431.

**F-GR-06 — 507 ist nirgends behandelt.** `ingest.php` antwortet bei vollem
Konto `507 {"error":"kontingent","grund":"…"}`. Die Uhr zeigt „Upload 507"
und versucht es bei jedem Anlass erneut, das Handy führt es als „später
erneut". Der Vertrag kennt 507 nicht. Behebbar ist es nur im Web
(aufräumen oder Grenze heben); → Q-GR-05, E-GR-07.

**F-GR-08 — `ANDROID_HOME` ist im Container nicht gesetzt.** Nach
`aufbauen.sh android` bricht `./gradlew` mit „SDK location not found" ab;
das SDK liegt unter `/opt/android-sdk`. Mit `ANDROID_HOME=/opt/android-sdk`
läuft es. Ob der Prüfstand den Wert selbst setzt, zeigt GR-07.

**F-GR-07 — Das Handy kennt keinen Abmeldezustand.** Die Uhr merkt sich
die erste 401/403 (`Storage 'abgem_seit'`, Frist 30 Tage, E-R17-22); das
Handy hält nur den letzten Sendebericht im Speicher
(`NAdokuApp.letzterSendebericht`). Für E-BE-08 braucht das Handy einen
dauerhaften Abmeldevermerk (GR-03). Eine Frist für den Klartextpuffer des
abgemeldeten Handys (wie die Uhr) ist damit **nicht** gebaut — das wäre ein
eigener Punkt (Lage H); er wird beim Abschluss als Backlog-Punkt
vorgeschlagen, nicht nebenbei gebaut (K4).

## 3. Fragen an die Betreiberin

**Q-GR-01 — Schlüsselvergleich nach E-BE-08 beibehalten?** → **E-GR-01.**

**Q-GR-02 — Wartezeit auf der Uhr: feste Zeiten, Feld im Körper oder nur
Handy?** → **E-GR-02.**

**Q-GR-03 — Auf die Wear-OS-Uhr warten?** → **E-GR-03.**

**Q-GR-04 — Konzept und Umsetzung in dieser Sitzung?** → **E-GR-04.**

**Q-GR-05 — 507 (Konto voll): Wartezeit und eigene Meldung?** Vorschlag:
Uhr und Handy warten nach einer 507 **60 Minuten** bis zum nächsten Versuch
und zeigen „Konto voll — im Web aufräumen" (Uhr: Sync-Seite; Handy:
Ergebniszeile). Nichts wird verworfen, nichts geparkt. Begründung: Der
Zustand löst sich nur durch eine Handlung im Web; jeder Versuch davor kostet
Funk und Akku und bekommt dieselbe Antwort. Alternative: wie heute,
„später erneut" ohne eigenen Text. → **E-GR-07.**

**Q-GR-06 — Neukoppeln mit Rückstand nur außerhalb eines Dienstes?**
Vorschlag: ja. Während eines Dienstes liegen auch die laufenden Pakete
(laufender Einsatz, laufendes Ruhesegment) ungesendet auf dem Gerät; sie
stehen nicht in der Warteschlange abgeschlossener Pakete, die die
Schlüsselbindung verwirft oder freigibt. Außerhalb des Dienstes ist alles
abgeschlossen, und die Bindung deckt den ganzen Puffer. Die Sync-Seite
beziehungsweise die Einstellungen sagen dann „Erst Dienst beenden".
→ **E-GR-08.**

## 4. Entscheidungen

**E-GR-01 — Schlüsselvergleich, mit berichtigter Begründung (02.11.2026,
Betreiberin, Q-GR-01).** Es bleibt beim Vergleich des Kontoschlüssels aus
E-BE-08: Bei „abgemeldet" (401 oder 403) darf das Gerät trotz Rückstand neu
koppeln; der Rückstand bleibt an den **bisherigen** Kontoschlüssel gebunden.
Bringt die neue Kopplung denselben Schlüssel, geht er hinaus; einen anderen,
geht nichts hinaus, und das Gerät fragt, ob er verworfen wird. Der Grund ist
F-GR-01 (Abfluss fremder Daten), nicht Unlesbarkeit; nach F-GR-02 schlägt
der Vergleich beim richtigen Konto nie falsch an. Kein Serverbedarf: Die
Kopplungsantwort trägt den Schlüssel seit Web 22 (Vertrag 1a.3a).

*Bauform (beide Geräte gleich):* Beim Trennen mit Rückstand im Zustand
„abgemeldet" wird der bisherige Kontoschlüssel als **Bindung des
Rückstands** dauerhaft abgelegt (Uhr: `Storage`, Handy: Ablage außerhalb
des Tresors — der Schlüssel ist öffentlich). Jeder Sendelauf prüft vor dem
ersten Paket: Bindung vorhanden und gleich dem aktuellen Schlüssel → Bindung
löschen, senden; vorhanden und verschieden → nichts senden, Rückfrage
anbieten; keine Bindung → wie heute. Hatte die alte Kopplung keinen
Schlüssel (Kopplung von vor Uhr 4.0.0 / Android 0.18.0), ist die Bindung
„unbekannt" und wird wie „verschieden" behandelt — lieber eine Rückfrage zu
viel als ein Abfluss. Die Bindung übersteht Neustarts; sie geht mit dem
Verwerfen des Rückstands und mit dem erfolgreichen Senden. Die 30-Tage-Frist
der Uhr bleibt und läuft weiter (Lage H, E-R17-22).

**E-GR-02 — Feste Wartezeiten auf der Uhr (02.11.2026, Betreiberin,
Q-GR-02 (a)).** Die Uhr wartet je Antwort eine feste Zeit, bevor ein
**selbsttätiger** Anlass wieder sendet: 429 → 15 Minuten (erste Sprosse der
Sperrleiter, Vertrag 5), 503 → 5 Minuten (Wartung 300 s; die Verbindungsgrenze
nennt 5 bis 300 s). Die Wartezeit liegt im Speicher; ein Neustart darf es
früher versuchen (dieselbe Abwägung wie bei `abgemeldet`). Kein Feld im
Körper, keine Serveränderung. Das Handy liest `Retry-After` aus der
Kopfzeile (es kann es) und nimmt die feste Zeit nur, wenn die Kopfzeile
fehlt. Damit ist auch F-GR-04 behoben: Der Zwei-Sekunden-Takt der
Sync-Seite trifft auf die Wartezeit und sendet nicht.

**E-GR-03 — Nicht auf die Wear-OS-Uhr warten (02.11.2026, Betreiberin,
Q-GR-03).** GR berührt `android/uhr/` nicht. Abgenommen wird mit der
Garmin-Uhr und dem Handy; die Wear-OS-Gerätetests (Prüfliste AR, -11 bis
-14) bleiben in der Prüfliste und in Rahmenplan 6.2.

**E-GR-04 — Konzept und Umsetzung in dieser Sitzung (02.11.2026,
Betreiberin, Q-GR-04).** Kürzel GR, Zweig wie oben. Paket für Paket nach
CLAUDE.md 7; nach jedem Paket Statusblock, Commit, Push.

**E-GR-05 — 403 nach `grund` unterscheiden, die Frist bleibt.** Die Uhr
unterscheidet künftig **abgemeldet** (401: gelöscht oder Schlüssel falsch)
von **gesperrt** (403: `device_disabled` oder `konto` mit `grund`
`gesperrt`, `wartet`, `unbestaetigt`). Gesperrt nennt den Grund und **keinen**
Rat zum Neukoppeln — der Weg heraus ist das Web. Beide halten das Senden an,
beide beginnen die 30-Tage-Frist (die Begründung aus E-R17-22 gilt für den
Klartextpuffer gleich, gleich warum das Gerät nicht senden darf). Kommt der
Körper der 403 nicht durch (geräteabhängig, wie bei 400), heißt die Zeile
„Gerät oder Konto gesperrt". Das Neukoppeln nach E-GR-01 steht **beiden**
Zuständen offen; der Vergleich schützt in beiden Fällen gleich.

**E-GR-06 — Senden nach dem Reaktivieren (Nr. 431 Teil 1).** Das Öffnen der
Sync-Seite (einmal beim Erscheinen, nicht im Zwei-Sekunden-Takt) und das
Dienstende versuchen es **einmal** trotz der Marke. Kommt wieder 401/403,
bleibt alles, wie es war; kommt 200, ist die Marke widerlegt und der
Rückstand geht hinaus. Das Handy braucht das nicht: Es hat keine
Speichermarke, die das Senden sperrt; sein Nachsende-Job plant sich nach
einem 401 nicht neu, aber jeder Sendeanlass (Dienst, App-Start, Knopf)
versucht es.

**E-GR-07 — 507: 60 Minuten warten, eigener Text (02.11.2026,
Betreiberin, Q-GR-05).** Uhr und Handy warten nach einer 507 sechzig
Minuten bis zum nächsten selbsttätigen Versuch und zeigen „Konto voll —
im Web aufräumen" (Uhr: Sync-Seite; Handy: Ergebniszeile). Nichts wird
verworfen oder geparkt.

**E-GR-08 — Neukoppeln mit Rückstand nur außerhalb des Dienstes
(02.11.2026, Betreiberin, Q-GR-06).** Im Dienst bleibt das Trennen mit
Rückstand gesperrt, die Meldung heißt „Erst Dienst beenden". Damit deckt
die Bindung aus E-GR-01 den ganzen Puffer.

**Modellwahl:** Opus für alle Pakete (K2); kein Fable-Schritt.
**Fächerung:** keine. Uhr- und Handy-Pakete berühren getrennte Quelldateien,
aber gemeinsam Vertrag, Changelog, Konzept und `docs/Technik.md`
(erzählender Text, CLAUDE.md 7); die Prüfarbeit läuft an einem Prüfstand.

## 5. Arbeitspakete

Jedes Paket benennt den Ort jeder neuen Funktion (K1, R74).

### GR-01 Vertrag (E-BE-07, F-GR-05, F-GR-06)

*Weg:* `docs/JSON-Vertrag.md` 5, Fehlertabelle: Zeilen für
`403 {"error":"device_disabled"}`, `403 {"error":"konto","grund":…}` (drei
Gründe) und `507 {"error":"kontingent","grund":…}` mit dem, was der Server
tut. Abschnitt 0, Zeile „401/403 halten den Upload an": Nr. 159 auf das
reduzieren, was es ist (geparkte Pakete sperren nicht). Das **Verhalten der
Geräte** in diesen Zeilen schreiben GR-02 und GR-04 nach, wenn es gebaut ist.

*Ort:* nur der Vertrag. *Fassung:* keine (nur `docs/`, CLAUDE.md 2 (1)).

*Abnahme:* jede Antwort von `ingest.php` außer 200 hat eine Zeile mit Code,
Körper und Bedeutung; gegengelesen gegen `json_out(…)` in `ingest.php`.

### GR-02 Handy, Antwortauswertung (Nr. 154, 229 Handy, 201 Handy, F-GR-06)

*Weg:*
1. `Netzantwort.Server` trägt zusätzlich `retryAfterS: Long?` aus der
   Kopfzeile (`HttpNetzweg`); Vorgabe `null`, damit die Prüffälle
   unverändert bleiben.
2. `Sendeantwort.lese()`: `kept_points` und `kept_meta` in
   `uebergangen` (Nr. 154); 403 als neuer Fall `Gesperrt(grund)` (Nr. 229);
   `SpaeterErneut` trägt die Wartezeit (Kopfzeile, sonst fest nach E-GR-02);
   507 nach E-GR-07.
3. `Sender`: hält einen Zeitpunkt „nicht vor" im Speicher; ein Lauf davor
   sendet nicht und meldet „später erneut". 403 hält den Lauf an wie 401.
4. Anzeige: Ergebniszeile und Hinweis für „gesperrt" (mit Grund) und für die
   Wartezeit; `kept_*` erscheinen in der Zeile „nicht vollständig
   übernommen".

*Ort:* `senden/Sendeantwort.kt`, `senden/Sender.kt`, `kopplung/Netzweg.kt`,
die Anzeige in `HauptActivity.kt`/`Meldungen.kt`/`strings.xml`.

*Abnahme:* Prüffälle in `SendeantwortTest` (kept_points, kept_meta, 403 mit
jedem Grund, 403 ohne Körper, 429 mit und ohne Kopfzeile, 507) und
`Sender`-Prüffall für „nicht vor"; `gradle test` grün; Vertrag 5 nennt das
Verhalten des Handys.

### GR-03 Handy, Kopplung (Nr. 157, E-GR-01, F-GR-07)

*Weg:*
1. Ein dauerhafter **Abmeldevermerk** am Handy: gesetzt bei 401/403 im
   Sendelauf, gelöscht bei 200, beim Koppeln und beim Trennen.
2. `Kopplungsdienst.trennen()`: Rückstand sperrt nicht mehr, wenn der
   Vermerk steht (und nach E-GR-08 kein Dienst läuft); dann wird der
   bisherige Kontoschlüssel als Bindung abgelegt, der Rückstand bleibt.
   Ohne Vermerk bleibt `Trennergebnis.Rueckstand` wie heute.
3. `Sender.sendeAlles()`: Prüfung der Bindung vor dem ersten Paket (E-GR-01);
   neuer Berichtsfall „Rückstand gehört einem anderen Konto".
4. Anzeige mit Rückfrage „N Pakete des bisherigen Kontos verwerfen?" und
   Puffer-Weg zum Verwerfen des ganzen Rückstands.

*Ort:* `kopplung/Kopplungsdienst.kt`, eine neue kleine Klasse für Vermerk
und Bindung in `kopplung/` (Ablage in `SharedPreferences`, öffentlich),
`senden/Sender.kt`, `puffer/Puffer.kt` (Verwerfen), Anzeige wie GR-02.

*Abnahme:* Prüffälle in `KopplungTest` (Trennen mit Rückstand ohne Vermerk
→ verweigert; mit Vermerk → getrennt, Bindung abgelegt) und im Sender
(gleicher Schlüssel → gesendet, Bindung fort; anderer → nichts gesendet,
Bericht nennt es; keine Bindung → wie heute); Rundlauf gegen die örtliche
Anlage: Gerät im Web löschen, Rückstand, neu koppeln an dasselbe Konto →
Pakete kommen an.

### GR-04 Uhr, Antwortauswertung (Nr. 229, 201 Uhr, 431 Teil 1, F-GR-04, F-GR-06)

*Weg:*
1. `Uploader.onResponse()`: 401 → `abgemeldet`; 403 → `gesperrt` mit Grund
   aus dem Körper (E-GR-05); 429/503/507 → Wartezeit „nicht vor" im
   Speicher (E-GR-02, E-GR-07).
2. `Uploader.syncAll()` prüft Wartezeit und Marke; neuer Einstieg
   `syncAllEinmalTrotz()` (Name in der Umsetzung) für die beiden Anlässe aus
   E-GR-06: `SyncView.onShow()` und das Dienstende.
3. `SyncView`: drei Texte für gesperrt ohne Rat zum Neukoppeln; Zeile zur
   Wartezeit („Server bremst · ab HH:MM"); „Konto voll" nach E-GR-07.

*Ort:* `Uploader.mc`, `SyncView.mc`, `Model.mc` (Dienstende), `Pair.mc`
(`onSchluessel` behandelt 403 wie der Upload).

*Abnahme:* Bau für alle Zielgeräte ohne Warnung; Simulator gegen die
örtliche Anlage: Gerät deaktivieren → „Gerät deaktiviert" ohne
Neukoppeln-Rat; aktivieren, Sync-Seite öffnen → Pakete gehen ohne Neustart
hinaus (Abnahme aus Nr. 431); Konto sperren → Grund steht da. Was der
Simulator nicht zeigt (Körper einer 403 auf echter Hardware), steht im
Prüfdokument vorn.

### GR-05 Uhr, Kopplung (Nr. 431 Teil 2, E-GR-01)

*Weg:* `Pair.start()`: Bei `abgemeldet` oder `gesperrt` (und nach E-GR-08
außerhalb des Dienstes) sperrt der Rückstand das Trennen nicht; die
Rückfrage nennt die Zahl und sagt, dass die Pakete nur an dasselbe Konto
gehen. `Pair.lokalTrennen()` legt dann den bisherigen Schlüssel als Bindung
ab (`Storage`), statt den Rückstand zu verlieren. `Uploader.syncAll()` prüft
die Bindung (E-GR-01). `SyncView` zeigt bei verschiedener Bindung „N Pakete
anderes Konto" und bietet mit kurzem START das Verwerfen an (Rückfrage).

*Ort:* `Pair.mc`, `Uploader.mc`, `SyncView.mc`.

*Abnahme:* Simulator gegen die örtliche Anlage: Gerät im Web löschen,
Rückstand, neu koppeln an dasselbe Konto → dieselben Pakete kommen an
(Abnahme aus Nr. 431); an ein anderes Konto → nichts geht hinaus, Rückfrage,
nach „Ja" ist der Rückstand fort und im anderen Konto erscheint nichts.

### GR-06 Uhr, Sync-Seite (Nr. 402)

*Weg:* Der Mittelblock weicht bei Platzmangel aus: kleinere Zahl
beziehungsweise Schrift über `Ui.fitFont`, sodass er nie unter `untenY`
reicht; der falsche Kommentar („es ueberlappt nichts") wird berichtigt.
Die Zeilen aus GR-04 und GR-05 sind dabei mitgezählt.

*Ort:* `SyncView.mc`.

*Abnahme:* Bildprobe im Uhr-Prüfstand mit vier und fünf Zeilen im unteren
Block auf Fenix 6 Pro, FR 945 und Venu 3s; keine zwei Texte übereinander.

### GR-07 Abschluss

Prüfstand über den Endstand, Prüfdokument (CLAUDE.md 7), Backlog-Punkte
nach `Backlog-Erledigt.md`, Vorschlag für F-GR-07 als Backlog-Punkt, Pull
Request. Erledigt-Zeile und Löschung von Konzept und Prüfdokument erst nach
der Freigabe.

## 6. Prüfprotokoll

**GR-01** (02.11.2026): Gegengelesen gegen jeden `json_out(…)` mit Code
ungleich 200 in `server/ingest.php` — 13 Stellen (400 `payload` dreimal,
400 `klartext`, 401 zweimal, 403 `device_disabled`, 403 `konto`, 405, 413,
429, 500 `server`, 507 `kontingent`), dazu die 503 aus `wartung_lib.php`.
Jede hat eine Zeile in der Fehlertabelle (Vertrag 5); vorher fehlten
403 (zwei Formen) und 507. Keine Fassung, kein Changelog (nur `docs/`).

**GR-02** (02.11.2026, Android 0.19.0): `./gradlew :handy:testDebugUnitTest`
mit `--tests '*Sendeantwort*' '*SenderTest*' '*Nachsenden*'` —
**39 Fälle, 0 Fehlschläge, 0 übersprungen** (`SenderTest` 22,
`NachsendenTest` 9, `SendeantwortTest` 8). Neu sind sechs: `kept_points`
und `kept_meta`; 403 mit allen drei Kontogründen, `device_disabled`, ohne
Körper und mit HTML-Körper; Wartezeiten aus Kopfzeile, Vorgabe und Deckel;
403 im Sendelauf (pausiert, Grund, eine Anfrage, nichts markiert); 429 mit
`Retry-After: 600` (nach 599 s keine Anfrage, nach 600 s gesendet, Erfolg
hebt die Wartezeit auf); 507 (eine Stunde, „Konto voll" auch während des
Wartens). `PruefServer` kann dafür Antwortkopfzeilen und kennt 403, 503,
507. Nicht gemessen: die Anzeige (Compose) — sie bekommt den Ausgang fertig
entschieden; Bilderlauf und Lint mit GR-03.

## 7. Verlauf

| Datum | Was |
|---|---|
| 02.11.2026 | GR-02 erledigt (Android 0.19.0). |
| 02.11.2026 | Q-GR-05, -06 entschieden (E-GR-07, -08). |
| 02.11.2026 | Konzept angelegt; Q-GR-01 bis -04 entschieden (E-GR-01 bis -04); Spanne 470 bis 479 reserviert. |
