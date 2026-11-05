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
| RD-01 | Fixture wieder erzeugbar (Nr. 388) | **erledigt** (05.11.2026) |
| RD-02 | Quelldaten: die fehlenden Spurfälle, Matrix, Generator | **erledigt** (05.11.2026) |
| RD-03 | Einspielen: Stufen `verdichten` und `loeschen` | **erledigt** (05.11.2026) |
| RD-04 | Demo-Reset legt Stufe 2 und 3 selbst an (Fixture-Format 3) | **erledigt** (05.11.2026, Web 23.8.0) |
| RD-05 | Fixture, Referenzexporte und Sollstände neu; Dokumente | **erledigt** (05.11.2026, Web 23.8.1) |
| RD-07 | Demo-Konto: Durchgang über jeden Bedienweg, jeder Fehler sofort behoben (E-RD-19) — **vor RD-05** | **erledigt** (05.11.2026) |
| RD-06 | Abschluss: Prüfdokument, Prüfliste (P-S11-30, -31), Pull Request | **Pull Request offen** (05.11.2026) — wartet auf Merge und Freigabe |

**In Arbeit:** nichts — alle Pakete umgesetzt, der Pull Request wartet auf die Betreiberin. Nach dem Merge: R91-Prüfung auf Staging (Prüfdokument, Spalte „Wer": Claude), dann nach Freigabe Erledigt-Zeile, Prüfliste, Backlog (Nr. 388, 500), Konzept löschen. Die
Umsetzung läuft auf dem Konzeptzweig `claude/admiring-sagan-mql8t4`
(Entscheid der Betreiberin zu Beginn der Umsetzungssitzung). **Es hakt:**
nirgends. *(Bis RD-01 stand bei RD-02 und RD-04 „wartet auf Q-RD-…" —
seit der Freigabe überholt.)*
Reihenfolge der Umsetzung: RD-01 → RD-02 → RD-03 → RD-04 → RD-07 → RD-05 →
RD-06 (E-RD-06).

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

**Dazu, seit der Freigabe (E-RD-19):** Alles, was am Demo-Konto nicht
einwandfrei geht und in diesem Schritt auffällt, wird **in diesem Schritt**
behoben — gleich, ob es die Fixture, den Reset, einen Bedienweg oder (wie
F-RD-08) den Browserweg betrifft. Ein Fund wird ein `F-RD-NN`, die
Behebung steht im Paket, das ihn gefunden hat, oder in RD-07; nichts davon
wandert ins Backlog. Die Grenze: Ein Fehler, der **nicht nur** das
Demo-Konto trifft und eine Schemaänderung oder einen Umbau außerhalb der
berührten Dateien verlangt, ist eine Rückfrage, kein stiller Umbau.

**Nicht Teil:** Schema und Migrationen — `track_stuecke` und `track_cuts`
tragen heute alles, was die neuen Fälle brauchen; kein `update.php` nach
dem Deploy. Nicht Teil: `watch/`, `android/`, `.github/`, der
JSON-Vertrag. Nicht Teil: neue Funktionen (etwa Schneiden aus einer
Einsatzspur, Q-RD-04 (c)) — ein Wunsch ist kein Fehler und wird ein
Backlog-Punkt. Die Auslieferung (`web-v…`) ist nicht Teil (CLAUDE.md 3).
*(Bis zur Freigabe stand hier auch der Browserweg des Wartungslaufs als
„nicht Teil"; seit E-RD-19 gilt das nicht mehr, wenn er im Demo-Konto
einen Fehler zeigt.)*

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
Abnahme; bestätigt er sich, wird er in RD-04 behoben (E-RD-19; bis zur
Freigabe stand hier „Backlog-Punkt, nicht Teil dieses Schritts").

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

**F-RD-15 — Die Aufrufliste des Einspiellaufs baute das Referenzkonto
nicht vollständig nach** (gefunden in RD-01). `einspielen/LIESMICH.md`
nannte den CSV-Import nicht (`browser/csv_import.mjs`, vier Einsätze mit
`origin import`), und `lokal_einrichten.sh` legt das Demo-Konto aus der
Fixture an — `--stufen konto` meldet dann „besteht bereits" und füllt den
Fixture-Bestand auf, statt ein Referenzkonto zu bauen. Gemessen: ohne den
Import 105 statt 109 Einsätze. Behoben in RD-01: beide Schritte stehen in
der Aufrufliste.

**F-RD-16 — Die Riegelprobe war auf einer frischen Anlage nicht fahrbar
und an einer Stelle grün ohne Gegenstand** (gefunden in RD-01, Vorab).
Das Negativkonto `umlauf-csv@example.invalid` entsteht nur in einem
Kreislauf; ohne es endet die Probe mit Rückgabe 2. Mit einem anderen
Konto: 3 von 10 rot, und Fall 2a („Rückgabe 2") war grün, weil
`erzeugen.php` vor jedem Riegel abbrach. Behoben in RD-01: Die Probe nennt
den Befehl, der das Konto anlegt, prüft Rolle und Rundenzahl des
Negativkontos vorab, und ein Fall misst das Ergebnis (Format 3 mit Punkten).

**F-RD-17 — Der Reset baut eine Einsatzhülle nur, wo Orte oder
Reanimation stehen** (gefunden in RD-01, keine Behebung). Das Gerät legt
zu jedem Einsatz mit Phasen eine Hülle an, auch ohne Koordinate; der Reset
(`edbak_restore()`, Zweig `'server'`) nur bei Inhalt. Gemessen: 106 Hüllen
im Referenzkonto, 101 nach dem Reset; die fünf übrigen tragen keine Orte und
keine Reanimation, und der Rundlauf Fixture → Reset → Fixture ist an Orten,
Reanimation, Spur, Schnitten und `pat_blob` deckungsgleich. Kein Fehler,
aber ein Zählunterschied, den der Abgleich in RD-05 nicht für einen halten
darf. Dazu ein Werkzeugfund für RD-03: `einspielen.py`, Stufe
`schneiden`, druckt „None geblieben" — `api/schneiden.php` antwortet seit
Web 22.2.0 ohne `geblieben` (die Punkte bleiben gesperrt im Segment).

**F-RD-18 — Die Schnittfenster der drei bestehenden Schnitte liegen auf
Stillstand** (gefunden in RD-02). Gemessen an der Generator-Ausgabe: In
`ar-12-1288367401` (D08) bewegt sich das Fahrzeug 9,3 km in der ersten Stunde
(der Rückweg, mit dem das Segment beginnt) und danach 0,3 km je Stunde —
GPS-Zittern am Standort; in D18 21,5 km Rückweg, dann 0,3 km/h; in D19 nur
Zittern. Die Annahme von Q-RD-04 und RD-02 Schritt 2 („die Bewegung
beschreibt `wegpunkte.py` wie bei den drei Quellsegmenten der Schnitte; der
Generator kann das schon, `ruhespur()`") trifft nicht zu: Eine
einsatzförmige Bewegung in einem Ruhesegment gab es nicht → E-RD-20.

**F-RD-19 — Kein Ruhesegment des Bestands läuft über Mitternacht** (gefunden
in RD-02). In D06, D14 und D22 überdeckt jeweils ein Einsatz 00:00 (D22:
`m-11-4172630859`, 23:50 bis 01:19); das Segment `ar-12-9295332214` aus dem
Befund zu Nr. 500 gibt es nicht mehr. Für E-RD-13 („Segment über Mitternacht
in D22") fehlte damit das Segment → E-RD-22.

**F-RD-20 — Der Wartungslauf verweigert sich dem Referenzkonto** (gefunden
in RD-03). Auf der Prüfanlage ist das Referenzkonto zugleich das Demo-Konto
(`demo_kennzeichnen.php`, sonst entstünde keine `edk1:`-Hülle), und
`api/wartungslauf.php` antwortet dem Demo-Konto mit leeren Listen und
`409 demo` (Nr. 459). Der Weg aus RD-03 („über `api/wartungslauf.php`")
lief so ins Leere. Gelöst im Werkzeug, nicht im Server: Die Stufe
`verdichten` hebt die Kennzeichnung auf (`demo_kennzeichnen.php
--aufheben`) und setzt sie im `finally` wieder; dazwischen arbeitet nur das
Python-Werkzeug, eine stille Umstellung der Hülle (nur im Browser) kann
nicht laufen. Gemessen danach: Hülle `edk1:`, Kennzeichnung zurück.

**F-RD-21 — Die Auslassliste des Wartungslaufs reicht 200 weit**
(gefunden in RD-03). `spur2_faellig_kandidaten()` liest `array_slice(
$auslassen, 0, 200)`; der Bestand hat 223 Spuren, jede auf Stufe 1 ist
fällig. Ein Client, der „bis die Liste leer ist" fragt, läuft im Kreis
(erster Lauf: nach 60 Listen kein Ende, alle 20 Aufträge waren da längst
getauscht). Im Browser unschädlich — er hört nach 20 auf und merkt sich
die Ausgelassenen je Sitzung; das Werkzeug hört auf, sobald alle Aufträge
erledigt sind. Kein Serverfehler, keine Behebung.

**F-RD-22 — „Rückgängig" nach dem Verdichten löschte die geschnittenen
Punkte, in jedem Konto** (gefunden in RD-04). `schnitt_rueckgaengig()` geht
davon aus, dass die Punkte eines Schnitts im Segment liegen („waren im
Segment nie fort"). Der Wartungslauf öffnet das Segment aber ohne die
gesperrten Punkte und packt nur den Rest — danach liegen sie allein im
Einsatz, und die Rücknahme löscht Einsatz und Spur. Im Demo-Bestand ist das
D18 (Schnitt auf Stufe 2). Behoben in RD-04 (E-RD-19): `409 verdichtet`,
wenn ein Stück der Stufe 2 oder 3 zur Zeit des Schnitts oder später
geschrieben wurde; ein Schnitt aus einer schon gepackten Spur (P-S11-30)
bleibt rücknehmbar.

**F-RD-23 — Die Schemaprobe kann die Anlage in den Wartungsmodus setzen**
(gefunden in RD-04, Prüfstand). `tools/schemaprobe/probe.php` ersetzt für
ihre Laufzeit `server/config.php` durch eine Wegwerfdatei mit einer frischen
Probe-Datenbank, in der Migrationen offen sind. Trifft in diesem Fenster eine
angemeldete Anfrage die Anlage, schaltet der Torwächter die Wartung ein
(`auth_guard.php`, `wartung_einschalten('torwaechter')`), und die Datei
bleibt liegen, wenn `config.php` zurück ist. Gemessen: `wartung.lock`
14:37:25 „torwaechter", am Ende der Schemaprobe; danach 503 für Bilderlauf,
Bedienprobe, Ortsdaten- und Wartungslaufprobe. Dieselbe Erscheinung steht
in `Sandbox-Setup.md` 6.1a als ungeklärt (16.10.2026). Die zeitgleiche
Anfrage ließ sich nicht zuordnen; der Wiederholungslauf blieb sauber. Kein
Fehler der Anwendung, keine Behebung in diesem Schritt — Backlog Nr. 510.

**F-RD-24 — Ein Ruhesegment mit Schnitt lief bei 390 px über** (gefunden in
RD-04 vom Bilderlauf am neuen Demobestand — RD-07-Art, sofort behoben,
E-RD-19). `schneiden.js` baute die Knopfreihe der Segmentzeile in jeder
Breite; mit „Schneiden" und „Schnitt zurücknehmen" wurde `div.zeile-aktionen`
512 px breit (Überlauf +122 bei 390, Seiten „Nachtdienst" und „ohne
Standort"). Das freigegebene Muster ist Design 9.3: Knopfreihe ab 720 px,
darunter „⋯" mit Blatt — jetzt im selben Markup wie `ui_zeilenaktionen()`.
Bisher sah es niemand, weil der Bilderlauf keinen Tag mit Schnitt in der
Tagesübersicht aufnahm.

**F-RD-25 — Nach einer neuen Fixture blieb das alte Schlüsselpaar des
Demo-Kontos stehen** (gefunden in RD-04, Prüfstand). Der Reset schreibt
Schlüsselhülle und Inhaltsschlüssel aus der Fixture, `demo_paar_sicherstellen()`
fragte aber nur „ist ein Paar da?". Stammt die Fixture aus einem anderen
Referenzkonto — jede neue tut das —, liegt `kp_privat` unter dem alten
Schlüssel: Der Browser öffnet keine Spur, keinen Ort, keine Reanimation und
läuft beim Versuch, das Paar zu ersetzen, in die Mengenbremse. Gemessen:
`kontopaar` nach 5 Versuchen gesperrt, 87 Konsolenfehler (429) im
Bilderlauf, fünf Wege der Bedienprobe „kein Einsatz mit Aufzeichnung".
**Hätte Produktiv mit der Fixture aus RD-05 getroffen.** Behoben: Paar neu,
wenn der private Teil mit dem Inhaltsschlüssel der Fixture nicht aufgeht.

**F-RD-26 — `demo_pruefen.mjs` prüfte gegen den Bestand vor dem
Demo-Ausbau** (gefunden in RD-07). Die Sollzahlen standen fest im Skript —
83 Einsätze, 15 Diensttage, 95 Segmente —, die eingecheckte Fixture trägt
seit dem Demo-Ausbau 104 / 21 / 118. Das Skript war damit gegen jede
Fixture seither rot, und seine Anleitung meldete „24 Prüfungen, 0
Befunde"; niemand fuhr es (Nr. 391: „von Hand"). Behoben: Die Sollzahlen
kommen aus der installierten Fixture (`server/demo/fixture.json.gz` oder
`DEMO_FIXTURE`) — damit ist F-RD-12 für dieses Werkzeug erledigt.

**F-RD-27 — Der Kern einer Sicherung nennt `n_original` bei Stufe 1 und 2
anders als das Konto** (gefunden in RD-05, beim ersten Lauf des Abgleichs).
`edbak_build()` schreibt dort `n` als `n_original` („die Zahlen beschreiben,
was in der Datei steht"), auch wo die Marke des Stücks weiter reicht — nach
einer Sequenzlücke oder wenn geschnittene Punkte beim Packen gefallen sind.
Gemessen: drei Segmente der Stufe 2. Die Fixture trägt die Marke, der Kern
nicht; das ist kein Fehler der Sicherung (Einspielen packt aus den Punkten
neu), aber ein Abgleich, der beide Zahlen gleichsetzt, wäre falsch rot.
Gelöst im Abgleich: `n_original` wird nur bei Stufe 3 verglichen, und
Stufe 1/2 nicht unterschieden (der Kern nennt beide 2). Der Umlauf selbst
verliert nichts: 223 von 223 Spuren kommen mit Stufe und `n_original`
zurück.

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
| E-RD-06 | Die Reihenfolge der Pakete ist zwingend: RD-01 (Erzeuger) und RD-04 (Reset) sind Code, RD-02 und RD-03 sind Bestand, RD-07 (Demo-Durchgang, seit E-RD-19) läuft mit einer Vorab-Fixture aus RD-01 vor RD-05, RD-05 erzeugt daraus Fixture und Sollstände. Die Fixture kann erst entstehen, wenn das Referenzkonto alle Fälle trägt (RD-02, RD-03) **und** der Erzeuger sie lesen kann (RD-01); eingespielt werden kann sie erst, wenn der Reset Format 3 versteht (RD-04). | Konzept, 05.11.2026 |
| E-RD-07 | Jeder neue Fall bekommt eine Zeile in `Pruefliste.md` nur, wenn er von Hand zu prüfen ist (P-S11-30 neu gefasst, P-S11-31 neu gefasst, ein Punkt je Spurfall in der Demo); was `pruefen.py`, der Kreislauf und der Abgleich messen, steht im Prüfprotokoll, nicht in der Prüfliste (CLAUDE.md 7). | Konzept, 05.11.2026 |
| E-RD-09 | Q-RD-01: (a) — `erzeugen.php` liest auf der Referenzmaschine und öffnet die Hüllen mit dem Schlüsselmaterial des Demo-Kontos. | Betreiberin, 05.11.2026 („Alle wie vorgeschlagen") |
| E-RD-10 | Q-RD-02: (a) — `gh_gepackt_bauen()` in PHP, gemessen von der Hüllenprobe; die Fixture trägt die Punkte ausgedünnt mit `stufe` und `n_original`. Damit gilt E-RD-02. | wie E-RD-09 |
| E-RD-11 | Q-RD-03: je Art etwa fünf Spuren auf Stufe 2 und fünf auf 3; die drei bestehenden Schnitte auf Stufe 1, 2 und 3; der Rest Stufe 1. | wie E-RD-09 |
| E-RD-12 | Q-RD-04: (d) — drei Übungssegmente mit ungeschnittener einsatzförmiger Spur, zwei auf Stufe 1, eines auf Stufe 2 (= Segment für P-S11-30); (a), (b) entfallen, (c) nicht Teil. | wie E-RD-09 |
| E-RD-13 | Q-RD-05: (a) — Segment über Mitternacht im Nachtdienst D22, Stufe 2, mit Schnitt nach Mitternacht (`beginn_tag = 1`). | wie E-RD-09 |
| E-RD-14 | Q-RD-06: (c) in zwei Segmenten — eine Sequenzlücke, eine Zeitlücke, beide als feste Marken im Generator. | wie E-RD-09 |
| E-RD-15 | Q-RD-07: erst schneiden, dann löschen, dann verdichten; ein zweites Segment nur mit Löschvermerk. | wie E-RD-09 |
| E-RD-16 | Q-RD-08: `fixture/abgleich.py` als Stufe-1-Riegel ohne Installation (Anlass Nr. 388). | wie E-RD-09 |
| E-RD-17 | Q-RD-09: Umsetzung in eigener Sitzung auf diesem Zweig; Pull Request am Ende von RD-06; Tags danach durch die Betreiberin. | wie E-RD-09 |
| E-RD-18 | Q-RD-10: (a) — der Demo-Zweig von `edbak_restore()` stempelt `erstellt_am` der Schnitte und Löschvermerke auf die Einspielzeit (F-RD-14). | wie E-RD-09 |
| E-RD-19 | **Jeder Fehler am Demo-Konto, der in diesem Schritt auffällt, wird in diesem Schritt behoben** — auch im Browserweg, auch wenn er nicht in Nr. 388 oder 500 steht. Dafür ein eigenes Paket RD-07 (Durchgang über jeden Bedienweg des Demo-Kontos) vor der Fixture; Funde als `F-RD-NN` mit Behebung und Messung im Paket. Grenze: siehe Auftrag. | Betreiberin, 05.11.2026 („Repariere alles, was mit dem Demo-Konto nicht perfekt funktioniert, direkt mit") |
| E-RD-20 | **Übungssegmente über eine Liste `uebungen` am Dienst** (`segment`, `beginn`, `ende`, `vorbild`), adressiert wie `schnitte` (F-RD-18). Die Bewegung fährt oder fliegt die Strecke eines Einsatzes desselben Dienstes (`vorbild`) von der Basis zum Einsatzort und zurück — am Boden auf dessen gespeicherter Straßengeometrie (`generator/routen/`), in der Luft zu dessen Wegpunkt `ort`. Keine neue Koordinate, kein Netzabruf. Abweichung von RD-02 Schritt 2 („die Übungssegmente brauchen kein Feld"). | Umsetzung RD-02, 05.11.2026 |
| E-RD-21 | **`beginn_tag`/`ende_tag` stehen nicht als Feld in der Quelle**: Der Tagesversatz folgt aus dem Datum in `beginn`, das jeder Zeitstempel ausgeschrieben trägt; zwei Angaben derselben Sache liefen auseinander (vgl. E-DA-09). `einspielen.py` rechnet ihn in RD-03 aus Datum und `dienst.day`. Abweichung von RD-02 Schritt 2. | Umsetzung RD-02, 05.11.2026 |
| E-RD-22 | **D22: E1 (`m-11-4172630859`) beginnt 90 Minuten früher** (22:20 bis 23:49 statt 23:50 bis 01:19), Strecke, Dauer und Phasenabstände unverändert (F-RD-19). Damit läuft das Segment `r-11-8948069843` von 23:49 bis 01:40, und der Schnitt nach Mitternacht liegt darin; „Einsätze vor und nach Mitternacht" bleibt erfüllt (E2 um 01:40), „Einsatz über Mitternacht" tragen weiter D06 und D14. | Umsetzung RD-02, 05.11.2026 |
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

*Gebaut (05.11.2026):* `erzeugen.php` neu nach dem Weg oben. Den Bestand
holt es aus `edbak_build()` (Kopf, dann Fenster zu `EDBAK_FENSTER`, die
Spurzuordnung aus `_spur_index`), die Stücke über `spur2_lesen_viele()`.
Den privaten Teil des Kontopaars liefert `kp_zustand()` unter dem
Inhaltsschlüssel aus `demo_inhaltsschluessel()`. Geöffnet wird mit
`gh_probe_oeffnen()`: Stufe 1 binär, Stufe 2 und 3 entpackt („B fest",
Bindung `seq:punkte:stufe:n_original`). Gepackte Punkte bekommen
`seq_from + i`, weil sie keine eigene Nummer tragen. Phasenorte gehen über
Nummer **und** Zeit an die Phasen des Kerns, nicht über die Position;
`ort_huelle` fällt weg (sie hängt am Paar der Referenzanlage), jede Phase
trägt `lat`/`lon`. Abbruch (Rückgabe 2, keine Datei) bei: Riegel auf
Rolle, Rundenzahl, `edk1:` (unverändert, vor jedem Öffnen); Hülle oder
Stück geht nicht auf; Punktzahl ≠ `n` des Umrisses; Phasenort ohne Phase;
Spur mit Stücken verschiedener Stufen; Klartext im Kern
(`edbak_klartext_fund()`). `quelle` nennt den Commit und ob Quelldaten
oder Generator davon abweichen. `riegelprobe.php` mit einem Fall mehr
(11 statt 10, F-RD-16); `fixture/LIESMICH.md` neu (40 Zeilen, Anlass
Nr. 388); `einspielen/LIESMICH.md` mit CSV-Import und dem Hinweis auf das
Demo-Konto aus `lokal_einrichten.sh` (F-RD-15); `Technik.md` 4.21 („Format
3", „Warum sie nicht aus einer `.edbak` kommen kann" neu gefasst,
Riegelprobe 11 von 11) und der Verzeichnisbaum. Keine Fassung
hochgestuft: nur `tools/` und `docs/`.

*Probleme und Lösung:* (1) Die erste Fixture hatte 105 Einsätze statt 109
— der CSV-Import fehlte im Referenzkonto (F-RD-15), nachgeholt. (2) Der
Rundlauf zeigte Phasen ohne `lat`/`lon`-Schlüssel an den fünf Einsätzen
ohne Hülle — vereinheitlicht. (3) Der Reset überschreibt das Referenzkonto
(es ist das Demo-Konto der Anlage); gemessen wurde deshalb auf einer
gesicherten Datenbank (`mariadb-dump`), danach zurückgelegt und die
eingecheckte Fixture per `git checkout` wiederhergestellt. (4) Der Zweig
für Stufe 2 und 3 lässt sich am heutigen Bestand nicht belegen (alles
Stufe 1) — belegt mit Prüfvektoren aus `referenz.py` (Prüfprotokoll).

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

*Gebaut (05.11.2026):* Vier Listen am Dienst, adressiert über `segment`
bzw. `einsatz` wie `schnitte` (E-RD-04): `verdichten` (20 Aufträge: je 5
Einsatzspuren und 5 Segmente auf Stufe 2 und auf 3, jede Gruppe mit Luft
und Boden; Stufe 3 mit Reanimation an `m-11-7761204385`, D09),
`loeschungen` (D18 neben dem Schnitt, D13 allein), `luecken` (Sequenz in
D04 `ar-12-1378533568`, 20 Nummern; Zeit in D02 `r-11-8559806448`,
15 Minuten), `uebungen` (D09 Luft und D17 Boden auf Stufe 1, D11 Boden auf
Stufe 2 = P-S11-30; E-RD-20). Zwei neue Schnitte: D20 über das ganze
Segment `ar-12-2837881200` und D22 nach Mitternacht in `r-11-8948069843`
(E-RD-22); die drei bestehenden auf Stufe 1 (D08), 2 (D18) und 3 (D19).
Bestand damit **111 Einsätze** (106 + 5 Schnitte), Quelldaten weiter 106.
`pruefen.py`: 16 Matrixzeilen „Spurfälle", jede Marke **gerechnet** (keine
getippt), Soll-Belegung `SPURFALL_ANZAHL` und die Matrix „Schnittzustand ×
Stufe" im Kopf, Stichtag für Stufe 3 (`STUFE3_STICHTAG` 28.09.2026),
Sachprüfungen je Liste (Ziel im Dienst, Fenster im Segment, nicht am
neuesten Tag, kein Überlappen, nie das ganze Segment löschen, ganzes-Segment-
Schnitt nicht verdichten, Luft und Boden je Stufe). `aufbauen.py` meldet
verwaiste Verweise nach dem Neubau. Generator: `spur.luecke()`,
`uebungsspur()` in `erzeugen.py`, Sendeplan springt über verlorene Nummern,
`ausgabe/luecken.json`; `generator/pruefen.py` lässt den Nummernsprung nur
an einer erklärten Sequenzlücke zu und verlangt beide Lücken. Schema um die
vier Listen erweitert; `FORMAT.md` (Abschnitt „Die Spurfälle", Schnitt nach
Mitternacht, 111, `D01…D22`), `generator/LIESMICH.md`, Kopf von
`erzeugen.py`, `Technik.md`-Verzeichnisbaum. Keine Fassung hochgestuft.

*Probleme und Lösung:* (1) Keine einsatzförmige Bewegung in den
Quellsegmenten (F-RD-18) → Liste `uebungen` mit Vorbild (E-RD-20). (2) Kein
Segment über Mitternacht (F-RD-19) → E1 in D22 vorgezogen (E-RD-22). (3)
`beginn_tag` als Feld wäre eine zweite Wahrheit neben dem Datum → abgeleitet
(E-RD-21). (4) Die Zahl der Marken weicht vom Konzept ab: sechzehn
Matrixzeilen statt „vierzehn" Marken — dazu kamen „Übungssegment auf
Stufe 2" und die Trennung Schnitt-Stufe 1/2/3 als eigene Zeilen;
`schnitt-stufe-1` und `-2` sind je zweimal belegt (D20, D22 kommen dazu).
*Für RD-03 und RD-05 vorgemerkt:* `einspielen.py` braucht den Tagesversatz
aus dem Datum (sonst schneidet es D22 am falschen Tag) und die Stufen
`loeschen`/`verdichten` mit `einsatz`-Zielen; „109" steht noch in
Rahmenplan R4 (Registerzeile, Anlass RD-06), `Technik.md` 4.21,
`tools/referenzdatensatz/LIESMICH.md` (Kreislauf) — nachzuziehen, wenn der
Bestand eingespielt und gemessen ist.

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

*Gebaut (05.11.2026):* `einspielen.py`: Stufen `loeschen` (nach
`schneiden`) und `verdichten` (zuletzt), `segment_finden()` als gemeinsame
Segmentsuche für Schneiden und Löschen, `tagesversatz()` aus dem Datum
(E-RD-21) für `beginn_tag`/`ende_tag` in beiden; die Ausgabe der Stufe
`schneiden` ohne das tote „geblieben" (F-RD-17). `loeschen` öffnet wie
`schneiden.js` ohne Sperren, verschließt den Rest als Gerätestücke oder
gepackt in derselben Stufe und fährt vorher einmal die Probe „ganze
Aufzeichnung" (409 `alles`, F-RD-09). `verdichten` holt die Liste des
Wartungslaufs, lässt aus, was nicht in `verdichten` steht, öffnet, dünnt
für Stufe 3 mit der PHP-Referenz aus (`tools/proben/huelle/
ausduennen_cli.php` → `spur_ausduennen()` und
`spur_ausduennung_pruefen()`, über `ortsdaten.ausduennen()`), packt, fährt
den Rundlauf und tauscht; die Demo-Kennzeichnung ruht dabei (F-RD-20).
Die Ortshöhe trägt die Stufe nicht nach (der Nachtrag hat sie gesetzt).
`demo_kennzeichnen.php --aufheben`. Dokumente: `einspielen/LIESMICH.md`,
`proben/huelle/LIESMICH.md`.

*Probleme und Lösung:* (1) Wartungslauf verweigert das Demo-Konto
(F-RD-20) → Kennzeichnung ruht. (2) Auslassliste begrenzt (F-RD-21) →
Abbruch nach erledigten Aufträgen. (3) Der Container wurde während des
ersten Aufbaus neu gestartet (Dienste weg, Dateien da); Startfolge 6.1a
gefahren, das Demo-Konto stand danach wieder mit dem Bestand aus RD-01 —
ein Lauf von `--stufen konto` hätte ihn aufgefüllt („besteht bereits");
angehalten nach `kennzeichnen`, Konto entfernt, neu aufgebaut. Lehre für
`einspielen/LIESMICH.md` (steht dort seit RD-01): das Demo-Konto vor
`--stufen konto` entfernen und nachsehen.

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

*Gebaut (05.11.2026, Web 23.8.0):* `gh_gepackt_innen()` und
`gh_gepackt_bauen()` in `server/geraetehuelle_lib.php` (Schlusskommentar
ersetzt); `edbak_restore()` Zweig `'server'`: `$spurSchreiben` nimmt den
Eintrag mit und baut bei `stufe` ≥ 2 **ein** Stück mit `n_original`
(Widerspruch → `pruef->melde`, Stufe 1). Schnitte und Löschvermerke des
Demo-Kontos stempelt `demo_bestand_einspielen()` danach auf **eine Sekunde
vor dem Reset** (`schnitte_stempeln()` in `spur_lib.php`, E-RD-18);
`edbak_restore()` lässt `erstellt_am` weiter reisen (E-R64-12). `demo_fixture_laden()`: Format 1 bis 3, höher
abgewiesen. Hüllenprobe: Block `gepackt` in `referenz.py` (eigener Block,
nicht in `FAELLE` — Uhr- und App-Vektoren bleiben byte-gleich, gemessen),
`probe_server.php` hält Innenform und Hülle byte-gleich dagegen.
Registerzeile Z49 (Decke 1, Aufrufer `backup_lib.php`). Dazu zwei Fehler
für alle Konten (E-RD-19): `schnitt_rueckgaengig()` mit
`spur2_seit_gepackt()` (F-RD-22) und `spur2_faellig_kandidaten()` ohne
Spuren, deren Stücke jedes unter einem Vermerk liegen (F-RD-08) — im
Server, nicht im Browser: Der Browser kann nichts tauschen, wo kein Punkt
ist; die Liste soll die Spur gar nicht nennen. Dokumente: `version.php`
(23.8.0, Neben), `CHANGELOG.md`, `Technik.md` 4.21 (Format 3, „Der
Demobestand verschlossen": packt), Schneiden (Rücknahme, Tabelle),
Wartungslauf (Fälligkeit, Innenform), `Handbuch.md` (Rückgängig nach dem
Verdichten), LIESMICH der Hüllenprobe und der Zählung.

*Probleme und Lösung:* (1) F-RD-22 aus dem Code gelesen, als die Frage
„welches `erstellt_am` darf der Reset setzen" kam — die Einspielzeit allein
hätte den D18-Schnitt rücknehmbar gemacht und dabei Punkte gelöscht; die
neue Bedingung (`geaendert_am >= erstellt_am`) deckt den gleichzeitigen
Stempel des Resets. (2) Ein deutsches Anführungszeichen in einer
PHP-Zeichenkette mit doppelten Anführungszeichen brach `probe_server.php`.
(3) Die Schemaprobe trug einen Prüffall, den die neue Fälligkeitsregel anders las (Nullzeiten) — berichtigt, nicht die Regel aufgeweicht. (4) Der erste Wurf stempelte im Zweig `'server'` von `edbak_restore()` die
Einspielzeit — die Wiederherstellungsprobe (Teil 11, 14) fährt diesen Zweig
und verlangt, dass `erstellt_am` reist: 2 rot. Dabei fiel auf, dass die
Einspielzeit selbst falsch wäre: Die Vermerke entstehen nach den Stücken,
ein Reset dauert rund 3 s, und ein Vermerk, jünger als das gepackte Stück,
ließe die Rücknahme von D18 zu und verlöre Punkte. Jetzt: Stempel nach dem
Einspielen, eine Sekunde vor dem Reset, nur im Demo-Konto. (5) Der
Prüfstand lief zweimal in Störungen: einmal Wartungsmodus mitten im Lauf
(F-RD-23), einmal ein echter Fund im Bilderlauf (F-RD-24, behoben in
`schneiden.js`, Design 9.3), und beim dritten Lauf F-RD-25 — ausgelöst,
weil ich zwischen den Läufen die Fixture gewechselt hatte; ohne diesen
Wechsel wäre der Fehler erst auf Produktiv aufgefallen. Die Mengenbremse der
Sandbox danach geleert (`rate_limits`, Töpfe `kontopaar`, `demo`, `demog`).
*Nicht getan:* F-RD-08 im Browser (`wartungslauf.js`) — mit dem Server-
Filter unnötig; `Backup-Format.md` — kein Feldname weicht ab.

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

*Gebaut (05.11.2026, Web 23.8.1):* Referenzkonto von Null wie in RD-03,
`erzeugen.php` → neue Fixture (111 Einsätze, 223 Spuren, 67 016 Punkte,
Stufe 1/2/3 = 61 523/4 249/1 244); `referenz_export.mjs` → neue
`.edbak` und CSV-ZIP vom 05.11.2026, die alten Dateien entfernt. Neu
`fixture/abgleich.py` (Stufe-1-Riegel `fixtureabgleich`, ohne Installation)
mit `referenz/umriss.json` als Brücke: die `.edbak` ist versiegelt und zu
öffnen braucht `cryptography`, also hält der Riegel Fixture gegen Umriss
und Umriss gegen die SHA-256 der `.edbak`; `--tief` rechnet den Umriss im
Prüfstand nach. Eintrag in `pruefablauf.json` und `pruefung.yml`,
`Pruefablauf.md` (Riegelliste, 6.11). Ausnahmen: in `csv_umlauf.json` zwei
Regeln gestrichen, die nichts mehr trafen, die gemessenen Zahlen begründet;
`edbak_umlauf.json` nur die Beschreibung. Nachgezogen: `messprotokoll.*`
(638 Anfragen), Bedienprobe `nachtdienst.mjs` (Sollfolge aus `start_sort`
statt fester Uhrzeiten — D22-E1 liegt seit RD-02 früher), Handbuch 3.2,
Technik 4.21 und Runbook, Prüfliste P-R4-16, die `LIESMICH.md` von
Referenzdatensatz, Vergleich und Fixture. RD-04 und RD-05 gehen nicht in
einer Fassung auf: RD-04 ist Code (Neben), RD-05 nur eine Datei unter
`server/` (Korrektur).
*Probleme und Lösung:* (1) F-RD-27 — der erste Abgleich war rot an drei
Segmenten der Stufe 2; Ursache war der Kern, nicht die Fixture. (2) Zwei
Regeln in `csv_umlauf.json` erklärten Abweichungen, die es mit dem neuen
Bestand nicht mehr gibt — gestrichen statt stehen gelassen, eine Regel ohne
Treffer erklärt nichts. (3) `messprotokoll.*` und P-R4-16 standen noch auf
einem älteren Bestand (612 statt 638 Anfragen, 21 statt 22 Dienste; bzw. 104 statt 106 Einsätze); ohne die
Zahlenliste in Schritt 5 wären sie stehen geblieben.
*Nicht getan:* `sichtpruefung.mjs` und Handbuch 11.6 tragen keine Zahl des
Bestands — nichts nachzuziehen.

### RD-07 Demo-Konto: Durchgang und Reparaturen (E-RD-19)

*Wofür:* Die Betreiberin will ein Demo-Konto, an dem **alles** geht.
Dieses Paket fährt jeden Bedienweg einmal mit einer Vorab-Fixture (aus dem
Referenzkonto nach RD-03, erzeugt mit RD-01, eingespielt mit RD-04) und
behebt, was es findet — sofort, im selben Paket, mit Fassung und
Changelog je Behebung.

*Weg:*
1. Liste der Bedienwege aus `docs/Handbuch.md` 3.2 und 11.6, `Technik.md`
   4.21 und `demo_pruefen.mjs`: Anlegen in der Verwaltung, Anmelden,
   Entsperren, Startseite, Tagesansicht, jeder neue Spurfall (Abschnitt 2
   und die Matrix aus RD-02), Schneiden an den drei Übungssegmenten,
   Rückgängig (E-RD-18), Zeitraum löschen, GPX-Abruf, Backup erstellen
   und wieder einspielen (in ein Prüfkonto), CSV-Export, Papierkorb,
   Einstellungen, Änderungsmarke und Reset nach 30 Minuten (im Prüfstand
   mit verschobener Marke, R4-04), Mengenbremse, Banner, Entfernen und
   erneutes Anlegen; dazu das, was der Wartungslauf im Demo-Konto **nicht**
   tun darf (Nr. 459) — und F-RD-08 am Segment „Schnitt nimmt alles".
2. Je Weg: erwartetes Ergebnis, gemessen in Chromium, bei berührtem
   `style.css`/`ui.php` in drei Engines (R89); Fund → `F-RD-NN`,
   Behebung, Messung danach.
3. Was `demo_pruefen.mjs` nicht fährt, aber hier geprüft wurde, kommt
   dort hinein, wenn es sich maschinell messen lässt (Grundsatz 5:
   Anlass-Zeile mit der Fundnummer); sonst in die Handprüfliste (RD-06).

*Ort:* nach Fund — `server/demo_lib.php`, `admin_demo.php`,
`auth_guard.php`, `api/wartungslauf.php`, `assets/wartungslauf.js`,
`api/schneiden.php`, die betroffenen Seiten; `tools/referenzdatensatz/browser/demo_pruefen.mjs`.

*Abnahme:* eine Tabelle im Prüfprotokoll — jeder Bedienweg mit Ergebnis
(Zahl), jeder Fund mit Nummer, Behebung und Nachmessung; `demo_pruefen.mjs`
grün mit der Zahl seiner Schritte; kein offener Demo-Fund beim Übergang
zu RD-05.

*Gebaut (05.11.2026):* Der Durchgang lief mit der Vorab-Fixture aus RD-03
(Format 3, 111 Einsätze, Stufe 1/2/3) auf der örtlichen Anlage, zuerst mit
`demo_pruefen.mjs` wie es war, dann erweitert. Gefunden und behoben wurden
**fünf Fehler am Demo-Konto**, vier davon schon im großen Prüfstand von
RD-04 und dort behoben (F-RD-22 Rücknahme nach dem Verdichten, F-RD-24
Überlauf der Segmentzeile, F-RD-25 Schlüsselpaar nach neuer Fixture, dazu
F-RD-08 Fälligkeit), einer hier (F-RD-26, die Probe selbst).
`demo_pruefen.mjs`: Sollzahlen aus der Fixture, neuer Abschnitt `spurfaelle`
(vor `reset`): jede Stufe an Segment und Einsatz vorhanden und gezeichnet;
Schneiden und Zurücknehmen am Übungssegment der Stufe 2 (P-S11-30 im
Browser); Zurücknehmen auf Stufe 1 geht, auf Stufe 2 nicht (die erwartete
409 zählt nicht als Konsolenfehler, jede andere schon); Zeitraum löschen;
der Wartungslauf nennt dem Demo-Konto 0 Spuren (Nr. 459, am echten
Endpunkt). Keine Änderung unter `server/` in diesem Paket — die Behebungen
stehen in Web 23.8.0 (RD-04).

*Bedienwege und wo sie gemessen sind:* Anlegen, Anmelden, Banner,
Entsperren und Lesen, Startseite, Tagesansicht, Einsatzseite, Löschen eines
Einsatzes, Stammdatum ändern, Spurfälle, Schneiden, Zurücknehmen, Zeitraum
löschen, Reset, Sperre der Konto-Identität, `kdf_upgrade`, Entfernen und
erneutes Anlegen — `demo_pruefen.mjs`. Mobile Segmentzeile — Bilderlauf
(RD-04) und `zeile.mjs`-Messung in drei Engines. Backup und CSV-Export des
Demo-Bestands samt Rücklauf in ein Prüfkonto — RD-05
(`referenz_export.mjs`, Kreisläufe). **Nicht maschinell:** GPX-Abruf einer
gepackten Spur, die 30-Minuten-Frist am echten Uhrwerk, die Mengenbremse
über 20 Anmeldungen — Handprüfliste (RD-06).

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
| RD-02 | **vier** Listen am Dienst nach dem Muster von `schnitte` (`uebungen` dazu, E-RD-20); sechzehn Matrixzeilen; `spur.luecke()`, `uebungsspur()`, `ausgabe/luecken.json`; Verweisprüfung in `aufbauen.py` | Adressierung `segment: client_ref`, `ruhespur()`, `spur.flug/fahrt/halt`, die Straßen aus `routen/`, `pruefen.py --matrix`, der Determinismus des Aufbaus | nichts |
| RD-03 | zwei Stufen in `einspielen.py` | `ortsdaten.py` (öffnen, bauen, packen — Format aus `referenz.py`), `api/wartungslauf.php`, `api/schneiden.php`, die Segmentsuche aus `stufe_schneiden()`, die PHP-Ausdünnung als Referenz (keine Python-Fassung) | nichts |
| RD-04 | `gh_gepackt_bauen()` in PHP (dritte Fassung eines Formats mit einer Referenz, wie Stufe 1); zwei Vektoren der Hüllenprobe; eine Registerzeile; eine Zeile für `erstellt_am` im Demo-Zweig (Q-RD-10) | `spur2_stueck_schreiben()` mit `stufe`/`n_original` (nimmt es schon), `edbak_restore()` Zweig `'server'`, Feldnamen der Nutzlast 13 | der Satz „Gepackt wird nur im Browser" |
| RD-05 | `fixture/abgleich.py` (Q-RD-08; sonst nichts — Referenzexporte und Sollstände werden erneuert, nicht neu gebaut) | `referenz_export.mjs`, `kreislauf.py`, `ausnahmen/*.json`, `pruefablauf.json` | die Fixture vom 27.09.2026 |
| RD-07 | nichts Geplantes — nur Behebungen an vorhandenen Stellen; neue Schritte in `demo_pruefen.mjs` statt eines neuen Werkzeugs | `demo_pruefen.mjs`, die Demo-Marke des Prüfstands (R4-04) | die Fehler, die es findet |
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
| RD-01 vorab | `erzeugen.php` vor der Änderung | Rückgabe, Datei | **Rückgabe 2**, Meldung „erzeugen.php ist seit Web 23 ausser Betrieb …", keine Datei |
| RD-01 vorab | `riegelprobe.php` vor der Änderung | Erwartungen | Vorgabekonto fehlt auf frischer Anlage → **Rückgabe 2**; mit `admin@example.invalid`: **10 Erwartungen, 3 nicht erfüllt** (1a, 1b, 2b), 2a grün ohne Gegenstand (F-RD-16) |
| RD-01 | `erzeugen.php` auf dem Referenzkonto (frisch aufgebaut: `einspielen.py` alle Stufen + `csv_import.mjs`) | Zahlen der Fixture | Rückgabe 0; 22 Diensttage, **109 Einsätze**, 123 Ruhesegmente, 2 Geräte, **221 Spuren, 67 478 Punkte** (alle Stufe 1), 737 Phasenorte, 9 Reanimationen, 3 Schnitte, 0 Löschvermerke, Papierkorb 5/1/5; 886 KB gepackt, 2,9 s |
| RD-01 | Gegenprobe Punktzahl, SQL auf `track_stuecke` | Summe der Stücke | 98 + 123 = **221 Spuren**, 50 096 + 17 382 = **67 478 Punkte**, Stufe nur 1 — gleich der Fixture und gleich der Referenz-`.edbak` vom 19.10.2026 (F-RD-01) |
| RD-01 | Zeilenzahl und `CHECKSUM TABLE` vor/nach `erzeugen.php` | 41 Tabellen | **keine Abweichung** (dreimal gefahren) |
| RD-01 | `demo_zuruecksetzen()` mit der erzeugten Fixture (DB gesichert und danach zurückgelegt) | Reset-Bericht, Dauer | 109 Einsätze, 123 Segmente, 22 Tage, 3 Schnitte übernommen, **0 verworfen, 0 übersprungen**; 3,13 s (ein Lauf, diese Umgebung) |
| RD-01 | Kacheln vor/nach Reset: `zeitraum.php?y=2026` im Browser (Chromium), `admin_demo.php` | Kachelwerte | gleich: **104 Einsätze, 21 Diensttage, Ø 5,0, 4 Sekundärtransporte, 2 590 km**; Admin **21 · 104 · 118 · 2**; 0 Seitenfehler |
| RD-01 | Spuren nach dem Reset, SQL | Stücke, Punkte | 221 Spuren, 67 478 Punkte; 101 Einsatzhüllen statt 106 (F-RD-17) |
| RD-01 | Rundlauf Fixture → Reset → Fixture, Feldvergleich je Eintrag | 232 Einträge | Spur, Stufe, `n_original`, Phasenorte, Reanimation, Schnitte (ohne `erstellt_am`), `pat_blob`, Zeiten **gleich**; abweichend nur `deleted_at` (5, frisch gestempelt), `day_id` (neu vergeben), `konto.name` (der Reset setzt „Demo NutzerIn") und vor der Vereinheitlichung `lat`/`lon` an 5 Einsätzen |
| RD-01 | Prüfvektoren aus `referenz.py` gegen `spur_oeffnen()` | 3 Stücke + 1 Störung | **4 von 4**: Stufe 1, 2 und 3 je 50 Punkte gleich der Referenz (Höhe null und negativ), umetikettiertes `n_original` abgewiesen (`pruefwert`) |
| RD-01 | `riegelprobe.php` nach der Änderung | Erwartungen | **11 von 11**, Rückgabe 0; Fall 2b mit der Meldung des Riegels (`edka1:`) |
| RD-02 vorab | `quelldaten/pruefen.py`, `generator/pruefen.py` vor der Änderung | Matrix, Einzelprüfungen | 98 Matrixzeilen, 0 offen, 7 266 Einzelprüfungen; Generator 339 472 Einzelprüfungen, 0 Befunde |
| RD-02 | Bewegung in den Schnittfenstern, Generator-Ausgabe | km je Stunde | D08 9,3 km (Rückweg), dann 0,3 km/h; D18 21,5 km, dann 0,3; D19 nur Zittern (F-RD-18) |
| RD-02 | `quelldaten/aufbauen.py` | Verweise | 123 Segmente, Kennungen außer D22 unverändert; „Alle Verweise der Listen am Dienst stehen"; Gegenprobe mit verbogenem Verweis gemeldet |
| RD-02 | `quelldaten/pruefen.py` | Matrix, Befunde | **114 Matrixzeilen, 0 offen**, 126 Marken, **7 506 Einzelprüfungen, 0 Befunde**; 1 394 Zeitstempel |
| RD-02 | Gegenprobe `pruefen.py`: Stufe 3 im November, Löschvermerk über dem Schnitt, Vorbild aus fremdem Dienst | Befunde | alle drei gemeldet (dazu die zwei Zählabweichungen), zurückgelegt wieder 0 |
| RD-02 | `generator/erzeugen.py`, `generator/pruefen.py` | Befunde | 69 306 Punkte (Ruhe 17 382 → 18 670), 638 Anfragen; **343 593 Einzelprüfungen, 0 Befunde**; Übungen im Tempo der Grenzen (300/140 km/h) |
| RD-02 | Gegenprobe Lückenprüfung (`luecken.json` verbogen) | Befunde | 3 gemeldet (Sprung nicht erklärt, Sequenzlücke fehlt, Zeitlücke fehlt), zurückgelegt 0 |
| RD-02 | `diff -r` Generator-Ausgabe vor/nach | geänderte Dienste | nur D02, D04, D09, D11, D17, D22 (Lücke, Übung, E1); **16 Dienste byteweise gleich**; zwei Läufe nach der Änderung byteweise gleich |
| RD-02 | Anfragen der Lücken | `seq_from`, Zeit | Sequenz: Anfrage endet bei 158, nächste beginnt bei 179 (20 Nummern); Zeit: Nummern lückenlos, Pause 16 min |
| RD-03 | Aufbau von Null (`einspielen.py` alle 12 Stufen + `csv_import.mjs`) | Stufen | alle ohne Abbruch, 718 Anfragen; Probe „ganze Aufzeichnung löschen" **409 alles**; 5 Schnitte (D22 am Folgetag), 2 Löschungen (8 und 30 Punkte), **20 Verdichtungen** |
| RD-03 | SQL auf `track_stuecke`/`track_cuts` (Referenzkonto) | Lage je Fall | 111 Einsätze, 123 Segmente, 223 Spuren: **Stufe 1/2/3 = 203/10/10 Spuren, 61 523/4 249/1 244 Punkte** (67 016); `track_cuts` 5 Schnitte, 2 Löschvermerke; D18 Stufe 2 n 392 / n_original 487 (65 geschnitten, 30 gelöscht); D19 Stufe 3 60/131; D22 Stufe 2 183/228; Sequenzlücke Stufe 2 229/249; Zeitlücke Stufe 1 65; D20 „nimmt alles" Stufe 1; Übungen D11 Stufe 2, D09/D17 Stufe 1; Rea-Einsatz Stufe 3 172/711 |
| RD-03 | F-RD-08: `spur2_faellig_kandidaten()` direkt | Fälligkeit | **D20 „Schnitt nimmt alles" fällig**, Platz 102 von 200 — bestätigt, Behebung RD-04 |
| RD-03 | Hülle und Kennzeichnung nach `verdichten` | Präfix, `demo_user_id` | `edk1:`, Kennzeichnung zurück |
| RD-03 | `sitzungsprobe.py` | Erwartungen | **2 von 2** |
| RD-03 | `fixture/erzeugen.php` auf dem neuen Referenzkonto (Vorab-Fixture für RD-07) | Zahlen, DB | Rückgabe 0; 111 Einsätze, 223 Spuren, Stufe 1/2/3 = 61 523/4 249/1 244 Punkte — gleich dem SQL; 5 Schnitte, 2 Löschvermerke; DB unverändert (41 Tabellen) |
| RD-04 | `probe_server.php` (Hüllenprobe Server) | Prüfungen | **18, 0 Abweichungen** (vorher 14): Innenform „B fest" und Hülle für Stufe 2 (80/194 Byte) und Stufe 3 (48/178 Byte) byte-gleich der Referenz |
| RD-04 | `referenz.py erzeugen` | geänderte Dateien | nur `vektoren.json`; `Vektoren.mc` und `watch/source/HuelleVektor.mc` byte-gleich |
| RD-04 | Reset mit der Vorab-Fixture (Format 3), dreimal | Bestand, Dauer | 111/123/22, 7 Vermerke übernommen, **0 verworfen**, `rejected` leer; Stufe 1/2/3 = **203/10/10 Spuren, 61 523/4 249/1 244 Punkte**, `n_original` je Fall gleich dem Referenzkonto; **2,91 / 3,17 / 3,07 s** (RD-01 hier mit Stufe-1-Fixture: 3,13 s) |
| RD-04 | Rundlauf Fixture → Reset → `erzeugen.php` → Feldvergleich | 234 Einträge | **0 Abweichungen** (Spur, Stufe, `n_original`, Orte, Rea, Schnitte und Löschvermerke ohne `erstellt_am`, `pat_blob`) |
| RD-04 | nach der Berichtigung (Stempel vor dem Reset): Reset, Zeitstempel, Rücknahme | Antwort | Vermerke 14:30:45, Stücke 14:30:47; D18 und D22 (Stufe 2) **409 verdichtet**, D08 **200, 65 Punkte**; Reset 2,95 s |
| RD-04 | Rücknahme über `api/schneiden.php` nach dem Reset (erster Wurf) | Antwort | D08 (Stufe 1): **200, 65 Punkte zurück** (vorher `409 altschnitt`); D18 (Stufe 2): **409 verdichtet**; Schnitt aus dem Übungssegment D11 (Stufe 2) und Rücknahme: **200 / 200, 109 Punkte** (P-S11-30) |
| RD-04 | F-RD-08: `spur2_faellig_kandidaten()` | Fälligkeit | fällig 199 statt 200, D20 nicht mehr darunter |
| RD-04 | F-RD-24: Segmentzeile D22 (Schnitt) bei 390 und 1200 px, Chromium, Firefox, WebKit | Überlauf, Bedienung | vorher **+122 px** (Bilderlauf); nachher in allen drei Engines **0 px**, „⋯" öffnet das Blatt mit 2 Einträgen, „Schneiden" darin öffnet den Bereich und schließt das Blatt; 0 Seitenfehler |
| RD-04 | F-RD-25: Reset im Wechsel alte Fixture → Vorab-Fixture → alte, danach `erzeugen.php` (öffnet jede Hülle) | Paar, Punkte | je Wechsel **Paar neu**, alle Hüllen offen: 67 292 / 67 016 / 67 292 Punkte; gleiche Fixture zweimal: **Paar bleibt** |
| RD-04 | Prüfstand neben/schmal, vier volle Läufe (je ~48 min) | rot | (1) 4 rot — Wartung durch die Schemaprobe (F-RD-23); (2) 2 rot — Bilderlauf F-RD-24 und Bedienprobe nach einem Demo-Reset mitten im Lauf (meine Handversuche hatten eine Änderungsmarke gesetzt); (3) 2 rot — F-RD-25 (429 an `kontopaar`, 87 Konsolenfehler); (4) **1 rot, 58 grün**: Bilderlauf 1 Aufnahme 503 (`12-einsatzansicht` @ 390, während die Bildaufnahme für 46a die Wartung schaltete, 16:16:53); Bilderlauf allein danach **0 Überlauf, 0 Konsolenfehler** — nicht wiederholbar |
| RD-04 | `tools/zaehlung/zaehlen.php` | Register | 49 Zeilen, 0 über, 0 unter; Z49 = 1 |
| RD-04 | Schemaprobe auf MariaDB 10.11, 10.6, MySQL 8.0, 8.4.0 (`plattform.sh`) | Prüfungen | erst **1 Fehlschlag je Fassung**: Fall 8/3 hatte Schnitt und Stück beide bei ts 0, die neue Fälligkeitsregel nahm ihn zu Recht heraus — Prüffall berichtigt (Schnitt 5–5), Fall 8/6 neu (ganz geschnitten, nicht fällig); danach **52 / 0** auf allen vier |
| RD-05 | Referenzkonto von Null, `erzeugen.php`, SQL-Gegenprobe | Zahlen | gleich RD-03: 111/123/22, 223 Spuren, Stufe 1/2/3 = 61 523/4 249/1 244 Punkte, 5 Schnitte, 2 Löschvermerke |
| RD-05 | `abgleich.py --tief`; Gegenproben | Abweichungen | neue Fixture **gleich**; alte Fixture vom 27.09.2026 **rot, 14 Abweichungen** (F-RD-01); verbogene `umriss.json` **rot**; Selbstprobe **8 von 8** |
| RD-05 | `kreislauf.py --art edbak --frisch` | Vergleiche | **345 356, 0 unerklärt, 22 erwartet** (vorher 347 566 / 22); Stufe und `n_original` direkt: **223 von 223 Spuren gleich** |
| RD-05 | `kreislauf.py --art csv --frisch` | Vergleiche | **11 424, 0 unerklärt, 1 324 erwartet** (vorher 9 120 / 1 021) |
| RD-05 | Demo-Reset mit der eingecheckten Fixture | Bestand, Dauer, Kacheln | 111 Einsätze, **0 verworfen**, **3,17 s**; Kacheln **106 Einsätze, 21 Diensttage, 2 590 km**; Admin **21 · 106 · 118 · 2** |
| RD-05 | Bedienprobe, Weg `nachtdienst` | Wege | **1 von 1** |
| RD-05 | Prüfstand neben/schmal, erster Lauf (2 726 s) | rot | **1 rot, 58 grün, 1 Hinweis**: Wortliste — „Flug" (Handbuch 3.2) und „Spur" (`Pruefablauf.md` 6.11) in neuem Text, ersetzt durch „Weg" und „Aufzeichnung", Textprobe danach 0 Treffer; Hinweis `erzeugen`: Tabelle in `Pruefablauf.md` 4 neu erzeugt (neuer Riegel), gehört in den Commit. Der Bericht des zweiten Laufs steht im Commit |
| RD-07 vorab | `demo_pruefen.mjs` wie es war, gegen die Vorab-Fixture | Prüfungen | 24, **4 Befunde** (Sollzahlen 83/15/95 aus der Zeit vor dem Demo-Ausbau, F-RD-26), 0 Konsolenfehler |
| RD-07 | `demo_pruefen.mjs lesen,spurfaelle,reset` (erweitert) | Prüfungen | 26, 0 Befunde, **1 Konsolenfehler** = die erwartete 409 der verweigerten Rücknahme → in der Probe ausgenommen |
| RD-07 | `demo_pruefen.mjs` vollständig nach `demo_entfernen()` (anlegen … sperren) | Prüfungen | **41, 0 Befunde, 0 Konsolenfehler, 0 nicht gemessen**; Stufe 2/3 je 5 Segmente und 5 Einsätze; Tag mit Stufe-3-Segment 17 Linien, Stufe-3-Einsatz 1 Linie; Übung: 169 Punkte geschnitten, zurückgenommen; Stufe 1 zurück, Stufe 2 „zusammengefasst"; Zeitraum löschen 10 Punkte; Wartungslauf 0 Spuren; Admin-Zahlen vor/nach Reset gleich der Fixture (21/106/118/2, Papierkorb 5) |
| RD-06 | Prüfstand (Bericht im Commit) | Proben | nur Dokumente unter `docs/konzepte/` berührt; Bericht im Commit |

**Grenzen, die jetzt schon feststehen:** Die Staging-Prüfung mit dem
Demo-Konto (P-S11-30 neu) geht erst nach dem Merge (R91). Ein echter
Safari bleibt Handprüfung. *(Bis RD-06 stand hier auch „F-RD-08 misst erst
RD-03" — gemessen in RD-03, behoben in RD-04.)*

## 8. Verlauf

| Datum | Was |
|---|---|
| 05.11.2026 | Konzept angelegt: Befund F-RD-01 bis -13, Fragen Q-RD-01 bis -09, Entscheidungen E-RD-01 bis -07, Pakete RD-01 bis -06. Fahrplanzeile 24 auf „Konzept", Backlog-Spanne 510–519 reserviert. |
| 05.11.2026 | Nach Rückfrage der Betreiberin (Wildwuchs, Q-RD-04, Übungstage): F-RD-04 und F-RD-11 berichtigt (Python hat das Format einmal; die Segmentkennung überlebt den Aufbau), E-RD-04 auf die vorhandene Adressierung `segment: client_ref` umgestellt, Q-RD-04 mit Lesart (d) „Übungssegmente, zwei auf Stufe 1, eines auf Stufe 2" als Vorschlag, E-RD-08 und Abschnitt 6 (Wildwuchs-Prüfung) neu; Matrixmarken von 14 auf 12 (10 Fälle). |
| 05.11.2026 | Zweite Rückfrage („geschnittene Tage?"): F-RD-14 (Demo-Schnitte gelten auf jeder Anlage als alt, Rücknahme antwortet 409 `altschnitt`), Q-RD-10 mit Vorschlag (a), die drei bestehenden Schnitte auf Stufe 1/2/3 verteilt (Q-RD-03), Matrix „Schnittzustand × Stufe" in `pruefen.py`, Marken auf 14. |
| 05.11.2026 | **Freigabe:** Q-RD-01 bis -10 wie vorgeschlagen (E-RD-09 bis -18). Dazu E-RD-19: Jeder Fehler am Demo-Konto wird in diesem Schritt behoben — neues Paket RD-07 (Demo-Durchgang) vor RD-05, der Browserweg ist nicht mehr ausgenommen, F-RD-08 wird bei Bestätigung behoben. Fahrplanzeile auf „freigegeben". |
| 05.11.2026 | **RD-06:** Prüfdokument (14 Handpunkte P-RD-01 bis -14, P-S11-30 und -31 neu gefasst), Pull Request geöffnet. Abschluss (Rahmenplan, Prüfliste, Backlog, Konzept löschen) nach Merge und Freigabe. |
| 05.11.2026 | **RD-05 erledigt (Web 23.8.1):** neue Fixture, Referenz-`.edbak` und CSV vom 05.11.2026, Kreisläufe neu begründet, Stufe-1-Riegel `fixtureabgleich`; F-RD-27. |
| 05.11.2026 | **RD-07 erledigt:** Durchgang über die Bedienwege des Demo-Kontos mit der Vorab-Fixture; fünf Funde behoben (vier davon in RD-04), `demo_pruefen.mjs` mit Sollzahlen aus der Fixture und Abschnitt `spurfaelle` (41 Prüfungen). F-RD-26. |
| 05.11.2026 | **RD-04 erledigt (Web 23.8.0):** Reset packt Stufe 2/3 aus Format 3, Einspielzeit für Demo-Vermerke, `gh_gepackt_bauen()` mit Vektoren; dazu F-RD-22 (Rücknahme nach Verdichten) und F-RD-08 (Fälligkeit) für alle Konten behoben. |
| 05.11.2026 | **RD-03 erledigt:** Stufen `loeschen` und `verdichten`, Tagesversatz aus dem Datum, PHP-Ausdünnung über `ausduennen_cli.php`; Referenzkonto von Null mit allen Fällen; F-RD-08 bestätigt; Befunde F-RD-20, -21. |
| 05.11.2026 | **RD-02 erledigt:** vier Spurfall-Listen, 16 Matrixzeilen, Lücken und Übungen im Generator; Befunde F-RD-18, -19, Entscheidungen der Umsetzung E-RD-20 bis -22 (Übungsliste mit Vorbild, Tagesversatz aus dem Datum, D22-E1 vorgezogen). |
| 05.11.2026 | **RD-01 erledigt:** `erzeugen.php` erzeugt wieder (Format 3, 67 478 Punkte), Riegelprobe 11 von 11, `fixture/LIESMICH.md` neu. Befunde F-RD-15 bis -17. Statusblock: „wartet auf Q-RD-…" bei RD-02 und RD-04 gestrichen (seit der Freigabe überholt). |
