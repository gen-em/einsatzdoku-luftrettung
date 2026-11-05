# Konzept RD — Referenzdatensatz und Demo: alle Spurfälle (Fahrplanschritt 24)

**Kürzel:** RD. Pakete `RD-NN`, Entscheidungen `E-RD-NN`, Befunde
`F-RD-NN`, Fragen `Q-RD-NN`, Prüfpunkte `P-RD-NN` (`docs/Pruefablauf.md` 7).

**Zweig:** `claude/admiring-sagan-mql8t4` (Konzept; die Umsetzung in einer
eigenen Sitzung, Q-RD-09) · **Herkunft:** Nr. 388 und Nr. 500, Schritt 24
im Rahmenplan (angelegt 05.11.2026, vor 13 auf Entscheid der Betreiberin) ·
**Backlog-Spanne:** 510 bis 519 · **Prüfdokument:**
`Pruefdokument-RD-Referenzdatensatz.md` (entsteht mit RD-06).

**Modell:** Opus für alle Pakete (K2); kein Fable-Schritt.
**Fächerung:** keine, in keinem Paket (CLAUDE.md 7). Jedes Paket endet mit
Prüfarbeit an der einen örtlichen Anlage, und RD-02 bis RD-05 schreiben
nacheinander in denselben Bestand (Quelldaten → Referenzkonto → Fixture →
Sollstände); was davon parallel liefe, misst einander.

**Keine Fassungsnummer in diesem Dokument** — welche Stufe eine Zählung
hochgeht, entscheidet die Umsetzung (K3, CLAUDE.md 2).

---

## Statusblock

| Paket | Inhalt | Stand |
|---|---|---|
| RD-01 | Fixture wieder erzeugbar (Nr. 388) | offen |
| RD-02 | Quelldaten: die fehlenden Spurfälle, Matrix, Generator | offen — wartet auf Q-RD-03 bis -06 |
| RD-03 | Einspielen: Stufen `verdichten` und `loeschen` | offen |
| RD-04 | Demo-Reset legt Stufe 2 und 3 selbst an (Fixture-Format 3) | offen — wartet auf Q-RD-02 |
| RD-05 | Fixture, Referenzexporte und Sollstände neu; Dokumente | offen |
| RD-06 | Abschluss: Prüfdokument, Prüfliste (P-S11-30, -31), Pull Request | offen |

**In Arbeit:** nichts — das Konzept steht, die Umsetzung hat nicht
begonnen. **Es hakt:** an den Fragen Q-RD-01 bis -09 (Abschnitt 3); ohne
Q-RD-01 und -02 beginnt kein Paket, ohne Q-RD-04 bis -06 nicht RD-02.
Q-RD-04 hat seit der Rückfrage vom 05.11.2026 einen Vorschlag (d), der
die Übungstage der Betreiberin einschließt; Q-RD-10 (Rücknahme im
Demo-Konto, F-RD-14) kam mit der zweiten Rückfrage hinzu.

---

## 1. Auftrag

Zwei Backlog-Punkte, ein Schritt:

- **Nr. 388** — Die Demo-Fixture (`server/demo/fixture.json.gz`) lässt sich
  seit Web 23 nicht mehr erzeugen. `tools/referenzdatensatz/fixture/erzeugen.php`
  bricht mit Rückgabe 2 ab, und mit ihm der Positivfall der
  `riegelprobe.php`.
- **Nr. 500** — Referenzdatensatz und Demo-Konto kennen nur Spurstücke der
  Stufe 1 und drei Schnitte. Es fehlen: Stufe 2 und 3, ein Schnitt neben
  einem Löschvermerk, ein Segment über Mitternacht, eines mit Lücke, ein
  Schnitt, der alles nimmt, „ein Einsatz mit eigener Spur" (Q-RD-04). Der
  Demo-Reset soll verdichtete Stücke selbst anlegen, weil der Wartungslauf
  das Demo-Konto seit Nr. 459 auslässt. Beide Kreisläufe bekommen neue
  Sollstände; P-S11-30 und -31 werden neu gefasst.

**Warum vor 13:** P7 schreibt das Handbuch an diesem Bestand neu
(Betreiberin, 05.11.2026). Jeder Spurfall, der erst danach in die Demo
kommt, fehlt dort in Bild und Text.

**Berührt werden** `tools/referenzdatensatz/` (Quelldaten, Generator,
Einspielen, Fixture, Vergleich, Referenzexporte), `server/demo_lib.php`,
`server/backup_lib.php` (nur der Zweig `'server'` von `edbak_restore()`),
`server/geraetehuelle_lib.php`, `server/demo/fixture.json.gz`,
`tools/proben/huelle/` (Vektor für das gepackte Stück in PHP), dazu die
Dokumente nach CLAUDE.md 2 und 9: `docs/Technik.md` 4.21 (Fixture-Format,
Reset), `docs/Backup-Format.md` (nur, falls ein Feld der Nutzlast neu
belegt wird), `docs/Handbuch.md` 3.2 und 11.6 (Zahlen des Demo-Bestands),
`docs/Pruefablauf.md` (neue Prüfzahlen in 6.11), die `LIESMICH.md` der
berührten Werkzeuge, `docs/konzepte/Pruefliste.md` (P-S11-30, -31, P-R4-16).

**Nicht Teil:** Schema und Migrationen — `track_stuecke` und `track_cuts`
tragen heute alles, was die neuen Fälle brauchen; kein `update.php` nach
dem Deploy. Nicht Teil: `watch/`, `android/`, `.github/`, der Browserweg
des Wartungslaufs (`assets/wartungslauf.js`, `assets/ortsdaten.js`), der
JSON-Vertrag. Nicht Teil: neue Funktionen des Schneidewerkzeugs (etwa
Schneiden aus einer Einsatzspur, Q-RD-04 (c)) — fällt die Antwort so aus,
ist das ein eigener Backlog-Punkt, nicht dieser Schritt. Die Auslieferung
(`web-v…`) ist nicht Teil (CLAUDE.md 3).

## 2. Befund

Gelesen am 05.11.2026 auf `origin/main` `7595d5a` (nach PR #151).

**Nr. 388 — bestätigt, mit zwei Zusätzen.** `erzeugen.php` setzt
`edbak_build()` am Stück mit Punktlisten voraus; seit Web 23 gibt es nur
Kopf und Fenster, und der Server sieht keine Punkte. Das Skript bricht
absichtlich ab.

**F-RD-01 — Die eingecheckte Fixture ist älter als die Referenz.** Die
Fixture trägt `erzeugt_am 2026-09-27`, `web_version 21.3.0`, Nutzlast 7,
67 292 Spurpunkte (50 096 in 98 Einsatzspuren, 17 196 in 123
Ruhesegmenten). Die Referenz (`referenz/einsatzdoku-backup-2026-10-19.edbak`,
Nutzlast 13) hat 67 478 Punkte: Seit Web 22.2.0 bleiben die Punkte eines
Schnitts im Segment (gesperrt statt verschoben), 186 Punkte in den drei
Quellsegmenten. Demo-Konto und Referenzkonto sind also schon heute nicht
derselbe Bestand — unbemerkt, weil kein Riegel beide vergleicht → RD-05
Abnahme, Q-RD-08.

**F-RD-02 — Was die Fixture an Schlüsselmaterial braucht, gibt keine
Schnittstelle her.** `password_hash`, `kdf_salt`, `pat_wrap_pw`,
`pat_wrap_rc`, `account_key` und `api_key_hash` der Geräte stehen nur in
der Datenbank. Ein reiner Browser- oder API-Weg (wie `referenz_export.mjs`)
kann die Fixture deshalb nicht allein erzeugen; der Erzeuger muss auf der
Referenzmaschine lesen, wie er es heute tut → Q-RD-01.

**F-RD-03 — Der Server kann die Hüllen des Demo-Kontos öffnen, und tut es
an anderer Stelle schon.** `demo_inhaltsschluessel()` rechnet den
Inhaltsschlüssel aus `DEMO_PASSWORT`; `kp_…` liefert den privaten Teil des
Kontopaars unter diesem Schlüssel (`edk1:`); `tools/proben/huelle/geraet.php`
öffnet Gerätehüllen in PHP („nie im Server", nur als Werkzeug). Ein
Erzeuger in `tools/` darf damit die Stücke und Einsatzhüllen des
Referenzkontos öffnen — mit demselben Schlüsselmaterial, das die Fixture
ohnehin mitführt. Das Demo-Konto ist das eine Konto, bei dem das keine
Aufweichung der Zusage ist (`docs/Technik.md` 4.21, vier Bedingungen).

**Nr. 500 — bestätigt, in jedem Punkt.** Gemessen an der Fixture:

| Fall | Heute |
|---|---|
| Stufe 2 oder 3 | 0 Spuren — der Reset baut aus Punktlisten nur Stufe 1 (`gh_stuecke_bauen()`); der Wartungslauf lässt das Demo-Konto aus (`api/wartungslauf.php`, Nr. 459) |
| Schnitt | 3, alle aus Ruhesegmenten (D08, D18, D19), keiner neben einem Löschvermerk |
| Löschvermerk (`track_cuts.mission_id IS NULL`) | 0; `einspielen.py` hat keine Stufe dafür, `api/schneiden.php` kennt den Weg (`action: 'loeschen'`) |
| Segment über Mitternacht | 1 abgeschlossenes (`ar-12-9295332214`, 28./29.03.2026, 44 Punkte — die Zeitumstellungsnacht) und das offene des D22; keines davon mit Schnitt oder Stufe 2/3 |
| Lücke | keine Sequenzlücke in 221 Spuren; eine Zeitlücke über 120 s nur in den drei Quellsegmenten (das ist der Schnitt) und in `am-12-8845270913` |
| Schnitt, der alles nimmt | keiner |

**F-RD-04 — Gepackt wird nur im Browser, und das steht so im Code.**
`geraetehuelle_lib.php` endet mit „`gh_gepackt_bauen()` gibt es hier nicht:
Gepackt wird nur im Browser". Die Innenform der Stufe 2 und 3 („B fest",
`docs/Technik.md` 4.6) gibt es in **zwei** Fassungen: der Referenz
`innen_gepackt()` in `tools/proben/huelle/referenz.py` (Python; das
Einspielwerkzeug importiert sie, `ortsdaten.py` Zeile „DAS FORMAT steht
in referenz.py und nur dort") und `EdOrtsdaten.gepacktBauen()` im Browser.
Für Stufe 1 gibt es dieselben zwei **und** eine dritte in PHP
(`gh_spur_innen()`, `gh_stuecke_bauen()` — für den Demo-Reset), gemessen
von der Hüllenprobe gegen die Referenz. Für einen Demo-Reset, der Stufe 2
und 3 anlegt, fehlt PHP genau diese dritte Fassung — oder die Fixture
trägt die Stücke fertig verschlossen → Q-RD-02.

**F-RD-05 — Ausdünnen im Reset wäre zu teuer, Packen nicht.** Die
PHP-Referenz der Ausdünnung (`spur_ausduennen()`) kostet quadratisch;
`spur_lib.php` nennt 114 s für eine Spur mit 50 000 Punkten. Der Reset
trägt die Besucherin, deren Anfrage ihn auslöst (heute 7,3 s). Deshalb
darf der Reset nicht ausdünnen, sondern nur **packen, was die Fixture schon
ausgedünnt trägt**: Die Fixture führt je Spur die Punkte nach der
Ausdünnung, `stufe` und `n_original` — ausgedünnt hat sie der reguläre
Wartungslauf im Referenzkonto, bevor die Fixture erzeugt wurde (RD-03 vor
RD-05). Packen ist linear (`gh_spur_innen()` ist es heute schon).

**F-RD-06 — Nutzlast 7 der Fixture kann Schnitte und Löschvermerke
tragen.** `edbak_restore()` liest `missions[].schnitte` und
`rest_segments[].loeschungen` unabhängig von der Fassung der Datei; die
Fixture trägt `schnitte` heute schon (drei, mit `quelle_art`, `quelle_ref`).
Ein Fassungswechsel des inneren Backups ist nicht nötig; neu sind nur die
Felder `stufe` und `n_original` an spurtragenden Einträgen, die Nutzlast 13
ohnehin definiert (`docs/Backup-Format.md`) → E-RD-03.

**F-RD-07 — Der Wartungslauf nimmt vom Client jede Stufe ≥ Bestand an.**
`spur2_wartung_tauschen()` prüft Stufe 3 gegen die Frist; `spur2_tauschen()`
verlangt `stufeNeu >= stufeAlt`. Die Liste nennt zwar `ziel_stufe` (nach 31
Tagen immer 3 — und im Referenzbestand ist **jede** Spur älter als 31
Tage), ein Client darf aber Stufe 2 schicken. Ein Einspielwerkzeug kann
also je Spur wählen, welche Stufe sie bekommt → RD-03.

**F-RD-08 — Ein Schnitt, der alles nimmt, bleibt womöglich für immer
fällig.** `wartungslauf.js`, `eine()`: Sind nach dem Ausblenden der
Sperren keine Punkte übrig, antwortet der Browser `'liegt'` und tauscht
nichts. Das Segment bleibt Stufe 1, `spur2_faellig_kandidaten()` nennt es
beim nächsten Lauf wieder (`stufe_min = 1`), und es belegt einen der 20
Plätze — nicht gemessen, aus dem Code gelesen. Genau dieser Fall soll in
den Bestand (Nr. 500); er misst dann diesen Befund mit → RD-02, RD-03
Abnahme; bestätigt er sich, ist es ein Backlog-Punkt aus der Spanne, kein
Teil dieses Schritts (Nicht-Ziel: der Browserweg).

**F-RD-09 — „Zeitraum löschen" lehnt das ganze Segment ab, „Schneiden"
nicht.** `api/schneiden.php` antwortet auf ein Löschen ohne verbleibendes
Stück mit `409 alles` („Dafür ist der Papierkorb da"). Ein Schnitt über das
ganze Segment ist dagegen erlaubt: Die Punkte bleiben, gesperrt. „Schnitt,
der alles nimmt" (Nr. 500) ist also der Schnitt, nicht das Löschen.

**F-RD-10 — Ein Schnitt nach Mitternacht ist erlaubt, einer über
Mitternacht nicht.** `pruefen.py` verbietet Schnitte, deren Beginn und Ende
auf verschiedenen Kalendertagen liegen (die Phasen rechnen mit dem
Tagesversatz des Beginns); `api/schneiden.php` nimmt `beginn_tag` und
`ende_tag` (+1 = Folgetag). `einspielen.py` übergibt heute immer den
Versatz 0 (`ortsdaten.zeiten()` mit `0`). Ein Schnitt **nach** Mitternacht
in einem Segment, das über Mitternacht läuft, ist der Fall, der den
Versatz 1 durch das Werkzeug und durch den Bestand bringt → Q-RD-05.

**F-RD-11 — Die Ruhesegmente entstehen bei jedem Aufbau neu, ihre
Kennung nicht.** `aufbauen.py` baut sie als Zwischenräume zwischen den
Einsätzen (`segment()`); die `client_ref` kommt aus
`random.Random(f"{SAMEN}-{kennung}")` und ist bei gleichem Bestand
dieselbe. Genau darauf baut die Liste `schnitte` am Dienst
(`segment: <client_ref>`), und sie überlebt den Aufbau. Ein Spurfall an
einem Ruhesegment darf also nicht **im** Segment stehen (das wird
überschrieben), wohl aber am Dienst mit derselben Adressierung — ein
zweiter Weg (etwa über eine Zeit) wäre Wildwuchs → E-RD-04. *(Bis zur
Rückfrage der Betreiberin am 05.11.2026 schlug das Konzept hier einen
Block mit Zeitangabe vor.)*

**F-RD-12 — Zwei Werkzeuge nennen die alten Zahlen.** `demo_pruefen.mjs`
und `sichtpruefung.mjs` greifen auf Kacheln und erste Einsatzzeile des
neuesten Diensttags; P-R4-16 in der Prüfliste nennt „21 · 104 · 118 · 2".
Jede Änderung des Bestands zieht dort nach → RD-05.

**F-RD-14 — Im Demo-Konto lässt sich kein Schnitt zurücknehmen, auch
auf Produktiv nicht.** `schnitt_rueckgaengig()` in `api/schneiden.php`
hält `erstellt_am` des Vermerks gegen `applied_at` der Migration
`2026_10_15_loeschvermerk` **dieser Anlage**; ist der Vermerk älter,
antwortet es `409 altschnitt` („vor der Verschlüsselung geschnitten …
Rückgängig würde die Punkte mitlöschen"). Die Fixture trägt ihre drei
Vermerke mit `erstellt_am 2026-09-27`, und `edbak_restore()` übernimmt
den Wert wörtlich (Kopf von `edbak_schnitt_werte()`: „`erstellt_am` reist
mit"). Produktiv hat die Migration am 20.10.2026 angewandt: Jeder Reset
legt drei Schnitte an, die die Anwendung für alt hält, obwohl die 186
Punkte im Segment liegen — die Meldung ist dort falsch. Eine neue Fixture
hilft nur, bis eine Anlage **nach** deren Erzeugungsdatum neu installiert
wird. Gefunden am 05.11.2026 bei der Rückfrage der Betreiberin, aus dem
Code gelesen, nicht auf Produktiv gemessen → Q-RD-10, RD-04.

**F-RD-13 — `schneiden.php` kennt als Quelle nur ein Ruhesegment.**
`track_cuts.owner_type` erlaubt `'mission'`, der Endpunkt liest die Quelle
aber nur aus `rest_segments` (`rest_id`). „Ein Einsatz mit eigener Spur"
kann also nicht „Schneiden aus der Einsatzspur" meinen, ohne dass das eine
neue Funktion wäre → Q-RD-04.

## 3. Fragen an die Betreiberin

| Nr. | Frage | Vorschlag | Paket |
|---|---|---|---|
| Q-RD-01 | **Weg der Fixture (Nr. 388).** (a) `erzeugen.php` neu: liest Konto, Geräte und `edbak_build()` (Kopf und Fenster, `pat_blob` als Chiffretext) auf der Referenzmaschine, öffnet Stücke und Einsatzhüllen mit dem Schlüsselmaterial des Demo-Kontos (F-RD-03) und schreibt die Punkte im Klartext in die Fixture. (b) Browserweg wie `referenz_export.mjs`, Schlüsselmaterial aus einem zweiten, lesenden Skript (F-RD-02). (c) Fixture aus den Quelldaten des Generators, ohne Konto. | **(a).** Ein Werkzeug, nur lesend, auf derselben Maschine wie heute; die Fixture stammt weiter aus dem Konto, das über die regulären Wege entstanden ist (Strecke, Anstieg, Schnitte, Papierkorb, Gerätekennungen). (b) braucht zwei Skripte und einen Browser für dasselbe Ergebnis. (c) umginge die Anwendung — Werte, die sie rechnet, stünden dann nicht in der Fixture, und `einspielen.py` hätte nichts mehr zu belegen. | RD-01 |
| Q-RD-02 | **Stufe 2 und 3 im Demo-Reset (Nr. 500).** (a) `geraetehuelle_lib.php` bekommt `gh_gepackt_bauen()` (vierte Fassung der Innenform „B fest", F-RD-04), die Hüllenprobe misst sie gegen `referenz.py`; die Fixture trägt je Spur `stufe`, `n_original` und die Punkte, wie sie nach der Ausdünnung liegen (F-RD-05). (b) Die Fixture trägt die Stücke fertig verschlossen; dann braucht jedes Demo-Konto auf jeder Anlage dasselbe Schlüsselpaar aus der Fixture, und `demo_paar_sicherstellen()` würde es setzen statt erzeugen. | **(a).** Der Server packt für dieses eine Konto, so wie er dafür heute schon Stufe 1 verschließt; die Zusage „der Server packt nicht" gilt für jedes andere Konto unverändert und steht so in `Technik.md` 4.21. (b) legt den privaten Teil eines Kontopaars ins Repositorium — für das Demo-Konto vertretbar, aber ein zweiter Mechanismus neben `demo_paar_sicherstellen()`, und ein Schlüsselwechsel der Hülle (Fassung 2) machte jede Fixture ungültig. | RD-04 |
| Q-RD-03 | **Verteilung der Stufen.** Wie viele Spuren auf Stufe 2, wie viele auf 3, der Rest Stufe 1? | **Je Art etwa fünf auf 2 und fünf auf 3** (Einsatzspuren und Ruhesegmente getrennt, so dass auf beiden Diensttypen Luft und Boden je eine liegt); darunter das Übungssegment der Stufe 2 aus Q-RD-04 (es ist zugleich das Segment für P-S11-30) und ein Einsatz mit Reanimation (die Reanimation ist kein Schutzpunkt der Ausdünnung, Technik 4.6). **Die drei bestehenden Schnitte verteilen sich auf die drei Stufen:** einer bleibt Stufe 1 (Punkte gesperrt im Segment, Rücknahme vorführbar — Q-RD-10), einer wird neu gepackt (Stufe 2, die geschnittenen Punkte fallen physisch), einer ausgedünnt (Stufe 3). Alles Übrige bleibt Stufe 1, damit die Fixture klein bleibt und der Reset weiter Stufe 1 misst. | RD-02 |
| Q-RD-04 | **Was heißt „ein Einsatz mit eigener Spur" in Nr. 500?** 98 von 109 Einsätzen tragen eine. (a) Ein Einsatz mit eigener Spur innerhalb eines verdichteten Ruhesegments. (b) Ein geschnittener Einsatz neben einem Einsatz mit eigener Spur. (c) Schneiden **aus** einer Einsatzspur — gibt es nicht (F-RD-13), wäre eine neue Funktion. **(d) Ein Einsatz, der nur als Spur existiert:** Niemand hat den Knopf gedrückt, die Bewegung zum Einsatzort und zurück liegt **ungeschnitten im Ruhesegment** — das Übungsmaterial für das Schneidewerkzeug (`api/schneiden.php`, Kopf: „Wer während eines Einsatzes keinen Knopf gedrückt hat, hat den Einsatz nicht — aber seine Spur hat er"). | **(d), und nur (d)** — nach der Rückfrage der Betreiberin vom 05.11.2026 („2–3 Einsatztage mit ungeschnittener Spur, an der man das Schneiden üben kann"). **Drei Diensttage** bekommen je ein Übungssegment mit einsatzförmiger Bewegung (Flug oder Fahrt, wie die drei Quellsegmente der Schnitte sie hatten — der Generator kann das schon, `ruhespur()`), ohne Einsatz, ohne Schnitt: **zwei auf Stufe 1, eines auf Stufe 2**. Das Segment der Stufe 2 ist das Segment für P-S11-30 („Schneiden nach dem Verdichten"), ein eigener Fall dafür entfällt. (a) und (b) belegen nichts, was Stufe 2 und Schnitt nicht schon belegen, und kommen nicht in die Matrix; (c) nur als Backlog-Punkt, wenn die Betreiberin ihn will. Der Demo-Reset holt die Übungssegmente 30 Minuten nach der ersten Änderung zurück — üben geht beliebig oft. **Bitte bestätigen.** | RD-02 |
| Q-RD-05 | **„Segment über Mitternacht".** Ein abgeschlossenes Ruhesegment über Mitternacht gibt es (Zeitumstellungsnacht D06). Soll der neue Fall (a) ein Segment über Mitternacht **ohne** Zeitumstellung sein (D22, Nachtdienst Luft), verdichtet auf Stufe 2, **mit einem Schnitt nach Mitternacht** (Versatz 1, F-RD-10), oder (b) nur das Segment, ohne Schnitt? | **(a).** Es ist der einzige Weg, `beginn_tag = 1` durch Werkzeug, Bestand und Demo zu bringen; das Segment der Zeitumstellungsnacht bleibt daneben auf Stufe 1. | RD-02 |
| Q-RD-06 | **„Segment mit Lücke".** (a) Eine **Sequenzlücke** (das Gerät hat Punkte verloren; `gh_stuecke_bauen()` beendet dort ein Stück, Stufe 2 deckt `seq_from … seq_from + n_original - 1`, `n_original > punkte`) oder (b) eine **Zeitlücke** (das Gerät hat pausiert, Nummern fortlaufend) oder (c) beides in einem Segment? | **(c), aber in zwei Segmenten:** je ein Fall, weil sie verschiedene Stellen messen — die Sequenzlücke die Marke und `n_original`, die Zeitlücke die Luftlinie und die Phasenzuordnung. Der Generator (`spur.py`) bekommt dafür zwei Marken an der Route, keinen Zufall. | RD-02 |
| Q-RD-07 | **Schnitt neben Löschvermerk — Reihenfolge.** Im selben Segment erst schneiden, dann einen zweiten Zeitraum löschen (der Löschvermerk ist jünger als der Schnitt), oder umgekehrt? | **Erst schneiden, dann löschen, dann verdichten** — so trifft der Fall „neu packen: ein Sperrvermerk ist jünger als das jüngste Stück" (`spur2_faellig_kandidaten()`), und das Segment durchläuft alle drei Zustände, die der Wartungslauf unterscheidet. Ein zweites Segment bekommt nur einen Löschvermerk ohne Schnitt. | RD-02, RD-03 |
| Q-RD-08 | **Ein Riegel gegen F-RD-01.** Soll ein Prüfmittel in Stufe 1 messen, dass Fixture und Referenz denselben Bestand tragen (Zahl der Diensttage, Einsätze, Segmente, Punkte je Stufe, Schnitte, Löschvermerke)? Heute vergleicht das nichts. | **Ja, als Stufe-1-Riegel ohne Installation** (`tools/referenzdatensatz/fixture/abgleich.py`, liest `fixture.json.gz` und den Kern der `.edbak` — der Kern ist seit Nutzlast 13 ohne Punkte, die Zahlen stehen als `n`, `stufe`, `n_original`). Anlass: Nr. 388. Rot, wenn die Zahlen auseinandergehen. | RD-05 |
| Q-RD-10 | **Rücknahme im Demo-Konto (F-RD-14).** (a) Der Reset stempelt `erstellt_am` der Schnitte und Löschvermerke auf die Reset-Zeit — nur im Zweig `'server'`, also nur für das Demo-Konto, so wie der Reset `deleted_at` schon frisch stempelt. (b) Nichts tun; die Fixture neu erzeugen und hinnehmen, dass eine später installierte Anlage die Demo-Schnitte für alt hält. | **(a).** Eine Zeile, kein neuer Weg; die Rücknahme wird damit auf jeder Anlage vorführbar, und die Matrix bekommt den Fall „Schnitt zurücknehmen" (an einem der drei bestehenden Schnitte, dem auf Stufe 1). Für jedes andere Konto bleibt der Wert, wie er reist. | RD-02, RD-04 |
| Q-RD-09 | **Sitzung und Abschluss.** Umsetzung in einer eigenen Sitzung (Arbeitsregel der Betreiberin) auf diesem Zweig; am Ende von RD-06 ein Pull Request? | **Eigene Sitzung, derselbe Zweig, Pull Request am Ende von RD-06**; die Tags setzt die Betreiberin danach (CLAUDE.md 3). | RD-06 |

## 4. Entscheidungen

| Nr. | Entscheidung | Wer, wann |
|---|---|---|
| E-RD-01 | Schritt 24 läuft vor 13 (P7), weil P7 das Handbuch an diesem Bestand neu schreibt; Nr. 388 wandert von 14 nach 24. | Betreiberin, 05.11.2026 (Rahmenplan Fassung 218) |
| E-RD-02 | Der Reset dünnt nicht aus; er packt, was die Fixture ausgedünnt trägt (F-RD-05). Die Ausdünnung geschieht genau einmal, im Referenzkonto, durch den regulären Wartungslauf — angestoßen vom Einspielwerkzeug (RD-03), nicht von einem Browser nebenbei. | Konzept, 05.11.2026 — *bitte mit Q-RD-02 bestätigen* |
| E-RD-03 | Die Fixture bleibt beim inneren Backup der Nutzlast 7 (Punktlisten im Eintrag) und wird **Fixture-Format 3**: an jedem spurtragenden Eintrag zusätzlich `stufe` und `n_original` (die Namen der Nutzlast 13), an Ruhesegmenten `loeschungen`, an Einsätzen `schnitte` wie bisher; `rea` und Phasenorte im Klartext wie bisher. `demo_fixture_laden()` nimmt Format 2 weiter an (Stufe 1 überall). Kein Wechsel der Nutzlast, weil `edbak_restore()` die neuen Felder schon liest (F-RD-06). | Konzept, 05.11.2026 |
| E-RD-04 | Jeder Spurfall steht in den **Quelldaten** mit einer Matrixzeile, nie nur im Einspielwerkzeug: Was `pruefen.py` nicht zählt, kann still verschwinden (Vorbild: die Schnitte, E-DA-11). **Eine Adressierung für alles:** Jede Handlung an einer Spur steht am Dienst als Liste mit `segment: <client_ref>` (bei Einsätzen `einsatz: <client_ref>`), genau wie `schnitte` heute — `loeschungen` (gleiche Felder wie ein Schnitt ohne Phasen), `verdichten` (`stufe`), `luecken` (`art`, `ab`, `dauer`). Kein neuer Block, keine Zeitsuche, nichts im Segment selbst (F-RD-11). *(Geändert nach der Rückfrage vom 05.11.2026; vorher „`spurfaelle` mit Zeitangabe".)* | Konzept, 05.11.2026 |
| E-RD-05 | Das Einspielwerkzeug geht nur **reguläre Wege** (R4): Verdichten über `api/wartungslauf.php` (`liste`, `tauschen`), Löschen über `api/schneiden.php` (`loeschen`), beides mit `ortsdaten.py` wie ein Browser. Kein SQL, kein eigener Weg in `spur_lib.php`. | Konzept, 05.11.2026 |
| E-RD-06 | Die Reihenfolge der Pakete ist zwingend: RD-01 (Erzeuger) und RD-04 (Reset) sind Code, RD-02 und RD-03 sind Bestand, RD-05 erzeugt daraus Fixture und Sollstände. Die Fixture kann erst entstehen, wenn das Referenzkonto alle Fälle trägt (RD-02, RD-03) **und** der Erzeuger sie lesen kann (RD-01); eingespielt werden kann sie erst, wenn der Reset Format 3 versteht (RD-04). | Konzept, 05.11.2026 |
| E-RD-07 | Jeder neue Fall bekommt eine Zeile in `Pruefliste.md` nur, wenn er von Hand zu prüfen ist (P-S11-30 neu gefasst, P-S11-31 neu gefasst, ein Punkt je Spurfall in der Demo); was `pruefen.py`, der Kreislauf und der Abgleich messen, steht im Prüfprotokoll, nicht in der Prüfliste (CLAUDE.md 7). | Konzept, 05.11.2026 |
| E-RD-08 | **Kein neuer Weg, wo ein alter trägt** (Rückfrage der Betreiberin, 05.11.2026): Jedes Paket nennt in Abschnitt 6, was es neu anlegt, was es wiederverwendet und was es ablöst. Neu entsteht nur, was keine vorhandene Stelle leisten kann: ein PHP-Packer (dritte Fassung desselben Formats, gegen die eine Referenz gemessen, wie Stufe 1 heute), zwei Einspielstufen am vorhandenen Werkzeug, ein Abgleich (Q-RD-08). Abgelöst wird der tote Erzeuger. | Konzept, 05.11.2026 |

## 5. Arbeitspakete

Jedes Paket benennt den Ort jeder neuen Funktion (K1, R74). Ein Paket mit
Änderung unter `server/` stuft `server/version.php` hoch und schreibt einen
Changelog-Eintrag (CLAUDE.md 2); ein Paket nur unter `tools/` und `docs/`
stuft nichts hoch. Die Prüfmittel laufen zuletzt, nach der letzten
Änderung (CLAUDE.md 6).

### RD-01 Fixture wieder erzeugbar (Nr. 388; Q-RD-01)

*Vorab:* Den heutigen Abbruch einmal messen (Rückgabe 2, Meldung), die
Riegelprobe einmal fahren (Positivfall rot, Negativfall grün — die Zahl
nennen).

*Weg (bei Q-RD-01 (a)):*
1. `erzeugen.php` liest wie heute Konto und Geräte aus der Datenbank
   (nur lesend, nur das Konto aus dem Argument, Rolle `user`, Riegel auf
   `edk1:` unverändert).
2. Den Bestand holt es über `edbak_build()` — Kopf und Fenster, wie
   `api/backup_*.php` es für den Browser tun; `pat_blob` kommt dabei als
   Chiffretext, Spuren als Umriss (`spur_ref`, `stufe`, `n_original`, `n`).
3. Die Stücke liest es über `spur2_lesen_viele()`, die Einsatzhüllen
   (`ort_huelle`) über den Weg, den `api/mission.php` geht, und öffnet
   beides mit dem privaten Teil des Kontopaars, den es wie `demo_lib.php`
   aus `DEMO_PASSWORT` ableitet (`demo_inhaltsschluessel()`,
   `demo_edk1_oeffnen()`; das Öffnen der Gerätehülle aus
   `tools/proben/huelle/geraet.php`, nicht aus `server/`). Jede Spur
   schreibt es als Punktliste mit `stufe` und `n_original` in den Eintrag,
   Phasenorte und Reanimation in die Felder, die Nutzlast 7 kennt;
   Löschvermerke und Schnitte aus dem Kern.
4. Die Fixture erhält `version: 3` (E-RD-03), `web_version` aus
   `version.php`, `quelle` mit dem Commit des Referenzbestands.
5. Das Skript bricht ab, wenn eine Hülle nicht aufgeht, eine Punktzahl
   nicht zum Umriss passt oder Klartext-Ortsdaten im Kern stehen
   (`edbak_klartext_fund()`); es hält die Jobs-Warnung aus dem heutigen
   Kopf, weil der Wartungslauf im Referenzkonto zwischen RD-03 und dem
   Erzeugen nichts mehr tauschen darf.
6. `riegelprobe.php` auf den neuen Erzeuger; `fixture/LIESMICH.md` (neu —
   das Verzeichnis hat keine) mit Anlass-Zeile Nr. 388.

*Ort:* `tools/referenzdatensatz/fixture/erzeugen.php`, `riegelprobe.php`,
neu `fixture/LIESMICH.md`; `docs/Technik.md` 4.21 „Die Fixture" (der
Absatz „Warum sie nicht aus einer `.edbak` kommen kann" wird neu gefasst:
Der Grund gilt weiter, der Weg ist ein anderer).

*Abnahme:* `erzeugen.php` schreibt aus dem heutigen Referenzkonto eine
Fixture; Punktzahl gleich der Summe der Umrisse (Zahl nennen); die Fixture
setzt das Demo-Konto zurück, und die Kacheln zeigen die heutigen Zahlen;
Riegelprobe in beiden Richtungen grün (Zahl der Fälle). Kein Schreiben in
die Datenbank (vorher/nachher `SHOW TABLE STATUS` oder Zeilenzahlen gleich).

### RD-02 Quelldaten: die fehlenden Spurfälle (Nr. 500; Q-RD-03 bis -07)

*Weg:*
1. Matrixzeilen in `quelldaten/pruefen.py` (Bereich „Spuren"), je Fall
   eine Marke — **vierzehn**: `stufe-2-einsatz`, `stufe-2-segment`,
   `stufe-3-einsatz`, `stufe-3-segment`, `stufe-3-rea`,
   `uebung-ungeschnitten` (dreimal, Q-RD-04), `schnitt-stufe-1`,
   `schnitt-stufe-2`, `schnitt-stufe-3` (die drei bestehenden, Q-RD-03),
   `schnitt-neben-loeschvermerk`, `loeschvermerk-allein`,
   `schnitt-nach-mitternacht`, `schnitt-nimmt-alles`, `luecke-sequenz`,
   `luecke-zeit`. Die Zustände als Matrix „Schnittzustand × Stufe" stehen
   im Kopf von `pruefen.py`, damit die nächste Erweiterung die leere Zelle
   sieht.
2. `FORMAT.md`: neben `schnitte` drei Listen derselben Bauart (E-RD-04):
   `loeschungen` (`segment`, `beginn`, `ende`), `verdichten` (`segment`
   oder `einsatz`, `stufe`), `luecken` (`segment`, `art`, `ab`, `dauer`);
   `schnitte` bekommt `beginn_tag` und `ende_tag` (Vorgabe 0). Die Regel
   „nicht über Mitternacht" bleibt; neu: ein Schnitt mit `beginn_tag = 1`
   liegt nach dem Diensttag. Die Übungssegmente brauchen **kein** Feld:
   Ihre Bewegung beschreibt `wegpunkte.py` wie bei den drei Quellsegmenten
   der Schnitte.
3. `pruefen.py` prüft: jede Marke genau so oft belegt, wie die Matrix
   sagt; Stufe 3 nur an Spuren, deren Ende mehr als 31 Tage vor dem
   Erzeugungsdatum der Referenz liegt (sonst verweigert
   `spur2_wartung_tauschen()` mit `frist`); ein Löschvermerk liegt in der
   Spur und neben keinem Schnitt außer im Fall `schnitt-neben-loeschvermerk`;
   `schnitt-nimmt-alles` deckt das ganze Segment; `aufbauen.py` prüft wie
   bei `schnitte`, dass jede genannte `client_ref` im Dienst steht.
4. Generator: `spur.py` bekommt Marken für die zwei Lücken (Q-RD-06), ohne
   Zufall; die alten Dienste behalten byteweise dieselben Spuren
   (`diff -r` gegen die Ausgabe vor der Änderung — der Nachweis der
   Generator-`LIESMICH.md`). `generator/pruefen.py` prüft die Lücken.
5. `matrix_abgleich.md` neu erzeugen (`pruefen.py --matrix`).

*Ort:* `tools/referenzdatensatz/quelldaten/` (`dienste/D*.json`,
`pruefen.py`, `aufbauen.py`, `FORMAT.md`, `schema/`, `matrix_abgleich.md`),
`generator/spur.py`, `generator/pruefen.py`.

*Abnahme:* `quelldaten/pruefen.py` 0 Befunde, keine offene Matrixzeile
(Zahl der Zeilen vorher/nachher); `generator/pruefen.py` ohne Befund;
`diff -r` der alten Dienste leer; die Zahl der Dienste und Einsätze in
`FORMAT.md` stimmt (heute 22 / 106, Bestand 109).

### RD-03 Einspielen: Stufen `verdichten` und `loeschen` (Nr. 500; Q-RD-07)

*Weg:*
1. Neue Stufe `loeschen` nach `schneiden`: findet das Segment wie
   `stufe_schneiden()` (über `start_hhmm`), öffnet die Stücke
   (`ortsdaten.spur_oeffnen()`), verschließt die Punkte außerhalb des
   Zeitraums neu (`stuecke_bauen()`), schickt `action: 'loeschen'` mit
   `stand`, `stuecke`, `geloescht`. Erwartet `409 alles` für einen Versuch
   über das ganze Segment — das wird als Probe aufs Exempel einmal gemessen,
   nicht als Fall gespeichert (F-RD-09).
2. Neue Stufe `verdichten` zuletzt: holt `liste` von
   `api/wartungslauf.php` (so oft, bis die genannten Spuren durch sind;
   `auslassen` für alles, was nicht in `spurfaelle` steht), packt mit
   `ortsdaten.gepackt_bauen()` auf die Stufe aus den Quelldaten (F-RD-07),
   dünnt für Stufe 3 mit der PHP-Referenz aus (`tools/proben/huelle/…` oder
   ein Aufruf von `spur_ausduennen()` über eine kleine CLI — die Umsetzung
   wählt; **nicht** eine dritte Fassung in Python), fährt den Rundlauf
   (öffnen und vergleichen) und schickt `tauschen`. Schnitte und
   Löschvermerke liegen dann schon (Reihenfolge Q-RD-07).
3. `beginn_tag`/`ende_tag` aus den Quelldaten in `stufe_schneiden()`
   (F-RD-10).
4. `einspielen/LIESMICH.md`: Aufruf, Reihenfolge, erwartete Zahlen.

*Ort:* `tools/referenzdatensatz/einspielen/einspielen.py`
(`stufe_loeschen()`, `stufe_verdichten()`), `ortsdaten.py` (Ausdünnung
anbinden), `einspielen/LIESMICH.md`.

*Abnahme:* Alle Stufen ohne Abbruch; danach im Referenzkonto je Fall die
erwartete Lage, gemessen über `spur2_umriss()`-Zahlen aus dem Kern eines
Backups (Stufe je Spur, `n_original`, `n`), `track_cuts` (Zahl der
Schnitte, der Löschvermerke); das Segment „Schnitt nimmt alles" nach dem
Lauf: Stufe 1 und in der Liste des Wartungslaufs noch genannt oder nicht
(F-RD-08 — die Zahl steht im Prüfprotokoll); `sitzungsprobe.py` 2 von 2.

### RD-04 Demo-Reset legt Stufe 2 und 3 selbst an (Nr. 500; Q-RD-02)

*Weg (bei Q-RD-02 (a)):*
1. `gh_gepackt_bauen($sec1, $art, $ref, $punkte, $stufe, $seqFrom, $nOriginal)`
   in `geraetehuelle_lib.php` — Innenform „B fest" wie `innen_gepackt()`
   in `referenz.py`, Bindung mit `platz_gepackt()`. Der Schlusskommentar
   der Datei wird ersetzt: Gepackt wird im Browser **und** für das
   Demo-Konto beim Reset; jeder andere Aufrufer ist ein Fehler (eine Zeile
   im Register `tools/zaehlung/register.php` mit Decke 1, Aufrufer
   `backup_lib.php`).
2. `edbak_restore()`, Zweig `'server'`, `$spurSchreiben`: trägt der
   Eintrag `stufe` ≥ 2, entsteht **ein** Stück über
   `gh_gepackt_bauen()` mit `n_original` aus dem Eintrag
   (`spur2_stueck_schreiben()` nimmt Stufe und `n_original` schon
   entgegen); sonst wie heute `gh_stuecke_bauen()`. Ein Eintrag mit
   `stufe` ≥ 2 und `n_original < count(track)` ist ein Fehler der Datei
   (`pruef->melde`), die Spur wird als Stufe 1 geschrieben und gezählt.
3. `demo_fixture_laden()`: Format 3 annehmen, Format 2 weiter.
3a. Bei Q-RD-10 (a): Im Zweig `'server'` bekommen Schnitte und
   Löschvermerke `erstellt_am` = Einspielzeit statt des Werts aus der
   Datei (F-RD-14); der Browserweg bleibt, wie er ist. Abnahme: nach dem
   Reset lässt sich der Schnitt der Stufe 1 im Demo-Konto zurücknehmen
   (Bedienweg, Meldung mit Punktzahl), und `schnitte_loeschen()` räumt den
   Vermerk.
4. Hüllenprobe: ein Vektor „gepackt, Stufe 2" und „gepackt, Stufe 3" in
   `referenz.py` → `vektoren.json`, `probe_server.php` hält
   `gh_gepackt_bauen()` byte-gleich dagegen (Anlass: Nr. 500).
5. `version.php` (Neben), `CHANGELOG.md`, `Technik.md` 4.21 („Die
   Fixture": Format 3; „Der Demobestand verschlossen": packt),
   `Backup-Format.md` nur, falls ein Feldname abweicht.

*Ort:* `server/geraetehuelle_lib.php`, `server/backup_lib.php`,
`server/demo_lib.php`, `tools/proben/huelle/referenz.py`,
`probe_server.php`, `tools/zaehlung/register.php`.

*Abnahme:* Hüllenprobe grün mit den zwei neuen Vektoren (Zahl der Fälle
vorher/nachher); Wartungslaufprobe und Ortsdatenprobe unverändert grün
(Muster `ortsdaten`, `wartungslauf`, `backup` in `pruefablauf.json`);
der Reset mit einer Format-3-Fixture aus einem **Handbestand** (eine Spur
je Stufe, von RD-01 aus einem Prüfkonto erzeugt) legt Stücke der Stufe 2
und 3 an, und der Browser öffnet sie (Bedienweg: Einsatz und Tagesansicht
des Demo-Kontos, Linie sichtbar). Resetdauer gemessen, Median aus drei
Läufen, gegen die heutigen 7,3 s.

### RD-05 Fixture, Referenzexporte und Sollstände neu (Nr. 388, 500; Q-RD-08)

*Weg:*
1. Referenzkonto von Null aufbauen (`lokal_einrichten.sh`, alle Stufen
   einschließlich RD-03), Jobs anhalten, `erzeugen.php` → neue
   `server/demo/fixture.json.gz`.
2. `referenz_export.mjs` → neue `referenz/*.edbak` und `*.zip`; die alten
   Dateien gehen (der Ordner darf je Format nur eine tragen,
   `neueste()`).
3. Beide Kreisläufe (`kreislauf.py --art edbak|csv --frisch`): neue
   Sollstände; **jede neue Abweichung einzeln begründet** in
   `vergleich/ausnahmen/*.json` (der Kopf dort erzählt die Messreihe
   fort). Erwartet: Stufe 3 ändert `n`, nicht `n_original`; der Umlauf
   darf die Stufe nicht verlieren (Einspielen einer Sicherung mit Stufe 3
   kommt als Stufe 3 zurück, `backup_lib.php` Kopf).
4. Abgleich Fixture ↔ Referenz (Q-RD-08) als Stufe-1-Riegel; Eintrag in
   `pruefablauf.json` (Muster für `server/demo/**` und
   `tools/referenzdatensatz/referenz/**`), Zeile in `Pruefablauf.md` 4
   (erzeugt der Prüfstand) und 6.11.
5. Zahlen nachziehen: `demo_pruefen.mjs`, `sichtpruefung.mjs`,
   `messprotokoll.*`, `LIESMICH.md` (Referenzdatensatz, Vergleich,
   Einspielen), `Handbuch.md` 3.2 und 11.6, `Technik.md` 4.21 (Größe der
   Fixture, Resetdauer), `Pruefliste.md` P-R4-16 (Kacheln).
6. `version.php` (Korrektur, weil die Fixture unter `server/` liegt und
   mit dem Tag ausgeliefert wird; die Umsetzung entscheidet, ob RD-04 und
   RD-05 in einer Fassung aufgehen), `CHANGELOG.md`.

*Ort:* `server/demo/fixture.json.gz`, `tools/referenzdatensatz/referenz/`,
`vergleich/ausnahmen/`, neu `fixture/abgleich.py`,
`tools/pruefstand/pruefablauf.json`, die genannten Dokumente.

*Abnahme:* Kreisläufe **0 unerklärt** (Einzelvergleiche und erklärte
Abweichungen mit Zahl, gegen die 347 566 / 22 und 9 120 / 1 021 von
heute); Abgleich grün, und einmal absichtlich rot (die alte Fixture gegen
die neue Referenz — genau F-RD-01); Demo-Reset auf der örtlichen Anlage
mit der neuen Fixture, Kacheln mit den neuen Zahlen; im Demo-Konto jeder
Fall aus Abschnitt 2 bedienbar (Tabelle mit Diensttag, Segment, was zu
sehen ist — die Vorlage der Handprüfliste in RD-06).

### RD-06 Abschluss

*Weg:*
1. Prüfdokument `Pruefdokument-RD-Referenzdatensatz.md` nach CLAUDE.md 7:
   zuerst, was nicht geprüft werden konnte; Grenzen der Mittel; Handprüfliste
   bis 20 Punkte — darunter P-S11-30 **neu gefasst** (im Demo-Konto auf
   Staging: das verdichtete Segment ohne Schnitt schneiden, dann einen
   Zeitraum löschen), P-S11-31 **neu gefasst** (nach Reset und Anmeldung
   öffnet ein Einsatz der Stufe 3 mit Karte, Linie und Phasen; ein
   Vergleichsbild aus dem Bilderlauf liegt diesmal **vor**), je ein Punkt
   für Mitternacht-Schnitt, Lücke, „Schnitt nimmt alles" im Demo.
2. Prüfstand im Zug fahren (`docs/Sandbox-Setup.md` 6.1a), Bericht in den
   Commit; Statusblock, Prüfprotokoll, Verlauf dieses Konzepts.
3. Pull Request (Q-RD-09). Nach dem Merge: R91-Prüfung auf Staging mit
   dem Demo-Konto (Prüfliste, Spalte „Wer": Claude), Erledigt-Zeile,
   Konzept löschen, Prüfdokument in die Prüfliste, Verlaufszeile — nach
   Freigabe der Betreiberin.

*Abnahme:* Stufe 1 grün auf dem Baum des Pull Requests; die drei
Prüfdokument-Fragen beantwortet (was fehlt, warum, wie nachzuholen).

## 6. Wildwuchs-Prüfung (E-RD-08)

Je Paket: was neu entsteht, was es wiederverwendet, was es ablöst.

| Paket | Neu | Wiederverwendet | Abgelöst |
|---|---|---|---|
| RD-01 | nichts — `erzeugen.php` wird umgebaut, nicht ersetzt; `fixture/LIESMICH.md` (fehlte) | `edbak_build()` (Kopf, Fenster), `spur2_lesen_viele()`, `demo_inhaltsschluessel()`, `demo_edk1_oeffnen()`, das Öffnen aus `tools/proben/huelle/geraet.php`, `riegelprobe.php` | der tote Weg über Punktlisten |
| RD-02 | drei Listen am Dienst nach dem Muster von `schnitte`; zehn Matrixmarken; zwei Lückenmarken in `spur.py` | Adressierung `segment: client_ref`, `ruhespur()` für die Übungsbewegung, `pruefen.py --matrix`, der Determinismus des Aufbaus | nichts |
| RD-03 | zwei Stufen in `einspielen.py` | `ortsdaten.py` (öffnen, bauen, packen — Format aus `referenz.py`), `api/wartungslauf.php`, `api/schneiden.php`, die Segmentsuche aus `stufe_schneiden()`, die PHP-Ausdünnung als Referenz (keine Python-Fassung) | nichts |
| RD-04 | `gh_gepackt_bauen()` in PHP (dritte Fassung eines Formats mit einer Referenz, wie Stufe 1); zwei Vektoren der Hüllenprobe; eine Registerzeile; eine Zeile für `erstellt_am` im Demo-Zweig (Q-RD-10) | `spur2_stueck_schreiben()` mit `stufe`/`n_original` (nimmt es schon), `edbak_restore()` Zweig `'server'`, Feldnamen der Nutzlast 13 | der Satz „Gepackt wird nur im Browser" |
| RD-05 | `fixture/abgleich.py` (Q-RD-08; sonst nichts — Referenzexporte und Sollstände werden erneuert, nicht neu gebaut) | `referenz_export.mjs`, `kreislauf.py`, `ausnahmen/*.json`, `pruefablauf.json` | die Fixture vom 27.09.2026 |
| RD-06 | Prüfdokument (Pflicht, CLAUDE.md 7) | Prüfliste, Prüfstand | — |

**Was bewusst nicht entsteht:** keine Python-Ausdünnung, kein eigener
Spurweg in `spur_lib.php`, kein Fassungswechsel der Nutzlast, kein
zweites Demo-Schlüsselpaar, keine neue Adressierung in den Quelldaten,
kein neues Verzeichnis unter `tools/`.

## 7. Prüfprotokoll

Je Paket Mittel und Zahl, nachgetragen von der Umsetzung. Was hier steht,
ist gemessen; was fehlt, steht im Prüfdokument an erster Stelle.

| Paket | Mittel | Gemessen | Ergebnis |
|---|---|---|---|
| RD-01 | `riegelprobe.php`, Reset mit erzeugter Fixture | — | — |
| RD-02 | `quelldaten/pruefen.py`, `generator/pruefen.py`, `diff -r` | — | — |
| RD-03 | `einspielen.py`, `sitzungsprobe.py`, Umrisszahlen | — | — |
| RD-04 | Hüllenprobe, Wartungslaufprobe, Ortsdatenprobe, Reset mit Handbestand | — | — |
| RD-05 | Kreisläufe csv/edbak, `abgleich.py`, Reset, Bedienweg Demo | — | — |
| RD-06 | Prüfstand (Bericht im Commit) | — | — |

**Grenzen, die jetzt schon feststehen:** Die Staging-Prüfung mit dem
Demo-Konto (P-S11-30 neu) geht erst nach dem Merge (R91). Ein echter
Safari bleibt Handprüfung. F-RD-08 (Fälligkeit nach „Schnitt nimmt
alles") misst erst RD-03.

## 8. Verlauf

| Datum | Was |
|---|---|
| 05.11.2026 | Konzept angelegt: Befund F-RD-01 bis -13, Fragen Q-RD-01 bis -09, Entscheidungen E-RD-01 bis -07, Pakete RD-01 bis -06. Fahrplanzeile 24 auf „Konzept", Backlog-Spanne 510–519 reserviert. |
| 05.11.2026 | Nach Rückfrage der Betreiberin (Wildwuchs, Q-RD-04, Übungstage): F-RD-04 und F-RD-11 berichtigt (Python hat das Format einmal; die Segmentkennung überlebt den Aufbau), E-RD-04 auf die vorhandene Adressierung `segment: client_ref` umgestellt, Q-RD-04 mit Lesart (d) „Übungssegmente, zwei auf Stufe 1, eines auf Stufe 2" als Vorschlag, E-RD-08 und Abschnitt 6 (Wildwuchs-Prüfung) neu; Matrixmarken von 14 auf 12 (10 Fälle). |
| 05.11.2026 | Zweite Rückfrage („geschnittene Tage?"): F-RD-14 (Demo-Schnitte gelten auf jeder Anlage als alt, Rücknahme antwortet 409 `altschnitt`), Q-RD-10 mit Vorschlag (a), die drei bestehenden Schnitte auf Stufe 1/2/3 verteilt (Q-RD-03), Matrix „Schnittzustand × Stufe" in `pruefen.py`, Marken auf 14. |
