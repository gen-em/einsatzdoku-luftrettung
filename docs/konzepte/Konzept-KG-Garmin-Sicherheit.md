# Konzept KG — Garmin-Teil des Sicherheitspakets

**Schritt 28 des Rahmenplans, Garmin-Anteil (E-KA-21).** Der Auftrag steht
in `docs/konzepte/Konzept-KA-Sicherheitspaket.md` Abschnitt 11; die Befunde
mit Fundstelle und Angriffsweg in `docs/konzepte/Audit-KA-Sicherheit-Krypto.md`.
Dieses Konzept führt den Stand der Instanz KG. Es legt keine Versionsnummer
fest; die Stufe je Paket steht da, die Nummer setzt die Umsetzung.

Dazu, auf Wunsch der BetreiberIn vom 10.11.2026, der **Umbau der
Reanimation auf der Garmin-Uhr** (KG-05) — kein Sicherheitsthema, aber
derselbe Code und dieselbe Instanz.

## Statusblock

| | |
|---|---|
| Stand | **Umsetzung** seit 10.11.2026, Zweig `claude/vigilant-cray-9riw34`, Backlog-Spanne 570–579 |
| In Arbeit | **KG-05** — fertig, Uhr 4.5.0, im selben Pull Request #168 wie KG-01 (E-KG-20); wartet auf Merge und Tag `uhr-v4.5.0`. Danach KG-02 |
| Erledigt | **KG-01** (Uhr 4.4.0, PR #168, noch nicht gemergt) |
| Hakt | nichts. Außerhalb der Grenzen berührt: `tools/pruefstand/pruefablauf.json` und die erzeugte Tabelle in `docs/Pruefablauf.md` 4 (F-KG-05) |
| Gelesen gegen | Commit `0be2092` · Uhr 4.3.3; die vier Befunde von KG-01 am 10.11.2026 an der heutigen Stelle bestätigt (Abschnitt 4) |

**Grenzen (Auftrag):** KG fasst `watch/`, `tools/uhr-pruefstand/`, die
Garmin-Abschnitte von `docs/Handbuch.md`, `docs/Technik.md`,
`docs/Geraete-Eingabe.md` und die Garmin-Zeilen von
`docs/Bedrohungsmodell.md` an — nicht `server/`, `android/`, `.github/`,
nicht den Rahmenplan. Eine nötige Serveränderung geht als Auftrag an die
BetreiberIn, die KA-Instanz baut sie.

---

## 1. Arbeitspakete

| Paket | Inhalt | Befunde | Hängt an | Modell | Fächerung | Stufe Uhr |
|---|---|---|---|---|---|---|
| KG-01 | Kontobindung und Sendeweg (Abschnitt 4) | F-KA-05, -07 (Uhr), -18 (Uhr), -43 (Huelle.mc, Pair.mc) | nichts | Opus | keine | Neben |
| KG-02 | Messung für KA-06a: hält `Toybox.Cryptography` ein statisches P-256-Paar dauerhaft (Neustart, App-Update)? Gibt es ECDSA, was kostet es an Zeit und Speicher? Ergebnis als Befund mit Zahlen | — | nichts | Opus | keine | keine (Messung) |
| KG-03 | Garmin-Teil von KA-06c: Geräteschlüssel, Gerätehülle Fassung 2, Kopplung mit Vergleichswert | F-KA-04, -12, -17 (Garmin) | KA-06a fertig, KA-M5 freigegeben | **Fable** für den Krypto-Kern — vorher anhalten | keine | Haupt (offen) |
| KG-04 | Prüfliste für die Gerätetests: Übergabe einer echten Uhr im laufenden Dienst, Kopplung mit Vergleichswert | — | KG-01, KG-03 | — | keine | keine |
| KG-05 | Umbau der Reanimation: Tastenbelegung, 10-s-Sperre, Vibrationen, Fehler kurz UP/DOWN (Abschnitt 5) | F-KG-01, F-KG-02, F-KG-03 | KG-01 (Reihenfolge, nicht Inhalt) | Opus | keine | Neben |

Ein Pull Request je Paket. Nach jedem Paket: Konzept fortschreiben,
Prüfstand fahren, mit dem Prüfbericht committen, pushen, PR öffnen,
abonnieren, anhalten.

---

## 2. Entscheidungen

| Nr. | Entscheidung | Wann, wer |
|---|---|---|
| E-KG-01 | KG-01 wie in Abschnitt 4 zusammengefasst | 10.11.2026, BetreiberIn |
| E-KG-02 | Der Umbau der Reanimation wird ein eigenes Paket KG-05 nach KG-01, mit eigenem PR | 10.11.2026, BetreiberIn |
| E-KG-03 | **kurz START** auf der Rea-Seite: ohne Rea → Rea beginnen; mit Rea → Rhythmuskontrolle **und** Countdown neu. Rea beginnen bleibt auf kurz START („muss einfach sein") | 10.11.2026, BetreiberIn |
| E-KG-04 | **lang START** → Untermenü. **UP** → Adrenalin. **DOWN** → Defibrillation **und** Countdown neu | 10.11.2026, BetreiberIn |
| E-KG-05 | **Kurz und lang UP/DOWN tun während einer Rea dasselbe** (Möglichkeit A). Die Uhr bucht beim Drücken, die Druckdauer zählt nicht. Abgewogen gegen „kurz ohne Funktion" (Fehldruck der linken Tasten beim Abwinkeln des Handgelenks); die BetreiberIn wählt die einfachere Regel | 10.11.2026, BetreiberIn |
| E-KG-06 | **10-s-Sperre:** dasselbe Ereignis wird innerhalb von 10 s nicht noch einmal gebucht — über Tasten **und** Menü, auch unmittelbar nach dem Beginn der Rea. Ein anderes Ereignis geht sofort | 10.11.2026, BetreiberIn |
| E-KG-07 | **„Timer neu starten" (Countdown neu ohne Eintrag) entfällt** — als Taste und im Menü | 10.11.2026, BetreiberIn |
| E-KG-08 | **Uhren ohne UP/DOWN** (Venu): Adrenalin und Defibrillation nur über das Menü — hingenommen | 10.11.2026, BetreiberIn |
| E-KG-09 | **Während einer laufenden oder pausierten Rea verlässt nur BACK die Rea-Seite**; kurz UP/DOWN und Wischen blättern dort nicht. Ohne Rea blättert die Seite wie bisher | 10.11.2026, BetreiberIn |
| E-KG-10 | **BACK ohne Bestätigungsdialog**, Ziel wie heute die Hauptanzeige. Zurück mit einem Druck UP (Seitenfolge läuft um). Ein Dialog läge auf START und wäre auf Touchgeräten per Tippen bestätigbar | 10.11.2026, BetreiberIn |
| E-KG-11 | **Countdown-Erinnerungen:** bei 0:30 1 × 2 s, bei 0:10 2 × 2 s, bei 0:00 3 × 2 s; Pause zwischen den Pulsen 0,6 s; jedes Muster in **einem** Aufruf. Ein Neustart des Countdowns verwirft ausstehende Erinnerungen; in der Pause gibt es keine | 10.11.2026, BetreiberIn |
| E-KG-12 | **Bestätigungen kurz, nach einer Regel:** Ereignis **ohne** Countdown-Neustart → 1 kurzer Puls; **mit** Countdown-Neustart (Rea beginnen, Rhythmuskontrolle, Defibrillation, Rea fortsetzen) → 2 kurze Pulse. Menü öffnen, Pause, Rea beenden → keine. Lang (2 s) gehört allein dem Countdown | 10.11.2026, BetreiberIn |
| E-KG-13 | **Alle Vibrationen im Rea-Modus mit 100 % Stärke** | 10.11.2026, BetreiberIn |
| E-KG-14 | **Ein Druck, den die 10-s-Sperre abweist, gibt kein Zeichen** (Antwort auf Q-KG-01). Ein zweites kurzes Muster neben „1 kurz" wäre unter Stress und mit Handschuh kaum zu unterscheiden | 10.11.2026, BetreiberIn |
| E-KG-15 | **KG-01 hebt die Uhr auf 4.4.0** (Nebenstufe, wie im Auftrag), obwohl es Behebungen sind: Das Koppeln im Dienst ist ein veränderter Bedienweg | 10.11.2026, BetreiberIn (Auftrag) |
| E-KG-17 | **UP und DOWN buchen beim Loslassen oder nach 1 s Halten, nicht beim Drücken.** Für die Bedienung gleich (jede Druckdauer bucht, E-KG-05); beim Drücken buchte die Tastensperre der Uhr, die mit UP beginnt, jedes Mal ein Adrenalin. Abweichung vom zuerst Gesagten („beim Drücken"), der BetreiberIn gemeldet | 11.11.2026, Umsetzung |
| E-KG-18 | **Das Rea-Menü öffnet auf Amiodaron** — Rhythmuskontrolle, Defibrillation und Adrenalin liegen auf Tasten | 11.11.2026, BetreiberIn |
| E-KG-19 | **In der Pause:** kurz START öffnet die Übersicht (fortsetzen oder beenden), UP und DOWN buchen nichts, lang START öffnet das Menü | 11.11.2026, BetreiberIn |
| E-KG-20 | **KG-05 kommt in denselben Pull Request wie KG-01** (#168), statt auf dessen Merge zu warten — ein eigener PR vom selben Zweig wäre nicht möglich gewesen | 11.11.2026, BetreiberIn |
| E-KG-16 | **Die Probe zu KG-01 läuft ohne Server** (`tools/uhr-pruefstand/sendeweg/`): Die Prüffassung hält jede Anfrage vor dem Absenden an und spielt die Antworten selbst vor. Ein verlorenes Paket und eine falsche Quittung lassen sich mit der echten Anlage nicht gezielt erzeugen; was `ingest.php` daraus macht, misst weiter `uhr-rundlauf` | 10.11.2026, Umsetzung |

---

## 3. Fragen

| Nr. | Frage | Stand |
|---|---|---|
| Q-KG-01 | Ein Druck, den die 10-s-Sperre abweist: gar kein Zeichen oder ein eigenes (z. B. sehr kurzer Puls)? | beantwortet 10.11.2026 → E-KG-14 |

---

## 4. KG-01 — Kontobindung und Sendeweg

Bestätigt am 10.11.2026 an der heutigen Stelle (Commit `0be2092`, Uhr 4.3.3):

| Punkt | Befund | Heutige Stelle | Was gebaut wird |
|---|---|---|---|
| 1 | F-KA-05 | `Pair.mc` `start()`: Die Sperre „Erst Dienst beenden" steht nur im Zweig „Rückstand, angehalten, ungebunden"; ohne abgeschlossenen Rückstand fällt der Weg bei laufendem Dienst durch | `start()` weist bei `Model.serviceActive` immer ab |
| 2 | F-KA-05 | `Uploader.mc` `_findJob()`: laufendes Ruhestück und laufender Einsatz ohne `istFremd`-Prüfung | Bindung deckt auch die laufenden Pakete, auch wenn die Trennung auf anderem Weg entsteht (`cred` gelöscht) |
| 3 | F-KA-18 (Uhr) | `Uploader.mc` `onResponse()`: `next_seq` ungeprüft übernommen | höchstens `seq_from` + gesendete Punkte |
| 4 | F-KA-07 (Uhr) | `Uploader.mc` `_send()`: Stückgröße = offene Punkte, wächst zwischen zwei Versuchen | Größe eines unbestätigten Stücks merken, identisch wiederholen |
| 5 | F-KA-43 | `Huelle.mc` Kommentar „Liegt er auf der Kurve?" gegen Bedrohungsmodell (F-S11-85); `Pair.mc:17-19` „wer ihn über die Schulter sieht, kann nichts auslösen" | Kommentare berichtigen |

**Gebaut (10.11.2026, Uhr 4.4.0):**

1. `Pair.start()`: erst `schluesselHolen()` (nur gekoppelt, nicht angehalten,
   ohne Schlüssel — dasselbe Konto, auch im Dienst erlaubt), dann **im Dienst
   immer** „Erst Dienst beenden", dann erst `sitzungStarten()` bzw. Trennen.
   Die alte Sperre im Zweig „gebunden trennen" ist damit überflüssig und fort.
   Eine Kopplung, die *vor* Dienstbeginn startet und *im* Dienst bestätigt
   wird, ist nicht erreichbar: Die Kopplungsansicht liegt oben auf dem Stapel,
   den Dienst beginnt nur der Startbildschirm darunter — gelesen, nicht
   gemessen.
2. `Uploader.binden()` nimmt die laufenden Referenzen mit (`laufendeRefs()`);
   `lokalTrennen()` bindet auch, wenn nur ein laufendes Paket Ungesendetes hat
   (`laufendOffen()`); `_findJob()` prüft laufende Pakete auf `istGeparkt` und
   `istFremd`; `fremdeVerwerfen()` verwirft ein laufendes Paket nie und lässt
   seine Bindung stehen — sonst ginge es nach Fristablauf, sobald es
   abgeschlossen ist, an die jetzige Kopplung.
3. `onResponse()`: `next_seq` höchstens `seq` + `n` aus `_inflight`; ohne
   Zahl „Antwort ungültig", nichts quittiert.
4. `_send()`: Anfang und Größe des gesendeten Stücks in `Storage 'stk_<ref>'`;
   gleicher Anfang → gleiche Größe; jede 200 und `verwerfen()` löschen die
   Marke.
5. Kommentare `Huelle.schluesselSetzen()` und Kopf von `Pair.mc` berichtigt;
   **derselbe falsche Satz stand im Handbuch 13** („er kann an deiner Uhr
   nichts auslösen") und ist mit berichtigt (F-KG-06).

Dokumente: Changelog (Uhr 4.4.0), Handbuch 1 und 13.1, Technik.md
(`Uploader.mc`, `Pair.mc`), Bedrohungsmodell (Garmin-Zeile, R24 Garmin-Teil),
`tools/uhr-pruefstand/LIESMICH.md`, Backlog Nr. 570 (angelegt und erledigt).

---

## 5. KG-05 — Umbau der Reanimation

### 5.1 Heute (Uhr 4.3.3)

Rea-Seite: kurz START beginnt bzw. öffnet das Menü; lang START startet den
Countdown neu; lang UP Adrenalin, lang DOWN Rhythmuskontrolle; kurz UP/DOWN
blättern. Menü (endlos, öffnet auf „Timer neu starten"): Timer neu starten,
Rhythmuskontrolle, Defibrillation, Adrenalin, Amiodaron, Zugang,
Intubation, Sonographie, ROSC, Tod, Rea BEENDEN (Pause → Übersicht),
Übersicht. Vibrationen: Bestätigung 0,2 s (80 %) bzw. 2 × 0,3 s (75 %);
Zyklusende 3 × 0,3 s (90 %) und 2 s später 2 × 0,3 s (75 %) in einem
zweiten Aufruf.

### 5.2 Danach (gebaut 11.11.2026, Uhr 4.5.0)

| Bedienung (Rea läuft) | Wirkung | Vibration |
|---|---|---|
| kurz START | Rhythmuskontrolle + Countdown neu | 2 kurz |
| lang START | Untermenü | keine |
| UP (kurz oder lang) | Adrenalin | 1 kurz |
| DOWN (kurz oder lang) | Defibrillation + Countdown neu | 2 kurz |
| BACK | Hauptanzeige, Rea läuft weiter | keine |
| Menü: übrige Ereignisse | Zeitstempel | 1 kurz |
| Menü: Rea BEENDEN / Übersicht | wie heute | keine |
| 0:30 / 0:10 / 0:00 | Erinnerung | 1 / 2 / 3 × 2 s |

Ohne Rea: kurz START beginnt (2 kurz), UP/DOWN blättern. Pausiert
(E-KG-19): kurz START Übersicht, lang START Menü, UP/DOWN nichts. Alle
Vibrationen 100 %. Die 10-s-Sperre gilt je Ereignis über alle Wege.
Die Schnittstelle zum Server ändert sich nicht: dieselben Ereignistypen,
Pause und Ende werden weiterhin nicht übertragen.

### 5.3 Wie gebaut

- `Cpr.buchen(type)`: der eine Weg für Tasten und Menü — Sperre je Art
  (`SPERRE_MS` 10 000, `System.getTimer()`, nur im Speicher), Neustart bei
  Rhythmuskontrolle und Defibrillation, Muster. `start()` setzt die Sperre
  der Rhythmuskontrolle gleich mit. `restartCycle()` vibriert nicht mehr.
  Erinnerungen im Tick; `restore()` holt keine verpasste nach.
- `Util.reaKurz(n)`, `Util.reaLang(n)`: 0,2 s/0,2 s und 2 s/0,6 s, 100 %,
  ein Aufruf. `Model.resusEvent()` vibriert nicht mehr selbst.
- `ActionDelegate`: `actUpKurz()`/`actDownKurz()` (Vorgabe blättern),
  `halteAbbrechen()`, `langNachLoslassen()`. Die übrigen Seiten verhalten
  sich wie vorher.
- `CprDelegate`: Belegung nach 5.2; `actPagePrev/Next` (Behaviors, auch
  Wischen) blättern nur ohne Rea. `CprMenuView`: ohne „Timer neu starten",
  `START_INDEX` 3.
- Dokumente: Handbuch 2.0 und 2.3, Technik.md 5.1 und Dateitabelle,
  Geräte-Eingabe 2 und 6, Changelog, Backlog Nr. 571 (angelegt und erledigt).
  F-KG-03 ist mit dem neuen Abschnitt 2.3 erledigt (Ring in der Pause blau,
  keine fünf Pulse mehr).

**Gerätetests (für KG-04):** Fenix 6 Pro — (1) kurz UP/DOWN während einer
Rea: kein Seitenwechsel, genau ein Eintrag (F-KG-01); (2) Rhythmuskontrolle
und Defibrillation: zwei kurze Pulse spürbar; (3) 2-s-Pulse nicht gekürzt,
1/2/3 unterscheidbar; (4) Erinnerungen bei 0:30/0:10/0:00 auch bei App im
Hintergrund; (5) lang START öffnet das Menü auf Amiodaron, ohne etwas zu
buchen; (6) Tastensperre während einer Rea bucht nichts; (7) Venu: lang
Action und lang Zurück öffnen das Menü.

---

## 6. Befunde der Umsetzung

| Nr. | Befund | Beleg | Stand |
|---|---|---|---|
| F-KG-01 | **Kurz UP/DOWN auf der Rea-Seite bucht ein Ereignis.** Das System blättert beim Drücken, das Loslassen erreicht die neue Seite, und der Halte-Timer der Rea-Seite (1 s) läuft weiter und meldet einen „langen Druck": UP → Adrenalin, DOWN → Rhythmuskontrolle, etwa 1 s nach dem Seitenwechsel | Fenix 6 Pro, BetreiberIn, 10.11.2026 (Vibration ~1 s nach dem Wechsel, Eintrag in der Übersicht). Im Simulator nicht nachstellbar (Drücken und Loslassen in derselben Millisekunde, `Input.mc`) | → KG-05 |
| F-KG-02 | **Bei Rhythmuskontrolle und Defibrillation ist der kurze Bestätigungspuls nicht spürbar** — der unmittelbar folgende Doppelpuls des Countdown-Neustarts bricht ihn ab | Fenix 6 Pro, BetreiberIn, 10.11.2026 | → KG-05 (E-KG-12: ein Muster je Bedienung, ein Aufruf) |
| F-KG-03 | **Kommentar und Handbuch stimmen beim Zyklusende nicht:** `Util.mc` nennt Teil 2 „einen Tick später" (es sind zwei Ticks, ~2 s); das Handbuch nennt alle fünf Pulse „kräftig" (Pulse 4 und 5 haben 75 % statt 90 %); das Handbuch nennt den Ring in der Pause gelb (Code: blau) | Lesen, 10.11.2026 | → KG-05 |
| F-KG-04 | **Die App vibriert auch, wenn sie nicht im Vordergrund ist** (Countdown-Ende) | Fenix 6 Pro, BetreiberIn, 10.11.2026 | eingetragen in `docs/Geraete-Eingabe.md` 2 (10.11.2026) |
| F-KG-05 | **Eine neue Uhr-Probe braucht zwei Dateien außerhalb der Grenzen von KG:** ihre Zeile in `tools/pruefstand/pruefablauf.json` und die daraus erzeugte Tabelle in `docs/Pruefablauf.md` 4 — ohne sie läuft die Probe nie, und `bestand` wäre rot (`Pruefablauf.md` 6.12). Berührt mit der kleinsten Änderung (ein Eintrag, ein Name im Muster `uhr`); der BetreiberIn gemeldet | 10.11.2026 | KG-01 |
| F-KG-06 | **Handbuch 13 trug denselben falschen Satz wie `Pair.mc`** (F-KA-43): ein abgeschwatzter Code löse an der Uhr nichts aus. Wer ihn hat, trägt ihn im eigenen Konto ein, und die Uhr fragt mit dessen Adresse — das zweite Tor hält nur, wenn die Trägerin liest | Lesen, 10.11.2026 | berichtigt in KG-01 |
| F-KG-08 | **Das Loslassen eines langen START ging an das eben geöffnete Rea-Menü und wählte dort den Eintrag unter dem Cursor** (Simulator: Amiodaron gebucht). Das Menü öffnet deshalb erst beim Loslassen, und die Seite schluckt es selbst (`langNachLoslassen()`). **Das Schnellmenü der Hauptanzeige öffnet nach demselben Muster** auf dem langen START — ob es dort einen Eintrag wählt, ist nicht gemessen; am Gerät nicht gemeldet | Probe `rea`, 11.11.2026 | Rea-Menü behoben in KG-05; Schnellmenü offen |
| F-KG-09 | **Im Simulator blättert ein kurzer UP-Druck auf der Rea-Seite ohne Rea zwei Seiten** (Uhr 4.4.0: „REANAV 3, REANAV 2"): Behavior beim Drücken, das Loslassen blättert auf der neuen Seite noch einmal. Am Gerät nicht gemeldet; nicht behoben, weil es ohne Rea nichts bucht | Gegenprobe `rea`, 11.11.2026 | eingetragen in `Geraete-Eingabe.md` 6 |
| F-KG-07 | **Alle Fälle der Probe in einem Aufruf lösen den Watchdog aus** (mehrere Verschließungen am Stück, `Code Executed Too Long` in `Huelle.umgedreht()`). Die Probe läuft deshalb je Fall in einem eigenen Takt. Die App selbst verschließt je Antwort ein Stück und ist davon nicht betroffen — auf einer echten Uhr gemessen ist das aber nicht | Simulator fenix6pro, 10.11.2026 | Probe angepasst |

**Gerätebeobachtungen stehen in `docs/Geraete-Eingabe.md` 2 und 6**, nicht
nur hier — dieses Konzept wird am Ende der Phase gelöscht.

**Noch am Gerät zu messen (für KG-05):** ob die Fenix 6 Pro einen
Vibrationsabschnitt von 2 s am Stück hält oder still kürzt.

**Grenze der Probe zu F-KG-01:** `Geraete-Eingabe.md` 6 belegt, dass der
Simulator diese Art Fehler auch mit getrenntem `keydown`/`keyup` nicht
erzeugt. Eine Probe dort kann rot vor der Behebung nicht zeigen; Beleg ist
die Uhr (Gerätetest in KG-04/KG-05).

---

## 7. Prüfprotokoll

### KG-01 (10.11.2026, Uhr 4.4.0)

**Nicht geprüft:** keine echte Uhr — weder das Koppeln im Dienst noch ein
echter Funkabriss. Was `ingest.php` mit einem identisch wiederholten Stück
tut, hängt an der Server-Seite von F-KA-07 (KA-04 Punkt 3, KA-Instanz).

| Mittel | Was es misst | Ergebnis |
|---|---|---|
| `sendeweg.sh fenix6pro` gegen den Zweig | 8 Prüfungen in 7 Fällen (Simulator, ohne Server) | **8 von 8 grün** |
| `SENDEWEG_WATCH=<0be2092> sendeweg.sh` (Uhr 4.3.3) | dieselben 8 gegen den Stand vor der Behebung | **5 rot** (`koppeln-ungekoppelt-im-dienst` „Hole Code…", `koppeln-gekoppelt-im-dienst` Trennen-Rückfrage, `bindung-laufender-einsatz` fremd=false, `stueck-wiederholt-gleich` 0:200 dann 0:400, `next-seq-gedeckelt` 100000); **3 Gegenproben grün** (Schlüssel holen im Dienst, nächstes Stück nach Quittung, Antwort ohne Zahl) |
| `pruefstand.sh bauen fenix6pro` | Übersetzen der App | BUILD SUCCESSFUL |
| `pruefen.sh bestand`, `kettenaufrufe/pruefen.py`, `pruefen.sh --selbstprobe` | Form, Einhängen, Riegel | 0 Befunde · kein Widerspruch · 15 von 15 Selbstproben |
| `steuerung/decken.py`, `uebersicht.py`, `nummern.py` | Backlog-Form und -Nummern | 21 Decken, 0 gerissen · 0 ohne Grammatik · 0 Überschneidungen |
| Prüfstand `pruefen.sh` (Stufe klein, schmal) | Riegel und Proben der Berührung, darunter `uhr-stufe1` (99 Geräte), `uhr-rundlauf`, `uhr-syncseite`, `uhr-sendeweg` | Bericht in der Commit-Nachricht von KG-01; ein Vorlauf über denselben Code am 10.11.2026: 32 grün, 0 rot, 0 nicht gemessen, 1 627 s |

### KG-05 (11.11.2026, Uhr 4.5.0)

**Nicht geprüft:** keine echte Uhr — weder F-KG-01 (im Simulator nicht
erzeugbar, `Geraete-Eingabe.md` 6), noch wie sich die Muster anfühlen, noch
ob 2-s-Pulse gehalten werden; keine Venu (die Probe drückt UP/DOWN).

| Mittel | Was es misst | Ergebnis |
|---|---|---|
| `rea.sh fenix6pro` gegen den Zweig | 15 Tastenschritte, je die neuen Konsolenzeilen (Buchung, Muster mit Dauer und Stärke, Seitenwechsel, Menü) gegen das Soll | **15 von 15 grün** (dritter Lauf; die ersten beiden waren Fehler der Probe: Konsolenverzug, Erwartung der 0:30-Erinnerung) |
| `REA_WATCH=<HEAD vor KG-05> rea.sh` (Uhr 4.4.0) | dieselben 15 gegen den alten Stand | **14 rot**, nur BACK grün |
| `pruefen.sh bestand`, `kettenaufrufe`, `steuerung/*` | Form, Einhängen, Backlog | 0 Befunde · kein Widerspruch · 21 Decken, 0 gerissen · 0 ohne Grammatik |
| Prüfstand `pruefen.sh` | Stufe und Proben nach Berührung | Bericht in der Commit-Nachricht von KG-05 |
