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
| In Arbeit | **KG-01** — bestätigt 10.11.2026 |
| Erledigt | — |
| Hakt | nichts. Offen: Q-KG-01 (Zeichen bei gesperrtem Druck, KG-05) |
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

---

## 3. Fragen

| Nr. | Frage | Stand |
|---|---|---|
| Q-KG-01 | Ein Druck, den die 10-s-Sperre abweist: gar kein Zeichen oder ein eigenes (z. B. sehr kurzer Puls)? Die Antwort „ja" vom 10.11.2026 ließ beides offen | offen, vor KG-05 |

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

Stand: bestätigt, Umsetzung beginnt.

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

### 5.2 Danach

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

Ohne Rea: kurz START beginnt (2 kurz), UP/DOWN blättern. Alle
Vibrationen 100 %. Die 10-s-Sperre gilt je Ereignis über alle Wege.
Die Schnittstelle zum Server ändert sich nicht: dieselben Ereignistypen,
Pause und Ende werden weiterhin nicht übertragen.

---

## 6. Befunde der Umsetzung

| Nr. | Befund | Beleg | Stand |
|---|---|---|---|
| F-KG-01 | **Kurz UP/DOWN auf der Rea-Seite bucht ein Ereignis.** Das System blättert beim Drücken, das Loslassen erreicht die neue Seite, und der Halte-Timer der Rea-Seite (1 s) läuft weiter und meldet einen „langen Druck": UP → Adrenalin, DOWN → Rhythmuskontrolle, etwa 1 s nach dem Seitenwechsel | Fenix 6 Pro, BetreiberIn, 10.11.2026 (Vibration ~1 s nach dem Wechsel, Eintrag in der Übersicht). Im Simulator nicht nachstellbar (Drücken und Loslassen in derselben Millisekunde, `Input.mc`) | → KG-05 |
| F-KG-02 | **Bei Rhythmuskontrolle und Defibrillation ist der kurze Bestätigungspuls nicht spürbar** — der unmittelbar folgende Doppelpuls des Countdown-Neustarts bricht ihn ab | Fenix 6 Pro, BetreiberIn, 10.11.2026 | → KG-05 (E-KG-12: ein Muster je Bedienung, ein Aufruf) |
| F-KG-03 | **Kommentar und Handbuch stimmen beim Zyklusende nicht:** `Util.mc` nennt Teil 2 „einen Tick später" (es sind zwei Ticks, ~2 s); das Handbuch nennt alle fünf Pulse „kräftig" (Pulse 4 und 5 haben 75 % statt 90 %); das Handbuch nennt den Ring in der Pause gelb (Code: blau) | Lesen, 10.11.2026 | → KG-05 |
| F-KG-04 | **Die App vibriert auch, wenn sie nicht im Vordergrund ist** (Countdown-Ende) | Fenix 6 Pro, BetreiberIn, 10.11.2026 | eingetragen in `docs/Geraete-Eingabe.md` 2 (10.11.2026) |

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

Noch nichts gemessen.
